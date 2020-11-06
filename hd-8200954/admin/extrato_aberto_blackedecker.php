<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

if (strlen($HTTP_POST_VARS["btnacao"]) > 0) {
	$btnacao = trim($HTTP_POST_VARS["btnacao"]);
}

if (strlen($HTTP_GET_VARS["btnacao"]) > 0) {
	$btnacao = trim($HTTP_GET_VARS["btnacao"]);
}

if (strlen($HTTP_POST_VARS["codigo"]) > 0) {
	$codigo = trim($HTTP_POST_VARS["codigo"]);
}

if (strlen($HTTP_GET_VARS["codigo"]) > 0) {
	$codigo = trim($HTTP_GET_VARS["codigo"]);
}

if (strlen($HTTP_GET_VARS["recalculo"]) > 0) {
	$recalculo = trim($HTTP_GET_VARS["recalculo"]);
}

if (strlen($HTTP_GET_VARS["extrato"]) > 0) {
	$extrato = trim($HTTP_GET_VARS["extrato"]);
}

if ($recalculo == "ok") {
	$sql = "SELECT tbl_extrato.oid
			FROM   tbl_extrato
			WHERE  tbl_extrato.extrato = $extrato;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$oid = trim(pg_result($res,0,0));
		
		#echo `/var/www/blackedecker/perl/reprocessa-os-web.pl $oid`;
		#$erro = `/var/www/blackedecker/perl/reprocessa-os-web.pl $oid`;
		
		#if (strlen($erro) > 0) {
		#	exit;
		#}
		
		echo `/var/www/blackedecker/perl/reprocessa-extrato-web.pl $oid`;
		$erro = `/var/www/blackedecker/perl/reprocessa-extrato-web.pl $oid`;
		
		if (strlen($erro) > 0) {
			exit;
		}
	}
}

if (strlen($HTTP_GET_VARS["aprovar"]) > 0) {
	$extrato = trim($HTTP_GET_VARS["aprovar"]);
	
	$sql = "INSERT INTO tbl_extrato_financeiro (
				extrato,
				valor
			) VALUES (
				$extrato,
				(
					SELECT to_char(tbl_extrato.total, 999999990.99) AS total
					FROM   tbl_extrato
					WHERE  tbl_extrato.extrato = $extrato
				)::float
			);";
	$res = pg_exec ($con,$sql);
	
	if (strlen ( pg_errormessage ($con) ) > 0) {
		$erro .= pg_errormessage ($con) ;
	}
	
	if (strlen($erro) == 0) {
		header("Location: extrato_aberto.php");
		exit;
	}
}

$layout_menu = "financeiro";
$title = "Posição do Extrato - Ordens de Serviço";

include "cabecalho.php";
?>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}
</style>

<script LANGUAGE="JavaScript">

