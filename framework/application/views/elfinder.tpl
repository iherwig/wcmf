{configvalue key="libDir" section="cms" varname="libDir"}
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
  <!--
  <script src="{$elFinderBaseDir}js/i18n/elfinder.ru.js" type="text/javascript" charset="utf-8"></script>
  -->

  <style type="text/css">
    #close, #open, #dock, #undock {
      width: 100px;
      position:relative;
      display: -moz-inline-stack;
      display: inline-block;
      vertical-align: top;
      zoom: 1;
      *display: inline;
      margin:0 3px 3px 0;
      padding:1px 0;
      text-align:center;
      border:1px solid #ccc;
      background-color:#eee;
      margin:1em .5em;
      padding:.3em .7em;
      border-radius:5px;
      -moz-border-radius:5px;
      -webkit-border-radius:5px;
      cursor:pointer;
    }
  </style>

  <script type="text/javascript" charset="utf-8">
    $().ready(function() {
      var f = $('#finder').elfinder({
        url: 'main.php?controller=ElFinderController&sid={sessionid}',
        lang: '{configvalue key="language" section="cms"}',

        editorCallback: function(url) {
          if (window.console && window.console.log) {
            window.console.log(url);
          }
          var funcNum = window.location.search.replace(/^.*CKEditorFuncNum=(\d+).*$/, "$1");
          window.opener.CKEDITOR.tools.callFunction(funcNum, url);
          window.close();
        },
        closeOnEditorCallback : true
        // docked : true,
        // dialog : {
        // 	title : 'File manager',
        // 	height : 500
        // }
      })
      // window.console.log(f)
      $('#close,#open,#dock,#undock').click(function() {
        $('#finder').elfinder($(this).attr('id'));
      })
    })
  </script>
</head>
<body>
  <div id="open">open</div><div id="close">close</div><div id="dock">dock</div><div id="undock">undock</div>
  <div id="finder">finder</div>
</body>
</html>
