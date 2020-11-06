<?php

include_once __DIR__ . '/aws_init.php';

define('S3CLASS', __DIR__ . '/anexaS3.class.php');
#define('S3TEST',  ($_serverEnvironment == 'development') ? 'testes/': ''); // Quando trabalha em ambiente de desenvolvimento, gravar sempre dentro de bucket:/testes/[filename]
define('S3TEST', ''); // Consultas no ambiente de produção. Uploads agora são sempre no TDocs.

$S3_sdk_OK = $AWS_sdk_OK and file_exists(S3CLASS);

