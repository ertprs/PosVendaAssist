<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica != 7 ){
	header("Location: menu_os.php");
	exit;
}

// ###############################################################
// Funcao para calcular diferenca entre duas horas
// ###############################################################
function calcula_hora($hora_inicio, $hora_fim){
	// Explode
	$ehora_inicio = explode(":",$hora_inicio);
	$ehora_fim    = explode(":",$hora_fim);

	// Tranforma horas em minutos
	$mhora_inicio = ($ehora_inicio[0] * 60) + $ehora_inicio[1];
	$mhora_fim    = ($ehora_fim[0] * 60) + $ehora_fim[1];

	// Subtrai as horas
	$total_horas = ( $mhora_fim - $mhora_inicio );

	// Tranforma em horas
	$total_horas_div = $total_horas / 60;

	// Valor de horas inteiro
	$total_horas_int = intval($total_horas_div);

	// Resto da subtracao = pega minutos
	$total_horas_sub = $total_horas - ($total_horas_int * 60);

	// Horas trabalhadas
	if ($total_horas_sub < 10) $total_horas_sub = "0".$total_horas_sub;
	$horas_trabalhadas = $total_horas_int.":".$total_horas_sub;

	// Retorna valor
	return $horas_trabalhadas;
}
// ###############################################################

$msg_erro = "";
$msg_debug = "";

$qtde_visita = 3;

if (strlen($_GET['os']) > 0)  $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if (strlen($os) == 0) {
	header("Location: os_parametros.php");
	exit;
}

if(strlen($_POST['btn_acao']) > 0) $btn_acao = strtolower($_POST['btn_acao']);

