<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	$_EXTKEY,
	'Secsignfe',
	'secsignFE'
);

if (TYPO3_MODE === 'BE') {

	/**
	 * Registers the Backend Module
	 */
	 
    $confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['secsign']);
    if($confArray['secsignHelpEnableBE']){
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'Secsign.' . $_EXTKEY,
            'tools',	 // Make module a submodule of 'tools'
            'secsignbe',	// Submodule key
            '',						// Position
            array(
                'Secsign' => 'settings',

            ),
            array(
                'access' => 'user,group',
                'icon'   => 'EXT:' . $_EXTKEY . '/ext_icon.png',
                'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_secsignbe.xlf',
            )
        );
    }

    /**
     * Adds SecSign ID fields to FE & BE User Settings
     */
    $cols_fe = array(
        'secsignid' => array(
            'exclude' => 0,
            'label' => 'SecSign ID',
            'config' => array(
                'type' => 'input'
            ,)));
    $cols_be = array(
        'secsignid' => array(
            'exclude' => 0,
            'label' => 'SecSign ID',
            'config' => array(
                'type' => 'input'
            ,)));

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users',$cols_fe,1);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users','secsignid', '', 'after:password');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users',$cols_be,1);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users','secsignid', '', 'after:password');

    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['secsignid'] = array(
        'label' => 'SecSign ID',
        'type' => 'text',
        'table' => 'be_users',
    );
    
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings('be_users.secsignid,secsignid','after:email');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'SecSignID');


/* Set backend login template */

// this only works for TYPO3 4.4 bis 6.x (actually also with 7.1)
$confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['secsign']);
//$confArray = unserialize($TYPO3_CONF_VARS['EXT']['extConf'][$_EXTKEY]);
if($confArray['secsignEnableBE']){
    $version = explode('.', TYPO3_version);
    
    if ($version[0] >= 7) {
    	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1433416747]['provider'] = SECSIGN\SecSignID\LoginProvider\SecSignIDLoginProvider::class;
    }
    
    $tmplPath = 'EXT:backend/Resources/Private/Templates/login.html';
	$template = 'typo3conf/ext/secsign/Resources/Private/Backend/Templates/login-v6.php';
	$TBE_STYLES['htmlTemplates'][$tmplPath] = PATH_site . $template;
    $TBE_STYLES['stylesheet2'] = '../typo3conf/ext/secsign/Resources/Public/css/secsignid_layout.css';
}

