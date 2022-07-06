<?php
    
namespace SecSign\Secsign\Connector;
//
// SecSign ID Api in php.
//
// (c) 2014-2022 SecSign Technologies Inc.
//
    
define("SCRIPT_VERSION", '2.0');
     
use Exception;         

/*
* PHP class to connect to a secsign id server. the class will check secsign id server certificate and request for authentication session generation for a given
* user id which is called secsign id. Each authentication session generation needs a new instance of this class.
*
* @author SecSign Technologies Inc.
* @copyright 2014-2017
*/
class SecSignIDApi
{
       
        // numeric script version.
        private $scriptVersion  = 0;
        private $referer        = NULL;
        private $logger = NULL;
        
      
        private $lastResponse = NULL;
        
        private $serverURL = "https://httpapi.secsign.com";
        private $serverPort = 443;
        private $pinAccountUser= "";
        private $pinAccountPassword= "";
        
        
        
        function __construct($serverURL,$pinAccountUser,$pinAccountPassword)
        {
           
            // script version from cvs revision string
            $this->scriptVersion = SCRIPT_VERSION;
            
            // use a constant string rather than using the __CLASS__ definition 
            // because this could cause problems when the class is in a submodule
            $this->referer = "SecSignIDApi_PHP";
            
            $this->prerequisite();
            $this->pinAccountUser=$pinAccountUser;
            $this->pinAccountPassword=$pinAccountPassword;
            $this->serverURL=$serverURL;
            
        }
        
        /*
         * Destructor
         */
        function __destruct()
        {
            
            $this->scriptVersion = NULL;            
            $this->logger = NULL;
        }
        
        /**
         * Function to check whether curl is available
		 */
        function prerequisite()
        {
            if(! function_exists("curl_init")){
                $this->log("curl_init does not exist. php-curl installed for this version of php?");
                return false;
            }
            
            if(! function_exists("curl_exec")){
                $this->log("curl_exec does not exist. php-curl installed for this version of php?");
                return false;
            }
            
            if(! is_callable("curl_init", true, $callable_name)){
                $this->log("curl_init is not callable. php-curl installed for this version of php?");
                return false;
            }
            
            if(! is_callable("curl_exec", true, $callable_name)){
                $this->log("curl_exec is not callable. php-curl installed for this version of php?");
                return false;
            }
            
            return true;
        }
        
        function setServer($p_serverURL)
        {
            $this->serverURL=$p_serverURL;
        }
        
        function setPinAccountUser($p_pinAccountUser)
        {
            $this->pinAccountUser=$p_pinAccountUser;
        }
        
        function setPinAccountPassword($p_pinAccountPassword)
        {
            $this->pinAccountPassword=$p_pinAccountPassword;
        }
        
        /*
         * Sets a function which is used as a logger
         */
        function setLogger($logger)
        {
            if($logger != NULL && isset($logger) && is_callable($logger) == TRUE){
                $this->logger = $logger;
            }
        }
        
        function getRestoreSecSignIDQRCode($secsignid,$accessToken=null)
        {
            if($accessToken)
            {
                $this->sendGET($this->serverURL.'/rest/v1/SecSignId/'.$secsignid.'?restoration'."&accesstoken=". urlencode($accessToken));
                $response = $this->getResponse();
                $jsonArray=json_decode($response, true);
                return $jsonArray;
            }else{
                $this->sendGET($this->serverURL.'/rest/v1/SecSignId/'.$secsignid.'?restoration');
                $response = $this->getResponse();
                $jsonArray=json_decode($response, true);
                return $jsonArray;
            }
        }
        
