<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

$title = "Fechamento de Ordem de Serviço";
if($sistema_lingua == 'ES')$title = "Cierre de órdenes de servicio";
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
				$ativo             = trim($_POST['ativo_'. $i]);
				$os                = trim($_POST['os_' . $i]);
				$nota_fiscal_saida = trim($_POST['nota_fiscal_saida_'. $i]);
				$data_nf_saida     = trim($_POST['data_nf_saida_'. $i]);

				// Verifica se o status da OS for 62 (intervencao da fabrica) // fabio 02/01/2007
				if ($login_fabrica == 10 AND 1==1){
					$sql = "SELECT  status_os
							FROM    tbl_os_status
							WHERE   os = $os
							ORDER BY data DESC LIMIT 1";
					$res = @pg_exec($con,$sql);
					if (pg_numrows($res) > 0) {
						$os_intervencao_fabrica = trim(pg_result($res,0,status_os));
						if ($os_intervencao_fabrica == '62') {
							$erro = "<br>OS $os está com Intervenção da Assistência técnica da Fábrica. Não pode ser fechada.";
						}
						if ($os_intervencao_fabrica == '65') {
							$erro = "<br>OS $os está em reparo na assistência técnica da Fábrica. Não pode ser fechada.";
						}
					}
				}


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

					if (strlen ($erro) == 0) {
						$sql = "UPDATE  tbl_os SET
										data_fechamento   = $xdata_fechamento  ,
										nota_fiscal_saida = $xnota_fiscal_saida,
										data_nf_saida     = $xdata_nf_saida
								WHERE   tbl_os.os         = $os";
						$res  = @pg_exec ($con,$sql);
						$erro = pg_errormessage ($con);
					}
					if (strlen ($erro) == 0 AND $login_fabrica == 1) {
						$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
						$res = @pg_exec ($con,$sql);
						$erro = pg_errormessage($con);
					}

					if (strlen ($erro) == 0) {
						$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
						$res = @pg_exec ($con,$sql);
						$erro = pg_errormessage($con);
					}

					if (strlen ($erro) > 0) {
						$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
						$linha_erro[$i] = 1;
						$msg_erro = $erro;
						$erro = '';
					}else{
						$res = @pg_exec ($con,"COMMIT TRANSACTION");
						$data_fechamento   = "";
						$nota_fiscal_saida = "";
						$data_nf_saida     = "";
						$msg_ok = 1;
						//$msg_ok = "<font size='2'><b>OS(s) fechada(s) com sucesso!!!</b></font>";
					}
				}//fim if
			}//for
		} // if msg_erro
	}//if
}
?>

<script language="JavaScript">
var checkflag = "false";
function SelecionaTodos(field) {
    if (checkflag == "false") {
        for (i = 0; i < field.length; i++) {
            field[i].checked = true;
        }
        checkflag = "true";
        return true;
    }
    else {
        for (i = 0; i < field.length; i++) {
            field[i].checked = false;
        }
        checkflag = "false";
        return true;
    }
}
</script>

<?
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Bad date external ") > 0) $msg_erro = "Data de fechamento inválida";
	if (strpos ($msg_erro,'"tbl_os" violates check constraint "data_fechamento"') > 0) $msg_erro = "Data de fechamento inválida";
	if (strpos ($msg_erro,"É necessário informar a solução na OS") > 0) $msg_solucao = 1;
	if (strpos ($msg_erro,"Para esta solução é necessário informar as peças trocadas") > 0) $msg_solucao = 1;
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}

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
<? echo "<font size='2'><b>OS(s) fechada(s) com sucesso!!!</b></font>"; ?>
	</td>
</tr>
</table>
<? } ?>

<br>

<?
if ($login_fabrica == 1 and 1 == 2) {
	echo "<table width='700' border='0' cellspacing='2' cellpadding='5' align='center'>";
	echo "<tr>";
	echo "<td align='center' bgcolor='#6699FF' style='font-color:#ffffff ; font-size:12px'>";
	echo "<B>Conforme comunicado de 04/01/2006, as OS's abertas até o dia 31/12/2005 poderão ser digitadas até o dia 31/01/2006.<br>Pedimos atenção especial com relação a esse prazo, pois depois do dia 01/02/2006 somente aceitaremos a abertura das OS's com data posterior a 02/01/2006.</B>";
	echo "</td>";
	echo "</tr>";
	echo "</table><br>";
}

