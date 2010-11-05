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
 * $Id: WCMFTestSuite.php 998 2009-05-29 01:29:20Z iherwig $
 */
require_once("PHPUnit/Framework/TestSuite.php");
require_once(BASE."wcmf/lib/util/class.InifileParser.php");

/**
 * @class WCMFTestSuite
 * @ingroup test
 * @brief WCMFTestSuite is a PHPUnit test suite, that
 * prepares wCMF tests.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class WCMFTestSuite extends PHPUnit_Framework_TestSuite
{
  protected function setUp()
  {
    // get configuration from file
    $GLOBALS['CONFIG_PATH'] = BASE.'application/include/';
    $configFile = $GLOBALS['CONFIG_PATH'].'config.ini';
    $parser = InifileParser::getInstance();
    $parser->parseIniFile($configFile, true);
  }

  protected function tearDown()
  {
  }
}

?>