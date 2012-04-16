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

/**
 * Control defines the interface for html controls. A Control instance
 * may be used to render controls of different input types.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Control {

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
  public function render($name, $inputType, $value, $editable=true, $language=null);

  /**
   * Translate a value with use of it's assoziated input type e.g get the location string from a location id.
   * (this is only done when the input type has a list definition).
   * @param value The value to translate (maybe comma separated list for list controls)
   * @param inputType The description of the control as given in the input_type property of a value (see Control::render())
   * @param nodeOid Serialized oid of the node containing this value (for determining remote oids) [default: null]
   * @param language The lanugage if Control should be localization aware. Optional,
   *                 default is Localization::getDefaultLanguage()
   * @return The translated value
   */
  public function translateValue($value, $inputType, $nodeOid=null, $language=null);
}
?>
