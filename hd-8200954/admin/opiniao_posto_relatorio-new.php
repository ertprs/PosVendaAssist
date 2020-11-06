<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="gerencia";
include 'autentica_admin.php';

$linha = $_POST['linha'];
if (strlen ($linha) == 0) $linha = $_GET['linha'];

$distribuidor_posto = $_POST['distribuidor_posto'];
if (strlen ($distribuidor_posto) == 0) $distribuidor_posto = $_GET['distribuidor_posto'];

$distribuidor = $_POST['distribuidor'];
if (strlen ($distribuidor) == 0) $distribuidor = $_GET['distribuidor'];

$estado = $_POST['estado'];
if (strlen ($estado) == 0) $estado = $_GET['estado'];

$title       = "Relatório Opinião Posto";
$cabecalho   = "Relatório Opinião Posto";
$layout_menu = "gerencia";
include 'cabecalho.php';

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.lnk {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#FF00CC;
}

.pesquisa {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

.table_line_2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
	color: #596d9b
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

</style>
<!--INICIO LINHA, DISTRIBUIDOR E ESTADO-->
<br>
<form name="frm_opiniao_posto" method="post" action="<? $PHP_SELF ?>">
<table width='700' border='1' cellspacing='0' cellpadding='0' bordercolor='#ced7e7'>
<tr>
<td class='table_line_2'>&nbsp;</td>
</tr>

<tr>
<td class='table_line_2'>&nbsp;Exibir o relatório dos postos da linha: &nbsp;
<?
$sql = "SELECT  *
		FROM    tbl_linha
		WHERE   tbl_linha.fabrica = $login_fabrica
		ORDER BY tbl_linha.nome;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<select style='width: 150px;' name='linha'>\n";
	echo "<option value=''>TODAS</option>\n";
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$aux_linha = trim(pg_result($res,$x,linha));
		$aux_nome  = trim(pg_result($res,$x,nome));
		echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
	}
	echo "</select>\n";
}
?>
</td>
</tr>
<tr>
<td class='table_line_2'>&nbsp;</td>
</tr>
</table>

<br>

<table width='700' border='1' cellspacing='0' cellpadding='0' bordercolor='#ced7e7'>
<tr>
<td class='table_line_2'>&nbsp;</td>
</tr>
<tr>
<td class='table_line_2'><input type="radio" name="distribuidor_posto" value="TODOS" <? if (strlen($distribuidor_posto) == 0 OR $distribuidor_posto == 'TODOS') echo " checked"; ?>>Exibir todos os Postos</td>
</tr>
<tr>
<td class='table_line_2'><input type="radio" name="distribuidor_posto" value="DISTRIB" <? if ($distribuidor_posto == 'DISTRIB') echo " checked"; ?>>Exibir somente distribuidores</td>
</tr>
<tr>
<td class='table_line_2'><input type="radio" name="distribuidor_posto" value="DIRETO" <? if ($distribuidor_posto == 'DIRETO') echo " checked"; ?>>Somente postos com pedidos diretos</td>
</tr>
<tr>
<td class='table_line_2'><input type="radio" name="distribuidor_posto" value="VIA-DISTRIB" <? if ($distribuidor_posto == 'VIA-DISTRIB') echo " checked"; ?>>Somente postos via distribuidor &nbsp;
<?

$sql = "SELECT	tbl_posto.posto,
				tbl_posto.nome 
		FROM	tbl_posto
		JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN    tbl_tipo_posto USING (tipo_posto)
		WHERE	tbl_tipo_posto.distribuidor IS TRUE
		ORDER BY tbl_posto.nome;";
$res = pg_exec ($con,$sql);
//echo $distribuidor;

if (pg_numrows($res) > 0) {

	echo "<select style='width: 400px;' name='distribuidor'>\n";
	echo "<option value='' selected>TODAS</option>\n";
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$aux_posto = trim(pg_result($res,$x,posto));
		$aux_nome  = trim(pg_result($res,$x,nome));
		echo "<option value='$aux_posto'"; if ($_POST['distribuidor'] == $aux_posto) echo " SELECTED "; echo ">$aux_nome</option>\n";
	}
	echo "</select>\n";
}
?>
</td>
</tr>
<tr>
<td class='table_line_2'>&nbsp;</td>
</tr>
</table>
<br>

