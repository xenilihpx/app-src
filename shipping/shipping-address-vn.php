<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);

$countryKey="vn";
?>
<?php if($OfferApi->targetLanguage=="zh-hant" || $OfferApi->targetLanguage=="zh-hans"){ ?>
            <div class="tw-mb-2 tw-flex tw-gap-1 tw-text-[#4D4D4D]" style="font-size:0.75em; padding: 7px;border: 1px solid #f6ca79;background: #fef6e9;line-height: 1.3;" >
                <span aria-hidden="true" style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:1.3em;height:1.3em;border-radius:3px;background:#2f7cf6;color:#fff;font-family:Georgia,'Times New Roman',serif;font-weight:bold;font-style:normal;font-size:1em;line-height:1;">i</span><span><?= $collector_sh->translate("type_english_us", "Please fill in the following shipping information in English.") ?></span>
            </div>
<?php } ?>

<div class="mb-3">
    <label class="p cart-input-label" for="address 1" label="Street, house and apartment number"><?=$collector_sh->translate("address_1_" . $countryKey, "Street address") ?></label>
    <div>
        <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("p_address_1_" . $countryKey, "Street address"); ?>" required="required">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="address 2" label="Street, house and apartment number"><?=$collector_sh->translate("address_2_" . $countryKey, "Address line 2 — optional"); ?></label>
    <div>
        <input id="fields_address2" name="address 2" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("p_address_2_" . $countryKey, "Address line 2 — optional");?>" required="required">
    </div>
</div>

<div class="mb-3 ">
    <label class="p cart-input-label" for="city" label="Town/City"><?=$collector_sh->translate("town_city_" . $countryKey, "Ward / Commune / Special zone"); ?></label>
    <div>
        <input id="fields_city" name="city" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_town_city_" . $countryKey, "Ward / Commune / Special zone"); ?>" required="required">
    </div>
</div>
<div class="mb-3">
    <label class="p cart-input-label" for="state" label="Tỉnh/Thành phố"><?= $collector_sh->translate("state_" . $countryKey, "Province / centrally governed city"); ?></label>
    <div>
        <select id="fields_state" name="state" class="cart-input p" required="required">
            <option value=""><?= $collector_sh->translate("p_state_" . $countryKey, "Select"); ?></option>
        </select>
    </div>
</div>
<div class="mb-3">
    <label class="p cart-input-label" for="zip" label="Zip/Postal Code"><?= $collector_sh->translate("zip_" . $countryKey, "Zip / Postal Code"); ?></label>
    <div>
        <input id="fields_zip" name="zip" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_zip_" . $countryKey, "Zip/Postal Code (optional)"); ?>" optional>
    </div>
</div>

<?php $collector_sh->saveTranslation(); ?>