<?php
if ($areaAdmin == true && empty($_POST['gravar']))
{
    $regras = array(
            "os|defeito_reclamado" => array(
                "obrigatorio" => true
            ),
            "consumidor|nome" => array(
                "obrigatorio" => true
            ),
            "consumidor|celular" => array(
            "obrigatorio" => true
            ),
            "consumidor|email" => array(
                "obrigatorio" => true
            )
        );
} 
else 
{
	$valida_anexo_boxuploader = "valida_anexo_boxuploader";
    atribuir_obrigatorio();
}

/*
 * - Configurações de
 * processos e auditorias
 */
$valida_garantia = "";
$valida_anexo = "valida_anexo_tecvoz";
$data_abertura_fixa = true;

$funcoes_fabrica = array(
    "verifica_estoque_peca_tecvoz",
    "valida_os_reincidente",
    "auditoria_troca_produto_tecvoz",
    "auditoria_troca_obrigatoria"
);

$auditorias = array(
    "auditoria_peca_critica",
    "auditoria_pecas_excedentes",
    "auditoria_km_tecvoz",
    "auditoria_numero_serie_tecvoz",
    "auditoria_troca_placa_tecvoz",
    "auditoria_troca_tecvoz"
);

/**
 * - grava_os_fabrica()
 * Gravações de campos únicos
 * para fábrica
 */


function grava_os_fabrica(){
    global $campos;

    $justificativa_adicionais = ($campos["produto"]["troca_produto"] == "t") ? array("troca_produto" => "t") : array("troca_produto" => "f");
	$justificativa_adicionais = json_encode($justificativa_adicionais);
	$tecnico = (!empty($campos["os"]["id_tecnico"]))  ? $campos["os"]["id_tecnico"] : "null";

    return array(
        "justificativa_adicionais" => "'{$justificativa_adicionais}'",
        "tecnico"                    => "{$tecnico}",
    );

}

/**
 * - verificaServicoRealizado()
 * Verifica Valores do serviço realizado
 * empregado às peças da Ordem de Serviço

 * @param servico_realizado Id do servico específico
 * @param parametros Escolha qual Flag de servico buscar
 */
function verificaServicoRealizado($servico_realizado = null,$parametros = null)
{
    global $con, $login_fabrica;

    $especifico = "";

    if (!empty($servico_realizado)) {
        $especifico .= " AND tbl_servico_realizado.servico_realizado = $servico_realizado";
    }

    if (!empty($parametros)) {
        switch($parametros) {
            case "pedido":
                $especifico .= "AND tbl_servico_realizado.gera_pedido IS TRUE";
                break;
            case "estoque":
                $especifico .= "AND tbl_servico_realizado.peca_estoque IS TRUE";
                break;
        }
    }

    $sql = "
	SELECT
	    tbl_servico_realizado.servico_realizado,
            tbl_servico_realizado.descricao,
            tbl_servico_realizado.gera_pedido,
            tbl_servico_realizado.peca_estoque,
            tbl_servico_realizado.solucao
        FROM tbl_servico_realizado
        WHERE tbl_servico_realizado.fabrica = {$login_fabrica}
        AND tbl_servico_realizado.troca_produto IS NOT TRUE
	{$especifico}
    ";
    
    $res = pg_query($con,$sql);

    while ($result = pg_fetch_object($res)) {
        $servicos[] = $result;
    }

    return $servicos;
}


/**
 * - verifica_estoque_peca_tecvoz()
 * Verifica a configuração de peca_estoque
 * do posto e faz as validações:
 *
 * ## Retira do peca_estoque
 * ## Troca o serviço e Gera o pedido
 */
