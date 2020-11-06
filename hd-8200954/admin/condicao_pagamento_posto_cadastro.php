<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";

include "autentica_admin.php";
include "funcoes.php";

include_once dirname(__FILE__) . '/../class/AuditorLog.php';

if ($_POST["btn_acao"] == "submit") {
	if(in_array($login_fabrica, array(151))){
		$postos_selecionados = $_POST["postos_selecionados"];
		$condicao            = $_POST["condicao"];
		$limite_minimo       = $_POST["valor_minimo"];

		if($limite_minimo != ""){
			$limite_minimo = str_replace(".", "", $limite_minimo);
			$limite_minimo = number_format($limite_minimo, 2, '.', '');
		} else {
			$limite_minimo = 0;
		}

		if($postos_selecionados != ""){
			$postos_selecionados = explode(",", $postos_selecionados);
		}

	} else {
		$codigo_posto        = $_POST["codigo_posto"];
		$postos_selecionados[] = $codigo_posto;
		$descricao_posto     = $_POST["descricao_posto"];
		$condicao            = $_POST["condicao"];
		$limite_minimo       = 0;
	}

	foreach ($postos_selecionados as $key => $codigo_posto) {
		if(in_array($login_fabrica, array(151))){
			$descricao_posto = $codigo_posto;
		}

		if(count($condicao) == 0 || strlen($codigo_posto) == 0 || strlen($descricao_posto) == 0){
			$msg_erro["msg"][] = "Preencha os campos obrigatórios";

	        if(count($condicao) == 0){
	             $msg_erro["campos"][] = "condicao";
	        }

	        if(strlen($codigo_posto) == 0){
	             $msg_erro["campos"][] = "posto";
	        }

	        if(strlen($descricao_posto) == 0){
	             $msg_erro["campos"][] = "posto_nome";
	        }
		}

		if(empty($codigo_posto) AND count($msg_erro["msg"]) == 0){
			$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
			$msg_erro["campos"][] = "posto";
		}

		if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
			$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
					JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
					AND UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}')";
			$res = pg_query($con, $sql);

			if (!pg_num_rows($res)) {
				$msg_erro["msg"][]    = "Posto não encontrado";
				$msg_erro["campos"][] = "posto";
			} else {
				$posto = pg_fetch_result($res, 0, "posto");
			}
		}

		if (empty($msg_erro["msg"])) {
			pg_query($con,"BEGIN");

			foreach($condicao AS $key => $value){

	            $sql_ver = "SELECT tbl_posto_condicao.* FROM tbl_posto_condicao
	            		JOIN tbl_posto_fabrica USING(posto)
	            	WHERE posto = {$posto} 
	            		AND condicao = {$value}
	            		AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
	            $res_ver = pg_query($con, $sql_ver);

	            unset($AuditorLog);

	            if(pg_num_rows($res_ver) == 0){
	            	$AuditorLog = new AuditorLog('INSERT');

	                $sql = "INSERT INTO tbl_posto_condicao(
	                		posto,
	                		condicao,
	                		limite_minimo
	                	) VALUES(
	                		{$posto},
	                		{$value},
	                		{$limite_minimo}
	            		)";
	                $res = pg_query($con,$sql);

	                $AuditorLog->RetornaDadosSelect($sql_ver)->EnviarLog('insert', 'tbl_posto_condicao',"$login_fabrica*$posto");

	                if (!$AuditorLog->OK) {
	                    $msg_erro .= 'Erro ao tentar gravar o log de registro!';
	                }

	                if(strlen(pg_last_error($con)) > 0){
	                    $msg_erro["msg"][] = pg_last_error();
	                }
	            } else {
	            	$AuditorLog = new AuditorLog('UPDATE');

	            	$sql = "UPDATE tbl_posto_condicao SET
                			limite_minimo = {$limite_minimo},
                			visivel = true
                		WHERE posto = {$posto}
                			AND condicao = {$value}";
        			$res = pg_query($con,$sql);

	                $AuditorLog->RetornaDadosSelect($sql_ver)->EnviarLog('update', 'tbl_posto_condicao',"$login_fabrica*$posto");

	                if (!$AuditorLog->OK) {
	                    $msg_erro .= 'Erro ao tentar gravar o log de registro!';
	                }

	                if(strlen(pg_last_error($con)) > 0){
	                    $msg_erro["msg"][] = pg_last_error();
	                }
	            }
			}
			
			if(count($msg_erro["msg"]) > 0){
				pg_query($con,"ROLLBACK");

			}else{
				$msg_success = true;
				pg_query($con,"COMMIT");
			}
		}
	}
}

