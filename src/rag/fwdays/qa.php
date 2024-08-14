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
$config->model = 'llama3.1';
$chat = new OllamaChat($config);

# Embedding
$embeddingGenerator = new OllamaEmbeddingGenerator($config);

$env = parse_ini_file(__DIR__ . '/../../../bin/.env');
# Elasticsearch
$es = (new ClientBuilder())::create()
    ->setHosts(['localhost:9200'])
    ->setBasicAuthentication('elastic', $env['ES_LOCAL_PASSWORD'])
    ->build();

$elasticVectorStore = new ElasticsearchVectorStore($es, $indexName = 'ollama');

# RAG
$qa = new QuestionAnswering(
    $elasticVectorStore,
    $embeddingGenerator,
    $chat
);

$answer = $qa->answerQuestion('How many moons has Neptune?');
printf("-- Answer:\n%s\n", $answer);

foreach ($qa->getRetrievedDocuments() as $doc) {
    printf("-- Document: %s\n", $doc->sourceName);
    printf("-- Content (%d characters): %s\n", strlen($doc->content), substr($doc->content, 0, 100));
}
