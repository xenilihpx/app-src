<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);

$country="_id";

// FEATURE TOGGLE: combined tabbed location picker.
// true  = Province/City/District/Village are shown as one widget with tabs (see screenshot request).
// false = reverts instantly to the original stacked cascading fields below - flip this back
//         any time without touching anything else on the page.
$useTabbedLocationPicker = true;
?>
<style>
#fields_city,
#fields_district,
#fields_village {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23343a40' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 9px 5px;
    padding-right: 32px;
    text-transform: capitalize;
}
</style>
<?php if($OfferApi->targetLanguage=="zh-hant" || $OfferApi->targetLanguage=="zh-hans"){ ?>
            <div class="tw-mb-2 tw-flex tw-gap-1 tw-text-[#4D4D4D]" style="font-size:0.75em; padding: 7px;border: 1px solid #f6ca79;background: #fef6e9;line-height: 1.3;" >
                <span>&#x2139;</span><span><?= $collector_sh->translate("type_english_us", "Please fill in the following shipping information in English.") ?></span>
            </div>
<?php } ?>

<?php if ($useTabbedLocationPicker): ?>

<link rel="preload" as="style" href="<?= REPONAME ?>/src/common/css/location-picker-tabs.css?t=1790227836923">
<link rel="stylesheet" href="<?= REPONAME ?>/src/common/css/location-picker-tabs.css?t=1790227836923">

<div class="mb-3">
    <label class="p cart-input-label" for="location_picker_display_id"><?= $collector_sh->translate("location_label".$country, "Province, City / Regency, District, Village"); ?></label>
    <div class="location-picker" data-location-picker data-field-order="fields_state fields_city fields_district fields_village">
        <textarea type="text" id="location_picker_display_id" class="cart-input p location-picker-trigger" readonly autocomplete="off"
               placeholder="<?= $collector_sh->translate("location_placeholder".$country, "Select province, city / regency, district and village"); ?>"></textarea>

        <div class="location-picker-panel">
            <div class="location-picker-tabs">
                <button type="button" class="location-picker-tab active" data-field="fields_state"><?= $collector_sh->translate("state".$country, "Province"); ?></button>
                <button type="button" class="location-picker-tab" data-field="fields_city" disabled><?=$collector_sh->translate("town_city".$country, "City / regency"); ?></button>
                <button type="button" class="location-picker-tab" data-field="fields_district" disabled><?=$collector_sh->translate("county".$country, "District") ?></button>
                <button type="button" class="location-picker-tab" data-field="fields_village" disabled><?=$collector_sh->translate("ward".$country, "Sub-district / village") ?></button>
            </div>
            <div class="location-picker-body">
                <div class="location-picker-pane" data-pane="fields_state">
                    <select id="fields_state" name="state" toSearch="state_code" class="cart-input p" required="required">
                        <option value=""><?= $collector_sh->translate("p_state".$country, "Select Province"); ?></option>
                    </select>
                    <div class="self-suggestions" data-role="province-list"></div>
                </div>

                <div class="location-picker-pane lp-collapsed" data-pane="fields_city">
                    <div class="self-autocomplete">
                        <input id="fields_city"
                               onEnabled="<?=$collector_sh->translate("city_onenabled_".$country, "Select city") ?>"
                               trigger="click focus keyup"
                               disabled
                               readonly
                               toSearch="city"
                               autocomplete="off"
                               autocapitalize="off"
                               autocorrect="off"
                               spellcheck="false"
                               name="city"
                               class="cart-input p search"
                               value=""
                               type="text"
                               onPlaceholder="<?= $collector_sh->translate("p_town_city_".$country, "Select province first",false); ?>"
                               placeholder="<?= $collector_sh->translate("p_town_city_".$country, "Select province first",false); ?>"
                               toenableel="fields_district" required="required">
                        <div class="self-suggestions" style="display:none;"></div>
                    </div>
                </div>

                <div class="location-picker-pane lp-collapsed" data-pane="fields_district">
                    <div class="self-autocomplete">
                        <input id="fields_district"
                            onEnabled="<?=$collector_sh->translate("district_onenabled_".$country, "Select district") ?>"
                            trigger="click focus"
                            disabled
                            readonly
                            toSearch="district"
                            class="cart-input p search"
                            value="" type="text"
                            placeholder="<?=$collector_sh->translate("p_county_on_".$country, "Select city first",false) ?>"
                            onPlaceholder="<?=$collector_sh->translate("p_county_on_".$country, "Select city first",false) ?>"
                            toenableel="fields_village"
                            required="required">
                        <div class="self-suggestions" style="display:none;"></div>
                    </div>
                </div>

                <div class="location-picker-pane lp-collapsed" data-pane="fields_village">
                    <div class="self-autocomplete">
                        <input id="fields_village"
                            onEnabled="<?=$collector_sh->translate("village_onenabled_".$country, "Select sub-district / village") ?>"
                            disabled readonly trigger="click focus"
                            toSearch="village"
                            linkElement="fields_zip"
                            name="ward" class="cart-input p search" value="" type="text"
                            placeholder="<?=$collector_sh->translate("p_ward_on_".$country, "Select district first",false) ?>"
                            onPlaceholder="<?=$collector_sh->translate("p_ward_on_".$country, "Select district first",false) ?>"
                            required="required">
                        <div class="self-suggestions" style="display:none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_zip" label="Zip/Postal Code"><?= $collector_sh->translate("zip".$country, "Postal code"); ?></label>
    <div>
        <input id="fields_zip" readonly name="zip" class="cart-input p" value="" type="text" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" placeholder="<?= $collector_sh->translate("p_zip".$country, "Select address first"); ?>" required="required">
    </div>
