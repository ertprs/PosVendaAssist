<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="cellcenter";
include "autentica_admin.php";
include 'funcoes.php';

$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);
$status      = trim($_POST["status"]);

if(strlen($btn_acao)>0 AND $select_acao == "gravar") {

	$qtde_hd     = $_POST["qtde_hd"];
	$select_acao = $_POST["select_acao"];
	$indice      = $_POST["indice"];

	if (strlen($indice)==0){
		$indice = 1;
	}

	for ($x=0;$x<$qtde_hd;$x++){

		$hd_chamado = $_POST["check_".$x];

		if (strlen($hd_chamado)>0 AND strlen($msg_erro) == 0){

			$res_hd = pg_exec($con,"BEGIN TRANSACTION");

			$data_nf_saida        = trim($_POST["data_nf_saida_".$x]);
			$numero_objeto        = trim($_POST["numero_objeto_".$x]);
			$data_pagamento       = trim($_POST["data_pagamento_".$x]);
			$valor_corrigido      = trim($_POST["valor_corrigido_".$x]);
			$data_retorno_produto = trim($_POST["data_retorno_produto_".$x]);

			$valor_corrigido      = str_replace(",",".",$valor_corrigido);

			if (strlen($numero_objeto) == 0){
				$xnumero_objeto = " NULL ";
			}else{
				$xnumero_objeto = "'".$numero_objeto."'";
			}

			$xdata_pagamento       = fnc_formata_data_pg($data_pagamento);
			$xdata_nf_saida        = fnc_formata_data_pg($data_nf_saida);
			$xdata_retorno_produto = fnc_formata_data_pg($data_retorno_produto);

			if(strlen($msg_erro) == 0 AND strlen($xdata_pagamento) > 0){
				if (strlen($valor_corrigido)==0){
					$valor_corrigido = 0;
				}
				$sql_2 = "UPDATE tbl_hd_chamado_troca SET 
									data_pagamento  = $xdata_pagamento, 
									valor_inpc      = $indice,
									valor_corrigido = $valor_corrigido 
							WHERE hd_chamado = $hd_chamado; ";
				#echo nl2br($sql_2);
				#echo "<br>";
				$res_2 = pg_exec($con,$sql_2);
			}

			if(strlen($msg_erro) == 0 AND strlen($xdata_nf_saida) > 0){
				$sql_2 = "UPDATE tbl_hd_chamado_troca SET data_nf_saida = $xdata_nf_saida WHERE hd_chamado = $hd_chamado; ";
				#echo nl2br($sql_2);
				#echo "<br>";
				$res_2 = pg_exec($con,$sql_2);
			}

			if(strlen($msg_erro) == 0 AND strlen($data_retorno_produto) > 0){
				$sql_2 = "UPDATE tbl_hd_chamado_troca SET data_retorno_produto = $xdata_retorno_produto WHERE hd_chamado = $hd_chamado; ";
				#echo nl2br($sql_2);
				#echo "<br>";
				$res_2 = pg_exec($con,$sql_2);
			}

			if(strlen($msg_erro) == 0 AND strlen($xnumero_objeto) > 0){
				$sql_2 = "UPDATE tbl_hd_chamado_troca SET numero_objeto = $xnumero_objeto WHERE hd_chamado = $hd_chamado; ";
				#echo nl2br($sql_2);
				#echo "<br>";
				$res_2 = pg_exec($con,$sql_2);
			}

			#echo "<hr>";

			if (strlen($msg_erro)==0){
				$res = pg_exec($con,"COMMIT TRANSACTION");
			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$layout_menu = "callcenter";
$title = "Relatório de Ressarcimento / Sedex Reverso";

include "cabecalho.php";

?>

<style type="text/css">

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

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

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
</style>

<script language="JavaScript">
function fnc_pesquisa_posto(campo, campo2, tipo) {
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


<script language="JavaScript">

	function date_onkeydown() {
	  if (window.event.srcElement.readOnly) return;
	  var key_code = window.event.keyCode;
	  var oElement = window.event.srcElement;
	  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
			var d = new Date();
			oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
							 String(d.getDate()).padL(2, "0") + "/" +
							 d.getFullYear();
			window.event.returnValue = 0;
		}
		if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
			if ((key_code > 47 && key_code < 58) ||
			  (key_code > 95 && key_code < 106)) {
				if (key_code > 95) key_code -= (95-47);
				oElement.value =
					oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
			}
			if (key_code == 8) {
				if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
					oElement.value = "dd/mm/aaaa";
				oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
					function ($0, $1, $2) {
						var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
						if (idx >= 5) {
							return $1 + "a" + $2;
						} else if (idx >= 2) {
							return $1 + "m" + $2;
						} else {
							return $1 + "d" + $2;
						}
					} );
				window.event.returnValue = 0;
			}
		}
		if (key_code != 9) {
			event.returnValue = false;
		}
	}

	var ok = false;
	var cont=0;
	function checkaTodos() {
		f = document.frm_pesquisa2;
		if (!ok) {
			for (i=0; i<f.length; i++){
				if (f.elements[i].type == "checkbox"){
					f.elements[i].checked = true;
					ok=true;
					if (document.getElementById('linha_'+cont)) {
						document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
					}
					cont++;
				}
			}
		}else{
			for (i=0; i<f.length; i++) {
				if (f.elements[i].type == "checkbox"){
					f.elements[i].checked = false;
					ok=false;
					if (document.getElementById('linha_'+cont)) {
						document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
					}
					cont++;
				}
			}
		}
	}

	function setCheck(theCheckbox,mudarcor,cor){
		if (document.getElementById(theCheckbox)) {
	//		document.getElementById(theCheckbox).checked = (document.getElementById(theCheckbox).checked ? false : true);
		}
		if (document.getElementById(mudarcor)) {
			document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
		}
	}

	function verificarAcao(combo){
		if (document.getElementById('observacao')){
			if (combo.value == '19'){
				document.getElementById('observacao').disabled = true;
			}else{
				document.getElementById('observacao').disabled = false;
			}
		}
	}

</script>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

		$("input[@rel='data']").maskedinput("99/99/9999");
	});
