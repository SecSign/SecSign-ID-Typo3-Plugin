<?php
namespace Secsign\Secsign;

$apiPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('secsign') . 'Resources/Public/SecSignIDApi/phpApi/SecSignIDApi.php';
require_once($apiPath);

use AuthSession;
use SecSignIDApi;
use TYPO3\CMS\Core\Error\Exception;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 - 2015 Torben Hansen <derhansen@gmail.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class SecsignAuthService extends \TYPO3\CMS\Sv\AbstractAuthenticationService
{

    const NOTICE = 0;
    const PERMISSION_ERROR = 1;
    const SYSTEM_ERROR = 2;
    const LOGIN_ERROR = 3;

    /**
     * Checks if service is available.
     *
     * @return boolean TRUE if service is available
     */
    public function init()
    {
        return true;
    }

    function getUser()
    {
        $confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['secsign']);
        $user = FALSE;

        //if username & password provided, go on except it is disabled
        if (GeneralUtility::_GP('userident') != "") {
                if($confArray['secsignDisableBEPW'] && $confArray['secsignEnableBE']){
                    //delete PW login Cookie
                    if (isset($_COOKIE['secsignLoginPw'])) {
                        unset($_COOKIE['secsignLoginPw']);
                        setcookie('secsignLoginPw', '', time() - 3600); // empty value and old timestamp
                    }
                    header('Refresh: 1; url=index.php?err=2');
                    die();
                }
                $user = $this->fetchUserRecord(GeneralUtility::_GP('username'));
                return $user;
        }

        if ($this->login['status'] == 'login') {
            if ($this->login['uident']) {
                $user = $this->fetchUserRecord($this->login['uname']);
                if (!is_array($user)) {
                    // Failed login attempt (no username found)
                    $this->writeSysLog('No Typo3 BE user found.', self::NOTICE);
                } else {
                    return $user;
                }
            }
        }

        $secsignid = GeneralUtility::_GP('secsigniduserid');
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('username', 'be_users', 'secsignid="' . $secsignid . '"');
        if ($res->field_count != 1) {
            //no user with SecSign ID
            $this->writeSysLog('SecSign ID does not exist.', self::NOTICE);
            return false;
        } else {
            //one user with SecSign ID
            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
            $typo3User = $row['username'];
            if ($typo3User == null OR $typo3User == '') {
                $this->writeSysLog('No Typo3 BE user found for the given SecSign ID.', self::NOTICE);
                header('Refresh: 1; url=index.php?err=1');
                die();
            }
        }
        $user = $this->fetchUserRecord($typo3User);
        return $user;
    }

    /**
     * Authenticates the user by using SecSign ID
     *
     * Will return one of following authentication status codes:
     *  - 0 - authentication failure
     *  - 100 - just go on. User is not authenticated but there is still no reason to stop
     *  - 200 - the service was able to authenticate the user
     *
     */
    public function authUser(array $user)
    {
        //if disabled, go on
        $confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['secsign']);
        if (!$confArray['secsignEnableBE']) {
            return 100;
        }

        //if username & password provided, go on
        if (GeneralUtility::_POST('username')) {
            return 100;
        }

        $secSignIDApi = NULL;
        try {
            $secSignIDApi = new SecSignIDApi();
        } catch (Exception $e) {
            $this->writeSysLog('No SecSign ID API found.', self::SYSTEM_ERROR);
            return false;
        }

        $authSession = new AuthSession();
        $authSession->createAuthSessionFromArray(array(
            'secsignid' => GeneralUtility::_GP('secsigniduserid'),
            'authsessionid' => GeneralUtility::_GP('secsignidauthsessionid'),
            'requestid' => GeneralUtility::_GP('secsignidrequestid'),
            'servicename' => GeneralUtility::_GP('secsignidservicename'),
            'serviceaddress' => GeneralUtility::_GP('secsignidserviceaddress'),
            'authsessionicondata' => GeneralUtility::_GP('secsignidauthsessionicondata')
        ));

        //Cancel Button
        $button = GeneralUtility::_GP('secsignid_authsession_button');
        if ($button == 'cancel') {
            try {
                $secSignIDApi->cancelAuthSession($authSession);
            } catch (Exception $e) {
                $this->writeSysLog('SecSign ID Cancel AuthSession not possible.', self::NOTICE);
                return false;
            }
            return false;
        }

        //Authenticate
        try {
            $authsessionStatus = $secSignIDApi->getAuthSessionState($authSession);
        } catch (Exception $e) {
            $this->writeSysLog('No SecSign ID AuthSession.', self::NOTICE);
            return false;
        }

        if (AuthSession::AUTHENTICATED != $authsessionStatus) {
            if (AuthSession::PENDING == $authsessionStatus || AuthSession::FETCHED == $authsessionStatus) {
                //PENDING
                return 100;
            } else {
                //DENIED
                $this->writeSysLog('SecSign ID was denied.', self::NOTICE);
                return 0;
            }
        } else {
            //AUTHENTICATED
            return 200;
        }
    }

    /**
     * Writes to devlog if enabled
     *
     * @param string $message Message for devlog
     * @return void
     */
    private function writeSysLog($message, $error)
    {
        $confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['secsign']);
        if ($confArray['secsignsyslog']) {
            $type = 255; //  Login or Logout action
            $action = 3; // failed login (+ errorcode 3)
            $GLOBALS['BE_USER']->writelog($type, $action, $error, 0, $message, array(), '', 0, 0, 0, '');

        }
    }

}