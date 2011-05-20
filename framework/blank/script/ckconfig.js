/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here.
  // See: http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html

  // config.autoGrow_maxHeight
  // config.autoGrow_minHeight
  // config.autoParagraph
  // config.autoUpdateElement
  // config.baseFloatZIndex
  // config.baseHref
  // config.basicEntities
  // config.blockedKeystrokes
  // config.bodyClass
  // config.bodyId
  // config.browserContextMenuOnCtrl
  // config.colorButton_backStyle
  // config.colorButton_colors
  // config.colorButton_enableMore
  // config.colorButton_foreStyle
  // config.contentsCss
  // config.contentsLangDirection
  // config.contentsLanguage
  // config.corePlugins
  // config.coreStyles_bold
  // config.coreStyles_italic
  // config.coreStyles_strike
  // config.coreStyles_subscript
  // config.coreStyles_superscript
  // config.coreStyles_underline
  // config.customConfig
  // config.defaultLanguage
  // config.devtools_styles
  // config.dialog_backgroundCoverColor
  // config.dialog_backgroundCoverOpacity
  // config.dialog_buttonsOrder
  // config.dialog_magnetDistance
  // config.dialog_startupFocusTab
  // config.disableNativeSpellChecker
  // config.disableNativeTableHandles
  // config.disableObjectResizing
  // config.disableReadonlyStyling
  // config.docType
  // config.editingBlock
  // config.emailProtection
  // config.enableTabKeyTools
  // config.enterMode
  // config.entities
  // config.entities_additional
  // config.entities_greek
  // config.entities_latin
  // config.entities_processNumerical
  config.extraPlugins = 'onchange';
  // config.filebrowserBrowseUrl - defined in wcmf/application/views/forms/ckeditor.tpl
  // config.filebrowserFlashBrowseUrl
  // config.filebrowserFlashUploadUrl
  // config.filebrowserImageBrowseLinkUrl
  // config.filebrowserImageBrowseUrl
  // config.filebrowserImageUploadUrl
  // config.filebrowserUploadUrl
  // config.filebrowserWindowFeatures
  config.filebrowserWindowHeight = 550;
  config.filebrowserWindowWidth = 830;
  // config.fillEmptyBlocks
  // config.find_highlight
  // config.font_defaultLabel
  // config.font_names
  // config.font_style
  // config.fontSize_defaultLabel
  // config.fontSize_sizes
  // config.fontSize_style
  // config.forceEnterMode
  config.forcePasteAsPlainText = true;
  // config.forceSimpleAmpersand
  // config.format_address
  // config.format_div
  // config.format_h1
  // config.format_h2
  // config.format_h3
  // config.format_h4
  // config.format_h5
  // config.format_h6
  // config.format_p
  // config.format_pre
  // config.format_tags
  // config.fullPage
  // config.height
  // config.htmlEncodeOutput
  // config.ignoreEmptyParagraph
  // config.image_previewText
  // config.image_removeLinkByEmptyURL
  // config.indentClasses
  // config.indentOffset
  // config.indentUnit
  // config.jqueryOverrideVal
  // config.justifyClasses
  // config.keystrokes
  // config.language
  // config.menu_groups
  // config.pasteFromWordCleanupFile
  // config.pasteFromWordNumberedHeadingToList
  // config.pasteFromWordPromptCleanup
  // config.pasteFromWordRemoveFontStyles
  // config.pasteFromWordRemoveStyles
  // config.plugins
  // config.protectedSource
  // config.readOnly
  // config.removeDialogTabs
  // config.removeFormatAttributes
  // config.removeFormatTags
  // config.removePlugins
  config.resize_dir = 'vertical';
  // config.resize_enabled
  // config.resize_maxHeight
  // config.resize_maxWidth
  // config.resize_minHeight
  // config.resize_minWidth
  // config.scayt_autoStartup
  // config.scayt_contextCommands
  // config.scayt_contextMenuItemsOrder
  // config.scayt_customDictionaryIds
  // config.scayt_customerid
  // config.scayt_maxSuggestions
  // config.scayt_moreSuggestions
  // config.scayt_sLang
  // config.scayt_srcUrl
  // config.scayt_uiTabs
  // config.scayt_userDictionaryName
  // config.sharedSpaces
  // config.shiftEnterMode
  config.skin = 'v2'; // config.skin = 'myskin,/customstuff/myskin/';
  // config.smiley_columns
  // config.smiley_descriptions
  // config.smiley_images
  // config.smiley_path
  // config.specialChars
  // config.startupFocus
  // config.startupMode
  // config.startupOutlineBlocks
  // config.startupShowBorders
  // config.stylesheetParser_skipSelectors
  // config.stylesheetParser_validSelectors
  // config.stylesSet - defined in wcmf/application/views/forms/ckeditor.tpl
  // config.tabIndex
  // config.tabSpaces
  // config.templates_files
  // config.templates_replaceContent
  config.theme = 'default';
  // config.toolbar
  // config.toolbar_Basic
  // config.toolbar_Full
  // config.toolbarCanCollapse
  // config.toolbarGroupCycling
  // config.toolbarLocation
  config.toolbarStartupExpanded = false;
  config.uiColor = "#E0E0D6";
  // config.undoStackSize
  // config.useComputedState
  config.width = 410;

  config.toolbar_wcmf =
  [
    ['Maximize'],['Source'],['Cut','Copy','Paste'],['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],'/',
    ['Bold','Italic'],['Image','Link','Unlink','Anchor'],['Table','BulletedList','HorizontalRule','SpecialChar'],['About']
  ];
};