$sua_os       = $_POST['sua_os'];
$codigo_posto = $_POST['codigo_posto'];
?>
<?
//takashi 12/12 email samuel
if($login_fabrica==1 and 1==2){
	$sql_data = "SELECT current_date as data";
	$res_data = pg_exec($con,$sql_data);
	$hoje     =  trim(pg_result($res_data,0,data));
	if($hoje > "2006-12-17" and $hoje < "2006-12-24"){
		include "comunicados/black2006.php";
		exit;
	}
}
?>
<table width="700" border="0" cellpadding="2" cellspacing="0" align="center">
<form name='frm_os_pesquisa' action='<? echo $PHP_SELF; ?>' method='post'>
<input type='hidden' name='btn_acao_pesquisa' value=''>
<tr height="22" bgcolor="#bbbbbb">
<? if ($login_fabrica == 1) { ?> <td align="center" width="35%"><a href='<? echo $PHP_SELF."?listar=todas"; ?>'><FONT COLOR="#CC0000"> &middot; Listar todas suas OS's</FONT></a></td> <? } ?>
	<TD>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><? if($sistema_lingua == 'ES') echo "Número de OS a ser cerrada";else echo "Número da OS a ser fechada";?></b></font>
		<input type='text' name='sua_os' size='10' value='<? echo $sua_os ?>'>
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os_pesquisa.btn_acao_pesquisa.value == '' ) { document.frm_os_pesquisa.btn_acao_pesquisa.value='continuar' ; document.frm_os_pesquisa.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar busca pela Ordem de Serviço" border='0' style='cursor: pointer'>
	</TD>
<? if ($login_fabrica <> 1) { ?> <td align="right"><a href='<? echo $PHP_SELF."?listar=todas"; ?>'><? if($sistema_lingua == 'ES') echo "Listar todas sus OS's";else echo "Listar todas suas OS's";?></a></td> <? } ?>
</tr>

<? if ($login_e_distribuidor == 't') { ?>

<tr height="22" bgcolor="#bbbbbb">
	<TD>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Listar Todas as OS do Posto</b></font>
		<input type='text' name='codigo_posto' size='8' value='<? echo $codigo_posto ?>'>
		<input type='submit' value='Listar' name='btn_listar_posto'>
	</TD>
	<TD>&nbsp;
	</TD>
</tr>

<? } ?>

</form>
</table>

<?
$btn_acao_pesquisa = $_POST['btn_acao_pesquisa'];
if (strlen($_GET['btn_acao_pesquisa']) > 0) $btn_acao_pesquisa = $_GET['btn_acao_pesquisa'];

$listar = $_POST['listar'];
if (strlen($_GET['listar']) > 0) $listar = $_GET['listar'];

$sua_os = $_POST['sua_os'];
if (strlen($_GET['sua_os']) > 0) $sua_os = $_GET['sua_os'];

$codigo_posto = $_POST['codigo_posto'];
if (strlen($_GET['codigo_posto']) > 0) $codigo_posto = $_GET['codigo_posto'];

?>

<br><br>

<?
$posto = $login_posto;
if (strlen ($codigo_posto) > 0) {
	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$posto = pg_result ($res,0,0);
}

	if ( (strlen($sua_os) > 0 AND $btn_acao_pesquisa == 'continuar') OR strlen($listar) > 0 OR strlen ($codigo_posto) > 0){
		$sql = "SELECT	tbl_os.os                                                  ,
						tbl_os.sua_os                                              ,
						tbl_os.serie                                               ,
						tbl_produto.descricao                                      ,
						tbl_produto.nome_comercial                                 ,
						to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
						tbl_os.consumidor_nome                                     ,
						tbl_os.consumidor_revenda                                  ,
						tbl_os.defeito_constatado                                  ,
						tbl_os.admin                                               ,
						tbl_os.tipo_atendimento
				FROM	tbl_os
				JOIN    (SELECT os FROM tbl_os WHERE posto = $posto AND fabrica = $login_fabrica AND data_fechamento IS NULL) oss ON tbl_os.os = oss.os
				JOIN	tbl_produto USING (produto)
				JOIN    tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
				WHERE	tbl_os.data_fechamento IS NULL
				AND		(tbl_os.excluida       IS NULL OR tbl_os.excluida IS FALSE)
				AND		tbl_os.fabrica = $login_fabrica ";

		if (strlen ($codigo_posto) == 0) {
			$sql .= " AND tbl_os.posto = $login_posto ";
		}else{
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' AND (tbl_os.posto = $login_posto OR tbl_os.posto IN (SELECT posto FROM tbl_posto_linha WHERE distribuidor = $login_posto))";
		}

		if ($login_fabrica == 1) $sua_os = substr($sua_os, strlen($login_codigo_posto), strlen($sua_os));

		if (strlen($sua_os) > 0) $sql .= "AND (tbl_os.sua_os ilike '$sua_os%' OR tbl_os.sua_os ILIKE '0$sua_os%' OR tbl_os.sua_os ILIKE '00$sua_os%' OR tbl_os.sua_os ILIKE '000$sua_os%' OR tbl_os.sua_os ILIKE '0000$sua_os%' OR tbl_os.sua_os ILIKE '00000$sua_os%' OR tbl_os.sua_os ILIKE '000000$sua_os%' OR tbl_os.sua_os ILIKE '0000000$sua_os%' OR tbl_os.sua_os ILIKE '00000000$sua_os%') ";

		//$sql .= "ORDER BY lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,0) DESC, lpad(tbl_os.os,20,0) DESC;";
		//$sql .= "ORDER BY lpad(tbl_os.sua_os,20,0) DESC, lpad(tbl_os.os,20,0) DESC LIMIT 50 ;";
		$sql .= "ORDER BY lpad(tbl_os.sua_os,20,0) DESC, lpad(tbl_os.os,20,0) DESC ;";

//if ($ip == '201.27.214.119') { echo $sql; exit; }
//if ($login_posto == '6393') { echo $sql; exit; }

		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage ($con);

		if (pg_numrows($res) > 0){

?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="center">
		<h4>
		Com o fechamento da OS você se habilita ao recebimento dos valores <br> que serão pagos no próximo Extrato.
		</h4>

		<?
		if ($login_fabrica == 1){
			echo "<table width='700' border='0' cellspacing='2' cellpadding='0' align='center'>";
			echo "<tr>";
			echo "<td align='center' width='18' height='18' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size=1>&nbsp; OSs que excederam o prazo limite de 30 dias para fechamento, favor informar o \"Motivo\"</font></td>";
			echo "</tr>";
			echo "<tr height='4'><td colspan='2'></td></tr>";
			echo "<tr>";
			echo "<td align='center' width='18' height='18' bgcolor='#FFCC66'>&nbsp;</td>";
			echo "<td align='left'><font size=1>&nbsp; OSs sem defeito constatado</font></td>";
			echo "</tr>";
			if (strlen($msg_solucao) > 0){
				echo "<tr height='4'><td colspan='2'></td></tr>";
				echo "<tr>";
				echo "<td align='center' width='18' height='18' bgcolor='#99FFFF'>&nbsp;</td>";
				echo "<td align='left'><font size=1>&nbsp; OSs sem solução e sem itens lançados.</font></td>";
				echo "</tr>";
			}
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
			<TD width='25%'><b><font size="2" face="Geneva, Arial, Helvetica, san-serif"><?if($sistema_lingua == 'ES') echo "Fecha Cierre";else echo "Data de Fechamento";?></font></TD>
			<TD nowrap>
				<input class="frm" type='text' name='data_fechamento' size='14' maxlength='10' value='<? echo $data_fechamento ?>'>
			</TD>
		</TR>
		</TABLE>

		<table width="700" border="0" cellspacing="1" cellpadding="4" align="center">
		<tr height="20" bgcolor="#bbbbbb">
			<td nowrap><input type='checkbox' class='frm' name='marcar' value='tudo' title='Selecione ou desmarque todos' onClick='SelecionaTodos(this.form.ativo);' style='cursor: hand;'></td>
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font></b></td>
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif"><?if($sistema_lingua == 'ES') echo "Fecha Abertura";else echo "Data Abertura";?></font></b></td>
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "Usuário";else echo "Consumidor";?></font></b></td>
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "Producto";else echo "Produto";?></font></b></td>
<? if ($login_fabrica <> 2 AND $login_fabrica <> 1 AND $login_fabrica<>20){ ?>
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

			$os               = trim(pg_result ($res,$i,os));
			$sua_os           = trim(pg_result ($res,$i,sua_os));
			$admin            = trim(pg_result ($res,$i,admin));
			$tipo_atendimento = trim(pg_result ($res,$i,tipo_atendimento));

//			$leftpad = trim(pg_result ($res,$i,leftpad));
//if ($ip == '201.0.9.216') { echo $leftpad; }
			if (strlen($sua_os) == 0) $sua_os = $os;
			$descricao = pg_result ($res,$i,nome_comercial) ;
			if (strlen ($descricao) == 0) $descricao = pg_result ($res,$i,descricao) ;

			 $consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda));
			 $defeito_constatado = trim(pg_result ($res,$i,defeito_constatado));

			if ($login_fabrica == 1) {
				$sql = "SELECT os
						FROM   tbl_os
						WHERE  os = $os
						AND    motivo_atraso IS NULL
						AND    consumidor_revenda = 'C'
						AND    (data_abertura + INTERVAL '30 days')::date < current_date;";
				$resY = pg_exec($con, $sql);
				if (pg_numrows($resY) > 0) {
					$cor = "#FF0000";
					$flag_bloqueio = "t";
				}else{
					$flag_bloqueio = "";
				}

#				$resX = pg_exec($con,"SELECT to_char (current_date , 'YYYY-MM-DD')");
#				$data_atual = pg_result($resX,0,0);

				if (strlen($defeito_constatado) == 0) {
					$cor = "#FFCC66";
					$flag_bloqueio = "t";
				}elseif ($flag_bloqueio == "t" AND strlen($defeito_constatado) <> 0){
					$flag_bloqueio = "t";
				}else{
					$flag_bloqueio = "";
				}
			}

			if (strlen($linha_erro[$i]) > 0) $cor = "#99FFFF";

?>

		<tr  bgcolor="<? echo $cor ?>" <? if ($linha_erro == $i and strlen ($msg_erro) > 0 )?>>
			<input type='hidden' name='os_<? echo $i ?>' value='<? echo pg_result ($res,$i,os) ?>'>
			<td align="center"><? if (strlen($flag_bloqueio) == 0) { ?><input type="checkbox" class="frm" name="ativo_<?echo $i?>" id="ativo" value="t"><? } ?></td>
			<? //Alterado por Wellington 06/12/2006 a pedido de Luiz Antonio, posto Jundservice (Lenoxx) deve abrir os_item ao clicar na OS ?>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><a href='<? if ($cor == "#FFCC66" or ($login_fabrica==11 and $login_posto==14254)) echo "os_item"; else echo "os_press"; ?>.php?os=<? echo $os ?>' target='_blank'><? if ($login_fabrica == 1) echo $login_codigo_posto; echo $sua_os; ?></a></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo pg_result ($res,$i,data_abertura) ?></td>
			<td NOWRAP ><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo substr (pg_result ($res,$i,consumidor_nome),0,10) ?></td>
			<td NOWRAP><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo pg_result ($res,$i,serie) . " - " . substr ($descricao,0,20) ?></td>
<? if ($login_fabrica <> 2 AND $login_fabrica <> 1 AND $login_fabrica<>20){ ?>
			<?
			# Lorenzetti - Quando OS aberta pelo SAC para atendimento em Domicilio, obrigatorio NF de Devolucao
			if ($consumidor_revenda == 'R' OR (1==2 AND $login_fabrica == 19 AND strlen ($admin) > 0 AND $tipo_atendimento == 2) ){
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
		Não foi(ram) encontrada(s) OS(s) não finalizada(s).
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
