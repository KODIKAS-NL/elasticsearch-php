<?php

namespace LegalThings;

use Codeception\TestCase\Test;

/**
 * Simple response wrapper used by tests to mimic ES8 response helpers.
 */
class FakeEsResponse
{
    /**
     * @var array
     */
    private $array;

    /**
     * @var bool|null
     */
    private $bool;

    /**
     * @param array     $array
     * @param bool|null $bool
     */
    public function __construct($array = [], $bool = null)
    {
        $this->array = $array;
        $this->bool = $bool;
    }

    /**
     * @return array
     */
    public function asArray()
    {
        return $this->array;
    }

    /**
     * @return bool
     */
    public function asBool()
    {
        return isset($this->bool) ? $this->bool : !empty($this->array);
    }
}

/**
 * Fake indices namespace for wrapper tests.
 */
class FakeIndicesClient
{
    /**
     * @var array
     */
    public $calls = [];

    /**
     * @var array
     */
    private $responses;

    /**
     * @param array $responses
     */
    public function __construct($responses = [])
    {
        $this->responses = $responses;
    }

    /**
     * @param string $method
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function responseFor($method, $default)
    {
        if (!array_key_exists($method, $this->responses)) {
            return $default;
        }

        return $this->responses[$method];
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function create($params)
    {
        $this->calls['create'][] = $params;

        return $this->responseFor('create', new FakeEsResponse([
            'acknowledged' => true,
            'shards_acknowledged' => true,
        ]));
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function delete($params)
    {
        $this->calls['delete'][] = $params;

        return $this->responseFor('delete', new FakeEsResponse([
            'acknowledged' => true,
        ]));
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function exists($params)
    {
        $this->calls['exists'][] = $params;

        return $this->responseFor('exists', new FakeEsResponse([], true));
    }
}

/**
 * Fake ES client for wrapper tests.
 */
class FakeClient
{
    /**
     * @var array
     */
    public $calls = [];

    /**
     * @var array
     */
    private $responses;

    /**
     * @var FakeIndicesClient
     */
    private $indicesClient;

    /**
     * @param array $responses
     * @param array $indicesResponses
     */
    public function __construct($responses = [], $indicesResponses = [])
    {
        $this->responses = $responses;
        $this->indicesClient = new FakeIndicesClient($indicesResponses);
    }

    /**
     * @return FakeIndicesClient
     */
    public function getIndicesClient()
    {
        return $this->indicesClient;
    }

    /**
     * @param string $method
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function responseFor($method, $default)
    {
        if (!array_key_exists($method, $this->responses)) {
            return $default;
        }

        return $this->responses[$method];
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function index($params)
    {
        $this->calls['index'][] = $params;

        return $this->responseFor('index', new FakeEsResponse([
            'result' => 'created',
            '_id' => '0001',
        ]));
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function update($params)
    {
        $this->calls['update'][] = $params;

        return $this->responseFor('update', new FakeEsResponse([
            'result' => 'updated',
            '_id' => '0001',
        ]));
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function get($params)
    {
        $this->calls['get'][] = $params;

        return $this->responseFor('get', new FakeEsResponse([
            '_source' => ['id' => '0001'],
        ]));
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function delete($params)
    {
        $this->calls['delete'][] = $params;

        return $this->responseFor('delete', new FakeEsResponse([
            'result' => 'deleted',
            '_id' => '0001',
        ]));
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function search($params)
    {
        $this->calls['search'][] = $params;

        return $this->responseFor('search', new FakeEsResponse([
            'hits' => ['hits' => [['_source' => ['id' => '0001']]]],
        ]));
    }

    /**
     * @return FakeIndicesClient
     */
    public function indices()
    {
        return $this->indicesClient;
    }
}

/**
 * Tests for Elasticsearch class
 *
 * @covers \LegalThings\Elasticsearch
 */
class ElasticsearchTest extends Test
{
    /**
     * @return array
     */
    protected function getConfig()
    {
        return [
            'hosts' => ['localhost:9200'],
            'retries' => 2,
        ];
    }

    public function testConstruct()
    {
        $config = $this->getConfig();
        $client = new FakeClient();

        $es = new Elasticsearch($config, $client);

        $this->assertEquals((object)$config, $es->config);
        $this->assertSame($client, $es->client);
    }

