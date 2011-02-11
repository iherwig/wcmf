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
require_once(WCMF_BASE."wcmf/lib/model/class.Node.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeUtil.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeIterator.php");
require_once(WCMF_BASE."wcmf/lib/visitor/class.CommitVisitor.php");
require_once(WCMF_BASE."wcmf/lib/util/class.Obfuscator.php");

/**
 * @class SortController
 * @ingroup Controller
 * @brief SortController is a controller that sorts Nodes of same type.
 *
 * This Controller sorts the Nodes by responding to the 'sortup', 'sortdown'
 * action names. It requires a parameter 'sortoid' which tells to which Node
 * the action should be applied. According to the given action the Nodes sortkey
 * attribute will be changed the way that it either swaps with its predecessor
 * ('prevoid') or with its successor ('nextoid') or move a given distance ('dist')
 * in a list of nodes that is either defined by the 'filter' or the 'poid' parameter.
 * If none of the both parameters is given, the list contains all entities that
 * have the same type as the type defined by 'sortoid'.
 *
 * After using the NodeUtil::setSortProperties method on a list of Nodes
 * you could write the following Smarty template code to support sorting:
 *
 * @code
 * {foreach from=$nodeList item=curNode}
 *   {if $curNode->getValue('hasSortUp')}
 *     <a href="javascript:setVariable('sortoid', '{$curNode->getOID()}'); setVariable('prevoid', '{$curNode->getValue('prevoid')}'); submitAction('sortup');">{translate text="up"}</a>
 *   {else}
 *     <span>{translate text="up"}</span>
 *   {/if}
 *   {if $curNode->getValue('hasSortDown')}
 *     <a href="javascript:setVariable('sortoid', '{$curNode->getOID()}'); setVariable('nextoid', '{$curNode->getValue('nextoid')}'); submitAction('sortdown');">{translate text="down"}</a>
 *   {else}
 *     <span>{translate text="down"}</span>
 *   {/if}
 * {/foreach}
 * @endcode
 *
 * <b>Input actions:</b>
 * - @em sortup Move the given Node up in the list
 * - @em sortdown Move the given Node down in the list
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] sortoid The oid of the Node to change its sortkey. The Controller assumes
 *            that the given Node has a sortkey attribute.
 * @param[in] prevoid The oid of the Node to swap with on sortup action.
 *            If not given, the Node with previous sortkey is taken.
 * @param[in] nextoid The oid of the Node to swap with on sortdown action.
 *            If not given, the Node with next sortkey is taken.
 * @param[in] dist The distance to move the Node up or down. 'prevoid', 'nextoid' will
 *            be ignored.
 * @param[in] filter A filter string to be used to filter the entities in the list,
 *            when moving by dist (see StringQuery), if no filter is defined, all
 *            entities of the type defined in sortoid are contained in the list to sort.
 * @param[in] poid As alternative to the filter parameter the entity list maybe defined
 *            by a parent oid, which means that all child nodes of that parent are
 *            contained in the list to sort.
 * @param[in] sortcol The name of the column to use for sorting.
 *            If not given it defaults to 'sortkey'.
 * @param[out] oid The oid of the Node that changed its sortkey (= sortoid).
 *
 * @author   ingo herwig <ingo@wemove.com>
 */
