<?php
if (!isset($OfferApi)) { include_once("../../integrated/setup.php"); }
include_once(BASEPATH . '/src/JsonTranslateCollector.php');
$collector_sh = new JsonCollector(
    targetLanguage: $OfferApi->targetLanguage,
    pageName: "checkout-shipping"
);

$country = "br";

// Messages used by the CEP lookup / validation script below
$br_msgs = [
    'looking'   => $collector_sh->translate("cep_looking".$country, "Looking up address..."),
    'notFound'  => $collector_sh->translate("cep_not_found".$country, "We couldn't find this CEP. Check it or fill in the address below."),
    'cep'       => $collector_sh->translate("err_cep".$country, "Enter a valid CEP."),
    'street'    => $collector_sh->translate("err_street".$country, "Enter your street."),
    'number'    => $collector_sh->translate("err_number".$country, "Enter the number."),
    'bairro'    => $collector_sh->translate("err_bairro".$country, "Enter your neighborhood."),
    'city'      => $collector_sh->translate("err_city".$country, "Enter your city."),
    'state'     => $collector_sh->translate("err_state".$country, "Select your state."),
    'cpf'       => $collector_sh->translate("err_cpf".$country, "Enter a valid CPF."),
];

// 27 UF codes - option value and text are both the code estado
$br_ufs = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];
?>

<style>
    #br-shipping-form .br-locked { background-color: #f3f3f3; color: #6b6b6b; }
    #br-shipping-form select.br-locked { pointer-events: none; }
    #br_cep_status.br-warn { color: #b45309; }
</style>

<div id="br-shipping-form">

<!-- 2. CEP -->
<div class="mb-3">
    <label class="p cart-input-label" for="fields_zip" label="Zip/Postal Code"><?= $collector_sh->translate("zip_cep".$country, "CEP (postal code)"); ?></label>
    <div>
        <input id="fields_zip" name="zip" class="cart-input p" value="" type="tel" inputmode="numeric" autocomplete="postal-code" maxlength="9" placeholder="00000-000" required="required">
    </div>
    <small id="br_cep_status" class="tw-block tw-text-xs" style="display:none;" aria-live="polite"></small>
    <a id="br_cep_help" href="https://buscacepinter.correios.com.br/app/endereco/index.php" target="_blank" rel="noopener noreferrer" class="tw-text-xs tw-text-blue-500 tw-underline"><?= $collector_sh->translate("dont_know_cep".$country, "Don't know your CEP?"); ?></a>
</div>

<!-- 3. Street -->
<div class="mb-3">
    <label class="p cart-input-label" for="fields_address1" label="Street"><?= $collector_sh->translate("street".$country, "Street") ?></label>
    <div>
        <input id="fields_address1" name="address 1" class="cart-input p" value="" type="text" autocomplete="address-line1" placeholder="<?= $collector_sh->translate("p_street".$country, "Street, avenue...") ?>" required="required">
    </div>
</div>

<!-- shipAddress2 stays empty for BR -->
<div class="mb-3 d-none">
    <label class="p cart-input-label" for="fields_address2" label="Address 2"><?= $collector_sh->translate("address_2".$country, "Address 2") ?></label>
    <div>
        <input id="fields_address2" name="address 2" class="cart-input p" value="" type="text" optional>
    </div>
</div>

<!-- 4. Number (1/3) + 5. Complement (2/3) -->
<div class="row align-items-start mb-3">
    <div class="col-sm-12">
        <div class="row mb-n3 align-items-start">
            <div class="col-4 pr-2">
                <div class="mb-3">
                    <label class="p cart-input-label" for="fields_houseno"><?= $collector_sh->translate("number".$country, "Number"); ?></label>
                    <div>
                        <input id="fields_houseno" name="fields_houseno" class="cart-input p" value="" type="text" inputmode="numeric" required="required">
                    </div>
                    <label class="tw-flex tw-items-center tw-gap-1 tw-mt-1 tw-mb-0 tw-text-xs tw-cursor-pointer" for="fields_no_number">
                        <input id="fields_no_number" type="checkbox">
                        <?= $collector_sh->translate("no_number".$country, "No number"); ?>
                    </label>
                </div>
            </div>
            <div class="col-8 pl-2">
                <div class="mb-3">
                    <label class="p cart-input-label" for="fields_apt_unit"><?= $collector_sh->translate("apt_unit".$country, "Apt, unit"); ?> <span class="tw-font-normal tw-text-[#4D4D4D]"><?= $collector_sh->translate("optional".$country, "(optional)"); ?></span></label>
                    <div>
                        <input id="fields_apt_unit" name="fields_apt_unit" class="cart-input p" value="" type="text" optional autocomplete="address-line2" placeholder="<?= $collector_sh->translate("p_apt_unit".$country, "Apt, block, back unit..."); ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 6. Bairro -->