if ($_POST["btn_acao"] == "ativar_inativar") {
    $condicao = $_POST["condicao"];
	$posto    = $_POST["posto"];

    $sql = "SELECT visivel FROM tbl_posto_condicao WHERE condicao = {$condicao} AND posto = {$posto}";
    $res = pg_query($con, $sql);

    $AuditorLog = new AuditorLog;
	$AuditorLog->RetornaDadosSelect($sql);

    if (pg_num_rows($res) > 0) {
    		$visivel_atual  = pg_fetch_result($res, 0, 'visivel');

    		$toggle_visivel = ($visivel_atual == 't') ? "f" : "t";

            $sqlUpdate = "UPDATE tbl_posto_condicao 
		            	  SET visivel = '$toggle_visivel' 
		            	  WHERE condicao = {$condicao} AND posto = {$posto}";
            $resUpdate = pg_query($con, $sqlUpdate);

            if (!pg_last_error()) {

                    $AuditorLog->RetornaDadosSelect($sql)->EnviarLog('select', 'tbl_posto_condicao',"$login_fabrica*$posto");

                    if (!$AuditorLog->OK) {
	                    $msg_erro .= 'Erro ao tentar gravar o log de registro!';
	                }

                    echo "success";
            } else {
                    echo "error";
            }
    }

    exit;
}

if ($_POST["btn_acao"] == "pesquisar") {
	if(in_array($login_fabrica, array(151))){
		$codigo_posto = $_POST["postos_selecionados"];
		$codigo_posto = str_replace(",", "','", $codigo_posto);
	} else {
		$codigo_posto    = $_POST["codigo_posto"];
		$descricao_posto = $_POST["descricao_posto"];
	}

	if(in_array($login_fabrica, array(151))){
		$descricao_posto = $codigo_posto;
	}

    if(empty($codigo_posto) AND count($msg_erro["msg"]) == 0 AND $login_fabrica <> 151){
        $msg_erro["msg"][] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "posto";
    }

    if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
        $sql = "SELECT array_agg(tbl_posto_fabrica.posto) AS posto
            FROM tbl_posto
                JOIN tbl_posto_fabrica USING(posto)
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                AND UPPER(tbl_posto_fabrica.codigo_posto) IN ('{$codigo_posto}')";
        $res = pg_query($con ,$sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][]    = "Posto não encontrado";
            $msg_erro["campos"][] = "posto";
        } else {
            $posto = pg_fetch_result($res, 0, "posto");
            $posto = str_replace("{", "(", $posto);
	    $posto = str_replace("}", ")", $posto);

	    $condPosto = " AND tbl_posto_fabrica.posto IN {$posto} ";
        }
    }

	if (empty($msg_erro["msg"])) {
		$sql = "SELECT tbl_condicao.condicao,
				tbl_condicao.descricao AS condicao_descricao,
				tbl_posto.nome AS posto_nome,
				tbl_posto_condicao.limite_minimo,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.posto,
				tbl_posto_condicao.visivel
			FROM tbl_posto_condicao
				JOIN tbl_posto ON tbl_posto_condicao.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto 
					AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_condicao ON tbl_posto_condicao.condicao = tbl_condicao.condicao 
					AND tbl_condicao.fabrica = {$login_fabrica}
			WHERE 1 = 1
			$condPosto
			ORDER BY tbl_posto_condicao.visivel DESC";
		$resSubmit = pg_query($con,$sql);

		$codigo_posto        = "";
		$descricao_posto     = "";
		$postos_selecionados = "";
		$condicao            = "";
		$limite_minimo       = "";
	}

	if( $_POST['excel'] == 't' ){
		$listaCondicaoPagamento = pg_fetch_all($resSubmit);
		$linkDownload = createCsvAndReturnLink($listaCondicaoPagamento);
	}
}

