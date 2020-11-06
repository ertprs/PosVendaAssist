<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}


// if($_REQUEST["completo"]){
// 	$completo = $_REQUEST["completo"];
// }

$parametro = $_REQUEST["parametro"];
$valor 	   = trim($_REQUEST["valor"]);
$posicao   = trim($_REQUEST["posicao"]);

if($_GET["valor"]){
	$valor = utf8_decode($valor);
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
			<form action="<?=$_SERVER['PHP_SELF']?>" method='POST'>
				<div class="span1"></div>
				<div class="span4">
					<input type="hidden" name="posicao" class="span12" value='<?=$posicao?>' />
					<input type="hidden" name="completo" class="span12" value='<?=$completo?>' />
					<select name="parametro" >
						<?php if ($login_fabrica == 189) {?>
						<option value="codigo" <?=($parametro == "codigo") ? "SELECTED" : ""?> >Código</option>
						<?php }?>
						<option value="cnpj" <?=($parametro == "cnpj") ? "SELECTED" : ""?> >CNPJ</option>
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
					case 'cnpj':
						$valor = str_replace(array(".", ",", "-", "/"), "", $valor);
						$whereAdc = "UPPER(tbl_transportadora.cnpj) ILIKE UPPER('%{$valor}%')";
						break;
					
					case 'codigo':
						$valor = str_replace(array(".", ",", "-", "/"), "", $valor);
						$whereAdc = "UPPER(tbl_transportadora_fabrica.codigo_interno) ILIKE UPPER('%{$valor}%')";
						break;
					
					case 'nome':
						$whereAdc = " (UPPER(tbl_transportadora.nome) ILIKE UPPER('%{$valor}%') OR UPPER(tbl_transportadora.fantasia) ILIKE UPPER('%{$valor}%'))";
						break;
				}

				if (isset($whereAdc)) {

					$sql = "
						SELECT
							tbl_transportadora.nome,
							tbl_transportadora.contato,
							tbl_transportadora.cnpj,
							tbl_transportadora.fantasia,
							tbl_transportadora.transportadora,
							tbl_transportadora.ie,
							tbl_transportadora.nome,
							tbl_transportadora_fabrica.codigo_interno,
							tbl_transportadora_fabrica.ativo,
							tbl_transportadora_fabrica.contato_email,
							tbl_transportadora_fabrica.contato_endereco,
							tbl_transportadora_fabrica.contato_cidade,
							tbl_transportadora_fabrica.contato_estado,
							tbl_transportadora_fabrica.contato_bairro,
							tbl_transportadora_fabrica.contato_cep,
							tbl_transportadora_fabrica.fone,
							tbl_transportadora_padrao.capital_interior,
							tbl_transportadora_padrao.valor_frete,
							tbl_transportadora_padrao.estado
						FROM tbl_transportadora
						LEFT JOIN tbl_transportadora_fabrica ON tbl_transportadora_fabrica.transportadora = tbl_transportadora.transportadora AND tbl_transportadora_fabrica.fabrica = {$login_fabrica}
						LEFT JOIN tbl_transportadora_padrao ON tbl_transportadora_padrao.transportadora = tbl_transportadora.transportadora
						WHERE {$whereAdc}
						
					";
					$res = pg_query($con,$sql);

					$rows = pg_num_rows($res);
					
					if ($rows > 0) { ?>

					<div id ="border_table">
						<table class="table table-striped table-bordered table-hover table-lupa" >
							<thead>
								<tr class="titulo_coluna">
									<th>Código</th>
									<th>CNPJ</th>
									<th>Nome</th>
								</tr>
							</thead>
							<tbody>
					<?
						for ($i = 0; $i < $rows; $i++){
							$nome             = pg_fetch_result($res, $i, "nome");
							$cnpj             = pg_fetch_result($res, $i, "cnpj");
							$fantasia         = pg_fetch_result($res, $i, "fantasia");
							$transportadora   = pg_fetch_result($res, $i, "transportadora");
							$ie 		  = pg_fetch_result($res, $i, "ie");
							$capital_interior = pg_fetch_result($res, $i, "capital_interior");
							$valor_frete      = pg_fetch_result($res, $i, "valor_frete");
							$estado           = pg_fetch_result($res, $i, "estado");
							$email 		  = pg_fetch_result($res, $i, "contato_email");
							$endereco         = pg_fetch_result($res, $i, "contato_endereco");
							$cidade           = pg_fetch_result($res, $i, "contato_cidade");
							$uf               = pg_fetch_result($res, $i, "contato_estado");
							$bairro           = pg_fetch_result($res, $i, "contato_bairro");
							$cep              = pg_fetch_result($res, $i, "contato_cep");
							$fone             = pg_fetch_result($res, $i, "fone");
							$codigo_interno   = pg_fetch_result($res, $i, "codigo_interno");
							$ativo            = pg_fetch_result($res, $i, "ativo");
							$contato            = pg_fetch_result($res, $i, "contato");
							
							$r = array(
								"transportadora"   => $transportadora,
								"nome"             => utf8_encode($nome),
								"cnpj"             => $cnpj,
								"ie"               => $ie,
								"codigo_interno"   => $codigo_interno,
								"fantasia"         => $fantasia,
								"ativo"            => $ativo,
								"estado"           => $estado,
								"capital_interior" => $capital_interior,
								"valor_frete"      => $valor_frete,
								"email" 	   => $email,
                            	"endereco" 	   => utf8_encode($endereco),
                            	"cidade" 	   => utf8_encode($cidade),
                            	"uf" 		   => $uf,
                            	"bairro" 	   => $bairro,
                            	"cep" 		   => $cep,
                            	"fone" 		   => $fone
							);

							if ($login_fabrica == 189) {
								$r["posicao"] = $posicao;
								$r["contato"] = $contato;
								$r["fone"] = $fone;
							}

							echo "<tr onclick='window.parent.retorna_transportadora(".json_encode($r)."); window.parent.Shadowbox.close();' >";
								echo "<td class='cursor_lupa'>{$codigo_interno}</td>";
								echo "<td class='cursor_lupa'>{$cnpj}</td>";
								echo "<td class='cursor_lupa'>{$nome}</td>";
							echo "</tr>";
						}
							?>
							</tbody>
						</table>
						</div>
					<?php

					}else{
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