<div class="mb-3">
    <label class="p cart-input-label" for="fields_bairro"><?= $collector_sh->translate("fields_bairro".$country, "Neighborhood"); ?></label>
    <div>
        <input id="fields_bairro" name="fields_bairro" class="cart-input p" value="" type="text" required="required">
    </div>
</div>

<!-- 7. City (2/3) + 8. State (1/3) -->
<div class="row align-items-start mb-3">
    <div class="col-sm-12">
        <div class="row mb-n3 align-items-start">
            <div class="col-8 pr-2">
                <div class="mb-3">
                    <label class="p cart-input-label" for="fields_city" label="Town/City"><?= $collector_sh->translate("city".$country, "City") ?></label>
                    <div>
                        <input id="fields_city" name="city" class="cart-input p" value="" type="text" autocomplete="address-level2" required="required">
                    </div>
                </div>
            </div>
            <div class="col-4 pl-2">
                <div class="mb-3">
                    <label class="p cart-input-label" for="fields_state" label="State/Province"><?= $collector_sh->translate("state_br".$country, "State"); ?></label>
                    <div>
                        <select id="fields_state" name="state" class="cart-input p" required="required">
                            <option value="">UF</option>
                            <?php foreach ($br_ufs as $uf) { ?><option value="<?= $uf ?>"><?= $uf ?></option><?php } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 9. CPF -->
<div class="mb-3">
    <label class="p cart-input-label" for="fields_cpf">CPF</label>
    <div>
        <input id="fields_cpf" name="fields_cpf" class="cart-input p" value="" type="tel" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" required="required">
    </div>
    <p class="tw-text-xs tw-text-[#4D4D4D] tw-mt-1 tw-mb-0"><?= $collector_sh->translate("cpf_hint".$country, "Required by Brazil's Federal Revenue to clear your order through customs."); ?></p>
</div>

</div><!-- /#br-shipping-form -->