function createCsvAndReturnLink($data){ 
	$path = '../xls/';
	$fileName = "condicao_pagamento_posto.csv";
	$fullPath = $path . $fileName;
	$delimitador = ';';
	
	$handler = fopen($fullPath, "w+");

	$header = [utf8_encode("CÓD. POSTO"),utf8_encode("POSTO NOME"),utf8_encode("CONDIÇÃO"),utf8_encode("ATIVO")];
	fputcsv($handler, $header, $delimitador);

	$row = [];
	foreach($data as $item){
		list($condicao, $condicao_descricao, $posto_nome, $codigo_posto, $posto, $visivel) = array_values($item);

		$row['codigo_posto']       = $codigo_posto;
		$row['posto_nome']         = $posto_nome;
		$row['condicao_descricao'] = $condicao_descricao;
		$row['ativo']              = ($visivel == "t") ? "Ativo"   : "Inativo";

		fputcsv($handler, $row, $delimitador);
		$row = [];
	}

	return $fullPath;
}

$layout_menu = "cadastros";
$title = "CONDIÇÃO DE PAGAMENTO POR POSTO";
include 'cabecalho_new.php';

$plugins = array(
	"price_format",
	"autocomplete",
	"shadowbox",
	"multiselect"
);

include("plugin_loader.php");
?>

<script>

$(function() {
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	<?php
	if(!in_array($login_fabrica, array(151))){
		?>
	$("#condicao").multiselect({
        selectedText: "# of # selected"
    });
		<?php
	}
	?>

	$(document).on("click", ".ativo_inativo", function () {
	    if (ajaxAction()) {
    		var that     = $(this);
            var condicao = $(that).data("condicao");
			var posto    = $(that).data("posto");

            $.ajax({
                async: false,
                url: "<?=$_SERVER['PHP_SELF']?>",
                type: "POST",
                dataType: "JSON",
                data: { btn_acao: "ativar_inativar", condicao: condicao, posto:posto },
                beforeSend: function () {
                    loading("show");
                },
                complete: function (data) {
                    data = data.responseText;

                    if (data == "success") {
                        $(that).toggleClass("btn-success btn-danger");

                        if ($(that).hasClass("btn-success")) {
                        	$(that).text("Ativo");
                        } else {
                        	$(that).text("Inativo");
                        }
                    } else {
                    	alert("Erro ao alterar condição");
                    }

                    loading("hide");
                }
            });
	    }
    });
});

function retorna_posto(infoPosto){
	<?php
	if(in_array($login_fabrica, array(151))){
	?>
	const option     = document.createElement('option');
	option.innerText = infoPosto.codigo + ' - ' + infoPosto.nome;
	option.value     = infoPosto.codigo;

	const postos = $('#postos');
	postos.append(option);

	$("#codigo_posto").val("");
	$("#descricao_posto").val("");
	<?php
	} else {
		?>
		$("#codigo_posto").val(infoPosto.codigo);
		$("#descricao_posto").val(infoPosto.nome);
		<?php
	}
	?>
}

$( document ).ready(function() {
	$("#removerPosto").on("click", function(){
		const selectPosto = $('#postos');
		const postosSelecionados = selectPosto.children("option:selected");

		Array.from(postosSelecionados).forEach(item => item.remove());
	});

	<?php
	if(in_array($login_fabrica, array(151))){
		?>
	$("#frm_condicao_pagamento_posto").on("submit",function(){
		const selectPosto = $('#postos');
		var itens         = "";
		
		selectPosto.children().each(function(key, option) { 
			if(itens != ""){
				itens += ",";
			}
			itens += option.value;
		}); 

		$("#postos_selecionados").val(itens);
	});

	$(".condPosto").on("click",function(){

		let codigo = $(this).data('codigo');
		let nome   = $(this).data('nome');
		let cond   = $(this).data('cond');
		let valor  = $(this).data('valor');
		
		const selectPosto = $('#postos');
		const option     = document.createElement('option');
		option.innerText = codigo + ' - ' + nome;
		option.value     = codigo;
		postos.append(option);

		$("#condicao").val(cond);
		$("#valor_minimo").val(valor);


	});
		<?php
	}
	?>
});
</script>

