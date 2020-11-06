<?php

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';
include __DIR__.'/autentica_usuario.php';
include __DIR__.'/funcoes.php';
include __DIR__.'/rotinas/esab/classes/extrato.php';

if (isset($_POST["ajax_gerar_extrato_os"])) {
	$arrayOs = $_POST["os"];
	$retorno = array();

	$erro = false;
	$usa_lgr = true;

	/*
	* Resgata o período dos 15 dias
	*/
	$data_15 = getPeriodoDiasLGR(14, date('Y-m-d H:i:s'));

	try {

		if(!count($arrayOs)){
			throw new \Exception("Selecione uma OS");
		}

		pg_query($con, "BEGIN TRANSACTION");

		$sql = "INSERT INTO tbl_extrato (fabrica, posto, data_geracao, mao_de_obra, pecas, total, avulso)
					VALUES
					({$login_fabrica}, {$login_posto},CURRENT_TIMESTAMP, 0, 0, 0, 0) RETURNING extrato";
		$res = pg_query($con,$sql);

		if(pg_last_error() > 0){
			throw new \Exception("Ocorreu um erro ao gerar extrato");
		}

		$extrato = pg_fetch_result($res, 0, "extrato");

		if(in_array($login_fabrica, [152,180,181,182])) {
			$sql_extrato_status = "INSERT INTO tbl_extrato_status (fabrica, extrato, data, obs, advertencia) 
                        VALUES ($login_fabrica, $extrato, now(), 'Pendente de Aprovação', false)";
            $res_extrato_status = pg_query($con, $sql_extrato_status);
		}
		/*
		* Relaciona as OSs com o Extrato
		*/
		foreach ($arrayOs as $os) {
			relacionaExtratoOSTela($login_fabrica, $login_posto, $extrato, date("Y-m-d"), $marca = null, $os);
		}

		/*
		* Insere lançamentos avulsos para o Posto
		*/

		atualizaAvulsoDoPosto($login_fabrica,$login_posto,$extrato);


		/*
		* Insere valor dos avulsos para o extrato
		*/
		atualizaValor($login_fabrica,$extrato);

		/**
		* Verifica LGR
		*/
		if($usa_lgr == true){
			verificaLGRClasse($extrato, $login_posto, $data_15);
		}

		/*
		* verifica valor avulso para nao gerar extrato negativo
		*/
		$total_extrato = calculaExtrato($extrato);

		if($login_fabrica == 145) {
            LGRNovo($extrato, $login_posto, $login_fabrica);
		}
		if($total_extrato <= 0){
			throw new \Exception("Extrato com valor negativo ou zerado acumulando");
		}

		/*
		* Commit
		*/
		if($erro == false){
			pg_query($con,"COMMIT");
			$retorno = array("success" => true, "extrato" => $extrato);
		}

	} catch (\Exception $e){

		pg_query($con,"ROLLBACK");
		$retorno = array("error" => true, "msg" => utf8_encode($e->getMessage()));
	}

	exit(json_encode($retorno));
}

function getOsProduto($os) {
	global $con, $login_fabrica, $login_posto;

	if (empty($os)) {
		return null;
	}

	$sql = "SELECT tbl_produto.descricao || ' - ' || tbl_produto.referencia AS produto
			FROM tbl_os
			INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
			INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
			WHERE tbl_os.fabrica = {$login_fabrica}
			AND tbl_os.posto = {$login_posto}
			AND tbl_os.os = {$os}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) == 0) {
		return null;
	}

	if (pg_num_rows($res) > 1) {
		$produtos = array();

		while ($produto = pg_fetch_object($res)) {
			$produtos[] = $produto->produto;
		}

		return $produtos;
	} else {
		return pg_fetch_result($res, 0, "produto");
	}
}

$sql = "SELECT
			tbl_os.os,
			tbl_os.sua_os,
			TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
			TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
			tbl_tipo_atendimento.descricao AS tipo_atendimento,
			TO_CHAR(tbl_os_extra.extrato_geracao, 'DD/MM/YYYY') AS extrato_geracao,
			tbl_os.mao_de_obra,
			tbl_os.qtde_km_calculada,
			tbl_os.valores_adicionais
		FROM tbl_os
		INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
		LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
		WHERE tbl_os.fabrica = {$login_fabrica}
		AND tbl_os.posto = {$login_posto}
		AND (tbl_os.data_fechamento IS NOT NULL AND tbl_os.finalizada IS NOT NULL)
		AND tbl_os_extra.extrato IS NULL
		AND tbl_os_extra.extrato_geracao IS NULL
		AND tbl_os.excluida is not true
		ORDER BY tbl_os.data_fechamento ASC";
		// echo nl2br($sql);
$resSubmit = pg_query($con, $sql);

$title = "Gerar Extrato";
include __DIR__.'/cabecalho_new.php';

