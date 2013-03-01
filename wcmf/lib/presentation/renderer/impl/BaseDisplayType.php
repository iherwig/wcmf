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
namespace wcmf\lib\presentation\renderer\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\View;
use wcmf\lib\presentation\renderer\DisplayType;

/**
 * BaseDisplayType is the base class for DisplayType implementations that
 * use a View instance to render the value. Each instance has a view
 * template assigned, which defines the actual representation of the value
 * (display type) in html. A class may use several view templates to render
 * different display types in html. The main purpose of spezialized subclasses
 * is the assignment of display type specific values to the associated view
 * before it will be rendered
 * (@see BaseDisplayType::assignViewValues()).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class BaseDisplayType implements DisplayType {

  /**
   * The view template used by the concrete renderer instance
   */
  private $_viewTpl = null;

  /**
   * Set the view template to use when rendering
   * @param viewTpl
   */
  public function setViewTpl($viewTpl) {
    $this->_viewTpl = $viewTpl;
  }

  /**
   * @see DisplayType::render
   */
  public function render($value, $attributes) {
    // split attributes into an array
    $attributeList = array();
    $attributeParts = preg_split("/[\s,;]+/", $attributes);
    foreach($attributeParts as $attribute) {
      if (strlen($attribute) > 0) {
        list($key, $value) = preg_split("/[=:]+/", $attribute);
        $key = trim(stripslashes($key));
        $value = trim(stripslashes($value));
        $attributeList[$key] = $value;
      }
    }

    // build renderer view
    $view = ObjectFactory::getInstance('view');
    $view->setup();

    // assign view values
    $view->assign('value', $value);
    $view->assign('attributes', $attributes);
    $view->assign('attributeList', $attributeList);

    // add subclass parameters
    $this->assignViewValues($view);

    // render the view
    $htmlString = $view->fetch(WCMF_BASE.$this->_viewTpl);
    return $htmlString;
  }

  /**
   * Assign the display type specific values to the view. Parameters assigned by default
   * may be retrieved by $view->getTemplateVars($paramName)
   * The default implementation does nothing, subclasses may overwrite this method
   * to meet special requirements.
   * @param view The view instance
   */
  protected function assignViewValues(View $view);
}
?>
