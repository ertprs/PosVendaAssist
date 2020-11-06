<?php
/**
 *
 * @since HD 770760 - alteradas algumas coisas nesta tela.
 *
 * Se acessa a tela diretamente (através do Menu Gerência > Transferência de OS)
 * precisa colocar as informações da OS e Posto e pesquisar.
 * Quando acessada através da os_consulta_lite.php as informações da OS
 * já são exibidas.
 * Do que jeito que está hoje (2011-11-25) precisa pesquisar o posto de destino
 * com a lupa, clicar em pesquisar para aparecer as informações do posto e só
 * então clicar em "Alterar" para transferir a OS.
 * Alterei para não precisar pesquisar as informações do posto de destino.
 *
 * Francisco Ambrozio - Fri Nov 25 15:51:13 BRST 2011
 *
 */

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";

$admin_privilegios = "call_center";
include_once "autentica_admin.php";

include_once "funcoes.php";

$erro     = '';
$ok       = '';
$btn_acao = '';
$info_os  = '';

if (!empty($_GET['ok'])) {
	$ok = $_GET['ok'];
}

if (!empty($_POST['btn_acao'])) {
	$btn_acao = $_POST['btn_acao'];
}

if (!empty($_POST['sua_os'])) {
	$sua_os = $_POST['sua_os'];
}
elseif (!empty($_GET['sua_os'])){
	$sua_os = $_GET['sua_os'];
}

if (!empty($_POST['posto_codigo_origem'])) {
	$posto_codigo_origem = $_POST['posto_codigo_origem'];
}
elseif (!empty($_GET['posto_codigo_origem'])) {
	$posto_codigo_origem = $_GET['posto_codigo_origem'];
}

if (!empty($_POST['posto_nome_origem'])) {
	$posto_nome_origem = $_POST['posto_nome_origem'];
}
elseif (!empty($_GET['posto_nome_origem'])) {
	$posto_nome_origem = $_GET['posto_nome_origem'];
}

