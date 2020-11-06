<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if($_POST["btn_acao"] == "submit"){
	$data_inicial   = $_POST['data_inicial'];
	$data_final	= $_POST['data_final'];
	$atendente	= $_POST['atendente'];
	$providencia 	= $_POST['providencia'];
	$cliente	= $_POST["cliente"];
	$cpf		= $_POST["cpf"];
	$status		= $_POST["status"];
	$origem 	= $_POST['origem'];
	$providencia3   = $_POST['providencia_nivel_3'];
	$motivo_contato = $_POST['motivo_contato'];

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_inicial."+6 months" ) < strtotime($aux_data_final)) {
				$msg_erro["msg"][]    = "Intervalo de pesquisa não pode ser no do que 6 meses.";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if(count($msg_erro['msg']) == 0){


    	if (!empty($providencia3)) {
    		$condProv   .= "AND tbl_hd_chamado_extra.hd_providencia = {$providencia3}";
    	}

    	if (!empty($motivo_contato)) {
    		$condMotivo .= "AND tbl_hd_chamado_extra.motivo_contato = {$motivo_contato}";
    	}

		if(!empty($atendente)){
			$cond = " AND tbl_hd_chamado.atendente = {$atendente} ";
		}

		if(!empty($providencia)){
			$cond .= " AND tbl_hd_motivo_ligacao.descricao = '$providencia' ";
		}

		if(!empty($cliente)){
			$cond .= " AND tbl_hd_chamado_extra.nome ilike '$cliente%' ";
		}

		if(!empty($cpf)){
			$cpf = str_replace("-", "", $cpf);
			$cpf = str_replace(".", "", $cpf);
			$cpf = str_replace("/", "", $cpf);

			$cond .= " AND tbl_hd_chamado_extra.cpf = '$cpf' ";
		}

		if(!empty($status)){
			$cond .= " AND tbl_hd_chamado.status = '$status' ";
		}

		if(in_array($login_fabrica, array(169,170))){
			$join_origem = " JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_chamado_extra.hd_chamado_origem
								AND tbl_hd_chamado_origem.fabrica = {$login_fabrica}
			";
			$campo_origem = ", tbl_hd_chamado_origem.descricao AS origem ";
			$as_origem = ", origem";

			if(strlen(trim($origem)) > 0){
				$cond .= " AND tbl_hd_chamado_extra.hd_chamado_origem = $origem ";
			}

			$as_classificacao = ", classificacao";

		}

		if ((in_array($login_fabrica, array(174)) || $telecontrol_distrib) && in_array($agrupar,array("n"))) {
			$as_classificacao = ", classificacao";
			$cond_classificacao = " AND hd.classificacao = tmp_hd_chamado_visao_{$login_admin}.classificacao ";
		}

		$sql = "SELECT 	tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.atendente,
						tbl_hd_chamado.data_providencia,
						tbl_hd_chamado_extra.hd_motivo_ligacao,
						tbl_hd_motivo_ligacao.descricao AS providencia,
						tbl_hd_motivo_ligacao.hd_motivo_ligacao as providencia_id,
						tbl_hd_classificacao.descricao AS classificacao,
						tbl_hd_classificacao.hd_classificacao AS classificacao_id,
						upper(tbl_admin.login) AS login
						$campo_origem
					INTO TEMP tmp_hd_chamado_visao_{$login_admin}
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
					JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente AND tbl_admin.fabrica = {$login_fabrica}
					LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_hd_chamado_extra.hd_motivo_ligacao
					AND tbl_hd_motivo_ligacao.fabrica = {$login_fabrica}
					LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao
					LEFT JOIN tbl_hd_providencia ON ( tbl_hd_providencia.hd_providencia = tbl_hd_chamado_extra.hd_providencia)
					AND tbl_hd_providencia.fabrica = {$login_fabrica}
					LEFT JOIN tbl_motivo_contato ON ( tbl_motivo_contato.motivo_contato = tbl_hd_chamado_extra.motivo_contato)
					AND tbl_motivo_contato.fabrica = {$login_fabrica}
					$join_origem
					WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
					AND tbl_hd_chamado.status NOT IN('Cancelado')
					AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
					$cond
					$condProv
					$condMotivo
";
		$res = pg_query($con, $sql);
		
		if($agrupar == "n"){
			$sql = "SELECT atendente,
							login,							
							providencia,
							COUNT(tmp_hd_chamado_visao_{$login_admin}.hd_chamado) AS total_geral,
							(SELECT COUNT(hd.hd_chamado) FROM tmp_hd_chamado_visao_{$login_admin} hd WHERE hd.atendente = tmp_hd_chamado_visao_{$login_admin}.atendente 
							$cond_classificacao
							AND hd.providencia = tmp_hd_chamado_visao_{$login_admin}.providencia 

							AND hd.data_providencia < CURRENT_DATE) AS em_atraso,
							(SELECT COUNT(hd.hd_chamado) FROM tmp_hd_chamado_visao_{$login_admin} hd WHERE hd.atendente = tmp_hd_chamado_visao_{$login_admin}.atendente 
							$cond_classificacao
							AND hd.providencia = tmp_hd_chamado_visao_{$login_admin}.providencia AND hd.data_providencia > CURRENT_DATE) AS em_dia,

							(SELECT COUNT(hd.hd_chamado) FROM tmp_hd_chamado_visao_{$login_admin} hd WHERE hd.atendente = tmp_hd_chamado_visao_{$login_admin}.atendente 
							$cond_classificacao
							AND hd.providencia = tmp_hd_chamado_visao_{$login_admin}.providencia AND hd.data_providencia = CURRENT_DATE) AS hoje 
							$as_origem
							$as_classificacao
						FROM tmp_hd_chamado_visao_{$login_admin}
						GROUP BY atendente,login, providencia $as_classificacao $as_origem
						ORDER BY login";
		}else if($agrupar == "a"){
			$sql = "SELECT atendente,
						login,
						COUNT(tmp_hd_chamado_visao_{$login_admin}.hd_chamado) AS total_geral,
						(SELECT COUNT(hd.hd_chamado) FROM tmp_hd_chamado_visao_{$login_admin} hd WHERE hd.atendente = tmp_hd_chamado_visao_{$login_admin}.atendente AND hd.data_providencia < CURRENT_DATE) AS em_atraso,
						(SELECT COUNT(hd.hd_chamado) FROM tmp_hd_chamado_visao_{$login_admin} hd WHERE hd.atendente = tmp_hd_chamado_visao_{$login_admin}.atendente AND hd.data_providencia > CURRENT_DATE) AS em_dia,
						(SELECT COUNT(hd.hd_chamado) FROM tmp_hd_chamado_visao_{$login_admin} hd WHERE hd.atendente = tmp_hd_chamado_visao_{$login_admin}.atendente AND hd.data_providencia = CURRENT_DATE) AS hoje
						FROM tmp_hd_chamado_visao_{$login_admin} GROUP BY atendente,login  ORDER BY login ";
		}else if($agrupar == "p"){
			$sql = "SELECT 	providencia,

COUNT(tmp_hd_chamado_visao_{$login_admin}.hd_chamado) AS total_geral,
(SELECT COUNT(hd.hd_chamado) FROM tmp_hd_chamado_visao_{$login_admin} hd WHERE hd.providencia = tmp_hd_chamado_visao_{$login_admin}.providencia AND hd.data_providencia < CURRENT_DATE) AS em_atraso,

(SELECT COUNT(hd.hd_chamado) FROM tmp_hd_chamado_visao_{$login_admin} hd WHERE hd.providencia = tmp_hd_chamado_visao_{$login_admin}.providencia AND hd.data_providencia > CURRENT_DATE) AS em_dia,

(SELECT COUNT(hd.hd_chamado) FROM tmp_hd_chamado_visao_{$login_admin} hd WHERE hd.providencia = tmp_hd_chamado_visao_{$login_admin}.providencia AND hd.data_providencia = CURRENT_DATE) AS hoje

						FROM tmp_hd_chamado_visao_{$login_admin}
						GROUP BY providencia
						ORDER BY providencia";
		} else if($agrupar == "c"){
			$sql = "SELECT 	classificacao,
							classificacao_id,
							COUNT(tmp_hd_chamado_visao_{$login_admin}.hd_chamado) AS total_geral,
							(SELECT COUNT(hd.hd_chamado) FROM tmp_hd_chamado_visao_{$login_admin} hd WHERE hd.data_providencia < CURRENT_DATE AND hd.classificacao_id = tmp_hd_chamado_visao_{$login_admin}.classificacao_id) AS em_atraso,
							(SELECT COUNT(hd.hd_chamado) FROM tmp_hd_chamado_visao_{$login_admin} hd WHERE hd.data_providencia > CURRENT_DATE AND hd.classificacao_id = tmp_hd_chamado_visao_{$login_admin}.classificacao_id) AS em_dia,
							(SELECT COUNT(hd.hd_chamado) FROM tmp_hd_chamado_visao_{$login_admin} hd WHERE hd.data_providencia = CURRENT_DATE AND hd.classificacao_id = tmp_hd_chamado_visao_{$login_admin}.classificacao_id) AS hoje
						FROM tmp_hd_chamado_visao_{$login_admin}
						GROUP BY classificacao_id,classificacao
						ORDER BY classificacao";
		}

		$resSubmit = pg_query($con, $sql);
		$count = pg_num_rows($resSubmit);

		if ($_POST["gerar_excel"]) {
			if (pg_num_rows($resSubmit) > 0) {

				$data = date("d-m-Y-H:i");
				$colspan = 4; 

				$fileName = "relatorio_visa-geral-atendimentos-{$login_fabrica}-{$data}.xls";

				$file = fopen("/tmp/{$fileName}", "w");

				if(in_array($agrupar,array("n","a"))){
					$thLogin = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Login</th>";
					$colspan++;
				}

				if(in_array($agrupar,array("n","p")) and $moduloProvidencia){
					$thProvidencia = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Providência</th>";
					$colspan++;
				}

				if(in_array($agrupar,array("n","c")) and $moduloProvidencia){
					$thclassificacao = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Classificação</th>";
					$colspan++;
				}

				if(in_array($login_fabrica, array(169,170)) AND $agrupar == 'n'){
					$thOrigem = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Origem</th>";
					$colspan++;
				}

				$thead = "
					<table border='1'>
						<thead>
							<tr>
								<th colspan='$colspan' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
									RELATÓRIO DE VISÃO GERAL DE ATENDIMENTOS
								</th>
							</tr>
							<tr>
								$thLogin
								$thProvidencia
								$thclassificacao
								$thOrigem
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Geral</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atrasado</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Em Dia</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Hoje</th>
							</tr>
						</thead>
						<tbody>
				";
				fwrite($file, $thead);

				for($j = 0; $j < pg_num_rows($resSubmit); $j++){

					$login 			= pg_fetch_result($resSubmit, $j, 'login');
					$providencia	= pg_fetch_result($resSubmit, $j, 'providencia');
					$total_geral	= pg_fetch_result($resSubmit, $j, 'total_geral');
					$em_atraso		= pg_fetch_result($resSubmit, $j, 'em_atraso');
					$em_dia 		= pg_fetch_result($resSubmit, $j, 'em_dia');
					$hoje			= pg_fetch_result($resSubmit, $j, 'hoje');
					$origem 		= pg_fetch_result($resSubmit, $j, 'origem');
					$atendente 		= pg_fetch_result($resSubmit, $j, 'atendente');
					$classificacao 	= pg_fetch_result($resSubmit, $j, 'classificacao');

					if(in_array($agrupar,array("n","a"))){
						$tdLogin = "<td>{$login}</td>";
					}
					if(in_array($login_fabrica, array(169,170)) AND $agrupar == 'n'){
						$tdOrigem = "<td>{$origem}</td>";
					}
					if(in_array($agrupar,array("n","p")) and $moduloProvidencia){
						$tdProcedencia = "<td>{$providencia}</td>";
					}
					if(in_array($agrupar,array("n","c")) and $moduloProvidencia){
						$tdClassificacao = "<td>{$classificacao}</td>";
					}

					$body ="
							<tr>
								$tdLogin
								$tdProcedencia
								$tdClassificacao
								$tdOrigem
								<td>$total_geral</td>
								<td>$em_atraso</td>
								<td>$em_dia</td>
								<td>$hoje</td>
							</tr>";

					fwrite($file, $body);
				}
				fwrite($file, "
							<tr>
								<th colspan='$colspan' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
							</tr>
						</tbody>
					</table>
				");

				fclose($file);

				if (file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					echo "xls/{$fileName}";
				}

			}

			exit;
		}
	}
}

$layout_menu = "callcenter";
$title= "RELATÓRIO DE VISÃO GERAL DE ATENDIMENTOS";
include "cabecalho_new.php";
$plugins = array(
	"datepicker",
	"mask",
	"dataTable",
	"shadowbox",
	"ajaxform"
);
include("plugin_loader.php");
?>
<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		Shadowbox.init();

		$(".explodir").click(function(){
			var data_inicial = $("#data_inicial").val();
			var data_final =  $("#data_final").val();
            var parametro = $(this).data('parametro');

            var atendente = $(this).data('atendente');
            var classificacao_agrupar = $(this).data('classificacao');
            var providencia_agrupar = $(this).data('providencia');
            var providencia = $("#providencia").val();
            var cliente  = $("#cliente").val();
            var cpf  = $("#cpf").val();
            var situacao = $("status").val();
            var agrupar = $('input[name=agrupar]:checked').val();
            var prov_nivel_3   = $("#providencia_nivel_3 option:selected").val();
            var motivo_contato = $("#motivo_contato option:selected").val();

            Shadowbox.open({
              content:    "dados_relatorio_visao_geral_atendimentos.php?parametro="+parametro+"&data_inicial="+data_inicial+"&data_final="+data_final+"&atendente="+atendente+"&providencia="+providencia+"&cliente="+cliente+"&cpf="+cpf+"&situacao"+situacao+"&agrupar="+agrupar+"&classificacao_agrupar="+classificacao_agrupar+"&providencia_agrupar="+providencia_agrupar+"&motivo_contato="+motivo_contato+"&providencia3="+prov_nivel_3,
                player: "iframe",
                title:      "Dados Receita",
                width:  900,
                height: 500
            });        
        });


	});
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

	<div class="row">
		<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
	</div>

