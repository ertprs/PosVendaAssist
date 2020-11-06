<?php
/**
 *
 *  monitoracron_ajax.php
 *
 *  Programa para monitoramento de rotinas cron - agendamento de execução
 *
 * @author  Francisco Ambrozio
 * @version 2012.03
 *
 */

if (empty($_POST['p']) and empty($_POST['plid'])) {
	exit;
}

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'monitoracron.inc.php';

$monitoraCron = new MonitoraCron();
$programa = trim($_POST['p']);
$perl = trim($_POST['plid']);

if (!empty($programa)) {
	$monitoraCron->ajaxAgenda($programa);
}
elseif (!empty($perl)) {
	$monitoraCron->removeAgenda($perl);
}

