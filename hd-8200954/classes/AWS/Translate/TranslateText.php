<?php

require_once __DIR__ . '/../SDK/aws-autoloader.php';

use Aws\Translate\TranslateClient; 
use Aws\Exception\AwsException;

/**
 * @author William Castro <william.castro@telecontrol.com.br>
 * @param string $currentLanguage Lingua mãe do texto
 * @param string $targetLanguage  Lingua qual texto será traduzido
 * @param string $text Texto a ser traduzido
 */

class TranslateText 
{
    public static function traduzir($currentLanguage, $targetLanguage, $text) {

        putenv("AWS_ACCESS_KEY_ID=AKIAYAX2UAI5FO5NE5XC");
        putenv("AWS_SECRET_ACCESS_KEY=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");

        $client = new Aws\Translate\TranslateClient([
            'profile' => 'default',
            'region' => 'us-east-1',
            'version' => '2017-07-01'
        ]);

        $textToTranslate = utf8_encode($text);

        try {
            $result = $client->translateText([
                'SourceLanguageCode' => $currentLanguage,
                'TargetLanguageCode' => $targetLanguage, 
                'Text' => $textToTranslate 
            ]);
            
            return $result;

        } catch (AwsException $e) {
            // output error message if fails
            echo $e->getMessage();
            echo "\n";
        }
    }
}