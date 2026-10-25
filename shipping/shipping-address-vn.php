<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);

$countryKey="vn";
?>
<style>
    #fields_state,
    #fields_city{
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23343a40' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 9px 5px;
        padding-right: 32px;
    }

    .vn-field-hint {
        color: #6b7280;
        font-size: 0.8em;
        margin-top: 4px;
    }
    input:disabled {
        cursor: default;
        background-color: #f5f4f4 !important;
    }
</style>
<?php if($OfferApi->targetLanguage=="zh-hant" || $OfferApi->targetLanguage=="zh-hans"){ ?>
            <div class="tw-mb-2 tw-flex tw-gap-1 tw-text-[#4D4D4D]" style="font-size:0.75em; padding: 7px;border: 1px solid #f6ca79;background: #fef6e9;line-height: 1.3;" >
                <span aria-hidden="true" style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:1.3em;height:1.3em;border-radius:3px;background:#2f7cf6;color:#fff;font-family:Georgia,'Times New Roman',serif;font-weight:bold;font-style:normal;font-size:1em;line-height:1;">i</span><span><?= $collector_sh->translate("type_english_us", "Please fill in the following shipping information in English.") ?></span>
            </div>
<?php } ?>

<div class="mb-3">
    <label class="p cart-input-label" for="state"><?= $collector_sh->translate("state_new_" . $countryKey, "Province / City"); ?></label>
    <div class="self-autocomplete">
        <input id="fields_state" 
            name="fields_state"
            class="cart-input p search" type="text"                 
            trigger="click focus"  
            toenableel="fields_city"
            readonly="readonly"
            toSearch="<?= ($OfferApi->targetLanguage!="en")? "state_vn" : "state_en"; ?>" 
            placeholder="<?= $collector_sh->translate("p_province_new".$countryKey, "Select Province / City",false); ?>"/> 
            <div class="self-suggestions" style="display:none;"></div>                    
    </div>     
</div>

<div class="mb-3 ">
    <?php $p_ward_placeholder = $collector_sh->translate("p_ward_".$countryKey, "Select province / city first",false); ?>
    <label class="p cart-input-label" for="city" label="Town/City"><?=$collector_sh->translate("town_city_" . $countryKey, "Ward / Commune / Special zone"); ?></label>
     <div class="self-autocomplete">
        <input id="fields_city" 
            name="fields_city"
            class="cart-input p search" type="text"                 
            trigger="click focus" 
            disabled="disabled"
            readonly="readonly"
            toSearch="<?= ($OfferApi->targetLanguage!="en")? "city_vn" : "city_en"; ?>" 
            onPlaceholder="<?=$p_ward_placeholder; ?>"
            placeholder="<?= $p_ward_placeholder; ?>"
            onEnabled="<?=$collector_sh->translate("ward_onenabled_".$countryKey, "Select ward / commune") ?>"/>
            <div class="self-suggestions" style="display:none;"></div>                    
    </div> 
     <div id="vn_postal_hint" class="vn-field-hint" style="display:none;"><?= $collector_sh->translate("postal_code_hint_new_".$countryKey, "Postal code: "); ?>&nbsp;<span id="vn_postal_code"></span></div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="address 1" label="Street, house and apartment number"><?=$collector_sh->translate("address_1_new_" . $countryKey, "Address") ?></label>
    <div>
        <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("p_address_1_new_" . $countryKey, "e.g. 123 Nguyen Hue"); ?>" required="required">
    </div>
     <small><?=$collector_sh->translate("address_2_help_" . $countryKey, "House number, alley, street"); ?></small>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="address 2" label="Street, house and apartment number"><?=$collector_sh->translate("address_2_new_" . $countryKey, "Building, apartment (optional)"); ?></label>
    <div>
        <input id="fields_address2" name="address 2" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("p_address_2_new_" . $countryKey, "e.g. Block A, apt 12.05");?>" required="required">
    </div>
   
</div>



<div class="mb-3" style="display:none">
    <label class="p cart-input-label" for="zip" label="Zip/Postal Code"><?= $collector_sh->translate("zip_" . $countryKey, "Zip / Postal Code"); ?></label>
    <div>
        <input id="fields_zip" name="zip" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_zip_" . $countryKey, "Zip/Postal Code (optional)"); ?>" optional>
    </div>
</div>

<script>
    document.getElementById("fields_city").addEventListener('change', function(event){
        var hint = document.getElementById("vn_postal_hint");
        var zip = document.getElementById("fields_zip").value.trim();

        document.getElementById("vn_postal_code").textContent = zip;
        hint.style.display = (event.target.value.trim() !== "") ? "block" : "none";
    });

    (function () {
        var msgInvalidProvince = <?= json_encode($collector_sh->translate("invalid_province_" . $countryKey, "Please select a province or city.")); ?>;
        var msgInvalidWard = <?= json_encode($collector_sh->translate("invalid_ward_" . $countryKey, "Please select a ward or commune.")); ?>;
        var msgInvalidAddress = <?= json_encode($collector_sh->translate("invalid_address_1_" . $countryKey, "Please enter your address.")); ?>;

        // Field-specific inline error messages (blur + submit), rendered with the
        // shared window.CRMIntegrated.inlineErrorMsg() so they look like the rest
        // of the checkout - same approach as shipping-address-th.php.
        var vnRequiredFields = [
            { el: document.getElementById('fields_state'), msg: msgInvalidProvince },
            { el: document.getElementById('fields_city'), msg: msgInvalidWard },
            { el: document.getElementById('fields_address1'), msg: msgInvalidAddress }
        ];

        function showVnFieldError(field) {
            var el = field.el;
            if (!el || el.offsetParent === null) { return; } // skip if hidden
            var value = (el.value || '').trim();
            var valid = value !== '';
            if (window.CRMIntegrated && typeof window.CRMIntegrated.inlineErrorMsg === 'function') {
                window.CRMIntegrated.inlineErrorMsg(el, value, valid, field.msg);
            }
            el.classList.toggle('error', !valid);
        }

        vnRequiredFields.forEach(function (field) {
            if (!field.el) { return; }
            field.el.addEventListener('blur', function () { showVnFieldError(field); });
            field.el.addEventListener('change', function () { showVnFieldError(field); });
        });

        var submitBtnEl = document.getElementById('submitForm');
        if (submitBtnEl) {
            submitBtnEl.addEventListener('click', function () {
                // Runs after the shared validateForm() handler so these messages
                // replace its generic "Required Field." text.
                setTimeout(function () {
                    vnRequiredFields.forEach(showVnFieldError);
                }, 0);
            });
        }
    })();
</script>
<?php $collector_sh->saveTranslation(); ?>