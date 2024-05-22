<?php
/**
 * RAG architecture with OpenAI and Elasticsearch
 */
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;
use LLPhant\OpenAIConfig;
use LLPhant\Chat\OpenAIChat;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Elasticsearch\ElasticsearchVectorStore;
use LLPhant\Query\SemanticSearch\QuestionAnswering;

# The OpenAI API key should be put in OPENAI_API_KEY env

$config = new OpenAIConfig();
$config->model = 'gpt-3.5-turbo';
$chat = new OpenAIChat($config);

# Embedding
$embeddingGenerator = new OpenAI3SmallEmbeddingGenerator();

# Elasticsearch
$es = (new ClientBuilder())::create()
    ->setHosts([getenv('ELASTIC_URL')])
    ->setApiKey(getenv('ELASTIC_API_KEY'))
    ->build();

$elasticVectorStore = new ElasticsearchVectorStore($es);

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
    printf("-- Chunk %d: %d characters\n", $doc->chunkNumber, strlen($doc->content));
}
