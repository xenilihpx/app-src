(function () {
    var KlarnaPayment = function (CRMIntegrated, config) {
        if(typeof CRMIntegrated == "undefined")  {
            alert("Please pass CRMIntegrated function as paramater.");
        }
        var klarna_obj = {},
            klarnaField = {
                        'email': '#klarnaEmail',
                        'fname': '#klarnaFirstname',
                        'lname': '#klarnaLastname',
                        'address1': '#klarnaAddress1',
                        'address2': '#klarnaAddress2',
                        'city': '#klarnaCity',
                        'country': '#klarnaCountry',
                        'state': '#klarnaState',
                        'zip': '#klarnaZip',
                        'phone': '#klarnaPhone'
            };
        klarna_obj.phoneIntelli;

        var CRMIntegrated = CRMIntegrated; 
        var autocompleteKlarna;
        var address1FieldKlarna;
        var address2FieldKlarna;
        var postalFieldKlarna;
        var fieldsCityKlarna; 
        
        //configure form text
        klarna_obj.formHeader=window.i18nData["klarna_billing_information"] || "Billing Information";
        klarna_obj.emailLabel = window.i18nData["klarna_email_placeholder"] || "Preferred Email Address";
        klarna_obj.fnameLabel = window.i18nData["klarna_fname_placeholder"] || "First Name";
        klarna_obj.lnameLabel = window.i18nData["klarna_lname_placeholder"] || "Last Name";
        klarna_obj.address1Label = window.i18nData["klarna_address1_placeholder"] || "Street Address";
        klarna_obj.address2Label = window.i18nData["klarna_address2_placeholder"] || "Apt / Suite / Other";
        klarna_obj.cityLabel = window.i18nData["klarna_city_placeholder"] || "City";
        klarna_obj.stateLabel =window.i18nData["klarna_state_province"] || "-- Select State / Province --";
        klarna_obj.zipLabel =window.i18nData["klarna_postal_code"] || "Zip / PostalCode";
        klarna_obj.submitText=window.i18nData["klarna_continue_with_text"] || "Continue with";

         if (typeof config != "undefined" && typeof config=="object") {
           if(config.hasOwnProperty("formHeader")){
                klarna_obj.formHeader=config.formHeader;               
           }

           if(config.hasOwnProperty("emailLabel")){
                klarna_obj.emailLabel=config.emailLabel;               
           }

           if(config.hasOwnProperty("fnameLabel")){
                klarna_obj.fnameLabel=config.fnameLabel;               
           }    

           if(config.hasOwnProperty("lnameLabel")){
                klarna_obj.lnameLabel=config.lnameLabel;               
           } 

           if(config.hasOwnProperty("address1Label")){
                klarna_obj.address1Label=config.address1Label;               
           }  

           if(config.hasOwnProperty("address2Label")){
                klarna_obj.address2Label=config.address2Label;               
           }  

           if(config.hasOwnProperty("cityLabel")){
                klarna_obj.cityLabel=config.cityLabel;               
           }  

           if(config.hasOwnProperty("stateLabel")){
                klarna_obj.stateLabel=config.stateLabel;               
           } 

           if(config.hasOwnProperty("zipLabel")){
                klarna_obj.zipLabel=config.zipLabel;               
           } 

           if(config.hasOwnProperty("submitText")){
                klarna_obj.submitText=config.submitText;               
           }            
        } 

        klarna_obj.createKlarnaDialog=function(){            
          
            var html = '';
                html += '<div id="klarna_handler_overlay">';
                html += '   <div class="klarna_handler_body"><a href="javascript:void(0);" id="klarna_handler_overlay_close">X</a>';
                html += '   <div id="klarna-billing-section" style="margin-top: 20px;">';                
                html += '       <h4 style="margin-bottom: 20px;">'+klarna_obj.formHeader+'</h4>';
                html += '       <div style="display: flex; flex-direction: column; gap: 12px;">';
                html += '           <input type="email" id="klarnaEmail" name="klarnaEmail" placeholder="'+klarna_obj.emailLabel+'" class="form-control ship-form" required style="padding: 10px; font-size: 14px;">';
                html += '           <select id="klarnaCountry" name="klarnaCountry" placeholder="Country" required style="padding: 10px; font-size: 14px;"></select>';
                html += '           <div style="display: flex; gap: 12px;" class="klarnaContainer">';
                html += '               <input type="text" id="klarnaFirstname" name="klarnaFirstname" placeholder="'+klarna_obj.fnameLabel+'"  class="form-control ship-form" required style="flex: 1; padding: 10px; font-size: 14px;">';
                html += '               <input type="text" id="klarnaLastname" name="klarnaLastname" placeholder="'+klarna_obj.lnameLabel+'" class="form-control ship-form" required style="flex: 1; padding: 10px; font-size: 14px;">';
                html += '           </div>';
                html += '           <input type="tel" id="klarnaPhone" name="klarnaPhone" class="form-control ship-form" required style="padding: 10px; font-size: 14px;width:100%;">';
                html += '           <div style="display: flex; gap: 12px;" class="klarnaContainer" >';
                html += '               <div id="kaddress1" style="position:relative;flex:1;"><input type="text" id="klarnaAddress1" name="klarnaAddress1" placeholder="'+klarna_obj.address1Label+'" class="form-control ship-form" required style="flex: 1; padding: 10px; font-size: 14px;width:100%;"></div>';
                html += '               <div  style="position:relative;flex:1;"><input type="text" id="klarnaAddress2" name="klarnaAddress2" placeholder="'+klarna_obj.address2Label+'" class="form-control ship-form" required style="flex: 1; padding: 10px; font-size: 14px;width:100%;"></div>';
                html += '           </div>';
                html += '           <input type="text" id="klarnaCity" name="klarnaCity" placeholder="'+klarna_obj.cityLabel+'" required class="form-control ship-form" style="padding: 10px; font-size: 14px;">';
                html += '           <div style="display: flex; gap: 12px;" class="klarnaContainer">';
                html += '               <select id="klarnaState" name="klarnaState" placeholder="State" required class="form-control ship-form" style="flex: 1; padding: 10px; font-size: 14px;width:100%;"></select>';
                html += '               <input type="text" id="klarnaZip" name="klarnaZip" placeholder="'+klarna_obj.zipLabel+'" class="form-control ship-form" required style="flex: 1; padding: 10px; font-size: 14px;width:100%;">';
                html += '           </div>';
                html += '           <div class="klarna-button-stripe btn" id="klarna-submit" style="width: 100%; background: #000; border-radius: 4px; border: 1px solid #000; padding: 10px; text-align: center; display: flex; justify-content: center; align-items: center; cursor: pointer;" role="button" aria-label="Continue with Klarna">';
                html += '               <span style="display: inline-flex; align-items: center; gap: 8px;">';
                html += '               <span style="font-size: 16px; font-weight: 500; color: #fff;">'+klarna_obj.submitText+'</span>';
                html += '               <img loading="lazy" src="'+commonFilesPath()+'src/common/images/Klarna_badge_32px.png" alt="Klarna" style="height: 24px;">';
                html += '           </span></div>';
                html += '       </div>';
                html += '     </div>';
                html += '   </div>';
                html += '</div>';

             
            if($("#klarna_handler_overlay").length==0){
                $('body').append(html);
			    
            }

            setTimeout(function(){
                $("#klarnaCountry").html($("#fields_country_select").html());
                $("#klarnaCountry").on('change',function(){
                    klarna_obj.populateStates($(this).val());
                });
                
                klarna_obj.intlPhoneInit();
                klarna_obj.inputKeyPressValidation();
                $("#klarnaCountry").val($("#fields_country_select").val()).trigger("change");
            },100);  
			return false;
        }

        klarna_obj.populateStates=function(country){
            $("#klarnaState").empty();
            $("#klarnaState").append($('<option></option>').val('').html(klarna_obj.stateLabel));
             $(States).each(function (key, value) {
                $.each(value, function (key, value) {
                    if (key == country) {
                        $.each(value, function (key, value) {
                            $.each(value, function (key, value) {
                                $("#klarnaState").append($('<option></option>').val(key).html(value));
                            });
                        });
                    }
                });
            });
        }

        klarna_obj.closeModalEvents= function(){
            	$(document).off('click', '#klarna_handler_overlay_close');
                $(document).on('click', '#klarna_handler_overlay_close', function() {
                    $('#klarna_handler_overlay').remove();
                }); 
        }

        klarna_obj.beginCheckoutExec=function(){

        }

        klarna_obj.openKlarnaDialog=function(){           
            $(document).on("click","#klarna-btn",function(){
                 klarna_obj.beginCheckoutExec();
                 klarna_obj.createKlarnaDialog();
                 $('#klarna_handler_overlay').fadeIn(100);
            
                 setTimeout(() => {
                    address1FieldKlarna = document.querySelector(klarnaField.address1);
                    address2FieldKlarna = document.querySelector(klarnaField.address2);
                    postalFieldKlarna = document.querySelector(klarnaField.zip);
                    fieldsCityKlarna = document.querySelector(klarnaField.city);

                    const countries_ =document.querySelector("#stripeCountries").value.toLowerCase().split(",");
                    autocompleteKlarna = new google.maps.places.Autocomplete(document.querySelector(klarnaField.address1), {
                        componentRestrictions: { country: countries_ },
                        fields: ["address_components", "geometry"],
                        types: ["address"],
                    });

                    autocompleteKlarna.addListener("place_changed", function(){
                            const place = autocompleteKlarna.getPlace();
                            let address1 = "";
                            let postcode = "";
                            let country ="";
                            let state ="";
                        
                            for (let component of place.address_components) {
                                // @ts-ignore remove once typings fixed
                                let componentType = component.types[0];

                                switch (componentType) {
                                case "street_number": {
                                    address1 = `${component.long_name} ${address1}`;
                                    break;
                                }

                                case "route": {
                                    address1 += component.short_name;
                                    break;
                                }

                                case "postal_code": {
                                    postcode = `${component.long_name}${postcode}`;
                                    break;
                                }

                                case "postal_code_suffix": {
                                    postcode = `${postcode}-${component.long_name}`;
                                    break;
                                }
                                case "locality":
                                    fieldsCityKlarna.value = component.long_name;
                                    if(component.long_name!=""){
                                        fieldsCityKlarna.classList.remove("error");                                    
                                    }
                                    break;
                                
                                case "country":
                                    country = component.short_name;
                                    
                                    break;
                                case "administrative_area_level_1": {
                                    state=component.short_name;        
                                    break;
                                }
                                }
                            }

                            //country
                            let select_country = document.querySelector(klarnaField.country);
                            select_country.value = country;
                            select_country.dispatchEvent(new Event("change", { bubbles: true }));

                            //state
                            var select_state = document.querySelector(klarnaField.state);
                            select_state.value = state;
                            select_state.dispatchEvent(new Event("change", { bubbles: true }));

                            address1FieldKlarna.value = address1;
                            if(postcode!=""){
                                    postalFieldKlarna.classList.remove("error");                                    
                                    postalFieldKlarna.value = postcode;
                            }
                    });
                }, 200);

                setTimeout(function(){
                    const pacContainers = document.querySelectorAll('.pac-container');
                    const popup = document.querySelectorAll('#klarna_handler_overlay');

                    // Move only the container for popup input into the popup
                    if (pacContainers.length > 1 && popup) {
                        const lastPac = pacContainers[pacContainers.length - 1];                     
                       $('#klarna_handler_overlay #kaddress1').append(lastPac);                     
                    }
                },300);
            });  

            $(document).on("click","#klarna-submit", function(e){
                   klarna_obj.validateForm(function () {
                        //success
                        CRMIntegrated.progressModal();                  
                        $("#klarna-submit").prop('disabled', true);
                        
                        var cartData = CRMIntegrated.getDataStorage();                        
                        //process import order
                        var model = {
                            call_type: "order_import_klarna",
                            token_: $("#token").attr("token"),
                            billingInfo: {
                                emailAddress: $(klarnaField.email).val().trim(),
                                firstName: $(klarnaField.fname).val().trim(),
                                lastName: $(klarnaField.lname).val().trim(),
                                address1: $(klarnaField.address1).val().trim(),
                                address2: $(klarnaField.address2).val().trim(),
                                city: $(klarnaField.city).val().trim(),
                                country: $(klarnaField.country).val().trim(),
                                state: $(klarnaField.state).val().trim(),
                                postalCode: $(klarnaField.zip).val().trim(),
                                phoneNumber: klarna_obj.phoneIntelli.getNumber().trim() 
                            },
                            products: [],
                            errorRedirectsTo: location.href,  
                            redirectsTo : location.href,
                            orderId: cartData.orderId,
                            custom1: cartData.transaction_id,
                            affId: cartData.affId,
                            sourceValue1: cartData.c1,
                            sourceValue2: cartData.c2,
                            sourceValue3: cartData.c3,
                            sourceValue4: cartData.c4,
                            sourceValue5: cartData.c5,
                            marketingOptIn : ($(klarnaField.marketingoptin).is(':checked') ? 'true' : 'false'),
                            shipProfileId: (($("#ShipProfileId").length == 1 && $("#ShipProfileId").val() != "")? parseInt($("#ShipProfileId").val()) :""),
                            couponCode: cartData.couponCode,                           
                        };

                        if(getQueryStringByName("testmode")=="1"){
                             model.testmode=getQueryStringByName("testmode");
                        }                                             
                       
                        if (cartData != null && cartData.products.length > 0) {                            
                            var items=CRMIntegrated.getCartItems(cartData, model);
                            CRMIntegrated.getResponse(items.model, function (data) {
                                //success callback
                                CRMIntegrated.nextPage(data, items.addOnAmount); //redirect to next page
                            }, function (data) {
                                //failure callback   
                                CRMIntegrated.removeProgressModal();    
                                CRMIntegrated.showMsg(data);     
                               $("#klarna-submit").removeAttr('disabled');
                            });

                        } else {
                            CRMIntegrated.showMsg(window.i18nData['no_orders'] || "There are no orders.");
                            $("#klarna-submit").removeAttr('disabled');    
                        }
                    },function (msg) {                           
                        $("#klarna-submit").removeAttr('disabled');
                        CRMIntegrated.showMsg(msg);
                    });

                    e.preventDefault();
            });    
        }

        klarna_obj.intlPhoneInit = function(){
             if (typeof intlTelInput != "undefined" && CRMIntegrated.intlPhone) { 
                    const input = document.querySelector(klarnaField.phone);
                    klarna_obj.phoneIntelli = window.intlTelInput(input, CRMIntegrated.geoLookUpOptions({
                        onlyCountries: $("#stripeCountries").val().split(","),
                        autoPlaceholder: "",
                        allowDropdown: false                    
                    }));
 
                    var $this = $(klarnaField.country);
                    $this.on('change', function () {
                        klarna_obj.phoneIntelli.setCountry(this.value);
                        CRMIntegrated.applyMask(klarna_obj.phoneIntelli,klarnaField.phone);    
                    });

                    input.addEventListener("countrychange", function () {
                        CRMIntegrated.applyMask(klarna_obj.phoneIntelli,klarnaField.phone);
                    });

                    input.addEventListener("countrychange", function () {
                        CRMIntegrated.applyMask(klarna_obj.phoneIntelli,klarnaField.phone);
                    });
                }
        }

         klarna_obj.inputKeyPressValidation=function(){
            var valid = false;
            for (var key in klarnaField) {
                if (klarnaField.hasOwnProperty(key)) {
                    var element = $(klarnaField[key]);
                    if (element.is('*')) {
                        //checks if the variable is an html element
                        $(element).on('input', {
                            key: key
                        }, function (data) {
                            var value = $(this).val().trim();
                            var fieldKey = extractKey(data);
                            switch (fieldKey) {
                                case 'email':
                                    var regex = /^(?!\.)^(?!.*\.{2})[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                                    valid = regex.test(value);
                                    break;
                                case 'fname':
                                case 'lname':
                                    var regex = /^[\p{L} '-]*$/u;
                                    if (value != "") {
                                        valid = regex.test(value);

                                    } else {
                                        valid = false;
                                    }
                                    break;
                                case 'phone':
                                     valid = klarna_obj.phoneIntelli.isValidNumber();
                                    break;
                                case 'address2':
                                    valid = true;
                                    break;                                
                                    
                                default:
                                    valid = value == '' ? false : true;
                                    break;
                            }


                            if (valid) {
                                $(this).removeClass("error").addClass("valid");
                            } else {
                                $(this).removeClass("valid").addClass("error");
                            }
                        });
                    } //end check element
                }
            }
        }


        klarna_obj.validateForm=function(successCallback, failedCallback){
            var errorMsg = [];
            var valid = true;
            var countRequired = 0;
            var totalErrors = 0;

            for (var key in klarnaField) {
                if (klarnaField.hasOwnProperty(key)) {
                    var element = $(klarnaField[key]);
                    if (element.is('*')) { //if the element exists
                        if ($(element).is(':visible') == true) {
                          
                            var value = $(element).val().trim() || "";
                            switch (key) {
                                case 'email':
                                    var regex = /^(?!\.)^(?!.*\.{2})[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                                    if (value != "") {
                                        valid = regex.test(value);
                                        if (!valid) {
                                            errorMsg.push(window.i18nData['invalid_email'] || 'Invalid email address.');
                                        }
                                    } else {
                                        countRequired++;
                                        valid = false;
                                    }

                                    break;
                                case 'fname':
                                case 'lname':
                                    var regex = /^[\p{L} '-]*$/u;
                                    if (value != "") {
                                        valid = regex.test(value);
                                        if (!valid) {
                                            if (key == "fname") {
                                                errorMsg.push(window.i18nData['invalid_fname'] || 'Invalid First Name.');
                                            } else if (key == "lname") {
                                                errorMsg.push(window.i18nData['invalid_lname'] || 'Invalid Last Name.');
                                            }
                                        }
                                    } else {
                                        countRequired++;
                                        valid = false;
                                    }
                                    break;

                                case 'phone':
                                    if (!klarna_obj.phoneIntelli.isValidNumber()) {
                                        countRequired++;
                                        valid = false;
                                         errorMsg.push(window.i18nData['invalid_phone'] || 'Invalid Phone Number.');
                                    }  else {
                                        valid = true;
                                    }
                                    break;
                               
                                case 'address2':
                                    valid = true;
                                    break;                              

                                default:
                                    valid = value == '' ? false : true;
                                    break;
                            }
                            if (valid) {
                                $(element).removeClass("error").addClass("valid");
                            }
                            else {
                                totalErrors++;
                                $(element).removeClass("valid").addClass("error");
                            }
                        }
                    }
                }
            }

            if (totalErrors == 0) {
                if (typeof successCallback === 'function') {
                    successCallback();
                }
            }
            else {

                if(countRequired > 0){
                    errorMsg.push(window.i18nData['required_fields'] || 'Required field(s) highlighted in red');
                }
                if (typeof failedCallback === 'function') {
                     failedCallback(((errorMsg.length != 0)? errorMsg  : window.i18nData['required_fields2'] || 'Please fill up required fields highlighted in red.'));
                }
            }
        }
        
        klarna_obj.init = function (callback) {                          
                
              $(document).ready(function () {  
                klarna_obj.closeModalEvents();                
                klarna_obj.openKlarnaDialog();              
                if(typeof callback=="function"){
                    callback();
                }                
            });
        }
        return klarna_obj;
    }

    var klarnaInit = 'CRMKlarnaPayment';
    if (typeof window[klarnaInit] !== 'function') {
        window[klarnaInit] = KlarnaPayment;
    }
})();
