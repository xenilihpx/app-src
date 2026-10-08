<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);
?>
<?php if($OfferApi->targetLanguage=="zh-hant" || $OfferApi->targetLanguage=="zh-hans"){ ?>
            <div class="tw-mb-2 tw-flex tw-gap-1 tw-text-[#4D4D4D]" style="font-size:0.75em; padding: 7px;border: 1px solid #f6ca79;background: #fef6e9;line-height: 1.3;" >
                <span aria-hidden="true" style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:1.3em;height:1.3em;border-radius:3px;background:#2f7cf6;color:#fff;font-family:Georgia,'Times New Roman',serif;font-weight:bold;font-style:normal;font-size:1em;line-height:1;">i</span><span><?= $collector_sh->translate("type_english_gb", "Please fill in the following shipping information in English.") ?></span>
            </div>
<?php } ?>

<div class="mb-3">
    <label class="p cart-input-label" for="address 1" label="Address Line 1"><?=$collector_sh->translate("address_1_gb", "Address Line 1") ?></label>
    <div>
        <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("p_address_1_gb", "House number and street name"); ?>" required="required">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="address 2" label="Address Line 2 (Optional)"><?=$collector_sh->translate("address_2_gb", "Address Line 2 (Optional)"); ?></label>
    <div>
        <input id="fields_address2" name="address 2" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("p_address_2_gb", "Apartment, suite, building name");?>">
    </div>
</div>

<div class="mb-3 sg-fields">
    <label class="p cart-input-label" for="city" label="Town/City"><?=$collector_sh->translate("town_city_gb", "Town/City"); ?></label>
    <div>
        <input id="fields_city" name="city" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_town_city_gb", "Town/City"); ?>" required="required">
    </div>
</div>

<div class="row align-items-start">
    <div class="col-sm-12">
        <div class="row mb-n3 align-items-start hk-fields">
            <div class="col-sm-6 pr-sm-2 hk-fields sg-fields">
                <div class="mb-3">
                    <label class="p cart-input-label" for="state" label="County (Optional)"><?= $collector_sh->translate("state_gb", "County (Optional)"); ?></label>
                    <div>
                        <select id="fields_state" name="state" class="cart-input p">
                            <option value=""><?= $collector_sh->translate("p_state_gb", "Select"); ?></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="p cart-input-label" for="zip" label="Postcode"><?= $collector_sh->translate("zip_gb", "Postcode"); ?></label>
                    <div>
                        <input id="fields_zip" name="zip" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_zip_gb", "Postcode"); ?>" required="required">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $collector_sh->saveTranslation(); ?>
