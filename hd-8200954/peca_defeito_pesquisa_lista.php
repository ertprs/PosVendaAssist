<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

if (!function_exists('ttext')) {
	include_once 'helpdesk/fn_ttext.php';
}

$pr_trad = array (
	'titulo' => array (
		'pt-br'	=> 'Pesquisa Peça',
		'es'	=> 'Busca Pieza',
		'en'	=> 'Piece Search'
	),
	"pesquisa_nome" => array (
		"pt-br"	=> "Resultados da pesquisa pelo <b>nome</b> da Peça: ",
		"es"	=> "Resultado de la búsqueda por <b>nombre</b> del Pieza: ",
	),
	"nome_not_found" => array (
		"pt-br"	=> "Peça '%s' não encontrada",
		"es"	=> "Pieza '%s' no encontrado",
	),
	"digite_ao_menos" => array (
		"pt-br"	=> "Digite ao menos as 3 primeiras letras para pesquisar por nome, ou os 3 primeiros dígitos da referência",
		"es"	=> "Escriba al menos las primeras 2 letras del nombre, o los 3 primeros dígitos de referencia",
	),
);

	$referencia	= trim($_REQUEST["referencia"]);
	$descricao	= trim($_REQUEST["descricao"]);
	$posicao	= trim($_REQUEST["input_posicao"]);
	$produto	= trim($_REQUEST['produto']);
	$codProduto 	= trim($_REQUEST['cod_produto']);
	$tipo		= trim($_REQUEST['tipo']);

	if (strlen($codProduto) == 0) {
		$sql = "select produto from tbl_produto where referencia = '$produto' and fabrica_i = $login_fabrica";
		$res = pg_query($con,$sql);
		$codProduto = pg_result($res,0,produto);
	}

	$codigo_defeitos = trim($_REQUEST['codigo_defeitos']);
	$codigo_defeitos = str_replace('\\','', $codigo_defeitos);
	$defeitos_hidden = $codigo_defeitos;
	$codigo_defeitos = json_decode($codigo_defeitos,true);

	$defeitos = array();
	foreach ($codigo_defeitos as $value) {
		$defeitos[] = "'$value'";
	}
	$defeitos =  implode(',', $defeitos);

	if(!empty($codProduto)){
		$join_lista_basica = " JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.produto = $codProduto ";
	}

	function verificaValorCampo($campo){
		return strlen($campo) > 0 ? $campo : "&nbsp;";
	}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
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

	<body>
		<div class="lp_header">
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>
		<?
			echo "<div class='lp_nova_pesquisa'>";
				echo "<form action='$PHP_SELF' method='POST' name='nova_pesquisa'>";
					echo "<table cellspacing='1' cellpadding='2' border='0'>";
						echo "<tr>";
							echo "<td>
								<label>Refêrencia</label>
								<input type='text' name='referencia' value='$referencia' style='width: 150px' maxlength='20' />
							</td>";
							echo "<td>
								<label>Descrição</label>
								<input type='text' name='descricao' value='$descricao' style='width: 370px' maxlength='80' />
							</td>";
							echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>";
						echo "</tr>";
					echo "</table>";
					echo "<input type='hidden' name='posicao' value='$posicao'>";
					echo "<input type='hidden' name='bateria' value='$bateria'>";
					echo "<input type='hidden' name='codigo_defeitos' value='$defeitos_hidden'>";
					echo "<input type='hidden' name='cod_produto' value='$codProduto'>";
				echo "</form>";
			echo "</div>";
if($login_fabrica == 134) {
	$tabela_dc = '_constatado';
	$codigo = 'codigo';
}else{
	$codigo = 'codigo_defeito';
}

