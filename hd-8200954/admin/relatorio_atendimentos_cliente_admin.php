<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';


if (filter_input(INPUT_POST,"btn_acao")) {
    $data_inicial       = filter_input(INPUT_POST,'data_inicial');
    $data_final         = filter_input(INPUT_POST,'data_final');
    $atendente          = filter_input(INPUT_POST,'atendente');
    $analitico          = filter_input(INPUT_POST,'analitico');
    $situacao_protocolo = filter_input(INPUT_POST,'situacao_protocolo');
    $tipo_data          = filter_input(INPUT_POST,'tipo_data');


	if (!strlen($data_inicial) || !strlen($data_final) || empty($tipo_data)) {
		$msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = traduz("Data Inválida");
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

            if (strtotime($aux_data_inicial."+3 months" ) < strtotime($aux_data_final)) {
				$msg_erro["msg"][]    = traduz("Intervalo de pesquisa não pode ser maior do que 3 mês.");
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if (count($msg_erro['msg']) == 0) {

		if(!empty($atendente)){
			$cond = " AND tbl_hd_chamado.atendente = {$atendente} ";
		}

 
		if (empty($_POST["gerar_excel"])) {
			$limit = "LIMIT 500";
		}

        if(!empty($situacao_protocolo)){
            switch($situacao_protocolo){
                case "todos":
                break;
                case "abertos":
                    $situacao = " AND tbl_hd_chamado.status = 'Aberto'";
                break;
                case "cancelados":
                    $situacao = " AND tbl_hd_chamado.status = 'Cancelado'";
                break;
                case "resolvidos":
                    $situacao = " AND tbl_hd_chamado.status = 'Resolvido'";
                break;
            }
        }

		if($analitico == "a"){

			$join = " LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ";
            $campo = ", tbl_hd_chamado_item.comentario, to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY') AS data_interacao";
			
		}else{
			
			$join = " LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_item.produto IS NOT NULL ";
			$join .= " LEFT JOIN tbl_hd_chamado_item hi2 ON hi2.hd_chamado = tbl_hd_chamado.hd_chamado AND hi2.os IS NOT NULL ";

			$join_produto = " LEFT JOIN tbl_produto TPI ON TPI.produto = tbl_hd_chamado_item.produto AND TPI.fabrica_i = {$login_fabrica} ";

		}

		switch ($tipo_data){
            case "abertura":
                $condData = " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
                break;
            case "ultima":
                $condData = " AND tbl_hd_chamado.hd_chamado in (
                                SELECT  WHDI.hd_chamado
                                FROM    tbl_hd_chamado_item WHDI
                                WHERE   WHDI.hd_chamado = tbl_hd_chamado.hd_chamado
                                AND     WHDI.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
                                AND     WHDI.hd_chamado_item = (
                                    SELECT  MAXHDI.hd_chamado_item
                                    FROM    tbl_hd_chamado_item MAXHDI
                                    WHERE   MAXHDI.hd_chamado = tbl_hd_chamado.hd_chamado
                              ORDER BY      MAXHDI.data DESC
                                    LIMIT   1
                                )
                            )
                ";
                break;            
		}
         

		$sql = "SELECT    tbl_hd_chamado.hd_chamado,
                        ab.nome_completo AS aberto_por,
                        at.nome_completo AS atendente_atual,
                        to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data_abertura,
                        tbl_produto.referencia AS referencia_produto,
                        tbl_produto.descricao AS descricao_produto,
                        tbl_cliente_admin.nome AS cliente_admin,
                        tbl_cliente_admin.cidade AS cidade_cliente_admin,
                        tbl_cliente_admin.estado AS estado_cliente_admin,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_posto.nome AS posto,
                        tbl_hd_chamado.status
                        {$campo}
                FROM tbl_hd_chamado
                INNER JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                INNER JOIN tbl_cliente_admin ON tbl_hd_chamado.cliente_admin = tbl_cliente_admin.cliente_admin AND tbl_cliente_admin.fabrica = {$login_fabrica}
                INNER JOIN tbl_admin ab ON tbl_hd_chamado.admin = ab.admin AND ab.fabrica = {$login_fabrica}
                INNER JOIN tbl_admin at ON tbl_hd_chamado.admin = at.admin AND at.fabrica = {$login_fabrica}
                {$join}
                LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
                LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                WHERE tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
                AND tbl_hd_chamado.cliente_admin IS NOT NULL
                AND tbl_hd_chamado.titulo !~* 'HELP-DESK'
                {$situacao} 
                {$condData}
                {$limit}";
        #echo nl2br($sql); 
        $resSubmit = pg_query($con, $sql);
		$count = pg_num_rows($resSubmit);          

		if ($_POST["gerar_excel"]) {
			if (pg_num_rows($resSubmit) > 0) {

				$data = date("d-m-Y-H:i");

				$fileName = "relatorio-atendimentos-cliente-admin-{$login_fabrica}-{$data}.csv";

				$file = fopen("/tmp/{$fileName}", "w");

				if(in_array($agrupar,array("n","a"))){
					$thLogin = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Login')."</th>";
				}

				if(in_array($agrupar,array("n","p"))){
					$thProvidencia = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Providência')."</th>";
				}

				$thead = "Protocolo;Pré-OS;Aberto por;Atendente Atual;Data;Referência do Produto;Produto;Cliente;Cidade;Estado (UF);Código do Posto;Posto;Status";

                if($analitico == "a"){
                    $thead .= ";Data Interação;Interaçãoo";
                }
                
                $thead .= "\n";

				fwrite($file, $thead);                

                $contador_resSubmit = pg_num_rows($resSubmit);
				for ($j = 0; $j < $contador_resSubmit; $j++) {

					$hd_chamado             = pg_fetch_result($resSubmit,$j,'hd_chamado');
                    $data_abertura          = pg_fetch_result($resSubmit,$j,'data_abertura');
                    $aberto_por             = pg_fetch_result($resSubmit,$j,'aberto_por');
                    $atendente_atual        = pg_fetch_result($resSubmit,$j,'atendente_atual');
                    $referencia_produto     = pg_fetch_result($resSubmit,$j,'referencia_produto');
                    $descricao_produto      = pg_fetch_result($resSubmit,$j,'descricao_produto');
                    $cliente_admin          = pg_fetch_result($resSubmit,$j,'cliente_admin');
                    $cidade_cliente_admin   = pg_fetch_result($resSubmit,$j,'cidade_cliente_admin');
                    $estado_cliente_admin   = pg_fetch_result($resSubmit,$j,'estado_cliente_admin');
                    $codigo_posto           = pg_fetch_result($resSubmit,$j,'codigo_posto');
                    $posto                  = pg_fetch_result($resSubmit,$j,'posto');
                    $status                 = pg_fetch_result($resSubmit,$j,'status');
                                        

					if($analitico == "a"){
                        $interacao                 = strip_tags(pg_fetch_result($resSubmit,$j,'comentario'));
			$interacao = str_replace("\n","",$interacao);
                        $data_interacao                 = pg_fetch_result($resSubmit,$j,'data_interacao');
                    }

					$hd_chamado_anterior = $hd_chamado;


                    $body = "{$hd_chamado};{$hd_chamado};{$aberto_por};{$atendente_atual};{$data};{$referencia_produto};{$descricao_produto};{$cliente_admin};{$cidade_cliente_admin};{$estado_cliente_admin};{$codigo_posto};{$posto};{$status}";

                    if($analitico == "a"){
                        $body .= ";$data_interacao;$interacao";
                    }

                    $body .= "\n";

					fwrite($file, $body);
				}

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
$title= traduz("RELATÓRIO DE VISÃO GERAL DE ATENDIMENTOS");
include "cabecalho_new.php";
$plugins = array(
	"datepicker",
	"mask",
	"dataTable",
	"ajaxform"
);
include("plugin_loader.php");
?>
<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));

	});
</script>
<style type="text/css">
    #optionsRadios1{
        margin-left: 14px;
    }
