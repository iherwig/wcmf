<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model\output;

use wcmf\lib\core\LogManager;
use wcmf\lib\persistence\output\OutputStrategy;
use wcmf\lib\persistence\PersistentObject;

/**
 * DotOutputStrategy outputs an object's content in a dot file.
 * @note file locking works not on NFS!
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DotOutputStrategy implements OutputStrategy {

  private $DEFAULT_NODE_STYLE = 'height=0.1,width=1,shape=box,style=filled,color="#49B4CF",fillcolor="#49B4CF",fontcolor=white,fontsize=14,fontname="Helvetica-Bold"';
  private $DEFAULT_EDGE_STYLE = 'color="#49B4CF"';

  private $file = '';
  private $fp = 0;
  private $fileOk = false; // indicates if we can write to the file
  private $nodeIndex = 0;
  private $nodeIndexMap = [];
  private $writtenNodes = [];

  private $nodeStyle = '';
  private $edgeStyle = '';

  private static $logger = null;

  /**
   * Constructor.
   * @param $file The output file name.
   * @param $nodeStyle Style definition to use for nodes (see dot documentation).
   * @param $edgeStyle Style definition to use for edges (see dot documentation).
   */
  public function __construct($file, $nodeStyle='', $edgeStyle='') {
    $this->file = $file;
    $this->fileOk = false;

    if ($nodeStyle != '') {
      $this->nodeStyle = $nodeStyle;
    }
    else {
      $this->nodeStyle = $this->DEFAULT_NODE_STYLE;
    }
    if ($edgeStyle != '') {
      $this->edgeStyle = $edgeStyle;
    }
    else {
      $this->edgeStyle = $this->DEFAULT_EDGE_STYLE;
    }
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
  }

  /**
   * @see OutputStrategy::writeHeader
   */
  public function writeHeader() {
    // check if file exists and is locked
    if (file_exists($this->file)) {
      $this->fp = fopen($this->file, "r");
      if (!$this->fp) {
        self::$logger->warn("Can't write to file ".$this->file.". Another user holds the lock. Try again later.");
        return;
      }
      else {
        fclose($this->fp);
      }
    }
    // check if file exists and is locked
    $this->fp = fopen($this->file, "w");
    if ($this->fp) {
      if (flock ($this->fp, LOCK_EX)) {
        $this->fileOk = true;
        fputs($this->fp, "digraph G {\n\n");
        fputs($this->fp, "  node [".$this->nodeStyle."]\n");
        fputs($this->fp, "  edge [".$this->edgeStyle."]\n\n");
        return true;
      }
    }
  }

  /**
   * @see OutputStrategy::writeFooter
   */
  public function writeFooter() {
    if ($this->fileOk) {
      fputs($this->fp, "\n}\n");
      flock ($this->fp, LOCK_UN);
      fclose($this->fp);
    }
  }

  /**
   * @see OutputStrategy::writeObject
   */
  public function writeObject(PersistentObject $obj) {
    if ($this->isWritten($obj)) {
      return;
    }
    if ($this->fileOk) {
      fputs($this->fp, '  n'.$this->getIndex($obj).' [label="'.$obj->getDisplayValue().'"]'."\n");
      $children = $obj->getChildren(false);
      for($i=0; $i<sizeOf($children); $i++) {
        fputs($this->fp, '  n'.$this->getIndex($obj).' -> n'.$this->getIndex($children[$i])."\n");
      }
      fputs($this->fp, "\n");
    }
    $oidStr = $obj->getOID()->__toString();
    $this->writtenNodes[$oidStr] = true;
  }

  /**
   * Check if a node is written.
   * @param $node The node to check
   * @return Boolean
   */
  private function isWritten($node) {
    $oidStr = $node->getOID()->__toString();
    return (isset($this->writtenNodes[$oidStr]));
  }

  /**
   * Get the node index.
   * @param $node The node to get the index of
   * @return Number
   */
  private function getIndex($node) {
    $oidStr = $node->getOID()->__toString();
    if (!isset($this->nodeIndexMap[$oidStr])) {
      $this->nodeIndexMap[$oidStr] = $this->getNextIndex();
    }
    return $this->nodeIndexMap[$oidStr];
  }

  /**
   * Get the next node index.
   * @return Number
   */
  private function getNextIndex() {
    return $this->nodeIndex++;
  }
}
?>
