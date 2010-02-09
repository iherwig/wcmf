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

/**
 * @class FTPUtil
 * @ingroup Util
 * @brief FTPUtil provides support for ftp functionality.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FTPUtil
{
  var $_errorMsg = '';
  var $_ftpConnId = null;
  var $_ftpRootDir = '';

  /**
   * Get last error message.
   * @return The error string
   */
  function getErrorMsg()
  {
    return $this->_errorMsg;
  }
  /**
   * Open a FTP connection to a given server.
   * @param params Initialization data given in an assoziative array with the following keys: 
   *               server, port, login, password
   * @return True on success, False else / error string provided by getErrorMsg()
   */
  function open($params)
  {
    // check ftp support
    if (!function_exists(ftp_connect))
    {
      $this->_errorMsg = 'ftp extension missing';
      return null;
    }
    
    $this->_errorMsg = '';
    // suppress warnings
    $oldErrorLevel = error_reporting (E_ERROR | E_PARSE);
    // set up basic connection
    $conn_id = ftp_connect($params['server'], $params['port'], 10);
    if ($conn_id)
    {
      // login with username and password
      $login_result = ftp_login($conn_id, $params['login'], $params['password']);     
      // check connection
      if ($login_result)
      {
        $this->_ftpConnId = $conn_id;
        // remember the root dir, which is needed if a relative path is given in copy()
        $this->_ftpRootDir = ftp_pwd($this->_ftpConnId);
        // use passive mode
        //ftp_pasv($this->_ftpConnId, true);

        return true;
      }  
      else  {
        $this->_errorMsg = 'login failed';
      }
    }    
    else
      $this->_errorMsg = 'connection to server failed';
    // reset error level
    error_reporting ($oldErrorLevel);
    return false;
  }
  /*
   * Get the files in a remote directory that match a pattern
   * @param dir The directory to search in [default: .]
   * @param pattern The pattern (regexp) to match [default: /./]
   * @param prependDirectoryName True/False whether to prepend the directory name to each file [default: false]
   * @param recursive True/False whether to recurse into subdirectories [default: false]
   * @result An array containing the filenames or null if failed, error string provided by getErrorMsg()
   */  
  function getFiles($directory='.', $pattern='/./', $prependDirectoryName=false, $recursive=false)
  {
    if ($this->_ftpConnId == null)
    {
      $this->_errorMsg = 'connect to server first';
      return null;
    }
    
    $result = null;
    $isfile = ftp_size($this->_ftpConnId, urldecode($directory));
    if ($isfile == "-1")
    {
      $result = array();
      $fileList = ftp_nlist($this->_ftpConnId, $directory);
      foreach ($fileList as $file)
      {
        $isfile = ftp_size($this->_ftpConnId, urldecode($file));
        if($recursive && $isfile == "-1") 
        {
          $files = $this->getFiles($file , $pattern, $prependDirectoryName, $recursive);
          $result = array_merge($result, $files);
        }
        else if($isfile != "-1" && preg_match($pattern, $file))
        {
          if (!$prependDirectoryName)
            $file = substr($file, strrpos($file, '/')+1);
          array_push($result, $file);
        }
      }
    }
    else
      $this->_errorMsg = "The directory '".$directory."' does not exist.";
    return $result;
  }
  /**
   * Get size an modification date of a remote file.
   * @param file The name of the file to get the info for
   * @result An assoziative array with keys 'size' and 'mtime' (values -1 indicate an error)
   */
  function getFileInfo($file)
  {
    if ($this->_ftpConnId == null)
    {
      $this->_errorMsg = 'connect to server first';
      return null;
    }
    
    ftp_chdir($this->_ftpConnId, $this->_ftpRootDir);
    $fileInfo = array();
    $file = urldecode($file);
    $fileInfo['size'] = ftp_size($this->_ftpConnId, $file);
    $fileInfo['mtime'] = ftp_mdtm($this->_ftpConnId, $file);
    
    return $fileInfo;
  }
  /**
   * Transfer a file to a given server via FTP.
   * @param file The name of the file to transfer (path relative to the script)
   * @param transferMode The transfer mode [FTP_ASCII | FTP_BINARY]
   * @param toDir The server upload directory (relative to serverroot) [default: './']
   * @param createDir True/False wether to create the directory if not existing [default: true]
   * @param destName The destination file / the same as the source file if null [default: null]
   * @return True on success, False else / error string provided by getLastError()
   */
  function copy($file, $transferMode, $toDir='./', $createDir=true, $destName=null)
  {
    if ($this->_ftpConnId == null)
    {
      $this->_errorMsg = 'connect to server first';
      return null;
    }
    
    $this->_errorMsg = '';
    // suppress warnings
    $oldErrorLevel = error_reporting (E_ERROR | E_PARSE);
    if ($this->_ftpConnId != null && file_exists($file))
    {
      // upload the file
      if ($destName == null)
        $destName = basename($file);

      // change dir (if we don't have an absolute path change dir to root dir)
      if ($toDir[0] != '/')
        ftp_chdir($this->_ftpConnId, $this->_ftpRootDir);
      if (!ftp_chdir($this->_ftpConnId, $toDir))
      {
        if ($createDir)
        {
          ftp_mkdir($this->_ftpConnId, $toDir);
          ftp_chdir($this->_ftpConnId, $toDir);
        }
        else
        {
          $this->_errorMsg = 'change dir failed: '.$toDir;
          return false;
        }
      }
        
      // upload file
      $upload = ftp_put($this->_ftpConnId, $destName, $file, $transferMode); 
      // check upload status
      if (!$upload) 
        $this->_errorMsg = 'file upload failed';
      else 
        return true;
    }
    // reset error level
    error_reporting ($oldErrorLevel);
    return false;
  }
  /**
   * Synchronize a file on the server. The method copies the local file to
   * the server if the remote file does not exist, differs in size or 
   * is older than the local file.
   * @param localFile The name of the local file (path relative to the script)
   * @param remoteFile The name of the remote file (path relative to serverroot)
   * @result True/False wether the file was transfered or not
   */
  function synchronize($localFile, $remoteFile)
  {
    if ($this->_ftpConnId == null)
    {
      $this->_errorMsg = 'connect to server first';
      return null;
    }
    
    $localInfo = stat($localFile);
    $remoteInfo = $this->getFileInfo($remoteFile);

    // transfer file only if it is newer or has another size or something is wrong with the
    // remote file
    if ($remoteInfo['size'] == -1 || $remoteInfo['size'] != $localInfo['size'] || 
      $remoteInfo['mtime'] == -1 || $remoteInfo['mtime'] < $localInfo['mtime'])
    {
      $pathInfo = pathinfo($remoteFile);
      $transfered = $this->copy($localFile, FTP_BINARY, $pathInfo['dirname'], true, $pathInfo['basename']);
      if ($transfered)
        return true;
    }
    return false;
  }
  /**
   * Delete a remote file.
   * @param file The name of the file to delete
   * @return True on success, False else / error string provided by getLastError()
   */
  function delete($file)
  {
    if ($this->_ftpConnId == null)
    {
      $this->_errorMsg = 'connect to server first';
      return null;
    }
    
    return ftp_delete($this->_ftpConnId, $file);
  }
  /**
   * Close a FTP connection to a given server.
   */
  function close()
  {
    $this->_errorMsg = '';
    // suppress warnings
    $oldErrorLevel = error_reporting (E_ERROR | E_PARSE);
    // close the FTP stream 
    if ($this->_ftpConnId != null)
      ftp_quit($this->_ftpConnId);
    $this->_ftpConnId = null;
    // reset error level
    error_reporting ($oldErrorLevel);
  }
}
?>
