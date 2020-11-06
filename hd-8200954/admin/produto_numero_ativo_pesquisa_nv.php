<?php

/* 
	Esse arquivo foi criado em substituição do arquivo produto_serie_pesquisa_fricon,
	pois outras fábricas necessitam usar esse programa também.
*/
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$num_ativo  = trim($_REQUEST["campo"]);
$tipo       = trim($_REQUEST["tipo"]);
$mapa_linha = trim($_REQUEST["mapa_linha"]);
$voltagem   = trim($_REQUEST["voltagem"]);
$pos 		= trim($_REQUEST["pos"]);
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Produto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css">
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
	<script src="js/jquery-1.3.2.js"	type="text/javascript"></script>
	<script src="js/thickbox.js"		type="text/javascript"></script>
	<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
	<style type="text/css">
		@import "../css/lupas/lupas.css";
		body {
			margin: 0;
			font-family: Arial, Verdana, Times, Sans;
			background: #fff;
		}
	</style>
	<script type="text/javascript">
		$(document).ready(function() {
			$("#gridRelatorio").tablesorter();
		}); 
	</script>
</head>
<body>
<div class="lp_header">
	<a href='' onclick='window.parent.Shadowbox.close();' style='border: 0;'>
		<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
	</a>
</div>

<div class='lp_nova_pesquisa' style="text-align: center;">
	<form action="<?=$_SERVER["PHP_SELF"]?>" method='POST' name='nova_pesquisa'>
		<input type="hidden" name="mapa_linha" value="<?=$mapa_linha?>" />
		<input type="hidden" name="tipo" value="<?=$tipo?>" />
		<input type="hidden" name="pos" value="<?=$pos?>" />
		<input type="hidden" name="voltagem" value="<?=$voltagem?>" />
		<label>Número do ativo: </label><input type="text" name="campo" value="<?=$num_ativo?>" placeholder="Digite o número do ativo..." />
		<input type="submit" value="Pesquisar" />
	</form>
</div>
<?

if ($tipo == "ordem") {
	if(strlen($num_ativo) > 0) {
	?>
		<div class='lp_pesquisando_por'>
			Pesquisando por número do ativo: <?=$num_ativo?>
		</div>
	<?
	$sql = "SELECT	tbl_numero_serie.serie,
					tbl_numero_serie.produto,
					tbl_produto.referencia  ,
					tbl_produto.descricao   ,
					tbl_produto.linha       ,
					tbl_produto.voltagem    ,
					tbl_numero_serie.ordem  ,
					tbl_produto.ativo
			FROM     tbl_numero_serie
 			JOIN     tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto
			WHERE    tbl_numero_serie.ordem ILIKE '%$num_ativo%'
			AND      tbl_numero_serie.fabrica = $login_fabrica limit 30";
	//echo nl2br($sql); exit;
	$res = pg_exec ($con,$sql);

	if (!pg_num_rows($res)) { 
		?>
		<div class='lp_msg_erro'>Produto '<?=$num_ativo?>' não encontrado</div>		
		<?
	}
}

// Descrição - Referência - Mapa_Linha - Série
if(pg_num_rows($res) > 1) {
?>
<table style='width:100%; border: 0;' cellspacing='1' class='lp_tabela' id='gridRelatorio'>
	<thead>
		<tr>
			<th>Ordem</th>
			<th>Série</th>
			<th>Referência</th>
			<th>Descrição</th>
			<th>Linha</th>
			<th>Voltagem</th>
		</tr>
	</thead>
	<tbody>
		<?
		for($i = 0; $i < pg_num_rows($res); $i++) {
			$cor 		= ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			$ordem  	= pg_fetch_result($res, $i, 'ordem');
			$serie      = pg_fetch_result($res, $i, 'serie');
			$referencia = pg_fetch_result($res, $i, 'referencia');
			$descricao  = pg_fetch_result($res, $i, 'descricao');
			$produto	= pg_fetch_result($res, $i, 'produto');
			$mapa_linha	= pg_fetch_result($res, $i, 'linha');
			$voltagem  	= pg_fetch_result($res, $i, 'voltagem');

			$onclick	= (trim($ordem)  	 != '' ? "'$ordem'" : "''") .
						  (trim($serie) 	 != '' ? ", '$serie'" : ", ''") .
						  (trim($referencia) != '' ? ", '$referencia'" : ", ''") .
						  (trim($descricao)  != '' ? ", '$descricao'" : ", ''") . 
						  (trim($produto)  	 != '' ? ", '$produto'" : ", ''") . 
						  (($mapa_linha == 't') ? ", $mapa_linha" : ", ''") . 
						  (trim($voltagem) 	 != '' ? ", '$voltagem'" : ", ''") . 
						  ((strlen($pos) > 0) ? ", '$pos'" : ", ''");

			echo "<tr style='background: $cor' onclick=\"window.parent.retorna_numero_ativo($onclick); window.parent.Shadowbox.close();\">
				  	<td style='text-align: center;'>$ordem</td>
				  	<td style='text-align: center;'>$serie</td>
				  	<td style='text-align: center;'>$referencia</td>
				  	<td style='text-align: center;'>$descricao</td>
				  	<td style='text-align: center;'>$mapa_linha</td>
				  	<td style='text-align: center;'>$voltagem</td>
				  </tr>";
		}
	?>
	</tbody>
</table>
<?
	} else if(pg_num_rows($res) == 1){
			$ordem  	= pg_fetch_result($res, $i, 'ordem');
			$serie      = pg_fetch_result($res, $i, 'serie');
			$referencia = pg_fetch_result($res, $i, 'referencia');
			$descricao  = pg_fetch_result($res, $i, 'descricao');
			$produto    = pg_fetch_result($res, $i, 'produto');
			$mapa_linha	= pg_fetch_result($res, $i, 'linha');
			$voltagem  	= pg_fetch_result($res, $i, 'voltagem');

			$onclick	= (trim($ordem)  	 != '' ? "'$ordem'" : "''") .
						  (trim($serie) 	 != '' ? ", '$serie'" : ", ''") .
						  (trim($referencia) != '' ? ", '$referencia'" : ", ''") .
						  (trim($descricao)  != '' ? ", '$descricao'" : ", ''") . 
						  (trim($produto)    != '' ? ", '$produto'" : ", ''") . 
						  (($mapa_linha == 't') ? ", $mapa_linha" : ", ''") . 
						  (trim($voltagem) 	 != '' ? ", '$voltagem'" : ", ''") . 
						  ((strlen($pos) > 0) ? ", '$pos'" : ", ''");
			?>
			<script type="text/javascript">
				window.parent.retorna_numero_ativo(<?=$onclick?>); window.parent.Shadowbox.close();
			</script>
			<?
	}
} else { ?>

	<div class='lp_msg_erro'>Informar toda ou parte da informação para realizar a pesquisa!</div>
<? } ?>
</body>
</html>