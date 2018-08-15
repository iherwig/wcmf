<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\search\impl;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\TransactionEvent;
use wcmf\lib\search\IndexedSearch;
use wcmf\lib\search\impl\DefaultIndexStrategy;
use wcmf\lib\util\StringUtil;
use ZendSearch\Lucene\Analysis\Analyzer\Analyzer;
use ZendSearch\Lucene\Analysis\TokenFilter\StopWords;
use ZendSearch\Lucene\Index\Term;
use ZendSearch\Lucene\Lucene;
use ZendSearch\Lucene\Search\Query\Wildcard;
use ZendSearch\Lucene\Search\QueryParser;
use ZendSearch\Lucene\Search\Weight\Boolean;

/**
 * LuceneSearch provides access to the search based on ZendSearch/Lucene.
 * The search index stored in the location that is defined by the parameter 'indexPath'.
 * To manage PersistentObjects in the index use the methods LuceneSearch::addToIndex()
 * and LuceneSearch::deleteFromIndex() and LuceneSearch::commitIndex().
 * The method LuceneSearch::getIndex() offers direct access to the search index
 * for advanced operations.
 *
 * @author Niko <enikao@users.sourceforge.net>
 */
class LuceneSearch implements IndexedSearch {

  private $indexStrategy;
  private $indexPath = '';
  private $liveUpdate = true;
  private $index;
  private $indexIsDirty = false;

  private static $logger = null;

  /**
   * Constructor
   */
  public function __construct() {
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
    $this->indexStrategy = new DefaultIndexStrategy();

    // listen to object change events
    ObjectFactory::getInstance('eventManager')->addListener(TransactionEvent::NAME,
      [$this, 'afterCommit']);
  }

  /**
   * Destructor
   */
  public function __destruct() {
    $this->commitIndex(false);
    ObjectFactory::getInstance('eventManager')->removeListener(TransactionEvent::NAME,
      [$this, 'afterCommit']);
  }

  /**
   * Set the IndexStrategy instance.
   * @param $indexStrategy
   */
  public function setIndexStrategy(IndexStrategy $indexStrategy) {
    $this->indexStrategy = $indexStrategy;
  }

  /**
   * Set the path to the search index.
   * @param $indexPath Directory relative to main
   */
  public function setIndexPath($indexPath) {
    $fileUtil = new FileUtil();
    $this->indexPath = $fileUtil->realpath(WCMF_BASE.$indexPath).'/';
    $fileUtil->mkdirRec($this->indexPath);
    if (!is_writeable($this->indexPath)) {
      throw new ConfigurationException("Index path '".$indexPath."' is not writeable.");
    }
    self::$logger->debug("Lucene index location: ".$this->indexPath);
  }

  /**
   * Get the path to the search index.
   * @return String
   */
  public function getIndexPath() {
    return $this->indexPath;
  }

  /**
   * Set if the search index should update itself, when
   * persistent objects are created/updated/deleted.
   * @param $liveUpdate Boolean
   */
  public function setLiveUpdate($liveUpdate) {
    $this->liveUpdate = $liveUpdate;
  }

  /**
   * Get if the search index should update itself, when
   * persistent objects are created/updated/deleted.
   * @return Boolean
   */
  public function getLiveUpdate() {
    return $this->liveUpdate;
  }

  /**
   * @see Search::check()
   */
  public function check($word) {
    $message = ObjectFactory::getInstance('message');
    // check for length and stopwords
    if (strlen($word) < 3) {
      return ($message->getText("The search term is too short"));
    }
    if (in_array($word, $this->getStopWords())) {
      return ($message->getText("The search terms are too common"));
    }
    return true;
  }

