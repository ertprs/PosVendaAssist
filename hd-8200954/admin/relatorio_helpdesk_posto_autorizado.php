<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = "";

if(isset($_POST["btn_pesquisar"])){
	$btn_acao = $_POST["btn_pesquisar"];

}else if(isset($_POST["btn_acao"])){
	$btn_acao = $_POST["btn_acao"];
}

if($btn_acao == "pesquisar"){
	$data_inicial     	 = $_POST["data_inicial"];
	$data_final       	 = $_POST["data_final"];
	$atendente        	 = $_POST["atendente"];
	$tipo_solicitacao 	 = $_POST["tipo_solicitacao"];
	$centro_distribuicao = $_POST['centro_distribuicao'];

	if(empty($data_inicial) || empty($data_final)){
		$msg_erro["msg"][] = "Preenche os campos obrigatorio";
		$msg_erro["campos"][] = "data_inicial";
		$msg_erro["campos"][] = "data_final";
	}else{
		try {
			validaData($data_inicial, $data_final, 1);

			list($dia, $mes, $ano) = explode("/", $data_inicial);
	        $aux_data_inicial      = $ano."-".$mes."-".$dia;

	        list($dia, $mes, $ano) = explode("/", $data_final);
	        $aux_data_final        = $ano."-".$mes."-".$dia;

	        $condicao = " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
		} catch (Exception $e) {
			$msg_erro["msg"][] = $e->getMessage();
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["campos"][] = "data_final";
		}
	}

	if(count($msg_erro["msg"]) == 0){
		if(!empty($atendente)){
			$condicao .= " AND tbl_admin.admin = {$atendente} ";
		}

		if(!empty($tipo_solicitacao)){
			$condicao .= " AND tbl_tipo_solicitacao.tipo_solicitacao = {$tipo_solicitacao} ";
		}

		if($login_fabrica == 151){
			if($centro_distribuicao[0] != "mk_vazio"){
				$distinct_p_adicionais = " DISTINCT ";				
				$order_p_adicionais = "";
				$campo_p_adicionais = "tbl_produto.parametros_adicionais::json->>'centro_distribuicao' AS centro_distribuicao,";
				$join_p_adicionais = " JOIN tbl_produto ON tbl_produto.fabrica_i = tbl_hd_chamado.fabrica ";
				$p_adicionais = " AND tbl_produto.parametros_adicionais::json->>'centro_distribuicao' = '$centro_distribuicao' ";				
			} else{
				$ordenar_sql = " ORDER BY tbl_hd_chamado_item.hd_chamado ASC, data_temp ASC";
			}
		} else {
			$ordenar_sql = " ORDER BY tbl_hd_chamado_item.hd_chamado ASC, data_temp ASC";
		}

		$sql = "SELECT {$distinct_p_adicionais} tbl_hd_chamado.hd_chamado, 
				tbl_hd_chamado.status,
				{$campo_p_adicionais}
				TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY') AS data_temp, 
				(CASE WHEN tbl_hd_chamado_item.posto IS NOT NULL THEN tbl_posto_fabrica.codigo_posto ELSE tbl_admin.nome_completo END) AS atendente_nome, 
				(tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome) AS posto_autorizado, 
				tbl_tipo_solicitacao.descricao AS tipo_solicitacao_chamado, 
				tbl_hd_chamado_extra.os 
			FROM tbl_hd_chamado_item 
				INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
				INNER JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado 
				LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin AND tbl_admin.fabrica = {$login_fabrica} 
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_hd_chamado.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
				INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto 
				INNER JOIN tbl_tipo_solicitacao ON tbl_tipo_solicitacao.tipo_solicitacao = tbl_hd_chamado.tipo_solicitacao AND tbl_tipo_solicitacao.fabrica = {$login_fabrica} 
				{$join_p_adicionais}
			WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
				{$condicao}
				{$p_adicionais}
				{$order_p_adicionais}
				{$ordenar_sql}
			";

		//die(nl2br($sql));
		$resRelatorio = pg_query($con,$sql);
	}
}

$layout_menu = "callcenter";

$title= "RELATÓRIO HELPDESK DO POSTO AUTORIZADO";

include "cabecalho_new.php";

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "select2",
   "dataTable"
);

include 'plugin_loader.php';

