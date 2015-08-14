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

  private $_file = '';
  private $_fp = 0;
  private $_fileOk = false; // indicates if we can write to the file
  private $_nodeIndex = 0;
  private $_nodeIndexMap = array();
  private $_writtenNodes = array();

  private $_nodeStyle = '';
  private $_edgeStyle = '';

  private static $_logger = null;

  /**
   * Constructor.
   * @param $file The output file name.
   * @param $nodeStyle Style definition to use for nodes (see dot documentation).
   * @param $edgeStyle Style definition to use for edges (see dot documentation).
   */
  public function __construct($file, $nodeStyle='', $edgeStyle='') {
    $this->_file = $file;
    $this->_fileOk = false;

    if ($nodeStyle != '') {
      $this->_nodeStyle = $nodeStyle;
    }
    else {
      $this->_nodeStyle = $this->DEFAULT_NODE_STYLE;
    }
    if ($edgeStyle != '') {
      $this->_edgeStyle = $edgeStyle;
    }
    else {
      $this->_edgeStyle = $this->DEFAULT_EDGE_STYLE;
    }
    if (self::$_logger == null) {
      self::$_logger = LogManager::getLogger(__CLASS__);
    }
  }

  /**
   * @see OutputStrategy::writeHeader
   */
  public function writeHeader() {
    // check if file exists and is locked
    if (file_exists($this->_file)) {
      $this->_fp = fopen($this->_file, "r");
      if (!$this->_fp) {
        self::$_logger->warn("Can't write to file ".$this->_file.". Another user holds the lock. Try again later.");
        return;
      }
      else {
        fclose($this->_fp);
      }
    }
    // check if file exists and is locked
    $this->_fp = fopen($this->_file, "w");
    if ($this->_fp) {
      if (flock ($this->_fp, LOCK_EX)) {
        $this->_fileOk = true;
        fputs($this->_fp, "digraph G {\n\n");
        fputs($this->_fp, "  node [".$this->_nodeStyle."]\n");
        fputs($this->_fp, "  edge [".$this->_edgeStyle."]\n\n");
        return true;
      }
    }
  }

  /**
   * @see OutputStrategy::writeFooter
   */
  public function writeFooter() {
    if ($this->_fileOk) {
      fputs($this->_fp, "\n}\n");
      flock ($this->_fp, LOCK_UN);
      fclose($this->_fp);
    }
  }

  /**
   * @see OutputStrategy::writeObject
   */
  public function writeObject(PersistentObject $obj) {
    if ($this->isWritten($obj)) {
      return;
    }
    if ($this->_fileOk) {
      fputs($this->_fp, '  n'.$this->getIndex($obj).' [label="'.$obj->getDisplayValue().'"]'."\n");
      $children = $obj->getChildren(false);
      for($i=0; $i<sizeOf($children); $i++) {
        fputs($this->_fp, '  n'.$this->getIndex($obj).' -> n'.$this->getIndex($children[$i])."\n");
      }
      fputs($this->_fp, "\n");
    }
    $oidStr = $obj->getOID()->__toString();
    $this->_writtenNodes[$oidStr] = true;
  }

  /**
   * Check if a node is written.
   * @param $node The node to check
   * @return Boolean
   */
  private function isWritten($node) {
    $oidStr = $node->getOID()->__toString();
    return (isset($this->_writtenNodes[$oidStr]));
  }

  /**
   * Get the node index.
   * @param $node The node to get the index of
   * @return Number
   */
  private function getIndex($node) {
    $oidStr = $node->getOID()->__toString();
    if (!isset($this->_nodeIndexMap[$oidStr])) {
      $this->_nodeIndexMap[$oidStr] = $this->getNextIndex();
    }
    return $this->_nodeIndexMap[$oidStr];
  }

  /**
   * Get the next node index.
   * @return Number
   */
  private function getNextIndex() {
    return $this->_nodeIndex++;
  }
}
?>
