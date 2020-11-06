<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

include 'funcoes.php';

if (strlen($_POST['callcenter']) > 0) $callcenter = trim($_POST['callcenter']);
if (strlen($_GET['callcenter']) > 0)  $callcenter = trim($_GET['callcenter']);
if (strlen($_POST['obs']) > 0)        $obs        = trim($_POST['obs']);
if (strlen($_POST['Observacao']) > 0) $gravar     = trim($_POST['Observacao']);

if(strlen($gravar) > 0){
	if(strlen($obs) > 0){
		$xobs = "Observação: ".$obs;

		$sql = "INSERT INTO tbl_providencia (
							callcenter   ,
							realizada_em  ,
							resposta_dada
					) VALUES (
							$callcenter       ,
							current_timestamp ,
							'$xobs'
					); ";
		$res = pg_exec($con,$sql);
	}else{
		$msg_erro = "Digite a observação.";
	}
}

/*================ LE BASE DE DADOS =========================*/
if (strlen ($callcenter) == 0) {

	header("Location: callcenter_cadastro_1.php");
	exit;

}elseif (strlen ($callcenter) > 0) {

	$sql = "SELECT	tbl_callcenter.callcenter                                     ,
					tbl_callcenter.serie                                          ,
					tbl_callcenter.revenda_nome                                   ,
					tbl_callcenter.natureza                                       ,
					tbl_callcenter.sua_os                                         ,
					to_char(tbl_callcenter.data_abertura,'DD/MM/YYYY') AS data_abertura     ,
					tbl_callcenter.reclamacao                                     ,
					tbl_callcenter.solucao                                        ,
					tbl_callcenter.nota_fiscal                                    ,
					to_char(tbl_callcenter.data_nf,'DD/MM/YYYY') AS data_nf       ,
					tbl_callcenter.cliente          AS consumidor_cliente         ,
					tbl_hd_chamado_extra.nome                AS consumidor_nome            ,
					tbl_hd_chamado_extra.cpf                 AS consumidor_cpf             ,
					tbl_hd_chamado_extra.endereco            AS consumidor_endereco        ,
					tbl_hd_chamado_extra.numero              AS consumidor_numero          ,
					tbl_hd_chamado_extra.complemento         AS consumidor_complemento     ,
					tbl_hd_chamado_extra.cep                 AS consumidor_cep             ,
					tbl_hd_chamado_extra.bairro              AS consumidor_bairro          ,
					tbl_hd_chamado_extra.nome                 AS consumidor_cidade          ,
					tbl_cidade.estado               AS consumidor_estado          ,
					tbl_hd_chamado_extra.rg                  AS consumidor_rg              ,
					tbl_hd_chamado_extra.fone                AS consumidor_fone            ,
					tbl_cliente_contato.email       AS consumidor_email           ,
					tbl_cliente_contato.celular     AS consumidor_celular         ,
					tbl_os.sua_os                   AS sua_os                     ,
					tbl_posto.nome                  AS posto_nome                 ,
					tbl_posto.fone                  AS posto_fone                 ,
					tbl_produto.descricao           AS produto_descricao          ,
					tbl_produto.linha                                             ,
					tbl_defeito_reclamado.descricao AS defeito_reclamado_descricao,
					tbl_admin.login                 AS atendente_nome             
			FROM	tbl_callcenter
			JOIN	tbl_hd_chamado_extra   USING(cliente)
			JOIN	tbl_cidade    on tbl_cidade.cidade = tbl_hd_chamado_extra.cidade
			LEFT JOIN tbl_cliente_contato USING(cliente)
			LEFT JOIN tbl_os      USING(os)
			LEFT JOIN tbl_posto   ON tbl_posto.posto = tbl_callcenter.posto
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_callcenter.produto
			LEFT JOIN tbl_defeito_reclamado ON tbl_callcenter.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
			LEFT JOIN tbl_admin             ON tbl_admin.admin = tbl_callcenter.admin
			WHERE	tbl_callcenter.callcenter = $callcenter
			AND		tbl_callcenter.fabrica    = $login_fabrica";
	$res = pg_exec ($con,$sql);

//echo $sql."<br>".pg_numrows($res); exit;

	if (pg_numrows($res) > 0) {
		$callcenter             = pg_result ($res,0,callcenter);
		$revenda_nome           = pg_result ($res,0,revenda_nome);
		$serie                  = pg_result ($res,0,serie);
		$natureza               = pg_result ($res,0,natureza);
		$consumidor_cliente     = pg_result ($res,0,consumidor_cliente);
		$consumidor_nome        = pg_result ($res,0,consumidor_nome);
		$consumidor_cpf         = pg_result ($res,0,consumidor_cpf);
		$consumidor_endereco    = pg_result ($res,0,consumidor_endereco);
		$consumidor_numero      = pg_result ($res,0,consumidor_numero);
		$consumidor_complemento = pg_result ($res,0,consumidor_complemento);
		$consumidor_cep         = pg_result ($res,0,consumidor_cep);
		$consumidor_bairro      = pg_result ($res,0,consumidor_bairro);
		$consumidor_cidade      = pg_result ($res,0,consumidor_cidade);
		$consumidor_estado      = pg_result ($res,0,consumidor_estado);
		$consumidor_rg          = pg_result ($res,0,consumidor_rg);
		$consumidor_fone        = pg_result ($res,0,consumidor_fone);
		$consumidor_email       = pg_result ($res,0,consumidor_email);
		$consumidor_celular     = pg_result ($res,0,consumidor_celular);
		$sua_os                 = pg_result ($res,0,sua_os);
		$posto_nome             = pg_result ($res,0,posto_nome);
		$posto_fone             = pg_result ($res,0,posto_fone);
		$produto_descricao      = pg_result ($res,0,produto_descricao);
		$defeito_reclamado      = pg_result ($res,0,defeito_reclamado_descricao);
		$reclamacao             = pg_result ($res,0,reclamacao);
		$solucao                = pg_result ($res,0,solucao);
		$atendente_nome         = pg_result ($res,0,atendente_nome);
		$data_abertura          = pg_result ($res,0,data_abertura);
		$nota_fiscal            = pg_result ($res,0,nota_fiscal);
		$data_nf                = pg_result ($res,0,data_nf);
	}
}


