<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;
use wcmf\lib\presentation\view\View;

if (!class_exists('Smarty')) {
    throw new \wcmf\lib\config\ConfigurationException(
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

  protected static $_sharedView = null;
  protected static $_actionKeyProvider = null;

  protected $_view = null;

  /**
   * Constructor
   */
  public function __construct() {
    $this->_view = new \Smarty();
    $this->_view->error_reporting = E_ALL;

    // set plugins directories
    $this->_view->addPluginsDir(
      dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/application/views/plugins/'
    );

    // load filter
    $this->_view->loadFilter('pre', 'removeprids');
    $this->_view->loadFilter('output', 'trimwhitespace');

    // setup default smarty directories
    $this->_view->setTemplateDir(WCMF_BASE);
    $cacheDir = session_save_path().DIRECTORY_SEPARATOR;
    $this->_view->setCompileDir($cacheDir.'templates_c/');
    $this->_view->setCacheDir($cacheDir.'cache/');
  }

  /**
   * Set whether the view should check for
   * template modifications or not
   * @param $compileCheck Boolean
   */
  public function setCompileCheck($compileCheck) {
    $this->_view->compile_check = $compileCheck;
  }

  /**
   * Set whether views should be cached
   * @param $caching Boolean
   */
  public function setCaching($caching) {
    $this->_view->caching = $caching;
  }

  /**
   * Set the time a view should be cached
   * @param $cacheLifeTime Integer (seconds)
   */
  public function setCacheLifetime($cacheLifeTime) {
    $this->_view->cache_lifetime = $cacheLifeTime;
  }

  /**
   * Set the caching directory
   * If not existing, the directory will be created relative to WCMF_BASE.
   * @param $cacheDir String
   */
  public function setCacheDir($cacheDir) {
    $this->_view->setCompileDir(WCMF_BASE.$cacheDir.'templates_c/');
    $this->_view->setCacheDir(WCMF_BASE.$cacheDir.'cache/');
    FileUtil::mkdirRec($this->_view->getCompileDir());
    FileUtil::mkdirRec($this->_view->getCacheDir());
  }

  /**
   * Set a additional plugins directory
   * @param $pluginsDir Directory relative to WCMF_BASE
   */
  public function setPluginsDir($pluginsDir) {
    $this->_view->addPluginsDir(WCMF_BASE.$pluginsDir);
  }

  /**
   * Set additional output filters
   * @param $outputFilter
   */
  public function setOutputFilter($outputFilter) {
    foreach ($outputFilter as $filter) {
      $this->_view->loadFilter('output', $filter);
    }
  }

  /**
   * @see View::setValue()
   */
  public function setValue($name, $value) {
    $this->_view->assign($name, $value);
  }

  /**
   * @see View::getValue()
   */
  public function getValue($name) {
    return $this->_view->getTemplateVars($name);
  }

  /**
   * @see View::getValues()
   */
  public function getValues() {
    return $this->_view->getTemplateVars();
  }

  /**
   * @see View::clearAllValues()
   */
  public function clearAllValues() {
    $this->_view->clearAllAssign();
  }

  /**
   * @see View::display()
   */
  public function render($tplFile, $cacheId=null, $display=true) {
    if ($display) {
      $this->_view->display($tplFile, $cacheId);
    }
    else {
      return $this->_view->fetch($tplFile, $cacheId);
    }
  }

  /**
   * @see View::clearCache()
   */
  public static function clearCache() {
    if (self::$_sharedView == null) {
      self::$_sharedView = ObjectFactory::getInstance('view');
    }
    return self::$_sharedView->_view->clearAllCache();
  }

  /**
   * @see View::isCached()
   * Returns also false, if caching is disabled to make sure that
   * views get regenerated every time when expected.
   */
  public static function isCached($tplFile, $cacheId=null) {
    if (self::$_sharedView == null) {
      self::$_sharedView = ObjectFactory::getInstance('view');
    }
    return (self::$_sharedView->_view->caching && self::$_sharedView->_view->isCached($tplFile, $cacheId));
  }

  /**
   * @see View::getTemplate()
   */
  public static function getTemplate($controller, $context, $action) {
    if (self::$_actionKeyProvider == null) {
      self::$_actionKeyProvider = new ConfigActionKeyProvider();
      self::$_actionKeyProvider->setConfigSection('views');
    }

    $actionKey = ActionKey::getBestMatch(self::$_actionKeyProvider, $controller, $context, $action);
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug('SmartyView::getTemplate: '.$controller."?".$context."?".$action.' -> '.$actionKey, __CLASS__);
    }
    // get corresponding view
    $config = ObjectFactory::getConfigurationInstance();
    try {
      $view = $config->getValue($actionKey, 'views', false);
    } catch (\Exception $ex) {
      throw new ConfigurationException("No view defined for ".$controller."?".$context."?".$action);
    }
    return $view;
  }
}
?>
