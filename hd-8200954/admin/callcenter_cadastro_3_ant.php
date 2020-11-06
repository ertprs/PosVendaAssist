<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

include 'funcoes.php';

if (strlen($_POST['callcenter']) > 0) $callcenter = trim($_POST['callcenter']);
if (strlen($_GET['callcenter']) > 0)  $callcenter = trim($_GET['callcenter']);

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";

if ($btn_acao == "continuar") {

	$realizar_em        = trim($_POST['realizar_em']);
	$enderecada         = trim($_POST['enderecada']);
	$perguntar          = trim($_POST['perguntar']);
	$resposta_dada      = trim($_POST['resposta_dada']);
	$solucionado        = trim($_POST['solucionado']);
	$ja_retirou_produto = trim($_POST['ja_retirou_produto']);

	if (strlen (trim ($realizada_em)) == 0)
		$xrealizada_em = 'null';
	else
		$xrealizada_em = "'" . formata_data($realizada_em) . "'" ;

	if (strlen (trim ($realizar_em)) == 0)
		$xrealizar_em = 'null';
	else
		$xrealizar_em = "'" . formata_data($realizar_em) . "'" ;

	if (strlen (trim ($enderecada)) == 0)
		$xenderecada = 'null';
	else
		$xenderecada = "'" . $enderecada . "'" ;

	if (strlen (trim ($perguntar)) == 0)
		$xperguntar = 'null';
	else
		$xperguntar = "'" . $perguntar . "'" ;

	if (strlen (trim ($resposta_dada)) == 0)
		$xresposta_dada = 'null';
	else
		$xresposta_dada = "'" . $resposta_dada . "'" ;

	if (strlen (trim ($solucionado)) == 0)
		$xsolucionado = 'null';
	else
		$xsolucionado = "'" . $solucionado . "'" ;

	if (strlen (trim ($ja_retirou_produto)) == 0) $ja_retirou_produto = 'f';

	$total_registros = trim($_POST['total_registros']);

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	if (strlen ($providencia) == 0) {
		/*================ INSERE NOVA =========================*/
			$sql = "INSERT INTO tbl_providencia (
						callcenter   ,
						data_gravacao,
						realizar_em  ,
						realizada_em ,
						enderecada   ,
						perguntar    ,
						resposta_dada,
						solucionado  ,
						ja_retirou_produto
					) VALUES (
						$callcenter      ,
						current_timestamp,
						$xrealizar_em    ,
						$xrealizada_em   ,
						$xenderecada     ,
						$xperguntar      ,
						$xresposta_dada  ,
						$xsolucionado    ,
						'$ja_retirou_produto'
					)";
	}else{
			/*================ ALTERA =========================*/
			$sql = "UPDATE tbl_providencia SET
						callcenter    = $callcenter    ,
						realizar_em   = $xrealizar_em ,
						realizada_em  = current_timestamp,
						enderecada    = $xenderecada   ,
						perguntar     = $xperguntar    ,
						resposta_dada = $xresposta_dada,
						solucionado   = $xsolucionado  ,
						ja_retirou_produto = '$ja_retirou_produto'
					WHERE callcenter  = $callcenter
					AND   providencia = $providencia";
	}
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	$msg_erro = substr($msg_erro,6);

	########## A L T E R A   tbl_callcenter ##########
	if (strlen($msg_erro) == 0) {
		$sql = "UPDATE tbl_callcenter SET
						solucionado = $xsolucionado
				WHERE  callcenter = $callcenter
				AND    fabrica    = $login_fabrica";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
	}

	if (strlen ($msg_erro) == 0) {
		// salva as questoes
		for($i=0; $i < $total_registros; $i++){
			// recebe valores
			$questao       = trim($_POST['questao_'.$i]);
			$questionario  = trim($_POST['questionario_'.$i]);
			$tipo_resposta = trim($_POST['tipo_resposta_'.$i]);

			if (!is_numeric($questao)){
				$questao = 'null';
			}
			// insere
			if ($questao <> 'null'){
				$sql = "INSERT INTO tbl_callcenter_questionario (
							callcenter          ,
							questionario        ,
							questionario_reposta,
							resposta            
						)VALUES(
							$callcenter        ,
							$questionario      ,
							$questao           ,
							null                 
						)";
				$resQ = pg_exec ($con,$sql);
			}
		}
	}

	if (strlen ($msg_erro) > 0) {
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}else{
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: callcenter_press.php?callcenter=$callcenter");
		exit;
	}
}

