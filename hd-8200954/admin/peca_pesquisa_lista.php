<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include_once "../class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$caminho = "imagens_pecas";

$ped_peca_garantia = $_GET['exibe'];
$peca_lib_garantia = 'f';

if (preg_match("os_item.php", $ped_peca_garantia)) {
   $peca_lib_garantia = 't';
}


if ($login_fabrica <> 10) {
	$caminho = $caminho."/".$login_fabrica;
}?>
<!DOCTYPE HTML public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peças pela Lista Básica ... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css">
<link href="css/posicionamento.css" rel="stylesheet" type="text/css">
<style type="text/css">
	body {
		margin-left: 0px;
		margin-right: 0px;
	}
</style>
</head>

<!-- <body onblur="setTimeout('window.close()',5500);"> -->
<body >
<br />
<img src="imagens/pesquisa_pecas.gif"><?php

if ($login_fabrica == 1) {
	$cond_libera_garantia = " /*AND tbl_peca_fora_linha.libera_garantia IS TRUE*/";
}

$defeito_constatado = $_GET['defeito_constatado'];

if($login_fabrica == 134){
	$peca_posicao = $_GET["posicao"];
}

if ($login_fabrica == 40) {

	if( $defeito_constatado == "") {
		echo "<script>alert('Selecione Defeito Constatado'); setTimeout('self.close();',1000)</script>";
		exit;
	}

	$cond_masterfrio  = " LEFT JOIN tbl_peca_defeito_constatado ON (tbl_peca_defeito_constatado.peca = tbl_peca.peca) ";
	$where_masterfrio = " AND (tbl_peca_defeito_constatado.defeito_constatado = $defeito_constatado OR tbl_peca_defeito_constatado.defeito_constatado IS NULL) ";

}

if($login_fabrica == 134){
		$codigo_defeito = json_decode(str_replace("\\","",trim($_GET['codigo_defeitos'])));
		foreach ( $codigo_defeito as $value) {
			if($value != ""){
				$codigo_defeito_aux[] = "'".$value."'";
			}
		}
		$codigo_defeito = implode(',', $codigo_defeito_aux);
		$sql_join = " join tbl_peca_defeito_constatado using(peca)
										join tbl_defeito_constatado using(defeito_constatado) ";

		$sql_cond = " AND   tbl_defeito_constatado.codigo in($codigo_defeito) ";
	}

$tipo = trim (strtolower ($_GET['tipo']));

if (strlen($_GET['produto']) > 0) {

	$produto_referencia = trim($_GET['produto']);
	$produto_referencia = str_replace(".","",$produto_referencia);
	$produto_referencia = str_replace(",","",$produto_referencia);
	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);

	$versao_produto = trim(strtoupper($_GET['versao_produto']));

	if ($usa_versao_produto and strlen($versao_produto)) {
		$cond_versao = " AND (tbl_lista_basica.type IS NULL OR tbl_lista_basica.type = '$versao_produto')";
	}

	$sql = "SELECT tbl_produto.produto, tbl_produto.descricao
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia') ";

	if (strlen(trim($_GET["voltagem"])) > 0) $sql .= " AND UPPER(tbl_produto.voltagem) = UPPER('".trim($_GET["voltagem"])."') ";

	$sql .=	"   AND tbl_linha.fabrica = $login_fabrica
				AND tbl_produto.ativo IS TRUE";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		$produto_descricao = pg_result ($res,0,descricao);
		$produto = pg_result ($res,0,produto);
	} else {
		$produto = '';
	}

}