?>
<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));

		// var table = new Object();
		// table['table'] = '#table_atendimento';
		// table['type'] = 'basic';
		// $.dataTableLoad(table);
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
<form name='frm_relatorio_helpdesk_posto_autorizado' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
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
						<select id="atendente" name="atendente" class="span12" >
							<option value="" >Selecione</option>
							<?php
							$sql = "SELECT admin, nome_completo
								FROM tbl_admin
								WHERE fabrica = {$login_fabrica}
									AND ativo IS TRUE
									AND callcenter_supervisor IS TRUE
								ORDER BY nome_completo ASC";
							$res = pg_query($con, $sql);

							if (pg_num_rows($res) > 0) {
								while ($objeto_atendente = pg_fetch_object($res)) {
									$selected = ($objeto_atendente->admin == $atendente) ? "selected" : "";

									?>
									<option value='<?=$objeto_atendente->admin?>' <?=$selected?> ><?=$objeto_atendente->nome_completo?></option>
									<?php
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
				<label class='control-label' for='tipo_solicitacao'>Tipo de Solicitação</label>
				<div class="controls controls-row">
					<div class="span12">
						<select id="tipo_solicitacao" name="tipo_solicitacao" class="span12" >
							<option value="" >Selecione</option>
							<?php
							$sql = "SELECT tipo_solicitacao, descricao
									FROM tbl_tipo_solicitacao
									WHERE fabrica = {$login_fabrica}
									AND ativo IS TRUE
									ORDER BY descricao ASC";
							$res = pg_query($con, $sql);

							if (pg_num_rows($res) > 0) {
								while ($objeto_solicitacao = pg_fetch_object($res)) {
									$selected = ($objeto_solicitacao->tipo_solicitacao == $tipo_solicitacao) ? "selected" : "";

									?>
									<option value='<?=$objeto_solicitacao->tipo_solicitacao?>' <?=$selected?> ><?=$objeto_solicitacao->descricao?></option>
									<?php
								}
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>

	<?php if($login_fabrica == 151){ ?>         
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='centro_distribuicao'>Centro Distribuição</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <select name="centro_distribuicao" id="centro_distribuicao">
                                <option value="mk_vazio" name="mk_vazio" <?php echo ($centro_distribuicao == "mk_vazio") ? "SELECTED" : ""; ?>>ESCOLHA</option>
                                <option value="mk_nordeste" name="mk_nordeste" <?php echo ($centro_distribuicao == "mk_nordeste") ? "SELECTED" : ""; ?>>MK Nordeste</option>
                                <option value="mk_sul" name="mk_sul" <?php echo ($centro_distribuicao == "mk_sul") ? "SELECTED" : ""; ?>>MK Sul</option>    
                            </select>
                        </div>                          
                    </div>                      
                </div>
            </div>
        </div>                       
	<?php } ?>

	<div class='row-fluid'>
		<div class='span5'></div>
		<button type="submit" class='btn' id="btn_acao">Pesquisar</button>
		<input type="hidden" id="btn_pesquisar" name="btn_pesquisar" value="pesquisar" >
	</div>
</form>
<?php
if(count($msg_erro["msg"]) == 0 && $btn_acao == "pesquisar"){
	if(pg_num_rows($resRelatorio) > 0){
		include_once 'relatorio_helpdesk_posto_autorizado_excel.php';
	?>
	<!-- <div id='btn_excel' class="btn_excel">
		<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Gerar Arquivo Excel</span>
		<br/>
	</div> -->
	<table id="table_atendimento" class="table table-bordered table-striped table-fixed">
		<thead>
			<tr class="titulo_coluna" >
				<?php if($login_fabrica == 151) { ?>
					<th colspan="8" >Atendimentos</th>
				<? } else { ?>
					<th colspan="7" >Atendimentos</th>
				<? } ?>
			</tr>
			<tr class="titulo_coluna">
				<th>Protocolo</th>
				<th>Interação</th>
				<th>Status</th>
				<th>Data</th>
				<th>Atendente</th>
				<th>Tipo de Solicitação</th>
				<th>O. S.</th>
				<?php if($login_fabrica == 151) { ?>
					<th colspan="8" >Centro Distribuição</th>
				<? } ?>
			</tr>
		</thead>
		<tbody>
			<?
			$i              = 1;
			$hd_chamado_ant = null;
			$count          = pg_num_rows($resRelatorio);

			for($j=0; $j<$count; $j++){
				$hd_chamado               = pg_fetch_result($resRelatorio, $j, "hd_chamado");
				$status                   = pg_fetch_result($resRelatorio, $j, "status");
				$data_temp                = pg_fetch_result($resRelatorio, $j, "data_temp");
				$atendente_nome           = pg_fetch_result($resRelatorio, $j, "atendente_nome");
				$tipo_solicitacao_chamado = pg_fetch_result($resRelatorio, $j, "tipo_solicitacao_chamado");
				$os                       = pg_fetch_result($resRelatorio, $j, "os");
				$parametros_adicionais    = pg_fetch_result($resRelatorio, $j, "centro_distribuicao");

				if ($hd_chamado_ant != $hd_chamado) {
                    $hd_chamado_ant = $hd_chamado;
                    $i = 1; 
                } else {  
                    $i++;
                }

				?>
				<tr>
					<td class="tac"><a target="_blank" href="helpdesk_posto_autorizado_atendimento.php?hd_chamado=<?=$hd_chamado?>" ><?=$hd_chamado?></a></td>
					<td class="tac"><?=$i?></td>
					<td class="tac"><?=$status?></td>
					<td class="tac"><?=$data_temp?></td>
					<td class="tac"><?=$atendente_nome?></td>
					<td class="tac"><?=$tipo_solicitacao_chamado?></td>
					<td class="tac"><a target="_blank" href="os_press.php?os=<?=$os?>" ><?=$os?></a></td>
					<?php
						if($login_fabrica == 151){							 
							if($parametros_adicionais == "mk_nordeste"){
								echo "<td>MK Nordeste</td>";
							}else if($parametros_adicionais == "mk_sul") {
								echo "<td>MK Sul</td>";	
							} else{
								echo "<td>&nbsp;</td>";	
							}						
						}
					?>
				</tr>
				<?
			}
			?>
		</tbody>
	</table>
	<script>
		$.dataTableLoad({ table: "#table_atendimento" });
	</script>
	<?php
	}else{
		?>
		<div style="background-color: #fcf8e3; border:1px solid #fbeed5;">
			<h4 style="color: #c09853; text-align: center;">Não foi encontrado nenhum atendimento</h4>
		</div>
		<?php
	}
}
include 'rodape.php';
?>
