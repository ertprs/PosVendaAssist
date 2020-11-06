<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "gerencia";
$title = "Fechamento de Extrato";

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim(strtolower($_POST["btnacao"]));
}

if ($btnacao == "aprovar") {

	$flag = 'false';

	for($i=0; $i < $_POST['total']; $i++){

		if($extrato[$i]){

			$valor_acrescimo = "null";
			$valor_desconto  = "null";
			$valor_obs       = "null";
			$obrigaObs       = "";

			if($acrescimo[$i]){
				$sql	= "SELECT fnc_limpa_moeda('$acrescimo[$i]')";
				$res	= pg_exec ($con,$sql);
				if (pg_result($res,0,0) == 0){
					$valor_acrescimo = "null";
					$msg_erro .= "Valor de <b>acréscimo</b> inválido.<br>";
					$matriz = $matriz . ";" . $i . ";";
//					break;
				}else{
					$valor_acrescimo = $acrescimo[$i];
					$obrigaObs = "true";
				}
			}

			if($desconto[$i]){
				$sql	= "SELECT fnc_limpa_moeda('$desconto[$i]')";
				$res	= pg_exec ($con,$sql);
				if (pg_result($res,0,0) == 0){
					$valor_desconto = "null";
					$msg_erro .= "Valor de <b>desconto</b> inválido.<br>";
					$matriz = $matriz . ";" . $i . ";";
//					break;
				}else{
					$valor_desconto = $desconto[$i];
					$obrigaObs = "true";
				}
			}

			if( (strlen($obs[$i]) == 0) && ($obrigaObs == "true") ){

				$msg_erro .= "<b>Observação</b> é obrigatória para dar <b>acréscimo</b> ou <b>desconto</b>.<br>";
				$matriz = $matriz . ";" . $i . ";";

			}elseif((strlen($obs[$i]) > 0) && ($obrigaObs == "")){

				//$msg_erro .= "<b>Observação</b> deve ser digitada somente quando <b>acréscimo</b> ou <b>desconto</b> forem preenchidos.<br>";
				//$matriz = $matriz . ";" . $i . ";";

			}else{

				if(strlen($obs[$i]) == 0){
					$valor_obs       = "null";
				}else{
					$valor_obs       = "'".$obs[$i]."'";
				}

			}

			if (($forma[$i] == 2) && (strlen($obs[$i]) == 0)){
				$msg_erro .= "<b>Observação</b> é obrigatória quando fechamento é <b>rejeitado</b>.<br>";
				$matriz = $matriz . ";" . $i . ";";

			}

			if(strlen($msg_erro) == 0){
				$sql = "UPDATE tbl_extrato_extra SET ";

				if($forma[$i] == 1){
					$sql .="acrescimo = $valor_acrescimo ,
							desconto  = $valor_desconto  ,
							obs       = $valor_obs       ,
							aprovar   = current_timestamp,
							rejeitar  = null           ,
							acumular  = null ";
				}elseif($forma[$i] == 2){
					$sql .="acrescimo = null           ,
							desconto  = null           ,
							obs       = $valor_obs       ,
							aprovar   = null           ,
							rejeitar  = current_timestamp,
							acumular  = null ";
				}else{
					$sql .="acrescimo = $valor_acrescimo ,
							desconto  = $valor_desconto  ,
							obs       = $valor_obs       ,
							aprovar   = null           ,
							rejeitar  = null           ,
							acumular  = current_timestamp ";
				}
				$sql .= "WHERE  extrato = '$extrato[$i]'";

				$res = pg_exec ($con,$sql);
			}

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
				break;
			}
		
		}

	}


	if(strlen($msg_erro) == 0){
		header("Location:extrato_detalhe.php");
		exit;
	}

}

