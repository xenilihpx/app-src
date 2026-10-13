<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
  targetLanguage: $OfferApi->targetLanguage,
  pageName: "checkout-shipping"
);

$country="pt";
?>
<style>
#pt-shipping-form .pt-address-confirm { color: #6b6b6b; font-size: 0.9em; margin-top: 6px; }
#pt-shipping-form .pt-manual-note { font-size: 0.85em; color: #6b6b6b; }
#pt-shipping-form .pt-door-hint { color: #b45309; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 4px; font-size: 0.85em; padding: 6px 8px; margin-top: 6px; }
</style>

<div id="pt-shipping-form">
    <div class="mb-1">
        <label class="p cart-input-label" for="fields_address1"><?= $collector_sh->translate("address_".$country, "Address") ?></label>
        <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" autocomplete="off" placeholder="<?= $collector_sh->translate("p_address_".$country, "E.g. Rua de Santa Catarina, 123") ?>" required="required">
        <!-- Código postal + Localidade read back from Google Places, shown as one grey line -->
        <div id="pt_address_confirm" class="p pt-address-confirm" style="display:none;"></div>
        <!-- Shown when the picked Places result has no street_number (customer picked a bare street) -->
        <div id="pt_door_hint" class="p pt-door-hint" style="display:none;"><?= $collector_sh->translate("door_number_hint_".$country, "Add the door number. E.g. Rua de Santa Catarina, 123") ?></div>
    </div>
    <div class="mb-3">
        <a href="javascript:void(0);" id="pt_manual_toggle" class="p" style="font-size: 0.85em; color: #1a73e8;"><?= $collector_sh->translate("manual_link_".$country, "Can't find your address? Enter it manually.") ?></a>
        <span id="pt_manual_note" class="p pt-manual-note" style="display:none;"><?= $collector_sh->translate("manual_note_".$country, "Fill in the fields manually.") ?></span>
    </div>

    <!-- Hidden until manual mode opens (link click, or a Places result without a valid postal code/locality) -->
    <div id="pt_manual_fields" style="display:none;">
        <div class="mb-3">
            <label class="p cart-input-label" for="fields_zip"><?= $collector_sh->translate("postal_code_".$country, "Postal code") ?></label>
            <input id="fields_zip" name="zip" class="cart-input p" value="" type="text" inputmode="numeric" maxlength="8" pattern="[0-9]{4}-[0-9]{3}" placeholder="<?= $collector_sh->translate("p_postal_code_".$country, "0000-000") ?>" required="required">
        </div>
        <div class="mb-3">
            <label class="p cart-input-label" for="fields_city"><?= $collector_sh->translate("locality_".$country, "Town") ?></label>
            <input id="fields_city" name="city" class="cart-input p" value="" type="text" placeholder="<?= $collector_sh->translate("p_locality_".$country, "E.g. Porto") ?>" required="required">
        </div>
    </div>

    <div class="mb-3">
        <label class="p cart-input-label" for="fields_address2"><?= $collector_sh->translate("unit_".$country, "Floor / unit (optional)") ?></label>
        <input id="fields_address2" name="address 2" class="cart-input p" value="" type="text" optional placeholder="<?= $collector_sh->translate("p_unit_".$country, "E.g. 2.º Esq.") ?>">
    </div>

    <!-- District (administrative_area_level_1) from Places -> shipState. No field on screen; kept as
         the hidden, optional #fields_state the shared checkout JS expects. Empty in manual mode. -->
    <div class="mb-3 d-none">
        <select id="fields_state" name="state" class="cart-input p" optional>
            <option value=""></option>
        </select>
    </div>
    <input type="hidden" id="pt_place_id" value="">
</div>

