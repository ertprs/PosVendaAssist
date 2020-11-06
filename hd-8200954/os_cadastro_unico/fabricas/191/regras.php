<?php
$data_abertura_fixa = true;

$regras["consumidor|cpf"]["obrigatorio"] = true;
$regras["consumidor|celular"]["obrigatorio"] = true;
$regras["consumidor|telefone"]["obrigatorio"] = true;

if (strlen(trim(getValue("consumidor[celular]"))) > 0 OR strlen(trim(getValue("consumidor[telefone]"))) > 0) {
    $regras["consumidor|telefone"]["obrigatorio"] = false;
    $regras["consumidor|celular"]["obrigatorio"] = false;
}

$regras["consumidor|email"]["obrigatorio"] = true;
$regras["os|defeito_reclamado"]["obrigatorio"] = true;

$regras["produto|serie"]["obrigatorio"] = false;


$id_orcamento = 0;
function id_tipo_atendimento_orcamento(){

    global $con, $login_fabrica;

    $sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND descricao ILIKE 'Or%amento'";
    $res = pg_query($con, $sql);

    return (pg_num_rows($res) > 0) ? pg_fetch_result($res, 0, "tipo_atendimento") : 0;

}

/* Insere o ID do tipo atendimento Orçamento para o posto interno */
if(verificaTipoPosto("posto_interno","TRUE") || $areaAdmin == true){
    $valida_anexo = "";
    $id_orcamento = id_tipo_atendimento_orcamento();
    $regras["os|numero_nf_remessa"]["obrigatorio"] = true;
    $regras["os|nota_fiscal"]["obrigatorio"] = false;
    $regras["os|data_compra"]["obrigatorio"] = false;
    $regras["os|defeito_reclamado"]["obrigatorio"] = false;
    $regras["consumidor|email"]["obrigatorio"] = false;

    if($areaAdmin != true){
        $regras["revenda|nome"]["obrigatorio"]   = true;
        $regras["revenda|cnpj"]["obrigatorio"]   = true;
    }

    if(getValue("os[tipo_atendimento]") == $id_orcamento){
	
	$regras_pecas = array( 
		 "lista_basica" => true,
		 "servico_realizado" => false,
		 "bloqueada_garantia" => true
	);
    }
    

}

function verifica_tipo_atendimento() {
    global $con, $login_fabrica;

    if (getValue("os[tipo_atendimento]")) {
        $sqlTipo = "SELECT lower(fn_retira_especiais(descricao)) AS descricao FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento=".getValue("os[tipo_atendimento]");
        $resTipo = pg_query($con, $sqlTipo);
        $descricaoTipo = pg_fetch_result($resTipo, 0, 'descricao');
        return $descricaoTipo;
    }

}

function verificaTipoPosto($tipo, $valor) {
    global $campos, $con, $login_fabrica, $login_posto;

    $id_posto = (strlen($login_posto) > 0) ? $login_posto : $_REQUEST["posto"]["id"];

	if(!empty($id_posto)) {
		$sql = "
			SELECT tbl_tipo_posto.tipo_posto
			FROM tbl_posto_fabrica
			INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
			AND tbl_posto_fabrica.posto = {$id_posto}
			AND tbl_tipo_posto.{$tipo} IS {$valor}
		";
		$res = pg_query($con, $sql);
	}
    if (pg_num_rows($res) > 0) {
        return true;
    } else {
        return false;
    }
}

if (!verificaTipoPosto("posto_interno","TRUE",$login_posto)) {

        $auditorias = array(
            "auditoria_peca_critica",
            "auditoria_km_fluidra",
            "auditoria_troca_obrigatoria",
            "auditoria_os_reincidente_fluidra",
            "auditoria_peca_garantia",
            "auditoria_valor_adicional_fluidra"
        );

} else {

    $regras["os|tecnico"] = array(
        "obrigatorio" => true
    );

    if (verifica_tipo_atendimento() == "Orçamento"){
        $regras["os|nota_fiscal"]["obrigatorio"]   = false;
        $regras["os|data_compra"]["obrigatorio"] = false;
        $regras["revenda|nome"]["obrigatorio"]   = false;
        $regras["revenda|cnpj"]["obrigatorio"]   = false;
        $regras["revenda|cnpj"]["function"]      = [];
        $regras["revenda|estado"]["obrigatorio"] = false;
        $regras["revenda|cidade"]["obrigatorio"] = false;
        $regras["produto|serie"]["obrigatorio"] = false;
        $regras_pecas["servico_realizado"] = false;
        $regras["produto|serie"] = array(
            "function" => array()
        );
        $valida_garantia = "";

    }

    $auditorias = [];

}