<!--form-->
	<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class ='titulo_tabela'>Parametros de Pesquisa </div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
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
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12 text-center' value= "<?=$data_final?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
		<div class='span2'></div>

		<div class='span4'>
			<div class='control-group <?=(in_array("atendente", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='atendente'>Atendente</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<select name='atendente' class='span12' >
							<option></option>
							<?php

							$sql = "SELECT admin, nome_completo
									FROM tbl_admin
									WHERE fabrica = {$login_fabrica}
									AND callcenter_supervisor IS TRUE
									ORDER BY nome_completo";
							$res = pg_query($con, $sql);

							if (pg_num_rows($res) > 0) {

								for ($i = 0; $i < pg_num_rows($res); $i++) {
									$admin = pg_fetch_result($res, $i, "admin");
									$nome_completo = pg_fetch_result($res, $i, "nome_completo");

									$selected = ($admin == $atendente) ? "selected" : "";

									echo "<option value='{$admin}' {$selected}>{$nome_completo}</option>";
								}
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Providência</label>
				<div class='controls controls-row'>
					<div class='span4'>

						<select name="providencia" id="providencia">
							<option value=""></option>
							<?php

								$sql = "SELECT descricao FROM tbl_hd_motivo_ligacao WHERE fabrica = $login_fabrica and ativo group by descricao order by descricao";
								$res = pg_query($con,$sql);
								foreach (pg_fetch_all($res) as $key) {

									$selected_providencia = ( isset($providencia) and ($providencia == $key['descricao']) ) ? "SELECTED" : '' ;

								?>
									<option value="<?php echo $key['descricao']?>" <?php echo $selected_providencia ?> >
										<?php echo $key['descricao']?>
									</option>
								<?php
								}

							?>
						</select>
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
					<label class='control-label' for='cliente'>Cliente</label>
					<div class='controls controls-row'>
						<div class='span12'>
								<input type="text" name="cliente" id="cliente" class='span12' value= "<?=$cliente?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='cpf'>CPF</label>
					<div class='controls controls-row'>
						<div class='span8'>
								<input type="text" name="cpf" id="cpf" class='span12 text-center' value= "<?=$cpf?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

	<div class='row-fluid'>
	    <div class='span2'></div>
	    <div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
			    <label class='control-label' for='data_inicial'>Situação</label>
			    <div class='controls controls-row'>
				<div class='span4'>

				    <select name="status" id="status">
					<option value=""></option>
					<?php

					$sql = "SELECT status FROM tbl_hd_status WHERE fabrica = $login_fabrica order by status";
					$res = pg_query($con,$sql);
					foreach (pg_fetch_all($res) as $key) {

						$selected_status = ( isset($status) and ($status ==     $key['status']) ) ? "SELECTED" : '' ;

					?>
						<option value="<?php echo $key['status']?>" <?php echo $selected_status ?> >
							<?php echo $key['status']?>
						</option>
					<?php
					}

					?>
				    </select>
				</div>
			    </div>
			</div>
	    </div>
	    <?php if(in_array($login_fabrica, array(169,170))){ ?>
		<div class='span4'>
	        <div class='control-group '>
	            <label class='control-label' for='xorigin'>Origem</label>
	            <div class='controls controls-row'>
	                <div class='span4'>
	                    <select name="origem">
	                        <option value=""></option>
	                        <?php
	                            $sql = "SELECT hd_chamado_origem,descricao
	                                        FROM tbl_hd_chamado_origem
	                                        WHERE fabrica = $login_fabrica
	                                        ORDER BY descricao";
	                            $res = pg_query($con,$sql);

	                            foreach (pg_fetch_all($res) as $key) {
	                                $selected_origem = ( isset($origem) and ($origem == $key['hd_chamado_origem']) ) ? "SELECTED" : '' ;
	                        ?>
	                            <option value="<?php echo $key['hd_chamado_origem']?>" <?php echo $selected_origem ?> >
	                                <?php echo $key['descricao']?>
	                            </option>
	                        <?php
	                        }
	                        ?>
	                    </select>
	                </div>
	            </div>
	        </div>
	    </div>
		<?php }?>

	    <div class='span2'></div>
	</div>
	<?php
	if (in_array($login_fabrica,[169,170])) {
	?>
		<div class="row-fluid">
			<span class="span2"></span>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label'>Providência Nível 3</label>
					<div class='controls controls-row'>
						<select name="providencia_nivel_3" id='providencia_nivel_3' class='frm'>
							<option value=""></option>
							<?php
								$sqlProvidencia3 = "SELECT hd_providencia, descricao
													FROM tbl_hd_providencia WHERE fabrica = {$login_fabrica}
													AND ativo IS TRUE
													ORDER BY descricao DESC";
								$resProvidencia3 = pg_query($con,$sqlProvidencia3);

								if(pg_num_rows($resProvidencia3) > 0){
									while($dadosProv = pg_fetch_object($resProvidencia3)){
										
										$selected = ($dadosProv->hd_providencia == $_POST['providencia_nivel_3']) ? "selected" : "";

										?>
										<option value="<?=$dadosProv->hd_providencia?>" <?=$selected?>>
											<?= $dadosProv->descricao ?>
										</option>
										<?php
									}
								}
							?>
						</select>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label'>Motivo Contato</label>
					<div class='controls controls-row'>
						<select name="motivo_contato" id='motivo_contato' class='frm'>
							<option value=""></option>
							<?php
								$sqlMotivoContato = "SELECT motivo_contato, descricao
													FROM tbl_motivo_contato WHERE fabrica = {$login_fabrica}
													AND ativo IS TRUE
													ORDER BY descricao DESC";
								$resMotivoContato = pg_query($con,$sqlMotivoContato);

								if(pg_num_rows($resMotivoContato) > 0){
									while($dadosContato = pg_fetch_object($resMotivoContato)){
										
										$selected = ($dadosContato->motivo_contato == $_POST['motivo_contato']) ? "selected" : "";

										?>
										<option value="<?=$dadosContato->motivo_contato?>" <?=$selected?>>
											<?= $dadosContato->descricao ?>
										</option>
										<?php
									}
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>

	<div class='row-fluid'>
		<div class='span2'></div>

		<div class='span8'>
			<div class='control-group <?=(in_array("atendente", $msg_erro["campos"])) ? "error" : ""?>'>
				<div class='controls controls-row'>
					<div class='span12'>
						<fieldset>
							<legend style="font-size:15px;font-weight:bold;">Agrupar</legend>
								<label class="radio">
							        <input type="radio" name="agrupar" value="n" checked>
							        Não agrupar
							    </label>
							    <label class="radio">
							        <input type="radio" name="agrupar" value="a" <?php echo ($agrupar == "a") ? "checked" : ""; ?>>
							        Por Atendente
							    </label>
							    <label class="radio">
							        <input type="radio" name="agrupar" value="p" <?php echo ($agrupar == "p") ? "checked" : ""; ?>>
							        Por Providência
							    </label>
							    <?php 
							    if (in_array($login_fabrica, array(174)) || $telecontrol_distrib) {
							    ?>
								<label class="radio">
							        <input type="radio" name="agrupar" value="c" <?php echo ($agrupar == "c") ? "checked" : ""; ?>>
							        Por Classificação
							    </label>
							    <?php 
								}
							    ?>
						</fieldset>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
		<p><br />
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br />
	</form>
	<br />
<?php
	if($btn_acao == "submit"){
		if(strlen ($msg_erro["msg"]) == 0  AND pg_num_rows($resSubmit) > 0){
?>
			<table id="resultado_atendimentos" class = 'table table-striped table-bordered table-hover table-fixed'>
				<thead>
					<tr class = 'titulo_coluna'>
					<?php
						if(in_array($agrupar,array("n","a"))){
							echo "<th>Login</th>";
						}
						if(in_array($agrupar,array("n","p")) and $moduloProvidencia){
							echo "<th>Providência</th>";
						}
						
						if(in_array($login_fabrica, array(169,170)) AND $agrupar == 'n'){
							echo "<th>Origem</th>";
						}

						if ((in_array($login_fabrica, array(174)) || $telecontrol_distrib) && in_array($agrupar,array("c","n")) and $moduloProvidencia) { ?>
							<th>Classificação</th>
					<?php
						}
					?>
						<th>Geral</th>
						<th>Atrasado</th>
						<th>Em Dia</th>
						<th>Hoje</th>
					</tr>
				</thead>
				<tbody>
					<?php
						for($i = 0; $i < $count; $i++){
							$login 			= pg_fetch_result($resSubmit, $i, 'login');
							$providencia	= pg_fetch_result($resSubmit, $i, 'providencia');
							$total_geral	= pg_fetch_result($resSubmit, $i, 'total_geral');
							$em_atraso		= pg_fetch_result($resSubmit, $i, 'em_atraso');
							$em_dia 		= pg_fetch_result($resSubmit, $i, 'em_dia');
							$hoje			= pg_fetch_result($resSubmit, $i, 'hoje');
							$origem 		= pg_fetch_result($resSubmit, $i, 'origem');
							$classificacao  = pg_fetch_result($resSubmit, $i, 'classificacao');
							$atendente 		= pg_fetch_result($resSubmit, $i, 'atendente');

							if(in_array($agrupar,array("n","a"))){
								$tdLogin = "<td class= 'tal'>{$login}</td>";
							}

							if(in_array($agrupar,array("n","p"))and $moduloProvidencia){
								$tdProcedencia = "<td class= 'tal'>{$providencia}</td>";
							}
							if(in_array($login_fabrica, array(169,170)) AND $agrupar == 'n'){
								$tdOrigem = "<td class='tal'>{$origem}</td>";
							}

							if ((in_array($login_fabrica, array(174)) || $telecontrol_distrib) && in_array($agrupar,array("c","n")) and $moduloProvidencia) {
								$tdClass = "<td class='tal'>{$classificacao}</td>";
							} 

							$body .= "<tr>
										$tdLogin
										$tdProcedencia
										$tdOrigem
										$tdClass
										<td class= 'tac'><a href='#' class='explodir' data-parametro='geral' data-atendente='$atendente'  data-providencia='$providencia' data-classificacao='$classificacao'>{$total_geral}</a></td>
										<td class= 'tac'><a href='#' class='explodir' data-parametro='atrasado' data-atendente='$atendente' data-providencia='$providencia' data-classificacao='$classificacao'>{$em_atraso}</a></td>
										<td class= 'tac'><a href='#' class='explodir' data-parametro='em_dia' data-atendente='$atendente' data-providencia='$providencia' data-classificacao='$classificacao'>{$em_dia}</a></td>
										<td class= 'tac'><a href='#' class='explodir' data-parametro='hoje' data-atendente='$atendente'  data-providencia='$providencia' data-classificacao='$classificacao'>{$hoje}</a></td>
									</tr>";
						}
						echo $body;
					?>
				</tbody>
			</table>
			<?php
			if ($count > 50) {
			?>
				<script>
					$.dataTableLoad({ table: "#resultado_atendimentos" });
				</script>
			<?php
			}

				$jsonPOST = excelPostToJson($_POST);
			?>
			<br />

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</div>
<?php
		}else{
			echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
		}
	}

include 'rodape.php';
?>
