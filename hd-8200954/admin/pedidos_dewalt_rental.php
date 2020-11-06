<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if (isset($_POST['ajax_grava_obs'])) {
	$pedido = $_POST['pedido'];
	$obs    = pg_escape_string($con,$_POST['obs']);

	$sqlAdmin = "SELECT login
				 FROM tbl_admin 
				 WHERE admin = $login_admin";
	$resAdmin = pg_query($con, $sqlAdmin);

	$nome_admin = pg_fetch_result($resAdmin, 0, 'login');

	$obs_pedido = "<b>".date("d/m/Y H:i:s")." $nome_admin</b> - ".$obs." <br /><br />";

	$sql = "UPDATE tbl_pedido SET obs = coalesce(obs,'') || '$obs_pedido' WHERE pedido = $pedido";
	pg_query($con, $sql);

	if (!pg_last_error()) {
		exit("sucesso");
	} else {
		exit("erro");
	}
}

if (isset($_POST['ajax_aprova_cancela_pedido'])) {

	pg_query($con,"BEGIN TRANSACTION");

	$pedido = $_POST['pedido'];
	$acao   = $_POST['acao'];

	$status_pedido           = ($acao == 'aprovar') ? 1 : 14;
	$status_pedido_descricao = ($acao == 'aprovar') ? 'Pedido Dewalt Rental aprovado' : 'Pedido Dewalt Rental reprovado';

	$sqlStatusPedido = "UPDATE tbl_pedido 
						SET status_pedido = $status_pedido
						WHERE pedido = $pedido";
	pg_query($con, $sqlStatusPedido);

	$sqlStatus = "INSERT INTO tbl_pedido_status (pedido,status,observacao,admin) VALUES ($pedido,$status_pedido,'$status_pedido_descricao',$login_admin)";
	pg_query($con, $sqlStatus);


	if ($acao != "aprovar") {
		$sqlPedido = "SELECT 
							tbl_pedido.distribuidor,
							tbl_pedido_item.peca
					  FROM tbl_pedido
					  JOIN tbl_pedido_item USING(pedido) 
					  WHERE pedido = $pedido";
		$resPedido = pg_query($con, $sqlPedido);			  

		while ($row = pg_fetch_array($resPedido)) {
			$distribuidor = (empty($row['distribuidor'])) ? "null" : $row['distribuidor'];
			$peca         = $row['peca'];

			$sqlCancela  = "SELECT fn_pedido_cancela($distribuidor,$login_fabrica,$pedido,$peca,'Pedido reprovado em auditoria Dewalt Rental',$login_admin)";
			pg_query($sqlCancela);

		}
	}

	$sqlPosto = "SELECT posto,seu_pedido 
                     FROM tbl_pedido
                     WHERE tbl_pedido.pedido = $pedido";
    $resPosto = pg_query($con,$sqlPosto);

    $posto      = pg_fetch_result($resPosto, 0, 'posto');
    $seu_pedido = pg_fetch_result($resPosto, 0, 'seu_pedido');

    //$pedido_descricao_comunicado = ($acao == 'aprovar') ? 'O pedido '.$seu_pedido.' foi aprovado pelo fabricante' : 'O pedido '.$seu_pedido.' foi reprovado pelo fabricante';

	/*$sqlComunicado = "INSERT INTO tbl_comunicado (mensagem,descricao,posto,fabrica,tipo,ativo,obrigatorio_site,pais,obrigatorio_os_produto,digita_os,reembolso_peca_estoque,destinatario_especifico) 
                          VALUES ('$pedido_descricao_comunicado <br />','$status_pedido_descricao',$posto,$login_fabrica,'Comunicado Automatico','t','t','BR','f',null,null,'')";
    $res = pg_query($con,$sqlComunicado);*/

	if (!pg_last_error($con)) {
		pg_query($con,"COMMIT TRANSACTION");
		exit("sucesso");
	} else {
		pg_query($con,"ROLLBACK TRANSACTION");
		exit("erro");
	}
}

