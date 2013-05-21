<?php
/**
 * Class to create zip files on the fly and stream directly to the HTTP client as the content is added.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Inspired by
 * CreateZipFile by Rochak Chauhan  www.rochakchauhan.com (http://www.phpclasses.org/browse/package/2322.html)
 * and
 * ZipStream by A. Grandt https://github.com/Grandt/PHPZip (http://www.phpclasses.org/package/6116)
 *
 * @author Nicolai Ehemann (en@enlightened.de)
 * @copyright 2013 Nicolai Ehemann
 * @license GNU GPL
 * @version 0.2
 */

class GPFLAGS {
  const ADD =           0x0008;  // ADD flag (sizes and crc32 are append in data descriptor
  const EFS =           0x0800;  // EFS flag (UTF-8 encoded filename and/or comment
};

class GZMETHOD {
  const STORE =         0x0000;  //  0 - The file is stored (no compression)
  const DEFLATE =       0x0008;  //  8 - The file is Deflated
}

class ZipStreamer {
  const VERSION = 0.2;

  const ZIP_LOCAL_FILE_HEADER = "\x50\x4b\x03\x04"; // Local file header signature
  const ZIP_CENTRAL_FILE_HEADER = "\x50\x4b\x01\x02"; // Central file header signature
  const ZIP_END_OF_CENTRAL_DIRECTORY = "\x50\x4b\x05\x06"; //end of Central directory record

  const EXT_FILE_ATTR_DIR = "\x10\x00\xFF\x41";
  const EXT_FILE_ATTR_FILE = "\x00\x00\xFF\x81";

  //TODO: make this dynamic, depending on flags/compression methods
  const ATTR_VERSION_TO_EXTRACT = "\x14\x00"; // Version needed to extract
  const ATTR_MADE_BY_VERSION = "\x1E\x03"; // Made By Version

  const STREAM_CHUNK_SIZE = 1048576; // 1mb chunks

  private $cdRec = array(); // central directory
  private $offset = 0; // offset of next file to be added
  private $isFinalized = false;

  /**
   * Constructor.
   *
   * @param bool   $sendHeaders Send suitable headers to the HTTP client (assumes nothing was sent yet)
   * @param string $archiveName Name to send to the HTTP client. Optional, defaults to "archive.zip".
   * @param string $contentType Content mime type. Optional, defaults to "application/zip".
   */
  function __construct($sendHeaders = false, $archiveName = 'archive.zip',
                       $contentType = 'application/zip') {
    //TODO: is this advisable/necessary?
    if (ini_get('zlib.output_compression')) {
      ini_set('zlib.output_compression', 'Off');
    }
    if ($sendHeaders) {
      $headerFile = null;
      $headerLine = null;
      if ( !headers_sent($headerFile, $headerLine)
            or die('<p><strong>Error:</strong> Unable to send file '.
                   '$archiveName. HTML Headers have already been sent from '.
                   '<strong>$headerFile</strong> in line <strong>$headerLine'.
                   '</strong></p>')) {
        if ( (ob_get_contents() === false || ob_get_contents() == '')
            or die('\n<p><strong>Error:</strong> Unable to send file '.
                   '<strong>$archiveName.epub</strong>. Output buffer '.
                   'already contains text (typically warnings or errors).</p>')) {
          header('Pragma: public');
          header('Last-Modified: ' . gmdate('D, d M Y H:i:s T'));
          header('Expires: 0');
          header('Accept-Ranges: bytes');
          header('Connection: Keep-Alive');
          header('Content-Type: ' . $contentType);
          header('Content-Disposition: attachment; filename="' . $archiveName . '";');
          header('Content-Transfer-Encoding: binary');
        }
      }
      flush();
      // turn off output buffering
      ob_end_flush();
    }
  }

  function __destruct() {
    $this->isFinalized = true;
    $this->cdRec = null;
    exit;
  }

