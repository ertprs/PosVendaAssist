<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

if($_POST["pesquisa_chamado"]){

	$hd_chamado = (int)$_POST["hd_chamado"];

	$sql = "SELECT  * FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$num_fabrica = pg_fetch_result($res, 0, 'fabrica');	

		$dados = array("tipo"=>"Chamado", "fabrica"=> $num_fabrica);

		echo json_encode($dados);
		exit;
	}
}

if ($_POST["verifica_chamado"] == "true") {
	$chamado = $_POST["chamado"];
	$fabrica = $_POST["fabrica"];

	$sql = "SELECT hd_chamado 
			FROM tbl_hd_chamado 
			WHERE fabrica_responsavel = {$login_fabrica} 
			AND hd_chamado = {$chamado} 
			AND fabrica = {$fabrica}";
	$res = pg_query($con, $sql);

	if (!pg_num_rows($res)) {
		echo "false";
	} else {
		echo "true";
	}

	exit;
}

if ($_POST["gravar"] == "true") {
	$fabrica    = $_POST["fabrica"];
	$chamado    = $_POST["chamado"];
	$tipo_venda = strtolower($_POST["tipo_venda"]);
	$valor      = $_POST["valor"];
	$valor      = str_replace(".", "", $valor);
	$valor      = str_replace(",", ".", $valor);	
	$mes        = $_POST["mes"];
	$ano        = $_POST["ano"];
	$quantidade = $_POST["quantidade"];
	$resposta 	= $_POST["resposta"];

	if (!strlen($chamado)) { 
		$chamado = "null";
	}

	if ($_POST["update"] != "true") {
		$sql = "SELECT * FROM tbl_controle_implantacao WHERE hd_chamado = $chamado and fabrica = $fabrica";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)>0){
			echo "parcelado";
			exit;
		}

		for($i=0; $i<$quantidade; $i++){

			$data_implantacao = "$ano-$mes-01 00:00:00";
			$data_implantacao = date('Y-m-d', strtotime($data_implantacao. ' + '.($i).' month'));

			if($resposta == "sim"){
				$parcela = $_POST["valor_$i"];	
				$parcela = str_replace(".", "", $parcela);
				$parcela = str_replace(",", ".", $parcela);
			}else{				
				$parcela 	= $valor / $quantidade;
			}

			//$data_implantacao "{$ano}-{$mes}-01 00:00:00";		

			$numero_parcela = $i + 1;
			$parcela = number_format($parcela, 2, '.', '');
			
			$sql = "INSERT INTO tbl_controle_implantacao
				(fabrica, data_implantacao, hd_chamado, valor_implantacao, tipo, admin, total_parcela, numero_parcela)
				VALUES
				({$fabrica}, '$data_implantacao', {$chamado}, {$parcela}, '{$tipo_venda}', {$login_admin}, '{$quantidade}', '{$numero_parcela}')";
			$res = pg_query($con, $sql);
		}
	}else{
		$controle_implantacao = $_POST["controle_implantacao"];

		$sql = "UPDATE tbl_controle_implantacao
				SET
					fabrica = {$fabrica},
					data_implantacao = '{$ano}-{$mes}-01 00:00:00',
					hd_chamado = {$chamado},
					valor_implantacao = {$parcela},
					tipo = '{$tipo_venda}',
					total_parcela = '{$quantidade}',
					numero_parcela = '{$numero_parcela}',
					admin = {$login_admin}
				WHERE controle_implantacao = {$controle_implantacao}";
		$res = pg_query($con, $sql);
	}

	if (strlen(pg_last_error()) > 0) {
		echo "false";
	} else {
		echo "true";
	}
	exit;

	
/*
	if ($_POST["update"] == "true") {
		$controle_implantacao = $_POST["controle_implantacao"];

		$sql = "UPDATE tbl_controle_implantacao
				SET
					fabrica = {$fabrica},
					data_implantacao = '{$ano}-{$mes}-01 00:00:00',
					hd_chamado = {$chamado},
					valor_implantacao = {$valor},
					tipo = '{$tipo_venda}',
					admin = {$login_admin}
				WHERE controle_implantacao = {$controle_implantacao}";
	} else {
		$sql = "INSERT INTO tbl_controle_implantacao
				(fabrica, data_implantacao, hd_chamado, valor_implantacao, tipo, admin)
				VALUES
				({$fabrica}, '{$ano}-{$mes}-01 00:00:00', {$chamado}, {$valor}, '{$tipo_venda}', {$login_admin})";
	}

	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		echo "false";
	} else {
		echo "true";
	}

	exit;
	*/
}

