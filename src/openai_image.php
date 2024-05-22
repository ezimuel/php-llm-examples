<?php
/**
 * OpenAI create image example
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$apiKey = getenv('OPENAI_API_KEY');
$client = OpenAI::client($apiKey);

$response = $client->images()->create([
    'model' => 'dall-e-3',
    'prompt' => 'A busy developer working on a laptop during a conference',
    'n' => 1,
    'size' => '1024x1024',
    'response_format' => 'b64_json',
]);

file_put_contents("image.png", base64_decode($response->data[0]->b64_json));
