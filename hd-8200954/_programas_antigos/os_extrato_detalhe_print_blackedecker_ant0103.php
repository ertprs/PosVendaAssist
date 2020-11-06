<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include 'autentica_usuario.php';

if ($login_fabrica <> 1) {
	header ("Location: menu_os.php");
	exit;
}
if ($extrato == '112849' or $extrato == '126901') {
	header ("Location: os_extrato_detalhe_print_blackedecker_tk.php?extrato=$extrato");
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
$res = pg_exec($con,$sql);

if (pg_numrows($res) == 1) {
	$posto                  = trim(pg_result($res,0,posto));
	$tipo_posto             = trim(pg_result($res,0,tipo_posto));
	$reembolso_peca_estoque = trim(pg_result($res,0,reembolso_peca_estoque));
}

#if ($reembolso_peca_estoque == 't' AND $extrato > 46902) {
#	header ("Location: os_extrato_detalhe_print_blackedecker_TESTE.php?extrato=".$extrato);
#	exit;
#}

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
</style>

</head>

<body>
<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD><IMG SRC="logos/cabecalho_print_<? echo strtolower ($login_fabrica_nome) ?>.gif" ALT="ORDEM DE SERVIÇO"></TD>
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
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$inicio_extrato = trim(pg_result($res,0,inicio));
		$final_extrato  = trim(pg_result($res,0,final));
	}

	if (strlen($inicio_extrato) == 0 AND strlen($final_extrato) == 0) {
		$sql = "SELECT  to_char(min(tbl_extrato.data_geracao),'DD/MM/YYYY') AS inicio,
						to_char(max(tbl_extrato.data_geracao),'DD/MM/YYYY') AS final
				FROM    tbl_extrato
				WHERE   tbl_extrato.extrato = $extrato";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$inicio_extrato = trim(pg_result($res,0,inicio));
			$final_extrato  = trim(pg_result($res,0,final));
		}
	}

	$sql = "SELECT  tbl_posto_fabrica.codigo_posto                          ,
					tbl_posto.posto                                         ,
					tbl_posto.nome                                          ,
					tbl_posto.endereco                                      ,
					tbl_posto.cidade                                        ,
					tbl_posto.estado                                        ,
					tbl_posto.cep                                           ,
					tbl_posto.fone                                          ,
					tbl_posto.fax                                           ,
					tbl_posto.contato                                       ,
					tbl_posto.email                                         ,
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
			WHERE   tbl_extrato.extrato = $extrato;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$codigo        = trim(pg_result($res,0,codigo_posto));
		$posto         = trim(pg_result($res,0,posto));
		$nome          = trim(pg_result($res,0,nome));
		$endereco      = trim(pg_result($res,0,endereco));
		$cidade        = trim(pg_result($res,0,cidade));
		$estado        = trim(pg_result($res,0,estado));
		$cep           = substr(pg_result($res,0,cep),0,2) .".". substr(pg_result($res,0,cep),2,3) ."-". substr(pg_result($res,0,cep),5,3);
		$fone          = trim(pg_result($res,0,fone));
		$fax           = trim(pg_result($res,0,fax));
		$contato       = trim(pg_result($res,0,contato));
		$email         = trim(pg_result($res,0,email));
		$cnpj          = trim(pg_result($res,0,cnpj));
		$ie            = trim(pg_result($res,0,ie));
		$banco         = trim(pg_result($res,0,banco));
		$agencia       = trim(pg_result($res,0,agencia));
		$conta         = trim(pg_result($res,0,conta));
		$data_extrato  = trim(pg_result($res,0,data));
		$protocolo     = trim(pg_result($res,0,protocolo));
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='left' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>BLACK & DECKER DO BRASIL LTDA</b></font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' width='50%' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>Rod. BR 050 S/N KM 167-LOTE 5 QVI - DI II</font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' width='50%' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>Uberaba - MG - 38056-580</font>\n";
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
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>EXTRATO DE SERVIÇOS $data_extrato</b></font>\n";
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
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Período:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='100' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$inicio_extrato</font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Até:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='120' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$final_extrato</font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Data:</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='230' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_atual</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Código:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$codigo</font>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Posto:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left' nowrap>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$nome</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Endereço:</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' nowrap>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$endereco</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left' width='70'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Cidade:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$cidade - $estado - $cep</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Telefone:</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='100' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$fone</font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Fax:</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='100' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$fax</font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>E-mail:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='250' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$email</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>CNPJ:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='130' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$cnpj</font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='30' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>IE:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='370' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$ie</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
	}
	
	echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	
	echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
	echo "<hr>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
	echo "</table>\n";
	
	$xtotal = 0;
	
	### OS NORMAL