if ($_POST["btn_gravar"] == "Pesquisar") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];
	$status_pedido      = $_POST['status_pedido'];

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

	if (!empty($status_pedido)) {
		$condStatus = " AND (
							SELECT ps.status
							FROM tbl_pedido_status ps
							WHERE ps.pedido = tbl_pedido.pedido
							ORDER BY ps.data DESC
							LIMIT 1
						) = $status_pedido";
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

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	$condData = " AND tbl_pedido.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";

	if (!count($msg_erro["msg"])) {

		if (!empty($posto)) {
			$cond_posto = " AND tbl_pedido.posto = {$posto} ";
		}else{
			$cond_posto = " AND tbl_pedido.posto <> 6359 ";
		}

		$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";

		$sql = "SELECT 
					tbl_posto.nome as nome_posto,
					tbl_posto_fabrica.contato_estado,
					tbl_posto_fabrica.contato_cidade,
					tbl_posto_fabrica.codigo_posto,
					tbl_pedido.data,
					tbl_pedido.total as total_pedido,
					tbl_pedido.finalizado,
					tbl_pedido.pedido,
					tbl_pedido.seu_pedido,
					(
					    SELECT SUM((tbl_pedido_item.preco * tbl_pedido_item.ipi) * tbl_pedido_item.qtde)
					    FROM tbl_pedido_item
					    WHERE tbl_pedido_item.pedido = tbl_pedido.pedido
					) as total_pedido_ipi
				FROM tbl_pedido
				JOIN tbl_posto ON tbl_posto.posto = tbl_pedido.posto
				JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto
				JOIN tbl_pedido_status ON tbl_pedido_status.pedido = tbl_pedido.pedido 
				AND UPPER(tbl_pedido_status.observacao) = 'PEDIDO DEWALT RENTAL EXCEDEU O VALOR PERMITIDO'
				AND tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
				WHERE tbl_pedido.tipo_pedido = 94
				$cond_posto
				$condData
				$condStatus
				$limit
				";

		$resSubmit = pg_query($con, $sql);
	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_os_atendimento-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='7' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE OS X ATENDIMENTOS
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Pedido</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Abertura</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Finalizado</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>UF</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Total</th>
						</tr>
					</thead>
					<tbody>";
			fwrite($file, $thead);
			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {

				$pedido 			= pg_fetch_result($resSubmit, $x, 'pedido');
				$data 				= pg_fetch_result($resSubmit, $x, 'data');
				$finalizado 		= pg_fetch_result($resSubmit, $x, 'finalizado');
				$posto_codigo 		= pg_fetch_result($resSubmit, $x, 'codigo_posto');
				$posto_descricao 	= pg_fetch_result($resSubmit, $x, 'nome_posto');
				$contato_cidade 	= pg_fetch_result($resSubmit, $x, 'contato_cidade');
				$uf_posto 			= pg_fetch_result($resSubmit, $x, 'contato_estado');
				$total_pedido 		= pg_fetch_result($resSubmit, $x, 'total_pedido_ipi');

				$body .="
					<tr>
						<td nowrap align='center' valign='top'>{$pedido}</td>
						<td nowrap align='center' valign='top'>{$data}</td>
						<td nowrap align='center' valign='top'>{$finalizado}</td>
						<td nowrap align='center' valign='top'>{$posto_codigo} - {$posto_descricao}</td>
						<td nowrap align='center' valign='top'>{$contato_cidade}</td>
						<td nowrap align='left' valign='top'>{$uf_posto}</td>
						<td nowrap align='center' valign='top'>{$total_pedido}</td>
					</tr>";

			}

			fwrite($file, $body);
			fwrite($file, "
						<tr>
							<th colspan='7' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
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

$layout_menu = "auditoria";
$title = "Auditoria De Pedidos D.R. Acima do Valor Máximo";
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

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("#btn_pedido_dewalt").click(function(){
			Shadowbox.open({
	            content: "valor_maximo_dewalt.php",
	            player: "iframe",
	            width: 700,
	            height: 250
	        });
		});

		$(".btn_acao").click(function(){
			var btn    = $(this);
			var pedido = $(btn).data("pedido");
			var tipo   = $(btn).data("tipo");

			if (tipo == 'aprovar' || tipo == 'cancelar') {
				var r = confirm("Deseja realmente "+tipo+" esse pedido?");
			}

      		if (r == true || tipo == 'alterar') {

				if (tipo == 'alterar') {
					Shadowbox.open({
			            content: "pecas_pedido.php?pedido="+pedido+"&pedido_dewalt=t",
			            player: "iframe",
			            width: 1000,
			            height: 700
			        });
				} else {
					$.ajax({
				        url: 'pedidos_dewalt_rental.php',
				        type: "POST",
				        data: {
				        	ajax_aprova_cancela_pedido: true, 
				        	pedido: pedido,
				        	acao: tipo 
				        },
				        timeout: 7000
				    }).fail(function(){
				        alert('Falha ao buscar condição');
				    }).done(function(data){

				    	if (tipo == 'aprovar') {
				    		var msg = "Pedido aprovado com sucesso";
				    		var classe  = "success";
				    	} else {
				    		var msg = "Pedido cancelado com sucesso";
				    		var classe = "important";
				    	}

				        if (data != "erro") {
				        	$(btn).closest("td").html("<div class='label label-"+classe+"'>"+msg+"</div>");
				        }
				    });
				}
			}
		});

		$(".btn-obs").click(function(){
			$("#grava_obs_pedido").attr("pedido",$(this).data('pedido'));
			$("#observacao").val("");
		});

		$("#grava_obs_pedido").click(function(){
			var pedido = $(this).attr('pedido');
			var obs    = $("#observacao").val();

			$.ajax({
			        url: 'pedidos_dewalt_rental.php',
			        type: "POST",
			        data: {
			        	ajax_grava_obs: true, 
			        	pedido: pedido,
			        	obs : obs
			        },
			        timeout: 7000
			    }).fail(function(){
			        alert('Falha ao inserir obs');
			    }).done(function(data){

			        if (data != "erro") {
			        	alert("Observação cadastrada com sucesso");
			        	$(".close").click();
			        }
			});
		});

	});

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
<form name='frm_pedidos' method='post' action="<?=$PHP_SELF?>" align='center' class="form-search form-inline tc_formulario">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class="container tc_container">

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
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<!--<h5 class='asteristico'>*</h5>-->
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='posto_nome'>Razão Social</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<!--<h5 class='asteristico'>*</h5>-->
							<input type="text" name="posto_nome" id="descricao_posto" class='span12' value="<? echo $posto_nome ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label' for='descrição peça'>Status do pedido</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<select name="status_pedido">
									<option value="18" <?php echo ($status_pedido == "18")? " selected " : " " ?> >Aguardando Aprovação</option>
									<option value="1" <?php echo ($status_pedido == "1")? " selected " : " " ?> >Pedidos Aprovados</option>
									<option value="14" <?php echo ($status_pedido == "14")? " selected " : " " ?> >Pedidos Cancelados</option>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
			<br />
			<?php 
				if (verificaAdminSupervisor($login_admin)) {
				?>
					<div class='row-fluid'>
						<div class='span2'></div>
						<div class='span8 tac'>
							<input type='button' id='btn_pedido_dewalt' name='btn_pedido_dewalt' value='Cadastrar Valor Máximo Para Pedidos Dewalt Rental' class='btn btn-primary' />
						</div>
						<div class='span2'></div>
					</div>
				<?php 
				}
			?>
			<center>
				<input type='submit' name='btn_gravar' value='Pesquisar' class='btn' />
				<input type='hidden' name='acao' value="<?=$acao?>" />
			</center>
			<br />

		</div>

	</form>
