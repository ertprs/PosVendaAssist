<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";

#Programa desenvolvido para Britânia solicitado no chamado 42726 - 08/10/2008

$sql = "SELECT posto_fabrica
		FROM tbl_fabrica
		WHERE fabrica = $login_fabrica ";
$res2 = pg_exec ($con,$sql);
$posto_da_fabrica = pg_result ($res2,0,0);

#Postos abaixo NÃO COBRAR PEÇAS
/*RETIRADO CONFORME SOLICITADO PELO TULIO (VISITA A BRITANIA) 01/06/2009 -  CONVERSA NO CHAT COM IGOR*/
//$postos_permitidos_novo_processo = array(0 => '0',1 => '6976', 2 => '20397', 3 => '4044', 4 => '1267', 5 => '6458', 6 => '710', 7 => '5037', 8 => '1752', 9 => '4311', 10 => '1537',11 => '6359');
$postos_permitidos_novo_processo = array(0 => '9999');

if (strlen($_POST["acao"]) > 0 ) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0 )  $acao = strtoupper($_GET["acao"]);

if (strlen($acao) > 0 && $acao == "PESQUISAR") {

	if (strlen(trim($_POST["data_inicial"])) > 0) $data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0) $data_inicial = trim($_GET["data_inicial"]);

	if (strlen(trim($_POST["data_final"])) > 0) $data_final = trim($_POST["data_final"]);
	if (strlen(trim($_GET["data_final"])) > 0) $data_final = trim($_GET["data_final"]);

	if (strlen(trim($_POST["codigo_posto"])) > 0) $codigo_posto = trim($_POST["codigo_posto"]);
	if (strlen(trim($_GET["codigo_posto"])) > 0)  $codigo_posto = trim($_GET["codigo_posto"]);

	if (strlen($codigo_posto)>0){
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto AS cod, tbl_posto.nome as nome, tbl_posto.posto as posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica=$login_fabrica
				AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res)>0){
			$posto_codigo = pg_result ($res,0,cod);
			$posto_nome   = pg_result ($res,0,nome);
			$posto        = pg_result ($res,0,posto);
			$sql_posto = " AND tbl_extrato_lgr.posto = $posto";
		}else{
			$sql_posto = " AND 1=2 ";
		}
	}


	if (strlen($data_inicial) > 0 AND strlen($data_final) > 0) {
		$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
		if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
		if (strlen($erro) == 0)                    $aux_data_inicial = @pg_result ($fnc,0,0);

		$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
		if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
		if (strlen($erro) == 0)                    $aux_data_final = @pg_result ($fnc,0,0);

		if (strlen($erro) == 0){
			$sql_data = " AND tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:01' AND '$aux_data_final 23:59:59'";
		}
	}
}


$layout_menu = "auditoria";
$title = "Relatório de Não Preechimento do LGR";
?>
<?
include "cabecalho.php";
?>

<style type="text/css">

.PesquisaTabela tbody td{
	text-align: left;
	font-weight: bold;
}

</style>

<?
include "javascript_pesquisas.php";
include "javascript_calendario.php";
?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>


<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<br>

