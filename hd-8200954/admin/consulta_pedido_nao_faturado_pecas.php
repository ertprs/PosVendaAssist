<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include __DIR__.'/funcoes.php';

if(isset($_POST['data_inicial']) || isset($_POST['numero_pedido'])){
	$data_inicial  = $_POST['data_inicial'];
	$data_final    = $_POST['data_final'];
	$status_pedido = $_POST['status_pedido'];
	$posto_nome    = $_POST['posto']['nome'];
	$posto_codigo  = $_POST['posto']['codigo'];
	$numero_pedido = $_POST['numero_pedido'];

	if(empty($numero_pedido)){
		if(empty($data_inicial)){
			$msg_erro['msg'][0] = "Preencha os campos obrigatórios!";
			$msg_erro['campos'][] = "data_inicial";
		}

		if(empty($data_final)){
			$msg_erro['msg'][0] = "Preencha os campos obrigatórios!";
			$msg_erro['campos'][] = "data_final";
		}

		if(empty($status_pedido)){
			$msg_erro['msg'][0] = "Preencha os campos obrigatórios!";
			$msg_erro['campos'][] = "status_pedido";
		}

		if(count($msg_erro['msg']) == 0){
			list($dia, $mes, $ano) = explode("/", $data_inicial);
			$aux_data_inicial      = $ano."-".$mes."-".$dia;

			list($dia, $mes, $ano) = explode("/", $data_final);
			$aux_data_final        = $ano."-".$mes."-".$dia;

			$resultado_data = validaData($data_inicial, $data_final, 31);

			if($resultado_data != "true"){
				$msg_erro['msg'][] .= "{$resultado_data} <br />";
				$msg_erro['campos'] = array("data_inicial","data_final");
			}
		}
	}

	if(count($msg_erro['msg']) == 0){

		if(!empty($numero_pedido)){
			$condicao = " AND tbl_pedido.pedido = {$numero_pedido} ";

		}else{
			// Ambos Pedidos (Não Faturado / Faturado Parcialmente)
			if($status_pedido == "1"){
				$condicao = " AND tbl_pedido.status_pedido IN (2,5) AND (tbl_pedido_item.qtde_faturada = 0 or tbl_pedido_item.qtde_faturada < (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada)) ";

			// Não Faturado
			}else if($status_pedido == "2"){
				$condicao = " AND tbl_pedido.status_pedido = 2 AND tbl_pedido_item.qtde_faturada = 0 ";

			// Faturado Parcialmente
			}else{
				$condicao = " AND tbl_pedido.status_pedido = 5 AND tbl_pedido_item.qtde_faturada < (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) ";
			}

			$condicao .= " AND tbl_pedido.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
		}

		if (strlen($posto_codigo) > 0 or strlen($posto_nome) > 0){
			$sql = "SELECT tbl_posto_fabrica.posto FROM tbl_posto
						JOIN tbl_posto_fabrica USING(posto)
					WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
						AND ((UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto_codigo}')) OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$posto_nome}'), 'LATIN-9'))
					)";
			$res = pg_query($con ,$sql);

			if (pg_num_rows($res) == 0) {
				$msg_erro["msg"][]   .= "Posto não encontrado";
				$msg_erro["campos"][] = "posto";
			} else {
				$posto = pg_fetch_result($res, 0, "posto");
				$condicao .= " AND tbl_posto_fabrica.posto = $posto ";
			}
		}

		$sql = "
			SELECT
				tbl_pedido.pedido,
				TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data_pedido,
				tbl_posto.cnpj,
				tbl_peca.referencia,
				tbl_peca.descricao AS descricao_peca,
				(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) AS quantidade,
				tbl_pedido_item.preco,
				tbl_pedido_item.pedido_item,
				TO_CHAR(tbl_pedido.previsao_entrega, 'DD/MM/YYYY') AS previsao_entrega,
				tbl_pedido.pedido_cliente
			FROM tbl_pedido_item
			INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
			INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.produto_acabado IS FALSE
			INNER JOIN tbl_posto ON tbl_posto.posto = tbl_pedido.posto
			INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			WHERE tbl_pedido.fabrica = {$login_fabrica}
			AND tbl_pedido.finalizado IS NOT NULL
			AND (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) > 0
			{$condicao}
			ORDER BY tbl_pedido.pedido
		";
		$resPedido = pg_query($con,$sql);
	}
}