function Extrato(extrato) {
	var janela_extrato = null;
	janela_extrato = this.open('detalhe_extrato.php?extrato=' + extrato,'1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	janela_extrato.parentwin = self;
}

function Alterar(extrato) {
	var janela_extrato = null;
	janela_extrato = this.open('envio_new_financeiro.php?extrato=' + extrato,'1', 'height=150,width=300,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	janela_extrato.parentwin = self;
}

/*
function Extrato(extrato) {
	var janela_extrato = null;
	janela_extrato = this.open('imprime_extrato_detalhado.php?extrato=' + extrato,'1', 'height=400,width=750,location=no,scrollbars=yes,menubar=yes,toolbar=yes,resizable=no')
	janela_extrato.parentwin = self;
}
*/

function Obs(extrato) {
	var janela_extrato = null;
	janela_extrato = this.open('imprime_obs_extrato.php?extrato=' + extrato,'1', 'height=300,width=400,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	janela_extrato.parentwin = self;
}

</script>

<? include "javascript_pesquisas.php" ?>

<p>

<table width="100%" border="0" cellpadding="2" cellspacing="2" align="center" background="<?echo $fundo?>">
<tr>
	<td align="center" width="1%"><img src='imagens/pixel.gif' width='50' height='10'></td>
	
	<td align="center" width="100%" class="f_<?echo $css;?>_10">
			<b><?echo $msg;?></b>
	</td>
	
	<td align="center" width="1%"><img src='imagens/pixel.gif' width='50' height='10'></td>
</tr>
</table>

<p>

<?
if (strlen($btnacao) == 0 and strlen($codigo) == 0 ) {
	echo "<form name='frm_aprovacao' method='post' action='$PHP_SELF'>";

	echo "<table align='center' border='0' cellspacing='0' cellpadding='2'>";
	echo "<tr>";
	echo "<td colspan='4' class='menu_top'><div align='center'><b>Pesquisa Posto</b></div></td>";
	echo "</tr>";
	echo "<tr class='table_line'>";
	echo "<td width='10px'>&nbsp;</td>";
	echo "<td align='center'>Código</td>";
	echo "<td align='center'>Razão Social</td>";
	echo "<td width='10px'>&nbsp;</td>";
	echo "</tr>";
	echo "<tr class='table_line'>";
	echo "<td width='10px'>&nbsp;</td>";
	echo "<td align='center'><input type='text' name='codigo' size='14' maxlength='14' value='' class='frm'>&nbsp;<img src='imagens_admin/btn_lupa.gif' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_aprovacao.codigo,document.frm_aprovacao.nome,'codigo')\"></td>";
	echo "<td align='center'><input type='text' name='nome' size='50' maxlength='60' value='' class='frm'>&nbsp;<img src='imagens_admin/btn_lupa.gif' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_aprovacao.codigo,document.frm_aprovacao.nome,'nome')\"></td>";
	echo "<td width='10px'>&nbsp;</td>";
	echo "</tr>";
	echo "<tr class='table_line'>";
	echo "<input type='hidden' name='btnacao'>";
	echo "<td colspan='4' align='center'><img src='imagens_admin/btn_pesquisar_400.gif' onclick=\"javascript: if ( document.frm_aprovacao.btnacao.value == '' ) { document.frm_aprovacao.btnacao.value='BUSCAR'; document.frm_aprovacao.submit() ; } else { alert ('Aguarde submissão'); }\" style='cursor: pointer;' alt='Clique aqui para pesquisar'></td>";
	echo "</tr>";
	echo "</table>";

	echo "</form>";
}

if (strlen($btnacao) > 0) {
	$resultado = 0;
	
	$sql = "SELECT  distinct
					codigo_posto        ,
					nome          ,
					data_extrato  ,
					data_aprovacao,
					ordem         ,
					extrato   ,
					obs           ,
					total         ,
					financeiro
			FROM (
					(
						SELECT  tbl_posto_fabrica.codigo_posto                                                              ,
								tbl_posto.nome                                                                ,
								to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')          AS data_extrato  ,
								to_char(tbl_extrato.aprovado, 'DD/MM/YYYY')              AS data_aprovacao,
								date_trunc('day', tbl_extrato.data_geracao)              AS ordem         ,
								tbl_extrato.extrato                                                   ,
								tbl_os_extra.obs                                                           ,
								tbl_extrato.total                                        AS total         ,
								to_char(tbl_extrato_pagamento.data_pagamento, 'DD/MM/YYYY') AS financeiro
						FROM    tbl_os_extra
						JOIN    tbl_os      ON tbl_os.os            = tbl_os_extra.os
						JOIN    tbl_posto       ON tbl_os.posto             = tbl_posto.posto
						JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto   = tbl_posto.posto
													AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN    tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
						LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
						WHERE   tbl_os_extra.extrato NOTNULL
						AND     tbl_extrato.aprovado     NOTNULL
						AND     tbl_os.posto =  (SELECT tbl_posto.posto
												FROM tbl_posto
												JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
																		AND tbl_posto_fabrica.fabrica = $login_fabrica
												WHERE tbl_posto_fabrica.codigo_posto = '$codigo')
						AND     tbl_extrato_pagamento.data_pagamento ISNULL
						GROUP BY    tbl_posto_fabrica.codigo_posto            ,
									tbl_posto.nome              ,
									tbl_extrato.extrato ,
									tbl_extrato.data_geracao,
									tbl_os_extra.obs         ,
									tbl_extrato.aprovado    ,
									tbl_extrato.total       ,
									tbl_extrato_pagamento.data_pagamento
						ORDER BY    tbl_extrato.data_geracao
					)union(
						SELECT  tbl_posto_fabrica.codigo_posto                                                              ,
								tbl_posto.nome                                                                ,
								to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')          AS data_extrato  ,
								to_char(tbl_extrato.aprovado, 'DD/MM/YYYY')              AS data_aprovacao,
								date_trunc('day', tbl_extrato.data_geracao)              AS ordem         ,
								tbl_extrato.extrato                                                   ,
								tbl_os_extra.obs                                                           ,
								tbl_extrato.total                                        AS total         ,
								to_char(tbl_extrato_pagamento.data_pagamento, 'DD/MM/YYYY') AS financeiro
						FROM    tbl_os_sedex
						JOIN    tbl_posto           ON  tbl_os_sedex.posto_origem = tbl_posto.posto
						JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto   = tbl_posto.posto
													AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN    tbl_extrato ON tbl_os_sedex.extrato  = tbl_extrato.extrato
						LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
						WHERE   tbl_os_sedex.extrato NOTNULL
						AND     tbl_extrato.aprovado       NOTNULL
						AND     tbl_os_sedex.posto_origem = (SELECT tbl_posto.posto
																	FROM tbl_posto
																	JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
																							AND tbl_posto_fabrica.fabrica = $login_fabrica
																	WHERE tbl_posto_fabrica.codigo_posto = '$codigo')
						AND     tbl_extrato_pagamento.data_pagamento ISNULL
						GROUP BY    tbl_posto_fabrica.codigo_posto            ,
									tbl_posto.nome              ,
									tbl_extrato.extrato ,
									tbl_extrato.data_geracao,
									tbl_os_extra.obs         ,
									tbl_extrato.aprovado    ,
									tbl_extrato.total       ,
									tbl_extrato_pagamento.data_pagamento
						ORDER BY    tbl_extrato.data_geracao
					)union(
						SELECT  tbl_posto_fabrica.codigo_posto                                                              ,
								tbl_posto.nome                                                                ,
								to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')          AS data_extrato  ,
								to_char(tbl_extrato.aprovado, 'DD/MM/YYYY')              AS data_aprovacao,
								date_trunc('day', tbl_extrato.data_geracao)              AS ordem         ,
								tbl_extrato.extrato                                                   ,
								tbl_os_extra.obs                                                           ,
								tbl_extrato.total                                        AS total         ,
								to_char(tbl_extrato_pagamento.data_pagamento, 'DD/MM/YYYY') AS financeiro
						FROM    tbl_posto
						JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto   = tbl_posto.posto
													AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN    tbl_extrato         ON tbl_extrato.posto = tbl_posto.posto
						LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
						WHERE   tbl_extrato.aprovado NOTNULL
						AND     tbl_extrato.posto = (SELECT tbl_posto.posto
													FROM tbl_posto
													JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
																			AND tbl_posto_fabrica.fabrica = $login_fabrica
													WHERE tbl_posto_fabrica.codigo_posto = '$codigo')
						AND     tbl_extrato_pagamento.data_pagamento ISNULL
						GROUP BY    tbl_posto_fabrica.codigo_posto            ,
									tbl_posto.nome              ,
									tbl_extrato.extrato ,
									tbl_extrato.data_geracao,
									tbl_os_extra.obs         ,
									tbl_extrato.aprovado    ,
									tbl_extrato.total       ,
									tbl_extrato_pagamento.data_pagamento
						ORDER BY    tbl_extrato.data_geracao
					)
				) AS x
				ORDER BY ordem;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows($res) > 0) {
		echo "<table width='100%' border='0' cellpadding='2' cellspacing='2' align='center' background='$fundo'>";
		echo "<tr>";
		echo "<td><img src='imagens/pixel.gif' width='50' height='10'></td>";
		
		echo "<td bgcolor='$cor_forte' align='center' width='15%'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#FFFFFF' size='2'>&nbsp;</font>";
		echo "</td>";
		
		echo "<td bgcolor='$cor_forte' align='center' width='15%'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#FFFFFF' size='2'><b>Extrato</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='$cor_forte' align='center' width='15%'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#FFFFFF' size='2'><b>Obs.</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='$cor_forte' align='center' width='55%'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#FFFFFF' size='2'><b>Posto</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='$cor_forte' align='center' width='15%'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#FFFFFF' size='2'><b>Total</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='$cor_forte' align='center' width='15%'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#FFFFFF' size='2'><b>Aprovação</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='$cor_forte' align='center' width='15%'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#FFFFFF' size='2'><b>Financeiro</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='$cor_forte' align='center' width='15%'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#FFFFFF' size='2'><b>Recálculo</b></font>";
		echo "</td>";
		
		echo "<td><img src='imagens/pixel.gif' width='50' height='10'></td>";
		echo "</tr>";
		
		for ($x = 0; $x < @pg_numrows($res); $x++) {
			$codigo         = trim(pg_result($res,$x,codigo_posto));
			$nome           = trim(pg_result($res,$x,nome));
			$extrato        = trim(pg_result($res,$x,extrato));
			$data_extrato   = trim(pg_result($res,$x,data_extrato));
			$data_aprovacao = trim(pg_result($res,$x,data_aprovacao));
			$obs            = trim(pg_result($res,$x,obs));
			$total          = pg_result($res,$x,total);
			$financeiro     = trim(pg_result($res,$x,financeiro));
			
			
			$cor = "#E8E3E3";
			
			echo "<tr>";
			
			echo "<td><img src='imagens/pixel.gif' width='50' height='10'></td>";
			
			echo "<td bgcolor='$cor' align='center' width='15%'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>$extrato</font>";
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='center' width='15%'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><a href='#Extrato' OnClick = 'Extrato($extrato)'>$data_extrato</a></font>";
			echo "</td>";
			
			if (strlen($obs) > 0) {
				echo "<td bgcolor='$cor' align='center' width='15%'>";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><a href='#Obs' OnClick = 'Obs($extrato)'>Ver</a></font>";
				echo "</td>";
			}else{
				echo "<td bgcolor='$cor' align='center' width='15%'>";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>";
				echo "</td>";
			}
			
			echo "<td bgcolor='$cor' align='left' width='55%'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>$codigo - $nome</font>";
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='right' width='15%'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>". number_format($total,2,",","."). "</font>";
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='center' width='15%'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>$data_aprovacao</font>";
			echo "</td>";
			
			if (strlen($financeiro) == 0) {
				echo "<td bgcolor='#FFFFFF' align='center' width='15%'>";
				echo "<a href='$PHP_SELF?aprovar=$extrato'>";
				echo "<img src='imagens/btnAprovarAzul.gif' align='absmiddle' hspace='5' border='0'>";
				echo "</a>";
				echo "</td>";
			}else{
				echo "<td bgcolor='$cor' align='center' width='15%'>";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><a href='#Alterar' OnClick = 'Alterar($extrato)'>$financeiro</font>";
				echo "</td>";
			}
			
			echo "<td bgcolor='$cor' align='center' width='15%'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><a href='$PHP_SELF?extrato=$extrato&codigo=$codigo&btnacao=1&recalculo=ok'>Recálculo</a></font>";
			echo "</td>";
			
			echo "<td><img src='imagens/pixel.gif' width='50' height='10'></td>";
			
			echo "</tr>";
		}
		echo "</table>";
		$resultado = 1;
	}
	
	if ($resultado == 0) {
		echo "<table width='100%' border='0' cellpadding='2' cellspacing='2' align='center' background='$fundo'>";
		echo "<tr>";
		
		echo "<td><img src='imagens/pixel.gif' width='50' height='10'></td>";
		
		echo "<td bgcolor='#FFFFFF' align='center' width='100%'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Posto informado não possue extratos aprovados!</b></font>";
		echo "</td>";
		
		echo "<td><img src='imagens/pixel.gif' width='50' height='10'></td>";
		
		echo "</tr>";
		echo "</table>";
	}
}

echo "<p>";


include 'rodape.php';
?>