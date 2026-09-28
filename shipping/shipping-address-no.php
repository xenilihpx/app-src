<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);
$country = "no";
$autoCityPlaceholder = $collector_sh->translate("p_town_city_" . $country, "Filled in automatically"); // used below in both the HTML placeholder and the JS
?>

<!-- Postnummer + Poststed side by side (1/3 + 2/3) - poststed is auto-filled (readonly) from the
     postnummer lookup below; if the code isn't found it unlocks for manual entry. -->
<div class="row align-items-start mb-3">
    <div class="col-sm-4">
        <div class="mb-3">
            <label class="p cart-input-label" for="fields_zip"><?= $collector_sh->translate("zip_" . $country, "Postal code") ?></label>
            <input id="fields_zip" name="zip" class="cart-input p" value="" type="text" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" placeholder="<?= $collector_sh->translate("p_zip_" . $country, "E.g. 0161") ?>" required="required">
        </div>
    </div>
    <div class="col-sm-8">
        <div class="mb-3">
            <label class="p cart-input-label" for="fields_city"><?= $collector_sh->translate("town_city_" . $country, "Postal town") ?></label>
            <input id="fields_city" name="city" class="cart-input p" value="" type="text" readonly placeholder="<?= htmlspecialchars($autoCityPlaceholder) ?>" required="required">
        </div>
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_address1"><?= $collector_sh->translate("address_1_" . $country, "Street address") ?></label>
    <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_address_1_" . $country, "E.g. Karl Johans gate 15") ?>" required="required">
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_address2"><?= $collector_sh->translate("address_2_" . $country, "Apartment / floor (optional)") ?></label>
    <input id="fields_address2" name="address 2" class="cart-input p" value="" type="text" optional placeholder="<?= $collector_sh->translate("p_address_2_" . $country, "E.g. H0301, 3rd floor") ?>">
</div>

<!-- Norway has no state/province in its address format; keep the (hidden, optional) field so the
     shared checkout JS still finds #fields_state -->
<div class="mb-3 d-none">
    <label class="p cart-input-label" for="fields_state"><?= $collector_sh->translate("state_" . $country, "State / Province") ?></label>
    <select id="fields_state" name="state" class="cart-input p" optional>
        <option value=""><?= $collector_sh->translate("p_state_" . $country, "Select State / Province") ?></option>
    </select>
</div>

<script>
(function () {
    var zip = document.getElementById('fields_zip');
    var city = document.getElementById('fields_city');
    var address1 = document.getElementById('fields_address1');
    var autoPlaceholder = <?= json_encode($autoCityPlaceholder) ?>;
    var manualPlaceholder = <?= json_encode($collector_sh->translate("p_town_city_manual_" . $country, "Enter your postal town")) ?>;
    var lookupTimer = null;
    var lastLookedUp = '';

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

    // Self-hosted lookup (integrated/?no_pnr=XXXX -> OfferApi::lookupPostnummerNO()) - no live call
    // to Bring. One exact lookup per complete 4-digit code, no cascade. kategori/street_ok are
    // resolved again server-side when the order is built (see OfferApi::postnummerCategoryNO());
    // they are intentionally never sent from this form.
    function lookup(pnr) {
        if (pnr === lastLookedUp) return;
        lastLookedUp = pnr;
         fetch('integrated/?search_data=true&q=' + encodeURIComponent(pnr) +'&country='+document.getElementById('fields_country_select').value.toLowerCase())
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (zip.value.trim() !== pnr) return; // zip changed while the request was in flight
                if (data && data.valid && data.poststed) {
                    lockAuto();
                    city.value = data.poststed;
                    city.classList.remove('error');
                    city.nextElementSibling.remove();
                } else {
                    unlockManual(true);
                }
            })
            .catch(function () {
                if (zip.value.trim() === pnr) unlockManual(true);
            });
    }

    zip.addEventListener('input', function () {
        var digits = zip.value.replace(/[^0-9]/g, '').slice(0, 4);
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
    function inlineError(field, valid, msg) {
        var next = field.nextElementSibling;
        if (next && next.classList && next.classList.contains('error-message')) next.parentNode.removeChild(next);
        field.classList.toggle('error', !valid);
        field.classList.toggle('valid', valid);
        if (!valid) {
            var span = document.createElement('span');
            span.className = 'error-message';
            span.style.color = 'red';
            span.style.fontSize = '14px';
            span.textContent = msg;
            field.parentNode.insertBefore(span, field.nextSibling);
        }
    }

    zip.addEventListener('blur', function () {
        var valid = /^[0-9]{4}$/.test(zip.value.trim());
        inlineError(zip, valid, (window.i18nData && window.i18nData['invalid_zip_no']) || '4 digits.');
    });

    city.addEventListener('blur', function () {
        var valid = city.value.trim() !== '';
        inlineError(city, valid, (window.i18nData && window.i18nData['invalid_city_no']) || 'Enter postal code first.');
    });

    address1.addEventListener('blur', function () {
        var valid = address1.value.trim() !== '';
        inlineError(address1, valid, (window.i18nData && window.i18nData['invalid_address_1_no']) || 'Enter street address and number.');
    });
})();
</script>
<?php $collector_sh->saveTranslation(); ?>
