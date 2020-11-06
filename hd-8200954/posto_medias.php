<?php ob_start(); ?>
<style type='text/css'>
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
</style>
<?
$headerHTML .= ob_get_clean();
$prazo_arend ='Prazo médio de Fechamento de OS nos últimos 30 dias';
$reinci = 'Porcentagem de OS abertas nos últimos 30 dias com Reincidêndia de até 90 dias';
$comuni = 'Porcentagem de Comunicados do tipo "Comunicado de não conformidade" recebidos pelo Posto em relação as OS abertas nos últimos 30 dias';

$sql = "SELECT * FROM tbl_indicador";
$res = pg_exec($con,$sql);
for($i=0;$i < pg_numrows($res);$i++){
	$campo = pg_result($res,$i,campo);
	if($campo == 'media_atend_mes'){
		$meta1 = pg_result($res,$i,meta);
	}
	if($campo == 'reincidencia'){
		$meta2 = pg_result($res,$i,meta);
	}
	if($campo == 'reclamacoes'){
		$meta3 = pg_result($res,$i,meta);			
	}
	
}

$sql = "SELECT MAX(data_geracao) AS data FROM tbl_posto_media_nova_computadores";
$res = pg_exec($con,$sql);		
$data = pg_result($res,0,data);
if (!empty($data)) {
	list($y,$m,$d) = explode('-',$data);
	$nova_data = $d.'/'.$m.'/'.$y;
	$sql = "SELECT tbl_posto_media_nova_computadores.* 
		FROM tbl_posto_media_nova_computadores
		WHERE tbl_posto_media_nova_computadores.fabrica = $login_fabrica
		AND tbl_posto_media_nova_computadores.posto = $login_posto
		AND tbl_posto_media_nova_computadores.data_geracao = '$data'";
	$res = pg_query($con,$sql);
	$total = pg_numrows($res);

	if ($total > 0) { ?>
	<br />
	<table align='center' class='tabela' cellspacing='1' width='760'>
		<caption class='titulo_coluna'>
			<span style='font-size:14px;'>ANÁLISE DE INDICADORES</span> <br> Última atualização: <?php echo $nova_data; ?>
		</caption>
<?php
		$qtde_aberta_30             = pg_result($res,0,qtde_aberta_30);
		$qtde_finalizadas_30        = pg_result($res,0,qtde_finalizadas_30);
		$porc_finalizada_20         = pg_result($res,0,porc_finalizada_20);
		$qtde_media_dias_finalizada = pg_result($res,0,qtde_media_dias_finalizada);
		$qtde_aberta                = pg_result($res,0,qtde_aberta);
		$qtde_aberta_20             = pg_result($res,0,qtde_aberta_20);
		$media_atend_mes            = pg_result($res,0,media_atend_mes);
		$qtde_os_reincidente_90     = pg_result($res,0,qtde_os_reincidente_90);
		$reincidencia               = pg_result($res,0,reincidencia);
		$qtde_os_sem_peca_2         = pg_result($res,0,qtde_os_sem_peca_2);
		$porc_os_sem_peca_2         = pg_result($res,0,porc_os_sem_peca_2);
		$media_peca_os_30           = pg_result($res,0,media_peca_os_30);
		$qtde_os_interv_30          = pg_result($res,0,qtde_os_interv_30);
		$porc_os_interv_30          = pg_result($res,0,porc_os_interv_30);
		$reclamacoes                = pg_result($res,0,reclamacoes);
		$nota1                      = pg_result($res,0,nota1);
		$nota2                      = pg_result($res,0,nota2);
		$nota3                      = pg_result($res,0,nota3);
		$nota_final                 = pg_result($res,0,nota_final);
		$ranking                    = pg_result($res,0,ranking);
		$data_geracao               = pg_result($res,0,data_geracao);
?>
		<thead>
			<tr class='titulo_coluna'>
				<th>Indicador</th> <th>Valor</th> <th>Meta</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><span title='<?= $prazo_arend;?>'>Prazo médio de atendimento (1 Mês)</span></td>
				<td align='center'><span title='<?= $prazo_arend;?>'><?php echo $qtde_media_dias_finalizada; ?></span></td>
				<td><span title='<?= $prazo_arend;?>'><?php echo $meta1; ?></span></td>
			</tr>
			<tr>
				<td><span title='<?= $reinci;?>'>Porcentagem de OS Reincidente 90 dias</span></td>
				<td align='center'><span title='<?= $reinci;?>'><?php echo number_format($reincidencia,2,',','.'); ?></span></td>
				<td><span title='<?= $reinci;?>'><?php echo $meta2; ?></span></td>
			</tr>
			<tr>
				<td><span title='<?= $comuni;?>'>Porcentagem de Comunicado de não conformidade </span></td>
				<td align='center'><span title='<?= $comuni;?>'><?php echo $reclamacoes; ?></span></td>
				<td><span title='<?= $comuni;?>'><?php echo $meta3; ?></span></td>
			</tr>
			
			
			<tr class='titulo_coluna'>
				<th><span title='Nota obtida após média calculada sobre os três indicadores'>Nota Final</span></th>
				<th colspan='2'><span title='Posição atual no Ranking de Avaliação'>Ranking</span></th>
			</tr>
			<tr bgcolor='#F7F5F0'>
				<td align='center'><?php echo number_format($nota_final,2,',','.'); ?></td>
				<td align='center' colspan='2'><?php echo $ranking; ?>º</td>
			</tr>
		</tbody>
	</table>
	<br />
<?php
	}
}

