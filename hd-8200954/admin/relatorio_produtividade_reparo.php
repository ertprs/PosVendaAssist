<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia";

include 'autentica_admin.php';
include 'funcoes.php';

if ($_GET["ajax_tecnicos_posto"] == true) {
	sleep(3);

	$posto = $_GET["posto"];

	if (empty($posto)) {
		$retorno = array("erro" => utf8_encode("Posto não informado"));
	} else {
		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			$retorno = array("erro" => utf8_encode("Posto não encontrado"));
		}
	}

	if (!isset($retorno["erro"])) {
		$sql = "SELECT tecnico, nome
				FROM tbl_tecnico
				WHERE fabrica = {$login_fabrica}
				AND posto = {$posto}";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			$retorno = array("sem_resultado" => true);
		} else {
			$tecnicos = array();

			while ($tecnico = pg_fetch_object($res)) {
				$tecnicos[] = array(
					"id"   => $tecnico->tecnico,
					"nome" => utf8_encode($tecnico->nome)
				);
			}

			$retorno = array("tecnicos" => $tecnicos);
		}
	}

	exit(json_encode($retorno));
}

$btn_acao       = $_POST['btn_acao'];

if($btn_acao == "submit"){
	$mes       			= $_POST["mes"];
	$ano      			= $_POST["ano"];
	$tipo_data          = $_POST["tipo_data"];
	$codigo_posto       = $_POST["codigo_posto"];
	$descricao_posto    = $_POST["descricao_posto"];
	$tecnico            = $_POST["tecnico"];
	$linha    			= $_POST["linha"];
	$familia    		= $_POST["familia"];
	$produto_referencia	= $_POST["produto_referencia"];
	$produto_descricao	= $_POST["produto_descricao"];

	# Validações
	if (!strlen($mes)) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "mes";
	}

	if (!strlen($ano)) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "ano";
	}

	if (!strlen($tipo_data)) {
		$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][]   = "tipo_data";
	}

	if(!empty($codigo_posto)){
		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$posto = pg_fetch_result($res,0,'posto');
			$cond = " AND tbl_os.posto = $posto";
		}else{
			$msg_erro['msg'] = "Posto não encontrado";
		}
	}

	if (!empty($produto_referencia)) {
		$sql = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND UPPER(referencia) = UPPER('{$produto_referencia}')";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){
			$produto = pg_fetch_result($res,0,'produto');
			$cond = " AND tbl_os.produto = $produto";
		}else{
			$msg_erro['msg'] = "Produto não encontrado";
		}
	}

	if(!count($msg_erro["msg"])){

		$ultimo_dia = date("t", mktime(0,0,0,$mes,'01',$ano));

		$data_inicial_formatada = "$ano-$mes-01";
		$data_final_formatada = "$ano-$mes-$ultimo_dia";

		if(!empty($familia)){
			$cond .= " AND tbl_produto.familia = $familia ";
		}

		if(!empty($linha)){
			$cond .= " AND tbl_produto.linha = $linha ";
		}

		if($tipo_data == "a"){
			$cond .= " AND tbl_os.data_abertura BETWEEN '$data_inicial_formatada 00:00:00' and '$data_final_formatada 23:59:59' ";
		}else{
			$cond .= " AND tbl_os.data_conserto BETWEEN '$data_inicial_formatada 00:00:00' and '$data_final_formatada 23:59:59' ";
		}

		if (count($tecnico) > 0) {
			$columnTecnico  = ", tbl_tecnico.tecnico";
			$joinTecnico    = "INNER JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_os.tecnico AND tbl_tecnico.fabrica = {$login_fabrica} AND tbl_tecnico.posto = {$posto}";
			$whereTecnico   = "AND tbl_tecnico.tecnico IN (".implode(",", $tecnico).")";
			$groupbyTecnico = ", tbl_tecnico.tecnico";
			$metaTecnico    = $tecnico;
		}


		$sql = "SELECT count(os) AS total_os, tbl_os.data_conserto::date AS data_conserto {$columnTecnico}
				FROM tbl_os
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
				{$joinTecnico}
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.data_conserto IS NOT NULL
				{$whereTecnico}
				$cond
				GROUP BY tbl_os.data_conserto::date {$groupbyTecnico}";
		$resSubmit = pg_query($con,$sql);
		$array_result = pg_fetch_all($resSubmit);

		foreach ($array_result as $key => $value) {
			if (isset($metaTecnico)) {
				$datas_conserto[$value["tecnico"]][$value["data_conserto"]] = $value["total_os"];
			} else {
				$datas_conserto[$value['data_conserto']] = $value['total_os'];
			}
		}

		$caminho = "metas/".$login_fabrica;
		$arquivo = $caminho."/".$ano."_".$login_fabrica.".txt";	

		if(file_exists($arquivo)){
			$conteudo = file_get_contents($arquivo);
			$metas = json_decode($conteudo,true);
			
			$metas = $metas[$mes];

			//DADOS PARA MONTAR O GRÁFICO

			foreach ($metas as $key => $value) {
				$meta[] = $key;
			}

			$meta_grafico = implode(",", $meta);

			if (isset($metaTecnico)) {
				$producao_grafico = array();
				$producao_meta    = array();

				foreach ($metaTecnico as $tecnico) {
					$sqlNomeTecnico = "SELECT nome FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND tecnico = {$tecnico}";
					$resNomeTecnico = pg_query($con, $sqlNomeTecnico);

					$tecnicoNome = pg_fetch_result($resNomeTecnico, 0, "nome");

					$producaoTecnico = array();

					foreach ($metas as $key => $value) {
						$data = "$ano-$mes-$key";

						if(array_key_exists($data, $datas_conserto[$tecnico])){
							$producaoTecnico[] = (int) $datas_conserto[$tecnico][$data];
						}else{
							$producaoTecnico[] = 0;
						}
					}

					$producao_grafico[] = array(
						"name" => $tecnicoNome,
						"data" => $producaoTecnico
					);
				}

				foreach ($metas as $key => $value) {
					$producao_meta[] = (int) $value;				
				}

				$producao_grafico[] = array(
					"name" => "Meta",
					"data" => $producao_meta
				);
			} else {
				$producao_grafico = array();
				$producao_geral   = array();
				$producao_meta    = array();

				foreach ($metas as $key => $value) {
					$data = "$ano-$mes-$key";

					if(array_key_exists($data, $datas_conserto)){
						$producao_geral[] = (int) $datas_conserto[$data];
					}else{
						$producao_geral[] = 0;
					}
				}

				foreach ($metas as $key => $value) {
					$producao_meta[] = (int) $value;				
				}

				$producao_grafico = array(
					array(
						"name" => utf8_encode("Produção"),
						"data" => $producao_geral
					),
					array(
						"name" => "Meta",
						"data" => $producao_meta
					)
				);
			}
		}
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE PRODUTIVIDADE DE REPARO";

include_once "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "shadowbox",
    "datepicker",
    "mask",
    "jquery_multiselect"
);

