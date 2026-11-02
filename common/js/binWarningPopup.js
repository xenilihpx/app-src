// Checkout BIN warning popup (Taiwan and Japan).
// Warns (never blocks) when the card's first 6 digits match a BIN listed for the user's country
// in src/common/data/decline-bins.json. Copy lives in window.i18nData (src/system/<lang>.js), keys bin_warning_*.
var CRMBinWarningPopup = (function () {
    var cardField = "#cc_number",
        submitBtn = "#submitForm",
        binListUrl = "src/common/data/decline-bins.json",
        storageKey = "bin_warning_shown",
        bins = {}; // "404378" => "ESUN"

    function t(key, fallback) {
        return (window.i18nData && window.i18nData[key]) || fallback;
    }

    // first 6 digits of the card number field + its bank key, or null when not on the list
    function matchCard() {
        var digits = ($(cardField).val() || "").replace(/[\s-]/g, "");
        if (!/^\d{6}/.test(digits)) {
            return null;
        }
        var bin = digits.substr(0, 6);
        return bins.hasOwnProperty(bin) ? { bin: bin, bank: bins[bin] } : null;
    }

    // tracked by BIN so the full card number is never kept in browser storage
    function shownList() {
        try {
            return JSON.parse(sessionStorage.getItem(storageKey)) || [];
        } catch (e) {
            return [];
        }
    }

    function alreadyShown(bin) {
        return shownList().indexOf(bin) != -1;
    }

    function markShown(bin) {
        var list = shownList();
        list.push(bin);
        try {
            sessionStorage.setItem(storageKey, JSON.stringify(list));
        } catch (e) {}
    }

    function funnelName() {
        var params = new URLSearchParams(window.location.search),
            funnel = [];
        $.each(["custom_config", "sub8", "sub10", "pr"], function (i, key) {
            if (params.get(key)) {
                funnel.push(key + "=" + params.get(key));
            }
        });
        return funnel.join("&") || "default";
    }

    function logShown(match, trigger) {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: "bin_warning_shown",
            bin_warning_bin: match.bin,
            bin_warning_bank: match.bank,
            bin_warning_page: window.location.pathname,
            bin_warning_funnel: funnelName(),
            bin_warning_trigger: trigger, // typed or submit
            bin_warning_timestamp: new Date().toISOString()
        });
    }

    function addStyles() {
        if ($("#bin_warning_style").length != 0) {
            return;
        }
        var css = "";
        css += "#bin_warning_overlay{position:fixed;top:0;left:0;width:100%;height:100%;z-index:2147483647;background:rgba(0,0,0,.55);overflow-y:auto;-webkit-overflow-scrolling:touch}";
        css += "#bin_warning_overlay .bin_warning_body{max-width:460px;width:92%;margin:100px auto;padding:24px 20px 16px;background:#fff;border-radius:10px;box-shadow:0 6px 24px rgba(0,0,0,.3);box-sizing:border-box;color:#222;font-size:15px;line-height:1.5;text-align:left}";
        css += "#bin_warning_overlay .bin_warning_message{margin:0 0 12px;font-weight:700}";
        css += "#bin_warning_overlay .bin_warning_note{margin:0 0 20px;padding:10px 12px;background:#fff6e5;border-left:3px solid #f0a500;font-size:14px}";
        css += "#bin_warning_overlay .bin_warning_primary{display:block;width:100%;padding:12px;border:0;border-radius:6px;background:#222;color:#fff;font-size:16px;font-weight:700;cursor:pointer}";
        css += "#bin_warning_overlay .bin_warning_secondary{display:block;margin:12px auto 0;padding:4px;background:none;border:0;color:#666;font-size:14px;text-decoration:underline;cursor:pointer}";
        css += "@media(max-width:767px){#bin_warning_overlay .bin_warning_body{margin:40px auto}}";
        $("head").append('<style id="bin_warning_style">' + css + "</style>");
    }

    function close() {
        $("#bin_warning_overlay").remove();
        $(document).off("keydown.binWarning");
    }

    function show(match, trigger) {
        if ($("#bin_warning_overlay").length != 0) {
            return;
        }
        markShown(match.bin);
        addStyles();

        var bankName = t("bin_warning_bank_" + match.bank, match.bank);
        var overlay = $('<div id="bin_warning_overlay" role="dialog" aria-modal="true">' +
                            '<div class="bin_warning_body">' +
                                '<p class="bin_warning_message"></p>' +
                                '<p class="bin_warning_note"></p>' +
                                '<button type="button" class="bin_warning_primary"></button>' +
                                '<button type="button" class="bin_warning_secondary"></button>' +
                            '</div>' +
                        '</div>');

        overlay.find(".bin_warning_message").text(t("bin_warning_message", "Heads up — your bank is having trouble processing international transactions. Please use a different card or a card from another bank."));
        overlay.find(".bin_warning_note").text(t("bin_warning_note", "Note: {BANK} cards are currently being declined. JCB cards are working normally.").replace("{BANK}", bankName));
        overlay.find(".bin_warning_primary").text(t("bin_warning_use_different_card", "Use a different card"));
        overlay.find(".bin_warning_secondary").text(t("bin_warning_continue", "Continue anyway"));

        // primary: close, clear the card number and focus it
        overlay.find(".bin_warning_primary").on("click", function () {
            close();
            $(cardField).val("").trigger("change").focus();
        });
        // secondary: just close, the customer can submit normally
        overlay.find(".bin_warning_secondary").on("click", close);
        $(document).on("keydown.binWarning", function (e) {
            if (e.keyCode == 27) {
                close();
            }
        });

        $("body").append(overlay);
        overlay.find(".bin_warning_primary").focus();
        logShown(match, trigger);
    }

    function check(trigger) {
        if (!$(cardField).is(":visible")) {
            return false; // card form hidden (PayPal, Klarna, ...)
        }
        var match = matchCard();
        if (!match || alreadyShown(match.bin)) {
            return false;
        }
        show(match, trigger);
        return true;
    }

    function bindEvents() {
        $(document).on("input keyup change", cardField, function () {
            check("typed");
        });

        // re-check on submit (paste/autofill). Capture phase so it runs before CRMIntegrated's submit
        // handler; only this one click is held back, the next submit goes through.
        window.addEventListener("click", function (e) {
            if ($(e.target).closest(submitBtn).length == 0) {
                return;
            }
            if (check("submit")) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        }, true);
    }

    return {
        init: function () {
            var country = ($("#userCountry").val() || "").toUpperCase();
            if ($.inArray(country, ["TW", "JP"]) == -1 || $(cardField).length == 0) {
                return;
            }
            $.ajax({ url: binListUrl, dataType: "json", cache: false }).done(function (data) {
                var list = (data.countries && data.countries[country]) || [];
                if (list.length == 0) {
                    return;
                }
                $.each(list, function (i, item) {
                    bins[item.bin] = item.bank;
                });
                bindEvents();
            });
        }
    };
})();

$(document).ready(function () {
    CRMBinWarningPopup.init();
});
