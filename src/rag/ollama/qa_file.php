<?php
/**
 * RAG architecture with Ollama and Elasticsearch
 */
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use LLPhant\Chat\OllamaChat;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\FileSystem\FileSystemVectorStore;
use LLPhant\OllamaConfig;
use LLPhant\Query\SemanticSearch\QuestionAnswering;

# Ollama with Llama3
$config = new OllamaConfig();
$config->model = 'llama3';
$chat = new OllamaChat($config);

# Embedding
$embeddingGenerator = new OllamaEmbeddingGenerator($config);

# File system vector store
$vectorStorePath = __DIR__ . '/../../../data/vectordb.json';
$store = new FileSystemVectorStore($vectorStorePath);

# RAG
$qa = new QuestionAnswering(
    $store,
    $embeddingGenerator,
    $chat
);

$answer = $qa->answerQuestion('How many moons has Neptune?');
printf("-- Answer:\n%s\n", $answer);

foreach ($qa->getRetrievedDocuments() as $doc) {
    printf("-- Document: %s\n", $doc->sourceName);
    printf("-- Content (%d characters): %s\n", strlen($doc->content), $doc->content);
}
