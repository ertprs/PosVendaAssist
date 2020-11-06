<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$msg = "";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0)  $acao = strtoupper($_GET["acao"]);

if ($acao == "PESQUISAR") {
	if (strlen($_POST["mes"]) > 0) $mes = $_POST["mes"];
	if (strlen($_GET["mes"]) > 0)  $mes = $_GET["mes"];
	if (strlen($_POST["ano"]) > 0) $ano = $_POST["ano"];
	if (strlen($_GET["ano"]) > 0)  $ano = $_GET["ano"];
	
	if (strlen($mes) == 0) $msg .= " Selecione o mês desejado para realizar a pesquisa. ";
	if (strlen($ano) == 0) $msg .= " Selecione o ano desejado para realizar a pesquisa. ";
	
	if (strlen($_POST["revenda_cnpj"]) > 0) $revenda_cnpj = trim($_POST["revenda_cnpj"]);
	if (strlen($_GET["revenda_cnpj"]) > 0)  $revenda_cnpj = trim($_GET["revenda_cnpj"]);
	if (strlen($_POST["revenda_nome"]) > 0) $revenda_nome = trim($_POST["revenda_nome"]);
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
	
	if (strlen($_POST["numero_os"]) > 0) $numero_os = trim($_POST["numero_os"]);
	if (strlen($_GET["numero_os"]) > 0)  $numero_os = trim($_GET["numero_os"]);
	
	if (strlen($_POST["numero_serie"]) > 0) $numero_serie = trim($_POST["numero_serie"]);
	if (strlen($_GET["numero_serie"]) > 0)  $numero_serie = trim($_GET["numero_serie"]);
}

$layout_menu = "os";
$title       = "Relação de Ordens de Serviços de Revenda Lançadas";

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

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo">
		<td colspan="4">Preencha os campos para realizar a pesquisa.<br>Campos com * é de preenchimento obrigatório.</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>Mês *</td>
		<td>Ano *</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
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
		<td>
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
		<td>&nbsp;</td>
		<td>CNPJ da Revenda</td>
		<td>Nome da Revenda</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td>
			<input type="text" name="revenda_cnpj" size="8" value="<?echo $revenda_cnpj?>">
			<img border="0" src="imagens/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar revendas pelo código" onclick="javascript: fnc_pesquisa_revenda (document.frm_pesquisa.revenda_cnpj, 'cnpj');">
		</td>
		<td>
			<input type="text" name="revenda_nome" size="15" value="<?echo $revenda_nome?>">
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
		<td>&nbsp;</td>
		<td>Numero da OS Revenda</td>
		<td>Número Série</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td><input type="text" name="numero_os" size="10" value="<?echo $numero_os?>"></td>
		<td><input type="text" name="numero_serie" size="10" value="<?echo $numero_serie?>"></td>
		<td>&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4"><img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>

<br>

<?
if (strlen($acao) > 0 && strlen($msg) == 0) {
	$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	
	$sql =	"SELECT	DISTINCT
					tbl_os_revenda.os_revenda                                          ,
					tbl_os_revenda.sua_os                                              ,
					tbl_os_revenda.explodida                                           ,
					TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS abertura     ,
					tbl_os_revenda.revenda                                             ,
					tbl_revenda.cnpj                                   AS revenda_cnpj ,
					tbl_revenda.nome                                   AS revenda_nome ,
					tbl_posto_fabrica.codigo_posto
		FROM		tbl_os_revenda
		JOIN		tbl_posto           ON  tbl_posto.posto                = tbl_os_revenda.posto
		JOIN		tbl_posto_fabrica   ON  tbl_posto.posto                = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica      = $login_fabrica
		JOIN		tbl_os_revenda_item ON  tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
		JOIN		tbl_produto         ON  tbl_produto.produto            = tbl_os_revenda_item.produto
		LEFT JOIN	tbl_revenda         ON  tbl_revenda.revenda            = tbl_os_revenda.revenda
		WHERE		tbl_os_revenda.fabrica = $login_fabrica
		AND			tbl_os_revenda.posto   = $login_posto
		AND tbl_os_revenda.digitacao BETWEEN '$data_inicial' AND '$data_final'";
	
	if (strlen($revenda) > 0) $sql .= " AND tbl_os_revenda.revenda = $revenda";
	
	if (strlen($numero_os) > 0) {
		if ($login_fabrica == 1) {
			$pos = strpos($numero_os, "-");
			if ($pos === false) {
				$pos = strlen($numero_os) - 5;
			}else{
				$pos = $pos - 5;
			}
			$numero_os = substr($numero_os, $pos, strlen($numero_os));
		}
		$sql .= " AND tbl_os_revenda.sua_os = '$numero_os'";
	}
	
	if (strlen($numero_serie) > 0) $sql .= " AND tbl_os_revenda_item.serie = '$numero_serie'";
	
	$sql .= " ORDER BY tbl_os_revenda.os_revenda DESC;";
	
	$res = pg_exec($con,$sql);
	
//	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res);
	
	if (pg_numrows($res) > 0) {
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i == 0) {
				echo "<tr class='Titulo' height='15'>";
				echo "<td>OS</td>";
				echo "<td>DATA</td>";
				echo "<td>REVENDA</td>";
				echo "<td colspan='3'>AÇÕES</td>";
				echo "</tr>";
			}
			
			$os_revenda    = trim(pg_result($res,$i,os_revenda));
			$sua_os        = trim(pg_result($res,$i,sua_os));
			$explodida          = trim(pg_result($res,$i,explodida));
			$abertura      = trim(pg_result($res,$i,abertura));
			$revenda_cnpj      = trim(pg_result($res,$i,revenda_cnpj));
			$revenda_nome      = trim(pg_result($res,$i,revenda_nome));
			$codigo_posto      = trim(pg_result($res,$i,codigo_posto));
			
			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			}else{
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}
			
			$sql =	"SELECT *
					FROM tbl_os
					WHERE sua_os ILIKE '$sua_os-%';";
			$resX = pg_exec($sql);
			
			if ($login_fabrica == 1) $sua_os = $codigo_posto.$sua_os;
			
			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap>" . $sua_os . "</td>";
			echo "<td nowrap>" . $abertura . "</td>";
			echo "<td nowrap><acronym title='CNPJ: $revenda_cnpj\nRAZÃO SOCIAL: $revenda_nome' style='cursor: help;'>" . substr($revenda_nome,0,20) . "</acronym></td>";
			
			echo "<td width='80' align='center'>";
			if (pg_numrows($resX) == 0 || strlen($explodida) == 0)
				echo "<a href='os_revenda.php?os_revenda=$os_revenda'><img border='0' src='imagens/btn_alterar_".$botao.".gif'></a>";
			else
				echo "&nbsp;";
			echo "</td>";
			
			echo "<td width='80' align='center'>";
			if ($login_fabrica == 1 && pg_numrows($resX) == 0 || strlen($explodida) == 0)
				echo "<a href='os_revenda_finalizada.php?os_revenda=$os_revenda&btn_acao=explodir'><img border='0' src='imagens/btn_explodir.gif'></a>";
			else
				echo "&nbsp;";
			echo "</td>";
			
			echo "<td width='80' align='center'>";
			echo "<a href='os_revenda_print.php?os_revenda=$os_revenda' target='_blank'><img border='0' src='imagens/btn_imprimir_".$botao.".gif'></a>";
			echo "</td>";
			
			echo "</tr>";
		}
		echo "</table>";
	}
}
?>

<br>

<? include "rodape.php" ?>
