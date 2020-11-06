<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

/*======= VERIFICA SE É A FÁBRICANTE CORRETA =========*/

	if ($login_fabrica_nome <> "Dynacom" AND $login_fabrica_nome <> "Tectoy" ) {
		header ("Location: os_cadastro.php");
		exit;
	}

/*======= <PHP> FUNÇOES DOS BOTÕES DE AÇÃO =========*/

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";

if ($btn_acao == "continuar") 
{
	$os = $_POST['os'];
	
	$data_abertura      = formata_data ($_POST['data_abertura']);
	$produto_referencia = trim ($_POST['produto_referencia']);
	$produto_serie      = strtoupper (trim ($_POST['produto_serie']));
	
	$consumidor_nome    = str_replace ("'","",$_POST['consumidor_nome']);
	$consumidor_fone    = $_POST['consumidor_fone'];
	
	$revenda_cnpj = str_replace ("-","",$_POST['revenda_cnpj']);
	$revenda_cnpj = str_replace (".","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("/","",$revenda_cnpj);
	$revenda_cnpj = substr ($revenda_cnpj,0,14);
	$revenda_nome = str_replace ("'","",$_POST['revenda_nome']);
	$nota_fiscal  = $_POST['nota_fiscal'];
	$data_nf      = formata_data ($_POST['data_nf']);
	
	$aparencia_produto           = strtoupper (trim ($_POST['aparencia_produto']));
	$acessorios                  = strtoupper (trim ($_POST['acessorios']));
	//$defeito_reclamado_descricao = strtoupper (trim ($_POST['defeito_reclamado_descricao']));
	
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	if (strlen ($data_abertura) <> 10) {
		$data_abertura = "null";
	}else{
		$cdata_abertura = $data_abertura;
		$data_abertura  = "'" . $data_abertura . "'" ;
	}

	if (strlen ($data_nf) <> 10) {
		$data_nf = "null";
	}else{
		$data_nf = "'" . $data_nf . "'" ;
	}
	
	if (strlen ($os) == 0) {
		/*================ INSERE NOVA OS =========================*/
		$produto = 0;
		$sql = "SELECT tbl_produto.produto
				FROM   tbl_produto
				JOIN   tbl_linha USING (linha)
				WHERE  tbl_produto.referencia = '$produto_referencia'
				AND    tbl_linha.fabrica      = $login_fabrica";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows ($res) == 0) {
			$msg_erro = "Produto $produto_referencia não cadastrado";
		}
		$produto = @pg_result ($res,0,0);
		
		if (strlen($msg_erro) == 0) {
			$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $produto";
			$res = @pg_exec ($con,$sql);
			
			if (@pg_numrows ($res) == 0) {
				$msg_erro = "Produto $produto_referencia sem garantia";
			}
			$garantia = trim(@pg_result($res,0,garantia));
		}
		
		if (strlen($msg_erro) == 0) {
			$sql = "SELECT ($data_nf::date + (($garantia || ' months')::interval + ('7 days')::interval))::date;";
			$res = @pg_exec ($con,$sql);
			
			if (@pg_numrows ($res) > 0) {
				$data_final_garantia = trim(pg_result($res,0,0));
			}
			
			if ($data_final_garantia < $cdata_abertura) {
				$msg_erro = "Produto $produto_referencia fora da garantia, vencida em ". substr($data_final_garantia,8,2) ."/". substr($data_final_garantia,5,2) ."/". substr($data_final_garantia,0,4);
			}
		}
		
//						defeito_reclamado_descricao
//						trim ('$defeito_reclamado_descricao')
		if (strlen ($msg_erro) == 0) {
			$sql = "INSERT INTO tbl_os (
						posto            ,
						fabrica          ,
						sua_os           ,
						data_abertura    ,
						consumidor_nome  ,
						consumidor_fone  ,
						revenda_cnpj     ,
						revenda_nome     ,
						nota_fiscal      ,
						data_nf          ,
						produto          ,
						serie            ,
						aparencia_produto,
						acessorios       

				) VALUES (
						$login_posto               ,
						$login_fabrica             ,
						trim ('$sua_os')           ,
						$data_abertura             ,
						trim ('$consumidor_nome')  ,
						trim ('$consumidor_fone')   ,
						trim ('$revenda_cnpj')     ,
						trim ('$revenda_nome')     ,
						trim ('$nota_fiscal')      ,
						$data_nf                   ,
						$produto                   ,
						'$produto_serie'           ,
						trim ('$aparencia_produto'),
						trim ('$acessorios')       

				)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
		}
	}else{
		/*================ ALTERA OS =========================*/
	}
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"SELECT CURRVAL ('seq_os')");
		$os  = pg_result ($res,0,0);
		
	}
	
	if (strlen ($msg_erro) == 0) {
		$res      = pg_exec ($con,"SELECT fn_valida_os($os, $login_fabrica)");
		$msg_erro = pg_errormessage($con);
	}
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: os_cadastro_dynacom_adicional.php?os=$os");
		exit;
	}else{
		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_fechamento\"") > 0)
			$msg_erro = "Data do fechamento menor que a data da abertura da Ordem de Serviço.";
		
		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
			$msg_erro = "Data da compra maior que a data da abertura da Ordem de Serviço.";
		
		if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_os_unico\"") > 0)
			$msg_erro = "Número da OS do Fabricante não pode ser repetida.";
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

