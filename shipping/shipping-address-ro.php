<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);
$country = "ro";
?>

<style>
#fields_city {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23343a40' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 9px 5px;
    padding-right: 32px;
} 

#fields_zip_hint{
    display:none;
}
</style>

<!-- Județ (County) -->
<div class="mb-3">
    <label class="p cart-input-label" for="fields_state"><?=$collector_sh->translate("state_".$country, "County") ?></label>
    <div>
        <select id="fields_state" name="state" class="cart-input p" required="required"  toSearch="state_code" onplaceholder="<?=$collector_sh->translate("p_state_new_".$country, "Select county", false) ?>">
          
        </select>
    </div>
</div>

<!-- Localitate + Cod poștal side by side (2/3 + 1/3) -->
<div class="row align-items-start mb-3">
    <div class="col-7">
        <label class="p cart-input-label" for="fields_city"><?=$collector_sh->translate("town_city_".$country, "Locality") ?></label>
        <div class="self-autocomplete">       
            <input id="fields_city" name="city" siruta_id="" class="cart-input p search" 
                disabled
                toSearch="city" linkElement="fields_zip"  trigger="click focus" readonly value="" type="text" 
                placeholder="<?=$collector_sh->translate("p_town_city_2_new_".$country, "Select county first") ?>" 
                onEnabled="<?=$collector_sh->translate("p_town_city_enable_new_".$country, "Select locality") ?>" 
                onPlaceholder="<?=$collector_sh->translate("p_town_city_2_new_".$country, "Select county first",false) ?>"required="required">
            <div class="self-suggestions" style="display:none;"></div>
        </div>
    </div>
    <div class="col-5">
        <label class="p cart-input-label" for="fields_zip"><?=$collector_sh->translate("zip_".$country, "Postal code") ?></label>
        <div>
            <input id="fields_zip" disabled name="zip" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("p_zip_".$country, "Ex. 400335") ?>" required="required" maxlength="6" inputmode="numeric">
            <small id="fields_zip_hint" class="hint"><a href="https://www.posta-romana.ro/cauta-cod-postal.html" style="color:blue;font-size:0.9em;" target="_blank" rel="noopener"><?=$collector_sh->translate("hint_zip_".$country, "Search by ZIP code") ?></a></small></div>
        </div>
    </div>
</div>

<!-- Stradă și număr -->
<div class="mb-3">
    <label class="p cart-input-label" for="fields_address1"><?=$collector_sh->translate("address_1_".$country, "Street and number") ?></label>
    <div>
        <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("p_address_1_".$country, "Ex. Str. Mihai Eminescu, nr. 12") ?>" required="required">
    </div>
</div>

<!-- Bloc / Scară / Etaj / Ap. — combined into a single optional field -->
<div class="mb-3">
    <label class="p cart-input-label" for="fields_address2"><?=$collector_sh->translate("apartment_detial_".$country, "Apartment details (optional)") ?></label>
    <div>
        <input id="fields_address2" name="address 2" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("p_bloc_scara_etaj_ap_".$country, "Ex. Bl. A3, Sc. B, Et. 4, Ap. 12") ?>">
    </div>
    <small class="p" style="color:#888;font-size:0.85em;"><?=$collector_sh->translate("bloc_hint_".$country, "Only if you live in an apartment block — optional.") ?></small>
</div>

<script>
(function () {
    var state = document.getElementById('fields_state');
    state.addEventListener('change', function (event) {
        var zipcode = document.getElementById('fields_zip');
        var city = document.getElementById('fields_city');
          zipcode.removeAttribute("disabled");
        zipcode.value="";
        city.value="";
         if(state.value!==""){
            city.removeAttribute("disabled");
            city.placeholder = city.getAttribute("onenabled")
        }else{
            city.setAttribute("disabled","");
            city.placeholder = city.getAttribute("onplaceholder");
        }
    });

    setTimeout(function(){
        var state_option = document.querySelector('#fields_state option[value=""]');
        state_option.innerHTML = state.getAttribute("onplaceholder");
    },400)
})();
</script>
<?php $collector_sh->saveTranslation(); ?>
