<?php
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include_once "autentica_admin.php";
include_once "funcoes.php";

####### HD 24472 - Francisco Ambrozio #######################################################
#		Relatório de Ordens de Serviço Conferidas por Linha									#
#																							#
#		Campos do Relatório (colunas):														#
#			- Código do Posto Autorizado													#
#			- Posto Autorizado																#
#			- Linha R$ 5,00 : Qtde de OSs enviadas (conferidas)								#
#			- Linha R$ 10,00: Qtde de OSs enviadas (conferidas)								#
#			- ... (todas as linhas de conferência individualmente)							#
#			- DT. CONF.																		#
#			- NF																			#
#			- Data da NF																	#
#			- Valor da NF																	#
#			- Valor a pagar																	#
#			- Caixa																			#
#			- OBS Britânia																	#
#			- OBS Posto																		#
#			- Previsão de PGTO																#
#																							#
#		Filtros:																			#
#			- Datas início e fim - data de conferência										#
#			- Posto (código e descrição)													#
#			- NF																			#
#			- Caixa																			#
#			- Datas início e fim - Previsão de PGTO											#
#																							#
####### 28/07/2008 ##########################################################################

$msg_erro = "";

if (strlen($btn_acao) > 0) {
	$data_incf = $_POST["data_incf"];
	$data_flcf = $_POST["data_flcf"];
	$nf        = $_POST["nf"];
	if ((strlen($data_incf) == 0) and (strlen($nf) == 0)){
		$msg_erro = "Informe o Período para realizar a Pesquisa ou o Número da NF.";
	}

	 if(strlen($msg_erro)==0){
			if ((strlen($data_flcf) == 0) and (strlen($nf) == 0)){
				$msg_erro = "Informe a data de conferência final para realizar a pesquisa ou informe o número da nota fiscal.";
			}
	 }

	 if(strlen($msg_erro)==0){
			if (((strlen($data_incf) == 0) and (strlen($data_flcf) == 0)) and (strlen($nf) == 0)){
				$msg_erro = "É obrigatório informar um período nas datas de conferência para realizar a pesquisa ou informar o número da nota fiscal!";
			}
	 }

	 if(strlen($msg_erro)==0){
			if ((strlen($data_incf) == 0) and (strlen($nf) > 0)){
				$msg_erro = "Informe a data de conferência inicial para realizar a pesquisa. Se desejar pesquisar apenas por Nota Fiscal deixe os campos Data de Conferência em branco.";
			}
	 }

	 if(strlen($msg_erro)==0){
			if ((strlen($data_flcf) == 0) and (strlen($nf) > 0)){
				$msg_erro = "Informe a data de conferência final para realizar. Se desejar pesquisar apenas por Nota Fiscal deixe os campos Data de Conferência em branco.";
			}
	 }

	 if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $data_incf);
        if(!checkdate($mi,$di,$yi))
            $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $data_flcf);
        if(!checkdate($mf,$df,$yf))
            $msg_erro = "Data Inválida";
    }

	if(strlen($msg_erro)==0){
		$aux_data_inicial = "$yi-$mi-$di";
		$aux_data_final = "$yf-$mf-$df";
	}
    if(strlen($msg_erro)==0){
        if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
			$msg_erro = "Data Inválida.";
		}
    }

	if (((strlen($data_incf) == 0) and (strlen($data_flcf) == 0)) and (strlen($nf) > 0)){
		$msg_erro = "";
	}
}

$layout_menu = "financeiro";
$title = "RELATÓRIO DE ORDENS DE SERVIÇO CONFERIDAS POR LINHA";

include_once "cabecalho.php";

?>

<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco{
	padding: 0 0 0 180px;
}
</style>

<? include_once "javascript_pesquisas.php";
   include_once "../js/js_css.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_incf').datepick({startDate:'01/01/2000'});
		$('#data_flcf').datepick({startDate:'01/01/2000'});
		$("#data_incf").mask("99/99/9999");
		$("#data_flcf").mask("99/99/9999");
		$('#data_inpp').datepick({startDate:'01/01/2000'});
		$('#data_flpp').datepick({startDate:'01/01/2000'});
		$("#data_inpp").mask("99/99/9999");
		$("#data_flpp").mask("99/99/9999");
	});
