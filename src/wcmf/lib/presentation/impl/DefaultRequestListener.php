<?php
namespace wcmf\lib\presentation\impl;

use wcmf\lib\core\EventManager;
use wcmf\lib\model\StringQuery;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\presentation\ApplicationEvent;

/**
 * DefaultRequestListener normalizes various search, sort and paging parameters
 * into those understood by wcmf controllers.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultRequestListener {

  private $_eventManager = null;
  private $_persistenceFacade = null;

  /**
   * Constructor
   */
  public function __construct(EventManager $eventManager, PersistenceFacade $persistenceFacade) {
    $this->_eventManager = $eventManager;
    $this->_eventManager->addListener(ApplicationEvent::NAME,
      array($this, 'listen'));
    $this->_persistenceFacade = $persistenceFacade;
  }

  /**
   * Destructor
   */
  public function __destruct() {
    $this->_eventManager->removeListener(ApplicationEvent::NAME,
      array($this, 'listen'));
  }

  /**
   * Listen to ApplicationEvent
   * @param $event ApplicationEvent instance
   */
  public function listen(ApplicationEvent $event) {
    if ($event->getStage() == ApplicationEvent::BEFORE_ROUTE_ACTION) {
      $request = $event->getRequest();
      if ($request != null) {
        $this->transformRequest($request);
      }
    }
    else if ($event->getStage() == ApplicationEvent::AFTER_EXECUTE_CONTROLLER) {
      $request = $event->getRequest();
      $response = $event->getResponse();
      if ($request != null && $response != null) {
        $this->transformResponse($request, $response);
      }
    }
  }

  protected function transformRequest($request) {
    // transform range header into paging parameters
    if ($request->hasHeader('Range')) {
      if (preg_match('/^items=([\-]?[0-9]+)-([\-]?[0-9]+)$/', $request->getHeader('Range'), $matches)) {
        $offset = intval($matches[1]);
        $limit = intval($matches[2])-$offset+1;
        $request->setValue('offset', $offset);
        $request->setValue('limit', $limit);
      }
    }

    // transform position header into reference oid parameter for sorting
    if ($request->hasHeader('Position')) {
      $position = $request->getHeader('Position');
      if ($position == 'last') {
        $referenceOid = 'ORDER_BOTTOM';
      }
      else {
        list($ignore, $orderReferenceIdStr) = preg_split('/ /', $position);
        if ($request->hasValue('relation') && $request->hasValue('sourceOid')) {
          // sort in relation
          $sourceOid = ObjectId::parse($request->getValue('sourceOid'));
          $relatedType = $this->getRelatedType($sourceOid, $request->getValue('relation'));
          $referenceOid = new ObjectId($relatedType, $orderReferenceIdStr);
        }
        else {
          // sort in root
          $referenceOid = new ObjectId($request->getValue('className'), $orderReferenceIdStr);
        }
      }
      $request->setValue('referenceOid', $referenceOid);
    }

    // parse get parameters
    foreach ($request->getValues() as $key => $value) {
      // sort definition
      if (preg_match('/^sort\(([^\)]+)\)$|sortBy=([.]+)$/', $key, $matches)) {
        $sortDefs = preg_split('/,/', $matches[1]);
        // ListController allows only one sortfield
        $sortDef = $sortDefs[0];
        $sortFieldName = substr($sortDef, 1);
        $sortDirection = preg_match('/^-/', $sortDef) ? 'desc' : 'asc';
        $request->setValue('sortFieldName', $sortFieldName);
        $request->setValue('sortDirection', $sortDirection);
      }
      // limit
      if (preg_match('/^limit\(([^\)]+)\)$/', $key, $matches)) {
        $rangeDefs = preg_split('/,/', $matches[1]);
        $limit = intval($rangeDefs[0]);
        $offset = sizeof($rangeDefs) > 0 ? intval($rangeDefs[1]) : 0;
        $request->setValue('offset', $offset);
        $request->setValue('limit', $limit);
      }
    }

    // create query from optional GET values encoded in RQL
    // (https://github.com/persvr/rql)
    if ($request->hasValue('className') && $request->hasValue('query')) {
      $type = $request->getValue('className');
      $stringQuery = StringQuery::fromRql($type, urldecode($request->getValue('query')));
      $query = $stringQuery->getQueryCondition();
      $request->setValue('query', $query);
    }
  }

  protected function transformResponse($request, $response) {
    // set content-range header
    if ($response->hasValue('list')) {
      $objects = $response->getValue('list');
      $size = sizeof($objects);

      // set response range header
      $offset = $request->getValue('offset');
      $limit = $size == 0 ? $offset : $offset+$size-1;
      $total = $response->getValue('totalCount');
      $response->setHeader('Content-Range', 'items '.$offset.'-'.$limit.'/'.$total);
    }
  }

  /**
   * Get the type that is used in the given role related to the
   * given source object.
   * @param $sourceOid ObjectId of the source object
   * @param $role The role name
   * @return String
   */
  protected function getRelatedType(ObjectId $sourceOid, $role) {
    $sourceMapper = $this->_persistenceFacade->getMapper($sourceOid->getType());
    $relation = $sourceMapper->getRelation($role);
    return $relation->getOtherType();
  }
}
?>