if ($btn_acao == "gravar"){
	// verifica se foram setados os dados de cadastro
	$taxa_visita   = trim($_POST['taxa_visita']);
	$qtde_hora     = trim($_POST['qtde_hora']);
	$hora_tecnica  = trim($_POST['hora_tecnica']);
	$qtde_diaria   = trim($_POST['qtde_diaria']);
	$diaria        = trim($_POST['diaria']);
	$qtde_km       = trim($_POST['qtde_km']);
	$visita_por_km = trim($_POST['visita_por_km']);

	if(strlen($taxa_visita) > 0)
		$xtaxa_visita = "'".str_replace(",",".",$taxa_visita)."'";
	else
		$xtaxa_visita = '0';

	if (strlen ($qtde_hora) > 0) {

		if (strlen ($qtde_hora) < 5) $qtde_hora = "0" . $qtde_hora ;

		$horas   = substr ($qtde_hora,0,2);
		$minutos = substr ($qtde_hora,3,2);
		$minutos = $minutos / 60 ;

		$qtde_hora = $horas + $minutos;
		$xqtde_hora = "'".$qtde_hora."'";
	}else{
		$xqtde_hora = '0';
	}

	if(strlen($hora_tecnica) > 0)
		$xhora_tecnica = "'".str_replace(",",".",$hora_tecnica)."'";
	else
		$xhora_tecnica = '0';

	if(strlen($qtde_diaria) > 0)
		$xqtde_diaria = "'".str_replace(",",".",$qtde_diaria)."'";
	else
		$xqtde_diaria = '0';

	if(strlen($diaria) > 0)
		$xdiaria = "'".str_replace(",",".",$diaria)."'";
	else
		$xdiaria = '0';

	if(strlen($qtde_km) > 0)
		$xqtde_km = "'".str_replace(",",".",$qtde_km)."'";
	else
		$xqtde_km = '0';

	if(strlen($visita_por_km) > 0)
		$xvisita_por_km = "'".str_replace(",",".",$visita_por_km)."'";
	else
		$xvisita_por_km = '0';


	$faturamento_cliente_revenda = trim($_POST['faturamento_cliente_revenda']);
	$condicao                    = trim($_POST['condicao']);
	$natureza_servico            = trim($_POST['natureza_servico']);
	$total_pecas_novas           = trim($_POST['total_pecas_novas']);
	$total_pecas_recuperadas     = trim($_POST['total_pecas_recuperadas']);

	if(strlen($natureza_servico) > 0)
		$xnatureza_servico = "'".$natureza_servico."'";
	else
		$xnatureza_servico = 'null';

	if(strlen($desconto_peca) > 0)
		$xdesconto_peca = "'".str_replace(",",".",$desconto_peca)."'";
	else
		$xdesconto_peca = '0';

	if(strlen($desconto_peca_recuperada) > 0)
		$xdesconto_peca_recuperada = "'".str_replace(",",".",$desconto_peca_recuperada)."'";
	else
		$xdesconto_peca_recuperada = 'null';

	if(strlen($deslocamento_km) > 0)
		$xdeslocamento_km = "'".$deslocamento_km."'";
	else
		$xdeslocamento_km = 'null';

	if(strlen($condicao) > 0)
		$xcondicao = "'".$condicao."'";
	else
		$xcondicao = 'null';

	if(strlen($total_pecas_novas) > 0)
		$xtotal_pecas_novas = "'".str_replace(",",".",$total_pecas_novas)."'";
	else
		$xtotal_pecas_novas = 0;

	if(strlen($total_pecas_recuperadas) > 0)
		$xtotal_pecas_recuperadas = "'".str_replace(",",".",$total_pecas_recuperadas)."'";
	else
		$xtotal_pecas_recuperadas = 0;

	if(strlen($faturamento_cliente_revenda) > 0)
		$xfaturamento_cliente_revenda = "'".$faturamento_cliente_revenda."'";
	else
		$xfaturamento_cliente_revenda = "'c'";

	if (strlen($msg_erro) == 0){
		$res = pg_exec($con,"BEGIN TRANSACTION");

		// update em OS
//					cobrar_percurso = $xcobrar_percurso ,
		$sql = "UPDATE tbl_os SET
					qtde_km         = $xqtde_km         ,
					visita_por_km   = $xvisita_por_km   ,
					taxa_visita     = $xtaxa_visita     ,
					qtde_hora       = $xqtde_hora       ,
					hora_tecnica    = $xhora_tecnica    ,
					qtde_diaria     = $xqtde_diaria     ,
					diaria          = $xdiaria          
				WHERE os = $os ";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		if (strlen($msg_erro) == 0){
			$sql = "SELECT *
					FROM	tbl_os_extra
					WHERE	os = $os";
			$res = pg_exec($con,$sql);
			
			if (pg_numrows($res) > 0){
				// update em OS_EXTRA
				$sql = "UPDATE tbl_os_extra SET
							desconto_peca               = $xdesconto_peca              ,
							desconto_peca_recuperada    = $xdesconto_peca_recuperada   ,
							faturamento_cliente_revenda = $xfaturamento_cliente_revenda,
							natureza_servico            = $xnatureza_servico           
						WHERE os = $os ";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro) == 0){
		for($i=0; $i<$qtde_produtos; $i++){
			$os_produto               = trim($_POST['os_produto_'.$i]);
			$mo                       = trim($_POST['mao_de_obra_'.$i]);
			$regulagem_peso_padrao    = trim($_POST['regulagem_peso_padrao_'.$i]);
			$certificado_conformidade = trim($_POST['certificado_conformidade_'.$i]);

			if(strlen($regulagem_peso_padrao) > 0)
				$regulagem_peso_padrao = "'".str_replace(",",".",$regulagem_peso_padrao)."'";
			else
				$regulagem_peso_padrao = 'null';

			if(strlen($certificado_conformidade) > 0)
				$certificado_conformidade = "'".str_replace(",",".",$certificado_conformidade)."'";
			else
				$certificado_conformidade = 'null';

			if(strlen($mo) > 0)
				$mo = "'".str_replace(",",".",$mo)."'";
			else
				$mo = 'null';

			$sql = "UPDATE tbl_os_produto SET
						regulagem_peso_padrao    = $regulagem_peso_padrao   ,
						certificado_conformidade = $certificado_conformidade,
						mao_de_obra              = $mo
					WHERE os_produto = $os_produto ";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0){
			$sql = "UPDATE tbl_os SET
						condicao        = $xcondicao                ,
						pecas           = $xtotal_pecas_novas       ,
						recondicionadas = $xtotal_pecas_recuperadas
					WHERE os = $os ";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0 AND strlen($contrato_numero) > 0){
			$sql = "UPDATE tbl_cliente SET
						contrato_numero = $contrato_numero
					WHERE cliente = $cliente ";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0){
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		//header ("Location: os_filizola_faturamento_print.php?os=$os");
		//header ("Location: os_filizola_relatorio.php?os=$os&print=1");
		
		echo "<script>";
		echo "	document.location='os_filizola_faturamento_print.php?os=$os';";
		echo "</script>";
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}


// #################################################################################//
if (strlen($os) > 0){
	$sql = "SELECT	tbl_os.condicao                          ,
					tbl_os.cobrar_percurso                   ,
					tbl_os.qtde_km                           ,
					tbl_os.visita_por_km                     ,
					tbl_os.taxa_visita                       ,
					tbl_os.qtde_hora                         ,
					tbl_os.hora_tecnica                      ,
					tbl_os.qtde_diaria                       ,
					tbl_os.diaria                            ,
					tbl_os_extra.obs                         ,
					tbl_os_extra.natureza_servico            ,
					tbl_os_extra.desconto_peca               ,
					tbl_os_extra.desconto_peca_recuperada    ,
					tbl_os_extra.faturamento_cliente_revenda ,
					tbl_cliente.consumidor_final             ,
					tbl_cliente.contrato_numero              
			FROM	tbl_os
			LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			LEFT JOIN tbl_cliente USING(cliente)
			WHERE	tbl_os.os    = $os
			AND		tbl_os.fabrica = $login_fabrica ";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$condicao                    = pg_result($res,0,condicao);
		$cobrar_percurso             = pg_result($res,0,cobrar_percurso);
		$qtde_km                     = pg_result($res,0,qtde_km);
		$visita_por_km               = pg_result($res,0,visita_por_km);
		$taxa_visita                 = pg_result($res,0,taxa_visita);
		$qtde_hora                   = pg_result($res,0,qtde_hora);
		$hora_tecnica                = pg_result($res,0,hora_tecnica);
		$qtde_diaria                 = pg_result($res,0,qtde_diaria);
		$diaria                      = pg_result($res,0,diaria);
		$obs                         = pg_result($res,0,obs);
		$natureza_servico            = pg_result($res,0,natureza_servico);
		$desconto_peca               = pg_result($res,0,desconto_peca);
		$desconto_peca_recuperada    = pg_result($res,0,desconto_peca_recuperada);
		$faturamento_cliente_revenda = pg_result($res,0,faturamento_cliente_revenda);
		$consumidor_final            = pg_result($res,0,consumidor_final);
		$contrato_numero             = pg_result($res,0,contrato_numero);
	}
}


// #################################################################################//
if (strlen ($os) > 0) {
	$sql = "SELECT * 
			FROM   vw_os_print 
			WHERE  os = $os";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os					= pg_result ($res,0,sua_os);
		$data_abertura			= pg_result ($res,0,data_abertura);
		$quem_abriu_chamado		= pg_result ($res,0,quem_abriu_chamado);
		$obs_os					= pg_result ($res,0,obs);
		$defeito_reclamado		= pg_result ($res,0,defeito_reclamado);
		$cliente				= pg_result ($res,0,cliente);
		$cliente_nome			= pg_result ($res,0,cliente_nome);
		$cliente_cpf			= pg_result ($res,0,cliente_cpf);
		$cliente_rg 			= pg_result ($res,0,cliente_rg);
		$cliente_endereco		= pg_result ($res,0,cliente_endereco);
		$cliente_numero			= pg_result ($res,0,cliente_numero);
		$cliente_complemento	= pg_result ($res,0,cliente_complemento);
		$cliente_bairro			= pg_result ($res,0,cliente_bairro);
		$cliente_cep			= pg_result ($res,0,cliente_cep);
		$cliente_cep			= substr($cliente_cep,0,5)."-".substr($cliente_cep,5,10);
		$cliente_cidade			= pg_result ($res,0,cliente_cidade);
		$cliente_fone			= pg_result ($res,0,cliente_fone);
		$cliente_nome			= pg_result ($res,0,cliente_nome);
		$cliente_estado			= pg_result ($res,0,cliente_estado);
		$cliente_contrato		= pg_result ($res,0,cliente_contrato);
		$posto_endereco			= pg_result ($res,0,posto_endereco);
		$posto_numero			= pg_result ($res,0,posto_numero);
		$posto_cep				= pg_result ($res,0,posto_cep);
		$posto_cep				= substr($posto_cep,0,5)."-".substr($posto_cep,5,10);
		$posto_cidade			= pg_result ($res,0,posto_cidade);
		$posto_estado			= pg_result ($res,0,posto_estado);
		$posto_fone				= pg_result ($res,0,posto_fone);
		$posto_cnpj				= pg_result ($res,0,posto_cnpj);
		$posto_ie				= pg_result ($res,0,posto_ie);
	}
}

$title = "Relatório de Movimento para Faturamento";
$layout_menu = "callcenter";
include 'cabecalho.php';

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
}

