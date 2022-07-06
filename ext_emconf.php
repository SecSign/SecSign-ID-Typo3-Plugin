<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "secsign"
 *
 * Auto generated by Extension Builder 2014-12-30
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'SecSignID',
	'description' => 'This extension allows users to authenticate using 2FA by SecSign ID, FIDO, TOTP or MailOTP.',
	'category' => 'plugin',
	'author' => 'SecSign Technologies Inc.',
	'author_email' => 'support@secsign.com',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'version' => '2.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '8.0.0-10.9.99'
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);