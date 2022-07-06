<?php

namespace SecSign\Secsign\Accessor;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class SettingsAccessor
{
    const DEBUG = true;
    
    static function usesOnPremiseServer()
    {
        $serverURLFromConfig = \SecSign\Secsign\Accessor\SettingsAccessor::getValueFromSetting('serverURL');
        if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG) { error_log("serverURL is ".$serverURLFromConfig ); }
        if(!$serverURLFromConfig || $serverURLFromConfig=="https://httpapi.secsign.com" || $serverURLFromConfig=="https://httpapi.secsigntest.com")
        {
            return true;
        }else{
            return false;
        }
    }
    
    static function getValueFromSetting($key)
    {
        if(class_exists("\TYPO3\CMS\Core\Configuration\ExtensionConfiguration"))
        {
            return
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)
            ->get('secsign',$key);
        }else{
            $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['secsign']);
            return $extensionConfiguration[$key];
        }
        
    }
    
    static function saveValueToSetting($key,$setting)
    {
        if(class_exists("\TYPO3\CMS\Core\Configuration\ExtensionConfiguration"))
        {
            if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG) { error_log('new version of settings'); }
            $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)
            ->get('secsign');
            if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG) { error_log('settings before: '.print_r($extensionConfiguration,true)); }
            $extensionConfiguration[$key]= $setting;
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)
            ->set('secsign','',$extensionConfiguration);
            if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG) { error_log('settings saved: '.print_r($extensionConfiguration,true)); }
            
        }else{
            if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG) { error_log('old version of settings'); }
            $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['secsign']);
            if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG) { error_log('settings before: '.print_r($extensionConfiguration,true)); }
            $extensionConfiguration[$key]= $setting;
            $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['secsign']= serialize($extensionConfiguration);
            $objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
            if(!$objectManager)
            {
                if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG) {error_log("objectManager is null");}
            }

            $configurationUtility = $objectManager->get('TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility');
            if(!$configurationUtility)
            {
                if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG) {error_log("configurationUtility is null");}
            }

            $newConfiguration = $configurationUtility->getCurrentConfiguration("secsign");
            if(!$newConfiguration)
            {
                if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG) {error_log("newConfiguration is null");}
            }

            \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($newConfiguration, $extensionConfiguration);

            $configurationUtility->writeConfiguration(
                $configurationUtility->convertValuedToNestedConfiguration($newConfiguration),
                "secsign"
            );
            if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG) { error_log('settings saved: '.print_r($extensionConfiguration,true)); }
            
        }
        
    }
    
    
}

