<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
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
} else {
	include 'autentica_usuario.php';
	$areaAdmin = false;
}


function retira_acentos( $texto ){
    $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@", "'" );
    $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_","" );
    return str_replace( $array1, $array2, $texto );
}

$contador_ver = "0";

if ($_REQUEST["posicao"]) {
	$posicao   = $_REQUEST["posicao"];
}

if ($_REQUEST["contrato_tabela"]) {
	$contrato_tabela   = $_REQUEST["contrato_tabela"];
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

				$('#resultados').on('click', '.produto-item', function() {
					var info = JSON.parse($(this).attr('data-produto'));
					if (typeof(info) == 'object') {
						window.parent.retorna_produto(info);
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
						<?php
							echo "<input type='hidden' name='posicao' value='$posicao' />";
							echo "<input type='hidden' name='contrato_tabela' value='$contrato_tabela' />";
						?>
						<select name="parametro">
							<option value="referencia" <?=($parametro == "referencia") ? "SELECTED" : ""?> ><?= traduz('Referência') ?></option>
							<option value="descricao"  <?=($parametro == "descricao")  ? "SELECTED" : ""?> ><?= traduz('Descrição') ?></option>
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
				case 'referencia':
					$valor = str_replace(array(".", ",", "-", "/", " "), "", $valor);
					$whereAdc = " AND (tbl_produto.referencia_pesquisa ILIKE '%{$valor}%' OR tbl_produto.referencia_fabrica ILIKE '%{$valor}%')";
					break;
				case 'descricao':
                    $whereAdc = " AND ( UPPER(fn_retira_especiais(tbl_produto.descricao)) LIKE UPPER('%' || fn_retira_especiais('$valor') || '%') OR
                                    UPPER(fn_retira_especiais(tbl_produto.nome_comercial)) LIKE UPPER('%' || fn_retira_especiais('{$valor}') || '%'))";
					break;
			}
			$sql = "
				SELECT tbl_produto.produto,
				tbl_produto.referencia,
				tbl_produto.descricao,
				tbl_familia.bosch_cfa,
				tbl_produto.ativo,
				tbl_contrato_tabela_item.preco
				 FROM tbl_produto
				 JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia AND tbl_familia.fabrica={$login_fabrica}
				 JOIN tbl_contrato_tabela_item ON tbl_contrato_tabela_item.produto = tbl_produto.produto and tbl_contrato_tabela_item.contrato_tabela={$contrato_tabela}
				WHERE tbl_produto.fabrica_i = {$login_fabrica}
				{$whereAdc};
			";
            $res = pg_query($con, $sql);
			if (pg_num_rows($res) > 0) {
				
			?>
			<div id="border_table">
				<table id="resultados" class="table table-striped table-bordered table-hover table-lupa" >
					<thead>
						<tr class='titulo_coluna'>
							<th><?= traduz('Referência') ?></th>
							<th><?= traduz('Nome') ?></th>
							<th><?= traduz('Preço') ?></th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						<?php for ($i = 0 ; $i < pg_num_rows($res); $i++) {

							$preco                    = pg_fetch_result($res, $i, 'preco');
							$produto                  = pg_fetch_result($res, $i, 'produto');
							$referencia               = pg_fetch_result($res, $i, 'referencia');
							$descricao                = pg_fetch_result($res, $i, 'descricao');
							$ativo                    = pg_fetch_result($res, $i, 'ativo');
							$bosch_cfa                = pg_fetch_result($res, $i, 'bosch_cfa');
							$xbosch_cfa  = json_decode($bosch_cfa, 1);
							$limite_horas_trabalhadas = isset($xbosch_cfa["limite_horas_trabalhadas"]) ? $xbosch_cfa["limite_horas_trabalhadas"] : 'false';

							$descricao = str_replace('"', '', $descricao);
							$descricao = str_replace("'", "", $descricao);
							$descricao = str_replace("''", "", $descricao);
							$descricao = retira_acentos($descricao);

							$mativo = ($ativo == 't') ?  " ATIVO " : " INATIVO ";

							
							$r = array(
								"produto"      => $produto,
								"descricao"    => utf8_encode($descricao),
								"referencia"   => $referencia,
								"preco"        =>  number_format($preco, 2, '.', ''),
								"bloquea_horimetro" => $limite_horas_trabalhadas,
							);

						
							if (strlen($posicao) > 0) {
								$r['posicao'] = $posicao;
							}

							$r = array_map('utf8_encode',$r);

								echo "
								<tr class='produto-item' data-produto='".json_encode($r)."'>
										<td class='tac cursor_lupa'>{$referencia}</td>
										<td class='cursor_lupa'>{$descricao}</td>
										<td class='tac cursor_lupa'>R$ ".number_format($preco, 2, '.', '')."</td>
										<td class='tac cursor_lupa'>{$mativo}</td>
								</tr>";

							}
						} else {
							echo '<div class="alert alert_shadobox"><h4>'.traduz('Nenhum produto encontrado').'</h4></div>';

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
