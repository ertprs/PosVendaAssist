<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include '../ajax_cabecalho.php';

$admin_privilegios="info_tecnica,call_center";
include 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';


$tecnico = $_GET['tecnico'];


$sql = "SELECT tecnico, nome, email, telefone, ativo, data_input FROM tbl_tecnico WHERE tecnico = $tecnico";
$res_tecnico = pg_query($con,$sql);

$res_tecnico = pg_fetch_array($res_tecnico);


$sql = "SELECT tr.titulo, TO_CHAR(tr.data_inicio,'DD/MM/YYYY') as data_inicio, tp.nota_tecnico, tp.participou, tp.aprovado
FROM tbl_treinamento_posto tp 
JOIN tbl_tecnico t ON tp.tecnico = t.tecnico
JOIN tbl_treinamento tr ON tp.treinamento = tr.treinamento
WHERE tp.participou IS TRUE 
AND t.tecnico = ".$tecnico." 
AND tr.fabrica = ".$login_fabrica;

$res = pg_query($con,$sql);

?>
<!DOCTYPE html />
<html>
<head>
<meta http-equiv=pragma content=no-cache>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="bootstrap/js/bootstrap.js"></script>
<script type="text/javascript" src="plugins/resize.js"></script>
<script type="text/javascript" src="plugins/shadowbox_lupa/lupa.js"></script>

<script type="text/javascript">


</script>
</head>
<body>
	<div class="container-fluid form_tc" style="height:600px; overflow: auto;">
		<div class="titulo_tabela">Histórico do Técnico</div>				
		<div class="row-fluid">
			<div class="span12 tac">
				<h4>Informações do Técnico</h4>
			</div>			
		</div>
		<div class="row-fluid">
			<div class="span3 tac">
				<b>Nome</b>
				<p><?=$res_tecnico['nome']?></p>
			</div>
			<div class="span3 tac">
				<b>Email</b>
				<p><?=$res_tecnico['email']?></p>
			</div>
			<div class="span3 tac">
				<b>Telefone</b>
				<p><?=$res_tecnico['telefone']?></p>
			</div>
			<div class="span3 tac">
				<b>Ativo</b>
				<p><?=$res_tecnico['ativo']? "Sim": "Não"?></p>
			</div>
		</div>
		<hr>

		<table class="table table-striped table-fixed">
			<thead>				
				<tr class="titulo_coluna">
					<th>Titulo do Treinamento</th>
					<th>Data do treinamento</th>
					<th>Participou?</th>
					<th>Nota do Técnico</th>
					<th>Aprovado?</th>
				</tr>
			</thead>
			<tbody>
				<?php
				while($treinamentoRes = pg_fetch_array($res)){
					?>
					<tr>
						<td class="tac"><?=$treinamentoRes['titulo']?></td>
						<td class="tac"><?=$treinamentoRes['data_inicio']?></td>
						<td class="tac"><?=$treinamentoRes['participou']? "Sim": "Não"?></td>
						<td class="tac"><?=$treinamentoRes['nota_tecnico']?></td>
						<td class="tac"><?=$treinamentoRes['aprovado']?"Sim":"Não"?></td>
					</tr>
					<?php
				}
				?>
				
			</tbody>
		</table>

		<div class="row-fluid">
			<div class="span12">
				<a class="btn" style="float: right;" href="detalhes_treinamento.php?treinamento=<?=$_GET['treinamento']?>"><i class="icon-circle-arrow-left"></i> Voltar</a>
			</div>
		</div>
</body>
</html>
