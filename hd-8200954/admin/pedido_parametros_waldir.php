<?php

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
					$cnpj         = trim(pg_result($res,$i,cnpj));
					$nome         = trim(pg_result($res,$i,nome));
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

if($login_fabrica <> 108 and $login_fabrica <> 111){
$layout_menu = "callcenter";
} else {
$layout_menu = "gerencia";
}

$title = "RELAÇÃO DE PEDIDOS LANÇADOS";


include "cabecalho.php";

die('321');

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

function fnc_pesquisa_posto_tela(tipo) {
	if (tipo == "codigo" ) {
		var xcampo = $('input[name=codigo_posto]').val();
	}

	if (tipo == "nome" ) {
		var xcampo = $('input[name=nome_posto]').val();
	}
	var campo  = document.getElementById('codigo_posto');
	var campo2 = document.getElementById('nome_posto');

	if (xcampo) {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else {
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}


function fnc_pesquisa_peca_tela (tipo) {
	var url = "";
	if (tipo == "referencia" ) {
		var xcampo = $('input[name=peca_referencia]').val();
	}

	if (tipo == "descricao" ) {
		var xcampo = $('input[name=peca_descricao]').val();
	}
		var campo  = document.getElementById('peca_referencia');
		var campo2 = document.getElementById('peca_descricao');

	if (xcampo) {
		var url = "";
		url               = "peca_pesquisa_2.php?campo=" + xcampo + "&tipo=" + tipo ;
		janela            = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia = campo;
		janela.descricao  = campo2;
		janela.focus();
	}else{
		alert("Informe toda ou parte da informação para a pesquisa");
	}
}
</script>


<? include "javascript_pesquisas.php"; ?>
<? //include "javascript_calendario.php"; ?>



<?php include '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */ ?>






<script type="text/javascript">
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

<script type="text/javascript">
	$(function(){
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").mask("99/99/9999");
		$("#data_final_01").mask("99/99/9999");
	});
</script>

<!-- Valida formularios -->
<script type="text/javascript">
function fcn_valida_formDatas()
{
	$("form[name=frm_pesquisa1]").submit();
}

function fcn_valida_formDatas2() 
{
	var certo = 0;
	var pesquisa = $("input[name=frm_submit]").val();
	if (pesquisa == "frm2_p2")
	{
		//VALIDAÇÃO DE PREENCHIMENTO DAS DATASA E/OU NRO DO PEDIDO
		if ($("input[name=chk_opt1]:checked").val() || $("input[name=chk_opt2]:checked").val() || $("input[name=chk_opt3]:checked").val() || $("input[name=chk_opt4]:checked").val() || $("input[name=chk_opt5]:checked").val() || ($("input[name=chk_opt6]:checked").val() && $("input[name=data_inicial_01]").val() && $("input[name=data_final_01]").val() ) || ( $("input[name=chk_opt9]:checked").val() && $("input[name=numero_pedido]").val() ) ) 
		{
			certo = 1;
		}else{
			certo = 0;
			var msg = "Selecione algum período ou o número do pedido para realizar este tipo de pesquisa";
		}
		//VALIDAÇÃO DE PREENCHIMENTO DO POSTO

		if ( ( $("input[name=chk_opt7]").attr('checked') == true && ( $("input[name=codigo_posto]").val().length == 0 ) ) ){
			
			certo = 0;
			var msg = "\n Pesquise o posto para efetuar a pesquisa";
		
		}else if ( ( $("input[name=chk_opt7]").attr('checked') == true && ( $("input[name=codigo_posto]").val().length > 0 ) ) && certo == 1 ) {
			
			certo = 1;
		
		}

		//VALIDAÇÃO DE PREENCHIMENTO DE PEÇA/PRODUTO
		if ( ( $("input[name=chk_opt8]").attr('checked') == true  ) ){

			<? if ($login_fabrica <> 6){ ?>
				
				if ( $("input[name=peca_referencia]").val().length == 0 ){
					certo = 0;
					var msg = "\n Pesquise a peça para efetuar a pesquisa";
				}else{
					certo = 1;
				}			
			
			<?php }else{ ?>

				if ( $("input[name=produto_referencia]").val().length == 0 ){
					certo = 0;
					var msg = "\n Pesquise o produto para efetuar a pesquisa";
				}else{
					certo = 1;
				}

			<? } ?>
		}

		if (certo == 0){

			$("#div_erro").html("<h1 align='center' style='color: #ffffff'>"+msg+"</h1>");
			$("#div_erro").show("slow");
			
		}else{

			$("form[name=frm_pesquisa]").submit();

		}

	}

}

$(document).ready(function(){


	$("input[name^=chk_opt]").click(function(){

		if ($(this).attr("checked")){
			
			var name = $(this).attr('name');
			var rel  = $(this).attr('rel'); 
			var nchk = $("input[name^=chk_opt]").length;

			if (rel < 6)
			{
				
				for (i = 1; i <= 6; i++)
				{
					if ($("input[name=chk_opt"+i+"]").attr("name") != name)
					{
						$("input[name=chk_opt"+i+"]").removeAttr("checked");
					}
				}
				$("input[name=chk_opt9]").removeAttr("checked");

			}
			
			
			if (rel == 6 || rel == 9)
			{

				for (i = 1; i <= 5; i++)
				{

					$("input[name=chk_opt"+i+"]").removeAttr("checked");

				}

				if (rel == 6){

					$("input[name=chk_opt9]").removeAttr("checked");

				}else if (rel = 9){

					$("input[name=chk_opt6]").removeAttr("checked");

				}

			}

		}

	});

});
</script>

<DIV ID="container" style="width: 100%; ">
<?
$sqlT = "SELECT COUNT(*) as total FROM tbl_fabrica WHERE fabrica = $login_fabrica AND fatura_manualmente IS TRUE";
$resT = pg_exec($con,$sqlT);

if(pg_numrows($resT) > 0){
	$total = pg_result($resT,0,0);
	if($total > 0 && $login_fabrica != 14){
?>
		<FORM name="frm_pesquisa1" METHOD="POST" ACTION="pedido_nao_exportado_consulta.php">
		<br>
			<TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2">
				<TR>
					<TD class="titulo_tabela" style="text-align:center;font-weight:bold;">
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
		<?php //if($login_fabrica != 88){ // HD 870865 - A Orbis apenas utilizará a tela de Pedidos não exportados para poder informar se o pedido poderá ser exportado ou não, já o faturamento é integrado?>
		<form name="frm_pesquisa3" method="POST" action="pedido_nao_faturado_consulta.php" id="frm_pesquisa3">
			<TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2">
				<TR>
					<TD class="titulo_tabela" style="text-align:center;font-weight:bold;">
						Exibe todos pedidos exportados e não faturados
					</TD>
				</TR>
				<TR>
					<td class="table_line" style="text-align:center;cursor:pointer">
						<img src="imagens/btn_exibirpedidosnaofaturados.gif" alt="Preencha as opções e clique aqui para pesquisar" onclick="document.getElementById('frm_pesquisa3').submit();">
					</td>
				</TR>
			</TABLE>
		</form>
		<BR>
		<?php// } ?>

<?
	}
}
?>
<center>
	<div id="div_erro" class="msg_erro" style="display: none; width: 700px;">
		
	</div>
</center>
<FORM name="frm_pesquisa" METHOD="GET" ACTION="pedido_consulta.php">

<input type="hidden" name="frm_submit" id="frm_submit" value="">
<TABLE width="700" class="formulario" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="4" class="titulo_tabela">Parâmetros de Pesquisa</TD>
</TR>
<TR>
	<TD style="width: 90px">&nbsp;</TD>
	<TD colspan="3" ><INPUT TYPE="checkbox" NAME="chk_opt1" value="1" id='chk_opt1' rel="1"><label for='chk_opt1'>&nbsp;Pedidos Lançados Hoje</label></TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD colspan="3"><INPUT TYPE="checkbox" NAME="chk_opt2" value="2" rel="2">&nbsp;Pedidos Lançados Ontem</TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD colspan="3"><INPUT TYPE="checkbox" NAME="chk_opt3" value="3" rel="3">&nbsp;Pedidos Lançados Nesta Semana</TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD colspan="3"><INPUT TYPE="checkbox" NAME="chk_opt4" value="3" rel="4">&nbsp;Pedidos Lançados Na Semana Anterior</TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD colspan="3"><INPUT TYPE="checkbox" NAME="chk_opt5" value="4" rel="5">&nbsp;Pedidos Lançados Neste Mês</TD>
</TR>
<TR><TD COLSPAN="4">&nbsp;</TD></TR>
<TR>
</TR>
</table>

<TABLE width="700" class="formulario" align="center" border="0" cellspacing="0" cellpadding="2">
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
	<TD><INPUT TYPE="checkbox" NAME="chk_opt6" value="5" nowrap rel="6">&nbsp;Entre Datas</TD>
	<TD>Data Inicial</TD>
	<TD>Data Final</TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD>&nbsp;</TD>
	<TD><INPUT size="12" maxlength="10" type="text" NAME="data_inicial_01" id="data_inicial_01" class="frm"></TD>
	<TD><INPUT size="12" maxlength="10" type="text" NAME="data_final_01" id="data_final_01" class="frm"></TD>
</TR>
<TR>
	<TD colspan="4" class="table_line" ><hr></TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD><input type="checkbox" name="chk_opt7" value="6" class="frm" rel="7">&nbsp;Posto</TD>
	<TD>Código do Posto</TD>
	<TD>Posto Autorizado</TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD>&nbsp;</TD>
	<TD align="left"><INPUT TYPE="text" class="frm" NAME="codigo_posto" id="codigo_posto" SIZE="10" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto_tela ('codigo')" <? } ?>><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto_tela ('codigo')"></TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" class="frm" NAME="nome_posto" ID="nome_posto" SIZE="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto_tela ('nome')" <? } ?>><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto_tela ('nome')"></TD>
</TR>
<TR>
	<TD colspan="4"><hr></TD>
</TR>
<? if ($login_fabrica <> 6){ ?>
<TR>
	<TD>&nbsp;</TD>
	<TD><INPUT TYPE="checkbox" NAME="chk_opt8" value="7" rel="8">&nbsp;Peça</TD>
	<TD>Referência</TD>
	<TD>Descrição</TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD>&nbsp;</TD>
	<TD align="left">
			<INPUT TYPE="text" class="frm" name="peca_referencia" id="peca_referencia" size="10">
			<IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_peca_tela ( 'referencia')">
	</TD>
	<TD class="table_line" align="left">
		<INPUT TYPE="text" class="frm" name="peca_descricao" id="peca_descricao" size="15">
		<IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="fnc_pesquisa_peca_tela ( 'descricao')">
	</TD>
</TR>
<? }else{ ?>
<TR>
	<TD>&nbsp;</TD>
	<TD><INPUT TYPE="checkbox" NAME="chk_opt8" value="7" rel="8">&nbsp;Aparelho</TD>
	<TD>Referência</TD>
	<TD>Descrição</TD>
</TR>
<TR>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD align="left"><INPUT TYPE="text" class="frm" NAME="produto_referencia" ID="produto_referencia" SIZE="10" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto(document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_nome, 'referencia')" <? } ?>><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_produto(document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_nome, 'referencia')"></TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" class="frm" NAME="produto_nome" ID="produto_nome" size="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto(document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_nome, 'descricao')" <? } ?>><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_produto(document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_nome, 'descricao')"></TD>
</TR>
<? } ?>
<TR>
	<TD colspan="4"><hr></TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD colspan="2"><INPUT TYPE="checkbox" NAME="chk_opt9" value="13" rel="9">&nbsp;Número do Pedido</TD>
	<TD align="left"><INPUT TYPE="text" class="frm" NAME="numero_pedido" size="17"></TD>
</TR>

<? if ($login_fabrica == 72) { #HD 280384 ?>
<TR>
	<TD colspan="4"><hr></TD>
</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD colspan="2"><INPUT TYPE="checkbox" NAME="chk_opt13" value="estado" rel="13">&nbsp;Estado</TD>
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
	<TD colspan="2"><INPUT TYPE="checkbox" NAME="chk_opt10" value="finalizado" rel="10">&nbsp;Pedido Não Finalizado</TD>

	<TD colspan="2"><INPUT TYPE="checkbox" NAME="chk_opt11" value="promocional" rel="11">&nbsp;Pedido Promocional</TD>

</TR>
<TR>
	<TD>&nbsp;</TD>
	<TD colspan="2"><INPUT TYPE="checkbox" NAME="chk_opt12" value="sedex" rel="12">&nbsp;Pedido Sedex</TD>
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
	<TD colspan="4" style="text-align: center;"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" onclick="javascript: $('#frm_submit').val('frm2_p2'); fcn_valida_formDatas2();" alt="Preencha as opções e clique aqui para pesquisar" value="&nbsp;"></TD>
</TR>
</TABLE>
</FORM>

<? include "rodape.php" ?>