.table_line2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
}

.table_line3 {
	text-align: right;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
}

.top_lst {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_lst {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	color:#000000;
	background-color: #ffffff
}

input, select {
	font-size: 10px;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: normal;
	border: 1x solid #a0a0a0;
	background-color: #FFFFFF;
}

input.AlignRight {
	text-align:right;
}

textarea {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1x solid #a0a0a0;
	background-color: #FFFFFF;
}

</style>

<body>
<?
	if (strlen($msg_erro) > 0){
?>
<TABLE>
<TR>
	<TD><? echo $msg_erro; ?></TD>
</TR>
</TABLE>
<?
	}
?>

<form name='frm_os' action='<? echo $PHP_SELF; ?>' method="post">
<input type="hidden" name="os" value="<? echo $os; ?>">
<input type="hidden" name="cliente" value="<? echo $cliente; ?>">
<TABLE class="borda" width="700px" border="0" cellspacing="2" cellpadding="0">

<?	########## DADOS DO CLIENTE ########## ?>

<?
if (strlen (trim ($cliente_ie)) == 0) $cliente_ie = "&nbsp";

switch (strlen (trim ($cliente_cpf))) {
case 0:
	$cliente_cpf = "&nbsp";
	break;
case 11:
	$cliente_cpf = substr ($cliente_cpf,0,3) . "." . substr ($cliente_cpf,3,3) . "." . substr ($cliente_cpf,6,3) . "-" . substr ($cliente_cpf,9,2);
	break;
case 14:
	$cliente_cpf = substr ($cliente_cpf,0,2) . "." . substr ($cliente_cpf,2,3) . "." . substr ($cliente_cpf,5,3) . "/" . substr ($cliente_cpf,8,4) . "-" . substr ($cliente_cpf,12,2);
	break;
}
?>
<TR>
	<TD class="menu_top" colspan="7" bgcolor="#d0d0d0">DADOS DO CLIENTE</TD>
</TR>

<TR>
	<TD class="line_lst">Raz.Soc.</TD>
	<TD class="table_line2" colspan='2'><? echo $cliente_nome ?>&nbsp</TD>
	<TD class="line_lst">CNPJ</TD>
	<TD class="table_line2" nowrap><? echo $cliente_cpf ?>&nbsp</TD>
	<TD class="line_lst">I.E.</TD>
	<TD class="table_line2"><? echo $cliente_rg ?>&nbsp</TD>
</TR>

<!-- ====== ENDEREÇO E TELEFONE ================ -->
<TR>
	<TD class="line_lst">Endereço</TD>
	<TD class="table_line2" colspan='2'><? echo $cliente_endereco . ", " . $cliente_numero . " " . $cliente_complenento ?>&nbsp</TD>
	<TD class="line_lst">CEP</TD>
	<TD class="table_line2"><? echo $cliente_cep ?>&nbsp</TD>
	<TD class="line_lst">Telefone</TD>
	<TD class="table_line2"><? echo $cliente_fone ?>&nbsp</TD>
</TR>

<!-- ====== Cep Municipio UF ================ -->
<TR>
	<TD class="line_lst">Bairro</TD>
	<TD class="table_line2" colspan=2><? echo $cliente_bairro ?>&nbsp</TD>
	<TD class="line_lst">Municipio</TD>
	<TD class="table_line2"><? echo $cliente_cidade ?>&nbsp</TD>
	<TD class="line_lst">Estado</TD>
	<TD class="table_line2"><? echo $cliente_estado ?>&nbsp</TD>
</TR>

<!-- ====== CONTATO E CHAMADO ================ -->
<TR>
	<TD class="line_lst">Contato</TD>
	<TD class="table_line2" colspan="6"><? echo $quem_abriu_chamado ?>&nbsp</TD>
</TR>
<TR>
</TR>

<!-- ====== MOTIVO ================ -->
<TR>
	<TD class="line_lst">Obs.:</TD>
	<TD class="table_line2" colspan="6"><? echo nl2br($obs_os); ?>&nbsp</TD>
</TR>
</TABLE>

<TABLE class="borda" width="700px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="7" bgcolor="#d0d0d0">COBRANÇA</TD>
</TR>

<TR>
	<TD class="line_lst">Endereço</TD>
	<TD class="table_line2" colspan='2'><? echo $cliente_endereco . ", " . $cliente_numero . " " . $cliente_complemento . $cliente_bairro ?>&nbsp</TD>
	<TD class="line_lst">CEP</TD>
	<TD class="table_line2"><? echo $cliente_cep ?>&nbsp</TD>
	<TD class="line_lst">Telefone</TD>
	<TD class="table_line2"><? echo $cliente_fone ?>&nbsp</TD>
</TR>
<TR>
	<TD class="line_lst">Bairro</TD>
	<TD class="table_line2" colspan=2><? echo $cliente_bairro ?>&nbsp</TD>
	<TD class="line_lst">Município</TD>
	<TD class="table_line2"><? echo $cliente_cidade ?>&nbsp</TD>
	<TD class="line_lst">Estado</TD>
	<TD class="table_line2"><? echo $cliente_estado ?>&nbsp</TD>
</TR>
</TABLE>

<TABLE width="700px" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD class="menu_top" colspan="7" bgcolor="#d0d0d0">&nbsp;</TD>
</TR>
<TR>
	<TD class="line_lst">OS</TD>
	<TD class="table_line2" width="40%" colspan='2'><input type="text" name="sua_os" value="<? echo $sua_os ?>" size="10" maxlength="">&nbsp</TD>
	<TD class="line_lst" nowrap>Nat. do Serviço</TD>
	<TD class="table_line2" width="25%">
		<select name="natureza_servico">
			<option value="" selected></option>
			<option value="CONSERTO" <? if ($natureza_servico == "CONSERTO") echo " selected "; ?>>CONSERTO</option>
			<option value="CONTRATO" <? if ($natureza_servico == "CONTRATO") echo " selected "; ?>>CONTRATO</option>
			<option value="MONTAGEM" <? if ($natureza_servico == "MONTAGEM") echo " selected "; ?>>MONTAGEM</option>
			<option value="INSTALAÇÃO" <? if ($natureza_servico == "INSTALAÇÃO") echo " selected "; ?>>INSTALAÇÃO</option>
		</select>
	</TD>
	<TD class="line_lst" nowrap>Cond. de pgto.</TD>
	<TD class="table_line2">
<?
	$sql = "SELECT *
			FROM   tbl_condicao
			WHERE  fabrica = $login_fabrica";
	$res2 = pg_exec ($con,$sql);
?>
		<select name="condicao">
			<option value="" selected></option>
<?
	for ($i=0; $i<pg_numrows($res2); $i++){
		$cod_condicao = pg_result($res2,$i,condicao);
		$descricao    = pg_result($res2,$i,descricao);
		echo "<option value='$cod_condicao' ";
		if ($condicao == $cod_condicao) echo " selected";
		echo ">$descricao</option>\n";
	}
?>
			<? echo $pagamento ?>
		</select>
	</TD>
</TR>
<TR>
	<TD class="line_lst">Endereço</TD>
	<TD class="table_line2"><input type="text" name="cliente_endereco_2" value="<? echo $cliente_endereco ?>" size='20'></TD>
	<TD class="line_lst">Nº <input type="text" name="cliente_numero_2" size='3' value="<? echo $cliente_numero ?>">&nbsp</TD>
	<TD class="line_lst">Municipio</TD>
	<TD class="table_line2"><input type="text" name="cliente_cidade" value="<? echo $cliente_cidade ?>">&nbsp</TD>
	<TD class="line_lst">Estado</TD>
	<TD class="table_line2"><input type="text" name="cliente_estado" value="<? echo $cliente_estado ?>" size='2' maxlength='2'>&nbsp</TD>
</TR>
<TR>
	<TD class="line_lst">Contato</TD>
	<TD class="table_line2" colspan="2"><? echo $quem_abriu_chamado ?>&nbsp</TD>
	<TD class="line_lst">Faturar para:</TD>
	<TD class="table_line2">
		<INPUT TYPE='radio' NAME='faturamento_cliente_revenda' VALUE='c' <? if ($faturamento_cliente_revenda == 'c') echo " checked " ?> >Cliente &nbsp;
		<INPUT TYPE='radio' NAME='faturamento_cliente_revenda' VALUE='r' <? if ($faturamento_cliente_revenda == 'r') echo " checked " ?> >Revenda
	</TD>
	<TD class="line_lst">Contrato nº </TD>
	<TD class="table_line2"><input type="text" name="contrato_numero" value="<? echo $contrato_numero ?>" size='15'></TD>
</TR>
</TABLE>

<br>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="2">
	<tr>
		<td COLSPAN=6 class="menu_top">OBS: PARA COLOCAR O MESMO VALOR EM TODOS OS PRODUTOS, DIGITE O VALOR NO PRIMEIRO CAMPO E CLIQUE SOBRE O TÍTULO</td>
	</tr>
	<tr>
		<td class="menu_top" rowspan=2>#</td>
		<td class="menu_top" COLSPAN=2>EQUIPAMENTO</td>
		<td class="menu_top">SÉRIE</td>
		<td class="menu_top">CAPACIDADE</td>
		<td class="menu_top">DEFEITO<br>RECLAMADO</td>
	</tr>
	<tr>
		<td class="menu_top"><a href='javascript:TodosMO();'>VALOR DE<BR>MÃO-DE-OBRA</A></td>
		<td class="menu_top"><a href='javascript:TodosRP();'>REGULAGEM<br>PESO PADRÃO</A></td>
		<td class="menu_top"><a href='javascript:TodosCC();'>CERTIFICADO<br>CONFORMIDADE</A></td>
		<TD class='menu_top'>SELO</TD>
		<TD class='menu_top'>LACRE ENCONTRADO</TD>
	</tr>
<?
	$sql = "SELECT	tbl_produto.referencia                    ,
					tbl_produto.descricao                     ,
					tbl_os_produto.os_produto                 ,
					tbl_os_produto.serie                      ,
					tbl_os_produto.capacidade                 ,
					tbl_os_produto.regulagem_peso_padrao      ,
					tbl_os_produto.certificado_conformidade   ,
					tbl_os_produto.mao_de_obra                ,
					tbl_defeito_reclamado.descricao AS defeito,
					tbl_os_produto.selo                       ,
					tbl_os_produto.lacre_encontrado
			FROM	tbl_os_produto
			JOIN	tbl_produto           USING (produto)
			JOIN	tbl_defeito_reclamado USING (defeito_reclamado)
			WHERE	tbl_os_produto.os = $os
			ORDER BY tbl_os_produto.os_produto";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {

		$cor = ($i%2 == 0) ? '#f8f8f8' : '#e9e9e9';

		echo "<tr bgcolor='$cor'>\n";
		echo "<td class='table_line2' rowspan=2><input type='hidden' name='os_produto_$i' value='".pg_result ($res,$i,os_produto)."'><b>";
		echo $i + 1;
		echo "</b></td>\n";
		echo "<td class='table_line2' colspan=2>".pg_result ($res,$i,referencia)." - ".pg_result ($res,$i,descricao)."</td>\n";
		echo "<td class='table_line2'>".pg_result ($res,$i,serie)."</td>\n";
		echo "<td class='table_line2'>".pg_result ($res,$i,capacidade)."</td>\n";
		echo "<td class='table_line2' align='center'>".pg_result ($res,$i,defeito)."</td>\n";
		echo "</tr>\n";

		echo "<tr bgcolor='$cor'>\n";
		echo "<td class='table_line3'><input type='text' name='mao_de_obra_$i'              value='".number_format (pg_result ($res,$i,mao_de_obra),2,",",".")."' size='7' class='AlignRight'></td>\n";
		echo "<td class='table_line3'><input type='text' name='regulagem_peso_padrao_$i'    value='".number_format (pg_result ($res,$i,regulagem_peso_padrao),2,",",".")."' size='7' class='AlignRight'></td>\n";
		echo "<td class='table_line3'><input type='text' name='certificado_conformidade_$i' value='".number_format (pg_result ($res,$i,certificado_conformidade),2,",",".")."' size='7' class='AlignRight'></td>\n";
		echo "<td class='table_line2'>".pg_result ($res,$i,selo)."</td>\n";
		echo "<td class='table_line2'>".pg_result ($res,$i,lacre_encontrado)."</td>\n";
		echo "</tr>\n";

		$array_os_produto[$i] = pg_result ($res,$i,os_produto);
		$array_posicao[$i]    = $i+1;

		$total_produtos += (pg_result ($res,$i,regulagem_peso_padrao) + pg_result ($res,$i,certificado_conformidade) + pg_result ($res,$i,mao_de_obra));

	}
	echo "<input type='hidden' name='qtde_produtos' value='$i'>\n";
