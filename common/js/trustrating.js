(function () {
    var TrustRating = function (config) {  
        var trust_obj = {}; 
            trust_obj.logoPath ="";
        var currentRating = 5;
            trust_obj.headerText= window.i18nData["trust_rating_popup_headertext"] || "How would you rate your shopping experience?";
            trust_obj.subText = window.i18nData["trust_rating_popup_subtext"] || "Your feedback means a lot";
            trust_obj.buttonText=window.i18nData["trust_rating_buttontext"] || "Submit";

         if (typeof config != "undefined" && typeof config=="object") {
           if(config.hasOwnProperty("headerText")){
                trust_obj.headerText=config.headerText;               
           }

           if(config.hasOwnProperty("subText")){
                trust_obj.subText=config.subText;               
           }

            if(config.hasOwnProperty("buttonText")){
                trust_obj.buttonText=config.buttonText;               
           }    
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

        window.getCurrentOffer = function () {
                    var currentOffer = window.location.pathname.substr(0, window.location.pathname.lastIndexOf('/')).toLowerCase().replace('/mobile', '');
                    var pos = currentOffer.lastIndexOf('/');
                    currentOffer = currentOffer.substr(pos).replace("/", "").trim();
                    if (currentOffer == extractDomain(window.location.pathname)) {
                        currentOffer = '';
                    }
                    return currentOffer;
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

        trust_obj.ratingEvent=function(el){
              // Hover highlight
            el.on('mouseenter', '.star', function() {
                const hoverValue = $(this).data('value');
                el.find('.star').each(function() {
                const val = $(this).data('value');
                $(this).toggleClass('hovered', val <= hoverValue);
                });
            });

            el.on('mouseleave', function() {
                el.find('.star').removeClass('hovered');
            });

            // Click to set rating
            el.on('click', '.star', function() {
                currentRating = $(this).data('value');
                trust_obj.updateStars(currentRating,el);
                $('#btn-rate-submit').attr("rating",currentRating);
                $(".trust_handler_body button").addClass("active");
            });
        }

        trust_obj.updateStars=function(rating,el){
            el.find('.star').each(function() {
                var val = $(this).data('value');
                $(this).toggleClass('filled', val <= rating);
            });
        }

        trust_obj.updateLogoSrc=function(path){
            trust_obj.logoPath="/"+path;
        }

        trust_obj.createTrustDialog=function(){            
          
            var html =    '<div class="trust_handler_body">';
                html +=    '        <a href="javascript:void(0);" id="trust_handler_overlay_close">X</a>';
                html +=    '        <div id="trust_header">';
                html +=    '            <img src="'+commonFilesPath()+trust_obj.logoPath+'" alt="logo" class="corp-logo" width="150" height="56">';
                html +=    '        </div>';
                html +=    '        <hr/>';
                html +=    '        <div id="trust_body">';
                html +=    '            <p>'+trust_obj.headerText+'</p>';
                html +=    '            <div class="star-rating" id="rating">';
                html +=    '                <svg viewBox="0 0 24 24" data-value="1" class="star"><path d="M12 17.27L18.18 21 16.54 13.97 22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z"/></svg>';
                html +=    '                <svg viewBox="0 0 24 24" data-value="2"  class="star"><path d="M12 17.27L18.18 21 16.54 13.97 22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z"/></svg>';
                html +=    '                <svg viewBox="0 0 24 24" data-value="3"  class="star"><path d="M12 17.27L18.18 21 16.54 13.97 22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z"/></svg>';
                html +=    '                <svg viewBox="0 0 24 24" data-value="4"  class="star"><path d="M12 17.27L18.18 21 16.54 13.97 22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z"/></svg>';
                html +=    '                <svg viewBox="0 0 24 24" data-value="5"  class="star"><path d="M12 17.27L18.18 21 16.54 13.97 22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z"/></svg>';
                html +=    '            </div>  ';                          
                html +=    '            <button id="btn-rate-submit">'+trust_obj.buttonText+'</button>';
                html +=    '            <small>'+trust_obj.subText+' <img width="10" height="10" src="'+commonFilesPath()+'src/common/images/heart.svg" alt="heart"></small>';
                html +=    '        </div>';
                html +=    '    </div>';
                //html +=    '</div>';
                html +='<div id="trust_rating_handler_overlay"></div>';

            if($("#trust_rating_handler_overlay").length==0){
                $('body').prepend(html);
			    $('#trust_rating_handler_overlay, .trust_handler_body').fadeIn(500);
            }
         
			return false;
        }

        trust_obj.closeModalEvents= function(){
            	$(window).keydown(function(e) {
                    if (e.which === 27 && $('#trust_rating_handler_overlay').length) {
                         $('#trust_rating_handler_overlay').remove();
                         $('.trust_handler_body').remove();
                         sessionStorage.setItem("addedRating"+getCurrentOffer(),true); //added session to not show the popup on the nextreload
                         trust_obj.closeEventTrack();
                    }
                });

                $(document).off('click', '#trust_rating_handler_overlay');
                $(document).on('click', '#trust_rating_handler_overlay', function() {
                     $(this).remove();
                     $('.trust_handler_body').remove();
                     sessionStorage.setItem("addedRating"+getCurrentOffer(),true); //added session to not show the popup on the nextreload
                     trust_obj.closeEventTrack();
                });

                $(document).off('click', '#trust_handler_overlay_close');
                $(document).on('click', '#trust_handler_overlay_close', function() {
                    $('#trust_rating_handler_overlay').remove();                     
                    $('.trust_handler_body').remove();
                    sessionStorage.setItem("addedRating"+getCurrentOffer(),true); //added session to not show the popup on the nextreload
                    trust_obj.closeEventTrack();
                });
        }

        trust_obj.closeEventTrack=function(){
             
        }

        trust_obj.buttonEvent=function(){
            $("#btn-rate-submit").on("click", function(){
                if($(this).hasClass("active")){
                    sessionStorage.setItem("addedRating"+getCurrentOffer(),true);
                    trust_obj.submit($(this));
                }                
            });
        }

        trust_obj.submit=function(el){           
            //alert("You rated: "+el.attr("rating"));
        }
        
        trust_obj.init = function (callback) {                          

              $(document).ready(function () {  
                setTimeout(function(){
                    if(sessionStorage.getItem("addedRating"+getCurrentOffer())==null){
                        trust_obj.createTrustDialog();
                        trust_obj.closeModalEvents();
                        trust_obj.buttonEvent();

                        var $rating = $('#rating');
                        trust_obj.updateStars(currentRating,$rating);
                        $('#btn-rate-submit').attr("rating",currentRating);
                        $(".trust_handler_body button").addClass("active");
                        trust_obj.ratingEvent($rating);
                    }                    
                },500);               

                if(typeof callback=="function"){
                    callback();
                }                
            });
        }
        return trust_obj;
    }

    var trustInit = 'CRMTrustRating';
    if (typeof window[trustInit] !== 'function') {
        window[trustInit] = TrustRating;
    }
})();
