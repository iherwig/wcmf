<input
  {$attributes}
  data-dojo-type="dijit.form.DateTextBox"
  data-dojo-props='
    type:"text",
    name:"{$name}",
    value:"{$value}"
    {if !$enabled}
      , disabled:true
    {/if}
  '
/>


{*
dojo.addOnLoad(function()
{
  dojo.declare(&quot;OracleDateTextBox&quot;, dijit.form.DateTextBox,
  {
    oracleFormat:
    {
      selector: 'date', datePattern: 'dd-MMM-yyyy', locale: 'en-us'
    },
    value: &quot;&quot;, // prevent parser from trying to convert to Date object
    postMixInProperties: function()
    {
      // change value string to Date object
      this.inherited(arguments);
      // convert value to Date object
      this.value = dojo.date.locale.parse(this.value, this.oracleFormat);
    },
    // To write back to the server in Oracle format, override the serialize method:
    serialize: function(dateObject, options)
    {
      return dojo.date.locale.format(dateObject, this.oracleFormat).toUpperCase();
    }
  }
  );
  function showServerValue()
  {
    dojo.byId('toServerValue').value=document.getElementsByName('oracle')[0].value;
  }
  new OracleDateTextBox(
  {
    value: &quot;31-DEC-2009&quot;,
    name: &quot;oracle&quot;,
    onChange: function(v)
    {
      setTimeout(showServerValue, 0)
    }
  }
  , &quot;oracle&quot;);
  showServerValue();
}
);
*}