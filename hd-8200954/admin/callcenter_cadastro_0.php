

<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

include 'funcoes.php';

$title = "Atendimento Call-Center"; 
$layout_menu = 'callcenter';

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

.menu_top2{
	text-align: center;
	font-family: Geneva, Arial, Helvetica, san-serif;
	font-size: x-small;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff

}

.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #CED7e7;
}

</style>

<!--=============== <FUNÇÕES> ================================!-->
<? include "javascript_pesquisas.php" ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<script language="JavaScript">

function fnc_pesquisa_posto_regiao(nome,cidade,estado) {
	if (cidade.value != "" || estado.value != "" || nome.value != ""){
		var url = "";
		url = "posto_pesquisa_regiao.php?nome=" + nome.value + "&cidade=" + cidade.value + "&estado=" + estado.value;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = document.frm_callcenter.posto_codigo;
		janela.nome    = document.frm_callcenter.posto_nome;
		janela.focus();
	}
}


/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Função : fnc_pesquisa_consumidor_nome (nome, cpf)
=================================================================*/
function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.cliente		= document.frm_callcenter.cliente;
	janela.nome			= document.frm_callcenter.consumidor_nome;
	janela.cpf			= document.frm_callcenter.consumidor_cpf;
	janela.rg			= document.frm_callcenter.consumidor_rg;
	janela.cidade		= document.frm_callcenter.consumidor_cidade;
	janela.estado		= document.frm_callcenter.consumidor_estado;
	janela.fone			= document.frm_callcenter.consumidor_fone;
	janela.endereco		= document.frm_callcenter.consumidor_endereco;
	janela.numero		= document.frm_callcenter.consumidor_numero;
	janela.complemento	= document.frm_callcenter.consumidor_complemento;
	janela.bairro		= document.frm_callcenter.consumidor_bairro;
	janela.cep			= document.frm_callcenter.consumidor_cep;
	janela.focus();
}

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.nome			= document.frm_callcenter.revenda_nome;
	janela.cnpj			= document.frm_callcenter.revenda_cnpj;
	janela.fone			= document.frm_callcenter.revenda_fone;
	janela.cidade		= document.frm_callcenter.revenda_cidade;
	janela.estado		= document.frm_callcenter.revenda_estado;
	janela.endereco		= document.frm_callcenter.revenda_endereco;
	janela.numero		= document.frm_callcenter.revenda_numero;
	janela.complemento	= document.frm_callcenter.revenda_complemento;
	janela.bairro		= document.frm_callcenter.revenda_bairro;
	janela.cep			= document.frm_callcenter.revenda_cep;
	janela.email		= document.frm_callcenter.revenda_email;
	janela.focus();
}


function fnc_pesquisa_os (campo) {
	url = "pesquisa_os.php?sua_os=" + campo.value;
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.sua_os			= document.frm_callcenter.sua_os;
	janela.data_abertura	= document.frm_callcenter.data_abertura;
	janela.focus();
}