?>
</table>

<script>
//Coloca 
var ok_MO = false;
function TodosMO() {
	f = document.frm_os;
	if (!ok_MO) {
		for (i=0; i < <? echo $i; ?>; i++){
			myNF  = "mao_de_obra_0";
			myNFF = "mao_de_obra_" + i;
			f.elements[myNFF].value = f.elements[myNF].value;
		}
		ok_MO = true;
	}else{
		for (i=1; i < <? echo $i; ?>; i++){
			myNFF = "mao_de_obra_" + i;
			f.elements[myNFF].value = "";
		}
		ok_MO = false;
	}
}

var ok_RP = false;
function TodosRP() {
	f = document.frm_os;
	if (!ok_RP) {
		for (i=0; i < <? echo $i; ?>; i++){
			myNF  = "regulagem_peso_padrao_0";
			myNFF = "regulagem_peso_padrao_" + i;
			f.elements[myNFF].value = f.elements[myNF].value;
		}
		ok_RP = true;
	}else{
		for (i=1; i < <? echo $i; ?>; i++){
			myNFF = "regulagem_peso_padrao_" + i;
			f.elements[myNFF].value = "";
		}
		ok_RP = false;
	}
}

var ok_CC = false;
function TodosCC() {
	f = document.frm_os;
	if (!ok_CC) {
		for (i=0; i < <? echo $i; ?>; i++){
			myNF  = "certificado_conformidade_0";
			myNFF = "certificado_conformidade_" + i;
			f.elements[myNFF].value = f.elements[myNF].value;
		}
		ok_CC = true;
	}else{
		for (i=1; i < <? echo $i; ?>; i++){
			myNFF = "certificado_conformidade_" + i;
			f.elements[myNFF].value = "";
		}
		ok_CC = false;
	}
}
</script>

