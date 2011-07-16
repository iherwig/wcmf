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
require_once(WCMF_BASE."wcmf/lib/util/Message.php");

/**
 * @class CSVUtil
 * @ingroup Util
 * @brief CSVUtil provides basic support for csv file functionality.
 * The first line of a csv file is supposed to hold the field names.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class CSVUtil
{
  var $_errorMsg = '';
  var $_fields = array();

  /**
   * Get last error message.
   * @return The error string
   */
  function getErrorMsg()
  {
    return $this->_errorMsg;
  }
  /**
   * Read a CSV file into an array
   * @param filename The name of the CSV file
   * @param separator The field separator used in the CSV file (e.g. \\t)
   * @param fielddelimiter The field delimiter used in the CSV file (e.g. ")
   * @result An assoziative array with keys 'fields', 'values' where
   *         values is an array of arrays holding the values
   */  
  function readCSVFile($filename, $separator, $fielddelimiter)
  {
    $result = array();
    $csv = fopen($filename, 'r');
    
    // get field definitions
    $this->_fields = CSVUtil::getValues(fgets($csv, 4096), $separator, $fielddelimiter); 
    $result['fields'] = $this->_fields;
    $result['values'] = array();
    
    // get values
    while (!feof ($csv)) 
    { 
      $line = fgets($csv, 4096);
      if (strlen($line) > 0)
      {
        $values = CSVUtil::getValues($line, $separator, $fielddelimiter);
        array_push($result['values'], $values);
      }
    } 
    
    fclose ($csv); 
    return $result;
  }
  /**
   * Get the values of of field from a line
   * @param line The line (represented as array returned from readCSVFile)
   * @param fieldName The name of the field
   * @result The value or false if not existing
   */  
  function getFieldValue($line, $fieldName)
  {
    if (in_array($fieldName, $this->_fields))
      return $line[array_search($fieldName, $this->_fields)];
    else
      return false;
  }
  /**
   * Get the values of of line
   * @param line The line to split
   * @param separator The field separator used in the CSV file (e.g. \\t)
   * @param fielddelimiter The field delimiter used in the CSV file (e.g. ")
   * @result An array of values
   */  
  function getValues($line, $separator, $fielddelimiter)
  {
    $line = trim($line);
    $values = preg_split("/".$separator."/", $line);

    // strip fielddelimiter from values
    if (strlen($fielddelimiter) > 0)
      for($i=0; $i<sizeof($values); $i++)
        $values[$i] = trim($values[$i], $fielddelimiter);

    return $values;
  }
}
?>
