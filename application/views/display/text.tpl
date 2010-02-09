{assign var="attributes" value=$attributes|default:'class="txtdefault"'}
<span {$attributes}>{$value|truncate:50:"...":true|default:"..."}</span>
