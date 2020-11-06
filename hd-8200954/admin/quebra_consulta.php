<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "CONSULTA RELATÓRIO DE QUEBRA";

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

</style>

<? include "javascript_pesquisas.php" ?>

<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<table width="400" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td>
		<font face='Arial, Verdana, Times, Sans' size='2' color='#FF0000'>
		<b><? echo $msg ?></b>
		</font>
	</td>
</tr>
</table>

<TABLE width="400" align="center" border="1" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="5" class="menu_top"><div align="center"><b>Selecione os critérios para pesquisa</b></div></TD>
</TR>

<!-- ========================= PERÍODO ============================ -->
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" rowspan='2' width='100px'>Período:&nbsp;</TD>
	<TD class="table_line">Data Inicial</TD>
	<TD class="table_line">Data Final</TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 185px"><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" onkeydown="date_onkeydown()" value="" language="javascript" onfocus="if (this.value=='') this.value='dd/mm/aaaa'">&nbsp;</TD>
	<TD class="table_line" style="width: 185px"><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" onkeydown="date_onkeydown()" value="" language="javascript" onfocus="if (this.value=='') this.value='dd/mm/aaaa'">&nbsp;</TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" colspan="5"><hr></TD>
</TR>


<!-- ========================= LINHA ============================ -->
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" rowspan='2' width='100px'>Linha:&nbsp;</TD>
	<TD class="table_line" colspan='2'>Descrição</TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2' style="width: 185px">
		<SELECT NAME="linha">
			<option value=''></option>
<?
	$sql = "SELECT  tbl_linha.linha,
					tbl_linha.nome
			FROM    tbl_linha
			WHERE   tbl_linha.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	
	for($i=0; $i < pg_numrows($res); $i++) {
		$linha = trim(pg_result($res,$i,linha));
		$nome  = trim(pg_result($res,$i,nome));
		echo "					<option value='$linha'>$nome</option>\n";
	}

?>
		</SELECT></TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" colspan="5"><hr></TD>
</TR>


<!-- ========================= PRODUTO ============================ -->
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" rowspan='2' width='100px'>Produto:&nbsp;</TD>
	<TD class="table_line">Ref.</TD>
	<TD class="table_line">Descrição</TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 185px"><INPUT size="12" maxlength="10" TYPE="text" NAME="referencia" ><IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="" style="cursor:pointer" alt="Clique aqui para pesquisar pela referência do produto"></TD>
	<TD class="table_line" style="width: 185px"><INPUT size="15" maxlength="15" TYPE="text" NAME="descricao"><IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="" style="cursor:pointer" alt="Clique aqui para pesquisar pela descrição do produto"></TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" colspan="5"><hr></TD>
</TR>

<!-- ========================= REGIÃO ============================ -->
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" rowspan='2' width='100px'>ESTADO:&nbsp;</TD>
	<TD class="table_line" colspan='2' >SELECIONE O ESTADO</TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2' style="width: 185px">
		<select name="Estado" size="1">
			  <option value="TD" selected>TODAS AS REGIÕES</option>
			  <option value="AC">ACRE</option>
			  <option value="AL">ALAGOAS</option>
			  <option value="AP">AMAPÁ</option>
			  <option value="AM">AMAZONAS</option>
			  <option value="BA">BAHIA</option>
			  <option value="CE">CEARÁ</option>
			  <option value="DF">DISTRITO FEDERAL</option>
			  <option value="ES">ESPÍRITO SANTO</option>
			  <option value="GO">GOIÁS</option>
			  <option value="MA">MARANHÃO</option>
			  <option value="MT">MATO GROSSO</option>
			  <option value="MS">MATO GROSSO DO SUL</option>
			  <option value="MG">MINAS GERAIS</option>
			  <option value="PA">PARÁ</option>
			  <option value="PB">PARAÍBA</option>
			  <option value="PR">PARANÁ</option>
			  <option value="PE">PERNAMBUCO</option>
			  <option value="PI">PIAUÍ</option>
			  <option value="RJ">RIO DE JANEIRO</option>
			  <option value="RN">RIO GRANDE DO NORTE</option>
			  <option value="RS">RIO GRANDE DO SUL</option>
			  <option value="RO">RONDÔNIA</option>
			  <option value="RR">RORAIMA</option>
			  <option value="SC">SANTA CATARINA</option>
			  <option value="SP">SÃO PAULO</option>
			  <option value="SE">SERGIPE</option>
			  <option value="TO">TOCANTINS</option>
			</SELECT></TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" colspan="5"><hr></TD>
</TR>


<!-- ========================= POSTO ============================ -->
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" rowspan='2' width='100px'>POSTO:&nbsp;</TD>
	<TD class="table_line">Código</TD>
	<TD class="table_line">Nome do Posto</TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 185px"><INPUT size="12" maxlength="10" TYPE="text" NAME="codigo" ><IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="" style="cursor:pointer" alt="Clique aqui para pesquisar pelo código do posto"></TD>
	<TD class="table_line" style="width: 185px"><INPUT size="15" maxlength="10" TYPE="text" NAME=""><IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="" style="cursor:pointer" alt="Clique aqui para pesquisar pelo nome do posto"></TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" colspan="5"><hr></TD>
</TR>





<!-- <TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line">
	<INPUT TYPE="radio" <? if ($criterio == "data_abertura") echo " checked "; ?> NAME="criterio" value="data_abertura">Abertura da OS
</TD>
	<TD class="table_line">
		<INPUT TYPE="radio" <? if ($criterio == "data_digitacao") echo " checked "; ?> NAME="criterio" value="data_digitacao">Lançamento da OS
	</TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
-->


<TR>
	<input type='hidden' name='btn_finalizar' value='0'>
	<TD colspan="5" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { alert('Efetuando Pesquisa...') ; document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
</TR>
</FORM>
</TABLE>

<? include "rodape.php" ?>