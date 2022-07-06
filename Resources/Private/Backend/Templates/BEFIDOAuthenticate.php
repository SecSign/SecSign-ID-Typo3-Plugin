<f:layout name="Login" /> 
<f:section name="loginFormFields">       
    
</form>
    <link rel="stylesheet" href="/typo3conf/ext/secsign/Resources/Public/css/secsignid_layout.css"/> 
     <style>


        h1 {
            margin-bottom: 10px;
            font-size: 20px;
            text-align: center;
        }

        h2 {
            margin-top: 15px;
            margin-bottom: 25px;
            font-size: 16px;
            text-align: center;
        }

        p {
            text-align: center;
        }


    </style>
    <div id="dialog" title="Basic dialog">
        <div id="content">
                    <div class="login-form-container">
                        <div>
                            <h1><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.fido.heading"/></h1>
                           

                            <div id="fido-authenticate" style="">
                                <h2><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.fido.auth.subheading"/></h2>
                                <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.fido.auth.text"/></p>

                                <div class="secsignid-error" style="display:none"></div>
                                <form id="fido-authenticate-form" class="login-form">
                                    <div>
                                        <button  id="start-fido-auth" class="submit-button" type="button">
                                            <span class="submit-button-text"><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.fido.auth.button"/></span>
                                        </button>
                                    </div>

                                    
                                </form>
 

                                <form id="fido-authenticate-finish-form" method="POST">
                                    <input name="secsign_method" type="hidden" value="fidoFinishAuthenticate">
                                    <input type="hidden" name="secsigniduserid" value="<f:format.raw>{secsignid}</f:format.raw>" />
                                    <input type="hidden" name="secsign_username" value="<f:format.raw>{secsign_username}</f:format.raw>" />
                                    <input id="input-authenticate-credentialId" name="credentialId" type="hidden">
                                    <input id="input-authenticate-clientDataJson" name="clientDataJson" type="hidden">
                                    <input id="input-authenticate-authenticatorData" name="authenticatorData" type="hidden">
                                    <input id="input-authenticate-signature" name="signature" type="hidden">
                                    <input id="input-authenticate-userHandle" name="userHandle" type="hidden">
                                    <input type="hidden" id="returnUrl" name="returnUrl" value="<f:format.raw>{RETURN_URL}</f:format.raw>">
                                </form>
                            </div>

                            <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.fido.hint"/></p>

                        </div>

                        <a target="_blank" href="https://secsign.com">
                                <div id="footer-image"></div>
                        </a>
                    </div>
                </div>
            </div>
            
            <script>
                $(document).ready(function ($) {
                   
                     
                    $(".typo3-login-wrap").css("max-width","600px");
                    $("#t3-login-submit-section").remove();
                    $(".typo3-login").css("position","inherit");
                    
                    
                    
                   var requestOptionsText='<f:format.raw>{fromServer}</f:format.raw>';
                   
                   console.log(requestOptionsText);
                    var txt = document.createElement('textarea');
                    txt.innerHTML = requestOptionsText;
                    requestOptionsText=txt.value;
                    console.log(requestOptionsText);
                   var requestOptionsJson=JSON.parse(requestOptionsText);
                    
                   $("#start-fido-auth").click(function ()
                   {
                        $(".secsignid-error").hide();
                        $("#start-fido-auth").attr("disabled",true);
                        var __origDefine = define;
                        define = null;
                        (function (r) { if (typeof exports === "object" && typeof module !== "undefined") { module.exports = r() } else if (typeof define === "function" && define.amd) { define([], r) } else { var e; if (typeof window !== "undefined") { e = window } else if (typeof global !== "undefined") { e = global } else if (typeof self !== "undefined") { e = self } else { e = this } e.base64js = r() } })(function () { var r, e, n; return function () { function r(e, n, t) { function o(i, a) { if (!n[i]) { if (!e[i]) { var u = typeof require == "function" && require; if (!a && u) return u(i, !0); if (f) return f(i, !0); var d = new Error("Cannot find module '" + i + "'"); throw d.code = "MODULE_NOT_FOUND", d } var c = n[i] = { exports: {} }; e[i][0].call(c.exports, function (r) { var n = e[i][1][r]; return o(n ? n : r) }, c, c.exports, r, e, n, t) } return n[i].exports } var f = typeof require == "function" && require; for (var i = 0; i < t.length; i++)o(t[i]); return o } return r }()({ "/": [function (r, e, n) { "use strict"; n.byteLength = c; n.toByteArray = v; n.fromByteArray = s; var t = []; var o = []; var f = typeof Uint8Array !== "undefined" ? Uint8Array : Array; var i = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/"; for (var a = 0, u = i.length; a < u; ++a) { t[a] = i[a]; o[i.charCodeAt(a)] = a } o["-".charCodeAt(0)] = 62; o["_".charCodeAt(0)] = 63; function d(r) { var e = r.length; if (e % 4 > 0) { throw new Error("Invalid string. Length must be a multiple of 4") } return r[e - 2] === "=" ? 2 : r[e - 1] === "=" ? 1 : 0 } function c(r) { return r.length * 3 / 4 - d(r) } function v(r) { var e, n, t, i, a; var u = r.length; i = d(r); a = new f(u * 3 / 4 - i); n = i > 0 ? u - 4 : u; var c = 0; for (e = 0; e < n; e += 4) { t = o[r.charCodeAt(e)] << 18 | o[r.charCodeAt(e + 1)] << 12 | o[r.charCodeAt(e + 2)] << 6 | o[r.charCodeAt(e + 3)]; a[c++] = t >> 16 & 255; a[c++] = t >> 8 & 255; a[c++] = t & 255 } if (i === 2) { t = o[r.charCodeAt(e)] << 2 | o[r.charCodeAt(e + 1)] >> 4; a[c++] = t & 255 } else if (i === 1) { t = o[r.charCodeAt(e)] << 10 | o[r.charCodeAt(e + 1)] << 4 | o[r.charCodeAt(e + 2)] >> 2; a[c++] = t >> 8 & 255; a[c++] = t & 255 } return a } function l(r) { return t[r >> 18 & 63] + t[r >> 12 & 63] + t[r >> 6 & 63] + t[r & 63] } function h(r, e, n) { var t; var o = []; for (var f = e; f < n; f += 3) { t = (r[f] << 16 & 16711680) + (r[f + 1] << 8 & 65280) + (r[f + 2] & 255); o.push(l(t)) } return o.join("") } function s(r) { var e; var n = r.length; var o = n % 3; var f = ""; var i = []; var a = 16383; for (var u = 0, d = n - o; u < d; u += a) { i.push(h(r, u, u + a > d ? d : u + a)) } if (o === 1) { e = r[n - 1]; f += t[e >> 2]; f += t[e << 4 & 63]; f += "==" } else if (o === 2) { e = (r[n - 2] << 8) + r[n - 1]; f += t[e >> 10]; f += t[e >> 4 & 63]; f += t[e << 2 & 63]; f += "=" } i.push(f); return i.join("") } }, {}] }, {}, [])("/") });
                   
                        authenticateCredential($.extend( true, {}, requestOptionsJson ).requestOptions);
                        define = __origDefine;
                   });
                   
                   
                   
                   function authenticateCredential(requestOptions) {
                        window.focus();
                        convertBase64ToBytesInPublicKeyCredentialRequestOptions(requestOptions);
                        navigator.credentials.get(requestOptions)
                            .then(function (newCredentialInfo) {
                                var credentialId = newCredentialInfo.id; //base64
                                var clientDataJSON = arrayBufferToStr(newCredentialInfo.response.clientDataJSON);
                                var authenticatorData = newCredentialInfo.response.authenticatorData;
                                var authenticatorDataByteArray = new Uint8Array(authenticatorData);
                                var authenticatorDataBase64 = base64js.fromByteArray(authenticatorDataByteArray);
                                var signature = newCredentialInfo.response.signature;
                                var signatureByteArray = new Uint8Array(signature);
                                var signatureBase64 = base64js.fromByteArray(signatureByteArray);
                                var userHandle = newCredentialInfo.response.userHandle;
                                var userHandleByteArray = new Uint8Array(userHandle);
                                var userHandleBase64 = base64js.fromByteArray(userHandleByteArray);

                                $("#input-authenticate-credentialId").val(credentialId);
                                $("#input-authenticate-clientDataJson").val(clientDataJSON);
                                $("#input-authenticate-authenticatorData").val(authenticatorDataBase64);
                                $("#input-authenticate-signature").val(signatureBase64);
                                $("#input-authenticate-userHandle").val(userHandleBase64);
                                $("#fido-authenticate-finish-form").submit();
                            }).catch(function (err) {
                                console.error(err);
                                $("#start-fido-auth").removeAttr("disabled");
                                $(".secsignid-error").text(""+err);
                                $(".secsignid-error").show();
                            });
                    }
                    
                    function convertBase64ToBytesInPublicKeyCredentialRequestOptions(options) {
                        options.publicKey.challenge = base64js.toByteArray(options.publicKey.challenge);
                        if (options.publicKey.allowCredentials) {
                            options.publicKey.allowCredentials.forEach(function (item, index) {
                                item.id = base64js.toByteArray(item.id);
                            });
                        }
                    }

                    function arrayBufferToStr(buf) {
                        return String.fromCharCode.apply(null, new Uint8Array(buf));
                    }

        }); 

        </script>
    
    <f:if condition="<f:format.raw>{switchAllowed}</f:format.raw>"> 
        <script>
            $(document).ready(function (event) {
                
                $("#secsignid-switch-link").insertAfter($("#fido-authenticate-form"));
                $("#login-form").append("<input type='hidden' name='secsignidswitchallowed' value='true'/>");
                $("#change").click(function ()
                {
                    $("#secsignid-switch-form").submit();
                });
            });
            
            
        </script>
        <form style="display:none" class="login-form" name="accesspassAuthSecsign" method="post" id="secsignid-switch-form">
            <input type="hidden" name="secsign_method" value="secsign_changeMethod"/>

            <input type="hidden" name="secsign_changeFrom" value="fido"/>

            <input type="hidden" name="secsigniduserid" value="<f:format.raw>{secsignid}</f:format.raw>"/>
            <input type="hidden" name="returnURL" value="<f:format.raw>{RETURN_URL}</f:format.raw>" />
            <input type="hidden" name="secsign_username" value="<f:format.raw>{secsign_username}</f:format.raw>" />



        </form>
        <center id="secsignid-switch-link">
                <a id="change" class="link">
                    Switch Method
                </a>
        </center>
    </f:if>
</f:section>