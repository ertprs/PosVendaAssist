<?php
	/**
	 *	@description Relatorio Pesquisa de Satisfação - HD 674943 e 720502
	 *  @author Brayan L. Rastelli.
	 **/
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include "autentica_admin.php";
?>
<html>
	<head>
		<title>RELATÓRIO DE ATENDIMENTOS - ORIENTAÇÃO DE USO</title>
		<style type="text/css">
			.download {
				width:200px;
				margin:auto;
			}
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
			.msg_erro{
				background-color:#FF0000;
				font: bold 14px "Arial";
				color:#FFFFFF;
				text-align:center;
			}
			.formulario{
				background-color:#D9E2EF;
				font:11px Arial;
				text-align:left;
			}
			button.download { margin-top : 15px; }
			table.form tr td{
				padding:10px 30px 0 0;
			}
			table.tabela tr td{
				font-family: verdana;
				font-size: 11px;
				border-collapse: collapse;
				border:1px solid #596d9b;
			}
			.texto_avulso{
			    font: 14px Arial; color: rgb(89, 109, 155);
			    background-color: #d9e2ef;
			    text-align: center;
			    width:700px;
			    margin: 10px auto;
			    border-collapse: collapse;
			    border:1px solid #596d9b;
			}
			div.formulario table.form{
				padding:10px 0 10px 60px;
				text-align:left;
			}
			
			div.formulario form p{ margin:0; padding:0; }
		</style>
	</head>
	<body>

