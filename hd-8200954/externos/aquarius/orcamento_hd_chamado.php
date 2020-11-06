<?php
include_once '../../dbconfig.php';
include_once '../../includes/dbconnect-inc.php';
include_once '../../class/communicator.class.php';

$orcamento 	= $_GET['orcamento'];
$status 	= $_GET['resposta'];
$admin_callcenter = $_GET['admin_callcenter'];
echo "<div align='center'><img src='logo_aquarius.png' /></div>";
$sqlValida = "SELECT aprovado, data_aprovacao, data_reprovacao, hd_chamado FROM tbl_orcamento where orcamento = {$orcamento}";
$resValida = pg_query($con, $sqlValida);
$data_aprovacao  = pg_fetch_result($resValida, 0, data_aprovacao);
$data_reprovacao = pg_fetch_result($resValida, 0, data_reprovacao);
$status_sql = pg_fetch_result($resValida, 0, aprovado);
$hd_chamado = pg_fetch_result($resValida, 0, hd_chamado);

if (is_null($admin_callcenter) && (!is_null($data_aprovacao) || !is_null($data_reprovacao))) {
	$data = date_format(date_create(($status_sql) ? $data_reprovacao : $data_aprovacao) , "d/m/Y H:i:s");
	$status = ($status_sql) ? 'Reprovada' : 'Aprovada' ;
	echo "<h3 style='text-align: center;'>Orçamento ja foi registrado como {$status}, em {$data}.</h3>";
	die;
}
if ($status == 'aprovado' && is_null($admin_callcenter)) {
	$sqlUpdateOrcamento = "	UPDATE tbl_orcamento 
							SET
							aprovado = 't',
							data_aprovacao = now(),
							tipo_aprovacao = 'Aprovado pelo cliente por e-mail.'
							WHERE orcamento = {$orcamento}
							returning hd_chamado";
	$updateOrcamento = pg_query($con,$sqlUpdateOrcamento);
	$hd_chamado = pg_fetch_result($updateOrcamento, 0, hd_chamado); 
	$ins = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, interno) VALUES ($hd_chamado, 'Aprovado pelo cliente por e-mail.', 't')";
	$color = 'green';
	$ator = "cliente";
} elseif ($status == 'aprovado' && !is_null($admin_callcenter)){
	$sqlUpdateOrcamento = "	UPDATE tbl_orcamento 
							SET
							aprovado = 't',
							data_aprovacao = now(),
							tipo_aprovacao = 'Aprovado pelo Callcenter.'
							WHERE orcamento = {$orcamento}
							returning hd_chamado";
	$updateOrcamento = pg_query($con,$sqlUpdateOrcamento);
	$hd_chamado = pg_fetch_result($updateOrcamento, 0, hd_chamado); 
	$updateOrcamento = pg_query($con,$sqlUpdateOrcamento);
	$ins = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, interno) VALUES ($hd_chamado, 'Aprovado pelo Callcenter', 't')";
	$color = 'green';
	$ator = "Callcenter";
}
if ($status == 'reprovado' && is_null($admin_callcenter) ) {
	$sqlUpdateOrcamento = "	UPDATE tbl_orcamento 
							SET
							aprovado = 'f',
							data_reprovacao = now()
							WHERE orcamento = {$orcamento}
							returning hd_chamado";
	$updateOrcamento = pg_query($con,$sqlUpdateOrcamento); 
	$hd_chamado = pg_fetch_result($updateOrcamento, 0, hd_chamado); 
	$updateOrcamento = pg_query($con,$sqlUpdateOrcamento);
	$ins = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, interno) VALUES ($hd_chamado, 'Reprovado pelo cliente por e-mail.', 't')";
	$color = 'red';
	$ator = "cliente";
} elseif ($status == 'reprovado' && !is_null($admin_callcenter)){
	$sqlUpdateOrcamento = "	UPDATE tbl_orcamento 
							SET
							aprovado = 'f',
							data_reprovacao = now()
							WHERE orcamento = {$orcamento}
							returning hd_chamado";
	$updateOrcamento = pg_query($con,$sqlUpdateOrcamento); 
	$hd_chamado = pg_fetch_result($updateOrcamento, 0, hd_chamado); 
	$updateOrcamento = pg_query($con,$sqlUpdateOrcamento);
	$ins = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, interno) VALUES ($hd_chamado, 'Reprovado pelo Callcenter', 't')";
	$color = 'red';
	$ator = "Callcenter";
}
$qry = pg_query($con, $ins);
$email = [		'sonali.araujo@aquariusbrasil.com',
			'atendimento@aquariusbrasil.com',
			'leonardo.oliveira@telecontrol.com.br'
		 ];	
$mensagem = "ORÇAMENTO $orcamento do Atendimento $hd_chamado foi $status pelo $ator.";
$mailTc = new TcComm('smtp@posvenda');
$res = $mailTc->sendMail(
	$email,
	"ORÇAMENTO - Atendimento {$hd_chamado} AQUÁRIUS BRASIL",
	$mensagem,
	$externalEmail
);
echo "<h2 style='color: $color;text-align: center;'>Orçamento $status</h2>";
