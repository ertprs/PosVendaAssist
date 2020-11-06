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
}  else {
	include 'autentica_usuario.php';
	include "funcoes.php";
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
					<select name="parametro" >
						<option value="cnpj" <?=($parametro == "cnpj") ? "SELECTED" : ""?> ><?=traduz("CNPJ")?></option>
						<option value="nome" <?=($parametro == "nome") ? "SELECTED" : ""?> ><?=traduz("Nome")?></option>
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
				switch ($parametro) {
					case 'cnpj':
						$valor = str_replace(array(".", ",", "-", "/"), "", $valor);
						$whereAdc = " AND cnpj = '$valor'";
						break;
					case 'nome':
						$whereAdc = " AND (UPPER(nome) ILIKE UPPER('%{$valor}%'))";
						break;
				}
				$sql = "SELECT * 
						  FROM tbl_representante
						 WHERE fabrica = {$login_fabrica}
						      {$whereAdc}
					  ORDER BY nome";
				
				$res = pg_query($con, $sql);


				if (pg_num_rows($res) > 0) {
			?>
						<table class="table table-striped table-bordered table-hover table-lupa" >
							<thead>
								<tr class='titulo_coluna'>
									<th><?=traduz("Nome")?></th>
									<th><?=traduz("CNPJ")?></th>
									<th><?=traduz("Cidade")?></th>
									<th><?=traduz("UF")?></th>
								</tr>
							</thead>
							<tbody>
							<?php
								for ($i = 0 ; $i < pg_num_rows($res); $i++) {
									$nome             = str_replace("'", "\'", pg_fetch_result($res, $i, "nome"));
									$representante             = pg_fetch_result($res, $i, "representante");
									$cnpj             = pg_fetch_result($res, $i, "cnpj");
									$cidade           = pg_fetch_result($res, $i, "cidade");
									$estado           = pg_fetch_result($res, $i, "estado");
									$codigo           = pg_fetch_result($res, $i, "codigo");
									
									$r = array(
										"nome"             => limpaString(utf8_encode($nome)),
										"representante"             => $representante,
										"cnpj"             => $cnpj,
										"estado"           => $estado,
										"cidade"           => utf8_encode($cidade),
									);

									$r = array_map_recursive('utf8_encode', $r);
									echo "
									<tr style='cursor:pointer' onclick='window.parent.retorna_representante(".json_encode($r)."); window.parent.Shadowbox.close();' >
										<td>$codigo - $nome</td>
										<td>$cnpj</td>
										<td>$cidade</td>
										<td>$estado</td>
									<tr>";
								}
							?>
							</tbody>
						</table>
					<?php
					} else {
						echo '
						<div class="alert alert_shadobox">
							<h4>'.traduz("Nenhum resultado encontrado").'</h4>
						</div>';
					}
		?>
	</div>
	</body>
</html>
