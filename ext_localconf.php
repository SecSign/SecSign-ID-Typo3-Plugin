<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'Secsign.' . $_EXTKEY,
	'Secsignfe',
	array(
		'Secsign' => 'login, logout, accesspass, cancel, auth, userlogout',
		
	),
	// non-cacheable actions
	array(
		'Secsign' => 'login, logout, accesspass, cancel, auth, userlogout',
		
	)
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService($_EXTKEY, 'auth', 'Secsign\Secsign\SecsignAuthService',
    array(
        'title' => 'FE/BE SecSign ID two-factor Authentication',
        'description' => 'Two-factor authentication with SecSign ID',
        'subtype' => 'authUserFE,authUserBE,getUserBE',
        'available' => TRUE,
        'priority' => 60,
        'quality' => 80,
        'os' => '',
        'exec' => '',
        'className' => 'Secsign\Secsign\SecsignAuthService'
    )
);
