<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="auditoria,call_center ";
include 'autentica_admin.php';

$msg_erro = "";
$msg_debug = "";
 

//  Para testes da tela de pesquisa
if (preg_match('/posto_login(.*).php/', $PHP_SELF, $a_suffix)) {
	$suffix = $a_suffix[1];
	if (!file_exists("posto_pesquisa$suffix.php")) unset($suffix);
}

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if (substr($btn_acao,0,5) == "Logar" ) {
	$posto_codigo = $_POST['posto_codigo'];

	$sql = "SELECT codigo_posto, senha FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$posto_codigo'";

	$res = pg_query($con,$sql);

	$senha = pg_fetch_result($res, 0, 'senha') ;
	$posto_codigo = pg_fetch_result($res, 0, 'codigo_posto') ;
//	echo "<form name='frm_login' method='post' target='_blank' action='../index.php'>";
//hd 8433 takashi 11/12/07  ?>
<html>
<head></head>
<body>
<form name='frm_login' method='post' target='_blank' action='../index.php?ajax=sim&acao=validar&redir=sim'>
	<input type='hidden' name='login' value='<?=$posto_codigo?>'>
	<input type='hidden' name='senha' value='<?=$senha?>'>
	<input type='hidden' name='btnAcao' value='Enviar'>
</form>
</body>

<script language='javascript'>
document.frm_login.submit();
window.setTimeout("document.location = '<?=$PHP_SELF?>'", 3000);
// document.location = '<?=$PHP_SELF?>';
</script>
</html>
<?
	exit ;

}

$visual_black = "auditoria-admin";

$title       = "Logar como Servicio";
$cabecalho   = "Logar como Servicio";
$layout_menu = "auditoria";
include 'cabecalho.php';
?>
<script language="JavaScript">
	function fnc_pesquisa_posto (campo, campo2, tipo) {
		if (tipo == "codigo" ) {
			var xcampo = campo;
		}

		if (tipo == "nome" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {
			var url = "";
			url = "posto_pesquisa<?=$suffix?>.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
			janela.retorno = "<? echo $PHP_SELF ?>";
			janela.posto_codigo	= campo;
			janela.porto_nome	= campo2;
			janela.focus();
		}
	}
</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

</style>

<? 
	if($msg_erro){
?>
<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
	<tr align='center'>
		<td class='error'>
			<? echo $msg_erro; ?>
		</td>
	</tr>
</table>
<?	} 
//echo $msg_debug;
?> 
<p>

<?
if (strlen($_GET['posto']) > 0)  $posto = trim($_GET['posto']);
if (strlen($_POST['posto']) > 0) $posto = trim($_POST['posto']);

if (strlen ($posto) > 0) {
	$sql = "SELECT tbl_posto.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
			FROM tbl_posto 
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_posto.posto = $posto";
	$res = pg_query($con,$sql);
	$posto_codigo = pg_fetch_result($res, 0, 'codigo_posto');
	$posto_nome	  = pg_fetch_result($res, 0, 'nome');
}
?>


<table width='600' align='center' border='0' bgcolor='#d9e2ef'>
<tr>
	<td align='center'>
		<font face='arial, verdana' color='#596d9b' size='-1'>
		Teclee el código del Servicio Autorizado o haga un clic en la lupa para consultar.
		</font>
	</td>
</tr>
</table>

<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>">
	<table width="600" border="0" cellspacing="5" cellpadding="0" align='center'>
	<tr>
		<td nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código del Servicio</font>
			<br>
			<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto (document.frm_posto.posto_codigo,document.frm_posto.posto_nome,'codigo')"></A>
		</td>

		<td nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nombre del Servicio</font>
			<br>
			<input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.posto_codigo,document.frm_posto.posto_nome,'nome')" style="cursor:pointer;"></A>
		</td>

	</tr>
	</table>
	<br>
	<center>
		<input type='submit' name='btn_acao' value='Logar como este Servicio'>
	</center>
