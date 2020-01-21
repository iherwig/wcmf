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
namespace wcmf\lib\model;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\UnionQueryProvider;

/**
 * ObjectQueryUnionQueryProvider provides queries as ObjectQuery instances.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ObjectQueryUnionQueryProvider implements UnionQueryProvider {
  protected $queries = [];

  /**
   * Constructor
   * @param $queries Array of ObjectQuery instances
   */
  public function __construct(array $queries) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // calculate ids and assign query definitions
    foreach ($queries as $query) {
      $type = $persistenceFacade->getFullyQualifiedType($query->getQueryType());
      $id = __CLASS__.','.$type.','.$query->getId();
      $this->queries[$id] = $query;
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
    $query = isset($this->queries[$queryId]) ? $this->queries[$queryId] : null;
    if (!$query) {
      throw new IllegalArgumentException('Query id '.$queryId.' is unknown');
    }
    return $query->execute($buildDepth, $orderby, $pagingInfo);
  }

  /**
   * Get the last query strings
   * @return Array of string
   */
  public function getLastQueryStrings() {
    $result = [];
    foreach ($this->queries as $query) {
      $result[] = $query->getLastQueryString();
    }
    return $result;
  }
}
?>
