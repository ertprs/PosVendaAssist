<?php
include 'dbconfig.php';
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include 'autentica_admin.php';
require_once '../class/tdocs.class.php';

if ($login_fabrica <> 1) {
	header ("Location: menu_financeiro.php");
	exit;
}

if (strlen(trim($_GET["extrato"])) > 0) $extrato = trim($_GET["extrato"]);

$sql = "SELECT  tbl_posto_fabrica.tipo_posto            ,
				tbl_posto_fabrica.posto                 ,
				tbl_posto_fabrica.reembolso_peca_estoque
		FROM    tbl_posto_fabrica
		JOIN    tbl_extrato ON tbl_extrato.posto = tbl_posto_fabrica.posto
		WHERE   tbl_extrato.extrato       = $extrato
		AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
$res = pg_query($con,$sql);

if (pg_num_rows($res) == 1) {
	$posto                  = trim(pg_fetch_result($res,0,posto));
	$tipo_posto             = trim(pg_fetch_result($res,0,tipo_posto));
	$reembolso_peca_estoque = trim(pg_fetch_result($res,0,reembolso_peca_estoque));
}
$layout_menu = "financeiro";
$title = "Black & Decker - Detalhe Extrato - Ordem de Serviço";
?>

<html>

<head>
<title><? echo $title ?></title>
<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
<meta http-equiv="Expires"       content="0">
<meta http-equiv="Pragma"        content="no-cache, public">
<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
<link type="text/css" rel="stylesheet" href="css/css_press.css">

<style>
/*******************************
 ELEMENTOS DE COR FONTE EXTRATO
*******************************/
.TdBold   {font-weight: bold;}
.TdNormal {font-weight: normal;}
.td_menu{
	font-family:Verdana, Arial, Helvetica, sans;
	font-size: 10px;
	font-weight: bold;
	color: #000000;
}

.td_conteudo{
	font-family:Verdana, Arial, Helvetica, sans;
	font-size: 10px;
	color: #000000;
}
</style>

</head>

<body>
<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD>
		<!--
		<IMG SRC="logos/cabecalho_print_<? echo strtolower ($login_fabrica_nome) ?>.png" ALT="ORDEM DE SERVIÇO" style='width: 130px;'>
		-->
		<IMG SRC="logos/logo_black_2016.png" ALT="ORDEM DE SERVIÇO" style='width: 180px;'>
	</TD>
</TR>
</TABLE>

<br>

