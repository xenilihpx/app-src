<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);

$country = "lu";
$autoCityPlaceholder = $collector_sh->translate("p_town_city_" . $country, "Filled in automatically"); // used below in both the HTML placeholder and the JS
?>

<style>
    /* Fixed "L-" prefix in front of the postal code. Visual only - it is NOT part of the value:
    shipPostalCode holds the bare 4 digits. */
    .lu-prefixed { display: flex; align-items: stretch; }
    .lu-prefixed .lu-prefix {
        display: flex; align-items: center; padding: 0 12px;
        border: 1px solid #ccc; border-right: 0;
        background: #f5f5f5; color: #555; font-weight: 600;
    }
    .lu-prefixed input { flex: 1 1 auto; min-width: 0; }
</style>

<!-- Luxembourg: no canton/region line in a postal address, no cascade. The 4-digit code postal
     identifies the localite, which auto-fills (readonly) from the lookup below; if the code isn't
     found it unlocks for manual entry so an order is never blocked. The country dropdown above is
     the checkout's own real country list (always first). -->
<div class="row align-items-start mb-3">
    <div class="col-sm-4">
        <div class="mb-3">
            <label class="p cart-input-label" for="fields_zip"><?= $collector_sh->translate("zip_" . $country, "Postal code") ?></label>
            <div class="lu-prefixed" id="lu_zip_wrap">
                <span class="lu-prefix">L-</span>
                <input id="fields_zip" name="zip" class="cart-input p" value="" type="text" inputmode="numeric" autocomplete="postal-code" maxlength="4" pattern="[0-9]{4}" placeholder="1234" required="required">
            </div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="mb-3">
            <label class="p cart-input-label" for="fields_city"><?= $collector_sh->translate("town_city_" . $country, "Locality") ?></label>
            <input id="fields_city" name="city" class="cart-input p" value="" type="text" autocomplete="address-level2" readonly placeholder="<?= htmlspecialchars($autoCityPlaceholder) ?>" required="required">
        </div>
    </div>
</div>

<!-- Number FIRST, then the street (Luxembourg order), merged into one line -->
<div class="mb-3">
    <label class="p cart-input-label" for="fields_address1"><?= $collector_sh->translate("address_1_" . $country, "Street address") ?></label>
    <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" autocomplete="address-line1" placeholder="<?= $collector_sh->translate("p_address_1_" . $country, "E.g. 8A Avenue Monterey") ?>" required="required">
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_address2"><?= $collector_sh->translate("address_2_" . $country, "Floor / apartment (optional)") ?></label>
    <input id="fields_address2" name="address 2" class="cart-input p" value="" type="text" optional autocomplete="address-line2" placeholder="<?= $collector_sh->translate("p_address_2_" . $country, "E.g. 3rd floor, apt. 12") ?>">
</div>

<!-- Luxembourg has no state/province in its address format; keep the (hidden, optional) field so the
     shared checkout JS still finds #fields_state -->
<div class="mb-3 d-none">
    <select id="fields_state" name="state" class="cart-input p" optional>
        <option value=""></option>
    </select>
</div>

<script>
(function () {
    var zip = document.getElementById('fields_zip');
    var zipWrap = document.getElementById('lu_zip_wrap');
    var city = document.getElementById('fields_city');
    var address1 = document.getElementById('fields_address1');
    var autoPlaceholder = <?= json_encode($autoCityPlaceholder) ?>;
    var manualPlaceholder = <?= json_encode($collector_sh->translate("p_town_city_manual_" . $country, "Enter the locality")) ?>;
    var lookupTimer = null;
    var lastLookedUp = '';

    // Bare 4 digits only. Tolerates a pasted "L-1234" (the "L-" is shown as a fixed prefix, never stored).
    function normalizeCp(v) {
        return String(v).replace(/^\s*[Ll]\s*-?\s*/, '').replace(/\D/g, '').slice(0, 4);
    }

    function lockAuto() {
        city.setAttribute('readonly', 'readonly');
    }

    function unlockManual(clearValue) {
        city.removeAttribute('readonly');
        if (clearValue) city.value = '';
        city.placeholder = manualPlaceholder;
    }

    function resetToEmpty() {
        lastLookedUp = '';
        lockAuto();
        city.value = '';
        city.placeholder = autoPlaceholder;
    }

    // Self-hosted lookup (integrated/?lu_cp=XXXX -> OfferApi::lookupCodePostalLU()) - no live external
    // call. One exact lookup per complete 4-digit code, no cascade. Found -> locality auto-fills and
    // stays readonly; not found (or request failed) -> unlock for manual entry, never block the order.
    // street_ok (0 = boite postale range) is resolved server-side and is intentionally not used here.
    function lookup(cp) {
        if (cp === lastLookedUp) return;
        lastLookedUp = cp;
        fetch('integrated/?search_data=true&q=' + encodeURIComponent(cp) +'&country='+document.getElementById('fields_country_select').value.toLowerCase())
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (zip.value.trim() !== cp) return; // code changed while the request was in flight
                if (data && data.valid && data.localite) {
                    lockAuto();
                    city.value = data.localite;
                    city.placeholder = autoPlaceholder;
                    city.classList.remove('error');
                    city.nextElementSibling.remove();
                } else {
                    unlockManual(true);
                }
            })
            .catch(function () {
                if (zip.value.trim() === cp) unlockManual(true);
            });
    }

    zip.addEventListener('paste', function (e) {
        var text = (e.clipboardData || window.clipboardData).getData('text');
        e.preventDefault();
        zip.value = normalizeCp(text);
        zip.dispatchEvent(new Event('input', { bubbles: true }));
    });

    zip.addEventListener('input', function () {
        var digits = normalizeCp(zip.value);
        if (digits !== zip.value) zip.value = digits;
        clearTimeout(lookupTimer);
        if (digits.length === 4) {
            lookupTimer = setTimeout(function () { lookup(digits); }, 250);
        } else {
            resetToEmpty();
        }
    });

    // Inline error messages, shown on blur. Self-contained (doesn't depend on the shared
    // form_obj instance, which isn't reliably reachable from this dynamically-loaded partial) -
    // matches the same "<span class='error-message'>" markup the rest of the checkout uses.
    // "anchor" is the element the message is inserted after (the wrapper for the prefixed postal code,
    // so the message doesn't land inside the flex row).
    function inlineError(field, anchor, valid, msg) {
        anchor = anchor || field;
        var next = anchor.nextElementSibling;
        if (next && next.classList && next.classList.contains('error-message')) next.parentNode.removeChild(next);
        field.classList.toggle('error', !valid);
        if (!valid) {
            var span = document.createElement('span');
            span.className = 'error-message';
            span.style.color = 'red';
            span.style.fontSize = '14px';
            span.textContent = msg;
            anchor.parentNode.insertBefore(span, anchor.nextSibling);
        }
    }

    zip.addEventListener('blur', function () {
        var valid = /^[0-9]{4}$/.test(zip.value.trim());
        inlineError(zip, zipWrap, valid, (window.i18nData && window.i18nData['invalid_zip_lu']) || '4 digits.');
    });

    city.addEventListener('blur', function () {
        var valid = city.value.trim() !== '';
        inlineError(city, null, valid, (window.i18nData && window.i18nData['invalid_city_lu']) || 'Enter the postal code first.');
    });

    address1.addEventListener('blur', function () {
        var valid = address1.value.trim() !== '';
        inlineError(address1, null, valid, (window.i18nData && window.i18nData['invalid_address_1_lu']) || 'Enter the number and street.');
    });
})();
</script>
<?php $collector_sh->saveTranslation(); ?>

