<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';
include "autentica_admin.php";

set_time_limit(0);

$admin_privilegios = "gerencia";
$layout_menu = "gerencia";

if ($_POST["btn_acao"] == "pesquisar") { 

    $posto         = "";
    $condPosto     = "";
    $msg_erro      = [];
    $codigo_posto  = $_POST['codigo_posto'];
    $mes_inicial   = $_POST['mes_inicial'];
    $ano_inicial   = $_POST['ano_inicial'];
    $mes_final     = $_POST['mes_final'];
    $ano_final     = $_POST['ano_final'];
    $xano          = $_POST['ano'];
    $xmes          = $_POST['mes'];

    if (empty($mes_inicial)) {
        $msg_erro["msg"]  = "Preencha os campos obrigatórios";
        $msg_erro["campo"][] = "mes_inicial";
    }

    if (empty($ano_inicial)) {
        $msg_erro["msg"]  = "Preencha os campos obrigatórios";
        $msg_erro["campo"][] = "ano_inicial";
    }

    if (empty($mes_final)) {
        $mes_final = date('m');
    }

    if (empty($ano_final)) {
        $ano_final = date('Y');
    }

    if (strtotime($ano_inicial) > strtotime($ano_final)) {
        $msg_erro["msg"]  = "Data inválida";
        $msg_erro["campo"][] = "ano_inicial";
    }

    $ultimo_dia = date("t", mktime(0,0,0,$mes_final,'01',$ano_final));

    $data_ini = $ano_inicial."-".$mes_inicial."-01";
    $data_f   = $ano_final."-".$mes_final."-".$ultimo_dia;
    $data_fim = date("Y-m-t", strtotime($data_f));

    $d_i = new DateTime($data_ini);
    $d_f = new DateTime($data_f);
    $mes_diff = $d_i->diff($d_f);

    $qtde_mes = ($mes_diff->days <= 31) ? 1 : ($mes_diff->days <= 62) ? 2 : 3;

    if ($mes_diff->days > 93) {
        $msg_erro["msg"]  = "Intervalo entre as datas não pode ser superior a 3 meses";
        $msg_erro["campo"][] = "mes_inicial";
        $msg_erro["campo"][] = "mes_final";    
    }

    if (strlen($codigo_posto) > 0) {
        $sqlPosto = "SELECT posto 
                       FROM tbl_posto_fabrica 
                      WHERE fabrica={$login_fabrica} 
                        AND codigo_posto='{$codigo_posto}'";
        $resPosto = pg_query($con, $sqlPosto);

        if (pg_num_rows($resPosto) > 0) {
            $posto = pg_fetch_result($resPosto, 0, 'posto');
        }

    }

    if (count($msg_erro["msg"]) == 0) {
        $dadosConsultaOS = [];
        $dadosConsultaPedido = [];
        $dadosConsultaOSPedido = [];

        if (strlen($posto) > 0) {
            $condPostoOS     = " AND tbl_os.posto = {$posto}";
            $condPostoPedido = " AND tbl_pedido.posto = {$posto}";
        }

        $sql_qtde_os = "SELECT count(1) AS qtde_os, 
                               count(1) FILTER(WHERE data_fechamento <= data_abertura + INTERVAL '1 MONTHS') AS qtde_os_aberta_finalizada_mes,
                               count(1) FILTER(WHERE data_fechamento > data_abertura + INTERVAL '1 MONTHS') AS qtde_os_aberta_finalizada_mais_mes,
                               count(1) FILTER(WHERE os_reincidente = TRUE) as qtde_reincidente, 
                               count(1) FILTER(WHERE data_abertura > data_digitacao + INTERVAL '5 DAYS') as qtde_os_mais_5_dia,
                               count(1) FILTER(WHERE data_abertura <= data_digitacao + INTERVAL '5 DAYS') as qtde_os_menos_5_dia,
                               tbl_posto_fabrica.posto
                        FROM tbl_os 
                        JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto 
                        JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                        WHERE tbl_os.fabrica = $login_fabrica
                        AND tbl_os.excluida IS NOT TRUE
                        $condPostoOS 
                        AND data_abertura BETWEEN '$data_ini 00:00:00' AND '$data_f 23:59:59' 
                        GROUP BY codigo_posto, tbl_posto_fabrica.posto";
        $res_qtde_os = pg_query($con, $sql_qtde_os); 
        if (pg_num_rows($res_qtde_os) > 0) {
            $dadosConsultaOS = pg_fetch_all($res_qtde_os);
        }

        $sql_os_pedido = "  SELECT DISTINCT 
                                count(DISTINCT tbl_os.os) AS qtde_os_com_peca, 
                                count(DISTINCT tbl_os.os) FILTER(WHERE tbl_pedido.data > tbl_os.data_abertura + INTERVAL '5 DAYS') AS qtde_os_mais_5_dia_pedido,
                                count(DISTINCT tbl_os.os) FILTER(WHERE tbl_pedido.data <= tbl_os.data_abertura + INTERVAL '5 DAYS') AS qtde_os_menos_5_dia_pedido,
                                count(tbl_os.os) AS qtde_peca,
                                tbl_posto_fabrica.posto
                            FROM tbl_os 
                            JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
                            JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
                            JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
                            JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
                            JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto 
                            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                            WHERE tbl_os.fabrica = $login_fabrica
                            AND tbl_os.excluida IS NOT TRUE
                            $condPostoOS
                            AND (tbl_pedido_item.qtde_cancelada = 0 OR tbl_pedido_item.qtde_cancelada IS NULL)
                            AND tbl_os.data_abertura BETWEEN '$data_ini 00:00:00' AND '$data_f 23:59:59'
                            GROUP BY codigo_posto, tbl_posto_fabrica.posto";
        $res_os_pedido = pg_query($con, $sql_os_pedido);
        if (pg_num_rows($res_os_pedido) > 0) {
            $dadosConsultaOSPedido = pg_fetch_all($res_os_pedido);
        }
        
        if (pg_last_error()) {
            $msg_erro["msg"] = "Erro ao efetuar a consulta";
        } 
    }
}