</form>
<br>
<br>
<?
#-------------------- Pesquisa Posto -----------------
if (strlen($posto) > 0 and strlen ($msg_erro) == 0 ) {
	$sql = "SELECT  tbl_posto_fabrica.posto               ,
					tbl_posto_fabrica.credenciamento      ,
					tbl_posto_fabrica.codigo_posto        ,
					tbl_posto_fabrica.tipo_posto          ,
					tbl_posto_fabrica.transportadora_nome ,
					tbl_posto_fabrica.transportadora      ,
					tbl_posto_fabrica.cobranca_endereco   ,
					tbl_posto_fabrica.cobranca_numero     ,
					tbl_posto_fabrica.cobranca_complemento,
					tbl_posto_fabrica.cobranca_bairro     ,
					tbl_posto_fabrica.cobranca_cep        ,
					tbl_posto_fabrica.cobranca_cidade     ,
					tbl_posto_fabrica.cobranca_estado     ,
					tbl_posto_fabrica.obs                 ,
					tbl_posto_fabrica.banco               ,
					tbl_posto_fabrica.agencia             ,
					tbl_posto_fabrica.conta               ,
					tbl_posto_fabrica.nomebanco           ,
					tbl_posto_fabrica.favorecido_conta    ,
					tbl_posto_fabrica.cpf_conta           ,
					tbl_posto_fabrica.tipo_conta          ,
					tbl_posto_fabrica.obs_conta           ,
					tbl_posto.nome                        ,
					tbl_posto.cnpj                        ,
					tbl_posto.ie                          ,
					tbl_posto.endereco                    ,
					tbl_posto.numero                      ,
					tbl_posto.complemento                 ,
					tbl_posto.bairro                      ,
					tbl_posto.cep                         ,
					tbl_posto.cidade                      ,
					tbl_posto.estado                      ,
					tbl_posto.email                       ,
					tbl_posto.fone                        ,
					tbl_posto.fax                         ,
					tbl_posto.suframa                     ,
					tbl_posto.contato                     ,
					tbl_posto.capital_interior            ,
					tbl_posto.nome_fantasia				  ,
					tbl_posto_fabrica.item_aparencia      ,
					tbl_posto_fabrica.senha               ,
					tbl_posto_fabrica.desconto            ,
					tbl_posto_fabrica.pedido_em_garantia  ,
					tbl_posto_fabrica.pedido_faturado     ,
					tbl_posto_fabrica.digita_os           ,
					tbl_posto_fabrica.prestacao_servico   ,
					tbl_posto_fabrica.pedido_via_distribuidor     ,
					tbl_transportadora.nome as transportadora_nome    ,
					tbl_tipo_posto.descricao as tipo_posto_descricao
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora  = tbl_posto_fabrica.transportadora
			LEFT JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto_fabrica.posto   = $posto ";
//if ($ip == '192.168.0.66') echo $sql;
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$posto            = trim(pg_fetch_result($res, 0, 'posto'));
		$credenciamento   = trim(pg_fetch_result($res, 0, 'credenciamento'));
		$codigo           = trim(pg_fetch_result($res, 0, 'codigo_posto'));
		$nome             = trim(pg_fetch_result($res, 0, 'nome'));
		$cnpj             = trim(pg_fetch_result($res, 0, 'cnpj'));
		$ie               = trim(pg_fetch_result($res, 0, 'ie'));
		$endereco         = trim(pg_fetch_result($res, 0, 'endereco'));
		$numero           = trim(pg_fetch_result($res, 0, 'numero'));
		$complemento      = trim(pg_fetch_result($res, 0, 'complemento'));
		$bairro           = trim(pg_fetch_result($res, 0, 'bairro'));
		$cep              = trim(pg_fetch_result($res, 0, 'cep'));
		$cidade           = trim(pg_fetch_result($res, 0, 'cidade'));
		$estado           = trim(pg_fetch_result($res, 0, 'estado'));
		$email            = trim(pg_fetch_result($res, 0, 'email'));
		$fone             = trim(pg_fetch_result($res, 0, 'fone'));
		$fax              = trim(pg_fetch_result($res, 0, 'fax'));
		$contato          = trim(pg_fetch_result($res, 0, 'contato'));
		$suframa          = trim(pg_fetch_result($res, 0, 'suframa'));
		$item_aparencia   = trim(pg_fetch_result($res, 0, 'item_aparencia'));
		$obs              = trim(pg_fetch_result($res, 0, 'obs'));
		$capital_interior = trim(pg_fetch_result($res, 0, 'capital_interior'));
		$tipo_posto       = trim(pg_fetch_result($res, 0, 'tipo_posto'));
		$senha            = trim(pg_fetch_result($res, 0, 'senha'));
		$desconto         = trim(pg_fetch_result($res, 0, 'desconto'));
		$nome_fantasia    = trim(pg_fetch_result($res, 0, 'nome_fantasia'));
		$transportadora   = trim(pg_fetch_result($res, 0, 'transportadora'));

		$cobranca_endereco       = trim(pg_fetch_result($res, 0, 'cobranca_endereco'));
		$cobranca_numero         = trim(pg_fetch_result($res, 0, 'cobranca_numero'));
		$cobranca_complemento    = trim(pg_fetch_result($res, 0, 'cobranca_complemento'));
		$cobranca_bairro         = trim(pg_fetch_result($res, 0, 'cobranca_bairro'));
		$cobranca_cep            = trim(pg_fetch_result($res, 0, 'cobranca_cep'));
		$cobranca_cidade         = trim(pg_fetch_result($res, 0, 'cobranca_cidade'));
		$cobranca_estado         = trim(pg_fetch_result($res, 0, 'cobranca_estado'));
		$pedido_em_garantia      = trim(pg_fetch_result($res, 0, 'pedido_em_garantia'));
		$pedido_faturado         = trim(pg_fetch_result($res, 0, 'pedido_faturado'));
		$digita_os               = trim(pg_fetch_result($res, 0, 'digita_os'));
		$prestacao_servico       = trim(pg_fetch_result($res, 0, 'prestacao_servico'));
		$banco                   = trim(pg_fetch_result($res, 0, 'banco'));
		$agencia                 = trim(pg_fetch_result($res, 0, 'agencia'));
		$conta                   = trim(pg_fetch_result($res, 0, 'conta'));
		$nomebanco               = trim(pg_fetch_result($res, 0, 'nomebanco'));
		$favorecido_conta        = trim(pg_fetch_result($res, 0, 'favorecido_conta'));
		$cpf_conta               = trim(pg_fetch_result($res, 0, 'cpf_conta'));
		$tipo_conta              = trim(pg_fetch_result($res, 0, 'tipo_conta'));
		$obs_conta               = trim(pg_fetch_result($res, 0, 'obs_conta'));
		$pedido_via_distribuidor = trim(pg_fetch_result($res, 0, 'pedido_via_distribuidor'));

		$transportadora_nome = trim(pg_fetch_result($res, 0, 'transportadora_nome'));
		$tipo_posto_descricao = trim(pg_fetch_result($res, 0, 'tipo_posto_descricao'));

		// Formatando dados
		$id_mask = (strlen($cnpj) == 11) ? '/(\d{3})(\d{3})(\d{3})(\d{2})/' : '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/'; // Máscara para CPF ou CNPJ
		$cnpj = (strlen($cnpj) == 11) ? preg_replace($id_mask, '$1.$2.$3-$4', $cnpj) : preg_replace($id_mask, '$1.$2.$3/$4-$5', $cnpj);
		$endereco         = str_replace("\"","",$endereco);
		$cep = preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cep);
	}
