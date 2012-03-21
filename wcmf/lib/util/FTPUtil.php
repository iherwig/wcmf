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
namespace wcmf\lib\util;

/**
 * FTPUtil provides support for ftp functionality.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FTPUtil {

  private $_ftpConnId = null;
  private $_ftpRootDir = '';

  /**
   * Open a FTP connection to a given server.
   * @param params Initialization data given in an assoziative array with the following keys:
   *               server, port, login, password
   */
  public function open($params) {
    // check ftp support
    if (!function_exists(ftp_connect)) {
      throw new RuntimeException('FTP extension missing');
    }

    // suppress warnings
    $oldErrorLevel = error_reporting(E_ERROR | E_PARSE);
    $error = '';
    // set up basic connection
    $connId = ftp_connect($params['server'], $params['port'], 10);
    if ($connId) {
      // login with username and password
      $loginResult = ftp_login($connId, $params['login'], $params['password']);
      // check connection
      if ($loginResult) {
        $this->_ftpConnId = $connId;
        // remember the root dir, which is needed if a relative path is given in copy()
        $this->_ftpRootDir = ftp_pwd($this->_ftpConnId);
        // use passive mode
        //ftp_pasv($this->_ftpConnId, true);
      }
      else  {
        $error = 'Login failed';
      }
    }
    else {
      $error = 'Connection to server failed';
    }
    error_reporting($oldErrorLevel);
    if (strlen($error) > 0) {
      throw new RuntimeException($error);
    }
  }

  /**
   * Get the files in a remote directory that match a pattern
   * @param dir The directory to search in [default: .]
   * @param pattern The pattern (regexp) to match [default: /./]
   * @param prependDirectoryName True/False whether to prepend the directory name to each file [default: false]
   * @param recursive True/False whether to recurse into subdirectories [default: false]
   * @return An array containing the filenames
   */
  public function getFiles($directory='.', $pattern='/./', $prependDirectoryName=false, $recursive=false) {
    if ($this->_ftpConnId == null) {
      throw new RuntimeConnection('Not connected. Connect to server first');
    }

    $result = array();
    $isfile = ftp_size($this->_ftpConnId, urldecode($directory));
    if ($isfile == "-1") {
      $fileList = ftp_nlist($this->_ftpConnId, $directory);
      foreach ($fileList as $file) {
        $isfile = ftp_size($this->_ftpConnId, urldecode($file));
        if($recursive && $isfile == "-1") {
          $files = $this->getFiles($file , $pattern, $prependDirectoryName, $recursive);
          $result = array_merge($result, $files);
        }
        else if($isfile != "-1" && preg_match($pattern, $file)) {
          if (!$prependDirectoryName) {
            $file = substr($file, strrpos($file, '/')+1);
          }
          $result[] = $file;
        }
      }
    }
    else {
      throw new RuntimeException("The directory '".$directory."' does not exist.");
    }
    return $result;
  }

  /**
   * Get size an modification date of a remote file.
   * @param file The name of the file to get the info for
   * @return An assoziative array with keys 'size' and 'mtime' (values -1 indicate an error)
   */
  public function getFileInfo($file) {
    if ($this->_ftpConnId == null) {
      throw new RuntimeConnection('Not connected. Connect to server first');
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
   * @param createDir True/False whether to create the directory if not existing [default: true]
   * @param destName The destination file / the same as the source file if null [default: null]
   */
  public function copy($file, $transferMode, $toDir='./', $createDir=true, $destName=null) {
    if ($this->_ftpConnId == null) {
      throw new RuntimeConnection('Not connected. Connect to server first');
    }

    // suppress warnings
    $oldErrorLevel = error_reporting (E_ERROR | E_PARSE);
    $error = '';
    if ($this->_ftpConnId != null && file_exists($file)) {
      // upload the file
      if ($destName == null) {
        $destName = basename($file);
      }
      // change dir (if we don't have an absolute path change dir to root dir)
      $changedDir = false;
      if ($toDir[0] != '/') {
        ftp_chdir($this->_ftpConnId, $this->_ftpRootDir);
      }
      $changedDir = ftp_chdir($this->_ftpConnId, $toDir);
      if (!$changedDir && $createDir) {
        ftp_mkdir($this->_ftpConnId, $toDir);
        $changedDir = ftp_chdir($this->_ftpConnId, $toDir);
      }
      if ($changedDir) {
        // upload file
        $upload = ftp_put($this->_ftpConnId, $destName, $file, $transferMode);
        // check upload status
        if (!$upload) {
          $error = 'File upload failed';
        }
      }
      else {
        $error = 'Change dir failed: '.$toDir;
      }
    }
    // reset error level
    error_reporting($oldErrorLevel);
    if (strlen($error) > 0) {
      throw new RuntimeException($error);
    }
  }
  /**
   * Synchronize a file on the server. The method copies the local file to
   * the server if the remote file does not exist, differs in size or
   * is older than the local file.
   * @param localFile The name of the local file (path relative to the script)
   * @param remoteFile The name of the remote file (path relative to serverroot)
   * @return Boolean whether the file was transfered or not
   */
  public function synchronize($localFile, $remoteFile) {
    if ($this->_ftpConnId == null) {
      throw new RuntimeConnection('Not connected. Connect to server first');
    }

    $localInfo = stat($localFile);
    $remoteInfo = $this->getFileInfo($remoteFile);

    // transfer file only if it is newer or has another size or something is wrong with the
    // remote file
    if ($remoteInfo['size'] == -1 || $remoteInfo['size'] != $localInfo['size'] ||
      $remoteInfo['mtime'] == -1 || $remoteInfo['mtime'] < $localInfo['mtime']) {

      $pathInfo = pathinfo($remoteFile);
      $this->copy($localFile, FTP_BINARY, $pathInfo['dirname'], true, $pathInfo['basename']);
      return true;
    }
    return false;
  }

  /**
   * Delete a remote file.
   * @param file The name of the file to delete
   * @return Boolean wheter successful or not
   */
  public function delete($file) {
    if ($this->_ftpConnId == null) {
      throw new RuntimeConnection('Not connected. Connect to server first');
    }
    return ftp_delete($this->_ftpConnId, $file);
  }

  /**
   * Close a FTP connection to a given server.
   */
  public function close() {
    // suppress warnings
    $oldErrorLevel = error_reporting (E_ERROR | E_PARSE);
    // close the FTP stream
    if ($this->_ftpConnId != null) {
      ftp_quit($this->_ftpConnId);
    }
    $this->_ftpConnId = null;
    // reset error level
    error_reporting ($oldErrorLevel);
  }
}
?>
