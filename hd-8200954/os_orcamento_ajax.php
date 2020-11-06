<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$os_orcamento = $_GET['os_orcamento'];
$linha        = $_GET['linha'];

$orcamento_envio = $_GET['orcamento_envio'];
if (strlen ($orcamento_envio) > 0) {
	$sql = "SELECT os_orcamento FROM tbl_os_orcamento WHERE os_orcamento = $os_orcamento";
	$res = pg_exec ($con,$sql);
	if(pg_numrows ($res) == 0) {
		echo "<erro>OS Orcamento $os_orcamento nao encontrada</erro>";
		exit;
	}
	$sql = "UPDATE tbl_os_orcamento SET orcamento_envio = '$orcamento_envio' WHERE os_orcamento = $os_orcamento";
	$res = @pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) == 0) {
		echo "<ok>Tudo OK</ok>";
		exit;
	}else{
		echo "<erro>" . pg_errormessage ($con) . "|" . $linha . "|orcamento_envioT" . "</erro>";
		exit;
	}
}

$orcamento_aprovacao = $_GET['orcamento_aprovacao'];
if (strlen ($orcamento_aprovacao) > 0) {
	$sql = "SELECT os_orcamento FROM tbl_os_orcamento WHERE os_orcamento = $os_orcamento";
	$res = pg_exec ($con,$sql);
	if(pg_numrows ($res) == 0) {
		echo "<erro>OS Orcamento $os_orcamento nao encontrada</erro>";
		exit;
	}
	$sql = "UPDATE tbl_os_orcamento SET orcamento_aprovacao = '$orcamento_aprovacao' WHERE os_orcamento = $os_orcamento";
	$res = @pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) == 0) {
		echo "<ok>Tudo OK</ok>";
		exit;
	}else{
		echo "<erro>" . pg_errormessage ($con) . "|" . $linha . "|orcamento_aprovacao" . "</erro>";
		exit;
	}
}

$conserto = $_GET['conserto'];
if (strlen ($conserto) > 0) {
	$sql = "SELECT os_orcamento FROM tbl_os_orcamento WHERE os_orcamento = $os_orcamento";
	$res = pg_exec ($con,$sql);
	if(pg_numrows ($res) == 0) {
		echo "<erro>OS Orcamento $os_orcamento nao encontrada</erro>";
		exit;
	}
	$sql = "UPDATE tbl_os_orcamento SET conserto = '$conserto' WHERE os_orcamento = $os_orcamento";
	$res = @pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) == 0) {
		echo "<ok>Tudo OK</ok>";
		exit;
	}else{
		echo "<erro>" . pg_errormessage ($con) . "|" . $linha . "|conserto" . "</erro>";
		exit;
	}
}

$fechamento = $_GET['fechamento'];
if (strlen ($fechamento) > 0) {
	$sql = "SELECT os_orcamento FROM tbl_os_orcamento WHERE os_orcamento = $os_orcamento";
	$res = pg_exec ($con,$sql);
	if(pg_numrows ($res) == 0) {
		echo "<erro>OS Orcamento $os_orcamento nao encontrada</erro>";
		exit;
	}
	$sql = "SELECT '$fechamento' > CURRENT_TIMESTAMP";
	$res = pg_exec ($con,$sql);
	if(pg_numrows ($res)>0) $data_maior = pg_result($res,0,0);
	if($data_maior=="t") {
		echo "<erro>A Data do fechamento no pode ser maior que a data atual</erro>";
		exit;
	}
	$sql = "UPDATE tbl_os_orcamento SET fechamento = '$fechamento' WHERE os_orcamento = $os_orcamento";
	$res = @pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) == 0) {
		echo "<ok>Tudo OK</ok>";
		exit;
	}else{
		echo "<erro>" . pg_errormessage ($con) . "|" . $linha . "|fechamento" . "</erro>";
		exit;
	}
}

$orcamento_aprovado = $_GET['orcamento_aprovado'];
if (strlen ($orcamento_aprovado) > 0) {
	$sql = "SELECT os_orcamento FROM tbl_os_orcamento WHERE os_orcamento = $os_orcamento";
	$res = pg_exec ($con,$sql);
	if(pg_numrows ($res) == 0) {
		echo "<erro>OS Orcamento $os_orcamento nao encontrada</erro>";
		exit;
	}
	$sql = "UPDATE tbl_os_orcamento SET orcamento_aprovado = '$orcamento_aprovado' WHERE os_orcamento = $os_orcamento";
	$res = @pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) == 0) {
		echo "<ok>Tudo OK</ok>";
		exit;
	}else{
		echo "<erro>" . pg_errormessage ($con) . "|" . $linha . "|orcamento_aprovado" . "</erro>";
		exit;
	}
}
?>