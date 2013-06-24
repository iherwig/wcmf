CKEDITOR.editorConfig = function( config ) {
      config.language = appConfig.defaultLanguage;
      config.stylesSet = 'default:'+appConfig.pathPrefix+'/js/config/ckeditor_styles.js';
      config.forcePasteAsPlainText = true;
      config.resize_dir = 'vertical';
      config.stylesSet = [
          { name: 'Strong Emphasis', element: 'strong' },
          { name: 'Emphasis', element: 'em' }
      ];
      config.theme = 'default';
      config.toolbarStartupExpanded = false;
      config.uiColor = "#E0E0D6";
      config.toolbar_wcmf = [
          ['Maximize'],['Source'],['Cut','Copy','Paste'],['Image','Link','Unlink','Anchor'],
          ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
          ['Bold','Italic'],['Table','BulletedList','HorizontalRule','SpecialChar'],['About']
      ];
      config.toolbar = 'wcmf';
};