function verifica_estoque_peca_tecvoz()
{
    global $con,$login_fabrica, $campos, $os;

    $posto = ($areaAdmin === false) ? $login_posto : $campos["posto"]["id"];

    $Os = new \Posvenda\Os($login_fabrica);

    $status_posto_controla_estoque = $Os->postoControlaEstoque($posto);

    if($status_posto_controla_estoque == true){

        $pecas_pedido = $campos["produto_pecas"];
        $nota_fiscal  = $campos["os"]["nota_fiscal"];
        $data_nf      = $campos["os"]["data_compra"];

        if(!empty($data_nf)){
            list($dia, $mes, $ano) = explode("/", $data_nf);
            $data_nf = $ano."-".$mes."-".$dia;
        }

        foreach ($pecas_pedido as $i => $pecas) {

            if(!empty($pecas["id"])){

                $servico         = $pecas["servico_realizado"];
                $peca            = $pecas["id"];
                $peca_referencia = $pecas["referencia"];
                $qtde            = $pecas["qtde"];
                $os_item         = get_os_item($os, $peca);

                $status_servico = $Os->verificaServicoUsaEstoque($servico);
                if($status_servico == true){

                    $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde);
                    if($status_estoque == false ){

                        /*
                         * - Mudando o serviço da peça para
                         * o correspondente à geração de pedido
                         */

                        $todos = verificaServicoRealizado(null,"pedido");
                        $atual = verificaServicoRealizado($servico);
                        $verificaSolucao = true;

                        $descricaoServicoAtual = $atual[0]->descricao;
                        if (strpos($descricaoServicoAtual,"Placa")) {
                            $verificaSolucao = false;
                        }

                        foreach ($todos as $servicos) {
                            if ($servicos->solucao == $verificaSolucao) {

                                $novoServico = $servicos->servico_realizado;

                                $sqlNovoServico = "
                                    UPDATE  tbl_os_item
                                    SET     servico_realizado = $novoServico
                                    WHERE   os_item = $os_item
                                ";
                                $resNovoServico = pg_query($con,$sqlNovoServico);
                            }
                        }
                        echo "Antigo: $servico<br>Novo:$novoServico<br>---";

                    } else {

                        $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida");

                    }

                } else if ($gravando != true) {

                    $status_exclusao = $Os->excluiMovimentacaoEstoque($posto, $peca, $os, $os_item);

                }

            }

        }
    }
}

/**
 * Posto Interno valida informação do técnico
 */
function valida_tecnico () {

    global $con,$campos,$login_fabrica,$login_posto,$msg_erro,$regras;

    if (verifica_tipo_posto("posto_interno","TRUE",$login_posto)) {

        $regras['os|id_tecnico'] = array(
            "obrigatorio" => true
        );

    }

}


/* Posto Externo obriga informação do celular*/
 
function obrigatoriedade_celular () {

    global $con,$campos,$login_fabrica,$login_posto,$msg_erro,$regras;

    if (verifica_tipo_posto("posto_interno","NOT TRUE",$login_posto)) {

        $regras['consumidor|celular'] = array(
            "obrigatorio" => true
        );

        $msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "consumidor[celular]";
    }

}


/* Posto Externo obriga informação do email */
 
