<?php
if (session_status() != PHP_SESSION_ACTIVE) {
    session_cache_limiter('nocache');	
    session_start();
}

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
date_default_timezone_set('America/New_York');

$serverName_ = $_SERVER['SERVER_NAME'] ?? ''; 
$repoName_= "";
  
// If running on localhost or 127.0.0.1 → development
if (in_array($serverName_, ['localhost', '127.0.0.1','bs-local.com'])) {      
    $repoName_="/".basename(dirname(__DIR__));
} 

define("BASEPATH_",$_SERVER['DOCUMENT_ROOT'].$repoName_);

include_once("KonnektiveApi.php");
require_once (BASEPATH_.'/vendor/autoload.php'); // Stripe SDK
use Stichoza\GoogleTranslate\GoogleTranslate;
use DeepL\Translator; 

class OfferApi extends KonnektiveApi {
	
    public $campaignId;
    public $paypalSandboxId;
    public $paypalProductionId;
    public $stripeSandboxId;
    public $stripeProductionId;
    public $corporateName;
    public $customDescriptor="";
    public $returnAddress;
    public $everflowOfferId;
    public $companyAddress;
    public $companyEmailAddress;
    public $companyEmailAddress1;
    public $companyPhoneNumber;
    public $upsells;
    public $countries;
    public $currency;
    public $currencySymbol;
    public $coupons;
    public $shipProfiles;
    public $checkoutType;
    public $offerProducts;
    public $upsaleProducts;
    public $taxes;
    public $zones;
    public $storePickup;
    public $currentPageType;  
    public $stripePaymentWallet;
    public $stripeKeys=[];  
    public $geoLangSelected=false;
    public $browserLangDefault;
    public $targetLanguage = "en";
    public $default_country="US";
    private $deepLKey; //api key
    private $cacheBustKey = "7f3a1c9e-58b2-4d6a-9c11-e2b4f0a6d7c3"; //secret for ?bustCampaignCache=
    public $sessionCountry;
    public $countryName="United States";
    public $reOrderDays;
    
	
    public function __construct() {
         parent::__construct();
         $this->deepLKey = getenv('DEEPL_KEY');
    }

    public function setupOffer($config, $queryCampaign=true, $clickEventFire=false,$setInvalidView=true){
        
        foreach ($config as $key => $value) {
            switch($key){
                case 'campaignId':
                    $this->campaignId= $value;                    
                    break;
                case 'targetLanguage':
                    $this->targetLanguage= $value;                    
                    break;                
                case 'paypalSandboxId':
                    $this->paypalSandboxId= $value;    
                    break;
                case 'paypalProductionId':
                    $this->paypalProductionId= $value;    
                    break;
                case 'stripeSandboxId':
                    $this->stripeSandboxId= $value;    
                    break;
                case 'stripeProductionId':
                    $this->stripeProductionId= $value;    
                    break;
                case 'corporateName':
                    $this->corporateName= $value;    
                    break;
                case 'customDescriptor':
                    $this->customDescriptor= $value;    
                    break;
                case 'companyAddress':
                    $this->companyAddress= $value;    
                    break;
                case 'companyEmailAddress':
                    $this->companyEmailAddress= $value;    
                    break;
                case 'companyEmailAddress1':
                    $this->companyEmailAddress1= $value;    
                    break;
                case 'companyPhoneNumber':
                    $this->companyPhoneNumber= $value;    
                    break;
                case 'returnAddress':
                    $this->returnAddress= $value;    
                    break;
                case 'everflowOfferId':
                    $this->everflowOfferId= $value;    
                    break;
                case 'campaignFunnel':
                    $this->upsells= $value;    
                    break;
                case 'checkoutType':
                    $this->checkoutType= $value;    
                    break;
                case 'stripeKeys':
                    $this->stripeKeys= $value;    
                    break;
                case 'stripePaymentWallet':
                    $this->stripePaymentWallet= $value;    
                    break;               
            }
        }

        if($queryCampaign){
            if(isset($_GET['bustCampaignCache']) && hash_equals($this->cacheBustKey, (string)$_GET['bustCampaignCache'])){
                $this->clearCampaignCache($this->campaignId); //force a fresh fetch below
            }

            //get the campaign setup
            $resp=json_decode($this->get_campaign($this->campaignId));
        
            if($resp->result==="SUCCESS"){   
                
                //sort countries
                usort($resp->countries, function ($a, $b) {
                    return strcmp($a->countryName, $b->countryName);
                });

                $this->countries=$resp->countries;

                $this->offerProducts=array_filter($resp->products, function($item) {
                    return $item->productType === 'OFFER';
                });

                $this->upsaleProducts=array_filter($resp->products, function($item) {
                    return $item->productType === 'UPSALE';
                });

                $this->currency=$resp->currency;
                $this->currencySymbol=$resp->currencySymbol;
                $this->coupons=$resp->coupons;
                $this->shipProfiles=$resp->shipProfiles;
                $this->taxes=$resp->taxes;
                $this->zones=$resp->zones;
                $this->storePickup=$resp->storePickup;
                $this->reOrderDays=$resp->reOrderDays;
                
        
            }else{
                echo '<div style="font-family:sans-serif;color:red; padding:10px;border:1px solid red;">'.$resp->message.'</div>';
                exit;
            }
            if(!isset($_GET['paypalAccept'])){
                $this->landers_clicks_import(setInvalidView:$setInvalidView); //initiate click import call
            }
        }else if($clickEventFire){
            if(!isset($_GET['paypalAccept'])){
                $this->landers_clicks_import(setInvalidView:$setInvalidView); //initiate click import call
            }
        }
    }

    //get coupons
    //get coupons
    public function getCoupon($couponCode){
        if(!is_array($couponCode)){
          
            $data=array_values(array_filter($this->coupons, function($item) {
					return stripos($item->couponCode, $couponCode) !== false;
			  }))[0];

            if(isset($data)){
                $discountAmount='<input type="hidden" id="couponDiscountPrice1"  name="couponDiscountPrice1" value="'.$data->discountPrice.'"/>';
                if($data->discountType=="PERCENT"){
                    $discountAmount='<input type="hidden" id="couponDiscountPerc1"  name="couponDiscountPerc1" value="'.$data->discountPerc.'"/>';
                }

                return '<div style="display:none;">'. 
                            '<input type="hidden" id="couponCode1" name="couponCode1" value="'.$data->couponCode.'"/>'. 
                            '<input type="hidden" id="couponDiscountType1"  name="couponDiscountType1" value="'.$data->discountType.'"/>'
                            .$discountAmount.
                            '<input type="hidden" id="couponCampaignProductId1"  name="couponCampaignProductId1" value="'.$data->campaignProductId.'"/>'.
                    '</div>';
            }
        }else if(is_array($couponCode)){
          
                $filteredCoupon = array_filter($this->coupons, function($item) use ($couponCode) {
                                return in_array($item->couponCode, $couponCode);
                      });
                  
                $couponInputs='<div style="display:none;">';                    
                foreach ($filteredCoupon as $key => $value) {   
                        $couponInputs.='<input type="hidden" id="'.$filteredCoupon[$key]->couponCode.'_"';
                        $couponInputs.=' value="'.$filteredCoupon[$key]->couponCode.'" ';
                        $couponInputs.=' couponDiscountType="'.$filteredCoupon[$key]->couponCode.'" ';
                        $couponInputs.=' couponCampaignProductId="'.$filteredCoupon[$key]->campaignProductId.'" ';

                        if($filteredCoupon[$key]->discountType=="PERCENT"){
                            $couponInputs.=' couponDiscountPerc="'.$filteredCoupon[$key]->discountPerc.'" ';
                        }else{
                            $couponInputs.=' couponDiscountPrice="'.$filteredCoupon[$key]->discountPrice.'" ';
                        }       
                        $couponInputs.=' />';                    
                }
                
                $couponInputs.='</div>';

              return $couponInputs;
        }
        
    }

    public function stichoza_google_translate($text){

        $tr = new GoogleTranslate($this->targetLanguage); 
        return $tr->translate($text);
    }

    public function searchZipCode($query='', $country_code='', $extraParams=[]){
       // $upstreamUrl = 'http://localhost/api-melara/address-api/suggest.php?q=' . urlencode($query) . '&country=' . urlencode($country_code);

        $upstreamUrl = 'https://api.melarapro.com/address-api/suggest.php?q=' . urlencode($query) . '&country=' . urlencode($country_code);
        foreach ($extraParams as $key => $value) {
            if ($value === '' || $value === null) continue;
            $upstreamUrl .= '&' . urlencode($key) . '=' . urlencode($value);
        }

            $ch = curl_init($upstreamUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['X-API-Key: WEB-PROXY-KEY-67890'],
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_PROXY          => ""
            ]);

            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($body === false) {
                http_response_code(502);
                echo json_encode(['success' => false, 'error' => 'Upstream request failed: ' . $curlError]);
                exit;
            }

