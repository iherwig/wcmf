<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model\mapper;

use Zend\Db\Sql\Predicate\Expression;

/**
 * Constant expression used in sql statements
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SQLConst extends Expression {

  private static $null = null;

  /**
   * Get the NULL expression
   * @return Expression
   */
  public static function NULL() {
    if (self::$null == null) {
      self::$null = new SQLConst('NULL');
    }
    return self::$null;
  }

  /**
   * Get the expression string
   * @return String
   */
  public function __toString() {
    return $this->getExpression();
  }
}
?>