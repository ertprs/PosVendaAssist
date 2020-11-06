<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include 'autentica_admin.php';

$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];
$produto      = $_GET['produto'];
$linha        = $_GET['linha'];
$estado       = $_GET['estado'];
$familia      = $_GET['familia'];
$posto        = $_GET['posto'];

$sql = "SELECT descricao FROM tbl_produto WHERE produto = $produto";

$res = pg_exec($con,$sql);
$descricao_produto = pg_result($res,0,descricao);

$aux_data_inicial = substr($data_inicial,8,2) . "/" . substr($data_inicial,5,2) . "/" . substr($data_inicial,0,4);
$aux_data_final   = substr($data_final,8,2)   . "/" . substr($data_final,5,2)   . "/" . substr($data_final,0,4);

$title = "RELATÓRIO DE QUEBRA DE PEÇAS x CUSTO";

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE><? echo $title; ?></TITLE>
<META NAME="Generator" CONTENT="EditPlus">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">

<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 10px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size:11px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid; 
	BORDER-TOP: #990000 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid; 
	BORDER-BOTTOM: #990000 1px solid; 
	BACKGROUND-COLOR: #FF0000;
}
</style>

<script>
function AbreDefeito(peca,data_inicial,data_final,linha,estado,produto){
	janela = window.open("relatorio_field_call_rate_defeitos.php?peca=" + peca + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&produto=<? echo $produto ?>","peca",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
function AbreSerie(peca,data_inicial,data_final,linha,estado,produto){
	janela = window.open("relatorio_field_call_rate_serie.php?peca=" + peca + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&produto=<? echo $produto ?>","peca",'resizable=1,scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
function AbreSemPeca(produto,data_inicial,data_final,linha,estado,defeito_constatado,solucao){
	janela = window.open("relatorio_field_call_rate_sem_peca.php?data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&defeito_constatado=" + defeito_constatado + "&solucao=" + solucao + "&produto=<? echo $produto ?>","peca",'resizable=1,scrollbars=yes,width=750,height=450,top=315,left=20');
	janela.focus();
}

</script>
</HEAD>

<BODY>
<?

echo "<div align='center'><b>$title </b>";
echo "<span class='Conteudo'><br>De $aux_data_inicial até $aux_data_final</B>";
echo "PRODUTO: <b>$descricao_produto </b></span></div>";


$cond_1 = "1=1";
$cond_2 = "1=1";
$cond_3 = "1=1";
$cond_4 = "1=1";
#if (strlen ($familia) > 0) $cond_1 = " tbl_produto.familia = $familia ";
if (strlen ($estado)  > 0) $cond_2 = " tbl_posto.estado    = '$estado' ";
if (strlen ($posto)   > 0) $cond_3 = " tbl_posto.posto     = $posto ";
#if (strlen ($linha)   > 0) $cond_4 = " tbl_produto.linha   = $linha ";
if($login_fabrica == 20)$tipo_data = " tbl_extrato_extra.exportado ";
else                    $tipo_data = " tbl_extrato.data_geracao ";
$sql = "SELECT COUNT(*) AS qtde , fcr.com_sem
		FROM (
			SELECT tbl_os.os , 
				(
					SELECT status_os FROM tbl_os_status 
					WHERE tbl_os_status.os = tbl_os_extra.os 
					ORDER BY data DESC LIMIT 1
				) AS status ,
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
					AND $tipo_data BETWEEN '$data_inicial' AND '$data_final'
				) oss            ON oss.os = tbl_os_extra.os
				JOIN tbl_extrato USING (extrato)
				JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
				JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
				JOIN tbl_posto   ON tbl_os.posto    = tbl_posto.posto AND tbl_posto.pais='BR'
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   $tipo_data BETWEEN '$data_inicial' AND '$data_final' ";
if ($login_fabrica == 14) $sql .= " AND   tbl_extrato.liberado IS NOT NULL ";
$sql .= "AND   tbl_os.excluida IS NOT TRUE
				AND   tbl_os.produto = $produto
				AND   $cond_1
				AND   $cond_2
				AND   $cond_3
				AND   $cond_4
		) fcr
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		GROUP BY fcr.com_sem " ;

$res = pg_exec ($con,$sql);

$qtde_com = 0 ;
$qtde_sem = 0 ;
for($i = 0 ; $i < pg_numrows($res) ; $i++){
	if (pg_result ($res,$i,com_sem) == "COM") $qtde_com = pg_result ($res,$i,qtde);
	if (pg_result ($res,$i,com_sem) == "SEM") $qtde_sem = pg_result ($res,$i,qtde);
}


$total = $qtde_com + $qtde_sem;

$porc_com = ($qtde_com/$total) * 100;
$porc_com = round($porc_com,0);
$porc_sem = 100 - $porc_com;

echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'  >";

echo "<tr bgcolor='#F1F4FA' class='Conteudo'><td align='center'>OS SEM PEÇAS</td><td align='right'><b>$qtde_sem </td><td align='right'><b> $porc_sem%</td></tr>";
echo "<tr bgcolor='#F1F4FA' class='Conteudo'><td align='center'>OS COM PEÇAS</td><td align='right'><b>$qtde_com </td><td align='right'><b> $porc_com%</td></tr>";
echo "<tr bgcolor='#d9e2ef' class='Conteudo'><td align='center'>TOTAL</td><td align='right'><b>$total</td><td align='right'><b>100 %</td></tr>";
echo "</table><br>";


$sql = "SELECT SUM(tbl_os.mao_de_obra) AS total_mao_de_obra
	FROM tbl_os 
	JOIN tbl_os_extra      USING (os)
	JOIN tbl_extrato       USING (extrato)
	JOIN tbl_extrato_extra USING (extrato)
	WHERE tbl_extrato.fabrica = $login_fabrica
	AND   tbl_os.produto      = $produto 
	AND ";
	if($login_fabrica == 20)  $sql .=	" tbl_extrato_extra.exportado BETWEEN '$data_inicial' AND '$data_final' ";
	else                      $sql .=	" tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
	if ($login_fabrica == 14) $sql .= " AND   tbl_extrato.liberado IS NOT NULL ";
$sql .= " AND   tbl_os.excluida IS NOT TRUE ; ";
$res = pg_exec($con, $sql);


if(pg_numrows($res) > 0){
	$total_mao_de_obra = pg_result($res,0,total_mao_de_obra);
	
	echo  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='500'>";
	echo  "<TR class='Titulo' background='imagens_admin/azul.gif' height='25'>";
	echo  "<TD colspan='2'><b>CUSTO TOTAL COM O PRODUTO: $descricao_produto</b></TD>";
	echo "</tr>";
	echo "<tr bgcolor='#F1F4FA' class='Exibe'><td align='center'>TOTAL DE MÃO DE OBRA</td><td align='right'><b>R$ ".number_format($total_mao_de_obra,2,",",".")."</td></tr>";
}


//se mexer nesse gráfico favor fazer a mesma alteração no relatorio_field_call_rate_pecas_grafico.php
if($login_fabrica <> 20) $sql = "SELECT distinct tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca, pecas.qtde AS ocorrencia,pecas.pecas,pecas.custo_peca ";
else $sql = " SELECT distinct tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca, pecas.qtde AS ocorrencia, pecas.qtde_os ,pecas.pecas,pecas.custo_peca ";

$sql .= " FROM tbl_peca
		JOIN   ( ";
		if($login_fabrica == 20) $sql .= " SELECT tbl_os_item.peca, SUM(tbl_os_item.qtde) AS qtde, COUNT(tbl_os_produto.os) AS qtde_os ,sum(tbl_os_item.preco) as pecas,sum(tbl_os_item.custo_peca) as custo_peca ";
		else $sql .= "                     SELECT tbl_os_item.peca, COUNT(tbl_os_produto.os) AS qtde,sum(tbl_os_item.preco) as pecas,sum(tbl_os_item.custo_peca) as custo_peca ";
		$sql .= " FROM tbl_os_item 
				  JOIN tbl_os_produto USING (os_produto)
				  JOIN (
						SELECT tbl_os.os , (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
						FROM tbl_os_extra
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_extrato_extra USING (extrato)
						JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
						JOIN tbl_posto   ON tbl_os.posto    = tbl_posto.posto
						JOIN tbl_produto ON tbl_os.produto  = tbl_produto.produto
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND   $tipo_data BETWEEN '$data_inicial' AND '$data_final' and tbl_posto.pais='BR' AND ";
if($login_fabrica == 20)  $sql .=	" tbl_extrato_extra.exportado BETWEEN '$data_inicial' AND '$data_final' ";
else                      $sql .=	" tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
if ($login_fabrica == 14) $sql .= "AND   tbl_extrato.liberado IS NOT NULL ";
$sql .= "				
						AND   tbl_os.excluida IS NOT TRUE
						AND   tbl_os.produto = $produto
						AND   $cond_1
						AND   $cond_2
						AND   $cond_3
						AND   $cond_4
				) fcr ON tbl_os_produto.os = fcr.os
				WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
				GROUP BY tbl_os_item.peca
		) pecas ON tbl_peca.peca = pecas.peca
		ORDER BY pecas.qtde DESC " ;


//if ($ip == "201.42.109.216") { echo nl2br($sql);}

//exit;

$res = pg_exec($con, $sql);

if(pg_numrows($res) > 0){

	for ($x = 0; $x < pg_numrows($res); $x++) {
		$pecas      = pg_result($res,$x,pecas);
		$ocorrencia = @pg_result($res,$x,ocorrencia);

		if($login_fabrica==1)$pecas = pg_result($res,$x,custo_peca);
		$total_pecas     = $total_pecas + $pecas ;
		if($login_fabrica == 20) $total_qtde_peca = $total_qtde_peca + $ocorrencia;
	}
	
	$total_final = $total_pecas + $total_mao_de_obra;
	echo "<tr bgcolor='#F1F4FA' class='Exibe'><td align='center'>TOTAL DE CUSTO DE PEÇAS</td> <td align='right'><b>R$ ".number_format($total_pecas,2,",",".")."</td></tr>";
	echo "<tr bgcolor='#d9e2ef' class='Exibe'><td align='center'>TOTAL</td><td align='right'><b>R$ ".number_format($total_final,2,",",".")."</td></tr>";
	echo "</table><br>";

	echo  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='600'>";
	echo  "<TR class='Titulo' background='imagens_admin/azul.gif' height='25'>";
	echo  "<TD><b>Referência</b></TD>";
	echo  "<TD><b>Peça</b></TD>";
	echo  "<TD><b>Ocorrências</b></TD>";
	if($login_fabrica == 20) echo  "<TD><b>Qtde Lançadas</b></TD>";
	echo  "<TD><b>Custo</b></TD>";
	echo  "<TD><b>%</b></TD>";
	if($login_fabrica == 20) echo  "<TD><b>% peças</b></TD>";
	echo  "<TD><b>Série</b></TD>";
	echo  "</TR>";


	
	for($i=0; $i<pg_numrows($res); $i++){
		$peca       = pg_result($res,$i,peca);
		$referencia = pg_result($res,$i,referencia);
		$descricao  = pg_result($res,$i,descricao);
		$ocorrencia = pg_result($res,$i,ocorrencia);
		if($login_fabrica == 20) $qtde_os    = pg_result($res,$i,qtde_os);
		$pecas      = pg_result($res,$i,pecas);
		if($login_fabrica==1)$pecas = pg_result($res,$i,custo_peca);

		if ($total_pecas > 0) {
			$porcentagem = (($pecas * 100) / $total_pecas);
		}
		
		if($login_fabrica == 20 AND $ocorrencia > 0){
			$porcentagem_pecas = (($ocorrencia * 100) / $total_qtde_peca);
		}

		if($cor=="#F1F4FA")$cor = '#F7F5F0';
		else               $cor = '#F1F4FA';

		echo "<TR class='Conteudo' bgcolor='$cor'>";
		echo "	<TD><a href='javascript:AbreDefeito(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\")'>$referencia</a></TD>";
		echo "	<TD align='left'>$descricao</TD>";
		if($login_fabrica == 20) echo "	<TD align='center'>$qtde_os </TD>";
		echo "	<TD align='center'>$ocorrencia</TD>";
		echo "	<TD align='right' width='75'>R$ ". number_format($pecas,2,",",".") ."</TD>";
		echo "	<TD >". number_format($porcentagem,2,",",".") ."%</TD>";
		if($login_fabrica == 20) echo "	<TD >". number_format($porcentagem_pecas,2,",",".") ."%</TD>";
		echo "	<TD><a href='javascript:AbreSerie(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\")'>#série</a></TD>";
		echo "</TR>";
		$total = $total + $pecas;
	}
	echo "<tr class='Conteudo' bgcolor='#d9e2ef'>";
		echo "<td colspan='3'><font size='2'><b><CENTER>VALOR TOTAL DE CUSTO PEÇA </b></td>";
		echo "<td colspan='5'><font size='2' color='009900'><b>R$". number_format($total_pecas,2,",",".") ." </b></td>";
		echo "</tr>";
	echo "</table>";
}

?>


</BODY>
</HTML>