if ($btnacao == "confirmar") {

	$soma = 0;

	for($i=0; $i < $_POST['total']; $i++){

		if($extrato[$i]){

			$valor_acrescimo = "null";
			$valor_desconto  = "null";
			$valor_obs       = "null";
			$obrigaObs       = "";

			if($acrescimo[$i]){
				$sql	= "SELECT fnc_limpa_moeda('$acrescimo[$i]')";
				$res	= pg_exec ($con,$sql);
				if (pg_result($res,0,0) == 0){
					$valor_acrescimo = "null";
					$msg_erro .= "Valor de <b>acréscimo</b> inválido.<br>";
					$matriz = $matriz . ";" . $i . ";";
//					break;
				}else{
					$valor_acrescimo = $acrescimo[$i];
					$obrigaObs = "true";
				}
			}

			if($desconto[$i]){
				$sql	= "SELECT fnc_limpa_moeda('$desconto[$i]')";
				$res	= pg_exec ($con,$sql);
				if (pg_result($res,0,0) == 0){
					$valor_desconto = "null";
					$msg_erro .= "Valor de <b>desconto</b> inválido.<br>";
					$matriz = $matriz . ";" . $i . ";";
//					break;
				}else{
					$valor_desconto = $desconto[$i];
					$obrigaObs = "true";
				}
			}

			if( (strlen($obs[$i]) == 0) && ($obrigaObs == "true") ){

				$msg_erro .= "<b>Observação</b> é obrigatória para dar <b>acréscimo</b> ou <b>desconto</b>.<br>";
				$matriz = $matriz . ";" . $i . ";";

			}elseif((strlen($obs[$i]) > 0) && ($obrigaObs == "")){

				$msg_erro .= "<b>Observação</b> deve ser digitada somente quando <b>acréscimo</b> ou <b>desconto</b> forem preenchidos.<br>";
				$matriz = $matriz . ";" . $i . ";";

			}else{

				if(strlen($obs[$i]) == 0){
					$valor_obs       = "null";
				}else{
					$valor_obs       = "'".$obs[$i]."'";
				}

			}

			if(strlen($msg_erro) == 0){
				$sql = "UPDATE tbl_extrato_extra SET 
						acrescimo = $valor_acrescimo, 
						desconto  = $valor_desconto , 
						obs       = $valor_obs
						WHERE  extrato = '$extrato[$i]'";

				$res = pg_exec ($con,$sql);
			}
		}
	}

	if(strlen($msg_erro) == 0){
		header("Location:extrato_detalhe.php");
		exit;
	}
}

include "cabecalho.php";

?>

<script>
var ok = true;
function checkaTodos() {
	f = document.frm_extrato;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
			}
		}
	}
}
</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
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

		echo "<TABLE width=\"700\" align='center' border=0>";
		echo "	<TR>";
		echo "		<TD align='center'>$msg_erro</TD>";
		echo "	</TR>";
		echo "	</TABLE>";

	}

echo "<FORM METHOD=POST NAME=frm_extrato ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='posto' value='$posto'>";

/*
echo "<TABLE width=\"700\" height=\"18\" align='center'>";
echo "	<TR>";
echo "		<TD background='imagens_admin/barrabg_titulo.gif' style='color: #596d9b;'><b>Para ver detalhes de qualquer uma das OS clique em seu respectivo número. <br> Mantenha o cursor sobre o número da OS para ver datas de abertura e fechamento.<br>Caso faça alguma alteração nos valores é obrigatório o preenchimento do campo de Observação. <br>Não se esqueça de clicar em gravar no final da tabela.<b><br></TD>";
echo "	</TR>";
echo "</TABLE>";
*/

// SQL
$sql = "SELECT		nome        ,
					cnpj        ,
					posto       ,
					codigo_posto,
					total       ,
					extrato     ,
					fabrica     ,
					acrescimo   ,
					desconto    ,
					obs         
		FROM		vw_fechamento_extrato
		WHERE		fabrica = $login_fabrica
		ORDER BY	extrato";
$res = pg_exec ($con,$sql);

$totalRegistros = pg_numrows($res);

