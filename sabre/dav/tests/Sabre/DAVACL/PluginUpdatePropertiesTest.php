<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;


require_once 'Sabre/DAVACL/MockPrincipal.php';

class PluginUpdatePropertiesTest extends \PHPUnit_Framework_TestCase {

    public function testUpdatePropertiesPassthrough() {

        $tree = array(
            new DAV\SimpleCollection('foo'),
        );
        $server = new DAV\Server($tree);
        $server->addPlugin(new Plugin());

        $result = $server->updateProperties('foo', array(
            '{DAV:}foo' => 'bar',
        ));

        $expected = array(
            'href' => 'foo',
            '403' => array(
                '{DAV:}foo' => null,
            ),
        );

        $this->assertEquals($expected, $result);

    }

    public function testRemoveGroupMembers() {

        $tree = array(
            new MockPrincipal('foo','foo'),
        );
        $server = new DAV\Server($tree);
        $server->addPlugin(new Plugin());

        $result = $server->updateProperties('foo', array(
            '{DAV:}group-member-set' => null,
        ));

        $expected = array(
            'href' => 'foo',
            '200' => array(
                '{DAV:}group-member-set' => null,
            ),
        );

        $this->assertEquals($expected, $result);
        $this->assertEquals(array(),$tree[0]->getGroupMemberSet());

    }

    public function testSetGroupMembers() {

        $tree = array(
            new MockPrincipal('foo','foo'),
        );
        $server = new DAV\Server($tree);
        $server->addPlugin(new Plugin());

        $result = $server->updateProperties('foo', array(
            '{DAV:}group-member-set' => new DAV\Property\HrefList(array('/bar','/baz'), true),
        ));

        $expected = array(
            'href' => 'foo',
            '200' => array(
                '{DAV:}group-member-set' => null,
            ),
        );

        $this->assertEquals($expected, $result);
        $this->assertEquals(array('bar','baz'),$tree[0]->getGroupMemberSet());

    }

    /**
     * @expectedException Sabre\DAV\Exception
     */
    public function testSetBadValue() {

        $tree = array(
            new MockPrincipal('foo','foo'),
        );
        $server = new DAV\Server($tree);
        $server->addPlugin(new Plugin());

        $result = $server->updateProperties('foo', array(
            '{DAV:}group-member-set' => new \StdClass(),
        ));

    }

    public function testSetBadNode() {

        $tree = array(
            new DAV\SimpleCollection('foo'),
        );
        $server = new DAV\Server($tree);
        $server->addPlugin(new Plugin());

        $result = $server->updateProperties('foo', array(
            '{DAV:}group-member-set' => new DAV\Property\HrefList(array('/bar','/baz'),false),
            '{DAV:}bar' => 'baz',
        ));

        $expected = array(
            'href' => 'foo',
            '403' => array(
                '{DAV:}group-member-set' => null,
            ),
            '424' => array(
                '{DAV:}bar' => null,
            ),
        );

        $this->assertEquals($expected, $result);

    }
}