if ($tipo == "tudo") {

	$descricao = trim(strtoupper($_GET["descricao"]));

	echo "<h4>Pesquisando toda a lista básica do produto: <br><i>$produto_referencia - $produto_descricao</i></h4>";

	echo "<br><br>";

	$res  = pg_exec($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_result($res, 0, 0);

	if ($qtde > 0 AND strlen($produto) > 0) {

		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.type                                ,
						z.posicao                             ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.type               ,
								y.posicao            ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.type                                      ,
										x.posicao                                   ,
										tbl_peca_fora_linha.peca AS peca_fora_linha
								FROM (
										SELECT  tbl_peca.peca            ,
												tbl_peca.referencia      ,
												tbl_peca.descricao       ,
												tbl_lista_basica.type    ,
												tbl_lista_basica.posicao
										FROM tbl_peca
										JOIN tbl_lista_basica USING (peca)
										JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto
										WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_produto.produto = $produto
										AND   tbl_peca.ativo IS TRUE
										AND   tbl_produto.ativo IS TRUE
										$cond_versao\n";

		if (strlen($descricao) > 0) $sql .= " AND ( UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%')) OR UPPER(TRIM(tbl_peca.referencia)) ILIKE UPPER(TRIM('%$descricao%')) )";

		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
								 $cond_libera_garantia
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para AND tbl_peca.fabrica = $login_fabrica ";

		if($login_fabrica==6) {
			$sql.=" ORDER BY z.referencia, z.descricao";
		} else {
			$sql.=" ORDER BY z.descricao";
		}

	} else {

		$sql = "SELECT z.peca                                ,
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
										WHERE fabrica = $login_fabrica
										AND ativo IS TRUE";
		if (strlen($descricao) > 0) $sql .= " AND ( UPPER(TRIM(descricao)) ILIKE UPPER(TRIM('%$descricao%')) OR UPPER(TRIM(referencia)) ILIKE UPPER(TRIM('%$descricao%')) )";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
								$cond_libera_garantia
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para AND tbl_peca.fabrica = $login_fabrica ";
				if($login_fabrica==6) {
					$sql.=" ORDER BY z.referencia, z.descricao";
				} else {
					$sql.=" ORDER BY z.descricao";
				}
	}

	$res = pg_exec($con, $sql);

	if (@pg_numrows($res) == 0) {
		echo "<h1>Nenhuma lista básica de peças encontrada para este produto</h1>";
		echo "<script language='JavaScript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}

}

if ($tipo == "descricao") {

	$descricao = trim(strtoupper($_GET["descricao"]));

	echo "<h4>Pesquisando por <b>descrição da peça</b>: <i>$descricao</i></h4>";
	echo "<p>";

	$res  = pg_exec($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_result($res,0,0);

	if ($qtde > 0 AND strlen($produto) > 0 ) {

		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.type                                ,
						z.posicao                             ,
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
								y.type               ,
								y.posicao            ,
								y.peca_fora_linha    ,
								y.libera_garantia	 ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.type                                      ,
										x.posicao                                   ,
										tbl_peca_fora_linha.peca AS peca_fora_linha ,
										tbl_peca_fora_linha.libera_garantia
								FROM (
										SELECT  tbl_peca.peca            ,
												tbl_peca.referencia      ,
												tbl_peca.descricao       ,
												tbl_lista_basica.type    ,
												tbl_lista_basica.posicao
										FROM tbl_peca
										JOIN tbl_lista_basica USING (peca)
										$cond_masterfrio
										JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto
										WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_produto.produto = $produto
										AND   tbl_peca.ativo IS TRUE
										AND   tbl_produto.ativo IS TRUE
										$cond_versao
										$where_masterfrio ";

		if (strlen($descricao) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%'))";

		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
								$cond_libera_garantia
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para and tbl_peca.fabrica = $login_fabrica ";

		if ($login_fabrica == 6) {
			$sql.=" ORDER BY z.referencia, z.descricao";
		} else {
			$sql.=" ORDER BY z.descricao";
		}

	} else {

		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
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
								y.peca_fora_linha    ,
								y.libera_garantia    ,
  								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										tbl_peca_fora_linha.peca AS peca_fora_linha ,
										tbl_peca_fora_linha.libera_garantia
								FROM (
										SELECT  tbl_peca.peca       ,
												tbl_peca.referencia ,
												tbl_peca.descricao
										FROM tbl_peca
										WHERE fabrica = $login_fabrica
										AND ativo IS TRUE";

		if (strlen($descricao) > 0) $sql .= " AND UPPER(TRIM(descricao)) ILIKE UPPER(TRIM('%$descricao%'))";

		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
								$cond_libera_garantia
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
						$cond_libera_garantia
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para and tbl_peca.fabrica = $login_fabrica ";

				if($login_fabrica==6) {
					$sql.=" ORDER BY z.referencia, z.descricao";
				} else {
					$sql.=" ORDER BY z.descricao";
				}

	}

	$res = pg_exec($con,$sql);

	if (@pg_numrows($res) == 0) {
		echo "<center>";
		if ($login_fabrica == 1) {
			echo "<h2>Item '$descricao' não existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o código correto</h2>";
		}else{
			echo "<h1>Peça '$descricao' não encontrada<br>para o produto $produto_referencia</h1>";
		}
		echo "</center>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
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

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peça</b>: <i>$referencia</i></font>";

	echo "<br><br>";

	$res  = pg_exec($con, "SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_result($res, 0, 0);

	// if($login_fabrica == 134 && qtde > 0 and strlen($produto) > 0){
	// 	$sql = "SELECT tbl_peca.peca, tbl_peca.referencia as peca_referencia, tbl_peca.descricao as peca_descricao
	// 		from tbl_peca join tbl_peca_defeito_constatado using(peca)
	// 		join tbl_defeito_constatado using(defeito_constatado)
	// 		join tbl_lista_basica using(peca)
	// 		where tbl_peca_defeito_constatado.fabrica = 134
	// 		and tbl_defeito_constatado.codigo in($codigo_defeito)
	// 		and tbl_lista_basica.produto = $produto
	// 		and UPPER(TRIM(tbl_peca.referencia_pesquisa)) ilike UPPER(TRIM('%$referencia%'));";

	// 		echo $sql;exit;

	// }else
	if ($qtde > 0 and strlen($produto) > 0) {

		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.type                                ,
						z.posicao                             ,
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
								y.type               ,
								y.posicao            ,
								y.peca_fora_linha    ,
								y.libera_garantia    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.type                                      ,
										x.posicao                                   ,
										tbl_peca_fora_linha.peca AS peca_fora_linha ,
										tbl_peca_fora_linha.libera_garantia
								FROM (
										SELECT  tbl_peca.peca            ,
												tbl_peca.referencia      ,
												tbl_peca.descricao       ,
												tbl_lista_basica.type    ,
												tbl_lista_basica.posicao
										FROM tbl_peca
										JOIN tbl_lista_basica USING (peca)
										$cond_masterfrio
										$sql_join
										JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto
										WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_produto.produto = $produto
										AND   tbl_peca.ativo IS TRUE
										AND   tbl_produto.ativo IS TRUE
										$sql_cond
										$cond_versao
										$where_masterfrio";
		if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.referencia_pesquisa)) ILIKE UPPER(TRIM('%$referencia%'))";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
								$cond_libera_garantia
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para and tbl_peca.fabrica = $login_fabrica ";

				if($login_fabrica==6) {
					$sql.=" ORDER BY z.referencia, z.descricao";
				} else {
					$sql.=" ORDER BY z.descricao";
				}
	}else{
		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
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
								y.peca_fora_linha    ,
								y.libera_garantia    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										tbl_peca_fora_linha.peca AS peca_fora_linha ,
										tbl_peca_fora_linha.libera_garantia
								FROM (
										SELECT  tbl_peca.peca       ,
												tbl_peca.referencia ,
												tbl_peca.descricao
										FROM tbl_peca
										WHERE fabrica = $login_fabrica
										AND ativo IS TRUE";
		if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(referencia_pesquisa)) ILIKE UPPER(TRIM('%$referencia%'))";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
								$cond_libera_garantia
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para and tbl_peca.fabrica=$login_fabrica";
				if($login_fabrica==6) {
					$sql.=" ORDER BY z.referencia, z.descricao";
				} else {
					$sql.=" ORDER BY z.descricao";
				}
	}
	$res = pg_exec($con,$sql);

	if (@pg_numrows($res) == 0) {

		echo "<center>";
		if ($login_fabrica == 1) {
			echo "<h2>Item '$referencia' não existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o código correto</h2>";
		} else {
			echo "<h1>Peça '$referencia' não encontrada<br>para o produto $produto_referencia</h1>";
		}

		echo "</center>";

		echo "<script language='javascript'>";
			echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;

	}

}

echo "<script language='JavaScript'>\n";
echo "<!--\n";
echo "this.focus();\n";
echo "// -->\n";
echo "</script>\n";

echo "<table width='100%' border='0'>\n";

if ($login_fabrica == 7) {

	$posto    = $_GET['posto'];
	$desconto = $_GET['desconto'];

	$sqlF = "SELECT tabela
				 FROM tbl_posto_linha
				 JOIN tbl_tabela USING(tabela)
			   WHERE posto = $posto
			   AND linha in(SELECT linha FROM tbl_produto WHERE produto = $produto)
			   AND tbl_tabela.fabrica = $login_fabrica";

	$resF = pg_exec($con,$sqlF);

	if (pg_numrows($resF) == 1) {
		$tabela = pg_result ($resF,0,0);
	}

}

if($telecontrol_distrib){
	$tabela = $_GET['tabela'];
}else{
	$tabela = "";
}

for ($i = 0; $i < pg_numrows($res); $i++) {

	$peca                   = trim(pg_result($res, $i, 'peca'));
	$peca_referencia        = trim(pg_result($res, $i, 'peca_referencia'));
	$peca_descricao         = trim(pg_result($res, $i, 'peca_descricao'));
	$peca_descricao         = str_replace('"', '', $peca_descricao);
	$type                   = trim(pg_result($res, $i, 'type'));
	$posicao                = trim(pg_result($res, $i, 'posicao'));
	$peca_fora_linha        = trim(pg_result($res, $i, 'peca_fora_linha'));
	$peca_para              = trim(pg_result($res, $i, 'peca_para'));
	$para                   = trim(pg_result($res, $i, 'para'));
	$para_descricao         = trim(pg_result($res, $i, 'para_descricao'));
	$cond_liberado_garantia = pg_result($res, $i, 'libera_garantia');
	$descricao              = str_replace('"', '', $descricao);

	if($telecontrol_distrib){
		$where = " and tabela = $tabela ";
	}else{
		$where = "";
	}

	$sql = "SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica $where";

	$resT = pg_exec($con,$sql);

	if (pg_numrows($resT) == 1) {

		$tabela = pg_result($resT, 0, 0);

		if (strlen($para) > 0) {
			$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
		} else {
			$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
		}

		$resT = pg_exec($con, $sqlT);

		if (pg_numrows($resT) == 1) {
			$preco = number_format(pg_result($resT,0,0),2,",",".");
		} else {
			$preco = "";
		}


	} else {

		if ($login_fabrica == 7) {

			if (pg_numrows($resF) == 1) {

				$sqlP = "SELECT preco from tbl_tabela_item WHERE peca = $peca AND tabela = $tabela";
				$resP = pg_query($con,$sqlP);

				if (pg_numrows($resP) > 0) {

					$preco = pg_result ($resP,0,0);
					$preco = $preco - (($desconto * $preco) / 100);
					$preco = number_format ($preco,2,",",".");

				}

			} else {

				$preco = "";

			}

		} else {

			$preco = "";

		}

	}

	echo "<tr>\n";

	if ($login_fabrica == 14) {
		echo "<td nowrap>";
		echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$posicao</font>";
		echo "</td>\n";
	}

	echo "<td nowrap>";
		echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$peca_referencia</font>";
	echo "</td>\n";

	echo "<td nowrap>";


	if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0) {

		if ($cond_liberado_garantia == 't' AND $peca_lib_garantia == 't' ) {//HD 729181
			echo "<a href=\"javascript: referencia.value='$peca_referencia'; descricao.value='$peca_descricao';";
			echo " preco.value='$preco';";
			echo " window.opener.busca_preco({$posicao_linha}); this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$peca_descricao</font></a>";
		}else{
			 echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$peca_descricao</font>";
		}
	}elseif($login_fabrica == 134){
		echo "<a href=\"javascript: referencia.value='$peca_referencia'; descricao.value='$peca_descricao'; ";		
		echo " window.opener.verificaEstoqueRecompra('$peca_referencia', '$peca_posicao');   this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$peca_descricao</font></a>";
	}else {

		echo "<a href=\"javascript: referencia.value='$peca_referencia'; descricao.value='$peca_descricao'; ";
		if ($login_fabrica == 14) echo " posicao.value='$posicao';";
		else                      echo " preco.value='$preco';";
		if ($login_fabrica == 74) echo " window.opener.adiciona_linha(); ";


		echo " window.opener.busca_preco({$posicao_linha}); this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$peca_descricao</font></a>";
	}

	echo "</td>\n";

	if ($login_fabrica == 1) {
		echo "<td nowrap>";
			echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$type</font>";
		echo "</td>\n";
	}

	echo "<td nowrap>";

		#Matheus - inseri a restrição pois estava dando erro em outras fabricas a britania tem imagens mas eh de um modo diferente
		if ($login_fabrica == 1 OR $login_fabrica == 4 OR $login_fabrica == 5 OR $login_fabrica == 11 OR $login_fabrica == 35 OR $login_fabrica == 45 OR $login_fabrica == 51 OR $login_fabrica == 50) {

		    $xpecas = $tDocs->getDocumentsByRef($peca, "peca");
		    if (!empty($xpecas->attachListInfo)) {

				$a = 1;
				foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
				    $fotoPeca = $vFoto["link"];
				    if ($a == 1){break;}
				}
				echo "<img src='$fotoPeca' width='50' border='0'><input type='hidden' name='peca_imagem' value='$fotoPeca'>";
		    } else {

				if ($dh = opendir("../".$caminho."/media/")) { #estamos pesquisando na media pq pdf não grava no diretorio pequeno
					while (false !== ($filename = readdir($dh))) {
						//echo $filename;
						if($contador == 1) break;
						$xpeca = $peca.'.';
						if (strpos($filename,$peca) !== false){
							$po = strlen($xpeca);
							if(substr($filename, 0,$po)==$xpeca){
								$contador++;

								?>
									<img src='../<?echo $caminho;?>/pequena/<?echo $filename; ?>' border='0'>
									<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>'>
								<?
							}
						}
					}

				}

			}

		}


	echo "</td>\n";

	$posicao_linha = $_REQUEST["posicao"];

	$sqlX =	"SELECT referencia, to_char(tbl_peca.previsao_entrega,'DD/MM/YYYY') AS previsao_entrega
				FROM tbl_peca
				WHERE referencia_pesquisa = UPPER('$peca_referencia')
				AND   fabrica = $login_fabrica;";

	$resX = pg_exec($con,$sqlX);

	if (pg_numrows($resX) == 0) {

		echo "<td nowrap>";

		if ($login_fabrica == 1) {

			if (strlen($peca_fora_linha) > 0) {
				echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>";
				echo "É obsoleta, não é mais fornecida";
				echo "</b></font>";
			}

		}

		if (strlen($peca_fora_linha) > 0 && $login_fabrica <> 1) {

			echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>";
			echo "Fora de linha";
			echo "</b></font>";

		} else {

			if (strlen($para) > 0) {

				echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>Mudou Para:</b></font>";
				echo " <a href=\"javascript: referencia.value='$para'; descricao.value='$para_descricao'; preco.value='$preco'; window.opener.busca_preco({$posicao_linha}); this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$para</font></a>";

			} else {
				echo "&nbsp;";
			}

		}

		echo "</td>\n";

	} else {

		echo "</tr>\n";
		echo "<tr>\n";
		$peca_previsao    = pg_result($resX, 0, 0);
		$previsao_entrega = pg_result($resX, 0, 1);

		$data_atual         = date("Ymd");
		$x_previsao_entrega = substr($previsao_entrega,6,4) . substr($previsao_entrega,3,2) . substr($previsao_entrega,0,2);

		echo "<td colspan='2'>\n";

		if ($data_atual < $x_previsao_entrega) {

			echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>";
			echo "Esta peça estará disponível em $previsao_entrega";
			echo "<br>";
			echo "Para as peças com prazo de fornecimento superior a 25 dias, a fábrica tomará as medidas necessárias para atendimento do consumidor";
			echo "</b></font>";

		}

		echo "</td>\n";

	}

	echo "</tr>\n";

}

echo "</table>\n";?>

</body>
</html>
