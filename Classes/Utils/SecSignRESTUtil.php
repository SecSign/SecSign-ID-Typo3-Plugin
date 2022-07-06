<?php

namespace SecSign\Secsign\Utils;


use SecSign\Secsign\Connector\SecSignIDApi;

class SecSignRESTUtil
{
    static function createSecSignIDApi()
    {
        //get Configuration
        $serverURLFromConfig = \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('serverURL');
        if($serverURLFromConfig)
        {
            $serverURL=$serverURLFromConfig;
        }else{
            $serverURL= SecSignConstants::SERVER_URL;
        }

        $pinAccountUserFromConfig = \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('pinAccountUser');
        if($pinAccountUserFromConfig)
        {
            $pinAccountUser=$pinAccountUserFromConfig;
        }else{
            $pinAccountUser= SecSignConstants::PIN_ACCOUNT_USER;
        }

        $pinAccountPasswordFromConfig = \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('pinAccountPassword');
        if($pinAccountPasswordFromConfig)
        {
            $pinAccountPassword=$pinAccountPasswordFromConfig;
        }else{
            $pinAccountPassword= SecSignConstants::PIN_ACCOUNT_PASSWORD;
        }
        
        if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG) { error_log("Create SecSignIDAPI with serverURL: ".$serverURL." and pinAcccountUser: ".$pinAccountUser );}
         
        
        $result=new SecSignIDApi($serverURL,$pinAccountUser,$pinAccountPassword);
        if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG) { $result->setLogger(error_log) ;}
        return $result;
    }
    
    
    
}