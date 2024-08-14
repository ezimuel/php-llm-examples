<?php
/**
 * Chat example with LLPhant
 */

use LLPhant\Chat\OllamaChat;
use LLPhant\OllamaConfig;

require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

# Ollama with Llama3
$config = new OllamaConfig();
$config->model = 'llama3.1';
$chat = new OllamaChat($config);

while (true) {
    $question = readline('(Llama 3.1) ask me anything: ');
    $response = $chat->generateText($question);

    printf("%s\n", $response);
}
