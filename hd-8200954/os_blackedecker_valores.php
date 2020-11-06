<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if($login_fabrica != 1 ){
	header("Location: menu_os.php");
	exit;
}

include 'funcoes.php';

$msg_erro = "";

$qtde_visita = 5;

if (strlen($_GET['os']) > 0)  $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if(strlen($_POST['btn_acao']) > 0) $btn_acao = strtolower($_POST['btn_acao']);

if ($btn_acao == "gravar"){

	$xobs     = trim($_POST['obs']);
	$xtecnico = trim($_POST['tecnico']);

	if(strlen($obs) > 0) $xobs = "'".$xobs."'";
	else                 $xobs = 'null';

	if(strlen($tecnico) > 0) $xtecnico = "'".$xtecnico."'";
	else                     $xtecnico = 'null';

	if (strlen($msg_erro) == 0){
		$res = pg_exec($con,"BEGIN TRANSACTION");

		########## tbl_os_visita ##########

		for ( $i = 0 ; $i < $qtde_visita ; $i++ ) {

			$xos_visita            = trim($_POST['os_visita_'. $i]);
			$xdata                 = fnc_formata_data_pg(trim($_POST['data_'. $i]));
			$xxdata                = str_replace("'","",$xdata);
			$xhora_chegada_cliente = trim($_POST['hora_chegada_cliente_'. $i]);
			$xhora_saida_cliente   = trim($_POST['hora_saida_cliente_'. $i]);
			$xkm_chegada_cliente   = trim($_POST['km_chegada_cliente_'. $i]);

			if (strlen($xos_visita) > 0 AND ($xdata == 'null' OR strlen($xhora_chegada_cliente) == 0 OR strlen($xhora_saida_cliente) == 0)) {
				$sql = "DELETE FROM tbl_os_visita
						WHERE  tbl_os_visita.os        = $os
						AND    tbl_os_visita.os_visita = $xos_visita;";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen($xos_visita) == 0 AND $xdata <> 'null' AND strlen($xhora_chegada_cliente) > 0 AND strlen($xhora_saida_cliente) > 0) {
				$xhora_chegada_cliente = "'$xxdata ".$xhora_chegada_cliente."'";
				$xhora_saida_cliente   = "'$xxdata ".$xhora_saida_cliente."'";
				$sql =	"INSERT INTO tbl_os_visita (
							os                   ,
							data                 ,
							hora_chegada_cliente ,
							hora_saida_cliente   ,
							km_chegada_cliente   ,
							hora_chegada_sede    ,
							hora_saida_sede      
						) VALUES (
							$os                    ,
							$xdata                 ,
							$xhora_chegada_cliente ,
							$xhora_saida_cliente   ,
							$xkm_chegada_cliente   ,
							current_timestamp      ,
							current_timestamp      
						)";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen($xos_visita) > 0 AND $xdata <> 'null' AND strlen($xhora_chegada_cliente) > 0 AND strlen($xhora_saida_cliente) > 0) {
				$xhora_chegada_cliente = "'$xxdata ".$xhora_chegada_cliente."'";
				$xhora_saida_cliente   = "'$xxdata ".$xhora_saida_cliente."'";
				$sql = "UPDATE tbl_os_visita set
							data                 = $xdata                 ,
							hora_chegada_cliente = $xhora_chegada_cliente ,
							hora_saida_cliente   = $xhora_saida_cliente   ,
							km_chegada_cliente   = $xkm_chegada_cliente   
						WHERE os = $os
						AND   os_visita = $xos_visita";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		} // Fim do FOR

		if (strlen($msg_erro) == 0){
			$sql = "UPDATE tbl_os_extra set
						obs        = $xobs,
						tecnico    = $xtecnico
					WHERE os = $os";
			$res = @pg_exec($con,$sql);
			$msg_erro = @pg_errormessage($con);
		}

	}

	if (strlen($msg_erro) == 0){
		$res = pg_exec ($con,"UPDATE tbl_os SET data_fechamento = current_timestamp WHERE os = $os AND fabrica = $login_fabrica");
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0){
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: os_item.php?os=$os");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}

########## LÊ BASE DE DADOS ##########
if (strlen($os) > 0) {

	$sql =	"SELECT tbl_os.os                                                    ,
					tbl_os.sua_os                                                ,
					to_char (tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
					tbl_os_extra.obs                                             ,
					tbl_os_extra.tecnico                                         ,
					tbl_posto_fabrica.codigo_posto                               
			FROM	tbl_os
			JOIN tbl_produto USING (produto)
			JOIN tbl_posto USING (posto)
			JOIN tbl_posto_fabrica  ON  tbl_posto.posto           = tbl_posto_fabrica.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os.os = $os
			AND   tbl_os.fabrica = $login_fabrica ";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) == 1) {
		$os            = pg_result($res,0,os);
		$sua_os        = pg_result($res,0,sua_os);
		$data_abertura = pg_result($res,0,data_abertura);
		$obs           = pg_result($res,0,obs);
		$tecnico       = pg_result($res,0,tecnico);
		$codigo_posto  = pg_result($res,0,codigo_posto);
	}

	$sql = "SELECT * FROM vw_os_print WHERE os = $os";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) == 1) {
		$obs_os              = pg_result($res,0,obs);
		$defeito_reclamado   = pg_result($res,0,defeito_reclamado);
		$cliente             = pg_result($res,0,cliente);
		$cliente_nome        = pg_result($res,0,cliente_nome);
		$cliente_cpf         = pg_result($res,0,cliente_cpf);
		$cliente_rg          = pg_result($res,0,cliente_rg);
		$cliente_endereco    = pg_result($res,0,cliente_endereco);
		$cliente_numero      = pg_result($res,0,cliente_numero);
		$cliente_complemento = pg_result($res,0,cliente_complemento);
		$cliente_bairro      = pg_result($res,0,cliente_bairro);
		$cliente_cep         = pg_result($res,0,cliente_cep);
		$cliente_cidade      = pg_result($res,0,cliente_cidade);
		$cliente_fone        = pg_result($res,0,cliente_fone);
		$cliente_nome        = pg_result($res,0,cliente_nome);
		$cliente_estado      = pg_result($res,0,cliente_estado);
		$cliente_contrato    = pg_result($res,0,cliente_contrato);
	}

}

$title = "Ordem de Serviço - Valores";

$layout_menu = "os";

include 'cabecalho.php';

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

.table_line2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
}