$title = "Atendimento Call-Center"; 
$layout_menu = 'callcenter';

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

</style>

<p style='font-size: 14px; color: #FF0000'><? if(strlen($msg_erro) > 0) echo "<b>". $msg_erro ."</b>";  ?></p>
<? $msg_erro = ''; ?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="left">
		<TABLE width=600 border="0" cellpadding="3" cellspacing="3">
		<FORM METHOD=POST name='frm_callcenter' ACTION="<? echo $PHP_SELF; ?>">
		<INPUT TYPE="hidden" name='callcenter' value='<? echo $callcenter; ?>'>
			<TR class='menu_top'>
				<TD>Número do atendimento</TD>
				<TD>Atendente</TD>
				<TD>Natureza do chamado</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $callcenter;?> &nbsp;</TD>
				<TD><? echo ucfirst($atendente_nome); ?></TD>
				<TD><? echo $natureza;?> &nbsp;</TD>
			</TR>
		</TABLE>
<hr>
		<TABLE width=600 border="0" cellpadding="3" cellspacing="3">
			<TR class='menu_top'>
				<TD>Nome Cliente</TD>
				<TD>CPF/CNPJ Cliente</TD>
			</TR>
			<TR class='table_line'>
				<input type='hidden' name='consumidor_cliente'>
				<TD><? echo $consumidor_nome; ?> &nbsp;</TD>
				<TD><? echo $consumidor_cpf; ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD>Endereco</TD>
				<TD>Número</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $consumidor_endereco; ?> &nbsp;</TD>
				<TD><? echo $consumidor_numero; ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD>Complemento</TD>
				<TD>CEP</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $consumidor_complemento; ?> &nbsp;</TD>
				<TD><? echo $consumidor_cep; ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD>Bairro</TD>
				<TD>Cidade / Estado</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $consumidor_bairro; ?> &nbsp;</TD>
				<TD><? echo $consumidor_cidade; ?> - <? echo $consumidor_estado; ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD>RG/IE</TD>
				<TD>Fone</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $consumidor_rg; ?> &nbsp;</TD>
				<TD><? echo $consumidor_fone; ?> &nbsp;</TD>
			</TR>
			<!--<TR class='menu_top'>
				<TD>e-Mail</TD>
				<TD>Celular</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $consumidor_email; ?> &nbsp;</TD>
				<TD><? echo $consumidor_celular; ?> &nbsp;</TD>
			</TR>-->
		</TABLE>
