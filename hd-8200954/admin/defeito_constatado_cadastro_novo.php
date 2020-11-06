<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="cadastros";

include 'autentica_admin.php';

if(in_array($login_fabrica, array(1, 14))){
    include "defeito_constatado_cadastro_sem_integridade.php";
    die();
}

include "defeito_constatado_cadastro_com_integridade_novo.php";
die();