if(isset($_POST['btn_acao']) && $_POST['btn_acao'] == "gerar_csv"){
	$data     = date("d-m-Y-H:i");
	$fileName = "csv_pedido-{$data}.csv";
	$file     = fopen("/tmp/{$fileName}", "w");

	$cabecalho = array(
		"os",
		"codigo_posto",
		"nome_posto");

	
	$cabecalho[] = "consumidor";
	$cabecalho[] = "numero_serie";
	$cabecalho[] = "pedido";
	$cabecalho[] = "cnpj";
	
	$cabecalho[] = "pedido_item";
	$cabecalho[] = "referencia_peca";
	$cabecalho[] = "descricao_peca";
	$cabecalho[] = "quantidade_pendente";
	$cabecalho[] = "preco";
	$cabecalho[] = "previsao_entrega";
	
	$dados = implode(";",$cabecalho)."\n";
	$array_pedido = array();

	$data_inicial  = $_POST['data_inicial'];
	$data_final    = $_POST['data_final'];
	$status_pedido = $_POST['status_pedido'];
	$posto_nome    = $_POST['posto_nome'];
	$posto_codigo  = $_POST['posto_codigo'];
	$numero_pedido = $_POST['numero_pedido'];
	$tipo_pedido   = $_POST['tipo_pedido'];

	if(!empty($numero_pedido)){
		$condicao = " AND tbl_pedido.pedido = {$numero_pedido} ";
	}else{
		// Ambos Pedidos (Não Faturado / Faturado Parcialmente)
		if($status_pedido == "1"){
			$condicao = " AND tbl_pedido.status_pedido IN (2,5) AND (tbl_pedido_item.qtde_faturada = 0 or tbl_pedido_item.qtde_faturada < (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada)) ";

		// Não Faturado
		}else if($status_pedido == "2"){
			$condicao = " AND tbl_pedido.status_pedido = 2 AND tbl_pedido_item.qtde_faturada = 0 ";

		// Faturado Parcialmente
		}else{
			$condicao = " AND tbl_pedido.status_pedido = 5 AND tbl_pedido_item.qtde_faturada < (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) ";
		}

		$condicao .= " AND tbl_pedido.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
	}

	$cond_join = "and (tbl_os.produto = tbl_produto.produto or tbl_os.produto isnull) ";

	if (strlen($posto_codigo) > 0 or strlen($posto_nome) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto FROM tbl_posto
					JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
					AND ((UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto_codigo}')) OR
				(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$posto_nome}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (pg_num_rows($res) == 0) {
			$msg_erro["msg"][]   .= "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
			$condicao .= " AND tbl_posto_fabrica.posto = $posto ";
		}
	}

	$sql = "SELECT tbl_pedido.pedido,
			tbl_os.sua_os,
			tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome,
			tbl_posto.estado,
			tbl_os.consumidor_nome,
			tbl_os_produto.serie,
			tbl_pedido.pedido_cliente,
			TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data_pedido,
			tbl_posto.cnpj,
			tbl_peca.referencia,
			tbl_os.produto,
			TO_CHAR(tbl_pedido.previsao_entrega, 'DD/MM/YYYY') AS previsao_entrega,
			tbl_peca.descricao AS descricao_peca,
			(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) AS quantidade,
			tbl_pedido_item.preco,
			tbl_pedido_item.pedido_item,
			tbl_pedido_item.peca,
			(
				SELECT distinct tbl_linha.nome 
				FROM tbl_lista_basica 
				JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.fabrica_i = {$login_fabrica} {$cond_join} 
				JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica} 
				WHERE tbl_lista_basica.fabrica = $login_fabrica 
				AND peca = peca LIMIT 1
			) AS nome_linha
		FROM tbl_pedido
		INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
		LEFT JOIN tbl_os_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
		LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
		LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
		INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$login_fabrica}
		INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
		WHERE tbl_pedido.fabrica = {$login_fabrica} AND tbl_pedido.finalizado IS NOT NULL
		AND tbl_peca.produto_acabado IS FALSE
		AND     (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) > 0
		{$condicao}
		ORDER BY tbl_pedido.pedido";
	$resPedido = pg_query($con,$sql);
	$array_regioes = array(
		"AC,AM,RR,PA,AP,MA,TO,PI,CE,RN,PB,PE,AL,SE,BA,SP",
		"RO,MT,GO,DF,MG",
		"RJ,ES,MS,PR,SC,RS"
	);

	while($objeto_res = pg_fetch_object($resPedido)){

		if(stripos($array_regioes[0], $objeto_res->estado) !== false){
			$estado = $array_regioes[0];

		}else if(stripos($array_regioes[1], $objeto_res->estado) !== false){
			$estado = $array_regioes[1];

		}else if(stripos($array_regioes[2], $objeto_res->estado) !== false){
			$estado = $array_regioes[2];

		}

		$cnpj = $objeto_res->cnpj;
		// $cnpj = preg_replace("/\D/g","",$cnpj);

		$array_expressao = array(
			'/^(\d{2})(\d)/',
			'/^(\d{2})\.(\d{3})(\d)/',
			'/\.(\d{3})(\d)/',
			'/(\d{4})(\d)/'
		);

		$array_replace = array(
			'$1.$2',
			'$1.$2.$3',
			'.$1/$2',
			'$1-$2'
		);

		$cnpj = preg_replace($array_expressao, $array_replace, $cnpj);


		$array_dados = array($objeto_res->sua_os,
			$objeto_res->codigo_posto,
			$objeto_res->nome);

		
		$array_dados[] = $objeto_res->consumidor_nome;
		$array_dados[] = '"'.$objeto_res->serie.'"';
		$array_dados[] = $objeto_res->pedido;

		$array_dados[] = '"'.$cnpj.'"';
		
		$array_dados[] = $objeto_res->pedido_item;
		$array_dados[] = str_replace(";"," ", $objeto_res->referencia);
		$array_dados[] = '"'.retira_acentos(str_replace(";"," ",str_replace('"','',$objeto_res->descricao_peca))).'"';
		$array_dados[] = $objeto_res->quantidade;
		$array_dados[] = number_format($objeto_res->preco,2,".","");

		if (empty($objeto_res->previsao_entrega)){
			$array_dados[] = "";
		}else{
			$array_dados[] = $objeto_res->previsao_entrega;
		}
		
		$dados .= implode(";",$array_dados)."\r\n";

		if(!in_array($objeto_res->pedido, $array_pedido)){
			$array_pedido[] = $objeto_res->pedido;
		}
	}

	fwrite($file, $dados);
	fclose($file);

	if (file_exists("/tmp/{$fileName}") && count($array_pedido) > 0) {
		system("mv /tmp/{$fileName} xls/{$fileName}");

		$resultado = array("success" => true, "arquivo" => "xls/{$fileName}");
	}else{
		$resultado = array("success" => false, "mensagem" => utf8_encode("Não foi possível gerar arquivo."));
	}
	echo json_encode($resultado);
	exit;
}

