<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	
	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
			
			$sql .= ($busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";
			
			$sql .=  ($busca == "codigo") ? " AND UPPER(tbl_produto.referencia) like UPPER('%$q%') ": " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
			
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$produto    = trim(pg_result($res,$i,produto));
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					echo "$produto|$descricao|$referencia";
					echo "\n";
				}
			}
		}
	}
	exit;
}


$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

$layout_menu = "callcenter";
$title = "RELAÇÃO DE PEDIDOS LANÇADOS";
#$body_onload = "javascript: document.frm_os.condicao.focus()";

include "cabecalho.php";

?>
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


function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
}
</script>


<? include "javascript_pesquisas.php"; ?>
<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>-->


<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="javascript" src="js/assist.js"></script>


<script language='javascript' src='ajax.js'></script>
<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}
			
	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#nome_posto").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#nome_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#nome_posto").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[2]) ;
		//alert(data[2]);
	});

	
	/* Busca por Produto */
	$("#produto_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#produto_nome").result(function(event, data, formatted) {
		$("#produto_referencia").val(data[2]) ;
	});

	/* Busca pelo Nome */
	$("#produto_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#produto_referencia").result(function(event, data, formatted) {
		$("#produto_nome").val(data[1]) ;
		//alert(data[2]);
	});

});
</script>

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

