<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center,gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = "";

$btn_acao = trim (strtolower ($_POST['btn_acao']));

if (strlen($_GET['os_manutencao']) > 0)  $os_manutencao = trim($_GET['os_manutencao']);
if (strlen($_POST['os_manutencao']) > 0) $os_manutencao = trim($_POST['os_manutencao']);

# executa funcao de explosao
if ($btn_acao == "explodir"){
	//HD 19570
	$sql = "UPDATE tbl_os_revenda SET 
						admin = $login_admin
			WHERE os_revenda = $os_manutencao
			AND   fabrica    = $login_fabrica";
	$res = @pg_exec ($con,$sql);
	$sql = "SELECT fn_explode_os_revenda($os_manutencao,$login_fabrica)";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if( strpos($msg_erro,'data_nf_superior_data_abertura') ) {
		$msg_erro="A data de nota fiscal não pode ser maior que a data de abertura. Por favor, clique em botão Alterar para fazer a correção.";
	}

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT  sua_os, posto
				FROM	tbl_os_revenda
				WHERE	os_revenda = $os_manutencao
				AND		fabrica = $login_fabrica";
		$res = pg_exec($con, $sql);
		$os_numero = @pg_result($res,0,sua_os);
		$posto     = @pg_result($res,0,posto);

		header("Location: os_manutencao_explodida.php?os_numero=$os_numero&posto=$posto");
		exit;
	}
}

if(strlen($os_manutencao) > 0){
	$sql = "SELECT  tbl_os_revenda.os_revenda,
					TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura,
					TO_CHAR(tbl_os_revenda.digitacao,'DD/MM/YYYY')     AS data_digitacao,
					tbl_os_revenda.posto,
					tbl_os_revenda.consumidor_nome,
					tbl_os_revenda.consumidor_cnpj,
					tbl_os_revenda.consumidor_cidade,
					tbl_os_revenda.consumidor_estado,
					tbl_os_revenda.qtde_km,
					tbl_os_revenda.quem_abriu_chamado,
					tbl_os_revenda.taxa_visita,
					tbl_os_revenda.hora_tecnica,
					tbl_os_revenda.cobrar_percurso,
					tbl_os_revenda.visita_por_km,
					tbl_os_revenda.diaria,
					tbl_os_revenda.regulagem_peso_padrao,
					tbl_os_revenda.obs,
					tbl_os_revenda.contrato,
					tbl_cliente.cliente,
					tbl_cliente.nome        AS cliente_nome,
					tbl_cliente.cpf         AS cliente_cpf,
					tbl_cidade.nome         AS cliente_cidade,
					tbl_cidade.estado       AS cliente_estado,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.posto,
					tbl_posto.nome,
					tbl_posto.cnpj,
					tbl_posto_fabrica.contato_cidade AS posto_cidade,
					tbl_posto_fabrica.contato_estado AS posto_estado
			FROM	tbl_os_revenda
			JOIN	tbl_posto USING(posto)
			JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_cliente ON tbl_cliente.cliente = tbl_os_revenda.cliente
			LEFT JOIN tbl_cidade  ON tbl_cidade.cidade   = tbl_cliente.cidade
			WHERE	tbl_os_revenda.fabrica    = $login_fabrica
			AND		tbl_os_revenda.os_revenda = $os_manutencao";
#	echo nl2br($sql);
	$res = pg_exec($con, $sql);
	if (pg_numrows($res) > 0) {
		$os_manutencao       = trim(pg_result($res,0,os_revenda));
		$data_abertura       = trim(pg_result($res,0,data_abertura));
		$data_digitacao      = trim(pg_result($res,0,data_digitacao));
		$posto               = trim(pg_result($res,0,posto));
		$consumidor_nome     = trim(pg_result($res,0,consumidor_nome));
		$qtde_km             = trim(pg_result($res,0,qtde_km));
		$quem_abriu_chamado  = trim(pg_result($res,0,quem_abriu_chamado));
		$taxa_visita         = trim(pg_result($res,0,taxa_visita));
		$hora_tecnica        = trim(pg_result($res,0,hora_tecnica));
		$cobrar_percurso     = trim(pg_result($res,0,cobrar_percurso));
		$visita_por_km       = trim(pg_result($res,0,visita_por_km));
		$diaria              = trim(pg_result($res,0,diaria));
		$regulagem_peso_padrao= trim(pg_result($res,0,regulagem_peso_padrao));
		$obs                 = trim(pg_result($res,0,obs));
		$contrato            = trim(pg_result($res,0,contrato));
		$cliente             = trim(pg_result($res,0,cliente));
		$cliente_nome        = trim(pg_result($res,0,cliente_nome));
		$cliente_cnpj        = trim(pg_result($res,0,cliente_cpf));
		$cliente_cidade      = trim(pg_result($res,0,cliente_cidade));
		$cliente_estado      = trim(pg_result($res,0,cliente_estado));
		$posto               = trim(pg_result($res,0,posto));
		$posto_codigo        = trim(pg_result($res,0,codigo_posto));
		$posto_nome          = trim(pg_result($res,0,nome));
		$posto_cidade        = trim(pg_result($res,0,posto_cidade));
		$posto_estado        = trim(pg_result($res,0,posto_estado));
		$diaria                = number_format($diaria,2,",",".");
		$taxa_visita           = number_format($taxa_visita,2,",",".");
		$visita_por_km         = number_format($visita_por_km,2,",",".");
		$hora_tecnica          = number_format($hora_tecnica,2,",",".");
		$regulagem_peso_padrao = number_format($regulagem_peso_padrao,2,",",".");
		$consumidor_cnpj       = trim(pg_result($res,0,consumidor_cnpj));
		$consumidor_cidade     = trim(pg_result($res,0,consumidor_cidade));
		$consumidor_estado     = trim(pg_result($res,0,consumidor_estado));

		// HD  92046
		if(strlen($consumidor_nome) > 0)      $cliente_nome        = $consumidor_nome       ;
		if(strlen($consumidor_cnpj) > 0)      $cliente_cnpj        = $consumidor_cnpj       ;
		if(strlen($consumidor_cidade)>0)      $cliente_cidade      = $consumidor_cidade      ;
		if(strlen($consumidor_estado)>0)      $cliente_estado      = $consumidor_estado      ;
	}else{
		header('Location: os_manutencao.php');
		exit;
	}
}