$layout_menu = "callcenter";
$title = "CONSULTA DE PEDIDO NÃO FATURADO / FATURADO PARCIALMENTE";

include "cabecalho_new.php";

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput"
);

include __DIR__."/plugin_loader.php";
?>

<script type="text/javascript">
$(function() {
	$("#data_inicial").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	Shadowbox.init();

	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});

	$("#btn_pesquisa").on("click",function(){
		$(this).button("loading");
	});

    $("button.gerar_csv").on("click",function(){
    	$("button.gerar_csv").button("loading");

    	$("div.mensagem").html("");
    	$("div.mensagem").removeClass("alert alert-error");

    	$.ajax({
			url: "consulta_pedido_nao_faturado_pecas.php",
			type: "post",
			data: { btn_acao: "gerar_csv",
				data_inicial :  $("#data_inicial").val(),
				data_final :    $("#data_final").val(),
				numero_pedido : $("#numero_pedido").val(),
				status_pedido : $("#status_pedido").val(),
				posto_nome : 	$("#posto_nome").val(),
				posto_codigo : 	$("#posto_codigo").val(),
				numero_pedido:  $("#numero_pedido").val()
			}
		})
		.done(function(data) {
			data = JSON.parse(data);
			if(data.success == true){
				window.location="<?=$_PHP_SELF?>"+data.arquivo;
			}else{
				$("div.mensagem").addClass("alert alert-error");
				$("div.mensagem").html("<h4>"+data.mensagem+"</h4>");
			}
			$("button.gerar_csv").button("reset");
		});
    });
});

