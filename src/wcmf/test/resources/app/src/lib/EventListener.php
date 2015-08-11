<?php
namespace app\src\lib;

use wcmf\lib\core\EventManager;
use wcmf\lib\persistence\PersistenceEvent;
use wcmf\lib\presentation\view\View;

/**
 * EventListener
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class EventListener {

  private $_eventManager = null;
  private $_view = null;

  /**
   * Constructor
   */
  public function __construct(EventManager $eventManager,
          View $view) {
    $this->_eventManager = $eventManager;
    $this->_view = $view;
    $this->_eventManager->addListener(PersistenceEvent::NAME,
      array($this, 'persisted'));
  }

  /**
   * Destructor
   */
  public function __destruct() {
    $this->_eventManager->removeListener(PersistenceEvent::NAME,
      array($this, 'persisted'));
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
    $this->_view->clearCache();
  }
}
?>