    public function testIndex()
    {
        $client = new FakeClient();
        $es = new Elasticsearch($this->getConfig(), $client);

        $result = $es->index('books', '0001', [
            'id' => '0001',
            'name' => 'My book two',
        ]);

        $this->assertEquals('created', $result['result']);
        $this->assertEquals('0001', $result['_id']);

        $params = $client->calls['index'][0];
        $this->assertEquals('books', $params['index']);
        $this->assertEquals('0001', $params['id']);
        $this->assertArrayNotHasKey('type', $params);
    }

    public function testUpdate()
    {
        $client = new FakeClient();
        $es = new Elasticsearch($this->getConfig(), $client);

        $result = $es->update('books', '0001', ['name' => 'My book three']);

        $this->assertEquals('updated', $result['result']);
        $this->assertEquals('0001', $result['_id']);

        $params = $client->calls['update'][0];
        $this->assertEquals('books', $params['index']);
        $this->assertEquals('0001', $params['id']);
        $this->assertEquals(['doc' => ['name' => 'My book three']], $params['body']);
        $this->assertArrayNotHasKey('type', $params);
    }

    public function testGet()
    {
        $client = new FakeClient();
        $es = new Elasticsearch($this->getConfig(), $client);

        $result = $es->get('books', '0001');

        $this->assertEquals('0001', $result['_source']['id']);

        $params = $client->calls['get'][0];
        $this->assertEquals('books', $params['index']);
        $this->assertEquals('0001', $params['id']);
        $this->assertArrayNotHasKey('type', $params);
    }

    public function testDelete()
    {
        $client = new FakeClient();
        $es = new Elasticsearch($this->getConfig(), $client);

        $result = $es->delete('books', '0001');

        $this->assertEquals('deleted', $result['result']);
        $this->assertEquals('0001', $result['_id']);

        $params = $client->calls['delete'][0];
        $this->assertEquals('books', $params['index']);
        $this->assertEquals('0001', $params['id']);
        $this->assertArrayNotHasKey('type', $params);
    }

    public function testSearch()
    {
        $client = new FakeClient();
        $es = new Elasticsearch($this->getConfig(), $client);

        $result = $es->search(
            'books',
            'My book',
            ['name'],
            [
                'id' => '0001',
                'updated(max)' => '2017-01-01T00:00:00',
                'year(min)' => 1973,
                'published' => false,
            ],
            ['^year'],
            15,
            0
        );

        $this->assertCount(1, $result['hits']['hits']);
        $this->assertEquals('0001', $result['hits']['hits'][0]['_source']['id']);

        $params = $client->calls['search'][0];
        $this->assertEquals('books', $params['index']);
        $this->assertEquals(['year:desc'], $params['sort']);
        $this->assertArrayNotHasKey('type', $params);
        $this->assertEquals('My book', $params['body']['query']['bool']['must'][0]['query_string']['query']);
        $this->assertEquals(['name'], $params['body']['query']['bool']['must'][0]['query_string']['fields']);
        $this->assertEquals(15, $params['body']['size']);
        $this->assertEquals(0, $params['body']['from']);
    }

    public function testSearchWithoutQueryUsesMatchAll()
    {
        $client = new FakeClient();
        $es = new Elasticsearch($this->getConfig(), $client);

        $es->search('books');

        $params = $client->calls['search'][0];
        $this->assertEquals(['match_all' => (object)[]], $params['body']['query']);
        $this->assertArrayNotHasKey('sort', $params);
    }

    public function testIndexCreate()
    {
        $client = new FakeClient();
        $es = new Elasticsearch($this->getConfig(), $client);

        $result = $es->indexCreate('books', [
            'settings' => [
                'number_of_shards' => 1,
            ],
        ]);

        $this->assertTrue($result['acknowledged']);
        $this->assertTrue($result['shards_acknowledged']);

        $params = $client->getIndicesClient()->calls['create'][0];
        $this->assertEquals('books', $params['index']);
    }

    public function testIndexExists()
    {
        $client = new FakeClient([], [
            'exists' => new FakeEsResponse([], true),
        ]);
        $es = new Elasticsearch($this->getConfig(), $client);

        $result = $es->indexExists('books');

        $this->assertTrue($result);

        $params = $client->getIndicesClient()->calls['exists'][0];
        $this->assertEquals('books', $params['index']);
    }

    public function testIndexDelete()
    {
        $client = new FakeClient([], [
            'delete' => new FakeEsResponse(['acknowledged' => true]),
        ]);
        $es = new Elasticsearch($this->getConfig(), $client);

        $result = $es->indexDelete('books');

        $this->assertTrue($result['acknowledged']);

        $params = $client->getIndicesClient()->calls['delete'][0];
        $this->assertEquals('books', $params['index']);
    }
}
