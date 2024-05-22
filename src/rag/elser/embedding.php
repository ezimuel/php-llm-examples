<?php
/**
 * Embedding with ELSER by Elasticsearch
 */
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;
use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;

# Read PDF file
printf ("- Reading the PDF files\n");
$reader = new FileDataReader(dirname(dirname(__DIR__)) . '/data/AI_act.pdf');
$documents = $reader->getDocuments();
printf("Number of PDF files: %d\n", count($documents));

# Document split
printf("- Document split\n");
$splitDocuments = DocumentSplitter::splitDocuments($documents, 512);
printf("Number of splitted documents (chunk): %d\n", count($splitDocuments));


# Create Elasticsearch index with sparse_vector (ELSER)
$es = (new ClientBuilder())::create()
    ->setHosts([getenv('ELASTIC_URL')])
    ->setApiKey(getenv('ELASTIC_API_KEY'))
    ->build();

$indexName = 'llphant_elser';

$params = [
    'index' => $indexName,
    'body' => [
        'mappings' => [
            'properties' => [
                'embedding' => [
                    'type' => 'sparse_vector'
                ],
                'content' => [
                    'type' => 'text'
                ]
            ]
        ]
    ]
];
$response = $es->indices()->create($params);

$pipelineName = 'elser-pipeline';
# Create an ingest pipeline with inference
$params = [
    'id' => $pipelineName,
    'body' => [
        'processors' => [
            [
                'inference' => [
                    'model_id' => '.elser_model_2_linux-x86_64',
                    'input_output' => [
                        'input_field' => 'content',
                        'output_field' => 'embedding'
                    ]
                ]
            ]
        ]
    ]
];
$response = $es->ingest()->putPipeline($params);

# Index all the splitted documents using bulk
$params = [
    'index' => $indexName,
    'body' => []
];
foreach ($splitDocuments as $document) {
    $params['body'][] = [
        'index' => [
            'pipeline' => $pipelineName,
        ],
    ];
    $params['body'][] = [
        'content' => $document->content,
        'formattedContent' => $document->formattedContent ?? '',
        'sourceType' => $document->sourceType,
        'sourceName' => $document->sourceName,
        'hash' => $document->hash,
        'chunkNumber' => $document->chunkNumber,
    ];
}
$response = $es->bulk($params);

if ($response['errors']) {
    $i = 0;
    foreach ($response['items'] as $item) {
        if (isset($item['index']['error'])) {
            printf("ERROR #%d\n", $i);
            printf("Status code: %s\n", $item['index']['status']);
            printf("Type: %s\n", $item['index']['error']['type']);
            printf("Reason: %s\n", $item['index']['error']['reason']);
            $i++;
        }
    }
}

printf("Added %d documents in Elasticsearch with sparse_vector embedding included\n", count($splitDocuments));