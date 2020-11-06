<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];


	if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				WHERE fabrica_i = {$login_fabrica}
				AND (
                  	(UPPER(referencia) = UPPER('{$produto_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$produto_descricao}'))
                )";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Produto não encontrado";
			$msg_erro["campos"][] = "produto";
		} else {
			$produto = pg_fetch_result($res, 0, "produto");
		}
	}

	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

            $d1 = date('Y-m-d', strtotime($aux_data_inicial . ' + 3 months'));
            $dt1 = new DateTime($d1);
            $dt2 = new DateTime($aux_data_final);

            $diff = date_diff( $dt2, $dt1);

            if($diff->invert == 1){
                $msg_erro["msg"][] = "Intervalo de Datas deve ser menor que 90 dias";
            }
			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if (!count($msg_erro["msg"])) {
		if (!empty($produto)){
			$cond_produto = " AND tbl_produto.produto = '{$produto}' ";
		}

		if (!empty($posto)) {
			$cond_posto = " AND tbl_hd_chamado_extra.posto = {$posto} ";
		}

		$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";

		$sql = "
                  SELECT DISTINCT tbl_hd_chamado.hd_chamado,
                         tbl_hd_chamado.hd_chamado_anterior,
                         tbl_defeito_reclamado.descricao as defeito_reclamado,
                         tbl_hd_chamado_extra.serie,
                         tbl_hd_chamado_extra.nome,
                         tbl_hd_chamado_extra.nota_fiscal,
                         tbl_cidade.estado,
                         tbl_cidade.nome as cidade_nome,
                         tbl_produto.referencia,
                         tbl_produto.descricao,
                
                         tbl_posto.nome as posto_nome,
                         tbl_posto.cnpj,
                         tbl_admin.nome_completo as atendente,
                         tbl_numero_serie.data_fabricacao,
                         tbl_hd_chamado.categoria as tipo_atendimento,
                         (SELECT tbl_defeito_reclamado.descricao 
                          FROM   tbl_hd_chamado_extra 
                          INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
                          WHERE tbl_hd_chamado_extra.hd_chamado = hd_chamado_anterior) as defeito_anterior
                  FROM tbl_hd_chamado_extra
                  INNER JOIN tbl_hd_chamado on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                  LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
                  LEFT JOIN tbl_hd_chamado_item ON  tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                  LEFT JOIN tbl_tipo_atendimento on tbl_tipo_atendimento.tipo_atendimento = tbl_hd_chamado_extra.tipo_atendimento
                  LEFT JOIN tbl_posto_fabrica on tbl_posto_fabrica.fabrica = tbl_hd_chamado.fabrica AND
                                                 tbl_posto_fabrica.posto = tbl_hd_chamado_extra.posto
                  LEFT JOIN tbl_posto on tbl_posto.posto = tbl_hd_chamado_extra.posto AND tbl_posto.posto <> 6359
                  LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto 
                  LEFT JOIN tbl_numero_serie on tbl_numero_serie.produto = tbl_produto.produto AND
                                                tbl_numero_serie.serie = tbl_hd_chamado_extra.serie AND
                                                tbl_numero_serie.fabrica = {$login_fabrica}
                                                
                  INNER JOIN tbl_admin on tbl_admin.admin = tbl_hd_chamado.admin
                  LEFT JOIN tbl_cidade on tbl_cidade.cidade = tbl_hd_chamado_extra.cidade
                  WHERE tbl_hd_chamado.fabrica = {$login_fabrica} AND
                        UPPER(tbl_hd_chamado_item.status_item) LIKE '%TROCA%' AND
                        tbl_hd_chamado_item.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                    
                  {$cond_posto}
				  {$cond_produto}
				  {$limit}";
        //echo nl2br($sql);
		$resSubmit = pg_query($con, $sql);
	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_protocolos_atendimento-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='9' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE PROTOCOLOS DE ATENDIMENTO
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Chamado</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cód. Produto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Desc. Produto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito Apresentado</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Série</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Fabricação</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>NF</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Consumidor</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atendente</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Reincidência</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito </th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo Atendimento </th>
						</tr>
					</thead>
					<tbody>
			";
			fwrite($file, $thead);

			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
				$hd_chamado          = pg_fetch_result($resSubmit, $i, 'hd_chamado');                   
				$hd_chamado_anterior = pg_fetch_result($resSubmit, $i, 'hd_chamado_anterior');                   
				$defeito_reclamado	 = pg_fetch_result($resSubmit, $i, 'defeito_reclamado');                   
				$serie               = pg_fetch_result($resSubmit, $i, 'serie');                   
                if($nota_fiscal == "null"){
                    $nota_fiscal = "";
                }

				$nome_cliente		 = pg_fetch_result($resSubmit, $i, 'nome');                   
				$estado              = pg_fetch_result($resSubmit, $i, 'estado');                   
				$nome_cidade		 = pg_fetch_result($resSubmit, $i, 'cidade_nome');                   
				$produto_referencia	 = pg_fetch_result($resSubmit, $i, 'referencia');                   
                $produto_descricao   = pg_fetch_result($resSubmit, $i, 'descricao' ); 
				
                $posto_nome          = pg_fetch_result($resSubmit, $i, 'posto_nome' ); 
				$cnpj                = pg_fetch_result($resSubmit, $i, 'cnpj' ); 
				$atendente           = pg_fetch_result($resSubmit, $i, 'atendente' ); 
                $data_fabricacao     = pg_fetch_result($resSubmit, $i, 'data_fabricacao' );
                if(!empty($data_fabricacao)){
                    $data_fabricacao     = date("d/m/Y",strtotime(pg_fetch_result($resSubmit, $i, 'data_fabricacao' )));
                }
				$tipo_atendimento    = pg_fetch_result($resSubmit, $i, 'tipo_atendimento' ); 

				$defeito_anterior     = pg_fetch_result($resSubmit, $i, 'defeito_anterior' ); 
                $body .="
						<tr>
							<td nowrap align='center' valign='top'>{$hd_chamado}</td>
							<td nowrap align='center' valign='top'>{$produto_referencia}</td>
							<td nowrap align='center' valign='top'>{$produto_descricao}</td>
							<td nowrap align='center' valign='top'>{$defeito_reclamado}</td>
							<td nowrap align='left' valign='top'>  {$serie}</td>
							<td nowrap align='center' valign='top'>{$data_fabricacao}</td>
				
	                        <td nowrap align='left' valign='top'>{$nota_fiscal}</td>
	                        <td nowrap align='left' valign='top'>{$nome_cliente}</td>
	                        <td nowrap align='left' valign='top'>{$nome_cidade}</td>
	                        <td nowrap align='left' valign='top'>{$estado}</td>
	                        <td nowrap align='left' valign='top'>{$cnpj} - {$posto_nome}</td
>	                        <td nowrap align='left' valign='top'>{$atendente}</td>
	                        <td nowrap align='left' valign='top'>{$hd_chamado_anterior}</td>
	                        <td nowrap align='left' valign='top'>{$defeito_anterior}</td>
	                        <td nowrap align='left' valign='top'>{$tipo_atendimento}</td>
						</tr>";
			}

			fwrite($file, $body);
			fwrite($file, "
						<tr>
							<th colspan='9' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
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
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE OS x Atendimentos";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<script language="javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

	});

	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

	function retorna_peca(retorno){
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
    }

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
									<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'>Ref. Produto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'>Descrição Produto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
