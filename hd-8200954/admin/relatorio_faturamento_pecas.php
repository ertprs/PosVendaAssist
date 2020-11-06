<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,auditoria";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "Relatório de peças faturadas";

include 'cabecalho.php';
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 11px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 11px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
}
</style>
<? include "javascript_pesquisas.php"; ?>
<? include "javascript_calendario.php"; ?>
<script>
	$(function(){
		$('input[rel=data]').datePicker({startDate:'01/01/2000'});
		$("input[rel=data]").maskedinput("99/99/9999");
	});
</script>
<script language="JavaScript">
function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa.php?forma=&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= campo;
		janela.descricao= campo2;
		janela.focus();
	}
}

</script>
<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>

<form name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">
<table width="450" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo">
		<td colspan="4">Preencha os campos para realizar a pesquisa.</td>
	</tr>
<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>Data Inicial</td>
		<td>Data Final</td>
</tr>
<tr class="Conteudo" bgcolor="#D9E2EF">
<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial;?>" rel="data"></TD>
<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final;?>" rel="data"></TD>
</tr>

<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>Código Posto</td>
		<td>Nome Posto</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>
			<input class='frm' type='text' name='posto_codigo' size='15' value='<? echo $posto_codigo;?>'>&nbsp;
			<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style='cursor:pointer' onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'codigo')">
		</td>
		<td>
			<input class='frm' type='text' name='posto_nome' size='25' value='<? echo $posto_nome;?>' >&nbsp;
			<img src='imagens_admin/btn_lupa.gif' style='cursor:pointer' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'nome')" style='cursor:pointer;'>
		</td>
	</tr>
		<tr bgcolor="#D9E2EF">
		<td colspan="2" align="center"><img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
	</table>


<?
$btn_acao = $_POST['acao'];

if(strlen($btn_acao)>0){

	$posto_nome    = $_POST['posto_nome'];
	$posto_codigo  = $_POST['posto_codigo'];
	$cond1 = " 1=1 ";
	if(strlen($posto_codigo)>0){
		$sql = "SELECT tbl_posto_fabrica.posto 
				from tbl_posto_fabrica
				WHERE codigo_posto='$posto_codigo'
				AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,posto);
			$cond1 = " tbl_pedido.posto = $posto ";
		}else{
			$erro .= "Posto não encontrado<br>";
		}
	}
	
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0 or $_POST["data_inicial_01"]=='dd/mm/aaaa') {
			$erro .= "Favor informar a data inicial para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0) ." 00:00:00";
			if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
		}
	}

	if (strlen($erro) == 0) {
		if (strlen($_POST["data_final_01"]) == 0 or $_POST["data_final_01"] == 'dd/mm/aaaa') {
			$erro .= "Favor informar a data final para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_final   = trim($_POST["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0) ." 23:59:59";
			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}

	if (strlen($erro) == 0) {

	$sql = "SELECT tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.posto,
					tbl_posto.nome,
					sum(tbl_pedido_item.qtde_faturada) as qtde_faturada
			FROM tbl_faturamento_item
			JOIN tbl_faturamento on tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
			JOIN tbl_pedido           on tbl_faturamento_item.pedido = tbl_pedido.pedido
			AND  tbl_pedido.tipo_pedido = 85
			JOIN tbl_pedido_item      on tbl_pedido_item.peca = tbl_faturamento_item.peca
			AND  tbl_pedido_item.pedido = tbl_faturamento_item.pedido
			JOIN tbl_posto on tbl_posto.posto = tbl_pedido.posto
			JOIN tbl_posto_fabrica    on tbl_posto_fabrica.posto = tbl_posto.posto 
			AND  tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_pedido.fabrica = $login_fabrica
			AND tbl_pedido.pedido_blackedecker notnull
			AND tbl_faturamento.emissao between '$aux_data_inicial' and '$aux_data_final'
			AND $cond1
			GROUP BY tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.posto,
					tbl_posto.nome
			ORDER by qtde_faturada desc";
	//echo $sql;
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<BR><BR><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='500'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='3' height='20'><font size='2'>TOTAL DE PEÇAS FATURADAS</font></td>";
		echo "</tr>";
	
		echo "<tr class='Titulo'>";
		echo "<td >Código</td>";
		echo "<td >Nome</td>";
		echo "<td >Qtde</td>";
		echo "</tr>";
	
		$total = pg_numrows($res);
		$total_pecas = 0;
		for ($i=0; $i<pg_numrows($res); $i++){
			$codigo_posto          = trim(pg_result($res,$i,codigo_posto));
			$posto                 = trim(pg_result($res,$i,posto));
			$nome_posto            = trim(pg_result($res,$i,nome));
			$qtde                  = trim(pg_result($res,$i,qtde_faturada));
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
			$total_pecas = $total_pecas + $qtde;
			echo "<tr class='Conteudo'align='center'>";
			echo "<td bgcolor='$cor' align='center' nowrap>$codigo_posto</td>";
			echo "<td bgcolor='$cor' align='left' nowrap>
			<a href='$PHP_SELF?posto=$posto&xdata_inicial=$aux_data_inicial&xdata_final=$aux_data_final'>$nome_posto</a></td>";
			echo "<td bgcolor='$cor' nowrap>$qtde&nbsp;</td>";
			echo "</tr>";
		}
		echo "<tr class='Conteudo'>";
		echo "<td colspan='2'><B>Total</b></td>";
		echo "<td >$total_pecas</td>";
		echo "</tr>";
		echo "</table>";
	}else{
	echo "<br><center>Nenhum resultado encontrado</center>";
	}
	}
}
if (strlen($erro) > 0) {
	?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $erro ?>
			
	</td>
</tr>
</table>
<?
}

