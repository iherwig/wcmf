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
require_once(BASE."wcmf/lib/presentation/format/class.AbstractFormat.php");
require_once(BASE."wcmf/lib/presentation/class.View.php");
require_once(BASE."wcmf/lib/presentation/class.WCMFInifileParser.php");
require_once(BASE."wcmf/lib/model/class.NodeUtil.php");
require_once(BASE."wcmf/lib/security/class.RightsManager.php");
require_once(BASE."wcmf/lib/util/class.FormUtil.php");
require_once(BASE."wcmf/lib/util/class.FileUtil.php");
require_once(BASE."wcmf/lib/util/class.Obfuscator.php");
require_once(BASE."wcmf/lib/util/class.ObjectFactory.php");

/**
 * @class HTMLFormat
 * @ingroup Format
 * @brief HTMLFormat realizes the HTML request/response format. Since all data
 * from the external representation arrives in form fields, grouping of values
 * has to be done via the field names. So Nodes are represented by their values
 * whose field names are of the form value-<name>-<oid>. All of these
 * values will be removed from the request and replaced by Node instances
 * representing the data. The each node is stored under its oid in the data array.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class HTMLFormat extends AbstractFormat
{
  /**
   * @see IFormat::deserialize()
   */
  public function deserialize(Request $request)
  {
    // construct nodes from values serialized as form fields
    // nodes are encoded in separated fields with names value-<name>-<oid>
    $data = $request->getData();
    $nodeValues = array();
    foreach ($data as $key => $value)
    {
      $valueDef = NodeUtil::getValueDefFromInputControlName($key);
      if ($valueDef != null && strlen($valueDef['oid']) > 0)
      {
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
  public function serialize(Response $response)
  {
    // assign the data to the view if one exists
    $controller = $response->getController();
    if ($controller->hasView())
    {
      // check if a view template is defined
      $request = $controller->getRequest();
      $viewTpl = self::getViewTemplate($response->getSender(), 
                    $request->getContext(), $request->getAction());
      if (!$viewTpl) {
        throw new ConfigurationException("View definition missing for ".
                    get_class($this).". Action key: ".$actionKey);
      }
      // create the view
      $view = ObjectFactory::createInstanceFromConfig('implementation', 'View');
      $view->setup();
      
      // assign the response data to the view
      $data = $response->getData();
      foreach (array_keys($data) as $variable)
      {
        if (is_scalar($data[$variable])) {
          $view->assign($variable, $data[$variable]);
        }
        else {
          $view->assignByRef($variable, $data[$variable]);
        }
      }
      // assign additional values
      $parser = InifileParser::getInstance();
      $rightsManager = RightsManager::getInstance();
      $authUser = $rightsManager->getAuthUser();
      $view->assignByRef('formUtil', new FormUtil());
      $view->assignByRef('nodeUtil', new NodeUtil());
      $view->assignByRef('obfuscator', Obfuscator::getInstance());
      $view->assign('applicationTitle', $parser->getValue('applicationTitle', 'cms'));
      if ($authUser != null) {
        $view->assignByRef('authUser', $authUser);
      }
      
      // display the view
      if ($view->caching && ($cacheId = $controller->getCacheId()) !== null) {
        $view->display(BASE.$viewTpl, $cacheId);
      }
      else {
        $view->display(BASE.$viewTpl);
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
  protected static function getViewTemplate($controller, $context, $action)
  {
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
?>
