<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$admin_privilegios	= "gerencia";
$layout_menu 		= "gerencia";
$title 				= traduz("RECEBIMENTO X ANÁLISE GARANTIA");

$msg_erro = array();

$btn_acao = $_POST['btn_acao'];
if ($btn_acao == "Pesquisar") {

	$data_inicial = trim($_POST["data_inicial"]);
	$data_final   = trim($_POST["data_final"]);
	$codigo_posto = trim($_POST['codigo_posto']);
	$descricao = trim($_POST['descricao']);
	$atendente = trim($_POST['atendente']);

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
	if(strlen($codigo_posto)>0){
		$sql = "SELECT posto
		          FROM tbl_posto_fabrica
		         WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
		$res = @pg_exec($con,$sql);
		if(pg_num_rows($res) > 0) {
			$posto = pg_result($res,0,0);
			if(strlen($posto)==0) {
				$msg_erro["msg"][]    = traduz("Posto não encontrado!");
				$msg_erro["campos"][] = "posto";
			} else {
				$condicao .= " AND   tbl_os.posto = $posto ";
			}
		}
	}

	if (strlen($atendente) > 0) {
		$condicao .= " AND   tbl_admin.admin   = $atendente ";
	}

	if(count($msg_erro['msg']) == 0){

		if(strlen($estado) > 0){
			if(!in_array($login_fabrica, array(152, 180, 181, 182)) && !isset($array_estado[$estado])){
				$msg_erro["msg"][]   .= "Estado não encontrado";
				$msg_erro["campos"][] = "estado";
			}
		}

		if(count($msg_erro["msg"]) == 0){

			if(strlen($estado) > 0){
				if(in_array($login_fabrica, array(152, 180, 181, 182))){
					$estado_sql = str_replace(",", "','",$estado);
				}
				$condicao .= " AND tbl_posto.estado IN ('$estado_sql')";
			}

			$sql = "SELECT 	TO_CHAR(tbl_auditoria_os.data_input , 'DD/MM/yyyy') as auditoria_data_input,
							tbl_os.os,
							tbl_os.posto,
							tbl_posto.nome,
							CASE    WHEN tbl_auditoria_os.liberada is not null THEN 'Liberada'
							        WHEN tbl_auditoria_os.cancelada is not null THEN 'Cancelada'
							        WHEN tbl_auditoria_os.reprovada is not null THEN 'Reprovada'
							        ELSE 'Aguardando' 
							END AS status_da_os,

							CASE    WHEN tbl_auditoria_os.liberada is not null THEN TO_CHAR(tbl_auditoria_os.liberada , 'DD/MM/yyyy')
							        WHEN tbl_auditoria_os.cancelada is not null THEN TO_CHAR(tbl_auditoria_os.cancelada , 'DD/MM/yyyy')
							        WHEN tbl_auditoria_os.reprovada is not null THEN TO_CHAR(tbl_auditoria_os.reprovada , 'DD/MM/yyyy')
							        ELSE null 
							END AS status_os_data,

							CASE WHEN tbl_auditoria_os.liberada is not null THEN tbl_auditoria_os.liberada::date - tbl_auditoria_os.data_input::date
							WHEN tbl_auditoria_os.cancelada is not null THEN tbl_auditoria_os.cancelada::date - tbl_auditoria_os.data_input::date
							WHEN tbl_auditoria_os.reprovada is not null THEN tbl_auditoria_os.reprovada::date - tbl_auditoria_os.data_input::date
							ELSE null END AS qtde,
							tbl_auditoria_status.descricao,
							tbl_admin.nome_completo,
							tbl_posto_fabrica.codigo_posto
							FROM tbl_os
							INNER JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
							INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							INNER JOIN tbl_auditoria_os ON tbl_os.os = tbl_auditoria_os.os
							INNER JOIN tbl_auditoria_status ON tbl_auditoria_os.auditoria_status = tbl_auditoria_status.auditoria_status
							INNER JOIN tbl_admin ON tbl_auditoria_os.admin = tbl_admin.admin
							WHERE tbl_os.fabrica = $login_fabrica								
								AND tbl_auditoria_status.auditoria_status = 6
								AND tbl_auditoria_os.observacao ~* 'Defeito Constatado'
								$condicao								
							GROUP BY
								auditoria_data_input,
								tbl_os.os,
								tbl_os.posto,
								tbl_posto.nome,
								status_da_os,
								status_os_data,
								qtde,
								tbl_auditoria_status.descricao,
								tbl_admin.nome_completo,
								tbl_posto_fabrica.codigo_posto
							ORDER BY tbl_posto.nome, tbl_os.os; ";
			//print_r(nl2br($sql));exit;
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
		
		$.autocompleteLoad(Array("posto"));
		
		$("span[rel=lupa]").click(function () {	$.lupa($(this));});

		$("#data_final").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$("#data_inicial").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

		$.dataTableLoad({
			table: "#gridRelatorioPosto"
	 	});
		
	});

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
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

<div class="row"> <b class="obrigatorio pull-right">* <?php echo traduz("Campos obrigatórios"); ?></b> </div>
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
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="estado" ><?php echo traduz("Estado/Região");?></label>
					<div class="controls control-row">
						<select id="estado" name="estado" class="span12" >
						</select>
					</div>
				</div>
			</div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="atendente" ><?php echo traduz("Atendente");?></label>
					<div class="controls control-row">
						<select id="atendente" name="atendente" class="span12" >
							<option value="" ></option>
							<?php
							$sql_a = "SELECT nome_completo, admin 
										FROM tbl_admin 
										WHERE fabrica = $login_fabrica 
										AND ativo = 't';";
							$res_a = pg_query($con,$sql_a);

							if (pg_num_rows($res_a) > 0) {
								$array_atendentes = pg_fetch_all($res_a);
								foreach ($array_atendentes as $admin) {
									$selected = ($atendente == $admin['admin']) ? "selected" : "";
									echo "<option value='".$admin['admin']."'".$selected." >".$admin['nome_completo']."</option>";
								}
							}
							?>
						</select>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'><?php echo traduz("Código Posto");?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='descricao_posto'><?php echo traduz("Razão Social");?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
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
					<th><?php echo traduz("Status OS");?></th>
					<th><?php echo traduz("Data Inicio Auditoria");?></th>
					<th><?php echo traduz("Data Fim Auditoria");?></th>
					<th><?php echo traduz("Qtde Dias");?></th>
					<th><?php echo traduz("Atendente");?></th>
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
				<td align='left'><a href="os_press.php?os=<?=$os?>" target="_blank"><?=$os?></a></td>
				<td align='center'><center><?=pg_fetch_result($resConsulta, $i, "status_da_os")?></center></td>
				<td align='center'><?=pg_fetch_result ($resConsulta,$i,"auditoria_data_input")?></td>
				<td align='center'><?=pg_fetch_result ($resConsulta,$i,"status_os_data")?></td>
				<td align='center'><?=pg_fetch_result ($resConsulta,$i,"qtde")?></td>
				<td align='center'><?=pg_fetch_result ($resConsulta,$i,"nome_completo")?></td>
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

