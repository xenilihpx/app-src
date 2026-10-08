<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');

$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);

$country="at";
?>
<?php if($OfferApi->targetLanguage=="zh-hant" || $OfferApi->targetLanguage=="zh-hans"){ ?>
            <div class="tw-mb-2 tw-flex tw-gap-1 tw-text-[#4D4D4D]" style="font-size:0.75em; padding: 7px;border: 1px solid #f6ca79;background: #fef6e9;line-height: 1.3;" >
                <span aria-hidden="true" style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:1.3em;height:1.3em;border-radius:3px;background:#2f7cf6;color:#fff;font-family:Georgia,'Times New Roman',serif;font-weight:bold;font-style:normal;font-size:1em;line-height:1;">i</span><span><?= $collector_sh->translate("type_english_at", "Please fill in the following shipping information in German.") ?></span>
            </div>
<?php } ?>

<div class="mb-3">
    <label class="p cart-input-label" for="address 1" label="Address Line 1"><?=$collector_sh->translate("address_label_".$country, "Address Line 1") ?></label>
    <div>
        <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("street_placeholder_".$country, "Street and House Number"); ?>" required="required">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="address 2" label="Building Name, Room Number, Company Dept."><?=$collector_sh->translate("address2_label_".$country, "Address Line 2 (optional)"); ?></label>
    <div>
        <input id="fields_address2" name="address 2" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("address2_placeholder_".$country, "Apartment, Floor, Entrance, c/o, etc.");?>" required="required">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="zip" label="Zip/Postal Code"><?= $collector_sh->translate("zip_label_".$country, "Postal Code"); ?></label>
    <div>
        <input id="fields_zip" name="zip" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("zip_placeholder_".$country, "Postal Code"); ?>" required="required">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="city" label="Town/City"><?=$collector_sh->translate("town_city_label_".$country, "City"); ?></label>
    <div>
        <input id="fields_city" name="city" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("town_city_placehoder_".$country, "City"); ?>" required="required">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="state" label="State/Province"><?= $collector_sh->translate("state_label__".$country, "State (optional)"); ?></label>
    <div>
        <select id="fields_state" name="state" class="cart-input p" required="required" optional></select>
    </div>
</div>
<?php $collector_sh->saveTranslation(); ?>
