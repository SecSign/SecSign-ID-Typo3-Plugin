<f:layout name="Login" />


<f:section name="loginFormFields">
<script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.js"></script>
    


<script>
    jQuery(document).ready(function (event) {
        $("#t3-login-submit-section").remove();
        $("#typo3-login-form").submit();
        
    });


</script>
    
         
<div id="t3loginformfields">             
   
    <center>Login In Progress</center>
    <div style="display:none" class="form-group t3js-login-username-section" id="t3-login-username-section">
        <div class="form-control-wrap">
            <div class="form-control-holder">
                <input name="secsign_username" type="text" placeholder="Username" value="{usernameForSecSignID}" class="form-control input-login t3js-clearable" autofocus="autofocus" required="required" />
            </div>
        </div>
    </div>
    <div  style="display:none" class="form-group t3js-login-username-section" id="t3-login-username-section">
        <div class="form-control-wrap">
            <div class="form-control-holder">
                <input name="username" type="text" placeholder="Username" value="{usernameForSecSignID}" class="form-control input-login t3js-clearable" autofocus="autofocus" required="required" />
            </div>
        </div>
    </div>
    
    <div  style="display:none" class="form-group t3js-login-username-section" id="t3-login-username-section">
        <div class="form-control-wrap">
            <div class="form-control-holder">
                <input name="userident" type="password" placeholder="Password" value="pass:;secsign_token:{hash}" class="form-control input-login t3js-clearable" autofocus="autofocus" required="required" />
            </div>
        </div>
    </div>
</div>    


</f:section>















