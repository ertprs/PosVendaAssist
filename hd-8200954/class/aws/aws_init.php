<?php
// Define e verifica a presença do SDK do Amazon Web Services.
// Também define o ambiente de trabalho, desenvolvimento (DEV_ENV==TRUE) ou produção (DEV_ENV==FALSE)
if (!defined('isCLI'))
    define('isCLI', (PHP_SAPI == 'cli'));
$hostname = (isCLI) ? trim(`hostname`) : $_SERVER['SERVER_NAME'];

// DEV_ENV true se está em ambiente de desenvolvimento
if (!defined('DEV_ENV'))
    define('DEV_ENV', ($_serverEnvironment === 'development'));

define('AWS_SDK', '/aws-amazon/sdk/sdk.class.php');
$AWS_sdk_OK = file_exists(AWS_SDK);
