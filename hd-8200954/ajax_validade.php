<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$data_abertura = trim($_GET ['data_abertura']);
$data_nf = trim($_GET ['data_nf']);
$produto= trim($_GET ['produto']);

$sql = "SELECT tbl_peca.garantia_diferenciada from tbl_lista_basica join tbl_produto using(produto) join tbl_peca using(peca) where tbl_produto.referencia='$produto' order by tbl_peca.garantia_diferenciada asc LIMIT 1";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	$garantia_diferenciada = pg_result($res,0,garantia_diferenciada);
}else{
	echo "Selecione o produto corretamente!";
exit;
}

//echo $sql;
$data_abertura = explode("/",$data_abertura);
$data_abertura = $data_abertura[2]."-".$data_abertura[1]."-".$data_abertura[0];
if(!checkdate($data_abertura[1],$data_abertura[0],$data_abertura[2])) 
	$msg_erro .= "Data Abertura Inválida";


$data_nf = explode("/",$data_nf);
$data_nf = $data_nf[2]."-".$data_nf[1]."-".$data_nf[0];
if(!checkdate($data_nf[1],$data_nf[0],$data_nf[2])) 
	$msg_erro .= "Data NF Inválida";


if($login_fabrica==24 and strlen ($data_abertura) >0 AND strlen ($data_nf) >0 AND strlen($garantia_diferenciada) > 0 AND $garantia_diferenciada > 0 and strlen ($msg_erro)  == 0) {
	$sql = "SELECT ('$data_nf'::date + (('$garantia_diferenciada months')::interval))::date as dt_vencimento_garantia_peca,
				(('$data_nf'::date + (('$garantia_diferenciada months')::interval))::date < '$data_abertura')as venceu";
	$res = @pg_exec($con,$sql);
//echo $sql;
	$msg_erro .= pg_errormessage($con);
	if (strpos($msg_erro,"datestyle") !== false) {
		$msg_erro = "<table width='100' border='0' cellpadding='1' cellspacing='1' bgcolor='#ecc3c3'>
					<tr>
						<td valign='middle' align='center'>
						<font face='Arial, Helvetica, sans-serif' color='#d03838' size='1'>Data(s) Inválida(s)</font>
						</td>
					</tr>
					</table>";
	}
	if(strlen($msg_erro)>0){
		echo $msg_erro;
	}else{
		$dt_vencimento_garantia_peca = @pg_result($res,0,dt_vencimento_garantia_peca);
		$venceu  = @pg_result($res,0,venceu);
		if($venceu=='t'){
			echo "<table width='450' border='0' cellpadding='1' cellspacing='1' bgcolor='#ecc3c3'>";
			echo "<tr>";
			echo "<td valign='middle' align='center'>";
			echo "<font face='Arial, Helvetica, sans-serif' color='#d03838' size='1'><B>Atenção:</B> Produto comprado a mais de $garantia_diferenciada meses, algumas peças estão fora da garantia</font>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}else{
			//echo "Produto dentro da garantia de 6 meses";
		}
	}
}
?>