if(strlen($msg_erro) > 0){
	$realizar_em   = $_POST['realizar_em'];
	$enderecada    = $_POST['enderecada'];
	$perguntar     = $_POST['perguntar'];
	$resposta_dada = $_POST['resposta_dada'];
	$solucionado   = $_POST['solucionado'];
	$ja_retirou_produto = $_POST['ja_retirou_produto'];
	$questao       = $_POST['questao_'.$i];
	$questionario  = $_POST['questionario_'.$i];
	$tipo_resposta = $_POST['tipo_resposta_'.$i];

}

/*================ LE BASE DE DADOS =========================*/
if (strlen ($callcenter) > 0) {
	$sql = "SELECT	tbl_callcenter.callcenter          ,
					tbl_callcenter.serie               ,
					tbl_callcenter.revenda_nome        ,
					tbl_callcenter.natureza            ,
					tbl_callcenter.sua_os              ,
					to_char(tbl_callcenter.data_abertura,'DD/MM/YYYY') AS data_abertura,
					tbl_callcenter.reclamacao          ,
					tbl_callcenter.solucao             ,
					tbl_callcenter.cliente  AS consumidor_cliente,
					tbl_callcenter.nota_fiscal         ,
					to_char(tbl_callcenter.data_nf,'DD/MM/YYYY')       AS data_nf      ,
					tbl_cliente.nome        AS consumidor_nome,
					tbl_cliente.cpf         AS consumidor_cpf,
					tbl_cliente.endereco    AS consumidor_endereco,
					tbl_cliente.numero      AS consumidor_numero,
					tbl_cliente.complemento AS consumidor_complemento,
					tbl_cliente.cep         AS consumidor_cep,
					tbl_cliente.bairro      AS consumidor_bairro,
					tbl_cidade.nome         AS consumidor_cidade,
					tbl_cidade.estado       AS consumidor_estado,
					tbl_cliente.rg          AS consumidor_rg,
					tbl_cliente.fone        AS consumidor_fone,
					tbl_cliente_contato.email   AS consumidor_email,
					tbl_cliente_contato.celular AS consumidor_celular,
					tbl_os.sua_os    AS sua_os         ,
					tbl_posto.nome   AS posto_nome     ,
					tbl_posto.fone   AS posto_fone     ,
					tbl_produto.descricao AS produto_descricao,
					tbl_produto.linha                  ,
					tbl_defeito_reclamado.descricao   AS defeito_reclamado_descricao
			FROM	tbl_callcenter
			JOIN	tbl_cliente   USING(cliente)
			JOIN	tbl_cidade    on tbl_cidade.cidade = tbl_cliente.cidade
			LEFT JOIN tbl_cliente_contato USING(cliente)
			LEFT JOIN tbl_os      USING(os)
			LEFT JOIN tbl_posto   ON tbl_posto.posto = tbl_callcenter.posto
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_callcenter.produto
			LEFT JOIN tbl_defeito_reclamado ON tbl_callcenter.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
			WHERE	tbl_callcenter.callcenter = $callcenter
			AND		tbl_callcenter.fabrica    = $login_fabrica";
	$res = pg_exec ($con,$sql);

//	echo $sql."<br>".pg_numrows($res); exit;

	if (pg_numrows($res) > 0) {
		$callcenter          = pg_result ($res,0,callcenter);
		$revenda_nome        = pg_result ($res,0,revenda_nome);
		$serie               = pg_result ($res,0,serie);
		$natureza            = pg_result ($res,0,natureza);
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
		$defeito_reclamado   = pg_result ($res,0,defeito_reclamado_descricao);
		$reclamacao          = pg_result ($res,0,reclamacao);
		$solucao             = pg_result ($res,0,solucao);
		$data_abertura       = pg_result ($res,0,data_abertura);
		$nota_fiscal         = pg_result ($res,0,nota_fiscal);
		$data_nf             = pg_result ($res,0,data_nf);
	}else{
		header('Location: callcenter_cadastro_1.php');
		exit;
	}
}