<?php
    if(count($msg_erro["msg"]) > 0){
        echo "<div class='alert alert-danger'><h4>";
        echo implode("<br />", $msg_erro["msg"]);
        echo "</h4></div>";
    }

	if($msg_success == true){
		$codigo_posto        = "";
		$descricao_posto     = "";
		$postos_selecionados = "";
		$condicao            = "";
		$limite_minimo       = "";
	    ?>
	    <div class="alert alert-success">
	        <h4><?=traduz("Condição de Pagamento cadastrado com Sucesso")?>!</h4>
	    </div>
	    <?php
	}

?>

<div class="row">
	<b class="obrigatorio pull-right">* <?=traduz("Campos obrigatórios")?> </b>
</div>

<form id="frm_condicao_pagamento_posto" method="post" class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '><?=traduz("Parâmetros de Pesquisa")?></div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>

		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'><?=traduz("Código Posto")?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?=$codigo_posto?>" >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>

		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='descricao_posto'><?=traduz("Nome Posto")?></label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?=$descricao_posto?>" >&nbsp;
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>

		<div class='span2'></div>
	</div>
<?php
if(in_array($login_fabrica, array(151))){
	?>
	<div class='row-fluid'>
		<div class='span2'></div>

		 <div class='span9'>
		 	<div style="padding-right: 7%">
		 		<input type="hidden" id="postos_selecionados" name="postos_selecionados" value="<?=$postos_selecionados?>">
				<select name="postos[]" id="postos" multiple style="width: 100%">
					<?php foreach ($postos as $posto) : ?>
						<option value="<?= $posto[0] ?>//<?= $posto[1] ?>"> <?= $posto[0] ?> - <?= $posto[1] ?> </option>
					<?php endforeach; ?>
				</select>
			</div>
		 </div>

		 <div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>

		<div class='span8'>
			<div style="text-align: center">
				<button type="button" class="btn btn-danger removerPosto" id="removerPosto"><?=traduz("Remover Posto")?></button>
			</div>
		</div>
	</div>
	<?php
}
?>
	<div class='row-fluid'>
		<div class='span2'></div>

		<div class='span4'>
            <div class='control-group <?=(in_array("condicao", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='condicao'><?=traduz("Condição Pagamento")?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                        <?php
                    	$multiple = 'multiple="multiple"';

                        if(in_array($login_fabrica, array(151))){
                        	$multiple = "";
                        }
                        ?>
                        <select name="condicao[]" id="condicao" <?=$multiple?>>
                            <?php
                            if(in_array($login_fabrica, array(151))){
                            	?>
                            	<option value="">Selecione</option>
                            	<?php
                            }
                            $sql = "SELECT condicao,descricao
                                FROM tbl_condicao
								WHERE fabrica = {$login_fabrica}
									AND visivel IS TRUE
									ORDER BY descricao";
                            $res = pg_query($con,$sql);

                            foreach (pg_fetch_all($res) as $key) {
                                $selected_condicao = ( isset($condicao) and (in_array($key['condicao'],$condicao)) ) ? "SELECTED" : '' ;
                            	?>
                            	<option value="<?=$key['condicao']?>" <?=$selected_condicao?>><?=$key['descricao']?></option>
                            	<?php
                            }
                            ?>
                        </select>
                        <div><strong></strong></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        if(in_array($login_fabrica, array(151))){
        ?>
		<div class='span4'>
			<div class='control-group <?=(in_array("condicao", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='condicao'><?=traduz("Valor Mínimo")?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                    	<input type="text" name="valor_minimo" id="valor_minimo" value="<?=$limite_minimo?>" price=true>
                    </div>
                </div>
            </div>
		</div>
		<?php
		}
		?>
		<div class='span2'></div>
	</div>
	<?php
	if(in_array($login_fabrica, array(151))){
	?>
	<div class='row-fluid'>
		<div class='span2'></div>

		<div class='span8'>
			<?php if( !empty($linkDownload) ){
				?>
				<a href="<?=$linkDownload?>" download target="_blank">
					<h5> Baixar Excel </h5> 
				</a>
				<?php
			}
			?>

			<div style="margin-bottom: 15px; display: flex; justify-content: center">
				<h5 style="margin-right: 15px;"> Gerar Excel </h5>
				<div style="display: flex; justify-content: center">
					<div style="margin-right: 10px; display: flex; align-items: center;">
						<label for="excel_t" style="margin-right: 3px;"> Sim </label>
						<input type="radio" value="t" name="excel" id="excel_t" <?= $_POST['excel'] == 't' ? 'checked' : null ?>>
					</div>
					<div style="display: flex; align-items: center;">
						<label for="excel_f" style="margin-right: 3px;"> Não </label>
						<input type="radio" value="f" name="excel" id="excel_f" <?= $_POST['excel'] != 't' ? 'checked' : null ?>>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	}
	?>
	<p><br/>
		<button class='btn btn-success' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz("Gravar")?></button>
		<button class='btn btn-info' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'pesquisar');"><?=traduz("Pesquisar")?></button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

