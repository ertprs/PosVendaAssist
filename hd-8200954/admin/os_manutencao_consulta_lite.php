<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";

$msg = "";

// pega os valores das variaveis dadas como parametros de pesquisa e coloca em um cookie
setcookie("cookredirect", $_SERVER["REQUEST_URI"]); // expira qdo fecha o browser

$os = $_GET["excluir"];

if (strlen($os) > 0) {
	$sql =	"SELECT sua_os
			FROM tbl_os
			WHERE os = $os;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (@pg_numrows($res) == 1) {
		$sua_os = @pg_result($res,0,0);
		$sua_os_explode = explode("-", $sua_os);
		$xsua_os = $sua_os_explode[0];
	}

	if ($login_fabrica == 3){
		$sql = "UPDATE tbl_os SET excluida = 't' , admin_excluida = $login_admin WHERE os = $os AND fabrica = $login_fabrica";
		$res = @pg_exec ($con,$sql);
		
		#158147 Paulo/Waldir desmarcar se for reincidente
		$sql = "SELECT fn_os_excluida_reincidente($os,$login_fabrica)";
		$res = pg_exec($con, $sql);

	}else{
		$sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin);";
		$res = @pg_exec ($con,$sql);
	}
	$msg_erro = pg_errormessage($con);
	$xsua_os = strtoupper($xsua_os);
	if (strlen($msg_erro) == 0) {
		$sql =	"SELECT sua_os
				FROM tbl_os
				WHERE sua_os LIKE '$xsua_os-%'
				AND   posto   = $login_posto
				AND   fabrica = $login_fabrica;";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (@pg_numrows($res) == 0) {
			$sql = "DELETE FROM tbl_os_revenda
					WHERE  tbl_os_revenda.sua_os  = '$xsua_os'
					AND    tbl_os_revenda.fabrica = $login_fabrica
					AND    tbl_os_revenda.posto   = $login_posto";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$url = $_COOKIE["cookredirect"];
		header("Location: $url");
		exit;
	}
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0)  $acao = strtoupper($_GET["acao"]);

