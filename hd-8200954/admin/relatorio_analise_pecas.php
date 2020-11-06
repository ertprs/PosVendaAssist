<?php
	
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";

	$admin_privilegios = "cadastros";
	include "autentica_admin.php";

	$msg = "";
	$msg_erro = array();

	/* Action Form */

	function valida_date($data = ""){

		if(strlen($data) > 0){

			list($dia, $mes, $ano) = explode("/", $data);
			return checkdate($mes, $dia, $ano);

		}

	}

	function valida_date_maior($data1 = "", $data2 = ""){

		if(strlen($data1) > 0 && strlen($data2) > 0){

			list($d, $m, $a) = explode("/", $data1);
			$data1 = $a."-".$m."-".$d;

			list($d, $m, $a) = explode("/", $data2);
			$data2 = $a."-".$m."-".$d;

			if(strtotime($data2) < strtotime($data1)){
				return false;
			}

			return true;

		}

	}

	function data_limite($data1, $data2){

		list($dia, $mes, $ano) = explode("/", $data1);
		$data1 = $ano."-".$mes."-".$dia;

		list($dia, $mes, $ano) = explode("/", $data2);
		$data2 = $ano."-".$mes."-".$dia;

		$inicio 	= new DateTime($data1);
		$fim 		= new DateTime($data2);
		$interval 	= date_diff($inicio, $fim);

		$interval = $interval->format('%a');

		return ((int)$interval > 366) ? true : false;

	}

	if(isset($_POST["btn_acao"]) || isset($_POST["gerar_excel"])){

		$data_abertura       = $_POST["data_abertura"];
		$data_abertura_final = $_POST["data_abertura_final"];
		$posto               = $_POST["posto"];
		$codigo_posto        = $_POST["codigo_posto"];
		$descricao_posto     = $_POST["descricao_posto"];
		$origem_recebimento  = $_POST["origem_recebimento"];
		$tecnico             = $_POST["tecnico"];
		$posicao_peca        = $_POST["posicao_peca"];
		$codigo_analise      = $_POST["codigo_analise"];

		if(!isset($_POST["gerar_excel"])){

			if(strlen($codigo_analise) == 0){

				if(empty($data_abertura)){
					$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		        	$msg_erro["campos"][]   = "data_abertura";
				}

				if(empty($data_abertura_final)){
					$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		        	$msg_erro["campos"][]   = "data_abertura_final";
				}

				if(!empty($data_abertura)){
					if(valida_date($data_abertura) == false){
						$msg_erro["msg"][] 		= "A data de Abertura Inicial é invalida";
		        		$msg_erro["campos"][]   = "data_abertura";
					}
				}

				if(!empty($data_abertura_final)){
					if(valida_date($data_abertura_final) == false){
						$msg_erro["msg"][] 		= "A data de Abertura Final é invalida";
		        		$msg_erro["campos"][]   = "data_abertura_final";
					}
				}

				if(!empty($data_abertura) && !empty($data_abertura_final)){

					if(valida_date_maior($data_abertura, $data_abertura_final) == false){
						$msg_erro["msg"][] 		= "A data de Abertura Inicial é maior que a Abertura Final";
		        		$msg_erro["campos"][]   = "data_abertura";
					}

					if(data_limite($data_abertura, $data_abertura_final) == true){
						$msg_erro["msg"][] 		= "O intervalo entre as datas não pode ser maior que 12 meses";
		        		$msg_erro["campos"][]   = "data_abertura";
					}


				}

			}

			if(count($msg_erro["msg"]) == 0){

				if(strlen($data_abertura) > 0){
					list($d, $m, $a) = explode("/", $data_abertura);
					$data_abertura_opt = $a."-".$m."-".$d;

					list($d, $m, $a) = explode("/", $data_abertura_final);
					$data_abertura_final_opt = $a."-".$m."-".$d;

					$cond_data_abertura = "tbl_analise_peca.data_abertura BETWEEN '{$data_abertura_opt} 00:00:00' AND '{$data_abertura_final_opt} 23:59:59' ";

				}

				if(strlen($origem_recebimento) > 0){
					$cond_origem_recebimento = "AND tbl_analise_peca.origem_recebimento = {$origem_recebimento} ";
				}

				if(strlen($tecnico) > 0){
					$cond_tecnico = "AND tbl_analise_peca.tecnico = {$tecnico} ";
				}

				if(strlen($posto) > 0){
					$cond_posto = " AND tbl_analise_peca.posto = {$posto} AND tbl_analise_peca.fabrica = {$login_fabrica} ";
				}

				if(strlen($posicao_peca) > 0){
					$cond_posicao_peca = "AND tbl_analise_peca.status_analise_peca = {$posicao_peca} ";
				}

				if(strlen($codigo_analise) > 0){
					$and = (strlen($data_abertura_opt) > 0) ? "AND" : "";
					$cond_codigo_analise = " {$and} tbl_analise_peca.analise_peca = {$codigo_analise} ";
				}

				$sql = "SELECT 
							tbl_analise_peca.analise_peca,
							tbl_analise_peca.data_abertura,
							tbl_analise_peca.nota_fiscal,
							tbl_analise_peca.posto,
							tbl_analise_peca.inicio_analise,
							tbl_analise_peca.termino_analise,
							tbl_analise_peca.termino_final,
						tbl_analise_peca_item.laudo_defeito_constatado,
						tbl_analise_peca_item.laudo_analise, 
							tbl_origem_recebimento.descricao AS origem_recebimento,
							tbl_tecnico.nome AS tecnico_nome,
							tbl_status_analise_peca.descricao AS status_analise_peca 
						FROM tbl_analise_peca 
						LEFT  JOIN tbl_analise_peca_item ON tbl_analise_peca_item.analise_peca = tbl_analise_peca.analise_peca 
						LEFT  JOIN tbl_peca ON tbl_peca.peca = tbl_analise_peca_item.peca 
						INNER JOIN tbl_origem_recebimento ON tbl_analise_peca.origem_recebimento = tbl_origem_recebimento.origem_recebimento 
						INNER JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_analise_peca.tecnico 
						INNER JOIn tbl_status_analise_peca ON tbl_status_analise_peca.status_analise_peca = tbl_analise_peca.status_analise_peca 
						WHERE 
							{$cond_data_abertura} 
							{$cond_codigo_analise} 
							{$cond_posto}
							{$cond_tecnico} 
							{$cond_origem_recebimento}  
							{$cond_posicao_peca}
						ORDER BY tbl_analise_peca.analise_peca ASC
						";
				$result = pg_query($con, $sql);

			}

		}else{

			if(strlen($data_abertura) > 0){
				list($d, $m, $a) = explode("/", $data_abertura);
				$data_abertura_opt = $a."-".$m."-".$d;

				list($d, $m, $a) = explode("/", $data_abertura_final);
				$data_abertura_final_opt = $a."-".$m."-".$d;

				$cond_data_abertura = "tbl_analise_peca.data_abertura BETWEEN '{$data_abertura_opt} 00:00:00' AND '{$data_abertura_final_opt} 23:59:59' ";

			}

			if(strlen($origem_recebimento) > 0){
				$cond_origem_recebimento = "AND tbl_analise_peca.origem_recebimento = {$origem_recebimento} ";
			}

			if(strlen($tecnico) > 0){
				$cond_tecnico = "AND tbl_analise_peca.tecnico = {$tecnico} ";
			}

			if(strlen($posto) > 0){
				$cond_posto = " AND tbl_analise_peca.posto = {$posto} AND tbl_analise_peca.fabrica = {$login_fabrica} ";
			}

			if(strlen($posicao_peca) > 0){
				$cond_posicao_peca = "AND tbl_analise_peca.status_analise_peca = {$posicao_peca} ";
			}

			if(strlen($codigo_analise) > 0){
				$and = (strlen($data_abertura_opt) > 0) ? "AND" : "";
				$cond_codigo_analise = " {$and} tbl_analise_peca.analise_peca = {$codigo_analise} ";
			}

			$sql = "SELECT 
						tbl_analise_peca.analise_peca,
						tbl_analise_peca.data_abertura,
						tbl_analise_peca.nota_fiscal,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome AS nome_posto,
						tbl_origem_recebimento.descricao AS origem_recebimento,
						tbl_tecnico.nome AS tecnico_nome,
						tbl_status_analise_peca.descricao AS status_analise_peca,
						tbl_analise_peca.data_entrega,
						tbl_analise_peca.autorizacao,
						tbl_analise_peca.responsavel_recebimento,
						tbl_analise_peca.nf_saida,
						tbl_analise_peca.data_nf_saida,
						tbl_analise_peca.volume,
						/* Item */
						tbl_peca.referencia AS peca_referencia,
						tbl_peca.descricao AS peca_descricao,
						tbl_analise_peca_item.numero_serie AS serie,
						tbl_analise_peca_item.lote,
						tbl_analise_peca_item.qtde,
						tbl_analise_peca_item.laudo_defeito_constatado,
						tbl_analise_peca_item.laudo_analise, 
						tbl_analise_peca_item.procede_reclamacao,
						tbl_analise_peca_item.garantia,
						tbl_analise_peca_item.laudo_apos_reparo,
						tbl_analise_peca_item.enviar_peca_nova,
						tbl_analise_peca_item.sucatear_peca,
						tbl_analise_peca_item.baixa_no_estoque,
						tbl_analise_peca_item.lancar_no_clain,
						tbl_analise_peca_item.gasto_nao_justifica_devolucao 
					FROM tbl_analise_peca 
					INNER JOIN tbl_analise_peca_item ON tbl_analise_peca_item.analise_peca = tbl_analise_peca.analise_peca 
					INNER JOIN tbl_peca ON tbl_peca.peca = tbl_analise_peca_item.peca 
					INNER JOIN tbl_posto ON tbl_posto.posto = tbl_analise_peca.posto 
					INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_analise_peca.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
					INNER JOIN tbl_origem_recebimento ON tbl_analise_peca.origem_recebimento = tbl_origem_recebimento.origem_recebimento 
					INNER JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_analise_peca.tecnico 
					INNER JOIn tbl_status_analise_peca ON tbl_status_analise_peca.status_analise_peca = tbl_analise_peca.status_analise_peca 
					WHERE 
						{$cond_data_abertura} 
						{$cond_codigo_analise} 
						{$cond_posto}
						{$cond_tecnico} 
						{$cond_origem_recebimento}  
						{$cond_posicao_peca}
					ORDER BY tbl_analise_peca.analise_peca ASC
					"; 

			// echo $sql; exit;

			$result = pg_query($con, $sql);

			if(pg_num_rows($result) > 0){

				$file     = "xls/relatorio-analise-pecas-{$login_fabrica}.xls";
		        $fileTemp = "/tmp/relatorio-analise-pecas-{$login_fabrica}.xls" ;
		        $fp       = fopen($fileTemp,'w');
		        
		        $th_defeito_constatado = '';
				$th_resultado_analise  = '';
				if ($login_fabrica == 129) {
					$th_defeito_constatado = "<th><font color='#FFFFFF'>Defeito Constatado</font></th>";
					$th_resultado_analise  = "<th><font color='#FFFFFF'>Resultado da Análise</font></th>";
				}

		        $head = "
                <table border='1'>
                    <thead>
                        <tr bgcolor='#596D9B'>
                            <th><font color='#FFFFFF'>Data de Abertura</font></th>
                            <th><font color='#FFFFFF'>Numero Ficha</font></th>
                            <th><font color='#FFFFFF'>Nota Fiscal</font></th>
                            <th><font color='#FFFFFF'>Código Posto</font></th>
                            <th><font color='#FFFFFF'>Nome Posto</font></th>
                            <th><font color='#FFFFFF'>Referência Peça/Produto</font></th>
                            <th><font color='#FFFFFF'>Descrição Peça/Produto</font></th>
                            {$th_defeito_constatado}
                            {$th_resultado_analise}
                            <th><font color='#FFFFFF'>Série</font></th>
                            <th><font color='#FFFFFF'>Lote</font></th>
                            <th><font color='#FFFFFF'>Quantidade</font></th>
                            <th><font color='#FFFFFF'>Origem de Recebimento</font></th>
                            <th><font color='#FFFFFF'>Responsável</font></th>
                            <th><font color='#FFFFFF'>Técnico</font></th>
                            <th><font color='#FFFFFF'>Procede Reclamação</font></th>
                            <th><font color='#FFFFFF'>Garantia</font></th>
                            <th><font color='#FFFFFF'>Laudo após reparo</font></th>
                            <th><font color='#FFFFFF'>Enviar peça nova</font></th>
                            <th><font color='#FFFFFF'>Sucatear Peça</font></th>
                            <th><font color='#FFFFFF'>Baixar no estoque</font></th>
                            <th><font color='#FFFFFF'>Lançar no Claim</font></th>
                            <th><font color='#FFFFFF'>Gasto não justificado com devolução</font></th>
                            <th><font color='#FFFFFF'>Data de entrega a expedição</font></th>
                            <th><font color='#FFFFFF'>Responsável pelo recebimento</font></th>
                            <th><font color='#FFFFFF'>Nota Fiscal de saída</font></th>
                            <th><font color='#FFFFFF'>Data da NF de saída</font></th>
                            <th><font color='#FFFFFF'>Volume</font></th>
                        </tr>
                    </thead>
                    <tbody>";
            	fwrite($fp, $head);

		        for($i = 0; $i < pg_num_rows($result); $i++){

		        	$body = "<tr>";

						$procede_reclamacao            = (pg_fetch_result($result, $i, "procede_reclamacao") == "t") ? "Sim" : "Não";
						$garantia                      = (pg_fetch_result($result, $i, "garantia") == "t") ? "Sim" : "Não";
						$laudo_apos_reparo             = (pg_fetch_result($result, $i, "laudo_apos_reparo") == "t") ? "Aprovado" : "Reprovado";
						$enviar_peca_nova              = (pg_fetch_result($result, $i, "enviar_peca_nova") == "t") ? "Sim" : "Não";
						$sucatear_peca                 = (pg_fetch_result($result, $i, "sucatear_peca") == "t") ? "Sim" : "Não";
						$baixa_no_estoque              = (pg_fetch_result($result, $i, "baixa_no_estoque") == "t") ? "Sim" : "Não";
						$lancar_no_clain               = (pg_fetch_result($result, $i, "lancar_no_clain") == "t") ? "Sim" : "Não";
						$gasto_nao_justifica_devolucao = (pg_fetch_result($result, $i, "gasto_nao_justifica_devolucao") == "t") ? "Sim" : "Não";

						if(strlen(pg_fetch_result($result, $i, "data_abertura")) > 0){
							list($ano, $mes, $dia) = explode("-", pg_fetch_result($result, $i, "data_abertura"));
							$data_abertura = $dia."/".$mes."/".$ano;
						}else{
							$data_abertura = "";
						}

						if(strlen(pg_fetch_result($result, $i, "data_entrega")) > 0){
							list($ano, $mes, $dia) = explode("-", pg_fetch_result($result, $i, "data_entrega"));
							$data_entrega = $dia."/".$mes."/".$ano;
						}else{
							$data_entrega = "";
						}

						if(strlen(pg_fetch_result($result, $i, "data_nf_saida")) > 0){
							list($ano, $mes, $dia) = explode("-", pg_fetch_result($result, $i, "data_nf_saida"));
							$data_nf_saida = $dia."/".$mes."/".$ano;
						}else{
							$data_nf_saida = "";
						}

	                    $body .= "<td align='center'>" . $data_abertura . "</td>";
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "analise_peca") . "</td>";
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "nota_fiscal") . "</td>";
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "codigo_posto") . "</td>";
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "nome_posto") . "</td>";
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "peca_referencia") . "</td>";
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "peca_descricao") . "</td>";
	                    if ($login_fabrica == 129) {
							$body .= "<td align='center'>".pg_fetch_result($result, $i, "laudo_defeito_constatado")."</td>";
							$body .= "<td align='center'>".pg_fetch_result($result, $i, "laudo_analise")."</td>";
						}
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "serie") . "</td>";
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "lote") . "</td>";
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "qtde") . "</td>";
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "origem_recebimento") . "</td>";
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "autorizacao") . "</td>";
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "tecnico_nome") . "</td>";
	                    $body .= "<td align='center'>" . $procede_reclamacao . "</td>";
	                    $body .= "<td align='center'>" . $garantia . "</td>";
	                    $body .= "<td align='center'>" . $laudo_apos_reparo . "</td>";
	                    $body .= "<td align='center'>" . $enviar_peca_nova . "</td>";
	                    $body .= "<td align='center'>" . $sucatear_peca . "</td>";
	                    $body .= "<td align='center'>" . $baixa_no_estoque . "</td>";
	                    $body .= "<td align='center'>" . $lancar_no_clain . "</td>";
	                    $body .= "<td align='center'>" . $gasto_nao_justifica_devolucao . "</td>";
	                    $body .= "<td align='center'>" . $data_entrega . "</td>";
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "responsavel_recebimento") . "</td>";
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "nf_saida") . "</td>";
	                    $body .= "<td align='center'>" . $data_nf_saida . "</td>";
	                    $body .= "<td align='center'>" . pg_fetch_result($result, $i, "volume") . "</td>";

	                $body .= "</tr>";

	                fwrite($fp, $body);

		        }

		        fwrite($fp, '</tbody></table>');
		        fclose($fp);

		        if(file_exists($fileTemp)){
		            system("mv $fileTemp $file");

		            if(file_exists($file)){
		                echo $file;
		            }
		        }

		        exit;

			}

		}

	}

	/* Action Form */

	$layout_menu = "cadastro";
	
	$title = "RELATÓRIO ANÁLISE DE PEÇAS";

	include "cabecalho_new.php";

	$plugins = array(
		"multiselect",
		"autocomplete",
		"datepicker",
		"shadowbox",
		"mask",
		"alphanumeric",
		"dataTable"
	);

	include("plugin_loader.php");

