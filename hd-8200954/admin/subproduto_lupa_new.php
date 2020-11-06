<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

/* hd_chamado=2926550
if (strlen($_COOKIE["cook_login_posto"]) > 0) {
	include 'autentica_usuario.php';
} else {
	include 'autentica_admin.php';
}
*/

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}

$parametro = $_REQUEST["parametro"];
$valor     = utf8_decode(trim($_REQUEST["valor"]));

#Usado para filtrar somente produtos de linhas atendidas pelo posto
if ($_REQUEST["posto"]) {
	$posto   = $_REQUEST["posto"];
}

if ($_REQUEST["produto"]) {
	$produto   = $_REQUEST["produto"];
}

if ($_REQUEST["ativo"]) {
	$ativo = $_REQUEST["ativo"];
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
					<input type="hidden" name="posicao" value='<?=$posicao?>' />
					<input type='hidden' name='produto' value='<?=$produto?>' />
					<input type='hidden' name='posto' value='<?=$posto?>' />
					<?php
					if (isset($ativo)) {
					?>
						<input type='hidden' name='ativo' value='<?=$ativo?>' />
					<?php
					}
					?>

					<select name="parametro"  >
						<option value="referencia" <?=($parametro == "referencia") ? "SELECTED" : ""?> >Refêrencia</option>
						<option value="descricao" <?=($parametro == "descricao") ? "SELECTED" : ""?> >Descrição</option>
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
	if (strlen($valor) >= 3) {
		switch ($parametro) {
			case 'referencia':
				$valor    = str_replace(array(".", ",", "-", "/", " "), "", $valor);
				$whereAdc = "UPPER(tbl_produto.referencia_pesquisa) LIKE UPPER('%{$valor}%')";
				break;

			case 'descricao':
				$whereAdc = "( UPPER(fn_retira_especiais(tbl_produto.descricao)) LIKE UPPER('%{$valor}%') OR UPPER(fn_retira_especiais(tbl_produto.nome_comercial)) LIKE UPPER('%{$valor}%') OR (UPPER(fn_retira_especiais(tbl_produto_idioma.descricao)) LIKE UPPER('%{$valor}%') AND tbl_produto_idioma.idioma = '{$sistema_lingua}') )";
				break;
		}

		if (isset($posto)) {
			$join_posto = " INNER JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_posto_linha.posto = {$posto} ";
		}

		if (isset($ativo)) {
			$where_ativo = " AND tbl_produto.ativo IS TRUE ";
		}
		if(!empty($produto)) {
			$sql = "( SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao, tbl_produto.voltagem, tbl_produto.ativo, tbl_produto.fabrica_origem, tbl_produto.numero_serie_obrigatorio
					FROM tbl_subproduto
					INNER JOIN tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_filho
					INNER JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = {$login_fabrica}
					{$join_posto}
					WHERE tbl_subproduto.produto_pai = $produto
					{$where_ativo}
					) UNION (
						SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao, tbl_produto.voltagem, tbl_produto.ativo, tbl_produto.fabrica_origem, tbl_produto.numero_serie_obrigatorio
						FROM tbl_subproduto
						INNER JOIN tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_pai
						INNER JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = {$login_fabrica}
						{$join_posto}
						WHERE tbl_subproduto.produto_filho = $produto
						{$where_ativo}
					)";
			$res = pg_query($con, $sql);
			$rows = pg_num_rows($res);
		}
		//echo nl2br($sql);exit;
		if ($rows > 0) {
		?>

		<div id="border_table">
			<table class="table table-striped table-bordered table-hover table-lupa" >
				<thead>
					<tr class='titulo_coluna'>
						<th>Código</th>
						<th>Nome</th>
						<th>Voltagem</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0 ; $i < $rows; $i++) {
						$produto           = pg_fetch_result($res, $i, 'produto');
						$voltagem          = pg_fetch_result($res, $i, 'voltagem');
						$referencia        = pg_fetch_result($res, $i, 'referencia');
						$descricao         = pg_fetch_result($res, $i, 'descricao');
						$ativo     		   = pg_fetch_result($res, $i, 'ativo');
						$tipo_produto      = pg_fetch_result($res, $i, 'fabrica_origem');
						$numero_serie_obrigatorio = pg_fetch_result($res, $i, 'numero_serie_obrigatorio');
						$sql_idioma = "SELECT descricao FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";
						$res_idioma = pg_query($con, $sql_idioma);

						if (pg_num_rows($res_idioma) >0) {
							$descricao = pg_fetch_result($res_idioma, 0, 'descricao');
						}

						$descricao = str_replace('"', '', $descricao);
						$descricao = str_replace("'", "", $descricao);
						$descricao = str_replace("''", "", $descricao);

						$mativo = ($ativo == 't') ?  " ATIVO " : " INATIVO ";

						$r = array(
							"produto"    => $produto,
							"descricao"  => utf8_encode($descricao),
							"referencia" => $referencia,
							"voltagem"   => $voltagem,
							"tipo_produto" => $tipo_produto,
							"numero_serie_obrigatorio" => $numero_serie_obrigatorio
						);

						echo "<tr onclick='window.parent.retorna_subproduto(".json_encode($r)."); window.parent.Shadowbox.close();' >";
							echo "<td class='cursor_lupa'>{$referencia}</td>";
							echo "<td class='cursor_lupa'>{$descricao}</td>";
							echo "<td class='cursor_lupa'>{$voltagem}</td>";
							echo "<td class='cursor_lupa'>{$mativo}</td>";
						echo "</tr>";
					}
				echo "</tbody>";
			echo "</table>";
		}else{
			echo '
			<div class="alert alert_shadobox">
				    <h4>Nenhum resultado encontrado</h4>
			</div>';

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

