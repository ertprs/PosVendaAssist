<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$sql = "SELECT * FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_exec($con,$sql);

if (@pg_numrows($res) > 0){
	$cod_posto  = pg_result($res,0,codigo_posto);
}else{
	header("Location: os_extrato.php");
	exit;
}


// conexao com banco da britania
#/////////////////////////////////////
$dbhost    = "192.168.0.3";
$dbbanco   = "postgres";
$dbport    = 5432;
$dbusuario = "britania";
$dbsenha   = "britania";
$dbnome    = "dbbritania";

if ($dbbanco == "postgres") {
	$parametros = "host=$dbhost dbname=$dbnome port=$dbport user=$dbusuario password=$dbsenha";
	if(!($con=pg_connect($parametros))) {
		echo "<p align=\"center\"><big><strong>Não foi possável
			estabelecer uma conexao com o banco de dados $dbnome.
			Favor contactar o Administrador.</strong></big></p>";
		exit;
	}
}
#/////////////////////////////////////


$erro       = 0;
$retornavel = 0;

$data = date("d/m/Y");

$total  = "total_posto  AS peca";
$tabela = "tabela_posto AS tabela";
$preco  = "preco_posto  AS preco";
$mobra  = "mobra_posto  AS mo";
$gmobra = "mobra_posto";

$title		= "Impressao do Produtos para a Nota de Devolução";

# Busca dados do Pedido na tabela TBPEDIDO #
$sql = "SET DateStyle TO 'SQL,EUROPEAN'";
$res = @pg_exec($con,$sql);

$sql = "SELECT * FROM vw_nota_devolucao WHERE cod_posto = '". $_GET["posto"] ."'  AND data_extrato = '". $_GET["extrato"] ."'";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	$nome_posto = pg_result($res,0,nome_posto);
}

echo "<html>";

echo "<head>";

echo "<title>$title</title>";

echo "<meta http-equiv='Pragma' content='no-cache'>";
echo "<meta http-equiv='Expires' content='0'>";

echo "<style type='text/css'>";
echo "<!--";
echo ".Fonte_Preta		{font-family: Arial, Verdana, Times, Sans; font-size: 10pt; text-decoration: none; color: #000000}";
echo ".Fonte_Azul		{font-family: Arial, Verdana, Times, Sans; font-size: 10pt; text-decoration: none; color: #0000FF}";
echo "-->";
echo "</style>";
echo "</head>";

echo "<body bgcolor='#FFFFFF' marginwidth='0' marginheight='0' topmargin='0' leftmargin='0'>";

echo "<br>";

