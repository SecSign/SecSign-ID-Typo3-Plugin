<?php

namespace SecSign\Secsign\Utils;

class SecSignPinAccountHelper
{
    static function checkForPinAccountAndCreateIfNeeded()
    {
        if(\SecSign\Secsign\Accessor\SettingsAccessor::usesOnPremiseServer())
        {
            $pinAccountUser = \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('pinAccountUser');
            $pinAccountPassword = \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('pinAccountPassword');
            if(!($pinAccountUser && $pinAccountPassword))
            {
                
                $secSignIDApi = \SecSign\Secsign\Utils\SecSignRESTUtil::createSecSignIDApi();
                try {
                    if($_SERVER['HTTPS'])
                    {
                        $serviceaddress='https://'.$_SERVER['HTTP_HOST'];
                    }else{
                        $serviceaddress='http://'.$_SERVER['HTTP_HOST'];
                    }
                    if($this->DEBUG) {error_log("serviceaddress is: ". $serviceaddress);}
                     
                    $result= $secSignIDApi->registerPlugin($serviceaddress,'Typo3 on '.$serviceaddress,'Typo3 AddOn on '.$serviceaddress,'4');
                    if($result['errormsg'])
                    {
                       if($this->DEBUG) { error_log("Error on creating PinAcccount: ".$result['errormsg']);}
                    }else{
                       $accountName=$result['accountName'];
                       $password=$result['password'];

                       if($this->DEBUG) {error_log("$accountName is". $accountName." password is ".$password);}

                       \SecSign\Secsign\Accessor\SettingsAccessor::saveValueToSetting('pinAccountUser', $accountName);
                       \SecSign\Secsign\Accessor\SettingsAccessor::saveValueToSetting('pinAccountPassword', $password);

                    }
                     
                 } catch (\Exception $e) {
                     error_log('Error on registering Plugin');
                 }
            }
        }
    }
}
