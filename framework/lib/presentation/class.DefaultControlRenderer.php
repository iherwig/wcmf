<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
require_once(BASE."wcmf/lib/util/class.InifileParser.php");
require_once(BASE."wcmf/lib/util/class.URIUtil.php");
require_once(BASE."wcmf/lib/presentation/class.View.php");
require_once(BASE."wcmf/lib/presentation/class.Controller.php");
require_once(BASE."wcmf/lib/presentation/class.InternalLink.php");

/**
 * @class DefaultControlRenderer
 * @ingroup Presentation
 * @brief DefaultControlRenderer is responsible for rendering html input controls.
 * Each control is defined in a smarty template, which is configured by the appropriate entry
 * in the configuration section 'htmlform' (e.g. the text input control has the entry 'text').
 * The templates get a default set of variables assigned. Additional variables, needed only for
 * certain controls, are assigned in the appropriate configure method (e.g. 'configure_text').
 * which may be overridden by subclasses.
 *
 * New controls may be defined by defining the template, putting it into the configuration
 * section 'htmlform' and maybe implementing a configure method in a subclass of DefaultControlRenderer.
 * If a subclass is needed, the key 'ControlRenderer' in the configuration section 'implementation'
 * must point to it (don't forget to give the class definition in the 'classmapping' section).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultControlRenderer
{
  var $FCKeditorCodeAdded = 0;
  var $resourceBrowserCodeAdded = 0;
  var $view = null;

  /**
   * Get the maximum file size for uploads.
   * Returns the value given in config key 'maxFileSize' in section 'htmlform', default: 200000
   * @return The maximum file size in bytes
   */
  function getMaxFileSize()
  {
    $MAX_FILE_SIZE = 200000;

    // try to get default max file size
    $parser = InifileParser::getInstance();
    if (($maxFileSize = $parser->getValue('maxFileSize', 'htmlform')) === false)
      $maxFileSize = $MAX_FILE_SIZE;
    return $maxFileSize;
  }
  /**
   * Render a HTML control of given type using the appropriate smarty template (defined in the
   * config section 'htmlform'). The given parameters will be passed to the view. If additional
   * parameters are needed an configure method must be implemented (name: configure_type)
   * @param type The type of the input control (the template is selected based on the type)
   * @param enabled Indicates wether the input control should be enabled or not
   * @param name The name of the input control (usually the name attribute in the control tag)
   * @param value The value to display (this may be an array if multiple values are possible e.g. select control)
   * @param error The input validation error if existing
   * @param attributes An attribute string to be placed in the input control tag (defining its appearance)
   * @param listMap An assoziative array defining the possible values for list controls
   *        (e.g. select control) see FormUtil::getListMap
   * @param inputType The original control definition string
   * @return The HMTL definition string of the input control
   */
  function renderControl($type, $enabled, $name, $value, $error, $attributes, $listMap, $inputType)
  {
    $parser = InifileParser::getInstance();

    // build input control
    if ($view == null)
      $view = new View();
    else
      $view->clear_all_assign();

    // set default view parameters
    $view->setup();
    $view->assign('enabled', $enabled);
    $view->assign('name', $name);
    $view->assign('value', $value);
    $view->assign('error', $error);
    $view->assign('attributes', $attributes);
    $view->assign('listMap', $listMap);
    $view->assign('inputType', $inputType);

    // split attributes into array and assign it
    $attributeList = array();
    $attributeParts = preg_split("/[\s,;]+/", $attributes);
    foreach($attributeParts as $attribute)
    {
      if (strlen($attribute) > 0)
      {
        list($key, $value) = preg_split("/[=:]+/", $attribute);
        $key = trim(stripslashes($key));
        $value = trim(stripslashes($value));
        $attributeList[$key] = $value;
      }
    }
    $view->assign('attributeList', $attributeList);

    // set additional view parameters if needed
    $configureFunction = "configure_".$type;
    if (method_exists($this, $configureFunction))
      $this->$configureFunction($view);

    if ($viewTpl = $parser->getValue($type, 'htmlform') === false) {
      throw new ConfigurationException("Unknown input control '".$type."'");
    }

    $htmlString = $view->fetch(BASE.$parser->getValue($type, 'htmlform'));
    return $htmlString;
  }
  /**
   * Set additional parameters to the select view
   * @param view A reference to the control view
   */
  function configure_select(&$view)
  {
    // see if we have an async select box
    $inputType = $view->get_template_vars('inputType');
    if (preg_match("/^select.*?#async\:(.+)$/", $inputType, $matches))
    {
      $list = $matches[1];
      // get the entity type to list and an optional filter
      $parts = preg_split('/\|/', $list);
      $entityType = array_shift($parts);
      $filter = join('', $parts);
      $view->assign('entityType', $entityType);
      $view->assign('filter', $filter);
      $view->assign('obfuscator', Obfuscator::getInstance());

      // get the translated value
      $listMap = $view->get_template_vars('listMap');
      $value = $view->get_template_vars('value');
      $view->assign('translatedValue', $listMap[$value]);

      $view->assign('isAsync', true);
    }
    else
      $view->assign('isAsync', false);
  }
  /**
   * Set additional parameters to the file control view
   * @param view A reference to the control view
   */
  function configure_file(&$view)
  {
    $view->assign('maxFileSize', DefaultControlRenderer::getMaxFileSize());
  }
  /**
   * Set additional parameters to the fileex control view
   * @param view A reference to the control view
   */
  function configure_fileex(&$view)
  {
    $parser = InifileParser::getInstance();
    $view->assign('maxFileSize', DefaultControlRenderer::getMaxFileSize());
    $view->assign('fieldDelimiter', FormUtil::getInputFieldDelimiter());
    $view->assign('uploadDir', $parser->getValue('uploadDir', 'media'));
  }
  /**
   * Set additional parameters to the filebrowser view
   * @param view A reference to the control view
   */
  function configure_filebrowser(&$view)
  {
    $view->assign('resourceBrowserCodeAdded', $this->resourceBrowserCodeAdded);
    $view->assign('directory', dirname($view->get_template_vars('value')));
    $this->resourceBrowserCodeAdded = 1;
  }
  /**
   * Set additional parameters to the linkbrowser view
   * @param view A reference to the control view
   */
  function configure_linkbrowser(&$view)
  {
    $value = $view->get_template_vars('value');
    if (InternalLink::isLink($value))
      $view->assign('isExternal', false);
    else
      $view->assign('isExternal', true);
    $view->assign('resourceBrowserCodeAdded', $this->resourceBrowserCodeAdded);
    $this->resourceBrowserCodeAdded = 1;
  }
  /**
   * Set additional parameters to the fckeditor view
   * @param view A reference to the control view
   */
  function configure_fckeditor(&$view)
  {
    $parser = InifileParser::getInstance();
    if (($libDir = $parser->getValue('libDir', 'cms')) === false) {
      throw new ConfigurationException("No library path 'libDir' defined in ini section 'cms'.");
    }
    $libDir .= '3rdparty/fckeditor/';
    $view->assign('libDir', $libDir);
    $view->assign('appDir', UriUtil::getProtocolStr().$_SERVER['HTTP_HOST'].str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']));
    $view->assign('FCKeditorCodeAdded', $this->FCKeditorCodeAdded);

    // unescape html special chars
    $trans = get_html_translation_table(HTML_ENTITIES);
    $trans = array_flip($trans);
    $value = strtr($view->get_template_vars('value'), $trans);
    $view->assign('value', preg_replace("/[\n\r]/", "", $value));
    $this->FCKeditorCodeAdded = 1;
  }
}
?>
