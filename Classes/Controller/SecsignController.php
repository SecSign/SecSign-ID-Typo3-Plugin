<?php
namespace Secsign\Secsign\Controller;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Secsign\Secsign\Controller\SecSignIDApi;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014 SecSign Technologies Inc., SecSign Technologies Inc.
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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

// $Id: SecsignController.php,v 1.1 2015/02/12 15:33:18 titus Exp $

/**
 * SecsignController
 */
class SecsignController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * action login
     *
     * @return void
     */
    public function loginAction()
    {
        //if the user is logged in show 'logout' link
        $user = $GLOBALS['TSFE']->fe_user->user['username'];
        if (isset($user) && $user != '') {
            $this->redirect('logout');
        }

        //check if authsession exists to prevent reload
        $sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_secsign_secsignfe');
        if (is_array($sessionData)) {
            if (array_key_exists('authsession', $sessionData)) {
                if ($sessionData['authsession'] != null)
                    $this->redirect('accesspass');
            }
        }

        $confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['secsign']);
        $pretext = $confArray['secsignPretextFE'];
        $posttext = $confArray['secsignPosttextFE'];
        if($pretext=='')$pretext='For more comfort and security use SecSign ID for sign-in.';
        if($posttext=='')$posttext='More information about the advantages of our two-factor authentication <a href="https://secsign.com">secsign.com</a>';

        $this->view->assign('secsignpretext', $pretext);
        $this->view->assign('secsignposttext', $posttext);
        $this->view->assign('secsign', 'login');
    }

    /**
     * action logout
     *
     * @return void
     */
    public function logoutAction()
    {
        $user = $GLOBALS['TSFE']->fe_user->user['username'];
        $confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['secsign']);
        $name = $confArray['secsignGreetingNameFE'];

        //if Secsignid
        if($name){
            //get secsignid
            $user = $GLOBALS['TSFE']->fe_user->user['secsignid'];
        }

        $this->view->assign('greeting', $confArray['secsignGreetingEnableFE']);
        $this->view->assign('user', $user);
    }

    /**
     * action accesspass
     *
     * @return void
     */
    public function accesspassAction()
    {

        $this->response->addAdditionalHeaderData('<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>');

        //get Api
        $secSignIDApi = NULL;
        try {
            $secSignIDApi = new SecSignIDApi();
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(),'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
        }

        //check if authsession exists to prevent reload
        $authsession = null;
        $sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_secsign_secsignfe');
        if (is_array($sessionData)) {
            if (array_key_exists('authsession', $sessionData)) {
                $authsession = unserialize($sessionData['authsession']);
            }
        }

        if ($authsession == null) {
            //check if SecSign ID is associated to typo3 user
            $secsignid = $this->request->getArgument('secsignid');

            $confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['secsign']);
            $secsignid_service_name = $confArray['secsignServicenameFE'];
            if($secsignid_service_name=='') $secsignid_service_name = $GLOBALS['TSFE']->page['title'];

            $app_uri = $this->uriBuilder->getRequest()->getRequestUri();
            if ($pos_get = strpos($app_uri, '?')) $app_uri = substr($app_uri, 0, $pos_get);
            $secsignid_service_address = $app_uri;
            try {
                $authsession = $secSignIDApi->requestAuthSession($secsignid, $secsignid_service_name, $secsignid_service_address);
            } catch (\Exception $e) {
                $this->addFlashMessage($e->getMessage(),'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
                $this->redirect('login');
            }

            if (isset($authsession)) {
                //store authsession in session
                $sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_secsign_secsignfe');
                $sessionData['authsession'] = serialize($authsession);
                $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_secsign_secsignfe', $sessionData);
                $GLOBALS['TSFE']->fe_user->storeSessionData();
            }
        }

        $auth = array(
            'secsignid' => $authsession->getSecSignID(),
            'secsignidauthsessionid' => $authsession->getAuthSessionID(),
            'secsignidrequestid' => $authsession->getRequestID(),
            'secsignidservicename' => $authsession->getRequestingServiceName(),
            'secsignidserviceaddress' => $authsession->getRequestingServiceAddress(),
            'secsignidauthsessionicondata' => $authsession->getIconData(),
        );
        $this->view->assign('auth', $auth);

    }

    /**
     * action cancel
     *
     * @return void
     */
    public function cancelAction()
    {
        //close SecSign authSession
        try {
            $secSignIDApi = new SecSignIDApi();
            $authsession = new AuthSession();
            $authsession->createAuthSessionFromArray(array(
                'secsignid' => $this->request->getArgument('secsignid'),
                'authsessionid' => $this->request->getArgument('secsignidauthsessionid'),
                'requestid' => $this->request->getArgument('secsignidrequestid'),
                'servicename' => $this->request->getArgument('secsignidservicename'),
                'serviceaddress' => $this->request->getArgument('secsignidserviceaddress'),
                'authsessionicondata' => $this->request->getArgument('secsignidauthsessionicondata')
            ));
            $secSignIDApi->cancelAuthSession($authsession);
        } catch (Exception $e) {
            $this->addFlashMessage($e->getMessage(),'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
        }

        //delete authsession
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_secsign_secsignfe', NULL);

        //go to login form
        $this->redirect('login');
    }

    /**
     * action auth
     *
     * @return void
     */
    public function authAction()
    {
        //check secsign auth response
        $secSignIDApi = NULL;
        try {
            $secSignIDApi = new SecSignIDApi();
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(),'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
        }

        $authSession = new AuthSession();
        $authSession->createAuthSessionFromArray(array(
            'secsignid' => $this->request->getArgument('secsignid'),
            'authsessionid' => $this->request->getArgument('secsignidauthsessionid'),
            'requestid' => $this->request->getArgument('secsignidrequestid'),
            'servicename' => $this->request->getArgument('secsignidservicename'),
            'serviceaddress' => $this->request->getArgument('secsignidserviceaddress'),
            'authsessionicondata' => $this->request->getArgument('secsignidauthsessionicondata')
        ));

        try {
            $authsessionStatus = $secSignIDApi->getAuthSessionState($authSession);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(),'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
        }

        if (AuthSession::AUTHENTICATED != $authsessionStatus) {
            if (AuthSession::PENDING == $authsessionStatus || AuthSession::FETCHED == $authsessionStatus) {
                //PENDING
                $this->addFlashMessage('Authentication Session is still pending. Please accept the correct access pass on your smartphone.','',\TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
                $this->redirect('accesspass');
            } else {
                //DENIED
                $this->addFlashMessage('SecSign ID was denied!','',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
                //delete authsession
                $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_secsign_secsignfe', NULL);
                $this->redirect('login');
            }
        } else {
            //AUTHENTICATED
            //get typo3 user
            $secsignid = $this->request->getArgument('secsignid');
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('username', 'fe_users', 'secsignid="'.$secsignid.'"');
            if($res->field_count == 0){
                //no user with SecSign ID
                $this->addFlashMessage('No user with the SecSign ID '.$secsignid,'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
                $this->redirect('login');
            }elseif($res->field_count == 1){
                //one user with SecSign ID
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
                $typo3User = $row['username'];

                if($typo3User==null OR $typo3User==''){
                    $this->addFlashMessage('No user with the SecSign ID '.$secsignid,'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
                    $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_secsign_secsignfe', NULL);
                    $this->redirect('login');
                }

                //do not use a particular pid
                $GLOBALS['TSFE']->fe_user->checkPid=0;
                $info = $GLOBALS['TSFE']->fe_user->getAuthInfoArray();
                $user = $GLOBALS['TSFE']->fe_user->fetchUserRecord($info['db_user'] , $typo3User);
                $ok=true; //$GLOBALS['TSFE']->fe_user->compareUident($user, $loginData);
                if($ok) {
                    //login successful
                    $GLOBALS['TSFE']->fe_user->createUserSession($user);
                    $GLOBALS['TSFE']->fe_user->setKey('ses', 'dummy', true);
                    //delete SecSign authsession
                    $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_secsign_secsignfe', null);
                    $confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['secsign']);
                    if($confArray['secsignLoginRedirectFE']){
                        $this->redirectToURI($confArray['secsignLoginRedirectFE']);
                    }else{
                        $this->redirect('logout');
                    }
                } else {
                    //delete authsession
                    $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_secsign_secsignfe', NULL);
                    $this->addFlashMessage('Login failed.','',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
                    $this->redirect('login');
                }
            }else{
                //multiple users with same SecSign ID
                $this->addFlashMessage('Multiple users have the same SecSign ID '.$secsignid,'',\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
                $this->redirect('login');
            }
        }
    }

    /**
     * action userlogout
     *
     * @return void
     */
    public function userlogoutAction()
    {
        //typo3 logout
        $GLOBALS['TSFE']->fe_user->logoff();

        $confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['secsign']);
        if($confArray['secsignLogoutRedirectFE']){
            $this->redirectToURI($confArray['secsignLogoutRedirectFE']);
        }else{
            $this->redirect('login');
        }
    }

    /**
     * action settings
     *
     * @return void
     */
    public function settingsAction()
    {

    }


    public function initializeAction() {
    }
}