<br>

<TABLE width="700" border="1" cellspacing="0" cellpadding="0">
<tr>
	<td class='menu_top' colspan='3' align='center'><b>Sub Total</b></td>
	<td width="80" class='table_line2' style='text-align: right; padding-right:7;'><b><?echo number_format($total_produtos,2,',','.')?></b></td>
</tr>
</table>

<br>

<TABLE width="700" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="13" bgcolor="#d0d0d0"> * Valores em REAL * </TD>
</TR>
<TR class="menu_top" bgcolor="#d0d0d0">
	<TD class="menu_top" bgcolor="#d0d0d0" width = '15%'>Quantidade</TD>
	<TD class="menu_top" bgcolor="#d0d0d0">Descrição</TD>
	<TD class="menu_top" bgcolor="#d0d0d0">Valor</TD>
	<TD class="menu_top" bgcolor="#d0d0d0">Valor Total</TD>
</TR>

<?
if (strlen($os) > 0) {
	$sql = "SELECT * FROM tbl_os_visita WHERE os = $os ORDER BY os_visita";
	$vis = @pg_exec ($con,$sql);
}

if ($_POST['btn_acao'] == 'recalcular') {
	if (strlen ($_POST['qtde_hora']) > 0) {
		$qtde_hora = $_POST['qtde_hora'];
		if (strlen ($qtde_hora) < 5) $qtde_hora = "0" . $qtde_hora;

		$horas   = substr ($qtde_hora,0,2);
		$minutos = substr ($qtde_hora,3,2);
		$minutos = $minutos / 60 ;
		$qtde_hora = $horas + $minutos;
	}

	if (strlen ($_POST['desconto_peca']) > 0)            $desconto_peca            = $_POST['desconto_peca'];
	if (strlen ($_POST['desconto_peca_recuperada']) > 0) $desconto_peca_recuperada = $_POST['desconto_peca_recuperada'];
	if (strlen ($_POST['qtde_diaria']) > 0)              $qtde_diaria              = $_POST['qtde_diaria'];
	if (strlen ($_POST['valor_diaria']) > 0)             $valor_diaria             = $_POST['valor_diaria'];
	if (strlen ($_POST['hora_tecnica']) > 0)             $hora_tecnica             = $_POST['hora_tecnica'];
	if (strlen ($_POST['km_geral']) > 0)                 $km_geral                 = $_POST['km_geral'];
	if (strlen ($_POST['taxa_visita']) > 0)              $taxa_visita              = $_POST['taxa_visita'];
	if (strlen ($_POST['qtde_km']) > 0)                  $qtde_km                  = $_POST['qtde_km'];
	if (strlen ($_POST['visita_por_km']) > 0)            $visita_por_km            = $_POST['visita_por_km'];

}

