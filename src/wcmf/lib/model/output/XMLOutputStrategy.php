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
 * XMLOutputStrategy outputs an object's content in a xml file
 * using the default format.
 * @note file locking works not on NFS!
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class XMLOutputStrategy implements OutputStrategy {

  protected  $_id = 0;
  protected  $_file = '';
  protected  $_docType = '';
  protected  $_dtd = '';
  protected  $_encoding = '';
  protected  $_fp = 0;
  protected  $_indent = '';
  protected  $_linebreak = "\n";
  protected  $_tagsToClose = null;
  protected  $_lastIndent = -1;
  protected  $_fileOk = false; // indicates if we can write to the file

  /**
   * Constructor.
   * @param file The output file name.
   * @param docType The document type.
   * @param dtd The document type definition name.
   * @param encoding The used encoding (default: UTF-8).
   * @param indent The number of spaces to indent (default: 2).
   * @param linebreak The linebreak char to use (default: \n).
   */
  public function __construct($file, $docType, $dtd, $encoding='UTF-8', $indent=2, $linebreak="\n") {
    $this->_file = $file;
    $this->_docType = $docType;
    $this->_dtd = $dtd;
    $this->_encoding = $encoding;
    $this->_indent = str_repeat(' ', $indent);
    $this->_linebreak = $linebreak;
    $this->_tagsToClose = array();
    $this->_fileOk = false;
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
      if(!flock ($this->_fp, LOCK_EX)) {
        Log::warn("Can't lock file ".$this->_file.". Proceeding without.", __CLASS__);
      }
      $this->_fileOk = true;
      $this->writeToFile('<?xml version="1.0" encoding="'.$this->_encoding.'"?>'.$this->_linebreak.'<!DOCTYPE '.$this->_docType.' SYSTEM "'.$this->_dtd.'">'.$this->_linebreak);
      return true;
    }
  }

  /**
   * @see OutputStrategy::writeFooter
   */
  public function writeFooter() {
    if ($this->_fileOk) {
      // print remaining open tags
      for ($i=0;$i<sizeOf($this->_tagsToClose);$i++) {
        $closeTag = $this->_tagsToClose[$i];
        $this->writeToFile(str_repeat($this->_indent, $closeTag["indent"]).'</'.$closeTag["name"].'>'.$this->_linebreak);
      }
      flock ($this->_fp, LOCK_UN);
      fclose($this->_fp);
    }
  }

  /**
   * @see OutputStrategy::writeObject
   */
  public function writeObject(PersistentObject $obj) {
    if ($this->_fileOk) {
      $curIndent = $obj->getDepth();
      if ($curIndent < $this->_lastIndent) {
        // write last opened and not closed tags
        for ($i=$this->_lastIndent-$curIndent;$i>0;$i--) {
          $closeTag = array_shift($this->_tagsToClose);
          $this->writeToFile(str_repeat($this->_indent, $closeTag["indent"]).'</'.$closeTag["name"].'>'.$this->_linebreak);
        }
      }
      $tagName = $this->writeObjectContent($obj, $curIndent);
      if ($tagName != "") {
        // remember open tag if not closed
        if ($obj->getNumChildren() > 0) {
          $closeTag = array("name" => $tagName, "indent" => $curIndent);
          array_unshift($this->_tagsToClose, $closeTag);
        }
        else {
          //$this->writeToFile($this->_linebreak.str_repeat($this->_indent, $curIndent).'</'.$tagName.'>'.$this->_linebreak);
          $this->writeToFile('</'.$tagName.'>'.$this->_linebreak);
        }
        // remember current indent
        $this->_lastIndent = $curIndent;
      }
    }
  }

  /**
   * Actually write text to the file.
   * @note subclasses will override this to implement any final conversion.
   * @param text The text to write
   */
  protected function writeToFile($text) {
  	fputs($this->_fp, $text);
  }

  /**
   * Write the object's content including opening tag, excluding closing tag.
   * @param obj The object to write.
   * @param curIndent The current indent.
   * @return The name of the opening tag
   */
  protected function writeObjectContent(PersistentObject $obj, $curIndent) {
    // write object's content
    $elementName = $this->getElementName($obj);

    // open tag
    $this->writeToFile(str_repeat($this->_indent, $curIndent).'<'.$elementName);

    // write object id
    if ($this->isWritingOIDs()) {
      $this->writeToFile(' id="'.$obj->getOID().'"');
    }
    // write object attributes
    $attributeNames = $obj->getValueNames();
    foreach ($attributeNames as $curAttribute) {
      $this->writeAttribute(&$obj, $curAttribute);
    }
    // close tag
    $this->writeToFile('>');
    if ($obj->getNumChildren() > 0) {
      $this->writeToFile($this->_linebreak);
    }
    return $elementName;
  }

  /**
   * Determine whether the oid should be written to the file. The default implementation returns true.
   * @note subclasses will override this to implement special application requirements.
   * @return Boolean whether the oid should be written
   */
  protected function isWritingOIDs() {
    return true;
  }

  /**
   * Get the xml element name for an object. The default implementation returns the result of the getType() method.
   * @note subclasses will override this to implement special application requirements.
   * @param obj The object to write
   * @return The xml name of the element
   */
  protected function getElementName(PersistentObject $obj) {
    return $obj->getType();
  }

  /**
   * Get the xml element value for an object. The default implementation replaces newlines by <br>
   * and applies htmlspecialchars to the result.
   * @note subclasses will override this to implement special application requirements.
   * @param obj The object to write
   * @param name The name of the element
   * @param value The value to write
   * @return The xml value
   */
  protected function getElementValue(PersistentObject $obj, $name, $value) {
    return htmlspecialchars(preg_replace("/\r\n|\n\r|\n|\r/", "<br />", $value));
  }

  /**
   * Write an object value.
   * @note subclasses will override this to implement special application requirements.
   * @param obj The object to write
   * @param name The name of the value
   */
  protected function writeAttribute(PersistentObject $obj, $name) {
    $value = $obj->getValue($name);
    if ($value != '') {
      $value = $this->getAttributeValue($obj, $name, $value);
      $this->writeToFile(' '.$this->getAttributeName($obj, $name).'="'.$value.'"');
    }
  }

  /**
   * Get the xml attribute name for an object value. The default implementation returns the name of the attribute.
   * @note subclasses will override this to implement special application requirements.
   * @param obj The object to write
   * @param name The name of the attribute
   * @return The xml name of the attribute
   */
  protected function getAttributeName(PersistentObject $obj, $name) {
    return $name;
  }

  /**
   * Get the xml attribute value for an object value. The default implementation replaces newlines by <br>
   * and applies htmlspecialchars to the result.
   * @note subclasses will override this to implement special application requirements.
   * @param obj The object to write
   * @param name The name of the attribute
   * @param value The value to write
   * @return The xml value
   */
  protected function getAttributeValue(PersistentObject $obj, $name, $value) {
    return htmlspecialchars(preg_replace("/\r\n|\n\r|\n|\r/", "<br />", $value));
  }
}
?>
