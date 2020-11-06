<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$title = "Fechamento de Ordem de Serviço";
$layout_menu = 'os';
include "cabecalho.php";

#------------ Fecha Ordem de Servico ------------#
$btn_acao = strtolower($_POST['btn_acao']);

if ($btn_acao == 'continuar') {

	$data_fechamento = $_POST['data_fechamento'];
	$qtde_os         = $_POST['qtde_os'];

	if (strlen($data_fechamento) == 0){
		$msg_erro = "Digite a data de fechamento.";
	}else{
		$xdata_fechamento = fnc_formata_data_pg ($data_fechamento);
		
		if($xdata_fechamento > "'".date("Y-m-d")."'") $msg_erro = "Data de fechamento maior que a data de hoje.";
		
		if (strlen($msg_erro) == 0){
			for ($i = 0 ; $i < $qtde_os ; $i++) {
				$ativo             = $_POST['ativo_'. $i];
				$os                = $_POST['os_' . $i];
				$nota_fiscal_saida = $_POST['nota_fiscal_saida_'. $i];
				$data_nf_saida     = $_POST['data_nf_saida_'. $i];

				if (strlen($data_nf_saida) == 0)
					$xdata_nf_saida = 'null';
				else
					$xdata_nf_saida    = fnc_formata_data_pg ($data_nf_saida) ;
					
				if (strlen($nota_fiscal_saida) == 0)
					$xnota_fiscal_saida = 'null';
				else
					$xnota_fiscal_saida = "'".$nota_fiscal_saida."'";
				
				if ($ativo == 't'){
					$res = pg_exec ($con,"BEGIN TRANSACTION");
					$sql = "UPDATE  tbl_os SET
									data_fechamento   = $xdata_fechamento  ,
									nota_fiscal_saida = $xnota_fiscal_saida,
									data_nf_saida     = $xdata_nf_saida
							WHERE   tbl_os.os         = $os
							AND     tbl_os.posto      = $login_posto
							AND     tbl_os.fabrica    = $login_fabrica";
					$res       = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage ($con);
					#echo $msg_erro;
					if (strlen ($msg_erro) == 0) {
						$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
						$res = @pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
					
					if (strlen ($msg_erro) > 0) {
						$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
						$linha_erro = $i;
						break;
					}else{
						$res = @pg_exec ($con,"COMMIT TRANSACTION");
						$data_fechamento   = "";
						$nota_fiscal_saida = "";
						$data_nf_saida     = "";
						$msg_ok = "<font size='2'><b>OS(s) fechada(s) com sucesso!!!</b></font>";
					}
				}//fim if
			}//for
		} // if msg_erro
	}//if
}

if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Bad date external ") > 0) $msg_erro = "Data de fechamento inválida";
	if (strpos ($msg_erro,'"tbl_os" violates check constraint "data_fechamento"') > 0) $msg_erro = "Data de fechamento inválida";
?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<br>
<? } ?>

<? if (strlen ($msg_ok) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#FFCC66">
<tr>
	<td height="27" valign="middle" align="center">
<? echo "$msg_ok"; ?>
	</td>
</tr>
</table>
<? } ?>

<br>