$title			= "Cadastro de Ordem de Serviço - Manutenção"; 
$layout_menu	= "callcenter";

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

.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #CED7e7;
}

</style>

<? 
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" color="#FF3333">
		<? echo $msg_erro ?>
		</font></b>
	</td>
</tr>
</table>
<?
}
?>

<br>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<input type='hidden' name='os_manutencao' value='<? echo $os_manutencao; ?>'>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr>
		<td valign="top" align="left">

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Cliente</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">CPF/CNPJ Cliente</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">KM Cliente</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Contrato</font>
					</td>
				</tr>

				<tr>
					<td align='center'><?=$cliente_nome?></td>
					<td align='center'><?=$cliente_cnpj?>
					</td>
					<td align='center'><?=$qtde_km?></td>
					<td align='center'><?=($contrato=='t')?"SIM":"NÃO";?><?=$numero_contrato?></td>
				</tr>
			</table>


			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código do posto</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do posto</font>
					</td>
				</tr>
				<tr>
					<td align='center'><?=$posto_codigo?></td>
					<td align='center'><?=$posto_nome?></td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Chamado aberto por</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Taxa de visita</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Hora técnica</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Mão-de-obra</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Regulagem</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Valor/km</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Valor diária</font>
					</td>
				</tr>
				<tr>
					<td align='center'><?=$quem_abriu_chamado?></td>
					<td align='center'><?=$taxa_visita?></td>
					<td align='center'><?=$hora_tecnica?></td>
					<td align='center'><?=$mao_de_obra?></td>
					<td align='center'><?=$regulagem_peso_padrao?></td>
					<td align='center'><?=$visita_por_km?></td>
					<td align='center'><?=$diaria?></select>
					</td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
					</td>
				</tr>
				<tr>
					<td align='center'><? echo $obs ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>


