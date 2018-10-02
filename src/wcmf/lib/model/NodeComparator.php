<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model;

use wcmf\lib\persistence\ObjectComparator;

/**
 * NodeComparator exists for compatibility reasons only.
 * It does not add any functionality to the base class.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeComparator extends ObjectComparator {
  const SORTTYPE_ASC = -1;  // sort children ascending
  const SORTTYPE_DESC = -2; // sort children descending
  const ATTRIB_OID = -3;  // sort by oid
  const ATTRIB_TYPE = -4; // sort by type
}
?>
