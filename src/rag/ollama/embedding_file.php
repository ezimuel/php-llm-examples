<?php
/**
 * Embedding with Ollama (Llama 3)
 */
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use LLPhant\Chat\OllamaChat;
use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\FileSystem\FileSystemVectorStore;
use LLPhant\OllamaConfig;

# You can run ollama locally and install LLama3 from here: https://ollama.com/library/llama3
$config = new OllamaConfig();
$config->model = 'llama3';
$chat = new OllamaChat($config);

# Read PDF file
printf ("- Reading the PDF files\n");
$pdfPath = __DIR__ . '/../../../data/Uranus_Neptune_moons.pdf';
$reader = new FileDataReader($pdfPath);
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

# File system vector store
printf("- Index all the embeddings using the file system\n");
$vectorStorePath = __DIR__ . '/../../../data/vectordb.json';
$store = new FileSystemVectorStore($vectorStorePath);

$store->addDocuments($embeddedDocuments);

printf("Added %d documents in %s\n", count($embeddedDocuments), realpath($vectorStorePath));