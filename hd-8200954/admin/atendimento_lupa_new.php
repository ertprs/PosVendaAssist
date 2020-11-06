<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include '../token_cookie.php';
$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
if ($areaAdmin === true ) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}

$tipo_pesquisa = $_REQUEST["tipo_pesquisa"];
$desc_pesquisa = $_REQUEST["descricao_pesquisa"];

// Parâmetro que irá receber o retorno a ser executado
// Exemplo: "atendimento_lupa_new.php?&funcao_retorno=retorna_dados_atendimento"
$funcao_retorno = $_REQUEST["funcao_retorno"];


// Se não receber o parâmetro tipo de pesquisa, por padrão irá tratar como atendimento
if(empty($tipo_pesquisa)){
	$tipo_pesquisa = "atendimento";
}

// Se não receber o parâmetro função retorno, irá executar, por padrão, a função "retorna_dados_atendimento" no parent
if(empty($funcao_retorno)){
	$funcao_retorno = 'retorna_dados_atendimento';
}

if(!empty($funcao_retorno)){
	$funcao_retorno = 'window.parent.' . $funcao_retorno;
}

if(!empty($desc_pesquisa) && !empty($tipo_pesquisa)){
	
	$sql = "SELECT tbl_hd_chamado.hd_chamado as atendimento, 
					   tbl_hd_chamado.status as status_atendimento,
					   tbl_hd_chamado.categoria as tipo_atendimento,
					   to_char(tbl_hd_chamado.data, 'DD/MM/YYYY') as data_atendimento,
					   tbl_hd_chamado_extra.nome as nome_cliente,
					   tbl_hd_chamado_extra.cpf as cpf_cliente,
				       concat_ws(', ', tbl_hd_chamado_extra.endereco, tbl_hd_chamado_extra.numero, 
				       				   tbl_hd_chamado_extra.bairro, tbl_hd_chamado_extra.cep) as endereco_cliente
		 		FROM tbl_hd_chamado JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		 		WHERE tbl_hd_chamado.fabrica = $login_fabrica ";

	switch(strtoupper($tipo_pesquisa)){

		case 'ATENDIMENTO' : 
		$sql .= " AND tbl_hd_chamado.hd_chamado = $desc_pesquisa "; break;

		case 'NOME' :
		$sql .= " AND upper(tbl_hd_chamado_extra.nome) = upper('$desc_pesquisa') "; break;

		default : 
		$sql .= " AND tbl_hd_chamado.hd_chamado = $desc_pesquisa ";
	}

	$sql .= "LIMIT 100";
	$qry = pg_query($con, $sql);

	if(pg_num_rows($qry) > 0){
		$atendimentos = pg_fetch_all($qry);
 	}
}

if(!function_exists("recuperaDadosAtendimento")){

	function recuperaDadosAtendimento($atendimento){

		foreach($atendimento as $key => $value){
			$atendimento[$key] = utf8_decode($value);
		}

		return json_encode($atendimento);
	}
}

?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />
		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>
	</head>
	<body>
		<div id="container_lupa" style="overflow-y:auto;">
			<div id="topo">
				<img class="espaco" src="imagens/logo_new_telecontrol.png">
				<img class="lupa_img pull-right" src="imagens/lupa_new.png">
			</div>
			<br /><hr />
			<div class="row-fluid">
				<form action="<?=$_SERVER['PHP_SELF']?>" method='POST' >
					<input type="hidden" name="funcao_retorno" value="<?=$funcao_retorno?>"?>
					<div class="span1"></div>
					<div class="span4">
						<label class="control-label" for="tipo_pesquisa">Pesquisa por:</label>
						<select name="tipo_pesquisa" >
							<option value="cpf" <?=(strtoupper($tipo_pesquisa) == "ATENDIMENTO") ? "SELECTED" : ""?> >Atendimento</option>
							<option value="nome" <?=(strtoupper($tipo_pesquisa) == "NOME") ? "SELECTED" : ""?> >Nome</option>
						</select>
					</div>
					<div class="span4">
						<label class="control-label" for="descricao_pesquisa"><?=utf8_decode("Descrição")?></label>
						<input type="text" name="descricao_pesquisa" class="span12" value="<?=$desc_pesquisa?>" />
					</div>
					<div class="span2" style="padding-top:18px">
						<button type="submit" class="btn pull-right">Pesquisar</button>
					</div>
					<div class="span1"></div>
				</form>
			</div>	
			<? if(!empty($atendimentos)) : ?>
				<div id="border_table">
					<table class="table table-striped table-bordered table-hover table-lupa" >
						<thead>
							<tr class='titulo_coluna'>
								<th>Atendimento</th>
								<th>Data</th>
								<th>Cliente</th>
								<th>CPF</th>
								<th>Status</th>
								<th>Tipo Atendimento</th>
							</tr>
						</thead>
						<tbody>
							<? foreach($atendimentos as $atendimento) : ?>
								<tr>
									<td>
										<? if(!empty($funcao_retorno)) : ?>
											<a href="#" onclick='<?="javascript:" . $funcao_retorno . "(". recuperaDadosAtendimento($atendimento) ."); window.parent.Shadowbox.close();"?>'><?=$atendimento['atendimento']?></a>
										<? else : ?>
											<a href="#" onclick="'javascript: window.parent.Shadowbox.close();'"><?=$atendimento['atendimento']?></a>
										<? endif; ?>	
									</td>
									<td><?=$atendimento['data_atendimento']?></td>
									<td><?=$atendimento['nome_cliente']?></td>
									<td><?=$atendimento['cpf_cliente']?></td>
									<td><?=$atendimento['status_atendimento']?></td>
									<td><?=$atendimento['tipo_atendimento']?></td>
								</tr>
							<? endforeach; ?>
						</tbody>
					</table>
				</div>		
			<? else: ?>
				<div class="alert" style="margin-top:20px"><h4>Nenhum registro encontrado</h4></div>
			<? endif; ?>
		</div>
	</body>
</html>
<script type="text/javascript">

$(document).ready(function(){
	$.dataTableLupa();
});

</script>
