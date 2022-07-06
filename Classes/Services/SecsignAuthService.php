<?php
namespace SecSign\Secsign\Services;




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

if(class_exists('\TYPO3\CMS\Core\Authentication\AbstractAuthenticationService'))
{
    class MiddleManService extends \TYPO3\CMS\Core\Authentication\AbstractAuthenticationService { }
}else{
    class MiddleManService extends \TYPO3\CMS\Sv\AbstractAuthenticationService { }
}

class SecsignAuthService extends MiddleManService
{

    const NOTICE = 0;
    const PERMISSION_ERROR = 1;
    const SYSTEM_ERROR = 2;
    const LOGIN_ERROR = 3;

    public function getUser()
    {
        $username=$this->login['uname'];
        
        $user = $this->fetchUserRecord($username);
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
        error_log("authUser called");
        //get username from loginData
        $uname=$this->login['uname'];
        //get 'password' from loginData
        $uident=$this->login['uident'];

        //divide 'password' into password and loginToken
        $dividerIndex=strpos($uident,';');
        $passPart=substr($uident,0,$dividerIndex);
        $pass=substr($passPart,5,strlen($passPart)-5);
        $tokenPart=substr($uident,$dividerIndex,strlen($uident)-$dividerIndex);
        $token=substr($tokenPart,15,strlen($tokenPart)-15);
        if(!$token)
        {
            //no token  -> no login
            return 0;
        }
        if($this->mode=='authUserFE')
        {
            
            //get secsignid of user
            $secsignidForUser=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser("secsignid",$uname);

            if(!$secsignidForUser)
            {
                $secsignidForUser=$uname;
            }
        
            $tokenForUser=\SecSign\Secsign\Accessor\DBAccessor::getHashForSecSignID($secsignidForUser);

            \SecSign\Secsign\Accessor\DBAccessor::deleteHashForSecSignID($secsignidForUser);
        }else{
            
            //get secsignid of user
            $secsignidForUser=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser("secsignid",$uname);
            if(!$secsignidForUser)
            {
                $secsignidForUser=$uname;
            }
            //get token saved for secsignid
            $tokenForUser=\SecSign\Secsign\Accessor\DBAccessor::getHashForSecSignIDBE($secsignidForUser);

            \SecSign\Secsign\Accessor\DBAccessor::deleteHashForSecSignIDBE($secsignidForUser);
        }
        if($tokenForUser)
        {
            //token found
            if($tokenForUser==$token)
            {
                //token is good -> login
                if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG){error_log('login success');};
                return 200;
            }else{
                //wrong token, no login
                if(SecSignSecsignAccessorSettingsAccessor::DEBUG){error_log('wrong token, no login');};
                return 0; 
            }
        }else{
           //no token in db, no login
            if(SecSignSecsignAccessorSettingsAccessor::DEBUG){error_log('no token in db, no login');};
           return 0; 
        }
        if(SecSignSecsignAccessorSettingsAccessor::DEBUG){error_log('no login check');};
        return 0;
        
    }
    
   
}

