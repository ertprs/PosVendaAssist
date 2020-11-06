<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="gerencia";


$data_inicial       = trim($_GET['data_inicial']);
$data_final         = trim($_GET['data_final']);
$produto            = trim($_GET['produto']);
$linha              = trim($_GET['linha']);
$estado             = trim($_GET['estado']);
$pais               = trim($_GET['pais']);
$familia            = trim($_GET['familia']);
$posto              = trim($_GET['posto']);
$consumidor_revenda = trim($_GET['consumidor_revenda']);
$tipo_pesquisa      = trim($_GET['tipo_pesquisa']);
$defeito_constatado = trim($_GET['defeito_constatado']);
$origem             = trim($_GET['origem']);


$gera_automatico = trim($_GET["gera_automatico"]);


if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

if (strlen($data_inicial)==0){
	include "gera_relatorio_pararelo_include.php";
}


if (strlen($data_inicial)>0 AND strlen($data_final)>0) {
	$btn_acao = "1";
}

if ($login_fabrica <> 20) $pais = 'BR';

// Alterado por Paulo - chamado : 3195
//ALTERADO POR IGOR
if(strlen($produto) > 0) {
	if($login_fabrica == 20 and $pais != 'BR'){
		$sql = "SELECT tbl_produto.referencia, tbl_produto_idioma.descricao FROM tbl_produto LEFT JOIN tbl_produto_idioma on tbl_produto.produto = tbl_produto_idioma.produto and tbl_produto_idioma.idioma = 'ES' WHERE tbl_produto.produto = $produto";
	}else{
		$sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
	}
	$res = pg_exec($con,$sql);
	$descricao_produto  = pg_result($res,0,descricao);
	$referencia_produto = pg_result($res,0,referencia);
}
$aux_data_inicial = substr($data_inicial,8,2) . "/" . substr($data_inicial,5,2) . "/" . substr($data_inicial,0,4);
$aux_data_final   = substr($data_final,8,2)   . "/" . substr($data_final,5,2)   . "/" . substr($data_final,0,4);

