<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}

$contador_ver = "0";

$parametro = trim(utf8_decode($_REQUEST["parametro"]));
$valor     = trim(utf8_decode($_REQUEST["valor"]));

$usa_rev_fabrica = in_array($login_fabrica, array(3,15,24));
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

		<script>
			$(function () {
				$.dataTableLupa();
			});
		</script>
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

					<div class="span1"></div>
					<div class="span4">
						<input type="hidden" name="posicao" class="span12" value='<?=$posicao?>' />
						<input type="hidden" name="parametro" value='<?=$parametro?>' />
						CPF/CNPJ:
						<select name="parametro" class='span6'>
							<option value="cpf_cnpj" <?=($parametro == "cpf_cnpj") ? "SELECTED" : ""?> >CPF / CNPJ</option>
							<option value="nome_consumidor" <?=($parametro == "nome_consumidor") ? "SELECTED" : ""?> >Nome Consumidor</option>
						</select>
					</div>
					<div class="span4">
						<input type="text" name="valor" class="span12" value="<?=$valor?>" />
					</div>
					<div class="span2">
						<button type="button" class="btn pull-right" onclick="$(this).parents('form').submit();">Pesquisar</button>
					</div>
					<div class="span1"></div>
				</form>
			</div>
			<?
			$msg_confirma = "0";
			if (strlen($valor) >= 3) {
				switch ($parametro) {
					case 'cpf_cnpj':
						$valor = str_replace(array(".", ",", "-", "/"), "", $valor);
						$where_parametro = "AND consumidor_cpf LIKE '{$valor}%'";
						break;
					
					case 'nome_consumidor':
						$where_parametro = "AND consumidor_nome ILIKE '%{$valor}%'";
						break;

					default:
						$where_parametro = "";
						break;
				}

				if ($login_fabrica == 175){
					$orderBy  = "ORDER BY consumidor_cpf, tbl_os.os DESC";
					$distinct = "DISTINCT ON (consumidor_cpf)";
				}else{
					$orderBy  = "";
					$distinct = "";
				}
				
				$limit = 10;

				$sql = "SELECT {$distinct}
						os,
						sua_os,
						consumidor_nome,
						consumidor_email,
						consumidor_endereco,
						consumidor_numero,
						consumidor_cep,
						consumidor_complemento,
						consumidor_bairro,
						consumidor_cidade,
						consumidor_estado,
						consumidor_fone,
						consumidor_cpf,
						consumidor_celular,
						consumidor_fone_comercial,
						consumidor_fone_recado
					FROM tbl_os
					WHERE fabrica = {$login_fabrica}
					{$where_parametro}
					{$orderBy}
					LIMIT {$limit}";
				$res = pg_query($con, $sql);
				$rows = pg_num_rows($res);

				if ($rows > 0) { ?>
					<div id="border_table">
						<table class="table table-striped table-bordered table-hover table-lupa" >
							<thead>
								<tr class='titulo_coluna'>
									<?php if ($login_fabrica <> 175){ ?>
										<th>OS</th>
									<?php } ?>
									<th>CPF/CNPJ</th>
									<th>Nome</th>
									<th>Cidade</th>
									<th>UF</th>
								</tr>
							</thead>
							<tbody>
								<? for ($i = 0; $i < $rows; $i++) {
									$resultado[$i]["os"] = pg_fetch_result($res, $i, os);
									$resultado[$i]["sua_os"] = pg_fetch_result($res, $i, sua_os);
									$resultado[$i]["nome"] = utf8_encode(pg_fetch_result($res, $i, consumidor_nome));
									$resultado[$i]["email"] = utf8_encode(pg_fetch_result($res, $i, consumidor_email));
									$resultado[$i]["endereco"] = utf8_encode(pg_fetch_result($res, $i, consumidor_endereco));
									$resultado[$i]["numero"] = pg_fetch_result($res, $i, consumidor_numero);
									$resultado[$i]["cep"] = pg_fetch_result($res, $i, consumidor_cep);
									$resultado[$i]["complemento"] = utf8_encode(pg_fetch_result($res, $i, consumidor_complemento));
									$resultado[$i]["bairro"] = utf8_encode(pg_fetch_result($res, $i, consumidor_bairro));
									$resultado[$i]["cidade"] = utf8_encode(pg_fetch_result($res, $i, consumidor_cidade));
									$resultado[$i]["estado"] = pg_fetch_result($res, $i, consumidor_estado);
									$resultado[$i]["fone"] = pg_fetch_result($res, $i, consumidor_fone);
									$resultado[$i]["cpf"] = pg_fetch_result($res, $i, consumidor_cpf);
									$resultado[$i]["celular"] = pg_fetch_result($res, $i, consumidor_celular);
									$resultado[$i]["fone_comercial"] = pg_fetch_result($res, $i, consumidor_fone_comercial);
									$resultado[$i]["fone_recado"] = pg_fetch_result($res, $i, consumidor_fone_recado);
									$r = $resultado[$i]; ?>

									<tr onclick='window.parent.retorna_consumidor_os(<?= json_encode($r); ?>); window.parent.Shadowbox.close();'>
										<?php if ($login_fabrica <> 175){?>
											<td class='cursor_lupa'><?= $resultado[$i]['sua_os']; ?></td>
										<?php } ?>
										<td class='cursor_lupa'><?= $resultado[$i]['cpf']; ?></td>
										<td class='cursor_lupa'><?= $resultado[$i]['nome']; ?></td>
										<td class='cursor_lupa'><?= $resultado[$i]['cidade']; ?></td>
										<td class='cursor_lupa'><?= $resultado[$i]['estado']; ?></td>
									</tr>
								<? } ?>
							</tbody>
						</table>
					</div>
				<? } else { ?>
					<div class="alert alert_shadobox">
						    <h4>Nenhum resultado encontrado</h4>
					</div>
				<? }
			} else { ?>
				<div class="alert alert_shadobox">
					<h4>Informe toda ou parte da informação para pesquisar!</h4>
				</div>
			<? } ?>
		</div>
	</body>
</html>

