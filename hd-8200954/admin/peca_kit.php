<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/AuditorLog.php';

if($_POST['excluir_kit_peca'] == true){

	$peca = $_POST["peca"];
	$kit_peca = $_POST['kitpeca'];

	$sql = "DELETE from tbl_kit_peca_peca where peca = $peca and kit_peca = $kit_peca ";
	$res = pg_query($con, $sql);
	$msg_erro = pg_last_error($con);

	if(strlen(trim($msg_erro))==0){
		$retorno['erro'] = false;
		$retorno['kitpeca'] = $kit_peca;
		$retorno['peca'] = $peca;
		$retorno['msg'] = utf8_encode("Peça excluida com sucesso");
	}else{
		$retorno['erro'] = true;
		$retorno['msg'] = utf8_encode("Falha ao excluir peça");
	}

	echo json_encode($retorno);

	exit;
}


if ($_POST["btn_acao"] == "Gravar") {
	$acao 			 = $_POST['acao'];
	$peca       	 = $_POST['peca'];
	$peca_referencia = $_POST['peca_referencia'];
	$peca_descricao  = $_POST['peca_descricao'];
	$kit_peca		 = $_POST['kit_peca'];
    $tabela          = $_POST['tabela'];
	$reembolso 		 = $_POST['reembolso'];

	$PickListPeca = $_POST["PickListPeca"];

	if(count($PickListPeca)==0){
		$msg_erro .= "Informe as peças do kit";
	}

	if(strlen($msg_erro)==0){

		foreach($PickListPeca as $peca){
			$peca = explode(" | ", $peca);

			$sql = "SELECT * FROM tbl_kit_peca_peca WHERE peca = $peca[0] and kit_peca = $kit_peca";
			$res = pg_query($con, $sql);
			if(pg_num_rows($res)>0){
				continue;
			}

			//$options = "<option value=''></option>";
			$sqlPecaPeca = "INSERT INTO tbl_kit_peca_peca (kit_peca, peca, qtde) VALUES ($kit_peca, ".trim($peca[0]).", 1) ";
			$resPecaPeca = pg_query($con, $sqlPecaPeca);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro .= pg_last_error($con);
			}
		}

		if(strlen(trim(pg_last_error($con)))>0){
			$msg_erro .= "Erro ao gravar";
		}else{
			$ok = "Peças adicionadas ao kit.";
		}	
	}
}

$layout_menu = "cadastro";
$title = "CADASTRO DE KIT DE PEÇAS";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"shadowbox",
	"price_format",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    var login_fabrica = <?=$login_fabrica?>;
	$(function() {
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this), ["pesquisa_produto_acabado", "sem-de-para", "kit", "fora_kit"]);
		});

		$("#btn_click").click(function(){
			selItPeca();
			document.frm_cadastro.submit();
		});		

		$(".excluir").click(function(){
			var peca 	= $(this).data('peca');
			var kitpeca = $(this).data('kitpeca');
			var posicao = $(this).data('posicao');

			$.ajax({
                type: "POST",
                url: "peca_kit.php",
                data: {"excluir_kit_peca":true,"peca":peca, "kitpeca": kitpeca},
                cache: false,
                success: function(data){
                	var retorno = $.parseJSON(data);               	

                    if(retorno.erro == true){
                      alert(retorno.msg);
                    }else{
                    	alert(retorno.msg);
                    	$(".linha_"+posicao).remove();                    	
                    }
                }
            });

		});



	});

	function retorna_peca(retorno){
		$("#kit_peca").val(retorno.kit_peca);
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
    }

   	function retorna_peca_fora_kit(retorno){
		$("#peca_id_geral").val(retorno.peca);
        $("#peca_referencia_geral").val(retorno.referencia);
		$("#peca_descricao_geral").val(retorno.descricao);
    }


    function set_peca_input(peca, referencia){
    	$("#peca_referencia").val(peca);
    	$("#peca_descricao").val(referenciac);

    	 $('html, body').animate({
	     	scrollTop: $("#form_pesquisa").offset().top
	     }, 500);
    }

    var singleSelect = true;  // Allows an item to be selected once only
	var sortSelect = true;  // Only effective if above flag set to true
	var sortPick = true;  // Will order the picklist in sort sequence

	// Selection - invoked on submit
	function selItPeca() {
		var pickList = document.getElementById("PickListPeca");
		var pickOptions = pickList.options;
		var pickOLength = pickOptions.length;
		for (var i = 0; i < pickOLength; i++) {
			pickOptions[i].selected = true;
		}
	}
    function addItPeca() {
		if ($('#peca_referencia_geral').val()=='')
			return false;

		if ($('#peca_descricao_geral').val()=='')
			return false;

		var pickList = document.getElementById("PickListPeca");
		var pickOptions = pickList.options;
		var pickOLength = pickOptions.length;
		pickOptions[pickOLength] = new Option($('#peca_referencia_geral').val()+" - "+ $('#peca_descricao_geral').val());
		pickOptions[pickOLength].value =$('#peca_id_geral').val()+ " | " +  $('#peca_referencia_geral').val()+" | "+ $('#peca_descricao_geral').val();

		$('#peca_referencia_geral').val("");
		$('#peca_descricao_geral').val("");

		if (sortPick) {
			var tempText;
			var tempValue;
			// Sort the pick list
			while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
				tempText = pickOptions[pickOLength-1].text;
				tempValue = pickOptions[pickOLength-1].value;
				pickOptions[pickOLength-1].text = pickOptions[pickOLength].text;
				pickOptions[pickOLength-1].value = pickOptions[pickOLength].value;
				pickOptions[pickOLength].text = tempText;
				pickOptions[pickOLength].value = tempValue;
				pickOLength = pickOLength - 1;
			}
		}

		pickOLength = pickOptions.length;
		$('#peca_referencia_geral').focus();

	}