if ($totalRegistros > 0) {

	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	echo "	<TR class='menu_top'>\n";
	echo "		<TD align='center' width='30'><img src=\"imagens_admin/selecione_todas.gif\" border=0 onclick=\"javascript:checkaTodos()\" ALT=\"Selecionar todas\" style=\"cursor:pointer;\"></TD>\n";
	echo "		<TD align='center' >POSTO</TD>\n";
	echo "		<TD align='center' width='80'>TOTAL</TD>\n";
	echo "		<TD align='center' width='50'>ACRÉSCIMO</TD>\n";
	echo "		<TD align='center' width='50'>DESCONTO</TD>\n";
	echo "		<TD align='center' width='160'>OBS</TD>\n";
	echo "		<TD align='center' width='20' style='font-size:9px'><ACRONYM TITLE=\"APROVAR\">APR.</ACRONYM></TD>\n";
	echo "		<TD align='center' width='20' style='font-size:9px'><ACRONYM TITLE=\"REJEITAR\">REJ.</ACRONYM></TD>\n";
	echo "		<TD align='center' width='20' style='font-size:9px'><ACRONYM TITLE=\"ACUMULAR\">ACUM.</ACRONYM></TD>\n";
	echo "	</TR>\n";

	$valorTotal = 0;

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){

		$nome			= trim(pg_result ($res,$i,nome));
		$cnpj			= trim(pg_result ($res,$i,cnpj));
		$posto			= trim(pg_result ($res,$i,posto));
		$codigo_posto	= trim(pg_result ($res,$i,codigo_posto));
		$total			= trim(pg_result ($res,$i,total));
		$extrato		= trim(pg_result ($res,$i,extrato));

		if ($btnacao == ""){
			$acrescimo	= trim(pg_result ($res,$i,acrescimo));
			$desconto	= trim(pg_result ($res,$i,desconto));
			$obs		= trim(pg_result ($res,$i,obs));
		}

		$valorTotal = $valorTotal + $total;

		$nomeSTR = strtoupper(substr($nome,0,25));

		$cor = "#d9e2ef";
		$btn = 'amarelo';
		if ($i % 2 == 0){
			$cor = '#F1F4FA';
			$btn = 'azul';
		}
		
		if (strstr($matriz, ";" . $i . ";")) $cor = '#E49494';

		echo "	<TR class='table_line' style='background-color: $cor;'>\n";
		echo "		<TD align='center'><input type='checkbox' name='extrato[$i]' value='$extrato' CHECKED><input type='hidden' name='posto[$i]' value='$posto'></TD>\n";
		echo "		<TD align='left'><ACRONYM TITLE=\"$cnpj [ $codigo_posto ] $nome\">$nomeSTR</ACRONYM></TD>\n";
		echo "		<TD align='right' style='padding-right:5px'>". number_format($total,2,",",".") ."</TD>\n";
		if ($btnacao == "") {
			echo "		<TD align='center'><input type=\"text\" size=\"6\" maxlenght=\"10\" name=\"acrescimo[$i]\" value=\"$acrescimo\" class=\"frm\" style=\"text-align:right\"></TD>\n";
			echo "		<TD align='center'><input type=\"text\" size=\"6\" maxlenght=\"10\" name=\"desconto[$i]\" value=\"$desconto\" class=\"frm\" style=\"text-align:right\"></TD>\n";
			echo "		<TD align='center'><input type=\"text\" size=\"23\" name=\"obs[$i]\" value=\"$obs\" class=\"frm\"></TD>\n";
		}else{
			echo "		<TD align='center'><input type=\"text\" size=\"6\" maxlenght=\"10\" name=\"acrescimo[$i]\" value=\"$acrescimo[$i]\" class=\"frm\" style=\"text-align:right\"></TD>\n";
			echo "		<TD align='center'><input type=\"text\" size=\"6\" maxlenght=\"10\" name=\"desconto[$i]\" value=\"$desconto[$i]\" class=\"frm\" style=\"text-align:right\"></TD>\n";
			echo "		<TD align='center'><input type=\"text\" size=\"23\" name=\"obs[$i]\" value=\"$obs[$i]\" class=\"frm\"></TD>\n";
		}
		echo "		<TD align='center' ><INPUT TYPE=\"radio\" NAME=\"forma[$i]\" VALUE=\"1\" checked></TD>\n";
		echo "		<TD align='center' ><INPUT TYPE=\"radio\" NAME=\"forma[$i]\" VALUE=\"2\"></TD>\n";
		echo "		<TD align='center' ><INPUT TYPE=\"radio\" NAME=\"forma[$i]\" VALUE=\"3\"></TD>\n";
		echo "	</TR>\n";

	}

	# formata valores
	$valorTotal		= number_format($valorTotal,2,",",".");

	echo "	<TR>\n";
	echo "		<TD colspan=\"2\" align=\"right\" style='padding-right:10px'><b>TOTAL</b></TD>\n";
	echo "		<TD bgcolor='$cor' align='right' style='padding-right:5px'><b>$valorTotal</b></TD>\n";
	echo "		<TD colspan=\"6\"><img src=\"imagens_admin/btn_confirmaralteracoes.gif\" onclick=\"javascript: if (document.frm_extrato.btnacao.value == '' ) { document.frm_extrato.btnacao.value='confirmar' ; document.frm_extrato.submit() } else { alert ('Aguarde submissão') }\" ALT=\"Confirmar alterações dos campos\" border='0' style=\"cursor:pointer;\"></TD>\n";
	echo "	</TR>\n";

	echo "</TABLE>\n";

?>
<TABLE align='center'>
<TR>
	<TD>
		<br>
		<input type='hidden' name='total' value='<? echo $totalRegistros; ?>'>
		<input type='hidden' name='btnacao' value=''>
		<img src='imagens_admin/btn_gravar.gif' onclick="javascript: if (document.frm_extrato.btnacao.value == '' ) { document.frm_extrato.btnacao.value='aprovar' ; document.frm_extrato.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar extratos" border='0' style="cursor:pointer;">
	</TD>
</TR>
</TABLE>
<?
}else{
?>
<TABLE align='center'>
<TR>
	<TD>
		<br><br><br>
	</TD>
</TR>
</TABLE>

<?
}
?>
</FORM>

<p>
<p>

<? include "rodape.php"; ?>