</div>

<?php
if (isset($resSubmit)) {
		if (pg_num_rows($resSubmit) > 0) {
			echo "<br />";

			if (pg_num_rows($resSubmit) > 500) {
				$count = 500;
				?>
				<div id='registro_max'>
					<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
				</div>
			<?php
			} else {
				$count = pg_num_rows($resSubmit);
			}
		?>
			<table id="relatorio_protocolos_atendimento" class='table table-striped table-bordered table-hover table-large' >
				<thead>
					<tr class='titulo_coluna' >
                      <th>Chamado            </th>
                      <th>Cód. Produto       </th>
                      <th>Desc. Produto      </th>
                      <th>Defeito Reclamado  </th>
                      <th>Série              </th>
                      <th>Data Fabricação    </th>
                      <th>NF                 </th>              
                      <th>Consumidor         </th>
                      <th>Cidade             </th>
                      <th>Estado             </th>                   
                      <th>Posto</th>
                      <th>Atendente          </th>
                      <th>Reincidência       </th>
                      <th>Defeito</th>
                      <th>Tipo Atendimento</th>
					</tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0; $i < $count; $i++) {


                        $hd_chamado          = pg_fetch_result($resSubmit, $i, 'hd_chamado');                   
                        $hd_chamado_anterior = pg_fetch_result($resSubmit, $i, 'hd_chamado_anterior');                   
                        $defeito_reclamado	 = pg_fetch_result($resSubmit, $i, 'defeito_reclamado');                   
                        $serie               = pg_fetch_result($resSubmit, $i, 'serie');                   
                        $nota_fiscal         = pg_fetch_result($resSubmit, $i, 'nota_fiscal');                   
                        if($nota_fiscal == "null"){
                            $nota_fiscal = "";
                        }
                        $nome_cliente		 = pg_fetch_result($resSubmit, $i, 'nome');                   
                        $estado              = pg_fetch_result($resSubmit, $i, 'estado');                   
                        $nome_cidade		 = pg_fetch_result($resSubmit, $i, 'cidade_nome');                   
                        $produto_referencia	 = pg_fetch_result($resSubmit, $i, 'referencia');                   
                        $produto_descricao   = pg_fetch_result($resSubmit, $i, 'descricao' ); 
				
                        $posto_nome          = pg_fetch_result($resSubmit, $i, 'posto_nome' ); 
                        $cnpj                = pg_fetch_result($resSubmit, $i, 'cnpj' ); 
                        $atendente           = pg_fetch_result($resSubmit, $i, 'atendente' );
                        $data_fabricacao     = pg_fetch_result($resSubmit, $i, 'data_fabricacao' );
                        if(!empty($data_fabricacao)){
                            $data_fabricacao     = date("d/m/Y",strtotime(pg_fetch_result($resSubmit, $i, 'data_fabricacao' )));
                        } 

                        
                        $tipo_atendimento    = pg_fetch_result($resSubmit, $i, 'tipo_atendimento' ); 
                        $defeito_anterior     = pg_fetch_result($resSubmit, $i, 'defeito_anterior' ); 
						$body = "<tr>
							<td nowrap class='tac'>{$hd_chamado}</td>
							<td nowrap class='tac'>{$produto_referencia}</td>
							<td nowrap class='tac'>{$produto_descricao}</td>
							<td nowrap class='tac'>{$defeito_reclamado}</td>
							<td nowrap class='tac'>{$serie}</td>
							<td nowrap class='tac'>".$data_fabricacao."</td>
	                        <td nowrap class='tac'>{$nota_fiscal}</td>
							<td nowrap class='tac'>{$nome_cliente}</td>
							<td nowrap class='tac'>{$nome_cidade}</td>
							<td nowrap class='tac'>{$estado}</td>
							<td nowrap class='tac'>{$cnpj} - {$posto_nome}</td>
							<td nowrap class='tac'>{$atendente} </td>
							<td nowrap class='tac'><a href='callcenter_interativo_new.php?callcenter={$hd_chamado_anterior}'>$hd_chamado_anterior</a></td>
							<td nowrap class='tac'>{$defeito_anterior}</td>
							<td nowrap class='tac'>{$tipo_atendimento}</td>
						</tr>";
						echo $body;
					}
					?>
				</tbody>
			</table>

			<?php 
			if ($count > 50) {
			?>
				<script>
					$.dataTableLoad({ table: "#relatorio_protocolos_atendimento" });
				</script>
			<?php
			}
			?>

			<br />

			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
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
