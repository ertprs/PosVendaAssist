<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
else                            $acao = strtoupper($_GET["acao"]);

if(strlen($_POST["codigo_posto"])>0) $codigo_posto = trim($_POST["codigo_posto"]);
else                                 $codigo_posto = trim($_GET["codigo_posto"]);

include "gera_relatorio_pararelo_include.php";

$title = "Relatório de Indicadores de Postos Autorizados";
include "cabecalho.php";
?>

<SCRIPT LANGUAGE="JavaScript">
<!--
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
}
//-->
</SCRIPT>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Subtitulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #394D7B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
</style>

	<BR>
	<FORM METHOD="POST" NAME="frm_relatorio" ACTION="<?php echo $PHP_SELF; ?>">
		<TABLE WIDTH='450' BORDER='0' CELLPADDING='0' CELLSPACING='0' ALIGN='CENTER'>
			<tr class="Titulo">
				<td><B>Posto</B></td>
				<td><B>Razão Social</B></td>
			</tr>
			<tr bgcolor="#D9E2EF">
				<td><input class="frm" type="text" name="codigo_posto" size="10" value="<?php echo $codigo_posto ?>">&nbsp;<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></td>
				<td><input class="frm" type="text" name="posto_nome" size="30" value="<?php echo $posto_nome ?>">&nbsp;<img src='imagens/btn_lupa.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"><br></td>
			</tr>
			<tr class="Conteudo" bgcolor="#D9E2EF">
				<td colspan="4">&nbsp;</td>
			</tr>
			<tr class="Conteudo" bgcolor="#D9E2EF">
				<td colspan="4">
				<INPUT TYPE="hidden" NAME="acao" ID="acao" VALUE="">
				<img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Clique AQUI para pesquisar"></td>
			</tr>
		</TABLE>
	</FORM>

<?php

