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
namespace test\lib;

use \PDO;
use wcmf\lib\config\InifileParser;

/**
 * ControllerTestCase is a PHPUnit test case, that
 * serves as base class for test cases used for Controllers.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase {
  // only instantiate pdo once for test clean-up/fixture load
  private static $pdo = null;

  // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
  private $conn = null;

  public final function getConnection() {
    if ($this->conn === null) {
      $parser = InifileParser::getInstance();
      $params = $parser->getSection('database');
      if (self::$pdo == null) {
        self::$pdo = new PDO($params['dbType'].':host='.$params['dbHostName'].';dbname='.$params['dbName'], $params['dbUserName'], $params['dbPassword']);
      }
      $this->conn = $this->createDefaultDBConnection(self::$pdo, $params['dbName']);
    }

    return $this->conn;
  }
}
?>