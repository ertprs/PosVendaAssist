<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$layout_menu = "callcenter";
$title = traduz("CONSULTA DE LINHA X FAMÍLIA DE PRODUTO");
if (in_array($login_fabrica, array(117))) {
	$title = traduz("CONSULTA DE MACRO - FAMÍLIA X FAMÍLIA DE PRODUTO");
}
include 'cabecalho_new.php';

if (in_array($login_fabrica,[11,172])) {
	$plugins = array("shadowbox");

	include ("plugin_loader.php");
}

?>

<BR>

<?

if (in_array($login_fabrica,[11,172])) { ?>
	
	<head>
		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	</head>
	
	<script type="text/javascript">
    	Shadowbox.init();
	</script>

<?php } 

###CARREGA AS LINHAS

if (strlen($_GET["familia"]) > 0)  $familia = trim($_GET["familia"]);
if (strlen($_POST["familia"]) > 0) $familia = trim($_POST["familia"]);

if (strlen($_GET["linha"]) > 0)  $linha = trim($_GET["linha"]);
if (strlen($_POST["linha"]) > 0) $linha = trim($_POST["linha"]);

if (in_array($login_fabrica,[11,172])) {
	if (strlen($_GET["linhas"]) > 0)  $linhas = trim($_GET["linhas"]);
	if (strlen($_POST["linhas"]) > 0) $linhas = trim($_POST["linhas"]);

	if (strlen($_GET["ativo"]) > 0)  $ativo = trim($_GET["ativo"]);
	if (strlen($_POST["ativo"]) > 0) $ativo = trim($_POST["ativo"]);

	if($ativo=='ativo') { // hd 55186
		$cond_1=" AND tbl_produto.ativo IS TRUE ";
	}elseif($ativo=='inativo'){
		$cond_1=" AND tbl_produto.ativo IS NOT TRUE ";
	}
}

if ($login_fabrica == 11 or $login_fabrica == 172) {
	$cond = strlen($linhas);
}

