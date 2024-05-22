<?php
/**
 * Chat example with LLPhant
 */

use LLPhant\Chat\OpenAIChat;
use LLPhant\OpenAIConfig;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = new OpenAIConfig();
$config->model = 'gpt-3.5-turbo';
$chat = new OpenAIChat($config);

$response = $chat->generateText('what is one + one ?');

printf("%s\n", $response);

$response = $chat->generateText('What is the capital city of Italy?');

printf("%s\n", $response);

printf("Total tokens usage: %d\n", $chat->getTotalTokens());