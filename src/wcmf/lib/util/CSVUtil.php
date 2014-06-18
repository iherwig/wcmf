<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\util;

/**
 * CSVUtil provides basic support for csv file functionality.
 * The first line of a csv file is supposed to hold the field names.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class CSVUtil {

  var $_fields = array();

  /**
   * Read a CSV file into an array
   * @param filename The name of the CSV file
   * @param separator The field separator used in the CSV file (e.g. \\t)
   * @param fielddelimiter The field delimiter used in the CSV file (e.g. ")
   * @return An assoziative array with keys 'fields', 'values' where
   *         values is an array of arrays holding the values
   */
  public static function readCSVFile($filename, $separator, $fielddelimiter) {
    $result = array();
    $csv = fopen($filename, 'r');

    // get field definitions
    $this->_fields = self::getValues(fgets($csv, 4096), $separator, $fielddelimiter);
    $result['fields'] = $this->_fields;
    $result['values'] = array();

    // get values
    while (!feof ($csv)) {
      $line = fgets($csv, 4096);
      if (strlen($line) > 0) {
        $values = self::getValues($line, $separator, $fielddelimiter);
        $result['values'][] = $values;
      }
    }

    fclose ($csv);
    return $result;
  }

  /**
   * Get the values of of field from a line
   * @param line The line (represented as array returned from readCSVFile)
   * @param fieldName The name of the field
   * @return The value or false if not existing
   */
  private static function getFieldValue($line, $fieldName) {
    if (in_array($fieldName, $this->_fields)) {
      return $line[array_search($fieldName, $this->_fields)];
    }
    else {
      return false;
    }
  }

  /**
   * Get the values of of line
   * @param line The line to split
   * @param separator The field separator used in the CSV file (e.g. \\t)
   * @param fielddelimiter The field delimiter used in the CSV file (e.g. ")
   * @return An array of values
   */
  private static function getValues($line, $separator, $fielddelimiter) {
    $line = trim($line);
    $values = preg_split("/".$separator."/", $line);

    // strip fielddelimiter from values
    if (strlen($fielddelimiter) > 0) {
      for($i=0; $i<sizeof($values); $i++) {
        $values[$i] = trim($values[$i], $fielddelimiter);
      }
    }
    return $values;
  }
}
?>
