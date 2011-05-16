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
<div id="loginForm" class="wcmf_form">
  <fieldset>
    <legend>{translate text="Login"}</legend>
    <ol>
      <li>
        <label for="user">{translate text="Login"}</label>
        <input id="user" data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props='required:true, name:"user"'/>
      </li>
      <li>
        <label for="password">{translate text="Password"}</label>
        <input id="password" data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props='required:true, name:"password", type:"password"'/>
      </li>
      <li>
        <label for="remember_me">{translate text="Remember me"}</label>
        <input id="remember_me" data-dojo-type="dijit.form.CheckBox" data-dojo-props='name:"remember_me", value:"true", checked:false'/>
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