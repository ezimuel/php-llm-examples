<?php
/**
 * RAG with OpenAI and ELSER by Elasticsearch
 */
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;
use LLPhant\OpenAIConfig;
use LLPhant\Chat\OpenAIChat;
use LLPhant\Embeddings\Document;

# The OpenAI API key should be put in OPENAI_API_KEY env

$config = new OpenAIConfig();
$config->model = 'gpt-3.5-turbo';
$chat = new OpenAIChat($config);

# Elasticsearch
$es = (new ClientBuilder())::create()
    ->setHosts([getenv('ELASTIC_URL')])
    ->setApiKey(getenv('ELASTIC_API_KEY'))
    ->build();

$indexName = 'llphant_elser';

$promptSystemMessage = "Use the following pieces of context to answer the question of the user. If you don't know the answer, just say that you don't know, don't try to make up an answer.\n\n{context}.";
$question = 'What is the AI act?';

# Semantic search using text_expansion
# @see https://www.elastic.co/guide/en/elasticsearch/reference/current/semantic-search-elser.html#text-expansion-query
$params = [
    'index' => $indexName,
    'body' => [
        'query' => [
            'text_expansion' => [
                'embedding' => [
                    'model_id' => '.elser_model_2_linux-x86_64',
                    'model_text' => $question
                ]
            ]
        ]
    ]
];
$result = $es->search($params);

# Create the documents to be used in the context (take the first $k results)
$k = 4;
$i = 1;
$retrievedDocs = [];
foreach ($result['hits']['hits'] as $hit) {
    if ($i>$k) {
        break;
    }
    $document = new Document();
    $document->embedding = $hit['_source']['embedding'];
    $document->content = $hit['_source']['content'];
    $document->formattedContent = $hit['_source']['formattedContent'];
    $document->sourceType = $hit['_source']['sourceType'];
    $document->sourceName = $hit['_source']['sourceName'];
    $document->hash = $hit['_source']['hash'];
    $document->chunkNumber = $hit['_source']['chunkNumber'];
    $retrievedDocs[] = $document;
    $i++;
}

// Create the systema message with context
$context = '';
foreach ($retrievedDocs as $document) {
    $context .= $document->content.' ';
}

$chat->setSystemMessage(str_replace('{context}', $context, $promptSystemMessage));
$answer = $chat->generateText($question);

printf("-- Answer:\n%s\n", $answer);
