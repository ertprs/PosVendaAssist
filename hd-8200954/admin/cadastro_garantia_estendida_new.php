<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

	if(!empty($_POST['delete_ajax'])){
		$cliente_garantia_estendida = $_POST['cliente_garantia_estendida'];
		$sql = "DELETE FROM tbl_cliente_garantia_estendida WHERE cliente_garantia_estendida = {$cliente_garantia_estendida}";
		$res = pg_query($con, $sql);
		if(pg_affected_rows($res) > 0){
			exit('1');
		}

		exit('0');
	}

include '../helpdesk/mlg_funciones.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
$layout_menu = "cadastro";
if($login_fabrica == 91){
	$title = "CADASTRO DE PRODUTOS INATIVADOS";
}else{
	$title = "CADASTRO DE GARANTIA ESTENDIDA";
}
include 'cabecalho_new.php';
//include "javascript_calendario.php";

$plugins = array(
    "datepicker",
    "mask",    
);

include("plugin_loader.php");

?>
<script type="text/javascript"    src="js/jquery.alphanumeric.js"></script>

<script language='JavaScript'>
$(function(){
	$('#qtde_tempo').numeric();

	$.datepickerLoad(Array("data_venda"));


	<?php if($login_fabrica == 153){?>
		$("#serieForm").numeric();
	<?php }?>

});
</script>

<?php
########################### FUNÇÕES INTERNAS DA PÁGINA #########################

