<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
require_once '../funcoes.php';

define('BS3', true);

if ($login_fabrica != 10) {
	exit;
}

if (isset($_POST["ajax_nota"])) {
	$hd_chamado  = $_POST["hd_chamado"];
	$nota_fiscal = $_POST["nota_fiscal"];
	$data_nf     = $_POST["data_nf"];

	$xdata_nf = formata_data($data_nf);

	$sql = "SELECT hd_chamado 
			FROM tbl_hd_chamado_extra
			WHERE hd_chamado = $hd_chamado";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) == 0) {
		$sql = "INSERT INTO tbl_hd_chamado_extra (hd_chamado, nota_fiscal, data_nf) 
				VALUES ($hd_chamado, '$nota_fiscal', '$data_nf')";
	} else {
		$sql = "UPDATE tbl_hd_chamado_extra 
				SET nota_fiscal = '$nota_fiscal',data_nf = '$data_nf' 
				WHERE hd_chamado = $hd_chamado";		
	}	

	pg_query($con, $sql);

	if (!pg_last_error($con)) {
		$retorno = array("ok" => "sim","hd_chamado" => $hd_chamado, "nota_fiscal" => $nota_fiscal, "data_nf" => $data_nf);
		exit(json_encode($retorno));
	} else {
		exit(json_encode(array("ok" => "erro")));
	}
}

$TITULO = "Relatório de Horas Faturadas";
$valor_hora_chat = 200;

$btn_acao = $_POST['pesquisar'];
$hd_chamado = $_POST['hd_chamado'];
$pago = $_POST['pago'];
$parcela = $_POST['parcela'];

if(!empty($hd_chamado) and !empty($pago)) {

	$sql = "SELECT data_pagamento
			FROM tbl_hd_chamado
			WHERE hd_chamado = $hd_chamado
			AND data_pagamento IS NULL";

	$res = pg_query($res, $sql);

	if (pg_num_rows($res) > 0) {
		$sql = "UPDATE tbl_hd_chamado SET
				data_pagamento = now()
				where hd_chamado = $hd_chamado";
		$res = pg_query($con,$sql);
	}

	$msg_erro = pg_last_error();	
	if(empty($msg_erro)) {
		echo "ok";
	}else{
		echo "erro ";
	}
	exit;
}

if(!empty($hd_chamado) and !empty($parcela)) {
	$numParcela = $_POST['numParcela'];
	$sql = "UPDATE tbl_controle_implantacao set 
				data_finalizacao = now(),
				finalizada = 't'				
				where hd_chamado = $hd_chamado and numero_parcela = $numParcela";
	$res = pg_query($con,$sql);

	echo pg_last_error($con);
	$msg_erro = pg_last_error();	
	if(empty($msg_erro)) {
		echo "ok";
	}else{
		echo "erro ";
	}
	exit;
}

if (isset($_POST['pesquisar']) || isset($_POST['chamados_ignorar'])) {

	$fabrica_busca       = $_POST['fabrica_busca'];
	$aux_mes             = $_POST['mes'];
	$aux_ano             = $_POST['ano'];
	$com_horas_faturadas = $_POST['com_horas_faturadas'];
	$data_inicial = $_POST['data_inicial'];
	$data_final = $_POST['data_final'];

}?>

<style type="text/css">
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.formulario{
		background-color:#D9E2EF;
		font:12px Arial;
	}
	.tac{
		text-align: center !important;
	}

	.fundo_tr {
		background-color: #f2dede !important;
	}

	td {
		vertical-align:middle !important;
	}
</style><?php