<?php

if($resSubmit){
	$rows = pg_num_rows($resSubmit);

	if ($rows > 0) {
	?>
		<table class="table table-bordered">
			<thead>
				<tr class="titulo_coluna" >
					<th><?=traduz("Cód. Posto")?></th>
					<th><?=traduz("Posto Nome")?></th>
					<?php
					if(in_array($login_fabrica, array(151))){
						?>
						<th><?=traduz("Valor Mínimo")?></th>
						<?php
					}
					?>
					<th><?=traduz("Condição")?></th>
					<th><?=traduz("Ativo")?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $rows; $i++) {
					$posto              = pg_fetch_result($resSubmit, $i, "posto");
					$codigo_posto       = pg_fetch_result($resSubmit, $i, "codigo_posto");
					$nome_posto         = pg_fetch_result($resSubmit, $i, "posto_nome");
					$condicao           = pg_fetch_result($resSubmit, $i, "condicao");
					$condicao_descricao = pg_fetch_result($resSubmit, $i, "condicao_descricao");
					$visivel            = pg_fetch_result($resSubmit, $i, "visivel");
					$limite_minimo      = pg_fetch_result($resSubmit, $i, "limite_minimo");
					?>

					<tr id="pedido_<?=$condicao?>" >
						<td class="tac">
						<?php
							if(in_array($login_fabrica,[151])){
								echo "<a href='javascript: void(0)' class='condPosto' data-codigo='{$codigo_posto}' data-nome='{$nome_posto}' data-cond='{$condicao}' data-valor='{$limite_minimo}'>{$codigo_posto}</a>";
							}else{
								echo $codigo_posto;
							}
						?>
						</td>
						<td class="tac"><?=$nome_posto?></td>
						<?php
						if(in_array($login_fabrica, array(151))){
							?>
							<td class="tac"><?=$limite_minimo?></td>
							<?php
						}
						?>
						<td><?=$condicao_descricao?></td>
						<td class="tac">
							<?php 
							$class_btn = ($visivel == "t") ? "success" : "danger";
							$desc_btn  = ($visivel == "t") ? "Ativo"   : "Inativo";
							?>
                            <button type="button" name="ativo_inativo" class="ativo_inativo btn btn-<?= $class_btn ?>" data-condicao="<?= $condicao ?>" data-posto="<?= $posto ?>">
                            	<?= $desc_btn ?>
                            </button>
						</td>
					</tr>

				<?php
				}
				?>
			</tbody>
		</table>
		<div class="row-fluid">
			<div class="span12 tac">
				<a href='relatorio_log_alteracao_new.php?parametro=tbl_posto_condicao&id=<?php echo $posto; ?>' target='_blank' name="btnAuditorLog">Visualizar Log Auditor</a>
			</div>
		</div>


	<?php
	} else {
	?>
		<div class="alert alert-error"><h4><?=traduz("Nenhum resultado encontrado")?></h4></div>
	<?php
	}

}
include "rodape.php";
?>
