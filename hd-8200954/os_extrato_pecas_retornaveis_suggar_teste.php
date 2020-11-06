<?
//arquivo alterado por takashi em 17/08/2006. Aparecia com a somatoria errada. Qdo agrupava por peça o valor total ficava diferente e o valor das pecas tambem. Arquivo anterior renomeado para os_extrato_pecas_retornaveis_ant.php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$extrato = trim($_GET['extrato']);
if (strlen($extrato) == 0) $extrato = trim($_POST['extrato']);

$servico_realizado = trim($_GET['servico_realizado']);
if (strlen($servico_realizado) == 0) $servico_realizado = trim($_POST['servico_realizado']);

if(strlen($extrato) == 0){
	header("Location: os_extrato.php");
	exit;
}
if($login_fabrica==6){
	header("Location: os_extrato_pecas_retornaveis_tectoy.php?extrato=$extrato");
	exit;
}

$msg_erro = "";

$layout_menu = "os";
$title = "Relação de Peças Retornáveis ";
	if ($login_fabrica == 3) { 
		$title .= "/ do Estoque ";
	}
$title .= "no Extrato";

if (strlen($_GET["agrupar"]) > 0) {
	$agrupar = trim($_GET["agrupar"]);
}else{
	$agrupar = "false";
}

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<p>
<?
if(strlen($msg_erro) > 0){
	echo "<TABLE width=\"650\" align='center' border=0>";
	echo "<TR>";
	
	echo "<TD align='center' class='error'>$msg_erro</TD>";
	
	echo "</TR>";
	echo "</TABLE>";
}

if ($login_fabrica == 11) {
	echo "<BR><BR>PROGRAMA DESATIVADO TEMPORARIAMENTE";
	exit;
}