<table width="700" border="0" cellpadding="2" cellspacing="0" align="center">
<form name='frm_os_pesquisa' action='<? echo $PHP_SELF; ?>' method='post'>
<input type='hidden' name='btn_acao_pesquisa' value=''>
<tr height="22" bgcolor="#bbbbbb">
	<TD>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Número da OS a ser fechada</b></font>
		<input type='text' name='sua_os' value=''>
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os_pesquisa.btn_acao_pesquisa.value == '' ) { document.frm_os_pesquisa.btn_acao_pesquisa.value='continuar' ; document.frm_os_pesquisa.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar busca pela Ordem de Serviço" border='0' style='cursor: pointer'>
	</TD>
	<td align="right"><a href='<? echo $PHP_SELF."?listar=todas"; ?>'>Listar todas as OS's</a></td>
</tr>
</form>
</table>

<?
$btn_acao_pesquisa = $_POST['btn_acao_pesquisa'];
if (strlen($_GET['btn_acao_pesquisa']) > 0) $btn_acao_pesquisa = $_GET['btn_acao_pesquisa'];

$listar            = $_POST['listar'];
if (strlen($_GET['listar']) > 0) $listar = $_GET['listar'];

$sua_os            = $_POST['sua_os'];
if (strlen($_GET['sua_os']) > 0) $sua_os = $_GET['sua_os'];

	if ( (strlen($sua_os) > 0 AND $btn_acao_pesquisa == 'continuar') OR strlen($listar) > 0){
?>

<br><br>

<?
		$sql = "SELECT	tbl_os.os                                                  ,
						tbl_os.sua_os                                              ,
						tbl_os.serie                                               ,
						tbl_produto.descricao                                      ,
						tbl_produto.nome_comercial                                 ,
						to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
						tbl_os.consumidor_nome                                     ,
						tbl_os.consumidor_revenda
				FROM	tbl_os 
				JOIN	tbl_produto USING (produto)
				WHERE	tbl_os.data_fechamento IS NULL
				AND		(tbl_os.excluida       IS NULL OR tbl_os.excluida IS FALSE)
				AND		tbl_os.posto   = $login_posto 
				AND		tbl_os.fabrica = $login_fabrica ";

		if ($login_fabrica == 1) {
			$sqlX =	"SELECT codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND   tbl_posto.posto           = $login_posto";
			$resX = pg_exec($con,$sqlX);
			if (pg_numrows($resX) > 0) {
				$codigo_posto = trim(pg_result($resX,0,codigo_posto));
			}
			$sua_os = substr($sua_os, strlen($codigo_posto), strlen($sua_os));
/*
			// verifica se deve ter motivo de atraso
			$sqlX = "SELECT to_char (tbl_os.data_abertura::date + INTERVAL '20 days', 'YYYY-MM-DD')";
			$resX = pg_exec ($con,$sqlX);
			$data_consultar = pg_result($resX,0,0);

			//$sqlX = "SELECT to_char (current_date , 'YYYYMMDD')";
			//$resX = pg_exec ($con,$sqlX);
			//$data_atual = pg_result ($resX,0,0);
			$data_atual = date("Ymd");

			if ($data_consultar < $data_atual) {
				$sql .= "AND tbl_os.motivo_atraso notnull ";
			}
*/
		}

		if (strlen($sua_os) > 0) $sql .= "AND tbl_os.sua_os ilike '%$sua_os%' ";

		$sql .= "ORDER BY lpad(substr(sua_os,0,strpos(sua_os,'-')),20,0) DESC, lpad(os,20,0) DESC;";

		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage ($con);

		if (pg_numrows($res) > 0){

?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="center">
		<h4>
		Com o fechamento da OS você se habilita ao recebimento do valor da mão-de-obra, que será pago no próximo Extrato.
		</h4>

		<?
		if ($login_fabrica == 1){
			echo "<table width='700' border='0' cellspacing='2' cellpadding='0' align='center'>";
			echo "<tr>";
			echo "<td align='center' width='18' height='18' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size=1>&nbsp; OSs que excederam o prazo limite de 30 dias para fechamento, favor informar o \"Motivo\"</font></td>";
			echo "</tr>";
			echo "</table>";
		}
		?>

		<!-- ------------- Formulário ----------------- -->

		<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input type='hidden' name='qtde_os' value='<? echo pg_numrows ($res); ?>'>

		<input type='hidden' name='btn_acao_pesquisa' value='<? echo $btn_acao_pesquisa ?>'>
		<input type='hidden' name='listar' value='<? echo $listar ?>'>

		<TABLE width="700" border="0" cellpadding="2" cellspacing="0" align="center">
		<TR height="20" bgcolor="#bbbbbb">
			<TD width='25%'><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">Data de Fechamento</font></TD>
			<TD nowrap>
				<input class="frm" type='text' name='data_fechamento' size='14' maxlength='10' value='<? echo $data_fechamento ?>'>
			</TD>
		</TR>
		</TABLE>

		<table width="700" border="0" cellspacing="1" cellpadding="4" align="center">
		<tr height="20" bgcolor="#bbbbbb">
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">&nbsp;</font></b></td>
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font></b></td>
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font></b></td>
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">Consumidor</font></b></td>
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">Produto</font></b></td>
<? if ($login_fabrica <> 2 AND $login_fabrica <> 1){ ?>
			<td nowrap><b><font size='2' face='Geneva, Arial, Helvetica, san-serif'>Nota Fiscal de Saída</font></b></td>
			<td nowrap><b><font size='2' face='Geneva, Arial, Helvetica, san-serif'>Data NF de Saída</font></b></td>
<? } ?>
		</tr>

<?
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
//			$data_nf_saida     = $_POST['data_nf_saida_' . $i];
//			$nota_fiscal_saida = $_POST['nota_fiscal_saida_' . $i];

			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			
			$os     = trim(pg_result ($res,$i,os));
			$sua_os = trim(pg_result ($res,$i,sua_os));
			
			if (strlen($sua_os) == 0) $sua_os = $os;
			$descricao = pg_result ($res,$i,nome_comercial) ;
			if (strlen ($descricao) == 0) $descricao = pg_result ($res,$i,descricao) ;

			$consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda));

			if ($login_fabrica == 1) {
				$sql =	"SELECT os
						FROM tbl_os
						WHERE os = $os
						AND   motivo_atraso IS NULL
						AND   (data_abertura + INTERVAL '30 days')::date < current_date;";
				$resY = pg_exec($con, $sql);
				if (pg_numrows($resY) > 0) { 
					$flag_motivo = "t";
					$cor = "#FF0000";
				}else{
					$flag_motivo = "";
				}

#				$resX = pg_exec($con,"SELECT to_char (current_date , 'YYYY-MM-DD')");
#				$data_atual = pg_result($resX,0,0);
			}
		?>

		<tr  bgcolor="<? echo $cor ?>" <? if ($linha_erro == $i and strlen ($msg_erro) > 0 )?>>
			<input type='hidden' name='os_<? echo $i ?>' value='<? echo pg_result ($res,$i,os) ?>'>
			<td align="center"><? if (strlen($flag_motivo) == 0) { ?><input type="checkbox" class="frm" name="ativo_<?echo $i?>" value="t" ><? } ?></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><a href='os_press.php?os=<? echo $os ?>' target='_blank'><? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; ?></a></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo pg_result ($res,$i,data_abertura) ?></td>
			<td NOWRAP ><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo substr (pg_result ($res,$i,consumidor_nome),0,10) ?></td>
			<td NOWRAP><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo pg_result ($res,$i,serie) . " - " . substr ($descricao,0,20) ?></td>
<? if ($login_fabrica <> 2 AND $login_fabrica <> 1){ ?>
			<?
			if ($consumidor_revenda == 'R'){
				echo "<td><input class='frm' type='text' name='nota_fiscal_saida_$i' size='14' maxlength='10' value='$nota_fiscal_saida'></td>";
				echo "<td><input class='frm' type='text' name='data_nf_saida_$i' size='14' maxlength='10' value='$data_nf_saida'></td>";
			}else{
				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
			}
			?>
<? } ?>
		</tr>
		<?
		}
		?>

		</table>

	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" background="" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_fechar_azul.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar com Ordem de Serviço" border='0' style='cursor: pointer'>
	</td>
</tr>

</form>

</table>
<?
		}else{
?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td valign="top" align="center">
		<h4>
		Não foi encontrada OS não finalizada com esse número.
		</h4>
	</td>
</tr>
</table>
<?
		}

	}
?>
<p>

<? include "rodape.php"; ?>