<hr>
		<TABLE width=600 border="0" cellpadding="3" cellspacing="3">
			<TR class='menu_top'>
				<TD colspan="2">Número da OS</TD>
				<TD colspan="2">Data abertura</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan="2"><? echo $sua_os; ?> &nbsp;</TD>
				<TD colspan="2"><? echo $data_abertura; ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD colspan="3">Nome Posto</TD>
				<TD>Telefone</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan="3"><? echo $posto_nome; ?> &nbsp;</TD>
				<TD><? echo $posto_fone; ?> &nbsp;</TD>
					<input type='hidden' name = 'posto_codigo'>
			</TR>
			<TR class='menu_top'>
				<TD>Produto</TD>
				<TD>Série</TD>
				<TD>Nota fiscal</TD>
				<TD>Data compra</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $produto_descricao; ?> &nbsp;</TD>
				<TD><? echo $serie; ?> &nbsp;</TD>
				<TD><? echo $nota_fiscal; ?> &nbsp;</TD>
				<TD><? echo $data_nf; ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD colspan='4'>Revenda</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='4'><? echo $revenda_nome; ?> &nbsp;</TD>
			</TR>
		</table>
<hr>
		<TABLE width=600 border="0" cellpadding="3" cellspacing="3">
			<TR class='menu_top'>
				<TD colspan='2'>Ocorrência / Reclamação</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='2'><? echo $defeito_reclamado; ?> &nbsp;</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='2'><? echo nl2br($reclamacao); ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD colspan='2'>Solução</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='2'><? echo nl2br($solucao); ?> &nbsp;</TD>
			</TR>
		</table>
<hr>
<?

$sql = "SELECT TO_CHAR(tbl_providencia.data_gravacao, 'DD/MM/YYYY') AS data_gravacao ,
				tbl_providencia.enderecada              ,
				tbl_providencia.resposta_dada           ,
				tbl_providencia.perguntar               ,
				tbl_providencia.solucionado             ,
				tbl_providencia.ja_retirou_produto      ,
				TO_CHAR(tbl_providencia.realizar_em, 'DD/MM/YYYY') AS realizar_em 
			FROM tbl_providencia
			JOIN tbl_callcenter using(callcenter)
			WHERE tbl_providencia.callcenter = $callcenter
			AND   tbl_callcenter.fabrica = $login_fabrica  ORDER BY tbl_providencia.providencia; ";
