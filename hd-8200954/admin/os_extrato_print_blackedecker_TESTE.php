<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

if ($login_fabrica <> 1) {
	header ("Location: menu_financeiro.php");
	exit;
}

$layout_menu = "financeiro";
$title = "Detalhe Extrato - Ordem de Serviço";
?>

<html>

<head>
<title><? echo $title ?></title>
<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
<meta http-equiv="Expires"       content="0">
<meta http-equiv="Pragma"        content="no-cache, public">
<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
<meta name      ="Author"        content="Telecontrol Networking Ltda">
<meta name      ="Generator"     content="na mão...">
<link type="text/css" rel="stylesheet" href="css/css_press.css">
</head>

<body>
<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD><IMG SRC="logos/cabecalho_print_<? echo strtolower ($login_fabrica_nome) ?>.gif" ALT="ORDEM DE SERVIÇO"></TD>
</TR>
</TABLE>

<br>

<?
if (strlen(trim($_GET["extrato"])) > 0) {
	$extrato    = trim($_GET["extrato"]);
	$data_atual = date("d/m/Y");
	
	$sql = "SELECT  to_char(min(tbl_os.data_fechamento),'DD/MM/YYYY') AS inicio,
					to_char(max(tbl_os.data_fechamento),'DD/MM/YYYY') AS final
			FROM    tbl_os
			JOIN    tbl_os_extra USING (os)
			WHERE   tbl_os_extra.extrato = $extrato;";
	$res = pg_exec ($con,$sql);
	
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
					tbl_posto.cidade                                       ,
					tbl_posto.estado                                       ,
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
		$posto         = trim(pg_result($res,0,posto));
		$codigo        = trim(pg_result($res,0,codigo_posto));
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

		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
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
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>NOTA DE CRÉDITO $data_extrato</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' nowrap align='right' >\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>$protocolo</b></font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Período:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='100' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$inicio_extrato</font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>até:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='120' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$final_extrato</font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Data:</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='280' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_atual</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Código:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='580' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$codigo</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Posto:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left' nowrap>\n";
		echo "<img src='imagens/pixel.gif' width='580' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$nome</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Endereço:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left' nowrap>\n";
		echo "<img src='imagens/pixel.gif' width='580' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$endereco</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Cidade:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='580' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$cidade - $estado - $cep</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
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
		echo "<img src='imagens/pixel.gif' width='300' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$email</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
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
		echo "<img src='imagens/pixel.gif' width='420' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$ie</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left' nowrap>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Banco:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left' nowrap>\n";
		echo "<img src='imagens/pixel.gif' width='580' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$banco</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' align='left' nowrap>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Agência:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left' nowrap>\n";
		echo "<img src='imagens/pixel.gif' width='580' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$agencia</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left' nowrap>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Conta:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left' nowrap>\n";
		echo "<img src='imagens/pixel.gif' width='580' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$conta</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
	}
	
	echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
	echo "<tr>\n";
	
	echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
	echo "<hr>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
	echo "</table>\n";
	
	$sql =	"SELECT tbl_extrato.total AS total
			FROM    tbl_extrato
			WHERE   tbl_extrato.extrato = $extrato;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$total_GE = pg_result($res,0,total);
		
		$sql =	"SELECT SUM(tbl_os.pecas)       AS total_PC   ,
						SUM(tbl_os.mao_de_obra) AS total_MO   
				FROM    tbl_os
				JOIN    tbl_os_extra USING (os)
				JOIN    tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
				WHERE   tbl_os_extra.extrato = $extrato
				AND     tbl_os.fabrica       = $login_fabrica
				GROUP BY tbl_extrato.avulso;";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$total_PC   = pg_result($res,0,total_PC);
			$total_MO   = pg_result($res,0,total_MO);
		}
		$total_PC = $total_PC + $total_RE;

		$sql = "SELECT  sum(valor) AS total_S_PC
				FROM    tbl_extrato_lancamento
				WHERE   tbl_extrato_lancamento.extrato = $extrato
				AND     tbl_extrato_lancamento.lancamento in (41,42)";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$total_S_PC = pg_result($res,0,total_S_PC);
		}

		$sql = "SELECT  sum(valor) AS total_DP_S
				FROM    tbl_extrato_lancamento
				WHERE   tbl_extrato_lancamento.extrato = $extrato
				AND     tbl_extrato_lancamento.lancamento = 40";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$total_S_DP = pg_result($res,0,total_DP_S);
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
	}
	
	if (strlen($total_MO) == 0) {
		### PARA CASOS APENAS DE ALUGUEL ###
		$total_MO = $total_AV;
	}
	
	$total_geral = $total_GE;
	
