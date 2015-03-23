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
	 * Registers a Backend Module
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

    t3lib_div::loadTCA("tx_secsign");
    t3lib_extMgm::addTCAcolumns("tx_secsign",$tempColumns);
    t3lib_extMgm::addToAllTCAtypes('tx_secsign','service_name','','');


    t3lib_div::loadTCA('fe_users');
    t3lib_extMgm::addTCAcolumns('fe_users',$cols_fe,1);
    t3lib_extMgm::addToAllTCAtypes('fe_users','secsignid', '', 'after:password');

    t3lib_div::loadTCA('be_users');
    t3lib_extMgm::addTCAcolumns('be_users',$cols_be,1);
    t3lib_extMgm::addToAllTCAtypes('be_users','secsignid', '', 'after:password');

    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['secsign'] = array(
        'label' => 'SecSign ID',
        'type' => 'text',
        'table' => 'be_users',
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings(
        'be_users.secsign,secsign',
        'after:email'
    );
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'SecSignID');

/* Set backend login template */
$confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['secsign']);
if($confArray['secsignEnableBE']){
    $version = explode('.', TYPO3_version);
    $tmplPath = 'EXT:backend/Resources/Private/Templates/login.html';
    $template = 'typo3conf/ext/secsign/Resources/Private/Backend/Templates/login-v6.php';
    /*if ($version[0] == 7) {
        $template = 'typo3conf/ext/secsign/Resources/Private/Backend/Templates/login-v7.php';
    }
    */

    $TBE_STYLES['htmlTemplates'][$tmplPath] = PATH_site . $template;
    $TBE_STYLES['stylesheet2'] = '../typo3conf/ext/secsign/Resources/Public/css/secsign.css';
}

