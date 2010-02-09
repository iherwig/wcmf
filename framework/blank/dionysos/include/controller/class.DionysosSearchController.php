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

require_once(BASE."wcmf/application/controller/class.AsyncPagingController.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/util/class.SearchUtil.php");
require_once 'Zend/Search/Lucene/Search/Highlighter/Interface.php';
/**
 * @class DionysosSearchController
 * @ingroup Controller
 * @brief .
 *
 * @author 	Niko <enikao@users.sourceforge.net>
 */
class DionysosSearchController extends AsyncPagingController
{
	private $searchData;
	private $query;

	/**
	 * @see AsyncPagingController::getObjects()
	 */
	function getObjects($type, $filter, $sortArray, &$pagingInfo)
	{
		global $g_sortCriteria;

		$index = SearchUtil::getIndex();

		$query = Zend_Search_Lucene_Search_QueryParser::parse($filter);

		if ($type) {
			$typeTerm = new Zend_Search_Lucene_Index_Term($type, 'type');
			$typeQuery = new Zend_Search_Lucene_Search_Query_Term($typeTerm);
			$parsedQuery = $query;
			$query = new Zend_Search_Lucene_Search_Query_Boolean();
			$query->addSubquery($parsedQuery, true);
			$query->addSubquery($typeQuery, true);
		}

		$this->query = $query;

		$results = null;

		$results = $index->find($query);

		$allOids = array();
		$relevance = array();
		$maxRelevance = 0;

		foreach ($results as $result) {
			$allOids[] = $result->oid;
			if (!array_key_exists($result->oid, $relevance)) {
				$relevance[$result->oid] = array(
					'relevance' => $result->score
				);
				$maxRelevance = max($maxRelevance, $result->score);
			}
		}

		$allOids = array_unique($allOids);
		$this->_response->setValue('maxRelevance', $maxRelevance);

		// update pagingInfo
		$totalCount = sizeof($allOids);

		// select the requested slice
		if ($pagingInfo->getPageSize() <= 0) {
			$size = $totalCount;
		} else {
			$size = $pagingInfo->getPageSize();
		}

		$start = ($pagingInfo->getPage() - 1) * $size;

		// load the objects
		$persistenceFacade = &PersistenceFacade::getInstance();
		$objects = array();

		$rightsManager = RightsManager::getInstance();
		$filteredObjects = array();

		if ((!$this->_request->getValue('sortByRelevance')) && count($sortArray) > 0) {
			for($i = 0; $i < sizeof($allOids); $i++) {
				$oid = $allOids[$i];
				if ($rightsManager->authorize($oid, '', ACTION_READ)) {
					$obj = $persistenceFacade->load($oid, BUILDEPTH_SINGLE);
					if ($obj) {
						$objects[] = $obj;
					} else {
						$totalCount--;
					}
				}
			}

			list($fieldName, $direction) = explode(' ', $sortArray[0]);

			$directionTag = strtolower($direction) == 'asc' ? SORTTYPE_ASC : SORTTYPE_DESC;

			$criteria  = array();
			$criteria [$fieldName] = $directionTag;
			$g_sortCriteria = $criteria;

			usort($objects, 'nodeCmpFunction');

			for ($i = $start; $i < sizeof($objects) && $i < ($start + $size); $i++) {
				$filteredObjects[] = $objects[$i];
				$oid = $objects[$i]->getOid();
				$this->searchData[$oid] = $relevance[$oid];
			}
		} else {
			$count = 0;
			for ($i = $start; $i < sizeof($allOids) && $count < $size; $i++) {
				$oid = $allOids[$i];
				if ($rightsManager->authorize($oid, '', ACTION_READ)) {
					$obj = $persistenceFacade->load($oid, BUILDEPTH_SINGLE);
					if ($obj) {
						$filteredObjects[] = $obj;
						$this->searchData[$oid] = $relevance[$oid];
						$count++;
					} else {
						$totalCount--;
					}
				}
			}
		}

		$pagingInfo->setTotalCount($totalCount);

		return $filteredObjects;
	}

	/**
	 * Modify the model passed to the view.
	 * @param nodes A reference to the array of node references passed to the view
	 */
	function modifyModel(&$nodes) {
		foreach ($nodes as $currNode) {
			// save words
			$valueNames = $currNode->getValueNames();
			foreach($valueNames as $currValueName) {
				list($valueType) = $currNode->getValueTypes($curValueName);
				if ($valueType == DATATYPE_ATTRIBUTE) {
					HighlightWordExtractor::resetWordStorage();
					$this->query->highlightMatches($currNode->getValue($curValueName), '', new HighlightWordExtractor());
					$newWords = HighlightWordExtractor::getWordStorage();
					$oldWords = array();
					$searchDataPart = $this->searchData[$currNode->getOid()];
					if (array_key_exists('matchingWords', $searchDataPart)) {
						$oldWords = $searchDataPart['matchingWords'];
					}
					$this->searchData[$currNode->getOid()]['matchingWords'] = array_merge_recursive($oldWords, $newWords);
				}
			}
		}

		$this->_response->setValue('searchData', $this->searchData);
	}
}

class HighlightWordExtractor implements Zend_Search_Lucene_Search_Highlighter_Interface {
	private static $words;

	private $doc;

	public function getDocument() {
		return $this->doc;
	}

	public function highlight($words) {
		self::$words[] = $words;
	}

	public function setDocument(Zend_Search_Lucene_Document_Html $document) {
		$this->doc = $document;
	}

	public static function resetWordStorage() {
		self::$words = array();
	}

	public static function getWordStorage() {
		return self::$words;
	}
}
