<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "cabecalho.php";
?>
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.link{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}

</style>

<style type="text/css">
a.dica{
position:relative; 
font:10px arial, verdana, helvetica, sans-serif; 
padding:0;
color:#333399;
text-decoration:none;
cursor:help; 
z-index:24;
}

a.dica:hover{
background:transparent;
z-index:25; 
}

a.dica span{display: none}
a.dica:hover span{ 
display:block;
position:absolute;
width:180px; 
text-align:justify;
left:0;
font: 10px arial, verdana, helvetica, sans-serif; 
padding:5px 10px;
border:1px solid #000099;
background:#FFCC00; 
color:#330066;
}
</style>
<?

//PERIODO DE ATENDIMENTO DO DISTRIB
$atend_periodo_ini = $_GET['atend_periodo_ini'];
$atend_periodo_fim = $_GET['atend_periodo_fim'];

//PERIODO DE EMISSAO DE  NOTA DA BRITANIA
$nf_periodo_ini = $_GET['nf_periodo_ini'];
$nf_periodo_fim = $_GET['nf_periodo_fim'];

if (strlen($atend_periodo_ini) == 0) {
	$atend_periodo_ini= 5;
}
if (strlen($atend_periodo_fim) == 0) {
	$atend_periodo_fim= 1;
}

if (strlen($nf_periodo_ini) == 0) {
	$sql="select (current_date - interval'5 day')::date as data";
	$res = pg_exec ($con,$sql);
	$nf_periodo_ini = trim(pg_result($res,0,data));
}else{
	$nf_periodo_ini = substr ($nf_periodo_ini,6,4) . "-" . substr ($nf_periodo_ini,3,2) . "-" . substr ($nf_periodo_ini,0,2) ;
}
if (strlen($nf_periodo_fim) == 0) {
	$nf_periodo_fim = date('Y-m-d');
}else{
	$nf_periodo_fim = substr ($nf_periodo_fim,6,4) . "-" . substr ($nf_periodo_fim,3,2) . "-" . substr ($nf_periodo_fim,0,2) ;
}
/*
//ENCONTRAR TODOS OS FATURAMENTOS QUE FORAM ATENDIDOS PELO DISTRIB NO PERIODO ESPECIFICADO PELO USUARIO
$sql = "select tbl_faturamento_item.os,
			tbl_faturamento_item.faturamento_item,
			tbl_faturamento_item.peca
		from tbl_faturamento 
		join tbl_faturamento_item using(faturamento) 
		where tbl_faturamento.fabrica=3 
			and tbl_faturamento.distribuidor = 4311 
			and tbl_faturamento_item.os is not null 
			and tbl_faturamento.emissao < current_date
			and tbl_faturamento.emissao > (current_date - interval'9999 day')::date";
//echo "sql: $sql";
$res_atend = pg_exec ($con,$sql);
$array_faturamento="";
if (pg_numrows($res_atend) > 0) {
	for($i=0; $i < pg_numrows($res_atend); $i++){
		$peca	= trim(pg_result($res_atend,$i,peca));
		$os		= trim(pg_result($res_atend,$i,os));
		$faturamento_item= trim(pg_result($res_atend,$i,faturamento_item));
		if(strlen($array_faturamento[$os][$peca])==0)
			$array_faturamento[$os][$peca]= true;
			//echo "<br><font color='red'>Atenção: OS: $os apresenta mais de uma peca: $peca </font>";
	}
}*/

//ENCONTRA TODOS OS FATURAMENTOS QUE FORAM ATENDIDOS PELA BRITANIA NO PERIODO ESPECIFICADO PELO USUARIO


$sql = "SELECT tbl_faturamento_item.os,
			tbl_faturamento_item.peca,
			tbl_peca.referencia,
			tbl_faturamento_item.faturamento_item,
			tbl_faturamento_item.faturamento,
			TO_CHAR(emissao,'DD/MM/YYYY') AS dt_emissao,
			tbl_faturamento.posto,
			tbl_faturamento.nota_fiscal,
			sua_os,			
			descricao
		FROM tbl_faturamento 
		join tbl_faturamento_item using(faturamento)
		JOIN tbl_os on (tbl_faturamento_item.os = tbl_os.os)
		JOIN tbl_peca ON tbl_peca.peca     = tbl_faturamento_item.peca
		WHERE tbl_faturamento.fabrica=3 
			AND tbl_faturamento.posto = 4311 
			AND tbl_faturamento_item.os is not null 
			AND tbl_faturamento.emissao < '$nf_periodo_fim' 
			AND tbl_faturamento.emissao > '$nf_periodo_ini'
			ORDER BY emissao ";

/*			AND tbl_faturamento.emissao < (CURRENT_DATE - INTERVAL'$nf_periodo_fim day')::DATE 
			AND tbl_faturamento.emissao > (CURRENT_DATE - INTERVAL'$nf_periodo_ini day')::DATE ";
*/
$res = pg_exec ($con,$sql);