function amarraClienteAdminOs() {
    global $con, $login_fabrica, $os, $campos;

    if (isset($campos["revenda"]["cnpj"]) && strlen($os) > 0 && strlen($campos["revenda"]["cnpj"]) > 0) {

        $sqlOs = "SELECT os FROM tbl_os WHERE cliente_admin IS NULL AND os = {$os} AND fabrica = {$login_fabrica}";
        $resOs = pg_query($con, $sqlOs);

        if (pg_num_rows($resOs) > 0) {
            $revenda_cnpj = str_replace(["-","/","."], "", $campos["revenda"]["cnpj"]);

            $sqlClienteAdmin = "SELECT cliente_admin,email FROM tbl_cliente_admin WHERE cnpj = '{$revenda_cnpj}' AND fabrica = {$login_fabrica}";
            $resClienteAdmin = pg_query($con, $sqlClienteAdmin);

            if (pg_num_rows($resClienteAdmin) > 0) {

                $xcliente_admin = pg_fetch_result($resClienteAdmin, 0, 'cliente_admin');
                $xemail = pg_fetch_result($resClienteAdmin, 0, 'email');

                $updateOs  = "UPDATE tbl_os SET cliente_admin={$xcliente_admin} WHERE os={$os} AND fabrica = {$login_fabrica}";
                $resUpdate = pg_query($con, $updateOs);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço #CLIADM");
                } else {
                    
                    if (strlen($xemail) > 0) {

                        include __DIR__."/../../../class/communicator.class.php";
                        $mailTc = new TcComm('smtp@posvenda');

                        $corpoMensagem = "Foi aberta uma Ordem de Serviço com o Número: {$os}";

                        $email   = $xemail;
                        $email   = "ronald.santos@telecontrol.com.br";//remover
                        $assunto = "Nova Ordem de Serviço Aberta - Fluidra";
                        $envio   = $mailTc->sendMail(
                            $email,
                            $assunto,
                            $corpoMensagem,
                            'noreply@telecontrol.com.br'
                        );

                    }
                }
            }
        }
    }
}


function atualizaRefIdHDForOs() {
    global $con, $login_fabrica, $os, $campos;

    if (isset($_REQUEST['preos']) && strlen($_REQUEST['preos']) > 0) {

        $xpreos   = $_REQUEST['preos'];

        $sqlTdocs = "SELECT * FROM tbl_tdocs WHERE referencia_id = {$xpreos}, contexto='os', referencia='os' AND fabrica = {$login_fabrica}";
        $resTdocs = pg_query($con, $sqlTdocs);

        if (pg_num_rows($resTdocs) > 0) {
    
            $upsqlTdocs = "UPDATE tbl_tdocs SET referencia_id={$os} WHERE referencia_id = {$xpreos} AND fabrica = {$login_fabrica}";
            $upresTdocs = pg_query($con, $upsqlTdocs);

            if (pg_last_error()) {
                throw new Exception("Erro ao lançar ordem de serviço #ATCTXOS");
            }
        }
        //refencia id troca os
    }
}

function auditoria_valor_adicional_fluidra(){
    global $con, $campos, $os, $login_fabrica;

    if(!empty($os) && count($campos["os"]["valor_adicional"]) > 0){
        
        if(verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao = 'OS em Auditoria de Valores Adicionais' AND tbl_auditoria_os.reprovada IS NULL AND tbl_auditoria_os.liberada IS NULL ", $os) === true){
            $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

            if($busca["resultado"]){
                $auditoria = $busca["auditoria"];

                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria, 'OS em Auditoria de Valores Adicionais')";
                pg_query($con,$sql);

                if(strlen(pg_last_error()) > 0){
                    throw new Exception("Erro ao criar auditoria de aplicação indevida para a OS");

                }else{
                    return true;
                }
            }else{
                throw new Exception("Erro ao buscar auditoria aplicação indevida");

            }
        }
    }
}

