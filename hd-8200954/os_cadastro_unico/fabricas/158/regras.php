<?php

function altera_servico_realizado($os_item, $servico)
{
    global $login_fabrica, $con;

    $sql = "UPDATE tbl_os_item set servico_realizado = {$servico}
            WHERE os_item = {$os_item}";
    $upd = pg_query($con, $sql);

    if (pg_affected_rows($upd) != 1) {
        throw new Exception("Erro ao alterar Serviço Realizado da Peça");
    }
}

function verifica_estoque_peca_imbera(){
    global $con, $login_fabrica, $campos, $os, $gravando;

    $posto = ($areaAdmin === false) ? $login_posto : $campos["posto"]["id"];

    $Os = new \Posvenda\Os($login_fabrica);

    $status_posto_controla_estoque = $Os->postoControlaEstoque($posto, $con);

    $unidade_negocio = $campos["os"]["unidade_negocio"];

    $sql = "
        SELECT servico_realizado, gera_pedido, troca_de_peca, peca_estoque
        FROM tbl_servico_realizado
        WHERE fabrica = {$login_fabrica}
        AND ativo IS TRUE
    ";
    $res = pg_query($con, $sql);

    $servicos = array();

    foreach (pg_fetch_all($res) as $k => $s) {
        $servicos[$s['servico_realizado']] = array(
            'servico_realizado' => $s['servico_realizado'],
            'gera_pedido'       => $s["gera_pedido"],
            'troca_de_peca'     => $s['troca_de_peca'],
            'peca_estoque'      => $s['peca_estoque']
        );

        if ($s["peca_estoque"] == "t") {
            $servicos['estoque'] = array(
                'servico_realizado' => $s['servico_realizado'],
                'gera_pedido'       => $s["gera_pedido"],
                'troca_de_peca'     => $s['troca_de_peca'],
                'peca_estoque'      => $s['peca_estoque']
            );
        }

        if ($s['gera_pedido'] == "t") {
            $servicos['gera_pedido'] = array(
                'servico_realizado' => $s['servico_realizado'],
                'gera_pedido'       => $s["gera_pedido"],
                'troca_de_peca'     => $s['troca_de_peca'],
                'peca_estoque'      => $s['peca_estoque']
            );
        }
    }

    if($status_posto_controla_estoque == true){
        $pecas = $campos["produto_pecas"];

        foreach ($pecas as $k => $peca) {
            $peca_id = $peca["id"];

            if ($k === "__modelo__" || empty($peca_id)) {
                continue;
            }

            $servico        = $servicos[$peca["servico_realizado"]];
            $servico_antigo = $servicos[$peca["servico_realizado_antigo"]];
            $qtde           = (int) $peca["qtde"];
            $qtde_antiga    = (int) $peca["qtde_antiga"];
            $os_item        = $peca["os_item"];

            //Verfica se a Peça já está lançada na OS
            if (empty($os_item)) {
                $os_item = $peca["os_item_insert"];

                //Se o Serviço Realizado for Troca de Peça (Gera Pedido)
                if ($servico["troca_de_peca"] == "t" && $servico["gera_pedido"] == "t") {
                    //Busca saldo da peça no estoque
                    $estoque = $Os->verificaEstoquePosto($posto, $peca_id, $qtde, $con);

                    //Se possuir saldo no estoque altera o serviço realizado para estoque e lança movimentação no estoque
                    if ($estoque) {
                        $servico = $servicos["estoque"];

                        altera_servico_realizado($os_item, $servico["servico_realizado"]);

                        $movimentoEstoque = $Os->lancaMovimentoEstoque($posto, $peca_id, $qtde, $os, $os_item, "", "", "saida", $con,$unidade_negocio);

                        if (!$movimentoEstoque) {
                            throw new Exception("Erro ao lançar movimentação no estoque #1");
                        }
                    } else {
                        continue;
                    }
                //Se o Serviço Realizado for Troca de Peça (Estoque)
                } else if ($servico["troca_de_peca"] == "t" && $servico["peca_estoque"] == "t") {
                    //Busca saldo da peça no estoque
                    $estoque = $Os->verificaEstoquePosto($posto, $peca_id, $qtde, $con);

                    //Se possuir saldo no estoque lança movimentação
                    if ($estoque) {
                        $movimentoEstoque = $Os->lancaMovimentoEstoque($posto, $peca_id, $qtde, $os, $os_item, "", "", "saida", $con, $unidade_negocio);

                        if (!$movimentoEstoque) {
                            throw new Exception("Erro ao lançar movimentação no estoque #2");
                        }
                    //Se não possuir saldo no estoque altera o serviço realizado para gera pedido
                    } else {
                        $servico = $servicos["gera_pedido"];

                        altera_servico_realizado($os_item, $servico["servico_realizado"]);
                    }
                } else {
                    continue;
                }
            } else {
                if ($Os->verificaPedido($os_item) != false) {
                    continue;
                }

                //Se estiver alterando o serviço realizado
                if ($servico["servico_realizado"] != $servico_antigo["servico_realizado"]) {
                    //Se o serviço realizado antigo for estoque, exclui a movimentação
                    if ($servico_antigo["peca_estoque"] == "t") {
                        $movimentacaoExcluida = $Os->excluiMovimentacaoEstoque($posto, $peca_id, $os, $os_item, $con);

                        if (!$movimentacaoExcluida) {
                            throw new Exception("Erro ao excluir movimentação no estoque #4");
                        }
                    }

                    //Se o novo serviço realizado for estoque
                    if ($servico["peca_estoque"] == "t") {
                        //Busca saldo da peça no estoque
                        $estoque = $Os->verificaEstoquePosto($posto, $peca_id, $qtde, $con);

                        //Se possuir saldo no estoque lança movimentação
                        if ($estoque) {
                            $movimentoEstoque = $Os->lancaMovimentoEstoque($posto, $peca_id, $qtde, $os, $os_item, "", "", "saida", $con,$unidade_negocio);

                            if (!$movimentoEstoque) {
                                throw new Exception("Erro ao lançar movimentação no estoque #3");
                            }
                        //Se não possuir saldo no estoque altera o serviço para gera pedido
                        } else {
                            $servico = $servicos["gera_pedido"];

                            altera_servico_realizado($os_item, $servico["servico_realizado"]);
                        }
                    //Se o novo eserviço realizado for gera pedido
                    } else if ($servico["gera_pedido"] == "t") {
                        //Busca saldo da peça no estoque
                        $estoque = $Os->verificaEstoquePosto($posto, $peca_id, $qtde, $con);

                        //Se possuir saldo no estoque altera o serviço realizado para estoque e lança movimentação
                        if ($estoque) {
                            $servico = $servicos["estoque"];

                            altera_servico_realizado($os_item, $servico["servico_realizado"]);

                            $movimentoEstoque = $Os->lancaMovimentoEstoque($posto, $peca_id, $qtde, $os, $os_item, "", "", "saida", $con,$unidade_negocio);

                            if (!$movimentoEstoque) {
                                throw new Exception("Erro ao lançar movimentação no estoque #4");
                            }
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                //Se estiver alterando a quantidade
                } else if ($qtde != $qtde_antiga) {
                    //Se o serviço realizado for estoque
                    if ($servico["peca_estoque"] == "t") {
                        //Se a quantidade antiga for menor que a nova quantidade
                        if ($qtde_antiga < $qtde) {
                            $diferenca = $qtde - $qtde_antiga;

                            //Busca saldo da peça no estoque para a diferença
                            $estoque = $Os->verificaEstoquePosto($posto, $peca_id, $diferenca, $con);

                            //Se possuir saldo no estoque exclui a antiga movimentação e lança uma nova movimentação
                            if ($estoque) {
                                $movimentacaoExcluida = $Os->excluiMovimentacaoEstoque($posto, $peca_id, $os, $os_item, $con);

                                if (!$movimentacaoExcluida) {
                                    throw new Exception("Erro ao excluir movimentação no estoque #1");
                                }

                                $movimentoEstoque = $Os->lancaMovimentoEstoque($posto, $peca_id, $qtde, $os, $os_item, "", "", "saida", $con,$unidade_negocio);

                                if (!$movimentoEstoque) {
                                    throw new Exception("Erro ao lançar movimentação no estoque #5");
                                }
                            //Se não possuir saldo no estque exclui a movimentação antiga e altera o serivço para gera pedido
                            } else {
                                $movimentacaoExcluida = $Os->excluiMovimentacaoEstoque($posto, $peca_id, $os, $os_item, $con);

                                if (!$movimentacaoExcluida) {
                                    throw new Exception("Erro ao excluir movimentação no estoque #2");
                                }

                                $servico = $servicos["gera_pedido"];

                                altera_servico_realizado($os_item, $servico["servico_realizado"]);
                            }
                        //Se a quantidade antiga for maior que a nova quantidade, exclui a movimentação antiga e lança uma nova movimentação
                        } else if ($qtde_antiga > $qtde) {
                            $movimentacaoExcluida = $Os->excluiMovimentacaoEstoque($posto, $peca_id, $os, $os_item, $con);

                            if (!$movimentacaoExcluida) {
                                throw new Exception("Erro ao excluir movimentação no estoque #3");
                            }

                            $movimentoEstoque = $Os->lancaMovimentoEstoque($posto, $peca_id, $qtde, $os, $os_item, "", "", "saida", $con,$unidade_negocio);

                            if (!$movimentoEstoque) {
                                throw new Exception("Erro ao lançar movimentação no estoque #6");
                            }
                        }
                    //Se o serviço realizado for gera pedido
                    } else if ($servico["gera_pedido"] == "t") {
                        //Busca saldo da peça no estoque
                        $estoque = $Os->verificaEstoquePosto($posto, $peca_id, $qtde, $con);

                        //Se possuir saldo no estoque altera o serviço realizado para estoque e lança movimentação
                        if ($estoque) {
                            $servico = $servicos["estoque"];

                            altera_servico_realizado($os_item, $servico["servico_realizado"]);

                            $movimentoEstoque = $Os->lancaMovimentoEstoque($posto, $peca_id, $qtde, $os, $os_item, "", "", "saida", $con,$unidade_negocio);

                            if (!$movimentoEstoque) {
                                throw new Exception("Erro ao lançar movimentação no estoque #7");
                            }
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }
            }
        }
    } else {
        foreach ($campos["produto_pecas"] as $pecas) {
            if (!empty($pecas["id"]) && $Os->verificaServicoUsaEstoque($pecas["servico_realizado"])) {
                $os_item = get_os_item($os, $pecas["id"]);
                altera_servico_realizado($os_item, "gera_pedido");
            }
        }
    }
}

/* Valida se o Defeito Reclamado pertence ao Produto da OS */
function valida_defeito_reclamado_produto(){

    global $con, $login_fabrica, $campos;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    if (!empty($tipo_atendimento)) {
        $sql = "SELECT grupo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento}";
        $res = pg_query($con, $sql);

        $grupo_atendimento = pg_fetch_result($res, 0, "grupo_atendimento");

        if ($grupo_atendimento != "S") {
            $produto           = $campos["produto"]["id"];
            $defeito_reclamado = $campos["os"]["defeito_reclamado"];
            $familia_produto   = $campos["produto"]["familia"];

            if(strlen($produto) > 0 && count(array_filter($defeito_reclamado)) > 0){

                foreach($defeito_reclamado as $defReclamado){

                    $sql = "SELECT tbl_diagnostico.diagnostico
                                FROM tbl_diagnostico
                                INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado AND tbl_defeito_reclamado.fabrica = {$login_fabrica}
                                WHERE tbl_diagnostico.familia = {$familia_produto}
                                AND tbl_diagnostico.defeito_reclamado = {$defReclamado}";
                    $res = pg_query($con, $sql);

                    if(pg_num_rows($res) == 0){
                        throw new Exception("Defeito Reclamado inválido para esse produto");
                    }
                }
            }
        }
    }
}

$regras["os|nota_fiscal"]["obrigatorio"] = false;

$regras["os|defeito_reclamado"]["obrigatorio"] = false; // estou negando aqui pois mudou o campo para multiselect e vou validar no função

$regras["os|data_compra"]["obrigatorio"] = false;
$regras["os|id_tecnico"]["obrigatorio"] = false;
$regras["os|unidade_negocio"]["obrigatorio"] = true;
$regras["os|qtde_km"]["function"] = array("zera_qtde_km_imbera");
$regras["os|data_abertura"]["function"] = array("valida_data_abertura_imbera");
$regras["consumidor|telefone"]["obrigatorio"] = false;
$regras["revenda|nome"]["obrigatorio"]   = false;
$regras["revenda|cnpj"]["obrigatorio"]   = false;
$regras["revenda|cidade"]["obrigatorio"] = false;
$regras["revenda|estado"]["obrigatorio"] = false;
$regras["produto|serie"]["obrigatorio"] = false;
$regras["produto|amperagem"]["obrigatorio"] = true;
$regras["produto|amperagem"]["function"] = array("valida_amperagem_imbera");
$regras["produto|patrimonio"]["function"] = array("valida_patrimonio_imbera");
$regras["produto|defeito_constatado"]["obrigatorio"] = false;
$regras["produto|defeito_constatado"]["function"] = array(
    "valida_familia_defeito_constatado_imbera",
    "valida_defeito_constatado_peca_lancada"
);
$regras["produto|id"]["function"][] = "valida_produto_trocado_imbera";






if (!empty($_REQUEST['os_id']) && $areaAdmin == true) {
	$regras["os|unidade_negocio"]["obrigatorio"] = false;
}

$regras["produto|pdv_chegada"]["obrigatorio"] = false;
$regras["produto|pdv_saida"]["obrigatorio"] = false;

// Admin pediu para tirar, mas pode ser que volte a obrigar.
if (!empty($_REQUEST["solucao_lancada"]) && 1==2) {
    $sqlPdv = "SELECT solucao FROM tbl_solucao WHERE fabrica = {$login_fabrica} AND solucao IN (".$_REQUEST["solucao_lancada"].") AND parametros_adicionais->>'programacao_pdv' = 'true' ";
    $resPdv = pg_query($con, $sqlPdv);
    if (pg_num_rows($resPdv) > 0) {
        $regras["produto|pdv_chegada"]["obrigatorio"] = true;
        $regras["produto|pdv_saida"]["obrigatorio"] = true;
    }
}

$valida_anexo_boxuploader = "valida_anexo_boxuploader";
$anexos_obrigatorios = [];

function valida_familia_defeito_constatado_imbera() {
    global $con, $login_fabrica, $campos, $defeitoConstatadoMultiplo;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    if (!empty($tipo_atendimento)) {
        $sql = "SELECT grupo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento}";
        $res = pg_query($con, $sql);

        $grupo_atendimento = pg_fetch_result($res, 0, "grupo_atendimento");

        if ($grupo_atendimento != "S") {
            $produto              = $campos["produto"]["id"];
            $defeitos_constatados = array();

            if (isset($defeitoConstatadoMultiplo)) {
                $defeitos_constatados = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);
            } else {
                $defeitos_constatados = array($campos["produto"]["defeito_constatado"]);
            }

            if (!empty($produto) && count($defeitos_constatados) > 0) {
                foreach($defeitos_constatados as $defeito_constatado) {
                    if(strlen($defeito_constatado)>0){
                        $sql = "SELECT *
                                FROM tbl_diagnostico
                                INNER JOIN tbl_familia ON tbl_familia.fabrica = {$login_fabrica} AND tbl_familia.familia = tbl_diagnostico.familia
                                INNER JOIN tbl_produto ON tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.familia = tbl_familia.familia
                                WHERE tbl_diagnostico.fabrica = {$login_fabrica}
                                AND tbl_diagnostico.defeito_constatado = {$defeito_constatado}
                                AND tbl_produto.produto = {$produto}";
                        $res = pg_query($con, $sql);

                        if (!pg_num_rows($res)) {
                            throw new Exception("Defeito constatado não pertence a famí­lia do produto");
                        }
                    }
                }
            }
        }
    }
}

function grava_os_fabrica(){

    global $campos;

    $tecnico = $campos["os"]["id_tecnico"];
    $defeito_reclamado = $campos['os']['defeito_reclamado'][0];


    if(strlen(trim($defeito_reclamado))==0){
         $msg_erro["msg"][] = "Informe o defeito reclamado";
            $msg_erro["campos"][] = "os[defeito_reclamado]";
             throw new Exception("Informe o defeito reclamado");
    }

    if (empty($tecnico)) {
        $tecnico = "null";
    }

    if (empty($defeito_reclamado)) {
        $defeito_reclamado = "null";
    }

    return array(
        "tecnico" 	=> "{$tecnico}",
        "defeito_reclamado" => "{$defeito_reclamado}"
    );

}

function zera_qtde_km_imbera() {
    global $campos, $con, $os, $login_fabrica;

    $qtde_km = $campos["os"]["qtde_km"];

	if(!empty($os)) {

        $sql = "SELECT *                      
                FROM tbl_hd_chamado_cockpit hcc
                JOIN tbl_os AS o ON o.hd_chamado = hcc.hd_chamado 
                AND o.fabrica = {$login_fabrica}
                JOIN tbl_posto_fabrica ON o.posto = tbl_posto_fabrica.posto 
                       AND tbl_posto_fabrica.fabrica = o.fabrica 
                       AND coalesce(tbl_posto_fabrica.parametros_adicionais::json->>'zera_km','t') <> 'f'
                WHERE hcc.fabrica = {$login_fabrica}
                AND o.os = {$os}";

        $res_kof = pg_query($con, $sql);


        $unidadeQuery = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = {$os}";
        
        $unidadeNegocio = pg_query($con, $unidadeQuery);
        $unidadeNegocio = pg_fetch_object($unidadeNegocio);
        $unidadeNegocio = json_decode($unidadeNegocio->campos_adicionais);
        $unidadeNegocio = $unidadeNegocio->unidadeNegocio;

        if (in_array($unidadeNegocio, [6900, 7000]) && $qtde_km >= 60) { 

                $queryAuditar = "INSERT INTO tbl_auditoria_os (os, auditoria_status, data_input, observacao)
                             VALUES ({$os}, 2, CURRENT_DATE, 'OS em auditoria de KM')";

                $resAuditar = pg_query($con, $queryAuditar);

        } else {

            if ($qtde_km < 60 || pg_num_rows($res_kof) > 0) {
                
                $qtde_km = 0;
            }
        }
	}

    $campos["os"]["qtde_km"] = $qtde_km;



}

function grava_os_extra_fabrica() {
    global $campos;

    $data_fabricacao = DateTime::createFromFormat('d/m/Y',$campos['os']['data_fabricacao']);
    $data_fabricacao = date_format($data_fabricacao, 'Y-m-d');

    $patrimonio = $campos["produto"]["patrimonio"];
    $amperagem = $campos["produto"]["amperagem"];
    if (empty($amperagem)) {
        $amperagem = 0;
    }

    return array(
        "serie_justificativa" => "'{$patrimonio}'",
        "regulagem_peso_padrao" => "{$amperagem}",
        "data_fabricacao" => ((!empty($data_fabricacao)) ? "'{$data_fabricacao}'" : "null")
    );
}

function grava_os_campo_extra_fabrica() {
    global $campos;

    $unidade_negocio = $campos["os"]["unidade_negocio"];
    $marca = $campos["os"]["marca"];

//    if($unidade_negocio == '7300' and strlen(trim($marca))==0){
  //      throw new Exception("O campo marca para essa unidade de negócio é obrigatório");
    //}

    $return = array();

    if(strlen(trim($marca))>0){
        $return["marca"] = $marca;
    }
    
    if(strlen($unidade_negocio) > 0){
        $return["unidadeNegocio"] = $unidade_negocio;        
    }

    if (!empty(trim($campos["produto"]["pdv_chegada"]))) {
        $return["pdv_chegada"] = trim($campos["produto"]["pdv_chegada"]);
    } else {
        $return["pdv_chegada"] = '';
    }

    if (!empty(trim($campos["produto"]["pdv_saida"]))) {
        $return["pdv_saida"] = trim($campos["produto"]["pdv_saida"]);
    } else {
        $return["pdv_saida"] = '';
    }

    return $return;
}

function valida_amperagem_imbera(){
    global $campos;

    $amperagem = $campos["produto"]["amperagem"];

    if ($amperagem > 40.00) {
        throw new Exception("A amperagem não deve ser maior que 40.00");
    }
}

/**
 * Função para validação de data de abertura
 */
function valida_data_abertura_imbera() {
    global $campos, $os;

    $data_abertura = $campos["os"]["data_abertura"];

    if (!empty($data_abertura) && empty($os)) {
        list($dia, $mes, $ano) = explode("/", $data_abertura);

        if (!checkdate($mes, $dia, $ano)) {
            throw new Exception("Data de abertura inválida");
        } else if (strtotime("{$ano}-{$mes}-{$dia}") < strtotime("today - 60 days")) {
            throw new Exception("Data de abertura não pode ser anterior a 60 dias");
        }
    }
}

function valida_patrimonio_imbera(){
    global $con, $login_fabrica, $campos;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
    $patrimonio = $campos["produto"]["patrimonio"];


    $sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND fora_garantia IS TRUE AND tipo_atendimento = {$tipo_atendimento}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0 && strlen($patrimonio) == 0) {
        throw new Exception("O campo patrimônio não pode ser vazio para esse tipo de atendimento");
    }
}

function valida_anexo_imbera() {
    global $campos, $msg_erro, $con, $login_fabrica, $anexos_obrigatorios;
       
        // Valida se OS de Posto Terceiro Obriga adição de Anexo
        $posto = $campos['posto']['id'];

        $sql = "SELECT * FROM tbl_posto_fabrica JOIN tbl_tipo_posto USING(tipo_posto,fabrica) WHERE posto = {$posto} AND posto_interno IS NOT TRUE AND tecnico_proprio IS NOT TRUE AND fabrica = {$login_fabrica};";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
           $anexos_obrigatorios = ["notafiscal"];
        }
    
}

$valida_anexo = "valida_anexo_imbera";

function auditoria_km_imbera() {
    global $con, $login_fabrica, $os, $campos;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
    $posto = $campos["posto"]["id"];

    $sql = "
        SELECT tipo_atendimento
        FROM tbl_tipo_atendimento
        WHERE fabrica = {$login_fabrica}
        AND km_google IS TRUE
        AND fora_garantia IS NOT TRUE
        AND tipo_atendimento = {$tipo_atendimento};
    ";
    $res_garantia = pg_query($con, $sql);

    $sql = "
        SELECT *
        FROM tbl_posto_fabrica pf
        JOIN tbl_tipo_posto tp USING(tipo_posto,fabrica)
        WHERE pf.fabrica = {$login_fabrica}
        AND pf.posto = {$posto}
        AND tp.posto_interno IS NOT TRUE
        AND tp.tecnico_proprio IS NOT TRUE;
    ";

    $res_terceiro = pg_query($con, $sql);

	if(!empty($os)) {
		$sql = "
			SELECT
			*
			FROM tbl_hd_chamado_cockpit hcc
			JOIN tbl_os o ON o.hd_chamado = hcc.hd_chamado AND o.fabrica = {$login_fabrica}
			WHERE hcc.fabrica = {$login_fabrica}
			AND o.os = {$os};
		";

		$res_kof = pg_query($con, $sql);
	}
    /*
     * Implantação Imbera
     * Regras Auditoria: Acima de 60 KM, OS em garantia e não ser de Origem KOF
     */
    if (((pg_num_rows($res_garantia) > 0 && pg_num_rows($res_terceiro) > 0) || pg_num_rows($res_kof) > 0) && $campos["os"]["qtde_km"] > 60) {

        if ($campos["os"]["qtde_km"] != $campos["os"]["qtde_km_hidden"]) {
            $complemento_obs = ', KM alterado manualmente';
        }

        if (verifica_auditoria_unica("tbl_auditoria_status.km = 't'", $os) === true) {
            $busca = buscaAuditoria("tbl_auditoria_status.km = 't'");

            if($busca['resultado']){
                $auditoria_status = $busca['auditoria'];
            }

            $sql = "INSERT INTO tbl_auditoria_os 
                    (os, auditoria_status, observacao, bloqueio_pedido) 
                    VALUES 
                    ({$os}, $auditoria_status, 'OS em auditoria de KM{$complemento_obs}', false)";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço");
            }
        }
    }

}