if ($acao == "PESQUISAR") {
	if (strlen(trim($_GET["opcao1"])) > 0)  $opcao1 = trim($_GET["opcao1"]);
	if (strlen(trim($_GET["opcao2"])) > 0)  $opcao2 = trim($_GET["opcao2"]);
	if (strlen(trim($_GET["opcao3"])) > 0)  $opcao3 = trim($_GET["opcao3"]);
	if (strlen(trim($_GET["opcao4"])) > 0)  $opcao4 = trim($_GET["opcao4"]);
	if (strlen(trim($_GET["opcao5"])) > 0)  $opcao5 = trim($_GET["opcao5"]);
	if (strlen(trim($_GET["opcao6"])) > 0)  $opcao6 = trim($_GET["opcao6"]);
	if (strlen(trim($_GET["opcao7"])) > 0)  $opcao7 = trim($_GET["opcao7"]);

	if (strlen($opcao1) == 0 && strlen($opcao2) == 0 && strlen($opcao3) == 0 && strlen($opcao4) == 0 && strlen($opcao5) == 0 && strlen($opcao6) == 0) {
		$msg .= " Selecione pelo menos uma opção para realizar a pesquisa. ";
	}

	if (strlen($erro) == 0 && strlen($opcao1) > 0) {
		if (strlen($_GET["mes"]) > 0)  $mes = $_GET["mes"];
		if (strlen($_GET["ano"]) > 0)  $ano = $_GET["ano"];

		if (strlen($mes) == 0) $msg .= " Selecione o mês para realizar a pesquisa. ";
		if (strlen($ano) == 0) $msg .= " Selecione o ano para realizar a pesquisa. ";
		if(strlen($opcao2)==0 AND strlen($opcao3)==0 and strlen($opcao4)==0)  $msg .= " Informe mais parametros para pesquisa. ";

	}else{
		$mes = "";
		$ano = "";
	}

	if (strlen($opcao2) > 0) {
		if (strlen($mes) == 0) $msg .= " Selecione o mês para realizar a pesquisa. ";
		if (strlen($ano) == 0) $msg .= " Selecione o ano para realizar a pesquisa. ";

		if (strlen($_GET["posto_codigo"]) > 0) $posto_codigo = "'".trim($_GET["posto_codigo"])."'";
		if (strlen($_GET["posto_nome"]) > 0)   $posto_nome = trim($_GET["posto_nome"]);

		if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
			$sql =	"SELECT tbl_posto_fabrica.posto        ,
							tbl_posto_fabrica.codigo_posto ,
							tbl_posto.nome
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING (posto)
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND   tbl_posto_fabrica.codigo_posto = $posto_codigo;";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$posto        = pg_result($res,0,posto);
				$posto_codigo = pg_result($res,0,codigo_posto);
				$posto_nome   = pg_result($res,0,nome);
			}else{
				$msg .= " Posto não encontrado. ";
			}
		}
	}else{
		$posto        = "";
		$posto_codigo = "";
		$posto_nome   = "";
	}

	if (strlen($opcao3) > 0) {
		if (strlen($mes) == 0) $msg .= " Selecione o mês para realizar a pesquisa. ";
		if (strlen($ano) == 0) $msg .= " Selecione o ano para realizar a pesquisa. ";

		if (strlen($_GET["revenda_cnpj"]) > 0)  $revenda_cnpj = trim($_GET["revenda_cnpj"]);
		if (strlen($_GET["revenda_nome"]) > 0)  $revenda_nome = trim($_GET["revenda_nome"]);

		if (strlen($revenda_cnpj) > 0 && strlen($revenda_nome) > 0) {
			$sql =	"SELECT revenda , cnpj , nome
					FROM tbl_revenda
					WHERE cnpj = '$revenda_cnpj';";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$revenda      = pg_result($res,0,revenda);
				$revenda_cnpj = pg_result($res,0,cnpj);
				$revenda_nome = pg_result($res,0,nome);
			}else{
				$msg .= " Revenda não encontrada. ";
			}
		}
	}else{
		$revenda = "";
		$revenda_cnpj = "";
		$revenda_nome = "";
	}

	if (strlen($opcao4) > 0) {
		if (strlen($mes) == 0) $msg .= " Selecione o mês para realizar a pesquisa. ";
		if (strlen($ano) == 0) $msg .= " Selecione o ano para realizar a pesquisa. ";

		if (strlen($_GET["produto_referencia"]) > 0)  $produto_referencia = trim($_GET["produto_referencia"]);
		if (strlen($_GET["produto_descricao"]) > 0)   $produto_descricao  = trim($_GET["produto_descricao"]);
		if (strlen($_GET["produto_voltagem"]) > 0)    $produto_voltagem   = trim($_GET["produto_voltagem"]);

		if (strlen($produto_referencia) > 0 && strlen($produto_descricao) > 0) {
			$sql =	"SELECT tbl_produto.produto    ,
							tbl_produto.referencia ,
							tbl_produto.descricao  ,
							tbl_produto.voltagem
					FROM tbl_produto
					JOIN tbl_linha USING (linha)
					WHERE tbl_linha.fabrica    = $login_fabrica
					AND   tbl_produto.referencia = '$produto_referencia'";
			if ($login_fabrica == 1) $sql .= " AND tbl_produto.voltagem = '$produto_voltagem';";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$produto            = pg_result($res,0,produto);
				$produto_referencia = pg_result($res,0,referencia);
				$produto_descricao  = pg_result($res,0,descricao);
				$produto_voltagem   = pg_result($res,0,voltagem);
			}else{
				$msg .= " Produto não encontrado. ";
			}
		}
	}else{
		$produto = "";
		$produto_referencia = "";
		$produto_descricao = "";
		$produto_voltagem = "";
	}

	if (strlen($opcao5) > 0) {
		if (strlen($_GET["numero_os"]) > 0)  $numero_os = trim($_GET["numero_os"]);

		if (strlen($numero_os) > 0 && strlen($numero_os) < 3) $msg .= " Digite o número de série com o mínimo de 3 números. ";
	}else{
		$numero_os = "";
	}

	if (strlen($opcao6) > 0) {
		if (strlen($_GET["numero_serie"]) > 0)  $numero_serie = trim($_GET["numero_serie"]);

		if (strlen($numero_serie) > 0 && strlen($numero_serie) < 3) $msg .= " Digite o número de série com o mínimo de 3 números. ";
	}else{
		$numero_serie = "";
	}
}

$layout_menu = "os";
$title       = "Relação de Ordens de Serviços de Manutenção Lançadas";

include "cabecalho.php";
?>

<script language="JavaScript">

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
	janela.nome			= document.frm_pesquisa.revenda_nome;
	janela.cnpj			= document.frm_pesquisa.revenda_cnpj;
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

</script>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<br>

