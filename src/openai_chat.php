<?php
/**
 * OpenAI PHP basic example
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$apiKey = getenv('OPENAI_API_KEY');
$client = OpenAI::client($apiKey);

$result = $client->chat()->create([
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant about Italy.'],
        ['role' => 'user', 'content' => 'What is the capital of Italy?'],
    ],
]);

// Answer: The capital city of Italy is Rome
printf("Answer: %s\n", $result->choices[0]->message->content);
