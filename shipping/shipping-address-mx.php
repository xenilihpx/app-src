<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);

$country="_mx";
?>
<?php if($OfferApi->targetLanguage=="zh-hant" || $OfferApi->targetLanguage=="zh-hans"){ ?>
            <div class="tw-mb-2 tw-flex tw-gap-1 tw-text-[#4D4D4D]" style="font-size:0.75em; padding: 7px;border: 1px solid #f6ca79;background: #fef6e9;line-height: 1.3;" >
                <span aria-hidden="true" style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:1.3em;height:1.3em;border-radius:3px;background:#2f7cf6;color:#fff;font-family:Georgia,'Times New Roman',serif;font-weight:bold;font-style:normal;font-size:1em;line-height:1;">i</span><span><?= $collector_sh->translate("type_english_us", "Please fill in the following shipping information in English.") ?></span>
            </div>
<?php } ?>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_zip" label="Zip/Postal Code"><?= $collector_sh->translate("zip".$country, "Postal code"); ?></label>
    <div>
        <!-- No suggestion list: one exact lookup per complete 5-digit code (see script below)
             fills the colonia dropdown plus #fields_city and #fields_state. -->
        <input id="fields_zip" autocomplete="postal-code" autocapitalize="off" autocorrect="off" spellcheck="false" name="zip" class="cart-input p" value="" type="text" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" placeholder="<?= $collector_sh->translate("p_zip_new_".$country, "E.g. 06600"); ?>" required="required">
    </div>
    <small id="state_municipality"
           data-searching="<?= htmlspecialchars($collector_sh->translate("zip_searching".$country, "Searching...", false), ENT_QUOTES); ?>"
           data-not-found="<?= htmlspecialchars($collector_sh->translate("zip_not_found".$country, "We couldn't find this postal code. Please check it.", false), ENT_QUOTES); ?>"></small>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="fields_neighborhood" label="neighborhood"><?=$collector_sh->translate("neighborhood".$country, "Neighborhood (colonia)"); ?></label>
    <div>
        <!-- Populated with every colonia of the selected postal code (see script below).
             The chosen colonia is copied into #fields_neighborhood (the submitted field);
             "not listed" reveals #fields_neighborhood so the shopper can type it instead. -->
        <!-- el-required: integrated.js validateForm() blocks submit while this is on the placeholder -->
        <select id="fields_neighborhood_drop" class="cart-input p" required="required" el-required disabled
                data-placeholder="<?= htmlspecialchars($collector_sh->translate("p_select_neighborhood".$country, "Select your neighborhood", false), ENT_QUOTES); ?>"
                data-not-listed="<?= htmlspecialchars($collector_sh->translate("neighborhood_not_listed".$country, "My neighborhood isn't listed"), ENT_QUOTES); ?>">
            <option value=""><?= $collector_sh->translate("p_select_neighborhood".$country, "Select your neighborhood",false); ?></option>
        </select>
    </div>
    <div id="fields_neighborhood_other_wrap" class="mt-2" style="display:none;">
        <input id="fields_neighborhood" name="neighborhood" class="cart-input p" value="" type="text" autocomplete="off" placeholder="<?=$collector_sh->translate("p_neighborhood".$country, "Neighborhood (colonia)");?>">
    </div>
</div>

<div class="mb-3">
    <label class="p cart-input-label" for="address 1" label="address 1"><?=$collector_sh->translate("address_1".$country, "Street") ?></label>
    <div>
        <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("p_address_1_new_".$country, "E.g. Av. Insurgentes Sur"); ?>" required="required">
    </div>
</div>

<div class="row align-items-start mb-3">
    <div class="col-sm-12">
        <div class="row mb-n3 align-items-start ">
            <div class="col-sm-6 pr-sm-2  ">
                <div class="mb-3">
                    <label class="p cart-input-label" for="fields_houseno"><?=$collector_sh->translate("Exterior_number ".$country, "Exterior number ") ?></label>
                    <div>
                        <input id="fields_houseno" name="fields_houseno" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("p_exterior_number_new_".$country, "E.g. 1234") ?>" required="required" data-gtm-form-interact-field-id="1">
                    </div>
                </div>
            </div>
            <div class="col-sm-6 num-in">
                <div class="mb-3">
                    <label class="p cart-input-label" for="fields_apt_unit"><?=$collector_sh->translate("interior_number_ ".$country, "Interior number (optional)") ?></label>
                    <div>
                        <input id="fields_apt_unit" name="fields_apt_unit" class="cart-input p" value="" type="text" optional placeholder="<?=$collector_sh->translate("p_interior_number_new_".$country, "E.g. Apt. 4B") ?>" data-gtm-form-interact-field-id="1">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-3 d-none">
    <label class="p cart-input-label" for="address 2" label="address 2"><?=$collector_sh->translate("address_2".$country, "Address 2"); ?></label>
    <div>
        <input id="fields_address2" name="address 2" class="cart-input p" value="" type="text" placeholder="<?=$collector_sh->translate("p_address_2".$country, "Address 2");?>" required="required">
    </div>
</div>