</script>

<script language="JavaScript">
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else{
			alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}
</script>

<? if (strlen($msg_erro) > 0) { ?>
<br>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class="msg_erro">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<? } ?>

<FORM name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>

<input type="hidden" name="btn_acao">

	<TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2" id='Formulario' class='formulario'>
		<TR class='titulo_tabela'><TD colspan='4'>Parâmetros de Pesquisa</TD></TR>
		<TBODY>

		<TR>
			<TH class='subtitulo' colspan='2'>Data de Conferência</TH>
		</TR>

		<TR>
			<TD class='espaco' width='130'>
					Data Inicial <br />
					<INPUT class="frm" size="12" maxlength="10" TYPE="text" NAME="data_incf" id="data_incf" value="<? if (strlen($data_incf) > 0) echo $data_incf; ?>" >
			</TD>

			<TD>
					Data Final <br />
					<INPUT class="frm" size="12" maxlength="10" TYPE="text" NAME="data_flcf" id="data_flcf" value="<? if (strlen($data_flcf) > 0) echo $data_flcf; ?>" >
			</TD>
		</TR>

		<TR>
			<TH>&nbsp;</TH>
		</TR>

		<TR>
			<TD class='espaco'>
						Cód. Posto <br />
						<input class="frm" type="text" name="codigo_posto" size="10" value="<? echo $codigo_posto ?>">&nbsp;
						<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')">
			</TD>

			<TD>
						Nome Posto <br />
						<input class="frm" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>">&nbsp;
						<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;">
			</TD>
		</TR>

		<TR>
			<TH>&nbsp;</TH>
		</TR>

		<TR>
			<TD class='espaco'>
						Nota Fiscal <br />
						<input class="frm" size="12" type='text' name='nf' value='<? echo $nf ?>' size='28' maxlength='20'>
			</TD>

			<TD>
					Caixa <br />
					<input class="frm" size="12" type='text' name='caixa' value='<? echo $caixa ?>' size='28' maxlength='20'>
			</TD>
		</TR>

		<TR>
			<TH>&nbsp;</TH>
		</TR>

		<TR>
			<TH class='subtitulo' colspan='2'>Previsão de Pagamento</TH>
		</TR>

		<TR>
			<TD class='espaco'>
					Data Inicial <br />
					<INPUT class="frm" size="12" maxlength="10" TYPE="text" NAME="data_inpp" id="data_inpp" value="<? if (strlen($data_inpp) > 0) echo $data_inpp; ?>" >
			</TD>

			<TD>
					Data Final <br />
					<INPUT class="frm" size="12" maxlength="10" TYPE="text" NAME="data_flpp" id="data_flpp" value="<? if (strlen($data_flpp) > 0) echo $data_flpp; ?>" >
			</TD>
		</TR>

		<TR>
			<TH>&nbsp;</TH>
		</TR>

		</TBODY>
		<TFOOT>
		<TR>
			<TD colspan="4" align='center'><input type="button" value="Pesquisar" onclick="javascript: document.frm_relatorio.btn_acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Clique AQUI para pesquisar">
			</TD>
		</TR>
		</TFOOT>
	</TABLE>
</FORM>

