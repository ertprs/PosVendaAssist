<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$contador_ver = "0";

$refe = trim(utf8_decode($_REQUEST["refe"]));
$conFab = "";
if ($refe == "roteiro") {
	$conFab = " AND tbl_cliente.fabrica={$login_fabrica}";
}
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
			<img class="espaco" src="../imagens/logo_new_telecontrol.png">
			<img class="lupa_img pull-right" src="../imagens/lupa_new.png">
		</div>
		<br /><hr />
		<div class="row-fluid">
			<form action="<?=$_SERVER['PHP_SELF']?>" method='POST' >

			<div class="span1"></div>
				<div class="span4">
					<input type="hidden" name="posicao" class="span12" value='<?=$posicao?>' />
					<input type="hidden" name="refe" class="span12" value='<?=$refe?>' />

					<select name="parametro"  >
						<option value="cnpj" <?=($parametro == "cnpj") ? "SELECTED" : ""?> >CPF / CNPJ</option>
						<option value="nome_consumidor" <?=($parametro == "nome_consumidor") ? "SELECTED" : ""?> >Nome Consumidor</option>
						<? if (in_array($login_fabrica, array(158))) { ?>
							<option value="nome_fantasia_consumidor" <?=($parametro == "nome_fantasia_consumidor") ? "SELECTED" : ""?> >Nome Fantasia</option>
						<? } ?>
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

		<?php
		$msg_confirma = "0";
		if (strlen($valor) >= 5) {

			switch ($parametro) {

				case 'cnpj':
					$valor = str_replace(array(".", ",", "-", "/"), "", $valor);

					if($login_fabrica==7){
						$sql = "SELECT  tbl_posto.nome        AS nome       ,
									tbl_posto.cnpj        AS cpf        ,
									tbl_posto.ie          AS rg         ,
									tbl_posto.endereco    AS endereco   ,
									tbl_posto.numero      AS numero     ,
									tbl_posto.complemento AS complemento,
									tbl_posto.fone        AS fone       ,
									tbl_posto.cep         AS cep        ,
									tbl_posto.bairro      AS bairro     ,
									tbl_posto.cidade      AS nome_cidade,
									tbl_posto.estado      AS estado     ,
									tbl_posto.posto       AS cliente
							FROM  tbl_posto
							JOIN  tbl_posto_consumidor USING(posto)
							WHERE  tbl_posto.cnpj      ILIKE '%$valor%'
							AND   tbl_posto_consumidor.fabrica = $login_fabrica
							ORDER BY tbl_posto.nome";
					} else if (in_array($login_fabrica, array(158))) {
						$sql = "SELECT tbl_cliente.*,
								tbl_cidade.nome AS nome_cidade,
								tbl_cidade.estado             ,
								tbl_cliente.fone
							FROM tbl_cliente
							LEFT JOIN tbl_cidade USING(cidade)
							LEFT JOIN tbl_fabrica_cliente USING(cliente)
							WHERE tbl_cliente.cpf ILIKE '%{$valor}%'
							AND tbl_fabrica_cliente.fabrica = {$login_fabrica}
							ORDER BY tbl_cliente.nome";
					} else {
						$sql = "SELECT      tbl_cliente.*                 ,
									tbl_cidade.nome AS nome_cidade,
									tbl_cidade.estado             ,
									tbl_cliente_contato.fone
							FROM        tbl_cliente
							LEFT JOIN   tbl_cidade USING (cidade)
							LEFT JOIN   tbl_cliente_contato USING (cliente)
							WHERE       tbl_cliente.cpf ILIKE '%$valor%' 
							{$conFab}
							ORDER BY    tbl_cliente.nome";
					}

					break;

				case 'nome_consumidor':
					if($login_fabrica==7){
						$sql = "SELECT  tbl_posto.nome        AS nome       ,
								tbl_posto.cnpj        AS cpf        ,
								tbl_posto.ie          AS rg         ,
								tbl_posto.endereco    AS endereco   ,
								tbl_posto.numero      AS numero     ,
								tbl_posto.complemento AS complemento,
								tbl_posto.fone        AS fone       ,
								tbl_posto.cep         AS cep        ,
								tbl_posto.bairro      AS bairro     ,
								tbl_posto.cidade      AS nome_cidade,
								tbl_posto.estado      AS estado     ,
								tbl_posto.posto       AS cliente
							FROM  tbl_posto
							JOIN  tbl_posto_consumidor USING(posto)
							WHERE  tbl_posto.nome     ILIKE '%$valor%'
							AND   tbl_posto_consumidor.fabrica = $login_fabrica
							ORDER BY tbl_posto.nome";
					}else if (in_array($login_fabrica, array(158))) {
						$sql = "SELECT tbl_cliente.*,
								tbl_cidade.nome AS nome_cidade,
								tbl_cidade.estado    as UF         ,
								tbl_cliente.fone
							FROM tbl_cliente
							LEFT JOIN tbl_cidade USING(cidade)
							LEFT JOIN tbl_fabrica_cliente USING(cliente)
							WHERE tbl_cliente.nome ILIKE '%{$valor}%'
							AND tbl_fabrica_cliente.fabrica = {$login_fabrica}
							ORDER BY tbl_cliente.nome";
					} else {
						$sql = "SELECT      tbl_cliente.*                 ,
								tbl_cidade.nome AS nome_cidade,
								tbl_cidade.estado     as UF        ,
								tbl_cliente_contato.fone
							FROM        tbl_cliente
							LEFT JOIN   tbl_cidade USING (cidade)
							LEFT JOIN   tbl_cliente_contato USING (cliente)
							WHERE       tbl_cliente.nome ILIKE '%$valor%' 
							{$conFab}
							ORDER BY    tbl_cliente.nome";
					}
				break;

				case 'nome_fantasia_consumidor':
					if (in_array($login_fabrica, array(158))) {
						$sql = "SELECT tbl_cliente.*,
								tbl_cidade.nome AS nome_cidade,
								tbl_cidade.estado  as UF           ,
								tbl_cliente.fone
							FROM tbl_cliente
							LEFT JOIN tbl_cidade USING(cidade)
							LEFT JOIN tbl_fabrica_cliente USING(cliente)
							WHERE tbl_cliente.nome_fantasia ILIKE '%{$valor}%'
							AND tbl_fabrica_cliente.fabrica = {$login_fabrica}
							ORDER BY tbl_cliente.nome";
					}
				break;
			}

			$res = pg_query($con, $sql);
			$rows = pg_num_rows($res);
			
			if ($rows > 0) { ?>
				<div id="border_table">
					<table class="table table-striped table-bordered table-hover table-lupa" >
						<thead>
							<tr class='titulo_coluna'>
								<th>CPF / CNPJ</th>
								<? if (in_array($login_fabrica, array(158))) { ?>
									<th>Nome Fantasia</th>
								<? } ?>
								<th>Nome</th>
								<th>Cidade</th>
								<th>UF</th>
							</tr>
						</thead>
						<tbody>
							<? for ($i = 0; $i < $rows; $i++) {
								$resultado[$i]["cliente"]          	= utf8_encode(pg_fetch_result($res, $i, 'cliente'));
								$resultado[$i]["cpf"]          	    = utf8_encode(pg_fetch_result($res, $i, 'cpf'));
								$resultado[$i]["nome"]              = utf8_encode(pg_fetch_result($res, $i, 'nome'));
								$resultado[$i]["nome_fantasia"]     = utf8_encode(pg_fetch_result($res, $i, 'nome_fantasia'));
								$resultado[$i]["consumidor_cidade"] = utf8_encode(pg_fetch_result($res, $i, 'nome_cidade'));
								$resultado[$i]["endereco"]       	= utf8_encode(pg_fetch_result($res, $i, 'endereco'));
								$resultado[$i]["rg"]            	= utf8_encode(pg_fetch_result($res, $i, 'rg'));
								$resultado[$i]["numero"]        	= utf8_encode(pg_fetch_result($res, $i, 'numero'));
								$resultado[$i]["complemento"]   	= utf8_encode(pg_fetch_result($res, $i, 'complemento'));
								$resultado[$i]["bairro"]        	= str_replace("'","", utf8_encode(pg_fetch_result($res, $i, 'bairro')));
								$resultado[$i]["estado"]        	= utf8_encode(pg_fetch_result($res, $i, 'uf'));
								$resultado[$i]["fone"]          	= utf8_encode(pg_fetch_result($res, $i, 'fone'));
								$resultado[$i]["cep"]           	= utf8_encode(pg_fetch_result($res, $i, 'cep'));
								$resultado[$i]["contrato_numero"]  	= utf8_encode(pg_fetch_result($res, $i, 'contrato_numero'));
								$resultado[$i]["contrato"]       	= utf8_encode(pg_fetch_result($res, $i, 'contrato'));
								$resultado[$i]["consumidor_final"]  = utf8_encode(pg_fetch_result($res, $i, 'consumidor_final'));
								$resultado[$i]["email"]  			= utf8_encode(pg_fetch_result($res, $i, 'email'));
								$r = json_encode($resultado[$i]); ?>
								 

								<tr onclick='window.parent.retorna_consumidor(<?= $r; ?>); window.parent.Shadowbox.close();' >
									<td class='cursor_lupa'><?= $resultado[$i]['cpf']; ?></td>
									<? if (in_array($login_fabrica, array(158))) { ?>
										<td class='cursor_lupa'><?= $resultado[$i]['nome_fantasia']; ?></td>
									<? } ?>
									<td class='cursor_lupa'><?= $resultado[$i]['nome']; ?></td>
									<td class='cursor_lupa'><?= $resultado[$i]['consumidor_cidade']; ?></td>
									<td class='cursor_lupa'><?= $resultado[$i]['estado']; ?></td>
								</tr>
							<? } ?>
						</tbody>
					</table>
				</div>
			<? }else{ ?>
				<div class="alert alert_shadobox">
					    <h4>Nenhum resultado encontrado</h4>
				</div>
			<? }
		} else { ?>
			<div class="alert alert_shadobox">
				<h4>Informe toda ou parte da informação para pesquisar!</h4>
			</div>
		<? } ?>
	</body>
</html>