</script>

<style>
	.desc_peca{
		text-transform: uppercase;
	}

</style>

<?php
if (strlen($msg_erro) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=$msg_erro?></h4>
    </div>
<?php
}
?>

<?php
if (strlen($ok) > 0) {
?>
    <div class="alert alert-success">
		<h4><?=$ok?></h4>
    </div>
<?php
}
?>

<div class="alert alert-success" id="exclui" style="display:none;">
	<h4>Preço excluído com sucesso</h4>
</div>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_cadastro' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' id="form_pesquisa">
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>
	<div class="row-fluid">

		<div class="span2"></div>

		<div class='span4'>
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_referencia'>Ref. Peças Kit</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						
						<input type="hidden" id="kit_peca" name="kit_peca" class='span12' maxlength="20" value="<? echo $kit_peca ?>" >


						<input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" pesquisa_produto_acabado="true" sem-de-para="true" kit="true" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_descricao'>Descrição Peça Kit</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" pesquisa_produto_acabado="true" sem-de-para="true"  kit="true" />
					</div>
				</div>
			</div>
		</div>

		<div class="span2"></div>

	</div>

	<div class="row-fluid">

		<div class="span2"></div>

		<div class='span4'>
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_referencia'>Ref. Peças</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="hidden" id="peca_id_geral" name="peca_id_geral" class='span12' maxlength="20" value="<? echo $peca_id_geral ?>" >
						<input type="text" id="peca_referencia_geral" name="peca_referencia_geral" class='span12' maxlength="20" value="<? echo $peca_referencia_geral ?>" >
						

						<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" pesquisa_produto_acabado="true" sem-de-para="true" fora_kit="true" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'>
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_descricao'>Descrição Peça</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="peca_descricao_geral" name="peca_descricao_geral" class='span12' value="<? echo $peca_referencia_geral ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" pesquisa_produto_acabado="true" sem-de-para="true"  fora_kit="true" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2" style="text-align: right;"><br>
			<button type="button" class="btn btn-success adicionar" onclick="addItPeca()">Adicionar</button>
		</div>

		<div class="span2"></div>

	</div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span8" style="text-align: center">
			<label class='control-label' for='peca_descricao'>Lista de Peças</label><br>

			<select multiple size='5' id="PickListPeca" name="PickListPeca[]" class='span12'>

			</select>
			<br>
			<!-- <button type="button" class="btn btn-danger">Remover</button> -->
		</div>		
		<div class="span2"></div>
	</div>
	<p>
		<br/>
		<input type="hidden" name="btn_acao" value="Gravar">
		<button type="button" id="btn_click" class="btn btn-primary">Gravar</button>
	</p>

	<br />
</form>

<table class="table table-striped table-bordered table-hover table-lupa" >
	<thead>
		<tr class='titulo_coluna'>
			<td>Referência</td>
			<td>Descrição</td>
			<td>Ações</td>
		</tr>
	</thead>
	<tbody>

		<?php 
			$sqlKits = "SELECT referencia, descricao, peca, kit_peca from tbl_kit_peca where fabrica = $login_fabrica ";
			$reskits = pg_query($con, $sqlKits);
			
			$posicao = 0;

			for($i=0; $i<pg_num_rows($reskits); $i++){
				$referencia = pg_fetch_result($reskits, $i, 'referencia');
				$descricao = pg_fetch_result($reskits, $i, 'descricao');
				$peca = pg_fetch_result($reskits, $i, 'peca');
				$kit_peca_id = pg_fetch_result($reskits, $i, 'kit_peca');				

				$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_kit_peca_peca.peca FROM tbl_kit_peca_peca 
								join tbl_peca on tbl_peca.peca = tbl_kit_peca_peca.peca and tbl_peca.fabrica = $login_fabrica  WHERE kit_peca = $kit_peca_id ";
				$res = pg_query($con, $sql);
				if(pg_num_rows($res)>0){
					echo "<tr>";
						echo "<td style='background-color:#cccccc !important'>$referencia</td>";
						echo "<td style='background-color:#cccccc !important'>$descricao</td>";
						echo "<td style='background-color:#cccccc !important'></td>";
					echo "<tr>";
				}

				for($kit=0; $kit<pg_num_rows($res); $kit++){
					$referenciaPecaKit = pg_fetch_result($res, $kit, 'referencia');
					$descricaoPecaKit = pg_fetch_result($res, $kit, 'descricao');
					$peca = pg_fetch_result($res, $kit, 'peca');

					echo "<tr class='linha_$posicao'>";
						echo "<td>$referenciaPecaKit</td>";
						echo "<td>$descricaoPecaKit</td>";
						echo "<td><center><button type='button' class='btn btn-danger excluir' data-peca='$peca' data-kitPeca='$kit_peca_id' data-posicao='$posicao' >Excluir</button> </center></td>";
					echo "</tr>";

					$posicao++;
				}				
			}
		?>



		<tr>
			<td></td>
			<td></td>
		</tr>
	</tbody>
</table>



<?php
			
echo "</div>";
include "rodape.php";
?>