$posto = $_GET['posto'];
$xdata_inicial = $_GET['xdata_inicial'];
$xdata_final =  $_GET['xdata_final'];
if(strlen($posto)>0 and strlen($xdata_inicial)>0  and strlen($xdata_final)>0){
	$sql = "select tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome
			from tbl_posto
			join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
			and tbl_posto_fabrica.fabrica = $login_fabrica
			where tbl_posto.posto = $posto";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$codigo_posto = pg_result($res,0,codigo_posto);
		$nome_posto = pg_result($res,0,nome);
	}

$sql = "SELECT tbl_peca.referencia as peca_referencia,
				tbl_peca.descricao as peca_descricao,
				tbl_pedido_item.qtde_faturada,
				tbl_faturamento.nota_fiscal,
				to_char(tbl_faturamento.emissao,'DD/MM/YYYY') as data_emissao,
				to_char(tbl_faturamento.saida,'DD/MM/YYYY') as data_saida,
				tbl_pedido_item.pedido
			FROM tbl_faturamento_item
			JOIN tbl_faturamento on tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
			JOIN tbl_pedido           on tbl_faturamento_item.pedido = tbl_pedido.pedido
			AND  tbl_pedido.tipo_pedido = 85
			JOIN tbl_pedido_item      on tbl_pedido_item.peca = tbl_faturamento_item.peca
			AND  tbl_pedido_item.pedido = tbl_faturamento_item.pedido
			JOIN tbl_peca on tbl_peca.peca = tbl_pedido_item.peca
			JOIN tbl_posto on tbl_posto.posto = tbl_pedido.posto
			JOIN tbl_posto_fabrica    on tbl_posto_fabrica.posto = tbl_posto.posto 
			AND  tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_pedido.fabrica = $login_fabrica
			AND tbl_pedido.pedido_blackedecker notnull
			AND tbl_pedido.posto = $posto
			AND tbl_faturamento.emissao between '$xdata_inicial' and '$xdata_final'
		ORDER by qtde_faturada desc";
//echo $sql; exit;
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<BR><BR><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='500'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='8' background='imagens_admin/azul.gif' height='20'><font size='2'>$codigo_posto - $nome_posto </font></td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
			echo "<td >Pedido</td>";
			echo "<td >Peça</td>";
			echo "<td >Nota Fiscal</td>";
			echo "<td >Data NF</td>";
			echo "<td >Data Saida</td>";
			echo "<td >Qtde</td>";
		echo "</tr>";
		for($i=0;pg_numrows($res)>$i;$i++){
			$peca_referencia          = trim(pg_result($res,$i,peca_referencia));
			$peca_descricao           = trim(pg_result($res,$i,peca_descricao));
			$qtde_faturada            = trim(pg_result($res,$i,qtde_faturada));
			$nota_fiscal              = trim(pg_result($res,$i,nota_fiscal));
			$data_emissao             = trim(pg_result($res,$i,data_emissao));
			$data_saida               = trim(pg_result($res,$i,data_saida));
			$pedido                   = trim(pg_result($res,$i,pedido));
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<tr  class='Conteudo'>";
			echo "<td  bgcolor='$cor' ><a href='pedido_admin_consulta.php?pedido=$pedido' target='blank'>$pedido</a></td>";
			echo "<td  bgcolor='$cor' align='left' nowrap >$peca_referencia - $peca_descricao</td>";
			echo "<td  bgcolor='$cor' >$nota_fiscal</td>";
			echo "<td  bgcolor='$cor' >$data_emissao</td>";
			echo "<td  bgcolor='$cor' >$data_saida</td>";
			echo "<td  bgcolor='$cor' >$qtde_faturada</td>";
		}
		echo "</table>";
	}
}

include "rodape.php" ;
?>