function auditoria_os_reincidente_fluidra() {
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

    $posto = $campos['posto']['id'];
    $cpf = preg_replace("/\D/", "", $campos["consumidor"]["cpf"]);

    $sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE AND cancelada IS NOT TRUE;";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res) > 0){

        $select = "
            SELECT
                tbl_os.os
            FROM tbl_os
            INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            WHERE tbl_os.fabrica = {$login_fabrica}
            AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
            AND tbl_os.excluida IS NOT TRUE
            AND tbl_os.os < {$os}
            AND tbl_os.posto = {$posto}
            AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
            AND tbl_os_produto.produto = {$campos['produto']['id']}
            AND tbl_os.consumidor_cpf = '{$cpf}'
            ORDER BY tbl_os.data_abertura DESC
            LIMIT 1;
        ";

        $resSelect = pg_query($con, $select);

        if (pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {
            $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");


            if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
                $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];
                }

                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'Auditoria de OS reincidente', true);
                ";

                pg_query($con,$sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço #AUD001");
                } else {
                    $os_reincidente_justificativa = true;
                    $os_reincidente = true;
                }
            }
        }
    }
}

function auditoria_km_fluidra() {
    global $con, $os, $login_fabrica, $campos;

    if ($campos['os']['solicita_km'] && verifica_auditoria_unica("tbl_auditoria_status.km = 't' AND tbl_auditoria_os.observacao ILIKE '%auditoria de KM%'", $os) === true) {
        $busca = buscaAuditoria("tbl_auditoria_status.km = 't'");
        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }

        $qtde_km = $campos["os"]["qtde_km"];
        $qtde_km_anterior = $campos["os"]["qtde_km_hidden"];

        if (!strlen($campos["os"]["qtde_km_hidden"])) {
            $campos["os"]["qtde_km_hidden"] = $campos["os"]["qtde_km"];
        }

        if ($qtde_km > 0) {
                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de KM', false);
                ";
        } elseif ($qtde_km != $campos["os"]["qtde_km_hidden"]) {
            $programa_insert = $_SERVER['PHP_SELF'];
          
            $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'KM alterado manualmente de $qtde_km_anterior para $qtde_km', false);
                ";
        }

        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD012");
        }
    }
}

function auditoria_peca_garantia(){
    global $login_fabrica, $campos, $os, $con, $login_admin;

    if(verifica_peca_lancada() === true){
        foreach ($campos["produto_pecas"] as $key => $dadosPecas) {
            if (!empty($dadosPecas["id"]) && !empty($dadosPecas["servico_realizado"])) {

                $servico_realizado = $dadosPecas["servico_realizado"];

                $sql = "SELECT servico_realizado
                        FROM tbl_servico_realizado
                        WHERE servico_realizado = {$servico_realizado}
                        AND (gera_pedido IS TRUE OR peca_estoque IS TRUE)";
                $res = pg_query($con, $sql);

                if (verifica_auditoria_unica("tbl_auditoria_os.observacao = 'Auditoria de pedido de peças'", $os) === true && pg_num_rows($res) > 0) {

                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) 
                            VALUES ({$os}, 4, 'Auditoria de pedido de peças', true)";
                    $res = pg_query($con, $sql);

                }
            }
        }
    }
}

function grava_os_fabrica() {
    global $con, $campos, $os;

    $cliente_admin     = $campos["os"]["cliente_admin"];

    if (strlen($cliente_admin) > 0) {
        $return['cliente_admin'] = $cliente_admin ;  
    }

    $descricao_status_orcamento = $campos["os"]["status_orcamento"];
    $sql_status = "SELECT status_os FROM tbl_status_os WHERE UPPER(fn_retira_especiais(descricao)) = UPPER(fn_retira_especiais(trim('{$descricao_status_orcamento}')))";
    $res_status = pg_query($con, $sql_status);

    if(pg_num_rows($res_status) > 0){
        $id_status_os = pg_fetch_result($res_status, 0, 'status_os');
        $return["status_os_ultimo"] = $id_status_os;
    }

    return $return;

}

