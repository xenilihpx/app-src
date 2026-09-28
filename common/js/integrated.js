(function () {
    var Form = function (config) {       
        
        var form_obj = {},
            
            //commonFilesPath = (window.location.pathname.toLowerCase().indexOf("/mobile")!=-1)? "../../" : "../",
            apiPath = (window.location.pathname.toLowerCase().indexOf("/mobile")!=-1)? "../" : "",
            allowedCreditCard=["visa", "mastercard", "amex", "discover"],
            url=apiPath + 'integrated/',
            formField = {
                        'email': '#fields_email',
                        'fname': '#fields_fname',
                        'lname': '#fields_lname',
                        'address1': '#fields_address1',
                        'address2': '#fields_address2',
                        'city': '#fields_city',
                        'country': '#fields_country_select',
                        'state': '#fields_state',
                        'cpf' : '#fields_cpf', //for brazil 
                        'county' : '#fields_county', //for uk
                        'consigneeId' : '#fields_consigneeId', //for taiwan
                        'pccc' : '#fields_pccc', //for south korea
                        'houseno' : '#fields_houseno', //for brazil 
                        'bairro' : '#fields_bairro', //for brazil 
                        'barangay' : '#fields_barangay', //for ph
                        'tax_id': '#fields_tax_id', //for mx, indonesia (npwp/nik)
                        'neighborhood': '#fields_neighborhood', //for mx, indonesia (rt/rw)
                        'apt_unit': '#fields_apt_unit', //for mx, indonesia (blok/unit/lantai)
                        'ward': '#fields_ward', 
                        'district': '#fields_district', //for indonesia (kecamatan)
                        'village': '#fields_village', //for indonesia (desa/kelurahan)
                        'rt': '#fields_rt', //for indonesia (rt)  
                        'rw': '#fields_rw', //for indonesia (rw)
                        'zip': '#fields_zip',
                        'phone': '#fields_phone',
                        'cardnumber': '#cc_number',
                        'month': '#fields_expmonth',
                        'year': '#fields_expyear',
                        'exp_date': '#expiry_date',
                        'cvv': '#cc_cvv',
                        'checkboxsameaddress': '#chkboxSameAddress',
                        'checkboxsameaddressFields': {
                            'fnameBill': '#fields_fname_bill',
                            'lnameBill': '#fields_lname_bill',
                            'address1Bill': '#fields_address1_bill',
                            'address2Bill': '#fields_address2_bill',
                            'cityBill': '#fields_city_bill',
                            'stateBill': '#fields_state_bill',
                            'countryBill': '#fields_country_bill',
                            'zipBill': '#fields_zip_bill',
                            'phoneBill': '#fields_phone_bill',
                        },
                        'agreeterms': '#fields_agree',
                        'marketingoptin': '#fields_marketing' //checkbox for allow marketing sms
                    },
            differentBillingContainer = '#billingAddress',
            submitBtn = '#submitForm',
            paypalBtn = '.paypal-btn';

            //exit popup setup starts here
            form_obj.withCouponPopup = false; //true to activate popup;
            form_obj.elCouponPopupId = "#exitpop";
            form_obj.couponPopupShowinSec=5000; //seconds
            form_obj.couponPopupCallback; //function
            //exit popup setup ends here

            form_obj.prodSelectElement="";//product selection
            form_obj.alreadyTaken=false;

            form_obj.intlPhone = false;
            form_obj.intlPhoneInitialCountry = "",
            form_obj.wallet_phoneIntelli,
            form_obj.phoneIntelli;            
            form_obj.dynamicShippingFields=false;        

        if (typeof config != "undefined" && typeof config=="object") {
           if(config.hasOwnProperty("allowedCreditCard") && Array.isArray(config.allowedCreditCard)){
                allowedCreditCard=config.allowedCreditCard;               
           }
           if(config.hasOwnProperty("withCouponPopup")){
                form_obj.withCouponPopup = config.withCouponPopup;
           }
           if(config.hasOwnProperty("elCouponPopupId")){               
                form_obj.elCouponPopupId = config.elCouponPopupId;
           }
           if(config.hasOwnProperty("couponPopupShowinSec")){             
                form_obj.couponPopupShowinSec = config.couponPopupShowinSec;
           }
           if(config.hasOwnProperty("couponPopupShowinSec")){             
                form_obj.couponPopupShowinSec = config.couponPopupShowinSec;
           }
           if(config.hasOwnProperty("couponPopupCallback") && typeof config.couponPopupCallback == "function" ){             
                form_obj.couponPopupCallback = config.couponPopupCallback;
           }
           if(config.hasOwnProperty("intlPhone") && typeof config.intlPhone == "boolean" ){             
                form_obj.intlPhone = config.intlPhone;
           }
            if(config.hasOwnProperty("intlPhoneInitialCountry") && typeof config.intlPhoneInitialCountry == "string" ){             
                form_obj.intlPhoneInitialCountry = config.intlPhoneInitialCountry;
           }

           if(config.hasOwnProperty("dynamicShippingFields") && typeof config.dynamicShippingFields == "boolean" ){             
                form_obj.dynamicShippingFields = config.dynamicShippingFields;
           }
        }  
        
        window.commonFilesPath = function(){
            if(location.hostname=="127.0.0.1" || location.hostname=="localhost"){
                var slashLength=location.pathname.split('/').length - 1;
                if(slashLength==1 || slashLength==2){
                    return './';
                }else{
                    return (window.location.pathname.toLowerCase().indexOf("/mobile")!=-1)? "../../" : "../";
                }
            } else {            
                return (window.location.pathname.toLowerCase().indexOf("/mobile")!=-1)? "../../" : "../";
            }
        }

        //global functions 

        window.mobileAndTabletCheck = function() {
            var check = false;
            (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
            return check;
        };
        
        window.getQueryStringByName=function (name) {
                    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
                    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                        results = regex.exec(location.search);
                    return results == null ? "" : $.trim(decodeURIComponent(results[1].replace(/\+/g, " ")));
        }

        window.extractDomain = function (url) {
                    var domain;
                    if (url.indexOf("://") > -1) {
                        domain = url.split('/')[2];
                    }
                    else {
                        domain = url.split('/')[0];
                    }
                    return domain.trim();
        }

        window.removeQuerystring = function (url, parameter) {
            var urlparts = url.split('?');
            if (urlparts.length >= 2) {
                var prefix = encodeURIComponent(parameter) + '=';
                var pars = urlparts[1].split(/[&;]/g);
                for (var i = pars.length; i-- > 0;) {
                    if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                        pars.splice(i, 1);
                    }
                }
                url = urlparts[0] + '?' + pars.join('&');
                return url;
            } else {
                return url;
            }
        }

        window.getCurrentOffer = function () {
                    var currentOffer = window.location.pathname.substr(0, window.location.pathname.lastIndexOf('/')).toLowerCase().replace('/mobile', '');
                    var pos = currentOffer.lastIndexOf('/');
                    currentOffer = currentOffer.substr(pos).replace("/", "").trim();
                    if (currentOffer == extractDomain(window.location.pathname)) {
                        currentOffer = '';
                    }
                    return currentOffer;
        }

        window.extractKey=function(jsonObject) {
                    if (jsonObject && jsonObject.data && jsonObject.data.key) {
                        return jsonObject.data.key;
                    } else {
                        return null;
                    }
        }

        //array with , 
        window.joinWithAnd=function(arr) {
                    if (arr.length === 0) return "";
                    if (arr.length === 1) return arr[0];
                    if (arr.length === 2) return arr.join(" and ");
                    return arr.slice(0, -1).join(", ") + " and " + arr[arr.length - 1];
        }

        window.removeLoading = function(){
            $(".loading-indicator").remove();
        }

        //popup window
        window.openNewWindow = function (page_url, type, window_name, width, height, top, left, features) {
                    if (!type) {
                            type = 'popup';
                    }
                    if (!width) {
                            width = 480;
                    }
                    if (!height) {
                            height = 480;
                    }
                    if (!top) {
                            top = 50;
                    }
                    if (!left) {
                            left = 50;
                    }
                    if (!features) {
                            features = 'resizable,scrollbars';
                    }

                    if(getQueryStringByName("lang")!=""){
                        page_url+="?lang="+getQueryStringByName("lang");
                    }

                    if (type == 'popup') {
                            var settings = 'height=' + height + ',';
                                settings += 'width=' + width + ',';
                                settings += 'top=' + top + ',';
                                settings += 'left=' + left + ',';
                                settings += features;
                                win = window.open(page_url, window_name, settings);
                                win.window.focus();
                    } else if (type == 'modal') {
                                
                                
                                var html = '';
                                html += '<div id="app_common_modal">';
                                html += '<div class="app_modal_body"><a href="javascript:void(0);" id="app_common_modal_close">X</a>';
                                html += '<div class="loading-indicator" style="position:absolute;left:3.9em;"><p style="display:flex;"><img src="'+commonFilesPath()+'src/common/images/loading_icon.gif" style="width:25px;height:25px;"/>&nbsp;Loading content...</p></div>';
                                html += '<iframe src="' + page_url + '" frameborder="0" onload="removeLoading();"></iframe></div>';
                                html += '</div>';

                                if (!$('#app_common_modal').length) {
                                    $('body').append(html);
                                }
                                $('#app_common_modal').fadeIn();
                    }
        }

        form_obj.redirectToNextPage=function(){
            if(form_obj.alreadyTaken && form_obj.isUpsellPage()){
                            form_obj.alreadyTaken=false;
                            $(".no-upsell-link:first").trigger("click");
            }
        }

        form_obj.paypalOnError=function(){
            if(getQueryStringByName("paypalAccept")=="1"){
                   window.location.href=location.origin+location.pathname;
            }
        }

        form_obj.errorMsgInUrl=function(){
                if(getQueryStringByName("errorMsg")!=""){
                     window.location.href=removeQuerystring(location.href,"errorMsg");
                }
        }

        //close modals events
        form_obj.closeModalEvents= function(){
            	$(window).keydown(function(e) {
                    if (e.which === 27 && $('#error_handler_overlay').length) {
                        $('#error_handler_overlay').remove();
                        form_obj.redirectToNextPage();
                        form_obj.errorMsgInUrl();
                        form_obj.paypalOnError();
                    }
                });

                $(document).off('click', '#error_handler_overlay');
                $(document).on('click', '#error_handler_overlay', function() {
                    $(this).remove();
                    form_obj.redirectToNextPage();
                    form_obj.errorMsgInUrl();
                    form_obj.paypalOnError();
                });

                $(document).off('click', '#error_handler_overlay_close');
                $(document).on('click', '#error_handler_overlay_close', function() {
                    $('#error_handler_overlay').remove();
                    form_obj.redirectToNextPage();
                    form_obj.errorMsgInUrl();
                    form_obj.paypalOnError();
                });

                $(document).on('click', '#app_common_modal_close', function() {
                    $('#app_common_modal').remove();
                    form_obj.redirectToNextPage();
                    form_obj.errorMsgInUrl();
                    form_obj.paypalOnError();
                });
        }

        //load common stylesheet
        form_obj.loadCommonStyles=function(){
            var styleSheet = document.createElement("link")
            styleSheet.setAttribute("id", "common_styles");
            styleSheet.href = commonFilesPath() + "src/common/css/common.css?t=" + new Date().getTime();
            styleSheet.rel = "stylesheet";
            if ($("#common_styles").length == 0) {
                document.head.appendChild(styleSheet);
            }
        }

        form_obj.disableBack=function(){
             var script = document.createElement("script");
            // 2. Add inline JavaScript code
            script.text = "history.pushState(null, null, window.location.href);"+
                          "history.back();"+
                          "history.forward();"+
                          "window.onpopstate = function () { history.go(1);};";

            // 3. Append it to <head>
            document.head.appendChild(script);       
        }

        //progress modal
        form_obj.progressModal=function(msg){
            var defaultMsg=(typeof msg =="undefined" || msg=="") ? window.i18nData['loading_msg'] || 'Your Product Is Being Reserved':msg; 
            var html=""
                html+='<section id="loading-indicator" class="popup-loading-wrapper" style="display: none">';
                html+='<div class="popup">';
                html+='<img src="'+commonFilesPath()+'src/common/images/icon-lock.png" alt="" class="lock-image" />';
                html+='   <p>'+(window.i18nData['loading_msg_header'] || 'Please wait a moment')+'</p>';
                html+='    <h3>'+defaultMsg+'</h3>';
                html+='    <img src="'+commonFilesPath()+'src/common/images/icon-loading.png" alt="" class="loading-image" />';
                html+='</div>';
                html+='</section>';
            
            $('body').append(html);
			$('#loading-indicator').fadeIn(500);
        }

        //remove progress modal
        form_obj.removeProgressModal=function(){
            $('#loading-indicator').fadeOut(500).remove();
        }

        form_obj.countryCheck=function (el, countrySel) {
            
            var zipMaxField = 5, phoneFieldMax = 12;
            

            //initiate default value 
            $(formField.state).val("").trigger("change");
            $(formField.cpf).val("");
            $(formField.houseno).val("");
            $(formField.bairro).val("");
            $(formField.city).val("");
            $(formField.zip).val("");

            if(countrySel=="MO" || countrySel=="PK" || countrySel=="QA" || countrySel=="HK" || countrySel=="TR"){
                $(".ddp").hide();
            }else {
                $(".ddp").show();
            }

            switch (((countrySel!=null && countrySel!="") ? countrySel.toLowerCase() : "")) {

                case 'ca':
                    zipMaxField = 7;
                    $(formField.zip).mask("AAA AAA");                    
                    break;
                case 'gb':
                    zipMaxField = 10;
                    $(formField.zip).mask("AAAAAAAA");
                    break;
                case 'cl':
                    zipMaxField = 7;
                    break;                
                case 'pt':
                    zipMaxField = 8;
                    $(formField.zip).mask("0000-000");
                    break;
                case 'jp':
                    zipMaxField = 8;
                    $(formField.zip).mask("000-0000");
                    break;
                case 'br':
                    zipMaxField = 9;
                    $(formField.zip).mask("00000-000");
                
                    break;
                case 'sg':
                    zipMaxField = 6;
                    $(formField.zip).mask("000000");
                    $(formField.state).val("SG").trigger("change").removeClass("error");
                    $(formField.state).next("span.error-message").remove();
                    $(formField.city).val("Singapore");
                 
                    break;
                case 'in':
                case 'vn':
                case 'ro':
                    zipMaxField = 6;
                    $(formField.zip).mask("000000");
                    break;
                case 'ie':
                    zipMaxField = 8;
                    $(formField.zip).mask("AAA AAAA");
                    break;
                case 'fi':
                case 'hr':
                case 'de':
                case 'fr':
                case 'mc':
                case 'cr':
                case 'mx':
                case 'es':
                case 'kr':
                    zipMaxField = 5;
                    $(formField.zip).mask("00000");
                    break;
                case 'my':
                    zipMaxField = 5;
                    $(formField.zip).mask("00000");
                    break;
                case 'id':
                    zipMaxField = 5;
                    $(formField.zip).mask("00000");
                    $(formField.tax_id).mask("0000000000000000");
                    break;

                case 'mo':
                    zipMaxField = 6; 
                    $(formField.zip).val('999078');    
                    $(formField.city).val('Macao');        
                    break;
                
                case 'tw':
                    zipMaxField = 6;
                    $(formField.zip).mask("000000");               
                    break;

                case 'se':
                case 'cz':
                    zipMaxField = 6;
                    $(formField.zip).mask("000 00");               
                    break;
                case 'nl':
                    zipMaxField = 7;
                    $(formField.zip).mask("0000 AA");
                    break;
                case 'at':
                case 'be':
                case 'ch':
                case 'lu':
                case 'au':
                case 'nz':
                case 'hu':
                case 'dk':
                case 'no':
                    zipMaxField = 4;
                    $(formField.zip).mask("0000");
                    break; 
                case 'us':
                    zipMaxField = 10;
                    $(formField.zip).mask("00000-0000");
                    break;                     
                case 'hk':
                     zipMaxField = 6;
                     $(formField.state).val("Hong Kong").removeClass("error");
                     $(formField.state).next("span.error-message").remove();
                     $(formField.zip).val('999077');
                  
                    break;
                case 'pl':
                    zipMaxField = 6;
                    $(formField.zip).mask("00-000");
                    break;
                default:
                    zipMaxField = 5;
                    //$(formField.phone).mask("000 000 0000");
                    break;
            }


            if (el.indexOf('bill') > 0) {
                //if this is from same as billing address country field
                $(formField.checkboxsameaddressFields.zipBill).attr('maxlength', zipMaxField);
                $(formField.checkboxsameaddressFields.phoneBill).attr('maxlength', phoneFieldMax);
            }
            else {
                $(formField.zip).attr('maxlength', zipMaxField);
                //$(formField.phone).attr('maxlength', phoneFieldMax);
            }
        }
        
        form_obj.reqEventAfterShipLoaded=function(el, country){
         
            form_obj.inputKeyPressValidation();
            
            //cpf for brazil
            if($(formField.cpf).length!=0){
                $(formField.cpf).mask('000.000.000-00');
            }

            if (el.indexOf('bill') > 0) {
                //if this is from same as billing address country field
                 form_obj.addStatesProvince(formField.checkboxsameaddressFields.stateBill);
            } else {
                form_obj.addStatesProvince(formField.state);
            }

            form_obj.countryCheck(el, country);
            form_obj.dynamicShipFieldCallback();
        }

        form_obj.unableToLoad=function(){
             $('#loading-shipping-detail').html("<small>"+(window.i18nData['something_wrong'] || 'Something went wrong.')+"</small>");
        }

        form_obj.dynamicShipFieldCallback = function(){
            
        }

        form_obj.loadDynamicShipFields=function(country,el){
            $("#shipping-container #loading-shipping-detail").remove();
            $("#shipping-container").prepend("<div id='loading-shipping-detail'><img src='"+commonFilesPath()+"src/common/images/load-shipping.svg' alt='loading' width='70' height='70'/><div>");
            $("#shipping-container").load("src/shipping/shipping-address-"+country.toLowerCase()+".php?lang="+$("html").attr("lang")+"&country_code="+country.toLowerCase(), function( response, status, xhr ) {
                if ( status == "error" && xhr.status==404 ) {
                    $("#shipping-container").load("src/shipping/shipping-address-us.php?lang="+$("html").attr("lang")+"&country_code="+country.toLowerCase(), function( response, status, xhr ) {
                        if (status != "error"){ 
                            form_obj.reqEventAfterShipLoaded(el, country);
                        }else{                           
                            form_obj.unableToLoad();
                        }                        
                    });
                } else if(status != "error") { 
                    form_obj.reqEventAfterShipLoaded(el, country);
                }else{
                    form_obj.unableToLoad();
                }
            });
        }

        //country field event that listens to change event on the country fields
        form_obj.countryFieldsEvent=function(el) {
            $(document).ready(function () {
                form_obj.countryCheck(el,"");

                $(el).unbind().change(function (e) {
                    //if(!form_obj.dynamicShippingFields){
                        if (el.indexOf('bill') > 0) {
                            //if this is from same as billing address country field
                            form_obj.addStatesProvince(formField.checkboxsameaddressFields.stateBill);
                        }
                        else {
                            form_obj.addStatesProvince(formField.state);
                        }
                    //}                    
                   
                    if($(this).val()!=""){     
                                          
                        if(form_obj.dynamicShippingFields && typeof e.originalEvent!="undefined"){
                            var tempFolder = $(this).find('option:selected').attr('temp-folder');
                            form_obj.loadDynamicShipFields($(this).val(),el,tempFolder);
                        } else {
                            form_obj.countryCheck(el, $(this).val());
                        }
                    }                    
                });

                $(formField.country).val($("#userCountry").val().toUpperCase()).trigger("change");
            });
        }

        //add states to select
        form_obj.addStatesProvince=function(el_id) {
            if($(formField.state).length==0){
                return;
            }
            var country;
            //then this must be a select box having a country to choose from
            if ($(formField.country).length > 0 && el_id == formField.state) {
                country = $(formField.country).find('option:selected').val();
            }
            else {
                country = $(formField.checkboxsameaddressFields.countryBill).find('option:selected').val();
                if (typeof country === 'undefined') {
                    country = $(formField.country).find('option:selected').val();
                }
            }
        
            $(el_id).empty();
            $(el_id).append($('<option></option>').val('').html((window.i18nData['select'] || '-- Select --')));
            $(States).each(function (key, value) {
                $.each(value, function (key, value) {
                    if (key == country) {
                        $.each(value, function (key, value) {
                            $.each(value, function (stateCode, stateName) {
                                //use the DeepL-translated name for the current language when
                                //available (see src/common/statesTranslator.php), else fall back to
                                //the English name from states.js
                                var translated = window.TranslatedStates
                                    && window.TranslatedStates[country]
                                    && window.TranslatedStates[country][stateCode];
                                $(el_id).append($('<option></option>').val(stateCode).html(translated || stateName));
                            });
                        });
                    }
                });
            });

            if (el_id != formField.checkboxsameaddressFields.stateBill) {
                if ($(formField.checkboxsameaddressFields.stateBill).length > 0) {
                    $(formField.checkboxsameaddressFields.stateBill).empty();
                    var options2 = $(el_id).html();
                    $(formField.checkboxsameaddressFields.stateBill).html(options2);
                }
            }
        }

        form_obj.showMsg=function(errors){
            var li = '';
            if(Array.isArray(errors)){
                $.each(errors, function(key, value) {
                    li += '<li>' + value + '</li>';
                });
            }else{
                li += '<li>' + errors + '</li>'; 
            }

            var html = '';
			html += '<div id="error_handler_overlay">';
			html += '<div class="error_handler_body"><a href="javascript:void(0);" id="error_handler_overlay_close">X</a><ul style="list-style: none;">' + li + '</ul></div>';
			html += '</div>';
            
            if($("#error_handler_overlay").length==0){
                $('body').append(html);
			    $('#error_handler_overlay').fadeIn(100);
            }
			
			return false;
        }

        form_obj.inputElEvents=function(){
            $(formField.cvv+","+formField.cardnumber).on('keydown', function (e) {
                // Allow: backspace, delete, tab, escape, enter, and arrows
                if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 37, 38, 39, 40]) !== -1 ||
                    // Allow: Ctrl+A, Command+A
                    (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
                    // Allow: Ctrl+C, Command+C
                    (e.keyCode === 67 && (e.ctrlKey === true || e.metaKey === true)) ||
                    // Allow: Ctrl+V, Command+V
                    (e.keyCode === 86 && (e.ctrlKey === true || e.metaKey === true)) ||
                    // Allow: Ctrl+X, Command+X
                    (e.keyCode === 88 && (e.ctrlKey === true || e.metaKey === true))) {
                    // let it happen, don't do anything
                    return;
                }
                // Ensure that it is a number and stop the keypress
                if ((e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });

            //check if test using test cards
            $(formField.cardnumber).on('blur keyup change', function () {

                if ($.trim($(this).val()) != '') {
                    var firstChar = $(this).val().substr(0, 1);
                    var creditCard = "";
                    if (firstChar.length > 0 && /^-?\d+$/.test(firstChar)) {
                        switch (parseInt(firstChar)) {
                            case 4:
                                creditCard = 'visa';                           
                                break;
                            case 5:
                                creditCard = 'mastercard';                            
                                break;
                            case 6:
                                creditCard = 'discover';
                                break;
                            case 3:
                                creditCard = 'amex';                           
                                break;
                            default:
                                creditCard = 'visa';
                        }
                        
                        if (allowedCreditCard.length >= 1 && allowedCreditCard.indexOf(creditCard) == -1) {
                            form_obj.showMsg("Only " + joinWithAnd(allowedCreditCard) + ((allowedCreditCard.length>1)? " are" : " is") + " supported!");
                            $(this).val('');
                        }
                    }
                }

                if ($(this).hasClass("valid")) {
                    if (
                        $(this).val() == "0000000000000000" ||
                        $(this).val() == "7111111111100000" ||
                        $(this).val() == "0000000001111111"
                    ) {
                        sessionStorage.setItem("testmode", true);
                    } else {
                        sessionStorage.removeItem("testmode");
                    }
                }
            });

            $(formField.cvv+","+formField.cardnumber+","+formField.phone+","+formField.phoneBill+",#wallet_phone").on('input', function () {
                this.value = this.value.replace(/\D/g, '');
            });

            $(formField.city+","+formField.checkboxsameaddressFields.cityBill).on('input', function () {
                var inputVal = $(this).val();
                //var validVal = inputVal.replace(/[^a-zA-Z\s]/g, '');
                $(this).val(inputVal);
            });

            $(formField.checkboxsameaddress).on('click', function () {
                if ($(this).is(':checked')) {
                    $(differentBillingContainer).hide();
                }
                else {                   
                    $(formField.checkboxsameaddressFields.countryBill).val($(formField.country).val());
                    setTimeout(function () {
                        $(formField.checkboxsameaddressFields.countryBill).trigger('change');                        
                    }, 500);

                    $(differentBillingContainer).show();
                }
            });

             //cpf for brazil
             if($(formField.cpf).length!=0){
                $(formField.cpf).mask('000.000.000-00');
             }
            


        }

        form_obj.inputsElSetup=function(){
            
            if($(formField.cardnumber).length!=0){
                $(formField.cardnumber).attr('maxlength', 16);
                $(formField.cvv).attr('maxlength', 4).attr('type', 'password').attr({ 'inputmode': 'numeric', 'pattern': '[0-9]*' });
                $(formField.cardnumber).attr({ 'inputmode': 'numeric', 'pattern': '[0-9]*' });
                $(formField.exp_date).attr('maxlength', 7).attr({ 'inputmode': 'numeric', 'pattern': '[0-9]*' });
            }

            if($(formField.phone).length!=0){                
                $(formField.phone).attr({ 'inputmode': 'numeric', 'pattern': '[0-9]*' });
                $(formField.checkboxsameaddressFields.phoneBill).attr({ 'inputmode': 'numeric', 'pattern': '[0-9]*' });
            }
            
            if($(formField.country).length!=0 || $(formField.checkboxsameaddressFields.countryBill).length!=0){
                form_obj.countryFieldsEvent(formField.country);
                form_obj.countryFieldsEvent(formField.checkboxsameaddressFields.countryBill);
            }
            
        }

        form_obj.ccExpirationDate=function(){
            if($(formField.month).length!=0 && $(formField.year).length!=0){
                
                // Define an array with month options
                var months = [
                    { value: '', text: (window.i18nData['month'] || 'MONTH') },
                    { value: '01', text: window.i18nData['january'] || '01 (Jan)' },
                    { value: '02', text: window.i18nData['february'] || '02 (Feb)' },
                    { value: '03', text: window.i18nData['march'] || '03 (Mar)' },
                    { value: '04', text: window.i18nData['april'] || '04 (Apr)' },
                    { value: '05', text: window.i18nData['may'] || '05 (May)' },
                    { value: '06', text: window.i18nData['june'] || '06 (Jun)' },
                    { value: '07', text: window.i18nData['july'] || '07 (Jul)' },
                    { value: '08', text: window.i18nData['august'] || '08 (Aug)' },
                    { value: '09', text: window.i18nData['september'] || '09 (Sep)' },
                    { value: '10', text: window.i18nData['october'] || '10 (Oct)' },
                    { value: '11', text: window.i18nData['november'] || '11 (Nov)' },
                    { value: '12', text: window.i18nData['december'] || '12 (Dec)' }
                ];

                
                $(formField.month).empty();
                $.each(months, function (index, month) {
                     $(formField.month).append($('<option></option>').attr('value', month.value).text(month.text));
                });

                // setup year expiration
                var currentYear = new Date().getFullYear();                
                var numberOfYears = 40;
                 $(formField.year).empty();
                 $(formField.year).append('<option value="">'+(window.i18nData['year'] || "YEAR") +'</option>');

                // Populate dropdown with year options
                for (var i = 0; i < numberOfYears; i++) {
                    var year = currentYear + i;
                     $(formField.year).append($('<option></option>').attr('value', year.toString().slice(-2)).text(year));
                }
            }
        }

        form_obj.differentBillingAddressForm=function(callback) {
       
            if ($('.billingAddress-input-format').length > 0) {
                var className = $(".billingAddress-input-format input").attr("class");
                var inputLabel = [
                    { label: "First Name", input: "<input class='" + className + "' id='" + formField.checkboxsameaddressFields.fnameBill.replace(/[.#]/g, '') + "' name='" + formField.checkboxsameaddressFields.fnameBill.replace(/[.#]/g, '') + "' placeholder='First Name'/>" },
                    { label: "Last Name", input: "<input class='" + className + "' id='" + formField.checkboxsameaddressFields.lnameBill.replace(/[.#]/g, '') + "' name='" + formField.checkboxsameaddressFields.lnameBill.replace(/[.#]/g, '') + "' placeholder='Last Name'/>" },
                    { label: "Address 1", input: "<input class='" + className + "' id='" + formField.checkboxsameaddressFields.address1Bill.replace(/[.#]/g, '') + "' name='" + formField.checkboxsameaddressFields.address1Bill.replace(/[.#]/g, '') + "' placeholder='Address 1'/>" },
                    { label: "Address 2", input: "<input class='" + className + "' id='" + formField.checkboxsameaddressFields.address2Bill.replace(/[.#]/g, '') + "' name='" + formField.checkboxsameaddressFields.address2Bill.replace(/[.#]/g, '') + "' placeholder='Address 2'/>" },
                    { label: "Country", input: "<select class='" + className + "' name='" + formField.checkboxsameaddressFields.countryBill.replace(/[.#]/g, '') + "' id='" + formField.checkboxsameaddressFields.countryBill.replace(/[.#]/g, '') + "'></select>" },
                    { label: "State", input: "<select class='" + className + "' name='" + formField.checkboxsameaddressFields.stateBill.replace(/[.#]/g, '') + "' id='" + formField.checkboxsameaddressFields.stateBill.replace(/[.#]/g, '') + "'></select>" },
                    { label: "City", input: "<input class='" + className + "' id='" + formField.checkboxsameaddressFields.cityBill.replace(/[.#]/g, '') + "' name='" + formField.checkboxsameaddressFields.cityBill.replace(/[.#]/g, '') + "' placeholder='City'/>" },
                    { label: "Zip", input: "<input class='" + className + "' maxlength='10' id='" + formField.checkboxsameaddressFields.zipBill.replace(/[.#]/g, '') + "' name='" + formField.checkboxsameaddressFields.zipBill.replace(/[.#]/g, '') + "' placeholder='Zip'/>" },
                ];

                $(".billingAddress-input-format input").remove();
                var inputFormat = $(".billingAddress-input-format").html();
                $(".billingAddress-input-format").remove();
                $.each(inputLabel, function (key, value) {
                    var newFormat = inputFormat.replace("{{label}}", value["label"]).replace("{{input}}", value["input"]);
                    $("#billingAddress").append(newFormat);
                });

                $("#" + formField.checkboxsameaddressFields.countryBill.replace(/[.#]/g, '')).html($(formField.country).html())
                //wait for 100 ms before adding states
                setTimeout(function () {
                    form_obj.addStatesProvince(formField.checkboxsameaddressFields.stateBill, 100);                   
                })
            }
            if (typeof callback === 'function') {
                callback();
            }
        }

        form_obj.optimizeCardExpiration=function(){
            $(formField.exp_date).unbind().on('input', function (event) {
                var inputValue = $(this).val().replace(/[^0-9]/g, ''); // Only allow numeric characters

                // Format the input as "MM / YY"
                if (inputValue.length >= 2) {
                    let month = parseInt(inputValue.slice(0, 2), 10);
                    // Check if month is valid once two digits are entered
                    if (month < 1 || month > 12) {
                        //form_obj.showMsg(window.i18nData['invalid_exp_month'] || 'Please enter a valid month (01-12).');
                        $(this).val(inputValue.slice(0, 1));
                        return;
                    }

                    // Add separator after month
                    if (inputValue.length > 2) {
                        var year = inputValue.slice(2, 4);
                        var curYear = new Date().getFullYear().toString().substr(2);
                        // Only check year validity if we have exactly 2 digits for the year
                        if (year.length === 2 && parseInt(year, 10) < parseInt(curYear)) {
                            //form_obj.showMsg(window.i18nData['invalid_exp_year'] || 'Please enter a valid year (' + curYear + ' or greater).');
                            $(this).val(inputValue.slice(0, 2) + ' / '); // Reset to just month with separator
                            return;
                        }
                        // Format as "MM / YY"
                        $(this).val(inputValue.slice(0, 2) + ' / ' + year);
                    }else{
                        if (event.originalEvent.inputType === "deleteContentBackward") {
                            $(this).val(inputValue.slice(0, 1));
                        }else{
                            $(this).val(inputValue.slice(0, 2) + ' / ');
                        }                                             
                    }                    
                } else {                    
                    if(inputValue > 1 && inputValue <= 9){
                        $(this).val("0"+inputValue+ ' / '); // Update field as user types if less than 2 digits
                    }else{  
                        $(this).val(inputValue); // Update field as user types if less than 2 digits
                    }                    
                }
            });
        }

        form_obj.checkEmptyFields=function(el, value, msg){
            if($(el).is('[optional]')){
                valid=true;
            }else if (value.trim() == "") {                                       
                valid = false;                                        
            }else{
                valid=true;
            }
            form_obj.inlineErrorMsg(el,value,valid,msg);
            return valid;
        }

        form_obj.inputKeyPressValidation=function(){
            form_obj.optimizeCardExpiration();

           

            var valid = false;
            for (var key in formField) {
                if (formField.hasOwnProperty(key)) {
                    var element = $(formField[key]);
                    if (element.is('*')) {
                        //checks if the variable is an html element                       

                         $(element).on('blur', {
                            key: key
                        }, function (data) {
                            var value = $(this).val().trim();
                            var fieldKey = extractKey(data);
                            if(fieldKey == "email"){
                                switch (fieldKey) {
                                    case 'email':
                                        var regex = /^(?!\.)^(?!.*\.{2})[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                                        valid = regex.test(value);
                                        form_obj.inlineErrorMsg(this,value,valid,window.i18nData['invalid_email'] || 'Invalid email address.');
                                        break;
                                }
                                if (valid) {
                                    $(this).removeClass("error");
                                } else {
                                    $(this).removeClass("valid").addClass("error");                                
                                }
                            }
                            
                        });

                        $(element).on('input', {
                            key: key
                        }, function (data) {
                            var value = $(this).val().trim();
                            var fieldKey = extractKey(data);
                            switch (fieldKey) {                               
                                case 'fname':
                                case 'lname':
                                    var regex = /^[\p{L} '.-]*$/u;
                                    if (value != "") {
                                        valid = regex.test(value);
                                        if (fieldKey == "fname") {
                                            form_obj.inlineErrorMsg(this,value,valid,window.i18nData['invalid_fname'] || 'Invalid First Name.');
                                        } else if (fieldKey == "lname") {
                                            form_obj.inlineErrorMsg(this,value,valid,window.i18nData['invalid_lname'] || 'Invalid Last Name.');
                                        }
                                    }else{
                                        valid = false;
                                        form_obj.inlineErrorMsg(this,value,valid,window.i18nData['required'] || 'Required Field');
                                    }
                                    break;
                                case 'consigneeId': 
                                    if (value != "") {
                                        if (value.length < 8) {
                                            valid = false;
                                        } else {
                                            valid = true;
                                        }
                                    } 
                                    form_obj.inlineErrorMsg(this,value,valid,window.i18nData['invalid_consigneeId'] || 'Invalid Consignee Id.');
                                    break;
                                
                                case 'pccc': 
                                    if (value != "") {
                                        if (value.length < 13) {
                                            valid = false;
                                        } else {
                                            valid = true;
                                        }
                                    } 
                                    form_obj.inlineErrorMsg(this,value,valid,window.i18nData['invalid_code_id'] || 'Invalid Code or ID.');
                                    break;

                                case 'district': 
                                    valid = form_obj.checkEmptyFields(this, value, window.i18nData['invalid_district'] || 'Please enter district');
                                    break;

                                case 'village': 
                                    valid = form_obj.checkEmptyFields(this, value, window.i18nData['invalid_village'] || 'Please enter village');
                                    break;  

                                case 'apt_unit':    
                                    valid = form_obj.checkEmptyFields(this, value, window.i18nData['invalid_apt_unit'] || 'Please enter apartment/unit number');
                                    break;

                                case 'neighborhood': 
                                    valid = form_obj.checkEmptyFields(this, value, window.i18nData['invalid_neighborhood'] || 'Please enter neighborhood');
                                    break;

                                case 'county':
                                    valid = form_obj.checkEmptyFields(this, value, window.i18nData['invalid_county'] || 'Please enter county');
                                    break;
                                
                                case 'ward':
                                    valid = form_obj.checkEmptyFields(this, value, window.i18nData['invalid_ward'] || 'Please enter ward');
                                    break;

                                // case 'address1':
                                //     valid = form_obj.checkEmptyFields(this, value, window.i18nData['invalid_address1'] || 'Please enter address line 1.');
                                //     break;  

                                case 'houseno':                                   
                                    valid = form_obj.checkEmptyFields(this, value, window.i18nData['invalid_houseno'] || 'Please enter house number.');
                                    break;

                                case 'bairro':                                   
                                    valid = form_obj.checkEmptyFields(this, value, window.i18nData['invalid_bairro'] || 'Please enter Bairro.');
                                    break;

                                case 'tax_id': 
                                    if (value != "") {
                                        if($(this).is('[minlength]') &&  value.length < $(this).attr("minlength")){
                                            valid=false;
                                        }else if (value.length < 15 && $(formField.country).val().toUpperCase() == "ID") {
                                            valid = false;
                                        } else if (value.length < 12 && $(formField.country).val().toUpperCase() == "MX") {
                                            valid = false;
                                        } else {
                                            valid = true;
                                        }
                                    } else {
                                        if($(this).is('[optional]')){
                                            valid=true;
                                        }else{
                                            valid=false;
                                        }
                                    }
                                    form_obj.inlineErrorMsg(this,value,valid,window.i18nData['invalid_tax_id'] || 'Invalid Code or Tax ID.');
                                    break;

                                case 'cvv':
                                    if (value != "") {
                                        if (value.length < 3) {
                                            valid = false;
                                        } else {
                                            valid = true;
                                        }
                                    } 
                                    form_obj.inlineErrorMsg(this,value,valid,window.i18nData['invalid_cvv'] || 'Invalid Card Verification Value.');
                                    break;
                                case 'cardnumber':
                                    if (value != "") {
                                        if (value.length < 15) {
                                            valid = false;
                                        } else {
                                            valid = true;
                                        }
                                    }
                                    form_obj.inlineErrorMsg(this,value,valid,window.i18nData['invalid_cc'] || 'Invalid Credit Card Number.');
                                    break;
                                case 'phone':
                                    if (value == "") {                                       
                                        valid = false;                                        
                                    }else{
                                        valid=true;
                                    }
                                    // if($(formField.country).val().toLowerCase()=="jp"){
                                    //     if(value.length==parseInt($(this).attr('maxlength'))){
                                    //         valid=true;
                                    //     }else{
                                    //         valid=false;                                           
                                    //     }
                                    // }else{
                                    //     valid = form_obj.phoneIntelli.isValidNumber();
                                    // }
                                    form_obj.inlineErrorMsg(this,value,valid,window.i18nData['required'] || 'Required Field');
                                    break;
                                case 'cpf':
                                    valid=form_obj.validate_cpf(value,true);
                                    form_obj.inlineErrorMsg(this,value,valid,window.i18nData['invalid_cpf'] || 'Please enter a valid CPF number.');
                                    break;

                                case 'exp_date':
                                    if (value.indexOf("/") != -1) {
                                        var cardExp = value.split('/');
                                        if (cardExp[0].trim() == "") {
                                            valid = false;
                                        } else if (cardExp[1].trim() == "") {                                            
                                            valid = false;
                                        } else if (cardExp[1].trim() != ""){
                                            var year = cardExp[1].trim();
                                            var curYear = new Date().getFullYear().toString().substr(2);
                                            if (year.length===1 || year.length === 2 && parseInt(year, 10) < parseInt(curYear)) {                                                
                                                valid=false;
                                            } else{
                                                valid=true;
                                            }                                           
                                        }else {
                                            valid = true;
                                        }
                                    } else {
                                        valid = false;
                                    }
                                    form_obj.inlineErrorMsg(this,value,valid,window.i18nData['invalid_exp_date'] || 'Invalid Expiration Date.');
                                    break;

                                case 'address2':
                                    valid = true;
                                    break;
                                case 'checkboxsameaddress':
                                    if ($(this).is(':checked') == false) {
                                        //verify the billing address
                                        var billFields = formField['checkboxsameaddressFields'];
                                        for (var key2 in billFields) {
                                            if (billFields.hasOwnProperty(key2)) {
                                                var billElement = billFields[key2];
                                                if (billElement != null) {
                                                    if (key2 == "fnameBill" || key2 == "lnameBill") {
                                                        $(billElement).on('input', {
                                                            key: key
                                                        }, function (data) {
                                                            var valueBill = $(this).val().trim();
                                                            var regex = /^[\p{L} '-]*$/u;
                                                            if (valueBill != '') {
                                                                valid = regex.test(valueBill);
                                                                if (!valid) {
                                                                    $(this).removeClass("valid").addClass("error");
                                                                } else {
                                                                    $(this).removeClass("error").addClass('valid');
                                                                }
                                                            } else {
                                                                $(this).removeClass("valid").addClass("error");
                                                            }
                                                        }
                                                        );

                                                    } else if (key2 != 'address2Bill') {
                                                        $(billElement).on('input', {
                                                            key: key
                                                        }, function (data) {
                                                            var valueBill = $(this).val().trim();
                                                            if (valueBill != '') {
                                                                $(this).removeClass("error").addClass('valid');
                                                            } else {
                                                                $(this).removeClass("valid").addClass("error");
                                                            }
                                                        });
                                                    }
                                                }
                                                valid = true;
                                            }
                                        }
                                    } else {
                                        valid = true;
                                    }
                                    break;
                                default:
                                    valid = value == '' ? false : true;
                                    if(typeof $(this).attr('optional')!="undefined"){
                                        valid=true;
                                    }
                                    
                                    form_obj.inlineErrorMsg(this,value,valid,window.i18nData['required'] || 'Required Field.');
                                    break;
                            }


                            if (valid) {
                                $(this).removeClass("error");
                            } else {
                                $(this).removeClass("valid").addClass("error");
                            }
                        });
                    } //end check element
                }
            }

            //require any input/select element marked with the el-required attribute
            $('input[el-required], select[el-required]').on('blur input change', function () {
                var value = $(this).val() ? $(this).val().trim() : "";
                var valid = value != "";
                form_obj.inlineErrorMsg(this, value, valid, window.i18nData['required'] || 'Required Field.');
                if (valid) {
                    $(this).removeClass("error");
                } else {
                    $(this).removeClass("valid").addClass("error");
                }
            });
        }


        form_obj.inlineErrorMsg=function(el, value, valid, msg){
       
                if(value=="" && !valid){
                    if(($(el).attr("id")=="fields_phone" || $(el).attr("id")=="wallet_phone") && $(".iti.iti--allow-dropdown").length!=0){
                        if( $(".iti.iti--allow-dropdown").next("span.error-message").length==0){
                            var parent=$("#"+$(el).attr("id")).parent();
                            $(el).next('span.error-message').remove();
                            $(parent).after('<span class="error-message" style="color:red;font-size:14px;">'+msg+'</span>');   
                        }else if($(".iti.iti--allow-dropdown").next("span.error-message").length!=0){
                            var parent=$("#"+$(el).attr("id")).parent();
                            $(parent).next('span.error-message').remove();
                            $(parent).after('<span class="error-message" style="color:red;font-size:14px;">'+msg+'</span>');   
                        }
               
                    }else{
       
                        $(el).next('span.error-message').remove();
                        $(el).after('<span class="error-message" style="color:red;font-size:14px;">'+msg+'</span>');
                    }                    
                }else if(value!="" && !valid){
                    if(($(el).attr("id")=="fields_phone" || $(el).attr("id")=="wallet_phone") && $(".iti.iti--allow-dropdown").length!=0){
                        if( $(".iti.iti--allow-dropdown").next("span.error-message").length==0){
                            var parent=$("#"+$(el).attr("id")).parent();
                            $(el).next('span.error-message').remove();
                            $(parent).after('<span class="error-message" style="color:red;font-size:14px;">'+msg+'</span>');   
                        }else if($(".iti.iti--allow-dropdown").next("span.error-message").length!=0){
                            var parent=$("#"+$(el).attr("id")).parent();
                            $(parent).next('span.error-message').remove();
                            $(parent).after('<span class="error-message" style="color:red;font-size:14px;">'+msg+'</span>');  
                        }
                                       
                    }else{
                        $(el).next('span.error-message').remove();
                        $(el).after('<span class="error-message" style="color:red;font-size:14px;">'+msg+'</span>');  
                   
                    }                 
                }else{
                    if(($(el).attr("id")=="fields_phone" || $(el).attr("id")=="wallet_phone") && $(".iti.iti--allow-dropdown").length!=0){
                        var parent=$("#"+$(el).attr("id")).parent();
                        $(parent).next('span.error-message').remove(); 
                      
                    }else{
                        $(el).next('span.error-message').remove();
                    }
                    
                }
        }

        form_obj.validateForm=function(successCallback, failedCallback){
            var errorMsg = [];
            var valid = true;
            var countRequired = 0;
            var totalErrors = 0;

            for (var key in formField) {
                if (formField.hasOwnProperty(key)) {
                    var element = $(formField[key]);
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
                                            form_obj.inlineErrorMsg(element,value,valid,window.i18nData['invalid_email'] || 'Invalid email address.');
                                        }
                                    } else {
                                        countRequired++;
                                        valid = false;
                                        form_obj.inlineErrorMsg(element,value,valid,window.i18nData['required'] || 'Required Field.');
                                    }

                                    break;
                                case 'fname':
                                case 'lname':                     
                                    var regex = /^[\p{L} '.-]*$/u;
                                    if (value != "") {
                                        valid = regex.test(value);
                                        if (!valid) {
                                            if (key == "fname") {
                                                errorMsg.push(window.i18nData['invalid_fname'] || 'Invalid First Name.');
                                                form_obj.inlineErrorMsg(element,value,valid,window.i18nData['invalid_fname'] || 'Invalid First Name.');
                                            } else if (key == "lname") {
                                                errorMsg.push(window.i18nData['invalid_lname'] || 'Invalid Last Name.');
                                                form_obj.inlineErrorMsg(element,value,valid,window.i18nData['invalid_lname'] || 'Invalid Last Name.');
                                            }
                                        }
                                    } else {
                                        countRequired++;
                                        valid = false;
                                        form_obj.inlineErrorMsg(element,value,valid,window.i18nData['required'] || 'Required Field.');
                                    }
                                 
                                    break;

                                case 'phone':
                                    if (value == "") {
                                        valid=false;
                                    }else{
                                        valid=true;
                                    }
                                    // if($(formField.country).val().toLowerCase()=="jp"){
                                    //      if(parseInt(value.length)==parseInt(element.attr('maxlength'))){
                                    //         valid=true;
                                    //     }else{
                                    //         valid=false;
                                    //         errorMsg.push(window.i18nData['invalid_phone'] || 'Invalid Phone Number.');
                                    //         form_obj.inlineErrorMsg(element,value,valid,window.i18nData['invalid_phone'] || 'Invalid Phone Number.');
                                    //     }
                                    // }else if (!form_obj.phoneIntelli.isValidNumber()) {
                                    //     countRequired++;
                                    //     valid = false;
                                    //     errorMsg.push(window.i18nData['invalid_phone'] || 'Invalid Phone Number.');
                                    //     form_obj.inlineErrorMsg(element,value,valid,window.i18nData['invalid_phone'] || 'Invalid Phone Number.');
                                    // }  else {
                                    //     valid = true;
                                    // }
                                    form_obj.inlineErrorMsg(element,value,valid,window.i18nData['required'] || 'Required Field.');
                                    break;
                                case 'cpf':
                                    valid=form_obj.validate_cpf(value,true);
                                    if(!valid){
                                        errorMsg.push(window.i18nData['invalid_cpf'] || 'Please enter a valid CPF number.');
                                        form_obj.inlineErrorMsg(element,value,valid,window.i18nData['invalid_cpf'] || 'Please enter a valid CPF number.');
                                    }
                                    break;

                                case 'consigneeId':
                                    if(value=="" || value.length<1){
                                        valid=false;
                                        errorMsg.push(window.i18nData['invalid_consigneeId'] || 'Invalid Consignee Id.');
                                        form_obj.inlineErrorMsg(element,value,valid,window.i18nData['invalid_consigneeId'] || 'Invalid Consignee Id.');
                                    }else{
                                        valid=true;
                                    }
                                    break;
                                
                                case 'pccc':
                                    if(value=="" || value.length<1){
                                        valid=false;
                                        errorMsg.push(window.i18nData['invalid_code_id'] || 'Invalid Code or ID.');
                                        form_obj.inlineErrorMsg(element,value,valid,window.i18nData['invalid_code_id'] || 'Invalid Code or ID.');
                                    }else{
                                        valid=true;
                                    }
                                    break;

                                case 'tax_id':
                                    valid = form_obj.checkEmptyFields(element, value, window.i18nData['invalid_tax_id'] || 'Invalid Code or Tax ID.');
                                    if(!valid){
                                        errorMsg.push(window.i18nData['invalid_tax_id'] || 'Invalid Code or Tax ID.');
                                    }
                                    break;

                                case 'district': 
                                    valid = form_obj.checkEmptyFields(element, value, window.i18nData['invalid_district'] || 'Please enter district');
                                    if(!valid){
                                        errorMsg.push(window.i18nData['invalid_district'] || 'Please enter district');
                                    }
                                    break;

                                case 'village': 
                                    valid = form_obj.checkEmptyFields(element, value, window.i18nData['invalid_village'] || 'Please enter village');
                                    if(!valid){
                                        errorMsg.push(window.i18nData['invalid_village'] || 'Please enter village');
                                    }   
                                    break;  

                                case 'apt_unit':    
                                    valid = form_obj.checkEmptyFields(element, value, window.i18nData['invalid_apt_unit'] || 'Please enter apartment/unit number');
                                    if(!valid){
                                        errorMsg.push(window.i18nData['invalid_apt_unit'] || 'Please enter apartment/unit number');
                                    }
                                    break;

                                case 'neighborhood': 
                                    valid = form_obj.checkEmptyFields(element, value, window.i18nData['invalid_neighborhood'] || 'Please enter neighborhood');
                                    if(!valid){
                                        errorMsg.push(window.i18nData['invalid_neighborhood'] || 'Please enter neighborhood');
                                    }
                                    break;

                                case 'county':
                                    valid = form_obj.checkEmptyFields(element, value, window.i18nData['invalid_county'] || 'Please enter county');
                                    if(!valid){
                                        errorMsg.push(window.i18nData['invalid_county'] || 'Please enter county');
                                    }
                                    break;
                                
                                case 'ward':
                                    valid = form_obj.checkEmptyFields(element, value, window.i18nData['invalid_ward'] || 'Please enter ward');
                                    if(!valid){
                                        errorMsg.push(window.i18nData['invalid_ward'] || 'Please enter ward');
                                    }
                                    break;

                                // case 'address1':
                                //     valid = form_obj.checkEmptyFields(this, value, window.i18nData['invalid_address1'] || 'Please enter address line 1.');
                                //     if(!valid){
                                //         errorMsg.push(window.i18nData['invalid_address1'] || 'Please enter address line 1.');
                                //     }
                                //     break;  

                                case 'houseno':                                   
                                    valid = form_obj.checkEmptyFields(element, value, window.i18nData['invalid_houseno'] || 'Please enter house number.');
                                    if(!valid){
                                        errorMsg.push(window.i18nData['invalid_houseno'] || 'Please enter house number.');
                                    }
                                    break;

                                case 'bairro':
                                    valid = form_obj.checkEmptyFields(element, value, window.i18nData['invalid_bairro'] || 'Please enter Bairro.');
                                    if(!valid){
                                        errorMsg.push(window.i18nData['invalid_bairro'] || 'Please enter Bairro.');
                                    }
                                    break;

                                case 'exp_date':
                                    if (value.indexOf("/") != -1) {
                                        var cardExp = value.split('/');
                                        if (cardExp[0].trim() == "") {
                                            errorMsg.push(window.i18nData['invalid_cc_month'] || 'Invalid Card Expiration Month.');
                                            valid = false;
                                        } else if (cardExp[1].trim() == "") {
                                            errorMsg.push(window.i18nData['invalid_cc_year'] || 'Invalid Card Expiration Year.');
                                            valid = false;
                                        } else if (cardExp[1].trim() != ""){
                                            var year = cardExp[1].trim();
                                            var curYear = new Date().getFullYear().toString().substr(2);
                                            if (year.length===1 || year.length === 2 && parseInt(year, 10) < parseInt(curYear)) {
                                                errorMsg.push(window.i18nData['invalid_cc_year'] || 'Invalid Card Expiration Year.');
                                                valid=false;
                                            } else{
                                                valid=true;
                                            }                                           
                                        } else {
                                            valid = true;
                                        }
                                        form_obj.inlineErrorMsg(element,value,valid,window.i18nData['invalid_exp_date'] || 'Invalid Expiration Date.');
                                    } else {
                                        countRequired++;
                                        valid = false;
                                        form_obj.inlineErrorMsg(element,value,valid,window.i18nData['required'] || 'Required Field.');
                                    }

                                    break;

                                case 'cardnumber':
                                    if (value != "") {
                                        if (value.length < 15) {
                                            valid = false;
                                            errorMsg.push(window.i18nData['invalid_cc'] || 'Invalid Credit Card Number.');
                                            form_obj.inlineErrorMsg(element,value,valid,window.i18nData['invalid_cc'] || 'Invalid Credit Card Number.');
                                        } else {
                                            valid = true;
                                        }
                                    } else {
                                        countRequired++;
                                        valid = false;
                                        form_obj.inlineErrorMsg(element,value,valid,window.i18nData['required'] || 'Required Field.');
                                    }
                                    break;
                                case 'address2':
                                    valid = true;
                                    break;
                                case 'cvv':
                                    if (value != "") {
                                        if (value.length < 3) {
                                            valid = false;
                                            errorMsg.push(window.i18nData['invalid_cvv'] || 'Invalid Card Verification Value.');
                                            form_obj.inlineErrorMsg(element,value,valid,window.i18nData['invalid_cvv'] || 'Invalid Card Verification Value.');
                                        } else {
                                            valid = true;
                                        }
                                    } else {
                                        countRequired++;
                                        valid = false;
                                        form_obj.inlineErrorMsg(element,value,valid,window.i18nData['required'] || 'Required Field.');
                                    }

                                    break;
                                case 'checkboxsameaddress':
                                    if (!$(element).is(':checked')) {
                                        //verify the billing address
                                        var billFields = formField['checkboxsameaddressFields'];
                                        for (var key2 in billFields) {
                                            if (billFields.hasOwnProperty(key2)) {
                                                var billElement = billFields[key2];
                                                if (billElement != null) {
                                                    if (key2 == "fnameBill" || key2 == "lnameBill") {
                                                        var regex = /^[\p{L} '-]*$/u;
                                                        var nameVal = $(billElement).val().trim();
                                                        if (nameVal != "") {
                                                            valid = regex.test(nameVal);
                                                            if (!valid) {
                                                                if (key2 == "fnameBill") {
                                                                    $(billElement).removeClass("valid").addClass("error");
                                                                    errorMsg.push(window.i18nData['invalid_billing_fname'] || 'Invalid Billing First Name.');
                                                                    totalErrors++;
                                                                } else if (key2 == "lnameBill") {
                                                                    $(billElement).removeClass("valid").addClass("error");
                                                                    errorMsg.push(window.i18nData['invalid_billing_lname'] || 'Invalid Billing Last Name.');
                                                                    totalErrors++;
                                                                }
                                                            } else {
                                                                if (key2 == "fnameBill") {
                                                                    $(billElement).removeClass("error").addClass("valid");
                                                                } else if (key2 == "lnameBill") {
                                                                    $(billElement).removeClass("error").addClass("valid");
                                                                }
                                                            }
                                                        } else {
                                                            $(billElement).removeClass("valid").addClass("error");
                                                            countRequired++;
                                                            totalErrors++;
                                                            valid = false;
                                                        }
                                                    } else if (key2 != 'address2Bill') {
                                                        if ($(billElement).is(':visible') == true) {
                                                            var valueBill = $(billElement).val().trim();
                                                            if (valueBill != '') {
                                                                $(billElement).removeClass("error").addClass("valid");
                                                            }
                                                            else {
                                                                countRequired++;
                                                                $(billElement).removeClass("valid").addClass("error");
                                                            }
                                                            valid = valueBill != '';
                                                            if (!valid) {
                                                                totalErrors++;
                                                            }
                                                        }
                                                        else {
                                                            valid = true;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    else {
                                        valid = true;
                                    }
                                    break;
                                case 'agreeterms':
                                    if (!element.is(':checked')) {
                                        errorMsg.push(window.i18nData['terms_agree'] || 'Please agree to our terms and conditions.');
                                        form_obj.inlineErrorMsg(element,value,valid,window.i18nData['terms_agree'] || 'Please agree to our terms and conditions.');
                                    }
                                    break;

                              

                                default:
                                    valid = value == '' ? false : true;
                                    if(typeof element.attr('optional')!="undefined"){
                                        valid=true;
                                    }
                                    
                                    form_obj.inlineErrorMsg(element,value,valid,window.i18nData['required'] || 'Required Field.');
                                    break;
                            }
                            if (valid) {
                                $(element).removeClass("error");
                            }
                            else {
                                totalErrors++;
                                $(element).removeClass("valid").addClass("error");
                            }
                        }
                    }
                }
            }

            //require any input/select element marked with the el-required attribute
            $('input[el-required], select[el-required]').each(function () {
                if ($(this).is(':visible')) {
                    var value = $(this).val() ? $(this).val().trim() : "";
                    var valid = value != "";
                    form_obj.inlineErrorMsg(this, value, valid, window.i18nData['required'] || 'Required Field.');
                    if (valid) {
                        $(this).removeClass("error");
                    } else {
                        countRequired++;
                        totalErrors++;
                        $(this).removeClass("valid").addClass("error");
                    }
                }
            });

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

        //add order products 
        form_obj.addOrderProd = function(item){
            var data = form_obj.getDataStorage();

            if(Object.keys(data).length==0 || !data.hasOwnProperty("products")){
                data.products=[];
                data.products.push(item);
                sessionStorage.setItem("offer_"+getCurrentOffer(),JSON.stringify(data));
            }else{
                data.products.push(item);
                sessionStorage.setItem("offer_"+getCurrentOffer(),JSON.stringify(data));
            }
        }

        //remove selected products in sessionStorage.
        form_obj.removeOrderProd=function(params){
            /* var params={type: "main", prodId:11, addOnType=""} */
                var data = form_obj.getDataStorage();
                if (data.hasOwnProperty("products") && data.products.length!=0) {
                    //find the item based on campaign product id and remove it
                     data.products = data.products.filter(function (item) {
                        if(params.hasOwnProperty("type")){
                            if(params.type=="main" || params.type=="add-on"){
                                return item.type!== params.type;
                            }
                        }else if(params.hasOwnProperty("prodId")){
                             return item.prodid !== params.prodId;
                        }else if(params.hasOwnProperty("addOnType")){
                             return item.addOnType!==params.addOnType;
                        }
                    });
                    sessionStorage.setItem('offer_' + getCurrentOffer(), JSON.stringify(data));
                }
        }

        //get data in the offer_{offer_name} in the session storage
        form_obj.getDataStorage= function(){             
             try {
                var data= sessionStorage.getItem("offer_" + getCurrentOffer());
                if(data==null){
                    return {};
                }else{
                    return JSON.parse(data);
                }               

             } catch (error) {
                return {};
             }
        }

        //get specific proper value in the offer_{offer_name} in the session storage
        form_obj.getDataPropertyValue=function(prop){
            var data=form_obj.getDataStorage();
            if(data.hasOwnProperty(prop)){
                return data[prop];
            }else{
                return "";
            }
        }

        //set specific proper value in the offer_{offer_name} in the session storage
        form_obj.setDataPropertyValue=function(prop,value){
            var data=form_obj.getDataStorage();
                data[prop]=value;
                sessionStorage.setItem("offer_"+getCurrentOffer(),JSON.stringify(data));
        }

        form_obj.afterEFClicked = function(){

        }

        form_obj.getAdvOfferId=function(oid){
            return ((sessionStorage.getItem("Adv_transId_"+getCurrentOffer())!=null && sessionStorage.getItem("Adv_transId_"+getCurrentOffer())!="") ? oid : "");
        }

        
        //max wait (ms) for EF.click before falling back to the direct click request
        form_obj.efClickTimeout=8000;

        //resolves with the transaction id of the click fired on this page load ("" if none could be generated)
        form_obj.efClickPromise=null;

        //EF.click through the Everflow SDK. The SDK promise never settles when /sdk/click returns no transaction id,
        //so it is capped by a timeout, and when the SDK is missing (blocked) or fails, /sdk/click is called directly.
        //Never rejects, resolves with the transaction id or "".
        form_obj.efClick=function(options){
            var sdkClick = (typeof EF!="undefined")
                ? form_obj.efPromiseTimeout(EF.click(options), form_obj.efClickTimeout, "EF.click")
                : Promise.reject(new Error("Everflow SDK not loaded"));

            return sdkClick.then(function(transactionId){
                return transactionId || form_obj.efClickRequest(options);
            }, function(err){
                console.warn(err && err.message ? err.message : err);
                return form_obj.efClickRequest(options);
            });
        }

        //same request the SDK makes for EF.click (GET {tracking_domain}/sdk/click?...&async=json)
        form_obj.efClickRequest=function(options){
            if(!options.offer_id && !options.transaction_id){
                console.warn('Everflow click skipped: missing "offer_id"');
                return Promise.resolve("");
            }
            var query={
                _ef_transaction_id: options.transaction_id || "",
                oid: options.offer_id || "",
                affid: options.affiliate_id || "",
                async: "json"
            };
            ["uid","source_id","sub1","sub2","sub3","sub4","sub5","sub6","sub7","sub8","sub9","sub10"].forEach(function(key){
                if(options[key]!==undefined && options[key]!==null && options[key]!==""){
                    query[key]=options[key];
                }
            });

            var request = fetch(options.tracking_domain + "/sdk/click?" + $.param(query), {
                method: "GET",
                credentials: "include"
            }).then(function (response) {
                return response.json();
            }).then(function (resp) {
                return (resp && resp.transaction_id) ? resp.transaction_id : "";
            });

            return form_obj.efPromiseTimeout(request, form_obj.efClickTimeout, "Everflow click request").catch(function (err) {
                console.warn(err && err.message ? err.message : err);
                return "";
            });
        }

        form_obj.triggerEFClick = function(param, retryCount){
            retryCount = retryCount || 0;
            if(typeof EF=="undefined" && retryCount<20){
                //Everflow SDK (scripts/main.js) may still be loading; wait ~6s for it,
                //after that efClick() calls /sdk/click directly so the transaction_id is still generated
                setTimeout(function(){
                    form_obj.triggerEFClick(param, retryCount+1);
                },300);
                return;
            }
            if(!param.transaction_id){
                //direct links may come without oid in the url, fall back to the offer's configured Everflow offer id
                param.oid = param.oid || form_obj.getEFOfferId();

                var ddmClick = function(){
                    return form_obj.efClick({
                        tracking_domain: $("#ddm_tracking_url").val() || "https://www.b04jdmd.com",
                        offer_id: param.oid,
                        affiliate_id: param.affId,
                        sub1: param.sub1,
                        sub2: param.sub2,
                        sub3: param.sub3,
                        sub4: param.sub4,
                        sub5: param.sub5,
                        sub6: param.sub6,
                        sub7: param.sub7,
                        sub8: param.sub8,
                        sub9: param.sub9,
                        sub10: param.sub10,
                        uid: param.uid,
                        source_id: param.source_id,
                        transaction_id: param.transaction_id || ""
                    }).then(function (transactionId) {
                        // transactionId containts the unique Everflow transaction ID
                        form_obj.saveEFTransactionId(param, transactionId);
                        return transactionId;
                    });
                };

                if (getQueryStringByName("affid2")) {
                    form_obj.efClickPromise = form_obj.efClick({
                            tracking_domain: $("#affiliate_tracking_url").val() || "https://www.smartbuy4u.club",
                            offer_id: getQueryStringByName("oid2"),
                            affiliate_id: getQueryStringByName("affid2"),
                            sub1: getQueryStringByName("sub1"),
                            sub2: getQueryStringByName("sub2"),
                            sub3: getQueryStringByName("sub3"),
                            sub4: getQueryStringByName("sub4"),
                            sub5: getQueryStringByName("sub5"),
                            sub6: getQueryStringByName("sub6"),
                            sub7: getQueryStringByName("sub7"),
                            sub8: getQueryStringByName("sub8"),
                            sub9: getQueryStringByName("sub9"),
                            sub10: getQueryStringByName("sub10"),
                            uid: getQueryStringByName("uid"),
                            transaction_id: getQueryStringByName("_ef_transaction_id")
                    }).then(function(transaction_id){
                            sessionStorage.setItem("Adv_offerId_"+getCurrentOffer(), transaction_id ? getQueryStringByName("oid2") : "");
                            sessionStorage.setItem("Adv_transId_"+getCurrentOffer(), transaction_id || "");
                            return ddmClick();
                    });
                } else {
                    form_obj.efClickPromise = ddmClick();
                }
            }

        }

        //store the transaction id generated by EF.click so the server side conversion can use it
        form_obj.saveEFTransactionId=function(param, transactionId){
            if(!transactionId){
                return;
            }
            var data = form_obj.getDataStorage();
            if (Object.keys(data).length==0) {
                data = param; //click finished before the click data was stored
            }
            data.transaction_id = transactionId;
            sessionStorage.setItem("offer_" + getCurrentOffer(), JSON.stringify(data));

            $("#everflow_trans_id").val(transactionId);
            form_obj.afterEFClicked();
        }

        //Everflow offer id: url oid/offer_id first, then the offer id configured for this offer (everflowOfferId)
        form_obj.getEFOfferId=function(){
            return getQueryStringByName("oid") || getQueryStringByName("offer_id") || $("#everFlowOfferId").val() || "";
        }

        //transaction id passed by the tracking link redirect; empty on direct links, where EF.click has to generate it
        form_obj.getEFTransactionIdFromUrl=function(){
            return getQueryStringByName("transaction_id") || getQueryStringByName("_ef_transaction_id");
        }

        form_obj.efClickData=function(){
            var _kn = {
                        affId: getQueryStringByName('affId') || getQueryStringByName('affid'),
                        transaction_id: form_obj.getEFTransactionIdFromUrl(),
                        c1: getQueryStringByName('c1'),
                        c2: getQueryStringByName('c2'),
                        c3: getQueryStringByName('c3'),
                        c4: getQueryStringByName('c4'),
                        c5: getQueryStringByName('c5')
                    }

                    //add everflow tracking parameters thru oid reserve paramater
                    //(falls back to the configured offer id so direct links without oid still get a transaction id)
                    if(form_obj.getEFOfferId()!=""){
                        _kn.oid=form_obj.getEFOfferId();
                       
                        if (getQueryStringByName('sub1') != '') {
                            _kn.sub1 = getQueryStringByName('sub1');
                        }
                        if (getQueryStringByName('sub2') != '') {
                            _kn.sub2 = getQueryStringByName('sub2');
                        }
                        if (getQueryStringByName('sub3') != '') {
                            _kn.sub3 = getQueryStringByName('sub3');
                        }
                        if (getQueryStringByName('sub4') != '') {
                            _kn.sub4 = getQueryStringByName('sub4');
                        }
                        if (getQueryStringByName('sub5') != '') {
                            _kn.sub5 = getQueryStringByName('sub5');
                        }
                        if (getQueryStringByName('sub6') != '') {
                            _kn.sub6 = getQueryStringByName('sub6');
                        }
                        if (getQueryStringByName('sub7') != '') {
                            _kn.sub7 = getQueryStringByName('sub7');
                        }
                        if (getQueryStringByName('sub8') != '') {
                            _kn.sub8 = getQueryStringByName('sub8');
                        }
                        if (getQueryStringByName('sub9') != '') {
                            _kn.sub9 = getQueryStringByName('sub9');
                        }
                        if (getQueryStringByName('sub10') != '') {
                            _kn.sub10 = getQueryStringByName('sub10');
                        }
                        if (getQueryStringByName('uid') != '') {
                            _kn.uid = getQueryStringByName('uid');
                        }
                        if (getQueryStringByName('source_id') != '') {
                            _kn.source_id = getQueryStringByName('source_id');
                        }                        
                    }
                    
                    return _kn;
        }

        form_obj.getKonnektiveAndEFParams=function() {

                if($("#everflow_trans_id").length=="0"){
                   $("body").append("<input type='hidden' id='everflow_trans_id' value=''/>");
                }
        
                if(form_obj.getEFTransactionIdFromUrl()!="" && (getQueryStringByName('affId') != '' || getQueryStringByName('affid') != '')){
                    sessionStorage.setItem("offer_" + getCurrentOffer(), JSON.stringify(form_obj.efClickData()));
                    $("#everflow_trans_id").val(form_obj.getEFTransactionIdFromUrl());
                    return;
                }

                //direct link (no transaction id in the url): EF.click generates the transaction id
                if (getQueryStringByName('affId') != '' || getQueryStringByName('affid') != '') {

                    if(form_obj.getDataPropertyValue("click_ef")!=true || form_obj.getEFTransactionIdFromUrl()==""){
                        form_obj.triggerEFClick(form_obj.efClickData());//trigger everflow click event
                        sessionStorage.setItem("offer_" + getCurrentOffer(), JSON.stringify(form_obj.efClickData()));
                    }        
                   
                }else if(Object.keys(form_obj.getDataStorage()).length==0){
                    sessionStorage.setItem("offer_" + getCurrentOffer(), JSON.stringify({}));
                }else if(form_obj.getDataPropertyValue("transaction_id")=="" && form_obj.getDataPropertyValue("affId")!="" && Object.keys(form_obj.getDataStorage()).length!=0){
                    form_obj.triggerEFClick(form_obj.getDataStorage());//trigger everflow click event
                }
        }       

        form_obj.phoneFormat=function(phone, country) {
            var result = phone;
            if (phone.indexOf('+') == -1 && phone != "" && country != "") {
                //if countryCallingCodes JSON data exists in the states.js
                if (typeof (countryCallingCodes) != "undefined") {
                    var callCode = countryCallingCodes[country];

                    if (phone.substring(0, callCode.length).trim() == callCode) {
                        //if the first digits are equal to the calling code, then just append +
                        result = '+' + phone.trim();
                    }
                    else {
                        //append + and country calling code
                        result = '+' + countryCallingCodes[country] + phone.trim();
                    }
                }
            }
            return result;
        }

        form_obj.purchaseExec=function(data){
            //gituyo ni incase naay pixel ipapafire
        }

        form_obj.tempDataOrder=function(data, callback){
            
            if(sessionStorage.getItem('offer_' + getCurrentOffer())!=null){
                var tempData = JSON.parse(sessionStorage.getItem('offer_' + getCurrentOffer()));
                tempData.firstName = data.firstName;
                tempData.lastName = data.lastName;
                tempData.address1 = data.address1;
                tempData.address2 = data.address2;
                tempData.city = data.city;
                tempData.state = data.state;
                tempData.country = data.country;
                tempData.postalCode = data.postalCode;
                tempData.shipFirstName = data.shipFirstName;
                tempData.shipLastName = data.shipLastName;
                tempData.shipAddress1 = data.shipAddress1;
                tempData.shipAddress2 = data.shipAddress2;
                tempData.shipCity = data.shipCity;
                tempData.shipState = data.shipState;
                tempData.shipCountry = data.shipCountry;
                tempData.shipPostalCode = data.shipPostalCode;
                tempData.emailAddress = data.emailAddress;
                tempData.phoneNumber = data.phoneNumber;
                tempData.billShipSame = data.billShipSame;
                tempData.orderId = data.orderId;
                tempData.customerId = data.customerId;
                tempData.orderId = data.orderId;
                tempData.orderStatus = data.orderStatus;
                tempData.orderType = data.orderType;
                tempData.dateCreated = data.dateCreated;
                tempData.paySource = data.paySource;
                tempData.nextPage = data.nextPage;
                tempData.amountPaid = parseFloat(data.amountPaid) || 0;
                tempData.totalDiscount = parseFloat(data.totalDiscount) || 0;
                tempData.shipTotal = parseFloat(data.shipTotal) || 0;
                tempData.subTotal = parseFloat(data.subTotal) || 0;
                tempData.totalAmount = parseFloat(data.totalAmount) || 0;

                if (tempData != null) {
                    sessionStorage.setItem('offer_' + getCurrentOffer(), JSON.stringify(tempData));
                }
            }
            
            if (typeof callback === 'function') {
                    callback();
            }
        }

        form_obj.initiateLead=function(){
            if($(formField.fname).length==0 && $(formField.lname).length==0){ return; }
              var cartData = form_obj.getDataStorage();
            sessionStorage.removeItem("submittedPartial");
            $(document).on("blur", "input[type='text'], input[type='tel'], input", function () {
                if (($(formField.email).val() != "" || $(formField.phone).val() != "")
                    && $(formField.fname).val() != ""
                    && $(formField.lname).val() != ""
                    && sessionStorage.getItem("submittedPartial") == null) {
        
                        //create a partial                                      
                        form_obj.getResponse({
                                call_type: "leads_import",
                                firstName: $(formField.fname).val().trim(),
                                lastName: $(formField.lname).val().trim(),
                                emailAddress: $(formField.email).val().trim(),
                                phoneNumber:form_obj.phoneIntelli.getNumber().trim(),
                                billShipSame : true,
                                custom1: cartData.transaction_id || $("#everflow_trans_id").val(),
                                affId: cartData.affId,
                                sourceValue1: cartData.c1,
                                sourceValue2: cartData.c2,
                                sourceValue3: cartData.c3,
                                sourceValue4: cartData.c4,
                                sourceValue5: cartData.c5,
                            },function (data) {
                                //success
                                if (data.result.toLowerCase() == "success") {
                                    if(!$(formField.fname).hasClass("error") && 
                                       !$(formField.lname).hasClass("error") && 
                                       !$(formField.email).hasClass("error") &&
                                       !$(formField.phone).hasClass("error") 
                                    ){
                                        sessionStorage.setItem("submittedPartial", true);
                                        form_obj.tempDataOrder(data.message);
                                    } 
                                }
                        },true);
                }

                if(sessionStorage.getItem("submittedPartial")!=null &&
                    sessionStorage.getItem("submittedPartial2")==null &&
                    $(formField.email).val() != "" && 
                    $(formField.phone).val() != "" && 
                    $(formField.fname).val() != "" && 
                    $(formField.lname).val() != "" &&
                    $(formField.address1).val() != "" &&
                    $(formField.city).val() != "" &&
                    $(formField.country).val() != "" &&
                    $(formField.state).val() != "" &&
                    $(formField.zip).val() != "" 
                  ){        
                    //create a partial                                      
                        form_obj.getResponse({
                                call_type: "leads_import",
                                firstName: $(formField.fname).val().trim(),
                                lastName: $(formField.lname).val().trim(),
                                emailAddress: $(formField.email).val().trim(),
                                phoneNumber: form_obj.phoneIntelli.getNumber().trim(),
                                address1: $(formField.address1).val().trim(),
                                address2: (($(formField.address2).length==0)? "" : $(formField.address2).val().trim()),
                                city: $(formField.city).val().trim(),
                                country: $(formField.country).val().trim(),
                                state: (($(formField.state).val()==null)? "" : $(formField.state).val().trim()),
                                postalCode: $(formField.zip).val().trim(),
                                billShipSame : true,
                                custom1: cartData.transaction_id || $("#everflow_trans_id").val(),
                                affId: cartData.affId,
                                sourceValue1: cartData.c1,
                                sourceValue2: cartData.c2,
                                sourceValue3: cartData.c3,
                                sourceValue4: cartData.c4,
                                sourceValue5: cartData.c5,
                            },function (data) {
                                sessionStorage.setItem("submittedPartial2", true);
                            },true);

                }
            });
        }

        form_obj.getCartItems=function(cartData, model){
                            var everFlowAmount = 0;
                            var addOnAmount = 0;
                            $.each(cartData.products, function (i) {
                                    var item = {};
                                    if (this.hasOwnProperty('prodid')) {
                                        item.productId = this.prodid.trim();
                                    }
                                    if (this.hasOwnProperty('variationDetailId')) {
                                        item.variantid = this.variationDetailId.trim();
                                    }
                                
                                    if (this.hasOwnProperty('shipping')) {
                                        item.shippingprice = this.shipping.trim();
                                    }

                                    everFlowAmount += parseFloat(this.price.trim()) * parseInt(this.qty.trim());
                                    if (this.hasOwnProperty('shipping')) {
                                        everFlowAmount += parseFloat(this.shipping.trim()) * parseInt(this.qty.trim());
                                    }
                                    if (this.hasOwnProperty('type')) {
                                        if (this.type.toLowerCase().indexOf("add-on") != -1) {
                                            addOnAmount += parseFloat(this.shipping.trim()) * parseInt(this.qty.trim());
                                            addOnAmount += parseFloat(this.price.trim()) * parseInt(this.qty.trim());
                                        }
                                        item.type=this.type.trim();
                                    }

                                    if (this.hasOwnProperty('amount') && typeof this.amountxqty == "boolean" && this.amountxqty == false) {
                                        item.price = parseFloat(this.amount.trim());
                                    } else if (this.hasOwnProperty('amount')) {
                                        item.price = parseFloat(this.amount.trim()) * parseInt(this.qty.trim());
                                    }

                                    if(this.hasOwnProperty("overrideQty")){
                                        item.overrideQty=this.overrideQty;
                                    }

                                    item.quantity = this.qty.trim();                    
                                    model.products.push(item);
                            });
                            model.addOnTotal=addOnAmount;
                       
                return {
                    model:model,
                    everFlowAmount: everFlowAmount,
                    addOnAmount: addOnAmount,
                    totalAmount: parseFloat(everFlowAmount).toFixed(2)
                };       
        }

        form_obj.submitForm=function(){
            if($(submitBtn).length!=0){
                $(document).on('click', submitBtn, function (e) {

                    form_obj.validateForm(function () {
                        //success
                        form_obj.progressModal();                  
                        $(submitBtn).prop('disabled', true);
                        
                        var cartData = form_obj.getDataStorage();
                        
                        //process import order
                        var model = {
                            call_type: "order_import",
                            token_: $("#token").attr("token"),
                            billingInfo: {},
                            shippingInfo: {
                                emailAddress: $(formField.email).val().trim() || cartData.emailAddress,
                                shipFirstName: $(formField.fname).val().trim() || cartData.shipFirstName,
                                shipLastName: $(formField.lname).val().trim() || cartData.shipLastName,
                                shipAddress1: $(formField.address1).val().trim() || cartData.shipAddress1,
                                shipAddress2: (($(formField.address2).length==0)? "" : $(formField.address2).val().trim() || cartData.shipAddress2),
                                shipCity: $(formField.city).val().trim() || cartData.shipCity,
                                shipCountry: $(formField.country).val().trim() || cartData.shipCountry,
                                shipState: (($(formField.country).val()=="HK")? "": $(formField.state).val().trim() || cartData.shipState),
                                shipPostalCode: $(formField.zip).val().trim() || cartData.postalCode,
                                phoneNumber: form_obj.phoneIntelli.getNumber().trim() || cartData.phoneNumber
                            },
                            products: [],
                            errorRedirectsTo: location.href,  
                            redirectsTo : location.href,
                            orderId: cartData.orderId,
                            custom1: cartData.transaction_id || $("#everflow_trans_id").val(),
                            affId: cartData.affId,
                            sourceValue1: cartData.c1,
                            sourceValue2: cartData.c2,
                            sourceValue3: cartData.c3,
                            sourceValue4: cartData.c4,
                            sourceValue5: cartData.c5,
                            marketingOptIn : ($(formField.marketingoptin).is(':checked') ? 'true' : 'false'),
                            shipProfileId: (($("#ShipProfileId").length == 1 && $("#ShipProfileId").val() != "")? parseInt($("#ShipProfileId").val()) :""),
                            couponCode: cartData.couponCode,
                           
                        };

                        if(getQueryStringByName("testmode")=="1"){
                             model.testmode=getQueryStringByName("testmode");
                        }

                        if(getQueryStringByName("turnstile")!=""){
                             model.turnstile=getQueryStringByName("turnstile");
                        }

                        if($(formField.country).val()=="RO" && $(formField.city).is('[siruta_id]')){
                            model.shippingInfo.shipCity=$(formField.city).attr("siruta_id");
                        }

                        if(getQueryStringByName("lang")!=""){
                             model.lang=getQueryStringByName("lang");
                        }

                        if($(formField.country).val()=="TW" && $(formField.zip).val()==""){
                            model.shippingInfo.shipPostalCode = "000000";
                        }

                        if ($("input[name='cf-turnstile-response']").length!=0) {
                            model.cf_turnstile_token=$("input[name='cf-turnstile-response']").val();
                        }else{
                            model.cf_turnstile_token="no-captcha";
                        }

                        if ($(formField.cpf).length!=0) {
                            model.cpf=$(formField.cpf).val().replace(/[.-]/g, '').trim();
                        }

                        if ($(formField.houseno).length!=0) {
                            model.houseno=$(formField.houseno).val().trim();
                        }

                        if ($(formField.county).length!=0) {
                            model.county=$(formField.county).val().trim();
                        }

                        if ($(formField.consigneeId).length!=0) {
                            model.consigneeId=$(formField.consigneeId).val().trim();
                        }

                        if ($(formField.bairro).length!=0) {
                            model.bairro=$(formField.bairro).val().trim();
                        }

                        if ($(formField.pccc).length!=0) {
                            model.pccc=$(formField.pccc).val().trim();
                        }

                        if ($(formField.barangay).length!=0) {
                            model.barangay=$(formField.barangay).val().trim();
                        }

                        if ($(formField.tax_id).length!=0) {
                            model.tax_id=$(formField.tax_id).val().trim();
                        }

                        if ($(formField.apt_unit).length!=0) {
                            model.apt_unit=$(formField.apt_unit).val().trim();
                        }

                        if ($(formField.neighborhood).length!=0) {
                            model.neighborhood=$(formField.neighborhood).val().trim();
                        }                        

                        if ($(formField.ward).length!=0) {
                            model.ward=$(formField.ward).val().trim();
                        }

                        if($(formField.district).length!=0) {
                            model.district=$(formField.district).val().trim();
                        }

                        if($(formField.village).length!=0) {
                            model.village=$(formField.village).val().trim();
                        }

                        //for indonesia, rt is required for the order import api
                        if ($(formField.rt).length!=0) {
                            model.rt=$(formField.rt).val().trim();
                        }

                        //for indonesia, rw is required for the order import api
                        if ($(formField.rw).length!=0) {
                            model.rw=$(formField.rw).val().trim();
                        }

                        if($("#checkoutPaymentType").length!=0){
                            if($("#checkoutPaymentType").val()=="paypal"){
                                model.call_type= "get_paypalURL_direct_api"; 
                                model.errorRedirectsTo=window.location.href;
                                model.salesUrl=window.location.origin + window.location.pathname;
                                model.redirectsTo=window.location.origin + window.location.pathname;
                            }else if($("#checkoutPaymentType").val()=="klarna"){
                                model.call_type= "order_import_klarna"; 
                            }else if($("#checkoutPaymentType").val()=="credit-card"){
                                model.call_type= "order_import";                  
                                model.cardNumber= $(formField.cardnumber).val().trim();
                                model.cardMonth= ((typeof $(formField.month).val() !="undefined") ? $(formField.month).val().trim() : $(formField.exp_date).val().split('/')[0].trim());
                                model.cardYear= ((typeof $(formField.year).val() !="undefined") ?$(formField.year).val().trim() : $(formField.exp_date).val().split('/')[1].trim());
                                model.cardSecurityCode= $(formField.cvv).val().trim();
                            } else {
                                model.call_type= "order_import_prepaid";
                                model.prepaidType= $("#checkoutPaymentType").val().toUpperCase();
                            }
                        }else{   
                            model.call_type= "order_import";                  
                            model.cardNumber= $(formField.cardnumber).val().trim();
                            model.cardMonth= ((typeof $(formField.month).val() !="undefined") ? $(formField.month).val().trim() : $(formField.exp_date).val().split('/')[0].trim());
                            model.cardYear= ((typeof $(formField.year).val() !="undefined") ?$(formField.year).val().trim() : $(formField.exp_date).val().split('/')[1].trim());
                            model.cardSecurityCode= $(formField.cvv).val().trim();
                        }

                        if ($(formField.checkboxsameaddress).is(':checked') || $(formField.checkboxsameaddress).length==0) {
                            model.billShipSame = true;
                        } else if(!$(formField.checkboxsameaddress).is(':checked')) {
                            
                            var billCountry=$(formField.checkboxsameaddressFields.countryBill).length > 0 ? $(formField.checkboxsameaddressFields.countryBill).val().trim() : $(formField.country).val().trim()
                            var phoneBillNumber=$(formField.checkboxsameaddressFields.phoneBill).length > 0 ? $(formField.checkboxsameaddressFields.phoneBill).val().replace(/\D/g, '').trim() : $(formField.phone).val().replace(/\D/g, '').trim()
                            var phoneBillFormatted=form_obj.phoneFormat(phoneBillNumber,billCountry);
                            //get the billing info from data data
                            model.billingInfo = {
                                 emailAddress: $(formField.email).val().trim() || cartData.emailAddress,
                                 firstName: $(formField.checkboxsameaddressFields.fnameBill).val().trim(),
                                 lastName: $(formField.checkboxsameaddressFields.lnameBill).val().trim(),
                                 address1: $(formField.checkboxsameaddressFields.address1Bill).val().trim(),
                                 city: $(formField.checkboxsameaddressFields.cityBill).val().trim(),
                                 country: billCountry,
                                 state: $(formField.checkboxsameaddressFields.stateBill).val().trim(),
                                 postalCode: $(formField.checkboxsameaddressFields.zipBill).val().trim(),
                                 phonenumber: phoneBillFormatted
                            };
                            model.billShipSame = false;
                        }                       
                       
                        if (cartData != null && cartData.products.length > 0) {
                            
                            var items=form_obj.getCartItems(cartData, model);
                            form_obj.getResponse(items.model, function (data) {
                                //success callback
                                form_obj.nextPage(data, items.addOnAmount); //redirect to next page
                            }, function (data) {
                                //failure callback   
                                form_obj.removeProgressModal();    
                                form_obj.showMsg(data);     
                                $(submitBtn).removeAttr('disabled', false);
                            });

                        } else {
                            form_obj.showMsg(window.i18nData['no_orders'] || "There are no orders.");
                            $(submitBtn).removeAttr('disabled');    
                        }
                    },function (msg) {                           
                        $(submitBtn).removeAttr('disabled', false);
                        form_obj.showMsg(msg);
                          
                                if($("input.error").length!=0){                                  
                                    $("input.error:first").focus();
                                }else if($("select.error").length!=0){
                                    $("select.error:first").focus();
                                }
                    });

                    e.preventDefault();
                });
            }
        }

        form_obj.conversionEverflowPerEvent=function (amount, prodName, paySource) {
            // intentionally blank function
            /* example function content:
             if (prodName.toLowerCase().indexOf('secure') != -1) {
                         CRMIntegrated.conversionEverflow({totalAmount:amount}, 79); //secureship event
                    }
            */
        }

        form_obj.execAdditionalBaseConversion=function (amount) {
            // intentionally blank function
        }

        //Everflow conversion promises still running; nextPage() waits for all of them before redirecting
        form_obj.pendingConversions=[];
        form_obj.currentOrderId="";

        //fires conversionEverflowPerEvent for every item of a successful purchase
        form_obj.firePerEventConversions=function (data) {
            if (!data || !data.message || !data.message.items) {
                return;
            }
            if (data.message.hasOwnProperty("duplicateOrder") && data.message.duplicateOrder) {
                return;
            }
            form_obj.currentOrderId = data.message.orderId;
            for (var i in data.message.items) {
                try {
                    var totalAmount = parseFloat(data.message.items[i].shipping) + parseFloat(data.message.items[i].price);
                    form_obj.conversionEverflowPerEvent(totalAmount.toFixed(2), data.message.items[i].name, data.paySource);
                } catch (err) {
                    //never block the redirect because of a per event conversion error
                    console.warn(err && err.message ? err.message : err);
                }
            }
        }

        //max wait (ms) for the conversion API call before redirecting anyway, so the redirect is never blocked forever
        form_obj.efConversionTimeout=5000;

        form_obj.efPromiseTimeout=function(promise, ms, label){
            return new Promise(function (resolve, reject) {
                var timer=setTimeout(function(){
                    reject(new Error(label+" timed out after "+ms+"ms"));
                }, ms);
                promise.then(function (value) {
                    clearTimeout(timer);
                    resolve(value);
                }, function (err) {
                    clearTimeout(timer);
                    reject(err);
                });
            });
        }

        //Creates the Everflow conversion server side through the Everflow Network API
        //(Create Conversions with Transaction IDs) instead of the EF.conversion() JS SDK.
        //Resolves with the transaction_id, rejects if the conversion could not be created.
        form_obj.fireEverflowConversion=function(params, sessionOrderId){
            return form_obj.waitEFTransactionId(params.transaction_id).then(function (transaction_id) {
                if (!transaction_id) {
                    throw new Error("Everflow conversion skipped: no transaction_id");
                }
                params.transaction_id = transaction_id;
                return form_obj.createEverflowConversion(params, sessionOrderId);
            });
        }

        //resolves with the given transaction id, or when it's empty, with the one from the EF.click
        //still running on this page (direct affiliate link), waiting at most efConversionTimeout
        form_obj.waitEFTransactionId=function(transaction_id){
            if (transaction_id || !form_obj.efClickPromise) {
                return Promise.resolve(transaction_id || form_obj.getDataPropertyValue("transaction_id") || "");
            }
            return form_obj.efPromiseTimeout(form_obj.efClickPromise, form_obj.efConversionTimeout, "EF.click").catch(function (err) {
                console.warn(err && err.message ? err.message : err);
                return form_obj.getDataPropertyValue("transaction_id") || "";
            });
        }

        form_obj.createEverflowConversion=function(params, sessionOrderId){
            var request = fetch(url, {
                method: 'POST',
                keepalive: true, //let the request finish even if the page navigates away
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    call_type: "ef_create_conversion",
                    token_: $("#token").attr("token"),
                    orderId: sessionOrderId,
                    offer_id: params.offer_id,
                    event_id: params.event_id || 0,
                    amount: params.amount,
                    order_id: params.order_id,
                    transaction_id: params.transaction_id
                })
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            }).then(function (resp) {
                if (!resp || resp.result != "SUCCESS") {
                    throw new Error("Everflow conversion failed: " + (resp && resp.message ? resp.message : "unknown error"));
                }
                return params.transaction_id;
            });

            return form_obj.efPromiseTimeout(request, form_obj.efConversionTimeout, "Everflow conversion");
        }

        form_obj.conversionEverflow3ds=function(){
             if (getQueryStringByName("main")=="true" || getQueryStringByName("upsell")=="true") {
                 var amount=0, params={};
                 if(getQueryStringByName("main")=="true"){
                    amount=(parseFloat(getQueryStringByName("amount"))).toFixed(2);
                    params = { 
                        offer_id: getQueryStringByName("oid"),
                        amount: amount,
                        order_id: getQueryStringByName("orderId"),
                        transaction_id: form_obj.getDataPropertyValue("transaction_id"),
                    };
                 }else if(getQueryStringByName("upsell")=="true"){
                    amount= parseFloat(getQueryStringByName("amount")).toFixed(2);
                    params = { 
                        offer_id: $("#everFlowOfferId").val(),
                        amount: amount,
                        order_id: getQueryStringByName("oid"),
                        transaction_id: form_obj.getDataPropertyValue("transaction_id"),
                        event_id: getQueryStringByName("event_id")
                    };
                 }

                form_obj.fireEverflowConversion(params, getQueryStringByName("orderId")).catch(function (err) {
                    console.warn(err && err.message ? err.message : err);
                });
                form_obj.execAdditionalBaseConversion(amount);
             }
        }

        form_obj.conversionEverflow=function(data, event_id){

            if (!data.duplicateOrder) {
                var ef_offer_id = (getQueryStringByName("oid")!="")? getQueryStringByName("oid") : form_obj.getDataPropertyValue("oid");
                var ef_affId    = (getQueryStringByName("affId")!="")? getQueryStringByName("affId") :form_obj.getDataPropertyValue("affId"); 
                var ef_transaction_id = (getQueryStringByName("transaction_id")!="")? getQueryStringByName("transaction_id") :form_obj.getDataPropertyValue("transaction_id");
                    ef_transaction_id = (ef_transaction_id=="")? $("#everflow_trans_id").val() : ef_transaction_id;
                var ef_currency = ($("#prodCurrency").length!=0)? $("#prodCurrency").val(): "USD";
                //per event conversions (e.g. secureship) only pass totalAmount, so fallback to the current order id
                var orderId = data.orderId || form_obj.currentOrderId;
                if (ef_offer_id !="" && ef_affId !="") {
                    var params = {
                        offer_id: ef_offer_id,
                        amount: parseFloat(data.totalAmount),
                        order_id: orderId,
                        transaction_id: ef_transaction_id,
                    };

                    if (typeof event_id != 'undefined') {
                        if (event_id != 0 || event_id != '') {
                            params.event_id = event_id;
                        }
                    } else if ($('#everFlowEventId').length > 0) {
                        params.event_id = $('#everFlowEventId').val();
                    }

                    var conversion = form_obj.fireEverflowConversion(params, orderId).then(function (transaction_id) {
                        if(typeof params.event_id == "undefined") {
                            form_obj.execAdditionalBaseConversion(params.amount);
                            form_obj.purchaseExec(data.purchaseData); // for purchase event
                        }
                        return transaction_id;
                    });
                    //track it para hulaton sa nextPage() before mo-redirect
                    form_obj.pendingConversions.push(conversion.catch(function (err) {
                        console.warn(err && err.message ? err.message : err);
                    }));
                    return conversion;
                } else {
                    return new Promise(function (resolve, reject) {
                        reject();
                    });
                }

            } else {
                return new Promise(function (resolve, reject) {
                    reject();
                });
            }
        }

        form_obj.EFConversionInit=function (data, addAmount, pageType) {
            if(typeof addAmount=="undefined"){
                addAmount=0;
            }
            if(typeof pageType=="undefined"){
                pageType="checkout";
            }

            var efData={};
            if(pageType=="checkout"){
                efData.totalAmount = (parseFloat(data.message.totalAmount) - parseFloat(addAmount)).toFixed(2);
                efData.orderId = data.message.orderId;
            }else{
                var totalAmount=addAmount; //
                efData.totalAmount = totalAmount; //this for upsell page
                efData.orderId = data.message.orderId;
            }
            
            if(data.message.hasOwnProperty("duplicateOrder") && data.message.duplicateOrder){
                efData.duplicateOrder = true;
            } else {
                efData.duplicateOrder = false;
            }
            efData.purchaseData=data;
            // Server may override the Everflow event id (e.g. no-thanks popup downsell accepted).
            var serverEfEventId = (data && data.message && data.message.everflowEventId) ? data.message.everflowEventId : undefined;
            return form_obj.conversionEverflow(efData, serverEfEventId);
        }

        form_obj.nextPage=function(data, addAmount, pageType) {
           
            function redirect(data) {
                if (data.result.toLowerCase() == "success") {                    
                    form_obj.tempDataOrder(data.message, function () {
                        var testmode="";
                        var lang="", custom_config="";
                        if(data.message.hasOwnProperty("testmode")){
                            if(data.message.testmode!="" && data.message.testmode!="null"){
                                testmode="&testmode="+data.message.testmode;
                            }
                        }

                        if(data.message.hasOwnProperty("lang")){
                            if(data.message.lang!="" && data.message.lang!="null"){
                                lang="&lang="+data.message.lang;
                            }
                        }

                        if(data.message.hasOwnProperty("custom_config")){
                            if(data.message.custom_config!="" && data.message.custom_config!="null" && data.message.custom_config!=null){
                                custom_config="&custom_config="+data.message.custom_config;
                            }
                        }
                   
                        if(location.hostname=="127.0.0.1" || location.hostname=="localhost"){
                            var url = location.pathname;
                            // Find the last slash and take everything before it
                            var basePath = url.substring(0, url.lastIndexOf("/"));
                            window.location.href = location.origin + ((getCurrentOffer()=="") ? "" : basePath )+ data.message.nextPage + '?orderId=' + data.message.orderId+testmode+lang+custom_config+"&country_code="+data.message.country_code;
                        } else {
                            window.location.href = location.origin + ((getCurrentOffer()=="") ? "" : "/"+getCurrentOffer() )+ data.message.nextPage + '?orderId=' + data.message.orderId+testmode+lang+custom_config+"&country_code="+data.message.country_code;
                        }
                        
                        
                    });                     
                }
            }
            form_obj.pendingConversions=[];
            var mainConversion = form_obj.EFConversionInit(data, addAmount, pageType).catch(function () {});
            if (data.result.toLowerCase() == "success") {
                form_obj.firePerEventConversions(data); //per event conversions (e.g. secureship)
            }

            //wait for the main and all per event conversions (each one is capped by efConversionTimeout)
            Promise.all([mainConversion].concat(form_obj.pendingConversions)).then(function () {
                redirect(data);
            });//EF conversion
        }

        form_obj.clientGeoLocation = function(callback){
            fetch("https://api.bigdatacloud.net/data/reverse-geocode-client", {
                method: 'GET',
                headers: {}
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
           
            }).then(function (data) {
               if(typeof callback == "function"){
                    callback(data);
               }
            })
        }
        
        form_obj.getResponse=function(model,successCallback,failureCallBack, importLead) {
           
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(model)
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            }).then(function (data) {
                    if (data.result.indexOf('MERC_REDIRECT') > -1) {
                            if (data.message.url != null) {
                                location.href = data.message.url;
                            }
                            else {
                                var code = data.message.script;
                                eval(code);
                            }
                    } 
                    else if(data.result == "ERROR"){                            
                            try {
                                var json = JSON.parse(JSON.stringify(data.message));
                                var msg = "";
                                for (var i in json) {
                                    msg += i+":"+json[i]+" ";
                                }  
                                 
                                if(data.decline){
                                    form_obj.showMsg(window.i18nData['decline']+"<br/> - "+data.message || "Your bank is blocking this payment. Please stay on this browser and <b>RESPOND TO THE BANK SMS OR EMAIL TO COMPLETE THIS ORDER</b>. Please verify your info and try again or use a different payment method.<br/><br/>Reason(s):");
                                }else if(Object.prototype.toString.call(data.message) === '[object Object]'){                                 
                                    form_obj.showMsg(msg);                                                              
                                }else{
                                    form_obj.showMsg(data.message);                                    
                                    if(data.message.toLowerCase().indexOf("taken")!=-1){
                                        form_obj.alreadyTaken=true;
                                    } 
                                }                  
                                
                            } catch (e) {
                                if(Object.prototype.toString.call(data.message) === '[object Object]' && !data.decline){
                                        var json = JSON.parse(JSON.stringify(data.message));
                                        var msg = "";
                                        for (var i in json) {
                                            msg += i+" "+json[i]+" ";
                                        } 
                                        form_obj.showMsg(msg);                                        

                                }else if(data.decline){                                   
                                    form_obj.showMsg(window.i18nData['decline']+"<br/> - "+data.message || "Your bank is blocking this payment. Please stay on this browser and <b>RESPOND TO THE BANK SMS OR EMAIL TO COMPLETE THIS ORDER</b>. Please verify your info and try again or use a different payment method.<br/><br/>Reason(s):");
                                }else{                                    
                                    form_obj.showMsg(data.message);                                    
                                    if(data.message.toLowerCase().indexOf("taken")!=-1){
                                        form_obj.alreadyTaken=true;
                                    }  
                                }                             
                                                            
                            }

                            if (typeof failureCallBack === 'function') {
                                failureCallBack(data);
                            }
                    }else{
                            //successful order
                            if (typeof successCallback === 'function') {
                                    successCallback(data);
                            } 
                                                       
                    }

            }).catch(function (error) {
                if(typeof importLead=="undefined"){
                    form_obj.removeProgressModal();
                }
                 
                if (typeof failureCallBack === 'function') {
                    failureCallBack(error);
                    console.log('Error:', error);
                }
            })           
            
        }  
        
        form_obj.paypalConfirmOrder=function(){
            if(getQueryStringByName("paypalAccept")==1){
                 var cartData = form_obj.getDataStorage();
                 var model = {
                    call_type: "paypal_transaction_confirm",
                    token: getQueryStringByName("token"),
                    payerId: getQueryStringByName("PayerID"),
                    paypalAccept: getQueryStringByName("paypalAccept"),
                    custom1: cartData.transaction_id || $("#everflow_trans_id").val(),
                    affiliateId: cartData.affId,
                    sourceValue1: cartData.c1,
                    sourceValue2: cartData.c2,
                    sourceValue3: cartData.c3,
                    sourceValue4: cartData.c4,
                    sourceValue5: cartData.c5,
                    couponCode: cartData.couponCode                            
                };
                if(getQueryStringByName("ba_token")!=""){
                    model.ba_token=getQueryStringByName("ba_token");
                }
                 form_obj.progressModal(window.i18nData['processing'] || 'Processing request...');
                 form_obj.getResponse(model, function (data) {
                                //success callback
                                form_obj.nextPage(data, parseFloat(data.message.addOnTotal)); //redirect to next page
                }, function (data) {
                                //failure callback 
                        form_obj.removeProgressModal();      
                        form_obj.showMsg(data);
                });
            }
        }
        
         form_obj.paypalTrigger = function () {
            if($(paypalBtn).length!=0){
                $(paypalBtn).unbind().on("click", function (e) {
                        e.preventDefault();
                        form_obj.paymentCustomCallback();// gituyo ni para kun naay add nga code like dle ipagawas ang popup kun mobayad na 
                        form_obj.beginCheckoutExec();

                        var cartData = form_obj.getDataStorage();
                        if (cartData.hasOwnProperty("products")) {
                            if (cartData.products.length > 0) {
                                    form_obj.progressModal(window.i18nData['paypal_processing'] || 'Please wait as we redirect you to PayPal Checkout...');

                                    var model = {
                                        call_type: "get_paypalURL",
                                        token_: $("#token").attr("token"),
                                        products: [],
                                        errorRedirectsTo: window.location.href,
                                        salesUrl:window.location.origin + window.location.pathname,
                                        redirectsTo :window.location.origin + window.location.pathname,
                                        custom1: cartData.transaction_id || $("#everflow_trans_id").val(),
                                        affiliateId: cartData.affId,
                                        sourceValue1: cartData.c1,
                                        sourceValue2: cartData.c2,
                                        sourceValue3: cartData.c3,
                                        sourceValue4: cartData.c4,
                                        sourceValue5: cartData.c5,
                                        testmode: getQueryStringByName("testmode"),
                                        marketingOptIn : ($(formField.marketingoptin).is(':checked') ? 'true' : 'false'),
                                        couponCode: cartData.couponCode
                                    };

                                    if ($("#ShipProfileId").length == 1 && $("#ShipProfileId").val() != "") {
                                            model.shipProfileId = parseInt($("#ShipProfileId").val());
                                    }

                                    if(getQueryStringByName("lang")!=""){
                                        model.lang=getQueryStringByName("lang");
                                    }
                                
                                    form_obj.getResponse(form_obj.getCartItems(cartData, model).model,function(data){
                                        if (data.message && data.message.paypalUrl) {
                                            window.location.href = data.message.paypalUrl;
                                        }
                                    },function(data){
                                        form_obj.removeProgressModal();    
                                        form_obj.showMsg(data);
                                    });
                            } else {
                                form_obj.showMsg(window.i18nData['no_item_in_cart'] || "There are no items on your cart. Please select your order.");
                            }
                        }
                        else {
                            form_obj.showMsg(window.i18nData['no_item_in_cart'] || "There are no items on your cart. Please select your order.");
                        }
                });
            }
        }

        
        //add addon to order by adding to session storage
        form_obj.addAddOnToOrder = function(obj){
            /* obj={el: ".productSelect", callback: function()} */
            if(typeof obj=="object"){
                if(obj.hasOwnProperty("el")){
                    $(document).on("change", obj.el, function(e){
                        e.preventDefault();
                        var item = {
                            'prodid': $(this).attr("data-campaignproductid"),
                            'prodname': $(this).attr("data-productname"),
                            'qty':  $(this).attr("data-quantity"),
                            'shipping': $(this).attr('data-shipping'),
                            'price': $(this).attr('data-unitprice'),
                            'type': "add-on",
                            'addOnType': $(this).attr('data-addontype'),
                        }

                        form_obj.removeOrderProd({"type":"add-on"}); //type : add-on

                        //this is used to override saving data to sessionStorage and order summary
                        if(obj.hasOwnProperty("callback") && typeof obj.callback=="function"){ 
                            obj.callback({
                                item:item,
                                element:obj.el,
                                target: $(this)
                            });
                        }else{   
                            if($(this).is(":checked")){
                                form_obj.addOrderProd(item);
                            }else{
                                form_obj.removeOrderProd({"type":"add-on"}); //type : add-on
                            }                    
                            
                        }
                    });
                }                
            }
        }

        //select product order selection and add it to session storage
        form_obj.selectProdToOrder = function(obj){
            /* obj={el: ".productSelect", callback: function()} */
            if(typeof obj=="object"){
                if(obj.hasOwnProperty("el")){
                    form_obj.prodSelectElement=obj.el;

                    $(document).on("click", obj.el, function(e){
                        e.preventDefault();
                        $(obj.el).removeClass("active");
                        $(this).addClass("active");
                        
                       
                        var item = {
                            'prodid': $(this).attr("data-campaignproductid"),
                            'prodname': $(this).attr("data-productname"),
                            'qty':  $(this).attr("data-quantity"),                           
                            'shipping': $(this).attr('data-shipping'),
                            'price': $(this).attr('data-unitprice'),
                            'productPrice': $(this).attr('data-productprice'),
                            'origPrice': $(this).attr('data-originalprice'),
                            'discountPrice': $(this).attr('data-discountedprice'),
                            'discountPerc': $(this).attr('data-discountperc'),
                            'additionalDiscount': (typeof $(this).attr('discountofftext') =="undefined") ? null : $(this).attr('discountofftext'),
                            'type': 'main'
                        }

                        if( typeof $(this).attr("data-overrideQuantity")!="undefined"){
                            item.overrideQty=$(this).attr("data-overrideQuantity");
                        }

                        form_obj.removeOrderProd({"type":"main"}); //type : main

                        //this is used to override saving data to sessionStorage and order summary
                        if(obj.hasOwnProperty("callback") && typeof obj.callback=="function"){ 
                            obj.callback({
                                item:item,
                                element:obj.el,
                                target: $(this)
                            });
                        }else{                           
                            form_obj.addOrderProd(item);
                        }
                    });
                }                
            }
        }

        //select product that was already selected
        form_obj.initSelectedProd = function(){            
                //click selected main product              
                if(form_obj.getDataPropertyValue("products").length!=0){                    
                        var selectedProd = form_obj.getDataPropertyValue("products").filter(function(item){
                                return item.type=="main";
                        })[0];
                                      
                        if(selectedProd.hasOwnProperty("prodid")){                                                            
                            $(form_obj.prodSelectElement+"[data-campaignproductid="+selectedProd.prodid+"]").trigger("click");
                        }else{
                            $(form_obj.prodSelectElement+".active").trigger("click");
                        }
                        form_obj.initActivatedCoupon(); //activate applied coupon 
                }else{
                        $(form_obj.prodSelectElement+".active").trigger("click");                        
                }
        }
        

        form_obj.isUpsellPage=function() {
            if (getQueryStringByName('gtmetrix').toLowerCase() == 'true') {
                //bypass if gmetrix test
                return true;
            }
            else {
                return ($('.yes-upsell-link').length > 0 || $('.no-upsell-relink').length > 0);
            }
        }

        form_obj.isThankyou=function() {
            return location.pathname.toLowerCase().indexOf("thankyou.php")!=-1;
        }

        form_obj.noUpsellLink=function(callback){         
            $(document).on("click", ".no-upsell-link", function(e){
                e.preventDefault();  
                form_obj.progressModal(window.i18nData['redirect_to_next'] || "Redirecting to the next page.");
               
                if(!$(this).hasClass("hasClicked_")){
                    $(this).addClass("hasClicked_");
                    var el=$(this);
                    form_obj.getResponse({
                        call_type: "no_upsell",
                        page: location.pathname.match(/\/([^/]+)\.php/)[1]
                    }, function (data) {
                        if(typeof callback=="function"){ 
                            callback(data);
                            el.removeClass("hasClicked_");
                        }else{                            
                            el.removeClass("hasClicked_");
                            var testmode="";
                            if(getQueryStringByName("testmode")=="1"){
                                testmode="&testmode="+getQueryStringByName("testmode");
                            }
                            var lang="";
                            if(getQueryStringByName("lang")!=""){
                                lang="&lang="+getQueryStringByName("lang");
                            } else if(data.message.hasOwnProperty("lang")){
                                if(data.message.lang!="" && data.message.lang!="null"){
                                    lang="&lang="+data.message.lang;
                                }
                            }
                            
                            if(getQueryStringByName("custom_config")!=""){
                                lang+="&custom_config="+getQueryStringByName("custom_config");                            
                            }

                            if(location.hostname=="127.0.0.1" || location.hostname=="localhost"){
                                var url = location.pathname;
                                // Find the last slash and take everything before it
                                var basePath = url.substring(0, url.lastIndexOf("/"));
                                location.href=location.origin + ((getCurrentOffer()=="") ? "" : basePath) + data.message.nextPage + '?orderId=' + data.message.orderId+testmode+lang+"&country_code="+data.message.country_code;
                            } else {
                                location.href=location.origin + ((getCurrentOffer()=="") ? "" : "/"+getCurrentOffer()) + data.message.nextPage + '?orderId=' + data.message.orderId+testmode+lang+"&country_code="+data.message.country_code;
                                //form_obj.removeProgressModal();
                            }

                            
                        }
                    },function(data){
                         form_obj.removeProgressModal();
                         el.removeClass("hasClicked_");
                    });  
                }                              
            });              
        }

        form_obj.cartItemTotalUpsell=function(){
              
                var item = {}; //if the upsell can have many products the customer can select, just loop them and push to mode.products array
                var totalAmount=0;
                $("input[type='hidden']._product_order").each(function () {  
                     item.quantity = $(this).attr("quantity");                  
                    //add price to override price
                    if($(this).is('[price]')){
                        item.price = $(this).attr('price');
                        totalAmount += parseFloat(item.price) * parseInt(item.quantity);
                    }else{                        
                         totalAmount += parseFloat($(this).attr('unitprice')) * parseInt(item.quantity);
                    }

                    //add shipping to override shipping
                    if($(this).is('[shippingprice]')){
                        item.shippingprice = $(this).attr('shippingprice');
                        totalAmount += parseFloat(item.shippingprice) * parseInt(item.quantity);
                    }else{                        
                        totalAmount += parseFloat($(this).attr('shipping')) * parseInt(item.quantity);
                    }                
                });
                return parseFloat(totalAmount).toFixed(2);
        }

        form_obj.cartItemUpsell=function(model){
              
                var item = {}; //if the upsell can have many products the customer can select, just loop them and push to mode.products array
                var totalAmount=0;
                $("input[type='hidden']._product_order").each(function () {
                    item.productId = $(this).val();
                    item.quantity = $(this).attr("quantity");

                    //add attribute variantId for product variants
                    if($(this).is('[variantId]')){
                        item.variantid = $(this).attr('variantId');
                    }
                    //add price to override price
                    if($(this).is('[price]')){
                        item.price = $(this).attr('price');
                        totalAmount += parseFloat(item.price) * parseInt(item.quantity);
                    }else{                        
                         totalAmount += parseFloat($(this).attr('unitprice')) * parseInt(item.quantity);
                    }


                    //add shipping to override shipping
                    if($(this).is('[shippingprice]')){
                        item.shippingprice = $(this).attr('shippingprice');
                        totalAmount += parseFloat(item.shippingprice) * parseInt(item.quantity);
                    }else{                        
                        totalAmount += parseFloat($(this).attr('shipping')) * parseInt(item.quantity);
                    }
                     model.products.push(item);                 
                });
                model.totalAmount = parseFloat(totalAmount).toFixed(2);
                return model;
        
        }

        form_obj.createStripeBtn=function(){
            var paySource=$("#paySource").attr("paySource");
            if(paySource=="LINK"){
                // Stripe Link has no badge image; render its wordmark on the Link brand color
                $(".yes-upsell-link").each(function() {
                        $(this).html('<span id="stripeBtnIcon" style="color:#011E0F;font-size:20px;">Pay with <b>link</b></span>').addClass("replacedBtn").attr("style","min-width:261px;padding:12px 5px!important;background:#00D66F!important;border-radius:4px!important;border-bottom:none!important;box-shadow: none!important;display:flex;justify-content:center;");
                });
            }else if(paySource=="GOOGLEPAY" || paySource=="APPLEPAY" || paySource=="STRIPE_KLARNA"){

                $(".yes-upsell-link").each(function() {
                        var image='<img id="stripeBtnIcon" src="'+commonFilesPath()+'src/common/images/'+paySource.toLowerCase()+'.png" style="width:136px!important; height: auto;"/>';
                        $(this).html(image).addClass("replacedBtn").attr("style","min-width:261px;padding:15px 5px!important;background:black!important;border-radius:4px!important;border-bottom:none!important;box-shadow: none!important;display:flex;justify-content:center;");
                });
               
            }
        }


        form_obj.yesUpsellLink=function(callback){
            form_obj.createStripeBtn();  
            $(document).on("click", ".yes-upsell-link", function(e){
                e.preventDefault();  
               form_obj.progressModal(window.i18nData['processing'] || "Processing request...");
               
                if(!$(this).hasClass("hasClicked_")){
                    $(this).addClass("hasClicked_");
                    var el=$(this);

                    var model = {
                        call_type: "yes_upsell",
                        token_: $("#token").attr("token"),
                        page: location.pathname.match(/\/([^/]+)\.php/)[1],                        
                        products:[]
                    } 
                    
                
                    if($("#paySource").attr("paySource")=="GOOGLEPAY" || $("#paySource").attr("paySource")=="APPLEPAY" || $("#paySource").attr("paySource")=="LINK"){
                        model.call_type= "stripe_express_order_import_upsell";
                        model.amount=form_obj.cartItemTotalUpsell();
                        model.withDecimal=form_obj.checkWithDecimal(parseFloat(form_obj.cartItemTotalUpsell()));
                        model.currency = $("#prodCurrency").val();  
                        model.returnurl = location.href;                                     
                    // }else if($("#paySource").attr("paySource")=="PREPAID"){  
                    //     //FOR prepaid paymen like: KLARNA                         
                    //     model.call_type= "prepaid_order_import_upsell";                  
                    //     model.errorRedirectsTo = location.href;
                    //     model.redirectsTo = location.href;                                   
                    }else{
                        model.errorRedirectsTo = location.href;
                        model.salesUrl = location.href;
                        model.redirectsTo = location.href;
                    }    

                   
                    if(getQueryStringByName("testmode")==1){
                        model.testmode=getQueryStringByName("testmode");
                    }

                    if(getQueryStringByName("lang")!=""){
                        model.lang=getQueryStringByName("lang");
                    }


                    var items=form_obj.cartItemUpsell(model);                    
                    form_obj.getResponse(items, function (data) {
                        if(typeof callback=="function"){ 
                            callback(data);
                            el.removeClass("hasClicked_");
                        }else{
                            //form_obj.removeProgressModal();
                            el.removeClass("hasClicked_");

                            //success callback credit card and paypal enabled reference transaction
                            var price=parseFloat($("#prod_id").attr("unitprice")) * parseInt($("#prod_id").attr("quantity"));
                            var shipping=parseFloat($("#prod_id").attr("shipping")) * parseInt($("#prod_id").attr("quantity"));
                            var upsaleAmount = (price + shipping).toFixed(2); 
                            
                            form_obj.nextPage(data, upsaleAmount, "upsell"); //redirect to next page
                        
                        }
                    },function(data){                       
                         form_obj.removeProgressModal();
                         el.removeClass("hasClicked_"); 
                    });  
                }          
            });
        }

        form_obj.exitPopup=function(){
             //show popup
             var setTimeoutExitpop;
             if(form_obj.withCouponPopup && !$(form_obj.elCouponPopupId).hasClass("show_popup") && getQueryStringByName("gtmetrix")==""){
                    setTimeoutExitpop=setTimeout(function(){
                        $(form_obj.elCouponPopupId).addClass("show_popup");
                        form_obj.withCouponPopup=false;
                        if(typeof form_obj.couponPopupCallback == "function"){
                        
                            form_obj.couponPopupCallback($(form_obj.elCouponPopupId));
                        }
                    },form_obj.couponPopupShowinSec);
             }

             //exit popup
             $(document).on('mouseleave', function(){                
                if(form_obj.withCouponPopup && !$(form_obj.elCouponPopupId).hasClass("show_popup")  && getQueryStringByName("gtmetrix")==""){       
                       
                    $(form_obj.elCouponPopupId).addClass("show_popup");
                    form_obj.withCouponPopup=false;
                    if(typeof form_obj.couponPopupCallback == "function"){
                    
                        form_obj.couponPopupCallback($(form_obj.elCouponPopupId));
                    }
                    clearTimeout(setTimeoutExitpop);
                }                   
             });
            
        }

        form_obj.closeExitPop=function(obj){
            /* obj={el: ".productSelect", callback: function()} */
            if(typeof obj=="object"){
                if(obj.hasOwnProperty("el")){
                    $(document).on("click",obj.el, function(){
                        var el= $(this);
                       
                        if(obj.hasOwnProperty("callback") && typeof obj.callback=="function"){ 
                            obj.callback(el);
                        }
                        $(form_obj.elCouponPopupId).remove();
                    });
                }
            }            
        }

        //execute couponCode selected if already applied
        form_obj.initActivatedCoupon = function(){            
                //click selected main product              
                if(form_obj.getDataPropertyValue("couponCode")!=""){ 
                        var couponData = {
                            el: form_obj.getDataPropertyValue("activationBtnElement")
                        }
                        $(couponData.el).trigger("click");//execute activation
                }
        }

        //function reponsible to populate discounted amount to the page
        form_obj.applyCouponToPage = function(item_, callback){
                //default
                if(typeof callback == "function"){
                        //kun mag override;
                        callback(item_);
                }else{
                        $(form_obj.prodSelectElement + "[data-campaignproductid]").each(function() {
                                    if(item_.targetProductIds.indexOf($(this).attr("data-campaignproductid"))!=-1){
                                            var qty = parseInt($(this).attr('data-quantity'));
                                            var actualPrice = parseFloat($(this).attr('data-unitprice')) * qty;
                                            var originalPrice = parseFloat($(this).attr('data-originalprice'));
                                            var discountOff = (actualPrice * item_.percent);
                                            var deductedPrice = actualPrice - discountOff;
                                            var perUnitPrice = deductedPrice / qty;
                                            var discountAppliedPercentage = [(originalPrice - deductedPrice) / originalPrice] * 100;
                                            
                                            $(this).attr('data-unitprice', perUnitPrice.toFixed(2));
                                            $(this).attr("discountOffText", item_.discountOffText);
                                            $(this).attr("discountOffLabel", " Off plus additional " + item_.discountOff + "% Off is Applied!");
                                            $(this).find(".js-discount-price").html("$" + deductedPrice.toFixed(2));
                                            $(this).find(".extended-text").html(item_.discountOffText);
                                            $(this).find(".discountAppliedPercentage").html(Math.round(discountAppliedPercentage)+"%"); //
                                    }                                
                        });
                        $(form_obj.prodSelectElement + "[data-campaignproductid].active").trigger("click");
                        $(form_obj.elCouponPopupId).remove();//exit popup
                }
        }


        //activate coupon
        form_obj.activateCoupon=function(obj){
            /* obj={el: ".productSelect", couponCode:SAVE10, discountPercentage: 10, discountAmount:10, targetProductIds:12, callback: function()} */
            if(typeof obj=="object"){                    

                    if(obj.hasOwnProperty("el") && obj.el!="" && obj.hasOwnProperty("couponCode") && obj.couponCode!=""){
                                                                       
                            $(document).on("click", obj.el, function(e){
                                    e.preventDefault();
                                    var item={};
                                            form_obj.setDataPropertyValue("couponCode",obj.couponCode || "");//save couponCode to sessionStorage
                                            form_obj.setDataPropertyValue("targetProductIds",obj.targetProductIds || "");//save target ProductIds to sessionStorage
                                            form_obj.setDataPropertyValue("activationBtnElement",obj.el || "");//save target ProductIds to sessionStorage
                                            item.targetProductIds=obj.targetProductIds;  
                                            
                                    if(obj.hasOwnProperty("discountPercentage")){
                                            form_obj.setDataPropertyValue("couponDiscountPercentage",(obj.discountPercentage) || 0);  
                                            item.percent = obj.discountPercentage;
                                            item.discountOff = obj.discountPercentage * 100;
                                            item.discountOffText = "+ "+item.discountOff+"% OFF"; 
                                    }else{
                                            form_obj.setDataPropertyValue("couponDiscountAmount",obj.discountAmount || 0);                           
                                    }

                                    if(typeof obj.callback == "function"){
                                        //kun mag override;
                                        obj.callback(item);
                                    }else{
                                        form_obj.applyCouponToPage(item);
                                    }
                            });
                    }            
            }
        }

        form_obj.convertDecimal= function(amount) {
            var zeroDecimalCurrencies = new Set([
                'BIF','CLP','DJF','GNF','JPY','KMF',
                'KRW','MGA','PYG','RWF','UGX',
                'VND','VUV','XAF','XOF','XPF'
            ]);

            if (zeroDecimalCurrencies.has($("#prodCurrency").val().toUpperCase())) {
                return Math.round(amount);
            }

            return Math.round(amount * 100);
        }

        form_obj.checkWithDecimal = function(value){
            return (Number.isFinite(value) && !Number.isInteger(value));
        }

        form_obj.getStripeAmount =function(){
                var stripeLineItems = [];//line items to display product price, name, discounts, shipping price to the customer during wallet pop-up 
                    var options = {
                        emailRequired: true,
                        phoneNumberRequired: true,
                        shippingAddressRequired: true,
                        allowedShippingCountries: [],
                        shippingRates: [],
                        lineItems: []
                    };

                if($("#paySource").length==0){ //if zero you are not in the upsell page
                        var cartData = form_obj.getDataStorage();
                        var shippingPrice = 0, mainTotal = 0, shippingRate = {};
                        if (cartData.products.length > 0) {
                            $.each(cartData.products, function (i) {
                                    if (this.hasOwnProperty('shipping')) {
                                        shippingPrice += form_obj.convertDecimal(parseFloat(this.shipping) * parseInt(this.qty));//convert to cents
                                    }
                                    mainTotal += form_obj.convertDecimal(parseFloat(this.price) * parseInt(this.qty));//convert to cents

                                    stripeLineItems.push({
                                        name: this.prodname,
                                        amount: Math.round(form_obj.convertDecimal(parseFloat(this.price) * parseInt(this.qty))) //Stripe requires the amount in cents
                                    });
                                });

                                if (shippingPrice > 0) {
                                    shippingPrice = Math.round(shippingPrice);//round off to nearest whole number   
                                    shippingRate = {
                                        id: 'standard-shipping',
                                        displayName: 'Standard Shipping',
                                        amount: shippingPrice,  // amount in cents
                                        deliveryEstimate: {
                                            maximum: { unit: 'day', value: 7 },
                                            minimum: { unit: 'day', value: 5 }
                                        }
                                    };
                                }
                                else {
                                    shippingRate = {
                                        id: 'free-shipping',
                                        displayName: 'Free Shipping',
                                        amount: 0,  // amount in cents
                                        deliveryEstimate: {
                                            maximum: { unit: 'day', value: 7 },
                                            minimum: { unit: 'day', value: 5 }
                                        }
                                    };
                                }

                                stripeLineItems.push({
                                    name: shippingRate.displayName,
                                    amount: shippingRate.amount
                                });

                                // Add the new shipping rate
                                options.shippingRates.push(shippingRate);
                                options.lineItems = options.lineItems.concat(stripeLineItems);
                                var hiddenValue = $('#stripeCountries').val().split(",")
                        
                                // Add the countries to allowedShippingCountries
                                options.allowedShippingCountries = options.allowedShippingCountries.concat(hiddenValue);
                                options.setupFutureUsage = 'off_session';
                                return {
                                    amount: (mainTotal + shippingRate.amount),
                                    options: options
                                }
                        }else{
                            return {
                                amount: 0,
                                options: options
                            }
                        }
            }else{
                var model = { products:[]};
                var item = {}; //if the upsell can have many products the customer can select, just loop them and push to mode.products array
                var totalAmount=0;                
                $("input[type='hidden']._product_order").each(function () {
                    item.productId = $(this).val();
                    item.quantity = $(this).attr("quantity");

                    //add attribute variantId for product variants
                    if($(this).is('[variantId]')){
                        item.variantid = $(this).attr('variantId');
                    }
                    //add price to override price
                    if($(this).is('[price]')){
                        item.price = $(this).attr('price');
                        totalAmount += parseFloat(item.price) * parseInt(item.quantity);
                    }else{                        
                         totalAmount += parseFloat($(this).attr('unitprice')) * parseInt(item.quantity);
                    }


                    //add shipping to override shipping
                    if($(this).is('[shippingprice]')){
                        item.shippingprice = $(this).attr('shippingprice');
                        totalAmount += parseFloat(item.shippingprice) * parseInt(item.quantity);
                    }else{                        
                        totalAmount += parseFloat($(this).attr('shipping')) * parseInt(item.quantity);
                    }

                    stripeLineItems.push({
                          name:  $(this).attr('prodname'),
                          amount: Math.round(form_obj.convertDecimal(totalAmount)) //Stripe requires the amount in cents
                    });
                    

                    model.products.push(item);                 
                });
                if (shippingPrice > 0) {
                                    shippingPrice = Math.round(shippingPrice);//round off to nearest whole number   
                                    shippingRate = {
                                        id: 'standard-shipping',
                                        displayName: 'Standard Shipping',
                                        amount: shippingPrice,  // amount in cents
                                        deliveryEstimate: {
                                            maximum: { unit: 'day', value: 7 },
                                            minimum: { unit: 'day', value: 5 }
                                        }
                                    };
                                }
                else {
                            shippingRate = {
                                        id: 'free-shipping',
                                        displayName: 'Free Shipping',
                                        amount: 0,  // amount in cents
                                        deliveryEstimate: {
                                            maximum: { unit: 'day', value: 7 },
                                            minimum: { unit: 'day', value: 5 }
                                        }
                                    };
                            }

                stripeLineItems.push({
                            name: shippingRate.displayName,
                            amount: shippingRate.amount
                });

                // Add the new shipping rate
                options.shippingRates.push(shippingRate);
                options.lineItems = options.lineItems.concat(stripeLineItems);
                var hiddenValue = $('#stripeCountries').val().split(",")
                        
                // Add the countries to allowedShippingCountries
                options.allowedShippingCountries = options.allowedShippingCountries.concat(hiddenValue);
                options.setupFutureUsage = 'off_session';

                model.totalAmount = parseFloat(totalAmount).toFixed(2);

                return {
                    amount:model.totalAmount,
                    options: options
                }

            }
        }  

        //var elements;       
        form_obj.stripeWalletButton = function(){
            if(typeof Stripe !="undefined" && $("#stripePaymentWallet").length!=0){                  
                form_obj.mountStripeWalletButton(); //mount buttons                
            }else if($("#stripePaymentWallet").length!=0){
                setTimeout(function(){
                    console.log("Stripe not yet loaded..");
                    form_obj.stripeWalletButton();
                },300)
            }
        }

        const expressButtons = [];
        // Shared error handler
        form_obj.handleError = function(error){
            form_obj.showMsg(error.message);
        };

        form_obj.attachSharedWalletEvents=function(expressCheckoutElement, elements, stripe) {
                expressCheckoutElement.on("click", function(event){
                     const getOptions=form_obj.getStripeAmount();
                    
                    if (getOptions.amount!=0) {   
                        if($("#paySource").length!=0){
                            elements.update({ amount: getOptions.amount *100 });
                        }else{
                            elements.update({ amount: getOptions.amount });
                        } 
                    } else {
                        form_obj.showMsg(window.i18nData['no_item_in_cart'] || "There are no items on your cart. Please select your order.");
                    }

                    form_obj.paymentCustomCallback();// gituyo ni para kun naay add nga code like dle ipagawas ang popup kun mobayad na 
                    form_obj.beginCheckoutExec();
                    event.resolve(getOptions.options);  
                });

                expressCheckoutElement.on("cancel", () => {
                    const getOptions = form_obj.getStripeAmount();
                      if($("#paySource").length!=0){
                            elements.update({ amount: getOptions.amount *100 });
                        }else{
                            elements.update({ amount: getOptions.amount });
                        } 
                
                });

                expressCheckoutElement.on("confirm", async (event) => {
                try {
                    // 1. Validate elements (still valid)
                    const { error: submitError } = await elements.submit();
                    if (submitError) {
                        form_obj.handleError(submitError);
                        return;
                    }

                    // 🔑 Create a PaymentMethod
                    const { error: pmError, paymentMethod } = await stripe.createPaymentMethod({
                        elements
                    });

                    if (pmError || !(paymentMethod && paymentMethod.id)) {
                        form_obj.handleError({
                            message: window.i18nData['paymentMethod_not_return'] || "PaymentMethod ID was not returned. Please try again later."
                        });
                        return;
                    }

                    const cartData = form_obj.getDataStorage();
                    const paymentMethodId = paymentMethod.id; 
                    // 2. Extract what we can from event (NEW)
                    const billing = event.billingDetails || {};
                    const shipping = event.shippingAddress || {};

                    // Parse names safely
                    const billName = (billing.name || "").trim();
                    const billParts = billName.split(" ");
                    const billLastName = billParts.length > 1 ? billParts.pop() : "";
                    const billFirstName = billParts.join(" ") || billName;

                    const shipName = (shipping.name || "").trim();
                    const shipParts = shipName.split(" ");
                    const shipLastName = shipParts.length > 1 ? shipParts.pop() : "";
                    const shipFirstName = shipParts.join(" ") || shipName;

                    // 3. Build your model EARLY (before payment)
                    let model = {
                        call_type: ($("#paySource").length != 0)
                            ? "stripe_express_order_import_upsell"
                            : "stripe_express_order_import",

                        token_: $("#token").attr("token"),
                        billShipSame: false,

                        billingInfo: {
                            emailAddress: billing.email || "",
                            firstName: billFirstName,
                            lastName: billLastName,
                            address1: (billing.address && billing.address.line1) || "",
                            address2: (billing.address && billing.address.line2) || "",
                            city: (billing.address && billing.address.city) || "",
                            country: (billing.address && billing.address.country) || "",
                            state: (billing.address && billing.address.state) || "",
                            postalCode: (billing.address && billing.address.postal_code) || "",
                            phonenumber: billing.phone || ""
                        },

                        shippingInfo: {
                            emailAddress: billing.email || "",
                            shipFirstName: shipFirstName || ".",
                            shipLastName: shipLastName || ".",
                            shipAddress1: (shipping.address && shipping.address.line1) || ".",
                            shipAddress2: (shipping.address && shipping.address.line2) || ".",
                            shipCity: (shipping.address && shipping.address.city) || ".",
                            shipCountry: (shipping.address && shipping.address.country) || ".",
                            shipState: (shipping.address && shipping.address.state) || ".",
                            shipPostalCode: (shipping.address && shipping.address.postal_code) || ".",
                            phoneNumber: shipping.phone || billing.phone || ""
                        },

                        products: [],
                        custom1: cartData.transaction_id || $("#everflow_trans_id").val(),
                        affId: cartData.affId || '',
                        sourceValue1: cartData.c1 || '',
                        sourceValue2: cartData.c2 || '',
                        sourceValue3: cartData.c3 || '',
                        sourceValue4: cartData.c4 || '',
                        sourceValue5: cartData.c5 || '',
                        marketingOptIn: ($(formField.marketingoptin).is(':checked') ? 'true' : 'false'),
                        shipProfileId: ($("#ShipProfileId").val() || ""),
                        couponCode: cartData.couponCode || '',
                        currency: $("#prodCurrency").val() || 'usd',
                        paymentType: event.expressPaymentType,
                        shippingName: shipping.name || "",
                        return_url: location.href,
                        paymentMethodId: paymentMethodId,
                        metadata: {
                                order_ref: cartData.transaction_id || $("#everflow_trans_id").val(),
                            }
                    };

                    if(getQueryStringByName("testmode")=="1"){
                        model.testmode=getQueryStringByName("testmode");
                    }

                    if(getQueryStringByName("lang")!=""){
                        model.lang=getQueryStringByName("lang");
                    }

                    if(model.shippingInfo.shipCountry=="BR" ||
                        model.shippingInfo.shipCountry=="TW" || 
                        model.shippingInfo.shipCountry=="KR" || 
                        model.shippingInfo.phoneNumber==null || 
                        model.shippingInfo.phoneNumber==""){

                        form_obj.walletReqFieldsDialog(model, cartData);
                    }else{
                        form_obj.submitWalletOrder(model, cartData);
                    }
                } catch (err) {
                    form_obj.handleError(err);
                }
            });
        }

        form_obj.submitWalletOrder=function(model,cartData){
                        if($("#paySource").length==0){//not an upsell page
                                        if (cartData != null && cartData.products.length > 0) {
                                                var items=form_obj.getCartItems(cartData, model);
                                                model.amount = parseFloat(items.totalAmount);
                                                model.withDecimal = form_obj.checkWithDecimal(parseFloat(items.totalAmount));
                                                form_obj.progressModal(window.i18nData['processing'] || "Processing request...");
                                                form_obj.getResponse(items.model, function (data) {
                                                    //success callback
                                                    form_obj.nextPage(data, items.addOnAmount); //redirect to next page
                                                }, function (data) {
                                                    //failure callback   
                                                    form_obj.removeProgressModal();    
                                                    form_obj.showMsg(data);  
                                                });   
                                        } else {
                                            form_obj.showMsg(window.i18nData['no_item_in_cart'] || "There are no items on your cart. Please select your order.");
                                        }
                                }else{
                                         model.products=[];
                                        var items=form_obj.cartItemUpsell(model); 
                                        items.amount = items.totalAmount;  
                                        items.withDecimal = form_obj.checkWithDecimal(parseFloat(items.totalAmount));
                                        model.page= location.pathname.match(/\/([^/]+)\.php/)[1];  
                                        form_obj.progressModal(window.i18nData['processing'] || "Processing request...");          
                                        form_obj.getResponse(items, function (data) {                                           
                                                //success callback credit card and paypal enabled reference transaction
                                                var price=parseFloat($("#prod_id").attr("unitprice")) * parseInt($("#prod_id").attr("quantity"));
                                                var shipping=parseFloat($("#prod_id").attr("shipping")) * parseInt($("#prod_id").attr("quantity"));
                                                var upsaleAmount = (price + shipping).toFixed(2); 
                                                
                                                form_obj.nextPage(data, upsaleAmount, "upsell"); //redirect to next page
                                        },function(data){                       
                                            form_obj.removeProgressModal();
                                        });
                    }
        }       

        form_obj.walletSubmit=function(model, cartData){              

             $("#wallet-submit").on("click",function(){        
                    var valid = true;
                    var totalErrors = 0;
                    $('#required-wallet-phone input').each(function(index, element) {
                        if ($(this).is(':visible') == true) {
                            var value = $(this).val().trim() || "";
                            var el = $(this);
                            if(el.attr("id")=="wallet_consignee_id"){
                                if(value=="" || value.length<1){
                                    valid=false;
                                    totalErrors++;
                                    form_obj.inlineErrorMsg(element,value,valid,window.i18nData['invalid_consigneeId'] || 'Invalid Consignee Id.');
                                }else{
                                    valid=true;
                                    model.consigneeId=$("#wallet_consignee_id").val()
                                }                                    
                            }else if(el.attr("id")=="wallet_pccc"){
                                if(value=="" || value.length<13){
                                    valid=false;
                                    totalErrors++;
                                    form_obj.inlineErrorMsg(element,value,valid,window.i18nData['invalid_code_id'] || 'invalid_code_id.');
                                }else{
                                    valid=true;
                                    model.pccc=$("#wallet_pccc").val()
                                }                                    
                            }else if(el.attr("id")=="wallet_cpf"){
                                valid=form_obj.validate_cpf(value,true);
                                if(!valid){
                                    totalErrors++;
                                    form_obj.inlineErrorMsg(element,value,valid,window.i18nData['invalid_cpf'] || 'Please enter a valid CPF number.');
                                }else{
                                    model.cpf=$("#wallet_cpf").val().replace(/[.-]/g, '').trim();
                                }
                            }else if(el.attr("id")=="wallet_phone"){
                                if (value == "") {
                                    totalErrors++;
                                    valid=false;
                                }else{
                                    valid=true;
                                    model.shippingInfo.phoneNumber=form_obj.wallet_phoneIntelli.getNumber().trim() || $("#wallet_phone").val()
                                }
                                form_obj.inlineErrorMsg(element,value,valid,window.i18nData['required'] || 'Required Field.');
                            }
                        }                
                    });

           
                if(totalErrors==0){
                    form_obj.submitWalletOrder(model, cartData);
                    $('#wallet_phone_handler_overlay').remove();
                }
            });            
        }

        form_obj.walletReqFieldsDialog=function(model, cartData){    
            var fields_="";

            if(model.shippingInfo.phoneNumber=="" || model.shippingInfo.phoneNumber==null || getQueryStringByName("test-no-phone") == "1"){
                fields_ +='<label style="margin-top:10px;display:block;">'+(window.i18nData["phone_number"] || 'Phone Number:') +'<span style="color:red;">*</span></label>';           
                fields_ += '<input type="tel" id="wallet_phone" name="wallet_phone" class="form-control ship-form" required style="padding: 10px; font-size: 14px;width:100%;">';  
            }

            if(model.shippingInfo.shipCountry=="BR"){
                fields_ +='<label style="margin-top:10px;display:block;">'+(window.i18nData["cpf"] || 'CPF:')+'<span style="color:red;">*</span></label>';           
                fields_ += '<input type="text" id="wallet_cpf" name="wallet_cpf" placeholder="000.000,000-00" class="form-control ship-form" required style="padding: 10px; font-size: 14px;width:100%;">';  
            }

            if(model.shippingInfo.shipCountry=="TW"){
                fields_ +='<label style="margin-top:10px;display:block;">'+(window.i18nData["consignee_id"] || 'Consignee ID:') +'<span style="color:red;">*</span></label>';           
                fields_ += '<input type="text" id="wallet_consignee_id" name="wallet_consignee_id" class="form-control ship-form" required style="padding: 10px; font-size: 14px;width:100%;">';  
            }
            
            if(model.shippingInfo.shipCountry=="KR"){
                fields_ +='<label style="margin-top:10px;display:block;">'+(window.i18nData["pccc"] || 'PCCC (Personal Customs Clearance Code):') +'<span style="color:red;">*</span></label>';           
                fields_ += '<input type="text" id="wallet_pccc" name="wallet_pccc" class="form-control ship-form" required style="padding: 10px; font-size: 14px;width:100%;">';  
            }              
          
          
            var html = '';
                html += '<div id="wallet_phone_handler_overlay" style="display:block;">';   
                html += '   <div class="wallet_phone_handler_body">';            
                html += '   <div id="wallet_phone-billing-section" style="margin-top: 20px;">';                
                html += '       <p style="margin-bottom: 20px;">'+(window.i18nData["wallet-payment-phone-header"] || 'Please fill in the required field(s) below to ensure a smooth and successful delivery of your order.') +'</p>';
                html += '       <div id="required-wallet-phone">';    
                html += fields_;  
                html += '          <div class="wallet-button-stripe btn" id="wallet-submit" style="margin-top:15px;width: 100%; background: #000; border-radius: 4px; border: 1px solid #000; padding: 10px; text-align: center; display: flex; justify-content: center; align-items: center; cursor: pointer;" role="button">';            
                html += '               <span style="font-size: 16px; font-weight: 500; color: #fff;">'+(window.i18nData["wallet-payment-submit-text"] || 'Submit')+'</span>';
                html += '           </div>';
                html += '       </div>';
                html += '     </div>';
                html += '   </div>';
                html += '</div>';
             
            if($("#wallet_phone_handler_overlay").length==0){
                $('body').append(html);	

                if($("#wallet_cpf").length!=0){
                    $("#wallet_cpf").mask('000.000.000-00');
                }

                $('#required-wallet-phone input').each(function(index, element) {
                    if ($(this).is(':visible') == true) {
                        var el = $(this);
                        el.on("focus", function(){
                            form_obj.inlineErrorMsg(element,"",true);
                        });
                    }
                });

                form_obj.walletSubmit(model, cartData);	  
            }

            if (typeof intlTelInput != "undefined" && form_obj.intlPhone) { 
                    var walletPhone = document.querySelector("#wallet_phone");
                    form_obj.wallet_phoneIntelli = window.intlTelInput(walletPhone, form_obj.geoLookUpOptions({
                        initialCountry: $("#userCountry").val(),
                        autoPlaceholder: ""                    
                    }));
            }            
			return false;
        }

        
        form_obj.createStripeExpressButton=function(mountSelector) {
                const key = ($("#stripePublishableKey_live").val() && 
                            (getQueryStringByName("testmode")=="1" || getQueryStringByName("testmode")=="true"))
                            ? $("#stripePublishableKey_sandbox").val()
                            : $("#stripePublishableKey_live").val();

                const stripe = Stripe(key,{
                                    locale: $("html").attr("lang")
                               });

                const elements = stripe.elements({
                    mode: "payment",
                    amount: 1000,
                    currency: $("#prodCurrency").val().toLowerCase(),
                    appearance: { variables: { borderRadius: '4px' } },
                    setupFutureUsage: 'off_session',
                    paymentMethodCreation: 'manual'   // allow manual createPaymentMethod
                   
                });

                const expressCheckoutOptions = {
                    paymentMethods: {
                    applePay: 'never',
                    googlePay: 'never',
                    klarna: 'never',
                    amazonPay: 'never',
                    paypal: 'never',
                    link: 'never',
                    naverPay:'never',
                    },
                    paymentMethodOrder: ['apple_pay','google_pay','link'],
                    buttonHeight: 47,
                    // Two wallets sit side by side; switched to one full-width column on "ready"
                    // when all three are available. overflow 'never' avoids a "More" button
                    layout: {
                        maxColumns: 2,
                        maxRows: 0,
                        overflow: 'never'
                    }
                };
                
                const walletPayments = $("#stripePaymentWallet").val().toLowerCase();
                if (walletPayments.includes('google_pay')) {
                    expressCheckoutOptions.paymentMethods.googlePay = 'always';
                }
                if (walletPayments.includes('apple_pay')) {
                    expressCheckoutOptions.paymentMethods.applePay = 'always';

                }
                if (walletPayments.includes('link')) {
                    // Link only accepts 'auto' | 'never'; Stripe hides it where Link is unavailable
                    expressCheckoutOptions.paymentMethods.link = 'auto';
                }
              
                const expressCheckoutElement = elements.create("expressCheckout", expressCheckoutOptions);

                // Attach shared events
                form_obj.attachSharedWalletEvents(expressCheckoutElement, elements, stripe);

                // Stack wallets full width when all three (Apple Pay, Google Pay, Link) are available;
                // with two or fewer keep the side-by-side layout. Hidden until ready to avoid a layout jump
                $(mountSelector).css("visibility", "hidden");
                expressCheckoutElement.on("ready", function(event){
                    var available = event.availablePaymentMethods || {};
                    var walletCount = ["applePay", "googlePay", "link"].filter(function(method){
                        return available[method];
                    }).length;
                    if (walletCount >= 3) {
                        expressCheckoutElement.update({
                            layout: { maxColumns: 1, maxRows: 0, overflow: 'never' }
                        });
                    }
                    $(mountSelector).css("visibility", "");
                });

                // Mount into the provided container
                expressCheckoutElement.mount(mountSelector);
                $(".divider_text_or").show();

                // Save reference if you want to loop later
                expressButtons.push({ expressCheckoutElement, elements });
        }

        form_obj.mountStripeWalletButton = function(){
            /*if($("#paySource").attr("paySource")=="GOOGLEPAY" || $("#paySource").attr("paySource")=="APPLEPAY"){  
                 var count=0;           
                 $(".yes-upsell-link").each(function() {
                    // clone the button
                    var newBtn = $(this).clone();
                    // optionally, modify it (e.g., add a class or change text)
                    newBtn.addClass("express-stripe-btn"+count).html("").attr("style","padding:0!important;background:transparent!important;border-bottom:none!important;box-shadow: none!important;");

                    // insert after the original button
                    $(this).after(newBtn);
                    $(this).remove();//remove Original
                    form_obj.createStripeExpressButton(".yes-upsell-link.express-stripe-btn"+count);
                    count++;
                });                   
            }else{*/
                 form_obj.createStripeExpressButton("#express-checkout-stripe");
            //}
        }

        form_obj.paymentCustomCallback=function(){
            // gituyo ni para kun naay add nga code like dle ipagawas ang popup kun mobayad na 
        }  
        
        form_obj.beginCheckoutExec=function(){
            // gituyo ni para kun naay add nga code like dle ipagawas ang popup kun mobayad na 
        }  

        var geo_options = {
            initialCountry: "auto",
            geoIpLookup: function (callback) {
                form_obj.clientGeoLocation(function(data){   
                    if($(formField.country+" option[value='"+data.countryCode+"']").length!=0){
                        callback(data.countryCode); 
                        geo_options.initialCountry="auto"; 
                        $(formField.country).val(data.countryCode).trigger("change");
                    } else {
                        $(formField.country).val(form_obj.intlPhoneInitialCountry).trigger("change");
                    }    
                });                                   
            },
            //placeholderNumberType: "MOBILE",
            separateDialCode: true,
            //fixDropdownWidth:true,
            countrySearch: true,
        };

        form_obj.geoLookUpOptions=function(options_) {
            for (var key in options_) {                
                geo_options[key] = options_[key];
            }    
            geo_options['autoPlaceholder'] = "";
            geo_options['nationalMode'] = false;     
               
            return geo_options;
        }

        form_obj.applyMask=function(iti,el) {
            var countryData = iti.getSelectedCountryData();

            // Get example in INTERNATIONAL format (with +countryCode)
            var exampleNumber = intlTelInputUtils.getExampleNumber(
                countryData.iso2,
                false, // false = international format
                intlTelInputUtils.numberFormat.INTERNATIONAL
            );

            // Remove country code from the example number string
            // Strip +countryCode (keep area code pattern)
            var dialCode = '+' + countryData.dialCode;
            var localExample = exampleNumber.replace(dialCode, '').trim();
            
            // Replace digits with 9s
            // var mask = localExample.replace(/[0-9]/g, "0");
            
            // $(formField.phone).attr('maxlength', mask.length);//added max length to phone field
            //     // Remove previous mask and apply new one
            // $(el).unmask();
            // $(el).mask(mask,{
            //         placeholder: localExample
            // });

            // Replace digits with 9s
            // var mask = localExample.replace(/[0-9]/g, "9");

            // // Remove previous mask and apply new one
            // $(el).inputmask('remove');
            // $(el).inputmask(mask, {
            //     placeholder: "_",
            //     showMaskOnHover: true,   // show the mask when hovered
            //     showMaskOnFocus: true,   // show when focused
            //     showMaskOnBlur: true,    // keep the mask visible even when blurred
            //     clearMaskOnLostFocus: false, // don't clear mask when unfocused
            //     autoUnmask: false         // keep placeholders visible
            // });
        }

        form_obj.validate_cpf=function (val, msk) {
            var regex = msk != undefined && msk ? /^\d{3}\.\d{3}\.\d{3}\-\d{2}$/ : /^[0-9]{11}$/;

            if (val.match(regex) != null) {
                //check all same numbers
                if (val.match(/\b(.+).*(\1.*){10,}\b/g) != null)
                    return false;

                var strCPF = val.replace(/\D/g, '');
                var sum;
                var rest;
                sum = 0;

                for (i = 1; i <= 9; i++)
                    sum = sum + parseInt(strCPF.substring(i - 1, i)) * (11 - i);

                rest = (sum * 10) % 11;

                if ((rest == 10) || (rest == 11))
                    rest = 0;

                if (rest != parseInt(strCPF.substring(9, 10)))
                    return false;

                sum = 0;
                for (i = 1; i <= 10; i++)
                    sum = sum + parseInt(strCPF.substring(i - 1, i)) * (12 - i);

                rest = (sum * 10) % 11;

                if ((rest == 10) || (rest == 11))
                    rest = 0;
                if (rest != parseInt(strCPF.substring(10, 11)))
                    return false;

                return true;
            }

            return false;
        }

        form_obj.intlPhoneInit = function(){
             if (typeof intlTelInput != "undefined" && form_obj.intlPhone) { 
                    const input = document.querySelector(formField.phone);
                    form_obj.phoneIntelli = window.intlTelInput(input, form_obj.geoLookUpOptions({
                        // onlyCountries: $("#stripeCountries").val().split(","), - remove to show all flag
                        initialCountry: $("#userCountry").val(),
                        autoPlaceholder: ""                    
                    }));

                    var $this = $(formField.country);
                  
                    $this.on('change', function () {
                        if(this.value==""){
                             form_obj.phoneIntelli.setCountry($("#userCountry").val().toUpperCase());
                             form_obj.applyMask(form_obj.phoneIntelli,formField.phone); 
                            //$(formField.country).val($("#userCountry").val()).trigger("change");    
                        }else{
                            form_obj.phoneIntelli.setCountry(this.value);
                            form_obj.applyMask(form_obj.phoneIntelli,formField.phone); 
                        }
                           
                    });

                    input.addEventListener("countrychange", function () {
                        form_obj.applyMask(form_obj.phoneIntelli,formField.phone); 
                    });

                    input.addEventListener("countrychange", function () {
                        form_obj.applyMask(form_obj.phoneIntelli,formField.phone);
                    });
                }
        }
         
        var idleTimer;
        var IDLE_TIME = 5 * 60 * 1000; // 10 minutes

        form_obj.resetTimer = function() {
                clearTimeout(idleTimer);
                idleTimer = setTimeout(function() {
                    if(location.hostname=="127.0.0.1" || location.hostname=="localhost"){
                            var url = location.pathname;
                            // Find the last slash and take everything before it
                            var basePath = url.substring(0, url.lastIndexOf("/"));
                            window.location.href = location.origin + ((getCurrentOffer()=="") ? "" : basePath )+ "/thankyou.php"+location.search;
                    }else{
                            window.location.href = location.origin + ((getCurrentOffer()=="") ? "" : "/"+getCurrentOffer() )+ "/thankyou.php"+location.search;
                    }
      
                }, IDLE_TIME);
        }

        form_obj.redirectIdleToTy=function(){
            var events = [
                "load",
                "mousemove",
                "mousedown",
                "click",
                "scroll",
                "keypress",
                "touchstart"
            ];

            for (var i = 0; i < events.length; i++) {
                window.addEventListener(events[i], form_obj.resetTimer);
            }
        }

        //initiatialize execution of form events code
        //you create a callback function if you want to initiate or execute script upon calling form_obj.exec();
        form_obj.init = function (callback) {
              window.addEventListener("load", function() {
                    form_obj.removeProgressModal();
                    form_obj.paypalConfirmOrder(); //paypal button event
                    //form_obj.googlePayInit();
                    if(getQueryStringByName("errorMsg")!="" && getQueryStringByName("errorMsg").toLowerCase().indexOf("hateoas")==-1){
                        form_obj.showMsg(getQueryStringByName("errorMsg")+".<br/><br/><em> "+( window.i18nData['page_reload'] || "The page will reload after you close the dialog.")+"</em>");
                    }
                   
              });
            
              form_obj.getKonnektiveAndEFParams(); //set konnektive params and Everflow params                             

              $(document).ready(function () {
                
                form_obj.loadCommonStyles();
                form_obj.closeModalEvents();  
                  
                form_obj.paypalTrigger(); //paypal button event            
            
                if(!form_obj.isUpsellPage() && !form_obj.isThankyou()){ //execute code content if not in theh upsell page
                    form_obj.initSelectedProd();//select previous selected product 
                    form_obj.stripeWalletButton(); //stripe wallet execute here         
                    form_obj.initiateLead(); //initiate lead event 
                    form_obj.intlPhoneInit(); //init IntlPhone                  
                    form_obj.exitPopup();//execute exit pop
                    form_obj.inputsElSetup();
                    form_obj.inputElEvents();
                    form_obj.ccExpirationDate();
                    form_obj.differentBillingAddressForm(function(){
                        form_obj.inputsElSetup();
                        form_obj.inputElEvents();                  
                        form_obj.addStatesProvince(formField.state);
                        form_obj.addStatesProvince(formField.checkboxsameaddressFields.stateBill);
                        form_obj.inputKeyPressValidation();
                        $(differentBillingContainer).hide();
                        $(formField.checkboxsameaddress).prop('checked', true);
                    });
                    form_obj.submitForm();//checkout submit button function
                } else if(form_obj.isUpsellPage()){ 
                    form_obj.disableBack();
                    form_obj.conversionEverflow3ds();                 
                    form_obj.noUpsellLink();
                    form_obj.yesUpsellLink();
                    if(getQueryStringByName("gtmetrix")==""){
                        form_obj.redirectIdleToTy();
                    }                    
                    //form_obj.stripeWalletButton();     
                } else if(form_obj.isThankyou()){
                    window.addEventListener("load", function() {
                        history.pushState(null, null, location.href);
                    });

                    form_obj.conversionEverflow3ds();
                    setTimeout(function(){
                         form_obj.getResponse({
                                    call_type:"click_import_thankyou",
                                    userAgent: navigator.userAgent,
                                    orderId: getQueryStringByName("orderId"),
                                    requestUri: location.href
                        },
                        function(data){
                            console.log(data);
                        },
                        function(data){
                            console.log(data);
                        }); 
                    },100);                       
                }

                if(typeof callback=="function"){
                    callback();
                }
                
            });
        }

        return form_obj;
    }

    var init = 'CRMIntegrated';
    if (typeof window[init] !== 'function') {
        window[init] = Form;
    }
})();

(function () {
    // Generic gray placeholder shown when any <img> on the page fails to load.
    var FALLBACK_IMAGE = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">' +
        '<rect width="200" height="200" fill="#eeeeee"/>' +
        '<path d="M40 150 L80 100 L110 130 L140 80 L160 150 Z" fill="#cccccc"/>' +
        '<circle cx="65" cy="70" r="14" fill="#cccccc"/>' +
        '</svg>'
    );

    // 'error' events on <img> don't bubble, so listen on the capture phase.
    document.addEventListener('error', function (e) {
        var img = e.target;
        if (img && img.tagName === 'IMG' && img.src !== FALLBACK_IMAGE) {
            img.src = FALLBACK_IMAGE;
            img.srcset = '';
        }
    }, true);
})();