</script>


<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">

function mostraIndice (valor){
	if (valor == 'ressarcimento'){
		$('#indice').css('display','');
	}else{
		$('#indice').css('display','none');
	}
}

$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[2];
	}
	
	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});
</script>

<? include "javascript_pesquisas.php"; 


$btn_acao       = $_POST['btn_acao'];

if($btn_acao == 'Pesquisar'){

	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$natureza     = $_POST['natureza'];
	$indice       = $_POST['indice'];

	if (strlen($natureza)==0){
		$msg_erro .= "Informe a natureza";
	}

	if(strlen($data_inicial) > 0 and strlen($data_final) > 0) {
		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";

		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}
}


if(strlen($msg_erro) > 0){
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$x = explode('ERROR: ',$msg_erro);
		$msg_erro = $x[1];
	}

	echo "<p align='center' style='font-size: 14px; font-family: verdana;'><FONT COLOR='#FF0000'><b>$msg_erro</FONT></b></p>";
}
?>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="380" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" height="20">
		<td align="center" background='imagens_admin/azul.gif'>Selecione os parâmetros para pesquisa</td>
	</tr>
</table>

<table width="380" align="center" border="0" cellspacing="0" cellpadding="2">
<tr>
	<td colspan='4' class="table_line" style="width: 10px">&nbsp;</td>
</tr>
<tr>
	<td class="table_line" style="width: 10px">&nbsp;</td>
	<td class="table_line">Data Inicial</td>
	<td class="table_line">Data Final</td>
	<td class="table_line" style="width: 10px">&nbsp;</td>
</tr>
<tr>
	<td class="table_line" style="width: 10px">&nbsp;</td>
	<TD class="table_line" style="width: 185px"><center><input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm">
	<TD class="table_line" style="width: 185px"><center><input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm">
	<td class="table_line" style="width: 10px">&nbsp;</td>
</tr>
</table>

<table width="380" align="center" border="0" cellspacing="0" cellpadding="2">
<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='4'>&nbsp;</td>
</tr>

<tr align='left'>
	<td class="table_line">&nbsp;</td>
	<td class="table_line" style="size: 10px" align='left'><INPUT TYPE="radio" NAME="natureza" value='ressarcimento' <? if(trim($natureza) == 'ressarcimento') echo "checked='checked'"; ?> onClick='mostraIndice(this.value)'>Ressarcimento Financeiro</td>
	<td class="table_line" style="size: 10px" align='left'><INPUT TYPE="radio" NAME="natureza" value='sedex_reverso' <? if(trim($natureza) == 'sedex_reverso') echo "checked='checked'"; ?> onClick='mostraIndice(this.value)'>Sedex Reverso</td>
	<td class="table_line">&nbsp;</td>
