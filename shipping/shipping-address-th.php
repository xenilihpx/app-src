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
                <span aria-hidden="true" style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:1.3em;height:1.3em;border-radius:3px;background:#2f7cf6;color:#fff;font-family:Georgia,'Times New Roman',serif;font-weight:bold;font-style:normal;font-size:1em;line-height:1;">i</span><span><?= $collector_sh->translate("type_english_us", $OfferApi->targetLanguage=="zh-hant" ? "請以英文填寫以下運送資訊。" : "请用英语填写以下发货信息。") ?></span>
            </div>
<?php } ?>

<style>
#fields_state,
#fields_city,
#fields_district {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23343a40' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 9px 5px;
    padding-right: 32px;
}
.th-field-hint {
    color: #6b7280;
    font-size: 0.8em;
    margin-top: 4px;
}
</style>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_state"><?= $collector_sh->translate("state_th", "Province"); ?></label>
    <!-- Options are populated by form_obj.addStatesProvince() in integrated.js from
         src/common/js/states.js's "TH" entry (already present, official two-digit
         province codes) - same mechanism every other country's state/province
         dropdown uses. Left empty here; that function always rebuilds it anyway. -->
    <select id="fields_state" name="state" class="cart-input p" required="required"></select>
</div>

<div class="mb-3">
    <?php $p_district_th = $collector_sh->translate("p_district_th", "Select province first"); ?>
    <label class="p cart-input-label" for="fields_city"><?= $collector_sh->translate("district_th", "District"); ?></label>
    <select id="fields_city" name="city" class="cart-input p" required="required" disabled data-placeholder="<?= htmlspecialchars($p_district_th, ENT_QUOTES); ?>">
        <option value=""><?= $p_district_th; ?></option>
    </select>
</div>

<div class="mb-3">
    <?php $p_subdistrict_th = $collector_sh->translate("p_subdistrict_th", "Select district first"); ?>
    <label class="p cart-input-label" for="fields_district"><?= $collector_sh->translate("subdistrict_th", "Sub-district"); ?></label>
    <select id="fields_district" name="ward" class="cart-input p" required="required" disabled data-placeholder="<?= htmlspecialchars($p_subdistrict_th, ENT_QUOTES); ?>">
        <option value=""><?= $p_subdistrict_th; ?></option>
    </select>
    <div id="th_postal_hint" class="th-field-hint" style="display:none;"></div>
</div>

<input type="hidden" id="fields_zip" name="zip" value="">

<div class="mb-3">
    <label class="p cart-input-label" for="fields_address1"><?= $collector_sh->translate("street_address_th", "Address"); ?></label>
    <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_street_address_th", "e.g. 123/45 Moo 4 Sukhumvit Road"); ?>" required="required">
    <div class="th-field-hint"><?= $collector_sh->translate("street_address_hint_th", "House number, moo, soi, road"); ?></div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_apt_unit"><?= $collector_sh->translate("unit_th", "Floor / Room (optional)"); ?></label>
    <input id="fields_apt_unit" name="apt_unit" class="cart-input p" value="" type="text" optional placeholder="<?= $collector_sh->translate("p_unit_th", "e.g. Floor 8 Room 802"); ?>">
</div>

