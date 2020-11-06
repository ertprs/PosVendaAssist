<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$admin_privilegios	= "gerencia";
$layout_menu 		= "gerencia";
$title 				= traduz("TEMPO MÉDIO DE FALHA POR EQUIPAMENTO");

$msg_erro = array();

$btn_acao = $_POST['btn_acao'];
if ($btn_acao == "Pesquisar") {

	$data_inicial = trim($_POST["data_inicial"]);
	$data_final   = trim($_POST["data_final"]);	
	$produto_referencia = trim($_POST['produto_referencia']);
	$produto_descricao = trim($_POST['produto_descricao']);	
	$linha = trim($_POST['linha']);	
	$familia = trim($_POST['familia']);

	//Validação Data Inical e Data Final
	if(empty($data_inicial) || empty($data_final)) {
		$msg_erro['msg'][]  = traduz("Preencha os campos obrigatórios");
		$msg_erro['campos'][] = "data";
	}else{
		try{
			$resultado_data = validaData($data_inicial, $data_final,6);

			$condicao = " AND tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final' ";

		}catch(Exception $e){
				$msg_erro["msg"][] = $e->getMessage();
				$msg_erro["campos"][] = "data";
		}
	}
	if (strlen($linha) > 0) {
		$condicao .= " AND tbl_produto.linha = $linha ";
	}
	if (strlen($familia) > 0) {
		$condicao .= " AND tbl_produto.familia = $familia ";
	}
	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto
		          FROM tbl_produto
		         WHERE referencia = '$produto_referencia' AND fabrica_i = $login_fabrica";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0) {
			$produto = pg_result($res,0,0);
			if(strlen($produto)==0) {
				$msg_erro["msg"][]    = traduz('Produto não encontrado!');
				$msg_erro["campos"][] = "produto";
			} else {
				$condicao .= " AND tbl_produto.produto = $produto ";
			}
		}
	}

	if(count($msg_erro['msg']) == 0){

		if(strlen($estado) > 0){
			if(!in_array($login_fabrica, array(152, 180, 181, 182)) && !isset($array_estado[$estado])){
				$msg_erro["msg"][]   .= traduz("Estado não encontrado");
				$msg_erro["campos"][] = "estado";
			}
		}

		if(count($msg_erro["msg"]) == 0){

			if(strlen($estado) > 0){
				if(in_array($login_fabrica, array(152, 180, 181, 182))) {
					$estado_sql = str_replace(",", "','",$estado);
				}
				$condicao .= " AND tbl_posto.estado IN ('$estado_sql')";
			}

			$sql = "SELECT 	TO_CHAR(tbl_os.data_abertura , 'DD/MM/yyyy') as data_abertura,
							TO_CHAR(tbl_os.data_nf , 'DD/MM/yyyy') as data_nf,
							tbl_os.os,
							tbl_os.data_abertura::date - tbl_os.data_nf::date as qtde,
							tbl_os.posto,
							tbl_posto.nome,
							tbl_posto_fabrica.codigo_posto,
							tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
							FROM tbl_os
							INNER JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
							INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							INNER JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
							WHERE tbl_os.fabrica = $login_fabrica
								$condicao								
							GROUP BY
								data_abertura,
								data_nf,
								tbl_os.os,
								qtde,
								tbl_os.posto,
								tbl_posto.nome,
								tbl_posto_fabrica.codigo_posto,
								tbl_produto.produto,
								tbl_produto.referencia,
								tbl_produto.descricao
							ORDER BY tbl_posto.nome, tbl_os.os; ";
			// print_r(nl2br($sql));exit;
			$resConsulta = pg_query($con,$sql);
			// var_dump(pg_last_error());
			$result =  pg_fetch_all($resConsulta);
		}
	}
}

include "cabecalho_new.php";

$plugins = array( "dataTable","datepicker","maskedinput","shadowbox","autocomplete" );
include("plugin_loader.php");

?>

