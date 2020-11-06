<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$array_estados = array(
    'AC' => 'Acre',
    'AL' => 'Alagoas',
    'AM' => 'Amazonas',
    'AP' => 'Amapá',
    'BA' => 'Bahia',
    'CE' => 'Ceara',
    'DF' => 'Distrito Federal',
    'ES' => 'Espírito Santo',
    'GO' => 'Goiás',
    'MA' => 'Maranhão',
    'MG' => 'Minas Gerais',
    'MS' => 'Mato Grosso do Sul',
    'MT' => 'Mato Grosso',
    'PA' => 'Pará',
    'PB' => 'Paraíba',
    'PE' => 'Pernambuco',
    'PI' => 'Piauí­',
    'PR' => 'Paraná',
    'RJ' => 'Rio de Janeiro',
    'RN' => 'Rio Grande do Norte',
    'RO' => 'Rondônia',
    'RR' => 'Roraima',
    'RS' => 'Rio Grande do Sul',
    'SC' => 'Santa Catarina',
    'SE' => 'Sergipe',
    'SP' => 'São Paulo',
    'TO' => 'Tocantins'
);

if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados)) {
        $sql = "
        	SELECT DISTINCT *
        	FROM (
	            	SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
	            UNION (
	            	SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
	            )
            ) AS cidade
            ORDER BY cidade ASC;
        ";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("Nenhuma cidade encontrada para o estado: {$estado}"));
        }
    } else {
        $retorno = array("error" => utf8_encode("Estado não encontrado"));
    }

    exit(json_encode($retorno));
}

if ($_POST["btn_acao"] == "submit") {

	$estado 			= $_POST['estado'];
	$cidade 			= $_POST['cidade'];
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$peca_referencia    = $_POST['peca_referencia'];
	$peca_descricao     = $_POST['peca_descricao'];
	$serie_peca 		= $_POST["serie_peca"];
	$serie_produto      = $_POST['serie_produto'];

	if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				WHERE fabrica_i = {$login_fabrica}
				AND ((UPPER(referencia) = UPPER('{$produto_referencia}'))
	                OR
	                (UPPER(descricao) = UPPER('{$produto_descricao}')))";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Produto não encontrado";
			$msg_erro["campos"][] = "produto";
		} else {
			$produto = pg_fetch_result($res, 0, "produto");
			$cond_produto = " AND pd.produto = {$produto} ";
		}
	}

	if (strlen($peca_referencia) > 0 or strlen($peca_descricao) > 0){
		$sql = "SELECT peca
				FROM tbl_peca
				WHERE fabrica = {$login_fabrica}
				AND ((UPPER(referencia) = UPPER('{$peca_referencia}'))
	                OR(UPPER(descricao) = UPPER('{$peca_descricao}')))";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Peça não encontrada";
			$msg_erro["campos"][] = "peca";
		} else {
			$peca = pg_fetch_result($res, 0, "peca");
			$cond_peca = "AND pc.peca = {$peca} ";
		}
	}

	if (!empty($cidade)){
		$join_cidade = " INNER JOIN tbl_cidade c ON c.cidade = hj.cidade AND c.nome = '{$cidade}' ";
		$cond_cidade = " AND pf.contato_cidade = c.nome ";
	}

    if (!empty($estado)){
    	$cond_estado = "AND pf.contato_estado = '{$estado}' ";
    }
    
    if (!empty($numero_serie)){
    	$cond_serie = " AND oi.peca_serie = '{$serie_peca}' ";
    }

    if (!empty($serie_produto)) {
    	$cond_serie_produto = "AND op.serie = '{$serie_produto}'";
    }
    
    if (!count($msg_erro["msg"])) {
		$sql = "SELECT
					o.os,
					o.sua_os,
					TO_CHAR(o.data_abertura,'DD/MM/YYYY') AS data_abertura,
					ta.descricao AS tipo_atendimento,
					pf.codigo_posto,
					p.nome AS nome_posto,
					pf.contato_estado AS estado_posto,
					pf.contato_cidade AS cidade_posto,
					pd.referencia AS produto_referencia,
					pd.descricao AS produto_descricao,
					op.serie AS produto_serie,
					pc.referencia AS peca_referencia,
					pc.descricao AS peca_descricao,
					oi.peca_serie,
					hj.motivo
				FROM tbl_os o
				INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = $login_fabrica
				INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = $login_fabrica
				INNER JOIN tbl_posto p ON p.posto = pf.posto
				INNER JOIN tbl_os_produto op ON op.os = o.os
				LEFT JOIN tbl_os_item oi ON oi.os_produto = op.os_produto
				LEFT JOIN tbl_peca pc ON pc.peca = oi.peca AND pc.fabrica = $login_fabrica
				INNER JOIN tbl_produto pd ON pd.produto = op.produto AND pd.fabrica_i = $login_fabrica
				INNER JOIN tbl_hd_jornada hj ON hj.hd_jornada = o.segmento_atuacao AND hj.fabrica = $login_fabrica
				$join_cidade
				WHERE o.fabrica = $login_fabrica
				AND o.finalizada IS NULL
				AND o.excluida IS NOT TRUE
				AND o.auditar IS TRUE
				AND CURRENT_DATE BETWEEN hj.data_inicio AND hj.data_fim
				$cond_estado
				$cond_cidade
				$cond_produto
				$cond_peca
				$cond_serie
				$cond_serie_produto
				ORDER BY o.data_abertura ASC";
		$resSubmit = pg_query($con, $sql);
	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_jornada_os-{$data}.csv";

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "OS; Abertura; Tipo Atendimento; Código do Posto; Nome do Posto; Estado do Posto; Cidade do Posto;Produto Referência; Produto Descrição; Produto Série; Peça Referência; Peça Descrição; Série Peça; Motivo Jornada\n";
			
			fwrite($file, utf8_encode($thead));

			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
				$os 				= pg_fetch_result($resSubmit, $i, 'os');
				$sua_os 			= pg_fetch_result($resSubmit, $i, 'sua_os');
				$data_abertura 		= pg_fetch_result($resSubmit, $i, 'data_abertura');
				$tipo_atendimento 	= pg_fetch_result($resSubmit, $i, 'tipo_atendimento');
				$codigo_posto 		= pg_fetch_result($resSubmit, $i, 'codigo_posto');
				$nome_posto 		= pg_fetch_result($resSubmit, $i, 'nome_posto');
				$estado_posto 		= pg_fetch_result($resSubmit, $i, 'estado_posto');
				$cidade_posto 		= pg_fetch_result($resSubmit, $i, 'cidade_posto');
				$produto_referencia = pg_fetch_result($resSubmit, $i, 'produto_referencia');
				$produto_descricao 	= pg_fetch_result($resSubmit, $i, 'produto_descricao');
				$produto_serie 		= pg_fetch_result($resSubmit, $i, 'produto_serie');
				$peca_referencia 	= pg_fetch_result($resSubmit, $i, 'peca_referencia');
				$peca_descricao 	= pg_fetch_result($resSubmit, $i, 'peca_descricao');
				$peca_serie 		= pg_fetch_result($resSubmit, $i, 'peca_serie');
				$motivo 			= pg_fetch_result($resSubmit, $i, 'motivo');
				
				$body .= "$sua_os;$data_abertura;$tipo_atendimento;$codigo_posto;$nome_posto;$estado_posto;$cidade_posto;$produto_referencia;$produto_descricao;$produto_serie;$peca_referencia;$peca_descricao;$serie_peca;$motivo\n";
			}

			fwrite($file, utf8_encode($body));
			fclose($file);
			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");
				echo "xls/{$fileName}";
			}
		}
		exit;
	}

}

