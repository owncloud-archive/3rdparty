<?php

namespace Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/DAV/AbstractServer.php';

class ServerPropsTest extends AbstractServer {

    protected function getRootNode() {

        return new FSExt\Directory(SABRE_TEMPDIR);

    }

    function setUp() {

        if (file_exists(SABRE_TEMPDIR.'../.sabredav')) unlink(SABRE_TEMPDIR.'../.sabredav');
        parent::setUp();
        file_put_contents(SABRE_TEMPDIR . '/test2.txt', 'Test contents2');
        mkdir(SABRE_TEMPDIR . '/col');
        file_put_contents(SABRE_TEMPDIR . 'col/test.txt', 'Test contents');
        $this->server->addPlugin(new Locks\Plugin(new Locks\Backend\File(SABRE_TEMPDIR . '/.locksdb')));

    }

    function tearDown() {

        parent::tearDown();
        if (file_exists(SABRE_TEMPDIR.'../.locksdb')) unlink(SABRE_TEMPDIR.'../.locksdb');

    }

    private function sendRequest($body) {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'PROPFIND',
            'HTTP_DEPTH'          => '0',
        );

        $request = new HTTP\Request($serverVars);
        $request->setBody($body);

        $this->server->httpRequest = ($request);
        $this->server->exec();

    }

    public function testPropFindEmptyBody() {

        $hasFired = false;

        $self = $this;
        // Also testing the beforeGetPropertiesForPath event.
        $this->server->subscribeEvent('beforeGetPropertiesForPath', function($path, $properties, $depth) use ($self, &$hasFired) {

            $hasFired = true;
            $self->assertEquals('', $path);
            $self->assertEquals(array(), $properties);
            $self->assertEquals(0, $depth);

        });

        $this->sendRequest("");

        $this->assertTrue($hasFired);

        $this->assertEquals('HTTP/1.1 207 Multi-Status',$this->response->status);

        $this->assertEquals(array(
                'Content-Type' => 'application/xml; charset=utf-8',
                'DAV' => '1, 3, extended-mkcol, 2',
                'Vary' => 'Brief,Prefer',
            ),
            $this->response->headers
         );

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d','urn:DAV');

        list($data) = $xml->xpath('/d:multistatus/d:response/d:href');
        $this->assertEquals('/',(string)$data,'href element should have been /');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:resourcetype');
        $this->assertEquals(1,count($data));

    }

    function testSupportedLocks() {

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:supportedlock />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d','urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry');
        $this->assertEquals(2,count($data),'We expected two \'d:lockentry\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope');
        $this->assertEquals(2,count($data),'We expected two \'d:lockscope\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:locktype');
        $this->assertEquals(2,count($data),'We expected two \'d:locktype\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope/d:shared');
        $this->assertEquals(1,count($data),'We expected a \'d:shared\' tag');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope/d:exclusive');
        $this->assertEquals(1,count($data),'We expected a \'d:exclusive\' tag');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:locktype/d:write');
        $this->assertEquals(2,count($data),'We expected two \'d:write\' tags');
    }

    function testLockDiscovery() {

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:lockdiscovery />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d','urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:lockdiscovery');
        $this->assertEquals(1,count($data),'We expected a \'d:lockdiscovery\' tag');

    }

    function testUnknownProperty() {

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:macaroni />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);
        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d','urn:DAV');
        $pathTests = array(
            '/d:multistatus',
            '/d:multistatus/d:response',
            '/d:multistatus/d:response/d:propstat',
            '/d:multistatus/d:response/d:propstat/d:status',
            '/d:multistatus/d:response/d:propstat/d:prop',
            '/d:multistatus/d:response/d:propstat/d:prop/d:macaroni',
        );
        foreach($pathTests as $test) {
            $this->assertTrue(count($xml->xpath($test))==true,'We expected the ' . $test . ' element to appear in the response, we got: ' . $body);
        }

        $val = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
        $this->assertEquals(1,count($val),$body);
        $this->assertEquals('HTTP/1.1 404 Not Found',(string)$val[0]);

    }

    /**
     * @covers Sabre\DAV\Server::parsePropPatchRequest
     */
    public function testParsePropPatchRequest() {

        $body = '<?xml version="1.0"?>
<d:propertyupdate xmlns:d="DAV:" xmlns:s="http://sabredav.org/NS/test">
  <d:set><d:prop><s:someprop>somevalue</s:someprop></d:prop></d:set>
  <d:remove><d:prop><s:someprop2 /></d:prop></d:remove>
  <d:set><d:prop><s:someprop3>removeme</s:someprop3></d:prop></d:set>
  <d:remove><d:prop><s:someprop3 /></d:prop></d:remove>
</d:propertyupdate>';

        $result = $this->server->parsePropPatchRequest($body);
        $this->assertEquals(array(
            '{http://sabredav.org/NS/test}someprop' => 'somevalue',
            '{http://sabredav.org/NS/test}someprop2' => null,
            '{http://sabredav.org/NS/test}someprop3' => null,
            ), $result);

    }

    /**
     * @covers Sabre\DAV\Server::updateProperties
     */
    public function testUpdateProperties() {

        $props = array(
            '{http://sabredav.org/NS/test}someprop' => 'somevalue',
        );

        $result = $this->server->updateProperties('/test2.txt',$props);

        $this->assertEquals(array(
            '200' => array('{http://sabredav.org/NS/test}someprop' => null),
            'href' => '/test2.txt',
        ), $result);

    }

    /**
     * @covers Sabre\DAV\Server::updateProperties
     * @depends testUpdateProperties
     */
    public function testUpdatePropertiesProtected() {

        $props = array(
            '{http://sabredav.org/NS/test}someprop' => 'somevalue',
            '{DAV:}getcontentlength' => 50,
        );

        $result = $this->server->updateProperties('/test2.txt',$props);

        $this->assertEquals(array(
            '424' => array('{http://sabredav.org/NS/test}someprop' => null),
            '403' => array('{DAV:}getcontentlength' => null),
            'href' => '/test2.txt',
        ), $result);

    }

    /**
     * @covers Sabre\DAV\Server::updateProperties
     * @depends testUpdateProperties
     */
    public function testUpdatePropertiesFail1() {

        $dir = new PropTestDirMock('updatepropsfalse');
        $objectTree = new ObjectTree($dir);
        $this->server->tree = $objectTree;

        $props = array(
            '{http://sabredav.org/NS/test}someprop' => 'somevalue',
        );

        $result = $this->server->updateProperties('/',$props);

        $this->assertEquals(array(
            '403' => array('{http://sabredav.org/NS/test}someprop' => null),
            'href' => '/',
        ), $result);

    }

    /**
     * @covers Sabre\DAV\Server::updateProperties
     * @depends testUpdateProperties
     */
    public function testUpdatePropertiesFail2() {

        $dir = new PropTestDirMock('updatepropsarray');
        $objectTree = new ObjectTree($dir);
        $this->server->tree = $objectTree;

        $props = array(
            '{http://sabredav.org/NS/test}someprop' => 'somevalue',
        );

        $result = $this->server->updateProperties('/',$props);

        $this->assertEquals(array(
            '402' => array('{http://sabredav.org/NS/test}someprop' => null),
            'href' => '/',
        ), $result);

    }

    /**
     * @covers Sabre\DAV\Server::updateProperties
     * @depends testUpdateProperties
     * @expectedException Sabre\DAV\Exception
     */
    public function testUpdatePropertiesFail3() {

        $dir = new PropTestDirMock('updatepropsobj');
        $objectTree = new ObjectTree($dir);
        $this->server->tree = $objectTree;

        $props = array(
            '{http://sabredav.org/NS/test}someprop' => 'somevalue',
        );

        $result = $this->server->updateProperties('/',$props);

    }

    /**
     * @depends testParsePropPatchRequest
     * @depends testUpdateProperties
     * @covers Sabre\DAV\Server::httpPropPatch
     */
    public function testPropPatch() {

        $serverVars = array(
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'PROPPATCH',
        );

        $body = '<?xml version="1.0"?>
<d:propertyupdate xmlns:d="DAV:" xmlns:s="http://www.rooftopsolutions.nl/testnamespace">
  <d:set><d:prop><s:someprop>somevalue</s:someprop></d:prop></d:set>
</d:propertyupdate>';

        $request = new HTTP\Request($serverVars);
        $request->setBody($body);

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(array(
                'Content-Type' => 'application/xml; charset=utf-8',
                'Vary' => 'Brief,Prefer',
            ),
            $this->response->headers
         );

        $this->assertEquals('HTTP/1.1 207 Multi-Status',$this->response->status,'We got the wrong status. Full XML response: ' . $this->response->body);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d','urn:DAV');
        $xml->registerXPathNamespace('bla','http://www.rooftopsolutions.nl/testnamespace');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop');
        $this->assertEquals(1,count($data),'We expected one \'d:prop\' element. Response body: ' . $body);

        $data = $xml->xpath('//bla:someprop');
        $this->assertEquals(1,count($data),'We expected one \'s:someprop\' element. Response body: ' . $body);

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
        $this->assertEquals(1,count($data),'We expected one \'s:status\' element. Response body: ' . $body);

        $this->assertEquals('HTTP/1.1 200 OK',(string)$data[0]);

    }

    /**
     * @depends testPropPatch
     */
    public function testPropPatchAndFetch() {

        $this->testPropPatch();
        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:" xmlns:s="http://www.rooftopsolutions.nl/testnamespace">
  <d:prop>
    <s:someprop />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/","xmlns\\1=\"urn:DAV\"",$this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d','urn:DAV');
        $xml->registerXPathNamespace('bla','http://www.rooftopsolutions.nl/testnamespace');

        $xpath='//bla:someprop';
        $result = $xml->xpath($xpath);
        $this->assertEquals(1,count($result),'We couldn\'t find our new property in the response. Full response body:' . "\n" . $body);
        $this->assertEquals('somevalue',(string)$result[0],'We couldn\'t find our new property in the response. Full response body:' . "\n" . $body);

    }

}

class PropTestDirMock extends SimpleCollection implements IProperties {

    public $type;

    function __construct($type) {

        $this->type =$type;
        parent::__construct('root');

    }

    function updateProperties($updateProperties) {

        switch($this->type) {
            case 'updatepropsfalse' : return false;
            case 'updatepropsarray' :
                $r = array(402 => array());
                foreach($updateProperties as $k=>$v) $r[402][$k] = null;
                return $r;
            case 'updatepropsobj' :
                return new \STDClass();
        }

    }

    function getProperties($requestedPropeties) {

        return array();

    }

}