if (isset($sua_os)) {
	$sua_os = explode("-", $sua_os);
	$sua_os = $sua_os[0];

	if (!empty($posto_codigo_origem)) {
		$posto_codigo_origem = trim($posto_codigo_origem);
		$condPostoCodigo = "AND trim(tbl_posto_fabrica.codigo_posto) = '$posto_codigo_origem'";
	} else {
		$condPostoCodigo = '';
	}

	$sql =	"SELECT tbl_os.os,
					tbl_os.sua_os,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					tbl_os.serie,
					tbl_os.consumidor_nome,
					tbl_os.consumidor_cpf,
					tbl_os.consumidor_fone,
					tbl_os.revenda_nome,
					tbl_os.revenda_cnpj,
					tbl_os.nota_fiscal,
					TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY') AS data_nf,
					tbl_os.consumidor_revenda,
					tbl_os.aparencia_produto,
					tbl_os.acessorios,
					tbl_os.posto,
					tbl_posto_fabrica.posto,
					tbl_posto_fabrica.codigo_posto AS posto_codigo,
					tbl_posto.nome AS posto_nome,
					tbl_tipo_posto.descricao AS posto_tipo,
					tbl_produto.referencia AS produto_referencia,
					tbl_produto.descricao AS produto_descricao,
					tbl_os.os_numero
			FROM  tbl_os
			JOIN  tbl_produto ON tbl_produto.produto = tbl_os.produto
			JOIN  tbl_posto ON tbl_posto.posto = tbl_os.posto
			JOIN  tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.os_numero = $sua_os
			$condPostoCodigo
			ORDER BY tbl_os.sua_os";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 1) {
		$os                 = pg_fetch_result($res, 0, 'os');
		$sua_os             = pg_fetch_result($res, 0, 'sua_os');
		$data_abertura      = pg_fetch_result($res, 0, 'data_abertura');
		$data_fechamento    = pg_fetch_result($res, 0, 'data_fechamento');
		$serie              = pg_fetch_result($res, 0, 'serie');
		$consumidor_nome    = pg_fetch_result($res, 0, 'consumidor_nome');
		$consumidor_cpf     = pg_fetch_result($res, 0, 'consumidor_cpf');
		$consumidor_fone    = pg_fetch_result($res, 0, 'consumidor_fone');
		$revenda_nome       = pg_fetch_result($res, 0, 'revenda_nome');
		$revenda_cnpj       = pg_fetch_result($res, 0, 'revenda_cnpj');
		$nota_fiscal        = pg_fetch_result($res, 0, 'nota_fiscal');
		$data_nf            = pg_fetch_result($res, 0, 'data_nf');
		$consumidor_revenda = pg_fetch_result($res, 0, 'consumidor_revenda');
		$aparencia_produto  = pg_fetch_result($res, 0, 'aparencia_produto');
		$acessorios         = pg_fetch_result($res, 0, 'acessorios');
		$posto_codigo       = pg_fetch_result($res, 0, 'posto_codigo');
		$posto              = pg_fetch_result($res, 0, 'posto');
		$posto_nome         = pg_fetch_result($res, 0, 'posto_nome');
		$posto_tipo         = pg_fetch_result($res, 0, 'posto_tipo');
		$produto_referencia = pg_fetch_result($res, 0, 'produto_referencia');
		$produto_descricao  = pg_fetch_result($res, 0, 'produto_descricao');
		$os_numero          = pg_fetch_result($res, 0, 'os_numero');
		$posto              = pg_fetch_result($res, 0, 'posto');

		$info_os .= '<br>';
		$info_os .= '<input type="hidden" name="os" value="' . $os . '">';
		$info_os .= '<input type="hidden" name="posto" value="' . $posto . '">';

		$info_os .= '<table width="750" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">';
		$info_os .= '<tr class="Menu">';
		$info_os .= '<td colspan="5">INFORMAÇÕES DA OS</td>';
		$info_os .= '</tr>';
		$info_os .= '<tr class="Menu">';
		$info_os .= '<td>OS FABRICANTE</td>';
		$info_os .= '<td>CÓDIGO DO POSTO</td>';
		$info_os .= '<td colspan="2">NOME DO POSTO</td>';
		$info_os .= '<td>TIPO DO POSTO</td>';
		$info_os .= '</tr>';
		$info_os .= '<tr class="Conteudo">';
		$info_os .= '<td nowrap>&nbsp;';
		if ($login_fabrica == 7)
			$info_os .= $sua_os;
		else
			$info_os .= $os;

		$upper_consumidor_revenda = strtoupper($consumidor_revenda);
		switch ($upper_consumidor_revenda) {
		case 'C':
			$info_os .= ' - CONSUMIDOR';
			break;
		case 'R':
			$info_os .= ' - REVENDA';
			break;
		}

		$info_os .= '<input type="hidden" name="os_numero" value="' . $os_numero . '">';
		$info_os .= '</td>';
		$info_os .= '<td nowrap><b>&nbsp;' . $posto_codigo . '</b></td>';
		$info_os .= '<td nowrap colspan="2"><b>&nbsp;' . $posto_nome . '</b></td>';
		$info_os .= '<td nowrap><b>&nbsp;' . $posto_tipo . '</b></td>';
		$info_os .= '</tr>';
		$info_os .= '<tr class="Menu">';
		$info_os .= '<td>DATA DE ABERTURA</td>';
		$info_os .= '<td>REFERÊNCIA DO PRODUTO</td>';
		$info_os .= '<td colspan="2">DESCRIÇÃO DO PRODUTO</td>';
		$info_os .= '<td>Nº SÉRIE</td>';
		$info_os .= '</tr>';
		$info_os .= '<tr class="Conteudo">';
		$info_os .= '<td nowrap>&nbsp;' . $data_abertura . '</td>';
		$info_os .= '<td nowrap>&nbsp;' . $produto_referencia . '</td>';
		$info_os .= '<td nowrap colspan="2">&nbsp;' . $produto_descricao . '</td>';
		$info_os .= '<td nowrap>&nbsp;' . $serie . '</td>';
		$info_os .= '</tr>';
		$info_os .= '<tr class="Menu">';
		$info_os .= '<td colspan="2">NOME DO CONSUMIDOR</td>';
		$info_os .= '<td>CPF/CNPJ DO CONSUMIDOR</td>';
		$info_os .= '<td colspan="2">TELEFONE DO CONSUMIDOR</td>';
		$info_os .= '</tr>';
		$info_os .= '<tr class="Conteudo">';
		$info_os .= '<td nowrap colspan="2">&nbsp;' .$consumidor_nome . '</td>';
		$info_os .= '<td nowrap>&nbsp;' . $consumidor_cpf . '</td>';
		$info_os .= '<td nowrap colspan="2">&nbsp;' . $consumidor_fone . '</td>';
		$info_os .= '</tr>';
		$info_os .= '<tr class="Menu">';
		$info_os .= '<td colspan="2">NOME DA REVENDA</td>';
		$info_os .= '<td>CNPJ REVENDA</td>';
		$info_os .= '<td>NOTA FISCAL</td>';
		$info_os .= '<td>DATA COMPRA</td>';
		$info_os .= '</tr>';
		$info_os .= '<tr class="Conteudo">';
		$info_os .= '<td nowrap colspan="2">&nbsp;' . $consumidor_nome . '</td>';
		$info_os .= '<td nowrap>&nbsp;' . $consumidor_cpf . '</td>';
		$info_os .= '<td nowrap>&nbsp;' . $nota_fiscal . '</td>';
		$info_os .= '<td nowrap>&nbsp;' . $data_nf . '</td>';
		$info_os .= '</tr>';
		$info_os .= '<tr class="Menu">';
		$info_os .= '<td colspan="2">APARÊNCIA DO PRODUTO</td>';
		$info_os .= '<td colspan="2">ACESSÓRIOS</td>';
		$info_os .= '<td>DATA DE FECHAMENTO</td>';
		$info_os .= '</tr>';
		$info_os .= '<tr class="Conteudo">';
		$info_os .= '<td nowrap colspan="2">&nbsp;' . $aparencia_produto . '</td>';
		$info_os .= '<td nowrap colspan="2">&nbsp;' . $acessorios . '</td>';
		$info_os .= '<td nowrap>&nbsp;' . $data_fechamento . '</td>';
		$info_os .= '</tr>';
		$info_os .= '</table>';
	} else {
		$info_os .= '<br>';
		$info_os .= '<table width="750" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">';
		$info_os .= '<tr class="Menu">';
		$info_os .= '<td>OS NÃO ENCONTRADA</td>';
		$info_os .= '</tr>';
		$info_os .= '</table>';
	}

}