  /**
   * Add a file to the archive at the specified location and file name.
   *
   * @param string $stream      Stream to read data from
   * @param string $filePath    Filepath and name to be used in the archive.
   * @param int    $timestamp   (Optional) Timestamp for the added file, if omitted or set to 0, the current time will be used.
   * @param string $fileComment (Optional) Comment to be added to the archive for this file. To use fileComment, timestamp must be given.
   * @param bool   $compress    (Optional) Compress file, if set to false the file will only be stored. Default FALSE.
   * @return bool $success
   */
  public function addFileFromStream($stream, $filePath, $timestamp = 0,
                  $fileComment = null, $compress = false) {
    OC_Log::write('ZipStreamer', 'addFileFromStream', OC_Log::DEBUG);
    if ($this->isFinalized) {
      return false;
    }

    if (!is_resource($stream) || get_resource_type($stream) != 'stream') {
      return false;
    }

    $filePath = self::normalizeFilePath($filePath);

    $gpFlags = GPFLAGS::ADD;
    if ($compress) {
      $gzMethod = GZMETHOD::DEFLATE;
    } else {
      $gzMethod = GZMETHOD::STORE;
    }

    OC_Log::write('ZipStreamer', 'beginFile', OC_Log::DEBUG);
    list ($gpFlags, $lfhLength) = $this->beginFile($filePath, $fileComment, $timestamp, $gpFlags, $gzMethod);

    OC_Log::write('ZipStreamer', 'streamFileData', OC_Log::DEBUG);
    list ($dataLength, $gzLength, $dataCRC32) = $this->streamFileData($stream, $compress);

    #OC_Log::write('ZipStreamer', 'addDataDescriptor', OC_Log::DEBUG);
    #$this->addDataDescriptor($dataLength, $gzLength, $dataCRC32);

    // build cdRec
    OC_Log::write('ZipStreamer', 'buildCentralDirectoryHeader', OC_Log::DEBUG);
    $this->cdRec[] = $this->buildCentralDirectoryHeader($filePath, $timestamp, $gpFlags, $gzMethod, $dataLength, $gzLength, $dataCRC32, self::EXT_FILE_ATTR_FILE, $this->offset);

    // calc offset
    $this->offset += $lfhLength + $gzLength;

    return true;
  }

  /**
   * Add an empty directory entry to the zip archive.
   *
   * @param string $directoryPath  Directory Path and name to be added to the archive.
   * @param int    $timestamp      (Optional) Timestamp for the added directory, if omitted or set to 0, the current time will be used.
   * @param string $fileComment    (Optional) Comment to be added to the archive for this directory. To use fileComment, timestamp must be given.
   * @return bool $success
   */
  public function addEmptyDir($directoryPath, $timestamp = 0, $fileComment = null) {
    if ($this->isFinalized) {
      return false;
    }

    $directoryPath = self::normalizeFilePath($directoryPath).'/';

    if (strlen($directoryPath) > 0) {
      $gpFlags = 0x0000; // Compression type 0 = stored
      $gzMethod = GZMETHOD::STORE; // Compression type 0 = stored

      OC_Log::write('ZipStreamer', 'beginFile', OC_Log::DEBUG);
      list ($gpFlags, $lfhLength) = $this->beginFile($directoryPath, $fileComment, $timestamp, $gpFlags, $gzMethod);

      // build cdRec
      OC_Log::write('ZipStreamer', 'buildCentralDirectoryHeader', OC_Log::DEBUG);
      $this->cdRec[] = $this->buildCentralDirectoryHeader($directoryPath, $timestamp, $gpFlags, $gzMethod, 0, 0, 0, self::EXT_FILE_ATTR_DIR, $this->offset);

      return true;
    }
    return false;
  }

