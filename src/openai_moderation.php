<?php
/**
 * OpenAI moderation example
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$apiKey = getenv('OPENAI_API_KEY');
$client = OpenAI::client($apiKey);

$response = $client->moderations()->create([
    'model' => 'text-moderation-latest',
    'input' => 'I want to k*** them.',
]);

printf("ID: %s\n",$response->id); 
printf("Model: %s\n", $response->model); 

foreach ($response->results as $result) {
    foreach ($result->categories as $category) {
        printf("Category: %s\n", $category->category->value); // 'violence'
        printf("Violated: %s\n", $category->violated ? 'yes' : 'no'); // true
        printf("Score: %.4f\n", $category->score); // 0.97431367635727
    }
}

#var_dump($response->toArray());