function auditoria_valor_adicional_imbera(){
    global $con, $campos, $os, $login_fabrica;
    
    if(!empty($os) && count($campos["os"]["valor_adicional"]) > 0){

        $verifica_auditoria_unica = verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao = 'OS em Auditoria de Valores Adicionais'", $os);

        $sql = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = {$os}";
        $res = pg_query($con,$sql);

        $campos["auditoria"]["valor_adicional_valor_antes"] = pg_fetch_result($res, 0, "valores_adicionais");
        
        $valores_anteriores = json_decode($campos["auditoria"]["valor_adicional_valor_antes"], true);

		$tem_valor = false;
        foreach ($campos["os"]["valor_adicional"] as $key => $val) {

            $arrValores = explode("|",$val);

            $arrValoresNovo[$arrValores[0]] = $arrValores[1];

			if(floatval($arrValores[1]) > 0) {
				$tem_valor = true;
			}

        }

        foreach ($valores_anteriores as $chave => $valor) {

            foreach ($valor as $descricao => $valor2) {

               $arrValoresAntigo[$descricao] = $valor2;
				if(floatval($valor2) > 0) {
					$tem_valor = true;
				}
            }

        }           

        $diferencasArray = array_diff($arrValoresNovo, $arrValoresAntigo);

        $verifica_auditoria_pendente = verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao = 'OS em Auditoria de Valores Adicionais' AND liberada IS NULL AND reprovada IS NULL", $os);

        $valores_adicionais_alterados = false;
        if (count($diferencasArray) > 0 && $verifica_auditoria_pendente) {

            $valores_adicionais_alterados = true;

        }

        if(($verifica_auditoria_unica === true || $valores_adicionais_alterados === true) and $tem_valor){
            $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

            if($busca["resultado"]){
                $auditoria = $busca["auditoria"];

                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) VALUES ($os, $auditoria, 'OS em Auditoria de Valores Adicionais', false)";
                pg_query($con,$sql);

                if(strlen(pg_last_error()) > 0){
                    throw new Exception("Erro ao criar auditoria de valores adicionais para a OS");
                }else{
                    return true;
                }
            }else{
                throw new Exception("Erro ao buscar auditoria valores adicionais");
            }
        }
    }
}

