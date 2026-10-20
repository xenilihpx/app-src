<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);

$country="pk";
?>
<style>
#fields_city{
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23343a40' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 9px 5px;
    padding-right: 32px;
    text-transform: capitalize;
}
</style>
<script> var arabicDigits = {'٠':'0','١':'1','٢':'2','٣':'3','٤':'4','٥':'5','٦':'6','٧':'7','٨':'8','٩':'9'};</script>

<div class="mb-3">
    <label class="p cart-input-label" for="state" label="Region/Province"><?= $collector_sh->translate("province_".$country, "Province / Region"); ?></label>
    <div>
        <select id="fields_state" name="state" class="cart-input p" toSearch="state_code" toenableel = "fields_city">
            <option value=""><?= $collector_sh->translate("p_province_".$country, "Select Province"); ?></option>
        </select>
    </div>
</div>

<div class="mb-3 ">
    <label class="p cart-input-label" for="city" label="City"><?=$collector_sh->translate("city_".$country, "City"); ?></label>
    <div class="self-autocomplete">
            <input id="fields_city" name="city"
                   toSearch="city" 
                   class="cart-input p search" value="" type="text" 
                   disabled trigger="click focus"  
                   onEnabled="<?= $collector_sh->translate("p_district_onenabled".$country, "Select city",false); ?>"
                   onPlaceholder="<?= $collector_sh->translate("p_district_new".$country, "Select province first",false); ?>"
                   placeholder="<?= $collector_sh->translate("p_district_new".$country, "Select province first",false); ?>"/>
            <div class="self-suggestions" style="display:none;"></div>   
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="address 2" label="Area / Block/ Sector"><?=$collector_sh->translate("address_area_".$country, "Area / Block / Sector"); ?></label>
    <div>
        <input id="fields_address2" name="address 2" class="cart-input p" oninput="this.value = this.value.replace(/[٠-٩]/g, function (d) { return arabicDigits[d]; });" value="" type="text" placeholder="<?=$collector_sh->translate("p_address_area_".$country, "For example F-8/1 or Gulshan Iqbal Block 6");?>" required="required">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="address 1" label="House number and Street"><?=$collector_sh->translate("address_street_".$country, "House number and street") ?></label>
    <div>

        <input id="fields_address1" name="address 1" oninput="this.value = this.value.replace(/[٠-٩]/g, function (d) { return arabicDigits[d]; });" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("p_address_street_".$country, "For example, House 123, Street 45"); ?>" required="required">
    </div>
</div>

 <div class="mb-3 d-none">
    <label class="p cart-input-label" for="zip" label="Zip/Postal Code"><?= $collector_sh->translate("zip_".$country, "Zip / Postal Code"); ?></label>
    <div>
        <input id="fields_zip" name="zip" class="cart-input p" value="00000" type="text" placeholder="<?= $collector_sh->translate("p_zip_".$country, "Zip/Postal Code"); ?>" required="required">
    </div>
</div>

 <!-- <div class="mb-3">
    <label class="p cart-input-label" for="fields_tax_id" label="Pakistan ID"><?//=$collector_sh->translate("tax_id_".$country, "Identity card/passport number"); ?></label>
    <div>
        <small class="p" style="display:block; color:#6b7280;"><?//= $collector_sh->translate("note_tax_id_".$country, "CNIC: 13 digits (e.g. 35202-1234567-1) or passport number"); ?></small>
        <input id="fields_tax_id" name="tax_id" class="cart-input p" value="" type="text" maxlength="15" minlength="12" required="required">
        <small class="p" style="display:block; color:#6b7280; margin-top:4px;"><?//= $collector_sh->translate("help_tax_id_".$country, "Required for customs clearance."); ?></small>
    </div>
</div>  -->

<script>
(function () {  
    var state = document.getElementById('fields_state');
    var zip = document.getElementById('fields_zip');

    state.addEventListener('change', function (event) {
        var city = document.getElementById('fields_city');   
        city.value="";
        zip.value = '.';

        if(state.value!==""){
            city.removeAttribute("disabled");
            city.placeholder = city.getAttribute("onenabled")
        }else{
            city.setAttribute("disabled","");
            city.placeholder = city.getAttribute("onplaceholder");
        }
    });

})();
</script>
<?php $collector_sh->saveTranslation(); ?>