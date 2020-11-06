<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

include "funcoes.php";
include "monitora.php";

$sql = "SELECT posto_fabrica
		FROM tbl_fabrica
		WHERE fabrica = $login_fabrica ";
$res2 = pg_exec ($con,$sql);
$posto_da_fabrica = pg_result ($res2,0,0);

if (strlen($_POST["acao"]) > 0 ) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0 )  $acao = strtoupper($_GET["acao"]);

if (strlen($acao) > 0 && $acao == "PESQUISAR") {


	if (strlen(trim($_GET["tipo_retorno"])) > 0)  $tipo_retorno = trim($_GET["tipo_retorno"]);
	if (strlen(trim($_POST["tipo_retorno"])) > 0) $tipo_retorno = trim($_POST["tipo_retorno"]);

	if (strlen(trim($_POST["data_inicial"])) > 0) $x_data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0) $x_data_inicial = trim($_GET["data_inicial"]);

	if ($x_data_inicial=='dd/mm/aaaa') $x_data_inicial="";
	
	$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
	
	if (strlen(trim($_POST["data_final"])) > 0) $x_data_final = trim($_POST["data_final"]);
	if (strlen(trim($_GET["data_final"])) > 0)  $x_data_final = trim($_GET["data_final"]);

	if ($x_data_final=='dd/mm/aaaa') $x_data_final="";
	
	$x_data_final   = fnc_formata_data_pg($x_data_final);
	
	if (strlen($x_data_inicial) > 0 && $x_data_inicial != "null") {
		$x_data_inicial = str_replace("'", "", $x_data_inicial);
		$dia_inicial = substr($x_data_inicial, 8, 2);
		$mes_inicial = substr($x_data_inicial, 5, 2);
		$ano_inicial = substr($x_data_inicial, 0, 4);
		$data_inicial = $dia_inicial . "/" . $mes_inicial . "/" . $ano_inicial;
	}
	
	if (strlen($x_data_final) > 0 && $x_data_final != "null") {
		$x_data_final = str_replace("'", "", $x_data_final);
		$dia_final = substr($x_data_final, 8, 2);
		$mes_final = substr($x_data_final, 5, 2);
		$ano_final = substr($x_data_final, 0, 4);
		$data_final = $dia_final . "/" . $mes_final . "/" . $ano_final;
	}
	
	if (strlen(trim($_POST["codigo_posto"])) > 0) $codi_posto = trim($_POST["codigo_posto"]);
	if (strlen(trim($_GET["codigo_posto"])) > 0)  $codi_posto = trim($_GET["codigo_posto"]);
	
	if (strlen($peca_referencia)>0){
		$sql_adicional_2 = " AND tbl_peca.referencia = '$peca_referencia' ";
		$sql = "SELECT  tbl_peca.referencia as ref, tbl_peca.descricao as desc, tbl_peca.peca as peca
			FROM tbl_peca
			WHERE tbl_peca.fabrica=$login_fabrica
			$sql_adicional_2";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res)>0){
			$peca_referencia = pg_result ($res,0,ref);
			$peca_descricao  = pg_result ($res,0,desc);
			$peca  = pg_result ($res,0,peca);
			$sql_adicional_2 = " AND tbl_peca.peca = $peca";
		}
	}

	if (strlen($codi_posto)>0){
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto AS cod, tbl_posto.nome as nome, tbl_posto.posto as posto
				FROM tbl_posto 
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica=$login_fabrica
				AND tbl_posto_fabrica.codigo_posto = '$codi_posto'";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res)>0){
			$posto_codigo = pg_result ($res,0,cod);
			$posto_nome   = pg_result ($res,0,nome);
			$posto        = pg_result ($res,0,posto);
			$sql_adicional = " AND tbl_faturamento.distribuidor = $posto";
		}else{
			$sql_adicional = " AND 1=2 ";
		}
	}

	if (strlen($x_data_inicial)>0 AND $x_data_inicial!='null' AND strlen($x_data_final)>0 AND $x_data_final!='null' ){
		$sql_adicional_3 = " AND tbl_faturamento.emissao BETWEEN '$x_data_inicial' AND '$x_data_final'";
	}

	if ($tipo_retorno == "1"){
		$sql_adicional_peca = " AND tbl_peca.devolucao_obrigatoria IS TRUE ";
	}elseif ($tipo_retorno == "2"){
		$sql_adicional_peca = " AND tbl_peca.devolucao_obrigatoria IS NOT TRUE ";
	}else{
		$sql_adicional_peca = " ";
	}

}