//	if (strlen($total_SD) > 0 and $total_SD > 0 and strlen($total_DP_S) == 0) {
//		$total_DP_S = $total_SD;
//	}
	
	echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
	echo "<tr>\n";
	
	echo "<td bgcolor='#FFFFFF' width='120' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Período Inicial:</b></font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$inicio_extrato</font>\n";
	echo "</td>\n";
	
	
	echo "<td bgcolor='#FFFFFF' width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total 10% tx. Administrativa:</b></font>\n";
	echo "</td>\n";
	
	# seleciona o lancamento de 10%
	$sql = "SELECT valor
			FROM   tbl_extrato_lancamento
			WHERE  extrato    = $extrato
			AND    fabrica    = $login_fabrica
			AND    lancamento = 47";
	$res = pg_exec($con,$sql);
	$valor_10 = @pg_result($res,0,0);

	echo "<td bgcolor='#FFFFFF' width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($valor_10,2,",",".") ."</font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
	echo "</table>\n";
	
	echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
	echo "<tr>\n";
	
	echo "<td bgcolor='#FFFFFF' width='120' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Período Final:</b></font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$final_extrato</font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Peça SEDEX:</b></font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_S_PC,2,",",".") ."</font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
	echo "</table>\n";

	echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
	echo "<tr>\n";
	
	echo "<td bgcolor='#FFFFFF' width='120' align='center'>\n";
	echo "&nbsp;\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='150' align='left'>\n";
	echo "&nbsp;\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Mão-de-obra OS:</b></font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_MO,2,",",".") ."</font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
	echo "</table>\n";
	
	echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
	echo "<tr>\n";
	
	echo "<td bgcolor='#FFFFFF' width='120' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Despesas SEDEX:</b></font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_S_DP,2,",",".") ."</font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
	echo "</table>\n";
	
	if (strlen($total_TF) > 0 AND $total_TF <> 0) {
		echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' width='120' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' width='150' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' width='250' align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Abatimento de Troca Faturada:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' width='50' align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_TF,2,",",".") ."</font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' width='10%' align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
	}
	
	echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
	echo "<tr>\n";
	
	echo "<td bgcolor='#FFFFFF' width='120' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>TOTAL GERAL:</b></font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_geral,2,",",".") ."</font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";
	
	echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
	echo "<tr>\n";
	
	echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
	echo "<hr>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
	echo "</table>\n";
	
	echo "<table border='0' cellpadding='0' cellspacing='0' width='650' align='center'>\n";
	echo "<tr>\n";
	
	echo "<td bgcolor='#FFFFFF' width='120' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>APROVAÇÕES</b></font>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
	echo "</table>\n";
	
	echo "<br><br>";
	
	echo "<table border='0' cellpadding='2' cellspacing='2' width='650' align='center'>\n";
	echo "<tr>\n";
	
	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>__________________________</font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>__________________________</font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>__________________________</font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>__________________________</font>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
	echo "<tr>\n";
	
	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>Emitente</font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>Ger. Assist. Técnica</font>\n";
	echo "</td>\n";

	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>Crédito e Cobrança</font>\n";
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
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>Data: ______/______/______</font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>Data: ______/______/______</font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>Data: ______/______/______</font>\n";
	echo "</td>\n";
	
	echo "<td bgcolor='#FFFFFF' width='25%' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>Data: ______/______/______</font>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
	echo "</table>\n";
}
?>

<br>

</body>

</html>
