<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\config\impl;

use wcmf\lib\config\ActionKey;
use wcmf\lib\config\ActionKeyProvider;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;

/**
 * PersistenceActionKeyProvider searches for action keys in the
 * application storage.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PersistenceActionKeyProvider implements ActionKeyProvider {

  private $_entityType = null;
  private $_valueMap = array();
  private $_cacheId = null;

  /**
   * Set the entity type to search in.
   * @param $entityType String
   */
  public function setEntityType($entityType) {
    $this->_entityType = $entityType;
    $this->_cacheId = null;
  }

  /**
   * Set the value map for the entity type.
   * @param $valueMap Associative array that maps the keys 'resource', 'context', 'action', 'value'
   *    to value names of the entity type.
   */
  public function setValueMap($valueMap) {
    $this->_valueMap = $valueMap;
  }

  /**
   * @see ActionKeyProvider::containsKey()
   */
  public function containsKey($actionKey) {
    return $this->getKeyValue($actionKey) !== null;
  }

  /**
   * @see ActionKeyProvider::getKeyValue()
   */
  public function getKeyValue($actionKey) {
    $query = new ObjectQuery($this->_entityType, __CLASS__.__METHOD__);
    $tpl = $query->getObjectTemplate($this->_entityType);
    $actionKeyParams = ActionKey::parseKey($actionKey);
    $tpl->setValue($this->_valueMap['resource'], Criteria::asValue('=', $actionKeyParams['resource']));
    $tpl->setValue($this->_valueMap['context'], Criteria::asValue('=', $actionKeyParams['context']));
    $tpl->setValue($this->_valueMap['action'], Criteria::asValue('=', $actionKeyParams['action']));
    $keys = $query->execute(BuildDepth::SINGLE);
    if (sizeof($keys) > 0) {
      return $keys[0]->getValue($this->_valueMap['value']);
    }
    return null;
  }

  /**
   * @see ActionKeyProvider::getCacheId()
   */
  public function getCacheId() {
    if ($this->_cacheId ==  null) {
      $this->_cacheId = __CLASS__.'.'.$this->_entityType;
    }
    return $this->_cacheId;
  }
}
?>