function valida_email () {

    global $con,$campos,$login_fabrica,$login_posto,$msg_erro,$regras;

    if (verifica_tipo_posto("posto_interno","NOT TRUE",$login_posto)) {

        $regras['consumidor|email'] = array(
            "obrigatorio" => true
        );

        $msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "consumidor[email]";
    }

}

    
$antes_valida_campos = 'validar_posto_externo';
/*Chamado 3572461 - Alterado os campos obrigatórios para os postos externos.*/
function validar_posto_externo ()
{
    global $con,$campos,$login_fabrica,$msg_erro,$regras;

    $login_posto = $campos['posto']['id'];
    
    if(empty($login_posto)){return false;}

    if(verifica_tipo_posto("posto_interno", "NOT TRUE", $login_posto))
    {
        if($campos['os']['consumidor_revenda'] == 'C') /*Caso seja Consumidor*/
        {
            /*Retirando a obrigatoriedade dos campos da revenda*/
            $regras['revenda|nome']['obrigatorio'] = false;
            $regras['revenda|cnpj']['obrigatorio'] = false;
            $regras['revenda|telefone']["obrigatorio"] = false;
            $regras['revenda|cep']["obrigatorio"] = false;
            $regras['revenda|endereco']["obrigatorio"] = false;
            $regras['revenda|numero']["obrigatorio"] = false;
            $regras['revenda|bairro']["obrigatorio"] = false;
            $regras['revenda|estado']["obrigatorio"] = false;
            $regras['revenda|cidade']["obrigatorio"] = false;

            /*Adicionando a obrigatoriedade dos dados do consumidor*/
            $regras['consumidor|nome']["obrigatorio"] = true;
            $regras['consumidor|cpf']["obrigatorio"] = true;
            $regras['consumidor|cep']["obrigatorio"] = true;
            $regras['consumidor|endereco']["obrigatorio"] = true;
            $regras['consumidor|numero']["obrigatorio"] = true;
            $regras['consumidor|bairro']["obrigatorio"] = true;
            $regras['consumidor|cidade']["obrigatorio"] = true;
            $regras['consumidor|estado']["obrigatorio"] = true;
            $regras['consumidor|celular']["obrigatorio"] = true;
            $regras['consumidor|email']["obrigatorio"] = true;

        }
        else if ($campos['os']['consumidor_revenda']== 'R') /*Caso seja Revenda*/
        {
            /*Retirando a obrigatoriedade dos campos do consumidor*/
            $regras['consumidor|nome']['obrigatorio'] = false;
            $regras['consumidor|cpf']['obrigatorio'] = false;
            $regras['consumidor|cep']['obrigatorio'] = false;
            $regras['consumidor|endereco']['obrigatorio'] = false;
            $regras['consumidor|numero']['obrigatorio'] = false;
            $regras['consumidor|bairro']['obrigatorio'] = false;
            $regras['consumidor|cidade']['obrigatorio'] = false;
            $regras['consumidor|estado']['obrigatorio'] = false;
            $regras['consumidor|celular']['obrigatorio'] = false;
            $regras['consumidor|email']['obrigatorio'] = false;
            
            /*Adicionando a obrigatoriedade dos dados da revenda*/
            $regras['revenda|nome']['obrigatorio'] = true;
            $regras['revenda|cnpj']['obrigatorio'] = true;
            $regras['revenda|telefone']['obrigatorio'] = true;
            $regras['revenda|cep']['obrigatorio'] = true;
            $regras['revenda|endereco']['obrigatorio'] = true;
            $regras['revenda|numero']['obrigatorio'] = true;
            $regras['revenda|bairro']['obrigatorio'] = true;
            $regras['revenda|estado']['obrigatorio'] = true;
            $regras['revenda|cidade']['obrigatorio'] = true;
        }
    }
}

