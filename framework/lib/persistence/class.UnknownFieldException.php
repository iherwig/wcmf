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

/**
 * @class UnknownFieldException
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class UnknownFieldException extends Exception
{
  private $_field = '';

  /**
   * Constructor
   * @param field The name of the field
   * @param message The error message
   * @param code The error code
   * @param previous The previous Exception
   */
  public function __construct($field, $message="", $code=0, Exception $previous=null)
  {
    parent::__construct($message, $code, $previous);
    $this->_field = $field;
  }

  /**
   * Get the name of the field
   * @return String
   */
  public function getField()
  {
    return $this->_field;
  }
}
?>