$layout_menu = "callcenter";
$title = "RELATÓRIO DE JORNADA DE ORDEM DE SERVIÇO";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"select2"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$('#estado').select2();
	    $('#cidade').select2();

	    /**
	     * Evento para quando alterar o estado carregar as cidades do estado
	     */
	    $("#estado").change(function() {
	        busca_cidade($(this).val());
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

    function retorna_serie_produto(retorno) {
    	$("#serie_produto").val(retorno.serie);
    }

    /**
	 * Função que busca as cidades do estado e popula o select cidade
	 */
	function busca_cidade(estado, cidade) {
	    $("#cidade").find("option").first().nextAll().remove();

	    if (estado.length > 0) {
	        $.ajax({
	            url: "jornada_cadastro.php",
	            type: "POST",
	            timeout: 60000,
	            data: { ajax_busca_cidade: true, estado: estado },
	            beforeSend: function() {
	                if ($("#cidade").next("img").length == 0) {
	                    $("#cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
	                }
	            },
	            complete: function(data) {
	                data = $.parseJSON(data.responseText);

	                if (data.error) {
	                    alert(data.error);
	                } else {
	                    $.each(data.cidades, function(key, value) {
	                        var option = $("<option></option>", { value: value, text: value });
	                        $("#cidade").append(option);
	                    });
	                }

	                $("#cidade").show().next().remove();
	            }
	        });
	    }

	    if(typeof cidade != "undefined" && cidade.length > 0){
	        $("#cidade option[value='"+cidade+"']").attr('selected','selected');
	    }

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
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
            <div class="control-group <?= (in_array('estado', $msg_erro['campos'])) ? "error" : ""; ?>">
                <label class="control-label" for="estado">Estado</label>
                <div class="controls controls-row">
                    <div class="span11">
                        <select id="estado" name="estado" class="span12" >
                            <option value="" >Selecione</option>
                            <? foreach ($array_estados as $sigla => $nome_estado) {
                                $selected = ($sigla == $estado) ? "selected" : ""; ?>
								<option value="<?= $sigla; ?>" <?= $selected; ?> ><?= $nome_estado; ?></option>
                            <? } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group <?= (in_array('cidade', $msg_erro['campos'])) ? "error" : ""; ?>">
                <label class="control-label" for="cidade">Cidade</label>
                <div class="controls controls-row">
                    <div class="span11">
                        <select id="cidade" name="cidade" class="span12" >
                            <option value="" >Selecione</option>
                            <? if (strlen($estado) > 0) {
                                $sql = "
                                	SELECT DISTINCT * FROM (
                                        SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
                                        UNION (
                                            SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                                        )
                                    ) AS cidade
                                    ORDER BY cidade ASC;
                                ";
                                $res = pg_query($con,$sql);
                                if (pg_num_rows($res) > 0) {
                                    while ($result = pg_fetch_object($res)) {
                                        $selected = (trim($result->cidade) == trim($cidade)) ? "SELECTED" : ""; ?>
                                        <option value="<?= $result->cidade; ?>" <?= $selected; ?> ><?= $result->cidade; ?></option>
                                    <? }
                                }
                            } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
	    <div class="span2"></div>
	</div>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?= (in_array('produto', $msg_erro['campos'])) ? "error" : ""; ?>'>
				<label class='control-label' for='produto_referencia'>Referência Produto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" class="frm" id="produto_referencia" name="produto_referencia" value="<?=$produto_referencia?>" size="12" maxlength="20">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?= (in_array('produto', $msg_erro['campos'])) ? "error" : ""; ?>'>
				<label class='control-label' for='produto_descricao'>Descrição Produto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" class="frm" id="produto_descricao" name="produto_descricao" value="<?=$produto_descricao?>" size="40" maxlength="50">
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
            <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='peca_referencia'>Referência Peças</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="peca_referencia" name="peca_referencia" maxlength="20" value="<?=$peca_referencia ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='peca_descricao'>Descrição Peça</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="peca_descricao" name="peca_descricao" value="<?=$peca_descricao?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("serie_peca", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='serie_peca'>Número de Série Peça</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="serie_peca" name="serie_peca" maxlength="20" value="<?=$serie_peca?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" parametro="serie_peca" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("serie_produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='serie_produto'>Série do Produto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="serie_produto" name="serie_produto" maxlength="20" value="<?=$serie_produto?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="serie_produto" parametro="serie_produto" />
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
	<div class="container-fluid">
		<table id="resultado_os_jornada" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class='titulo_coluna' >
					<th>OS</th>
					<th>Abertura</th>
					<th>Tipo Atendimento</th>
                    <th>Código do Posto</th>
                    <th>Nome do Posto</th>
					<th>Estado do Posto</th>
					<th>Cidade do Posto</th>
					<th>Produto Referência</th>
					<th>Produto Descrição</th>
					<th>Produto Série</th>
					<th>Peça Referência</th>
					<th>Peça Descrição</th>
					<th>Série Peça</th>
					<th>Motivo Jornada</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $count; $i++) {
					$os 				= pg_fetch_result($resSubmit, $i, 'os');
					$sua_os 			= pg_fetch_result($resSubmit, $i, 'sua_os');
					$data_abertura 		= pg_fetch_result($resSubmit, $i, 'data_abertura');
					$tipo_atendimento 	= pg_fetch_result($resSubmit, $i, 'tipo_atendimento');
					$codigo_posto 		= pg_fetch_result($resSubmit, $i, 'codigo_posto');
					$nome_posto 		= pg_fetch_result($resSubmit, $i, 'nome_posto');
					$estado_posto 		= pg_fetch_result($resSubmit, $i, 'estado_posto');
					$cidade_posto 		= pg_fetch_result($resSubmit, $i, 'cidade_posto');
					$produto_referencia = pg_fetch_result($resSubmit, $i, 'produto_referencia');
					$produto_descricao 	= pg_fetch_result($resSubmit, $i, 'produto_descricao');
					$produto_serie 		= pg_fetch_result($resSubmit, $i, 'produto_serie');
					$peca_referencia 	= pg_fetch_result($resSubmit, $i, 'peca_referencia');
					$peca_descricao 	= pg_fetch_result($resSubmit, $i, 'peca_descricao');
					$peca_serie 		= pg_fetch_result($resSubmit, $i, 'peca_serie');
					$motivo 			= pg_fetch_result($resSubmit, $i, 'motivo');
				?>
					<tr>
						<td><a href="os_press.php?os=<?=$os?>" target="_blank"><?=$sua_os?></a></td>
						<td><?=$data_abertura?></td>
						<td><?=$tipo_atendimento?></td>
						<td><?=$codigo_posto?></td>
						<td><?=$nome_posto?></td>
						<td><?=$estado_posto?></td>
						<td><?=$cidade_posto?></td>
						<td><?=$produto_referencia?></td>
						<td><?=$produto_descricao?></td>
						<td><?=$produto_serie?></td>
						<td><?=$peca_referencia?></td>
						<td><?=$peca_descricao?></td>
						<td><?=$peca_serie?></td>
						<td><?=$motivo?></td>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>
	</div>
		<?php
		if ($count > 50) {
		?>
			<script>
				$.dataTableLoad({ table: "#resultado_os_jornada" });
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
			<span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
		    <span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
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
