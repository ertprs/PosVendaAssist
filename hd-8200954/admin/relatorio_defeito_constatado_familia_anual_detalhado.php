<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";
$admin_privilegios="gerencia";
$layout_menu = 'gerencia';
include "funcoes.php";

if ($_POST["Pesquisar"] == "Pesquisar") {
	$acao = $_POST["Pesquisar"];
}

$title = "RELATÓRIO ANUAL DE OS POR DEFEITOS CONSTATADOS E POR FAMÍLIA";

if(strlen($_POST['codigo_posto']) > 0 ) {

	$sql_posto = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '".$_POST['codigo_posto']."'";
	$res_posto = pg_query($con,$sql_posto);
	if(pg_numrows($res_posto)) {
		$cod_posto = pg_fetch_result($res_posto,0,posto);
		$cond_posto  = ' AND tbl_posto_fabrica.posto = ' . $cod_posto . '';
	}else{
		$msg_erro = 'Posto Não Encontrado';
	}

}

if(strlen($_POST['estado']) > 0) {
	$cond_estado = " AND bi_os.estado = '".$_POST['estado']."'";
}

//VALIDA OS CAMPOS
if ($acao == "Pesquisar" && strlen($msg_erro) == 0) {
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

if ($acao == "Pesquisar" && strlen($msg_erro) == 0 ) {
	$ano_atual = intval(date("Y"));

	if(strlen($mi) > 0 ) {
		$mes_inicial = $mi;
		$mes_final   = $mf;

		$cond_mes = "AND mes = '{$mi}' ";
	}else{
		$mes_inicial = 01;
		$mes_final = 12;
	}

	$data_ini	 = date("$ano-$mes-01");
	$d			 = date('t', strtotime($data_ini));

	if(strlen($_POST['familia']) > 0 ){
		$cond_familia  = ' AND bi_os.familia = ' . $_POST['familia'];
	}


	$sql = "SELECT distinct
				bi_os.os,
				bi_os.serie,
                bi_os.familia,
                tbl_familia.descricao AS familia_descricao,
                bi_os.posto,                
                tbl_posto.nome AS posto_nome,
                tbl_produto.descricao AS prod_descricao,
                tbl_produto.referencia AS prod_referencia,
                tbl_defeito_constatado.codigo AS defeito_constatado_codigo,
                tbl_defeito_constatado.descricao AS defeito_desc,
	            tbl_defeito_constatado_grupo.defeito_constatado_grupo,
	            tbl_defeito_constatado_grupo.grupo_codigo,
	            tbl_defeito_constatado_grupo.descricao AS defeito_constatado_grupo_descricao,
                bi_os.defeito_constatado,
                tbl_hd_chamado_item.hd_chamado,
                tbl_marca.nome as marca_nome,
                TO_CHAR(bi_os.data_finalizada, 'MM') AS mes
                INTO TEMP TABLE tmp_os_familia_tudo
            FROM bi_os
           		JOIN tbl_familia ON bi_os.familia=tbl_familia.familia
            	JOIN tbl_defeito_constatado ON bi_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado
            	JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado.defeito_constatado_grupo = tbl_defeito_constatado_grupo.defeito_constatado_grupo AND tbl_defeito_constatado_grupo.fabrica = $login_fabrica
            	JOIN tbl_produto ON bi_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
            	--JOIN tbl_os ON tbl_os.os = bi_os.os AND tbl_os.fabrica = $login_fabrica
            	JOIN tbl_marca ON bi_os.marca = tbl_marca.marca
            	JOIN tbl_posto_fabrica ON bi_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            	JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
            	LEFT JOIN tbl_hd_chamado_item ON bi_os.os = tbl_hd_chamado_item.os

            WHERE bi_os.fabrica=$login_fabrica
                AND bi_os.data_finalizada BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
                AND bi_os.excluida IS NOT TRUE
                $cond_familia
                $cond_posto
                $cond_estado
            ORDER BY bi_os.familia, mes,grupo_codigo,defeito_desc;
            SELECT * FROM tmp_os_familia_tudo;
	         ";
	$res_c = pg_query($con, $sql);
	// echo nl2br($sql);exit;

	$meses = array('01' => "Janeiro", '02' => "Fevereiro", '03' => "Março", '04' => "Abril", '05' => "Maio", '06' => "Junho", '07' => "Julho", '08' => "Agosto", '09' => "Setembro", '10' => "Outubro", '11' => "Novembro", '12' => "Dezembro");

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($res_c)>0) {
			$sql_e = "SELECT familia,mes,count(*) as total  FROM tmp_os_familia_tudo GROUP BY familia,mes ORDER BY familia;";
			$res_e = pg_query($con,$sql_e);

			for ($x=0; $x < pg_num_rows($res_e) ; $x++) { 
				$mes_ep[pg_fetch_result($res_e, $x, familia)][pg_fetch_result($res_e, $x, mes)] = pg_fetch_result($res_e, $x, total);
			}

			$data = date("d-m-Y-H-i");
			$fileName = "relatorio_defeito_constatado_familia_anula_detalhado_{$data}.csv";
			$file = fopen("/tmp/{$fileName}", "w");

			$head ="Família;Código Defeito;Grupo Defeito;Defeito Constatado;Marca";
		
			$w_mes_inicial = strtotime($aux_data_inicial);
			$w_mes_final = strtotime($aux_data_final);
			while( $w_mes_inicial <= $w_mes_final ){
				$head .= ";OS" . $meses[date('m',$w_mes_inicial)] . ";% Mês" . $meses[date('m',$w_mes_inicial)] . ";";
				$w_mes_inicial = strtotime('+1 months',$w_mes_inicial);			
			}
			$head .="OS;Atendimento;Série;Produto;Posto\r\n";
			
			fwrite($file, $head);
			$body = '';

			for ($x=0; $x<pg_num_rows($res_c);$x++){
				$ex_os 									= pg_fetch_result($res_c, $x, os);
				$ex_defeito_constatado					= intval(pg_fetch_result($res_c, $x, defeito_constatado));
				$ex_count_os							= intval(pg_fetch_result($res_c, $x, count_os));
				$ex_mes									= intval(pg_fetch_result($res_c, $x, mes));
				$ex_familia								= floatval(pg_fetch_result($res_c, $x, familia));
				$ex_familia_descricao					= pg_fetch_result($res_c, $x, familia_descricao);
				$ex_defeito_constatado_codigo			= pg_fetch_result($res_c, $x, defeito_constatado_codigo);
				$ex_marca_nome 							= pg_fetch_result($res_c, $x, marca_nome);
				$ex_posto_nome 							= pg_fetch_result($res_c, $x, posto_nome);
				$ex_prod_descricao  					= pg_fetch_result($res_c, $x, prod_descricao);
				$ex_prod_referencia  					= pg_fetch_result($res_c, $x, prod_referencia);
				$ex_prod_serie     					   	= pg_fetch_result($res_c, $x, serie);
				$ex_defeito_constatado_grupo   		   	= pg_fetch_result($res_c, $x, defeito_constatado_grupo);
				$ex_defeito_constatado_grupo_descricao 	= pg_fetch_result($res_c, $x, defeito_constatado_grupo_descricao);
				$ex_defeito_constatado_descricao       	= pg_fetch_result($res_c, $x, defeito_desc);
				$ex_hd_chamado       				   	= pg_fetch_result($res_c, $x, hd_chamado);

				$body .= $ex_familia_descricao.";".$ex_defeito_constatado_codigo.";".$ex_defeito_constatado_grupo_descricao.";".$ex_defeito_constatado_descricao.";".$ex_marca_nome;

				$w_mes_inicial = strtotime($aux_data_inicial);
				$w_mes_final = strtotime($aux_data_final);
				while( $w_mes_inicial <= $w_mes_final ){
					if (date('m',$w_mes_inicial) == $ex_mes) {
						$porc_mes = 100/$mes_ep[$ex_familia][date('m',$w_mes_inicial)];
						$body .= ";1;" . number_format($porc_mes, 2, ",", "") . "%";
					}else{
						$body .= ";0;0,00%";
					}
					$w_mes_inicial = strtotime('+1 months',$w_mes_inicial);			
				}
				$body .= ";".$ex_os.";".$ex_hd_chamado.";".$ex_prod_serie.";".$ex_prod_referencia." - ".$ex_prod_descricao.";".$ex_posto_nome;
				$body .= "\r\n";
			}

			$body = $body;
		    fwrite($file, $body);
		    fclose($file);
		    if (file_exists("/tmp/{$fileName}")) {

                system("mv /tmp/{$fileName} xls/{$fileName}");

                echo "xls/{$fileName}";
			}
		}
		exit;
	}
}
include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"ajaxform"
);

