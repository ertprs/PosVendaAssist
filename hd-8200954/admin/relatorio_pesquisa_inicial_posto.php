<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="gerencia";
include "autentica_admin.php";
include "funcoes.php";

if (isset($_POST['gerar'])) {

	if ($_POST["data_inicial"]) {
		$data_inicial = trim($_POST["data_inicial"]);	
	} 
	
	if ($_POST["data_final"]) {
		$data_final = trim($_POST["data_final"]);	
	} 
	if (empty($data_inicial) || empty($data_final)) {
		$msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
		$msg_erro["campos"][] = "data";
	}
		
	if (count($msg_erro) == 0) {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);
		if (!checkdate($mi,$di,$yi) || !checkdate($mf,$df,$yf)) {
			$msg_erro["msg"][]    = traduz("Data Inválida");
			$msg_erro["campos"][] = "data";
		}
	}

	if(count($msg_erro) == 0) {
		$aux_data_inicial = "$yi-$mi-$di";
		$aux_data_final = "$yf-$mf-$df";
	
		if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
			$msg_erro["msg"][]    = traduz("Data Inválida");
			$msg_erro["campos"][] = "data";
		}

		if(count($msg_erro) == 0) {
			if (strtotime("$aux_data_inicial + 3 month" ) < strtotime($aux_data_final)) {
				$msg_erro["msg"][]    = traduz("O intervalo entre as datas não pode ser maior que 3 meses.");
				$msg_erro["campos"][] = "data";
			}	
		}

		if(count($msg_erro) == 0) {
			$condData = " AND tbl_resposta.data_input BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
		}
	}

	$pesquisa = (int) $_POST['pesquisa'];
	if (!empty($pesquisa)) {
		$condPesquisa = " AND tbl_pesquisa.pesquisa = $pesquisa ";	
	} else {
		$msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
		$msg_erro["campos"][] = "pesquisa";
	}

	if (count($msg_erro) == 0) {

		$sql_pergunta = "SELECT tbl_pergunta.pergunta, 
							tbl_pergunta.descricao AS pergunta_descricao
						 FROM tbl_pesquisa
						 JOIN tbl_pesquisa_pergunta ON tbl_pesquisa.pesquisa = tbl_pesquisa_pergunta.pesquisa
						 JOIN tbl_pergunta ON tbl_pesquisa_pergunta.pergunta = tbl_pergunta.pergunta AND tbl_pergunta.fabrica = $login_fabrica
						 WHERE tbl_pesquisa.fabrica = $login_fabrica
						 $condPesquisa
						 ORDER BY tbl_pesquisa_pergunta.ordem;
						 ";
		$res_pergunta = pg_query($con, $sql_pergunta);
		$dadosPergunta = pg_fetch_all($res_pergunta);
		$qtdePergunta = count($dadosPergunta);

		if (pg_num_rows($res_pergunta) > 0) {
			$sqlx = "SELECT DISTINCT
							tbl_posto.cnpj,
							tbl_posto.nome AS nome_posto,
							tbl_posto.posto,
							tbl_resposta.resposta,
							tbl_pergunta.pergunta,
							TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY') AS data_resposta,
							LOWER(TO_ASCII(tbl_resposta.txt_resposta, 'LATIN9')) AS tipo_resposta_item
							INTO TEMP relatorio_pesquisa_posto_$login_fabrica
					FROM tbl_resposta
					JOIN tbl_pesquisa ON tbl_pesquisa.pesquisa = tbl_resposta.pesquisa AND tbl_pesquisa.fabrica = $login_fabrica
					JOIN tbl_pesquisa_pergunta ON tbl_pesquisa.pesquisa = tbl_pesquisa_pergunta.pesquisa
					JOIN tbl_pergunta ON tbl_pergunta.pergunta = tbl_resposta.pergunta AND tbl_pergunta.fabrica = $login_fabrica
					LEFT JOIN tbl_tipo_resposta ON tbl_tipo_resposta.tipo_resposta = tbl_pergunta.tipo_resposta AND tbl_tipo_resposta.fabrica = $login_fabrica
					JOIN tbl_posto ON tbl_posto.posto = tbl_resposta.posto
					WHERE tbl_pesquisa.fabrica = $login_fabrica
					$condData
					$condPesquisa
					ORDER BY tbl_posto.nome ASC";
			$resx  = pg_query($con,$sqlx);

			$limit = (501 * $qtdePergunta);

			$sqlx = "SELECT * FROM relatorio_pesquisa_posto_$login_fabrica LIMIT $limit";
			$resx = pg_query($con,$sqlx);

			$dadospPesquisa = pg_fetch_all($resx);
		
			if (count($dadospPesquisa) > 0) {
				$dadosResposta = [];
				$posicao = 0;

				foreach ($dadospPesquisa as $key => $rows) {
					$pergunta = $rows["pergunta"];	
					
					if (count($dadosResposta) == 0 ) {
						$dadosResposta[$posicao]['nome_posto'] = $rows['nome_posto'];
						$dadosResposta[$posicao]['cnpj'] = $rows['cnpj'];
						$dadosResposta[$posicao]['resposta_'.$pergunta] = $rows['resposta'];
						$dadosResposta[$posicao]['tipo_resposta_item_'.$pergunta] = $rows['tipo_resposta_item'];
						$dadosResposta[$posicao]['data_resposta'] = $rows['data_resposta'];
						$qtde_pergunta--;
						continue;
					}

					if ($dadosResposta[$posicao]['nome_posto'] != $rows['nome_posto']) {
						$posicao++;
						$dadosResposta[$posicao]['nome_posto'] = $rows['nome_posto'];

						if ($dadosResposta['cnpj'] != $rows['cnpj']) {
							$dadosResposta[$posicao]['cnpj'] = $rows['cnpj'];
						}

						if ($dadosResposta['resposta'] != $rows['resposta']) {
							$dadosResposta[$posicao]['resposta_'.$pergunta] = $rows['resposta'];
							$dadosResposta[$posicao]['tipo_resposta_item_'.$pergunta] = $rows['tipo_resposta_item'];
							$dadosResposta[$posicao]['data_resposta'] = $rows['data_resposta'];
						}
						$qtde_pergunta--;
					} else {
						if ($dadosResposta['resposta'] != $rows['resposta']) {
							$dadosResposta[$posicao]['resposta_'.$pergunta] = $rows['resposta'];
							$dadosResposta[$posicao]['tipo_resposta_item_'.$pergunta] = $rows['tipo_resposta_item'];
							$dadosResposta[$posicao]['data_resposta'] = $rows['data_resposta'];
							$qtde_pergunta--;
						}
					}
				}
			}
		}
	}

	if ($_POST["gerar_excel"] && count($msg_erro) == 0) {
		
		$sqlEx = "SELECT * FROM relatorio_pesquisa_posto_$login_fabrica";
		$resEx  = pg_query($con,$sqlEx);

		$dadospPesquisaEx = pg_fetch_all($resEx);

		if (count($dadospPesquisaEx) > 0) {
			$dadosRespostaEx = [];
			$posicaoEx = 0;

			foreach ($dadospPesquisaEx as $key => $rows) {
				$perguntaEx = $rows["pergunta"];

				if (count($dadosRespostaEx) == 0 ) {
					$dadosRespostaEx[$posicaoEx]['nome_posto'] = $rows['nome_posto'];
					$dadosRespostaEx[$posicaoEx]['cnpj'] = $rows['cnpj'];
					$dadosRespostaEx[$posicaoEx]['resposta_'.$perguntaEx] = $rows['resposta'];
					$dadosRespostaEx[$posicaoEx]['tipo_resposta_item_'.$perguntaEx] = $rows['tipo_resposta_item'];
					$dadosRespostaEx[$posicaoEx]['data_resposta'] = $rows['data_resposta'];
					$qtde_perguntaEx--;
					continue;
				}

				if ($dadosRespostaEx[$posicaoEx]['nome_posto'] != $rows['nome_posto']) {
					$posicaoEx++;
					$dadosRespostaEx[$posicaoEx]['nome_posto'] = $rows['nome_posto'];

					if ($dadosRespostaEx['cnpj'] != $rows['cnpj']) {
						$dadosRespostaEx[$posicaoEx]['cnpj'] = $rows['cnpj'];
					}

					if ($dadosRespostaEx['resposta'] != $rows['resposta']) {
						$dadosRespostaEx[$posicaoEx]['resposta_'.$perguntaEx] = $rows['resposta'];
						$dadosRespostaEx[$posicaoEx]['tipo_resposta_item_'.$perguntaEx] = $rows['tipo_resposta_item'];
						$dadosRespostaEx[$posicaoEx]['data_resposta'] = $rows['data_resposta'];
					}
					$qtde_perguntaEx--;
				} else {
					if ($dadosRespostaEx['resposta'] != $rows['resposta']) {
						$dadosRespostaEx[$posicaoEx]['resposta_'.$perguntaEx] = $rows['resposta'];
						$dadosRespostaEx[$posicaoEx]['tipo_resposta_item_'.$perguntaEx] = $rows['tipo_resposta_item'];
						$dadosRespostaEx[$posicaoEx]['data_resposta'] = $rows['data_resposta'];
						$qtde_perguntaEx--;
					}
				}
			}
		}

		if (count($dadosRespostaEx) > 0) {
            $colspan = $qtdePergunta + 3;
            $data = date("d-m-Y-H:i");

            $fileName = "relatorio_pesquisa_inicial_posto-{$data}.xls";

            $file = fopen("/tmp/{$fileName}", "w");
            $thead = "
                <table border='1'>
                    <thead>
                        <tr>
                            <th colspan='$colspan' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >".traduz('
                                RELATÓRIO DE PESQUISA INICAL DO POSTO')."
                            </th>
                        </tr>
            ";
            fwrite($file, $thead);

            $tbody .="<tr>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('CNPJ')."</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Posto')."</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Data Resposta')."</th>";

            foreach($dadosPergunta as $natureza => $array_dados){

                $tbody .="
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz($array_dados['pergunta_descricao'])."</th>";
            }
                
                $tbody .=" </tr>
                	</thead>
                    <tbody>";

            foreach($dadosRespostaEx as $chave => $valor){
                    $tbody .= "
                        <tr>
                    ";
                    $tbody .= "
		                    <td class='tac'>".preg_replace("/([0-9]{2})([0-9]{3})([0-9]{3})([0-9]{4})([0-9]{2})/", "$1.$2.$3/$4-$5", $valor['cnpj'])."</td>
		                    <td class='tac'>".traduz($valor['nome_posto'])."</td>
		                    <td class='tac'>".$valor['data_resposta']."</td>";

		                    foreach ($dadosPergunta as $key => $value) {
		                    	$resp = (isset($valor['tipo_resposta_item_'.$value["pergunta"]])) ? $valor['tipo_resposta_item_'.$value["pergunta"]] : "&nbsp"; 
					            $tbody .= "
					                    <td class='tac'>".$resp."</td>
			                            ";
			                }
                $tbody .= "</tr >
                ";
            }
            $tbody .= "</tbody>";
        }
        $tbody .= "</table>";
        fwrite($file, $tbody);
        fclose($file);

        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");

            echo "xls/{$fileName}";
        }

        exit;
	}
}
	