<table width='700' border='1' cellspacing='0' cellpadding='0' bordercolor='#ced7e7'>
<tr>
<td class='table_line_2'>&nbsp;</td>
</tr>
<tr>
<td class='table_line_2'>&nbsp;Exibir o relatório dos postos do estado: &nbsp;
<?
$sql = "SELECT  estado
		FROM    tbl_estado
		ORDER BY estado;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<select style='width: 80px;' name='estado'>\n";
	echo "<option value=''>TODOS</option>\n";
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$aux_estado = trim(pg_result($res,$x,estado));
		$aux_nome  = trim(pg_result($res,$x,nome));
		echo "<option value='$aux_estado'"; if ($estado == $aux_estado) echo " SELECTED "; echo ">$aux_estado</option>\n";
	}
	echo "</select>\n";	
}
?>
</td>
</tr>
<tr>
<td class='table_line_2'>&nbsp;</td>
</tr>
</table>
<!--FIM LINHA, DISTRIBUIDOR E ESTADO-->
<br>

<input type='hidden' name='btn_acao' value=''>
<table width='700' border='0' cellspacing='0' cellpadding='0'>
<tr>
	<td><img src='imagens/btn_relatorio.gif' style='cursor: pointer;' onclick="javascript: document.frm_opiniao_posto.btn_acao.value='relatorio' ; document.frm_opiniao_posto.submit() ; " ALT='Relatório' border='0'></td>
</tr>
<?
if (strlen($linha) > 0 OR strlen($distribuidor_posto) > 0 OR strlen($distribuidor) > 0){
?>
<tr>
	<td>&nbsp;</td>
</tr>
<tr>
	<td><img src='imagens/btn_imprimir.gif' style='cursor: pointer;' onclick="javascript: window.open('opiniao_posto_relatorio_print.php?<? echo "linha=$linha&distribuidor_posto=$distribuidor_posto&distribuidor=$distribuidor&estado=$estado"; ?>','print','resizable=1,toolbar=1,scrollbars=1,width=640,height=480,top=0,left=0');" ALT='Relatório' border='0'></td>
</tr>
<?
}
?>
</table>
</form>



<?

flush();

$res = pg_exec ($con,"SELECT opiniao_posto FROM tbl_opiniao_posto WHERE fabrica = $login_fabrica AND ativo IS TRUE ");
if (pg_numrows ($res) == 0) {
	echo "<center><h2>Não existem pesquisas de opinião ativas</h2></center>\n";
	exit;
}
$opiniao_posto = pg_result ($res,0,0);

$x_distribuidor = $distribuidor_posto;
//if ($distribuidor_posto == "X") $x_distribuidor = $distribuidor ;

$link_get = "";
if (strlen($distribuidor) == 0){
	$link_get = "linha=$linha&estado=$estado&distribuidor_posto=$x_distribuidor";
} else if (strlen($distribuidor) > 0){ 
	$link_get = "linha=$linha&estado=$estado&distribuidor_posto=$x_distribuidor&distribuidor=$distribuidor";
}

if (strlen ($linha) == 0) {
	$linha_selecao = " 1=1 " ;
}else{
	$linha_selecao = " tbl_posto.posto IN (SELECT tbl_posto_linha.posto FROM tbl_posto_linha WHERE tbl_posto_linha.linha = $linha) " ;
}

if (strlen ($estado) == 0) {
	$estado_selecao = " 1=1 " ;
}else{
	$estado_selecao = " tbl_posto.estado = '$estado' " ;
}

if ($distribuidor_posto == 'TODOS'){
	$distribuidor_selecao = "1=1" ;
}

if ($distribuidor_posto == 'DIRETO'){
	$distribuidor_selecao = " tbl_posto_fabrica.distribuidor is NULL " ;
}
//echo " :: $distribuidor ::";
if ($distribuidor_posto == 'VIA-DISTRIB'){
	if (strlen ($distribuidor) == 0) {
		$distribuidor_selecao = " tbl_posto_fabrica.distribuidor notnull " ;
	}else{
		$distribuidor_selecao = " tbl_posto_fabrica.distribuidor = '$distribuidor'";
	}
}

