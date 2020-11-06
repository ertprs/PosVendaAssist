<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$admin_privilegios="call_center";
	include 'autentica_admin.php';
	include 'funcoes.php';

	if(isset($_POST["id_producao"]) && isset($_POST["qtde_vendida"])){

		$id_producao 	= $_POST["id_producao"];
		$qtde_vendida 	= $_POST["qtde_vendida"];

		$sql = "UPDATE tbl_producao SET qtde_venda = {$qtde_vendida} WHERE producao = {$id_producao}";
		$res = pg_query($con, $sql);

		$erro = pg_last_error($res);
		$data = (strlen($erro) == 0) ? array("result" => true) : array("result" => false);

		echo json_encode($data);

		exit;

	}

	$mes = trim($_REQUEST["mes"]);
	$ano = trim($_REQUEST["ano"]);

	$sql = "SELECT producao, mes, ano, produto, qtde_venda, admin FROM tbl_producao WHERE mes = {$mes} AND ano = {$ano} ORDER BY mes, ano, produto, qtde_venda, admin, producao ASC";
	$res = pg_query($con, $sql);

	/* Gerar Excel */
	if(isset($_POST["gerar_excel"])){

		if(pg_num_rows($res) > 0){

			$file     = "xls/relatorio-parque-garantia-{$login_fabrica}-{$mes}-{$ano}.xls";
			$fileTemp = "/tmp/relatorio-parque-garantia-{$login_fabrica}-{$mes}-{$ano}.xls";
	        $fp     = fopen($fileTemp,"w");

	        $head = "<table border='1'>
	                    <thead>
	                        <tr>
	                            <th bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' colspan='6' >RELATÓRIO DE PARQUE GARANTIA - $mes/$ano</th>
	                        </tr>
	                        <tr>
	                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Mês</th>
	                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Ano</th>
	                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Admin</th>
	                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Ref. Produto</th>
	                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Desc. Produto</th>
	                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Qtde. Vendida</th>
	                        </tr>
	                    </thead>
	                    <tbody>";

	        fwrite($fp, $head);

	        $total_venda = 0;

	        for($i = 0; $i < pg_num_rows($res); $i++){

	        	$producao 		= pg_fetch_result($res, $i, "producao");
				$mes 			= pg_fetch_result($res, $i, "mes");
				$ano 			= pg_fetch_result($res, $i, "ano");
				$produto 		= pg_fetch_result($res, $i, "produto");
				$qtde_vendida 	= pg_fetch_result($res, $i, "qtde_venda");
				$admin 			= pg_fetch_result($res, $i, "admin");

				$total_venda += $qtde_vendida;

	        	/* Produto */

				$sql_produto = "SELECT referencia, descricao FROM tbl_produto WHERE produto = {$produto}";
				$res_produto = pg_query($con, $sql_produto);

				$produto_referencia = pg_fetch_result($res_produto, 0, "referencia");
				$produto_descricao = pg_fetch_result($res_produto, 0, "descricao");

				/* Admin */

				$sql_admin = "SELECT login FROM tbl_admin WHERE admin = {$admin}";
				$res_admin = pg_query($con, $sql_admin);

				$usuario = pg_fetch_result($res_admin, 0, "login");

	            $body = "
	            	<tr>
	            		<td>{$mes}</td>
	            		<td>{$ano}</td>
	            		<td>{$usuario}</td>
	            		<td>{$produto_referencia}</td>
	            		<td>{$produto_descricao}</td>
	            		<td>{$qtde_vendida}</td>
	            	</tr>";


	            fwrite($fp, $body);

	        }

	        fwrite($fp, "<tr><th colspan='5' align='right'>Total</th><th align='right'>{$total_venda}</th></tr>");
	        fwrite($fp, "</tbody></table>");
	        fclose($fp);
	        if(file_exists($fileTemp)){
	            system("mv $fileTemp $file");

	            if(file_exists($file)){
	                echo $file;
	            }
	        }

		}

		exit;

	}

?>

