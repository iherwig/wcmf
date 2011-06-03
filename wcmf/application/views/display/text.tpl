{assign var="attributes" value=$attributes|default:'class="txtdefault"'}
<span {$attributes}>{$value|strip_tags:true|truncate:50:"...":true|default:"..."}</span>