$total_horas = intval($qtde_hora);
$minutos     = $qtde_hora - $total_horas ;
$minutos     = number_format(($minutos * 60),2);
$minutos     = intval($minutos);

$hora_geral  = str_pad ($total_horas , 2 , '0' , STR_PAD_LEFT);
$minutos     = str_pad ($minutos     , 2 , '0' , STR_PAD_LEFT);

$valor_total_horas = $qtde_hora * $hora_tecnica;

$qtde_hora = $hora_geral . ":" . $minutos ;

echo "<TR>\n";
echo "	<TD class='table_line'><input type='text' name='qtde_hora' value = '$qtde_hora'  size = '8'></TD>\n";
echo "	<TD class='table_line2'>Horas</TD>\n";
echo "	<TD class='table_line2'>total de <input type='text' name='hora_tecnica' value = '".number_format($hora_tecnica,2,',','.')."'  size = '8' class='AlignRight'> / hora &nbsp;</TD>\n";
echo "	<TD class='table_line3'><input disabled type='text' name='valor_total_horas' value = ".number_format($valor_total_horas,2,',','.')."  class='AlignRight'></TD>\n";
echo "</TR>\n";

$total_diaria = $qtde_diaria * $diaria;
echo "<TR>\n";
echo "	<TD class='table_line'><input type='text' name='qtde_diaria' value = '$qtde_diaria' size = '8'></TD>\n";
echo "	<TD class='table_line2'>Dias</TD>\n";
echo "	<TD class='table_line2'>total de <input type='text' name='diaria' value = '".number_format($diaria,2,',','.')."' size = '8' class='AlignRight'> / dia</TD>\n";
echo "	<TD class='table_line3'><input type='text' name='total_diaria' value = '" . number_format ($total_diaria,2,',','.') . "' disabled  class='AlignRight'></TD>\n";
echo "</TR>\n";

$total_por_deslocamento_km = $qtde_km * $visita_por_km;
echo "<TR>\n";
echo "	<TD class='table_line'><input type='text' name='qtde_km' value = '$qtde_km'  size = '8'></TD>\n";
echo "	<TD class='table_line2'>Km</TD>\n";
echo "	<TD class='table_line2'> total de <input type='text' name='visita_por_km' value = '".number_format($visita_por_km,2,',','.')."' size='8' class='AlignRight'> / km</TD>\n";
echo "	<TD class='table_line3'><input disabled type='text' name='total_por_deslocamento_km' value = '" . number_format ($total_por_deslocamento_km,2,',','.') . "' class='AlignRight'></TD>\n";
echo "</TR>\n";

