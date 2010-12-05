{extends file="lib:application/views/main.tpl"}

{block name=head append}
<script type="text/javascript">
$(document).ready(function() {
  $(document).bind('keyup', 'return', function() {
    submitAction('dologin');
  });
});
</script>
{/block}

{block name=metaNavigation}{/block}
{block name=contentNavigation}{/block}

{block name=content}
<div class="contentblock">
  <span class="spacer"></span>
  <span class="left">{translate text="Login"}</span>
  <span class="right">{$formUtil->getInputControl("user", "text", "", true)}</span>
  <span class="left">{translate text="Password"}</span>
  <span class="right">{$formUtil->getInputControl("password", "password", "", true)}</span>
  <span class="spacer"></span>
  <span class="left">{translate text="Remember me"}</span>
  <span class="right">{$formUtil->getInputControl("remember_me", "checkbox[class='check']#fix:1[ ]", "", true)}</span>
  <span class="spacer"></span>
  <span class="left">&nbsp;</span>
  <span class="right"><a href="javascript:submitAction('dologin');">{translate text="Log in"}</a></span>
</div>
{/block}