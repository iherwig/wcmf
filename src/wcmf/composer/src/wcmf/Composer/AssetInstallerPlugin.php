<?php
namespace wcmf\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class AssetInstallerPlugin implements PluginInterface {

  public function activate(Composer $composer, IOInterface $io) {
    $installer = new AssetInstaller($io, $composer);
    $composer->getInstallationManager()->addInstaller($installer);
  }
}
?>