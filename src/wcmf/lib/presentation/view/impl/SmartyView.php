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
namespace wcmf\lib\presentation\view\impl;

use wcmf\lib\config\ActionKey;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\config\impl\ConfigActionKeyProvider;
use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;
use wcmf\lib\presentation\view\View;

if (!class_exists('Smarty')) {
    throw new ConfigurationException(
            'wcmf\lib\presentation\view\impl\SmartyView requires '.
            'Smarty. If you are using composer, add smarty/smarty '.
            'as dependency to your project');
}

/**
 * View is used by Controller to handle the view presentation in MVC pattern.
 * View is a subclass of Smarty that is customized for use with the wCMF.
 *
 * @note This class requires Smarty
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SmartyView implements View {

  private static $sharedView = null;
  private static $actionKeyProvider = null;
  private static $logger = null;

  protected $view = null;

  /**
   * Constructor
   */
  public function __construct() {
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
    $this->view = new \Smarty();
    $this->view->error_reporting = E_ALL;

    // set plugins directories
    $this->view->addPluginsDir(
      dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/application/views/plugins/'
    );

    // load filter
    $this->view->loadFilter('pre', 'removeprids');
    $this->view->loadFilter('output', 'trimwhitespace');

    // setup default smarty directories
    $this->view->setTemplateDir(WCMF_BASE);
    $cacheDir = session_save_path().DIRECTORY_SEPARATOR;
    $this->view->setCompileDir($cacheDir.'templates_c/');
    $this->view->setCacheDir($cacheDir.'cache/');
  }

  /**
   * Set whether the view should check for
   * template modifications or not
   * @param $compileCheck Boolean
   */
  public function setCompileCheck($compileCheck) {
    $this->view->compile_check = $compileCheck;
  }

  /**
   * Set whether views should be cached
   * @param $caching Boolean
   */
  public function setCaching($caching) {
    $this->view->caching = $caching ? \Smarty::CACHING_LIFETIME_CURRENT : \Smarty::CACHING_OFF;
  }

  /**
   * Set the time a view should be cached
   * @param $cacheLifeTime Integer (seconds)
   */
  public function setCacheLifetime($cacheLifeTime) {
    $this->view->cache_lifetime = $cacheLifeTime;
  }

  /**
   * Set the caching directory
   * If not existing, the directory will be created relative to WCMF_BASE.
   * @param $cacheDir String
   */
  public function setCacheDir($cacheDir) {
    $this->view->setCompileDir(WCMF_BASE.$cacheDir.'templates_c/');
    $this->view->setCacheDir(WCMF_BASE.$cacheDir.'cache/');

    $fileUtil = new FileUtil();
    $fileUtil->mkdirRec($this->view->getCompileDir());
    $fileUtil->mkdirRec($this->view->getCacheDir());
  }

  /**
   * Set a additional plugins directory
   * @param $pluginsDir Directory relative to WCMF_BASE
   */
  public function setPluginsDir($pluginsDir) {
    $this->view->addPluginsDir(WCMF_BASE.$pluginsDir);
  }

  /**
   * Set additional output filters
   * @param $outputFilter
   */
  public function setOutputFilter($outputFilter) {
    foreach ($outputFilter as $filter) {
      $this->view->loadFilter('output', $filter);
    }
  }

  /**
   * @see View::setValue()
   */
  public function setValue($name, $value) {
    $this->view->assign($name, $value);
  }

  /**
   * @see View::getValue()
   */
  public function getValue($name) {
    return $this->view->getTemplateVars($name);
  }

  /**
   * @see View::getValues()
   */
  public function getValues() {
    return $this->view->getTemplateVars();
  }

  /**
   * @see View::clearAllValues()
   */
  public function clearAllValues() {
    $this->view->clearAllAssign();
  }

  /**
   * @see View::display()
   */
  public function render($tplFile, $cacheId=null, $display=true) {
    if ($display) {
      $this->view->display($tplFile, $cacheId);
    }
    else {
      return $this->view->fetch($tplFile, $cacheId);
    }
  }

  /**
   * @see View::clearCache()
   */
  public static function clearCache() {
    if (self::$sharedView == null) {
      self::$sharedView = ObjectFactory::getInstance('view');
    }
    return self::$sharedView->view->clearAllCache();
  }

  /**
   * @see View::isCached()
   * Returns also false, if caching is disabled to make sure that
   * views get regenerated every time when expected.
   */
  public static function isCached($tplFile, $cacheId=null) {
    if (self::$sharedView == null) {
      self::$sharedView = ObjectFactory::getInstance('view');
    }
    $tpl = self::$sharedView->view->createTemplate($tplFile, $cacheId);
    return $tpl->isCached();
  }

  /**
   * @see View::getCacheDate()
   */
  public static function getCacheDate($tplFile, $cacheId=null) {
    if (!self::isCached($tplFile, $cacheId)) {
      return null;
    }
    $tpl = self::$sharedView->view->createTemplate($tplFile, $cacheId);
    return \DateTime::createFromFormat('U', $tpl->cached->timestamp);
  }

  /**
   * @see View::getTemplate()
   */
  public static function getTemplate($controller, $context, $action) {
    $config = ObjectFactory::getInstance('configuration');
    if (self::$actionKeyProvider == null) {
      self::$actionKeyProvider = new ConfigActionKeyProvider($config, 'views');
    }

    $actionKey = ActionKey::getBestMatch(self::$actionKeyProvider, $controller, $context, $action);
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug('SmartyView::getTemplate: '.$controller."?".$context."?".$action.' -> '.$actionKey);
    }
    // get corresponding view
    try {
      $view = $config->getValue($actionKey, 'views', false);
    }
    catch (\Exception $ex) {
      return false;
    }
    return $view;
  }
}
?>