if(strlen($defeito_constatado)>0) { 
	$sql="SELECT descricao as descricao_defeito_constatado from tbl_defeito_constatado WHERE defeito_constatado=$defeito_constatado";
	$res = pg_exec($con,$sql);
	$descricao_defeito_constatado  = pg_result($res,0,descricao_defeito_constatado );

}
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
function AbreDefeito(peca,data_inicial,data_final,linha,estado,pais,posto,produto,tipo_pesquisa,defeito_constatado,origem){
	janela = window.open("relatorio_field_call_rate_defeitos.php?peca=" + peca + "&data_inicial=" + data_inicial + "&consumidor_revenda=<? echo $consumidor_revenda?>&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado  + "&pais=" + pais + "&posto=" + posto+ "&tipo_pesquisa=" + tipo_pesquisa + "&defeito_constatado=" + defeito_constatado + "&origem=" + origem+"&produto=<? echo $produto ?>","peca",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
function AbreSerie(peca,data_inicial,data_final,linha,estado,pais,posto,produto,tipo_pesquisa,defeito_constatado,origem){
	janela = window.open("relatorio_field_call_rate_serie.php?peca=" + peca + "&data_inicial=" + data_inicial + "&consumidor_revenda=<? echo $consumidor_revenda?>&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&pais=" + pais +"&posto=" + posto+ "&tipo_pesquisa=" + tipo_pesquisa + "&defeito_constatado=" + defeito_constatado + "&origem=" + origem +"&produto=<? echo $produto ?>","peca",'resizable=1,scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
function AbreSemPeca(produto,data_inicial,data_final,linha,estado,pais,posto,defeito_constatado,origem,solucao,tipo_pesquisa){
	janela = window.open("relatorio_field_call_rate_sem_peca.php?data_inicial=" + data_inicial + "&consumidor_revenda=<? echo $consumidor_revenda?>&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&pais=" + pais +"&posto=" + posto+ "&defeito_constatado=" + defeito_constatado + "&origem=" + origem + "&solucao=" + solucao + "&tipo_pesquisa=" + tipo_pesquisa + "&produto=<? echo $produto ?>","peca",'resizable=1,scrollbars=yes,width=750,height=450,top=315,left=20');
	janela.focus();
}
function Comparativo(produto,data_inicial,data_final,linha,estado,pais,posto,defeito_constatado,origem,solucao,tipo_pesquisa){
	janela = window.open("relatorio_field_call_rate_pecas_comparativo.php?data_inicial=" + data_inicial + "&consumidor_revenda=<? echo $consumidor_revenda?>&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&pais=" + pais +"&posto=" + posto+ "&defeito_constatado=" + defeito_constatado + "&origem=" + origem + "&solucao=" + solucao + "&tipo_pesquisa=" + tipo_pesquisa + "&produto=<? echo $produto ?>","peca",'resizable=1,scrollbars=yes,width=750,height=450,top=315,left=20');
	janela.focus();
}
</script>
</HEAD>

<BODY>
<?
$cond_1 = "1=1";
$cond_2 = "1=1";
$cond_3 = "1=1";
$cond_4 = "1=1";
$cond_5 = "1=1";
$cond_6 = "1=1";
$cond_7 = "1=1";
$cond_8 = "1=1";
$cond_9 = "1=1";
$cond_origem = "1=1";
	
#if (strlen ($familia) > 0) $cond_1 = " tbl_produto.familia = $familia ";
if (strlen ($estado) > 0)             $cond_2 = " tbl_posto.estado    = '$estado' ";
if (strlen ($posto)  > 0)             $cond_3 = " tbl_posto.posto     = $posto ";
if($login_fabrica==14) {
	if (strlen ($linha)  > 0)             $cond_4 = " tbl_produto.familia   = $linha 	";
} else {
	if (strlen ($linha)  > 0)             $cond_4 = " tbl_produto.linha   = $linha ";
}
if (strlen ($consumidor_revenda) > 0) $cond_5 = " tbl_os.consumidor_revenda = '$consumidor_revenda' ";
if (strlen ($pais)   > 0)             $cond_6 = " tbl_posto.pais     = '$pais' ";
if(strlen($produto)  >0)              $cond_7=" tbl_os.produto = $produto";
if(strlen($defeito_constatado)>0)     $cond_8=" tbl_os.defeito_constatado = $defeito_constatado";
if(strlen($origem)>0)     $cond_origem=" tbl_produto.origem= '$origem'";
if($login_fabrica== 14) {
	if(strlen($familia) > 0) { 
		$cond_9=" tbl_produto.familia=$familia";
	}
}
echo "<table><tr><td align='center'>";
//include 'relatorio_field_call_rate_pecas_grafico.php';
echo "</td></tr></table>";
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
<?if(strlen($produto) > 0) {?>
		<TD HEIGHT='25' class='titPreto12' align='center'>PRODUTO: <b><? echo $referencia_produto ." - ". $descricao_produto; ?></b></TD>
<? } elseif(strlen($defeito_constatado)>0) { ?>
		<TD HEIGHT='25' class='titPreto12' align='center'>DEFEITO CONSTATADO: <b><? echo $descricao_defeito_constatado; ?></b></TD>
<? } ?>

	</TR>
</TABLE>

<BR>

<?
flush();


if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}

if (strlen($msg_erro)>0){
	echo "<p>".$msg_erro."</p>";
}

if (strlen($btn_acao)>0 AND strlen($msg_erro)==0){

	echo "<p>Relatório gerado em ".date("d/m/Y")." às ".date("H:i")."</p><br>";

	if($login_fabrica == 20 and $pais != 'BR'){
		$tipo_data = " tbl_extrato.data_geracao ";
	}else{
		if($login_fabrica == 20) $tipo_data = " tbl_extrato_extra.exportado ";
		else                     $tipo_data = " tbl_extrato.data_geracao ";
	}
	if ($login_fabrica == 14) $sql_14 = "AND   tbl_extrato.liberado IS NOT NULL ";

	$sql = "
		SELECT tbl_os_extra.os,(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
		INTO  TEMP temp_fcr_pecas2_osex_$login_admin
		FROM  tbl_os_extra
		JOIN tbl_extrato USING (extrato)
		JOIN tbl_extrato_extra USING (extrato)
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND $tipo_data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
		$sql_14;

		CREATE INDEX temp_fcr_pecas2_osex_os_$login_admin ON temp_fcr_pecas2_osex_$login_admin(os);

		SELECT tbl_os.os 
		INTO TEMP temp_fcr_pecas2_os_$login_admin
		FROM tbl_os 
		JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
		JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
		JOIN temp_fcr_pecas2_osex_$login_admin ON temp_fcr_pecas2_osex_$login_admin.os = tbl_os.os  AND (temp_fcr_pecas2_osex_$login_admin.status NOT IN (13,15) OR							temp_fcr_pecas2_osex_$login_admin.status IS NULL)
		WHERE 1=1
		AND tbl_os.excluida IS NOT TRUE
		AND   $cond_7
		AND   $cond_1
		AND   $cond_2
		AND   $cond_3
		AND   $cond_4
		AND   $cond_5
		AND   $cond_8
		AND   $cond_9
	";
	if  ($login_fabrica == 20 and $pais != 'BR') $sql .= "AND   $cond_6 ";

	$sql .=";
		 CREATE INDEX temp_fcr_pecas2_os_os_$login_admin ON temp_fcr_pecas2_os_$login_admin(os);

			SELECT tbl_os_extra.os , 
				CASE WHEN (
					SELECT tbl_os_item.os_item 
					FROM tbl_os_item 
					JOIN tbl_os_produto USING (os_produto) 
					WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1
				) IS NULL THEN 'SEM'
				ELSE 'COM' END AS com_sem
			INTO TEMP temp_fcr_pecas2_comsem_$login_admin
			FROM tbl_os_extra
			JOIN temp_fcr_pecas2_os_$login_admin oss            ON oss.os = tbl_os_extra.os
			JOIN tbl_extrato USING (extrato)
			JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato;

		CREATE INDEX temp_fcr_pecas2_comsem_OS_$login_admin ON temp_fcr_pecas2_comsem_$login_admin(os);

		SELECT COUNT(*) AS qtde , fcr.com_sem
		FROM temp_fcr_pecas2_comsem_$login_admin fcr
		GROUP BY fcr.com_sem  ";

	/*
	if($login_fabrica == 24){
		$sql= " SELECT COUNT(*) AS qtde , fcr.com_sem
				FROM (
					SELECT tbl_os.os , 
						CASE WHEN (
							SELECT tbl_os_item.os_item 
							FROM tbl_os_item 
							JOIN tbl_os_produto USING (os_produto) 
							WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1
						) IS NULL THEN 'SEM'
						ELSE 'COM' END AS com_sem
						FROM tbl_os_extra
						JOIN (
							SELECT tbl_os.os 
							FROM tbl_os
							JOIN tbl_os_extra USING (os)
							JOIN tbl_extrato USING (extrato)
							JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
							WHERE tbl_os.produto = $produto 
							AND $tipo_data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
						) oss            ON oss.os = tbl_os_extra.os
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
						JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						JOIN tbl_posto   ON tbl_os.posto    = tbl_posto.posto 
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND   $tipo_data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' AND   tbl_os.excluida IS NOT TRUE
						AND   tbl_os.produto = $produto
						AND   $cond_1
						AND   $cond_2
						AND   $cond_3
						AND   $cond_4
						AND   $cond_5
				) fcr
				GROUP BY fcr.com_sem  ";
		}
	*/
	if($login_fabrica == 6){
		/*$sql= "
				SELECT COUNT(*) AS qtde , fcr.com_sem
				FROM (
					SELECT tbl_os.os ,
							CASE WHEN (
							SELECT tbl_os_item.os_item 
							FROM tbl_os_item 
							JOIN tbl_os_produto USING (os_produto) 
							WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1
							) IS NULL THEN 'SEM'
							ELSE 'COM' END AS com_sem
					FROM tbl_os_extra
					JOIN (
						SELECT tbl_os.os 
						FROM tbl_os
						JOIN tbl_os_extra USING (os)
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
						WHERE tbl_os.produto = $produto  
						AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
					) oss ON oss.os = tbl_os_extra.os
					JOIN tbl_extrato USING (extrato)
					JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
					JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto 
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' AND tbl_os.excluida IS NOT TRUE
					AND tbl_os.produto = $produto
					AND   $cond_1
					AND   $cond_2
					AND   $cond_3
					AND   $cond_4
					AND   $cond_5 
				) fcr
				GROUP BY fcr.com_sem ";
		*/
		if($tipo_pesquisa=="data_abertura"){
			$sql= "
					SELECT COUNT(*) AS qtde , fcr.com_sem
					FROM (
						SELECT tbl_os.os , 
								CASE WHEN (
								SELECT tbl_os_item.os_item 
								FROM tbl_os_item 
								JOIN tbl_os_produto USING (os_produto) 
								WHERE tbl_os_produto.os = tbl_os.os LIMIT 1
								) IS NULL THEN 'SEM'
								ELSE 'COM' END AS com_sem
						FROM tbl_os
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto 
						WHERE tbl_os.fabrica = $login_fabrica
						AND tbl_os.data_abertura BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
						AND tbl_os.excluida IS NOT TRUE
						AND   $cond_7
						AND   $cond_1
						AND   $cond_2
						AND   $cond_3
						AND   $cond_4
						AND   $cond_5 
					) fcr
					GROUP BY fcr.com_sem ";
		}
	}
	#echo nl2br($sql);
	#exit;
	$res = pg_exec ($con,$sql);

	$qtde_com = 0 ;
	$qtde_sem = 0 ;

	for($i = 0 ; $i < pg_numrows($res) ; $i++){
		if (pg_result ($res,$i,com_sem) == "COM") $qtde_com = pg_result ($res,$i,qtde);
		if (pg_result ($res,$i,com_sem) == "SEM") $qtde_sem = pg_result ($res,$i,qtde);
	}

	$total = $qtde_com + $qtde_sem;

	if ($qtde_com > 0){
		$porc_com = ($qtde_com/$total) * 100;
	}
	else
		$porc_com = 0;
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
	flush();


	if($login_fabrica<>14){

		$sql = "SELECT tbl_os.os , tbl_os.sua_os,
			CASE WHEN (SELECT tbl_os_item.os_item FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1) IS NULL THEN 'SEM' ELSE 'COM' END AS com_sem
			FROM tbl_os_extra 
			JOIN tbl_extrato USING (extrato)
			JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
			JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
			JOIN tbl_produto ON tbl_produto.produto= tbl_os.produto 
			JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			JOIN temp_fcr_pecas2_osex_$login_admin ON temp_fcr_pecas2_osex_$login_admin.os = tbl_os.os
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   $tipo_data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
			AND   tbl_os.excluida IS NOT TRUE
			AND   tbl_os.produto = $produto
			AND   $cond_1
			AND   $cond_2
			AND   $cond_3
			AND   $cond_4
			" ;
		if  ($login_fabrica == 20 and $pais != 'BR') $sql .= "AND   $cond_6 ";

	/*	if($login_fabrica==24){
			$sql = "SELECT tbl_os.os , tbl_os.sua_os,
							CASE WHEN (SELECT tbl_os_item.os_item FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1) IS NULL THEN 'SEM' ELSE 'COM' END AS com_sem
					FROM tbl_os_extra 
					JOIN (SELECT tbl_os.os FROM tbl_os JOIN tbl_os_extra USING (os) JOIN tbl_extrato USING (extrato) WHERE tbl_os.produto = $produto AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59') oss ON oss.os = tbl_os_extra.os
					JOIN tbl_extrato USING (extrato)
					JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
					JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
					JOIN tbl_produto ON tbl_produto.produto= tbl_os.produto 
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND   $tipo_data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
					AND   tbl_os.excluida IS NOT TRUE
					AND   tbl_os.produto = $produto
					AND   $cond_1
					AND   $cond_2
					AND   $cond_3
					AND   $cond_4
					" ;
		}
	*/
		$res = @pg_exec($con, $sql);
		
		if(@pg_numrows($res) > 0){
		
			$imprime		= null;
			for($i=0; $i<pg_numrows($res); $i++){
				$os				= pg_result($res,$i,os);
				$sua_os			= pg_result($res,$i,sua_os);
				$com_sem		= pg_result($res,$i,com_sem);
			
				if($login_fabrica<>6 and 1==2){
					if(($com_sem == 'SEM') and ($imprime == null)) {
						$imprime = 1;
						echo "<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>";
						echo "<TR>";
						echo "<TD class='titChamada10' align = 'center' >OS sem peças</TD>";
						echo "</TR>";
					}
					if ($com_sem == 'SEM') {
						$cor = '2';
						if ($i % 2 == 0) $cor = '1';
						echo "<TR class='bgTRConteudo$cor'>";
						echo "<TD class='conteudo10' align='center'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></TD>";
						echo "</TR>";
					}
				}
			}	
		
		}

	}

	flush();

	if($login_fabrica == 20 and $pais !='BR'){
		$sql_peca =" tbl_peca_idioma.descricao AS descricao_espanhol, ";
		$join_pc_idioma="LEFT JOIN tbl_peca_idioma on tbl_peca_idioma.peca = tbl_peca.peca AND tbl_peca_idioma.idioma = 'ES'";
	}else{
		$sql_peca=" tbl_peca.descricao, ";
		$join_pc_idioma="";
	}


	/*takashi 3977 linha 748   , 
	(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status*/
	$sql =" 
		SELECT tbl_os_item.peca, COUNT(*) AS qtde
		INTO TEMP temp_fcr_pecas2_peca_$login_admin
		FROM tbl_os_item
		JOIN tbl_os_produto USING (os_produto)
		JOIN temp_fcr_pecas2_os_$login_admin fcr ON tbl_os_produto.os = fcr.os
		GROUP BY tbl_os_item.peca;

		CREATE INDEX temp_fcr_pecas2_peca_PECA_$login_admin ON temp_fcr_pecas2_peca_$login_admin(peca);

		SELECT tbl_peca.referencia,
		$sql_peca
		tbl_peca.peca, pecas.qtde AS ocorrencia
		FROM tbl_peca 
		$join_pc_idioma
		JOIN   temp_fcr_pecas2_peca_$login_admin pecas ON tbl_peca.peca = pecas.peca
		ORDER BY pecas.qtde DESC " ;

	//echo nl2br($sql);
	/*takashi 3977 linha 733 WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)*/
	/*if($login_fabrica==24){
		$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca, pecas.qtde AS ocorrencia
				FROM tbl_peca
				JOIN   (SELECT tbl_os_item.peca, COUNT(*) AS qtde
						FROM tbl_os_item
						JOIN tbl_os_produto USING (os_produto)
						JOIN   (SELECT tbl_os.os 
								FROM tbl_os_extra
								JOIN tbl_extrato USING (extrato)
								JOIN tbl_extrato_extra USING (extrato)
								JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
								JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
								JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
								WHERE tbl_extrato.fabrica = $login_fabrica
								AND    tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
								AND   tbl_os.excluida IS NOT TRUE
								AND   tbl_os.produto = $produto
								AND   $cond_1
								AND   $cond_2
								AND   $cond_3
								AND   $cond_4
								AND   $cond_5 
						) fcr ON tbl_os_produto.os = fcr.os
						GROUP BY tbl_os_item.peca
				) pecas ON tbl_peca.peca = pecas.peca
			ORDER BY pecas.qtde DESC " ;
	}
	*/
	if($login_fabrica==6){
		/*$sql = "SELECT tbl_peca.referencia, 
						tbl_peca.descricao, 
						tbl_peca.peca, 
						pecas.qtde AS ocorrencia
				FROM tbl_peca 
				JOIN (SELECT tbl_os_item.peca, COUNT(*) AS qtde
				FROM tbl_os_item
				JOIN tbl_os_produto USING (os_produto)
				JOIN (
						SELECT tbl_os.os 
						FROM tbl_os_extra
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_extrato_extra USING (extrato)
						JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
						JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os.produto = $produto
						AND   $cond_1
						AND   $cond_2
						AND   $cond_3
						AND   $cond_4
						AND   $cond_5
				) fcr ON tbl_os_produto.os = fcr.os
				GROUP BY tbl_os_item.peca
				) pecas ON tbl_peca.peca = pecas.peca
				ORDER BY pecas.qtde DESC ";
		*/
		if($tipo_pesquisa=="data_abertura"){
			$sql = "SELECT tbl_peca.referencia, 
						tbl_peca.descricao, 
						tbl_peca.peca, 
						pecas.qtde AS ocorrencia
				FROM tbl_peca 
				JOIN (
					SELECT tbl_os_item.peca, COUNT(*) AS qtde
					FROM tbl_os_item
					JOIN tbl_os_produto USING (os_produto)
					JOIN (
							SELECT tbl_os.os 
							FROM tbl_os 
							JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
							JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
							WHERE tbl_os.fabrica = $login_fabrica
							AND tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final' 
							AND tbl_os.excluida IS NOT TRUE
							AND   $cond_7
							AND   $cond_1
							AND   $cond_2
							AND   $cond_3
							AND   $cond_4
							AND   $cond_5
							AND   $cond_8
					) fcr ON tbl_os_produto.os = fcr.os
					GROUP BY tbl_os_item.peca
				) pecas ON tbl_peca.peca = pecas.peca
				ORDER BY pecas.qtde DESC ";
		}	
	}

	$res = pg_exec($con, $sql);

	if(pg_numrows($res) > 0){
		if($login_fabrica==3)echo "<center><a href='javascript:Comparativo(\"$produto\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$pais\",\"$posto\",\"\",\"\",\"\",\"$tipo_pesquisa\")'>Ver o comparativo dos ultimos 3 meses</a>";
		/*IGOR- HD 9436 - ANTES DE ALTERAR, ESTÁ DIFERENTE OS PARAMETROS DA FUNÇÃO COM O LINK*/
		//echo "<center><a href='javascript:Comparativo(\"$produto\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$pais\",\"$posto\",\"\",\"$tipo_pesquisa\")'>Ver o comparativo dos ultimos 3 meses</a>";
		//Comparativo(produto,data_inicial,data_final,linha,estado,pais,posto,defeito_constatado,solucao,tipo_pesquisa){
		echo "<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>";
		echo "<TR>";
		echo "<TD class='titChamada10'>REFERÊNCIA</TD>";
		echo "<TD class='titChamada10'>PEÇA</TD>";
		echo "<TD class='titChamada10'>OCORRÊNCIAS</TD>";
		echo "<TD class='titChamada10'>%</TD>";
		echo "<TD class='titChamada10'># Série</TD>";
		echo "</TR>";


		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
		}
		
		for($i=0; $i<pg_numrows($res); $i++){
			$peca       = pg_result($res,$i,peca);
			$referencia = pg_result($res,$i,referencia);
			if($login_fabrica == 20 and $pais !='BR'){
				$descricao  = pg_result($res,$i,descricao_espanhol);		
				if(strlen($descricao) == 0){
					$descricao = "<font color = 'red'>Tradução não cadastrada</font>";
				}
			}else{
				$descricao  = pg_result($res,$i,descricao);
			}
			$ocorrencia = pg_result($res,$i,ocorrencia);

			if ($total_ocorrencia > 0) {
				$porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
			}
		
			$cor = '2';
			if ($i % 2 == 0) $cor = '1';

			echo "<TR class='bgTRConteudo$cor'>";
			
			echo "<TD class='conteudo10' align='center'><a href='javascript:AbreDefeito(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$pais\",\"$posto\",\"\",\"$tipo_pesquisa\",\"$defeito_constatado\",\"$origem\")'>$referencia</a></TD>";
			echo "<TD class='conteudo10' align='left'>$descricao</TD>";
			echo "<TD class='conteudo10' align='center'>$ocorrencia</TD>";
			echo "<TD class='conteudo10' align='center'>". number_format($porcentagem,2,",",".") ."%</TD>";

			echo "<TD class='conteudo10' align='center'><a href='javascript:AbreSerie(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$pais\",\"$posto\",\"\",\"$tipo_pesquisa\",\"$defeito_constatado\",\"$origem\")'>#série</a></TD>";

			echo "</TR>";
		}
		echo "<TR >";
		echo "<TD class='titChamada10' align='center' colspan='2'><B>TOTAL</b></TD>";
		echo "<TD class='titChamada10' align='center'>$total_ocorrencia</TD>";
		echo "<TD class='titChamada10' align='center'>&nbsp</TD>";
		echo "<TD class='titChamada10' align='center'>&nbsp</TD>";
		echo "</TR>";
		echo "</table>";

	}

	if($login_fabrica == 11){
		$sql =" 
			SELECT    solucao_os, COUNT(*) AS qtde
			INTO TEMP temp_fcr_pecas2_solucao_$login_admin
			FROM      tbl_os
			JOIN      temp_fcr_pecas2_comsem_$login_admin fcr ON tbl_os.os = fcr.os AND fcr.com_sem = 'SEM'
			GROUP BY  solucao_os;
		
			CREATE INDEX temp_fcr_pecas2_solucao_SOLUCAO_$login_admin ON temp_fcr_pecas2_solucao_$login_admin(solucao_os);
		
			SELECT  tbl_solucao.solucao,
				tbl_solucao.descricao,
				solucao.qtde AS ocorrencia
			FROM tbl_solucao
			JOIN   temp_fcr_pecas2_solucao_$login_admin solucao ON tbl_solucao.solucao = solucao.solucao_os
			ORDER BY solucao.qtde DESC " ;
		
		$res = pg_exec($con, $sql);
		$total_ocorrencia = 0;
		if(pg_numrows($res) > 0){
			echo "<BR><TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>";
			echo "<TR>";
			echo "<TD class='titChamada10'>SOLUÇÃO</TD>";
			echo "<TD class='titChamada10'>OCORRÊNCIAS</TD>";
			echo "<TD class='titChamada10'>%</TD>";
			echo "<TD class='titChamada10'># Série</TD>";
			echo "</TR>";

			for ($x = 0; $x < pg_numrows($res); $x++) $total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
			
			for($i=0; $i<pg_numrows($res); $i++){
				$solucao    = pg_result($res,$i,solucao);
				$referencia = pg_result($res,$i,descricao);
				if($login_fabrica == 20 and $pais !='BR'){
					$descricao  = pg_result($res,$i,descricao_espanhol);		
					if(strlen($descricao) == 0){
						$descricao = "<font color = 'red'>Tradução não cadastrada</font>";
					}
				}else{
					$descricao  = pg_result($res,$i,descricao);
				}
				$ocorrencia = pg_result($res,$i,ocorrencia);
		
				if ($total_ocorrencia > 0) {
					$porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
				}
			
				$cor = '2';
				if ($i % 2 == 0) $cor = '1';
		
				echo "<TR class='bgTRConteudo$cor'>";
				echo "<TD class='conteudo10' align='left'>$descricao</TD>";
				echo "<TD class='conteudo10' align='center'>$ocorrencia</TD>";
				echo "<TD class='conteudo10' align='center'>". number_format($porcentagem,2,",",".") ."%</TD>";
				echo "<TD class='conteudo10' align='center'><a href='javascript:AbreSerie(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$pais\",\"$posto\",\"\",\"$tipo_pesquisa\",\"$solucao\",\"$origem\")'>#série</a></TD>";
				echo "</TR>";
			}
			echo "<TR >";
			echo "<TD class='titChamada10' align='center' colspan='2'><B>TOTAL</b></TD>";
			echo "<TD class='titChamada10' align='center'>$total_ocorrencia</TD>";
			echo "<TD class='titChamada10' align='center'>&nbsp</TD>";
			echo "<TD class='titChamada10' align='center'>&nbsp</TD>";
			echo "</TR>";
			echo "</table>";
		
		}
	}














	if ($login_fabrica == 20 and $pais !='BR'){
		$xsql_defeito    = " tbl_defeito_constatado_idioma.descricao AS defeito_constatado_descricao_espanhol,";
		$xsql_solucao    = " tbl_servico_realizado_idioma.descricao  AS solucao_descricao_espanhol, ";
		$joinA           = " LEFT JOIN tbl_servico_realizado_idioma  ON tbl_os.solucao_os         = tbl_servico_realizado_idioma.servico_realizado";
		$joinB           = " LEFT JOIN tbl_defeito_constatado_idioma ON tbl_os.defeito_constatado = tbl_defeito_constatado_idioma.defeito_constatado ";
		$group_defeito   = " tbl_defeito_constatado_idioma.descricao, tbl_os.solucao_os,";
		$group_solucao   = " tbl_servico_realizado_idioma.descricao ";

	}else{
		$xsql_defeito    = " tbl_defeito_constatado.descricao AS defeito_constatado_descricao,";
		$xsql_solucao    = " tbl_solucao.descricao            AS solucao_descricao, ";
		$joinA           = " LEFT JOIN tbl_solucao            ON tbl_os.solucao_os         = tbl_solucao.solucao";
		$joinB           = " LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado ";
		$group_defeito   = " tbl_defeito_constatado.descricao, tbl_os.solucao_os,";
		$group_solucao   = " tbl_solucao.descricao ";
	}

		$xsql="
			SELECT DISTINCT(tbl_os.os) 
			INTO TEMP temp_fcr_notin_$login_admin
			FROM tbl_os 
			JOIN tbl_os_extra using(os) 
			join tbl_extrato using (extrato)
			JOIN tbl_produto ON tbl_produto.produto= tbl_os.produto 
			JOIN tbl_os_produto ON tbl_os.os= tbl_os_produto.os 
			WHERE tbl_os.fabrica = $login_fabrica 
			AND tbl_os.produto=$produto
			AND tbl_os.excluida IS NOT TRUE 
			AND (tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ) ;
			
			SELECT   tbl_os.defeito_constatado,
				$xsql_defeito 
				tbl_os.solucao_os , 
				$xsql_solucao
				count(tbl_os.os) AS qtde 
			FROM tbl_os 
			JOIN tbl_posto         ON    tbl_os.posto = tbl_posto.posto 
			JOIN tbl_os_extra using(os) 
			JOIN tbl_extrato using (extrato) 
			$joinB
			$joinA
			WHERE tbl_os.produto = $produto 
			AND   tbl_os.fabrica = $login_fabrica 
			AND  tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";
			if(strlen($estado)>0)     $xsql.= " and  tbl_posto.estado = '$estado' ";
			if($login_fabrica == 20 ) $xsql.= " and  tbl_posto.pais = '$pais' "; 
			$xsql.=" AND $cond_3
				 AND tbl_os.os NOT IN (select os from temp_fcr_notin_$login_admin
					
				)
			GROUP BY tbl_os.defeito_constatado, 
			$group_defeito 
			$group_solucao
			ORDER BY qtde desc";
	//EXCEPT
	/*takashi hd3977 linha 439	AND (tbl_os_extra.status_os NOT IN (13,15) OR tbl_os_extra.status_os IS NULL) */
	//if($ip=="201.92.126.18")echo $xsql;
	/*######################## takashi 05-12-06
	Liberar apenas qdo todas as OS estiverem 
	com o relacionamento de integridade
	Lembrando que antigamente a solucao_os era na tbl_servico_realizado
	agora com a integridade pega na tbl_solucao
	##############################################*/
		/*if($login_fabrica==24){
		$xsql="SELECT tbl_os.defeito_constatado,
					tbl_defeito_constatado.descricao AS defeito_constatado_descricao, 
					tbl_os.solucao_os , 
					tbl_solucao.descricao AS solucao_descricao, 
					count(os) AS qtde 
				FROM tbl_os 
				join tbl_posto on tbl_os.posto=tbl_posto.posto 
				JOIN tbl_os_extra using(os) 
				JOIN tbl_extrato using (extrato) 
				LEFT JOIN tbl_defeito_constatado on tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado 
				LEFT JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao
				WHERE tbl_os.produto=$produto 
				AND tbl_os.fabrica=$login_fabrica 
				AND   $cond_5 ";
			if(strlen($estado)>0){$xsql.=" and  tbl_posto.estado = '$estado' ";}
				$xsql.=" AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
				AND tbl_os.os NOT IN( 
					SELECT DISTINCT(tbl_os.os) 
					FROM tbl_os 
					JOIN tbl_os_extra using(os) 
					join tbl_extrato using (extrato)
					JOIN tbl_produto ON tbl_produto.produto= tbl_os.produto 
					JOIN tbl_os_produto ON tbl_os.os= tbl_os_produto.os 
					WHERE tbl_os.fabrica = $login_fabrica 
					AND tbl_os.produto=$produto
					AND tbl_os.excluida IS NOT TRUE 
					AND (tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' )
				)GROUP BY tbl_os.defeito_constatado, tbl_defeito_constatado.descricao, tbl_os.solucao_os, tbl_solucao.descricao
				ORDER BY qtde desc";
		}*/
		
		if($login_fabrica==6){
	$xsql = "SELECT tbl_os.defeito_constatado,
					tbl_defeito_constatado.descricao AS defeito_constatado_descricao,
					tbl_os.solucao_os , 
					tbl_solucao.descricao AS solucao_descricao, 
					count(tbl_os.os) AS qtde 
			FROM tbl_os 
			JOIN tbl_posto on tbl_os.posto=tbl_posto.posto 
			JOIN tbl_os_extra using(os) 
			JOIN tbl_extrato using (extrato) 
			JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato 
			LEFT JOIN tbl_defeito_constatado on tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado 
			LEFT JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao
			WHERE tbl_os.produto = $produto
			AND tbl_os.fabrica = $login_fabrica
			AND $cond_5 ";
			if(strlen($estado)>0){$xsql.=" and  tbl_posto.estado = '$estado' ";}
			$xsql.=" AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
			AND tbl_os.os NOT IN( 
					SELECT DISTINCT(tbl_os.os) 
					FROM tbl_os 
					JOIN tbl_os_extra using(os) 
					join tbl_extrato using (extrato)
					JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
					JOIN tbl_produto ON tbl_produto.produto= tbl_os.produto 
					JOIN tbl_os_produto ON tbl_os.os= tbl_os_produto.os 
					WHERE tbl_os.fabrica = $login_fabrica
					AND tbl_os.produto   = $produto
					AND tbl_os.excluida IS NOT TRUE 
					AND ( tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59')
			)GROUP BY tbl_os.defeito_constatado, tbl_defeito_constatado.descricao, tbl_os.solucao_os, tbl_solucao.descricao 
			ORDER BY qtde desc";

		if($tipo_pesquisa=="data_abertura"){

	$xsql = "SELECT tbl_os.defeito_constatado,
					tbl_defeito_constatado.descricao AS defeito_constatado_descricao,
					tbl_os.solucao_os , 
					tbl_solucao.descricao AS solucao_descricao, 
					count(tbl_os.os) AS qtde 
			FROM tbl_os 
			JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os
			JOIN tbl_posto on tbl_os.posto = tbl_posto.posto 
			LEFT JOIN tbl_defeito_constatado on tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado 
			LEFT JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao
			WHERE tbl_os.produto = $produto
			AND tbl_os.fabrica = $login_fabrica
			AND $cond_5 ";
			if(strlen($estado)>0){$xsql.=" and  tbl_posto.estado = '$estado' ";}
			$xsql.=" AND tbl_os.data_abertura BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
			AND tbl_os.os NOT IN( 
					SELECT DISTINCT(tbl_os.os) 
					FROM tbl_os 
					JOIN tbl_produto ON tbl_produto.produto= tbl_os.produto 
					JOIN tbl_os_produto ON tbl_os.os= tbl_os_produto.os 
					WHERE tbl_os.fabrica = $login_fabrica
					AND tbl_os.produto   = $produto
					AND tbl_os.excluida IS NOT TRUE 
					AND ( tbl_os.data_abertura BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59')
			)GROUP BY tbl_os.defeito_constatado, tbl_defeito_constatado.descricao, tbl_os.solucao_os, tbl_solucao.descricao 
			ORDER BY qtde desc";
		}


		}
	if($login_fabrica==24){
		$xres = pg_exec($con, $xsql);


		$qtde_por_defeito= pg_numrows($xres);
		if(pg_numrows($xres) > 0){
			echo "<br><TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>";
			echo "<TR>";
			echo "<TD colspan='5' class='titChamada10' align = 'center' >OS sem peças</TD>";
			echo "</TR>";
			echo "<TR>";
			echo "<TD class='titChamada10'>Produto</TD>";
			echo "<TD class='titChamada10'>Defeito Constatado</TD>";
			echo "<TD class='titChamada10'>Solucao</TD>";
			echo "<TD class='titChamada10'>%</TD>";
			echo "<TD class='titChamada10'>Quantidade</TD>";
			echo "</TR>";
			for($a=0; $a<pg_numrows($xres); $a++){
				$defeito_constatado                  = pg_result($xres,$a,defeito_constatado);
				$solucao                             = pg_result($xres,$a,solucao_os);
				$qtde = pg_result($xres,$a,qtde);
				if ($login_fabrica == 20 and $pais != 'BR'){
					$defeito_constatado_descricao = pg_result($xres,$a,defeito_constatado_descricao_espanhol);
					$solucao_descricao            = pg_result($xres,$a,solucao_descricao_espanhol);
				}
				else {
					$defeito_constatado_descricao = pg_result($xres,$a,defeito_constatado_descricao);
					$solucao_descricao            = pg_result($xres,$a,solucao_descricao);
				}
				$qtde = pg_result($xres,$a,qtde);
				if ($qtde_sem > 0) {
					$xporcentagem = ($qtde * 100)/$qtde_sem;
				} else {
					$xporcentagem = 0;
				}

				if($defeito_constatado_descricao==''){
					$defeito_constatado_descricao="Os sem defeito selecionados";
					$defeito_constatado	="00";
				}
				if($solucao_descricao=='' and $login_fabrica<>15){ $solucao	="00"; }
				if($solucao_descricao==''){
					if($login_fabrica==3 AND 1==2){
						$xxsql = "select solucao, descricao from tbl_solucao where fabrica=$login_fabrica";;
						$xxres = pg_exec($con, $xxsql);
						$solucao	= pg_result($xxres,0,solucao);
						$solucao_descricao				= pg_result($xxres,0,descricao);
					}else{
						$solucao_descricao="Os sem solução selecionadas";
					}
				}
				$xcor = ($a % 2 == 0) ? "#FEFEFE": '#F9FCFF';
				echo "<TR bgcolor='$xcor'>";
				echo "<TD class='conteudo101' nowrap>$descricao_produto</TD>";
				echo "<TD class='conteudo101'><a href='javascript:AbreSemPeca(\"$produto\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$pais\",\"$posto\",\"$defeito_constatado\",\"$origem\",\"$solucao\",\"$tipo_pesquisa\")'>$defeito_constatado_descricao</A></TD>";
				echo "<TD class='conteudo101'>$solucao_descricao</TD>";
				echo "<TD class='conteudo101' align='center'>". number_format($xporcentagem,2,",",".") ."</TD>";
				echo "<TD class='conteudo101' align='center'>$qtde</TD>";
				echo "</TR>";
			}
			echo "</TABLE><BR><BR>";
		}
	//fim takashi	
	}
	flush();
}
?>

</TABLE>

</BODY>
</HTML>
