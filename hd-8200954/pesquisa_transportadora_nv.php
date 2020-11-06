<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if (!function_exists('ttext')) {
	include_once 'helpdesk/fn_ttext.php';
}
$pr_trad = array (
	'titulo' => array (
		'pt-br'	=> 'Pesquisa Transportadora',
		'es'	=> 'Busca Transportadora',
		'en'	=> 'Carriers Search'
	),
	"pesquisa_nome" => array (
		"pt-br"	=> "Resultados da pesquisa pelo <b>nome</b> da Transportadora: ",
		"es"	=> "Resultado de la búsqueda por <b>nombre</b> del Transportadora: ",
	),
	"nome_not_found" => array (
		"pt-br"	=> "Transportadora '%s' não encontrada",
		"es"	=> "Distribuidor '%s' no encontrado",
	),
	"pesquisa_cnpj" => array (
		"pt-br"	=> "Resultados da pesquisa pelo <b>CNPJ</b> da Transportadora: ",
		"es"	=> "Resultado de la búsqueda por <b>nº ID Fiscal</b> del Transportadora: ",
	),
	"cnpj_not_found" => array (
		"pt-br"	=> "Transportadora de CNPJ '%s' não encontrada",
		"es"	=> "Transportadora con ID Fiscal '%s' no encontrado",
	),
	"digite_ao_menos" => array (
		"pt-br"	=> "Digite ao menos as 4 primeiras letras para pesquisar por nome, ou os 6 primeiros dígitos do CNPJ",
		"es"	=> "Escriba al menos las primeras 4 letras del nombre, o los 6 primeros dígitos del nº de ID Fiscal",
	),
);

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<meta http-equiv='pragma' content='no-cache'>
		<title><?=ttext($pr_trad, "titulo")?></title>
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}
		</style>
		<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
		<script src="plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
		<script type='text/javascript'>
			//função para fechar a janela caso a telca ESC seja pressionada!
			$(window).keypress(function(e) { 
				if(e.keyCode == 27) { 
					 window.parent.Shadowbox.close();
				}
			});

			$(document).ready(function() {
				$("#gridRelatorio").tablesorter();
			}); 
		</script>
	</head>

<body style="margin: 0px 0px 0px 0px;">
	<div class="lp_header">
		<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
			<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
		</a>
	</div>

<?
	echo "<div class='lp_nova_pesquisa'>";
			echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST' name='nova_pesquisa'>";
				echo "<input type='hidden' name='forma' value='$forma' />";
				echo "<table cellspacing='1' cellpadding='2' border='0'>";
					echo "<tr>";
						echo "<td>
							<label>".traduz("cnpj.revenda",$con,$cook_idioma)."</label>
							<input type='text' name='cnpj' value='$cnpj' style='width: 150px' maxlength='20' />
						</td>"; 
						echo "<td>
							<label>".traduz('nome.revenda',$con,$cook_idioma)."</label>
							<input type='text' name='nome' value='$nome' style='width: 370px' maxlength='80' />
						</td>"; 
						echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>"; 
					echo "</tr>";
				echo "</table>";
			echo "</form>";
		echo "</div>";


$nome = strtoupper(trim($_REQUEST['nome']));
$cnpj = strtoupper(trim($_REQUEST['cnpj']));
$codigo = strtoupper(trim($_REQUEST['codigo']));

function verificaValorCampo($campo){
	return strlen($campo) > 0 ? $campo : "&nbsp;";
}

if($login_fabrica == 94){
	$left_join_transp = " LEFT JOIN tbl_transportadora_valor ON tbl_transportadora.transportadora = tbl_transportadora_valor.transportadora AND tbl_transportadora_valor.fabrica = $login_fabrica ";
	$campo_frete = " ,tbl_transportadora_valor.valor_kg ";
}

