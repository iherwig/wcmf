<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\impl;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\UnionQueryProvider;

/**
 * DefaultUnionQueryProvider provides queries for multiple types using the
 * PersistentFacade::loadObjects() method.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultUnionQueryProvider implements UnionQueryProvider {
  protected $queries = [];

  /**
   * Constructor
   * @param $types Array of types to query
   * @param $criteria
   */
  public function __construct($types, $criteria=null) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // group criteria by type
    $tmpCriteria = [];
    if ($criteria) {
      foreach ($criteria as $criterion) {
        $type = $persistenceFacade->getFullyQualifiedType($criterion->getType());
        if (!isset($tmpCriteria[$type])) {
          $tmpCriteria[$type] = [];
        }
        $tmpCriteria[$type][] = $criterion;
      }
    }

    // calculate ids and assign query definitions
    foreach ($types as $type) {
      $type = $persistenceFacade->getFullyQualifiedType($type);
      $typeCriteria = isset($tmpCriteria[$type]) ? $tmpCriteria[$type] : [];
      $id = __CLASS__.','.$type.','.join(',', $typeCriteria);
      $this->queries[$id] = [
          'type' => $type,
          'criteria' => $typeCriteria,
      ];
    }
  }

  /**
   * @see UnionQueryProvider::getIds()
   */
  public function getIds() {
    return array_keys($this->queries);
  }

  /**
   * @see UnionQueryProvider::execute()
   */
  public function execute($queryId, $buildDepth, $orderby, $pagingInfo) {
    $queryDef = isset($this->queries[$queryId]) ? $this->queries[$queryId] : null;
    if (!$queryDef) {
      throw new IllegalArgumentException('Query id '.$queryId.' is unknown');
    }
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $type = $queryDef['type'];
    $criteria = $queryDef['criteria'];
    $tmpPerm = $permissionManager->addTempPermission($type, '', PersistenceAction::READ);
    $result = $persistenceFacade->loadObjects($type, $buildDepth, $criteria, $orderby, $pagingInfo);
    $permissionManager->removeTempPermission($tmpPerm);
    return $result;
  }
}
?>
