<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);

$country="_tw";

// ---- Delivery type + convenience-store pickup (7-ELEVEN / FamilyMart / Hi-Life) ----
// Shown only on pages that opt in with a data-tw-store-pickup attribute (checkout-tw-cod.php);
// every other TW checkout keeps the home-delivery address form exactly as before.
// Each key is translated once here (the collector rejects a key used twice) and reused below.
$twT = [
    'type_label'    => $collector_sh->translate("tw_delivery_type_label".$country, "Delivery type"),
    'home'          => $collector_sh->translate("tw_delivery_home".$country, "Home delivery"),
    'family'        => $collector_sh->translate("tw_delivery_family".$country, "FamilyMart"),
    'hilife'        => $collector_sh->translate("tw_delivery_hilife".$country, "Hi-Life"),
    'pickup_store'  => $collector_sh->translate("tw_pickup_store".$country, "Pickup store"),
    'search_ph'     => $collector_sh->translate("tw_store_search_ph".$country, "Search store name, address or number"),
    'store_first'   => $collector_sh->translate("tw_store_first".$country, "Select county/city and district first"),
    'store_loading' => $collector_sh->translate("tw_store_loading".$country, "Loading stores…"),
    'store_none'    => $collector_sh->translate("tw_store_none".$country, "No {chain} stores in this district. Choose another district."),
    'store_nomatch' => $collector_sh->translate("tw_store_nomatch".$country, "No matching stores."),
    'store_error'   => $collector_sh->translate("tw_store_load_error".$country, "Stores couldn't be loaded. Try again, or enter your store below."),
    'store_no'      => $collector_sh->translate("tw_store_no".$country, "Store no."),
    'change_store'  => $collector_sh->translate("tw_change_store".$country, "Change store"),
    'store_err'     => $collector_sh->translate("tw_store_err".$country, "Select a pickup store."),
    'not_listed'    => $collector_sh->translate("tw_store_not_listed".$country, "My store isn't listed"),
    'store_number'  => $collector_sh->translate("tw_store_number".$country, "Store number (optional)"),
    'store_name'    => $collector_sh->translate("tw_store_name".$country, "Store name"),
    'name_err'      => $collector_sh->translate("tw_store_name_err".$country, "Enter the store name."),
    'store_address' => $collector_sh->translate("tw_store_address".$country, "Store address"),
    'addr_err'      => $collector_sh->translate("tw_store_addr_err".$country, "Enter the store address."),
    'hint'          => $collector_sh->translate("tw_store_hint".$country, "We'll text you when your parcel arrives at the store.\nSelf-pickup: Bring an ID matching the recipient's name.\nPickup by someone else: Provide the recipient's name, last 3 digits of their mobile number, or tracking number (any one)."),
    'id_err_711'    => $collector_sh->translate("tw_id_err_711".$country, "Store number is 6 digits."),
    'id_err_family' => $collector_sh->translate("tw_id_err_family".$country, "FamilyMart store number is 6 digits starting with 0."),
    'id_err_hilife' => $collector_sh->translate("tw_id_err_hilife".$country, "Hi-Life store number is 4 digits."),
    'mobile_err'    => $collector_sh->translate("tw_mobile_err".$country, "Convenience store pickup with cash payment requires a mobile number. Please do not enter a landline number."),
    'mobile_err_len' => $collector_sh->translate("tw_mobile_err_len".$country, "Please enter a Taiwan mobile number, e.g. 0912 345 678 or 912 345 678."),
    'mobile_err_home' =>$collector_sh->translate("tw_mobile_err_home".$country, "Enter a Taiwan mobile number (10 digits starting with 09)."),
    'addr_no_err'   => $collector_sh->translate("tw_addr1_number_err".$country, "Enter road, lane and number (must include 號)."),
    'home_only'     => $collector_sh->translate("tw_home_only_card".$country, "Store pickup is only available with cash on delivery."),
];
$twH = function ($k) use ($twT) { return htmlspecialchars($twT[$k], ENT_QUOTES, 'UTF-8'); };
// Store-pickup hint has a "Label: rest of the line" layout on its 2nd/3rd lines (e.g. "Self-pickup: ...",
// "本人取件：..."); each line is escaped on its own and only the leading "Label:"/"Label：" gets wrapped
// in <strong>, so the translated text can never inject arbitrary HTML.
$twHintHtml = function () use ($twT) {
    $lines = explode("\n", $twT['hint']);
    $lines = array_map(function ($line) {
        $escaped = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
        $escaped = preg_replace('/^([^:：]+[:：])/u', '<strong>$1</strong>', $escaped);
        // 。、（） sit too high in their own glyph box (every font tested, not a font-fallback issue) -
        // nudge them down so they line up with the surrounding text. The colon is intentionally left
        // alone here - it doesn't have the same problem and shouldn't be touched.
        return preg_replace('/([。、（）])/u', '<span class="punct">$1</span>', $escaped);
    }, $lines);
    return implode('<br/>', $lines);
};
$twHomeSvg  = '<svg viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="1.8" aria-hidden="true"><path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>';

