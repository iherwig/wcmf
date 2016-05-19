<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model\mapper;

use \Zend_Db_Expr;

/**
 * Constant expression used in sql statements
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SQLConst {

  private static $null = null;
  private static $count = null;

  /**
   * Get the NULL expression
   * @return Zend_Db_Expr
   */
  public static function NULL() {
    if (self::$null == null) {
      self::$null = new Zend_Db_Expr('NULL');
    }
    return self::$null;
  }

  /**
   * Get the COUNT(*) expression
   * @return Zend_Db_Expr
   */
  public static function COUNT() {
    if (self::$count == null) {
      self::$count = new Zend_Db_Expr('COUNT(*)');
    }
    return self::$count;
  }
}
?>