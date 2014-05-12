<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\lib\search\impl;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\io\FileUtil;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\StateChangeEvent;
use wcmf\lib\search\IndexedSearch;
use wcmf\lib\util\StringUtil;

if (strpos(get_include_path(), 'Zend') === false) {
  set_include_path(get_include_path().PATH_SEPARATOR.WCMF_BASE.'wcmf/vendor/zend');
}
require_once('Zend/Search/Lucene.php');
require_once('Zend/Search/Lucene/Analysis/TokenFilter/StopWords.php');

/**
 * LuceneSearch provides access to the search based on Zend_Search_Lucene.
 * The search index stored in the location that is defined by the parameter 'indexPath'.
 * To manage PersistentObjects in the index use the methods LuceneSearch::addToIndex()
 * and LuceneSearch::deleteFromIndex() and LuceneSearch::commitIndex().
 * The method LuceneSearch::getIndex() offers direct access to the search index
 * for advanced operations.
 *
 * @author Niko <enikao@users.sourceforge.net>
 */
class LuceneSearch implements IndexedSearch {

  private $_indexPath = '';
  private $_index;
  private $_indexIsDirty = false;

  /**
   * Constructor
   */
  public function __construct() {
    // listen to object change events
    ObjectFactory::getInstance('eventManager')->addListener(StateChangeEvent::NAME,
      array($this, 'stateChanged'));
    register_shutdown_function(array($this, 'shutdown'));
  }

  /**
   * Shutdown search
   */
  public function shutdown() {
    $this->commitIndex();
    ObjectFactory::getInstance('eventManager')->removeListener(StateChangeEvent::NAME,
      array($this, 'stateChanged'));
  }

  /**
   * Set the path to the search index.
   * @param indexPath Directory relative to main
   */
  public function setIndexPath($indexPath) {
    $this->_indexPath = FileUtil::realpath(WCMF_BASE.$indexPath).'/';
    FileUtil::mkdirRec($this->_indexPath);
    if (!is_writeable($this->_indexPath)) {
      throw new ConfigurationException("Index path '".$indexPath."' is not writeable.");
    }
    Log::debug("Lucene index location: ".$this->_indexPath, __CLASS__);
  }

  /**
   * Get the path to the search index.
   * @return String
   */
  private function getIndexPath() {
    return $this->_indexPath;
  }

  /**
   * @see Search::check()
   */
  public function check($word) {
    // check for length and stopwords
    if (strlen($word) < 3) {
      return (Message::get("The search term is too short"));
    }
    if (in_array($word, $this->getStopWords())) {
      return (Message::get("The search terms are too common"));
    }
    return true;
  }

  /**
   * @see Search::find()
   */
  public function find($searchTerm, PagingInfo $pagingInfo=null) {
    $results = array();
    $index = $this->getIndex(false);
    if ($index) {
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $query = \Zend_Search_Lucene_Search_QueryParser::parse($searchTerm, 'UTF-8');
      try {
        $hits = $index->find($query);
        if ($pagingInfo != null && $pagingInfo->getPageSize() > 0) {
          $pagingInfo->setTotalCount(sizeof($hits));
          $hits = array_slice($hits, $pagingInfo->getOffset(), $pagingInfo->getPageSize());
        }
        foreach($hits as $hit) {
          $oidStr = $hit->oid;
          $oid = ObjectId::parse($oidStr);

          // get the summary with highlighted text
          $summary = '';
          $highlightedRegex = '/((<b style="color:black;background-color:#[0-9a-f]{6}">)+)([^<]+?)((<\/b>)+)/';
          $obj = $persistenceFacade->load($oid);
          if ($obj) {
            $valueNames = $obj->getPersistentValueNames();
            foreach ($valueNames as $curValueName) {
              $inputType = $obj->getValueProperty($curValueName, 'input_type');
              $value = $this->encodeValue($obj->getValue($curValueName), $inputType);
              if (strlen($value) > 0) {
                $highlighted = @$query->htmlFragmentHighlightMatches(strip_tags($value), 'UTF-8');
                $matches = array();
                if (preg_match($highlightedRegex, $highlighted, $matches)) {
                  $hitStr = $matches[3];
                  $highlighted = preg_replace($highlightedRegex, ' <em class="highlighted">$3</em> ', $highlighted);
                  $highlighted = trim(preg_replace('/&#13;|[\n\r\t]/', ' ', $highlighted));
                  $excerpt = StringUtil::excerpt($highlighted, $hitStr, 300, '');
                  $summary = $excerpt;
                  break;
                }
              }
            }
            $results[$oidStr] = array(
                'oid' => $oidStr,
                'score' => $hit->score,
                'summary' => $summary
            );
          }
        }
      }
      catch (Exception $ex) {
        // do nothing, return empty result
      }
    }
    return $results;
  }

  /**
   * @see Search::isSearchable()
   */
  public function isSearchable(PersistentObject $obj) {
    return (boolean) $obj->getProperty('is_searchable');
  }

  /**
   * @see IndexedSearch::resetIndex()
   */
  public function resetIndex() {
    $indexPath = $this->getIndexPath();
    return \Zend_Search_Lucene::create($indexPath);
  }