<script>
(function () {
    var REPO = "<?= REPONAME ?>";
    var postalLabel = <?= json_encode($collector_sh->translate("postal_code_hint_th", "Postal code {zip}")); ?>;
    var provincePlaceholder = <?= json_encode($collector_sh->translate("p_state_th", "Select province")); ?>;
    var msgInvalidProvince = <?= json_encode($collector_sh->translate("invalid_province_th", "Please select a province.")); ?>;
    var msgInvalidDistrict = <?= json_encode($collector_sh->translate("invalid_district_th", "Please select a district.")); ?>;
    var msgInvalidSubdistrict = <?= json_encode($collector_sh->translate("invalid_subdistrict_th", "Please select a sub-district.")); ?>;
    var msgInvalidStreetAddress = <?= json_encode($collector_sh->translate("invalid_street_address_th", "Please enter your address.")); ?>;

    var provinceEl = document.getElementById('fields_state');
    var districtEl = document.getElementById('fields_city');
    var subdistrictEl = document.getElementById('fields_district');
    var zipEl = document.getElementById('fields_zip');
    var hintEl = document.getElementById('th_postal_hint');

    // #fields_state's own <option value="..."> codes come from states.js's "TH"
    // entry (official two-digit province codes, e.g. "10" for Bangkok) - a
    // different numbering than src/district/th/districts.json's province_id (which
    // follows the source dataset's own 1-77 sequence). This maps one to the
    // other so the district cascade below can filter correctly regardless of
    // which code scheme the shopper's actual <option> carries.
    // "S" (Phatthaya/Pattaya, a self-governing city rather than one of the 77
    // provinces) maps to Chon Buri, the province it actually sits within.
    var STATE_CODE_TO_PROVINCE_ID = {"37":26,"15":6,"31":20,"24":15,"18":9,"36":25,"22":13,"50":38,"57":45,"20":11,"86":69,"46":34,"62":49,"71":56,"40":28,"81":64,"10":1,"52":40,"51":39,"42":30,"16":7,"58":46,"44":32,"49":37,"26":17,"73":58,"48":36,"30":19,"60":47,"80":63,"55":43,"96":76,"39":27,"43":31,"12":3,"13":4,"94":74,"82":65,"93":73,"S":11,"56":44,"67":54,"76":61,"66":53,"65":52,"14":5,"54":42,"83":66,"25":16,"77":62,"85":68,"70":55,"21":12,"45":33,"27":18,"47":35,"11":2,"74":59,"75":60,"19":10,"91":71,"33":22,"17":8,"90":70,"64":51,"72":57,"84":67,"32":21,"63":50,"92":72,"23":14,"34":23,"41":29,"61":48,"53":41,"95":75,"35":24};

    var districts = []; // each has a nested .subdistricts array (see src/district/th/districts.json)
    var zipByTambonId = {};

    function resetSelect(el) {
        el.innerHTML = '<option value="">' + el.getAttribute('data-placeholder') + '</option>';
    }

    function fillSelect(el, items) {
        var frag = document.createDocumentFragment();
        var placeholderOpt = document.createElement('option');
        placeholderOpt.value = '';
        placeholderOpt.textContent = el.getAttribute('data-placeholder');
        frag.appendChild(placeholderOpt);
        items.forEach(function (it) {
            var opt = document.createElement('option');
            opt.value = it.id;
            opt.textContent = it.label;
            frag.appendChild(opt);
        });
        el.innerHTML = '';
        el.appendChild(frag);
    }

    fetch(REPO + '/src/district/th/districts.json').then(function (r) { return r.json(); }).then(function (data) {
        districts = data;
    }).catch(function (err) {
        console.error('Failed to load Thailand address data', err);
    });

    // form_obj.addStatesProvince() in integrated.js (see the comment on
    // #fields_state above) always writes its own generic placeholder
    // ("-- Select --" / window.i18nData['select']) into the empty-value
    // option after populating the list - same fix shipping-address-ro.php
    // uses for its own county dropdown. Runs after that population (which
    // happens synchronously on page load, and again on every country-field
    // change) to swap in the Thailand-specific wording instead.
    setTimeout(function () {
        var opt = provinceEl.querySelector('option[value=""]');
        if (opt) { opt.textContent = provincePlaceholder; }
    }, 400);

    provinceEl.addEventListener('change', function () {
        zipEl.value = '';
        hintEl.style.display = 'none';
        resetSelect(subdistrictEl);
        subdistrictEl.disabled = true;

        var provinceId = STATE_CODE_TO_PROVINCE_ID[provinceEl.value];
        if (!provinceId) {
            resetSelect(districtEl);
            districtEl.disabled = true;
            return;
        }

        var filtered = districts.filter(function (d) { return d.province_id === provinceId; });
        fillSelect(districtEl, filtered.map(function (d) { return { id: d.id, label: (d.prefix_th || '') + d.th }; }));
        districtEl.disabled = false;
    });

    districtEl.addEventListener('change', function () {
        zipEl.value = '';
        hintEl.style.display = 'none';

        if (districtEl.value === '') {
            resetSelect(subdistrictEl);
            subdistrictEl.disabled = true;
            return;
        }

        var districtId = parseInt(districtEl.value, 10);
        var district = districts.find(function (d) { return d.id === districtId; });
        var subdistricts = district ? district.subdistricts : [];
        zipByTambonId = {};
        subdistricts.forEach(function (s) { zipByTambonId[s.id] = s.zip; });
        fillSelect(subdistrictEl, subdistricts.map(function (s) { return { id: s.id, label: (s.prefix_th || '') + s.th }; }));
        subdistrictEl.disabled = false;
    });

    subdistrictEl.addEventListener('change', function () {
        var zip = subdistrictEl.value !== '' ? zipByTambonId[parseInt(subdistrictEl.value, 10)] : '';
        zipEl.value = zip || '';
        if (zip) {
            hintEl.textContent = postalLabel.replace('{zip}', zip);
            hintEl.style.display = 'block';
        } else {
            hintEl.style.display = 'none';
        }
    });

    // Field-specific inline error messages (blur + submit) - the shared
    // validateForm() in integrated.js only has a generic "Required Field."
    // message for #fields_state/#fields_city/#fields_address1, and its
    // "district" message is written for Indonesia's kecamatan. Using the
    // same window.CRMIntegrated.inlineErrorMsg() renderer keeps the look
    // identical while giving each Thai field its own wording.
    var addressEl = document.getElementById('fields_address1');
    var thRequiredFields = [
        { el: provinceEl, msg: msgInvalidProvince },
        { el: districtEl, msg: msgInvalidDistrict },
        { el: subdistrictEl, msg: msgInvalidSubdistrict },
        { el: addressEl, msg: msgInvalidStreetAddress }
    ];

    function showThFieldError(field) {
        var el = field.el;
        if (!el || el.offsetParent === null) { return; } // skip if hidden/disabled out of flow
        var value = (el.value || '').trim();
        var valid = value !== '';
        if (window.CRMIntegrated && typeof window.CRMIntegrated.inlineErrorMsg === 'function') {
            window.CRMIntegrated.inlineErrorMsg(el, value, valid, field.msg);
        }
        el.classList.toggle('error', !valid);
    }

    thRequiredFields.forEach(function (field) {
        if (!field.el) { return; }
        field.el.addEventListener('blur', function () { showThFieldError(field); });
        field.el.addEventListener('change', function () { showThFieldError(field); });
    });

    var submitBtnEl = document.getElementById('submitForm');
    if (submitBtnEl) {
        submitBtnEl.addEventListener('click', function () {
            // Runs after the shared validateForm() handler (registered earlier,
            // during initial page parse) so these messages are the ones left
            // showing instead of the generic/Indonesia-worded defaults.
            setTimeout(function () {
                thRequiredFields.forEach(showThFieldError);
            }, 0);
        });
    }
})();
</script>
<?php $collector_sh->saveTranslation(); ?>
