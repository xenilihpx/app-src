<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);
$country="nz";
?>
<?php if($OfferApi->targetLanguage=="zh-hant" || $OfferApi->targetLanguage=="zh-hans"){ ?>
            <div class="tw-mb-2 tw-flex tw-gap-1 tw-text-[#4D4D4D]" style="font-size:0.75em; padding: 7px;border: 1px solid #f6ca79;background: #fef6e9;line-height: 1.3;" >
                <span aria-hidden="true" style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:1.3em;height:1.3em;border-radius:3px;background:#2f7cf6;color:#fff;font-family:Georgia,'Times New Roman',serif;font-weight:bold;font-style:normal;font-size:1em;line-height:1;">i</span><span><?= $collector_sh->translate("type_english_nz", "Please fill in the following shipping information in English.") ?></span>
            </div>
<?php } ?>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_address1"><?= $collector_sh->translate("address_".$country, "Address") ?></label>
    <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" autocomplete="address-line1" placeholder="<?= $collector_sh->translate("p_address_".$country, "E.g. 12 Queen Street") ?>" required="required">
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_address2"><?= $collector_sh->translate("address2_".$country, "Apartment, suite, building (optional)") ?></label>
    <input id="fields_address2" name="address 2" class="cart-input p" value="" type="text" autocomplete="address-line2" optional placeholder="<?= $collector_sh->translate("p_address2_".$country, "E.g. Unit 3, Harbour House") ?>">
</div>

<!-- Suburb (wide) + Postcode (narrow) side by side -->
<div class="row align-items-start mb-3">
    <div class="col-sm-8">
        <div class="mb-3">
            <label class="p cart-input-label" for="fields_suburb"><?= $collector_sh->translate("suburb_".$country, "Suburb") ?></label>
            <input id="fields_suburb" name="suburb" class="cart-input p" value="" type="text" autocomplete="address-level3" placeholder="<?= $collector_sh->translate("p_suburb_eg_".$country, "E.g. Ponsonby") ?>" required="required">
        </div>
    </div>
    <div class="col-sm-4">
        <div class="mb-3">
            <label class="p cart-input-label" for="fields_zip"><?= $collector_sh->translate("zip_".$country, "Postcode") ?></label>
            <input id="fields_zip" name="zip" class="cart-input p" value="" type="text" inputmode="numeric" autocomplete="postal-code" maxlength="4" pattern="[0-9]{4}" placeholder="<?= $collector_sh->translate("p_zip_eg_".$country, "E.g. 1011") ?>" required="required">
        </div>
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_city"><?= $collector_sh->translate("town_city_label_".$country, "Town / city") ?></label>
    <input id="fields_city" name="city" class="cart-input p" value="" type="text" autocomplete="address-level2" placeholder="<?= $collector_sh->translate("p_town_city_eg_".$country, "E.g. Auckland") ?>" required="required">
</div>

<!-- Region isn't used for NZ delivery; keep the (hidden, optional) field so the shared
     checkout JS still finds #fields_state -->
<div class="mb-3 d-none">
    <select id="fields_state" name="state" class="cart-input p" optional>
        <option value=""></option>
    </select>
</div>

<script>
(function () {
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

    document.getElementById('fields_address1').addEventListener('blur', function () {
        var valid = this.value.trim() !== '';
        inlineError(this, valid, (window.i18nData && window.i18nData['invalid_address_nz']) || 'Enter your street number and name.');
    });

    document.getElementById('fields_suburb').addEventListener('blur', function () {
        var valid = this.value.trim() !== '';
        inlineError(this, valid, (window.i18nData && window.i18nData['invalid_suburb_nz']) || 'Enter your suburb.');
    });

    // Postcode accepts digits only, capped at 4 - strip anything else as the shopper types
    // (maxlength alone only limits length, it doesn't block non-digit characters).
    document.getElementById('fields_zip').addEventListener('input', function () {
        var digits = this.value.replace(/[^0-9]/g, '').slice(0, 4);
        if (digits !== this.value) this.value = digits;
    });

    document.getElementById('fields_zip').addEventListener('blur', function () {
        var valid = /^[0-9]{4}$/.test(this.value.trim());
        inlineError(this, valid, (window.i18nData && window.i18nData['invalid_zip_nz']) || '4 digits.');
    });

    document.getElementById('fields_city').addEventListener('blur', function () {
        var valid = this.value.trim() !== '';
        inlineError(this, valid, (window.i18nData && window.i18nData['invalid_city_nz']) || 'Enter your town or city.');
    });
})();
</script>
<?php $collector_sh->saveTranslation(); ?>