<table width="550" border="0" cellpadding="2" cellspacing="3" align="center" bgcolor="#ffffff">
	<TR>
		<TD colspan="4"><br></TD>
	</TR>
	<tr class="menu_top">
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Produto</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Descrição do produto</font></td>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número de série</font></td>
		<td align="center"><font size="1" face="Geneva, Arial, Helvetica, san-serif">Cap.</font></td>
		<!--<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Peso Padrão </font></td>
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Cert. Conf</font></td>-->
		<td align="center" nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Reclamado</font></td>
	</tr>
<?
if ($os_manutencao){
	// seleciona do banco de dados
	$sql = "SELECT  tbl_os_revenda_item.os_revenda_item            ,
					tbl_os_revenda_item.produto                    ,
					tbl_os_revenda_item.serie                      ,
					tbl_os_revenda_item.capacidade                 ,
					tbl_os_revenda_item.regulagem_peso_padrao      ,
					tbl_os_revenda_item.certificado_conformidade   ,
					tbl_os_revenda_item.defeito_reclamado          ,
					tbl_os_revenda_item.defeito_reclamado_descricao,
					tbl_produto.referencia                         ,
					tbl_produto.descricao
			FROM	tbl_os_revenda
			JOIN	tbl_os_revenda_item USING(os_revenda)
			JOIN	tbl_produto         USING (produto)
			LEFT JOIN	tbl_defeito_reclamado USING(defeito_reclamado)
			WHERE	tbl_os_revenda.fabrica         = $login_fabrica
			AND		tbl_os_revenda_item.os_revenda = $os_manutencao 
			ORDER BY tbl_os_revenda_item.os_revenda_item ASC ";
	$res = pg_exec($con, $sql);
	$qtde_item_os = pg_numrows($res);

	for ($i=0; $i<$qtde_item_os; $i++) {
		$os_revenda_item             = pg_result($res,$i,os_revenda_item);
		$produto_referencia          = pg_result($res,$i,referencia);
		$produto_descricao           = pg_result($res,$i,descricao);
		$produto_serie               = pg_result($res,$i,serie);
		$produto_capacidade          = pg_result($res,$i,capacidade);
		$regulagem_peso_padrao       = pg_result($res,$i,regulagem_peso_padrao);
		$certificado_conformidade    = pg_result($res,$i,certificado_conformidade);
		$defeito_reclamado           = pg_result($res,$i,defeito_reclamado);
		$defeito_reclamado_descricao = pg_result($res,$i,defeito_reclamado_descricao);
	?>
		<tr>
			<td align="center">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_referencia ?></font>
			</td>
			<td align="left" nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"> <? echo $produto_descricao; ?></font>
			</td>
			<td align="center">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_serie ?></font>
			</td>
			<td align="center" nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $produto_capacidade ?></font>
			</td>
			<!--<td align="center">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $regulagem_peso_padrao ?></font>
			</td>
			<td align="center">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $certificado_conformidade ?></font>
			</td>-->
			<td align="center">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $defeito_reclamado_descricao ?></font>
			</td>
		</tr>
<?	}
}
?>
</table>

<br>

<input type='hidden' name='btn_acao' value=''>

<center>
<img src='imagens/btn_alterarcinza.gif'  onclick="javascript: document.location='os_manutencao.php?os_manutencao=<? echo $os_manutencao; ?>'" ALT="Alterar" border='0' style="cursor:pointer;">
<img src='imagens/btn_explodir.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='explodir' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Explodir" border='0' style="cursor:pointer;">
<img src='imagens/btn_imprimir.gif' onclick="javascript: window.open('os_print_manutencao.php?os_manutencao=<? echo $os_manutencao; ?>','osmanutencao');" ALT="Imprimir" border='0' style="cursor:pointer;">
</center>

<br>

<center><a href="javascript:go.history(-1)"><img src="imagens/btn_voltar.gif"></a></center>

</form>
<br>

<? include 'rodape.php'; ?>