  /**
   * @see Search::find()
   */
  public function find($searchTerm, PagingInfo $pagingInfo=null, $createSummary=true) {
    $results = [];
    $index = $this->getIndex(false);
    if ($index) {
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $query = QueryParser::parse($searchTerm, 'UTF-8');
      try {
        $hits = $index->find($query);
        if ($pagingInfo != null && $pagingInfo->getPageSize() > 0) {
          $pagingInfo->setTotalCount(sizeof($hits));
          $hits = array_slice($hits, $pagingInfo->getOffset(), $pagingInfo->getPageSize());
        }
        foreach($hits as $hit) {
          $oidStr = $hit->oid;
          $oid = ObjectId::parse($oidStr);

          $summary = '';
          if ($createSummary) {
            // get the summary with highlighted text
            $highlightedRegex = '/((<b style="color:black;background-color:#[0-9a-f]{6}">)+)([^<]+?)((<\/b>)+)/';
            $obj = $persistenceFacade->load($oid);
            if ($obj) {
              $valueNames = $obj->getValueNames(true);
              foreach ($valueNames as $curValueName) {
                $inputType = $obj->getValueProperty($curValueName, 'input_type');
                $value = $obj->getValue($curValueName);
                if (!is_object($value) && !is_array($value)) {
                  $value = $this->indexStrategy->encodeValue($value, $inputType);
                  if (strlen($value) > 0) {
                    $highlighted = @$query->htmlFragmentHighlightMatches(strip_tags($value), 'UTF-8');
                    $matches = [];
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
              }
            }
          }
          $results[$oidStr] = [
              'oid' => $oidStr,
              'score' => $hit->score,
              'summary' => $summary
          ];
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
    return (boolean) $obj->getProperty('isSearchable');
  }

  /**
   * @see IndexedSearch::resetIndex()
   */
  public function resetIndex() {
    $indexPath = $this->getIndexPath();
    return Lucene::create($indexPath);
  }

  /**
   * @see IndexedSearch::commitIndex()
   */
  public function commitIndex($optimize = true) {
    self::$logger->debug("Commit index");
    if ($this->indexIsDirty) {
      $index = $this->getIndex(false);
      $index->commit();
      if ($optimize) {
        $index->optimize();
      }
    }
  }

  /**
   * @see IndexedSearch::optimizeIndex()
   */
  public function optimizeIndex() {
    $index = $this->getIndex(false);
    $index->optimize();
  }

  /**
   * @see IndexedSearch::addToIndex()
   */
  public function addToIndex(PersistentObject $obj) {
    if ($this->isSearchable($obj)) {
      $index = $this->getIndex();
      $oidStr = $obj->getOID()->__toString();

      $this->deleteFromIndex($obj->getOID());

      // create document for each language
      $localization = ObjectFactory::getInstance('localization');
      foreach ($localization->getSupportedLanguages() as $language => $languageName) {
        // load translation
        $indexObj = $localization->loadTranslation($obj, $language, false);

        if (self::$logger->isDebugEnabled()) {
          self::$logger->debug("Add/Update index for: ".$oidStr." language:".$language);
        }

        $doc = $this->indexStrategy->getDocument($indexObj, $language);
        $index->addDocument($doc);
      }
      $this->indexIsDirty = true;
    }
  }

  /**
   * @see IndexedSearch::deleteFromIndex()
   */
  public function deleteFromIndex(ObjectId $oid) {
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Delete from index: ".$oid);
    }
    $index = $this->getIndex();

    $term = new Term($oid->__toString(), 'oid');
    $docIds = $index->termDocs($term);
    foreach ($docIds as $id) {
      $index->delete($id);
    }
    $this->indexIsDirty = true;
  }

  /**
   * Listen to TransactionEvents
   * @param $event TransactionEvent instance
   */
  public function afterCommit(TransactionEvent $event) {
    if ($this->liveUpdate && $event->getPhase() == TransactionEvent::AFTER_COMMIT) {
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      // add inserted/updated objects
      foreach (array_merge(array_values($event->getInsertedOids()), $event->getUpdatedOids()) as $oid) {
        $object = $persistenceFacade->load(ObjectId::parse($oid));
        $this->addToIndex($object);
      }
      // remove deleted objects
      foreach ($event->getDeletedOids() as $oid) {
        $this->deleteFromIndex(ObjectId::parse($oid));
      }
    }
  }

  /**
   * Get the search index.
   * @param $create Boolean whether to create the index, if it does not exist (default: _true_)
   * @return An instance of ZendSearch/SearchIndexInterface or null
   */
  private function getIndex($create = true) {
    if (!$this->index || $create) {
      $indexPath = $this->getIndexPath();

      $analyzer = new LuceneUtf8Analyzer();

      // add stop words filter
      $stopWords = $this->getStopWords();
      $stopWordsFilter = new StopWords($stopWords);
      $analyzer->addFilter($stopWordsFilter);

      Analyzer::setDefault($analyzer);
      Wildcard::setMinPrefixLength(0);
      QueryParser::setDefaultEncoding('UTF-8');
      QueryParser::setDefaultOperator(QueryParser::B_AND);

      try {
        $this->index = Lucene::open($indexPath);
        //$this->index->setMaxMergeDocs(5);
        //$this->index->setMergeFactor(5);
      }
      catch (\Exception $ex) {
        $this->index = $this->resetIndex();
      }
    }
    return $this->index;
  }

  /**
   * Get a list of words that are forbidden to search for
   * @return Array
   */
  protected function getStopWords() {
    return explode("\n", $GLOBALS['STOP_WORDS']);
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
