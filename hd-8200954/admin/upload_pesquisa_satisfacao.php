<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = "UPLOAD PESQUISA DE SATISFAÇÃO";
$layout_menu = "callcenter";
$admin_privilegios = "call_center";

if (count($_FILES) > 0) {

    $arquivo = "/tmp/" . $_FILES['file-csv']['name'];

    if (!move_uploaded_file($_FILES['file-csv']['tmp_name'], $arquivo)) {
        $errorMsg['general'][] = "Não foi possível fazer o upload do arquivo";
    }

    $conteudo = file_get_contents($arquivo);
    $codificacao = mb_detect_encoding($conteudo.'x', 'UTF-8, ISO-8859-1');
    if($codificacao == 'ISO-8859-1'){
        $errorMsg['general'][] = "O arquivo esta no formato ISO-8859-1, o formato aceito é UTF-8";
    }

    $linhas = explode("\n", $conteudo);

    /** @var array $cidadesSemiCache */
    /** Essa variavel é utilizada para armazenar cidades ja encontradas em
     * SELECT's para serem reutilizadas, sem ter a necessidade de refazer o
     * SELECT que ta demorando um bocado...
     */
    $cidadesSemiCache = array();
    if(count($errorMsg['general']) == 0){
        /**
        O CLIENTE DESEJA QUE SEJA IMPORTADO SOMENTE SE TODAS LINHAS DEREM CERTO,
        POR ISSO A NECESSIDADE DE ABRIR UM BEGIN NO INICIO MESMO CONTENDO SELECTS NO INTERIOR DO SCRIPT,
        ISSO FOI FEITO DEVIDO A NECESSIDADE DO CLIENTE (BLACK & DECKER) USAR A TELA
        O PROCESSO CORRETO SERIA REFATORAR A TELA E REALIZAR OS INSERTS POSTERIOR
        A VALIDAÇÃO DAS LINHAS.
        */
        $res = pg_query($con, "BEGIN;");
        $qtdLinhas = 0;
        foreach ($linhas as $i => $linha) {
            if ($i == 0) {
                continue;
            }

            list(
                $pais,
                $cidade,
                $posto_autorizado,
                $os,
                $equipamento,
                $marca,
                $produto,
                $qual,
                $recomendacao,
                $principal_razao_pontuacao,
                $expandir_pontuacao,
                $tempo_reparo,
                $preco_reparo,
                $qualidade_reparo,
                $atencao_atendente,
                $explicacao_reparo,
                $aspecto_visual_posto_autorizado,
                $satisfacao_geral,
                $numero_dias_reparo,
                $mes,
                $ano,
                $nps_score
                ) = explode(";", $linha);


            $pais = trim($pais);
            $cidade = trim($cidade);
            $posto_autorizado = trim($posto_autorizado);
            $os = trim($os);
            $equipamento = trim($equipamento);
            $marca = trim($marca);
            $produto = trim($produto);
            $qual = trim($qual);
            $recomendacao = trim($recomendacao);
            $principal_razao_pontuacao = trim($principal_razao_pontuacao);
            $expandir_pontuacao = trim($expandir_pontuacao);
            $tempo_reparo = trim($tempo_reparo);
            $preco_reparo = trim($preco_reparo);
            $qualidade_reparo = trim($qualidade_reparo);
            $atencao_atendente = trim($atencao_atendente);
            $explicacao_reparo = trim($explicacao_reparo);
            $aspecto_visual_posto_autorizado = trim($aspecto_visual_posto_autorizado);
            $satisfacao_geral = trim($satisfacao_geral);
            $numero_dias_reparo = trim($numero_dias_reparo);
            $mes = trim($mes);
            $ano = trim($ano);
            $nps_score = trim($nps_score);

            $erros = array();

            if (empty($pais)) {
                $erros[] = "Informe o País";
            } else {
                $pais = strtolower(retira_acentos($pais));
                $sql = "SELECT pais FROM tbl_pais WHERE LOWER(pais) = '{$pais}' OR LOWER(fn_retira_especiais(nome)) = '{$pais}'";
                $res = pg_query($con, $sql);

                if (!pg_num_rows($res)) {
                    $erros[] = "País {$pais} não encontrado" . "\n";
                }
            }

            if (empty($cidade)) {
                $erros[] = "Informe a Cidade";
            } else {
                $cidadeSemAcento = strtolower(retira_acentos($cidade));
                if (!array_key_exists($cidadeSemAcento, $cidadesSemiCache)) {
                    $sql = "SELECT cidade FROM tbl_posto join tbl_posto_fabrica using(posto) WHERE LOWER(fn_retira_especiais(cidade)) = '{$cidadeSemAcento}' and fabrica = $login_fabrica";
                    $res = pg_query($con, $sql);

                    if (!pg_num_rows($res)) {
                        $erros[] = "Cidade {$cidade} não encontrada" . "\n";
                    } else {
                        $cidadeCache = pg_fetch_result($res, 0, "cidade");
                        $cidadesSemiCache[$cidadeSemAcento] = $cidadeCache;
                    }
                }
            }

    //        if (empty($posto_autorizado)) {
    //            $erros[] = "Informe o Posto Autorizado";
    //        } else {
    ////            $posto_autorizado = strtolower(retira_acentos($posto_autorizado));
    //            $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND (LOWER(fn_retira_especiais(nome_fantasia)) = LOWER(fn_retira_especiais('{$posto_autorizado}')) OR LOWER(fn_retira_especiais(nome)) = LOWER(fn_retira_especiais('{$posto_autorizado}')))";
    //
    //            file_put_contents("/tmp/teste1.txt", $sql . "\n", FILE_APPEND);
    //
    //            $res = pg_query($con, $sql);
    //
    //            if (!pg_num_rows($res)) {
    //                $erros[] = "Posto Autorizado {$posto_autorizado} não encontrado";
    //            } else {
    //                $posto_autorizado = pg_fetch_result($res, 0, "posto_autorizado");
    //            }
    //        }

    //        if (empty($os)) {
    //            $erros[] = "Informe a Ordem de Serviço";
    //        } else {
    //            $sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND sua_os = {$os}";
    //            $res = pg_query($con, $sql);
    //
    //            if (!pg_num_rows($res)) {
    //                $erros[] = "Ordem de Serviço {$os} não encontrada";
    //            }
    //        }

            $equipamentos = array(
                "martelo" => "Martillos",
                "furadeira" => "Taladros / H. Inalámbricas",
                "mecanica" => "Metalmecánica",
                "madeira" => "Madera",
                "serras" => "H. Estacionaria",
                "jardinagem" => "Jardín",
                "gasolina" => "Gasolina / Explosión",
                "pneumatica" => "Neumática",
                "outro" => "Otra"
            );

            if (empty($equipamento)) {
                $erros[] = "Informe o Equipamento" . "\n";
            } else {
                $e = array_search(utf8_decode($equipamento), $equipamentos);


                if ($e === false) {
                    $erros[] = "Equipamento não encontrado -> '" . $equipamento . "'\n";
                } else {
                    $equipamento = $e;
                }
            }

            if (empty($marca)) {
                $erros[] = "Informe a Marca";
            } else {
                $marca = strtolower($marca);

                $sql = "SELECT marca FROM tbl_marca WHERE fabrica = {$login_fabrica} AND LOWER(fn_retira_especiais(nome)) = fn_retira_especiais('{$marca}')";

                $res = pg_query($con, $sql);

                if (!pg_num_rows($res)) {
                    $erros[] = "Marca {$marca} não encontrada" . "\n";
                } else {
                    $marca = pg_fetch_result($res, 0, "marca");
                }
            }

            if (empty($produto)) {
                $erros[] = "Informe o Produto" . "\n";
            }

            if ($equipamento == "outro" && empty($qual)) {
                $erros[] = "Para o Equipamento Outro é necessário informar o campo Qual -> '" . $equipamento . " - " . $qual . "'\n";
            }

            if ($recomendacao < 0 || $recomendacao > 10) {
                $erros[] = "Recomendação deve ser entre 0 e 10" . "\n";
            }

            $principais_razoes = array(
                "atencao_suporte_telefonico" => "Atención y soporte (Telefónica)",
                "atencao_suporte_recepcao" => "Atención y soporte (Recepción del centro de servicio)",
                "falha_precoce_ferramenta" => "Falla temprana de la herramienta",
                "qualidade_produto" => "Calidad del producto",
                "tempo_de_resposta" => "Tiempo de respuesta",
                "custo_orcamento_reparo" => "Costo y/ó presupuesto de reparación",
                "rastreamento_reparacao" => "Seguimiento de la reparación",
                "tempo_repado" => "Tiempo de reparación",
                "qualidade_reparacao" => "Calidad de la reparación",
                "servico_prestado_centro" => "Servicio prestado por el centro de servicio"
            );

            if (empty($principal_razao_pontuacao)) {
                $erros[] = "Informe a Principal Razão para a pontuação" . "\n";
            } else {
                $e = array_search(utf8_decode($principal_razao_pontuacao), $principais_razoes, true);

                if ($e === false) {
                    $erros[] = "Principal Razão para a pontuação não encontrada, procurando por -> '" . $principal_razao_pontuacao . "'\n";
                } else {
                    $principal_razao_pontuacao = $e;
                }
            }

            $notas = array(
                "plenamente_satisfeito" => "totalmente satisfecho",
                "muito_satisfeito" => "bastante satisfecho",
                "satisfeito" => "neutral",
                "pouco_satisfeito" => "poco satisfecho",
                "insatisfeito" => "nada satisfecho"
            );

            if (!in_array(strtolower($tempo_reparo), $notas)) {
                $erros[] = "Tempo de Reparo deve ser alguma das seguintes opções: Totalmente satisfecho, Bastante Satisfecho, Neutral, Poco Satisfecho, Nada Satisfecho -> '" . $tempo_reparo . "'\n";
            } else {
                $tempo_reparo = array_search(strtolower($tempo_reparo), $notas);
            }

            if (!in_array(strtolower($preco_reparo), $notas)) {
                $erros[] = "Preço do Reparo deve ser alguma das seguintes opções: Totalmente satisfecho, Bastante Satisfecho, Neutral, Poco Satisfecho, Nada Satisfecho -> '" . $preco_reparo . "'\n";
            } else {
                $preco_reparo = array_search(strtolower($preco_reparo), $notas);
            }

            if (!in_array(strtolower($qualidade_reparo), $notas)) {
                $erros[] = "Qualidade do Reparo deve ser alguma das seguintes opções: Totalmente satisfecho, Bastante Satisfecho, Neutral, Poco Satisfecho, Nada Satisfecho -> '" . $qualidade_reparo . "'\n";
            } else {
                $qualidade_reparo = array_search(strtolower($qualidade_reparo), $notas);
            }

            if (!in_array(strtolower($atencao_atendente), $notas)) {
                $erros[] = "Atenção do Atendente deve ser alguma das seguintes opções: Totalmente satisfecho, Bastante Satisfecho, Neutral, Poco Satisfecho, Nada Satisfecho -> '" . $atencao_atendente . "'\n";
            } else {
                $atencao_atendente = array_search(strtolower($atencao_atendente), $notas);
            }

            if (!in_array(strtolower($explicacao_reparo), $notas)) {
                $erros[] = "Explicação do Reparo deve ser alguma das seguintes opções: Totalmente satisfecho, Bastante Satisfecho, Neutral, Poco Satisfecho, Nada Satisfecho -> '" . $expandir_pontuacao . "'\n";
            } else {
                $explicacao_reparo = array_search(strtolower($explicacao_reparo), $notas);
            }

            if (!in_array(strtolower($aspecto_visual_posto_autorizado), $notas)) {
                $erros[] = "Aspecto visual da Assistência deve ser alguma das seguintes opções: Totalmente satisfecho, Bastante Satisfecho, Neutral, Poco Satisfecho, Nada Satisfecho -> '" . $aspecto_visual_posto_autorizado . "'\n";
            } else {
                $aspecto_visual_posto_autorizado = array_search(strtolower($aspecto_visual_posto_autorizado), $notas);
            }

            if (!in_array(strtolower($satisfacao_geral), $notas)) {
                $erros[] = "Satisfação geral deve ser alguma das seguintes opções: Totalmente satisfecho, Bastante Satisfecho, Neutral, Poco Satisfecho, Nada Satisfecho -> '" . $satisfacao_geral . "'\n";
            } else {
                $satisfacao_geral = array_search(strtolower($satisfacao_geral), $notas);
            }

            if (empty($numero_dias_reparo) || ((is_numeric($numero_dias_reparo) && ($numero_dias_reparo > 8)) && $numero_dias_reparo != "Mais de 8")) {

                $erros[] = "Número de dias na assistência deve ser de 1 a 8 ou Mais de 8 -> '" . $numero_dias_reparo . "'\n";
            } else {
                if ($numero_dias_reparo == "Mais de 8") {
                    $numero_dias_reparo = "mais";
                }
            }

            $dia = explode(".",$mes);
            $dia = trim($dia[0]);

            if($dia == ""){
                $dia = "01";
            }
            $mes = preg_replace("([0-9]|\.|\s)", "", $mes);

            $meses = array(
                "01" => "Enero",
                "02" => "Febrero",
                "03" => "Marzo",
                "04" => "Abril",
                "05" => "Mayo",
                "06" => "Junio",
                "07" => "Julio",
                "08" => "Agosto",
                "09" => "Septiembre",
                "10" => "Octubre",
                "11" => "Noviembre",
                "12" => "Diciembre"
            );

            $e = array_search($mes, $meses);

            if ($e === false) {
                $erros[] = "Mês {$mes} inválido" . "\n";
            } else {
                $mes = $e;
            }

            if (empty($ano) || !is_numeric($ano)) {
                $erros[] = "Ano {$ano} inválido" . "\n";
            }

            if (!in_array($nps_score, array("Promoter", "Passive", "Detractor"))) {
                $erros[] = "NPS Score deve ser Promoter, Passive ou Detractor -> '" . $nps_score . "'\n";
            }

            if (count($erros) > 0) {

                $errorMsgs[$i] = $erros;

    //            file_put_contents("/tmp/teste1.txt", $i . " - " . implode("---", $erros) . "\n\n\n\n\n\n", FILE_APPEND);

                $erros = array();

                continue;
            }

            $resultado = array(
                "cidade" => $cidade,
                "language" => "pt",
                "pais" => strtoupper($pais),
                "posto_autorizado" => $posto_autorizado,
                "posto" => $posto_autorizado,
                "os" => $os,
                "equipamento" => $equipamento,
                "marca" => $marca,
                "produto" => $produto,
                "qual" => $qual,
                "outro_qual" => $qual,
                "recomendacao" => $recomendacao,
                "principal_razao_pontuacao" => $principal_razao_pontuacao,
                "razao_pontuacao" => $principal_razao_pontuacao,
                "expandir_pontuacao" => $expandir_pontuacao,
                "complemento_classificacao" => $expandir_pontuacao,
                "nota_tempo_reparo" => $tempo_reparo,
                "nota_preco_reparo" => $preco_reparo,
                "nota_qualidade_reparo" => $qualidade_reparo,
                "nota_atencao" => $atencao_atendente,
                "nota_explicacao" => $explicacao_reparo,
                "nota_aspecto" => $aspecto_visual_posto_autorizado,
                "nota_geral" => $satisfacao_geral,
                "numero_dias_reparo" => $numero_dias_reparo,
                "numero_dias" => $numero_dias_reparo,
                "mes" => $mes,
                "ano" => $ano,
                "nps_score" => $nps_score
            );

            $resultado = json_encode($resultado);

            /**
             * Infelizmente foi necessário copiar esse insert sem lógica alguma
             * feito por um programador que se achou mais esperto que a análise do
             * chamado que dizia para fazer essa pesquisa de satisfação em um
             * outro conjunto de tabelas onde não seria necessário fazer essa "solução técnica alternativa"!
             */
            file_put_contents("/tmp/teste1.txt", "$i \n", FILE_APPEND);
            $sqlOsTemp = "
                INSERT INTO tbl_os (
                    fabrica,
                    posto,
                    obs,
                    data_abertura,
                    data_digitacao
                ) VALUES (
                    1,
                    6359,
                    'OS aberta para cadastro de pesquisa de satisfação para américa latina',
                    CURRENT_DATE,
                    CURRENT_DATE
                ) RETURNING os
            ";

            $resOsTemp = pg_query($con, $sqlOsTemp);
            $os_email = pg_fetch_result($resOsTemp, 0, os);
            $resultado = addslashes($resultado);
            $sqlGravar = "
              INSERT INTO tbl_laudo_tecnico_os (
                  titulo      ,
                  os          ,
                  observacao  ,
                  data,
                  fabrica
              ) VALUES (
                  'Pesquisa de satisfação - $pais',
                  $os_email                           ,
                  '$resultado'                        ,
                  '".$ano."-".$mes."-".$dia."',
                  1
              )";
            $resLaudo = pg_query($con, $sqlGravar);
            $qtdLinhas += 1;
        }

        if(count($errorMsgs)>0){
            $errorMsg['general'][] = "O arquivo não foi importado por conter linhas com erros";
            $res = pg_query($con, "ROLLBACK;");
        }else{
            $errorMsg['success'][] =  "O Arquivo foi importado com sucesso, total de ".$qtdLinhas." registros processados";
            $res = pg_query($con, "COMMIT;");
        }
    }
}


