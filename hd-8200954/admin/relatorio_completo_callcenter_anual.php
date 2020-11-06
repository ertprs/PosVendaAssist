<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if(filter_input(INPUT_POST,"btn_acao")){
    $data_inicial       = filter_input(INPUT_POST,'data_inicial');
    $data_final         = filter_input(INPUT_POST,'data_final');
    $atendente          = filter_input(INPUT_POST,'atendente');
    $providencia        = filter_input(INPUT_POST,'providencia');
    $cliente            = filter_input(INPUT_POST,"cliente");
    $cpf                = filter_input(INPUT_POST,"cpf");
    $situacao_protocolo = filter_input(INPUT_POST,'situacao_protocolo');
    $tipo_data          = filter_input(INPUT_POST,'tipo_data');
    $centro_distribuicao = filter_input(INPUT_POST,'centro_distribuicao');

    if (!strlen($data_inicial) || !strlen($data_final) || empty($tipo_data)) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data";
        $msg_erro["campos"][] = "tipo";
    } else {
        list($di, $mi, $yi) = explode("/", $data_inicial);
        list($df, $mf, $yf) = explode("/", $data_final);

        if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
            $msg_erro["msg"][]    = "Data Inválida";
            $msg_erro["campos"][] = "data";
        } else {
            $aux_data_inicial = "{$yi}-{$mi}-{$di}";
            $aux_data_final   = "{$yf}-{$mf}-{$df}";

            if (strtotime($aux_data_inicial."+1 YEARS" ) < strtotime($aux_data_final)) {
                $msg_erro["msg"][]    = "Intervalo de pesquisa não pode ser maior do que 12 mêses.";
                $msg_erro["campos"][] = "data";
            }
        }
    }

    if(count($msg_erro['msg']) == 0){

        if(!empty($atendente)){
            $cond = " AND tbl_hd_chamado.atendente = {$atendente} ";
        }

        if(!empty($providencia)){
            $cond .= " AND tbl_hd_chamado_extra.hd_motivo_ligacao = {$providencia} ";
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

   
		$join = " LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tmp.hd_chamado AND tbl_hd_chamado_item.produto IS NOT NULL AND tbl_hd_chamado_item.data >= tmp.data";
		$join .= " LEFT JOIN tbl_hd_chamado_item hi2 ON hi2.hd_chamado = tmp.hd_chamado AND hi2.os IS NOT NULL AND hi2.data >= tmp.data ";

		$join_produto = " LEFT JOIN tbl_produto TPI ON TPI.produto = tbl_hd_chamado_item.produto AND TPI.fabrica_i = {$login_fabrica} ";


        $campoTitular   = "";

        if (in_array($login_fabrica, array(151))) {
            $campoTitular      = " JSON_FIELD('nome_titular_nf', tbl_hd_chamado_extra.array_campos_adicionais) AS titular_nf, JSON_FIELD('cpf_titular_nf', tbl_hd_chamado_extra.array_campos_adicionais) AS cpf_titular_nota, ";
        }


		$datas = relatorio_data("$aux_data_inicial","$aux_data_final");
		$cont = 0;

		foreach($datas as $data_pesquisa){
			$aux_data_inicial = $data_pesquisa[0];
			$aux_data_final = $data_pesquisa[1];
			$aux_data_final = str_replace(' 23:59:59', '', $aux_data_final);
	
			switch ($tipo_data){
				case "abertura":
					$condData = " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
					break;
				case "troca":
					$condData = " AND     tbl_os_troca.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";
					$join_troca = " JOIN tbl_os_troca ON  tbl_hd_chamado_extra.os = tbl_os_troca.os and tbl_os_troca.fabric = $login_fabrica "; 
					break;
			}		

			if($cont == 0) {
				$sql = "create temp table tmp_callcenter_$login_admin as SELECT tbl_hd_chamado.hd_chamado,
					tbl_hd_chamado_extra.origem,
					tbl_hd_chamado.data,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY')   AS data_abertura,
					to_char(tbl_hd_chamado.data_resolvido,'DD/MM/YYYY')   AS data_finalizado,
					tbl_hd_chamado_extra.dias_aberto,
					tbl_hd_chamado.fabrica,
					tbl_hd_chamado.status                                       AS situacao,
					$campoTitular
					tbl_hd_chamado_extra.os,
					tbl_hd_chamado_extra.hd_motivo_ligacao,
					hd_classificacao,
					tbl_hd_chamado_extra.produto
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
					$join_troca
					WHERE tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
					AND tbl_hd_chamado.posto ISNULL
					$situacao
					$condData
					$cond
					$limit ; 
					
					UPDATE tmp_callcenter_$login_admin set os = tbl_hd_chamado_item.os  FROM tbl_hd_chamado_item where tmp_callcenter_$login_admin.hd_chamado = tbl_hd_chamado_item.hd_chamado and tbl_hd_chamado_item.os notnull and tmp_callcenter_$login_admin.os isnull;
					UPDATE tmp_callcenter_$login_admin set produto = tbl_os.produto FROM tbl_os where tbl_os.os = tmp_callcenter_$login_admin.os and tmp_callcenter_$login_admin.produto isnull;";
			}else{
				$sql = "insert into tmp_callcenter_$login_admin SELECT  tbl_hd_chamado.hd_chamado,
					tbl_hd_chamado_extra.origem,
					tbl_hd_chamado.data,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY')   AS data_abertura,
					to_char(tbl_hd_chamado.data_resolvido,'DD/MM/YYYY')   AS data_finalizado,
					tbl_hd_chamado_extra.dias_aberto,
					tbl_hd_chamado.fabrica,
					tbl_hd_chamado.status                                       AS situacao,
					$campoTitular
					tbl_hd_chamado_extra.os,
					tbl_hd_chamado_extra.hd_motivo_ligacao,
					hd_classificacao,
					tbl_hd_chamado_extra.produto
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
					$join_troca
					WHERE tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
					AND tbl_hd_chamado.posto ISNULL
					$situacao
					$condData
					$cond
					$limit ; 
					UPDATE tmp_callcenter_$login_admin set os = tbl_hd_chamado_item.os  FROM tbl_hd_chamado_item where tmp_callcenter_$login_admin.hd_chamado = tbl_hd_chamado_item.hd_chamado and tbl_hd_chamado_item.os notnull and tmp_callcenter_$login_admin.os isnull;
					UPDATE tmp_callcenter_$login_admin set produto = tbl_os.produto FROM tbl_os where tbl_os.os = tmp_callcenter_$login_admin.os and tmp_callcenter_$login_admin.produto isnull;";
			}

			$res = pg_query($con,$sql);

			if (!$_POST["gerar_excel"] and in_array($cont,range(0,2))) {

				$sqlx = "select count(1) from tmp_callcenter_$login_admin ";
				$resx = pg_query($con, $sqlx);
				$qtde_total = pg_fetch_result($resx, 0, 0);
				if($qtde_total > 499) break;
			}

			$cont++;
		}

        if($login_fabrica == 151){            
            if($centro_distribuicao != 'mk_vazio') {
                $condicao_p_adicionais= " OR tbl_produto.parametros_adicionais::json->>'centro_distribuicao' = '$centro_distribuicao'";
                $campo_p_adicionais = "                
                         TPE.parametros_adicionais::json->>'centro_distribuicao'   AS
                        centro_distribuicao,";                
                $join_parametros_adicionais = " LEFT JOIN tbl_produto ON tbl_produto.fabrica_i = tmp.fabrica";
            }
        }  

		$sql = "CREATE INDEX tmp_hd_callcenter on tmp_callcenter_$login_admin(hd_chamado) ;
				CREATE INDEX tmp_hd2_callcenter on tmp_callcenter_$login_admin(hd_motivo_ligacao) ;
				CREATE INDEX tmp_hd3_callcenter on tmp_callcenter_$login_admin(hd_classificacao) ;
				CREATE INDEX tmp_hd4_callcenter on tmp_callcenter_$login_admin(os) ;
				CREATE INDEX tmp_hd5_callcenter on tmp_callcenter_$login_admin(produto) ;
				CREATE INDEX tmp_hd6_callcenter on tmp_callcenter_$login_admin(fabrica) ;


			   SELECT distinct tmp.*,
				tbl_ressarcimento.valor_original AS valor_ressarcimento,                                
                {$campo_p_adicionais}
				TPE.referencia as referencia_produto,
				TPE.descricao as descricao_produto,
				tbl_hd_motivo_ligacao.descricao AS providencia,
				(
					SELECT  gerar_pedido
					FROM    tbl_os_troca
					WHERE   tbl_os_troca.os = tmp.os 
					AND     tbl_os_troca.ressarcimento IS NOT TRUE
					ORDER BY      os_troca DESC
					LIMIT   1
				) AS gerar_pedido,                                                                                
				tbl_hd_classificacao.descricao                              AS classificacao,
				(
					SELECT  tbl_causa_troca.descricao
					FROM    tbl_os_troca
					JOIN    tbl_causa_troca USING(causa_troca)
					WHERE   tbl_os_troca.os = tmp.os 
					AND tbl_os_troca.ressarcimento IS NOT TRUE
					ORDER BY      os_troca DESC
					LIMIT   1
				)                                                           AS motivo,
				(
					SELECT tbl_os_item.pedido
					FROM tbl_os_item 
					INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
					WHERE tbl_os_produto.os = tmp.os and tbl_servico_realizado.troca_produto is true limit 1
				) AS pedido_troca
				FROM tmp_callcenter_$login_admin tmp 
				JOIN tbl_hd_classificacao      ON tbl_hd_classificacao.hd_classificacao    = tmp.hd_classificacao
				AND tbl_hd_classificacao.fabrica             = tmp.fabrica
				JOIN tbl_hd_motivo_ligacao     ON tbl_hd_motivo_ligacao.hd_motivo_ligacao  = tmp.hd_motivo_ligacao
				AND tbl_hd_motivo_ligacao.fabrica            = tmp.fabrica
				LEFT JOIN tbl_produto TPE      ON TPE.produto                              = tmp.produto
				AND TPE.fabrica_i                            = tmp.fabrica
				LEFT JOIN tbl_ressarcimento    ON tbl_ressarcimento.os                     = tmp.os
				AND tbl_ressarcimento.fabrica                = tmp.fabrica
                {$join_parametros_adicionais}
				WHERE tmp.fabrica = $login_fabrica
                {$condicao_p_adicionais}";    

		$resSubmit = pg_query($con, $sql);

        $count = pg_num_rows($resSubmit);

        if ($_POST["gerar_excel"]) {            
            if (pg_num_rows($resSubmit) > 0) {

                $data = date("d-m-Y-H:i");

                $fileName = "relatorio_geral-atendimentos-{$login_fabrica}-{$data}.csv";

                $file = fopen("/tmp/{$fileName}", "w");

                if(in_array($agrupar,array("n","a"))){
                    $thLogin = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Login</th>";
                }

                if(in_array($agrupar,array("n","p"))){
                    $thProvidencia = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Providência</th>";
                }

                $campoTitular = "";

                if ($login_fabrica == 151) {
                    $campoTitular = "Titular da NF;CPF do Titular;";
                    $thead = "Protocolo;Origem;Data Abertura;{$campoTitular}OS;Dias em Aberto;Referência;Descrição Produto;Situação;Classificação;Providência Tomada;Data Finalizado;Recompra / Troca;Nr. Pedido;Motivo;Centro Distribuicao\n";
                }else{
                    $thead = "Protocolo;Data Abertura;OS;Dias em Aberto;Referência;Descrição Produto;Situação;Classificação;Providência Tomada;Data Finalizado;Recompra / Troca;Nr. Pedido;Motivo;\n";
                }

                fwrite($file, utf8_encode($thead));


                for($j = 0; $j < pg_num_rows($resSubmit); $j++){

                    $hd_chamado             = pg_fetch_result($resSubmit,$j,'hd_chamado');
                    $origem                 = pg_fetch_result($resSubmit,$j,'origem');
                    $data_abertura          = pg_fetch_result($resSubmit,$j,'data_abertura');
                    $referencia_produto     = pg_fetch_result($resSubmit,$j,'referencia_produto');
                    $situacao               = pg_fetch_result($resSubmit,$j,'situacao');
                    $descricao_produto      = pg_fetch_result($resSubmit,$j,'descricao_produto');
                    $pedido                 = pg_fetch_result($resSubmit,$j,'pedido');
                    $troca                  = pg_fetch_result($resSubmit,$j,'gerar_pedido');
                    $motivo                 = pg_fetch_result($resSubmit,$j,'motivo');
                    $data_finalizado        = pg_fetch_result($resSubmit,$j,'data_finalizado');
                    $valor_ressarcimento    = pg_fetch_result($resSubmit,$j,'valor_ressarcimento');                                      
                    $rastreamento           = pg_fetch_result($resSubmit,$j,'conhecimento');
                    $total_interacoes       = pg_fetch_result($resSubmit,$j, "total_interacoes");
                    $pedido_troca           = pg_fetch_result($resSubmit,$j, "pedido_troca");
                    $ressar_troca           = ($troca == "t") ? "TROCA" : "";
                    $ressar_troca           = ($valor_ressarcimento > 0) ? "RESSARCIMENTO" : $ressar_troca;
                    $pedido                 = $pedido_troca;
                    $dias_aberto            = pg_fetch_result($resSubmit,$j,'dias_aberto');
                    $classificacao          = pg_fetch_result($resSubmit,$j,'classificacao');
                    $providencia            = pg_fetch_result($resSubmit,$j,'providencia');
                    $os                    = pg_fetch_result($resSubmit,$j,'os');

                    if($login_fabrica ==151){
                        $titular_nf       = pg_fetch_result($resSubmit,$j, "titular_nf");
                        $cpf_titular_nota = pg_fetch_result($resSubmit,$j, "cpf_titular_nota");
                        $parametros_adicionais  = pg_fetch_result($resSubmit,$j,'centro_distribuicao');  

                        $titular_nf = str_replace(array(";","<br>","\n","\r"), '', $titular_nf);

                        $body ="{$hd_chamado};{$origem};{$data_abertura};{$titular_nf};{$cpf_titular_nota};{$xos};{$dias_aberto};{$referencia_produto};{$descricao_produto};{$situacao};{$classificacao};{$providencia};{$data_finalizado};{$ressar_troca};{$pedido};{$motivo};";
                        
                        if($parametros_adicionais == "mk_nordeste"){
                            $body .= "MK Nordeste\n";    
                        }else if($parametros_adicionais == "mk_sul") {
                            $body .= "MK Sul\n"; 
                        } else {
                            $body .= "\n";   
                        }
                            
                    }else{
                        $body ="{$hd_chamado};{$data_abertura};{$xos};{$dias_aberto};{$referencia_produto};{$descricao_produto};{$situacao};{$classificacao};{$providencia};{$data_finalizado};{$ressar_troca};{$pedido};{$motivo};\n";
                    }

                    fwrite($file, utf8_encode($body));
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
$title= "RELATÓRIO DE VISÃO GERAL DE ATENDIMENTOS ANUAL";
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
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>

    <form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <div class ='titulo_tabela'>Parametros de Pesquisa </div>
        <br/>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
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
                    <label class='control-label' for='data_final'>Data Final</label>
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
                <div class='control-group'>
                    <label class='control-label' for='data_inicial'>Providência</label>
                    <div class='controls controls-row'>
                        <div class='span4'>

                            <select name="providencia" id="providencia">
                                <option value=""></option>
                                <?php

                                    $sql = "SELECT hd_motivo_ligacao, descricao FROM tbl_hd_motivo_ligacao WHERE fabrica = $login_fabrica and ativo order by descricao";
                                    $res = pg_query($con,$sql);
                                    foreach (pg_fetch_all($res) as $key) {

                                        $selected_providencia = ( isset($providencia) and ($providencia == $key['hd_motivo_ligacao']) ) ? "SELECTED" : '' ;

                                    ?>
                                        <option value="<?php echo $key['hd_motivo_ligacao']?>" <?php echo $selected_providencia ?> >
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
                        <div class='span10'>
                                <input type="text" name="cpf" id="cpf" class='span12 text-center' value= "<?=$cpf?>">
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
                        <div class='span12'>
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
        <? } ?>
        <br />
        <div class='row-fluid' style="margin-top: -10px !important;min-height: 0px !important">
            <div class='span2'></div>
            <div class='span10'>
                Situação do Protocolo:
                <label class="radio">
                <input type="radio" name="situacao_protocolo" id="optionsRadios1" value="todos" checked>Todos
                </label>
                <label class="radio">
                <input type="radio" name="situacao_protocolo" id="optionsRadios1" value="abertos" <?if($situacao_protocolo=="abertos") echo "checked";?>> Abertos
                </label>
                <label class="radio">
                <input type="radio" name="situacao_protocolo" id="optionsRadios1" value="cancelados" <?if($situacao_protocolo=="cancelados") echo "checked";?>> Cancelados
                </label>
                <label class="radio">
                <input type="radio" name="situacao_protocolo" id="optionsRadios1" value="resolvidos" <?if($situacao_protocolo=="resolvidos") echo "checked";?>> Resolvidos
                </label>
            </div>
        </div>

        <div class='row-fluid' style="margin-top: 10px !important;min-height: 0px !important">
            <div class='span2'></div>
            <div class='span10'>
                <div class='control-group <?=(in_array("tipo", $msg_erro["campos"])) ? "error" : ""?>'>
                    <h5 class='asteristico'>*</h5>Tipo de Data:
                    <label class="radio">
                        <input type="radio" name="tipo_data" id="optionsRadios1" value="abertura" <?if($tipo_data=="abertura") echo "checked";?>> Abertura
                    </label>
                    <label class="radio">
                        <input type="radio" name="tipo_data" id="optionsRadios1" value="troca" <?if($tipo_data=="troca") echo "checked";?>> Troca / Recompra
                    </label>
                </div>
            </div>
        </div>
        <p>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br />
    </form>
    <br />
<?php
    if(filter_input(INPUT_POST,"btn_acao")){
        if(strlen ($msg_erro["msg"]) == 0  AND pg_num_rows($resSubmit) > 0){
?>
            </div>
            <table id="resultado_atendimentos" class = 'table table-striped table-bordered table-hover'>
                <thead>
                    <tr class = 'titulo_coluna'>
                        <th>Protocolo</th>
                        <?php if($login_fabrica == 151){?>
                            <th>Origem</th> 
                        <?php } ?>

                        <th>Data Abertura</th>
                        <?php if ($login_fabrica == 151) { /*HD - 6177097*/ ?>
                            <th>Titular da NF</th>
                            <th>CPF do Titular</th>
                        <?php } ?>
                        <th>OS</th>
                        <th>Dias em Aberto</th>
                        <th>Referência</th>
                        <th>Descrição Produto</th>
                        <th>Situação</th>
                        <th>Classificação</th>
                        <th>Providência Tomada</th>
                        <th>Data Finalizado</th>
                        <th>Recompra / Troca</th>
                        <th>Nr. Pedido</th>
                        <th>Motivo</th>
                        <?php if($login_fabrica == 151) { ?>
                            <th>Centro Distribuição</th>
                        <?php } ?>
                    </tr>
                </thead>
                <?php                 
                    for($i = 0; $i < $count; $i++){

						$hd_chamado             = pg_fetch_result($resSubmit,$i,'hd_chamado');
                        $origem                 = pg_fetch_result($resSubmit,$i,'origem');
						$dias_aberto            = pg_fetch_result($resSubmit,$i,'dias_aberto');
						$data_abertura     = pg_fetch_result($resSubmit,$i,'data_abertura');
						$referencia_produto     = pg_fetch_result($resSubmit,$i,'referencia_produto');
						$descricao_produto      = pg_fetch_result($resSubmit,$i,'descricao_produto');
						$providencia            = pg_fetch_result($resSubmit,$i,'providencia');
						$data_finalizado        = pg_fetch_result($resSubmit,$i,'data_finalizado');
						$pedido                 = pg_fetch_result($resSubmit,$i,'pedido');
						$situacao               = pg_fetch_result($resSubmit,$i,'situacao');
						$classificacao          = pg_fetch_result($resSubmit,$i,'classificacao');
						$troca                  = pg_fetch_result($resSubmit,$i,'gerar_pedido');
						$motivo                 = pg_fetch_result($resSubmit,$i,'motivo');
                        $parametros_adicionais  = pg_fetch_result($resSubmit,$i,'centro_distribuicao');
						$data_troca_recompra    = pg_fetch_result($resSubmit,$i,'data_troca_recompra');
					 	$valor_ressarcimento    = pg_fetch_result($resSubmit,$i,'valor_ressarcimento');
						$pedido_troca           = pg_fetch_result($resSubmit,$i,'pedido_troca');
						$ressar_troca           = ($troca == "t") ? "TROCA" : "";
						$ressar_troca           = ($valor_ressarcimento > 0) ? "RESSARCIMENTO" : $ressar_troca;
						$pedido                 = $pedido_troca;
						$xos                    = pg_fetch_result($resSubmit,$i,'xos');
						$os                    = pg_fetch_result($resSubmit,$i,'os');
						$os = empty($os) ? $xos : $os; 

                        $valTitular = "";

						$body .= "<tr>
							<td class= 'tac'>{$hd_chamado}</td>
                        ";
                        if($login_fabrica == 151){
                            $titular_nf       = pg_fetch_result($resSubmit,$i, "titular_nf");
                            $cpf_titular_nota = pg_fetch_result($resSubmit,$i, "cpf_titular_nota");

                            $valTitular = "
                                <td class= 'tal'>$titular_nf</td>
                                <td class= 'tal'>$cpf_titular_nota</td>
                            ";

                            $body .= "<td class= 'tac'>{$origem}</td>";
						}
                        $body .= "<td class= 'tac'>{$data_abertura}</td>
                            {$valTitular}
							<td class= 'tac'>{$os}</td>
							<td class= 'tac'>{$dias_aberto}</td>
							<td class= 'tac'>{$referencia_produto}</td>
							<td class= 'tac' nowrap>{$descricao_produto}</td>
							<td class= 'tac'>{$situacao}</td>
							<td class= 'tac'>{$classificacao}</td>
							<td class= 'tac' nowrap>{$providencia}</td>
							<td class= 'tac'>{$data_finalizado}</td>
							<td class= 'tac'>{$ressar_troca}</td>
							<td class= 'tac'>{$pedido}</td>
							<td class= 'tac'>{$motivo}</td>";                                 
                        if($parametros_adicionais == "mk_nordeste"){
                            $body .= "<td>MK Nordeste</td>";    
                        }else if($parametros_adicionais == "mk_sul") {
                            $body .= "<td>MK Sul</td>"; 
                        } else{
                            $body .= "<td>&nbsp;</td>"; 
                        }                        

                        $body .= "</tr>";
                    }
                    echo $body;
                ?>
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

