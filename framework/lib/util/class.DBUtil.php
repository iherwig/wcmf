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
require_once(BASE."wcmf/lib/model/mapper/class.RDBMapper.php");
require_once(BASE."wcmf/lib/util/class.Log.php");
require_once(BASE."wcmf/lib/util/class.InifileParser.php");

/**
 * @class DBUtil
 * @ingroup Util
 * @brief DBUtil provides database helper functions.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DBUtil
{
  /**
   * Execute a sql script. Execution is done inside a transaction, which is rolled back in case of failure.
   * @param file The filename of the sql script
   * @param initSection The name of the configuration section that defines the database connection
   * @return True/False wether execution succeeded or not.
   */
  function executeScript($file, $initSection)
  {
    if (file_exists($file))
    {
      Log::info('Executing SQL script '.$file.' ...', __CLASS__);

      // find init params
      $parser = &InifileParser::getInstance();
      $initParams = null;
      if (($initParams = $parser->getSection($initSection)) === false)
      {
        WCMFException::throwEx("No '".$initSection."' section given in configfile.", __FILE__, __LINE__);
        return false;
      }
      // connect to the database using a RDBMapper instance
      $mapper = new RDBMapper($initParams);
      $connection = $mapper->getConnection();

      Log::debug('Starting transaction ...', __CLASS__);
      $connection->startTrans();

      $ok = true;
      $fh = fopen($file, 'r');
      if ($fh)
      {
        while (!feof($fh))
        {
          $command = fgets($fh, 8192);
          if (strlen(trim($command)) > 0)
          {
            Log::debug('Executing command: '.$command, __CLASS__);
            $ok = $connection->Execute($command);
            if (!$ok)
              break;
          }
        }
        fclose($fh);
      }
      if ($ok)
      {
        Log::debug('Execution succeeded, committing ...', __CLASS__);
        $connection->commitTrans();
      }
      else
      {
        Log::error('Execution failed. Reason'.$connection->ErrorMsg(), __CLASS__);
        Log::debug('Rolling back ...', __CLASS__);
        $connection->rollbackTrans();
      }
      Log::debug('Finished SQL script '.$file.'.', __CLASS__);
    }
    else
    {
      Log::error('SQL script '.$file.' not found.', __CLASS__);
    }
  }
}
?>
