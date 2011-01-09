{extends file="lib:application/views/base.tpl"}

{block name=head append}
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

{block name=content}
<div class="contentblock">
  <fieldset>
    <legend>{translate text="Login"}</legend>
    <ol>
      <li>
        <label for="user">{translate text="Login"}</label>
        <input type="text" id="user" name="user" required="true" dojoType="dijit.form.ValidationTextBox"/>
      </li>
      <li>
        <label for="password">{translate text="Password"}</label>
        <input type="text" id="password" name="password" required="true" dojoType="dijit.form.ValidationTextBox"/>
      </li>
      <li>
        <label for="remember_me">{translate text="Remember me"}</label>
        <input id="remember_me" name="remember_me" dojoType="dijit.form.CheckBox" value="1" checked="false">
      </li>
    </ol>
  </fieldset>
  <p>  
    <button dojoType="dijit.form.Button" type="button">{translate text="Log in"}
      <script type="dojo/method" event="onClick" args="evt">wcmf.Action.login();</script>
    </button>
  </p>
</div>
{/block}