<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
$layout_menu = "gerencia";

$title = "DETALHE DE OS DE CORTESIA COMERCIAL";

//$sql = "SELECT nome_completo, admin from tbl_admin where admin='$_GET[admin]'";
//$res = pg_exec ($con,$sql);
//$admin    = trim(pg_result($res,0,nome_completo))   ;

$sql = "select tbl_escritorio_regional.descricao as escritorio,
TO_CHAR (tbl_os.data_digitacao,'yyyy-mm') AS data_digitacao,
tbl_os.os as os,
tbl_os.pecas as pecas,
tbl_os.mao_de_obra as mao_de_obra 
into temp tmp_os_cortesia
FROM tbl_os
JOIN tbl_os_status USING (os)
JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
JOIN tbl_promotor_treinamento ON tbl_promotor_treinamento.promotor_treinamento = tbl_os.promotor_treinamento
JOIN tbl_escritorio_regional ON tbl_promotor_treinamento.escritorio_regional = tbl_escritorio_regional.escritorio_regional
WHERE tbl_os.fabrica = 20
AND tbl_os_status.status_os = 93
AND tbl_os.tipo_atendimento = 16
AND tbl_os.data_digitacao BETWEEN '{$_GET[data_ini]} 00:00:00' AND '{$_GET[data_fim]} 23:59:59';

select distinct data_digitacao, 
escritorio, 
sum (pecas + mao_de_obra) as valor ,
count(os) as total_os
from tmp_os_cortesia
GROUP BY escritorio,
data_digitacao order by data_digitacao asc";

//echo nl2br($sql);
//exit;
$res1         = pg_exec ($con,$sql);
$aDados       = array();
$aEscritorios = array();

// seleciona os escritorios
$sql = "select distinct escritorio from tmp_os_cortesia";
$res2= pg_exec($con,$sql);
$rows = pg_numrows($res2);
if ( $rows > 0 ) {
	while ( $row = pg_fetch_assoc($res2) ) {
		$aEscritorios[$row['escritorio']] = $row['escritorio'];
	}
}

while ($row = pg_fetch_assoc($res1)) {
	$mes        = $row['data_digitacao'];
	$escritorio = $row['escritorio'];
	$ano = substr($row['data_digitacao'],2,2);

	if ( ! isset($aDados[$mes]) ) {
		// se nao existe esse mes, coloca os escritorios como default;
		$aDados[$mes] = array();
		$aDados[$mes] = $aEscritorios;
	}
	$aDados[$mes][$escritorio] = $row;
}
//echo "<pre>",print_r($aDados),"</pre>";
?>
<html>
	<head>
		<title> Detalhe de Os Cortesia Comercial</title>
		<style>
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
		</style>
	</head>
	<body>
		<?
		include "cabecalho.php";
		?>
		<center>
		<BR><BR>
		
		<table width="700" align="center" class="tabela" cellspacing="1">
			<?php foreach ($aDados as $mes=>$linha): ?>
			<?
				$mes = substr($mes,5,2);
				if($mes=='01')$mes_m = "Jan";
				if($mes=='02')$mes_m = "Fev";
				if($mes=='03')$mes_m = "Mar";
				if($mes=='04')$mes_m = "Abr";
				if($mes=='05')$mes_m = "Mai";
				if($mes=='06')$mes_m = "Jun";
				if($mes=='07')$mes_m = "Jul";
				if($mes=='08')$mes_m = "Ago";
				if($mes=='09')$mes_m = "Set";
				if($mes=='10')$mes_m = "Out";
				if($mes=='11')$mes_m = "Nov";
				if($mes=='12')$mes_m = "Dec";			
			?>
				<tr>
					<td width="150px" rowspan=2 align='center' class="titulo_tabela"> &nbsp; </td>
					<td colspan="<?php echo count($aEscritorios); ?>" class="titulo_coluna" align='center'>
						<b><?php echo $mes_m."-".$ano; ?></b>
					</td>
				</tr>
				<tr>
					<?php foreach ($aEscritorios as $escritorio): ?>
						<td bgcolor="#FFFFFF" width="100px" class="titulo_coluna"> <?php echo $escritorio; ?> </td>
					<?php endforeach; ?>
				</tr>
				<tr>
					<td class="titulo_coluna"> Qtde OS's </td>
					<?php foreach ($aEscritorios as $escritorio): ?>
						<td bgcolor="#F7F5F0"> <?php echo ( is_array($linha[$escritorio]) ) ? $linha[$escritorio]['total_os'] : '&nbsp;' ; ?> </td>
					<?php endforeach; ?>
				</tr>
				<tr>
					<td class="titulo_coluna"> Valor em Reais </td>
					<?php foreach ($aEscritorios as $escritorio): ?>
						<td bgcolor="#F1F4FA"> 
							<?php echo ( is_array($linha[$escritorio]) ) ? $linha[$escritorio]['valor'] : '&nbsp;' ; ?> 
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</table>
		</center>
	</body>
</html>