<script type="text/javascript">
	$(function() {
		
		Shadowbox.init();
		
		$.autocompleteLoad(Array("produto"));
		
		$("span[rel=lupa]").click(function () {	$.lupa($(this));});

		$("#data_final").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$("#data_inicial").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

		$.dataTableLoad({
			table: "#gridRelatorioPosto"
	 	});
		
	});

	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
	    $("#produto_descricao").val(retorno.descricao);
	}


	var map = {"â":"a","Â":"A","à":"a","À":"A","á":"a","Á":"A","ã":"a","Ã":"A","ê":"e","Ê":"E","è":"e","È":"E","é":"e","É":"E","î":"i","Î":"I","ì":"i","Ì":"I","í":"i","Í":"I","õ":"o","Õ":"O","ô":"o","Ô":"O","ò":"o","Ò":"O","ó":"o","Ó":"O","ü":"u","Ü":"U","û":"u","Û":"U","ú":"u","Ú":"U","ù":"u","Ù":"U","ç":"c","Ç":"C","ñ":"n"};

    function removerAcentos(string) { 
        return string.replace(/[\W\[\] ]/g,function(a) {
            return map[a]||a}) 
    };

    /** select de provincias/estados */
    $(function() {

    	$("#estado option").remove();
    	
    	$("#estado optgroup").remove();

    	$("#estado").append("<option value=''>TODOS OS ESTADOS</option>");

        var post = "<?php echo $_POST['estado']; ?>";

 		<?php if (in_array($login_fabrica,[181])) { ?> 

            $("#estado").append('<optgroup label="Provincias">');
                
            var select = "";
            
            <?php 
			
			$provincias_CO = getProvinciasExterior("CO");
        	
        	foreach ($provincias_CO as $provincia) { ?>

                var provincia = '<?= $provincia ?>';

                var semAcento = removerAcentos(provincia);

				if (post == semAcento) {

					select = "selected";
				}

            	var option = "<option value='" + semAcento + "' " + select +">" + provincia + "</option>";

                $("#estado").append(option);

                select = "";

            <?php } ?>

                $("#estado").append('</optgroup>');

	  	<?php } ?>

	  	<?php if (in_array($login_fabrica,[182])) { ?>
  			
		  	
		  	$("#estado").append('<optgroup label="Provincias">');
  			
  			var select = "";
                
            <?php 

            $provincias_PE = getProvinciasExterior("PE");

            foreach ($provincias_PE as $provincia) { ?>

                var provincia = '<?= $provincia ?>';

                var semAcento = removerAcentos(provincia);

               	if (post == semAcento) {
                	
                	select = "selected";
                }

            	var option = "<option value='" + semAcento + "' " + select +">" + provincia + "</option>";

                select = "";

			<?php } ?>

			$("#estado").append(option);
		
		<?php } ?>

		<?php if (in_array($login_fabrica,[180])) {  ?>

			$("#estado").append('<optgroup label="Provincias">');

			var select = "";
                
            <?php 

            $provincias_AR = getProvinciasExterior("AR");

            foreach ($provincias_AR as $provincia) { ?>

	            var provincia = '<?= $provincia ?>';

	            var semAcento = removerAcentos(provincia);

	           	if (post == semAcento) {

	            	select = "selected";
	            } 

            	var option = "<option value='" + semAcento + "' " + select +">" + provincia + "</option>";

                $("#estado").append(option);

                select = "";

            <?php } ?>

            $("#estado").append('</optgroup>');

		<?php } ?> 
        <?php if (in_array($login_fabrica, [152])) { ?>
			
			var array_regioes = [

					"BA,SE,AL,PE,PB,RN,CE,PI,MA,SP",
					"MG,DF,GO,MT,RO,AC,AM,RR,PA,AP,TO",
					"MS,PR,SC,RS,RJ,ES"
				];

		   	$("#estado").append('<optgroup label="Regiões">');
         
			var select = "";
			
			$.each(array_regioes, function( index, value ) {
			 
                if (post == value) {
                	select = "selected";
                }

				var option = "<option value=" + value + " "+ select + ">" + value + "</option>";

			    $("#estado").append(option);

			    select = "";
			});
			 
            $("#estado").append('</optgroup>');

		  <?php } ?>

        <?php if (!in_array($login_fabrica, [180,181,182])) { ?>	

			$("#estado").append('<optgroup label="Estados">');
            
        	<?php foreach ($estados_BR as $sigla => $estado) { ?>

	            var estado = '<?= $estado ?>';
	            var sigla = '<?= $sigla ?>';

            	if (post == sigla) {

            		select = "selected";
            	}

	            var option = "<option value='" + sigla + "'" + select +">" + estado + "</option>";

                $("#estado").append(option);

        	<?php } ?>

        	$("#estado").append('</optgroup>');

		<?php } ?>       
        
    });


</script>

<?php if (count($msg_erro["msg"]) > 0) {	?>
	<div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php 	}	?>

