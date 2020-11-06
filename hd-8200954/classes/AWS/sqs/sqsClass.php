<?php


require_once __DIR__ . '/../SDK/aws-autoloader.php';

use Aws\Sqs\SqsClient; 
use Aws\Exception\AwsException;

putenv("AWS_ACCESS_KEY_ID=AKIAYAX2UAI5ILBJM2G3");
putenv("AWS_SECRET_ACCESS_KEY=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");

class sqsClass {

	private $client;
	private $queueName;

	function __construct($region = 'us-east-1', $version = '2012-11-05', $profile = 'default'){

		$this->client = new SqsClient([
		    'region' => $region,
		    'version' => $version,
		]);
	}

	public function createQueue($queueName, $delay = 5, $MaximumMessageSize = 4096){

		$result = $this->client->createQueue(array(
	        'QueueName' => $queueName,
	        'Attributes' => array(
	            'DelaySeconds' => $delay,
	            'MaximumMessageSize' => $MaximumMessageSize, // 4 KB
	        ),
	    ));

	    return $result; 
	}

	public function sendMessage($params, $queueUrl){

		$params['QueueUrl'] = $queueUrl; 

		$result = $this->client->sendMessage($params);		
		return $result;

	}

	public function receiveMessagem($queueUrl, $maxNumberOfMessages = 1, $waitTimeSeconds = 0){
		$result = $this->client->receiveMessage(array(

	        'AttributeNames' => ['SentTimestamp'],
	        'MaxNumberOfMessages' => $maxNumberOfMessages,
	        'MessageAttributeNames' => ['All'],
	        'QueueUrl' => $queueUrl, // REQUIRED
	        'WaitTimeSeconds' => $waitTimeSeconds,
	    ));

	    return $result; 

	}

	public function deleteMessagem($queueUrl){
		// $result = $client->deleteMessage([
        //     'QueueUrl' => $queueUrl, // REQUIRED
        //     'ReceiptHandle' => $result->get('Messages')[0]['ReceiptHandle'] // REQUIRED
        // ]);


	}

}


?>