?>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="5" class="menu_top">
			<font color='#36425C'>DATOS DEL SERVICIO AUTORIZADO</font>
		</td>
	</tr>
	<tr class="menu_top">
		<td>ID 1</td>
		<td>ID 2</td>
		<td>TELÉFONO</td>
		<td>FAX</td>
		<td>CONTACTO</td>
	</tr>
	<tr class="table_line">
		<td><? echo $cnpj ?>&nbsp;</td>
		<td><? echo $ie ?></td>
		<td><? echo $fone ?></td>
		<td><? echo $fax ?></td>
		<td><? echo $contato ?></td>
	</tr>
	<tr class="menu_top">
		<td colspan="2">CÓDIGO</td>
		<td colspan="5">NOMBRE DEL SERVICIO</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><? echo $codigo ?>&nbsp;</td>
		<td colspan="3"><? echo $nome ?></td>
	</tr>
</table>
<br>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td colspan="2">DIRECCIÓN</td>
		<td>NÚMERO</td>
		<td colspan="2">COMPLEMENTO</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><? echo $endereco ?>&nbsp;</td>
		<td><? echo $numero ?></td>
		<td colspan="2"><? echo $complemento ?></td>
	</tr>
	<tr class="menu_top">
		<td colspan="2">BARRIO</td>
		<td>APARTADO POSTAL</td>
		<td>CIUDAD</td>
		<td>PROVINCIA/ESTADO</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><? echo $bairro ?>&nbsp;</td>
		<td><? echo $cep ?></td>
		<td><? echo $cidade ?></td>
		<td><? echo $estado ?></td>
	</tr>
