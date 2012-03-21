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
namespace wcmf\lib\presentation\format;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\presentation\WCMFInifileParser;
use wcmf\lib\presentation\control\Control;
use wcmf\lib\presentation\format\AbstractFormat;
use wcmf\lib\presentation\format\Formatter;
use wcmf\lib\presentation\format\IFormat;
use wcmf\lib\security\RightsManager;
use wcmf\lib\util\Obfuscator;

/**
 * Define the message format
 */
define("MSG_FORMAT_HTML", "HTML");

/**
 * HTMLFormat realizes the HTML request/response format. Since all data
 * from the external representation arrives in form fields, grouping of values
 * has to be done via the field names. So Nodes are represented by their values
 * whose field names are of the form value-<name>-<oid>. All of these
 * values will be removed from the request and replaced by Node instances
 * representing the data. The each node is stored under its oid in the data array.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class HTMLFormat extends AbstractFormat {

  /**
   * @see IFormat::deserialize()
   */
  public function deserialize(Request $request) {
    // construct nodes from values serialized as form fields
    // nodes are encoded in separated fields with names value-<name>-<oid>
    $data = $request->getValues();
    $nodeValues = array();
    foreach ($data as $key => $value) {
      $valueDef = Control::getValueDefFromInputControlName($key);
      if ($valueDef != null && strlen($valueDef['oid']) > 0) {
        $node = &$this->getNode($valueDef['oid']);
        $node->setValue($valueDef['name'], $value);
        array_push($nodeValues, $key);
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
   * @see IFormat::serialize()
   */
  public function serialize(Response $response) {
    // assign the data to the view if one exists
    $controller = $response->getController();
    if ($controller->hasView()) {
      // check if a view template is defined
      $request = $controller->getRequest();
      $viewTpl = self::getViewTemplate($response->getSender(),
                    $request->getContext(), $request->getAction());
      if (!$viewTpl) {
        throw new ConfigurationException("View definition missing for ".
                    "request: ".$request->__toString());
      }
      // create the view
      $view = ObjectFactory::createInstanceFromConfig('implementation', 'View');
      $view->setup();

      // assign the response data to the view
      $data = $response->getValues();
      foreach (array_keys($data) as $variable) {
        if (is_scalar($data[$variable])) {
          $view->assign($variable, $data[$variable]);
        }
        else {
          $view->assignByRef($variable, $data[$variable]);
        }
      }
      // assign additional values
      $rightsManager = RightsManager::getInstance();
      $authUser = $rightsManager->getAuthUser();
      $view->assignByRef('nodeUtil', new NodeUtil());
      $view->assignByRef('obfuscator', Obfuscator::getInstance());
      if ($authUser != null) {
        $view->assignByRef('authUser', $authUser);
      }

      // display the view
      if ($view->caching && ($cacheId = $controller->getCacheId()) !== null) {
        $view->display(WCMF_BASE.$viewTpl, $cacheId);
      }
      else {
        $view->display(WCMF_BASE.$viewTpl);
      }
    }
  }

  /**
   * Get the template filename for the view from the configfile.
   * @param controller The name of the controller
   * @param context The name of the context
   * @param action The name of the action
   * @return The filename of the template or false, if no view is defined
   */
  protected static function getViewTemplate($controller, $context, $action) {
    $view = '';
    $parser = WCMFInifileParser::getInstance();
    $actionKey = $parser->getBestActionKey('views', $controller, $context, $action);
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug('HTMLFormat::getViewTemplate: '.$controller."?".$context."?".$action.' -> '.$actionKey, __CLASS__);
    }
    // get corresponding view
    $view = $parser->getValue($actionKey, 'views', false);
    return $view;
  }
}

// register this format
Formatter::registerFormat(MSG_FORMAT_HTML, __NAMESPACE__.HTMLFormat);
?>
