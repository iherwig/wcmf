<form name="{$formName|default:""}" action="main.php" enctype="multipart/form-data" method="post" target="{$target|default:""}" onsubmit="{$onsubmit|default:""}">
<input type="hidden" name="controller" value="{$_controller}" />
<input type="hidden" name="context" value="{$_context}" />
<input type="hidden" name="usr_action" value="{$_action}" />
<input type="hidden" name="sid" value="{$sid}" />
<input type="hidden" name="oid" value="{$oid}" />
<input type="hidden" name="poid" value="{$poid}" />
<input type="hidden" name="newtype" value="" />
<input type="hidden" name="newrole" value="" />
<input type="hidden" name="deleteoids" value="" />

<input type="hidden" name="old_controller" value="{$_controller}" />
<input type="hidden" name="old_context" value="{$_context}" />
<input type="hidden" name="old_usr_action" value="{$_action}" />
<input type="hidden" name="old_response_format" value="{$_responseFormat}" />
<input type="hidden" name="old_oid" value="{$oid}" />

<input type="hidden" name="sortoid" value="" />
<input type="hidden" name="prevoid" value="" />
<input type="hidden" name="nextoid" value="" />

<input type="hidden" name="targetoid" value="" />
<input type="hidden" name="associateoids" value="" />
<input type="hidden" name="rootType" value="{$rootType}" />