<? if (strlen($msg) > 0) { ?>
<table width="600" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td class="error"><?echo $msg?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_pesquisa" method="get" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="450" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo">
		<td colspan="6">Preencha os campos para realizar a pesquisa.</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao1" value="1" class="frm" <? if (strlen($opcao1) > 0) echo "checked"; ?>> Período </td>
		<td>Mês</td>
		<td colspan="2">Ano</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>
			<select name="mes" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}
			?>
			</select>
		</td>
		<td colspan="2">
			<select name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 2003 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao2" value="2" class="frm" <? if (strlen($opcao2) > 0) echo "checked"; ?>> Posto</td>
		<td>Código do Posto</td>
		<td colspan="2">Razão Social</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>
			<input type="text" name="posto_codigo" size="8" value="<?echo $posto_codigo?>" class="frm">
			<img border="0" src="imagens/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo, document.frm_pesquisa.posto_nome, 'codigo')">
		</td>
		<td colspan="2">
			<input type="text" name="posto_nome" size="15" value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo, document.frm_pesquisa.posto_nome, 'nome')">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao3" value="3" class="frm" <? if (strlen($opcao3) > 0) echo "checked"; ?>> Cliente</td>
		<td>CPF/CNPJ do Cliente</td>
		<td colspan="2">Nome do Cliente</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>
			<input type="text" name="revenda_cnpj" size="8" value="<?echo $revenda_cnpj?>" class="frm">
			<img border="0" src="imagens/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar revendas pelo código" onclick="javascript: fnc_pesquisa_revenda (document.frm_pesquisa.revenda_cnpj, 'cnpj');">
		</td>
		<td colspan="2">
			<input type="text" name="revenda_nome" size="15" value="<?echo $revenda_nome?>" class="frm">
			<img border="0" src="imagens/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar pelo nome da revenda." onclick="javascript: fnc_pesquisa_revenda (document.frm_pesquisa.revenda_nome, 'nome');">
		</td>
		<td>
			&nbsp;
			<input type='hidden' name = 'revenda_fone'>
			<input type='hidden' name = 'revenda_cidade'>
			<input type='hidden' name = 'revenda_estado'>
			<input type='hidden' name = 'revenda_endereco'>
			<input type='hidden' name = 'revenda_numero'>
			<input type='hidden' name = 'revenda_complemento'>
			<input type='hidden' name = 'revenda_bairro'>
			<input type='hidden' name = 'revenda_cep'>
			<input type='hidden' name = 'revenda_email'>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao4" value="4" class="frm" <? if (strlen($opcao4) > 0) echo "checked"; ?>> Produto</td>
		<td>Referência</td>
		<td>Descrição</td>
		<td>Voltagem</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td><input type="text" name="produto_referencia" size="8" value="<?echo $produto_referencia?>" class="frm"> <img src="imagens/btn_lupa.gif" width='20' height='18'  width='20' height='18' style="cursor: hand;" align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao, 'referencia', document.frm_pesquisa.produto_voltagem)"></td>
		<td><input type="text" name="produto_descricao" size="15" value="<?echo $produto_descricao?>" class="frm"> <img src="imagens/btn_lupa.gif" width='20' height='18'  style="cursor: hand;" align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao, 'descricao', document.frm_pesquisa.produto_voltagem)"></td>
		<td><input type='text' name='produto_voltagem' size='5' value="<?echo $produto_voltagem?>" class="frm"></td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td align="left" colspan="2"><input type="checkbox" name="opcao5" value="5" class="frm" <? if (strlen($opcao5) > 0) echo "checked"; ?>> Número da OS Revenda</td>
		<td colspan="2"><input type="text" name="numero_os" size="15" value="<?echo $numero_os?>" class="frm"></td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td align="left" colspan="2"><input type="checkbox" name="opcao6" value="6" class="frm" <? if (strlen($opcao6) > 0) echo "checked"; ?>> Número Série</td>
		<td colspan="2"><input type="text" name="numero_serie" size="15" value="<?echo $numero_serie?>" class="frm"></td>
		<td>&nbsp;</td>
	</tr>
	<? if($login_fabrica==19){ ?>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td align="left" colspan="4"><input type="checkbox" name="opcao7" value="7" class="frm" <? if (strlen($opcao7) > 0) echo "checked"; ?>> Somente OS não Efetivadas</td>
		<td>&nbsp;</td>
	</tr>
<? } ?>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="6" align="center"><img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>

<br>

