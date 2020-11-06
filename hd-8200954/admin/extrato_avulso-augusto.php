<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$msg_erro = "";

$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND (tbl_posto.cnpj = '$q' OR tbl_posto_fabrica.codigo_posto ='$q')";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

function retira_acentos( $texto ){
 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
 $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
 return str_replace( $array1, $array2, $texto );
}


if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim(strtolower($_POST["btn_acao"]));

if (strlen($_POST["extrato"]) > 0) $extrato = $_POST["extrato"];
if (strlen($_GET["extrato"]) > 0) $extrato = $_GET["extrato"];

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"]) > 0) $posto = $_GET["posto"];

if (strlen($_POST["posto_codigo"]) > 0) $posto_codigo = $_POST["posto_codigo"];
if (strlen($_GET["posto_codigo"]) > 0) $posto_codigo = $_GET["posto_codigo"];

if (strlen($_POST["posto_nome"]) > 0) $posto_nome = $_POST["posto_nome"];
if (strlen($_GET["posto_nome"]) > 0) $posto_nome = $_GET["posto_nome"];

if (strlen($_POST["lista_lancamento"]) > 0) $lista_lancamento = $_POST["lista_lancamento"];
if (strlen($_GET["lista_lancamento"]) > 0) $lista_lanamento = $_GET["lista_lancamento"];

if (strlen($_POST["total_lanca"]) > 0) $total_lanca = $_POST["total_lanca"];
if (strlen($_GET["total_lanca"]) > 0) $total_lanca = $_GET["total_lanca"];


