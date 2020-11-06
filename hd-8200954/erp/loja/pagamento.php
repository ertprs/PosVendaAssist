<?
include 'dbconfig.php';
include 'dbconnect-inc.php';
include 'configuracao.php';
include '../../funcoes.php';

$cook_posto = $_COOKIE['cook_pessoa'];
if(strlen($cook_posto)==0)
	header("Location:identificacao.php");

$data_abertura = date("d/m/Y");

include "topo.php";
?>

<script language='javascript' src='../ajax.js'></script>


<script language='javascript'>
function verificar_pagamento(id) {

	if (id==''){
		return false;
	}
	url = "pagamento.php?ajax=sim&acao=parcelamento";

	var valor_total_tmp		= document.getElementById('valores_total_geral').innerHTML;

	parametros = "condicao="+id+"&valor_total="+valor_total_tmp;

	var com2      = document.getElementById('id_condicao_pagamento');
	com2.innerHTML = "&nbsp;&nbsp;Calculando...&nbsp;&nbsp;<br><img src='../../imagens/carregar2.gif' >";

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('POST',url,true);
	http_forn[curDateTime].setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http_forn[curDateTime].setRequestHeader("CharSet", "ISO-8859-1");
	http_forn[curDateTime].setRequestHeader("Content-length", url.length);
	http_forn[curDateTime].setRequestHeader("Connection", "close");
	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4){
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304){
//alert(http_forn[curDateTime].responseText);
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					com2.innerHTML = response[1];
				}else{
					com2.innerHTML = 'Erro ao mostrar condições de pagamento. Tente novamente';
				}
			}
		}
	}

	http_forn[curDateTime].send(parametros);
}
</script>

<?

echo "<table width='750' border='0' align='center' cellpadding='0' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
echo "<td width='182' valign='top'>";
include "menu.php";
echo "<BR>";?>
	<td>
<?

	//--===== FORMAS DE PAGAMENTO -=====================================================================
	echo "<table style=' border:#eceae6 2px solid; background-color: #f6f6f6' align='center' width='250' border='0'>";
	echo "<tr height='20' bgcolor='#f6f6f6'>";
	echo "<td rowspan='6' width='20px' valign='top'>&nbsp;</td>";
	echo "</tr>";
	echo "<tr height='140' >";

		echo "<td class='Label' width='100px'>Pagamento </td>";
		echo "<td class='Label'>";

		echo "<select class='Caixa' size='1' name='condicao' id='condicao' onchange='javascript:verificar_pagamento(this.value)'>\n";
		echo "<option value=''></option>\n";

		$sql = "SELECT condicao,descricao,parcelas,visivel
				FROM tbl_condicao
				WHERE fabrica=$login_empresa
				AND visivel IS TRUE
				ORDER BY condicao ASC";
		$res = pg_exec ($con,$sql) ;
		
		if (pg_numrows($res) > 0) {
			for ($k = 0; $k <pg_numrows($res); $k++) {
				$condicao      = trim(pg_result($res,$k,condicao));
				$descricao     = trim(pg_result($res,$k,descricao));
				$parcelas      = trim(pg_result($res,$k,parcelas));

				/*if ($condicao_pagamento==$condicao) {
					$select_cond = " SELECTED ";
					$dias_parcela = $parcelas;
				}*/

				$parcelas_array = explode("|",$parcelas);
				$parcelas_qtde  = count($parcelas_array);
				$parcelas       = str_replace("|"," / ",$parcelas);

				if ($parcelas_qtde==1 AND trim($parcelas==0)){
					$parcelas = "Á Vista";
				}
				echo "<option value='$condicao' $select_cond>$descricao - $parcelas</option>\n";
				$select_cond="";
			}
		}
		echo "</select>";

		if (strlen($parcelas)>0){
			$parcelas = explode("|",$parcelas);
			$valor_parcela=number_format($total_geralzao/count($parcelas),2,".","");
			$parc = array();
			for ($i=0;$i<count($parcelas);$i++){
				$data_tmp = date("d/m/Y",strtotime($data_abertura)+$parcelas[$i]*60*60*24);
				array_push($parc,$data_tmp);
			}
			for ($i=1;$i<=count($parc);$i++){
				$resposta .= $i."ª Parc. ".$parc[$i-1]." - R$ $valor_parcela<br>";
			}
		}
		echo "</tr>";
		echo "<tr>";
		echo "<td class='Label' colspan='2'><span id='id_condicao_pagamento'>$resposta</span></td>";
		echo "</tr>";
		echo "</table>";
	echo "</td>";
	echo "</tr>";

	echo "</table>";
?>
	</td>
</tr>
</table>