<?
if (strlen($acao) > 0 && strlen($msg) == 0) {
	if (strlen($mes) > 0 && strlen($ano) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}
	$sql =	"SELECT DISTINCT
					tbl_os_revenda.os_revenda                                          ,
					tbl_os_revenda.sua_os                                              ,
					tbl_os_revenda.explodida                                           ,
					TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS abertura     ,
					tbl_os_revenda.revenda                                             ,
					tbl_os_revenda.posto                                               ,
					tbl_cliente.cpf                                    AS cliente_cpf  ,
					tbl_cliente.nome                                   AS cliente_nome ,
					tbl_posto.nome as nome_posto                                       ,
					tbl_posto_fabrica.codigo_posto
		FROM		tbl_os_revenda
		LEFT JOIN	tbl_os_revenda_item ON  tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
		LEFT JOIN	tbl_produto         ON  tbl_produto.produto            = tbl_os_revenda_item.produto
		LEFT JOIN	tbl_cliente         ON  tbl_cliente.cliente            = tbl_os_revenda.cliente
		JOIN tbl_posto on tbl_posto.posto = tbl_os_revenda.posto
		JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE	tbl_os_revenda.fabrica = $login_fabrica
		AND		tbl_os_revenda.os_manutencao IS TRUE ";

	if (strlen($data_inicial) > 0 && strlen($data_final) > 0) {
		$sql .= " AND tbl_os_revenda.digitacao BETWEEN '$data_inicial' AND '$data_final'";
	}

	$numero_os    = strtoupper($numero_os);
	$numero_serie = strtoupper($numero_serie);

	if (strlen($posto) > 0)        $sql .= " AND tbl_os_revenda.posto = $posto";
	if (strlen($revenda) > 0)      $sql .= " AND tbl_os_revenda.revenda = $revenda";
	if (strlen($produto) > 0)      $sql .= " AND tbl_os_revenda_item.produto = $produto";
	if (strlen($numero_os) > 0)    $sql .= " AND tbl_os_revenda.sua_os LIKE '%$numero_os%'";
	if (strlen($numero_serie) > 0) $sql .= " AND tbl_os_revenda_item.serie LIKE '%$numero_serie%'";

	$sql .= " ORDER BY tbl_os_revenda.os_revenda DESC;";
	$res = pg_exec($con,$sql);
	$total_registro = pg_numrows($res);
	if ($total_registro > 0) {

		echo "<table border='1' cellpadding='2' align='center' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td>OS</td>";
		echo "<td>DATA</td>";
		echo "<td>CLIENTE</td>";
		echo "<td colspan='3'>AÇÕES</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$os_manutencao= trim(pg_result($res,$i,os_revenda));
			$sua_os       = trim(pg_result($res,$i,sua_os));
			$explodida    = trim(pg_result($res,$i,explodida));
			$abertura     = trim(pg_result($res,$i,abertura));
			$cliente_cnpj = trim(pg_result($res,$i,cliente_cpf));
			$cliente_nome = trim(pg_result($res,$i,cliente_nome));
			$xxposto      = trim(pg_result($res,$i,posto));
			$nome_posto   = trim(pg_result($res,$i,nome_posto));
			$codigo_posto = trim(pg_result($res,$i,codigo_posto));

			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			}else{
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap>".$sua_os."</td>";
			echo "<td nowrap>".$abertura."</td>";
			echo "<td nowrap><acronym title='CNPJ: $cliente_cnpj\nRAZÃO SOCIAL: $cliente_nome' style='cursor: help;'>" . substr($cliente_nome,0,20) . "</acronym></td>";

			echo "<td width='80' align='center'>";
			if (strlen($explodida) == 0) {
				echo "<a href='os_manutencao.php?os_manutencao=$os_manutencao'><img border='0' src='imagens/btn_alterar_".$botao.".gif'></a>";
			}
			echo "</td>";
			echo "<td width='80' align='center'>";
			if (strlen($explodida) == 0){
				echo "<a href='os_manutencao_finalizada.php?os_manutencao=$os_manutencao&btn_acao=explodir'><img border='0' src='imagens/btn_explodir.gif'></a>";
			}
			echo "</td>";

			echo "<td width='80' align='center'>";
			echo "<a href='os_print_manutencao.php?os_manutencao=$os_manutencao' target='_blank'><img border='0' src='imagens/btn_imprimir_".$botao.".gif'></a>";
			echo "</td>";

			echo "</tr>";
		}
		echo "</table>";
		echo "<p align='center'><b>Total de $total_registro registro(s).</b></p>";
	}else{
		echo "<table border='0' align='center'>";
		echo "<tr>";
		echo "<td><img border='0' src='imagens/atencao.gif'></td>";
		echo "<td> &nbsp; <b>Não foi encontrado nenhuma OS nessa pesquisa.</b></td>";
		echo "</tr>";
		echo "</table>";
	}
}
?>

<br>

<? include "rodape.php" ?>
