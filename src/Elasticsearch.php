<?php

namespace LegalThings;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Client;

class Elasticsearch
{
    /**
     * @var object
     */
    public $config;
    
    /**
     * @var Client
     */
    public $client;
    
    
    /**
     * Class constructor
     * 
     * @param object|array $config
     * @param Client       $client
     */
    public function __construct($config = [], $client = null)
    {
        $this->config = (object)$config;
        
        $this->client = $client ?: $this->create($this->config);
    }
    
    /**
     * Create a client
     * 
     * @param object $config
     * 
        * @return Client
     */
    protected function create($config)
    {
        $quiet = isset($config->quiet) ? $config->quiet : false;
        
        $client = ClientBuilder::fromConfig((array)$config, $quiet);
        
        return $client;
    }


    /**
     * Normalize an ES client response to array when possible.
     *
     * @param mixed $response
     *
     * @return array
     */
    protected function responseToArray($response)
    {
        if (is_array($response)) {
            return $response;
        }

        if (is_object($response) && method_exists($response, 'asArray')) {
            return $response->asArray();
        }

        return (array)$response;
    }

    /**
     * Normalize an ES client response to bool when possible.
     *
     * @param mixed $response
     *
     * @return bool
     */
    protected function responseToBool($response)
    {
        if (is_bool($response)) {
            return $response;
        }

        if (is_object($response) && method_exists($response, 'asBool')) {
            return $response->asBool();
        }

        return (bool)$response;
    }
    
    
    /**
     * Index data in Elasticsearch
     * 
     * @param string       $index   index name
     * @param string       $id      identifier for the data
     * @param array|object $data    data to index
     * 
     * @return array
     */
    public function index($index, $id, $data)
    {
        $params = [
            'index' => $index,
            'id' => $id,
            'body' => $data
        ];

        return $this->responseToArray($this->client->index($params));
    }
    
    /**
     * Update data in Elasticsearch
     * 
     * @param string       $index   index name
     * @param string       $id      identifier for the data
     * @param array|object $data    data to index
     * 
     * @return array
     */
    public function update($index, $id, $data)
    {
        $params = [
            'index' => $index,
            'id' => $id,
            'body' => ['doc' => $data]
        ];
        
        return $this->responseToArray($this->client->update($params));
    }
    
    /**
     * Get data in Elasticsearch
     * 
     * @param string       $index   index name
     * @param string       $id      identifier for the data
     * 
     * @return array
     */
    public function get($index, $id)
    {
        $params = [
            'index' => $index,
            'id' => $id
        ];

        return $this->responseToArray($this->client->get($params));
    }
    
    /**
     * Delete data in Elasticsearch
     * 
     * @param string       $index   index name
     * @param string       $id      identifier for the data
     * 
     * @return array
     */
    public function delete($index, $id)
    {
        $params = [
            'index' => $index,
            'id' => $id
        ];

        return $this->responseToArray($this->client->delete($params));
    }
    
    /**
     * Search through Elasticsearch
     * This function will take care of transforming filters and queries to data that Elasticsearch expects
     * 
     * @param string       $index   index name
     * @param string       $text    text to search for
     *                              example 'john doe'
     * @param array        $fields  search for text only in given fields
     *                              example ['name']
     * @param array|object $filter  filter the results, example
     *                              example ['year(min)' => 2016, 'type' => 'foo']
     * @param array        $sort    sort the results
     *                              example ['^last_modified'] or ['last_modified:desc'] or ['last_modified']
     * @param int          $limit   limit the results
     * @param int          $offset  return results starting from given offset
     * 
     * @return array
     */
    public function search($index, $text = null, $fields = [], $filter = [], $sort = [], $limit = null, $offset = null)
    {
        $must = null;
        if (isset($text)) {
            $must = [
                'query_string' => [
                    'query' => $text,
                    'fields' => $fields,
                    'default_operator' => 'AND'
                ]
            ];
        }
        
        if (isset($filter)) {
            $filter = (new ElasticFilter($filter))->transform();
        }
        
        if (isset($sort)) {
            $sort = (new ElasticSort($sort))->transform();
        }
        
        if (isset($must) || !empty($filter)) {
            $query = ['bool' => []];

            if (isset($must)) {
                $query['bool']['must'] = [$must];
            }

            if (!empty($filter)) {
                $query['bool']['filter'] = $filter;
            }
        } else {
            $query = ['match_all' => (object)[]];
        }

        $body = ['query' => $query];
        
        if (isset($offset)) {
            $body['from'] = $offset;
        }
        
        if (isset($limit)) {
            $body['size'] = $limit;
        }
        
        $params = [
            'index' => $index,
            'body' => $body
        ];

        if (!empty($sort)) {
            $params['sort'] = $sort;
        }

        return $this->responseToArray($this->client->search($params));
    }
    
    
    /**
     * Create an index in Elasticsearch
     * 
     * @param string       $index   index name
     * @param array|object $data    configuration for the index
     * 
     * @return array
     */
    public function indexCreate($index, $data = [])
    {
        $params = [
            'index' => $index,
            'body' => $data
        ];

        return $this->responseToArray($this->client->indices()->create($params));
    }
    
    /**
     * Delete an index in Elasticsearch
     * 
     * @param string       $index   index name
     * 
     * @return array
     */
    public function indexDelete($index)
    {
        $params = [
            'index' => $index
        ];

        return $this->responseToArray($this->client->indices()->delete($params));
    }
    
    /**
     * Check if an index exists in Elasticsearch
     * 
     * @param string       $index   index name
     * 
     * @return boolean
     */
    public function indexExists($index)
    {
        $params = [
            'index' => $index
        ];

        return $this->responseToBool($this->client->indices()->exists($params));
    }
}