echo "<TR>\n";
echo "	<TD class='table_line2'>&nbsp;</TD>\n";
echo "	<TD class='table_line2'>Taxa de visita</TD>\n";
echo "	<TD class='table_line2'>&nbsp;</TD>\n";
echo "	<TD class='table_line3'><input type='text' name='taxa_visita' value = '" . number_format ($taxa_visita,2,',','.') . "' class='AlignRight'></TD>\n";
echo "</TR>\n";

$sub_total_mobra = $valor_total_horas + $total_por_deslocamento_km + $total_diaria + $taxa_visita;

?>
</TABLE>

<br>

<TABLE width="700" border="1" cellspacing="0" cellpadding="0">
<tr>
	<td class='menu_top' colspan='3' align='center'><b>Sub Total</b></td>
	<td width="80" class='table_line2' style='text-align: right; padding-right:7;'><b><?echo number_format($sub_total_mobra,2,',','.')?></b></td>
</tr>
</table>

<br>
<TABLE width="700" border="1" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="7" bgcolor="#d0d0d0">Recuperação</TD>
</TR>
<TR>
	<TD class="menu_top" style="width: 030px;">ITEM</TD>
	<TD class="menu_top" style="width: 080px;">CÓDIGO</TD>
	<TD class="menu_top" style="width: 030px;">QTDE</TD>
	<TD class="menu_top">MATERIAL</TD>
	<TD class="menu_top">UNITÁRIO</TD>
	<TD class="menu_top">TOTAL</TD>
</TR>

<?
	if(strlen($os) > 0){

		$item    = 0;

		$sql = "SELECT	tbl_os_item.os_item                ,
						tbl_os_item.pedido                 ,
						tbl_os_item.qtde                   ,
						tbl_os_produto.os_produto          ,
						tbl_peca.referencia                ,
						tbl_peca.descricao                 ,
						tbl_tabela_item.preco AS preco_item
				FROM	tbl_os
				JOIN	tbl_os_produto ON tbl_os_produto.os       = tbl_os.os 
				JOIN	tbl_os_item    ON tbl_os_item.os_produto  = tbl_os_produto.os_produto 
				LEFT JOIN tbl_peca     ON tbl_peca.peca           = tbl_os_item.peca 
				JOIN	tbl_tabela     ON tbl_tabela.tabela       = 29
				LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca 
										  AND tbl_tabela_item.tabela = tbl_tabela.tabela 
				WHERE	tbl_os.os      = $os
				AND		tbl_os.fabrica = $login_fabrica
				AND tbl_os_item.servico_realizado = 36";
		$res = pg_exec ($con,$sql) ;

		if(pg_numrows($res) > 0) {

			$total_geral = 0;
			$j = 0;

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$os_item	= pg_result($res,$i,os_item);
				$os_produto = pg_result($res,$i,os_produto);
				$pedido		= pg_result($res,$i,pedido);
				$peca		= pg_result($res,$i,referencia);
				$qtde		= pg_result($res,$i,qtde);
				$preco		= pg_result($res,$i,preco_item);
				$descricao	= pg_result($res,$i,descricao);
				$total		= $qtde * $preco;

				$total_geral	= $total_geral + $total;

				for ($j=0; $j<count($array_os_produto); $j++){
					if ($array_os_produto[$j] == $os_produto) $item = $array_posicao[$j];
				}

				echo "<TR height='20'>\n";
				echo "	<TD class='table_line'>$item</TD>\n";
				echo "	<TD class='table_line'><input type='hidden' name='os_item_recuperada_$i' value='$os_item'>$peca</TD>\n";
				echo "	<TD class='table_line'>$qtde</TD>\n";
				echo "	<TD class='table_line2'>$descricao</TD>\n";
				echo "	<TD class='table_line3' style='padding-right:7;'>".number_format ($preco,2,',','.')."<input type='hidden' name='os_preco_recuperada_$i' value='$preco'></TD>\n";
				echo "	<TD class='table_line3' style='padding-right:7;'>".number_format ($total,2,',','.')."</TD>\n";
				echo "</TR>\n";
			}

			if (strlen($desconto_peca_recuperada) > 0 AND strlen($total_geral) > 0)
				$total_geral = $total_geral - ($total_geral * ($desconto_peca_recuperada / 100));

			echo "<TR height='20'>\n";
			echo "	<TD colspan='5' class='table_line3' style='padding-right:7;'>Desconto: <INPUT type='text' name='desconto_peca_recuperada' value='$desconto_peca_recuperada' size='2' maxlength='2'> %</TD>\n";
			echo "	<TD class='table_line3' style='padding-right:7;'><b>".number_format ($total_geral,2,',','.')."</b>";
			echo "		<input type='hidden' name='total_pecas_recuperadas' value='$total_geral'>";
			echo "		<input type='hidden' name='qtde_recuperada' value='$i'>";
			echo "	</TD>\n";
			echo "</TR>\n";

		}

	}

	$total_servicos += $total_geral;

?>

</TABLE>

<br>

<TABLE width="700" border="1" cellspacing="0" cellpadding="0">
<tr>
	<td class='menu_top' colspan='3' align='center'><b>Total Serviços</b></td>
	<td width="80" class='table_line2' style='text-align: right; padding-right:7;'><b><?echo number_format($total_servicos,2,',','.')?></b></td>
</tr>
</table>

<br>

<TABLE width="700" border="1" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="8" bgcolor="#d0d0d0">Peças</TD>
</TR>
<TR>
	<TD class="menu_top" style="width: 030px;">ITEM</TD>
	<TD class="menu_top" style="width: 080px;">CÓDIGO</TD>
	<TD class="menu_top" style="width: 030px;">QTDE</TD>
	<TD class="menu_top">MATERIAL</TD>
	<TD class="menu_top">IPI</TD>
	<TD class="menu_top">C</TD>
	<TD class="menu_top">UNITÁRIO</TD>
	<TD class="menu_top">TOTAL</TD>
