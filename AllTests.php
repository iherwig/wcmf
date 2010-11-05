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
 * $Id: AllTests.php 1038 2009-08-10 16:06:33Z iherwig $
 */

/**
 * This script is the entry point for test execution.
 * See readme.txt
 */
error_reporting(E_ALL | E_PARSE);

require_once("base_dir.php");
require_once(BASE."wcmf/lib/core/AutoLoader.php");

$path = realpath("./lib/phpunit");
set_include_path(get_include_path().PATH_SEPARATOR.$path);

define("TEST_DIR", "tests");

require_once("PHPUnit/TextUI/TestRunner.php");
require_once("lib/WCMFTestSuite.php");
require_once("lib/WCMFTestCase.php");

/**
 * Read the ignore list
 */
$IGNORE_LIST = array();
$content = file_get_contents(TEST_DIR.'/ignore.txt');
$IGNORE_LIST = preg_split('/\n/', $content);

/**
 * Read the test scripts from TEST_DIR folder
 */
$TESTS = array();
if ($dh = opendir(TEST_DIR))
{
  while(($file = readdir($dh)) !== false)
  {
    if (preg_match('/Test\.php$/', $file) && !in_array($file, $IGNORE_LIST)) {
      $TESTS[] = preg_replace('/\.php$/', '', $file);
    }
  }
  closedir($dh);
}
foreach($TESTS as $test) {
  require_once(TEST_DIR."/".$test.".php");
}
/**
 * Setup the test suite
 */
$suite = new WCMFTestSuite();
foreach($TESTS as $test) {
  $suite->addTestSuite($test);
}
/**
 * Run the tests
 */
ob_start();
PHPUnit_TextUI_TestRunner::run($suite);
$result = ob_get_contents();
ob_clean();
?>
<html>
<head>
  <title>wCMF Test Results</title>
</head>
<body>
<pre>
Running tests:<br />
<?php
foreach ($TESTS as $test) {
  echo $test."<br />";
}
echo "<br />";
echo $result;
?>
</pre>
</body>
</html>