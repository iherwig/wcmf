// get path of directory ckeditor
var basePath = CKEDITOR.basePath;
basePath = basePath.substr(0, basePath.indexOf("ckeditor/"));

// load external plugins
(function() {
   CKEDITOR.plugins.addExternal('find', basePath+'ckeditor-plugins/find/', 'plugin.js');
   CKEDITOR.plugins.addExternal('mediaembed', basePath+'ckeditor-plugins/mediaembed/', 'plugin.js');
})();

CKEDITOR.editorConfig = function( config ) {
      config.language = appConfig.uiLanguage;
      config.stylesSet = 'default:'+appConfig.pathPrefix+'/js/config/ckeditor_styles.js';
      config.forcePasteAsPlainText = true;
      config.resize_dir = 'vertical';
      config.stylesSet = [
          { name: 'Strong Emphasis', element: 'strong' },
          { name: 'Emphasis', element: 'em' }
      ];
      config.theme = 'default';
      config.extraPlugins = 'find,mediaembed';
      config.toolbarStartupExpanded = false;
      config.toolbarCanCollapse = true;
      config.uiColor = "#E0E0D6";
      config.toolbar_wcmf = [
          ['Maximize'],['Source'],['Cut','Copy','Paste'],['Undo','Redo','Find'],
          ['Image','MediaEmbed','Link','Unlink','Anchor'],
          ['Bold','Italic','RemoveFormat'],['Table','BulletedList','HorizontalRule','SpecialChar'],['Format','Styles'],['About']
      ];
      config.toolbar = 'wcmf';
};
