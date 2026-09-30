function formatNumber(amount, currencySymbol) {

        if(currencySymbol === "CHF" || currencySymbol === "Fr." || currencySymbol === "Fr"){
            var chfDecimal = (amount % 1 !== 0) ? 2 : 0;
            var chfRounded = (chfDecimal === 0) ? Math.round(amount) : Number(amount).toFixed(2);
            var chfParts = String(chfRounded).split(".");
            chfParts[0] = chfParts[0].replace(/\B(?=(\d{3})+(?!\d))/g, "'");
            return "CHF " + chfParts.join(".");
        }

        if(currencySymbol === "RSD" || currencySymbol === "din." || currencySymbol === "din"){
            var rsdDecimal = (amount % 1 !== 0) ? 2 : 0;
            var rsdRounded = (rsdDecimal === 0) ? Math.round(amount) : Number(amount).toFixed(2);
            var rsdParts = String(rsdRounded).split(".");
            rsdParts[0] = rsdParts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            return rsdParts.join(",") + " RSD";
        }

        var code = undefined;
        var symbolPrefix = false;
        var decimal = (amount % 1 !== 0) ? 2 : 0;

        if(currencySymbol === "€"){
            if($("#userCountry").val() === "NL"){
                symbolPrefix = true;
            }
            currencySymbol ="€";
            code = "de-DE";
           
            
        }else if(currencySymbol === "Kr"){

            code = "da-DK";
            currencySymbol ="kr.";

        }else if(currencySymbol === "Kč"){

            code = "cs-CZ";
            currencySymbol ="Kč";

        }else if(currencySymbol === "zł"){

            code = "pl-PL";
            currencySymbol ="zł";

        }else if(currencySymbol === "Ft"){

            code = "hu-HU";
            currencySymbol ="Ft";

        }else if(currencySymbol === "₫") {

            code = "vi-VN";
            currencySymbol ="₫";
            decimal = 0;

        }else if(currencySymbol === "Rp") {

            symbolPrefix = true;
            code = "id-ID";
            decimal = 0;

        }else if (
            currencySymbol === "NT$" ||
            currencySymbol === "¥" ||
            currencySymbol === "₩"){

            symbolPrefix = true;
            decimal = 0;  
            code = undefined; 

        }else if(currencySymbol === "TL"){
            
            code = "tr-TR";       

        }else if(currencySymbol === "RON"){

            code = "ro-Ro"; 
            currencySymbol ="lei";    

        }else if(currencySymbol === "QR"){

            symbolPrefix = true;
            currencySymbol ="QR ";     

        }else if(currencySymbol === "PKRs"){

            symbolPrefix = true;   
            currencySymbol ="PKR "; 
            decimal=0;    

        }else{
            symbolPrefix = true;
            code = undefined; 
        }

        var formatted = Number(((decimal==0) ? Math.round(amount) : amount)).toLocaleString(code, {
            minimumFractionDigits: decimal,
            maximumFractionDigits: decimal
        });

        return symbolPrefix ? (currencySymbol +  formatted) 
                            : (formatted +" "+ currencySymbol); 
}