if ($btn_acao == "ALTERAR") {
	if (!empty($_POST['os_numero'])) {
		$os_numero = $_POST['os_numero'];
	} else {
		$erro .= 'Pesquise a OS Fabricante a ser transferida.<br/>';
	}

	if (!empty($_POST['posto_codigo_destino'])) {
		$posto_codigo_destino = $_POST['posto_codigo_destino'];
	} else {
		$erro .= 'Informe o Código do Posto a ser transferido a OS.<br/>';
	}

	if (!empty($_POST['posto_nome_destino'])) {
		$posto_nome_destino = $_POST['posto_nome_destino'];
	} else {
		$erro .= 'Informe o Nome do Posto a ser transferido a OS.<br/>';
	}

	if (empty($erro)) {
		$posto_codigo_destino = trim($posto_codigo_destino);
		$posto_nome_destino   = trim($posto_nome_destino);
		$sqlPosto = "SELECT posto FROM tbl_posto JOIN tbl_posto_fabrica USING (posto)
					WHERE trim(tbl_posto_fabrica.codigo_posto) = '$posto_codigo_destino'
					AND trim(tbl_posto.nome) = '$posto_nome_destino'
					AND tbl_posto_fabrica.fabrica = $login_fabrica";
		$resPosto = pg_query($con, $sqlPosto);

		if (pg_num_rows($resPosto) == 1) {
			$posto_destino = pg_fetch_result($resPosto, 0, 'posto');

			$res = pg_query($con,"BEGIN TRANSACTION");

			$sql =	"SELECT tbl_os.os,
							tbl_os.sua_os,
							tbl_os.data_fechamento,
							tbl_os.posto
						FROM tbl_os
						WHERE tbl_os.os_numero = $os_numero
						AND   fabrica = $login_fabrica";
			$res = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows > 0) {
				for ($x=0; $x<$rows; $x++){
					$os              = pg_fetch_result($res,$x,'os');
					$sua_os          = pg_fetch_result($res,$x,'sua_os');
					$data_fechamento = pg_fetch_result($res,$x,'data_fechamento');

					if(strlen($data_fechamento) == 0){
						$sql = "UPDATE tbl_os SET posto = $posto_destino
							WHERE os_numero = $os_numero
							AND   fabrica = $login_fabrica";
						$res = @pg_query($con, $sql);
						$erro = pg_last_error($con);
					} else {
						$erro .= " OS $sua_os já fechada não pode ser transferida.<br/> ";
					}
				}

				if (strlen($erro)==0) {
					$sql = "UPDATE tbl_os_revenda SET posto = $posto_destino
							WHERE os_revenda = $os_numero
							AND   fabrica = $login_fabrica;";
					$res = @pg_query($con, $sql);
					$erro = pg_last_error($con);
				}

			} else {
				$erro .= " OS não encontrada.<br/> ";
			}

			if (strlen($erro) == 0) {
				$res = pg_query($con,"COMMIT TRANSACTION");
				$ok = "<br> Transferência efetuada com sucesso! <br><br>";
				header ("Location: $PHP_SELF?ok=$ok");
				echo '...';
				exit;
			} else {
				$res = pg_query($con, "ROLLBACK TRANSACTION");
			}

		} else {
			$erro .= 'Posto não encontrado.';
		}

	}
}