include("plugin_loader.php");
?>
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

</script>

<? 
if (strlen($msg_erro) > 0) { ?>
	<div class="alert alert-error">
		<h4><?=$msg_erro?></h4>
    </div>
<? 
}?>

<form name="frm_busca" method="POST" action="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
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
								$res_g = pg_query($con,$sql);
								foreach (pg_fetch_all($res_g) as $key) {
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
		<input type="submit" class="btn" name="Pesquisar" value="Pesquisar" />
	</p><br/>


</form>
</div>
<?
if ($acao == "Pesquisar" && strlen($msg_erro) == 0) {
	if (pg_num_rows($res_c) > 0) {

		$familia_anterior = pg_fetch_result($res_c, 0, familia);
		$familia_ant_descricao = strtoupper(pg_fetch_result($res_c, 0, familia_descricao));
		$colspan_t = 2;

		$sql_f = "SELECT	mes, 
							count(*)  as total
					FROM tmp_os_familia_tudo 
					WHERE familia = $familia_anterior
					GROUP BY mes; ";
		$res_f = pg_query($con,$sql_f);

		for ($y=0; $y < pg_num_rows($res_f) ; $y++) { 
			$mes_p[pg_fetch_result($res_f, $y, mes)] = pg_fetch_result($res_f, $y, total); 
			$tot_meses = $tot_meses + pg_fetch_result($res_f, $y, total);
		}
		$porc_meses = 100/$tot_meses;
		?>
		<br />
		<table class='table table-striped table-bordered table-fixed'>
			<!-- <thead> -->
				<tr class='titulo_coluna'>
					<th align='left' colspan='100%'><?=$familia_ant_descricao?></th>
				</tr>
				<tr class='titulo_coluna'>
					<th>Código Defeito</th>
					<th>Grupo Defeito</th>
					<th>Defeito Constatado</th>
					<th>Marca</th>
					<?php
					//if (1==2) {						
					$w_mes_inicial = strtotime($aux_data_inicial);
					$w_mes_final = strtotime($aux_data_final);
					while( $w_mes_inicial <= $w_mes_final ){?>
						<th>OS <?=$meses[date('m',$w_mes_inicial)]?></th>
						<th>% Mês <?=$meses[date('m',$w_mes_inicial)]?></th>
					<?php
						$colspan_t++;								
						$w_mes_inicial = strtotime('+1 months',$w_mes_inicial);			
					}
					//}
					?>
					<th>Total OS</th>
					<th>%</th>
					<th>OS</th>
					<th>Atendimento</th>
					<th>Série</th>
					<th>Produto</th>
					<th>Posto</th>
				</tr>
			<!-- </thead> -->
		<?php
		$qtde_os = 0;
		for($i = 0; $i < pg_num_rows($res_c); $i++) {
			$qtde_os++;
			$os 								= pg_fetch_result($res_c, $i, os);
			$defeito_constatado					= intval(pg_fetch_result($res_c, $i, defeito_constatado));
			$count_os							= intval(pg_fetch_result($res_c, $i, count_os));
			$mes								= pg_fetch_result($res_c, $i, mes);
			$familia							= floatval(pg_fetch_result($res_c, $i, familia));
			$defeito_constatado_codigo			= pg_fetch_result($res_c, $i, defeito_constatado_codigo);
			$marca_nome 						= pg_fetch_result($res_c, $i, marca_nome);
			$posto_nome 						= pg_fetch_result($res_c, $i, posto_nome);
			$prod_descricao  					= pg_fetch_result($res_c, $i, prod_descricao);
			$prod_referencia  					= pg_fetch_result($res_c, $i, prod_referencia);
			$prod_serie     					= pg_fetch_result($res_c, $i, serie);
			$defeito_constatado_grupo   		= pg_fetch_result($res_c, $i, defeito_constatado_grupo);
			$defeito_constatado_grupo_descricao = pg_fetch_result($res_c, $i, defeito_constatado_grupo_descricao);
			$defeito_constatado_descricao       = pg_fetch_result($res_c, $i, defeito_desc);
			$hd_chamado       = pg_fetch_result($res_c, $i, hd_chamado);

			if ($familia != $familia_anterior) {
				$familia_anterior = $familia;
				$familia_descricao = pg_fetch_result($res_c, $i, familia_descricao);

				$sql_f = "SELECT	mes, 
									count(*)  as total
							FROM tmp_os_familia_tudo 
							WHERE familia = $familia
							GROUP BY mes; ";
				$res_f = pg_query($con,$sql_f);
				$tot_meses = 0;
				for ($y=0; $y < pg_num_rows($res_f) ; $y++) { 
					$mes_p[pg_fetch_result($res_f, $y, mes)] = pg_fetch_result($res_f, $y, total); 
					$tot_meses = $tot_meses + pg_fetch_result($res_f, $y, total);
				}
				$porc_meses = 100/$tot_meses;
				//print_r($mes_p);
				?>
				<tr>
					<td colspan='100%'></td>
				</tr>
				<tr class='titulo_coluna'>
					<th align='left' colspan='100%'><?=$familia_descricao?></th>
				</tr>
				<tr class='titulo_coluna'>
					<th>Código Defeito</th>
					<th>Grupo Defeito</th>
					<th>Defeito Constatado</th>
					<th>Marca</th>
					<?php
					//if (1==2) {						
					$w_mes_inicial = strtotime($aux_data_inicial);
					$w_mes_final = strtotime($aux_data_final);
					while( $w_mes_inicial <= $w_mes_final ){?>
						<th>OS <?=$meses[date('m',$w_mes_inicial)]?></th>
						<th>% Mês <?=$meses[date('m',$w_mes_inicial)]?></th>
					<?php
						$w_mes_inicial = strtotime('+1 months',$w_mes_inicial);
					}
					//}
					?>
					<th>Total OS</th>
					<th>%</th>
					<th>OS</th>
					<th>Atendimento</th>
					<th>Série</th>
					<th>Produto</th>
					<th>Posto</th>
				</tr>
			<?				
			}?>
			<!-- <tbody> -->
				<tr bgcolor='#F7F5F0'>
					<td><?=$defeito_constatado_codigo?></td>
					<td nowrap><?=$defeito_constatado_grupo_descricao?></td>
					<td><?=$defeito_constatado_descricao?></td>
					<td><?=$marca_nome?></td>
					<?php
					//if (1==2) {
					$w_mes_inicial = strtotime($aux_data_inicial);
					$w_mes_final = strtotime($aux_data_final);
					
					while( $w_mes_inicial <= $w_mes_final ){
						if (date('m',$w_mes_inicial) == $mes) {
							$porc_mes = 100/$mes_p[date('m',$w_mes_inicial)];							
							?>
							<td>1</td>
							<td><?php echo number_format($porc_mes, 2, ",", "") ?>%</td>
						<?php
						}else{?>
							<td>0</td>
							<td>0,00%</td>
						<?php
						}
						$w_mes_inicial = strtotime('+1 months',$w_mes_inicial);			
					}					
					?>
					<td>1</td>
					<td><?=number_format($porc_meses,2,',','')?>%</td>
					<?php
					//}
					?>
					<td><a href='os_press.php?os=<?=$os?>' target='_blank'><?=$os?></a></td>
					<td><a href='callcenter_interativo_new.php?callcenter=<?=$hd_chamado?>' target='_blank'><?=$hd_chamado?></a></td>
					<td><?=$prod_serie?></td>
					<td><?=$prod_referencia?> - <?=$prod_descricao?></td>
					<td><?=$posto_nome?></td>
				</tr>
			<!-- </tbody> -->
			<?php			
			$familia_posterior = pg_fetch_result($res_c, $i+1, familia);
			if ($familia != $familia_posterior) {
				$colspan_t = "4";			
			?>
			<!-- <tfoot style='font-weight: bold;'> -->
				<tr style='font-weight: bold;'>
					<td colspan='<?=$colspan_t?>'>TOTAL GERAL</td>
					<?php
					//if (1==2) {					
					$w_mes_inicial = strtotime($aux_data_inicial);
					$w_mes_final = strtotime($aux_data_final);
					while( $w_mes_inicial <= $w_mes_final ){
						$mes_w = date('m',$w_mes_inicial);						
						if (!empty($mes_p[$mes_w])) {
							$porc_mes = 100/$mes_p[date('m',$w_mes_inicial)];
							?>
							<td><?=$mes_p[date('m',$w_mes_inicial)]?></td>
							<td>100%</td>
						<?php
						}else{?>
							<td>0</td>
							<td>0,00%</td>
						<?php
						}
						$w_mes_inicial = strtotime('+1 months',$w_mes_inicial);			
					}
					//}
					?>
					<td><?=$qtde_os?></td>
					<td>100%</td>
					<td colspan="5"></td>
				</tr>
			<!-- </tfoot> -->
			<?php
			$qtde_os = 0;
			}		
		}?>
		</table>
		<br />
		<?php
			$jsonPOST = excelPostToJson($_POST);
		?>

		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt">Gerar Excel</span>
		</div>
	<?		
	} else { ?>
		<div class="container">
			<div class="alert">
			    <h4>Nenhum resultado encontrado</h4>
			</div>
		</div>
	<?php
	}
}
?>
<br />
<?php
include "rodape.php";
?>