function atribuir_obrigatorio()
{
    global $con,$campos,$login_fabrica,$msg_erro,$regras, $valida_anexo_boxuploader, $login_posto;

    $login_posto = !empty($login_posto) ? $login_posto :  $campos['posto']['id'];

    $regras = array(
        "posto|id" => array(
            "obrigatorio" => true
        ),
        "os|hd_chamado" => array(
            "function" => array("valida_atendimento")
        ),
        "os|data_abertura" => array(
            "obrigatorio" => true,
            "regex"       => "date",
            "function"    => array("valida_data_abertura")
        ),
        "os|data_compra" => array(
            "obrigatorio" => false,
            "regex"       => "date",
            "function"    => array("valida_data_compra")
        ),
        "os|tipo_atendimento" => array(
            "obrigatorio" => true
        ),
        "os|qtde_km" => array(
            "function" => array("valida_deslocamento")
        ),
        "os|nota_fiscal" => array(
            "obrigatorio" => false
        ),
        "produto|id" => array(
            "obrigatorio" => true,
            "function"    => array("valida_posto_atende_produto_linha")
        ),
        "produto|defeito_constatado" => array(
            "function" => array("valida_familia_defeito_constatado", "valida_defeito_constatado_peca_lancada", "obriga_peca_defeito_contastado")
        )
    );

    if(empty($login_posto)){return false;}

    if(verifica_tipo_posto("posto_interno", "NOT TRUE", $login_posto)) /*Posto Externo*/
    {
        
        $regras = array_merge($regras,array(
           "os|id_tecnico" => array(
                "function" => array("valida_tecnico")
            ),
            "os|defeito_reclamado" => array(
                "obrigatorio" => true
            ),
            "consumidor|nome" => array(
                "obrigatorio" => true
            ),
            "consumidor|cpf" => array(
                "obrigatorio" => true,
                "function" => array("valida_consumidor_cpf")
            ),
            "consumidor|cep" => array(
                "obrigatorio" => true,
                "regex" => "cep"
            ),
            "consumidor|endereco" => array(
                "obrigatorio" => true
            ),
            "consumidor|numero" => array(
                "obrigatorio" => true
            ),
            "consumidor|bairro" => array(
                "obrigatorio" => true
            ),
            "consumidor|cidade" => array(
                "obrigatorio" => true
            ),
            "consumidor|estado" => array(
                "obrigatorio" => true
            ),
            "consumidor|celular" => array(
                "obrigatorio" => true,
                "function" => array("valida_celular_os")
            ),
            "consumidor|email" => array(
                "obrigatorio" => true,
                "regex" => "email"
            ),
            "produto|troca_produto" => array(
                "function" => array("valida_troca_produto_tecvoz")
            ),
            "produto|serie" => array(
                "function" => array("valida_numero_de_serie_tecvoz")
            ),
            "revenda|nome" => array(
                "obrigatorio" => true
            ),
            "revenda|cnpj" => array(
                "obrigatorio" => true,
                "function"    => array("valida_revenda_cnpj")
            ),
            "revenda|telefone" => array(
                "obrigatorio" => true
            ),
            "revenda|cep" => array(
                "obrigatorio" => true
            ),
            "revenda|endereco" => array(
                "obrigatorio" => true
            ),
            "revenda|bairro" => array(
                "obrigatorio" => true
            ),
            "revenda|estado" => array(
                "obrigatorio" => true
            ),
            "revenda|cidade" => array(
                "obrigatorio" => true
            ),
            "revenda|numero" => array(
                "obrigatorio" => true
            )
            ));
		
			$valida_anexo_boxuploader = "valida_anexo_boxuploader";
    }
    else /* Posto Interno */
    {
        $regras = array_merge($regras,array(
            "os|id_tecnico" => array(
                "function" => array("valida_tecnico")
            ),
            "os|defeito_reclamado" => array(
                "obrigatorio" => true
            ),
            "consumidor|nome" => array(
                "obrigatorio" => true
            ),
            "consumidor|cpf" => array(
                "obrigatorio" => false,
                "function" => array("valida_consumidor_cpf")
            ),
            "consumidor|cep" => array(
                "obrigatorio" => false,
                "regex" => "cep"
            ),
            "produto|troca_produto" => array(
                "function" => array("valida_troca_produto_tecvoz")
            ),
            "revenda|cnpj" => array(
                "obrigatorio" => false,
                "function"    => array("valida_revenda_cnpj")
            ),
            "produto|serie" => array(
                "function" => array("valida_numero_de_serie_tecvoz")
            )
        ));
		
		$valida_anexo_boxuploader = "";
    }
}

function obriga_peca_defeito_contastado(){
    global $con, $campos, $login_fabrica,$login_posto, $msg_erro;

    $defeito_constatado_id = $campos['produto']['defeito_constatado'];
    $troca_produto = $campos["produto"]["troca_produto"];
    $sql = "SELECT lancar_peca 
            FROM tbl_defeito_constatado 
            WHERE defeito_constatado = $defeito_constatado_id AND fabrica = $login_fabrica";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res)>0){
        $lancar_peca = pg_fetch_result($res, 0, 'lancar_peca');
    }
    if (verifica_peca_lancada() == false AND $lancar_peca == 't' and empty($troca_produto)) {
        throw new Exception("Para esse defeito constatado é necessário lançar peca.");
    }
}


/**
 * - valida_numero_de_serie_tecvoz()
 * Realiza a validação do número de série
 * da Tecvoz, verificando se o número consiste
 * com o produto OU foi digitado S/N,
 * para cair em auditoria
 */