// Absolute URL of this folder (src/shipping/), worked out from the file's location, so the logos and the store
// lookup resolve the same whether this partial is included by a page (in any folder) or loaded over AJAX.
// Falls back to the page-relative path (one level up on /mobile pages).
$twDocRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$twDir     = realpath(__DIR__);
$twBaseUrl = ($twDocRoot && $twDir && stripos($twDir, $twDocRoot) === 0)
    ? str_replace('\\', '/', substr($twDir, strlen($twDocRoot))) . '/'
    : ((stripos($_SERVER['REQUEST_URI'] ?? '', '/mobile') !== false ? '../' : '') . 'src/shipping/');
// Chain logos (src/common/images/): 7-ELEVEN 2021 logo, FamilyMart 2016 logo, Hi-Life logo from hilife.com.tw.
// The logo is the tab's only content, so its alt text is the chain name. ?v=<mtime> busts the browser cache when a file is replaced.
$twImgUrl = preg_replace('#shipping/$#', '', $twBaseUrl) . 'common/images/';   // src/shipping/ -> src/common/images/
$twImgDir = dirname(__DIR__) . '/common/images/';
$twLogo = function ($file, $alt) use ($twImgUrl, $twImgDir) {
    $v = @filemtime($twImgDir . $file);
    $src = $twImgUrl . $file . ($v ? '?v=' . $v : '');
    return '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '" decoding="async">';
};
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
/* exclude checkbox/radio: they always match :read-only, which wiped the checked fill (e.g. SecureShip) */
input:not([type=checkbox]):not([type=radio]):disabled {
  cursor: default;
  background-color: #f5f4f4 !important;
}

/* Delivery type tabs + store pickup */
#twDeliveryTypeWrap[hidden], #twStoreBlock[hidden], #twHomeBlock[hidden], .tw-store-wrap [hidden], #twHomeOnlyNote[hidden] { display: none !important; }
.tw-tabs { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 16px; }
.tw-tab { border: 1px solid #d6d6d6; background: #fff; padding: 10px 4px; cursor: pointer; font: inherit; color: #222;
    font-size: 13px; font-weight: 600; display: flex; flex-direction: column; align-items: center; gap: 6px; min-height: 64px; justify-content: center; }