            http_response_code($status ?: 502);
            echo $body;
    }

  
    public function deepLTranslate($text) {
        if($this->targetLanguage=="en"){
            return $text;
        }else{
            // Map language codes to DeepL supported codes
            $deepLLanguageMap = [
                'pt-br' => 'pt-BR',    // Portuguese (Brazil)
                'pt-pt' => 'pt-PT',    // Portuguese (Portugal)
                'mx' => 'es'
            ];

            // Get the correct DeepL language code
            $deeplLang = $deepLLanguageMap[$this->targetLanguage] ?? $this->targetLanguage;

            $translator = new Translator($this->deepLKey);
            $result = $translator->translateText(
                $text,
                null,
                $deeplLang,
                ['tagHandling' => 'html']
            );
            return $result->text;
        }
    }

    public function initProductAddOn($productName=""){
       return $this->initProduct($productName,null);
    }

    public function invalidView($setInvalidView=true){
        if(isset($_GET['gtmetrix']) || !$setInvalidView){
            return;
        }
        if(strpos($this->currentPageType,"upsellPage")!==false || strpos($this->currentPageType,"thankyouPage")!==false){
                // if(isset($_SESSION['sessionCountry'])){
                //     if($_SESSION['sessionCountry']!=$this->sessionCountry){
                //          header("Location: ".$this->requestURI());
                //     }
                // }
                // if(isset($_SESSION['order_data_'.$this->campaignId])){//redirect if order_data not exist or orderId is not exists
                //     if($_SESSION['order_data_'.$this->campaignId]["orderId"]!==$_GET['orderId']){
                //         header("Location: ".$this->requestURI());
                //     }
                // }else{
                //      header("Location: ".$this->requestURI());
                // } 
                if(!isset($_SESSION['order_data_'.$this->campaignId])){//redirect if order_data not exist or orderId is not exists
                     header("Location: ".$this->requestURI());
                     exit;
                }
        }
    }

    public function initProduct($productName="",$orderData=""){
 
            if (is_array($orderData)) {

                //execute basic 3ds
                if ($orderData['finalizeTransaction'] == 1) {
                            $data = json_decode($orderData['orderData']); 
                            
                            if(isset($_GET['upsell'])){  
                                $_SESSION['order_data_'.$this->campaignId]['items']= $data->items ;
                            } else {
                                $filteredMainProduct = array_values(array_filter($data->items, function($item) {
                                    return $item->productId===$_SESSION['mainProductId_'.$this->campaignId];
                                })); 

                                $mainQty = (($this->upsells[0]['baseQtyFromCheckout']===true) ? $_SESSION['order_data_'.$this->campaignId]['mainProductQty'] : 1);
                                
                                unset($_SESSION['order_data_'.$this->campaignId]); //clear order data first
                                $_SESSION['order_data_'.$this->campaignId] =[
                                    "campaignId" => $data->campaignId,
                                    "emailAddress" => $data->emailAddress,
                                    "firstName" => $data->firstName,
                                    "lastName" => $data->lastName,                                
                                    "mainProductQty" => $mainQty,
                                    "mainProductId" => $filteredMainProduct[0]->productId,
                                    "mainProductPrice" => $filteredMainProduct[0]->price,
                                    "orderId" => $data->orderId,
                                    "paySource" => $data->paySource, 
                                    "prepaidType" =>$data->prepaidType,                         
                                    "items"=> $data->items                 
                                ];
                            }            

                            unset($_SESSION['token']); //clear token in every successful purchase 
                            if($this->funnelPage()!=="payment" && $this->funnelPage()!=="checkout" && $this->funnelPage()!=="index" && !str_contains($this->funnelPage(), "checkout")){

                                $filtered = array_values(array_filter($data->items, function($item){
                                    return $item->productId===$_SESSION["stored_products_order_".$this->campaignId]["product1_id"];
                                }));

                                // If the accepted product is the no-thanks popup downsell, fire the
                                // downsell-specific Everflow event instead of the main upsell one.
                                $efEventId = $this->resolveDownsellEverflowEventId($this->funnelPage())
                                             ?? $this->nextFunnelPage()['everflowEventId'];

                                header("Location: ".$this->requestURI().$this->nextFunnelPage()['yesBtnTo'].".php?orderId=".$data->orderId.
                                                  "&amount=".round((float)$filtered[0]->price,2).
                                                  "&oid=".$this->everflowOfferId.
                                                  "&event_id=".$efEventId.
                                                  "&upsell=true");
                                exit;
                            } else {
                                header("Location: ".$this->requestURI().$this->upsells[0]['page'].".php?orderId=".$data->orderId.
                                                  "&amount=".round($data->totalAmount,2).
                                                  "&oid=".$this->everflowOfferId.
                                                  "&main=true");
                                exit;
                            }
                }
            }else if($orderData!==null){
                echo '<div style="font-family:sans-serif;color:red; padding:10px;border:1px solid red;"><b>initProduct()</b> method requires <b>orderData</b>. example: initProduct(productName:"xxx",<b>orderData:$_POST<b/>); </div>';
                exit;
            }


            if(!isset($_SESSION['token'])){
                    $_SESSION['token'] = bin2hex(random_bytes(32));//create token here
            }          
          
            $element="";
            if(strpos($this->currentPageType,"upsellPage")!==false){
          
                //product upsale
                if(count($this->nextFunnelPage()['productIds'])==1){                      
                      
                      $filtered = array_values(array_filter($this->upsaleProducts, function($item) {
                                return stripos($item->campaignProductId, $this->nextFunnelPage()['productIds'][0]) !== false;
                      }));  
                      
                      $defaultPrice = $filtered[0]->price;
                      //   $defaultQty = (($this->nextFunnelPage()['baseQtyFromCheckout']===true) ? $_SESSION['order_data_'.$this->campaignId]['mainProductQty'] : 1);
                      
                      // accumulateQty: checkout qty + all accepted upsell qtys (e.g. 3 + 10 = 13)
                      // baseQtyFromCheckout: checkout qty only (e.g. 3)
                      // default: always 1
                      if (($this->nextFunnelPage()['accumulateQty'] ?? false) === true) {
                            $defaultQty = $_SESSION['order_data_'.$this->campaignId]['accumulatedQty']
                                        ?? $_SESSION['order_data_'.$this->campaignId]['mainProductQty'];
                        } else if ($this->nextFunnelPage()['baseQtyFromCheckout'] === true) {
                            $defaultQty = $_SESSION['order_data_'.$this->campaignId]['mainProductQty'];
                        } else {
                            $defaultQty = 1;
                        }
                        
                      $defaultActualPrice = round(((double) $filtered[0]->price * $defaultQty),2);
                      $maxOrderQty=$filtered[0]->maxOrderQty;
                     
                      //product variants
                      $firstVariantId="";
                      if(count($filtered[0]->variants)!=0){
                          $element.="<select id='product_variants' name='product_variants'>";
                                foreach ($filtered[0]->variants as $key => $value) {
                                    if($firstVariantId==""){
                                        $firstVariantId=$filtered[0]->variants[$key]->variantDetailId;
                                    }
                                    $element.="<option value='".$filtered[0]->variants[$key]->variantDetailId.
                                              "' title='".$filtered[0]->variants[$key]->title.
                                              "' variantOptionSequence1='".$filtered[0]->variants[$key]->variantOptionSequence1.
                                              "' variantName1='".$filtered[0]->variants[$key]->variantName1."'></option>";	
                                }
                          $element.="</select>";
                      }

                      $element.="<input type='hidden' class='_product_order' id='prod_id' value='".$filtered[0]->campaignProductId.
                                            "' prodname='".$filtered[0]->productName.
                                            "' maxqty='".$filtered[0]->maxOrderQty.
                                            "' unitprice='".$filtered[0]->price.
                                            "' shipping='".$filtered[0]->shippingPrice.
                                            "' quantity='".$defaultQty."'".
                                            (($firstVariantId!="") ? " variantId='".$firstVariantId."'": "")."/>";

                    
                     
    
                }else if(count($this->nextFunnelPage()['productIds']) > 0){
                      $filterIds=$this->nextFunnelPage()['productIds'];

                      // array_values() so $selectedProd->product is 0-indexed; pages read product[0]
                      $filtered = array_values(array_filter($this->upsaleProducts, function($item) use ($filterIds) {
                                return in_array($item->campaignProductId, $filterIds);
                      }));

                      $defaultProd = array_values(array_filter($this->upsaleProducts, function($item) use ($filterIds) {
                            if($this->nextFunnelPage()['baseQtyFromCheckout']===true){
                                return in_array($item->campaignProductId, $filterIds) && $item->maxOrderQty==$_SESSION['order_data_'.$this->campaignId]['mainProductQty'];
                            }else{
                                return in_array($item->campaignProductId, $filterIds);
                            }

                      }));

                      // preserve the priority order defined in productIds (first id = default) instead of
                      // whatever order the CRM API happened to return the products in
                      usort($defaultProd, function($a, $b) use ($filterIds) {
                            return array_search($a->campaignProductId, $filterIds) <=> array_search($b->campaignProductId, $filterIds);
                      });

                      //product variants
                      $firstVariantId="";
                      if(count($defaultProd[0]->variants)!=0){
                          $element.="<select id='product_variants' name='product_variants'>";
                                foreach ($defaultProd[0]->variants as $key => $value) {
                                    if($firstVariantId==""){
                                        $firstVariantId=$defaultProd[0]->variants[$key]->variantDetailId;
                                    }

                                    $element.="<option value='".$defaultProd[0]->variants[$key]->variantDetailId.
                                              "' title='".$defaultProd[0]->variants[$key]->title.
                                              "' variantOptionSequence1='".$defaultProd[0]->variants[$key]->variantOptionSequence1.
                                              "' variantName1='".$defaultProd[0]->variants[$key]->variantName1."'></option>";	
                                }
                          $element.="</select>";
                      }

                      $defaultPrice=$defaultProd[0]->price;
                      $defaultQty=$defaultProd[0]->maxOrderQty;
                      $defaultActualPrice = round(((double) $defaultProd[0]->price * $defaultQty),2);
                      $maxOrderQty=$defaultProd[0]->maxOrderQty;
                      $element.="<input type='hidden' class='_product_order' id='prod_id' value='".$defaultProd[0]->campaignProductId.
                                                "' prodname='".$defaultProd[0]->productName.
                                                "' maxqty='".$defaultProd[0]->maxOrderQty.
                                                "' unitprice='".$defaultProd[0]->price.
                                                "' shipping='".$defaultProd[0]->shippingPrice.
                                                "' quantity='".$defaultQty."'".
                                                (($firstVariantId!="") ? " variantId='".$firstVariantId."'": "")."/>";
                     
                      $element.="<select id='upsellProds' name='upsellProds'>";
                        foreach ($filtered as $key => $value) {
                            $element.="<option value='".$filtered[$key]->campaignProductId."' prodId='".$filtered[$key]->campaignProductId."' quantity='".$filtered[$key]->maxOrderQty."' price='".$filtered[$key]->price."'></option>";	
						}
                      $element.="</select>";

                      
                }

                if($productName!=""){
                     $filtered = array_values(array_filter($this->upsaleProducts, function($item) use ($productName) {
                            return stripos($item->productName, $productName) !== false;
                     }));
                }
   
                //everflow event id
                $element.='<input type="hidden" id="everFlowEventId" name="everFlowEventId" value="'.$this->nextFunnelPage()['everflowEventId'].'"/>';
                //everflow event id used only when the no-thanks popup downsell is accepted (set per funnel page, currently US config only)
                $element.='<input type="hidden" id="downsellEverFlowEventId" name="downsellEverFlowEventId" value="'.($this->nextFunnelPage()['downsellEverflowEventId'] ?? '').'"/>';
                if($_SESSION['order_data_'.$this->campaignId]["prepaidType"]!==null){
                    $element.='<input type="hidden" id="paySource" name="paySource" paysource="'.$_SESSION['order_data_'.$this->campaignId]["prepaidType"].'"/>';
                }else{
                    $element.='<input type="hidden" id="paySource" name="paySource" paysource="'.$_SESSION['order_data_'.$this->campaignId]["paySource"].'"/>';
                }

            }else{
                //product offer                  
                if($productName==""){
                    $filtered =$this->offerProducts;
                }else{                
                    $filtered = array_values(array_filter($this->offerProducts, function($item) use ($productName) {
                        return stripos($item->productName, $productName) !== false;
                    }));
                }
            }            

            if(strpos($this->currentPageType,"thankyouPage")!==false){
                $element.='<input type="hidden" id="sessionId" name="sessionId" value="'.$_SESSION['session_id_'.$this->campaignId].'"/>';
                return (object) array('hiddenInputs'=> '<div style="display:none;">'.$element.' </div>'); //return the first product filtered
            }else{
                $element.='<input type="hidden" id="token" name="token" token="'. $_SESSION['token'].'"/>'; // add token             
                $element.='<input type="hidden" id="prodCurrency" name="prodCurrency" currencySymbol="'.$this->currencySymbol.'" value="'.$this->currency.'"/>';
                $element.='<input type="hidden" id="merchantName" name="merchantName"  value="'.$this->corporateName.'"/>';
                

                 if(count($this->stripeKeys) > 0){
                    $listCountries=array();
                    foreach ($this->countries as $country) {
                        if (isset($country->countryCode)) {
                            $listCountries[] = $country->countryCode;
                        }
                    }

                    if($this->stripePaymentWallet!=""){
                        $element.='<input type="hidden" id="stripePaymentWallet" name="stripePaymentWallet"  value="'.$this->stripePaymentWallet.'"/>';
                    }
                    
                    $element.="<input type='hidden' id='stripeCountries' name='stripeCountries'  value='".implode(",", $listCountries)."'/>";
                    $element.='<input type="hidden" id="stripePublishableKey_sandbox" name="stripePublishableKey_sandbox"  value="'.$this->stripeKeys["sandbox_publicKey"].'"/>';
                    $element.='<input type="hidden" id="stripePublishableKey_live" name="stripePublishableKey_live"  value="'.$this->stripeKeys["live_publicKey"].'"/>';
                }else{
                    $element.="<input type='hidden' id='stripeCountries' name='stripeCountries'  value='".implode(",", $listCountries)."'/>";
                }    
                    $element.="<input type='hidden' id='userCountry' name='userCountry'  value='".strtoupper($_SESSION['sessionCountry'])."'/>";
                    $element.="<input type='hidden' id='userLang' name='userLang'  value='".$_SESSION['lang']."'/>";
                    if(str_contains($this->funnelPage(), "checkout") || str_contains($this->funnelPage(), "payment")){
                         $element.="<input type='hidden' id='checkoutPaymentType' name='checkoutPaymentType' value='credit-card'/>";
                    }                  
                
                    return (object) array('hiddenInputs'=> '<div style="display:none;">'.$element.' </div>',
                                  'product' => $filtered,
                                  'price' => $defaultPrice,
                                  'qty' => $defaultQty,
                                  'maxOrderQty' => $maxOrderQty,
                                  'pricexqty' => $defaultActualPrice); //return the first product filtered

            }                
    }

    public function formatNumber($amount){
          $forceToZeroDecimal=number_format($amount, 2);
          $decimal= (is_numeric($amount) && floor($amount) != $amount) ? 2 : 0 ;

          if($this->currencySymbol=="NT$" || $this->currencySymbol=="¥" || $this->currencySymbol=="₩" ){
                $forceToZeroDecimal=number_format($amount, 0);
          }
          if($this->countryName=="Indonesia"){
                return $this->currencySymbol.number_format($amount, 0, ',', '.');
          }
          if($this->countryName=="Netherlands" && $this->currencySymbol=="€"){
            return $this->currencySymbol.' '.number_format($amount, $decimal, ',', '.');
          } else if($this->currencySymbol=="€"){                    
            return number_format($amount, $decimal, ',', '.') . ' '.$this->currencySymbol;
          }

          if($this->currencySymbol=="₫"){
            return number_format(round($amount), 0, ',', '.') . ' '.$this->currencySymbol;
          }

          if($this->currencySymbol=="MX$"){
            return "$".(($amount == floor($amount)) ? number_format($amount, 0) : $forceToZeroDecimal). ' MXN';
          }

          if($this->currencySymbol=="RON"){
            return number_format($amount, $decimal, ',', '.'). ' lei';
          }

          if($this->currencySymbol=="PKRs"){
            return "PKR".' '.number_format($amount, 0);
          }

          if($this->currencySymbol=="Ft" || $this->currencySymbol=="zł" || $this->currencySymbol=="Kč" || $this->currencySymbol=="Kr"){
            if($this->currencySymbol=="Kr"){
                return number_format($amount,$decimal, ',', ' ') . ' '.strtolower($this->currencySymbol).".";
            }else{
                return number_format($amount,$decimal, ',', ' ') . ' '.$this->currencySymbol;
            }

          }

          if($this->currencySymbol=="QR"){
            return $this->currencySymbol." ".(($amount == floor($amount)) ? number_format($amount, 0) : $forceToZeroDecimal);    
          }

          if($this->currencySymbol=="TL"){
            return number_format($amount, $decimal, ',', '.')." ".$this->currencySymbol;
          }

          return $this->currencySymbol."".(($amount == floor($amount)) ? number_format($amount, 0) : $forceToZeroDecimal);      
    }

    public function detectBrowserLanguage($available,$default='en')
    {  
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return $default;
        }

        $availableKeys = array_keys($available);

        $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $parsed = [];

        // Step 1: Parse header
        foreach ($langs as $lang) {
            if (strpos($lang, ';q=') !== false) {
                list($code, $q) = explode(';q=', $lang);
            } else {
                $code = $lang;
                $q = 1.0;
            }

            $code = strtolower(trim($code));
            $parsed[$code] = (float)$q;
        }

        arsort($parsed);
        $newArray = array_slice($parsed, 0, 1, true);
      
        // Step 2: Matching logic
        foreach ($newArray as $browserLang => $q) {

            // Exact match (pt-br === pt-br)
            if (isset($available[$browserLang])) {
                $this->browserLangDefault=(!isset($_SESSION['lang'])) ? $browserLang : $_SESSION['lang'];
                return $this->browserLangDefault;
            }

            // Extract base (pt-br → pt)
            $base = explode('-', $browserLang)[0];

            // Special-case Chinese: script/region determines Hant vs Hans
            if ($base === 'zh') {
                $traditionalRegions = ['zh-tw', 'zh-hk', 'zh-mo', 'zh-hant'];
                $simplifiedRegions  = ['zh-cn', 'zh-sg', 'zh-hans'];

                if (in_array($browserLang, $traditionalRegions, true) && isset($available['zh-hant'])) {
                    $this->browserLangDefault = (!isset($_SESSION['lang'])) ? 'zh-hant' : $_SESSION['lang'];
                    return $this->browserLangDefault;
                }

                if (in_array($browserLang, $simplifiedRegions, true) && isset($available['zh-hans'])) {
                    $this->browserLangDefault = (!isset($_SESSION['lang'])) ? 'zh-hans' : $_SESSION['lang'];
                    return $this->browserLangDefault;
                }

                // Bare "zh" with no region — pick a sensible default (simplified is the common web default)
                if (isset($available['zh-hans'])) {
                    $this->browserLangDefault = (!isset($_SESSION['lang'])) ? 'zh-hans' : $_SESSION['lang'];
                    return $this->browserLangDefault;
                }
            }

            //Base match (en-US → en)
            if (isset($available[$base])) {
                $this->browserLangDefault = (!isset($_SESSION['lang'])) ? $base : $_SESSION['lang'];
                return $this->browserLangDefault;
            }

            //Prefix match (zh → zh-hant)
            foreach ($availableKeys as $key) {
                if (str_starts_with($key, $base)) {
                    $this->browserLangDefault = (!isset($_SESSION['lang'])) ? $key : $_SESSION['lang'];
                    return $this->browserLangDefault;
                }
            }
        }

        // fallback
        //return $default;
        return null;
    }
    
    public function languages($subFolder=""){
        $directory = BASEPATH.$subFolder."/lang"; // Replace with your directory path
        $folders = array();

        if (is_dir($directory)) {
            // Get all files and directories in the specified path
            $items = scandir($directory);
            $activeLang = "English";

            foreach ($items as $item) {
                // Skip the special "." (current directory) and ".." (parent directory) entries
                if ($item !== '.' && $item !== '..') {
                    $path = $directory . DIRECTORY_SEPARATOR . $item;
                    // Check if the item is a directory
                    if (is_dir($path) && $item !="system") {
                        switch ($item){
                            case 'de':
                                $folders['de'] = 'Deutsch'; 
                                break;

                            case 'pl':
                                $folders['pl'] = 'Polski'; 
                                break;
                                
                            case 'ja':
                                $folders['ja'] = '日本語';
                                break;

                            case 'pt-br':
                                $folders['pt-br'] = 'Português (Brasil)'; 
                                break;

                            case 'zh-hant':
                                $folders['zh-hant'] = '繁體中文';
                                break;  
                                
                            case 'zh-hans':
                                $folders['zh-hans'] = '简体中文';
                                break;  
                            
                            case 'fr':
                                $folders['fr'] = 'Français';
                                break;

                            case 'es':
                                $folders['es'] ='Español (ES)';
                                break;
                            
                            case 'ko':
                                $folders['ko'] ='한국어';
                                break;

                            case 'en':
                                $folders['en'] = 'English' ; 
                                break;

                            case 'tl':
                                $folders['tl'] = 'Tagalog' ;
                                break;

                            case 'cs':
                                $folders['cs'] = 'Český' ;
                                break;

                            case 'da':
                                $folders['da'] = 'Dansk' ;
                                break;

                            case 'hu':
                                $folders['hu'] = 'Magyar' ;
                                break;

                            case 'it':
                                $folders['it'] = 'Italiano' ;
                                break;

                            case 'fi':
                                $folders['fi'] = 'Suomi' ;
                                break;

                            case 'nl':
                                $folders['nl'] = 'Nederlands' ;
                                break;

                            case 'mx':
                                $folders['mx'] = 'Español (MX)' ;
                                break;

                            case 'vi':
                                $folders['vi'] = 'Tiếng Việt' ;
                                break;

                            case 'ca':
                                $folders['ca'] = 'Catalan' ;
                                break;

                            case 'id':
                                $folders['id'] = 'Bahasa Indonesia' ;
                                break;

                            case 'pt-pt':
                                $folders['pt-pt'] = 'Português' ;
                                break;

                            case 'ar':
                                $folders['ar'] = 'العربية' ;
                                break;

                            case 'ro':
                                $folders['ro'] = 'Română' ;
                                break;
                            
                            case 'tr':
                                $folders['tr'] = 'Türkçe' ;
                                break;

                            case 'lb':
                                $folders['lb'] = 'Luxembourgish' ;
                                break;

                            case 'ur':
                                $folders['ur'] = 'اردو' ;
                                break;

                            case 'sv':
                                $folders['sv'] = 'Svenska' ;
                                break;

                        }
                       
                    }
                }
            } 
        } 
            
        return $folders;
    }

    public function definedLanguages(){        

            if($this->geoLangSelected){
                return array(
                    "active" => $this->targetLanguage,
                    "active_lang" =>  $this->languages()[$this->targetLanguage],
                    "languages" => $this->languages()
                );
            }else if (isset($_SESSION['lang'])){
                return array(
                    "active" => $_SESSION['lang'],
                    "active_lang" =>  $this->languages()[$_SESSION['lang']],
                    "languages" => $this->languages()
                );
            }else{
                $browserLangSelect=$this->browserLangDefault;
                return array(
                    "active" => (isset($_GET['lang'])) ? $_GET['lang'] : $browserLangSelect,
                    "active_lang" => (isset($_GET['lang']) && $this->languages()[$_GET['lang']]!=null) ? $this->languages()[$_GET['lang']]: $this->languages()[$browserLangSelect],
                    "languages" =>$this->languages()
                );
            }
        
    }

    public function requestURI($removeFile=true){

        $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

        // Split into segments
        $parts = explode('/', trim($uri, '/'));

        // If last segment looks like a filename (contains a dot), remove it
        if ((strpos(end($parts), '.') !== false || strpos(end($parts),'integrated') !==false) && $removeFile) {
            array_pop($parts);
        }else if (!$removeFile && strpos(end($parts),'integrated') !==false){
            array_pop($parts);
        }
       
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/".((implode("/",$parts)!=="") ? implode("/",$parts)."/" : "") ;
       
       
    }

    public function funnelPage(){
        // Get the current path (e.g., /glucovitaflow/upsell.php)
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Get the basename (e.g., upsell.php)
        $filename = basename($path);
       
        //check if there .php extension
        if(!str_contains($filename, ".php")){
            return "index";
        }
        // Remove the .php extension (e.g., upsell)
        $pageName = pathinfo($filename, PATHINFO_FILENAME);
        return $pageName;
    }

    private function upsellPageIndexPosition($filename){
        $index = -1;
        foreach ($this->upsells as $key => $step) {
            if ($step['page'] === $filename) {
                $index = $key+1;
                break;
            }
        }
        return $index;
    }

    public function nextFunnelPage($curPage=""){
        if($curPage==""){
            $curPage=$this->funnelPage();
        }
        foreach ($this->upsells as $page) {
            if (isset($page['page']) && $page['page'] === $curPage) {
                return $page; // Return the whole sub-array
            }
        }
        return null; // If not found
    }

    /**
     * Returns the downsell-specific Everflow event id ONLY when the product just
     * accepted on $page is that funnel's no-thanks popup downsell (any configured
     * productId other than productIds[0], which is always the main upsell) and a
     * downsellEverflowEventId is configured for it (currently US config only).
     * Otherwise null, meaning the normal upsell event id should be used.
     *
     * Relies on $_SESSION['stored_products_order_'][...]['product1_id'], which is
     * set from the (popup-swapped) _product_order hidden input on every upsell import.
     */
    public function resolveDownsellEverflowEventId($page=""){
        $funnel = $this->nextFunnelPage($page);
        if (!$funnel || !isset($funnel['downsellEverflowEventId'])) {
            return null;
        }
        $mainPid    = $funnel['productIds'][0] ?? null;
        $orderedPid = $_SESSION["stored_products_order_".$this->campaignId]["product1_id"] ?? null;
        if ($orderedPid !== null && $mainPid !== null
            && (string)$orderedPid !== (string)$mainPid) {
            return $funnel['downsellEverflowEventId'];
        }
        return null;
    }

    public function getDescriptor($productId=""){
        if(isset($_SESSION['order_data_'.$this->campaignId]['items']) && $productId!==""){
            $filtered = array_values(array_filter($_SESSION['order_data_'.$this->campaignId]['items'], function($item) use ($productId) {
                    return $item->productId == $productId;
            }));
            return isset($filtered[0]) ? ($filtered[0]->descriptor ?? '') : '';
        }else{
            return "";
        }
    }

    public function requestMethod(){
        $request_method = $_SERVER["REQUEST_METHOD"];

        switch ($request_method) {           
            case 'POST':
                $this->handlePOST();
                break;
            default:
                http_response_code(405);
                echo json_encode([
                    "result" => "ERROR",
                    "message" => "Method Not Allowed"
                ]);
        }
    }  

    public function ipifyFetch(){
        $ch = curl_init("https://api.ipify.org?format=json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        $clientIp = $data['ip'] ?? '';

        return $clientIp;
    }

    private function targetCurrency($countryCode, $currencyCode=""){
        if($_SERVER['REQUEST_METHOD']==="GET" && isset($_SESSION["manualCurrency"])){
            if(!isset($_GET['orderId']) && !isset($_GET['token'])){ //upsell pages have an orderid, meaning payment using paypal
                //remove or unset session if not upsell
                 unset($_SESSION["manualCurrency"]);   
            }else{
                //retain the country_code if upsell
                $currencyCode=strtoupper($_SESSION["manualCurrency"]);
            }                 
        }

        //for post request
        if(isset($_SESSION["manualCurrency"]) && $_SERVER['REQUEST_METHOD']==="POST"){
            $currencyCode=strtoupper($_SESSION["manualCurrency"]);
        }else{
            $_SESSION["manualCurrency"]=strtolower($currencyCode);
        }
       
        $file = BASEPATH .'/integrated/config_' . strtolower($countryCode) . '_'.strtolower($currencyCode).'.php';  

        if (is_file($file)) {
           $_SESSION["manualCurrency"]=strtolower($currencyCode);
           return (object) array('userCountry'=> strtolower($countryCode),
                                 'userCountryTargetCurrency' =>  strtolower($countryCode) . '_'.strtolower($currencyCode));         
        } else{
           return (object) array('userCountry'=> strtolower($countryCode),
                                 'userCountryTargetCurrency' =>  strtolower($countryCode));
        }
    }

    public function getUserCountry($allowedCountries){
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        // If running on localhost or 127.0.0.1 → development
        if (!in_array($serverName, ['localhost', '127.0.0.1'])) {  
            $loc = $_SERVER['HTTP_CF_IPCOUNTRY']?? $_SERVER['GEOIP_COUNTRY_CODE'] ??  $this->default_country;
        // } else if (isset($_SESSION['sessionCountry'])) {
        //     // On localhost, reuse the session country to avoid hitting Cloudflare on every request
        //     $loc = strtoupper($_SESSION['sessionCountry']);
        } else {
            $url = "https://www.cloudflare.com/cdn-cgi/trace";
            // Initialize cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            $trace = curl_exec($ch);
            curl_close($ch);

            if ($trace === false) {
            return $this->default_country;
            }

            // Parse same as above
            $lines = explode("\n", trim($trace));
            $data = [];
            foreach ($lines as $line) {
                $parts = explode("=", $line, 2);
                if (count($parts) === 2) {
                    $data[$parts[0]] = $parts[1];
                }
            }

            $loc = $data['loc'];
        }  

        $country_code =  in_array($loc ?? $this->default_country, $allowedCountries, true)? $loc: $this->default_country;
        if((isset($_GET['country_code']) && $_GET['country_code']!="" && in_array(strtoupper($_GET['country_code']), $allowedCountries, true))){
            $country_code=strtoupper($_GET['country_code']);        
        }else if($_SERVER['REQUEST_METHOD']==="GET" && isset($_SESSION["sessionCountry"])){
            if(!isset($_GET['orderId']) && !isset($_GET['token'])){ //upsell pages have an orderid, meaning payment using paypal
                //remove or unset session if not upsell
                 unset($_SESSION["sessionCountry"]);   
            }else{
                //retain the country_code if upsell
                $country_code=strtoupper($_SESSION["sessionCountry"]);
            }
                 
        }
        
        
        //for post request
        if(isset($_SESSION["sessionCountry"]) && $_SERVER['REQUEST_METHOD']==="POST"){
            $country_code=strtoupper($_SESSION["sessionCountry"]);
        }else{
            $_SESSION["sessionCountry"]=strtolower($country_code);
        }

        $this->sessionCountry=strtolower($country_code);       

        $this->getCountryName($country_code); 

        return $this->targetCurrency($country_code, $_GET['currency_']);
    }

    public function getCountryName($country_code){
        switch ($country_code){

            case "US":
                $this->countryName= "United States";
                break;
            
            case "PL":
                $this->countryName= "Poland";
                break;

            case "JP":
                $this->countryName= "Japan";
                break;

            case "DE":
                $this->countryName= "Germany";
                break;

            case "CA":
                $this->countryName= "Canada";
                break;

            case "CH":
                $this->countryName= "Switzerland";
                break;

            case "AT":
                $this->countryName= "Austria";
                break;
            case "PT":
                $this->countryName= "Portugal";
                break;

            case "BR":
                $this->countryName= "Brazil";
                break;

            case "AU":
                $this->countryName= "Australia";
                break;

            case "GB":
                $this->countryName= "United Kingdom";
                break;

            case "NZ":
                $this->countryName= "New Zealand";
                break;

            case "IE":
                $this->countryName= "Ireland";
                break;

            case "PH":
                $this->countryName= "Philippines";
                break;

            case "SG":
                $this->countryName= "Singapore";
                break;

            case "IN":
                $this->countryName= "India";
                break;
            
            case "FR":
                $this->countryName= "France";
                break;

            case "ES":
                $this->countryName= "Spain";
                break;

            case "HK":
                $this->countryName= "Hong Kong";
                break;
            
            case "MY":
                $this->countryName= "Malaysia";
                break;

            case "TW":
                $this->countryName= "Taiwan";
                break;

            case "SE":
                $this->countryName= "Sweden";
                break;

            case "MO":
                $this->countryName= "Macao";
                break;
            
            case "LU":
                $this->countryName= "Luxembourg";
                break;

            case "BE":
                $this->countryName= "Belgium";
                break;

            case "MC":
                $this->countryName= "Monaco";
                break;
            
            case "TH":
                $this->countryName= "Thailand";
                break;  
                
            case "KR":
                $this->countryName= "South Korea";
                break; 
            
            case "NL":
                $this->countryName= "Netherlands";
                break; 

            case "MX":
                $this->countryName= "Mexico";
                break;

            case "DK":
                $this->countryName= "Denmark";
                break;

            case "CZ":
                $this->countryName= "Czech Republic";
                break;

            case "HU":
                $this->countryName= "Hungary";
                break;

            case "IT":
                $this->countryName= "Italy";
                break;

            case "FI":
                $this->countryName= "Finland";
                break;

            case "VN":
                $this->countryName= "Vietnam";
                break;

            case "ID":
                $this->countryName= "Indonesia";
                break;
            case "QA":
                $this->countryName= "Qatar";
                break;

            case "RO":
                $this->countryName= "Romania";
                break;

            case "TR":
                $this->countryName= "Turkey";
                break;

            case "PK":
                $this->countryName= "Pakistan";
                break;
            default:
                $this->countryName= "United States";
                break;                

        }
    }

    public function getClientIp(): string {
        // Get the default remote IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        // Check for X-Forwarded-For header (can contain multiple IPs)
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Take the first IP in the list
            $forwardedFor = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $firstIp = trim($forwardedFor[0]);
            if (!empty($firstIp)) {
                $ipAddress = $firstIp;
            }
        }

        // If still empty, check for X-Real-IP header
        if (empty($ipAddress) && !empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ipAddress = $_SERVER['HTTP_X_REAL_IP'];
        }else if(empty($ipAddress)){
            $ipAddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        // Validate and return a clean IP string (IPv4 or IPv6)
        return filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : '';
    }

    public function landers_clicks_import($params="",$setInvalidView=true){
        if (session_status() == PHP_SESSION_ACTIVE) {	
         
            if (!isset($_SESSION["affId"]) || (isset($_GET["affId"]) && $_SESSION['affId']!=$_GET["affId"])) {               
                $_SESSION['affId'] = $_GET["affId"];
            }else if (!isset($_SESSION["affId"]) || (isset($_GET["affid"]) && $_SESSION['affId']!=$_GET["affid"])) {           
                $_SESSION['affId'] = $_GET["affid"];
            }
            
            if (!isset($_SESSION["c1"]) || (isset($_GET["c1"]) && $_SESSION['c1']!=$_GET["c1"])) {
                $_SESSION["c1"] = $_GET["c1"];
            }
            
            if (!isset($_SESSION["c2"]) || (isset($_GET["c2"]) && $_SESSION['c2']!=$_GET["c2"])) {
                $_SESSION["c2"] = $_GET["c2"];
            }
            
            if (!isset($_SESSION["c3"]) || (isset($_GET["c3"]) && $_SESSION['c3']!=$_GET["c3"])) {
                $_SESSION["c3"] = $_GET["c3"];
            }

            if (!isset($_SESSION["sub5"]) || (isset($_GET["sub5"]) && $_SESSION['sub5']!=$_GET["sub5"])) {
                $_SESSION["sub5"] = $_GET["sub5"];
            }

            //sub8 for holiday banner
            if (!isset($_SESSION["sub8"]) || (isset($_GET["sub8"]) && $_SESSION['sub8']!=$_GET["sub8"])) {
                $_SESSION["sub8"] = $_GET["sub8"];
            }

            //sub9 for dynamic headlines
            if (!isset($_SESSION["sub9"]) || (isset($_GET["sub9"]) && $_SESSION['sub9']!=$_GET["sub9"])) {
                $_SESSION["sub9"] = $_GET["sub9"];
            }

            if (!isset($_SESSION["custom_config"]) || (isset($_GET["custom_config"]) && $_SESSION['custom_config']!=$_GET["custom_config"])) {
                $_SESSION["custom_config"] = $_GET["custom_config"];
            }
         
         
            $pageType="";
            //get pageType through filenames 
            if($this->funnelPage()=="checkout"  || 
                $this->funnelPage() =="payment" || 
                ($this->checkoutType=="onePage" && $this->funnelPage() =="index")){  
             
                if(isset($_SESSION["step2"]))  { //check basin hingbalik na sya og order kun exists step2 reset session_id
                    unset($_SESSION['session_id_'.$this->campaignId]);
                    unset( $_SESSION["step2"]); //remove session step2
                    unset($_SESSION['order_id_'.$this->campaignId]); 
                    
                    //unset affiliate stored parameters
                    unset($_SESSION['affId']);
                    unset($_SESSION["c1"]);
                    unset($_SESSION["c2"]);
                    unset($_SESSION["c3"]);
                    unset($_SESSION["sub5"]);

                    //custom_config kun naay lain nga setup nga ila gusto itarget tas naa nay existing
                    unset($_SESSION["custom_config"]);
                }  

                $pageType="checkoutPage";
            }else if($this->funnelPage()=="thankyou"){
                //unset($_SESSION['session_id_'.$this->campaignId]); //end of transaction reset session_id
                $pageType="thankyouPage";
               
            }else{
               
                $index=$this->upsellPageIndexPosition($this->funnelPage());
                if($index!=-1){
                    $_SESSION["step2"]=true; //this is to indicate that it proceed from checkout                                 
                    $pageType="upsellPage".$index;
                    
                }else{
                    $pageType="leadPage";  
                    if(isset($_SESSION["step2"]))  { //check basin hingbalik na sya og order kun exists step2 reset session_id
                        unset($_SESSION['session_id_'.$this->campaignId]);
                        unset( $_SESSION["step2"]); //remove session step2
                        unset($_SESSION['order_id_'.$this->campaignId]); 

                        //unset affiliate stored parameters
                        unset($_SESSION['affId']);
                        unset($_SESSION["c1"]);
                        unset($_SESSION["c2"]);
                        unset($_SESSION["c3"]);
                        unset($_SESSION["sub5"]);
                    }                           
                }
            }
            
            $this->currentPageType=$pageType;
            $uri = $_SERVER["REQUEST_URI"];
            
            $queryString = parse_url($uri, PHP_URL_QUERY);
            $urlPath = $queryString ? '?' . $queryString : '';
            if(strpos(basename(parse_url($uri, PHP_URL_PATH)), ".php") !==false ){
                $urlPath = basename(parse_url($uri, PHP_URL_PATH)).$urlPath;
            }
            $params=array(
				"affId" 			=> $_SESSION["affId"],
				"sourceValue1" 	    => $_SESSION["c1"],
				"sourceValue2" 	    => $_SESSION["c2"],
				"sourceValue3" 	    => $_SESSION["c3"],
                "campaignId" 		=> $this->campaignId,
                "sessionId"         => $_SESSION['session_id_'.$this->campaignId],
				"pageType"			=> $pageType,
				"ipAddress" 		=> $this->getClientIp(),				
				"userAgent" 		=> isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "",
				"requestUri" 		=> urlencode($this->requestURI().$urlPath)
			);

            
            if(!isset($_SESSION['session_id_'.$this->campaignId])){
                unset($params['sessionId']);
                $_SESSION['first_landpage_'.$this->campaignId]=preg_replace('#/$#', '', $this->requestURI().$urlPath);
            } 

            if($pageType!=="thankyouPage"){
                $resp=$this->import_click($params);
                $resp_decode = json_decode($resp);
            
                if($resp_decode->result=="SUCCESS"){
                    $_SESSION['session_id_'.$this->campaignId] = $resp_decode->message->sessionId;                    
                }
            }            
            $this->invalidView(setInvalidView:$setInvalidView);
	    }
    }

    public function upsellPageNumber(){ 
        return $this->upsellPageIndexPosition($this->funnelPage());
    }

    public function totalUpsellPage(){ 
        return count($this->upsells);
    }

    private function messageCheck($message,$result){
        $decline=false;

        if (isset($message) && is_object($message)) { 
           
            foreach ($message as $key => $value) {  
                $translated = $this->deepLTranslate($value);
                $translatedKey = $this->deepLTranslate($key);
                
                if (stripos($value, 'decline') !== false) {
                    $decline=true;
                }

                unset($message->$key);
                $message->$translatedKey = $translated;
                           
            }            
            echo json_encode([
                "result" => $result,
                "message" => $message,
                "decline" => $decline
            ]); 
        }else{  
            if (stripos($message, 'decline') !== false) {
                    $decline=true;
            }         
            
            echo json_encode([
                "result" => $result,
                "message" => $this->deepLTranslate($message),
                "decline" => $decline
            ]);
        }
    }

    public function leads_import($data){   
        $data["campaignId"] = $this->campaignId; 
        $data['sessionId'] = $_SESSION['session_id_'.$this->campaignId];
        $data['custom_order_language']=$this->targetLanguage; //send to api the selected language
        $resp=$this->import_lead($data);
        $resp_decode = json_decode($resp);
    
        if($resp_decode->result=="SUCCESS"){ 
            $_SESSION['order_id_'.$this->campaignId] = $resp_decode->message->orderId;  
            echo $resp;
        } else if($resp_decode->result=="ERROR"){
            //$this->messageCheck($resp_decode->message,$resp_decode->result);
        }
    }

    public function link($path){

        if($_SERVER['QUERY_STRING']!=""){
            $params = $_GET;
            $query = http_build_query($params);
            return $path."?".$query;
        }else{
            return $path;
        }
    }

    public function query_customer_order(){
        if(isset($_GET['orderId'])){
            $cacheKey = 'order_cache_' . $this->campaignId . '_' . $_GET['orderId'];
            if (isset($_SESSION[$cacheKey])) {
                $resp = unserialize($_SESSION[$cacheKey]);
            } else {
                $resp = $this->get_order(
                    $_GET['orderId'],
                    $_SESSION['order_data_'.$this->campaignId]['emailAddress'],
                    $_SESSION['order_data_'.$this->campaignId]['campaignId']
                );
                if ($resp !== null) {
                    $_SESSION[$cacheKey] = serialize($resp);
                }
            }

            $this->currencySymbol=$resp->currencySymbol; //currency at thankyou page
            
            return $resp;
        }else{
            if(!isset($_GET["gtmetrix"])){
                header("Location: ".$this->requestURI());
                exit;
            }

        }
    }
    
    public function campaign_query(){
        $resp=$this->get_campaign($this->campaignId);
        echo $resp;
    }

    private function extractOrders($data){
        $result=[];

        unset($_SESSION["stored_products_order_".$this->campaignId]); //reset;
        $_SESSION["stored_products_order_".$this->campaignId] = [];

        foreach ($data as $index => $product){

            foreach($product as $key => $value){
                switch($key){
                    case "productId":
                        $result["product".($index+1)."_id"] = $value;
                        //used value once redirected to the page
                        $_SESSION["stored_products_order_".$this->campaignId]["product".($index+1)."_id"] = $value;
                        break;
                    case "quantity":
                          $result["product".($index+1)."_qty"] = $value;
                          $_SESSION["stored_products_order_".$this->campaignId]["product".($index+1)."_qty"] = $value;                    
                        break;
                    case "shippingprice":
                        $result["product".($index+1)."_shipPrice"] = $value;
                        $_SESSION["stored_products_order_".$this->campaignId]["product".($index+1)."_shipPrice"] =  $value;
                        break;
                    case "variantid":
                        $result["variant".($index+1)."_id"] = $value;
                        $_SESSION["stored_products_order_".$this->campaignId]["variant".($index+1)."_id"] = $value;
                        break;
                    case "price":
                        $result["product".($index+1)."_price"] = $value;
                        $_SESSION["stored_products_order_".$this->campaignId]["product".($index+1)."_price"] = $value;
                        break;
                    case "type":
                        //this is to store main order product for the following upsells pages
                        if($value==="main"){
                            if(isset($product['overrideQty'])){
                                 $result['mainProductQty'] = $product['overrideQty'];
                            }else{
                                 $result['mainProductQty'] = $product['quantity'];
                            }
                                                     
                            $result['mainProductId'] = $product['productId'];
                            $_SESSION['mainProductQty_'.$this->campaignId] = $result['mainProductQty'];
                            $_SESSION['mainProductId_'.$this->campaignId] = $result['mainProductId'];
                        }
                        break;
                    
                }
            }
        }
        //added custom descriptor
        if(isset($this->customDescriptor) && $this->customDescriptor!=""){
             $result['customDescriptor'] = $this->customDescriptor;            
        }
        return $result;
    }

    private function paypal_transaction_confirm($data){
         $data["campaignId"] = $this->campaignId; 
         $data["paypalBillerId"]=$_SESSION['order_data_'.$this->campaignId]['paypalBillerId'];
         $data['couponCode'] = $_SESSION['order_data_'.$this->campaignId]['couponCode'];
         $data['shipProfileId'] = $_SESSION['order_data_'.$this->campaignId]['shipProfileId'];        
         $data['custom1'] = $_SESSION['order_data_'.$this->campaignId]["custom1"];
        
         //sessionId and orderId
         $data['sessionId'] = $_SESSION['session_id_'.$this->campaignId];
         if(isset($_SESSION['order_id_'.$this->campaignId])){
            $data['orderId']=$_SESSION['order_id_'.$this->campaignId];
         }

         $data['custom_order_track_url']=$_SESSION['first_landpage_'.$this->campaignId]; //track url 
         $data['custom_order_language']=$this->targetLanguage; //send to api the selected language

         $data=array_merge($data,$this->extractOrders($_SESSION['order_data_'.$this->campaignId]['orderProducts'])); //orders         
         unset($data['products']);
        
        $data=$this->customFields($data);
        $resp=$this->confirm_paypal($data);
        $resp_decode = json_decode($resp);
        $checkDup_order = $this->check_duplicate_order($resp_decode->message->customerId); //check previous

        if($resp_decode->result=="SUCCESS"){             

            $_SESSION['order_data_'.$this->campaignId]["firstName"]= $resp_decode->message->shipFirstName;
            $_SESSION['order_data_'.$this->campaignId]["lastName"]= $resp_decode->message->shipLastName;
            $_SESSION['order_data_'.$this->campaignId]["emailAddress"]= $resp_decode->message->emailAddress;
            $_SESSION['order_data_'.$this->campaignId]["orderId"]= $resp_decode->message->orderId;           
            $_SESSION['order_data_'.$this->campaignId]["items"]= $resp_decode->message->items;

            unset($_SESSION['token']); //clear token in every successful purchase     
            $resp_decode->message->nextPage = "/".$this->upsells[0]['page'].".php";
            $resp_decode->message->addOnTotal = $_SESSION['order_data_'.$this->campaignId]['addOnTotal'];
            $resp_decode->message->testmode = $data['testmode'];
            $resp_decode->message->country_code = $_SESSION['sessionCountry'];
            $resp_decode->message->custom_config = $_SESSION['custom_config'];
            
            if(!isset($data['testmode'])){
                unset($resp_decode->message->testmode);
            }

            if(!isset($data['lang'])){
                unset($resp_decode->message->lang);
            } else if(isset($_SESSION['lang'])){
                $resp_decode->message->lang = $_SESSION['lang'];
            } else if($data['lang']!="") {
                $resp_decode->message->lang = $data['lang'];
            } else{
                $resp_decode->message->lang = $this->targetLanguage;
            }

            
        }

        if($resp_decode->result=="ERROR"){
                $this->messageCheck($resp_decode->message,$resp_decode->result);
        }else{
                $resp_decode=$this->logDuplicateOrder($checkDup_order,$resp_decode);//log duplicate
                echo json_encode([
                    "result" => $resp_decode->result,
                    "message" => $resp_decode->message
                ]);              
        }

    }
   
    private function get_paypalURL($data){
        $data["campaignId"] = $this->campaignId; 
        $data["paySource"]="PAYPAL"; 
      
        //sessionId and orderId
        $data['sessionId'] = $_SESSION['session_id_'.$this->campaignId];
        if(isset($_SESSION['order_id_'.$this->campaignId])){
            $data['orderId']=$_SESSION['order_id_'.$this->campaignId];
        }

        $data['userAgent']=$_SERVER['HTTP_USER_AGENT'];
        if ($data["testmode"]=="true" || $data["testmode"]==true){
            $data["paypalBillerId"]=$this->paypalSandboxId;
        } else {
            $data["paypalBillerId"]=$this->paypalProductionId;
        }
          
        $data=array_merge($data,$this->extractOrders($data['products'])); //orders
        $orderProducts = $data["products"];
        unset($data['products']);

        $data=$this->customFields($data);
        $resp=$this->import_order($data);
        $resp_decode = json_decode($resp);

        if($resp_decode->result=="SUCCESS"){ 
            unset($_SESSION['order_data_'.$this->campaignId]); //clear order data first

            $_SESSION['order_data_'.$this->campaignId] =[
                "campaignId" => $this->campaignId,                
                "mainProductQty" => $data['mainProductQty'],
                "mainProductId" => $data['mainProductId'],
                "mainProductPrice" => $data['mainProductPrice'],
                "paySource" => $data["paySource"],
                "orderProducts" => $orderProducts,
                "couponCode"=> $data['couponCode'],
                "shipProfileId"=> $data['shipProfileId'],
                "marketingOptIn"=> $data['marketingOptIn'],
                "custom1"=> $data['custom1'],
                "paypalBillerId" =>$data["paypalBillerId"],
                "addOnTotal" =>$data['addOnTotal'],
              
            ];
        }
        
        if($resp_decode->result=="ERROR"){
                $this->messageCheck($resp_decode->message,$resp_decode->result);
        }else{
                echo json_encode([
                    "result" => $resp_decode->result,
                     "message" => $resp_decode->message
                ]);              
        }
       
    }

    private function get_paypalURL_direct_api($data){
        $data["campaignId"] = $this->campaignId; 
        $data["paySource"]="PAYPAL"; 
        
        //sessionId and orderId
        $data['sessionId']=$_SESSION['session_id_'.$this->campaignId];
        if(isset($_SESSION['order_id_'.$this->campaignId])){
            $data['orderId']=$_SESSION['order_id_'.$this->campaignId];
        }


        $data['userAgent']=$_SERVER['HTTP_USER_AGENT'];
        if ($data["testmode"]=="true" || $data["testmode"]==true){
            $data["forceMerchantId"]=$this->paypalSandboxId;
        } else {
            $data["forceMerchantId"]=$this->paypalProductionId;
        }
          
        $data=array_merge($data,$this->extractOrders($data['products'])); //orders
        $orderProducts = $data["products"];
        unset($data['products']);

        $data=$this->customFields($data);
        $resp=$this->import_order($data);
        $resp_decode = json_decode($resp);

        if($resp_decode->result=="SUCCESS"){ 
            unset($_SESSION['order_data_'.$this->campaignId]); //clear order data first

            $_SESSION['order_data_'.$this->campaignId] =[
                "campaignId" => $this->campaignId,                
                "mainProductQty" => $data['mainProductQty'],
                "mainProductId" => $data['mainProductId'],
                "mainProductPrice" => $data['mainProductPrice'],
                "paySource" => $data["paySource"],
                "orderProducts" => $orderProducts,
                "couponCode"=> $data['couponCode'],
                "shipProfileId"=> $data['shipProfileId'],
                "marketingOptIn"=> $data['marketingOptIn'],
                "custom1"=> $data['custom1'],
                "paypalBillerId" =>$data["paypalBillerId"],
                "addOnTotal" =>$data['addOnTotal'],
              
            ];
        }

        if($resp_decode->result=="ERROR"){
            $this->messageCheck($resp_decode->message,$resp_decode->result);
        }else{
            echo json_encode([
                "result" => $resp_decode->result,
                "message" => $resp_decode->message
            ]);              
        }
    }

    private function logDuplicateOrder($checkDup_order,$resp_decode){
            $checkDupDecode= json_decode($checkDup_order);
            
            //log duplicate order here
            if($checkDup_order!=null){
                $resp_decode->message->duplicateOrder = (($checkDupDecode->foundOrders > 1) ? true : false);
                if($resp_decode->message->duplicateOrder){
                    $this->customer_add_note(
                        array(
                            "customerId" =>$checkDupDecode->customerId,
                            "message" => "Order ID ".$resp_decode->message->orderId." is a duplicate order. Customer Id:".$checkDupDecode->customerId." placed order(s) within the last 30 days."
                        )
                    );                     
                }else{
                    unset($resp_decode->message->duplicateOrder);
                }          
            }

            return $resp_decode;
    }

    private function customFields($data){

        if(isset($data['cpf'])){
            $data['custom_order_brazil_cpf']= $data['cpf'];          
        }

        if(isset($data['consigneeId'])){
            $data['custom_order_consigneeId']= $data['consigneeId'];          
        }
        
        if(isset($data['pccc'])){
            $data['custom_order_personal_customs_clearance_code']= $data['pccc'];          
        }       


        if(isset($data['barangay'])){
            $data['custom_order_barangay']= $data['barangay'];
        }

        if(isset($data['houseno'])){
            if(isset($data['shipAddress1']) && $data['shipAddress1']!=""){
                    $data['shipAddress1']=$data['shipAddress1'].", ". $data['houseno'];
            }
            
            if(isset($data['shipCountry']) && $data['shipCountry']=="BR"){
                $data['custom_order_brazil_houseNo']= $data['houseno'];
            }else{ 
                $data['custom_order_houseNo']= $data['houseno'];
            }                      
        }

        if(isset($data['apt_unit'])){
            if(isset($data['shipAddress1']) && $data['shipAddress1']!=""){
                    $data['shipAddress1']=$data['shipAddress1'].", ". $data['apt_unit'];
            }
            
            $data['custom_order_apt_unit']= $data['apt_unit'];                     
        }

        if(isset($data['barangay'])){
            if(isset($data['shipAddress1']) && $data['shipAddress1']!=""){
                    $data['shipAddress1']=$data['shipAddress1'].", Brgy. ". $data['barangay'];
            }                                 
        }        

        if(isset($data['bairro'])){
            if(isset($data['shipAddress2']) && $data['shipAddress2']!=""){
                $data['shipAddress2']=$data['shipAddress2'].", ". $data['bairro'];
            }else{
                 $data['shipAddress1']=$data['shipAddress1'].", ". $data['bairro'];
            }
            
            $data['custom_order_brazil_bairro']= $data['bairro'];          
        }

        if(isset($data['neighborhood'])){
            if(isset($data['shipCountry']) && $data['shipCountry']=="MX"){
                $data['shipAddress2']=$data['shipAddress2']." ". $data['neighborhood'];
            }else if(isset($data['shipCountry']) && $data['shipCountry']=="ID"){
                $data['shipAddress1']=$data['shipAddress1'].", ". $data['neighborhood'];
            }
            $data['custom_order_neighborhood']= $data['neighborhood'];
        }

        if(isset($data['rt']) || isset($data['rw'])){
            if(isset($data['rt']) && isset($data['rw'])){
                $data['shipAddress2']=$data['shipAddress2'].", RT ". $data['rt']."/ RW ". $data['rw'];   
                $data['custom_order_rt']= $data['rt'];
                $data['custom_order_rw']= $data['rw'];        
            } else if(isset($data['shipCountry']) && $data['shipCountry']=="ID"){
                $data['shipAddress2']=$data['shipAddress2'].", RT ". $data['rt'];
                $data['custom_order_rt']= $data['rt']; 
            } else if(isset($data['shipCountry']) && $data['shipCountry']=="ID"){
                $data['shipAddress2']=$data['shipAddress2'].", RW ". $data['rw'];
                $data['custom_order_rw']= $data['rw']; 
            }   
        }

       
        if(isset($data['district'])){           
            if(isset($data['shipCountry']) && $data['shipCountry']=="ID"){
                $data['shipAddress2']=$data['shipAddress2'].", ". $data['district'];
                $data['custom_order_district']= $data['district']; 
            }else{
                $data['custom_order_district']= $data['district']; 
            }                     
        }


        if(isset($data['village'])){
            if(isset($data['shipCountry']) && $data['shipCountry']=="ID"){
                $data['shipAddress2']=$data['shipAddress2']." ". $data['village'];
                $data['custom_order_village']= $data['village']; 
            }else{
                $data['custom_order_village']= $data['village'];
            }             
        } 

        if(isset($data['ward'])){           
                $data['custom_order_ward']= $data['ward'];
        }

        if(isset($data['county'])){         
                $data['custom_order_county']= $data['county']; 
        }
        
        if(isset($data['tax_id'])){
            if($data['shipCountry']=="MX"){
                $data['custom_order_mx_rfc_curp']= $data['tax_id'];
            }else if($data['shipCountry']=="ID"){
                 $data['custom_order_id_npwp_nik']= $data['tax_id'];
            }else if($data['shipCountry']=="PK"){
                 $data['custom_order_identity_card_or_passport']= $data['tax_id'];
            }              
        }        

        if(isset($data['shipCountry']) && $data['shipCountry']=="VN"){
            if(!isset($data['shipPostalCode']) || $data['shipPostalCode']==""){
                $data['shipPostalCode']= (isset($data['postalCode']) && $data['postalCode']!="") ? $data['postalCode'] : "000000";
            }
            if(!isset($data['postalCode']) || $data['postalCode']==""){
                $data['postalCode']= (isset($data['shipPostalCode']) && $data['shipPostalCode']!="") ? $data['shipPostalCode'] : "000000";
            }
        }

        if(isset($_SESSION["affId"])){
            $data['affId']=$_SESSION["affId"];             
        }else{
            $data['affId']="";      
        }
        
        if(isset($_SESSION["c1"])){
            $data['sourceValue1']=$_SESSION["c1"]; 
        }else{
            $data['sourceValue1']="";
        }

        if(isset($_SESSION["c2"])){
            $data['sourceValue2']=$_SESSION["c2"]; 
        }else{
            $data['sourceValue2']="";
        }

        if(isset($_SESSION["c3"])){
            $data['sourceValue3']=$_SESSION["c3"]; 
        }else{
            $data['sourceValue3']="";
        }

        if(isset($_SESSION["sub5"])){
            $data['custom_order_sub5']= $_SESSION["sub5"];
        }

        if(isset($_SESSION["custom_config"])){
            $data['custom_order_custom_config']= $_SESSION["custom_config"];
        }

        //sub9 is for everflow and sub9 is also use for dynamic headlines 
        if(isset($_SESSION["sub9"])){
            $data['custom_order_dynamic_headlines']= $_SESSION["sub9"];
        }

        //sub8is for everflow and sub8 is also use for holiday banner
        if(isset($_SESSION["sub8"])){
            $data['custom_order_holiday_banner']= $_SESSION["sub8"];
        }

        if(isset($data['custom1']) && $data['custom1']=="" && isset($_SESSION['direct_link_'.$this->campaignId])){
            $data['custom1']= $_SESSION['direct_link_'.$this->campaignId];
        } else if(!isset($data['custom1']) && isset($_SESSION['direct_link_'.$this->campaignId])){
            $data['custom1']= $_SESSION['direct_link_'.$this->campaignId];             
        }

        return $data;
    }

    private function importOrder($data){
 
        if ($data["testmode"]=="true" || $data["testmode"]==true){
            $data["forceMerchantId"] = $this->stripeSandboxId;
        }

        $data["campaignId"] = $this->campaignId; 
        $data["paySource"] = "CREDITCARD";     

        //sessionId and orderId
        $data['sessionId'] = $_SESSION['session_id_'.$this->campaignId];
        if(isset($_SESSION['order_id_'.$this->campaignId])){
            $data['orderId']=$_SESSION['order_id_'.$this->campaignId];
        }

        $data['userAgent']=$_SERVER['HTTP_USER_AGENT'];
       
        //shippingInfo
        foreach ($data['shippingInfo'] as $key => $value){
            $data[$key]=$value;
        }

        //billing info
        foreach ($data['billingInfo'] as $key => $value){
            $data[$key]=$value;
        }

        $data=array_merge($data,$this->extractOrders($data['products'])); //orders        
        $orderProducts = $data["products"];

        unset($data["billingInfo"]);
        unset($data["products"]);
        unset($data["shippingInfo"]);

        $data['custom_order_track_url']=$_SESSION['first_landpage_'.$this->campaignId]; //track url 
        $data['custom_order_language']=$this->targetLanguage; //send to api the selected language

        $data=$this->customFields($data);
        $resp=$this->import_order($data);
        $resp_decode = json_decode($resp);
        $checkDup_order = $this->check_duplicate_order($resp_decode->message->customerId); //check previous
      
        if($resp_decode->result=="SUCCESS"){ 
            unset($_SESSION['order_data_'.$this->campaignId]); //clear order data first

            $_SESSION['order_data_'.$this->campaignId] =[
                "campaignId" => $this->campaignId,
                "emailAddress" => $resp_decode->message->emailAddress,
                "firstName" => $data['shipFirstName'],
                "lastName" => $data['shipLastName'],
                "mainProductQty" => $data['mainProductQty'],
                "mainProductId" => $data['mainProductId'],
                "mainProductPrice" => $data['mainProductPrice'],
                "orderId" => $resp_decode->message->orderId,
                "paySource" => $resp_decode->message->paySource,
                "orderProducts" => $orderProducts, 
                "items"=>  $resp_decode->message->items,
                "couponCode"=> $data['couponCode'],
                "shipProfileId"=> $data['shipProfileId'],
                "marketingOptIn"=> $data['marketingOptIn'],
                "forceMerchantId" =>  $data["forceMerchantId"]
            ];

            
            unset($_SESSION['token']); //clear token in every successful purchase     
            $resp_decode->message->nextPage = "/".$this->upsells[0]['page'].".php";
            $resp_decode->message->addOnTotal = $data['addOnTotal'];
            $resp_decode->message->testmode = $data['testmode'];
            $resp_decode->message->country_code = $_SESSION['sessionCountry'];
            $resp_decode->message->custom_config = $_SESSION['custom_config'];
            
            if(!isset($data['testmode'])){
                unset($resp_decode->message->testmode);
            }

            if(!isset($data['lang'])){
                unset($resp_decode->message->lang);
            } else if(isset($_SESSION['lang'])){
                $resp_decode->message->lang = $_SESSION['lang'];
            } else if($data['lang']!="") {
                $resp_decode->message->lang = $data['lang'];
            } else {
                $resp_decode->message->lang = $this->targetLanguage;
            }
             
        }

        if($resp_decode->result=="ERROR"){
            $this->messageCheck($resp_decode->message,$resp_decode->result);
        }else{
            $resp_decode=$this->logDuplicateOrder($checkDup_order,$resp_decode);//log duplicate
            echo json_encode([
                "result" => $resp_decode->result,
                "message" => $resp_decode->message
            ]);              
        }     
    }

    private function importOrderKlarna($data){

        if ($data["testmode"]=="true" || $data["testmode"]==true){
            $data["forceMerchantId"] = $this->stripeSandboxId;
        }else{
             $data["forceMerchantId"] = $this->stripeProductionId;
        }
       
        $data["campaignId"] = $this->campaignId; 
        $data["paySource"] = "PREPAID";
        $data['prepaidType'] = "KLARNA";
       
        //sessionId and orderId
        $data['sessionId'] = $_SESSION['session_id_'.$this->campaignId];
        if(isset($_SESSION['order_id_'.$this->campaignId])){
            $data['orderId']=$_SESSION['order_id_'.$this->campaignId];
        }

        $data['userAgent']=$_SERVER['HTTP_USER_AGENT'];
        
       
        //billing info
        foreach ($data['billingInfo'] as $key => $value){
            $data[$key]=$value;
        }

        
        $data=array_merge($data,$this->extractOrders($data['products'])); //orders  

        $orderProducts = $data["products"];
        $data['shipAddress1']=$data['address1'];
        $data['shipPostalCode']=$data['postalCode'];
        $data['shipCity']=$data['city'];
        $data['shipCountry']=$data['country'];
        $data['shipState']=$data['state'];

        unset($data["billingInfo"]);
        unset($data["products"]);

        $data['custom_order_track_url']=$_SESSION['first_landpage_'.$this->campaignId]; //track url 
        $data['custom_order_language']=$this->targetLanguage; //send to api the selected language

        $data=$this->customFields($data);
        $resp=$this->import_order($data);
        $resp_decode = json_decode($resp);
        $checkDup_order = $this->check_duplicate_order($resp_decode->message->customerId); //check previous

        if($resp_decode->result=="ERROR"){
            $this->messageCheck($resp_decode->message,$resp_decode->result);
        }else{
            $resp_decode=$this->logDuplicateOrder($checkDup_order,$resp_decode); //logDuplicate
            echo json_encode([
                "result" => $resp_decode->result,
                "message" => $resp_decode->message
            ]);              
        }          
    }

    private function importOrderPrepaid($data){

        if ($data["testmode"]=="true" || $data["testmode"]==true){
            $data["forceMerchantId"] = $this->stripeSandboxId;
        }else{
             $data["forceMerchantId"] = $this->stripeProductionId;
        }
       
        $data["campaignId"] = $this->campaignId; 
        $data["paySource"] = "PREPAID";
       
        //sessionId and orderId
        $data['sessionId'] = $_SESSION['session_id_'.$this->campaignId];
        if(isset($_SESSION['order_id_'.$this->campaignId])){
            $data['orderId']=$_SESSION['order_id_'.$this->campaignId];
        }

        $data['userAgent']=$_SERVER['HTTP_USER_AGENT'];        
       
         //shippingInfo
        foreach ($data['shippingInfo'] as $key => $value){
            $data[$key]=$value;
        }

        //billing info
        foreach ($data['billingInfo'] as $key => $value){
            $data[$key]=$value;
        }
        
        
        $data=array_merge($data,$this->extractOrders($data['products'])); //orders          

        unset($data["billingInfo"]);
        unset($data["products"]);
        unset($data["shippingInfo"]);

        $data['custom_order_track_url']=$_SESSION['first_landpage_'.$this->campaignId]; //track url 
        $data['custom_order_language']=$this->targetLanguage; //send to api the selected language
      
        $data=$this->customFields($data);
        $resp=$this->import_order($data);
        $resp_decode = json_decode($resp);
        $checkDup_order = $this->check_duplicate_order($resp_decode->message->customerId); //check previous

        if($resp_decode->result=="ERROR"){
            $this->messageCheck($resp_decode->message,$resp_decode->result);
        }else{
            $resp_decode=$this->logDuplicateOrder($checkDup_order,$resp_decode); //logDuplicate
            echo json_encode([
                "result" => $resp_decode->result,
                "message" => $resp_decode->message
            ]);              
        }          
    }

    private function importUpsell($data){
        
        $data["paySource"] =  $_SESSION['order_data_'.$this->campaignId]['paySource'];        
        if($_SESSION['order_data_'.$this->campaignId]['paySource']==="PAYPAL"){
            $data["forceMerchantId"] = $_SESSION['order_data_'.$this->campaignId]["paypalBillerId"];
        }else if($_SESSION['order_data_'.$this->campaignId]["forceMerchantId"]!==null){
            $data["forceMerchantId"] = $_SESSION['order_data_'.$this->campaignId]["forceMerchantId"];
        } else if ($data["testmode"]=="true" || $data["testmode"]==true){
            $data["forceMerchantId"] = $this->stripeSandboxId;
        } 
               
        $data['sessionId'] = $_SESSION['session_id_'.$this->campaignId];
        $data["orderId"] =  $_SESSION['order_data_'.$this->campaignId]['orderId'];

        $data=array_merge($data,$this->extractOrders($data['products'])); //orders  
        $orderProducts = $data["products"];
        unset($data["products"]);

        $resp=$this->import_upsale($data);
        $resp_decode = json_decode($resp);
        
        if($resp_decode->result=="SUCCESS"){ 
            unset($_SESSION['order_data_upsell'.$this->campaignId]); //clear order data first

            $_SESSION['order_data_'.$this->campaignId]["items"]= $resp_decode->message->items;

            // Track cumulative qty across accepted upsells
            $upsellQty = intval($data['product1_qty'] ?? 0);
            $prevQty = $_SESSION['order_data_'.$this->campaignId]['accumulatedQty'] 
                    ?? $_SESSION['order_data_'.$this->campaignId]['mainProductQty'];
            $_SESSION['order_data_'.$this->campaignId]['accumulatedQty'] = intval($prevQty) + $upsellQty;


            $_SESSION['order_data_upsell'.$this->campaignId] =[
                "orderProducts" => $orderProducts
            ];

            unset($_SESSION['token']); //clear token in every successful purchase
            $resp_decode->message->nextPage = "/".$this->nextFunnelPage($data['page'])['yesBtnTo'].".php";
            $resp_decode->message->testmode = $data['testmode'];
            $resp_decode->message->country_code = $_SESSION['sessionCountry'];
            $resp_decode->message->custom_config = $_SESSION['custom_config'];
            // card (non-3DS) + PayPal reference-transaction upsells: when the no-thanks popup
            // downsell was accepted, tell the client to fire the downsell Everflow event.
            $dsEfEventId = $this->resolveDownsellEverflowEventId($data['page']);
            if ($dsEfEventId !== null) {
                $resp_decode->message->everflowEventId = $dsEfEventId;
            }
            if(!isset($data['testmode'])){
                unset($resp_decode->message->testmode);
            }

            if(!isset($data['lang'])){
                unset($resp_decode->message->lang);
            } else if(isset($_SESSION['lang'])){
                $resp_decode->message->lang = $_SESSION['lang'];
            } else if($data['lang']!="") {
                $resp_decode->message->lang = $data['lang'];
            } else {
                $resp_decode->message->lang = $this->targetLanguage;
            }
        }else if($resp_decode->result=="MERC_REDIRECT" && $data["paySource"] ==="PAYPAL"){
             echo json_encode([
                "result" => "ERROR",
                "message" => $this->deepLTranslate("Reference Transaction was disabled in the gateway.")
            ]); 
            return;        
        }

        if($resp_decode->result=="ERROR"){
            $this->messageCheck($resp_decode->message,$resp_decode->result);
        }else{
            echo json_encode([
                "result" => $resp_decode->result,
                "message" => $resp_decode->message
            ]);              
        } 

    }

    private function noUpsellClick($data){ 
        $lang=null;
        if(isset($_SESSION['lang'])){
            $lang = $_SESSION['lang'];
        } else if($data['lang']!="") {
            $lang = $data['lang'];
        } else {
            $lang = $this->targetLanguage;
        }   
       
        echo json_encode([
                "result" => "SUCCESS",                
                "message"=>[
                    "orderId" => $_SESSION['order_data_'.$this->campaignId]['orderId'],
                    "nextPage" => "/".$this->nextFunnelPage($data['page'])['noBtnTo'].".php",
                    "paypalUrl"=> null,
                    "country_code"=> $_SESSION['sessionCountry'],
                    "lang" => $lang
                ]
        ]);
    }

    public function confirmOrder($data){              
        $resp=$this->confirm_order($data["orderId"]);
        $resp_decode = json_decode($resp);
        echo json_encode([
                "result" => $resp_decode->result,
                "message" => $resp_decode->message
        ]); 
    }

    private function to_cents($amount, $currency): int {
         $zeroDecimalCurrencies = [
            'BIF','CLP','DJF','GNF','JPY','KMF',
            'KRW','MGA','PYG','RWF','UGX',
            'VND','VUV','XAF','XOF','XPF'
        ];

        $currency = strtoupper($currency);

        if (in_array($currency, $zeroDecimalCurrencies, true)) {
            return (int) round($amount);
        }

        return (int) round($amount * 100);        
    }

    private function queryCustomerRec($fname, $lname, $email, $numDays){
         $foundOrders=0;
         $checkCustomer = $this->queryCustomerRecord($fname, $lname, $email, $numDays);
         if($checkCustomer != null){
                       $customerRecord= json_decode($checkCustomer); 
                       $checkExistingorder = $this->check_duplicate_order($customerRecord->customerId, $numDays); //check previous
                       
                       if($checkExistingorder != null){
                            $existingOrderFound= json_decode($checkExistingorder);  
                            $foundOrders=$existingOrderFound->foundOrders;
                       }

         }
        return $foundOrders;
    }

    private function checkEmptyValues($shipField, $billingField){
        if($shipField == "" || $shipField == "."){
            if($billingField == "" || $billingField == "."){
                return ".";
            }else{
                return $billingField;
            }
        }else{
            return $shipField;
        }
    }

    private function stripeExpressOrderImport($data){
            
            $secretKey = $this->stripeKeys['live_secretKey'];
            if ($data["testmode"]=="true" || $data["testmode"]==true){
                $secretKey=$this->stripeKeys['sandbox_secretKey'];
            }

            if($data['paymentType'] == "google_pay" ){
                $data['paySource']= 'GOOGLEPAY';
            }else if($data['paymentType'] == "apple_pay" ){
                $data['paySource']= 'APPLEPAY';
            }else{
                echo json_encode([
                        "result" => "ERROR",
                        "message" => "Invalid Payment Type"
                ]);
                exit;
            }

            //shippingInfo
            foreach ($data['shippingInfo'] as $key => $value){
                $data[$key]=$value;
            }

            //billing info
            foreach ($data['billingInfo'] as $key => $value){
                $data[$key]=$value;
            }   

            $data=$this->customFields($data);
            
            // Ensure Stripe SDK loaded
            if (!class_exists(\Stripe\Stripe::class)) {
                echo json_encode(["result"=>"ERROR","message"=>$this->deepLTranslate("Stripe SDK not loaded. Did you run composer require stripe/stripe-php?")]);
                return;
            }

            if($this->queryCustomerRec($data['firstName'], $data['lastName'], $data['emailAddress'], (int)$this->reOrderDays - 1) > 0){
                echo json_encode(["result"=>"ERROR","message"=>$this->deepLTranslate("You have already purchased from this campaign.")]);
                return;        
            }

            \Stripe\Stripe::setApiKey($secretKey);

            try {

                    $customer = \Stripe\Customer::create([
                        'email' => $data['emailAddress'],
                        'name'  => $data['shippingName'],
                        'phone' => $data['phoneNumber'],
                        'address' => [
                            'line1'       => $data['address1'],
                            'line2'       => $data['address2'],
                            'city'        => $data['city'],
                            'state'       => $data['state'],
                            'postal_code' => $data['postalCode'],
                            'country'     => $data['country']
                        ],
                        'shipping' => [
                            'name' => $data['shippingName'],
                            'phone' => $data['phoneNumber'],
                            'address' => [
                                'line1'       => $data['shipAddress1'],
                                'line2'       => $data['shipAddress2'],
                                'city'        => $data['shipCity'],
                                'state'       => $data['shipState'],
                                'postal_code' => $data['shipPostalCode'],
                                'country'     => $data['shipCountry']
                            ]
                        ]
                    ]);          

                    $paymentIntent = \Stripe\PaymentIntent::create([
                            'amount' => $this->to_cents($data['amount'], $data['currency']), // $49.99
                            'currency' => $data['currency'],
                            //'confirmation_token' => $data['confirmationTokenId'],
                            'payment_method' => $data['paymentMethodId'],
                            'return_url' => $data['return_url'],
                            'setup_future_usage' => 'off_session',
                            'confirmation_method' => 'automatic',
                            'description' => 'Main Product Order',
                            'confirm' => true,
                            'customer' =>  $customer->id
                    ]);  
                    
                    if ($paymentIntent->status === 'succeeded') {
                    
                               $data["campaignId"] = $this->campaignId; 
                               $data['userAgent']=$_SERVER['HTTP_USER_AGENT']; 
                               $data['skipQA']=  true;  
                               //$data['externalOrderId'] = $paymentIntent->id;                             
                               //$data['externalOrderId'] = $charge->id;
                               $data=array_merge($data,$this->extractOrders($data['products'])); //orders        
                               $orderProducts = $data["products"];
			                   $data['address1']= $this->checkEmptyValues(trim($data['address1']), trim($data['shipAddress1']));
                               $data['address2']= $this->checkEmptyValues(trim($data['address2']), trim($data['shipAddress2']));
                               $data['city']=  $this->checkEmptyValues(trim($data['city']), trim($data['shipCity']));
                               $data['state']= $this->checkEmptyValues(trim($data['state']), trim($data['shipState']));
                               $data['postalCode']=  $this->checkEmptyValues(trim($data['postalCode']),trim($data['shipPostalCode']));
                               $data['country']= $this->checkEmptyValues(trim($data['country']), trim($data['shipCountry'])); 
                               
                               $data['shipAddress1']= $this->checkEmptyValues(trim($data['shipAddress1']), trim($data['address1']));
                               $data['shipAddress2']= $this->checkEmptyValues(trim($data['shipAddress2']), trim($data['address2']));
                               $data['shipCity']=  $this->checkEmptyValues(trim($data['shipCity']), trim($data['city']));
                               $data['shipState']= $this->checkEmptyValues(trim($data['shipState']), trim($data['state']));
                               $data['shipPostalCode']=  $this->checkEmptyValues(trim($data['shipPostalCode']),trim($data['postalCode']));
                               $data['shipCountry']= $this->checkEmptyValues(trim($data['shipCountry']), trim($data['country']));

                               $data['shipFirstName']= $this->checkEmptyValues(((preg_match('/\d/', $data['shipFirstName']))? trim($data['firstName']) : trim($data['shipFirstName'])), trim($data['firstName']));
                               $data['shipLastName']= $this->checkEmptyValues(((preg_match('/\d/', $data['shipLastName']))? trim($data['lastName']) : trim($data['shipLastName'])), trim($data['lastName']));

                               $data['custom_order_track_url']=$_SESSION['first_landpage_'.$this->campaignId]; //track url
                               $data['custom_order_language']=$this->targetLanguage; //send to api the selected language
                               
                               //SessionId and OrderId
                               $data['sessionId'] = $_SESSION['session_id_'.$this->campaignId];
                                //if(isset($_SESSION['order_id_'.$this->campaignId])){
                                //   $data['orderId']=$_SESSION['order_id_'.$this->campaignId];
                                //} 

                               unset($data["billingInfo"]);
                               unset($data["products"]);
                               unset($data["shippingInfo"]);
                                
                              
                               $resp=$this->import_order($data);
                               $resp_decode = json_decode($resp);
                               $checkDup_order = $this->check_duplicate_order($resp_decode->message->customerId); //check previous

                                if($resp_decode->result=="SUCCESS"){ 
                                    unset($_SESSION['order_data_'.$this->campaignId]); //clear order data first

                                    //add note to customer
                                    $this->customer_add_note(
                                        array(
                                            "customerId" =>$resp_decode->message->customerId,
                                            "message" => $resp_decode->message->orderId." created using Stripe Express Checkout. Generated Stripe Customer id: ".$customer->id.
                                                                                        ", Payment Type: ".$data["paySource"].
                                                                                        ", Payment Intent Id: ". $paymentIntent->id
                                        )
                                    );
                                    
                                    $_SESSION['order_data_'.$this->campaignId] =[
                                        "campaignId" => $this->campaignId,
                                        "emailAddress" => $resp_decode->message->emailAddress,
                                        "firstName" => $resp_decode->message->firstName,
                                        "lastName" => $resp_decode->message->lastName,
                                        "walletCustomerId" => $customer->id, //stripe customer id
                                        "walletPaymentIntentId" => $paymentIntent->id, //stripe payment id
                                        "walletPaymentMethodId" => $data['paymentMethodId'], //stripe confirmation token
                                        "mainProductQty" => $data['mainProductQty'],
                                        "mainProductId" => $data['mainProductId'],
                                        "mainProductPrice" => $data['mainProductPrice'],
                                        "orderId" => $resp_decode->message->orderId,
                                        "paySource" => $data['paySource'],
                                        "orderProducts" => $orderProducts, 
                                        "items"=>  $resp_decode->message->items,
                                        "couponCode"=> $data['couponCode'],
                                        "shipProfileId"=> $data['shipProfileId'],
                                        "marketingOptIn"=> $data['marketingOptIn']
                                    ];

                                    unset($_SESSION['token']); //clear token in every successful purchase     
                                    $resp_decode->message->nextPage = "/".$this->upsells[0]['page'].".php";
                                    $resp_decode->message->addOnTotal = $data['addOnTotal'];
                                    $resp_decode->message->testmode = $data['testmode'];
                                    $resp_decode->message->country_code = $_SESSION['sessionCountry'];
                                    $resp_decode->message->custom_config = $_SESSION['custom_config'];
                                    
                                    if(!isset($data['testmode'])){
                                        unset($resp_decode->message->testmode);
                                    }

                                    if(!isset($data['lang'])){
                                        unset($resp_decode->message->lang);
                                    } else if(isset($_SESSION['lang'])){
                                        $resp_decode->message->lang = $_SESSION['lang'];
                                    } else if($data['lang']!="") {
                                        $resp_decode->message->lang = $data['lang'];
                                    }else{
                                        $resp_decode->message->lang = $this->targetLanguage;
                                    }
                                     
                                }
                              
                                if($resp_decode->result=="ERROR"){
                                    $this->messageCheck($resp_decode->message,$resp_decode->result);
                                }else{
                                    $resp_decode=$this->logDuplicateOrder($checkDup_order,$resp_decode);//log duplicate
                                    echo json_encode([
                                        "result" => $resp_decode->result,
                                        "message" => $resp_decode->message
                                    ]);              
                                }
                    } else{

                            // Log decline in Konnektive                            
                            $data["campaignId"] = $this->campaignId; 
                            $data['sessionId'] = $_SESSION['session_id_'.$this->campaignId];
                            $data['userAgent']=$_SERVER['HTTP_USER_AGENT'];
                            $data['firstName']= $data['shipFirstName'];
                            $data['lastName']=  $data['shipLastName'];
                            $data['emailAddress']=  $data['email'];
                            unset($data["billingInfo"]);
                            unset($data["products"]);
                            unset($data["shippingInfo"]);

                            $importDec=$this->import_order($data);
                            if($importDec->result=="SUCCESS"){ 
                                // Order QA Decline
                                $qaData = [                        
                                    'orderId'  => $importDec->message->orderId,
                                    'action'   => 'DECLINE'
                                ];
                                $this->order_qa($data);
                            }
                            echo json_encode([
                                        "result" => "ERROR",
                                        "message" => $this->deepLTranslate("Transaction Declined")
                            ]); 
                    }
            } catch (Exception $e) {
                 echo json_encode([
                        "result" => "ERROR",
                        "message" => $this->deepLTranslate($e->getMessage())
                ]);
            }   
    }

    private function stripeExpressUpsellImport($data){

            // Guard against duplicate Stripe charges: if this upsell's product(s) are already
            // part of the order (e.g. double-submit, back-button resubmit, or a retried request),
            // reject here instead of charging Stripe again and letting Checkout Champ report
            // "already taken" after the money has already moved.
            $existingItems = $_SESSION['order_data_'.$this->campaignId]['items'] ?? [];
            $requestedProductIds = array_column($data['products'] ?? [], 'productId');
            foreach ($existingItems as $item) {
                if (isset($item->productId) && in_array($item->productId, $requestedProductIds)) {
                    echo json_encode([
                        "result" => "ERROR",
                        "message" => $this->deepLTranslate("This upsale was already taken.")
                    ]);
                    return;
                }
            }

            $secretKey = $this->stripeKeys['live_secretKey'];
            if ($data["testmode"]=="true" || $data["testmode"]==true){
                $secretKey=$this->stripeKeys['sandbox_secretKey'];
            }

            // if($data['paymentType'] == "google_pay" ){
            //     $data['paySource']= 'GOOGLEPAY';
            // }else if($data['paymentType'] == "apple_pay" ){
            //     $data['paySource']= 'APPLEPAY';
            // }else{
            //     echo json_encode([
            //             "result" => "ERROR",
            //             "message" => "Invalid Payment Type"
            //     ]);
            //     exit;
            // }

            //shippingInfo
            // foreach ($data['shippingInfo'] as $key => $value){
            //     $data[$key]=$value;
            // }

            //billing info
            // foreach ($data['billingInfo'] as $key => $value){
            //     $data[$key]=$value;
            // }  
            
            // Ensure Stripe SDK loaded
            if (!class_exists(\Stripe\Stripe::class)) {
                echo json_encode(["result"=>"ERROR","message"=>$this->deepLTranslate("Stripe SDK not loaded. Did you run composer require stripe/stripe-php?")]);
                return;
            }

            \Stripe\Stripe::setApiKey($secretKey);   
            try {
                    // Charge via Stripe
                    $paymentIntent = \Stripe\PaymentIntent::create([
                        'amount' => $this->to_cents($data['amount'], $data['currency']), // $49.99
                        'currency' => $data['currency'],
                        //'confirmation_token' => $data['confirmationTokenId'],
                        'payment_method' => $_SESSION['order_data_'.$this->campaignId]['walletPaymentMethodId'],
                        'return_url' => $data['returnurl'],                       
                        'setup_future_usage' => 'off_session',
                        'confirmation_method' => 'automatic',
                        'description' => 'Upsell Product Order',
                        'customer' => $_SESSION['order_data_'.$this->campaignId]['walletCustomerId'],
                        'confirm' => true
                    ]);
                    
                    if ($paymentIntent->status === 'succeeded') {                    
                                $data["campaignId"] = $this->campaignId;   
                                $data['orderId'] =  $_SESSION['order_data_'.$this->campaignId]['orderId'];  
                                $data['externalOrderId'] = $paymentIntent->id;                               
                                $data['skipQA']=  true;
                                $data=array_merge($data,$this->extractOrders($data['products'])); //orders  
                                $orderProducts = $data["products"];
                                
                                //unset($data["billingInfo"]);
                                unset($data["products"]);
                                //unset($data["shippingInfo"]);
                                
                                $resp=$this->import_upsale($data);
                                $resp_decode = json_decode($resp);

                                if($resp_decode->result=="SUCCESS"){ 
                                    unset($_SESSION['order_data_upsell'.$this->campaignId]); //clear order data first

                                    //add note to customer
                                    $this->customer_add_note(
                                        array(
                                            "customerId" =>$resp_decode->message->customerId,
                                            "message" => "Upsell order: ".$resp_decode->message->orderId." created using Stripe Express Checkout. Stripe Customer id: ".$_SESSION['order_data_'.$this->campaignId]['walletCustomerId'].
                                                                                        ", Payment Type: ".$_SESSION['order_data_'.$this->campaignId]["paySource"].
                                                                                        ", Payment Intent Id: ". $paymentIntent->id
                                        )
                                    );

                                    $_SESSION['order_data_'.$this->campaignId]["items"]= $resp_decode->message->items;

                                    // Track cumulative qty across accepted upsells
                                    $upsellQty = intval($data['product1_qty'] ?? 0);
                                    $prevQty = $_SESSION['order_data_'.$this->campaignId]['accumulatedQty'] 
                                            ?? $_SESSION['order_data_'.$this->campaignId]['mainProductQty'];
                                    $_SESSION['order_data_'.$this->campaignId]['accumulatedQty'] = intval($prevQty) + $upsellQty;

                                    $_SESSION['order_data_upsell'.$this->campaignId] =[
                                        "orderProducts" => $orderProducts
                                    ];

                                    unset($_SESSION['token']); //clear token in every successful purchase
                                    $resp_decode->message->nextPage = "/".$this->nextFunnelPage($data['page'])['yesBtnTo'].".php";
                                    $resp_decode->message->testmode = $data['testmode'];
                                    $resp_decode->message->country_code = $_SESSION['sessionCountry'];
                                    $resp_decode->message->custom_config = $_SESSION['custom_config'];
                                    // Google Pay / Apple Pay express upsells: when the no-thanks popup
                                    // downsell was accepted, tell the client to fire the downsell Everflow event.
                                    $dsEfEventId = $this->resolveDownsellEverflowEventId($data['page']);
                                    if ($dsEfEventId !== null) {
                                        $resp_decode->message->everflowEventId = $dsEfEventId;
                                    }
                                    if(!isset($data['testmode'])){
                                        unset($resp_decode->message->testmode);
                                    }
                                    if(!isset($data['lang'])){
                                        unset($resp_decode->message->lang);
                                    } else if(isset($_SESSION['lang'])){
                                        $resp_decode->message->lang = $_SESSION['lang'];
                                    } else if($data['lang']!="") {
                                        $resp_decode->message->lang = $data['lang'];
                                    } else {
                                        $resp_decode->message->lang = $this->targetLanguage;
                                    }
                                }
                                
                                if($resp_decode->result=="ERROR"){
                                    $this->messageCheck($resp_decode->message,$resp_decode->result);
                                }else{
                                    echo json_encode([
                                        "result" => $resp_decode->result,
                                        "message" => $resp_decode->message
                                    ]);              
                                }
                                
                    } else{

                            // Order QA Decline
                            $qaData = [                        
                                    'orderId'  => $_SESSION['order_data_'.$this->campaignId]['orderId'],
                                    'action'   => 'DECLINE'
                            ];
                            $this->order_qa($data);                            
                            echo json_encode([
                                        "result" => "ERROR",
                                        "message" => $this->deepLTranslate("Upsell Transaction Declined")
                            ]); 
                    }
            } catch (Exception $e) {
                 echo json_encode([
                        "result" => "ERROR",
                        "message" => $this->deepLTranslate($e->getMessage())
                ]);
            }        
    }

    private function stripePaymentIntentKlarna($data){
           $secretKey = $this->stripeKeys['live_secretKey'];
            if ($data["testmode"]=="true" || $data["testmode"]==true){
                $secretKey=$this->stripeKeys['sandbox_secretKey'];
            }

             // Ensure Stripe SDK loaded
            if (!class_exists(\Stripe\Stripe::class)) {
                echo json_encode(["result"=>"ERROR","message"=>$this->deepLTranslate("Stripe SDK not loaded. Did you run composer require stripe/stripe-php?")]);
                return;
            }

            \Stripe\Stripe::setApiKey($secretKey); 

            try {
                $intent = \Stripe\PaymentIntent::create([
                    'amount' => $data['amount'] ?? 1000,
                    'currency' => strtolower($data['currency'] ?? 'usd'),
                    'payment_method_types' => ['klarna'], 
                ]);
                echo json_encode(['clientSecret' => $intent->client_secret]);
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode(['error' => $e->getMessage()]);
            }

    }

    private function handlePOST() {
        // Check if required form fields are set
        $data = json_decode(file_get_contents("php://input"), true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['call_type'])) {
            http_response_code(400);
            echo json_encode([
                "result" => "ERROR",
                "message" => $this->deepLTranslate("Invalid or missing 'call_type'")
            ]);
            exit;
        }
        
        switch($data['call_type']){
            case 'leads_import':
                $this->leads_import($data);
                break;

            case 'campaign_query':
                $this->campaign_query();
                break;

            case 'order_import':                
                if (!isset($data['token_']) || $data['token_'] !== $_SESSION['token']) {
                    echo json_encode([
                        "result" => "ERROR",
                        "message" => $this->deepLTranslate("Invalid Request. Please reload the page.")
                    ]);
                    exit;
                }else{
                    $this->importOrder($data);
                }
                break;

            case 'get_paypalURL':
                if (!isset($data['token_']) || $data['token_'] !== $_SESSION['token']) {
                    echo json_encode([
                        "result" => "ERROR",
                        "message" => $this->deepLTranslate("Invalid Request. Please reload the page.")
                    ]);
                    exit;
                }else{
                    $this->get_paypalURL($data);
                }
                break;
            case 'get_paypalURL_direct_api':
                if (!isset($data['token_']) || $data['token_'] !== $_SESSION['token']) {
                    echo json_encode([
                        "result" => "ERROR",
                        "message" => $this->deepLTranslate("Invalid Request. Please reload the page.")
                    ]);
                    exit;
                }else{
                    $this->get_paypalURL_direct_api($data);
                }
                break;

            case 'paypal_transaction_confirm':
                $this->paypal_transaction_confirm($data);
                break;
    
            case 'no_upsell':
                $this->noUpsellClick($data);
                break;

            case 'yes_upsell':
                if (!isset($data['token_']) || $data['token_'] !== $_SESSION['token']) {
                    echo json_encode([
                        "result" => "ERROR",
                        "message" => $this->deepLTranslate("Invalid Request. Please reload the page.")
                    ]);
                    exit;
                }else{
                    $this->importUpsell($data);
                }               
                break;
            
            case 'click_import_thankyou':
                 $params=array( 
                    "sessionId"         => $_SESSION['session_id_'.$this->campaignId],
                    "pageType"			=> "thankyouPage",				
                    "userAgent" 		=> $data["userAgent"],
                    "requestUri" 		=> urlencode($data['requestUri']),
                    "campaignId"        => $this->campaignId
                );                
                $resp=$this->import_click($params);
                $resp_decode = json_decode($resp);
                
                $resp=$this->confirm_order($data['orderId']);   
                if($resp_decode->result=="SUCCESS"){
                     echo json_encode([
                        "result" => $resp_decode->result,
                        "message" => $resp_decode->message
                    ]);    
                                
                }                
                break;
            case 'stripe_express_order_import':
                 if (!isset($data['token_']) || $data['token_'] !== $_SESSION['token']) {
                    echo json_encode([
                        "result" => "ERROR",
                        "message" => $this->deepLTranslate("Invalid Request. Please reload the page.")
                    ]);
                    exit;
                }else{
                    $this->stripeExpressOrderImport($data);
                }
                break;

            case 'stripe_express_order_import_upsell':
                 if (!isset($data['token_']) || $data['token_'] !== $_SESSION['token']) {
                    echo json_encode([
                        "result" => "ERROR",
                        "message" => $this->deepLTranslate("Invalid Request. Please reload the page.")
                    ]);
                    exit;
                }else{
                   $this->stripeExpressUpsellImport($data);
                }

                break;

            case 'payment_intent_klarna':

                if (!isset($data['token_']) || $data['token_'] !== $_SESSION['token']) {
                    echo json_encode([
                        "result" => "ERROR",
                        "message" => $this->deepLTranslate("Invalid Request. Please reload the page.")
                    ]);
                    exit;
                }else{
                   $this->stripePaymentIntentKlarna($data);
                }
                break;

            case 'order_import_klarna':

                if (!isset($data['token_']) || $data['token_'] !== $_SESSION['token']) {
                    echo json_encode([
                        "result" => "ERROR",
                        "message" => $this->deepLTranslate("Invalid Request. Please reload the page.")
                    ]);
                    exit;
                }else{
                   $this->importOrderKlarna($data);
                }
                break;

            case 'order_import_prepaid':

                if (!isset($data['token_']) || $data['token_'] !== $_SESSION['token']) {
                    echo json_encode([
                        "result" => "ERROR",
                        "message" => $this->deepLTranslate("Invalid Request. Please reload the page.")
                    ]);
                    exit;
                }else{
                   $this->importOrderPrepaid($data);
                }
                break;

            case 'ef_direct_link':
                if (isset($data['trans_id'])) {
                    $_SESSION['direct_link_'.$this->campaignId]=$data['trans_id'];
                }
                break;
            
        }
       
        
    }
}

