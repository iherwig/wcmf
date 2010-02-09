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
require_once(BASE."wcmf/lib/presentation/class.ApplicationException.php");

/**
 * @class DionysosException
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DionysosException extends ApplicationException
{
  /**
   * Dionysos exception codes
   */
   
  /* general */
  const GENERAL_WARNING    = 1;
  const GENERAL_ERROR      = 2;
  const GENERAL_FATAL      = 3;
  const ACTION_INVALID     = 4;
  const SESSION_INVALID    = 5;
  const PARAMETER_INVALID  = 6;
  const OID_INVALID        = 7;
  const CLASS_NAME_INVALID = 8;
  
  /* login action */
  const AUTHENTICATION_FAILED = 100;

  /* logout action */
  
  /* list action */
  const LIMIT_NEGATIVE         = 200;
  const OFFSET_OUT_OF_BOUNDS   = 201;
  const SORT_FIELD_UNKNOWN     = 202;
  const SORT_DIRECTION_UNKNOWN = 203;
  
  /* read action */
  const DEPTH_INVALID = 300;
  
  /* update action */
  const ATTRIBUTE_NAME_INVALID  = 400;
  const ATTRIBUTE_VALUE_INVALID = 401;
  const CONCURRENT_UPDATE       = 402;

  /* create action */

  /* delete action */
  
  /* associate action */
  const ROLE_INVALID        = 600;
  const ASSOCIATION_INVALID = 601;

  /* disassociate action */
  const ASSOCIATION_NOT_FOUND = 700;
  
  /* actionSet action */

  /* log action */
  
  /* search action */
  const SEARCH_NOT_SUPPORTED = 800;
}
?>