$layout_menu = "gerencia";
$title = "Relatório de Devolução de Peças Pendentes";
?>

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
<script>
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

</script>

<?
include "cabecalho.php";
?>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script language="javascript">

function fnc_pesquisa_peca_lista(peca_referencia, peca_descricao, tipo) {
	var url = "";
	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?peca=" + peca_referencia.value + "&tipo=" + tipo + "&exibe=/assist/admin/relatorio_intervencao.php";
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?descricao=" + peca_descricao.value + "&tipo=" + tipo + "&exibe=/assist/admin/relatorio_intervencao.php";
	}
	if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.preco		= document.frm_relatorio.preco_null;
		janela.focus();
	}else{
		alert("Digite pelo menos 3 caracteres!");
	}
}
</script>

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
<input type="hidden" name="preco_null">
<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Relatório de Devolução de Peças em Garantia</td>
	</tr>
	

	<tr>
		<td bgcolor='#DBE5F5'>
	
			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>
				<tr width='100%' >
					<td colspan='4' align='left' height='20'> Relatório de Peças com Devolução Pendente<br><br>
					</td>	
				</tr>

				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Código Posto:&nbsp;</td>
					<td colspan='2' align='left'>
						<input class="frm" type="text" name="codigo_posto" size="10" value="<? echo $codi_posto ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></A>
					</td>	
				</tr>
				<tr>
					<td colspan='2' align='right'>Razão Social:&nbsp;</td>
					<td colspan='2' align='left'><input class="frm" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
				</tr>

<!--
				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Data Inicial:&nbsp;</td>
					<td colspan='2' align='left'>
						<input class='frm' type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_inicial; ?>">
						
					</td>	
				</tr>
				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Data Final:&nbsp;</td>
					<td colspan='2' align='left'>
						<input class='frm' type="text" name="data_final" id="data_final" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final; else ?>">
					</td>	
				</tr>
-->
				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Peças:&nbsp;</td>
					<td colspan='2' align='left'>
						<input type='radio' name='tipo_retorno' value='' <? if ($tipo_retorno=='') echo "checked"; ?>>Todas Peças&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input type='radio' name='tipo_retorno' value='1' <? if ($tipo_retorno=='1') echo "checked"; ?>>Retornáveis
						<input type='radio' name='tipo_retorno' value='2' <? if ($tipo_retorno=='2') echo "checked"; ?>>Não Retornáveis
					</td>	
				</tr>
<!--
				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Peça:&nbsp;</td>
					<td colspan='2' align='left'>
						<input class='frm' type="text" name="peca_referencia" value="<? echo $peca_referencia ?>" size="10" maxlength="20"><a href="javascript: fnc_pesquisa_peca_lista (window.document.frm_relatorio.peca_referencia,window.document.frm_relatorio.peca_descricao,'referencia')"><IMG SRC="imagens/lupa.png" align="absmiddle"></a>
						<input class='frm' type="text" name="peca_descricao" value="<? echo $peca_descricao ?>" size="25" maxlength="50"><a href="javascript: fnc_pesquisa_peca_lista (window.document.frm_relatorio.peca_referencia,window.document.frm_relatorio.peca_descricao,'descricao')"><IMG SRC="imagens/lupa.png" align="absmiddle" ></a>
					</td>	
				</tr>
-->
				<tr bgcolor="#D9E2EF">
					<td colspan="4" align="center" ><br><img border="0" src="imagens/btn_pesquisar_400.gif"
					onClick="if (document.frm_relatorio.acao.value=='PESQUISAR')
					alert('Aguarde submissão');
					else{
					document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();}" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
				</tr>

			</table>
		</td>
	</tr>
</table>

<br>

