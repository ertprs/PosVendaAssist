<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";
$admin_privilegios="gerencia";
$layout_menu = 'gerencia';
include "funcoes.php";

if (strlen($_POST["acao"]) > 0) $acao = $_POST["acao"];

if ($_POST["acao"] == 0 && strtoupper($_GET["acao"]) == "PESQUISAR") {
	$acao = $_GET["acao"];
}

$acao = trim(strtoupper($acao));

$title = "RELATÓRIO ANUAL DE OS POR DEFEITOS CONSTATADOS E POR FAMÍLIA";



if ($excel) {
	ob_start();
}
else {
	include "cabecalho_new.php";
}

	$plugins = array(
		"autocomplete",
		"datepicker",
		"shadowbox",
		"mask",
		"dataTable"
	);

include("plugin_loader.php");
?>

</style>

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
	});

	function retorna_posto(retorno){
      $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
   }

   function expande(ordem) {
    var elemento = document.getElementById('completo_' + ordem);
    var display = elemento.style.display;
    if (display == "none") {
      elemento.style.display = "";
      $('#icone_expande_' + ordem ).removeClass('icon-plus').addClass('icon-minus');
    } else {
      elemento.style.display = "none";
      $('#icone_expande_' + ordem ).removeClass('icon-minus').addClass('icon-plus');
    }
  }

</script>

<?php
	if(strlen($_GET['codigo_posto']) > 0 ) {

		$sql_posto = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '".$_GET['codigo_posto']."'";
		$res_posto = pg_query($con,$sql_posto);
		if(pg_numrows($res_posto)) {
			$cod_posto = pg_result($res_posto,0,posto);
			$cond_posto  = ' AND posto = ' . $cod_posto . '';
		}
		else
			$msg_erro = 'Posto Não Encontrado';

	}

	if(strlen($_GET['estado']) > 0) {
		$cond_estado = " AND bi_os.estado = '".$_GET['estado']."'";
	}

    //VALIDA OS CAMPOS
    if ($acao == "PESQUISAR" && strlen($msg_erro) == 0) {
        $data_inicial = $_REQUEST['data_inicial'];
	    $data_final   = $_REQUEST['data_final'];

        list($di, $mi, $yi) = explode("/", $data_inicial);
		if(@!checkdate($mi,$di,$yi))
			$msg_erro = "Data inicial inválida!";


		if(empty($msg_erro)){
            list($df, $mf, $yf) = explode("/", $data_final);
            if(@!checkdate($mf,$df,$yf))
                $msg_erro = "Data final inválida!";
		}

		if(empty($msg_erro)){
            $aux_data_inicial = "$yi-$mi-$di";
            $aux_data_final = "$yf-$mf-$df";

            $mes_inicial = $mi;
            $mes_inicial = $mf;
		}

		if(empty($msg_erro)){
			if(strtotime($aux_data_final) < strtotime($aux_data_inicial) or strtotime($aux_data_final) > strtotime('today')){
				$msg_erro = "Data inválida!";
			}
		}

        if(empty($msg_erro)){
            $sql = "SELECT '$aux_data_inicial'::date + interval '2 months' > '$aux_data_final'";
            $res = pg_query($con,$sql);
            $periodo = pg_fetch_result($res,0,0);
            if($periodo == 'f'){
                $msg_erro = "O período não podem ser maior que dois meses";
            }
		}


    }
?>

<? if (strlen($msg_erro) > 0) { ?>

	<div class="alert alert-error">
		<h4><?=$msg_erro?></h4>
    </div>
<? } ?>

<? if (strlen($_GET["msg"]) > 0) { ?>
	<!-- <br>
	<table width="700" border="0" cellspacing="0" cellpadding="0" align="center">
		<tr class="texto_avulso">
			<td colspan="4" height='25'><? echo $_GET["msg"]; ?></td>
		</tr>
	</table> -->
<? }