if(strlen($familia) == 0 AND strlen($linha) == 0 AND $cond == 0){

    if ($login_fabrica == 117) {
            $sql = "SELECT DISTINCT tbl_linha.linha,
                                            tbl_linha.nome AS nome_linha,
                                            tbl_linha.marca,
                                            tbl_marca.nome AS nome_marca
                FROM tbl_linha
                    JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                    JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
                    LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_linha.marca
                WHERE tbl_macro_linha_fabrica.fabrica = $login_fabrica
                    AND     tbl_linha.ativo = TRUE
                ORDER BY tbl_linha.nome;";
    } else {
		$sql = "SELECT    tbl_linha.linha             ,
						  tbl_linha.nome AS nome_linha,
						  tbl_linha.marca             ,
						  tbl_marca.nome AS nome_marca
				FROM      tbl_linha
				LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_linha.marca
				WHERE     tbl_linha.fabrica = $login_fabrica
				ORDER BY  tbl_marca.nome, tbl_linha.nome;";
	}

	$res = @pg_exec ($con,$sql);

	echo "<form class='form-search form-inline tc_formulario'>";

	if (in_array($login_fabrica, array(117))) {
		echo "<div class='titulo_tabela'>".traduz("Relação das Macro - Famílias e Famílias Cadastradas")."<br><font size='1'>".traduz("(Clique sobre o nome da macro - família ou da família para exibir os produtos relacionados)")."</font></div>";
	}else{
		echo "<div class='titulo_tabela'>".traduz("Relação das Linhas e Famílias Cadastradas")."<br><font size='1'>".traduz("(Clique sobre o nome da linha ou da família para exibir os produtos relacionados)")."</font></div>";
	}

	echo "<table class='table table-striped table-bordered table-large'>";
	if ($multimarca == 't') {
		echo "<tr><TD class = 'menu_top'>MARCA</TD></tr>";
	}
		
	for ($i = 0 ; $i <  pg_numrows($res) ; $i++){
		
		echo "<TR class='titulo_coluna'>";
		echo "<TH><font size='2'><a style='color: white;' href='$PHP_SELF?linha=".trim(pg_result($res,$i,linha))."' >".trim(pg_result($res,$i,nome_linha))."</a></font></th>";
		if ($multimarca == 't') echo "<Th align = 'left'><font size='1'>".trim(pg_result($res,$i,nome_marca))."</font></TH>";
		echo "</TR>";
		$sql = "SELECT DISTINCT
						tbl_linha.nome       ,
						tbl_familia.familia  ,
						tbl_familia.descricao
				FROM	tbl_produto
				JOIN	tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
									AND tbl_linha.linha = ".pg_result($res,$i,linha)."
									AND tbl_linha.fabrica = $login_fabrica
				JOIN	tbl_familia ON tbl_familia.familia = tbl_produto.familia
									AND tbl_familia.fabrica = $login_fabrica;";
		$resX = pg_exec ($con,$sql);
		$total = pg_numrows($resX);
		for ($x = 0 ; $x < $total ; $x++){
			if($x%2==0) $cor="#F7F5F0"; else $cor = "#F1F4FA";
		
			echo "<TR>";
			echo "<TD class='tac'>";
			echo "<font size='1'> &nbsp; &nbsp; <a href='$PHP_SELF?linha=".trim(pg_result($res,$i,linha))."&familia=".trim(pg_result($resX,$x,familia))."'>".trim(pg_result($resX,$x,descricao))."</a></font></TD>";
			echo "</TR>";
		}
		echo "<tr><TD> &nbsp;</TD></tr>";
	}
	
	if ($login_fabrica == 11 or $login_fabrica == 172) {
		echo "<TR>";
			echo "<TD align='left'><font size='1'><a href='$PHP_SELF?linhas=todas'>".traduz("Todas a Linhas e Famílias")."</a></font></TD>";
		echo "</TR>";
	}
	echo "</table></form>";
	echo "<br><br>";
}
if ($login_fabrica == 11 or $login_fabrica == 172) { 
	$ordenacao = " ORDER BY tbl_produto.referencia ";
	$data_cadastro_sql = "CASE WHEN tbl_produto.data_atualizacao notnull THEN to_char(tbl_produto.data_atualizacao::date, 'DD/MM/YYYY') ELSE to_char(tbl_produto.data_input::date, 'DD/MM/YYYY') END AS data_cadastro,";
}else{
	$ordenacao = " ORDER BY tbl_produto.descricao ";
	$data_cadastro_sql = "";
}

###CARREGA OD PRODUTOS DA LINHA
if(strlen($linhas) > 0){
	$sql = "SELECT    tbl_linha.nome AS nome_linha ,
					  tbl_produto.produto          ,
					  tbl_produto.referencia       ,
					  tbl_produto.referencia_fabrica ,
					  tbl_produto.descricao        ,
					  $data_cadastro_sql
					  tbl_produto.garantia         ,
					  tbl_produto.ativo            ,
					  tbl_produto.mao_de_obra
			FROM      tbl_produto
			LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE     tbl_linha.fabrica = $login_fabrica
			$cond_1
			$ordenacao;";
	$resZ = @pg_exec ($con,$sql);

	$titulo = traduz("Todas as Linhas e Famílias");
}

if(strlen($linha) > 0 AND strlen($familia) == 0){
	$sql = "SELECT    tbl_linha.nome AS nome_linha ,
					  tbl_produto.produto          ,
					  tbl_produto.referencia       ,
					  tbl_produto.referencia_fabrica,
					  tbl_produto.descricao        ,
					  $data_cadastro_sql
					  tbl_produto.garantia         ,
					  tbl_produto.ativo            ,
					  tbl_produto.mao_de_obra
			FROM      tbl_produto
			LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE     tbl_linha.fabrica = $login_fabrica
			AND       tbl_produto.linha = $linha
			$cond_1
			$ordenacao;";
	$resZ = @pg_exec ($con,$sql);

	$titulo = traduz("Linha ").@pg_result($resZ,0,nome_linha);
}