$layout_menu = "gerencia";
$title = "RELATÓRIO DE PESQUISA DE SATISFAÇÃO POSTO";
include "cabecalho_new.php";
$plugins = array(
	"datepicker",
	"mask",
	"dataTable"
);
include("plugin_loader.php");

?>

<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.dataTableLoad({ table: "#relatorio_pesq_posto"});

	});
	
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error msgErro">
		<h4><?=$msg_erro["msg"][0]?></h4>
    </div>
<?php
}
?>
<div class="alert alert-warning">
	<h4>Limite de 500 registros em tela, para mais registros gerar arquivo CSV</h4>
</div>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST" name="frm" class='form-search form-inline tc_formulario' id="form_relatorio_pesquisa">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
		<div class='row-fluid'>
			<div class='span3'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label for="data_inicial">Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span5'>
								<h5 class='asteristico'>*</h5>
									<input type="text" name="data_inicial" class="span12" id="data_inicial"  value="<?=isset($_POST['data_inicial'])?$_POST['data_inicial'] : '' ?>" />
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label for="data_final">Data Final</label>
					<div class='controls controls-row'>
						<div class='span5'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final" id="data_final" class="span12" value="<?=isset($_POST['data_final'])?$_POST['data_final'] : ''?>"/>
						</div>
					</div>
				</div>
			</div>
			<div class='span3'></div>
		</div>
		<div class='row-fluid'>
			<div class='span3'></div>
			<div class='span6'>
				<div class='control-group <?=(in_array("pesquisa", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>Pesquisa</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
							<select id="pesquisa" name="pesquisa" class="frm span12">
								<option value=""></option>
								<?php 
									$sql = "SELECT pesquisa,descricao
											FROM tbl_pesquisa
											WHERE fabrica = $login_fabrica";
									$res = pg_query($con,$sql);
									for ( $i = 0; $i < pg_num_rows($res); $i++ ) {
									
										$xpesquisa = pg_result($res,$i,'pesquisa');
										$xselected = $_POST['pesquisa'] == $xpesquisa ? 'selected' : '';
										$xdescricao= pg_result($res,$i,'descricao');
										echo '<option value="'.$xpesquisa.'" '.$xselected.'>'.$xdescricao.'</option>';
									
									}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span3'></div>
		</div>
		<input class="tac btn" type="submit" name="gerar" value="Consultar" />
		<br /><br />
	</form>
