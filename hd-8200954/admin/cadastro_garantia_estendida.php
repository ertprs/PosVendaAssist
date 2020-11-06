

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
$title = "CADASTRO DE GARANTIA ESTENDIDA";
include 'cabecalho.php';
include "javascript_calendario.php";

########################### FUNÇÕES INTERNAS DA PÁGINA #########################

function geraFormulario($formTitle = NULL, $formNames = NULL, $postValues = NULL, $infoMessage = NULL){

	$form = NULL;
	global $login_fabrica;

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


	$form .= (empty($infoMessage)) ? "" : $infoMessage;

	// $form .= "<table align=\"center\" width=\"700px\" style=\"background-color: #D9E2EF; font-family: Arial, san-serief; font-size: 12px;\">";
	// $form .= "<tr>";
	// $form .= "<td colspan=\"3\" align=\"center\" style=\"background-color:#596D9B; color: #FFFFFF; font-size: 14px;font:bold;\">";
	// $form .= "<strong>".$formTitle."</strong>";
	// $form .= "</td>";
	// $form .= "</tr>";
	// $form .= "<tr>";
	// $form .= "<td>";

	$disabled_edit = (intval($postValues['cliente_garantia_estendida']) > 0) ? " disabled = 'disabled' " : "";

	$form .= "

		<form method=\"post\" action='cadastro_garantia_estendida.php'>

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
			<table class='formulario' width='700px' cellspacing='1' cellpadding='3'>
				<tr>
					<td colspan='4' class='titulo_tabela'>{$formTitle}</td>
				</tr>
				<tr>
					<td width='100px'>&nbsp;</td>
					<td width='250px'>&nbsp;</td>
					<td width='250px'>&nbsp;</td>
					<td width='100px'>&nbsp;</td>
				</tr>
				<tr>
					<td>&nbsp;</td>";
		
		if($login_fabrica == 153){
			$form .= "<td> 
							CPF<br />
							<input type='text' name='CPF' value='{$postValues['cpf']}' class='frm' class='' maxlength='11' id='CPF' style='width:200px;'>	
						</td>";
		}else{
			$form .= "<td>
							Número da Série<br />
							<input type=\"text\" class=\"frm\" name=\"".$formNames['serieForm']."\"  id=\"".$formNames['serieForm']."\" value='{$postValues[$formNames['serieForm']]}' style=\"width:200px;\" {$disabled_edit} />
						</td>";
		}

		$form .= "<td> 
						Tempo<br />
						<select class=\"frm\" name=\"".$formNames['tempo']."\"  style=\"width:200px; \" >
							<option ".$mes1Select." value = 1>1 mês</option>
							<option ".$mes2Select." value = 2>2 meses</option>
							<option ".$mes3Select." value = 3>3 meses</option>
							<option ".$mes4Select." value = 4>4 meses</option>
							<option ".$mes4Select." value = 5>5 meses</option>
						</select>
					</td>
					<td>&nbsp;</td>	
				</tr>
				<tr>
					<td>&nbsp;</td>	
					<td>
						Referência<br />
						<input type=\"text\" class=\"frm\" id=\"".$formNames['referencia']."\" name=\"".$formNames['referencia']."\" value=\"".$postValues[$formNames['referencia']]."\"  style=\"width:200px; \" {$disabled_edit} />
						<img src=\"imagens/lupa.png\" id=\"LupaReferencia\" border='0' align='absmiddle' style='cursor: pointer'>
					</td>
					<td>
						Descrição<br />
						<input type=\"text\" class=\"frm\" id=\"".$formNames['descricao']."\" name=\"".$formNames['descricao']."\" value=\"".$postValues[$formNames['descricao']]."\" style=\"width:200px; \" {$disabled_edit} />
						<img src=\"imagens/lupa.png\" id=\"LupaDescricao\" border='0' align='absmiddle' style='cursor: pointer'>
					</td>
					<td>&nbsp;</td>	
				</tr>";
		if($login_fabrica <> 153){		
			$form .= "<tr>
						<td>&nbsp;</td>	
						<td colspan='2'>
							Motivo<br />
							<textarea style=\"width: 455px;\" class=\"frm\" name=\"".$formNames['motivo']."\">".$postValues[$formNames['motivo']]."</textarea>
						</td>
						<td>&nbsp;</td>	
					</tr>";
		}
		$form .= "<tr>
					<td>&nbsp;</td>	
					<td  colspan='2' style='padding: 20px 10px;'>
						<input type=\"button\" class='btn_limpar' value=\"Limpar\"  style=\"margin-right:105px;\"/>
						<input type=\"submit\" name=\"".$formNames['submitButton']."\" value=\"Gravar\" />
						<input type=\"submit\" name=\"".$formNames['submitButton']."\" value=\"Consultar\" style=\"margin-left:105px;\"  />
					</td>
					<td>&nbsp;</td>	
				</tr>	
			</table>
		</form>
	";

	// $form .= "</td>";
	// $form .= "</tr>";
	// $form .= "</table>";
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

function geraTabela($Array){

	$tabela = NULL;

	global $login_fabrica;

	$qtArray = count($Array);

	if(empty($Array[0])){
			$tabela .= "<table cellspacing='1' cellpadding='2' align=\"center\" width=\"700px\" style=\"background-color: #D9E2EF; font-family: Arial, san-serief; font-size: 12px;\">";
			$tabela .= "<tr>";
			$tabela .= "<td colspan=\"3\" align=\"center\" style=\"background-color:#596D9B; color: #FFFFFF; font-size: 14px;font:bold;\">";
			$tabela .= "<strong>Consulta de Todos Registros</strong>";
			$tabela .= "</td>";
			$tabela .= "</tr>";
			$tabela .= "<tr>";
			$tabela .= "<td>";
			$tabela .= "Não existem registros!";
			$tabela .= "</td>";
			$tabela .= "</tr>";
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
		$tabela .= "<table cellspacing='1' cellpadding='2' border='0' class='tabela' bgcolor='#333'  align=\"center\" width=\"700\" >";
			$tabela .= "<tr class='titulo_coluna'>";
			$tabela .= "<td><input  type=\"checkbox\" id=\"selecionarTodos\" /></td>";

					foreach ($Array[0] as $key => $value) {
					
						$key = ($key == "numero_serie") ? "Nº Série" : $key;
						
						$key = ($key == "garantia_mes") ? "Garantia" : $key;
						$key = ($key == "nome_completo") ? "Administrador" : $key;
						$key = ($key == "motivo") ? "Motivo" : $key;
						$key = ($key == "descricao") ? "Produto" : $key;

						if(!in_array($key, array('os','cliente_garantia_estendida'))) {
							if(($key == "Nº Série" or $key == "Motivo") and $login_fabrica == 153){
 								continue;
							}else{
								$tabela .= "<td>{$key}</td>";
							}
						}
					}
					$tabela .= "<td>Ações</td>";
				$tabela .= "</tr>";

				for($countArray = 0; $qtArray>$countArray;$countArray++){
					$cor = ($countArray % 2) ? "#F7F5F0" : "#F1F4FA";

					// Não deixar excluit se já tiver OS 
					$disabled_delete = (!empty($Array[$countArray]['os'])) ? " disabled = 'disabled' " : "";
					
					$tabela .= "<tr class='table_line' bgcolor='{$cor}'>";
						$tabela .= "<td>";
							if(trim(empty($disabled_delete)))
								$tabela .= "<input type=\"checkbox\" name=\"id_cliente_garantia_estendida\" id=\"id_garantia_estendida\" value=\"".$Array[$countArray]['cliente_garantia_estendida']."\" {$disabled_delete} />";
							else
								$tabela .= "&nbsp;";
						$tabela .="</td>";
							foreach ($Array[$countArray] as $key => $value) {
								if($key == "garantia_mes"){
									switch ($value) {
										case $value == 1 : $value = $value." mês"; break;
										case $value > 1 : $value = $value." meses"; break;
										default: $value = $valuebreak;
									}
								}

								if(!in_array($key, array('os','cliente_garantia_estendida'))) {
									if(($key == "numero_serie" or $key == 'motivo') and $login_fabrica == 153){
		 								continue;
									}else{
										$tabela .= "<td>".$value."</td>";
									}
								}
							}



						$tabela .= "<td nowrap rel='{$Array[$countArray]['cliente_garantia_estendida']}'>
										<input type='button' class='btn_edit' value='Editar' />
										<input type='button' class='btn_apagar' value='Apagar' {$disabled_delete} />
									</td>";
					$tabela .= "</tr>";
				
				}


			$tabela .= "<tr  class='titulo_coluna' >";
				$tabela .= "<td  align='left' colspan='7'>";
					$tabela .= "<select>
									<option>Excluir</option>
								</select>
								<input type=\"submit\" value=\"Gravar\" />";
				$tabela .= "</td>";
			$tabela .= "</tr>";
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

					window.location.href =  'cadastro_garantia_estendida.php?cliente_garantia_estendida='+cliente_garantia_estendida;
				});

				$('.btn_limpar').bind('click', function(){
					var cliente_garantia_estendida = $(this).parent().attr('rel');

					window.location.href =  'cadastro_garantia_estendida.php';
				});

				$('.btn_apagar').bind('click', function(){
					var cliente_garantia_estendida = $(this).parent().attr('rel');
					var tr = $(this).parent().parent();

					if(cliente_garantia_estendida.length > 0){
						$.ajax({
						  	type: 'POST',
						  	url: 'cadastro_garantia_estendida.php',
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
						content : 'produto_pesquisa_nv.php?'+tipo+'='+produto.val(),
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
	$registrosPag = 10;
	

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
			'cliente_garantia_estendida' => 'cliente_garantia_estendida'
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

					$infoMessage = "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: red; font-size:12px;\"><tr><td><strong>A garantia '{$verfNumeroSerie}' já foi utilizada e por isso não pode ser excluída!</strong></td></tr></table>";
					$erroQuery = TRUE;
				}
			}
			
			$cmdDelete .= ") AND fabrica = ".$login_fabrica." AND os IS NULL;";
			
			//$cmdDelete;

			if($erroQuery == FALSE){
				$queryDelete = pg_query($con, $cmdDelete);
				if(($qtPost-1) == 1){
					$infoMessage = "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: #008000; font-size:12px;\"><tr><td><strong> ".($qtPost-1)." registro excluído com sucesso!</strong></td></tr></table>";
				}else{
					$infoMessage = "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: #008000; font-size:12px;\"><tr><td><strong> ".($qtPost-1)." registros excluídos com sucesso!</strong></td></tr></table>";
				}

			}
			
		}else{
			$infoMessage = "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: red; font-size:12px;\"><tr><td><strong>Você não selecionou campo para exclusão!</strong></td></tr></table>";
		}

	}

	$varPost = getPost($formNames);
	if($varPost['submitButton'] == "Limpar"){
		unset($varPost);
	}

	if($varPost['submitButton'] == "Gravar"){
		$cliente_garantia_estendida = (int) $varPost['cliente_garantia_estendida'];

		$infoMessage = NULL;

		if($cliente_garantia_estendida == 0){

			if($login_fabrica == 153){
				$cpf 						= $_POST["CPF"];		

				if(strlen(trim($cpf))==0){
					$infoMessage .= "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: red; font-size:12px;\"><tr><td><strong>O Campo CPF deve ser preenchido.</strong></td></tr></table>";
				}
				if(empty($varPost['tempo']) || $varPost['tempo'] ==  " "){
					$infoMessage .= "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: red; font-size:12px;\"><tr><td><strong>O Campo tempo deve ser preenchido.</strong></td></tr></table>";
				}

				if((strlen(trim($varPost['referencia']))==0) and (strlen(trim($varPost['descricao']))==0) ){
					$infoMessage .= "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: red; font-size:12px;\"><tr><td><strong>Informe um Produto.</strong></td></tr></table>";
				}


			}else{
				if(empty($varPost['serieForm']) || $varPost['serieForm'] ==  " "){
					$infoMessage = "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: red; font-size:12px;\"><tr><td><strong>Campo numero de série em branco</strong></td></tr></table>";
				}
			}			

			$verfSql = 'SELECT
						tbl_os.serie
					FROM tbl_os
					WHERE tbl_os.fabrica = \''.$login_fabrica.'\'
					AND tbl_os.serie = \''.$varPost['serieForm'].'\' LIMIT 1;';
						
			$verfQuery = pg_query($con,$verfSql);

			$verfSerie = trim(pg_fetch_result($verfQuery,0,serie));

			if(empty($verfSerie)  AND empty($infoMessage) and $login_fabrica <> 153){
				$infoMessage = "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: red; font-size:12px;\"><tr><td><strong>
				Não existe OS para esta série
				</strong></td></tr></table>";
			}

			$verfSql2 = "SELECT
								tbl_cliente_garantia_estendida.numero_serie
							  FROM tbl_cliente_garantia_estendida
							  WHERE tbl_cliente_garantia_estendida.numero_serie = '{$varPost['serieForm']}' LIMIT 1;";
			$verfQuery2 = pg_query($con,$verfSql2);

			if(pg_num_rows($verfQuery2) AND empty($infoMessage)){
				$verfSerie2 = trim(pg_fetch_result($verfQuery2,0,'numero_serie'));

				if(!empty($verfSerie2)){
					$infoMessage = "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: red; font-size:12px;\"><tr><td><strong>
					Serie já cadastrada.
					</strong></td></tr></table>";
				}
			}

			if(empty($varPost['referencia']) AND empty($infoMessage)){
				$infoMessage = "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: red; font-size:12px;\"><tr><td><strong>
					Informe a referência do produto!
					</strong></td></tr></table>";
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
					$infoMessage = "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: red; font-size:12px;\"><tr><td><strong>
						Informe um produto válido!
						</strong></td></tr></table>";
				}
			}
			
			if(empty($varPost['motivo']) AND empty($infoMessage) and $login_fabrica <> 153){
				$infoMessage = "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: red; font-size:12px;\"><tr><td><strong>Campo motivo em branco</strong></td></tr></table>";
			}

			if(empty($infoMessage)){

				foreach ($varPost as $key => $value) {

					if(empty($value)){
						$varPost[$key] = " ";

						$varPost[$key] = ($key == "dataNf") ? date("Y-m-d") : " ";
					}
				}

				if($login_fabrica == 153){
					$campo_positron = ", cpf ";
					$values_positron = ", '".$cpf."'";	
				}
				


				$insertSql = "INSERT INTO tbl_cliente_garantia_estendida (
						numero_serie,
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
						$campo_positron
					) 
					VALUES (
						'".$varPost['serieForm']."',
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
						'".$varPost['dataNf']."',
						'".$varPost['estadoConsumidor']."',
						'".$varPost['produto']."'
						$values_positron
					);";
				$queryInsert = pg_query($con, $insertSql);
				unset($varPost);
				$infoMessage = "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: #008000; font-size:12px;\"><tr><td><strong>Registro inserido com sucesso!</strong></td></tr></table>";
			}
		} else {
			if($login_fabrica == 153){
				$cpf 						= $_POST["CPF"];	
				$update_position = ", cpf = '{$cpf}' ";
			}
			$sql = "
				UPDATE 
				 	tbl_cliente_garantia_estendida
				SET
					garantia_mes = '{$varPost['tempo']}',
					motivo = '{$varPost['motivo']}',
					produto = '{$varPost['produto']}'
					$update_position
				WHERE 
					cliente_garantia_estendida = {$cliente_garantia_estendida};";
			$res = pg_query($con, $sql);
			unset($varPost);
			$infoMessage = "<table align=\"center\"  width=\"700px\" style=\"color: #FFFFFF; background-color: #008000; font-size:12px;\"><tr><td><strong>Registro atualizado com sucesso!</strong></td></tr></table>";
		}
		
		
	}

	if($_GET['cliente_garantia_estendida']){

		if($login_fabrica == 153){
			$campos_positron = ', tbl_cliente_garantia_estendida.cpf ';
		}
		
		$sql = "
				SELECT 
					tbl_cliente_garantia_estendida.cliente_garantia_estendida,
					tbl_cliente_garantia_estendida.numero_serie,
					tbl_cliente_garantia_estendida.motivo,
					tbl_cliente_garantia_estendida.garantia_mes,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_produto.produto
					$campos_positron
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

			if($login_fabrica == 153){
				$varPost['cpf'] 					= $cliente_garantia_estendida[0]['cpf'];
			}
		}
	}

	$geraForm = geraFormulario("Cadastro de Garantia Estendida", $formNames, $varPost, $infoMessage);
	
	echo $geraForm['form'];

	if($varPost['submitButton'] == "Consultar" || !empty($page)){

			$page = (empty($page)) ? 1 : $page;

			$regInicial = $registrosPag*($page-1);

			$regInicial = ($regInicial < 0) ? 1 : $regInicial;

			$cond_serie = (strlen($varPost['serieForm']) > 0) ? " AND tbl_cliente_garantia_estendida.numero_serie = '{$varPost['serieForm']}' " : "";
			

		$arrayDb = geraSQL('select', 
							'tbl_cliente_garantia_estendida 
								JOIN tbl_admin USING (admin) 
								JOIN tbl_os ON tbl_os.serie = tbl_cliente_garantia_estendida.numero_serie 
								LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_cliente_garantia_estendida.produto', 
							'DISTINCT tbl_cliente_garantia_estendida.os, numero_serie, garantia_mes, motivo, tbl_admin.nome_completo, cliente_garantia_estendida,tbl_produto.referencia || \' - \' || descricao as descricao', 
							'WHERE tbl_cliente_garantia_estendida.fabrica = '.$login_fabrica.$cond_serie.' ORDER BY cliente_garantia_estendida DESC LIMIT '.$registrosPag.' OFFSET '.$regInicial.' ' );	

		$qtTotal = geraSQL('select', 'tbl_cliente_garantia_estendida', 'COUNT(numero_serie) as total', 'WHERE fabrica = '.$login_fabrica.$cond_serie.'');	

		echo geraTabela($arrayDb);

		if(!empty($qtTotal[0]['total'])){
			$qtPages = $qtTotal[0]['total']/$registrosPag;
			echo "<table cellspacing='1' cellpadding='2' align=\"center\" width=\"700px\" >";
			echo "<tr>";
			echo "<td>";
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
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}


	}

}

###################################### FIM #####################################



main();
echo "<br /><br /><br />";
include "rodape.php";
?>