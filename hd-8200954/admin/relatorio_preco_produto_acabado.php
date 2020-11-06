<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";
include 'autentica_admin.php';

$admin_privilegios="gerencia";
$title = "RELATÓRIO DE PREÇOS DE APARELHOS";

include "cabecalho.php";
?>

<style type="text/css">
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
.espaco td{
	padding:10px 0 10px;
}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
}
</style>

<?
$sql = "SELECT  tbl_peca.referencia                        ,
				tbl_peca.descricao                         ,
				tbl_tabela.sigla_tabela                    ,
				tbl_tabela.descricao   AS descricao_tabela ,
				tbl_tabela_item.preco
		FROM tbl_tabela
		JOIN tbl_tabela_item ON tbl_tabela_item.tabela = tbl_tabela.tabela
		JOIN tbl_peca        ON tbl_peca.peca          = tbl_tabela_item.peca AND tbl_peca.fabrica = $login_fabrica
		WHERE tbl_tabela.fabrica = $login_fabrica
		AND   tbl_peca.produto_acabado IS TRUE
		GROUP BY tbl_peca.referencia ,
		tbl_peca.descricao           ,
		tbl_tabela.sigla_tabela      ,
		tbl_tabela.descricao         ,
		tbl_tabela_item.preco
		HAVING length(tbl_peca.descricao)>0
		ORDER BY tbl_peca.descricao";
#echo nl2br($sql);
$res = pg_exec($con,$sql);

if(pg_numrows($res)>0){

	$data = date("dmY");

	echo "<p id='id_download' style='display:none; padding:0;margin:0;'><img src='imagens/excell.gif'>
	<INPUT TYPE='button' value='Download em EXCEL' onclick=\"window.location='xls/relatorio-preco-produto-acabado-$data.xls'\">
	
	</p>";

	$arquivo_nome = "relatorio-preco-produto-acabado-$data.xls";
	$path         = "/www/assist/www/admin/xls/";
	$path_tmp     = "/tmp/assist/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	$fp = fopen($arquivo_completo_tmp,"w");

	fputs($fp,"<table width='100%' border='1' cellpadding='2'cellspacing='2' align='center'>\t");
	fputs($fp,"<tr>\t");
	fputs($fp,"<td align='center'>Refer&ecirc;ncia</td>\t");
	fputs($fp,"<td align='center'>Descri&ccedil;&atilde;o</td>\t");
	fputs($fp,"<td align='center'>Sigla Tabela</td>\t");
	fputs($fp,"<td align='center'>Descri&ccedil;&atilde;o Tabela</td>\t");
	fputs($fp,"<td align='center'>Pre&ccedil;o</td>\t");
	fputs($fp,"</tr>\t");


	echo "<table width='80%' border='0' cellpadding='0'cellspacing='1' align='center' class='tabela'>";
		echo "<tr class='titulo_coluna'>";
			echo "<td align='center'>Refer&ecirc;ncia</td>";
			echo "<td align='left'>Descri&ccedil;&atilde;o</td>";
			echo "<td align='center'>Sigla Tabela</td>";
			echo "<td align='center'>Descri&ccedil;&atilde;o Tabela</td>";
			echo "<td align='center'>Pre&ccedil;o</td>";
		echo "</tr>";
		for($i=0; $i<pg_numrows($res);$i++){
			#$peca                  = pg_result($res,$i,peca);
			$peca_referencia       = pg_result($res,$i,referencia);
			$peca_descricao        = pg_result($res,$i,descricao);
			$peca_tabela           = pg_result($res,$i,sigla_tabela);
			$peca_descricao_tabela = pg_result($res,$i,descricao_tabela);
			$peca_preco            = pg_result($res,$i,preco);

			$xpeca_preco = number_format($peca_preco, 2, ",",".");

			fputs($fp,"<tr>\t");
			fputs($fp,"<td>$peca_referencia</td>\t");
			fputs($fp,"<td align='left'>$peca_descricao</td>\t");
			fputs($fp,"<td align='center'>$peca_tabela</td>\t");
			fputs($fp,"<td align='center'>$peca_descricao_tabela</td>\t");
			fputs($fp,"<td>$xpeca_preco</td>\t");
			fputs($fp,"</tr>\t");

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				echo "<tr bgcolor='$cor'>";
					echo "<td>$peca_referencia</td>";
					echo "<td align='left'>$peca_descricao</td>";
					echo "<td>$peca_tabela</td>";
					echo "<td>$peca_descricao_tabela</td>";
					echo "<td align='right'>$xpeca_preco</td>";
				echo "</tr>";
		}
	echo "</table>";

	system("mv $arquivo_completo_tmp $arquivo_completo");

	echo "<script language='javascript'>";
	echo "document.getElementById('id_download').style.display='block';";
	echo "</script>";
	flush();
	echo "<br>";

	fputs($fp,"</table>");
	fclose ($fp);
}


















?>