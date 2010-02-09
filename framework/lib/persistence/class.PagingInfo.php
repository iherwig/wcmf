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

/**
 * @class PagingInfo
 * @ingroup Persistence
 * @brief PagingInfo contains information about a paged list.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PagingInfo
{
  var $_pageSize = 10;
  var $_page = 0;
  var $_offset = 0;
  var $_totalCount = 0;

  /**
   * Creates a PagingInfo object.
   * @param pageSize The pageSize (-1 to set no page size)
   */  
  public function PagingInfo($pageSize)
  {
    $this->_pageSize = intval($pageSize);
  }

  /**
   * Set the number of list items.
   * @param totalCount The number of list items.
   */  
  public function setTotalCount($totalCount)
  {
    $this->_totalCount = intval($totalCount);
  }

  /**
   * Get the number of list items.
   * @return The number of list items.
   */  
  public function getTotalCount()
  {
    return $this->_totalCount;
  }

  /**
   * Set the current page (1-based) (also sets the offset).
   * @param page The current page.
   */  
  public function setPage($page)
  {
    $this->_page = intval($page);
    $this->_offset = ($page - 1) * $this->_pageSize;
  }

  /**
   * Get the current page (1-based).
   * @return The current page.
   */  
  public function getPage()
  {
    return $this->_page;
  }

  /**
   * Get the size of a pages.
   * @return The size of a pages.
   */  
  public function getPageSize()
  {
    return $this->_pageSize;
  }

  /**
   * Get the number of pages.
   * @return The number of pages.
   */  
  public function getPageCount()
  {
    return ceil($this->_totalCount / $this->_pageSize);
  }

  /**
   * Set the current offset (also selects the page).
   * @param offset The current list offset.
   */  
  public function setOffset($offset)
  {
    $this->_offset = $offset;
    $this->_page = ceil(intval($offset) / $this->_pageSize) + 1;
  }

  /**
   * Get the current offset.
   * @return The offset.
   */  
  public function getOffset()
  {
    return $this->_offset;
  }

  /**
   * Determine if we are on the first page.
   * @return True/false.
   */  
  public function isOnFirstPage()
  {
    return $this->_page == 1;
  }

  /**
   * Determine if we are on the first page.
   * @return True/false.
   */  
  public function isOnLastPage()
  {
    return $this->_page == $this->getPageCount;
  }
}
?>
