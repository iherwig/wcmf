<?
/**
 *  constants.php
 *
 * +===========================================================================================+
 * | Constants that are used across all the php files of the project are defined here to prevent
 * | the inclusion of unnecessary code.
 * |
 * +-------------------------------------------------------------------------------------------+
 * | Copyright:
 * |
 * | constants.php: Constant defines used by all the XmlDb code
 * |
 * | Copyright (C) 2001 Nigel Swinson, Nigel@Swinson.com
 * |
 * | This program is free software; you can redistribute it and/or
 * | modify it under the terms of the GNU General Public License
 * | as published by the Free Software Foundation; either version 2
 * | of the License, or (at your option) any later version.
 * |
 * | This program is distributed in the hope that it will be useful,
 * | but WITHOUT ANY WARRANTY; without even the implied warranty of
 * | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * | GNU General Public License for more details.
 * |
 * | You should have received a copy of the GNU General Public License
 * | along with this program; if not, write to the Free Software
 * | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 * +===========================================================================================+
 *
 * @author  Nigel Swinson
 * @link    http://sourceforge.net/projects/phpxmldb/
 * @CVS $Id$
 */

// Flags for specifying permissions.  In general there will be a boolean flag which
// either specifies a secure mode, which means that only those items with ENABLE
// set will be accessible, or the secure flag will be false, in which case only those
// functions set to DISABLE will not be accessible.
define('XMLDB_PERMISSION_DISABLE', 0x0);
define('XMLDB_PERMISSION_INHERIT', 0x1);
define('XMLDB_PERMISSION_ENABLE', 0x2);

?>