$plugins = array(
   "shadowbox",
   "dataTable"
);

include __DIR__."/admin/plugin_loader.php";

if(count($msg_erro["msg"]) > 0) {
?>
	<br />
	<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro["msg"])?></h4></div>
<?php
}
?>

<style>
div.divOsProdutos {
	display: none;
}
</style>

<script>
$(function() {

	Shadowbox.init();

	$("button.verProdutos").click(function() {
		var div = $(this).next().clone();

		Shadowbox.open({
			content: $(div).html(),
			player: "html",
			width: 400,
			height: 300
		});
	});

	$("#selecionar_todos").change(function() {
		if ($(this).is(":checked")) {
			$("input[name='os[]']").each(function() {
				$(this)[0].checked = true;
			});
		} else {
			$("input[name='os[]']").each(function() {
				$(this)[0].checked = false;
			});
		}
	});

	$("button.gerar_extrato_os").click(function() {
		var button = $(this);

		var os = [];

		$("input[name='os[]']").each(function() {
			if ($(this).is(":checked")) {
				os.push($(this).val());
			}
		});

		if (os.length > 0) {
			var erro = [];

			$.ajax({
				url: "liberar_os_extrato.php",
				type: "POST",
				data: { ajax_gerar_extrato_os: true, os: os },
				beforeSend: function() {
					$(button).button("loading");
					$("div.erro-libera-os > strong").html("");
					$("div.erro-libera-os").hide();
					$("div.extrato-gerado").html("").hide();
				}
			})
			.done(function(data) {
				data = JSON.parse(data);


				if(data.error){
					$("div.erro-libera-os > strong").html(data.msg);
					$("div.erro-libera-os").show();

				}else if (data.success == true){
					os.forEach( function(osid) {
						$("tr[rel="+osid+"]").find("td").first().html("<label class='label label-success' >Entrou no extrato: "+data.extrato+"</label>");
					});
					$("div.extrato-gerado").html("<strong>Foi gerado de número "+data.extrato+"</strong><br />Extrato está aguardando a liberação fábrica, após liberação estará disponível para a consulta.").show();
				}

				$(button).button("reset");
			});
		} else {
			alert("Selecione uma ordem de serviço");
		}
	});
});

</script>

<?php

if (isset($resSubmit)) {
	if (pg_num_rows($resSubmit) > 0) {
	?>
		<br />

		<div class="alert alert-error erro-libera-os" style="display:none;">
			<!-- <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> -->
			<strong></strong>
		</div>

		<div class="alert alert-success extrato-gerado" style="display:none;">
			<!-- <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> -->
			<strong></strong>
		</div>


		<table class="table table-striped table-bordered table-hover table-fixed" >
			<thead>
				<tr class="titulo_coluna" >
					<th><input type="checkbox" id="selecionar_todos" title="Selecionar todas as OSs" /></th>
					<th>OS</th>
					<th>Data Abertura</th>
					<th>Data Fechamento</th>
					<th>Tipo de Atendimento</th>
					<th>Produto</th>
					<th>Valor prévio da OS</th>
				</tr>
			</thead>
			<tbody>
				<?php
				while ($os = pg_fetch_object($resSubmit)) {
					$produto = getOsProduto($os->os);
					$totalizador = (float) ($os->mao_de_obra + $os->qtde_km_calculada + $os->valores_adicionais);

					?>
					<tr rel="<?=$os->os?>" >
						<td class="tac" ><input type="checkbox" name="os[]" value="<?=$os->os?>" /></td>
						<td><a href="os_press.php?os=<?=$os->os?>" target="_blank" ><?=$os->sua_os?></a></td>
						<td><?=$os->data_abertura?></td>
						<td><?=$os->data_fechamento?></td>
						<td><?=utf8_decode($os->tipo_atendimento)?></td>
						<td>
							<?php
							if (is_array($produto)) {
								echo "
									<button type='button' class='verProdutos btn btn-link' >Ver Produtos</button>
									<div class='divOsProdutos' >
									<div class='titulo_tabela '>OS: {$os->sua_os}</div>
									<br />
									<ul>
								";

								foreach ($produto as $value) {
									echo "<li>{$value}</li>";
								}

								echo "
									</ul>
									</div>
								";
							} else {
								echo $produto;
							}
							?>
						</td>
						<td><?=number_format($totalizador,2,',','.')?></td>
					</tr>
				<?php
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="7" >
						<button type='button' class='gerar_extrato_os btn btn-success' data-loading-text='Gerando Extrato...' >Gerar Extrato para as OSs selecionadas</button>
					</td>
				</tr>
			</tfoot>
		</table>

		<br />
	<?
	} else {
	?>
		<div class="alert alert-danger" >
			<h5>Nehum resultado encontrado</h5>
		</div>
	<?php
	}
}

include "rodape.php";

?>
