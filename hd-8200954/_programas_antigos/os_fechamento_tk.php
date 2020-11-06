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
		if($sistema_lingua == "ES") $msg_erro = "Digite la fecha de cierre";
		else                        $msg_erro = "Digite a data de fechamento.";
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
				if ($login_fabrica == 3 AND $ativo == 't'){
					$sql = "SELECT  status_os
							FROM    tbl_os_status
							WHERE   os = $os
							ORDER BY data DESC LIMIT 1";
					$res = @pg_exec($con,$sql);
					if (pg_numrows($res) > 0) {
						$os_intervencao_fabrica = trim(pg_result($res,0,status_os));
						if ($os_intervencao_fabrica == '62') {
							$erro .= "OS $os está com Intervenção da Assistência técnica da Fábrica. Não pode ser fechada.";
						}
						if ($os_intervencao_fabrica == '65') {
							$erro .= "OS $os está em reparo na assistência técnica da Fábrica. Não pode ser fechada.";
						}
						if ($os_intervencao_fabrica == '72') {
							$erro .= "OS $os está com intervenção do SAP. Não pode ser fechada.";
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

					if ($login_fabrica == 1) {
						$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
						$res = @pg_exec ($con,$sql);
						$erro = pg_errormessage($con);
						# esta alteracao foi necessaria devido ao chamado 1419
						# Na verdade o valida os item deve ser realizado quando digitar o item, mas
						# quando a Fabiola/Silvania questionou sobre OS com item que não constavam na
						# lista básica, o Tulio começou a validar os itens no fechamento tambem.
						# começou a causar problemas com o Type, e substituição de peças.
						if (strpos ($erro,"na lista b") > 0 and strpos ($erro,"m o type est") > 0) $erro = '';
						if (strpos ($erro,"Referência") > 0 and strpos ($erro,"mudou para") > 0) $erro = '';
					}
					if (strlen ($erro) == 0) {
						$res = pg_exec ($con,"BEGIN TRANSACTION");

						if (strlen ($erro) == 0) {
							$sql = "UPDATE  tbl_os SET
											data_fechamento   = $xdata_fechamento  ,
											nota_fiscal_saida = $xnota_fiscal_saida,
											data_nf_saida     = $xdata_nf_saida
									WHERE   tbl_os.os         = $os";
						if($ip=="201.42.111.45"){
echo "$sql<BR>";}
							$res  = @pg_exec ($con,$sql);
							$erro = pg_errormessage ($con);
						}
		
						if (strlen ($erro) == 0) {
							$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
							$res = @pg_exec ($con,$sql);
							$erro = pg_errormessage($con);
						}

						if (strlen ($erro) > 0) {
							echo $erro;
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
					}
					else{
						$msg_erro = $erro;
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
	//echo $msg_erro;
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
<br>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center" class='error'>
		<? 
		echo $msg_erro;
		?>
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
						tbl_produto.produto                                        ,
						tbl_produto.descricao                                      ,
						tbl_produto.nome_comercial                                 ,
						to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
						tbl_os.consumidor_nome                                     ,
						tbl_os.consumidor_revenda                                  ,
						tbl_os.defeito_constatado                                  ,
						tbl_os.admin                                               ,
						tbl_os.tipo_atendimento
				FROM	tbl_os
				JOIN    (SELECT distinct tbl_os.os FROM tbl_os  ";
		if($login_fabrica==20 and $listar=="todas"){
			$sql .= " JOIN tbl_os_produto on tbl_os.os = tbl_os_produto.os  ";
		}
		$sql .= " WHERE posto = $posto AND fabrica = $login_fabrica AND data_fechamento IS NULL
				) oss ON tbl_os.os = oss.os
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

		if (strlen($sua_os) > 0) $sql .= "AND (tbl_os.sua_os like '$sua_os%' OR tbl_os.sua_os LIKE '0$sua_os%' OR tbl_os.sua_os LIKE '00$sua_os%' OR tbl_os.sua_os LIKE '000$sua_os%' OR tbl_os.sua_os LIKE '0000$sua_os%' OR tbl_os.sua_os LIKE '00000$sua_os%' OR tbl_os.sua_os LIKE '000000$sua_os%' OR tbl_os.sua_os LIKE '0000000$sua_os%' OR tbl_os.sua_os LIKE '00000000$sua_os%') ";

		if($login_fabrica==20 and $listar=="todas"){
			$sql .= " AND tbl_os.mao_de_obra > 0 ";
		}
		//$sql .= "ORDER BY lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,0) DESC, lpad(tbl_os.os,20,0) DESC;";
		//$sql .= "ORDER BY lpad(tbl_os.sua_os,20,0) DESC, lpad(tbl_os.os,20,0) DESC LIMIT 50 ;";
		$sql .= "ORDER BY lpad(tbl_os.sua_os,20,0) DESC, lpad(tbl_os.os,20,0) DESC ;";

//echo $listar ; echo $sql; exit; 
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
		<?
		if($sistema_lingua == "ES") echo "Con la cierre de la OS usted habilitase al recibimiento de los valores";
		else                        echo "Com o fechamento da OS você se habilita ao recebimento dos valores <br> que serão pagos no próximo Extrato."
		?>
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
			<td nowrap>
			<? if($login_fabrica<>20){ ?>
			<input type='checkbox' class='frm' name='marcar' value='tudo' title='Selecione ou desmarque todos' onClick='SelecionaTodos(this.form.ativo);' style='cursor: hand;'>
			<? } ?>
			</td>
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">OS <?if($login_fabrica<>20){?>Fabricante<? } ?></font></b></td>
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif"><?if($sistema_lingua == 'ES') echo "Fecha Abertura";else echo "Data Abertura";?></font></b></td>
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "Usuário";else echo "Consumidor";?></font></b></td>
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua == 'ES') echo "Producto";else echo "Produto";?></font></b></td>
<? if ($login_fabrica <> 2 AND $login_fabrica <> 1 AND $login_fabrica<>20){ ?>
			<td nowrap><b><font size='2' face='Geneva, Arial, Helvetica, san-serif'>Nota Fiscal de Saída</font></b></td>
			<td nowrap><b><font size='2' face='Geneva, Arial, Helvetica, san-serif'>Data NF de Saída</font></b></td>
<? } ?>
			<?if($login_fabrica==20){?>
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua=='ES')echo "Valor de Piezas";else echo "Valor das Peças";?>
			</font></b></td>
			<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua=='ES')echo "Mano de Obra";else echo "Mão-de-Obra";?>
			</font></b></td>


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
			$produto          = trim(pg_result ($res,$i,produto));

//			$leftpad = trim(pg_result ($res,$i,leftpad));
//if ($ip == '201.0.9.216') { echo $leftpad; }
			if (strlen($sua_os) == 0) $sua_os = $os;
			$descricao = pg_result ($res,$i,nome_comercial) ;
			if (strlen ($descricao) == 0) $descricao = pg_result ($res,$i,descricao) ;

			 $consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda));
			 $defeito_constatado = trim(pg_result ($res,$i,defeito_constatado));

			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";
		
			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$descricao  = trim(@pg_result($res_idioma,0,descricao));
			}
			//--=== Tradução para outras linguas ================================================


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
<?
if ($login_fabrica == "20") {

    $pecas              = 0;
    $mao_de_obra        = 0;
    $tabela             = 0;
    $desconto           = 0;
    $desconto_acessorio = 0;

    $ysql = "SELECT mao_de_obra FROM tbl_produto_defeito_constatado WHERE produto = (SELECT produto FROM tbl_os WHERE os = $os) AND defeito_constatado = (SELECT defeito_constatado FROM tbl_os WHERE os = $os)";
    $yres = pg_exec ($con,$ysql);
    if (pg_numrows ($yres) == 1) {
        $mao_de_obra = pg_result ($yres,0,mao_de_obra);
    }

    $ysql = "SELECT tabela,desconto,desconto_acessorio FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
    $yres = pg_exec ($con,$ysql);

    if (pg_numrows ($yres) == 1) {

        $tabela             = pg_result ($yres,0,tabela)            ;
        $desconto           = pg_result ($yres,0,desconto)          ;
        $desconto_acessorio = pg_result ($yres,0,desconto_acessorio);

    }
    if (strlen ($desconto) == 0) $desconto = "0";

    if (strlen ($tabela) > 0) {

        $ysql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total
                FROM tbl_os
                JOIN tbl_os_produto USING (os)
                JOIN tbl_os_item    USING (os_produto)
                JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
                WHERE tbl_os.os = $os";
        $yres = pg_exec ($con,$ysql);

        if (pg_numrows ($yres) == 1) {
            $pecas = pg_result ($yres,0,0);
        }
    }else{
        $pecas = "0";
    }

    $valor_liquido = 0;

    if ($desconto > 0 and $pecas <> 0) {

        $ysql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
        $yres = pg_exec ($con,$ysql);
        if (pg_numrows ($res) == 1) {
            $produto = pg_result ($yres,0,0);
        }
        //echo 'peca'.$pecas;
        if( $produto == '20567' ){
            $desconto_acessorio = '0.2238';
            $valor_desconto = round ( (round ($pecas,2) * $desconto_acessorio ) ,2);

        }else{
            $valor_desconto = round ( (round ($pecas,2) * $desconto / 100) ,2);
        }

        $valor_liquido = $pecas - $valor_desconto ;

    }
    $acrescimo = 0;
    if($login_pais<>"BR"){
        $ysql = "select pecas,mao_de_obra  from tbl_os where os=$os";
        $yres = pg_exec ($con,$ysql);

        if (pg_numrows ($yres) == 1) {
            $valor_liquido = pg_result ($yres,0,pecas);
            $mao_de_obra   = pg_result ($yres,0,mao_de_obra);
        }
        $ysql = "select imposto_al  from tbl_posto_fabrica where posto=$login_posto and fabrica=$login_fabrica";
        $yres = pg_exec ($con,$ysql);

        if (pg_numrows ($yres) == 1) {
            $imposto_al   = pg_result ($yres,0,imposto_al);
            $imposto_al   = $imposto_al / 100;
            $acrescimo     = ($valor_liquido + $mao_de_obra) * $imposto_al;
        }
    }

    $total = $valor_liquido + $mao_de_obra + $acrescimo;

    $total          = number_format ($total,2,",",".")         ;
    $mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
    $acrescimo      = number_format ($acrescimo ,2,",",".")    ;
    $valor_desconto = number_format ($valor_desconto,2,",",".");
    $valor_liquido  = number_format ($valor_liquido ,2,",",".");

    echo "<td align='center'>" ;
    echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'>$valor_liquido</font>" ;
    echo "</td>";
    echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$mao_de_obra</font></td>";

}

?>
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
		<?
		if($sistema_lingua == "ES") echo "No fue(ran) encuentrada(s) OS(s) cerrada(s)";
		else                        echo "Não foi(ram) encontrada(s) OS(s) não finalizada(s).";
		?>
		
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