if(strlen($familia) > 0){
	$sql = "SELECT    tbl_familia.descricao AS nome_familia,
					  tbl_produto.produto                  ,
					  tbl_produto.referencia               ,
					  tbl_produto.referencia_fabrica,
					  tbl_produto.descricao                ,
					  $data_cadastro_sql
					  tbl_produto.garantia                 ,
					  tbl_produto.ativo                    ,
					  tbl_produto.mao_de_obra
			FROM      tbl_produto
			LEFT JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
			WHERE     tbl_familia.fabrica = $login_fabrica
			AND       tbl_produto.familia = $familia
			$cond_1
			$ordenacao;";
	$resZ = @pg_exec ($con,$sql);

	$titulo = traduz("Família ").@pg_result($resZ,0,nome_familia);

	$sql = "SELECT	tbl_familia.mao_de_obra_adicional_distribuidor
			FROM	tbl_familia
			WHERE	tbl_familia.fabrica = $login_fabrica
			AND		tbl_familia.familia = $familia";
	$resL = pg_exec ($con,$sql);
	$mao_de_obra_adicional_distribuidor = @pg_result($resL,0,mao_de_obra_adicional_distribuidor);

}
if(strlen($familia) > 0 OR strlen($linha) > 0 OR strlen($linhas) > 0){
	if(@pg_numrows($resZ) > 0){
		if($login_fabrica == 171) {
			$colspanTitulo = "9";
		} elseif (in_array($login_fabrica,[11,172])) {
			$colspanTitulo = "9";
		} else {
			$colspanTitulo = "5";
		}
		if($login_fabrica==11 or $login_fabrica == 172) { // HD 55186
			echo "<br>";
			echo "<table border='0' cellspacing='0' cellpadding='0'>";
			echo "<tr height='18'>";
			echo "<td width='18' ><img title='Ativo' src='imagens/status_vermelho.png'></td>";
			echo "<td align='left'><font size='1'><b>&nbsp;  <a href=$PHP_SELF?linha=$linha&familia=$familia&linhas=$linhas&ativo=inativo>";
			echo traduz("Listar produtos inativos");
			echo "</a></b></font></td><BR>";
			echo "</tr>";
			echo "<tr height='18'>";
			echo "<td width='18'><img title='Ativo' src='imagens/status_verde.png'></td>";
			echo "<td align='left'><font size='1'><b>&nbsp;  <a href='$PHP_SELF?linha=$linha&familia=$familia&linhas=$linhas&ativo=ativo'>";
			echo traduz("Listar produtos ativos");
			echo "</a></b></font></td>";
			echo "</tr>";
			echo "<tr height='18'>";
			echo "<td width='18'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp;  <a href='$PHP_SELF?linha=$linha&familia=$familia&linhas=$linhas'>";
			echo traduz("Listar todos");
			echo "</a></b></font></td>";
			echo "</tr>";
			echo "</table>";
			echo "</br>";
		}

		echo "<TABLE class='table table-striped table-bordered table-large'>";
		echo "<thead>";
		echo "<TR>";
		echo "<Th class='titulo_tabela' colspan='$colspanTitulo'> ".traduz("Produtos na %", null, null, [$titulo])." </Th>";
		echo "</TR>";
		echo "<TR class='tabela_titulo'>";
		echo "<TD class='menu_top' colspan='5'>".traduz("Adicional Mão-de-Obra Distribuidor para a Família: ").number_format($mao_de_obra_adicional_distribuidor,2,",",".")."</TD>";
		echo "</TR>";

		echo "<TR>";
		if (in_array($login_fabrica,[11,172])) {
			echo "<TH class='menu_top'>".traduz("Status")."</TH>";	
			echo "<TH class='menu_top'>".traduz("Referência")."</TH>";
			echo "<TH class='menu_top'>".traduz("Data Cadastro")."</TH>";
			echo "<TH class='menu_top'>".traduz("Descrição")."</TH>";
			echo "<TH class='menu_top'>".traduz("Garantia")."</TH>";
			echo "<TH class='menu_top'>".traduz("Mão-de-Obra")."</TH>";
			echo "<TH class='menu_top'>".traduz("Visualizar Log")."</TH>";
		} else {
			echo "<Th class='menu_top'>&nbsp;</TH>";
			if($login_fabrica == 171) {
				echo "<TH class='menu_top'>".traduz("Referência Fábrica")."</TH>";
			}
			echo "<TH class='menu_top'>".traduz("Referência")."</TH>";
			echo "<TH class='menu_top'>".traduz("Descrição")."</TH>";
			echo "<TH class='menu_top'>".traduz("Garantia")."</TH>";
			echo "<TH class='menu_top'>".traduz("Mão-de-Obra")."</TH>";
		}
		echo "</TR>";
		echo "</thead>";

		$data = date('Ymd');
		$arquivo_nome     = "linha_X_familia_produto_xls-$login_fabrica-$data.xls";
		$arquivo ="xls/linha_X_familia_produto_xls-$login_fabrica-$data.xls";
		$fp = fopen($arquivo, "w");

		fputs($fp, "<TABLE width = '700' align = 'center' border = '0' cellspacing='1' cellpadding='1' class='tabela'>");
		fputs($fp, "<TR>");
		if (in_array($login_fabrica,[11,172])) {
			fputs($fp, "<TD class='titulo_tabela' colspan='8' align='center' >.:: ".traduz("Produtos na")." $titulo ::.</TD>");
		} else {
			fputs($fp, "<TD class='titulo_tabela' colspan='4' align='center' >.:: ".traduz("Produtos na")." $titulo ::.</TD>");
		}
		fputs($fp, "</TR>");
		fputs($fp, "<TR class='subtitulo'>");
		fputs($fp, "<TD  colspan='4' align='center' >".traduz("Adicional Mão-de-Obra Distribuidor para a Família: ").number_format($mao_de_obra_adicional_distribuidor,2,",",".")."</TD>");
		fputs($fp, "</TR>");

		fputs($fp, "<TR class='titulo_coluna'>");
		if (in_array($login_fabrica,[11,172])) {
			fputs($fp, "<TD align='center'>".traduz("Status")."</TD>");
			fputs($fp, "<TD align='center'>".traduz("Referência")."</TD>");
			fputs($fp, "<TD align='center'>".traduz("Data Cadastro<")."/TD>");	
			fputs($fp, "<TD align='center'>".traduz("Descrição")."</TD>");
			fputs($fp, "<TD align='center'>".traduz("Garantia")."</TD>");
			fputs($fp, "<TD align='center'>".traduz("Mão-de-Obra")."</TD>");
		} else {
			if($login_fabrica == 171) {
				fputs($fp, "<TD align='center'>".traduz("Referência Fábrica")."</TD>");
			}
			fputs($fp, "<TD align='center'>".traduz("Referência")."</TD>");
			fputs($fp, "<TD align='center'>".traduz("Descrição")."</TD>");
			fputs($fp, "<TD align='center'>".traduz("Garantia")."</TD>");
			fputs($fp, "<TD align='center'>".traduz("Mão-de-Obra")."</TD>");
		}
		
		fputs($fp, "</TR>");

		for ($i = 0 ; $i < @pg_numrows($resZ) ; $i++){
			$produto           = trim(@pg_result($resZ,$i,produto));
			$referencia        = trim(@pg_result($resZ,$i,referencia));
			$referencia_fabrica= trim(@pg_result($resZ,$i,referencia_fabrica));
			$descricao         = trim(@pg_result($resZ,$i,descricao));
			if (in_array($login_fabrica,[11,172])) {
				$data_cadastro = trim(pg_fetch_result($resZ, $i, data_cadastro));
			}
			$garantia          = trim(@pg_result($resZ,$i,garantia));
			$ativo             = trim(@pg_result($resZ,$i,ativo));
			$mao_de_obra       = trim(@pg_result($resZ,$i,mao_de_obra));

			if($ativo =='t') $bolinha='verde';
			else             $bolinha='vermelho';
			if($i%2==0) $cor="#F7F5F0"; else $cor = "#F1F4FA";
			echo "<TR rel='$bolinha' bgcolor='$cor'>";
			echo "<td align='center'>";
			if ($ativo <> 't') echo "<img src='imagens/status_".$bolinha.".png' border='0' alt='Inativo'>";
			else               echo "<img src='imagens/status_".$bolinha.".png' border='0' alt='Ativo'>";
			echo "</td>";

			if($login_fabrica == 171) {
				echo "<TD align='left'><font size='1'>$referencia_fabrica</font></TD>";
			}

			echo "<TD align='left'><font size='1'>$referencia</font></TD>";
			if (in_array($login_fabrica,[11,172])) {
				echo "<TD align='left'><font size='1'>$data_cadastro</font></TD>";
			}
			echo "<TD align='left'><font size='1'><a href='produto_consulta.php?produto=$produto'>$descricao</a></font></TD>";
			echo "<TD align='center'><font size='1'>$garantia meses</font></TD>";
			echo "<TD align='right'><font size='1' ";
			if($mao_de_obra <= 0) echo "color=#ff0000";
			echo ">".number_format($mao_de_obra,2,",",".")."</font></TD>";
			if (in_array($login_fabrica,[11,172])) {
				echo "<TD align='left'><font size='1'><a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_produto&id=$produto'>Visualizar Log Auditor</a></font></TD>";
			}

			echo "</TR>";

			fputs($fp, "<TR>");
			if (in_array($login_fabrica,[11,172])) {
				if ($ativo <> 't') {
					$st = traduz('Inativo');
				} else {
					$st = traduz('Ativo');
				}   
				fputs($fp, "<TD align='left' bgcolor='#FFFFFF'><font size='1'>$st</font></TD>");
			
				fputs($fp, "<TD align='left' bgcolor='#FFFFFF'><font size='1'>$referencia</font></TD>");
				fputs($fp, "<TD align='left' bgcolor='#FFFFFF'><font size='1'>$data_cadastro</font></TD>");
				fputs($fp, "<TD align='left' bgcolor='#FFFFFF'><font size='1'><a href='produto_consulta.php?produto=$produto'>$descricao</a></font></TD>");
				fputs($fp, "<TD align='center' bgcolor='#FFFFFF'><font size='1'>$garantia meses</font></TD>");
				fputs($fp, "<TD align='right' bgcolor='#FFFFFF'><font size='1' ");
				if($mao_de_obra <= 0) fputs($fp, "color=#ff0000");
				fputs($fp, ">".number_format($mao_de_obra,2,",",".")."</font></TD>");
			} else {
				fputs($fp, "<TD align='left' bgcolor='#FFFFFF'><font size='1'>$referencia</font></TD>");
				fputs($fp, "<TD align='left' bgcolor='#FFFFFF'><font size='1'><a href='produto_consulta.php?produto=$produto'>$descricao</a></font></TD>");
				fputs($fp, "<TD align='center' bgcolor='#FFFFFF'><font size='1'>$garantia meses</font></TD>");
				fputs($fp, "<TD align='right' bgcolor='#FFFFFF'><font size='1' ");
				if($mao_de_obra <= 0) fputs($fp, "color=#ff0000");
				fputs($fp, ">".number_format($mao_de_obra,2,",",".")."</font></TD>");	
			}
			
			fputs($fp, "</TR>");
		}
	
		echo "</TABLE>";
		fputs($fp, "</TABLE>");
		if ($login_fabrica == 11 or $login_fabrica == 172) {
			echo "<br/>";
			echo "<center><a class='btn' style='color: black;' href='xls/$arquivo_nome'><font face='Arial, Verdana, Times, Sans' size='2'>".traduz("Clique aqui para fazer o download do arquivo .xls")."</font></a></center> <br/><br/>";
		}
	}else{
		echo "<font size='2' face='verdana' color='#63798D'><b>".traduz('PRODUTOS NÃO CADASTRADOS')."</b></font>";
	}
	echo "<br>";
	echo "<input type='button' value='".traduz('Voltar')."' class='btn btn-primary' onClick='javascript:window.location=\"$PHP_SELF\"'>";
}
include("rodape.php");
?>