if ($excel) {
?>
<style type="text/css">
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	    padding: 2px 0;
	}
	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.msg_erro{
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	.texto_avulso{
		font: 14px Arial; color: rgb(89, 109, 155);
		background-color: #d9e2ef;
		text-align: center;
		width:700px;
		margin: 0 auto;
	}
</style>
<?php
}
else {
?>

<form name="frm_busca" method="GET" action="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
	<input type="hidden" name="acao">

	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<!-- Inicio Linha -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='familia'>Familia</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="familia" id="familia">
							<option value=""></option>
							<?php
								$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica and ativo order by descricao";
								$res = pg_query($con,$sql);
								foreach (pg_fetch_all($res) as $key) {
									$selected_familia = ( isset($familia) and ($familia == $key['familia']) ) ? "SELECTED" : '' ;
								?>
									<option value="<?php echo $key['familia']?>" <?php echo $selected_familia ?> >
										<?php echo $key['descricao']?>
									</option>
								<?php
								}
							?>
						</select>
					</div>
					<div class='span2'></div>
				</div>
			</div>
		</div>
	</div>
	<!-- Fim Linha -->

	<!-- Inicio Data Inicial/Data Final -->
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
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- Fim Data Inicial/Data Final -->

	<!-- Código Posto/ Descrição Posto -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'>Código Posto</label>
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
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='descricao_posto'>Nome Posto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- Fim Código Posto/ Descrição Posto -->

	<!-- Marca/Estado -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='estado'>Estado</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<select name="estado" id="estado">
							<option value=""></option>
							<?php
								$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
									"AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
									"ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
									"MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
									"PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
									"RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
									"RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
									"SP"=>"SP - São Paulo","TO"=>"TO - Tocantins"
								);

								foreach ($array_estado as $k => $v) {
									echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<!-- Fim Marca/Estado -->

	<p><br/>
		<input class='btn' type="submit" onclick="javascript: document.frm_busca.acao.value='PESQUISAR'; document.frm_busca.submit();" style="cursor:pointer " value="Pesquisar" name="gerar"/>
	</p><br/>


</form>
</div>
<?
} //if ($excel)