$title       = "Atendimento Call-Center"; 
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

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
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
				<input type='hidden' name='consumidor_cliente'>
				<TD><? echo $consumidor_nome; ?> &nbsp;</TD>
				<TD><? echo $consumidor_cpf; ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD>Endereco</TD>
				<TD>Número</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $consumidor_endereco; ?> &nbsp;</TD>
				<TD><? echo $consumidor_numero; ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD>Complemento</TD>
				<TD>CEP</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $consumidor_complemento; ?> &nbsp;</TD>
				<TD><? echo $consumidor_cep; ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD>Bairro</TD>
				<TD>Cidade / Estado</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $consumidor_bairro; ?> &nbsp;</TD>
				<TD><? echo $consumidor_cidade; ?> - <? echo $consumidor_estado; ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD>RG/IE</TD>
				<TD>Fone</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $consumidor_rg; ?> &nbsp;</TD>
				<TD><? echo $consumidor_fone; ?> &nbsp;</TD>
			</TR>
			<!--<TR class='menu_top'>
				<TD>e-Mail</TD>
				<TD>Celular</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $consumidor_email; ?> &nbsp;</TD>
				<TD><? echo $consumidor_celular; ?> &nbsp;</TD>
			</TR>-->
		</TABLE>
<hr>
		<TABLE width=600 border="0" cellpadding="3" cellspacing="3">
			<TR class='menu_top'>
				<TD colspan='2'>Número da OS</TD>
				<TD colspan='2'>Data abertura</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='2'><? echo $sua_os; ?> &nbsp;</TD>
				<TD colspan='2'><? echo $data_abertura; ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD colspan='3'>Nome Posto</TD>
				<TD>Telefone</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='3'><? echo $posto_nome; ?> &nbsp;</TD>
				<TD><? echo $posto_fone; ?> &nbsp;</TD>
					<input type='hidden' name = 'posto_codigo'>
			</TR>
			<TR class='menu_top'>
				<TD>Produto</TD>
				<TD>Série</TD>
				<TD>Nota fiscal</TD>
				<TD>Data da compra</TD>
			</TR>
			<TR class='table_line'>
				<TD><? echo $produto_descricao; ?> &nbsp;</TD>
				<TD><? echo $serie; ?> &nbsp;</TD>
				<TD><? echo $data_nf; ?> &nbsp;</TD>
				<TD><? echo $nota_fiscal; ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD colspan='4'>Revenda</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='4'><? echo $revenda_nome; ?> &nbsp;</TD>
			</TR>
		</table>
<hr>
		<TABLE width=600 border="0" cellpadding="3" cellspacing="3">
			<TR class='menu_top'>
				<TD colspan='2'>Ocorrência / Reclamação</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='2'><? echo $defeito_reclamado; ?> &nbsp;</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='2'><? echo nl2br($reclamacao); ?> &nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD colspan='2'>Solução</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='2'><? echo nl2br($solucao); ?> &nbsp;</TD>
			</TR>
		</table>
<?
$sql = "SELECT	questionario ,
				pergunta     ,
				tipo_resposta 
		FROM	tbl_questionario
		WHERE	fabrica = $login_fabrica";
$res = pg_exec($con,$sql);
$NumRows = pg_numrows($res);

if ($NumRows < 0) {
	echo "<hr>\n";
	echo "		<TABLE width=600 border='0' cellpadding='3' cellspacing='3'>\n";
	echo "			<TR class='menu_top'>\n";
	echo "				<TD>Questão</TD>\n";
	echo "				<TD>Resposta</TD>\n";
	for($i=0; $i < $NumRows; $i++){
		echo "			<TR class='table_line'>\n";
		echo "				<TD>".pg_result($res,$i,pergunta)."</TD>\n";
		echo "					<input type='hidden' name='tipo_resposta_$i' value='".pg_result($res,$i,tipo_resposta)."'>\n";
		echo "					<input type='hidden' name='questionario_$i' value='".pg_result($res,$i,questionario)."'>\n";
		echo "				<TD>";
		if (pg_result($res,$i,tipo_resposta) == 't'){
			echo "					<input type='radio' name='questao_$i' value='Sim'";
			echo " checked>Sim ";
			echo "					<input type='radio' name='questao_$i' value='Não'";
			echo ">Não ";
		}else{
			$sql = "SELECT	questionario_resposta,
							resposta
					FROM	tbl_questionario_resposta
					WHERE	questionario = ".pg_result($res,$i,questionario);
			$resX = pg_exec($con,$sql);
			echo "				<select name='questao_$i'>\n";
			echo "					<option selected></option>\n";
			for($j=0; $j < pg_numrows($resX); $j++){
				echo "					<option value='".pg_result($resX,$j,questionario_resposta)."'";
				if (pg_result($resX,$j,questionario_resposta) == pg_result($res,$i,questionario)) echo "selected";
				echo ">".pg_result($resX,$j,resposta)."</option>\n";
			}
			echo "				</select>\n";
		}
		echo "				</TD>\n";
		echo "			</TR>\n";
	}
	echo "		</table>\n";
}
echo "		<input type='hidden' name='total_registros' value='$i'>\n";
?>
<hr>
		<TABLE width=600 border="0" cellpadding="3" cellspacing="3">
