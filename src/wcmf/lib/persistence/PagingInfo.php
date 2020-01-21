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
namespace wcmf\lib\persistence;

/**
 * PagingInfo contains information about a paged list.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PagingInfo {

  const SIZE_INFINITE = -1;

  private $pageSize = 10;
  private $page = 1;
  private $offset = 0;
  private $totalCount = 0;
  private $ignoreTotalCount = false;

  /**
   * Creates a PagingInfo object. The ignoreTotalCount parameter may be
   * set to true, if the count is to be ignored. This may speed up loading
   * of objects, because an extra count query may be omitted.
   * @param $pageSize The pageSize (PagingInfo::SIZE_INFINITE to set no page size)
   * @param $ignoreTotalCount Boolean whether this instance ignores the
   *    total count or not (optional, default: _false_)
   */
  public function __construct($pageSize, $ignoreTotalCount=false) {
    $this->pageSize = intval($pageSize);
    if ($this->pageSize == self::SIZE_INFINITE) {
      $this->pageSize = PHP_INT_MAX;
    }
    $this->ignoreTotalCount = $ignoreTotalCount;
  }

  /**
   * Set the number of list items.
   * @param $totalCount The number of list items.
   */
  public function setTotalCount($totalCount) {
    $this->totalCount = intval($totalCount);
  }

  /**
   * Get the number of list items.
   * @return Number
   */
  public function getTotalCount() {
    return $this->totalCount;
  }

  /**
   * Set the current page (1-based) (also sets the offset).
   * @param $page The current page.
   */
  public function setPage($page) {
    $this->page = intval($page);
    $this->offset = ($page - 1) * $this->pageSize;
  }

  /**
   * Get the current page (1-based).
   * @return Number
   */
  public function getPage() {
    return $this->page;
  }

  /**
   * Get the size of a pages.
   * @return Number
   */
  public function getPageSize() {
    return $this->pageSize;
  }

  /**
   * Get the number of pages.
   * @return Number
   */
  public function getPageCount() {
    return ceil($this->totalCount / $this->pageSize);
  }

  /**
   * Set the current offset (also selects the page).
   * @param $offset The current list offset.
   */
  public function setOffset($offset) {
    $this->offset = $offset;
    $this->page = ceil(intval($offset) / $this->pageSize) + 1;
  }

  /**
   * Get the current offset.
   * @return Number
   */
  public function getOffset() {
    return $this->offset;
  }

  /**
   * Determine if we are on the first page.
   * @return Boolean
   */
  public function isOnFirstPage() {
    return $this->page == 1;
  }

  /**
   * Determine if we are on the first page.
   * @return Boolean
   */
  public function isOnLastPage() {
    return $this->page == $this->getPageCount;
  }

  /**
   * Check if this instance iignores the total count.
   * @return Boolean
   */
  public function isIgnoringTotalCount() {
    return $this->ignoreTotalCount;
  }

  /**
   * Get the pages for a pagination navigation
   * @param $urlPattern Url string to use containing literal {page}, that will be replaced
   * @param $maxDisplayPages Maximum number of pages to display (optional, default: 10)
   * @return Array with keys 'first', 'last', 'current', 'prev', 'next', 'pages' and arrays with 'url', 'num' as values or null, if page count <= 1
   */
  public function getPagination($urlPattern, $maxDisplayPages=10) {
    if ($this->getPageCount() <= 1) {
      return null;
    }

    // calculate pages
    $getPage = function($val) use ($urlPattern) {
      return ['num' => $val, 'url' => str_replace('{page}', $val, $urlPattern)];
    };

    $first = 1;
    $last = $this->getPageCount();
    $page = $this->getPage();

    $halfRange = floor($maxDisplayPages/2);
    $startPage = ($page < $halfRange) ? $first : max([$page-$halfRange, $first]);
    $endPage = $maxDisplayPages-1 + $startPage;
    $endPage = ($last < $endPage) ? $last : $endPage;
    $diff = $startPage - $endPage + $maxDisplayPages-1;
    $startPage -= ($startPage - $diff > 0) ? $diff : 0;

    $pages = array_map($getPage, range($startPage, $endPage));

    return [
        'first' => $getPage($first),
        'last' => $getPage($last),
        'current' => $getPage($page),
        'prev' => $page > $startPage ? $getPage($page-1) : null,
        'next' => $page < $endPage ? $getPage($page+1) : null,
        'pages' => $pages,
    ];
  }
}
?>
