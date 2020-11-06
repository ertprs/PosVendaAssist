<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

 
if ($_POST["btn_acao"] == "submit") {
 
    $postos_selecionados = implode("','", $_POST['PickList']);  //text area
    $posto_km 		     = str_replace(',','.', $_POST['preco']); // valor_km (preco)
    $todos_postos 	     = $_POST['todos_postos']; //checkbox (todos_postos)

 
    //Valor KM
	if (empty($posto_km) || $posto_km == NULL) {
		$msg_erro["msg"][] = "Preencha os campos obrigatórios.";
		$msg_erro['campos'][]   = "preco";
	}

	//Checkbox
	if (empty($todos_postos) && count($_POST['PickList']) == 0) {
		$msg_erro["msg"][] = "Selecione os posto(s).";
		$msg_erro['campos'][]   = "codigo_posto";
	}
	
	if (count($msg_erro) == 0) {

		 //salva valor_km para todos os postos se o checkbox estiver marcado.
		 if (!empty($todos_postos)) {

		   $sql = "UPDATE tbl_posto_fabrica SET valor_km = {$posto_km} WHERE fabrica = $login_fabrica";
		   $res = pg_query($con,$sql);
		   
		 
		 //sala valor_km somente para os postos selecionados manualmente.  
		 } else {
		     
		   $sql_postos = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto IN ('$postos_selecionados') AND
		   fabrica=$login_fabrica";
		  

		   $res_posto = pg_query($con,$sql_postos); 

		   while ($dados = pg_fetch_array($res_posto)) {
		      	  $array_posto[] = $dados['posto'];
		   }
		      $postos_selecionados_2 = implode(",", $array_posto);

		    
				if ($login_fabrica == 30) {
					//verifica se existe campo 'valor_km_fixo'  dentro do array parametros_adicionais
				     $verificacampo =  "SELECT  parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND json_field('valor_km_fixo', parametros_adicionais) <> '' AND posto IN ($postos_selecionados_2)";
				     $res = pg_query($con,$verificacampo);
				     
				    //senão existir campo dentro do array será preciso inserir campo.
				    //atualiza todos postos selecionados com valor_km inserido.
				    if (pg_num_rows($res) == 0) {
				    	  			    	   
					     	$parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'), true);
					     	$parametros_adicionais['valor_km_fixo'] = $posto_km;
					     	$parametros_adicionais = json_encode($parametros_adicionais);

				            $sql = "UPDATE tbl_posto_fabrica SET parametros_adicionais = '$parametros_adicionais' WHERE posto IN ($postos_selecionados_2) AND fabrica = $login_fabrica";
				     	    $res = pg_query($con,$sql);
				     	  
				    //se campo existir (valor_km_fixo) então salvar novo valor dentro do array do campo parametros_adicionais.    
				    //para todos os postos selecionados.
	                }  else {
	                	 
			  				$sql = "UPDATE tbl_posto_fabrica SET parametros_adicionais = jsonb_set(parametros_adicionais::jsonb,'  {valor_km_fixo}','\"$posto_km \"') WHERE posto IN ($postos_selecionados_2) AND fabrica = $login_fabrica";
					        $res = pg_query ($con,$sql); 
					       
		   		 	    }		
				}		      	

				//senão for fábrica 30 então insere para outras fabricas.
	    	    else {
	    	    	 
			  	      $sql = "UPDATE tbl_posto_fabrica SET valor_km = {$posto_km} WHERE fabrica = $login_fabrica AND posto IN ($postos_selecionados_2)";
			 	  	  $res = pg_query ($con,$sql);
			 	 	}   
		}

		$msg_sucesso = 'Gravado com sucesso.';
		$posto_km = "";


	}	 
}

$layout_menu = "gerencia";
$title = "Manutenção KM posto";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"price_format"
);