include("plugin_loader.php");

// Combos que são montados a partir do BD ou seus options se diferenciam por fábrica
/*
* LINHA
*/
$sql = "SELECT  *
        FROM    tbl_linha
        WHERE   tbl_linha.fabrica = $login_fabrica
        AND     tbl_linha.ativo = TRUE
        ORDER BY tbl_linha.nome;";
$res = pg_query ($con,$sql);

if (pg_num_rows($res) > 0) {
    $options_linha = array();
    for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
        $aux_linha = trim(pg_fetch_result($res,$x,linha));
        $aux_nome  = trim(pg_fetch_result($res,$x,nome));
        $options_linha[$aux_linha] = $aux_nome;
    }
}
/*
* Família
*/
$sql = "SELECT  *
        FROM    tbl_familia
        WHERE   tbl_familia.fabrica = $login_fabrica
        AND     tbl_familia.ativo = TRUE
        ORDER BY tbl_familia.descricao;";
    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {
        $options_familia = array();
        for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
            $aux_familia        = trim(pg_fetch_result($res,$x,familia));
            $aux_descricao      = trim(pg_fetch_result($res,$x,descricao));
            $aux_codigo_familia = trim(pg_fetch_result($res,$x,codigo_familia));

            if ($_RESULT["familia"] == $aux_familia){
                $selected = 'selected="selected"';
                $codigo_selecionado = (empty($aux_codigo)) ? $aux_codigo_familia : $aux_codigo;
                $_RESULT["codigo_validacao_serie"] = $aux_codigo;
            }else{
                $selected = null;
            }

            $options_familia[$aux_familia] = $aux_descricao;
            
        }
    }

