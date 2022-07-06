<f:layout name="Login" />

      
           
         
<f:section name="loginFormFields">   
         
</form>
<link rel="stylesheet" href="/typo3conf/ext/secsign/Resources/Public/css/secsignid_layout.css"/>
            <div id="dialog" title="Basic dialog">
                <div id="content">
                    <div class="login-form-container">
                        <div id="login-form" class="login-form">
                            <form id="secsignid-qrcode-form" method="post">
                                    <input type="hidden" name="qrcodebase64" id="qrcodebase64" value="{qrcodebase64}"/>
                                    <input type="hidden" name="restoreurl" id="createurl" value="{restoreurl}"/>
                                    <input type="hidden" name="secsign_method" id="secsign_checkRestoreQRCode" value="secsign_checkRestoreQRCode"/>
                                    <input type="hidden" name="secsignid" id="secsignid" value="{secsignid}"/>
                                    <input type="hidden" name="email" id="email" value="{email}"/>
                                    <input type="hidden" name="secsign_username" id="secsignid" value="{secsign_username}"/>
                            </form>
                            <div id="secsign-qrcode" style="display: block;">
                                <h3><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.activate.twofa.heading" /></h3>
                                <h4 id="qrcode-secsign-id">{secsignid}</h4>
                                <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.activate.twofa.start" /></p>

                                <p class="secsign-step"><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.activate.twofa.1" /></p>
                                <div class="secsign-app-button-container">
                                                                <a href="https://itunes.apple.com/us/app/secsign-id/id581467871?mt=8" target="_blank" class="secsign-app-button">
                                        <svg class="secsign-app-button-icon" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid" viewBox="0 0 20 20">
                                            <path d="M17.5640259,13.8623047c-0.4133301,0.9155273-0.6115723,1.3251343-1.1437988,2.1346436c-0.7424927,1.1303711-1.7894897,2.5380249-3.086853,2.5500488c-1.1524048,0.0109253-1.4483032-0.749939-3.0129395-0.741333c-1.5640259,0.008606-1.8909302,0.755127-3.0438843,0.7442017c-1.296814-0.0120239-2.2891235-1.2833252-3.0321655-2.4136963c-2.0770874-3.1607666-2.2941895-6.8709106-1.0131836-8.8428955c0.9106445-1.4013062,2.3466187-2.2217407,3.6970215-2.2217407c1.375,0,2.239502,0.7539673,3.3761597,0.7539673c1.1028442,0,1.7749023-0.755127,3.3641357-0.755127c1.201416,0,2.4744263,0.6542969,3.3816528,1.7846069C14.0778809,8.4837646,14.5608521,12.7279663,17.5640259,13.8623047z M12.4625244,3.8076782c0.5775146-0.741333,1.0163574-1.7880859,0.8571167-2.857666c-0.9436035,0.0653076-2.0470581,0.6651611-2.6912842,1.4477539	C10.0437012,3.107605,9.56073,4.1605835,9.7486572,5.1849365C10.7787476,5.2164917,11.8443604,4.6011963,12.4625244,3.8076782z">
                                            </path>
                                        </svg>
                                        <div style="margin:auto;">iOS App Store</div>
                                    </a>
                                                                                            <a href="https://play.google.com/store/apps/details?id=com.secsign.secsignid&amp;pcampaignid=MKT-Other-global-all-co-prtnr-py-PartBadge-Mar2515-1" target="_blank" class="secsign-app-button">
                                        <svg class="secsign-app-button-icon" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid" viewBox="0 0 20 20">
                                            <path d="M4.942627,18.0508423l7.6660156-4.3273926l-1.6452026-1.8234253L4.942627,18.0508423z M2.1422119,2.1231079C2.0543823,2.281311,2,2.4631958,2,2.664917v15.1259766c0,0.2799683,0.1046143,0.5202026,0.2631226,0.710144l7.6265259-7.7912598L2.1422119,2.1231079z M17.4795532,9.4819336l-2.6724854-1.508606l-2.72229,2.7811279l1.9516602,2.1630249l3.4431152-1.9436035C17.7927856,10.8155518,17.9656372,10.5287476,18,10.2279053C17.9656372,9.927063,17.7927856,9.6402588,17.4795532,9.4819336zM13.3649292,7.1592407L4.1452026,1.954834l6.8656616,7.609314L13.3649292,7.1592407z">
                                            </path>
                                        </svg>
                                        Google Play Store
                                    </a>
                                                                                            <a href="https://secsign.com/downloads/plugins/SecSignID-Windows-Installer.msi" target="_blank" class="secsign-app-button">
                                        <svg class="secsign-app-button-icon" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid" viewBox="0 0 20 20">
                                            <path d="M9.5,3.2410278V9.5H18V2L9.5,3.2410278z M2,9.5h6.5V3.3870239L2,4.3359985V9.5z M9.5,16.7589722L18,18v-7.5H9.5V16.7589722z M2,15.6640015l6.5,0.9489746V10.5H2V15.6640015z">
                                            </path>
                                        </svg>
                                        Windows Desktop Installer
                                    </a>
                                                                                            <a href="https://apps.apple.com/us/app/secsign-id/id1038409057?mt=12" target="_blank" class="secsign-app-button">
                                        <svg class="secsign-app-button-icon" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid" viewBox="0 0 20 20">
                                            <path d="M17.5640259,13.8623047c-0.4133301,0.9155273-0.6115723,1.3251343-1.1437988,2.1346436c-0.7424927,1.1303711-1.7894897,2.5380249-3.086853,2.5500488c-1.1524048,0.0109253-1.4483032-0.749939-3.0129395-0.741333c-1.5640259,0.008606-1.8909302,0.755127-3.0438843,0.7442017c-1.296814-0.0120239-2.2891235-1.2833252-3.0321655-2.4136963c-2.0770874-3.1607666-2.2941895-6.8709106-1.0131836-8.8428955c0.9106445-1.4013062,2.3466187-2.2217407,3.6970215-2.2217407c1.375,0,2.239502,0.7539673,3.3761597,0.7539673c1.1028442,0,1.7749023-0.755127,3.3641357-0.755127c1.201416,0,2.4744263,0.6542969,3.3816528,1.7846069C14.0778809,8.4837646,14.5608521,12.7279663,17.5640259,13.8623047z M12.4625244,3.8076782c0.5775146-0.741333,1.0163574-1.7880859,0.8571167-2.857666c-0.9436035,0.0653076-2.0470581,0.6651611-2.6912842,1.4477539	C10.0437012,3.107605,9.56073,4.1605835,9.7486572,5.1849365C10.7787476,5.2164917,11.8443604,4.6011963,12.4625244,3.8076782z">
                                            </path>
                                        </svg>
                                        MacOS App Store
                                    </a>
                                                            </div>

                                <div class="divider"></div>

                                <p class="secsign-step"><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.activate.twofa.2" /></p>
                                <div class="center">
                                    <div id="qrcode-mail-container" class="center-item">
                                        <p></p>
                                        <div class="login-form-field-input">
                                            <input id="qrcode-mail" type="text" disabled="" value="{email}">
                                        </div>
                                    </div>
                                    <div class="center-item">
                                        <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.activate.twofa.qr" /></p>
                                    </div>
                                    <div class="center-item">
                                        <img id="qrcode-image" class="qrcode" src="data:image/jpg;base64,{qrcodebase64}">
                                    </div>
                                    <div class="center-item">
                                        <a id="qrcode-desktop-url" class="link" target="_blank" href="{restoreurl}">
                                            <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.activate.twofa.qr.desktop" />
                                        </a>
                                    </div>
                                </div>

                                <div class="divider"></div>

                                <div id="secsign-qr-code-last-step-create" style="display: none;">
                                    <p class="secsign-step"><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.activate.twofa.qr.continue" /></p>
                                </div>

                                <div id="secsign-qr-code-last-step-restore">
                                    <p class="secsign-step"><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.activate.twofa.3" /></p>
                                    <div class="attention-container">
                                        <div class="attention"></div>
                                        <p><strong><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.activate.twofa.3.important" /></strong>
                                            <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.activate.twofa.3.spam" /></p>
                                    </div>

                                    <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.activate.twofa.3.end" />
                                </div>

                                                        

                                 
                            </div>
                                
                        </div>
                    </div>
                </div>
                
            </div>
            
            <script>
                $(document).ready(function (event) {
                    $(".typo3-login-wrap").css("max-width","600px");
                    $("#t3-login-submit-section").remove();
                    $(".typo3-login").css("position","inherit");
                    
                    var timeoutObject=window.setTimeout(function()
                    {
                        $("#secsignid-qrcode-form").submit();
                    }, 3000);
                    $("#secsignid-cancelbutton").click(function( )
                    {
                        clearTimeout(timeoutObject);
                    });
                });
            </script>
</f:section >

