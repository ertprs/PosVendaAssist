<?php 


require_once __DIR__ . '/../SDK/aws-autoloader.php';

use Aws\Sqs\SqsClient; 
use Aws\Exception\AwsException;

putenv("AWS_ACCESS_KEY_ID=AKIAYAX2UAI5ILBJM2G3");
putenv("AWS_SECRET_ACCESS_KEY=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");

$queue_url = "https://sqs.us-east-1.amazonaws.com/551355155002/queue_email"; 

$client = new SqsClient([
    'region' => 'us-east-1',
    'version' => '2012-11-05',
]);

try {
    $temMsg = "sim";

    while($temMsg == 'sim'){

        $result = $client->receiveMessage(array(
            'QueueUrl' => $queue_url, // REQUIRED
        ));
        if (!empty($result->get('Messages'))) {
            echo "<pre>";
           print_r($result->get('Messages'));
            echo "</pre>";
            // $result = $client->deleteMessage([
            //     'QueueUrl' => $queue_url, // REQUIRED
            //     'ReceiptHandle' => $result->get('Messages')[0]['ReceiptHandle'] // REQUIRED
            // ]);
        } else {
            echo "No messages in queue. \n";
            $temMsg = "nao";
        }

    }
} catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
}