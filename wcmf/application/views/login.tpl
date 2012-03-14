{extends file="lib:application/views/base.tpl"}

{block name=script append}
<script type="text/javascript">
{literal}
  dojo.addOnLoad(function() {
    dojo.connect(null, "onkeyup", function(e) {
      if (e.keyCode == dojo.keys.ENTER) {
        wcmf.Action.login();
      }
    });
  });
{/literal}
</script>
{/block}

{block name=center}
<form class="well form-horizontal" id="loginForm">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="login">{translate text="Login name"}</label>
            <div class="controls">
                <input id="login" data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props='required:true, name:"user"' placeholder="{translate text="Login name"}">
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="login">{translate text="Password"}</label>
            <div class="controls">
                <input id="password" data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props='required:true, name:"password", type:"password"' placeholder="{translate text="Password"}">
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="remember_me"></label>
            <div class="controls">
                <label class="checkbox">
                    <input id="remember_me" type="checkbox" value="true"> {translate text="Remember me"}
                </label>
            </div>
        </div>

        <div class="controls">
            <button class="btn btn-primary" onclick="wcmf.Action.login(); return false;">{translate text="Log in"}</button>
        </div>
    </fieldset>

</form>
{/block}