if (strlen($_GET["controle_implantacao"]) > 0) {
	$sql = "SELECT 
				fabrica,
				hd_chamado AS chamado,
				tipo,
				valor_implantacao AS valor,
				DATE_PART('MONTH', data_implantacao) AS mes,
				DATE_PART('YEAR', data_implantacao) AS ano
			WHERE controle_implantacao = {$_GET['controle_implantacao']}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$_RESULT = array(
			"fabrica"    => pg_fetch_result($res, 0, "fabrica"),
			"chamado"    => pg_fetch_result($res, 0, "chamado"),
			"tipo_venda" => pg_fetch_result($res, 0, "tipo"),
			"valor"      => number_format(pg_fetch_result($res, 0, "valor"), 2, ",", "."),
			"mes"        => (int) pg_fetch_result($res, 0, "mes"),
			"ano"        => pg_fetch_result($res, 0, "ano")
		);
	} else {
		$msg_erro = "Faturamento não encontrado";
	}
}

$arrayMes = array (
	1  => "Janeiro",
	2  => "Fevereiro",
	3  => "Março",
	4  => "Abril",
	5  => "Maio",
	6  => "Junho",
	7  => "Julho",
	8  => "Agosto",
	9  => "Setembro",
	10 => "Outubro",
	11 => "Novembro",
	12 => "Dezembro"
);

?>

<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="../admin/plugins/price_format/jquery.price_format.1.7.min.js"></script>
<script src="../admin/plugins/price_format/config.js"></script>
<script src="../admin/plugins/price_format/accounting.js"></script>
<script src="../admin/plugins/jquery.alphanumeric.js"></script>
<script type='text/javascript' src='../externos/bootstrap3/js/bootstrap.min.js'></script>
<link rel='stylesheet' href='../externos/bootstrap3/css/bootstrap.min.css'>
<link rel='stylesheet' href='../externos/bootstrap3/css/bootstrap-theme.min.css'>
<link href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">