function valida_numero_de_serie_tecvoz()
{
    global $con, $campos, $login_fabrica,$login_posto, $msg_erro;

    $produto_id         = $campos["produto"]["id"];
    $produto_serie      = $campos["produto"]["serie"];
    $produto_sem_serie  = $campos["produto"]["sem_ns"];
    $data_compra        = formata_data($campos["os"]["data_compra"]);
    $serieValidada      = false;


    /*
     * - POSTO INTERNO não fará
     * a validação de garantia por
     * número de série
     */
    if (verifica_tipo_posto("posto_interno","NOT TRUE",$login_posto)) {
        /*
         * - Valida digitação
         * do Número de Série
         */
        if (!empty($produto_serie)) {
            $sql = "
                SELECT  tbl_numero_serie.data_fabricacao,
                        tbl_produto.garantia
                FROM    tbl_numero_serie
                JOIN    tbl_produto USING(produto)
                WHERE   tbl_numero_serie.fabrica    = $login_fabrica
                AND     tbl_produto.fabrica_i       = $login_fabrica
                AND     tbl_produto.produto         = $produto_id
                AND     tbl_numero_serie.serie      = '$produto_serie'
            ";
            $res = pg_query($con,$sql);

            if (pg_num_rows($res) == 0) {
                $msg_erro["msg"]["obrigatorio"] = "Número de Série não corresponde com o produto";
                $msg_erro["campos"][] = "produto[serie]";
            }

            $data_fab = pg_fetch_result($res,0,0);
            $garantia   = pg_fetch_result($res,0,1);

//             if (strtotime($data_compra) < strtotime($data_venda)) {
//                 $msg_erro["msg"]["obrigatorio"] = "Data de compra pelo consumidor é inválida";
//                 $msg_erro["campos"][] = "os[data_compra]";
//             }

            /*if (strtotime($data_fab ."+".$garantia." months") < strtotime(date('Y-m-d'))) {
                $msg_erro["msg"]["obrigatorio"] = "Produto Fora de Garantia";
            }*/
        }
    }

    /*
     * - Retira Obrigatoriedade de Número
     * de Série, caso esteja marcado como
     * produto Sem Série
     */

    if (!$produto_sem_serie && empty($produto_serie)) {
        $msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "produto[serie]";
    }
}

/**
 * - Validação de OS REINCIDENTE:
 * Regras: OS com mesmo número de série
 * e com menos de 90 dias poderá ser gravada
 * como reincidente
 */
function valida_os_reincidente()
{
    global $con, $campos, $login_fabrica,$os,$msg_erro;

    $produto_serie      = $campos["produto"]["serie"];
    $produto_sem_serie  = $campos["produto"]["sem_ns"];

    if (!empty($os)) {
	    $whereOs = "AND os < {$os}";
    }

    if (!empty($produto_serie) && !$produto_sem_serie) {
        $sql = "
		SELECT
			os
		FROM tbl_os
		WHERE serie = '{$produto_serie}'
		AND data_abertura < CURRENT_DATE
		AND (data_abertura + INTERVAL '90 days')::DATE  > CURRENT_DATE
		AND excluida IS NOT TRUE
    		{$whereOs}
		ORDER BY os DESC
		LIMIT 1;
	";

        $res = pg_query($con,$sql);

        if (pg_num_rows($res)) {
		$os_anterior = pg_fetch_result($res,0,0);
		
		$sqlGrava = "
			UPDATE tbl_os
			SET os_reincidente = TRUE
			WHERE os = $os;
		
			UPDATE  tbl_os_extra
                	SET os_reincidente = $os_anterior
                	WHERE os = $os;
	    	";

		$resGrava = pg_query($con,$sqlGrava);

        }
    }
}

/**
 * - Valida Anexo
 * Função que valida o numero de
 * anexos que o usuário do posto deve
 * cadastrar, conforme os dados passados
 */