</div>

<?php else: ?>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_state" label="State/Province"><?= $collector_sh->translate("state".$country, "Province"); ?></label>
    <div>
        <select id="fields_state" name="state" toSearch="state_code" class="cart-input p" required="required">
            <option value=""><?= $collector_sh->translate("p_state".$country, "Select Province"); ?></option>
        </select>
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_city" label="Town/City"><?=$collector_sh->translate("town_city".$country, "City / regency"); ?></label>
    <div class="self-autocomplete">
        <input id="fields_city"
               onEnabled="<?=$collector_sh->translate("city_onenabled_".$country, "Select city") ?>"
               trigger="click focus keyup"
               disabled
               readonly
               toSearch="city"
               autocomplete="off"
               autocapitalize="off"
               autocorrect="off"
               spellcheck="false"
               name="city"
               class="cart-input p search"
               value=""
               type="text"
               onPlaceholder="<?= $collector_sh->translate("p_town_city_".$country, "Select province first",false); ?>"
               placeholder="<?= $collector_sh->translate("p_town_city_".$country, "Select province first",false); ?>"
               toenableel="fields_district" required="required">
        <div class="self-suggestions" style="display:none;"></div>
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_district"><?=$collector_sh->translate("county".$country, "District") ?></label>
    <div class="self-autocomplete">
        <input id="fields_district"
            onEnabled="<?=$collector_sh->translate("district_onenabled_".$country, "Select district") ?>"
            trigger="click focus"
            disabled
            readonly
            toSearch="district"
            class="cart-input p search"
            value="" type="text"
            placeholder="<?=$collector_sh->translate("p_county_on_".$country, "Select city first",false) ?>"
            onPlaceholder="<?=$collector_sh->translate("p_county_on_".$country, "Select city first",false) ?>"
            toenableel="fields_village"
            required="required">
        <div class="self-suggestions" style="display:none;"></div>
    </div>
</div>