/*================ LE OS DA BASE DE DADOS =========================*/

$os = $_GET['os'];

if (strlen ($os) > 0) {
	$sql = "SELECT * FROM tbl_os WHERE oid = $os AND posto = $login_posto";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 1) {
		$sua_os							= pg_result ($res,0,sua_os);
		$data_abertura					= pg_result ($res,0,data_abertura);
		$produto_referencia				= pg_result ($res,0,produto);
		$produto_descricao				= pg_result ($res,0,descricao);
		$produto_serie					= pg_result ($res,0,serie);
		$consumidor_nome				= pg_result ($res,0,consumidor_nome);
		$consumidor_fone				= pg_result ($res,0,consumidor_fone);
		$revenda_cnpj					= pg_result ($res,0,revenda_cnpj);
		$revenda_nome					= pg_result ($res,0,revenda_nome);
		$revenda_fone					= pg_result ($res,0,revenda_fone);
		$nota_fiscal					= pg_result ($res,0,nota_fiscal);
		$data_nf						= pg_result ($res,0,data_nf);
		$aparencia						= pg_result ($res,0,aparencia_produto);
		$acessorios						= pg_result ($res,0,acessorios);
		$defeito_reclamado				= pg_result ($res,0,defeito_reclamado);
		//$defeito_reclamado_descricao	= pg_result ($res,0,defeito_reclamado_descricao);
	}
}

/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen ($msg_erro) > 0) {
	$os								= $_POST['os'];
	$sua_os							= $_POST['sua_os'];
	$data_abertura					= $_POST['data_abertura'];
	$produto_referencia				= $_POST['produto_referencia'];
	$produto_serie					= $_POST['produto_serie'];
	$consumidor_nome				= $_POST['consumidor_nome'];
	$consumidor_cpf					= $_POST['consumidor_cpf'];
	$consumidor_fone				= $_POST['consumidor_fone'];
	$revenda_cnpj					= $_POST['revenda_cnpj'];
	$revenda_nome					= $_POST['revenda_nome'];
	$revenda_fone					= $_POST['revenda_fone'];
	$nota_fiscal					= $_POST['nota_fiscal'];
	$data_nf						= $_POST['data_nf'];
	$aparencia						= $_POST['aparencia'];
	$acessorios						= $_POST['acessorios'];
	$defeito_reclamado				= $_POST['defeito_reclamado'];
	//$defeito_reclamado_descricao	= $_POST['defeito_reclamado_descricao'];

	$sql = "SELECT tbl_produto.descricao
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  tbl_produto.referencia = '$produto_referencia'
			AND    tbl_linha.fabrica      = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$produto_descricao = @pg_result ($res,0,0);
}

$body_onload = "javascript: document.frm_os.sua_os.focus()";
$title = "Cadastro de Ordem de Serviço"; 
$layout_menu = 'os';