if ($acao == "PESQUISAR" && strlen($msg_erro) == 0) {
	$ano_atual = intval(date("Y"));

	if(strlen($mi) > 0 ) {
		$mes_inicial = $mi;
		$mes_final   = $mf;

		$cond_mes = "AND mes = '{$mi}' ";
	}
	else{
		$mes_inicial = 01;
		$mes_final = 12;
	}

	$data_ini	 = date("$ano-$mes-01");
	$d			 = date('t', strtotime($data_ini));

	if(strlen($_GET['familia']) > 0 )
		$cond_familia  = ' AND bi_os.familia = ' . $_GET['familia'];


	$sql = "SELECT 
	                bi_os.familia,
	                bi_os.posto,
	                tbl_defeito_constatado.codigo AS defeito_constatado_codigo,
	                bi_os.defeito_constatado,
	                tbl_familia.descricao,
	                tbl_defeito_constatado.defeito_constatado_grupo,
	                bi_os.os,
	                TO_CHAR(bi_os.data_finalizada, 'MM') AS mes
	                INTO TEMP TABLE tmp_os_familia_tudo
	            FROM bi_os
               		JOIN tbl_familia ON bi_os.familia=tbl_familia.familia
	            	JOIN tbl_defeito_constatado ON bi_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado
					--JOIN tbl_os ON tbl_os.os = bi_os.os AND tbl_os.fabrica = $login_fabrica
	            WHERE bi_os.fabrica=$login_fabrica
	                AND bi_os.data_finalizada BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
	                AND bi_os.excluida IS NOT TRUE
	                $cond_familia
	                $cond_posto
	                $cond_estado
	         ";
	$res = pg_query($con, $sql);
	// echo nl2br($sql);

	$sql = "SELECT 
							familia,
							defeito_constatado_codigo,
							defeito_constatado,
							descricao,
							defeito_constatado_grupo,
							mes,
							COUNT(os) AS count_os
							INTO TEMP TABLE tmp_os_familia
					FROM tmp_os_familia_tudo
					GROUP BY 
								familia,
								defeito_constatado_codigo,
								defeito_constatado,
								descricao,
								defeito_constatado_grupo,
								mes
					ORDER BY descricao,
								defeito_constatado_codigo,
								defeito_constatado_grupo,
								mes;
					SELECT * FROM tmp_os_familia;
					";
	$res = pg_query($con, $sql);

	if (pg_numrows($res) > 0) {
		$defeitos = array();
		//$meses = array("Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
		$meses = array('01' => "Janeiro", '02' => "Fevereiro", '03' => "Março", '04' => "Abril", '05' => "Maio", '06' => "Junho", '07' => "Julho", '08' => "Agosto", '09' => "Setembro", '10' => "Outubro", '11' => "Novembro", '12' => "Dezembro");
		$ultimo_mes = $mf;
		$familia_anterior = floatval(pg_result($res, 0, familia));
		$sql = "SELECT descricao FROM tbl_familia WHERE familia=" . $familia_anterior;
		$res_familia = pg_query($con, $sql);
		$familia_ant_descricao = strtoupper(pg_result($res_familia, 0, descricao));

		if(strlen($_GET['familia']) > 0 )
			$cond_familia  = ' AND familia = ' . $_GET['familia'];

		$sql = "
		SELECT SUM(count_os) as total
		FROM tmp_os_familia
		WHERE 1 = 1
		$cond_familia
		";
		$res_total	= pg_query($con, $sql);
		$todas_os	= pg_result($res_total,0,total);
		//die($sql);

		for($i = 0; $i < pg_num_rows($res); $i++) {
			$os 						= pg_result($res, $i, os);
			$defeito_constatado			= intval(pg_result($res, $i, defeito_constatado));
			$count_os					= intval(pg_result($res, $i, count_os));
			$mes_t						= pg_result($res, $i, mes);
			$familia					= floatval(pg_result($res, $i, familia));
			$defeito_constatado_codigo	= pg_result($res, $i, defeito_constatado_codigo);

			$defeitos[$defeito_constatado . "|" . $familia . "|" . $defeito_constatado_codigo ][$mes_t]["os"] = $count_os;
			$defeitos[$defeito_constatado . "|" . $familia . "|" . $defeito_constatado_codigo ][$mes_t]["tempo"] = $tempo_atendimento;
			$defeitos[$defeito_constatado . "|" . $familia . "|" . $defeito_constatado_codigo ][$mes_t]["valor"] = $sum_mao_de_obra;
		}

		echo "<br />";

		if($excel){
			echo "<table class='tabela' cellspacing='1' cellpadding='0' align='center'>";
		}else{
			echo "<table class='table table-striped table-bordered table-fixed'>";
		}

		echo "<thead>
			<tr class='titulo_coluna'>
				<th align='left' colspan='100%'> $familia_ant_descricao</th>
			</tr>
			<tr class='titulo_coluna'>
				<th>Código Defeito</th>
				<th>Grupo Defeito</th>
				<th>Defeito</th>
				";
		$w_mes_inicial = strtotime($aux_data_inicial);
		$w_mes_final = strtotime($aux_data_final);
		while( $w_mes_inicial <= $w_mes_final ){
			echo "
				<th>OS " . $meses[date('m',$w_mes_inicial)] . "</th>
				<th>% Mês " . $meses[date('m',$w_mes_inicial)] . "</th>";
			$w_mes_inicial = strtotime('+1 months',$w_mes_inicial);			
		}
		if (1==2) {
		for($i = ($mes_inicial-1); $i < $ultimo_mes; $i++) {
			echo "
				<th> OS " . $meses[$i] . "</th>
				<th> % Mês " . $meses[$i] . "</th>";
		}
		}
		echo "
				<th>Total OS</th>
				<th>%</th>
			</tr>
		</thead>
		<tbody>";

		/* fim do cabecalho */

		$total_geral_os_mensal = array();
		$total_geral_tempo_mensal = array();
		$total_geral_os = 0;
		$count =0;

		foreach($defeitos as $defeito_constatado_familia => $mes_array) {
			$count++;
			$partes = explode("|", $defeito_constatado_familia);
			$defeito_constatado = intval($partes[0]);
			$familia = intval($partes[1]);
			$defeito_constatado_codigo = $partes[2];

			if($login_fabrica == 52){
			}

			//dados primeira familia, quando tiver filtro por familia
			if($count == 1 && strlen($_GET['familia']==0)) {
				$sql_tf = "
				SELECT SUM(count_os) as total_familia
				FROM tmp_os_familia
				WHERE familia = $familia
				$cond_mes;";

				$res_tf = pg_query($con,$sql_tf);
				$total_os_familia = pg_result($res_tf,0,total_familia);
			}

			/* dados do defeito constatado */
			$sql = "
			SELECT
			tbl_defeito_constatado.descricao,
			tbl_defeito_constatado_grupo.defeito_constatado_grupo,
			tbl_defeito_constatado_grupo.descricao AS defeito_constatado_grupo_descricao

			FROM
			tbl_defeito_constatado
			JOIN tbl_defeito_constatado_grupo USING(defeito_constatado_grupo)

			WHERE
			defeito_constatado=$defeito_constatado
			";
			$res = pg_query($con, $sql);
			$defeito_constatado_descricao = pg_result($res, 0, descricao);
			$defeito_constatado_grupo = pg_result($res, 0, defeito_constatado_grupo);
			$defeito_constatado_grupo_descricao = pg_result($res, 0, defeito_constatado_grupo_descricao);

			/* dados da familia */
			$sql = "SELECT descricao FROM tbl_familia WHERE familia=" . $familia;
			$res_familia = pg_query($con, $sql);
			$familia_descricao = strtoupper(pg_result($res_familia, 0, descricao));

			/* totalizando por familia, e novo cabecalho pra cada familia (sem filtro) */
			if($familia != $familia_anterior) {

				$colspan =  3;
				$colspan_b =  5;

				echo "
				<tr style='font-weight: bold;'>
				<td colspan=$colspan>TOTAL GERAL</td>
				";

				$total_geral_os_familia = 0;
				//if (1==2) {						
				$w_mes_inicial = strtotime($aux_data_inicial);
				$w_mes_final = strtotime($aux_data_final);
				while( $w_mes_inicial <= $w_mes_final ){
					$colspan_b = $colspan_b + 2;
					$mes = date('m',$w_mes_inicial);
					$cond_mes = "AND mes = '{$mes}' ";
					//sql pra pegar o total de os por familia
					$sql_tf = "
					SELECT SUM(count_os) as total_familia
					FROM tmp_os_familia
					WHERE familia = $familia_anterior
					$cond_mes;";
					//echo $sql_tf."<br>";
					$res_tf = pg_query($con,$sql_tf);
					$total_os_familia = pg_result($res_tf,0,total_familia);

					if(is_null($total_os_familia))
						$total_os_familia = 0;

					$total_geral_os_familia += $total_os_familia; //total, somando todos os meses.

					$percentual = $total_os_familia > 0 ? '100%' : '0%';

					echo "
						<td>" . $total_os_familia . "</td>
						<td>".$percentual."</td>";						
					$w_mes_inicial = strtotime('+1 months',$w_mes_inicial);			
				}
				//}
				if (1==2) {
				for ($m = intval($mes_inicial); $m <= $ultimo_mes; $m++) {
                    $mes = str_pad($m, 2, "0", STR_PAD_LEFT);

					$cond_mes = "AND mes = '{$mes}' ";

					if($mes != $m)
						continue;

					//sql pra pegar o total de os por familia
					$sql_tf = "
					SELECT SUM(count_os) as total_familia
					FROM tmp_os_familia
					WHERE familia = $familia_anterior
					$cond_mes;";
					//echo $sql_tf."<br>";
					$res_tf = pg_query($con,$sql_tf);
					$total_os_familia = pg_result($res_tf,0,total_familia);

					if(is_null($total_os_familia))
						$total_os_familia = 0;

					$total_geral_os_familia += $total_os_familia; //total, somando todos os meses.

					$percentual = $total_os_familia > 0 ? '100%' : '0%';

					echo "
						<td>" . $total_os_familia . "</td>
						<td>".$percentual."</td>";
				}
			}

				echo "
						<td>$total_geral_os_familia</td>
						<td>100%</td>

					</tr>";
				// fim do total

				/* cria novo cabecalho */
				echo "
						<tr>
							<td colspan='$colspan_b'>&nbsp;</td>
						</tr>
						<tr class='titulo_coluna'>
							<th align='left' colspan='100%'> $familia_descricao</th>
						</tr>
						<tr class='titulo_coluna'>
							<th>Código Defeito</th>
							<th width='120'>Grupo Defeito</th>
							<th>Defeito</th>
							";
					$w_mes_inicial = strtotime($aux_data_inicial);
					$w_mes_final = strtotime($aux_data_final);
					while( $w_mes_inicial <= $w_mes_final ){
						echo "
							<th> OS " . $meses[date('m',$w_mes_inicial)] . "</th>
							<th> % Mês " . $meses[date('m',$w_mes_inicial)] . "</th>";
						$w_mes_inicial = strtotime('+1 months',$w_mes_inicial);			
					}
					if (1==2) {						
					for($i = ($mes_inicial -1); $i < $ultimo_mes; $i++){
						echo "
							<th width='80'> OS " . $meses[$i] . "</th>
							<th width='80'> % Mês " . $meses[$i] . "</th>";
					}
					}

					echo "
							<th width=50>Total OS</th>
							<th>%</th>
						</tr>
					";
			}

			//$cor = ($count % 2) ? "#F7F5F0" : "#F1F4FA";
			echo "
			<tr bgcolor='#F7F5F0'>
				<td>$defeito_constatado_codigo</td>
				<td>$defeito_constatado_grupo-$defeito_constatado_grupo_descricao</td>
				<td>$defeito_constatado_descricao</td>";


			$total_os		= 0;
			$count_os		= 0;
			$total_servico	= 0;

			$w_mes_inicial = strtotime($aux_data_inicial);
			$w_mes_final = strtotime($aux_data_final);
			while( $w_mes_inicial <= $w_mes_final ){
				$mes = date('m',$w_mes_inicial);
				$cond_mes = "AND mes = '".$mes."' ";
				if(strlen($_GET['familia']) > 0 )
					$cond_familia  = ' AND familia = ' . $_GET['familia'];
				else
					$cond_familia  = ' AND familia = ' . $familia;

				$sql_pc = "SELECT SUM(count_os) as total
							FROM tmp_os_familia
							WHERE 1 = 1
							$cond_familia
							$cond_mes
							";
				$res_pc = pg_query($con,$sql_pc);
				// echo $sql_pc."<br>";

				$total_geral_os_mensal[$mes] = pg_result($res_pc,0,total); // pega o total de OS

				if(empty($total_geral_os_mensal[$mes])){
					$total_geral_os_mensal[$mes] = 0;
				}
				
				if ($count_os = $mes_array[$mes]["os"]) {
				}else {
					$count_os = 0;
				}

				if($total_geral_os_mensal[$mes] != 0) // calculo de os por mes
					$porc_atendimento[$mes] = ($count_os / $total_geral_os_mensal[$mes]) * 100;
				else
					$porc_atendimento[$mes] = 0;

				// calculo de os por mes/familia

				$total_os						+= $count_os;
				$total_geral_pct_mensal[$mes]	+= $porc_atendimento[$mes];
				$total_geral_os					+= $count_os;

				$total_os_por_familia			= $total_geral_os_mensal[$mes];

				if(strlen($_GET['familia']) == 0 && $total_os_por_familia > 0)
					$porc_atendimento[$mes] = ($count_os / $total_os_por_familia) * 100;

				echo '
				<td>'.$count_os.'</td>
				<td>'.number_format($porc_atendimento[$mes],2,',','').'%</td>';

				$w_mes_inicial = strtotime('+1 months',$w_mes_inicial);			
			}

			//for antigo
			if (1==2) {
			for ($m = intval($mes_inicial); $m <= $ultimo_mes; $m++) {

				if(strlen($_GET['mes'])==0){
					if($m < 10)
						$mes = 0 . $m;
					else
						$mes =$m;

					$cond_mes = "AND mes = '".$mes."' ";
				}

				if($mes != $m)
					continue;

				if(strlen($_GET['familia']) > 0 )
					$cond_familia  = ' AND familia = ' . $_GET['familia'];
				else
					$cond_familia  = ' AND familia = ' . $familia;

				$sql_pc = "SELECT SUM(count_os) as total
							FROM tmp_os_familia
							WHERE 1 = 1
							$cond_familia
							$cond_mes
							";
				//die($sql_pc);
				$res_pc = pg_query($con,$sql_pc);
				$total_geral_os_mensal[$m] = pg_result($res_pc,0,total); // pega o total de OS

				if(empty($total_geral_os_mensal[$m]))
					$total_geral_os_mensal[$m] = 0;

				if ($count_os = $mes_array[$m]["os"]) {
				}else {
					$count_os = 0;
				}

				if($total_geral_os_mensal[$m] != 0) // calculo de os por mes
					$porc_atendimento[$m] = ($count_os / $total_geral_os_mensal[$m]) * 100;
				else
					$porc_atendimento[$m] = 0;

				// calculo de os por mes/familia

				$total_os						+= $count_os;
				$total_geral_pct_mensal[$m]		+= $porc_atendimento[$m];
				$total_geral_os					+= $count_os;

				$total_os_por_familia			= $total_geral_os_mensal[$m];

				if(strlen($_GET['familia']) == 0 && $total_os_por_familia > 0)
					$porc_atendimento[$m] = ($count_os / $total_os_por_familia) * 100;

				echo '
				<td>'.$count_os.'</td>
				<td>'.number_format($porc_atendimento[$m],2,',','').'%</td>';
			}
			}

			if($todas_os >0){
				$media_servico = ($total_os / $todas_os) * 100;
			}

			if(strlen($_GET['familia'])==0 && $total_os_por_familia > 0) {
				$media_servico = ($total_os / $total_os_por_familia) * 100;
			}

			if(strlen($_GET['familia']) == 0 && strlen($_GET['mes'])==0) {
				$sql_tf = "
				SELECT SUM(count_os) as total_familia
				FROM tmp_os_familia
				WHERE familia = $familia
				";

				$res_tf = pg_query($con,$sql_tf);
				$total_os_familia_total = pg_result($res_tf,0,total_familia);
				$media_servico = ($total_os / $total_os_familia_total) * 100;
			}

			if($login_fabrica == 52){
				$expande = $familia.$defeito_constatado_grupo.$defeito_constatado.$count;
			}

			$media_total += $media_servico;

			if($login_fabrica == 52){
				echo "
					<td>$total_os &nbsp;&nbsp;&nbsp;<i style='cursor: pointer;' onClick='expande($expande)' class='icon-plus'></i></td>
					<td>".number_format($media_servico,2,',','')."%</td>
				</tr>";
			}else{
				echo "
					<td>$total_os</td>
					<td>".number_format($media_servico,2,',','')."%</td>
				</tr>";
			}

			if($login_fabrica == 52){

				$sql_os = "SELECT os
								FROM tmp_os_familia_tudo
								WHERE familia = $familia
								AND defeito_constatado_grupo = $defeito_constatado_grupo
								AND defeito_constatado = $defeito_constatado
							";

				$res_os = pg_query($con, $sql_os);
				$count2 = pg_num_rows($res_os);
				$body = "<tr id='completo_$expande' style='display: none;'>
							<td colspan='8'>
								<div class='row-fluid'>
									<table class='table table-striped table-bordered table-fixed'>
										<thead>
											<tr class='titulo_coluna'>
												<th>OS</th>
												<th>Atendimento</th>
												<th>Série</th>
												<th>Produto</th>
												<th colspan='4'>Posto</th>
											</tr>
										</thead>
										<tbody>";
										$id_os = "";
				for ($z=0; $z < $count2; $z++) {
					$id_os = pg_fetch_result($res_os, $z, 'os');

					$slq_dados = "SELECT tbl_hd_chamado_item.hd_chamado,
											tbl_posto.nome,
											tbl_hd_chamado_item.serie,
											tbl_produto.referencia,
											tbl_produto.descricao
										FROM tbl_hd_chamado_item
										JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado_item.hd_chamado
										JOIN tbl_posto ON tbl_posto.posto = tbl_hd_chamado_extra.posto
										JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
										JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_item.produto
										WHERE tbl_hd_chamado_item.os = $id_os
										AND tbl_posto_fabrica.fabrica = $login_fabrica";
					$res_dados = pg_query($con, $slq_dados);
					$count3 = pg_num_rows($res_dados);


										for ($y=0; $y < $count3; $y++) {

											$atendimento 			= pg_fetch_result($res_dados, $y, 'hd_chamado');
											$posto_nome 			= pg_fetch_result($res_dados, $y, 'nome');
											$numero_serie 			= pg_fetch_result($res_dados, $y, 'serie');
											$produto_referencia 	= pg_fetch_result($res_dados, $y, 'referencia');
											$produto_descricao 	= pg_fetch_result($res_dados, $y, 'descricao');

											if($excel){
												$body .="<tr>
															<td>$id_os</td>
															<td>$atendimento</td>";
											}else{
												$body .="<tr>
															<td><a href='os_press.php?os=$id_os'>$id_os</a></td>
															<td><a href='callcenter_interativo_new.php?callcenter=$atendimento'>$atendimento</a></td>";
											}
														$body .="<td>$numero_serie</td>
															<td>$produto_referencia - $produto_descricao</td>
															<td colspan='4'>$posto_nome</td>
														</tr>";
											}

				}
				$body .="		</tbody>
									</table>
								<div>
							</td>
						</tr>";
				if($excel){
					$body .="<tr><td colspan='5'>&nbsp;</td></tr>";
				}

					echo $body;
			}
			$familia_anterior = $familia;
		}

		if(strlen($_GET['familia'] == 0)) { //totaliza a ultima familia
			$colspan =  3;
			echo "
					</tbody>
					<tfoot style='font-weight: bold;'>";

			echo "	<tr>
					<td colspan=$colspan>TOTAL GERAL</td>";
			$total_ultima_familia= 0;
			$w_mes_inicial = strtotime($aux_data_inicial);
			$w_mes_final = strtotime($aux_data_final);
			while( $w_mes_inicial <= $w_mes_final ){
				$mes = date('m',$w_mes_inicial);
				echo "
					<td>" . $total_geral_os_mensal[$mes] . "</td>
					<td>100%</td>";
				$total_ultima_familia += $total_geral_os_mensal[$mes];

				$w_mes_inicial = strtotime('+1 months',$w_mes_inicial);			
			}

			if (1==2) {
			for ($m = intval($mes_inicial); $m <= $ultimo_mes; $m++) {
				if(strlen($_GET['mes'])>0) {
					if($m < 10)
						$mes = 0 . $m;
					else
						$mes =$m;
					if($mes != $_GET['mes'])
						continue;
				}
				echo "
					<td>" . $total_geral_os_mensal[$m] . "</td>
					<td>100%</td>";
				$total_ultima_familia += $total_geral_os_mensal[$m];
			}
			}

			echo "
					<td>$total_ultima_familia</td>
					<td>100%</td>

				</tr>
			</tfoot>
			</table>";
		}

		if(strlen($_GET['familia'] != 0)) { // totaliza quando filtra por familia
			echo "
					</tbody>
					<tfoot style='font-weight: bold;'>";

			$colspan =  3;

			echo "	<tr>
					<td colspan=$colspan>TOTAL GERAL</td>";

			$w_mes_inicial = strtotime($aux_data_inicial);
			$w_mes_final = strtotime($aux_data_final);
			while( $w_mes_inicial <= $w_mes_final ){
				$mes = date('m',$w_mes_inicial);
				echo "
				<td>" . $total_geral_os_mensal[$mes] . "</td>
				<td>" . number_format($total_geral_pct_mensal[$mes], 2, ",", "") . "%</td>";

				$w_mes_inicial = strtotime('+1 months',$w_mes_inicial);			
			}
			if (1 == 2) {					
			for ($m = intval($mes_inicial); $m <= $ultimo_mes; $m++) {
				if(strlen($_GET['mes'])>0) {
					if($m < 10)
						$mes = 0 . $m;
					else
						$mes =$m;
					if($mes != $_GET['mes'])
						continue;
				}
				echo "
					<td>" . $total_geral_os_mensal[$m] . "</td>
					<td>" . number_format($total_geral_pct_mensal[$m], 2, ",", "") . "%</td>";
			}
			}

			echo "
					<td>$total_geral_os</td>
					<td>".number_format($media_total,2,',','')."%</td>

				</tr>
			</tfoot>
			</table>";
		}
	}
	else {
		echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
	}
}
echo "<br>";


if ($excel) {
	$conteudo_excel = ob_get_clean();
	$arquivo = fopen("xls/relatorio_defeito_constatado_os_anual_$login_fabrica$login_admin.xls", "w+");
	fwrite($arquivo, $conteudo_excel);
	fclose($arquivo);
	header("location:xls/relatorio_defeito_constatado_os_anual_$login_fabrica$login_admin.xls");
}else {
	if ($acao == "PESQUISAR" AND strlen(pg_numrows($res) > 0)) {
		echo "
			<div class='row-fluid tac'>
				<button class='btn' onclick=\"window.location='" . $PHP_SELF . "?" . $_SERVER["QUERY_STRING"] . "&excel=1'\"> Download em Excel</button>";
		echo "</div>";
	}

	include "rodape.php";
}
?>