<?
if (strlen($extrato) > 0) {
	$data_atual = date("d/m/Y");

	$sql = "SELECT  to_char(min(tbl_os.data_fechamento),'DD/MM/YYYY') AS inicio,
					to_char(max(tbl_os.data_fechamento),'DD/MM/YYYY') AS final
			FROM    tbl_os
			JOIN    tbl_os_extra USING (os)
			WHERE   tbl_os_extra.extrato = $extrato;";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$inicio_extrato = trim(pg_fetch_result($res,0,'inicio'));
		$final_extrato  = trim(pg_fetch_result($res,0,'final'));
	}

	if (strlen($inicio_extrato) == 0 AND strlen($final_extrato) == 0) {
		$sql = "SELECT  to_char(min(tbl_extrato.data_geracao),'DD/MM/YYYY') AS inicio,
						to_char(max(tbl_extrato.data_geracao),'DD/MM/YYYY') AS final
				FROM    tbl_extrato
				WHERE   tbl_extrato.extrato = $extrato";
		$res = pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) {
			$inicio_extrato = trim(pg_fetch_result($res,0,'inicio'));
			$final_extrato  = trim(pg_fetch_result($res,0,'final'));
		}
	}

	$sql = "SELECT  tbl_posto_fabrica.codigo_posto                                          ,
					tbl_posto.posto                                         ,
					tbl_posto.nome                                          ,
					tbl_posto_fabrica.contato_endereco AS endereco          ,
					tbl_posto_fabrica.contato_cidade   AS cidade            ,
					tbl_posto_fabrica.contato_estado   AS estado            ,
					tbl_posto_fabrica.contato_cep      AS cep               ,
					tbl_posto_fabrica.contato_fone_comercial as fone                                         ,
					tbl_posto_fabrica.contato_fax as fax                                           ,
					tbl_posto.contato                                       ,
					tbl_posto_fabrica.contato_email    AS email             ,
					tbl_posto.cnpj                                          ,
					tbl_posto.ie                                            ,
					tbl_posto_fabrica.banco                                 ,
					tbl_posto_fabrica.agencia                               ,
					tbl_posto_fabrica.conta                                 ,
					tbl_extrato.protocolo                                   ,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_extrato ON tbl_extrato.posto = tbl_posto.posto
			WHERE   tbl_extrato.extrato = $extrato
			AND     tbl_extrato.fabrica = $login_fabrica;";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$codigo        = trim(pg_fetch_result($res,0,codigo_posto));
		$posto         = trim(pg_fetch_result($res,0,posto));
		$nome          = trim(pg_fetch_result($res,0,nome));
		$endereco      = trim(pg_fetch_result($res,0,endereco));
		$cidade        = trim(pg_fetch_result($res,0,cidade));
		$estado        = trim(pg_fetch_result($res,0,estado));
		$cep           = substr(pg_fetch_result($res,0,cep),0,2) .".". substr(pg_fetch_result($res,0,cep),2,3) ."-". substr(pg_fetch_result($res,0,cep),5,3);
		$fone          = trim(pg_fetch_result($res,0,fone));
		$fax           = trim(pg_fetch_result($res,0,fax));
		$contato       = trim(pg_fetch_result($res,0,contato));
		$email         = trim(pg_fetch_result($res,0,email));
		$cnpj          = trim(pg_fetch_result($res,0,cnpj));
		$ie            = trim(pg_fetch_result($res,0,ie));
		$banco         = trim(pg_fetch_result($res,0,banco));
		$agencia       = trim(pg_fetch_result($res,0,agencia));
		$conta         = trim(pg_fetch_result($res,0,conta));
		$data_extrato  = trim(pg_fetch_result($res,0,data));
		$protocolo     = trim(pg_fetch_result($res,0,protocolo));

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' width='100%' align='left' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>BLACK & DECKER DO BRASIL LTDA</b></font>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "<tr>\n";

		echo "<td nowrap bgcolor='#FFFFFF' width='100%' colspan=2 align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>End.</b> Rod. BR 050 S/N KM 167-LOTE 5 QVI &nbsp;&nbsp;-&nbsp;&nbsp; <b>Bairro:</b> DI II</font>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "<tr>\n";

		echo "<td nowrap bgcolor='#FFFFFF' width='100%' colspan=2 align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Cidade:</b> Uberaba &nbsp;&nbsp;-&nbsp;&nbsp; <b>Estado:</b> MG &nbsp;&nbsp;-&nbsp;&nbsp; <b>Cep:</b> 38064-750</font>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' width='50%' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>Inscrição CNPJ: 53.296.273/0001-91</font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' width='50%' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>Inscrição Estadual: 701.948.711.00-98</font>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>NOTA DE CRÉDITO $data_extrato</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' nowrap align='right' >\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>$protocolo</b></font>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br>Período:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='100' height='1'><br>$inicio_extrato\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br>Até:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='120' height='1'><br>$final_extrato\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br>Data:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left'  class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='230' height='1'><br>$data_atual\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br>Código:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br>$codigo\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br>Posto:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' nowrap class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br>$nome\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br>Endereço:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' nowrap class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br>$endereco\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' align='left' width='70' class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br>Cidade:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br>$cidade - $estado - $cep\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br>Telefone:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='100' height='1'><br>$fone\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br>&nbsp;&nbsp;&nbsp;Fax:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='100' height='1'><br>$fax\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br>&nbsp;&nbsp;&nbsp;E-mail:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='240' height='1'><br>$email\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br>CNPJ:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='130' height='1'><br>$cnpj\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='30' height='1' ><br>IE:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='370' height='1'><br>$ie\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' align='left' nowrap class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br>Banco:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' nowrap class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br>$banco\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' align='left' nowrap class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br>Agência:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' nowrap class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br>$agencia\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' align='left' nowrap class='td_menu'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br>Conta:\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' nowrap class='td_conteudo'>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br>$conta\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";
	}
	$sql = "SELECT  tbl_extrato.total
			FROM    tbl_extrato
			WHERE   tbl_extrato.extrato = $extrato";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$total_GE = pg_fetch_result($res,0,total);

		$sql = "SELECT total_custo_peca_os_item
				FROM tbl_extrato_extra
				WHERE   tbl_extrato_extra.extrato = $extrato ";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$total_PC   = pg_fetch_result($res,0,0);
		}

		$sql = "SELECT  total_mao_de_obra_os
				FROM tbl_extrato_extra
				WHERE   tbl_extrato_extra.extrato = $extrato ";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$total_MO   = pg_fetch_result($res,0,0);
		}

		$sql = "SELECT  tbl_extrato.avulso AS total_DP_S FROM tbl_extrato WHERE extrato = $extrato";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$total_DP_S = pg_fetch_result($res,0,total_DP_S);
		}

		$total_PC = $total_PC + $total_RE;
	}
	$total_retorno = 0;
	$sql = "SELECT  total_os_geo_visita
			FROM    tbl_extrato_extra
			WHERE   tbl_extrato_extra.extrato = $extrato ";
	$res_retorno = pg_query ($con,$sql);
	if (pg_num_rows($res_retorno) > 0) {
		$total_retorno= pg_fetch_result($res_retorno,0,total_os_geo_visita);
	}
	// DESPESAS AVULSAS
	$sql = "SELECT  total_avulso_os_sedex
			FROM    tbl_extrato_extra
			WHERE   extrato = $extrato ";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$total_AV = pg_fetch_result($res,0,0);
	}
	if ($total_MO == 0) {
		### PARA CASOS APENAS DE ALUGUEL ###
		$total_MO = $total_AV;

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "</tr>\n";
		echo "</table>\n";
	}else{
		$sql = "  SELECT sum(valor_extrato_lancamento)
					FROM tbl_extrato_extra_item
					WHERE lancamento in (165,132,45)
					AND extrato=$extrato;";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$total_MO += pg_fetch_result($res,0,0);
		}
	}
	$total_geral = $total_GE;

	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";

	echo "<td width='100%' align='left'>\n";
	echo "<hr>\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";

	echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";

	echo "<td width='120' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Período Inicial:</b></font>\n";
	echo "</td>\n";

	echo "<td width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$inicio_extrato</font>\n";
	echo "</td>\n";

	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Peça OS:&nbsp;</b></font>\n";
	echo "</td>\n";

	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_PC,2,",",".") ."</font>\n";
	echo "</td>\n";

	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";
	$sql = "SELECT sum(tbl_os.pecas * (((regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb->>'TxAdmGrad')::float -1))) 
			from tbl_os
			join tbl_os_extra using(os)
				join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os and campos_adicionais ~'TxAdm'
			where tbl_os_extra.extrato = $extrato
			and tbl_os.pecas > 0
			and (((regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb->>'TxAdmGrad')::float)) > 0
 ";
	$resX = pg_query($con, $sql);
	$totalTx = pg_fetch_result($resX,0, 0); 

	$sql = "SELECT sum(valor)
			FROM tbl_extrato_lancamento
			WHERE extrato = $extrato
			AND lancamento = 47";

	$resX = pg_query ($con,$sql);
	$taxa_adm = 0 ;
	if (pg_num_rows ($resX) > 0) $taxa_adm = pg_fetch_result ($resX,0,0);

	$taxa_adm =  ($taxa_adm > 0) ? $taxa_adm : $totalTx;

	$total_geral += $totalTx;
	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";

	echo "<td width='120' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";

	echo "<td width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";

	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Taxa Administrativa:&nbsp;</b></font>\n";
	echo "</td>\n";

	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($taxa_adm,2,",",".") ."</font>\n";
	echo "</td>\n";

	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";
	$sql = "SELECT  distinct tbl_os.os                                                      ,
					sua_os                                                         ,
					tbl_produto.referencia                                         ,
					tbl_os_sedex.os_sedex ,
					nota_fiscal                                                    ,
					nota_fiscal_saida                                              ,
					to_char(data_nf_saida,'DD/MM/YYYY') as data_nf_saida           ,
					case when tbl_extrato_lancamento.valor *-1 <>tbl_os_troca.total_troca then tbl_extrato_lancamento.valor * -1 else tbl_os_troca.total_troca end as total_troca                                      ,
					tbl_os.mao_de_obra                                             ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf        ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					tipo_atendimento
				FROM tbl_os_extra
				JOIN tbl_os         ON tbl_os.os = tbl_os_extra.os
				JOIN tbl_os_troca   ON tbl_os_troca.os = tbl_os_extra.os
				JOIN tbl_produto    ON tbl_produto.produto = tbl_os.produto
				LEFT JOIN tbl_os_sedex   ON tbl_os_sedex.os = tbl_os_troca.os
				LEFT JOIN tbl_extrato_lancamento ON tbl_extrato_lancamento.os_sedex = tbl_os_sedex.os_sedex and tbl_extrato_lancamento.extrato = $extrato
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os_extra.extrato = $extrato
				AND tipo_atendimento in (17,18,35);";

	$res = pg_query ($con,$sql);
	if(pg_num_rows($res) > 0){
		for ($x = 0; $x < pg_num_rows($res); $x++) {
			$troca_total              = trim(pg_fetch_result($res,$x,total_troca));
			$xtotal   = $xtotal - $troca_total;
			$troca_total = round($troca_total,2);
			$troca_sub_total = ($troca_sub_total) + ($troca_total);
			$troca_total = number_format($troca_total,2,".",",");
			$troca_total_mo = $troca_total + $troca_mao_de_obra;
		}
	}

	#---------- Total de Peças SEDEX ------------

		/*HD 73295*/
		$sql = "SELECT total_pecas_os_sedex
				FROM tbl_extrato_extra
				WHERE extrato = $extrato";
		$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$total_S_PC = pg_fetch_result($res,0,0);
	}




	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";

	echo "<td width='120' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Período Final:</b></font>\n";
	echo "</td>\n";

	echo "<td width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$final_extrato</font>\n";
	echo "</td>\n";

	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Peça SEDEX:&nbsp;</b></font>\n";
	echo "</td>\n";

	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_S_PC,2,",",".") ."</font>\n";
	echo "</td>\n";

	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";



	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";

	echo "<td width='120' align='center'>\n";
	echo "</td>\n";

	echo "<td width='150' align='left'>\n";
	echo "</td>\n";

	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Mão-de-obra OS:&nbsp;</b></font>\n";
	echo "</td>\n";

	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_MO+$total_retorno,2,",",".") ."</font>\n";
	echo "</td>\n";

	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";

	$sql = "SELECT  total_os_geo_deslocamento AS total_km
			FROM    tbl_extrato_extra
			WHERE   tbl_extrato_extra.extrato = $extrato ";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		$total_km = pg_fetch_result($res,0,'total_km');
	}

	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	echo "<td width='120' align='center'>\n";
	echo "</td>\n";
	echo "<td width='150' align='left'>\n";
	echo "</td>\n";
	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total deslocamento km (OS Geo):&nbsp;</b></font>\n";
	echo "</td>\n";
	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_km,2,",",".") ."</font>\n";	echo "</td>\n";
	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

