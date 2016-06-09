<?php

// $Id: example.php,v 1.14 2014/05/28 15:10:23 titus Exp $
// $Source: /encfs/checkout/antonio/cvs/SecCommerceDev/seccommerce/secsignerid/php/example.php,v $
// $Log: example.php,v $
// Revision 1.14  2014/05/28 15:10:23  titus
// Added copyright and set correct company name.
//
// Revision 1.13  2014/04/08 15:28:15  titus
// Added copyright comment because of being published on GitHub.
//
// Revision 1.12  2013-04-24 12:10:07  jwollner
// fixed comments, so they can be removed by the ant script
//
// Revision 1.11  2013-04-17 12:47:25  jwollner
// added geotrust cert and fixed path
//
// Revision 1.10  2013-02-21 11:32:10  jwollner
// fixed wordpress and php plugin
//
// Revision 1.9  2012-12-21 10:46:23  jwollner
// added readme und fixed comments and urls in examples
//
// Revision 1.8  2012-08-20 13:22:22  titus
// php interfaces now uses authentication session instead of tickets. the class definitions can be found in SecSignIDApi.php
//
// Revision 1.7  2012-07-11 14:34:04  titus
// getTicketStatus() was renamed to getTicketState.
// request itself was renamed from ReqGetAuthTicketStatus to ReqGetAuthTicketState
//
// Revision 1.6  2012-01-18 14:49:18  titus
// php interface has been renamed to SecSignerIDConnector. changed function names and parameter lists
//
// Revision 1.5  2012-01-02 14:18:18  titus
// method names have been changed in SecPKIConnector. the MobielAuthTicket does not contain ticket state any more.
//
// Revision 1.4  2011-10-04 16:25:20  titus
// renamed http parameter userid to secsignerid
//
// Revision 1.3  2011-07-27 11:18:38  titus
// adapted changes in class SecPKIConnector
//
// Revision 1.2  2011-05-23 14:33:24  titus
// if ticket has been accepted or denied, it has to be disposed manually.
//
// Revision 1.1  2011-03-31 13:16:45  titus
// initial version

