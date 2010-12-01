<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
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
 *
 * $Id$
 */
require_once(BASE."application/dionysos/include/lib/class.DionysosException.php");
require_once(BASE."wcmf/lib/model/class.NodeIterator.php");
require_once(BASE."wcmf/lib/security/class.RightsManager.php");
require_once(BASE."wcmf/lib/util/class.Log.php");

/**
 * @class DionysosControllerDelegate
 * @ingroup Presentation
 * @brief DionysosControllerDelegate maps wCMF request/response data in a
 * Dionysos compliant way.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DionysosControllerDelegate
{
	/**
	 * @see ControllerDelegate::postInitialize()
	 */
	function postInitialize(&$controller)
	{
		$request = &$controller->_request;
		$response = &$controller->_response;

		// always return dionysos json format
		$response->setFormat('Dionysos');

		// add the request data to the response
		$requestData = $request->getData();
		foreach ($requestData as $key => $value) {
			$response->setValue($key, $value);
		}

		// remove wcmf specific response values
		$ignoreValues = array('usr_action');
		foreach ($ignoreValues as $key) {
			$response->clearValue($key);
		}

		// do controller/action specific value mappings
		switch ($request->getAction())
		{
			case 'dologin':
				$request->setValue('login', $request->getValue('user'));
				break;

			case 'list':
				if ($request->getValue('offset') < 0) {
					throw new DionysosException($request, $response, DionysosException::OFFSET_OUT_OF_BOUNDS, DionysosException::OFFSET_OUT_OF_BOUNDS);
				}
				if ($request->getValue('limit') < 0) {
					throw new DionysosException($request, $response, DionysosException::LIMIT_NEGATIVE, DionysosException::LIMIT_NEGATIVE);
				}
				if (!PersistenceFacade::isKnownType($request->getValue('className'))) {
					throw new DionysosException($request, $response, DionysosException::CLASS_NAME_INVALID, DionysosException::CLASS_NAME_INVALID);
				}
				$type = PersistenceFacade::getInstance()->create($request->getValue('className'), BUILDDEPTH_SINGLE);
				if ($request->hasValue('sortFieldName') && !$type->hasValue($request->getValue('sortFieldName'))) {
					throw new DionysosException($request, $response, DionysosException::SORT_FIELD_UNKNOWN, DionysosException::SORT_FIELD_UNKNOWN);
				}
				if ($request->hasValue('sortDirection') && !in_array($request->getValue('sortDirection'), array('asc', 'desc'))) {
					throw new DionysosException($request, $response, DionysosException::SORT_DIRECTION_UNKNOWN, DionysosException::SORT_DIRECTION_UNKNOWN);
				}

				$request->setValue('type', $request->getValue('className'));
				$request->setValue('start', $request->getValue('offset'));
				$request->setValue('sort', $request->getValue('sortFieldName'));
				$request->setValue('dir', $request->getValue('sortDirection'));
				$request->setValue('completeObjects', true);
				break;

			case 'display':
				// cast to requested format
				if ($response->hasValue('depth')) {
					$response->setValue('depth', intval($response->getValue('depth')));
				}
				if ($request->hasValue('depth') && $request->getValue('depth') < -1) {
					throw new DionysosException($request, $response, DionysosException::DEPTH_INVALID, DionysosException::DEPTH_INVALID);
				}
				$oid = $request->getValue('oid');
				if (!PersistenceFacade::getInstance()->load($oid, BUILDDEPHT_SINGLE)) {
					throw new DionysosException(null, null, 'The object id '.$oid.' is unknown', DionysosException::OID_INVALID);
				}

				if (!$request->hasValue('depth')) {
					$request->setValue('depth', 1);
				}
				break;

			case 'save':
				$oid = $request->getValue('oid');
				$type = PersistenceFacade::getOIDParameter($oid, 'type');
				if (!PersistenceFacade::isKnownType($type)) {
					throw new DionysosException($request, $response, 'Entity type '.$type.' is unknown', DionysosException::CLASS_NAME_INVALID);
				}
				if (!PersistenceFacade::getInstance()->load($oid, BUILDDEPHT_SINGLE)) {
					throw new DionysosException(null, null, 'The object id '.$oid.' is unknown', DionysosException::OID_INVALID);
				}
				break;

			case 'new':
				if (!PersistenceFacade::isKnownType($request->getValue('className'))) {
					throw new DionysosException($request, $response, 'Entity type '.$request->getValue('className').' is unknown', DionysosException::CLASS_NAME_INVALID);
				}
				$request->setValue('newtype', $request->getValue('className'));
				break;

			case 'delete':
				$oid = $request->getValue('oid');
				$type = PersistenceFacade::getOIDParameter($oid, 'type');
				if (!PersistenceFacade::isKnownType($type)) {
					throw new DionysosException($request, $response, 'Entity type '.$type.' is unknown', DionysosException::CLASS_NAME_INVALID);
				}
				if (!PersistenceFacade::getInstance()->load($oid, BUILDDEPHT_SINGLE)) {
					throw new DionysosException(null, null, 'The object id '.$oid.' is unknown', DionysosException::OID_INVALID);
				}
				$request->setValue('deleteoids', $oid);
				break;

			case 'associate':
			case 'disassociate':
				$role = $request->getValue('role');
				if (!PersistenceFacade::isKnownType($role)) {
					throw new DionysosException($request, $response, 'Entity role '.$role.' is unknown', DionysosException::ROLE_INVALID);
				}
				$sourceOid = $request->getValue('sourceOid');
				$sourceType = PersistenceFacade::getOIDParameter($sourceOid, 'type');
				if (!PersistenceFacade::isKnownType($sourceType)) {
					throw new DionysosException($request, $response, 'Entity type '.$sourceType.' is unknown', DionysosException::CLASS_NAME_INVALID);
				}
				if (!PersistenceFacade::getInstance()->load($sourceOid, BUILDDEPHT_SINGLE)) {
					throw new DionysosException(null, null, 'The object id '.$sourceOid.' is unknown', DionysosException::OID_INVALID);
				}
				$targetOid = $request->getValue('targetOid');
				$targetType = PersistenceFacade::getOIDParameter($targetOid, 'type');
				if (!PersistenceFacade::isKnownType($targetType)) {
					throw new DionysosException($request, $response, 'Entity type '.$targetType.' is unknown', DionysosException::CLASS_NAME_INVALID);
				}
				if (!PersistenceFacade::getInstance()->load($targetOid, BUILDDEPHT_SINGLE)) {
					throw new DionysosException(null, null, 'The object id '.$targetOid.' is unknown', DionysosException::OID_INVALID);
				}

				$request->setValue('oid', $sourceOid);
				// map targetOid to the role class
				$associateOID = PersistenceFacade::composeOID(array('type' => $role,
          'id' => PersistenceFacade::getOIDParameter($request->getValue('targetOid'), 'id')));
				$request->setValue('associateoids', $associateOID);
				$request->setValue('associateAs', 'child');
				break;

			case 'multipleAction':

				// map action names
				$parser = &WCMFInifileParser::getInstance();
				$actionMap = $parser->getSection('actionmap');
				$data = &$request->getValue('actionSet');
				for($i=0, $actions=array_keys($data), $numActions=sizeof($actions); $i<$numActions; $i++)
				{
					$action = $data['action'.$i]['action'];
					$data['action'.$i]['usr_action'] = $actionMap[$action];
				}

				$request->setValue('data', $data);
				break;
			case 'search':
				if ($request->getValue('offset') < 0) {
					throw new DionysosException($request, $response, DionysosException::OFFSET_OUT_OF_BOUNDS, DionysosException::OFFSET_OUT_OF_BOUNDS);
				}
				if ($request->getValue('limit') < 0) {
					throw new DionysosException($request, $response, DionysosException::LIMIT_NEGATIVE, DionysosException::LIMIT_NEGATIVE);
				}
				if ($request->getValue('className')) {
					if(!PersistenceFacade::isKnownType($request->getValue('className'))) {
						throw new DionysosException($request, $response, DionysosException::CLASS_NAME_INVALID, DionysosException::CLASS_NAME_INVALID);
					}
					$type = PersistenceFacade::getInstance()->create($request->getValue('className'), BUILDDEPTH_SINGLE);
					if ($request->hasValue('sortFieldName') && !$type->hasValue($request->getValue('sortFieldName'))) {
						throw new DionysosException($request, $response, DionysosException::SORT_FIELD_UNKNOWN, DionysosException::SORT_FIELD_UNKNOWN);
					}
					if ($request->hasValue('sortDirection') && !in_array($request->getValue('sortDirection'), array('asc', 'desc'))) {
						throw new DionysosException($request, $response, DionysosException::SORT_DIRECTION_UNKNOWN, DionysosException::SORT_DIRECTION_UNKNOWN);
					}
					if (!$type->getProperty('is_searchable')) {
						throw new DionysosException($request, $response, DionysosException::SEARCH_NOT_SUPPORTED, DionysosException::SEARCH_NOT_SUPPORTED);
					}
				}


				$request->setValue('type', $request->getValue('className'));
				$request->setValue('start', $request->getValue('offset'));
				$request->setValue('sort', $request->getValue('sortFieldName'));
				$request->setValue('dir', $request->getValue('sortDirection'));
				$request->setValue('filter', $request->getValue('query'));
				break;
		}
	}
	/**
	 * @see ControllerDelegate::validate()
	 */
	function validate(&$controller)
	{
		return true;
	}
	/**
	 * @see ControllerDelegate::preExecute()
	 */
	function preExecute(&$controller)
	{
	}
	/**
	 * @see ControllerDelegate::postExecute()
	 */
	function postExecute(&$controller, $result)
	{
		$request = &$controller->_request;
		$response = &$controller->_response;

		// remove wcmf specific response values
		// (don't remove controller because otherwise a longtask will not work)
		$ignoreValues = array(/*'controller'*/);
		foreach ($ignoreValues as $key) {
			$response->clearValue($key);
		}

		// do controller/action specific value mappings
		switch ($request->getAction())
		{
			case 'dologin':
				$authUser = &RightsManager::getInstance()->getAuthUser();
				if ($authUser) {
					$roles = $authUser->getRoles();
					$roleNames = array();
					for($i=0, $numRoles=sizeof($roles); $i<$numRoles; $i++) {
						$roleNames[] = $roles[$i]->getName();
					}
					$response->setValue('roles', $roleNames);
					$response->setValue('implementedPackages', array('base'));
				}
				else {
					throw new DionysosException($request, $response, DionysosException::AUTHENTICATION_FAILED, DionysosException::AUTHENTICATION_FAILED);
				}
				break;

			case 'list':
			case 'search':
				$response->clearValue('type');
				$response->clearValue('start');
				$response->clearValue('sort');
				$response->clearValue('dir');
				$response->clearValue('completeObjects');

				if ($response->getValue('totalCount') > 0 && $request->getValue('offset') >= $response->getValue('totalCount')) {
					$response->clearValue('list');
					throw new DionysosException($request, $response, DionysosException::OFFSET_OUT_OF_BOUNDS, DionysosException::OFFSET_OUT_OF_BOUNDS);
				}
				// cast to requested format
				if ($response->hasValue('offset')) {
					$response->setValue('offset', intval($response->getValue('offset')));
				}
				if ($response->hasValue('limit')) {
					$response->setValue('limit', intval($response->getValue('limit')));
				}
				$response->setValue('list', $response->getValue('objects'));
				$response->clearValue('objects');
				break;

			case 'display':
				$response->clearValue('rootType');
				$response->clearValue('rootTemplateNode');
				$response->clearValue('possibleparents');
				$response->clearValue('possiblechildren');
				$response->clearValue('lockMsg');
				$response->clearValue('viewMode');
				$response->setValue('object', $response->getValue('node'));
				$response->clearValue('node');

				// remove many to many objects from object tree
				if ($response->getValue('object'))
				{
					$iter = new NodeIterator($response->getValue('object'));
					while(!$iter->isEnd())
					{
						$curObj = &$iter->getCurrentObject();
						$children = $curObj->getChildren();
						for ($i=0, $numChildren=sizeof($children); $i<$numChildren; $i++)
						{
							$curChild = &$children[$i];
							if ($curChild->isManyToManyObject())
							{
								$curObj->deleteChild($curChild->getOID(), true);
								// add parents of many to many object instead
								$curChild->loadParents();
								$parents = $curChild->getParents();
								for ($j=0, $numParents=sizeof($parents); $j<$numParents; $j++)
								{
									$curParent = &$parents[$j];
									if ($curParent->getBaseOID() != $curObj->getBaseOID()) {
										$curParent->deleteChild($curChild->getOID(), true);
										// hack: prevent automatic addition of nm object
										$curObj->_children[sizeof($curObj->_children)] = &$curParent;
										$curParent->updateParent($curObj);
									}
								}
							}
						}
						$iter->proceed();
					}
				}
				break;

			case 'save':
				$oid = $request->getValue('oid');
				$response->clearValue($oid);
				break;

			case 'new':
				break;

			case 'delete':
				$response->setValue('oid', $request->getValue('oid'));
				break;

			case 'associate':
				$response->clearValue('manyToMany');
				break;

			case 'multipleAction':
				$response->setValue('resultSet', $response->getValue('data'));
				$response->clearValue('actionSet');
				$response->clearValue('data');
				break;
		}
		return $result;
	}
	/**
	 * @see ControllerDelegate::assignAdditionalViewValues()
	 */
	function assignAdditionalViewValues(&$controller)
	{
	}
}
?>
