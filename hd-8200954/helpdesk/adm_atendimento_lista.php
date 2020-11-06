<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica <> 10) {
    header ("Location: index.php");
}

$TITULO = "Lista de Chamados";?>
<!DOCTYPE HTML>
<?
include "menu.php";
?>
<link rel="stylesheet" type="text/css" href="css/css/ext-all.css"/>
<link rel="stylesheet" type="text/css" href="css/ext.css"/>
<script type="text/javascript" src="js/ext-jquery-adapter.js"></script>
<script type="text/javascript" src="js/ext-all.js"></script>
<script type="text/javascript" src="js/ext-chamados.js"></script>
<script type="text/javascript" src="js/ext-lang-pt_BR.js"></script>
<script type="text/javascript" src="js/chamado0816.js"></script>
<script type="text/javascript" src="js/export.js"></script>
<script type="text/javascript" src="js/jquery.ui.interaction.min.js"></script>

<body style="padding: 0px 20px 20px 20px;">
<?php
$sql = "SELECT *
		FROM tbl_change_log
		JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_change_log.fabrica
        LEFT JOIN tbl_change_log_admin 
            ON tbl_change_log.change_log = tbl_change_log_admin.change_log 
            AND tbl_change_log_admin.admin = $login_admin
		WHERE tbl_change_log_admin.data IS NULL
		AND tbl_change_log.admin <> $login_admin";

$res = pg_query ($con,$sql);
if(pg_num_rows($res) > 0) {
?>
    <center>
        <a href="change_log_mostra.php" target="_blank" class="link_log">
        Existem CHANGE LOGs para serem lidos. Clique aqui para visualizar.
        </a>
    </center>
<?php } ?>

    <div id="bloco"
        style="z-index:99999;
        top: 40px; right: 280px; 
        position: absolute;">
    </div>

    <form id="history-form" class="x-hidden">
        <input id="x-history-field" type="hidden" />
        <iframe id="x-history-frame">
        </iframe>
    </form>
    <div id="chamados" class="container"> </div>
    <div id="chamado_detalhe"> </div>
    <div id="chamado_analise"> </div>

    <div id="chamados" class="container"> </div>

<?php include "rodape.php" ?>