function geraFormulario($formTitle = NULL, $formNames = NULL, $postValues = NULL, $infoMessage = NULL, $login_fabrica, $campoErro){

	$form = NULL;
	if($login_fabrica == 3){
		if($postValues[$formNames['tempo']] == "1"){
			$mes1Select =  "selected=\"selected\"";
		}
		if($postValues[$formNames['tempo']] == "2"){
			$mes2Select =  "selected=\"selected\"";
		}
		if($postValues[$formNames['tempo']] == "3"){
			$mes3Select =  "selected=\"selected\"";
		}
		if($postValues[$formNames['tempo']] == "4"){
			$mes4Select =  "selected=\"selected\"";
		}
	}

	$form .= (empty($infoMessage)) ? "" : $infoMessage;

	$disabled_edit = (intval($postValues['cadastro_garantia_estendida_new']) > 0) ? " disabled = 'disabled' " : "";

	$form .= "

		<form method=\"post\" name='frm_relatorio' align='center' class='form-search form-inline tc_formulario' action='cadastro_garantia_estendida_new.php'>

			<input type=\"hidden\" name='cliente_garantia_estendida' id='cliente_garantia_estendida' value='{$postValues['cliente_garantia_estendida']}' />
			<input type=\"hidden\" name=\"".$formNames['nomeConsumidor']."\" id=\"".$formNames['nomeConsumidor']."\"  value=\"".$postValues[$formNames['nomeConsumidor']]."\" />
			<input type=\"hidden\" name=\"".$formNames['cpfConsumidor']."\" id=\"".$formNames['cpfConsumidor']."\"  value=\"".$postValues[$formNames['cpfConsumidor']]."\" />
			<input type=\"hidden\" name=\"".$formNames['cidadeConsumidor']."\" id=\"".$formNames['cidadeConsumidor']."\"  value=\"".$postValues[$formNames['cidadeConsumidor']]."\" />
			<input type=\"hidden\" name=\"".$formNames['enderecoConsumidor']."\" id=\"".$formNames['enderecoConsumidor']."\"  value=\"".$postValues[$formNames['enderecoConsumidor']]."\" />
			<input type=\"hidden\" name=\"".$formNames['cepConsumidor']."\" id=\"".$formNames['cepConsumidor']."\"  value=\"".$postValues[$formNames['cepConsumidor']]."\" />
			<input type=\"hidden\" name=\"".$formNames['notaFiscal']."\" id=\"".$formNames['notaFiscal']."\"  value=\"".$postValues[$formNames['notaFiscal']]."\" />
			<input type=\"hidden\" name=\"".$formNames['revendaNome']."\" id=\"".$formNames['revendaNome']."\"  value=\"".$postValues[$formNames['revendaNome']]."\" />
			<input type=\"hidden\" name=\"".$formNames['estadoConsumidor']."\" id=\"".$formNames['estadoConsumidor']."\"  value=\"".$postValues[$formNames['estadoConsumidor']]."\" />
			<input type=\"hidden\" name=\"".$formNames['numeroConsumidor']."\" id=\"".$formNames['numeroConsumidor']."\"  value=\"".$postValues[$formNames['numeroConsumidor']]."\" />
			<input type=\"hidden\" name=\"".$formNames['dataNf']."\" id=\"".$formNames['dataNf']."\"  value=\"".$postValues[$formNames['dataNf']]."\" />
			<input type=\"hidden\" name='produto' id='produto'  value=\"".$postValues[$formNames['produto']]."\" />

			<div class='titulo_tabela '>{$formTitle}</div>
			<br/>";

		if(in_array($login_fabrica, [153,157])){
			$form .="<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group $campoErro[cpf]'>
						<label class='control-label' for='serieForm'>CPF do Cliente</label>
						<div class='controls controls-row'>						
						<h5 class='asteristico'>*</h5>
							<div class='span7 input-append'>
								<input type='text' id='cpf' name='cpf' class='span12' maxlength='11' value=\"".$postValues['cpf']."\">								
							</div>
						</div>
					</div>
				</div>";
			if(in_array($login_fabrica, [153,157])){
				$form .="<div class='span4'>
						<div class='control-group $campoErro[data_venda]'>
							<label class='control-label' for='serieForm'>Data Venda</label>
							<div class='controls controls-row'>						
							<h5 class='asteristico'>*</h5>
								<div class='span7 input-append'>
									<input type='text' id='data_venda' name='data_venda' class='span12' maxlength='11' value=\"".$postValues['data_compra']."\">								
								</div>
							</div>
						</div>
					</div>";
			}
			$form .="</div>";
		}			

			$form .="<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>

					<div class='control-group $campoErro[serieForm] '>
						<label class='control-label' for='serieForm'>Número Série</label>
						<div class='controls controls-row'>
								<h5 class='asteristico'>*</h5>							
							<div class='span7 input-append'>
								<input type='text' id=\"".$formNames['serieForm']."\" name=\"".$formNames['serieForm']."\" class='span12' maxlength='' value=\"".$postValues[$formNames['serieForm']]."\"{$disabled_edit}' >
								<span class='add-on' id=\"imgLupa\"><i class='icon-search'></i></span>
							</div>
						</div>
					</div>
				</div>
			";

			if($login_fabrica == 3){
				$form .="<div class='span4'>
					<div class='control-group' >
						<label class='control-label' for='tempo'>Tempo</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<select name=\"".$formNames['tempo']."\" >
									<option ".$mes1Select." value = 1>1 mês</option>
									<option ".$mes2Select." value = 2>2 meses</option>
									<option ".$mes3Select." value = 3>3 meses</option>
									<option ".$mes4Select." value = 4>4 meses</option>
									<option ".$mes4Select." value = 5>5 meses</option>
								</select>
							</div>
						</div>
					</div>
				</div>";
			}else{
				$form .="<div class='span2'>
								<div class='control-group $campoErro[tempo]'>
									<label class='control-label' for='tempo'>Tempo</label>
									<div class='controls controls-row'>";
										if($login_fabrica == 153){
											$form .= "<h5 class='asteristico'>*</h5>";
										}
										$form .="<div class='span8'>
											<input type='text' id='qtde_tempo' name=\"".$formNames['tempo']."\" class='span12' maxlength='2' value=\"".$postValues[$formNames['tempo']]."\"{$disabled_edit}' >
										</div>
									</div>
								</div>
							</div>
							<div class='span2'>
								<div class='control-group>
									<label class='control-label' for='tempo' style='margin-top: 25px; margin-left: -55px;' >Qtde Mes</label>
								</div>
							</div>

							";
			}

			$form .="<div class='span2'></div>
			</div>

			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group $campoErro[referencia]'>
						<label class='control-label' for='referencia'>Referência</label>
						<div class='controls controls-row'>
							<h5 class='asteristico'>*</h5>
							<div class='span7 input-append'>
								<input type='text' id=\"".$formNames['referencia']."\" name=\"".$formNames['referencia']."\" class='span12' maxlength='' value=\"".$postValues[$formNames['referencia']]."\" {$disabled_edit}' >
								<span class='add-on' id=\"LupaReferencia\"><i class='icon-search'></i></span>
							</div>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group $campoErro[produto]' >
						<label class='control-label' for='descricao'>Descrição</label>
						<div class='controls controls-row'>
							<h5 class='asteristico'>*</h5>
							<div class='span12 input-append'>
								<input type='text' id=\"".$formNames['descricao']."\" name=\"".$formNames['descricao']."\" class='span12' maxlength='' value=\"".$postValues[$formNames['descricao']]."\" {$disabled_edit}' >
								<span class='add-on' id=\"LupaDescricao\"><i class='icon-search'></i></span>
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>";

			if($login_fabrica == 3 or $login_fabrica == 153){
				$form .="<div class='row-fluid'>
						<div class='container'>
							<div class='span2'></div>
							<div class='span8'>
								<div class='control-group'>
									<label class='control-label'>Motivo</label>
									<div class='controls controls-row'>
									<textarea class=\"span12\" name=\"".$formNames['motivo']."\" size='30' maxlength='255' >".$postValues[$formNames['motivo']]."</textarea>							</div>
								</div>
							</div>
							<div class='span2'></div>
						</div>
					</div>";
			}
			$botao_upload = "";
			if (in_array($login_fabrica, array(176))) {
				$botao_upload = "<a href=\"cadastro_garantia_estendida_via_upload.php\" class='btn btn-warning' style=\"margin-left:75px;\">Cadastro via Upload</a>";
			}
			$form .="<p><br/>
				<input type=\"button\" class='btn' id='btn_limpar' value=\"Limpar\"  style=\"margin-right:75px;\"/>
				<input type=\"submit\" class='btn btn-success' name=\"".$formNames['submitButton']."\" value=\"Gravar\" />
				{$botao_upload }
				<input type=\"submit\" class='btn btn-primary' name=\"".$formNames['submitButton']."\" value=\"Consultar\" style=\"margin-left:75px;\"  />
			</p><br/>
			</div>
		</form>
	";

	$return['form'] = $form;
	$return['formNames'] = $formNames ;
	return $return;

}