if($acao=="PESQUISAR"){

	if(strlen($codigo_posto)>0){
		$sql = "
		SELECT posto
		FROM tbl_posto_fabrica
		WHERE codigo_posto = '$codigo_posto'
		AND fabrica = $login_fabrica
		";
		$res = pg_exec($con, $sql);

		if(pg_numrows($res)>0) $posto = pg_result($res,0,posto);

		if(strlen($posto)>0){
			$cond_posto      = " AND tbl_posto_media_atendimento.posto = $posto ";
		}
	}

	$sql = "
	SELECT MAX(data_geracao) FROM tbl_posto_media_atendimento WHERE fabrica=$login_fabrica
	";
	$res = pg_exec($con, $sql);
	$data_geracao = pg_result($res, 0, 0);


	//INICIO DO XLS
	$data_xls = date('dmy');
	echo `rm /tmp/assist/relatorio-indicadores-pa-$login_fabrica.xls`;
	$fp = fopen ("/tmp/assist/relatorio-indicadores-pa-$login_fabrica.html","w");
	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>INDICADORES DE POSTOS AUTORIZADOS - $data_xls");
	fputs ($fp,"</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");
	$cab .= "<TABLE WIDTH='100%' BORDER='0' CELLPADDING='2' CELLSPACING='2' ALIGN='CENTER'>";

	$cab .= "<TR class='Titulo'>";
		$cab .= "<TD>Código do Posto</TD>";
		$cab .= "<TD>Descrição do Posto</TD>";
		$cab .= "<TD>Qtde OSs Finalizadas<br>3 extratos</TD>";
		$cab .= "<TD>% de OSs finalizadas abaixo de 30 dias<br>3 extratos</TD>";
		$cab .= "<TD>Prazo Médio de Finalização<br>3 extratos</TD>";
		$cab .= "<TD>Quantidade de OSs abertas no posto</TD>";
		$cab .= "<TD>Quantidade de OSs abertas ha mais de 90 dias</TD>";
		$cab .= "<TD>Média das datas das OSs abertas no Posto</TD>";
		$cab .= "<TD>Quantidade de OSs reincidentes 3 extratos</TD>";
		$cab .= "<TD>% de reincidências 3 extratos</TD>";
		$cab .= "<TD>Quantidade de OSs com pedido em até 5 dias</TD>";
		$cab .= "<TD>Quantidade de OSs com pedido</TD>";
		$cab .= "<TD>% de OSs com pedidos em até 5 dias</TD>";
		$cab .= "<TD>Peças por OS<br>3&nbsp;extratos</TD>";
		$cab .= "<TD>Nota</TD>";
		$cab .= "<TD>Rank</TD>";
	$cab .= "</TR>";

	$sql = "
	SELECT
	tbl_posto_media_atendimento.posto,
	tbl_posto_fabrica.codigo_posto,
	tbl_posto.nome,
	tbl_posto_media_atendimento.qtde_extrato,
	tbl_posto_media_atendimento.qtde_finalizadas_30,
	tbl_posto_media_atendimento.qtde_media_extrato,
	tbl_posto_media_atendimento.qtde_aberta,
	tbl_posto_media_atendimento.qtde_aberta_90,
	tbl_posto_media_atendimento.qtde_media,
	tbl_posto_media_atendimento.qtde_os_reincidente_90,
	tbl_posto_media_atendimento.qtde_digitada_90,
	tbl_posto_media_atendimento.porc_qtde_os_reincidente_90,
	tbl_posto_media_atendimento.qtde_pedidos_5_90,
	tbl_posto_media_atendimento.qtde_pedidos_90,
	tbl_posto_media_atendimento.porc_qtde_pedidos_90,
	CASE WHEN qtde_digitada_90 > 0 THEN
		(qtde_peca_90::double precision / qtde_digitada_90)::double precision
	ELSE 0
	END AS peca_por_os,
	tbl_posto_media_atendimento.nota,
	tbl_posto_media_atendimento.ranking,
	tbl_posto_media_atendimento.data_geracao
	
	FROM
	tbl_posto_media_atendimento
	JOIN tbl_posto ON tbl_posto_media_atendimento.posto = tbl_posto.posto
	JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica=$login_fabrica

	WHERE
	tbl_posto_media_atendimento.fabrica=$login_fabrica
	AND tbl_posto_media_atendimento.data_geracao='$data_geracao'
	AND qtde_extrato <= 10
	$cond_posto

	ORDER BY
	ranking
	";

	$res = pg_exec($con, $sql);

	if(pg_numrows($res)>0){
		echo "<BR><BR>";
		fputs ($fp,"<TABLE WIDTH='100%' BORDER='0' CELLPADDING='2' CELLSPACING='2' ALIGN='CENTER'>");
			echo "<TR class='Titulo'>";
			fputs ($fp,"<TR>");
				echo "<TD colspan=17 class='Subtitulo'>POSTOS COM ATÉ 10 OS DIGITADAS NOS ÚLTIMOS 90 DIAS</TD>";
				fputs ($fp,"<TD colspan=17>POSTOS COM ATÉ 10 OS DIGITADAS NOS ÚLTIMOS 90 DIAS</TD>");
			echo "</TR>";
			fputs ($fp,"</TR>");
			echo "<TR class='Titulo'>";
			fputs ($fp,"<TR>");
				echo "<TD colspan=17 class='Subtitulo'>METAS: % de OSs abaixo de 30 dias: 100% | Prazo Médio de Atendimento: 10 Dias | Reincidência: 0,5% | Peças Por OS: 0,60</TD>";
				fputs ($fp,'<TD colspan=17>METAS: % de OSs abaixo de 30 dias: 100% | Prazo Médio de Atendimento: 10 Dias | Reincidência: 0,5% | Peças Por OS: 0,60</TD>');
			echo "</TR>";
			fputs ($fp,'</TR>');
			echo $cab;
			fputs ($fp,$cab);

		for($i=0; $i<pg_numrows($res); $i++){
			$posto                       = pg_result($res, $i, posto);
			$codigo_posto                = pg_result($res, $i, codigo_posto);
			$nome_posto                  = pg_result($res, $i, nome);
			$qtde_extrato                = pg_result($res, $i, qtde_extrato);
			$qtde_finalizadas_30         = pg_result($res, $i, qtde_finalizadas_30);
			$qdte_media_extrato          = pg_result($res, $i, qtde_media_extrato);
			$qtde_aberta                 = pg_result($res, $i, qtde_aberta);
			$qtde_aberta_90              = pg_result($res, $i, qtde_aberta_90);
			$qdte_media                  = pg_result($res, $i, qtde_media);
			$qtde_os_reincidente_90      = pg_result($res, $i, qtde_os_reincidente_90);
			$qtde_digitada_90            = pg_result($res, $i, qtde_digitada_90);
			$porc_qtde_os_reincidente_90 = pg_result($res, $i, porc_qtde_os_reincidente_90);
			$qtde_pedidos_5_90           = pg_result($res, $i, qtde_pedidos_5_90);
			$qtde_pedidos_90             = pg_result($res, $i, qtde_pedidos_90);
			$porc_qtde_pedidos_90        = pg_result($res, $i, porc_qtde_pedidos_90);
			$peca_por_os                 = pg_result($res, $i, peca_por_os);
			$nota                        = pg_result($res, $i, nota);
			$rank                        = pg_result($res, $i, ranking);
			
			$qtde_finalizadas_30         = number_format($qtde_finalizadas_30,2,",",".");
			$qdte_media_extrato          = number_format($qdte_media_extrato,2,",",".");
			$qdte_media                  = number_format($qdte_media,2,",",".");
			$qtde_os_reincidente_90      = number_format($qtde_os_reincidente_90,2,",",".");
			$porc_qtde_os_reincidente_90 = number_format($porc_qtde_os_reincidente_90,2,",",".");
			$porc_qtde_pedidos_90        = number_format($porc_qtde_pedidos_90,2,",",".");
			$peca_por_os                 = number_format($peca_por_os,2,",",".");
			$nota                        = number_format($nota,2,",",".");

			if($i % 2 == 0){
				$cor = "#F1F4FA";
			}else{
				$cor = "#F7F5F0";
			}

			if (($i % 20 == 0) && ($i != 0) && (pg_num_rows($res) - $i > 5))
			{
				echo $cab;
			}

			echo "<TR class='Conteudo' bgcolor='$cor'>";
			fputs ($fp,"<TR bgcolor='$cor'>");
				echo "<TD nowrap align='center'>$codigo_posto</TD>";
				fputs ($fp,"<TD nowrap align='center'>$codigo_posto</TD>");
				echo "<TD nowrap align='left'>$nome_posto</TD>";
				fputs ($fp,"<TD nowrap align='left'>$nome_posto</TD>");
				echo "<TD>$qtde_extrato</TD>";
				fputs ($fp,"<TD>$qtde_extrato</TD>");
				echo "<TD>$qtde_finalizadas_30</TD>";
				fputs ($fp,"<TD>$qtde_finalizadas_30</TD>");
				echo "<TD>$qdte_media_extrato</TD>";
				fputs ($fp,"<TD>$qdte_media_extrato</TD>");
				echo "<TD>$qtde_aberta</TD>";
				fputs ($fp,"<TD>$qtde_aberta</TD>");
				echo "<TD>$qtde_aberta_90</TD>";
				fputs ($fp,"<TD>$qtde_aberta_90</TD>");
				echo "<TD>$qdte_media</TD>";
				fputs ($fp,"<TD>$qdte_media</TD>");
				echo "<TD>$qtde_os_reincidente_90</TD>";
				fputs ($fp,"<TD>$qtde_os_reincidente_90</TD>");
				echo "<TD>$porc_qtde_os_reincidente_90</TD>";
				fputs ($fp,"<TD>$porc_qtde_os_reincidente_90</TD>");
				echo "<TD>$qtde_pedidos_5_90</TD>";
				fputs ($fp,"<TD>$qtde_pedidos_5_90</TD>");
				echo "<TD>$qtde_pedidos_90</TD>";
				fputs ($fp,"<TD>$qtde_pedidos_90</TD>");
				echo "<TD>$porc_qtde_pedidos_90</TD>";
				fputs ($fp,"<TD>$porc_qtde_pedidos_90</TD>");
				echo "<TD>$peca_por_os</TD>";
				fputs ($fp,"<TD>$peca_por_os</TD>");
				echo "<TD>$nota</TD>";
				fputs ($fp,"<TD>$nota</TD>");
				echo "<TD WIDTH=150>$rank</TD>";
				fputs ($fp,"<TD WIDTH=150>$rank</TD>");
			echo "</TR>";
			fputs ($fp,"</TR>");
		}
	}

	$sql = "
	SELECT
	tbl_posto_media_atendimento.posto,
	tbl_posto_fabrica.codigo_posto,
	tbl_posto.nome,
	tbl_posto_media_atendimento.qtde_extrato,
	tbl_posto_media_atendimento.qtde_finalizadas_30,
	tbl_posto_media_atendimento.qtde_media_extrato,
	tbl_posto_media_atendimento.qtde_aberta,
	tbl_posto_media_atendimento.qtde_aberta_90,
	tbl_posto_media_atendimento.qtde_media,
	tbl_posto_media_atendimento.qtde_os_reincidente_90,
	tbl_posto_media_atendimento.qtde_digitada_90,
	tbl_posto_media_atendimento.porc_qtde_os_reincidente_90,
	tbl_posto_media_atendimento.qtde_pedidos_5_90,
	tbl_posto_media_atendimento.qtde_pedidos_90,
	tbl_posto_media_atendimento.porc_qtde_pedidos_90,
	CASE WHEN qtde_digitada_90 > 0 THEN
		(qtde_peca_90::double precision / qtde_digitada_90)::double precision
	ELSE 0
	END AS peca_por_os,
	tbl_posto_media_atendimento.nota,
	tbl_posto_media_atendimento.ranking,
	tbl_posto_media_atendimento.data_geracao
	
	FROM
	tbl_posto_media_atendimento
	JOIN tbl_posto ON tbl_posto_media_atendimento.posto = tbl_posto.posto
	JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica=$login_fabrica

	WHERE
	tbl_posto_media_atendimento.fabrica=$login_fabrica
	AND tbl_posto_media_atendimento.data_geracao='$data_geracao'
	AND qtde_extrato > 10
	AND qtde_extrato <= 100
	$cond_posto

	ORDER BY
	ranking
	";

	$res = pg_exec($con, $sql);

	if(pg_numrows($res)>0)
	{
			$sql = "
			SELECT
			COUNT(ranking)
			
			FROM
			tbl_posto_media_atendimento
			
			WHERE
			tbl_posto_media_atendimento.fabrica=$login_fabrica
			AND tbl_posto_media_atendimento.data_geracao='$data_geracao'
			AND qtde_extrato > 10
			AND qtde_extrato <= 100
			";
			$res_total = pg_query($con, $sql);
			$rank_total = pg_result($res_total, 0, 0);

			fputs ($fp,"<TR><TD></TD></TR>");
			
			echo "<TR class='Titulo'>";
			fputs ($fp,"<TR>");
				echo "<TD colspan=17 class='Subtitulo'>POSTOS COM 11 A 100 OS DIGITADAS NOS ÚLTIMOS 90 DIAS</TD>";
				fputs ($fp,"<TD colspan=17 >POSTOS COM 11 A 100 OS DIGITADAS NOS ÚLTIMOS 90 DIAS</TD>");
			echo "</TR>";
			fputs ($fp,"</TR>");
			echo "<TR class='Titulo'>";
			fputs ($fp,"<TR>");
				echo "<TD colspan=17 class='Subtitulo'>METAS: % de OSs abaixo de 30 dias: 100% | Prazo Médio de Atendimento: 10 Dias | Reincidência: 0,5% | Peças Por OS: 0,60</TD>";
				fputs ($fp,"<TD colspan=17>METAS: % de OSs abaixo de 30 dias: 100% | Prazo Médio de Atendimento: 10 Dias | Reincidência: 0,5% | Peças Por OS: 0,60</TD>");
			echo "</TR>";
			fputs ($fp,"</TR>");
			echo $cab;
			fputs ($fp,$cab);

		for($i=0; $i<pg_numrows($res); $i++){
			$posto                       = pg_result($res, $i, posto);
			$codigo_posto                = pg_result($res, $i, codigo_posto);
			$nome_posto                  = pg_result($res, $i, nome);
			$qtde_extrato                = pg_result($res, $i, qtde_extrato);
			$qtde_finalizadas_30         = pg_result($res, $i, qtde_finalizadas_30);
			$qdte_media_extrato          = pg_result($res, $i, qtde_media_extrato);
			$qtde_aberta                 = pg_result($res, $i, qtde_aberta);
			$qtde_aberta_90              = pg_result($res, $i, qtde_aberta_90);
			$qdte_media                  = pg_result($res, $i, qtde_media);
			$qtde_os_reincidente_90      = pg_result($res, $i, qtde_os_reincidente_90);
			$qtde_digitada_90            = pg_result($res, $i, qtde_digitada_90);
			$porc_qtde_os_reincidente_90 = pg_result($res, $i, porc_qtde_os_reincidente_90);
			$qtde_pedidos_5_90           = pg_result($res, $i, qtde_pedidos_5_90);
			$qtde_pedidos_90             = pg_result($res, $i, qtde_pedidos_90);
			$porc_qtde_pedidos_90        = pg_result($res, $i, porc_qtde_pedidos_90);
			$peca_por_os                 = pg_result($res, $i, peca_por_os);
			$nota                        = pg_result($res, $i, nota);
			$rank                        = pg_result($res, $i, ranking);
			
			$qtde_finalizadas_30         = number_format($qtde_finalizadas_30,2,",",".");
			$qdte_media_extrato          = number_format($qdte_media_extrato,2,",",".");
			$qdte_media                  = number_format($qdte_media,2,",",".");
			$qtde_os_reincidente_90      = number_format($qtde_os_reincidente_90,2,",",".");
			$porc_qtde_os_reincidente_90 = number_format($porc_qtde_os_reincidente_90,2,",",".");
			$porc_qtde_pedidos_90        = number_format($porc_qtde_pedidos_90,2,",",".");
			$peca_por_os                 = number_format($peca_por_os,2,",",".");
			$nota                        = number_format($nota,2,",",".");

			if($i % 2 == 0){
				$cor = "#F1F4FA";
			}else{
				$cor = "#F7F5F0";
			}

			if (($i % 20 == 0) && ($i != 0) && (pg_num_rows($res) - $i > 5))
			{
				echo $cab;
			}

			echo "<TR class='Conteudo' bgcolor='$cor'>";
			fputs ($fp,"<TR bgcolor='$cor'>");
				echo "<TD nowrap align='center'>$codigo_posto</TD>";
				fputs ($fp,"<TD nowrap align='center'>$codigo_posto</TD>");
				echo "<TD nowrap align='left'>$nome_posto</TD>";
				fputs ($fp,"<TD nowrap align='left'>$nome_posto</TD>");
				echo "<TD>$qtde_extrato</TD>";
				fputs ($fp,"<TD>$qtde_extrato</TD>");
				echo "<TD>$qtde_finalizadas_30</TD>";
				fputs ($fp,"<TD>$qtde_finalizadas_30</TD>");
				echo "<TD>$qdte_media_extrato</TD>";
				fputs ($fp,"<TD>$qdte_media_extrato</TD>");
				echo "<TD>$qtde_aberta</TD>";
				fputs ($fp,"<TD>$qtde_aberta</TD>");
				echo "<TD>$qtde_aberta_90</TD>";
				fputs ($fp,"<TD>$qtde_aberta_90</TD>");
				echo "<TD>$qdte_media</TD>";
				fputs ($fp,"<TD>$qdte_media</TD>");
				echo "<TD>$qtde_os_reincidente_90</TD>";
				fputs ($fp,"<TD>$qtde_os_reincidente_90</TD>");
				echo "<TD>$porc_qtde_os_reincidente_90</TD>";
				fputs ($fp,"<TD>$porc_qtde_os_reincidente_90</TD>");
				echo "<TD>$qtde_pedidos_5_90</TD>";
				fputs ($fp,"<TD>$qtde_pedidos_5_90</TD>");
				echo "<TD>$qtde_pedidos_90</TD>";
				fputs ($fp,"<TD>$qtde_pedidos_90</TD>");
				echo "<TD>$porc_qtde_pedidos_90</TD>";
				fputs ($fp,"<TD>$porc_qtde_pedidos_90</TD>");
				echo "<TD>$peca_por_os</TD>";
				fputs ($fp,"<TD>$peca_por_os</TD>");
				echo "<TD>$nota</TD>";
				fputs ($fp,"<TD>$nota</TD>");
				echo "<TD nowrap align='right'>$rank / $rank_total</TD>";
				fputs ($fp,"<TD nowrap align='right'>$rank / $rank_total</TD>");
			echo "</TR>";
			fputs ($fp,"</TR>");
		}
	}

	$sql = "
	SELECT
	tbl_posto_media_atendimento.posto,
	tbl_posto_fabrica.codigo_posto,
	tbl_posto.nome,
	tbl_posto_media_atendimento.qtde_extrato,
	tbl_posto_media_atendimento.qtde_finalizadas_30,
	tbl_posto_media_atendimento.qtde_media_extrato,
	tbl_posto_media_atendimento.qtde_aberta,
	tbl_posto_media_atendimento.qtde_aberta_90,
	tbl_posto_media_atendimento.qtde_media,
	tbl_posto_media_atendimento.qtde_os_reincidente_90,
	tbl_posto_media_atendimento.qtde_digitada_90,
	tbl_posto_media_atendimento.porc_qtde_os_reincidente_90,
	tbl_posto_media_atendimento.qtde_pedidos_5_90,
	tbl_posto_media_atendimento.qtde_pedidos_90,
	tbl_posto_media_atendimento.porc_qtde_pedidos_90,
	CASE WHEN qtde_digitada_90 > 0 THEN
		(qtde_peca_90::double precision / qtde_digitada_90)::double precision
	ELSE 0
	END AS peca_por_os,
	tbl_posto_media_atendimento.nota,
	tbl_posto_media_atendimento.ranking,
	tbl_posto_media_atendimento.data_geracao
	
	FROM
	tbl_posto_media_atendimento
	JOIN tbl_posto ON tbl_posto_media_atendimento.posto = tbl_posto.posto
	JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica=$login_fabrica

	WHERE
	tbl_posto_media_atendimento.fabrica=$login_fabrica
	AND tbl_posto_media_atendimento.data_geracao='$data_geracao'
	AND qtde_extrato > 100
	$cond_posto

	ORDER BY
	ranking
	";

	$res = pg_exec($con, $sql);

	if(pg_numrows($res)>0){
			$sql = "
			SELECT
			COUNT(ranking)
			
			FROM
			tbl_posto_media_atendimento
			
			WHERE
			tbl_posto_media_atendimento.fabrica=$login_fabrica
			AND tbl_posto_media_atendimento.data_geracao='$data_geracao'
			AND qtde_extrato > 100
			";

			//EBANO: RETIRADA A SQL QUE HAVIA NESTE PONTO, ESTAVA ERRADA, A CERTA É A QUE FICOU
			$res_total = pg_query($con, $sql);
			$rank_total = pg_result($res_total, 0, 0);

			fputs ($fp,"<TR><TD></TD></TR>");

			echo "<TR class='Titulo'>";
			fputs ($fp,"<TR>");
				echo "<TD colspan=17 class='Subtitulo'>POSTOS COM MAIS DE 100 OS DIGITADAS NOS ÚLTIMOS 90 DIAS</TD>";
				fputs ($fp,"<TD colspan=17 class='Subtitulo'>POSTOS COM MAIS DE 100 OS DIGITADAS NOS ÚLTIMOS 90 DIAS</TD>");
			echo "</TR>";
			fputs ($fp,"</TR>");
			echo "<TR class='Titulo'>";
			fputs ($fp,"<TR>");
				echo "<TD colspan=17 class='Subtitulo'>METAS: % de OSs abaixo de 30 dias: 100% | Prazo Médio de Atendimento: 10 Dias | Reincidência: 0,5% | Peças Por OS: 0,60</TD>";
				fputs ($fp,"<TD colspan=17 class='Subtitulo'>METAS: % de OSs abaixo de 30 dias: 100% | Prazo Médio de Atendimento: 10 Dias | Reincidência: 0,5% | Peças Por OS: 0,60</TD>");
			echo "</TR>";
			fputs ($fp,"</TR>");
			echo $cab;
			fputs ($fp,$cab);

		for($i=0; $i<pg_numrows($res); $i++){
			$posto                       = pg_result($res, $i, posto);
			$codigo_posto                = pg_result($res, $i, codigo_posto);
			$nome_posto                  = pg_result($res, $i, nome);
			$qtde_extrato                = pg_result($res, $i, qtde_extrato);
			$qtde_finalizadas_30         = pg_result($res, $i, qtde_finalizadas_30);
			$qdte_media_extrato          = pg_result($res, $i, qtde_media_extrato);
			$qtde_aberta                 = pg_result($res, $i, qtde_aberta);
			$qtde_aberta_90              = pg_result($res, $i, qtde_aberta_90);
			$qdte_media                  = pg_result($res, $i, qtde_media);
			$qtde_os_reincidente_90      = pg_result($res, $i, qtde_os_reincidente_90);
			$qtde_digitada_90            = pg_result($res, $i, qtde_digitada_90);
			$porc_qtde_os_reincidente_90 = pg_result($res, $i, porc_qtde_os_reincidente_90);
			$qtde_pedidos_5_90           = pg_result($res, $i, qtde_pedidos_5_90);
			$qtde_pedidos_90             = pg_result($res, $i, qtde_pedidos_90);
			$porc_qtde_pedidos_90        = pg_result($res, $i, porc_qtde_pedidos_90);
			$peca_por_os                 = pg_result($res, $i, peca_por_os);
			$nota                        = pg_result($res, $i, nota);
			$rank                        = pg_result($res, $i, ranking);
			
			$qtde_finalizadas_30         = number_format($qtde_finalizadas_30,2,",",".");
			$qdte_media_extrato          = number_format($qdte_media_extrato,2,",",".");
			$qdte_media                  = number_format($qdte_media,2,",",".");
			$qtde_os_reincidente_90      = number_format($qtde_os_reincidente_90,2,",",".");
			$porc_qtde_os_reincidente_90 = number_format($porc_qtde_os_reincidente_90,2,",",".");
			$porc_qtde_pedidos_90        = number_format($porc_qtde_pedidos_90,2,",",".");
			$peca_por_os                 = number_format($peca_por_os,2,",",".");
			$nota                        = number_format($nota,2,",",".");

			if($i % 2 == 0){
				$cor = "#F1F4FA";
			}else{
				$cor = "#F7F5F0";
			}

			if (($i % 20 == 0) && ($i != 0) && (pg_num_rows($res) - $i > 5))
			{
				echo $cab;
			}

			echo "<TR class='Conteudo' bgcolor='$cor'>";
			fputs ($fp,"<TR  bgcolor='$cor'>");
				echo "<TD nowrap align='center'>$codigo_posto</TD>";
				fputs ($fp,"<TD nowrap align='center'>$codigo_posto</TD>");
				echo "<TD nowrap align='left'>$nome_posto</TD>";
				fputs ($fp,"<TD nowrap align='left'>$nome_posto</TD>");
				echo "<TD>$qtde_extrato</TD>";
				fputs ($fp,"<TD>$qtde_extrato</TD>");
				echo "<TD>$qtde_finalizadas_30</TD>";
				fputs ($fp,"<TD>$qtde_finalizadas_30</TD>");
				echo "<TD>$qdte_media_extrato</TD>";
				fputs ($fp,"<TD>$qdte_media_extrato</TD>");
				echo "<TD>$qtde_aberta</TD>";
				fputs ($fp,"<TD>$qtde_aberta</TD>");
				echo "<TD>$qtde_aberta_90</TD>";
				fputs ($fp,"<TD>$qtde_aberta_90</TD>");
				echo "<TD>$qdte_media</TD>";
				fputs ($fp,"<TD>$qdte_media</TD>");
				echo "<TD>$qtde_os_reincidente_90</TD>";
				fputs ($fp,"<TD>$qtde_os_reincidente_90</TD>");
				echo "<TD>$porc_qtde_os_reincidente_90</TD>";
				fputs ($fp,"<TD>$porc_qtde_os_reincidente_90</TD>");
				echo "<TD>$qtde_pedidos_5_90</TD>";
				fputs ($fp,"<TD>$qtde_pedidos_5_90</TD>");
				echo "<TD>$qtde_pedidos_90</TD>";
				fputs ($fp,"<TD>$qtde_pedidos_90</TD>");
				echo "<TD>$porc_qtde_pedidos_90</TD>";
				fputs ($fp,"<TD>$porc_qtde_pedidos_90</TD>");
				echo "<TD>$peca_por_os</TD>";
				fputs ($fp,"<TD>$peca_por_os</TD>");
				echo "<TD>$nota</TD>";
				fputs ($fp,"<TD>$nota</TD>");
				echo "<TD nowrap align='right'>$rank / $rank_total</TD>";
				fputs ($fp,"<TD nowrap align='right'>$rank / $rank_total</TD>");
			echo "</TR>";
			fputs ($fp,"</TR>");
		}
		echo "</TABLE>";
		fputs ($fp,"</TABLE>");
	}

	if ($login_fabrica == 3) {
		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-indicadores-pa-$login_fabrica-$data_xls.xls /tmp/assist/relatorio-indicadores-pa-$login_fabrica.html`;
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><BR>RELATÓRIO DE INDICADORES DE POSTOS AUTORIZADOS<BR>Clique aqui para fazer o </font><a href='xls/relatorio-indicadores-pa-$login_fabrica-$data_xls.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
	}
}

echo nl2br($sql);
?>


<?php include "rodape.php" ?>
