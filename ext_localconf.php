<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService($_EXTKEY, 'auth', 'SecSign\Secsign\Services\SecsignAuthService',
    array(
        'title' => 'SecSign ID two-factor Authentication',
        'description' => 'Two-factor authentication with SecSign ID',
        'subtype' => 'authUserBE,getUserBE,authUserFE',
        'available' => TRUE,
        'priority' => 100,
        'quality' => 100,
        'os' => '',
        'exec' => '',
        'className' => 'SecSign\Secsign\Services\SecsignAuthService'
    )
);


//////// BACKEND ////////////



//////// FRONTEND ///////////////

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
    '
        page.config.contentObjectExceptionHandler = 0


        plugin.tx_secsignfe_pi1 = USER_INT
        plugin.tx_secsignfe_pi1 {
            userFunc = SecSign\Secsign\Controller\LoginController->main

            # Storage
            storagePid = {$styles.content.loginform.pid}
            recursive = {$styles.content.loginform.recursive}
        }

        lib.contentElement {
            templateRootPaths.1000 = EXT:secsign/Resources/Private/Templates/
        }

        # Setting "felogin" plugin TypoScript
        tt_content.login =< lib.contentElement
        tt_content.login {
            templateName = Generic
            variables {
                content =< plugin.tx_secsignfe_pi1
            }
        }
    '
);

// Add login to new content element wizard
if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
        mod.wizards.newContentElement.wizardItems.forms {
            elements.login {
                iconIdentifier = secsignfe-plugin-loginplugin
                title = SecSign Login
                description = Login For FE Users. Secured by SecSign 2FA.
                tt_content_defValues {
                    CType = login
                }
            }
            show :=addToList(login)
        }
    ');
}

/**
 * load icon for content element
 */
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);

$iconRegistry->registerIcon(
        'secsignfe-plugin-loginplugin',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        ['source' => 'EXT:secsign/Resources/Public/images/secsign_logo.png']
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']
   ['SecSign\\Secsign\\UserFunctions\\FormEngine\\SecSignIDEval'] = '';