function retorna_posto(retorno) {
	$("#posto_id").val(retorno.posto);
	$("#posto_codigo").val(retorno.codigo);
	$("#posto_nome").val(retorno.nome);
}
</script>
<? if (count($msg_erro['msg']) > 0) { ?>
	<div class="alert alert-error">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
	<h4><?=implode("<br />", $msg_erro['msg'])?></h4></div>
<? } ?>
<div class="mensagem"></div>

<form name="frm_consulta_pedido_faturado" method="POST" action="<?echo $PHP_SELF?>"  class="form-search form-inline tc_formulario">
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<div class='row-fluid'>
		<div class="span2"></div>
		<div class="span2">
			<div class='control-group' >
				<label class="control-label" for="numero_pedido">Nº Pedido</label>
				<div class="controls controls-row">
					<div class="span12">
						<input id="numero_pedido" name="numero_pedido" class="span12" type="text" value="<?=$numero_pedido?>" />
					</div>
				</div>
			</div>
		</div><div class="span2">
			<div class='control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? "error" : "" ?>' >
				<label class="control-label" for="data_inicial">Data Inicial</label>
				<div class="controls controls-row">
					<div class="span12"><h5 class='asteristico'>*</h5>
						<input id="data_inicial" name="data_inicial" class="span12" type="text" value="<?=$data_inicial?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2">
			<div class='control-group <?=(in_array('data_final', $msg_erro['campos'])) ? "error" : "" ?>' >
				<label class="control-label" for="data_final">Data Final</label>
				<div class="controls controls-row">
					<div class="span12"><h5 class='asteristico'>*</h5>
						<input id="data_final" name="data_final" class="span12" type="text" value="<?=$data_final?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2">
			<div class='control-group <?=(in_array('status_pedido', $msg_erro['campos'])) ? "error" : "" ?>'>
				<label class="control-label" for="status_pedido">Status do Pedido</label>
				<div class="controls controls-row">
					<div class="span12"><h5 class='asteristico'>*</h5>
						<select name="status_pedido" id="status_pedido">
							<option value="">Selecione</option>
							<option value="1" <?php echo $status_pedido == "1" ? "selected" : ""; ?>>Ambos</option>
							<option value="2" <?php echo $status_pedido == "2" ? "selected" : ""; ?>>Não Faturado</option>
							<option value="3" <?php echo $status_pedido == "3" ? "selected" : ""; ?>>Faturado Parcialmente</option>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class='row-fluid'>
		<div class="span2"></div>
		<input type="hidden" id="posto" name="posto" value="<?=$posto?>" />
			<div class="span2">
				<div class='control-group' >
					<label class="control-label" for="posto_codigo">Código do Posto</label>
					<div class="controls controls-row">
						<div class="span10 input-append">
							<input id="posto_codigo" name="posto[codigo]" class="span12" type="text" value="<?=getValue('posto[codigo]')?>"/>
							<span class="add-on" rel="lupa">
								<i class="icon-search"></i>
							</span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class="span4">
				<div class='control-group' >
					<label class="control-label" for="posto_nome">Nome do Posto</label>
					<div class="controls controls-row">
						<div class="span10 input-append">
							<input id="posto_nome" name="posto[nome]" class="span12" type="text" value="<?=getValue('posto[nome]')?>"/>
							<span class="add-on" rel="lupa">
								<i class="icon-search"></i>
							</span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<?php if (in_array($login_fabrica, [167, 203])) { ?>
				<div class="span4">
					<div class='control-group' >
						<label class="control-label" for="posto_nome">Tipo de Pedido</label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<?
				                $sql = "SELECT  tbl_tipo_pedido.tipo_pedido,
				                tbl_tipo_pedido.descricao
				                FROM    tbl_tipo_pedido
				                WHERE   tbl_tipo_pedido.fabrica = $login_fabrica
				                ORDER BY tbl_tipo_pedido.descricao";
				                $res = pg_query($con,$sql);

				                if (pg_numrows($res) > 0) {
				                    ?><select name='tipo_pedido' id='tipo_pedido' class='parametros_tabela'>
				                        <option value=''>Todos os Pedidos</option>

				                        <?php 
				                        for ($i = 0 ; $i < pg_num_rows($res); $i++){
				                            $aux_tipo      = trim(pg_result($res,$i,'tipo_pedido'));
				                            $aux_descricao = trim(pg_result($res,$i,'descricao'));

				                            $selected = ($aux_tipo == $_POST['tipo_pedido']) ? "selected" : "";
				                        ?>    
				                          	<option value='<?= $aux_tipo ?>' <?= $selected ?> >
				                          		<?= $aux_descricao ?>
				                          	</option>
				                        <?php     
				                        } ?>
				                    </select>
				                <?php
				                } ?>
							</div>
						</div>
					</div>
				</div>
			<?php 
			}
			?>
	</div>
	<p>
		<button type="submit" class='btn' data-loading-text="Pesquisando..." id="btn_pesquisa" >Pesquisar</button>
	</p>
	<br />
