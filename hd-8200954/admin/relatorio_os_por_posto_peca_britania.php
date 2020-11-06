<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

set_time_limit(180);

$admin_privilegios = "auditoria";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != "automatico") {
	include "autentica_admin.php";
}

include 'funcoes.php';

if ($_serverEnvironment == "production") {
    include "gera_relatorio_pararelo_include.php";
}
if (strlen(trim($_REQUEST["btn_acao"])) > 0)       $btn_acao = trim($_REQUEST["btn_acao"]);

if ($btn_acao == "submit") {

	$mes = $_REQUEST["mes"];
	$ano = $_REQUEST["ano"];
	$btn_acao = "submit";
    $zestado = $_REQUEST['estado'];

	if(strlen($mes) == 0){

		$msg_erro_form["msg"][]    = "Por favor, insira o Ano";
        $msg_erro_form["campos"][] = "ano";

        }

        if(strlen($ano) == 0){

            $msg_erro_form["msg"][]    = "Por favor, insira o Mês";
            $msg_erro_form["campos"][] = "mes";

        }

    }
    $layout_menu = "callcenter";
    $title = "RELATÓRIO MENSAL DE ORDENS DE SERVIÇO";

    include "cabecalho_new.php";

    $plugins = array();

    include("plugin_loader.php");

    if(strlen($btn_acao) > 0 && count($msg_erro_form["msg"]) == 0){
        if ($_serverEnvironment == "production") {
            include "gera_relatorio_pararelo.php";
        }
    }

    if($gera_automatico != "automatico") {
        if ($_serverEnvironment == "production") {
            include "gera_relatorio_pararelo_verifica.php";
        }
    }

    ?>
    <script>

        $('#loading, #loading-block').show();

        $(function(){
            $('#loading, #loading-block').hide();
        });
    </script>
    <div class="alert alert-block tac">
        O relatório é gerado a partir de todas as OS digitadas pelos postos, mesmo que a OS esteja incompleta (não finalizada). 
        Se o posto não digitar o defeito constatado, solução ou peça, estes campos aparecerão em branco. 
        <br /> <br />
        <strong>Ao clicar para Pesquisar será realizado o download do arquivo com as informações. <br /> O relatório pode demorar de 5 a 10 minutos, por favor aguarde!</strong>
    </div>

    <?php
    if (count($msg_erro_form["msg"]) > 0) {
    ?>
        <div class="alert alert-error">
            <h4><?=implode("<br />", $msg_erro_form["msg"])?></h4>
        </div>
    <?php
    }
    ?>

    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>

    <form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>

        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
        <br/>
        <div class='row-fluid'>
            <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("ano", $msg_erro_form["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='ano'>Ano</label>
                        <div class='controls controls-row'>
                            <div class='span8'>
                                <h5 class='asteristico'>*</h5>
                                <select name="ano" id="ano" class="span12">
                                    <option value=""></option>
                                    <?php

                                    for($i = 2003; $i <= date("Y"); $i++){
                                        $selected = ($ano == $i) ? "SELECTED" : "";
                                        echo "<option value='".$i."' {$selected}>{$i}</option>";
                                    }

                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("mes", $msg_erro_form["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='mes'>Mês</label>
                    <div class='controls controls-row'>
                        <div class='span8'>
                            <h5 class='asteristico'>*</h5>
                                <select name="mes">
                                    <option value=""></option>
                                    <?php

                                    $meses = array(
                                            1 => "Janeiro",
                                            2 => "Fevereiro",
                                            3 => "Março",
                                            4 => "Abril",
                                            5 => "Maio",
                                            6 => "Junho",
                                            7 => "Julho",
                                            8 => "Agosto",
                                            9 => "Setembro",
                                            10 => "Outubro",
                                            11 => "Novembro",
                                            12 => "Dezembro"
                                        );

                                    foreach ($meses as $mes_num => $mes_desc) {
                                        $selected = ($mes_num == $mes) ? "SELECTED" : "";
                                        echo "<option value='{$mes_num}' {$selected}>{$mes_desc}</option>";
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
            <div class="span3">
                <div class='control-group'>
                    <label class='control-label' for='mes'>Estado</label>
                    <div class='controls controls-row'>
                        <div class='span8'>
                            <select name="estado">
                            <?php 
                                $selectEstado = "SELECT * FROM tbl_estado WHERE pais = 'BR' AND visivel = 't' ORDER BY NOME ASC";
                                $resEstado = pg_query($con, $selectEstado);
                                $estados = pg_fetch_all($resEstado);

				echo "<option value='todos_estados'>Todos os estados</option>";

                                foreach ($estados as $estado) {
                                    $selected = "";
                                    if ($zestado == $estado['estado']) 
                                        $selected = "selected";
                                    echo "<option value='{$estado['estado']}' {$selected} >{$estado['nome']}</option>";
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
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));$('#loading').show();$('#loading-block').show();">Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p>

        <br/>

    </form>

    <?php    
        echo "
            <div class='alert tac' id='msg_carregando' style='display:none;'>
                    <b>Aguarde a geração do arquivo.</b> <br />  <br />
                <img src='imagens/ajax-carregando.gif' />
            </div>";



    if (strlen($btn_acao) > 0 && count($msg_erro_form["msg"]) == 0) {

        if (strlen($mes) > 0 AND strlen($ano) > 0) {
            $data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
            $data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
        }

        if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != 'todos_estados') {
            $where_estado = " AND contato_estado = '{$_REQUEST['estado']}' ";
        }
        
        // if (isset($zestado)) {
        //     $todos_estados = " tbl_posto_fabrica.contato_estado";
        //     $join_estado = "JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto";
        //     if ($zestado != "selecionar_todos") {
        //         $where_estado = " AND tbl_posto_fabrica.contato_estado = '$zestado' ";                
        //     } 
        // }

        
        $sql = "SELECT tbl_os.os ,
                tbl_os.sua_os ,
                tbl_os.consumidor_nome ,
                tbl_os.consumidor_revenda ,
                tbl_os.consumidor_fone ,
                tbl_os.serie ,
                tbl_os.revenda_nome ,
                tbl_os.data_digitacao ,
                tbl_os.data_abertura ,
                tbl_os.data_fechamento ,
                tbl_os.finalizada ,
                tbl_os.data_conserto ,
                tbl_os.data_nf ,
                replace(tbl_os.obs,'\"','') as obs_os ,
                tbl_os.obs_reincidencia ,
                data_abertura::date - tbl_os.data_nf::date AS dias_uso ,
                tbl_os.produto,
                tbl_os.posto,
                tbl_os.defeito_constatado,
                tbl_os.defeito_reclamado,
                tbl_os.solucao_os,
                tbl_os.fabrica,
                tbl_os.excluida,
                tbl_os.cancelada,
                tbl_os.defeito_reclamado_descricao as df_descricao,
                tbl_os.aparencia_produto            AS aparencia_produto,
                tbl_os.acessorios                   AS acessorios,
                tbl_os.troca_garantia_admin,
                tbl_os.tecnico
                INTO temp tmp_os_britania_$login_admin
                FROM tbl_os
                WHERE tbl_os.fabrica = $login_fabrica
                AND   tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
                AND   tbl_os.excluida IS NOT TRUE
                AND   tbl_os.posto <> 6359;";


                //echo $zestado."<br>";
                //exit($sql);

        $sql .= "
                CREATE INDEX idx_tosb_prod_$login_admin ON tmp_os_britania_$login_admin(produto);
                CREATE INDEX idx_tosb_posto_$login_admin ON tmp_os_britania_$login_admin(posto);
                CREATE INDEX idx_tosb_os_$login_admin ON tmp_os_britania_$login_admin(os);
                CREATE INDEX idx_tosb_tga_$login_admin ON tmp_os_britania_$login_admin(troca_garantia_admin);
                CREATE INDEX idx_tosb_tec_$login_admin ON tmp_os_britania_$login_admin(tecnico);
            ";

        $sql .= "SELECT  tmp_os_britania_$login_admin.os                                                           ,
                tmp_os_britania_$login_admin.sua_os                                                       ,
                tmp_os_britania_$login_admin.consumidor_nome                                              ,
                tmp_os_britania_$login_admin.consumidor_revenda                                           ,
                tmp_os_britania_$login_admin.consumidor_fone                                              ,
                tmp_os_britania_$login_admin.serie                                                        ,
                tmp_os_britania_$login_admin.revenda_nome                                                 ,
                tmp_os_britania_$login_admin.data_digitacao                                               ,
                tmp_os_britania_$login_admin.data_abertura                                                ,
                tmp_os_britania_$login_admin.data_fechamento                                              ,
                tmp_os_britania_$login_admin.finalizada                                                   ,
                tmp_os_britania_$login_admin.data_conserto                                                ,
                tmp_os_britania_$login_admin.data_nf                                                      ,
                tmp_os_britania_$login_admin.obs_os                                            ,
                tmp_os_britania_$login_admin.obs_reincidencia                                             ,
                tmp_os_britania_$login_admin.dias_uso           ,
                tmp_os_britania_$login_admin.produto,
                tmp_os_britania_$login_admin.posto,
                tmp_os_britania_$login_admin.defeito_constatado,
                tmp_os_britania_$login_admin.defeito_reclamado,
                tmp_os_britania_$login_admin.solucao_os,
                tmp_os_britania_$login_admin.fabrica,
                tmp_os_britania_$login_admin.excluida,
                tmp_os_britania_$login_admin.cancelada,
                tmp_os_britania_$login_admin.df_descricao,
                tbl_produto.referencia,
                tbl_produto.descricao,
                tbl_produto.linha,
                tbl_produto.familia,
                tbl_produto.marca,
                tbl_posto_fabrica.codigo_posto                                      ,
                tbl_posto.nome,
                tbl_posto.fone,
                tbl_posto_fabrica.contato_email,
                tbl_posto_fabrica.contato_estado,
                tbl_posto_fabrica.admin_sap,
                tbl_os_item.digitacao_item,
                tbl_os_item.peca,
                tbl_os_item.servico_realizado,
                tbl_os_item.pedido,
                tbl_linha.nome                               AS nome_linha,
                tbl_familia.descricao                        AS nome_familia,
                troca_admin.login                            AS troca_admin,
                TO_CHAR(data,'dd/mm/yyyy hh:mi') AS data_troca          ,
                setor                            AS setor_troca         ,
                situacao_atendimento             AS situacao_atend_troca,
                tbl_os_troca.observacao          AS observacao_troca    ,
                tbl_peca.referencia             AS peca_referencia_troca ,
                tbl_peca.descricao              AS peca_descricao_troca  ,
                tbl_causa_troca.descricao       AS causa_troca           ,
                tbl_os_troca.modalidade_transporte  AS modalidade_transporte_troca,
                tbl_os_troca.envio_consumidor       AS envio_consumidor_troca,
                tbl_os_extra.orientacao_sac         AS orientacao_sac,
                tbl_os_extra.extrato                AS extrato,
                tmp_os_britania_$login_admin.aparencia_produto,
                tmp_os_britania_$login_admin.acessorios,
                tbl_os_item.obs                     AS obs,
                tbl_tecnico.nome                    AS nome_tecnico,
                (SELECT array(
                    SELECT os_status::text
                        || '||'
                        || status_os::text
                        || '||'
                        || observacao::text
                        || '||'
                        || CASE WHEN tbl_admin.login isnull THEN ' ' ELSE tbl_admin.login::text end
                        || '||'
                        || to_char(data, 'DD/MM/YYYY')
                        || '||'
                        || CASE WHEN tbl_os_status.admin isnull THEN ' ' ELSE tbl_os_status.admin::text end
                    FROM  tbl_os_status
                    LEFT JOIN tbl_admin USING(admin)
                    WHERE tbl_os_status.os = tmp_os_britania_$login_admin.os
                    AND tbl_os_status.fabrica_status = $login_fabrica
                    AND status_os IN (72,73,62,64,65,87,88,116,117)
                    ORDER BY data ASC)
                ) AS status_os_dados
            into temp tmp_os_$login_admin
            FROM tmp_os_britania_$login_admin
            JOIN tbl_produto ON tmp_os_britania_$login_admin.produto = tbl_produto.produto and tbl_produto.fabrica_i = $login_fabrica
            JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha and tbl_linha.fabrica=$login_fabrica
            JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
            JOIN tbl_posto ON tmp_os_britania_$login_admin.posto   = tbl_posto.posto
            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            LEFT JOIN tbl_os_extra           ON tbl_os_extra.os = tmp_os_britania_$login_admin.os AND tbl_os_extra.i_fabrica=tmp_os_britania_$login_admin.fabrica
            LEFT JOIN tbl_os_produto         ON tmp_os_britania_$login_admin.os        = tbl_os_produto.os
            LEFT JOIN tbl_os_item            ON tbl_os_produto.os_produto = tbl_os_item.os_produto AND tbl_os_item.fabrica_i = tmp_os_britania_$login_admin.fabrica
            LEFT JOIN tbl_admin troca_admin  ON tmp_os_britania_$login_admin.troca_garantia_admin = troca_admin.admin
            LEFT JOIN tbl_os_troca           ON tbl_os_troca.os = tmp_os_britania_$login_admin.os and tbl_os_troca.fabric = $login_fabrica
            LEFT JOIN tbl_peca               ON tbl_os_troca.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica
            LEFT JOIN tbl_causa_troca        ON tbl_os_troca.causa_troca = tbl_causa_troca.causa_troca and tbl_causa_troca.fabrica=$login_fabrica
            LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tmp_os_britania_$login_admin.tecnico AND tmp_os_britania_$login_admin.posto = tbl_tecnico.posto AND tbl_tecnico.fabrica = $login_fabrica
            ;
            ";

        $sql .= "CREATE INDEX tmp_os_fabrica_os on tmp_os_$login_admin(fabrica,os);";

        $sql .= "CREATE INDEX tmp_os_fabrica_os_peca on tmp_os_$login_admin(peca);";

        $sql .= "CREATE INDEX tmp_os_fabrica_os_pedido on tmp_os_$login_admin(pedido);";

        $sql .= "CREATE INDEX tmp_os_fabrica_os_servico_realizado on tmp_os_$login_admin(servico_realizado);";

        $sql .= "CREATE INDEX tmp_os_fabrica_os_posto_excluida on tmp_os_$login_admin(fabrica,os,posto,excluida);";

        #echo nl2br($sql)."<br><br>";

        $res = pg_query($con, $sql);

        $msg_erro .= pg_errormessage($con);

        $sql = "SELECT distinct tmp_os_$login_admin.os                                     ,
                        tmp_os_$login_admin.sua_os                                          ,
                        tmp_os_$login_admin.consumidor_nome                                 ,
                        tmp_os_$login_admin.consumidor_revenda                              ,
                        tmp_os_$login_admin.consumidor_fone                                 ,
                        tmp_os_$login_admin.serie                                           ,
                        tmp_os_$login_admin.revenda_nome                                    ,
                        tmp_os_$login_admin.df_descricao                                    ,
                        tmp_os_$login_admin.obs_os                                          ,
                        tmp_os_$login_admin.obs_reincidencia                                ,
                        tmp_os_$login_admin.fabrica,
                        to_char (tmp_os_$login_admin.data_digitacao,'DD/MM/YYYY')  AS data_digitacao,
                        to_char (tmp_os_$login_admin.data_abertura,'DD/MM/YYYY')   AS data_abertura ,
                        to_char (tmp_os_$login_admin.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                        to_char (tmp_os_$login_admin.finalizada,'DD/MM/YYYY')      AS data_finalizada,
                        to_char (tmp_os_$login_admin.data_conserto,'DD/MM/YYYY')   AS data_conserto  ,
                        to_char (tmp_os_$login_admin.data_nf,'DD/MM/YYYY')         AS data_nf        ,
                        tmp_os_$login_admin.dias_uso                                                 ,
                        tbl_marca.nome                                AS marca_nome         ,
                        tmp_os_$login_admin.referencia                AS produto_referencia ,
                        tmp_os_$login_admin.descricao                 AS produto_descricao  ,
                        tbl_peca.referencia                           AS peca_referencia    ,
                        tbl_peca.descricao                            AS peca_descricao     ,
                        tbl_servico_realizado.descricao               AS servico            ,
                        tbl_defeito_constatado.descricao              AS defeito_constatado ,
                        tbl_defeito_reclamado.descricao               AS defeito_reclamado  ,
                        tbl_solucao.descricao                         AS solucao            ,
                        tmp_os_$login_admin.nome_linha                AS linha              ,
                        tmp_os_$login_admin.nome_familia              AS familia            ,
                        TO_CHAR (tmp_os_$login_admin.digitacao_item,'DD/MM/YYYY')  AS data_digitacao_item,
                        tmp_os_$login_admin.codigo_posto                                      ,
                        tmp_os_$login_admin.nome                            AS nome_posto         ,
                        tmp_os_$login_admin.contato_estado                  AS estado_posto,
                        tmp_os_$login_admin.admin_sap                       AS admin_sap,
                        tmp_os_$login_admin.contato_email                   AS email_posto,
                        tmp_os_$login_admin.fone                            AS posto_fone,
                        (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tmp_os_$login_admin.os AND tbl_os_status.fabrica_status=$login_fabrica ORDER BY os_status DESC LIMIT 1) AS status_os,
                        tmp_os_$login_admin.pedido                                                   ,
                        tmp_os_$login_admin.troca_admin,
                        tmp_os_$login_admin.data_troca          ,
                        tmp_os_$login_admin.setor_troca         ,
                        tmp_os_$login_admin.situacao_atend_troca,
                        tmp_os_$login_admin.observacao_troca    ,
                        tmp_os_$login_admin.peca_referencia_troca ,
                        tmp_os_$login_admin.peca_descricao_troca  ,
                        tmp_os_$login_admin.causa_troca           ,
                        tmp_os_$login_admin.modalidade_transporte_troca,
                        tmp_os_$login_admin.envio_consumidor_troca,
                        tmp_os_$login_admin.orientacao_sac,
                        tmp_os_$login_admin.aparencia_produto,
                        tmp_os_$login_admin.cancelada,
                        tmp_os_$login_admin.acessorios,
                        tmp_os_$login_admin.extrato,
                        tmp_os_$login_admin.obs,
                        tmp_os_$login_admin.nome_tecnico,
                        tmp_os_$login_admin.peca,
                        tmp_os_$login_admin.status_os_dados
                                INTO TEMP tmp_os_os_$login_admin

                        FROM tmp_os_$login_admin
                        LEFT JOIN tbl_peca               ON tmp_os_$login_admin.peca              = tbl_peca.peca           AND tbl_peca.fabrica = $login_fabrica
                        LEFT JOIN tbl_marca              ON tbl_marca.marca               = tmp_os_$login_admin.marca
                        LEFT JOIN tbl_defeito_reclamado  ON tmp_os_$login_admin.defeito_reclamado      = tbl_defeito_reclamado.defeito_reclamado  AND tbl_defeito_reclamado.fabrica = $login_fabrica
                        LEFT JOIN tbl_defeito_constatado ON tmp_os_$login_admin.defeito_constatado     = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
                        LEFT JOIN tbl_servico_realizado  ON tmp_os_$login_admin.servico_realizado = tbl_servico_realizado.servico_realizado  AND tbl_servico_realizado.fabrica=$login_fabrica
                        LEFT JOIN tbl_solucao            ON tmp_os_$login_admin.solucao_os             = tbl_solucao.solucao AND tbl_solucao.fabrica=$login_fabrica
                        WHERE tmp_os_$login_admin.fabrica = $login_fabrica 
                        $where_estado ;";

                        
        $sql .= "CREATE INDEX tmp_os_os_fabrica ON tmp_os_os_$login_admin(fabrica);";
        $sql .= "CREATE INDEX tmp_os_os_pedido ON tmp_os_os_$login_admin(pedido);";

        #echo nl2br($sql)."<br><br>";
        $res = pg_query($con, $sql);

        $msg_erro .= pg_errormessage($con);

        $sql = " SELECT tmp_os_os_$login_admin.*,
                            to_char (tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
                             case
                                 when tbl_pedido_item.qtde = tbl_pedido_item.qtde_faturada then
                                      'FATURADO INTEGRAL'
                                 when tbl_pedido_item.qtde = tbl_pedido_item.qtde_cancelada then
                                      'CANCELADO TOTAL'
                                 when tbl_pedido_item.qtde < tbl_pedido_item.qtde_faturada then
                                      'FATURADO PARCIAL'
                                 else
                                      'AGUARDANDO FATURAMENTO'
                              end     AS status_pedido,
                            tbl_pedido.status_pedido as ped_status_pedido
            INTO TEMP tmp_os_pedido_$login_admin
                    FROM tmp_os_os_$login_admin
                    LEFT JOIN tbl_pedido             ON tmp_os_os_$login_admin.pedido            = tbl_pedido.pedido AND tbl_pedido.fabrica = $login_fabrica
                    LEFT JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido AND tmp_os_os_$login_admin.peca = tbl_pedido_item.peca
                    WHERE tmp_os_os_$login_admin.fabrica = $login_fabrica;";

        $res      = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);
        #echo nl2br($sql)."<br><br>";

        $sql = "SELECT tbl_faturamento_item.pedido,nota_fiscal,emissao 
            INTO TEMP tmp_faturamento_$login_admin
            FROM tbl_faturamento
            JOIN tbl_faturamento_item USING(faturamento)
            WHERE fabrica=$login_fabrica
            and tbl_faturamento_Item.pedido in (select pedido from tmp_os_pedido_$login_admin);

            alter table tmp_os_pedido_$login_admin add nota_fiscal character varying(20);
            alter table tmp_os_pedido_$login_admin add emissao date;

            UPDATE tmp_os_pedido_$login_admin SET nota_fiscal = tmp_faturamento_$login_admin.nota_fiscal, emissao = tmp_faturamento_$login_admin.emissao
                              FROM tmp_faturamento_$login_admin 
                              WHERE tmp_faturamento_$login_admin.pedido = tmp_os_pedido_$login_admin.pedido;";
        $res      = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);
        #echo nl2br($sql)."<br><br>";

        $sql ="SELECT * FROM tmp_os_pedido_$login_admin;";
        $res      = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);
        #echo nl2br($sql)."<br><br>";
        $arquivo_nome2 = "os_por_posto_peca_" . $login_admin . ".csv";

        if ($_serverEnvironment == "production") {
            $arquivo_nome  = "/tmp/os_por_posto_peca_" . $login_admin . ".csv";
            $arquivo_zip   = "/var/www/assist/www/admin/xls/os_por_posto_peca_" . $login_admin . ".zip";
            $arquivo_link  = "/assist/admin/xls/os_por_posto_peca_" . $login_admin . ".zip";
       } else {
            $arquivo_nome  = "xls/os_por_posto_peca_" . $login_admin . ".csv";
            $arquivo_zip   = "xls/os_por_posto_peca_" . $login_admin . ".zip";
            $arquivo_link  = $arquivo_nome;
        }

	if (is_file($arquivo_zip)) {
		unlink($arquivo_zip);
	}

	if (is_file($arquivo_nome)) {
		unlink($arquivo_nome);
	}

	$arquivo = @fopen($arquivo_nome, "w");

	 if (!is_resource($arquivo)) {
	 	$msg_erro .= 'Erro ao gerar arquivo, entre em contato com o suporte.';
	 }

	if (!empty($msg_erro)) {

		echo "<div class='alert alert-danger'><h4>$msg_erro</h4></div>";

	} else if (pg_numrows($res) > 0) {

		ob_flush();
		flush();

		fwrite($arquivo, "Sua OS");
        fwrite($arquivo, ";");
		fwrite($arquivo, "Consumidor/Revenda");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Consumidor Nome");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Consumidor Fone");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Número de Série");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Solução");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Data Digitação");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Data Abertura");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Data Fechamento");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Data Finalizada");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Data Conserto");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Data NF Compra");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Dias de Uso");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Marca");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Produto Referência");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Produto Descrição");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Linha");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Familia");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Peça Referência");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Peça Descrição");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Defeito Reclamado");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Defeito Constatado");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Serviço");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Digitação Item");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Código Posto");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Nome Posto");
		fwrite($arquivo, ";");
        fwrite($arquivo, "Estado");
        fwrite($arquivo, ";");
		fwrite($arquivo, "Nome Revenda");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Nota Fiscal");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Emissão");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Status do Pedido");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Status da OS");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Responsável");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Data");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Trocado Por");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Causa da Troca");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Observação Troca");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Justificativa do Posto");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Aparencia geral do aparelho/produto");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Acessórios deixados junto com o aparelho");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Informações sobre o defeito");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Observações da OS");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Orientações do SAC ao Posto Autorizado");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Data de Conferencia");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Previsão de Pagamento");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Nota Fiscal Conferência");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Justificativa do Pedido de Peça");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Técnico Responsável");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Pedido");
		fwrite($arquivo, ";");
		fwrite($arquivo, "Data Pedido");

        if ($login_fabrica == 3) { /*HD - 6078292*/
            fwrite($arquivo, ";");
            fwrite($arquivo, "Tipo de Atendimento");
        }


        fwrite($arquivo, ";");
        fwrite($arquivo, "E-mail Posto");
        fwrite($arquivo, ";");
        fwrite($arquivo, "Telefone Posto");
        fwrite($arquivo, ";");
        fwrite($arquivo, "Inspetor");
        fwrite($arquivo, ";");

		fwrite($arquivo, "\n");

		$extrato_anterior = '';

		for ($i = 0; $i < pg_num_rows($res); $i++) {

			$os                          = pg_fetch_result($res, $i, 'os');
			$sua_os                      = pg_fetch_result($res, $i, 'sua_os');
			$pedido                      = pg_fetch_result($res, $i, 'pedido');
			$data_pedido                 = pg_fetch_result($res, $i, 'data_pedido');
			$consumidor_nome             = pg_fetch_result($res, $i, 'consumidor_nome');
			$consumidor_revenda          = pg_fetch_result($res, $i, 'consumidor_revenda');
			$consumidor_fone             = pg_fetch_result($res, $i, 'consumidor_fone');
			$serie                       = pg_fetch_result($res, $i, 'serie');
			$solucao                     = pg_fetch_result($res, $i, 'solucao');
			$data_digitacao              = pg_fetch_result($res, $i, 'data_digitacao');
			$data_abertura               = pg_fetch_result($res, $i, 'data_abertura');
			$data_fechamento             = pg_fetch_result($res, $i, 'data_fechamento');
			$data_finalizada             = pg_fetch_result($res, $i, 'data_finalizada');
			$data_conserto               = pg_fetch_result($res, $i, 'data_conserto');
			$data_nf                     = pg_fetch_result($res, $i, 'data_nf');
			$dias_uso                    = pg_fetch_result($res, $i, 'dias_uso');
			$marca_nome                  = pg_fetch_result($res, $i, 'marca_nome');
			$produto_referencia          = pg_fetch_result($res, $i, 'produto_referencia');
			$produto_descricao           = pg_fetch_result($res, $i, 'produto_descricao');
			$linha                       = pg_fetch_result($res, $i, 'linha');
			$familia                     = pg_fetch_result($res, $i, 'familia');
			$peca_referencia             = pg_fetch_result($res, $i, 'peca_referencia');
			$peca_descricao              = pg_fetch_result($res, $i, 'peca_descricao');
			$servico                     = pg_fetch_result($res, $i, 'servico');
			$defeito_constatado          = pg_fetch_result($res, $i, 'defeito_constatado');
			$defeito_reclamado           = pg_fetch_result($res, $i, 'defeito_reclamado');
			$data_digitacao_item         = pg_fetch_result($res, $i, 'data_digitacao_item');
			$codigo_posto                = pg_fetch_result($res, $i, 'codigo_posto');
			$nome_posto                  = pg_fetch_result($res, $i, 'nome_posto');
			$estado_posto                = pg_fetch_result($res, $i, 'estado_posto');
			$revenda_nome                = pg_fetch_result($res, $i, 'revenda_nome');
			$nota_fiscal                 = pg_fetch_result($res, $i, 'nota_fiscal');
			$emissao                     = pg_fetch_result($res, $i, 'emissao');


			#$sqlNF = "SELECT tbl_faturamento.emissao, tbl_faturamento.nota_fiscal
			#	      FROM tbl_faturamento JOIN tbl_faturamento_item USING(faturamento)
			#	      WHERE tbl_faturamento_item.pedido = ".pg_fetch_result($res, $i, 'pedido')."
			#	      AND tbl_faturamento_item.os = ".pg_fetch_result($res, $i, 'os')."
			#	      AND tbl_faturamento_item.peca = ".pg_fetch_result($res, $i, 'peca')."
			#	      AND tbl_faturamento.fabrica = ".$login_fabrica."
			#	      ORDER BY tbl_faturamento.emissao DESC LIMIT 1;";
			//echo $sqlNF;
			#$resNF      = pg_query($con, $sqlNF);
			#$msg_erro = pg_errormessage($con);
			#$NF_Emissao[$i] = (pg_fetch_all($resNF));
			//var_dump($resNF[0]['emissao']);
			#$nota_fiscal                 = $NF_Emissao[$i][0]['nota_fiscal'];
			#$emissao                     = $NF_Emissao[$i][0]['emissao'];
			$status_pedido               = pg_fetch_result($res, $i, 'status_pedido');
			$status_os                   = pg_fetch_result($res, $i, 'status_os');
			$troca_admin                 = pg_fetch_result($res, $i, 'troca_admin');
			$data_troca                  = pg_fetch_result($res, $i, 'data_troca');
			$setor_troca                 = pg_fetch_result($res, $i, 'setor_troca');
			$situacao_atend_troca        = pg_fetch_result($res, $i, 'situacao_atend_troca');
			$observacao_troca            = pg_fetch_result($res, $i, 'observacao_troca');
			$peca_referencia_troca       = pg_fetch_result($res, $i, 'peca_referencia_troca');
			$peca_descricao_troca        = pg_fetch_result($res, $i, 'peca_descricao_troca');
			$modalidade_transporte_troca = pg_fetch_result($res, $i, 'modalidade_transporte_troca');
			$envio_consumidor_troca      = pg_fetch_result($res, $i, 'envio_consumidor_troca');
			$orientacao_sac              = pg_fetch_result($res, $i, 'orientacao_sac');
			$causa_troca                 = pg_fetch_result($res, $i, 'causa_troca');
			$aparencia_produto           = pg_fetch_result($res, $i, 'aparencia_produto');
			$acessorios                  = pg_fetch_result($res, $i, 'acessorios');
			$df_descricao                = pg_fetch_result($res, $i, 'df_descricao');
			$obs_os                      = pg_fetch_result($res, $i, 'obs_os');
			$obs_reincidencia            = pg_fetch_result($res, $i, 'obs_reincidencia');
			$extrato                     = pg_fetch_result($res, $i, 'extrato');
			$nome_tecnico                = pg_fetch_result($res, $i, 'nome_tecnico');
			$status_os_dados             = pg_fetch_result($res, $i, 'status_os_dados');
            $inspetor                    = pg_fetch_result($res, $i, 'admin_sap');
            $contato_email               = pg_fetch_result($res, $i, 'email_posto');
            $posto_fone                  = pg_fetch_result($res, $i, 'posto_fone');

			$status_os_dados = str_replace("{","",$status_os_dados);
			$status_os_dados = str_replace("}","",$status_os_dados);

            if ($login_fabrica == 3) { /*HD - 6078292*/
                $aux_sql = "SELECT tbl_tipo_atendimento.descricao AS tipo_atendimento FROM tbl_tipo_atendimento JOIN tbl_os ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento AND tbl_os.fabrica = $login_fabrica WHERE tbl_os.os = $os";
                $aux_res = pg_query($con, $aux_sql);
                $tipo_atendimento = utf8_decode(pg_fetch_result($aux_res, 0, 'tipo_atendimento'));
            }

			//HD 321633

			if (strlen($extrato)>0){

				$sql_extrato_conferencia = "SELECT 	TO_CHAR(data_conferencia,'dd/mm/yyyy') AS data_conferencia,
													nota_fiscal,
													TO_CHAR(previsao_pagamento,'dd/mm/yyyy') AS previsao_pagamento
											FROM tbl_extrato_conferencia
											WHERE tbl_extrato_conferencia.extrato= $extrato";
				$res_extrato_conferencia = pg_query($con,$sql_extrato_conferencia);

				if ( $extrato != $extrato_anterior and pg_num_rows($res_extrato_conferencia)>0){

					$xdata_conferencia   = pg_result($res_extrato_conferencia,0,'data_conferencia');
					$xnota_fiscal        = pg_result($res_extrato_conferencia,0,'nota_fiscal');
					$xprevisao_pagamento = pg_result($res_extrato_conferencia,0,'previsao_pagamento');

					$xdata_conferencia   = str_replace(";"," ",$xdata_conferencia);
					$xnota_fiscal        = str_replace(";"," ",$xnota_fiscal);
					$xprevisao_pagamento = str_replace(";"," ",$xprevisao_pagamento);


				}
				$extrato_anterior = $extrato;

			}else{
				$xdata_conferencia   = " ";
				$xnota_fiscal        = " ";
				$xprevisao_pagamento = " ";
			}

			//HD 321633 fim

			$sua_os                      = str_replace(";"," ",$sua_os);
			$consumidor_revenda          = str_replace(";"," ",$consumidor_revenda);
			$pedido                      = str_replace(";"," ",$pedido);
            $data_pedido                 = str_replace(";"," ",$data_pedido);
			$consumidor_nome             = str_replace(";"," ",$consumidor_nome);
			$consumidor_nome             = str_replace("\r"," ",$consumidor_nome);
			$consumidor_fone             = str_replace(";"," ",$consumidor_fone);
			$serie                       = str_replace(";"," ",$serie);
			$solucao                     = str_replace(";"," ",$solucao);
			$data_digitacao              = str_replace(";"," ",$data_digitacao);
			$data_abertura               = str_replace(";"," ",$data_abertura);
			$data_fechamento             = str_replace(";"," ",$data_fechamento);
			$data_finalizada             = str_replace(";"," ",$data_finalizada);
			$data_conserto               = str_replace(";"," ",$data_conserto);
			$data_nf                     = str_replace(";"," ",$data_nf);
			$dias_uso                    = str_replace(";"," ",$dias_uso);
			$marca_nome                  = str_replace(";"," ",$marca_nome);
			$produto_referencia          = str_replace(";"," ",$produto_referencia);
			$produto_descricao           = str_replace(";"," ",$produto_descricao);
			$linha                       = str_replace(";"," ",$linha);
			$familia                     = str_replace(";"," ",$familia);
			$peca_referencia             = str_replace(";"," ",$peca_referencia);
			$peca_descricao              = str_replace(";"," ",$peca_descricao);
			$servico                     = str_replace(";"," ",$servico);
			$defeito_constatado          = str_replace(";"," ",$defeito_constatado);
            $defeito_reclamado           = str_replace(";"," ",$defeito_reclamado);
			$defeito_constatado  = str_replace ([";","/"],",",$defeito_constatado);
			$defeito_reclamado   = str_replace ([";","/"],",",$defeito_reclamado);
			$data_digitacao_item         = str_replace(";"," ",$data_digitacao_item);
			$codigo_posto                = str_replace(";"," ",$codigo_posto);
			$nome_posto                  = str_replace(";"," ",$nome_posto);
			$estado_posto                = str_replace(";"," ",$estado_posto);
			$revenda_nome                = str_replace(";"," ",$revenda_nome);
			$nota_fiscal                 = str_replace(";"," ",$nota_fiscal);
			$emissao                     = str_replace(";"," ",$emissao);
			$status_pedido               = str_replace(";"," ",$status_pedido);
			$status_os                   = str_replace(";"," ",$status_os);
			$troca_admin                 = str_replace(";"," ",$troca_admin);
			$data_troca                  = str_replace(";"," ",$data_troca);
			$setor_troca                 = str_replace(";"," ",$setor_troca);
			$situacao_atend_troca        = str_replace(";"," ",$situacao_atend_troca);
			$peca_referencia_troca       = str_replace(";"," ",$peca_referencia_troca);
			$peca_descricao_troca        = str_replace(";"," ",$peca_descricao_troca);
			$modalidade_transporte_troca = str_replace(";"," ",$modalidade_transporte_troca);
			$envio_consumidor_troca      = str_replace(";"," ",$envio_consumidor_troca);
			$df_descricao                = str_replace(";"," ",$df_descricao);
			$df_descricao                = str_replace("null"," ",$df_descricao);
			$aparencia_produto           = str_replace(";"," ",$aparencia_produto);
			$acessorios                  = str_replace("null"," ",(str_replace (";"," ",$acessorios)));
			$orientacao_sac            	= str_replace("\r"," ",str_replace("\t"," ",str_replace("<br />"," ",str_replace("\n"," ",str_replace("null"," ",str_replace(";"," ",$orientacao_sac))))));
			$obs_os            			= str_replace("\r"," ",str_replace("\t"," ",str_replace("<br />"," ",str_replace("\n"," ",str_replace("null"," ",str_replace(";"," ",$obs_os))))));
			$obs_reincidencia            = str_replace("\r"," ",str_replace("\t"," ",str_replace("<br />"," ",str_replace("\n"," ",str_replace("null"," ",str_replace(";"," ",$obs_reincidencia))))));
			$observacao_troca            = str_replace("\r"," ",str_replace("\t"," ",str_replace("<br />"," ",str_replace("\n"," ",str_replace("null"," ",str_replace(";"," ",$observacao_troca))))));

			fwrite($arquivo, $sua_os             );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $consumidor_revenda );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $consumidor_nome    );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $consumidor_fone    );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $serie              );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $solucao            );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $data_digitacao     );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $data_abertura      );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $data_fechamento    );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $data_finalizada    );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $data_conserto      );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $data_nf            );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $dias_uso           );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $marca_nome         );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $produto_referencia );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $produto_descricao  );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $linha              );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $familia            );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $peca_referencia    );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $peca_descricao     );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $defeito_reclamado  );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $defeito_constatado );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $servico            );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $data_digitacao_item);
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $codigo_posto       );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $nome_posto         );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $estado_posto       );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $revenda_nome       );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $nota_fiscal        );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $emissao            );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $status_pedido      );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $status_os          );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $troca_admin        );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $data_troca         );
			fwrite($arquivo, ";"                 );

			if (!empty($peca_referencia_troca)) {
				fwrite($arquivo, $peca_referencia_troca." - ".$peca_descricao_troca);
			} else {
				fwrite($arquivo, " " );
			}

			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $causa_troca        );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $observacao_troca   );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $obs_reincidencia   );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $aparencia_produto  );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $acessorios         );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $df_descricao       );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $obs_os             );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $orientacao_sac     );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $xdata_conferencia  );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $xprevisao_pagamento);
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $xnota_fiscal       );
			fwrite($arquivo, ";"                 );

			if (strlen($status_os_dados) > 0) {

				$status_os_dados = explode('","',$status_os_dados);

				$conteudo = '';

				foreach ($status_os_dados as $status_os_item) {

					$status_os_item = str_replace('"',"",$status_os_item);
					$status_os_item = explode('||',$status_os_item);

					if (($status_os_item[1] == 72 OR  $status_os_item[1] == 64) AND strlen($status_os_item[2]) > 0) {
						$pos = strpos($status_os_item[2], 'Justificativa:');

						if ($pos !== false) {
							$status_os_item[2] = strstr($status_os_item[2],"Justificativa:");
							$status_os_item[2] = str_replace("Justificativa:"," ",$status_os_item[2]);
						}

					}

					$status_os_item[2] = trim($status_os_item[2]);

					if (strlen($status_os_item[2]) == 0 AND $status_os_item[1] == 73) $status_os_item[2] = "Autorizado";
					if (strlen($status_os_item[2]) == 0 AND $status_os_item[1] == 72) $status_os_item[2] = "-";

					if (strlen($status_os_item[3]) > 0) {
						$status_os_item[3] = " ($status_os_item[3])";
					}

					$conteudo.= "Data: $status_os_item[4]     ";
					$status_os_item[1] = trim($status_os_item[1]);

					switch ($status_os_item[1]) {
						case '72':
							$conteudo.= 'Justificativa do Posto: ';
							break;
						case '73':
							$conteudo.= 'Resposta da Fábrica: ';
							break;
						case '62':
							$conteudo.= 'OS em Intervenção ';
							break;
						case '65':
							$conteudo.= 'OS em reparo na Fábrica';
							break;
						case '64':
							$conteudo.= 'Resposta da Fábrica: ';
							break;
						case '87':
						case '88':
						case '116':
						case '116':
							$conteudo.= 'Fábrica: ';
							break;
					}

					/*if ($status_os_item[1] == 72)
						$conteudo .= "Justificativa do Posto: ";
					if ($status_os_item[1] == 73)
						$conteudo .= "Resposta da Fábrica: ";
					if ($status_os_item[1] == 62)
						$conteudo .= "OS em Intervenção ";
					if ($status_os_item[1] == 65)
						$conteudo .= "OS em reparo na Fábrica ";
					if ($status_os_item[1] == 64)
						$conteudo .= "Resposta da Fábrica: ";
					if ($status_os_item[1] == 87 OR $status_os_item[1] == 116) {
						$conteudo .= "Fábrica: ";
					}

					if ($status_os_item[1] == 88 OR $status_os_item[1] == 117) {
						$conteudo .= "Fábrica:";
					}*/

					$conteudo.= $status_os_item[2] . '     ';

					#$conteudo .= "     Obs: $status_observacao     ";


				}

				$conteudo = str_replace("\r"," ",$conteudo);
				$conteudo = str_replace("\t"," ",$conteudo);
				$conteudo = str_replace("<br />"," ",$conteudo);
				$conteudo = str_replace("\n"," ",$conteudo);
				$conteudo = str_replace("null"," ",$conteudo);
				$conteudo = str_replace(";"," ",$conteudo);

				fwrite($arquivo,$conteudo);

			}

			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $nome_tecnico       );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $pedido             );
			fwrite($arquivo, ";"                 );
			fwrite($arquivo, $data_pedido        );

            if ($login_fabrica == 3) { /*HD - 6078292*/
                fwrite($arquivo, ";"               );
                fwrite($arquivo, $tipo_atendimento );                
            }

            fwrite($arquivo, ";"                 );
            fwrite($arquivo, $contato_email      );
            fwrite($arquivo, ";"                 );
            fwrite($arquivo, $posto_fone         );
            fwrite($arquivo, ";"                 );
            fwrite($arquivo, $inspetor           );
            fwrite($arquivo, ";"                 );

			fwrite($arquivo, "\n"                );

		}
		#echo $conteudo;
		fclose($arquivo);
     	system("zip $arquivo_zip $arquivo_nome > /dev/null");
		// $h = proc_open("zip $arquivo_zip $arquivo_nome","w");

  //       $status = proc_get_status($h);

  //       if($status['running'] == true){
  //   		proc_close($h);
  //           $status = proc_get_status($h);
  //       }

  //       if($status['running'] !== true){
  //   		// system("cd xls/ && zip $arquivo_zip $arquivo_nome2 > /dev/null");
  //   		echo "<div class='tac'>";
  //   			echo "<input type='button' class='btn' value='Download do Arquivo' onclick=\"window.location='".$arquivo_link."'\">";
  //   		echo "</div>";

  //           if(file_exists($arquivo_link)){
  //       		echo "<script>
  //                   if($('#loading').is(':visibled')){
  //                       $('#loading').hide();$('#loading-block').hide();
  //                   }
  //               </script>";
  //           }
  //       }

		$h = popen("cd /xls && zip $arquivo_zip $arquivo_nome2","r");
        pclose($h);
        // system("cd xls/ && zip $arquivo_zip $arquivo_nome2 > /dev/null");
        echo "<div class='tac'>";
            echo "<input type='button' class='btn' value='Download do Arquivo' onclick=\"window.location='".$arquivo_link."'\">";
        echo "</div>";
        echo "<script>document.getElementById('msg_carregando').style.display='none';</script>";
        ob_flush();
        flush();

	} else {

		echo "<div class='alert alert-warning'><h4>Nenhum Resultado Encontrado</h4></div>";

	}

}

echo "<br /> <br /> <br />";

include "rodape.php";

?>
