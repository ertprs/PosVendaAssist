<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$areaClienteAdmin = false;
$areaAdminRepresentante = false;

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {		
	include 'autentica_admin.php';
	$areaAdmin = true;
} elseif (preg_match("/\/admin_es\//", $_SERVER["PHP_SELF"])) {
	include "../fn_traducao.php";
	include 'autentica_admin.php';
	$areaAdmin = true;
} elseif (preg_match("/\/admin_representante\//", $_SERVER["PHP_SELF"])) {
	include "../fn_traducao.php";
	include 'autentica_admin.php';
	$areaAdminRepresentante = true;
}  elseif (preg_match("/\/admin_cliente\//", $_SERVER["PHP_SELF"])) {
	include "../fn_traducao.php";
	include 'autentica_admin.php';
	$areaClienteAdmin = true;
} else {
	include 'autentica_usuario.php';
	$areaAdmin = false;
}


function retira_acentos( $texto ){
    $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@", "'" );
    $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_","" );
    return str_replace( $array1, $array2, $texto );
}


if ($_REQUEST["parametro"]) {
	$parametro   = $_REQUEST["parametro"];
}

if ($_REQUEST["valor"]) {
	$valor = $_REQUEST["valor"];
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
				var option = {
						"sDom": "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
						"bInfo": false,
						"bFilter": false,
						"bLengthChange": false,
						"bPaginate": false,
						"aaSorting": [[2, "desc" ]]
					};
				$.dataTableLupa(option);

				$('#resultados').on('click', '.cliente-item', function() {
					var info = JSON.parse($(this).attr('data-cliente'));
					if (typeof(info) == 'object') {
						window.parent.retorna_cliente(info);
						window.parent.Shadowbox.close();
					}
				});
			});
		</script>
	</head>

	<body>
		<div id="container_lupa" style="overflow-y:auto;z-index:1">
			<div id="topo">
				<img class="espaco" src="imagens/logo_new_telecontrol.png">
				<img class="lupa_img pull-right" src="imagens/lupa_new.png">
			</div>
			<br /><hr />
			<div class="row-fluid">
				<form action="<?=$_SERVER['PHP_SELF']?>" method='POST' >

				<div class="span1"></div>
					<div class="span4">
						<select name="parametro">
							<?php if ($login_fabrica == 190) {?>
							<option value="cpf" <?=($parametro == "cpf") ? "SELECTED" : ""?> >CPF</option>
							<?php } else {?>
							<option value="codigo" <?=($parametro == "codigo") ? "SELECTED" : ""?> ><?= traduz('Código') ?></option>
							<?php }?>
							<option value="nome"  <?=($parametro == "nome")  ? "SELECTED" : ""?> ><?= traduz('Nome') ?></option>
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
				case 'cpf':
					$valor = str_replace(array(".", ",", "-", "/", " "), "", $valor);
					$whereAdc = " AND cnpj ILIKE '%{$valor}%'";
					break;
				case 'nome':
                    $whereAdc = " AND fn_retira_especiais(nome) ILIKE '%' || fn_retira_especiais('$valor') || '%'";
					break;
			}
			$sql = "
				SELECT *
				  FROM tbl_cliente_admin
				 WHERE fabrica= {$login_fabrica}
				  {$whereAdc};";
            $res = pg_query($con, $sql);
			if (pg_num_rows($res) > 0) {
				
			?>
			<div id="border_table">
				<table id="resultados" class="table table-striped table-bordered table-hover table-lupa" >
					<thead>
						<tr class='titulo_coluna'>
							<th><?= traduz('Código') ?></th>
							<th><?= traduz('Nome') ?></th>
							<th><?= traduz('CPF / CNPJ') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php for ($i = 0 ; $i < pg_num_rows($res); $i++) {

							$cliente_admin	= pg_fetch_result($res, $i, 'cliente_admin');
							$codigo			= pg_fetch_result($res, $i, 'codigo');
							$nome			= pg_fetch_result($res, $i, 'nome');
							$cnpj 			= pg_fetch_result($res, $i, 'cnpj');
							$endereco 			= pg_fetch_result($res, $i, 'endereco');
							$numero 			= pg_fetch_result($res, $i, 'numero');
							$cep 			= pg_fetch_result($res, $i, 'cep');
							$bairro 			= pg_fetch_result($res, $i, 'bairro');
							$complemento 			= pg_fetch_result($res, $i, 'complemento');
							$cidade 			= pg_fetch_result($res, $i, 'cidade');
							$estado 			= pg_fetch_result($res, $i, 'estado');
							$email 			= pg_fetch_result($res, $i, 'email');
							$fone 			= pg_fetch_result($res, $i, 'fone');
							$celular 			= pg_fetch_result($res, $i, 'celular');
							$r = array(
								"cliente_admin"      => $cliente_admin,
								"endereco"    => utf8_encode($endereco),
								"numero"    => utf8_encode($numero),
								"cep"    => utf8_encode($cep),
								"complemento"    => utf8_encode($complemento),
								"cidade"    => utf8_encode($cidade),
								"estado"    => utf8_encode($estado),
								"email"    => utf8_encode($email),
								"fone"    => utf8_encode($fone),
								"celular"    => utf8_encode($celular),
								"bairro"    => utf8_encode($bairro),
								"nome"    => utf8_encode($nome),
								"cnpj"   => $cnpj
							);

							$r = array_map('utf8_encode',$r);

								echo "
								<tr class='cliente-item' data-cliente='".json_encode($r)."'>
										<td class='tac cursor_lupa'>{$codigo}</td>
										<td class='cursor_lupa'>{$nome}</td>
										<td class='tac cursor_lupa'>{$cnpj}</td>
								</tr>";

							}
						} else {
							echo '<div class="alert alert_shadobox"><h4>'.traduz('Nenhum cliente encontrado').'</h4></div>';

						}
						echo "
					</tbody>
				</table>";

			}  else {
				echo '<div class="alert alert_shadobox"><h4>'.traduz('Informe toda ou parte da informação para pesquisar!').'</h4></div>';
			}
		?>
			</div>
		</div>
	</body>
</html>
