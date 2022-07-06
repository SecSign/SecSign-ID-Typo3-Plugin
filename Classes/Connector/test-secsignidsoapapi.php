<?php
    //
	// (c) 2012 - 2020 SecSign Technologies Inc.
	//
	
    include 'SecSignIDSoapApi.php';
    
   
    //
    // Create an instance of SecSignIDSoapApi.
    //
    echo "create new instance of SecSignIDSoapApi." . PHP_EOL;
    
	$secSignIDSoapApi = new SecSignIDSoapApi(); // use of default settings
	$secSignIDSoapApi = new SecSignIDSoapApi("https://secpkiapi.secsigntest.com"); // use different server setting if default server shall not be used
	$secSignIDSoapApi = new SecSignIDSoapApi("https://localhost:25100"); // use different server setting if default server shall not be used
	
	
	$servicename = "Your Website Login";
    $serviceaddress = php_uname("n"); // "http://www.yoursite.com/";
    $secsignid = "leila";
    
	// request auth session
	$response = $secSignIDSoapApi->requestAuthSession($secsignid, $servicename, $serviceaddress);

	$authSessionId = $response->AuthSessionID;
	$passIcondata  = $response->PassIconData;
	
	// show the pass icon data to user
	// do further checks with the auth session state
	
	$secondsToWaitUntilNextCheck = 10;
    $noError = TRUE;
    $response = $secSignIDSoapApi->getAuthSessionState($authSessionId);
    $authSessionState = $response->AuthSessionState;
    
    while(($authSessionState == AuthSession::PENDING || $authSessionState == AuthSession::FETCHED) && $noError)
    {
        try
        {
            $response = $secSignIDSoapApi->getAuthSessionState($authSessionId);
    		$authSessionState = $response->AuthSessionState;
    
            echo "auth session state    : " . $authSessionState . PHP_EOL;
            
            if($authSessionState == AuthSession::PENDING || $authSessionState == AuthSession::FETCHED){
                sleep($secondsToWaitUntilNextCheck);
            }
        } 
        catch (Exception $e) 
        {
            echo "could not get auth session status for auth session '" . $authSession->getAuthSessionID() . "' : " . $e->getMessage() . PHP_EOL;
            $noError = FALSE;
        }
    }
    
    // either the uer accepted the auth session or it expired or was rejected by server
	if($authSessionState == AuthSession::AUTHENTICATED)
    {
        echo "user has accepted the auth session '" . $authSessionId . "'." . PHP_EOL;
    }
    else if($authSessionState == AuthSession::DENIED)
    {
        echo "user has denied the auth session '" . $authSessionId . "'." . PHP_EOL;
        $authSessionState = $secSignIDSoapApi->cancelAuthSession($authSessionId); // after the auth session is successfully canceled it is not possible to inquire the status again
        if($authSessionState == AuthSession::CANCELED)
        {
            echo "authentication session successfully cancelled..." . PHP_EOL;
        }
    }
    else {
        echo "auth session '" . $authSessionId . "' has state " . authSessionState . "." . PHP_EOL;
        $authSessionState = $secSignIDSoapApi->cancelAuthSession($authSessionId); // after the auth session is successfully canceled it is not possible to inquire the status again
        if($authSessionState == AuthSession::CANCELED)
        {
            echo "authentication session successfully cancelled..." . PHP_EOL;
        }
    }
	
?>
