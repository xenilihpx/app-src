<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);
$country="rs";
?>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_address1"><?= $collector_sh->translate("address_".$country, "Street address") ?></label>
    <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_address_".$country, "E.g. Bulevar kralja Aleksandra 45") ?>" required="required">
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_address2"><?= $collector_sh->translate("address2_".$country, "Floor / apartment (optional)") ?></label>
    <input id="fields_address2" name="address 2" class="cart-input p" value="" type="text" optional placeholder="<?= $collector_sh->translate("p_address2_".$country, "E.g. 3rd floor, apt. 12") ?>">
</div>

<div class="row align-items-start mb-3">
    <div class="col-sm-4">
        <div class="mb-3">
            <label class="p cart-input-label" for="fields_zip"><?= $collector_sh->translate("zip_".$country, "Postal code") ?></label>
            <input id="fields_zip" name="zip" class="cart-input p" value="" type="text" inputmode="numeric" maxlength="5" pattern="[0-9]{5}" placeholder="<?= $collector_sh->translate("p_zip_".$country, "E.g. 11000") ?>" required="required">
        </div>
    </div>
    <div class="col-sm-8">
        <div class="mb-3">
            <label class="p cart-input-label" for="fields_city"><?= $collector_sh->translate("town_city_".$country, "Town / city") ?></label>
            <input id="fields_city" name="city" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_town_city_".$country, "E.g. Beograd") ?>" required="required">
        </div>
    </div>
</div>

<!-- Serbia has no district/region or state/province field in this build; keep the (hidden,
     optional) field so the shared checkout JS still finds #fields_state -->
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
        inlineError(this, valid, (window.i18nData && window.i18nData['invalid_address_rs']) || 'Enter street and house number.');
    });

    // Postal code accepts digits only, capped at 5 - strip anything else as the shopper types
    // (maxlength alone only limits length, it doesn't block non-digit characters).
    document.getElementById('fields_zip').addEventListener('input', function () {
        var digits = this.value.replace(/[^0-9]/g, '').slice(0, 5);
        if (digits !== this.value) this.value = digits;
    });

    document.getElementById('fields_zip').addEventListener('blur', function () {
        var valid = /^[0-9]{5}$/.test(this.value.trim());
        inlineError(this, valid, (window.i18nData && window.i18nData['invalid_zip_rs']) || '5 digits.');
    });

    document.getElementById('fields_city').addEventListener('blur', function () {
        var valid = this.value.trim() !== '';
        inlineError(this, valid, (window.i18nData && window.i18nData['invalid_city_rs']) || 'Enter the town or city.');
    });
})();
</script>
<?php $collector_sh->saveTranslation(); ?>
