<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

### NOVO ##

if ($_POST["btn_acao"] == "submit") {
	$linha   = $_POST['linha'];
	$familia = $_POST['familia'];
	$status  = $_POST['status_rede'];

	if(!empty($linha)){
		$cond_linha = " AND tbl_produto.linha = $linha ";
	}

	if(!empty($familia)){
		$cond_familia = " AND tbl_produto.familia = $familia ";
	}

    if($status == "todos"){
        $cond_status = "";
    }else{
        $cond_status = " AND tbl_produto.ativo = '$status' ";
    }

	if(empty($_POST['gerar_excel']) and empty($_POST['gerar_csv'])) {
		$limit = " LIMIT 500 ";
	}

	$sql = "SELECT  tbl_produto.produto,
						tbl_produto.referencia AS produto_referencia,
						tbl_produto.descricao AS produto_descricao,
						tbl_produto.garantia AS prazo_garantia,
						tbl_produto.mao_de_obra AS valor_mao_obra,
						tbl_produto.valor_troca_gas AS valor_recarga_gas,
						tbl_produto.ativo AS status_rede,
						tbl_produto.origem,
						tbl_produto.voltagem,
						tbl_produto.lista_troca,
						tbl_produto.numero_serie_obrigatorio,
						tbl_produto.validar_serie AS validar_serial,
						tbl_produto.uso_interno_ativo AS status_uso_interno,
						tbl_produto.intervencao_tecnica,
						tbl_produto.produto_principal,
						tbl_produto.troca_obrigatoria,
						tbl_produto.radical_serie,
						tbl_produto.radical_serie2,
						tbl_produto.radical_serie3,
						tbl_produto.radical_serie4,
						tbl_produto.radical_serie5,
						tbl_produto.radical_serie6,
						tbl_marca.nome AS marca,
						tbl_produto_fornecedor.nome AS fornecedor,
						tbl_produto_idioma.descricao AS fornecedor_descricao,
						tbl_linha.nome AS nome_linha,
						tbl_familia.descricao AS nome_familia,
						tbl_produto_valida_serie.mascara AS mascaras
					FROM tbl_produto
					LEFT JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca AND tbl_marca.fabrica = $login_fabrica
					LEFT JOIN tbl_produto_fornecedor USING (produto_fornecedor)
					LEFT JOIN tbl_produto_idioma ON tbl_produto.produto = tbl_produto_idioma.produto
					LEFT JOIN tbl_linha USING (linha)
                    LEFT JOIN tbl_familia USING (familia)
					LEFT JOIN tbl_produto_valida_serie ON tbl_produto.produto = tbl_produto_valida_serie.produto AND tbl_produto_valida_serie.fabrica = $login_fabrica
					WHERE tbl_produto.fabrica_i = $login_fabrica
					$cond_familia
					$cond_status
					$cond_linha
					ORDER BY tbl_produto.descricao
					$limit";
	  $resSubmit = pg_query($con, $sql);

	### BAIXAR EXCELL ###
	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$total_registros = 0;
			$data = date("d-m-Y-H:i");
			$count_excel = count($resultados);
			$fileName = "relatorio_de_produtos_cadastrados-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");

			$thead = "
				<table border='1'>
				<thead>
				<tr>
				<th colspa='11' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
				RELATÓRIO DE PRODUTOS CADASTRADOS
				</th>
				</tr>
				<tr>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Referência do Produto</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição do Produto</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Prazo de Garantia</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Valor da Mão de Obra Posto</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Valor Recarga de Gás</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Linha</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Familia</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Origem</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Fornecedor do Produto</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição do Fornecedor</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Voltagem</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status Rede</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status Uso Interno</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Marca</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Lista de Troca</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Intervenção Técnica</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Numero de Série Obrigatório</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto Principal</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Troca Obrigatória</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Validar Serial</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Radicais</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Mascaras</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data de Cadastro da Vista Explodida</th>
				</tr>
				</thead>
				<tbody>
				";
			fwrite($file, $thead);

			while ($linha = pg_fetch_assoc($resSubmit)) {
				$total_registros++;
				$radicais = array();
				$radicais = array($linha['radical_serie2'],$linha['radical_serie3'],$linha['radical_serie4'],$linha['radical_serie5'],$linha['radical_serie6']);

				$radical = $linha['radical_serie'];
				$produto = $linha['produto'];
                
				if(!empty($produto)) {
					$sqlv = " SELECT to_char(data,'DD/MM/YYYY') as data_vista_explodida
                        FROM tbl_comunicado
                        LEFT JOIN tbl_comunicado_produto USING(comunicado)
                        WHERE tbl_comunicado.produto = $produto 
                        AND fabrica = $login_fabrica
                        AND tbl_comunicado.tipo ='Vista Explodida' 
                        ORDER BY data DESC 
                        LIMIT 1" ;
					$resv = pg_query($con, $sqlv);
					if(pg_num_rows($resv) > 0) {
						$linha['data_vista_explodida'] = pg_fetch_result($resv,0, 'data_vista_explodida');
					}else{
						$linha['data_vista_explodida'] = null;
					}
                }

				$linha['origem']                   = ($linha['origem'] == "Imp") ? "Importado" : "Nacional";
				$linha['status_rede']              = ($linha['status_rede'] == "t") ? "Ativo" : "Inativo";
				$linha['status_uso_interno']       = ($linha['status_uso_interno']== "t") ? "Ativo" : "Inativo";
				$linha['lista_troca']              = ($linha['lista_troca'] == "t") ? "Sim" : "Não";
				$linha['intervencao_tecnica']      = ($linha['intervencao_tecnica'] == "t") ? "Sim" : "Não";
				$linha['numero_serie_obrigatorio'] = ($linha['numero_serie_obrigatorio'] == "t") ? "Sim" : "Não";
				$linha['produto_principal']        = ($linha['produto_principal'] == "t") ? "Sim" : "Não";
				$linha['troca_obrigatoria']        = ($linha['troca_obrigatoria'] == "t") ? "Sim" : "Não";
				$linha['validar_serial']           = ($linha['validar_serial'] == "t") ? "Sim" : "Não";
				$linha['valor_mao_obra']           = number_format($linha['valor_mao_obra'],2,',','.');
				$linha['valor_recarga_gas']        = number_format($linha['valor_recarga_gas'],2,',','.');

				$body = "<tr>
					<td align='left'>".$linha['produto_referencia']."</td>
					<td align='left'>".$linha['produto_descricao']."</td>
					<td align='center'>".$linha['prazo_garantia']."</td>
					<td align='center'>".number_format($linha['valor_mao_obra'],2,',','.')."</td>
					<td align='center'>".number_format($linha['valor_recarga_gas'],2,',','.')."</td>
					<td>".$linha['nome_linha']."</td>
					<td>".$linha['nome_familia']."</td>
					<td>".$linha['origem']."</td>
					<td>".$linha['fornecedor']."</td>
					<td>".$linha['fornecedor_descricao']."</td>
					<td>".$linha['voltagem']."</td>
					<td align='center'>".$linha['status_rede']."</td>
					<td align='center'>".$linha['status_uso_interno']."</td>
					<td>".$linha['marca']."</td>
					<td align='center'>".$linha['lista_troca']."</td>
					<td align='center'>".$linha['intervencao_tecnica']."</td>
					<td align='center'>".$linha['numero_serie_obrigatorio']."</td>
					<td align='center'>".$linha['produto_principal']."</td>
					<td align='center'>".$linha['troca_obrigatoria']."</td>
					<td align='center'>".$linha['validar_serial']."</td>
					<td>".$radical."</td>
					<td>".$linha['mascaras']."</td>
					<td align='center'>".$linha['data_vista_explodida']."</td>
					</tr>";

				foreach($radicais AS $radical){
					if(!empty($radical)){
						$total_registros++;
						$body .= "<tr>
							<td align='left'>".$linha['produto_referencia']."</td>
							<td align='left'>".$linha['produto_descricao']."</td>
							<td align='center'>".$linha['prazo_garantia']."</td>
							<td align='center'>".number_format($linha['valor_mao_obra'],2,',','.')."</td>
							<td align='center'>".number_format($linha['valor_recarga_gas'],2,',','.')."</td>
							<td>".$linha['nome_linha']."</td>
							<td>".$linha['nome_familia']."</td>
							<td>".$linha['origem']."</td>
							<td>".$linha['fornecedor']."</td>
							<td>".$linha['fornecedor_descricao']."</td>
							<td>".$linha['voltagem']."</td>
							<td align='center'>".$linha['status_rede']."</td>
							<td align='center'>".$linha['status_uso_interno']."</td>
							<td>".$linha['marca']."</td>
							<td align='center'>".$linha['lista_troca']."</td>
							<td align='center'>".$linha['intervencao_tecnica']."</td>
							<td align='center'>".$linha['numero_serie_obrigatorio']."</td>
							<td align='center'>".$linha['produto_principal']."</td>
							<td align='center'>".$linha['troca_obrigatoria']."</td>
							<td align='center'>".$linha['validar_serial']."</td>
							<td>".$radical."</td>
							<td>".$linha['mascaras']."</td>
							<td align='center'>".$linha['data_vista_explodida']."</td>
							</tr>";
					}
				}
				fwrite($file, $body);
			}
			fwrite($file, "
				<tr>
				<th colspan='9' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".$count_excel." registros</th>
				</tr>
				</tbody>
				</table>
				");

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
		}

		exit;
	}
	### FIM EXCELL ###

	### BAIXAR CSV ###
	if ($_POST["gerar_csv"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$total_registros = 0;
			//$resultados = pg_fetch_all($resSubmit);
			$data = date("d-m-Y-H:i");
			$fileName = "relatorio_de_produtos_cadastrados-{$data}.csv";

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "Referência do Produto;Descrição do Produto;Prazo de Garantia;Valor da Mão de Obra Posto;Valor Recarga de Gás;Linha;Familia;Origem;Fornecedor do Produto;Descrição do Fornecedor;Voltagem;Status Rede;Status Uso Interno;Marca;Lista de Troca;Intervenção Técnica;Numero de Série Obrigatório;Produto Principal;Troca Obrigatória;Validar Serial;Radicais;Mascaras;Data de Cadastro da Vista Explodida\n";
			fwrite($file, $thead);

			//foreach ($resultados AS $linha){
			while ($linha = pg_fetch_assoc($resSubmit)) {
				$total_registros++;
				$radicais = array();
				$radicais = array($linha['radical_serie2'],$linha['radical_serie3'],$linha['radical_serie4'],$linha['radical_serie5'],$linha['radical_serie6']);
				$produto = $linha['produto'];
				if(!empty($produto)) {
					$sqlv = " SELECT to_char(data,'DD/MM/YYYY') as data_vista_explodida
							FROM tbl_comunicado
							LEFT JOIN tbl_comunicado_produto USING(comunicado)
							WHERE tbl_comunicado.produto = $produto 
							AND fabrica = $login_fabrica
							AND tbl_comunicado.tipo ='Vista Explodida' order by data desc limit 1" ;
					$resv = pg_query($con, $sqlv);
					if(pg_num_rows($resv) > 0) {
						$linha['data_vista_explodida'] = pg_fetch_result($resv,0, 'data_vista_explodida');
					}else{
						$linha['data_vista_explodida'] = null;
					}

				}
				$radical = $linha['radical_serie'];

				$linha['origem']                   = ($linha['origem'] == "Imp") ? "Importado" : "Nacional";
				$linha['status_rede']              = ($linha['status_rede'] == "t") ? "Ativo" : "Inativo";
				$linha['status_uso_interno']       = ($linha['status_uso_interno']== "t") ? "Ativo" : "Inativo";
				$linha['lista_troca']              = ($linha['lista_troca'] == "t") ? "Sim" : "Não";
				$linha['intervencao_tecnica']      = ($linha['intervencao_tecnica'] == "t") ? "Sim" : "Não";
				$linha['numero_serie_obrigatorio'] = ($linha['numero_serie_obrigatorio'] == "t") ? "Sim" : "Não";
				$linha['produto_principal']        = ($linha['produto_principal'] == "t") ? "Sim" : "Não";
				$linha['troca_obrigatoria']        = ($linha['troca_obrigatoria'] == "t") ? "Sim" : "Não";
				$linha['validar_serial']           = ($linha['validar_serial'] == "t") ? "Sim" : "Não";
				$linha['valor_mao_obra']           = number_format($linha['valor_mao_obra'],2,',','.');
				$linha['valor_recarga_gas']        = number_format($linha['valor_recarga_gas'],2,',','.');

				$body = "{$linha['produto_referencia']};{$linha['produto_descricao']};{$linha['prazo_garantia']};{$linha['valor_mao_obra']};{$linha['valor_recarga_gas']};{$linha['nome_linha']};{$linha['nome_familia']};{$linha['origem']};{$linha['fornecedor']};{$linha['fornecedor_descricao']};{$linha['voltagem']};{$linha['status_rede']};{$linha['status_uso_interno']};{$linha['marca']};{$linha['lista_troca']};{$linha['intervencao_tecnica']};{$linha['numero_serie_obrigatorio']};{$linha['produto_principal']};{$linha['troca_obrigatoria']};{$linha['validar_serial']};{$linha['radical_serie']};{$linha['mascaras']};{$linha['data_vista_explodida']}\n";

				foreach($radicais AS $radical){
					if(!empty($radical)){
						$total_registros++;
						$body .= "{$linha['produto_referencia']};{$linha['produto_descricao']};{$linha['prazo_garantia']};{$linha['valor_mao_obra']};{$linha['valor_recarga_gas']};{$linha['nome_linha']};{$linha['nome_familia']};{$linha['origem']};{$linha['fornecedor']};{$linha['fornecedor_descricao']};{$linha['voltagem']};{$linha['status_rede']};{$linha['status_uso_interno']};{$linha['marca']};{$linha['lista_troca']};{$linha['intervencao_tecnica']};{$linha['numero_serie_obrigatorio']};{$linha['produto_principal']};{$linha['troca_obrigatoria']};{$linha['validar_serial']};{$radical};{$linha['mascaras']};{$linha['data_vista_explodida']}\n";

					}
				}
				fwrite($file, $body);
			}

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
		}

		exit;
	}
	### FIM CSV ###
}
###########