$btn_acao = $_POST['btn_acao'];
if ($btn_acao == "relatorio") {

	$sql = "SELECT DISTINCT tbl_posto.posto, tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_posto.estado
			INTO TEMP TABLE TMP_posto 
			FROM tbl_posto 
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_posto_linha   ON tbl_posto.posto = tbl_posto_linha.posto
			JOIN tbl_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
			JOIN tbl_tipo_posto    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto 
			WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO' ";

	if (strlen ($linha) > 0) $sql .= " AND tbl_posto_linha.linha = $linha " ;
	if ($distribuidor_posto == 'DIRETO') $sql .= " AND tbl_posto_fabrica.distribuidor IS NULL ";
	if ($distribuidor_posto == 'DISTRIB') $sql .= " AND tbl_tipo_posto.distribuidor IS TRUE ";
	if ($distribuidor_posto == 'VIA-DISTRIB') $sql .= " AND tbl_posto_fabrica.distribuidor = $distribuidor ";
	if (strlen ($estado) > 0) $sql .= " AND tbl_posto.estado = '$estado' ";
		
	$res = pg_exec ($con,$sql);
			
			

	
	
/*	
	$sql = "SELECT COUNT(tbl_posto_fabrica.posto) AS qtde 
			FROM tbl_posto_fabrica 
			JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
			AND tbl_posto_fabrica.fabrica = $login_fabrica 
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
			AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND $linha_selecao 
			AND $estado_selecao 
			AND $distribuidor_selecao ";
*/

	$sql = "SELECT COUNT(tbl_posto_fabrica.posto) AS qtde 
			FROM tbl_posto_fabrica 
			JOIN TMP_posto ON tbl_posto_fabrica.posto = TMP_posto.posto  ";
	$res = pg_exec ($con,$sql);
	$qtde_posto = pg_result ($res,0,0);

/*
	$sql = "SELECT	tbl_posto_fabrica.posto       ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome                 
			FROM tbl_posto_fabrica 
			JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN (
				SELECT DISTINCT posto FROM tbl_opiniao_posto_resposta 
				JOIN tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta) 
				JOIN tbl_opiniao_posto USING (opiniao_posto) 
				WHERE tbl_opiniao_posto.opiniao_posto = $opiniao_posto
			) resp ON resp.posto = tbl_posto_fabrica.posto
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
			AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND   $linha_selecao
			AND   $estado_selecao
			AND   $distribuidor_selecao
			ORDER BY tbl_posto.nome";
*/


	$sql = "SELECT	TMP_posto.nome                 
			FROM TMP_posto 
			JOIN (
				SELECT DISTINCT posto FROM tbl_opiniao_posto_resposta 
				JOIN tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta) 
				JOIN tbl_opiniao_posto USING (opiniao_posto) 
				WHERE tbl_opiniao_posto.opiniao_posto = $opiniao_posto
			) resp ON resp.posto = TMP_posto.posto
			ORDER BY TMP_posto.nome";


	$res4 = pg_exec ($con,$sql);

	$qtde_resposta = pg_numrows ($res4);

	$sql = "SELECT	tbl_posto_fabrica.posto       ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome                ,
					tbl_posto.fone                ,
					resp.posto AS resp_posto      
			FROM tbl_posto_fabrica 
			JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
			LEFT JOIN (
				SELECT DISTINCT posto FROM tbl_opiniao_posto_resposta 
				JOIN tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta) 
				JOIN tbl_opiniao_posto USING (opiniao_posto) 
				WHERE tbl_opiniao_posto.opiniao_posto = $opiniao_posto
			) resp ON resp.posto = tbl_posto_fabrica.posto
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
			AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND   resp.posto IS NULL
			AND   $linha_selecao
			AND   $estado_selecao
			AND   $distribuidor_selecao
			ORDER BY tbl_posto.nome";
	$res3 = pg_exec ($con,$sql);

	$qtde_sem_resposta = pg_numrows ($res3);

	echo "<br>\n";
	echo "<table width='700'>\n";
	echo "<tr>\n";
	echo "<td colspan='3' CLASS='pesquisa'>\n";
	echo "QUANTIDADE DE POSTOS CREDENCIADOS: " .$qtde_posto;
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='pesquisa'>POSTOS QUE RESPONDERAM</td>\n";
	echo "<td CLASS='menu_top'>$qtde_resposta</td>\n";
	echo "<td CLASS='menu_top'><a href=$PHP_SELF?listartudo=2&$link_get>CLIQUE AQUI PARA CONSULTAR</a></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td CLASS='pesquisa'>POSTOS QUE NÃO RESPONDERAM</td>\n";
	echo "<td CLASS='menu_top'>$qtde_sem_resposta </td>\n";
	echo "<td CLASS='menu_top'><a href=$PHP_SELF?listartudo=3&$link_get>CLIQUE AQUI PARA CONSULTAR</a></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<br>\n";


	$sql = "SELECT	tbl_opiniao_posto_pergunta.pergunta              ,
					tbl_opiniao_posto_pergunta.tipo_resposta         ,
					tbl_opiniao_posto_pergunta.opiniao_posto_pergunta
			FROM	tbl_opiniao_posto_pergunta
			JOIN	tbl_opiniao_posto ON tbl_opiniao_posto.opiniao_posto = tbl_opiniao_posto_pergunta.opiniao_posto
			WHERE	tbl_opiniao_posto.fabrica = $login_fabrica
			ORDER BY tbl_opiniao_posto_pergunta.tipo_resposta        , 
					tbl_opiniao_posto_pergunta.ordem;";
	$res = pg_exec($con,$sql);

	echo "<table width='700'>\n";

	if (pg_numrows($res) > 0){
		for ($i=0; $i <pg_numrows($res); $i++){
			$opiniao_posto_pergunta = pg_result($res,$i,opiniao_posto_pergunta);
			$pergunta               = pg_result($res,$i,pergunta);
			$tipo_resposta          = pg_result($res,$i,tipo_resposta);
			
			$sql = "SELECT
					(
						SELECT	count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_muito_satisfeito
						FROM	tbl_opiniao_posto_resposta
						JOIN	tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
						JOIN	tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
						JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
						WHERE	tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
						AND		tbl_opiniao_posto_resposta.resposta               = 'muito satisfeito'
						AND		tbl_opiniao_posto_pergunta.tipo_resposta in ('F')
						AND		$linha_selecao
						AND		$estado_selecao
						AND		$distribuidor_selecao
					) AS muito_satisfeito,
					( 
						SELECT	count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_satisfeito
						FROM	tbl_opiniao_posto_resposta
						JOIN	tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
						JOIN	tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
						JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
						WHERE	tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
						AND		tbl_opiniao_posto_resposta.resposta               = 'satisfeito'
						AND		tbl_opiniao_posto_pergunta.tipo_resposta in ('F')
						AND		$linha_selecao
						AND		$estado_selecao
						AND		$distribuidor_selecao
					) AS satisfeito,
					(
						SELECT	count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_nem_nem
						FROM	tbl_opiniao_posto_resposta
						JOIN	tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
						JOIN	tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
						JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
						WHERE	tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
						AND		tbl_opiniao_posto_resposta.resposta               = 'nem satisfeito nem insatisfeito'
						AND		tbl_opiniao_posto_pergunta.tipo_resposta in ('F')
						AND		$linha_selecao
						AND		$estado_selecao
						AND		$distribuidor_selecao
					) AS nem_satisfeito_nem_insatisfeito,
					( 
						SELECT	count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_muito_insatisfeito
						FROM	tbl_opiniao_posto_resposta
						JOIN	tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
						JOIN	tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
						JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
						WHERE	tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
						AND		tbl_opiniao_posto_resposta.resposta               = 'muito insatisfeito'
						AND		tbl_opiniao_posto_pergunta.tipo_resposta in ('F')
						AND		$linha_selecao
						AND		$estado_selecao
						AND		$distribuidor_selecao
					) AS muito_insatisfeito,
					( 
						SELECT	count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_insatisfeito
						FROM	tbl_opiniao_posto_resposta
						JOIN	tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
						JOIN	tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
						JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
						WHERE	tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
						AND		tbl_opiniao_posto_resposta.resposta               = 'insatisfeito'
						AND		tbl_opiniao_posto_pergunta.tipo_resposta in ('F')
						AND		$linha_selecao
						AND		$estado_selecao
						AND		$distribuidor_selecao
					) AS insatisfeito,
					( 
						SELECT	count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_sim
						FROM	tbl_opiniao_posto_resposta
						JOIN	tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
						JOIN	tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
						JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
						WHERE	tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
						AND		tbl_opiniao_posto_resposta.resposta               = 't'
						AND		tbl_opiniao_posto_pergunta.tipo_resposta in ('S')
						AND		$linha_selecao
						AND		$estado_selecao
						AND		$distribuidor_selecao
					) AS sim,
					( 
						SELECT	count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_nao
						FROM	tbl_opiniao_posto_resposta
						JOIN	tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
						JOIN	tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
						JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
						WHERE	tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
						AND		tbl_opiniao_posto_resposta.resposta               = 'f'
						AND		tbl_opiniao_posto_pergunta.tipo_resposta in ('S')
						AND		$linha_selecao
						AND		$estado_selecao
						AND		$distribuidor_selecao
					) AS nao,
					( 
						SELECT	count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_muito_progresso
						FROM	tbl_opiniao_posto_resposta
						JOIN	tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
						JOIN	tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
						JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
						WHERE	tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
						AND		tbl_opiniao_posto_resposta.resposta               = 'muito progresso'
						AND		tbl_opiniao_posto_pergunta.tipo_resposta in ('P')
						AND		$linha_selecao
						AND		$estado_selecao
						AND		$distribuidor_selecao
					) AS muito_progresso,
					( 
						SELECT	count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_melhorou
						FROM	tbl_opiniao_posto_resposta
						JOIN	tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
						JOIN	tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
						JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
						WHERE	tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
						AND		tbl_opiniao_posto_resposta.resposta               = 'melhorou'
						AND		tbl_opiniao_posto_pergunta.tipo_resposta in ('P')
						AND		$linha_selecao
						AND		$estado_selecao
						AND		$distribuidor_selecao
					) AS melhorou,
					( 
						SELECT	count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_permaneceu_igual
						FROM	tbl_opiniao_posto_resposta
						JOIN	tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
						JOIN	tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
						JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
						WHERE	tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
						AND		tbl_opiniao_posto_resposta.resposta               = 'permaneceu igual'
						AND		tbl_opiniao_posto_pergunta.tipo_resposta in ('P')
						AND		$linha_selecao
						AND		$estado_selecao
						AND		$distribuidor_selecao
					) AS permaneceu_igual,
					( 
						SELECT	count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_piorou
						FROM	tbl_opiniao_posto_resposta
						JOIN	tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
						JOIN	tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
						JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
						WHERE	tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
						AND		tbl_opiniao_posto_resposta.resposta               = 'piorou'
						AND		tbl_opiniao_posto_pergunta.tipo_resposta in ('P')
						AND		$linha_selecao
						AND		$estado_selecao
						AND		$distribuidor_selecao
					) AS piorou;";
			$res2 = pg_exec($con,$sql);

if ($ip == '192.168.0.66') echo "&middot; ".nl2br($sql)." <br><br>";

			if ($tipo_resposta == 'S'){
				echo "<tr><td CLASS='pesquisa' colspan='2' ><div align='left'><b>$pergunta</b></div></td></tr>\n";
			}else 	if ($tipo_resposta == 'F'){
				echo "<tr><td CLASS='pesquisa' colspan='5' ><div align='left'><b>$pergunta</b></div></td></tr>\n";
			}else 	if ($tipo_resposta == 'P'){
				echo "<tr><td CLASS='pesquisa' colspan='4' ><div align='left'><b>$pergunta</b></div></td></tr>\n";
			}
		
			$stotal   = 0;
			$perc_sim = 0;
			$perc_nao = 0; 
			if ($tipo_resposta == 'S'){
				$sim = pg_result ($res2,0, sim);
				$nao = pg_result ($res2, 0, nao);
				$stotal = $stotal + $sim + $nao;
				if ($stotal > 0) $perc_sim  = ($sim/$stotal)*100;
				$perc_sim  = number_format ($perc_sim,0);
				if ($stotal > 0) $perc_nao  = ($nao/$stotal)*100;
				$perc_nao  = number_format ($perc_nao,0);

				echo "<tr>\n";
				echo "<td class='pesquisa'>SIM</td>\n";
				echo "<td class='pesquisa'>NÃO</td>\n";
				echo "</tr>\n";
				echo "<tr>\n";
				echo "<td class='menu_top'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=t' ";
				if ($sim > $nao) echo "style=color:#CC0000";
				echo ">$sim ($perc_sim %)</a></td>\n";
				echo "<td class='menu_top'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=f' ";
				if ($nao > $sim) echo "style=color:#CC0000";
				echo ">$nao ($perc_nao %)</a></td>\n";
				echo "</tr>\n";
				echo "<tr><td colspan='2' >&nbsp;</td></tr>\n";
			}
			
			$total = 0;
			$perc_muito_satisfeito = 0;
			$perc_satisfeito = 0;
			$perc_nem_satisfeito_nem_insatisfeito = 0;
			$perc_insatisfeito = 0;
			$perc_muito_insatisfeito = 0;
			if ($tipo_resposta == 'F'){
				$campo[0]    = pg_result ($res2,0, muito_satisfeito);
				$campo[1]    = pg_result ($res2,0, satisfeito);
				$campo[2]    = pg_result ($res2,0, nem_satisfeito_nem_insatisfeito);
				$campo[3]    = pg_result ($res2,0, insatisfeito);
				$campo[4]    = pg_result ($res2,0, muito_insatisfeito);
				$total = $total + $campo[0] + $campo[1] + $campo[2] + $campo[3] + $campo[4];

				if ($total > 0) $perc_muito_satisfeito= ($campo[0]/$total)*100;
				$perc_muito_satisfeito                = number_format ($perc_muito_satisfeito,0);
				
				if ($total > 0) $perc_satisfeito      = ($campo[1]/$total)*100;
				$perc_satisfeito                      = number_format ($perc_satisfeito,0);
				
				if ($total > 0) $perc_nem_satisfeito_nem_insatisfeito = ($campo[2]/$total)*100;
				$perc_nem_satisfeito_nem_insatisfeito = number_format ($perc_nem_satisfeito_nem_insatisfeito,0);
				
				if ($total > 0) $perc_insatisfeito                    = ($campo[3]/$total)*100;
				$perc_insatisfeito                    = number_format ($perc_insatisfeito,0);
				
				if ($total > 0) $perc_muito_insatisfeito              = ($campo[4]/$total)*100;
				$perc_muito_insatisfeito              = number_format ($perc_muito_insatisfeito,0);
				
				for($j=0; $j<4; $j++){
					$posMaior[$j] = 0;
				}

				$respMaior = 0;

				for ($r=0; $r<4; $r++){
					if ($respMaior < $campo[$r]){
						$respMaior = $campo[$r];
						$posMaior[$r] = 1;
						for($j=0; $j<$r; $j++){
							$posMaior[$j] = 0;
						}
						
					}
				}

				echo "<tr>\n";
				echo "<td class='pesquisa'>Muito Satisfeito</td>\n";
				echo "<td class='pesquisa'>Satisfeito</td>\n";
				echo "<td class='pesquisa'>Nem Satisfeito/ Nem Insatisfeito</td>\n";
				echo "<td class='pesquisa'>Insatisfeito</td>\n";
				echo "<td class='pesquisa'>Muito Insatisfeito</td>\n";
				echo "</tr>\n";
				echo "<tr>\n";
		
				echo "<td class='menu_top'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=t' ";
		
				echo "<td class='menu_top'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=muito satisfeito' ";
				if ($posMaior[0] == 1) echo "style=color:#CC0000";
				echo ">$campo[0] ($perc_muito_satisfeito %)</a></td>\n";

				echo "<td class='menu_top'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=satisfeito' ";
				if ($posMaior[1] == 1) echo "style=color:#CC0000";
				echo ">$campo[1]  ($perc_satisfeito %)</a></td>\n";

				echo "<td class='menu_top'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=nem satisfeito nem insatisfeito'";
				if ($posMaior[2] == 1) echo "style=color:#CC0000";
				echo ">$campo[2]  ($perc_nem_satisfeito_nem_insatisfeito %)</a></td>\n";

				echo "<td class='menu_top'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=insatisfeito'";
				if ($posMaior[3] == 1) echo "style=color:#CC0000";
				echo ">$campo[3]  ($perc_insatisfeito %)</a></td>\n";

				echo "<td class='menu_top'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=muito insatisfeito'";
				if ($posMaior[4] == 1) echo "style=color:#CC0000";
				echo ">$campo[4]  ($perc_muito_insatisfeito %)</a></td>\n";
				
				echo "</tr>\n";
				echo "<tr><td colspan='5' >&nbsp;</td></tr>\n";
			}

			$total = 0;
			$perc_muito_progresso = 0;
			$perc_melhorou = 0;
			$perc_permaneceu_igual = 0;
			$perc_piorou = 0;
			if ($tipo_resposta == 'P'){
			
				$campo[5]               = pg_result ($res2,0, muito_progresso);
				$campo[6]               = pg_result ($res2,0, melhorou);
				$campo[7]               = pg_result ($res2,0, permaneceu_igual);
				$campo[8]               = pg_result ($res2,0, piorou);

				$total = $total + $campo[5] + $campo[6] + $campo[7] + $campo[8];

				$perc_muito_progresso   = ($campo[5]/$total)*100;
				$perc_muito_progresso   = number_format ($perc_muito_progresso,0);
				$perc_melhorou          = ($campo[6]/$total)*100;
				$perc_melhorou          = number_format ($perc_melhorou,0);
				$perc_permaneceu_igual  = ($campo[7]/$total)*100;
				$perc_permaneceu_igual  = number_format ($perc_permaneceu_igual,0);
				$perc_piorou            = ($campo[8]/$total)*100;
				$perc_piorou            = number_format ($perc_piorou,0);

				
				for($j=5; $j<8; $j++){
					$posMaior[$j] = 0;
				}

				$respMaior = 0;

				for ($r=5; $r<8; $r++){
					if ($respMaior < $campo[$r]){
						$respMaior = $campo[$r];
						$posMaior[$r] = 1;
						for($j=5; $j<$r; $j++){
							$posMaior[$j] = 0;
						}
						
					}
				}


				echo "<tr>\n";
				echo "<td class='pesquisa'>MUITO PROGRESSO</td>\n";
				echo "<td class='pesquisa'>MELHOROU</td>\n";
				echo "<td class='pesquisa'>PERMANECEU IGUAL</td>\n";
				echo "<td class='pesquisa'>PIOROU</td>\n";
				echo "</tr>\n";
				echo "<tr>\n";

				echo "<td class='menu_top'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=muito progresso'";
				if ($posMaior[5] == 1) echo "style=color:#CC0000";
				echo ">$campo[5] ($perc_muito_progresso %)</a></td>\n";

				echo "<td class='menu_top'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=melhorou'";
				if ($posMaior[6] == 1) echo "style=color:#CC0000";
				echo ">$campo[6] ($perc_melhorou %)</a></td>\n";

				echo "<td class='menu_top'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=permaneceu igual'";
				if ($posMaior[7] == 1) echo "style=color:#CC0000";
				echo ">$campo[7] ($perc_permaneceu_igual %)</a></td>\n";

				echo "<td class='menu_top'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=piorou'";
				if ($posMaior[8] == 1) echo "style=color:#CC0000";
				echo ">$campo[8] ($perc_piorou %)</a></td>\n";

				echo "</tr>\n";
				echo "<tr><td colspan='4' >&nbsp;</td></tr>\n";
			}
		}//fim for

	}//if
		echo "</table>\n";	

}


