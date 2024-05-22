<?php
/**
 * OpenAI speech example
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$apiKey = getenv('OPENAI_API_KEY');
$client = OpenAI::client($apiKey);

$response = $client->audio()->speech([
    'model' => 'tts-1',
    'input' => 'Good morning PHP developers',
    'voice' => 'onyx',
    'speed' => 0.95
]);

file_put_contents('phpday.mp3', $response);