<!DOCTYPE html>
<html>
	<head>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>

		<script>

			function atualiza_qtde_venda(id_producao){
				var qtde_vendida = $("#qtde_venda_"+id_producao).val();

				$.ajax({
					url: "<?php echo $_SERVER['PHP_SELF']; ?>",
					type: "POST",
					data: {
						id_producao : id_producao,
						qtde_vendida : qtde_vendida
					},
					beforeSend: function(){
						$(".box_msg_"+id_producao).html("<em>aguarde...</em>");
					},
					complete: function(data){
						data = $.parseJSON(data.responseText);
						if(data.result == true){
							$(".box_msg_"+id_producao).html("<div class='alert alert-success tac' style='margin-top: 5px; margin-bottom: 0px; font-size: 12px; padding: 5px;'>Alterado com Sucesso</div>");
						}else{
							$(".box_msg_"+id_producao).html("<div class='alert alert-danger tac' style='margin-top: 5px; margin-bottom: 0px; font-size: 12px; padding: 5px;'>Erro ao alterar</div>")
						}

						setTimeout(function(){	
							$(".box_msg_"+id_producao).hide(500);
						},4000);

					}
				});

			}

			function ajaxAction () {
    		if ($("#loading_action").val() == "t") {
	    			alert("Espere o processo atual terminar!");
	    			return false;
	    		} else {
	    			return true;
	    		}
	    	}

			$(function () {

				var loadingCount = 0;

				var zindexSelector = '.ui-widget';

	    		var subZIndex = function(){
	    			$(zindexSelector).each(function(){
	    				var oldZindex = $(this).css('z-index');
	    				$(this).attr('old-z-index',oldZindex);
	    				$(this).css('z-index',1);
	    			});
	    		};

	    		var returnZIndex = function(){
	    			$('[old-z-index]').each(function(){
	    				var oldZindex = $(this).attr('old-z-index');
	    				$(this).removeAttr('old-z-index');
	    				$(this).css('z-index',oldZindex);
	    			});
	    		};

				var funcLoading = function(display){

		    		switch (display) {
		    			case true:
		    			case "show":
		    				loadingCount += 1;
		    				if(loadingCount != 1)
		    					return;
		    				subZIndex();
		    				$("#loading").show();
		    				$("#loading-block").show();
							$("#loading_action").val("t");
		    				break;
		    			case false:
		    			case "hide":
		    				if(loadingCount >0)
		    					 loadingCount-= 1;
		    				if(loadingCount != 0)
		    					return;
		    				$("#loading").hide();
							$("#loading_action").val("f");
							$("#loading-block").hide();
							returnZIndex();
		    				break;
		    		}	
	    		};

	    		window.loading = funcLoading;

	    		$("#gerar_excel").click(function () {
	    			if (ajaxAction()) {
	    				var json = $.parseJSON($("#jsonPOST").val());
	    				json["gerar_excel"] = true;

		    			$.ajax({
		    				url: "<?=$_SERVER['PHP_SELF']?>",
		    				type: "POST",
		    				data: json,
		    				beforeSend: function () {
		    					loading("show");
		    				},
		    				complete: function (data) {
		    					window.open(data.responseText, "_blank");

		    					loading("hide");
		    				}
		    			});
	    			}
	    		});
	    	});

		</script>

	</head>
	<body>

		<div class="container" style="overflow: auto; height: 440px; width: 99%;">

			<input type="hidden" id="loading_action" value="f" />

			<h4 class="tac">Quantidade de Parque Garantia para o mês <?php echo $mes; ?> de <?php echo $ano; ?> </h4>

			<?php

				if(pg_num_rows($res) > 0){

					?>
					<table class="table table-striped table-bordered table-hover" style="margin: 0 auto; width: 99%;">
		                <thead>
		                    <tr class="titulo_coluna">
		                    	<th calss="tac">Mês</th>
		                    	<th calss="tac">Ano</th>
		                    	<th calss="tac">Admin</th>
		                    	<th calss="tac">Ref. Produto</th>
		                    	<th calss="tac">Desc. Produto</th>
		                    	<th calss="tac">Qtde. Vendida</th>
		                    	<th calss="tac">Ação</th>
		                    </tr>
		                </thead>
		                <tbody>
							<?php

								$total = 0;

								for($i = 0; $i < pg_num_rows($res); $i++){

									$producao 		= pg_fetch_result($res, $i, "producao");
									$mes 			= pg_fetch_result($res, $i, "mes");
									$ano 			= pg_fetch_result($res, $i, "ano");
									$produto 		= pg_fetch_result($res, $i, "produto");
									$qtde_vendida 	= pg_fetch_result($res, $i, "qtde_venda");
									$admin 			= pg_fetch_result($res, $i, "admin");

									/* Produto */

									$sql_produto = "SELECT referencia, descricao FROM tbl_produto WHERE produto = {$produto}";
									$res_produto = pg_query($con, $sql_produto);

									$produto_referencia = pg_fetch_result($res_produto, 0, "referencia");
									$produto_descricao = pg_fetch_result($res_produto, 0, "descricao");

									/* Admin */

									$sql_admin = "SELECT login FROM tbl_admin WHERE admin = {$admin}";
									$res_admin = pg_query($con, $sql_admin);

									$usuario = pg_fetch_result($res_admin, 0, "login");

									echo "<tr calss='tac'>";
										echo "<td>".$mes."</td>";
										echo "<td>".$ano."</td>";
										echo "<td>".$usuario."</td>";
										echo "<td>".$produto_referencia."</td>";
										echo "<td>".$produto_descricao."</td>";
										echo "<td>
												<div>
													<input type='text' id='qtde_venda_$producao' value='$qtde_vendida' class='span2' /> <br /> 
													<button type='button' onclick='atualiza_qtde_venda($producao)' class='btn btn-small span2' style='margin-left: 0px;'>Alterar Qtde. Vendida</button> 
												</div>
												<div style='clear: both;'></div>
												<div class='box_msg_$producao'></div>
											</td>";
										echo "<td class='tac'><button type='button' class='btn btn-small btn-success' onclick='window.parent.alterar($producao);'>Alterar</button></td>";
									echo "</tr>";

									$total += $qtde_vendida;

								}
							?>
						</tbody>
						<tfoot>
							<tr>
								<td style="text-align: right;" colspan="5"><h4>Total de PG</h4></td>
								<td colspan="2"><h4><?php echo $total; ?></h4></td>
							</tr>
						</tfoot>
					</table>

					<br />

					<div id='gerar_excel' class="btn_excel">
				        <input type="hidden" id="jsonPOST" value='<?php echo json_encode(array("mes" => $mes, "ano" => $ano)); ?>' />
				        <span><img src="imagens/excel.png" /></span>
				        <span class="txt">Gerar Arquivo Excel</span>
				    </div>

				    <br />

					<?php

				}else{
					?>
					<br />
					<div class="alert alert-warning text-center" style="margin-left: 10px; margin-right: 10px;">
		                <h4>Nenhum resultado encontrado</h4>
		            </div>
					<?php
				}

			?>

		</div>

	</body>
</html>