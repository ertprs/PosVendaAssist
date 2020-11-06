<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
	include "funcoes.php";
	$area_admin = true;
}  elseif (preg_match("/\/admin_es\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
	include '../funcoes.php';
}  elseif (preg_match("/\/admin_cliente\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
	include '../funcoes.php';
	include_once '../fn_traducao.php';
}   elseif (preg_match("/\/admin_representante\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
	include '../funcoes.php';
	include_once '../fn_traducao.php';
}  else {
	include 'autentica_usuario.php';
	include "funcoes.php";
}


if ($_REQUEST["matriz"]) {
	$matriz = $_REQUEST["matriz"];
}

if ($_REQUEST["posicao"]) {
	$posicao = $_REQUEST["posicao"];
}

if ($_REQUEST["cadastra_tecnico_admin"]){ //usada na tela admin/treinamento_cadastro.php
	$cadastra_tecnico_admin = $_REQUEST["cadastra_tecnico_admin"];
}

if ($_REQUEST["completo"]) {
	$completo = $_REQUEST["completo"];
}

if ($_REQUEST["locadora-revenda"]) {
	$locadora_revenda = $_REQUEST["locadora-revenda"];
}
$refe_roteiro = "";
if ($_REQUEST["refe"]) {
	$refe_roteiro = $_REQUEST["refe"];
}

$parametro = $_REQUEST["parametro"];
$valor     = utf8_decode(trim($_REQUEST["valor"]));

if(isset($_REQUEST["parametro"]) && $_REQUEST["parametro"] == "cnpj"){
	$cnpj_posto = $_REQUEST["valor"];
	if (strpos($cnpj_posto, "/") AND strpos($cnpj_posto, "-") AND strpos($cnpj_posto, ".")) {
		$cnpj_posto = str_replace("/", "", $cnpj_posto);
		$cnpj_posto = str_replace("-", "", $cnpj_posto);
		$cnpj_posto = str_replace(".", "", $cnpj_posto);
	}
}

if(isset($_REQUEST["parametro"]) && $_REQUEST["parametro"] == "cep"){
	$cep_posto = $_REQUEST["valor"];
	if (strpos($cep_posto, "-") OR strpos($cep_posto, ".")) {
		$cep_posto = str_replace("-", "", $cep_posto);
		$cep_posto = str_replace(".", "", $cep_posto);
	}
}

if(isset($_REQUEST["parametro"]) && $_REQUEST["parametro"] == "codigo_representante"){
	$codigo_representante = $_REQUEST["codigo"];
	$tipo = "representante";
}

if(isset($_REQUEST["parametro"]) && $_REQUEST["parametro"] == "descricao_representante"){
	$descricao_representante = $_REQUEST["nome"];
	$tipo = "representante";
}

if ($login_fabrica == 183){
	$page = $_REQUEST["page"];

	if ($login_tipo_posto_codigo == "Rep"){
		$sql = "SELECT representante FROM tbl_representante WHERE fabrica = {$login_fabrica} AND cnpj = '{$login_cnpj}'";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0){
			$id_representante = pg_fetch_result($res, 0, "representante");
			$cond_rep_posto = "
				JOIN tbl_posto_fabrica_representante ON tbl_posto_fabrica_representante.posto = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica_representante.representante = {$id_representante}
			";
		}
	}

	if ($page == "relatorio_cliente_representante"){
		$joinTipoPosto = " JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica} AND tbl_tipo_posto.codigo = 'Rep' ";
	}
}

function limpaString ($string){
	$stringLimpa = str_replace("'", "", $string);
	return $stringLimpa;
}

if (preg_match("/\/admin_cliente\//", $_SERVER["PHP_SELF"])) {
	$aux_url = '../';
} else {
	$aux_url = '';
}
?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="<?=$aux_url;?>bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="<?=$aux_url;?>bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="<?=$aux_url;?>css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="<?=$aux_url;?>bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="<?=$aux_url;?>plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="<?=$aux_url;?>plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="<?=$aux_url;?>bootstrap/js/bootstrap.js"></script>
		<script src="<?=$aux_url;?>plugins/dataTable.js"></script>
		<script src="<?=$aux_url;?>plugins/resize.js"></script>
		<script src="<?=$aux_url;?>plugins/shadowbox_lupa/lupa.js"></script>

		<script>
			$(function () {
				$.dataTableLupa();
			});
		</script>
	</head>

	<body>
		<div id="container_lupa" style="overflow-y:auto;">
			<div id="topo">
				<img class="espaco" src="<?=$aux_url;?>imagens/logo_new_telecontrol.png">
				<img class="lupa_img pull-right" src="<?=$aux_url;?>imagens/lupa_new.png">
			</div>
			<br /><hr />
			<div class="row-fluid">
			<form action="<?=$_SERVER['PHP_SELF']?>" method='POST' >
				<div class="span1"></div>
				<div class="span4">
					<input type="hidden" name="completo" value='<?=$completo?>' />
					<? if (isset($locadora_revenda)) { ?>
						<input type="hidden" name="locadora-revenda" value='<?=$locadora_revenda?>' />
					<? } ?>
					<? if (isset($matriz)) { ?>
						<input type="hidden" name="matriz" value='<?= $matriz; ?>' />
					<? } ?>
					<?php if (!empty($refe_roteiro)) { ?>
						<input type="hidden" name="refe" value='<?= $refe_roteiro; ?>' />
					<?php } ?>
					<select name="parametro" >
						<option value="codigo" <?=($parametro == "codigo") ? "SELECTED" : ""?> ><?=traduz("Código")?></option>
						<option value="nome" <?=($parametro == "nome") ? "SELECTED" : ""?> ><?=traduz("Nome")?></option>
						<?php if (!empty($refe_roteiro)) { ?>
						<option value="cidade" <?=($parametro == "cidade") ? "SELECTED" : ""?> ><?=traduz("Cidade")?></option>
						<?php } ?>
					</select>
				</div>
				<div class="span4">
					<input type="text" name="valor" class="span12" value="<?=$valor?>" />
				</div>
				<div class="span2">
					<button type="button" class="btn pull-right" onclick="$(this).parents('form').submit();"><?=traduz("Pesquisar")?></button>
				</div>
			</form>
			</div>
			<?php
			if (strlen($valor) >= 3 ) {
				switch ($parametro) {
					case 'codigo':
						if (!empty($refe_roteiro)) {
							$valor = str_replace(array(".", ",", "-", "/"), "", $valor);
							$whereAdc = " tbl_posto.cnpj = '$valor'";
							break;
						} else {							
							$valor = str_replace(array(".", ",", "-", "/"), "", $valor);
							$whereAdc = "UPPER(tbl_posto_fabrica.codigo_posto) ILIKE UPPER('%{$valor}%')";
							break;
						}

					case 'nome':
						if ($_REQUEST["telecontrol"] == "t") {
							$whereAdc = "tbl_posto.nome ILIKE '%{$valor}%'";
						} else {
							$whereAdc = "(UPPER(tbl_posto.nome) ILIKE UPPER('%{$valor}%') OR UPPER(tbl_posto_fabrica.nome_fantasia) ILIKE UPPER('%{$valor}%') OR UPPER(tbl_posto.nome_fantasia) ILIKE UPPER('%{$valor}%'))";
						}
						break;

					case 'codigo_nome':
						$whereAdc = "
							(UPPER(tbl_posto_fabrica.codigo_posto) ILIKE UPPER('%{$valor}%')
							OR
							(UPPER(tbl_posto.nome) ILIKE UPPER('%{$valor}%') OR UPPER(tbl_posto_fabrica.nome_fantasia) ILIKE UPPER('%{$valor}%') OR UPPER(tbl_posto.nome_fantasia) ILIKE UPPER('%{$valor}%')))
						";
						break;
					case 'cidade':
						$whereAdc = "
							(UPPER(tbl_posto_fabrica.contato_cidade) ILIKE UPPER('%{$valor}%'))";
						break;
				}

				if(isset($cnpj_posto)){
					$whereAdc = " tbl_posto.cnpj = '{$cnpj_posto}' ";
				}
				if(isset($cep_posto)){
					$whereAdc = " tbl_posto_cep_atendimento.cep_inicial = '{$cep_posto}' ";
					$joinPosto = "JOIN tbl_posto_cep_atendimento ON tbl_posto.posto = tbl_posto_cep_atendimento.posto ";
				}

				if ($tipo == 'representante') {
					if ($parametro == "codigo_representante") {
						$whereAdc = " codigo ilike '%$valor%'";
					} else if ($parametro == "descricao_representante") {
						$whereAdc = " nome ilike '%$valor%'";
					}
				}

				if (isset($whereAdc)) {
					if (!empty($completo) || $login_fabrica == 7) {
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
					if ($login_fabrica != 117) {
						$filterByLocation = "";
						if(!empty($_REQUEST["estado"])){
							$filterByLocation = " AND tbl_posto_fabrica.contato_estado = '" . $_REQUEST["estado"] . "'";
						}
						if(!empty($_REQUEST["cidade"])){
							$filterByLocation = " AND tbl_posto_fabrica.cod_ibge = " . $_REQUEST["cidade"];
						}
					}

					if (isset($locadora_revenda) && $login_fabrica == 148) {
						$distinctPosto = "DISTINCT";
						$joinTipoPosto = "
							INNER JOIN tbl_posto_tipo_posto ON tbl_posto_tipo_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_tipo_posto.fabrica = {$login_fabrica}
							INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
						";
						$whereTipoPosto = "
							AND (tbl_tipo_posto.locadora IS TRUE OR tbl_tipo_posto.tipo_revenda IS TRUE)
						";
					}

					if (in_array($login_fabrica, array(157,163))) {
						$distinctPosto = "DISTINCT tbl_tipo_posto.posto_interno ,";
						$joinTipoPosto = "
							LEFT JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
						";
						$whereTipoPosto = "
							AND (tbl_tipo_posto.locadora IS TRUE OR tbl_tipo_posto.tipo_revenda IS TRUE)
						";
					}

					if (in_array($login_fabrica, array(173,174))) {
						$distinctPosto = "DISTINCT tbl_tipo_posto.posto_interno ,";
						$joinTipoPosto = "
							LEFT JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
						";
					}

					if($login_fabrica == 161){
						$camposAdc = " ,tbl_posto.suframa ";
					}

					if ($login_fabrica == 183){
						$camposAdc = ", tbl_posto_fabrica.contato_email, tbl_posto_fabrica.contato_numero, tbl_posto_fabrica.contato_fone_comercial, tbl_posto_fabrica.contato_complemento";
					}

					if ($matriz == 't') {
						$whereMatriz = "AND JSON_FIELD('matriz', tbl_posto_fabrica.parametros_adicionais) = 't'";
					} else if ($matriz == 'f') {
						$whereMatriz = "AND (JSON_FIELD('matriz', tbl_posto_fabrica.parametros_adicionais) != 't' OR JSON_FIELD('matriz', tbl_posto_fabrica.parametros_adicionais) IS NULL)";
					}

					if(!$area_admin) {
						$cond = " AND tbl_posto_fabrica.credenciamento != 'DESCREDENCIADO' ";
					}

					if($login_fabrica == 42){
						$cond_credenciado_makita = " AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO' ";
					}

					if ($_REQUEST["telecontrol"] == "t") {
						$sql = "SELECT
								tbl_posto.posto,
								tbl_posto.cnpj,
								tbl_posto.pais,
								fn_retira_especiais(tbl_posto.nome) nome,
								tbl_posto.ie
								FROM tbl_posto
								WHERE {$whereAdc}
								ORDER BY tbl_posto.nome";
					} else {
						$sql = "SELECT
								{$distinctPosto} tbl_posto.posto,
							tbl_posto.cnpj,
							fn_retira_especiais(tbl_posto.nome) AS nome,
							tbl_posto.ie,
							tbl_posto.pais,
							tbl_posto_fabrica.contato_endereco AS endereco,
							tbl_posto_fabrica.contato_numero AS numero,
							tbl_posto_fabrica.contato_bairro AS bairro,
							tbl_posto_fabrica.contato_cidade AS cidade,
							tbl_posto_fabrica.contato_estado AS estado,
							tbl_posto_fabrica.contato_cep AS cep,
							tbl_posto_fabrica.codigo_posto,
                            tbl_posto_fabrica.tipo_posto,
                            tbl_posto_fabrica.desconto,
							tbl_posto_fabrica.controla_estoque,
							tbl_posto_fabrica.credenciamento,
							tbl_posto_fabrica.parametros_adicionais,
							tbl_posto_fabrica.cod_ibge,
							tbl_posto_fabrica.latitude,
							tbl_posto_fabrica.longitude
							{$camposAdc}
						FROM tbl_posto
						JOIN tbl_posto_fabrica USING (posto)
						{$joinTipoPosto}
						{$joinPosto}
						{$cond_rep_posto}
						WHERE {$whereAdc}
						{$filterByLocation}
						AND tbl_posto_fabrica.fabrica = {$login_fabrica}
						{$whereMatriz}
						{$cond}
						{$cond_credenciado_makita}
						ORDER BY nome";
					}
						
					if ($parametro == "codigo_representante") {
						$sql = "SELECT * FROM tbl_representante WHERE $whereAdc";
					} else if ($parametro == "descricao_representante") {
						$sql = "SELECT * FROM tbl_representante WHERE $whereAdc";
					}
					//die(nl2br($sql));
					$res = pg_query($con, $sql);
					$rows = pg_num_rows($res);
					
					if ($rows > 0) {
					?>
						<div id="border_table">
						<table class="table table-striped table-bordered table-hover table-lupa" >
							<thead>
								<tr class='titulo_coluna'>
							<?php
								if ($tipo == "representante") {
							?>
									<th><?=traduz("Nome")?></th>
									<th><?=traduz("CNPJ")?></th>
									<th><?=traduz("Cidade")?></th>
									<th><?=traduz("UF")?></th>
							<? } else { ?>
									<th><?=traduz("Código")?></th>
									<th><?=traduz("CNPJ")?></th>
									<th><?=traduz("Nome")?></th>
									<th><?=traduz("Cidade")?></th>
									<th><?=traduz("Estado")?></th>
							<?	} ?>
								</tr>
							</thead>
							<tbody>
								<?php
								for ($i = 0 ; $i < $rows; $i++) {
									$credenciamento   = pg_fetch_result($res, $i, "credenciamento");
									$codigo_posto     = pg_fetch_result($res, $i, "codigo_posto");
									$tipo_posto       = pg_fetch_result($res, $i, "tipo_posto");
									$posto            = pg_fetch_result($res, $i, "posto");
									$nome             = str_replace("'", "\'", pg_fetch_result($res, $i, "nome"));
									$cnpj             = pg_fetch_result($res, $i, "cnpj");
									$pais_posto       = pg_fetch_result($res, $i, "pais");
									$endereco         = pg_fetch_result($res, $i, "endereco");
									$numero           = pg_fetch_result($res, $i, "numero");
									$bairro           = pg_fetch_result($res, $i, "bairro");
									$cidade           = pg_fetch_result($res, $i, "cidade");
									$estado           = pg_fetch_result($res, $i, "estado");
									$cep              = pg_fetch_result($res, $i, "cep");
									$latitude         = pg_fetch_result($res, $i, "latitude");
									$longitude        = pg_fetch_result($res, $i, "longitude");
									$controla_estoque = pg_fetch_result($res, $i, "controla_estoque");
									$ie 			  = pg_fetch_result($res, $i, "ie");
									$cod_ibge         = pg_fetch_result($res, $i, cod_ibge);
									$pais_posto       = pg_fetch_result($res, $i, "pais");

									if ($tipo == "representante") {
										$representante = trim(pg_result($res, $i, 'representante'));
										$codigo        = trim(pg_result($res, $i, 'codigo'));
										$nome          = trim(pg_result($res, $i, 'nome'));
										$cnpj          = trim(pg_result($res, $i, 'cnpj'));
										$cidade        = trim(pg_result($res, $i, 'cidade'));
										$estado        = trim(pg_result($res, $i, 'estado'));
									}

									if (!empty($completo) || $login_fabrica == 7) {
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

									if ($tipo == "representante") {
										$r = array(
											"codigo"           => $codigo,
											"nome"             => limpaString(utf8_encode($nome)),
											"representante"    => $representante
										);
									} else {
										$r = array(
											"posto"            => $posto,
											"nome"             => limpaString(utf8_encode($nome)),
											"codigo"           => $codigo_posto,
											"cnpj"             => $cnpj,
											"pais"             => $pais_posto,
											"latitude"         => $latitude,
											"longitude"        => $longitude,
											"controla_estoque" => (($controla_estoque == "t") ? true : false),
											"estado"           => $estado,
											"cidade"           => utf8_encode($cidade),
                                            "cep"              => $cep,
											"cod_ibge"         => $cod_ibge,
											"ie"			   => $ie,
											"pais"             => $pais_posto
										);

										if (strlen($posicao) > 0) {
											$r["posicao"] = $posicao;
										}
										if (in_array($login_fabrica, array(157,163,173,174))) {
											$posto_interno         = pg_fetch_result($res,$i,'posto_interno');
											$r["posto_interno"] = ($posto_interno == "t") ? true : false;
										}

										if($login_fabrica == 161){
											$r["suframa"] = pg_fetch_result($res, $i, "suframa");
										}

										if($login_fabrica == 157){
											$desconto = (strlen(pg_fetch_result($res, $i, "desconto")) > 0) ? pg_fetch_result($res, $i, "desconto") : 0;
											$r["desconto"] = $desconto;
										}

										if (in_array($login_fabrica, array(169,170))){
											$dados_tecnicos = array();

											if ($cadastra_tecnico_admin == "true"){
												$sql_tecnico = "
													SELECT tecnico, nome
													FROM tbl_tecnico
													WHERE posto = {$posto}
													AND fabrica = {$login_fabrica}
													AND tbl_tecnico.ativo IS TRUE ";
												$res_tecnico = pg_query($con, $sql_tecnico);

												if(pg_num_rows($res_tecnico) > 0){
													$tecnicos = pg_fetch_all($res_tecnico);
													foreach ($tecnicos as $info_tecnico) {
														$dados_tecnicos[] = array(
															'tecnico' => $info_tecnico['tecnico'],
															'nome' => $info_tecnico['nome'],
														);
													}
													
												}
											}
											$r["dados_tecnicos"] = $dados_tecnicos;
											$r["cadastra_tecnico_admin"] = $cadastra_tecnico_admin;
										}

										if ($login_fabrica == 183){
											$contato_email 			= pg_fetch_result($res, $i, 'contato_email');
											$contato_numero 		= pg_fetch_result($res, $i, 'contato_numero');
											$contato_fone_comercial = pg_fetch_result($res, $i, 'contato_fone_comercial');
											$contato_complemento 	= pg_fetch_result($res, $i, 'contato_complemento');

											$r["contato_email"] 		 = $contato_email;
											$r["contato_numero"]		 = $contato_numero;
											$r["contato_fone_comercial"] = $contato_fone_comercial;
											$r["contato_complemento"] 	 = $contato_complemento;
										}

										if ($tipo_posto_multiplo) {

											$sql = "SELECT
														tbl_tipo_posto.tipo_posto,
														tbl_tipo_posto.descricao,
														tbl_tipo_posto.locadora,
														tbl_tipo_posto.tipo_revenda,
														tbl_tipo_posto.montadora,
														tbl_tipo_posto.posto_interno,
														tbl_tipo_posto.tecnico_proprio
													FROM tbl_posto_tipo_posto
													JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
													WHERE
														tbl_posto_tipo_posto.fabrica = {$login_fabrica}
														AND tbl_posto_tipo_posto.posto = {$posto}";
										} else {
											$sql = "SELECT * FROM tbl_tipo_posto WHERE tipo_posto = $tipo_posto";
										}

										$resTipoPosto = pg_query($con, $sql);

										if(pg_num_rows($resTipoPosto) > 0){
											$tipos_postos = array();

											$tipos = pg_fetch_all($resTipoPosto);
											foreach ($tipos as $info_tipo) {
												$tipos_postos[] = array(
													'tipo_posto'      => $info_tipo['tipo_posto'],
													'descricao'       => utf8_encode(retira_acentos($info_tipo['descricao'])),
													'locadora'        => ($info_tipo['locadora']      == 't'),
													'tipo_revenda'    => ($info_tipo['tipo_revenda']  == 't'),
													'montadora'       => ($info_tipo['montadora']     == 't'),
													'posto_interno'   => ($info_tipo['posto_interno'] == 't'),
													'tecnico_proprio' => ($info_tipo['tecnico_proprio'] == 't')
												);
											}
											$r["tipos_postos"] = $tipos_postos;
										}

									}
									$r = array_map_recursive('utf8_encode', $r);
									if ($tipo == "representante") {
										echo "<tr onclick='window.parent.retorna_representante(".json_encode($r)."); window.parent.Shadowbox.close();' >";
									} else {
										echo "<tr onclick='window.parent.retorna_posto(".json_encode($r)."); window.parent.Shadowbox.close();' >";
									}

										if ($tipo == "representante") {
											echo "<td><a class='cursor_lupa'>$codigo - $nome</a></td>";
											echo "<td class='cursor_lupa'>$cnpj</td>";
											echo "<td><a class='cursor_lupa'>$cidade</a></td>";
											echo "<td><a class='cursor_lupa'>$estado</a></td>";
										} else {
											echo "<td class='cursor_lupa'>{$codigo_posto}</td>";
											echo "<td class='cursor_lupa'>{$cnpj}</td>";
											echo "<td class='cursor_lupa'>{$nome}</td>";
											echo "<td class='cursor_lupa'>{$cidade}</td>";
											echo "<td class='cursor_lupa'>{$estado}</td>";
											echo "</tr>";
										}
								}
								?>
							</tbody>
						</table>
						</div>
					<?php
					} else {
						echo '
						<div class="alert alert_shadobox">
							<h4>'.traduz("Nenhum resultado encontrado").'</h4>
						</div>';
					}
				}
			} else {
				echo '
					<div class="alert alert_shadobox">
						<h4>'.traduz("Informe toda ou parte da informação para pesquisar!").'</h4>
					</div>';
				}
		?>
	</div>
	</body>
</html>
