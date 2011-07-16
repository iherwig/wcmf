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
require_once(WCMF_BASE.'wcmf/lib/util/InifileParser.php');
require_once(WCMF_BASE.'wcmf/lib/persistence/StateChangeEvent.php');

$includePath = get_include_path();
if (strpos($includePath, 'Zend') === false) {
  set_include_path(get_include_path().PATH_SEPARATOR.WCMF_BASE.'wcmf/3rdparty/zend');
}
require_once('Zend/Search/Lucene.php');

function gShutdownSearch()
{
  LuceneSearch::commitIndex();
}
register_shutdown_function('gShutdownSearch');

// listen to object change events
EventManager::getInstance()->addListener(StateChangeEvent::NAME,
  array('LuceneSearch', 'stateChanged'));

/**
 * @class LuceneSearch
 * @ingroup Util
 * @brief This class provides access to the search based on Zend_Search_Lucene.
 * The search index stored in the location that is defined by the configuration key 'indexPath'
 * in the configuration section 'search'. To manage PersistentObjects in the index use the
 * methods LuceneSearch::indexInSearch() and LuceneSearch::deleteFromSearch() and LuceneSearch::commitIndex().
 * The method LuceneSearch::getIndex() offers direct access to the search index for advanced operations.
 *
 * @author Niko <enikao@users.sourceforge.net>
 */
class LuceneSearch
{
  const INI_SECTION = 'search';
  const INI_INDEX_PATH = 'indexPath';

  private static $index;
  private static $indexPath;
  private static $indexIsDirty = false;

  /**
   * Get the search index.
   * @param create True/False wether to create the index, if it does not exist [default: true]
   * @return An instance of Zend_Search_Lucene_Interface
   */
  public static function getIndex($create = true)
  {
    if (!self::$index && $create)
    {
      $indexPath = self::getIndexPath();

      Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive());
      if (defined('Zend_Search_Lucene_Search_Query_Wildcard')) {
        Zend_Search_Lucene_Search_Query_Wildcard::setMinPrefixLength(0);
      }
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

  /**
   * Reset the search index.
   */
  public static function resetIndex()
  {
    $indexPath = self::getIndexPath();

    return Zend_Search_Lucene::create($indexPath);
  }

  /**
   * Add a PersistentObject instance to the search index. This method modifies the
   * index. For that reason LuceneSearch::commitIndex() should be called afterwards.
   * @param obj The PersistentObject instance.
   */
  public static function indexInSearch(PersistentObject $obj)
  {
    if (self::isIndexInSearch($obj))
    {
      Log::debug("Add/Update index for: ".$obj->getOID(), __CLASS__);
      $index = self::getIndex();
      $encoding = new EncodingUtil();

      $doc = new Zend_Search_Lucene_Document();

      $valueNames = $obj->getValueNames();

      $doc->addField(Zend_Search_Lucene_Field::unIndexed('oid', $obj->getOID()->__toString(), 'utf-8'));
      $typeField = Zend_Search_Lucene_Field::keyword('type', $obj->getType(), 'utf-8');
      $typeField->isStored = false;
      $doc->addField($typeField);

      foreach ($valueNames as $currValueName)
      {
        $inputType = $obj->getProperty('input_type');
        $value = $obj->getValue($currValueName);
        switch($inputType)
        {
          case 'text':
            $doc->addField(Zend_Search_Lucene_Field::unStored($currValueName, $encoding->convertIsoToCp1252Utf8($value), 'utf-8'));
            break;

          case 'fckeditor':
            $doc->addField(Zend_Search_Lucene_Field::unStored($currValueName,
              html_entity_decode($encoding->convertIsoToCp1252Utf8(strip_tags($value)), ENT_QUOTES,'utf-8'), 'utf-8'));
            break;

          default:
            if (is_scalar($value)) {
              $field = Zend_Search_Lucene_Field::keyword($currValueName, $value, 'utf-8');
              $field->isStored = false;
              $doc->addField($field);
            }
        }
      }

      $term = new Zend_Search_Lucene_Index_Term($obj->getOID()->__toString(), 'oid');
      $docIds  = $index->termDocs($term);
      foreach ($docIds as $id)
      {
        $index->delete($id);
      }

      $index->addDocument($doc);
      self::$indexIsDirty = true;
    }
  }

  /**
   * Delete a PersistentObject instance from the search index.
   * @param obj The PersistentObject instance.
   */
  public static function deleteFromSearch(PersistentObject $obj)
  {
    if (self::isIndexInSearch($obj))
    {
      Log::debug("Delete from index: ".$obj->getOID(), __CLASS__);
      $index = self::getIndex();

      $term = new Zend_Search_Lucene_Index_Term($obj->getOID()->__toString(), 'oid');
      $docIds  = $index->termDocs($term);
      foreach ($docIds as $id)
      {
        $index->delete($id);
      }
      self::$indexIsDirty = true;
    }
  }

  /**
   * Commit any changes made by using LuceneSearch::indexInSearch() and SearchIndex::deleteFromSearch().
   * @note By default this method only commits the index if changes were made using the methods mentioned above.
   * If you want to make sure that the index is committed in any case, set forceCommit to true.
   * @param forceCommit True/False wether the index should be committed even if no changes were made
   *   using the methods mentioned above [default: false].
   */
  public static function commitIndex($forceCommit = false)
  {
    Log::debug("Commit index", __CLASS__);
    if (self::$indexIsDirty || $forceCommit)
    {
      $index = self::getIndex(false);
      if ($index) {
        $index->commit();
      }
    }
  }

  /**
   * Get the path to the index.
   * @return The path.
   */
  private static function getIndexPath()
  {
    if (!self::$indexPath)
    {
      $parser = InifileParser::getInstance();
      if (($path = $parser->getValue(self::INI_INDEX_PATH, self::INI_SECTION)) !== false)
      {
        self::$indexPath = WCMF_BASE . 'application/' . $path;

        if (!file_exists(self::$indexPath)) {
          FileUtil::mkdirRec(self::$indexPath);
        }

        if (!is_writeable(self::$indexPath)) {
          Log::error("Index path '".self::$indexPath."' is not writeable.", __CLASS__);
        }

        Log::debug("Lucene index location: ".self::$indexPath, __CLASS__);
      }
      else
      {
        Log::error($parser->getErrorMsg(), __CLASS__);
      }
    }
    return self::$indexPath;
  }

  /**
   * Check if the instance object is contained in the search index
   * (defined by the property 'is_searchable')
   * @param obj PersistentObject instance
   * @return True/False wether the object is contained or not
   */
  private static function isIndexInSearch(PersistentObject $obj)
  {
    return (boolean) $obj->getProperty('is_searchable');
  }

  /**
   * Listen to StateChangeEvents
   * @param event StateChangeEvent instance
   */
  public static function stateChanged(StateChangeEvent $event)
  {
    $object = $event->getObject();
    $newState = $event->getNewValue();
    switch ($newState)
    {
      case PersistentObject::STATE_NEW:
        self::indexInSearch($object);
        break;

      case PersistentObject::STATE_DIRTY:
        self::indexInSearch($object);
        break;

      case PersistentObject::STATE_DELETED:
        self::deleteFromSearch($object);
        break;
    }
  }
}