</style>
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
		<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
	</div>

<!--form-->
	<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class ='titulo_tabela'><?=traduz('Parametros de Pesquisa')?> </div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
					<div class='controls controls-row'>
						<div class='span6'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
					<div class='controls controls-row'>
						<div class='span6'>
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
				<label class='control-label' for='atendente'><?=traduz('Atendente')?></label>
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
		<div class='span2'></div>
    	</div>
        	 
    	<div class='row-fluid'>
            <div class='span2'></div>
            <div class='span10'>
                <?=traduz('Situação do Protocolo:')?>
                <input type="radio" name="situacao_protocolo" id="optionsRadios1" value="todos" checked><?=traduz('Todos')?>
                <input type="radio" name="situacao_protocolo" id="optionsRadios1" value="abertos" <?if($situacao_protocolo=="abertos") echo "checked";?>> <?=traduz('Abertos')?>
                <input type="radio" name="situacao_protocolo" id="optionsRadios1" value="cancelados" <?if($situacao_protocolo=="cancelados") echo "checked";?>> <?=traduz('Cancelados')?>
                <input type="radio" name="situacao_protocolo" id="optionsRadios1" value="resolvidos" <?if($situacao_protocolo=="resolvidos") echo "checked";?>> <?=traduz('Resolvidos')?>
            </div>
        </div>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span10'>
                <?=traduz('Tipo do Relatório:')?>
                <input type="radio" name="analitico" id="optionsRadios1" value="a" checked> <?=traduz('Analítico')?>
                <input type="radio" name="analitico" id="optionsRadios1" value="s" <?if($analitico=="s") echo "checked";?>> <?=traduz('Sintético')?>
            </div>
        </div>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span10'>
                <div class='control-group <?=(in_array("tipo", $msg_erro["campos"])) ? "error" : ""?>'>
                    <h5 class='asteristico'>*</h5><?=traduz('Tipo de Data:')?>
                    <label class="radio">
                        <input type="radio" name="tipo_data" id="optionsRadios1" value="abertura" <?if($tipo_data=="abertura" or empty($tipo_data)) echo "checked";?>> <?=traduz('Abertura')?>
                    </label>
                    <label class="radio">
                        <input type="radio" name="tipo_data" id="optionsRadios1" value="ultima" <?if($tipo_data=="ultima") echo "checked";?>> <?=traduz('Última Interação')?>
                    </label>
                </div>
            </div>
        </div>
    	<p>
    		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
    		<input type='hidden' id="btn_click" name='btn_acao' value='' />
    	</p><br />
	</form>
	<br />
