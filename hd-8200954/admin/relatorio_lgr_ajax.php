<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$extrato = $_GET['extrato'];

if(in_array($login_fabrica, array(153))){
	$sql = "
			SELECT DISTINCT
			   tbl_peca.referencia,
			   tbl_peca.descricao, 
			   tbl_extrato_lgr.qtde,
			   tbl_faturamento_item.preco,
			   (tbl_extrato_lgr.qtde * tbl_faturamento_item.preco) AS total
			FROM
			   tbl_extrato_lgr
			JOIN tbl_faturamento_item ON tbl_faturamento_item.extrato_devolucao = tbl_extrato_lgr.extrato AND tbl_faturamento_item.peca = tbl_extrato_lgr.peca
			JOIN tbl_peca ON tbl_peca.peca = tbl_extrato_lgr.peca AND tbl_peca.fabrica = $login_fabrica
			WHERE
			   tbl_extrato_lgr.extrato = $extrato
	";
	//echo $sql; 
	$res = pg_query($con, $sql);
	if(pg_num_rows($res) > 0)
	{
		echo "$extrato|";

		$thead = "";
		$tbody = "";
		$tfooter = "";
		$total_nf = 0;

		$thead .= "
			<table class='table table-striped table-bordered table-fixed'>
				<thead>
					<tr class = 'titulo_coluna'>
						<th>Código</th>
						<th>Descrição</th>
						<th>Qtde.</th>
						<th>Preço</th>
						<th>Total</th>
					</tr>
				</thead>
				<tbody>
		";

		for($i = 0; $i < pg_num_rows($res); $i++){
			$referencia = trim(pg_result($res, $i, referencia));
			$descricao = trim(pg_result($res, $i, descricao));
			$qtde      = trim(pg_result($res, $i, qtde));
			$preco     = trim(pg_result($res, $i, preco));
			$total     = trim(pg_result($res, $i, total));

			$total_nf += $total;

			$preco = number_format($preco,2,",",".");
			$total = number_format($total,2,",",".");
			
			$tbody .= "
				<tr>
					<td class='tac'>{$referencia}</td>
					<td class='tal'>{$descricao}</td>
					<td class='tac'>{$qtde}</td>
					<td class='tac'>R$ {$preco}</td>
					<td class='tac'>R$ {$total}</td>
				</tr>
			";
		}

		$total_nf = number_format($total_nf,2,",",".");

		$sql2 = "
			SELECT DISTINCT
			   tbl_faturamento.nota_fiscal
			FROM
			   tbl_extrato_lgr
			JOIN tbl_faturamento_item ON tbl_faturamento_item.extrato_devolucao = tbl_extrato_lgr.extrato AND tbl_faturamento_item.peca = tbl_extrato_lgr.peca
			JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
			WHERE
			   tbl_extrato_lgr.extrato = $extrato;
		";

		$res2 = pg_query($con, $sql2);
		$numero_nf = "";

		for($x = 0; $x < pg_num_rows($res2); $x++){
			$auxiliar_numero_nf = trim(pg_result($res2, $x, nota_fiscal));
			if(($x + 1) >= pg_num_rows($res2)){
				$numero_nf .= "$auxiliar_numero_nf";
			}else{
				$numero_nf .= "$auxiliar_numero_nf, ";
			}
		}

		$tfooter .= "
				<tr class = 'titulo_coluna'>
					<th colspan='3' class='tal'>Referente as NF's {$numero_nf}</th>
					<th class='tar'>Total da Nota</th>
					<th class='tal'>R$ {$total_nf}</th>
				</tr>
			</tbody>
			</table>
		";

		echo $thead;
		echo $tbody;
		echo $tfooter;
	}
}
?>