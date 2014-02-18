<?php

namespace Sabre\DAV\FSExt;

use Sabre\DAV;

require_once 'Sabre/TestUtil.php';

class FileTest extends \PHPUnit_Framework_TestCase {

    function setUp() {

        file_put_contents(SABRE_TEMPDIR . '/file.txt', 'Contents');

    }

    function tearDown() {

        \Sabre\TestUtil::clearTempDir();

    }

    function testPut() {

       $file = new File(SABRE_TEMPDIR . '/file.txt');
       $result = $file->put('New contents');

       $this->assertEquals('New contents',file_get_contents(SABRE_TEMPDIR . '/file.txt'));
       $this->assertEquals('"' . md5('New contents') . '"', $result);

    }

    function testRange() {

       $file = new File(SABRE_TEMPDIR . '/file.txt');
       $file->put('0000000');
       $file->putRange('111',3);

       $this->assertEquals('0011100',file_get_contents(SABRE_TEMPDIR . '/file.txt'));

    }

    function testRangeStream() {

       $stream = fopen('php://memory','r+');
       fwrite($stream, "222");
       rewind($stream);

       $file = new File(SABRE_TEMPDIR . '/file.txt');
       $file->put('0000000');
       $file->putRange($stream,3);

       $this->assertEquals('0022200',file_get_contents(SABRE_TEMPDIR . '/file.txt'));

    }


    function testGet() {

       $file = new File(SABRE_TEMPDIR . '/file.txt');
       $this->assertEquals('Contents',stream_get_contents($file->get()));

    }

    function testDelete() {

       $file = new File(SABRE_TEMPDIR . '/file.txt');
       $file->delete();

       $this->assertFalse(file_exists(SABRE_TEMPDIR . '/file.txt'));

    }

    function testGetETag() {

       $file = new File(SABRE_TEMPDIR . '/file.txt');
       $this->assertEquals('"' . md5('Contents') . '"',$file->getETag());

    }

    function testGetContentType() {

       $file = new File(SABRE_TEMPDIR . '/file.txt');
       $this->assertNull($file->getContentType());

    }

    function testGetSize() {

       $file = new File(SABRE_TEMPDIR . '/file.txt');
       $this->assertEquals(8,$file->getSize());

    }

}
