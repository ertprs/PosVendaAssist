<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}

$contador_ver = "0";


$parametro = $_REQUEST["parametro"];
$extratifica = $_REQUEST["extratifica"];
$valor     = utf8_decode(trim($_REQUEST["valor"]));
$cnpjNotNull = isset($_REQUEST['cnpj_not_null'])?$_REQUEST['cnpj_not_null']:false;

$usa_rev_fabrica = in_array($login_fabrica, array(3,24,117,184,191,200)); /*HD-3992758 Retirada a fábrica 15*/

if ($extratifica == true) {
	$valor_extra = $valor = str_replace(array(".", ",", "-", "/"), "", $valor);
	$valor_extra = substr($valor_extra, 0,3);
	if (is_numeric($valor_extra)) {
		$parametro = 'cnpj';
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

					<?php
						if($cnpjNotNull):
					?>
						<input type="hidden" name="cnpj_not_null" class="span12" value='<?=$cnpjNotNull?>' />
					<?php
						endif;
					?>

					<select name="parametro"  >
						<option value="cnpj" <?=($parametro == "cnpj") ? "SELECTED" : ""?> ><?= traduz('CNPJ') ?></option>
						<option value="razao_social" <?=($parametro == "razao_social") ? "SELECTED" : ""?> ><?= traduz('Razão Social') ?></option>
					</select>
				</div>
				<div class="span4">
					<input type="text" name="valor" class="span12" value="<?=$valor?>" />
				</div>
				<div class="span2">
					<button type="button" class="btn pull-right" onclick="$(this).parents('form').submit();"><?= traduz('Pesquisar') ?></button>
				</div>
				<div class="span1"></div>
			</form>
		</div>




<?php
		$msg_confirma = "0";

		if (strlen($valor) >= 3) {			

			switch ($parametro) {
				case 'cnpj':
					$valor = str_replace(array(".", ",", "-", "/"), "", $valor);

					if ($usa_rev_fabrica) {
						$whereAdc = " WHERE cnpj ~* '^$valor' ";
					} else {
						$whereAdc = " WHERE tbl_revenda.cnpj ~* '^$valor' ";
					}
					break;

				case 'razao_social':
				    $cond = '^' . $valor;

					if ($login_fabrica == 117 OR $usa_rev_fabrica) {
						$cond = $valor;
					}

					if ($usa_rev_fabrica) {
						$whereAdc = " WHERE contato_razao_social ~* '$cond' ";
					} else {
						$whereAdc = " WHERE tbl_revenda.nome ~* '$cond' ";
					}
					break;
			}

			if($cnpjNotNull)
				$whereAdc.= ' AND tbl_revenda.cnpj IS NOT NULL ';

			if($login_fabrica != 50 AND !$usa_rev_fabrica)
				$whereAdc.= ' AND tbl_revenda.cnpj_validado IS TRUE ';

			if ($login_fabrica == 52) {
				$join_tbl_cliente_admin = " JOIN tbl_cliente_admin ON tbl_cliente_admin.fabrica = $login_fabrica AND tbl_cliente_admin.cnpj = tbl_revenda.cnpj ";
			} else {
				$join_tbl_cliente_admin = "";
			}

			$sql = "SELECT  tbl_revenda.revenda,
							tbl_revenda.nome,
							tbl_revenda.cnpj                ,
			                tbl_revenda.endereco     ,
						    tbl_revenda.numero       ,
						    tbl_revenda.complemento  ,
						    tbl_revenda.bairro       ,
						    tbl_revenda.cep          ,
						    tbl_cidade.estado				        ,
						    tbl_cidade.nome AS cidade_nome           ,
						    tbl_cidade.cidade AS cidade          ,
						    tbl_revenda.fone         ,
						    tbl_revenda.ie                   ,
						    tbl_revenda.contato         ,
						    tbl_revenda.email        ,
						    tbl_revenda.fax          ,
						    null AS contato_nome_fantasia,
						    null AS contato_cidade
			FROM     tbl_revenda
			{$join_tbl_cliente_admin}
			LEFT JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade
			$whereAdc			
			ORDER BY tbl_revenda.nome";

		    if ($usa_rev_fabrica) {
		    	$sql = "SELECT
							tbl_revenda_fabrica.revenda,
							tbl_revenda_fabrica.contato_razao_social AS nome,
							tbl_revenda_fabrica.cnpj                ,
			                tbl_revenda_fabrica.contato_endereco AS endereco    ,
						    tbl_revenda_fabrica.contato_numero  AS numero     ,
						    tbl_revenda_fabrica.contato_complemento AS complemento ,
						    tbl_revenda_fabrica.contato_bairro AS bairro      ,
						    tbl_revenda_fabrica.contato_cep  AS cep        ,
						    tbl_cidade.estado				        ,
						    tbl_cidade.nome AS cidade_nome           ,
						    tbl_cidade.cidade AS cidade 			,
						    tbl_revenda_fabrica.contato_fone AS fone        ,
						    tbl_revenda_fabrica.ie                   ,
						    tbl_revenda_fabrica.contato_nome  AS contato       ,
						    tbl_revenda_fabrica.contato_email AS email       ,
						    tbl_revenda_fabrica.contato_fax  AS fax          ,
						    tbl_revenda_fabrica.contato_nome_fantasia,
						    tbl_revenda_fabrica.contato_cidade
					FROM tbl_revenda_fabrica
					LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
					$whereAdc
					AND tbl_revenda_fabrica.fabrica = $login_fabrica
					ORDER BY tbl_cidade.estado, tbl_cidade.cidade, contato_razao_social";
			}
			$res = pg_query($con, $sql);
			$rows = pg_num_rows($res);
			if ($rows > 0) {
			?>

			<div id="border_table">
				<table class="table table-striped table-bordered table-hover table-lupa" >
					<thead>
						<tr class='titulo_coluna'>
							<th><?= traduz('CNPJ') ?></th>
							<th><?= traduz('Nome') ?></th>
							<th><?= traduz('Cidade') ?></th>
							<th>UF</th>
						</tr>
					</thead>
					<tbody>
						<?php
						for ($i = 0 ; $i < $rows; $i++) {
							$resultado[$i]["revenda_fabrica"] 	= utf8_encode(pg_fetch_result($res, $i, 'revenda'));
							$resultado[$i]["razao"]         	= utf8_encode(pg_fetch_result($res, $i, 'nome'));
							$resultado[$i]["desc"] 				= $resultado[$i]["razao"];
							$resultado[$i]["cnpj"]          	= utf8_encode(pg_fetch_result($res, $i, 'cnpj'));
							$resultado[$i]["cod"] 				= $resultado[$i]["cnpj"];
							$resultado[$i]["endereco"]       	= utf8_encode(pg_fetch_result($res, $i, 'endereco'));
							$resultado[$i]["numero"]        	= utf8_encode(pg_fetch_result($res, $i, 'numero'));
							$resultado[$i]["complemento"]   	= utf8_encode(pg_fetch_result($res, $i, 'complemento'));
							$resultado[$i]["bairro"]        	= utf8_encode(pg_fetch_result($res, $i, 'bairro'));
							$resultado[$i]["cep"]           	= utf8_encode(pg_fetch_result($res, $i, 'cep'));
							$resultado[$i]["estado"]        	= utf8_encode(pg_fetch_result($res, $i, 'estado'));
							$resultado[$i]["cidade"]        	= utf8_encode(pg_fetch_result($res, $i, 'cidade'));
							$resultado[$i]["cidade_nome"]      	= (pg_fetch_result($res, $i, 'cidade_nome'));
							$resultado[$i]["fone"]          	= utf8_encode(pg_fetch_result($res, $i, 'fone'));
							$resultado[$i]["ie"]            	= utf8_encode(pg_fetch_result($res, $i, 'ie'));
							$resultado[$i]["contato"]       	= utf8_encode(pg_fetch_result($res, $i, 'nome'));
							$resultado[$i]["email"]         	= utf8_encode(pg_fetch_result($res, $i, 'email'));
							$resultado[$i]["fax"]           	= utf8_encode(pg_fetch_result($res, $i, 'fax'));
							$resultado[$i]["nome_fantasia"] 	= utf8_encode(pg_fetch_result($res, $i, 'contato_nome_fantasia'));

							$r = $resultado[$i];

							echo "<tr onclick='window.parent.retorna_revenda(".json_encode($r)."); window.parent.Shadowbox.close();' >";
								echo "<td class='cursor_lupa'>{$resultado[$i]['cnpj']}</td>";
								echo "<td class='cursor_lupa'>{$resultado[$i]['razao']}</td>";
								echo "<td class='cursor_lupa'>{$resultado[$i]['cidade_nome']}</td>";
								echo "<td class='cursor_lupa'>{$resultado[$i]['estado']}</td>";
							echo "</tr>";
						}
					echo "</tbody>";
				echo "</table>";
			}else{
				echo '
				<div class="alert alert_shadobox">
					    <h4>'.traduz('Nenhum resultado encontrado').'</h4>
				</div>';

			}
		} else {
			echo '

				<div class="alert alert_shadobox">
				    <h4>'.traduz('Informe toda ou parte da informação para pesquisar!').'</h4>
				</div>';
		}

		?>

	</div>

	</body>
</html>

