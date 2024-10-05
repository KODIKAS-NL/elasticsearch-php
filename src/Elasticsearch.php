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
     * @return Logger $logger
     */
    protected function create($config)
    {
        $quiet = isset($config->quiet) ? $config->quiet : false;
        
        $client = ClientBuilder::fromConfig((array)$config, $quiet);
        
        return $client;
    }
    
    
    /**
     * Index data in Elasticsearch
     * 
     * @param string       $index   index name
     * @param string       $type    index type
     * @param string       $id      identifier for the data
     * @param array|object $data    data to index
     * 
     * @return array
     */
    public function index($index, $type, $id, $data)
    {
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
            'body' => $data
        ];

        return $this->client->index($params);
    }
    
    /**
     * Update data in Elasticsearch
     * 
     * @param string       $index   index name
     * @param string       $type    index type
     * @param string       $id      identifier for the data
     * @param array|object $data    data to index
     * 
     * @return array
     */
    public function update($index, $type, $id, $data)
    {
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
            'body' => ['doc' => $data]
        ];
        
        return $this->client->update($params);
    }
    
    /**
     * Get data in Elasticsearch
     * 
     * @param string       $index   index name
     * @param string       $type    index type
     * @param string       $id      identifier for the data
     * 
     * @return array
     */
    public function get($index, $type, $id)
    {
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id
        ];

        return $this->client->get($params);
    }
    
    /**
     * Delete data in Elasticsearch
     * 
     * @param string       $index   index name
     * @param string       $type    index type
     * @param string       $id      identifier for the data
     * 
     * @return array
     */
    public function delete($index, $type, $id)
    {
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id
        ];

        return $this->client->delete($params);
    }
    
    /**
     * Search through Elasticsearch
     * This function will take care of transforming filters and queries to data that Elasticsearch expects
     * 
     * @param string       $index   index name
     * @param string       $type    index type
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
    public function search($index, $type, $text = null, $fields = [], $filter = [], $sort = [], $limit = null, $offset = null)
    {
        if (isset($text)) {
            $text = [
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
        
        $body = [
            'query' => [
                'bool' => [
                    'must' => $text,
                    'filter' => $filter
                ]
            ]
        ];
        
        if (isset($offset)) {
            $body['from'] = $offset;
        }
        
        if (isset($limit)) {
            $body['size'] = $limit;
        }
        
        $params = [
            'index' => $index,
            'type' => $type,
            'sort' => $sort,
            'body' => $body
        ];

        return $this->client->search($params);
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

        return $this->client->indices()->create($params);
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

        return $this->client->indices()->delete($params);
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

        return $this->client->indices()->exists($params);
    }
}