<?
$sql = "SELECT	to_char(tbl_providencia.realizar_em,'DD/MM/YYYY')   AS realizar_em ,
				to_char(tbl_providencia.realizada_em, 'DD/MM/YYYY') AS realizada_em,
				tbl_providencia.enderecada                                         ,
				tbl_providencia.perguntar                                          ,
				tbl_providencia.resposta_dada                                      ,
				tbl_providencia.solucionado                                        ,
				tbl_providencia.ja_retirou_produto
		FROM	tbl_providencia
		JOIN	tbl_callcenter USING(callcenter)
		WHERE	tbl_providencia.callcenter  = $callcenter 
		AND		tbl_callcenter.fabrica      = $login_fabrica
		AND		tbl_providencia.solucionado = 't'";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0){

	echo "<TR class='menu_top'>\n";
	echo "<TD colspan=3>HISTÓRICO DO ATENDIMENTO</TD>\n";
	echo "</TR>\n";
	echo "<TR class='menu_top'>\n";
	echo "<TD>Encaminhado para</TD>\n";
	echo "<TD>Realizar em</TD>\n";
	echo "<TD>Solucionado</TD>\n";
	echo "</TR>\n";
	echo "<TR class='menu_top'>\n";
	echo "<TD colspan=3>Perguntar</TD>\n";
	echo "</TR>\n";
	echo "<TR class='menu_top'>\n";
	echo "<TD colspan=3>Resposta dada</TD>\n";
	echo "</TR>\n";

	for($i=0; $i<pg_numrows($res); $i++){
		$realizar_em   = pg_result ($res,$i,realizar_em);
		$realizada_em  = pg_result ($res,$i,realizada_em);
		$enderecada    = pg_result ($res,$i,enderecada);
		$perguntar     = pg_result ($res,$i,perguntar);
		$resposta_dada = pg_result ($res,$i,resposta_dada);
		$solucionado   = pg_result ($res,$i,solucionado);

		$bg = ($i%2 == 0)? '#F8F8F8' : '#FBFDFF';

		echo "<TR class='table_line'>\n";
		echo "<TD align=center bgcolor='$bg'>$enderecada</TD>\n";
		echo "<TD align=center bgcolor='$bg'>$realizar_em</TD>\n";
		echo "<TD align=center bgcolor='$bg'>";
		if ($solucionado == 't') echo "Sim - Em $realizada_em "; else echo "Não";
		echo "</TD>\n";
		echo "</TR>\n";
		echo "<TR class='table_line'>\n";
		echo "<TD colspan=3 bgcolor='$bg'>".nl2br($perguntar)."</TD>\n";
		echo "</TR>\n";
		echo "<TR class='table_line'>\n";
		echo "<TD colspan=3 bgcolor='$bg'>".nl2br($resposta_dada)."</TD>\n";
		echo "</TR>\n";
	}
}