//			AND   (length(tbl_os.obs) = 0 OR tbl_os.obs isnull)

	$sql =	"SELECT tbl_os.os                                                     ,
					tbl_os.sua_os                                                 ,
					(SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os) AS pecas  ,
					tbl_os.mao_de_obra                                            ,
					tbl_os.nota_fiscal                                            ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf       ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento
			FROM    tbl_os_extra
			JOIN    tbl_os USING (os)
			WHERE   tbl_os_extra.extrato = $extrato
			AND     tbl_os.fabrica = $login_fabrica
			AND     ( tbl_os.satisfacao IS NULL OR tbl_os.satisfacao IS FALSE )
			ORDER BY lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,0) ASC,
					replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,0),'-','') ASC;";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		//if($ip=="201.42.46.223"){ echo "$sql";}
//		echo "<td bgcolor='#FFFFFF' width='5%' align='center' colspan='2'>\n";
//		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS</b></font>\n";
//		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' width='20%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' width='10%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>NF</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' width='10%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>NF Data</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' width='15%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Abertura</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' width='15%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Fechamento</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Peça</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total MO</b></font>\n";
		echo "</td>\n";
		
#		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
#		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça + MO</b></font>\n";
#		echo "</td>\n";
		
		echo "</tr>\n";
		
		// monta array para ver duplicidade
		$busca_array     = array();
		$localizou_array = array();
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$nota_fiscal   = trim(pg_result($res,$x,nota_fiscal));
			if (in_array($nota_fiscal, $busca_array)) {
				$localizou_array[] = $nota_fiscal;
			}
			$busca_array[] = $nota_fiscal;
		}
		
		// monta array da tela
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$sua_os    = trim(pg_result($res,$x,sua_os));
			$os        = trim(pg_result($res,$x,os));
			$pecas     = trim(pg_result($res,$x,pecas));
			$maodeobra = trim(pg_result($res,$x,mao_de_obra));
			$data_abertura   = trim(pg_result($res,$x,data_abertura));
			$data_fechamento = trim(pg_result($res,$x,data_fechamento));
			$nota_fiscal     = trim(pg_result($res,$x,nota_fiscal));
			$data_nf         = trim(pg_result($res,$x,data_nf));
			
			$total_os = $pecas + $maodeobra;
			
			$bold = "TdNormal";
			
			if (in_array($nota_fiscal, $localizou_array)) {
				$bold = "TdBold";
			}
			
			echo "<tr class='$bold'>\n";
//			echo "<td align='center' colspan='2'>\n";
//			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><a href='os_press.php?os=$os' target='_blank'><b>OS</b></a></font>\n";
//			echo "</td>\n";
			
			echo "<td align='center' colspan='2'>\n";
			echo "<a href='os_press.php?os=$os' target='_blank'><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$codigo$sua_os</font></a>\n";
			echo "</td>\n";

			echo "<td align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$nota_fiscal</a></font>\n";
			echo "</td>\n";

			echo "<td align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_nf</a></font>\n";
			echo "</td>\n";

			echo "<td align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_abertura</a></font>\n";
			echo "</td>\n";

			echo "<td align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_fechamento</a></font>\n";
			echo "</td>\n";

			echo "<td align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($pecas,2,",",".") ."</font>\n";
			echo "</td>\n";
			
			echo "<td align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($maodeobra,2,",",".") ."</font>\n";
			echo "</td>\n";
			