function auditoria_os_reincidente_imbera() {
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
    $defeitos         = $campos["produto"]["defeitos_constatados_multiplos"];
    $patrimonio       = $campos["produto"]["patrimonio"];
    $serie            = $campos["produto"]["serie"];
    $hd_chamado       = $campos["os"]["hd_chamado"];
    $posto            = $campos["posto"]["id"];

    $sql = "
        SELECT os 
        FROM tbl_os 
        WHERE fabrica = {$login_fabrica} 
        AND os = {$os} 
        AND os_reincidente IS NOT TRUE
    ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0 
        && !empty($tipo_atendimento) 
        && !empty($hd_chamado) 
        && !empty($defeitos) 
        && (!empty($patrimonio) || !empty($serie)) 
        && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true
    ) {
	    $sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento AND codigo = '90'";
	    $res = pg_query($con,$sql);

	    if(pg_num_rows($res) == 0){
		$sql = "
		    SELECT tbl_cliente_admin.codigo 
		    FROM tbl_hd_chamado
		    INNER JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin AND tbl_cliente_admin.fabrica = {$login_fabrica}
		    WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
		    AND tbl_hd_chamado.hd_chamado = {$hd_chamado}
		";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
		    throw new \Exception("Erro ao verificar o cliente admin");
		}

		$codigo_cliente_admin = pg_fetch_result($res, 0, "codigo");

		if (in_array(strtoupper($codigo_cliente_admin), array("158-ALPUNTO", "158-KOF"))) {
		    $whereSeriePatrimonio = "
			AND tbl_os_extra.serie_justificativa = '{$patrimonio}'
		    ";
		    $msg = "Patrimônio e Defeito Constatado";
		} else {
		    $whereSeriePatrimonio = "
			AND tbl_os_produto.serie = '{$serie}'
		    ";
		    $msg = "Número de Série e Defeito Constatado";
		}

		$sql = "
		    SELECT fora_garantia 
		    FROM tbl_tipo_atendimento 
		    WHERE fabrica = {$login_fabrica} 
		    AND tipo_atendimento = {$tipo_atendimento}
		";
		$res = pg_query($con, $sql);

		$tipo_atendimento_fora_garantia = pg_fetch_result($res, 0, "fora_garantia");

		if (in_array(strtoupper($codigo_cliente_admin), array("158-ALPUNTO", "158-KOF"))) {
			$campoData = " tbl_os.finalizada::date";
		}else{
			$campoData = " tbl_os.data_abertura";
		}

		if ($tipo_atendimento_fora_garantia == "t") {
		    $whereDias = "
			AND $campoData > (CURRENT_DATE - INTERVAL '30 days')
		    ";
		} else {
		    $whereDias = "
			AND $campoData > (CURRENT_DATE - INTERVAL '90 days')
		    ";
		}

		$sql = "SELECT tbl_os.os
			FROM tbl_os
			INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
			WHERE tbl_os.fabrica = {$login_fabrica}
			{$whereDias}
			{$whereSeriePatrimonio}
			AND ARRAY[{$defeitos}] && ARRAY(
			    SELECT defeito_constatado 
			    FROM tbl_os_defeito_reclamado_constatado 
			    WHERE tbl_os_defeito_reclamado_constatado.os = tbl_os.os
			)
			AND tbl_os.excluida IS NOT TRUE
			AND tbl_os.posto = $posto
			AND tbl_os.os < {$os}
			ORDER BY tbl_os.data_abertura DESC
			LIMIT 1";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
		    $os_reincidente_numero = pg_fetch_result($res, 0, "os");

		    $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

		    if($busca['resultado']){
			$auditoria_status = $busca['auditoria'];
		    }

		    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
			    ({$os}, $auditoria_status, 'OS Reincidente por {$msg}')";
		    $res = pg_query($con, $sql);

		    if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao gravar Ordem de Serviço");
		    } else {
			$os_reincidente = true;
		    }
		}
	    }
    }
}