input, select {
	font-size: 10px;
}

.top_lst {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_lst {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

input {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1x solid #a0a0a0;
	background-color: #FFFFFF;
}

TEXTAREA {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1x solid #a0a0a0;
	background-color: #FFFFFF;
}

</style>

<? if (strlen($msg_erro) > 0){ ?>
<TABLE width='100%'>
<TR>
	<TD class='error'><? echo $msg_erro; ?></TD>
</TR>
</TABLE>
<?
}
?>

<form name='frm_os' action='<? echo $PHP_SELF; ?>' method="post">
<input type="hidden" name="os"      value="<? echo $os; ?>">
<input type="hidden" name="sua_os"  value="<? echo $sua_os; ?>">

<?
///////// se nao foi setado valor da OS
if (strlen($os) > 0) {
?>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">Ordem de Serviço</td>
		<td class="table_line2" nowrap><? echo $codigo_posto.$sua_os; ?></td>
		<td class="menu_top">Abertura</td>
		<td class="table_line2" nowrap><? echo $data_abertura ?></td>
	</tr>
</table>

<br>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
<?
	if (strlen (trim ($cliente_ie)) == 0) $cliente_ie = "&nbsp";

	switch (strlen (trim ($cliente_cpf))) {
		case 0:
			$cliente_cpf = "&nbsp";
		break;
		case 11:
			$cliente_cpf = substr ($cliente_cpf,0,3) . "." . substr ($cliente_cpf,3,3) . "." . substr ($cliente_cpf,6,3) . "-" . substr ($cliente_cpf,9,2);
		break;
		case 14:
			$cliente_cpf = substr ($cliente_cpf,0,2) . "." . substr ($cliente_cpf,2,3) . "." . substr ($cliente_cpf,5,3) . "/" . substr ($cliente_cpf,8,4) . "-" . substr ($cliente_cpf,12,2);
		break;
	}

?>
	<tr>
		<td class="menu_top">RAZÃO SOCIAL</td>
		<TD class="table_line2" nowrap colspan='2'><? echo $cliente_nome ?>&nbsp</TD>
		<td class="menu_top">CNPJ</td>
		<TD class="table_line2" nowrap><? echo $cliente_cpf ?>&nbsp</TD>
		<td class="menu_top">IE</td>
		<TD class="table_line2"><? echo $cliente_rg ?>&nbsp</TD>
	</tr>
	<tr>
		<td class="menu_top">ENDEREÇO</td>
		<TD class="table_line2" colspan='2'><? echo $cliente_endereco . ", " . $cliente_numero . " " . $cliente_complenento ?>&nbsp</TD>
		<td class="menu_top">CEP</td>
		<TD class="table_line2"><? echo substr($cliente_cep,0,5)."-".substr ($cliente_cep,5,3); ?>&nbsp</TD>
		<td class="menu_top">TELEFONE</td>
		<TD class="table_line2"><? echo $cliente_fone ?>&nbsp</TD>
	</tr>
	<tr>
		<td class="menu_top">BAIRRO</td>
		<TD class="table_line2" colspan='2'><? echo $cliente_bairro ?>&nbsp</TD>
		<td class="menu_top">CIDADE</td>
		<TD class="table_line2"><? echo $cliente_cidade ?>&nbsp</TD>
		<td class="menu_top">ESTADO</td>
		<TD class="table_line2"><? echo $cliente_estado ?>&nbsp</TD>
	</tr>
	<tr>
		<td class="menu_top">DEFEITO</td>
		<TD class="table_line2" colspan='2'><? echo $defeito_reclamado ?>&nbsp</TD>
		<td class="menu_top">CONTATO</td>
		<TD class="table_line2" colspan="2"><? echo $quem_abriu_chamado ?>&nbsp</TD>
	</tr>
	<tr>
		<td class="menu_top">OBS</td>
		<TD class="table_line2" colspan='5'><? echo $obs_os ?>&nbsp</TD>
	</tr>
</table>

<BR>

<?
if (strlen($os) > 0 AND strlen($msg_erro) == 0) {
	$sql  = "SELECT tbl_os_visita.os_visita                                                        ,
					to_char(tbl_os_visita.data,'DD/MM/YYYY')               AS data                 ,
					to_char(tbl_os_visita.hora_chegada_cliente, 'HH24:MI') AS hora_chegada_cliente ,
					to_char(tbl_os_visita.hora_saida_cliente, 'HH24:MI')   AS hora_saida_cliente   ,
					tbl_os_visita.km_chegada_cliente                                               
			FROM    tbl_os_visita
			WHERE   tbl_os_visita.os = $os
			ORDER BY tbl_os_visita.os_visita;";
	$res = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	$flag = pg_numrows($res);
}

for($x = 0 ; $x < $qtde_visita ; $x++) {

	if ($x == 0) {
		echo "<table class='border' width='700' align='center' border='0' cellpadding='1' cellspacing='3'>\n";
		echo "<tr>\n";
		echo "<td width='25%' class='menu_top' rowspan='2'>DATA<br>(Ex.: ".date("d/m/Y").")</td>\n";
		echo "<td width='25%' class='menu_top' colspan='2'>Tempo de Serviço</td>\n";
		echo "<td width='25%' class='menu_top' rowspan='2'>Km<br>Total</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td width='25%' class='menu_top'>Início</td>\n";
		echo "<td width='25%' class='menu_top'>Término</td>\n";
		echo "</tr>\n";
	}

	if (strlen($msg_erro) == 0) {
		$os_visita            = trim(@pg_result($res,$x,os_visita));
		$data                 = trim(@pg_result($res,$x,data));
		$hora_chegada_cliente = trim(@pg_result($res,$x,hora_chegada_cliente));
		$hora_saida_cliente   = trim(@pg_result($res,$x,hora_saida_cliente));
		$km_chegada_cliente   = trim(@pg_result($res,$x,km_chegada_cliente));
	}else{
		$os_visita            = $_POST['os_visita_'.$x];
		$data                 = $_POST['data_'.$x];
		$hora_chegada_cliente = $_POST['hora_chegada_cliente_'.$x];
		$hora_saida_cliente   = $_POST['hora_saida_cliente_'.$x];
		$km_chegada_cliente   = $_POST['km_chegada_cliente_'.$x];
	}

	if ($x % 2 == 0) $cor = "#F0F0F0";
	else             $cor = "#FAFAFA";

	echo "<input type='hidden' name='os_visita_$x' value='$os_visita'>\n";
	echo "<TR bgcolor=$cor>\n";
	echo "<TD align='center'><INPUT TYPE='text' NAME='data_$x' value='$data' size='12' maxlength='10'></TD>\n";
	echo "<TD align='center'><INPUT TYPE='text' NAME='hora_chegada_cliente_$x' value='$hora_chegada_cliente' size='06' maxlength='5'></TD>\n";
	echo "<TD align='center'><INPUT TYPE='text' NAME='hora_saida_cliente_$x' value='$hora_saida_cliente' size='06' maxlength='5'></TD>\n";
	echo "<TD align='center'><INPUT TYPE='text' NAME='km_chegada_cliente_$x' value='$km_chegada_cliente' size='06' maxlength='5'></TD>\n";
	echo "</TR>\n";
}
?>
</table>

<br>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">Observações</td>
	</tr>
	<tr>
		<TD class="table_line"><TEXTAREA NAME="obs" ROWS="2" COLS="122"><? echo $obs; ?></TEXTAREA></TD>
	</tr>
</table>

<br>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">Relatório do Técnico</td>
	</tr>
	<tr>
		<TD class="table_line"><TEXTAREA NAME="tecnico" ROWS="5" COLS="122"><? echo $tecnico; ?></TEXTAREA></TD>
	</tr>
</table>

<br>

<center>
<input type='hidden' name='btn_acao' value=''>
<img src="imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
<img src="imagens/btn_voltar.gif" style="cursor: pointer;" onclick="javascript: history.back(-1);" ALT="Voltar e digitar outra OS" border='0'>
</center>

<?
} // fim do if q verifica se OS foi setada
?>

</form>

<? include 'rodape.php'; ?>