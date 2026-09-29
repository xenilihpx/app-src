<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);

$country="ch";
?>
<?php if($OfferApi->targetLanguage=="zh-hant" || $OfferApi->targetLanguage=="zh-hans"){ ?>
            <div class="tw-mb-2 tw-flex tw-gap-1 tw-text-[#4D4D4D]" style="font-size:0.75em; padding: 7px;border: 1px solid #f6ca79;background: #fef6e9;line-height: 1.3;" >
                <span>&#x2139;</span><span><?= $collector_sh->translate("type_english_ch", "Please fill in the following shipping information in German.") ?></span>
            </div>
<?php } ?>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_address1"><?= $collector_sh->translate("address_".$country, "Address") ?></label>
    <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_address_".$country, "E.g. Bahnhofstrasse 12") ?>" required="required">
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_address2"><?= $collector_sh->translate("address2_".$country, "Additional address info (optional)") ?></label>
    <input id="fields_address2" name="address 2" class="cart-input p" value="" type="text" optional placeholder="<?= $collector_sh->translate("p_address2_".$country, "E.g. 3rd floor, c/o Muster") ?>">
</div>

<!-- Postleitzahl (narrow) + Ort (wide) side by side -->
<div class="row align-items-start mb-3">
    <div class="col-sm-4">
        <div class="mb-3">
            <label class="p cart-input-label" for="fields_zip"><?= $collector_sh->translate("zip_".$country, "Postal code") ?></label>
            <input id="fields_zip" name="zip" class="cart-input p" value="" type="text" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" placeholder="<?= $collector_sh->translate("p_zip_".$country, "E.g. 8001") ?>" required="required">
        </div>
    </div>
    <div class="col-sm-8">
        <div class="mb-3">
            <label class="p cart-input-label" for="fields_city"><?= $collector_sh->translate("town_city_".$country, "Town / city") ?></label>
            <input id="fields_city" name="city" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_town_city_".$country, "E.g. Zürich") ?>" required="required">
        </div>
    </div>
</div>

<!-- Switzerland has no state/province in its address format; keep the (hidden, optional) field so the
     shared checkout JS still finds #fields_state -->
<div class="mb-3 d-none">
    <select id="fields_state" name="state" class="cart-input p" optional>
        <option value=""></option>
    </select>
</div>
<?php $collector_sh->saveTranslation(); ?>
