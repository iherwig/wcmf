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

  private static $_null = null;
  private static $_count = null;

  /**
   * Get the NULL expression
   * @return Zend_Db_Expr
   */
  public static function NULL() {
    if (self::$_null == null) {
      self::$_null = new Zend_Db_Expr('NULL');
    }
    return self::$_null;
  }

  /**
   * Get the COUNT(*) expression
   * @return Zend_Db_Expr
   */
  public static function COUNT() {
    if (self::$_count == null) {
      self::$_count = new Zend_Db_Expr('COUNT(*)');
    }
    return self::$_count;
  }
}
?>