$title = "Transferência de Ordem de Serviço";

$layout_menu = 'os';

include_once "cabecalho.php";
?>

<style type="text/css">
.Menu {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596D9B;
	background-color: #D9E2EF;
}
.Conteudo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#000000;
	background-color: #FFFFFF;
}
.Tabela {
	border: 1px solid #CED7E7;
}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>

<?php include_once "javascript_pesquisas.php"; ?>

<?php
$msg = '';
if (!empty($ok)) {
	$class = 'sucesso';
	$msg   = $ok;
}
elseif (!empty($erro)) {
	$class = 'error';
	$msg   = $erro;
}
?>

<?php if (!empty($msg)) { ?>
<br>
<table width="650" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td valign="middle" align="center" class="<?php echo $class; ?>"><?php echo $msg; ?></td>
	</tr>
</table>
<br>
<?php } ?>

<form name="frm_tranferencia" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="btn_acao" value="">

<table width="650" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">
	<tr class="Menu">
		<td colspan="3">INFORMAÇÕES PARA CONSULTA</td>
	</tr>
	<tr class="Menu">
		<td>OS FABRICANTE</td>
		<td>CÓDIGO DO POSTO</td>
		<td>NOME DO POSTO</td>
	</tr>
	<tr>
		<td><input type="text" name="sua_os" size="20" value="<?echo $sua_os?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui a OS do Fabricante.');"></td>
		<td><input type="text" name="posto_codigo_origem" size="10" value="<?echo $posto_codigo_origem?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o código do posto e clique na lupa para efetuar a pesquisa.');">&nbsp;<img border="0" src="imagens/btn_buscar5.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_tranferencia.posto_codigo_origem, document.frm_tranferencia.posto_nome_origem, 'codigo')" style="cursor: pointer;"></td>
		<td><input type="text" name="posto_nome_origem" size="40" value="<?echo $posto_nome_origem?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o nome do posto e clique na lupa para efetuar a pesquisa.');">&nbsp;<img border="0" src="imagens/btn_buscar5.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_tranferencia.posto_codigo_origem, document.frm_tranferencia.posto_nome_origem, 'nome')" style="cursor: pointer;"></td>
	</tr>
</table>

<br>

<img border="0" src="imagens/btn_pesquisar_400.gif" onclick="javascript: if (document.frm_tranferencia.btn_acao.value == '' ) { document.frm_tranferencia.btn_acao.value='PESQUISA' ; document.frm_tranferencia.submit() } else { alert ('Aguarde submissão') }" ALT="Consultar OS Fabricante" style="cursor: pointer;">

<br>

<?php
if (!empty($info_os)) {
	echo $info_os;
} else {
	?>
	<br>
	<table width="750" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">
		<tr class="Menu">
			<td>PREENCHA O CAMPO OS FABRICANTE</td>
		</tr>
	</table>
<?php } ?>

<br>

<table width="650" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">
	<tr class="Menu">
		<td colspan="3">INFORMAÇÕES PARA CONSULTA - POSTO DESTINO</td>
	</tr>
	<tr class="Menu">
		<td>CÓDIGO DO POSTO</td>
		<td>NOME DO POSTO</td>
	</tr>
	<tr>
		<td>
			<input type="text" name="posto_codigo_destino" size="15" value="<?echo $posto_codigo_destino; ?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o código do posto e clique na lupa para efetuar a pesquisa.');">&nbsp;<img border="0" src="imagens/btn_buscar5.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_tranferencia.posto_codigo_destino, document.frm_tranferencia.posto_nome_destino, 'codigo')" style="cursor: pointer;">
		</td>
		<td>
			<input type="text" name="posto_nome_destino" size="50" value="<?echo $posto_nome_destino?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o nome do posto e clique na lupa para efetuar a pesquisa.');">&nbsp;<img border="0" src="imagens/btn_buscar5.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_tranferencia.posto_codigo_destino, document.frm_tranferencia.posto_nome_destino, 'nome')" style="cursor: pointer;">
		</td>
	</tr>
</table><br>

<?php if (strlen($os) > 0 AND strlen($posto) > 0) { ?>
<img border="0" src="imagens/btn_alterarcinza.gif" onclick="javascript: if (document.frm_tranferencia.btn_acao.value == '' ) { document.frm_tranferencia.btn_acao.value='ALTERAR' ; document.frm_tranferencia.submit() } else { alert ('Aguarde submissão') }" ALT="Transferir OS" style="cursor: pointer;">
<?php } ?>

</form>

<br>

<?php include_once "rodape.php";?>

