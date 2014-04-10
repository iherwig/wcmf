<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\lib\presentation\format\impl;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Action;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\presentation\format\impl\AbstractFormat;
use wcmf\lib\util\StringUtil;
use wcmf\lib\util\Obfuscator;

/**
 * HtmlFormat realizes the HTML request/response format. Since all data
 * from the external representation arrives in form fields, grouping of values
 * has to be done via the field names. So Nodes are represented by their values
 * whose field names are of the form value-<name>-<oid>. All of these
 * values will be removed from the request and replaced by Node instances
 * representing the data. The each node is stored under its oid in the data array.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class HtmlFormat extends AbstractFormat {

  private static $_inputFieldNameDelimiter = '-';

  /**
   * @see Format::getMimeType()
   */
  public function getMimeType() {
    return 'text/html';
  }

  /**
  }
   * @see Format::deserialize()
   */
  public function deserialize(Request $request) {
    // construct nodes from values serialized as form fields
    // nodes are encoded in separated fields with names value-<name>-<oid>
    $data = $request->getValues();
    $nodeValues = array();
    foreach ($data as $key => $value) {
      $valueDef = self::getValueDefFromInputControlName($key);
      if ($valueDef != null && strlen($valueDef['oid']) > 0) {
        $node = &$this->getNode($valueDef['oid']);
        $node->setValue($valueDef['name'], $value);
        $nodeValues[] = $key;
      }
    }

    // replace node values by nodes
    foreach ($nodeValues as $key) {
      $request->clearValue($key);
    }
    $deserializedNodes = $this->getNodes();
    foreach (array_keys($deserializedNodes) as $oid) {
      $request->setValue($oid, $deserializedNodes[$oid]);
    }
  }

  /**
   * @see Format::serialize()
   */
  public function serialize(Response $response) {
    // check if a view template is defined
    $viewTpl = self::getViewTemplate($response->getSender(),
                  $response->getContext(), $response->getAction());
    if (!$viewTpl) {
      throw new ConfigurationException("View definition missing for ".
                  "response: ".$response->__toString());
    }
    // create the view
    $view = ObjectFactory::getInstance('view');

    // assign the response data to the view
    $data = $response->getValues();
    foreach (array_keys($data) as $variable) {
      $view->setValue($variable, $data[$variable]);
    }

    // display the view
    $view->render(WCMF_BASE.$viewTpl, $response->getCacheId());
  }

  /**
   * Get the template filename for the view from the configfile.
   * @param controller The name of the controller
   * @param context The name of the context
   * @param action The name of the action
   * @return The filename of the template or false, if no view is defined
   */
  protected static function getViewTemplate($controller, $context, $action) {
    $actionKey = Action::getBestMatch('views', $controller, $context, $action);
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug('HtmlFormat::getViewTemplate: '.$controller."?".$context."?".$action.' -> '.$actionKey, __CLASS__);
    }
    // get corresponding view
    $config = ObjectFactory::getConfigurationInstance();
    $view = $config->getValue($actionKey, 'views', false);
    return $view;
  }

  /**
   * Get the object value definition from a HTML input field name.
   * @param name The name of input field in the format value-<name>-<oid>, where name is the name
   *              of the attribute belonging to the node defined by oid
   * @return An associative array with keys 'oid', 'language', 'name' or null if the name is not valid
   */
  protected static function getValueDefFromInputControlName($name) {
    if (!(strpos($name, 'value') == 0)) {
      return null;
    }
    $def = array();
    $fieldDelimiter = StringUtil::escapeForRegex(self::$_inputFieldNameDelimiter);
    $pieces = preg_split('/'.$fieldDelimiter.'/', $name);
    if (sizeof($pieces) != 3) {
      return null;
    }
    $ignore = array_shift($pieces);
    $def['language'] = array_shift($pieces);
    $def['name'] = array_shift($pieces);
    $def['oid'] = array_shift($pieces);

    return $def;
  }
}
?>
