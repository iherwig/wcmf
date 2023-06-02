<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\security\impl;

use wcmf\lib\config\ActionKey;
use wcmf\lib\config\Configuration;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\config\impl\ConfigActionKeyProvider;
use wcmf\lib\config\impl\InifileConfiguration;
use wcmf\lib\core\LogTrait;
use wcmf\lib\core\Session;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\security\PermissionManager;
use wcmf\lib\security\impl\AbstractPermissionManager;
use wcmf\lib\security\principal\PrincipalFactory;
use wcmf\lib\util\StringUtil;

/**
 * StaticPermissionManager retrieves authorization rules from the
 * application configuration.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class StaticPermissionManager extends AbstractPermissionManager implements PermissionManager {
  use LogTrait;

  const AUTHORIZATION_SECTION = 'authorization';

  private $configuration = null;
  private $actionKeyProvider = null;

  /**
   * Constructor
   * @param PersistenceFacade $persistenceFacade
   * @param Session $session
   * @param Configuration $configuration
   */
  public function __construct(PersistenceFacade $persistenceFacade,
          Session $session,
          PrincipalFactory $principalFactory,
          Configuration $configuration) {
    parent::__construct($persistenceFacade, $session, $principalFactory);
    $this->configuration = $configuration;
    $this->actionKeyProvider = new ConfigActionKeyProvider($this->configuration,
            self::AUTHORIZATION_SECTION);
  }

  /**
   * @see PermissionManager::getPermissions()
   */
  public function getPermissions(string $resource, string $context, string $action): ?array {
    $result = null;
    $actionKey = ActionKey::getBestMatch($this->actionKeyProvider, $resource, $context, $action);
    if (strlen($actionKey) > 0) {
      $result = $this->deserializePermissions($this->actionKeyProvider->getKeyValue($actionKey));
    }
    if (self::logger()->isDebugEnabled()) {
      self::logger()->debug("Permissions for $resource?$context?$action (->$actionKey): ".trim(StringUtil::getDump($result)));
    }
    return $result;
  }

  /**
   * @see PermissionManager::setPermissions()
   */
  public function setPermissions(string $resource, string $context, string $action, ?array $permissions): void {
    $permKey = ActionKey::createKey($resource, $context, $action);
    $config = $this->getConfigurationInstance();
    $configInstance = $config['instance'];
    $isChanged = false;

    if ($permissions != null) {
      // set permissions
      $rolesStr = $this->serializePermissions($permissions);
      if (strlen($rolesStr)) {
        $configInstance->setValue($permKey, $rolesStr, self::AUTHORIZATION_SECTION, true);
        $isChanged = true;
      }
    }
    else {
      // delete permissions
      $configInstance->removeKey($permKey);
      $isChanged = true;
    }

    if ($isChanged) {
      $configInstance->writeConfiguration(basename($config['file']));
    }
  }

  /**
   * @see PermissionManager::createPermission()
   */
  public function createPermission(string $resource, string $context, string $action, string $role, string $modifier): bool {
    return self::modifyPermission($resource, $context, $action, $role, $modifier);
  }

  /**
   * @see PermissionManager::removePermission()
   */
  public function removePermission(string $resource, string $context, string $action, string $role): bool {
    return self::modifyPermission($resource, $context, $action, $role, null);
  }

  /**
   * Modify a permission for the given role.
   * @param string $resource The resource (e.g. class name of the Controller or object id).
   * @param string $context The context in which the action takes place.
   * @param string $action The action to process.
   * @param string $role The role to authorize.
   * @param string $modifier One of the PERMISSION_MODIFIER_ constants, null, if the permission
   *    should be removed.
   * @return bool
   */
  protected function modifyPermission(string $resource, string $context, string $action, string $role, string $modifier): bool {
    $permKey = ActionKey::createKey($resource, $context, $action);
    $permVal = '';
    if ($modifier != null) {
      $permVal = $modifier.$role;
    }
    $config = $this->getConfigurationInstance();
    $configInstance = $config['instance'];
    $value = $configInstance->getValue($permKey, self::AUTHORIZATION_SECTION);
    if ($value === false && $modifier != null) {
      $configInstance->setValue($permKey, $permVal, self::AUTHORIZATION_SECTION, true);
    }
    else {
      // remove role from value
      $newValue = preg_replace('/ +/', ' ', str_replace([PermissionManager::PERMISSION_MODIFIER_ALLOW.$role,
                    PermissionManager::PERMISSION_MODIFIER_DENY.$role], "", $value));
      if (strlen($newValue) > 0) {
        $configInstance->setValue($permKey, $newValue." ".$permVal, self::AUTHORIZATION_SECTION, false);
      }
      else {
        $configInstance->removeKey($permKey, self::AUTHORIZATION_SECTION);
      }
    }

    $configInstance->writeConfiguration(basename($config['file']));
    return true;
  }

  /**
   * Get the configuration instance and file that is used to store the permissions.
   * @return array{'instance': Configuration, 'file': string}
   */
  protected function getConfigurationInstance(): array {
    // get config file to modify
    $configFiles = $this->configuration->getConfigurations();
    if (sizeof($configFiles) == 0) {
      throw new ConfigurationException('No configuration files exist');
    }

    // create a writable configuration and modify the permission
    $mainConfig = $configFiles[0];
    $config = new InifileConfiguration(dirname($mainConfig).'/');
    $config->addConfiguration(basename($mainConfig));
    return [
      'instance' => $config,
      'file' => $mainConfig
    ];
  }
}
?>