</form>
<?php
if(isset($_POST['data_inicial'])){
	if(pg_num_rows($resPedido) > 0){
?>
	<p class="tac">
		<button type="button" class='btn btn-success gerar_csv' data-loading-text="Gerando arquivo...">Gerar Arquivo CSV</button>
	</p>

	<table id="resultado_pedido" class='table table-striped table-bordered table-large' style="margin: 0 auto;" >
		<thead>
			<tr class='titulo_coluna'>
				<th>Pedido</th>
				<th>CNPJ Posto</th>
				<th>Peça</th>
				<th>Quantidade Pendente</th>
				<th>Preço</th>
			</tr>
		</thead>
		<tbody>
			<?php
			while($objeto_pedido = pg_fetch_object($resPedido)){
			?>
			<tr>
				<td><?=$objeto_pedido->pedido?></td>
				<td><?=$objeto_pedido->cnpj?></td>
				<td><?=$objeto_pedido->referencia?> - <?=$objeto_pedido->descricao_peca?></td>
				<td><?=$objeto_pedido->quantidade?></td>
				<td><?=number_format($objeto_pedido->preco,2,".","")?></td>
			</tr>
			<?php
			}
			?>
		</tbody>
	</table>
<?php
	}elseif(count($msg_erro['msg']) == 0){
		?>
		<div class="container">
			<div class="alert">
				<h4>Nenhum resultado encontrado</h4>
			</div>
		</div>
		<?php
	}
}
include "rodape.php";
?>
