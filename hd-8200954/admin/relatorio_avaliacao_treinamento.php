<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include '../ajax_cabecalho.php';
$admin_privilegios="info_tecnica";
include 'autentica_admin.php';
$layout_menu = "tecnica";
$title = "TREINAMENTOS REALIZADOS";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "ajaxform",
    "dataTable"
);

include "plugin_loader.php";
include "javascript_pesquisas.php";


$sql = "SELECT treinamento, titulo, tbl_cidade.nome as cidade FROM tbl_treinamento LEFT JOIN tbl_cidade USING(cidade) where fabrica = $login_fabrica AND data_finalizado IS NOT NULL ORDER BY data_finalizado DESC ";
$res_treinamentos = pg_query($con,$sql);

?>

<form class="form-search form-inline tc_formulario" name="frm_relatorio" id="frm_relatorio">
    <div id="alertaErro" class="alert alert-error" style="display: none;"><h4></h4></div>
    <div id="Alerta" class="alert" style="display: none;"><h4></h4></div>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <div class="row-fluid">
    	<div class="span12 tac">
    		<p>Busca por <b>Estado</b> ou por <b>Treinamento</b></p>
    	</div>
    </div>
    <div class="row-fluid">
    	<div class="span2"></div>
    	<div class="span8">
	        <div class="control-group ">
	            <label class="control-label" for="listaEstado">Estado</label>
	            <div class="controls controls-row">
	                <div class="">
	                    <select id="listaEstado" name="listaEstado" class="span12 ">
							<option value="">Selecione um estado</option>
							<option value="AC">Acre - AC</option>
							<option value="AL">Alagoas - AL</option>
							<option value="AP">Amapá - AP</option>
							<option value="AM">Amazonas - AM</option>
							<option value="BA">Bahia - BA</option>
							<option value="CE">Ceará - CE</option>
							<option value="DF">Distrito Federal - DF</option>
							<option value="ES">Espirito Santo - ES</option>
							<option value="GO">Goiás - GO</option>
							<option value="MA">Maranhão - MA</option>
							<option value="MT">Mato Grosso - MT</option>
							<option value="MS">Mato Grosso do Sul - MS</option>
							<option value="MG">Minas Gerais - MG</option>
							<option value="PA">Pará - PA</option>
							<option value="PB">Paraíba - PB</option>
							<option value="PR">Paraná - PR</option>
							<option value="PE">Pernambuco - PE</option>
							<option value="PI">Piaui - PI</option>
							<option value="RJ">Rio de Janeiro - RJ</option>
							<option value="RN">Rio Grande do Norte - RN</option>
							<option value="RS">Rio Grande do Sul - RS</option>
							<option value="RO">Rondônia - RO</option>
							<option value="RR">Roraima - RR</option>
							<option value="SC">Santa Catarina - SC</option>
							<option value="SP">São Paulo - SP</option>
							<option value="SE">Sergipe - SE</option>
							<option value="TO">Tocantins - TO</option>
						</select>
	                    
	                    
	                </div>
	            </div>
	        </div>
	    </div>	    	    
    </div>    
    <div class="row-fluid">
    	<div class="span2"></div>
    	<div class="span8">
	        <div class="control-group ">
	            <label class="control-label" for="listaEstado">Treinamentos</label>
	            <div class="controls controls-row">
	                <div class="">
	                    <select id="treinamento" name="treinamento" class="span12 ">
	                    	<option value="">Selecione um treinamento</option>
	                    	<?php
	                    	while($treinamento = pg_fetch_array($res_treinamentos)){
	                    		?>
	                    		<option value="<?=$treinamento['treinamento']?>"><?=$treinamento['titulo']." - ".$treinamento['cidade']?></option>
	                    		<?php
	                    	}
	                    	?>
	                    </select>
	                </div>
	            </div>
	        </div>
	    </div>
    </div>
    </br>
    <input type="button" class="btn btn-primary" value="Pesquisar" name='bt_busca' id='bt_busca'>
    <br>
    <br>
</form>




<script type="text/javascript">	
	$(function(){
		$("#listaEstado").change(function(){
			$("#treinamento").val("");
		});

		$("#treinamento").change(function(){
			$("#listaEstado").val("");
		});

		$("#bt_busca").click(function(){
			if($("#listaEstado").val() == "" && $("#treinamento").val() == ""){
				alert("Escolha um parâmetro");
				return;
			}

			alert("kajs");
		});
	})
</script>