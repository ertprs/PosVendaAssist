<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include "monitora_cabecalho.php";
$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];
$produto      = $_GET['produto'];
$linha        = $_GET['linha'];
$estado       = $_GET['estado'];
$familia      = $_GET['familia'];
$posto        = $_GET['posto'];
$tipo         = $_GET['tipo'];
$tipo_os      = $_GET['tipo_os'];
$cond_5       = " 1=1 ";


if($login_fabrica == 24){
	$matriz_filial = $_GET["matriz_filial"];
	if(strlen($matriz_filial)>0){
		$cond_matriz_filial = " AND substr(tbl_os.serie,length(tbl_os.serie) - 1, 2) = '$matriz_filial' ";
	}
}

if (strlen ($tipo_os)  > 0) $cond_5 = " tbl_os.consumidor_revenda = '$tipo_os' ";

$cond_conversor = "";
$param_conversor = "";

if (!empty($_GET["dcg"]) and $_GET["dcg"] == "true") {
    $cond_conversor = " AND tbl_os.defeito_constatado <> 23118 AND tbl_os.solucao_os <> 4504 ";
    $param_conversor = "&dcg=true";
}

if($tipo=="produto"){
	$sql = "SELECT tbl_produto.descricao 
			FROM tbl_produto
			JOIN tbl_familia using(familia)
			WHERE produto = $produto
			and fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	$descricao_produto = pg_fetch_result($res,0,descricao);
}else{
	$descricao_produto = "$produto";
}
$aux_data_inicial = substr($data_inicial,8,2) . "/" . substr($data_inicial,5,2) . "/" . substr($data_inicial,0,4);
$aux_data_final   = substr($data_final,8,2)   . "/" . substr($data_final,5,2)   . "/" . substr($data_final,0,4);
$title = "RELATÓRIO DE QUEBRA DE PEÇAS";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE><? echo $title; ?></TITLE>
<META NAME="Generator" CONTENT="EditPlus">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">

<style type="text/css">

