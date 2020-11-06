<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$title = "Pedido de Peças - peças mais pedidas";

if(isset($_POST["btn_acao"])){

	$fabrica      = $_POST["fabrica"];
	$data_inicial = $_POST["data_inicial"];
	$data_final   = $_POST["data_final"];

}

?>

<html>
	<head>

		<title><?php echo $title ?></title>
		<link type="text/css" rel="stylesheet" href="css/css.css">
		<script language='javascript' src='../ajax.js'></script>
		<?include "javascript_calendario_new.php"; ?>
		<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
		<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
		<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
		<script type='text/javascript' src='js/dimensions.js'></script>
		<!-- <script type="text/javascript" src="../admin/js/jquery.maskmoney.js"></script> -->
		<script type="text/javascript" src="js/thickbox.js"></script>
		<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

		<script type="text/javascript">

		$(function(){

			$('#data_inicial').datePicker({startDate:'01/01/2000'});
			$('#data_final').datePicker({startDate:'01/01/2000'});
			$("#data_inicial").maskedinput("99/99/9999");
			$("#data_final").maskedinput("99/99/9999");
		});

		</script>

		<style type="text/css">

			.msg-erro{
				background-color: #ff0000;
				color: #ffffff;
				padding: 10px;
				width: 780px;
				margin-bottom: 20px;
				border-radius: 5px;
			}

		</style>

	</head>

	<body>

		<?php include 'menu.php';?>

		<center>
			<h1><?php echo $title; ?></h1>
		</center>

		<center>

			<?php

			if(strlen($msg_erro) > 0){
				echo "<div class='msg-erro'>{$msg_erro}</div>";
			}

			?>

			<p align="center" style="color: #999;">
				Quando o filtro estiver sem data inicial e final, o sistema limitara em 1 ano
			</p>

			<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='POST'>

				<table>
					
					<tr>
						<td>
							Fabricas 
							<select name="fabrica" class="frm">
								<option></option>

								<?php

								$sql_fabricas = "SELECT fabrica, nome FROM tbl_fabrica WHERE fabrica IN ({$telecontrol_distrib}) ORDER BY nome"; 
								$res_fabricas = pg_query($con, $sql_fabricas);

								if(pg_num_rows($res_fabricas) > 0){

									for($i = 0; $i < pg_num_rows($res_fabricas); $i++){

										$fabrica_codigo = pg_fetch_result($res_fabricas, $i, "fabrica");
										$fabrica_nome = pg_fetch_result($res_fabricas, $i, "nome");

										$selected = ($fabrica_codigo == $fabrica) ? "SELECTED" : "";

										echo "<option value='{$fabrica_codigo}' {$selected}>{$fabrica_nome}</option>";

									}

								}

								?>

							</select>
						</td>
						<td> &nbsp; Data Inicial </td> <td> <input type="text" name="data_inicial" id="data_inicial" value="<?php echo $data_inicial; ?>" class="frm" /></td>
						<td> &nbsp; Data Final </td> <td> <input type="text" name="data_final" id="data_final" value="<?php echo $data_final; ?>" class="frm" /></td>
					</tr>

					<tr>
						<td align='center' colspan='3'><input type='submit' name='btn_acao' id='btn_acao' value='Pesquisar'></td>
					</tr>

				</table>

			</form>

		</center>

		<?php

		if(isset($_POST["btn_acao"])){

			list($dia, $mes, $ano) = explode("/", $data_inicial);
			$data_inicial_bd = $ano."-".$mes."-".$dia;

			list($dia, $mes, $ano) = explode("/", $data_final);
			$data_final_bd = $ano."-".$mes."-".$dia;

			if(strlen($fabrica) > 0 && strlen($data_inicial) > 0 && strlen($data_final) > 0){

				$sql = "SELECT  
						  	tbl_peca.peca,
						  	tbl_pedido_item.qtde,
						  	tbl_peca.referencia,
						  	tbl_peca.descricao,
						  	tbl_fabrica.nome AS nome_fabrica,
						  	tbl_pedido.pedido,
						  	(
						    	SELECT 
						       		COUNT(DISTINCT tbl_os.os) 
						    	FROM tbl_os_item 
						    	INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
						    	INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os 
						    	WHERE 
						      		tbl_os_item.pedido = tbl_pedido.pedido 
						      		AND tbl_os.fabrica = {$fabrica} 
						  	) AS os_pedidos 
						INTO TEMP tmp_pedidos_pecas_distrib 
						FROM tbl_pedido_item 
						INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido 
						INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.pedido_em_garantia IS TRUE 
						INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca 
						INNER JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_pedido.fabrica 
						WHERE 
							tbl_pedido.fabrica = {$fabrica}   
							AND tbl_pedido.finalizado NOTNULL 
							AND tbl_pedido.data BETWEEN '{$data_inicial_bd} 00:00:00' AND '{$data_final_bd} 23:59:59';
						SELECT 
						  	peca, 
						  	SUM(qtde) AS qtde_pecas, 
						  	referencia, 
						  	descricao,
						  	nome_fabrica,
						  	ARRAY(
						    	SELECT x.pedido FROM tmp_pedidos_pecas_distrib x WHERE x.peca = tmp_pedidos_pecas_distrib.peca
						  	) AS pedidos_arr,
						  	SUM(os_pedidos) AS qtde_os   
						FROM tmp_pedidos_pecas_distrib 
						GROUP BY peca, qtde, referencia, descricao, pedido, nome_fabrica, os_pedidos 
						ORDER BY qtde_pecas DESC  
						LIMIT 10;
				";

				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){

				?>

				<table align="center" border="0" cellspacing="1" cellpadding="5" style="width: 800px;">
					<thead>
						<tr bgcolor="#0099CC" style="color: #fff; font-weight: bold; font-size: 16px;">
							<th style='width: 100px;'>Fábrica</th>
							<th style='width: 400px;'>Peça</th>
							<th style='width: 100px;'>Qtde</th>
							<th style='width: 100px;'>Pedidos Faturados</th>
							<th style='width: 100px;'>OS</th>
						</tr>
					</thead>

					<tbody>
						
						<?php

						for ($i = 0; $i < pg_num_rows($res); $i++) { 
							
							$nome_fabrica = pg_fetch_result($res, $i, "nome_fabrica");
							$peca         = pg_fetch_result($res, $i, "peca");
							$referencia   = pg_fetch_result($res, $i, "referencia");
							$descricao    = pg_fetch_result($res, $i, "descricao");
							$qtde_pecas   = pg_fetch_result($res, $i, "qtde_pecas");
							$pedidos_arr  = pg_fetch_result($res, $i, "pedidos_arr");
							$oss          = pg_fetch_result($res, $i, "qtde_os");

							$pedidos_str = str_replace(array("{", "}"), "", $pedidos_arr);
							$pedidos_arr = explode(",", $pedidos_str);
							$pedidos = count($pedidos_arr);

							$cor = ($i % 2 == 0) ? "#ccc" : "#eee";

							echo "
							<tr bgcolor='{$cor}'>
								<td>{$nome_fabrica}</td>
								<td>{$referencia} - {$descricao}</td>
								<td align='center'>{$qtde_pecas}</td>
								<td align='center'>".( ($pedidos > 0) ? "<a href='pedidos_pecas_detalhe.php?tipo=pedido&peca={$peca}&fabrica={$fabrica}&data_inicial={$data_limite}&data_final=&pedido={$pedidos_str}' target='_blank'>{$pedidos}</a>" : $pedidos) . "</td>
								<td align='center'>".( ($oss > 0) ? "<a href='pedidos_pecas_detalhe.php?tipo=os&peca={$peca}&fabrica={$fabrica}&data_inicial={$data_limite}&data_final=&pedido={$pedidos_str}' target='_blank'>{$oss}</a>" : $oss) . "</td>
							</tr>
							";

						}

						?>

					</tbody>

				</table>

				<?php

				}else{

					$sql_nome = "SELECT nome FROM tbl_fabrica WHERE fabrica = {$fabrica}";
					$res_nome = pg_query($con, $sql_nome);

					$nome_fabrica = pg_fetch_result($res_nome, 0, "nome");

					echo "<p align='center'>Nenhum registro encontrado para a {$nome_fabrica}!</p>";
				}

			}else if(strlen($fabrica) > 0){

				$ano = (int)date("Y") - 1;
				$data_limite = $ano."-".date("m")."-".date("d");

				$sql = "SELECT  
						  	tbl_peca.peca,
						  	tbl_pedido_item.qtde,
						  	tbl_peca.referencia,
						  	tbl_peca.descricao,
						  	tbl_fabrica.nome AS nome_fabrica,
						  	tbl_pedido.pedido,
						  	(
						    	SELECT 
						       		COUNT(DISTINCT tbl_os.os) 
						    	FROM tbl_os_item 
						    	INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
						    	INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os 
						    	WHERE 
						      		tbl_os_item.pedido = tbl_pedido.pedido 
						      		AND tbl_os.fabrica = {$fabrica} 
						      		AND tbl_os_item.peca = tbl_peca.peca
						  	) AS os_pedidos 
						INTO TEMP tmp_pedidos_pecas_distrib 
						FROM tbl_pedido_item 
						INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido 
						INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.pedido_em_garantia IS TRUE 
						INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca 
						INNER JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_pedido.fabrica 
						WHERE 
							tbl_pedido.fabrica = {$fabrica}   
							AND tbl_pedido.finalizado NOTNULL 
							AND tbl_pedido.data >= '{$data_limite}';
						SELECT 
						  	peca, 
						  	SUM(qtde) AS qtde_pecas, 
						  	referencia, 
						  	descricao,
						  	nome_fabrica,
						  	ARRAY(
						    	SELECT x.pedido FROM tmp_pedidos_pecas_distrib x WHERE x.peca = tmp_pedidos_pecas_distrib.peca
						  	) AS pedidos_arr,
						  	SUM(os_pedidos) AS qtde_os 
						FROM tmp_pedidos_pecas_distrib 
						GROUP BY peca, qtde, referencia, descricao, pedidos_arr, nome_fabrica, os_pedidos 
						ORDER BY qtde_pecas DESC  
						LIMIT 100;
				";

				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){

					?>

					<table align="center" border="0" cellspacing="1" cellpadding="5" style="width: 800px;">
						<thead>
							<tr bgcolor="#0099CC" style="color: #fff; font-weight: bold; font-size: 16px;">
								<th style='width: 100px;'>Fábrica</th>
								<th style='width: 400px;'>Peça</th>
								<th style='width: 100px;'>Qtde</th>
								<th style='width: 100px;'>Pedidos Faturados</th>
								<th style='width: 100px;'>OS</th>
							</tr>
						</thead>

						<tbody>
							
							<?php

							for ($i = 0; $i < pg_num_rows($res); $i++) { 
								
								$nome_fabrica = pg_fetch_result($res, $i, "nome_fabrica");
								$peca         = pg_fetch_result($res, $i, "peca");
								$referencia   = pg_fetch_result($res, $i, "referencia");
								$descricao    = pg_fetch_result($res, $i, "descricao");
								$qtde_pecas   = pg_fetch_result($res, $i, "qtde_pecas");
								$pedidos_arr  = pg_fetch_result($res, $i, "pedidos_arr");
								$oss          = pg_fetch_result($res, $i, "qtde_os");

								$pedidos_str = str_replace(array("{", "}"), "", $pedidos_arr);
								$pedidos_arr = explode(",", $pedidos_str);
								$pedidos = count($pedidos_arr);

								$cor = ($i % 2 == 0) ? "#ccc" : "#eee";

								echo "
								<tr bgcolor='{$cor}'>
									<td>{$nome_fabrica}</td>
									<td>{$referencia} - {$descricao}</td>
									<td align='center'>{$qtde_pecas}</td>
									<td align='center'>".( ($pedidos > 0) ? "<a href='pedidos_pecas_detalhe.php?tipo=pedido&peca={$peca}&fabrica={$fabrica}&data_inicial={$data_limite}&data_final=&pedido={$pedidos_str}' target='_blank'>{$pedidos}</a>" : $pedidos) . "</td>
									<td align='center'>".( ($oss > 0) ? "<a href='pedidos_pecas_detalhe.php?tipo=os&peca={$peca}&fabrica={$fabrica}&data_inicial={$data_limite}&data_final=&pedido={$pedidos_str}' target='_blank'>{$oss}</a>" : $oss) . "</td>
								</tr>
								";

							}

							?>

						</tbody>

					</table>

					<?php

				}else{

					$sql_nome = "SELECT nome FROM tbl_fabrica WHERE fabrica = {$fabrica}";
					$res_nome = pg_query($con, $sql_nome);

					$nome_fabrica = pg_fetch_result($res_nome, 0, "nome");

					echo "<p align='center'>Nenhum registro encontrado para a {$nome_fabrica}!</p>";
				}

			}else if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

				$fabricas_array = explode(",", $telecontrol_distrib);

				$cont = 0;

				foreach ($fabricas_array as $key => $value) {

					$i = $cont++;
					
					$fabrica = $value;

					$sql = "SELECT  
							  	tbl_peca.peca,
							  	tbl_pedido_item.qtde,
							  	tbl_peca.referencia,
							  	tbl_peca.descricao,
							  	tbl_fabrica.nome AS nome_fabrica,
							  	tbl_pedido.pedido,
							  	(
							    	SELECT 
							       		COUNT(DISTINCT tbl_os.os) 
							    	FROM tbl_os_item 
							    	INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
							    	INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os 
							    	WHERE 
							      		tbl_os_item.pedido = tbl_pedido.pedido 
							      		AND tbl_os.fabrica = {$fabrica} 
							  	) AS os_pedidos 
							INTO TEMP tmp_pedidos_pecas_distrib_$i 
							FROM tbl_pedido_item 
							INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido 
							INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.pedido_em_garantia IS TRUE 
							INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca 
							INNER JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_pedido.fabrica 
							WHERE 
								tbl_pedido.fabrica = {$fabrica}   
								AND tbl_pedido.finalizado NOTNULL 
								AND tbl_pedido.data BETWEEN '{$data_inicial_bd} 00:00:00' AND '{$data_final_bd} 23:59:59';
							SELECT 
							  	peca, 
							  	SUM(qtde) AS qtde_pecas, 
							  	referencia, 
							  	descricao,
							  	nome_fabrica,
							  	ARRAY(
							    	SELECT x.pedido FROM tmp_pedidos_pecas_distrib x WHERE x.peca = tmp_pedidos_pecas_distrib.peca
							  	) AS pedidos_arr,
							  	SUM(os_pedidos) AS qtde_os  
							FROM tmp_pedidos_pecas_distrib_$i 
							GROUP BY peca, qtde, referencia, descricao, pedido, nome_fabrica, os_pedidos 
							ORDER BY qtde_pecas DESC  
							LIMIT 10;
					";

					$res = pg_query($con, $sql);

					if(pg_num_rows($res) > 0){

					?>

					<table align="center" border="0" cellspacing="1" cellpadding="5" style="width: 800px;">
						<thead>
							<tr bgcolor="#0099CC" style="color: #fff; font-weight: bold; font-size: 16px;">
								<th style='width: 100px;'>Fábrica</th>
								<th style='width: 400px;'>Peça</th>
								<th style='width: 100px;'>Qtde</th>
								<th style='width: 100px;'>Pedidos Faturados</th>
								<th style='width: 100px;'>OS</th>
							</tr>
						</thead>

						<tbody>
							
							<?php

							for ($i = 0; $i < pg_num_rows($res); $i++) { 
								
								$nome_fabrica = pg_fetch_result($res, $i, "nome_fabrica");
								$peca         = pg_fetch_result($res, $i, "peca");
								$referencia   = pg_fetch_result($res, $i, "referencia");
								$descricao    = pg_fetch_result($res, $i, "descricao");
								$qtde_pecas   = pg_fetch_result($res, $i, "qtde_pecas");
								$pedidos_arr  = pg_fetch_result($res, $i, "pedidos_arr");
								$oss          = pg_fetch_result($res, $i, "qtde_os");

								$pedidos_str = str_replace(array("{", "}"), "", $pedidos_arr);
								$pedidos_arr = explode(",", $pedidos_str);
								$pedidos = count($pedidos_arr);

								$cor = ($i % 2 == 0) ? "#ccc" : "#eee";

								echo "
								<tr bgcolor='{$cor}'>
									<td>{$nome_fabrica}</td>
									<td>{$referencia} - {$descricao}</td>
									<td align='center'>{$qtde_pecas}</td>
									<td align='center'>".( ($pedidos > 0) ? "<a href='pedidos_pecas_detalhe.php?tipo=pedido&peca={$peca}&fabrica={$fabrica}&data_inicial={$data_limite}&data_final=&pedido={$pedidos_str}' target='_blank'>{$pedidos}</a>" : $pedidos) . "</td>
									<td align='center'>".( ($oss > 0) ? "<a href='pedidos_pecas_detalhe.php?tipo=os&peca={$peca}&fabrica={$fabrica}&data_inicial={$data_limite}&data_final=&pedido={$pedidos_str}' target='_blank'>{$oss}</a>" : $oss) . "</td>
								</tr>
								";

							}

							?>

						</tbody>

					</table>

					<?php

					}else{

						$sql_nome = "SELECT nome FROM tbl_fabrica WHERE fabrica = {$fabrica}";
						$res_nome = pg_query($con, $sql_nome);

						$nome_fabrica = pg_fetch_result($res_nome, 0, "nome");

						echo "<p align='center'>Nenhum registro encontrado para a {$nome_fabrica}!</p>";
					}

				}

			}else{

				$fabricas_array = explode(",", $telecontrol_distrib);

				$cont = 0;

				$ano = (int)date("Y") - 1;
				$data_limite = $ano."-".date("m")."-".date("d");

				foreach ($fabricas_array as $key => $value) {

					$i = $cont++;
					
					$fabrica = $value;

					$sql = "SELECT  
							  	tbl_peca.peca,
							  	tbl_pedido_item.qtde,
							  	tbl_peca.referencia,
							  	tbl_peca.descricao,
							  	tbl_fabrica.nome AS nome_fabrica,
							  	tbl_pedido.pedido,
							  	(
							    	SELECT 
							       		COUNT(DISTINCT tbl_os.os) 
							    	FROM tbl_os_item 
							    	INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
							    	INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os 
							    	WHERE 
							      		tbl_os_item.pedido = tbl_pedido.pedido 
							      		AND tbl_os.fabrica = {$fabrica} 
							  	) AS os_pedidos 
							INTO TEMP tmp_pedidos_pecas_distrib_$i 
							FROM tbl_pedido_item 
							INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido 
							INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.pedido_em_garantia IS TRUE 
							INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca 
							INNER JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_pedido.fabrica 
							WHERE 
								tbl_pedido.fabrica = {$fabrica}   
								AND tbl_pedido.finalizado NOTNULL 
								AND tbl_pedido.data >= '{$data_limite}';
							SELECT 
							  	peca, 
							  	SUM(qtde) AS qtde_pecas, 
							  	referencia, 
							  	descricao,
							  	nome_fabrica,
							  	ARRAY(
							    	SELECT x.pedido FROM tmp_pedidos_pecas_distrib_$i x WHERE x.peca = tmp_pedidos_pecas_distrib_$i.peca
							  	) AS pedidos_arr,
							  	SUM(os_pedidos) AS qtde_os 
							FROM tmp_pedidos_pecas_distrib_$i 
							GROUP BY peca, qtde, referencia, descricao, pedido, nome_fabrica, os_pedidos 
							ORDER BY qtde_pecas DESC  
							LIMIT 10;
					";

					$res = pg_query($con, $sql);

					echo pg_last_error(); 

					if(pg_num_rows($res) > 0){

					?>

					<table align="center" border="0" cellspacing="1" cellpadding="5" style="width: 800px;">
						<thead>
							<tr bgcolor="#0099CC" style="color: #fff; font-weight: bold; font-size: 16px;">
								<th style='width: 100px;'>Fábrica</th>
								<th style='width: 400px;'>Peça</th>
								<th style='width: 100px;'>Qtde</th>
								<th style='width: 100px;'>Pedidos Faturados</th>
								<th style='width: 100px;'>OS</th>
							</tr>
						</thead>

						<tbody>
							
							<?php

							for ($i = 0; $i < pg_num_rows($res); $i++) { 
								
								$nome_fabrica = pg_fetch_result($res, $i, "nome_fabrica");
								$peca         = pg_fetch_result($res, $i, "peca");
								$referencia   = pg_fetch_result($res, $i, "referencia");
								$descricao    = pg_fetch_result($res, $i, "descricao");
								$qtde_pecas   = pg_fetch_result($res, $i, "qtde_pecas");
								$pedidos_arr  = pg_fetch_result($res, $i, "pedidos_arr");
								$oss          = pg_fetch_result($res, $i, "qtde_os");

								$pedidos_str = str_replace(array("{", "}"), "", $pedidos_arr);
								$pedidos_arr = explode(",", $pedidos_str);
								$pedidos = count($pedidos_arr);

								$cor = ($i % 2 == 0) ? "#ccc" : "#eee";

								echo "
								<tr bgcolor='{$cor}'>
									<td>{$nome_fabrica}</td>
									<td>{$referencia} - {$descricao}</td>
									<td align='center'>{$qtde_pecas}</td>
									<td align='center'>".( ($pedidos > 0) ? "<a href='pedidos_pecas_detalhe.php?tipo=pedido&peca={$peca}&fabrica={$fabrica}&data_inicial={$data_limite}&data_final=&pedido={$pedidos_str}' target='_blank'>{$pedidos}</a>" : $pedidos) . "</td>
									<td align='center'>".( ($oss > 0) ? "<a href='pedidos_pecas_detalhe.php?tipo=os&peca={$peca}&fabrica={$fabrica}&data_inicial={$data_limite}&data_final=&pedido={$pedidos_str}' target='_blank'>{$oss}</a>" : $oss) . "</td>
								</tr>
								";

							}

							?>

						</tbody>

					</table>

					<?php

					}else{

						$sql_nome = "SELECT nome FROM tbl_fabrica WHERE fabrica = {$fabrica}";
						$res_nome = pg_query($con, $sql_nome);

						$nome_fabrica = pg_fetch_result($res_nome, 0, "nome");

						echo "<p align='center'>Nenhum registro encontrado para a {$nome_fabrica}!</p>";
					}

				}

			}

		}

		?>

		<? include "rodape.php"; ?>

	</body>

</html>