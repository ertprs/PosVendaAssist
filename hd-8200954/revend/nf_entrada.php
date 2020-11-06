<?
require '../dbconfig.php';
require '../includes/dbconnect-inc.php';

require 'autentica_revenda.php';
include '../ajax_cabecalho.php';

if(strlen($_POST['btnacao'])>0){
	$total = $_POST['total'];
	for ($i = 0 ; $i < $total ; $i++){
		if(isset($_POST["agrupada_$i"])){
			$os = $_POST["agrupada_$i"];
			$sql = "UPDATE tbl_os SET conferido_saida = TRUE WHERE os = $os";
			$res = pg_exec ($con,$sql);
			$sql = "SELECT nota_fiscal_saida FROM tbl_os WHERE os = $os";
			$res = pg_exec ($con,$sql);
			$nota = pg_result($res,0,0);
			$msg = "$nota<br>";
		}
	}
}


$aba = 4;
$title = "Recebimento de Notas Fiscais de Produto do posto";
include 'cabecalho.php';
echo "<br>";
if(isset($msg)) echo "<div id='ok' class='OK'>Notas conferidas com sucesso:<br>$msg</div>";
$sql = "SELECT distinct tbl_os.os,
				tbl_os.sua_os,
				tbl_os.nota_fiscal_saida,
				tbl_os.serie,
				TO_CHAR(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida,
				tbl_produto.produto,
				tbl_produto.referencia,
				tbl_produto.descricao,
				tbl_lote_revenda.lote_revenda,
				tbl_lote_revenda.lote,
				tbl_lote_revenda.nota_fiscal,
				TO_CHAR(tbl_lote_revenda.data_nf,'DD/MM/YYYY') AS data_nf,
				tbl_fabrica.nome
		FROM tbl_lote_revenda 
		JOIN tbl_fabrica         USING (fabrica)
		JOIN tbl_posto           USING (posto)
		JOIN tbl_os_revenda      USING (lote_revenda)
		JOIN tbl_os_revenda_item USING (os_revenda)
		JOIN tbl_os              ON tbl_os.os               = tbl_os_revenda_item.os_lote 
		JOIN tbl_produto         ON tbl_produto.produto     = tbl_os.produto
		JOIN tbl_posto_fabrica   ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_os.fabrica = $login_fabrica
		AND   tbl_lote_revenda.revenda = $login_revenda
		AND   tbl_os.nota_fiscal_saida IS NOT NULL
		AND   tbl_os.conferido_saida   IS NOT TRUE; ";

$res = pg_exec ($con,$sql);
$total = pg_numrows($res);
if(pg_numrows($res)>0){
	echo "<form name='nf_entrada' method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' value='$total' name='total'>";
	echo "<center><b>Produtos devolvidos pelo posto para confer?ncia</b></center>";
	echo "<table width='700' align='center' class='HD' cellpadding='0' cellspacing='0'>";
	echo "<tr class='Titulo'>";
	//echo "<td align='center'>&nbsp;</td>";
	echo "<td align='center'>OK</td>";
	echo "<td align='left'>Série</td>";
	echo "<td align='left'>Produto</td>";
	echo "<td align='left'>Fábrica</td>";
	echo "<td align='left'>NF Devolução</td>";
	echo "<td align='left'>Data <acronym title='Data da nota fiscal de devolução enviada pelo posto autorizado'>[?]</acronym></td>";
	echo "<td align='left'>Lote</td>";
//	echo "<td align='left'>NF Lote</td>";
//	echo "<td align='left'>Data</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$os                = trim(pg_result($res,$i,os));
		$sua_os            = trim(pg_result($res,$i,sua_os));
		$nota_fiscal_saida = trim(pg_result($res,$i,nota_fiscal_saida));
		$data_nf_saida     = trim(pg_result($res,$i,data_nf_saida));
		$lote_revenda      = trim(pg_result($res,$i,lote_revenda));
		$lote              = trim(pg_result($res,$i,lote));
		$nota_fiscal       = trim(pg_result($res,$i,nota_fiscal));
		$data_nf           = trim(pg_result($res,$i,data_nf));
		$produto           = trim(pg_result($res,$i,produto));
		$referencia        = trim(pg_result($res,$i,referencia));
		$descricao         = trim(pg_result($res,$i,descricao));
		$fabrica_nome      = trim(pg_result($res,$i,nome));
		$serie             = trim(pg_result($res,$i,serie));

		$cor = "#ffffff";
		if ($i % 2 == 0) $cor = "#FFEECC";

		echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";

		if (strlen ($conferencia) > 0) {
			$conferencia = "OK";
		}else{
			$conferencia = "--";
		}
		echo "<td align='left' nowrap>";
		echo "<input type='checkbox' name='agrupada_$i' value='$os'>" ;
		echo "</td>\n";
		echo "<td align='left' nowrap>$serie</td>\n";
		echo "<td align='left' nowrap>$descricao</td>\n";
		echo "<td align='left' nowrap>$fabrica_nome</td>\n";
		echo "<td align='left' nowrap>$nota_fiscal_saida</td>\n";
		echo "<td align='left' nowrap>$data_nf_saida</td>\n";
		echo "<td align='left' nowrap><a href=\"lote_consulta.php?lote_revenda=$lote_revenda&ajax=sim&acao=detalhes&ver=normal&TB_iframe=true&height=500&width=700\" class=\"thickbox\" id='LinkAjuda' title='Lote $lote - Nota Fiscal de Retorno $data_nf_saida'>$lote</a></td>\n";
//		echo "<td align='left' nowrap>$nota_fiscal</td>\n";
//		echo "<td align='left' nowrap>$data_nf</td>\n";
		echo "</tr>\n";
	}

	echo "</table>\n";
	echo "<input type='hidden' name='qtde_nf' value='$i'>";
	echo "<center><input type='submit' name='btnacao' value='Conferir Agrupado'></center>";
	echo "</form>";
}else{
	echo "<center>Nenhum produto devolvido!</center>";
}

include 'rodape.php';
?>