<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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