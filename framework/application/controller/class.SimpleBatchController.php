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
require_once(BASE."wcmf/application/controller/class.BatchController.php");

/**
 * @class SimpleBatchController
 * @ingroup Controller
 * @brief SimpleBatchController is a controller demonstrating the use
 * of BatchController for cutting a long task into a fixed number
 * of smaller tasks.
 * 
 * <b>Input actions:</b>
 * - see BatchController
 *
 * <b>Output actions:</b>
 * - see BatchController
 * 
 * @author ingo herwig <ingo@wemove.com>
 */
class SimpleBatchController extends BatchController
{
  /**
   * @see BatchController::getWorkPackage()
   */
  function getWorkPackage($number)
  {
    // create 2 static work packages where each consists of 5 * $number+1 oids,
    // these will be processed in portions of 3
    if ($number < 2)
    {
      // create different oid lists
      $oids = array();
      for ($i=0; $i<5*($number+1); $i++)
        array_push($oids, $number."-".$i);
        
      // for demonstration purposes we call different methods for different oid lists
      if ($number == 0)
        $callback = 'createFileA';
      if ($number == 1)
        $callback = 'createFileB';
      
      return array('name' => 'File '.$number, 'size' => 3, 'oids' => $oids, 'callback' => $callback);
    }
    else
      return null;
  }
  /**
   * Create one file of type A for each oid in oids
   * @param oids The oids to process
   * @note This is a callback method called on a matching work package @see BatchController::addWorkPackage()
   */
  function createFileA($oids)
  {
    // do some processing depending on state here
    foreach ($oids as $oid)
    {
      $curNum = sprintf("%04s",$oid);
      $fh = fopen("result".$curNum."_A.txt", "a");
      fputs($fh, date("F j, Y, g:i a").": SimpleBatchController created file A #".$curNum."\n");
      fclose($fh);
    }
  }
  /**
   * Create one file of type B for each oid in oids
   * @param oids The oids to process
   * @note This is a callback method called on a matching work package @see BatchController::addWorkPackage()
   */
  function createFileB($oids)
  {
    // do some processing depending on state here
    foreach ($oids as $oid)
    {
      $curNum = sprintf("%04s",$oid);
      $fh = fopen("result".$curNum."_B.txt", "a");
      fputs($fh, date("F j, Y, g:i a").": SimpleBatchController created file B #".$curNum."\n");
      fclose($fh);
    }
  }
}
?>
