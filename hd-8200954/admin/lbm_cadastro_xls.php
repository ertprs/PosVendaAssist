<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

$layout_menu = "cadastro";
$title = "Lista Básica do Produto";


if($login_fabrica <> 11){
	header('Location: lbm_cadastro.php');
}

include 'cabecalho.php';

if(strlen($_POST['produto'])>0) $produto = $_POST['produto']; else  $produto = $_GET['produto'];

	if (strlen ($produto) > 0) {
			$sql = "SELECT produto, referencia, descricao FROM tbl_produto WHERE produto = $produto";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 0) {
				$msg_erro  = "Produto $referencia não cadastrado";
				$descricao = "";
				$produto   = "";
			}else{
				$descricao   = pg_result ($res,0,descricao);
				$referencia  = pg_result ($res,0,referencia);
				$produto     = pg_result ($res,0,produto);
			}
	}

	flush();

	echo "<br><br>";
	echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo "<tr>";
	echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
	echo "</tr>";
	echo "</table>";
	echo"<table  border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td>
			<img src='imagens/excell.gif'>
		</td>
		<td align='center'>
			<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font>
			<a href='xls/lista_basica_xls-$login_fabrica-$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>
				download do arquivo em EXCEL
				</font>
			</a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<BR>";
	echo "<table width='400' align='center' border='1'>";
	echo "<tr  bgcolor='#d9e2ef'>";
	echo "<td align='center'><FONT SIZE='2' FACE='Arial'><b>Referência</b></FONT></td>";
	echo "<td align='center'><FONT SIZE='2' FACE='Arial'><b>Descrição</b></FONT></td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td align='center'><FONT SIZE='2' FACE='Arial'>$referencia</FONT></td>";
		echo "<td align='center'><FONT SIZE='2' FACE='Arial'>$descricao</FONT></td>";
	echo "</tr>";
	echo "</table>";

	flush();
	
	$arquivo = "/var/www/assist/www/admin/xls/lista_basica_xls-$login_fabrica-$data.xls";
	$fp = fopen($arquivo, "w");

	fputs($fp, "<table width='400' align='center' border='1'>");
	fputs($fp, "<tr>");
	fputs($fp, "<td align='center' colspan='2'><b>Referência</b></td>");
	fputs($fp, "<td align='center' colspan='3'><b>Descrição</b></td>");
	fputs($fp, "</tr>");
	fputs($fp, "<tr>");
	fputs($fp, "<td align='center' colspan='2'>$referencia</td>");
	fputs($fp, "<td align='center' colspan='3'>$descricao</td>");
	fputs($fp, "</tr>");
	
	fputs($fp, "<tr>");
	fputs($fp, "<td colspan='5'>&nbsp;</td>");
	fputs($fp, "</tr>");

	fputs($fp, "<tr>");
	fputs($fp, "<td align='center'>Ordem </td>");
	fputs($fp, "<td align='center'>Posição </td>");
	fputs($fp, "<td align='center'>Peça </td>");
	fputs($fp, "<td align='center'>Descrição</td>");
	fputs($fp, "<td align='center'>Qtde</td>");
	fputs($fp, "</tr>");

	if (strlen ($produto) > 0) {
		$sql = "SELECT      tbl_lista_basica.lista_basica  ,
							tbl_lista_basica.posicao       ,
							tbl_lista_basica.ordem         ,
							tbl_lista_basica.serie_inicial ,
							tbl_lista_basica.serie_final   ,
							tbl_lista_basica.qtde          ,
							tbl_lista_basica.type          ,
							tbl_peca.referencia            ,
							tbl_peca.descricao
					FROM    tbl_lista_basica
					JOIN    tbl_peca USING (peca)
					WHERE   tbl_lista_basica.fabrica = $login_fabrica
					AND     tbl_lista_basica.produto = $produto
					ORDER BY tbl_peca.referencia, tbl_peca.descricao";
		$res = pg_exec ($con,$sql);
	
		for ($i=0; $i<pg_numrows($res); $i++){

		$ordem         = pg_result ($res,$i,ordem);
		$posicao       = pg_result ($res,$i,posicao);
		$peca          = pg_result ($res,$i,referencia);
		$descricao     = pg_result ($res,$i,descricao);
		$qtde          = pg_result ($res,$i,qtde);
	
		fputs($fp, "<tr>");
		fputs($fp, "<td align='center'>$ordem </td>");
		fputs($fp, "<td align='center'>$posicao </td>");
		fputs($fp, "<td align='center'>$peca </td>");
		fputs($fp, "<td align='center'>$descricao</td>");
		fputs($fp, "<td align='center'>$qtde</td>");
		fputs($fp, "</tr>");
		}
	fputs($fp, "</table>");
	fclose($fp);

	}

	include "rodape.php";
?>