</TR>

<?
	if(strlen($os) > 0){

		$item    = 0;

		$total_geral = 0;

		$sql = "SELECT  distinct
						tbl_os_item.os_item                ,
						tbl_os_item.pedido                 ,
						tbl_os_item.qtde                   ,
						tbl_os_produto.os_produto          ,
						tbl_peca.referencia                ,
						tbl_peca.descricao                 ,
						tbl_peca.origem                    ,
						tbl_peca.unidade                   ,
						tbl_peca.ipi                       ,
						tbl_peca.peso                      ,
						tbl_tabela_item.preco AS preco_item
				FROM    tbl_os_item
				LEFT JOIN tbl_peca USING (peca)
				LEFT JOIN tbl_tabela ON tbl_tabela.tabela = 26
				LEFT JOIN tbl_tabela_item USING (peca)
				left JOIN tbl_os_produto USING(os_produto)
				left JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
				WHERE   tbl_os.os      = $os
				AND     tbl_os.fabrica = $login_fabrica
				AND     tbl_os_item.servico_realizado NOT IN (12, 36) ";
		$res = pg_exec ($con,$sql) ;

		if(pg_numrows($res) > 0) {

			$total = 0;

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$os_item	= pg_result($res,$i,os_item);
				$os_produto = pg_result($res,$i,os_produto);
				$pedido		= pg_result($res,$i,pedido);
				$peca		= pg_result($res,$i,referencia);
				$qtde		= pg_result($res,$i,qtde);
				$preco		= pg_result($res,$i,preco_item);
				$descricao	= pg_result($res,$i,descricao);
				$origem		= pg_result($res,$i,origem);
				$unidade	= pg_result($res,$i,unidade);
				$ipi		= pg_result($res,$i,ipi);
				$peso		= pg_result($res,$i,peso);

				$preco_sem_ipi = $qtde * $preco;
				if ($consumidor_final <> 'f') {
					$preco = $preco + ($preco_sem_ipi * $ipi / 100);
				}
				$total = $preco;

				$valor_total = $qtde * $preco;
				$total_geral = $total_geral + $total;

				if ($origem == "TER") {
					$origem = "C";
				}else{
					$origem = "T";
				}

				for ($j=0; $j<count($array_os_produto); $j++){
					if ($array_os_produto[$j] == $os_produto) $item = $array_posicao[$j];
				}

				echo "<TR height='20'>\n";
				echo "	<TD class='table_line'>$item</TD>\n";
				echo "	<TD class='table_line'><input type='hidden' name='os_item_peca_$i' value='$os_item'>$peca</TD>\n";
				echo "	<TD class='table_line'>$qtde</TD>\n";
				echo "	<TD class='table_line2'>$descricao</TD>\n";
				echo "	<TD class='table_line3' style='padding-right:7;'>";
				if ($consumidor_final <> 'f') {
					echo "	$ipi %<input type='hidden' name='os_ipi_peca_$i' value='$ipi'>";
				}
				echo "</TD>\n";
				echo "	<TD class='table_line2'>$origem</TD>\n";
				echo "	<TD class='table_line3' style='padding-right:7;'>".number_format ($preco_sem_ipi,2,',','.')."<input type='hidden' name='os_preco_peca_$i' value='$preco_sem_ipi'></TD>\n";
				echo "	<TD class='table_line3' style='padding-right:7;'>".number_format ($total,2,',','.')."</TD>\n";
				echo "</TR>\n";
			}

			if (strlen($desconto_peca) > 0 AND strlen($total_geral) > 0) 
				$total_geral = $total_geral - ($total_geral * ($desconto_peca / 100));
				$total_pecas += $total_geral;
			echo "<TR height='20'>\n";
			echo "	<TD colspan='7' class='table_line3' style='padding-right:7;'>Desconto: <INPUT type='text' name='desconto_peca' value='$desconto_peca' size='2' maxlength='2'> %</TD>\n";
			echo "	<TD class='table_line3' style='padding-right:7;'><b>".number_format ($total_geral,2,',','.')."</b>";
			echo "		<input type='hidden' name='total_pecas_novas' value='$total_geral'>";
			echo "		<input type='hidden' name='qtde_peca' value='$i'>";
			echo "	</TD>\n";
			echo "</TR>\n";

		}

	}

	$total_os = $sub_total_mobra + $total_servicos + $total_pecas + $total_produtos;

?>

</TABLE>

<br>

<TABLE width="700" border="1" cellspacing="0" cellpadding="0">
<tr>
	<td class='menu_top' colspan='3' align='center'><b>Peças novas</b></td>
	<td width="80" class='table_line2' style='text-align: right; padding-right:7;'><b><?echo number_format($total_pecas,2,',','.')?></b></td>
</tr>
</table>

<br>

<TABLE width="700" border="1" cellspacing="0" cellpadding="0">
<tr>
	<td class='menu_top' colspan='3' align='center'><b>Total geral da OS</b></td>
	<td width="80" class='table_line2' style='text-align: right; padding-right:7;'><b><?echo number_format($total_os,2,',','.')?></b></td>
</tr>
</table>

<p>
<input type="hidden" name="btn_acao" value="">
<img src='imagens/btn_recalcular.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='recalcular' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Recalcular valores da OS" border='0' style='cursor: pointer'>
&nbsp;&nbsp;&nbsp;&nbsp;
<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar dados da OS" border='0' style='cursor: pointer'>

</form>

<p>

<? 
include 'rodape.php';
?>