<?php
namespace app\src\lib;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\PersistenceEvent;

/**
 * EventListener
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class EventListener {

  private $_eventManager = null;

  /**
   * Constructor
   */
  public function __construct() {
    $this->_eventManager = ObjectFactory::getInstance('eventManager');
    $this->_eventManager->addListener(PersistenceEvent::NAME, array($this, 'persisted'));
  }

  /**
   * Destructor
   */
  public function __destruct() {
    $this->_eventManager->removeListener(PersistenceEvent::NAME, array($this, 'persisted'));
  }

  /**
   * Listen to PersistenceEvent
   * @param $event PersistenceEvent instance
   */
  public function persisted(PersistenceEvent $event) {
    $this->invalidateCachedViews();
  }

  /**
   * Invalidate cached views on object change.
   */
  protected function invalidateCachedViews() {
    $view = ObjectFactory::getInstance('view');
    $view->clearCache();
  }
}
?>
