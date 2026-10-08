<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);

$country="_tw";
?>


<!-- Temporarily hidden for TW. The field is skipped by validateForm() in integrated.js (it
     only validates :visible fields), so hiding it here also makes it non-required. -->
<div class="mb-3" style="display:none">
    <label class="p cart-input-label" for="consigneeId" label=""><?=$collector_sh->translate("consigneeId_label_".$country, "Consignee ID"); ?></label>
    <div class="tooltip-box">
        <span class="tooltip-dialog"><?= $collector_sh->translate("consigneeId_note", "Note: Ensure consignee ID matches name to avoid delays."); ?></span>
        <input id="fields_consigneeId" name="consigneeId" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("consigneeId_".$country, "Enter National ID / ARC "); ?>">
    </div>
</div>
<div class="mb-3">
    <label class="p cart-input-label" for="city" label="Town/City"><?=$collector_sh->translate("town_city".$country, "Town/City"); ?></label>
    <div>
        <input id="fields_city" name="city" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_town_city".$country, "Town/City"); ?>" required="required">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="state" label="State/Province"><?= $collector_sh->translate("state_district".$country, "District"); ?></label>
    <div>
        <select id="fields_state" name="state" class="cart-input p" required="required">
            <option value=""><?= $collector_sh->translate("p_state_".$country, "Select"); ?></option>
        </select>
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="address 1" label="Address Line 1"><?=$collector_sh->translate("address_".$country, "Address Line 1") ?></label>
    <div>
        <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("tw_address_1_".$country, "Road, Lane, Alley, No."); ?>" required="required">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="address 2" label="Building Name, Room Number, Company Dept."><?=$collector_sh->translate("tw_address_2".$country, "Address Line 2"); ?></label>
    <div>
        <input id="fields_address2" name="address 2" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("tw_address_2_p_".$country, "Floor, Building, Unit");?>" required="required">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="zip" label="Zip/Postal Code"><?= $collector_sh->translate("zip_postal".$country, "Postal Code"); ?></label>
    <div>
        <input id="fields_zip" name="zip" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_zip_".$country, "Postcode (optional)"); ?>" optional>
    </div>
</div>
<?php $collector_sh->saveTranslation(); ?>