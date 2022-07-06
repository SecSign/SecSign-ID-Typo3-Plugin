<?php
namespace SecSign\Secsign\LoginProvider;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use SecSign\Secsign\Connector\SecSignIDApi;
use TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility;

/**
 * Class SecSignIDLoginProvider
 */
class SecSignIDBELoginProvider extends UsernamePasswordLoginProvider 
{

    private $view;
    private $pageRenderer;
    private $loginController;
    private $languageService;
    private $DEBUG;
   
    
    
    /*
            $template = 'typo3conf/ext/secsign/Resources/Private/Backend/Templates/check.php';
            parent::render($view, $pageRenderer, $loginController);
            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
    */  
    
    
    /**
     * constructor that gets configuration and sets important values
     */
    function __construct()
    {
        $this->languageService=$GLOBALS['LANG'];
        $this->languageService->includeLLFile('EXT:secsign/Resources/Private/Language/locallang.xlf');
              
        $this->DEBUG=\SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('debugLogging');
        \SecSign\Secsign\Utils\SecSignPinAccountHelper::checkForPinAccountAndCreateIfNeeded();
        
        
        
        
        

    }
    
   
    
    
    
    /**
     * The main method of the plugin
     *
     * @param string $content The PlugIn content
     * @param array $conf The PlugIn configuration
     * @return string The content that is displayed on the website
     * @throws \RuntimeException when no storage PID was configured.
     */
    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController)
    {
        //get language-file
        $this->view=$view;
        $this->pageRenderer=$pageRenderer;
        $this->loginController= $loginController;
        
        $this->content = '';
        $this->returnURL=GeneralUtility::_GP('returnURL');
        
        error_log(print_r($_POST,true));
        if(GeneralUtility::_GP('userident'))
        {
            if($this->DEBUG) {error_log('auth in progress');}
            return;
        }
        
        switch(GeneralUtility::_GP('secsign_method'))
        {
           case 'secsign_checkLogin':
                //check Login
                $this->handleFirstStep();
                break;
            case 'secsign_changeMethod':
                $this->handleSelectedStart();
                break;
            case 'secsign_change_to':
                $this->handleSelectedActiveOrNew();
                break;
            case 'secsign_change_to_inactive':
                //change Method
                $this->handleSelectedInactive();
                break;
            case 'secsign_checkQRCode':
                $this->handleSecSignQRCodeCheck();
                break;
            case 'secsign_checkRestoreQRCode':
                $this->handleSecSignQRCodeRestoreCheck();
                break;
            case 'secsign_existing_create':
                $this->handleIDDialogExisting();
                break;
            case 'secsign_free_create':
                $this->handleIDDialogCreate();
                break;
            case 'secsign_checkAccessPass':
            case 'secsign_checkAccessPassJustConfirm':
                $this->handleSecSignAuthCheck();
                break;
            case 'secsign_cancelAccessPass':
                $this->handleSecSignAuthCancel();
                break;
            case 'secsign_checkTOTP':
                $this->handleTOTPCheck();
                break;
            case 'secsign_checkMailOTP':
                $this->handleMailOTPLoginCheck();
                break;
            case 'secsign_registerFIDOStart':
                $this->handleFIDORegisterStart();
                return $this->content;
                break;
            case 'secsign_finishFIDO':
                $this->handleFIDORegisterFinish();
                break;
            case 'fidoFinishAuthenticate':
                $this->handleFIDOAuthFinish();
                break;
            case 'secsign_back_from_change_to':
            case 'secsign_back_from_change_to_inactive':
                $this->handleSelectedBack();
                break;
            default:
                //check existing auth session
                if(!$this->checkExistingAuthSession())
                {
                    //check if user is already loggedin
                    $this->checkUserIsLoggedIn();
                }
                break;
                         
                               
        }
    
                        
    }
    
    
   private function handleFirstStep()
    {
        $username=$_POST['secsign_username'];
        //check Login  
        if($this->handlePasswordLoginCheck($username))
        {
            if($this->checkUserNeeds2FA($username) && \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('feActivated'))
            {
                $this -> determineSecondStep($username);
            }else{
                //no 2FA needed -> create hash so login will go on
                $this -> handlePasswordLogin($username);
            }

        }else{
            //wrong username/password
            $this->errorMsg=$this->languageService->getLL('secsign_msg_wrong_password');
            $this->showLogin();
            return;
        }
    }
    
    /**
     * checks if password is correct or not
     * @return boolean true, if password for username is correct, else false
     */
    protected function handlePasswordLoginCheck($username)
    {
        //get parameters from request
        $password=$_POST['secsign_password'];
        
        if($this->DEBUG) { error_log("login for: ".$username); }
        
        //get pwd from DB
        $passwordFromDB=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser("password",$username);
        
        if(!$passwordFromDB)
        {
            //no password found -> no user with this username
            if($this->DEBUG) { error_log("no password found for: ".$username); }
            return false;
        }
        if($this->DEBUG) { error_log("password in db found for: ".$username); }
        
        //get unhashed password
        if($password===$passwordFromDB)
        {
            //unhashed password correct, should never occur
            if($this->DEBUG) { error_log("unhashed password correct for: ".$username); }
            return true;
        }else{
            //check hashed password
            if(class_exists('\TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory'))
            {
                //Typo3 > 10.x
                $mode = 'BE';
                $success = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::class)
                ->get($passwordFromDB, $mode) # or getDefaultHashInstance($mode)
                ->checkPassword($password, $passwordFromDB);
                if($success)
                {
                    if($this->DEBUG) { error_log("password correct for: ".$username); }
                    return true;
                }else{
                    if($this->DEBUG) { error_log("password incorrect for: ".$username); }
                    return false;
                }
            }else{
                //Typo3 < 10.x
                 //check hashed password
                $defaultHashingClassName=SaltedPasswordsUtility::getDefaultSaltingHashingMethod('BE');
                $defaultHashingInstance= new $defaultHashingClassName();
                if($defaultHashingInstance->checkPassword($password,$passwordFromDB))
                {
                    if($this->DEBUG) { error_log("password correct for: ".$username); }
                    return true;
                }else{
                    if($this->DEBUG) { error_log("password incorrect for: ".$username); }
                    return false;
                }
            }
            
        }
        
    }
    
    /**
     * handles login by username and password, if no 2FA needed
     * @return string html to render
     */
    protected function handlePasswordLogin($username) {
        
        $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('secsignid', $username);
        
        //create random hash for user
        $random=rand();
        $hash=md5($random);
          
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('lastMethod', '', $username);
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('activeMethods', '', $username);
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('chooseToActivate', '', $username);
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('needsAccessToken', '', $username);
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('tokenID', '', $username);
        if($secsignid)
        {
            //delete old values if exists
            \SecSign\Secsign\Accessor\DBAccessor::deleteHashForSecSignIDBE($secsignid);

            //save random number as token for user
            \SecSign\Secsign\Accessor\DBAccessor::insertHashForSecSignIDBE($hash,$secsignid);


            $this->showLoginInProgressPWD($hash,$secsignid,$username);
        }else{
            //delete old values if exists
            \SecSign\Secsign\Accessor\DBAccessor::deleteHashForSecSignIDBE($username);

            //save random number as token for user
            \SecSign\Secsign\Accessor\DBAccessor::insertHashForSecSignIDBE($hash,$username);


            $this->showLoginInProgressPWD($hash,$username,$username);
        }

        
    }
    
    private function determineSecondStep($username)
    {
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('chooseToActivate', '', $username);
        
        $allowedOptions= \SecSign\Secsign\Accessor\DBAccessor::getAllowed2FAMethodsBE($username);
        if($this->DEBUG) {error_log('allowedOptions is '. print_r($allowedOptions,true));}
        
        $activeMethods=\SecSign\Secsign\Accessor\DBAccessor::getActivated2FAMethodsBE($username);
        if($this->DEBUG) {error_log('activeMethods is '. print_r($activeMethods,true));}
        
        $lastMethod= \SecSign\Secsign\Accessor\DBAccessor::getLastMethodBE($username);
        if($this->DEBUG) {error_log('lastMethod is '.$lastMethod);}
        
        $lastMethod= \SecSign\Secsign\Accessor\DBAccessor::getLastMethodBE($username);
        if($this->DEBUG) {error_log('lastMethod is '.$lastMethod);}
        
        //how many active methods exists
        switch(count($activeMethods))
        {
            //no active method -> show selection to activate first
            case 0:
                $this->showSelectionToInactive($allowedOptions,$username,true);
                break;
            //one active method -> start auth with active method
            case 1:
                if(in_array($activeMethods[0], $allowedOptions))
                {
                    switch($activeMethods[0])
                    {
                        case 'secsignid':
                            $this->handleSecSignPre();
                            break;
                        case 'fido':
                            $this->handleFIDOPre();
                            break;
                        case 'totp':
                            $this->handleTOTPPre();
                            break;
                        case 'mailotp':
                            $this->handleMailOTPPre();
                            break;
                    } 
                }else
                {
                    $subArray=array_diff($activeMethods,$allowedOptions);
                    if(count($subArray)>0)
                    {
                        $this->showSelectionToActive($subArray,$username,false);
                    }else{
                        $this->showSelectionToInactive($allowedOptions, $username);
                    }
                }
                break;
            //multiple active methods 
            case 2:
            case 3:
            case 4:
                //if lastMethod exists, use it
                if($lastMethod && in_array($lastMethod, $allowedOptions))
                {
                    switch($lastMethod)
                    {
                        case 'secsignid':
                            $this->handleSecSignPre();
                            break;
                        case 'fido':
                            $this->handleFIDOPre();
                            break;
                        case 'totp':
                            $this->handleTOTPPre();
                            break;
                        case 'mailotp':
                            $this->handleMailOTPPre();
                            break;
                    } 
                }else{
                //no last method, so show dialog to select or activate new if allowed
                    $subArray=array_intersect($activeMethods,$allowedOptions);
                    if(count($subArray)>0)
                    {
                        $notActivated=array_diff($allowedOptions,$activeMethods);
                        if(count($notActivated)>0)
                        {
                            $this->showSelectionToActive($subArray,$username,true);
                        }else{
                            $this->showSelectionToActive($subArray,$username,false);
                        }
                        
                    }else{
                        $this->showSelectionToInactive($allowedOptions,$username,false);
                    }
                }
                break;
        }   
    }
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    /**
     * handles start of fido login
     */
    private function handleFIDOPre($accessToken=null)
    {
        $username=$_POST['secsign_username'];
         //check if user has SecSignID
        if($this->checkUserHasSecSignID($username))
        {
            $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('secsignid', $username);
            //check fido activated
            if($this->checkFIDOIsActive($username))
            {
                //start login with fido
                $this->handleFIDOAuthStart($username,$secsignid);
            }else{
                //allow register of FIDO device
                $this->showFIDORegisterNameInput($username,$secsignid,$accessToken);
            }
        }else{
            //no SecSign ID, so create one
            $this->handleFIDOCreateID($username);
        }
    }
    
    private function checkFIDOIsActive($username)
    {
        $activeMethods=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('activeMethods', $username);
        if(strpos($activeMethods, 'fido')===false)
        {
            return false;
        }else{
            return true;            
        }
    }
    
    private function handleFIDOAuthStart($username,$secsignid)
    {
        $changeAllowed= \SecSign\Secsign\Accessor\DBAccessor::getChangeAllowedBE($username);
        
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
        $result=$secSignIDApi->startFIDOAuthentication($secsignid, $_SERVER['SERVER_NAME']);
        
        $this->showFIDOAuth($username,$secsignid,$result,$changeAllowed);
    }
    /**
     * handles finish of FIDO Auth
     */
    private function handleFIDOAuthFinish()
    {
        $username=$_POST['secsign_username'];
        if($this->DEBUG) {error_log('username is '.$username);}

        $secsignid=$_POST['secsigniduserid'];
        if($this->DEBUG) {error_log('secsignid is '.$secsignid);}

        $credentialId=$_POST['credentialId'];
        if($this->DEBUG) {error_log('credentialId is '.$credentialId);}

        $clientDataJson=$_POST['clientDataJson'];
        if($this->DEBUG) {error_log('clientDataJson is '.$clientDataJson);}

        $authenticatorData=$_POST['authenticatorData'];
        if($this->DEBUG) {error_log('authenticatorData is '.$authenticatorData);}

        $signature=$_POST['signature'];
        if($this->DEBUG) {error_log('signature is '.$signature);}

        $userHandle=$_POST['userHandle'];
        if($this->DEBUG) {error_log('userHandle is '.$userHandle);}
        
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
        
        //check if activation of methods is in progress and accessToken is needed
        $toConfirm=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('chooseToActivate', $username);
        if($this->DEBUG) {error_log('toConfirm is '.$toConfirm);}
        
        $needsAccessToken=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('needsAccessToken', $username);
        if($this->DEBUG) {error_log('needsAccessToken is '.$needsAccessToken);}        
        
        //if confirmation with AccessToken is needed, get AccessToken with authentication
        if($toConfirm && $needsAccessToken && $toConfirm!='fido')
        {
            $tokenID=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('tokenID', $username);
            $answer=$secSignIDApi->requestAccessTokenForFIDOAuthentication($tokenID,$secsignid, $credentialId, $clientDataJson, $authenticatorData, $signature, $userHandle);
            if($answer['errormsg'])
            {
                //error on fido, show error on login page
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('chooseToActivate', '', $username);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('needsAccessToken', '', $username);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('tokenID', '', $username);
                $this->errorMsg=$this->languageService->getLL('msg_FIDO_ERROR');
                $this->showLogin();
                return;
            }else{
                $token=$answer['token'];
                if($this->DEBUG) {error_log('token is '.$token);}
            }
           
        //else just check the authentication    
        }else{
            $answer=$secSignIDApi->finishFIDOAuthentication($secsignid, $credentialId, $clientDataJson, $authenticatorData, $signature, $userHandle);
            
            if($answer['errormsg'])
            {
                //error on fido, show error on login page
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('chooseToActivate', '', $username);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('needsAccessToken', '', $username);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('tokenID', '', $username);
                $this->errorMsg=$this->languageService->getLL('msg_FIDO_ERROR');
                $this->showLogin();
                return;
            }
            
        }
        
        //check that username of authentication fits username of secsignid and create hash for login
        if($this->checkUsernamesAreEqual($username,$secsignid))
        {
            //set fido as last and as activated method
            \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('lastMethod', 'fido', $username);
            \SecSign\Secsign\Accessor\DBAccessor::addActiveMethodForBEUser('fido', $username);

            //check if login was intended to confirm activation of other method
            $toConfirm=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('chooseToActivate', $username);
            if($toConfirm)
            {
                switch ($toConfirm)
                {
                    case 'secsignid':
                        //start secsignid, after confirmation with fido
                        $this->handleSecSignPre($token);
                        return;
                    case 'fido':
                        //fido should be activated, so login
                        $hash=$this->createHash($username, $secsignid);
                        \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('chooseToActivate', '', $username);
                        \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('needsAccessToken', '', $username);
                        \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('tokenID', '', $username);
                        $this->showLoginInProgressPWD($hash,$secsignid,$username);
                        break;
                    case 'totp':
                        //start totp, after confirmation with fido
                        $this->handleTOTPPre($token);
                        break;
                    case 'mailotp':
                        //start mailotp, after confirmation with fido
                        $this->handleMailOTPPre($token);
                        break;
                }
            }else{
                //no confirm needed, so just login
                $hash=$this->createHash($username, $secsignid);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('chooseToActivate', '', $username);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('needsAccessToken', '', $username);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('tokenID', '', $username);
                $this->showLoginInProgressPWD($hash,$secsignid,$username);
                return;
            }
        }

        
                
         
                 
         
    }
    
    
    /**
     * handles start of FIDO device register,
     * tell the server the name of fido device and get all needed information from server
     */
    private function handleFIDORegisterStart()
    {
        $username=$_POST['secsign_username'];
        if($this->DEBUG) {error_log('username is '.$username);}
        
        $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('secsignid', $username);
        if(!$secsignid)
        {
            $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('secsignid_temp', $username);
        }
        if($this->DEBUG) {error_log('secsignid is '.$secsignid);} 
        
        //get entered name for FIDO-Authenticator
        $credentialName=$_POST['fido-register-name'];
        if($this->DEBUG) {error_log('credentialName is '.$credentialName);}
        
        //is AccessToken given? Use to start FIDO register
        if($_POST['accesstoken'] && $_POST['accesstoken']!='')
        {
            $accesstoken=$_POST['accesstoken'];
            $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
            $result=$secSignIDApi->startFIDORegister($secsignid, $credentialName, $_SERVER['SERVER_NAME'],$accesstoken);
        }else{
            $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
            $result=$secSignIDApi->startFIDORegister($secsignid, $credentialName, $_SERVER['SERVER_NAME']);
        }
        
        
         //creationOptions given by server, give to Browser
        $creationOptions=$result['creationOptions'];
        if($this->DEBUG) {error_log('creationOptions is '.print_r($creationOptions,true));}
        
        //put data in response (i.e. text on html-Page)
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEFIDORequest.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        
        error_log(json_encode($creationOptions));
        
        $this->view->assign('fromServer','toGet:'.json_encode($creationOptions).';;;;;');
    }
    
     /***
     * handles finish of registration for FIDO device and starts FIDO Auth
     */
    private function handleFIDORegisterFinish()
    {
         $username=$_POST['secsign_username'];
         if($this->DEBUG) {error_log('username is '.$username);}
         
         $secsignid=$_POST['secsigniduserid'];
         if($this->DEBUG) {error_log('secsignid is '.$secsignid);}
         
         $credentialId=$_POST['credentialId'];
         if($this->DEBUG) {error_log('credentialId is '.$credentialId);}
         
         $clientDataJson=$_POST['clientDataJson'];
         if($this->DEBUG) {error_log('clientDataJson is '.$clientDataJson);}
         
         $attestationObject=$_POST['attestationObject'];
         if($this->DEBUG) {error_log('attestationObject is '.$attestationObject);}
         
         //check if accessToken is given and use it to register Fido device
         if($_POST['accesstoken'] && $_POST['accesstoken']!='')
         {
            $accessToken=$_POST['accesstoken'];
            $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
            $secSignIDApi->finishFIDORegister($secsignid, $credentialId, $clientDataJson, $attestationObject,$accessToken);
          
            $this->handleFIDOAuthStart($username,$secsignid);
         }else{
            $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
            $secSignIDApi->finishFIDORegister($secsignid, $credentialId, $clientDataJson, $attestationObject);
          
            $this->handleFIDOAuthStart($username,$secsignid);
         }
         
    }
    
    
    
    private function handleFIDOCreateID($username)
    {
        $newSecSignId=$this->determineSecSignIDForUsername($username,false);
        //check if Secsign ID exists
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
        try {
            $exists = $secSignIDApi->checkSecSignID($newSecSignId);
            if($exists)
            {
                $tempSecSignID='';
                $i=1;
                do{
                    $tempSecSignID=$newSecSignId . $i;
                    $exists = $secSignIDApi->checkSecSignID($tempSecSignID);
                    $i++;
                }while($exists);
                $newSecSignId=$tempSecSignID;
                    
            }
           
            $this->createSecSignIDWithoutView($username,$newSecSignId);
            $this->showFIDORegisterNameInput($username,$newSecSignId);
         } catch (\Exception $e) {
             error_log($e->getMessage());
             $this->errorMsg=$this->languageService->getLL('msg_ERROR_LOGIN');
             $this->showLogin();
             return;
         }
    }      
    
    private function handleTOTPPre($accessToken=null)
    {
        $username=$_POST['secsign_username'];
         //check if user has SecSignID
        if($this->checkUserHasSecSignID($username))
        {
            $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('secsignid', $username);
            if($this->checkTOTPIsActive($username))
            {
                $this->handleTOTPStart($username,$secsignid);
            }else{
                $this->handleTOTPShowQRCode($username,$accessToken);
            }
        }else{
            //no SecSign ID, so create one
            $this->handleTOTPCreateID($username);
        }
    }
    
    private function checkTOTPIsActive($username)
    {
        $activeMethods=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('activeMethods', $username);
        if(strpos($activeMethods, 'totp') === false)
        {
            return false;
        }else{
            return true;
        }
    }
    
    private function handleTOTPStart($username,$secsignid)
    {
        $changeAllowed= \SecSign\Secsign\Accessor\DBAccessor::getChangeAllowedBE($username);
        
        $this->showTOTPAuth($username,$secsignid,$changeAllowed);
    }
    
    private function handleTOTPShowQRCode($username,$accessToken)
    {
        $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('secsignid', $username);
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
        
        //accesstoken given? use it for getting TOTP QRCode
        if($accessToken)
        {
            $result=$secSignIDApi->getTOTPQRCode($secsignid,$accessToken);
        }else{
            $result=$secSignIDApi->getTOTPQRCode($secsignid);
        }
       
        //get secret from TOTP Url
        $fullURL=$result['totp']['totpkeyuri'];
        $startIndex = strpos($fullURL,'secret=') + 7;
        $endIndex = strpos($fullURL,"&",$startIndex);
        $secret = substr($fullURL, $startIndex,$endIndex-$startIndex);
        $this->showTOTPQRCode($username,$secsignid,$result['totp']['totpqrcodebase64'],$secret);
    }
    
    private function handleTOTPCreateID($username)
    {
        $newSecSignId=$this->determineSecSignIDForUsername($username,false);
        //check if Secsign ID exists
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
        try {
            $exists = $secSignIDApi->checkSecSignID($newSecSignId);
            if($exists)
            {
                $tempSecSignID='';
                $i=1;
                do{
                    $tempSecSignID=$newSecSignId . $i;
                    $exists = $secSignIDApi->checkSecSignID($tempSecSignID);
                    $i++;
                }while($exists);
                $newSecSignId=$tempSecSignID;
                    
            }
           
            $this->createSecSignIDWithoutView($username,$newSecSignId);
            $result=$secSignIDApi->getTOTPQRCode($newSecSignId);
            $fullURL=$result['totp']['totpkeyuri'];
            $startIndex = strpos($fullURL,'secret=') + 7;
            $endIndex = strpos($fullURL,"&",$startIndex);
            $secret = substr($fullURL, $startIndex,$endIndex-$startIndex);
            $this->showTOTPQRCode($username,$newSecSignId,$result['totp']['totpqrcodebase64'],$secret);
            
         } catch (\Exception $e) {
             error_log($e->getMessage());
             $this->errorMsg=$this->languageService->getLL('msg_ERROR_LOGIN');
             $this->showLogin();
             return;
         }
    } 
    
    private function handleTOTPCheck()
    {
        $totpCode=$_POST['secsign_totp'];
        $secsignid=$_POST['secsigniduserid'];
        $username=$_POST['secsign_username'];
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
        
        $toConfirm=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('chooseToActivate', $username);
        if($this->DEBUG) {error_log('toConfirm is '.$toConfirm);}
        $needsAccessToken=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('needsAccessToken', $username);
        if($this->DEBUG) {error_log('needsAccessToken is '.$needsAccessToken);}
        
        //if totp is used to get accessToken
        if($toConfirm && $needsAccessToken && $toConfirm!='totp')
        {
            //get token for activation of new method
            $tokenID=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('tokenID', $username);
            $answer=$secSignIDApi->requestAccessTokenForTOTPAuthentication($tokenID,$totpCode);
            
            if($answer['error'])
            {
                $success=false;
            }else{
                $success=true;
                $token=$answer['token'];
            }
            
        }else{
        //else just check the totp
            $success=$secSignIDApi->checkTOTPCode($secsignid,$totpCode);
            
        }
        if($success)
        {
            $toConfirm=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('chooseToActivate', $username);
            if($toConfirm)
            {
                
                switch ($toConfirm)
                {
                    case 'secsignid':
                        $this->handleSecSignPre($token);
                        break;
                    case 'fido':
                        $this->handleFIDOPre($token);
                        break;
                    case 'totp':
                        if($this->checkUsernamesAreEqual($username, $secsignid))
                        {
                            $hash=$this->createHash($username, $secsignid);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('lastMethod', 'totp', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::addActiveMethodForBEUser('totp', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('chooseToActivate', '', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('needsAccessToken', '', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('tokenID', '', $username);
                            $this->showLoginInProgressPWD($hash,$secsignid,$username);
                            return;
                            
                        }
                        break;
                    case 'mailotp':
                        $this->handleMailOTPPre($token);
                        break;

                }
            }else{
                if($this->checkUsernamesAreEqual($username, $secsignid))
                {
                    $hash=$this->createHash($username, $secsignid);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('lastMethod', 'totp', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::addActiveMethodForBEUser('totp', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('chooseToActivate', '', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('needsAccessToken', '', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('tokenID', '', $username);
                    $this->showLoginInProgressPWD($hash,$secsignid,$username);
                    return;

                }
            }
            
            
        }else{
             $this->errorMsg=$this->languageService->getLL('secsign_msg_wrong_totp');
             $this->showTOTPAuth($username,$secsignid,$_POST['secsignidswitchallowed']);
        }
        
        
    }
    
    private function handleMailOTPPre()
    {
        $username=$_POST['secsign_username'];
         //check if user has SecSignID
        if($this->checkUserHasSecSignID($username))
        {
            $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('secsignid', $username);
            $this->handleMailOTPStart($username,$secsignid);
        }else{
            //no SecSign ID, so create one
            $this->handleMailOTPCreateID($username);
        }
    }
    
    
    private function handleMailOTPStart($username,$secsignid)
    {
        $email= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('email', $username);
        
        $changeAllowed= \SecSign\Secsign\Accessor\DBAccessor::getChangeAllowedBE($username);
        
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
        $secSignIDApi->getMailOTPCode($secsignid, $email);
        
        $this->showMailOTPAuth($username,$secsignid,$changeAllowed);
    }
    
    private function handleMailOTPCreateID($username)
    {
        $newSecSignId=$this->determineSecSignIDForUsername($username,false);
        //check if Secsign ID exists
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
        try {
            $exists = $secSignIDApi->checkSecSignID($newSecSignId);
            if($exists)
            {
                $tempSecSignID='';
                $i=1;
                do{
                    $tempSecSignID=$newSecSignId . $i;
                    $exists = $secSignIDApi->checkSecSignID($tempSecSignID);
                    $i++;
                }while($exists);
                $newSecSignId=$tempSecSignID;
                    
            }
           
            $this->createSecSignIDWithoutView($username,$newSecSignId);
            $this->handleMailOTPStart($username,$newSecSignId);
            
         } catch (\Exception $e) {
             error_log($e->getMessage());
             $this->errorMsg=$this->languageService->getLL('msg_ERROR_LOGIN');
             $this->showLogin();
             return;
         }
    }       
    
    private function handleMailOTPLoginCheck()
    {
        //TODO cleanup
        if($_SERVER['HTTPS'])
        {
            $serviceaddress='https://'.$_SERVER['HTTP_HOST'];
        }else{
            $serviceaddress='http://'.$_SERVER['HTTP_HOST'];
        }
        $mailotpCode=$_POST['secsign_mailotp'];
        $secsignid=$_POST['secsigniduserid'];
        $username=$_POST['secsign_username'];
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
        
        $toConfirm=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('chooseToActivate', $username);
        
        
        if($secSignIDApi->checkMailOTPCode($secsignid,$mailotpCode))
        {

            //get username for authed secsignid
            $usernameForSecSignID=\SecSign\Secsign\Accessor\DBAccessor::getValueForSecSignIDBE("username",$secsignid);


            if(!$usernameForSecSignID)
            {
                 //check temp IDs
                 $tempusernameForSecSignID=\SecSign\Secsign\Accessor\DBAccessor::getValueForTempSecSignIDBE("username",$secsignid);
                     if($tempusernameForSecSignID)
                     {
                         //save as secsignid
                         //delete temp secsignid
                         \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('secsignid_temp','',$tempusernameForSecSignID);
                         \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('secsignid',$secsignid,$tempusernameForSecSignID);
                         $usernameForSecSignID=$tempusernameForSecSignID;

                     }else{
                         //no user for the authed SecSign ID found -> ignore
                         $this->errorMsg=$this->languageService->getLL('msg_NO_USER');
                         $this->showLogin();
                         return;
                     }
            }


            \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('lastMethod', 'mailotp', $usernameForSecSignID);
            \SecSign\Secsign\Accessor\DBAccessor::addActiveMethodForBEUser('mailotp', $usernameForSecSignID);


            if($toConfirm)
            {
                switch ($toConfirm)
                {
                     case 'secsignid':
                        $this->handleSecSignPre();
                        break;
                    case 'fido':
                        break;
                    case 'totp':
                        $this->handleTOTPPre();
                        break;
                    case 'mailotp':
                       if($this->checkUsernamesAreEqual($usernameForSecSignID, $secsignid))
                        {
                           $hash=$this->createHash($usernameForSecSignID, $secsignid);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('chooseToActivate', '', $usernameForSecSignID);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('needsAccessToken', '', $usernameForSecSignID);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('tokenID', '', $usernameForSecSignID);
                            $this->showLoginInProgressPWD($hash,$secsignid,$usernameForSecSignID);
                            return;
                            
                        }
                        break;
                }
            }else{
                if($this->checkUsernamesAreEqual($usernameForSecSignID, $secsignid))
                {
                    $hash=$this->createHash($usernameForSecSignID, $secsignid);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('chooseToActivate', '', $usernameForSecSignID);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('needsAccessToken', '', $usernameForSecSignID);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('tokenID', '', $usernameForSecSignID);
                    $this->showLoginInProgressPWD($hash,$secsignid,$usernameForSecSignID);
                    return;

                }
            }
        }else{
             $this->errorMsg=$this->languageService->getLL('secsign_msg_wrong_password');
             $this->showMailOTPAuth($username,$secsignid,$_POST['secsignidswitchallowed']);
        }
            
        
        
    }
    
    
            
    
    private function handleSelectedStart()
    {
        
        $secsignid=$_POST['secsigniduserid'];
        if($this->DEBUG) {error_log('secsignid is '.$secsignid);}
        
        $username=$_POST['secsign_username'];
        if(!$username)
        {
             $username= \SecSign\Secsign\Accessor\DBAccessor::getValueForSecSignIDBE("username", $secsignid);
        }
        if($this->DEBUG) {error_log('username is '.$username);}

        //if switch from SecSign ID cancel AccessPass to prevent freeze of ID
        $changeFrom=$_POST['secsign_changeFrom'];
        if($this->DEBUG) {error_log('secsign_changeFrom is '.$secsign_changeFrom);}
    
        if($changeFrom=='secsignid')
        {
            //cancel AccessPass
            $authsessionid=$_POST['secsignidauthsessionid'];

            //cancel auth session on server
            $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
            try{
                 $secSignIDApi-> cancelAuthSession($authsessionid);
                 session_start();
                 unset($_SESSION['secsignidauthsessionid']);
                 unset($_SESSION['secsignidauthsessionicondata']);
                 unset($_SESSION['secsignidservicename']);
                 unset($_SESSION['secsigniduserid']);
                 unset($_SESSION['secsignidserviceaddress']);
                 unset($_SESSION['secsignidswitchallowed']);

             } catch (\Exception $e) {
                 error_log($e->getMessage());
                 $this->errorMsg=$this->languageService->getLL('msg_ERROR_LOGIN');
                 $this->showLogin();
                 return;
             }
        }
        
        //check already Activated one
        $activatedMethods= \SecSign\Secsign\Accessor\DBAccessor::getActivated2FAMethodsBE($username);
        if($this->DEBUG) {error_log('activatedMethods is '.print_r($activatedMethods,true));}
        
        $allowedMethods=\SecSign\Secsign\Accessor\DBAccessor::getAllowed2FAMethodsBE($username);
        if($this->DEBUG) {error_log('allowedMethods is '.print_r($allowedMethods,true));}
        
        $notActivatedMethods=\SecSign\Secsign\Accessor\DBAccessor::getNotActivatedAndAllowedMethodsBE($username);
        if($this->DEBUG) {error_log('notActivatedMethods is '.print_r($notActivatedMethods,true));}
        
        $newAllowed= count($notActivatedMethods)>0;
        if($this->DEBUG) {error_log('newAllowed is '.$newAllowed);}   
        
        $allowedAndActivated=\SecSign\Secsign\Accessor\DBAccessor::getAllowedAndActivated2FAMethodsBE($username);
        if($this->DEBUG) {error_log('allowedAndActivated is '.print_r($allowedAndActivated,true));}
        
        if(count($allowedAndActivated)>1)
        {
            //has more than one activeMethod -> show Selection + new if $newAllowed
            $this->showSelectionToActive($allowedAndActivated,$username,$newAllowed);
        }else{
            //has one method (0 methods -> no switch possible) -> show inactive methods to activate
            $this->showSelectionToInactive($notActivatedMethods,$username);
        }
    }
    
    /**
     * handles selection of an Active or new method
     * active method -> start auth , new -> show list of allowed Methods 
     */
    private function handleSelectedActiveOrNew()
    {
        $username=$_POST['secsign_username'];
        if($this->DEBUG) {error_log('username is '.$username);}
        
        $selectedMethod=$_POST['selected-method'];
        if($this->DEBUG) {error_log('change to '.$selectedMethod);}
        
        $allowedOptions= \SecSign\Secsign\Accessor\DBAccessor::getAllowed2FAMethodsBE($username);
        if($this->DEBUG) {error_log('allowedOptions is '. print_r($allowedOptions,true));}
        
        $activeMethods=\SecSign\Secsign\Accessor\DBAccessor::getActivated2FAMethodsBE($username);
        if($this->DEBUG) {error_log('activeMethods is '. print_r($activeMethods,true));}
        
        $lastMethod=\SecSign\Secsign\Accessor\DBAccessor::getLastMethodBE($username);
        if($this->DEBUG) {error_log('lastMethod is '.$lastMethod);}

        //if inactive methode selected before
        if($_POST['toConfirm']!='')
        {
            \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('chooseToActivate', $_POST['toConfirm'], $username);
            if($this->DEBUG) {error_log('toConfirm is '.$_POST['toConfirm']);}
            
            $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('secsignid', $username);
            if($this->DEBUG) {error_log('secsignid is '.$secsignid);}
            
            //check if AccessToken is needed
            if($this->checkAccessTokenNeeded($secsignid))
            {
                $capability="";
                switch ($_POST['toConfirm'])
                {
                   case 'secsignid':
                        $capability='SecSignIdDevice';
                        break;
                   case 'fido':
                        $capability='FIDODevice';
                        break;
                   case 'totp':
                        $capability='TOTPSecret';
                        break;
                   case 'mailotp':
                       //mailotp needs no accesstoken, so start other method to confirm
                       switch($selectedMethod)
                        {
                            case 'secsignid':
                                $this->handleSecSignPre();
                                break;
                            case 'fido':
                                $this->handleFIDOPre();
                                break;
                            case 'totp':
                                $this->handleTOTPPre();
                                break;
                        }
                        return;
                }
                if($this->DEBUG) {error_log('capability is is '.$capability);}
                
                //set needsAccessToken for later steps
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('needsAccessToken', true, $username);
                if($this->DEBUG) {error_log('needsAccessToken set for '.$username);}
                
                $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
                if($_SERVER['HTTPS'])
                {
                    $serviceaddress='https://'.$_SERVER['HTTP_HOST'];
                }else{
                    $serviceaddress='http://'.$_SERVER['HTTP_HOST'];
                }
                switch($selectedMethod)
                {
                   case 'secsignid':
                       if($this->DEBUG) {error_log('secsignid for accessToken');}
                       $result=$secSignIDApi->getAccessTokenAuthorization($secsignid, $serviceaddress, 'SecSignID', $capability);
                       $tokenID=$result['tokenid'];
                       $sessionID=$result['authsessionid'];
                       $this->showAccessPass($username,null, $sessionID, null, $secsignid, $serviceaddress, false, null);
                       break;
                   case 'fido':
                       if($this->DEBUG) {error_log('fido for accessToken');}
                       $result=$secSignIDApi->getAccessTokenAuthorization($secsignid, $serviceaddress, 'FIDO', $capability);
                       $tokenID=$result['tokenid'];
                       $fidoInformation=array('requestOptions'=>$result['fido']);
                       $this->showFIDOAuth($username, $secsignid, $fidoInformation, false);
                       break;
                   case 'totp':
                       if($this->DEBUG) {error_log('totp for accessToken');}
                       $result=$secSignIDApi->getAccessTokenAuthorization($secsignid, $serviceaddress, 'TOTP', $capability);
                       $tokenID=$result['tokenid'];
                       $this->handleTOTPPre();
                       break;
                   case 'mailotp':
                       $this->errorMsg=$this->languageService->getLL('msg_MAILOTP_FOR_TOKEN');
                       $this->showLogin();
                       return;

                } 
                if($this->DEBUG) {error_log('tokenID is: '.$tokenID);}
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('tokenID', $tokenID, $username); 

            }else{
                
                //access without acccesToken allowed, so start confirmation by selected method
                switch($selectedMethod)
                {
                    case 'secsignid':
                        $this->handleSecSignPre();
                        break;
                    case 'fido':
                        $this->handleFIDOPre();
                        break;
                    case 'totp':
                        $this->handleTOTPPre();
                        break;
                    case 'mailotp':
                        $this->handleMailOTPPre();
                        break;

                } 
            }

            
            
        }else{
            //active Method chosen -> just switch OR new -> show inactive methods
            switch($selectedMethod)
            {
                case 'secsignid':
                    $this->handleSecSignPre();
                    break;
                case 'fido':
                    $this->handleFIDOPre();
                    break;
                case 'totp':
                    $this->handleTOTPPre();
                    break;
                case 'mailotp':
                    $this->handleMailOTPPre();
                    break;
                case 'new':
                    $possibleSelections= array_diff($allowedOptions,$activeMethods);
                    $this->showSelectionToInactive($possibleSelections, $username,false);
                    break;
            }
        }
            
            
    }
    
    private function handleSelectedInactive()
    {
        $username=$_POST['secsign_username'];
        if($this->DEBUG) {error_log('username is '.$username);}
        
        $selectedMethod=$_POST['selected-method'];
        if($this->DEBUG) {error_log('change to '.$selectedMethod);}
        
        $allowedOptions= \SecSign\Secsign\Accessor\DBAccessor::getAllowed2FAMethodsBE($username);
        if($this->DEBUG) {error_log('allowedOptions is '. print_r($allowedOptions,true));}
        
        $activeMethods= \SecSign\Secsign\Accessor\DBAccessor::getActivated2FAMethodsBE($username);
        if($this->DEBUG) {error_log('activeMethods is '. print_r($activeMethods,true));}
        
        $lastMethod= \SecSign\Secsign\Accessor\DBAccessor::getLastMethodBE($username);
        if($this->DEBUG) {error_log('lastMethod is '.$lastMethod);}

        
        $isFirst=$_POST['isFirst'];
        if($this->DEBUG) {error_log('isFirst is '.$isFirst);}
        
        //if first method -> no confirmation by other method needed.
        if($isFirst)
        {
            //show new login method
            switch($selectedMethod)
            {
                case 'secsignid':
                    $this->handleSecSignPre();
                    break;
                case 'fido':
                    $this->handleFIDOPre();
                    break;
                case 'totp':
                    $this->handleTOTPPre();
                    break;
                case 'mailotp':
                    $this->handleMailOTPPre();
                    break;
            } 
        }else{
            //not first method so let user choose confirmation method
            $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('secsignid', $username);
            $accesTokenNeeded=$this->checkAccessTokenNeeded($secsignid);
            if($this->DEBUG) {error_log('accesTokenNeeded is '.$accesTokenNeeded);}
            
            $diffed_active=array_diff($activeMethods,array('mailotp'));
            if($this->DEBUG) {error_log('diffed_active is '.print_r($diffed_active,true));}
            
            if($accesTokenNeeded && $selectedMethod!='mailotp')
            {
                $this->showSelectionToActive($diffed_active, $username, false, $selectedMethod);
            }else{
                $this->showSelectionToActive($activeMethods, $username, false, $selectedMethod);
            }
            
        }
        
    }
    
    /**
     * handles click on Back on change View
     */
    private function handleSelectedBack()
    {
        //show login again, to allow new start of login
        $this->showLogin();
    }
    
    
    
    
    
    
    private function handleSecSignPre($accessToken=null)
    {
        $username=$_POST['secsign_username'];
        //check if user has SecSignID
        if($this->checkUserHasSecSignID($username))
        {
            $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('secsignid', $username);
            //has SecSign ID , check has Devices
            if($this->checkSecSignIDHasDevices($secsignid))
            {
                $this->handleSecSignStart($username,$secsignid);
            }else{
                $this->handleSecSignExistingRestoreQRCode($username,$secsignid,$accessToken);
            }
        }else{
            //no SecSign ID, so create one
            $this->handleIDAutomatic($username);
        }
    }
    
    
    /**
     * starts authentication session and shows the access pass
     *
     * @return string html to render
     */
    protected function handleSecSignStart($username,$secsignid)
    {
        $changeAllowed= \SecSign\Secsign\Accessor\DBAccessor::getChangeAllowedBE($username);
        //create Auth Session and show AccessPass
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
       
        $serviceaddress=$_SERVER['HTTP_HOST'];
        $servicename = \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('serviceName');
        if(!$servicename)
        {
            $servicename = "Typo3 on ".$serviceaddress;
        }
        
        $username= \SecSign\Secsign\Accessor\DBAccessor::getValueForSecSignIDBE('username', $secsignid);
        if(!$username)
        {
            $username= \SecSign\Secsign\Accessor\DBAccessor::getValueForTempSecSignIDBE('username', $secsignid);
        }
        
        $showaccesspassicons="true";
   
        try{
            $resultArray=$secSignIDApi->startAuth($secsignid,$servicename,$serviceaddress,$showaccesspassicons);
            if(!$resultArray['error'])
            {
                $authsessionicondata=$resultArray['authsessionicondata'];
                $authsessionid=$resultArray['authsessionid'];

                $this->showAccessPass($username,$authsessionicondata, $authsessionid, $servicename, $secsignid, $serviceaddress, $changeAllowed, $this->returnURL);
                
                
            }else{
                $this->errorMsg=$resultArray['errormsg'];
                $this->showLogin();
                return;
            }
            
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->errorMsg=$this->languageService->getLL('msg_ERROR_LOGIN');
            $this->showLogin();
            return;
        }
       
    }
    
    /**
     * checks whether the QR-Code is scanned -> SecSign ID created
     * @return string html to render
     */
    protected function handleSecSignQRCodeCheck()
    {
       $secsignid=$_POST['secsignid'];
       $username=$_POST['secsign_username'];
       //check whether SecSign ID is created (exists) -> QR-Code scanned
       $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
       try{
            $exists=$secSignIDApi->checkQRCode($secsignid);

            if($exists)
            {
                //created, so start auth
                //save SecSign ID as temporary, to find user for authed SecSign ID.
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser("secsignid_temp",$secsignid,$username);
                $changeAllowed= \SecSign\Secsign\Accessor\DBAccessor::getChangeAllowedBE($username);
                $this->handleSecSignStart($username,$secsignid);
            }else{
                //not created, keep testing
                $this->showQRCode($_POST['createurl'],$_POST['qrcodebase64'] ,$secsignid,$username);
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->errorMsg=$this->languageService->getLL('msg_ERROR_LOGIN');
            $this->showLogin();
            return;
        }
    }
    
    private function handleSecSignExistingRestoreQRCode($username,$secsignid,$accessToken=null)
    {
        $email=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser("email",$username);
        if($this->DEBUG) {error_log("found email ".$email. " for user ".$username);}
        
        
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser("tokenID", '', $username);
        if($this->DEBUG) {error_log("accessTokenID is ".$accessTokenID);}
        
        //accessToken given? use it to get QR-Code
        if($accessToken)
        {
            $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
         
            $result=$secSignIDApi->getRestoreSecSignIDQRCode($secsignid,$accessToken);
            $subJSON=$result[$secsignid];
            $restorationJSON=$subJSON['restoration'];
            $restoreurl=$restorationJSON['restoreurl'];
            $qrcodebase64=$restorationJSON['qrcodebase64'];
            //restoration not active -> activate by saving mail 
            if($restoreurl==null)
            {
                $result=$secSignIDApi->saveMailForUser($secsignid,$email,$accessToken);

                $restoreurl=$result['restoreurl'];
                $qrcodebase64=$result['qrcodebase64'];
            }
        }else{
            $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
         
            $result=$secSignIDApi->getRestoreSecSignIDQRCode($secsignid);
            $subJSON=$result[$secsignid];
            $restorationJSON=$subJSON['restoration'];
            $restoreurl=$restorationJSON['restoreurl'];
            $qrcodebase64=$restorationJSON['qrcodebase64'];
            if($restoreurl==null)
            {
                $secSignIDApi->saveMailForUser($secsignid,$email);

                $result=$secSignIDApi->getRestoreSecSignIDQRCode($secsignid);
                $subJSON=$result[$secsignid];
                $restorationJSON=$subJSON['restoration'];
                $restoreurl=$restorationJSON['restoreurl'];
                $qrcodebase64=$restorationJSON['qrcodebase64'];
            }
        }
        
       

        $this->showQRCodeRestore($restoreurl, $qrcodebase64, $secsignid,$username,$email);
    }
    
     /**
     * checks whether the QR-Code is scanned -> SecSign ID created
     * @return string html to render
     */
    protected function handleSecSignQRCodeRestoreCheck()
    {
       $secsignid=$_POST['secsignid'];
       $username=$_POST['secsign_username'];
       $email=$_POST['email'];
       //check whether SecSign ID is created (exists) -> QR-Code scanned
       $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
       try{
            $count=$secSignIDApi->getDevicesOfSecSignID($secsignid);

            if($count>0)
            {
                //created, so start auth
                if(\SecSign\Secsign\Accessor\SettingsAccessor::usesOnPremiseServer())
                {
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser("secsignid_temp",$secsignid,$username);
                }
                $changeAllowed= \SecSign\Secsign\Accessor\DBAccessor::getChangeAllowedBE($username);
                $this->handleSecSignStart($username,$secsignid);
            }else{
                //not created, keep testing
                $this->showQRCodeRestore($_POST['restoreurl'],$_POST['qrcodebase64'] ,$secsignid,$username,$email);
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->errorMsg=$this->languageService->getLL('msg_ERROR_LOGIN');
            $this->showLogin();
            return;
        }
    }
    
    
    /**
     * cancels an auth session, if the user pushes cancel
     * @return string html to render
     */
    protected function handleSecSignAuthCancel()
    {
       $authsessionid=$_POST['secsignidauthsessionid'];
       
       //cancel auth session on server
       $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
       try{
            $secSignIDApi-> cancelAuthSession($authsessionid);
            session_start();
            unset($_SESSION['secsignidauthsessionid']);
            unset($_SESSION['secsignidauthsessionicondata']);
            unset($_SESSION['secsignidservicename']);
            unset($_SESSION['secsigniduserid']);
            unset($_SESSION['secsignidserviceaddress']);
            unset($_SESSION['secsignidswitchallowed']);
            
            $this->showLogin();
            return;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->errorMsg=$this->languageService->getLL('msg_ERROR_LOGIN');
            $this->showLogin();
            return;
        }
       
       
    }
    
    
     /**
     * checks if auth session is authenticated on the server, i.e. selected right Access Pass
     * @return string html to render
     */
   protected function handleSecSignAuthCheck()
   {
       $authsessionid=$_POST['secsignidauthsessionid'];
       $secsignid=$_POST['secsigniduserid'];
       $username=$_POST['secsign_username'];
       
       if($this->DEBUG) {error_log('username on checkAccessPass is '.$username);}
       
       //get auth session state from server
       $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
       try{
            $authsessionstate=$secSignIDApi->checkAuthSession($authsessionid);

            $SESSION_STATE_NOSTATE = 0;
            $SESSION_STATE_PENDING = 1;
            $SESSION_STATE_EXPIRED = 2;
            $SESSION_STATE_AUTHENTICATED = 3;
            $SESSION_STATE_DENIED = 4;
            $SESSION_STATE_SUSPENDED = 5;
            $SESSION_STATE_CANCELED = 6;
            $SESSION_STATE_FETCHED = 7;
            $SESSION_STATE_INVALID = 8;

            if ($authsessionstate == $SESSION_STATE_AUTHENTICATED) {
                
                $toConfirm=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('chooseToActivate', $username);
                if($this->DEBUG) {error_log('toConfirm is '.$toConfirm);}
                $needsAccessToken=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('needsAccessToken', $username);
                if($this->DEBUG) {error_log('needsAccessToken is '.$needsAccessToken);}


                if($toConfirm && $needsAccessToken && $toConfirm!='secsignid')
                {
                    $tokenID=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('tokenID', $username);
                    if($this->DEBUG) {error_log('tokenID is '.$tokenID);}

                    $answer=$secSignIDApi->requestAccessTokenForSecSignAuthentication($tokenID);
                    $token=$answer['token'];

                    switch ($toConfirm)
                    {
                        case 'fido':
                            $this->handleFIDOPre($token);
                            break;
                        case 'totp':
                            $this->handleTOTPPre($token);
                            break;
                        case 'mailotp':
                            $this->handleMailOTPPre($token);
                            break;

                    }
                }else{
                    if($toConfirm)
                    {
                        
                        switch ($toConfirm)
                        {
                            case 'secsignid':
                                if($this->checkUsernamesAreEqual($username, $secsignid))
                                {
                                    $hash=$this->createHash($username, $secsignid,$authsessionid);
                                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('lastMethod', 'secsignid', $username);
                                    \SecSign\Secsign\Accessor\DBAccessor::addActiveMethodForBEUser('secsignid', $username);
                                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('chooseToActivate', '', $username);
                                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('needsAccessToken', '', $username);
                                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('tokenID', '', $username);
                                    $this->showLoginInProgress($hash,$secsignid,$username);
                                }
                                return;
                            case 'fido':
                                $this->handleFIDOPre();
                                break;
                            case 'totp':
                                $this->handleTOTPPre();
                                break;
                            case 'mailotp':
                                $this->handleMailOTPPre();
                                break;

                        }
                    }else{
                        if($this->checkUsernamesAreEqual($username, $secsignid))
                        {
                            $hash=$this->createHash($username, $secsignid,$authsessionid);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('lastMethod', 'secsignid', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::addActiveMethodForBEUser('secsignid', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('chooseToActivate', '', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('needsAccessToken', '', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('tokenID', '', $username);
                            $this->showLoginInProgress($hash,$secsignid,$username);
                        }
                        return;
                    }
                }


                session_start();
                unset($_SESSION['secsignidauthsessionid']);
                unset($_SESSION['secsignidauthsessionicondata']);
                unset($_SESSION['secsignidservicename']);
                unset($_SESSION['secsigniduserid']);
                unset($_SESSION['secsignidserviceaddress']);
                unset($_SESSION['secsignidswitchallowed']);
                
            }else{
                 if(($authsessionstate == $SESSION_STATE_PENDING) || ($authsessionstate == $SESSION_STATE_FETCHED)){
                     // session is still pending or fetched... next run
                     $secsignidauthsessionicondata=$_POST['secsignidauthsessionicondata'];
                     $secsignidauthsessionid=$_POST['secsignidauthsessionid'];
                     $secsignidservicename=$_POST['secsignidservicename'];
                     $secsigniduserid=$_POST['secsigniduserid'];
                     $secsignidserviceaddress=$_POST['secsignidserviceaddress'];
                     $secsignidswitchallowed=$_POST['secsignidswitchallowed'];
                     $returnURL=$_POST['returnURL'];
                     $this->showAccessPass($username,$secsignidauthsessionicondata,$secsignidauthsessionid,$secsignidservicename,$secsigniduserid,$secsignidserviceaddress,$secsignidswitchallowed,$returnURL);
                     return;
                 } else {
                     //error/abort cases -> show message
                     if (($authsessionstate == $SESSION_STATE_DENIED) || ($authsessionstate == $SESSION_STATE_EXPIRED) || 
                         ($authsessionstate == $SESSION_STATE_SUSPENDED) || ($authsessionstate == $SESSION_STATE_INVALID) || 
                         ($authsessionstate == $SESSION_STATE_CANCELED) || ($authsessionstate == $SESSION_STATE_NOSTATE)) 
                     {
                         switch($authsessionstate)
                         {
                             case $SESSION_STATE_DENIED:
                             {
                                  $this->errorMsg=$this->languageService->getLL('msg_SESSION_STATE_DENIED');
                                 break;
                             }
                             case $SESSION_STATE_SUSPENDED:
                             {
                                  $this->errorMsg=$this->languageService->getLL('msg_SESSION_STATE_SUSPENDED');
                                 break;
                             }
                             case $SESSION_STATE_INVALID:
                             {
                                  $this->errorMsg=$this->languageService->getLL('msg_SESSION_STATE_INVALID');
                                 break;
                             }
                             case $SESSION_STATE_EXPIRED:
                             {
                                  $this->errorMsg=$this->languageService->getLL('msg_SESSION_STATE_EXPIRED');
                                 break;
                             }
                         }
                         //delete session data
                         session_start();
                         unset($_SESSION['secsignidauthsessionid']);
                         unset($_SESSION['secsignidauthsessionicondata']);
                         unset($_SESSION['secsignidservicename']);
                         unset($_SESSION['secsigniduserid']);
                         unset($_SESSION['secsignidserviceaddress']);
                         unset($_SESSION['secsignidswitchallowed']);
                         
                         $this->showLogin();
                         return;   
                        }
                    }
                }
            return;
       } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->errorMsg=$this->languageService->getLL('msg_ERROR_LOGIN');
            $this->showLogin();
            return;
        }
   }
    
    
    
   
    
    
   
    
    
    
    
    protected function handleIDDialogExisting() {        
        $username=$_POST['secsign_username'];
        $secsignid=$_POST['existingID'];
        
        //check other user already uses SecSign ID
        if(\SecSign\Secsign\Utils\SecSignUtils::checkDuplicateID($secsignid))
        {
            if($this->DEBUG) {error_log("found user with secsignid ".$secsignid);}
            $this->errorMsg=$this->languageService->getLL('msg_SECSIGNID_DUPLICATE');
        }else{
            //check SecSign ID exists -> else error "not exists"
            $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
            if(!$secSignIDApi->checkSecSignID($secsignid))
            {
                $this->errorMsg=$this->languageService->getLL('msg_SECSIGNID_NOT_EXISTS');
            }
        }
        
        if($this->errorMsg)
        {
            //check what should be done with error case on SecSign ID
            $createOption=\SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('secsignidCreation');
            switch ($createOption)
            {
                case 'free':
                    if($this->DEBUG) {error_log("custom new or existing SecSignID should be created/added -> show Page");}
                    $this->showAddAndFree($username);
                    break;
                default:
                    $existOption=\SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('existingID');
                    switch($existOption)
                    {
                        case 'existing';
                            if($this->DEBUG) {error_log("existing SecSignID should be added -> show Page");}
                            $this->showAddExisiting($username);
                            break;
                        case 'both';
                            if($this->DEBUG) {error_log("custom new or existing SecSignID should be created/added -> show Page");}
                            $this->showAddAndFree($username);
                            break;

                    }
                    break;
            }
        }else{
            //save existing id and start auth
            try{
                if($this->DEBUG) {error_log("existing SecSignID saved ".$secsignid);}
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('secsignid_temp',$secsignid,$username);
                $changeAllowed= \SecSign\Secsign\Accessor\DBAccessor::getChangeAllowedBE($username);
                $this->handleSecSignStart($username,$secsignid);

            } catch (\Exception $e) {
                error_log($e->getMessage());
                $this->errorMsg=$this->languageService->getLL('msg_ERROR_LOGIN');
                $this->showLogin();
                return;
            }
        }
        
        
    }

    
    protected function handleIDDialogCreate()
    {
        $username=$_POST['secsign_username'];
        $secsignid=$_POST['wishID'];
        
        //check other user already uses SecSign ID
        if(\SecSign\Secsign\Utils\SecSignUtils::checkDuplicateID($secsignid))
        {
            if($this->DEBUG) {error_log("found user with secsignid ".$secsignid);}
            $this->errorMsg=$this->languageService->getLL('msg_SECSIGNID_DUPLICATE');
        }else{
            //check SecSign ID already exists
            $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
            if($secSignIDApi->checkSecSignID($secsignid))
            {
                $this->errorMsg=$this->languageService->getLL('msg_SECSIGNID_EXISTS');
            }
        }
        
        try{
            if($this->errorMsg)
            {
                //check what should happen on error case
                $createOption=\SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('secsignidCreation');
                switch ($createOption)
                {
                    case 'free':
                        if($this->DEBUG) {error_log("custom new or existing SecSignID should be created/added -> show Page");}
                        $this->showAddAndFree($username);
                        break;
                    default:
                        $existOption=\SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('existingID');
                        switch($existOption)
                        {
                            case 'new';
                                if($this->DEBUG) {error_log("custom new SecSignID should be created -> show Page");}
                                $this->showFreeCreate($username);
                                break;
                            case 'both';
                                if($this->DEBUG) {error_log("custom new or existing SecSignID should be created/added -> show Page");}
                                $this->showAddAndFree($username);
                                break;

                        }
                        break;
                }
                    
            }else{
                //create SecSign ID
                $this->createSecSignID($username, $secsignid);
            }
           
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->errorMsg=$this->languageService->getLL('msg_ERROR_LOGIN');
            $this->showLogin();
            return;
        }
        
        
    }
    


    protected function handleIDAutomatic($username)
    {
       //get pattern from settings
       $newSecSignId=$this->determineSecSignIDForUsername($username);
       
       if($newSecSignId!="")
       {
            //check if Secsign ID exists
            $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
            try {
                 $exists = $secSignIDApi->checkSecSignID($newSecSignId);
                 if($exists)
                 {
                     $this->handleIDAutomaticExisting($username,$newSecSignId);
                 }else{
                     $this->createSecSignID($username,$newSecSignId);
                  }
             } catch (\Exception $e) {
                 error_log($e->getMessage());
                 $this->errorMsg=$this->languageService->getLL('msg_ERROR_LOGIN');
                 $this->showLogin();
                 return;
             }
       }else{
           //content already rendered
       }
       
    }
    
    
    
    
    
    private function handleIDAutomaticExisting($username,$newSecSignId)
    {
        if($this->DEBUG) {error_log("SecSign ID ".$newSecSignId. ' already exists.');}
        
        //check config what should happen on existing autoID
        $existOption=\SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('existingID');
        
        switch($existOption)
        {
            case 'index';
                //search for free SecSign ID by adding index
                $i=0;
                do{
                    $i=$i+1;
                    $testSecSignID=$newSecSignId.$i;
                    $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
                    $exists = $secSignIDApi->checkSecSignID($testSecSignID);
                } while($exists);

                $newSecSignId=$testSecSignID;
                $this->createSecSignID($username,$newSecSignId);
                break;
            case 'new';
                //allow free new SecSign ID 
                if($this->DEBUG) {error_log("custom new SecSignID should be created -> show Page");}
                $this->showFreeCreate($username);
                break;
            case 'existing';
                //allow free existing SecSign ID
                if($this->DEBUG) {error_log("existing SecSignID should be added -> show Page");}
                $this->showAddExisiting($username);
                break;
            case 'both';
                //allow free existing or new SecSign ID
                if($this->DEBUG) {error_log("custom new or existing SecSignID should be created/added -> show Page");}
                $this->showAddAndFree($username);
                break;
            case 'error';
                //just show error, to contact admin
                if($this->DEBUG) {error_log("Error should be shown");}
                $this->errorMsg=$this->languageService->getLL('msg_ALREADY_EXISTS_ADMIN');
                $this->showLogin();
                break;
        }
    }
    
    
    
   
    
    private function checkUserIsLoggedIn()
    {
        if(class_exists(\TYPO3\CMS\Core\Context\Context::class))
        {
            $context = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
            $this->userIsLoggedIn=$context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
        }else{
            $this->userIsLoggedIn = $this->frontendController->loginUser;
        }

        if ($this->userIsLoggedIn) {
            //show logout
           $this->showLogout();
        }else{
            //show login
            $this->showLogin();
        }
    }
    
    private function checkExistingAuthSession()
    {
        //get session and show authsession if one exists
        session_start();
        $authsessionFromSession=$_SESSION['secsignidauthsessionid'];
        if($authsessionFromSession)
        {
            $secsignidauthsessionicondata=$_SESSION['secsignidauthsessionicondata'];
            $secsignidauthsessionid=$_SESSION['secsignidauthsessionid'];
            $secsignidservicename=$_SESSION['secsignidservicename'];
            $secsigniduserid=$_SESSION['secsigniduserid'];
            $secsignidserviceaddress=$_SESSION['secsignidserviceaddress'];
            $secsignidswitchallowed=$_SESSION['secsignidswitchallowed'];
            $returnURL="";
            $username= \SecSign\Secsign\Accessor\DBAccessor::getValueForSecSignIDBE('username', $secsigniduserid);
            $this->showAccessPass($username,$secsignidauthsessionicondata,$secsignidauthsessionid,$secsignidservicename,$secsigniduserid,$secsignidserviceaddress,$secsignidswitchallowed,$returnURL);

            return true;
        }
        return false;
    }
    
    /**
     * checks whether user has a saved SecSign ID or not
     * @return boolean true, if user has a SecSign ID, else false
     */
    protected function checkUserHasSecSignID($username)
    {
        //get SecSign ID for username
        $secsignidForUser=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser("secsignid",$username);
                
        
        if($secsignidForUser && $secsignidForUser!='')
        {
            return true;
        }else{
            return false;
        }
    }

      /**
     * checks whether User needs 2FA or not, by checking group settings
     * @return boolean true, if user needs 2FA, else false
     */
    protected function checkUserNeeds2FA($username) {
        //get groupids for user with username
        $usergroup=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('usergroup', $username);
        //as array
        $groupArray=explode(",",$usergroup);
        
        //check if one group has 2fa activated      
        foreach ($groupArray as $groupid) {
            $needsTwoFA=\SecSign\Secsign\Accessor\DBAccessor::getNeedsTwoFAForBEGroupID($groupid);
            if($needsTwoFA)
            {
                return true;
            }
        }
        
        
        return false;
    }
    
    private function checkSecSignIDHasDevices($secsignidForUser)
    {
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
        //get devices of secsignid, 0 -> not on device yet
        if($secSignIDApi->getDevicesOfSecSignID($secsignidForUser)==0)
        {
            return false;
        }else{
            return true;
        }
    }
    
    private function determineSecSignIDForUsername($username,$nullPossible=true)
    {
        //check configuration what should be automatic id
       $createOption=\SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('secsignidCreation');
       if($this->DEBUG) {error_log("createOption is ".$createOption);}
       $newSecSignId="";
       switch ($createOption)
       {
           case 'username':
                $newSecSignId=$username;
                if($this->DEBUG) {error_log("new SecSignID should be ".$newSecSignId);}
                return $newSecSignId;
                break;
            case 'email':
                $email=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('email', $username);
                if($email)
                {
                    $newSecSignId=$email;
                }else{
                    $newSecSignId=$username;
                }
                
                if($this->DEBUG) {error_log("new SecSignID should be ".$newSecSignId);}
                return $newSecSignId;
                break;
           case 'pattern':
                $pattern= \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('secsignidPattern');
                $email=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('email', $username);
                if($this->DEBUG) {error_log("pattern is ".$pattern);}

                 //use pattern
                $newSecSignId=str_replace("%username%",$username,$pattern);
                $newSecSignId=str_replace("%email%",$email,$pattern);
                if($this->DEBUG) {error_log("new SecSignID should be ".$newSecSignId);}
                return $newSecSignId;
                break;
           case 'free':
               if($nullPossible)
               {
                    if($this->DEBUG) {error_log("free SecSignID -> show Page");}
                    $this->showAddAndFree($username);
                    return "";
               }else{
                    $newSecSignId=$username;
                    if($this->DEBUG) {error_log("new SecSignID should be ".$newSecSignId);}
                    return $newSecSignId;
                    break;
               }
               break;
       }
       
    }

    
    
    private function createSecSignIDWithoutView($username,$newSecSignId)
    {
        //create SecSignID and save to temp in db
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
        $secSignIDApi->createSecSignIDWithoutMail($newSecSignId);
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('secsignid_temp', $newSecSignId, $username);

    }
    
    //create SecSign ID for user
    private function createSecSignID($username,$newSecSignId)
    {
        
        //create SecSign ID and show QR-Code
        if($this->DEBUG) {error_log("create SecSignID ".$newSecSignId);}
        
        $useRestore= \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('useMailCode');
        $email= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('email', $username);
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
        if($useRestore && $email && $email!='')
        {
            if(!\SecSign\Secsign\Accessor\SettingsAccessor::usesOnPremiseServer())
            {
                $result=$secSignIDApi->getRestoreSecSignIDQRCode($newSecSignId);
                $subJSON=$result[$newSecSignId];
                $restorationJSON=$subJSON['restoration'];
                $restoreurl=$restorationJSON['restoreurl'];
                $qrcodebase64=$restorationJSON['qrcodebase64'];
                
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('secsignid', $newSecSignId, $username);

                $this->showQRCodeRestore($restoreurl, $qrcodebase64, $newSecSignId,$username,$email);
            }else{
                $result=$secSignIDApi->createRestoreSecSignID($newSecSignId,$email);
                $restoreurl=$result['restoreurl'];
                $qrcodebase64=$result['qrcodebase64'];

                $this->showQRCodeRestore($restoreurl, $qrcodebase64, $newSecSignId,$username,$email);
            }
        }else{
            $result=$secSignIDApi->getCreateSecsignIDQRCode($newSecSignId);
            $createurl=$result['createurl'];
            $qrcodebase64=$result['qrcodebase64'];
            
            $this->showQRCode($createurl, $qrcodebase64, $newSecSignId,$username);
        }
    }
    
    /**
     *  creates hash to check on login and saves it in db
     */
    private function createHash($username,$secsignid,$seed=null)
    {
        //create random number for user
        if($seed)
        {
            $hash=md5($seed);
        }else{
            $rand= rand();
            $hash=md5($rand);
        }

        //delete old values if exists
        \SecSign\Secsign\Accessor\DBAccessor::deleteHashForSecSignIDBE($secsignid);

        //save random number as token for user
        \SecSign\Secsign\Accessor\DBAccessor::insertHashForSecSignIDBE($hash,$secsignid);
        
        return $hash;
    }
    
    
    
    /***
     * checks whether username from login fits the username of the secsignid.
     * To prevent form manipulation.
     */
    private function checkUsernamesAreEqual($username,$secsignid)
    {
        //get username for authed secsignid
        $usernameForSecSignID=\SecSign\Secsign\Accessor\DBAccessor::getValueForSecSignIDBE("username",$secsignid);

        if(!$usernameForSecSignID)
        {
            //check temp IDs
            $tempusernameForSecSignID=\SecSign\Secsign\Accessor\DBAccessor::getValueForTempSecSignIDBE("username",$secsignid);
            if($tempusernameForSecSignID)
            {
                //save as secsignid
                //delete temp secsignid
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('secsignid_temp','',$tempusernameForSecSignID);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForBEUser('secsignid',$secsignid,$tempusernameForSecSignID);
                $usernameForSecSignID=$tempusernameForSecSignID;

            }else{
                //no user for the authed SecSign ID found -> error
                $this->errorMsg=$this->languageService->getLL('msg_NO_USER');
                $this->showLogin();
                return false;
            }
        }
        if($this->DEBUG) {error_log('usernameForSecSignID is '.$usernameForSecSignID.' and username is'.$username);}
        if($usernameForSecSignID==$username)
        {
            if($this->DEBUG) {error_log('usernames are equal');}
            return true;
        }else{
            //username of secsignid does not fit username of auth -> error
            $this->errorMsg=$this->languageService->getLL('msg_NO_USER');
            $this->showLogin();
            return false;
        }
        return false;
    }
    
   
    
    /***
     * check whether AccessToken is needed for this secsignid
     * true, if accessToken is needed, else false
     */
    private function checkAccessTokenNeeded($secsignid)
    {
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
        if($_SERVER['HTTPS'])
        {
            $serviceaddress='https://'.$_SERVER['HTTP_HOST'];
        }else{
            $serviceaddress='http://'.$_SERVER['HTTP_HOST'];
        }
        $result=$secSignIDApi->getAccessTokenInfo($secsignid, $serviceaddress);
        if($this->DEBUG) {error_log('accessAllowedWithoutToken is ' .$result['accessAllowedWithoutToken']);}

        //accesToken needed
        if(!($result['accessAllowedWithoutToken']==true))
        {
            return true;
        }else {
            return false;
        }
    }
    
   
   
    
        /**
     * renders login Form
     *
     * @return string html to render
     */
    protected function showLogin()
    {
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BELogin.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        if($this->errorMsg)
        { 
            $this->view->assign('ERROR_MSG', $this->errorMsg);
        }else{
            $this->view->assign('ERROR_MSG', '');
        }
    }
    
    
    
     protected function showFreeCreate($username)
    {
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEBothCreate.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        $this->view->assign('RETURN_URL', $this->returnURL);
        $this->view->assign('secsign_username', $username);
        if($this->errorMsg)
        { 
            $this->view->assign('ERROR_MSG', $this->errorMsg);
        }else{
            $this->view->assign('ERROR_MSG', '');
        }
        
        $this->view->assign('create-visible', 'none');
        $this->view->assign('show-create', 'block');
        $this->view->assign('show-add', 'none');
        
        $this->view->assign('add-visible', 'none');
        $this->view->assign('create-visible', 'block');
       
    }
    
    protected function showAddExisiting($username)
    {
        
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEBothCreate.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        $this->view->assign('RETURN_URL', $this->returnURL);
        $this->view->assign('secsign_username', $username);
        if($this->errorMsg)
        { 
            $this->view->assign('ERROR_MSG', $this->errorMsg);
        }else{
            $this->view->assign('ERROR_MSG', '');
        }
        
        $this->view->assign('create-visible', 'none');
        $this->view->assign('show-create', 'none');
        $this->view->assign('show-add', 'block');
        
        $this->view->assign('add-visible', 'block');
        $this->view->assign('create-visible', 'none');
        
       
    }
    
    
    protected function showAddAndFree($username)
    {
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEBothCreate.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        $this->view->assign('RETURN_URL', $this->returnURL);
        $this->view->assign('secsign_username', $username);
        if($this->errorMsg)
        { 
            $this->view->assign('ERROR_MSG', $this->errorMsg);
        }else{
            $this->view->assign('ERROR_MSG', '');
        }
        
        $this->view->assign('create-visible', 'none');
        $this->view->assign('show-create', 'block');
        $this->view->assign('show-add', 'none');
        
        $this->view->assign('add-visible', 'block');
        $this->view->assign('create-visible', 'block');
       
        
    }
    
    /**
     * renders QR Code to create SecSign ID
     * @param type $createurl       url to create SecSign ID in app by scheme
     * @param type $qrcodebase64    qrcode as base64 img to show on page
     * @param type $newSecSignId    the secsignid that should be created
     * @param type $username        the username of the user to create SecSign ID for
     * @return string html to render          
     */
    protected function showQRCode($createurl,$qrcodebase64,$newSecSignId,$username)
    {
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEShowCreateQRCode.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        $this->view->assign('RETURN_URL', $this->returnURL);
        
        $this->view->assign('createurl', $createurl);
        $this->view->assign('qrcodebase64', $qrcodebase64);
        $this->view->assign('secsignid', $newSecSignId);
        $this->view->assign('secsign_username', $username);
        
        
       
    }
    
    /**
     * renders QR Code to create SecSign ID
     * @param type $createurl       url to create SecSign ID in app by scheme
     * @param type $qrcodebase64    qrcode as base64 img to show on page
     * @param type $newSecSignId    the secsignid that should be created
     * @param type $username        the username of the user to create SecSign ID for
     * @return string html to render          
     */
    protected function showQRCodeRestore($restoreurl,$qrcodebase64,$newSecSignId,$username,$email)
    {
        
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEShowRestoreQRCode.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        $this->view->assign('RETURN_URL', $this->returnURL);
        
        $this->view->assign('restoreurl', $restoreurl);
        $this->view->assign('email', $email);
        $this->view->assign('qrcodebase64', $qrcodebase64);
        $this->view->assign('secsignid', $newSecSignId);
        $this->view->assign('secsign_username', $username);
       
    }
            
    /**
     * renders Login In Progress Page
     *
     * @return string html to render
     */
    protected function showLoginInProgress($hash,$secsignid,$usernameForSecSignID)
    {
        
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BELoginSuccessAccessPass.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        $this->view->assign('RETURN_URL', htmlspecialchars($this->returnURL));
        $this->view->assign('ACCESS_PASS_DATA', htmlspecialchars($_POST['secsignidauthsessionicondata']));
        $this->view->assign('secsignidauthsessionid',htmlspecialchars($_POST['secsignidauthsessionid']));
        $this->view->assign('secsignidauthsessionicondata',  htmlspecialchars($_POST['secsignidauthsessionicondata']));
        $this->view->assign('secsignidservicename', htmlspecialchars($_POST['secsignidservicename']));
        $this->view->assign('secsigniduserid',htmlspecialchars($_POST['secsigniduserid']));
        $this->view->assign('secsignidserviceaddress', htmlspecialchars($_POST['secsignidserviceaddress']));
        $this->view->assign('hash', $hash);
        $this->view->assign('secsignid', $secsignid);
        $this->view->assign('usernameForSecSignID', $usernameForSecSignID);
        
        
        
    }
    
    /**
     * renders Login In Progress Page
     *
     * @return string html to render
     */
    protected function showLoginInProgressPWD($hash,$secsignid,$usernameForSecSignID)
    {
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BELoginSuccess.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        $this->view->assign('hash', $hash);
        $this->view->assign('secsignid', $secsignid);
        $this->view->assign('usernameForSecSignID', $usernameForSecSignID);
        
        
        
    }
    
   
    
    /**
     * renders AccessPass if already was shown before
     *
     * @return string html to render
     */
    protected function showAccessPassFromSession()
    {
        
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEAccessPass.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        $this->view->assign('RETURN_URL', htmlspecialchars($this->returnURL));
        $this->view->assign('ACCESS_PASS_DATA', htmlspecialchars($_SESSION['secsignidauthsessionicondata']));
        $this->view->assign('secsignidauthsessionid',htmlspecialchars($_SESSION['secsignidauthsessionid']));
        $this->view->assign('secsignidauthsessionicondata',  htmlspecialchars($_SESSION['secsignidauthsessionicondata']));
        $this->view->assign('secsignidservicename', htmlspecialchars($_SESSION['secsignidservicename']));
        $this->view->assign('secsigniduserid',htmlspecialchars($_SESSION['secsigniduserid']));
        $this->view->assign('secsignidserviceaddress', htmlspecialchars($_SESSION['secsignidserviceaddress']));
        
    }
    
    
    
    
    private function showSelectionToActive($possibleSelections,$username,$newAllowed,$toConfirm=null)
    {
        $secsignid=in_array('secsignid', $possibleSelections);
        $fido=in_array('fido', $possibleSelections);
        $totp=in_array('totp', $possibleSelections);
        $mailotp=in_array('mailotp', $possibleSelections);
        
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEShowSelectionToActivate.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        
       
        
        $this->view->assign('RETURN_URL' , htmlspecialchars($this->returnURL));
        $this->view->assign('secsign_username' , htmlspecialchars($username));
         if($toConfirm)
        {
             $this->view->assign('toConfirm' , htmlspecialchars($toConfirm));
        }else{
             $this->view->assign('toConfirm','');
        }
        
        
        
        
  
        
        if($secsignid)
        {
            $this->view->assign('secsignid' ,true);
        }
        if($fido)
        {
            $this->view->assign('fido' ,true);
        }
        if($totp)
        {
            $this->view->assign('totp' ,true);
        }
        if($mailotp)
        {
            $this->view->assign('mailotp' ,true);
        }
        if($newAllowed)
        {
            $this->view->assign('new' ,true);
        }
        
        
       
        
        return ;
    }
    
    private function showSelectionToInactive($possibleSelections,$username,$isFirst=false)
    {
        $secsignid=in_array('secsignid', $possibleSelections);
        $fido=in_array('fido', $possibleSelections);
        $totp=in_array('totp', $possibleSelections);
        $mailotp=in_array('mailotp', $possibleSelections);
        
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEShowSelectionToInactive.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        
       
        
        $this->view->assign('RETURN_URL' , htmlspecialchars($this->returnURL));
        $this->view->assign('secsign_username' , htmlspecialchars($username));
       
        
        
        if($isFirst)
        {
            $this->view->assign('isFirst' ,true);
        }else{
            $this->view->assign('isFirst' ,false);
        }
        
        if($secsignid)
        {
            $this->view->assign('secsignid' ,true);
        }
        if($fido)
        {
            $this->view->assign('fido' ,true);
        }
        if($totp)
        {
            $this->view->assign('totp' ,true);
        }
        if($mailotp)
        {
            $this->view->assign('mailotp' ,true);
        }
        
        
       
        
        return ;
    }
    
    private function showTOTPAuth($username,$secsignid,$switchAllowed)
    {
        
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEShowTOTPLogin.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        
        
        $this->view->assign('RETURN_URL',$this->returnURL);
        if( $this->errorMsg)
        {
            $this->view->assign('ERROR_MSG','<div class="secsignid-error">'.$this->errorMsg.'</div>');
        }else{
            $this->view->assign('ERROR_MSG','');
        }
        $this->view->assign('secsigniduserid',$secsignid);
        $this->view->assign('secsign_username',$username);
        
       
        
        
        
        $this->view->assign('switchAllowed',$switchAllowed);
       
        
    }
    
    private function showMailOTPAuth($username,$secsignid,$switchAllowed)
    {
        
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEShowMailOTPLogin.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        
        $this->view->assign('RETURN_URL',htmlspecialchars($this->returnURL));
        if( $this->errorMsg)
        {
            $this->view->assign('ERROR_MSG','<div class="secsignid-error">'.$this->errorMsg.'</div>');
        }else{
            $this->view->assign('ERROR_MSG',htmlspecialchars(''));
        }
        $this->view->assign('secsignid',htmlspecialchars($secsignid));
        $this->view->assign('secsign_username',htmlspecialchars($username));
        
        $this->view->assign('switchAllowed',$switchAllowed);
       
       
        
    }
    
    
    
    
    private function showTOTPQRCode($username,$secsignid,$totpqrcodebase64,$totpkeyuri)
    {
        
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEShowTOTPQRCode.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        
        $this->view->assign('RETURN_URL',htmlspecialchars($this->returnURL));
        if( $this->errorMsg)
        {
            $this->view->assign('ERROR_MSG','<div class="secsignid-error">'.$this->errorMsg.'</div>');
        }else{
            $this->view->assign('ERROR_MSG',htmlspecialchars(''));
        }
        
        $this->view->assign('totpQRSecret',htmlspecialchars($totpkeyuri));
        $this->view->assign('totpQRCode',htmlspecialchars($totpqrcodebase64));
        $this->view->assign('secsignid',htmlspecialchars($secsignid));
        $this->view->assign('secsign_username',htmlspecialchars($username));
        
        
        
    }

    /**
     * renders AccessPass if already was shown before
     *
     * @return string html to render
     */
    protected function showAccessPass($username,$secsignidauthsessionicondata,$secsignidauthsessionid,$secsignidservicename,$secsigniduserid,$secsignidserviceaddress,$secsignidswitchallowed,$returnURL)
    {
        
        if($secsignidauthsessionicondata)
        {
            
            $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEAccessPass.php';
            parent::render($this->view, $this->pageRenderer, $this->loginController);
            $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        
            $this->view->assign('ACCESS_PASS_DATA',htmlspecialchars($secsignidauthsessionicondata));
            $this->view->assign('secsignidauthsessionid',htmlspecialchars($secsignidauthsessionid));
            $this->view->assign('secsignidauthsessionicondata',htmlspecialchars($secsignidauthsessionicondata));
            $this->view->assign('secsignidservicename',htmlspecialchars($secsignidservicename));
            $this->view->assign('secsigniduserid',htmlspecialchars($secsigniduserid));
            $this->view->assign('secsignidserviceaddress',htmlspecialchars($secsignidserviceaddress));
            $this->view->assign('secsign_username',htmlspecialchars($username));
            

            $this->view->assign('RETURN_URL',htmlspecialchars($returnURL));

        }else{
            $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEAccessPassJustConfirm.php';
            parent::render($this->view, $this->pageRenderer, $this->loginController);
            $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        
            $this->view->assign('secsignidauthsessionid',htmlspecialchars($secsignidauthsessionid));
            
            $this->view->assign('secsigniduserid',htmlspecialchars($secsigniduserid));
            $this->view->assign('secsignidserviceaddress',htmlspecialchars($secsignidserviceaddress));
            $this->view->assign('secsign_username',htmlspecialchars($username));

            $this->view->assign('RETURN_URL',htmlspecialchars($returnURL));

         
            
        }

        $this->view->assign('switchAllowed',$secsignidswitchallowed);
    }
    
    
    
  
    
    private function showFIDORegisterNameInput($username,$secsignid,$accessToken=null)
    {
        
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEFIDORegisterStart.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
            
         $this->view->assign('RETURN_URL',htmlspecialchars($this->returnURL));
        if( $this->errorMsg)
        {
            $this->view->assign('ERROR_MSG','<div class="secsignid-error">'.$this->errorMsg.'</div>');
        }else{
            $this->view->assign('ERROR_MSG',htmlspecialchars(''));
        }
        $this->view->assign('secsignid',htmlspecialchars($secsignid));
        $this->view->assign('secsign_username',htmlspecialchars($username));
        if($accessToken)
        {
            $this->view->assign('accesstoken',htmlspecialchars($accessToken));
        }else{
            $this->view->assign('accesstoken','');
        }
        
        
       
       
        
       
    }
    
    private function showFIDOAuth($username,$secsignid,$fromServer,$otherOptionsAllowed)
    {
        $template = 'EXT:secsign/Resources/Private/Backend/Templates/BEFIDOAuthenticate.php';
        parent::render($this->view, $this->pageRenderer, $this->loginController);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($template));
        
        $this->view->assign('fromServer',json_encode($fromServer));
   
        $this->view->assign('RETURN_URL',htmlspecialchars($this->returnURL));
        if( $this->errorMsg)
        {
            $this->view->assign('ERROR_MSG','<div class="secsignid-error">'.$this->errorMsg.'</div>');
        }else{
            $this->view->assign('ERROR_MSG',htmlspecialchars(''));
        }
        $this->view->assign('secsignid',htmlspecialchars($secsignid));
        $this->view->assign('secsign_username',htmlspecialchars($username));
        
         $this->view->assign('switchAllowed',$otherOptionsAllowed);
        
        
    }
     
    
}

    