#			echo "<td align='right'>\n";
#			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_os,2,",",".") ."</font>\n";
#			echo "</td>\n";

			echo "</tr>\n";
		}


		echo "<tr class='$bold'>\n";
		
		echo "<td align='center' colspan='10'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>TOTAL DE PEÇAS COMPRADAS</a></font>\n";
		echo "</td>\n";
		
		$sql = "SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) 
				FROM  tbl_os_item 
				JOIN  tbl_os_produto USING (os_produto) 
				JOIN  tbl_os_extra   ON tbl_os_produto.os = tbl_os_extra.os
				WHERE tbl_os_extra.extrato = $extrato AND tbl_os_item.servico_realizado = 90";
		$resX = pg_exec ($con,$sql);
		$total_pecas = 0 ;
		if (pg_numrows ($resX) > 0) $total_pecas = pg_result ($resX,0,0);

		echo "<td align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";


		echo "<tr class='$bold'>\n";
		
		echo "<td align='center' colspan='10'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>TOTAL DE PEÇAS ENVIADAS EM GARANTIA</a></font>\n";
		echo "</td>\n";
		
		$sql = "SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) 
				FROM  tbl_os_item 
				JOIN  tbl_os_produto USING (os_produto) 
				JOIN  tbl_os_extra   ON tbl_os_produto.os = tbl_os_extra.os
				WHERE tbl_os_extra.extrato = $extrato AND tbl_os_item.servico_realizado = 62";
		$resX = pg_exec ($con,$sql);
		$total_pecas = 0 ;
		if (pg_numrows ($resX) > 0) $total_pecas = pg_result ($resX,0,0);

		echo "<td align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";


		echo "<tr class='$bold'>\n";
		
		echo "<td align='center' colspan='10'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>10% DE TAXA ADM.</a></font>\n";
		echo "</td>\n";
		
		# seleciona o lancamento de 10%
		$sql = "SELECT valor
				FROM   tbl_extrato_lancamento
				WHERE  extrato    = $extrato
				AND    fabrica    = $login_fabrica
				AND    lancamento = 47";
		$res = pg_exec($con,$sql);
		$valor_10 = @pg_result($res,0,0);

		echo "<td align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($valor_10,2,",",".") ."</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";


		echo "</table>\n";
	}
	
	### OS SATISFAÇÃO DEWALT
	$sql =	"SELECT tbl_os_extra.os    ,
					tbl_os.os          ,
					(SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os) AS pecas  ,
					tbl_os.mao_de_obra ,
					tbl_os.sua_os      ,
					tbl_os.laudo_tecnico,
					tbl_os.nota_fiscal,
					tbl_os.data_nf
			FROM    tbl_os_extra
			JOIN    tbl_os USING (os)
			WHERE   tbl_os_extra.extrato = $extrato
			AND     tbl_os.fabrica = $login_fabrica
			AND     tbl_os.satisfacao IS TRUE
			ORDER BY substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')) ASC,
					lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')+1,length(tbl_os.sua_os)),5,0) ASC,
					tbl_os.sua_os;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='15%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS</b></font>\n";
		echo "</td>\n";
		
		//adicionado por Wellington 29/01/2007 - 09:30
		echo "<td width='10%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>NF</b></font>\n";
		echo "</td>\n";

		//adicionado por Wellington 29/01/2007 - 09:30
		echo "<td width='15%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>NF Data</b></font>\n";
		echo "</td>\n";

		echo "<td width='15%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Laudo Técnico</b></font>\n";
		echo "</td>\n";
		
		echo "<td width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Peça</b></font>\n";
		echo "</td>\n";
		
		echo "<td width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total MO</b></font>\n";
		echo "</td>\n";
		
		echo "<td width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça + MO</b></font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$sua_os        = trim(pg_result($res,$x,sua_os));
			$os            = trim(pg_result($res,$x,os));
			$pecas         = trim(pg_result($res,$x,pecas));
			$maodeobra     = trim(pg_result($res,$x,mao_de_obra));
			$laudo_tecnico = trim(pg_result($res,$x,laudo_tecnico));
			$nf            = trim(pg_result($res,$x,nota_fiscal));
			$nf_data       = trim(pg_result($res,$x,data_nf));

			$total_os = $pecas + $maodeobra;

			echo "<tr>\n";
			
			echo "<td width='15%' align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$codigo$sua_os</a></font>\n";
			echo "</td>\n";
			
			//adicionado por Wellington 29/01/2007 - 09:30
			echo "<td width='10%' align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>&nbsp; $nf</a></font>\n";
			echo "</td>\n";

			//adicionado por Wellington 29/01/2007 - 09:30
			echo "<td width='15%' align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>&nbsp; $data_nf</a></font>\n";
			echo "</td>\n";
			
			echo "<td width='15%' align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>&nbsp; $laudo_tecnico</a></font>\n";
			echo "</td>\n";

			echo "<td width='15%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($pecas,2,",",".") ."</font>\n";
			echo "</td>\n";
			
			echo "<td width='15%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($maodeobra,2,",",".") ."</font>\n";
			echo "</td>\n";
			
			echo "<td width='15%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_os,2,",",".") ."</font>\n";
			echo "</td>\n";
			
			echo "</tr>\n";
		}
		echo "</table>\n";
	}

	### PEÇAS ENVIADAS EM GARANTIA
	if ($tipo_posto == 4 OR $tipo_posto == 5 OR $tipo_posto == 10) {
		$sql = "SELECT   DISTINCT
						 tbl_peca_ressarcida.pedido_garantia
				FROM     tbl_peca
				JOIN     tbl_peca_ressarcida ON tbl_peca_ressarcida.peca = tbl_peca.peca
				WHERE    tbl_peca_ressarcida.extrato  = $extrato
				AND      tbl_peca_ressarcida.qtde = 0
				ORDER BY tbl_peca_ressarcida.pedido_garantia;";
		$res = @pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
			echo "<tr>\n";
			
			echo "<td width='100%' align='left'>\n";
			echo "<hr>\n";
			echo "</td>\n";
			
			echo "</tr>\n";
			echo "</table>\n";
			
			echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
			echo "<tr>\n";
			
			echo "<td align='left' colspan='6'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>PEÇAS FATURADAS EM GARANTIA (SN-GART)</b></font>\n";
			echo "</td>\n";
			
			echo "</tr>\n";
			echo "<tr>\n";
			
			echo "<td align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>PEDIDO</b></font>\n";
			echo "</td>\n";
			
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>PEÇA</b></font>\n";
			echo "</td>\n";
			
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>QTDE</b></font>\n";
			echo "</td>\n";
			
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>TOTAL</b></font>\n";
			echo "</td>\n";
			
			echo "</tr>\n";
			
			for ($x = 0; $x < pg_numrows($res); $x++) {
				$pedido_garantia = trim(pg_result($res,$x,pedido_garantia));
				
				$sql = "SELECT  tbl_peca.referencia                  ,
								tbl_peca.nome                        ,
								tbl_posicao_faturamento.qtde_faturada,
								tbl_posicao_faturamento.valor_unitario_peca
						FROM    tbl_posicao_faturamento
						JOIN    tbl_peca ON tbl_peca.referencia_antiga = tbl_posicao_faturamento.referencia_peca
						WHERE   tbl_posicao_faturamento.natureza_operacao = 'SN-GART'
						AND     substr(trim(tbl_posicao_faturamento.pedido_mfg),4,length(pedido_mfg))::integer = $pedido_garantia
						ORDER BY tbl_peca.referencia;";
				$res1 = @pg_exec ($con,$sql);
				
				for ($y = 0; $y < @pg_numrows($res1); $y++) {
					$peca = trim(pg_result($res1,$y,referencia)) ." - ". trim(pg_result($res1,$y,nome));
					$qtde = trim(pg_result($res1,$y,qtde_faturada));
					$ress = trim(pg_result($res1,$y,valor_unitario_peca));
					
					
					echo "<tr>\n";
					
					echo "<td align='center' colspan='2' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$pedido_garantia</a></font>\n";
					echo "</td>\n";
					
					echo "<td align='left' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$peca</a></font>\n";
					echo "</td>\n";
					
					echo "<td align='right'>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$qtde</font>\n";
					echo "</td>\n";
					
					echo "<td align='right'>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($ress,2,",",".") ."</font>\n";
					echo "</td>\n";
					
					echo "</tr>\n";
				}
			}
			echo "</table>\n";
		}
	}
	
	
	# Peças das OSs
	$sql = "SELECT  tbl_os.sua_os,
					tbl_produto.referencia AS ref_equipamento ,
					tbl_produto.descricao  AS nome_equipamento,
					tbl_peca.referencia    AS ref_peca        ,
					tbl_peca.descricao     AS nome_peca       ,
					tbl_os_item.qtde
			FROM    tbl_os_item
			JOIN    tbl_os_produto        ON tbl_os_item.os_produto                  = tbl_os_produto.os_produto
			JOIN    tbl_os                ON tbl_os_produto.os                       = tbl_os.os
			JOIN    tbl_os_extra          ON tbl_os.os                               = tbl_os_extra.os
			JOIN    tbl_produto           ON tbl_os.produto                          = tbl_produto.produto
			JOIN    tbl_peca              ON tbl_os_item.peca                        = tbl_peca.peca
			JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
			WHERE   tbl_os_extra.extrato = $extrato
			AND     tbl_os.fabrica = $login_fabrica
			ORDER BY substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')) ASC,
					lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')+1,length(tbl_os.sua_os)),5,0) ASC,
					tbl_os.sua_os;";
