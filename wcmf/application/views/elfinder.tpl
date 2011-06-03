{configvalue key="libDir" section="cms" varname="libDir"}
{configvalue key="language" section="cms" varname="lang"}
{$elFinderBaseDir=$libDir|cat:"3rdparty/elfinder/"}

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>{translate text="Media Pool"}</title>
  <link rel="stylesheet" href="{$elFinderBaseDir}js/ui-themes/base/ui.all.css" type="text/css" media="screen" title="no title" charset="utf-8">
  <link rel="stylesheet" href="{$elFinderBaseDir}css/elfinder.css" type="text/css" media="screen" title="no title" charset="utf-8">

  <script src="{$elFinderBaseDir}js/jquery-1.4.1.min.js" type="text/javascript" charset="utf-8"></script>
  <script src="{$elFinderBaseDir}js/jquery-ui-1.7.2.custom.min.js" type="text/javascript" charset="utf-8"></script>

  <script src="{$elFinderBaseDir}js/elfinder.min.js" type="text/javascript" charset="utf-8"></script>
  <script src="{$elFinderBaseDir}js/i18n/elfinder.{$lang}.js" type="text/javascript" charset="utf-8"></script>
  <script type="text/javascript" charset="utf-8">
    $().ready(function() {
      var f = $('#finder').elfinder({
        url: 'main.php?controller=ElFinderController&sid={sessionid}',
        lang: '{$lang}',
        cutURL: '{$rootUrl}',

        editorCallback: function(url) {
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
        closeOnEditorCallback: true,
        places: '',
        width: 800,
        height: 400,
        cssClass: 'wcmfFinder',
        rememberLastDir: true
      })
    })
  </script>
</head>
<body>
  <div id="finder">finder</div>
</body>
</html>