?>

<script>

	$(function(){

		Shadowbox.init();

		$.datepickerLoad(Array("data_abertura", "data_abertura_final"));

		$("#data_abertura").mask("99/99/9999");
		$("#data_abertura_final").mask("99/99/9999");
		$("#codigo_analise").numeric();

		$(document).on("click", "span[rel=lupa]", function () {
			$.lupa($(this),Array('posicao'));
		});

	});

	function retorna_posto(retorno){
	    $("#posto").val(retorno.posto);
	    $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}
	
</script>

<?php
if ((count($msg_erro["msg"]) > 0) ) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">* Campos obrigatórios </b>
</div>

<form name="frm_credenciamento" method="POST" class="" action="<? echo $PHP_SELF; ?> ">

	<div class="tc_formulario">

		<div class="titulo_tabela">Paramêtros de Pesquisa</div>

		<br />

		<input type="hidden" name="posto" id="posto" value="<? echo $posto?>">

		<div class="row-fluid">

			<div class="span2"></div>

			<div class='span3'>
                <div class='control-group <?=(in_array("data_abertura", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_abertura'>Data Abertura Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                        	<h5 class='asteristico'>*</h5>
                            <input type="text" name="data_abertura" id="data_abertura" size="12" class='span12' value= "<?=$data_abertura?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class='span3'>
                <div class='control-group <?=(in_array("data_abertura_final", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_abertura_final'>Data Abertura Final</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                        	<h5 class='asteristico'>*</h5>
                            <input type="text" name="data_abertura_final" id="data_abertura_final" size="12" class='span12' value= "<?=$data_abertura_final?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class='span2'>
                <div class='control-group <?=(in_array("codigo_analise", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='codigo_analise'>Código da Análise</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" name="codigo_analise" id="codigo_analise" size="12" class='span12' value= "<?=$codigo_analise?>">
                        </div>
                    </div>
                </div>
            </div>

        </div>

		<div class="row-fluid">
			<div class="span2"></div>

			<div class="span3">
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>

					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<?php echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa">
								<i class='icon-search' ></i>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</span>
						</div>

					</div>
				</div>
			</div>

			<div class="span5">

				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Razão Social</label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?php echo $descricao_posto ?>" >
							<span class='add-on' rel="lupa">
								<i class='icon-search' ></i>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</span>
						</div>
					</div>
				</div>
			</div>

		</div>

		<div class="row-fluid">

			<div class="span2"></div>

	        <div class='span6'>
	            <div class='control-group <?=(in_array("origem_recebimento", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='origem_recebimento'>Origem do Recebimento</label>
	                <div class='controls controls-row'>
	                    <div class='span12'>
                            <select name="origem_recebimento" id="origem_recebimento" class='span12'>
                            	<option value=""></option>

                            	<?php

                            	$sql = "SELECT * FROM tbl_origem_recebimento WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao ASC";
                            	$res = pg_query($con, $sql);

                            	if(pg_num_rows($res) > 0){

                            		for($i = 0; $i < pg_num_rows($res); $i++){

                            			$codigo = pg_fetch_result($res, $i, "origem_recebimento");
                            			$descricao = pg_fetch_result($res, $i, "descricao");

                            			$selected = ($codigo == $origem_recebimento) ? "SELECTED" : "";

                            			echo "<option value='{$codigo}' {$selected}>{$descricao}</option>";

                            		}

                            	}

                            	?>

                            </select>
	                    </div>
	                </div>
	            </div>
	        </div>
			
			<div class="span1"></div>

		</div>

		<div class="row-fluid">

			<div class="span2"></div>

			<div class='span4'>
	            <div class='control-group <?=(in_array("tecnico", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='tecnico'>Técnico</label>
	                <div class='controls controls-row'>
	                    <div class='span11'>
                            <select name="tecnico" id="tecnico" class='span12'>
                            	<option value=""></option>

                            	<?php

                            	$sql = "SELECT tecnico, nome FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY nome ASC";
                            	$res = pg_query($con, $sql);

                            	if(pg_num_rows($res) > 0){

                            		for($i = 0; $i < pg_num_rows($res); $i++){

                            			$codigo = pg_fetch_result($res, $i, "tecnico");
                            			$nome = pg_fetch_result($res, $i, "nome");

                            			$selected = ($codigo == $tecnico) ? "SELECTED" : "";

                            			echo "<option value='{$codigo}' {$selected}>{$nome}</option>";

                            		}

                            	}

                            	?>

                            </select>
	                    </div>
	                </div>
	            </div>
	        </div>

	        <div class='span4'>
	            <div class='control-group <?=(in_array("posicao_peca", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='posicao_peca'>Posição da Peça</label>
	                <div class='controls controls-row'>
	                    <div class='span12'>
                            <select name="posicao_peca" id="posicao_peca" class='span12'>
                            	<option value=""></option>

                            	<?php

                            	$sql = "SELECT status_analise_peca, descricao FROM tbl_status_analise_peca WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao ASC";
                            	$res = pg_query($con, $sql);

                            	if(pg_num_rows($res) > 0){

                            		for($i = 0; $i < pg_num_rows($res); $i++){

                            			$codigo = pg_fetch_result($res, $i, "status_analise_peca");
                            			$nome = pg_fetch_result($res, $i, "descricao");

                            			$selected = ($codigo == $posicao_peca) ? "SELECTED" : "";

                            			echo "<option value='{$codigo}' {$selected}>{$nome}</option>";

                            		}

                            	}

                            	?>

                            </select>
	                    </div>
	                </div>
	            </div>
	        </div>

		</div>

		<p>
			<br/>
        	<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        	<input type='hidden' id="btn_click" name='btn_acao' value='' />
    	</p>

    	<br />

	</div>

