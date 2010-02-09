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
set_include_path(get_include_path().PATH_SEPARATOR.BASE.'wcmf/3rdparty/zend');

require_once BASE.'wcmf/3rdparty/zend/Zend/Search/Lucene.php';
require_once BASE.'wcmf/lib/util/class.InifileParser.php';

/**
 * @class SearchUtil
 * @ingroup Util
 * @brief This class provides access to the search based on Zend_Search_Lucene.
 *
 * @author 	Niko <enikao@users.sourceforge.net>
 */
class SearchUtil
{
	const INI_SECTION = 'search';
	const INI_INDEX_PATH = 'indexPath';

	private static $index;
	private static $indexPath;

	public static function getIndex($create = true)
	{
		if (!self::$index && $create)
		{
			$indexPath = self::getIndexPath();

			Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive());
			Zend_Search_Lucene_Search_Query_Wildcard::setMinPrefixLength(0);
			Zend_Search_Lucene_Search_QueryParser::setDefaultOperator(Zend_Search_Lucene_Search_QueryParser::B_AND);

			try {
				self::$index = Zend_Search_Lucene::open($indexPath);
			}
			catch (Zend_Search_Lucene_Exception $ex) {
				self::$index = self::resetIndex();
			}
		}
		return self::$index;
	}

	public static function resetIndex() {
		$indexPath = self::getIndexPath();
		 
		return Zend_Search_Lucene::create($indexPath);
	}

	private static function getIndexPath()
	{
		if (!self::$indexPath)
		{
			$parser = InifileParser::getInstance();
			if (($path = $parser->getValue(self::INI_INDEX_PATH, self::INI_SECTION)) !== false)
			{
				self::$indexPath = BASE . 'application/' . $path;

				if (!file_exists(self::$indexPath)) {
					FileUtil::mkdirRec(self::$indexPath);
				}

				if (!is_writeable(self::$indexPath)) {
					Log::error("Index path '".self::$indexPath."' is not writeable.", __CLASS__);
				}

				Log::info("Lucene index location: ".self::$indexPath, __CLASS__);
			}
			else
			{
				Log::error($parser->getErrorMsg(), __CLASS__);
			}
		}
		return self::$indexPath;
	}
}