$title = "RELATÓRIO DE CLASSIFICAÇÃO DOS POSTOS";
include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "shadowbox",
    "mask",
    "alphanumeric",
    "dataTable",
);

include("plugin_loader.php");
?>

<script type="text/javascript" charset="utf-8">
    $(function() {
        $("span[rel=lupa]").click(function () {
                    $.lupa($(this));
        });
        Shadowbox.init();
        $.autocompleteLoad(Array("posto"));

        $.dataTableLoad({table: "#tabela"});

    });

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

</script>
<?php 
    if (count($msg_erro["msg"]) > 0) {
        echo "
            <div class='alert alert-danger'>
                <h4>".$msg_erro["msg"]."</h4>
            </div>";
    }
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_rel" method="POST" action="ranking_postos.php">
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class='span2'>
            <div class='control-group  <?=(in_array("mes_inicial", $msg_erro["campo"])) ? "error" : ""?>'>
                <label class='control-label' for='mes_inicial'>Mês Inicial</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <select class="span12" name="mes_inicial" id="mes_inicial">
                        <option value="">Selecione ...</option>
                        <?php 
                            for ($i=1; $i <= 12 ; $i++) { 
                                $mes_inicial = str_pad($i,2,"0", STR_PAD_LEFT);
                                $selected = ($mes_inicial == $_POST["mes_inicial"]) ? "selected" : "";
                                echo "<option value='{$mes_inicial}' {$selected }>{$mes_inicial}</option>";
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group  <?=(in_array("ano_inicial", $msg_erro["campo"])) ? "error" : ""?>'>
                <label class='control-label' for='ano_inicial'>Ano Inicial</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <select class="span12" name="ano_inicial" id="ano_inicial">
                        <option value="">Selecione ...</option>
                        <?php 
                            for ($ano_inicial = 2004; $ano_inicial <= date("Y") ; $ano_inicial++) { 
                                $selected = ($ano_inicial == $_POST["ano_inicial"]) ? "selected" : "";
                                echo "<option value='{$ano_inicial}' {$selected }>{$ano_inicial}</option>";
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class='span2'>
            <div class='control-group'>
                <label class='control-label' for='mes_final'>Mês Final</label>
                <div class='controls controls-row'>
                    <select class="span12" name="mes_final" id="mes_final">
                        <option value="">Selecione ...</option>
                        <?php 
                            for ($i=1; $i <= 12 ; $i++) { 
                                $mes_final = str_pad($i,2,"0", STR_PAD_LEFT);
                                $selected = ($mes_final == $_POST["mes_final"]) ? "selected" : "";
                                echo "<option value='{$mes_final}' {$selected }>{$mes_final}</option>";
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group'>
                <label class='control-label' for='ano_final'>Ano Final</label>
                <div class='controls controls-row'>
                    <select class="span12" name="ano_final" id="ano_final">
                        <option value="">Selecione ...</option>
                        <?php 
                            for ($ano_final = 2004; $ano_final <= date("Y") ; $ano_final++) { 
                                $selected = ($ano_final == $_POST["ano_final"]) ? "selected" : "";
                                echo "<option value='{$ano_final}' {$selected }>{$ano_final}</option>";
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="span2"></div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="">Código do Posto</label>
                <div class="controls controls-row">
                    <div class="input-append">
                        <INPUT TYPE="text" class="frm" NAME="codigo_posto" value="<?=$codigo_posto?>" id="codigo_posto">
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="">Posto Autorizado</label>
                <div class="controls controls-row">
                    <div class="input-append">
                        <INPUT TYPE="text" class="frm" NAME="descricao_posto" value="<?=$descricao_posto?>" id="descricao_posto">
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div><br />
    <input type="hidden" name="btn_acao" value="pesquisar" />
    <input class="btn " type="submit" value="Pesquisar">
    <br /><br />
</form>
</div>

<?php if ($_POST["btn_acao"] == "pesquisar" && count($msg_erro["msg"]) == 0) { 

    $arquivoPostos = "xls/relatorio-ranking-{$login_fabrica}-".date('Y-m-d-h-i-s').".xls";
?>
    <div class='gerar_excel_os btn_excel' onclick="window.open('<?= $arquivoPostos ?>')">
        <span><img src='imagens/excel.png' /></span>
        <span class="txt">Gerar Arquivo Excel</span>
    </div>
<?php

        if (count($dadosConsultaOS) > 0 || count($dadosConsultaOSPedido) > 0) {
            $array_completo = [];

            if (count($dadosConsultaOS) >= count($dadosConsultaOSPedido)) {
                foreach ($dadosConsultaOS as $key => $value) {
                    $array_posto[] = $value['posto']; 
                }

                $array_posto = array_unique($array_posto);

                foreach ($array_posto as $key => $value) {
                    foreach ($dadosConsultaOS as $ky => $val) {
                        if ($value == $val['posto']) {
                            $array_completo[$value]['dadosConsultaOS'][] = $val; 
                        }
                    }

                    foreach ($dadosConsultaOSPedido as $ky => $val) {
                        if ($value == $val['posto']) {
                            $array_completo[$value]['dadosConsultaOSPedido'][] = $val; 
                        }
                    }
                }

            } else {
              
                foreach ($dadosConsultaOSPedido as $key => $value) {
                    $array_posto[] = $value['posto']; 
                }

                $array_posto = array_unique($array_posto);

                foreach ($array_posto as $key => $value) {
                    foreach ($dadosConsultaOS as $ky => $val) {
                        if ($value == $val['posto']) {
                            $array_completo[$value]['dadosConsultaOS'][] = $val; 
                        }
                    }

                    foreach ($dadosConsultaOSPedido as $ky => $val) {
                        if ($value == $val['posto']) {
                            $array_completo[$value]['dadosConsultaOSPedido'][] = $val; 
                        }
                    }
                }
            }

    ob_start();
    ?>
            <table class='table table-striped table-bordered table-hover ' id='tabela'>
              <thead>
                <tr class='titulo_coluna'>
                    <th nowrap>Código Posto</th>
		    <th nowrap>Descrição Posto</th>
		    <?php if($login_fabrica == 91){ ?>
			<th nowrap>E-mail</th>
		    <?php } ?>
                    <th nowrap>Qtde OS AB Mês</th>
                    <th nowrap>% Qtde OS AB Finalizada 30 Dias</th>
                    <th nowrap>% Qtde OS AB Finalizada Mais de 30 Dias</th>
                    <th nowrap>% Qtde OS Reincidente</th>
                    <th nowrap>Qtde OS AB Até 5 Dias</th>
                    <th nowrap>Qtde OS AB Mais de 5 Dias</th>
                    <th nowrap>Qtde de Peça Solicitada Mês</th>
                    <th nowrap>Média Peça/OS</th>
                    <th nowrap>% OS Com Pedidos Até 5 Dias</th>
                    <th nowrap>% OS Com Pedidos Mais de 5 Dias</th>
                    <th nowrap>Pontuação</th>
                    <th nowrap>Classificação</th>
                </tr>
              </thead>
              <tbody>
    <?php
            foreach ($array_completo as $posto => $dados) {

                $conteudo_dados_os = [];

                $qtde_os_mes = [];
                if (isset($dados['dadosConsultaOS'])) {
                    foreach ($dados['dadosConsultaOS'] as $position => $value) {

                        $sql_posto = "SELECT    nome AS nome_posto,
						cnpj AS codigo_posto,
						contato_email AS email
					FROM tbl_posto 
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                                        WHERE tbl_posto.posto = $posto";
                        $res_posto = pg_query($con, $sql_posto);
                        $codigo_posto = pg_fetch_result($res_posto, 0, 'codigo_posto');
			$nome_posto = pg_fetch_result($res_posto, 0, 'nome_posto');
			$email_posto = pg_fetch_result($res_posto, 0, 'email');
                    
                        $qtd_mes_porcentagem = 0;
                        $mes_chave = $posto;
                        
                        $conteudo_dados_os[$position]['posto'][] = "<td>".$value['posto']."</td>";
                        $conteudo_dados_os[$position]['codigo_posto'][] = "<td>".$codigo_posto."</td>";
			$conteudo_dados_os[$position]['nome_posto'][] = "<td>".$nome_posto."</td>";

			if($login_fabrica == 91){
				$conteudo_dados_os[$position]['email'][] = "<td>".$email_posto."</td>";
			}

                        $conteudo_dados_os[$position]['qtde_os'][] = "<td>".$value['qtde_os']."</td>";
                        $qtde_os_mes[$posto] = $value['qtde_os'];
                        $qtd_mes_porcentagem = number_format(($value['qtde_os_aberta_finalizada_mes'] * 100) / $value['qtde_os'], 2, ',', ' ');
                        
                        $conteudo_dados_os[$position]['qtd_mes_porcentagem'][] = "<td>".$qtd_mes_porcentagem."</td>";
                        if ($value['qtde_os'] == $value['qtde_os_aberta_finalizada_mes']) {
                            $conteudo_dados_os[$position][$mes_chave]['pontos'] += 40;
                        } else if ($value['qtde_os_aberta_finalizada_mes'] > 0) {
                            $conteudo_dados_os[$position][$mes_chave]['pontos'] += 5;
                        }

                        $qtd_os_mais_30 = $value['qtde_os'] - $value['qtde_os_aberta_finalizada_mes'];
                        $qtd_mais_mes_porcentagem = number_format(($qtd_os_mais_30 * 100) / $value['qtde_os'], 2, ',', ' ');
                        $conteudo_dados_os[$position]['qtd_mais_mes_porcentagem'][] = "<td>".$qtd_mais_mes_porcentagem."</td>";
                        if ($value['qtde_os'] == $qtd_os_mais_30) {
                            $conteudo_dados_os[$position][$mes_chave]['pontos'] -= 40;
                        } else if ($qtd_os_mais_30 > 0) {
                            $conteudo_dados_os[$position][$mes_chave]['pontos'] -= 5;
                        }

                        $qtde_reincidente_porcentagem = number_format(($value['qtde_reincidente'] * 100) / $value['qtde_os'], 2, ',', ' ');
                        $conteudo_dados_os[$position]['qtde_reincidente_porcentagem'][] = "<td>".$qtde_reincidente_porcentagem."</td>";
                        if ($value['qtde_reincidente'] == 0) {
                            $conteudo_dados_os[$position][$mes_chave]['pontos'] += 20;
                        } else if ($value['qtde_reincidente'] == 1) {
                            $conteudo_dados_os[$position][$mes_chave]['pontos'] += 5;
                        } else {
                            $conteudo_dados_os[$position][$mes_chave]['pontos'] -= 10;
                        }

                        $conteudo_dados_os[$position]['qtde_os_menos_5_dia'][] = "<td>".$value['qtde_os_menos_5_dia']."</td>";
                        if ($value['qtde_os'] == $value['qtde_os_menos_5_dia']) {
                            $conteudo_dados_os[$position][$mes_chave]['pontos'] += 10;
                        } else if ($value['qtde_os_menos_5_dia'] > 0) {
                            $conteudo_dados_os[$position][$mes_chave]['pontos'] += 3;
                        }                        

                        $conteudo_dados_os[$position]['qtde_os_mais_5_dia'][] = "<td>".$value['qtde_os_mais_5_dia']."</td>";
                        if ($value['qtde_os'] == $value['qtde_os_mais_5_dia']) {
                            $conteudo_dados_os[$position][$mes_chave]['pontos'] -= 10;
                        } else if ($value['qtde_os_mais_5_dia'] > 0) {
                            $conteudo_dados_os[$position][$mes_chave]['pontos'] -= 5;
                        }
                    }
                }

                if (isset($dados['dadosConsultaOSPedido'])) {
                    foreach ($dados['dadosConsultaOSPedido'] as $position => $value) {
                        $pp = str_replace(["<td>","</td>"], "", $conteudo_dados_os[$position]['posto']);
                        $pp = implode("", $pp);
                        if ($value['posto'] == $pp) {

                            $mes_chave = $value['posto'];
                        
                            $conteudo_dados_os[$position]['qtde_peca'][] = "<td>".$value['qtde_peca']."</td>";
                            $media_peca_os = number_format(($value['qtde_peca'] / $qtde_os_mes[$posto]), 2, ',', ' ');
                            $conteudo_dados_os[$position]['media_peca_os'][] = "<td>".$media_peca_os."</td>";
                            if ($media_peca_os <= 3) {
                                $conteudo_dados_os[$position][$mes_chave]['pontos'] += 10;
                            } else if ($media_peca_os <= 4) {
                                $conteudo_dados_os[$position][$mes_chave]['pontos'] += 5;
                            } else if ($media_peca_os <= 6) {
                                $conteudo_dados_os[$position][$mes_chave]['pontos'] += 3;
                            } else {
                                $conteudo_dados_os[$position][$mes_chave]['pontos'] += 1;
                            }

                            $os_menos_5_dia_porcentagem = number_format(($value['qtde_os_menos_5_dia_pedido'] * 100) / $value['qtde_os_com_peca'], 2, ',', ' ');
                            $conteudo_dados_os[$position]['os_menos_5_dia_porcentagem'][] = "<td>".$os_menos_5_dia_porcentagem."</td>";
                            if ($value['qtde_os_menos_5_dia_pedido'] == $value['qtde_os_com_peca']) {
                                $conteudo_dados_os[$position][$mes_chave]['pontos'] += 20;
                            } else if ($value['qtde_os_mais_5_dia_pedido'] > 0) {
                                $conteudo_dados_os[$position][$mes_chave]['pontos'] += 5;
                            }
                            
                            $os_mais_5_dia_porcentagem = number_format(($value['qtde_os_mais_5_dia_pedido'] * 100) / $value['qtde_os_com_peca'], 2, ',', ' ');
                            $conteudo_dados_os[$position]['os_mais_5_dia_porcentagem'][] = "<td>".$os_mais_5_dia_porcentagem."</td>";
                            if ($value['qtde_os_mais_5_dia_pedido'] == $value['qtde_os_com_peca']) {
                                $conteudo_dados_os[$position][$mes_chave]['pontos'][$mes_chave] -= 10;
                            } else if ($value['qtde_os_mais_5_dia_pedido'] > 0) {
                                $conteudo_dados_os[$position][$mes_chave]['pontos'][$mes_chave] -= 5;
                            }
                        }
                    }
                }

                for ($i = 0; $i < count($conteudo_dados_os); $i++) {
                    echo "<tr>";
                        echo (isset($conteudo_dados_os[$i]['codigo_posto'][$i])) ? $conteudo_dados_os[$i]['codigo_posto'][$i] : "<td></td>";
		    echo (isset($conteudo_dados_os[$i]['nome_posto'][$i])) ? $conteudo_dados_os[$i]['nome_posto'][$i] : "<td></td>";

		    if($login_fabrica == 91){
				echo (isset($conteudo_dados_os[$i]['email'][$i])) ? $conteudo_dados_os[$i]['email'][$i] : "<td></td>";
		    }

                        echo (isset($conteudo_dados_os[$i]['qtde_os'][$i])) ? $conteudo_dados_os[$i]['qtde_os'][$i] : "<td></td>";
                        echo (isset($conteudo_dados_os[$i]['qtd_mes_porcentagem'][$i])) ? $conteudo_dados_os[$i]['qtd_mes_porcentagem'][$i] : "<td></td>";
                        echo (isset($conteudo_dados_os[$i]['qtd_mais_mes_porcentagem'][$i])) ? $conteudo_dados_os[$i]['qtd_mais_mes_porcentagem'][$i] : "<td></td>";
                        echo (isset($conteudo_dados_os[$i]['qtde_reincidente_porcentagem'][$i])) ? $conteudo_dados_os[$i]['qtde_reincidente_porcentagem'][$i] : "<td></td>";
                        echo (isset($conteudo_dados_os[$i]['qtde_os_menos_5_dia'][$i])) ? $conteudo_dados_os[$i]['qtde_os_menos_5_dia'][$i] : "<td></td>";
                        echo (isset($conteudo_dados_os[$i]['qtde_os_mais_5_dia'][$i])) ? $conteudo_dados_os[$i]['qtde_os_mais_5_dia'][$i] : "<td></td>";
                        echo (isset($conteudo_dados_os[$i]['qtde_peca'][$i])) ? $conteudo_dados_os[$i]['qtde_peca'][$i] : "<td></td>";
                        echo (isset($conteudo_dados_os[$i]['media_peca_os'][$i])) ? $conteudo_dados_os[$i]['media_peca_os'][$i] : "<td></td>";
                        echo (isset($conteudo_dados_os[$i]['os_menos_5_dia_porcentagem'][$i])) ? $conteudo_dados_os[$i]['os_menos_5_dia_porcentagem'][$i] : "<td></td>";
                        echo (isset($conteudo_dados_os[$i]['os_mais_5_dia_porcentagem'][$i])) ? $conteudo_dados_os[$i]['os_mais_5_dia_porcentagem'][$i] : "<td></td>";
                        echo  "<td>".$conteudo_dados_os[$i][$mes_chave]['pontos']."</td>";

                        if ($conteudo_dados_os[$i][$mes_chave]['pontos'] >= 100) {
                            echo "<td>5 Estrelas</td>";    
                        } else if ($conteudo_dados_os[$i][$mes_chave]['pontos'] >= 86) {
                            echo "<td>4 Estrelas</td>";   
                        } else if ($conteudo_dados_os[$i][$mes_chave]['pontos'] >= 75) {
                            echo "<td>3 Estrelas</td>";   
                        } else if ($conteudo_dados_os[$i][$mes_chave]['pontos'] >= 56) {
                            echo "<td>2 Estrelas</td>";   
                        } else if ($conteudo_dados_os[$i][$mes_chave]['pontos'] <= 55) {
                            echo "<td>1 Estrelas</td>";   
                        }
                    echo "</tr>";
                } 
            } 
            echo "</tbody>";
        echo "</table>";
        }
        ?>
<?php 

    $excel = ob_get_contents();
    $fp = fopen($arquivoPostos,"w");
    fwrite($fp, $excel);
    fclose($fp);

} ?>
<script type="text/javascript">

    $(".juizado_procon").contents().filter(function(){
        return this.nodeType === 3;
    }).remove();

</script>

<?php include "rodape.php"; ?>
