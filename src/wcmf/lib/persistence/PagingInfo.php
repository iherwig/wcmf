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
namespace wcmf\lib\persistence;

/**
 * PagingInfo contains information about a paged list.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PagingInfo {

  private $_pageSize = 10;
  private $_page = 0;
  private $_offset = 0;
  private $_totalCount = 0;
  private $_ignoreTotalCount = false;

  /**
   * Creates a PagingInfo object. The ignoreTotalCount parameter may be
   * set to true, if the count is to be ignored. This may speed up loading
   * of objects, because an extra count query may be omitted.
   * @param pageSize The pageSize (-1 to set no page size)
   * @param ignoreTotalCount Boolean whether this instance ignores the
   *    total count or not, optional [default: false]
   */
  public function __construct($pageSize, $ignoreTotalCount=false) {
    $this->_pageSize = intval($pageSize);
    $this->_ignoreTotalCount = $ignoreTotalCount;
  }

  /**
   * Set the number of list items.
   * @param totalCount The number of list items.
   */
  public function setTotalCount($totalCount) {
    $this->_totalCount = intval($totalCount);
  }

  /**
   * Get the number of list items.
   * @return Number
   */
  public function getTotalCount() {
    return $this->_totalCount;
  }

  /**
   * Set the current page (1-based) (also sets the offset).
   * @param page The current page.
   */
  public function setPage($page) {
    $this->_page = intval($page);
    $this->_offset = ($page - 1) * $this->_pageSize;
  }

  /**
   * Get the current page (1-based).
   * @return Number
   */
  public function getPage() {
    return $this->_page;
  }

  /**
   * Get the size of a pages.
   * @return Number
   */
  public function getPageSize() {
    return $this->_pageSize;
  }

  /**
   * Get the number of pages.
   * @return Number
   */
  public function getPageCount() {
    return ceil($this->_totalCount / $this->_pageSize);
  }

  /**
   * Set the current offset (also selects the page).
   * @param offset The current list offset.
   */
  public function setOffset($offset) {
    $this->_offset = $offset;
    $this->_page = ceil(intval($offset) / $this->_pageSize) + 1;
  }

  /**
   * Get the current offset.
   * @return Number
   */
  public function getOffset() {
    return $this->_offset;
  }

  /**
   * Determine if we are on the first page.
   * @return Boolean
   */
  public function isOnFirstPage() {
    return $this->_page == 1;
  }

  /**
   * Determine if we are on the first page.
   * @return Boolean
   */
  public function isOnLastPage() {
    return $this->_page == $this->getPageCount;
  }

  /**
   * Check if this instance iignores the total count.
   * @return Boolean
   */
  public function isIgnoringTotalCount() {
    return $this->_ignoreTotalCount;
  }
}
?>