function getPost($formNames){

	$qtNames = count($formNames);

	if(!empty($qtNames)){
		foreach ($formNames as $value) {
			$getPost[$value] = $_POST[$value];
		}
	}else{
		return FALSE;
	}

	return $getPost;

}

function geraSQL($type = NULL, $table = NULL, $campos = NULL, $where = NULL, $values = NULL, $join = NULL){
	global $con;

	switch ($type) {
		case 'insert':
			$cmdSQL = "INSERT INTO ".$table." (".$campos.") VALUES (".$values.");";

			break;
		case 'select':

			$cmdSQL = "SELECT ".$campos." FROM ".$table." ".$JOIN." ".$where." ;";
			break;

		default:

			break;
	}
	$res = pg_query($con, $cmdSQL);
	if($res){
		$fetchDb = pg_fetch_all($res);
		return $fetchDb;
	}

	return false;
}

function geraTabela($Array, $login_fabrica){

	$tabela = NULL;

	$qtArray = count($Array);

	if(empty($Array[0])){
			$tabela .= "<table class='table table-striped table-bordered table-hover table-fixed'>";
			$tabela .= "<thead>";
			$tabela .= "<tr class='titulo_coluna'>";
			$tabela .= "<th colspan=\"3\">";
			$tabela .= "Consulta de Todos Registros";
			$tabela .= "</th>";
			$tabela .= "</tr>";
			$tabela .= "</thead>";
			$tabela .= "<tbody>";
			$tabela .= "<tr>";
			$tabela .= "<td class='tac'>";
			$tabela .= "Não existem registros!";
			$tabela .= "</td>";
			$tabela .= "</tr>";
			$tabela .= "</tbody>";
			$tabela .= "</table>";
		return $tabela;
	}

	$tabela .= "<script>
					function move_i(what) { what.style.background='#D9E2EF'; }
					function move_o(what) { what.style.background='#FFFFFF'; }
				</script>";
	// $tabela .= "<table cellspacing='0' cellpadding='2'  align=\"center\" width=\"695px\" cellspacing=\"1\" id=\"tabelaRegistro\">";
	// $tabela .= "<tr>";
	// $tabela .= "<td colspan=\"3\" align=\"center\" style=\"background-color:#596D9B; color: #FFFFFF; font-size: 14px;font:bold;\">Consulta de Todos Registros</td>";
	// $tabela .= "</tr>";
	// $tabela .= "<tr>";
	// $tabela .= "<td>";

	$tabela .= "<br /><br />";
	$tabela .= "<form method=\"post\">";
		$tabela .= "<input type=\"hidden\" name=\"consultaAction\" value=\"excluir\" />";
		$tabela .= "<table class='table table-striped table-bordered table-hover table-fixed'>";
			$tabela .= "<thead>";
			$tabela .= "<tr class='titulo_coluna'>";

			if($login_fabrica <> 91){
				$tabela .= "<th><input  type=\"checkbox\" id=\"selecionarTodos\" /></th>";
			}
					foreach ($Array[0] as $key => $value) {
						if($login_fabrica == 91){
							$key = ($key == "os") ? "OS" : $key;
						}
						$key = ($key == "numero_serie") ? "Nº Série" : $key;
						$key = ($key == "garantia_mes") ? "Garantia" : $key;
						$key = ($key == "nome_completo") ? "Administrador" : $key;
						$key = ($key == "motivo") ? "Motivo" : $key;
						$key = ($key == "descricao") ? "Produto" : $key;

						if($login_fabrica == 91){
							if(!in_array($key, array('cliente_garantia_estendida'))) {
								$tabela .= "<th>{$key}</th>";
							}
						}else{
							if(!in_array($key, array('os','cliente_garantia_estendida'))) {
								$tabela .= "<th>{$key}</th>";
							}
						}
					}
				$tabela .= "<th>Ações</th>";
				$tabela .= "</tr>";
				$tabela .= "</thead>";
				$tabela .= "<tbody>";
				for($countArray = 0; $qtArray>$countArray;$countArray++){
					$cor = ($countArray % 2) ? "#F7F5F0" : "#F1F4FA";

					// Não deixar excluit se já tiver OS
					if($login_fabrica <> 91 and $login_fabrica <> 153){
						$disabled_delete = (!empty($Array[$countArray]['os'])) ? " disabled = 'disabled' " : "";
					}
					$tabela .= "<tr class='table_line'>";
					if($login_fabrica <> 91){
						$tabela .= "<td><center>";
							if(trim(empty($disabled_delete)))
								$tabela .= "<input type=\"checkbox\" name=\"id_cliente_garantia_estendida\" id=\"id_garantia_estendida\" value=\"".$Array[$countArray]['cliente_garantia_estendida']."\" {$disabled_delete} />";
							else
								$tabela .= "&nbsp;";
						$tabela .="</center></td>";
					}
							foreach ($Array[$countArray] as $key => $value) {
								if($key == "garantia_mes"){
									switch ($value) {
										case $value == 1 : $value = $value." mês"; break;
										case $value > 1 : $value = $value." meses"; break;
										default: $value = $valuebreak;
									}
								}

								if($login_fabrica == 91){
									if(!in_array($key, array('cliente_garantia_estendida'))) {
										$tabela .= "<td>".$value."</td>";
									}
								}else{
									if(!in_array($key, array('os','cliente_garantia_estendida'))) {
										$tabela .= "<td>".$value."</td>";
									}
								}
							}

						$tabela .= "<td nowrap id='botoes' rel='{$Array[$countArray]['cliente_garantia_estendida']}'>
										<input type='button' class='btn_edit btn' value='Editar' />
										<input type='button' class='btn_apagar btn btn-danger' value='Apagar' {$disabled_delete} /> 
									</td>";
					$tabela .= "</tr>";

				}
			$tabela .= "</tbody>";
		if($login_fabrica <> 91){
			$tabela .= "<tfoot>";
			$tabela .= "<tr  class='titulo_coluna' >";
				$tabela .= "<th class='tal' colspan='8'>";
					$tabela .= "<select>
									<option>Excluir</option>
								</select>
								<input type=\"submit\" style='margin-bottom: 10px; margin-left: 20px;' class='btn' value=\"Gravar\" />";
				$tabela .= "</th>";
			$tabela .= "</tr>";
			$tabela .= "</tfoot>";
		}
		$tabela .= "</table>";
	$tabela .= "</form>";
	// $tabela .= "</td>";
	// $tabela .= "</tr>";
	// $tabela .= "</table>";
	return $tabela;

}

