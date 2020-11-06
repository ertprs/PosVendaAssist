<?php
include "dbconfig.php";
include "dbconnect-inc.php";
include 'autentica_admin.php';

$contador_ver = "0";

if ($_REQUEST["posicao"]) {
	$posicao   = $_REQUEST["posicao"];
}

$parametro = $_REQUEST["parametro"];
$valor     = trim($_REQUEST["valor"]);

if ($_GET["valor"]) {
	$valor = utf8_decode($valor);
}

if($_REQUEST['produtoId']){
	$produtoId = $_REQUEST['produtoId'];
}

if($_REQUEST['produtoAcao']){
	$produtoAcao = $_REQUEST['produtoAcao'];
}

if($_REQUEST['listaTroca']){
	$listaTroca = $_REQUEST['listaTroca'];
}

if($_REQUEST['voltagemForm']){
	$voltagemForm = $_REQUEST['voltagemForm'];
}

## CADASTRO SUBCONJUNTO ##
if($_REQUEST['pai'] == 'true'){
	$pai = true;
}

if($_REQUEST['filho'] == 'true'){
	$filho = true;
}
##########################

?>

<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="../admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="../admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="../admin/css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="../admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />
		

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="../admin/bootstrap/js/bootstrap.js"></script>
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
					<input type="hidden" name="posicao" value='<?=$posicao?>' />
					
					<?if(isset($produtoId)){?>
						<input type="hidden" name="produtoId" value="<?=$produtoId?>" />
					<? } ?>

					<?
						if(isset($produtoAcao)){
							echo "<input type='hidden' name='produtoAcao' value='<?=$produtoAcao?>' />";
						}
						
						if(isset($de)){
							echo "<input type='hidden' name='de' value='<?=$de?>'/>";
						}

						if(isset($para)){
							echo "<input type='hidden' name='para' value='<?=$para?>'/>";
						}

						if(isset($pai)){
							echo "<input type='hidden' name='pai' value='<?=$pai?>'/>";
						}

						if(isset($filho)){
							echo "<input type='hidden' name='filho' value='<?=$filho?>'/>";
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
		$msg_confirma = "0";
			
		if ($login_fabrica == 30 && strlen($valor) >= 3) {
			switch ($parametro) {
				case 'referencia':
					$valor = str_replace(array(".", ",", "-", "/"), "", $valor);
					$whereAdc = "UPPER(tbl_produto.referencia_pesquisa) LIKE UPPER('%{$valor}%')";
					break;
				
				case 'descricao':
					$whereAdc = "(UPPER(tbl_produto.descricao) LIKE UPPER('%{$valor}%') OR UPPER(tbl_produto.nome_comercial) LIKE UPPER('%{$valor}%') )";
					break;
			}

			if (isset($whereAdc)) {
				$sql = "SELECT CASE WHEN tbl_produto.marca = 164 THEN 't' ELSE 'f' END AS itatiaia
						FROM tbl_produto
						JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
						WHERE 
						{$whereAdc}
						AND tbl_linha.fabrica = {$login_fabrica}
						";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$itatiaia = pg_fetch_result($res, 0, 'itatiaia');

					if ($itatiaia == 't') {
						$contador_ver ="1";

						echo "<script>";
							echo "alert('Este produto é ITATIAIA não pode ser aberto Ordem de Serviço pelo Posto, somente o CALLCENTER poderá abrir. Favor entrar em contato com o CALLCENTER!');";
							echo "window.parent.Shadowbox.close();";
						echo "</script>";
					}
				}
			}
		}

		if (strlen($valor) >= 3) {
			switch ($parametro) {
				case 'referencia':
					$valor = str_replace(array(".", ",", "-", "/", " "), "", $valor);

					if ($login_fabrica == 20) {
						$whereAdc = "(UPPER(tbl_produto.referencia_pesquisa) LIKE UPPER('%{$valor}%') OR UPPER(tbl_produto.referencia_fabrica) LIKE UPPER('%{$valor}%') OR UPPER(tbl_produto.referencia) LIKE UPPER('%{$valor}%'))";
					} else {
						$whereAdc = "UPPER(tbl_produto.referencia_pesquisa) LIKE UPPER('%{$valor}%')";
					}
					break;
				
				case 'descricao':
					if ($login_fabrica <> 20) {
						$whereAdc = "( UPPER(fn_retira_especiais(tbl_produto.descricao)) LIKE UPPER('%{$valor}%') OR UPPER(fn_retira_especiais(tbl_produto.nome_comercial)) LIKE UPPER('%{$valor}%') OR (UPPER(fn_retira_especiais(tbl_produto_idioma.descricao)) LIKE UPPER('%{$valor}%') AND tbl_produto_idioma.idioma = '{$sistema_lingua}') )";
					} else {
						$whereAdc = "( UPPER(fn_retira_especiais(tbl_produto.descricao)) LIKE UPPER('%{$valor}%') OR UPPER(fn_retira_especiais(tbl_produto.nome_comercial)) LIKE UPPER('%{$valor}%') )";
					}
					break;
			}

			if ($login_fabrica == 14) {
				$joinAdc .= " JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia ";
			}

			if ($login_fabrica <> 20) {
				$joinAdc .= " LEFT JOIN tbl_produto_idioma USING(produto) LEFT JOIN tbl_produto_pais USING(produto) ";
			}

			if ($login_fabrica == 14 || $login_fabrica == 66) {
				$whereAdc .= " AND tbl_produto.abre_os IS TRUE ";
			}

			if ($login_fabrica == 14 && $login_pais == 'BR') {
				$whereAdc .= " AND UPPER(tbl_produto.origem) <> 'IMP' AND UPPER(tbl_produto.origem) <> 'USA' AND UPPER(tbl_produto.origem) <> 'ASI' ";
			}

			if($listaTroca){
				$whereAdc .=" AND tbl_produto.lista_troca IS TRUE AND tbl_produto.ativo IS TRUE";
			}

			$sql = "SELECT *
					FROM tbl_produto
					JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
					{$joinAdc}
					WHERE
					{$whereAdc}
					AND tbl_linha.fabrica = {$login_fabrica}
					";
			$res = pg_query($con, $sql);
			$rows = pg_num_rows($res);

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
							$produto            = pg_fetch_result($res, $i, 'produto');
							$linha              = pg_fetch_result($res, $i, 'linha');
							$nome_comercial     = pg_fetch_result($res, $i, 'nome_comercial');
							$voltagem           = pg_fetch_result($res, $i, 'voltagem');
							$referencia         = pg_fetch_result($res, $i, 'referencia');
							$descricao          = pg_fetch_result($res, $i, 'descricao');
							$referencia_fabrica = pg_fetch_result($res, $i, 'referencia_fabrica');
							$garantia           = pg_fetch_result($res, $i, 'garantia');
							$ativo              = pg_fetch_result($res, $i, 'ativo');
							$valor_troca        = pg_fetch_result($res, $i, 'valor_troca');
							$troca_garantia     = pg_fetch_result($res, $i, 'troca_garantia');
							$troca_faturada     = pg_fetch_result($res, $i, 'troca_faturada');
							$mobra              = str_replace(".", ",", pg_fetch_result($res, $i, "mao_de_obra"));
							$off_line           = pg_fetch_result($res, $i, "off_line");
							$capacidade         = pg_fetch_result($res, $i, 'capacidade');
							$ipi                = pg_fetch_result($res, $i, "ipi");
							$troca_obrigatoria  = pg_fetch_result($res, $i, 'troca_obrigatoria');

							$sql_idioma = "SELECT descricao FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";
							$res_idioma = pg_query($con, $sql_idioma);

							if (pg_num_rows($res_idioma) >0) {
								$descricao = pg_fetch_result($res_idioma, 0, 'descricao');
							}



								$descricao			= str_replace('"', '', $descricao);
								$descricao			= str_replace("'", "", $descricao);
								$descricao			= str_replace("''", "", $descricao);

								$mativo = ($ativo == 't') ?  " ATIVO " : " INATIVO ";

								if (strlen($ipi) > 0 && $ipi != "0") {
									$valor_troca = $valor_troca * (1 + ($ipi /100));
								}

								$produto_pode_trocar = 1;

								if ($troca_produto == 't' || $revenda_troca == 't') {
									if ($troca_faturada != 't' && $troca_garantia != 't') {
										$produto_pode_trocar = 0;
									}
								}

								$produto_so_troca = 1;

								if ($troca_obrigatoria_consumidor == 't' || $troca_obrigatoria_revenda == 't') {
									if ($troca_obrigatoria == 't') {
										$produto_so_troca = 0;
									}
								}

								$r = array(
									"produto"   => $produto,
									"descricao" => utf8_encode($descricao),
									"referencia" => $referencia
								);
								if(isset($produtoId)){
									$r["id"] = $produto; 
								}

								if(isset($produtoAcao)){
									$r["produtoAcao"] = $produtoAcao;
								}

								if (strlen($posicao) > 0) {
									$r["posicao"] = $posicao;
								}

								if (strlen($voltagemForm) > 0) {
									$r["voltagemForm"] = $voltagem;
								}

								if($pai == true){
									$r['pai'] = $pai;
								}

								if($filho == true){
									$r['filho'] = $filho;
								}


							echo "<tr onclick='window.parent.retorna_produto(".json_encode($r)."); window.parent.Shadowbox.close();' >";
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

