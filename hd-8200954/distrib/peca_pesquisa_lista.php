<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

$login_fabrica = 10;
$login_posto = 4311;

$sql = "SELECT tipo_posto from tbl_posto_fabrica where fabrica = $login_fabrica and posto = $login_posto";
$res = pg_query($con,$sql);
if (pg_num_rows($res)>0) {
	$tipo_posto = pg_fetch_result($res,0,0);
}

#include 'cabecalho_pop_pecas.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$caminho = "../imagens_pecas";

$exibe_mensagem = 't';
if (strpos($_GET['exibe'],'pedido') !== false) $exibe_mensagem = 'f';


?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<? if ($sistema_lingua=='ES') { ?>
	<title> Busca repuesto por la lista básica ... </title>
<? } else { ?>
	<title> Pesquisa Peças pela Lista Básica ... </title>
<? } ?>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

<?php include "javascript_calendario_new.php"; ?>

</head>


<style>
	.Div{
		BORDER-RIGHT:     #6699CC 1px solid;
		BORDER-TOP:       #6699CC 1px solid;
		BORDER-LEFT:      #6699CC 1px solid;
		BORDER-BOTTOM:    #6699CC 1px solid;
		FONT:             10pt Arial ;
		COLOR:            #000;
		BACKGROUND-COLOR: #FfFfFF;
	}
</style>

<? $onBlur = "onblur=\"setTimeout('window.close()',10000);\""; ?>
<body leftmargin="0" >
<br>
<img src="../imagens/pesquisa_pecas<? if($sistema_lingua == "ES") echo "_es"; ?>.gif">

<?
$tipo = trim (strtolower ($_GET['tipo']));

$cond_produto =" 1=1 ";

echo "<div id='div_peca' style='display:none; Position:absolute; border: 1px solid #949494;background-color: #b8b7af;width:410px; heigth:400px'>";
	echo "</div>";