<script>
(function () {
    var ZIP_RE = /^\d{4}-\d{3}$/;
    var form = document.getElementById('pt-shipping-form');
    var address1 = document.getElementById('fields_address1');
    var address2 = document.getElementById('fields_address2');
    var zip = document.getElementById('fields_zip');
    var city = document.getElementById('fields_city');
    var state = document.getElementById('fields_state');
    var placeId = document.getElementById('pt_place_id');
    var confirmLine = document.getElementById('pt_address_confirm');
    var manualLink = document.getElementById('pt_manual_toggle');
    var manualNote = document.getElementById('pt_manual_note');
    var manualFields = document.getElementById('pt_manual_fields');
    var doorHint = document.getElementById('pt_door_hint');
    var picked = false;
    var manualMode = false;
    // Street string Places returned when the pick had NO street_number - '' otherwise. The door
    // number check compares the Morada against this, never 'does the Morada contain a digit'
    // (street names like 'Rua 5 de Outubro' or 'Avenida 25 de Abril' already contain numbers).
    var bareStreet = '';

    function isBareStreet() {
        return bareStreet !== '' && address1.value.trim() === bareStreet;
    }

    function updateDoorHint() {
        doorHint.style.display = isBareStreet() ? '' : 'none';
    }

    function msg(key, fallback) {
        return (window.i18nData && window.i18nData[key]) || fallback;
    }

    // Same "<span class='error-message'>" markup the rest of the checkout uses. Self-contained
    // because the shared form_obj isn't reliably reachable from this dynamically-loaded partial.
    function inlineError(field, valid, text) {
        var next = field.nextElementSibling;
        if (next && next.classList && next.classList.contains('error-message')) next.parentNode.removeChild(next);
        field.classList.toggle('error', !valid);
        field.classList.toggle('valid', valid);
        if (!valid) {
            var span = document.createElement('span');
            span.className = 'error-message';
            span.style.color = 'red';
            span.style.fontSize = '14px';
            span.textContent = text;
            field.parentNode.insertBefore(span, field.nextSibling);
        }
        return valid;
    }

    function clearError(field) {
        var next = field.nextElementSibling;
        if (next && next.classList && next.classList.contains('error-message')) next.parentNode.removeChild(next);
        field.classList.remove('error');
    }

    function setState(value) {
        if (value && !state.querySelector('option[value="' + value.replace(/"/g, '\\"') + '"]')) {
            state.add(new Option(value, value));
        }
        state.value = value || '';
    }

    // 1250096 -> 1250-096
    function formatZip(value) {
        var digits = value.replace(/\D/g, '').slice(0, 7);
        return digits.length > 4 ? digits.slice(0, 4) + '-' + digits.slice(4) : digits;
    }

    function openManualMode() {
        manualMode = true;
        manualFields.style.display = '';
        confirmLine.style.display = 'none';
        manualLink.style.display = 'none';
        manualNote.style.display = '';
        setState(''); // shipState is pushed empty in manual mode - never guessed
        zip.value = formatZip(zip.value);
        clearError(address1);
    }

    function validateAddress() {
        if (address1.value.trim() === '' || !(picked || manualMode)) {
            return inlineError(address1, false, msg('invalid_address_pt', 'Select your address from the list.'));
        }
        return inlineError(address1, !isBareStreet(), msg('invalid_door_number_pt', 'Add the door number.'));
    }

    function validateZip() {
        return inlineError(zip, ZIP_RE.test(zip.value.trim()), msg('invalid_zip_pt', 'Format 0000-000.'));
    }

    function validateCity() {
        return inlineError(city, city.value.trim() !== '', msg('invalid_city_pt', 'Enter the town.'));
    }

    // Picked from Google Places (dispatched by src/common/js/address.google.autocomplete.js).
    address1.addEventListener('address:picked', function (e) {
        var d = e.detail || {};
        picked = true;
        placeId.value = d.placeId || '';
        zip.value = formatZip(d.postcode || '');
        city.value = d.city || '';
        bareStreet = (d.street && !d.streetNumber) ? d.street.trim() : '';
        clearError(address1);
        updateDoorHint();

        if (manualMode) { setState(''); return; }

        setState(d.district || '');
        if (ZIP_RE.test(zip.value) && city.value.trim() !== '') {
            confirmLine.textContent = zip.value + ' ' + city.value;
            confirmLine.style.display = '';
        } else {
            openManualMode(); // no valid código postal or localidade in the result
        }
    });

    // Editing the Morada after a pick clears código postal, localidade and the confirmation line -
    // the customer has to pick again. Programmatic fills don't fire 'input', so this is typing only.
    address1.addEventListener('input', function () {
        // Adding the door number to a bare-street pick keeps the pick (código postal, localidade).
        if (bareStreet !== '' && address1.value.trim().indexOf(bareStreet) === 0) {
            updateDoorHint();
            if (!isBareStreet()) clearError(address1);
            return;
        }
        if (manualMode) { updateDoorHint(); return; }
        bareStreet = '';
        updateDoorHint();
        picked = false;
        zip.value = '';
        city.value = '';
        placeId.value = '';
        setState('');
        confirmLine.textContent = '';
        confirmLine.style.display = 'none';
    });

    // Delayed so clicking a suggestion (which blurs the field first) isn't flagged before the pick lands.
    address1.addEventListener('blur', function () {
        setTimeout(validateAddress, 300);
    });

    zip.addEventListener('input', function () {
        var formatted = formatZip(this.value);
        if (formatted !== this.value) this.value = formatted;
    });
    zip.addEventListener('blur', validateZip);
    city.addEventListener('blur', validateCity);

    manualLink.addEventListener('click', function (e) {
        e.preventDefault();
        openManualMode();
    });

    function isActive() {
        return document.body.contains(form);
    }

    // true/false for the submit handler.
    window.validatePtShipping = function () {
        if (!isActive()) return true;
        var ok = validateAddress();
        if (manualMode) {
            ok = validateZip() && ok;
            ok = validateCity() && ok;
        }
        return ok;
    };

    window.getPtShippingPayload = function () {
        return {
            country: 'PT',
            street_address: address1.value.trim(),
            unit: address2.value.trim(),
            postal_code: zip.value.trim(),
            city: city.value.trim(),
            district: state.value,
            place_id: placeId.value
        };
    };

    // Printable address block, e.g. "Rua de Santa Catarina, 123, 2.º Esq.\n4000-447 Porto\nPortugal"
    window.formatPtAddress = function () {
        var p = window.getPtShippingPayload();
        return [
            p.street_address + (p.unit ? ', ' + p.unit : ''),
            (p.postal_code + ' ' + p.city).trim(),
            'Portugal'
        ].join('\n');
    };

    // Picked up by form_obj.validateForm() in src/common/js/integrated.js on submit.
    window.validateShippingForm = window.validatePtShipping;
})();
</script>
<?php $collector_sh->saveTranslation(); ?>
