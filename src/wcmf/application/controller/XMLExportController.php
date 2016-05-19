<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
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
  const CACHE_SECTION = 'xmlexport';
  const CACHE_KEY_ROOT_OIDS = 'rootOids';
  const CACHE_KEY_EXPORTED_OIDS = 'exportedOids';

  // session name constants
  const SESSION_VARNAME = __CLASS__;
  const LAST_INDENT_VAR = 'lastIndent';
  const TAGS_TO_CLOSE_VAR = 'tagsToClose';

  // default values, maybe overriden by corresponding request values (see above)
  private $DOCFILE = "export.xml";
  private $DOCTYPE = "";
  private $DTD = "";
  private $DOCROOTELEMENT = "Root";
  private $DOCLINEBREAK = "\n";
  private $DOCINDENT = "  ";
  private $NODES_PER_CALL = 10;

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
   * @param $cache
   */
  public function __construct(Session $session,
          PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager,
          ActionMapper $actionMapper,
          Localization $localization,
          Message $message,
          Configuration $configuration,
          Cache $cache) {
    parent::__construct($session, $persistenceFacade, $permissionManager,
            $actionMapper, $localization, $message, $configuration);
    $this->cache = $cache;
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
        $request->setValue('docFile', $this->DOCFILE);
      }
      if (!$request->hasValue('docType')) {
        $request->setValue('docType', $this->DOCTYPE);
      }
      if (!$request->hasValue('dtd')) {
        $request->setValue('dtd', $this->DTD);
      }
      if (!$request->hasValue('docRootElement')) {
        $request->setValue('docRootElement', $this->DOCROOTELEMENT);
      }
      if (!$request->hasValue('docLinebreak')) {
        $request->setValue('docLinebreak', $this->DOCLINEBREAK);
      }
      if (!$request->hasValue('docIndent')) {
        $request->setValue('docIndent', $this->DOCINDENT);
      }
      if (!$request->hasValue('nodesPerCall')) {
        $request->setValue('nodesPerCall', $this->NODES_PER_CALL);
      }

      // initialize session variables
      $sessionData = array(
        self::LAST_INDENT_VAR => 0,
        self::TAGS_TO_CLOSE_VAR => array()
      );
      $session->set(self::SESSION_VARNAME, $sessionData);

      // reset iterator
      PersistentIterator::reset($this->ITERATOR_ID, $session);
    }
    // initialize parent controller after default request values are set
    parent::initialize($request, $response);
  }

  /**
   * @see BatchController::getWorkPackage()
   */
  protected function getWorkPackage($number) {
    if ($number == 0) {
      return array('name' => $this->getMessage()->getText('Initialization'),
          'size' => 1, 'oids' => array(1), 'callback' => 'initExport');
    }
    else {
      return null;
    }
  }

  /**
   * @see BatchController::getDownloadFile()
   */
  protected function getDownloadFile() {
    $cacheDir = session_save_path().DIRECTORY_SEPARATOR;
    $docFile = $this->getRequestValue('docFile');
    return $cacheDir.$docFile;
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
    $rootOIDs = array();
    $config = $this->getConfiguration();
    $rootTypes = $config->getValue('rootTypes', 'application');
    if (is_array($rootTypes)) {
      $persistenceFacade = $this->getPersistenceFacade();
      foreach($rootTypes as $rootType) {
        $rootOIDs = array_merge($rootOIDs, $persistenceFacade->getOIDs($rootType));
      }
    }

    // store root object ids in session
    $nextOID = array_shift($rootOIDs);
    $this->cache->put(self::CACHE_SECTION, self::CACHE_KEY_ROOT_OIDS, $rootOIDs);

    // empty exported oids
    $tmp = array();
    $this->cache->put(self::CACHE_SECTION, self::CACHE_KEY_EXPORTED_OIDS, $tmp);

    // create work package for first root node
    $this->addWorkPackage(
            $this->getMessage()->getText('Exporting tree: start with %0%', array($nextOID)),
            1, array($nextOID), 'exportNodes');
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

    // check for iterator in session
    $iterator = PersistentIterator::load($this->ITERATOR_ID, $persistenceFacade, $session);
    // no iterator but oid given, start with new root oid
    if ($iterator == null && sizeof($oids) > 0 && $oids[0] != null) {
      $iterator = new PersistentIterator($this->ITERATOR_ID, $persistenceFacade, $session, $oids[0]);
    }
    // no iterator, no oid, finish
    if ($iterator == null) {
      $this->addWorkPackage($message->getText('Finish'), 1, array(null), 'finishExport');
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
    $rootOIDs = $this->cache->get(self::CACHE_SECTION, self::CACHE_KEY_ROOT_OIDS);
    if (!$iterator->valid() && sizeof($rootOIDs) > 0) {
      // if the current iterator is finished, reset the iterator and proceed with the next root oid
      $nextOID = array_shift($rootOIDs);
      // store remaining root oids in session
      $this->cache->put(self::CACHE_SECTION, self::CACHE_KEY_ROOT_OIDS, $rootOIDs);
      // delete iterator to start with new root oid
      PersistentIterator::reset($this->ITERATOR_ID, $session);

      $name = $message->getText('Exporting tree: start with %0%', array($nextOID));
      $this->addWorkPackage($name, 1, array($nextOID), 'exportNodes');
    }
    elseif ($iterator->valid()) {
      // proceed with current iterator
      $iterator->save();

      $name = $message->getText('Exporting tree: continue with %0%', array($iterator->current()));
      $this->addWorkPackage($name, 1, array(null), 'exportNodes');
    }
    else {
      // finish
      $this->addWorkPackage($message->getText('Finish'), 1, array(null), 'finishExport');
    }
  }

  /**
   * Finish the XML export (object ids parameter will be ignored)
   * @param $oids The object ids to process
   * @note This is a callback method called on a matching work package, see BatchController::addWorkPackage()
   */
  protected function finishExport($oids) {
    $session = $this->getSession();

    // get document definition
    $docFile = $this->getDownloadFile();
    $docRootElement = $this->getRequestValue('docRootElement');
    $docLinebreak = $this->getRequestValue('docLinebreak');

    // end document
    $fileHandle = fopen($docFile, "a");
    $this->endTags($fileHandle, 0);
    $this->fileUtil->fputsUnicode($fileHandle, '</'.$docRootElement.'>'.$docLinebreak);
    fclose($fileHandle);

    // clear session variables
    $tmp = null;
    $this->cache->put(self::CACHE_SECTION, self::CACHE_KEY_ROOT_OIDS, $tmp);
    $session->set(self::SESSION_VARNAME, $tmp);
  }

  /**
   * Ends all tags up to $curIndent level
   * @param $fileHandle The file handle to write to
   * @param $curIndent The depth of the node in the tree
   */
  protected function endTags($fileHandle, $curIndent) {
    $session = $this->getSession();

    // get document definition
    $docIndent = $this->getRequestValue('docIndent');
    $docLinebreak = $this->getRequestValue('docLinebreak');

    // get document state from session
    $sessionData = $session->get(self::SESSION_VARNAME);
    $lastIndent = $sessionData[self::LAST_INDENT_VAR];
    $tagsToClose = $sessionData[self::TAGS_TO_CLOSE_VAR];

    // write last opened and not closed tags
    if ($curIndent < $lastIndent) {
      for ($i=$lastIndent-$curIndent; $i>0; $i--) {
        $closeTag = array_shift($tagsToClose);
        if ($closeTag) {
          $this->fileUtil->fputsUnicode($fileHandle, str_repeat($docIndent, $closeTag["indent"]).'</'.$closeTag["name"].'>'.$docLinebreak);
        }
      }
    }

    // update document state in session
    $sessionData[self::LAST_INDENT_VAR] = $lastIndent;
    $sessionData[self::TAGS_TO_CLOSE_VAR] = $tagsToClose;
    $session->set(self::SESSION_VARNAME, $sessionData);
  }

  /**
   * Serialize a Node to XML
   * @param $fileHandle The file handle to write to
   * @param $oid The object id of the node
   * @param $depth The depth of the node in the tree
   */
  protected function writeNode($fileHandle, ObjectId $oid, $depth) {
    $persistenceFacade = $this->getPersistenceFacade();
    $session = $this->getSession();

    // get document definition
    $docIndent = $this->getRequestValue('docIndent');
    $docLinebreak = $this->getRequestValue('docLinebreak');

    // get document state from session
    $sessionData = $session->get(self::SESSION_VARNAME);
    $lastIndent = $sessionData[self::LAST_INDENT_VAR];
    $tagsToClose = $sessionData[self::TAGS_TO_CLOSE_VAR];

    // load node and get element name
    $node = $persistenceFacade->load($oid);
    $elementName = $persistenceFacade->getSimpleType($node->getType());

    // check if the node is written already
    $exportedOids = $this->cache->get(self::CACHE_SECTION, self::CACHE_KEY_EXPORTED_OIDS);
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
        $closeTag = array("name" => $elementName, "indent" => $curIndent);
        array_unshift($tagsToClose, $closeTag);
      }
      else {
        $this->fileUtil->fputsUnicode($fileHandle, '</'.$elementName.'>'.$docLinebreak);
      }
      // remember current indent
      $lastIndent = $curIndent;

      // register exported node
      $exportedOids[] = $oid->__toString();
      $this->cache->put(self::CACHE_SECTION, self::CACHE_KEY_EXPORTED_OIDS, $exportedOids);
    }

    // update document state in session
    $sessionData[self::LAST_INDENT_VAR] = $lastIndent;
    $sessionData[self::TAGS_TO_CLOSE_VAR] = $tagsToClose;
    $session->set(self::SESSION_VARNAME, $sessionData);
  }

  /**
   * Get number of children of the given node, that were not visited yet
   * @param $node
   * @return Integer
   */
  protected function getNumUnvisitedChildren(Node $node) {
    $exportedOids = $this->cache->get(self::CACHE_SECTION, self::CACHE_KEY_EXPORTED_OIDS);

    $childOIDs = array();
    $mapper = $node->getMapper();
    $relations = $mapper->getRelations('child');
    foreach ($relations as $relation) {
      if ($relation->getOtherNavigability()) {
        $childValue = $node->getValue($relation->getOtherRole());
        if ($childValue != null) {
          $children = $relation->isMultiValued() ? $childValue : array($childValue);
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
    return htmlentities(str_replace(array("\r", "\n"), array("", ""), nl2br($value)), ENT_QUOTES);
  }
}
?>
