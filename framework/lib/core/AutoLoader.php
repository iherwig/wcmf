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
$classMapping = array();

function __autoload($className)
{
  global $classMapping;

  // don't search if there was a call to class_exists
  $stack = debug_backtrace();
  if ($stack[1]['function'] == 'class_exists') {
    return;
  }

  Log::error($className." searched from ".$stack[1]['file'], "__autoload");

  // search the class definition and register it, if found
  if (!array_key_exists($className, $classMapping))
  {
    // ask ObjectFactory first
    $objectFactory = &ObjectFactory::getInstance();
    $classFile = $objectFactory->getClassfileFromConfig($className);
    if ($classFile !== false) {
      $classMapping[$className] = BASE.$classFile;
    }
    else
    {
      // search directories
      $dir = searchClass($className);
      if ($dir === false) {
        return;
        //throw new Exception("Unable to load definition of class: $className.");
      }
      $classMapping[$className] = $dir.getFileName($className);
    }
  }
  require_once $classMapping[$className];
}

/**
 * Get the file name of a class definition.
 *
 * @param className The name of the class
 * @return The file name
 */
function getFileName($className)
{
  return 'class.'.$className.'.php';
}

/**
 * Search a class definition in any subfolder of BASE
 * Code from: http://php.net/manual/en/language.oop5.autoload.php
 *
 * @param className The name of the class
 * @param sub The start directory [optional]
 * @return The directory name
 */
function searchClass($className, $sub="/")
{
  if(file_exists(BASE.$sub.getFileName($className))) {
    return BASE.$sub;
  }

  $dir = dir(BASE.$sub);
  while(false !== ($folder = $dir->read()))
  {
    if($folder != "." && $folder != "..")
    {
      if(is_dir(BASE.$sub.$folder))
      {
        $subFolder = searchClass($className, $sub.$folder."/");

        if($subFolder) {
          return $subFolder;
        }
      }
    }
  }
  $dir->close();
  return false;
}
?>
