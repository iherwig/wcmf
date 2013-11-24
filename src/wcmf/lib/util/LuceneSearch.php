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
namespace wcmf\lib\util;

use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\StateChangeEvent;
use wcmf\lib\util\LuceneSearch;

$includePath = get_include_path();
if (strpos($includePath, 'Zend') === false) {
  set_include_path(get_include_path().PATH_SEPARATOR.WCMF_BASE.'wcmf/vendor/zend');
}
require_once('Zend/Search/Lucene.php');

function gShutdownSearch() {
  LuceneSearch::commitIndex();
  ObjectFactory::getInstance('eventManager')->removeListener(StateChangeEvent::NAME,
    array('LuceneSearch', 'stateChanged'));
}
register_shutdown_function('gShutdownSearch');

// listen to object change events
ObjectFactory::getInstance('eventManager')->addListener(StateChangeEvent::NAME,
  array('LuceneSearch', 'stateChanged'));

/**
 * LuceneSearch provides access to the search based on Zend_Search_Lucene.
 * The search index stored in the location that is defined by the configuration key 'indexPath'
 * in the configuration section 'search'. To manage PersistentObjects in the index use the
 * methods LuceneSearch::indexInSearch() and LuceneSearch::deleteFromSearch() and LuceneSearch::commitIndex().
 * The method LuceneSearch::getIndex() offers direct access to the search index for advanced operations.
 *
 * @author Niko <enikao@users.sourceforge.net>
 */
class LuceneSearch {

  const INI_SECTION = 'search';
  const INI_INDEX_PATH = 'indexPath';

  private static $isActivated = null;
  private static $index;
  private static $indexPath;
  private static $indexIsDirty = false;

