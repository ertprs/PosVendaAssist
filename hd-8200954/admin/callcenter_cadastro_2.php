<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

include 'funcoes.php';

if (strlen($_POST['call_center']) > 0) $call_center = trim($_POST['call_center']);
if (strlen($_GET['call_center']) > 0) $call_center = trim($_GET['call_center']);

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";

if ($btn_acao == "continuar") {
	$consumidor_cliente     = trim($_POST['consumidor_cliente']);
	$consumidor_nome        = trim($_POST['consumidor_nome']);
	$consumidor_cpf         = trim($_POST['consumidor_cpf']);
	$consumidor_endereco    = trim($_POST['consumidor_endereco']);
	$consumidor_numero      = trim($_POST['consumidor_numero']);
	$consumidor_complemento = trim($_POST['consumidor_complemento']);
	$consumidor_cep         = trim($_POST['consumidor_cep']);
	$consumidor_bairro      = trim($_POST['consumidor_bairro']);
	$consumidor_cidade      = trim($_POST['consumidor_cidade']);
	$consumidor_estado      = trim($_POST['consumidor_estado']);
	$consumidor_rg          = trim($_POST['consumidor_rg']);
	$consumidor_fone        = trim($_POST['consumidor_fone']);
	$consumidor_email       = trim($_POST['consumidor_email']);
	$consumidor_celular     = trim($_POST['consumidor_celular']);
	$defeito_reclamado      = trim($_POST['defeito_reclamado']);
	$reclamacao             = trim($_POST['reclamacao']);
	$solucao                = trim($_POST['solucao']);
	$posto_cnpj             = trim($_POST['posto_cnpj']);


	if (strlen (trim ($solucao)) == 0)
		$msg_erro = "Digite a solução.";
	else
		$xsolucao = "'" . $solucao. "'" ;
	
	if (strlen (trim ($reclamacao)) == 0)
		$msg_erro = "Digite a reclamação.";
	else
		$xreclamacao = "'" . $reclamacao. "'" ;

	if (strlen (trim ($defeito_reclamado)) == 0)
		$xdefeito_reclamado = 'null';
	else
		$xdefeito_reclamado = "'" . $defeito_reclamado. "'" ;
//TAKASHI 09-11  - solicitacao da Paula tectoy
	/*if($login_fabrica==6 a){
		$xdefeito_reclamado == 'null';
		$msg_erro .= "Favor inserir defeito reclamado.<BR>";
	}
*/

	if (strlen (trim ($consumidor_celular)) == 0)
		$xconsumidor_celular = 'null';
	else
		$xconsumidor_celular = "'" . $consumidor_celular . "'" ;

	if (strlen (trim ($consumidor_email)) == 0)
		$xconsumidor_email = 'null';
	else
		$xconsumidor_email = "'" . $consumidor_email . "'" ;

	if (strlen (trim ($consumidor_fone)) == 0)
		$xconsumidor_fone = 'null';
	else
		$xconsumidor_fone = "'" . $consumidor_fone . "'" ;

	if (strlen (trim ($consumidor_rg)) == 0)
		$xconsumidor_rg = 'null';
	else
		$xconsumidor_rg = "'" . $consumidor_rg . "'" ;

	if (strlen (trim ($consumidor_estado)) == 0)
		$msg_erro  = "Digite o estado.";
	else
		$xconsumidor_estado = "'" . strtoupper($consumidor_estado) . "'" ;

	if (strlen (trim ($consumidor_cidade)) == 0)
		$msg_erro  = "Digite a cidade.";
	else
		$xconsumidor_cidade = "'" . strtoupper($consumidor_cidade) . "'" ;

	if (strlen (trim ($consumidor_bairro)) == 0)
		$xconsumidor_bairro = 'null';
	else
		$xconsumidor_bairro = "'" . $consumidor_bairro . "'" ;

	if (strlen (trim ($consumidor_cep)) == 0) {
		$xconsumidor_cep = 'null';
	}else {
		$xconsumidor_cep = str_replace("-","",$consumidor_cep) ;
		$xconsumidor_cep = "'" . str_replace(".","",$xconsumidor_cep) . "'" ;
	}

	if (strlen (trim ($consumidor_complemento)) == 0)
		$xconsumidor_complemento = 'null';
	else
		$xconsumidor_complemento = "'" . $consumidor_complemento . "'" ;

	if (strlen (trim ($consumidor_numero)) == 0)
		$xconsumidor_numero = 'null';
	else
		$xconsumidor_numero = "'" . $consumidor_numero . "'" ;

	if (strlen (trim ($consumidor_endereco)) == 0)
		$xconsumidor_endereco = 'null';
	else
		$xconsumidor_endereco = "'" . $consumidor_endereco . "'" ;

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	if (strlen ($msg_erro) == 0) {
		if (strlen($consumidor_cliente) > 0){
			$sql = "SELECT fnc_qual_cidade ($xconsumidor_cidade,$xconsumidor_estado)";
			$res = pg_exec ($con,$sql);
			$cidade = pg_result ($res,0,0);

			$sql = "UPDATE tbl_cliente SET 
						endereco    = $xconsumidor_endereco,
						numero      = $xconsumidor_numero,
						complemento = $xconsumidor_complemento,
						cep         = $xconsumidor_cep,
						bairro      = $xconsumidor_bairro,
						cidade      = $cidade,
						rg          = $xconsumidor_rg,
						fone        = $xconsumidor_fone
					WHERE cliente   = $consumidor_cliente";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
		}
		if($login_fabrica == 15){
			$posto_cnpj = trim($_POST['posto_cnpj']);
				$posto_cnpj = str_replace (".","",$posto_cnpj);
				$posto_cnpj = str_replace (",","",$posto_cnpj);
				$posto_cnpj = str_replace ("-","",$posto_cnpj);
				$posto_cnpj = str_replace ("/","",$posto_cnpj);

			if(strlen($posto_cnpj) > 0){
				$sql = "SELECT posto FROM tbl_posto
						JOIN tbl_posto_fabrica using(posto)
						WHERE cnpj = '$posto_cnpj'
						AND   fabrica = $login_fabrica ";
				$res            = pg_exec($con,$sql);
				$posto_posto    = pg_result($res,0,0);

				if(pg_numrows($res) == 1 AND strlen($posto_posto) > 0){
					$sql = "SELECT natureza FROM tbl_callcenter
							WHERE callcenter = $callcenter 
							AND   fabrica = $login_fabrica ";
					$res      = pg_exec($con,$sql);
					$natureza = pg_result($res,0,0);

					if(pg_numrows($res) == 1){
						if($natureza == "Informação"){
							$sql = " UPDATE tbl_callcenter SET
									posto   = '$posto_posto'
									WHERE callcenter = $callcenter ";
							$res = pg_exec($con,$sql);
						}
					}
				}
			}
		}
		if (strlen ($msg_erro) == 0) {
			$sql = "UPDATE tbl_callcenter SET
						defeito_reclamado = $xdefeito_reclamado,
						reclamacao        = $xreclamacao,
						solucao           = $xsolucao
					WHERE callcenter = $callcenter
					AND   fabrica    = $login_fabrica";
//if ($ip == '192.168.0.68') { echo $sql; exit; }
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
		}
	}
	if (strlen ($msg_erro) > 0) {
		$consumidor_cliente     = $_POST['consumidor_cliente'];
		$consumidor_nome        = $_POST['consumidor_nome'];
		$consumidor_cpf         = $_POST['consumidor_cpf'];
		$consumidor_endereco    = $_POST['consumidor_endereco'];
		$consumidor_numero      = $_POST['consumidor_numero'];
		$consumidor_complemento = $_POST['consumidor_complemento'];
		$consumidor_cep         = $_POST['consumidor_cep'];
		$consumidor_bairro      = $_POST['consumidor_bairro'];
		$consumidor_cidade      = $_POST['consumidor_cidade'];
		$consumidor_estado      = $_POST['consumidor_estado'];
		$consumidor_rg          = $_POST['consumidor_rg'];
		$consumidor_fone        = $_POST['consumidor_fone'];
		$consumidor_email       = $_POST['consumidor_email'];
		$consumidor_celular     = $_POST['consumidor_celular'];
		$defeito_reclamado      = $_POST['defeito_reclamado'];
		$reclamacao             = $_POST['reclamacao'];
		$solucao                = $_POST['solucao'];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}else{
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: callcenter_cadastro_3.php?callcenter=$callcenter");
		exit;
	}
}