function grava_os_extra_fabrica() {
   
    global $con, $campos, $os, $login_fabrica;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
   
    if (!empty($tipo_atendimento)) {
        $sql = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento}";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) > 0){
            $descricao = pg_fetch_result($res_status, 0, 'descricao');

            if (verifica_tipo_atendimento() == "orcamento"){

                $mo_adicional = str_replace(",",".",str_replace(".","", $campos["os"]["valor_adicional_mo"]));

                if (!empty($mo_adicional)){
                    return array(
                        "mao_de_obra_adicional" => $mo_adicional,
                    );
                }
            }
        }
    }
}

function grava_os_campo_extra_fabrica() {
    global $campos;

    $return = array("numero_nf_remessa" => $campos["os"]["numero_nf_remessa"]);
    
    return $return;
}

function grava_multiplas_solucoes_fluidra() {
    global $con, $os, $campos, $login_fabrica;

    if (!empty($campos["produto"]["solucoes_multiplos"])) {
        $solucoes = explode(",", $campos["produto"]["solucoes_multiplos"]);
        for($i = 0; $i < count($solucoes); $i++){

            $sol = $solucoes[$i];

            $sql_sol = "SELECT defeito_constatado_reclamado FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND solucao = {$sol}";
            $res_sol = pg_query($con, $sql_sol);

            if (!pg_num_rows($res_sol)) {
                $sql_sol = "INSERT INTO tbl_os_defeito_reclamado_constatado (os, solucao, fabrica) VALUES ({$os}, {$sol}, {$login_fabrica})";
                $res_sol = pg_query($con, $sql_sol);
            }
        }
    }

}

function enviaEmailSeparacaoEstoque(){
    global $con, $os, $campos, $login_fabrica;

    $pecas_os = $campos['produto_pecas'];

    foreach ($pecas_os as $pecas) {

        if(!empty($pecas["id"]) AND $pecas['separa_estoque'] == "t"){

            $servico          = $pecas["servico_realizado"];
            $peca             = $pecas["id"];
            $peca_referencia  = $pecas["referencia"];
            $peca_descricao   = $pecas["descricao"];
            $qtde             = $pecas["qtde"];
            $os_item          = get_os_item($os, $peca);
            $liberacao_pedido = get_liberacao_pedido_os_item($os_item,$os);

            if($liberacao_pedido != "t"){
                $pecas_email[] = array("referencia" => $peca_referencia, "descricao" => $peca_descricao, "qtde" => $qtde);

                $sql = "UPDATE tbl_os_item SET liberacao_pedido = TRUE WHERE os_item = {$os_item}";
                $res = pg_query($con,$sql);
                
            }
        }
    }

    if(count($pecas_email) > 0){

        include __DIR__."/../../../class/communicator.class.php";
        $mailTc = new TcComm('smtp@posvenda');

        $corpoMensagem = "Realizar a transferência de estoque do M1 para o 31 para os itens da Ordem de Serviço {$os} listadas abaixo: <br><br>";

        $corpoMensagem .= "<table cellspacing='0' cellpadding='0'>
                <tr>
                    <td style='border:solid 1px;font-weight:bold;'>Referência</td>
                    <td style='border:solid 1px;font-weight:bold;'>Descrição</td>
                    <td style='border:solid 1px;font-weight:bold;'>Qtde</td>
                </tr>";

        foreach ($pecas_email as $key => $value) {
            $corpoMensagem .= "<tr>
                    <td style='border:solid 1px;'>{$value['referencia']}</td>
                    <td style='border:solid 1px;'>{$value['descricao']}</td>
                    <td align='center' style='border:solid 1px;'>{$value['qtde']}</td>
                  </tr>";
        }

        $corpoMensagem .= "</table>";

        $email = "flavio.zequin@telecontrol.com.br";
        $assunto = "Solicitação de transferência de estoque";
        $envio = $mailTc->sendMail(
            $email,
            $assunto,
            $corpoMensagem,
            'noreply@telecontrol.com.br'
        );

    }
}