// Retirado do SQL a pedido da Fabiola em 11/11/2005
//			AND     tbl_servico_realizado.troca_de_peca IS NOT FALSE

	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS</b></font>\n";
		echo "</td>\n";
		
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Equipamento</b></font>\n";
		echo "</td>\n";
		
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça</b></font>\n";
		echo "</td>\n";
		
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Qtde</b></font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			echo "<tr>\n";
			
			echo "<td align='center' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$codigo". trim(pg_result($res,$x,sua_os)) ."</a></font>\n";
			echo "</td>\n";
			
			echo "<td align='left' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_result($res,$x,ref_equipamento)) ." - ". substr(trim(pg_result($res,$x,nome_equipamento)),0,15) ."</font>\n";
			echo "</td>\n";
			
			echo "<td align='left' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_result($res,$x,ref_peca)) ." - ". trim(pg_result($res,$x,nome_peca)) ."</font>\n";
			echo "</td>\n";
			
			echo "<td align='center' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_result($res,$x,qtde)) ."</font>\n";
			echo "</td>\n";
			
			echo "</tr>\n";
		}
		echo "</table>\n";
	}
	
	$sql = "SELECT  tbl_extrato.total
			FROM    tbl_extrato
			WHERE   tbl_extrato.extrato = $extrato";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$total_GE = pg_result($res,0,total);
		
		$sql = "SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) 
				FROM tbl_os_item
				JOIN tbl_os_produto USING (os_produto)
				JOIN tbl_os_extra   ON tbl_os_produto.os = tbl_os_extra.os
				WHERE   tbl_os_extra.extrato = $extrato
				AND     tbl_os_item.servico_realizado = 90";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$total_PC   = pg_result($res,0,0);
		}
		
		$sql = "SELECT  sum(tbl_os.mao_de_obra) AS total_MO
				FROM    tbl_os
				JOIN    tbl_os_extra USING (os)
				WHERE   tbl_os_extra.extrato = $extrato";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$total_MO   = pg_result($res,0,total_MO);
		}
		
		$sql = "SELECT  tbl_extrato.avulso AS total_DP_S FROM tbl_extrato WHERE extrato = $extrato";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$total_DP_S = pg_result($res,0,total_DP_S);
		}
		
		$total_PC = $total_PC + $total_RE;
	
	}
	
	// DESPESAS AVULSAS
	$sql = "SELECT  sum(valor) AS total_Avulso
			FROM    tbl_extrato_lancamento
			WHERE   extrato = $extrato
			AND     lancamento not in (40,41,42)";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$total_AV = pg_result($res,0,total_Avulso);
	}