.tw-tab .tw-logo { height: 22px; display: flex; align-items: center; justify-content: center; }
.tw-tab .tw-logo svg { width: 22px; height: 22px; }
/* Chain tabs show the logo only: sized per logo shape, scaled down to fit narrow tabs */
.tw-tab .tw-logo-img { height: auto; max-width: 100%; padding: 0 4px; }
.tw-tab .tw-logo img { width: auto; max-width: 100%; display: block; object-fit: contain; }
.tw-tab .tw-logo-711 img { height: 36px; }      /* tall mark */
.tw-tab .tw-logo-family img { height: 16px; }   /* ~7:1 wordmark */
.tw-tab .tw-logo-hilife img { height: 24px; }   /* ~4:1 heart + 萊爾富 */
.tw-tab[aria-selected=true] { border: 2px solid #2b6cb0; background: #f2f7fd; padding: 9px 3px; }
.tw-tab:focus-visible { outline: 2px solid #4a90d9; }
/* Home delivery only (TW.setHomeOnly, e.g. card payment): store tabs greyed out and not clickable */
.tw-tab:disabled { cursor: not-allowed; background: #f5f5f5; border-color: #e3e3e3; color: #999; }
.tw-tab:disabled .tw-logo { opacity: .35; filter: grayscale(1); }
.tw-homeonly-note { margin: -8px 0 16px; font-size: 12px; color: #777; }
.tw-store-search { margin-bottom: 0 !important; }
.tw-store-list { border: 1px solid #d6d6d6; border-top: 0; max-height: 264px; overflow-y: auto; background: #fff; }
/* Error states (same look as the TW COD mock): red border, white field, small dark-red message under it */
#twStoreBlock .tw-invalid .tw-store-search,
#twStoreBlock .tw-invalid .tw-store-list,
#twStoreBlock .tw-invalid .tw-picked,
#twStoreBlock .cart-input.error { border-color: #c0392b !important; background-color: #fff !important; box-shadow: none !important; }
#twStoreBlock .tw-invalid .tw-store-search { border-bottom-color: #d6d6d6 !important; }
/* message text: same as the shared "Required Field" message (red, 14px, set inline by inlineError) */
#twStoreBlock .error-message { display: block; }
.tw-opt { font-weight: 400; color: #777; }
.tw-store-list .tw-msg { padding: 14px 12px; font-size: 13px; color: #777; }
.tw-store { display: flex; gap: 10px; align-items: flex-start; padding: 10px 12px; border-top: 1px solid #eee; cursor: pointer; margin: 0; font-weight: 400; }
.tw-store:first-child { border-top: 0; }
.tw-store:hover { background: #f7f9fc; }
.tw-store input { margin: 3px 0 0; flex: 0 0 auto; }
.tw-store .tw-nm { font-size: 14px; font-weight: 600; }
.tw-store .tw-no { font-size: 12px; color: #777; font-weight: 400; margin-left: 6px; }
.tw-store .tw-ad { font-size: 12px; color: #555; margin-top: 2px; }
.tw-picked { border: 2px solid #2b6cb0; background: #f2f7fd; padding: 10px 12px; display: flex; justify-content: space-between; gap: 10px; }
.tw-picked .tw-nm { font-size: 14px; font-weight: 700; }
.tw-picked .tw-ad { font-size: 12px; color: #555; margin-top: 2px; }
.tw-linkbtn { background: none; border: 0; color: #2b6cb0; font: inherit; font-size: 13px; cursor: pointer; padding: 0; white-space: nowrap; text-decoration: underline; }
.tw-check { display: flex; align-items: center; gap: 8px; font-size: 14px; margin: 12px 0; cursor: pointer; font-weight: 400; }
.tw-check input { width: 16px; height: 16px; margin: 0; }
.tw-manual { border-left: 3px solid #d6d6d6; padding-left: 12px; }
.tw-hint { font-size: 13px; color: #777; margin-top: 4px; white-space: pre-line; line-height: 20px; }
/* 。、（） (see $twHintHtml() above) sit too high in their own glyph box; nudging them down with
   position lines them up with the rest of the text. The colon is left alone - it doesn't need this. */
.tw-hint .punct { position: relative; top: 0.4em; }
@media (max-width: 480px) { .tw-tabs { grid-template-columns: repeat(2, 1fr); } }
</style>

<!-- tw_delivery_type -->
<div id="twDeliveryTypeWrap" hidden>
    <div class="tw-tabs" role="tablist" aria-label="<?= $twH('type_label'); ?>" id="twTabs">
        <button type="button" class="tw-tab" role="tab" data-type="HOME" aria-selected="true"><span class="tw-logo"><?= $twHomeSvg; ?></span><?= $twH('home'); ?></button>
        <button type="button" class="tw-tab" role="tab" data-type="711" aria-selected="false"><span class="tw-logo tw-logo-img tw-logo-711"><?= $twLogo('tw-711.svg', '7-ELEVEN'); ?></span><!--7-ELEVEN--></button>
        <button type="button" class="tw-tab" role="tab" data-type="FAMILY" aria-selected="false"><span class="tw-logo tw-logo-img tw-logo-family"><?= $twLogo('tw-familymart.svg', $twT['family']); ?></span><?//= $twH('family'); ?></button>
        <button type="button" class="tw-tab" role="tab" data-type="HILIFE" aria-selected="false"><span class="tw-logo tw-logo-img tw-logo-hilife"><?= $twLogo('tw-hilife.svg', $twT['hilife']); ?></span><?//= $twH('hilife'); ?></button>
    </div>
    <p class="tw-homeonly-note" id="twHomeOnlyNote" hidden><?= $twH('home_only'); ?></p>
    <input type="hidden" id="tw_delivery_type" name="tw_delivery_type" value="HOME">
    <!-- Checkout Champ custom fields (tw_store_*): set by TW.fill() on submit, empty for HOME.
         integrated.js addTwStorePickup() adds them to the order; OfferApi::customFields() sends them as custom_order_tw_*. -->
    <input type="hidden" id="tw_store_id" value="">
    <input type="hidden" id="tw_store_name" value="">
    <input type="hidden" id="tw_store_address" value="">
    <input type="hidden" id="tw_store_manual" value="">
</div>

 <div class="tw-mb-2 tw-flex tw-gap-1 tw-text-[#4D4D4D]" style="font-size:0.75em; padding: 7px;border: 1px solid #f6ca79;background: #fef6e9;line-height: 1.3;" >
            <span aria-hidden="true" style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:1.3em;height:1.3em;border-radius:3px;background:#2f7cf6;color:#fff;font-family:Georgia,'Times New Roman',serif;font-weight:bold;font-style:normal;font-size:1em;line-height:1;">i</span><span><?= $collector_sh->translate("type_zhtranditional_new_tw", "Please enter your address in Traditional Chinese.") ?></span>
    </div>
    
<div class="mb-3">
    <label class="p cart-input-label" for="city" label="County / city"><?=$collector_sh->translate("town_city_new".$country, "County / city"); ?></label>
    <div class="self-autocomplete">
        <input id="fields_city" 
               onErrorEmpty="<?=$collector_sh->translate("city_empty".$country, "Select your county or city.");?>"
               trigger="click focus" readonly toenableel="fields_district" toSearch="city" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" name="city" class="search cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_town_city_new2".$country, "Select County / City"); ?>" required="required">
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
                        <input type="text" trigger="click focus" linkElement="fields_zip" disabled readonly
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
                        <input disabled id="fields_zip" name="zip" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_zip_new".$country, "Auto"); ?>" required="required">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="twHomeBlock">
<div class="mb-3">
    <label class="p cart-input-label" for="address 1" label="Address Line 1"><?=$collector_sh->translate("address_new".$country, "Address") ?></label>
    <div>
        <input id="fields_address1" name="address 1"
          onErrorEmpty="<?=$collector_sh->translate("on_error_address1_empty".$country, "Enter road, lane and number.");?>"
          onErrorValue="<?=$collector_sh->translate("on_error_address1_".$country, "Enter your address in Traditional Chinese.");?>"
          onErrorNumber="<?= $twH('addr_no_err'); ?>"  class="cart-input p" value="" type="text" placeholder="例：信義路五段7號" required="required">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="address 2" label="Building Name, Room Number, Company Dept."><?=$collector_sh->translate("tw_address".$country, "Floor / unit (optional)"); ?></label>
    <div>
        <input id="fields_address2" name="address 2" onErrorValue="<?=$collector_sh->translate("on_error_address2_new_".$country, "Enter your floor/unit in Traditional Chinese.");?>" class="cart-input p" value="" type="text" placeholder="例：12 樓之 3">
    </div>
</div>
</div>

<!-- STORE PICKUP (711 / FAMILY / HILIFE): county/city, district and postal code above are shared with home delivery. -->
<div id="twStoreBlock" class="tw-store-wrap" hidden>
    <div class="mb-3" id="twFieldStore">
        <label class="p cart-input-label" for="twStoreSearch"><?= $twH('pickup_store'); ?></label>
        <div id="twStorePicker">
            <input type="search" id="twStoreSearch" class="cart-input p tw-store-search" placeholder="<?= $twH('search_ph'); ?>" autocomplete="off" disabled>
            <div class="tw-store-list" id="twStoreList" role="radiogroup" aria-label="<?= $twH('pickup_store'); ?>"><div class="tw-msg"><?= $twH('store_first'); ?></div></div>
        </div>
        <div class="tw-picked" id="twPicked" hidden>
            <div><div class="tw-nm" id="twPickedName"></div><div class="tw-ad" id="twPickedAddr"></div></div>
            <button type="button" class="tw-linkbtn" id="twChangeStore"><?= $twH('change_store'); ?></button>
        </div>
    </div>

    <label class="tw-check"><input type="checkbox" id="twManual"> <?= $twH('not_listed'); ?></label>
    <div class="tw-manual" id="twManualBlock" hidden>
        <?php
        // "Store number (optional)" / "門市店號（非必填）": show a trailing bracketed part in grey, as in the mock
        $twOptLabel = function ($k) use ($twH) {
            return preg_replace('/\s*([（(][^（()）]*[)）])\s*$/u', ' <span class="tw-opt">$1</span>', $twH($k));
        };
        ?>
        <div class="mb-3">
            <label class="p cart-input-label" for="twStoreId"><?= $twOptLabel('store_number'); ?></label>
            <input type="text" id="twStoreId" class="cart-input p" inputmode="numeric" maxlength="6" autocomplete="off">
        </div>
        <div class="mb-3">
            <label class="p cart-input-label" for="twStoreName"><?= $twH('store_name'); ?></label>
            <input type="text" id="twStoreName" class="cart-input p" placeholder="例：信義門市" autocomplete="off">
        </div>
        <div class="mb-3">
            <label class="p cart-input-label" for="twStoreAddr"><?= $twOptLabel('store_address'); ?></label>
            <input type="text" id="twStoreAddr" class="cart-input p" placeholder="例：台北市信義區信義路五段 7 號" autocomplete="off">
        </div>
    </div>
    <div class="tw-hint"><?= $twHintHtml(); ?></div>
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
            areaChanged();
        }
    });

    // County/city or district changed: reload the pickup-store list right away (like the mock's change handlers).
    function areaChanged() {
        var tw = window.__twStorePickup;
        if (tw && typeof tw.reload === 'function') tw.reload();
    }
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
            areaChanged();
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
        } else if (valid && address1.getAttribute('data-tw-need-no') === '1') {
            // house number (號) is checked on submit (home delivery); once that has run, blur keeps checking it
            // so the message doesn't disappear before the number is added
            inlineError(address1, null, address1.value.indexOf('號') !== -1, address1.getAttribute("onErrorNumber"));
        } else {
            inlineError(address1, null, valid, address1.getAttribute("onErrorValue"));
            if(!valid) {
                address1.value="";
            }
        }
        
        
    });

    address2.addEventListener('blur', function () {
        var empty = address2.value.trim() == '';
        var valid = empty || /[\u4e00-\u9fff]/.test(address2.value.trim());

        inlineError(address2, null, valid, address2.getAttribute("onErrorValue"));
        if(!valid) {
            address2.value="";
        }
    });
})();
</script>

<script>
// ===== Delivery type + convenience-store pickup =====
// This partial is included in the page and can be re-loaded over AJAX (country change), so elements are
// looked up fresh every time and the document-level listeners are registered only once.
(function () {
    var TW = window.__twStorePickup = window.__twStorePickup || {};
    TW.txt = <?= json_encode($twT, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    TW.state = { picked: null, areaStores: [], lastArea: '', filled: false };

    var CHAIN    = { '711': '7-ELEVEN', 'FAMILY': '全家', 'HILIFE': '萊爾富' };   // order payload (shipAddress1), always Chinese
    var ID_RE    = { '711': /^\d{6}$/, 'FAMILY': /^0\d{5}$/, 'HILIFE': /^\d{4}$/ };
    var ID_PH    = { '711': '例：238038', 'FAMILY': '例：021695', 'HILIFE': '例：4162' };
    // Store list comes from the address API (tw_store_lookup) through the integrated/ search_data proxy.
    // src/shipping/ -> <site root>/integrated/
    var ENDPOINT = <?= json_encode(preg_replace('#src/shipping/$#', '', $twBaseUrl) . 'integrated/?search_data=true&country=tw&toSearch=store&q=', JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?>;

    var $id = function (id) { return document.getElementById(id); };
    function txt(k) { return TW.txt[k] || ''; }
    function chainUi(t) { return t === '711' ? '7-ELEVEN' : (t === 'FAMILY' ? txt('family') : txt('hilife')); }
    function type() { var el = $id('tw_delivery_type'); return el ? el.value : 'HOME'; }
    function enabled() { return !!document.querySelector('[data-tw-store-pickup]'); }
    function isStore() { return enabled() && type() !== 'HOME'; }
    function val(id) { var el = $id(id); return el ? el.value.trim() : ''; }
    function esc(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;'); }
    // full-width digits -> half-width, 台 -> 臺, lower-case: search matching only
    function norm(s) { return String(s).replace(/[０-９]/g, function (c) { return String.fromCharCode(c.charCodeAt(0) - 0xFEE0); }).replace(/台/g, '臺').toLowerCase(); }

    // Same inline error markup as the rest of the checkout: red <span class="error-message"> after the anchor.
    function inlineError(field, anchor, valid, msg) {
        anchor = anchor || field;
        if (!anchor || !anchor.parentNode) return valid;
        var next = anchor.nextElementSibling;
        if (next && next.classList && next.classList.contains('error-message')) next.parentNode.removeChild(next);
        if (field) field.classList.toggle('error', !valid);
        if (!valid) {
            var span = document.createElement('span');
            span.className = 'error-message';
            span.style.color = 'red';
            span.style.fontSize = '14px';
            span.style.display = 'block';
            span.textContent = msg;
            anchor.parentNode.insertBefore(span, anchor.nextSibling);
        }
        return valid;
    }
    function setAddr(a1, a2) {
        var f1 = $id('fields_address1'), f2 = $id('fields_address2');
        if (f1) f1.value = a1;
        if (f2) f2.value = a2;
    }
    function phoneAnchor() { var p = $id('fields_phone'); return p ? (p.closest('.iti') || p) : null; }

    // ---- tabs ----
    function selectType(t) {
        var input = $id('tw_delivery_type');
        if (!input) return;
        Array.prototype.forEach.call(document.querySelectorAll('.tw-tab'), function (b) {
            b.setAttribute('aria-selected', b.getAttribute('data-type') === t ? 'true' : 'false');
        });
        input.value = t;
        $id('twHomeBlock').hidden = t !== 'HOME';
        $id('twStoreBlock').hidden = t === 'HOME';
        $id('twStoreId').placeholder = ID_PH[t] || '';
        $id('twStoreId').maxLength = t === 'HILIFE' ? 4 : 6;
        clearStoreErrors();
        setStoreFields('', '', '', '');   // a store is only kept once it's validated on submit (TW.fill)
        if (t === 'HOME') {
            if (TW.state.filled) { setAddr('', ''); TW.state.filled = false; }
        }
        // the mobile check applies to every type, only the wording differs: refresh a message that's already showing
        var phone = $id('fields_phone');
        if (phone && phone.classList.contains('error') && phone.value.trim() !== '') validatePhone();
        TW.state.lastArea = '';
        loadStores();
        document.dispatchEvent(new CustomEvent('tw:deliverytype', { detail: { type: t } }));
    }

    // ---- store picker ----
    function fetchStores(chain, city, district) {
        var q = '&chain=' + encodeURIComponent(chain) + '&city=' + encodeURIComponent(city) + '&district=' + encodeURIComponent(district);
        return fetch(ENDPOINT + q, { credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (data) {
                // -> [[store_id, store_name, store_address], ...] as used by render()/pick()
                var stores = data && Array.isArray(data.stores) ? data.stores : [];
                return stores.map(function (s) { return [String(s.store_id), s.store_name, s.store_address]; });
            });
    }
    function listMsg(msg) { $id('twStoreList').innerHTML = '<div class="tw-msg">' + esc(msg) + '</div>'; }
    function loadStores() {
        if (!isStore() || !$id('twStoreList')) return;
        var chain = type(), c = val('fields_city'), d = val('fields_district');
        var area = chain + '|' + c + '|' + d;
        if (area === TW.state.lastArea) return;
        TW.state.lastArea = area;
        unpick();
        var search = $id('twStoreSearch');
        search.value = '';
        search.disabled = true;
        TW.state.areaStores = [];
        if (!c || !d) { listMsg(txt('store_first')); return; }
        listMsg(txt('store_loading'));
        fetchStores(chain, c, d).then(function (rows) {
            if (area !== TW.state.lastArea) return; // stale response
            TW.state.areaStores = rows;
            search.disabled = !rows.length;
            render();
        }, function () {
            if (area !== TW.state.lastArea) return;
            TW.state.lastArea = ''; // retry on the next check
            listMsg(txt('store_error'));
        });
    }
    function render() {
        var rowsAll = TW.state.areaStores;
        if (!rowsAll.length) { listMsg(txt('store_none').replace('{chain}', chainUi(type()))); return; }
        var q = norm($id('twStoreSearch').value.trim());
        var rows = q ? rowsAll.filter(function (s) { return norm(s[0] + ' ' + s[1] + ' ' + s[2]).indexOf(q) !== -1; }) : rowsAll;
        if (!rows.length) { listMsg(txt('store_nomatch')); return; }
        $id('twStoreList').innerHTML = rows.map(function (s) {
            return '<label class="tw-store"><input type="radio" name="tw_store_pick" value="' + esc(s[0]) + '">' +
                '<div><div class="tw-nm">' + esc(s[1]) + '<span class="tw-no">' + esc(txt('store_no')) + ' ' + esc(s[0]) + '</span></div>' +
                '<div class="tw-ad">' + esc(s[2]) + '</div></div></label>';
        }).join('');
    }
    function unpick() {
        TW.state.picked = null;
        if (!$id('twStorePicker')) return;
        $id('twStorePicker').hidden = false;
        $id('twPicked').hidden = true;
        Array.prototype.forEach.call($id('twStoreList').querySelectorAll('input'), function (i) { i.checked = false; });
    }
    function pick(id) {
        var s = TW.state.areaStores.filter(function (x) { return x[0] === id; })[0];
        if (!s) return;
        TW.state.picked = s;
        $id('twPickedName').textContent = chainUi(type()) + ' ' + s[1] + '　' + txt('store_no') + ' ' + s[0];
        $id('twPickedAddr').textContent = s[2];
        $id('twStorePicker').hidden = true;
        $id('twPicked').hidden = false;
        validatePicked();
    }

    // ---- validation ----
    function validatePicked() {
        // red border on the search box + list (or the picked card), like the mock
        $id('twFieldStore').classList.toggle('tw-invalid', !TW.state.picked);
        // anchored after #twPicked (last element of the field) so the message sits under the picker or the picked card
        return inlineError(null, $id('twPicked'), !!TW.state.picked, txt('store_err'));
    }
    function idErr() { var t = type(); return t === 'FAMILY' ? txt('id_err_family') : (t === 'HILIFE' ? txt('id_err_hilife') : txt('id_err_711')); }
    // Store number is optional: blank is fine, but a number that is entered must match the chain's format.
    function validateStoreId() {
        var t = type(), v = val('twStoreId');
        return inlineError($id('twStoreId'), null, v === '' || (ID_RE[t] ? ID_RE[t].test(v) : false), idErr());
    }
    function validateStoreName() { return inlineError($id('twStoreName'), null, val('twStoreName') !== '', txt('name_err')); }
    function validateStoreAddr() { return inlineError($id('twStoreAddr'), null, val('twStoreAddr') !== '', txt('addr_err')); }
    // Phone digits without the +886 country code (0912 345 678 / +886 912 345 678 -> 0912345678 / 912345678)
    function phoneDigits() { var el = $id('fields_phone'); return el ? el.value.replace(/\D/g, '').replace(/^886/, '') : ''; }
    function mobileOk() {
        var el = $id('fields_phone');
        if (!el) return true;
        var iso = 'tw';
        try {
            var inst = window.intlTelInputGlobals && window.intlTelInputGlobals.getInstance(el);
            if (inst) iso = (inst.getSelectedCountryData().iso2 || '').toLowerCase();
        } catch (e) {}
        return iso === 'tw' && /^0?9\d{8}$/.test(phoneDigits());
    }
    // Store pickup: a number starting with 09 / 9 that fails is a mobile with too few or too many digits
    // (e.g. 091234567, 09123456789), so it gets its own message; anything else (a landline) keeps mobile_err.
    function mobileErr() {
        if (!isStore()) return txt('mobile_err_home');
        return /^0?9/.test(phoneDigits()) ? txt('mobile_err_len') : txt('mobile_err');
    }
    // Home delivery accepts landline and mobile numbers: never show a phone error there. Store pickup needs a mobile.
    function validatePhone() { return inlineError($id('fields_phone'), phoneAnchor(), isStore() ? mobileOk() : true, mobileErr()); }
    // Home delivery address: same rules and messages as the blur checks in the first script
    // (Address required + Traditional Chinese, Floor/unit optional but Traditional Chinese when entered),
    // plus, on submit only: Address must include the house number 號 (信義路五段 fails, 信義路五段7號 passes).
    var HAN_RE = /[一-鿿]/;
    function addr1Ok(v) { return v !== '' && HAN_RE.test(v) && v.indexOf('號') !== -1; }
    function addr1Msg() {
        var el = $id('fields_address1');
        if (!el) return '';
        var v = el.value.trim();
        if (v === '') return el.getAttribute('onErrorEmpty') || '';
        if (!HAN_RE.test(v)) return el.getAttribute('onErrorValue') || '';
        return txt('addr_no_err');
    }
    function validateAddr1() {
        var el = $id('fields_address1');
        if (!el) return true;
        el.setAttribute('data-tw-need-no', '1');   // from now on the blur check in the first script also checks 號
        return inlineError(el, null, addr1Ok(el.value.trim()), addr1Msg());
    }
    function addr2Msg() { var el = $id('fields_address2'); return el ? el.getAttribute('onErrorValue') || '' : ''; }
    function validateAddr2() {
        var el = $id('fields_address2');
        if (!el) return true;
        var v = el.value.trim();
        return inlineError(el, null, v === '' || HAN_RE.test(v), addr2Msg());
    }
    function clearStoreErrors() {
        if (!$id('twStoreBlock')) return;
        Array.prototype.forEach.call($id('twStoreBlock').querySelectorAll('.error-message'), function (e) { e.parentNode.removeChild(e); });
        Array.prototype.forEach.call($id('twStoreBlock').querySelectorAll('.error'), function (e) { e.classList.remove('error'); });
        Array.prototype.forEach.call($id('twStoreBlock').querySelectorAll('.tw-invalid'), function (e) { e.classList.remove('tw-invalid'); });
    }
    // Validates the store fields or, for home delivery, the address fields (plus the mobile number for both)
    // and shows their inline errors. TW.errors holds the messages that failed, for the submit popup.
    TW.validate = function () {
        TW.errors = [];
        if (!enabled()) return true;
        var check = function (ok, msg) { if (!ok) TW.errors.push(msg); return ok; };
        if (!isStore()) {
            var m1 = addr1Msg();   // read first: the message depends on the value
            check(validateAddr1(), m1);
            check(validateAddr2(), addr2Msg());
        } else if ($id('twManual').checked) {
            check(validateStoreId(), idErr());
            check(validateStoreName(), txt('name_err'));
            check(validateStoreAddr(), txt('addr_err'));
        } else {
            check(validatePicked(), txt('store_err'));
        }
        check(validatePhone(), mobileErr());
        return TW.errors.length === 0;
    };

    // Store fields failed on submit: also run the shared checkout validation so every other required field
    // gets its inline error too, then show all messages in the shared error popup. The order is never sent.
    function showSubmitErrors() {
        var crm = window.CRMIntegrated;
        if (crm && typeof crm.validateForm === 'function' && typeof crm.showMsg === 'function') {
            // The shared check clears inline messages on fields it considers valid (e.g. a landline phone),
            // so the store checks run again afterwards to put their inline errors back.
            crm.validateForm(function () {
                TW.validate();
                crm.showMsg(TW.errors);                                   // only the store fields are wrong
            }, function (msg) {
                TW.validate();
                crm.showMsg([].concat(msg || [], TW.errors));             // shared errors + store errors
            });
            return;
        }
        var err = document.querySelector('#twStoreBlock .error-message, #twHomeBlock .error-message, .iti + .error-message');
        if (err) err.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }
    // Writes the store into the address fields the shared submit code sends (shipAddress1 / shipAddress2).
    TW.fill = function () {
        var m = $id('twManual').checked, s = TW.state.picked;
        var id = m ? val('twStoreId') : s[0];          // text, leading zeros kept
        var name = m ? val('twStoreName') : s[1];
        var addr = m ? val('twStoreAddr') : s[2];
        // store number is optional on manual entry: leave out the （…） part when it's blank
        setAddr(CHAIN[type()] + ' ' + name + (id ? '（' + id + '）' : ''), addr || val('fields_district'));
        setStoreFields(id, name, addr, m ? '1' : '0');   // Checkout Champ custom fields
        TW.state.filled = true;
    };
    // Hidden inputs read by integrated.js (tw_store_id is text: leading zeros kept, e.g. 004114).
    function setStoreFields(id, name, addr, manual) {
        var f = { tw_store_id: id, tw_store_name: name, tw_store_address: addr, tw_store_manual: manual };
        Object.keys(f).forEach(function (k) { var el = $id(k); if (el) el.value = f[k]; });
    }

    // ---- home delivery only ----
    // TW.setHomeOnly(true) disables the store pickup tabs and switches to HOME (checkout-tw-cod-v2.php calls it while
    // Card is the payment method: store pickup is cash on delivery only). The flag is data-tw-home-only="1" on the
    // page's [data-tw-store-pickup] form, so it survives this partial being re-loaded over AJAX (country change) and
    // the page can set it before the partial exists.
    function pickupForm() { return document.querySelector('[data-tw-store-pickup]'); }
    function homeOnly() { var f = pickupForm(); return !!(f && f.getAttribute('data-tw-home-only') === '1'); }
    function applyHomeOnly() {
        var on = homeOnly();
        Array.prototype.forEach.call(document.querySelectorAll('.tw-tab'), function (b) {
            b.disabled = on && b.getAttribute('data-type') !== 'HOME';
        });
        var note = $id('twHomeOnlyNote');
        if (note) note.hidden = !on;
        if (on && type() !== 'HOME') selectType('HOME');
    }
    TW.setHomeOnly = function (on) {
        var f = pickupForm();
        if (f) { if (on) f.setAttribute('data-tw-home-only', '1'); else f.removeAttribute('data-tw-home-only'); }
        applyHomeOnly();
    };
    TW.applyHomeOnly = applyHomeOnly;

    // ---- per-render setup ----
    TW.reload = loadStores;   // called by the county/city + district value setters above
    if (enabled()) $id('twDeliveryTypeWrap').hidden = false;
    applyHomeOnly();          // first, so the tabs are locked even if anything below fails
    selectType('HOME');

    // ---- document-level listeners, registered once ----
    if (TW.bound) return;
    TW.bound = true;

    document.addEventListener('click', function (e) {
        var tab = e.target.closest && e.target.closest('.tw-tab');
        if (tab) { if (!tab.disabled && tab.getAttribute('data-type') !== type()) selectType(tab.getAttribute('data-type')); return; }
        if (e.target.closest && e.target.closest('#twChangeStore')) { unpick(); $id('twStoreSearch').focus(); return; }

        // Runs in the capture phase, before the shared #submitForm handler in integrated.js:
        // invalid store / home address fields stop the click there, so the order is never submitted.
        if (e.target.closest && e.target.closest('#submitForm') && enabled()) {
            if (!TW.validate()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                showSubmitErrors();
                return;
            }
            if (isStore()) TW.fill();
        }
    }, true);

    // Backup for a native form submit (e.g. a script calling requestSubmit): never let invalid store / home fields through.
    document.addEventListener('submit', function (e) {
        if (enabled() && e.target.querySelector && e.target.querySelector('#twStoreBlock') && !TW.validate()) {
            e.preventDefault();
            e.stopImmediatePropagation();
            showSubmitErrors();
        }
    }, true);

    document.addEventListener('change', function (e) {
        if (e.target.name === 'tw_store_pick') { pick(e.target.value); return; }
        if (e.target.id === 'twManual') {
            $id('twManualBlock').hidden = !e.target.checked;
            $id('twFieldStore').hidden = e.target.checked;
            clearStoreErrors();
            if (e.target.checked) $id('twStoreId').focus();
        }
    });
    document.addEventListener('input', function (e) {
        if (e.target.id === 'twStoreSearch') render();
        if (e.target.id === 'twStoreId') e.target.value = e.target.value.replace(/\D/g, '');
        // once a field shows an error, re-check while typing so the message clears as soon as it's fixed
        if (e.target.id === 'twStoreId' && e.target.classList.contains('error')) validateStoreId();
        if (e.target.id === 'twStoreName' && e.target.classList.contains('error')) validateStoreName();
        if (e.target.id === 'twStoreAddr' && e.target.classList.contains('error')) validateStoreAddr();
        if (e.target.id === 'fields_phone' && enabled() && e.target.classList.contains('error') && mobileOk()) validatePhone();
        if (enabled() && !isStore() && e.target.classList.contains('error')) {
            if (e.target.id === 'fields_address1' && addr1Ok(e.target.value.trim())) validateAddr1();   // clear once fixed
            if (e.target.id === 'fields_address2') validateAddr2();
        }
    });
    document.addEventListener('blur', function (e) {
        if (!enabled()) return;
        if (e.target.id === 'fields_phone' && e.target.value.trim() !== '') validatePhone();
        if (!isStore()) return;   // home address fields are checked on blur by the first script
        if (e.target.id === 'twStoreId') validateStoreId();
        if (e.target.id === 'twStoreName') validateStoreName();
        if (e.target.id === 'twStoreAddr') validateStoreAddr();
    }, true);

    // County/city and district are set by the suggestion script without change events: poll for changes.
    setInterval(loadStores, 400);
})();
</script>
<?php $collector_sh->saveTranslation(); ?>