<?
if (strlen($btn_acao) > 0 and strlen($msg_erro) == 0) {
	# Quebra a data em dia, mês e ano
	$ano_dicf = substr($data_incf, -4, 4);
	$mes_dicf = substr($data_incf, -7, 2);
	$dia_dicf = substr($data_incf, -10, 2);
	# Formata a data para AAAA - MM - DD
	$data_inicialcf = date("Y-m-d", mktime(0, 0, 0, $mes_dicf, $dia_dicf, $ano_dicf));
	$ano_dfcf = substr($data_flcf, -4, 4);
	$mes_dfcf = substr($data_flcf, -7, 2);
	$dia_dfcf = substr($data_flcf, -10, 2);
	$data_finalcf = date("Y-m-d", mktime(0, 0, 0, $mes_dfcf, $dia_dfcf, $ano_dfcf));

	$ano_dipp = substr($data_inpp, -4, 4);
	$mes_dipp = substr($data_inpp, -7, 2);
	$dia_dipp = substr($data_inpp, -10, 2);
	$data_inicialpp = date("Y-m-d", mktime(0, 0, 0, $mes_dipp, $dia_dipp, $ano_dipp));
	$ano_dfpp = substr($data_flpp, -4, 4);
	$mes_dfpp = substr($data_flpp, -7, 2);
	$dia_dfpp = substr($data_flpp, -10, 2);
	$data_finalpp = date("Y-m-d", mktime(0, 0, 0, $mes_dfpp, $dia_dfpp, $ano_dfpp));

	if (strlen($codigo_posto) > 0){
		$sqlposto = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'";
		$resposto = pg_exec($con,$sqlposto);
		$postoid  = pg_result ($resposto,0,0);
		$sqlcondposto = "AND tbl_extrato.posto = $postoid";
	}

	if ((strlen($data_inpp) > 0) and (strlen($data_flpp) > 0)){
		$sqlcondpgto = "AND tbl_extrato_conferencia.previsao_pagamento BETWEEN '$data_inicialpp 00:00:00' and '$data_finalpp 23:59:59' ";
	}

	if ((strlen($nf) > 0) and ((strlen($data_incf) == 0) and (strlen($data_flcf) == 0))){
		$sqlcondnf = "AND tbl_extrato_conferencia.nota_fiscal = '$nf'";
	}

	if ((strlen($nf) == 0) and ((strlen($data_incf) > 0) and (strlen($data_flcf) > 0))){
		$sqlcondnf = "AND tbl_extrato_conferencia.data_conferencia BETWEEN '$data_inicialcf 00:00:00' and '$data_finalcf 23:59:59' ";
	}

	if ((strlen($nf) > 0) and ((strlen($data_incf) > 0) and (strlen($data_flcf) > 0))){
		$sqlcondnf  = "AND tbl_extrato_conferencia.data_conferencia BETWEEN '$data_inicialcf 00:00:00' and '$data_finalcf 23:59:59' ";
		$sqlcondnf .= "AND tbl_extrato_conferencia.nota_fiscal = '$nf' ";
	}

	if (strlen($caixa) > 0){
		$sqlcondcaixa = "AND tbl_extrato_conferencia.caixa = '$caixa'";
	}

	$sql = "SELECT
					tbl_extrato_conferencia.extrato_conferencia											,
					tbl_extrato_conferencia.extrato														,
					to_char (tbl_extrato_conferencia.data_conferencia, 'dd/mm/yyyy') AS dat_conferencia	,
					tbl_extrato_conferencia.nota_fiscal													,
					to_char (tbl_extrato_conferencia.data_nf, 'dd/mm/yyyy') AS dat_nf					,
					tbl_extrato_conferencia.valor_nf													,
					tbl_extrato_conferencia.caixa														,
					tbl_extrato_conferencia.obs_fabricante												,
					tbl_extrato_conferencia.obs_posto													,
					tbl_extrato_conferencia.valor_nf_a_pagar AS valor_pagar								,
					to_char (tbl_extrato_conferencia.previsao_pagamento, 'dd/mm/yyyy') AS previsao_pgto	,
					tbl_extrato_conferencia_item.linha													,
					tbl_extrato_conferencia_item.extrato_conferencia_item								,
					tbl_extrato_conferencia_item.qtde_conferida											,
					tbl_extrato_conferencia_item.mao_de_obra_unitario						as unitario,
					tbl_extrato.posto																	,
					to_char (tbl_extrato.data_geracao, 'dd/mm/yyyy') AS data_geracao					,
					tbl_linha.nome AS linha_nome														,
					tbl_posto_fabrica.codigo_posto														,
					tbl_posto.nome AS posto_nome														,
					tbl_admin.nome_completo AS admin_nome
				INTO TEMP TABLE tmp_rel_os_conf_$login_admin
				FROM tbl_extrato_conferencia
				JOIN tbl_extrato        USING(extrato)
				JOIN tbl_posto          USING(posto)
				LEFT JOIN tbl_admin  ON tbl_admin.admin = tbl_extrato_conferencia.admin
				LEFT JOIN tbl_extrato_conferencia_item ON tbl_extrato_conferencia.extrato_conferencia = tbl_extrato_conferencia_item.extrato_conferencia
				LEFT JOIN tbl_linha ON tbl_extrato_conferencia_item.linha = tbl_linha.linha
				LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_extrato.fabrica = 3
				AND   tbl_extrato_conferencia.cancelada IS NOT TRUE
				$sqlcondposto
				$sqlcondpgto
				$sqlcondnf
				$sqlcondcaixa;

			SELECT extrato									,
						dat_conferencia						,
						nota_fiscal							,
						dat_nf								,
						valor_nf							,
						caixa								,
						obs_fabricante						,
						obs_posto							,
						previsao_pgto						,
						linha								,
						qtde_conferida	as conferidas		,
						linha_nome							,
						unitario							,
						codigo_posto						,
						valor_pagar							,
						posto_nome							,
						admin_nome							,
						data_geracao                        ,
						(qtde_conferida * unitario) as valor_total_linha
				FROM tmp_rel_os_conf_$login_admin
				ORDER BY posto_nome, nota_fiscal, dat_nf";
#echo nl2br($sql);
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0){
		$arquivo_nome     = "relatorio-os-conferida-linha-$login_fabrica.$login_admin.xls";
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		$fp = fopen ($arquivo_completo_tmp,"w");

		fputs ($fp,"<html>");
		fputs ($fp,"<body>");

		echo "<p id='id_download' style='display:none'><button onclick=\"window.location='xls/$arquivo_nome'\">Download em Excel</button></p>";

		/* hd 44300 */
		$sqlx = "SELECT linha, linha_nome,
						sum(qtde_conferida) as qtde_conferida,
						sum(qtde_conferida * unitario) as valor_pagar
				FROM tmp_rel_os_conf_$login_admin
				GROUP BY linha, linha_nome
				ORDER BY linha_nome";
		$resx = pg_exec($con,$sqlx);

		if (pg_numrows($resx) >0) {
			$conteudo .="<BR>";
			$conteudo .="<center><div style='width:98%;'><table  align='center' width='700' border='0' class='tabela' cellpadding='0' cellspacing='1'>";
			$conteudo .="<thead>";
			$conteudo .="<tr class='titulo_coluna'>";
			$conteudo .="<td nowrap align='left'><b>Linha</b></a></td>";
			$conteudo .="<td nowrap align='right'><b>Qtde</b></a></td>";
			$conteudo .="<td nowrap align='right'><b>Valor a pagar por linha</b></a></td>";
			$conteudo .="</tr>";
			$conteudo .="</thead>";
			$conteudo .="<tbody>";

			for ($x = 0 ; $x < pg_numrows($resx) ; $x++){
				$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";

				$linha_nomex       = trim(pg_result($resx,$x,linha_nome));
				$qtde_conferidax   = trim(pg_result($resx,$x,qtde_conferida));
				$valor_pagarx      = trim(pg_result($resx,$x,valor_pagar));

				$valor_pagarx = number_format($valor_pagarx,2,",",".");

				$conteudo .="<tr bgcolor='$cor'>";
				$conteudo .="<td nowrap align='left'>$linha_nomex</td>";
				$conteudo .="<td nowrap align='right'>$qtde_conferidax</td>";
				$conteudo .="<td nowrap align='right'>$valor_pagarx</td>";
				$conteudo .="</tr>";
			}
			$conteudo .="</tbody>";
			$conteudo .="</center></div></table>";
		}

		$conteudo .="<br>";
		$conteudo .="<table align='center' width='700' border='0' class='tabela' cellpadding='0' cellspacing='1' class='tabela'>";
		$conteudo .="<thead>";
		$conteudo .="<tr class='titulo_coluna' >";# bgcolor='#D9E2EF' fontcolor='#FFFFFF'>";
		$conteudo .="<td nowrap align='center'><b>Cód. Posto</b></a></td>";
		$conteudo .="<td nowrap align='left'><b>&nbsp;Nome do Posto</b></a></td>";
		$conteudo .="<td nowrap align='left'><b>Linha</b></a></td>";
		$conteudo .="<td nowrap align='center'><b>M. O. Unit.</b></a></td>";
		$conteudo .="<td nowrap align='center'><b>OSs Conf.</b></a></td>";
		$conteudo .="<td nowrap align='center'><b>Data Conf.</b></a></td>";
		$conteudo .="<td nowrap align='center'><b>Nota Fiscal</b></a></td>";
		$conteudo .="<td nowrap align='center'><b>Data NF</b></a></td>";
		$conteudo .="<td nowrap align='center'><b>Valor NF</b></a></td>";
		$conteudo .="<td nowrap align='center'><b>Valor Total Linha</b></a></td>";
		$conteudo .="<td nowrap align='center'><b>Valor a Pagar</b></a></td>";
		$conteudo .="<td nowrap align='center'><b>Caixa</b></a></td>";
		$conteudo .="<td nowrap align='center'><b>Obs Britânia</b></a></td>";
		$conteudo .="<td nowrap align='center'><b>Obs Posto</b></a></td>";
		$conteudo .="<td nowrap align='center'><b>Previsão Pgto.</b></a></td>";
		$conteudo .="<td nowrap align='center'><b>Data Extr.</b></a></td>";
		$conteudo .="<td nowrap align='center'><b>Admin</b></a></td>";
		$conteudo .="</tr>";
		$conteudo .="</thead>";
		$conteudo .="<tbody>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++){
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
			$posto_nome         = trim(pg_result($res,$i,posto_nome));
			$linha_nome         = trim(pg_result($res,$i,linha_nome));
			$unitario           = trim(pg_result($res,$i,unitario));
			$conferidas         = trim(pg_result($res,$i,conferidas));
			$data_conferencia   = trim(pg_result($res,$i,dat_conferencia));
			$nota_fiscal        = trim(pg_result($res,$i,nota_fiscal));
			$data_nf            = trim(pg_result($res,$i,dat_nf));
			$valor_nf           = trim(pg_result($res,$i,valor_nf));

			//hd 44300
			$valor_total_linha  = trim(pg_result($res,$i,valor_total_linha));

			$valor_pagar        = trim(pg_result($res,$i,valor_pagar));
			$caixa              = trim(pg_result($res,$i,caixa));
			$obs_fabricante     = trim(pg_result($res,$i,obs_fabricante));
			$obs_posto          = trim(pg_result($res,$i,obs_posto));
			$previsao_pgto      = trim(pg_result($res,$i,previsao_pgto));
			$admin_nome         = trim(pg_result($res,$i,admin_nome));
			$data_geracao       = trim(pg_result($res,$i,data_geracao));

			$unitario = number_format($unitario,2,",",".");
			$valor_nf = number_format($valor_nf,2,",",".");
			$valor_pagar = number_format($valor_pagar,2,",",".");
			$valor_total_linha = number_format($valor_total_linha,2,",",".");

			$conteudo .="<tr >";
			$conteudo .="<td nowrap bgcolor=$cor align='center'>$codigo_posto</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='left'>$posto_nome</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='left'>$linha_nome</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='right'>$unitario</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='center'>$conferidas</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='center'>$data_conferencia</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='center'>$nota_fiscal</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='center'>$data_nf</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='right'>$valor_nf</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='right'>$valor_total_linha</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='right'>$valor_pagar</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='center'>$caixa</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='left'>$obs_fabricante</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='left'>$obs_posto</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='center'>$previsao_pgto</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='center'>$data_geracao</td>";
			$conteudo .="<td nowrap bgcolor=$cor align='center'>$admin_nome</td>";
			$conteudo .="</tr>";
		}
		$conteudo .="</tbody>";
		$conteudo .="</table>";
		echo $conteudo;

		fputs ($fp,$conteudo);


		fputs ($fp,"</body>");
		fputs ($fp,"</html>");

		fclose ($fp);
		flush();
		echo ` cp $arquivo_completo_tmp $path `;

		echo "<script language='javascript'>";
		echo "document.getElementById('id_download').style.display='block';";
		echo "</script>";
		echo "<br>";

		flush();

//		echo "<br>";
	} else {
		echo "<br>";
		echo "<h6>Nenhum resultado obtido segundo os parâmetros fornecidos.</h6>";
		echo "<br>";
	}

}

include_once "rodape.php";
?>