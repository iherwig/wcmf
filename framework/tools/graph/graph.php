<?php
/**
 * This script demonstrates how to output an object tree to a dot file
 */
error_reporting(E_ERROR | E_PARSE);
define("BASE", realpath ("../../../")."/");
define("LOG4PHP_CONFIGURATION", "../log4php.properties");

require_once(BASE."wcmf/lib/util/class.Log.php");
require_once(BASE."wcmf/lib/util/class.InifileParser.php");
require_once(BASE."wcmf/lib/model/class.Node.php");
require_once(BASE."wcmf/lib/model/class.NodeIterator.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/visitor/class.OutputVisitor.php");
require_once(BASE."wcmf/lib/output/class.DotOutputStrategy.php");
require_once(BASE."wcmf/lib/output/class.XMLOutputStrategy.php");

$parser = &InifileParser::getInstance();
$parser->parseIniFile('config.ini', true);

// get root oids
$oids = array();
$rootTypes = $parser->getValue('rootTypes', 'cms');
if (is_array($rootTypes))
{
  $persistenceFacade = PersistenceFacade::getInstance();
  foreach($rootTypes as $rootType)
  {
    Log::debug("getting oids for: ".$rootType, "graph");
    $oids = array_merge($oids, $persistenceFacade->getOIDs($rootType));
    Log::debug($oids, "graph");
  }
}

// construct tree from root oids
$persistenceFacade = PersistenceFacade::getInstance();
$rootNode = new Node("root");
foreach($oids as $oid)
{
  $node = $persistenceFacade->load($oid, BUILDDEPTH_INFINITE);
  $rootNode->addChild($node);
}

// output tree to dot
$filename = "graph.dot";
$os = new DotOutputStrategy($filename);
$ov = new OutputVisitor($os);
$nIter = new NodeIterator($rootNode);
$ov->startIterator($nIter);
Log::info("produced file: ".$filename, "graph");
Log::info("use dot to produce image: dot -Tpng ".$filename." > graph.png", "graph");
?>