/*================ LE BASE DE DADOS =========================*/
if (strlen ($callcenter) > 0 and strlen($msg_erro) == 0) {
	$sql = "SELECT	tbl_callcenter.callcenter                                          ,
					tbl_callcenter.serie                                               ,
					tbl_callcenter.revenda_nome                                        ,
					tbl_callcenter.natureza                                            ,
					tbl_callcenter.sua_os                                              ,
					to_char(tbl_callcenter.data_abertura,'DD/MM/YYYY') as data_abertura,
					tbl_callcenter.defeito_reclamado                                   ,
					tbl_callcenter.reclamacao                                          ,
					tbl_callcenter.solucao                                             ,
					tbl_callcenter.cliente  AS consumidor_cliente                      ,
					tbl_callcenter.nota_fiscal                                         ,
					to_char(tbl_callcenter.data_nf,'DD/MM/YYYY')       as data_nf      ,
					tbl_cliente.nome        AS consumidor_nome,
					tbl_cliente.cpf         AS consumidor_cpf,
					tbl_cliente.endereco    AS consumidor_endereco,
					tbl_cliente.numero      AS consumidor_numero,
					tbl_cliente.complemento AS consumidor_complemento,
					tbl_cliente.cep         AS consumidor_cep,
					tbl_cliente.bairro      AS consumidor_bairro,
					tbl_cliente.rg          AS consumidor_rg,
					tbl_cliente.fone        AS consumidor_fone,
					tbl_cliente_contato.email   AS consumidor_email,
					tbl_cliente_contato.celular AS consumidor_celular,
					tbl_cidade.nome         AS consumidor_cidade,
					tbl_cidade.estado       AS consumidor_estado,
					tbl_os.sua_os    AS sua_os         ,
					tbl_posto.nome   AS posto_nome     ,
					tbl_posto.fone   AS posto_fone     ,
					tbl_posto.cidade                   ,
					tbl_posto.bairro                   ,
					tbl_posto.estado                   ,
					tbl_posto.cnpj                     ,
					tbl_produto.descricao AS produto_descricao,
					tbl_produto.linha                  
			FROM	tbl_callcenter
			JOIN	tbl_cliente   USING(cliente)
			JOIN	tbl_cidade    on tbl_cidade.cidade = tbl_cliente.cidade
			LEFT JOIN tbl_cliente_contato USING(cliente)
			LEFT JOIN tbl_os      USING(os)
			LEFT JOIN tbl_posto   ON tbl_posto.posto = tbl_callcenter.posto
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_callcenter.produto
			WHERE	tbl_callcenter.callcenter = $callcenter
			AND		tbl_callcenter.fabrica    = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$callcenter          = pg_result ($res,0,callcenter);
		$revenda_nome        = pg_result ($res,0,revenda_nome);
		$serie               = pg_result ($res,0,serie);
		$natureza            = pg_result ($res,0,natureza);
		$defeito_reclamado   = pg_result ($res,0,defeito_reclamado);
		$reclamacao          = pg_result ($res,0,reclamacao);
		$solucao             = pg_result ($res,0,solucao);
		$consumidor_cliente  = pg_result ($res,0,consumidor_cliente);
		$consumidor_nome     = pg_result ($res,0,consumidor_nome);
		$consumidor_cpf      = pg_result ($res,0,consumidor_cpf);
		$consumidor_endereco = pg_result ($res,0,consumidor_endereco);
		$consumidor_numero   = pg_result ($res,0,consumidor_numero);
		$consumidor_complemento = pg_result ($res,0,consumidor_complemento);
		$consumidor_cep      = pg_result ($res,0,consumidor_cep);
		$consumidor_bairro   = pg_result ($res,0,consumidor_bairro);
		$consumidor_cidade   = pg_result ($res,0,consumidor_cidade);
		$consumidor_estado   = pg_result ($res,0,consumidor_estado);
		$consumidor_rg       = pg_result ($res,0,consumidor_rg);
		$consumidor_fone     = pg_result ($res,0,consumidor_fone);
		$consumidor_email    = pg_result ($res,0,consumidor_email);
		$consumidor_celular  = pg_result ($res,0,consumidor_celular);
		$sua_os              = pg_result ($res,0,sua_os);
		$posto_nome          = pg_result ($res,0,posto_nome);
		$posto_fone          = pg_result ($res,0,posto_fone);
		$produto_descricao   = pg_result ($res,0,produto_descricao);
		$linha               = pg_result ($res,0,linha);
		$data_abertura       = pg_result ($res,0,data_abertura);
		$data_abertura       = pg_result ($res,0,data_abertura);
		$nota_fiscal         = pg_result ($res,0,nota_fiscal);
		$data_nf             = pg_result ($res,0,data_nf);
		$posto_cidade        = pg_result ($res,0,cidade);
		$posto_bairro        = pg_result ($res,0,bairro);
		$posto_estado        = pg_result ($res,0,estado);
		$posto_cnpj          = pg_result ($res,0,cnpj);

		if($login_fabrica == 15 AND $natureza == "Informação" AND strlen($posto_cnpj) > 0){
			$sql = "SELECT codigo_posto FROM tbl_posto_fabrica
					JOIN  tbl_callcenter using(posto)
					WHERE tbl_callcenter.callcenter = $callcenter
					AND   tbl_posto_fabrica.fabrica = $login_fabrica ";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) > 0);
				$posto_codigo = pg_result($res,0,0);
		}

	}else{
		header('Location: callcenter_cadastro_1.php');
		exit;
	}
}

