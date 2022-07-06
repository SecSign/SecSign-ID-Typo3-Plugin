<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

///////////// BACKEND /////////////////

if (TYPO3_MODE === 'BE') {

	

    /**
     * Adds SecSign ID fields to FE & BE User Settings
     */
    $cols_be = array(
        'secsignid' => array(
            'exclude' => 0,
            'label' => 'SecSign ID',
            'config' => array(
                'type' => 'input',
                'eval' => 'SecSign\\Secsign\\UserFunctions\\FormEngine\\SecSignIDEval'
                
            ,)));
    
    $cols_be_groups = array(
        'needs_twofa' => array(
            'label' => 'Needs 2FA Authentication',
            'config' => array(
                'type' => 'check'
            ,)
         )
      );
    
    $cols_be_groups2 = array(
        'allowed_methods' => array(
            'label' => 'Allowed 2FA Methods',
            'displayCond' => 'FIELD:needs_twofa:REQ:true',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingleBox',
                'items' => [
                    [
                        'SecSignID',
                        'secsignid',
                    ],
                    [
                        'FIDO',
                        'fido',
                    ],
                    [
                        'TOTP',
                        'totp',
                    ],
                    [
                        'MailOTP',
                        'mailotp',
                    ],
                ],
            )
         )
      );
    
   

    $cols_be2 = array(
      'secsignid_temp' => array(
          'label' => 'SecSign ID',
          'config' => array(
              'type' => 'passthrough'
          ,)
       )
    );
    
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_groups',$cols_be_groups);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_groups',$cols_be_groups2);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users',$cols_be);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users',$cols_be2);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users','secsignid', '', 'after:password');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_groups','needs_twofa', '', 'after:subgroup');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_groups','allowed_methods', '', 'after:needs_twofa');


    
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings('be_users.secsignid,secsignid','after:email');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'SecSignID');


/* Set backend login template */
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1433416747]['provider'] = SecSign\Secsign\LoginProvider\SecSignIDBELoginProvider::class;
   

///////////// FRONTEND /////////////////

 /**
* register Plugin to be available to select
*/
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
   'SecSign.Secsign',
   'Loginplugin',
   'Login Plugin'
);


/**
* Adds SecSign ID fields to FE User Settings
*/
$cols_fe = array(
  'secsignid' => array(
      'label' => 'SecSign ID',
      'config' => array(
          'type' => 'input',
          'eval' => 'SecSign\\Secsign\\UserFunctions\\FormEngine\\SecSignIDEval'
      ,)
   )
);

$cols_fe2 = array(
  'secsignid_temp' => array(
      'label' => 'SecSign ID',
      'config' => array(
          'type' => 'passthrough'
      ,)
   )
);

$cols_fe_groups = array(
  'needs_twofa' => array(
      'label' => 'Needs 2FA Authentication',
      'config' => array(
          'type' => 'check'
      ,)
   )
);

$cols_fe_groups2 = array(
    'allowed_methods' => array(
        'label' => 'Allowed 2FA Methods',
        'displayCond' => 'FIELD:needs_twofa:REQ:true',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingleBox',
            'items' => [
                [
                    'SecSignID',
                    'secsignid',
                ],
                [
                    'FIDO',
                    'fido',
                ],
                [
                    'TOTP',
                    'totp',
                ],
                [
                    'MailOTP',
                    'mailotp',
                ],
            ],
        )
     )
  );


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_groups',$cols_fe_groups);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_groups',$cols_fe_groups2);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users',$cols_fe);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users',$cols_fe2);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users',$cols_fe3);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users',$cols_fe4);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users','secsignid', '', 'after:password');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_groups','needs_twofa', '', 'after:subgroup');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_groups','allowed_methods', '', 'after:needs_twofa');
       