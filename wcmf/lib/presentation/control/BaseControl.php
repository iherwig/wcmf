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
namespace wcmf\lib\presentation\control;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Localization;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\presentation\View;
use wcmf\lib\presentation\control\Control;
use wcmf\lib\util\StringUtil;

/**
 * BaseControl is the base class for html controls that use a View instance to
 * render the control. Each instance has a view template assigned, which defines
 * the actual representation of the control in html. A class may use
 * several view templates to render different html controls. The main
 * purpose of spezialized subclasses is the assignment of control specific
 * values to the associated view before it will be rendered
 * (@see BaseControl::assignViewValues()).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class BaseControl implements Control {

  private $_controlIndex = array();

  /**
   * Get a HTML input control for a given description.
   * @param name The name of the control (HTML name attribute)
   * @param inputType The definition of the control as given in the input_type property of a value
   *        The definition is of the form @code type @endcode or @code type[attributes]#list @endcode
   *        where list must be given for controls that allow to select from a list of values
   *        - type: a control type defined in the configuration file (section 'htmlform')
   *        - attributes: a string of attributes for the input control as used in the HTML definition (e.g. 'cols="50" rows="4"')
   *        - list: a list definition for which a ListStrategy is registered (@see Control::registerListStrategy).
   *                The list definition has the form @code listType:typeSpecificConfiguration @endcode
   * @param value The predefined value of the control (maybe comma separated list for list controls)
   * @param editable True/False if this is set false the function returns only the translated value (processed by translateValue()) [default: true]
   * @param language The lanugage if the Control should be localization aware. Optional,
   *                 default null (= Localization::getDefaultLanguage())
   * @param parentView The View instance, in which the control should be embedded. Optional,
   *                 default null
   * @return The HTML control string or the translated value string depending in the editable parameter
   */
  public function render($name, $inputType, $value, $editable=true, $language=null, $parentView=null) {
    // create the view
    $view = $this->createView($name, $inputType, $value, $editable, $language, $parentView);

    // add subclass parameters
    $this->assignViewValues($view);

    // render the view
    return $view->fetch(WCMF_BASE.$this->_viewTpl);
  }

  /**
   * @see Control::translateValue()
   */
  public function translateValue($value, $inputType, $replaceBR=false, $nodeOid = null, $language=null) {
    return $value;
  }

  /**
   * Create the control view.
   * @param name @see Control::render
   * @param inputType @see Control::render
   * @param value @see Control::render
   * @param editable @see Control::render
   * @param language @see Control::render
   * @param parentView @see Control::render
   * @return View instance
   */
  protected function createView($name, $inputType, $value, $editable=true, $language=null, $parentView=null) {
    $value = strval($value);

    // get definition and list from description
    if (strPos($inputType, '#')) {
      list($def, $list) = preg_split('/#/', $inputType, 2);
      $listMap = $this->getListMap($list, $value, null, $language);
    }
    else {
      $def = $inputType;
    }

    // if editable, make the value a list if we have a list type and the value contains comma separators
    // if not editable, translate the value (using the translateValue() method)
    if ($editable) {
      if (strlen($list) == 0 && strPos($value, ',')) {
        $value = preg_split('/,/', $value);
      }
      else {
        $value = htmlspecialchars($value);
      }
    }
    else {
      $value = $this->translateValue($value, $inputType, false, null, $language);
    }

    // get type and attributes from definition
    preg_match_all("/[\w][^\[\]]+/", $def, $matches);
    if (sizeOf($matches[0]) > 0) {
      list($type, $attributes) = $matches[0];
    }
    if (!$type || strlen($type) == 0) {
      $type = 'text';
    }

    // add '[]' to name if 'multiple' selection is given in attributes
    if (strPos($attributes, 'multiple')) {
      $name .= '[]';
    }

    // get error from session
    $session = ObjectFactory::getInstance('session');
    $error = $session->getError($name);

    // split attributes into an array
    $attributeList = array();
    $attributeParts = preg_split("/[\s,;]+/", $attributes);
    foreach($attributeParts as $attribute) {
      if (strlen($attribute) > 0) {
        list($key, $val) = preg_split("/[=:]+/", $attribute);
        $key = trim(stripslashes($key));
        $val = trim(stripslashes($val));
        $attributeList[$key] = $val;
      }
    }

    // setup the control view
    $view = ObjectFactory::getInstance('view');
    $view->setup();

    // assign view values
    $view->assign('enabled', $editable);
    $view->assign('name', $name);
    $view->assign('value', $value);
    $view->assign('error', $error);
    $view->assign('listMap', $listMap);
    $view->assign('inputType', $inputType);
    $view->assign('controlIndex', $this->getControlIndex($parentView));
    $view->assign('attributes', $attributes);
    $view->assign('attributeList', $attributeList);
    $this->registerWithView($parentView);

    return $view;
  }

  /**
   * Assign the control specific values to the view. Parameters assigned by default
   * may be retrieved by $view->getTemplateVars([$paramName])
   * @param view The view instance
   */
  protected function assignViewValues(View $view) {
  }

  /**
   * Get the index of the control in the given parent view.
   * This method relies on a call to Control::registerWithView()
   * @param parentView The parent View instance, with which the control is registered
   * @return The number of times that the control was registered with the parent view
   */
  private function getControlIndex($parentView) {
    $parentKey = spl_object_hash($parentView);
    if (isset($this->_controlIndex[$parentKey])) {
      return $this->_controlIndex[$parentKey];
    }
    return 0;
  }

  /**
   * Register this control with a given parent view.
   * @param parentView The parent View instance, with which the control should be registered
   */
  private function registerWithView($parentView) {
    $parentKey = spl_object_hash($parentView);
    if (!isset($this->_controlIndex[$parentKey])) {
      $this->_controlIndex[$parentKey] = 0;
    }
    $this->_controlIndex[$parentKey]++;
  }
}
?>
