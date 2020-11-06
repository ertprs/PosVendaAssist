<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
//include 'autentica_admin.php';


$digito = $_GET["typing"];

$sql= "SELECT posto, nome, cidade FROM tbl_posto where nome ilike '%".$digito."%'ORDER BY nome limit 10;";

$res = pg_exec ($con,$sql);


$resposta = "";
if(pg_numrows($res)>0) {

	$resposta .= "<table border='1' bordercolor='#aaaadd' cellpadding='1' cellspacing='1' >";
	$resposta .= "<tr>";
	$resposta .= "<td align='center' style='font-size:12px;' >";
	$resposta .= "Fornecedor";
	$resposta .= "</td>";
	$resposta .= "<td  align='center' style='font-size: 12px; '>";
	$resposta .= "Cidade";
	$resposta .= "</td>";
	$resposta .= "</tr>";
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
		$posto	= trim(pg_result($res,$i,posto));	

		$nome	= trim(pg_result($res,$i,nome));	
		$cidade	= trim(pg_result($res,$i,cidade));	


echo "<div style=' filter: alpha(opacity=90); opacity: .90;' onselect=\"this.text.value = '$nome-$posto';$('fornID').value = '$posto'\">";
echo "<span width='100' class='informal' style='font-size:10px'></span>";
echo "<table width='300'>";
echo "<tr>";
echo "<td nowrap width='180'>$nome";
echo "</td>";
echo "<td nowrap width='120'>$cidade";
echo "</td>";
echo "</tr>";
echo "</table>";
	


echo "</div>";

    }



/*	
		$resposta .= "<tr>";
		$resposta .= "<td nowrap align='center'>$emissao - $total_nota";
//		$resposta .= "<a href=\"javascript:transportar('$posto', '$nome');\" style='font-size: 9px;'>$nome</a>";
		$resposta .= "</td>";
		$resposta .= "<td nowrap align='center'>fabrica: $fabrica - faturamento: $faturamento";
//		$resposta .= "<a href=\"javascript:transportar('$posto', '$nome');\" style='font-size: 9px;'>$cidade</a>";
		$resposta .= "</td>";
		$resposta .= "</tr>";
*/
		if($i==9){
			$cont=pg_numrows($res);
			$resposta .= "<tr align='center' style='font-size: 10px; '>";
			$resposta .= "<td colspan='2'>";
			$resposta .= "...-$ajax- </td>";
			$resposta .= "</tr>";
		}
	//}
	//echo "<a href=\"javascript: opener.document.location = retorno + '?fornecedor=$fornecedor'; this.close() ;\" > " ;
	$resposta .= "</table>";
//	echo ($resposta);
	//print urlencode($resposta);
//}else{
		$resposta .= "<table>";
		$resposta .= "<tr>";
		$resposta .= "<td align=><font size='3'>";
		$resposta .= "Não encontrado!";
		$resposta .= "</font></td>";
		$resposta .= "<td><font size='3'>";
		$resposta .= "";
		$resposta .= "</font></td>";
		$resposta .= "</tr>";
		$resposta .= "</table>";
//		echo ($resposta);
		//print urlencode($resposta);
}