class SortController extends Controller
{
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return false;
  }
  /**
   * @see Controller::validate()
   */
  function validate()
  {
    if(!$this->_request->hasValue('sortoid'))
    {
      $this->setErrorMsg("No 'sortoid' given in data.");
      return false;
    }
    return true;
  }
  /**
   * Sort Nodes.
   * @return Array of given context and action 'ok' in every case.
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    $persistenceFacade = &PersistenceFacade::getInstance();

    // do actions
    if ($this->_request->getAction() == 'sortdown' && PersistenceFacade::isValidOID($this->_request->getValue('nextoid')))
    {
      // if action is sortdown and nextoid is given, we have to swap the Nodes oids
      $node1 = &$persistenceFacade->load($this->_request->getValue('sortoid'), BUILDDEPTH_SINGLE);
      $node2 = &$persistenceFacade->load($this->_request->getValue('nextoid'), BUILDDEPTH_SINGLE);
      $this->swapNodes($node1, $node2, true);
    }
    else if ($this->_request->getAction() == 'sortup' && PersistenceFacade::isValidOID($this->_request->getValue('prevoid')))
    {
      // if action is sortup and prevoid is given, we have to swap the Nodes oids
      $node1 = &$persistenceFacade->load($this->_request->getValue('sortoid'), BUILDDEPTH_SINGLE);
      $node2 = &$persistenceFacade->load($this->_request->getValue('prevoid'), BUILDDEPTH_SINGLE);
      $this->swapNodes($node1, $node2, true);
    }
    else
    {
      // if prevoid/nextoid are not given, we have to load all Nodes and sort...
      $this->sortAll();
    }

    $this->_response->setValue('oid', $this->_request->getValue('sortoid'));
    $this->_response->setAction('ok');
    return true;
  }
  /**
   * Get the name of the column to use for sorting.
   * @return The name.
   */
  function getSortColumn()
  {
    // determine the sort column
    $sortCol = 'sortkey';
    if (strlen($this->_request->getValue('sortcol')) > 0) {
      $sortCol = $this->_request->getValue('sortcol');
    }
    return $sortCol;
  }
  /**
   * Swap sortkey of two given Nodes.
   * @param node1 first Node
   * @param node2 second Node
   * @param doSave True/False wether to save the Nodes or not
   */
  function swapNodes($node1, $node2, $doSave)
  {
    $sortCol = $this->getSortColumn();

    $sortkey1 = $node2->getValue($sortCol);
    $sortkey2 = $node1->getValue($sortCol);

    // fallback sortkeys have never been set
    if (!$sortkey1 || !$sortkey2)
    {
      $this->sortAll();
      return;
    }

    // fallback if sortkeys are identical
    if ($sortkey1 == $sortkey2) {
      $sortkey1++;
    }
    // actually swap sortkeys
    $node1->setValue($sortCol, $sortkey1);
    $node2->setValue($sortCol, $sortkey2);

    if ($doSave)
    {
      $node1->save();
      $node2->save();
    }
  }
  /**
   * Sort all Nodes.
   */
  function sortAll()
  {
    $sortCol = $this->getSortColumn();
    $type = PersistenceFacade::getOIDParameter($this->_request->getValue('sortoid'), 'type');
    $nodes = array();

    // load all children of poid if the poid parameter is given
    if ($this->_request->hasValue('poid'))
    {
      $poid = $this->_request->getValue('poid');
      if (PersistenceFacade::isValidOID($poid))
      {
        $persistenceFacade = &PersistenceFacade::getInstance();
        $parent = &$persistenceFacade->load($poid, 1);
        if ($parent) {
          $nodes = Node::sort($parent->getChildren(), 'sortkey');
        }
      }
      else {
        $this->setErrorMsg("The 'poid' parameter is invalid.");
      }
    }
    // load all nodes defined by filter if the filter parameter is given
    else if ($this->_request->hasValue('filter'))
    {
      // unveil the filter value if it is obfuscated
      $filter = $this->_request->getValue('filter');
      $unveiled = Obfuscator::unveil($filter);
      if (strlen($filter) > 0 && strlen($unveiled) > 0) {
        $filter = $unveiled;
        $nodes = ObjectQuery::executeString($type, $filter, BUILDDEPTH_SINGLE, array($type.'.'.$sortCol));
      }
      else {
        $this->setErrorMsg("The 'poid' parameter is invalid.");
      }
    }
    // load all nodes defined by 'sortoid' type in any other case
    else
    {
      $filter = NodeUtil::getNodeQuery($type);
      $nodes = ObjectQuery::executeString($type, $filter, BUILDDEPTH_SINGLE, array($type.'.'.$sortCol));
    }

    // load all nodes and set their sortkeys ascending
    $rootNode = new Node('');
    $sortkey = 1;
    for ($i=0; $i<sizeof($nodes); $i++)
    {
      $nodes[$i]->setValue($sortCol, $sortkey);
      $rootNode->addNode($nodes[$i]);
      $sortkey++;
    }

    $dist = 1;
    if (strlen($this->_request->getValue('dist')) > 0) {
      $dist = $this->_request->getValue('dist');
    }
    // swap nodes
    $nodes = $rootNode->getChildren();
    $numNodes = sizeof($nodes);
    for ($j=0; $j<$dist; $j++)
    {
      for ($i=0; $i<$numNodes; $i++)
      {
        if ($nodes[$i]->getOID() == $this->_request->getValue('sortoid'))
        {
          if ($this->_request->getAction() == 'sortdown' && $i<$numNodes-1) {
            $this->swapNodes($nodes[$i], $nodes[$i+1], false);
          }
          elseif ($this->_request->getAction() == 'sortup' && $i>0) {
            $this->swapNodes($nodes[$i], $nodes[$i-1], false);
          }
        }
      }
      $nodes = Node::sort($nodes, $sortCol);
    }

    // don't save the root node
    $rootNode->setState(STATE_CLEAN, false);

    // commit changes
    $nIter = new NodeIterator($rootNode);
    $cv = new CommitVisitor();
    $cv->startIterator($nIter);
  }
}
?>