table.tabela tr td{
	font-family: verdana; 
	font-size: 11px; 
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
</style>

<? include "javascript_pesquisas.php" ?>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>

<!-- Valida formularios -->
<script type="text/javascript" language="javascript">
function fcn_valida_formDatas()
{
	f = document.frm_pesquisa1;
/*	if(f.data_inicial_01.value.length < 10)
	{
		alert('Digite a Data Inicial');
		f.data_inicial_01.focus();
		return false;
	}
	if(f.data_final_01.value.length < 10)
	{
		alert('Digite a Data Final');
		f.data_final_01.focus();
		return false;
	}
	if(f.codigo_posto.value == "")
	{
		alert('Digite o Código do Posto');
		f.codigo_posto.focus();
		return false;
	}
	if(f.nome_posto.value == "")
	{
		alert('Digite o Nome do Posto');
		f.nome_posto.focus();
		return false;
	}
*/	
	f.submit();
}

function fcn_valida_formDatas2() 
{
	f = document.frm_pesquisa2;
/*
	// verifica se algum radio foi selecionado
	var total = 0;
	var max = f.radioDatas.length;
	for (var idx = 0; idx < max; idx++)
	{
		if (eval("f.radioDatas[" + idx + "].checked") == true)
		{
			total += 1;
		}
	}
	if(total == 0)
	{
		alert('Selecione uma das opções');
		return false;
	}else{
		if(f.radioDatas['0'].checked == true)
		{
			if(f.data_inicial_02.value.length < 10)
			{
				alert('Digite a Data Inicial');
				f.data_inicial_02.focus();
				return false;
			}
			if(f.data_final_02.value.length < 10)
			{
				alert('Digite a Data Final');
				f.data_final_02.focus();
				return false;
			}
		}
		if(f.radioDatas['5'].checked == true)
		{
			if(f.codigo_posto.value == "")
			{
				alert('Digite o Código do Posto');
				f.codigo_posto.focus();
				return false;
			}
			if(f.nome_posto.value == "")
			{
				alert('Digite o Nome do Posto');
				f.nome_posto.focus();
				return false;
			}
		}
	}
*/
	f.submit();
}
</script>

<DIV ID="container" style="width: 100%; ">

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->

<?
if (($login_fabrica == 8) || ($login_fabrica == 15) || ($login_fabrica == 51) || ($login_fabrica == 59) || ($login_fabrica == 65) || ($login_fabrica == 43) || ($login_fabrica == 80 ) || ($login_fabrica == 88 )){
?>
<FORM name="frm_pesquisa1" METHOD="POST" ACTION="pedido_nao_exportado_consulta.php">
<br>
	<TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2">
		<TR>
			<TD class="menu_top" style="text-align:center;font-weight:bold;">
				Exibe todos pedidos não exportados
			</TD>
		</TR>
		<TR>
			<TD class="table_line" style="text-align: center;">
				<IMG src="imagens/btn_exibirpedidosnaoexportados.gif"
					 alt="Preencha as opções e clique aqui para pesquisar"
				 onclick="javascript: fcn_valida_formDatas();" style="cursor:pointer ">
			</TD>
		</TR>
	</TABLE>
</form>
<BR>

<FORM name="frm_pesquisa3" METHOD="POST" ACTION="pedido_nao_faturado_consulta.php">
	<TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2">
		<TR>
			<TD class="menu_top" style="text-align:center;font-weight:bold;">
				Exibe todos pedidos exportados e não faturados
			</TD>
		</TR>
		<TR>
			<TD class="table_line" style="text-align:center;cursor:pointer">
				<IMG src="imagens/btn_exibirpedidosnaofaturados.gif"
                     alt="Preencha as opções e clique aqui para pesquisar"
				 onclick="javascript:document.frm_pesquisa3.submit();"></TD>
		</TR>
	</TABLE>
</form>
<BR>

<?
}
?>

<FORM name="frm_pesquisa2" METHOD="GET" ACTION="pedido_consulta.php">

<TABLE width="700" class="formulario" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="4" class="titulo_tabela">Parâmetros de Pesquisa</TD>
</TR>
<TR>
	<TD style="width: 90px">&nbsp;</TD>
	<TD colspan="3" ><INPUT TYPE="checkbox" NAME="chk_opt1" value="1" id='chk_opt1'><label for='chk_opt1'>&nbsp;Pedidos Lançados Hoje</label></TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD colspan="3"><INPUT TYPE="checkbox" NAME="chk_opt2" value="2">&nbsp;Pedidos Lançados Ontem</TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD colspan="3"><INPUT TYPE="checkbox" NAME="chk_opt3" value="3">&nbsp;Pedidos Lançados Nesta Semana</TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD colspan="3"><INPUT TYPE="checkbox" NAME="chk_opt4" value="3">&nbsp;Pedidos Lançados Na Semana Anterior</TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD colspan="3"><INPUT TYPE="checkbox" NAME="chk_opt5" value="4">&nbsp;Pedidos Lançados Neste Mês</TD>
</TR>
<TR><TD COLSPAN="4">&nbsp;</TD></TR>
<TR>
	<TD colspan="4" style="text-align: center;"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" onclick="javascript: fcn_valida_formDatas2();" alt="Preencha as opções e clique aqui para pesquisar" value="&nbsp;"></TD>
</TR>
<? if ($login_fabrica == 1) { ?>
<TR>
	<TD colspan="4"><hr></TD>
</TR>
<TR>
	<TD colspan="4" class="table_line">
		<TABLE width="98%" align="center" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<TD class="table_line"><input type="radio" name="tipo_pedido" value="" checked>Todos</TD>
				<TD class="table_line"><input type="radio" name="tipo_pedido" value="87|peca">Garantia Peças</TD>
				<TD class="table_line"><input type="radio" name="tipo_pedido" value="87|produto">Garantia Produtos</TD>
				<TD class="table_line"><input type="radio" name="tipo_pedido" value="86">Faturado</TD>
				<TD class="table_line"><input type="radio" name="tipo_pedido" value="94">Locador</TD>
			</TR>
		</TABLE>
	</TD>
</TR>
<? } ?>
<TR>
	<TD colspan="4"><hr></TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD><INPUT TYPE="checkbox" NAME="chk_opt6" value="5" nowrap>&nbsp;Entre Datas</TD>
	<TD>Data Inicial</TD>
	<TD>Data Final</TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD>&nbsp;</TD>
	<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" class="frm"></TD>
	<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" class="frm"></TD>
</TR>
<TR>
	<TD colspan="4" class="table_line" ><hr></TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD><INPUT TYPE="checkbox" NAME="chk_opt7" value="6" class="frm">&nbsp;Posto</TD>
	<TD>Código do Posto</TD>
	<TD>Posto Autorizado</TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD>&nbsp;</TD>
	<TD align="left"><INPUT TYPE="text" class="frm" NAME="codigo_posto" id="codigo_posto" SIZE="10" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa2.codigo_posto,document.frm_pesquisa2.nome_posto,'codigo')" <? } ?>><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa2.codigo_posto,document.frm_pesquisa2.nome_posto,'codigo')"></TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" class="frm" NAME="nome_posto" ID="nome_posto" SIZE="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa2.codigo_posto,document.frm_pesquisa2.nome_posto,'nome')" <? } ?>><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa2.codigo_posto,document.frm_pesquisa2.nome_posto,'nome')"></TD>
</TR>
<TR>
	<TD colspan="4"><hr></TD>
