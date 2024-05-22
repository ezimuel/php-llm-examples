<?php
/**
 * Embedding with OpenAI
 */
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;
use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Elasticsearch\ElasticsearchVectorStore;

# The OpenAI API key should be put in OPENAI_API_KEY env

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
$embeddingGenerator = new OpenAI3SmallEmbeddingGenerator();
$embeddedDocuments = $embeddingGenerator->embedDocuments($splitDocuments);

# Elasticsearch
printf("- Index all the embeddings to Elasticsearch\n");
$es = (new ClientBuilder())::create()
    ->setHosts([getenv('ELASTIC_URL')])
    ->setApiKey(getenv('ELASTIC_API_KEY'))
    ->build();

$elasticVectorStore = new ElasticsearchVectorStore($es);
$elasticVectorStore->addDocuments($embeddedDocuments);

printf("Added %d documents in Elasticsearch with embedding included\n", count($embeddedDocuments));