/* --------------- DESPESAS ADICIONAIS OS GEO --------------------- */
	#113 |       1 | OS GEO - Despesas Adicionais | C              | t
	$sql = "SELECT  total_os_geo_despesa AS total_adicional
			FROM    tbl_extrato_extra
			WHERE   tbl_extrato_extra.extrato = $extrato ";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		$total_adicional = pg_fetch_result($res,0,'total_adicional');
	}

	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	echo "<td width='120' align='center'>\n";
	echo "</td>\n";
	echo "<td width='150' align='left'>\n";
	echo "</td>\n";
	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Valor Adicional(OS Geo):&nbsp;</b></font>\n";
	echo "</td>\n";
	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_adicional,2,",",".") ."</font>\n";
	echo "</td>\n";
	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
/* --------------- TROCA FATURADA --------------------------------- */
	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";

	echo "<td width='120' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";

	echo "<td width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";

	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Troca Faturada:&nbsp;</b></font>\n";
	echo "</td>\n";

	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>-". number_format($troca_sub_total,2,",",".") ."</font>\n";
	echo "</td>\n";

	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";

/* --------------- Sub Total Despesas SEDEX ---------------------*/
	$sql = "SELECT  total_despesas_os_sedex
			FROM    tbl_extrato_extra
			WHERE   tbl_extrato_extra.extrato = $extrato ";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$total_DP_S = pg_fetch_result($res,0,0);
	}

	$sql = "SELECT SUM(valor) FROM tbl_extrato_lancamento WHERE extrato = $extrato AND admin notnull and lancamento not in (47) ";
	$resX = pg_query ($con,$sql);
	$outros = 0 ;
	if (pg_num_rows ($resX) > 0) $outros = pg_fetch_result ($resX,0,0);

	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";

	echo "<td width='120' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";

	echo "<td width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";

	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Despesas SEDEX:&nbsp;</b></font>\n";
	echo "</td>\n";

	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_DP_S,2,",",".") ."</font>\n";
	echo "</td>\n";

	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";


	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";

	echo "<td width='120' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";

	echo "<td width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";

	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>TOTAL GERAL:&nbsp;</b></font>\n";
	echo "</td>\n";

	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_geral,2,",",".") ."</font>\n";
	echo "</td>\n";

	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";

	echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";

	echo "<td width='100%' align='left'>\n";
	echo "<hr>\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";