</TR>
<? if ($login_fabrica == 1 or $login_fabrica == 3 or $login_fabrica == 80 ) { ?>
<TR>
	<TD>&nbsp;</TD>
	<TD><INPUT TYPE="checkbox" NAME="chk_opt8" value="7">&nbsp;Peça</TD>
	<TD>Referência</TD>
	<TD>Descrição</TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD>&nbsp;</TD>
	<TD align="left"><INPUT TYPE="text" class="frm" NAME="peca_referencia" SIZE="10"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_peca ( document.frm_pesquisa2.peca_referencia, document.frm_pesquisa2.peca_descricao , 'referencia')"></TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" class="frm" NAME="peca_descricao" size="15"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_peca ( document.frm_pesquisa2.peca_referencia, document.frm_pesquisa2.peca_descricao , 'descricao')"></TD>
</TR>
<? }else{ ?>
<TR>
	<TD>&nbsp;</TD>
	<TD><INPUT TYPE="checkbox" NAME="chk_opt8" value="7">&nbsp;Aparelho</TD>
	<TD>Referência</TD>
	<TD>Descrição</TD>
</TR>
<TR>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<!--
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="peca_referencia" SIZE="10"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_peca (document.frm_pesquisa2.peca_referencia, document.frm_pesquisa2.peca_descricao, 'referencia')"></TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="peca_descricao" size="15"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="fnc_pesquisa_peca (document.frm_pesquisa2.peca_referencia, document.frm_pesquisa2.peca_descricao, 'descricao')"></TD>
	-->
	<TD align="left"><INPUT TYPE="text" class="frm" NAME="produto_referencia" ID="produto_referencia" SIZE="10" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto(document.frm_pesquisa2.produto_referencia, document.frm_pesquisa2.produto_nome, 'referencia')" <? } ?>><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_produto(document.frm_pesquisa2.produto_referencia, document.frm_pesquisa2.produto_nome, 'referencia')"></TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" class="frm" NAME="produto_nome" ID="produto_nome" size="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto(document.frm_pesquisa2.produto_referencia, document.frm_pesquisa2.produto_nome, 'descricao')" <? } ?>><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_produto(document.frm_pesquisa2.produto_referencia, document.frm_pesquisa2.produto_nome, 'descricao')"></TD>
</TR>
<? } ?>
<TR>
	<TD colspan="4"><hr></TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD colspan="2"><INPUT TYPE="checkbox" NAME="chk_opt9" value="13">&nbsp;Número do Pedido</TD>
	<TD align="left"><INPUT TYPE="text" class="frm" NAME="numero_pedido" size="17"></TD>
</TR>

<? if ($login_fabrica == 72) { #HD 280384 ?>
<TR>
	<TD colspan="4"><hr></TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD colspan="2"><INPUT TYPE="checkbox" NAME="chk_opt13" value="estado">&nbsp;Estado</TD>
	<TD align="left">
		<select name="estado" size="1" class="frm">
			<option value="" <? if (strlen($estado) == 0) echo "selected"; ?>></option>
			<option value="AC" <? if ($estado == "AC") echo "selected"; ?>>AC - Acre</option>
			<option value="AL" <? if ($estado == "AL") echo "selected"; ?>>AL - Alagoas</option>
			<option value="AM" <? if ($estado == "AM") echo "selected"; ?>>AM - Amazonas</option>
			<option value="AP" <? if ($estado == "AP") echo "selected"; ?>>AP - Amapá</option>
			<option value="BA" <? if ($estado == "BA") echo "selected"; ?>>BA - Bahia</option>
			<option value="CE" <? if ($estado == "CE") echo "selected"; ?>>CE - Ceará</option>
			<option value="DF" <? if ($estado == "DF") echo "selected"; ?>>DF - Distrito Federal</option>
			<option value="ES" <? if ($estado == "ES") echo "selected"; ?>>ES - Espírito Santo</option>
			<option value="GO" <? if ($estado == "GO") echo "selected"; ?>>GO - Goiás</option>
			<option value="MA" <? if ($estado == "MA") echo "selected"; ?>>MA - Maranhão</option>
			<option value="MG" <? if ($estado == "MG") echo "selected"; ?>>MG - Minas Gerais</option>
			<option value="MS" <? if ($estado == "MS") echo "selected"; ?>>MS - Mato Grosso do Sul</option>
			<option value="MT" <? if ($estado == "MT") echo "selected"; ?>>MT - Mato Grosso</option>
			<option value="PA" <? if ($estado == "PA") echo "selected"; ?>>PA - Pará</option>
			<option value="PB" <? if ($estado == "PB") echo "selected"; ?>>PB - Paraíba</option>
			<option value="PE" <? if ($estado == "PE") echo "selected"; ?>>PE - Pernambuco</option>
			<option value="PI" <? if ($estado == "PI") echo "selected"; ?>>PI - Piauí</option>
			<option value="PR" <? if ($estado == "PR") echo "selected"; ?>>PR - Paraná</option>
			<option value="RJ" <? if ($estado == "RJ") echo "selected"; ?>>RJ - Rio de Janeiro</option>
			<option value="RN" <? if ($estado == "RN") echo "selected"; ?>>RN - Rio Grande do Norte</option>
			<option value="RO" <? if ($estado == "RO") echo "selected"; ?>>RO - Rondônia</option>
			<option value="RR" <? if ($estado == "RR") echo "selected"; ?>>RR - Roraima</option>
			<option value="RS" <? if ($estado == "RS") echo "selected"; ?>>RS - Rio Grande do Sul</option>
			<option value="SC" <? if ($estado == "SC") echo "selected"; ?>>SC - Santa Catarina</option>
			<option value="SE" <? if ($estado == "SE") echo "selected"; ?>>SE - Sergipe</option>
			<option value="SP" <? if ($estado == "SP") echo "selected"; ?>>SP - São Paulo</option>
			<option value="TO" <? if ($estado == "TO") echo "selected"; ?>>TO - Tocantins</option>
		</select>
	</TD>
</TR>
<? } ?>