include "cabecalho.php";
?>

<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js">
</script>

<script>
/*****************************************************************
Nome da Função : displayText
		Apresenta em um campo as informações de ajuda de onde 
		o cursor estiver posicionado.
******************************************************************/
function displayText( sText ) {
	document.getElementById("displayArea").innerHTML = sText;
}

/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/
function fnc_pesquisa_produto (referencia,descricao) {
	var url = "";
	if (referencia.value != "" || descricao.value != "") {
		url = "pesquisa_tabela_2.php?referencia=" + referencia.value + "&descricao=" + descricao.value;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.referencia = referencia;
		janela.descricao  = descricao;
		janela.focus();
	}
}

/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Função : fnc_pesquisa_consumidor_nome (campo, tipo)
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
	janela.nome			= document.frm_os.consumidor_nome;
	janela.cpf			= document.frm_os.consumidor_cpf;
	janela.cidade		= document.frm_os.consumidor_cidade;
	janela.estado		= document.frm_os.consumidor_estado;
	janela.fone			= document.frm_os.consumidor_fone;
	janela.endereco		= document.frm_os.consumidor_endereco;
	janela.numero		= document.frm_os.consumidor_numero;
	janela.complemento	= document.frm_os.consumidor_complemento;
	janela.bairro		= document.frm_os.consumidor_bairro;
	janela.cep			= document.frm_os.consumidor_cep;
	janela.focus();
}

/* ============= Função PESQUISA REVENDA ====================
Nome da Função : fnc_pesquisa_REVENDA (campo, tipo)
===========================================================*/
function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.nome			= document.frm_os.revenda_nome;
	janela.cnpj			= document.frm_os.revenda_cnpj;
	janela.fone			= document.frm_os.revenda_fone;
	janela.cidade		= document.frm_os.revenda_cidade;
	janela.estado		= document.frm_os.revenda_estado;
	janela.endereco		= document.frm_os.revenda_endereco;
	janela.numero		= document.frm_os.revenda_numero;
	janela.complemento	= document.frm_os.revenda_complemento;
	janela.bairro		= document.frm_os.revenda_bairro;
	janela.cep			= document.frm_os.revenda_cep;
	janela.focus();
}

/* ============= Função FORMATA CNPJ =============================*/
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

/* ============= Função AJUSTA CAMPO DE DATAS =============================*/
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
# <PHP> VERIFICA DUPLICIDADE DE OS
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0)
		$msg_erro = "Esta ordem de serviço já foi cadastrada";
?>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->
<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" color="#FF3333">
		<? echo $msg_erro ?>
		</font></b>
	</td>