$layout_menu = "gerencia";
$title = "RELATÓRIO DE PRODUTOS CADASTRADOS";
include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});
 	$(function() {
    var table = new Object();
    table['table'] = '#resultado_produtos_cadastrados';
    table['type'] = 'full';
    $.dataTableLoad(table);
	});
</script>

<!-- FORM NOVO -->
<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<!-- Retirado no HD-2393979 Interação 100
<div class="row">
	<b class="obrigatorio pull-right">  Obs* Para baixar o Arquivo em CSV escolher a opção e realizar a pesquisa </b>
</div>
-->
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<div class="row-fluid">
		<div class="span1"></div>

		<!-- Linha -->
		<div class="span3">
			<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='linha'>Linha</label>
				<div class='controls controls-row'>
					<div class="span3">
						<select name="linha" id="linha" style='width:150px;'>
							<option value=""></option>
							<?php
							$sql = "SELECT linha, nome
									FROM tbl_linha
									WHERE fabrica = $login_fabrica
									AND ativo";
							$res = pg_query($con,$sql);

							foreach (pg_fetch_all($res) as $key) {
								$selected_linha = ( isset($linha) and ($linha == $key['linha']) ) ? "SELECTED" : '' ;

							?>
								<option value="<?php echo $key['linha']?>" <?php echo $selected_linha ?> >

									<?php echo $key['nome']?>

								</option>
							<?php
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<!-- Fim Linha -->

		<!-- Familia -->
		<div class='span4'>
			<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='familia'>Familia</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="familia" id="familia">
							<option value=""></option>
							<?php

								$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica and ativo  IS TRUE order by descricao";
								$res = pg_query($con,$sql);
								foreach (pg_fetch_all($res) as $key) {

									$selected_familia = ( isset($familia) and ($familia == $key['familia']) ) ? "SELECTED" : '' ;

								?>
									<option value="<?php echo $key['familia']?>" <?php echo $selected_familia ?> >
										<?php echo $key['descricao']?>
									</option>


								<?php
								}

							?>
						</select>
					</div>
					<div class='span2'></div>
				</div>
			</div>
		</div>
		<!-- Fim Familia -->

		<!-- Status Rede -->
		<div class="span3">
			<div class='control-group <?=(in_array("status_rede", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='status_rede'>Status Rede</label>
				<div class='controls controls-row'>
					<div class="span3">
						<select name="status_rede" id="status_rede" style='width:150px;'>
              <option value="todos" <?php echo ($status == "todos") ? "SELECTED" : "";?>>Todos</option>
							<option value="t" <?php echo ($status == "t") ? "SELECTED" : "";?>>Ativo</option>
          		<option value="f" <?php echo ($status == "f") ? "SELECTED" : "";?>>Inativo</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<!-- Fim Status Rede -->
	</div>
	<br />
	<!-- <div class="row-fluid">
		<div class="span1"></div>
		<div class="span3">
			<label class="radio">
				<input type="radio" name="arq" value="xls" checked>
				Arquivo XLS
			</label>
		</div>

		<div class="span3">
			<label class="radio">
				<input type="radio" name="arq" value="csv" <?php echo ($_POST['arq'] == "csv") ? "checked" : ""; ?>>
				Arquivo CSV
			</label>
		</div>
	</div> -->
	<p>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
</div>
<!-- ////FIM//// -->

<?php
if (isset($resSubmit)) {
	if (pg_num_rows($resSubmit) > 0) {
		echo "<br />";
		$total_registros = 0;
		$resultados = pg_fetch_all($resSubmit);
		$linhas = count($resultados);
    #echo $linhas;exit;
    if($linhas > 500) {
			?>
			<div id='registro_max'>
				<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
			</div>
		<?php
		}

		?>
		<table style="width:3000px !important;" id="resultado_produtos_cadastrados" class='table table-striped table-bordered' >
			<thead>
				<tr class="titulo_coluna">
					<th>Referência do Produto</th>
					<th>Descrição do Produto</th>
					<th width="50px;">Prazo de Garantia</th>
					<th width="76px;">Valor da Mão de Obra Posto</th>
					<th width="75px;">Valor Recarga de Gás</th>
					<th>Linha</th>
					<th>Familia</th>
					<th>Origem</th>
					<th>Fornecedor do Produto</th>
					<th>Descrição do Fornecedor</th>
					<th>Voltagem</th>
					<th>Status Rede</th>
					<th>Status Uso Interno</th>
					<th>Marca</th>
					<th>Lista de Troca</th>
					<th>Intervenção Técnica</th>
					<th>Numero de Série Obrigatório</th>
					<th>Produto Principal</th>
					<th>Troca Obrigatória</th>
					<th>Validar Serial</th>
					<th>Radicais</th>
					<th>Mascaras</th>
					<th>Data de Cadastro da Vista Explodida</th>
				</tr>
			</thead>
			<tbody>
				<?php
          foreach ($resultados AS $linha){
            $total_registros++;

            if($total_registros > 500){
             break;
            }

            $radicais = array();
            $radicais = array($linha['radical_serie2'],$linha['radical_serie3'],$linha['radical_serie4'],$linha['radical_serie5'],$linha['radical_serie6']);

            $radical = $linha['radical_serie'];

			$produto = $linha['produto'];
			if(!empty($produto)) {
				$sqlv = " SELECT to_char(data,'DD/MM/YYYY') as data_vista_explodida
						FROM tbl_comunicado
						LEFT JOIN tbl_comunicado_produto USING(comunicado)
						WHERE (tbl_comunicado.produto = $produto or tbl_comunicado_produto.produto = $produto)
						AND fabrica = $login_fabrica
						AND tbl_comunicado.tipo ='Vista Explodida' order by data desc limit 1" ;
				$resv = pg_query($con, $sqlv);
				if(pg_num_rows($resv) > 0) {
					$linha['data_vista_explodida'] = pg_fetch_result($resv,0, 'data_vista_explodida');
				}else{
					$linha['data_vista_explodida'] = null;
				}

			}


            $linha['origem']                   = ($linha['origem'] == "Imp") ? "Importado" : "Nacional";
            $linha['status_rede']              = ($linha['status_rede'] == "t") ? "Ativo" : "Inativo";
            $linha['status_uso_interno']       = ($linha['status_uso_interno']== "t") ? "Ativo" : "Inativo";
            $linha['lista_troca']              = ($linha['lista_troca'] == "t") ? "Sim" : "Não";
            $linha['intervencao_tecnica']      = ($linha['intervencao_tecnica'] == "t") ? "Sim" : "Não";
            $linha['numero_serie_obrigatorio'] = ($linha['numero_serie_obrigatorio'] == "t") ? "Sim" : "Não";
            $linha['produto_principal']        = ($linha['produto_principal'] == "t") ? "Sim" : "Não";
            $linha['troca_obrigatoria']        = ($linha['troca_obrigatoria'] == "t") ? "Sim" : "Não";
            $linha['validar_serial']           = ($linha['validar_serial'] == "t") ? "Sim" : "Não";
            $linha['valor_mao_obra']           = number_format($linha['valor_mao_obra'],2,',','.');
            $linha['valor_recarga_gas']        = number_format($linha['valor_recarga_gas'],2,',','.');

            $body .= "<tr>
                <td align='left'>".$linha['produto_referencia']."</td>
                <td align='left'>".$linha['produto_descricao']."</td>
                <td align='center'>".$linha['prazo_garantia']."</td>
                <td align='center'>".number_format($linha['valor_mao_obra'],2,',','.')."</td>
                <td align='center'>".number_format($linha['valor_recarga_gas'],2,',','.')."</td>
                <td>".$linha['nome_linha']."</td>
                <td>".$linha['nome_familia']."</td>
                <td>".$linha['origem']."</td>
                <td>".$linha['fornecedor']."</td>
                <td>".$linha['fornecedor_descricao']."</td>
                <td>".$linha['voltagem']."</td>
                <td align='center'>".$linha['status_rede']."</td>
                <td align='center'>".$linha['status_uso_interno']."</td>
                <td>".$linha['marca']."</td>
                <td align='center'>".$linha['lista_troca']."</td>
                <td align='center'>".$linha['intervencao_tecnica']."</td>
                <td align='center'>".$linha['numero_serie_obrigatorio']."</td>
                <td align='center'>".$linha['produto_principal']."</td>
                <td align='center'>".$linha['troca_obrigatoria']."</td>
                <td align='center'>".$linha['validar_serial']."</td>
                <td>".$radical."</td>
                <td>".$linha['mascaras']."</td>
                <td align='center'>".$linha['data_vista_explodida']."</td>
            </tr>";


	          foreach($radicais AS $radical){
	            if(!empty($radical)){
	              $total_registros++;

                $body .= "<tr>
                            <td align='left'>".$linha['produto_referencia']."</td>
                            <td align='left'>".$linha['produto_descricao']."</td>
                            <td align='center'>".$linha['prazo_garantia']."</td>
                            <td align='center'>".number_format($linha['valor_mao_obra'],2,',','.')."</td>
                            <td align='center'>".number_format($linha['valor_recarga_gas'],2,',','.')."</td>
                            <td>".$linha['nome_linha']."</td>
                            <td>".$linha['nome_familia']."</td>
                            <td>".$linha['origem']."</td>
                            <td>".$linha['fornecedor']."</td>
                            <td>".$linha['fornecedor_descricao']."</td>
                            <td>".$linha['voltagem']."</td>
                            <td align='center'>".$linha['status_rede']."</td>
                            <td align='center'>".$linha['status_uso_interno']."</td>
                            <td>".$linha['marca']."</td>
                            <td align='center'>".$linha['lista_troca']."</td>
                            <td align='center'>".$linha['intervencao_tecnica']."</td>
                            <td align='center'>".$linha['numero_serie_obrigatorio']."</td>
                            <td align='center'>".$linha['produto_principal']."</td>
                            <td align='center'>".$linha['troca_obrigatoria']."</td>
                            <td align='center'>".$linha['validar_serial']."</td>
                            <td>".$radical."</td>
                            <td>".$linha['mascaras']."</td>
                            <td align='center'>".$linha['data_vista_explodida']."</td>
                          </tr>";
              }
	          }
        	}
        	echo $body;
				?>
			</tbody>
		</table>
		<?php
				$jsonPOST = excelPostToJson($_POST);
			?>
			<div class="row-fluid">
				<div class="span6">
					<div id='gerar_excel' class="btn_excel pull-right">
						<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
						<span><img src='imagens/excel.png' /></span>
						<span class="txt">Gerar Arquivo Excel</span>
					</div> <br /> <br/>
				</div>
			<?php
				$jsonPOSTcsv = csvPostToJson($_POST);
			?>
				<div class="span6">
					<div id='gerar_csv' class="btn_excel pull-left">
						<input type="hidden" id="jsonPOSTcsv" value='<?=$jsonPOSTcsv?>' />
						<span><img src='imagens/icon_csv.png' /></span>
						<span class="txt">Gerar Arquivo CSV</span>
					</div> <br /> <br/>
				</div>
			</div>

<?php

	}else{
		echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
	}
}

include 'rodape.php';?>

