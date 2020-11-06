<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "info_tecnica";
include 'autentica_usuario.php';
include 'traducao.php';
$posto = true;

$sql = "
		SELECT pl.linha, l.nome
		FROM tbl_posto_linha pl
		JOIN tbl_linha l ON l.linha = pl.linha AND l.fabrica = {$login_fabrica}
		AND pl.ativo IS TRUE
		AND pl.posto = {$login_posto};
	";

//Consulta para listar Manuais Britania
if($login_fabrica == 3){
	$sql = "SELECT 		tbl_linha.nome AS linha_nome,
						tbl_linha.linha,
						tbl_familia.descricao AS familia_nome,
						tbl_familia.familia,
						tbl_comunicado.comunicado,
						tbl_comunicado.extensao,
						tbl_comunicado.descricao AS titulo,
						tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_comunicado.parametros_adicionais->>'data_documento' AS data_documento
					FROM tbl_comunicado
					INNER JOIN tbl_produto USING(produto)
					INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
					INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
					INNER JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_posto_linha.posto = {$login_posto}
					WHERE tbl_comunicado.fabrica = {$login_fabrica}
					AND     tbl_comunicado.ativo IS TRUE
					AND tbl_comunicado.tipo = 'Manual de Serviço'					
					GROUP BY tbl_linha.nome,
					tbl_linha.linha,
					tbl_familia.descricao,
					tbl_familia.familia,
					tbl_comunicado.comunicado,
					tbl_comunicado.descricao,
					tbl_produto.referencia,
					tbl_produto.descricao";		
		$res = pg_query($con,$sql); 		
		$manuaisservicos = pg_fetch_all($res);
		echo pg_num_rows($manuaisservicos);
} else {
	//Consulta para listar as Vistas Explodidas
	$sql = "SELECT 		tbl_linha.nome AS linha_nome,
						tbl_linha.linha,
						tbl_familia.descricao AS familia_nome,
						tbl_familia.familia,
						tbl_comunicado.comunicado,
						tbl_comunicado.extensao,
						tbl_comunicado.descricao AS titulo,
						tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_comunicado.parametros_adicionais->>'data_documento' AS data_documento
					FROM tbl_comunicado
					left join tbl_comunicado_produto using (comunicado)
                                        JOIN tbl_produto on tbl_produto.produto in (tbl_comunicado.produto, tbl_comunicado_produto.produto)
					INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
					INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
					INNER JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_posto_linha.posto = {$login_posto}
					WHERE tbl_comunicado.fabrica = {$login_fabrica}
					AND     tbl_comunicado.ativo IS TRUE
					AND tbl_comunicado.tipo = 'Vista Explodida'
					--AND tbl_produto.familia = 5617
					GROUP BY tbl_linha.nome,
					tbl_linha.linha,
					tbl_familia.descricao,
					tbl_familia.familia,
					tbl_comunicado.comunicado,
					tbl_comunicado.descricao,
					tbl_produto.referencia,
					tbl_produto.descricao";
	$res = pg_query($con,$sql); 
	$comunicadosVE = pg_fetch_all($res);
	//Consulta para listar as Esquemas Elétricos
	$sql = "SELECT 		tbl_linha.nome AS linha_nome,
						tbl_linha.linha,
						tbl_familia.descricao AS familia_nome,
						tbl_familia.familia,
						tbl_comunicado.comunicado,
						tbl_comunicado.descricao AS titulo,
						tbl_comunicado.extensao,
						tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_comunicado.parametros_adicionais->>'data_documento' AS data_documento
					FROM tbl_comunicado
					left join tbl_comunicado_produto using (comunicado)
                                        JOIN tbl_produto on tbl_produto.produto in (tbl_comunicado.produto, tbl_comunicado_produto.produto)
					INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
					INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
					INNER JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_posto_linha.posto = {$login_posto}
					WHERE tbl_comunicado.fabrica = {$login_fabrica}
					AND     tbl_comunicado.ativo IS TRUE
					AND tbl_comunicado.tipo ILIKE 'Esquema El%'
					--AND tbl_produto.familia = 5617
					GROUP BY tbl_linha.nome,
					tbl_linha.linha,
					tbl_familia.descricao,
					tbl_familia.familia,
					tbl_comunicado.comunicado,
					tbl_comunicado.descricao,
					tbl_produto.referencia,
					tbl_produto.descricao";
	$res = pg_query($con,$sql);
	$comunicadosEE = pg_fetch_all($res);


	//Consulta para listar as Alterações Técnicas
	$sql = "SELECT 		tbl_linha.nome AS linha_nome,
						tbl_linha.linha,
						tbl_familia.descricao AS familia_nome,
						tbl_familia.familia,
						tbl_comunicado.comunicado,
						tbl_comunicado.descricao AS titulo,
						tbl_comunicado.extensao,
						tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_comunicado.parametros_adicionais->>'data_documento' AS data_documento
					FROM tbl_comunicado
					left join tbl_comunicado_produto using (comunicado)
                                         JOIN tbl_produto on tbl_produto.produto in (tbl_comunicado.produto, tbl_comunicado_produto.produto)
					INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
					INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
					INNER JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_posto_linha.posto = {$login_posto}
					WHERE tbl_comunicado.fabrica = {$login_fabrica}
					AND   tbl_comunicado.ativo IS TRUE
					AND fn_retira_especiais(tbl_comunicado.tipo) = 'Alteracoes Tecnicas'
					--AND tbl_produto.familia = 5617
					GROUP BY tbl_linha.nome,
					tbl_linha.linha,
					tbl_familia.descricao,
					tbl_familia.familia,
					tbl_comunicado.comunicado,
					tbl_comunicado.descricao,
					tbl_produto.referencia,
					tbl_produto.descricao";
	$res = pg_query($con,$sql);
	$comunicadosAT = pg_fetch_all($res);


	//Consulta para listar os Manuais Técnicas
	$sql = "SELECT 		tbl_linha.nome AS linha_nome,
						tbl_linha.linha,
						tbl_familia.descricao AS familia_nome,
						tbl_familia.familia,
						tbl_comunicado.comunicado,
						tbl_comunicado.descricao AS titulo,
						tbl_comunicado.extensao,
						tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_comunicado.parametros_adicionais->>'data_documento' AS data_documento
					FROM tbl_comunicado
					left join tbl_comunicado_produto using (comunicado)
					JOIN tbl_produto on tbl_produto.produto in (tbl_comunicado.produto, tbl_comunicado_produto.produto)
					INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
					INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
					INNER JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_posto_linha.posto = {$login_posto}
					WHERE tbl_comunicado.fabrica = {$login_fabrica}
					AND   tbl_comunicado.ativo IS TRUE
					AND fn_retira_especiais(tbl_comunicado.tipo) = 'Manual Tecnico'
					--AND tbl_produto.familia = 5617
					GROUP BY tbl_linha.nome,
					tbl_linha.linha,
					tbl_familia.descricao,
					tbl_familia.familia,
					tbl_comunicado.comunicado,
					tbl_comunicado.descricao,
					tbl_produto.referencia,
					tbl_produto.descricao";
	$res = pg_query($con,$sql);
	$comunicadosMT = pg_fetch_all($res);
}
include "cabecalho_new.php";