</div>
<?php
if (pg_num_rows($resSubmit) > 0) { 
	if (pg_num_rows($resSubmit) > 500) { ?>
		<div class="alert alert-warning">
			<h4>Em tela serão mostrados apenas 500 resultados, para visualizar os dados completos baixe o arquivo excel</h4>
		</div>
	<?php
	}
	?>
	  <div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
          <h3 id="myModalLabel">Informe uma Observação</h3>
        </div>
        <div class="modal-body tac">
          <p>
            <textarea name="observacao" id="observacao" style="width: 450px;">
            </textarea>
          </p>
        </div>
        <div class="modal-footer">
          <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
          <button type='button' pedido='' class='btn btn-primary' id="grava_obs_pedido">Gravar</button>
        </div>
      </div> 
	<br />
	<table class="table table-bordered" id="tabela_pedidos" style="width: 98%;">
		<thead>
			<tr class="titulo_tabela">
				<th colspan="8">Lista de pedidos</th>
			</tr>
			<tr class="titulo_coluna">
				<th>Pedido</th>
				<th>Abertura</th>
				<th>Finalizado</th>
				<th>Posto</th>
				<th>Cidade</th>
				<th>UF</th>
				<th>Total</th>
				<th>Ações</th>
			</tr>
		</thead>
		<tbody>
			<?php 
			for($x=0;$x<pg_num_rows($resSubmit);$x++) {

				$pedido 			= pg_fetch_result($resSubmit, $x, 'pedido');
				$seu_pedido         = pg_fetch_result($resSubmit, $x, 'seu_pedido');
				$data 				= pg_fetch_result($resSubmit, $x, 'data');
				$finalizado 		= pg_fetch_result($resSubmit, $x, 'finalizado');
				$posto_codigo 		= pg_fetch_result($resSubmit, $x, 'codigo_posto');
				$posto_descricao 	= pg_fetch_result($resSubmit, $x, 'nome_posto');
				$contato_cidade 	= pg_fetch_result($resSubmit, $x, 'contato_cidade');
				$uf_posto 			= pg_fetch_result($resSubmit, $x, 'contato_estado');
				$total_pedido 		= pg_fetch_result($resSubmit, $x, 'total_pedido_ipi');
			?>
				<tr>
					<td class="tac">
						<a href="pedido_admin_consulta.php?pedido=<?= $pedido ?>" target="_blank">
							<?= $seu_pedido ?>
						</a>
					</td>
					<td class="tac">
						<?= mostra_data_hora($data) ?>
					</td>
					<td class="tac">
						<?= mostra_data_hora($finalizado) ?>
					</td>
					<td>
						<?= $posto_codigo ?> - <?= $posto_descricao ?>
					</td>	
					<td>
						<?= $contato_cidade ?>
					</td>
					<td class="tac">
						<?= $uf_posto ?>
					</td>
					<td class="tac">
						R$ <?= number_format($total_pedido,2) ?>
					</td>
					<td class="tac">
					<?php 
					if ($_POST["status_pedido"] == '18') {
					?>
						<button class="btn btn-success btn-small btn_acao" data-pedido="<?= $pedido ?>" data-tipo="aprovar">
							Aprovar
						</button>
						<button class="btn btn-danger btn-small btn_acao" data-pedido="<?= $pedido ?>" data-tipo="cancelar">
							Cancelar
						</button>
						<button class="btn btn-primary btn-small btn_acao" data-pedido="<?= $pedido ?>" data-tipo="alterar">
							Alterar
						</button>
						<button href='#myModal' role='button' data-toggle='modal' class="btn btn-warning btn-small btn-obs" data-pedido="<?= $pedido ?>" data-tipo="observacao">
							Observações
						</button>
					<?php 
					} ?>
					</td>
				</tr>
			<?php	
			}
			?>									
		</tbody>
	</table>
	<?php
		$jsonPOST = excelPostToJson($_POST);
	?>

	<div id='gerar_excel' class="btn_excel">
		<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Gerar Arquivo Excel</span>
	</div>
<?php
} else if ($_POST["btn_gravar"] == "Pesquisar") { ?>
	<div class="container">
		<div class="alert alert-warning">
			<h4>Nenhum resultado encontrado</h4>
		</div>
	</div>
<?php
}
?>
	<script>
		$.dataTableLoad({ 
			table: "#tabela_pedidos"
		});
	</script>
<?php
include 'rodape.php';?>