$res = pg_exec($con,$sql);
//			echo "$sql";
if(pg_numrows($res) > 0){
	echo "<br>";
	echo "<table width='500' align='center' border='0' cellspacing='1' cellpadding='0'>";
	echo "<tr class='menu_top' style='font-size: 14px'>";
		echo "<td align='center' colspan='2'>Histórico</td>";
	echo "</tr>";
	echo "</table>";
	$j=1;
	for ($i=0; $i<pg_numrows($res);$i++){
		$kdata_gravacao      = pg_result($res,$i,data_gravacao);
		$kenderecada         = pg_result($res,$i,enderecada);
		$kresposta_dada      = pg_result($res,$i,resposta_dada);
		$kperguntar          = pg_result($res,$i,perguntar);
		$krealizar_em        = pg_result($res,$i,realizar_em);
		$ksolucionado        = pg_result($res,$i,solucionado);
		$kja_retirou_produto = pg_result($res,$i,ja_retirou_produto);
		
		echo "<table width='500' align='center' border='0' cellspacing='1' cellpadding='0'>";
		echo "<tr class='menu_top'>";
			echo "<td colspan='2' style='font-size:12px' align='left'>$j ª Interação</td>";
		echo "</tr>";
		echo "<tr style='font-family: verdana; font-size: 11px;'>";
			echo "<td width='110' bgcolor='#d9e2ef' align='right' nowrap><b>Data Interação</b>&nbsp;</td>";
			echo "<td>&nbsp;$kdata_gravacao</td>";
		echo "</tr>";
		echo "<tr style='font-family: verdana; font-size: 11px;'>";
			echo "<td width='110' bgcolor='#d9e2ef' align='right' nowrap><b>Pergunta</b>&nbsp;</td>";
			echo "<td >&nbsp;$kperguntar</td>";
		echo "</tr>";
		echo "<tr style='font-family: verdana; font-size: 11px;'>";
			echo "<td width='110' bgcolor='#d9e2ef' align='right' nowrap><b>Resposta dada</b>&nbsp;</td>";
			echo "<td>&nbsp;$kresposta_dada</td>";
		echo "</tr>";
		echo "<tr style='font-family: verdana; font-size: 11px;'>";
			echo "<td width='110' bgcolor='#d9e2ef' align='right' nowrap><b>Encaminhada</b>&nbsp;</td>";
			echo "<td>&nbsp;$kenderecada</td>";
		echo "</tr>";
		echo "<tr style='font-family: verdana; font-size: 11px;'>";
			echo "<td width='110' bgcolor='#d9e2ef' align='right' nowrap><b>Realizar em</b>&nbsp;</td>";
			echo "<td>&nbsp;$krealizar_em</td>";
		echo "</tr>";
		echo "<tr style='font-family: verdana; font-size: 11px;'>";
			echo "<td width='110' bgcolor='#d9e2ef' align='right' nowrap><b>Solucionado</b>&nbsp;</td>";
			if($ksolucionado == 'f') echo "<td>&nbsp;Não</td>";
			else echo "<td>&nbsp;Sim</td>";
		echo "</tr>";
		echo "<tr style='font-family: verdana; font-size: 11px;'>";
			echo "<td width='110' bgcolor='#d9e2ef' align='right' nowrap><b>Retirou Produto</b>&nbsp;</td>";
			if($kja_retirou_produto == 'f') echo "<td>&nbsp;Não</td>";
			else echo "<td>&nbsp;Sim</td>";
		echo "</tr>";

		$j++;
//					echo "<br>Data: $data_gravacao - End.: $enderecada - Resp.: $resposta_dada";
		echo "</table><br>";
	}
}
?>
		<br><br>
		<TABLE width=600 border="0" cellpadding="3" cellspacing="3">
			<TR>
				<TD colspan='2' align='center'>
					<input type="hidden" name="btn_acao" value="">
<!--					<img src='imagens/btn_voltar.gif' style="cursor:pointer" onclick="javascript: history.back();" ALT="Voltar" border='0'> -->
					<img src='imagens/btn_voltar.gif' style="cursor:pointer" onclick="javascript: window.location='callcenter_cadastro_3.php?callcenter=<? echo $callcenter; ?>'" ALT="Voltar" border='0'>
				</TD>
				<TD colspan='2' align='center'>
					<input type="hidden" name="btn_acao" value="">
					<img src='imagens/btn_continuar.gif' style="cursor:pointer" onclick="javascript: window.location='callcenter_cadastro_1.php'" ALT="Cadastrar novo chamado" border='0'>
				</TD>
			</TR>
		</TABLE>
		</FORM>
	</td>
	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>

<p>

<? include "rodape.php"; ?>