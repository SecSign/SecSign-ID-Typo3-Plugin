<?php

namespace SecSign\Secsign\Accessor;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

class DBAccessor
{
    static function deleteHashForSecSignID($secsignid)
    {
        
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('secsign_hashes');
        $queryBuilder->delete('secsign_hashes')
                ->where($queryBuilder->expr()->eq('secsignid', $queryBuilder->createNamedParameter($secsignid, \PDO::PARAM_STR)))
                ->execute();
    }
    
    static function deleteHashForSecSignIDBE($secsignid)
    {
        
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('secsign_hashes_be');
        $queryBuilder->delete('secsign_hashes_be')
                ->where($queryBuilder->expr()->eq('secsignid', $queryBuilder->createNamedParameter($secsignid, \PDO::PARAM_STR)))
                ->execute();
    }
    
    static function getValueForSecSignID($key,$secsignid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
        $query = $queryBuilder
            ->select($key)
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('secsignid', $queryBuilder->createNamedParameter($secsignid, \PDO::PARAM_STR)))
            ->execute();
        $affectedRows=$query->fetch();
        return $affectedRows[$key];
    }
    
    static function getValueForSecSignIDBE($key,$secsignid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $query = $queryBuilder
            ->select($key)
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('secsignid', $queryBuilder->createNamedParameter($secsignid, \PDO::PARAM_STR)))
            ->execute();
        $affectedRows=$query->fetch();
        return $affectedRows[$key];
    }
    
    
    
    static function getHashForSecSignID($secsignid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('secsign_hashes');
        $query = $queryBuilder
            ->select('authedHash')
            ->from('secsign_hashes')
            ->where($queryBuilder->expr()->eq('secsignid', $queryBuilder->createNamedParameter($secsignid, \PDO::PARAM_STR)))
            ->execute();
        $affectedRows=$query->fetch();
        return $affectedRows['authedHash'];
    }
    
    static function getHashForSecSignIDBE($secsignid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('secsign_hashes_be');
        $query = $queryBuilder
            ->select('authedHash')
            ->from('secsign_hashes_be')
            ->where($queryBuilder->expr()->eq('secsignid', $queryBuilder->createNamedParameter($secsignid, \PDO::PARAM_STR)))
            ->execute();
        $affectedRows=$query->fetch();
        return $affectedRows['authedHash'];
    }
    
    static function getValueForTempSecSignID($key,$secsignid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
        $query = $queryBuilder
            ->select($key)
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('secsignid_temp', $queryBuilder->createNamedParameter($secsignid, \PDO::PARAM_STR)))
            ->execute();
        $affectedRows=$query->fetch();
        return $affectedRows[$key];
    }
    
    static function getValueForTempSecSignIDBE($key,$secsignid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $query = $queryBuilder
            ->select($key)
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('secsignid_temp', $queryBuilder->createNamedParameter($secsignid, \PDO::PARAM_STR)))
            ->execute();
        $affectedRows=$query->fetch();
        return $affectedRows[$key];
    }
    
    static function insertHashForSecSignID($hash,$secsignid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('secsign_hashes');
        $affectedRows = $queryBuilder
            ->insert('secsign_hashes')
            ->values([
               'secsignid' => $secsignid,
               'authedHash' => $hash,
            ])
            ->execute();
    }
    
    static function insertHashForSecSignIDBE($hash,$secsignid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('secsign_hashes_be');
        $affectedRows = $queryBuilder
            ->insert('secsign_hashes_be')
            ->values([
               'secsignid' => $secsignid,
               'authedHash' => $hash,
            ])
            ->execute();
    }
    
    static function updateValueForFEUser($key,$value,$username)
    {
        error_log("key is ".$key);
        error_log("value is ".$value);
        error_log("username is ".$username);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
        $queryBuilder->update('fe_users')
                ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username, \PDO::PARAM_STR)))
                ->set($key,$value)
                ->execute();
           
    }
    
    static function updateValueForBEUser($key,$value,$username)
    {
        error_log("key is ".$key);
        error_log("value is ".$value);
        error_log("username is ".$username);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $queryBuilder->update('be_users')
                ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username, \PDO::PARAM_STR)))
                ->set($key,$value)
                ->execute();
           
    }

    static function getValueForFEUser($key,$username)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
        $query = $queryBuilder
            ->select($key)
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username, \PDO::PARAM_STR)))
            ->execute();
        $affectedRows=$query->fetch();
        return $affectedRows[$key];
    }
    
    static function getValueForBEUser($key,$username)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $query = $queryBuilder
            ->select($key)
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username, \PDO::PARAM_STR)))
            ->execute();
        $affectedRows=$query->fetch();
        return $affectedRows[$key];
    }
    
    static function getNeedsTwoFAForGroupID($groupid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_groups');
        $query = $queryBuilder
            ->select('needs_twofa')
            ->from('fe_groups')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($groupid, \PDO::PARAM_INT)))
            ->execute();
        $affectedRows=$query->fetch();
        return $affectedRows['needs_twofa'];
    }
    
    static function getNeedsTwoFAForBEGroupID($groupid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_groups');
        $query = $queryBuilder
            ->select('needs_twofa')
            ->from('be_groups')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($groupid, \PDO::PARAM_INT)))
            ->execute();
        $affectedRows=$query->fetch();
        return $affectedRows['needs_twofa'];
    }
    
    static function getAllowedMethodsForGroupID($groupid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_groups');
        $query = $queryBuilder
            ->select('allowed_methods')
            ->from('fe_groups')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($groupid, \PDO::PARAM_STR)))
            ->execute();
        $affectedRows=$query->fetch();
        return $affectedRows['allowed_methods'];
    }
    
    static function getAllowedMethodsForBEGroupID($groupid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_groups');
        $query = $queryBuilder
            ->select('allowed_methods')
            ->from('be_groups')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($groupid, \PDO::PARAM_STR)))
            ->execute();
        $affectedRows=$query->fetch();
        return $affectedRows['allowed_methods'];
    }
    
    static function addActiveMethodForFEUser($activeMethod,$username)
    {
        $valueBefore=DBAccessor::getValueForFEUser('activeMethods', $username);
        if($valueBefore!='')
        {
                $valueArray= explode(',', $valueBefore);
                if(!in_array($activeMethod, $valueArray))
                {
                    $valueArray[]=$activeMethod;
                }
        }else{
            $valueArray= array();
            $valueArray[]=$activeMethod;
        }
        
        
        DBAccessor::updateValueForFEUser('activeMethods', implode(',',$valueArray), $username);
    }
    
    
    static function addActiveMethodForBEUser($activeMethod,$username)
    {
        $valueBefore=DBAccessor::getValueForBEUser('activeMethods', $username);
        if($valueBefore!='')
        {
                $valueArray= explode(',', $valueBefore);
                if(!in_array($activeMethod, $valueArray))
                {
                    $valueArray[]=$activeMethod;
                }
        }else{
            $valueArray= array();
            $valueArray[]=$activeMethod;
        }
        
        
        DBAccessor::updateValueForBEUser('activeMethods', implode(',',$valueArray), $username);
    }
    
    
    static function getAllowed2FAMethods($username)
    {
        //get groupids for user with username
        $usergroup=\SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('usergroup', $username);
        //as array
        $groupArray=explode(",",$usergroup);
        
        $allowedForUser=array();
        //check if one group has 2fa activated      
        foreach ($groupArray as $groupid) {
            if(\SecSign\Secsign\Accessor\DBAccessor::getNeedsTwoFAForGroupID($groupid))
            {
                if(SettingsAccessor::getValueFromSetting('debugLogging')) {error_log('groupid is '.$groupid);}

                $allowedForGroup=\SecSign\Secsign\Accessor\DBAccessor::getAllowedMethodsForGroupID($groupid);
                if(SettingsAccessor::getValueFromSetting('debugLogging')) {error_log('allowedForGroup is '.$allowedForGroup);}

                $allowedForGroupArray=explode(",",$allowedForGroup);
                if(SettingsAccessor::getValueFromSetting('debugLogging')) {error_log('allowedForGroupArray is '. print_r($allowedForGroupArray,true));}

                $allowedForUser=\SecSign\Secsign\Utils\SecSignUtils::mergeAllowedMethods($allowedForUser, $allowedForGroupArray);
                if(SettingsAccessor::getValueFromSetting('debugLogging')) {error_log('allowedForUser is '. print_r($allowedForUser,true));}
            }
        }
        
        
        return $allowedForUser;
    }
    
     static function getAllowed2FAMethodsBE($username)
    {
        //get groupids for user with username
        $usergroup=\SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('usergroup', $username);
        //as array
        $groupArray=explode(",",$usergroup);
        
        $allowedForUser=array();
        //check if one group has 2fa activated      
        foreach ($groupArray as $groupid) {
            if(\SecSign\Secsign\Accessor\DBAccessor::getNeedsTwoFAForBEGroupID($groupid))
            {
                if(SettingsAccessor::getValueFromSetting('debugLogging')) {error_log('groupid is '.$groupid);}

                $allowedForGroup=\SecSign\Secsign\Accessor\DBAccessor::getAllowedMethodsForBEGroupID($groupid);
                if(SettingsAccessor::getValueFromSetting('debugLogging')) {error_log('allowedForGroup is '.$allowedForGroup);}

                $allowedForGroupArray=explode(",",$allowedForGroup);
                if(SettingsAccessor::getValueFromSetting('debugLogging')) {error_log('allowedForGroupArray is '. print_r($allowedForGroupArray,true));}

                $allowedForUser=\SecSign\Secsign\Utils\SecSignUtils::mergeAllowedMethods($allowedForUser, $allowedForGroupArray);
                if(SettingsAccessor::getValueFromSetting('debugLogging')) {error_log('allowedForUser is '. print_r($allowedForUser,true));}
            }
        }
        
        
        return $allowedForUser;
    }
    
    static function getActivated2FAMethods($username)
    {
        $activatedMethods= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('activeMethods',$username);
        if($activatedMethods=='')
        {
            $activatedMethodsArray=array();
        }else{
            $activatedMethodsArray=explode(",",$activatedMethods);
        }
        
        
        return $activatedMethodsArray;
    }
    
    static function getAllowedAndActivated2FAMethods($username)
    {
        $activatedMethods= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('activeMethods',$username);
        $allowedMethodsArray= self::getAllowed2FAMethods($username);
        if($activatedMethods=='')
        {
            $resultArray=array();
        }else{
            $activatedMethodsArray=explode(",",$activatedMethods);
            $resultArray= array_intersect($activatedMethodsArray,$allowedMethodsArray);
        }
        
        
        return $resultArray;
    }
    
    static function getActivated2FAMethodsBE($username)
    {
        $activatedMethods= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('activeMethods',$username);
        if($activatedMethods=='')
        {
            $activatedMethodsArray=array();
        }else{
            $activatedMethodsArray=explode(",",$activatedMethods);
        }
        
        
        return $activatedMethodsArray;
    }
    
    static function getAllowedAndActivated2FAMethodsBE($username)
    {
        $activatedMethods= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('activeMethods',$username);
        $allowedMethodsArray= self::getAllowed2FAMethodsBE($username);
        
        if($activatedMethods=='')
        {
            $resultArray=array();
        }else{
            $activatedMethodsArray=explode(",",$activatedMethods);
            $resultArray= array_intersect($activatedMethodsArray,$allowedMethodsArray);
        }
        
        
        return $resultArray;
    }
    
    
    static function getLastMethod($username)
    {
        $lastMethod= \SecSign\Secsign\Accessor\DBAccessor::getValueForFEUser('lastMethod',$username);
        
        return $lastMethod;
    }
    
    static function getLastMethodBE($username)
    {
        $lastMethod= \SecSign\Secsign\Accessor\DBAccessor::getValueForBEUser('lastMethod',$username);
        
        return $lastMethod;
    }
    
    static function getChangeAllowed($username)
    {
        $activatedMethods=\SecSign\Secsign\Accessor\DBAccessor::getActivated2FAMethods($username);
        $allowedMethods=\SecSign\Secsign\Accessor\DBAccessor::getAllowed2FAMethods($username);
        
        $intersect= array_intersect($activatedMethods,$allowedMethods);
        $notActivated= array_diff($allowedMethods,$activatedMethods);
        
        $changeAllowed=count($intersect)>1 || count($notActivated) > 0;
        return $changeAllowed;
    }
    
    static function getChangeAllowedBE($username)
    {
        $activatedMethods=\SecSign\Secsign\Accessor\DBAccessor::getActivated2FAMethodsBE($username);
        $allowedMethods=\SecSign\Secsign\Accessor\DBAccessor::getAllowed2FAMethodsBE($username);
        
        $intersect= array_intersect($activatedMethods,$allowedMethods);
        $notActivated= array_diff($allowedMethods,$activatedMethods);
        
        $changeAllowed=count($intersect)>1 || count($notActivated) > 0;
        return $changeAllowed;
    }
    
    static function getNotActivatedAndAllowedMethods($username)
    {
        $activatedMethods=\SecSign\Secsign\Accessor\DBAccessor::getActivated2FAMethods($username);
        $allowedMethods=\SecSign\Secsign\Accessor\DBAccessor::getAllowed2FAMethods($username);
        
        return array_diff($allowedMethods,$activatedMethods);
    }
    
    static function getNotActivatedAndAllowedMethodsBE($username)
    {
        $activatedMethods=\SecSign\Secsign\Accessor\DBAccessor::getActivated2FAMethodsBE($username);
        $allowedMethods=\SecSign\Secsign\Accessor\DBAccessor::getAllowed2FAMethodsBE($username);
        
        return array_diff($allowedMethods,$activatedMethods);
    }
}