<script>
	$(function () {
		$.ajaxSetup({
			async: false
		});

		$("#chamado").numeric();

		var form = $("#frm_faturamento");
		var input = {};
		var erro;

		$("#gravar").click(function () {
			var valorTotal = 0;
			var valorTemp = "";
			var valor_chamado;

			input.fabrica    = $(form).find("#fabrica");
			input.chamado    = $(form).find("#chamado");
			input.valor      = $(form).find("#valor");
			input.mes        = $(form).find("#mes");
			input.ano        = $(form).find("#ano");
			input.tipo_venda = $(form).find("#tipo_venda");
			input.quantidade = $(form).find("#quantidade");
			input.resposta 	 = $(form).find("#resposta");


			if ($(form).find("#controle_implantacao").val().length > 0) {
				input.controle_implantacao = $(form).find("#controle_implantacao");
			}

			if (validaForm() === true) {
				var data = {
					gravar: true
				};

				if (input.controle_implantacao != undefined) {
					data.update = true;
				}

				$.each(input, function (key, input) {
					data[key] = $(input).val();
				});

				a=0;
				$("input[name^=valor_]").each(function(){
					data['valor_'+a] = $("#valor_"+a).val();

					valorTemp = data['valor_'+a];
					valorTemp = valorTemp.replace(".", "");
					valorTemp = valorTemp.replace(",", ".");
					valorTemp = parseFloat(valorTemp);

					valorTotal += valorTemp;
					a++;
				});
				valor_chamado = data['valor'];

				valor_chamado = valor_chamado.replace(".", "");
				valor_chamado = valor_chamado.replace(",", ".");

				if(data['resposta'] == "sim"){
					if(valorTotal != valor_chamado){
						alert("Os valores das parcelas é diferente do valor total.")
						return false;
					}
				}

				$("#gravando").show();
				$("#erro, #success, #gravar").hide();
				$("#erro > h4, #success > h4").text("");
				$("div.control-group.error").removeClass("error");

				$.ajax({
					url: "cadastro_faturamento.php",
					type: "POST",
					data: data,
					complete: function (data) {
						data = data.responseText;
						if(data == "parcelado") {
							$("#erro > h4").append("Chamado já parcelado.<br />");
							$("#erro").show();
						}else if (data == "false") {
							$("#erro > h4").append("Erro ao gravar faturamento<br />");
							$("#erro").show();
						} else {
							if (input.controle_implantacao != undefined) {
								$("#success > h4").append("Faturamento atualizado com Sucesso!");
								$("#success").show();
							} else {
								$("#success > h4").append("Faturamento cadastrado com Sucesso!");
								$("#success").show();
								$(form).find("input, select").val("");
							}

							setTimeout(function () {
								window.parent.Shadowbox.close();
							}, 3000);

						}
					}
				});
			} else {
				$.each(erro.campos, function (key, value) {
					input[key].parents("div.control-group").addClass("error");
				});

				if (erro.obrigatorio === true) {
					$("#erro > h4").append("Preencha os campos obrigatórios<br />");
				}

				if (erro.msg.length > 0) {
					$("#erro > h4").append(erro.msg.join("<br />"));
				}

				$("#erro").show();
			}

			$("#gravando").hide();
			$("#gravar").show();

		});

		$("#quantidade").blur(function(){

			var txt;
			var valor = "";
			var r = confirm("Deseja colocar o valor das parcelas manualmente?");
			$(".campos").html('');
			if (r == true) {
			    $("#resposta").val("sim");
				
				var qtde = $("#quantidade").val();

				for(i=0; i< qtde; i++){
					if((i % 2) == 0){
						valor += "<div class='row'><div class='col-md-2 col-sm-2 col-xs-2 col-lg-2'></div> ";
							valor += "<div class='col-md-4 col-sm-4 col-xs-4 col-lg-4'>";
								valor += "<div class='control-group'>";
									valor += "<label class='control-label' for=''>Parcela "+(i+1)+"</label>";
									valor += "<div class='controls controls-row'>";
										valor += "<input  class='span10' type='text' price='true' name='valor_"+i+"' id='valor_"+i+"' value=''>";
									valor += "</div>";
								valor += "</div>";
							valor += "</div>";
					}else{
							valor += "<div class='col-md-4 col-sm-4 col-xs-4 col-lg-4'>";
								valor += "<div class='control-group'>";
									valor += "<label class='control-label' for=''>Parcela "+(i+1)+"</label>";
									valor += "<div class='controls controls-row'>";
										valor += "<input  class='span10' type='text'  price='true' name='valor_"+i+"' id='valor_"+i+"' value=''>";
									valor += "</div>";
								valor += "</div>";
							valor += "</div>";
							valor += "<div class='col-md-2 col-sm-2 col-xs-2 col-lg-2'></div>";
						valor += "</div>";
					}				
				}
				valor += "<div class='col-md-2 col-sm-2 col-xs-2 col-lg-2'></div>";
					valor += "</div>";
				$(".campos").append(valor);

			} else {
				$(".campos").html('');
				$("#resposta").val("nao");
			}

			$("input[price=true]").each(function () {
				var cents = $(this).attr('pricecents');
				if(cents == undefined){
					cents = 2;
				}

				$(this).priceFormat({
					prefix: '',
		            thousandsSeparator: '.',
		            centsSeparator: ',',
		            centsLimit: parseInt(cents)
				});
			});
		});

		$("#chamado").blur(function(){
		
			var hd_chamado = $("#chamado").val();

			$.ajax({
				url: "cadastro_faturamento.php",
				type: "POST",
				dataType: "json",
				data: {pesquisa_chamado: true, hd_chamado: hd_chamado},
				success: function (data) {
					$('#fabrica option[value="'+data.fabrica+'"]').prop('selected', true);
					$('#tipo_venda option[value="'+data.tipo+'"]').prop('selected', true);
					
				}		
			});
		});

		function validaForm () {
			erro = {
				obrigatorio: false,
				campos: {},
				msg: []
			};

			var validado = true;

			var fabrica    = $(input.fabrica).val();
			var chamado    = $(input.chamado).val();
			var valor      = $(input.valor).val();
			valor          = valor.replace(/\./g, "").replace(/\,/g, ".");
			valor          = parseFloat(valor);
			var mes        = $(input.mes).val();
			var ano        = $(input.ano).val();
			var tipo_venda = $(input.tipo_venda).val();

			var ano_atual    = new Date();
			ano_atual        = ano_atual.getFullYear();
			var ano_anterior = (ano_atual - 1);

			//Verifica campos obrigatórios
			if (fabrica.length == 0) {
				validado            = false;
				erro.obrigatorio    = true;
				erro.campos.fabrica = true;
			}

			if (isNaN(valor) === true) {
				validado          = false;
				erro.obrigatorio  = true;
				erro.campos.valor = true;
			}

			if (mes.length == 0) {
				validado         = false;
				erro.obrigatorio = true;
				erro.campos.mes  = true;
			}

			if (ano.length == 0) {
				validado         = false;
				erro.obrigatorio = true;
				erro.campos.ano  = true;
			}


			if (tipo_venda.length == 0) {
				validado               = false;
				erro.obrigatorio       = true;
				erro.campos.tipo_venda = true;
			}

			//Verifica os valores dos campos
			if (erro.campos.valor == undefined && (valor == 0)) {
				validado          = false;
				erro.campos.valor = true;

				erro.msg.push("Valor não pode ser 0");
			}

			if (erro.campos.mes == undefined && (mes < 1 || mes > 12)) {
				validado = false;
				erro.campos.mes = true;

				erro.msg.push("Mês inválido");
			}

			if (erro.campos.ano == undefined && (ano < ano_anterior || ano > ano_atual)) {
				validado        = false;
				erro.campos.ano = true;

				erro.msg.push("Ano inválido");
			}

			if ($.trim(chamado).length > 0) {
				if (fabrica.length == 0) {
					validado            = false;
					erro.campos.fabrica = true;
					erro.msg.push("Selecione a fábrica para verificar o chamado");	
				} else if (verificaChamado(chamado, fabrica) === false) {
					validado            = false;
					erro.campos.chamado = true;
					erro.msg.push("Chamado não encontrado");
				}
			}

			return validado;
		}

		function verificaChamado(chamado, fabrica) {
			var ajax = $.ajax({
				url: "cadastro_faturamento.php",
				type: "POST",
				data: { verifica_chamado: true, chamado: chamado, fabrica: fabrica }
			});

			if (ajax.responseText == "false") {
				var ajaxReturn = false;
			} else {
				var ajaxReturn = true;
			}

			return ajaxReturn;
		}
	});
