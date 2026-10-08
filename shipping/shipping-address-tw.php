<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);

$country="_tw";
?>

<style>
#fields_city,
#fields_district{
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23343a40' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 9px 5px;
    padding-right: 32px;
    text-transform: capitalize;
}
input:disabled, input:read-only {
  cursor: default;
  background-color: #f5f4f4 !important;
}
</style>

<!-- Temporarily hidden for TW. The field is skipped by validateForm() in integrated.js (it
     only validates :visible fields), so hiding it here also makes it non-required. -->
<!-- <div class="mb-3" style="display:none">
    <label class="p cart-input-label" for="consigneeId" label=""><?//=$collector_sh->translate("consigneeId_label_".$country, "Consignee ID"); ?></label>
    <div class="tooltip-box">
        <span class="tooltip-dialog"><?//= $collector_sh->translate("consigneeId_note", "Note: Ensure consignee ID matches name to avoid delays."); ?></span>
        <input id="fields_consigneeId" name="consigneeId" class="cart-input p" value="" type="text" placeholder="<?//= $collector_sh->translate("consigneeId_".$country, "Enter National ID / ARC "); ?>">
    </div>
</div> -->
 <div class="tw-mb-2 tw-flex tw-gap-1 tw-text-[#4D4D4D]" style="font-size:0.75em; padding: 7px;border: 1px solid #f6ca79;background: #fef6e9;line-height: 1.3;" >
            <span aria-hidden="true" style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:1.3em;height:1.3em;border-radius:3px;background:#2f7cf6;color:#fff;font-family:Georgia,'Times New Roman',serif;font-weight:bold;font-style:normal;font-size:1em;line-height:1;">i</span><span><?= $collector_sh->translate("type_zhtranditional_new_tw", "Please enter your address in Traditional Chinese.") ?></span>
    </div>
    
<div class="mb-3">
    <label class="p cart-input-label" for="city" label="County / city"><?=$collector_sh->translate("town_city_new".$country, "County / city"); ?></label>
    <div class="self-autocomplete">
        <input id="fields_city" 
               onErrorEmpty="<?=$collector_sh->translate("city_empty".$country, "Select your county or city.");?>"
               trigger="click focus keyup" toenableel="fields_district" toSearch="city" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" name="city" class="search cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_town_city_new2".$country, "Select County / City"); ?>" required="required">
        <div id="self-suggestions" class="self-suggestions" style="display:none;"></div>
    </div>
</div>

<div class="row align-items-start mb-3">
    <div class="col-sm-12">
        <div class="row mb-n3 align-items-start ">
            <div class="col-sm-8 pr-sm-2  ">
                <div class="mb-3">
                    <label class="p cart-input-label" for="state" label="State/Province"><?= $collector_sh->translate("state_district".$country, "District"); ?></label>
                    <div class="self-autocomplete">
                        <input type="hidden" id="fields_state" name="state" value="">
                        <input type="text" trigger="click keyup focus" linkElement="fields_zip" disabled
                               toSearch="district" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" 
                               class="search cart-input p" required="required" id="fields_district" name="state"                               
                               onEnabled="<?=$collector_sh->translate("p_town_city_enable_new_".$country, "Select district") ?>" 
                               onPlaceholder="<?=$collector_sh->translate("p_state_district2_new".$country, "Select county / city first",false) ?>"
                               placeholder="<?= $collector_sh->translate("p_state_district_new".$country, "Select county / city first"); ?>">
                        <div id="self-suggestions" class="self-suggestions" style="display:none;"></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="mb-3"> 
                    <label class="p cart-input-label" for="zip" label="Zip/Postal Code"><?= $collector_sh->translate("zip_postal".$country, "Postal Code"); ?></label>
                    <div> 
                        <input readonly id="fields_zip" name="zip" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_zip_new".$country, "Auto"); ?>" required="required">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="mb-3">
    <label class="p cart-input-label" for="address 1" label="Address Line 1"><?=$collector_sh->translate("address_new".$country, "Address") ?></label>
    <div>
        <input id="fields_address1" name="address 1"
          onErrorEmpty="<?=$collector_sh->translate("on_error_address1_empty".$country, "Enter road, lane and number.");?>"
          onErrorValue="<?=$collector_sh->translate("on_error_address1_".$country, "Enter your address in Traditional Chinese.");?>"  class="cart-input p" value="" type="text" placeholder="例：信義路五段7號" required="required">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="address 2" label="Building Name, Room Number, Company Dept."><?=$collector_sh->translate("tw_address".$country, "Floor / unit (optional)"); ?></label>
    <div>
        <input id="fields_address2" name="address 2" onErrorValue="<?=$collector_sh->translate("on_error_address2_new_".$country, "Enter your floor/unit in Traditional Chinese.");?>" class="cart-input p" value="" type="text" placeholder="例：12 樓之 3">
    </div>
</div>


<script>
(function () {
    var city = document.getElementById('fields_city');
    var district = document.getElementById('fields_district');
    var state = document.getElementById('fields_state');
    var zip = document.getElementById('fields_zip');
    var address1 = document.getElementById('fields_address1');
    var address2 = document.getElementById('fields_address2');

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

    // Keep fields_state in sync with fields_district. The suggestion script sets
    // district.value directly (no input/change event), so hook the value setter
    // on this element to catch every assignment, including clears.
    var valueProp = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
    Object.defineProperty(district, 'value', {
        configurable: true,
        get: function () { return valueProp.get.call(this); },
        set: function (v) {
            valueProp.set.call(this, v);
            state.value = v;
        }
    });
    state.value = district.value;

    // Clear district and zip whenever county / city is emptied
    function clearIfCityEmpty() {
        if (city.value.trim() === '') {
            district.value = '';
            zip.value = '';
        }
    }

    ['input', 'change', 'blur', 'keyup'].forEach(function (evt) {
        city.addEventListener(evt, clearIfCityEmpty);
    });

    // Also catch programmatic assignments (e.g. city.value = '' from scripts),
    // which don't fire input/change events.
    Object.defineProperty(city, 'value', {
        configurable: true,
        get: function () { return valueProp.get.call(this); },
        set: function (v) {
            valueProp.set.call(this, v);
            clearIfCityEmpty();
        }
    });

    city.addEventListener('blur', function () {
       if(city.value.trim() == ''){     
            inlineError(city, null, false, city.getAttribute("onErrorEmpty"));
       }
  
    });


    address1.addEventListener('blur', function () {
        var valid = /[\u4e00-\u9fff]/.test(address1.value.trim());
        var empty = address1.value.trim() == '';
        if (empty) {
            inlineError(address1, null, false, address1.getAttribute("onErrorEmpty"));
        } else {
            inlineError(address1, null, valid, address1.getAttribute("onErrorValue"));
            if(!valid) {
                address1.value="";
            }
        }
        
        
    });

    address2.addEventListener('blur', function () {
        var valid = /[\u4e00-\u9fff]/.test(address2.value.trim());
        
        inlineError(address2, null, valid, address2.getAttribute("onErrorValue"));
        if(!valid) {
            address2.value="";
        }
    });
})();
</script>
<?php $collector_sh->saveTranslation(); ?>