include("plugin_loader.php");
?>
<script type='text/javascript' src='js/fckeditor/fckeditor.js'></script>
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

		    $("#preco").priceFormat({
                prefix: '',
                centsSeparator: ',',
                thousandsSeparator: '.'
            });
	});

	function mostra_div () {
    	if ($("#check_postos").is(":checked")) {
    		$("#campos_postos").hide();
    	} else {
    		$("#campos_postos").show();
    	}
	}

	///////////////////////////////////////////////////////////

	var singleSelect = true;  // Allows an item to be selected once only
	var sortSelect = true;  // Only effective if above flag set to true
	var sortPick = true;  // Will order the picklist in sort sequence

	function delIt() {
	  var pickList = document.getElementById("PickList");
	  var pickIndex = pickList.selectedIndex;
	  var pickOptions = pickList.options;
	  while (pickIndex > -1) {
	    pickOptions[pickIndex] = null;
	    pickIndex = pickList.selectedIndex;
	  }
	}

	// Initialise - invoked on load
	function initIt() {
	  var pickList = document.getElementById("PickList");
	  var pickOptions = pickList.options;
	  pickOptions[0] = null;  // Remove initial entry from picklist (was only used to set default width)
	}

	// Selection - invoked on submit
	function selIt(btn) {
		var pickList = document.getElementById("PickList");
		if (pickList == null) return true;
		var pickOptions = pickList.options;
		var pickOLength = pickOptions.length;
	/*	if (pickOLength < 1) {
			alert("Nenhuma produto selecionado!");
			return false;
		}*/
		for (var i = 0; i < pickOLength; i++) {
			pickOptions[i].selected = true;
		}
	/*	return true;*/
	}

	function addIt() {

		var fabrica = <?=$login_fabrica?>;

		if ($('#codigo_posto').val()=='')
			return false;

		if ($('#descricao_posto').val()=='')
			return false;

		var pickList = document.getElementById("PickList");
		var pickOptions = pickList.options;
		var pickOLength = pickOptions.length;

		pickOptions[pickOLength] = new Option($('#codigo_posto').val()+" - "+ $('#descricao_posto').val());
		pickOptions[pickOLength].value = $('#codigo_posto').val();

		$('#codigo_posto').val("");
		$('#descricao_posto').val("");

		if (sortPick) {
			var tempText;
			var tempValue;
			// Sort the pick list
			while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
				tempText = pickOptions[pickOLength-1].text;
				tempValue = pickOptions[pickOLength-1].value;
				pickOptions[pickOLength-1].text = pickOptions[pickOLength].text;
				pickckOptions[pickOLength-1].value = pickOptions[pickOLength].value;
				pickOptions[pickOLength].text = tempText;
				pickOptions[pickOLength].value = tempValue;
				pickOLength = pickOLength - 1;
			}
		}

		pickOLength = pickOptions.length;
		$('#codigo_posto').focus();

	}

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
	} else if (!empty($msg_sucesso)) { ?>
			<div class="alert alert-success">
			<h4><?= $msg_sucesso ?></h4>
			</div>
		<?php
	  }
?>

 <div class="row">
	<b class="obrigatorio pull-right">* Campos obrigatórios </b>
 </div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
		<div class='titulo_tabela '> Parâmetros de Pesquisa</div>
		<br/>


		<div class="row-fluid">
			<div class='span2'></div>
			<div class='span4'>
			   	<div class='control-group <?=(in_array("preco", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Valor KM</label>
					<div class='controls controls-row'>
											
						<div class='span12 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="preco" id="preco" price="true" size="12" maxlength="10" class='span12'value="<?= $posto_km ?>" >
						</div>
					</div>
				</div>
			</div>


			<div class="span2 tac">
				<label>Todos os Postos</label>
				<br>
				<?php if($todos_postos == 't'){
					$checked = " checked ";
				}else{
					$checked = "  ";
				}

				?>
				<input type="checkbox" id="check_postos" name="todos_postos" value="t" onclick="mostra_div();" <?=$checked ?>>
			</div>
		</div>

		<div class="row-fluid" id="campos_postos">
			<div id='id_multi'>

		    	<div class='row-fluid'>

			        <div class='span2'></div>

			        <div class='span2'>
			            <div class='control-group <?=(in_array("codigo_posto", $msg_erro["campos"])) ? "error" : ""?>'>
			                <label class='control-label' for='codigo_posto'><?php echo traduz("Código Posto"); ?></label>
			                <div class='controls controls-row'>
			                    <div class='span10 input-append'>
			                    	<h5 class='asteristico'>*</h5> 
			                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
									<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
									<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
			                    </div>
			                </div>
			            </div>
			        </div>
			        <div class='span4'>
			            <div class='control-group <?=(in_array("preco", $msg_erro["campos"])) ? "error" : ""?>'>
			                <label class='control-label' for='produto_descricao_multi'><?php echo traduz("Descrição Posto"); ?></label>
			                <div class='controls controls-row'>
			                    <div class='span11 input-append'>
			                    	<h5 class='asteristico'>*</h5>
			                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
									<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
									<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
			                    </div>
			                </div>
			            </div>
			        </div>

			        
			        <div class='span2'>
			        	<label>&nbsp;</label>
			        	<input type='button' name='adicionar' id='adicionar' value='Adicionar' class='btn btn-success' onclick='addIt();' style="width: 100%;">
			        </div>

			       
			    </div>

			  
			    <p class="tac">
			    	<?php echo ("(Selecione o produto e clique em <strong>Adicionar</strong>)"); ?>
			    </p>

			    <div class='row-fluid'>

			        <div class='span2'></div>
 
 			        <div class='span8'>
			        	<select multiple size='5' id="PickList" name="PickList[]" class='span12'></select>
			        </div>
			    
				    </div>
				    <br />
				    <div class="row-fluid">
				    	<!-- botao remover -->
						<p class="tac">
							<input type="button" value="Remover" onclick="delIt();" class='btn btn-danger' style="width: 126px;">
						</p>
				    </div>
				</div>

			</div>
	
			<p><br/>
				<button class='btn' id="btn_acao" type="button"  onclick="selIt();submitForm($(this).parents('form'));">Gravar</button>
				<input type='hidden' id="btn_click" name='btn_acao' value='' />
			</p><br/>
	</form>
	</div>

	<script type="text/javascript">
		$(document).ready(function() {
    		mostra_div();
		});
	</script>
<?php
include 'rodape.php';?>