<div class="row align-items-start mb-3" style="display:none;">
    <div class="col-sm-12">
        <div class="row mb-n3 align-items-start ">
            <div class="col-sm-6 pr-sm-2  ">
                <div class="mb-3 ">
                    <label class="p cart-input-label" for="city" label="Town/City"><?=$collector_sh->translate("town_city".$country, "Municipality / borough"); ?></label>
                    <div>
                        <input id="fields_city" name="city" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_town_city".$country, "Municipality / borough"); ?>" required="required">
                    </div>
                </div>
            </div>
            <div class="col-sm-6 num-in">
                <div class="mb-3">
                    <label class="p cart-input-label" for="state" label="State/Province"><?= $collector_sh->translate("state".$country, "State"); ?></label>
                    <div>
                        <select id="fields_state" name="state" class="cart-input p" required="required">
                            <option value=""><?= $collector_sh->translate("p_state".$country, "Select State"); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- <div class="mb-3">
    <label class="p cart-input-label" for="fields_tax_id" label="tax_id"><?=$collector_sh->translate("tax_id".$country, "RFC or CURP (optional)"); ?></label>
    <p class="tw-text-xs tw-text-[#4D4D4D] tw-mb-1"><?=$collector_sh->translate("tax_id_sublabel".$country, "RFC (ej. GODE561231GR8) o CURP (ej. GODE561231HDFRRN04)"); ?></p>
    <div>
        <input id="fields_tax_id" name="tax_id" class="cart-input p" optional value="" type="text" maxlength="18" required="required">
    </div>
    <p class="tw-text-xs tw-text-[#4D4D4D] tw-mt-1 tw-mb-0"><?=$collector_sh->translate("tax_id_hint".$country, "La aduana puede solicitarlo en caso de inspección."); ?></p>
</div> -->