if (strlen($nome) > 0) {
	echo "<div class='lp_pesquisando_por'>".ttext($pr_trad, "pesquisa_nome").": $nome</div>";
	
	$sql = "SELECT      tbl_transportadora.*, tbl_transportadora_fabrica.codigo_interno $campo_frete
			FROM        tbl_transportadora
			JOIN        tbl_transportadora_fabrica USING (transportadora)
			$left_join_transp
			WHERE       tbl_transportadora_fabrica.fabrica = $login_fabrica
			AND         tbl_transportadora.nome ILIKE '%$nome%'
			AND			tbl_transportadora_fabrica.ativo
			ORDER BY    tbl_transportadora.nome";

}elseif (strlen($cnpj) > 0) {# HD 289285
	$cnpj = str_replace (" ","",$cnpj);
	$cnpj = str_replace (".","",$cnpj);
	$cnpj = str_replace ("/","",$cnpj);
	$cnpj = str_replace ("-","",$cnpj);

	echo "<div class='lp_pesquisando_por'>".ttext($pr_trad, "pesquisa_cnpj").": $cnpj</div>";
	
	$sql = "SELECT      tbl_transportadora.*, tbl_transportadora_fabrica.codigo_interno $campo_frete
			FROM        tbl_transportadora
			JOIN        tbl_transportadora_fabrica USING (transportadora)
			$left_join_transp
			WHERE       tbl_transportadora.cnpj LIKE '%$cnpj%'
			AND         tbl_transportadora_fabrica.fabrica = $login_fabrica
			AND			tbl_transportadora_fabrica.ativo
			ORDER BY    tbl_transportadora.nome";

}elseif (strlen($codigo) > 0) {
	echo "<div class='lp_pesquisando_por'>".ttext($pr_trad, "pesquisa_codigo").": $codigo</div>";
	
	$sql = "SELECT      tbl_transportadora.*, tbl_transportadora_fabrica.codigo_interno $campo_frete
			FROM        tbl_transportadora
			JOIN        tbl_transportadora_fabrica USING (transportadora)
			$left_join_transp
			WHERE       tbl_transportadora_fabrica.codigo_interno = '$codigo'
			AND         tbl_transportadora_fabrica.fabrica = $login_fabrica
			AND			tbl_transportadora_fabrica.ativo
			ORDER BY    tbl_transportadora.nome";
	
}

$res = pg_query ($con,$sql);

if (pg_num_rows ($res) > 0 ) {
	
	if (pg_num_rows($res) == 1 ){
		$transportadora   = trim(pg_fetch_result($res,0,transportadora));
		$nome             = trim(pg_fetch_result($res,0,nome));
		$cnpj             = trim(pg_fetch_result($res,0,cnpj));
		$fantasia         = trim(pg_fetch_result($res,0,fantasia));
		$codigo_interno   = trim(pg_fetch_result($res,0,codigo_interno));

		if($login_fabrica == 94){
			$frete   = trim(pg_fetch_result($res,0,valor_kg));
		}else{
			$frete = "";
		}


		echo "<script type='text/javascript'>";
			echo "window.parent.retorna_transportadora('$transportadora','$nome','$cnpj','$fantasia','$codigo_interno','$frete'); window.parent.Shadowbox.close();";
		echo "</script>";
	}
	
		?>
		<table width='100%' border='0' cellspacing='1' cellspacing='0' class='lp_tabela' id='gridRelatorio'>
			<thead>
				<tr>
					<th><?=traduz("cnpj",$con,$cook_idioma)?></th>
					<th><?=traduz('codigo.interno',$con,$cook_idioma)?></th>
					<th><?=traduz('nome',$con,$cook_idioma)?></th>

					<?php
						if($login_fabrica == 94){
					?>
							<th><?=traduz('frete',$con,$cook_idioma)?></th>
					<?php
						}
					?>
				</tr>
			</thead>
			<tbody>
		<?php

	for ( $i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
		$transportadora   = trim(pg_fetch_result($res,$i,transportadora));
		$nome             = trim(pg_fetch_result($res,$i,nome));
		$cnpj             = trim(pg_fetch_result($res,$i,cnpj));
		$fantasia         = trim(pg_fetch_result($res,$i,fantasia));
		$codigo_interno   = trim(pg_fetch_result($res,$i,codigo_interno));

		if($login_fabrica == 94){
			$frete   = trim(pg_fetch_result($res,$i,valor_kg));
		}else{
			$frete = "";
		}

			$onclick = "onclick= \"javascript: window.parent.retorna_transportadora('$transportadora','$nome','$cnpj','$fantasia','$codigo_interno','$frete'); window.parent.Shadowbox.close();\"";

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			echo "<tr style='background: $cor; cursor:pointer;' $onclick>";
				echo "<td>".verificaValorCampo($cnpj)."</td>";
				echo "<td>".verificaValorCampo($codigo_interno)."</td>";
				echo "<td>".verificaValorCampo($nome)."</td>";

				if($login_fabrica == 94){
					$frete = number_format($frete,2,",",".");
					echo "<td>".verificaValorCampo($frete)."</td>";
				}
			echo "</tr>";		
	}
	echo "</tbody>";
	echo "</table>\n";
} else {
	echo "<div class='lp_msg_erro'>Nenhum resultado encontrado</div>";
}
?>

</body>
</html>