$listartudo = $_GET['listartudo'];
if ($listartudo == 2){
	$sql = "SELECT tbl_posto_fabrica.posto, tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_posto.email 
		FROM tbl_posto_fabrica 
		JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
		JOIN (
			SELECT DISTINCT posto FROM tbl_opiniao_posto_resposta 
			JOIN tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta) 
			JOIN tbl_opiniao_posto USING (opiniao_posto) 
			WHERE tbl_opiniao_posto.opiniao_posto = $opiniao_posto
		) resp ON resp.posto = tbl_posto_fabrica.posto
		WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
		AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
		AND   $linha_selecao
		AND   $estado_selecao
		AND   $distribuidor_selecao
		ORDER BY tbl_posto.nome;";
	$res4 = pg_exec ($con,$sql);
//echo $sql;
//echo pg_numrows($res4);
	$qtde_resposta = pg_numrows ($res4);
//echo $sql;
	echo "<table width='700'>\n";
	echo "<tr class=pesquisa><td colspan='3'>RELAÇÃO DOS POSTOS QUE RESPONDERAM</td></tr>\n";
	
	for ($i = 0 ; $i < pg_numrows ($res4) ; $i++) {
		$codigo_posto  = pg_result ($res4,$i,codigo_posto);
		$nome_posto    = pg_result ($res4,$i,nome);
		$email_posto   = pg_result ($res4,$i,email);
		echo "<tr class='menu_top'>\n";
		echo "<td align='left'>&nbsp; $codigo_posto</td>\n";
		echo "<td align='left'><a href='$PHP_SELF?listartudo=2&listardetalhes=4&codigo_posto=$codigo_posto&$link_get'>&nbsp; $nome_posto</a></td>\n";
		echo "<td align='left'>&nbsp; <a href='mailto:$email_posto'>$email_posto</a></td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
	echo "<br>\n";
}

