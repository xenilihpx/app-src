//to use just put a class .search on the input (or select), .toSearch="city" database column that you want to search and trigger="click focus keyup" to trigger suggestion
//<input type="text" class="search" toSearch="city" trigger="click focus"/>
//A select instead of an input loads the suggestions as its own <option>s and picks one via native "change" - no floating dropdown needed:
//<select class="search" toSearch="city" trigger="focus"><option value="">Select a city</option></select>
(function () {
    var KEY_UP = 38, KEY_DOWN = 40, KEY_ENTER = 13, KEY_ESCAPE = 27;
    var LOADING_DELAY = 300; // only show "Searching..." if it's actually taking a while
    var REQUEST_TIMEOUT = 8000;

    // Calls api/proxy.php, not api/suggest.php directly - the real API key
    // lives server-side in the proxy and is never sent to the browser.

    // Each bound .search input gets its own suggestions box and its own
    // independent state (debounce timer, active index, in-flight request,
    // teardown, ...), so typing in fields_city only ever opens/affects the
    // suggestions box next to fields_city - never the one next to fields_zip
    // or any other field.
    var teardownByInput = new WeakMap();

    function initSearchInput(searchInput) {
        // Forms are swapped in/out via AJAX (see integrated.js), so this can
        // fire repeatedly for freshly-inserted inputs - guard against binding
        // the same node twice.
        if (searchInput.dataset.suggestBound === '1') return;

        // A <select class="search"> has nowhere to render a floating dropdown
        // and no free-typed value to validate - suggestions become its own
        // <option>s instead, and picking one is just a native "change".
        var isSelect = searchInput.tagName === 'SELECT';

        // The suggestions box lives next to its own input in the markup, e.g.
        // shipping-address-*.php:
        //   <div class="self-autocomplete">
        //     <input class="... search" id="fields_city">
        //     <div class="self-suggestions"></div>
        //   </div>
        // Look it up relative to this input (instead of a single shared id)
        // so every search field gets its own, independent dropdown. A select
        // field doesn't need one at all.
        var wrap = (searchInput.closest && searchInput.closest('.self-autocomplete')) || searchInput.parentElement;
        var suggestionsBox = wrap ? wrap.querySelector('.self-suggestions') : null;
        if (!isSelect && !suggestionsBox) return;

        if (suggestionsBox) {
            // Positioned inline (not in the stylesheet) since "top" depends on
            // this specific input's rendered height - the same .self-suggestions
            // markup backs differently-sized search inputs across the address
            // form, so a single fixed CSS value can't fit all of them.
            suggestionsBox.style.position = 'absolute';
            suggestionsBox.style.left = '0';
            suggestionsBox.style.zIndex = '2';
        }

        searchInput.dataset.suggestBound = '1';
        searchInput.style.cursor = 'pointer';

        // A select can't show a placeholder the way an <input> does -
        // onPlaceholder/onEnabled (the same custom attributes an <input>
        // version of this field would carry, e.g. shipping-address-id.php's
        // fields_district) are shown instead as the text of its own
        // empty-value option, swapped whenever the field's disabled state
        // changes (a page's own cascade script toggling it as the parent
        // field gets picked or cleared).
        var placeholderOption = isSelect ? searchInput.querySelector('option[value=""]') : null;
        var onEnabledText = searchInput.getAttribute('onEnabled');
        var onPlaceholderText = searchInput.getAttribute('onPlaceholder');
        var placeholderObserver = null;

        function syncPlaceholderOptionText() {
            var text = searchInput.disabled ? onPlaceholderText : onEnabledText;
            if (text != null) placeholderOption.textContent = text;
        }

        if (placeholderOption && (onEnabledText != null || onPlaceholderText != null)) {
            syncPlaceholderOptionText();
            placeholderObserver = new MutationObserver(syncPlaceholderOptionText);
            placeholderObserver.observe(searchInput, { attributes: true, attributeFilter: ['disabled'] });
        }

        // trigger="keyup click focus" (space separated, any combination) picks
        // which user actions open the suggestions for THIS input:
        //  - "keyup": search as the user types (debounced) - the default when
        //    the attribute is omitted, matching the original behavior. Not
        //    meaningful for a select (nothing to type), so a select defaults
        //    to "focus" instead.
        //  - "click" / "focus": search immediately using whatever is already
        //    typed as soon as the field is clicked into / receives focus.
        var defaultTrigger = isSelect ? 'focus' : 'keyup';
        var triggers = (searchInput.getAttribute('trigger') || defaultTrigger).toLowerCase().split(/\s+/).filter(Boolean);
        if (!triggers.length) triggers = [defaultTrigger];

        // toSearch="city" tells the API which column this field's query should
        // be matched against (e.g. city vs. zip), so the same suggest endpoint
        // can back several differently-scoped search inputs on one form.
        var toSearchColumn = searchInput.getAttribute('toSearch') || '';

        var zipcodeField = document.getElementById('fields_zip');
        var city_municipalityField = document.getElementById('fields_city');
        var stateField = document.getElementById('fields_state');
        var address2Field = document.getElementById('fields_address2');
        var neighborhoodField = document.getElementById('fields_neighborhood');
        var ward_villageField = document.getElementById('fields_village');
        var district_countyField = document.getElementById('fields_district');

        var debounceTimer = null;
        var loadingTimer = null;
        var activeIndex = -1;
        var requestSeq = 0;

        // trigger="keyup" lets the shopper free-type, but only a value picked
        // from the self-suggestions list is trustworthy - free-typed text may
        // not match anything real. suggestionSelected tracks whether the
        // current value was actually set by clicking a suggestion (see
        // selectSuggestion below); userEdited tracks whether the shopper has
        // typed since then, which invalidates a prior pick. onBlur uses both
        // to wipe an unconfirmed value instead of letting it reach the form.
        var suggestionSelected = false;
        var userEdited = false;

        // What this field's value was the last time a suggestion was
        // confirmed - lets applyToEnableEl (below) tell a genuine change
        // (e.g. picking a different city) apart from re-confirming the same
        // value, since only a real change should reset whatever the
        // toEnableEl cascading element(s) already hold (that selection
        // belonged to the old value and no longer applies).
        var previousToEnableValue = searchInput.value || '';

        // On iOS/Android, tapping a suggestion normally blurs the input first,
        // dismissing the on-screen keyboard and reflowing the page before "click"
        // fires - which can make the tap silently miss. Prevent the default
        // mousedown behavior so the input keeps focus and the tap lands reliably.
        function onSuggestionsMousedown(e) {
            e.preventDefault();
        }

        function onInput() {
            var query = searchInput.value.replace(/^\s+|\s+$/g, '');
            clearTimeout(debounceTimer);

            // Any manual typing invalidates a previous suggestion pick - the
            // value now needs to be re-confirmed by picking again.
            userEdited = true;
            suggestionSelected = false;

            if (query.length < 1) {
                hideSuggestions();
                return;
            }

            debounceTimer = setTimeout(function () {
                fetchSuggestions(query);
            }, 250);
        }

        // "click"/"focus" triggers fire from a single discrete user action
        // rather than a stream of keystrokes, so search right away instead of
        // debouncing - and unlike typing, they ignore whatever text is
        // already sitting in the field (e.g. a value left over from a
        // previous pick) and search on the cascade params from the other
        // fields alone, so the field lists everything valid for the current
        // cascade instead of being narrowed by its own stale value.
        //
        // trigger="click focus" binds both events, but clicking an unfocused
        // input fires a native "focus" then "click" as one gesture - without
        // this guard both handlers would search. The focus (if bound) always
        // searches and marks the click that immediately follows it as already
        // handled; a click with no preceding focus (field was already
        // focused) still searches on its own, e.g. to reopen a closed list.
        var suppressNextClick = false;

        function onImmediateTrigger(e) {
            if (e && e.type === 'click' && suppressNextClick) {
                suppressNextClick = false;
                return;
            }
            if (e && e.type === 'focus') suppressNextClick = true;

            clearTimeout(debounceTimer);

            fetchSuggestions('');
        }

        function onKeydown(e) {
            var items = suggestionsBox.getElementsByTagName('div');
            var code = e.keyCode || e.which;

            if (code === KEY_DOWN) {
                if (!items.length) return;
                e.preventDefault();
                activeIndex = (activeIndex + 1) % items.length;
                highlight(items);
            } else if (code === KEY_UP) {
                if (!items.length) return;
                e.preventDefault();
                activeIndex = (activeIndex - 1 + items.length) % items.length;
                highlight(items);
            } else if (code === KEY_ENTER) {
                if (!items.length) return;
                e.preventDefault();
                if (activeIndex >= 0 && items[activeIndex]) items[activeIndex].click();
            } else if (code === KEY_ESCAPE) {
                hideSuggestions();
            } else {
                // Unlike the branches above, this one must run even with an
                // empty/not-yet-loaded suggestions list - see jumpToLetter's
                // own comment for why.
                jumpToLetter(e, items);
            }
        }

        // Typing a single letter/digit jumps to (and scrolls to reveal) the
        // next rendered suggestion whose label starts with it - the same
        // type-ahead a native <select> already gives for free, but these
        // rows are plain <div>s (needed for city/district/village, which
        // render from the API rather than <option>s), so nothing does this
        // automatically. Starts searching just after whichever row is
        // currently active so repeated presses of the same key cycle
        // through every row sharing that first letter, and wraps around the
        // full list.
        function jumpToLetter(e, items) {
            // A trigger="keyup" field only actually free-types search-as-
            // you-type when it can be typed into at all - readonly blocks
            // the "input" event onInput() needs, so its keyup wiring never
            // fires no matter what the shopper types. Skip this local
            // jump/highlight ONLY when real free-typing is genuinely live
            // (keyup wired AND not readonly) - that's the one case where the
            // keystroke must reach the input untouched. Everything else
            // (city here, since shipping-address-id.php keeps it readonly
            // even with trigger="keyup" - and any "pick only" field like
            // district/village once enabled, which has no keyup trigger to
            // begin with) has nowhere valid to put free text, so every
            // keystroke should navigate the suggestions already on screen
            // instead, never get written into the field's real value.
            var canFreeType = triggers.indexOf('keyup') !== -1 && !searchInput.readOnly;
            if (canFreeType) return;
            if (e.ctrlKey || e.metaKey || e.altKey) return;
            var letter = e.key;
            if (!letter || letter.length !== 1) return;

            // Block the character before checking whether the list has
            // actually loaded yet - a "pick only" field must never accept
            // typed text, even for a keystroke that lands right as its tab
            // gains focus, before the suggestions fetch it kicked off has
            // had time to come back and render any rows.
            e.preventDefault();
            if (!items.length) return;

            letter = letter.toLowerCase();
            var i, idx, text;
            for (i = 1; i <= items.length; i++) {
                idx = (activeIndex + i) % items.length;
                text = items[idx].textContent ? items[idx].textContent.replace(/^\s+/, '') : '';
                if (text.charAt(0).toLowerCase() === letter) {
                    activeIndex = idx;
                    highlight(items);
                    if (items[idx].scrollIntoView) items[idx].scrollIntoView({ block: 'nearest' });
                    return;
                }
            }
        }

        function onDocumentClick(e) {
            if (e.target !== searchInput) hideSuggestions();
        }

        // The mousedown preventDefault above keeps focus on the input when a
        // suggestion is tapped/clicked, so this only fires for a genuine blur
        // (tabbing away, clicking an unrelated element, etc).
        function onBlur() {
           hideSuggestions();

           // trigger="keyup" fields must be confirmed by picking a suggestion.
           // If the shopper typed something and left without picking one, the
           // value was never validated against the self-suggestion data, so
           // clear it rather than let an unconfirmed/incorrect value stick.
           if (triggers.indexOf('keyup') !== -1 && userEdited && !suggestionSelected) {
                searchInput.value = '';
                clearLinkedElement(searchInput);
                if (searchInput.hasAttribute('siruta_id')) {
                    searchInput.setAttribute('siruta_id','');
                }
                applyToEnableEl();
           }
           userEdited = false;
        }

        // Only bind the trigger events actually requested for this input.
        var triggerEvents = [];
        if (triggers.indexOf('keyup') !== -1) triggerEvents.push(['input', onInput]);
        if (triggers.indexOf('click') !== -1) triggerEvents.push(['click', onImmediateTrigger]);
        if (triggers.indexOf('focus') !== -1) triggerEvents.push(['focus', onImmediateTrigger]);

        var t;
        for (t = 0; t < triggerEvents.length; t++) {
            searchInput.addEventListener(triggerEvents[t][0], triggerEvents[t][1]);
        }
        searchInput.addEventListener('blur', onBlur);

        // A select has no floating dropdown to navigate with arrow keys, click
        // outside of, or preventDefault a mousedown on - picking a suggestion is
        // just choosing one of its <option>s, which fires a native "change".
        if (isSelect) {
            searchInput.addEventListener('change', onSelectChange);
        } else {
            searchInput.addEventListener('keydown', onKeydown);
            document.addEventListener('click', onDocumentClick);
            suggestionsBox.addEventListener('mousedown', onSuggestionsMousedown);
        }

        teardownByInput.set(searchInput, function () {
            var t;
            for (t = 0; t < triggerEvents.length; t++) {
                searchInput.removeEventListener(triggerEvents[t][0], triggerEvents[t][1]);
            }
            searchInput.removeEventListener('blur', onBlur);

            if (placeholderObserver) placeholderObserver.disconnect();

            if (isSelect) {
                searchInput.removeEventListener('change', onSelectChange);
            } else {
                searchInput.removeEventListener('keydown', onKeydown);
                document.removeEventListener('click', onDocumentClick);
                suggestionsBox.removeEventListener('mousedown', onSuggestionsMousedown);
            }

            delete searchInput.dataset.suggestBound;
        });

        function highlight(items) {
            var i;
            for (i = 0; i < items.length; i++) {
                if (i === activeIndex) {
                    addClass(items[i], 'active');
                } else {
                    removeClass(items[i], 'active');
                }
            }
        }

        function onSelectChange() {
            var option = searchInput.options[searchInput.selectedIndex];
            if (!option || !option.dataset.item) return;
            selectSuggestion(JSON.parse(option.dataset.item));
        }

        function showSelectStatus(text) {
            // Only ever replace this field's own status line - real
            // suggestion options (including whatever's currently selected)
            // are left untouched, so a background refetch (the default
            // "focus" trigger fires one on every reopen) can't blank out a
            // value the shopper already picked.
            var previousStatus = searchInput.querySelector('option[data-status="1"]');
            if (previousStatus) previousStatus.parentNode.removeChild(previousStatus);

            var option = document.createElement('option');
            option.textContent = text;
            option.disabled = true;
            option.dataset.suggestion = '1';
            option.dataset.status = '1';
            // Right below the placeholder rather than appended at the end, so
            // it reads as progress feedback rather than just another choice.
            searchInput.insertBefore(option, searchInput.options[1] || null);
        }

        function renderSelectOptions(suggestions) {
            // Removing the old suggestion options (including any leftover
            // status line) and appending the fresh ones is a single
            // synchronous rebuild (no separate "clear" paint the shopper can
            // see), so the previously selected option can just be matched
            // back up by value afterward instead of being left in the DOM -
            // which would otherwise linger as a visible duplicate the next
            // time this same list is fetched.
            var selected = searchInput.options[searchInput.selectedIndex];
            var previousValue = (selected && selected.dataset.suggestion === '1') ? selected.value : null;

            clearSelectSuggestions(searchInput);

            var i, item, option, matchIndex = -1;
            for (i = 0; i < suggestions.length; i++) {
                item = suggestions[i];
                option = document.createElement('option');
                option.value = capitalizeFirstLetter(item.label);
                option.textContent = capitalizeFirstLetter(item.label);
                // The full suggestion is stashed on the option itself (instead
                // of a separate lookup map) so onSelectChange can recover it
                // straight from whichever <option> the browser reports as
                // selected, with no extra bookkeeping to keep in sync.
                option.dataset.item = JSON.stringify(item);
                option.dataset.suggestion = '1';
                searchInput.appendChild(option);
                if (previousValue !== null && matchIndex === -1 && item.label === previousValue) {
                    matchIndex = searchInput.options.length - 1;
                }
            }

            if (matchIndex !== -1) searchInput.selectedIndex = matchIndex;
        }

        function fetchSuggestions(query) {
            requestSeq += 1;
            var seq = requestSeq;
            
            // Only show a loading state once the request has been pending a bit -
            // avoids a flash of "Searching..." on fast/local connections.
            clearTimeout(loadingTimer);
            loadingTimer = setTimeout(function () {
                if (seq === requestSeq){
                    showStatus(window.i18nData['searching'] || 'Searching...');
                } 
            }, LOADING_DELAY);

            var apiPath = ((window.location.pathname.toLowerCase().indexOf("/mobile")!=-1)? "../" : "");

            var url = apiPath + 'integrated/?search_data=true&q=' + encodeURIComponent(query) + "&country=" + encodeURIComponent(document.getElementById('fields_country_select').value.toLowerCase());
            if (toSearchColumn) url += '&toSearch=' + encodeURIComponent(toSearchColumn);
            url += buildCascadeParams(searchInput);

            var xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.timeout = REQUEST_TIMEOUT;

            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                // Ignore this response if a newer request has since been sent -
                // guards against out-of-order replies on flaky mobile connections.
                if (seq !== requestSeq) return;

                clearTimeout(loadingTimer);

                if (xhr.status !== 200) {
                    showStatus(window.i18nData['unable_to_load_suggestions'] || 'Could not load suggestions');
                    return;
                }

                var data;
                try {
                    data = JSON.parse(xhr.responseText);
                } catch (err) {
                    showStatus(window.i18nData['unable_to_load_suggestions'] || 'Could not load suggestions');
                    return;
                }

                if (!data.success || !data.suggestions || !data.suggestions.length) {
                    showStatus(window.i18nData['unable_to_load_suggestions'] || 'Could not load suggestions');
                    // hideSuggestions();
                    return;
                }

                renderSuggestions(data.suggestions);
            };

            xhr.ontimeout = xhr.onerror = function () {
                if (seq !== requestSeq) return;
                clearTimeout(loadingTimer);
                showStatus(window.i18nData['unable_to_load_suggestions'] || 'Could not load suggestions');
            };

            xhr.send();
        }

        // Recomputed on every show (not just once at bind time) so a later
        // layout change - responsive breakpoint, font load, etc. - that
        // resizes the input is still reflected the next time suggestions open.
        function positionSuggestions() {
            suggestionsBox.style.top = searchInput.offsetHeight + 'px';
        }

        function showStatus(text) {
            if (isSelect) {
                showSelectStatus(text);
                return;
            }
            suggestionsBox.innerHTML = '';
            activeIndex = -1;

            var row = document.createElement('div');
            row.className = 'status';
            row.textContent = text;
            suggestionsBox.appendChild(row);

            positionSuggestions();
            suggestionsBox.style.display = 'block';
        }

        function renderSuggestions(suggestions) {
            if (isSelect) {
                renderSelectOptions(suggestions);
                return;
            }

            suggestionsBox.innerHTML = '';
            activeIndex = -1;

            var i;
            for (i = 0; i < suggestions.length; i++) {
                (function (item) {
                    var row = document.createElement('div');
                    row.textContent = item.label;
                    row.addEventListener('click', function () {
                        selectSuggestion(item);
                    });
                    suggestionsBox.appendChild(row);
                })(suggestions[i]);
            }

            positionSuggestions();
            suggestionsBox.style.display = 'block';
        }

        function capitalizeFirstLetter(str) {
            return str
                    .split(" ")
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                    .join(" ");
        }
        
        // Wraps setFieldValue with one extra rule: when the field being filled
        // is this very select (the one whose native "change" just fired), its
        // exact <option> is already correctly selected - re-deriving it via
        // setFieldValue's fuzzy text match could land on a different option
        // when two suggestions share similar city/district text. An <input>
        // never has this native selection to fall back on, so it still needs
        // the explicit assignment even when it's the field that triggered the
        // pick (see the comment below).
        function applySuggestionValue(field, value) {
            if (field === searchInput && isSelect) return;
            setFieldValue(field, value);
        }

        function selectSuggestion(item) {
            //searchInput.value = item.label;

            // Mark this field's value as confirmed so onBlur doesn't wipe it
            // out again (it only clears values that were typed but never
            // matched to a suggestion).
            suggestionSelected = true;
            userEdited = false;

            var country_selected=document.querySelector('#fields_country_select');
            if(item.village && item.village.trim() !== "" && item.elementToFill.indexOf(ward_villageField.getAttribute('toSearch'))!=-1 
                || item.elementToFill.indexOf('village')!=-1) {
                    ward_villageField.classList.remove("error");
                    applySuggestionValue(ward_villageField, (country_selected.value=="ID")? capitalizeFirstLetter(item.village) : item.village);

                    var village_=document.querySelector('#fields_village + span.error-message');
                    if(village_ && !ward_villageField.classList.contains('error')){
                        village_.remove();
                    }
            }

            if(item.district && item.district.trim() !== "" && item.elementToFill.indexOf(district_countyField.getAttribute('toSearch'))!=-1 
                || item.elementToFill.indexOf('district')!=-1) {
                    district_countyField.classList.remove("error");
                    // FIX FOR fields_district auto suggestion
                    var districtValue = (country_selected.value=="TH")? (item[district_countyField.getAttribute('toSearch')] || item.district) : item.district;
                    applySuggestionValue(district_countyField, (country_selected.value=="ID")? capitalizeFirstLetter(districtValue) : districtValue);

                    if(country_selected.value=="TH"){
                        district_countyField.setAttribute("value_id",item.district_th);
                    }

                    var district_=document.querySelector('#fields_district + span.error-message');
                    if(district_ && !district_countyField.classList.contains('error')){
                        district_.remove();
                    }
            }
         

            if(item.zipcode && item.zipcode.trim() !== "" && item.elementToFill.indexOf(zipcodeField.getAttribute('toSearch'))!=-1 
                || item.elementToFill.indexOf('zipcode')!=-1) {
                    zipcodeField.classList.remove("error");
                    applySuggestionValue(zipcodeField, item.zipcode);

                    if(item.zipcode && item.zipcode!=null && item.zipcode!=""){
                        zipcodeField.setAttribute("disabled", "true");
                        if(document.querySelector('#fields_zip_hint')){
                            document.querySelector('#fields_zip_hint').style.display="none";
                        }
                        
                    }else{
                        zipcodeField.removeAttribute("disabled");
                        zipcodeField.removeAttribute("readonly");
                        if(document.querySelector('#fields_zip_hint')){
                            document.querySelector('#fields_zip_hint').style.display="block";
                        }
                       
                    }

                    var zip_=document.querySelector('#fields_zip + span.error-message');
                    if(zip_ && !zipcodeField.classList.contains('error')){
                        zip_.remove();
                    }


            }

            if(item.city && item.city.trim() !== "" && item.elementToFill.indexOf(city_municipalityField.getAttribute('toSearch'))!=-1 
                ||  item.elementToFill.indexOf('city')!=-1) {
                    city_municipalityField.classList.remove("error");
                    // TH: same as district above - city_th/city_en to match this field's toSearch column
                    var cityValue = (country_selected.value=="TH")? (item[city_municipalityField.getAttribute('toSearch')] || item.city) : item.city;
                    applySuggestionValue(city_municipalityField, (country_selected.value=="ID")? capitalizeFirstLetter(cityValue) : cityValue);

                    if(country_selected.value=="RO"){
                        city_municipalityField.setAttribute("siruta_id",item.siruta);
                    }

                    if(country_selected.value=="TH"){
                        city_municipalityField.setAttribute("value_id",item.city_th);
                    }else if(country_selected.value=="VN"){
                        city_municipalityField.setAttribute("value_id",item.city_vn);
                    }

                    var city_=document.querySelector('#fields_city + span.error-message');
                    if(city_ && !city_municipalityField.classList.contains('error')){
                        city_.remove();
                    }
            }

            if(item.neighborhood && item.neighborhood.trim() !== "" && item.elementToFill.indexOf(neighborhoodField.getAttribute('toSearch'))!=-1 
                || item.elementToFill.indexOf('neighborhood')!=-1) {
                    neighborhoodField.classList.remove("error");
                    applySuggestionValue(neighborhoodField, item.neighborhood);

                    var neighborhood_=document.querySelector('#fields_neighborhood + span.error-message');
                    if(neighborhood_ && !neighborhoodField.classList.contains('error')){
                        neighborhood_.remove();
                    }
            }

            if(item.address2 && item.address2.trim() !== "" && item.elementToFill.indexOf(address2Field.getAttribute('toSearch'))!=-1 
                || item.elementToFill.indexOf('address2')!=-1) {
                    address2Field.classList.remove("error");
                    applySuggestionValue(address2Field, item.address2);

                    var address_2=document.querySelector('#fields_address2 + span.error-message');
                    if(address_2 && !address2Field.classList.contains('error')){
                        address_2.remove();
                    }
            }

            //for other element that are not declared
            var inputElement = document.querySelector('input[toSearch="'+item.targetElement+'"]');              
            if( zipcodeField !== inputElement && 
                city_municipalityField !== inputElement && 
                stateField !== inputElement && 
                address2Field !== inputElement && 
                neighborhoodField !== inputElement && 
                ward_villageField !== inputElement && 
                district_countyField !== inputElement && 
                item.targetElement && 
                inputElement) {

                    inputElement.classList.remove("error");
                    applySuggestionValue(inputElement, item.label);
                  
                    var input_el=document.querySelector('input[toSearch="'+item.targetElement+'"] + span.error-message');
                    if(input_el && !inputElement.classList.contains('error')){
                        input_el.remove();
                    }
            }

            if(item.state && item.state.trim() !== "" && item.elementToFill.indexOf(stateField.getAttribute('toSearch'))!=-1 
                || item.elementToFill.indexOf('state')!=-1) {
                    stateField.classList.remove("error");
                    if(stateField.tagName === 'INPUT'){
                        applySuggestionValue(stateField, item.label);

                          if(country_selected.value=="TH"){
                                stateField.setAttribute("value_id",item.province_th);
                          }else if(country_selected.value=="VN"){
                                stateField.setAttribute("value_id",item.province_vn);
                          }

                    
                        var input_el=document.querySelector('input[toSearch="'+item.targetElement+'"] + span.error-message');
                        if(input_el && !stateField.classList.contains('error')){
                            input_el.remove();
                        }
                    }else if (document.querySelectorAll("#fields_state option[value='"+item.state+"']").length > 0) {
                        stateField.value = item.state;
                        stateField.dispatchEvent(new Event("change", { bubbles: true }));
                    }else{

                        var found=false;
                        for (var option of stateField.options) {
                            if (option.text.toLowerCase().includes(item.state.toLowerCase())) {
                                found=true;
                                option.selected = true;
                                break;
                            }
                        }

                        if(!found){
                            var option = new Option(item.state, item.state, false, true);
                            stateField.add(option);
                        }
                }

                var state_=document.querySelector('#fields_state + span.error-message');
                if(state_ && !stateField.classList.contains('error')){
                    state_.remove();
                }

            }

            if(country_selected.value.toLowerCase()=="id" && item.elementToFill.indexOf('zipcode')!=-1){
                 document.getElementById('fields_address1').focus();
            }else if(country_selected.value.toLowerCase()=="mx" || country_selected.value.toLowerCase()=="ro" && item.elementToFill.indexOf('zipcode')!=-1){
                 document.getElementById('fields_address1').focus();
            }

            applyToEnableEl();
            hideSuggestions();
        }

        // toEnableEl="otherFieldId anotherFieldId" on a .search field names the
        // element(s) (by id) that only become usable once a real suggestion has
        // been picked for THIS field - e.g. fields_city's toEnableEl="fields_district"
        // shouldn't let the shopper open the district field until a city was
        // actually chosen. Space-separated so one field can enable more than one.
        function applyToEnableEl() {
            var targetIds = (searchInput.getAttribute('toEnableEl') || '').split(/\s+/).filter(Boolean);
            if (!targetIds.length) return;

            var currentValue = searchInput.value ? searchInput.value.replace(/^\s+|\s+$/g, '') : '';
            var hasValue = !!currentValue;
            var valueChanged = currentValue !== previousToEnableValue;
            previousToEnableValue = currentValue;

            // Each target names its own two placeholder texts via onEnabled/
            // onPlaceholder (e.g. fields_address2's onEnabled="Select
            // Neighborhood", onPlaceholder="Select district first" in
            // shipping-address-tr.php) - same attribute pair the <select>
            // placeholder-option handling above reads for itself. Swap in
            // whichever applies to THIS field's current state.
            var i, targetEl, placeholderText;
            for (i = 0; i < targetIds.length; i++) {
                targetEl = document.getElementById(targetIds[i]);
                if (!targetEl) continue;

                if (hasValue) {
                    targetEl.removeAttribute('disabled');
                    targetEl.removeAttribute('readonly');
                    placeholderText = targetEl.getAttribute('onEnabled');
                } else {
                    targetEl.setAttribute('disabled', '');
                    placeholderText = targetEl.getAttribute('onPlaceholder');
                }

                if (valueChanged) {
                    targetEl.value = '';
                    clearLinkedElement(targetEl);
                }
                if (placeholderText != null) targetEl.placeholder = placeholderText;
            }

            // A changed value invalidates the rest of the cascade too, not
            // just the immediate target(s) - e.g. state -> city -> district:
            // changing state must reset district as well, even though state
            // only names city directly in its own toEnableEl. Everything
            // further down was populated for the old value and none of it
            // still applies, so put each of those back behind its own
            // disabled/onPlaceholder state (they aren't this field's direct
            // target, so hasValue above doesn't drive them).
            if (valueChanged) {
                var downstreamIds = collectToEnableIds(searchInput);
                for (i = 0; i < downstreamIds.length; i++) {
                    if (targetIds.indexOf(downstreamIds[i]) !== -1) continue;
                    targetEl = document.getElementById(downstreamIds[i]);
                    if (!targetEl) continue;

                    targetEl.value = '';
                    clearLinkedElement(targetEl);
                    targetEl.setAttribute('disabled', '');
                    placeholderText = targetEl.getAttribute('onPlaceholder');
                    if (placeholderText != null) targetEl.placeholder = placeholderText;
                }
            }
        }

        function hideSuggestions() {
            // Invalidate any in-flight request/loading timer so a late response or
            // delayed "Searching..." can't reappear after the box was dismissed.
            requestSeq += 1;
            clearTimeout(loadingTimer);

            // A select has no floating box to hide - its <option>s stay put
            // until the next fetch replaces them.
            if (!suggestionsBox) return;

            suggestionsBox.style.display = 'none';
            suggestionsBox.innerHTML = '';
            activeIndex = -1;
        }
    }

    // linkElement="otherFieldId" on a field names an element whose value only
    // means something together with this field's own - e.g. fields_village's
    // linkElement="fields_zip" in shipping-address-id.php, since the zipcode
    // a village suggestion fills in is specific to that village. So whenever
    // this field's own value gets cleared, the linked element no longer
    // corresponds to anything selected either and must be cleared with it.
    function clearLinkedElement(el) {
        if (!el) return;
        var linkedId = el.getAttribute('linkElement');
        if (!linkedId) return;
        var linkedEl = document.getElementById(linkedId);
        if (linkedEl) linkedEl.value = '';
    }

    // A field named in startEl's own toEnableEl (see applyToEnableEl) only
    // gets enabled once startEl itself is picked - it's downstream of
    // startEl, not an independent narrowing field. That target can itself
    // name a further toEnableEl (e.g. state -> city -> district), so walk
    // the whole chain rather than just the first hop; a visited set guards
    // against a cycle.
    function collectToEnableIds(startEl) {
        var ids = [];
        var visited = {};
        var queue = (startEl.getAttribute('toEnableEl') || '').split(/\s+/).filter(Boolean);

        while (queue.length) {
            var id = queue.shift();
            if (visited[id]) continue;
            visited[id] = true;
            ids.push(id);

            var el = document.getElementById(id);
            if (!el) continue;
            var nextIds = (el.getAttribute('toEnableEl') || '').split(/\s+/).filter(Boolean);
            for (var j = 0; j < nextIds.length; j++) queue.push(nextIds[j]);
        }

        return ids;
    }

    // Every other address field already filled in (city, state, zipcode, ...)
    // narrows the suggestions for the field currently being searched - e.g.
    // typing a district should only match districts within the already-picked
    // city/state. Each of those fields is tagged with its own toSearch="..."
    // (the same attribute used above to pick the query column), so cascading
    // is just: collect every other toSearch field that currently has a value
    // and forward it as toSearchValue=fieldValue. Empty fields are skipped -
    // an unfilled field can't narrow anything. Fields downstream of
    // currentInput in its own toEnableEl chain are skipped too - whatever
    // they're still holding (possibly stale, from before they were last
    // disabled/cleared) shouldn't be forwarded to narrow currentInput's own
    // search.
    function buildCascadeParams(currentInput) {
        var scope = document.getElementById('shipping-container') || document;
        var fields = scope.querySelectorAll('[toSearch]');
        var params = '';
        var i, field, key, value;

        var enabledIds = collectToEnableIds(currentInput);

        for (i = 0; i < fields.length; i++) {
            field = fields[i];
            if (field === currentInput) continue;
            if (enabledIds.length && field.id && enabledIds.indexOf(field.id) !== -1) continue;

            key = field.getAttribute('toSearch');
            value = field.value ? field.value.replace(/^\s+|\s+$/g, '') : '';


            if (key && value) {
                params += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(value);
            }
        }

        return params;
    }

    function clearSelectSuggestions(selectEl) {
        var stale = selectEl.querySelectorAll('option[data-suggestion="1"]');
        var i;
        for (i = 0; i < stale.length; i++) {
            //stale[i].setAttribute("disabled",'');
            stale[i].parentNode.removeChild(stale[i]);
        }
    }

    // Changing one cascade field (e.g. state) invalidates every select.search
    // field further down the cascade (e.g. city, district) - their currently
    // loaded <option>s belong to the old value and would otherwise sit there
    // stale until that field is next focused, showing the wrong list (or
    // visibly flickering into a fresh one) for a moment. Fields ordered
    // before the one that changed are left alone - e.g. picking a district
    // shouldn't blank out the city it cascades from.
    function clearDownstreamSelectSuggestions(changedField) {
        var scope = document.getElementById('shipping-container') || document;
        var fields = scope.querySelectorAll('[toSearch]');
        var i, isDownstream = false;

        for (i = 0; i < fields.length; i++) {
            if (fields[i] === changedField) {
                isDownstream = true;
                continue;
            }
            if (isDownstream && fields[i].tagName === 'SELECT' && fields[i].classList.contains('search')) {
                clearSelectSuggestions(fields[i]);
            }
        }
    }

    document.addEventListener('change', function (e) {
        var target = e.target;
        if (target && target.hasAttribute && target.hasAttribute('toSearch')) {
            clearDownstreamSelectSuggestions(target);
        }
    });

    function addClass(el, name) {
        if ((' ' + el.className + ' ').indexOf(' ' + name + ' ') === -1) {
            el.className = (el.className + ' ' + name).replace(/^\s+/, '');
        }
    }

    function removeClass(el, name) {
        var re = new RegExp('(^|\\s)' + name + '(\\s|$)', 'g');
        el.className = el.className.replace(re, ' ').replace(/^\s+|\s+$/g, '');
    }

    // Assigning .value directly always works for a plain <input>, but a
    // <select> can only show a value that matches one of its own <option>s -
    // if the "clean" value a suggestion fills in (item.city, item.district, ...)
    // doesn't exactly match the <option> value that field was populated with
    // (item.label, which can differ in case/formatting - e.g. the select's own
    // options came from renderSelectOptions using the raw label), a blind
    // assignment silently resets the select to nothing selected. Match loosely
    // (by value, then by option text) before falling back to adding a new
    // option, same fallback the state field below already relied on.
    function setFieldValue(field, value) {
        if (!field) return;
        if (field.tagName !== 'SELECT') {
            field.value = value;
            return;
        }

        var i, option;
        for (i = 0; i < field.options.length; i++) {
            if (field.options[i].value === value) {
                field.selectedIndex = i;
                return;
            }
        }
        for (i = 0; i < field.options.length; i++) {
            option = field.options[i];
            if (option.text && option.text.toLowerCase() === String(value).toLowerCase()) {
                field.selectedIndex = i;
                return;
            }
        }
        for (i = 0; i < field.options.length; i++) {
            option = field.options[i];
            if (option.text && option.text.toLowerCase().indexOf(String(value).toLowerCase()) !== -1) {
                field.selectedIndex = i;
                return;
            }
        }

        field.add(new Option(value, value, false, true));
    }

    function forEachSearchInput(root, fn) {
        if (!root || root.nodeType !== 1) return;
        if (root.matches && root.matches('input.search, select.search')) fn(root);
        if (root.querySelectorAll) {
            var found = root.querySelectorAll('input.search, select.search');
            for (var i = 0; i < found.length; i++) fn(found[i]);
        }
    }

    function teardownSearchInput(searchInput) {
        var teardown = teardownByInput.get(searchInput);
        if (teardown) {
            teardown();
            teardownByInput.delete(searchInput);
        }
    }

    // Forms are present on first load whenever they're the default country,
    // and re-inserted via AJAX (#shipping-container.load(...) in integrated.js)
    // any time the shopper switches the country dropdown afterward - watch for
    // both cases instead of only checking once at parse time. Every input.search
    // field found (there can be more than one per form) is bound independently.
    forEachSearchInput(document.body, initSearchInput);

    new MutationObserver(function (mutations) {
        for (var i = 0; i < mutations.length; i++) {
            var added = mutations[i].addedNodes;
            for (var j = 0; j < added.length; j++) forEachSearchInput(added[j], initSearchInput);

            // Clean up listeners for inputs removed by an AJAX form swap, so a
            // stale document click handler doesn't linger for a detached node.
            var removed = mutations[i].removedNodes;
            for (var k = 0; k < removed.length; k++) forEachSearchInput(removed[k], teardownSearchInput);
        }
    }).observe(document.body, { childList: true, subtree: true });
})();