  /**
   * @see IndexedSearch::commitIndex()
   */
  public function commitIndex($optimize = true) {
    Log::debug("Commit index", __CLASS__);
    if ($this->_indexIsDirty) {
      $index = $this->getIndex(false);
      if ($index) {
        $index->commit();
        if ($optimize) {
          $index->optimize();
        }
      }
    }
  }

  /**
   * @see IndexedSearch::optimizeIndex()
   */
  public function optimizeIndex() {
    $index = $this->getIndex(false);
    if ($index) {
      $index->optimize();
    }
  }

  /**
   * @see IndexedSearch::addToIndex()
   */
  public function addToIndex(PersistentObject $obj) {
    if ($this->isSearchable($obj)) {
      Log::debug("Add/Update index for: ".$obj->getOID(), __CLASS__);
      $index = $this->getIndex();

      $doc = new \Zend_Search_Lucene_Document();

      $valueNames = $obj->getPersistentValueNames();

      $doc->addField(\Zend_Search_Lucene_Field::unIndexed('oid', $obj->getOID()->__toString(), 'UTF-8'));
      $typeField = \Zend_Search_Lucene_Field::keyword('type', $obj->getType(), 'UTF-8');
      $typeField->isStored = false;
      $doc->addField($typeField);

      foreach ($valueNames as $curValueName) {
        $inputType = $obj->getValueProperty($curValueName, 'input_type');
        $value = $this->encodeValue($obj->getValue($curValueName), $inputType);
        if (preg_match('/^text|^f?ckeditor/', $inputType)) {
          $value = strip_tags($value);
          $doc->addField(\Zend_Search_Lucene_Field::unStored($curValueName, $value, 'UTF-8'));
        }
        else {
          $field = \Zend_Search_Lucene_Field::keyword($curValueName, $value, 'UTF-8');
          $field->isStored = false;
          $doc->addField($field);
        }
      }

      $term = new \Zend_Search_Lucene_Index_Term($obj->getOID()->__toString(), 'oid');
      $docIds  = $index->termDocs($term);
      foreach ($docIds as $id) {
        $index->delete($id);
      }

      $index->addDocument($doc);
      $this->_indexIsDirty = true;
    }
  }

  /**
   * @see IndexedSearch::deleteFromIndex()
   */
  public function deleteFromIndex(PersistentObject $obj) {
    if ($this->isSearchable($obj)) {
      Log::debug("Delete from index: ".$obj->getOID(), __CLASS__);
      $index = $this->getIndex();

      $term = new \Zend_Search_Lucene_Index_Term($obj->getOID()->__toString(), 'oid');
      $docIds  = $index->termDocs($term);
      foreach ($docIds as $id) {
        $index->delete($id);
      }
      $this->_indexIsDirty = true;
    }
  }

  /**
   * Listen to StateChangeEvents
   * @param event StateChangeEvent instance
   */
  public function stateChanged(StateChangeEvent $event) {
    $object = $event->getObject();
    $oldState = $event->getOldValue();
    $newState = $event->getNewValue();
    if (($oldState == PersistentObject::STATE_NEW || $oldState == PersistentObject::STATE_DIRTY)
            && $newState == PersistentObject::STATE_CLEAN) {
      $this->addToIndex($object);
    }
    elseif ($newState == PersistentObject::STATE_DELETED) {
      $this->deleteFromIndex($object);
    }
  }

  /**
   * Get the search index.
   * @param create Boolean whether to create the index, if it does not exist [default: true]
   * @return An instance of Zend_Search_Lucene_Interface or null
   */
  private function getIndex($create = true) {
    if (!$this->_index || $create) {
      $indexPath = $this->getIndexPath();

      $analyzer = new Analyzer();

      // add stop words filter
      $stopWords = $this->getStopWords();
      $stopWordsFilter = new \Zend_Search_Lucene_Analysis_TokenFilter_StopWords($stopWords);
      $analyzer->addFilter($stopWordsFilter);

      \Zend_Search_Lucene_Analysis_Analyzer::setDefault($analyzer);
      \Zend_Search_Lucene_Search_Query_Wildcard::setMinPrefixLength(0);
      \Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('UTF-8');
      \Zend_Search_Lucene_Search_QueryParser::setDefaultOperator(\Zend_Search_Lucene_Search_QueryParser::B_AND);

      try {
        $this->_index = \Zend_Search_Lucene::open($indexPath);
        //$this->_index->setMaxMergeDocs(5);
        //$this->_index->setMergeFactor(5);
      }
      catch (\Zend_Search_Lucene_Exception $ex) {
        $this->_index = $this->resetIndex();
      }
    }
    return $this->_index;
  }

  /**
   * Encode the given value according to the input type
   * @param value
   * @param inputType
   * @return String
   */
  protected function encodeValue($value, $inputType) {
    if (preg_match('/^f?ckeditor/', $inputType)) {
      $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }
    return trim($value);
  }

  /**
   * Get a list of words that are forbidden to search for
   * @return Array
   */
  protected function getStopWords() {
    return explode("\n", $GLOBALS['STOP_WORDS']);
  }
}

class Analyzer extends \Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive {
  /**
   * Override method to make sure we are using utf-8
   */
  public function setInput($data, $encoding = '') {
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
