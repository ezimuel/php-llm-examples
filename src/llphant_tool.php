<?php
/**
 * Function calling example with LLPhant
 */

use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\Parameter;
use LLPhant\Chat\OpenAIChat;
use LLPhant\OpenAIConfig;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = new OpenAIConfig();
$config->model = 'gpt-3.5-turbo';
$chat = new OpenAIChat($config);

class WeatherStation
{
    public function get_temperature(string $city): float
    {
        return 20.5;
    }
}

$city = new Parameter('city', 'string', 'the name of the city');

$tool = new FunctionInfo(
    'get_temperature',
    new WeatherStation(),
    'Get the current temperature for a city',
    [$city],
    $requiredParameters = [$city]
);
$chat->addTool($tool);
$chat->setSystemMessage('You are a weather bot. Use the provided functions to answer questions.');

$answer = $chat->generateText('What is the temperature now in Turin?');

printf("Answer: %s\n", $answer);

var_dump($chat->getLastResponse());