$title = "Atendimento Call-Center"; 
$layout_menu = 'callcenter';

include "cabecalho.php";

?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

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
	background-color: #ffffff
}


.border {
	border: 1px solid #ced7e7;
}

.table_line2 {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
}



</style>

<!--=============== <FUNÇÕES> ================================!-->
<? include "javascript_pesquisas.php" ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<script language="JavaScript">
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
	janela.cliente		= document.frm_callcenter.consumidor_cliente;
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

function fnc_pesquisa_callcenter(campo, campo2, campo3, campo4, campo5, campo6, campo7, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (tipo == "cidade" ) {
		var xcampo = campo3;
	}

	if (tipo == "estado" ) {
		var xcampo = campo4;
	}

	if (tipo == "bairro" ) {
		var xcampo = campo5;
	}

	if(tipo == "cnpj" ) {
		var xcampo = campo6;
	}

	if(tipo == "linha" ) {
		var xcampo = campo7;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_callcenter.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=650, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.cidade  = campo3;
		janela.estado  = campo4;
		janela.bairro  = campo5;
		janela.cnpj    = campo6;
		janela.linha   = campo7;
		janela.focus();
	}
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
		<? echo $msg_erro ?>
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


<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<td><img height="1" width="20" src="/imagens/spacer.gif"></td>
	<td valign="top" align="left">
		<TABLE width=600 border="0" cellpadding="3" cellspacing="3">
		<FORM METHOD=POST name='frm_callcenter' ACTION="<? echo $PHP_SELF; ?>">
		<INPUT TYPE="hidden" name='callcenter' value='<? echo $callcenter; ?>'>
		<INPUT TYPE="hidden" name='posto_posto' value='<? echo $posto_posto; ?>'>
			<TR class='menu_top'>
				<TD>Número do atendimento</TD>
				<TD>Atendente</TD>
				<TD>Natureza do chamado</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $callcenter;?> &nbsp;</TD>
				<TD><? echo ucfirst($login_login);?> &nbsp;</TD>
				<TD><? echo $natureza;?> &nbsp;</TD>
			</TR>
		</TABLE>
<hr>
		<TABLE width=600 border="0" cellpadding="3" cellspacing="3">
			<TR class='menu_top'>
				<TD>Nome Cliente</TD>
				<TD>CPF/CNPJ Cliente</TD>
			</TR>
			<TR class='table_line'>
				<input type='hidden' name = 'consumidor_cliente' value='<? echo $consumidor_cliente; ?>'>
				<TD><? echo $consumidor_nome; ?> &nbsp;</TD>
				<TD><? echo $consumidor_cpf; ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD>Endereco</TD>
				<TD>Número</TD>
			</TR>
			<TR class='table_line'>
				<TD><INPUT TYPE="text" NAME="consumidor_endereco" value="<? echo $consumidor_endereco; ?>" size='40'></TD>
				<TD><INPUT TYPE="text" NAME="consumidor_numero" value="<? echo $consumidor_numero; ?>" size='5'></TD>
			</TR>
			<TR class='menu_top'>
				<TD>Complemento</TD>
				<TD>CEP</TD>
			</TR>
			<TR class='table_line'>
				<TD><INPUT TYPE="text" NAME="consumidor_complemento" value="<? echo $consumidor_complemento; ?>" size='40'></TD>
				<TD><INPUT TYPE="text" NAME="consumidor_cep" value="<? echo $consumidor_cep; ?>" size='9'></TD>
			</TR>
			<TR class='menu_top'>
				<TD>Bairro</TD>
				<TD>Cidade / Estado</TD>
			</TR>
			<TR class='table_line'>
				<TD><INPUT TYPE="text" NAME="consumidor_bairro" value="<? echo $consumidor_bairro; ?>" size='40'></TD>
				<TD><INPUT TYPE="text" NAME="consumidor_cidade" value="<? echo $consumidor_cidade; ?>" size='30'> - <INPUT TYPE="text" NAME="consumidor_estado" value="<? echo $consumidor_estado; ?>" size='2' maxlength='2'></TD>
			</TR>
			<TR class='menu_top'>
				<TD>RG/IE</TD>
				<TD>Fone</TD>
			</TR>
			<TR class='table_line'>
				<TD><INPUT TYPE="text" NAME="consumidor_rg" value="<? echo $consumidor_rg; ?>" size='15'></TD>
				<TD><INPUT TYPE="text" NAME="consumidor_fone" value="<? echo $consumidor_fone; ?>" size='15'></TD>
			</TR>
			<!--<TR class='menu_top'>
				<TD>e-Mail</TD>
				<TD>Celular</TD>
			</TR>
			<TR class='table_line'>
				<TD><INPUT TYPE="text" NAME="consumidor_email" value="<? echo $consumidor_email; ?>" size='30'></TD>
				<TD><INPUT TYPE="text" NAME="consumidor_celular" value="<? echo $consumidor_celular; ?>" size='15'></TD>
			</TR>-->
		</TABLE>
<hr>
		<TABLE width=600 border="0" cellpadding="3" cellspacing="3">
<?
if (strlen($sua_os) > 0){
?>
			<TR class='menu_top'>
				<TD colspan=2>Número da OS</TD>
				<TD colspan=2>Data abertura</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan=2><? echo $sua_os; ?> &nbsp;</TD>
				<TD colspan=2><? echo $data_abertura; ?> &nbsp;</TD>
			</TR>
<?
}
if (strlen($posto_nome) > 0){
?>
			<TR class='menu_top'>
				<TD colspan=3>Nome Posto</TD>
				<TD>Telefone</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan=3><? echo $posto_nome; ?> &nbsp;</TD>
				<TD><? echo $posto_fone; ?> &nbsp;</TD>
					<input type='hidden' name = 'posto_codigo'>
			</TR>
<?
}
if (strlen($produto_descricao) > 0){
?>
			<TR class='menu_top'>
				<TD>Produto</TD>
				<TD>Série</TD>
				<TD>Nota fiscal</TD>
				<TD>Data da compra</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $produto_descricao; ?> &nbsp;</TD>
				<TD><? echo $serie; ?> &nbsp;</TD>
				<TD><? echo $nota_fiscal; ?> &nbsp;</TD>
				<TD><? echo $data_nf; ?> &nbsp;</TD>
			</TR>
<?
}
if (strlen($revenda_nome) > 0){
?>
			<TR class='menu_top'>
				<TD colspan='4'>Revenda</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='4'><? echo $revenda_nome; ?> &nbsp;</TD>
			</TR>
<?
}
?>
		</table>
<hr>
		<TABLE width=600 border="0" cellpadding="3" cellspacing="3">
			<TR class='menu_top'>
				<TD colspan='4'>Ocorrência / Reclamação</TD>
			</TR>
<?
if (strlen($linha) > 0){
?>
			<TR class='table_line'>
				<TD colspan='4' align='center'>
<?

if ($login_fabrica <> 6) {
	switch ($natureza) {
			case 'Dúvidas' :
				$duvida_reclamacao = 'DV';
				break;
			case 'Insatisfação' :
				$duvida_reclamacao = 'IS';
				break;
			default :
				$duvida_reclamacao = 'RC';
				break;
	}
}else{
	switch ($natureza) {
		case 'Reclamação' :
			$duvida_reclamacao = 'RC';
			break;
		case 'Ocorrência' :
			$duvida_reclamacao = 'RC';
			break;
		case 'Defeito' :
			$duvida_reclamacao = 'DF';
			break;
		case 'Informação' :
			$duvida_reclamacao = 'IN';
			break;
		case 'Insatisfação' :
			$duvida_reclamacao = 'IS';
			break;
		case 'Troca do Produto' :
			$duvida_reclamacao = 'TP';
			break;
		case 'Engano' :
			$duvida_reclamacao = 'EN';
			break;
		case 'Outras Áreas' :
			$duvida_reclamacao = 'OA';
			break;
		case 'Email' :
			$duvida_reclamacao = 'RC';
			break;
	}
}
$sql = "SELECT *
		FROM   tbl_defeito_reclamado
		WHERE tbl_defeito_reclamado.fabrica = $login_fabrica 
		AND   tbl_defeito_reclamado.duvida_reclamacao = '$duvida_reclamacao'
		AND   tbl_defeito_reclamado.ativo IS TRUE";

if($duvida_reclamacao == 'RC' and $login_fabrica==6){//chamado 1238
	$sql = "SELECT *
		FROM   tbl_defeito_reclamado
		WHERE tbl_defeito_reclamado.fabrica = $login_fabrica 
		AND   tbl_defeito_reclamado.duvida_reclamacao = '$duvida_reclamacao'
		AND   tbl_defeito_reclamado.ativo IS TRUE";
		
	$sql = "SELECT  distinct tbl_defeito_reclamado.descricao, 
					tbl_defeito_reclamado.defeito_reclamado 
			FROM tbl_diagnostico 
			JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado =  tbl_diagnostico.defeito_reclamado and tbl_defeito_reclamado.fabrica = $login_fabrica
			JOIN tbl_produto on tbl_diagnostico.linha=tbl_produto.linha
			JOIN tbl_callcenter on tbl_callcenter.produto = tbl_produto.produto
			WHERE tbl_callcenter.callcenter= $callcenter
			AND tbl_callcenter.fabrica = $login_fabrica";

}


$res = pg_exec($con,$sql);



//echo "$sql<br>";
$y=1;
echo "					<table border='0' cellspacing='2' cellpadding='2'>\n";
echo "						<tr><td align='left'>";
for($i=0; $i<pg_numrows($res); $i++){
	echo "<input type='radio' name='defeito_reclamado' value='".pg_result($res,$i,defeito_reclamado)."'";
	if (pg_result($res,$i,defeito_reclamado) == $defeito_reclamado) echo " checked";
	echo ">".pg_result($res,$i,descricao);
	$resto = $y % 2;
	$y++;
	if($resto == 0){
		echo "</td></tr>\n";
		echo "						<tr><td align='left'>";
	} else {
		echo "</td>\n";
		echo "							<td align='left'>";
	}
}
echo "</td></tr></table>\n";

}
?>
	<TABLE width=600 border="0" cellpadding="3" cellspacing="3">
			<TR class='table_line'>
				<TD colspan='4' align='center'><textarea name='reclamacao' cols=70 rows=5><? echo $reclamacao; ?></textarea></TD>
			</TR>


			<TR class='menu_top'>
				<TD colspan='4'>Informações sobre posto</TD>
			</TR>
			<TR class='table_line'>

			<TABLE width='600' align='center' border='0' cellspacing='1' cellpadding='1'>
			<input type='hidden' name='btnacao' value=''>

			<? 
			if($login_fabrica == 15 AND $natureza == 'Informação')
				{
			?>

			<TR class='menu_top'>
				<TD COLSPAN='4' ALIGN='center'> Relação dos Postos </TD>
			</TR>

			<TR>
				<TD class='table_line' ALIGN='left'>Código</td>
				<TD class='table_line' align='left'><input type='text' name='posto_codigo' size='18' value='<? echo $posto_codigo; ?>'>
					<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_callcenter(document.frm_callcenter.posto_codigo,document.frm_callcenter.posto_nome,document.frm_callcenter.posto_cidade,document.frm_callcenter.posto_estado,document.frm_callcenter.posto_bairro,document.frm_callcenter.posto_cnpj,document.frm_callcenter.posto_linha,'codigo')" style="cursor:pointer;">
				</td>

				<TD class='table_line' ALIGN='left'>Razão Social </td>
				<TD class='table_line' align='left'><input type='text' name='posto_nome' size='45' value='<? echo $posto_nome; ?>' >
					<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_callcenter(document.frm_callcenter.posto_codigo,document.frm_callcenter.posto_nome,document.frm_callcenter.posto_cidade,document.frm_callcenter.posto_estado,document.frm_callcenter.posto_bairro,document.frm_callcenter.posto_cnpj,document.frm_callcenter.posto_linha,'nome')" style="cursor:pointer;">
				</td>
			</TR>

			<TR>
				<TD class='table_line' ALIGN='left'>Cidade </td>
				<TD class='table_line' align='left'><input type='text' name='posto_cidade' size='18' value='<? echo $posto_cidade; ?>'>
					<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_callcenter(document.frm_callcenter.posto_codigo,document.frm_callcenter.posto_nome,document.frm_callcenter.posto_cidade,document.frm_callcenter.posto_estado,document.frm_callcenter.posto_bairro,document.frm_callcenter.posto_cnpj,document.frm_callcenter.posto_linha,'cidade')" style="cursor:pointer;">
				</td>

				<td class='table_line' align='left'>Estado</td>
				<td class='table_line' align='left'><input type='text' name='posto_estado' size='2' value='<? echo $posto_estado; ?>' >
					<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_callcenter(document.frm_callcenter.posto_codigo,document.frm_callcenter.posto_nome,document.frm_callcenter.posto_cidade,document.frm_callcenter.posto_estado,document.frm_callcenter.posto_bairro,document.frm_callcenter.posto_cnpj,document.frm_callcenter.posto_linha,'estado')" style="cursor:pointer;">
				</TD>
			</TR>

			<TR>
				<td class='table_line' align='left'>Bairro</td>
				<td class='table_line' align='left'><input type='text' name='posto_bairro' size='18' value='<? echo $posto_bairro; ?>' >
					<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_callcenter(document.frm_callcenter.posto_codigo,document.frm_callcenter.posto_nome,document.frm_callcenter.posto_cidade,document.frm_callcenter.posto_estado,document.frm_callcenter.posto_bairro,document.frm_callcenter.posto_cnpj,document.frm_callcenter.posto_linha,'bairro')" style="cursor:pointer;">
				</TD>
				
				<TD class='table_line' ALIGN='left'>CNPJ</td>
				<TD class='table_line' align='left'><input type='text' name='posto_cnpj' size='18' value='<? echo $posto_cnpj; ?>'>
					<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_callcenter(document.frm_callcenter.posto_codigo,document.frm_callcenter.posto_nome,document.frm_callcenter.posto_cidade,document.frm_callcenter.posto_estado,document.frm_callcenter.posto_bairro,document.frm_callcenter.posto_cnpj,document.frm_callcenter.posto_linha,'cnpj')" style="cursor:pointer;">
				</td>
			</TR>

			<TR>
				<TD class='table_line' ALIGN='left'>Linha</td>
				<TD class='table_line' align='left'><input type='text' name='posto_linha' size='18' value='<? echo $posto_linha; ?>'>
					<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_callcenter(document.frm_callcenter.posto_codigo,document.frm_callcenter.posto_nome,document.frm_callcenter.posto_cidade,document.frm_callcenter.posto_estado,document.frm_callcenter.posto_bairro,document.frm_callcenter.posto_cnpj,document.frm_callcenter.posto_linha,'linha')" style="cursor:pointer;">
				</td>
			</TR>

			<?}?>

			<TR class='menu_top'>
				<TD colspan='4'>Solução</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='4' align='center'><textarea name='solucao' cols=70 rows=5><? echo "$solucao"; ?></textarea></TD>
			</TR>
			<TR>
				<TD colspan='4' align='center'>
					<input type="hidden" name="btn_acao" value="">
<!-- 					<img src='imagens/btn_voltar.gif' style="cursor:pointer" onclick="javascript: window.location='callcenter_cadastro_1.php?callcenter=<? echo $callcenter; ?>'" ALT="Página anterior" border='0'> -->
					<img src='imagens/btn_continuar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_callcenter.btn_acao.value == '' ) { document.frm_callcenter.btn_acao.value='continuar' ; document.frm_callcenter.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar" border='0'>
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
