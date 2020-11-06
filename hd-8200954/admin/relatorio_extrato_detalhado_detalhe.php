<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$linha        = $_GET[linha];
if(strlen($linha)>0){
	$cond_1 = "AND   tbl_produto.linha   = $linha ";
}
$data_ini     = $_GET[data_ini];
$data_fim     = $_GET[data_final];
$codigo_posto = $_GET[cod_posto];


$sql = "SELECT	tbl_posto_fabrica.codigo_posto ,
				tbl_familia.descricao ,
				tbl_posto.nome ,
				sum(tbl_os.pecas::numeric(8,2)) AS total_pecas ,
				tbl_extrato.total::numeric(8,2) AS total_total ,
				sum(tbl_os.qtde_km_calculada::numeric(8,2)) AS total_km ,
				sum(tbl_os.mao_de_obra::numeric(8,2)) AS total_mo ,
				tbl_extrato.extrato,
				tbl_extrato.data_geracao,
				TO_CHAR (tbl_extrato.data_geracao , 'dd/mm/yyyy') AS data
			FROM tbl_extrato
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_posto         ON tbl_posto.posto          = tbl_extrato.posto
			JOIN tbl_os_extra      ON tbl_os_extra.extrato     = tbl_extrato.extrato
			JOIN tbl_os            ON tbl_os.os                = tbl_os_extra.os
			JOIN tbl_produto       ON tbl_os.produto           = tbl_produto.produto
			JOIN tbl_familia       ON tbl_familia.familia      = tbl_produto.familia
			WHERE tbl_extrato.fabrica=$login_fabrica AND   tbl_extrato.data_geracao BETWEEN '$data_ini' AND '$data_fim' AND tbl_posto_fabrica.codigo_posto='$codigo_posto'
			$cond_1
			GROUP BY
				tbl_posto_fabrica.codigo_posto ,
				tbl_familia.descricao ,
				tbl_posto.nome ,
				tbl_extrato.total ,
				tbl_extrato.extrato,
				tbl_extrato.data_geracao,
				TO_CHAR (tbl_extrato.data_geracao , 'dd/mm/yyyy')
			ORDER BY tbl_extrato.data_geracao
			";
//echo nl2br($sql);
//exit;
$res = pg_exec($con,$sql);

$posto_nome   = trim(pg_result($res,$x,nome));
$codigo_posto = trim(pg_result($res,$x,codigo_posto));
?>
<html>
	<head>
		<title> Detalhe...</title>
		<style type="text/css">
			table, td{
				font:100% Arial, Helvetica, sans-serif;
			}
			table{width:800px;border-collapse:collapse;margin:1em 0;}
			th, td{text-align:left;padding:.5em;border:1px solid #fff;}
			th{background:#328aa4 url(tr_back.gif) repeat-x;color:#fff;}
			td{background:#e5f1f4;
			}

			/* tablecloth styles */

			tr.even td{background:#e5f1f4;}
			tr.odd td{background:#f8fbfc;}

			th.over, tr.even th.over, tr.odd th.over{background:#4a98af;}
			th.down, tr.even th.down, tr.odd th.down{background:#bce774;}
			th.selected, tr.even th.selected, tr.odd th.selected{}

			td.over, tr.even td.over, tr.odd td.over{background:#ecfbd4;}
			td.down, tr.even td.down, tr.odd td.down{background:#bce774;color:#fff;}
			td.selected, tr.even td.selected, tr.odd td.selected{background:#bce774;color:#555;}

			/* use this if you want to apply different styleing to empty table cells*/
			td.empty, tr.odd td.empty, tr.even td.empty{background:#fff;}
		</style>
	</head>
	<body>
		<center>
		<table>
			<tr>
				<th colspan=6>Posto: <?=$posto_nome;?> - <?=$codigo_posto;?></th>
			</tr>
			<tr>
				<th>Data Extrato</th><th>Família</th><th>KM</th><th>Peças</th><th>Mão de Obra</th><th>Total</th>
			</tr>
			<?
			if(pg_numrows($res)>0){
				for($x=0;$x<pg_numrows($res);$x++){
					$familia = trim(pg_result($res,$x,descricao));
					$km = trim(pg_result($res,$x,total_km));
					$pecas = trim(pg_result($res,$x,total_pecas));
					$mao_obra = trim(pg_result($res,$x,total_mo));
					$data_extrato = trim(pg_result($res,$x,data));
					$total = $km + $pecas + $mao_obra;
					?>
					<tr>
					<td><?=$data_extrato;?></td>
					<td><?=$familia;?></td>
					<td><?=$km;?></td>
					<td><?=$pecas;?></td>
					<td><?=$mao_obra;?></td>
					<td><?=$total;?></td>
					</tr>
					<?
				}
			}
			?>
		</table>
		</center>
		<center> <a href="javascript:history.back(1)">Voltar</a> </center>
	</body>
</html>