include "javascript_pesquisas.php";

$plugins = array(
   "shadowbox"
);

include __DIR__.'/admin/plugin_loader.php';

?>

<script type="text/javascript">
	
	$(function(){

		$(".linha").click(function(){

			let obj = $(this);
			let linha = $(obj).attr("rel");
			let tr = $(obj).parent("tbody").find("tr."+linha);
			let icone = ((obj).find("i.icone"));

			if($(tr).is(":visible")){
				$(icone).removeClass("icon-chevron-down");
				$(icone).addClass("icon-chevron-right");
				$(tr).hide();
			}else{
				$(icone).removeClass("icon-chevron-right");
				$(icone).addClass("icon-chevron-down");
				$(tr).show();
			}
		});

		$(".familia").click(function(){

			let obj = $(this);
			let familia = $(obj).attr("rel");
			let tr = $(obj).parent("tbody").find("tr."+familia);
			let icone = ((obj).find("i.icone"));
			
			if($(tr).is(":visible")){
				$(icone).removeClass("icon-chevron-down");
				$(icone).addClass("icon-chevron-right");
				$(tr).hide();
			}else{
				$(icone).removeClass("icon-chevron-right");
				$(icone).addClass("icon-chevron-down");
				$(tr).show();
			}
		});

		$("a").click(function () {
            var comunicado = $(this).attr("rel");

			$.get("verifica_s3_comunicado.php", {
                comunicado: comunicado,
                tipo: 've',
                fabrica: '<?=$login_fabrica?>'
            }, function (data) {
                if (data.length > 0) {
                	Shadowbox.init();
                    Shadowbox.open({
                        content :   data,
                        player  :   "iframe",
                        title   :   "Arquivo",
                        width   : 900
                    });
                } else {
                    alert("Arquivo não encontrado!");
                }

                $.unblockUI();
            });
        });
	});