/*
	if (strlen($total_MO) == 0) {
		### PARA CASOS APENAS DE ALUGUEL ###
		$total_MO = $total_AV;
	}
*/
	############ OS SEDEX ############
/*
	$sql = "SELECT  lpad(tbl_os_sedex.os_sedex,5,0) AS os_sedex ,
					tbl_os_sedex.total_pecas                     ,
					tbl_os_sedex.despesas                        ,
					tbl_os_sedex.total
			FROM    tbl_os_sedex
			WHERE   tbl_os_sedex.extrato_origem = $extrato 
			AND     tbl_os_sedex.fabrica = $login_fabrica
			ORDER BY tbl_os_sedex.os_sedex";
*/
	$sql = "SELECT * 
			FROM (
				(
					SELECT	lpad(tbl_os_sedex.os_sedex,5,0) AS os_sedex ,
							tbl_os_sedex.total_pecas                    ,
							tbl_os_sedex.despesas                       ,
							tbl_os_sedex.sua_os_origem                  ,
							tbl_os_sedex.sua_os_destino                 ,
							tbl_os_sedex.total 
					FROM	tbl_os_sedex 
					WHERE	tbl_os_sedex.extrato_origem = $extrato 
					AND		tbl_os_sedex.fabrica = $login_fabrica 
					ORDER BY tbl_os_sedex.os_sedex
				) union (
					SELECT	lpad(tbl_os_sedex.os_sedex,5,0) AS os_sedex ,
							tbl_os_sedex.total_pecas                    ,
							tbl_os_sedex.despesas                       ,
							tbl_os_sedex.sua_os_origem                  ,
							tbl_os_sedex.sua_os_destino                 ,
							tbl_os_sedex.total 
					FROM	tbl_os_sedex 
					WHERE	tbl_os_sedex.extrato_destino = $extrato 
					AND		tbl_os_sedex.posto_origem = 6901 
					AND		tbl_os_sedex.fabrica = $login_fabrica 
					ORDER BY tbl_os_sedex.os_sedex
				) 
			) AS x;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<br>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='25%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS SEDEX - Crédito</b></font>\n";
		echo "</td>\n";
		
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Peça</b></font>\n";
		echo "</td>\n";
		
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Despesas</b></font>\n";
		echo "</td>\n";
		
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça + Despesas</b></font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$sua_os        = trim(pg_result($res,$x,sua_os_origem));
			$cr_os_destino = trim(pg_result($res,$x,sua_os_destino));
			$total_pecas   = trim(pg_result($res,$x,total_pecas));
			$despesas      = trim(pg_result($res,$x,despesas));
			$total         = trim(pg_result($res,$x,total));
			
			$xtotal   = $xtotal + $total_pecas + $despesas;
			
			if($cr_os_destino == 'CR' AND strlen($sua_os) > 0){
				$sql2 = "SELECT sua_os FROM tbl_os WHERE os = '$sua_os' AND tbl_os.fabrica = $login_fabrica; ";
				$res2 = pg_exec($con, $sql2);
				$sua_os = pg_result($res2, 0,sua_os);
			}
			echo "<tr>\n";
			
			echo "<td width='25%' align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$codigo$sua_os</a></font>\n";
			echo "</td>\n";
			
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
			echo "</td>\n";
			
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($despesas,2,",",".") ."</font>\n";
			echo "</td>\n";
			
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total,2,",",".") ."</font>\n";
			echo "</td>\n";
			
			echo "</tr>\n";
		}
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
	}
	
	# SEDEX CRÉDITO
	$sql = "SELECT  lpad(tbl_os_sedex.os_sedex,5,0) AS os_sedex ,
					tbl_peca.referencia             AS ref_peca ,
					tbl_peca.descricao              AS nome_peca,
					tbl_os_sedex_item.qtde
			FROM    tbl_os_sedex_item
			JOIN    tbl_os_sedex ON tbl_os_sedex_item.os_sedex = tbl_os_sedex.os_sedex
			JOIN    tbl_peca     ON tbl_os_sedex_item.peca     = tbl_peca.peca
			WHERE   tbl_os_sedex.extrato_origem = $extrato
			AND     tbl_os_sedex.fabrica = $login_fabrica
			ORDER BY tbl_os_sedex.os_sedex";
	$res = pg_exec ($con,$sql);
