<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$msg_erro = "";

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim(strtolower($_POST["btn_acao"]));

if (strlen($_POST["extrato"]) > 0) $extrato = $_POST["extrato"];
if (strlen($_GET["extrato"]) > 0) $extrato = $_GET["extrato"];

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"]) > 0) $posto = $_GET["posto"];

if (strlen($_POST["posto_codigo"]) > 0) $posto_codigo = $_POST["posto_codigo"];
if (strlen($_GET["posto_codigo"]) > 0) $posto_codigo = $_GET["posto_codigo"];

if (strlen($_POST["lista_lancamento"]) > 0) $lista_lancamento = $_POST["lista_lancamento"];
if (strlen($_GET["lista_lancamento"]) > 0) $lista_lanamento = $_GET["lista_lancamento"];

if ($btn_acao == 'gravar'){

	if(strlen($posto) == 0 and strlen($posto_codigo) > 0){
		$sql = "SELECT posto
				FROM   tbl_posto_fabrica
				WHERE  tbl_posto_fabrica.codigo_posto = '$posto_codigo'
				AND    tbl_posto_fabrica.fabrica      = $login_fabrica;";
//if ($ip == '201.0.9.216') echo "1) $sql<br>";
		$res = pg_exec($con,$sql);
		$posto = pg_result($res,0,posto);
	}

	$res = pg_exec($con,"BEGIN TRANSACTION");

	//lenoxx não fecha o extrato no ato do lançamento avulso (entra no proximo extrato)
	if (($login_fabrica <> 11) and (strlen($extrato) == 0)){
		$sql = "INSERT INTO tbl_extrato (
					posto    ,
					fabrica  ,
					total    ,
					aprovado 
				) VALUES (
					$posto            ,
					$login_fabrica    ,
					0                 ,
					current_timestamp 
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
	
	$sql = "UPDATE tbl_extrato SET aprovado = null 
			WHERE  extrato = $extrato 
			AND    fabrica = $login_fabrica";
//if ($ip == '201.0.9.216') echo "3) $sql<br>";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage ($con);
	
	for ($i = 0 ; $i < 5 ; $i++) {

		$extrato_lancamento = $_POST ['extrato_lancamento_' . $i];
		$lancamento         = $_POST ['lancamento_' . $i] ;
		$historico          = $_POST ['historico_' . $i] ;
		$valor              = $_POST ['valor_' . $i] ;
		$ant_valor          = $_POST ['ant_valor_' . $i] ;

		//detete
		if (strlen($extrato_lancamento) > 0 AND strlen($lancamento) == 0 AND strlen($historico) == 0 AND strlen($valor) == 0){
			$sql = "DELETE FROM tbl_extrato_lancamento
					WHERE  extrato_lancamento = $extrato_lancamento;";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
		if (strlen($lancamento) > 0 OR strlen($historico) > 0 OR strlen($valor) > 0){

			if (strlen($valor) == 0)
				$msg_erro = "Informe lo Valor";
			else
				$xvalor = trim($valor);

			if (strlen($lancamento) == 0)
				$msg_erro = "Informe la descripción del lanzamiento";
			else
				$xlancamento = "'".trim($lancamento)."'";

			if (strlen($historico) == 0)
				$xhistorico = 'null';
			else
				$xhistorico = "'". trim($historico) ."'";

			$total_ant_valor += $ant_valor;

			if (strlen($msg_erro) == 0) {

				$sql = "SELECT debito_credito FROM tbl_lancamento WHERE lancamento = $lancamento and fabrica = $login_fabrica;";
//if ($ip == '201.0.9.216') echo "4) $sql<br>";
				$resL = @pg_exec($con, $sql);
				$debito_credito = @pg_result($resL,0,debito_credito);

				$sql = "SELECT fnc_limpa_moeda('$xvalor');";
//if ($ip == '201.0.9.216') echo "5) $sql<br>";
				$resM = @pg_exec($con, $sql);
				$xvalor = @pg_result($resM,0,0);
				
				if ($debito_credito == 'D') $xvalor = '-'.$xvalor;

				if (strlen ($extrato_lancamento) == 0) {
					$sql = "INSERT INTO tbl_extrato_lancamento (
								posto         ,
								fabrica       ,
								extrato       ,
								lancamento    ,
								historico     ,
								valor         
							) VALUES (
								$posto         ,
								$login_fabrica ,
								$extrato       ,
								$xlancamento   ,
								$xhistorico    ,
								'$xvalor'
							)";
				}else{
					$sql = "UPDATE tbl_extrato_lancamento SET
								lancamento = $xlancamento ,
								historico  = $xhistorico  ,
								valor      = '$xvalor'
							WHERE extrato_lancamento = $extrato_lancamento;";
				}
//if ($ip == '201.0.9.216') echo "6) $sql<br>";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);

				//BOSCH - neste caso temos que criar um novo extrato e setar ele no valor de avulso.
				if($login_fabrica==20){
					/*$sql = "UPDATE tbl_extrato set mao_de_obra=0,pecas=0,avulso=$xvalor WHERE extrato = $extrato;";
					$res = pg_exec ($con,$sql);
					if (pg_errormessage ($con) > 0) $msg_erro = pg_errormessage ($con);*/
				}
			}
		}//fim ifao
	}//for

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT SUM (valor) AS valor
				FROM tbl_extrato_lancamento
				WHERE tbl_extrato_lancamento.extrato = $extrato
				AND   tbl_extrato_lancamento.fabrica = $login_fabrica;";
//if ($ip == '201.0.9.216') echo "7) $sql<br>";
		$res3 = pg_exec($con,$sql);
		$valor_total = pg_result($res3,0,valor);

		$sql = "UPDATE tbl_extrato SET 
						total =  $valor_total - $total_ant_valor + total
				WHERE extrato = $extrato;";
//if ($ip == '200.246.168.219') echo "8) $sql<br>";
		$res5 = pg_exec($con,$sql);

	}

	if ( (strlen ($msg_erro) == 0) and ($extrato <> 'null') ) {
		$sql = "SELECT fn_calcula_extrato($login_fabrica,$extrato);";
//if ($ip == '200.246.168.219') echo "9) $sql<br>";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

if ($ip == '201.27.212.208') echo $msg_erro;
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: menu_financeiro.php");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "financeiro";
$title = "Lanzamientos Avulsos";

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

</style>
<? include "javascript_pesquisas.php" ;?>

<script src="js/cal2.js"></script>


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
<FORM METHOD='POST' NAME='frm_extrato_avulso' ACTION="<? echo $PHP_SELF ?>">
<input type='hidden' name='btn_acao' value=''>
<input type='hidden' name='posto' value='<?echo $posto;?>'>
<input type='hidden' name='extrato' value='<?echo $extrato;?>'>

<TR class='menu_top'>
	<td nowrap align='left'>&nbsp;Código del Servicio</td>
	<td align='left' colspan='2'>&nbsp;Nombre del Servicio</td>
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
		<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto (document.frm_extrato_avulso.posto_codigo,document.frm_extrato_avulso.posto_nome,'codigo')"></A>
	</td>
	<td align='left' width='75%' nowrap>
		<input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_extrato_avulso.posto_codigo,document.frm_extrato_avulso.posto_nome,'nome')" style="cursor:pointer;"></A>
	</td>
	<?
	if ($login_fabrica == 11) {
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

<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>

<TR class='menu_top'>
	<TD ALIGN='center' colspan='3'>LANZAMIENTO DE EXTRACTO AVULSO</TD>
</TR>

<TR class='menu_top'>
<TD width='30%'>DESCRIPCIÓN</TD>
<TD width='50%'>HISTÓRICO</TD>
<TD width='20%'>COSTO</TD>
</TR>

<?

//lancamentos com extrato
if (strlen($extrato) > 0){
	$sql = "SELECT	*
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
		$sql = "SELECT *
				FROM  tbl_extrato_lancamento
				WHERE extrato isnull
				AND   fabrica = $login_fabrica
				AND   posto   = $lposto
				ORDER BY data_lancamento;";

		$res = pg_exec ($con,$sql);
	}
}


for ($i = 0; $i < 5; $i++){
	$lancamento          = @pg_result($res,$i,lancamento);
	$historico           = @pg_result($res,$i,historico);
	$valor               = @pg_result($res,$i,valor);
	$extrato_lancamento  = @pg_result($res,$i,extrato_lancamento);
//	$debito_credito      = @pg_result($res,$i,debito_credito);

	echo "<TR bgcolor='#f8f8f8'>";
	echo "<TD>";

	$sql = "SELECT  lancamento, descricao
			FROM    tbl_lancamento
			WHERE   tbl_lancamento.fabrica = $login_fabrica
			ORDER BY tbl_lancamento.descricao;";
	$res1 = pg_exec ($con,$sql);
	
	if (pg_numrows($res1) > 0) {
		echo "<select class='frm' style='width: 200px;' name='lancamento_$i'>\n";
		echo "<option value=''>ELIJA</option>\n";
		
		for ($x = 0 ; $x < pg_numrows($res1) ; $x++){
			$aux_lancamento = trim(pg_result($res1,$x,lancamento));
			$aux_descricao  = trim(pg_result($res1,$x,descricao));
			
			echo "<option value='$aux_lancamento'"; if ($lancamento == $aux_lancamento) echo " SELECTED "; echo ">$aux_descricao</option>\n";
		}
		echo "</select>\n";
	}else {echo  "<h2><a href='lancamentos_avulsos_cadastro.php'>Lanzamiento no encuentrado</a></h2>";}

	echo "</TD>";
//	echo "<TD><input type='text' class='frm' name='historico_$i' value='$historico' size='50' maxlength='50'></TD>";
	echo "<TD><textarea name=\"historico_$i\" rows=\"3\" cols=\"50\" class='frm'>$historico</TEXTAREA></TD>";
	echo "<TD><input type='text' class='frm' name='valor_$i' value='$valor' size='10' maxlength='10'>";
	echo "	<input type='hidden' name='ant_valor_$i' value='$valor'></TD>";
	echo "</TR>";
	
	echo "<input type='hidden' name='extrato_lancamento_$i' value='$extrato_lancamento'>";

}

?>
</table>

<p>

<img src="imagens_admin/btn_gravar.gif" onclick="javascript: document.frm_extrato_avulso.btn_acao.value='gravar' ; document.frm_extrato_avulso.submit(); " ALT="Gravar formulário" border='0'>
</form>
<p>

<p>
<? include "rodape.php"; ?>
<?
//$sql = "DELETE FROM tbl_extrato_lancamento WHERE extrato_lancamento = 134;";$res = pg_exec($con,$sql);
//$sql = "update tbl_extrato set total=10 WHERE extrato = 364 and posto=4927 and fabrica=10"; $res = pg_exec($con,$sql);
?>
