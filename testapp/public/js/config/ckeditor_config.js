CKEDITOR.editorConfig = function( config ) {
	config.language = appConfig.defaultLanguage;
        config.stylesSet = 'default:'+appConfig.pathPrefix+'/js/config/ckeditor_styles.js';
};