<script>
(function () {
    var zipField = document.getElementById('fields_zip');
    var stateMunicipality = document.getElementById('state_municipality');
    var cityField = document.getElementById('fields_city');
    var stateField = document.getElementById('fields_state');
    if (!zipField || !stateMunicipality || !cityField || !stateField) return;

    function clearFieldError(field) {
        field.classList.remove('error');
        var err = document.querySelector('#' + field.id + ' + span.error-message');
        if (err) err.remove();
    }

    // Put the state on the <select>: reuse a matching option, else add one
    function setState(state) {
        if (stateField.tagName !== 'SELECT') {
            stateField.value = state;
        } else if (!state) {
            stateField.selectedIndex = 0;
        } else {
            var match = null, key = state.toLowerCase();
            for (var i = 0; i < stateField.options.length; i++) {
                var o = stateField.options[i];
                if (o.value && (o.value.toLowerCase() === key || o.text.trim().toLowerCase() === key)) { match = o; break; }
            }
            if (!match) {
                match = new Option(state, state);
                stateField.add(match);
            }
            match.selected = true;
        }
        stateField.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function setCityState(city, state) {
        cityField.value = city;
        cityField.dispatchEvent(new Event('change', { bubbles: true }));
        setState(state);
        if (city) clearFieldError(cityField);
        if (state) clearFieldError(stateField);
        stateMunicipality.textContent = [city, state].filter(Boolean).join(', ');
    }

    // ---- Neighborhood (colonia) dropdown -------------------------------
    var neighborhoodSelect = document.getElementById('fields_neighborhood_drop');
    var otherWrap = document.getElementById('fields_neighborhood_other_wrap');
    var neighborhoodField = document.getElementById('fields_neighborhood'); // submitted value
    var NOT_LISTED = 'not-listed';
    var neighborhoodSeq = 0;

    function addOption(select, value, text) {
        var opt = document.createElement('option');
        opt.value = value;
        opt.textContent = text;
        select.appendChild(opt);
        return opt;
    }

    // Copy the dropdown's choice into #fields_neighborhood, or reveal it for
    // free typing when "not listed" is chosen.
    function syncNeighborhood(focusOther) {
        var selected = neighborhoodSelect.options[neighborhoodSelect.selectedIndex];
        var isOther = !!(selected && selected.dataset.role === NOT_LISTED);

        if (isOther) {
            // Don't carry a colonia picked earlier into the free-text field
            if (neighborhoodField.dataset.fromDrop === '1') neighborhoodField.value = '';
            neighborhoodField.dataset.fromDrop = '';
            otherWrap.style.display = 'block';
            if (focusOther) neighborhoodField.focus();
        } else {
            neighborhoodField.value = selected ? selected.value : '';
            neighborhoodField.dataset.fromDrop = '1';
            otherWrap.style.display = 'none';
        }

        neighborhoodField.classList.remove('error');
        var err = document.querySelector('#fields_neighborhood + span.error-message');
        if (err && neighborhoodField.value.trim() !== '') err.remove();

        // Clear the dropdown's own required error once something is chosen
        if (neighborhoodSelect.value !== '') {
            neighborhoodSelect.classList.remove('error');
            var dropErr = document.querySelector('#fields_neighborhood_drop + span.error-message');
            if (dropErr) dropErr.remove();
        }
    }

    // Placeholder only, disabled - until a postal code has been picked
    function resetNeighborhood() {
        neighborhoodSeq += 1;
        neighborhoodSelect.innerHTML = '';
        addOption(neighborhoodSelect, '', neighborhoodSelect.dataset.placeholder);
        neighborhoodSelect.disabled = true;
        syncNeighborhood(false);
    }

    function renderNeighborhoods(colonias) {
        neighborhoodSelect.innerHTML = '';

        // Several colonias: shopper must choose, so start on the placeholder
        if (colonias.length !== 1) {
            addOption(neighborhoodSelect, '', neighborhoodSelect.dataset.placeholder);
        }
        colonias.forEach(function (name) { addOption(neighborhoodSelect, name, name); });

        // Always last: "not listed" opens a free-text field below the dropdown
        // Non-empty value so the dropdown itself passes el-required; the revealed
        // #fields_neighborhood is then validated as the required field instead.
        var notListed = addOption(neighborhoodSelect, NOT_LISTED, neighborhoodSelect.dataset.notListed);
        notListed.dataset.role = NOT_LISTED;

        neighborhoodSelect.disabled = false;
        if (colonias.length === 1) {
            neighborhoodSelect.selectedIndex = 0;          // One colonia: pre-selected
        } else if (colonias.length === 0) {
            notListed.selected = true;                      // Lookup failed: free text only
        } else {
            neighborhoodSelect.selectedIndex = 0;          // Placeholder
        }
        syncNeighborhood(false);
    }

    // ---- Postal code lookup ----------------------------------------------
    // Exactly one request per complete 5-digit code; answers are cached so
    // re-entering a code already looked up doesn't hit the API again.
    var zipCache = {};
    var lookedUpZip = '';

    function applyZipResult(result) {
        setCityState(result.city, result.state);
        if (!result.colonias.length) stateMunicipality.textContent = stateMunicipality.dataset.notFound;
        renderNeighborhoods(result.colonias);
    }

    function lookupZip(zip) {
        if (zipCache[zip]) { applyZipResult(zipCache[zip]); return; }

        var countrySelect = document.getElementById('fields_country_select');
        var seq = ++neighborhoodSeq;
        var apiPath = (window.location.pathname.toLowerCase().indexOf('/mobile') !== -1) ? '../' : '';
        var url = apiPath + 'integrated/?search_data=true&toSearch=zipcode'
            + '&q=' + encodeURIComponent(zip)
            + '&country=' + encodeURIComponent(countrySelect ? countrySelect.value.toLowerCase() : 'mx');
        if (document.documentElement.lang) url += '&lang=' + encodeURIComponent(document.documentElement.lang);

        stateMunicipality.textContent = stateMunicipality.dataset.searching;

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.timeout = 15000;
        xhr.onload = function () {
            if (seq !== neighborhoodSeq) return;   // zip changed meanwhile
            var result = { colonias: [], city: '', state: '' };
            var ok = false;
            try {
                var data = JSON.parse(xhr.responseText);
                ok = xhr.status === 200 && data.success !== false;
                var seen = {};
                (data.suggestions || []).forEach(function (s) {
                    if (s.zipcode !== zip) return;  // exact code only
                    if (!result.city && s.city) result.city = s.city.trim();
                    if (!result.state && s.state) result.state = s.state.trim();
                    var name = (s.neighborhood || '').trim();
                    if (name && !seen[name.toLowerCase()]) {
                        seen[name.toLowerCase()] = true;
                        result.colonias.push(name);
                    }
                });
            } catch (err) { ok = false; }

            result.colonias.sort(function (a, b) { return a.localeCompare(b, 'es', { sensitivity: 'base' }); });
            if (ok) zipCache[zip] = result;           // failed requests can be retried
            applyZipResult(result);
        };
        xhr.ontimeout = xhr.onerror = function () {
            if (seq !== neighborhoodSeq) return;
            lookedUpZip = '';                         // allow a retry of the same code
            applyZipResult({ colonias: [], city: '', state: '' });
        };
        xhr.send();
    }

    function onZipChange() {
        // Digits only - also cleans pasted/autofilled values like "06 600"
        var digits = (zipField.value || '').replace(/\D/g, '').slice(0, 5);
        if (digits !== zipField.value) zipField.value = digits;
        if (digits === lookedUpZip) return;

        lookedUpZip = '';
        resetNeighborhood();
        setCityState('', '');

        if (digits.length === 5) {
            lookedUpZip = digits;
            lookupZip(digits);
        }
    }

    neighborhoodSelect.addEventListener('change', function () {
        syncNeighborhood(true);
    });

    // Anything typed by hand is the shopper's own value, not a dropdown copy
    neighborhoodField.addEventListener('input', function () {
        neighborhoodField.dataset.fromDrop = '';
    });

    // 'input' covers typing and paste; 'change' catches browser autofill
    zipField.addEventListener('input', onZipChange);
    zipField.addEventListener('change', onZipChange);

    // A code already present when the form is shown (e.g. restored on back)
    if (zipField.value) onZipChange();
})();
</script>

<?php $collector_sh->saveTranslation(); ?>