  /**
   * Close the archive.
   * A closed archive can no longer have new files added to it.
   * @return bool $success
   */
  public function finalize() {
  	if (!$this->isFinalized) {

  		$cd = implode('', $this->cdRec);

  		// print central directory
  		echo $cd;

  		// print end of central directory record
  		OC_Log::write('ZipStreamer', 'buildEndOfCentralDirectoryRecord', OC_Log::DEBUG);
  		echo $this->buildEndOfCentralDirectoryRecord(strlen($cd));

  		flush();

  		$this->isFinalized = true;
  		$cd = null;
  		$this->cdRec = null;

  		return true;
  	}
  	return false;
  }

  private function beginFile($filePath, $fileComment, $timestamp,
                  $gpFlags = 0x0000, $gzMethod = GZMETHOD::STORE, $dataLength = 0,
                  $gzLength = 0, $dataCRC32 = 0) {
    $isFileUTF8 = mb_check_encoding($filePath, 'UTF-8') && !mb_check_encoding($filePath, 'ASCII');
    $isCommentUTF8 = !empty($fileComment) && mb_check_encoding($fileComment, 'UTF-8') && !mb_check_encoding($fileComment, 'ASCII');
    if ($isFileUTF8 || $isCommentUTF8) {
      $gpFlags |= GPFLAGS::EFS;
    }
    OC_Log::write('ZipStreamer', 'buildLocalFileHeader', OC_Log::DEBUG);
    $localFileHeader = $this->buildLocalFileHeader($filePath, $timestamp, $gpFlags, $gzMethod, $dataLength, $gzLength, $dataCRC32);
    echo $localFileHeader;

    return array ($gpFlags, strlen($localFileHeader));
  }

  private function streamFileData($stream, $compress) {
    $dataLength = 0;
    $gzLength = 0;
    $hashCtx = hash_init('crc32b');

    while (!feof($stream)) {
      $data = fread($stream, self::STREAM_CHUNK_SIZE);
      $dataLength += strlen($data);
      hash_update($hashCtx, $data);
      if ($compress) {
        //TODO: this is broken.
        $data = gzdeflate($data);
      }
      $gzLength += strlen($data);
      echo $data;

      OC_Log::write('ZipStreamer', 'streamed '.$dataLength.' bytes of data', OC_Log::DEBUG);
      flush();
    }
    return array ($dataLength, $gzLength, unpack('N', hash_final($hashCtx, true))[1]);
  }

  private function buildLocalFileHeader($filePath, $timestamp,
                  $gpFlags = 0x0000, $gzMethod = GZMETHOD::STORE, $dataLength = 0,
                  $gzLength = 0, $dataCRC32 = 0) {
    $dosTime = self::getDosTime($timestamp);

    return ''
      . self::ZIP_LOCAL_FILE_HEADER   // local file header signature     4 bytes  (0x04034b50)
      . self::ATTR_VERSION_TO_EXTRACT //version needed to extract       2 bytes
      . pack('v', $gpFlags)           //general purpose bit flag        2 bytes
      . pack('v', $gzMethod)            //compression method              2 bytes
      . $dosTime                      //last mod file time              2 bytes
                                      //last mod file date              2 bytes
      . pack('V', $dataCRC32)         //crc-32                          4 bytes
      . pack('V', $gzLength)          //compressed size                 4 bytes
      . pack('V', $dataLength)        //uncompressed size               4 bytes
      . pack('v', strlen($filePath))  //file name length                2 bytes
      //TODO: implement?
      . pack('v', 0)                  //extra field length              2 bytes
      . $filePath                     //file name (variable size)
      //TODO: implement?
      . '';                           //extra field (variable size)
  }