<?php
	if (isset($_POST['gerar']) && count($msg_erro) > 0) {
?>
		<div id="erro" class="msg_erro" style="display:none;"><?=$msg_erro?></div>
<?php
	} else if (count($msg_erro) == 0 && isset($_POST['gerar']) && count($dadosResposta) == 0) {
?>
		<div class="alert alert-warning"><h4>Não foram encontrados resultados para essa pesquisa</h4></div>
<?php
	}
?>

</div>

<?php 
	if (count($dadosResposta) > 0 && count($msg_erro) == 0) {
?>
	 	<table id='relatorio_pesq_posto' class='table table-striped table-bordered table-hover table-fixed relatorio_pesq_posto'>
	 		<thead>
	 			<tr class="expand titulo_coluna">
	 				<th><?=traduz('CNPJ')?></th>
	 				<th><?=traduz('Posto')?></th>
	 				<th><?=traduz('Data Resposta')?></th>
<?php
				 	foreach ($dadosPergunta as $k => $perg) {
?>
				 		<th ><?=traduz($perg['pergunta_descricao'])?></th>
<?php
				 	}
?>
	 			</tr>
	 		</thead>
	 		<tbody>
<?php
				foreach ($dadosResposta as $ky => $val) {
?>
					<tr>
 						<td><?=preg_replace("/([0-9]{2})([0-9]{3})([0-9]{3})([0-9]{4})([0-9]{2})/", "$1.$2.$3/$4-$5", $val['cnpj'])?></td>
 						<td><?=traduz($val['nome_posto'])?></td>
 						<td><?=$val['data_resposta']?></td>
<?php
						foreach ($dadosPergunta as $key => $value) {
							$resp = (isset($val['tipo_resposta_item_'.$value["pergunta"]])) ? $val['tipo_resposta_item_'.$value["pergunta"]] : "&nbsp";
?>
							<td><?=$resp?></td>
<?
						}
?>
 					</tr>	
<?php
				}
?>
	 		</tbody>
	 	</table>


<?
    $jsonPOST = excelPostToJson($_POST);
?>

<div id='gerar_excel' class="btn_excel">
    <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
    <span><img src='imagens/excel.png' /></span>
    <span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
</div>
<?php
	}
?>
	
	<script>
		$(window).load(function() {
		 	let tamanho = 0;
		 	tamanho = $("#form_relatorio_pesquisa").width();
		 	tamanho = tamanho + tamanho;
		    $("#relatorio_pesq_posto_wrapper").width(tamanho+"px");
		});

		setTimeout(function(){ $(".msgErro").hide("slow") }, 3000);

	</script>

<?php include 'rodape.php'; ?>