  /**
   * Get the search index.
   * @param create Boolean whether to create the index, if it does not exist [default: true]
   * @return An instance of Zend_Search_Lucene_Interface or null
   */
  public static function getIndex($create = true) {
    if (!self::isActivated()) {
      return null;
    }
    if (!self::$index && $create) {
      $indexPath = self::getIndexPath();

      $analyzer = new Analyzer();

      // add stop words filter
      $stopWords = self::getStopWords();
      $stopWordsFilter = new Zend_Search_Lucene_Analysis_TokenFilter_StopWords($stopWords);
      $analyzer->addFilter($stopWordsFilter);

      Zend_Search_Lucene_Analysis_Analyzer::setDefault($analyzer);
      Zend_Search_Lucene_Search_Query_Wildcard::setMinPrefixLength(0);
      Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('UTF-8');
      Zend_Search_Lucene_Search_QueryParser::setDefaultOperator(Zend_Search_Lucene_Search_QueryParser::B_AND);

      try {
        self::$index = Zend_Search_Lucene::open($indexPath);
        //self::$index->setMaxMergeDocs(5);
        //self::$index->setMergeFactor(5);
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
  public static function resetIndex() {
    if (!self::isActivated()) {
      return;
    }
    $indexPath = self::getIndexPath();
    return Zend_Search_Lucene::create($indexPath);
  }

  /**
   * Add a PersistentObject instance to the search index. This method modifies the
   * index. For that reason LuceneSearch::commitIndex() should be called afterwards.
   * @param obj The PersistentObject instance.
   */
  public static function indexInSearch(PersistentObject $obj) {
    if (!self::isActivated()) {
      return;
    }
    if (self::isIndexInSearch($obj)) {
      Log::debug("Add/Update index for: ".$obj->getOID(), __CLASS__);
      $index = self::getIndex();

      $doc = new Zend_Search_Lucene_Document();

      $valueNames = $obj->getValueNames();

      $doc->addField(Zend_Search_Lucene_Field::unIndexed('oid', $obj->getOID()->__toString(), 'UTF-8'));
      $typeField = Zend_Search_Lucene_Field::keyword('type', $obj->getType(), 'UTF-8');
      $typeField->isStored = false;
      $doc->addField($typeField);

      foreach ($valueNames as $curValueName) {
        $inputType = $obj->getProperty('input_type');
        $value = self::encodeValue($obj->getValue($curValueName), $inputType);
        if (preg_match('/^text|^f?ckeditor/', $inputType)) {
          $value = strip_tags($value);
          $doc->addField(Zend_Search_Lucene_Field::unStored($curValueName, $value, 'UTF-8'));
        }
        else {
          $field = Zend_Search_Lucene_Field::keyword($curValueName, $value, 'UTF-8');
          $field->isStored = false;
          $doc->addField($field);
        }
      }

      $term = new Zend_Search_Lucene_Index_Term($obj->getOID()->__toString(), 'oid');
      $docIds  = $index->termDocs($term);
      foreach ($docIds as $id) {
        $index->delete($id);
      }

      $index->addDocument($doc);
      self::$indexIsDirty = true;
    }
  }

  private static function encodeValue($value, $inputType)
  {
    if (preg_match('/^f?ckeditor/', $inputType)) {
      $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }
    return trim($value);
  }

  /**
   * Delete a PersistentObject instance from the search index.
   * @param obj The PersistentObject instance.
   */
  public static function deleteFromSearch(PersistentObject $obj) {
    if (!self::isActivated()) {
      return;
    }
    if (self::isIndexInSearch($obj)) {
      Log::debug("Delete from index: ".$obj->getOID(), __CLASS__);
      $index = self::getIndex();

      $term = new Zend_Search_Lucene_Index_Term($obj->getOID()->__toString(), 'oid');
      $docIds  = $index->termDocs($term);
      foreach ($docIds as $id) {
        $index->delete($id);
      }
      self::$indexIsDirty = true;
    }
  }

  /**
   * Commit any changes made by using LuceneSearch::indexInSearch() and SearchIndex::deleteFromSearch().
   * @note This method only commits the index if changes were made using the methods mentioned above.
   * @param optimize Boolean whether the index should be optimized after commit [default: true].
   */
  public static function commitIndex($forceCommit = false) {
    if (!self::isActivated()) {
      return;
    }
    Log::debug("Commit index", __CLASS__);
    if (self::$indexIsDirty) {
      $index = self::getIndex(false);
      if ($index) {
        $index->commit();
        if ($optimize) {
          $index->optimize();
        }
      }
    }
  }

  /**
   * Optimize the index
   */
  public static function optimizeIndex() {
    if (!self::isActivated()) {
      return;
    }
    $index = self::getIndex(false);
    if ($index) {
      $index->optimize();
    }
  }

  /**
   * Check if a index path is defined in the configuration.
   * @return Boolean
   */
  public static function isActivated() {
    if (self::$isActivated === null) {
      $config = ObjectFactory::getConfigurationInstance();
      self::$isActivated = $config->getValue(self::INI_INDEX_PATH, self::INI_SECTION) !== false;
    }
    return self::$isActivated;
  }

  /**
   * Get the path to the index.
   * @return The path.
   */
  private static function getIndexPath() {
    if (!self::$indexPath) {
      $config = ObjectFactory::getConfigurationInstance();
      $path = $config->getValue(self::INI_INDEX_PATH, self::INI_SECTION);
      self::$indexPath = realpath($path);

      if (!file_exists(self::$indexPath)) {
        FileUtil::mkdirRec(self::$indexPath);
      }

      if (!is_writeable(self::$indexPath)) {
        Log::error("Index path '".self::$indexPath."' is not writeable.", __CLASS__);
      }

      Log::debug("Lucene index location: ".self::$indexPath, __CLASS__);
    }
    return self::$indexPath;
  }

  /**
   * Check if the instance object is contained in the search index
   * (defined by the property 'is_searchable')
   * @param obj PersistentObject instance
   * @return Boolean wether the object is contained or not
   */
  private static function isIndexInSearch(PersistentObject $obj) {
    return (boolean) $obj->getProperty('is_searchable');
  }

  /**
   * Listen to StateChangeEvents
   * @param event StateChangeEvent instance
   */
  public static function stateChanged(StateChangeEvent $event) {
    $object = $event->getObject();
    $oldState = $event->getOldValue();
    $newState = $event->getNewValue();
    if (($oldState == PersistentObject::STATE_NEW || $oldState == PersistentObject::STATE_DIRTY)
            && $newState == PersistentObject::STATE_CLEAN) {
      self::indexInSearch($object);
    }
    elseif ($newState == PersistentObject::STATE_DELETED) {
      self::deleteFromSearch($object);
    }
  }

  /**
   * Get a list of words that are forbidden to search for
   * @return Array
   */
  public static function getStopWords() {
    return explode("\n", $GLOBALS['STOP_WORDS']);
  }

  /**
   * Search for searchTerm in index
   * @param searchTerm
   * @return Associative array with object ids as keys and
   * associative array with keys 'oid', 'score', 'summary' as value
   */
  public static function find($searchTerm) {
    $results = array();
    if (!self::isActivated()) {
      return $results;
    }
    $index = self::getIndex(false);
    if ($index) {
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $query = Zend_Search_Lucene_Search_QueryParser::parse($searchTerm, 'UTF-8');
      try {
        $hits = $index->find($query);
        foreach($hits as $hit) {
          $oid = $hit->oid;

          // get the summary with highlighted text
          $summary = '';
          $highlightedRegex = '/((<b style="color:black;background-color:#[0-9a-f]{6}">)+)([^<]+?)((<\/b>)+)/';
          $obj = $persistenceFacade->load($oid);
          $valueNames = $obj->getValueNames();
          foreach ($valueNames as $curValueName) {
            $inputType = $obj->getProperty('input_type');
            $value = self::encodeValue($obj->getValue($curValueName), $inputType);
            if (strlen($value) > 0) {
              $highlighted = $query->htmlFragmentHighlightMatches(strip_tags($value), 'UTF-8');
              $matches = array();
              if (preg_match($highlightedRegex, $highlighted, $matches)) {
                $hit = $matches[3];
                $highlighted = preg_replace($highlightedRegex, ' <em class="highlighted">$3</em> ', $highlighted);
                $highlighted = trim(preg_replace('/&#13;|[\n\r\t]/', ' ', $highlighted));
                $excerpt = StringUtil::excerpt($highlighted, $hit, 300, '');
                $summary = $excerpt;
                break;
              }
            }
          }
          $results[$oid] = array(
              'oid' => $oid,
              'score' => $hit->score,
              'summary' => $summary
          );
        }
      }
      catch (Exception $ex) {
        // do nothing, return empty result
      }
    }
    return $results;
  }
}

class Analyzer extends Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive {
  /**
   * Override method to make sure we are using utf-8
   */
  public function setInput($data, $encoding = '')
  {
    parent::setInput($data, 'UTF-8');
  }
}

/**
 * Standard german/english stop words taken from Lucene's StopAnalyzer
 */
$GLOBALS['STOP_WORDS'] = <<<'EOD'
ein
einer
eine
eines
einem
einen
der
die
das
dass
daß
du
er
sie
es
was
wer
wie
wir
und
oder
ohne
mit
am
im
in
aus
auf
ist
sein
war
wird
ihr
ihre
ihres
als
für
von
mit
dich
dir
mich
mir
mein
sein
kein
durch
wegen
wird
a
an
and
are
as
at
be
but
by
for
if
in
into
is
it
no
not
of
on
or
s
such
t
that
the
their
then
there
these
they
this
to
was
will
with
EOD;
?>
