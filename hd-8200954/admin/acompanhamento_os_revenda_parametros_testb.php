<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center,gerencia";
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
			
			if ($busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
			}
			
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
			
			if ($busca == "codigo"){
				$sql .= " AND UPPER(tbl_produto.referencia) like UPPER('%$q%') ";
			}else{
				$sql .= " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
			}
			
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


$msg_erro = "";

$layout_menu = "gerencia";
$title = "ACOMPANHAMENTO DE OS DE REVENDA";

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

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	if (campo.value != "") {
		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.nome			= document.frm_pesquisa.nome_revenda;
		janela.cnpj			= document.frm_pesquisa.cnpj_revenda;
		janela.fone			= document.frm_pesquisa.revenda_fone;
		janela.cidade		= document.frm_pesquisa.revenda_cidade;
		janela.estado		= document.frm_pesquisa.revenda_estado;
		janela.endereco		= document.frm_pesquisa.revenda_endereco;
		janela.numero		= document.frm_pesquisa.revenda_numero;
		janela.complemento	= document.frm_pesquisa.revenda_complemento;
		janela.bairro		= document.frm_pesquisa.revenda_bairro;
		janela.cep			= document.frm_pesquisa.revenda_cep;
		janela.email		= document.frm_pesquisa.revenda_email;
		janela.focus();
	}
	else
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
}

</script>



<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

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



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

color: #7092BE
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}
</style>

<? include "javascript_pesquisas.php" ?>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<!--
<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>

<DIV ID="container" style="width: 100%; ">

<br>

<FORM name="frm_pesquisa" METHOD="GET" ACTION="acompanhamento_os_revenda_consulta_hmlg_testb.php">
<TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
<TR>
	<TD colspan="5" class="titulo_tabela">Parâmetros de Pesquisa</TD>
</TR>
<TR>
	<TD style="width: 10px">&nbsp;</TD>
	<TD colspan=4><INPUT TYPE="checkbox" NAME="chk_opt1" value="1">&nbsp; OS Lançadas Hoje</TD>
</TR>
<TR>
	<TD style="width: 10px">&nbsp;</TD>
	<TD colspan=4><INPUT TYPE="checkbox" NAME="chk_opt2" value="1">&nbsp; OS Lançadas Ontem</TD>
</TR>
<TR>
	<TD style="width: 10px">&nbsp;</TD>
	<TD colspan=4><INPUT TYPE="checkbox" NAME="chk_opt3" value="1">&nbsp; OS Lançadas Nesta Semana</TD>
</TR>
<TR>
	<TD style="width: 10px">&nbsp;</TD>
	<TD colspan=4><INPUT TYPE="checkbox" NAME="chk_opt4" value="1">&nbsp; OS Lançadas Neste Mês</TD>
</TR>
<TR>
	<TD colspan="5" style="text-align: center;"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onClick="document.frm_pesquisa.submit();" alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
<TR>
	<TD colspan="5"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD style="width: 50px">&nbsp;</TD>
	<TD><INPUT TYPE="checkbox" NAME="chk_opt5" value="1" width="80">&nbsp;Entre datas</TD>
	<TD align='left'>Data Inicial</TD>
	<TD align='left' colspan='2'>Data Final</TD>
</TR>
<TR>
	<TD style="width: 10px">&nbsp;</TD>
	<TD style="width: 80px">&nbsp;</TD>
	<TD align='left'><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value='' class="frm" ></TD>
	<TD align='left' colspan=2><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" value='' class="frm"></TD>
</TR>
<TR>
	<TD colspan="5"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="50" ><INPUT TYPE="checkbox" NAME="chk_opt6" value="1"> Posto</TD>
	<TD width="180" >Código do Posto</TD>
	<TD width="180" >Nome do Posto</TD>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: center;">&nbsp;</TD>
	<TD  align="left"><INPUT TYPE="text" NAME="codigo_posto" ID="codigo_posto" SIZE="15" class="frm"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')"></TD>
	<TD width="151"  style="text-align: left;"><INPUT TYPE="text" NAME="nome_posto" ID="nome_posto" size="30" class="frm"> <IMG src="imagens/lupa.png" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')"></TD>
	<TD width="19"  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" ><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="50" ><INPUT TYPE="checkbox" NAME="chk_opt7" value="1">Aparelho</TD>
	<TD width="100" >Referência</TD>
	<TD width="180" >Descrição</TD>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: center;">&nbsp;</TD>
	<TD  align="left"><INPUT TYPE="text" NAME="produto_referencia" ID="produto_referencia" SIZE="15" class="frm"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'referencia')"></TD>
	<TD  style="text-align: left;"><INPUT TYPE="text" NAME="produto_nome" ID="produto_nome" size="30" class="frm"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao')"></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" ><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="50" ><INPUT TYPE="checkbox" NAME="chk_opt8" value="1">Revenda</TD>
	<TD width="100" >CNPJ da revenda</TD>
	<TD width="180" >Nome da revenda</TD>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: center;">&nbsp;</TD>
	<TD  align="left"><INPUT TYPE="text" NAME="cnpj_revenda" SIZE="15" class="frm"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar revendas pelo código" onclick="javascript: fnc_pesquisa_revenda (document.frm_pesquisa.cnpj_revenda,'cnpj')"></TD>
	<TD  style="text-align: left;"><INPUT TYPE="text" NAME="nome_revenda" size="30" class="frm"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar pelo nome da revenda." onclick="javascript: fnc_pesquisa_revenda (document.frm_pesquisa.nome_revenda,'nome')"></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
<input type="hidden" name="revenda_fone" value="">
<input type="hidden" name="revenda_cidade" value="">
<input type="hidden" name="revenda_estado" value="">
<input type="hidden" name="revenda_endereco" value="">
<input type="hidden" name="revenda_cep" value="">
<input type="hidden" name="revenda_numero" value="">
<input type="hidden" name="revenda_complemento" value="">
<input type="hidden" name="revenda_bairro" value="">
<input type='hidden' name = 'revenda_email'>

</TR>
<TR>
	<TD colspan="5" ><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD  style="text-align: center;">&nbsp;</TD>
	<TD colspan="2" ><INPUT TYPE="checkbox" NAME="chk_opt9" value="1"> Não Finalizadas</TD>
	<TD  style="text-align: center;">&nbsp;</TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: left;">&nbsp;</TD>
	<TD  colspan=3><INPUT TYPE="checkbox" NAME="chk_opt10" value="1"> Numero da OS Revenda &nbsp;&nbsp;
		<INPUT TYPE="text" NAME="numero_os" size="17" class="frm"></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5"  style="text-align: center;"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onClick="document.frm_pesquisa.submit();" alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>

</TABLE>
</FORM>
<BR>

</div>

<? include "rodape.php" ?>