<? if (strlen($erro) > 0) { ?>
<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" class="Error">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">

<TABLE width="600" align="center" border="0" cellspacing="0" cellpadding="2" class='PesquisaTabela'>
	<CAPTION>Relatório do Não Preechimento do LGR</CAPTION>
	<TBODY>
	<TR>
		<TD>Data Inicial *</TD>
		<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial" class="frm" id="data_inicial" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" ></TD>
		<TD>Data Final *</TD>
		<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final" class="frm" id="data_final" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" ></TD>
	</TR>
	<TR>
		<TD>Código Posto:</TD>
		<TD><input class="frm" type="text" name="codigo_posto" size="10" value="<? echo $codigo_posto ?>" >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></TD>
		<TD>Razão Social</TD>
		<TD><input class="frm" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></A></TD>
	</TR>
	<TR>
		<td COLSPAN='4'><p>(*) Data do extrato</p></td>
	</TR>

	</TBODY>
	<TR>
		<TD colspan="4" align='center'>
			<center>
			<input type='hidden' name='btn_finalizar' value='0'>
			<IMG src="imagens_admin/btn_pesquisar_400.gif" onClick="
			if (document.frm_relatorio.acao.value=='PESQUISAR')
				alert('Aguarde submissão');
			else{
				document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();
			}"  style="cursor:pointer " alt='Clique AQUI para pesquisar'>
			</center>
		</TD>
	</TR>
</TABLE>

<br>

<?
if (strlen($acao) > 0 && strlen($erro) == 0) {

	$sql = "SELECT tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome,
					tbl_extrato.extrato,
					TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao
			FROM tbl_extrato
			JOIN tbl_posto USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN (		SELECT  DISTINCT tbl_extrato_lgr.posto, tbl_extrato_lgr.extrato
						FROM tbl_extrato_lgr
						JOIN tbl_extrato USING(extrato)
						JOIN tbl_peca ON tbl_peca.peca = tbl_extrato_lgr.peca
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						LEFT JOIN tbl_extrato_devolucao ON tbl_extrato_devolucao.extrato     = tbl_extrato.extrato
						LEFT JOIN tbl_faturamento       ON tbl_faturamento.extrato_devolucao = tbl_extrato.extrato AND tbl_faturamento.distribuidor = tbl_extrato_lgr.posto
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND tbl_faturamento.posto                   IS NULL
						AND tbl_extrato_devolucao.extrato_devolucao IS NULL
						/*AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'*/
						AND tbl_extrato_lgr.posto <> 6359
						AND (
							(tbl_extrato.extrato > 240000
							AND tbl_extrato.posto IN (".implode(",",$postos_permitidos_novo_processo).")
							AND tbl_peca.produto_acabado IS TRUE
							) OR (
							tbl_extrato.extrato > 240000
							AND tbl_extrato.posto NOT IN (".implode(",",$postos_permitidos_novo_processo).")
							) OR (
							tbl_extrato.extrato < 240000
							)
						)
						$sql_posto
						$sql_data
						/*AND tbl_extrato.data_geracao > '2008-08-01'*/
						/*AND    (tbl_peca.devolucao_obrigatoria IS TRUE OR tbl_peca.produto_acabado IS TRUE)*/
						AND (tbl_extrato_lgr.qtde_nf IS NULL OR tbl_extrato_lgr.qtde_nf = 0)
			) X ON X.extrato  = tbl_extrato.extrato
			ORDER BY tbl_posto.nome,tbl_extrato.data_geracao";
	if ($ip=='200.246.168.156'){
		#echo nl2br($sql);
	}
	echo nl2br($sql);
	#exit;
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table style='font-family: verdana ; font-size: 10px; border-collapse: collapse' align='center'  bordercolor='#d2e4fc' border='1'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='11' height='25'>RELAÇÃO DE POSTOS QUE NÃO PREENCHERAM O LGR</td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td>Código Posto</td>";
		echo "<td>Nome Posto</td>";
		echo "<td>Extrato</td>";
		echo "<td>Data do Extrato</td>";
		echo "</tr>";

		$posto_ant = "";
		$qtde_resultado = pg_numrows($res);

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$codigo_posto	= trim(pg_result($res,$i,codigo_posto));
			$nome			= trim(pg_result($res,$i,nome));
			$extrato		= trim(pg_result($res,$i,extrato));
			$data_geracao	= trim(pg_result($res,$i,data_geracao));

			if($cor=="#F1F4FA")     $cor = '#F7F5F0';
			else                    $cor = '#F1F4FA';

			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td nowrap align='center'>";if ($codigo_posto!=$posto_ant) echo $codigo_posto; echo "</td>";
			echo "<td nowrap align='left'>";  if ($codigo_posto!=$posto_ant) echo $nome;         echo "</td>";
			echo "<td nowrap align='center'>".$extrato."</td>";
			echo "<td nowrap align='center'>".$data_geracao ."</td>";
			echo "</tr>";

			$posto_ant = $codigo_posto;

		}
		echo "</table>";
		echo "<br>";
		echo "<center><p>Total: $qtde_resultado Extratos<p></center>";
	}else{
		echo "<br><br><center><b class='Conteudo'>Nenhum resultado encontrada</b></center><br><br>";
	}
}

include "rodape.php";
?>
