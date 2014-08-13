<?php
/**
* Copyright (C) 2014 Nicolai Ehemann <en@enlightened.de>
*
* This file is licensed under the GNU GPL version 3 or later.
* See COPYING for details.
*/
require "src/ZipStreamer.php";

class TestZipStreamer extends PHPUnit_Framework_TestCase
{
  protected $outstream;

  protected function setUp() {
    parent::setUp();
    $this->outstream = fopen('php://memory', 'rw');
  }

  protected function tearDown() {
    fclose($this->outstream);
    parent::tearDown();
  }

  protected function getOutput() {
    rewind($this->outstream);
    return stream_get_contents($this->outstream);
  }

  protected function assertOutputEqualsFile($filename) {
    return $this->assertEquals(file_get_contents($filename), $this->getOutput());
  }

  protected function assertContainsOneMatch($pattern, $input) {
    $results = preg_grep($pattern, $input);
    return $this->assertEquals(1, sizeof($results));
  }

  public function providerSendHeadersOK() {
    # array(filename, mimetype), expectedMimetype, expextedFilename, $description
    return array(
      array(array(), 'application/zip', 'archive.zip', 'default headers'),
      array(array('file.zip', 'application/octet-stream'), 'application/octet-stream', 'file.zip', 'specific headers')
    );
  }

  /**
  * @dataProvider providerSendHeadersOK
  * @preserveGlobalState disabled
  * @runInSeparateProcess
  */
  public function testSendHeadersOK($arguments, $expectedMimetype, $expectedFilename, $description) {
    $zip = new ZipStreamer\ZipStreamer(array(
                 'outstream' => $this->outstream
    ));
    call_user_func_array(array($zip, "sendHeaders"), $arguments);
    $headers = xdebug_get_headers();
    $this->assertContains('Pragma: public', $headers);
    $this->assertContains('Expires: 0', $headers);
    $this->assertContains('Accept-Ranges: bytes', $headers);
    $this->assertContains('Connection: Keep-Alive', $headers);
    $this->assertContains('Content-Transfer-Encoding: binary', $headers);
    $this->assertContains('Content-Type: '.$expectedMimetype, $headers);
    $this->assertContains('Content-Disposition: attachment; filename="' . $expectedFilename . '";', $headers);
    $this->assertContainsOneMatch('/^Last-Modified: /', $headers);
  }

  public function testZipfileEmpty() {
    $zip = new ZipStreamer\ZipStreamer(array(
                 'outstream' => $this->outstream
    ));
    $zip->finalize();
    $this->assertOutputEqualsFile('test/data/empty.zip');
  }

  public function testZipfileOneEmptyDir() {
    $zip = new ZipStreamer\ZipStreamer(array(
                 'outstream' => $this->outstream
    ));
    
    $zip->addEmptyDir("test", 1);
    
    $zip->finalize();
    $this->assertOutputEqualsFile('test/data/oneEmptyDir.zip');
  }

  public function testZipfileOneFile() {
    $zip = new ZipStreamer\ZipStreamer(array(
                 'outstream' => $this->outstream
    ));
    
    $string = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed elit diam, posuere vel aliquet et, malesuada quis purus. Aliquam mattis aliquet massa, a semper sem porta in. Aliquam consectetur ligula a nulla vestibulum dictum. Interdum et malesuada fames ac ante ipsum primis in faucibus. Nullam luctus faucibus urna, accumsan cursus neque laoreet eu. Suspendisse potenti. Nulla ut feugiat neque. Maecenas molestie felis non purus tempor, in blandit ligula tincidunt. Ut in tortor sit amet nisi rutrum vestibulum vel quis tortor. Sed bibendum mauris sit amet gravida tristique. Ut hendrerit sapien vel tellus dapibus, eu pharetra nulla adipiscing. Donec in quam faucibus, cursus lacus sed, elementum ligula. Morbi volutpat vel lacus malesuada condimentum. Fusce consectetur nisl euismod justo volutpat sodales.';
    $stream = fopen('php://memory','r+');
    fwrite($stream, $string);
    rewind($stream);
    $zip->addFileFromStream($stream, 'test1.txt', 1);
    fclose($stream);

    $zip->finalize();
    $this->assertOutputEqualsFile('test/data/oneFile.zip');
  }

  public function testZipfileSimpleStructure() {
    $zip = new ZipStreamer\ZipStreamer(array(
                 'outstream' => $this->outstream
    ));

    $string = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed elit diam, posuere vel aliquet et, malesuada quis purus. Aliquam mattis aliquet massa, a semper sem porta in. Aliquam consectetur ligula a nulla vestibulum dictum. Interdum et malesuada fames ac ante ipsum primis in faucibus. Nullam luctus faucibus urna, accumsan cursus neque laoreet eu. Suspendisse potenti. Nulla ut feugiat neque. Maecenas molestie felis non purus tempor, in blandit ligula tincidunt. Ut in tortor sit amet nisi rutrum vestibulum vel quis tortor. Sed bibendum mauris sit amet gravida tristique. Ut hendrerit sapien vel tellus dapibus, eu pharetra nulla adipiscing. Donec in quam faucibus, cursus lacus sed, elementum ligula. Morbi volutpat vel lacus malesuada condimentum. Fusce consectetur nisl euismod justo volutpat sodales.';
    $stream = fopen('php://memory','r+');
    fwrite($stream, $string);
    rewind($stream);
    $zip->addFileFromStream($stream, 'test1.txt', 1);
    fclose($stream);

    $zip->addEmptyDir("test", 1);
 
    $string = 'Duis malesuada lorem lorem, id sodales sapien sagittis ac. Donec in porttitor tellus, eu aliquam elit. Curabitur eu aliquam eros. Nulla accumsan augue quam, et consectetur quam eleifend eget. Donec cursus dolor lacus, eget pellentesque risus tincidunt at. Pellentesque rhoncus purus eget semper porta. Duis in magna tincidunt, fermentum orci non, consectetur nibh. Aliquam tortor eros, dignissim a posuere ac, rhoncus a justo. Sed sagittis velit ac massa pulvinar, ac pharetra ipsum fermentum. Etiam commodo lorem a scelerisque facilisis. ';
    $stream = fopen('php://memory','r+');
    fwrite($stream, $string);
    rewind($stream);
    $zip->addFileFromStream($stream, 'test/test2.txt', 1);
    fclose($stream);

    $zip->finalize();
    $this->assertOutputEqualsFile('test/data/simpleStructure.zip');    
  }

}

?>
