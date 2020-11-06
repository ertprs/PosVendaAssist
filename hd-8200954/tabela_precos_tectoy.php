<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_fabrica == 14) {
	header ("Location: tabela_precos_intelbras.php");
	exit;
}
if ($login_fabrica == 1) {
	header ("Location: tabela_precos_blackedecker_consulta.php");
	exit;
}

$sql = "SELECT pedido_faturado FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
if (pg_result ($res,0,0) == 'f') {
	$title = "Tabela de Preços";
	$layout_menu = 'preco';
	include "cabecalho.php";
	echo "<H4>TABELA DE PREÇOS BLOQUEADA</H4>";
	include "rodape.php";
	exit;
}




include 'funcoes.php';

$liberar_preco = true ;
if ($login_fabrica == 3 AND $login_e_distribuidor <> true AND ($login_distribuidor == 1007 OR $login_distribuidor == 560)) $liberar_preco = false;


$title = "Tabela de Preços";

$layout_menu = 'preco';
include "cabecalho.php";

if($_POST['tabela'])             $tabela             = $_POST['tabela'];

if($_POST['referencia_produto']) $referencia_produto = $_POST['referencia_produto'];
if($_POST['descricao_produto'])  $descricao_produto  = $_POST['descricao_produto'];

if($_GET['tabela'])             $tabela              = $_GET['tabela'];

if($_GET['referencia_produto']) $referencia_produto  = $_GET['referencia_produto'];
if($_GET['descricao_produto'])  $descricao_produto   = $_GET['descricao_produto'];

if($_POST['referencia_peca']) $referencia_peca       = $_POST['referencia_peca'];
if($_POST['descricao_peca'])  $descricao_peca        = $_POST['descricao_peca'];

if($_GET['referencia_peca']) $referencia_peca        = $_GET['referencia_peca'];
if($_GET['descricao_peca'])  $descricao_peca         = $_GET['descricao_peca'];

if ($login_fabrica == 3) {
	if (strlen($descricao_produto) == 0 AND strlen($referencia_produto) == 0 AND strlen($descricao_peca) == 0 AND strlen($referencia_peca) == 0) {
		$tabela = "";
	}
}

?>

<? include "javascript_pesquisas.php" ?>

<script language="JavaScript">

/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/
function fnc_pesquisa_produtoXXX (referencia,descricao,tabela) {
	var url = "";
	if (referencia.value != "" || descricao.value != "") {
		url = "pesquisa_tabela.php?referencia=" + referencia.value + "&descricao=" + descricao.value + "&retorno=<?echo $PHP_SELF?>";
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.referencia = referencia;
		janela.descricao  = descricao;
		janela.tabela     = tabela;
		janela.focus();
	}
}
</script>

<style>
.letras {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 13px;
	font-weight: bold;
	border: 0px solid;
	color:#007711;
	background-color: #ffffff
}

.lista {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 13px;
	font-weight: normal;
	border: 0px solid;
	color:#000000;
}
</style>
<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->

<? include "javascript_pesquisas.php" ?>



<form method='POST' action='<? echo $PHP_SELF ?>' name='frm_tabela'>

<table width="500" border="0" cellpadding="0" cellspacing="4" align="center" bgcolor="#ffffff">
<tr>
	<td align="left" width="200" bgcolor='#d9e2ef'>
		<font face="arial"><b>&nbsp;&nbsp;Tabela</b></font>
	</td>

	<td align="left" width="300">
		<select name="tabela" size="1" tabindex="0" class='frm' onchange='javascript: FuncTabela(this.value);'>
<?

		$res = pg_exec ($con,"SELECT linha_pedido FROM tbl_fabrica WHERE fabrica = $login_fabrica");
		$linha_pedido = pg_result ($res,0,0);


		$sql = "SELECT      tbl_tabela.tabela      ,
							tbl_tabela.sigla_tabela,
							tbl_tabela.descricao
				FROM        tbl_tabela
				JOIN        tbl_posto_linha USING (tabela)
				JOIN        tbl_linha    ON tbl_linha.linha   = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
				WHERE       tbl_tabela.fabrica    = $login_fabrica
				AND         tbl_posto_linha.posto = $login_posto
				AND         tbl_tabela.ativa   = 't'
				GROUP BY    tbl_tabela.tabela      ,
							tbl_tabela.sigla_tabela,
							tbl_tabela.descricao ";
		if ($login_fabrica == 1) $sql .= "ORDER BY tbl_tabela.tabela ASC";
		else                     $sql .= "ORDER BY tbl_tabela.sigla_tabela";