//echo $sql;
	if (pg_numrows($res) > 0) {
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td align='center'>\n";
		echo "<font width='150' face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS SEDEX - Crédito</b></font>\n";
		echo "</td>\n";
		
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça</b></font>\n";
		echo "</td>\n";
		
		echo "<td align='center'>\n";
		echo "<font width='50' face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Qtde</b></font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			echo "<tr>\n";
			
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$codigo". trim(pg_result($res,$x,os_sedex)) ."</a></font>\n";
			echo "</td>\n";
			
			echo "<td align='left' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_result($res,$x,ref_peca)) ." - ". trim(pg_result($res,$x,nome_peca)) ."</font>\n";
			echo "</td>\n";
			
			echo "<td align='center' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_result($res,$x,qtde)) ."</font>\n";
			echo "</td>\n";
			
			echo "</tr>\n";
		}
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
	}
	

	
	
	
	# SEDEX DÉBITO
	$sql = "SELECT  tbl_os_sedex.sua_os_destino                  ,
					tbl_os_sedex.total_pecas_destino             ,
					tbl_os_sedex.despesas                        ,
					tbl_os_sedex.total_destino
			FROM    tbl_os_sedex
			WHERE   tbl_os_sedex.extrato_destino = $extrato
			AND     tbl_os_sedex.fabrica         = $login_fabrica
			AND     tbl_os_sedex.posto_destino   = $posto
			AND     tbl_os_sedex.total_pecas_destino > 0
			ORDER BY tbl_os_sedex.os_sedex";
	//echo $sql;
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<br>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='25%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS SEDEX - Débito</b></font>\n";
		echo "</td>\n";
		
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Peça</b></font>\n";
		echo "</td>\n";
		
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Despesas</b></font>\n";
		echo "</td>\n";
		
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça + Despesas</b></font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$sua_os      = trim(pg_result($res,$x,sua_os_destino));
			$total_pecas = trim(pg_result($res,$x,total_pecas_destino));
			$despesas    = trim(pg_result($res,$x,despesas));
			$total       = trim(pg_result($res,$x,total_destino));
			
			$xtotal   = $xtotal + $total_pecas + $despesas;
			
			echo "<tr>\n";
			
			echo "<td width='25%' align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$sua_os</a></font>\n";
			echo "</td>\n";
			
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
			echo "</td>\n";
			
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($despesas,2,",",".") ."</font>\n";
			echo "</td>\n";
			
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total,2,",",".") ."</font>\n";
			echo "</td>\n";
			
			echo "</tr>\n";
		}
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
	}
	

