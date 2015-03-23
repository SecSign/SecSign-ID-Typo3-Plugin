SecSign ID Typo3 Plugin
===========================

Use SecSign ID two-factor authentication on your Typo3 site with an easy and highly secure user login using iOS or Android mobile devices.


SecSign ID - The mobile way to log into web sites

SecSign ID is an extension for real two-factor authentication (2FA) for Typo3 sites.
If you seek for more information about about two-factor authentication have a look at <https://www.secsign.com/two-factor-authentication/> 
or at the SecSign blog <https://www.secsign.com/two-factor-authentication-vs-two-step-verification/>.

## SecSign ID features:

* Quick and easy to use single sign-on with 2048 bit high security
* Eliminates password chaos and security concerns
* No mobile number, credit card or time-consuming registration required.
* No need for long cryptical passwords, time-consuming retyping of codes from SMS or reading of QR codes
* High security and strong cryptography on all levels

## Technical details (only for experts):

* Up to 2048 bit asymmetric private keys
* Brute force resistant private key storage (SafeKey mechanism)
* Private keys are never transmitted to authentication server
* High availability through redundant remote failover servers
* Multi-tier high security architecture with multiple firewalls and protocol filters.

More information at at <https://www.secsign.com/security-id/>.


**SecSign ID:**

1. Get the app for iPhone <https://itunes.apple.com/app/secsign/id581467871> or Android <https://play.google.com/store/apps/details?id=com.secsign.secsignid>
2. Choose a unique user short name
3. Choose a short PIN to secure your SecSign ID on your phone

That's it! You can now use your SecSign ID to sign in.


## Installation

**Prerequesites**


* A working Typo3 6.2 LTS Installation on PHP 5.3.7 or higher.
* Curl has to be activated. Open the Install-Tool and check the Parameter [SYS][curlUse] under the section "All Configuration".
* The extension file secsign.zip.


**Install the Plugin**

* Log into the Typo3 backend and click on "Admin Tools > Extension Manager" in the main menu.
* Click on the "Upload Extension" button, select the downloaded zip archive in the opening form and hit "Upload!".
* When the extension was successfully installed, you will see a green activated icon.


![SecSign User help](/Resources/Public/screenshots/install.png)


**Configuration**

The configuration panel lets you manage the extension behaviour for the Frontend and Backend login process.

* Log into the Typo3 backend and click on "Admin Tools > Extension Manager" in the main menu.
* Search for the SecSign ID Extension and click on the "Configure" icon in the "Actions" column.
* Under the front- and backend tab you are going to find the following options:


Frontend:

* Service name: The name of this web site as it shall be displayed on the user's smart phone.
* Pre-text: This is the text or HTML that is displayed above the login form.
* Post-text: This is the text or HTML that is displayed below the login form.
* Login Redirection Page: Select the page the user will be redirected to after a successful login. If empty, the user will return to the same page.
* Logout Redirection Page: Select the page the user will be redirected to after successfully ending their current session by logging out. If empty, the user will return to the same page.
* Show Greeting: Show or hide the simple greeting text.
* Show Name/Username: Displays the Typo3 name or SecSign ID after login.

Backend:

* Enable Backend: Use SecSign ID for backend authentication. JavaScript needs to be enabled in your browser.
* Help: Displays the Help page under Admin Tools – Secsign ID. Reload backend after change.
* Syslog: Writes all backend login errors to the syslog.


![SecSign User help](/Resources/Public/screenshots/config.png)


## Information and Tutorial:

More information is available at the Typo3 SecSign ID tutorial website at <https://www.secsign.com/typo3-tutorial/>.



For more detailed information about two-factor-authentication (2FA) or two-step-authentication please 
have a look at the SecSign blog entry <https://www.secsign.com/two-factor-authentication-vs-two-step-verification/>.
