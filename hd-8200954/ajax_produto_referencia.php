<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

//RECEBE PARaMETRO
// $produto_referencia = $_POST["produto_referencia"];
$produto_referencia = $_GET["produto_referencia"]; 
$fabrica = $_GET["fabrica"]; 


//print urlencode("referência <b>".$produto_referencia."</b><br><br>");

$sql = "SELECT tbl_produto.referencia 
		FROM tbl_produto 
		JOIN tbl_familia using(familia) 
		WHERE tbl_familia.fabrica=$fabrica 
		AND tbl_produto.referencia like '".$produto_referencia."%' 
		LIMIT 1";
//echo "$sql";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		$referencia = pg_result($res,$x,referencia);
		print urlencode("<a href=\"javascript: if (document.frm_locacao.btn_acao.value == '') { document.frm_os.referencia.value = '$referencia';}\" >".$referencia."</a><br>");
		//print urlencode("<a href=\"javascript: referencia.value='$referencia'; qtde.focus(); this.close();\"><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>$referencia</font></a><BR>");

	}
}
?>
