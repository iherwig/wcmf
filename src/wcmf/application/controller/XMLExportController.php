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
namespace wcmf\application\controller;

use wcmf\application\controller\BatchController;
use wcmf\lib\config\Configuration;
use wcmf\lib\core\Session;
use wcmf\lib\i18n\Localization;
use wcmf\lib\i18n\Message;
use wcmf\lib\io\Cache;
use wcmf\lib\io\FileUtil;
use wcmf\lib\model\Node;
use wcmf\lib\model\PersistentIterator;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\presentation\ActionMapper;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\security\PermissionManager;

/**
 * XMLExportController exports the content tree into an XML file.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Initiate the export.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `docFile`         | The name of the file to write to (path relative to script main location) (default: 'export.xml')
 * | _in_ `docType`         | The document type (will be written into XML header) (default: '')
 * | _in_ `dtd`             | The dtd (will be written into XML header) (default: '')
 * | _in_ `docRootElement`  | The root element of the document (use this to enclose different root types if necessary) (default: 'Root')
 * | _in_ `docLinebreak`    | The linebreak character(s) to use (default: '\n')
 * | _in_ `docIndent`       | The indent character(s) to use (default: '  ')
 * | _in_ `nodesPerCall`    | The number of nodes to process in one call (default: 10)
 * </div>
 * </div>
 *
 * For additional actions and parameters see [BatchController actions](@ref BatchController).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class XMLExportController extends BatchController {
  const CACHE_KEY_ROOT_OIDS = 'rootOids';
  const CACHE_KEY_EXPORTED_OIDS = 'exportedOids';
  const CACHE_KEY_LAST_INDENT = 'lastIndent';
  const CACHE_KEY_TAGS_TO_CLOSE = 'tagsToClose';

  // persistent iterator id
  const ITERATOR_ID_VAR = 'XMLExportController.iteratorid';

  // default values, maybe overriden by corresponding request values (see above)
  const DOCFILE = "export.xml";
  const DOCTYPE = "";
  const DTD = "";
  const DOCROOTELEMENT = "Root";
  const DOCLINEBREAK = "\n";
  const DOCINDENT = "  ";
  const NODES_PER_CALL = 10;

  private $cache = null;
  private $fileUtil = null;

  /**
   * Constructor
   * @param $session
   * @param $persistenceFacade
   * @param $permissionManager
   * @param $actionMapper
   * @param $localization
   * @param $message
   * @param $configuration
   * @param $staticCache
   */
  public function __construct(Session $session,
          PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager,
          ActionMapper $actionMapper,
          Localization $localization,
          Message $message,
          Configuration $configuration,
          Cache $staticCache) {
    parent::__construct($session, $persistenceFacade, $permissionManager,
            $actionMapper, $localization, $message, $configuration);
    $this->cache = $staticCache;
    $this->fileUtil = new FileUtil();
  }

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    // initialize controller
    if ($request->getAction() != 'continue') {
      $session = $this->getSession();

      // set defaults (will be stored with first request)
      if (!$request->hasValue('docFile')) {
        $request->setValue('docFile', self::DOCFILE);
      }
      if (!$request->hasValue('docType')) {
        $request->setValue('docType', self::DOCTYPE);
      }
      if (!$request->hasValue('dtd')) {
        $request->setValue('dtd', self::DTD);
      }
      if (!$request->hasValue('docRootElement')) {
        $request->setValue('docRootElement', self::DOCROOTELEMENT);
      }
      if (!$request->hasValue('docLinebreak')) {
        $request->setValue('docLinebreak', self::DOCLINEBREAK);
      }
      if (!$request->hasValue('docIndent')) {
        $request->setValue('docIndent', self::DOCINDENT);
      }
      if (!$request->hasValue('nodesPerCall')) {
        $request->setValue('nodesPerCall', self::NODES_PER_CALL);
      }

      // set the cache section and directory for the download file
      $config = $this->getConfiguration();
      $cacheBaseDir = WCMF_BASE.$config->getValue('cacheDir', 'StaticCache');
      $cacheSection = 'xml-export-'.uniqid().'/cache';
      $downloadDir = $cacheBaseDir.dirname($cacheSection).'/';
      FileUtil::mkdirRec($downloadDir);
      $request->setValue('cacheSection', $cacheSection);
      $request->setValue('downloadFile', $downloadDir.$request->getValue('docFile'));

      // initialize cache
      $this->cache->put($cacheSection, self::CACHE_KEY_LAST_INDENT, 0);
      $this->cache->put($cacheSection, self::CACHE_KEY_TAGS_TO_CLOSE, []);

      // reset iterator
      PersistentIterator::reset($this->ITERATOR_ID_VAR, $session);
    }
    // initialize parent controller after default request values are set
    parent::initialize($request, $response);
  }

  /**
   * @see BatchController::getWorkPackage()
   */
  protected function getWorkPackage($number) {
    if ($number == 0) {
      return ['name' => $this->getMessage()->getText('Initialization'),
          'size' => 1, 'oids' => [1], 'callback' => 'initExport'];
    }
    else {
      return null;
    }
  }

  /**
   * @see BatchController::getDownloadFile()
   */
  protected function getDownloadFile() {
    return $this->getRequestValue('downloadFile');
  }

  /**
   * Initialize the XML export (object ids parameter will be ignored)
   * @param $oids The object ids to process
   * @note This is a callback method called on a matching work package, see BatchController::addWorkPackage()
   */
  protected function initExport($oids) {
    // get document definition
    $docFile = $this->getDownloadFile();
    $docType = $this->getRequestValue('docType');
    $dtd = $this->getRequestValue('dtd');
    $docRootElement = $this->getRequestValue('docRootElement');
    $docLinebreak = $this->getRequestValue('docLinebreak');
    $cacheSection = $this->getRequestValue('cacheSection');

    // delete export file
    if (file_exists($docFile)) {
      unlink($docFile);
    }

    // start document
    $fileHandle = fopen($docFile, "a");
    $this->fileUtil->fputsUnicode($fileHandle, '<?xml version="1.0" encoding="UTF-8"?>'.$docLinebreak);
    if ($docType != "") {
      $this->fileUtil->fputsUnicode($fileHandle, '<!DOCTYPE '.$docType.' SYSTEM "'.$dtd.'">'.$docLinebreak);
    }
    $this->fileUtil->fputsUnicode($fileHandle, '<'.$docRootElement.'>'.$docLinebreak);
    fclose($fileHandle);

    // get root types from ini file
    $rootOIDs = [];
    $config = $this->getConfiguration();
    $rootTypes = $config->getValue('rootTypes', 'application');
    if (is_array($rootTypes)) {
      $persistenceFacade = $this->getPersistenceFacade();
      foreach($rootTypes as $rootType) {
        $rootOIDs = array_merge($rootOIDs, $persistenceFacade->getOIDs($rootType));
      }
    }

    // store root object ids in cache
    $nextOID = array_shift($rootOIDs);
    $this->cache->put($cacheSection, self::CACHE_KEY_ROOT_OIDS, $rootOIDs);

    // empty exported oids
    $tmp = [];
    $this->cache->put($cacheSection, self::CACHE_KEY_EXPORTED_OIDS, $tmp);

    // create work package for first root node
    $this->addWorkPackage(
            $this->getMessage()->getText('Exporting tree: start with %0%', [$nextOID]),
            1, [$nextOID], 'exportNodes');
  }

  /**
   * Serialize all Nodes with given object ids to XML
   * @param $oids The object ids to process
   * @note This is a callback method called on a matching work package, see BatchController::addWorkPackage()
   */
  protected function exportNodes($oids) {
    // Export starts from root oids and iterates over all children.
    // On every call we have to decide what to do:
    // - If there is an iterator stored in the session we are inside a tree and continue iterating (_NODES_PER_CALL nodes)
    //   until the iterator finishes
    // - If the oids array holds one value!=null this is assumed to be an root oid and a new iterator is constructed
    // - If there is no iterator and no oid given, we return

    $session = $this->getSession();
    $persistenceFacade = $this->getPersistenceFacade();
    $message = $this->getMessage();

    // get document definition
    $docFile = $this->getDownloadFile();
    $nodesPerCall = $this->getRequestValue('nodesPerCall');
    $cacheSection = $this->getRequestValue('cacheSection');

    // check for iterator in session
    $iterator = PersistentIterator::load($this->ITERATOR_ID_VAR, $persistenceFacade, $session);
    // no iterator but oid given, start with new root oid
    if ($iterator == null && sizeof($oids) > 0 && $oids[0] != null) {
      $iterator = new PersistentIterator($this->ITERATOR_ID_VAR, $persistenceFacade, $session, $oids[0]);
    }
    // no iterator, no oid, finish
    if ($iterator == null) {
      $this->addWorkPackage($message->getText('Finish'), 1, [null], 'finishExport');
      return;
    }

    // process nodes
    $fileHandle = fopen($docFile, "a");
    $counter = 0;
    while ($iterator->valid() && $counter < $nodesPerCall) {
      // write node
      $this->writeNode($fileHandle, $iterator->current(), $iterator->key()+1);
      $iterator->next();
      $counter++;
    }
    $this->endTags($fileHandle, 0);
    fclose($fileHandle);

    // decide what to do next
    $rootOIDs = $this->cache->get($cacheSection, self::CACHE_KEY_ROOT_OIDS);
    if (!$iterator->valid() && sizeof($rootOIDs) > 0) {
      // if the current iterator is finished, reset the iterator and proceed with the next root oid
      $nextOID = array_shift($rootOIDs);
      // store remaining root oids in the cache
      $this->cache->put($cacheSection, self::CACHE_KEY_ROOT_OIDS, $rootOIDs);
      // delete iterator to start with new root oid
      PersistentIterator::reset($this->ITERATOR_ID_VAR, $session);

      $name = $message->getText('Exporting tree: start with %0%', [$nextOID]);
      $this->addWorkPackage($name, 1, [$nextOID], 'exportNodes');
    }
    elseif ($iterator->valid()) {
      // proceed with current iterator
      $iterator->save();

      $name = $message->getText('Exporting tree: continue with %0%', [$iterator->current()]);
      $this->addWorkPackage($name, 1, [null], 'exportNodes');
    }
    else {
      // finish
      $this->addWorkPackage($message->getText('Finish'), 1, [null], 'finishExport');
    }
  }

  /**
   * Finish the XML export (object ids parameter will be ignored)
   * @param $oids The object ids to process
   * @note This is a callback method called on a matching work package, see BatchController::addWorkPackage()
   */
  protected function finishExport($oids) {
    // get document definition
    $docFile = $this->getDownloadFile();
    $docRootElement = $this->getRequestValue('docRootElement');
    $docLinebreak = $this->getRequestValue('docLinebreak');
    $cacheSection = $this->getRequestValue('cacheSection');

    // end document
    $fileHandle = fopen($docFile, "a");
    $this->endTags($fileHandle, 0);
    $this->fileUtil->fputsUnicode($fileHandle, '</'.$docRootElement.'>'.$docLinebreak);
    fclose($fileHandle);

    // clear cache
    $tmp = null;
    $this->cache->put($cacheSection, self::CACHE_KEY_ROOT_OIDS, $tmp);
  }

  /**
   * Ends all tags up to $curIndent level
   * @param $fileHandle The file handle to write to
   * @param $curIndent The depth of the node in the tree
   */
  protected function endTags($fileHandle, $curIndent) {
    // get document definition
    $docIndent = $this->getRequestValue('docIndent');
    $docLinebreak = $this->getRequestValue('docLinebreak');
    $cacheSection = $this->getRequestValue('cacheSection');

    // get document state from cache
    $lastIndent = $this->cache->get($cacheSection, self::CACHE_KEY_LAST_INDENT);
    $tagsToClose = $this->cache->get($cacheSection, self::CACHE_KEY_TAGS_TO_CLOSE);

    // write last opened and not closed tags
    if ($curIndent < $lastIndent) {
      for ($i=$lastIndent-$curIndent; $i>0; $i--) {
        $closeTag = array_shift($tagsToClose);
        if ($closeTag) {
          $this->fileUtil->fputsUnicode($fileHandle, str_repeat($docIndent, $closeTag["indent"]).'</'.$closeTag["name"].'>'.$docLinebreak);
        }
      }
    }

    // update document state in cache
    $this->cache->put($cacheSection, self::CACHE_KEY_LAST_INDENT, $lastIndent);
    $this->cache->put($cacheSection, self::CACHE_KEY_TAGS_TO_CLOSE, $tagsToClose);
  }

  /**
   * Serialize a Node to XML
   * @param $fileHandle The file handle to write to
   * @param $oid The object id of the node
   * @param $depth The depth of the node in the tree
   */
  protected function writeNode($fileHandle, ObjectId $oid, $depth) {
    $persistenceFacade = $this->getPersistenceFacade();

    // get document definition
    $docIndent = $this->getRequestValue('docIndent');
    $docLinebreak = $this->getRequestValue('docLinebreak');
    $cacheSection = $this->getRequestValue('cacheSection');

    // get document state from cache
    $lastIndent = $this->cache->get($cacheSection, self::CACHE_KEY_LAST_INDENT);
    $tagsToClose = $this->cache->get($cacheSection, self::CACHE_KEY_TAGS_TO_CLOSE);

    // load node and get element name
    $node = $persistenceFacade->load($oid);
    $elementName = $persistenceFacade->getSimpleType($node->getType());

    // check if the node is written already
    $exportedOids = $this->cache->get($cacheSection, self::CACHE_KEY_EXPORTED_OIDS);
    if (!in_array($oid->__toString(), $exportedOids)) {
      // write node
      $mapper = $node->getMapper();

      $hasUnvisitedChildren = $this->getNumUnvisitedChildren($node) > 0;

      $curIndent = $depth;
      $this->endTags($fileHandle, $curIndent);

      // write object's content
      // open start tag
      $this->fileUtil->fputsUnicode($fileHandle, str_repeat($docIndent, $curIndent).'<'.$elementName);
      // write object attributes
      $attributes = $mapper->getAttributes();
      foreach ($attributes as $curAttribute) {
        $attributeName = $curAttribute->getName();
        $value = $node->getValue($attributeName);
        $this->fileUtil->fputsUnicode($fileHandle, ' '.$attributeName.'="'.$this->formatValue($value).'"');
      }
      // close start tag
      $this->fileUtil->fputsUnicode($fileHandle, '>');
      if ($hasUnvisitedChildren) {
        $this->fileUtil->fputsUnicode($fileHandle, $docLinebreak);
      }

      // remember end tag if not closed
      if ($hasUnvisitedChildren) {
        $closeTag = ["name" => $elementName, "indent" => $curIndent];
        array_unshift($tagsToClose, $closeTag);
      }
      else {
        $this->fileUtil->fputsUnicode($fileHandle, '</'.$elementName.'>'.$docLinebreak);
      }
      // remember current indent
      $lastIndent = $curIndent;

      // register exported node
      $exportedOids[] = $oid->__toString();
      $this->cache->put($cacheSection, self::CACHE_KEY_EXPORTED_OIDS, $exportedOids);
    }

    // update document state in cache
    $this->cache->put($cacheSection, self::CACHE_KEY_LAST_INDENT, $lastIndent);
    $this->cache->put($cacheSection, self::CACHE_KEY_TAGS_TO_CLOSE, $tagsToClose);
  }

  /**
   * Get number of children of the given node, that were not visited yet
   * @param $node
   * @return Integer
   */
  protected function getNumUnvisitedChildren(Node $node) {
    $cacheSection = $this->getRequestValue('cacheSection');
    $exportedOids = $this->cache->get($cacheSection, self::CACHE_KEY_EXPORTED_OIDS);

    $childOIDs = [];
    $mapper = $node->getMapper();
    $relations = $mapper->getRelations('child');
    foreach ($relations as $relation) {
      if ($relation->getOtherNavigability()) {
        $childValue = $node->getValue($relation->getOtherRole());
        if ($childValue != null) {
          $children = $relation->isMultiValued() ? $childValue : [$childValue];
          foreach ($children as $child) {
            $childOIDs[] = $child->getOID();
          }
        }
      }
    }
    $numUnvisitedChildren = 0;
    foreach ($childOIDs as $childOid) {
      if (!in_array($childOid->__toString(), $exportedOids)) {
        $numUnvisitedChildren++;
      }
    }
    return $numUnvisitedChildren;
  }

  /**
   * Format a value for XML output
   * @param $value The value to format
   * @return The formatted value
   * @note Subclasses may overrite this for special application requirements
   */
  protected function formatValue($value) {
    return htmlentities(str_replace(["\r", "\n"], ["", ""], nl2br($value)), ENT_QUOTES);
  }

  /**
   * @see BatchController::cleanup()
   */
  protected function cleanup() {
    $downloadDir = dirname($this->getRequestValue('downloadFile'));
    FileUtil::emptyDir($downloadDir);
    rmdir($downloadDir);
    parent::cleanup();
  }
}
?>
