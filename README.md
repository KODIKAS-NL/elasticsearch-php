Legal Things - Elasticsearch PHP
==================

This library aims to provide you with a simplified interface to use Elasticsearch.
Internally it uses the original [elastic/elasticsearch-php](https://github.com/elastic/elasticsearch-php) library.

The client of the original library is exposed and can be used if needed.

## Requirements

- [PHP](http://www.php.net) >= 7.4.0
- [Elasticsearch](https://www.elastic.co/elasticsearch) 8.x


_Required PHP extensions are marked by composer_


## Installation

The library can be installed using composer.

    composer require legalthings/elasticsearch-php


## Upgrade Guide (ES6 -> ES8)

If your consumer still uses the old typed-document method signatures, use the mapping below.

### Method Signature Changes

| Old usage (ES6 style) | New usage (ES8 style) |
| --- | --- |
| `$es->index($index, $type, $id, $data)` | `$es->index($index, $id, $data)` |
| `$es->update($index, $type, $id, $data)` | `$es->update($index, $id, $data)` |
| `$es->get($index, $type, $id)` | `$es->get($index, $id)` |
| `$es->delete($index, $type, $id)` | `$es->delete($index, $id)` |
| `$es->search($index, $type, $text, $fields, $filter, $sort, $limit, $offset)` | `$es->search($index, $text, $fields, $filter, $sort, $limit, $offset)` |

### Elasticsearch 8 Behavior Changes

- Document types are removed in Elasticsearch 8. Do not send or depend on `_type`.
- Search responses now return `hits.total` as an object (`{ "value": ..., "relation": ... }`) instead of an integer.
- When calling `search()` without text and filter, this wrapper sends a `match_all` query.
- Wrapper methods continue to return arrays/bools for compatibility. If you use `$es->client` directly, the ES8 client returns response objects with helpers like `asArray()` and `asBool()`.

### Minimal Consumer Migration Checklist

1. Remove `$type` from all wrapper method calls.
2. Remove assertions or parsing logic that expects `_type` in ES responses.
3. Update `hits.total` handling to read `hits.total.value` when needed.
4. If your consumer instantiates the ES8 client directly, make sure a PSR-17 implementation is installed (this package uses `nyholm/psr7`).


## Client
To create a client you need to pass [configuration options](https://github.com/legalthings/elasticsearch-php#configuration).
If no options are given, `localhost:9200` is automatically used as the host.

```php
use LegalThings/Elasticsearch;

$config = ['hosts' => 'elasticsearch.example.com:9200'];

$es = new Elasticsearch($config);
```

You can use the original [elastic/elasticsearch-php](https://github.com/elastic/elasticsearch-php) client directly if you need its functionality.

```php
$info = $es->client->info();
```


## Configuration
Configuration is passed to Elasticsearch's configuration builder, which means you can provide any configuration options that it accepts.
See [this](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/connecting.html) link for more information.

Add `["quiet" => true]` to the config if you want to add custom keys to the configuration.
If quiet isn't provided, Elasticsearch will throw an exception if it encounters keys unrelated to the Elasticsearch client.

```php
[
    'hosts' => ['localhost:9200'],
    'retries' => 2
]
```


## Search
The `search()` method is used to perform common, basic search operations in Elasticsearch.

The method automatically [transforms filters](https://github.com/legalthings/elasticsearch-php#filters) and text searches to the correct Elasticsearch equivalent,
so you don't have to do that manually.

```php
use LegalThings/Elasticsearch;

$es = new Elasticsearch($config);

$index = 'books';
$text = 'My book';
$fields = ['name'];
$filter = [
    'id' => '0001',
    'updated(max)' => '2017-01-01T00:00:00',
    'year(min)' => 1973,
    'published' => false
];
$sort = ['^year'];
$limit = 15;
$offset = 0;

$result = $es->search($index, $text, $fields, $filter, $sort, $limit, $offset);
```

```json
{
  "took": 1,
  "timed_out": false,
  "_shards": {
    "total": 5,
    "successful": 5,
    "failed": 0
  },
  "hits": {
    "total": {
      "value": 1,
      "relation": "eq"
    },
    "max_score": null,
    "hits": [{
      "_index": "books",
      "_id": "0001",
      "_score": null,
      "_source": {
        "id": "0001",
        "updated": "2017-01-01T00:00:00",
        "year": 1980,
        "published": false,
        "name": "My book two"
      },
      "sort": [1980]
    }]
  }
}
```


## Index
The `index()` method is used to perform common, basic index operations in Elasticsearch

```php
use LegalThings/Elasticsearch;

$es = new Elasticsearch($config);
        
$index = 'books';
$id = '0001';
$data = [
    'id' => '0001',
    'updated' => '2017-01-01T00:00:00',
    'year' => 1980,
    'published' => false,
    'name' => 'My book two'
];

  $result = $es->index($index, $id, $data);
```

```json
{
  "_index": "books",
  "_id": "0001",
  "_version": 1,
  "result": "created",
  "_shards": {
    "total": 2,
    "successful": 1,
    "failed": 0
  },
  "created": true
}
```


## Update
The `update()` method is used to perform common, basic update operations in Elasticsearch, which partially updates a document

```php
use LegalThings/Elasticsearch;

$es = new Elasticsearch($config);
        
$index = 'books';
$id = '0001';
$data = [
    'name' => 'My book three'
];

$result = $es->update($index, $id, $data);
```

```json
{
  "_index": "books",
  "_id": "0001",
  "_version": 2,
  "result": "updated",
  "_shards": {
    "total": 0,
    "successful": 0,
    "failed": 0
  }
}
```


## Get
The `get()` method is used to perform common, basic get operations in Elasticsearch.

```php
use LegalThings/Elasticsearch;

$es = new Elasticsearch($config);
        
$index = 'books';
$id = '0001';

$result = $es->get($index, $id);
```

```json
{
  "_index": "books",
  "_id": "0001",
  "_version": 1,
  "found": true,
  "_source": {
    "id": "0001",
    "updated": "2017-01-01T00:00:00",
    "year": 1980,
    "published": false,
    "name": "My book two"
  }
}
```


## Delete
The `delete()` method is used to perform common, basic delete operations in Elasticsearch.

```php
use LegalThings/Elasticsearch;

$es = new Elasticsearch($config);
        
$index = 'books';
$id = '0001';

$result = $es->delete($index, $id);
```

```json
{
  "found": true,
  "_index": "books",
  "_id": "0001",
  "_version": 1,
  "result": "deleted",
  "_shards": {
    "total": 2,
    "successful": 1,
    "failed": 0
  }
}
```


## Filters

This library makes it easy to filter for data in Elasicsearch, because you don't have to transform the filter in a specific structure.
See the [tests](https://github.com/legalthings/elasticsearch-php/blob/414a05f8c9127b69773f853953351a7df47a335c/tests/unit/ElasticFilterTest.php#L24-L62) for more examples.
See [jasny filter](https://github.com/jasny/db#filter) for more information about the syntax.

```php
use LegalThings/ElasticFilter;

$filter = [
    'id' => '0001',
    'authors' => ['John', 'Jane'],
    'deleted' => null,
    'start_date(min)' => '2017-01-01T00:00:00',
    'end_date(max)' => '2018-01-01T00:00:00',
    'age(min)' => 25,
    'tags(not)' => ['foo', 'bar'],
    'published(not)' => null,
    'colors(any)' => ['blue', 'green'],
    'colors(none)' => ['red'],
    'category(all)' => ['A', 'B', 'C']
];

$ef = new ElasticFilter($filter);
$query = $ef->transform();

$this->assertEquals([
    'bool' => [
        'must' => [
            [ 'term' => [ 'id' => '0001' ] ],
            [ 'terms' => [ 'authors' => ['John', 'Jane'] ] ],
            [ 'missing' => [ 'field' => 'deleted' ] ],
            [ 'range' => [ 'start_date' => [ 'gte' => '2017-01-01T00:00:00' ] ] ],
            [ 'range' => [ 'end_date' => [ 'lte' => '2018-01-01T00:00:00' ] ] ],
            [ 'range' => [ 'age' => [ 'gte' => 25 ] ] ],
            [ 'terms' => [ 'colors' => [ 'blue', 'green' ] ] ],
            [ 'term' => [ 'category' => [ 'A', 'B', 'C' ] ] ]
        ],
        'must_not' => [
            [ 'terms' => [ 'tags' => [ 'foo', 'bar' ] ] ],
            [ 'missing' => [ 'field' => 'published' ] ],
            [ 'term' => [ 'colors' => [ 'red' ] ] ]
        ]
    ]
], $query);
```

Alternatively the filter can be composed using a [fluent interface](https://en.wikipedia.org/wiki/Fluent_interface).
This will output the same query as the example above.

```php
use LegalThings/ElasticFilter;

$ef = new ElasticFilter();

$query = $ef->addDefaultFilter('id', '0001')
    ->addDefaultFilter('authors', ['John', 'Jane'])
    ->addDefaultFilter('deleted', null)
    ->addMinFilter('start_date', '2017-01-01T00:00:00')
    ->addMaxFilter('end_date', '2018-01-01T00:00:00')
    ->addMinFilter('age', 25)
    ->addNotFilter('tags', ['foo', 'bar'])
    ->addNotFilter('published', null)
    ->addAnyFilter('colors', ['blue', 'green'])
    ->addNoneFilter('colors', ['red'])
    ->addAllFilter('category', ['A', 'B', 'C'])
    ->transform();

$this->assertEquals([...], $query); // same output as first example
```


## Mapping

The library comes with predefined [Elasticsearch mappings](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html#mapping) that you can use.
See [this](https://github.com/legalthings/elasticsearch-php/blob/master/src/ElasticMap.php) file for more maps.

```php
use LegalThings/ElasticMap;

$es = new Elasticsearch($config);

$result = $es->client->indices()->create([
    'index' => 'my_index',
    'body' => ElasticMap::getFullTextSearchMapping()
]);
```