$auditorias = array(
    "auditoria_peca_critica",
    "auditoria_pecas_excedentes",
    "auditoria_km_imbera",
    "auditoria_valor_adicional_imbera",
    "auditoria_os_reincidente_imbera"
);

unset($valida_garantia);

$funcoes_fabrica = array("verifica_estoque_peca_imbera", "grava_solucao_imbera", "grava_defeito_reclamado_excedente", "valida_numero_serie_bloqueado_imbera");

$antes_valida_campos = "tipo_atendimento_piso";

function tipo_atendimento_piso() {
    global $campos, $regras;

    $postoInterno = verifica_tipo_posto("posto_interno", "TRUE", $campos["posto"]["id"]);

    if ($postoInterno == true) {
        $regras["produto|serie"]["obrigatorio"] = true;
        //$regras["produto|serie"]["function"]    = array("valida_numero_serie_posto_intenro_imbera");
    }

    if (!empty($campos["os"]["tipo_atendimento"])) {
        $tipo_atendimento = descricao_tipo_atendimento($campos["os"]["tipo_atendimento"]);

        if (preg_match("/Piso/i", $tipo_atendimento)) {
            $regras["produto|amperagem"]["obrigatorio"] = false;
            $regras["consumidor|nome"]["obrigatorio"]   = false;
            $regras["consumidor|cidade"]["obrigatorio"] = false;
            $regras["consumidor|estado"]["obrigatorio"] = false;
        }
    }
}

