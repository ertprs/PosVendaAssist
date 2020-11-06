<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

$login_fabrica_distrib = 153;


if ($_REQUEST["completo"]) {
	$completo   = $_REQUEST["completo"];
}

if ($_REQUEST["locadora-revenda"]) {
	$locadora_revenda   = $_REQUEST["locadora-revenda"];
}

$parametro = $_REQUEST["parametro"];
$valor     = utf8_decode(trim($_REQUEST["valor"]));

if(isset($_REQUEST["parametro"]) && $_REQUEST["parametro"] == "cnpj"){
	$cnpj_posto = $_REQUEST["valor"];
}

?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="../bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="../bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="../css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="../bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="../plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="../bootstrap/js/bootstrap.js"></script>
		<script src="../plugins/dataTable.js"></script>
		<script src="../plugins/resize.js"></script>
		<script src="../plugins/shadowbox_lupa/lupa.js"></script>

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
					<input type="hidden" name="completo" value='<?=$completo?>' />

					<?php
					if (isset($locadora_revenda)) {
					?>
						<input type="hidden" name="locadora-revenda" value='<?=$locadora_revenda?>' />
					<?php
					}
					?>

					<select name="parametro" >
						<option value="cnpj" <?=($parametro == "cnpj") ? "SELECTED" : ""?> >CNPJ</option>
						<option value="codigo" <?=($parametro == "codigo") ? "SELECTED" : ""?> >Código</option>
						<option value="nome" <?=($parametro == "nome") ? "SELECTED" : ""?> >Nome</option>
					</select>
				</div>
				<div class="span4">
					<input type="text" name="valor" class="span12" value="<?=$valor?>" />
				</div>
				<div class="span2">
					<button type="button" class="btn pull-right" onclick="$(this).parents('form').submit();">Pesquisar</button>
				</div>
			</form>
			</div>
			<?php
			if (strlen($valor) >= 3) {
				switch ($parametro) {
					case 'codigo':
						$valor = str_replace(array(".", ",", "-", "/"), "", $valor);
						$whereAdc = "UPPER(tbl_posto_fabrica.codigo_posto) ILIKE UPPER('%{$valor}%')";
						break;
					
					case 'nome':
						$whereAdc = "(UPPER(tbl_posto.nome) ILIKE UPPER('%{$valor}%') OR UPPER(tbl_posto_fabrica.nome_fantasia) ILIKE UPPER('%{$valor}%') OR UPPER(tbl_posto.nome_fantasia) ILIKE UPPER('%{$valor}%'))";
						break;
				}
				if(isset($cnpj_posto)){
					$whereAdc = " tbl_posto.cnpj = '{$cnpj_posto}' ";
				}
				if (isset($whereAdc)) {
					if (isset($completo) || $login_fabrica_distrib == 7) {
						$camposAdc = ", tbl_posto_fabrica.contato_email, 
									   tbl_posto_fabrica.contato_endereco || ' - ' || tbl_posto_fabrica.contato_numero  AS contato_endereco,
									   tbl_posto_fabrica.contato_complemento,
									   tbl_posto_fabrica.contato_bairro,
									   tbl_posto_fabrica.contato_cep,
									   tbl_posto_fabrica.contato_fone_comercial,
									   tbl_posto_fabrica.contato_fax,
									   tbl_posto_fabrica.contato_nome,
									   tbl_posto_fabrica.contato_cidade,
									   tbl_posto_fabrica.contato_estado";
					}
					if ($login_fabrica_distrib != 117) {
						$filterByLocation = "";
						if(!empty($_REQUEST["estado"])){
							$filterByLocation = " AND tbl_posto_fabrica.contato_estado = '" . $_REQUEST["estado"] . "'";
						}
						if(!empty($_REQUEST["cidade"])){
							$filterByLocation = " AND tbl_posto_fabrica.cod_ibge = " . $_REQUEST["cidade"];
						}	
					}
					
					if (isset($locadora_revenda) && $login_fabrica_distrib == 148) {
						$distinctPosto = "DISTINCT";
						$joinTipoPosto = "
							INNER JOIN tbl_posto_tipo_posto ON tbl_posto_tipo_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_tipo_posto.fabrica = {$login_fabrica_distrib}
							INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica_distrib}
						";
						$whereTipoPosto = "
							AND (tbl_tipo_posto.locadora IS TRUE OR tbl_tipo_posto.tipo_revenda IS TRUE)
						";
					}
					if ($login_fabrica_distrib == 153) {
						//$distinctPosto = "DISTINCT tbl_tipo_posto.posto_interno ,";
						$joinTipoPosto = "
							LEFT JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica_distrib}
						";
						$whereTipoPosto = "
							AND tbl_tipo_posto.distribuidor IS TRUE
						";
					}

					
					 $sql = "SELECT
								{$distinctPosto} tbl_posto.posto, 

								tbl_posto.cnpj,
								tbl_posto.nome,
								tbl_posto_fabrica.contato_endereco AS endereco,
								tbl_posto_fabrica.contato_numero AS numero,
								tbl_posto_fabrica.contato_bairro AS bairro,
								tbl_posto_fabrica.contato_cidade AS cidade,
								tbl_posto_fabrica.contato_estado AS estado, 
								tbl_posto_fabrica.contato_cep AS cep, 
								tbl_posto_fabrica.codigo_posto,
								tbl_posto_fabrica.controla_estoque,
								tbl_posto_fabrica.credenciamento,
								tbl_posto_fabrica.cod_ibge,
								tbl_posto_fabrica.latitude,
								tbl_posto_fabrica.longitude
								{$camposAdc}
							FROM tbl_posto
							JOIN tbl_posto_fabrica USING (posto)
							{$joinTipoPosto}
							WHERE {$whereAdc}
							{$filterByLocation}
							AND tbl_posto_fabrica.fabrica = {$login_fabrica_distrib}
							AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
							ORDER BY tbl_posto.nome";

					$res = pg_query($con, $sql);

					//thiago
					//echo nl2br($sql);

					$rows = pg_num_rows($res);
					if ($rows > 0) {
					?>
						<div id="border_table">             
						<table class="table table-striped table-bordered table-hover table-lupa" >
							<thead>
								<tr class='titulo_coluna'>
									<th>Código</th>
									<th>CNPJ</th>
									<th>Nome</th>
									<th>Cidade</th>
									<th>Estado</th>
								</tr>
							</thead>
							<tbody>
								<?php
								for ($i = 0 ; $i < $rows; $i++) {
									$credenciamento   = pg_fetch_result($res, $i, "credenciamento");
									$codigo_posto     = pg_fetch_result($res, $i, "codigo_posto");
									$posto            = pg_fetch_result($res, $i, "posto");
									$nome             = str_replace("'", "\'", pg_fetch_result($res, $i, "nome"));
									$cnpj             = pg_fetch_result($res, $i, "cnpj");
									$endereco         = pg_fetch_result($res, $i, "endereco");
									$numero           = pg_fetch_result($res, $i, "numero");
									$bairro           = pg_fetch_result($res, $i, "bairro");
									$cidade           = pg_fetch_result($res, $i, "cidade");
									$estado           = pg_fetch_result($res, $i, "estado");
									$cep              = pg_fetch_result($res, $i, "cep");
									$latitude         = pg_fetch_result($res, $i, "latitude");
									$longitude        = pg_fetch_result($res, $i, "longitude");
									$controla_estoque = pg_fetch_result($res, $i, "controla_estoque");
									$cod_ibge         = pg_fetch_result($res, $i, cod_ibge);

									if (isset($completo) || $login_fabrica_distrib == 7) {
										$endereco       = pg_fetch_result($res, $i, 'contato_endereco');
										$bairro         = pg_fetch_result($res, $i, 'contato_bairro');
										$cep            = pg_fetch_result($res, $i, 'contato_cep');
										$fone_comercial = pg_fetch_result($res, $i, 'contato_fone_comercial');
										$fax            = pg_fetch_result($res, $i, 'contato_fax');
										$email          = pg_fetch_result($res, $i, 'contato_email');
										$contato        = pg_fetch_result($res, $i, 'contato_nome');
										$endereco       .= ' ' . pg_fetch_result($res, $i, 'contato_complemento');
										$cidade         = pg_fetch_result($res,$i,'contato_cidade');
										$estado         = pg_fetch_result($res,$i,'contato_estado');
									}

									$r = array(
										"posto"            => $posto,
										"nome"             => utf8_encode($nome),
										"codigo"           => $codigo_posto,
										"cnpj"             => $cnpj,
										"latitude"         => $latitude,
										"longitude"        => $longitude,
										"controla_estoque" => (($controla_estoque == "t") ? true : false),
										"estado"           => $estado,
										"cidade"           => $cidade,
										"cod_ibge"         => $cod_ibge

									);

									if($login_fabrica_distrib == 157){
										$posto_interno         = pg_fetch_result($res,$i,'posto_interno');
										$r["posto_interno"] = ($posto_interno == "t") ? true : false;
									}
									if($login_fabrica_distrib == 148){

										$sql = "SELECT 
													tbl_tipo_posto.tipo_posto,
													tbl_tipo_posto.descricao,
													tbl_tipo_posto.locadora,
													tbl_tipo_posto.tipo_revenda,
													tbl_tipo_posto.montadora,
													tbl_tipo_posto.posto_interno  
												FROM tbl_posto_tipo_posto 
												JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica_distrib}  
												WHERE 
													tbl_posto_tipo_posto.fabrica = {$login_fabrica_distrib} 
													AND tbl_posto_tipo_posto.posto = {$posto}";
										$resTipoPosto = pg_query($con, $sql);

										if(pg_num_rows($resTipoPosto) > 0){

											$tipos_postos = array();

											for ($x = 0; $x < pg_num_rows($resTipoPosto); $x++) { 
												$tipos_postos[] = array(
														"tipo_posto"    => pg_fetch_result($resTipoPosto, $x, "tipo_posto"),
														"descricao"     => pg_fetch_result($resTipoPosto, $x, "descricao"),
														"locadora"     => (pg_fetch_result($resTipoPosto, $x, "locadora") == "t") ? true : false,
														"tipo_revenda"  => (pg_fetch_result($resTipoPosto, $x, "tipo_revenda") == "t") ? true : false,
														"montadora"     => (pg_fetch_result($resTipoPosto, $x, "montadora") == "t") ? true : false,
														"posto_interno" => (pg_fetch_result($resTipoPosto, $x, "posto_interno") == "t") ? true : false
													);
											}

											$r["tipos_postos"] = $tipos_postos;

										}

									}

									echo "<tr onclick='window.parent.retorna_posto(".json_encode($r)."); window.parent.Shadowbox.close();' >";
										echo "<td class='cursor_lupa'>{$codigo_posto}</td>";
										echo "<td class='cursor_lupa'>{$cnpj}</td>";
										echo "<td class='cursor_lupa'>{$nome}</td>";
										echo "<td class='cursor_lupa'>{$cidade}</td>";
										echo "<td class='cursor_lupa'>{$estado}</td>";
									echo "</tr>";
								}
								?>
							</tbody>
						</table>
						</div>
					<?php
					} else {
						echo '
						<div class="alert alert_shadobox">
							<h4>Nenhum resultado encontrado</h4>
						</div>';
					}
				}
			} else {
				echo '
					<div class="alert alert_shadobox">
						<h4>Informe toda ou parte da informação para pesquisar!</h4>
					</div>';
				}
		?>
	</div>
	</body>
</html>