//================================================================ INICIO HTML ================================================================

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "ajaxform",
    "dataTable"
);

include("plugin_loader.php");

if (count($errorMsg['general']) > 0) {
    ?>
    <div class="alert alert-error">
        <h4><?php echo implode("<br />", $errorMsg['general']) ?></h4>
    </div>
    <?php
}

if (count($errorMsg['success']) > 0) {
    ?>
    <div class="alert alert-success">
        <h4><?php echo implode("<br />", $errorMsg['success']) ?></h4>
    </div>
    <?php
}

?>

<div class="row">
    <b class="obrigatorio pull-right"> * Campos obrigatórios </b>
</div>
<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario' enctype="multipart/form-data">
    <div class='titulo_tabela '>Upload de Pesquisa de Satisfação</div>
    <br/>


    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='produto_referencia'>Arquivo CSV</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="file" id="file-csv" name="file-csv" class='span12' maxlength="20" value="">
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <br/>
    <p><br/>
        <input type="submit" class="btn" name="btn-send" value="Enviar"/>
    </p><br/>

    <?php
    if(count($errorMsgs)>0){
    ?>

    <div class='titulo_tabela ' style="background-color: #ee5f5b">Linhas que não foram gravadas</div>
    <table class="table table-striped table-bordered table-hover table-fixed dataTable">
        <thead>
            <tr class="titulo_tabela">
                <th>Linha</th>
                <th>Erro</th>
            </tr>
        </thead>
        <tbody>
        <?php
        foreach ($errorMsgs as $linha => $value) {
            ?>
            <tr>
                <td><?=$linha+1?></td>
                <td>
                    <ul>
                        <?php
                        foreach ($value as $val) {
                            ?>
                            <li><?=$val?></li>
                            <?php
                        }
                        ?>
                    </ul>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
    <?php
    }
    ?>
</FORM>
</div>
<? include "rodape.php" ?>