<div class="row"> <b class="obrigatorio pull-right">* <?php echo traduz("Campos obrigatórios");?> </b> </div>
<form name='frm_custo' class="form-search form-inline tc_formulario" action='<? echo $PHP_SELF ?>' method='post'>

	<div class="titulo_tabela"><?php echo traduz("Parâmetros de Pesquisa");?></div>
	<br />
	<div class="container tc_container">
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?php echo traduz("Data Inicial");?></label>
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
					<label class='control-label' for='data_final'><?php echo traduz("Data Final");?></label>
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
				<div class='control-group'>
	                <label class='control-label' for='produto_referencia'><?php echo traduz("Ref. Produto");?></label>
	                <div class='controls controls-row'>
	                    <div class='span10 input-append'>
	                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<?=$produto_referencia?>" >
	                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
	                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
	                    </div>
	                </div>
	            </div>
			</div>
			<div class='span4'>
				<div class='control-group'>
	                <label class='control-label' for='produto_descricao'><?php echo traduz("Descrição Produto");?></label>
	                <div class='controls controls-row'>
	                    <div class='span10 input-append'>
	                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<?=$produto_descricao?>" >
	                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
	                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
	                    </div>
	                </div>
	            </div>
			</div>
			<div class='span2'></div>
		</div>


		<div class='row-fluid'>
			<div class='span2'></div>

			<div class="span4">
                <div class="control-group">
                    <label class='control-label' for='linha'><?php echo traduz("Linha");?></label>
                    <div class='controls controls-row'>
						<?
				        $sql_linha = "SELECT
				                            linha,
				                            nome
				                    FROM tbl_linha
				                    WHERE tbl_linha.fabrica = $login_fabrica
				                    ORDER BY tbl_linha.nome ";
				        $res_linha = pg_query($con, $sql_linha);
						?>
                        <select name="linha" class="span12">
                            <option value=''><?php echo traduz("ESCOLHA");?></option>
						<?php

					        if (pg_num_rows($res_linha) > 0) {
					            for ($j = 0 ; $j < pg_num_rows($res_linha) ; $j++){
					                $aux_linha    = trim(pg_fetch_result($res_linha,$j,linha));
					                $aux_descricao  = trim(pg_fetch_result($res_linha,$j,nome));

									?><option value = "<?=$aux_linha?>" <?=($linha == $aux_linha) ? " SELECTED " : ""?>><?=$aux_descricao?></option><?
            					}
       						}
							?>
                    	</select>
                    </div>
                </div>
            </div>

			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='familia'><?php echo traduz("Familia");?></label>
					<div class='controls controls-row'>						
						<?php
						$sql = "SELECT *
								  FROM tbl_familia
								 WHERE tbl_familia.fabrica = $login_fabrica
							  ORDER BY tbl_familia.descricao;";
						$res = pg_query ($con,$sql);?>
						<select name='familia' class="span12">
							<option value=''><?php echo traduz("ESCOLHA");?></option>

						<?php
						if (pg_num_rows($res) > 0) {

							for ($x = 0 ; $x < pg_num_rows($res) ; $x++) {
								$aux_familia    = trim(pg_fetch_result($res,$x,familia));
								$aux_descricao  = trim(pg_fetch_result($res,$x,descricao));

								echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
							}
							echo "</select>\n";
						}
						?>						
					</div>
				</div>
            </div>

            <div class='span2'></div>
        </div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="estado" ><?php echo traduz("Estado/Região");?></label>
					<div class="controls control-row">
						<select id="estado" name="estado" class="span12" >
						</select>
					</div>
				</div>
			</div>
			<div class="span4"></div>
			<div class='span2'></div>
		</div>

		<br />
			<center>				
				<input class="btn" type="submit" name="btn_acao" value="Pesquisar">
			</center>
		<br />
	</div>
</form>
<br />

<?php
if ($btn_acao == "Pesquisar") {
	if(pg_num_rows($resConsulta) > 0) {
	?>
		<table id='gridRelatorioPosto' class='table table-striped table-bordered table-hover table-fixed'>
			<thead>
				<tr class='titulo_coluna'>
					<th><?php echo traduz("Posto Código");?></th>
					<th><?php echo traduz("Posto Nome");?></th>
					<th><?php echo traduz("OS");?></th>
					<th><?php echo traduz("Referência Produto");?></th>
					<th><?php echo traduz("Descrição Produto");?></th>
					<th><?php echo traduz("Data Abertura OS");?></th>
					<th><?php echo traduz("Data Nota Fiscal");?></th>
					<th><?php echo traduz("Qtde Dias");?></th>
				</tr>
			</thead>
			<tbody>
		<?php
		for ($i = 0 ; $i < pg_num_rows ($resConsulta) ; $i++) {
			$os = pg_fetch_result ($resConsulta,$i,"os");
			?>
			<tr>
				<td align='center'><center><?=pg_fetch_result($resConsulta, $i, "codigo_posto")?></center></td>
				<td align='center'><center><?=pg_fetch_result($resConsulta, $i, "nome")?></center></td>
				<td align='left'><a href="os_press.php?os=<?=$os?>" target="_blank"><?=$os?></td>
				<td align='left'><?=pg_fetch_result ($resConsulta,$i,"referencia")?></td>
				<td align='left'><?=pg_fetch_result ($resConsulta,$i,"descricao")?></td>
				<td align='center'><?=pg_fetch_result ($resConsulta,$i,"data_abertura")?></td>
				<td align='center'><?=pg_fetch_result ($resConsulta,$i,"data_nf")?></td>
				<td align='center'><?=pg_fetch_result ($resConsulta,$i,"qtde")?></td>				
			</tr>
		<?php
		} ?>
		</table>
	<?php
	} else { ?>
		<div class='alert'><h4><?php echo traduz("Não foram encontrados registros no período indicado."); ?></h4></div>
	<?php
	}
}
?>
<br /><br />
<?php
include "rodape.php";