/* ------------------------------------------------------------ */
	
	$classProtocolo = new \Posvenda\Fabricas\_1\Protocolo($login_fabrica, $con, $tcComm);

	$tDocsAss = new TDocs($con, $login_fabrica, 'assinatura');

	$sqlProtocolo = "SELECT codigo
					 FROM tbl_extrato_agrupado
					 WHERE extrato = {$extrato}";
	$resProtocolo = pg_query($con, $sqlProtocolo);

	$codigoProtocolo = pg_fetch_result($resProtocolo, 0, "codigo");

	$dadosAprovadores = $classProtocolo->getAprovadoresProtocolo($codigoProtocolo);

	$dataComercial = "______/______/______";
	if (!empty($dadosAprovadores["aprovador_comercial"])) {

        $img_assinatura = $tDocsAss->getDocumentsByRef($dadosAprovadores["id_aprovador_comercial"])->url;

        $assinaturaComercial = "<img id='imagem_firma' src='{$img_assinatura}' width='170' height='40' />";

        $dataComercial = $dadosAprovadores["data_aprovacao_comercial"];

    }

    $dataContasPagar = "______/______/______";
    if (!empty($dadosAprovadores["aprovador_contas_pagar"])) {

        $img_assinatura = $tDocsAss->getDocumentsByRef($dadosAprovadores["id_aprovador_contas_pagar"])->url;

        $assinaturaContas = "<img id='imagem_firma' src='{$img_assinatura}' width='170' height='40' />";

        $dataContasPagar = $dadosAprovadores["data_aprovacao_contas_pagar"];

    }

	echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
	echo "<tr>\n";

	echo "<td bgcolor='#FFFFFF' width='120' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>APROVAÇÕES</b></font>\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";

	echo "<br><br>";

	echo "<table border='0 cellpadding='2' cellspacing='8' width='650' align='center'>\n";
	echo "<tr>\n";

	echo "<td bgcolor='#FFFFFF' width='25%' align='bottom' style='border-bottom:1px solid #000;'>\n";
	echo $assinaturaComercial;
	echo "</td>\n";

	echo "<td bgcolor='#FFFFFF' width='25%' align='bottom' style='border-bottom:1px solid #000;'>\n";

    $sql = "SELECT  extrato,
                    TO_CHAR(conferido,'DD/MM/YYYY') AS data_liberacao,
                    admin_conferiu
            FROM    tbl_extrato_status
            WHERE   extrato = $extrato
            AND     pendente IS FALSE
            AND     obs ILIKE 'Aguardando aprova%'
    ";
    $res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0) {

        $data_liberacao = pg_fetch_result($res,0,data_liberacao);
        $admin_conferiu = pg_fetch_result($res,0,admin_conferiu);

        $tdocs = new TDocs($con, $login_fabrica);
        $assinatura = $tdocs->getDocumentsByRef($admin_conferiu, 'assinatura')->url;

        if (isset($assinatura)) {
            echo "<img src='".$assinatura."' width='170' height='40'/>\n";
        } else {
            echo "&nbsp;";
        }
	}
	echo "</td>\n";

	echo "<td bgcolor='#FFFFFF' width='25%' align='bottom' style='border-bottom:1px solid #000;'>&nbsp;\n";
	echo $assinaturaContas;
	echo "</td>\n";

	echo "</tr>\n";
	echo "<tr>\n";

	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>Emitente</font>\n";
	echo "</td>\n";

	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>Assistência Técnica</font>\n";
	echo "</td>\n";

	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>Contas a pagar</font>\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";

	echo "<br><br>";

	echo "<table border='0' cellpadding='2' cellspacing='2' width='650' align='center'>\n";
	echo "<tr>\n";

	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>Data: {$dataComercial}</font>\n";
	echo "</td>\n";

	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	if (empty($data_liberacao)) {
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>Data: ______/______/______</font>\n";
	} else {
        echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>Data: $data_liberacao</font>\n";
	}
	echo "</td>\n";

	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>Data: {$dataContasPagar}</font>\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";
}

?>
<br>

</body>

</html>