/*function valida_numero_serie_posto_intenro_imbera() {
    global $con, $campos, $login_fabrica, $msg_erro;

    $serie   = $campos["produto"]["serie"];
    $produto = $campos["produto"]["id"];

    if (!empty($serie) && !empty($produto)) {
        $sql = "
            SELECT numero_serie FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND produto = {$produto} AND serie = '{$serie}'
        ";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][] = "Número de Série inválido";
            $msg_erro["campos"][] = "produto[serie]";
        }
    }
}*/

function valida_numero_serie_bloqueado_imbera() {
    global $con, $campos, $login_fabrica, $msg_erro;

    $serie   = $campos["produto"]["serie"];

    if (!empty($serie)) {
        $sql = "SELECT ns.serie
                FROM tbl_numero_serie AS ns 
                    INNER JOIN tbl_serie_controle AS sc ON sc.serie = ns.serie AND sc.fabrica = {$login_fabrica} 
                WHERE   ns.fabrica = {$login_fabrica} 
                    AND ns.serie   = '{$serie}';";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            throw new Exception("Número de série do produto está bloqueado, favor entrar em contato com o fabricante.");
        }
    }
}

function grava_solucao_imbera() {
    global $con, $campos, $os, $login_fabrica, $funcoes_fabrica;

    $solucoes = $campos["produto"]["solucao"];

    $sql = "DELETE FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND solucao IS NOT NULL";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro ao gravar soluções");
    }

    foreach ($solucoes as $solucao) {
        $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado (os, solucao, fabrica) VALUES ({$os}, {$solucao}, {$login_fabrica})";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao gravar soluções");
        }
    }
}

