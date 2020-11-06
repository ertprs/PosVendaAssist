<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';

	$admin_privilegios="call_center";
	include 'autentica_admin.php';
	include 'funcoes.php';

	function retiraVirgula($str){

		$str = str_replace(",", "", $str);
		$str = str_replace(";", "", $str);

		return $str;

	}


	if($_POST["gerar_excel"]){

		$sql = "
			SELECT tbl_peca.referencia,
			tbl_peca.descricao,
			tbl_produto.referencia_fabrica,
			tbl_produto.referencia AS referencia_produto,
			tbl_produto.parametros_adicionais, 
			CASE
			WHEN tbl_peca_fora_linha.peca notnull THEN
				'OBSOLETO'
			WHEN tbl_depara.peca_de notnull THEN
				'SUBST'
			WHEN tbl_peca.bloqueada_venda IS TRUE AND tbl_peca.bloqueada_garantia IS TRUE THEN
				'INPNAT'
			ELSE
				' '
			END AS status,
			tbl_lista_basica.qtde
			FROM tbl_lista_basica
			JOIN tbl_peca ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
			JOIN tbl_produto ON tbl_lista_basica.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
			LEFT JOIN tbl_peca_fora_linha ON tbl_peca.peca = tbl_peca_fora_linha.peca AND tbl_peca_fora_linha.fabrica = $login_fabrica
			LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de AND tbl_depara.fabrica = $login_fabrica
			WHERE tbl_lista_basica.fabrica = $login_fabrica
			AND (tbl_lista_basica.ativo is not false)
			AND tbl_peca.produto_acabado is not true  
			ORDER BY tbl_produto.referencia 
		";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_pecas_lista_basicas-{$data}.csv";
			
			$file = fopen("/tmp/{$fileName}", "w");

			header('Content-Type: application/csv; charset=iso-8859-1');
			header('Content-Disposition: attachment; filename="/tmp/{$fileName}"');

			if($login_fabrica == 1){
				$thead = "Código da Peca; Descricao da Peca; Data Descontinuada; Status da Peca; Codigo do Produto Telecontrol; Codigo do Produto Interno; Quantidade \n";
			}else{
				$thead = "Código da Peca; Descricao da Peca; Status da Peca; Codigo do Produto Telecontrol; Codigo do Produto Interno \n";
			}

			fwrite($file, $thead);

			for ($i = 0; $i < pg_num_rows($res); $i++) {

				$referencia_peca 		= pg_fetch_result($res, $i, 'referencia');
				$referencia_interna 	= pg_fetch_result($res, $i, 'referencia_fabrica');
				$referencia_produto 	= pg_fetch_result($res, $i, 'referencia_produto');
				$descricao 				= pg_fetch_result($res, $i, 'descricao');
				$status 				= pg_fetch_result($res, $i, 'status');
				$quantidade				= pg_fetch_result($res, $i, 'qtde');
				$parametros_adicionais  = pg_fetch_result($res, $i, 'parametros_adicionais');

				$parametros_adicionais  = json_decode($parametros_adicionais, true);

				$data_descontinuada 	= $parametros_adicionais['data_descontinuado'];

				$referencia_peca		= retiraVirgula($referencia_peca);
				$referencia_interna		= retiraVirgula($referencia_interna);
				$referencia_produto		= retiraVirgula($referencia_produto);
				$descricao				= retiraVirgula($descricao);
				$status					= retiraVirgula($status);
				$quantidade				= retiraVirgula($quantidade);

				if(strlen($referencia_peca) > 0){
					if($login_fabrica == 1){
						$body .="{$referencia_peca}; {$descricao}; {$data_descontinuada}; {$status}; {$referencia_produto}; {$referencia_interna}; {$quantidade} \n";
					}else{
						$body .="{$referencia_peca}; {$descricao}; {$status}; {$referencia_produto}; {$referencia_interna} \n";
					}

				}
			}

			fwrite($file, $body);
			fwrite($file, "Total de ".pg_num_rows($res)." registros");

			fclose($file);

			if(isset($_POST['peca_consulta_dados']) && $_POST['peca_consulta_dados'] == "sim"){
				header("location: xls/{$fileName}");
			}

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}

		}

		exit;

	}

	$layout_menu = "callcenter";
	$title = "PEÇAS QUE CONSTAM EM LISTAS BÁSICAS DE PRODUTOS";
	include "cabecalho_new.php";

	$plugins = array(
		"dataTable"
	);

	include("plugin_loader.php");

	echo "<div class='container'>";

		$sql = "
			SELECT tbl_peca.referencia,
			tbl_peca.descricao,
			tbl_produto.referencia_fabrica,
			tbl_produto.referencia AS referencia_produto,
			CASE
			WHEN tbl_peca_fora_linha.peca notnull THEN
				'OBSOLETO'
			WHEN tbl_depara.peca_de notnull THEN
				'SUBST'
			WHEN tbl_peca.bloqueada_venda IS TRUE AND tbl_peca.bloqueada_garantia IS TRUE THEN
				'INPNAT'
			ELSE
				' '
			END AS status
			FROM tbl_lista_basica
			JOIN tbl_peca ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
			JOIN tbl_produto ON tbl_lista_basica.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
			LEFT JOIN tbl_peca_fora_linha ON tbl_peca.peca = tbl_peca_fora_linha.peca AND tbl_peca_fora_linha.fabrica = $login_fabrica
			LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de AND tbl_depara.fabrica = $login_fabrica
			WHERE tbl_lista_basica.fabrica = $login_fabrica
			AND tbl_lista_basica.ativo 
			LIMIT 501
		";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			if(pg_num_rows($res) > 500){
				echo "<div class='text-error tac'><h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6></div>";
			}

			?>

			<br />

			<table id="resultado_os_atendimento" class='table table-striped table-bordered' width="100%">
				<thead>
					<tr class='titulo_tabela'>
						<th colspan='9'>Relatório de Peças nas Listas Básicas</th>
					</tr>
					<tr class='titulo_coluna'>
						<th>Código da Peça</th>
						<th>Descrição da Peça</th>
						<th>Status da Peça</th>
						<th>Código do Produto Telecontrol</th>
						<th>Código do Produto Interno</th>
					</tr>
				</thead>
				<tbody id="resultado">

				<?php

					for($i = 0; $i < pg_num_rows($res); $i++){

						$referencia_peca 		= pg_fetch_result($res, $i, 'referencia');
						$referencia_interna 	= pg_fetch_result($res, $i, 'referencia_fabrica');
						$referencia_produto 	= pg_fetch_result($res, $i, 'referencia_produto');
						$descricao 				= pg_fetch_result($res, $i, 'descricao');
						$status 				= pg_fetch_result($res, $i, 'status');

						echo "
						<tr>
							<td>{$referencia_peca}</td>
							<td>{$descricao}</td>
							<td>{$status}</td>
							<td>{$referencia_produto}</td>
							<td>{$referencia_interna}</td>
						</tr>
						";

					}

				?>
				</tbody>
			</table>

			<br />

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='{"gerar_excel" : true}' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</div>

			<br />


			<?php
			echo $fileName;

		}else{
			echo "<div class='alert alert-block'><h4>Nenhum registro encontrado</h4></div>";
		}

		if(pg_num_rows($res) > 50){

			echo "
				<script>
					$.dataTableLoad({ table: '#resultado_os_atendimento' });
				</script>
			";

		}

	echo "</div>";

	include "rodape.php";

?>
