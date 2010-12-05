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
require_once(WCMF_BASE."wcmf/lib/core/class.ConfigurationException.php");
require_once(WCMF_BASE."wcmf/lib/util/class.InifileParser.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.View.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.Controller.php");

/**
 * @class DefaultValueRenderer
 * @ingroup Presentation
 * @brief DefaultValueRenderer is responsible for rendering (Node) values of a given display type.
 * Each display type is defined in a smarty template, which is configured by the appropriate entry
 * in the configuration section 'htmldisplay' (e.g. the image display type has the entry 'image').
 * The templates get a default set of variables assigned. Additional variables, needed only for
 * certain display types, are assigned in the appropriate configure method (e.g. 'configure_image')
 * which may be overridden by subclasses.
 *
 * New controls may be defined by defining the template, putting it into the configuration
 * section 'htmlform' and maybe implementing a configure method in a subclass of DefaultControlRenderer.
 * If a subclass is needed, the key 'ControlRenderer' in the configuration section 'implementation'
 * must point to it (don't forget to give the class definition in the 'classmapping' section).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultValueRenderer
{
  /**
   * Render a value of given type using the appropriate smarty template (defined in the
   * config section 'htmldisplay'). The given parameters will be passed to the view. If additional
   * parameters are needed an configure method must be implemented (name: configure_type)
   * @param type The display type of the value (the template is selected based on the type)
   * @param value The value to display
   * @param attributes An attribute string to be placed in the html tag (defining its appearance)
   * @return The HMTL representation of the value
   */
  function renderValue($type, $value, $attributes)
  {
    $parser = InifileParser::getInstance();

    // build input control
    $view = new View();

    // set default view parameters
    $view->setup();
    $view->assign('value', $value);
    $view->assign('attributes', $attributes);

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
    if (method_exists($this, $configureFunction)) {
      $this->$configureFunction($view);
    }
    if ($viewTpl = $parser->getValue($type, 'htmldisplay') === false) {
      throw new ConfigurationException("Unknown value display '".$type."'");
    }
    $htmlString = $view->fetch(WCMF_BASE.$parser->getValue($type, 'htmldisplay'));
    return $htmlString;
  }
  /**
   * Set additional parameters to the image html representation
   * @param view A reference to the html view
   */
  function configure_image(View $view)
  {
    $value = $view->get_template_vars('value');
    if (file_exists($value))
    {
      $properties = getimagesize($value);
      $view->assign('width', $properties[0]);
      $view->assign('height', $properties[1]);
      $view->assign('filename', basename($value));
      $view->assign('exists', true);
    }
    else {
      $view->assign('exists', false);
    }
  }
  /**
   * Set additional parameters to the text html representation
   * @param view A reference to the html view
   */
  function configure_text(View $view)
  {
  }
}
?>