if ($listartudo == 3){
	$sql = "SELECT  tbl_posto_fabrica.posto        ,
					tbl_posto_fabrica.codigo_posto ,
					tbl_posto.nome                 ,
					tbl_posto.fone                 ,
					resp.posto AS resp_posto
			FROM tbl_posto_fabrica 
			JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
			LEFT JOIN (
				SELECT DISTINCT posto FROM tbl_opiniao_posto_resposta 
				JOIN tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta) 
				JOIN tbl_opiniao_posto USING (opiniao_posto) 
				WHERE tbl_opiniao_posto.opiniao_posto = $opiniao_posto
			) resp ON resp.posto = tbl_posto_fabrica.posto
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
			AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND   resp.posto IS NULL
			AND   $linha_selecao
			AND   $estado_selecao
			AND   $distribuidor_selecao
			ORDER BY tbl_posto.nome";
	$res3 = pg_exec ($con,$sql);
//	echo pg_numrows($res3);
	$qtde_sem_resposta = pg_numrows ($res3);

	echo "<table width='700'>\n";
	echo "<tr class=pesquisa><td colspan='3'>RELAÇÃO DOS POSTOS QUE NÃO RESPONDERAM</td></tr>\n";
	for ($i = 0 ; $i < pg_numrows ($res3) ; $i++) {
		$codigo_posto = pg_result ($res3,$i,codigo_posto);
		$nome_posto   = pg_result ($res3,$i,nome);
		$fone_posto   = pg_result ($res3,$i,fone);
		echo "<tr class='menu_top'>\n";
		echo "<td align='left'>&nbsp; $codigo_posto</td>\n";
		echo "<td align='left'>&nbsp; $nome_posto</td>\n";
		echo "<td align='left'>&nbsp; $fone_posto</td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
	echo "<br>\n";
}

