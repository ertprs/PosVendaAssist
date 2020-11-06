<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include '../anexaNF_inc.php';

header('Content-type: application/json');

if (empty($_GET['os'])) {
    die('{}');
}

$anexos = temNF((int) $_GET['os'], count);

echo '{"anexos": ' . (int) $anexos . '}';

