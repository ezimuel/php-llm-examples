<?php
/**
 * Embedding with Ollama (Llama 3)
 */
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;
use LLPhant\Chat\OllamaChat;
use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Elasticsearch\ElasticsearchVectorStore;
use LLPhant\OllamaConfig;

# You can run ollama locally and install LLama3 from here: https://ollama.com/library/llama3
$config = new OllamaConfig();
$config->model = 'llama3';
$chat = new OllamaChat($config);

# Read PDF file
printf ("- Reading the PDF files\n");
$reader = new FileDataReader(dirname(dirname(__DIR__)) . '/data/AI_act.pdf');
$documents = $reader->getDocuments();
printf("Number of PDF files: %d\n", count($documents));

# Document split
printf("- Document split\n");
$splitDocuments = DocumentSplitter::splitDocuments($documents, 1000);
printf("Number of splitted documents (chunk): %d\n", count($splitDocuments));

# Embedding
printf("- Embedding\n");
$embeddingGenerator = new OllamaEmbeddingGenerator($config);
$embeddedDocuments = $embeddingGenerator->embedDocuments($splitDocuments);

# Elasticsearch
printf("- Index all the embeddings to Elasticsearch\n");
$es = (new ClientBuilder())::create()
    ->setHosts([getenv('ELASTIC_URL')])
    ->setApiKey(getenv('ELASTIC_API_KEY'))
    ->build();

$elasticVectorStore = new ElasticsearchVectorStore($es, $indexName = 'ollama');
$elasticVectorStore->addDocuments($embeddedDocuments);

printf("Added %d documents in Elasticsearch with embedding included\n", count($embeddedDocuments));