</tr>

<tr align='left' id='indice'  <? if(trim($natureza) != 'ressarcimento') echo "style='display:none'"; ?>>
	<td class="table_line">&nbsp;</td>
	<td class="table_line" style="size: 10px" align='left'>Índice: <input type="text" name="indice" size="8" maxlength="5" value="<? if (strlen($indice)==0) $indice = 1; echo $indice ?>" class="frm"></td>
	<td class="table_line" style="size: 10px" align='left'>&nbsp;</td>
	<td class="table_line">&nbsp;</td>
</tr>
<tr align='left'>
	<td class="table_line">&nbsp;</td>
	<td class="table_line" style="size: 10px" align='left'><INPUT TYPE="radio" NAME="status" value='aberto' <? if(trim($status) == 'aberto') echo "checked='checked'"; ?>>Aberto</td>
	<td class="table_line" style="size: 10px" align='left'><INPUT TYPE="radio" NAME="status" value='fechado' <? if(trim($status) == 'fechado' OR strlen(trim($status)) ==0) echo "checked='checked'"; ?>>Fechado</td>
	<td class="table_line">&nbsp;</td>
</tr>
<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='4' align='center'><input type="submit" name="btn_acao" value="Pesquisar"></td>
</tr>
</table>

</form>


<?
if ($btn_acao == 'Pesquisar' and strlen($msg_erro)==0) {

	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado as callcenter,
					to_char(tbl_hd_chamado_extra.data_abertura,'DD/MM/YYYY') as abertura_callcenter,
					tbl_hd_chamado_extra.nome,
					tbl_hd_chamado_extra.endereco ,
					tbl_hd_chamado_extra.numero ,
					tbl_hd_chamado_extra.complemento ,
					tbl_hd_chamado_extra.bairro ,
					tbl_hd_chamado_extra.cep ,
					tbl_hd_chamado_extra.fone ,
					tbl_hd_chamado_extra.fone2 ,
					tbl_hd_chamado_extra.email ,
					tbl_hd_chamado_extra.cpf ,
					tbl_hd_chamado_extra.rg ,
					tbl_hd_chamado_extra.cliente ,
					tbl_hd_chamado_extra.consumidor_revenda,
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado,
					tbl_hd_chamado_extra.origem,
					tbl_admin.login as atendente,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data,
					tbl_hd_chamado.status,
					tbl_hd_chamado.categoria as natureza_operacao,
					tbl_posto.posto,
					tbl_hd_chamado.titulo as assunto,
					tbl_hd_chamado.categoria,
					tbl_produto.produto,
					tbl_produto.linha,
					tbl_produto.referencia as produto_referencia,
					tbl_produto.descricao as produto_nome,
					tbl_produto.voltagem,
					tbl_defeito_reclamado.defeito_reclamado,
					tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
					tbl_hd_chamado_extra.reclamado,
					tbl_hd_chamado_extra.os,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
					tbl_hd_chamado_extra.nota_fiscal,
					tbl_hd_chamado_extra.revenda,
					tbl_hd_chamado_extra.revenda_nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome as posto_nome,
					to_char(tbl_hd_chamado_extra.data_abertura_os,'DD/MM/YYYY') as data_abertura,
					tbl_hd_chamado_extra.receber_info_fabrica,
					tbl_os.sua_os as sua_os,
					tbl_hd_chamado_extra.abre_os,
					tbl_hd_chamado_extra.leitura_pendente,
					tbl_hd_chamado.atendente as atendente_pendente,
					tbl_hd_chamado_extra.defeito_reclamado_descricao as hd_extra_defeito,
					tbl_hd_chamado_troca.valor_produto,
					tbl_hd_chamado_troca.valor_inpc,
					tbl_hd_chamado_troca.valor_corrigido,
					tbl_hd_chamado_troca.numero_objeto,
					to_char(tbl_hd_chamado_troca.data_nf_saida,'DD/MM/YYYY')        AS data_nf_saida,
					to_char(tbl_hd_chamado_troca.data_pagamento,'DD/MM/YYYY')       AS data_pagamento,
					to_char(tbl_hd_chamado_troca.data_retorno_produto,'DD/MM/YYYY') AS data_retorno_produto,
					CURRENT_DATE - tbl_hd_chamado_extra.data_nf AS qtde_dias
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra USING(hd_chamado)
		JOIN tbl_hd_chamado_troca USING(hd_chamado)
		LEFT JOIN tbl_hd_chamado_extra_banco USING(hd_chamado)
		LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		JOIN tbl_admin  on tbl_hd_chamado.atendente = tbl_admin.admin
		LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto
		LEFT JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto  and tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
		LEFT JOIN tbl_revenda on tbl_revenda.revenda = tbl_hd_chamado_extra.revenda
		LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
		LEFT JOIN tbl_os on tbl_os.os = tbl_hd_chamado_extra.os
		WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica 
		AND   tbl_hd_chamado.status <> 'Cancelado'
		";

	if (strlen($status)> 0) {
		if ($status == 'aberto'){
			$sql .= " AND   tbl_hd_chamado.status = 'Resolvido' "; 
		}else{
			$sql .= " AND   tbl_hd_chamado.status <> 'Resolvido' "; 
		}
	}

	if (strlen($natureza)> 0) {
		$sql .=" AND tbl_hd_chamado.categoria =  '$natureza' "; 
	}

	if(strlen($xdata_inicial) > 0 and strlen($xdata_final) > 0){
		$sql .= " AND tbl_hd_chamado.data BETWEEN '$xdata_inicial' AND '$xdata_final'";
	}

	$sql .= " ORDER BY tbl_hd_chamado.data ASC,tbl_hd_chamado.admin ASC  ";

#echo nl2br($sql);
#exit;
	$res		= pg_exec($con,$sql);
	$qtde_hd	= pg_numrows($res);

	if($qtde_hd>0){

		echo "<BR><BR>";
		echo "<FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";
		echo "<input type='hidden' name='data_inicial' value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'   value='$data_final'>";
		echo "<input type='hidden' name='natureza'     value='$natureza'>";
		echo "<input type='hidden' name='indice'       value='$indice'>";

		echo "<table width='800' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";

		echo "<tr>";
		if($natureza =='ressarcimento' OR $natureza == 'sedex_reverso'){
			echo "<td bgcolor='#485989'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></td>";
		}
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Chamado</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Consumidor</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Fone</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>CPF/CNPJ</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Produto</B></font></td>";
		if ($login_fabrica ==59){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Voltagem</B></font></td>";
		}
	if ($natureza == 'ressarcimento'){
		#echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Defeito</B></font></td>";
		#echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Revenda</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Data NF</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Data Saída</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Número Objeto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Valor</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Dados Bancários</B></font></td>";
		#echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Código Spalla</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Data Pagamento</B></font></td>";
	}
	if ($natureza == 'sedex_reverso'){
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Número Objeto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Observações</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Data Retorno</B></font></td>";
	}
		echo "</tr>";

		$cores = '';

		for ($x=0; $x<$qtde_hd;$x++){
			$callcenter               = pg_result($res,$x,callcenter);
			$abertura_callcenter      = pg_result($res,$x,abertura_callcenter);
			$data_abertura_callcenter = pg_result($res,$x,data);
			$natureza_chamado         = pg_result($res,$x,natureza_operacao);
			$consumidor_nome          = pg_result($res,$x,nome);
			$cliente                  = pg_result($res,$x,cliente);
			$consumidor_cpf           = pg_result($res,$x,cpf);
			$consumidor_rg            = pg_result($res,$x,rg);
			$consumidor_email         = pg_result($res,$x,email);
			$consumidor_fone          = pg_result($res,$x,fone);
			$consumidor_fone2         = pg_result($res,$x,fone2);
			$consumidor_cep           = pg_result($res,$x,cep);
			$consumidor_endereco      = pg_result($res,$x,endereco);
			$consumidor_numero        = pg_result($res,$x,numero);
			$consumidor_complemento   = pg_result($res,$x,complemento);
			$consumidor_bairro        = pg_result($res,$x,bairro);
			$consumidor_cidade        = pg_result($res,$x,cidade_nome);
			$consumidor_estado        = pg_result($res,$x,estado);
			$consumidor_revenda       = pg_result($res,$x,consumidor_revenda);
			$origem                   = pg_result($res,$x,origem);
			$assunto                  = pg_result($res,$x,assunto);
			$sua_os                   = pg_result($res,$x,sua_os);
			$os                       = pg_result($res,$x,os);
			$data_abertura            = pg_result($res,$x,data_abertura);
			$produto                  = pg_result($res,$x,produto);
			$produto_referencia       = pg_result($res,$x,produto_referencia);
			$produto_nome             = pg_result($res,$x,produto_nome);
			$voltagem                 = pg_result($res,$x,voltagem);
			$serie                    = pg_result($res,$x,serie);
			$data_nf                  = pg_result($res,$x,data_nf);
			$nota_fiscal              = pg_result($res,$x,nota_fiscal);
			$revenda                  = pg_result($res,$x,revenda);
			$revenda_nome             = pg_result($res,$x,revenda_nome);
			$posto                    = pg_result($res,$x,posto);
			$posto_nome               = pg_result($res,$x,posto_nome);
			$defeito_reclamado        = pg_result($res,$x,defeito_reclamado);
			$reclamado                = pg_result($res,$x,reclamado);
			$status_interacao         = pg_result($res,$x,status);
			$atendente                = pg_result($res,$x,atendente);
			$receber_informacoes	  = pg_result($res,$x,receber_info_fabrica);
			$codigo_posto	          = pg_result($res,$x,codigo_posto);
			$linha         	          = pg_result($res,$x,linha);
			$abre_os                  = pg_result($res,$x,abre_os);
			$leitura_pendente         = pg_result($res,$x,leitura_pendente);
			$atendente_pendente       = pg_result($res,$x,atendente_pendente);
			$categoria                = pg_result($res,$x,categoria);
			$hd_extra_defeito         = pg_result($res,$x,hd_extra_defeito);
			$valor_produto            = pg_result($res,$x,valor_produto);
			$valor_inpc               = pg_result($res,$x,valor_inpc);
			$valor_corrigido          = pg_result($res,$x,valor_corrigido);
			$numero_objeto            = pg_result($res,$x,numero_objeto);
			$data_nf_saida            = pg_result($res,$x,data_nf_saida);
			$data_pagamento           = pg_result($res,$x,data_pagamento);
			$data_retorno_produto     = pg_result($res,$x,data_retorno_produto);
			$qtde_dias                = pg_result($res,$x,qtde_dias);

			/*Uma forma de controlar se foi pago ou nao. Se nao, o calculo é feito*/
			if (strlen($data_pagamento)==0){
				if ($qtde_dias > 0 and $valor_produto > 0){
					if ($indice > 0){
						$valor_corrigido = $valor_produto + ($valor_produto * ($qtde_dias/30) * $indice / 100);
					}else{
						$valor_corrigido = $valor_produto + ($valor_produto * ($qtde_dias/30) / 100);
					}
				}
			}
		
			
			$cores++;

			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';


			echo "<tr bgcolor='$cor' id='linha_$x'>";
			if($natureza =='ressarcimento' OR $natureza == 'sedex_reverso'){
				echo "<td align='center' width='0'>";
					echo "<input type='checkbox' name='check_$x' id='check_$x' value='$callcenter' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";
					if (strlen($msg_erro)>0){
						if (strlen($_POST["check_".$x])>0){
							echo " CHECKED ";
						}
					}
					echo ">";
				echo "</td>";
			}
			echo "<td style='font-size: 9px; font-family: verdana' nowrap >";
			echo "<a href='callcenter_interativo_new.php?callcenter=$callcenter'  target='_blank'>".$callcenter."</a>";
			echo "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$consumidor_nome."</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$consumidor_fone."</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$consumidor_cpf."</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$produto_referencia." - ".$produto_nome."</td>";
			if ($login_fabrica ==59){
				echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$voltagem."</td>";
			}

		if ($natureza == 'ressarcimento'){
			#echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$reclamado."</td>";
			#echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$revenda_nome."</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$data_nf."</td>";
			echo "<td align='center' nowrap style='font-size: 9px'>";
			if(strlen($data_nf_saida) > 0 ) {
				echo "<input type='hidden' name='data_nf_saida_$x' value='$data_nf_saida'>".$data_nf_saida;
			}else{
				echo "<input size='12' type='text' name='data_nf_saida_$x' class='frm' rel='data'>";
			}
			echo "</td>";

			echo "<td align='center' nowrap style='font-size: 9px'>";
			if(strlen($numero_objeto) > 0 ) {
				echo "<input type='hidden' name='numero_objeto_$x' value='$numero_objeto'>".$numero_objeto;
			}else{
				echo "<input size='12' type='text' name='numero_objeto_$x' class='frm'>";
			}
			echo "</td>";

			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><input type='hidden' name='valor_corrigido_$x' value='$valor_corrigido'>R$ ".number_format($valor_corrigido, 2, ',', ' ') ."</td>";

			$dados_bancarios = "";
			$sql = "SELECT 	banco            ,
							agencia          ,
							contay           ,
							nomebanco        ,
							favorecido_conta ,
							cpf_conta        ,
							tipo_conta       
					FROM tbl_hd_chamado_extra_banco
					WHERE hd_chamado = $callcenter";
			$resB = pg_exec($con,$sql);

			if(pg_numrows($resB)>0){
				$banco            = pg_result($resB,0,banco);
				$agencia          = pg_result($resB,0,agencia);
				$contay           = pg_result($resB,0,contay);
				$nomebanco        = pg_result($resB,0,nomebanco);
				$favorecido_conta = pg_result($resB,0,favorecido_conta);
				$cpf_conta        = pg_result($resB,0,cpf_conta);
				$tipo_conta       = pg_result($resB,0,tipo_conta);

				$dados_bancarios = "Banco: ".$banco."<br>
									Agência: ".$agencia."<br>
									Conta: ".$contay." - ".$tipo_conta."<br>
									".$nomebanco."<br>
									Favorecido: ".$favorecido_conta." - CPF: ".$tipo_conta."
									";
			}

			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$dados_bancarios."</td>";
			#echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$codigo_spalla."</td>";
		
			echo "<td align='center' nowrap style='font-size: 9px'>";
			if(strlen($data_pagamento) > 0 ) {
				echo "<input type='hidden' name='data_pagamento_$x' value='$data_pagamento'>".$data_pagamento;
			}else{
				echo "<input size='12' type='text' name='data_pagamento_$x' class='frm' rel='data'>";
			}
			echo "</td>";
		}
		if ($natureza == 'sedex_reverso'){
			echo "<td align='center' nowrap style='font-size: 9px'>";
			if(strlen($numero_objeto) > 0 ) {
				echo "<input type='hidden' name='numero_objeto_$x' value='$numero_objeto'>".$numero_objeto;
			}else{
				echo "<input size='12' type='text' name='numero_objeto_$x' class='frm'>";
			}
			echo "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$reclamado."</td>";
			echo "<td align='center' nowrap style='font-size: 9px'>";
			if(strlen($data_retorno_produto) > 0 ) {
				echo "<input type='hidden' name='data_retorno_produto_$x' value='$data_retorno_produto'>".$data_retorno_produto;
			}else{
				echo "<input size='12' type='text' name='data_retorno_produto_$x' class='frm' rel='data'>";
			}
			echo "</td>";
		}

			echo "</tr>";
		}

		echo "<input type='hidden' name='qtde_hd' value='$x'>";
		echo "<tr>";

		echo "<td height='20' bgcolor='#485989' colspan='100%' align='left'> &nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
		if(trim($natureza) == 'ressarcimento' or trim($natureza) == 'sedex_reverso'){
			echo "<select name='select_acao' size='1' class='frm' onChange='verificarAcao(this)'>";
			echo "<option value=''></option>";
			echo "<option value='gravar'";  if ($_POST["select_acao"] == "gravar")  echo " selected"; echo ">GRAVAR</option>";
			echo "</select>";
			#echo "&nbsp;&nbsp; <font color='#FFFFFF'><b>Motivo:<b></font> <input class='frm' type='text' name='observacao' id='observacao' size='50' maxlength='900' value=''  "; if ($_POST["select_acao"] == "19") echo " DISABLED "; echo ">";
			echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
			echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick=\"avascript: 
				if (document.frm_pesquisa2.select_acao.value==''){
					alert('Selecione a ação');
				}else{
					document.frm_pesquisa2.submit();
				}\" style='cursor: hand;' border='0' align='absmiddle'></td>";
		}else {
			echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
			echo "<input type='hidden' name='select_acao' value='gravar_nf_envio'>";
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'></td>";
		}
		echo "</table>";
		echo "</form>";
		echo "<p>Chamados encontrados: $qtde_hd</p>";
	}else{ 
		echo "<center><p>Nenhum chamado encontrado.</p></center><br>";
	}
	$msg_erro = '';
}

include "rodape.php" ?>