<?
if (strlen($acao) > 0 && strlen($erro) == 0) {
	//echo "<p align='center'>Aguarde, processando...</p>";
	//flush();

	if (strlen($posto)>0) {
		$posto = " AND tbl_os.posto=$posto";
	}

		$sql =  "
			SELECT  tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome,
					tbl_faturamento_item.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					SUM(tbl_faturamento_item.qtde - CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END) as qtde,
					tbl_faturamento_item.preco,
					SUM(tbl_faturamento_item.preco * tbl_faturamento_item.qtde - CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END) as total,
					tbl_faturamento.nota_fiscal,
					TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') as emissao
			FROM tbl_faturamento
			JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
			JOIN tbl_peca             ON tbl_peca.peca                    = tbl_faturamento_item.peca
			JOIN tbl_extrato          ON tbl_extrato.extrato              = tbl_faturamento.extrato_devolucao
			JOIN tbl_posto            ON tbl_posto.posto                  = tbl_faturamento.distribuidor
			JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.posto          = tbl_posto.posto
			WHERE tbl_faturamento.fabrica = $login_fabrica
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			AND tbl_faturamento.distribuidor  IS NOT NULL
			AND tbl_faturamento.distribuidor <> 6359
			AND tbl_faturamento.posto <> 6359
			AND tbl_faturamento.posto = $posto_da_fabrica
			AND (tbl_faturamento_item.qtde - CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END)>0
			AND tbl_faturamento.conferencia IS NOT NULL
			AND tbl_faturamento.devolucao_concluida IS NOT TRUE 
			$sql_adicional
			$sql_adicional_2
			$sql_adicional_3
			$sql_adicional_peca
			GROUP BY tbl_posto_fabrica.codigo_posto,tbl_posto.nome,tbl_faturamento_item.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_faturamento_item.preco,tbl_faturamento.nota_fiscal, tbl_faturamento.emissao
			ORDER BY tbl_faturamento.emissao,tbl_faturamento.nota_fiscal ASC,tbl_faturamento_item.peca DESC";

	#if($ip=='200.246.168.156') echo nl2br($sql); 
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) > 0) {
		echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='11'>RELAÇÃO DE PEÇAS PENDENTES</td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td>Código Posto</td>";
		echo "<td>Nome Posto</td>";
		echo "<td>NF</td>";
		echo "<td>Data NF</td>";
		echo "<td>Peça</td>";
		echo "<td>Qtde</td>";
		echo "<td>Preço</td>";
		echo "<td>Total</td>";
		echo "</tr>";

		$posto_ant = "";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$codigo_posto	= trim(pg_result($res,$i,codigo_posto));
			$nome			= trim(pg_result($res,$i,nome));
			$peca			= trim(pg_result($res,$i,peca));
			$referencia		= trim(pg_result($res,$i,referencia));
			$descricao		= trim(pg_result($res,$i,descricao));
			$qtde			= trim(pg_result($res,$i,qtde));
			$preco			= trim(pg_result($res,$i,preco));
			$total			= trim(pg_result($res,$i,total));
			$nota_fiscal	= trim(pg_result($res,$i,nota_fiscal));
			$emissao		= trim(pg_result($res,$i,emissao));

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td nowrap align='left'>";if ($nome!=$posto_ant) echo $codigo_posto; echo "</td>";
			echo "<td nowrap align='left'>";if ($nome!=$posto_ant) echo $nome; echo "</td>";
			echo "<td nowrap align='left'>".$nota_fiscal."</td>";
			echo "<td nowrap align='left'>".$emissao."</td>";
			echo "<td nowrap align='left'>".$referencia . " - ".$descricao."</td>";
			echo "<td nowrap align='center'>".$qtde ."</td>";
			echo "<td nowrap align='right'>".number_format($preco,2,",",".")."</td>";
			echo "<td nowrap align='right'>".number_format($total,2,",",".")."</td>";
			echo "</tr>";

			$posto_ant = $nome;

		}
		echo "</table>";
		echo "<br>";
	}else{
		echo "<br><br><center><b class='Conteudo'>Nenhuma pendência encontrada</b></center><br><br>";
	}
}

include "rodape.php";
?>