/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digitação
		Parâm.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(cnpj, form){
	var mycnpj = '';
		mycnpj = mycnpj + cnpj;
		myrecord = "revenda_cnpj";
		myform = form;
		
		if (mycnpj.length == 2){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 6){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 10){
			mycnpj = mycnpj + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 15){
			mycnpj = mycnpj + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
}

/* ============= Função FORMATA CPF =============================
Nome da Função : formata_cpf (cpf, form)
		Formata o Campo de CPF a medida que ocorre a digitação
		Parâm.: cpf (numero), form (nome do form)
=================================================================*/
function formata_cpf(cpf, form){
	var mycpf = '';
		mycpf = mycpf + cpf;
		myrecord = "consumidor_cpf";
		myform = form;
		
		if (mycpf.length == 3){
			mycpf = mycpf + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
		if (mycpf.length == 7){
			mycpf = mycpf + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
		if (mycpf.length == 11){
			mycpf = mycpf + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
}

/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
		Ajusta a formatação da Máscara de DATAS a medida que ocorre
		a digitação do texto.
=================================================================*/
function ajustar_data(input , evento)
{
	var BACKSPACE=  8; 
	var DEL=  46; 
	var FRENTE=  39; 
	var TRAS=  37; 
	var key; 
	var tecla; 
	var strValidos = "0123456789" ;
	var temp;
	tecla= (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

	if (( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS)) {
		return true; 
			}
		if ( tecla == 13) return false; 
		if ((tecla<48)||(tecla>57)){
			return false;
			}
		key = String.fromCharCode(tecla); 
		input.value = input.value+key;
		temp="";
		for (var i = 0; i<input.value.length;i++ )
			{
				if (temp.length==2) temp=temp+"/";
				if (temp.length==5) temp=temp+"/";
				if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
					temp=temp+input.value.substr(i,1);
			}
			}
					input.value = temp.substr(0,10);
				return false;
}
</script>


<? 
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
<? 
	echo $msg_erro;

	// recarrega os campos
	$natureza           = trim($_POST['natureza']);
	$consumidor_nome    = trim($_POST['consumidor_nome']);
	$consumidor_cpf     = trim($_POST['consumidor_cpf']);
	$consumidor_cliente = trim($_POST['consumidor_cliente']);
	$sua_os             = trim($_POST['sua_os']);
	$data_abertura      = trim($_POST['data_abertura']);
	$produto_referencia = trim($_POST['produto_referencia']);
	$produto_nome       = trim($_POST['produto_serie']);
	$produto_serie      = trim($_POST['produto_serie']);
	$revenda_nome       = trim($_POST['revenda_nome']);
	$posto_nome         = trim($_POST['posto_nome']);
	$posto_codigo       = trim($_POST['posto_codigo']);

?>
	</td>
</tr>
</table>
<?
}
//echo $msg_debug ;
?>

<?
$sql = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
$res = pg_exec ($con,$sql);
$hoje = pg_result ($res,0,0);
?>

<br>
<FORM METHOD=POST name='frm_callcenter' ACTION="<? echo $PHP_SELF; ?>">

<table width="650" border="0" cellpadding="0" cellspacing="2" align="center" bgcolor="#ffffff" class="table">
<input type='hidden' name='callcenter' value='<? echo $callcenter; ?>'>
	<TR class='menu_top'>
		<TD width='50%' class='menu_top2'>Atendente</TD>
		<TD class='menu_top2'>Natureza do chamado</TD>
	</TR>
	<TR class='table_line'>
		<TD><? echo ucfirst($login_login); if (strlen($atendente_nome) > 0) echo " / Atendido por: ".ucfirst($atendente_nome); ?></TD>
		<TD>
			<SELECT NAME="natureza" class="frm" >
				<option value='' SELECTED>Selecione</option>
				<option value='Reclamação'       <? if($natureza == 'Reclamação')       echo ' selected';?>>Reclamação</option>
				<option value='Dúvidas'          <? if($natureza == 'Dúvidas')          echo ' selected';?>>Dúvidas</option>
				<option value='Troca de produto' <? if($natureza == 'Troca de produto') echo ' selected';?>>Troca de produto</option>
			</SELECT>
		</TD>
		</TR>
</table>
</form>

<br>

<table width="650" border="0" cellpadding="0" cellspacing="2" align="center" bgcolor="#ffffff" class="table">
	<TR class='menu_top'>
		<TD width='50%' class='menu_top' colspan='3'>1ª Opção</TD>
	</tr>
	<TR >
		<TD class='menu_top2' width='20%'>Número da OS</TD>		
		<TD width='20%'><INPUT TYPE="text" class="frm" NAME="sua_os" size="10" value="<? echo $sua_os; ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_os (document.frm_callcenter.sua_os)' style='cursor: pointer'></TD>
		<td width='60%'><input type="hidden" name="btn_acao" value=""><img src='imagens/btn_continuar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_callcenter.btn_acao.value == '' ) { document.frm_callcenter.btn_acao.value='continuar' ; document.frm_callcenter.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar" border='0'></td>
	</TR>
</table>
<BR><BR>	

<table width="600" border="0" cellpadding="0" cellspacing="2" align="center" bgcolor="#ffffff" class="table">
	<TR class='menu_top'>
		<TD width='50%' class='menu_top' colspan='3'>2ª Opção</TD>
	</tr>
	<TR class='menu_top'>
		<TD class='menu_top2'>Código Posto</TD>
		<TD class='menu_top2'>Nome Posto</TD>
		<TD class='menu_top2'>Cidade</TD>
	</TR>
	<TR class='table_line'>
		<TD><INPUT TYPE="text" class="frm" NAME="posto_codigo" size="30" value="<? echo $posto_codigo; ?>">&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto_regiao (document.frm_callcenter.posto_nome,document.frm_callcenter.consumidor_cidade,document.frm_callcenter.consumidor_estado)" style="cursor:pointer;"></TD>
		<TD><INPUT TYPE="text" class="frm" NAME="posto_nome" size="30" value="<? echo $posto_nome; ?>">&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto_regiao (document.frm_callcenter.posto_nome,document.frm_callcenter.consumidor_cidade,document.frm_callcenter.consumidor_estado)" style="cursor:pointer;"></TD>
		<td><INPUT TYPE="text" class="frm" NAME="posto_cidade" size="30" value="<? echo $posto_cidade; ?>">&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto_regiao (document.frm_callcenter.posto_nome,document.frm_callcenter.consumidor_cidade,document.frm_callcenter.consumidor_estado)" style="cursor:pointer;"></td>
	</TR>
	<tr><td>&nbsp;</td></tr>
	<TR class='menu_top'>
		<TD class='menu_top2' width = '25%'>Data abertura</TD>
		<TD class='menu_top2' width = '25%'>Referência Produto</TD>
		<TD class='menu_top2' width = '50%'>Descrição Produto</TD>
	</TR>
	<TR class='table_line'>
		<TD><INPUT TYPE="text" class="frm" NAME="data_abertura" size="10" maxlength='10'  value="<? echo $data_abertura; ?>">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisa')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
		<TD><INPUT TYPE="text" class="frm" NAME="referencia_produto" size="20" maxlength='10'  value="<? echo $referencia_produto; ?>">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle'></TD>
		<TD><INPUT TYPE="text" class="frm" NAME="produto_nome" size="30" value="<? echo $produto_nome; ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao')"></TD>
	</TR>
	<tr><td>&nbsp;</td></tr>
	<TR class='menu_top'>
		<TD class='menu_top2'>Série</TD>
		<TD class='menu_top2' colspan='2'>Revenda</TD>
	</TR>
	<TR class='table_line'>
		<TD><INPUT TYPE="text" class="frm" NAME="produto_serie" size="15" value="<? echo $produto_serie; ?>"></TD>
			<input type='hidden' name='produto_referencia' value="<? echo $produto_referencia; ?>">
		<TD colspan='2'><INPUT TYPE="text" class="frm" NAME="revenda_nome" size="60" value="<? echo $revenda_nome; ?>"></TD>
	</TR>
	<tr><td>&nbsp;</td></tr>
	<TR class='menu_top'>
		<TD class='menu_top2'>CPF/CNPJ Cliente</TD>
		<TD class='menu_top2' colspan='2'>Nome Cliente</TD>
	</TR>
	<TR class='table_line'>
		<TD><INPUT TYPE="text"  class="frm" NAME="consumidor_cpf" size="15" value="<? echo $consumidor_cpf; ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_callcenter.consumidor_cpf,"cpf")'  style='cursor: pointer'></TD>
		<TD colspan='2'><INPUT TYPE="text" class="frm" NAME="consumidor_nome" size="60" value="<? echo $consumidor_nome; ?> ">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_callcenter.consumidor_nome, "nome")'  style='cursor: pointer'></TD>
	</TR>
	<tr><td>&nbsp;</td></tr>
	<TR class='menu_top'>
		<TD class='menu_top2'>Cidade</TD>
		<TD class='menu_top2'>Estado</TD>
		<TD>&nbsp;</TD>
	</TR>
	<TR class='table_line'>
		<TD ><INPUT TYPE="text" class="frm" NAME="consumidor_cidade" size="30" value="<? echo $consumidor_cidade; ?>"></TD>
		<TD><INPUT TYPE="text" class="frm" NAME="consumidor_estado" size="2" maxlength="2" value="<? echo $consumidor_estado; ?>"></TD>
		<TD >&nbsp;</TD>
	</TR>
	<TR>
		<TD colspan='3' align='right'>
			<input type="hidden" name="btn_acao" value="">
			<img src='imagens/btn_continuar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_callcenter.btn_acao.value == '' ) { document.frm_callcenter.btn_acao.value='continuar' ; document.frm_callcenter.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar" border='0'>&nbsp;&nbsp;&nbsp;&nbsp;
		</TD>
	</TR>
		</FORM>
		</TABLE>
	</td>
	<td><img height="1" width="16" src="/imagens/spacer.gif"></td>
</tr>
</table>

<p>

<? include "rodape.php";?>