if ($listartudo == 4){
	$pergunta = $_GET['pergunta'];
	$resposta = $_GET['resposta'];

	$sql = "SELECT 	tbl_posto_fabrica.codigo_posto, 
					tbl_posto.nome ,
					tbl_posto.email
			FROM	tbl_posto_fabrica 
			JOIN	tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
			JOIN	tbl_opiniao_posto_resposta ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
			WHERE	tbl_posto_fabrica.fabrica                         = $login_fabrica 
			AND		tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $pergunta
			AND		tbl_opiniao_posto_resposta.resposta               = '$resposta'
			ORDER BY tbl_posto.nome";
	$resX = pg_exec ($con,$sql);

	$sql = "SELECT	pergunta
			FROM	tbl_opiniao_posto_pergunta
			WHERE   opiniao_posto_pergunta = $pergunta";
	$resY = pg_exec($con,$sql);

	$desc_pergunta = pg_result($resY,0,pergunta);

	echo "<table width='700'>\n";
	if ($resposta == 'f') $resposta = 'não';
	if ($resposta == 't') $resposta = 'sim';
	echo "<tr class=pesquisa><td colspan='3'>RELAÇÃO DOS POSTOS QUE RESPONDERAM \"".ucfirst($resposta)."\" A QUESTÃO \"".$desc_pergunta."\"</td></tr>\n";
	for ($i = 0 ; $i < pg_numrows ($resX) ; $i++) {
		$codigo_posto = pg_result ($resX,$i,codigo_posto);
		$nome_posto   = pg_result ($resX,$i,nome);
		$email_posto  = pg_result ($resX,$i,email);
		echo "<tr class='menu_top'>\n";
		echo "<td align='left'>&nbsp; $codigo_posto</td>\n";
		echo "<td align='left'>&nbsp; $nome_posto</td>\n";
		echo "<td align='left'>&nbsp; <a href='mailto:$email_posto'>$email_posto</a></td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
	echo "<br>\n";
}


if ($listardetalhes == 4){

if (strlen($_GET['codigo_posto']) > 0) $codigo_posto = $_GET['codigo_posto'];

echo "<br>";

$sql = "SELECT	tbl_opiniao_posto.cabecalho
		FROM tbl_opiniao_posto 
		WHERE opiniao_posto = $opiniao_posto";
$res = pg_exec($con,$sql);
$cabecalho = strtoupper(pg_result($res,0,cabecalho));

$sql = "SELECT tbl_posto.nome
		FROM tbl_posto
		JOIN tbl_posto_fabrica USING (posto)
		WHERE tbl_posto_fabrica.codigo_posto = $codigo_posto";
$res2 = pg_exec($con,$sql);

$posto_nome = strtoupper(pg_result($res2,0,nome));

?>

<p>
<table class="border" width='700' align='center' border='0' cellpadding="0" cellspacing="1">
	<tr class="pesquisa">
		<td align='center' colspan='2'><?echo $cabecalho?></td>
	</tr>
	<tr class="pesquisa">
		<td align='left' colspan='2'>&nbsp;POSTO: <?echo $posto_nome?></td>
	</tr>
	<TR>
		<TD class='pesquisa' WIDTH='50%'><div align='center'>&nbsp;<b>PERGUNTAS </b></div></TD>
		<TD class='pesquisa' WIDTH='50%'><div align='CENTER'>&nbsp;<b>RESPOSTAS </b></div></TD>
	</TR>
	
	<?
		$sql = "SELECT  DISTINCT 
						tbl_opiniao_posto_pergunta.pergunta,
						tbl_opiniao_posto_resposta.resposta,
						tbl_opiniao_posto_pergunta.ordem   
				FROM	tbl_opiniao_posto_resposta
				JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto         = tbl_opiniao_posto_resposta.posto 
										  AND tbl_posto_fabrica.fabrica      = $login_fabrica
										  AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'
				JOIN    tbl_opiniao_posto_pergunta ON tbl_opiniao_posto_pergunta.opiniao_posto_pergunta = tbl_opiniao_posto_resposta.opiniao_posto_pergunta
				JOIN    tbl_opiniao_posto ON tbl_opiniao_posto.opiniao_posto = tbl_opiniao_posto_pergunta.opiniao_posto
				WHERE   tbl_opiniao_posto.fabrica        = $login_fabrica
				ORDER BY tbl_opiniao_posto_pergunta.ordem;";
		$res2 = pg_exec($con,$sql);

		if (pg_numrows($res2) > 0){
			for ($i=0; $i < pg_numrows($res2); $i++){
				$pergunta = pg_result($res2,$i,pergunta);
				$resposta = pg_result($res2,$i,resposta);
				if ($resposta == 't') $resposta = 'Sim';
				if ($resposta == 'f') $resposta = 'Não';

				echo "<TR>\n";
				echo "	<TD class='menu_top'><div align='left'>&nbsp;$pergunta</div></TD>\n";
				echo "	<TD class='menu_top'><div align='left'>&nbsp;$resposta</div></TD>\n";
				echo "</TR>\n";

			}//for
		}//if

echo "</TABLE>";

}

?>

<?include "rodape.php"; ?>
