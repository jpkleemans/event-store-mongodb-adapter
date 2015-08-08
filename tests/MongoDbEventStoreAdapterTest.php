<?php

namespace Prooph\EventStore\Adapter\MongDbTest;

use Prooph\EventStore\Adapter\MongoDb\MongoDbEventStoreAdapter;
use Prooph\EventStore\Stream\DomainEventMetadataWriter;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use Prooph\EventStoreTest\Mock\UserCreated;
use Prooph\EventStoreTest\Mock\UsernameChanged;
use Prooph\EventStoreTest\TestCase;

/**
 * Class MongoDbEventStoreAdapterTest
 * @package Prooph\EventStore\Adapter\MongDbTest
 */
class MongoDbEventStoreAdapterTest extends TestCase
{
    /**
     * @var MongoDbEventStoreAdapter
     */
    protected $adapter;

    protected function setUp()
    {
        $client = new \MongoClient();
        $dbName = 'mongo_adapter_test';

        $client->selectDB($dbName)->drop();

        $options = [
            'mongo_client' => $client,
            'db_name'      => $dbName
        ];

        $this->adapter = new MongoDbEventStoreAdapter($options);
    }

    /**
     * @test
     */
    public function it_creates_a_stream()
    {
        $testStream = $this->getTestStream();

        $this->adapter->beginTransaction();

        $this->adapter->create($testStream);

        $this->adapter->commit();

        $streamEvents = $this->adapter->loadEventsByMetadataFrom(new StreamName('Prooph\Model\User'), array('tag' => 'person'));

        $this->assertEquals(1, count($streamEvents));

        $this->assertEquals($testStream->streamEvents()[0]->uuid()->toString(), $streamEvents[0]->uuid()->toString());
        $this->assertEquals($testStream->streamEvents()[0]->createdAt()->format('Y-m-d\TH:i:s.uO'), $streamEvents[0]->createdAt()->format('Y-m-d\TH:i:s.uO'));
        $this->assertEquals('Prooph\EventStoreTest\Mock\UserCreated', $streamEvents[0]->messageName());
        $this->assertEquals('contact@prooph.de', $streamEvents[0]->payload()['email']);
        $this->assertEquals(1, $streamEvents[0]->version());
    }

    /**
     * @return Stream
     */
    private function getTestStream()
    {
        $streamEvent = UserCreated::with(
            array('name' => 'Max Mustermann', 'email' => 'contact@prooph.de'),
            1
        );

        DomainEventMetadataWriter::setMetadataKey($streamEvent, 'tag', 'person');

        return new Stream(new StreamName('Prooph\Model\User'), array($streamEvent));
    }
}