        function createSecSignIDWithMail($secsignid,$email)
        {
            $paras=array('secsignid'=>$secsignid,'email'=>$email,'enable'=>'true');
            $this->sendPOST($this->serverURL.'/rest/v2/SecSignId/',$paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        function createSecSignIDWithoutMail($secsignid)
        {
            $paras=array('secsignid'=>$secsignid,'enable'=>'true');
            $this->sendPOST($this->serverURL.'/rest/v2/SecSignId/',$paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        function createRestoreSecSignID($secsignid,$email)
        {
            $paras=array('secsignid'=>$secsignid,'email'=>$email,'enable'=>'true');
            $this->sendPOST($this->serverURL.'/rest/v2/SecSignId/',$paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        function getDevicesOfSecSignID($secsignid)
        {
            $this->sendGET($this->serverURL.'/rest/v1/Device/'.$secsignid.'/Count');
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            $count=$jsonArray['count'];
            return $count;
        }
        
        function checkSecSignID($secsignid){
            $this->sendGET($this->serverURL.'/rest/v2/SecSignId/'.$secsignid.'?exist');
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            $exists=$jsonArray['exist'];
            return $exists;
        }
        
        function registerPlugin($url,$siteName,$pluginName,$pluginType){
            $paras=array('url'=>$url,'siteName'=>$siteName,'pluginName'=>$pluginName,'pluginType'=>$pluginType);
            $this->sendPOST($this->serverURL.'/rest/v2/PluginRegistration', $paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        function getCreateSecsignIDQRCode($secsignid){
            $this->sendGET($this->serverURL.'/rest/v2/SecSignId/'.$secsignid.'?createqrcode');
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            $createurl=$jsonArray['createurl'];
            $qrcodebase64=$jsonArray['qrcodebase64'];
            $answer=array("createurl" => $createurl, "qrcodebase64" => $qrcodebase64, "secsignid"=> $secsignid);
            return $answer;
        }
        
        function checkQRCode($secsignid)
        {
            $this->sendGET($this->serverURL.'/rest/v2/SecSignId/'.$secsignid.'?exist');
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            $exists=$jsonArray['exist'];
            return $exists;
        }
        
        function cancelAuthSession($authsessionid)
        {
            $this->sendDELETE($this->serverURL.'/rest/v2/AuthSession/'.$authsessionid);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
        }
        
        function checkAuthSession($authsessionid)
        {
            $this->sendGET($this->serverURL.'/rest/v2/AuthSession/'.$authsessionid);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray['authsessionstate'];
           
        }
        
        function startAuth($secsignid,$servicename,$serviceaddress,$showaccesspassicons)
        {
             $encodedParameters= "secsignid=" . urlencode($secsignid) . "&servicename=" . urlencode($servicename) .
                    "&serviceaddress=" . urlencode($serviceaddress) ."&showaccesspassicons=" . urlencode($showaccesspassicons);

            $this->sendGET($this->serverURL.'/rest/v2/AuthSession?'.$encodedParameters);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        function getTOTPQRCode($secsignid,$accessToken=null)
        {
            if($accessToken)
            {
                $this->sendGET($this->serverURL.'/rest/v2/TOTP?secsignid='.$secsignid.'&accesstoken='.$accessToken);
                $response = $this->getResponse();
                $jsonArray=json_decode($response, true);
                return $jsonArray;
            }else{
                $this->sendGET($this->serverURL.'/rest/v2/TOTP?secsignid='.$secsignid);
                $response = $this->getResponse();
                $jsonArray=json_decode($response, true);
                return $jsonArray;
            }
            
        }
        
        function checkTOTPCode($secsignid,$totpCode)
        {
            $this->sendGET($this->serverURL."/rest/v2/TOTP/Verify/". $totpCode ."?secsignid=".$secsignid);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray['valid'];
        }
        
        function getMailOTPCode($secsignid,$email)
        {
            $this->sendGET($this->serverURL."/rest/v2/OTP?secsignid=" .$secsignid."&email=" .$email);
            $response = $this->getResponse();
            if(strpos($response,'error')>=0)
            {
                return false;
            }else{
                return true;
            }
        }
        
        function checkMailOTPCode($secsignid,$mailotpCode)
        {
            
            $this->sendGET($this->serverURL."/rest/v2/OTP/valid/". urlencode($mailotpCode) ."?secsignid=".urlencode($secsignid));
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray['valid'];
        }
        
        function getAccessTokenInfo($secsignid,$serviceurl)
        {
            $paras=array('secsignid'=>$secsignid,'serviceurl'=>$serviceurl);
            $this->sendPOST($this->serverURL.'/rest/v2/AccessToken/Info', $paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        function getAccessTokenAuthorization($secsignid,$serviceurl,$authenticationMethod,$capabilities)
        {
            $paras=array('secsignid'=>$secsignid,'serviceurl'=>$serviceurl,'authmethod'=>$authenticationMethod,'capabilities'=>$capabilities);
            $this->sendPOST($this->serverURL.'/rest/v2/AccessToken', $paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        function getAccessTokenForMailOTP($tokenID,$mailOTPCode)
        {
            $paras=array();
            $this->sendPOST($this->serverURL.'/rest/v2/AccessToken/'.$tokenID, $paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        function requestAccessTokenForFIDOAuthentication($tokenID,$secsignid, $credentialId, $clientDataJson, $authenticatorData, $signature, $userHandle)
        {
            $paras=array('userName'=>$secsignid,'credentialId'=>$credentialId,'clientDataJSON'=>$clientDataJson,'authenticatorData'=>$authenticatorData,'signature'=>$signature,'userHandle'=>$userHandle);
            $this->sendPOST($this->serverURL.'/rest/v2/AccessToken/'.$tokenID, $paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
         function requestAccessTokenForTOTPAuthentication($tokenID,$totpCode)
        {
            $paras=array('totp'=>$totpCode);
            $this->sendPOST($this->serverURL.'/rest/v2/AccessToken/'.$tokenID, $paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        function requestAccessTokenForSecSignAuthentication($tokenID)
        {
            $paras=array();
            $this->sendPOST($this->serverURL.'/rest/v2/AccessToken/'.$tokenID, $paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        
        
        
        function startFIDORegister($secsignid,$credentialNickname,$uri,$accesstoken=null)
        {
            
            if($accesstoken)
            {
                $paras=array('userName'=>$secsignid,'rpId'=>$uri,'rpName'=>$uri,'credentialNickname'=>$credentialNickname,'accesstoken'=>$accesstoken);
            }else{
                $paras=array('userName'=>$secsignid,'rpId'=>$uri,'rpName'=>$uri,'credentialNickname'=>$credentialNickname);
            }
            
            $this->sendPOST($this->serverURL.'/rest/v2/FIDO/Register/Start', $paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        function finishFIDORegister($secsignid,$credentialID,$clientDataJson,$attestationObject,$accesstoken=null)
        {
            
            if($accesstoken)
            {
                $paras=array('userName'=>$secsignid,'credentialId'=>$credentialID,'clientDataJSON'=>$clientDataJson,'attestationObject'=>$attestationObject,'accesstoken'=>$accesstoken);
            }else{
                $paras=array('userName'=>$secsignid,'credentialId'=>$credentialID,'clientDataJSON'=>$clientDataJson,'attestationObject'=>$attestationObject);
            }
            
            $this->sendPOST($this->serverURL.'/rest/v2/FIDO/Register/Finish', $paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        function startFIDOAuthentication($secsignid,$url)
        {
            $paras=array('userName'=>$secsignid,'rpId'=>$url);
            $this->sendPOST($this->serverURL.'/rest/v2/FIDO/Authenticate/Start', $paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        function finishFIDOAuthentication($secsignid,$credentialId,$clientDataJSON,$authenticatorData,$signature,$userHandle)
        {
            $paras=array('userName'=>$secsignid,'credentialId'=>$credentialId,'clientDataJSON'=>$clientDataJSON,'authenticatorData'=>$authenticatorData,'signature'=>$signature,'userHandle'=>$userHandle);
            $this->sendPOST($this->serverURL.'/rest/v2/FIDO/Authenticate/Finish', $paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
       
        function saveMailForUser($secsignid,$email)
        {
             $paras=array('secsignid'=>$secsignid,'update'=>'restoration','email'=>$email,'enable'=>'true');
            $this->sendPOST($this->serverURL.'/rest/v1/SecSignId/'.$secsignid,$paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        function activateRestoration($secsignid)
        {
            $paras=array('secsignid'=>$secsignid,'restoration'=>true);
            $this->sendPOST($this->serverURL.'/rest/v2/SecSignId/',$paras);
            $response = $this->getResponse();
            $jsonArray=json_decode($response, true);
            return $jsonArray;
        }
        
        /*
         * logs a message if logger instance is not NULL
         */
        private function log($message)
        {
            if($this->logger != NULL){
                $logMessage = __CLASS__ . " (v" . $this->scriptVersion . "): " . $message;
                call_user_func($this->logger, $logMessage);
            }
        }
        
        
        
        /*
         * Gets last response
         */
        function getResponse()
        {
            return $this->lastResponse;
        }
        
        /*
         * sends given parameters to secsign id server and wait given amount
         * of seconds till the connection is timed out
         */
        function sendPOST($url, $parameters)
        {		
            $this->log("url: ".$url." parameters: ". print_r($parameters,true));
            $timeout_in_seconds = 25;
            
            $parametersForPOST=http_build_query($parameters, '', '&');
            
            // create cURL resource
            $ch = $this->getPOSTCURLHandle($url, $parametersForPOST, $timeout_in_seconds);
            $this->log("curl_init: " . $ch);
            
            // $output contains the output string
            $this->log("cURL curl_exec sent params: " . $parametersForPOST);
            $output = curl_exec($ch);
            if ($output === false) 
            {
                $this->log("curl_error: " . curl_error($ch));
            }

            // close curl resource to free up system resources
            $this->log("curl_close: " . $ch);
            curl_close($ch);
            
            // check if output is NULL. in that case the secsign id might not have been reached.
            if($output == NULL)
            {
                $this->log("curl: output is NULL. Server " . $this->secSignIDServer . ":" . $this->secSignIDServerPort . " has not been reached.");
                $this->log("curl_error: " . curl_error($ch));
                throw new Exception("curl_exec error: can't connect to Server - " . curl_error($ch));
            }
            
            $this->log("curl_exec response: " . ($output == NULL ? "NULL" : $output));
            $this->lastResponse = $output;
            
            return $output;
            
            
        }
        
        /*
         * sends given parameters to secsign id server and wait given amount
         * of seconds till the connection is timed out
         */
        function sendGET($url)
        {		
            $this->log("url: " . $url);
            $timeout_in_seconds = 25;
            
            // create cURL resource
            $ch = $this->getGETCURLHandle($url, $timeout_in_seconds);
            $this->log("curl_init: " . $ch);
            
            // $output contains the output string
            $output = curl_exec($ch);
            if ($output === false) 
            {
                $this->log("curl_error: " . curl_error($ch));
            }

            // close curl resource to free up system resources
            $this->log("curl_close: " . $ch);
            
            
            // check if
            // output is NULL. in that case the secsign id might not have been reached.
            if($output == NULL)
            {
                $this->log("output is NULL. Call was on " . $url);
                $this->log("curl_error: " . curl_error($ch));
                throw new Exception("curl_exec error: can't connect to Server - " . curl_error($ch));
                   
            }
            curl_close($ch);
            
            
            
            $this->log("curl_exec response: " . ($output == NULL ? "NULL" : $output));
            $this->lastResponse = $output;
            
            return $output; // will throw an exception in case of an error
        }
        
        /*
         * sends given parameters to secsign id server and wait given amount
         * of seconds till the connection is timed out
         */
        function sendDELETE($url)
        {		
            $this->log("url: " . $url);
            $timeout_in_seconds = 25;
            
            // create cURL resource
            $ch = $this->getDELETECURLHandle($url, $timeout_in_seconds);
            $this->log("curl_init: " . $ch);
            
            // $output contains the output string
            $output = curl_exec($ch);
            if ($output === false) 
            {
                $this->log("curl_error: " . curl_error($ch));
            }

            // close curl resource to free up system resources
            $this->log("curl_close: " . $ch);
            
            
            // check if
            // output is NULL. in that case the secsign id might not have been reached.
            if($output == NULL)
            {
                $this->log("output is NULL. Call was on " . $url);
                $this->log("curl_error: " . curl_error($ch));
                throw new Exception("curl_exec error: can't connect to Server - " . curl_error($ch));
                   
            }
            curl_close($ch);
            
            
            
            $this->log("curl_exec response: " . ($output == NULL ? "NULL" : $output));
            $this->lastResponse = $output;
            
            return $output; // will throw an exception in case of an error
        }
        
        
        
        /*
         * Gets a cURL resource handle.
         */
        private function getPOSTCURLHandle($url,$parameters, $timeout_in_seconds)
        {
            
            
            // create cURL resource
            $ch = curl_init();
            
            // set url
            curl_setopt($ch, CURLOPT_URL, $url);
            //curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
            //curl_setopt($ch, CURLOPT_SSLVERSION, 3);
            
            curl_setopt($ch, CURLOPT_USERPWD, $this->pinAccountUser . ":" . $this->pinAccountPassword);
            
            //return the transfer as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0); // value 0 will strip header information in response 
            
            // set connection timeout
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout_in_seconds);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout_in_seconds);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            
            // make sure the common name of the certificate's subject matches the server's host name
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            
            // validate the certificate chain of the server
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            //The CA certificates
            curl_setopt($ch, CURLOPT_CAINFO, realpath(dirname(__FILE__)) .'/curl-ca-bundle.crt');
            
            // add referer
            curl_setopt($ch, CURLOPT_REFERER, $this->referer); 
            
            // add all parameter and change request mode to POST
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
            
            return $ch;
        }
        
        /*
         * Gets a cURL resource handle.
         */
        private function getGETCURLHandle($url,$timeout_in_seconds)
        {
            // create cURL resource
            $ch = curl_init($url);
            
             curl_setopt($ch, CURLOPT_HTTPGET, 1);
             curl_setopt($ch, CURLOPT_USERPWD, $this->pinAccountUser . ":" . $this->pinAccountPassword);
            
            //return the transfer as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0); // value 0 will strip header information in response 
            
            // set connection timeout
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout_in_seconds);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout_in_seconds);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            
            // make sure the common name of the certificate's subject matches the server's host name
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            
            // validate the certificate chain of the server
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            //The CA certificates
            curl_setopt($ch, CURLOPT_CAINFO, realpath(dirname(__FILE__)) .'/curl-ca-bundle.crt');
            
            // add referer
            curl_setopt($ch, CURLOPT_REFERER, $this->referer); 
            
            
            return $ch;
        }
        
        /*
         * Gets a cURL resource handle.
         */
        private function getDELETECURLHandle($url,$timeout_in_seconds)
        {
            // create cURL resource
            $ch = curl_init($url);
            
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_USERPWD, $this->pinAccountUser . ":" . $this->pinAccountPassword);
            
            //return the transfer as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0); // value 0 will strip header information in response 
            
            // set connection timeout
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout_in_seconds);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout_in_seconds);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            
            // make sure the common name of the certificate's subject matches the server's host name
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            
            // validate the certificate chain of the server
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            //The CA certificates
            curl_setopt($ch, CURLOPT_CAINFO, realpath(dirname(__FILE__)) .'/curl-ca-bundle.crt');
            
            // add referer
            curl_setopt($ch, CURLOPT_REFERER, $this->referer); 
            
            
            return $ch;
        }
}
	
