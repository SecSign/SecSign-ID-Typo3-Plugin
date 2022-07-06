<?php
namespace SecSign\Secsign\Controller;

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


use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility;

/**
 * Plugin 'Website User Login' for the 'felogin' extension.
 */
class LoginController extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    private $DEBUG=true;
    
    /**
     * Same as class name
     *
     * @var string
     */
    public $prefixId = 'tx_secsignfe_pi1';
    
    /**
     * The extension key.
     *
     * @var string
     */
    public $extKey = 'secsign';

    /**
     * Is user logged in?
     *
     * @var bool
     */
    protected $userIsLoggedIn;

    /**
     * Holds the template for FE rendering
     *
     * @var string
     */
    protected $template;

    /**
     * A list of page UIDs, either an integer or a comma-separated list of integers
     *
     * @var string
     */
    public $spid;
    
    private $content;

    
    public $errorMsg;
    

    
    
    public $returnURL;

    
    /**
     * constructor that gets configuration and sets important values
     */
    function __construct()
    {
        parent::__construct();
              
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
    public function main($content, $conf)
    {
        //get language-file
        $this->pi_loadLL('EXT:secsign/Resources/Private/Language/locallang.xlf');
        $this->content = '';
        
        $this->conf = $conf;
        $this->returnURL=GeneralUtility::_GP('returnURL');
        
        
        if(GeneralUtility::_GP('logintype'))
        {
            if($this->DEBUG) {error_log('auth in progress');}
        }
        
        
        if($this->DEBUG) {error_log('secsign_method is '.GeneralUtility::_GP('secsign_method'));}
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
    
                        
        //render
        return $this->pi_wrapInBaseClass($this->content) ;
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
            $this->errorMsg=$this->pi_getLL('secsign_msg_wrong_password');
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
        $passwordFromDB=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser("password",$username);
        
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
                $mode = 'FE';
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
                $defaultHashingClassName=SaltedPasswordsUtility::getDefaultSaltingHashingMethod('FE');
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
        
        $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('secsignid', $username);
        
        //create random hash for user
        $random=rand();
        $hash=md5($random);
          
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('lastMethod', '', $username);
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('activeMethods', '', $username);
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('chooseToActivate', '', $username);
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('needsAccessToken', '', $username);
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('tokenID', '', $username);
        if($secsignid)
        {
            //delete old values if exists
            \SecSign\Secsign\Accessor\DBAccessor::deleteHashForSecSignID($secsignid);

            //save random number as token for user
            \SecSign\Secsign\Accessor\DBAccessor::insertHashForSecSignID($hash,$secsignid);


            $this->showLoginInProgressPWD($hash,$secsignid,$username);
        }else{
            //delete old values if exists
            \SecSign\Secsign\Accessor\DBAccessor::deleteHashForSecSignID($username);

            //save random number as token for user
            \SecSign\Secsign\Accessor\DBAccessor::insertHashForSecSignID($hash,$username);


            $this->showLoginInProgressPWD($hash,$username,$username);
        }

        
    }
    
    private function determineSecondStep($username)
    {
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('chooseToActivate', '', $username);
        
        $allowedOptions= \SecSign\Secsign\Accessor\DBAccessor::getAllowed2FAMethods($username);
        if($this->DEBUG) {error_log('allowedOptions is '. print_r($allowedOptions,true));}
        
        $activeMethods=\SecSign\Secsign\Accessor\DBAccessor::getActivated2FAMethods($username);
        if($this->DEBUG) {error_log('activeMethods is '. print_r($activeMethods,true));}
        
        $lastMethod= \SecSign\Secsign\Accessor\DBAccessor::getLastMethod($username);
        if($this->DEBUG) {error_log('lastMethod is '.$lastMethod);}
        
        $lastMethod= \SecSign\Secsign\Accessor\DBAccessor::getLastMethod($username);
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
            $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('secsignid', $username);
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
        $activeMethods=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('activeMethods', $username);
        if(strpos($activeMethods, 'fido')===false)
        {
            return false;
        }else{
            return true;            
        }
    }
    
    private function handleFIDOAuthStart($username,$secsignid)
    {
        $changeAllowed= \SecSign\Secsign\Accessor\DBAccessor::getChangeAllowed($username);
        
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
        $toConfirm=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('chooseToActivate', $username);
        if($this->DEBUG) {error_log('toConfirm is '.$toConfirm);}
        
        $needsAccessToken=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('needsAccessToken', $username);
        if($this->DEBUG) {error_log('needsAccessToken is '.$needsAccessToken);}        
        
        //if confirmation with AccessToken is needed, get AccessToken with authentication
        if($toConfirm && $needsAccessToken && $toConfirm!='fido')
        {
            $tokenID=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('tokenID', $username);
            $answer=$secSignIDApi->requestAccessTokenForFIDOAuthentication($tokenID,$secsignid, $credentialId, $clientDataJson, $authenticatorData, $signature, $userHandle);
            if($answer['errormsg'])
            {
                //error on fido, show error on login page
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('chooseToActivate', '', $username);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('needsAccessToken', '', $username);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('tokenID', '', $username);
                $this->errorMsg=$this->pi_getLL('msg_FIDO_ERROR');
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
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('chooseToActivate', '', $username);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('needsAccessToken', '', $username);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('tokenID', '', $username);
                $this->errorMsg=$this->pi_getLL('msg_FIDO_ERROR');
                $this->showLogin();
                return;
            }
            
        }
        
        //check that username of authentication fits username of secsignid and create hash for login
        if($this->checkUsernamesAreEqual($username,$secsignid))
        {
            //set fido as last and as activated method
            \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('lastMethod', 'fido', $username);
            \SecSign\Secsign\Accessor\DBAccessor::addActiveMethodForFEUser('fido', $username);

            //check if login was intended to confirm activation of other method
            $toConfirm=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('chooseToActivate', $username);
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
                        \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('chooseToActivate', '', $username);
                        \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('needsAccessToken', '', $username);
                        \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('tokenID', '', $username);
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
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('chooseToActivate', '', $username);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('needsAccessToken', '', $username);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('tokenID', '', $username);
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
        
        $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('secsignid', $username);
        if(!$secsignid)
        {
            $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('secsignid_temp', $username);
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
        $this->content='toGet:'.json_encode($creationOptions).";;;;;";
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
             $this->errorMsg=$this->pi_getLL('msg_ERROR_LOGIN');
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
            $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('secsignid', $username);
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
        $activeMethods=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('activeMethods', $username);
        if(strpos($activeMethods, 'totp') === false)
        {
            return false;
        }else{
            return true;
        }
    }
    
    private function handleTOTPStart($username,$secsignid)
    {
        $changeAllowed= \SecSign\Secsign\Accessor\DBAccessor::getChangeAllowed($username);
        
        $this->showTOTPAuth($username,$secsignid,$changeAllowed);
    }
    
    private function handleTOTPShowQRCode($username,$accessToken)
    {
        $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('secsignid', $username);
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
             $this->errorMsg=$this->pi_getLL('msg_ERROR_LOGIN');
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
        
        $toConfirm=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('chooseToActivate', $username);
        if($this->DEBUG) {error_log('toConfirm is '.$toConfirm);}
        $needsAccessToken=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('needsAccessToken', $username);
        if($this->DEBUG) {error_log('needsAccessToken is '.$needsAccessToken);}
        
        //if totp is used to get accessToken
        if($toConfirm && $needsAccessToken && $toConfirm!='totp')
        {
            //get token for activation of new method
            $tokenID=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('tokenID', $username);
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
            $toConfirm=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('chooseToActivate', $username);
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
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('lastMethod', 'totp', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::addActiveMethodForFEUser('totp', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('chooseToActivate', '', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('needsAccessToken', '', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('tokenID', '', $username);
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
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('lastMethod', 'totp', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::addActiveMethodForFEUser('totp', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('chooseToActivate', '', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('needsAccessToken', '', $username);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('tokenID', '', $username);
                    $this->showLoginInProgressPWD($hash,$secsignid,$username);
                    return;

                }
            }
            
            
        }else{
             $this->errorMsg=$this->pi_getLL('secsign_msg_wrong_totp');
             $this->showTOTPAuth($username,$secsignid,$_POST['secsignidswitchallowed']);
        }
        
        
    }
    
    private function handleMailOTPPre()
    {
        $username=$_POST['secsign_username'];
         //check if user has SecSignID
        if($this->checkUserHasSecSignID($username))
        {
            $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('secsignid', $username);
            $this->handleMailOTPStart($username,$secsignid);
        }else{
            //no SecSign ID, so create one
            $this->handleMailOTPCreateID($username);
        }
    }
    
    
    private function handleMailOTPStart($username,$secsignid)
    {
        $email= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('email', $username);
        
        $changeAllowed= \SecSign\Secsign\Accessor\DBAccessor::getChangeAllowed($username);
        
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
             $this->errorMsg=$this->pi_getLL('msg_ERROR_LOGIN');
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
        
        $toConfirm=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('chooseToActivate', $username);
        
        
        if($secSignIDApi->checkMailOTPCode($secsignid,$mailotpCode))
        {

            //get username for authed secsignid
            $usernameForSecSignID=\SecSign\Secsign\Accessor\DBAccessor::getValueForSecSignID("username",$secsignid);


            if(!$usernameForSecSignID)
            {
                 //check temp IDs
                 $tempusernameForSecSignID=\SecSign\Secsign\Accessor\DBAccessor::getValueForTempSecSignID("username",$secsignid);
                     if($tempusernameForSecSignID)
                     {
                         //save as secsignid
                         //delete temp secsignid
                         \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('secsignid_temp','',$tempusernameForSecSignID);
                         \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('secsignid',$secsignid,$tempusernameForSecSignID);
                         $usernameForSecSignID=$tempusernameForSecSignID;

                     }else{
                         //no user for the authed SecSign ID found -> ignore
                         $this->errorMsg=$this->pi_getLL('msg_NO_USER');
                         $this->showLogin();
                         return;
                     }
            }


            \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('lastMethod', 'mailotp', $usernameForSecSignID);
            \SecSign\Secsign\Accessor\DBAccessor::addActiveMethodForFEUser('mailotp', $usernameForSecSignID);


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
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('chooseToActivate', '', $usernameForSecSignID);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('needsAccessToken', '', $usernameForSecSignID);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('tokenID', '', $usernameForSecSignID);
                            $this->showLoginInProgressPWD($hash,$secsignid,$usernameForSecSignID);
                            return;
                            
                        }
                        break;
                }
            }else{
                if($this->checkUsernamesAreEqual($usernameForSecSignID, $secsignid))
                {
                    $hash=$this->createHash($usernameForSecSignID, $secsignid);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('chooseToActivate', '', $usernameForSecSignID);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('needsAccessToken', '', $usernameForSecSignID);
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('tokenID', '', $usernameForSecSignID);
                    $this->showLoginInProgressPWD($hash,$secsignid,$usernameForSecSignID);
                    return;

                }
            }
        }else{
             $this->errorMsg=$this->pi_getLL('secsign_msg_wrong_password');
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
             $username= \SecSign\Secsign\Accessor\DBAccessor::getValueForSecSignID("username", $secsignid);
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
                 $this->errorMsg=$this->pi_getLL('msg_ERROR_LOGIN');
                 $this->showLogin();
                 return;
             }
        }
        
        //check already Activated one
        $activatedMethods= \SecSign\Secsign\Accessor\DBAccessor::getActivated2FAMethods($username);
        if($this->DEBUG) {error_log('activatedMethods is '.print_r($activatedMethods,true));}
        
        $allowedMethods=\SecSign\Secsign\Accessor\DBAccessor::getAllowed2FAMethods($username);
        if($this->DEBUG) {error_log('allowedMethods is '.print_r($allowedMethods,true));}
        
        $notActivatedMethods=\SecSign\Secsign\Accessor\DBAccessor::getNotActivatedAndAllowedMethods($username);
        if($this->DEBUG) {error_log('notActivatedMethods is '.print_r($notActivatedMethods,true));}
        
        $newAllowed= count($notActivatedMethods)>0;
        if($this->DEBUG) {error_log('newAllowed is '.$newAllowed);}   
        
        $allowedAndActivated=\SecSign\Secsign\Accessor\DBAccessor::getAllowedAndActivated2FAMethods($username);
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
        
        $allowedOptions= \SecSign\Secsign\Accessor\DBAccessor::getAllowed2FAMethods($username);
        if($this->DEBUG) {error_log('allowedOptions is '. print_r($allowedOptions,true));}
        
        $activeMethods=\SecSign\Secsign\Accessor\DBAccessor::getActivated2FAMethods($username);
        if($this->DEBUG) {error_log('activeMethods is '. print_r($activeMethods,true));}
        
        $lastMethod=\SecSign\Secsign\Accessor\DBAccessor::getLastMethod($username);
        if($this->DEBUG) {error_log('lastMethod is '.$lastMethod);}

        //if inactive methode selected before
        if($_POST['toConfirm']!='')
        {
            \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('chooseToActivate', $_POST['toConfirm'], $username);
            if($this->DEBUG) {error_log('toConfirm is '.$_POST['toConfirm']);}
            
            $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('secsignid', $username);
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
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('needsAccessToken', true, $username);
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
                       $this->errorMsg=$this->pi_getLL('msg_MAILOTP_FOR_TOKEN');
                       $this->showLogin();
                       return;

                } 
                if($this->DEBUG) {error_log('tokenID is: '.$tokenID);}
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('tokenID', $tokenID, $username); 

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
        
        $allowedOptions= \SecSign\Secsign\Accessor\DBAccessor::getAllowed2FAMethods($username);
        if($this->DEBUG) {error_log('allowedOptions is '. print_r($allowedOptions,true));}
        
        $activeMethods= \SecSign\Secsign\Accessor\DBAccessor::getActivated2FAMethods($username);
        if($this->DEBUG) {error_log('activeMethods is '. print_r($activeMethods,true));}
        
        $lastMethod= \SecSign\Secsign\Accessor\DBAccessor::getLastMethod($username);
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
            $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('secsignid', $username);
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
            $secsignid= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('secsignid', $username);
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
        $changeAllowed= \SecSign\Secsign\Accessor\DBAccessor::getChangeAllowed($username);
        //create Auth Session and show AccessPass
        $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
       
        $serviceaddress=$_SERVER['HTTP_HOST'];
        $servicename = \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('serviceName');
        if(!$servicename)
        {
            $servicename = "Typo3 on ".$serviceaddress;
        }
        
        $username= \SecSign\Secsign\Accessor\DBAccessor::getValueForSecSignID('username', $secsignid);
        if(!$username)
        {
            $username= \SecSign\Secsign\Accessor\DBAccessor::getValueForTempSecSignID('username', $secsignid);
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
            $this->errorMsg=$this->pi_getLL('msg_ERROR_LOGIN');
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
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser("secsignid_temp",$secsignid,$username);
                $changeAllowed= \SecSign\Secsign\Accessor\DBAccessor::getChangeAllowed($username);
                $this->handleSecSignStart($username,$secsignid);
            }else{
                //not created, keep testing
                $this->showQRCode($_POST['createurl'],$_POST['qrcodebase64'] ,$secsignid,$username);
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->errorMsg=$this->pi_getLL('msg_ERROR_LOGIN');
            $this->showLogin();
            return;
        }
    }
    
    private function handleSecSignExistingRestoreQRCode($username,$secsignid,$accessToken=null)
    {
        $email=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser("email",$username);
        if($this->DEBUG) {error_log("found email ".$email. " for user ".$username);}
        
        
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser("tokenID", '', $username);
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
                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser("secsignid_temp",$secsignid,$username);
                }
                $changeAllowed= \SecSign\Secsign\Accessor\DBAccessor::getChangeAllowed($username);
                $this->handleSecSignStart($username,$secsignid);
            }else{
                //not created, keep testing
                $this->showQRCodeRestore($_POST['restoreurl'],$_POST['qrcodebase64'] ,$secsignid,$username,$email);
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->errorMsg=$this->pi_getLL('msg_ERROR_LOGIN');
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
            $this->errorMsg=$this->pi_getLL('msg_ERROR_LOGIN');
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
                
                $toConfirm=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('chooseToActivate', $username);
                if($this->DEBUG) {error_log('toConfirm is '.$toConfirm);}
                $needsAccessToken=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('needsAccessToken', $username);
                if($this->DEBUG) {error_log('needsAccessToken is '.$needsAccessToken);}


                if($toConfirm && $needsAccessToken && $toConfirm!='secsignid')
                {
                    $tokenID=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('tokenID', $username);
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
                                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('lastMethod', 'secsignid', $username);
                                    \SecSign\Secsign\Accessor\DBAccessor::addActiveMethodForFEUser('secsignid', $username);
                                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('chooseToActivate', '', $username);
                                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('needsAccessToken', '', $username);
                                    \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('tokenID', '', $username);
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
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('lastMethod', 'secsignid', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::addActiveMethodForFEUser('secsignid', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('chooseToActivate', '', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('needsAccessToken', '', $username);
                            \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('tokenID', '', $username);
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
                                  $this->errorMsg=$this->pi_getLL('msg_SESSION_STATE_DENIED');
                                 break;
                             }
                             case $SESSION_STATE_SUSPENDED:
                             {
                                  $this->errorMsg=$this->pi_getLL('msg_SESSION_STATE_SUSPENDED');
                                 break;
                             }
                             case $SESSION_STATE_INVALID:
                             {
                                  $this->errorMsg=$this->pi_getLL('msg_SESSION_STATE_INVALID');
                                 break;
                             }
                             case $SESSION_STATE_EXPIRED:
                             {
                                  $this->errorMsg=$this->pi_getLL('msg_SESSION_STATE_EXPIRED');
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
            $this->errorMsg=$this->pi_getLL('msg_ERROR_LOGIN');
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
            $this->errorMsg=$this->pi_getLL('msg_SECSIGNID_DUPLICATE');
        }else{
            //check SecSign ID exists -> else error "not exists"
            $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
            if(!$secSignIDApi->checkSecSignID($secsignid))
            {
                $this->errorMsg=$this->pi_getLL('msg_SECSIGNID_NOT_EXISTS');
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
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('secsignid_temp',$secsignid,$username);
                $changeAllowed= \SecSign\Secsign\Accessor\DBAccessor::getChangeAllowed($username);
                $this->handleSecSignStart($username,$secsignid);

            } catch (\Exception $e) {
                error_log($e->getMessage());
                $this->errorMsg=$this->pi_getLL('msg_ERROR_LOGIN');
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
            $this->errorMsg=$this->pi_getLL('msg_SECSIGNID_DUPLICATE');
        }else{
            //check SecSign ID already exists
            $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
            if($secSignIDApi->checkSecSignID($secsignid))
            {
                $this->errorMsg=$this->pi_getLL('msg_SECSIGNID_EXISTS');
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
            $this->errorMsg=$this->pi_getLL('msg_ERROR_LOGIN');
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
                 $this->errorMsg=$this->pi_getLL('msg_ERROR_LOGIN');
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
                $this->errorMsg=$this->pi_getLL('msg_ALREADY_EXISTS_ADMIN');
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
            $username= \SecSign\Secsign\Accessor\DBAccessor::getValueForSecSignID('username', $secsigniduserid);
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
        $secsignidForUser=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser("secsignid",$username);
                
        
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
        $usergroup=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('usergroup', $username);
        //as array
        $groupArray=explode(",",$usergroup);
        
        //check if one group has 2fa activated      
        foreach ($groupArray as $groupid) {
            $needsTwoFA=\SecSign\Secsign\Accessor\DBAccessor::getNeedsTwoFAForGroupID($groupid);
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
                $email=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('email', $username);
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
                $email=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('email', $username);
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
        \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('secsignid_temp', $newSecSignId, $username);

    }
    
    //create SecSign ID for user
    private function createSecSignID($username,$newSecSignId)
    {
        
        //create SecSign ID and show QR-Code
        if($this->DEBUG) {error_log("create SecSignID ".$newSecSignId);}
        
        $useRestore= \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('useMailCode');
        $email= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('email', $username);
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
                
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('secsignid', $newSecSignId, $username);

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
        \SecSign\Secsign\Accessor\DBAccessor::deleteHashForSecSignID($secsignid);

        //save random number as token for user
        \SecSign\Secsign\Accessor\DBAccessor::insertHashForSecSignID($hash,$secsignid);
        
        return $hash;
    }
    
    
    
    /***
     * checks whether username from login fits the username of the secsignid.
     * To prevent form manipulation.
     */
    private function checkUsernamesAreEqual($username,$secsignid)
    {
        //get username for authed secsignid
        $usernameForSecSignID=\SecSign\Secsign\Accessor\DBAccessor::getValueForSecSignID("username",$secsignid);

        if(!$usernameForSecSignID)
        {
            //check temp IDs
            $tempusernameForSecSignID=\SecSign\Secsign\Accessor\DBAccessor::getValueForTempSecSignID("username",$secsignid);
            if($tempusernameForSecSignID)
            {
                //save as secsignid
                //delete temp secsignid
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('secsignid_temp','',$tempusernameForSecSignID);
                \SecSign\Secsign\Accessor\DBAccessor::updateValueForFEUser('secsignid',$secsignid,$tempusernameForSecSignID);
                $usernameForSecSignID=$tempusernameForSecSignID;

            }else{
                //no user for the authed SecSign ID found -> error
                $this->errorMsg=$this->pi_getLL('msg_NO_USER');
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
            $this->errorMsg=$this->pi_getLL('msg_NO_USER');
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
        //just render login
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/Login.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_LOGIN###');     
        $subpartArray = ($linkpartArray = []);
        $loginLogo=\SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting("feLoginLogo");
        if($loginLogo)
        {
            $markerArray['###login_logo###'] = '<center><img style="width:300px;margin-bottom:15px" src="'.$loginLogo.'"/></center>';
        }else{
            $markerArray['###login_logo###'] = '';
        }
        if( $this->errorMsg)
        {
           
            $markerArray['###ERROR_MSG###'] = '<div class="secsignid-error">'.$this->errorMsg.'</div>';
            
        }else{
            $markerArray['###ERROR_MSG###'] = htmlspecialchars('');
            
        }
        
        if($this->DEBUG) { error_log("returnURL is ".$this->returnURL); }
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        //$markerArray['###PID###'] = htmlspecialchars($this->spid);
        
        $headingText= \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('feHeadingText');
        if($headingText)
        {
            $markerArray['###secsign.login.heading###'] = htmlspecialchars($headingText);
        }else{
            $markerArray['###secsign.login.heading###'] = htmlspecialchars($this->pi_getLL('secsign.login.heading'));
        }
        
        $headingColor= \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('feHeadingColor');
        if($headingColor)
        {
            $markerArray['###headingColor###'] = htmlspecialchars($headingColor);
        }else{
            $markerArray['###headingColor###'] = '#333333';
        }
        $labelColor= \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('feLabelColor');
        if($labelColor)
        {
            $markerArray['###labelColor###'] = htmlspecialchars($labelColor);
        }else{
            $markerArray['###labelColor###'] = '#6B778C';
        }
        
        $buttonColor= \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('feButtonColor');
        if($buttonColor)
        {
            $markerArray['###buttonColor###'] = htmlspecialchars($buttonColor);
        }else{
            $markerArray['###buttonColor###'] = '#DDDDDD';
        }
        
        $buttonTextColor= \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('feButtonText');
        if($buttonTextColor)
        {
            $markerArray['###buttonTextColor###'] = htmlspecialchars($buttonTextColor);
        }else{
            $markerArray['###buttonTextColor###'] = '#000000';
        }
        $markerArray['###secsign.login.button.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.button.text'));
        $markerArray['###secsign.login.username.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.username.label'));
        $markerArray['###secsign.login.password.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.password.label'));
        
        
        
        $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
       
    }
    
    
    
     protected function showFreeCreate($username)
    {
        $result="";
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/BothCreate.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        $markerArray['###secsign_username###'] = htmlspecialchars($username);
        if($this->errorMsg)
        {                    
            $markerArray['###ERROR_MSG###'] = '<div class="secsignid-error">'.$this->errorMsg.'</div>';
        }else{
            $markerArray['###ERROR_MSG###'] = htmlspecialchars('');
        }
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        //$markerArray['###PID###'] = htmlspecialchars($this->spid);
        
        $markerArray['###secsign.login.button.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.button.text'));
        $markerArray['###secsign.login.username.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.username.label'));
        $markerArray['###secsign.login.password.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.password.label'));
        $markerArray['###secsign.login.heading###'] = htmlspecialchars($this->pi_getLL('secsign.login.heading'));
        $markerArray['###secsign.create.text.1###'] = htmlspecialchars($this->pi_getLL('secsign.create.text.1'));
        $markerArray['###secsign.create.text.2###'] = htmlspecialchars($this->pi_getLL('secsign.create.text.2'));
        $markerArray['###secsign.create.text.3###'] = htmlspecialchars($this->pi_getLL('secsign.create.text.3'));
        $markerArray['###secsign.create.already.link###'] = htmlspecialchars($this->pi_getLL('secsign.create.already.link'));
        $markerArray['###secsign.create.new.field###'] = htmlspecialchars($this->pi_getLL('secsign.create.new.field'));
        $markerArray['###secsign.create.new.button###'] = htmlspecialchars($this->pi_getLL('secsign.create.new.button'));
        $markerArray['###secsign.add.text.1###'] = htmlspecialchars($this->pi_getLL('secsign.add.text.1'));
        $markerArray['###secsign.add.text.2###'] = htmlspecialchars($this->pi_getLL('secsign.add.text.2'));
        $markerArray['###secsign.add.text.3###'] = htmlspecialchars($this->pi_getLL('secsign.add.text.3'));
        $markerArray['###secsign.add.create.link###'] = htmlspecialchars($this->pi_getLL('secsign.add.create.link'));
        $markerArray['###secsign.add.add.field###'] = htmlspecialchars($this->pi_getLL('secsign.add.add.field'));
        $markerArray['###secsign.add.add.button###'] = htmlspecialchars($this->pi_getLL('secsign.add.add.button'));
        $markerArray['###secsign.button.cancel###'] = htmlspecialchars($this->pi_getLL('secsign.button.cancel'));
        
        $subpart = $this->templateService->getSubpart($this->template, '###BOTH_DIALOG_TEMPLATE_BEGIN###');     
        $subpartArray = ($linkpartArray = []);
        $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        
        
        $subpart = $this->templateService->getSubpart($this->template, '###BOTH_CREATE_TEMPLATE###');     
        $subpartArray = ($linkpartArray = []);
        $markerArray['###add-visible###'] = htmlspecialchars('none');
        $markerArray['###show-create###'] = htmlspecialchars('block');
        $markerArray['###show-add###'] = htmlspecialchars('none');
        $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        
       
        
        $subpart = $this->templateService->getSubpart($this->template, '###BOTH_DIALOG_TEMPLATE_END###');     
        $subpartArray = ($linkpartArray = []);
        $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        
        return ;
       
    }
    
    protected function showAddExisiting($username)
    {
        
        $result="";
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/BothCreate.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        $markerArray['###secsign_username###'] = htmlspecialchars($username);
        if($this->errorMsg)
        {                    
            $markerArray['###ERROR_MSG###'] = '<div class="secsignid-error">'.$this->errorMsg.'</div>';
        }else{
            $markerArray['###ERROR_MSG###'] = htmlspecialchars('');
        }
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        //$markerArray['###PID###'] = htmlspecialchars($this->spid);
        
        $markerArray['###secsign.login.button.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.button.text'));
        $markerArray['###secsign.login.username.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.username.label'));
        $markerArray['###secsign.login.password.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.password.label'));
        $markerArray['###secsign.login.heading###'] = htmlspecialchars($this->pi_getLL('secsign.login.heading'));
        $markerArray['###secsign.create.text.1###'] = htmlspecialchars($this->pi_getLL('secsign.create.text.1'));
        $markerArray['###secsign.create.text.2###'] = htmlspecialchars($this->pi_getLL('secsign.create.text.2'));
        $markerArray['###secsign.create.text.3###'] = htmlspecialchars($this->pi_getLL('secsign.create.text.3'));
        $markerArray['###secsign.create.already.link###'] = htmlspecialchars($this->pi_getLL('secsign.create.already.link'));
        $markerArray['###secsign.create.new.field###'] = htmlspecialchars($this->pi_getLL('secsign.create.new.field'));
        $markerArray['###secsign.create.new.button###'] = htmlspecialchars($this->pi_getLL('secsign.create.new.button'));
        $markerArray['###secsign.add.text.1###'] = htmlspecialchars($this->pi_getLL('secsign.add.text.1'));
        $markerArray['###secsign.add.text.2###'] = htmlspecialchars($this->pi_getLL('secsign.add.text.2'));
        $markerArray['###secsign.add.text.3###'] = htmlspecialchars($this->pi_getLL('secsign.add.text.3'));
        $markerArray['###secsign.add.create.link###'] = htmlspecialchars($this->pi_getLL('secsign.add.create.link'));
        $markerArray['###secsign.add.add.field###'] = htmlspecialchars($this->pi_getLL('secsign.add.add.field'));
        $markerArray['###secsign.add.add.button###'] = htmlspecialchars($this->pi_getLL('secsign.add.add.button'));
        $markerArray['###secsign.button.cancel###'] = htmlspecialchars($this->pi_getLL('secsign.button.cancel'));
        
        $subpart = $this->templateService->getSubpart($this->template, '###BOTH_DIALOG_TEMPLATE_BEGIN###');     
        $subpartArray = ($linkpartArray = []);
        $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        
        
        
        $subpart = $this->templateService->getSubpart($this->template, '###BOTH_ADD_TEMPLATE###');     
        $subpartArray = ($linkpartArray = []);
        $markerArray['###create-visible###'] = htmlspecialchars('none');
        $markerArray['###show-create###'] = htmlspecialchars('none');
        $markerArray['###show-add###'] = htmlspecialchars('block');
        $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
       
        
        $subpart = $this->templateService->getSubpart($this->template, '###BOTH_DIALOG_TEMPLATE_END###');     
        $subpartArray = ($linkpartArray = []);
        $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        
        return ;
       
    }
    
    
    protected function showAddAndFree($username)
    {
        $result="";
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/BothCreate.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        $markerArray['###secsign_username###'] = htmlspecialchars($username);
        if($this->errorMsg)
        {                    
            $markerArray['###ERROR_MSG###'] = '<div class="secsignid-error">'.$this->errorMsg.'</div>';
        }else{
            $markerArray['###ERROR_MSG###'] = htmlspecialchars('');
        }
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        //$markerArray['###PID###'] = htmlspecialchars($this->spid);
        
        $markerArray['###secsign.login.button.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.button.text'));
        $markerArray['###secsign.login.username.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.username.label'));
        $markerArray['###secsign.login.password.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.password.label'));
        $markerArray['###secsign.login.heading###'] = htmlspecialchars($this->pi_getLL('secsign.login.heading'));
        $markerArray['###secsign.create.text.1###'] = htmlspecialchars($this->pi_getLL('secsign.create.text.1'));
        $markerArray['###secsign.create.text.2###'] = htmlspecialchars($this->pi_getLL('secsign.create.text.2'));
        $markerArray['###secsign.create.text.3###'] = htmlspecialchars($this->pi_getLL('secsign.create.text.3'));
        $markerArray['###secsign.create.already.link###'] = htmlspecialchars($this->pi_getLL('secsign.create.already.link'));
        $markerArray['###secsign.create.new.field###'] = htmlspecialchars($this->pi_getLL('secsign.create.new.field'));
        $markerArray['###secsign.create.new.button###'] = htmlspecialchars($this->pi_getLL('secsign.create.new.button'));
        $markerArray['###secsign.add.text.1###'] = htmlspecialchars($this->pi_getLL('secsign.add.text.1'));
        $markerArray['###secsign.add.text.2###'] = htmlspecialchars($this->pi_getLL('secsign.add.text.2'));
        $markerArray['###secsign.add.text.3###'] = htmlspecialchars($this->pi_getLL('secsign.add.text.3'));
        $markerArray['###secsign.add.create.link###'] = htmlspecialchars($this->pi_getLL('secsign.add.create.link'));
        $markerArray['###secsign.add.add.field###'] = htmlspecialchars($this->pi_getLL('secsign.add.add.field'));
        $markerArray['###secsign.add.add.button###'] = htmlspecialchars($this->pi_getLL('secsign.add.add.button'));
        $markerArray['###secsign.button.cancel###'] = htmlspecialchars($this->pi_getLL('secsign.button.cancel'));
        
        
        $markerArray['###create-visible###'] = htmlspecialchars('none');
        $markerArray['###show-create###'] = htmlspecialchars('block');
        $markerArray['###show-add###'] = htmlspecialchars('none');
        
        $subpart = $this->templateService->getSubpart($this->template, '###BOTH_DIALOG_TEMPLATE_BEGIN###');     
        $subpartArray = ($linkpartArray = []);
        $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        
        
        $subpart = $this->templateService->getSubpart($this->template, '###BOTH_CREATE_TEMPLATE###');     
        $subpartArray = ($linkpartArray = []);
        $markerArray['###add-visible###'] = htmlspecialchars('block');
        $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        
        
        $subpart = $this->templateService->getSubpart($this->template, '###BOTH_ADD_TEMPLATE###');     
        $subpartArray = ($linkpartArray = []);
        $markerArray['###create-visible###'] = htmlspecialchars('block');
        $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
       
        
        $subpart = $this->templateService->getSubpart($this->template, '###BOTH_DIALOG_TEMPLATE_END###');     
        $subpartArray = ($linkpartArray = []);
        $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        
        return ;
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
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/ShowCreateQRCode.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        $subpart = $this->templateService->getSubpart($this->template, '###CREATE_QR_TEMPLATE###');     
        $subpartArray = ($linkpartArray = []);
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        
        $markerArray['###createurl###'] = htmlspecialchars($createurl);
        $markerArray['###qrcodebase64###'] = htmlspecialchars($qrcodebase64);
        $markerArray['###secsignid###'] = htmlspecialchars($newSecSignId);
        $markerArray['###secsign_username###'] = htmlspecialchars($username);
        
        $markerArray['###secsign.activate.twofa.heading###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.heading'));
        $markerArray['###secsign.activate.twofa.start###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.start'));
        $markerArray['###secsign.activate.twofa.1###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.1'));
        $markerArray['###secsign.activate.twofa.2###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.2'));
        $markerArray['###secsign.activate.twofa.qr###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.qr'));
        $markerArray['###secsign.activate.twofa.qr.desktop###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.qr.desktop'));
        $markerArray['###secsign.activate.twofa.qr.continue###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.qr.continue'));
        
        $markerArray['###secsign.login.button.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.button.text'));
        $markerArray['###secsign.login.username.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.username.label'));
        $markerArray['###secsign.login.password.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.password.label'));
        $markerArray['###secsign.login.heading###'] = htmlspecialchars($this->pi_getLL('secsign.login.heading'));
        
        $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
       
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
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/ShowRestoreQRCode.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        $subpart = $this->templateService->getSubpart($this->template, '###RESTORE_QR_TEMPLATE###');     
        $subpartArray = ($linkpartArray = []);
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        
        $markerArray['###restoreurl###'] = htmlspecialchars($restoreurl);
        $markerArray['###email###'] = htmlspecialchars($email);
        $markerArray['###qrcodebase64###'] = htmlspecialchars($qrcodebase64);
        $markerArray['###secsignid###'] = htmlspecialchars($newSecSignId);
        $markerArray['###secsign_username###'] = htmlspecialchars($username);
        
        $markerArray['###secsign.activate.twofa.heading###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.heading'));
        $markerArray['###secsign.activate.twofa.start###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.start'));
        $markerArray['###secsign.activate.twofa.1###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.1'));
        $markerArray['###secsign.activate.twofa.2###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.2'));
        $markerArray['###secsign.activate.twofa.qr###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.qr'));
        $markerArray['###secsign.activate.twofa.qr.desktop###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.qr.desktop'));
        $markerArray['###secsign.activate.twofa.qr.continue###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.qr.continue'));
        $markerArray['###secsign.activate.mail###'] = htmlspecialchars($this->pi_getLL('secsign.activate.mail'));
        $markerArray['###secsign.activate.twofa.3###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.3'));
        $markerArray['###secsign.activate.twofa.3.important###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.3.important'));
        $markerArray['###secsign.activate.twofa.3.end###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.3.end'));
        $markerArray['###secsign.activate.twofa.3.spam###'] = htmlspecialchars($this->pi_getLL('secsign.activate.twofa.3.spam'));
        
        $markerArray['###secsign.login.button.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.button.text'));
        $markerArray['###secsign.login.username.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.username.label'));
        $markerArray['###secsign.login.password.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.password.label'));
        $markerArray['###secsign.login.heading###'] = htmlspecialchars($this->pi_getLL('secsign.login.heading'));
        
        $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
       
    }
    
    
    /**
     * renders Login In Progress Page
     *
     * @return string html to render
     */
    protected function showLoginInProgress($hash,$secsignid,$usernameForSecSignID)
    {
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/AccessPass.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_ACCESS_PASS###');
        $subpartArray = ($linkpartArray = []);
        $markerArray['###ACCESS_PASS_DATA###'] = htmlspecialchars($_POST['secsignidauthsessionicondata']);
        $markerArray['###secsignidauthsessionid###'] = htmlspecialchars($_POST['secsignidauthsessionid']);
        $markerArray['###secsignidauthsessionicondata###'] = htmlspecialchars($_POST['secsignidauthsessionicondata']);
        $markerArray['###secsignidservicename###'] = htmlspecialchars($_POST['secsignidservicename']);
        $markerArray['###secsigniduserid###'] = htmlspecialchars($_POST['secsigniduserid']);
        $markerArray['###secsignidserviceaddress###'] = htmlspecialchars($_POST['secsignidserviceaddress']);
        $markerArray['###secsign_username###'] = htmlspecialchars($usernameForSecSignID);
        
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        
        $markerArray['###secsign.button.cancel###'] = htmlspecialchars($this->pi_getLL('secsign.button.cancel'));
        $markerArray['###secsign.accesspass.for.text###'] = htmlspecialchars($this->pi_getLL('secsign.accesspass.for.text'));
        //$markerArray['###PID###'] = htmlspecialchars($this->spid);
        $markerArray['###AUTHED_HASH###'] = htmlspecialchars($hash);
        $markerArray['###SECSIGN_ID###'] = htmlspecialchars($secsignid);
        $markerArray['###USERNAME###'] = htmlspecialchars($usernameForSecSignID);
        
        //delete session data
        session_start();
        unset($_SESSION['secsignidauthsessionid']);
        unset($_SESSION['secsignidauthsessionicondata']);
        unset($_SESSION['secsignidservicename']);
        unset($_SESSION['secsigniduserid']);
        unset($_SESSION['secsignidserviceaddress']);
        unset($_SESSION['secsignidswitchallowed']);
        
        
       
        $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_LOGIN_ON_ACCESS_PASS###');
        $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        if($this->DEBUG) { error_log("otherOptions is ".print_r($this->otherOptions,true)); }
        if($this->otherOptions)
        {
            $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_SWITCH_METHOD_ACCESS_PASS###');
            $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        }
        
        
        
        
    }
    
     /**
     * renders Login In Progress Page
     *
     * @return string html to render
     */
    protected function showLoginInProgressPWD($hash,$secsignid,$usernameForSecSignID)
    {
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/LoginInProgress.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_LOGIN_IN_PROGRESS###');
        $subpartArray = ($linkpartArray = []);
        
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        
        //$markerArray['###PID###'] = htmlspecialchars($this->spid);
        $markerArray['###AUTHED_HASH###'] = htmlspecialchars($hash);
        $markerArray['###SECSIGN_ID###'] = htmlspecialchars($secsignid);
        $markerArray['###USERNAME###'] = htmlspecialchars($usernameForSecSignID);
        $markerArray['###secsign_username###'] = htmlspecialchars($usernameForSecSignID);
        
        //delete session data
        session_start();
        unset($_SESSION['secsignidauthsessionid']);
        unset($_SESSION['secsignidauthsessionicondata']);
        unset($_SESSION['secsignidservicename']);
        unset($_SESSION['secsigniduserid']);
        unset($_SESSION['secsignidserviceaddress']);
        unset($_SESSION['secsignidswitchallowed']);
        
        
       
        $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());

        
        
        
        
    }
    
    
    
     private function showSelectionToActive($possibleSelections,$username,$newAllowed,$toConfirm=null)
    {
        $secsignid=in_array('secsignid', $possibleSelections);
        $fido=in_array('fido', $possibleSelections);
        $totp=in_array('totp', $possibleSelections);
        $mailotp=in_array('mailotp', $possibleSelections);
        
        
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/ShowSelectionToActivate.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        
        
        if($toConfirm)
        {
            $markerArray['###secsignid.login.methodselect.default.title###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.active.title'));
            $markerArray['###secsignid.login.methodselect.default.subtitle###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.active.subtitle'));
        }else{
            $markerArray['###secsignid.login.methodselect.default.title###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.default.title'));
            $markerArray['###secsignid.login.methodselect.default.subtitle###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.default.subtitle'));
        }
        $markerArray['###secsignid.login.methodselect.id.description###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.id.description'));
        $markerArray['###secsignid.login.methodselect.id.title###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.id.title'));
        $markerArray['###secsignid.login.methodselect.totp.description###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.totp.description'));
        $markerArray['###secsignid.login.methodselect.totp.title###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.totp.title'));
        $markerArray['###secsignid.login.methodselect.fido.description###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.fido.description'));
        $markerArray['###secsignid.login.methodselect.fido.title###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.fido.title'));
        $markerArray['###secsignid.login.methodselect.mail.description###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.mail.description'));
        $markerArray['###secsignid.login.methodselect.mail.title###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.mail.title'));
        $markerArray['###secsignid.login.methodselect.new.description###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.new.description'));
        $markerArray['###secsignid.login.methodselect.new.title###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.new.title'));
        $markerArray['###secsign.button.cancel###'] = htmlspecialchars($this->pi_getLL('secsign.button.cancel'));
        
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        $markerArray['###secsign_username###'] = htmlspecialchars($username);
         if($toConfirm)
        {
             $markerArray['###toConfirm###'] = htmlspecialchars($toConfirm);
        }else{
             $markerArray['###toConfirm###'] = '';
        }
        
        
        $markerArray['###secsign.login.button.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.button.text'));
        $markerArray['###secsign.login.username.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.username.label'));
        $markerArray['###secsign.login.password.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.password.label'));
        $markerArray['###secsign.login.heading###'] = htmlspecialchars($this->pi_getLL('secsign.login.heading'));
        
        
        $subpart = $this->templateService->getSubpart($this->template, '###SELECT_TO_TEMPLATE_START###');     
        $subpartArray = ($linkpartArray = []);
        $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        
        if($secsignid)
        {
            $subpart = $this->templateService->getSubpart($this->template, '###SELECT_TO_TEMPLATE_SECSIGNID###');     
            $subpartArray = ($linkpartArray = []);
            $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        }
        if($fido)
        {
            $subpart = $this->templateService->getSubpart($this->template, '###SELECT_TO_TEMPLATE_FIDO###');     
            $subpartArray = ($linkpartArray = []);
            $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        }
        if($totp)
        {
            $subpart = $this->templateService->getSubpart($this->template, '###SELECT_TO_TEMPLATE_TOTP###');     
            $subpartArray = ($linkpartArray = []);
            $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        }
        if($mailotp)
        {
            $subpart = $this->templateService->getSubpart($this->template, '###SELECT_TO_TEMPLATE_MAILOTP###');     
            $subpartArray = ($linkpartArray = []);
            $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        }
        if($newAllowed)
        {
            $subpart = $this->templateService->getSubpart($this->template, '###SELECT_TO_TEMPLATE_NEW###');     
            $subpartArray = ($linkpartArray = []);
            $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        }
        
        
        $subpart = $this->templateService->getSubpart($this->template, '###SELECT_TO_TEMPLATE_END###');     
        $subpartArray = ($linkpartArray = []);
        $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        
        
        return ;
    }
    
    private function showSelectionToInactive($possibleSelections,$username,$isFirst=false)
    {
        $secsignid=in_array('secsignid', $possibleSelections);
        $fido=in_array('fido', $possibleSelections);
        $totp=in_array('totp', $possibleSelections);
        $mailotp=in_array('mailotp', $possibleSelections);
        
        
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/ShowSelectionToInactive.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        
        $markerArray['###secsignid.login.methodselect.inactive.title###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.inactive.title'));
        $markerArray['###secsignid.login.methodselect.inactive.subtitle###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.inactive.subtitle'));
        $markerArray['###secsignid.login.methodselect.id.description###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.id.description'));
        $markerArray['###secsignid.login.methodselect.id.title###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.id.title'));
        $markerArray['###secsignid.login.methodselect.totp.description###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.totp.description'));
        $markerArray['###secsignid.login.methodselect.totp.title###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.totp.title'));
        $markerArray['###secsignid.login.methodselect.fido.description###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.fido.description'));
        $markerArray['###secsignid.login.methodselect.fido.title###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.fido.title'));
        $markerArray['###secsignid.login.methodselect.mail.description###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.mail.description'));
        $markerArray['###secsignid.login.methodselect.mail.title###'] = htmlspecialchars($this->pi_getLL('secsignid.login.methodselect.mail.title'));
        $markerArray['###secsign.button.cancel###'] = htmlspecialchars($this->pi_getLL('secsign.button.cancel'));
        
        
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        $markerArray['###secsign_username###'] = htmlspecialchars($username);
        $markerArray['###isFirst###'] = htmlspecialchars($isFirst);
        
        $markerArray['###secsign.login.button.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.button.text'));
        $markerArray['###secsign.login.username.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.username.label'));
        $markerArray['###secsign.login.password.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.password.label'));
        $markerArray['###secsign.login.heading###'] = htmlspecialchars($this->pi_getLL('secsign.login.heading'));
        
        
        $subpart = $this->templateService->getSubpart($this->template, '###SELECT_TO_TEMPLATE_START###');     
        $subpartArray = ($linkpartArray = []);
        $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        
        if($secsignid)
        {
            $subpart = $this->templateService->getSubpart($this->template, '###SELECT_TO_TEMPLATE_SECSIGNID###');     
            $subpartArray = ($linkpartArray = []);
            $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        }
        if($fido)
        {
            $subpart = $this->templateService->getSubpart($this->template, '###SELECT_TO_TEMPLATE_FIDO###');     
            $subpartArray = ($linkpartArray = []);
            $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        }
        if($totp)
        {
            $subpart = $this->templateService->getSubpart($this->template, '###SELECT_TO_TEMPLATE_TOTP###');     
            $subpartArray = ($linkpartArray = []);
            $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        }
        if($mailotp)
        {
            $subpart = $this->templateService->getSubpart($this->template, '###SELECT_TO_TEMPLATE_MAILOTP###');     
            $subpartArray = ($linkpartArray = []);
            $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        }
        
        
        $subpart = $this->templateService->getSubpart($this->template, '###SELECT_TO_TEMPLATE_END###');     
        $subpartArray = ($linkpartArray = []);
        $this->content.=$this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        
        
        return ;
    }
    
    private function showTOTPAuth($username,$secsignid,$switchAllowed)
    {
        
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/ShowTOTPLogin.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        $subpart = $this->templateService->getSubpart($this->template, '###TOTP_LOGIN_TEMPLATE###');     
        $subpartArray = ($linkpartArray = []);
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        if( $this->errorMsg)
        {
            $markerArray['###ERROR_MSG###'] = '<div class="secsignid-error">'.$this->errorMsg.'</div>';
        }else{
            $markerArray['###ERROR_MSG###'] = htmlspecialchars('');
        }
        $markerArray['###secsignid###'] = htmlspecialchars($secsignid);
        $markerArray['###secsign_username###'] = htmlspecialchars($username);
        
        $markerArray['###secsign.login.button.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.button.text'));
        $markerArray['###secsign.login.totp.heading###'] = htmlspecialchars($this->pi_getLL('secsign.login.totp.heading'));
        $markerArray['###secsign.login.totp.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.totp.text'));
        $markerArray['###secsign.login.totp.code.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.totp.code.label'));
       
        $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        if($switchAllowed)
        {
            $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_SWITCH_METHOD_TOTP###');
            $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        }
    }
    
    private function showMailOTPAuth($username,$secsignid,$switchAllowed)
    {
        
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/ShowMailOTPLogin.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        $subpart = $this->templateService->getSubpart($this->template, '###MAILOTP_LOGIN_TEMPLATE###');     
        $subpartArray = ($linkpartArray = []);
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        if( $this->errorMsg)
        {
            $markerArray['###ERROR_MSG###'] = '<div class="secsignid-error">'.$this->errorMsg.'</div>';
        }else{
            $markerArray['###ERROR_MSG###'] = htmlspecialchars('');
        }
        $markerArray['###secsignid###'] = htmlspecialchars($secsignid);
        $markerArray['###secsign_username###'] = htmlspecialchars($username);
        
        $markerArray['###secsign.login.mailotp.heading###'] = htmlspecialchars($this->pi_getLL('secsign.login.mailotp.heading'));
        $markerArray['###secsign.login.mailotp.code.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.mailotp.code.label'));
        $markerArray['###secsign.login.mailotp.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.mailotp.text'));
        $markerArray['###secsign.login.button.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.button.text'));
       
       
        $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        if($switchAllowed)
        {
            $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_SWITCH_METHOD_MAILOTP###');
            $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        }
    }
    
    
    
    
    private function showTOTPQRCode($username,$secsignid,$totpqrcodebase64,$totpkeyuri)
    {
        
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/ShowTOTPQRCode.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        $subpart = $this->templateService->getSubpart($this->template, '###TOTP_QR_TEMPLATE###');     
        $subpartArray = ($linkpartArray = []);
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        if( $this->errorMsg)
        {
            $markerArray['###ERROR_MSG###'] = '<div class="secsignid-error">'.$this->errorMsg.'</div>';
        }else{
            $markerArray['###ERROR_MSG###'] = htmlspecialchars('');
        }
        
        $markerArray['###totpQRSecret###'] = htmlspecialchars($totpkeyuri);
        $markerArray['###totpQRCode###'] = htmlspecialchars($totpqrcodebase64);
        $markerArray['###secsignid###'] = htmlspecialchars($secsignid);
        $markerArray['###secsign_username###'] = htmlspecialchars($username);
        
        $markerArray['###secsign.login.button.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.button.text'));
        $markerArray['###secsign.login.totp.heading###'] = htmlspecialchars($this->pi_getLL('secsign.login.totp.heading'));
        $markerArray['###secsign.login.totp.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.totp.text'));
        $markerArray['###secsign.login.totp.code.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.totp.code.label'));
        
        $markerArray['###secsign.login.totp.register.code.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.totp.register.code.text'));
        $markerArray['###secsign.login.totp.register.code.link###'] = htmlspecialchars($this->pi_getLL('secsign.login.totp.register.code.link'));
        $markerArray['###secsign.login.totp.register.text.2###'] = htmlspecialchars($this->pi_getLL('secsign.login.totp.register.text.2'));
        $markerArray['###secsign.login.totp.register.text.1###'] = htmlspecialchars($this->pi_getLL('secsign.login.totp.register.text.1'));
        $markerArray['###secsign.login.totp.register.subheading###'] = htmlspecialchars($this->pi_getLL('secsign.login.totp.register.subheading'));
        $markerArray['###secsignid.button.next###'] = htmlspecialchars($this->pi_getLL('secsignid.button.next'));
        
        
        $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
       
        
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
            
            $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/AccessPass.html';
            $template = GeneralUtility::getFileAbsFileName($templateFile);
            $this->template = file_get_contents($template);
            $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_ACCESS_PASS###');
            $subpartArray = ($linkpartArray = []);
        
            $markerArray['###ACCESS_PASS_DATA###'] = htmlspecialchars($secsignidauthsessionicondata);
            $markerArray['###secsignidauthsessionid###'] = htmlspecialchars($secsignidauthsessionid);
            $markerArray['###secsignidauthsessionicondata###'] = htmlspecialchars($secsignidauthsessionicondata);
            $markerArray['###secsignidservicename###'] = htmlspecialchars($secsignidservicename);
            $markerArray['###secsigniduserid###'] = htmlspecialchars($secsigniduserid);
            $markerArray['###secsignidserviceaddress###'] = htmlspecialchars($secsignidserviceaddress);
            $markerArray['###secsign_username###'] = htmlspecialchars($username);
            

            $markerArray['###RETURN_URL###'] = htmlspecialchars($returnURL);

            $markerArray['###secsign.button.cancel###'] = htmlspecialchars($this->pi_getLL('secsign.button.cancel'));
            $markerArray['###secsign.accesspass.for.text###'] = htmlspecialchars($this->pi_getLL('secsign.accesspass.for.text'));




            $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
            $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_NO_LOGIN_ACCESS_PASS###');
            $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
            $otherOptionsAllowed=$secsignidswitchallowed;
            if($this->DEBUG) { error_log("otherOptionsAllowed is ".$otherOptionsAllowed); }
            if($otherOptionsAllowed)
            {
                $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_SWITCH_METHOD_ACCESS_PASS###');
                $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
            }
        }else{
            $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/AccessPassJustConfirm.html';
            $template = GeneralUtility::getFileAbsFileName($templateFile);
            $this->template = file_get_contents($template);
            $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_ACCESS_PASS_JUST_CONFIRM###');
            $subpartArray = ($linkpartArray = []);
        
            $markerArray['###secsignidauthsessionid###'] = htmlspecialchars($secsignidauthsessionid);
            
            $markerArray['###secsigniduserid###'] = htmlspecialchars($secsigniduserid);
            $markerArray['###secsignidserviceaddress###'] = htmlspecialchars($secsignidserviceaddress);
            $markerArray['###secsign_username###'] = htmlspecialchars($username);

            $markerArray['###RETURN_URL###'] = htmlspecialchars($returnURL);

            $markerArray['###secsign.button.cancel###'] = htmlspecialchars($this->pi_getLL('secsign.button.cancel'));
            $markerArray['###secsign.accesspass.for.text###'] = htmlspecialchars($this->pi_getLL('secsign.accesspass.for.text'));




            $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
            $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_NO_LOGIN_ACCESS_PASS_JUST_CONFIRM###');
            $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
            $otherOptionsAllowed=$secsignidswitchallowed;
            if($this->DEBUG) { error_log("otherOptionsAllowed is ".$otherOptionsAllowed); }
            if($otherOptionsAllowed)
            {
                $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_SWITCH_METHOD_ACCESS_PASS_JUST_CONFIRM###');
                $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
            }
        }

    }
    
    
    
    /**
     * renders logout form, if user is already logged in
     *
     * @return string html to render
     */
    protected function showLogout()
    {
        
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/Logout.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_LOGOUT###');
        $subpartArray = ($linkpartArray = []);
        $markerArray['###LOGOUT_LABEL###'] = htmlspecialchars($this->pi_getLL('logout'));
        $markerArray['###NAME###'] = htmlspecialchars($this->frontendController->fe_user->user['name']);
        $markerArray['###USERNAME###'] = htmlspecialchars($this->frontendController->fe_user->user['username']);
        $markerArray['###USERNAME_LABEL###'] = htmlspecialchars($this->pi_getLL('username'));
        $markerArray['###NOREDIRECT###'] = $this->noRedirect ? '1' : '0';
        $markerArray['###PREFIXID###'] = $this->prefixId;
        
        $markerArray['###secsign.button.logout###'] = htmlspecialchars($this->pi_getLL('secsign.button.logout'));
        $markerArray['###secsign.login.username.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.username.label'));
       
        $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
       
    }
    
    private function showFIDORegisterNameInput($username,$secsignid,$accessToken=null)
    {
        
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/FIDORegisterStart.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        $subpart = $this->templateService->getSubpart($this->template, '###FIDO_REGISTER_START_TEMPLATE###');     
        $subpartArray = ($linkpartArray = []);
         $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        if( $this->errorMsg)
        {
            $markerArray['###ERROR_MSG###'] = '<div class="secsignid-error">'.$this->errorMsg.'</div>';
        }else{
            $markerArray['###ERROR_MSG###'] = htmlspecialchars('');
        }
        $markerArray['###secsignid###'] = htmlspecialchars($secsignid);
        $markerArray['###secsign_username###'] = htmlspecialchars($username);
        if($accessToken)
        {
            $markerArray['###accesstoken###'] = htmlspecialchars($accessToken);
        }else{
            $markerArray['###accesstoken###'] = '';
        }
        
        $markerArray['###secsign.login.button.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.button.text'));
        $markerArray['###secsign.login.username.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.username.label'));
        $markerArray['###secsign.login.password.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.password.label'));
        $markerArray['###secsign.login.heading###'] = htmlspecialchars($this->pi_getLL('secsign.login.heading'));
        
        $markerArray['###secsignid.login.fido.register.button###'] = htmlspecialchars($this->pi_getLL('secsignid.login.fido.register.button'));
        $markerArray['###secsignid.login.fido.register.input.hint###'] = htmlspecialchars($this->pi_getLL('secsignid.login.fido.register.input.hint'));
        $markerArray['###secsignid.login.fido.register.label###'] = htmlspecialchars($this->pi_getLL('secsignid.login.fido.register.label'));
        $markerArray['###secsignid.login.fido.register.subheading###'] = htmlspecialchars($this->pi_getLL('secsignid.login.fido.register.subheading'));
        $markerArray['###secsignid.login.fido.register.text###'] = htmlspecialchars($this->pi_getLL('secsignid.login.fido.register.text'));
        $markerArray['###secsignid.login.fido.heading###'] = htmlspecialchars($this->pi_getLL('secsignid.login.fido.heading'));
        $markerArray['###secsignid.login.fido.hint###'] = htmlspecialchars($this->pi_getLL('secsignid.login.fido.hint'));
       
       
        $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
       
    }
    
    private function showFIDOAuth($username,$secsignid,$fromServer,$otherOptionsAllowed)
    {
        $templateFile = 'EXT:secsign/Resources/Private/Frontend/Templates/FIDOAuthenticate.html';
        $template = GeneralUtility::getFileAbsFileName($templateFile);
        $this->template = file_get_contents($template);
        $subpart = $this->templateService->getSubpart($this->template, '###FIDO_AUTH_TEMPLATE###');     
        $subpartArray = ($linkpartArray = []);
        
        $markerArray['###fromServer###'] = json_encode($fromServer);
        
        error_log('from markerArray: '.$markerArray['###fromServer###']);
        $markerArray['###RETURN_URL###'] = htmlspecialchars($this->returnURL);
        if( $this->errorMsg)
        {
            $markerArray['###ERROR_MSG###'] = '<div class="secsignid-error">'.$this->errorMsg.'</div>';
        }else{
            $markerArray['###ERROR_MSG###'] = htmlspecialchars('');
        }
        $markerArray['###secsignid###'] = htmlspecialchars($secsignid);
        $markerArray['###secsign_username###'] = htmlspecialchars($username);
        
        $markerArray['###secsignid.login.fido.heading###'] = htmlspecialchars($this->pi_getLL('secsignid.login.fido.heading'));
        $markerArray['###secsignid.login.fido.auth.subheading###'] = htmlspecialchars($this->pi_getLL('secsignid.login.fido.auth.subheading'));
        $markerArray['###secsignid.login.fido.auth.text###'] = htmlspecialchars($this->pi_getLL('secsignid.login.fido.auth.text'));
        $markerArray['###secsignid.login.fido.auth.button###'] = htmlspecialchars($this->pi_getLL('secsignid.login.fido.auth.button'));
        $markerArray['###secsignid.login.fido.hint###'] = htmlspecialchars($this->pi_getLL('secsignid.login.fido.hint'));
        
        $markerArray['###secsign.login.button.text###'] = htmlspecialchars($this->pi_getLL('secsign.login.button.text'));
        $markerArray['###secsign.login.username.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.username.label'));
        $markerArray['###secsign.login.password.label###'] = htmlspecialchars($this->pi_getLL('secsign.login.password.label'));
        $markerArray['###secsign.login.heading###'] = htmlspecialchars($this->pi_getLL('secsign.login.heading'));
        
        $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
       
        if($otherOptionsAllowed)
        {
            $subpart = $this->templateService->getSubpart($this->template, '###TEMPLATE_SWITCH_METHOD_FIDO###');
            $this->content.= $this->templateService->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
        }
    }
    
     
    
}