</form>

<?php

	if(isset($result)){

		$count = pg_num_rows($result);

		if($count > 0){
			$th_defeito_constatado = '';
			$th_resultado_analise  = '';
			$colspan = '9';
			if ($login_fabrica == 129) {
				$th_defeito_constatado = '<th>Defeito Constatado</th>';
				$th_resultado_analise  = '<th>Resultado da Análise</th>';
				$colspan = '11';
			}

			?>

			<div class='alert tac'>Para efetuar alterações, clique no Código da Análise.</div>

			<div class="container" style="width: 1200px; margin-left: -160px;">
			    <table id="listagem" class='table table-striped table-bordered table-hover' style="width: 100%;">
			        <thead>
			            <tr class='titulo_tabela'>
			                <th colspan="<?php echo $colspan;?>">Relação de Análises de Peças cadastradas</th>
			            </tr>
			            <tr class='titulo_coluna' >
			                <th>Codigo</th>
			                <th>Data Abertura</th>
			                <th>Posto</th>
			                <th>Nota Fiscal</th>
			                <th>Origem Recebimento</th>
			                <th>Técnico</th>
			                <?php echo $th_defeito_constatado;?>
			                <?php echo $th_resultado_analise;?>
			                <th>Posição da Peça</th>
			                <th>Status</th>
			                <th>Ação</th>
			            </tr>
			        </thead>

			        <tbody>
			            <?php

							$td_defeito_constatado = '';
							$td_resultado_analise  = '';
			                for ($i = 0; $i < $count; $i++) {

								$analise_peca        = pg_fetch_result($result, $i, "analise_peca");
								$data_abertura       = pg_fetch_result($result, $i, "data_abertura");
								$nota_fiscal         = pg_fetch_result($result, $i, "nota_fiscal");
								$origem_recebimento  = pg_fetch_result($result, $i, "origem_recebimento");
								$status_analise_peca = pg_fetch_result($result, $i, "status_analise_peca");
								$tecnico_nome        = pg_fetch_result($result, $i, "tecnico_nome");
								$posto             	 = pg_fetch_result($result, $i, "posto");
								$inicio_analise      = pg_fetch_result($result, $i, "inicio_analise");
								$termino_analise     = pg_fetch_result($result, $i, "termino_analise");
								$termino_final       = pg_fetch_result($result, $i, "termino_final");

								if ($login_fabrica == 129) {
									$laudo_defeito_constatado = pg_fetch_result($result, $i, "laudo_defeito_constatado");
									$laudo_analise       = pg_fetch_result($result, $i, "laudo_analise");
									$td_defeito_constatado = '<td>'.$laudo_defeito_constatado.'</td>';
									$td_resultado_analise  = '<td>'.$laudo_analise.'</td>';
								}
								if(strlen($termino_analise) > 0){

									$parcial = (strlen($termino_final) == 0) ? "(Parcial)" : "";

									list($data, $hora) = explode(" ", (strlen($termino_final) > 0) ? $termino_final : $termino_analise);
									list($ano, $mes, $dia) = explode("-", $data);
									$data = $dia."/".$mes."/".$ano;
									list($h, $m, $s) = explode(":", $hora);
									$status = "<strong class='text-error'>Finalizado {$parcial} <br /> <small>({$data} às {$h}:{$m})</small></strong>";

								}else{
									$status = "<strong class='text-success'>Aberto</strong>";
								}

								list($a, $m, $d) = explode("-", $data_abertura);
								$data_abertura = $d."/".$m."/".$a;

								$btn_consultar = "<a href='analise_pecas_press.php?analise_peca={$analise_peca}' target='_blank' class='btn btn-primary' >Consultar</a>";

								$sql_posto = "SELECT 
											tbl_posto_fabrica.codigo_posto, 
											tbl_posto.nome 
										FROM tbl_posto_fabrica 
										INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto  
										WHERE 
											tbl_posto_fabrica.fabrica = {$login_fabrica} 
											AND tbl_posto_fabrica.posto = {$posto}";
								$res_posto = pg_query($con, $sql_posto);

								$codigo_posto = pg_fetch_result($res_posto, 0, "codigo_posto");
								$nome_posto   = pg_fetch_result($res_posto, 0, "nome");

			                    ?>
			                    <tr>
			                        <td class="tac"><a href="analise_pecas.php?analise_peca=<?php echo $analise_peca ?>" target="_blank"><?php echo $analise_peca ?><a></td>
			                        <td><?php echo $data_abertura; ?></td>
			                        <td><?php echo "<strong>" . $codigo_posto . "</strong> - " . $nome_posto; ?></td>
			                        <td><?php echo $nota_fiscal; ?></td>
			                        <td><?php echo $origem_recebimento; ?></td>
			                        <td><?php echo $tecnico_nome; ?></td>
			                        <?php echo $td_defeito_constatado; ?>
			                        <?php echo $td_resultado_analise; ?>
			                        <td><?php echo $status_analise_peca; ?></td>
			                        <td class="tac" nowrap><?php echo $status; ?></td>
			                        <td class="tac"><?php echo $btn_consultar; ?></td>
			                    </tr>         
			            <? } ?>

			        </tbody>
			    </table>
			</div>

			<?php if ($count > 0) { ?>
            <script>
                $.dataTableLoad({
                    table : "#listagem"
                });
            </script>
            <?php }?>

            <?php  

            $arr_excel = array(
            	"data_abertura"       => $_POST["data_abertura"], 
				"data_abertura_final" => $_POST["data_abertura_final"],
				"posto"               => $_POST["posto"],
				"codigo_posto"        => $_POST["codigo_posto"],
				"descricao_posto"     => $_POST["descricao_posto"],
				"origem_recebimento"  => $_POST["origem_recebimento"],
				"tecnico"             => $_POST["tecnico"],
				"posicao_peca"        => $_POST["posicao_peca"],
				"codigo_analise"      => $_POST["codigo_analise"]
            );

            ?>

            <div id='gerar_excel' class="btn_excel">
		        <input type="hidden" id="jsonPOST" value='<?=json_encode($arr_excel)?>' />
		        <span><img src='imagens/excel.png' /></span>
		        <span class="txt">Gerar Arquivo Excel</span>
		    </div>

			<?php

		}else{

			echo "<br /> <div class='alert alert-warning tav'> <h4>Nenhum resultado encontrado</h4> </div> <br />";

		}

	}

?>

<br /> <br />

<?php include "rodape.php";?>