$options_mes = array(
				"01" => "Janeiro",
				"02" => "Fevereiro",
				"03" => "Março",
				"04" => "Abril",
				"05" => "Maio",
				"06" => "Junho",
				"07" => "Julho",
				"08" => "Agosto",
				"09" => "Setembro",
				"10" => "Outubro",
				"11" => "Novembro",
				"12" => "Dezembro",
				);

for ($i = date('Y'); $i > date('Y') - 3 ; $i--) { 
	$options_ano[$i] = $i;
}

$inputs = array(
	"mes" => array(
		"span"     => 4,
		"label"    => "Mês",
		"type"     => "select",
		"width"    => 5,
		"required" => true,
		"options" => $options_mes
	),
	"ano" => array(
		"span"     => 4,
		"label"    => "Ano",
		"type"     => "select",
		"width"    => 5,
		"required" => true,
		"options" => $options_ano
	),
	"tipo_data" => array(
		"label"    => "Tipo Data",
		"type"     => "radio",
		"radios"  => array(
			"a" => "Data Abertura",
			"c" => "Data Conserto"
		),
		"required" => true,
		"span"     => 8
	),
	"codigo_posto" => array(
		"span"      => 4,
		"label"     => "Código Posto",
		"type"      => "input/text",
		"width"     => 10,
		"lupa"		=> array(
				"name"   	=> "codigo_posto",
				"tipo"   	=> "posto",
				"parametro" => "codigo"
			)
	),
	"descricao_posto" => array(
		"span"      => 4,
		"label"     => "Posto",
		"type"      => "input/text",
		"width"     => 10,
		"lupa"		=> array(
				"name"   	=> "descricao_posto",
				"tipo"   	=> "posto",
				"parametro" => "nome"
			)
	),
	"tecnico[]" => array(
		"id"   => "tecnico",
        "type" => "select",
        "span" => 8,
        "width" => 5,
        "label" => "Técnico <span style='color: #B94A48;'>(selecione um posto)</span>",
        "mostra_opcao_vazia" => false,
        "extra" => array(
        	"multiple" => "multiple",
        	"style" => "margint-bottom: 5px;"
        )
    ),
	"linha" => array(
        "type" => "select",
        "span" => 4,
        "width" => 10,
        "label" => "Linha",
        "options" => $options_linha
    ),
    "familia" => array(
        "type" => "select",
        "span" => 4,
        "width" => 10,
        "label" => "Família",
        "options" => $options_familia
    ),
    "produto_referencia"=> array(
        "span"=>4,
		"width" => 6,
        "type"=>"input/text",
        "label"=>"Referência Produto",
		"lupa"=>array("name"=>"produto_referencia", "tipo"=>"produto", "parametro"=>"referencia")
    ),
    "produto_descricao"=> array(
        "span"=>4,
        "width" => 12,
        "type"=>"input/text",
        "label"=>"Descrição Produto",
		"lupa"=>array("name"=>"produto_descricao", "tipo"=>"produto", "parametro"=>"descricao")
    ),
);