  private function buildCentralDirectoryHeader($filePath, $timestamp, $gpFlags,
                  $gzMethod, $dataLength, $gzLength, $dataCRC32, $extFileAttr,
                  $offset) {
    $dosTime = self::getDosTime($timestamp);

    return ''
      . self::ZIP_CENTRAL_FILE_HEADER   //central file header signature   4 bytes  (0x02014b50)
      . self::ATTR_MADE_BY_VERSION      //version made by                 2 bytes
      . self::ATTR_VERSION_TO_EXTRACT   //version needed to extract       2 bytes
      . pack('v', $gpFlags)             //general purpose bit flag        2 bytes
      . pack('v', $gzMethod)              //compression method              2 bytes
      . $dosTime                        //last mod file time              2 bytes
                                        //last mod file date              2 bytes
      . pack('V', $dataCRC32)           //crc-32                          4 bytes
      . pack('V', $gzLength)            //compressed size                 4 bytes
      . pack('V', $dataLength)          //uncompressed size               4 bytes
      . pack('v', strlen($filePath))    //file name length                2 bytes
      //TODO: implement?
      . pack('v', 0)                    //extra field length              2 bytes
      //TODO: implement?
      . pack('v', 0)                    //file comment length             2 bytes
      . pack('v', 0)                    //disk number start               2 bytes
      . pack('v', 0)                    //internal file attributes        2 bytes
      . $extFileAttr                    //external file attributes        4 bytes
      . pack('V', $offset)              //relative offset of local header 4 bytes
      . $filePath                       //file name (variable size)
      //TODO: implement?
      . ''                              //extra field (variable size)
      //TODO: implement?
      . '';                             //file comment (variable size)
  }

  private function buildEndOfCentralDirectoryRecord($cdRecLength) {
    $cdRecCount = sizeof($this->cdRec);

    return ''
      . self::ZIP_END_OF_CENTRAL_DIRECTORY  //end of central dir signature    4 bytes  (0x06054b50)
      . pack('v', 0)                        //number of this disk             2 bytes
      . pack('v', 0)                        //number of the disk with the
                                            //start of the central directory  2 bytes
      . pack('v', $cdRecCount)              //total number of entries in the
                                            //central directory on this disk  2 bytes
      . pack('v', $cdRecCount)              //total number of entries in
                                            //the central directory           2 bytes
      . pack('V', $cdRecLength)             //size of the central directory   4 bytes
      . pack('V', $this->offset)            //offset of start of central
                                            //directory with respect to
                                            //the starting disk number        4 bytes
      //TODO: implement?
      . pack('v', 0)                        //.ZIP file comment length        2 bytes
      //TODO: implement?
      . '';                                 //.ZIP file comment       (variable size)
  }

  private function addDataDescriptor($dataLength,  $gzLength, $dataCRC32) {
    echo $this->buildDataDescriptor($dataCRC32, $gzLength, $dataLength);
  }

  private function buildDataDescriptor($dataCRC32, $gzLength, $dataLength) {

    return ''
      . pack('V', $dataCRC32)    //crc-32                          4 bytes
      . pack('V', $gzLength)     //compressed size                 4 bytes
      . pack('V', $dataLength);  //uncompressed size               4 bytes
  }

  // Utility methods ////////////////////////////////////////////////////////

  private static function normalizeFilePath($filePath) {
    return trim(str_replace('\\', '/', $filePath), '/');
  }

  /**
   * Calculate the 2 byte dostime used in the zip entries.
   *
   * @param int $timestamp
   * @return 2-byte encoded DOS Date
   */
  private static function getDosTime($timestamp = 0) {
  	$timestamp = (int)$timestamp;
  	$oldTZ = @date_default_timezone_get();
  	date_default_timezone_set('UTC');
  	$date = ($timestamp == 0 ? getdate() : getdate($timestamp));
  	date_default_timezone_set($oldTZ);
  	if ($date['year'] >= 1980) {
  		return pack('V', (($date['mday'] + ($date['mon'] << 5) + (($date['year']-1980) << 9)) << 16) |
  				(($date['seconds'] >> 1) + ($date['minutes'] << 5) + ($date['hours'] << 11)));
  	}
  	return "\x00\x00\x00\x00";
  }

}