if($login_fabrica == 134 && !empty($codProduto) && strlen($defeitos) > 0 && (strlen($referencia) == 0 && strlen($descricao) == 0)){

		$sql = "SELECT 	tbl_peca.peca,
			   			tbl_peca.referencia as peca_referencia,
					   	tbl_peca.descricao as peca_descricao
					FROM tbl_peca join tbl_peca_defeito_constatado using(peca)
					join tbl_defeito_constatado using(defeito_constatado)
					join tbl_lista_basica using(peca)
					WHERE tbl_peca_defeito_constatado.fabrica = $login_fabrica
					AND tbl_defeito_constatado.codigo in ($defeitos)
					AND tbl_peca.ativo IS TRUE
					AND tbl_lista_basica.produto = $codProduto;";

}elseif ((strlen($referencia) > 2 || ($tipo == "lista" && !empty($codProduto))) && strlen($defeitos) > 0) {

	if ($login_fabrica != 131) { /*HD - 6411025*/
		$join_defeito = "
			JOIN tbl_peca_defeito$tabela_dc ON tbl_peca.peca = tbl_peca_defeito$tabela_dc.peca
			JOIN tbl_defeito$tabela_dc ON tbl_peca_defeito$tabela_dc.defeito$tabela_dc = tbl_defeito$tabela_dc.defeito$tabela_dc
			AND tbl_defeito$tabela_dc.fabrica = $login_fabrica
			AND tbl_defeito$tabela_dc.$codigo IN($defeitos)
		";
	}

	echo "<div class='lp_pesquisando_por'>Pesquisando pela referência: $referencia</div>";

	$referencia = strtoupper($referencia);
	$referencia = str_replace (".","",$referencia);
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace ("/","",$referencia);
	$referencia = str_replace (" ","",$referencia);

	$sql =	"SELECT distinct z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao  ,
						z.libera_garantia
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para ,
								y.libera_garantia
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										tbl_peca_fora_linha.peca AS peca_fora_linha,
										tbl_peca_fora_linha.libera_garantia
								FROM (
										SELECT  tbl_peca.peca              ,
												tbl_peca.referencia        ,
												tbl_peca.descricao         ,
												tbl_peca.bloqueada_garantia
										FROM tbl_peca
										$join_defeito
										$join_lista_basica
										WHERE tbl_peca.fabrica = $login_fabrica
										AND tbl_peca.ativo IS TRUE
										AND UPPER(TRIM(referencia_pesquisa)) ILIKE UPPER(TRIM('%$referencia%'))
								) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				ORDER BY z.descricao";
} elseif(strlen($descricao) > 2 && strlen($defeitos) > 0){

	$sql =	"SELECT distinct z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao ,
						z.libera_garantia
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para ,
								y.libera_garantia
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										tbl_peca_fora_linha.peca AS peca_fora_linha,
										tbl_peca_fora_linha.libera_garantia
								FROM (
										SELECT  tbl_peca.peca       ,
												tbl_peca.referencia ,
												tbl_peca.descricao  ,
												tbl_peca.bloqueada_garantia
										FROM tbl_peca
										JOIN tbl_peca_defeito$tabela_dc ON tbl_peca.peca = tbl_peca_defeito$tabela_dc.peca
										JOIN tbl_defeito$tabela_dc ON tbl_peca_defeito$tabela_dc.defeito$tabela_dc = tbl_defeito$tabela_dc.defeito$tabela_dc
										AND tbl_defeito$tabela_dc.fabrica = $login_fabrica
										AND tbl_defeito$tabela_dc.$codigo IN($defeitos)
										$join_lista_basica
										WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_peca.ativo IS TRUE
										AND UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%'))
								) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				ORDER BY z.descricao";
} else {
	$msg_erro = "Informar toda ou parte da informação para realizar a pesquisa!";
}
if(strlen($msg_erro) > 0){
	echo "<div class='lp_msg_erro'>$msg_erro</div>";
}else{
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) > 0) {?>
		<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
			<thead>
				<tr>
					<th width="20%">Código</th>
					<th width="40%">Descrição</th>
				</tr>
			</thead>
			<tbody><?
				for ($i = 0 ; $i < pg_num_rows($res); $i++) {
					$peca	= trim(pg_result($res, $i, peca));
					$referencia	= trim(pg_result($res, $i, peca_referencia));
					$descricao	= trim(pg_result($res, $i, peca_descricao));
					$ipi		= trim(pg_result($res, $i, peca_fora_linha));
					$origem		= trim(pg_result($res, $i, de));
					$peca_para       = trim(@pg_result($res,$i,peca_para));
					$para            = trim(@pg_result($res,$i,para));
					$para_descricao  = trim(@pg_result($res,$i,para_descricao));

					$contax=1;
					if(strlen($para) > 0) {
						for($xx=0;$xx<$contax;$xx++){
							$peca_parax= $peca_para;
							$sql_para="SELECT peca_para,para,(select descricao from tbl_peca where tbl_peca.peca = tbl_depara.peca_para) as descricao FROM tbl_depara join tbl_peca on tbl_peca.peca = tbl_depara.peca_de LEFT JOIN tbl_peca_fora_linha USING(peca) WHERE tbl_depara.fabrica = $login_fabrica AND peca_de = $peca_parax AND peca_fora_linha IS NULL";
							$res_para=pg_exec($con,$sql_para);
							if(pg_numrows($res_para) >0){
								$peca_para       = trim(@pg_result($res_para,0,peca_para));
								$para            = trim(@pg_result($res_para,0,para));
								$para_descricao  = trim(@pg_result($res_para,0,descricao));
								$contax++;
							}
						}
					}

					if(pg_num_rows($res) == 1){
						echo "<script type='text/javascript'>";
							echo "window.parent.retorna_dados_peca('$peca','$referencia','$descricao','$ipi','$origem','$para','$peca_para','$para_descricao','$posicao'); window.parent.Shadowbox.close();";
						echo "</script>";
					}

					$descricao = str_replace(array('"', '\\'), array("&quot;",""), $descricao);

					$onclick = "onclick= \"javascript: window.parent.retorna_dados_peca('$peca','$referencia','$descricao','$ipi','$origem','$para','$peca_para','$para_descricao','$posicao'); window.parent.Shadowbox.close();\"";

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					if(empty($para)){
						echo "<tr style='background: $cor' $onclick>";
							echo "<td>".verificaValorCampo($referencia)."</td>";
							echo "<td>".verificaValorCampo($descricao)."</td>";
						echo "</tr>";
					}else{
						echo "<tr style='background: $cor' $onclick>";
							echo "<td>".$referencia."</td>";
							echo "<td> <b>Mudou Para:</b> ".verificaValorCampo($para_descricao)."</td>";
						echo "</tr>";
					}
				}
	}else {
		if(!empty($bateria)){
			echo "<div class='lp_msg_erro'>A peça selecionada não é referente a lista básica do produto cadastrado ou não é uma BATERIA !</div>";
		}else{
			echo "<div class='lp_msg_erro'>Nenhum resultado encontrado</div>";
		}
	}

}
?>

</body>
</html>
