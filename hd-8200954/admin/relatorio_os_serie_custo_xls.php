<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'includes/funcoes.php';
	include "autentica_admin.php";
	if(isset($_GET['gerar']) && strlen($_GET['gerar']) > 0) {

		$data_inicial	 = $_GET['data_inicial'];
		$data_final		 = $_GET['data_final'];
		$situacao		 = $_GET['situacao_os'];
		$estado			 = $_GET['estado'];
		$prod_referencia = $_GET['produto_referencia'];

		if( strlen($data_inicial) > 0 && strlen($data_final) > 0 ){

			if( $_GET['filtro_data'] == 'abertura' || strlen($_GET['filtro_data']) ==0 )
					$campo = 'data_abertura';
			else if( $_GET['filtro_data'] == 'digitacao' ) {
				$campo			= 'data_digitacao';
				$data_inicial	= $data_inicial;
				$data_final		= $data_final;
			}
			else if ( $_GET['filtro_data'] == 'finaliza' )
				$campo = 'data_fechamento';

			if( $data_inicial != $data_final )
				$cond_data = "AND tbl_os.".$campo." BETWEEN '".$data_inicial."' AND '".$data_final."'";
			else
				$cond_data = "AND tbl_os.".$campo." = '".$data_inicial."'";
		}
		else
			$msg_erro = "Data Inválida";

		if($situacao_os == 'finalizada')
			$cond_situacao = 'AND tbl_os.finalizada IS NOT NULL';

		if( strlen($_GET['codigo_posto']) > 0 ) {
			$sql_posto = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '".$_GET['codigo_posto']."'";
			$res_posto = pg_query($con,$sql_posto);
			if( pg_numrows($res_posto) ) {
				$cod_posto = pg_result($res_posto,0,posto);
				$cond_posto  = ' AND tbl_os.posto = ' . $cod_posto . '';
			}
			else
				$msg_erro = 'Posto Não Encontrado';
		}
		else
			$cond_posto = 'AND tbl_os.posto NOT IN (6359)';

		if( strlen($prod_referencia) > 0 ) {

			$sql_prod = "SELECT produto 
						 FROM tbl_produto 
						 JOIN tbl_linha USING (linha)
						 WHERE referencia = '".$prod_referencia."'
						 AND fabrica = ".$login_fabrica."";
			$res_prod = pg_query($con,$sql_prod);

			if( pg_num_rows($res_prod) > 0 ) {

				$produto	= pg_result($res_prod, 0, 'produto');
				$cond_prod	= 'AND tbl_os.produto = '.$produto;

			}
			else
				$msg_erro = 'Produto Não Encontrado.';

		}

		if(strlen($estado)>0)
			$cond_regiao = "AND tbl_posto.estado = '".$estado."'";
	}
?>
<?php if(strlen($msg_erro) > 0) { ?>
	<div class="msg_erro" style="width:700px; margin:auto;"><?php echo $msg_erro; ?></div>
<?php } ?>
<?php 
	if ( isset($_GET['gerar']) && strlen($msg_erro) == 0 ) {
		$sql = "
			SELECT	serie,
			tbl_produto.referencia || '-' || tbl_produto.descricao as produto, 
			tbl_os.defeito_reclamado_descricao, 
			tbl_defeito_constatado.descricao, 
			os, 
			tbl_posto.nome, 
			tbl_os.mao_de_obra + tbl_os_extra.taxa_visita + qtde_km_calculada as mao_de_obra,
			pecas
			FROM tbl_os
			JOIN tbl_produto USING(produto)
			JOIN tbl_defeito_constatado USING(defeito_constatado)
			JOIN tbl_posto USING(posto)
			JOIN tbl_os_extra USING (os)
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = ".$login_fabrica."
			WHERE tbl_os.fabrica = ".$login_fabrica."
			$cond_posto
			$cond_data
			$cond_situacao
			$cond_regiao
			$cond_prod
			AND tbl_os.excluida IS NOT NULL
		";
		//die($sql);
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0) { //exibe os dados

			$data = date ('Y-m-d');
			$arq      = "/var/www/assist/www/admin/xls/relatorio-os-serie-custo-$login_fabrica-$data.xls";
			$arq_html = "/tmp/assist/relatorio-os-serie-custo-$login_fabrica.html";
			if(file_exists($arq_html))
				exec ("rm -f $arq_html");
			if(file_exists($arq))
				exec ("rm -f $arq");
			$fp = fopen($arq_html,"w");
			fputs($fp, '
					<table cellspacing="1" border="1">
						<thead>
							<tr>
								<th>Nº Série</th>
								<th>Descrição</th>
								<th>Defeito Reclamado</th>
								<th>Defeito Constatado</th>
								<th>Nº da OS</th>
								<th>Posto</th>
								<th align="right">Custo M.O</th>
								<th align="right">Custo Peças</th>
								<th align="right">Total M.O + Peças</th>
							</tr>
						</thead>
			<tbody>');
			//loop results
			for($i = 0; $i < pg_num_rows($res); $i++) {
				$serie			= pg_result($res,$i,'serie');
				$produto		= pg_result($res,$i,'produto');
				$def_reclamado	= pg_result($res,$i,'defeito_reclamado_descricao');
				$def_descricao	= pg_result($res,$i,'descricao');
				$os				= pg_result($res,$i,'os');
				$posto_nome		= pg_result($res,$i,'nome');
				$mao_obra		= pg_result($res,$i,'mao_de_obra');
				$peca			= pg_result($res,$i,'pecas');
				$total          = $peca + $mao_obra;
				$total_peca		+= $peca;
				$total_mao_obra += $mao_obra;
				fputs($fp, '
				<tr>
					<td align="left">'.$serie.'</td>
					<td>'.$produto.'</td>
					<td>'.$def_reclamado.'</td>
					<td>'.$def_descricao.'</td>
					<td>'.$os.'</td>
					<td>'.$posto_nome.'</td>
					<td align="right">'.number_format($mao_obra,2,',','.').'</td>
					<td align="right">'.number_format($peca,2,',','.').'</td>
					<td align="right">'.number_format($total,2,',','.').'</td>
				</tr>
				');
			}
			$total_geral = $total_mao_obra + $total_peca;
			fputs($fp, "
					<tr>
						<td colspan='5'>&nbsp;</td>
						<td>".number_format($total_mao_obra,2,',','.')."</td>
						<td>".number_format($total_peca,2,',','.')."</td>
						<td>".number_format($total_geral,2,',','.')."</td>
					</tr>
					</tbody>
				</table>
			</html>" . PHP_EOL);
			rename($arq_html, $arq);
			header("Location:xls/relatorio-os-serie-custo-$login_fabrica-$data.xls");

		}
		else
			echo 'Não Foram Encontrados Resultados para esta Pesquisa.';
	}
?>