//
// SecSign ID Api example in php.
//
// (c) 2014 SecSign Technologies Inc.
//

	include 'SecSignIDApi.php';
    
    function logFromSecSignIDApi($message)
    {
        //$log = &JLog::getInstance('secsignid.log');
        //$log->addEntry(array('comment' => 'SecSignIDApi: ' . $message));
        echo $message . PHP_EOL;
    };

	//
	//
	// Example how to retrieve an authentication session, ask its status and withdraw the authentication session.
    //
    //	
    
    //
    // Create an instance of SecSignIDApi.
    //
    echo "create new instance of SecSignIDApi." . PHP_EOL;
	$secSignIDApi = new SecSignIDApi();
                                                  
    //
    // If extended logging is wished set a reference to a function (or the name of a function). 
    // All messages will be given as parameter to this function. 
    // If the function is callable this will be used to log messages
    //
    $secSignIDApi->setLogger('logFromSecSignIDApi');


    //
    // The servicename and address is mandatory. It has to be send to the server.
    // The value of $servicename will be shown on the display of the smartphone. The user then can decide whether he accepts the authentication session shown on his mobile phone.
    //
    $servicename = "Your Website Login";
    $serviceaddress = "http://www.yoursite.com/";
    $secsignid = "username";
    
    //
    // Get a auth session for the sepcified SecSign ID
    //
    // If $secsignid is null or empty an exception is thrown.
    // If $servicename is null or empty an exception is thrown.
    //
    try
    {
        $authSession = $secSignIDApi->requestAuthSession($secsignid, $servicename, $serviceaddress);
        echo "got authSession '" . $authSession . "'" . PHP_EOL;
    }
    catch(Exception $e)
    {
        echo "could not get an authentication session for SecSign ID '" . $secsignid . "' : " . $e->getMessage() . PHP_EOL;
        exit();
    }
    
    //
    // Get the auth session status
    //
    // If $authSession is null or not an instance of AuthSession an exception is thrown
    //
    $authSessionState = AuthSession::NOSTATE;
    
    try
    {
        $authSessionState = $secSignIDApi->getAuthSessionState($authSession);
        echo "got auth session state: " . $authSessionState . PHP_EOL;
    }
    catch(Exception $e)
    {
        echo "could not get status for authentication session '" . $authSession->getAuthSessionID() . "' : " . $e->getMessage() . PHP_EOL;
        exit();
    }
    
    
    
    // If the script shall wait till the user has accepted the auth session or denied it,  it has to ask the server frequently
    $secondsToWaitUntilNextCheck = 10;
    $noError = TRUE;
	
    while(($authSessionState == AuthSession::PENDING || $authSessionState == AuthSession::FETCHED) && $noError)
    {
        try
        {
            $authSessionState = $secSignIDApi->getAuthSessionState($authSession);
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
    
    
    if($authSessionState == AuthSession::AUTHENTICATED)
    {
        echo "user has accepted the auth session '" . $authSession->getAuthSessionID() . "'." . PHP_EOL;
        
        $secSignIDApi->releaseAuthSession($authSession);
        echo "auth session '" . $authSession->getAuthSessionID() . "' was released." . PHP_EOL;
    }
    else if($authSessionState == AuthSession::DENIED)
    {
        echo "user has denied the auth session '" . $authSession->getAuthSessionID() . "'." . PHP_EOL;
        $authSessionState = $secSignIDApi->cancelAuthSession($authSession); // after the auth session is successfully canceled it is not possible to inquire the status again
        if($authSessionState == AuthSession::CANCELED)
        {
            echo "authentication session successfully cancelled..." . PHP_EOL;
        }
    }
    else {
        echo "auth session '" . $authSession->getAuthSessionID() . "' has state " . authSessionState . "." . PHP_EOL;
        $authSessionState = $secSignIDApi->cancelAuthSession($authSession); // after the auth session is successfully canceled it is not possible to inquire the status again
        if($authSessionState == AuthSession::CANCELED)
        {
            echo "authentication session successfully cancelled..." . PHP_EOL;
        }
    }
    
    //
    // When using SecSignIDApi.php in a webservice like Joomla, Wordpress or something else
    // it can be necessary to get and check an auth session in two steps.
    // First the auth session is requested. After that all important information has to be stored. In joomla this is done by writing the information to the html output as a hidden field.
    // The html output will show the access pass and ask the user to press a button after he has accepted or denied the auth session on his mobile phone.
    // By pressing the button all information will be send to the server to a script which can use different functions to create an instance of MobileAuth session.
    // Using this instance the secsign id server can be asked for the auth session status. The script then has to decide whether the user is redirected to an internal area or back to the login.
    //
    
    echo PHP_EOL . PHP_EOL . PHP_EOL;
    echo "creating new auth session for secsign id '" . $secsignid . "'." . PHP_EOL;
    
    $secSignIDApi = new SecSignIDApi();
    $authSession = $secSignIDApi->requestAuthSession($secsignid, $servicename, $serviceaddress);
    
    echo "got auth session '" . $authSession . "'." . PHP_EOL;
        
    // The following information are required to request authsession state. These information shall be written to html output, session variables etc.
    $secsignidFromAuthSession = $authSession->getSecSignID();
    $authsessionidFromAuthSession = $authSession->getAuthSessionID();
    $requestidFromAuthSession = $authSession->getRequestID();
    $icondataFromAuthSession = $authSession->getIconData(); // This data is base64 encoded and can be displayed directly in the browser

    $secSignIDApi = null;

    // Build a valid AuthSession instance
    echo "create new AuthSession instance." . PHP_EOL;
    $authSession = new AuthSession();
    $authSession->createAuthSessionFromArray(
                                        array('secsignid'           => $secsignidFromAuthSession,
                                              'authsessionid'		=> $authsessionidFromAuthSession,
                                              'requestid'           => $requestidFromAuthSession,
                                              'servicename'         => $servicename,
                                              'serviceaddress'      => $serviceaddress,
                                              'authsessionicondata' => icondataFromAuthSession));
    
    
    // Create new SecSignIDApi instance
    echo "create new instance of SecSignIDApi." . PHP_EOL;
    $secSignIDApi = new SecSignIDApi();
    
    
    // Ask for auth session state using the newly build AuthSession instance
    echo "get auth session status from server." . PHP_EOL;
    $authSessionState = $secSignIDApi->getAuthSessionState($authSession);
    
    echo "cancel auth session." . PHP_EOL;
    
    // Canceling the ticket here is just to clean up
    $authSessionState = $secSignIDApi->cancelAuthSession($authSession);
    
?>