function autocomplete(){

	$source = NULL;
	$source .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"js/jquery.autocomplete.css\" />";
	$source .= "<script type=\"text/javascript\" src=\"js/jquery.bgiframe.min.js\"></script>";
	$source .= "<script type=\"text/javascript\" src=\"js/jquery.autocomplete.js\"></script>";	
	$source .= "<script src='../plugins/shadowbox/shadowbox.js'	type='text/javascript'></script>";
	$source .= "<link rel='stylesheet' type='text/css' href='../plugins/shadowbox/shadowbox.css' media='all'>";
	//$source .= "<script type=\"text/javascript\" src=\"js/jquery.dimensions.js\"></script>";
	$source .= "

		<script>
			$(document).ready(function() {
				//numero_serie();
				Shadowbox.init();

				$(\"#imgLupa\").click(function () {
		      		numero_serie();
			    });

				$('#LupaReferencia').click(function(){
					pesquisaProduto($('#referencia'), 'referencia');
				});

				$('#LupaDescricao').click(function(){
					pesquisaProduto($('#descricao'), 'descricao');
				});


			    $('#selecionarTodos').click(function() {
			        if(this.checked == true){
			            $(\"input[type=checkbox]\").each(function() {
			                this.checked = true;
			            });
			        } else {
			            $(\"input[type=checkbox]\").each(function() {
			                this.checked = false;
			            });
			        }
			    });

				$('.btn_edit').bind('click', function(){
					var cliente_garantia_estendida = $(this).parent().attr('rel');

					window.location.href =  'cadastro_garantia_estendida_new.php?cliente_garantia_estendida='+cliente_garantia_estendida;
				});

				$('#btn_limpar').bind('click', function(){
					var cliente_garantia_estendida = $(this).parent().attr('rel');

					window.location.href =  'cadastro_garantia_estendida_new.php';
				});

				$('.btn_apagar').bind('click', function(){
					var cliente_garantia_estendida = $(this).parent().attr('rel');
					var tr = $(this).parent().parent();

					if(cliente_garantia_estendida.length > 0){
						$.ajax({
						  	type: 'POST',
						  	url: 'cadastro_garantia_estendida_new.php',
						  	data: {'cliente_garantia_estendida':cliente_garantia_estendida, 'delete_ajax':'delete_ajax'},
						  	success: function(data) {
						  		if(data == 1)
						  			tr.fadeOut('1000');
						  		else
						  			alert('Erro ao apagar registro!');
						  	}
						});
					}

				});
			});


			function numero_serie(){
				//$(\"#referencia\").attr('disabled', true);
	      		//$(\"#descricao\").attr('disabled', true);

				var serieForm = $(\"#serieForm\").val();

				if(serieForm != \"\"){
			    	$.get(\"autocomplete_os_ajax.php\", { q:serieForm },
				    function(data){
				    	if(data != \"\"){
				      		var retorno = data.split(\"|\");
				      		$(\"#serieForm\").val(retorno[0]);
				      		$(\"#referencia\").val(retorno[1]);
				      		$(\"#descricao\").val(retorno[2]);
				      		$(\"#nomeConsumidor\").val(retorno[3]);
				      		$(\"#enderecoConsumidor\").val(retorno[4]);
				      		$(\"#cidadeConsumidor\").val(retorno[5]);
				      		$(\"#cpfConsumidor\").val(retorno[6]);
				      		$(\"#notaFiscal\").val(retorno[7]);
				      		$(\"#revendaNome\").val(retorno[8]);
				      		$(\"#cepConsumidors\").val(retorno[9]);
				      		$(\"#estadoConsumidor\").val(retorno[10]);
				      		$(\"#numeroConsumidor\").val(retorno[11]);
				      		$(\"#dataNf\").val(retorno[12]);
				      		$(\"#produto\").val(retorno[13]);
				      	}else{
				      		alert(\"Número de Série não encontrado\");
				      	}
				    });
				} else {
				 	alert(\"Digite um número de série\");
				}
			}

			function pesquisaProduto(produto,tipo){

				if (jQuery.trim(produto.val()).length > 2){
					Shadowbox.open({
						content : 'produto_lupa_new.php?parametro='+tipo+'&valor='+produto.val(),
						player 	: 'iframe',
						title 	: 'Produto',
						width 	: 800,
						height 	: 500
					});
				}else{
					alert('Informar toda ou parte da informação para realizar a pesquisa!');
					produto.focus();
				}
			}

			function retorna_dados_produto(referencia,descricao,produto,linha,nome_comercial,voltagem,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada){
				gravaDados('referencia',referencia);
				gravaDados('descricao',descricao);
				gravaDados('produto',produto);
			}

			function retorna_produto (retorno) {
				$('#produto').val(retorno.produto);
				$('#referencia').val(retorno.referencia);
				$('#descricao').val(retorno.descricao);
			}

			function gravaDados(name, valor){
				try {
					$('input[name='+name+']').val(valor);
				} catch(err){
					return false;
				}
			}
		</script>
	";

	return $source;

}

function style(){
	$style = NULL;
	$style .= '
	<style>
		#tabelaRegistro{
			margin-top: 50px;
		}
		#tabelaRegistro tr td{
		    font-family: verdana;
		    font-size: 11px;
		    border-collapse: collapse;
		    border:1px solid #596d9b;
		}
		#botoes{
			text-align:center;
		}
	</style>
';
	return $style;
}

####################################### FIM ####################################


/*-----------------------------------------------------------------------------*/


################################# FUNÇÃO DA MAIN ###############################

function main(){

	global $login_admin;
	global $login_fabrica;
	global $con;
	global $PHP_SELF;

	$page = $_GET['page'];
	$registrosPag = 30;


	echo autocomplete();

	echo style();

	$formNames = array(
			'numSerie' => 'numSerie',
			'descricao' => 'descricao',
			'tempo' => 'tempo',
			'motivo' => 'motivo',
			'submitButton' => 'submitButton',
			'cpfConsumidor' => 'cpfConsumidor',
			'nomeConsumidor' => 'nomeConsumidor',
			'cidadeConsumidor' => 'cidadeConsumidor',
			'enderecoConsumidor' => 'enderecoConsumidor',
			'estadoConsumidor' => 'estadoConsumidor',
			'numeroConsumidor' => 'numeroConsumidor',
			'cepConsumidor' => 'cepConsumidor',
			'notaFiscal' => 'notaFiscal',
			'revendaNome' => 'revendaNome',
			'serieForm' => 'serieForm',
			'dataNf' => 'dataNf',
			'referencia' => 'referencia',
			'produto' => 'produto',
			'cliente_garantia_estendida' => 'cliente_garantia_estendida',
			'cpf' => 'cpf'
		);

	if($_POST['consultaAction'] == "excluir"){
		$infoMessage = NULL;

		$postDelete = file_get_contents("php://input");

		$explodePost = explode("&", $postDelete);

		$qtPost = count($explodePost);

		if($qtPost>1){
			$cmdDelete =  NULL;

			$cmdDelete .= "DELETE FROM tbl_cliente_garantia_estendida WHERE cliente_garantia_estendida in (";

			for($countPost = 1; $countPost<$qtPost; $countPost++){

				// exemplo de soma de datas no PHP date('d/m/Y',mktime(0,0,0, date('m') + 5,date('d', date('Y'))));
				$numPost = explode("=", $explodePost[$countPost]);

				if($countPost >= ($qtPost-1)){
					$cmdDelete .= $numPost[1]."";
				}else{
					$cmdDelete .= $numPost[1].",";
				}

				$sqlVerfGarantia = "
							SELECT
								tbl_cliente_garantia_estendida.os,
								tbl_cliente_garantia_estendida.numero_serie
							FROM tbl_cliente_garantia_estendida
							WHERE
								cliente_garantia_estendida = '{$numPost[1]}'
								AND os IS NOT NULL;";

				$verfGarantiaQuery = pg_query($con,$sqlVerfGarantia);

				$erroQuery = FALSE;
				if(pg_num_rows($verfGarantiaQuery) > 0){
					$verfNumeroSerie = trim(pg_fetch_result($verfGarantiaQuery,0,'numero_serie'));

					$infoMessage = "
					<div class='container'>
						<div class='alert alert-error'>
							    <h4>A garantia '{$verfNumeroSerie}' já foi utilizada e por isso não pode ser excluída!</h4>
						</div>
					</div>";
					$erroQuery = TRUE;
				}
			}

			$cmdDelete .= ") AND fabrica = ".$login_fabrica." AND os IS NULL;";

			//$cmdDelete;

			if($erroQuery == FALSE){
				$queryDelete = pg_query($con, $cmdDelete);
				if(($qtPost-1) == 1){
					$infoMessage = "
					<div class='container'>
						<div class='alert alert-success'>
							    <h4>".($qtPost-1)." registro excluído com sucesso!</h4>
						</div>
					</div>";
				}else{
					$infoMessage = "
					<div class='container'>
						<div class='alert alert-success'>
							    <h4>".($qtPost-1)." registros excluídos com sucesso!</h4>
						</div>
					</div>";
				}

			}

		}else{
			$infoMessage = "<div class='container'>
						<div class='alert alert-error'>
							    <h4>Você não selecionou campo para exclusão!</h4>
						</div>
					</div>";
		}

	}

	$varPost = getPost($formNames);
	if($varPost['submitButton'] == "Limpar"){
		unset($varPost);
	}

	if($varPost['submitButton'] == "Gravar"){

		if(in_array($login_fabrica, [153,157])){
			$cpf = $_POST['cpf'];			
		}

		if(in_array($login_fabrica, [157])){
			$data_venda = $_POST['data_venda'];			
		}

		$cliente_garantia_estendida = (int) $varPost['cliente_garantia_estendida'];
		$infoMessage = NULL;

		if($login_fabrica == 153){
			if(empty($varPost['cpf']) || $varPost['cpf'] ==  " "){
					$infoMessage .= "<div class='container'>
				<div class='alert alert-error'>
					    <h4>O campo CPF deve ser preenchido. </h4>
				</div>
				</div>";
					$campoErro['cpf'] = "error";
			}

			$serie = $varPost['serieForm'];

			//valida número de série
			$primeiro_caracter = substr($serie, 0,1);
			$ano = substr($serie, 1,2);
			$mes = substr($serie, 3,2);
			$finais = substr($serie, 5,5);

			$ano_limite = date("y") - 5;

			$array_primeiro = array(7,8,3);

			if(!in_array($primeiro_caracter, $array_primeiro)){
				$erro = TRUE;
			}

			if(strlen(trim($serie)) != 10){
				$erro = TRUE;
			}

			if($ano < $ano_limite or $ano > date("y")){
				$erro = true;
			}

			if($mes > 12){
				$erro = true;
			}

			if($erro == 1 and $serie <> 'S/N' and empty($infoMessage)){
				$infoMessage .= "<div class='container'>
						<div class='alert alert-error'>
							    <h4>Número de série inválido</h4>
						</div>
						</div>";
				$campoErro['serieForm'] = "error";
			}			
			//valida número de série
		}

		if($cliente_garantia_estendida == 0){
			if(empty($varPost['serieForm']) || $varPost['serieForm'] ==  " " AND empty($infoMessage)){
				$infoMessage .= "<div class='container'>
			<div class='alert alert-error'>
				    <h4>Campo numero de série em branco</h4>
			</div>
			</div>";
				$campoErro['serieForm'] = "error";
			}

		if(!in_array($login_fabrica, array(157, 153, 176))){
			$verfSql = 'SELECT
						tbl_os.serie
					FROM tbl_os
					WHERE tbl_os.fabrica = \''.$login_fabrica.'\'
					AND tbl_os.serie = \''.$varPost['serieForm'].'\' LIMIT 1;';

			$verfQuery = pg_query($con,$verfSql);

			$verfSerie = trim(pg_fetch_result($verfQuery,0,serie));

			if(empty($verfSerie)  AND empty($infoMessage)){
				$infoMessage = "<div class='alert alert-error'>
				    <h4>Não existe OS para esta série </h4>
			</div>";
			}
		}

			$verfSql2 = "SELECT
								tbl_cliente_garantia_estendida.numero_serie
							  FROM tbl_cliente_garantia_estendida
							  WHERE tbl_cliente_garantia_estendida.numero_serie = '{$varPost['serieForm']}'
							  AND fabrica = $login_fabrica LIMIT 1;";
			$verfQuery2 = pg_query($con,$verfSql2);

			if(pg_num_rows($verfQuery2) AND empty($infoMessage)){
				$verfSerie2 = trim(pg_fetch_result($verfQuery2,0,'numero_serie'));

				if(!empty($verfSerie2)){
					$infoMessage = "
					<div class='container'>
						<div class='alert alert-error'>
							    <h4>Serie já cadastrada.</h4>
						</div>
					</div>";
				}
			}

			if(empty($varPost['referencia']) AND empty($infoMessage)){
					$infoMessage = "
					<div class='container'>
						<div class='alert alert-error'>
							    <h4>Informe a referência do produto!</h4>
						</div>
					</div>";
					$campoErro['referencia'] = "error";
			}elseif(empty($infoMessage)){
				$sql = "SELECT
							tbl_produto.produto
						FROM
							tbl_produto
							JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
						WHERE
							tbl_produto.referencia = '{$varPost['referencia']}'
							AND tbl_produto.ativo;";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res) == 1 AND empty($infoMessage)){
					$varPost['produto']= pg_fetch_result($res,0,'produto');
				} else {
					$infoMessage = "
					<div class='container'>
						<div class='alert alert-error'>
							    <h4>Informe um produto válido!</h4>
						</div>
					</div>";
					$campoErro['produto'] = "error";
				}
			}

			if($login_fabrica == 153){
				if(empty($varPost['tempo']) AND empty($infoMessage)){
					$infoMessage = "
					<div class='container'>
						<div class='alert alert-error'>
							    <h4>Informe o tempo de garantia!</h4>
						</div>
					</div>";
					$campoErro['tempo'] = "error";
				}
			}


			if($login_fabrica == 3){
				if(empty($varPost['motivo']) AND empty($infoMessage)){
					$infoMessage = "
					<div class='container'>
						<div class='alert alert-error'>
							    <h4>Campo motivo em branco</h4>
						</div>
					</div>";
				}
			}
			if(empty($infoMessage)){

				foreach ($varPost as $key => $value) {

					if(empty($value)){
						$varPost[$key] = " ";

						$varPost[$key] = ($key == "dataNf") ? date("Y-m-d") : " ";
					}
				}

				if($login_fabrica == 153){
					$CampoCpf = "cpf, ";
					$valueCpf = " '$cpf' ,";
				}

				if(in_array($login_fabrica, [157])){
					$CampoCpf = "cpf, ";
					$valueCpf = " '$cpf' ,";
					
					$data_venda = formata_data($data_venda);

					$CampoDataVenda = "data_compra, ";
					$valueDataVenda = " $data_venda ";	
				}else{
					$valueDataVenda = $varPost['dataNf'];
				}				

				$insertSql = "INSERT INTO tbl_cliente_garantia_estendida (
						numero_serie,
						$CampoCpf
						motivo,
						admin,
						fabrica,
						garantia_mes,
						nome,
						endereco,
						numero,
						cep,
						cidade,
						revenda_nome,
						nota_fiscal,
						data_compra,
						estado,
						produto
					)
					VALUES (
						'".$varPost['serieForm']."',
						$valueCpf
						'".$varPost['motivo']."',
						'".$login_admin."',
						'".$login_fabrica."',
						'".$varPost['tempo']."',
						'".$varPost['nomeConsumidor']."',
						'".$varPost['enderecoConsumidor']."',
						'".$varPost['numeroConsumidor']."',
						'".$varPost['cepConsumidor']."',
						'".$varPost['cidadeConsumidor']."',
						'".$varPost['revendaNome']."',
						'".$varPost['notaFiscal']."',
						'".$valueDataVenda."',
						'".$varPost['estadoConsumidor']."',
						'".$varPost['produto']."'
					);";

					
				$queryInsert = pg_query($con, $insertSql);

				unset($varPost);
				$infoMessage = "
					<div class='container'>
						<div class='alert alert-success'>
							    <h4>Registro inserido com sucesso!</h4>
						</div>
					</div>";
			}
		} else {

			if($login_fabrica == 153){
				$CampoUpdate = " cpf = '$cpf',  ";
			}

			$sql = "
				UPDATE
				 	tbl_cliente_garantia_estendida
				SET
					garantia_mes = '{$varPost['tempo']}',
					motivo = '{$varPost['motivo']}',
					$CampoUpdate
					produto = '{$varPost['produto']}'
				WHERE
					cliente_garantia_estendida = {$cliente_garantia_estendida};";
			$res = pg_query($con, $sql);
			unset($varPost);
			$infoMessage = "
			<div class='container'>
			<div class='alert alert-success'>
				    <h4>Registro atualizado com sucesso!</h4>
			</div>
			</div>";
		}

	}

	if($_GET['cliente_garantia_estendida']){
		if($login_fabrica == 153){
			$campoCpf = " tbl_cliente_garantia_estendida.cpf, ";
		}

		if($login_fabrica == 157){
			$campoCpf 			= " tbl_cliente_garantia_estendida.cpf , ";
			$campoDataCompra	= " tbl_cliente_garantia_estendida.data_compra ,";
		}

		$sql = "
				SELECT
					tbl_cliente_garantia_estendida.cliente_garantia_estendida,
					tbl_cliente_garantia_estendida.numero_serie,
					tbl_cliente_garantia_estendida.motivo,
					$campoCpf
					$campoDataCompra
					tbl_cliente_garantia_estendida.garantia_mes,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_produto.produto
				FROM tbl_cliente_garantia_estendida
					LEFT JOIN tbl_produto ON (tbl_produto.produto = tbl_cliente_garantia_estendida.produto  )
				WHERE
					tbl_cliente_garantia_estendida.cliente_garantia_estendida = {$_GET['cliente_garantia_estendida']}
		;";

		$res = pg_query($con, $sql);

		if(pg_num_rows($res)){
			$cliente_garantia_estendida = pg_fetch_all($res);

			$varPost['cliente_garantia_estendida']	= $cliente_garantia_estendida[0]['cliente_garantia_estendida'];
			$varPost['motivo'] 						= $cliente_garantia_estendida[0]['motivo'];
			$varPost['referencia'] 					= $cliente_garantia_estendida[0]['referencia'];
			$varPost['descricao'] 					= $cliente_garantia_estendida[0]['descricao]'];
			$varPost['tempo'] 						= $cliente_garantia_estendida[0]['garantia_mes'];
			$varPost['referencia'] 					= $cliente_garantia_estendida[0]['referencia'];
			$varPost['descricao'] 					= $cliente_garantia_estendida[0]['descricao'];
			$varPost['serieForm'] 					= $cliente_garantia_estendida[0]['numero_serie'];
			$varPost['produto'] 					= $cliente_garantia_estendida[0]['produto'];
			$varPost['cpf']							= $cliente_garantia_estendida[0]['cpf'];
			if($login_fabrica == 157){
				$data_compra 	 	= $cliente_garantia_estendida[0]['data_compra'];
				if(strlen(trim($data_compra))>0){
					$varPost['data_compra'] = mostra_data($data_compra);
				}
			}
		}
	}

	if($login_fabrica == 91){
		$geraForm = geraFormulario("Cadastro de Produtos Inativados", $formNames, $varPost, $infoMessage, $login_fabrica, $campoErro);
	}else{
		$geraForm = geraFormulario("Cadastro de Garantia Estendida", $formNames, $varPost, $infoMessage, $login_fabrica, $campoErro);
	}

	echo $geraForm['form'];

	if($varPost['submitButton'] == "Consultar" || !empty($page)){

			$page = (empty($page)) ? 1 : $page;

			$regInicial = $registrosPag*($page-1);

			$regInicial = ($regInicial < 0) ? 1 : $regInicial;


			if(strlen(trim($varPost['serieForm']))>0){
				$cond_serie = (strlen($varPost['serieForm']) > 0) ? " AND tbl_cliente_garantia_estendida.numero_serie = '{$varPost['serieForm']}' " : "";
			}

			if($login_fabrica == 153){
				$cond_CPF = (strlen(trim($_POST['cpf'])) > 0) ? " AND tbl_cliente_garantia_estendida.cpf = '".trim($_POST['cpf'])."'" : "";

				$varPost['cpf'] = $_POST['cpf'];
			}

			if(strlen(trim($_POST['produto']))>0){
				$cond_produto .= " and tbl_cliente_garantia_estendida.produto = '".$_POST["produto"]."' ";	
			}
		
		$arrayDb = geraSQL('select',
							'tbl_cliente_garantia_estendida
								JOIN tbl_admin USING (admin)
								LEFT JOIN tbl_os ON tbl_os.serie = tbl_cliente_garantia_estendida.numero_serie
								LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_cliente_garantia_estendida.produto',
							'DISTINCT tbl_cliente_garantia_estendida.os, numero_serie, garantia_mes, motivo, tbl_admin.nome_completo, cliente_garantia_estendida,tbl_produto.referencia || \' - \' || descricao as descricao',
							'WHERE tbl_cliente_garantia_estendida.fabrica = '.$login_fabrica.$cond_serie.$cond_CPF.$cond_produto.' ORDER BY cliente_garantia_estendida DESC LIMIT '.$registrosPag.' OFFSET '.$regInicial.' ' );

		$qtTotal = geraSQL('select', 'tbl_cliente_garantia_estendida', 'COUNT(numero_serie) as total', 'WHERE fabrica = '.$login_fabrica.$cond_serie.'');

		echo geraTabela($arrayDb, $login_fabrica);

		if(!empty($qtTotal[0]['total'])){
			$qtPages = $qtTotal[0]['total']/$registrosPag;
			echo "<table class='table table-fixed' >";
			echo "<thead>";
			echo "<tr>";
			echo "<th>";
			if($page > 1 && (int)$qtPages > 0){
				echo "<a href=\"".$PHP_SELF."?page=".($page-1)."\"  style=\"font-size: 12px;\">Anterior</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			}
			for($countPages = 1; $countPages<=(int)$qtPages; $countPages++){

				if((int)$qtPages>0){
					if($page == $countPages){
						echo "&nbsp;&nbsp;<a href=\"#\" style=\"color: #000000; font-size: 12px;\">".$countPages."</a>&nbsp;&nbsp;";
					}else{
						echo "&nbsp;&nbsp;<a href=\"".$PHP_SELF."?page=".$countPages."\" style=\"font-size: 12px;\" >".$countPages."</a>&nbsp;&nbsp;";
					}
				}

			}
			if(($page+1) < ((int)$qtPages+1) && (int)$qtPages>0){
				if(empty($page)){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"".$PHP_SELF."?page=".($page+2)."\"  style=\"font-size: 12px;\">Próximo</a>";
				}else{
					echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"".$PHP_SELF."?page=".($page+1)."\" style=\"font-size: 12px;\">Próximo</a>";
				}
			}
			echo "</th>";
			echo "</tr>";
			echo "</thead>";
			echo "</table>";
		}

	}

}

###################################### FIM #####################################



main();
echo "<br /><br /><br />";
include "rodape.php";
?>