$sql = "SELECT	tbl_providencia.providencia                                        ,
				to_char(tbl_providencia.realizar_em,'DD/MM/YYYY')   AS realizar_em ,
				to_char(tbl_providencia.realizada_em, 'DD/MM/YYYY') AS realizada_em,
				tbl_providencia.enderecada                                         ,
				tbl_providencia.perguntar                                          ,
				tbl_providencia.resposta_dada                                      ,
				tbl_providencia.solucionado                                        ,
				tbl_providencia.ja_retirou_produto
		FROM	tbl_providencia
		JOIN	tbl_callcenter USING(callcenter)
		WHERE	tbl_providencia.callcenter  = $callcenter 
		AND		tbl_callcenter.fabrica      = $login_fabrica
		AND		tbl_providencia.solucionado <> 't'
		ORDER BY tbl_providencia.providencia DESC LIMIT 1 ";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0){
	$providencia   = pg_result ($res,0,providencia);
	$realizar_em   = pg_result ($res,0,realizar_em);
	$realizada_em  = pg_result ($res,0,realizada_em);
	$enderecada    = pg_result ($res,0,enderecada);
	$perguntar     = pg_result ($res,0,perguntar);
	$resposta_dada = pg_result ($res,0,resposta_dada);
	$solucionado   = pg_result ($res,0,solucionado);
	$ja_retirou_produto = pg_result ($res,0,ja_retirou_produto);;
}else{
	$providencia   = "";
	$realizar_em   = "";
	$realizada_em  = "";
	$enderecada    = "";
	$perguntar     = "";
	$resposta_dada = "";
	$solucionado   = "";
	$ja_retirou_produto = "";
}
	echo "<input type='hidden' name='providencia' value='$providencia'>";
?>
			<TR class='table_line'>
				<TD colspan=3>&nbsp;</TD>
			</TR>
			<TR class='menu_top'>
				<TD>Encaminhado para</TD>
				<TD>Realizar em</TD>
				<TD>Já Retirou Produto</TD>
				<TD>Solucionado</TD>
			</TR>
			<TR class='table_line'>
				<TD align=center>
					<select name='enderecada'>
						<option selected></option>
						<option value="Gerente Assistência Técnica" <? if ($enderecada == "Gerente Assistência Técnica") echo " selected"?>>Gerente Assistência Técnica</option>
						<option value="Gerente Comercial"           <? if ($enderecada == "Gerente Comercial") echo " selected"?>>Gerente Comercial</option>
						<option value="Posto"                       <? if ($enderecada == "Posto") echo " selected"?>>Posto</option>
						<option value="Retorno"                     <? if ($enderecada == "Retorno") echo " selected"?>>Retorno</option>
					</select>
				</TD>
				<TD align=center><input type='text' name='realizar_em' value='<? echo $realizar_em; ?>' size='10' maxlength='10'></TD>
				<TD align=center nowrap>
					<input type='radio' name='ja_retirou_produto' value='t' <? if ($ja_retirou_produto == 't') echo " checked"?>>Sim 
					<input type='radio' name='ja_retirou_produto' value='f' <? if ($ja_retirou_produto <> 't') echo " checked"?>>Não
				</TD>
				<TD align=center nowrap>
					<input type='radio' name='solucionado' value='t' <? if ($solucionado == 't') echo " checked"?>>Sim 
					<input type='radio' name='solucionado' value='n' <? if ($solucionado <> 't') echo " checked"?>>Não
				</TD>
			</TR>
			<TR class='menu_top'>
				<TD colspan=4>Perguntar</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan=4><textarea name='perguntar' cols=69 rows=3><? echo $perguntar ?></textarea></TD>
			</TR>
			<TR class='menu_top'>
				<TD colspan=4>Resposta dada</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan=4><textarea name='resposta_dada' cols=69 rows=3><? echo $resposta_dada ?></textarea></TD>
			</TR>
		</table>
		<br>
		<TABLE width=600 border="0" cellpadding="3" cellspacing="3">
			<TR>
				<TD colspan='2' align='center'>
					<input type="hidden" name="btn_acao" value="">
<!-- 					<img src='imagens/btn_voltar.gif' style="cursor:pointer" onclick="javascript: window.location='callcenter_cadastro_2.php?callcenter=<? echo $callcenter; ?>'" ALT="Página anterior" border='0'> -->
						<img src='imagens/btn_continuar.gif' style="cursor:pointer" onclick="javascript: document.frm_callcenter.btn_acao.value='continuar' ; document.frm_callcenter.submit()" ALT="Continuar" border='0'>
					<!--
					<input type="hidden" name="btn_acao" value="">
					<img src='imagens/btn_continuar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_callcenter.btn_acao.value == '' ) { document.frm_callcenter.btn_acao.value='continuar' ; document.frm_callcenter.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar" border='0'>					
					-->
				</TD>
			</TR>
		</TABLE>
		</FORM>
	</td>
	<td><img height="1" width="16" src="/imagens/spacer.gif"></td>
</tr>
</table>

<p>

<? include "rodape.php"; ?>