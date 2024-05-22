<?php
/**
 * RAG architecture with Ollama and Elasticsearch
 */
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;
use LLPhant\Chat\OllamaChat;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Elasticsearch\ElasticsearchVectorStore;
use LLPhant\OllamaConfig;
use LLPhant\Query\SemanticSearch\QuestionAnswering;

# Ollama with Llama3
$config = new OllamaConfig();
$config->model = 'llama3';
$chat = new OllamaChat($config);

# Embedding
$embeddingGenerator = new OllamaEmbeddingGenerator($config);

# Elasticsearch
$es = (new ClientBuilder())::create()
    ->setHosts([getenv('ELASTIC_URL')])
    ->setApiKey(getenv('ELASTIC_API_KEY'))
    ->build();

$elasticVectorStore = new ElasticsearchVectorStore($es, $indexName = 'ollama');

# RAG
$qa = new QuestionAnswering(
    $elasticVectorStore,
    $embeddingGenerator,
    $chat
);

$answer = $qa->answerQuestion('What is the AI act?');
printf("-- Answer:\n%s\n", $answer);

foreach ($qa->getRetrievedDocuments() as $doc) {
    printf("-- Document: %s\n", $doc->sourceName);
    printf("-- Content (%d characters): %s\n", strlen($doc->content), $doc->content);
}
