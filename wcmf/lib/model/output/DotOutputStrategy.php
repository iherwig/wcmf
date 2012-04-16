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
namespace wcmf\lib\model\output;

use wcmf\lib\core\Log;
use wcmf\lib\persistence\output\OutputStrategy;

/**
 * DotOutputStrategy outputs an object's content in a dot file.
 * @note file locking works not on NFS!
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DotOutputStrategy implements OutputStrategy {

  private $DEFAULT_NODE_STYLE = 'height=0.1,width=1,shape=box,style=filled,color="#49B4CF",fillcolor="#49B4CF",fontcolor=white,fontsize=14,fontname="Helvetica-Bold"';
  private $DEFAULT_EDGE_STYLE = 'arrowhead=none,arrowtail=none,color="#49B4CF"';

  private $_file = '';
  private $_fp = 0;
  private $_fileOk = false; // indicates if we can write to the file
  private $_nodeIndex = 0;

  private $_nodeStyle = '';
  private $_edgeStyle = '';

  /**
   * Constructor.
   * @param file The output file name.
   * @param nodeStyle Style definition to use for nodes (see dot documentation).
   * @param edgeStyle Style definition to use for edges (see dot documentation).
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
  }

  /**
   * @see OutputStrategy::writeHeader
   */
  public function writeHeader() {
    // check if file exists and is locked
    if (file_exists($this->_file)) {
      $this->_fp = fopen($this->_file, "r");
      if (!$this->_fp) {
        Log::warn("Can't write to file ".$this->_file.". Another user holds the lock. Try again later.", __CLASS__);
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
    if (!$obj->getProperty('nodeIndex')) {
      $obj->setProperty('nodeIndex', $this->getNextIndex());
    }
    if ($this->_fileOk) {
      fputs($this->_fp, '  n'.$obj->getProperty('nodeIndex').' [label="'.$obj->getOID().'"]'."\n");
      $children = $obj->getChildren();
      for($i=0; $i<sizeOf($children); $i++) {
        if (!$children[$i]->getProperty('nodeIndex')) {
          $children[$i]->setProperty('nodeIndex', $this->getNextIndex());
        }
        fputs($this->_fp, '  n'.$obj->getProperty('nodeIndex').' -> n'.$children[$i]->getProperty('nodeIndex')."\n");
      }
      fputs($this->_fp, "\n");
    }
  }

  /**
   * Get the next Node index.
   * @return The next Node index.
   */
  private function getNextIndex() {
    return $this->_nodeIndex++;
  }
}
?>
