<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);
$country="th";
?>
<?php if($OfferApi->targetLanguage=="zh-hant" || $OfferApi->targetLanguage=="zh-hans"){ ?>
            <div class="tw-mb-2 tw-flex tw-gap-1 tw-text-[#4D4D4D]" style="font-size:0.75em; padding: 7px;border: 1px solid #f6ca79;background: #fef6e9;line-height: 1.3;" >
                <span aria-hidden="true" style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:1.3em;height:1.3em;border-radius:3px;background:#2f7cf6;color:#fff;font-family:Georgia,'Times New Roman',serif;font-weight:bold;font-style:normal;font-size:1em;line-height:1;">i</span><span><?= $collector_sh->translate("type_english_us", $OfferApi->targetLanguage=="zh-hant" ? "請以英文填寫以下運送資訊。" : "请用英语填写以下发货信息。") ?></span>
            </div>
<?php } ?>

<style>
#fields_state,
#fields_city,
#fields_district {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23343a40' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 9px 5px;
    padding-right: 32px;
}
.th-field-hint {
    color: #6b7280;
    font-size: 0.8em;
    margin-top: 4px;
}

input:disabled {
  cursor: default;
  background-color: #f5f4f4 !important;
}
</style>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_state"><?= $collector_sh->translate("state_th", "Province"); ?></label>

    <div class="self-autocomplete">
        <input id="fields_state" 
            name="fields_state"
            class="cart-input p search" type="text"                 
            trigger="click focus"  
            toenableel="fields_city"
            readonly="readonly"
            toSearch="<?= ($OfferApi->targetLanguage!="en")? "state_th" : "state_en"; ?>" 
            placeholder="<?= $collector_sh->translate("p_province_new".$country, "Select Province",false); ?>"/> 
            <div class="self-suggestions" style="display:none;"></div>                    
    </div> 
</div>

<div class="mb-3">
    <?php $p_district_th = $collector_sh->translate("p_district_th", "Select province first",false); ?>
    <label class="p cart-input-label" for="fields_city"><?= $collector_sh->translate("district_th", "District"); ?></label>
    <div class="self-autocomplete">
        <input id="fields_city" 
            name="fields_city"
            class="cart-input p search" type="text"                 
            trigger="click focus"  
            toenableel="fields_district"
            disabled="disabled"
            readonly="readonly"
            toSearch="<?= ($OfferApi->targetLanguage!="en")? "city_th" : "city_en"; ?>" 
            onPlaceholder="<?=$p_district_th; ?>"
            placeholder="<?= $p_district_th; ?>"
            onEnabled="<?=$collector_sh->translate("district_onenabled_".$country, "Select district") ?>"/>
            <div class="self-suggestions" style="display:none;"></div>                    
    </div> 
    
</div>

<div class="mb-3">
    <?php $p_subdistrict_th = $collector_sh->translate("p_subdistrict_th", "Select district first"); ?>
    <label class="p cart-input-label" for="fields_district"><?= $collector_sh->translate("subdistrict_th", "Sub-district"); ?></label>

    <div class="self-autocomplete">
        <input id="fields_district" 
            name="fields_district"
            class="cart-input p search" type="text"                 
            trigger="click focus" 
            disabled="disabled"
            readonly="readonly"
            toSearch="<?= ($OfferApi->targetLanguage!="en")? "district_th" : "district_en"; ?>" 
            onPlaceholder="<?=$p_subdistrict_th; ?>"
            placeholder="<?= $p_subdistrict_th; ?>"
            onEnabled="<?=$collector_sh->translate("sub_district_onenabled_".$country, "Select sub-district") ?>"/>
            <div class="self-suggestions" style="display:none;"></div>                    
    </div> 
    <div id="th_postal_hint" class="th-field-hint" style="display:none;"><?= $collector_sh->translate("postal_code_hint_new_th", "Postal code: "); ?><span id="th_postal_code"></span></div>
</div>

<input type="hidden" id="fields_zip" name="zip" value="">
<input type="hidden" id="fields_address2" name="address2" value="">

<div class="mb-3">
    <label class="p cart-input-label" for="fields_address1"><?= $collector_sh->translate("street_address_th", "Address"); ?></label>
    <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_street_address_th", "e.g. 123/45 Moo 4 Sukhumvit Road"); ?>" required="required">
    <div class="th-field-hint"><?= $collector_sh->translate("street_address_hint_th", "House number, moo, soi, road"); ?></div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_apt_unit"><?= $collector_sh->translate("unit_th", "Floor / Room (optional)"); ?></label>
    <input id="fields_apt_unit" name="apt_unit" class="cart-input p" value="" type="text" optional placeholder="<?= $collector_sh->translate("p_unit_th", "e.g. Floor 8 Room 802"); ?>">