<? if ($login_fabrica == 1) { ?>
<TR>
	<TD colspan="5"><hr></TD>
</TR>
<TR>

	<TD>&nbsp;</TD>
	<TD colspan="2"><INPUT TYPE="checkbox" NAME="chk_opt10" value="finalizado">&nbsp;Pedido Não Finalizado</TD>

	<TD colspan="2"><INPUT TYPE="checkbox" NAME="chk_opt11" value="promocional">&nbsp;Pedido Promocional</TD>

</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD colspan="2"><INPUT TYPE="checkbox" NAME="chk_opt12" value="sedex">&nbsp;Pedido Sedex</TD>
	<TD>&nbsp;</TD>
</TR>
<? } ?>
<? if($login_fabrica == 3){ ?>
<TR>
	<TD colspan="4"><hr></TD>
</TR>
<TR>
	<TD colspan="4">
		<TABLE width="80%" align="center" border="0" cellspacing="0" cellpadding="0">
			<TR>
				<TD><input type="radio" name="tipo_pedido" value="" checked> Todos</TD>
				<TD><input type="radio" name="tipo_pedido" value="3"> Garantia</TD>
				<TD><input type="radio" name="tipo_pedido" value="2"> Faturado</TD>
			</TR>
		</TABLE>
	</TD>
</TR>
<? } ?>
<TR>
	<TD colspan="4" style="text-align: center;">&nbsp;</TD>
</TR>

<? if(in_array($login_fabrica,array(51,45,24,85))){ ?>
<TR>
	<TD colspan="4"><hr></TD>
</TR>
<tr>
	<td colspan="4" align="center">
		&nbsp;&nbsp;&nbsp;&nbsp;Status Pedido&nbsp;
		<?
			if($login_fabrica==45 or $login_fabrica == 85){
				$cond_status = " status_pedido IN(1, 2, 3, 4, 5, 8, 9, 14) ";
			}else if($login_fabrica==51){
				$cond_status = " status_pedido IN(1, 2, 4, 5, 7, 8, 11, 12, 13, 14) ";
			}else{
				$cond_status = " 1=1 ";
			}

			$sqlS = "SELECT status_pedido,
							descricao
					 FROM tbl_status_pedido
					 WHERE $cond_status;";
			#echo $sqlS;
			$resS = pg_exec($con, $sqlS);

			if(pg_numrows($resS)>0){
				echo "<select name='status_pedido'>";
					echo "<option value=''></option>";
				for($s=0; $s<pg_numrows($resS); $s++){
					$status_pedido    = pg_result($resS, $s, status_pedido);
					$status_descricao = pg_result($resS, $s, descricao);
					echo "<option value='$status_pedido'>$status_descricao</option>";
				}
				echo "</select>";
			}
		?>
	</td>
</tr>
<TR>
	<TD colspan="4">&nbsp;</TD>
</TR>
<? } ?>

<TR>
	<TD colspan="4" style="text-align: center;"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" onclick="javascript: fcn_valida_formDatas2();" alt="Preencha as opções e clique aqui para pesquisar" value="&nbsp;"></TD>
</TR>
</TABLE>
</FORM>

<? include "rodape.php" ?>