// MOSTRA TODAS AS OS EXCLUÍDAS E SUAS PEÇAS COM VALORES.
	$sql = "SELECT  tbl_os_sedex.sua_os_destino              ,
					tbl_os_sedex.total_pecas                 ,
					tbl_os_sedex.os_sedex
				FROM    tbl_os_sedex
				JOIN    tbl_extrato_lancamento using(os_sedex)
				JOIN    tbl_os_status ON tbl_os_sedex.os_sedex = tbl_os_status.os_sedex
			WHERE   tbl_os_sedex.extrato_destino = $extrato
			AND     tbl_os_sedex.fabrica         = $login_fabrica
			AND     tbl_os_sedex.posto_destino   = $posto
			AND     tbl_os_sedex.total > 0
			ORDER BY tbl_os_sedex.os_sedex";
	//echo $sql;
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<br>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='100%' align='left'>\n";
		echo "\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			
			echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
			echo "<tr>\n";
			
			echo "<td width='25%' align='center' colspan='4'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS EXCLUÍDA: </b></font><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='1'>$codigo$sua_os</a></font>";
			echo "</td>\n";
			
/*				echo "<td width='25%' align='center'>\n";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS SEDEX</b></font>\n";
				echo "</td>\n";
			
				echo "<td width='25%' align='center'>\n";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Peças</b></font>\n";
				echo "</td>\n";
				
				echo "<td width='25%' align='center'>\n";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça + Despesas</b></font>\n";
				echo "</td>\n";
*/			
			echo "</tr>\n";

			
			$sua_os      = trim(pg_result($res,$x,sua_os_destino));
			$total_pecas = trim(pg_result($res,$x,total_pecas));
			$os_sedex    = trim(pg_result($res,$x,os_sedex));
			
			$xtotal   = $xtotal + $total_pecas + $despesas;
			
/*			echo "<tr>\n";
			
			echo "<td width='25%' align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$codigo$sua_os</a></font>\n";
			echo "</td>\n";
			
//			echo "<td width='25%' align='right'>\n";
//			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". $os_sedex ."</font>\n";
//			echo "</td>\n";
			
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
			echo "</td>\n";
			
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "</tr>";
*/
			$sqlX = "SELECT tbl_os_status.os         ,
							tbl_peca.referencia      ,
							tbl_peca.descricao       ,
							tbl_os_item.custo_peca
						FROM tbl_os_status 
						JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os_status.os
						JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
						JOIN tbl_peca       ON tbl_peca.peca = tbl_os_item.peca
					WHERE tbl_os_status.status_os = 15 
					AND   tbl_os_status.extrato = $extrato 
					AND   tbl_os_status.os_sedex = '$os_sedex' ;";
			$resX = pg_exec($con,$sqlX);