if ($tipo == "descricao") {
	$descricao = trim(strtoupper($_GET["descricao"]));
	$texto = ($sistema_lingua == "ES") ? 'Buscando por el <b>nombre del repuesto</b>' : 'Pesquisando por <b>descrição da peça</b>';?>
	<p style='font-family:Arial, Verdana, Times, Sans;font-size: 12px'><?=$texto?>
	<h4><i><? echo $descricao ?></i></h4>
	</p>
<?

		$sql =	"SELECT DISTINCT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,";
		$sql.= "						tbl_peca.descricao AS para_descricao
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
";
		$sql.= "
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
";
		$sql.= "
										tbl_peca_fora_linha.peca AS peca_fora_linha
								FROM (
										SELECT  tbl_peca.peca              ,
												tbl_peca.referencia        ,
												tbl_peca.descricao         ,
												tbl_peca.bloqueada_garantia
										FROM tbl_peca";
										$sql .= "
										WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_peca.ativo IS TRUE
										AND   tbl_peca.promocao_site";
										if (strlen($descricao) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%'))";
										$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				ORDER BY z.descricao";
	$res = pg_query($con,$sql);

	if (@pg_num_rows($res) == 0 and strlen($kit_peca_sim) == 0) {
		if($sistema_lingua == "ES") echo "Reupesto '$descricao' no encontrado <br>para el producto $produto_referencia";
		else                        echo "<h1>Peça '$descricao' não encontrada<br>para o produto $produto_referencia</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',10000);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "referencia") {
	$referencia = trim(strtoupper($_GET["peca"]));
	$referencia = str_replace(".","",$referencia);
	$referencia = str_replace(",","",$referencia);
	$referencia = str_replace("-","",$referencia);
	$referencia = str_replace("/","",$referencia);
	$referencia = str_replace(" ","",$referencia);

	echo "<p style='font-family:Arial, Verdana, Times, Sans;font-size:12px'>";
	echo ($sistema_lingua == "ES") ? "Buscando por <b>referencia</b>: " : "Pesquisando por <b>referência da peça</b>: ";
	echo "<i>$referencia</i></p>";
	echo "<br>";

	$res = pg_query ($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_fetch_result ($res,0,0);

		$sql =	"SELECT DISTINCT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,";
		$sql.= "				tbl_peca.descricao AS para_descricao
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
";
		$sql.= "				y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,";
		$sql.= "
										tbl_peca_fora_linha.peca AS peca_fora_linha
								FROM (
										SELECT  tbl_peca.peca				,
												tbl_peca.referencia			,
												tbl_peca.descricao			,
												tbl_peca.bloqueada_garantia	
										FROM tbl_peca ";
		$sql .= "					WHERE tbl_peca.fabrica = $login_fabrica
									AND   tbl_peca.ativo IS TRUE
									AND   tbl_peca.promocao_site ";
		if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.referencia_pesquisa)) ILIKE UPPER(TRIM('%$referencia%'))";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				ORDER BY z.descricao";
	#echo nl2br($sql);exit;
	$res = @pg_query($con,$sql);

	if (@pg_num_rows($res) == 0) {
		if($sistema_lingua == "ES") echo "<h1>Repuesto '$referencia' no encontrado <br>para el producto $produto_referencia</h1>";
			else  echo "<h1>Peça '$referencia' não encontrada<br>para o produto $produto_referencia</h1>";
		echo "<script language='JavaScript'>";
		echo "setTimeout('window.close()',10000);";
		echo "</script>";
		exit;
	}
}



echo "<script language='JavaScript'>\n";
echo "<!--\n";
echo "this.focus();\n";
echo "// -->\n";
echo "</script>\n";

$contador = 999;

$num_pecas = pg_num_rows($res);

echo "<table width='100%' border='1'>";

for ( $i = 0 ; $i < $num_pecas; $i++ ) {
	$peca				= trim(@pg_fetch_result($res,$i,peca));
	$peca_referencia	= trim(@pg_fetch_result($res,$i,peca_referencia));
	$peca_descricao		= trim(@pg_fetch_result($res,$i,peca_descricao));
	$peca_descricao_js	= strtr($peca_descricao, array('"'=>'&rdquo;', "'"=>'&rsquo;'));  	$peca_fora_linha	= trim(@pg_fetch_result($res,$i,peca_fora_linha));
	$peca_para			= trim(@pg_fetch_result($res,$i,peca_para));
	$para				= trim(@pg_fetch_result($res,$i,para));
	$para_descricao		= trim(@pg_fetch_result($res,$i,para_descricao));
	$bloqueada_garantia	= trim(@pg_fetch_result($res,$i,bloqueada_garantia));

	$sql_idioma = "SELECT descricao AS peca_descricao FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";
	
	$res_idioma = @pg_query($con,$sql_idioma);
	if (@pg_num_rows($res_idioma) >0) {
		$peca_descricao    = pg_fetch_result($res_idioma,0,peca_descricao);
		$peca_descricao_js	= strtr($peca_descricao, array('"'=>'&rdquo;', "'"=>'&rsquo;'));  //07/05/2010 MLG - HD 235753
	}



	$resT = @pg_query($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");



		if (pg_num_rows($resT) == 1) {
		$tabela = pg_fetch_result ($resT,0,0);
		if (strlen($para) > 0) {
			$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
		}else{
			$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
		}
		$resT = pg_query($con,$sqlT);
		$preco = (pg_num_rows($resT) == 1) ? number_format (pg_fetch_result($resT,0,0),2,",",".") : "";
		}else{
			$preco = "";
		}
	


	$contador++;
	$cor = (strlen($peca_fora_linha) > 0) ? '#FFEEEE' : '#ffffff';

	echo "<tr bgcolor='$cor'>\n";
	echo "<td nowrap style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:black'>$peca_referencia</td>\n";

	echo "<td nowrap style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:black'>";

	if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0) {
		echo $peca_descricao;
	}else{
		echo '<a href="javascript: ';
		echo "referencia.value='$peca_referencia';descricao.value='$peca_descricao_js '; ";
		echo " preco.value='$preco';";
		echo ' this.close();"';
		echo " style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:blue'>$peca_descricao</a>";
		
	}
	echo "</td>\n";

	$sqlX =	"SELECT DISTINCT referencia, to_char(tbl_peca.previsao_entrega,'DD/MM/YYYY') AS previsao_entrega
			FROM tbl_peca
			WHERE referencia_pesquisa = UPPER('$peca_referencia')
			AND   fabrica = $login_fabrica
			AND   previsao_entrega NOTNULL;";
	$resX = pg_query($con,$sqlX);

	if (pg_num_rows($resX) == 0) {
		echo "<td nowrap>";
		if (strlen($peca_fora_linha) > 0) {
			echo "<span style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:black;font-weight:bold'>";
			
			echo ($sistema_lingua=='ES') ? "Obsoleta" : "Fora de linha";
			echo "</span>";
		}else{
			if (strlen($para) > 0 ) {
					if(strlen($peca_descricao)>0){ #HD 228968
						$sql_idioma = "SELECT tbl_peca_idioma.descricao AS peca_descricao FROM tbl_peca_idioma JOIN tbl_peca USING(peca) WHERE descricao = '$peca_descricao' AND upper(idioma) = '$sistema_lingua'";

						$res_idioma = @pg_query($con,$sql_idioma);
						if (@pg_num_rows($res_idioma) >0) {
							$peca_descricao  = preg_replace('/([\"|\'])/','\\$1',(pg_fetch_result($res_idioma,0,peca_descricao)));
						}
					}

					echo "<span style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:black;font-weight:bold'>Mudou Para</span>";
					echo " <a href=\"javascript: ";
					echo " referencia.value='$para'; descricao.value='$peca_descricao'; preco.value='$preco'; ";
					echo " this.close();";
					echo "\"style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>$para</a>";
				
			}else{
				echo "&nbsp;";
			}
		}
		echo "</td>\n";
		echo "<td nowrap align='right'>";

		$diretorio_verifica="/var/www/telecontrol/www/loja/media/produtos/";
		if(is_dir($diretorio_verifica) == true){
			if ($dh = opendir("/var/www/telecontrol/www/loja/media/produtos/")) {
			$contador=0;
				while (false !== ($filename = readdir($dh))) {
					if($contador == 1) break;
					if (strpos($filename,$peca) !== false){
						$contador++;
						$po = strlen($peca);
						if(substr($filename, 0,$po)==$peca){
							$filename2 = $peca."_pq";
							echo "<img src='/loja/media/produtos/$filename2' border='0'>";
						}
					}
				}
			}
			if($contador == 0){
				if ($dh = opendir("/var/www/telecontrol/www/loja/media/produtos/")) {
					$contador=0;
					while (false !== ($filename = readdir($dh))) {
						if($contador == 1) break;
						if(!empty($filename) and !empty($peca_referencia)) {
							if (strpos($filename,$peca_referencia) !== false){
								$contador++;
								$po = strlen($peca_referencia);
								if(substr($filename, 0,$po)==$peca_referencia){
									$filename2 = $peca_referencia."_pq";
									echo "<img src='/loja/media/produtos/$filename2' border='0'>";
								}
							}
						}
					}
				}
			}
		}

		echo "</td>\n";

	}else{
		echo "</tr>\n";
		echo "<tr>\n";
		$peca_previsao    = pg_fetch_result($resX,0,0);
		$previsao_entrega = pg_fetch_result($resX,0,1);

		$data_atual         = date("Ymd");
		$x_previsao_entrega = substr($previsao_entrega,6,4) . substr($previsao_entrega,3,2) . substr($previsao_entrega,0,2);
		echo "<td colspan='2' style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:black;font-weight:bold'>\n";
		if ($data_atual < $x_previsao_entrega) {
		if ($sistema_lingua=='ES') echo "Este repuesto estará disponible en: $previsao_entrega";
		else echo "Esta peça estará disponível em $previsao_entrega";
		echo "<br>";
		if ($sistema_lingua=='ES') echo "Para repuestos con plazo de entrega superior a 25 días, el fabricante tomará las medidas necesarias para atender al consumidor";
		else echo "Para as peças com prazo de fornecimento superior a 25 dias, a fábrica tomará as medidas necessárias para atendimento do consumidor";
		}
		echo "</td>\n";
	}

	echo "</tr>\n";
}

echo "</table>\n";
?>

</body>
</html>
