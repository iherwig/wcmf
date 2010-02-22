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
 * @class RDBManyToManyRelationDescription
 * @ingroup Persistence
 * @brief Instances of RDBManyToManyRelationDescription describe a many to many relation
 * from 'this' end to 'other' end  in a relational database.
 * This relation is always realized by a connecting database table and can be resolved
 * into a many-to-one relation from 'this' end to the relation type and a one-to-many relation
 * from the relation type to the 'other' end.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RDBManyToManyRelationDescription
{
  public $thisEndRelation = '';
  public $otherEndRelation = '';
  public $hierarchyType = 'child';

  /**
   * Constructor.
   * @see RelationDescription::__construct
   * @param thisEndRelation The RDBOneToManyRelationDescription describing the relation between 'this' end and the connecting type
   * @param oneToManyRelationDescription The RDBManyToOneRelationDescription describing the relation between the connecting type and the 'other' end
   */
  public function __construct(RDBOneToManyRelationDescription $thisEndRelation, RDBManyToOneRelationDescription $otherEndRelation)
  {
    $this->thisEndRelation = $thisEndRelation;
    $this->otherEndRelation = $otherEndRelation;
  }

  /**
   * Delegate property access to contained relation descriptions.
   */
  public function __get($propName)
  {
    if (strpos($propName, 'this') === 0) {
      return $this->thisEndRelation->$propName;
    }
    elseif (strpos($propName, 'other') === 0) {
      return $this->otherEndRelation->$propName;
    }
  }
}
?>