function valida_anexo_tecvoz()
{
    global $campos, $msg_erro, $con, $login_fabrica;

    
    if (verifica_tipo_posto("posto_interno","NOT TRUE", $login_posto)) {
        
        $data_fabricacao = date("Y-m-d", strtotime(str_replace('/', '-', $campos['produto']['data_fabricacao'])));

        if ($campos['produto']['sem_ns'] == 't' || strtotime($data_fabricacao."+18 months") < strtotime(date('Y-m-d'))) {
            $anexos_obrigatorios = ["notafiscal"];
        }

        
    }
}
$valida_anexo = "valida_anexo_tecvoz";
/*
 * -- AUDITORIAS --
 */

/**
 * - auditoria_km_tecvoz()
 * Entra em Auditoria, qualquer OS
 * em Deslocamento
 */
function auditoria_km_tecvoz()
{
    global $con, $campos, $login_fabrica,$os;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    $sql = "
        SELECT  COUNT(1) AS tem_deslocamento
        FROM    tbl_tipo_atendimento
        WHERE   tipo_atendimento    = $tipo_atendimento
        AND     fabrica             = $login_fabrica
        AND     km_google           IS TRUE
    ";
    $res = pg_query($con,$sql);

    $tem_deslocamento = pg_fetch_result($res,0,0);

    if ($tem_deslocamento > 0 && verifica_auditoria_unica("tbl_auditoria_status.km = 't'",$os) && $troca_produto != 't') {
        $busca = buscaAuditoria("tbl_auditoria_status.km = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }
        $sqlGrava = "
            INSERT INTO tbl_auditoria_os (
                os,
                auditoria_status,
                observacao
            ) VALUES (
                $os,
                $auditoria_status,
                'OS em auditoria de KM por deslocamento de técnico'
            )
        ";
        $resGrava = pg_query($con,$sqlGrava);

        if (pg_last_error($con)) {
            throw new Exception("Erro ao lançar Ordem de Servico");
        }
    }
}

/**
 * - auditoria_numero_serie_tecvoz()
 * Caso a OS tenha como número de Série
 * o termo 'S/N', entrará em Auditoria
 */
function auditoria_numero_serie_tecvoz()
{
    global $con, $campos, $login_fabrica,$os;

    $produto_id         = $campos["produto"]["id"];
    $produto_serie      = $campos["produto"]["serie"];
    $produto_sem_serie  = $campos["produto"]["sem_ns"];

    /*
     * - POSTO INTERNO não fará
     * a validação de garantia por
     * número de série
     */
    if (verifica_tipo_posto("posto_interno","NOT TRUE",$login_posto)) {
        /*
         * - Valida digitação
         * do Número de Série
         */
        if (!empty($produto_serie) && !$produto_sem_serie) {
            $sql = "
                SELECT  tbl_numero_serie.data_venda,
                        tbl_produto.garantia
                FROM    tbl_numero_serie
                JOIN    tbl_produto USING(produto)
                WHERE   tbl_numero_serie.fabrica    = $login_fabrica
                AND     tbl_produto.fabrica_i       = $login_fabrica
                AND     tbl_produto.produto         = $produto_id
                AND     tbl_numero_serie.serie      = '$produto_serie'
            ";
            $res = pg_query($con,$sql);

            $data_venda = pg_fetch_result($res,0,data_venda);
            $garantia   = pg_fetch_result($res,0,garantia);


            if (strtotime($data_venda ."+".$garantia." months") < strtotime(date('Y-m-d')) && verifica_auditoria_unica("tbl_auditoria_status.numero_serie = 't' AND observacao ILIKE '%Fora de garantia%'",$os) && $troca_produto != 't') {
                $busca = buscaAuditoria("tbl_auditoria_status.numero_serie = 't'");

                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];
                }
                $sqlGrava = "
                    INSERT INTO tbl_auditoria_os (
                        os,
                        auditoria_status,
                        observacao
                    ) VALUES (
                        $os,
                        $auditoria_status,
                        'OS em auditoria por produto com Número de série Fora de garantia'
                    )
                ";
                $resGrava = pg_query($con,$sqlGrava);
            }
        }

        if ($produto_sem_serie && verifica_auditoria_unica("tbl_auditoria_status.numero_serie = 't' AND observacao ILIKE '%por falta%'",$os)) {
            $busca = buscaAuditoria("tbl_auditoria_status.numero_serie = 't'");

            if($busca['resultado']){
                $auditoria_status = $busca['auditoria'];
            }
            $sqlGrava = "
                INSERT INTO tbl_auditoria_os (
                    os,
                    auditoria_status,
                    observacao
                ) VALUES (
                    $os,
                    $auditoria_status,
                    'OS em auditoria por falta de Número de Série'
                )
            ";
            $resGrava = pg_query($con,$sqlGrava);

            if (pg_last_error($con)) {
                throw new Exception("Erro ao lançar Ordem de Servico");
            }
        }
    }
}