</script>

	<div id="erro" class="alert alert-danger" style="display: <?=(strlen($msg_erro) > 0) ? 'block' : 'none'?>;text-align: center;" >
		<h4><?=$msg_erro?></h4>
	</div>

	<?php

	if (strlen($msg_erro) > 0) {
		echo "</div>";

		exit;
	}

	?>

	<div id="success" class="alert alert-success" style="display: none;">
		<h4></h4>
	</div>
	<div class="container">

	<div class="row">
		<b class="obrigatorio pull-right" style="color: #B94A48;"> * Campos obrigatórios </b>
	</div>

	<div class="panel panel-default">
	<form class="tc_formulario" id="frm_faturamento" method="POST" >
		<input type="hidden" id="controle_implantacao" name="controle_implantacao" value="<?=$_GET['controle_implantacao']?>" />
			<div class="panel-heading">
        		<h3 class="panel-title"><center>Cadastro de faturamento</center></h3>
        	</div>
        	<div class="panel-body">
        		  <div class="row">
					<div class="col-md-2 col-sm-2 col-xs-2 col-lg-2"></div>
		              <div class="col-md-4 col-sm-4 col-xs-4 col-lg-4">
		                <div class="form-group">
		                  <label for="CNPJ"><span style="color: red;">*</span> Fábrica:</label>
		                  <div class="input-group">
		          			<select class="form-control" id="fabrica" name="fabrica">
								<option></option>
								<?php

								$sql = "SELECT fabrica, nome 
										FROM tbl_fabrica 
										WHERE ativo_fabrica IS TRUE 
										ORDER BY nome ASC";
								$res = pg_query($con, $sql);

								$rows = pg_num_rows($res);

								for ($i = 0; $i < $rows; $i++) { 
									$fabrica = pg_fetch_result($res, $i, "fabrica");
									$nome    = pg_fetch_result($res, $i, "nome");

									$selected = ($_RESULT["fabrica"] == $fabrica) ? "selected" : "";

									echo "<option value='{$fabrica}' {$selected} >{$nome}</option>";
								}

								?>
							</select>
		                  </div>
		                </div>
		              </div>
		              <div class="col-md-4 col-sm-4 col-xs-4 col-lg-4">
		                <div class="form-group">
		                  <label for="CNPJ"><span style="color: red;">*</span> Nº Chamado</label>
		                  <div class="input-group">
		          			<input class="form-control" type="text" id="chamado" name="chamado" value="<?=$_RESULT['chamado']?>" />
		                  </div>
		                </div>
		              </div>
		              <div class="col-md-1 col-sm-1 col-xs-1 col-lg-1"></div>
		          </div>
		          <div class="row">
					<div class="col-md-2 col-sm-2 col-xs-2 col-lg-2"></div>		
		              <div class="col-md-4 col-sm-4 col-xs-4 col-lg-4">
		                <div class="form-group">
		                  <label for="CNPJ"><span style="color: red;">*</span> Tipo de Venda:</label>
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
		              <div class="col-md-4 col-sm-4 col-xs-4 col-lg-4">
		                <div class="form-group">
		                  <label for="CNPJ"><span style="color: red;">*</span> Valor</label>	
		                  <div class="input-group">
		          			<input type="text" price="true" id="valor" name="valor" value="<?=$_RESULT['valor']?>" />
		                  </div>
		                </div>
		              </div>  
		              <div class="col-md-1 col-sm-1 col-xs-1 col-lg-1"></div>
		          </div>
		          <br />
		          <div class="row">
					<div class="col-md-2 col-sm-2 col-xs-2 col-lg-2"></div>
		              <div class="col-md-4 col-sm-4 col-xs-4 col-lg-4">
		                <div class="form-group">
		                  <label for="CNPJ"><span style="color: red;">*</span> Mês: </label>
		                  <div class="input-group">
		          			<select class="form-control" id="mes" name="mes">
								<option></option>
								<?php

								foreach ($arrayMes as $mesNumero => $mesNome) {
									$selected = ($_RESULT["mes"] == $mesNumero) ? "selected" : "";

									echo "<option value='{$mesNumero}' {$selected} >{$mesNome}</option>";
								}

								?>
							</select>
		                  </div>
		                </div>
		              </div> 
		              <div class="col-md-4 col-sm-4 col-xs-4 col-lg-4">
		                <div class="form-group">
		                  <label for="CNPJ"><span style="color: red;">*</span> Ano: </label>
		                  <div class="input-group">
		          			<select class="form-control" id="ano" name="ano">
								<option></option>
								<?php

								for ($i = (date("Y") - 1); $i <= date("Y") ; $i++) { 
									$selected = ($_RESULT["ano"] == $i) ? "selected" : "";

									echo "<option {$selected} >{$i}</option>";
								}

								?>
							</select>
		                  </div>
		                </div>
		              </div>
		              <div class="col-md-1 col-sm-1 col-xs-1 col-lg-1"></div>
		          </div>
		          <div class="row">
					<div class="col-md-2 col-sm-2 col-xs-2 col-lg-2"></div>
		              <div class="col-md-4 col-sm-4 col-xs-4 col-lg-4">
		                <div class="form-group">
		                  <label class="control-label" for=''>Quantidade</label>
		                  <div class="input-group">
		          			<input  class="span2" type="text" name="quantidade" id="quantidade" value="<?=$quantidade?>">
		                  </div>
		                </div>
		              </div>
		              <div class="col-md-1 col-sm-1 col-xs-1 col-lg-1"></div>
		          </div>
		          <div class="campos"></div>
				</form>	
			<br/>

				<span id="gravando" style="display: none;" >
					Gravando<br />
					<img src="imagens/loading_img.gif" />
				</span>
				<br />
				<center>
					<button type="button" class="btn btn-primary" id="gravar" >Gravar</button>
				</center>

		<br/>
		</div>
</div>