</script>

<?
	if($login_fabrica == 3){
	?>
		<br>
		<table style='width: 1000px; margin: 0 auto; border: 0; height: auto !important'>		
			<tr>
				<td>
					<form class='form-search form-inline tc_formulario' name="frm_infotec" method="get" action="<? echo $PHP_SELF ?>">		
						<input type="hidden" name="acao">
						<input type="hidden" name="tipo" value="Manual de Serviço">
						<div class='titulo_tabela '>			
							<? echo "SELECIONE OS PARÂMETROS PARA A PESQUISA";?>
						</div>
						<br>
						<div class='row-fluid'>
							<div class='span2'></div>
							<div class='span4'>
								<label class='control-label'><? echo traduz("referencia",$con,$cook_idioma); if ($cook_idioma=='ES') echo " 1"; ?></label>
								<div class='controls controls-row'>
									<div class='input-append'>
										<input  type="text" id="referencia" name="produto_referencia">
										<span class='add-on' rel="lupa" style="cursor:pointer" name='referencia_tecnica' id='referencia_tecnica' onclick="javascript: fnc_pesquisa_produto (document.frm_infotec.produto_referencia, document.frm_infotec.produto_descricao, 'referencia', document.frm_infotec.produto_voltagem)"><i class='icon-search'></i></span>		 			
							 		</div>
							 	</div>
							</div>							
							<div class='span4'>
								<label class='control-label'><? echo traduz("descricao",$con,$cook_idioma); if ($cook_idioma=='ES') echo " 1"; ?></label>
								<div class='controls controls-row'>
									<div class='input-append'>
										<input  type="text" id="descricao" name="produto_descricao" height="700px">
										<input type="hidden" name="produto_voltagem">
										<span class='add-on' rel="lupa" style="cursor:pointer" name='descricao_tecnica' id='descricao_tecnica' onclick="javascript: fnc_pesquisa_produto (document.frm_infotec.produto_referencia, document.frm_infotec.produto_descricao, 'descricao', document.frm_infotec.produto_voltagem)"><i class='icon-search'></i></span>		 			
							 		</div>
							 	</div>
							</div>			
							<div class='span2'></div>
						</div>
						<br>
						<input type='hidden' name='btn_acao' value=''>	
						<input class="tac btn" id='btn_pesquisar' name='btn_pesquisar' type='submit' style="cursor: pointer;"  onClick="document.frm_infotec.acao.value='PESQUISAR'; document.frm_infotec.submit();" ALT="<?fecho ("pesquisar",$con,$cook_idioma);?>" border='0' value='Localizar'>	
						<br><br>
					</form>
				</td>
			</tr>
		</table>
		<!-- TABELA VISTA EXPLODIDA -->
		<table class='table table-bordered table-large' style='width: 1000px; margin: 0 auto;height: auto !important'>
			<thead>
				<tr class='titulo_tabela'><th>Manual de Serviço</th></tr>
			</thead>
			<tbody>
				<?php
					foreach ($manuaisservicos as $i => $linha) {

						if($linha['linha'] == $linha_ant) continue;

						echo "<tr class='linha' rel='{$linha['linha']}'>
								<td>
									<i class='icon-chevron-right icone'></i> 
									<button class='btn btn-link'><b>{$linha['linha_nome']}</b></button>
								</td>
							   </tr>
							   <tr style='display:none;' class='{$linha['linha']}'>
								<td>
									<table class='table'>";

									foreach ($manuaisservicos as $j => $familia) {

										if($familia['familia'] == $familia_ant || $familia['linha'] != $linha['linha']) continue;

										echo "<tr class='familia' rel='{$familia['familia']}'>
												<td>
													<i class='icon-chevron-right icone'></i> 
													<button class='btn btn-link'><b>{$familia['familia_nome']}</b></button>
												</td>
											   </tr>";

										$familia_ant = $familia['familia'];

										echo "<tr style='display:none;' class='{$familia['familia']}'>
												<td>
													<table class='table' width='100%'>
														<tr class='titulo_tabela'>
															<td>Referência Produto</td>
															<td>Descrição Produto</td>
															<td>Data Documento</td>
														</tr>";
														foreach ($manuaisservicos as $j => $produto) {

															if($produto['familia'] != $familia['familia']) continue;

															echo "<tr>
																	<td><a href='info_tecnica_visualiza_new.php?tipo=Manual de Serviço&linha={$linha['linha']}' rel='{$produto['comunicado']}' class='btn btn-link abre_comunicado'>{$produto['referencia']}</a></td> 
																	<td><a href='info_tecnica_visualiza_new.php?tipo=Manual de Serviço&linha={$linha['linha']}' rel='{$produto['comunicado']}' class='btn btn-link abre_comunicado'>{$produto['descricao']}</a></td>
																	<td>{$produto['data_documento']}</td>
																   </tr>";

															$produto_ant = $produto['referencia'];
														}
													echo "</table>
												</td>
											   </tr>";
									}

							echo "</table>
							</td>
						</tr>";

						$linha_ant = $linha['linha'];
					}
				?>
			</tbody>
		</table>
	<?
	} else {
?>

<table class='table table-bordered table-large' style='width: 1000px; margin: 0 auto;height: auto !important'>
	<thead>
		<tr class='titulo_tabela'><th><?php echo traduz('vista.explodida'); ?></th></tr>
	</thead>
	<tbody>
		<?php
			foreach ($comunicadosVE as $i => $linha) {

				if($linha['linha'] == $linha_ant) continue;

				echo "<tr class='linha' rel='{$linha['linha']}'>
						<td>
							<i class='icon-chevron-right icone'></i> 
							<button class='btn btn-link'><b>{$linha['linha_nome']}</b></button>
						</td>
					   </tr>";

				echo "<tr style='display:none;' class='{$linha['linha']}'>
						<td>
							<table class='table'>";

							foreach ($comunicadosVE as $j => $familia) {

								if($familia['familia'] == $familia_ant || $familia['linha'] != $linha['linha']) continue;

								echo "<tr class='familia' rel='{$familia['familia']}'>
										<td>
											<i class='icon-chevron-right icone'></i> 
											<button class='btn btn-link'><b>{$familia['familia_nome']}</b></button>
										</td>
									   </tr>";

								$familia_ant = $familia['familia'];

								echo "<tr style='display:none;' class='{$familia['familia']}'>
										<td>
											<table class='table' width='100%'>
												<tr class='titulo_tabela'>
													<td>Referência Produto</td>
													<td>Descrição Produto</td>";
													if ($login_fabrica == 161) {
														echo "<td>Título</td>";
													}
													echo "<td>Data Documento</td>
												</tr>";
												foreach ($comunicadosVE as $j => $produto) {

													if($produto['familia'] != $familia['familia']) continue;

													echo "<tr>
															<td><a href='javascript:void(0);' rel='{$produto['comunicado']}' class='btn btn-link abre_comunicado'>{$produto['referencia']}</a></td> 
															<td><a href='javascript:void(0);' rel='{$produto['comunicado']}' class='btn btn-link abre_comunicado'>{$produto['descricao']}</a></td>";
															if ($login_fabrica == 161) {
																echo "<td>{$produto['titulo']}</td>";
															}
													echo "	<td>{$produto['data_documento']}</td>
														   </tr>";

													$produto_ant = $produto['referencia'];

												}
											echo "</table>
										</td>
									   </tr>";
							}

					echo "</table>
					</td>
				</tr>";

				$linha_ant = $linha['linha'];
			}
		?>
	</tbody>
</table>

<br>

<table class='table table-bordered table-large' style='width: 1000px; margin: 0 auto;height: auto !important'>
	<thead>
		<tr class='titulo_tabela'><th><?php echo traduz ('esquema.eletrico'); ?></th></tr>
	</thead>
	<tbody>
		<?php
			$linha_ant = "";

			foreach ($comunicadosEE as $i => $linha) {

				if($linha['linha'] == $linha_ant) continue;

				echo "<tr class='linha' rel='{$linha['linha']}'>
						<td>
							<i class='icon-chevron-right icone'></i> 
							<button class='btn btn-link'><b>{$linha['linha_nome']}</b></button>
						</td>
					   </tr>";

				echo "<tr style='display:none;' class='{$linha['linha']}'>
						<td>
							<table class='table'>";

							$familia_ant = "";

							foreach ($comunicadosEE as $j => $familia) {

								if($familia['familia'] == $familia_ant || $familia['linha'] != $linha['linha']) continue;

								echo "<tr class='familia' rel='{$familia['familia']}'>
										<td>
											<i class='icon-chevron-right icone'></i> 
											<button class='btn btn-link'><b>{$familia['familia_nome']}</b></button>
										</td>
									   </tr>";

								$familia_ant = $familia['familia'];

								echo "<tr style='display:none;' class='{$familia['familia']}'>
										<td>
											<table class='table' width='100%'>
												<tr class='titulo_tabela'>
													<td>Referência Produto</td>
													<td>Descrição Produto</td>";
													if ($login_fabrica == 161) {
														echo "<td>Título</td>";
													}
											  echo "<td>Data Documento</td>
												</tr>";
												foreach ($comunicadosEE as $j => $produto) {

													if($produto['familia'] != $familia['familia']) continue;

													echo "<tr>
															<td><a href='#' rel='{$produto['comunicado']}' class='btn btn-link'>{$produto['referencia']}</a></td> 
															<td><a href='#' rel='{$produto['comunicado']}' class='btn btn-link'>{$produto['descricao']}</a></td>";
															if ($login_fabrica == 161) {
																echo "<td>{$produto['titulo']}</td>";
															}
													echo "	<td>{$produto['data_documento']}</td>
														   </tr>";

													$produto_ant = $produto['referencia'];

												}
											echo "</table>
										</td>
									   </tr>";
							}

					echo "</table>
					</td>
				</tr>";

				$linha_ant = $linha['linha'];
			}
		?>
	</tbody>
</table>

<br>

<table class='table table-bordered table-large' style='width: 1000px; margin: 0 auto;height: auto !important'>
	<thead>
		<tr class='titulo_tabela'><th><?php echo traduz ('alteracoes.tecnicas'); ?></th></tr>
	</thead>
	<tbody>
		<?php
			$linha_ant = "";

			foreach ($comunicadosAT as $i => $linha) {

				if($linha['linha'] == $linha_ant) continue;

				echo "<tr class='linha' rel='{$linha['linha']}'>
						<td>
							<i class='icon-chevron-right icone'></i> 
							<button class='btn btn-link'><b>{$linha['linha_nome']}</b></button>
						</td>
					   </tr>";

				echo "<tr style='display:none;' class='{$linha['linha']}'>
						<td>
							<table class='table'>";

							$familia_ant = "";

							foreach ($comunicadosAT as $j => $familia) {

								if($familia['familia'] == $familia_ant || $familia['linha'] != $linha['linha']) continue;

								echo "<tr class='familia' rel='{$familia['familia']}'>
										<td>
											<i class='icon-chevron-right icone'></i> 
											<button class='btn btn-link'><b>{$familia['familia_nome']}</b></button>
										</td>
									   </tr>";

								$familia_ant = $familia['familia'];

								echo "<tr style='display:none;' class='{$familia['familia']}'>
										<td>
											<table class='table' width='100%'>
												<tr class='titulo_tabela'>
													<td>Referência Produto</td>
													<td>Descrição Produto</td>";
													if ($login_fabrica == 161) {
														echo "<td>".traduz('titulo')."</td>";
													}
											  echo "<td>".traduz('data.documento')."</td>
												</tr>";
												foreach ($comunicadosAT as $j => $produto) {

													if($produto['familia'] != $familia['familia']) continue;

													echo "<tr>
															<td><a href='#' rel='{$produto['comunicado']}' class='btn btn-link'>{$produto['referencia']}</a></td> 
															<td><a href='#' rel='{$produto['comunicado']}' class='btn btn-link'>{$produto['descricao']}</a></td>";
															if ($login_fabrica == 161) {
																echo "<td>{$produto['titulo']}</td>";
															}
													echo "	<td>{$produto['data_documento']}</td>
														   </tr>";

													$produto_ant = $produto['referencia'];

												}
											echo "</table>
										</td>
									   </tr>";
							}

					echo "</table>
					</td>
				</tr>";

				$linha_ant = $linha['linha'];
			}
		?>
	</tbody>
</table>

<br>

<table class='table table-bordered table-large' style='width: 1000px; margin: 0 auto;height: auto !important'>
	<thead>
		<tr class='titulo_tabela'><th><?php echo traduz ('manuais.tecnicos'); ?></th></tr>
	</thead>
	<tbody>
		<?php
			$linha_ant = "";

			foreach ($comunicadosMT as $i => $linha) {

				if($linha['linha'] == $linha_ant) continue;

				echo "<tr class='linha' rel='{$linha['linha']}'>
						<td>
							<i class='icon-chevron-right icone'></i> 
							<button class='btn btn-link'><b>{$linha['linha_nome']}</b></button>
						</td>
					   </tr>";

				echo "<tr style='display:none;' class='{$linha['linha']}'>
						<td>
							<table class='table'>";

							$familia_ant = "";

							foreach ($comunicadosMT as $j => $familia) {

								if($familia['familia'] == $familia_ant || $familia['linha'] != $linha['linha']) continue;

								echo "<tr class='familia' rel='{$familia['familia']}'>
										<td>
											<i class='icon-chevron-right icone'></i> 
											<button class='btn btn-link'><b>{$familia['familia_nome']}</b></button>
										</td>
									   </tr>";

								$familia_ant = $familia['familia'];

								echo "<tr style='display:none;' class='{$familia['familia']}'>
										<td>
											<table class='table' width='100%'>
												<tr class='titulo_tabela'>
													<td>Referência Produto</td>
													<td>Descrição Produto</td>";
													if ($login_fabrica == 161) {
														echo "<td>Título</td>";
													}
											  echo "<td>Data Documento</td>
												</tr>";

												foreach ($comunicadosMT as $j => $produto) {

													if($produto['familia'] != $familia['familia']) continue;

													echo "<tr>
															<td><a href='#' rel='{$produto['comunicado']}' class='btn btn-link'>{$produto['referencia']}</a></td> 
															<td><a href='#' rel='{$produto['comunicado']}' class='btn btn-link'>{$produto['descricao']}</a></td>";
															if ($login_fabrica == 161) {
																echo "<td>{$produto['titulo']}</td>";	
															}
													echo "  <td>{$produto['data_documento']}</td>
														   </tr>";

													$produto_ant = $produto['referencia'];

												}
											echo "</table>
										</td>
									   </tr>";
							}

					echo "</table>
					</td>
				</tr>";

				$linha_ant = $linha['linha'];
			}
		?>
	</tbody>
</table>
<?php
}
include "rodape.php";
?>
