{assign var="attributes" value=$attributes|default:'class="linkdefault"'}
<a href="{$value}" target="_blank">{linktext url=$value|truncate:50:"...":true|default:"..."}</a>