</table>
<br>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td>E-MAIL</td>
		<td>CAPITAL/PROVINCIA</td>
		<td>TIPO DE SERVICIO</td>
		<!-- <td>PEDIDO EM GARANTIA</td> -->
		<td>DESCUENTO</td>
	</tr>
	<tr class="table_line">
		<td>
			<? echo $email ?>
		</td>
		<td>
			<? if ($capital_interior == 'CAPITAL') echo 'Capital';
				 if ($capital_interior == 'INTERIOR') echo 'Interior'; ?>
		</td>
		<td>
			<? echo $tipo_posto_descricao; ?>
		</td>
<!-- 
		<td>
			<select name='pedido_em_garantia' size='1'>
				<option value=''></option>
				<option value='t' <? if ($pedido_em_garantia == "t") echo " selected "; ?> >Sim</option>
				<option value='f' <? if ($pedido_em_garantia == "f") echo " selected "; ?> >Não</option>
			</select>
		</td>
 -->
		<td><? echo $desconto ?> %</td>
	</tr>
</table>
<br>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td colspan="2">NOMBRE COMERCIAL DEL SERVICIO</td>
		<td>ÍTEM APARENCIA</td>
	</tr>
	<tr class="table_line">
		<td colspan="2">
			<? echo $nome_fantasia ?>&nbsp;
		</td>
		<td>
			<?echo ($item_aparencia == 't') ? "SÍ" : "NO";?>
		</td>
	</tr>
	<tr class="menu_top">
		<td colspan="5">Observaciones</td>
	</tr>
	<tr class="table_line">
		<td colspan="5">
			<? echo $obs ?>&nbsp;
		</td>
	</tr>
</table>
<br>
<?
$sql = "SELECT  tbl_linha.nome AS linha_nome ,
				tbl_tabela.sigla_tabela ,
				tbl_posto.nome_fantasia
		FROM    tbl_posto_linha
		JOIN    tbl_linha ON tbl_posto_linha.linha = tbl_linha.linha 
		LEFT JOIN tbl_posto ON tbl_posto_linha.distribuidor = tbl_posto.posto
		LEFT JOIN tbl_tabela ON tbl_posto_linha.tabela = tbl_tabela.tabela
		WHERE tbl_posto_linha.posto = $posto
		AND   tbl_linha.fabrica = $login_fabrica";
$res = pg_query($con,$sql);

echo "<table width='500' align='center' border='1'>";
echo "<tr class='menu_top'>";
echo "<td>Línea Atendida</td>";
echo "<td>Tabla</td>";
echo "<td>Distribuidor</td>";
echo "</tr>";

for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
	echo "<tr class='table_line'>";
	echo "<td>" . pg_fetch_result($res, $i, 'linha_nome'). "</td>";
	echo "<td>" . pg_fetch_result($res, $i, 'sigla_tabela'). "</td>";
	if (strlen (pg_fetch_result($res, $i, 'nome_fantasia')) > 0) {
		echo "<td>" . pg_fetch_result($res, $i, 'nome_fantasia'). "</td>";
	}else{
		echo "<td><b>Fábrica</b></td>";
	}
	echo "</tr>";
}

echo "</table>";


} 

?>

<p>

<? include "rodape.php"; ?>
