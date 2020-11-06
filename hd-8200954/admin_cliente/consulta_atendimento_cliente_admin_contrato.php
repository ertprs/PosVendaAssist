<?php
include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios="contrato";
include 'autentica_admin.php';
//include 'funcoes.php';

if(isset($_POST['btn_acao']) AND $_POST['btn_acao'] == "Consultar"){
	$data_inicial 	= $_POST['data_inicial'];
	$data_final 	= $_POST['data_final'];
	$hd_chamado 	= $_POST['atendimento'];
	$status 	= $_POST['status'];

	if(strlen($hd_chamado) > 0){
		$cond = " AND tbl_hd_chamado.hd_chamado = {$hd_chamado} ";
	}else{
		if(strlen($data_inicial) == 0 OR strlen($data_final) == 0){
			$msg_erro["msg"][] = "Informe um período para realizar a pesquisa";
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["campos"][] = "data_final";
		}else{
			list($di,$mi,$yi) = explode("/",$data_inicial);
			list($df,$mf,$yf) = explode("/",$data_final);

			if(!checkdate($mi,$di,$yi)){
				$msg_erro["msg"][] = "Data inicial inválida";
				$msg_erro["campos"][] = "data_inicial";
			}else{
				$data_ini = "$yi-$mi-$di";
			}

			if(!checkdate($mf,$df,$yf)){
				$msg_erro["msg"][] = "Data final inválida";
				$msg_erro["campos"][] = "data_final";
			}else{
				$data_fim = "$yf-$mf-$df";
			}

			if(count($msg_erro["msg"]) == 0){
				$cond = " AND tbl_hd_chamado.data BETWEEN '$data_ini 00:00:00' and '$data_fim 23:59:59' ";

				if(strlen($status) > 0){
					$cond .= " AND tbl_hd_chamado.status = '$status' ";
				}
			}
		}
	}
}

if(strlen($status) == 0 AND !isset($_POST['btn_acao'])){
	$cond = " AND tbl_hd_chamado.status = 'Aberto' ";
}

$sql = "SELECT  tbl_hd_chamado.hd_chamado,
				tbl_produto.referencia,
				tbl_produto.descricao,
				tbl_hd_chamado.status,
				to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS abertura,
				TO_CHAR(tbl_hd_chamado.data_providencia,'DD/MM/YYYY') AS prazo_limite,
				tbl_hd_chamado_extra.os
		  FROM tbl_hd_chamado
	 LEFT JOIN tbl_hd_chamado_item USING(hd_chamado)
	 LEFT JOIN tbl_hd_chamado_extra USING(hd_chamado)
	 LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_item.produto  AND tbl_produto.fabrica_i = {$login_fabrica}
		 WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
		   AND tbl_hd_chamado.cliente_admin = {$login_cliente_admin}
	           {$cond}";

$resT = pg_query($con,$sql);
$totaltendimentos = pg_num_rows($resT);
$layout_menu = "contrato";
$title = "RELATÓRIO DE CONSULTA DE ATENDIMENTOS";
include 'cabecalho_novo.php';

$plugins = array(
    "shadowbox",
    "price_format",
    "mask",
    "ckeditor",
    "autocomplete",
    "datepicker",
    "ajaxform",
    "fancyzoom",
    "multiselect"
);
include("plugin_loader.php");
?>
<script type="text/javascript" charset="utf-8">

    $(function(){
	    $('#data_inicial').datepicker({startDate:'01/01/2000'}).mask("99/99/9999");
		$('#data_final').datepicker({startDate:'01/01/2000'}).mask("99/99/9999");
    });
</script>


<style>
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial" !important;
	color:#FFFFFF;
	text-align:center;
	padding: 2px 0;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial" !important;
    color:#FFFFFF;
    text-align:center;
}

</style>
<div id='erro' class='alert alert-danger Erro' style="visibility: hidden;"></div>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
<?php if (count($msg_erro["msg"]) > 0) {?>
    <div class="alert alert-error">
        <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
    </div>
<?php }?>
<form name="frm_relatorio" METHOD="POST" class='form-search form-inline tc_formulario' ACTION="<? echo $PHP_SELF ?>">
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span2'>
            <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label'>Data Inicial</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" class="span12" value="<?php echo (isset($data_inicial) && strlen($data_inicial) > 0) ? $data_inicial : "";?>" name="data_inicial" id="data_inicial">
                    </div>
                </div>
            </div>
        </div>
        <div class='span1'></div>
        <div class='span2'>
            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" class="span12" value="<?php echo (isset($data_final) && strlen($data_final) > 0) ? $data_final : "";?>" name="data_final" id="data_final">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span3'>
            <div class='control-group'>
                <label class='control-label'>Atendimento</label>
                <input type="text" name="atendimento" id="atendimento" class="span12" maxlength="10" value="<? if (strlen($atendimento) > 0) echo $atendimento; ?>" >
            </div>
        </div>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label'>Status</label>
                <select name="status" class="span12">
					<option value=""></option>

					<?php
					$sqlS = " SELECT status FROM tbl_hd_status where fabrica=$login_fabrica ";
					$resS = pg_query($con,$sqlS);

				        for ($i = 0; $i < pg_num_rows($resS);$i++){

						$status_hd = pg_result($resS,$i,0);
		                                $selected_status = ($status_hd == $status) ? "SELECTED" : null;
		                        ?>
		                        <option value="<?=$status_hd?>" <?echo $selected_status?> ><?echo $status_hd?></option>
		                        <?php
					}
				        ?>
				</select>
            </div>
        </div>
    </div>
    <p><br/>
        <input value="Consultar" class='btn' name="btn_acao" id="btn_acao" type="submit">
    </p><br/>

</form>

<?php if ($totaltendimentos > 0) {?>
    <table class="table table-bordered table-hover table-stripe table-fixed">
    	<thead>
	        <tr class="titulo_coluna">
	            <th>Atendimento</th>
	            <th>Produto</th>
	            <th>Data</th>
	            <th>OS</th>
	            <th>Status</th>
	        </tr>
    	</thead>
    	<tbody>
		<?php
	        for($i = 0; $i < $totaltendimentos; $i++){

	            $atendimento = pg_fetch_result($resT,$i,hd_chamado);
	            $referencia = pg_fetch_result($resT,$i,referencia);
	            $descricao = pg_fetch_result($resT,$i,descricao);
	            $status = pg_fetch_result($resT,$i,status);
		    $abertura = pg_fetch_result($resT,$i,abertura);

	            $prazo_limite = pg_fetch_result($resT, $i, 'prazo_limite');
	            $os = pg_fetch_result($resT, $i, 'os');

		   

		?>
	    <tr>
			<td class='tac'>
				<a href="pre_os_cadastro_sac.php?hd_chamado=<?=$atendimento?>" target="_blank"><?=$atendimento?></a>
			</td>
            <td class='left'><?=$referencia?> - <?=$descricao?></td>
            <td class='tac'><?=$abertura?></td>
            <td class='tac'><?=$os?></td>
            <td class='tac'><?=$status?></td>
        </tr>
		<?php }?>
	</tbody>
</table>

<?php
}else{
	echo '<div class="alert alert-waring"><h4>Nenhum atendimento encontrado.</h4></div>';
}

include 'rodape.php';