</div>

<script>
(function () {

    var provincePlaceholder = <?= json_encode($collector_sh->translate("p_state_th", "Select province")); ?>;
    var msgInvalidProvince = <?= json_encode($collector_sh->translate("invalid_province_th", "Please select a province.")); ?>;
    var msgInvalidDistrict = <?= json_encode($collector_sh->translate("invalid_district_th", "Please select a district.")); ?>;
    var msgInvalidSubdistrict = <?= json_encode($collector_sh->translate("invalid_subdistrict_th", "Please select a sub-district.")); ?>;
    var msgInvalidStreetAddress = <?= json_encode($collector_sh->translate("invalid_street_address_th", "Please enter your address.")); ?>;

    var provinceEl = document.getElementById('fields_state');
    var districtEl = document.getElementById('fields_city');
    var subdistrictEl = document.getElementById('fields_district');
    var zipEl = document.getElementById('fields_zip');
    var hintEl = document.getElementById('th_postal_hint');
    var postalCodeEl = document.getElementById('th_postal_code');
    var aptUnitEl = document.getElementById('fields_apt_unit');
    var address2El = document.getElementById('fields_address2');

    // self-host-address-suggestion.js assigns .value directly on #fields_zip
    // and #fields_district (both when a suggestion is picked and when the
    // cascade clears them) without dispatching any event, so hook the value
    // setter on those elements to know whenever they change.
    function watchValue(el, callback) {
        if (!el) { return; }
        var desc = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
        Object.defineProperty(el, 'value', {
            configurable: true,
            get: function () { return desc.get.call(this); },
            set: function (v) { desc.set.call(this, v); callback(); }
        });
        el.addEventListener('input', callback);
        el.addEventListener('change', callback);
    }

    function syncPostalHint() {
        var zip = (zipEl.value || '').trim();
        postalCodeEl.textContent = zip;
        hintEl.style.display = zip !== '' ? 'block' : 'none';
    }

    function syncAddress2() {
        address2El.value = [aptUnitEl.value, subdistrictEl.value]
            .map(function (v) { return (v || '').trim(); })
            .filter(function (v) { return v !== ''; })
            .join(', ');
    }

    watchValue(zipEl, syncPostalHint);
    watchValue(subdistrictEl, syncAddress2);
    aptUnitEl.addEventListener('input', syncAddress2);
    aptUnitEl.addEventListener('change', syncAddress2);
    syncPostalHint();
    syncAddress2();

    // Field-specific inline error messages (blur + submit) - the shared
    // validateForm() in integrated.js only has a generic "Required Field."
    // message for #fields_state/#fields_city/#fields_address1, and its
    // "district" message is written for Indonesia's kecamatan. Using the
    // same window.CRMIntegrated.inlineErrorMsg() renderer keeps the look
    // identical while giving each Thai field its own wording.
    var addressEl = document.getElementById('fields_address1');
    var thRequiredFields = [
        { el: provinceEl, msg: msgInvalidProvince },
        { el: districtEl, msg: msgInvalidDistrict },
        { el: subdistrictEl, msg: msgInvalidSubdistrict },
        { el: addressEl, msg: msgInvalidStreetAddress }
    ];

    function showThFieldError(field) {
        var el = field.el;
        if (!el || el.offsetParent === null) { return; } // skip if hidden/disabled out of flow
        var value = (el.value || '').trim();
        var valid = value !== '';
        if (window.CRMIntegrated && typeof window.CRMIntegrated.inlineErrorMsg === 'function') {
            window.CRMIntegrated.inlineErrorMsg(el, value, valid, field.msg);
        }
        el.classList.toggle('error', !valid);
    }

    thRequiredFields.forEach(function (field) {
        if (!field.el) { return; }
        field.el.addEventListener('blur', function () { showThFieldError(field); });
        field.el.addEventListener('change', function () { showThFieldError(field); });
    });

    var submitBtnEl = document.getElementById('submitForm');
    if (submitBtnEl) {
        submitBtnEl.addEventListener('click', function () {
            // Runs after the shared validateForm() handler (registered earlier,
            // during initial page parse) so these messages are the ones left
            // showing instead of the generic/Indonesia-worded defaults.
            setTimeout(function () {
                thRequiredFields.forEach(showThFieldError);
            }, 0);
        });
    }
})();
</script>
<?php $collector_sh->saveTranslation(); ?>