if (isset($posto)) {
	$sql = "SELECT tecnico, nome FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND posto = {$posto}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$inputs["tecnico[]"]["options"] = array();

		while ($tecnico = pg_fetch_object($res)) {
			$inputs["tecnico[]"]["options"][$tecnico->tecnico] = $tecnico->nome;
		}
	}
}

?>

<script src="js/novo_highcharts.js"></script>
<script src="js/modules/exporting.js"></script>

<script>
		$(function(){
		
			Shadowbox.init();
		
			$.datepickerLoad(Array("data_final", "data_inicial"));

			$('#data_inicial').mask("99/99/9999");
			$('#data_final').mask("99/99/9999");

			$("span[rel=descricao_posto]").click(function () {
				$.lupa($(this));
			});

			$("span[rel=codigo_posto]").click(function () {
				$.lupa($(this));
			});

			$("span[rel=produto_referencia]").click(function () {
				$.lupa($(this));
			});

			$("span[rel=produto_descricao]").click(function () {
				$.lupa($(this));
			});

			$(".list_os").click(function(){
				var posto     = $(this).data("posto");
				var data      = $(this).data("data");
				var tipo_data = $(this).data("tipodata");
				var familia   = $(this).data("familia");
				var linha     = $(this).data("linha");
				var produto   = $(this).data("produto");
				var tecnico   = $(this).data("tecnico");

				if (typeof posto == "undefined") {
					posto = "";
				}

				if (typeof familia == "undefined") {
					familia = "";
				}

				if (typeof linha == "undefined") {
					linha = "";
				}

				if (typeof produto == "undefined") {
					produto = "";
				}

				if (typeof tecnico == "undefined") {
					tecnico = "";
				}

				Shadowbox.open({
					content: "listar_prdutividade_os_reparo.php?posto="+posto+"&data="+data+"&tipo_data="+tipo_data+"&familia="+familia+"&linha="+linha+"&produto="+produto+"&tecnico="+tecnico,
					player: "iframe",
					width: 1800,
					height: 800
				});

			});

			$("#tecnico").multiSelect();

			$('#container').highcharts({
		        chart: {
		            type: 'line'
		        },
		        title: {
		            text: 'RELATÓRIO DE PRODUTIVIDADE DE REPARO '
		        },
		        subtitle: {
		            text: 'Período: <?=$options_mes[$mes]?> de <?=$ano?>'
		        },
		        xAxis: {
		            categories: [<?=$meta_grafico?>]
		        },
		        yAxis: {
		            title: {
		                text: 'Produtividade reparo'
		            }
		        },
		        plotOptions: {
		            line: {
		                dataLabels: {
		                    enabled: true
		                },
		                enableMouseTracking: false
		            }
		        },
		        series: <?=json_encode($producao_grafico)?>
		    });
		});

		function retorna_posto(retorno){
	        $("#codigo_posto").val(retorno.codigo);
			$("#descricao_posto").val(retorno.nome);

			var tecnicos = [];

			$.ajax({
				url: "relatorio_produtividade_reparo.php",
				type: "get",
				data: { ajax_tecnicos_posto: true, posto: retorno.posto },
				beforeSend: function() {
					$("#tecnico").after("<div class='alert alert-info' style='margin-bottom: 0px;' >Carregando, aguarde...</div>");
					$("#tecnico option").remove();
				}
			}).always(function(data) {
				data = JSON.parse(data);

				if (data.erro) {
					alert(data.erro);
				} else if (data.tecnicos) {
					data.tecnicos.forEach(function(tecnico, key){
						var option = $("<option></option>", {
							value: tecnico.id,
							text: tecnico.nome
						});

						$("#tecnico").append(option);
					});
				}

				$("#tecnico").next("div.alert-info").remove();
				$("#tecnico").multiSelect("refresh")
			});
	    }

	    function retorna_produto (retorno) {
			$("#produto_referencia").val(retorno.referencia);
			$("#produto_descricao").val(retorno.descricao);
		}
		
		
		
		
	</script>

	<div class="container">
	
		<?php
		/* Erro */
		if (count($msg_erro["msg"]) > 0) {
		?>
			<div class="alert alert-error">
				<h4><?php echo implode("<br />", $msg_erro["msg"])?></h4>
			</div>
		<?php } ?>
	
		<div class="container">
			<strong class="obrigatorio pull-right"> * Campos obrigatórios </strong>
		</div>
		
		<form name='frm_relatorio' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
		
			<div class='titulo_tabela'>Parâmetros de Pesquisa</div> <br/>
			
			<? echo montaForm($inputs,null);?>

			<p>
				<br/>
				<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
				<input type='hidden' id="btn_click" name='btn_acao' value='' />
			</p>
			
			<br/>
			
		</form>
		
	</div>