if ($login_fabrica == 2){
	echo "<table width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td colspan=7 align='center'><font color='#FF0000'<b>AGUARDAR UM BOM VOLUME DE PEÇAS COM DEFEITOS PARA O ENVIO DAS MESMAS, ENTRAR EM CONTATO POR EMAIL PARA VERIFICAR O MEIO DE TRANSPORTE A SER ENVIADO.</b></font></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}

echo "<TABLE width=\"650\" align='center' border=0>";
echo "<TR class='menu_top'>\n";

echo "<TD align='center'><a href='$PHP_SELF?agrupar=true&extrato=$extrato'><font color='#000000'>Agrupar por peça</font></a></TD>\n";
echo "<TD align='center'><a href='$PHP_SELF?agrupar=false&extrato=$extrato'><font color='#000000'>Não agrupar</font></a></TD>\n";

echo "</TR>";
echo "</TABLE>";

echo "<p>";

if ($agrupar == "false" or strlen($agrupar)==0) {

	$sql = "SELECT tbl_os.os                                 ,
					tbl_os.sua_os                            ,
					tbl_os.consumidor_nome                   ,
					tbl_peca.referencia as peca_referencia   , 
					tbl_peca.descricao     as peca_nome      , 
					tbl_os_item.qtde                         , 
					tbl_os_item.preco                        ,
					tbl_os_item.custo_peca                   ,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
			FROM tbl_os
			JOIN tbl_os_extra using(os)
			JOIN tbl_os_produto using(os)
			JOIN tbl_os_item using(os_produto)
			JOIN tbl_peca using(peca)
			JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato 
			JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado 
			WHERE tbl_os_extra.extrato = $extrato 
			AND tbl_extrato.fabrica = $login_fabrica
			AND tbl_peca.devolucao_obrigatoria IS TRUE 
			AND tbl_servico_realizado.troca_de_peca IS TRUE
			AND tbl_servico_realizado.gera_pedido IS TRUE";

	$res = pg_exec ($con,$sql);
	$totalRegistros = pg_numrows($res);
//echo $sql;
	echo "<TABLE width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	
	if ($totalRegistros > 0){
		echo "<TR class='menu_top'>\n";
		
		echo "<TD colspan='4' align = 'center'>";
		echo "EXTRATO $extrato GERADO EM " . pg_result ($res,0,data_geracao) ;
		echo "</TD>";
		
		echo "</TR>\n";
		

		echo "<TR class='menu_top'>\n";
		
			echo "<TD align='center' >OS</TD>\n";
			echo "<TD align='center' >CLIENTE</TD>\n";

		
		echo "<TD align='center' >PEÇA</TD>\n";
		echo "<TD align='center' >QTDE</TD>\n";
		
		echo "</TR>\n";
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
			$os					= trim(pg_result ($res,$i,os));
			$sua_os				= trim(pg_result ($res,$i,sua_os));
			$consumidor			= trim(pg_result ($res,$i,consumidor_nome));
			//$produto_nome		= trim(pg_result ($res,$i,produto_nome));
			//$produto_referencia	= trim(pg_result ($res,$i,produto_referencia));
			$peca_referencia	= trim(pg_result ($res,$i,peca_referencia));
			$peca_nome			= trim(pg_result ($res,$i,peca_nome));
			$preco				= trim(pg_result ($res,$i,preco));
			$qtde				= trim(pg_result ($res,$i,qtde));
			$preco				= number_format($preco,2,",",".");
			
			$cor = "#d9e2ef";
			$btn = 'amarelo';
			
			if ($i % 2 == 0){
				$cor = '#F1F4FA';
				$btn = 'azul';
			}
			
			if (strstr($matriz, ";" . $i . ";")) {
				$cor = '#E49494';
			}
			
			if (strlen ($sua_os) == 0) $sua_os = $os;
			
			echo "<TR class='table_line' style='background-color: $cor;'>\n";
			
			if ($agrupar == "false") {
				echo "<TD align='center' nowrap><a href='os_press.php?os=$os' target='_blank'><font color='#000000'>$sua_os</font></a></TD>\n";
				echo "<TD align='left' nowrap>$consumidor</TD>\n";
			}
			
			echo "<TD align='left' nowrap>$peca_referencia - $peca_nome</TD>\n";
			echo "<TD align='center' nowrap>$qtde</TD>\n";
			echo "</TR>\n";
		}
	}
	
	echo "</TABLE>\n";
	
	echo "<br>";
	
}else{
	$sql = "SELECT	tbl_peca.referencia as peca_referencia   , 
					tbl_peca.descricao     as peca_nome      , 
					sum(tbl_os_item.qtde) as qtde            , 
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
			FROM tbl_os
			JOIN tbl_os_extra using(os)
			JOIN tbl_os_produto using(os)
			JOIN tbl_os_item using(os_produto)
			JOIN tbl_peca using(peca)
			JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato 
			JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado 
			WHERE tbl_os_extra.extrato = $extrato 
			AND tbl_extrato.fabrica = $login_fabrica
			AND tbl_peca.devolucao_obrigatoria IS TRUE 
			AND tbl_servico_realizado.troca_de_peca IS TRUE
			AND tbl_servico_realizado.gera_pedido IS TRUE
			GROUP BY tbl_peca.referencia, tbl_peca.descricao, tbl_extrato.data_geracao";
	$res = pg_exec ($con,$sql);
	$totalRegistros = pg_numrows($res);
//echo $sql;
	echo "<TABLE width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	
	if ($totalRegistros > 0){
		echo "<TR class='menu_top'>\n";
		
		echo "<TD colspan='4' align = 'center'>";
		echo "EXTRATO $extrato GERADO EM " . pg_result ($res,0,data_geracao) ;
		echo "</TD>";
		
		echo "</TR>\n";
		

		echo "<TR class='menu_top'>\n";
		
		echo "<TD align='center' >PEÇA</TD>\n";
		echo "<TD align='center' >QTDE</TD>\n";
		
		echo "</TR>\n";
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
			
			$peca_referencia	= trim(pg_result ($res,$i,peca_referencia));
			$peca_nome			= trim(pg_result ($res,$i,peca_nome));
			$qtde				= trim(pg_result ($res,$i,qtde));
			$qtde_total = $qtde_total + $qtde;
			
			$cor = "#d9e2ef";
			$btn = 'amarelo';
			
			if ($i % 2 == 0){
				$cor = '#F1F4FA';
				$btn = 'azul';
			}
			
			if (strstr($matriz, ";" . $i . ";")) {
				$cor = '#E49494';
			}
			
			if (strlen ($sua_os) == 0) $sua_os = $os;
			
			echo "<TR class='table_line' style='background-color: $cor;'>\n";
					
			echo "<TD align='left' nowrap>$peca_referencia - $peca_nome</TD>\n";
			echo "<TD align='center' nowrap>$qtde</TD>\n";
			echo "</TR>\n";
		}
			echo "<TR class='table_line' style='background-color: $cor;'>\n";
				echo "<TD align='center' nowrap>Total de peças</TD>\n";	
			echo "<TD align='center' nowrap>$qtde_total</TD>\n";
			echo "</TR>\n";
	}
	
	echo "</TABLE>\n";
	
	echo "<br>";
	

}
?>


<TABLE align='center'>
<TR>
	<TD>
		<br>
		<img src="imagens/btn_voltar.gif" onclick="javascript: window.location='os_extrato.php';" ALT="Voltar" border='0' style="cursor:pointer;">
	</TD>
</TR>
</TABLE>

<p>
<p>

<? include "rodape.php"; ?>