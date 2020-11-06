<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

$layout_menu = "os";
$title = "Extrato Distribuidor";

include "cabecalho.php";

$data = trim($_GET['data']);
if (strlen(trim($_POST['data'])) > 0) $data = trim($_POST['data']);

$dataFormatada = substr($data,8,2)."/".substr($data,5,2)."/".substr($data,0,4);
$codposto = trim($_GET['codposto']);
if (strlen(trim($_POST['codposto'])) > 0) $codposto = trim($_POST['codposto']);

if ($btn_acao == 'baixar'){
	$extrato = $_POST["extrato"];
	$baixado = $_POST["baixado"];
	$obs     = $_POST["obs"];

	if (strlen($baixado) > 0) {
		$aux_baixado = str_replace ("/","",$baixado);
		$aux_baixado = str_replace ("-","",$aux_baixado);
		$aux_baixado = str_replace (".","",$aux_baixado);
		$aux_baixado = str_replace (" ","",$aux_baixado);

		$dia = trim (substr ($aux_baixado,0,2));
		$mes = trim (substr ($aux_baixado,2,2));
		$ano = trim (substr ($aux_baixado,4,4));
		if (strlen ($ano) == 2) $ano = "20" . $ano;

		$aux_baixado = $ano . "-" . $mes . "-" . $dia ;

		$aux_baixado = "'" . $aux_baixado . "'";
	}else{
		$msg_erro = "Preencha a data de baixa deste extrato";
	}

	if (strlen($obs) > 0)
		$aux_obs = "'" . $obs . "'";
	else
		$aux_obs = "null";

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		if (strlen($extrato) > 0) {
			$sql = "UPDATE tbl_extrato_extra SET
						baixado         = $aux_baixado     ,
						obs             = $aux_obs         ,
						baixa_digitacao = current_timestamp,
						distribuidor    = $login_posto     
					WHERE tbl_extrato_extra.extrato = $extrato
					AND   tbl_extrato_extra.extrato = tbl_extrato.extrato
					AND   tbl_extrato_extra.baixado IS NULL
					AND   tbl_extrato.fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (strlen ($msg_erro) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				header ("Location: extrato_consulta.php");
				exit;
			}else{
				$baixado         = $_POST["baixado"];
				$obs             = $_POST["obs"];
				$extrato         = $_POST["extrato"];
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}

?>

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
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<?
echo "<br>";
// INICIO DA SQL
/*
$sql = "SELECT  tbl_posto_fabrica.posto            ,
				tbl_posto_fabrica.codigo_posto     ,
				tbl_posto.nome                     ,
				sum(tbl_os.mao_de_obra) AS MO_Posto,
				sum(tbl_os.mao_de_obra + tbl_familia.mao_de_obra_adicional_distribuidor) AS MO_Distribuidor
		FROM    tbl_posto_fabrica
		JOIN    tbl_posto          ON tbl_posto.posto         = tbl_posto_fabrica.posto
		JOIN    tbl_fabrica        ON tbl_fabrica.fabrica     = tbl_posto_fabrica.fabrica
		JOIN    tbl_os             ON tbl_os.posto            = tbl_posto_fabrica.posto
		JOIN    tbl_produto        ON tbl_produto.produto     = tbl_os.produto
		JOIN    tbl_familia        ON tbl_familia.familia     = tbl_produto.familia
		JOIN    tbl_os_extra       ON tbl_os_extra.os         = tbl_os.os
		JOIN    tbl_extrato        ON tbl_os_extra.extrato    = tbl_extrato.extrato
		WHERE   tbl_extrato.data_geracao BETWEEN '$data 00:00:00' AND '$data 23:59:59'
		AND     (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto)
		AND     tbl_fabrica.fabrica = $login_fabrica
		GROUP BY        tbl_posto_fabrica.posto       ,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome
		ORDER BY sum(tbl_os.mao_de_obra + tbl_familia.mao_de_obra_adicional_distribuidor) DESC, 
				sum(tbl_os.mao_de_obra) DESC,
				tbl_posto_fabrica.posto;";
*/

$sql = "SELECT  distinct
				tbl_posto_fabrica.posto            ,
				tbl_posto_fabrica.codigo_posto     ,
				tbl_posto.nome                     ,
				tbl_extrato.mao_de_obra            ,
				sum(tbl_familia.mao_de_obra_adicional_distribuidor) AS adicional
		FROM    tbl_posto_fabrica
		JOIN    tbl_posto           ON tbl_posto.posto         = tbl_posto_fabrica.posto
		JOIN    tbl_fabrica         ON tbl_fabrica.fabrica     = tbl_posto_fabrica.fabrica
		JOIN    tbl_os              ON tbl_os.posto            = tbl_posto_fabrica.posto
		JOIN    tbl_produto         ON tbl_produto.produto     = tbl_os.produto
		JOIN    tbl_familia         ON tbl_familia.familia     = tbl_produto.familia
		JOIN    tbl_os_extra        ON tbl_os_extra.os         = tbl_os.os
		JOIN    tbl_extrato         ON tbl_os_extra.extrato    = tbl_extrato.extrato
		WHERE   tbl_extrato.data_geracao BETWEEN '$data 00:00:00' AND '$data 23:59:59'
		AND     (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto)
		AND     tbl_fabrica.fabrica = $login_fabrica
		GROUP BY tbl_posto_fabrica.posto      ,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome                ,
				tbl_extrato.mao_de_obra
		ORDER BY tbl_extrato.mao_de_obra  DESC,
				tbl_posto_fabrica.posto;";
$res = pg_exec ($con,$sql);
//if ($ip == '192.168.0.20') echo $sql;

echo "<TABLE width='580' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

if (pg_numrows($res) > 0) {
	echo "<TR class='menu_top'><TD colspan='7'>Extrato de $dataFormatada</TD></tr>";

	echo "<TR class='menu_top'>\n";
	echo "	<TD align=\"center\">CÓDIGO</TD>\n";
	echo "	<TD align=\"center\">RAZÃO SOCIAL</TD>\n";
	echo "	<TD align=\"center\">MO PA</TD>\n";
	echo "	<TD align=\"center\">MO DIST</TD>\n";
	echo "</TR>\n";
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$posto				= trim(pg_result($res,$i,posto));
		$codigo_posto		= trim(pg_result($res,$i,codigo_posto));
		$nome				= trim(pg_result($res,$i,nome));
		$MO_Posto			= trim(pg_result($res,$i,mao_de_obra));
		$adicional			= trim(pg_result($res,$i,adicional));

		if ($posto == $login_posto) {
			$MO_Distribuidor = $MO_Posto;
		}else{
			$MO_Distribuidor = $MO_Posto + $adicional;
		}

		$totalMO_Distribuidor += $MO_Distribuidor;
		$totalMO_Posto        += $MO_Posto;

		echo "<TR class='table_line' style='background-color: $cor;'>\n";
		echo "	<TD align='left' style='padding-left:7px;'><a href='$PHP_SELF?codposto=$posto&data=$data'>$codigo_posto</a></TD>\n";
		echo "	<TD align='left' >$nome</TD>\n";
		echo "	<TD align='right'  style='padding-right:3px;' nowrap>".number_format($MO_Posto,2,',','.')."</TD>\n";
		echo "	<TD align='right'  style='padding-right:3px;' nowrap>".number_format($MO_Distribuidor,2,',','.')."</TD>\n";
		echo "</TR>\n";
	}
	echo "<TR class='table_line' style='background-color: $cor;'>\n";
	echo "	<TD COLSPAN = '2' align='right' style='padding-left:7px;'><b>TOTAL&nbsp;</b></TD>\n";
	echo "	<TD align='right' >".number_format($totalMO_Posto,2,',','.')."</TD>\n";
	echo "	<TD align='right' >".number_format($totalMO_Distribuidor,2,',','.')."</TD>\n";
	echo "</TR>\n";
		
}else{

	echo "	<TR class='table_line'>\n";
	echo "		<TD align=\"center\">NENHUM EXTRATO FOI ENCONTRADO</TD>\n";
	echo "	</TR>\n";
	echo "	<TR>\n";
	echo "		<TD align=\"center\">\n";
	echo "			<br><a href='menu_os.php'><img src='imagens/btn_voltar.gif'></a>";
	echo "		</TD>\n";
	echo "	</TR>\n";

}

echo "</TABLE>\n";

if(strlen($codposto) > 0){
	$sql = "SELECT  tbl_os.sua_os                                                  ,
					tbl_os.os                                                      ,
					tbl_os.mao_de_obra                                             ,
					tbl_os.consumidor_nome                                         ,
					tbl_os.data_abertura                                           ,
					tbl_os.data_fechamento                                         ,
					tbl_os.mao_de_obra_distribuidor AS MO_Distribuidor             ,
					tbl_familia.mao_de_obra_adicional_distribuidor  AS adicional   ,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
			FROM	tbl_os_extra
			JOIN	tbl_os      USING(os)
			JOIN    tbl_produto        ON tbl_produto.produto       = tbl_os.produto
			JOIN    tbl_familia        ON tbl_familia.familia       = tbl_produto.familia
			JOIN    tbl_extrato        ON tbl_extrato.extrato       = tbl_os_extra.extrato
			WHERE   tbl_extrato.data_geracao BETWEEN '$data 00:00:00' AND '$data 23:59:59'
			AND	tbl_os.posto = $codposto";
	$res = pg_exec ($con,$sql);
	
	$adicional         = 0;
	$total             = 0;
	$total_mao_de_obra = 0;
	$total_pecas       = 0;

	echo "<br><br>";
	echo "<TABLE width='580' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	echo "<TR class='menu_top'>\n";
	echo "	<TD align=\"center\">OS</TD>\n";
	echo "	<TD align=\"center\">CLIENTE</TD>\n";
	echo "	<TD align=\"center\">MO PA</TD>\n";
	echo "	<TD align=\"center\">MO DIST</TD>\n";
	echo "</TR>\n";
	
	for ($i = 0 ; $i < pg_numrows($res); $i++){
		$os				= trim(pg_result ($res,$i,os));
		$sua_os			= trim(pg_result ($res,$i,sua_os));
		$adicional		= trim(pg_result ($res,$i,adicional));
		$mao_de_obra	= trim(pg_result ($res,$i,mao_de_obra));
		$MO_Distribuidor = trim(pg_result ($res,$i,MO_Distribuidor));
		$consumidor_nome= strtoupper(trim(pg_result ($res,$i,consumidor_nome)));
		$consumidor_str	= substr($consumidor_nome,0,23);
		$data_abertura  = trim (pg_result ($res,$i,data_abertura));
		$data_fechamento= trim (pg_result ($res,$i,data_fechamento));
		
		if ($codposto == $login_posto) {
			if ($MO_Distribuidor == 0 OR strlen($MO_Distribuidor) == 0) {
				$MO_Distribuidor = $mao_de_obra;
			}
			$mao_de_obra = $MO_Distribuidor;
		}else{
			if ($MO_Distribuidor == 0 OR strlen($MO_Distribuidor) == 0) {
				$MO_Distribuidor = $mao_de_obra + $adicional;
			}
		}
		
		# soma valores
		$total_mao_de_obra += $mao_de_obra ;
		$total_MO_Distribuidor += $MO_Distribuidor ;
		
		$cor = "#d9e2ef";
		if ($i % 2 == 0) $cor = '#F1F4FA';
		
		echo "	<TR class='table_line' style='background-color: $cor;'>\n";
		echo "		<TD align='center'><acronym title=\"Abertura: $data_abertura | Fechamento: $data_fechamento \"><a href=\"os_press.php?os=$os\" target='_blank'>$sua_os</a></acronym></TD>\n";
		echo "		<TD align='left' nowrap><acronym title=\"$consumidor\">$consumidor_str</acronym></TD>\n";
		echo "		<TD align='right' style='padding-right:5px'>" . number_format ($mao_de_obra,2,",",".") . "</TD>\n";
		echo "		<TD align='right' style='padding-right:5px'>" . number_format ($MO_Distribuidor,2,",",".") . "</TD>\n";
		echo "	</TR>\n";
	}
	echo "<TR class='table_line' style='background-color: $cor;'>\n";
	echo "	<TD COLSPAN = '2' align='right' style='padding-left:7px;'><b>TOTAL&nbsp;</b></TD>\n";
	echo "	<TD align='right' >".number_format($total_mao_de_obra,2,',','.')."</TD>\n";
	echo "	<TD align='right' >".number_format($total_MO_Distribuidor,2,',','.')."</TD>\n";
	echo "</TR>\n";
	echo "</TABLE>\n";
}

// ----------------- verifica se é distribuidor       ----------------- 
// ----------------- para poder dar baixa no extrato  ----------------- 
$sql = "SELECT  distribuidor     
		FROM    tbl_posto_fabrica
		WHERE   posto        = $codposto
		AND     distribuidor = $login_posto";
$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0){

	$sql = "SELECT	to_char(tbl_extrato_extra.baixado,'DD/MM/YYYY') AS baixado,
					tbl_extrato_extra.obs                                     ,
					tbl_extrato_extra.extrato
			FROM	tbl_extrato_extra
			JOIN	tbl_extrato  ON tbl_extrato.extrato  = tbl_extrato_extra.extrato
			JOIN	tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato
			JOIN	tbl_os       ON tbl_os_extra.os      = tbl_os.os
			WHERE	tbl_extrato.data_geracao BETWEEN '$data 00:00:00' AND '$data 23:59:59'
			AND		tbl_os.posto = $codposto";
	$res = @pg_exec ($con,$sql);

	if (@pg_numrows($res) > 0){
		$extrato = @pg_result ($res,0,extrato);
		$obs     = @pg_result ($res,0,obs);
		if (strlen (@pg_result ($res,0,baixado)) > 0) $ja_baixado = true ;
?>
	<HR WIDTH='580' ALIGN='CENTER'>

	<TABLE width='580' border='0' cellspacing='1' cellpadding='0'>
	<FORM METHOD=POST NAME='frm_extrato_os' ACTION="<? echo $PHP_SELF; ?>">
	<input type='hidden' name='extrato' value='<? echo $extrato; ?>'>
	<input type='hidden' name='btn_acao' value=''>
	<input type='hidden' name='codposto' value='<? echo $codposto; ?>'>
	<input type='hidden' name='data' value='<? echo $data; ?>'>
	<TR>
		<TD height='20' class="menu_top" colspan='4'>PAGAMENTO EFETUADO AO POSTO AUTORIZADO</TD>
	</TR>
	<TR>
		<TD align='left' class="menu_top">EXTRATO PAGO EM: </TD>
		<TD class="table_line">
			<?
			if ($ja_baixado == false) {
				echo "<INPUT TYPE='text' NAME='baixado' size='10' maxlength='10' value='" . $baixado . "'>&nbsp;";
				//echo "<INPUT TYPE='text' NAME='baixado' size='10' maxlength='10' value='" . $baixado . "'>&nbsp;<img src='imagens/btn_lupa.gif' align='absmiddle' onclick=\"javascript:showCal('dataPesquisa')\" style='cursor:pointer' alt='Clique aqui para abrir o calendário'>";
			}else{
				echo $baixado;
			}
			?>
		</TD>
		<TD align='left' class="menu_top"><center>OBS:</center></TD>
		<TD class="table_line">
			<?
			if ($ja_baixado == false) {
				echo "<INPUT TYPE='text' NAME='obs'  size='55' value='" . $obs . "'>";
			}else{
				echo $obs;
			}
			?>
		</td>
	</TR>
	<TR>
		<TD colspan='4' ALIGN='center'><img src='imagens/btn_baixar.gif' onclick="javascript: if (document.frm_extrato_os.btn_acao.value == '' ) { document.frm_extrato_os.btn_acao.value='baixar' ; document.frm_extrato_os.submit() } else { alert ('Aguarde submissão') }" ALT='Baixar' border='0' style='cursor:pointer;'></TD>
	</TR>
	</form>
	</TABLE>
<?
	}
}
?>
<p>

<p>
<? include "rodape.php"; ?>