</div>

<?php
	
	if($btn_acao == "submit"){

		if (pg_num_rows($resSubmit) > 0) {

			?>
			<table align="center" id="resultado_produtividade_repadro" class='table table-striped table-bordered table-hover table-large' >
				<thead>
					<tr class='titulo_coluna' >
						<th>DATA</th>
            		<?php
            			foreach ($metas as $key => $value) {
            				echo "<th>$key</th>";
            			}
            		?>
            			<th>TOTAL</th>
					</tr>
                </thead>
				<tbody>
		<?php
				if (isset($metaTecnico)) {
					foreach ($metaTecnico as $tecnico) {
						$sqlNomeTecnico = "SELECT nome FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND tecnico = {$tecnico}";
						$resNomeTecnico = pg_query($con, $sqlNomeTecnico);

						$tecnicoNome = pg_fetch_result($resNomeTecnico, 0, "nome");

						echo "<tr>
								<td class='tac'>{$tecnicoNome}</td>";

								$total_prod = 0;
		    					foreach ($metas as $key => $value) {

		    						$data = "$ano-$mes-$key";

		    						if(array_key_exists($data, $datas_conserto[$tecnico])){
		    							$total_prod += $datas_conserto[$tecnico][$data];
		    							echo "<td class='tac'>
		    									<a href='javascript:void(0);' class='list_os' data-tecnico='$tecnico' data-posto='$posto' data-data='$data' data-tipodata='$tipo_data' data-familia='$familia' data-linha='$linha' data-produto='$produto' >{$datas_conserto[$tecnico][$data]}</a>
		    									(".number_format((($datas_conserto[$tecnico][$data] * 100) / $value), 2, ",", ".")."%)
		    								  </td>";
		    						}else{
		    							echo "<td class='tac'>0</td>";
		    						}

		    					}
		    					echo "<td class='tac'>{$total_prod}</td>";
		    			echo "</tr>";
	    			}
				} else {
					echo "<tr>
							<td class='tac'>PRODUÇÃO</td>";

							$total_prod = 0;
	    					foreach ($metas as $key => $value) {

	    						$data = "$ano-$mes-$key";

	    						if(array_key_exists($data, $datas_conserto)){
	    							$total_prod += $datas_conserto[$data];
	    							echo "<td class='tac'>
	    									<a href='javascript:void(0);' class='list_os' data-posto='$posto' data-data='$data' data-tipodata='$tipo_data' data-familia='$familia' data-linha='$linha' data-produto='$produto' >{$datas_conserto[$data]}</a>
	    								  </td>";
	    						}else{
	    							echo "<td class='tac'>0</td>";
	    						}

	    					}
	    					echo "<td class='tac'>{$total_prod}</td>";
	    			echo "</tr>";
	    		}

    			echo "<tr>";
    			echo "<td class='tac'>METAS</td>";
    			$total_meta = 0;
    			foreach ($metas as $key => $value) {
    				$total_meta += $value;
    				echo "<td class='tac'>$value</td>";
    			}
    			echo "<td class='tac'>{$total_meta}</td>";
    			echo "</tr>";
    			echo "</tbody>";
    			echo "</table>";
            		

		}else{
			echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
		}

		?>
			<br />
			<div id="container" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
		<?php

	}

/* Rodapé */
	include 'rodape.php';
?>