function grava_defeito_reclamado_excedente() {
    global $con, $campos, $os, $login_fabrica, $funcoes_fabrica;

    $defeito_reclamado_excedente = $campos["os"]["defeito_reclamado"];

    unset($defeito_reclamado_excedente[0]);

    $sql = "DELETE FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND defeito_reclamado IS NOT NULL";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro ao gravar soluções");
    }

    foreach ($defeito_reclamado_excedente as $def_rec) {
        $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado (os, defeito_reclamado, fabrica) VALUES ({$os}, {$def_rec}, {$login_fabrica})";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao gravar defeito reclamado excedente");
        }
    }
}


$pre_funcoes_fabrica = array("auditoria_tecnico_proprio_os_fora_garantia", "valida_defeito_reclamado_produto", "busca_servico_realizado_antigo_pecas", "admin_km_imbera");

function admin_km_imbera() {
    global $campos, $areaAdmin, $nova_os;

    if ($areaAdmin === true AND $nova_os === true) {
        $campos['os']['qtde_km'] = "null";
    }
}

function auditoria_tecnico_proprio_os_fora_garantia() {
    global $con, $campos, $login_fabrica, $auditorias;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
    $posto            = $campos["posto"]["id"];

    if (!empty($posto) && !empty($tipo_atendimento)) {
        $sql = "
            SELECT tbl_posto_fabrica.posto
            FROM tbl_posto_fabrica
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
            WHERE tbl_posto_fabrica.posto = {$posto}
            AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            AND tbl_tipo_posto.tecnico_proprio IS TRUE
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Execption("Erro ao verificar tipo do posto");
        }

        if (pg_num_rows($res) > 0) {
            $sql = "
                SELECT fora_garantia
                FROM tbl_tipo_atendimento
                WHERE fabrica = {$login_fabrica}
                AND tipo_atendimento = {$tipo_atendimento}
                AND fora_garantia IS TRUE
            ";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Execption("Erro ao verificar tipo de atendimento");
            }

            if (pg_num_rows($res) > 0) {
                $auditorias = array();
            }
        }
    }
}

