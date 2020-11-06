<?php

require_once __DIR__ . '/../SDK/aws-autoloader.php';

use Aws\Sqs\SqsClient; 
use Aws\Exception\AwsException;

putenv("AWS_ACCESS_KEY_ID=AKIAYAX2UAI5ILBJM2G3");
putenv("AWS_SECRET_ACCESS_KEY=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");

$client = new SqsClient([
    'region' => 'us-east-1',
    'version' => '2012-11-05',
]);

$queue_url = "https://sqs.us-east-1.amazonaws.com/551355155002/queue_email"; 

$codigo =  rand(10,5789);

echo "codigo $codigo <br> ";

$params = [
    'DelaySeconds' => 10,
    'MessageAttributes' => [
        "Fabrica" => [
            'DataType' => "Number",
            'StringValue' => "1"
        ],
        "Acao"=> [
            'DataType' => "String",
            'StringValue' => "Cancelamento"
        ],
        "CodigoPosto" => [
            'DataType' => "String",
            'StringValue' => "01122"
        ]
    ],
    'MessageBody' => "mensagem de codigo $codigo ",
    'QueueUrl' => $queue_url
];


try {
    $result = $client->sendMessage($params);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
} catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
}
 