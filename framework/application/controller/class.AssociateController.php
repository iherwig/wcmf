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
require_once(BASE."wcmf/lib/presentation/class.Controller.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/model/class.Node.php");
require_once(BASE."wcmf/lib/model/class.NullNode.php");

/**
 * @class AssociateController
 * @ingroup Controller
 * @brief AssociateController is a controller that (dis-)associates Nodes
 * (by setting the parent/child relations).
 *
 * <b>Input actions:</b>
 * - @em associate Associate one Node to another
 * - @em disassociate Disassociate one Node from another
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] oid The object id of the Node to associate a Node as child to
 * @param[in] associateoids The object ids of the Nodes to (dis-)associate as parents/children (comma separated list)
 * @param[in] associateAs The role of the associated Nodes as seen from oid: Either 'parent' or 'child'
 * @param[out] manyToMany The created many to many Node if one was created
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AssociateController extends Controller
{
  /**
   * @see Controller::validate()
   */
  function validate()
  {
    if(!PersistenceFacade::isValidOID($this->_request->getValue('oid')))
    {
      $this->setErrorMsg("No valid 'oid' given in data.");
      return false;
    }
    return true;
  }
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return false;
  }
  /**
   * (Dis-)Associate the Nodes.
   * @return Array of given context and action 'ok' in every case.
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $lockManager = &LockManager::getInstance();

    // get parent node
    $parentOID = $this->_request->getValue('oid');
    $lockManager->releaseLock($parentOID);
    $parentNode = &$persistenceFacade->load($parentOID, BUILDDEPTH_SINGLE);

    // iterate over associateoids
    $associateoids = $this->_request->getValue('associateoids');
    $associateoidArray = preg_split('/,/', $associateoids);
    foreach ($associateoidArray as $associateoid)
    {
      $associateoid = trim($associateoid);
      if(!PersistenceFacade::isValidOID($associateoid))
      {
        $this->setErrorMsg("Invalid oid given in data.");
        $this->_response->setAction('ok');
        return true;
      }

      // if the current user has a lock on the object, release it
      $lockManager->releaseLock($associateoid);
      $childNode = &$persistenceFacade->load($associateoid, BUILDDEPTH_SINGLE);

      if ($parentNode != null && $childNode != null)
      {
        // create templates of parent and child
        $parentType = PersistenceFacade::getOIDParameter($parentOID, 'type');
        $parentTemplate = &$persistenceFacade->create($parentType, 1);
        $childType = PersistenceFacade::getOIDParameter($associateoid, 'type');
        $childTemplate = &$persistenceFacade->create($childType, 1);

        // process actions
        if ($this->_request->getAction() == 'associate')
        {
          // check if we can directly associate child to parent
          if ($this->_request->getValue('associateAs') == 'child' && $this->isDirectAssociation($parentTemplate, $childTemplate))
          {
            $parentNode->addChild($childNode);
            $parentNode->setType($parentNode->getType());
            $childNode->save();
          }
          // check if we can directly associate parent to child
          else if ($this->_request->getValue('associateAs') == 'parent' && $this->isDirectAssociation($childTemplate, $parentTemplate))
          {
            $childNode->addChild($parentNode);
            $parentNode->save();
          }
          else
          {
            // if the parent is connected via an association object, we have to create one
            $linkType = $this->findAssociationType($parentTemplate, $childTemplate);
            if ($linkType != null)
            {
              $link = &$persistenceFacade->create($linkType, BUILDTYPE_SINGLE);
              $parentNode->addChild($link);
              $link->save();
              $link = &$persistenceFacade->load($link->getOID(), BUILDTYPE_SINGLE);
              $childNode->addChild($link);
              $link->save();
              $this->_response->setValue("manyToMany", $link);
            }
            else
            {
              $this->appendErrorMsg(Message::get("Cannot associate %1% and %2%. No direct connection and no connection type found.",
                array($associateoid, $parentOID)));
            }
          }
        }
        elseif ($this->_request->getAction() == 'disassociate')
        {
          // check if it is a direct association from child to parent
          if ($this->isDirectAssociation($parentTemplate, $childTemplate))
          {
            // use a NullNode to empty foreign key
            $parentNode = new NullNode($parentTemplate->getType());
            $parentNode->addChild($childNode);
            $childNode->save();
          }
          // check if it is a direct association from parent to child
          else if ($this->isDirectAssociation($childTemplate, $parentTemplate))
          {
            // use a NullNode to empty foreign key
            $childNode = new NullNode($childTemplate->getType());
            $childNode->addChild($parentNode);
            $parentNode->save();
          }
          else
          {
            // if the parent is connected via an association object, we have to delete that
            $linkType = $this->findAssociationType($parentTemplate, $childTemplate);
            if ($linkType != null)
            {
              // find association object as child of both (parent and child)
              // since the same nm object can have different roles (= different oids),
              // a simple array_intersect of the children oids does not work here
              $parentNode->loadChildren();
              $childNode->loadChildren();
              $parentChildren = $parentNode->getChildren();
              $childChildren = $childNode->getChildren();
              for($i=0, $countI=sizeof($parentChildren); $i<$countI; $i++)
              {
                for($j=0, $countJ=sizeof($childChildren); $j<$countJ; $j++)
                {
                  $objA = &$parentChildren[$i];
                  $objB = &$childChildren[$j];
                  if (($objA->getType() == $linkType || $objB->getType() == $linkType) && ($objA->getBaseOID() == $objB->getBaseOID()))
                  {
                    $objA->delete();
                  }
                }
              }
            }
            else
            {
              $this->appendErrorMsg(Message::get("Cannot disassociate %1% and %2%. No direct connection and no connection type found.",
                array($associateoid, $parentOID)));
            }
          }
        }
      }
      else
      {
        if ($parentNode == null)
          $this->appendErrorMsg(Message::get("Cannot %1% %2% and %3%. Parent does  not exist.", array($this->_request->getAction(), $associateoid, $parentOID)));
        if ($childNode == null)
          $this->appendErrorMsg(Message::get("Cannot %1% %2% and %3%. Child does  not exist.", array($this->_request->getAction(), $associateoid, $parentOID)));
      }
    }
    $this->_response->setAction('ok');
    return true;
  }

  /**
   * Check if two Nodes are directly assiociated (in a direct parent-child relation)
   * @param parent A template of the parent object (with children attached)
   * @param child The child to check
   * @return True/False
   */
  function isDirectAssociation(&$parent, &$child)
  {
    $childTypeChildren = $parent->getChildrenEx(null, $child->getType(), null, null);
    if (sizeof($childTypeChildren) > 0)
      return true;
    else
      return false;
  }

  /**
   * Search for an child type of parent that establishes the association between a given
   * parent and child or vice versa.
   * @param parent A template of the parent object (with children attached)
   * @param child The child to check
   * @return The type
   */
  function findAssociationType(&$parent, &$child)
  {
    foreach ($parent->getChildren() as $possibleChild)
    {
      if (in_array('manyToMany', $possibleChild->getPropertyNames()))
      {
        $associationEnds = $possibleChild->getProperty('manyToMany');
        if (in_array($child->getType(), $associationEnds))
          return $possibleChild->getType();
      }
    }
    foreach ($child->getChildren() as $possibleChild)
    {
      if (in_array('manyToMany', $possibleChild->getPropertyNames()))
      {
        $associationEnds = $possibleChild->getProperty('manyToMany');
        if (in_array($parent->getType(), $associationEnds))
          return $possibleChild->getType();
      }
    }
    return null;
  }
}
?>

