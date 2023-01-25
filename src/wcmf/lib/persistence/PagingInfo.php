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

  private int $pageSize = 10;
  private int $page = 1;
  private int $offset = 0;
  private int $totalCount = 0;
  private bool $ignoreTotalCount = false;

  /**
   * Creates a PagingInfo object. The ignoreTotalCount parameter may be
   * set to true, if the count is to be ignored. This may speed up loading
   * of objects, because an extra count query may be omitted.
   * @param int $pageSize The pageSize (PagingInfo::SIZE_INFINITE to set no page size)
   * @param bool $ignoreTotalCount Boolean whether this instance ignores the
   *    total count or not (optional, default: _false_)
   */
  public function __construct(int $pageSize, bool $ignoreTotalCount=false) {
    $this->pageSize = intval($pageSize);
    if ($this->pageSize == self::SIZE_INFINITE) {
      $this->pageSize = PHP_INT_MAX;
    }
    $this->ignoreTotalCount = $ignoreTotalCount;
  }

  /**
   * Set the number of list items.
   * @param int $totalCount The number of list items.
   */
  public function setTotalCount(int $totalCount): void {
    $this->totalCount = intval($totalCount);
  }

  /**
   * Get the number of list items.
   * @return int
   */
  public function getTotalCount(): int {
    return $this->totalCount;
  }

  /**
   * Set the current page (1-based) (also sets the offset).
   * @param int $page The current page.
   */
  public function setPage(int $page): void {
    $this->page = intval($page);
    $this->offset = ($page - 1) * $this->pageSize;
  }

  /**
   * Get the current page (1-based).
   * @return int
   */
  public function getPage(): int {
    return $this->page;
  }

  /**
   * Get the size of a pages.
   * @return int
   */
  public function getPageSize(): int {
    return $this->pageSize;
  }

  /**
   * Get the number of pages.
   * @return int
   */
  public function getPageCount(): int {
    return intval(ceil($this->totalCount / $this->pageSize));
  }

  /**
   * Set the current offset (also selects the page).
   * @param int $offset The current list offset.
   */
  public function setOffset(int $offset): void {
    $this->offset = $offset;
    $this->page = intval(ceil(intval($offset) / $this->pageSize)) + 1;
  }

  /**
   * Get the current offset.
   * @return int
   */
  public function getOffset(): int {
    return $this->offset;
  }

  /**
   * Determine if we are on the first page.
   * @return bool
   */
  public function isOnFirstPage(): bool {
    return $this->page == 1;
  }

  /**
   * Determine if we are on the first page.
   * @return bool
   */
  public function isOnLastPage(): bool {
    return $this->page == $this->getPageCount();
  }

  /**
   * Check if this instance iignores the total count.
   * @return bool
   */
  public function isIgnoringTotalCount(): bool {
    return $this->ignoreTotalCount;
  }

  /**
   * Get the pages for a pagination navigation
   * @param string $urlPattern Url string to use containing literal {page}, that will be replaced
   * @param int $maxDisplayPages Maximum number of pages to display (optional, default: 10)
   * @return array{first: array{num: int, url: string}, last: array{num: int, url: string}, current: array{num: int, url: string},
   *  prev: array{num: int, url: string}|null, next: array{num: int, url: string}|null, pages: array<array{num: int, url: string}>} or null, if page count <= 1
   */
  public function getPagination(string $urlPattern, int $maxDisplayPages=10): ?array {
    if ($this->getPageCount() <= 1) {
      return null;
    }

    // calculate pages
    $getPage = function(int $val) use ($urlPattern): array {
      return ['num' => $val, 'url' => str_replace('{page}', ''.$val, $urlPattern)];
    };

    $first = 1;
    $last = $this->getPageCount();
    $page = $this->getPage();

    $halfRange = floor($maxDisplayPages/2);
    $startPage = ($page < $halfRange) ? $first : intval(max([$page-$halfRange, $first]));
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
