<?php
namespace wcmf\Composer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

class AssetInstaller extends LibraryInstaller {

  public function getInstallPath(PackageInterface $package) {
    return "app/public/vendor/".$package->getPrettyName();
  }

  public function supports($packageType) {
    return (bool)("wcmf-asset" === $packageType);
  }
}
?>