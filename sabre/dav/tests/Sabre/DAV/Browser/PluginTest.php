<?php

namespace Sabre\DAV\Browser;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class PluginTest extends DAV\AbstractServer{

    function setUp() {

        parent::setUp();
        $this->server->addPlugin(new Plugin());

    }

    function testCollectionGet() {

        $serverVars = array(
            'REQUEST_URI'    => '/dir',
            'REQUEST_METHOD' => 'GET',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 200 OK',$this->response->status);
        $this->assertEquals(array(
            'Content-Type' => 'text/html; charset=utf-8',
            ),
            $this->response->headers
        );

        $this->assertTrue(strpos($this->response->body, 'Index for dir/') !== false);
        $this->assertTrue(strpos($this->response->body, '<a href="/dir/child.txt"><img src="/?sabreAction=asset&assetName=icons%2Ffile.png" alt="" width="24" />')!==false);

    }

    function testNotFound() {

        $serverVars = array(
            'REQUEST_URI'    => '/random',
            'REQUEST_METHOD' => 'GET',
        );

        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 404 Not Found',$this->response->status);

    }

    function testPostOtherContentType() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'text/xml',
        );
        $request = new HTTP\Request($serverVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 501 Not Implemented', $this->response->status);

    }

    function testPostNoSabreAction() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        );
        $postVars = array();

        $request = new HTTP\Request($serverVars,$postVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 501 Not Implemented', $this->response->status);

    }

    function testPostMkCol() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        );
        $postVars = array(
            'sabreAction' => 'mkcol',
            'name' => 'new_collection',
        );

        $request = new HTTP\Request($serverVars,$postVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('HTTP/1.1 302 Found', $this->response->status);
        $this->assertEquals(array(
            'Location' => '/',
        ), $this->response->headers);

        $this->assertTrue(is_dir(SABRE_TEMPDIR . '/new_collection'));

    }

}