/**
 * - auditoria_troca_tecvoz()
 * Entra em auditoria toda Ordem de Serviço
 * que ter menos de 15 dias entre a data de
 * abertura e da compra do produto
 */
function auditoria_troca_tecvoz()
{
    global $con, $campos, $login_fabrica,$os;

    $data_abertura  = formata_data($campos["os"]["data_abertura"]);
    $data_compra    = formata_data($campos["os"]["data_compra"]);

    if (strtotime($data_compra . "+ 15 days") >= strtotime($data_abertura) && verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't'",$os) && $troca_produto != 't') {
        $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }
        $sqlGrava = "
            INSERT INTO tbl_auditoria_os (
                os,
                auditoria_status,
                observacao
            ) VALUES (
                $os,
                $auditoria_status,
                'OS em auditoria por proximidade entre data compra e abertura de OS'
            )
        ";
        $resGrava = pg_query($con,$sqlGrava);

        if (pg_last_error($con)) {
            throw new Exception("Erro ao lançar Ordem de Servico");
        }
    }
}

/**
 * - auditoria_troca_placa_tecvoz()
 * ## APENAS PARA POSTO EXTERNO ##
 * Verifica se o serviço realizado é
 * Troca de Placa. Se for, entrará em
 * auditoria de peças
 */
function auditoria_troca_placa_tecvoz()
{
    global $con, $campos, $login_fabrica,$login_posto, $msg_erro,$os;

    if (verifica_tipo_posto("posto_interno","NOT TRUE",$login_posto)) {

	$servicoTrocaPlaca = false;

	foreach ($campos["produto_pecas"] as $i => $servico_realizado) {
	    if (strlen($servico_realizado['servico_realizado']) > 0) {
		$verificaServico = verificaServicoRealizado($servico_realizado['servico_realizado']);
                $tipoServico = $verificaServico[0]->descricao;
                if (strpos($tipoServico,"Placa")) {
                    $servicoTrocaPlaca = true;
                    break;
		}
	    }
	}

        if ($servicoTrocaPlaca && verifica_auditoria_unica("tbl_auditoria_status.peca = 't'",$os) && $troca_produto != 't') {
            $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

            if($busca['resultado']){
                $auditoria_status = $busca['auditoria'];
            }
            $sqlGrava = "
                INSERT INTO tbl_auditoria_os (
                    os,
                    auditoria_status,
                    observacao
                ) VALUES (
                    $os,
                    $auditoria_status,
                    'OS em auditoria para troca de placa'
                )
            ";
            $resGrava = pg_query($con,$sqlGrava);

            if (pg_last_error($con)) {
                throw new Exception("Erro ao lançar Ordem de Servico");
            }
        }
    }
}

/**
 * - auditoria_troca_produto_tecvoz()
 * Entra em auditoria Ordem de Serviço
 * que foi selecionado o tipo de atendimento
 * solicitação de troca
 */
function auditoria_troca_produto_tecvoz()
{
    global $con, $campos, $login_fabrica, $os;

    $troca_produto = $campos['produto']['troca_produto'];

    if ($troca_produto == 't' && verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't'", $os)) {
        $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }
        $sqlGrava = "
            INSERT INTO tbl_auditoria_os (
                os,
                auditoria_status,
                observacao
            ) VALUES (
                $os,
                $auditoria_status,
                'OS em auditoria por Solicitação de Troca'
            )
        ";
        $resGrava = pg_query($con,$sqlGrava);

        if (pg_last_error($con)) {
            throw new Exception("Erro ao lançar Ordem de Servico");
        }
    }
}