if (1 == 2) {
$sql =	"SELECT DISTINCT
			    sua_os,
			    cod_posto,
			    data,
			    data_abertura,
			    data_fechamento,
			    data_extrato,
			    $mobra,
			    vr_recolhimento,
			    vr_deslocamento,
			    quilometragem,
			    lancamento,
			    finalizado,
			    peca,
			    $tabela,
			    $preco,
			    nome_posto,
			    nome_peca,
			    retornavel,
			    qtde
		FROM    vw_nota_devolucao
		WHERE   posto        = $posto
		AND     retornavel   = 'f'
		AND     data_extrato = (SELECT fnc_formata_data('$extrato'))";
$res = pg_exec($con,$sql);

$retornavel = 0;

if (pg_numrows($res) > 0) {
	$retornavel = 1;
	
	echo "<table align='center' border='1' cellpadding='0' cellspacing='0' width='95%'>";
	echo "<tr>";
	
	echo "<td align='center' width='100%'>";
	
	echo "<table align='center' border='0' cellpadding='0' cellspacing='0' width='100%'>";
	echo "<tr>";
	
	echo "<td align='center' width='20%' class='Fonte_Azul'><a href='britania_posicao_extrato.php'>Extrato</a><br><b>$extrato</b></td>";
	echo "<td align='left'   width='80%' class='Fonte_Preta'><span class='Fonte_Preta'><b>$nome_posto</b><br>RELAÇÃO DE PEÇAS PARA EMISSÃO DA NOTA DE DEVOLUÇÃO</span></td>";
	
	echo "</tr>";
	echo "</table>";
	
	echo "</td>";
	
	echo "</tr>";
	echo "<tr>";
	
	echo "<td align='center' width='100%'>";
	
	echo "<table align='center' border='1' cellpadding='2' cellspacing='2' width='100%'>";
	echo "<tr>";
	
	echo "<td bgcolor='#FFFFFF' align='center' width='10%'><span class='Fonte_Preta'><b>OS</b></span></td>";
	echo "<td bgcolor='#FFFFFF' align='center' width='15%'><span class='Fonte_Preta'><b>REFERÊNCIA</b></span></td>";
	echo "<td bgcolor='#FFFFFF' align='left' width='45%'><span class='Fonte_Preta'><b>PEÇA</b></span></td>";
	echo "<td bgcolor='#FFFFFF' align='center' width='10%'><span class='Fonte_Preta'><b>QTDE</b></span></td>";
	echo "<td bgcolor='#FFFFFF' align='center' width='10%'><span class='Fonte_Preta'><b>UN</b></span></td>";
	echo "<td bgcolor='#FFFFFF' align='center' width='10%'><span class='Fonte_Preta'><b>TOTAL</b></span></td>";
	
	echo "</tr>";
	
	$tot_valor = 0;
	
	for ($y=0;$y<pg_numrows($res);$y++) {
		$sua_os          = pg_result($res,$y,sua_os);
		$referencia      = pg_result($res,$y,peca);
		$nome_peca       = pg_result($res,$y,nome_peca);
		$qtde            = pg_result($res,$y,qtde);
		$valor           = pg_result($res,$y,preco);
		$tot_valor       = $tot_valor + ($valor * $qtde);
		
		echo "<tr>";
		
		echo "<td bgcolor='#FFFFFF' align='right' width='10%'><span class='Fonte_Preta'>$sua_os</span></td>";
		echo "<td bgcolor='#FFFFFF' align='center' width='15%'><span class='Fonte_Preta'>$referencia</span></td>";
		echo "<td bgcolor='#FFFFFF' align='left' width='45%'><span class='Fonte_Preta'>$nome_peca</span></td>";
		echo "<td bgcolor='#FFFFFF' align='right' width='10%'><span class='Fonte_Preta'>$qtde</span></td>";
		echo "<td bgcolor='#FFFFFF' align='right' width='10%'><span class='Fonte_Preta'>" . number_format($valor,2,",",".") . "</span></td>";
		echo "<td bgcolor='#FFFFFF' align='right' width='10%'><span class='Fonte_Preta'>" . number_format($valor * $qtde,2,",",".") . "</span></td>";
		
		echo "</tr>";
	}
	
	echo "<tr>";
	echo "<td bgcolor='#FFFFFF' align='center' width='50%' colspan='5'><span class='Fonte_Preta'><b>TOTAL PEÇAS</b></span></td>";
	echo "<td bgcolor='#FFFFFF' align='right' width='50%'><span class='Fonte_Preta'><b>" . number_format($tot_valor,2,",",".") . "</b></span></td>";
	echo "</tr>";
	
	echo "</table>";
	
	
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
}

################### PEÇAS RETORNÁVEIS ###################

$sql =	"SELECT DISTINCT
			    os,
			    sua_os,
			    posto,
			    data,
			    data_abertura,
			    data_fechamento,
			    data_extrato,
			    $mobra,
			    vr_recolhimento,
			    vr_deslocamento,
			    quilometragem,
			    lancamento,
			    finalizado,
			    peca,
			    $tabela,
			    $preco,
			    nome_posto,
			    nome_peca,
			    retornavel,
			    qtde
		FROM    view_nota_devolucao
		WHERE   posto        = $posto
		AND     data_extrato = (SELECT fnc_formata_data('$extrato'))";
$res = pg_exec($con,$sql);
#AND     (retornavel   = 't' OR nome_peca ilike '%(R)%')

if (@pg_numrows($res) > 0) {
	
	echo "<table align='center' border='1' cellpadding='0' cellspacing='0' width='95%'>";
	echo "<tr>";
	
	if ($retornavel == 0) {
		echo "<td align='center' width='100%'>";
		
		echo "<table align='center' border='0' cellpadding='0' cellspacing='0' width='100%'>";
		echo "<tr>";
		
		echo "<td align='center' width='20%' class='Fonte_Azul'><a href='britania_posicao_extrato.php'>Extrato</a><br><b>$extrato</b></td>";
		echo "<td align='left'   width='80%' class='Fonte_Preta'><span class='Fonte_Preta'><b>$nome_posto</b><br>RELAÇÃO DE PEÇAS PARA EMISSÃO DA NOTA DE DEVOLUÇÃO</span></td>";
		
		echo "</tr>";
		echo "</table>";
		
		echo "</td>";
		
		echo "</tr>";
		echo "<tr>";
	}
	
	echo "<td align='center' width='100%'>";
	
	echo "<table align='center' border='1' cellpadding='2' cellspacing='2' width='100%'>";
	echo "<tr>";
	
	echo "<td bgcolor='#FFFFFF' align='center' width='10%'><span class='Fonte_Preta'><b>OS</b></span></td>";
	echo "<td bgcolor='#FFFFFF' align='center' width='15%'><span class='Fonte_Preta'><b>REFERÊNCIA</b></span></td>";
	echo "<td bgcolor='#FFFFFF' align='left' width='45%'><span class='Fonte_Preta'><b>PEÇAS RETORNÁVEIS</b></span></td>";
	echo "<td bgcolor='#FFFFFF' align='center' width='10%'><span class='Fonte_Preta'><b>QTDE</b></span></td>";
	echo "<td bgcolor='#FFFFFF' align='center' width='10%'><span class='Fonte_Preta'><b>UN</b></span></td>";
	echo "<td bgcolor='#FFFFFF' align='center' width='10%'><span class='Fonte_Preta'><b>TOTAL</b></span></td>";
	
	echo "</tr>";
	
	$tot_valor = 0;
	
	for ($y=0;$y<pg_numrows($res);$y++) {
		$sua_os          = pg_result($res,$y,sua_os);
		$referencia      = pg_result($res,$y,peca);
		$nome_peca       = pg_result($res,$y,nome_peca);
		$qtde            = pg_result($res,$y,qtde);
		$valor           = pg_result($res,$y,preco);
		$tot_valor       = $tot_valor + ($valor * $qtde);
		
		echo "<tr>";
		
		echo "<td bgcolor='#FFFFFF' align='right' width='10%'><span class='Fonte_Preta'>$sua_os</span></td>";
		echo "<td bgcolor='#FFFFFF' align='center' width='15%'><span class='Fonte_Preta'>$referencia</span></td>";
		echo "<td bgcolor='#FFFFFF' align='left' width='45%'><span class='Fonte_Preta'>$nome_peca</span></td>";
		echo "<td bgcolor='#FFFFFF' align='right' width='10%'><span class='Fonte_Preta'>$qtde</span></td>";
		echo "<td bgcolor='#FFFFFF' align='right' width='10%'><span class='Fonte_Preta'>" . number_format($valor,2,",",".") . "</span></td>";
		echo "<td bgcolor='#FFFFFF' align='right' width='10%'><span class='Fonte_Preta'>" . number_format($valor * $qtde,2,",",".") . "</span></td>";
		
		echo "</tr>";
	}
	
	echo "<tr>";
	echo "<td bgcolor='#FFFFFF' align='center' width='50%' colspan='5'><span class='Fonte_Preta'><b>TOTAL PEÇAS RETORNÁVEIS</b></span></td>";
	echo "<td bgcolor='#FFFFFF' align='right' width='50%'><span class='Fonte_Preta'><b>" . number_format($tot_valor,2,",",".") . "</b></span></td>";
	echo "</tr>";
	
	echo "</table>";
	
	
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}

	echo "<br>";

################### MÃO DE OBRA ###################

$sql =	"SELECT DISTINCT
			    os,
			    sua_os,
			    $mobra
		FROM    view_nota_devolucao
		WHERE   posto        = '$posto'
		AND     data_extrato = (SELECT fnc_formata_data('$extrato'))
		UNION
		SELECT os,
			   sua_os,
			   $mobra
		FROM   tbl_os
		WHERE  tbl_os.finalizado = 't'
		AND NOT EXISTS
			   (
			   SELECT * FROM tbl_item_os WHERE tbl_item_os.os = tbl_os.os
			   )
		AND posto = $posto
		AND data_extrato = (SELECT fnc_formata_data('$extrato'))";
	
$sql = "SELECT
		DISTINCT
		os         ,
		sua_os     ,
		$mobra
FROM    tbl_os
WHERE   tbl_os.finalizado = 't'
AND     posto        = $posto
AND     data_extrato = (SELECT fnc_formata_data('$extrato'))
GROUP BY os    ,
		 sua_os,
		 $gmobra
ORDER BY sua_os";

$res = pg_exec($con,$sql);
//echo $sql;
if (@pg_numrows($res) > 0) {
	
	echo "<table align='center' border='1' cellpadding='0' cellspacing='0' width='95%'>";
	echo "<tr>";
	
	if ($retornavel == 0) {
		echo "<td align='center' width='100%'>";
		
		echo "<table align='center' border='0' cellpadding='0' cellspacing='0' width='100%'>";
		echo "<tr>";
		
		echo "<td align='center' width='20%' class='Fonte_Azul'><a href='britania_posicao_extrato.php'>Extrato</a><br><b>$extrato</b></td>";
		echo "<td align='left'   width='80%' class='Fonte_Preta'><span class='Fonte_Preta'><b>$nome_posto</b><br>RELAÇÃO DE PEÇAS PARA EMISSÃO DA NOTA DE DEVOLUÇÃO</span></td>";
		
		echo "</tr>";
		echo "</table>";
		
		echo "</td>";
		
		echo "</tr>";
		echo "<tr>";
	}
	
	echo "<td align='center' width='100%'>";
	
	echo "<table align='center' border='1' cellpadding='2' cellspacing='2' width='100%'>";
	echo "<tr>";
	
	echo "<td bgcolor='#FFFFFF' align='center' width='10%'><span class='Fonte_Preta'><b>OS</b></span></td>";
	echo "<td bgcolor='#FFFFFF' align='center' width='80%' colspan='4'>&nbsp;</td>";
	echo "<td bgcolor='#FFFFFF' align='right' width='10%'><span class='Fonte_Preta'><b>MO</b></span></td>";
	
	echo "</tr>";
	
	$tot_valor = 0;
	
	for ($y=0;$y<pg_numrows($res);$y++) {
		$sua_os          = pg_result($res,$y,sua_os);
		$mobra           = pg_result($res,$y,mo);
		$tot_valor       = $tot_valor + $mobra;
		
		echo "<tr>";
		
		echo "<td bgcolor='#FFFFFF' align='right' width='10%'><span class='Fonte_Preta'>$sua_os</span></td>";
		echo "<td bgcolor='#FFFFFF' align='center' width='80%' colspan='4'>&nbsp;</td>";
		echo "<td bgcolor='#FFFFFF' align='right' width='10%'><span class='Fonte_Preta'>". number_format($mobra,2,",",".") . "</span></td>";
		
		echo "</tr>";
	}
	
	echo "<tr>";
	echo "<td bgcolor='#FFFFFF' align='center' width='50%' colspan='5'><span class='Fonte_Preta'><b>TOTAL MÃO DE OBRA</b></span></td>";
	echo "<td bgcolor='#FFFFFF' align='right' width='50%'><span class='Fonte_Preta'><b>" . number_format($tot_valor,2,",",".") . "</b></span></td>";
	echo "</tr>";
	
	echo "</table>";
	
	
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}






echo "</body>";
echo "</html>";
?>