</tr>
</table>
<? } ?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="left">
		
		<!-- ------------- Formulário ----------------- -->
		
		<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input class="frm" type="hidden" name="os" value="<? echo $os ?>">
		<!-- input class="frm" type="hidden" name="codproduto" value="<? echo $codproduto ?>" -->
		
		<p>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
				<br>
				<input class="frm" type="text" name="sua_os" size="20" maxlength="20"  value="<? echo $sua_os ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font>
				<br>
				<input name="data_abertura" size="12" maxlength="10" value="<? echo $data_abertura ?>" type="text" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Data da Abertura da OS.');" tabindex="0" onkeyup="if (this.value.length==10){}" onKeyPress="return ajustar_data(this, event)"> <a href="javascript:;"><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript:showCal('dataOSAbertura')" alt="Clique aqui para abrir o calendário"></a>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Referência Produto</font>
				<br>
				<input class="frm" type="text" name="produto_referencia" size="10" maxlength="20" value="<? echo $produto_referencia ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');"> <a href="#"><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao)' alt="Clique aqui para pesquisar pela referência do produto"></a>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Descrição Produto</font>
				<br>
				<input class="frm" type="text" name="produto_descricao" size="40" value="<? echo $produto_descricao ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');"> <a href="#"><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao)' alt="Clique aqui para pesquisar pela descrição do produto"></a>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
				<br>
				<input class="frm" type="text" name="produto_serie" size="12" maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.');">
			</td>
		</tr>
		</table>
		
		<hr>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Consumidor</font>
				<br>
				<input class="frm" type="text" name="consumidor_nome" size="40" maxlength="50" value="<? echo $consumidor_nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o nome do Cliente.');">
				 <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome,"nome")' alt="Clique aqui para pesquisar pelo nome do Cliente" style="cursor:pointer;">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">C.P.F.</font>
				<br>
				<input class="frm" type="text" name="consumidor_cpf" size="25" maxlength="30" value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o CPF do Cliente.');">
				 <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")' alt="Clique aqui para pesquisar pelo CPF do Cliente " style="cursor:pointer;">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
				<br>
				<input class="frm" type="text" name="consumidor_fone"   size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
			</td>
		</tr>
		</table>
		
		<hr>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td style="width: 320px">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
				<br>
				<input class="frm" type="text" name="revenda_nome" size="40" maxlength="50" value="<? echo $revenda_nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o nome da REVENDA onde foi adquirido o produto.');">&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor: pointer" border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' alt="Clique aqui para realizar a busca pela parte ou pelo nome da revenda." style="cursor:pointer;">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
				<br>
				<input class="frm" type="text" name="revenda_cnpj" size="25" maxlength="18" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número no Cadastro Nacional de Pessoa Jurídica.');" onKeyUp="formata_cnpj(this.value, 'frm_os')">&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor: pointer" border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' alt="Clique aqui para pesquisar pelo CNPJ da Revenda." style="cursor:pointer;">
			</td>
			<td>
				&nbsp;
			</td>
		</tr>
		</table>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
			<tr>
			<td style="width: 200px">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone Revenda</font>
				<br>
				<input class="frm" type="text" name="revenda_fone" size="20" maxlength="20" value="<? echo $revenda_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o telefone da REVENDA onde foi adquirido o produto.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
				<br>
				<input class="frm" type="text" name="nota_fiscal"  size="15"  maxlength="6"  value="<? echo $nota_fiscal ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número da Nota Fiscal.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Compra</font>
				<br>
				<input name="data_nf" size="15" maxlength="10" value="<? echo $data_nf ?>" type="text" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a data da compra.');" tabindex="0" onkeyup="if (this.value.length==10){}" onKeyPress="return ajustar_data(this, event)">&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor: pointer" border='0' align='absmiddle' onclick="javascript:showCal('dataOSNf')" alt="Clique aqui para abrir o calendário">
			</td>
			</tr>
		</table>

		<hr>

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Aparência do Produto</font>
				<br>
				<input class="frm" type="text" name="aparencia_produto" size="35" value="<? echo $aparencia_produto ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');">
			</td>
			<td colspan="2">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Acessórios</font>
				<br>
				<input class="frm" type="text" name="acessorios" size="35" value="<? echo $acessorios ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com os acessórios deixados junto ao produto.');">
			</td>
			<!--td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Reclamado</font>
				<br>
				<input class="frm" type="text" name="defeito_reclamado_descricao" size="35" value="<? echo $defeito_reclamado_descricao ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com o defeito alegado pelo consumidor.');">
			</td-->
		</tr>
		</table>
	</td>
	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>

<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<br>
		<!-- h3>Lance na tela a seguir o defeito e as peças substituidas</h3-->
	</td>
</tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar com Ordem de Serviço" border='0' style="cursor:pointer;">
	</td>
</tr>

<input type="hidden" name="consumidor_endereco">
<input type="hidden" name="consumidor_numero">
<input type="hidden" name="consumidor_complemento">
<input type="hidden" name="consumidor_bairro">
<input type="hidden" name="consumidor_cep">
<input type="hidden" name="consumidor_cidade">
<input type="hidden" name="consumidor_estado">

<input type='hidden' name ='revenda_cidade'>
<input type='hidden' name ='revenda_estado'>
<input type='hidden' name ='revenda_endereco'>
<input type='hidden' name ='revenda_numero'>
<input type='hidden' name ='revenda_complemento'>
<input type='hidden' name ='revenda_bairro'>
<input type='hidden' name ='revenda_cep'>

</form>
</table>

<p>

<? include "rodape.php";?>