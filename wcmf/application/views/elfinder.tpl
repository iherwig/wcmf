{configvalue key="libDir" section="cms" varname="libDir"}
{configvalue key="language" section="cms" varname="lang"}
{$elFinderBaseDir=$libDir|cat:"3rdparty/elfinder/"}

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
  "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{translate text="Media Pool"}</title>

    <!-- jQuery and jQuery UI (REQUIRED) -->
    <link rel="stylesheet" type="text/css" media="screen" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/themes/smoothness/jquery-ui.css">
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/jquery-ui.min.js"></script>

    <!-- elFinder CSS (REQUIRED) -->
    <link rel="stylesheet" type="text/css" media="screen" href="{$elFinderBaseDir}css/elfinder.min.css">

    <!-- Mac OS X Finder style for jQuery UI smoothness theme (OPTIONAL) -->
    <link rel="stylesheet" type="text/css" media="screen" href="{$elFinderBaseDir}css/theme.css">

    <!-- elFinder JS (REQUIRED) -->
    <script type="text/javascript" src="{$elFinderBaseDir}js/elfinder.min.js"></script>

    <!-- elFinder translation (OPTIONAL) -->
    <script type="text/javascript" src="{$elFinderBaseDir}js/i18n/elfinder.{$lang}.js"></script>

    <!-- elFinder initialization (REQUIRED) -->
    <script type="text/javascript" charset="utf-8">
      $().ready(function() {
        var elf = $('#elfinder').elfinder({
          lang: '{$lang}',
          url: 'main.php?controller=ElFinderController&sid={sessionid}',
          cutURL: '{$rootUrl}',
          getFileCallback: function(url) {
            // prepend media path
            url = '{$rootPath}'+url;
            if (window.console && window.console.log) {
              window.console.log(url);
            }
            var fieldName = '{$fieldName}';
            // check for a widget with the id given in fieldName
            if (fieldName.length > 0 && window.opener) {
              var widget = window.opener.dijit.byId(fieldName);
              if (widget) {
                widget.set('value', url);
              }
            }
            // check for ckeditor
            if (window.opener.CKEDITOR) {
              var funcNum = window.location.search.replace(/^.*CKEditorFuncNum=(\d+).*$/, "$1");
              window.opener.CKEDITOR.tools.callFunction(funcNum, url);
            }
            window.close();
          },
          //closeOnEditorCallback: true,
          ui : ['toolbar'/*, 'places'*/, 'tree', 'path', 'stat'],
          width: 800,
          height: 400,
          cssClass: 'wcmfFinder',
          rememberLastDir: true
        }).elfinder('instance');
      });
    </script>
  </head>
  <body>
    <div id="elfinder">finder</div>
  </body>
</html>
