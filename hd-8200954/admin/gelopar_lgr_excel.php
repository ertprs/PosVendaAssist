<?php 

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

system('rm xls/relatorio_faturamento_2011_lgr_gelopar.xls');
$fp = fopen ("xls/relatorio_faturamento_2011_lgr_gelopar.xls","w+");

			fputs ($fp,"<html>");
			fputs ($fp,"<head>");
			fputs ($fp,"<title>RELATÓRIO DE FATURAMENTO DE LGR 2011 - GELOPAR ");
			fputs ($fp,"</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs ($fp,"</head>");
			fputs ($fp,"<body>");
			

$sql = "SELECT posto,codigo_posto,nome_fantasia from tbl_posto_fabrica where fabrica=85 and credenciamento in ('CREDENCIADO','EM DESCREDENCIAMENTO')";
//echo nl2br($sql);
$res = pg_query($con,$sql);

for ($i = 0; $i < pg_num_rows($res); $i++){
	
	$posto = pg_result($res,$i,'posto');
	$codigo_posto = pg_result($res,$i,'codigo_posto');
	$nome_fantasia = pg_result($res,$i,'nome_fantasia');
	
	fputs ($fp,"<table>");
	fputs ($fp,"
	
		<tr class=\"titulo_tabela\">
			<td>
				<h2>$codigo_posto - $nome_fantasia</h2><br>
			</td>
		</tr>
		<tr>
			<td>
				<table class=\"tabela\" cellpadding=\"0\" cellspacing=\"1\">
					<tr class=\"titulo_coluna\">
						<td>Peça Referencia</td>
						<td>Peça Descricao</td>
						<td>Qtde.</td>
						<td>Nota Fiscal</td>
						<td>Emissão</td>
						<td>OS</td>
					</tr>
					
	");		
	
	$sql_posto_lgr = "
		SELECT  tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_faturamento_item.qtde,
				tbl_faturamento.nota_fiscal,
				TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') as emissao,
				tbl_os.sua_os
		
		FROM tbl_faturamento 
		
		JOIN tbl_faturamento_item on tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
		JOIN tbl_peca on tbl_faturamento_item.peca = tbl_peca.peca
		JOIN tbl_os_item on tbl_faturamento_item.pedido = tbl_os_item.pedido 
		JOIN tbl_os_produto on tbl_os_item.os_produto = tbl_os_produto.os_produto
		JOIN tbl_os on tbl_os_produto.os = tbl_os.os
		JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os
		
		WHERE tbl_faturamento.fabrica = $login_fabrica 
		AND tbl_faturamento.emissao between '2011-01-01' and '2011-12-31' 
		AND tbl_os.excluida is not true 
		AND tbl_os.finalizada is not null
		and tbl_faturamento.fabrica = $login_fabrica
		AND tbl_faturamento.posto = $posto 
		AND tbl_peca.devolucao_obrigatoria is true
		and tbl_os_item.fabrica_i = $login_fabrica 
		and tbl_os.fabrica = $login_fabrica
		AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
	";
	// echo nl2br($sql_posto_lgr);exit;
	$res_posto_lgr = pg_query($con,$sql_posto_lgr);


	
	for ($x = 0;$x < pg_num_rows($res_posto_lgr);$x++){
		$peca_referencia = pg_result($res_posto_lgr,$x,'referencia');
		$peca_descricao = pg_result($res_posto_lgr,$x,'descricao');
		$qtde = pg_result($res_posto_lgr,$x,'qtde');
		$nota_fiscal = pg_result($res_posto_lgr,$x,'nota_fiscal');
		$emissao = pg_result($res_posto_lgr,$x,'emissao');
		$sua_os = pg_result($res_posto_lgr,$x,'sua_os');
		
		fputs ($fp,"
				<tr>
					<td align='left'>
						$peca_referencia
					</td>
					<td align='left'>
						$peca_descricao
					</td>
					<td align='left'>
						$qtde
					</td>
					<td align='left'>
						$nota_fiscal
					</td>
					<td align='left'>
						$emissao
					</td>
					<td align='left'>
						$sua_os
					</td>
				</tr>
		");

	}
	fputs ($fp,"		
				</table>
				
			</td>
			</tr>

			</table>
			<br>
			<br>
	");

}
fputs ($fp,"</body>");
fputs ($fp,"</html>");
fclose ($fp);

echo "<div align='center'>";
	echo "<input type='button' value='Download em Excel' onclick='window.location.href=\"xls/relatorio_faturamento_2011_lgr_gelopar.xls\"' >";
echo "</div>";