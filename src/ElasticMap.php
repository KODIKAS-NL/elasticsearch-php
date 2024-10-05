<?php

namespace LegalThings;

class ElasticMap
{
    /**
     * Get basic full text search mapping
     * Use this when you want to be able to full text search for every property based on words seperated by whitespaces
     * 
     * @return array
     */
    public static function getFullTextSearchMapping()
    {
        return [
            'settings' => [
                'analysis' => [
                    'filter' => [
                        'ngram_filter' => [
                            'type' => 'ngram',
                            'min_gram' => '2',
                            'max_gram' => '20',
                            'token_chars' => ['letter', 'digit', 'punctuation', 'symbol']
                        ]
                    ],
                    'analyzer' => [
                        'default' => [
                            'type' => 'custom',
                            'tokenizer' => 'whitespace',
                            'filter' => ['lowercase', 'asciifolding', 'ngram_filter']
                        ],
                        'default_search' => [
                            'type' => 'custom',
                            'tokenizer' => 'whitespace',
                            'filter' => ['lowercase', 'asciifolding']
                        ]
                    ]
                ],
                'index' => [
                    'max_ngram_diff' => 20
                ]
            ]
        ];
    }
}