<?php

	if ( $_GET['tela'] ) {

		$aux_data_inicial = $_GET['data_inicial'];
		$aux_data_final = $_GET['data_final'];
		
		if (!empty($_GET['data_inicial']) && !empty($_GET['data_final'])) {
			$cond[] = "AND tbl_hd_chamado.data BETWEEN '{$_GET['data_inicial']} 00:00:00' AND '{$_GET['data_final']} 23:59:59'";
		}
		if (!empty($_GET['produto']) ) {
			$cond[] = "AND tbl_hd_chamado_extra.produto = {$_GET['produto']}";
		}
		if (!empty ($_GET['atendente'])) {
			$cond[] = "AND tbl_hd_chamado.atendente = {$_GET['atendente']}";
		}

		if (!empty($_GET['orientacao'])) {
			$cond[] = "AND orientacao_uso";
		}

		if (empty($cond)) {
			$msg_erro = "Consulta Inválida";
		}
		else 
			$cond = implode(" ", $cond);

	}
	else
		$msg_erro = "Consulta Inválida";
	
	if (!empty($msg_erro)) {
		die ('<div class="msg_erro" style="width:700px; margin:auto;">'.$msg_erro.'</div>');
	}

	$link_xls = "xls/relatorio_atendimento_orientacao_uso_popup_$login_fabrica_" . date("d-m-y") . '.xls';
	if (file_exists($link_xls))
		exec("rm -f $link_xls");
	if ( is_writable("xls/") ) 
		$file = fopen($link_xls, 'a+');
	else
		echo 'Sem Permissão de escrita';

	if ($_GET['tela'] == 2) {

		$sql = "SELECT (SELECT COUNT(tbl_hd_chamado.hd_chamado) as total 
			FROM tbl_hd_chamado JOIN tbl_hd_chamado_extra USING(hd_chamado) 
			JOIN tbl_produto USING(produto) JOIN tbl_admin ON tbl_admin.admin = atendente 
			WHERE tbl_hd_chamado.fabrica = $login_fabrica
			AND tbl_hd_chamado.categoria = 'produto_reclamacao' 
			$cond),
			( SELECT COUNT(tbl_hd_chamado.hd_chamado) as total 
			FROM tbl_hd_chamado JOIN tbl_hd_chamado_extra USING(hd_chamado) 
			JOIN tbl_produto USING(produto) JOIN tbl_admin ON tbl_admin.admin = atendente 
			WHERE tbl_hd_chamado.fabrica = $login_fabrica
			AND tbl_hd_chamado.categoria = 'produto_reclamacao' 
			$cond and orientacao_uso)";

		$res = pg_query($con,$sql);
		$total = pg_result($res,0,0);
		$total_orientacao = pg_result($res,0,1);
		
		$sql = "SELECT tbl_hd_chamado.atendente, tbl_admin.nome_completo, tbl_produto.produto, 
				tbl_produto.referencia || ' - ' || tbl_produto.descricao as descricao, 
				COUNT(tbl_hd_chamado.hd_chamado) as total_reclamacao
				FROM tbl_hd_chamado JOIN tbl_hd_chamado_extra USING(hd_chamado) 
				JOIN tbl_produto USING(produto) JOIN tbl_admin ON tbl_admin.admin = atendente 
				WHERE tbl_hd_chamado.fabrica = $login_fabrica
				AND tbl_hd_chamado.categoria = 'produto_reclamacao' 
				$cond
				GROUP BY tbl_hd_chamado.atendente, nome_completo, tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao 
				ORDER BY nome_completo";

		$res = pg_query($con,$sql);

		ob_start();

		for ($i = 0; $i < pg_num_rows($res); $i++) {
			
			if ($i == 0) {
				
				echo '<table class="tabela" cellspacing="1" align="center">
					  	<tr class="titulo_coluna">
					  		<th>Atendente</th>
					  		<th>Produto</th>
					  		<th>Total Prod. Reclamação</th>
					  		<th>%</th>
					  		<th>Orientação de Uso</th>
					  		<th>%</th>
					  	</tr>';

			}

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			$total_reclamacao = pg_result($res,$i,'total_reclamacao');
			$pc_total_rec = number_format ( $total_reclamacao * 100 / $total, 2,',', '.' );

			$xatendente = pg_result($res,$i,'atendente');
			$xproduto = pg_result($res,$i,'produto');

			$url_params = "tela=3&data_inicial=$aux_data_inicial&data_final=$aux_data_final&produto=$xproduto&atendente=$xatendente";
			$url_params_orientacao = "tela=3&data_inicial=$aux_data_inicial&data_final=$aux_data_final&atendente=$xatendente&produto=$xproduto&orientacao=t";

			$sql = "SELECT COUNT(tbl_hd_chamado.hd_chamado)
					FROM tbl_hd_chamado 
					JOIN tbl_hd_chamado_extra USING (hd_chamado)
					WHERE fabrica = $login_fabrica
					AND atendente = $xatendente
					AND produto = $xproduto
					AND orientacao_uso IS TRUE
					$cond";

			$res2 = pg_query($con,$sql);

			$orientacao = pg_result($res2,0,0);
			$orientacao = ($orientacao == 0) ? null : $orientacao;
			$xorientacao = (!empty($orientacao)) ? '<a href="?'.$url_params_orientacao.'">'.$orientacao.'</a>' : '&nbsp;';

			$pc_orientacao = ($total_orientacao > 0) ? number_format ($orientacao * 100 / $total,2) : '&nbsp;';
			$pc_total_orientacao += $pc_orientacao;
			$pc_orientacao = $pc_orientacao == 0 ?  '&nbsp;' : $pc_orientacao;
			
			echo '<tr bgcolor="'.$cor.'">
										
					<td>'.pg_result($res,$i,'nome_completo').'</td>
					<td>'.pg_result($res,$i,'descricao').'</td>
					<td align="center"><a href="?'.$url_params.'">'.$total_reclamacao.'</a></td>
					<td align="center">'.$pc_total_rec.'</td>
					<td align="center">'.$xorientacao.'</td>
					<td align="center">'.$pc_orientacao.'</td>

	 			  </tr>';

		}

		if (pg_num_rows($res)) {

			$url_params = "tela=3&data_inicial=$aux_data_inicial&data_final=$aux_data_final&atendente=$xatendente";
			$url_params_orientacao = "tela=3&data_inicial=$aux_data_inicial&data_final=$aux_data_final&atendente=$xatendente&orientacao=t";
			
			$xtotal_orientacao = (!empty($total_orientacao)) ? '<a href="?'.$url_params_orientacao.'">'.$total_orientacao.'</a>' : '&nbsp;';
//			$pc_total_orientacao = (!empty($total_orientacao)) ? number_format($total_orientacao * 100 / $total,2) : '&nbsp;';

			echo '<tr class="titulo_coluna">
					<th colspan="2">Total</th>
					<th><a href="?'.$url_params.'">'.$total.'</a></th>
					<th>100%</th>
					<th>'.$xtotal_orientacao.'</th>
					<th>'.$pc_total_orientacao.'</th>
				  </tr>
			   </table>';

		}

		$dados_relatorio = ob_get_contents();
		
		fwrite($file,$dados_relatorio);

	}

	if ($_GET['tela'] == 3) {

		$sql = "SELECT  tbl_hd_chamado.hd_chamado, tbl_hd_chamado.titulo, TO_CHAR(tbl_hd_chamado.data, 'DD-MM-YYYY') as data, MAX( TO_CHAR(tbl_hd_chamado_item.data, 'DD-MM-YYYY') ) as ultima_interacao, CASE WHEN orientacao_uso IS TRUE THEN 'Sim' ELSE '&nbsp;' END AS orientacao_uso, tbl_produto.referencia || ' - ' || tbl_produto.descricao as descricao, tbl_admin.nome_completo
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra USING (hd_chamado)
				LEFT JOIN tbl_hd_chamado_item USING (hd_chamado)
				JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto
				JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin
				WHERE tbl_hd_chamado.fabrica = $login_fabrica
				AND tbl_hd_chamado.categoria = 'produto_reclamacao'
				$cond
				GROUP BY tbl_hd_chamado.hd_chamado, tbl_hd_chamado.titulo, tbl_hd_chamado.data, orientacao_uso, tbl_produto.descricao, tbl_admin.nome_completo, tbl_produto.referencia
				ORDER BY tbl_hd_chamado.data DESC, tbl_admin.nome_completo, tbl_produto.descricao";

		$res = pg_query($con,$sql);
		$total = pg_num_rows($res);

		ob_start();

		for ($i = 0; $i < $total; $i++) {
			
			if ($i == 0) {
				
				echo '<table class="tabela" cellspacing="1" align="center">
					  	<tr class="titulo_coluna">
					  		<th>Protocolo</th>
					  		<th>Assunto</th>
					  		<th>Abertura</th>
					  		<th>Última Interação</th>
					  		<th>Orientação de Uso</th>
					  		<th>Produto</th>
					  		<th>Atendente</th>
					  	</tr>';

			}

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			echo '<tr bgcolor="'.$cor.'">
					<td>
						<a href="callcenter_interativo_new.php?callcenter='.pg_result($res,$i,'hd_chamado').'" target="_blank">'.pg_result($res,$i,'hd_chamado').'</a>
					</td>
					<td>&nbsp;'.pg_result($res,$i,'titulo').'</td>
					<td>'.pg_result($res,$i,'data').'</td>
					<td>&nbsp;'.pg_result($res,$i,'ultima_interacao').'</td>
					<td align="center">'.pg_result($res,$i,'orientacao_uso').'</td>
					<td>'.pg_result($res,$i,'descricao').'</td>
					<td>'.pg_result($res,$i,'nome_completo').'</td>
	 			  </tr>';

		}

		if (pg_num_rows($res)) {
			
			echo '<tr class="titulo_coluna">
					<th colspan="6">Total</th>
					<th>'.$total.'</th>
				  </tr>
			   </table>';

		}

		$dados_relatorio = ob_get_contents();
		
		fwrite($file,$dados_relatorio);

	}

	if ( isset ($file) && !empty($dados_relatorio) ) {
		echo "<br /><div class='download'><button onclick=\"window.open('$link_xls') \">Download XLS</button></div>";
		fclose($file);
	}
	else if(empty($msg_erro) && isset($_POST['gerar']) )
		echo "Não foram encontrados resultados para essa pesquisa";
	
?>

	</body>
</html>
