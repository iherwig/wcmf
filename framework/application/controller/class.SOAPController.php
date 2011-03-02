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
require_once(WCMF_BASE."wcmf/lib/presentation/class.Controller.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/persistence/locking/class.LockManager.php");
require_once(WCMF_BASE."wcmf/lib/model/class.Node.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeUtil.php");

/**
 * @class SOAPController
 * @ingroup Controller
 * @brief SOAPController is a controller that handles SOAP requests.
 *
 * <b>Input actions:</b>
 * - @em soapSearch Search for objects that match a searchterm in any attribute
 * - @em soapAdvancedSearch Search for objects of a given type
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param [in] searchterm The search term to use (needed for the action soapSearch)
 * @param [in] type The entity type to search for (needed for the action soapAdvancedSearch)
 * @param [in] query The query string to use, see StringQuery (needed for the action soapAdvancedSearch)
 * @param [out] soapResult The result of the processed action
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SOAPController extends Controller
{
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
      return false;
  }
  /**
   * Execute the requested soap action and add 'soapResult' to the data array
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    // process actions
    $action = $this->_request->getAction();
    if ($action == 'soapSearch')
      $this->soapSearch($this->_request->getValue('searchterm'));

    else if ($action == 'soapAdvancedSearch')
      $this->soapAdvancedSearch($this->_request->getValue('type'), $this->_request->getValue('query'));

    // release all locks
    $lockManager = &LockManager::getInstance();
    $lockManager->releaseAllLocks();

    $this->_response->setAction('ok');
    return false;
  }

  /**
   * Search all searchable types for a given term.
   * @param searchTerm The term to search for
   */
  function soapSearch($searchTerm)
  {
    // get all known types from configuration file
    $parser = &InifileParser::getInstance();
    $types = array_keys($parser->getSection('typemapping'));

    // query for each type
    $objectList = array();
    foreach ($types as $type)
    {
      $query = new ObjectQuery($type);
      $tpl = &$query->getObjectTemplate($type, ObjectQuery::QUERYOP_OR, ObjectQuery::QUERYOP_OR);

      // only search types with attributes and which are searchable
      if ($tpl->getProperty('is_searchable') == true)
      {
        $iter = new NodeValueIterator($tpl, false);
        while (!$iter->isEnd())
        {
          $curNode = $iter->getCurrentNode();
          $valueName = $iter->getCurrentAttribute();
          $value = $curNode->getValue($valueName);
          if (strlen($value) > 0) {
            $curNode->setValue($valueName, "LIKE '%".$value."%'");
          }
          $iter->proceed();
        }

        $nodes = $query->execute(BUILDDEPTH_SINGLE);
        foreach ($nodes as $node)
        {
    			$object = array();
    			$object['type'] = $node->getType();
    			$object['oid'] = $node->getOID();
          $object['displayName'] = strip_tags(preg_replace("/[\r\n']/", " ", NodeUtil::getDisplayValue($node)));
    			array_push($objectList, $object);
    	  }
      }
    }
    $this->_response->setValue('soapResult', $objectList);
  }

  /**
   * Search for instances of a given type, that satisfy the given query.
   * @param type The type to search for
   * @param queryStr The query string to satisfy
   */
  function soapAdvancedSearch($type, $queryStr)
  {
    $query = new StringQuery($type);
    $query->setConditionString($queryStr);
    $nodes = $query->execute(BUILDDEPTH_SINGLE);
		$objectList = array();
    foreach ($nodes as $node)
    {
			$object = array();
			$object['type'] = $node->getType();
			$object['oid'] = $node->getOID();
      $object['displayName'] = strip_tags(preg_replace("/[\r\n']/", " ", NodeUtil::getDisplayValue($node)));
			array_push($objectList, $object);
	  }
    $this->_response->setValue('soapResult', $objectList);
  }
}
?>
