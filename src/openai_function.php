<?php
/**
 * OpenAI moderation example
 */

 require dirname(__DIR__) . '/vendor/autoload.php';

 $apiKey = getenv('OPENAI_API_KEY');
 $client = OpenAI::client($apiKey);

 /**
  * Returns the current temperature in a location as sentence
  */
function get_current_weather(string $location, string $unit = 'celsius'): string
{
    $temperature = 20.5; // # this should be a result of an HTTP API call
    return sprintf(
        "The temperature in %s is %.2f %s", 
        $location, 
        $temperature, 
        $unit === 'celsius' ? '°C' : '°F'
    );
}

$question = 'What\'s the weather like in Turin?';

$response = $client->chat()->create([
    'model' => 'gpt-3.5-turbo-0613',
    'messages' => [
        ['role' => 'user', 'content' => $question],
    ],
    'tools' => [
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_current_weather',
                'description' => 'Get the current weather in a given location',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => [
                            'type' => 'string',
                            'description' => 'The city and state, e.g. San Francisco, CA',
                        ],
                        'unit' => [
                            'type' => 'string',
                            'enum' => ['celsius', 'fahrenheit']
                        ],
                    ],
                    'required' => ['location'],
                ],
            ],
        ]
    ]
]);

// If toolCalls is not empty I need to execute the function
// and give back to GPT to give a final answer
foreach ($response->choices as $choice) {
    foreach ($choice->message->toolCalls as $call) {
        if ($call->type === 'function') {
            $arguments = json_decode($call->function->arguments, true);
            printf("Calling function  %s(%s)\n", $call->function->name, implode('=', $arguments));
            $result = call_user_func($call->function->name, ...$arguments);
            if (!empty($result)) {
                $response = $client->chat()->create([
                    'model' => 'gpt-3.5-turbo-0613',
                    'messages' => [
                        [
                            'role' => 'system', 
                            'content' => sprintf(
                                "Knowing that %s, answer to the user question",
                                $result
                            )
                        ],
                        ['role' => 'user', 'content' => $question],
                    ]
                ]);
            }
        }
    }
}

printf("Answer: %s\n",  $response->choices[0]->message->content);
