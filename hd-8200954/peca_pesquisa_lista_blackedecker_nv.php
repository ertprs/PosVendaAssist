<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

function verificaValorCampo($campo){
	return strlen($campo) > 0 ? $campo : "&nbsp;";
}

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

# verifica se posto pode ver pecas de itens de aparencia
$sql = "SELECT tbl_posto_fabrica.item_aparencia
		FROM   tbl_posto
		JOIN   tbl_posto_fabrica USING(posto)
		WHERE  tbl_posto.posto           = $login_posto
		AND    tbl_posto_fabrica.fabrica = $login_fabrica";
$res = pg_exec($con,$sql);
//echo "$sql";
if (pg_numrows($res) > 0) {
	$item_aparencia = pg_result($res,0,item_aparencia);
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv='pragma' content='no-cache'>
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
				echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST' name='nova_pesquisa'>";
					echo "<input type='hidden' name='forma' value='$forma' />";
					echo "<table cellspacing='1' cellpadding='2' border='0'>";
						echo "<tr>";
							echo "<td>
								<label>Código da Peça</label>
								<input type='text' name='codigo' value='$codigo' style='width: 150px' maxlength='20' />
							</td>"; 
							echo "<td>
								<label>Descrição</label>
								<input type='text' name='nome' value='$nome' style='width: 370px' maxlength='80' />
							</td>"; 
							echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>"; 
						echo "</tr>";
					echo "</table>";
				echo "</form>";
			echo "</div>";
		?>


<?
$posicao	= trim($_REQUEST["posicao"]);
$referencia = trim(strtoupper($_GET["peca"]));
$xreferencia = str_replace(".","",$referencia);
$xreferencia = str_replace(",","",$xreferencia);
$xreferencia = str_replace("-","",$xreferencia);
$xreferencia = str_replace("/","",$xreferencia);
$xreferencia = str_replace(" ","",$xreferencia);


if (strlen($xreferencia) > 0) {
	echo "<div class='lp_pesquisando_por'>Pesquisando pela referência: $referencia</div>";

	$sql =	"SELECT z.peca                                ,
					z.referencia       AS peca_referencia ,
					z.descricao        AS peca_descricao  ,
					z.peca_fora_linha                     ,
					z.de                                  ,
					z.para                                ,
					z.peca_para                           ,
					tbl_peca.descricao AS para_descricao
			FROM (
					SELECT  y.peca               ,
							y.referencia         ,
							y.descricao          ,
							y.peca_fora_linha    ,
							tbl_depara.de        ,
							tbl_depara.para      ,
							tbl_depara.peca_para
					FROM (
							SELECT  x.peca                                      ,
									x.referencia                                ,
									x.descricao                                 ,
									tbl_peca_fora_linha.peca AS peca_fora_linha
							FROM (
									SELECT  tbl_peca.peca       ,
											tbl_peca.referencia ,
											tbl_peca.descricao
									FROM tbl_peca
									WHERE tbl_peca.fabrica = $login_fabrica
									AND   tbl_peca.ativo IS TRUE
									AND   tbl_peca.produto_acabado IS NOT TRUE
									AND   tbl_peca.acessorio IS NOT TRUE";
	if (strlen($xreferencia) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.referencia_pesquisa)) = UPPER(TRIM('$xreferencia'))";
	if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
	$sql .= "					) AS x
							LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
						) AS y
					LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
				) AS z
			LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
			ORDER BY z.descricao";
}
$res = pg_exec ($con,$sql);



if (pg_numrows($res) > 0) { ?>
	<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
			<thead>
				<tr>
					<th>Referência</th>
					<th>Descrição</th>
				</tr>
			</thead>
			<tbody>
<?
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$peca            = trim(@pg_result($res,$i,peca));
		$peca_referencia = trim(@pg_result($res,$i,peca_referencia));
		$peca_descricao  = trim(@pg_result($res,$i,peca_descricao));
		$peca_descricao  = str_replace('"','',$peca_descricao);
		$peca_fora_linha = trim(@pg_result($res,$i,peca_fora_linha));
		$peca_para       = trim(@pg_result($res,$i,peca_para));
		$para            = trim(@pg_result($res,$i,para));
		$para_descricao  = trim(@pg_result($res,$i,para_descricao));

		if(pg_num_rows($res) == 1){
			if(!empty($para)) {
				echo "<script type='text/javascript'>";
						echo "window.parent.retorna_peca('$para','$para_descricao','$posicao'); window.parent.Shadowbox.close();";
				echo "</script>";
			} else {
				echo "<script type='text/javascript'>";
						echo "window.parent.retorna_peca('$peca_referencia','$peca_descricao','$posicao'); window.parent.Shadowbox.close();";
				echo "</script>";
			}
			exit;
		}

		$onclick = "onclick= \"javascript: window.parent.retorna_peca('$peca_referencia','$peca_descricao','$posicao'); window.parent.Shadowbox.close();\"";
		if(!empty($para)) {
		
			$onclick = "onclick= \"javascript: window.parent.retorna_peca('$para','$para_descricao','$posicao'); window.parent.Shadowbox.close();\"";
		}

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		echo "<tr style='background: $cor' $onclick>";
			echo "<td>".verificaValorCampo($peca_referencia)."</td>";
			if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0) {
				echo "<td <font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>".verificaValorCampo($peca_descricao)."</font></td>";
			}else{
				echo "<td>".verificaValorCampo($peca_descricao)."</td>";
			}
		echo "</tr>";

		if (strlen($peca_fora_linha) > 0) {
			echo "<tr>\n";
			echo "<td colspan='2' nowrap>";
			echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b>";
			echo "A peça acima é obsoleta, não é mais fornecida";
			echo "</b></font>";
			echo "</td>\n";
			echo "</tr>\n";

			/* HD 152533 */
			if (strlen($para) > 0) {
				echo "<tr>\n";
				echo "<td colspan='2' nowrap>";
				echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b>A peça acima mudou Para:</b></font>";
				echo " <font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>".verificaValorCampo($para)."</font>";
				echo "</td>\n";
				echo "</tr>\n";
			}
		}else{
			if (strlen($para) > 0) {
				echo "<tr>\n";
				echo "<td colspan='2' nowrap>";
				echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b>A peça acima mudou Para:</b></font>";
				echo " <font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>".verificaValorCampo($para)."</font>";
				echo "</td>\n";
				echo "</tr>\n";
			}
		}
		
	}

	echo "</table>";
}else{
	echo "<div class='lp_msg_erro'>Nehum resultado encontrado</div>";
}
?>

</body>
</html>