function envia_email_consumidor() {
    global $con, $login_fabrica, $campos, $os, $externalId, $_REQUEST;

    
    include __DIR__."/../../../class/communicator.class.php";

    $mailTc = new TcComm('smtp@posvenda');
    $sql = "select tbl_os.os,
                   tbl_os.sua_os,
                   tbl_os.consumidor_nome,
                   tbl_os.consumidor_email,
                   tbl_os.consumidor_cidade,
                   tbl_os.consumidor_estado,
                   tbl_os.consumidor_fone,
                   tbl_os.consumidor_cpf,
                   tbl_os.consumidor_revenda,
                   tbl_os.consumidor_endereco,
                   tbl_os.consumidor_numero,
                   tbl_os.consumidor_cep,
                   tbl_os.consumidor_complemento,
                   tbl_os.consumidor_bairro,
                   tbl_os.consumidor_celular,
                   tbl_os.consumidor_fone_comercial,
                   tbl_os.consumidor_nome_assinatura,
                   tbl_os.consumidor_fone_recado,
                   tbl_os.tipo_atendimento,
                   tbl_os.cliente_admin,
                   TO_CHAR(tbl_os.finalizada, 'DD/MM/YYYY HH24:MI:SS') AS finalizada,
                   TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY HH24:MI:SS') AS data_digitacao,
                   TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
                   TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
                   tbl_os.revenda_cnpj,
                   tbl_os.revenda_nome,
                   TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nf,
                   tbl_os.revenda_nome,
                   tbl_os.revenda_cnpj,
                   tbl_defeito_reclamado.descricao AS defeito_reclamado_nome,
                   tbl_defeito_constatado.descricao AS defeito_constatado_nome,
                   tbl_os.nota_fiscal,
                   tbl_produto.descricao AS nome_produto,
                   tbl_produto.referencia AS referencia_produto,
                   tbl_status_os.descricao AS status_orcamento,
                   tbl_os_extra.mao_de_obra_adicional,
                   tbl_tipo_atendimento.descricao AS tipo_atendimento_desc,
                   tbl_status_checkpoint.descricao AS status_da_os,
                   tbl_os_revenda.campos_extra AS revenda_campos_extra,
                   tbl_revenda_fabrica.contato_email AS email_revenda
             FROM tbl_os 
             JOIN tbl_os_produto USING(os)
             JOIN tbl_os_extra USING(os)
             JOIN tbl_status_checkpoint ON tbl_os.status_checkpoint = tbl_status_checkpoint.status_checkpoint
             JOIN tbl_tipo_atendimento ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento  AND tbl_tipo_atendimento.fabrica={$login_fabrica}
        LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado AND tbl_defeito_reclamado.fabrica={$login_fabrica}
        LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica={$login_fabrica}
        LEFT JOIN tbl_status_os ON tbl_os.status_os_ultimo = tbl_status_os.status_os
        LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i={$login_fabrica}
        LEFT JOIN tbl_os_revenda ON tbl_os_revenda.sua_os = SPLIT_PART(tbl_os.sua_os, '-', 1) AND tbl_os_revenda.fabrica = {$login_fabrica}
        LEFT JOIN tbl_revenda_fabrica ON tbl_os.revenda = tbl_revenda_fabrica.revenda AND tbl_revenda_fabrica.fabrica = {$login_fabrica}
            WHERE tbl_os.os={$os}
              AND tbl_os.fabrica={$login_fabrica};";
        
  
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        $xos = pg_fetch_result($res, 0, 'os');
        $consumidor_nome                = pg_fetch_result($res, 0, 'consumidor_nome');
        $consumidor_email               = pg_fetch_result($res, 0, 'consumidor_email');
        $consumidor_cidade              = pg_fetch_result($res, 0, 'consumidor_cidade');
        $consumidor_estado              = pg_fetch_result($res, 0, 'consumidor_estado');
        $consumidor_fone                = pg_fetch_result($res, 0, 'consumidor_fone');
        $consumidor_cpf                 = pg_fetch_result($res, 0, 'consumidor_cpf');
        $consumidor_revenda             = pg_fetch_result($res, 0, 'consumidor_revenda');
        $consumidor_endereco            = pg_fetch_result($res, 0, 'consumidor_endereco');
        $consumidor_numero              = pg_fetch_result($res, 0, 'consumidor_numero');
        $consumidor_cep                 = pg_fetch_result($res, 0, 'consumidor_cep');
        $consumidor_complemento         = pg_fetch_result($res, 0, 'consumidor_complemento');
        $consumidor_bairro              = pg_fetch_result($res, 0, 'consumidor_bairro');
        $consumidor_celular             = pg_fetch_result($res, 0, 'consumidor_celular');
        $consumidor_fone_comercial      = pg_fetch_result($res, 0, 'consumidor_fone_comercial');
        $consumidor_nome_assinatura     = pg_fetch_result($res, 0, 'consumidor_nome_assinatura');
        $consumidor_fone_recado         = pg_fetch_result($res, 0, 'consumidor_fone_recado');
        $tipo_atendimento               = pg_fetch_result($res, 0, 'tipo_atendimento_desc');
        $qtde_km                        = pg_fetch_result($res, 0, 'qtde_km');
        $data_abertura                  = pg_fetch_result($res, 0, 'data_abertura');
        $data_digitacao                 = pg_fetch_result($res, 0, 'data_digitacao');
        $data_fechamento                = pg_fetch_result($res, 0, 'data_fechamento');
        $data_nf                        = pg_fetch_result($res, 0, 'data_nf');
        $rev_nome                       = pg_fetch_result($res, 0, 'revenda_nome');
        $rev_cnpj                       = pg_fetch_result($res, 0, 'revenda_cnpj');
        $defeito_reclamado              = pg_fetch_result($res, 0, 'defeito_reclamado_nome');
        $defeito_constatado             = pg_fetch_result($res, 0, 'defeito_constatado_nome');
        $nf                             = pg_fetch_result($res, 0, 'nota_fiscal');
        $data_nf                        = pg_fetch_result($res, 0, 'data_nf');
        $finalizada                     = pg_fetch_result($res, 0, 'finalizada');
        $data_consertado                = pg_fetch_result($res, 0, 'data_consertado');
        $referencia_produto             = pg_fetch_result($res, 0, 'referencia_produto');
        $nome_produto                   = pg_fetch_result($res, 0, 'nome_produto');
        $numero_serie                   = pg_fetch_result($res, 0, 'numero_serie');
        $rg_produto                     = pg_fetch_result($res, 0, 'rg_produto');
        $status_orcamento               = pg_fetch_result($res, 0, 'status_orcamento');
        $status_da_os                   = pg_fetch_result($res, 0, 'status_da_os');
        $mao_de_obra_adicional          = pg_fetch_result($res, 0, 'mao_de_obra_adicional');
        $email_revenda                  = pg_fetch_result($res, 0, 'email_revenda');
        $cliente_admin                  = pg_fetch_result($res, 0, 'cliente_admin');
        $revenda_campos_extra           = json_decode(pg_fetch_result($res, 0, 'revenda_campos_extra'), true);
        $revenda_email                  = $revenda_campos_extra["revenda_email"];
        $tipo_atendimento               = $campos["os"]["tipo_atendimento"]; 
        $tipo_de_os                     = $campos["os"]["consumidor_revenda"];

        $status_os = $status_da_os;
        $status_oc = $status_orcamento ;
        $os_fabricante = "OS FABRICANTE";

        $xos2 = $xos;
        
        if (in_array($tipo_atendimento, [33004,76967]) && $tipo_de_os == "R") {
            $consumidor_email = (!empty($revenda_email)) ? $revenda_email : $consumidor_email;
            $xos2             = pg_fetch_result($res, 0, 'sua_os');
        }

        $email = (!empty($cliente_admin)) ? $email_revenda : $consumidor_email;

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $assunto    = 'Fluidra - Orçamento';

            $corpoMensagem = '
            <p>Prezado '.$consumidor_nome.'.</p>
            <p>Segue abaixo o orçamento para reparo do produto <b>'.$referencia_produto .' - '. $nome_produto.'</b>.</p><br>
                    <table width="700" border="0" cellspacing="1" cellpadding="0" style="border: 1px solid #d2e4fc;background-color: #485989;" align="center">
                        <tbody>
                            <tr>
                                <td rowspan="4" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="300">
                                    <center>
                                        '.$os_fabricante.'<br>&nbsp;
                                        <b><font size="6" color="#C67700">'.$xos2.'</font></b>
                                    </center>
                                </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" height="15" colspan="4">&nbsp;Datas da OS</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Abertura &nbsp;</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15">&nbsp;'.$data_abertura.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Digitação </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15">&nbsp;'.$data_digitacao.'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Fechamento&nbsp;</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15" id="data_fechamento">&nbsp;'.$data_fechamento.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Finalizada </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15" id="finalizada">&nbsp;'.$finalizada.'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Data da NF </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$data_nf.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Fechado em  </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" id="fechado_em" width="100" height="15">&nbsp;'.$data_fechamento.'      </td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">
                                    <b></b><center><b>
                                        '.$status_os.'
                                    </b></center>
                                </td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Consertado &nbsp; </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15" colspan="1" id="consertado">&nbsp;&nbsp;'.$data_consertado.'</td>                
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15"></td>
                            </tr>
                        </tbody>
                    </table>

                    <table width="700" border="0" cellspacing="1" cellpadding="0" style="border: 1px solid #d2e4fc;background-color: #485989;" align="center">
                        <tbody>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Tipo de Atendimento</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$tipo_atendimento.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="100">STATUS ORÇAMENTO</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" nowrap="">&nbsp;'.$status_oc.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="100">Quantidade de KM</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" nowrap="">&nbsp;'.$qtde_km.' KM</td>
                            </tr>
                        </tbody>
                    </table>

                    <table width="700" border="0" cellspacing="1" cellpadding="0" style="border: 1px solid #d2e4fc;background-color: #485989;" align="center">
                        <tbody>
                            <tr>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" height="15" colspan="100%">&nbsp;Informações do Produto   </td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">Referência</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$referencia_produto.' </td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">Descrição</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$nome_produto.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">N. de Série   &nbsp;</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">'.$numero_serie.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">RG Produto</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" nowrap="">'.$rg_produto.'</td>
                            </tr>
                       </tbody>
                    </table>

                    <table width="700" border="0" cellspacing="1" cellpadding="0" align="center" style="border: 1px solid #d2e4fc;background-color: #485989;">
                        <tbody>
                            <tr>
                                <td height="15" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" colspan="100%">&nbsp;Defeitos</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">Reclamado</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" width="140">'.$defeito_reclamado.'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Defeito Constatado</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp; '.$defeito_constatado.'</td>
                            </tr>
                        </tbody>
                    </table>

                    <table width="700" border="0" cellspacing="1" cellpadding="0" align="center" style="border: 1px solid #d2e4fc;background-color: #485989;">
                        <tbody>
                            <tr>
                                <td  style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" colspan="100%" height="15">&nbsp;Informações sobre o consumidor </td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Nome</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" width="300">&nbsp;'.$consumidor_nome.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">Telefone Residencial</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$consumidor_fone.'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Celular</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" width="300">&nbsp;'.$consumidor_celular.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">Telefone Comercial</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_fone_comercial .'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" nowrap="">CPF Consumidor</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15"> &nbsp;'. $consumidor_cpf .'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">CEP</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_cep .'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Endereço</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_endereco .'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Número</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_numero .'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Complemento</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_complemento .'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Bairro</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" nowrap="">&nbsp;'. $consumidor_bairro .'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">Cidade</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp;'. $consumidor_cidade .'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">Estado</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp;'. $consumidor_estado .'</td>
                            </tr>
                           <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">E-Mail</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp;'. $consumidor_email .'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">&nbsp;</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>

                    <table width="700" border="0" cellspacing="1" cellpadding="0" align="center" style="border: 1px solid #d2e4fc;background-color: #485989;">
                        <tbody>
                            <tr>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" colspan="100%" height="15">&nbsp;Informações Da Revenda</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Nome</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" width="300" 1="">&nbsp;'.$rev_nome.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="80">CNPJ Revenda</td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$rev_cnpj.'</td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">NF Número </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$nf.'</td>
                                <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Data da NF </td>
                                <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$data_nf.'</td>
                            </tr>
                        </tbody>
                    </table>

                    <table width="700" border="0" cellspacing="1" cellpadding="0" align="center" style="border: 1px solid #d2e4fc;background-color: #485989;">
                            <tbody>
                                <tr>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" colspan="100%" height="15">&nbsp;DIAGNÓSTICOS - COMPONENTES - MANUTENÇÕES EXECUTADAS</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: left;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Componente</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: center;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">QTD</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: center;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Preço unitário</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: center;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">PREÇO TOTAL</td>
                                </tr>';
                        $sqlItens = "
                            SELECT tbl_os_item.peca,
                                tbl_os_item.qtde,
                                tbl_peca.referencia,
                                tbl_peca.descricao,
                                tbl_os_item.preco
                            FROM tbl_os
                                JOIN tbl_os_produto USING(os)
                                JOIN tbl_os_item USING(os_produto)
                                JOIN tbl_peca USING(peca)
                            WHERE
                                tbl_os.os = $xos
                                AND tbl_os.fabrica = $login_fabrica;";

                        $resItens = pg_query($con,$sqlItens);

                        if (pg_num_rows($resItens) > 0) {
                            $total_pecas = [];
                            foreach (pg_fetch_all($resItens) as $key => $rows) {
                            $total_pecas[] = ($rows['qtde']*$rows['preco']);

                $corpoMensagem .= '
                                <tr>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">'. $rows['referencia'] .' - '. $rows['descricao'] .'</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: center;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">'. $rows['qtde'] .'</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: center;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format($rows['preco'], 2, ',', '.') .'</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: center;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format(($rows['qtde']*$rows['preco']), 2, ',', '.') .'</td>
                                </tr>';
                            }
                        }
                $corpoMensagem .= '
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" colspan="3">Valor de MÃO DE OBRA</td>
                                    <td  colspan="8" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format($mao_de_obra_adicional, 2, ',', '.') .'</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" colspan="3">Valor Total Peças</td>
                                    <td colspan="8" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format(array_sum($total_pecas), 2, ',', '.') .'</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" colspan="3">Valor total geral</td>
                                    <td colspan="8" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format((array_sum($total_pecas)+$mao_de_obra_adicional), 2, ',', '.') .'</td>
                                </tr>';
                $corpoMensagem .= '
                            </tbody>
                        </table>
                    <br>

                    <p>Estamos no aguardo da aprovação do orçamento para prosseguir com o reparo do produto.</p><br/>
                    <p><em>Caso aprovem o orçamento, solicitamos que façam deposito na conta abaixo e enviem a imagem do comprovante para o e-mail: sac@fluidra.com.br  junto com o numero da OS.</em></p><br>

                    Disponibilizamos também o pagamento através de cartão de crédito. Neste caso, solicitamos que entrem em contato pelo telefone (11) 3628-8969 e falem com o SAC.
                    <br><br>

                    Em caso de duvidas ficamos a disposição !!
                    <br>

                    <p>Atenciosamente</p>
                    <p>Fluidra</p>
                    ';

                $envio = $mailTc->sendMail(
                    $email,
                    $assunto,
                    $corpoMensagem,
                    'sac@fluidra.com.br'
                );

                return $envio;

        }
    }
    
}

$funcoes_fabrica[] = "grava_multiplas_solucoes_fluidra";

if(verificaTipoPosto("posto_interno","TRUE")){
    $funcoes_envia_email = ["enviaEmailSeparacaoEstoque","amarraClienteAdminOs","atualizaRefIdHDForOs"];
}

?>
