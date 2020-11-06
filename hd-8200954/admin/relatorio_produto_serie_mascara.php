<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include 'autentica_admin.php';

$title = "Relatório de Mascara de Número de Série";
include 'cabecalho.php';
?>

<style>
td{
font-size: 11px;
font-family: arial;
}
.Titulo{
background-color:#5D92B1;
font-size: 12px;
font-family: arial;
color:#FFFFFF;
background-image: url('imagens_admin/azul.gif');
}
</style>

<? include "javascript_pesquisas.php"; ?>

<SCRIPT LANGUAGE="JavaScript">
<!--
	function fnc_pesquisa_produto (campo, campo2, tipo) {
		if (tipo == "referencia" ) {
			var xcampo = campo;
		}

		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}


		if (xcampo.value != "") {
			var url = "";
			url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
			janela.referencia	= campo;
			janela.descricao	= campo2;
			janela.focus();
		}

		else{
			alert("Preencha toda ou parte da informação para realizar a pesquisa!");
		}
	}
//-->
</SCRIPT>

<BR>
<FORM METHOD="POST" NAME="frm_relatorio" ACTION="<? $PHP_SELF; ?>">
	<table width='500' class='Conteudo' style='background-color: #D9E2EF;' border='0' cellpadding="2" cellspacing="2" align='center'>
		<tr>
			<td background='imagens_admin/azul.gif' align="center" colspan="5">
				<span style="font-family: Arial; font-size: 13px; font-weight: bold; color: #FFFFFF;">Consulta por produto</span>
			</td>
		</tr>
		<tr bgcolor="#D9E2EF">
			<td align="left">
				<span>Referência<br>
				<input type="text" name="produto_referencia" value="<? echo $produto_referencia ?>" size="15" maxlength="20" class='frm'>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia,document.frm_relatorio.produto_descricao,'referencia')" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'></span>
			</td>
			<td align="left" nowrap>
				<span>Descrição<br>
				<input type="text" name="produto_descricao" value="<? echo $produto_descricao ?>" size="45" maxlength="50" class='frm'>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia,document.frm_relatorio.produto_descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'></span>
			</td>
		</tr>
		<tr bgcolor="#D9E2EF">
			<td colspan="2" align="center" >
			<INPUT TYPE="hidden" NAME="btn_acao">
			<img border="0" src="imagens/btn_pesquisar_400.gif" onClick="document.frm_relatorio.btn_acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
		</tr>
	</table>
</FORM>

<?

$produto_referencia = $_POST['produto_referencia'];

if(strlen($produto_referencia)>0){
	$cond_produto = "AND tbl_produto.referencia = '$produto_referencia'";
}else{
	$cond_produto = "AND 1=1";
}

$sql = "SELECT DISTINCT tbl_produto.produto,
				tbl_produto.referencia     ,
				tbl_produto.descricao      ,
				tbl_produto.ativo
		FROM tbl_produto
		JOIN tbl_linha USING(linha)
		JOIN tbl_produto_valida_serie USING(produto)
		WHERE tbl_linha.fabrica = $login_fabrica
		$cond_produto
		ORDER BY tbl_produto.referencia";
$res  = pg_query($con, $sql);
$xres = pg_num_rows($res);

if(pg_num_rows($res)>0){
	echo "<br><BR>";
	echo "<table width='750' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr class='Titulo'>";
			echo "<td>Status</td>";
			echo "<td>Referência</td>";
			echo "<td>Descrição</td>";
			echo "<td colspan='100%'>Mascara</td>";
		echo "</tr>";

	for($i=0; $i<pg_num_rows($res); $i++){
		$produto    = pg_fetch_result($res,$i,produto);
		$referencia = pg_fetch_result($res,$i,referencia);
		$descricao  = pg_fetch_result($res,$i,descricao);
		$ativo      = pg_fetch_result($res,$i,ativo);

		if($i%2==0) $cor = "#D9E2EF";
		else        $cor = "#F7F7F7";

		echo "<tr bgcolor='$cor'>";
			echo "<td align='center'>";
			echo ($ativo <> 't') ?"<img src='imagens_admin/status_vermelho.gif' border='0' alt='Inativo'>" : "<img src='imagens_admin/status_verde.gif' border='0' alt='Ativo'>";
			echo "</td>";
			echo "<td align='left'>$referencia</td>";
			echo "<td align='left' nowrap>$descricao</td>";

			$sqlM = "SELECT tbl_produto_valida_serie.mascara
					FROM tbl_produto_valida_serie
					WHERE tbl_produto_valida_serie.produto = $produto
					and   fabrica = $login_fabrica
					ORDER BY mascara";
			$resM = pg_query($con, $sqlM);

			if(pg_num_rows($resM)>0){
				for($x=0; $x<pg_num_rows($resM); $x++){
					$mascara = pg_fetch_result($resM,$x,mascara);
					echo "<td style='text-align:left; font-size: 14px'>$mascara</td>";
				}
			}

		echo "</tr>";
	}
	echo "</table>";
}else{
	echo "<P align='center'>Nenhum resultado encontrado!</P>";
}

?>
<? include "rodape.php"; ?>