.titPreto14 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titPreto12 {
	color: #000000;
	/*text-align: left;*/
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titDatas12 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titChamada10{
	background-color: #596D9B;
	color: #ffffff;
	text-align: center;
	font:11px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.conteudo10 {
	color: #000000;
	font:10px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}
.conteudo101{
	color: #000000;
	font:10px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}
.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}

</style>

<script>
function AbreDefeito(peca,data_inicial,data_final,linha,estado,produto,tipo){

	janela = window.open("relatorio_field_call_rate_defeitos_grupo.php?peca=" + peca + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&produto=<? echo $produto ?>&tipo=" + tipo  + "&tipo_os=<?echo $tipo_os . $param_conversor;?>","defeito",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
function AbreSerie(peca,data_inicial,data_final,linha,estado,produto,tipo){

	janela = window.open("relatorio_field_call_rate_serie_grupo.php?peca=" + peca + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&produto=<? echo $produto ?>&tipo="+tipo  + "&tipo_os=<?echo $tipo_os . $param_conversor;?>","serie",'resizable=1,scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
function AbreSerieGrafico(peca,data_inicial,data_final,linha,estado,produto,tipo){
	janela = window.open("relatorio_field_call_rate_nserie_grafico.php?peca=" + peca + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&produto=<? echo $produto ?>"  + "&tipo_os=<?echo $tipo_os . $param_conversor;?>","grafico",'resizable=1,scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
function AbreSemPeca(produto,data_inicial,data_final,linha,estado,defeito_constatado,solucao,tipo,com_sem){

	janela = window.open("relatorio_field_call_rate_sem_peca_grupo.php?data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&defeito_constatado=" + defeito_constatado + "&solucao=" + solucao + "&produto=<? echo $produto ?>&tipo="+ tipo  + "&tipo_os=<? echo $tipo_os; ?>&com_sem="+com_sem <?php echo '+ "' . $param_conversor . '"' ?>,"peca",'resizable=1,scrollbars=yes,width=750,height=450,top=315,left=20');
	janela.focus();
}

</script>
</HEAD>

<BODY>
<?



echo "<table align='center'><tr><td align='center'>";
include 'relatorio_field_call_rate_pecas_grafico_grupo.php';
echo "</td></tr></table>";

flush();
?>
<TABLE WIDTH = '600' align = 'center'>
	<TR>
		<TD class='titPreto14' align = 'center'><B><? echo $title; ?></B></TD>
	</TR>
	<TR>
		<TD class='titDatas12'><? echo $aux_data_inicial." até ".$aux_data_final ?></TD>
	</TR>
	<TR>
		<TD class='titPreto14'>&nbsp;</TD>
	</TR>
	<TR>
		<TD HEIGHT='25' class='titPreto12' align='center'>PRODUTO: <b><? echo $descricao_produto; ?></b></TD>
	</TR>
</TABLE>

<BR>

<?
	/*
	if($tipo!="produto"){
		$sql_array_produto = "SELECT ARRAY_TO_STRING(ARRAY(SELECT tbl_produto.produto FROM tbl_produto JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha WHERE tbl_linha.fabrica = $login_fabrica AND tbl_produto.referencia_fabrica = $descricao_produto'), ',') AS produto;";
		$res = pg_query ($con,$sql);
		$produto = pg_result($res_com,0,produto);
	}
	*/ 
	
/*$sql="SELECT produto
		INTO TEMP produto_$login_fabrica
		FROM tbl_produto
		JOIN tbl_linha USING(linha)
		WHERE fabrica=$login_fabrica";
$sql .=($tipo=="produto") ?" AND tbl_produto.produto = $produto": " AND tbl_produto.referencia_fabrica ='$produto' ";

$sql .= " ; 
		CREATE INDEX produto_produto_$login_fabrica on produto_$login_fabrica(produto);

		SELECT os,produto,defeito_constatado, solucao_os, fabrica
		INTO TEMP os_$login_fabrica
		FROM tbl_os
		WHERE fabrica=$login_fabrica
		AND produto in (SELECT produto from produto_$login_fabrica where 1=1)
		AND tbl_os.excluida is not true
		AND $cond_5 
		;
*/
	if($tipo=="produto"){
		$cond_6 = " os_$login_fabrica.produto = $produto ";
		$join_6 = " ";
		$cond_produto = " 1 = 1 ";
	}else{
		$cond_6 = " tbl_produto.referencia_fabrica = '$produto' ";
		$join_6 = " JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto ";
		$join_6 .= " JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica ";

		$cond_produto  = ($tipo=="produto") ?"  tbl_produto.produto = $produto ": "  tbl_produto.referencia_fabrica ='$produto' ";
	}


	if ($tipo=='grupo') {
		
				$sql = "SELECT 
						tbl_produto.referencia , tbl_produto.produto
					FROM  tbl_produto 
					JOIN  tbl_familia using(familia)
					WHERE tbl_familia.fabrica = $login_fabrica
					AND   referencia_fabrica  = '$produto'
					AND   tbl_produto.ativo   IS TRUE";
				$xres = pg_query($con,$sql);

				if(pg_num_rows($xres)>0){
					for($y=0;pg_num_rows($xres)>$y;$y++){
						$produto2[] = pg_result($xres,$y,produto);
					}
					$produto_nome = $produto;
					$produto = implode(',',$produto2);
				}
	}


	$sql = "
		SELECT	
			tbl_os.os,
			tbl_os.produto,
			tbl_os.defeito_constatado, 
			tbl_os.solucao_os, 
			tbl_os.fabrica
		INTO TEMP os_$login_fabrica
		FROM tbl_os JOIN tbl_os_extra USING(OS)  
			JOIN tbl_extrato USING(extrato)
		WHERE tbl_os.fabrica=$login_fabrica 
			AND produto in ($produto)
			AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
			AND tbl_os.excluida is not true
			AND tbl_extrato.liberado is not null
			AND tbl_extrato.total > 0
			AND $cond_5
			$cond_matriz_filial
			$cond_conversor
		;
		CREATE INDEX os_os_x$login_fabrica ON os_$login_fabrica(os);
		CREATE INDEX os_os_y$login_produto ON os_$login_fabrica(produto);
		CREATE INDEX os_os_z$login_def_constatado ON os_$login_fabrica(defeito_constatado);
		CREATE INDEX os_os_w$login_solucao_os ON os_$login_fabrica(solucao_os);

		SELECT DISTINCT os 
			INTO TEMP tmp_com_peca_$login_fabrica
		FROM os_$login_fabrica JOIN tbl_os_produto USING(os)
			JOIN tbl_os_item USING(os_produto)
			JOIN tbl_servico_realizado USING(servico_realizado) 
		WHERE tbl_servico_realizado.troca_de_peca is true 
			AND os_$login_fabrica.fabrica = $login_fabrica 
			AND tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado ;

		SELECT DISTINCT os 
		INTO TEMP tmp_sem_peca_$login_fabrica
		FROM os_$login_fabrica 
			LEFT JOIN tbl_os_produto USING(os)
			LEFT JOIN tbl_os_item USING(os_produto)
			LEFT JOIN tbl_servico_realizado USING(servico_realizado) 
		WHERE (tbl_servico_realizado.troca_de_peca is false 
			AND os_$login_fabrica.fabrica = $login_fabrica 
			AND tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado) OR tbl_os_item.os_item is null ;

		DELETE FROM tmp_sem_peca_$login_fabrica WHERE os IN (select os from tmp_com_peca_$login_fabrica);";

flush();
$res = pg_query ($con,$sql);

$sql_com = "SELECT COUNT(*) AS qtde_com FROM tmp_com_peca_$login_fabrica" ;
$res_com =  pg_query($con,$sql_com);

$qtde_com = pg_result($res_com,0,qtde_com);

$sql_sem = "SELECT COUNT(*) AS qtde_sem FROM tmp_sem_peca_$login_fabrica" ;
$res_sem =  pg_query($con,$sql_sem);

$qtde_sem = pg_result($res_sem,0,qtde_sem);

$total = $qtde_com + $qtde_sem;

$porc_com = ($qtde_com/$total) * 100;
$porc_com = round($porc_com,0);
$porc_sem = 100 - $porc_com;

?>

<TABLE WIDTH='250' align='center'>
	<TR>
		<TD HEIGHT='25' class='titPreto12' align='right'>OS sem peças :</TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $qtde_sem; ?></b></TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $porc_sem; ?> %</b></TD>
	</TR>
	<TR>
		<TD HEIGHT='25' class='titPreto12' align='right'>OS com peças :</TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $qtde_com; ?></b></TD>
        		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $porc_com; ?> %</b></TD>
	</TR>
	<TR>
		<TD HEIGHT='25' class='titPreto12' align='right'>Total :</TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $total; ?></b></TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b>100 %</b></TD>
	</TR>

</TABLE>

<br>

<?
include "relatorio_field_call_rate_def_constatado_grafico.php";
echo "<BR><BR>";

flush();
		
	/*	$zzsql = "SELECT 	os_$login_fabrica.os , 
							tbl_defeito_constatado.defeito_constatado as defeito_constatado,
							tbl_defeito_constatado.descricao as defeito_constatado_descricao,
							tbl_solucao.solucao ,
							tbl_solucao.descricao as solucao_descricao,
							CASE WHEN(
								SELECT tbl_servico_realizado.troca_de_peca
								FROM tbl_os_produto
								JOIN tbl_os_item using(os_produto)
								JOIN tbl_servico_realizado using(servico_realizado)
								WHERE tbl_os_produto.os = os_$login_fabrica.os limit 1 ) IS TRUE
								THEN 'com' ELSE 'sem' END AS com_sem 
					FROM os_$login_fabrica
					JOIN oe_$login_fabrica USING(os)
					JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = os_$login_fabrica.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
					JOIN tbl_solucao            on os_$login_fabrica.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica
					$join_6
					WHERE os_$login_fabrica.fabrica = $login_fabrica
					AND $cond_6
					 ";

		$xsql = "SELECT defeito_constatado             ,  
						defeito_constatado_descricao   , 
						solucao                     ,  
						solucao_descricao              , 
						count(os)    as qtde
				FROM ( 
					$zzsql
				) as fcr 
				WHERE fcr.com_sem='sem'
				GROUP BY  	defeito_constatado ,  
							defeito_constatado_descricao,
							solucao,  
							solucao_descricao 
				order by qtde desc";
		*/
	$xsql = "
		SELECT 
			tbl_defeito_constatado.defeito_constatado	AS defeito_constatado			,
			tbl_defeito_constatado.descricao			AS defeito_constatado_descricao	,
			tbl_solucao.solucao					AS solucao					,
			tbl_solucao.descricao					AS solucao_descricao			,
			count(os)							AS qtde
		FROM os_$login_fabrica 
			JOIN tmp_sem_peca_$login_fabrica USING(os)
			JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = os_$login_fabrica.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
			JOIN tbl_solucao on os_$login_fabrica.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica
		GROUP BY 
			tbl_defeito_constatado.defeito_constatado ,
			tbl_defeito_constatado.descricao,
			tbl_solucao.solucao,
			tbl_solucao.descricao
		ORDER BY qtde DESC;";

	$xres = pg_query($con, $xsql);
	$qtde_por_defeito= pg_num_rows($xres);
	if(pg_num_rows($xres) > 0){
		echo "<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>";
		echo "<TR>";
		echo "<TD colspan='5' class='titChamada10' align = 'center' >OS sem peças</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='titChamada10'>Produto</TD>";
		echo "<TD class='titChamada10'>Defeito Constatado</TD>";
		echo "<TD class='titChamada10'>Solução</TD>";
		echo "<TD class='titChamada10'>%</TD>";
		echo "<TD class='titChamada10'>Quantidade</TD>";
		echo "</TR>";
		for($a=0; $a<pg_num_rows($xres); $a++){
			$defeito_constatado			= pg_fetch_result($xres,$a,defeito_constatado);
			$solucao					= pg_fetch_result($xres,$a,solucao);
			$defeito_constatado_descricao	= pg_fetch_result($xres,$a,defeito_constatado_descricao);
			$solucao_descricao			= pg_fetch_result($xres,$a,solucao_descricao);
			$qtde						= pg_fetch_result($xres,$a,qtde);
			
			$xporcentagem = ($qtde * 100)/$qtde_sem;
		//	$xporcentagem = round($xporcentagem,0);
			if($defeito_constatado_descricao==''){
				$defeito_constatado_descricao="Os sem defeito selecionado";
				$defeito_constatado	="00";
			}
			if($solucao_descricao==''){ $solucao	="00"; }
			if($solucao_descricao==''){
				$solucao_descricao="Os sem solução selecionada";

			}
			$xcor = ($a % 2 == 0) ? "#FEFEFE": '#F9FCFF';
			echo "<TR bgcolor='$xcor'>";
			echo "<TD class='conteudo101'>$descricao_produto</TD>";
 			echo "<TD class='conteudo101'><a href='javascript:AbreSemPeca(\"$produto\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$defeito_constatado\",\"$solucao\",\"$tipo\",\"sem\")'>$defeito_constatado_descricao</A></TD>";
			echo "<TD class='conteudo101'>$solucao_descricao</TD>";
			echo "<TD class='conteudo101' align='center'>". number_format($xporcentagem,2,",",".") ."</TD>";
			echo "<TD class='conteudo101' align='center'>$qtde</TD>";
			echo "</TR>";
		}
		echo "</TABLE><BR><BR>";
	}
//fim takashi	
		include "relatorio_field_call_rate_def_constatado_sem_grafico.php";
echo "<BR><BR>";
/////////////////
flush();

//exit;
/*
$zzsql = "SELECT 	os_$login_fabrica.os , 
							tbl_defeito_constatado.defeito_constatado as defeito_constatado,
							tbl_defeito_constatado.descricao as defeito_constatado_descricao,
							tbl_solucao.solucao ,
							tbl_solucao.descricao as solucao_descricao,
							CASE WHEN(
									SELECT tbl_servico_realizado.troca_de_peca
									FROM tbl_os_produto
									JOIN tbl_os_item using(os_produto)
									JOIN tbl_servico_realizado using(servico_realizado)
									WHERE tbl_os_produto.os = os_$login_fabrica.os limit 1 ) IS TRUE
									THEN 'com' ELSE 'sem' END AS com_sem 
					FROM os_$login_fabrica
					JOIN oe_$login_fabrica ON os_$login_fabrica.os = oe_$login_fabrica.os 
					$join_6
					JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = os_$login_fabrica.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
					JOIN tbl_solucao            on os_$login_fabrica.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica
					WHERE os_$login_fabrica.fabrica = $login_fabrica
					AND  $cond_6 ";
		$xsql = "SELECT defeito_constatado            ,
						defeito_constatado_descricao  ,
						solucao                    ,
						solucao_descricao             ,
						count(os)    as qtde
				FROM ( 
					$zzsql 
				) as fcr 
				WHERE fcr.com_sem='com'
				GROUP BY  	defeito_constatado ,  
							defeito_constatado_descricao,
							solucao,  
							solucao_descricao 
				order by qtde desc";
*/

	$xsql = "
		SELECT 
			tbl_defeito_constatado.defeito_constatado	AS defeito_constatado			,
			tbl_defeito_constatado.descricao			AS defeito_constatado_descricao	,
			tbl_solucao.solucao					AS solucao					,
			tbl_solucao.descricao					AS solucao_descricao			,
			count(os)							AS qtde
		FROM os_$login_fabrica 
			JOIN tmp_com_peca_$login_fabrica USING(os)
			JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = os_$login_fabrica.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
			JOIN tbl_solucao on os_$login_fabrica.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica
		GROUP BY 
			tbl_defeito_constatado.defeito_constatado ,
			tbl_defeito_constatado.descricao,
			tbl_solucao.solucao,
			tbl_solucao.descricao
		ORDER BY qtde DESC;";
	$xres = pg_query($con, $xsql);
	//echo nl2br($xsql);
//	exit;


	$qtde_por_defeito= pg_num_rows($xres);
	if(pg_num_rows($xres) > 0){
		echo "<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>";
		echo "<TR>";
		echo "<TD colspan='5' class='titChamada10' align = 'center' >OS com peças</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='titChamada10'>Produto</TD>";
		echo "<TD class='titChamada10'>Defeito Constatado</TD>";
		echo "<TD class='titChamada10'>Solução</TD>";
		echo "<TD class='titChamada10'>%</TD>";
		echo "<TD class='titChamada10'>Quantidade</TD>";
		echo "</TR>";
		for($a=0; $a<pg_num_rows($xres); $a++){
			$defeito_constatado			= pg_fetch_result($xres,$a,defeito_constatado);
			$solucao					= pg_fetch_result($xres,$a,solucao);
			$defeito_constatado_descricao	= pg_fetch_result($xres,$a,defeito_constatado_descricao);
			$solucao_descricao			= pg_fetch_result($xres,$a,solucao_descricao);
			$qtde						= pg_fetch_result($xres,$a,qtde);
			$xxporcentagem				= ($qtde * 100)/$qtde_com;

			if($defeito_constatado_descricao==''){
				$defeito_constatado_descricao="Os sem defeito selecionado";
				$defeito_constatado	="00";
			}
			if($solucao_descricao==''){ $solucao	="00"; }
			if($solucao_descricao==''){
				$solucao_descricao="Os sem solução selecionada";

			}
			$xcor = ($a % 2 == 0) ? "#FEFEFE": '#F9FCFF';
			echo "<TR bgcolor='$xcor'>";
			echo "<TD class='conteudo101'>$descricao_produto</TD>";
 			echo "<TD class='conteudo101'><a href='javascript:AbreSemPeca(\"$produto\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$defeito_constatado\",\"$solucao\",\"$tipo\",\"com\")'>$defeito_constatado_descricao</A></TD>";
			echo "<TD class='conteudo101'>$solucao_descricao</TD>";
			echo "<TD class='conteudo101' align='center'>". number_format($xxporcentagem,2,",",".") ."</TD>";
			echo "<TD class='conteudo101' align='center'>$qtde</TD>";
			echo "</TR>";
		}
		echo "</TABLE><BR><BR>";
	}
		include "relatorio_field_call_rate_def_constatado_com_grafico.php";
echo "<BR><BR>";





flush();
if(pg_num_rows($yres) > 0){
?>
	<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>
	<TR>
		<TD class='titChamada10'>REFERÊNCIA</TD>
		<TD class='titChamada10'>PEÇA</TD>
		<TD class='titChamada10'>OCORRÊNCIAS</TD>
		<TD class='titChamada10'>%</TD>
		<TD class='titChamada10'># Série</TD>
	</TR>
<?

	for ($x = 0; $x < pg_num_rows($yres); $x++) {
		$total_ocorrencia = $total_ocorrencia + pg_fetch_result($yres,$x,ocorrencia);
	}
	
	for($i=0; $i<pg_num_rows($yres); $i++){
		$peca       = pg_fetch_result($yres,$i,peca);
		$referencia = pg_fetch_result($yres,$i,referencia);
		$descricao  = pg_fetch_result($yres,$i,descricao);
		$ocorrencia = pg_fetch_result($yres,$i,ocorrencia);

		if ($total_ocorrencia > 0) {
			$porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
		}
	
		$cor = '2';
		if ($i % 2 == 0) $cor = '1';

		echo "<TR class='bgTRConteudo$cor'>";

		echo "	<TD class='conteudo10' align='center'><a href='javascript:AbreDefeito(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"\",\"$tipo\")'>$referencia</a></TD>";
		echo "	<TD class='conteudo10' align='left'>$descricao</TD>";
		echo "	<TD class='conteudo10' align='center'>$ocorrencia</TD>";
		echo "	<TD class='conteudo10' align='center'>". number_format($porcentagem,2,",",".") ."%</TD>";
		echo "	<TD class='conteudo10' align='center'><a href='javascript:AbreSerie(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"\",\"$tipo\")'>#série</a></TD>";
// <a href='javascript:AbreSerieGrafico(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$produto\")'>#série</a> 
		echo "</TR>";
	}
		echo "<TR >";
		echo "	<TD class='titChamada10' align='center' colspan='2'><B>TOTAL</b></TD>";
		echo "	<TD class='titChamada10' align='center'>$total_ocorrencia</TD>";
		echo "	<TD class='titChamada10' align='center'>&nbsp</TD>";
		echo "	<TD class='titChamada10' align='center'>&nbsp</TD>";
		echo "</TR>";
echo "</table>";

}

?>

</TABLE>

</BODY>
</HTML>
<? include "monitora_rodape.php";?>