function converte_data($date) {
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

function verificaHoraTrabalhada($hora_trabalhada, $hora_cobrada) {
	if (empty($hora_trabalhada) || empty($hora_cobrada)) {
		return false;
	}
	$quebraHoraTrab = explode(":",$hora_trabalhada);
	$minutosTrab = $quebraHoraTrab[0];
	$minutosTrab = $minutosTrab*60;
	$minutosTrab =$minutosTrab+$quebraHoraTrab[1];
	$minutosCob = $hora_cobrada*60;
	return ($minutosTrab > $minutosCob) ? true : false;
}

include "menu.php";
?>

<script type="text/javascript" >
	$(function() {
		$("#data_inicial,#data_final").datepicker().mask("99/99/9999");
		$("input[name^=data_nf_]").datepicker().mask("99/99/9999");
		$("input[name^=nota_fiscal_]").numeric();

		var retira_botao_remover = true;

		$("input[name^=retira_excel_]").each(function() {
			var retira_botao_remover = false;
		});

		if (!retira_botao_remover) {
			$("#remover_chamados").hide();
		}  

		$("#remover_chamados").click(function() {
			var chamados = [];
			$("input[name^=retira_excel_]:checked").each(function() {
				var hd_chamado = $(this).val();
				chamados.push(hd_chamado);
			});

			var chamados_string = chamados.join(',');

			if (chamados_string != "") {
				$("form[name=filtrar]").append("<input type='hidden' name='chamados_ignorar' value='"+chamados_string+"' />");

				$("form[name=filtrar]").submit();
			} else {
				alert("Nenhum chamado selecionado");
			}

		});

		$("input[name^=retira_excel_]:checked").each(function() {
			$(this).closest("tr").addClass("fundo_tr");
		});

		$("input[name^=retira_excel_]").click(function() {
			$(this).closest("tr").toggleClass("fundo_tr");
		});

		$(document).on('click', '.gravar', function() {
			var hd_chamado  = $(this).val();
			var nota_fiscal = $("#nota_fiscal_"+hd_chamado).val();
			var data_nf     = $("#data_nf_"+hd_chamado).val();

			if ((nota_fiscal != "") && (data_nf != "")) {

				var pago = $("#chamado_foi_pago_"+hd_chamado).val();


					$.ajax({

						url: window.location.href,
						type: "POST",
						data: {
							hd_chamado  : hd_chamado,
							nota_fiscal : nota_fiscal,
							data_nf     : data_nf,
							ajax_nota   : true
						},
						timeout: 8000
					}).fail(function(){
						alert("Não foi possível completar a alteração");
					}).done(function(data) {
						var parsed = JSON.parse(data);

						if (parsed.ok == 'sim') {
							$(".info_nota_"+parsed.hd_chamado).html("<span style='width: 100% ;text-align: center;'>"+parsed.nota_fiscal+"</span>");
							$(".info_data_nf_"+parsed.hd_chamado).html("<span style='text-align: center;width: 100% ;'>"+parsed.data_nf+"</span>");

							$(".btn_"+parsed.hd_chamado).html('<button class="alterar" style="cursor: pointer;" value="'+parsed.hd_chamado+'">Alterar</button>');
							
							hdPago(parsed.hd_chamado);

						} else {
							alert("Erro ao cadastrar, revise os dados informados");
						}

					});
			} else {
				alert("Preencha a nota fiscal e data de emissão");
			}

		});

		$(document).on('click', '.alterar', function() {
			var hd_chamado  = $(this).val();
			var nota_fiscal = $(".info_nota_"+hd_chamado+" > span").html();
			var data_nf     = $(".info_data_nf_"+hd_chamado+" > span").html();

			$(".info_nota_"+hd_chamado).html('<input type="text" style="width: 100px;" id="nota_fiscal_'+hd_chamado+'" value="'+nota_fiscal+'" name="nota_fiscal_'+hd_chamado+'" />');

			$(".info_data_nf_"+hd_chamado).html('<input type="text" style="width: 100px;" id="data_nf_'+hd_chamado+'" value="'+data_nf+'" name="data_nf_'+hd_chamado+'" />');

			$(".btn_"+hd_chamado).html('<button class="gravar" style="cursor: pointer;" value="'+hd_chamado+'">Gravar</button>');
		});
	});
</script>
<div class="container" style="width: 60%;">
      <div class="panel panel-default">
      		<div class="panel-heading">
        		<h3 class="panel-title"><center>Relatório de Horas Faturadas</center></h3>
        	</div>
        	<div class="panel-body">	
				<form name='filtrar' method='POST' ACTION='<?= $PHP_SELF ?>'>
					<div class="row">
						<div class="col-md-3 col-sm-3"></div>	
		              <div class="col-md-6 col-sm-4">
		                <div class="form-group">
		                  <label for="CNPJ">Fabricante:</label>
		                  <div class="input-group">
		          			<?php 
							$sqlfabrica = "SELECT * FROM tbl_fabrica WHERE ativo_fabrica IS TRUE ORDER BY nome";
							$resfabrica = pg_query ($con,$sqlfabrica);
							$n_fabricas = pg_num_rows($res);
							?>
							<select class="form-control" style='width: 180px;' name='fabrica_busca'>
								<option value=''>Todas Fabricas</option>
								<?php
								for ($x = 0 ; $x < pg_num_rows($resfabrica) ; $x++){
									$fabrica   = trim(pg_fetch_result($resfabrica,$x,fabrica));
									$nome      = trim(pg_fetch_result($resfabrica,$x,nome));
									echo "<option value='$fabrica'"; if ($fabrica_busca == $fabrica) echo " SELECTED "; echo ">$nome</option>\n";
								}
							?>
							</select>
		                  </div>
		                </div>
		              </div>
		              <div class="col-md-3 col-sm-3"></div>
		          </div>
		          <div class="row">
					<div class="col-md-3 col-sm-3"></div>	
		              <div class="col-md-4 col-sm-4">
		                <div class="form-group">
		                  <label for="CNPJ"><span style="color: red;">* </span> Data Inicial</label>
		                  <div class="input-group">
		          			<input type='txt' name='data_inicial' class="form-control" rel='data' id='data_inicial' style='width: 180px;' value='<?= $data_inicial ?>'>
		                  </div>
		                </div>
		              </div>
		              <div class="col-md-4 col-sm-4">
		                <div class="form-group">
		                  <label for="CNPJ"><span style="color: red;">* </span> Data Final</label>
		                  <div class="input-group">
		          			<input type='text' name='data_final' class="form-control" rel='data' id='data_final' style='width: 180px;' value='<?= $data_final ?>'>
		                  </div>
		                </div>
		              </div>	
		              <div class="col-md-2 col-sm-2"></div>
		          </div>
		          <div class="row">
					<div class="col-md-3 col-sm-3 col-xs-3 col-lg-3"></div>		
		              <div class="col-md-4 col-sm-4 col-xs-4 col-lg-4">
		                <div class="form-group">
		                  <label for="CNPJ"> Tipo de Venda:</label>
		                  <div class="input-group">
		          			<select class="form-control" id="tipo_venda" name="tipo_venda">
								<option></option>
								<option <?=($_RESULT["tipo_venda"] == strtolower("Comercial")) ? "selected" : ""?> >Comercial</option>
								<option <?=($_RESULT["tipo_venda"] == strtolower("Consultoria")) ? "selected" : ""?> >Consultoria</option>
								<option <?=($_RESULT["tipo_venda"] == strtolower("Implantação de Linha")) ? "selected" : ""?> >Implantação de Linha</option>
								<option <?=($_RESULT["tipo_venda"] == strtolower("Novo Módulo")) ? "selected" : ""?> >Novo Módulo</option>
								<option <?=($_RESULT["tipo_venda"] == strtolower("Treinamento")) ? "selected" : ""?> >Treinamento</option>
							</select>
		                  </div>
		                </div>
		              </div>
		              <div class="col-md-1 col-sm-1 col-xs-1 col-lg-1"></div>
		          </div>
		          <div class="row">
					<div class="col-md-3 col-sm-3"></div>	
		              <div class="col-md-4 col-sm-4">
		                <div class="form-group">
		                  <div class="input-group">
		          			    <?php
								if ($com_horas_faturadas <> '') {
									$selciona_condicao_tem_horas = "checked='checked'";
								}
								?>

								<input type='checkbox' name='com_horas_faturadas' <?= $selciona_condicao_tem_horas ?> id='com_horas_faturadas' value='1'>
								Tem Horas a Faturar
		                  </div>
		                </div>
		              </div>
		              <div class="col-md-3 col-sm-3">
		                <div class="form-group">
		                  		<?php
								if ($resolvidos <> '') {
									$selciona_resolvidos = "checked='checked'";
								}
								?>

								<input type='checkbox' name='resolvidos' <?= $selciona_resolvidos ?> value='1'>
								Resolvidos
		                  </div>
		                </div>
		              <div class="col-md-3 col-sm-3"></div>
		          </div>
		          <div class="row">
					<div class="col-md-3 col-sm-3"></div>	
		              <div class="col-md-4 col-sm-4">
		                <div class="form-group">
		                  <div class="input-group">
								<input type='checkbox' name='chamados_pagos' <?= (isset($_POST["chamados_pagos"]) ? "checked" : "") ?> id='chamados_pagos' value='1'>
								Somente Chamados Pagos
		                  </div>
		                </div>
		              </div>
		          </div>    
		          <br />
		          <div class="row">
					<div class="col-md-3 col-sm-3"></div>	
		              <div class="col-md-3 col-sm-3 col-md-offset-2">
		                <div class="form-group">
		                  <div class="input-group">
								<INPUT TYPE="submit" class="btn btn-default" name="pesquisar" value="Pesquisar">
		                  </div>
		                </div>
		              </div>	
		              <div class="col-md-3 col-sm-3"></div>
		          </div>
				</FORM>
		</div>
	</div>
</div>
<link type="text/css" rel="stylesheet" media="screen" href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src='../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js'></script>
<script src="../admin/plugins/jquery.alphanumeric.js"></script>
<script src='../admin/plugins/jquery.mask.js'></script>
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox_lupa/shadowbox.css" />
<script src="../plugins/shadowbox_lupa/shadowbox.js"></script>
<script>
	$(function () {
		Shadowbox.init();

		$("#cadastro_faturamento").click(function () {
			Shadowbox.open({
				content: "cadastro_faturamento.php",
				player: "iframe",
				width: 800,
				height: 570
			});
		});
	});

	function hdPago(hd_chamado) {
		$.ajax({
		type: "POST",
		url: "<?=$PHP_SELF?>",
		data: {
			"hd_chamado":hd_chamado,
			"pago":"pago"
		},
		success: function(data){
			if(data == 'ok'){
				$('#btn_'+hd_chamado).hide();
				$('#chamado_foi_pago_'+hd_chamado).val("sim");
			}
		} 
		});
	}

		function PagoParcela(hd_chamado, parcela, posicao) {
			$.ajax({
			type: "POST",
			url: "<?=$PHP_SELF?>",
			data: {
				"hd_chamado":hd_chamado,
				"parcela":"parcela",
				"numParcela": parcela
			},
			success: function(data){
				if(data == 'ok'){
					$('#pagamento_parcela_'+hd_chamado+'_'+posicao).hide();
				}
			} 
		});
	}
</script>
<div style="width: 400px; text-align: center; margin: 0 auto;">
	<hr />
	<button type="button" class="btn btn-success" id="cadastro_faturamento" >Cadastro de faturamento</button>
	<br /><br />
</div>

<?php
$imagem = "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8' title='Aguardando a resposta do Solicitante do chamado'>";

$btn_acao = $_POST['pesquisar'];

if (isset($_POST['pesquisar']) || isset($_POST['chamados_ignorar'])) {
	

	if (strlen($msg_erro) == 0) {
		$arquivo_nome     = "relatorio_horas_faturadas-".$login_fabrica.".".$login_admin.".xls";
		$caminho_arquivo  = "../admin/xls/".$arquivo_nome;

		fopen($caminho_arquivo, "w+");
		$fp = fopen($caminho_arquivo, "a");

		//$aux_mes             = $_POST['mes'];
		//$aux_ano             = $_POST['ano'];
		$data_inicial        = $_POST["data_inicial"];
		$data_final          = $_POST["data_final"];
		$fabrica_busca       = $_POST['fabrica_busca'];
		$com_horas_faturadas = $_POST['com_horas_faturadas'];
		$resolvidos          = $_POST['resolvidos'];
		$chamados_pagos		 = $_POST['chamados_pagos'];


		$data_inicialx = formata_data($data_inicial);
		$data_finalx = formata_data($data_final);

		if ($chamados_pagos != "") {
			$condicao_pagos = " AND tbl_hd_chamado_extra.data_nf is not null ";
		} else {
			$condicao_pagos = " AND tbl_hd_chamado_extra.data_nf is null";
		}

		if ($resolvidos <> '') {
			$condicao_resolvidos = "AND tbl_hd_chamado.status='Resolvido' ";

			$hd_resolvidos = true; 

		}

		if ($aux_mes < '10') {
			$aux_mes = '0'.$aux_mes;
		}

		$condicao_fabrica = "";

		if (strlen($fabrica_busca) > 0) {
			$condicao_fabrica = " AND tbl_hd_franquia.fabrica ='".$fabrica_busca."'";
		}

		if (isset($_POST["chamados_ignorar"])) {
			$chamados_ignorar = $_POST["chamados_ignorar"];

			$condicao_ignorar_hd = " AND tbl_hd_chamado.hd_chamado NOT IN ($chamados_ignorar)";
		}

//$aux_mes = "01";
//$aux_ano = "2017";
		if ($com_horas_faturadas <> '') {
			$condicao_tem_horas = "tbl_hd_franquia.hora_faturada > '0' ";
			$sql_faturadas = "SELECT  distinct tbl_fabrica.fabrica ,
												tbl_fabrica.nome 
								from tbl_hd_franquia
								JOIN tbl_fabrica
								on tbl_hd_franquia.fabrica = tbl_fabrica.fabrica
								left join tbl_controle_implantacao on tbl_fabrica.fabrica = tbl_controle_implantacao.fabrica
								where 							     
							     periodo_inicio between '".substr($data_inicialx, 0, -2)."01 00:00:00"."' and '$data_finalx 23:59:59'

								AND ($condicao_tem_horas OR (
									SELECT COUNT(0) FROM tbl_controle_implantacao
									WHERE tbl_controle_implantacao.fabrica = tbl_fabrica.fabrica
									AND data_implantacao between '$data_inicialx 00:00:00' and '$data_finalx 23:59:59'
								) > 0 or (select count(1) from tbl_hd_chamado
									where tbl_hd_chamado.fabrica = tbl_hd_franquia.fabrica
									and hora_faturada > 0 
									and data_aprovacao notnull
									AND data_resolvido between '$data_inicialx 00:00:00' and '$data_finalx 23:59:59'
									
									) > 0 
								)
								$condicao_fabrica
								group by tbl_fabrica.fabrica , tbl_fabrica.nome 
					ORDER BY nome ";
		}
		$sql_fabrica = " SELECT tbl_fabrica.fabrica ,
								tbl_fabrica.nome 
								
							from tbl_hd_franquia
							JOIN tbl_fabrica
							on tbl_hd_franquia.fabrica = tbl_fabrica.fabrica
							where  periodo_inicio between '".substr($data_inicialx, 0, -2)."01 00:00:00"."' and '$data_finalx 23:59:59' 
							$condicao_tem_horas
							$condicao_fabrica
							group by tbl_fabrica.fabrica, tbl_fabrica.nome 
							order by tbl_fabrica.nome ";

		if ($com_horas_faturadas <> '') {
			$sql_fabrica = $sql_faturadas;
		}

		$res_fabrica = pg_exec($con, $sql_fabrica);

		if (pg_num_rows($res_fabrica) > 0) {?>

			<div class="container">				

						<?php

						fputs ($fp, "<table width='800px' border='0'><tr><td>");

						for ($d = 0; $d < pg_num_rows($res_fabrica); $d++) {
							$total_desconto = 0;
							$busc_fabrica        = "";
							$nome_fabrica        = "";
							$data_inicio_fabrica = "";
							$data_fim_fabrica    = "";						

							$busc_fabrica        = pg_result($res_fabrica, $d, 'fabrica');
							$nome_fabrica        = pg_result($res_fabrica, $d, 'nome');
							//$data_inicio_fabrica = pg_result($res_fabrica, $d, 'periodo_inicio');
							//$data_fim_fabrica    = pg_result($res_fabrica, $d, 'periodo_fim');

							$data_inicio_fabrica 	= "$data_inicialx 00:00:00";
							$data_fim_fabrica 		= "$data_finalx 23:59:59";

							$data_inicio_parcelamento = substr($data_inicialx, 0, -2)."01 00:00:00";


							$nome_fabrica        = strtoupper($nome_fabrica);
							
							$codicao_data_inic_fim = "";
							$codicao_data_inic_fim_2 = "";
							$codicao_data_inic_fim_1 = "";

							if(empty($data_fim_fabrica)){
								//$data_fim_fabrica = date("Y-m-d"). " 23:59:59";
								$data_fim_fabrica = "$data_finalx 23:59:59";
							}

							//or (tbl_hd_chamado.data_resolvido BETWEEN '$data_inicio_fabrica' AND '$data_fim_fabrica' and data_aprovacao notnull)

							if (strlen($data_fim_fabrica) > 0) {
								$codicao_data_inic_fim_2	= " and (tbl_hd_chamado.data_aprovacao BETWEEN '$data_inicio_fabrica' AND '$data_fim_fabrica' or (tbl_hd_chamado.data_resolvido BETWEEN '$data_inicio_fabrica' AND '$data_fim_fabrica' and data_aprovacao notnull) ) ";
								if($hd_resolvidos)  $codicao_data_inic_fim_2 = " and  (tbl_hd_chamado.data_resolvido BETWEEN '$data_inicio_fabrica' AND '$data_fim_fabrica' and data_aprovacao notnull)  ";
							} else {
								$codicao_data_inic_fim_1	= " and (tbl_hd_chamado.data_aprovacao > '$data_inicio_fabrica' or (tbl_hd_chamado.data_resolvido > '$data_inicio_fabrica'  and data_aprovacao notnull) ) ";
							}

							$sql = "select tbl_hd_chamado.hd_chamado,
											tbl_hd_chamado.fabrica,
											tbl_hd_chamado.hora_faturada,
											tbl_fabrica.nome ,
											tbl_hd_chamado.fabrica,
											tbl_hd_chamado.status,
											tbl_fabrica.fabrica ,
											tbl_hd_chamado_extra.nota_fiscal,
											tbl_hd_chamado_extra.data_nf,
											tbl_hd_chamado.data_pagamento,
											TO_CHAR(tbl_hd_chamado.data_aprovacao,'DD/MM/YYYY') AS data,
											TO_CHAR(tbl_hd_chamado.data_aprovacao,'DD/MM/YYYY') AS data_aprovacao,
											TO_CHAR(tbl_hd_chamado.data_resolvido,'DD/MM/YYYY') AS data_resolvido,
											tbl_hd_chamado.hora_desenvolvimento,
											tbl_hd_chamado.hora_faturada,
											tbl_hd_chamado.valor_desconto AS desconto
								 from tbl_hd_chamado
								 join tbl_fabrica
								 on tbl_hd_chamado.fabrica = tbl_fabrica.fabrica 
								 $codicao_data_inic_fim_1
								 left join tbl_hd_chamado_extra on tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
								 left join tbl_controle_implantacao on tbl_controle_implantacao.hd_chamado = tbl_hd_chamado.hd_chamado and tbl_controle_implantacao.fabrica = $busc_fabrica 
								 where tbl_hd_chamado.fabrica = $busc_fabrica
								 and tbl_hd_chamado.fabrica_responsavel = 10
								 and tbl_hd_chamado.status not in ('Cancelado')
								 $condicao_resolvidos
								 $condicao_pagos
								 $condicao_ignorar_hd
								 and tbl_controle_implantacao.controle_implantacao is null
								 and tbl_hd_chamado.hora_desenvolvimento is NOT NULL
								 and tbl_hd_chamado.hora_faturada notnull
								 $codicao_data_inic_fim_2 ";
							$res = pg_exec($con,$sql);

							$sqlCI = "SELECT hd_chamado AS chamado, tipo, valor_implantacao AS valor, numero_parcela, total_parcela
									FROM tbl_controle_implantacao
									WHERE fabrica = {$busc_fabrica} and
									data_implantacao between '$data_inicio_parcelamento' and '$data_fim_fabrica' AND data_finalizacao is null AND finalizada is not true ";

							$resCI = pg_query($con, $sqlCI);

							$rowsCI = pg_num_rows($resCI);
							if(pg_num_rows($res) == 0 and pg_numrows($resCI) == 0 ) continue;
					?>
					<?php 
						if($d > 0)
							
								$total_horas_cobradas = "";
								$total_horas       = "";
								$aux_hd_chamado    = "";
								$aux_fabrica       = "";
								$aux_hora_faturada = "";
								$aux_nome          = "";
								$cor               = "";
								$aux_nome          = "";
								$aux_hd_chamado    = "";
								$aux_hora_faturada = "";
								$horas_sup 		   = "";
								$horas_dev 		   = "";
								$h_suporte   	   = array();
								$h_dev   		   = array();

								$qtde_hd_fabrica = pg_num_rows($res);
						if ($qtde_hd_fabrica > 0) { ?>

							<table class="table table-bordered table-striped">
								<tr class="titulo_tabela" style="background-color: #3d3e71 !important;">
									<th colspan="100%" class='tac'><?= $nome_fabrica. " ".  mostra_data( substr($data_inicio_fabrica, 0, 10)). " - ".mostra_data(substr($data_fim_fabrica, 0, 10)) ?></th>
								</tr>
								<tr>
									<th class='tac'>Remover</th>
									<th class='tac'>Chamados</th>
									<th class='tac'>Status</th>
									<th class='tac'>Horas Cobradas</th>
									<th class='tac'>Horas Faturadas</th>
									<th class='tac'>Horas Sup.</th>
									<th class='tac'>Horas Desenv.</th>
									<th class='tac'>Horas Trab.</th>
									<th class="tac">Desconto</th>
									<th class="tac">Nota Fiscal</th>
									<th class="tac">Data Emissão</th>
									<th class='tac'>Ação</th>

								</tr><?php
								fputs ($fp,"<tr><th> &nbsp; </th></tr>");
						
								fputs ($fp,"<tr><th><center><b>$nome_fabrica</b></center></th></tr><tr><td>");

								fputs ($fp,'<table class="tablesorter" width="400px" border="1">
												<tr>
													<th align="center">Chamado Aprovado</th>
													<th align="center">Horas Cobradas</th>
													<th align="center">Horas Faturadas</th>
													<th align="center">Horas Sup.</th>
													<th align="center">Horas Desenv.</th>
													<th align="center">Horas Trab.</th>
													<th align="center">Desconto</th>
													<th align="center">Nota Fiscal</th>
													<th align="center">Data Emissão</th>
												</tr>'
								);

								for ($i = 0; $i < pg_num_rows($res); $i++) {

									$aux_hd_chamado    = pg_result($res, $i, 'hd_chamado');
									$aux_fabrica       = pg_result($res, $i, 'fabrica');
									$status	           = pg_result($res, $i, 'status');
									$aux_hora_faturada = pg_result($res, $i, 'hora_desenvolvimento');
									$hora_desenvolvimento = pg_result($res, $i, 'hora_desenvolvimento');
									$aux_hora_fat_real = pg_result($res, $i, 'hora_faturada');
									$aux_nome          = pg_result($res, $i, 'nome');
									$aux_desconto      = pg_fetch_result($res, $i, "desconto");
									$nota_fiscal       = pg_fetch_result($res, $i, "nota_fiscal");
									$data_nf           = pg_fetch_result($res, $i, "data_nf");
									$data_pagamento    = pg_fetch_result($res, $i, "data_pagamento");

									$aux_hora_faturada = (strlen($aux_hora_fat_real) > 0 and $aux_hora_fat_real <> $aux_hora_faturada) ? $aux_hora_fat_real : $aux_hora_faturada;

									//$total_horas = $total_horas + $aux_hora_faturada;
									$total_horas_cobradas = $total_horas_cobradas + $hora_desenvolvimento;
									$total_horas = $total_horas + $aux_hora_fat_real;

									$horas_diferenca += ($hora_desenvolvimento - $aux_hora_faturada);

									$total_horas_desenvolvimento += $hora_desenvolvimento;

									$total_desconto += $aux_desconto;
									$cor   = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
								
									$sql_sup ="SELECT TO_CHAR((EXTRACT(epoch FROM SUM(termino-data_inicio))/60 || ' minutes')::INTERVAL, 'HH24:MI')
												 FROM tbl_hd_chamado_atendente
											     JOIN tbl_admin using(admin)
												 JOIN tbl_hd_chamado_item USING(hd_chamado,admin)
												WHERE tbl_hd_chamado_atendente.hd_chamado = $aux_hd_chamado
												  AND data = data_inicio
												  AND data_inicio NOTNULL
												  AND termino NOTNULL
											      AND grupo_admin=6";
									$res_sup = pg_query($con, $sql_sup);
									if (pg_num_rows($res_sup) > 0) {
										$horas_sup = pg_fetch_result($res_sup, 0, 0);
									}

									if (strlen($horas_sup) == 0) {
										$horas_sup = "00:00";
									} else {
										$xhoras_sup = explode(":",$horas_sup);
										$horas_sup = $xhoras_sup[0].":".$xhoras_sup[1];
										$h_suporte[] = $horas_sup;
									}


									$sql_dev ="SELECT TO_CHAR((EXTRACT(epoch FROM SUM(termino-data_inicio))/60 || ' minutes')::INTERVAL, 'HH24:MI')
											  FROM tbl_hd_chamado_atendente
											  JOIN tbl_admin using(admin)
											  JOIN tbl_hd_chamado_item USING(hd_chamado,admin)
											 WHERE tbl_hd_chamado_atendente.hd_chamado = $aux_hd_chamado
											   AND data = data_inicio
											   AND data_inicio NOTNULL
										 	   AND termino NOTNULL
											   AND grupo_admin=4 ";
									$res_dev = pg_query($con, $sql_dev);
									if (pg_num_rows($res_dev) > 0) {
										$horas_dev= pg_fetch_result($res_dev, 0, 0);
									}

									if (strlen($horas_dev) == 0) {
										$horas_dev = "00:00";
									} else {
										$xhoras_dev = explode(":",$horas_dev);
										$horas_dev = $xhoras_dev[0].":".$xhoras_dev[1];
										
									}
									$h_dev[] = $horas_dev;

									$horas_tra = somaHoras(array($horas_sup,$horas_dev));
								?>
									<tr>
										<td class="tac">
											<span>
												<input type="checkbox" name="retira_excel_<?= $aux_hd_chamado ?>" id="retira_excel_<?= $aux_hd_chamado ?>" value="<?= $aux_hd_chamado ?>" />
											</span>
										</td>
										<td class="tac">
											<a href="adm_chamado_detalhe.php?hd_chamado=<?php echo $aux_hd_chamado;?>" style="position: relative;float: center;text-decoration: none;vertical-align: middle !important;" target="_blank">
												<?php echo $aux_hd_chamado;?>
											</a>
										</td>
										<td class='tac'><?php echo $status;?></td>
										<td class='tac'><?php echo $hora_desenvolvimento;?></td>
										<td class='tac'><?php echo (strlen($aux_hora_fat_real) > 0)? $aux_hora_fat_real : "0"; ?></td>
										<td class='tac'><?php echo $horas_sup;?></td>
										<td class='tac'><?php echo $horas_dev;?></td>
										<td class='tac'><?php echo $horas_tra;?></td>
										<td class="tac">R$ <?php echo number_format($aux_desconto, 2, ",", ".");?></td>
										<?php if (!empty($nota_fiscal)) { ?>
												<td class="info_nota_<?= $aux_hd_chamado ?> tac"><span><?= $nota_fiscal ?></span></td>
										<?php } else { ?>
												<td class="info_nota_<?= $aux_hd_chamado ?> tac">
													<input class="form-control" type="text" style="width: 100px;" id="nota_fiscal_<?= $aux_hd_chamado ?>" name="nota_fiscal_<?= $aux_hd_chamado ?>" />
												</td>
										<?php } 
											  if (!empty($data_nf)) { ?>
												<td class="info_data_nf_<?= $aux_hd_chamado ?> tac" align="center"><span><?= mostra_data($data_nf) ?></span></td>
										<?php } else { ?>
												<td class="info_data_nf_<?= $aux_hd_chamado ?> tac">
													<input class="form-control" type="text" style="width: 100px;" id="data_nf_<?= $aux_hd_chamado ?>" name="data_nf_<?= $aux_hd_chamado ?>" />
												</td>
										<?php } ?>
										<td class='btn_<?= $aux_hd_chamado ?> tac'>
										<?php
											  if(empty($data_nf) || empty($nota_fiscal) && $data_pagamento == "") { 
										?>
												<button class="gravar btn btn-default" style="cursor: pointer;" value="<?=$aux_hd_chamado?>">
													Gravar
												</button>
										<?php } else { ?>
												<button class="alterar btn btn-default" style="cursor: pointer;" value="<?=$aux_hd_chamado?>">
													Alterar
												</button>
										<?php } ?>
										</td>
										<input type="hidden" id="chamado_foi_pago_<?= $aux_hd_chamado ?>" value="<?= ($data_pagamento == '') ? "nao" : "sim" ?>" />
									</tr><?php

									$aux_hora_fat_real = (strlen($aux_hora_fat_real) > 0)? $aux_hora_fat_real : "0";

									fputs ($fp,'<tr>
													<td align="center">'.$aux_hd_chamado.'</td>
													<td align="center">'.$hora_desenvolvimento.'</td>
													<td align="center">'.$aux_hora_fat_real.'</td>
													<td align="center">'.$horas_sup.'</td>
													<td align="center">'.$horas_dev.'</td>
													<td align="center">'.$horas_tra.'</td>
													<td align="center">R$ '.number_format($aux_desconto, 2, ",", ".").'</td>
													<td align="center">'.$nota_fiscal.'</td>
													<td align="center">'.mostra_data($data_nf).'</td>
												</tr>'
											);

								}

									$totalDEV = somaHoras($h_dev);
									$totalSUP = somaHoras($h_suporte);

									$totalTRA = somaHoras(array($totalDEV,$totalSUP));

									$hrtrab = verificaHoraTrabalhada($totalTRA, $total_horas);//verifica se horas trabalhadas e maior que a cobrada
									$corStatus = ($hrtrab) ? "#ff0000": "#000000";
								?>
								<tr>
									<td>&nbsp;</td>	
									<td>&nbsp;</td>
									<td class="tac">
										<span style="font-size:14px;"><b>Total&nbsp;</b></span>
									</td>
									<td class="tac"><span style="font-size:14px;"><b><?=$total_horas_cobradas?></b></span></td>

									<td class='tac'><span style="font-size:14px;"><b><?php echo $total_horas;?></b></span></td>
									<td class='tac'>
										 <span style="font-size:14px;"><b><?php echo somaHoras($h_suporte);?></b></span>
									</td>
									<td class='tac'>
										<span style="font-size:14px;"><b><?php echo somaHoras($h_dev);?></b></span>
									</td>
									<td class='tac'><span style="font-size:14px;color:<?php echo $corStatus;?>"><b>
									<?php echo $totalTRA;?></b></span></td>
									<td class="tac"><span style="font-size:14px;"><b>R$ <?php echo number_format($total_desconto, 2, ",", ".");?></b></span></td>
									<td class="tac"></td>
									<td class="tac"></td>
									<td class="tac"></td>
								</tr>
							</table><?php
							$totalFinalDEV[] = $totalDEV;
							$totalFinalSUP[] = $totalSUP;

							fputs ($fp,'<tr>
											<td align="center"><span style="font-size:14px;" align="center"><b>Total&nbsp;</b></span>
											</td>
											<td align="center"><span style="font-size:14px;"><b>'.$total_horas_cobradas.'</b></span>
											</td>
											<td align="center"><span style="font-size:14px;"><b>'.$total_horas.'</b></span>
											</td>
											<td align="center"><span style="font-size:14px;"><b>'.$totalSUP.'</b></span>
											</td>
											<td align="center"><span style="font-size:14px;"><b>'.$totalDEV.'</b></span>
											</td>
											<td align="center"><span style="font-size:14px;"><b>'.$totalTRA.'</b></span>
											</td>
											<td align="center"><span style="font-size:14px;"><b>R$ '.number_format($total_desconto, 2, ",", ".").'</b></span>
											</td>
										</tr>
									</table>');

						}
						 $soma_total_horas			= "";
						 $aux_hora_utilizada		= "";
						 $total_pagar				= "";
						 $aux_valor_hora_franqueada = "";
						 $aux_hora_franqueada		= "";
						 $aux_hora_faturada			= "";
						 $aux_valor_hora_franqueada	= "";
						 $aux_saldo_hora			= "";
						 $aux_hora_utilizada		= "";
						 $soma_horas_cobrar = 0;


						$sql = "SELECT saldo_hora		  ,
									mes                   ,
									ano                   ,
									hora_franqueada       ,
									hora_faturada         ,
									hora_utilizada        ,
									valor_hora_franqueada ,
									to_char(periodo_inicio,'DD/MM/YYYY') as periodo_inicio,
									to_char(periodo_fim,'DD/MM/YYYY') as periodo_fim,
									nome,
									tbl_hd_franquia.fabrica
									hora_maxima
								from tbl_hd_franquia
									JOIN tbl_fabrica USING(fabrica)
									where periodo_inicio between '".substr($data_inicialx, 0, -2)."01 00:00:00"."' and '$data_finalx 23:59:59'

									and tbl_hd_franquia.fabrica = $busc_fabrica

								order by fabrica
								LIMIT 1 ";

						$res = pg_exec($con, $sql);

	
						 if (pg_num_rows($res) > 0) {

							 $aux_hora_franqueada		= pg_result($res, 0, 'hora_franqueada');
							 $aux_hora_faturada			= pg_result($res, 0, 'hora_faturada');
							 $aux_valor_hora_franqueada	= pg_result($res, 0, 'valor_hora_franqueada');
							 $aux_saldo_hora			= pg_result($res, 0, 'saldo_hora');
							 $aux_hora_utilizada		= pg_result($res, 0, 'hora_utilizada');

							 $soma_total_horas = $aux_saldo_hora + $aux_hora_franqueada;

							 //adicinado no hd-2585522
							 // $aux_hora_faturada = $total_horas;
							 // $aux_hora_utilizada = $aux_hora_faturada;

							 if ($soma_total_horas <= 0) {
								$soma_total_horas = "0";
							 }

							 if ($aux_hora_utilizada <= 0) {
								$aux_hora_utilizada = "0";
							 }

							 $soma_horas_cobrar = $aux_hora_faturada;

							 if ($soma_horas_cobrar > 0) {
								$total_pagar = $soma_horas_cobrar * $aux_valor_hora_franqueada;
							 } else {
								$total_pagar = "0";
							 }

							 $aux_valor_hora_franqueada = money_format('%.2n', $aux_valor_hora_franqueada);
							 $aux_valor_hora_franqueada = str_replace('.',',',$aux_valor_hora_franqueada);
							 $cor   = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

							 $aux_hora_utilizada = $aux_hora_utilizada + $aux_hora_faturada;

							 if ($soma_total_horas >= $aux_hora_utilizada) {
								$aux_hora_utilizada = "0";
							 }
							 if ($soma_total_horas >= $total_horas) {
								#$total_horas = "0";
							 }
							 $totalHorasAFaturar += $aux_hora_utilizada;
							 $total_horas_faturar = $total_horas_faturar + $aux_hora_utilizada;
							 $total_horas_fabrica = $total_horas_fabrica + $soma_total_horas;


							$CI_valor = 0;
								$total_CI = 0 ; 
							if ($rowsCI > 0) {

								if($qtde_hd_fabrica == 0){
									fputs ($fp,'<table><tr><td></td></tr></table>');
								}
								fputs ($fp,'<table  border="1">
												<tr class="titulo_tabela" style="background-color: darkred !important;">
													<th colspan="4" class="tac">Faturamentos '."$nome_fabrica".' </th>
												</tr>
												<tr>
													<th align="center">Chamado</th>
													<th align="center">Tipo de Venda</th>
													<th align="center">Parcelas</th>
													<th align="center">Valor</th>
												</tr>');
							?>
								<table class="table table-striped">
									<tr class="titulo_tabela" style="background-color: darkred !important;">
										<th colspan="100%" class="tac">Faturamentos <?= $nome_fabrica ?></th>
									</tr>
									<tr>
										<th class="tac">Chamado</th>
										<th class="tac">Tipo de Venda</th>
										<th class="tac">Parcela</th>
										<th class="tac">Valor</th>
									</tr>

									<?php
									$total_CI = 0 ;
									$Total_CI_valor  = 0;
									for ($j = 0; $j < $rowsCI; $j++) {
										$CI_chamado = pg_fetch_result($resCI, $j, "chamado");
										$CI_tipo_venda = utf8_decode(pg_fetch_result($resCI, $j, "tipo"));
										$CI_valor = pg_fetch_result($resCI, $j, "valor");
										$CI_numero_parcela = pg_fetch_result($resCI, $j, 'numero_parcela');
										$CI_total_parcela =  pg_fetch_result($resCI, $j, 'total_parcela');

										$Total_CI_valor += $CI_valor; 

										$cor   = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

										?>
										<tr>
											<td class="tac">
												<a href="adm_chamado_detalhe.php?hd_chamado=<?=$CI_chamado?>" style="text-decoration: none;" target="_blank" ><?=$CI_chamado?>
											</td>
											<td class="tac"><?=$CI_tipo_venda?></td>
											<td class="tac"><?echo "$CI_numero_parcela/$CI_total_parcela"?></td>
											<td class="tac">R$ <?=number_format($CI_valor, 2, ",", ".")?></td>
											<td class="tac">
												<button type='button' id="pagamento_parcela_<?= $CI_chamado ?>_<?= $j ?>" onclick='javascript:PagoParcela(<?=$CI_chamado?>, <?=$CI_numero_parcela?>, <?= $j ?>)'>Pago</button>
											</td>
										</tr>

									<?php
										fputs ($fp,'<tr>
														<td>'.$CI_chamado.'</td>
														<td>'.$CI_tipo_venda.'</td>
														<td> '.$CI_numero_parcela.'/'.$CI_total_parcela.' </td>
														<td>R$ '.number_format($CI_valor, 2, ",", ".").'</td>
													</tr>');


									}
									?>
									<tr>
												<td colspan='3' align='right'><b>Total</b></td>
												<td><b>R$ <?= number_format($Total_CI_valor, 2, ",", ".") ?></b>
												</td>
												<td></td>
									</tr>
									<?php


									fputs ($fp,'</table>');

									?>
								</table>

							<?php
							}
							$soma_total_desconto += $total_desconto;

							$total_pagar = $total_horas*$aux_valor_hora_franqueada;
							$aux_hora_utilizada = $total_horas;
						    $totalHorasAFaturarRes += $aux_hora_utilizada;

							if ($total_pagar > 0 && $total_desconto > 0) {
								$total_pagar = $total_pagar - $total_desconto;
							}
		
							if($CI_valor > 0) {
								$total_pagar += $Total_CI_valor;
							}
							$soma_total_apagar += $total_pagar;

							$total_desconto = money_format('%.2n', $total_desconto);
							$total_desconto = str_replace('.',',',$total_desconto);
							

							$total_pagar = money_format('%.2n', $total_pagar);
							$total_pagar = str_replace('.',',',$total_pagar);
							//$total_pagar = number_format($total_pagar,2,',','.');

							//if ($rowsCI > 0) {
								$aux_hora_faturada = (strlen($aux_hora_faturada)>0) ? $aux_hora_faturada : "0";

								$totalafaturar += $aux_hora_faturada; 
							?>

								<table class="table table-striped">
									<tr>
										<th class='tac'>Franquia do Mês</th>
										<th class='tac'>Custo Hora</th>
										<th class='tac'>Horas a Faturar</th>
										<th class='tac'>Valor</th>
										<th class='tac'>Desconto</th>
									</tr>
									<tr>
										<td class='tac'><?php echo $soma_total_horas;?></td>
										<td class='tac'>R$ <?php echo $aux_valor_hora_franqueada;?></td>
										<td class='tac'><?php echo $aux_hora_utilizada;?></td>
										<td class='tac' style="font-size:14px;color:#6D9C59;"><b>R$ <?php echo $total_pagar;?></b></td>
										<td class='tac' style="font-size:14px;color:#9C5E59;"><b>R$ <?php echo $total_desconto;?></b></td>
									</tr>

									<!-- <tr>
										<td class='tac'><b>Custo Hora&nbsp;</b></td>
										<td class='tac'><span><b>R$ <?php echo $aux_valor_hora_franqueada;?></b></span></td>
										<td colspan="2">&nbsp;</td>
									</tr> -->

									<?php

									$sql_chat = "SELECT COALESCE(MAX(qtde),0) as total
												   FROM tbl_fabrica_chat
												  WHERE data between '$data_inicio_fabrica' and '$data_fim_fabrica' 
												  AND fabrica = $aux_fabrica;";

									$res_chat = @pg_query($con, $sql_chat);

									if (@pg_num_rows($res_chat)) {

										$qtde_chat = pg_result($res_chat, 0, 'total');

										if ($qtde_chat) {

											$total_chat = ($valor_hora_chat * $qtde_chat);
											$soma_total_apagar += $total_chat;

											echo '<tr>';
												echo '<td><span style="float:right;align-text:right;"><b>Custo Suporte via Chat&nbsp;</b></span></td>';
												echo '<td><span><b>R$ '.$valor_hora_chat.'</b></span></td>';
												echo '<td style="font-size:14px;font-weight:bold;color:#6D9C59;background:#F1F4FA;">';
													echo 'R$ '.$total_chat;
												echo '</td>';
											echo '</tr>';

										}

									}?>

								</table>

							<?php
							//}
							// fputs ($fp,'<table class="tablesorter" width="400px" border="1"><tr><th>Franquia do Mês</th><th>Horas a Faturar</th><th>Valor</th><th>Desconto</th></tr><tr><td>'.$soma_total_horas.'</td><td>'.$aux_hora_utilizada.'</td><td><b>R$ '. $total_pagar.'</b></td><td><b>R$ '. $total_desconto.'</b></td></tr><tr><td><span style="float:right;align-text:right;font-size:14px;"><b>Custo Hora&nbsp;</b></span></td><td><span style="font-size:14px;"><b>R$ '. $aux_valor_hora_franqueada.'</b></span></td><td colspan=\"2\">&nbsp;</td></tr></table><table><tr><td>&nbsp;</td></tr></table>');
							

							fputs ($fp,'<table  border="1">
											<tr>
												<th align="center">Franquia do Mês</th>
												<th align="center">Custo Hora</th>
												<th align="center">Horas a Faturar</th>
												<th align="center">Valor</th>
												<th align="center">Desconto</th>
											</tr>
											<tr>
												<td align="center">'.$soma_total_horas.'</td>
												<td align="center">'.$aux_valor_hora_franqueada.'</td>
												<td align="center">'.$aux_hora_utilizada.'</td>
												<td align="center"><b>R$ '. $total_pagar.'</b></td>
												<td align="center"><b>R$ '. $total_desconto.'</b></td>
											</tr>
										</table>');

						}

					}
					?>

						</table>
					<?php

					$soma_total_apagar = money_format('%.2n', $soma_total_apagar);
					//$soma_total_apagar = str_replace('.',',',$soma_total_apagar);
					$soma_total_apagar = number_format($soma_total_apagar,2,',','.');

					$soma_total_desconto = money_format('%.2n', $soma_total_desconto);
					//$soma_total_desconto = str_replace('.',',',$soma_total_desconto);
					$soma_total_desconto = number_format($soma_total_desconto,2,',','.');
					?>

					<table class="table table-bordered">
						<tr class="titulo_tabela" style="background-color: darkcyan !important;">
							<th class='tac'><span style="color:white;font-size:12px;"><b>Total Franquia do Mês&nbsp;</b></span></th>
							<th class='tac'><span style="color:white;font-size:12px;"><b>Total Horas Desenv.&nbsp;</b></span></th>
							<th class='tac'><span style="color:white;font-size:12px;"><b>Total Horas Sup.&nbsp;</b></span></th>
							<th class='tac'><span style="color:white;font-size:12px;"><b>Total Horas Trab.&nbsp;</b></span></th>
							<th class='tac'><span style="color:white;font-size:12px;"><b>Total a Faturar&nbsp;</b></span></th>
							<th class='tac'><span style="color:white;font-size:12px;"><b>Total do Valor&nbsp;</b></span></th>
							<th class='tac'><span style="color:white;font-size:12px;"><b>Total do Desconto&nbsp;</b></span></th>
						</tr><?php

						fputs ($fp,'<table>
										<tr>
											<td colspan="9"></td>
										</tr>
									</table>');						

						fputs ($fp,'<table class="tablesorter" width="400px" border="1">

										<tr>
											<th><span><b>Total Franquia do Mês&nbsp;</b></span></th>
											<th><span><b>Total Horas Desenv.&nbsp;</b></span></th>
											<th><span><b>Total Horas Sup.&nbsp;</b></span></th>
											<th><span><b>Total Horas Trab.&nbsp;</b></span></th>
											<th><span><b>Total a Faturar&nbsp;</b></span></th>
											<th><span><b>Total do Valor&nbsp;</b></span></th>
											<th align="center"><span><b>Total do Desconto&nbsp;</b></span></th>
										</tr>'); 
						$TOTALDEV = somaHoras($totalFinalDEV);
						$TOTALSUP = somaHoras($totalFinalSUP);
						$TOTALTRAB = somaHoras(array($TOTALDEV,$TOTALSUP));
						//$TOTALAFATURAR = (!empty($resolvidos)) ? $totalHorasAFaturarRes : $totalHorasAFaturar;
						?>

						<tr>
							<td class='tac' style="font-size:14px;"><b><?php echo $total_horas_fabrica;?></b></td>
							<td class='tac' style="font-size:14px;color:#6D9C59;"><b><?php echo $TOTALDEV?></b></td>
							<td class='tac' style="font-size:14px;color:#6D9C59;"><b><?php echo $TOTALSUP?></b></td>
							<td class='tac' style="font-size:14px;color:#6D9C59;"><b><?php echo $TOTALTRAB;?></b></td>
							<td class='tac' style="font-size:14px;"><b><?php echo $totalafaturar;//$total_horas_faturar;?></b></td>
							<td class='tac' style="font-size:14px;color:#6D9C59;"><b>R$ <?php echo $soma_total_apagar;?></b></td>
							<td class='tac' style="font-size:14px;color:#AE5E59;"><b>R$ <?php echo $soma_total_desconto;?></b></td>
						</tr>
					</table><?php

					fputs ($fp,'<tr>
									<td align="center"><b>'.$total_horas_fabrica.'</b></td>
									<td align="center"><b>'.$TOTALDEV.'</b></td>
									<td align="center"><b>'.$TOTALSUP.'</b></td>
									<td align="center"><b>'.$TOTALTRAB.'</b></td>
									<td align="center"><b>'.$totalafaturar.'</b></td>
									<td align="center"><b>R$ '.$soma_total_apagar.'</b></td>
									<td align="center"><b>R$ '.$soma_total_desconto.'</b></td>
								</tr>
							</table>'); ?>

			<?php fputs ($fp,"</td></tr></table>"); ?>
			<center>
				<button class="btn btn-danger" id="remover_chamados"> Remover Selecionados </button>
				<br />
			</center>
			<?php
			if (file_exists($caminho_arquivo)) {

				echo "<br>";
				echo "<table width='800px' border='0' cellspacing='2' cellpadding='2' align='center' border='1'>";
					echo "<tr>";
						echo "<td align='center'><center><a href='$caminho_arquivo'><span><img src='../admin/imagens/excel.png' width='30px' /></span>
						<span class='btn_excel' class='txt' style='font-size: 11pt;'>Gerar Arquivo Excel</span></a></center></td>";
					echo "</tr>";
				echo "</table>";

			}

		} else {

			echo '<center><span style="font-family: arial;color: #666;">" Nenhum resultado encontrado para essa pesquisa ... "</span></center>';

		}

	}

}

include "rodape.php";?>

</body>
</html>