<script>
(function () {
    var MSG = <?= json_encode($br_msgs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
    var UFS = <?= json_encode($br_ufs); ?>;
    var TIMEOUT = 3500;

    function $id(id) { return document.getElementById(id); }
    var form = $id('br-shipping-form');
    var f = {
        cep: $id('fields_zip'),
        street: $id('fields_address1'),
        number: $id('fields_houseno'),
        noNumber: $id('fields_no_number'),
        complement: $id('fields_apt_unit'),
        bairro: $id('fields_bairro'),
        city: $id('fields_city'),
        state: $id('fields_state'),
        cpf: $id('fields_cpf')
    };
    var cepStatus = $id('br_cep_status');
    var cepHelp = $id('br_cep_help');

    function digits(v) { return String(v || '').replace(/\D/g, ''); }
    function val(el) { return el ? String(el.value || '').trim() : ''; }

    // ---- inline errors (same markup integrated.js uses) -----------------
    function setError(el, msg) {
        clearError(el);
        el.classList.remove('valid');
        el.classList.add('error');
        var span = document.createElement('span');
        span.className = 'error-message';
        span.style.cssText = 'color:red;font-size:14px;';
        span.textContent = msg;
        el.insertAdjacentElement('afterend', span);
    }
    function clearError(el) {
        el.classList.remove('error');
        var next = el.nextElementSibling;
        if (next && next.classList.contains('error-message')) next.remove();
    }

    // ---- Estado: keep UF codes as option text --------------------------
    // integrated.js repopulates #fields_state with full state names after this
    // partial loads, so put the UF-code list back whenever that happens.
    function ufOptionsOk() {
        var o = f.state.options;
        if (o.length !== UFS.length + 1 || o[0].value !== '' || o[0].text !== 'UF') return false;
        for (var i = 0; i < UFS.length; i++) {
            if (o[i + 1].value !== UFS[i] || o[i + 1].text !== UFS[i]) return false;
        }
        return true;
    }
    function applyUfOptions() {
        if (ufOptionsOk()) return;
        var current = f.state.value;
        f.state.innerHTML = '';
        f.state.appendChild(new Option('UF', ''));
        UFS.forEach(function (uf) { f.state.appendChild(new Option(uf, uf)); });
        f.state.value = UFS.indexOf(current) !== -1 ? current : '';
    }
    if (window.MutationObserver) {
        new MutationObserver(applyUfOptions).observe(f.state, { childList: true });
    }

    // ---- Validation -------------------------------------------------------
    function isValidCpf(value) {
        var d = digits(value);
        if (d.length !== 11 || /^(\d)\1{10}$/.test(d)) return false;
        for (var t = 9; t < 11; t++) {
            var sum = 0;
            for (var i = 0; i < t; i++) sum += parseInt(d.charAt(i), 10) * (t + 1 - i);
            var check = (sum * 10) % 11 % 10;
            if (check !== parseInt(d.charAt(t), 10)) return false;
        }
        return true;
    }

    var RULES = {
        cep:    function () { var d = digits(f.cep.value); return d.length === 8 && d !== '00000000'; },
        street: function () { return val(f.street) !== ''; },
        number: function () { return f.noNumber.checked || val(f.number) !== ''; },
        bairro: function () { return val(f.bairro) !== ''; },
        city:   function () { return val(f.city) !== ''; },
        state:  function () { return UFS.indexOf(f.state.value) !== -1; },
        cpf:    function () { return isValidCpf(f.cpf.value); }
    };

    function validateField(key) {
        var ok = RULES[key]();
        if (ok) clearError(f[key]); else setError(f[key], MSG[key]);
        return ok;
    }

    // True when every BR field passes; shows inline errors on the ones that don't
    function validateBrShipping() {
        var ok = true;
        Object.keys(RULES).forEach(function (key) { if (!validateField(key)) ok = false; });
        return ok;
    }

    // Values as they will be sent; address1 already merged:
    // "{street}, {number}, {bairro}" or "{street}, {number} / {complement}, {bairro}"
    function getBrShippingPayload() {
        var number = f.noNumber.checked ? 'S/N' : val(f.number);
        var complement = val(f.complement);
        var cep = digits(f.cep.value);
        return {
            country: 'BR',
            postal_code: cep.length === 8 ? cep.slice(0, 5) + '-' + cep.slice(5) : val(f.cep),
            street: val(f.street),
            number: number,
            complement: complement,
            brazil_bairro: val(f.bairro),
            city: val(f.city),
            state: f.state.value,
            brazil_cpf: digits(f.cpf.value),
            address1: val(f.street) + ', ' + number + (complement ? ' / ' + complement : '') + ', ' + val(f.bairro),
            address2: ''
        };
    }

    window.validateBrShipping = validateBrShipping;
    window.getBrShippingPayload = getBrShippingPayload;

    // integrated.js validateForm() calls this on submit. Only enforce while the
    // BR form is the one on the page (the country can be switched afterwards).
    window.validateShippingForm = function () {
        if (!document.body.contains(form)) return true;
        return validateBrShipping();
    };

    // Errors on blur too. Deferred so it runs after integrated.js's own blur
    // check on the same field and this message is the one left showing.
    Object.keys(RULES).forEach(function (key) {
        f[key].addEventListener('blur', function () {
            setTimeout(function () { if (document.body.contains(form)) validateField(key); }, 0);
        });
    });

    // ---- Sem número --------------------------------------------------------
    f.noNumber.addEventListener('change', function () {
        if (f.noNumber.checked) {
            f.number.value = 'S/N';
            f.number.readOnly = true;
            f.number.classList.add('br-locked');
            clearError(f.number);
        } else {
            f.number.value = '';
            f.number.readOnly = false;
            f.number.classList.remove('br-locked');
            f.number.focus();
        }
    });

    // ---- CEP lookup -----------------------------------------------------------
    var lookupSeq = 0;
    var lookedUpCep = '';
    var filled = { street: '', bairro: '' };   // values the last lookup put in

    function setStatus(text, warn) {
        cepStatus.textContent = text || '';
        cepStatus.style.display = text ? 'block' : 'none';
        cepStatus.classList.toggle('br-warn', !!warn);
    }

    function lockCityState(lock) {
        f.city.readOnly = lock;
        f.city.classList.toggle('br-locked', lock);
        f.state.classList.toggle('br-locked', lock);
        if (lock) f.state.setAttribute('tabindex', '-1'); else f.state.removeAttribute('tabindex');
    }
    // A locked <select> can't be readonly natively; block keyboard changes too
    f.state.addEventListener('keydown', function (e) {
        if (f.state.classList.contains('br-locked') && e.key !== 'Tab') e.preventDefault();
    });

    function getJson(url) {
        var ctrl = window.AbortController ? new AbortController() : null;
        var timer = setTimeout(function () { if (ctrl) ctrl.abort(); }, TIMEOUT);
        return fetch(url, ctrl ? { signal: ctrl.signal } : {}).then(function (res) {
            clearTimeout(timer);
            if (res.status !== 200) return { status: res.status, data: null };
            return res.json().then(function (data) { return { status: 200, data: data }; });
        }, function (err) {
            clearTimeout(timer);
            throw err;
        });
    }

    // Resolves to {found, street, bairro, city, state} | {notFound} | {down}
    function lookupCep(cep) {
        function viaCep(brasilSaidNotFound) {
            return getJson('https://viacep.com.br/ws/' + cep + '/json/').then(function (r) {
                var d = r.data;
                if (r.status !== 200 || !d) return brasilSaidNotFound ? { notFound: true } : { down: true };
                if (d.erro === true || d.erro === 'true') return { notFound: true };
                return { found: true, street: d.logradouro, bairro: d.bairro, city: d.localidade, state: d.uf };
            }, function () {
                return brasilSaidNotFound ? { notFound: true } : { down: true };
            });
        }

        return getJson('https://brasilapi.com.br/api/cep/v2/' + cep).then(function (r) {
            if (r.status === 200 && r.data) {
                var d = r.data;
                return { found: true, street: d.street, bairro: d.neighborhood, city: d.city, state: d.state };
            }
            return viaCep(r.status === 404);
        }, function () {
            return viaCep(false);
        });
    }

    // Endereço / Bairro: only overwrite when empty or still the previous lookup's value
    function fillIfUntouched(key, value) {
        var current = val(f[key]);
        if (current === '' || current === filled[key]) {
            f[key].value = value || '';
            filled[key] = value || '';
            if (value) clearError(f[key]);
        }
    }

    function applyResult(result) {
        if (result.found) {
            var street = String(result.street || '').trim();
            f.city.value = String(result.city || '').trim();
            if (UFS.indexOf(result.state) !== -1) f.state.value = result.state;
            clearError(f.city);
            clearError(f.state);
            f.state.dispatchEvent(new Event('change', { bubbles: true }));
            lockCityState(true);
            setStatus('');
            cepHelp.style.display = 'none';

            if (street) {
                fillIfUntouched('street', street);
                fillIfUntouched('bairro', String(result.bairro || '').trim());
                if (!f.noNumber.checked) f.number.focus();
            } else {
                // Single-CEP town: only city/state are known
                f.street.focus();
            }
        } else if (result.notFound) {
            lockCityState(false);
            setStatus(MSG.notFound, true);
        } else {
            // Both services down: no message, everything stays editable
            lockCityState(false);
            setStatus('');
        }
    }

    function onCepChange() {
        var cep = digits(f.cep.value);
        if (cep === lookedUpCep) return;

        // CEP edited: drop any pending lookup, unlock, reset message + link
        lookupSeq += 1;
        lookedUpCep = '';
        lockCityState(false);
        setStatus('');
        cepHelp.style.display = '';

        if (cep.length !== 8) return;

        lookedUpCep = cep;
        var seq = lookupSeq;
        setStatus(MSG.looking, false);
        lookupCep(cep).then(function (result) {
            if (seq !== lookupSeq) return;   // a newer CEP has been typed since
            applyResult(result);
        }, function () {
            if (seq === lookupSeq) applyResult({ down: true });
        });
    }

    // keyup too: the jQuery mask rewrites the value on keyup
    f.cep.addEventListener('input', onCepChange);
    f.cep.addEventListener('keyup', onCepChange);
    f.cep.addEventListener('paste', function () { setTimeout(onCepChange, 0); });
})();
</script>
<?php $collector_sh->saveTranslation(); ?>