//			echo "$sqlX";
			if (pg_numrows($resX) > 0) {

				echo "<tr>";

				echo "<td colspan='4' align='left' style='font-family: Verdana, Arial, Helvetica, sans; font-size: 10px; font-weight: bold'>\n";
				echo "PEÇAS\n";
				echo "</td>\n";

				echo "</tr>";

				for($k = 0 ; $k < pg_numrows($resX); $k++){
					
					$ref   = pg_result($resX,$k,referencia);
					$desc  = pg_result($resX,$k,descricao);
					$custo = pg_result($resX,$k,custo_peca);

					echo "<tr style='font-family: Verdana, Arial, Helvetica, sans; font-size: 9px; color: #797979'>";

					echo "<td colspan='3' align='left'>\n";
					echo "$ref - $desc\n";
					echo "</td>\n";
				
					echo "<td width='25%' align='right'>\n";
					echo "". number_format($custo,2,",",".") ."\n";
					echo "</td>\n";

					echo "</tr>";
				}
			}
			echo "</tr>";
			echo "<tr>";
				echo "<td width='25%' align='right' colspan='2'>\n";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Peças:</b></font> <font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
				echo "</td>\n";
					
				echo "<td width='25%' align='right' colspan='2'>\n";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça + Despesas:</b></font> <font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
				echo "</td>\n";
			
			echo "</tr>\n";

			echo "</table>";

			echo "<tr>";
				echo "<td>&nbsp;</td>";
			echo "</tr>";
		}


		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
	}


	$sql = "SELECT  tbl_os_sedex.sua_os_destino             ,
					tbl_peca.referencia         AS ref_peca ,
					tbl_peca.descricao          AS nome_peca,
					tbl_os_sedex_item.qtde
			FROM    tbl_os_sedex_item
			JOIN    tbl_os_sedex ON tbl_os_sedex_item.os_sedex = tbl_os_sedex.os_sedex
			JOIN    tbl_peca     ON tbl_os_sedex_item.peca     = tbl_peca.peca
			WHERE   tbl_os_sedex.extrato_destino = $extrato
			AND     tbl_os_sedex.fabrica = $login_fabrica
			ORDER BY tbl_os_sedex.sua_os_destino ASC";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td align='center'>\n";
		echo "<font width='150' face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS SEDEX - Débito</b></font>\n";
		echo "</td>\n";
		
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça</b></font>\n";
		echo "</td>\n";
		
		echo "<td align='center'>\n";
		echo "<font width='50' face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Qtde</b></font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			echo "<tr>\n";
			
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_result($res,$x,sua_os_destino)) ."</a></font>\n";
			echo "</td>\n";
			
			echo "<td align='left' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_result($res,$x,ref_peca)) ." - ". trim(pg_result($res,$x,nome_peca)) ."</font>\n";
			echo "</td>\n";
			
			echo "<td align='center' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_result($res,$x,qtde)) ."</font>\n";
			echo "</td>\n";
			
			echo "</tr>\n";
		}

		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
	}
	

	# OUTROS
	$sql = "SELECT  * FROM tbl_extrato_lancamento WHERE extrato = $extrato AND lancamento = 45";
	$res = pg_exec ($con,$sql);

	$outros = 0 ;
	if (pg_numrows($res) > 0 AND 1==2 ) {

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";

		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td width='25%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Outros Lançamentos</b></font>\n";
		echo "</td>\n";

		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Valor</b></font>\n";
		echo "</td>\n";

		echo "</tr>\n";

		for ($x = 0; $x < pg_numrows($res); $x++) {
			$descricao   = trim(pg_result($res,$x,descricao));
			$valor       = trim(pg_result($res,$x,valor));
			
			$outros += $valor ;

			echo "<tr>\n";
			
			echo "<td width='25%' align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$descricao</a></font>\n";
			echo "</td>\n";
			
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($valor,2,",",".") ."</font>\n";
			echo "</td>\n";
			
			echo "</tr>\n";
		}
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
	}
	
	
	
	
	
	if (strlen($total_MO) == 0) {
		### PARA CASOS APENAS DE ALUGUEL ###
		$total_MO = $total_AV;
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='100%' align='left'>\n";
		if (strlen($total_SD) > 0) {
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OUTROS SERVIÇOS PRESTADOS</b></font>\n";
		}else{
			$total_DP_S = $total_SD;
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OUTRAS DESPESAS</b></font>\n";
		}
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
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

	

	$sql = "SELECT valor FROM tbl_extrato_lancamento WHERE extrato = $extrato AND lancamento = 47";
	$resX = pg_exec ($con,$sql);
	$taxa_adm = 0 ;
	if (pg_numrows ($resX) > 0) $taxa_adm = pg_result ($resX,0,0);
	
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
	


	
	#---------- Total de Peças SEDEX ------------

	$sql = "SELECT  sum(valor) AS total_S_PC
			FROM    tbl_extrato_lancamento
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_extrato_lancamento.lancamento in (41,42)";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$total_S_PC = pg_result($res,0,total_S_PC);
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
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_MO,2,",",".") ."</font>\n";
	echo "</td>\n";
	
	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
	echo "</table>\n";
	
	$sql = "SELECT  sum(valor) AS total_DP_S
			FROM    tbl_extrato_lancamento
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_extrato_lancamento.lancamento = 40";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$total_DP_S = pg_result($res,0,total_DP_S);
	}

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
	


	









	
	if (strlen($total_TF) > 0 AND $total_TF <> 0) {
		$xtotal = $xtotal - $total_TF;
		
		echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='120' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
		echo "</td>\n";
		
		echo "<td width='150' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
		echo "</td>\n";
		
		echo "<td width='250' align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Abatimento de Troca Faturada:&nbsp;</b></font>\n";
		echo "</td>\n";
		
		echo "<td width='50' align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_TF,2,",",".") ."</font>\n";
		echo "</td>\n";
		
		echo "<td width='10%' align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
	}
	

	
	if (1==2) {
		echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td width='120' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
		echo "</td>\n";
		
		echo "<td width='150' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
		echo "</td>\n";
		
		echo "<td width='250' align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Outros:&nbsp;</b></font>\n";
		echo "</td>\n";
		
		echo "<td width='50' align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($outros,2,",",".") ."</font>\n";
		echo "</td>\n";
		
		echo "<td width='10%' align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
	}	

	
	
	
	
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
}
?>

<br>

</body>

</html>
<SCRIPT LANGUAGE="JavaScript">
<!--
//window.print();
//-->
</SCRIPT>
