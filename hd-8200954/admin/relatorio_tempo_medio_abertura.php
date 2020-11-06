<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$admin_privilegios	= "gerencia";
$layout_menu 		= "gerencia";
$title 				= traduz("RELATÓRIO MÉDIO DE ABERTURA OS");



if($_GET['verifica_defeitos']=="true")
{
	?>
	<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
	<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

	<?
	if(strlen($_GET["os"]))
	{
		$os = $_GET["os"];
		$sql = "	SELECT tbl_defeito_constatado.descricao,
						tbl_os_defeito_reclamado_constatado.tempo_reparo
					FROM tbl_os
					INNER JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os
					INNER JOIN tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado
					WHERE tbl_os.os = $os";
	 	$res = pg_query($con,$sql);
	 	if(pg_num_rows($res)>0){
	 		$result = pg_fetch_all($res);
	 		echo "<br /><label class='titulo_coluna' style='text-align:center; height:30px; font-size:17px; vertical-align:center;'><b>" . traduz("Defeito(s) Constatado(s)</b> da OS:") . $os . "</label><br />";
			echo "<table class='table table-striped table-bordered table-hover table-fixed'>
					<thead>
						<tr class='titulo_coluna'>
							<th>" . traduz("Defeito Constatado") . "</th>
							<th>" . traduz("Tempo de reparo") . "</th>
						</tr>
					</thead>
					<tbody>
						";

			foreach ($result as $key => $value) {
				echo "<tr>
						<td> ".$value['descricao']."</td>";
				echo "	<td> ".$value['tempo_reparo']." min</td>
					 </tr>";
			}
				echo "
					</tbody>
				</table>";
		}else{
			echo "<div class='alert'><h4>" . traduz("Não foram encontrados registros.") . "</h4></div>";
		}
	}
	exit;
}
$msg_erro = array();

include "cabecalho_new.php";

$plugins = array( "dataTable","shadowbox" );

include("plugin_loader.php");

$btn_acao = $_POST['btn_acao'];
if ($btn_acao == "submit") {
	$data_inicial = trim($_POST["data_inicial"]);
	$data_final   = trim($_POST["data_final"]);
	$estado       = trim($_POST["estado"]);
	$linha        = trim($_POST["linha"]);
	$familia      = trim($_POST["familia"]);
	$mes          = trim($_POST['mes']);
	$ano          = trim($_POST['ano']);


	if (strlen($mes)>0 AND strlen($ano)>0){
		$data_inicial = $ano . "-" . $mes . "-01 00:00:00";
		$data_final   = pg_fetch_result (pg_query ($con,"SELECT ('$data_inicial'::date + INTERVAL '1 month' - INTERVAL '1 day')::date "),0,0) . " 23:59:59";
	}
	if(empty($data_inicial) || empty($data_final)) {
		$msg_erro['msg'][]  = traduz("Preencha os campos obrigatórios");
		$msg_erro['campos'][] = "data";
	}else{
try{
			$resultado_data = validaData($data_inicial, $data_final);

			$condicao = " AND tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final' ";

		}catch(Exception $e){
				$msg_erro["msg"][] = $e->getMessage();
				$msg_erro["campos"][] = "data_inicial";
				$msg_erro["campos"][] = "data_final";
		}
	}

	if(strlen(trim($linha))>1){
		$condicao .= " AND tbl_produto.linha      = $linha ";
	}

    if(strlen(trim($familia))>1) {
    	$condicao .= " AND tbl_produto.familia  = $familia ";
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
				if(in_array($login_fabrica, array(152, 180, 181, 182))){
					$estado = str_replace(",", "','",$estado);
				}
				$condicao .= " AND tbl_posto.estado IN ('$estado')";
			}

				/*
					*o resultado irá mostrar os campos posto e tempo médio de reparo (em horas)
					para buscar o tempo médio deve buscar todas as os's do mês e ano selecionado
					(tbl_os.data_abertura), pegar todos os defeitos constatados da os e somar o tempo de reparo ai você tem o tempo total da os
					(tbl_os_defeito_reclamado_constatado.tempo_reparo), depois dividir pela quantidade de OS's e pronto já tem o tempo médio de reparo
					(nesse campo ele é gravado em minutos)
					*criar um gráfico de barras que irá listar o top 20
					postos com a menor média de
					tempo de reparo e um outro gráfico de barras
					com o top 20 postos com a maior média de tempo de reparo
					ShiShoSho!! dia em que o pudim(rafael-macedo) confundio aspas com alguma outra coisa
				*/

			$sql = "SELECT
						TO_CHAR(tbl_os.data_abertura , 'DD') as data_abertura,
						tbl_os.os,
						SUM(tbl_os_defeito_reclamado_constatado.tempo_reparo) as tempo_reparo,
						tbl_os.posto,
						tbl_posto.nome,
						tbl_posto_fabrica.codigo_posto
					FROM tbl_os
				LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
				INNER JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				INNER JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os
				WHERE tbl_os.fabrica = {$login_fabrica}

				{$condicao}
				GROUP BY tbl_os.posto ,
							tbl_posto.nome,
							tbl_posto_fabrica.codigo_posto,
							tbl_os.os ,
							data_abertura
				ORDER BY tbl_os.os ";
			// print_r(nl2br($sql));exit;
			$resConsulta = pg_query($con,$sql);
			// var_dump(pg_last_error());
			$result =  pg_fetch_all($resConsulta);
			// É atribuído novamente o valor original do POST para a variável utilizar no elemento select
			$estado = trim($_POST["estado"]);
		}
	}
}