$produto_os_mobile = null;

function valida_produto_trocado_imbera() {
    global $con, $login_fabrica, $os, $campos, $produto_os_mobile, $funcoes_fabrica;

    $produto_lancado = $campos["produto"]["id"];
    $os_mobile       = $campos["os"]["os_numero"];

    if (!empty($produto_lancado) && !empty($os_mobile)) {
        $sql = "
            SELECT p.produto, p.referencia 
            FROM tbl_os_produto osp 
            INNER JOIN tbl_produto p ON p.produto = osp.produto AND p.fabrica_i = {$login_fabrica}
            WHERE osp.os = {$os}
        ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $produto_os = pg_fetch_result($res, 0, "produto");
        }

        if ($produto_lancado != $produto_os) {
            $cockpit = new \Posvenda\Cockpit($login_fabrica);

            $pecas = $cockpit->getPecasOsMobile($os_mobile);

            if (count($pecas) > 0) {
                throw new Exception("Erro ao alterar produto, já existe peças lançadas no dispositivo móvel");
            } else {
                $produto_os_mobile = pg_fetch_result($res, 0, "referencia");
                $funcoes_fabrica[] = "altera_produto_os_mobile_imbera";
            }
        }
    }
}

function altera_produto_os_mobile_imbera() {
    global $con, $login_fabrica, $os, $campos, $produto_os_mobile, $login_admin;

    $produto_lancado = $campos["produto"]["referencia"];
    $serie           = $campos["produto"]["serie"];
    $patrimonio      = $campos["produto"]["patrimonio"];

    $sql = "
        INSERT INTO tbl_os_interacao
        (fabrica, os, admin, comentario)
        VALUES
        ({$login_fabrica}, {$os}, {$login_admin}, 'Produto alterado de {$produto_os_mobile} para {$produto_lancado}')
    ";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro ao trocar o produto");
    }

    $cockpit = new \Posvenda\Cockpit($login_fabrica);

    $cockpit->gravaProdutoOsMobile($os, $produto_lancado, $serie, $patrimonio);
    $cockpit->inativaProdutoOsMobile($os, $produto_os_mobile);
}

function busca_servico_realizado_antigo_pecas() {
    global $campos, $con;

    $pecas = $campos["produto_pecas"];

    foreach ($pecas as $i => $peca) {
        $os_item = $peca["os_item"];

        if (empty($os_item)) {
            $campos["produto_pecas"][$i]["qtde_antiga"]              = null;
            $campos["produto_pecas"][$i]["servico_realizado_antigo"] = null;
        } else {
            $sql = "SELECT qtde, servico_realizado FROM tbl_os_item WHERE os_item = {$os_item}";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0 || !pg_num_rows($res)) {
                throw new Exception("Erro ao buscar informações das peças já lançadas");
            }

            $peca = pg_fetch_assoc($res);

            $campos["produto_pecas"][$i]["qtde_antiga"]              = $peca["qtde"];
            $campos["produto_pecas"][$i]["servico_realizado_antigo"] = $peca["servico_realizado"];
        }
    }
}

$regras_pecas["peca_subitem"] = true;
