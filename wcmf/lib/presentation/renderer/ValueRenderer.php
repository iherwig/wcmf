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
namespace wcmf\lib\presentation\renderer;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\config\InifileParser;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\IView;
use wcmf\lib\presentation\renderer\ValueRenderer;

/**
 * ValueRenderer is the base class for html renderers. Each ValueRenderer
 * instance has a view template assigned, which defines the actual
 * representation of the value (display type) in html. A ValueRenderer class may use
 * several view templates to render different display types in html. The main
 * purpose of ValueRenderer classes is the assignment of display type specific
 * values to the associated view before it will be rendered
 * (@see ValueRenderer::assignViewValues()).
 *
 * ValueRenderer are be bound to value types in the configuration section
 * named 'htmldisplay' in the following way:
 * @code
 * [htmldisplay]
 * displayType = {rendererClass, viewTemplate} e.g.
 * image = {ImageRenderer, wcmf/application/views/display/image.tpl}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class ValueRenderer
{
  const RENDERER_SECTION_NAME = 'htmldisplay';

  private $_viewTpl = null;

  /**
   * Get an instance of the renderer class that matches the given displayType.
   * The implementation searches in the configuration section 'htmldisplay' for the
   * most specific key that matches the displayType.
   * @param displayType The value type to get the renderer for
   * @return A ValueRenderer instance
   */
  public static function getRenderer($displayType)
  {
    $parser = InifileParser::getInstance();
    if(($rendererSection = $parser->getSection(self::RENDERER_SECTION_NAME)) === false) {
      throw new ConfigurationException("No renderers defined. ".$parser->getErrorMsg());
    }
    // get best matching renderer definition
    $bestMatch = '';
    foreach (array_keys($rendererSection) as $rendererDef)
    {
      if (strpos($displayType, $rendererDef) === 0 && strlen($rendererDef) > strlen($bestMatch)) {
        $bestMatch = $rendererDef;
      }
    }
    // instantiate the renderer
    if (strlen($bestMatch) > 0)
    {
      $rendererDef = $rendererSection[$bestMatch];
      if (!is_array($rendererDef) || sizeof($rendererDef) != 2) {
        throw new ConfigurationException("Expected an array of size 2 for key '".
          $bestMatch."' in section '".self::RENDERER_SECTION_NAME."'");
      }
      $rendererClass = $rendererDef[0];
      $viewTpl = $rendererDef[1];
      $renderer = ObjectFactory::createInstance($rendererClass);
      if (!$renderer instanceof ValueRenderer) {
        throw new ConfigurationException($rendererClass." does not inherit from ValueRenderer");
      }
      $renderer->_viewTpl = $viewTpl;
      return $renderer;
    }
    // no match found
    throw new ConfigurationException("No renderer found for value type '".
      $displayType."' in section '".self::RENDERER_SECTION_NAME."'");
  }
  /**
   * Render a value of given display type using the appropriate smarty template (defined in the
   * config section 'htmldisplay'). The given parameters will be passed to the view.
   * @param displayType The display type of the value
   * @param value The value to display
   * @param attributes An attribute string to be placed in the html tag (defining its appearance)
   * @return The HMTL representation of the value
   */
  public static function render($displayType, $value, $attributes)
  {
    $renderer = self::getRenderer($displayType);

    // split attributes into an array
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

    // build renderer view
    $view = ObjectFactory::createInstanceFromConfig('implementation', 'View');
    $view->setup();

    // assign view values
    $view->assign('value', $value);
    $view->assign('attributes', $attributes);
    $view->assign('attributeList', $attributeList);

    // add subclass parameters
    $renderer->assignViewValues($view);

    // render the view
    $htmlString = $view->fetch(WCMF_BASE.$renderer->_viewTpl);
    return $htmlString;
  }
  /**
   * Assign the display type specific values to the view. Parameters assigned by default
   * may be retrieved by $view->getTemplateVars($paramName)
   * The default implementation does nothing, subclasses may overwrite this method
   * to meet special requirements.
   * @param view The view instance
   */
  protected abstract function assignViewValues(IView $view);
}
?>