//		if ($ip == '201.42.112.110') echo $sql; exit;
		$res = pg_exec($con,$sql);

		if (pg_numrows ($res) == 0 and $linha_pedido <> 't' ) {
			$sql = "SELECT *
					FROM   tbl_tabela
					WHERE  tbl_tabela.fabrica = $login_fabrica
					AND    tbl_tabela.ativa   = 't' ";

			if ($login_fabrica == 1) $sql .= "AND tbl_tabela.sigla_tabela not in ('GARAN') ";

			if ($login_fabrica == 1) $sql .= "ORDER BY tbl_tabela.tabela ASC";
			else                     $sql .= "ORDER BY tbl_tabela.sigla_tabela";

			$res = pg_exec($con,$sql);
		}

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$aux_tabela       = trim(pg_result($res,$i,tabela));
			$aux_sigla_tabela = trim(pg_result($res,$i,descricao));

			echo "<option "; if ($tabela == $aux_tabela) echo " selected "; echo " value='$aux_tabela'>$aux_sigla_tabela</option>";
		}
?>
		</select>
	</td>
</tr>
</table>



<table width="500" border="0" cellpadding="0" cellspacing="4" align="center" bgcolor="#ffffff">
<tr>
	<td align="left" width="200" bgcolor='#d9e2ef'>
		<font face="arial" size='2'><b>&nbsp;&nbsp;Código da Peça</b></font>
	</td>

	<td align="left" width="300"><input class="frm" type="hidden" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>"><input class="frm" type="hidden" name="voltagem" value="<? echo $voltagem; ?>">
		<input class="frm" type="text" name="peca_referencia" size="15" value="<? echo $peca_referencia; ?>"><img src='imagens/btn_buscar5.gif' style="cursor: pointer;" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="javascript: fnc_pesquisa_peca_lista(window.document.frm_tabela.produto_referencia.value,window.document.frm_tabela.peca_referencia,window.document.frm_tabela.peca_descricao, window.document.frm_tabela.preco, window.document.frm_tabela.voltagem,'referencia')" >
	</td>
</tr>
<tr>
	<td align="left" width="200" bgcolor='#d9e2ef'>
		<font face="arial" size='2'><b>&nbsp;&nbsp;Descrição da Peça</b></font>
	</td>

	<td align="left" width="300">
		<input class="frm" type="text" name="peca_descricao" size="30" value="<? echo $peca_descricao ?>"><img src='imagens/btn_buscar5.gif' style="cursor: pointer;" alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle' onclick="javascript: fnc_pesquisa_peca_lista(window.document.frm_tabela.produto_referencia.value,window.document.frm_tabela.peca_referencia,window.document.frm_tabela.peca_descricao, window.document.frm_tabela.preco, window.document.frm_tabela.voltagem,'descricao')">
		
	</td>
</tr>
</table>

		<input type="hidden" name="preco"   value="">
<table width="500" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao"   value="">
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_tabela.btn_acao.value == '' ) { document.frm_tabela.btn_acao.value='continuar' ; document.frm_tabela.submit() } else { alert ('Aguarde submissão') }" ALT="Listar tabela de preços" border='0' style='cursor: hand;'>
	</td>
</tr>
</table>

</form>

<?
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$peca_referencia = $_POST['peca_referencia'];
	$peca_descricao  = $_POST['peca_descricao'];
	$tabela          = $_POST['tabela'];
	
	$sql = "Select tbl_peca.peca ,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_tabela_item.preco
			from tbl_peca 
			join tbl_tabela_item on tbl_tabela_item.peca = tbl_peca.peca 
			and tbl_tabela_item.tabela = $tabela
			where tbl_peca.referencia = '$peca_referencia'";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		$referencia = pg_result($res,0,referencia);
		$descricao  = pg_result($res,0,descricao);
		$preco      = pg_result($res,0,preco);
		$preco      = number_format($preco,2,",",".");
		if($referencia == "070035793846" or substr($referencia,0,3) == "050")$preco = "indisponivel";

		echo "<table width='500' border='0' cellpadding='2' cellspacing='1' align='center' bgcolor='#d9e2ef'  style='font-face:verdana;font-size:10px' >";
		echo "<TR>";
		echo "<Td ><B>Referência</B></td>";
		echo "<Td><B>Descrição</B></td>";
		echo "<Td><B>Preço</B></td>";
		echo "</tr>";
		echo "<TR>";
		echo "<Td bgcolor='#ffffff'>$referencia</td>";
		echo "<Td bgcolor='#ffffff'>$descricao</td>";
		echo "<Td bgcolor='#ffffff'>$preco</td>";
		echo "</tr>";
		echo "</table>";
		
	}else{
		echo "<center>Nenhum resultado encontrado!</center>";
	}

}
?>

<? include "rodape.php"; ?>
