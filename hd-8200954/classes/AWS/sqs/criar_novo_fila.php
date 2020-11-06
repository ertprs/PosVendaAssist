<?php

require_once __DIR__ . '/../SDK/aws-autoloader.php';

use Aws\Sqs\SqsClient; 
use Aws\Exception\AwsException;

$queueName = "queue_email";

putenv("AWS_ACCESS_KEY_ID=AKIAYAX2UAI5ILBJM2G3");
putenv("AWS_SECRET_ACCESS_KEY=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");

$client = new SqsClient([
    'region' => 'us-east-1',
    'version' => '2012-11-05',
]);

try {
    $result = $client->createQueue(array(
        'QueueName' => $queueName,
        'Attributes' => array(
            'DelaySeconds' => 5,
            'MaximumMessageSize' => 4096, // 4 KB
        ),
    ));
    var_dump($result);
} catch (AwsException $e) {

        // output error message if fails
         error_log($e->getMessage());
    
         }