if ($btn_acao == 'gravar'){
	if(strlen($posto) == 0 and strlen($posto_codigo) > 0){
		$sql = "SELECT posto
				FROM   tbl_posto_fabrica
				WHERE  tbl_posto_fabrica.codigo_posto = '$posto_codigo'
				AND    tbl_posto_fabrica.fabrica      = $login_fabrica;";
		$res = pg_exec($con,$sql);
		$posto = pg_result($res,0,posto);
	}

	$res = pg_exec($con,"BEGIN TRANSACTION");

	//lenoxx não fecha o extrato no ato do lançamento avulso (entra no proximo extrato)
	//takashi hd 9482 $login_fabrica <> 45  13/12/07
	if (($login_fabrica <> 7 and $login_fabrica <> 51 and $login_fabrica <> 11 and $login_fabrica <> 3 and $login_fabrica <> 45 AND $login_fabrica<>30 AND $login_fabrica<>50 AND $login_fabrica<>59 and $login_fabrica <> 5) and (strlen($extrato) == 0)){
		# HD 111271
		if($login_fabrica == 20) {
			$liberado_campo = ",liberado_telecontrol";
			$liberado_valor = ",current_timestamp";
		}
		$sql = "INSERT INTO tbl_extrato (
					posto    ,
					fabrica  ,
					total    ,
					aprovado
					$liberado_campo
				) VALUES (
					$posto            ,
					$login_fabrica    ,
					0                 ,
					current_timestamp
					$liberado_valor
				)";
		$res = pg_exec ($con,$sql);
		if (pg_errormessage ($con) > 0) $msg_erro = pg_errormessage ($con);

		if (strlen($msg_erro) == 0){
			$sql = "SELECT CURRVAL ('seq_extrato')";
			$res = pg_exec ($con,$sql);
			$extrato = pg_result ($res,0,0);
			$msg_erro = pg_errormessage ($con);
		}
	}

	if (strlen($extrato) == 0) $extrato = 'null';

	if($login_fabrica==1 AND strlen($extrato) > 0 AND $extrato <> "null"){//HD 46333
		$sqlA = "SELECT aprovado
				 FROM tbl_extrato
				 WHERE fabrica = $login_fabrica
				 AND   extrato = $extrato";
		$resA = pg_exec($con, $sqlA);
		if(pg_numrows($resA)>0) $data_aprovado = pg_result($resA, 0, aprovado);
	}

	$sql = "UPDATE tbl_extrato SET aprovado = null
			WHERE  extrato = $extrato
			AND    fabrica = $login_fabrica";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage ($con);

	for ($i = 0 ; $i < $total_lanca ; $i++) {

		$extrato_lancamento = $_POST ['extrato_lancamento_' . $i];
		$lancamento         = $_POST ['lancamento_' . $i] ;
		$historico          = $_POST ['historico_' . $i] ;
		$valor              = $_POST ['valor_' . $i] ;
		$ant_valor          = $_POST ['ant_valor_' . $i] ;

		// HD 11015 Paulo
		if($login_fabrica == 3) {
			$competencia_futura = $_POST['competencia_futura_' . $i];

			$competencia_futura = str_replace (" " , "" , $competencia_futura);
			$competencia_futura = str_replace ("-" , "" , $competencia_futura);
			$competencia_futura = str_replace ("/" , "" , $competencia_futura);
			$competencia_futura = str_replace ("." , "" , $competencia_futura);

			if (strlen ($competencia_futura) > 0) {
				$competencia_futura = "'".substr ($competencia_futura,2,4) . "-" . substr ($competencia_futura,0,2) . "-01" ."'";
				$sql="SELECT $competencia_futura::date > current_date ";
				$res=pg_exec($con,$sql);
				$data_competencia=pg_result($res,0,0);

				if($data_competencia == 'f') {
					$msg_erro="A Data de Competência Deveria Ser Maior Que A Data Atual";
				}
			}else {
				$competencia_futura = 'null';
			}
		} else {
			$competencia_futura = 'null';
		}
		//HD 11015 ^

		if (strlen($extrato_lancamento) > 0 AND strlen($lancamento) == 0 AND strlen($historico) == 0 AND strlen($valor) == 0){
			$sql = "DELETE FROM tbl_extrato_lancamento
					WHERE  extrato_lancamento = $extrato_lancamento;";
			$res = @pg_exec($con,$sql);

			$msg_erro = pg_errormessage($con);
		}
		if (strlen($lancamento) > 0 OR strlen($historico) > 0 OR strlen($valor) > 0){

			if (strlen($valor) == 0)
				$msg_erro = "Informe o Valor";
			else
				$xvalor = trim($valor);

			if (strlen($lancamento) == 0)
				$msg_erro = "Informe a Descrição do Lançamento";
			else
				$xlancamento = "'".trim($lancamento)."'";

			if (strlen($historico) == 0)
				$xhistorico = 'null';
			else
				$xhistorico = "'". trim($historico) ."'";

			$total_ant_valor += $ant_valor;

			if (strlen($msg_erro) == 0) {

				$sql = "SELECT debito_credito FROM tbl_lancamento WHERE lancamento = $lancamento and fabrica = $login_fabrica;";
				$resL = @pg_exec($con, $sql);
				$debito_credito = @pg_result($resL,0,debito_credito);

				$sql = "SELECT fnc_limpa_moeda('$xvalor');";
				$resM = @pg_exec($con, $sql);
				$xvalor = @pg_result($resM,0,0);

				if ($debito_credito == 'D') $xvalor = '-'.$xvalor;

				if (strlen ($extrato_lancamento) == 0) {
					$sql = "INSERT INTO tbl_extrato_lancamento (
								posto                ,
								fabrica              ,
								extrato              ,
								lancamento           ,
								historico            ,
								valor                ,
								admin                ,
								competencia_futura
							) VALUES (
								$posto               ,
								$login_fabrica       ,
								$extrato             ,
								$xlancamento         ,
								$xhistorico          ,
								'$xvalor'            ,
								$login_admin         ,
								$competencia_futura
							)";
				} else{
					$sql = "UPDATE tbl_extrato_lancamento SET
								lancamento         = $xlancamento         ,
								historico          = $xhistorico          ,
								valor              = '$xvalor'            ,
								competencia_futura = $competencia_futura
							WHERE extrato_lancamento = $extrato_lancamento;";
				}
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);

			}
		}//fim ifao
	}//for
	
	# HD 111271
	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
	
	if (strlen ($msg_erro) == 0 and strlen($extrato) > 0) {
		$config["tamanho"] = 2048000; // Tamanho máximo do arquivo (em bytes)
		if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){
			// Verifica o mime-type do arquivo
			if (!preg_match("/\/(zip|x-zip|x-zip-compressed|x-compress|x-compressed|pdf|msword|doc|word|x-msw6|x-msword|pjpeg|jpeg|png|gif|bmp|msexcel|xls|vnd.ms-excel|richtext|plain|html)$/", $arquivo["type"])){
				$msg_erro = "Arquivo em formato inválido!";
			} else { // Verifica tamanho do arquivo
				if ($arquivo["size"] > $config["tamanho"])
					$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
			}
			if (strlen($msg_erro) == 0) {
				// Pega extensão do arquivo
				preg_match("/\.(pdf|doc|gif|bmp|png|jpg|jpeg|rtf|xls|txt|zip){1}$/i", $arquivo["name"], $ext);
				$aux_extensao = "'".$ext[1]."'";
				$arquivo["name"]=retira_acentos($arquivo["name"]);
				$nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));

				$nome_anexo = "/www/assist/www/admin/documentos/" . $extrato."-".strtolower ($nome_sem_espaco);

				if (strlen($msg_erro) == 0) {
					if (copy($arquivo["tmp_name"], $nome_anexo)) {
					}else{
						$msg_erro = "Arquivo não foi enviado!!!";
					}
				}
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT SUM (valor) AS valor
				FROM tbl_extrato_lancamento
				WHERE tbl_extrato_lancamento.extrato = $extrato
				AND   tbl_extrato_lancamento.fabrica = $login_fabrica;";
		$res3 = pg_exec($con,$sql);
		$valor_total = pg_result($res3,0,valor);

		if($login_fabrica == 20) {
			$sql_avulso = " , avulso = $valor_total,mao_de_obra=0,pecas=0 ";
		}
		$sql = "UPDATE tbl_extrato SET
						total =  $valor_total - $total_ant_valor + total
						$sql_avulso
				WHERE extrato = $extrato;";
		$res5 = pg_exec($con,$sql);
	}
	if ( (strlen ($msg_erro) == 0) and ($extrato <> 'null') ) {
		//if($login_fabrica <> 20){

			$sql = "SELECT fn_calcula_extrato($login_fabrica,$extrato);";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			if(strlen($msg_erro) == 0 AND strlen($data_aprovado) > 0 AND $login_fabrica == 1){//HD 46333
				$sql = "UPDATE tbl_extrato SET aprovado = '$data_aprovado'
						WHERE  extrato = $extrato
						AND    fabrica = $login_fabrica";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage ($con);
			}
		/*}else{
			$sql = "SELECT fn_calcula_extrato($login_fabrica,$extrato);";
			$txt_bosch .= "10-$sql <br>";

			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			$sql = "
			SELECT SUM(tbl_extrato_lancamento.valor) as valor_avulso
			FROM   tbl_extrato_lancamento
			WHERE  tbl_extrato_lancamento.extrato = $extrato;";
	//echo "sql: $sql";
			$txt_bosch .= "<br>sql: $sql";
			$res = @pg_exec($con,$sql);

			$tot_avulso = pg_result($res,0,valor_avulso);
			if(strlen($tot_avulso)==0){
				$tot_avulso = 0;
			}

			if($tot_avulso > 0){
				$sql = "
					UPDATE tbl_extrato SET
						total              = mao_de_obra  + pecas + $tot_avulso,
						avulso             = $tot_avulso
					WHERE  tbl_extrato.extrato = $extrato
					AND    tbl_extrato.fabrica = $login_fabrica;";
				$txt_bosch .= "<br>sql: $sql";
				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

		}*/
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: menu_financeiro.php");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "financeiro";
$title = "Lançamentos Avulsos";

include "cabecalho.php";

?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size:11px;
	font-weight: bold;
	border: 1px solid;
	background-color: #D9E2EF
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.aviso {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size:11px;
	font-weight: italic;
	border: 1px solid;
	background-color: #FFFFFF

}
</style>
<? include "javascript_pesquisas.php" ;?>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>

<!-- HD 11015 Paulo -->
<script type="text/javascript" charset="utf-8">
	$(function(){
		$("input[rel='mascara_data']").maskedinput("99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.augusto.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});

</script>

<?if (strlen ($msg_erro) > 0) {?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<?
		echo $msg_erro;
		echo "<script>alert('$msg_erro');history.back()</script>";
		exit;
		?>
	</td>
</tr>
</table>
<?}

if (strlen($posto) > 0){

	$sql = "SELECT tbl_posto.nome                ,
				   tbl_posto_fabrica.codigo_posto
			FROM   tbl_posto
			JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto    = $posto
								     AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE  tbl_posto.posto = $posto;";
	$res = pg_exec($con,$sql);
	$posto_codigo       = @pg_result($res,0,codigo_posto);
	$posto_nome         = @pg_result($res,0,nome);
}

?>

<TABLE width='700' border='0' align='center' cellspacing='2' cellpadding='4'>
<FORM METHOD='POST' NAME='frm_extrato_avulso' ACTION="<? echo $PHP_SELF ?>" enctype="multipart/form-data">
<input type='hidden' name='btn_acao' value=''>
<input type='hidden' name='posto' value='<?echo $posto;?>'>
<input type='hidden' name='extrato' value='<?echo $extrato;?>'>

<TR class='menu_top'>
	<td nowrap align='left'>&nbsp;Código do Posto</td>
	<td align='left' colspan='2'>&nbsp;Nome do Posto</td>
</tr>
<?if ((strlen($extrato) > 0) AND (strlen($posto) > 0)){
?>
	<tr>
		<td nowrap align='left' width='30%'><?echo $posto_codigo; ?></td>
		<td align='left' width='70%'><? echo $posto_nome; ?></td>
	</TR>
<?}else{?>
<tr>
	<td nowrap align='left' width='30%' nowrap>
		<input class="frm" type="text" name="posto_codigo" id="posto_codigo" size="15" value="<? echo $posto_codigo ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto (document.frm_extrato_avulso.posto_codigo,document.frm_extrato_avulso.posto_nome,'codigo')"></A>
	</td>
	<td align='left' width='75%' nowrap>
		<input class="frm" type="text" name="posto_nome" id="posto_nome" size="50" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_extrato_avulso.posto_codigo,document.frm_extrato_avulso.posto_nome,'nome')" style="cursor:pointer;"></A>
	</td>
	<?
	if ($login_fabrica == 7 or $login_fabrica == 11 or $login_fabrica == 20 or $login_fabrica == 45 ) {
		echo "<td align='right' width='25%' nowrap>";
		echo "<input type=\"hidden\" value=\"\" name=\"lista_lancamento\">";
		echo "<a href=\"javascript: document.frm_extrato_avulso.lista_lancamento.value='listar' ; document.frm_extrato_avulso.submit(); \" ALT=\"Exibir lançamentos deste Posto que ainda não entraram em um extrato\" border='0'\"><font face='Verdana' size='1'><B>Exibir Lançamentos deste Posto</B></font>";
		echo "</td>";
	}
	?>
</TR>
<?}?>
</TABLE>
<p>
<?
	if($login_fabrica == 3) {
	echo "<div>";
	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>";
	echo "<TR class='aviso'>";
	echo "<TD ALIGN='center'>";
	echo "Informar a data de competência caso necessite definir o mês do pagamento. Avulsos sem a data de competência futura entrarão automaticamente no próximo fechamento de extrato.";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	}
?>
<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>

<caption class='menu_top' border='0'>
	LANÇAMENTO DE EXTRATO AVULSO
</caption>

<TR class='menu_top'>
<TD width='25%'>DESCRIÇÃO</TD>
<TD width='40%'>HISTÓRICO</TD>
<TD width='15%'>VALOR</TD>
<? if ($login_fabrica == 3) {?>
	<TD width='20%'>COMPETÊNCIA FUTURA</TD>
<? } ?>

</TR>

<?

//lancamentos com extrato
if (strlen($extrato) > 0){
	$sql = "SELECT	lancamento                ,
					historico                 ,
					valor                     ,
					extrato_lancamento        ,
					to_char(competencia_futura,'MM/YYYY') as competencia_futura
			FROM	tbl_extrato_lancamento
			WHERE	extrato = $extrato
			AND     fabrica = $login_fabrica;";
	$res = pg_exec ($con,$sql);

//lançamentos sem extrato
} elseif ( ($lista_lancamento == 'listar') and (strlen(trim($posto_codigo))>0) ) {
	$sql = "SELECT posto
			FROM  tbl_posto_fabrica
			WHERE codigo_posto = '$posto_codigo'
			AND   fabrica = $login_fabrica;";
	$resX = pg_exec ($con,$sql);

	$lposto = @pg_result($resX,0,posto);

	if (pg_numrows($resX) > 0) {
		$sql = "SELECT	lancamento                ,
						historico                 ,
						valor                     ,
						extrato_lancamento        ,
						to_char(competencia_futura,'MM/YYYY') as competencia_futura
				FROM  tbl_extrato_lancamento
				WHERE extrato isnull
				AND   fabrica = $login_fabrica
				AND   posto   = $lposto
				ORDER BY data_lancamento;";
		$res = pg_exec ($con,$sql);
	}
}

$total_lanca = 5;

if(strlen($extrato) > 0){
	$sql3 = "SELECT count(*) as total_lanca FROM tbl_extrato_lancamento WHERE extrato = $extrato";
	$res3 = pg_exec($con,$sql3);
	if(pg_numrows($res3)> 0){
		$total_lanca = pg_result($res3,0,total_lanca)+3;
	}
}

echo "<INPUT TYPE='hidden' NAME='total_lanca' value='$total_lanca'>";

for ($i = 0; $i < $total_lanca; $i++){
	$lancamento          = @pg_result($res,$i,lancamento);
	$historico           = @pg_result($res,$i,historico);
	$valor               = @pg_result($res,$i,valor);
	$extrato_lancamento  = @pg_result($res,$i,extrato_lancamento);

	//hd 11015 Paulo
	$competencia_futura  = @pg_result($res,$i,competencia_futura);
//	$debito_credito      = @pg_result($res,$i,debito_credito);

	echo "<TR bgcolor='#f8f8f8'>";
	echo "<TD>";

	$sql = "SELECT  lancamento, descricao
			FROM    tbl_lancamento
			WHERE   tbl_lancamento.fabrica = $login_fabrica
			AND      tbl_lancamento.ativo IS TRUE
			ORDER BY tbl_lancamento.descricao;";
	$res1 = pg_exec ($con,$sql);

	if (pg_numrows($res1) > 0) {
		echo "<select class='frm' style='width: 180px;' name='lancamento_$i'>\n";
		echo "<option value=''>ESCOLHA</option>\n";

		for ($x = 0 ; $x < pg_numrows($res1) ; $x++){
			$aux_lancamento = trim(pg_result($res1,$x,lancamento));
			$aux_descricao  = trim(pg_result($res1,$x,descricao));

			echo "<option value='$aux_lancamento'"; if ($lancamento == $aux_lancamento) echo " SELECTED "; echo ">$aux_descricao</option>\n";
		}
		echo "</select>\n";
	}else {echo  "<h2><a href='lancamentos_avulsos_cadastro.php'>Lançamento não encontrado</a></h2>";}

	echo "</TD>";
//	echo "<TD><input type='text' class='frm' name='historico_$i' value='$historico' size='50' maxlength='50'></TD>";
	$disabled = '';
	if($login_fabrica == 1 AND strlen($extrato) > 0 AND strlen($valor) > 0 ){
		$disabled = 'readonly';
	}
	echo "<TD><textarea name=\"historico_$i\" rows=\"3\" cols=\"45\" class='frm' $disabled>$historico</TEXTAREA></TD>";
	echo "<TD><input type='text' class='frm' name='valor_$i' value='$valor' size='10' maxlength='10' >";
	echo "<input type='hidden' name='ant_valor_$i' value='$valor'></TD>";
	//HD 11015 Paulo
	if ($login_fabrica == 3 ) {
		echo "<td><input type='text' class='frm' name='competencia_futura_$i' value='$competencia_futura' rel='mascara_data' size='8' maxlength='7' ";
		if(strlen($extrato) > 0) {
			echo " disabled>";
		} else {
			echo " >";
		}
		echo "</td>";
	}
	echo "</TR>";

	echo "<input type='hidden' name='extrato_lancamento_$i' value='$extrato_lancamento'>";

}

?>
</table>
<?
	if($login_fabrica == 20) { // HD  111271
		echo "<div>";
		echo "<input type='file' name='arquivo'>";
		echo "</div>";
	}
?>
<p>

<img src="imagens_admin/btn_gravar.gif" onclick="javascript:
if (document.frm_extrato_avulso.btn_acao.value==''){
	document.frm_extrato_avulso.btn_acao.value='gravar' ;
	document.frm_extrato_avulso.submit();
}else{
	alert('Aguarde submissão');
}" ALT="Gravar formulário" border='0'>
</form>
<p>

<p>
<? include "rodape.php"; ?>
