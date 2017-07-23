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
namespace wcmf\application\controller;

use wcmf\application\controller\BatchController;
use wcmf\lib\config\Configuration;
use wcmf\lib\core\Session;
use wcmf\lib\i18n\Localization;
use wcmf\lib\i18n\Message;
use wcmf\lib\io\Cache;
use wcmf\lib\io\FileUtil;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\presentation\ActionMapper;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\security\PermissionManager;

/**
 * CSVImportController imports instances of one type into the storage. It uses
 * the `fgetcsv` function of PHP with the default values for delimiter, enclosing
 * and escape character.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Initiate the import.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `docFile`         | The file upload as associative array with the following keys: 'name', 'type', 'tmp_name' (typically a $_FILES entry)
 * | _in_ `className`       | The entity type to import instances of
 * | _in_ `nodesPerCall`    | The number of nodes to process in one call (default: 50)
 * </div>
 * </div>
 *
 * For additional actions and parameters see [BatchController actions](@ref BatchController).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class CSVImportController extends BatchController {
  const CACHE_KEY_STATS = 'stats';

  // default values, maybe overriden by corresponding request values (see above)
  private $NODES_PER_CALL = 50;

  private $cache = null;

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
      // set defaults (will be stored with first request)
      if (!$request->hasValue('nodesPerCall')) {
        $request->setValue('nodesPerCall', $this->NODES_PER_CALL);
      }

      // move the file upload
      if ($request->hasValue('docFile')) {
        $config = $this->getConfiguration();
        $cacheBaseDir = WCMF_BASE.$config->getValue('cacheDir', 'StaticCache');
        $cacheSection = 'csv-import-'.uniqid().'/cache';
        $uploadDir = $cacheBaseDir.dirname($cacheSection).'/';
        FileUtil::mkdirRec($uploadDir);
        $uploadFile = $uploadDir.FileUtil::uploadFile($request->getValue('docFile'), $uploadDir.'data.csv');
        $request->setValue('cacheSection', $cacheSection);
        $request->setValue('uploadFile', $uploadFile);
      }

      // get the csv header
      if (($fileHandle = fopen($uploadFile, "r")) !== false &&
              ($header = fgetcsv($fileHandle)) !== false) {
        $request->setValue('csvHeader', $header);
      }

      // initialize cache
      $this->cache->put($cacheSection, self::CACHE_KEY_STATS, [
          'processed' => 0,
          'updated' => 0,
          'created' => 0,
          'skipped' => 0
      ]);
    }
    // initialize parent controller after default request values are set
    parent::initialize($request, $response);
  }

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if ($request->getAction() != 'continue') {
      if (!$request->hasValue('className') ||
        !$this->getPersistenceFacade()->isKnownType($request->getValue('className'))) {
        $response->addError(ApplicationError::get('PARAMETER_INVALID',
          ['invalidParameters' => ['className']]));
        return false;
      }
      // check if the upload succeeded and csv is valid
      if (!$request->hasValue('csvHeader')) {
        $response->addError(ApplicationError::get('PARAMETER_INVALID',
          ['invalidParameters' => ['docFile']]));
        return false;
      }
      // check for permission to create/update instances of className
      if (!$this->getPermissionManager()->authorize($request->getValue('className'), '', PersistenceAction::CREATE) ||
              !$this->getPermissionManager()->authorize($request->getValue('className'), '', PersistenceAction::UPDATE)) {
        $response->addError(ApplicationError::get('PERMISSION_DENIED'));
        return false;
      }
    }
    // do default validation
    return parent::validate();
  }

  /**
   * @see BatchController::getWorkPackage()
   */
  protected function getWorkPackage($number) {
    if ($number == 0) {
      return ['name' => $this->getMessage()->getText('Initialization'),
          'size' => 1, 'oids' => [1], 'callback' => 'initImport'];
    }
  }

  /**
   * Initialize the CSV import (object ids parameter will be ignored)
   * @param $oids The object ids to process
   * @note This is a callback method called on a matching work package, see BatchController::addWorkPackage()
   */
  protected function initImport($oids) {
    $message = $this->getMessage();

    // get document definition
    $type =  $this->getRequestValue('className');
    $docFile = $this->getRequestValue('uploadFile');
    $cacheSection = $this->getRequestValue('cacheSection');

    // get stats from cache
    $stats = $this->cache->get($cacheSection, self::CACHE_KEY_STATS);
    $this->getResponse()->setValue('stats', $stats);

    // oids are line numbers
    $oids = [];
    $file = new \SplFileObject($docFile);
    $file->seek(1); // skip header
    $eof = false;
    while (!$eof) {
      $content = $file->current();
      if (strlen(trim($content)) > 0) {
        $oids[] = $file->key();
      }
      $file->next();
      $eof = $file->eof();
    }

    // create work packages for nodes
    $nodesPerCall = $this->getRequestValue('nodesPerCall');
    $this->addWorkPackage(
            $message->getText('Importing %0%', [$type]),
            $nodesPerCall, $oids, 'importNodes');
  }

  /**
   * Serialize all Nodes with given object ids to CSV
   * @param $oids The object ids to process
   * @note This is a callback method called on a matching work package, see BatchController::addWorkPackage()
   */
  protected function importNodes($oids) {
    $this->requireTransaction();
    $persistenceFacade = $this->getPersistenceFacade();
    $permissionManager = $this->getPermissionManager();

    // get document definition
    $type = $this->getRequestValue('className');
    $docFile = $this->getRequestValue('uploadFile');
    $header = $this->getRequestValue('csvHeader');
    $cacheSection = $this->getRequestValue('cacheSection');

    // get stats from cache
    $stats = $this->cache->get($cacheSection, self::CACHE_KEY_STATS);

    // get type definition
    $mapper = $persistenceFacade->getMapper($type);
    $pkNames = $mapper->getPkNames();

    // process csv lines
    $file = new \SplFileObject($docFile);
    $file->setFlags(\SplFileObject::READ_CSV);
    foreach ($oids as $oid) {
      $file->seek($oid);
      // read line
      $data = array_combine($header, $file->current());

      // get primary key values
      $pkValues = [];
      foreach ($pkNames as $pkName) {
        if (!empty($data[$pkName])) {
          $pkValues[] = $data[$pkName];
        }
      }

      // get object to create/update
      $isUpdate = sizeof($pkValues) == sizeof($pkNames);
      if ($isUpdate) {
        $oid = new ObjectId($type, $pkValues);
        if ($permissionManager->authorize($oid, '', PersistenceAction::READ) &&
                $permissionManager->authorize($oid, '', PersistenceAction::UPDATE)) {
          $obj = $persistenceFacade->load($oid);
        }
      }
      else {
        if ($permissionManager->authorize($type, '', PersistenceAction::CREATE)) {
          $obj = $persistenceFacade->create($type);
        }
      }

      // update the object
      if ($obj != null) {
        foreach ($data as $name => $value) {
          if (!in_array($name, $pkNames) && $mapper->hasAttribute($name)) {
            $value = empty($value) ? null : $value;
            if ($value !== $obj->getValue($name)) {
              $obj->setValue($name, $value);
            }
          }
        }
        $state = $obj->getState();
        if ($state == PersistentObject::STATE_NEW) {
          $stats['created']++;
        }
        elseif ($state == PersistentObject::STATE_DIRTY) {
          $stats['updated']++;
        }
        else {
          $stats['skipped']++;
        }
      }
      else {
        $stats['skipped']++;
      }
      $stats['processed']++;
    }

    // update stats
    $this->cache->put($cacheSection, self::CACHE_KEY_STATS, $stats);
    $this->getResponse()->setValue('stats', $stats);
  }

  /**
   * @see BatchController::cleanup()
   */
  protected function cleanup() {
    $uploadDir = dirname($this->getRequestValue('uploadFile'));
    FileUtil::emptyDir($uploadDir);
    rmdir($uploadDir);
    parent::cleanup();
  }
}
?>