?>

<style type="text/css">

	#container-highcharts {
		height: 400px;
		min-width: 310px;
		max-width: 800px;
		margin: 0 auto;
	}
	#gridRelatorioPosto {
		background-color : #FFF ;
	}

</style>

<script type="text/javascript" charset="utf-8">
	$(function() {
		Shadowbox.init();

		$.dataTableLoad({
			table: "#gridRelatorioPosto"
	 	});

	   	$(".shadowbox_os").click(function() {
    		var os =  $(this).data("os");

    		var url = "relatorio_tempo_medio_abertura.php?os="+os+"&verifica_defeitos=true";
			Shadowbox.open({
				content: url,
				player: "iframe",
				height: 600,
				width: 800
			});
		});
	});


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

        <?php if ($login_fabrica == 152) { ?>

            var array_regioes = [
                
                "BA,SE,AL,PE,PB,RN,CE,PI,MA,SP",
                "MG,DF,GO,MT,RO,AC,AM,RR,PA,AP,TO",
                "MS,PR,SC,RS,RJ,ES"
            ];

            $("#estado").append('<optgroup label="Regioes">');

            $.each(array_regioes, function( index, regioes ) {
        
                var opRegiao = new Option("option text", index);
                $(opRegiao).html(regioes, index);
                $("#estado").append(opRegiao);
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

<div class="row"> <b class="obrigatorio pull-right">* <?php echo traduz("Campos obrigatórios");?></b> </div>
<form name='frm_custo' class="form-search form-inline tc_formulario" action='<? echo $PHP_SELF ?>' method='post'>

	<div class="titulo_tabela"><?php echo traduz("Parâmetros de Pesquisa");?></div>
	<br />
	<div class="container tc_container">
		<div class='row-fluid'>
			<div class='span2'></div>

			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='Mes'><?php echo traduz("Mês");?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<select name='mes' size='1'>
								<option value=''></option>
								<option value='01' <? if ($mes == '01') echo ' selected ' ?> ><?=traduz('Janeiro')?></option>
								<option value='02' <? if ($mes == '02') echo ' selected ' ?> ><?=traduz('Fevereiro')?></option>
								<option value='03' <? if ($mes == '03') echo ' selected ' ?> ><?=traduz('Março')?></option>
								<option value='04' <? if ($mes == '04') echo ' selected ' ?> ><?=traduz('Abril')?></option>
								<option value='05' <? if ($mes == '05') echo ' selected ' ?> ><?=traduz('Maio')?></option>
								<option value='06' <? if ($mes == '06') echo ' selected ' ?> ><?=traduz('Junho')?></option>
								<option value='07' <? if ($mes == '07') echo ' selected ' ?> ><?=traduz('Julho')?></option>
								<option value='08' <? if ($mes == '08') echo ' selected ' ?> ><?=traduz('Agosto')?></option>
								<option value='09' <? if ($mes == '09') echo ' selected ' ?> ><?=traduz('Setembro')?></option>
								<option value='10' <? if ($mes == '10') echo ' selected ' ?> ><?=traduz('Outubro')?></option>
								<option value='11' <? if ($mes == '11') echo ' selected ' ?> ><?=traduz('Novembro')?></option>
								<option value='12' <? if ($mes == '12') echo ' selected ' ?> ><?=traduz('Dezembro')?></option>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='Ano'><?php echo traduz("Ano");?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<?php
							$sql = "SELECT date_part('YEAR', min(data_abertura))as data_min from tbl_os where fabrica = {$login_fabrica}";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res)>0){
								$sql = "SELECT date_part('YEAR', current_date )as data_min ";
								$res = pg_query($con,$sql);
							}
							$data = pg_fetch_result($res, 0, "data_min");
							?>

							<select name='ano' size='1' class='Caixa'>
								<option value=''></option>
								<?php
									for ($i = $data ; $i <= date("Y") ; $i++)
									{
										echo "<option value='$i'";
										if ($ano == $i) echo " selected";
										echo ">$i</option>";
									}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="container tc_container">
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class="span4">
                    <div class="control-group">
                        <label class='control-label' for='linha '><?php echo traduz("Linha"); ?></label>
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
                            <select name="linha" >
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
						<label class='control-label' for='familia'><?php echo traduz("Familia"); ?></label>
						<div class='controls controls-row'>
							<div class='span4'>
								<?php
									$sql = "SELECT *
											  FROM tbl_familia
											 WHERE tbl_familia.fabrica = $login_fabrica
										  ORDER BY tbl_familia.descricao;";
									$res = pg_query ($con,$sql);

									if (pg_num_rows($res) > 0)
									{
										echo "
											<select name='familia'>
												<option value=''>" . traduz("ESCOLHA") . "</option>";

										for ($x = 0 ; $x < pg_num_rows($res) ; $x++)
										{
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
                </div>
                <div class='span2'></div>
            </div>
        </div>


		<div class='row-fluid'>
			<div class='span2'></div>
			<div class="span6">
				<div class="control-group">
					<label class="control-label" for="estado" ><?php echo traduz("Estado/Região");?></label>
					<div class="controls control-row">
						<select id="estado" name="estado" class="span12" >
						
					 	<?php if (isset($_POST['estado'])) { 
                            
		                    $sigla = $_POST['estado'];
		                    $estado = $_POST['estado'];

		                    if (!in_array($login_fabrica, [180, 181, 182])) {

		                        $brasil = [ 
		                            "AC" => "AC - Acre",
		                            "AL" => "AL - Alagoas",
		                            "AM" => "AM - Amazonas",
		                            "AP" => "AP - Amapá",
		                            "BA" => "BA - Bahia",
		                            "CE" => "CE - Ceará"  ,
		                            "DF" => "DF - Distrito Federal",
		                            "ES" => "ES - Espírito Santo" ,
		                            "GO" => "GO - Goiás","MA - Maranhão", 
		                            "MG" => "MG - Minas Gerais",
		                            "MS" => "MS - Mato Grosso do Sul",
		                            "MT" => "MT - Mato Grosso",
		                            "PA" => "PA - Pará","PB - Paraíba",
		                            "PE" => "PE - Pernambuco",
		                            "PI" => "PI - Piauí","PR - Paraná",
		                            "RJ" => "RJ - Rio de Janeiro",
		                            "RN" => "RN - Rio Grande do Norte",
		                            "RO" => "RO - Rondônia", 
		                            "RN" => "RR - Roraima",
		                            "RS" => "RS - Rio Grande do Sul", 
		                            "SC" => "SC - Santa Catarina",
		                            "SE" => "SE - Sergipe", 
		                            "SP" => "SP - São Paulo",
		                            "TO" => "TO - Tocantins" 
		                        ];

		                        $estado = $brasil[$sigla];
		                    } ?>

                    <option value="<?= $sigla ?>"><?= $estado ?></option>
                    <? } else { ?>
                        <option value=""   
                        <?php if (strlen($estado) == 0)    
                            echo " selected "; 
                        ?>
                        >TODOS OS ESTADOS 
                        <?php if($login_fabrica == 86) {
                            echo " e/ou Regiões"; }?>   
                        </option>
                    <?php } ?>
						</select>
					</div>
				</div>
			</div>
		</div>

	<br />
		<center>
			<input type="hidden" name="btn_acao" value="submit">
			<input class="btn" type="submit" name="btn_acao;"alt="Gerar Relatório" value="Gerar Relatório">
		</center>
	<br />
</form>
<br />

<?php
if ($btn_acao == "submit")
{
	if(pg_num_rows($resConsulta) > 0)
	{
		// print_r(json_encode($result));exit;
		$sqlg ="SELECT
					tbl_posto.nome,
					SUM(tbl_os_defeito_reclamado_constatado.tempo_reparo) / count(tbl_os.os) as media
				FROM tbl_os
				LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
				INNER JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				INNER JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os
				WHERE tbl_os.fabrica = {$login_fabrica}
				{$condicao}
				GROUP BY tbl_posto.nome
				ORDER BY media
				LIMIT 20 ";
				// print_r(nl2br($sqlg));exit;
		$res_graf = pg_query($con,$sqlg);


		if(pg_num_rows($res_graf))
		{
			$result_grap = pg_fetch_all($res_graf);
			?>
			<script src="js/novo_highcharts.js"></script>
			<script src="js/modules/exporting.js"></script>
			<script type="text/javascript">
			$(function () {
	    		$('#container-highcharts').highcharts({
			        chart: {
			            type: 'column',
			            margin: 75,
			            options3d: {
			                enabled: true,
			                alpha: 10,
			                beta: 25,
			                depth: 70
			            }
			        },
			        title: {
			            text: '<?php echo traduz("MÉDIA DE TEMPO DE REPARO POR POSTO");?>'
			        },
			        subtitle: {
			            text: ''
			        },
			        plotOptions: {
			            column: {
			                depth: 25
			            }
			        },
			        xAxis: {
			            categories: [
		            	<?php foreach ($result_grap as $key => $value) {
			            		echo json_encode($value['nome']);
			           			echo ", ";
			           		}
						?> ]
			        },
			        yAxis: {
			            title: {
			                text: "<?php echo traduz('Minutos');?>"
			            }
			        },
			        series: [{
			            name: '<?php echo traduz("Média"); ?>',
			            data: [ <?php
			            	foreach ($result_grap as $key => $value) {
			            		echo $value['media'];
			            		echo ", ";
			           		}
			            ?> ]
			        }]
			    });
			});
			</script>
			<?

			echo '<div id="container-highcharts" style="height:400px;"></div><br />';
		}

		echo "	<table id='gridRelatorioPosto' class='table table-striped table-bordered table-hover table-fixed'>
				<thead>
					<tr class='titulo_coluna'>
						<th>" . traduz("OS") ."</th>
						<th>" . traduz("Tempo de reparo ") ."</th>
						<th>" . traduz("Data Abertura")    ."</th>
						<th>" . traduz("Posto Nome")       ."</th>
						<th>" . traduz("Posto Código")     ."</th>
					</tr>
				</thead>
				<tbody>";

		for ($i = 0 ; $i < pg_num_rows ($resConsulta) ; $i++)
		{

				$os = pg_fetch_result ($resConsulta,$i,"os");
			echo "<tr class='shadowbox_os' data-os='$os' >";
				echo "<td align='left'>".$os."</td>";
				echo "<td align='center'>".pg_fetch_result ($resConsulta,$i,"tempo_reparo")."</td>";
				echo "<td align='left'>".pg_fetch_result ($resConsulta,$i,"data_abertura")."/$mes/$ano</td>";
				echo "<td align='center'><center>".pg_fetch_result($resConsulta, $i, "nome")."</center></td>";
				echo "<td align='center'><center>".pg_fetch_result($resConsulta, $i, "codigo_posto")."</center></td>";

			echo "</tr>";

			$total +=pg_fetch_result ($resConsulta,$i,'total');
		}
		echo "</table>";
	}
	else
	{
		echo "<div class='alert'><h4>" . traduz("Não foram encontrados registros no período indicado.") . "</h4></div>";
	}
}
echo "<br /><br />";
include "rodape.php";
?>