$nf_periodo_ini= substr ($nf_periodo_ini,8,2) . "/" . substr ($nf_periodo_ini,5,2) . "/" . substr ($nf_periodo_ini,0,4) ;
$nf_periodo_fim= substr ($nf_periodo_fim,8,2) . "/" . substr ($nf_periodo_fim,5,2) . "/" . substr ($nf_periodo_fim,0,4) ;
echo "<BR>";
echo "LISTA DE OS's NÃO ATENDIDAS PELO DISTRIB.";
echo "<BR>";
echo "<BR>";
echo "<table width='650' border='1' cellspacing='1' cellpadding='3' align='center'>\n";
echo "<form name='frm_per' method='get' action='$PHP_SELF'>";
echo "<tr><td colspan='10'>\n";
/*echo "Período de Atendimento:<input type='text' name='atend_periodo_ini' id='atend_periodo_ini' size='10' maxlength='11' value='$atend_periodo_ini'>dias\n";	
echo " a <input type='text' name='atend_periodo_fim' id='atend_periodo_fim' size='10' maxlength='10' value='$atend_periodo_fim'> dias\n";	
echo "</td></tr>\n";
echo "<tr><td colspan='10'>\n";*/
echo "Notas de Saída da Britania no período de <input type='text' name='nf_periodo_ini' id='nf_periodo_ini' size='12' maxlength='11' value='$nf_periodo_ini'>\n";	
echo " <input type='text' name='nf_periodo_fim' id='nf_periodo_fim' size='12' maxlength='10' value='$nf_periodo_fim'>\n";	
echo "<INPUT TYPE='submit' name='bt_per' id='bt_per' value='Pesquisar'>";
echo "</td></tr>\n";
echo "</form>\n";

echo "<tr>\n";
echo "<td class='menu_top' width='20'>#</td>\n";
echo "<td class='menu_top'>PEÇA</td>\n";
echo "<td class='menu_top'>DESCRIÇÃO</td>\n";
echo "<td class='menu_top'>OS</td>\n";
echo "<td class='menu_top'>NOTA FISCAL</td>\n";
echo "<td class='menu_top'>DATA <br> EMISSÃO</td>\n";
echo "<td class='menu_top'>DATA <br> CONFERENCIA</td>\n";
echo "</tr>\n";
		
if (pg_numrows($res) > 0) {

	$c=0;	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

		
		$peca		= trim(pg_result($res,$i,peca)) ;
		//$fat_item= trim(pg_result($res,$i,faturamento_item));
		$os= trim(pg_result($res,$i,os));
/*		$descricao	= trim(pg_result($res,$i,descricao)) ;
		$faturamento= trim(pg_result($res,$i,faturamento)) ;
		$sua_os		= trim(pg_result($res,$i,sua_os)) ;
		$posto		= trim(pg_result($res,$i,posto)) ;
		$nota_fiscal= trim(pg_result($res,$i,nota_fiscal)) ;
		$emissao	= trim(pg_result($res,$i,dt_emissao)) ;
*/
		$referencia= trim(pg_result($res,$i,referencia));
		$fat_item= trim(pg_result($res,$i,faturamento_item));
		$descricao	= trim(pg_result($res,$i,descricao)) ;
		$faturamento= trim(pg_result($res,$i,faturamento)) ;
		$sua_os		= trim(pg_result($res,$i,sua_os)) ;
		$posto		= trim(pg_result($res,$i,posto)) ;
		$nota_fiscal= trim(pg_result($res,$i,nota_fiscal)) ;
		$emissao	= trim(pg_result($res,$i,dt_emissao)) ;

		$sql = "select posto, os, nota_fiscal, emissao, tbl_faturamento_item.peca
		from tbl_faturamento
		join tbl_faturamento_item using(faturamento)
		where fabrica = 3 and distribuidor = 4311 and os = $os and tbl_faturamento_item.peca = $peca";
		$res2 = pg_exec ($con,$sql);


	


		if (pg_numrows($res2) > 0){

		/*	echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
			echo "<td align='left' nowrap>" . ($c+1) . "&nbsp;&nbsp;&nbsp;</td>\n";
			echo "<td align='left' nowrap>$faturamento</td>\n";
			echo "<td align='left' nowrap>$peca</td>\n";
			echo "<td nowrap align='center'>$descricao</td>\n";
			echo "<td align='left' nowrap><a href='os_press.php?os=$os' target='_blank' class='link'>$os</a></td>\n";
			echo "<td align='center' nowrap>$sua_os</font></td>\n";
			echo "<td align='center'>$nota_fiscal</td>\n";
			echo "<td align='center'>$emissao</td>\n";
			echo "<td nowrap align='center'><font color='red'>ENCONTRADO</font></td>\n";
			echo "</tr>\n";
*/
			//$array_faturamento[$os][$peca]="imprimiu";
		}else{
			$cor = "#ffffff";
			if ($c % 2 == 0) $cor = "#DDDDEE";

			echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
			echo "<td align='center' nowrap>" . ($c+1) . "&nbsp;</td>\n";
			echo "<td align='left' nowrap>$referencia</td>\n";
			echo "<td nowrap align='left'>$descricao</td>\n";
			echo "<td align='left' nowrap><a href='os_press.php?os=$os' target='_blank' class='link'>$sua_os</a></td>\n";
			echo "<td align='center'>$nota_fiscal</td>\n";
			echo "<td align='center'>$emissao</td>\n";
			echo "<td align='center'>$emissao</td>\n";
			echo "</tr>\n";
			$c++;
		}
	}
	echo "</table>\n";
}else{
	echo "NADA ENCONTRADO NO PERÍODO DE $nf_periodo_ini A $nf_periodo_fim";
}
?>
<p>
<? include "rodape.php"; ?>