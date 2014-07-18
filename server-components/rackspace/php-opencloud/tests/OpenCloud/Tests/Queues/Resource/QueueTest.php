<?php

/**
 * @copyright Copyright 2012-2014 Rackspace US, Inc.
  See COPYING for licensing information.
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version   1.5.9
 * @author    Glen Campbell <glen.campbell@rackspace.com>
 * @author    Jamie Hannaford <jamie.hannaford@rackspace.com>
 */

namespace OpenCloud\Tests\Queues\Resource\Resource;

use OpenCloud\Queues\Service;
use OpenCloud\Common\Metadata;
use OpenCloud\Tests\Queues\QueuesTestCase;

class QueueTest extends QueuesTestCase
{

    public function test_Create()
    {
        $this->addMockSubscriber($this->makeResponse(null, 201));
        $this->assertInstanceOf('OpenCloud\Queues\Resource\Queue', $this->service->createQueue('test'));
    }
    
    /**
     * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function test_Create_Fails_With_Incorrect_Response()
    {
        $this->addMockSubscriber($this->makeResponse(null, 404));
        $this->service->createQueue('baz');
    }
    
    /**
     * @expectedException OpenCloud\Queues\Exception\QueueException
     */
    public function test_Create_Fails_With_Incorrect_Name()
    {
        $this->service->createQueue('baz!!!*');
    }
    
    /**
     * @expectedException OpenCloud\Common\Exceptions\UpdateError
     */
    public function test_Update_Fails()
    {
        $this->queue->update(array('name' => 'new name'));
    }
    
    public function test_Delete()
    {
        $this->queue->setName('test');
        $this->queue->delete();
    }
    
    public function test_Metadata()
    {
        $this->addMockSubscriber($this->makeResponse(null, 204));

        $this->queue->setName('test')
                    ->saveMetadata(array(
                        'new metadata' => 'bar'
                    ));
        
        $metadata = $this->queue->getMetadata();

        $this->assertInstanceOf('OpenCloud\Common\Metadata', $metadata);
        $this->assertEquals('bar', $metadata->{'new metadata'});
        
        $newMetadata = new Metadata();
        $newMetadata->setArray(array(
            'foo' => 'bar'
        ));
        $this->queue->setMetadata($newMetadata);
    }
    
    /**
     * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function test_Metadata_Fails_If_Queue_Not_Found()
    {
        $this->addMockSubscriber($this->makeResponse(null, 404));
        $this->queue->retrieveMetadata();
    }
    
    /**
     * @expectedException OpenCloud\Common\Exceptions\InvalidArgumentError
     */
    public function test_SetMetadata_Fails_Without_Correct_Data()
    {
        $this->queue->setMetadata('');
    }
            
    public function test_Stats()
    {
        $this->assertNotNull($this->queue->setName('foo')->getStats());
    }
    
    public function test_Get_Message()
    {
        $this->assertInstanceOf(
            'OpenCloud\Queues\Resource\Message', 
            $this->queue->getMessage()
        );
    }
    
    public function test_List_Message()
    {
        $this->assertInstanceOf(
            self::COLLECTION_CLASS,
            $this->queue->setName('foo')->listMessages(array(
                'ids' => array(100, 901, 58)
            ))
        );
    }
    
    public function test_Delete_Message()
    {
        $this->assertTrue(
            $this->queue->setName('foo')->deleteMessages(array(100, 901, 58))
        );
    }
            
    public function test_Claim_Messages()
    {
        $this->addMockSubscriber($this->makeResponse('[{"body":{"event":"BackupStarted"},"age":239,"href":"/v1/queues/demoqueue/messages/51db6f78c508f17ddc924357?claim_id=51db7067821e727dc24df754","ttl":300}]', 201));
        $this->assertInstanceOf(
            'OpenCloud\Queues\Resource\Message',
            $this->queue->setName('foo')->claimMessages()->first()
        );
    }
    
    /**
     * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function test_Claim_Messages_Fails_If_Queue_Not_Found()
    {
        $this->addMockSubscriber($this->makeResponse(null, 404));

        $this->queue->claimMessages();
    }
    
    public function test_Getting_Claim()
    {
        $claim = $this->queue->getClaim();
        
        $this->assertInstanceOf('OpenCloud\Queues\Resource\Claim', $claim);
        $this->assertEquals($this->queue, $claim->getParent());
    }
    
}