<?php            
	if(filter_input(INPUT_POST,"btn_acao") and count($msg_erro["msg"]) == 0 ){
		if(count($msg_erro["msg"]) == 0 AND pg_num_rows($resSubmit) > 0){
?>
            </div>
			<table id="resultado_atendimentos" class = 'table table-striped table-bordered table-hover table-large'>
				<thead>
					<tr class = 'titulo_coluna'>						
						<th><?=traduz('Protocolo')?></th>
                        <th><?=traduz('Pré-OS')?></th>
                        <th><?=traduz('Aberto por')?></th>
                        <th><?=traduz('Atendente Atual')?></th>
                        <th><?=traduz('Data')?></th>
                        <th><?=traduz('Referência do Produto')?></th>
                        <th><?=traduz('Produto')?></th>
                        <th><?=traduz('Cliente')?></th>
                        <th><?=traduz('Cidade')?></th>
                        <th><?=traduz('Estado (UF)')?></th>
                        <th><?=traduz('Código do Posto')?></th>
                        <th><?=traduz('Posto')?></th>
                        <th><?=traduz('Status')?></th>

                        <?php
                            if($analitico == "a"){
                        ?>
                                <th><?=traduz('Data Interação')?></th>
                                <th><?=traduz('Interação')?></th>
                        <?php
                            }
                        ?>
                        <th><?=traduz('Ações')?></th>
					</tr>
				</thead>
				<tbody>
					<?php
                        for($i = 0; $i < $count; $i++){
							$hd_chamado             = pg_fetch_result($resSubmit,$i,'hd_chamado');
                            $data_abertura          = pg_fetch_result($resSubmit,$i,'data_abertura');
                            $aberto_por             = pg_fetch_result($resSubmit,$i,'aberto_por');
                            $atendente_atual        = pg_fetch_result($resSubmit,$i,'atendente_atual');
                            $referencia_produto     = pg_fetch_result($resSubmit,$i,'referencia_produto');
                            $descricao_produto      = pg_fetch_result($resSubmit,$i,'descricao_produto');
                            $cliente_admin          = pg_fetch_result($resSubmit,$i,'cliente_admin');
                            $cidade_cliente_admin   = pg_fetch_result($resSubmit,$i,'cidade_cliente_admin');
                            $estado_cliente_admin   = pg_fetch_result($resSubmit,$i,'estado_cliente_admin');
                            $codigo_posto           = pg_fetch_result($resSubmit,$i,'codigo_posto');
                            $posto                  = pg_fetch_result($resSubmit,$i,'posto');
                            $status                 = pg_fetch_result($resSubmit,$i,'status');

                            if($analitico == "a"){
                                $interacao                 = pg_fetch_result($resSubmit,$j,'comentario');
                                $data_interacao            = pg_fetch_result($resSubmit,$j,'data_interacao');
                            }
                          					        
							$body =   "<tr>
                                            <td class= 'tac' nowrap>
                                                <a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='blank'>{$hd_chamado}</a>
                                            </td>
    										<td class= 'tac'>{$hd_chamado}</td>
    										<td class= 'tal' nowrap>{$aberto_por}</td>
    										<td class= 'tal'nowrap>{$atendente_atual}</td>
    										<td class= 'tac'>{$data_abertura}</td>
    										<td class= 'tal'>{$referencia_produto}</td>
                                            <td class= 'tal' nowrap>{$descricao_produto}</td>
    										<td class= 'tal' nowrap>{$cliente_admin}</td>
    										<td class= 'tal'>{$cidade_cliente_admin}</td>
    										<td class= 'tac'>{$estado_cliente_admin}</td>
    										<td class= 'tal'>{$codigo_posto}</td>
    										<td class= 'tal' nowrap>{$posto}</td>
    										<td class= 'tac'>{$status}</td>";

                            if($analitico == "a"){
                                $body .=   " <td class= 'tac'>{$data_interacao}</td>
                                            <td class= 'tal' nowrap>{$interacao}</td>";
                            }

                            $body .= "<td>
                                        <a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='blank'>
                                            <button type='button' class='btn btn-small btn-primary'>".traduz('Consultar')."</button>
                                        </a>
                                        <a href='callcenter_interativo_print.php?callcenter=$hd_chamado' target='blank'>
                                                <button type='button' class='btn btn-small tac' style='width: 72px; margin-bottom: 5px;'>Imprimir</button>
                                        </a>
                                    </td>";

                            $body .= "</tr>";

                            echo $body;
						}
						
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
				<span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
			</div>
<?php
		}else{
			echo '
			<div class="container">
			<div class="alert">
				    <h4>'.traduz("Nenhum resultado encontrado").'</h4>
			</div>
			</div>';
		}
	}

include 'rodape.php';
?>