<div class="row align-items-start mb-3">
    <div class="col-sm-12">
        <div class="row mb-n3 align-items-start ">
            <div class="col-sm-8 pr-sm-2">
                <div class="mb-3">
                    <label class="p cart-input-label" for="fields_village"><?=$collector_sh->translate("ward".$country, "Sub-district / village") ?></label>
                    <div class="self-autocomplete">
                        <input id="fields_village"
                            onEnabled="<?=$collector_sh->translate("village_onenabled_".$country, "Select sub-district / village") ?>"
                            disabled readonly trigger="click focus"
                            toSearch="village"
                            linkElement="fields_zip"
                            name="ward" class="cart-input p search" value="" type="text"
                            placeholder="<?=$collector_sh->translate("p_ward_on_".$country, "Select district first",false) ?>"
                            onPlaceholder="<?=$collector_sh->translate("p_ward_on_".$country, "Select district first",false) ?>"
                            required="required">
                        <div class="self-suggestions" style="display:none;"></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="mb-3">
                    <label class="p cart-input-label" for="fields_zip" label="Zip/Postal Code"><?= $collector_sh->translate("zip".$country, "Postal code"); ?></label>
                    <div>
                        <input id="fields_zip" readonly name="zip" class="cart-input p" value="" type="text" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" placeholder="<?= $collector_sh->translate("p_zip".$country, "Select address first"); ?>" required="required">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<div class="mb-3">
    <label class="p cart-input-label" for="address 1" label="Street"><?=$collector_sh->translate("address_1".$country, "Street and number") ?></label>
    <div>
        <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("p_address_1".$country, "Street and number"); ?>" required="required">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_apt_unit"><?=$collector_sh->translate("apt_unit".$country, "Block / unit / floor (optional)") ?></label>
    <div>
        <input id="fields_apt_unit" name="apt_unit" class="cart-input p" value="" type="text" optional placeholder="<?=$collector_sh->translate("p_apt_unit".$country, "Block / unit / floor (optional)") ?>">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_rt_rw"><?=$collector_sh->translate("rt_rw".$country, "RT/RW (optional)") ?></label>
    <div>
        <input id="fields_rt_rw" optional class="cart-input p" value="" type="text" pattern="[0-9]{1,3} / [0-9]{1,3}" maxlength="9" placeholder="<?=$collector_sh->translate("p_rt_rw".$country, "e.g. 005 / 002") ?>">
    </div>
    <!-- fields_rt/fields_rw stay in the DOM (hidden) purely so the order-import
         API keeps getting the separate rt/rw values it already expects (see
         integrated.js's formField.rt/formField.rw) - the shopper now only
         sees the one combined box above, split on "/" by this page's own
         script further down. -->
    <input type="hidden" id="fields_rt" optional name="rt" value="">
    <input type="hidden" id="fields_rw" optional name="rw" value="">
</div>


<script>
(function () {  
    var state = document.getElementById('fields_state');
  
    state.addEventListener('change', function (event) {
        var zipcode = document.getElementById('fields_zip');
        var city = document.getElementById('fields_city');
        var village = document.getElementById('fields_village');
        var district = document.getElementById('fields_district');
        zipcode.value="";
        city.value="";
        village.value="";
        district.value="";
        
        if(state.value!==""){
            city.removeAttribute("disabled");
            city.placeholder = city.getAttribute("onenabled")
        }else{
            city.setAttribute("disabled","");
            city.placeholder = city.getAttribute("onplaceholder");
        }
    });

    document.getElementById("fields_city").addEventListener('change', function(event){
        var el = event.target;
        var targetElId = el.getAttribute("toenableel");
        var nextEl = document.getElementById(targetElId);

        if (el.value.trim() !== "") {
            el.placeholder = el.getAttribute("onenabled");
            if(nextEl){
               nextEl.removeAttribute("disabled");
            }
        } else {
            el.placeholder = el.getAttribute("onplaceholder");
             if(nextEl){
               nextEl.setAttribute("disabled","");
            }
        }
    });

    // Combined "RT/RW" box: keeps the two real (hidden) fields.js reads for
    // submission - see the comment on fields_rt/fields_rw above - in sync by
    // splitting this field's own value on "/" every keystroke.
    var rtRw = document.getElementById('fields_rt_rw');
    var rt = document.getElementById('fields_rt');
    var rw = document.getElementById('fields_rw');

    function syncRtRw(e) {
        // A backspace that just deleted part of the " / " separator below
        // (see showSlash) shouldn't have it immediately reappear - that
        // would make deleting it look like it does nothing.
        var isDeleting = !!(e && typeof e.inputType === 'string' && e.inputType.indexOf('delete') === 0);

        // Spaces are purely cosmetic around the separator - ignored here
        // (along with anything that isn't a digit or "/") and reapplied by
        // showSlash below. Same shape the two original fields each enforced
        // on their own (pattern="[0-9]{3}") - digits only, capped at 3 per side.
        var raw = rtRw.value.replace(/[^0-9/]/g, '');
        var slashIndex = raw.indexOf('/');
        var rtPart, rwPart, showSlash;

        if (slashIndex === -1) {
            rtPart = raw.slice(0, 3);
            rwPart = raw.slice(3, 6);
        } else {
            rtPart = raw.slice(0, slashIndex).slice(0, 3);
            rwPart = raw.slice(slashIndex + 1).replace(/\//g, '').slice(0, 3);
        }

        if (rwPart) {
            showSlash = true; // an RW digit is already there - always keep it
        } else if (isDeleting) {
            // No RW digit yet and the shopper is backspacing: with a 3-char
            // " / " separator, one backspace only removes one of those
            // characters (usually the trailing space) - re-padding it back
            // here would make the whole separator un-deletable. Drop it in
            // one go instead so the next backspace edits RT's digits.
            showSlash = false;
        } else {
            // Forward typing: keep an explicit "/" the shopper typed, or
            // auto-insert one the moment RT's 3rd digit lands.
            showSlash = slashIndex !== -1 || rtPart.length === 3;
        }

        rtRw.value = showSlash ? (rtPart + ' / ' + rwPart) : rtPart;
        rt.value = rtPart;
        rw.value = rwPart;
    }

    rtRw.addEventListener('input', syncRtRw);

    // Covers a value already sitting in the hidden fields as the page loads
    // (e.g. a server-rendered prefill) so the shopper sees it too, instead
    // of the visible box starting blank while the hidden fields silently
    // hold a value.
    if (rt.value || rw.value) {
        rtRw.value = rt.value + (rw.value ? ' / ' + rw.value : '');
    }

})();
</script>

<?php if ($useTabbedLocationPicker): ?>
    <script src="<?= REPONAME ?>/src/common/js/location-picker-tabs.js?t=1790227836923" defer></script>
<?php endif; ?>
<?php $collector_sh->saveTranslation(); ?>

