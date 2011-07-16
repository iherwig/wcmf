<?php
/**
 * This script demonstrates how to output an object tree to a dot file
 */
error_reporting(E_ERROR | E_PARSE);
define("WCMF_BASE", realpath ("../../../")."/");
define("LOG4PHP_CONFIGURATION", "../log4php.properties");

require_once(WCMF_BASE."wcmf/lib/util/Log.php");
require_once(WCMF_BASE."wcmf/lib/util/InifileParser.php");
require_once(WCMF_BASE."wcmf/lib/model/Node.php");
require_once(WCMF_BASE."wcmf/lib/model/NodeIterator.php");
require_once(WCMF_BASE."wcmf/lib/persistence/PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/visitor/OutputVisitor.php");
require_once(WCMF_BASE."wcmf/lib/output/DotOutputStrategy.php");
require_once(WCMF_BASE."wcmf/lib/output/XMLOutputStrategy.php");

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
  $rootNode->addNode($node);
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
