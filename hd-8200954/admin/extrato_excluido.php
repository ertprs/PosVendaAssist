<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$msg_erro="";
$btn_acao=$_POST['btnacao'];
$mes=$_POST['mes'];
$ano=$_POST['ano'];
if((strlen($mes) ==0 or strlen($ano) ==0) and strlen($btn_acao) >0){
	$msg_erro="Por favor, selecione o mês e o ano para a consulta";
}

$admin_privilegios="financeiro";
include "autentica_admin.php";


$layout_menu = "financeiro";
$title = "Consulta e Manutenção de Extratos";

include "cabecalho.php";



?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10PX	;
	font-weight: bold;
	border: 1px solid;
;
	background-color: #D9E2EF
}

.resultado1 {
	background:#FFFFFF;
}

.resultado2 {
	background:#FFFFCC;
}

</style>
<?
if(strlen($msg_erro) >0){
echo "	<table border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffffff' width = '730'>";
echo "<tr>";
echo "<td valign='middle' align='center' class='error'>";
echo $msg_erro;
echo "</td>";
echo "</tr>";
echo "</table>";

}


echo "<FORM METHOD='POST' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
echo "<TABLE width='600' align='center' border='0' cellspacing='3' cellpadding='2'>\n";
echo "<input type='hidden' name='btnacao' value=''>";

echo "<TR class='menu_top'>\n";
echo "	<TD COLSPAN='4' ALIGN='center'>";
echo "		Consultar extratos gerados em";
echo "	</TD>";
echo "<TR>\n";

echo "<tr  align='left'>";
echo "	<td> * Mês</td>";
echo "	<td>";
echo "<select name='mes' size='1' class='frm'>
			<option value=''></option>";
		for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}

echo "</select>";
echo "	</td>";
echo "<td> * Ano</td>";
echo "<td>";
echo "<select name='ano' size='1' class='frm'>";
echo "<option value=''></option>";
			for ($i = 2006 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
echo "</select></td></tr>";
echo "</TABLE>\n";

echo "<br><img src=\"imagens_admin/btn_filtrar.gif\" onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() \" ALT=\"Filtrar extratos\" border='0' style=\"cursor:pointer;\">\n";

echo "</form>";
?>

<br>

<?

if(strlen($btn_acao) >0 and strlen($msg_erro) ==0 ){
	$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));

	$sql="SELECT codigo_posto,
				nome,
				to_char(data_geracao,'DD/MM/YYYY') AS data_geracao,
				qtde_os,
				total
		FROM tbl_extrato_excluido
		JOIN tbl_posto USING(posto)
		JOIN tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_extrato_excluido.fabrica
		WHERE tbl_posto_fabrica.fabrica=$login_fabrica
		AND data_geracao between '$data_inicial' and '$data_final' 
		ORDER BY data_geracao asc;";
	$res=pg_exec($con,$sql);

	if(pg_numrows($res) >0){
		
	echo "<table width='700' height=16 border='2' cellspacing='0' cellpadding='0' align='center'>";
	echo "<thead align='center'>Extratos Excluídos</thead>";
	echo "<tr class='menu_top'>";
	echo "<td align='center'>Código</td>";
	echo "<td align='center' nowrap>Nome do Posto</td>";
	echo "<td align='center'>Data</td>";
	echo "<td align='center' nowrap>Qtde. OS</td>";
	echo "<td align='center'>Total</td>";
				echo "</tr>";
		for($i=0;$i< pg_numrows($res);$i++){
			$codigo_posto = pg_result($res,$i,codigo_posto);
			$nome         = pg_result($res,$i,nome);
			$data_geracao = pg_result($res,$i,data_geracao);
			$qtde_os      = pg_result($res,$i,qtde_os);
			$total        = pg_result($res,$i,total);

			if ($i % 2 == 0) $cor = "resultado1";
			else             $cor = "resultado2";
			echo "<tr class='$cor'>";
			echo "<td align='center'>$codigo_posto</td>";
			echo "<td align='center' nowrap>$nome</td>";
			echo "<td align='center'>$data_geracao</td>";
			echo "<td align='center'>$qtde_os</td>";
			echo "<td align='right' nowrap>R$ $total</td>";
			echo "</tr>";
		}
	echo "</table>";
	}else{
		echo "Nenhum Resultado encontrado!";
	}
}

?>
<? include "rodape.php"; ?>
