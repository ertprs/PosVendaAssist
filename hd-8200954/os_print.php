<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_fabrica == 1) {
    include("os_print_blackedecker.php");
    exit;
}

if ($login_fabrica == 14) {
    include("os_print_intelbras.php");
    exit;
}

if ($login_fabrica == 30) {
    include("os_print_esmaltec.php");
    exit;
}

include_once('funcoes.php');

if(in_array($login_fabrica,array(145,152,180,181,182))){
    $os = trim($_GET["os"]);

    if($login_fabrica == 145){
        $sql_tipo_os = "SELECT tbl_tipo_atendimento.grupo_atendimento
                        FROM tbl_os
                        LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                        WHERE tbl_os.os = {$os} AND tbl_os.fabrica = {$login_fabrica}";
    }else{
        $sql_tipo_os = "SELECT tbl_tipo_atendimento.tipo_atendimento FROM tbl_tipo_atendimento
                INNER JOIN tbl_os ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
            WHERE tbl_tipo_atendimento.fabrica = {$login_fabrica} AND tbl_os.os = {$os}
                AND tbl_tipo_atendimento.entrega_tecnica IS TRUE";
    }
    $res_tipo_os = pg_query($con, $sql_tipo_os);

    if(pg_num_rows($res_tipo_os) > 0){

        if($login_fabrica == 145){
            $grupo_atendimento = strtoupper(pg_fetch_result($res_tipo_os, 0, "grupo_atendimento"));
        }

        if($grupo_atendimento == "R" and $login_fabrica == 145){
            header("Location: os_print_revisao.php?os={$os}");
			exit;
        }elseif($login_fabrica == 145){
            header("Location: os_print_visita.php?os={$os}");
            exit;
        }else{
            header("Location: os_print_entrega_tecnica.php?os={$os}");
            exit;
        }

    }

}


if($login_fabrica == 134){
    $tema = "Serviço Realizado";
    $temaPlural = "Serviços Realizados";
    $temaMPlural = "SERVIÇOS REALIZADOS";
    $temaMaiusculo = "SERVIÇO REALIZADO";
}else{
    $tema = "Defeito Constatado";
    $temaPlural = "Defeitos Constatados";
    $temaMPlural = "DEFEITOS CONSTATADOS";
    $temaMaiusculo = "DEFEITO CONSTATADO";
}

$data_os = getValorFabrica([
	0   => traduz('data.abertura'),
	3   => 'Data de Entrada do Produto No Posto',
    101 => 'Entrada',
	104 => 'Data de Recebimento do Produto',
    157 => 'Data Entrada Prod Assist'
]);
$data_osMaiuscula = getValorFabrica([
	0   => mb_strtoupper(traduz('data.abertura')),
	3   => 'DATA DE ENTRADA DO PRODUTO NO POSTO',
    101 => 'ENTRADA',
	104 => 'DATA DE RECEBIMENTO DO PRODUTO',
    157 => 'DATA ENTRADA PROD ASSIST'
]);

$os              = intval($_GET['os']);
//HD 371911
$os              = (!$os && isset($os_include)) ? $os_include : $os;
$modo            = $_GET['modo'];
$qtde_etiquetas  = $_GET['qtde_etiquetas'];

if (in_array($login_fabrica, array(137,144,167,203))) {// Verifica se o posto é Interno

    $sql = "SELECT posto
            FROM tbl_posto_fabrica
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica AND tbl_tipo_posto.posto_interno
            WHERE tbl_posto_fabrica.fabrica = " . $login_fabrica . "
            AND tbl_posto_fabrica.posto = " . $login_posto;
    $res = pg_query($con,$sql);

    if( pg_num_rows($res) > 0) {

        $posto_interno = true;

    }else{

        $posto_interno = false;

    }

}

//Adicionando validação da OS para posto e fábrica
if (strlen($os)) {
    $sql = "SELECT os FROM tbl_os WHERE os=$os AND fabrica=$login_fabrica AND posto=$login_posto";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) == 0) {
        echo "OS não encontrada";
        die;
    }

    /**
     *
     * HD 739078 - latinatec: os em auditoria (aberta a mais de 60 dias) não pode consultar
     *
     */
    if ($login_fabrica == 15) {
        $os_bloq_tipo = '120,201, 122, 123, 126';
        $sqlStOs = "select status_os from tbl_os_status where status_os in ($os_bloq_tipo) and os = $os and fabrica_status = $login_fabrica order by data desc limit 1";
        $resStOs = pg_query($con, $sqlStOs);

        if (pg_num_rows($resStOs) > 0) {
            $status_atual = pg_result($resStOs, 0, 'status_os');
            if ($status_atual == 120) {
                echo '<div style="margin-top: 20px; color: #FF0000; font-weight: bold; text-align: center;">';
                    echo 'OS fora do prazo para fechamento.<br/><br/>';
                    echo '<input type="button" value=" Fechar " onClick="window.close()" />';
                echo '</div>';
                exit;
            }
        }
    }
}

if ($login_fabrica == 7) {
#   header ("Location: os_print_manutencao.php?os=$os&modo=$modo");
    header ("Location: os_print_filizola.php?os=$os&modo=$modo");
    exit;
}

#------------ Le OS da Base de dados ------------#
if (strlen ($os) > 0) {
    $col_serie = (isset($novaTelaOs)) ? 'tbl_os_produto.serie' : 'tbl_os.serie';
    $sql =  "SELECT tbl_os.sua_os                                                  ,
                    to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
                    to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                    tbl_produto.produto                                            ,
                    tbl_produto.referencia                                         ,
                    tbl_produto.parametros_adicionais AS parametros_adicionais_produto,
                    tbl_produto.preco AS produto_preco                             ,
                    tbl_produto.familia AS produto_familia                         ,
                    tbl_produto.referencia_fabrica                                 ,
                    tbl_produto.descricao                                          ,
                    tbl_produto.qtd_etiqueta_os                                    ,
                    tbl_os_extra.serie_justificativa                               ,
                                tbl_os_extra.hora_tecnica                          ,
                            tbl_os_extra.qtde_horas                                ,
                    tbl_defeito_reclamado.descricao AS defeito_cliente             ,
                    tbl_os.cliente                                                 ,
                    tbl_os.revenda                                                 ,
                    $col_serie                                                     ,
                    tbl_os.codigo_fabricacao                                       ,
                    tbl_os.consumidor_cpf                                          ,
                    tbl_os.consumidor_nome                                         ,
                    tbl_os.consumidor_fone                                         ,
                    tbl_os.consumidor_celular                                      ,
                    tbl_os.consumidor_fone_comercial AS consumidor_fonecom         ,
                    tbl_os.consumidor_email                                        ,
                    tbl_os.consumidor_endereco                                     ,
                    tbl_os.consumidor_numero                                       ,
                    tbl_os.consumidor_complemento                                  ,
                    tbl_os.consumidor_bairro                                       ,
                    tbl_os.consumidor_cep                                          ,
                    tbl_os.consumidor_cidade                                       ,
                    tbl_os.consumidor_estado                                       ,
                    tbl_os.revenda_cnpj                                            ,
                    tbl_os.obs                                                     ,
                    tbl_os.revenda_nome                                            ,
                    tbl_os.nota_fiscal                                             ,
                    tbl_os.qtde_km                                                 ,
                    to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf        ,
                    tbl_os.defeito_reclamado                                       ,
                    tbl_os.capacidade                                              ,
                    tbl_os.defeito_reclamado_descricao                             ,
                    tbl_os.acessorios                                              ,
                    tbl_os.aparencia_produto                                       ,
                    tbl_os.finalizada                                              ,
                    tbl_os.prateleira_box                                          ,
                    tbl_os.certificado_garantia                                    ,
                    tbl_os.cortesia                                                ,
                    tbl_os.contrato                                                ,
                    tbl_os.justificativa_adicionais                                ,
                    tbl_os.data_conserto                                           ,
                    tbl_os_extra.obs   as ponto_referencia                         ,
                    tbl_os_extra.obs_adicionais                                    ,
                    tbl_os.consumidor_nome_assinatura AS contato_consumidor,
                    tbl_os.condicao AS contador,
                    tbl_os_extra.inicio_atendimento                                    ,
                    tbl_os_extra.termino_atendimento                                    ,
                    tbl_os_extra.regulagem_peso_padrao                                    ,
                    tbl_posto.nome                                                 ,
                    tbl_os.rg_produto                                           ,
                    tbl_os.qtde_diaria,
                    tbl_posto_fabrica.contato_endereco   as endereco               ,
                    tbl_posto_fabrica.contato_numero     as numero                 ,
                    tbl_posto_fabrica.contato_bairro     as bairro                 ,
                    tbl_posto_fabrica.contato_cep        as cep                    ,
                    tbl_posto_fabrica.contato_cidade     as cidade                 ,
                    tbl_posto_fabrica.contato_estado     as estado                 ,
                    tbl_posto_fabrica.contato_fone_comercial as fone               ,
                    tbl_posto.cnpj                                                 ,
                    tbl_posto.ie                                                   ,
                    tbl_posto.pais                                                 ,
                    tbl_posto_fabrica.contato_email as email                                               ,
                    tbl_os.consumidor_revenda                                      ,
                    tbl_os.tipo_os,
                    tbl_os.tipo_atendimento                                        ,
                    tbl_os.tecnico_nome                                            ,
                    tbl_os.os_posto                                         ,
                    tbl_os.tecnico,
                    tbl_os.tecnico_nome,
                    tbl_tipo_atendimento.descricao              AS nome_atendimento,
                    tbl_tipo_atendimento.codigo                 AS codigo_atendimento,
                    tbl_os.qtde_produtos                                           ,
                    tbl_os.excluida                                                ,
                    tbl_defeito_constatado.descricao          AS defeito_constatado,
                    tbl_solucao.descricao                                AS solucao,
                    upper(tbl_linha.nome) AS linha,
                    tbl_os.qtde_hora,
                    tbl_os.hora_tecnica as os_hora_tecnica,
		    tbl_os.troca_garantia";

    if ($login_fabrica == 176)
    {
        $sql .= " , tbl_os.type ";
    }

    if(isset($novaTelaOs)){
        $join_cockpit = '';
        if ($login_fabrica == 158) {
            $sql .= ',tbl_hd_chamado_cockpit.dados as dadoscockpit';
            $join_cockpit = 'LEFT JOIN tbl_hd_chamado_cockpit ON tbl_os.fabrica = tbl_hd_chamado_cockpit.fabrica
                                AND tbl_hd_chamado_cockpit.hd_chamado = (SELECT hd_chamado FROM tbl_hd_chamado_extra WHERE os = tbl_os.os)';
        }

        $sql .= " FROM tbl_os
                    JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
                    JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                    JOIN tbl_posto USING (posto)
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                    LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                    LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado
                    LEFT JOIN tbl_defeito_constatado ON tbl_os_produto.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                    LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao
		    LEFT JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
		    {$join_cockpit}
                    WHERE tbl_os.os = $os
                    AND tbl_os.posto = $login_posto
                    ORDER BY tbl_os_produto ASC LIMIT 1";
    }else{
        $sql .= " FROM    tbl_os
                    JOIN    tbl_produto USING (produto)
                    JOIN    tbl_os_extra USING (os)
                    JOIN    tbl_posto   USING (posto)
                    JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                    LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                    LEFT JOIN tbl_defeito_reclamado USING (defeito_reclamado)
                    LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                    LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao
                    LEFT JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
                    WHERE   tbl_os.os = $os
                    AND     tbl_os.posto = $login_posto";
    }

    $res = pg_exec ($con,$sql);
    if (pg_numrows ($res) == 1) {
        $sua_os                         = pg_result($res,0,'sua_os');
        $data_abertura                  = pg_result($res,0,'data_abertura');
        $data_fechamento                = pg_result($res,0,'data_fechamento');
        $referencia                     = pg_result($res,0,'referencia');
        if ( !in_array($login_fabrica, array(7,11,15,172)) ) { 
            $box_prateleira =  trim(pg_result ($res,0,'prateleira_box'));
        }
        $codigo_lacre                   = pg_result($res,0,'codigo_fabricacao');
        $modelo                         = pg_result($res,0,'referencia_fabrica');
        $produto_referencia_fabrica     = pg_result($res,0,'referencia_fabrica');
        $produto                        = pg_result($res,0,'produto');
        $descricao                      = pg_result($res,0,'descricao');
        $serie                          = pg_result($res,0,'serie');
        $serie_justificativa            = pg_result($res,0,'serie_justificativa');
        $hora_tecnica                   = pg_fetch_result ($res,0,'hora_tecnica');
        $qtde_horas                     = pg_fetch_result ($res,0,'qtde_horas');
        $codigo_fabricacao              = pg_result($res,0,'codigo_fabricacao');
        $cliente                        = pg_result($res,0,'cliente');
        $revenda                        = pg_result($res,0,'revenda');
        $consumidor_cpf                 = pg_result($res,0,'consumidor_cpf');
        $consumidor_nome                = pg_result($res,0,'consumidor_nome');
        $consumidor_endereco            = pg_result($res,0,'consumidor_endereco');
        $consumidor_numero              = pg_result($res,0,'consumidor_numero');
        $consumidor_complemento         = pg_result($res,0,'consumidor_complemento');
        $consumidor_bairro              = pg_result($res,0,'consumidor_bairro');
        $consumidor_cidade              = pg_result($res,0,'consumidor_cidade');
        $consumidor_estado              = pg_result($res,0,'consumidor_estado');
        $consumidor_cep                 = pg_result($res,0,'consumidor_cep');
        $consumidor_referencia          = pg_result($res,0,'ponto_referencia');
        $consumidor_fone                = pg_result($res,0,'consumidor_fone');
        $consumidor_celular             = pg_result($res,0,'consumidor_celular');
        $consumidor_fonecom             = pg_result($res,0,'consumidor_fonecom');
        $consumidor_email               = strtolower(trim (pg_result($res,0,'consumidor_email')));
        $revenda_cnpj                   = pg_result($res,0,'revenda_cnpj');
        $revenda_nome                   = pg_result($res,0,'revenda_nome');
        $nota_fiscal                    = pg_result($res,0,'nota_fiscal');
        $data_nf                        = pg_result($res,0,'data_nf');
        $defeito_reclamado              = pg_result($res,0,'defeito_reclamado');
        $aparencia_produto              = pg_result($res,0,'aparencia_produto');
        $acessorios                     = pg_result($res,0,'acessorios');
        $defeito_cliente                = pg_result($res,0,'defeito_cliente');
        $defeito_reclamado_descricao    = pg_result($res,0,'defeito_reclamado_descricao');
        $posto_nome                     = pg_result($res,0,'nome');
        $posto_endereco                 = pg_result($res,0,'endereco');
        $posto_numero                   = pg_result($res,0,'numero');
        $posto_bairro                   = pg_result($res,0,'bairro');
        $posto_cep                      = pg_result($res,0,'cep');
        $posto_cidade                   = pg_result($res,0,'cidade');
        $posto_estado                   = pg_result($res,0,'estado');
        $posto_fone                     = pg_result($res,0,'fone');
        $posto_cnpj                     = pg_result($res,0,'cnpj');
        $posto_ie                       = pg_result($res,0,'ie');
        $posto_email                    = pg_result($res,0,'email');
        $sistema_lingua                 = strtoupper(trim(pg_result($res,0,'pais')));
        $consumidor_revenda             = pg_result($res,0,'consumidor_revenda');
        $obs                            = pg_result($res,0,'obs');
        $finalizada                     = pg_result($res,0,'finalizada');
        $data_conserto                  = pg_result($res,0,'data_conserto');
        $qtde_produtos                  = pg_result($res,0,'qtde_produtos');
        $excluida                       = pg_result($res,0,'excluida');
        $tipo_atendimento               = trim(pg_result($res,0,'tipo_atendimento'));
        $tecnico_nome                   = trim(pg_result($res,0,'tecnico_nome'));
        $qtde_diaria                    = pg_fetch_result($res, 0, "qtde_diaria");
        $serie_reoperado		        = pg_fetch_result($res,0,'serie_reoperado');
        $embalagem_original		        = pg_fetch_result($res, 0, 'embalagem_original');
        $inicio_atendimento		        = pg_fetch_result($res, 0, 'inicio_atendimento');
        $termino_atendimento		    = pg_fetch_result($res, 0, 'termino_atendimento');
        $regulagem_peso_padrao		    = pg_fetch_result($res, 0, 'regulagem_peso_padrao');
	    $troca_garantia			        = pg_fetch_result($res,0,'troca_garantia');

        /*
        if ($login_fabrica == 178){
            $produto_preco = pg_fetch_result($res, 0, 'produto_preco');
            $produto_familia = pg_fetch_result($res, 0, 'produto_familia');

            $parametros_adicionais_produto  = pg_fetch_result($res, 0, "parametros_adicionais_produto");
            $parametros_adicionais_produto  = json_decode($parametros_adicionais_produto, true);

            $fora_linha = $parametros_adicionais_produto["fora_linha"];
            $marcas     = $parametros_adicionais_produto["marcas"];

            if ($fora_linha == "true"){
                $marcas = explode(",", $marcas);

                unset($cond_marca);
                foreach ($marcas as $key => $value) {
                    if ($key > 0 ){
                        $cond_marca .= " OR parametros_adicionais ILIKE '%$marcas[$key]%'";
                    }else{
                        $cond_marca .= "parametros_adicionais ILIKE '%$marcas[$key]%'";
                    }
                }
                
                $preco_base = $produto_preco + ($produto_preco/100 *20);
                    
                if (!empty($produto_familia)){
                    $sqlProd = "
                        SELECT produto, referencia, descricao, preco
                        FROM tbl_produto
                        WHERE fabrica_i = {$login_fabrica}
                        AND produto NOT IN ($produto)
                        AND $cond_marca
                        AND lista_troca = 't'
                        AND familia = $produto_familia
                        ORDER BY descricao ASC ";
                    $resProd = pg_query($con, $sqlProd);
                    
                    if (pg_num_rows($resProd) > 0){
                        $produtos = array();

                        for ($y=0; $y < pg_num_rows($resProd); $y++) { 
                            $preco_produto_troca = pg_fetch_result($resProd, $y, "preco");
                            if (($preco_produto_troca >= $produto_preco AND $preco_produto_troca < $preco_base)){
                                $produtos[] = array(
                                    "referencia" => pg_fetch_result($resProd, $y, "referencia").' - '.substr(pg_fetch_result($resProd, $y, "descricao"), 0, 30)
                                );
                                $contador = count($produtos);
                                if ($contador == 3){
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        */
        if (in_array($login_fabrica, array(156))) {
            $void = $serie_reoperado;
            $sem_ns = $embalagem_original;
        }

        $os_posto = pg_fetch_result($res, 0, "os_posto");

        if ($login_fabrica == 143) {
            $rg_produto = pg_fetch_result($res, 0, "rg_produto");
        }

        if ($login_fabrica == 175){
            $qtde_disparos = pg_fetch_result($res, 0, 'capacidade');
        }

        if(in_array($login_fabrica, [167, 203])){
            $contato_consumidor = pg_fetch_result($res, 0, 'contato_consumidor');
            $contador = pg_fetch_result($res, 0, 'contador');
        }

        if ($login_fabrica == 148) {
            $os_horimetro = pg_fetch_result($res, 0, "qtde_hora");
            $os_revisao = pg_fetch_result($res, 0, "os_hora_tecnica");

            $obs_adicionais_json = json_decode(pg_fetch_result($res, 0, "obs_adicionais"));

            $serie_motor       = $obs_adicionais_json->serie_motor;
            $serie_transmissao = $obs_adicionais_json->serie_transmissao;
        }

        if($login_fabrica == 137){

            $dados  = pg_result($res,0,rg_produto);

            $dados          = json_decode($dados);
            $cfop           = $dados->cfop;
            $valor_unitario = $dados->vu;
            $valor_nota     = $dados->vt;

        }

        if($login_fabrica == 158){
            $dadoscockpit = json_decode(pg_result($res,$i,dadoscockpit), true);
        }

        if (in_array($login_fabrica, array(169,170))) {
            $produto_emprestimo = pg_fetch_result($res, 0, contrato);
            $recolhimento = pg_fetch_result($res, 0, recolhimento);
            $revenda_contato = pg_fetch_result($res, 0, contato_consumidor);
            $justificativa_adicionais = pg_fetch_result($res, 0, justificativa_adicionais);
            $justificativa_adicionais = json_decode($justificativa_adicionais, true);

            if (isset($justificativa_adicionais["motivo_visita"])) {
                $motivo_visita = utf8_decode($justificativa_adicionais["motivo_visita"]);
            }
        }

        if ($login_fabrica == 176)
        {
            $indice = pg_fetch_result($res, 0, type);
        }

        $tecnico                        = trim(pg_fetch_result($res,0,tecnico));
        $nome_atendimento               = trim(pg_result($res,0,'nome_atendimento'));
        $codigo_atendimento               = trim(pg_result($res,0,'codigo_atendimento'));
        $defeito_constatado             = trim(pg_result($res,0,'defeito_constatado'));
        $solucao                        = trim(pg_result($res,0,'solucao'));
        $qtd_etiqueta_os                = trim(pg_result($res,0,'qtd_etiqueta_os'));
        $tipo_os                        = trim(pg_result($res,0,'tipo_os'));
        $qtde_km                        = trim(pg_result($res,0,'qtde_km'));
        $certificado_garantia           = trim(pg_result($res,0,'certificado_garantia'));
        $certificado_garantia = ($certificado_garantia AND $certificado_garantia != "null") ? "$certificado_garantia" : "";
        $cortesia                   = pg_result($res,0,cortesia);
        $cortesia = ($cortesia == "t") ? "Sim" : "Não";
        $os_de_garantia = (strlen($certificado_garantia) > 0 AND $certificado_garantia != "null") ? "Sim" : "Não";

        $obs_adicionais                 = json_decode(utf8_encode(pg_result ($res,0,'obs_adicionais')),true);
        $linha                          = trim(pg_result($res,0,'linha'));

        $qtde_km = (empty($qtde_km)) ? 0 : $qtde_km;

        if (strlen($sistema_lingua) == 0)
            $sistema_lingua = 'BR';
        if ($sistema_lingua <>'BR') {
            $lingua = "ES";
        } else {
            $lingua = "BR";
        }

        if (in_array($login_fabrica, array(169,170))){
            $sql_tecnico = "
                SELECT tbl_tecnico.nome,
                    to_char(tbl_tecnico_agenda.data_agendamento, 'DD/MM/YYYY') AS data_agendamento
                FROM tbl_tecnico_agenda
                JOIN tbl_tecnico using(tecnico)
                WHERE os = $os
                AND confirmado IS NOT NULL ORDER BY ordem DESC LIMIT 1";
            $res_tecnico = pg_query($con,$sql_tecnico);
            $data_agendamento = pg_fetch_result($res_tecnico, 0, 'data_agendamento');
            $tecnico_nome_midea = pg_fetch_result($res_tecnico,0,nome);
        }

        if (strlen($tecnico) > 0) {
            $sql = "SELECT nome FROM tbl_tecnico WHERE tecnico= {$tecnico};";
            $res_tecnico = pg_query($con, $sql);

            if (pg_num_rows($res_tecnico)) {
                $tecnico_nome = pg_result($res_tecnico, 0, nome);
            }
        }

        $Dias['BR']     = array(0 => "Domingo",     "Segunda-feira","Terça-feira",
                                     "Quarta-feira","Quinta-feira", "Sexta-feira",
                                     "Sábado",      "Domingo");
        $Dias['ES']     = array(0 => "Domingo", "Lunes",    "Martes", "Miércoles",
                                     "Jueves",  "Viernes",  "Sábado" );
        $meses['BR']    = array(1 => "Janeiro", "Fevereiro","Março",    "Abril",
                                     "Maio",    "Junho",    "Julho",    "Agosto",
                                     "Setembro","Outubro",  "Novembro", "Dezembro");
        $meses['ES']    = array(1 => "Enero",     "Febrero","Marzo",    "Abril",
                                     "Mayo",      "Junio",  "Julio",    "Agosto",
                                     "Septiembre","Octubre","Noviembre","Diciembre");

        if (strlen($qtde_etiquetas) > 0 AND $qtde_etiquetas > 0) {
            $qtd_etiqueta_os = $qtde_etiquetas;
        } else {
            if (strlen($qtd_etiqueta_os) == 0) {
                $qtd_etiqueta_os = ($login_fabrica == 59) ? 2 : 5;
            }
        }

        if (in_array($login_fabrica, array(2,20,46,91,115,116,117,120,201,123,124,125,126,127,128,129,131,134,136,138))) {

            $cond_left = (in_array($login_fabrica, array(20))) ? " LEFT " : "";

            $sql_item = "SELECT tbl_os_item.peca                              ,
                tbl_peca.referencia             AS peca_referencia            ,
                tbl_peca.referencia_fabrica             AS peca_referencia_fabrica            ,
                tbl_peca.descricao              AS peca_descricao             ,
                tbl_os_item.qtde                AS peca_qtde                  ,
                tbl_os_item.defeito                                           ,
                tbl_defeito.descricao           AS  descricao_defeito         ,
                tbl_os_item.servico_realizado                                 ,
                tbl_servico_realizado.descricao AS  descricao_servico_realizado
                FROM tbl_os_item
                JOIN tbl_os_produto USING(os_produto)
                JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
                LEFT JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_item.defeito AND tbl_defeito.fabrica = $login_fabrica
                {$cond_left} JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
                JOIN tbl_os ON tbl_os.os = tbl_os_produto.os where tbl_os.os = $os";
            $res_item = pg_query($con, $sql_item);

            if (pg_num_rows($res_item) > 0) {

                if(!in_array($login_fabrica,array(20,46,115,116,117,123,124,125,126,127,128,129,131,134,136,138))){

                $topo_peca = "
                    <TABLE  width='596' border='0' cellspacing='0' cellpadding='0'>
                        <TR>
                            <TD colspan='4'><BR></TD>
                        </TR>
                    </TABLE>";
                }
                $topo_peca .= "<TABLE class='borda'  width='596' border='0' cellspacing='0' cellpadding='0'>
                    <TR>
                        <TD class='titulo'>".traduz("peca")."</TD>
                        <TD class='titulo'><center>".traduz("quantidade")."</center></TD>";

                        if(!in_array($login_fabrica,array(20,46,115,116,117,123,124,125,126,127,128,129,131,134,136,138))){

                            $topo_peca .= "<TD class='titulo'>".traduz("defeito")."</TD>";
                        }
                        $topo_peca .= "<TD class='titulo'>".traduz("servico")."</TD>
                    </TR>";

                    for ($z = 0; $z < pg_numrows($res_item); $z++) {
                        $peca                        = pg_result($res_item, $z, peca);
                        $peca_referencia             = pg_result($res_item, $z, peca_referencia);
                        $peca_referencia_fabrica             = pg_result($res_item, $z, peca_referencia_fabrica);
                        $peca_descricao              = pg_result($res_item, $z, peca_descricao);
                        $peca_qtde                   = pg_result($res_item, $z, peca_qtde);
                        $descricao_defeito           = pg_result($res_item, $z, descricao_defeito);
                        $descricao_servico_realizado = pg_result($res_item, $z, descricao_servico_realizado);

                        if(in_array($login_fabrica, array(20))){
                            $sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = UPPER('$cook_idioma') ";

                            $res_idioma = pg_query($con,$sql_idioma);
                            if (pg_num_rows($res_idioma) >0) {
                                $peca_descricao  = trim(pg_fetch_result($res_idioma, 0, "descricao"));
                            }
                        }

                        $peca_dynacom .= "<TR>";
                            $peca_dynacom .= "<TD class='conteudo'>$peca_referencia - ".substr($peca_descricao,0,25)."</TD>";
                            $peca_dynacom .= "<TD class='conteudo'><center>$peca_qtde</center></TD>";


                            if(!in_array($login_fabrica,array(20,46,115,116,117,123,124,125,126,127,128,129,131,134,136,138,143))){

                                $peca_dynacom .= "<TD class='conteudo'>$descricao_defeito</TD>";
                            }
                            $peca_dynacom .= "<TD class='conteudo'>$descricao_servico_realizado</TD>";
                        $peca_dynacom .= "</TR>";
                    }
                $peca_dynacom .= "  </TABLE>";
            }
        }

        //--=== Tradução para outras linguas ============================= Raphael HD:1212
        if ((strlen(trim($produto)) > 0) and (strlen(trim($lingua))> 0)) {
            $sql_idioma = " SELECT * FROM tbl_produto_idioma
                            WHERE produto     = $produto
                            AND upper(idioma) = '$lingua'";
            $res_idioma = @pg_exec($con,$sql_idioma);

            if (@pg_numrows($res_idioma) >0) {
                $descricao  = trim(@pg_result($res_idioma,0,descricao));
            }
        }

        if ((strlen(trim($defeito_reclamado))>0) and (strlen(trim($lingua))>0)) {
            $sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
                            WHERE defeito_reclamado = $defeito_reclamado
                            AND upper(idioma)        = '$lingua'";
            $res_idioma = pg_exec($con,$sql_idioma);

            if (pg_numrows($res_idioma) >0) {
                $defeito_cliente  = trim(@pg_result($res_idioma,0,descricao));
            }
        }

        if ((strlen(trim($tipo_atendimento))>0) and (strlen(trim($lingua))>0)) {
            $sql_idioma = " SELECT * FROM tbl_tipo_atendimento_idioma
                    WHERE tipo_atendimento = '$tipo_atendimento'
                    AND upper(idioma)   = '$lingua'";

            $res_idioma = @pg_exec($con,$sql_idioma);

            if (@pg_numrows($res_idioma) > 0) {
                $nome_atendimento  = trim(@pg_result($res_idioma,0,descricao));
            }

        }

        //--=== Tradução para outras linguas ================================================

        if (strlen($revenda) > 0) {

            $sql = "SELECT  tbl_revenda.endereco   ,
                            tbl_revenda.numero     ,
                            tbl_revenda.complemento,
                            tbl_revenda.bairro     ,
                            tbl_revenda.cep
                    FROM    tbl_revenda
                    WHERE   tbl_revenda.revenda = $revenda;";

            $res1 = pg_exec ($con,$sql);

            if (pg_numrows($res1) > 0) {
                $revenda_endereco    = strtoupper(trim(pg_result($res1,0,endereco)));
                $revenda_numero      = trim(pg_result($res1,0,numero));
                $revenda_complemento = strtoupper(trim(pg_result($res1,0,complemento)));
                $revenda_bairro      = strtoupper(trim(pg_result($res1,0,bairro)));
                $revenda_cep         = trim(pg_result($res1,0,cep));
                $revenda_cep         = substr($revenda_cep,0,2) .".". substr($revenda_cep,2,3) ."-". substr($revenda_cep,5,3);
            }

        }

        if(in_array($login_fabrica, array(138))){

            $sql = "SELECT COUNT(os_produto) FROM tbl_os_produto WHERE os = $os";
            $res2 = pg_query($con,$sql);
            $coun_os_produto = pg_fetch_result($res2, 0, 0);

            if($coun_os_produto > 1){

                $sql = "SELECT referencia,descricao,serie
                        FROM tbl_produto
                        JOIN tbl_os_produto ON tbl_os_produto.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
                        WHERE tbl_os_produto.os = $os
                        ORDER BY tbl_os_produto.os_produto DESC
                        LIMIT 1";
                $res2 = pg_query($con,$sql);

                $referencia_subproduto = pg_fetch_result($res2, 0, 'referencia');
                $descricao_subproduto  = pg_fetch_result($res2, 0, 'descricao');
                $serie_subproduto      = pg_fetch_result($res2, 0, 'serie');

            }

        }

    }

    $sql = "UPDATE tbl_os_extra SET impressa = current_timestamp WHERE os = $os;";
    $res = pg_exec($con,$sql);

}

function convertDataBR($data){
    $dt = explode('-',$data);

    return $dt[2].'/'.$dt[1].'/'.$dt[0];
}

if (strlen($sua_os) == 0) $sua_os = $os;

$title = traduz("ordem.de.servico.balcao.impressao");
//echo "$qtde_produtos";?>
<html>
<head>
    <title><? echo $title ?></title>
    <meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
    <meta http-equiv="Expires"       content="0">
    <meta http-equiv="Pragma"        content="no-cache, public">
    <meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
    <meta name      ="Author"        content="Telecontrol Networking Ltda">
    <meta name      ="Generator"     content="na mão...">
    <meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
    <meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">
    <link type="text/css" rel="stylesheet" href="css/css_press.css">
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <style type="text/css">
        body {
            margin: 1em;
        }
        .titulo {
            font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
            text-align: left;
            color: #000000;
            background: #D0D0D0;
            border-bottom: dotted 1px #a0a0a0;
            border-right: dotted 1px #a0a0a0;
            border-left: dotted 1px #a0a0a0;
            padding: 1px,1px,1px,1px;
            <?
                if ($login_fabrica==85){
            ?>
                    font-family: Arial;
                    font-size: 8pt;
            <?
                }else if(in_array($login_fabrica, [167, 203])){
            ?>
                font-size: 08pt;
            <?php
                }else{
            ?>
                font-size: 07px;
            <?
            }
            ?>
        }
        .titulo_destaque{
            font-size: 9px;
            text-align: left;
            color: #000000;
            background: #D0D0D0;
            border-bottom: dotted 1px #a0a0a0;
            border-right: dotted 1px #a0a0a0;
            border-left: dotted 1px #a0a0a0;
            padding: 1px,1px,1px,1px;
        }
        .texto{
            font-size: 12px;
            font: arial;
            background: #ffffff;
            padding: 1px,1px,1px,1px;
            text-align: justify;
        }
        .assinatura{
            border: 1px solid;
            width: 200px;
            text-align: left;
            display: inline-block;
        }

        .data_entrada{
            border: 1px solid;
            width: 100px;
            text-align: left;
            display: inline-block;
        }
        .texto_termos{
            width: 600px;
            margin-top:3px;

        }

        .texto_termos p{
            font: 7px 'Arial' !important;
            text-align: justify;
            margin: 0 0 5px 0;
        }

        .conteudo {
            <?
                if ($login_fabrica==3){
            ?>
            font: 10px Arial;
            <?
                }elseif ($login_fabrica==85){
            ?>
            font: 8pt Arial;
            background: #EEEEEE;
<?
                }else if(in_array($login_fabrica, [167, 203])){
?>
                font: 09px Arial;

<?
                } else if (in_array($login_fabrica,array(91,158))) {
?>
                font: 13px Arial;
<?
                } else {
?>
                font: 8px Arial;
<?
                }
?>
            text-align: left;
            background: #ffffff;
            border-right: dotted 1px #a0a0a0;
            border-left: dotted 1px #a0a0a0;
        }

        .conteudo_destaque {
            font-size: 9px;
            text-align: left;
            background: #ffffff;
            border-right: dotted 1px #a0a0a0;
            border-left: dotted 1px #a0a0a0;
            padding: 1px,1px,1px,1px;
        }

        td.conteudo ul li {
            list-style: square inside;
        }

        .conteudo2 {
            font-size: 8px;
            font-family: Arial;
        }

        .titulo2{
            font-size: 8px;
            font-weight: bold;
            font-family: Arial;
            border-bottom: 1px solid #000000;
            text-align:center;
            background-color: #cccccc;
        }

        .borda {
            border: solid 1px #c0c0c0;
        }

        .etiqueta {

            <?
            if ($login_fabrica == 59){
                ?>
                font: bold 80% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
                <?
            }else{
                ?>
                font: 52% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
                <?
            }
            ?>
            color: #000000;
            text-align: center
        }

/*        @media print {
            
            html {
                -moz-transform       : scale( 0.8, 0.8);
                -moz-transform-origin: top left;
            }
               
        }*/

        h2 {
            font: 60% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
            color: #000000
        }
        <? if ($login_fabrica == 85) { ?>
        table[width="600"] {
            width: 100%;
        }
        <? } ?>
            }
    </style>
    <style type='text/css' media='print' >
        body {
            margin: 0px;
        }
        .noPrint {display:none;}
    </style><?php

    // HD 34292
    if ($login_posto == '14236' or $login_posto == '2498'){?>
        <style type="text/css">
            .titulo {
                font-size: 9px;
                border-bottom-style: solid;
                border-left-style: solid;
            }

            .conteudo {
                font-size: 11px;
                border-bottom-style: solid;
                border-left-style: solid;
            }
        </style><?php
    }?>
</head><?php
/*OS GEO METAIS*/

if ($login_fabrica == 178 AND $consumidor_revenda == "S"){
    $consumidor_revenda = "CONSTRUTORA";
}

if ($login_fabrica == "1" and $tipo_os == "13") {
    $consumidor_revenda = 'OS GEO';
} else {
    if ($consumidor_revenda == 'R')
        $consumidor_revenda = 'REVENDA';
    else
        if ($consumidor_revenda == 'C')
            $consumidor_revenda = ($login_fabrica == 122) ? 'CLIENTE' : 'CONSUMIDOR';
}?>
<body>

<?php
//HD 371911
if(!isset($os_include)):?>
    
    <?php if ($login_fabrica < 177){ // Autorizado pelo Waldir 22/08/2018 ?>
    <div class='noPrint'>
        <input  type=button name='fbBtPrint' value='Versão Matricial' onclick="window.location='os_print_matricial.php?os=<? echo $os; ?>'" />
        <br />
        <hr class='noPrint' />
    </div>
    <?php } ?>
<?php endif;?>

<TABLE width="600" border="0" cellspacing="0" cellpadding="0">

    <?php
    if($login_fabrica == 52){
        ?>
            <tr>
                <td colspan="4" align="right">
                    <strong style="font: 14px arial; font-weight: bold;"><?= traduz("via.do.consumidor") ?></strong>
                </td>
            </tr>
            <TR class="conteudo">
                <TD>
                    <?php
                        // $img_contrato = 'logos/';
                        // $img_contrato .= 'cabecalho_print_' . strtolower($login_fabrica_nome) . '.gif';
                    $img_contrato = "logos/logo_fricon.jpg";
                    ?>
                    <IMG SRC="<? echo ($img_contrato); ?>" height="40" ALT="ORDEM DE SERVIÇO">
                </TD>
                <td align="center">
                    <strong><?= traduz("posto.autorizado") ?></strong> <br />
                    <?php
                        echo ($posto_nome != "") ? $posto_nome."<br />" : "";
                        echo ($posto_endereco != "") ? $posto_endereco : "";
                        echo ($posto_numero != "") ? $posto_numero.", " : "";
                        echo ($posto_bairro != "") ? $posto_bairro.", " : "";
                        echo ($posto_cep != "") ? " <br /> CEP: ".$posto_cep." " : "";
                        echo ($posto_cidade != "") ? $posto_cidade." - " : "";
                        echo ($posto_estado != "") ? $posto_estado : "";
                    ?>
                </td>
                <td align="center" class="borda" style="padding: 5px;">
                    <strong><?= traduz("data.emissao") ?></strong> <br />
                    <?=date("d/m/Y");?>
                </td>
                <td align="center" class="borda" style="padding: 5px;">
                    <strong><?= traduz("numero.os") ?></strong> <br />
                    <?=$os;?>
                </td>
            </TR>
        <?php
    }else{
        ?>
            <TR style="text-align: center;"> <?php
                $img_contrato = 'logos/';

                if ($login_fabrica == 3) {
                    $sql = "SELECT logo
                            from tbl_marca
                            join tbl_produto using(marca)
                            where tbl_marca.fabrica = $login_fabrica
                            and tbl_produto.produto = $produto";
                    $res = pg_exec($con,$sql);

                    if (pg_numrows($res) > 0) {
                        $logo = pg_result($res,0,0);
                        if ($logo <> 'britania.jpg') {
                            $img_contrato .= $logo;
                        } else {
                            $img_contrato .= "cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
                        }
                    }
                } else {
                    $array_logos = array(
                        20 => 'bosch.jpg',
                        85 => 'gelopar.png',
                        72 => 'mallory.png',
                        51 => 'cabecalho_print_gamaitaly.gif',
                        88 => 'orbisdobrasil.jpg',
                        89 => 'Daiken.gif',
                        90 => 'logo_ibbl.jpg',
                        91 => 'logomarca_wanke.gif'
                    );

                    if (in_array($login_fabrica, $array_logos)):
                        $img_contrato .= $array_logos[$login_fabrica];
                    elseif (in_array($login_fabrica, array(40,80,81))):
                        $img_contrato .= strtolower($login_fabrica_nome) . '.gif';
                    else:
                        $img_contrato .= 'cabecalho_print_' . strtolower($login_fabrica_nome) . '.gif';
                    endif;
                }

                if(isset($novaTelaOs)){
                    if (in_array($login_fabrica, array(175))){
                        $img_contrato = 'logos/logo_'.strtolower($login_fabrica_nome).".png";
                    }else{
                        $img_contrato = 'logos/logo_'.strtolower($login_fabrica_nome).".jpg";
                    }
                }

                if($login_fabrica == 20){
                    $img_contrato = 'logos/cabecalho_print_bosch.jpg';
                }


                if($login_fabrica == 35){
                    $img_contrato = 'logos/logo_cadence_new.png';
                }

                /*HD - 6164934*/
                if ($login_fabrica == 80) {
                    $img_contrato = "logos/logo_amvox.jpeg";
                }

                if ($login_fabrica == 144) {
                    $img_contrato = "logos/logo_hikari.jpg";
                }

                ?>
                <TD rowspan="2" style="text-align: left;">
                    <?php if ($login_fabrica == 11): #HD 891549 ?>
                        <label style="font:bold 09px Arial;">Aulik Ind. e Com. Ltda.</label>
                    <?php else:
                        $height_britania = ($login_fabrica == 3) ? 30 : 40 ;
                        if(file_exists($img_contrato)) {
                            if($login_fabrica == 20){
                        ?>
                                <IMG SRC="<? echo $img_contrato; ?>" HEIGHT='<?=$height_britania?>' ALT="ORDEM DE SERVIÇO">
                    <?php
                            }else{
                                if ($_serverEnvironment == "development") {
                                    echo '<IMG SRC="'.$img_contrato.'" HEIGHT="'.$height_britania.'" ALT="ORDEM DE SERVIÇO" width="240">';
                                } else {
                                    echo '<IMG SRC="https://posvenda.telecontrol.com.br/assist/'.$img_contrato.'" HEIGHT="'.$height_britania.'" ALT="ORDEM DE SERVIÇO" width="240">';
                                }
                 
                            }
                        }
                        endif ?>
                </TD>
                <?php 
                    if ($login_fabrica == 175){
                        include_once "class/tdocs.class.php";
                        $amazonTC = new TDocs($con, 10);
                        $documents = $amazonTC->getdocumentsByRef($login_posto, 'logomarca_posto')->attachListInfo;
                        if (count($documents) > 0){
                            foreach ($documents as $key => $value) {
                                $link_logo_tdocs = $value['link'];
                            }
                        }
                        if (!empty($link_logo_tdocs)){
                ?>
                            <TD rowspan="2" style="padding-top: 5px; padding-left: 14px; padding-bottom: 5px;">
                                <IMG SRC="<?=$link_logo_tdocs?>" height="60">
                            </TD>
                <?php            
                        }
                    }
                ?>
                <TD style="font-size: 08px;">
                    <? if ($sistema_lingua <> 'BR') {
                        echo "<font size=-2> SERVICIO AUTORIZADO";
                    }else{
                        if ($login_fabrica <> 3){

                            if(in_array($login_fabrica, array(169,170))) {
                                echo "<img src='logos/logo_midea_blue.png' alt='Midea' style='height:22px; width:120px;'><br>";
                            }

                            echo "POSTO AUTORIZADO </font><BR>";
                        }
                        echo substr($posto_nome,0,30);
                    }
                        ?>
                    </TD>
                <TD style="font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 07px;"><? if ($sistema_lingua<>'BR') echo "FECHA EMISIÓN"; else echo "DATA EMISSÃO"?></TD>
                <TD style="font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 07px;"><? if ($sistema_lingua<>'BR') echo "NÚMERO"; else echo "NÚMERO OS";?></TD>
            </TR>
            <TR style="font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 07px;">
                <TD style="font-size: 09px; text-align: center; width: 350px; "><?php
                    ########## CABECALHO COM DADOS DO POSTOS ##########
                    echo $posto_endereco .",".$posto_numero." - CEP ".$posto_cep."<br>";
                    echo $posto_cidade ." - ".$posto_estado." - Telefone: ".$posto_fone."<br>";
                    if ($login_fabrica == 3){
                        # HD 30788 - Francisco Ambrozio (11/8/2008)
                        # Adicionado email do posto para Britânia
                        echo "Email: ".$posto_email."<br>";
                    }
                    if ($sistema_lingua<>'BR') echo "ID1 ";
                    else                       echo "CNPJ/CPF ";
                    echo $posto_cnpj;
                    if ($sistema_lingua<>'BR') echo " - ID2";
                    else                       echo " - IE/RG ";
                    echo $posto_ie;?>
                </TD>
                <TD style="border: 1px solid #a0a0a0; font-size: 10px;"><?php
                    ########## DATA DE ABERTURA ##########?>
                    <b><? echo $data_abertura ?></b>
                </TD>
                <TD style="border: 1px solid #a0a0a0;"><?php
                    ########## SUA OS ##########
                    if (strlen($consumidor_revenda) == 0) {
                        echo "<center><b> <span style='font-size: 14px;'> $sua_os </span></b></center>";
                    } else {
                        echo "<center><b> <span style='font-size: 14px;'> $sua_os </span><br> $consumidor_revenda  </b></center>";
                    }?>
                </TD>
            </TR>

            <?php if($login_fabrica == 124){ ?>
            <tr>
                <td colspan="4" style="text-align:center; font-size:11px;"><?= traduz("prezado.consumidor.o.acompanhamento.da.sua.ordem.de.servico.podera.ser.realizado.atraves.do.site") ?> <a href="HTTP://WWW.GAMMAFERRAMENTAS.COM.BR" target="_blank">WWW.GAMMAFERRAMENTAS.COM.BR</a> </td>
            </tr>

            <?php }

            if($login_fabrica == 3){ ?>
            <tr>
                <td colspan="4" style="text-align:center; font-size:11px;"><?= traduz("prezado.consumidor.o.acompanhamento.da.sua.ordem.de.servico.podera.ser.realizado.atraves.do.site") ?> <a href="http://www.britania.com.br/" target="_blank">http://www.britania.com.br/</a> </td>
            </tr>

            <?php }
            if($login_fabrica == 35){ ?>
                <tr>
                    <td colspan="4" style="font: 10px Arial !important; text-align:center">
                        <?= traduz("para.consultar.o.status.da.sua.ordem.de.servico.aberta.em.uma.de.nossas.assistencias.tecnicas.favor.acessar") ?> <a href="http://www.cadence.com.br" target="_blank">www.cadence.com.br</a> <?= traduz("e.informar.o.numero.da.ordem.de.servico.e.seu.cpf") ?>.
                    </td>
                </tr>
            <?php }

             ?>

        <?php
    }
    ?>

</TABLE>

<?php

if (($login_fabrica == 1) || ($login_fabrica == 19)) $colspan = 6;
else $colspan = 5;

if ($login_fabrica == 11) {
    echo "<TABLE width='600' border='0' cellspacing='0' cellpadding='0'>";
        echo "<TR><TD align='left'><font face='arial' size='1px'>via do cliente</font></TD></TR>";
    echo "</TABLE>";
}?>

<TABLE class="borda" width="600" border="1" cellspacing="0" cellpadding="0"><?php
    if ($excluida == "t") {?>
        <TR>
            <TD colspan="<? echo $colspan ?>" bgcolor="#FFE1E1" align="center"><h1><?= traduz("ordem.de.servico.excluida") ?></h1></TD>
        </TR><?php
    }?>
    <TR>
        <TD class="titulo" colspan="<? echo $colspan ?>"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre la ordem de servicio"; else echo "Informações sobre a Ordem de Serviço";?></TD>
    </TR><?php
    if ($login_fabrica == 50) {
        $sql_status = "SELECT
            status_os,
            observacao,
            tbl_admin.login,
            to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
            FROM tbl_os_status
            LEFT JOIN tbl_admin USING(admin)
            WHERE os=$os
            AND status_os IN (98,99,100,101,102,103,104)
            ORDER BY data DESC LIMIT 1";

        $res_status = pg_exec($con,$sql_status);
        $resultado  = pg_numrows($res_status);

        if ($resultado == 1) {

            $data_status        = trim(pg_result($res_status,0,data));
            $status_os          = trim(pg_result($res_status,0,status_os));
            $status_observacao  = trim(pg_result($res_status,0,observacao));
            $intervencao_admin  = trim(pg_result($res_status,0,login));

            if ($status_os == 98 or $status_os == 99 or $status_os == 100 or $status_os == 101 or $status_os == 102 or $status_os == 103 or $status_os == 104) {
                $sql_status = "select descricao from tbl_status_os where status_os = $status_os";
                $res_status = pg_exec($con, $sql_status );

                if (pg_numrows($res_status) > 0)
                    $descricao_status = pg_result($res_status, 0, 0);
                echo "<TR>";
                    echo "<TD class='titulo'>".traduz("data")." &nbsp;</TD>";
                    echo "<TD class='titulo'>ADMIN &nbsp;</TD>";
                    echo "<TD class='titulo'>STATUS &nbsp;</TD>";
                    echo "<TD class='titulo' colspan='2'>".traduz("motivo")." &nbsp;</TD>";
                echo "</TR>";
                echo "<TR>";
                    echo "<TD class='conteudo' width='10%'> $data_status </TD>";
                    echo "<TD class='conteudo'>&nbsp;$intervencao_admin </TD>";
                    echo "<TD class='conteudo'>&nbsp;$descricao_status </TD>";
                    echo "<TD class='conteudo' colspan='2'>&nbsp;$status_observacao </TD>";
                echo "</TR>";
            }
        }
    }?>

    <?php

    if ($login_fabrica == 148) {
        $colspanDataAbertura = 3;
    }

    if (in_array($login_fabrica, array(143,167,175,177,203))) {
        $colspanDataAbertura = 2;
    }

     if(in_array($login_fabrica, array(20,104))){
            $select_os_remanufatura = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
            $res_os_remanufatura = pg_query($con, $select_os_remanufatura);

            if (pg_num_rows($res_os_remanufatura) > 0) {
                $json_os_remanufatura = json_decode(pg_fetch_result($res_os_remanufatura, 0, "campos_adicionais"), true);

                if($login_fabrica == 104){
                    $data_recebimento_produto      = $json_os_remanufatura["data_recebimento_produto"];
                }

                if($login_fabrica == 20){
                    $motivo_ordem      = $json_os_remanufatura["motivo_ordem"];
                }
            }
        }

    ?>
    <TR>
        <TD class="titulo"><?= traduz("fabricante") ?></TD>
        <TD class="titulo"><?= traduz("os.fabricante") ?></TD>
        <? if($login_fabrica == 157 and !empty($os_posto)){ ?>
            <TD class="titulo"><?= traduz("os.interna") ?> </TD>
        <? } 

           if (in_array($login_fabrica, [144])) { ?>
                <TD class="titulo"><?= traduz("Número Único") ?> </TD>
           <?php
           } 
        ?>
        <?php if ($login_fabrica == 176){ $colspanDataAbertura = '3'; } ?>
        <TD class="titulo" <?="colspan='{$colspanDataAbertura}'"?> ><?=$data_osMaiuscula?></TD>
        <?if($login_fabrica == 104){?>
        <TD class="titulo"><?=$data_os?></TD>
        <?}?>
    </TR>

    <TR height='5'>
        <TD class="conteudo"<?=$colspan?>><? echo "<b>".$login_fabrica_nome."</b>" ?></TD>
        <TD class="conteudo"><? echo "<b>".$sua_os."</b>" ?></TD>
        <? if(in_array($login_fabrica, [144,157]) and !empty($os_posto)){ ?>
            <TD class="conteudo"><? echo "<b>".$os_posto."</b>" ?></TD>
        <? } ?>
        <TD class="conteudo" <?="colspan='{$colspanDataAbertura}'"?> ><? echo $data_abertura ?></TD>
        <?php if($login_fabrica == 104){ echo "<TD class='conteudo'>$data_recebimento_produto</TD>"; }?>
    </TR>

    <?php
        if($login_fabrica == 87){
            if(intval($tipo_atendimento) > 0){
                $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);

                $tipo_atendimento = pg_fetch_result($res_tipo_atendimento,0,'descricao');
            }?>

            <tr height='5'>
                <td colspan='3'>
                    <table class="borda" width="100%" border="0" cellspacing="0" cellpadding="0" style='border: none;'>
                        <tr>
                            <td class="titulo"><?= traduz("nota.fiscal") ?></td>
                            <td class="titulo"><?= traduz("tipo.atendimento") ?></td>
                            <td class="titulo"><?= traduz("horas.trabalhadas") ?></td>
                            <td class="titulo"><?= traduz("horas.tecnicas") ?></td>
                            <td class="titulo"><?= traduz("tecnicos") ?></td>
                        </tr>
                        <tr height='5'>
                            <td class="conteudo"><? echo $nota_fiscal ?></td>
                            <td class="conteudo"><? echo $tipo_atendimento ?></td>
                            <td class="conteudo"><? echo $hora_tecnica ?></td>
                            <td class="conteudo"><? echo $qtde_horas ?></td>
                            <td class='conteudo'><? echo $tecnico_nome?></td>
                        </tr>
                    </table>
                </td>
            </tr>
    <?php }?>
    <?php if (in_array($login_fabrica, [139])) { ?>
        <tr colspan='4'>
            <td colspan='4' class="titulo text-left">
                <strong>Peças Utilizadas:</strong>
            </td>
        </tr>
    <?php } ?>
    <TR>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "REFERENCIA"; else echo "REFERÊNCIA";?></TD>
        <?if ($login_fabrica == 96) {?>
            <TD class="titulo"><?= traduz("modelos") ?></TD><?php
        }?>
        <TD class="titulo" <?=($login_fabrica == 171) ? "colspan='3'" : ''; ?>><? if ($sistema_lingua<>'BR') echo "DESCRIPCIÓN"; else echo "DESCRIÇÃO";?></TD>
        <? if($login_fabrica <> 127 && $login_fabrica <> 171){ ?>
            <td class="titulo" <?=($login_fabrica == 148) ? "colspan='3'" : ""?>>
                <? if($login_fabrica == 35) {
                    echo "PO#";
                } else {
                    if ($sistema_lingua<>'BR') echo "SERIE "; else echo "NÚM. DE SÉRIE ";
                }?>
            </td>
            <?php
                if ($login_fabrica == 177){
            ?>
                <td class='titulo'><?=strtoupper(traduz("lote"))?></td>
            <?php
                }
                if ($login_fabrica == 175){
            ?>
                <td class='titulo'><?=strtoupper(traduz("qtde.disparos"))?></td>
            <?php        
                }

                if ($login_fabrica == 176)
                {
            ?>
                    <td class='titulo'><?= traduz("indice") ?></td>
            <?php
                }
            ?>
            <? if (in_array($login_fabrica, array(156))) { ?>
                <td class="titulo">VOID</td>
            <? }

            if(in_array($login_fabrica, [167, 203])){
            ?>
                <td class='titulo'><?= ($login_fabrica == 203) ? "CONTADOR / HRS TRABALHADAS" : traduz("contador") ?></td>
            <?php
            }

        }
        if ($login_fabrica == 1) {?>
            <TD class="titulo"><?= traduz("cod.fabricacao") ?></TD><?php
        }

        if ($login_fabrica == 19) {
        ?>
            <TD class="titulo"><?= traduz("quantidade") ?></TD><?php
        }

        if ($login_fabrica == 143) { ?>
            <td class="titulo"><?= traduz("horimetro") ?></td>
        <? }
        if($login_fabrica == 104 ) { ?>
            <td></td>
        <? } ?>
    </TR>

    <tr height='5'>
                <TD class="conteudo"><?= $referencia ?>  <? echo ($login_fabrica == 171) ? " / " . $produto_referencia_fabrica : ""; ?></TD>
        <?if ($login_fabrica == 96) {?>
            <TD class="conteudo"><?= $modelo ?></TD><?php
        }?>
        <TD class="conteudo" <?=($login_fabrica == 171) ? "colspan='3'" : ''; ?>><?= $descricao ?></TD>
        <?php if (!in_array($login_fabrica, array(171))) {
        ?>
        <td class="conteudo" <?=($login_fabrica == 148) ? "colspan='3'" : ""?>>
            <?
            if (in_array($login_fabrica, array(156)) && $sem_ns == "t") {
                $serie = "Sem número de série";
            }
            echo $serie;
            ?>
        </td>
        <?php
            if ($login_fabrica == 177){
        ?>
            <td class='conteudo'><?=$codigo_fabricacao?></td>
        <?php         
            }
            if ($login_fabrica == 175){
        ?>
            <td class='conteudo'><?=$qtde_disparos?></td>
        <?php        
            }

            if ($login_fabrica == 176)
            {
        ?>
                <td class='conteudo'><?php echo $indice; ?></td>
        <?php
            }
        ?>
        <? } if (in_array($login_fabrica, array(156))) { ?>
            <td class="conteudo"><?= $void; ?></td>
        <? } ?>

        <?php if(in_array($login_fabrica, [167, 203])){ ?>
            <td class='conteudo'><?=$contador?></td>
        <?php } ?>
        <? if ($login_fabrica == 1) { ?>
            <TD class="conteudo"><? echo $codigo_fabricacao ?></TD><?php
        }
        if ($login_fabrica == 19) { ?>
            <TD class="conteudo"><? echo $qtde_produtos ?></TD><?php
        }

        if ($login_fabrica == 143) { ?>
            <td class="titulo"><?=$rg_produto?></td>
        <? }
        if ($login_fabrica == 104 ) { ?>
            <td></td>
        <? } ?>
    </tr>
    <? if (in_array($login_fabrica, array(169,170))) {
        if (number_format($qtde_km,2,',','.') > 0) { ?>
            <tr>
                <td class="titulo" colspan="3"><?= traduz("produto.retirado.para.a.oficina") ?></td>
            </tr>
            <tr>
                <td class="conteudo" colspan="3"><?= ($recolhimento == "t") ? "Sim" : "Não"; ?></td>
            </tr>
        <? }
    }
    if($login_fabrica == 153 and $tipo_atendimento == 243){?>
        <TR>
            <TD class="titulo" colspan='3'><? if ($sistema_lingua<>'BR') echo "CODIGO LACRE"; else echo "CÓDIGO LACRE";?></TD>
        </TR>
        <TR>
            <TD class="conteudo" colspan='3'><? echo $codigo_lacre ?></TD>
        </TR>
    <? }
    if ($login_fabrica == 148) { ?>
        <tr>
            <td class="titulo" ><?= traduz("n.de.serie.motor") ?></td>
            <td class="titulo" ><?= traduz("n.de.serie.transmissao") ?></td>
            <td class="titulo" ><?= traduz("horimetro") ?></td>
            <td class="titulo" ><?= traduz("revisao") ?></td>
        </tr>
         <tr>
            <td class="conteudo" ><?=$serie_motor?></td>
            <td class="conteudo" ><?=$serie_transmissao?></td>
            <td class="conteudo" ><?=$os_horimetro?></td>
            <td class="conteudo" ><?=$os_revisao?></td>
        </tr>
    <? }
    if ($login_fabrica == 158 && !empty($serie_justificativa)) { ?>
    <tr>
        <td class="titulo" colspan="3"><?= traduz("patrimonio") ?></td>
    </tr>
    <tr>
        <td class="conteudo" colspan="3"><?= $serie_justificativa; ?></td>
    </tr>
    <? }
    if($login_fabrica == 86 and $serie_justificativa != 'null'){ // HD 328591 ?>
        <tr>
            <td colspan='6' class='titulo'><?= traduz("justificativa.numero.serie") ?></td>
        </tr>
        <tr>
            <td colspan='6' class='conteudo'><?= $serie_justificativa ?></td>
        </tr>
    <? }
    if(in_array($login_fabrica, array(138)) && $coun_os_produto > 1){ ?>
            <TR>
                <TD class="titulo"><?= ($sistema_lingua<>'BR') ? "REFERENCIA" : "REFERÊNCIA SUBCONJUNTO"; ?></TD>
                <TD class="titulo"><?= ($sistema_lingua<>'BR') ? "DESCRIPCIÓN" : "DESCRIÇÃO SUBCONJUNTO"; ?></TD>
                <TD class="titulo">
                    <?= ($sistema_lingua<>'BR') ? "SERIE " : "NÚM. DE SÉRIE SUBCONJUNTO"; ?>
                </TD>
            </TR>

            <TR height='5'>
                <TD class="conteudo"><?= $referencia_subproduto ?></TD>
                <TD class="conteudo"><?= $descricao_subproduto ?></TD>
                <TD class="conteudo"><?= $serie_subproduto ?></TD>
            </TR>
    <? } ?>
    <?php if (in_array($login_fabrica, array(158))) { ?>
    <TR>
        <TD class="titulo"><?= traduz("numero.da.matricula.do.cliente") ?></TD>
        <TD class="titulo"><?= traduz("comentario") ?> KOF</TD>
        <TD class="titulo"></TD>
    </TR>
    <TR>
        <TD class="conteudo"><? echo $dadoscockpit['idCliente'] ?></TD>
        <TD class="conteudo"><? echo $dadoscockpit['comentario'] ?></TD>
        <TD class="conteudo"></TD>
    </TR>
    <?php } ?>
</TABLE>
<? if ($login_fabrica == 20) { //HD 679930

    $sqlC = "SELECT tbl_posto.cidade, tbl_posto_fabrica.posto
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica using(posto)
                    JOIN tbl_os on (tbl_posto_fabrica.fabrica = tbl_os.fabrica and tbl_posto.posto = tbl_os.posto)
                    WHERE tbl_os.os=$os
                    AND tbl_posto_fabrica.fabrica = $login_fabrica";

    $resC = pg_query($con,$sqlC);

    if (pg_num_rows($resC)>0){

        $cidade_posto = strtolower(trim(pg_result($resC,0,0))) ;
        $posto        = pg_result($resC,0,1);

        if ($cidade_posto == 'panama' ){
            ?>
        <TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
            <tr>
                <td class="titulo">VALOR DE LAS PIEZAS</td>
                <td class="titulo">VALOR DE LA MANO DE OBRA</td>
                <td class="titulo">ADICIONES</td>
                <td class="titulo">TOTAL</td>
            </tr>
            <?
            $sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) == 1) {
                $valor_liquido = pg_fetch_result ($res,0,pecas);
                $mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
            }
            $sql = "select imposto_al  from tbl_posto_fabrica where posto=$posto and fabrica=$login_fabrica";
            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) == 1) {
                $imposto_al   = pg_fetch_result ($res,0,imposto_al);
                $imposto_al   = $imposto_al / 100;
                $acrescimo     = ($valor_liquido + $mao_de_obra) * $imposto_al;
            }
            $total = $valor_liquido + $mao_de_obra + $acrescimo;

            $total          = number_format ($total,2,",",".")         ;
            $mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
            $acrescimo      = number_format ($acrescimo ,2,",",".")    ;
            $valor_desconto = number_format ($valor_desconto,2,",",".");
            $valor_liquido  = number_format ($valor_liquido ,2,",",".");
            ?>
            <tr>
                <td class="conteudo">   <?=$valor_liquido?> </td>
                <td class="conteudo">   <?=$mao_de_obra?>   </td>
                <td class="conteudo"> + <?=$acrescimo?>     </td>
                <td class="conteudo">   <?=$total?>         </td>
            </tr>
        </TABLE>
            <?
        }

    }
}

if($login_fabrica == 169 AND $consumidor_revenda == "REVENDA" AND in_array($tipo_atendimento,array(304,305,315))){
	$displayConsumidor = "style='display:none;'";
}

?>

	<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0" <?=$displayConsumidor?>>
    <TR>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL USUARIO"; else echo ($login_fabrica == 122) ? "NOME DO CLIENTE" : "NOME DO CONSUMIDOR";?></TD>
        <?php if($login_fabrica <> 20){ ?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "CIUDAD"; else echo "CIDADE";?></TD>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "PROVINCIA"; else echo "ESTADO";?></TD>
        <?php } ?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "TELÉFONO"; else echo "FONE";?></TD>
        <TD class="titulo">CELULAR</TD>
        <?if(in_array($login_fabrica,[120,201])){?>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMERCIAL"; else echo "COMERCIAL";?></TD>
        <?php } ?>
        <?php if(in_array($login_fabrica, [167, 203])){ ?>
            <td class='titulo'>CONTATO</td>
        <?php } ?>
        <?php if(in_array($login_fabrica, [203])){ ?>
            <td class='titulo'>EMAIL</td>
        <?php } ?>
    </TR>
    <TR>
        <TD class="conteudo"><? echo $consumidor_nome ?></TD>
        <?php if($login_fabrica <> 20){ ?>
        <TD class="conteudo"><? echo $consumidor_cidade ?></TD>
        <TD class="conteudo"><? echo $consumidor_estado ?></TD>
        <?php } ?>
        <TD class="conteudo"><? echo $consumidor_fone ?></TD>
        <TD class="conteudo"><? echo $consumidor_celular ?></TD>
        <?if(in_array($login_fabrica,[120,201])){?>
            <TD class="conteudo"><? echo $consumidor_fonecom ?></TD>
        <?php } ?>
        <?php if(in_array($login_fabrica, [167, 203])){ ?>
            <td class='conteudo'><?=$contato_consumidor?></td>
        <?php } ?>
        <?php if(in_array($login_fabrica, [203])){ ?>
            <td class='conteudo'><?=$consumidor_email?></td>
        <?php } ?>
    </TR>
</TABLE><?php

if (in_array($login_fabrica,array(3,52,74,147))) {

    # HD 30788 - Francisco Ambrozio (11/8/2008)
    # Adicionado tels. celular e comercial do consumidor para Britânia ?>
    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD class="titulo"><? echo traduz("celular") ?></TD>
              <? if(!in_array($login_fabrica,array(147))){  ?>
            <TD class="titulo"><? echo traduz("telefone.comercial") ?></TD>
              <? }  ?>
            <TD class="titulo"><? echo traduz("email") ?></TD>
        </TR>
        <TR>
            <TD class="conteudo"><? echo $consumidor_celular ?></TD>
            <TD class="conteudo"><? echo $consumidor_fonecom ?></TD>
            <TD class="conteudo"><? echo $consumidor_email ?></TD>
        </TR>
    </TABLE><?php

}?>
<?php if($login_fabrica <> 20){ ?>
<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0" <?=$displayConsumidor?>>
    <TR>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECCIÓN"; else echo "ENDEREÇO";?></TD>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NUMERO"; else echo "NÚMERO";?></TD>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMPLEMIENTO"; else echo "COMPLEMENTO";?></TD>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "BARRIO"; else echo "BAIRRO";?></TD>
        <TD class="titulo"><? if (in_array($login_fabrica, array(52,183)))  {if ($sistema_lingua<>'BR') echo "PUNTO DE REFERENCIA"; else echo "PONTO DE REFERÊNCIA";}?></TD>
    </TR>
    <TR>
        <TD class="conteudo"><? echo $consumidor_endereco ?></TD>
        <TD class="conteudo"><? echo $consumidor_numero ?></TD>
        <TD class="conteudo"><? echo $consumidor_complemento ?></TD>
        <TD class="conteudo"><? echo $consumidor_bairro ?></TD>
        <TD class="conteudo"><? if(in_array($login_fabrica, array(52,183))) echo $consumidor_referencia ?></TD>
    </TR>
</TABLE>
<?php } ?>
<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0" <?=$displayConsumidor?>>
    <TR>
        <?php if($login_fabrica <> 20){ ?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARTADO POSTAL"; else echo "CEP";?></TD>
        <?php } ?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "IDENTIFICACIÓN USUARIO"; else echo "CPF";?></TD>
    </TR>
    <TR>
        <?php if($login_fabrica <> 20){ ?>
        <TD class="conteudo"><? echo $consumidor_cep ?></TD>
        <?php } ?>
        <TD class="conteudo"><? echo $consumidor_cpf ?></TD>
    </TR>
</TABLE>

<? if($login_fabrica == 122){ ?>
        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <TR>
                <TD class="titulo">CPD DO CLIENTE</TD>
                <TD class="titulo"><?= traduz("contato") ?></TD>
            </TR>
            <TR>
                <TD class="conteudo"><? echo $obs_adicionais['consumidor_cpd'] ?></TD>
                <TD class="conteudo"><? echo $obs_adicionais['consumidor_contato'] ?></TD>
            </TR>
        </TABLE>
<? } ?>
<? if($login_fabrica == 35){ ?>
<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD class="titulo" colspan="5"><?= traduz("informacoes.sobre.a.revenda") ?></TD>
    </TR>
        <TR>
        <TD class="titulo">CNPJ</TD>
        <TD class="titulo"><?= traduz("nome") ?></TD>
        <TD class="titulo">NF N.</TD>
        <TD class="titulo"><?= traduz("data.nf") ?></TD>
    </TR>
    <TR>
        <TD class="conteudo"><? echo $revenda_cnpj ?></TD>
        <TD class="conteudo"><? echo $revenda_nome ?></TD>
        <TD class="conteudo"><? echo $nota_fiscal ?></TD>
        <TD class="conteudo"><? echo $data_nf ?></TD>
    </TR>
</TABLE>
<?php }
    if($login_fabrica <> 20 AND !$defeitoReclamadoCadastroDefeitoReclamadoCliente){
?>
<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD class="titulo">
        <?  if ($login_fabrica == 203) {
                echo "DEFEITO RECLAMADO PELO CLIENTE";
            } else {
                if ($sistema_lingua<>'BR') echo "DEFECTO PRESENTADO POR EL USUARIO"; else echo "DEFEITO APRESENTADO PELO CLIENTE";
            }
        ?>
        </TD>
        <?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                <TD class="titulo">BOX / PRATELEIRA</TD>
        <?php } ?>
        <?php if (in_array($login_fabrica, [203])) { ?>
                <TD class="titulo">DEFEITO CONSTATADO PELO TÉCNICO</TD>
        <?php } ?>
    </TR>
    <TR>
        <TD class="conteudo"><?echo $defeito_cliente; echo ($defeito_reclamado_descricao != 'null' && !in_array($login_fabrica, array(50))) ? " - ".$defeito_reclamado_descricao : '';?></TD>
        <?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                <TD class="conteudo"><? echo $box_prateleira; ?></TD>
        <?php } ?>
        <?php if (in_array($login_fabrica, [203])) { ?>
                <TD class="conteudo"><? echo $defeito_constatado; ?></TD>
        <?php } ?>
    </TR>
</TABLE>

        <?php

        if (in_array($login_fabrica, array(169,170,173))) {

            $sql_cons = "SELECT
                                tbl_defeito_constatado.defeito_constatado,
                                tbl_defeito_constatado.descricao         ,
                                tbl_defeito_constatado.codigo
                                FROM tbl_os_defeito_reclamado_constatado
                                JOIN tbl_defeito_constatado USING(defeito_constatado)
                                WHERE os = $os";

            $res_cons = pg_query($con,$sql_cons);


            ?>

            <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
                <TR>
                    <TD class="titulo"><?= traduz("codigo.defeito.constatado") ?></TD>
                    <TD class="titulo"><?= traduz("descricao.defeito.constatado") ?></TD>
                </TR>

                <?php


                    if(pg_num_rows($res_cons)>0) {

                        for($i=0;$i<pg_num_rows($res_cons);$i++) {


                            $defeito_constatado_codigo    = pg_result($res_cons,$i,'codigo');
                            $defeito_constatado_descricao = pg_result($res_cons,$i,'descricao');
                        ?>
                            <TR>
                            <TD class="conteudo"><?=$defeito_constatado_codigo?></TD>
                            <TD class="conteudo"><?=$defeito_constatado_descricao?></TD>

                        </TR>
                    <?php

                        }

                    } else {


                        ?>

                        <TR>
                            <TD class="conteudo borda">&nbsp;</TD>
                            <TD class="conteudo borda">&nbsp;</TD>
                        </TR>
                        <TR>
                            <TD class="conteudo borda">&nbsp;</TD>
                            <TD class="conteudo borda">&nbsp;</TD>
                        </TR>

                        <?php

                    }
                ?>
            </TABLE>
            <?php
        }
        ?>
<?php
}
?>

<?php if ($defeitoReclamadoCadastroDefeitoReclamadoCliente){
    $sql = "SELECT tbl_defeito_constatado.descricao FROM tbl_defeito_constatado INNER JOIN tbl_os_produto ON tbl_os_produto.defeito_constatado = tbl_defeito_constatado.defeito_constatado WHERE tbl_os_produto.os = {$os}";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        $defeito_constatado_descricao = pg_fetch_result($res, 0, "descricao");
    }
?>
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
    <TR>
        <TD class="titulo">DEFEITO CONSTATADO</TD>
        <TD class="titulo">DEFEITO RECLAMADO</TD>
        <?php
        if ($login_fabrica != 175) {
        ?>
            <TD class="titulo">DEFEITO RECLAMADO CLIENTE</TD>
        <?php
        }
        ?>
    </TR>
    <TR>
        <TD class="conteudo"><?=$defeito_constatado_descricao?></TD>
        <TD class="conteudo"><?=$defeito_cliente?></TD>
        <?php
        if ($login_fabrica != 175) {
        ?>
            <TD class="conteudo"><?=$defeito_reclamado_descricao?></TD>
        <?php
        }
        ?>
    </TR>
</TABLE>
<?php } ?>

<?php if( ( ($login_fabrica == 95 || $login_fabrica == 59) and strlen($finalizada) > 0) || in_array($login_fabrica, [96,148])){?>
    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE CIERRE"; else echo "DATA DE FECHAMENTO";?></TD>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE REPARACIÓN"; else echo "DATA DE CONSERTO";?></TD>
            <?php
            if (!in_array($login_fabrica, [148])) { ?>
                <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FALLO CONSTATADO"; else echo $temaMaiusculo;?></TD>
            <?php
            } else {
                echo "<td class='titulo'>DATA FALHA</td>";
            }
            ?>
        </TR>
        <TR>
            <TD class="conteudo"><? echo convertDataBR(substr($finalizada,0,10)); ?></TD>
            <TD class="conteudo"><? echo convertDataBR(substr($data_conserto,0,10)); ?></TD>
            <?php
            if (!in_array($login_fabrica, [148])) { ?>
                <TD class="conteudo"><? echo $defeito_constatado; ?></TD>
            <?php
            } else { 
                echo "<td class='conteudo'>{$obs_adicionais['data_falha']}</td>";
            }
            ?>
        </TR>
    </TABLE>
    <?php
}
if(!in_array($login_fabrica,array(120,201,128,138))){
    if ($login_fabrica == 163) {
        $sql_ta = "SELECT descricao FROM tbL_tipo_atendimento WHERE tipo_atendimento = (SELECT tipo_atendimento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) AND fabrica = $login_fabrica";
        $res_ta = pg_query($con, $sql_ta);

        if(pg_num_rows($res_ta) > 0){
            $desc_tipo_atendimento = pg_fetch_result($res_ta, 0, 'descricao');
        }
    }
    $sql_servico = "
        SELECT tbl_os_item.peca,
            tbl_os_item.qtde,
            tbl_os_item.custo_peca,
            tbl_peca.referencia,
            tbl_peca.descricao,
            tbl_os_item.porcentagem_garantia,
            tbl_os_item.os_por_defeito,
            tbl_os_item.peca_serie,
            tbl_os_item.peca_serie_trocada,
            tbl_peca.referencia_fabrica AS peca_referencia_fabrica,
            tbl_os_item.preco,
            tbl_servico_realizado.descricao AS servico_realizado,
            tbl_os_extra.regulagem_peso_padrao,
            tbl_os_extra.qtde_horas,
            tbl_defeito.descricao AS defeito_descricao
        FROM tbl_os
            LEFT JOIN tbl_os_extra USING(os)
            JOIN tbl_os_produto USING(os)
            JOIN tbl_os_item USING(os_produto)
            JOIN tbl_peca USING(peca)
            LEFT JOIN tbl_servico_realizado ON (tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado)
            LEFT JOIN tbl_defeito USING(defeito)
        WHERE
            tbl_os.os = $os
            AND tbl_os.fabrica = $login_fabrica;";

    $res_servico = pg_query($con,$sql_servico);

        if ($login_fabrica == 203) {
            echo '<table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="titulo" colspan="3">SERVIÇO / REPARO REALIZADO / PEÇAS UTILIZADAS</td>
                    </tr>
                </table>';
        }
        echo '<table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">';
            echo '<tr>';
                if($login_fabrica == 171){
                    echo '<td class="titulo">'.traduz("referencia.fabrica").'</td>';
                }

                if ($login_fabrica == 177){
                    echo '<td class="titulo">'.strtoupper(traduz("lote")).'</td>';
                    echo '<td class="titulo">'.strtoupper(traduz("lote.nova.peca")).'</td>';
                }
                if ($login_fabrica == 175){
                    echo '<td class="titulo">SÉRIE</td>';
                    echo '<td class="titulo">QTDE DISPAROS</td>';
                    echo '<td class="titulo">COMPONENTE RAIZ</td>';
                }

                echo '<td class="titulo">'.traduz("referencia").'</td>';
                echo '<td class="titulo">'.traduz("descricao").'</td>';
                if($login_fabrica != 148){
                    echo '<td class="titulo">'.traduz("quantidade").'</td>';
                }

                if (in_array($login_fabrica, array(163)) AND $desc_tipo_atendimento == 'Fora de Garantia') {
                    $valor_total_pecas = 0;
                    echo "
                    <td class='titulo' style='text-align: center;'>".traduz("preco.unitario")."</td>
                    <td class='titulo' style='text-align: center;'>".traduz("preco.total")."</td>
                    ";

                }

                if(in_array($login_fabrica, [167, 203]) AND $nome_atendimento == "Orçamento"){
                    echo "
                    <td class='titulo' style='text-align: center;'>".traduz("preco.unitario")."</td>
                    <td class='titulo' style='text-align: center;'>".traduz("valor.total")."</td>
                    ";
                }

                if (in_array($login_fabrica, array(169,170,183))) { ?>
                    <td class="titulo"><?= traduz("defeito") ?></td>
                <? }

                if($login_fabrica == 96){
                    echo '<td class="titulo">FREE OF CHARGE</td>';
                } else{
                    if (!in_array($login_fabrica, array(167,203))){
                        echo '<td class="titulo">'.traduz("servico").'</td>';
                    }
                    if ($login_fabrica == 171) {
                        echo '<td class="titulo">'.traduz("pressao.da.agua.mca").'</td>';
                        echo '<td class="titulo">'.traduz("tempo.de.uso.mes").'</td>';
                    }
                }

                if($login_fabrica == 148){
                    echo "
                    <td class='titulo' style='text-align: center;'>".traduz("quantidade")."</td>
                    <td class='titulo' style='text-align: center;'>".traduz("valor.unitario")."</td>
                    <td class='titulo' style='text-align: center;'>".traduz("valor.total")."</td>
                    ";
                }

            echo '</tr>';

	    if (pg_num_rows($res_servico) > 0) {
            for ($x = 0; $x < pg_num_rows($res_servico); $x++) {

                $_referencia = pg_fetch_result($res_servico,$x,referencia);
                $_descricao = pg_fetch_result($res_servico,$x,descricao);
                $_custo_peca = pg_fetch_result($res_servico,$x,custo_peca);
                $_preco = pg_fetch_result($res_servico,$x,preco);
                $_descricao_defeito = pg_fetch_result($res_servico,$x,defeito_descricao);
                $_servico_realizado = pg_fetch_result($res_servico,$x,servico_realizado);
                $_qtde = pg_fetch_result($res_servico,$x,qtde);
                $_referencia_fabrica = pg_fetch_result($res_servico, $x, 'peca_referencia_fabrica');
                $_regulagem_peso_padrao = pg_fetch_result($res_servico, $x, 'regulagem_peso_padrao');
                $_qtde_horas = pg_fetch_result($res_servico, $x, 'qtde_horas');

                echo '<tr>';
                    if($login_fabrica == 171){
                        echo "<td class='conteudo'>$_referencia_fabrica</td>";
                    }

                    echo "<td class='conteudo'>$_referencia</td>";
                    echo "<td class='conteudo'>$_descricao</td>";

                    if ($login_fabrica == 177){
                        $peca_serie_trocada = pg_fetch_result($res_servico, $x, "peca_serie_trocada");
                        $peca_serie = pg_fetch_result($res_servico, $x, "peca_serie");

                        echo "<td class='conteudo'>$peca_serie_trocada</td>";
                        echo "<td class='conteudo'>$peca_serie</td>";
                    }

                    if ($login_fabrica == 175){
                        $qtde_disparos_peca = pg_fetch_result($res_servico, $x, "porcentagem_garantia");
                        $numero_serie_peca = pg_fetch_result($res_servico, $x, "peca_serie");
                        $componente_raiz = pg_fetch_result($res_servico, $x, "os_por_defeito");
                        echo "<td class='conteudo'>$numero_serie_peca</td>";
                        echo "<td class='conteudo'>$qtde_disparos_peca</td>";
                        echo "<td class='conteudo'>".(($componente_raiz == "t")? "SIM":"NÃO")."</td>";
                    }

                    if($login_fabrica != 148){
                        echo "<td class='conteudo'>$_qtde</td>";
                    }

                    if (in_array($login_fabrica, array(163)) AND $desc_tipo_atendimento == 'Fora de Garantia') {
                        $qtde_peca      = (strlen(pg_fetch_result($res_servico,$x,"qtde")) == 0) ? 0 : pg_fetch_result($res_servico,$x,"qtde");

                        $aux_valor_total = (strlen(pg_fetch_result($res_servico,$x,"custo_peca")) == 0) ? 0 : pg_fetch_result($res_servico,$x,"custo_peca");
                        $valor_total_pecas = $valor_total_pecas + $aux_valor_total;

                        $valor_total    = (strlen(pg_fetch_result($res_servico,$x,"custo_peca")) == 0) ? 0 : number_format(pg_fetch_result($res_servico,$x,"custo_peca"), 2);
                        $valor_unitario = number_format($valor_total / $qtde_peca, 2);

                        echo "
                        <td class='conteudo' style='text-align: center;'>{$valor_unitario}</td>
                        <td class='conteudo' style='text-align: center;'>{$valor_total}</td>
                        ";
                    }

                    if(in_array($login_fabrica, [167, 203]) && $nome_atendimento == 'Orçamento'){
                        $valor_unitario    = (strlen(pg_fetch_result($res_servico,$x,"preco")) == 0) ? 0 : number_format(pg_fetch_result($res_servico,$x,"preco"), 2);
                        $preco_total_aux = number_format($valor_unitario*$_qtde, 2);
                        $valor_total_pecas += $preco_total_aux;
                        echo "
                        <td style='text-align: center;' class='conteudo'>{$valor_unitario}</td>
                        <td style='text-align: center;' class='conteudo'>{$preco_total_aux}</td>
                        ";
                    }

                    if (in_array($login_fabrica, array(169,170,183))) { ?>
                        <td class="conteudo"><?= $_descricao_defeito; ?></td>
                    <? }

                    if (!in_array($login_fabrica, array(167,203))){
                        echo "<td class='conteudo'>$_servico_realizado</td>";
                    }

                    if($login_fabrica == 148){

                        $qtde_peca      = (strlen(pg_fetch_result($res_servico,$x,"qtde")) == 0) ? 0 : pg_fetch_result($res_servico,$x,"qtde");
                        $valor_total    = (strlen(pg_fetch_result($res_servico,$x,"custo_peca")) == 0) ? 0 : number_format(pg_fetch_result($res_servico,$x,"custo_peca"), 2);
                        $valor_unitario = number_format($valor_total / $qtde_peca, 2);

                        echo "
                        <td class='conteudo' style='text-align: center;'>{$qtde_peca}</td>
                        <td class='conteudo' style='text-align: center;'>{$valor_unitario}</td>
                        <td class='conteudo' style='text-align: center;'>{$valor_total}</td>
                        ";
                    }

                    if ($login_fabrica == 171) {
                        echo "<td class='conteudo' style='text-align: center;'>{$_regulagem_peso_padrao}</td>";
                        echo "<td class='conteudo' style='text-align: center;'>{$_qtde_horas}</td>";
                    }

                echo '</tr>';

            }
	    }

            if (in_array($login_fabrica, array(163)) AND $desc_tipo_atendimento == 'Fora de Garantia') {
                $sql_ext = "SELECT valores_adicionais
                                FROM tbl_os_campo_extra
                                WHERE os = $os
                                    AND fabrica = $login_fabrica;";
                $res_ext = pg_query($con,$sql_ext);

                if (pg_num_rows($res_ext) > 0) {

                    $valores_adicionais = pg_fetch_result($res_ext, 0, "valores_adicionais");
                    $valores_adicionais = json_decode($valores_adicionais, true);

                    $valor_adicional = $valores_adicionais["Valor Adicional"];
                    $desconto        = $valores_adicionais["Desconto"];

                    $total_geral = $valor_total_pecas + $valor_adicional - $desconto;

                    echo "
                    <tr>
                        <td class='conteudo' style='text-align: center;' colspan='2' ></td>
                        <td class='conteudo' style='text-align: left;' colspan='2' >".traduz("valor.total.pecas")."</td>
                        <td class='conteudo' style='text-align: center;'>".number_format($valor_total_pecas, 2)."</td>
                        <td class='conteudo' style='text-align: center;'></td>
                    </tr>
                    <tr>
                        <td class='conteudo' style='text-align: center;' colspan='2' ></td>
                        <td class='conteudo' style='text-align: left;' colspan='2' >".traduz("valor.adicional")."</td>
                        <td class='conteudo' style='text-align: center;'>".number_format($valor_adicional, 2)."</td>
                        <td class='conteudo' style='text-align: center;'></td>
                    </tr>
                    <tr>
                        <td class='conteudo' style='text-align: center;' colspan='2' ></td>
                        <td class='conteudo' style='text-align: left;' colspan='2' >".traduz("valor.de.desconto")."</td>
                        <td class='conteudo' style='text-align: center;'>".number_format($desconto, 2)."</td>
                        <td class='conteudo' style='text-align: center;'></td>
                    </tr>
                    <tr>
                        <td class='conteudo' style='text-align: center;' colspan='2' ></td>
                        <td class='conteudo' style='text-align: left;' colspan='2' >".traduz("valor.total.geral")."</td>
                        <td class='conteudo' style='text-align: center;'>".number_format($total_geral, 2)."</td>
                        <td class='conteudo' style='text-align: center;'></td>
                    </tr>
                    ";
                }

            }

            if(in_array($login_fabrica, [167, 203])){
                $sql_adicionais = "SELECT valores_adicionais, campos_adicionais FROM tbl_os_campo_extra WHERE os = $os ";
                $res_adicionais = pg_query($con, $sql_adicionais);

                $campos_adicionais = pg_fetch_result($res_adicionais, 0, "campos_adicionais");
                $campos_adicionais = json_decode($campos_adicionais, true);
                $campo_adicional = $campos_adicionais["valor_adicional_peca_produto"];

                $valores_adicionais = pg_fetch_result($res_adicionais, 0, "valores_adicionais");
                $valores_adicionais = json_decode($valores_adicionais, true);

                if(strlen(trim($campo_adicional)) > 0){
                    $campo_adicional = $campos_adicionais["valor_adicional_peca_produto"];
                }else{
                    $campo_adicional = 0;
                }

                $total_geral = $valor_total_pecas + $campo_adicional;
                $total_geral = number_format($total_geral, 2, ".", ",");
                if($nome_atendimento == "Orçamento"){
                    echo "<tr>
                        <td class='titulo' colspan='4'><strong>".traduz("valor.mao.de.obra")."</strong></td>
                        <td style='text-align: center;' class='titulo'><strong>{$campo_adicional}</strong></td>
                        <td colspan='1' class='titulo'></td>
                    </tr>";
                    echo "<tr>
                        <td class='titulo' colspan='4'><strong>".traduz("valor.total")."</strong></td>
                        <td style='text-align: center;' class='titulo'><strong>{$total_geral}</strong></td>
                        <td colspan='1' class='titulo'></td>
                    </tr>";
                }

                if(count($valores_adicionais) > 0){
                    echo"<tr>
                        <td style='text-align: center;' class='titulo' colspan='6'>CUSTOS ADICIONAIS DA OS</td>
                    </tr>";

                    echo "<tr>
                            <td class='titulo' colspan='3'>".traduz("servico")."</td>
                            <td class='titulo' colspan='3'>".traduz("valor")."</td>
                    </tr>";


                    foreach ($valores_adicionais as $key) {
                        foreach ($key as $key1 => $value1) {
                            echo "<tr>
                            <td class='conteudo' colspan='3'>{$key1}</td>
                            <td class='conteudo' colspan='3'>{$value1}</td>
                            </tr>";
                        }
                    }
                }
            }
        echo "</table>";

    }

if(in_array($login_fabrica,array(87))){
    $sql_peca = "
            SELECT
                peca_causadora.referencia AS referencia_causadora,
                peca_causadora.descricao AS descricao_causadora,
                tbl_peca.referencia AS peca_referencia,
                tbl_peca.referencia_fabrica             AS peca_referencia_fabrica            ,
                tbl_peca.descricao AS peca_descricao,
                tbl_os_item.qtde,
                tbl_defeito.descricao AS defeito_descricao,
                tbl_servico_realizado.descricao AS servico_realizado,
                tbl_os_item.soaf
            FROM tbl_os_item
                JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto
                JOIN tbl_os ON tbl_os_produto.os=tbl_os.os
                JOIN tbl_peca ON tbl_os_item.peca=tbl_peca.peca
                LEFT JOIN tbl_peca AS peca_causadora ON tbl_os_item.peca_causadora=peca_causadora.peca
                LEFT JOIN tbl_defeito ON tbl_os_item.defeito=tbl_defeito.defeito
                LEFT JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado=tbl_servico_realizado.servico_realizado
                JOIN tbl_lista_basica ON tbl_os.produto=tbl_lista_basica.produto AND tbl_peca.peca=tbl_lista_basica.peca
            WHERE
                tbl_os.os=$os;";
    $res_peca = pg_exec($con,$sql_peca);

    if (pg_num_rows($res_peca) > 0) {

        $peca_itens =  "<table cellspacing='0' cellpadding='0' border='0' width='600' class='borda'>";
             $peca_itens .=  "<tr>";
                $peca_itens .=  "<th class='titulo'>".traduz("componente")."</th>";
                $peca_itens .=  "<th class='titulo'>".traduz("quantidade")."</th>";
                $peca_itens .=  "<th class='titulo'>".traduz("causa.falha")."</th>";
               // $peca_itens .=  "<th class='titulo'>ITEM CAUSADOR</th>";
                $peca_itens .=  "<th class='titulo'>".traduz("servico")."</th>";
                $peca_itens .=  "<th class='titulo'>SOAF</th>";
            $peca_itens .=  "</tr>";
            for($i = 0; $i < pg_num_rows($res_peca); $i++) {
                extract(pg_fetch_array($res_peca));

                if(!empty($soaf)){
                    $sql_soaf = "SELECT descricao from tbl_tipo_soaf WHERE fabrica = $login_fabrica  AND tipo_soaf = $soaf;";
                    $res_soaf = pg_query($con, $sql_soaf);
                    if(pg_num_rows($res_soaf)){
                        $soaf = pg_fetch_result($res_soaf, 0, 'descricao');
                    }else  $soaf =  "&nbsp;";
                }else  $soaf =  "&nbsp;";

                $peca_itens .=  "<tr>";
                    $peca_itens .=  "<td class='conteudo'>{$peca_referencia} - {$peca_descricao}</td>";
                    $peca_itens .=  "<td class='conteudo'>{$qtde}</td>";
                    $peca_itens .=  "<td class='conteudo'>{$defeito_descricao}</td>";
                   // $peca_itens .=  "<td class='conteudo'>{$referencia_causadora} - {$descricao_causadora}</td>";
                    $peca_itens .=  "<td class='conteudo'>{$servico_realizado}</td>";
                    $peca_itens .=  "<td class='conteudo'>{$soaf}</td>";
                $peca_itens .=  "</tr>";
            }

        $peca_itens .=  "</table>";

    }

    if(!empty($peca_itens))
        echo $peca_itens;
}
if($login_fabrica <> 20){
?>

<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARENCIA GENERAL DEL PRODUCTO"; else echo "APARÊNCIA GERAL DO PRODUTO";?></TD>
        <?php if (in_array($login_fabrica, [203])) { echo "<TD class='titulo'>ACESSÓRIOS / SUPRIMENTOS QUE ACOMPANHAM O EQUIPAMENTO</TD>"; } else { ?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ACCESORIOS DEJADOS POR EL USUARIO"; else echo "ACESSÓRIOS DEIXADOS PELO CLIENTE";?></TD>
        <?php } ?>
    </TR>
    <TR>
        <TD class="conteudo"><? echo $aparencia_produto ?></TD>
        <TD class="conteudo"><? echo $acessorios ?></TD>
    </TR>
</TABLE>
<?php } ?>
<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD class="titulo"><? if ($login_fabrica == 171) { echo "COMENTÁRIO SOBRE A VISITA"; }else{ if ($sistema_lingua<>'BR') echo "OBSERVACIONES"; else echo "OBSERVAÇÃO"; }?></TD>
    </TR>
    <TR>
        <TD class="conteudo">
            <?php
                $obs = wordwrap($obs, 110, '<br/>', true);
                echo $obs
            ?>

        </TD>
    </TR>
</TABLE><?php

//if($login_fabrica==19){
//Wellington 05/02/2007 - Alguem retirou este if da fabrica 19 e não comentou o porque... Estou pulando este item para fabrica 11
if ($login_fabrica <> 11 and $login_fabrica <> 24) {

    if (strlen($tipo_os) > 0 and $login_fabrica == 19) {

        $sqll = "SELECT descricao from tbl_tipo_os where tipo_os=$tipo_os";
        $ress = pg_exec($con,$sqll);

        $tipo_os_descricao = pg_result($ress,0,0);

    }

    if(empty($nome_atendimento) AND !empty($tipo_atendimento)){
        $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE tipo_atendimento = {$tipo_atendimento}";
        $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);

        if(pg_num_rows($res_tipo_atendimento) == 1){
            $nome_atendimento = pg_fetch_result($res_tipo_atendimento,0,'descricao');
        }

    }

    if(!in_array($login_fabrica, array(87))){
    ?>
    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ATENDIMIENTO"; else echo "ATENDIMENTO";?></TD><?php
            if ($login_fabrica == 19) { ?>
                <TD class="titulo"><?= traduz("motivo") ?></TD><?php
            }?>

            <?php if($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){ ?>
                <TD class="titulo"><?= traduz("motivo.ordem") ?></TD>
            <?php } ?>
            <?php if(!in_array($login_fabrica,array(20,161))){ ?>
            <TD class="titulo">
                <?if($login_fabrica != 52 && $login_fabrica != 124){
                    if ($sistema_lingua<>'BR'){
                        echo "NOMBRE DEL TÉCNICO";
                    }else{
                        echo "NOME DO TÉCNICO";
                    }
                }?>
            </TD>
            <?php } 
            if (in_array($login_fabrica, [144]) && $posto_interno) { ?>
                <td class="titulo"><?= traduz("Código Rastreio") ?></td>
            <?php
            } ?>
        </TR>
        <TR>
            <TD class="conteudo"><? echo $codigo_atendimento." - ".$nome_atendimento ?></TD><?php
            if ($login_fabrica == 19) {?>
                <TD class="titulo"><? echo "$tipo_os_descricao";?></TD><?php
            }?>
            <?php if($login_fabrica == 20  and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){ ?>
                <TD class="conteudo"><? echo $motivo_ordem ?></TD>
            <?php } ?>
            <?php if($login_fabrica <> 20){ ?>
            <TD class="conteudo"><? echo $tecnico_nome ?></TD>
            <?php }
             if (in_array($login_fabrica, [144]) && $posto_interno) { ?>
                <td class="conteudo">
                    <?
                    $sql_pac = "SELECT tbl_os_extra.pac
                                FROM tbl_os_extra
                                WHERE tbl_os_extra.os = {$os}";
                    $res_pac = pg_query($con, $sql_pac);

                    echo pg_fetch_result($res_pac,0,'pac');
                    ?>
                </td>
            <?php
            }
            ?>
        </TR>
    </TABLE>

    <?php //HD-3200578
        if($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){
            $obs_motivo_ordem = array();
            if($motivo_ordem == 'PROCON (XLR)'){
                $obs_motivo_ordem[] = 'Protocolo:';
                $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['protocolo']);
            }
            if($motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
                $obs_motivo_ordem[] = 'CI ou Solicitante:';
                $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['ci_solicitante']);
            }

            if($motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
                $obs_motivo_ordem[] = "Descrição Peças:";
                if(strlen(trim($json_os_remanufatura['descricao_peca_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['descricao_peca_1']);
                }
                if(strlen(trim($json_os_remanufatura['descricao_peca_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['descricao_peca_2']);
                }
                if(strlen(trim($json_os_remanufatura['descricao_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['descricao_peca_3']);
                }
            }

            if($motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){
                if(strlen(trim($json_os_remanufatura['codigo_peca_1'])) > 0 OR strlen(trim(utf8_decode($json_os_remanufatura['codigo_peca_2']))) > 0 OR strlen(trim($json_os_remanufatura['codigo_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= 'Código Peças:';
                }
                if(strlen(trim($json_os_remanufatura['codigo_peca_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['codigo_peca_1']);
                }
                if(strlen(trim($json_os_remanufatura['codigo_peca_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['codigo_peca_2']);
                }
                if(strlen(trim($json_os_remanufatura['codigo_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['codigo_peca_3']);
                }

                if(strlen(trim($json_os_remanufatura['numero_pedido_1'])) > 0 OR strlen(trim($json_os_remanufatura['numero_pedido_2'])) > 0 OR strlen(trim($json_os_remanufatura['numero_pedido_3'])) > 0){
                    $obs_motivo_ordem[] .= 'Número Pedidos:';
                }
                if(strlen(trim($json_os_remanufatura['numero_pedido_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['numero_pedido_1']);
                }
                if(strlen(trim($json_os_remanufatura['numero_pedido_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['numero_pedido_2']);
                }
                if(strlen(trim($json_os_remanufatura['numero_pedido_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['numero_pedido_3']);
                }
            }

            if($motivo_ordem == "Linha de Medicao (XSD)"){
                $obs_motivo_ordem[] .= 'Linha de Medição(XSD):';
                $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['linha_medicao']);
            }
            if($motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
                $obs_motivo_ordem[] .= 'Pedido não fornecido - Valor Mínimo(XSS):';
                $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['pedido_nao_fornecido']);
            }

            if($motivo_ordem == 'Contato SAC (XLR)'){
                $obs_motivo_ordem[] .= 'N° do Chamado:';
                $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['contato_sac']);
            }

            if($motivo_ordem == 'Bloqueio financeiro (XSS)' OR $motivo_ordem == 'Ameaca de Procon (XLR)' OR $motivo_ordem == 'Defeito reincidente (XQR)'){
                $obs_motivo_ordem[] .= "Detalhes:";
                $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['detalhe']);
            }
        ?>
        <table class='borda' width="600" border="0" cellspacing="0" cellpadding="0">
            <tr><td class='titulo'><?= traduz("observacao.motivo.ordem") ?></td></tr>
            <tr><td class='conteudo'><?php echo implode('<br/>', $obs_motivo_ordem); ?></td></tr>
        </table>
    <?php
        }
        //FIM HD-3200578
    ?>

    <? if(in_array($login_fabrica,array(117,123,124,127,128))) { ?>

    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
                <TD class="titulo" colspan='2'><?= traduz("os.de.cortesia") ?></TD>
                <TD class="titulo" colspan='2'><?= traduz("os.garantia.estendida") ?></TD>
            </TR>
            <TR>
                <TD class="conteudo" colspan='2'><? echo $cortesia;?></TD>
                <TD class="conteudo" colspan='2'><? echo $os_de_garantia;?></TD>
        </TR>
    </TABLE>

    <? if(!in_array($login_fabrica,array(123,124,126,127,128,134,136))) { ?>

        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <TR>
                    <TD class="titulo" colspan='2'><?= traduz("garantia.estendida") ?></TD>
                </TR>
                <TR>
                    <TD class="conteudo" colspan='2'><? echo ($certificado_garantia) ? "Sim" : "Não";?></TD>
            </TR>
        </TABLE>
    <? }
    }

    if ($login_fabrica == 114) {
        $sql_linha = "SELECT tbl_produto.linha FROM tbl_produto JOIN tbl_os ON tbl_os.produto = tbl_produto.produto AND tbl_os.fabrica = $login_fabrica WHERE tbl_produto.fabrica_i = $login_fabrica AND tbl_os.os = $os";
        $res_linha = pg_query($con, $sql_linha);

        $linha = pg_fetch_result($res_linha, 0, "linha");
    }

    if($login_fabrica != 124 && $login_fabrica != 126 && (($login_fabrica == 114 && !in_array($linha, array(691,692,710)) ) || $login_fabrica != 114)){
    ?>

    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <?php
        $colspan = "2";

        if (in_array($login_fabrica, array(141,144))) {
            $select_os_tipo_posto = "SELECT tbl_posto_fabrica.tipo_posto
                                    FROM tbl_os
                                    INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                                    WHERE tbl_os.fabrica = {$login_fabrica} AND tbl_os.os = {$os}";
            $res_os_tipo_posto = pg_query($con, $select_os_tipo_posto);

            if (pg_num_rows($res_os_tipo_posto) > 0) {
                $os_tipo_posto = pg_fetch_result($res_os_tipo_posto, 0, "tipo_posto");
            }

            if (in_array($os_tipo_posto, array(452,453))) {
                $select_os_remanufatura = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
                $res_os_remanufatura = pg_query($con, $select_os_remanufatura);

                if (pg_num_rows($res_os_remanufatura) > 0) {
                    $json_os_remanufatura = json_decode(pg_fetch_result($res_os_remanufatura, 0, "campos_adicionais"), true);
                    $os_remanufatura               = $json_os_remanufatura["os_remanufatura"];
                    $data_recebimento_produto      = $json_os_remanufatura["data_recebimento_produto"];
                }

                $colspan = "1";
            }

        }

        if (!in_array($login_fabrica, array(150,20,175))) {
        ?>
        <TR>
            <?php if (!in_array($login_fabrica, [203])) { ?>
            <TD class="titulo" colspan='<?=$colspan?>'><?= traduz("deslocamento") ?></TD>
            <?php } ?>
            <?php
            if ($login_fabrica == 171) {
            ?>
            <TD class="titulo" colspan='1'><?= traduz("quantidade.de.visitas") ?></TD>
            <?php
            }
            if (in_array($login_fabrica, array(141,144)) && in_array($os_tipo_posto, array(452,453))) {
            ?>
                <td class='titulo' ><?= traduz("remanufatura") ?></td>
            <?php
            }

            if (in_array($login_fabrica, array(142,156,169,170))) { ?>
                <TD class="titulo" ><?= traduz("visitas") ?></TD>
            <?php
            }

            if (in_array($login_fabrica, array(169,170)) AND !empty($data_agendamento)){
            ?>
                <td class="titulo"><?= traduz("data.agendamento") ?></td>
            <?
            }

            ?>
        </TR>
        <TR>
            <?php if (!in_array($login_fabrica, [203])) { ?>
            <TD class="conteudo" colspan='<?=$colspan?>'><? echo number_format($qtde_km,2,',','.');?>&nbsp;KM</TD>
            <?php } ?>
            <?php
            if ($login_fabrica == 171) {
            ?>
            <TD class="conteudo" colspan='1'><?=$qtde_diaria;?></TD>
            <?php
            }
            if (in_array($login_fabrica, array(141,144)) && in_array($os_tipo_posto, array(452,453))) {
            ?>
                <td class='conteudo'><?=($os_remanufatura == "t") ? "Sim" : "Não"?></td>
            <?php
            }

            if (in_array($login_fabrica, array(142,156,169,170))) { ?>
                <TD class="conteudo" ><?=$qtde_diaria?>&nbsp;</TD>
            <?php
            }

            if (in_array($login_fabrica, array(169,170)) AND !empty($data_agendamento)){
            ?>
                <td class="conteudo"><?=$data_agendamento?></td>
            <?
            }
            ?>
        </TR>
        <?php
        }
        if (in_array($login_fabrica, array(169,170)) && strlen($motivo_visita) > 0) { ?>
            <tr>
                <td class="titulo" colspan="3"><?= traduz("motivo.da.s.visita.s") ?></td>
            </tr>
            <tr>
                <td class="conteudo" colspan="3"><?= $motivo_visita; ?></td>
            </tr>
        <? } ?>
    </TABLE>
<?
    }

    if (in_array($login_fabrica, array(158))) { ?>
        <table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="titulo"><?= traduz("inicio.atendimento") ?></td>
                <td class="titulo"><?= traduz("termino.atendimento") ?></td>
                <td class="titulo"><?= traduz("amperagem") ?></td>
            </tr>
            <tr>
                <td class="conteudo"><?= $inicio_atendimento; ?></td>
                <td class="conteudo"><?= $termino_atendimento; ?></td>
                <td class="conteudo"><?= $regulagem_peso_padrao; ?> A</td>
            </tr>
        <?php 
            $sqlPdv = " SELECT JSON_FIELD('pdv_chegada', campos_adicionais) AS pdv_chegada, 
                               JSON_FIELD('pdv_saida', campos_adicionais) AS pdv_saida 
                        FROM tbl_os_campo_extra 
                        WHERE os = $os 
                        AND fabrica = $login_fabrica";
            $resPdv = pg_query($con, $sqlPdv);
            if (pg_num_rows($resPdv) > 0) {
                $pdv_chegada = pg_fetch_result($resPdv, 0, 'pdv_chegada');
                $pdv_saida   = pg_fetch_result($resPdv, 0, 'pdv_saida');

                if (!empty(trim($pdv_chegada)) && !empty(trim($pdv_saida))) {
                ?>
                    <tr>
                        <td class="titulo">PROGRAMAÇÃO NA CHEGADA PDV</td>
                        <td class="titulo">PROGRAMAÇÃO NA SAÍDA PDV</td>
                        <td class="titulo"></td>
                    </tr>
                    <tr>
                        <td class="conteudo"><?= $pdv_chegada; ?></td>
                        <td class="conteudo"><?= $pdv_saida; ?></td>
                        <td class="conteudo"></td>
                    </tr>
                <?php
                }
            }
        ?>
        </table>
    <?php }

    if (in_array($login_fabrica, array(169,170))) { ?>
        <table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="titulo"><?= traduz("cliente.ausente") ?></td>
             </tr>
            <tr>
                <td class="conteudo">( ) <?= traduz("sim") ?> ( ) <?= traduz("nao") ?></td>
            </tr>
        </table>
    <?php }

    if($login_fabrica == 137){

        ?>
        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">

            <TR>
                <TD class="titulo">CFOP</TD>
                <TD class="titulo"><?= traduz("valor.unitario") ?></TD>
                <TD class="titulo"><?= traduz("total.da.nota") ?></TD>
            </TR>
            <TR>
                <TD class="conteudo"><? echo $cfop ?></TD>
                <TD class="conteudo"><? echo $valor_unitario ?></TD>
                <TD class="conteudo"><? echo $valor_nota ?></TD>
            </TR>

        </TABLE>
        <?php

    }

    if (in_array($login_fabrica,array(59,127))) {
        $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND campos_adicionais notnull";
        $res = pg_query($con,$sql);

        if(pg_num_rows($res) > 0){
            $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

            foreach ($campos_adicionais as $key => $value) {
                $$key = $value;
            }
            if ($login_fabrica == 127){
                $enviar_os = ($enviar_os == "t") ? "Sim" : "Não";
                ?>
                    <TABLE width="600" border="0" cellspacing="0" cellpadding="0" class='borda'>
                        <TR>
                            <TD class="titulo">Envio p/ DL</TD>
                            <TD class="titulo"><?= traduz("cod.rastreio") ?>&nbsp;</TD>
                        </TR>
                        <TR>
                            <TD class="conteudo">&nbsp;<?=$enviar_os?></TD>
                            <TD class="conteudo">&nbsp;<?=$codigo_rastreio?> </TD>
                        </TR>
                    </TABLE>
                <?php
             }
             if ($login_fabrica == 59){
                $sql = "SELECT tipo_posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
                $res = pg_query($con,$sql);
                $tipo_posto = pg_fetch_result($res,0,'tipo_posto');

			if(strlen($os)>0 and $tipo_posto == 464){

                    if ($origem=='recepcao'){
                        $origem = 'Recepção';
                    }elseif(strlen($origem)>0){
                        $origem = 'Sedex reverso';
                    }

                ?>
                    <TABLE width="600" border="0" cellspacing="0" cellpadding="0" class='borda'>
                        <TR>
                            <TD class="titulo"><?= traduz("origem") ?>&nbsp;</TD>
                        </TR>
                        <TR>
                            <TD class="conteudo">&nbsp;<?=$origem?></TD>
                        </TR>
                    </TABLE>
                <?php
                }
            }
        }
    }

    if($login_fabrica == 52){

        // HD-896985

        $sqlTecnico = "SELECT tecnico FROM tbl_os_extra WHERE os = ".$os."";

        $resTecnico = pg_query($con,$sqlTecnico);


        $tecnicoData = pg_fetch_result ($resTecnico,0,tecnico);

        $explodeTecnico = explode("|", $tecnicoData);

        $tecnicoNome = $explodeTecnico[0];
        $tecnicoRg = $explodeTecnico[1];

        ?>
        <table width="600">
        <tr>
            <td class="titulo"><?= traduz("rg.do.tecnico") ?></td>
            <td class="titulo"><?= traduz("nome.do.tecnico") ?></td>
        </tr>
        <tr>
            <td class="conteudo" colspan='1'>&nbsp;<?php echo $tecnicoRg;?></td>

            <td class="conteudo" colspan='1'>&nbsp;<?php echo $tecnicoNome;?></td>
        </tr>
    </table>
    <?php } ?>

    <?php }

}

if (($login_fabrica == 2 AND strlen($data_fechamento) > 0) || $login_fabrica == 59) {?>

    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0"><?php

        if ($login_fabrica == 59) {//HD 337865

            $sql_cons = "SELECT
                    tbl_defeito_constatado.defeito_constatado,
                    tbl_defeito_constatado.descricao         ,
                    tbl_defeito_constatado.codigo,
                    tbl_solucao.solucao,
                    tbl_solucao.descricao as solucao_descricao
            FROM tbl_os_defeito_reclamado_constatado
            JOIN tbl_defeito_constatado USING(defeito_constatado)
            LEFT JOIN tbl_solucao USING(solucao)
            WHERE os = $os";

            $res_dc = pg_query($con, $sql_cons);

            if (pg_num_rows($res_dc) > 0) {

                for ($x = 0; $x < pg_num_rows($res_dc); $x++) {

                    $dc_defeito_constatado = pg_fetch_result($res_dc, $x, 'defeito_constatado');
                    $dc_solucao            = pg_fetch_result($res_dc, $x, 'solucao');

                    $dc_descricao          = pg_fetch_result($res_dc, $x, 'descricao');
                    $dc_codigo             = pg_fetch_result($res_dc, $x, 'codigo');
                    $dc_solucao_descricao  = pg_fetch_result($res_dc, $x, 'solucao_descricao');

                    echo "<tr>";

                    echo "<td class='titulo' height='15'>$temaMaiusculo</td>";
                    echo "<td class='conteudo'>&nbsp; $dc_descricao</td>";
                    echo "<td class='titulo' height='15'>".traduz("solucao")."</td>";
                    echo "<td class='conteudo'>&nbsp; $dc_solucao_descricao</td>";

                    echo "</tr>";

                }

                echo "<TD class='titulo'>DT FECHA. OS</TD>";
                echo "<TD class='conteudo'>$data_fechamento</TD>";

            }

        } else {

            echo "<TR>";
             if (strlen($defeito_constatado) > 0) {
                echo "<TD class='titulo'>$temaMaiusculo</TD>";
                echo "<TD class='titulo'>SOLUÇÃO</TD>";
                echo "<TD class='titulo'>DT FECHA. OS</TD>";
            }
            echo "</TR>";
            echo "<TR>";
            if (strlen($defeito_constatado) > 0) {
                echo "<TD class='conteudo'>$defeito_constatado</TD>";
                echo "<TD class='conteudo'>$solucao</TD>";
                echo "<TD class='conteudo'>$data_fechamento</TD>";
            }

        }?>
        </TR>
    </TABLE><?php

}

 if($login_fabrica == 178 AND $troca_garantia == "t"){
    $sql = "SELECT p.referencia, p.descricao
                            FROM tbl_produto p
                            JOIN tbl_os o ON o.os = {$os}
                            JOIN tbl_os_produto op ON op.os = o.os
                            JOIN tbl_produto pos ON pos.produto = op.produto
                            WHERE p.fabrica_i = {$login_fabrica}
                            AND p.ativo IS TRUE
                            AND p.familia = pos.familia
                            AND p.parametros_adicionais::jsonb->'marcas' ? o.marca::text";
    $resP = pg_query($con,$sql);
    $produtosTroca = pg_fetch_all($resP);

?>

        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <TR>
                <TD class='titulo'><?php echo traduz("opcoes.de.troca.do.produto");?></td>
            </TR>
    <?php
                   if(count($produtosTroca) > 0){

                        foreach($produtosTroca AS $key => $value){
                    
                            echo "<tr><td class='conteudo'>{$value['referencia']} - {$value['descricao']}</td></tr>";
                        }
                    }
    ?>
        </TABLE>
<?php
    }
?>
<?php if($login_fabrica != 52 AND !in_array($login_fabrica, [167, 203])){ ?>
    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD class='titulo'>
                <?php 
                    if (in_array($login_fabrica, [139])) { 
                        echo ($sistema_lingua <> 'BR') ? "Problema identificado y solucionado. Técnico:" : "Problema Identificado e Corrigido. Técnico:";
                    } else {
                        echo ($sistema_lingua <> 'BR') ? "Diagnóstico, repuesto utilizado y resolución del problema. Técnico:" : "Diagnóstico, Peças usadas e Resolução do Problema. Técnico:";
                    }
                ?>
            </TD>
        </TR>
<?php

        if ($login_fabrica == 42) {
            $sqlVerAud = "
                SELECT  tbl_auditoria_os.os              AS os_auditoria ,
                        tbl_auditoria_os.bloqueio_pedido                 ,
                        tbl_auditoria_os.paga_mao_obra
                FROM    tbl_auditoria_os
                WHERE   tbl_auditoria_os.os                  = $os
                AND     tbl_auditoria_os.auditoria_status    = 6
		AND     tbl_auditoria_os.liberada            IS NOT NULL
		AND     tbl_auditoria_os.observacao ~* 'Cortesia Comercial'
            ";
            $resVerAud = pg_query($con,$sqlVerAud);

            $os_auditoria       = pg_fetch_result($resVerAud,0,os_auditoria);
            $bloqueio_pedido    = pg_fetch_result($resVerAud,0,bloqueio_pedido);
            $paga_mao_obra      = pg_fetch_result($resVerAud,0,paga_mao_obra);

            if (!empty($os_auditoria)) {
                if ($bloqueio_pedido == 'f' && $paga_mao_obra == 'f') {
                    $msgAviso = "Verificando a analise técnica realizada pela Assitência Técnica Autorizada Makita, esse tipo de defeito apresentado não se caracteriza defeito de fabricação, sendo que nessas condições o equipamento perde o direito a garantia. Desta vez, a Makita do Brasil está concedendo em caráter de cortesia comercial a(s) peça(s) ou acessório(s) designados neste documento, ficando apenas a mão-de-obra de reparo a cargo do consumidor.
À Assistência Técnica, favor orientar o consumidor na forma correta de uso do equipamento e explicar ao mesmo que está sendo concedida uma cortesia comercial e não uma garantia. Solicitar ao consumidor que assine a cópia deste documento para comprovar a ciência deste fato.
Obs: Após firmado, este documento deverá ser enviado à Makita junto das demais peças de garantia.";
                } else {
                    $msgAviso = "Verificando a analise técnica realizada pela Assitência Técnica Autorizada Makita, esse tipo de defeito apresentado não se caracteriza defeito de fabricação, sendo que nessas condições o equipamento perde o direito a garantia. Desta vez, a Makita do Brasil está concedendo em caráter de cortesia comercial a(s) peça(s) ou acessório(s) designados neste documento e a mão-de-obra de reparo.
À Assistência Técnica, favor orientar o consumidor na forma correta de uso do equipamento e explicar ao mesmo que está sendo concedida uma cortesia comercial e não uma garantia. Solicitar ao consumidor que assine a cópia deste documento para comprovar a ciência deste fato.
Obs: Após firmado, este documento deverá ser enviado à Makita junto das demais peças de garantia.";
                }
            }
        }
?>
        <TR>
            <TD class='conteudo'>
<?
        if (empty($os_auditoria)) {

            echo $topo_peca.$peca_dynacom;
        } else {
            echo $msgAviso;
        }
?>
            </TD>
        </TR>
    </TABLE>
<?

    }

    if ($login_fabrica == 35) { ?>
        <TABLE width="600" border="0" cellspacing="0" cellpadding="0">
            <TR><TD> 
                <div style="font: 9px arial !important;">
                        Os serviços prestados pelo Posto Autorizado dentro do período de garantia do produto, deverão ser realizados no prazo máximo de 30 dias, contados a partir da data de recebimento do produto na assistência. Importante informar seu celular e e-mail para que assim que concluído o reparo seja feito comunicado a você para retirada do produto.
                </div>
            </TD></TR>
        </TABLE>
    <?php } ?>
 <?php
        if ($login_fabrica == 131) {

            $query_adicionais = "SELECT campos_adicionais 
                   FROM tbl_os_campo_extra 
                   WHERE os = {$os}";

            $res_adicionais = pg_query($con, $query_adicionais);

            $campos_adicionais = pg_fetch_result($res_adicionais, 0, campos_adicionais);

            $campos_adicionais = json_decode($campos_adicionais); ?>

            <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
                <TR>
                <?php if ($campos_adicionais->tipo_envio_peca == "utilizar_estoque") { ?>
                    <tr>
                        <td class="titulo" width="100">Sobre a(s) peça(s)&nbsp;</td>
                        <td class="titulo" width="100">Prazo de entrega estimado&nbsp;</td>
                    </tr>  
                    <tr>
                        <td class="conteudo">&nbsp;Utilizar as peças do estoque da assistência</td>
                        <td class="conteudo">&nbsp;<?php echo date("d-m-Y", strtotime($campos_adicionais->previsao_entrega)); ?></td>
                    </tr>

                <?php } else { ?>
                    <tr>    
                        <td class="conteudo">&nbsp;Aguardar as peças serem enviadas pela fábrica</td>
                    </tr>  
                <?php } ?>
                </TR>
            </TABLE>
        <?php } ?>
<TABLE width="<?=$width_table ;?>" border="0" cellspacing="0" cellpadding="0">
        <?php
        if(in_array($login_fabrica, array(169,170))){
            ?>

            <TR>
                <TD width="50%" style='font-size: 10px'>
                    <?= traduz("hora.inicio.visita") ?> __:__
                </td>
                <TD width="50%" style='font-size: 10px'>
                    <?= traduz("hora.termino.visita") ?> __:__
                </td>
            </TR>
            <TR>
                <TD style='font-size: 10px'>
                    <?php
                        echo '<br />'.$posto_cidade .", ". $data_abertura.'<br/><br/>';
                    ?>
                </TD>
            </TR>
                <TR>
                    <TD style='font-size: 08px;<?=$espacamento?>' colspan="3">
                        <? echo "Técnico: " . $tecnico_nome_midea ?>  - Assinatura: _____________________________________________________________________________________________
                </tr>
                <TR>
                     <TD style='font-size: 08px;<?=$espacamento?>' colspan="3">
                         <? echo '<br><br>'. $consumidor_nome ?>  Assinatura: _____________________________________________________________________________________________
                </tr>
            <?php
        }
        ?>
</table>
<?php

if ($login_fabrica == 129) {
    $sql = "SELECT titulo, observacao
            FROM tbl_laudo_tecnico_os
            WHERE fabrica = $login_fabrica
            AND os = $os
            ORDER BY ordem ASC";
    $res = pg_query($con, $sql);

    $rows = pg_num_rows($res);

    unset($laudo_tecnico);

    if ($rows > 0) {
        for ($i = 0; $i < $rows; $i++) {
            $laudo_tecnico[pg_fetch_result($res, $i, "titulo")] = pg_fetch_result($res, $i, "observacao");
        }
    }
?>


    <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
        <tr>
            <td class='titulo' colspan="4">&nbsp;<?= traduz("laudo.tecnico") ?></td>
        </tr>
        <tr>
            <td class='titulo' colspan="2"><?= traduz("nome.da.assitencia.tecnica.autorizada") ?></td>
            <td class='titulo'><?= traduz("n.da.assitencia") ?></td>
            <td class='titulo'><?= traduz("data") ?></td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_posto_nome']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_posto_numero']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_data_abertura']?></td>
        </tr>
    </table>
    <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
        <tr>
            <td class='titulo' colspan="2"><?= traduz("nome.do.cliente") ?></td>
            <td class='titulo' colspan="2"><?= traduz("endereco") ?></td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_cliente_nome']?></td>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_cliente_endereco']?></td>
        </tr>
    </table>
    <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
        <tr>
            <td class='titulo'><?= traduz("cidade") ?></td>
            <td class='titulo'><?= traduz("uf") ?></td>
            <td class='titulo'><?= traduz("bairro") ?></td>
            <td class='titulo'>TEL.</td>
        </tr>
        <tr>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_cidade']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_estago']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_bairro']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_telefone']?></td>
        </tr>
    </table>
    <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
        <tr>
            <td class='titulo' colspan="2"><?= traduz("local.da.compra") ?></td>
            <td class='titulo'><?= traduz("nota.fiscal") ?></td>
            <td class='titulo'><?= traduz("data") ?></td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_local_compra']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_nota_fiscal']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_nota_fiscal_data']?></td>
        </tr>
    </table>
    <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
        <tr>
            <td class='titulo'><?= traduz("instalado.em") ?></td>
            <td class='titulo' colspan="3"><?= traduz("nome.da.instaladora") ?></td>
        </tr>
        <tr>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_data_instalado']?></td>
            <td class='conteudo' colspan="3"><?=$laudo_tecnico['laudo_tecnico_instaladora_nome']?></td>
        </tr>
    </table>
    <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
        <tr>
            <td class='titulo'><?= traduz("agua.utilizada") ?></td>
            <td class='titulo'><?= traduz("pressurizador") ?></td>
            <td class='titulo'><?= traduz("tensao") ?></td>
            <td class='titulo'><?= traduz("tipo.de.gas") ?></td>
        </tr>
        <tr>
            <td class='conteudo'>
                <?php
                switch ($laudo_tecnico["laudo_tecnico_agua_utilizada"]) {
                    case 'direto_da_rua':
                        echo "DIRETO DA RUA/REDE DE ABASTECIMENTO";
                        break;

                    case 'caixa':
                        echo "CAIXA/REDE DE ABASTECIMENTO";
                        break;

                    case 'poco':
                        echo "POÇO";
                        break;
                }
                ?>
            </td>
            <td class='conteudo'>
                <?php
                switch ($laudo_tecnico["laudo_tecnico_pressurizador"]) {
                    case 'true':
                        echo "SIM";
                        break;

                    case 'false':
                        echo "NÃO";
                        break;
                }
                ?>
            </td>
            <td class='conteudo'>
                <?php
                switch ($laudo_tecnico["laudo_tecnico_tensao"]) {
                    case '110v':
                        echo "110V";
                        break;

                    case '220v':
                        echo "220V";
                        break;

                    case 'pilha':
                        echo "PILHA";
                        break;
                }
                ?>
            </td>
            <td class='conteudo'>
                <?php
                switch ($laudo_tecnico["laudo_tecnico_tipo_gas"]) {
                    case 'gn':
                        echo "GN";
                        break;

                    case 'glp':
                        switch ($laudo_tecnico["laudo_tecnico_gas_glp"]) {
                            case 'estagio_unico':
                                $estagio = "ESTÁGIO ÚNICO";
                                break;

                            case 'dois_estagios':
                                $estagio = "DOIS ESTÁGIOS";
                                break;
                        }

                        echo "GLP $estagio";
                        break;
                }
                ?>
            </td>
        </tr>
    </table>
    <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
        <tr>
            <td class='titulo'><?= traduz("pressao.de.gas.dinamica") ?></td>
            <td class='titulo'><?= traduz("pressao.de.gas.estatica") ?></td>
            <td class='titulo'><?= traduz("pressao.de.agua.dinamica") ?></td>
            <td class='titulo'><?= traduz("pressao.de.agua.estatica") ?></td>
        </tr><tr>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_gas_dinamica']?> (consumo máx.)</td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_gas_estatica']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_agua_dinamica']?> (consumo máx.)</td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_agua_estatica']?></td>
        </tr>
    </table>
    <table width="600px" border="0" cellspacing="1" cellpadding="0" class='borda' style="table-layout: fixed;" >
        <tr>
            <td class='titulo'><?= traduz("diametro.do.duto") ?></td>
            <td class='titulo'><?= traduz("comprimento.total.do.duto") ?></td>
            <td class='titulo'><?= traduz("quant.de.curvas") ?></td>
        </tr>
        <tr>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_diametro_duto']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_comprimento_total_duto']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_quantidade_curvas']?></td>
        </tr>
    </table>
    <table width="600px" border="0" cellspacing="1" cellpadding="0" class='borda'>
        <tr>
            <td class='titulo'><?= traduz("caracteristicas.do.local.de.instalacao") ?></td>
            <td class='titulo'><?= traduz("instalacao.de.acordo.com.o.nbr.13.103") ?></td>
        </tr>
        <tr>
            <td class='conteudo'>
            <?php
            switch ($laudo_tecnico["laudo_tecnico_caracteristica_local_instalacao"]) {
                case 'externo':
                    echo "EXTERNO";
                    break;

                case 'interno':
                    echo "INTERNO";

                    switch ($laudo_tecnico["laudo_tecnico_local_instalacao_interno_ambiente"]) {
                        case 'area_servico':
                            echo " ÁREA DE SERVIÇO";
                            break;

                        case 'outro':
                            echo " {$laudo_tecnico['laudo_tecnico_local_instalacao_interno_ambiente_outro']}";
                            break;
                    }
                    break;
            }
            ?>
            </td>
            <td class='conteudo'>
                <?php
                switch ($laudo_tecnico["laudo_tecnico_instalacao_nbr"]) {
                    case 'true':
                        echo "SIM";
                        break;

                    case 'false':
                        echo "NÃO";
                        break;
                }
                ?>
            </td>
        </tr>
    </table>
    <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
        <tr>
            <td class='titulo' colspan="2"><?= traduz("problema.diagnosticado") ?></td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_problema_diagnosticado']?></td>
        </tr>
    </table>
    <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
        <tr>
            <td class='titulo' colspan="2"><?= traduz("providencias.adotadas") ?></td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_providencias_adotadas']?></td>
        </tr>
    </table>
    <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
        <tr>
            <td class='titulo'  colspan="2"><?= traduz("nome.do.tecnico") ?></td>
        </tr>
        <tr>
            <td class='conteudo'  colspan="2"><?=$laudo_tecnico['laudo_tecnico_tecnico_nome']?></td>
        </tr>
    </table>
<?php
}

if ($login_fabrica == 6 AND $linha == "TABLET"){ ?>
<TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
    <TD>
    <h2>
        <?= traduz("se.houver.a.necessidade.de.formatacao.em.seu.tablet.informamos.que.todos.os.dados.fotos.videos.musicas.etc.e/ou.possiveis.aplicativos.instalados.serao.perdidos.sem.a.possibilidade.de.recuperacao") ?>.
    </h2>
    </TD>
</TR>
<TR>
</TR>
</TABLE>
<?php }

if(!in_array($login_fabrica, [167, 203])){

    if ($login_fabrica <> 3){ ?>
    <TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
        </TR>
    </TABLE>
    <?}



    if ($login_fabrica == 11) { //WELLINGTON 05/02/2007

        echo "<CENTER>";
        echo "<TABLE width='650px' border='0' cellspacing='0' cellpadding='0'>";
        echo "<TR class='titulo'>";
        echo "<TD style='font-size: 09px; text-align: center; width: 100%;'>";

        ########## CABECALHO COM DADOS DO POSTOS ##########
        echo $posto_nome."<BR>";
        echo "CNPJ/CPF ".$posto_cnpj ." - IE/RG ".$posto_ie;
        echo "</TD></TR></TABLE></CENTER>";

    }

    if ($login_fabrica == 11) {
        echo "<TABLE width='600' border='0' cellspacing='0' cellpadding='0'>";
            echo "<TR><TD align='left'><font face='arial' size='1px'>".traduz("via.do.fabricante.assinada.pelo.cliente")."</font></TD></TR>";
        echo "</TABLE>";
    }

    if ($login_fabrica == 3){
        ?>

        <TABLE width="600" border="0" cellspacing="0" cellpadding="0" class="borda">
            <TR>
                <TD class="conteudo"><B><?= traduz("retiro.o.produto.acima.descrito.isento.do.defeito.reclamado.e.nas.mesmas.condicoes.de.apresentacao.de.sua.entrada.neste.posto.de.servicos.comprovado.atraves.de.teste.efetuado.na.entrega.do.aparelho") ?>.</B>
                </TD>
             </TD>
        </TABLE>


        <div class='texto_termos'>

            <p>
                1) <?= traduz("declaro.para.os.devidos.fins.que.o.equipamento/acessorio.s.referente.s.a.esta.ordem.de.servico.e.sao.usado.s.e.de.minha.propriedade.e.estara.ao.nesta.assistencia.tecnica.para.o.reparo.portanto.assumo.toda.a.responsabilidade.quanto.a.sua.procedencia") ?>.
            </p>

            <p>
                2) <?= traduz("desde.ja.autorizo.a.assistencia.tecnica.a.entregar.o.s.objeto.s.aqui.identificado.s.a.quem.apresentar.esta.ordem.de.servico.1.via.e.tambem.a.cobrar.o.valor.de.r.1.00.hum.real.por.dia.a.titulo.de.guarda.do.equipamento.caso.nao.venha.retira.los.no.prazo.de.10.dias.apos.o.comunicado.que.o.reparo.foi.efetuado.ou.da.nao.aprovacao.do.orcamento.se.houver") ?>.
            </p>

            <p>
                <?= traduz("declaro.e.concordo.com.os.dados.acima") ?>:
            </p>

            <p>
                <?= traduz("de.acordo") ?>:___/___/____ <?= traduz("visto.do.cliente") ?>:_________________________________________<br /><br />
                <?= traduz("retirada") ?>:___/___/_____  <?= traduz("quem") ?>:__________________________ <?= traduz("documento") ?>:_____________
            </p>

        </div>

        <TABLE width="600" border="0" cellspacing="0" cellpadding="0">
            <TR>
                <TD style='font-size: 10px'><?php
                    echo $posto_cidade .", ". $data_abertura;
                ?>
                </TD>
            </TR>
            <TR>
                    <TD style='font-size: 08px'><? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: ";?></TD>
            </TR>
        </TABLE><br>
        <?
    }

    if ($login_fabrica == 3){ ?>
    <TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
        </TR>
    </TABLE>
    <?}
    ?>

    <?php
    if($login_fabrica == 52){
        ?>
        <br />
        <TABLE width="600" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td colspan="4" align="right">
                    <strong style="font: 14px arial; font-weight: bold;"><?= traduz("via.do.posto") ?></strong>
                </td>
            </tr>
            <TR class="conteudo">
                <TD>
                    <?php
                        // $img_contrato = 'logos/';
                        // $img_contrato .= 'cabecalho_print_' . strtolower($login_fabrica_nome) . '.gif';
                        $img_contrato = "logos/logo_fricon.jpg";
                    ?>
                    <IMG SRC="<? echo ($img_contrato); ?>" height="40" ALT="ORDEM DE SERVIÇO">
                </TD>
                <td align="center">
                    <strong>POSTO AUTORIZADO</strong> <br />
                    <?php
                        echo ($posto_nome != "") ? $posto_nome."<br />" : "";
                        echo ($posto_endereco != "") ? $posto_endereco : "";
                        echo ($posto_numero != "") ? $posto_numero.", " : "";
                        echo ($posto_bairro != "") ? $posto_bairro.", " : "";
                        echo ($posto_cep != "") ? " <br /> CEP: ".$posto_cep." " : "";
                        echo ($posto_cidade != "") ? $posto_cidade." - " : "";
                        echo ($posto_estado != "") ? $posto_estado : "";
                    ?>
                </td>
                <td align="center" class="borda" style="padding: 5px;">
                    <strong><?= traduz("data.emissao") ?></strong> <br />
                    <?=date("d/m/Y");?>
                </td>
                <td align="center" class="borda" style="padding: 5px;">
                    <strong><?= traduz("numero.os") ?></strong> <br />
                    <?=$os;?>
                </td>
            </TR>
        </TABLE>
        <?php
    } ?>

    <TABLE class="borda" width="600" border="1" cellspacing="0" cellpadding="0">
        <TR>
            <TD class="titulo" colspan="6"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre la orden de servicio"; else echo "Informações sobre a Ordem de Serviço";?></TD>
        </TR><?php
        if ($login_fabrica == 50) {

            $sql_status = "SELECT status_os,
                                    observacao,
                                    tbl_admin.login,
                                    to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
                                FROM tbl_os_status
                                LEFT JOIN tbl_admin USING(admin)
                                WHERE os = $os
                                AND status_os IN (98,99,100,101,102,103,104)
                                ORDER BY data DESC LIMIT 1";

            $res_status = pg_exec($con,$sql_status);
            $resultado  = pg_numrows($res_status);

            if ($resultado == 1) {

                $data_status        = trim(pg_result($res_status,0,data));
                $status_os          = trim(pg_result($res_status,0,status_os));
                $status_observacao  = trim(pg_result($res_status,0,observacao));
                $intervencao_admin  = trim(pg_result($res_status,0,login));

                if ($status_os == 98 or $status_os == 99 or $status_os == 100 or $status_os == 101 or $status_os == 102 or $status_os == 103 or $status_os == 104) {

                    $sql_status = "select descricao from tbl_status_os where status_os = $status_os";
                    $res_status = pg_exec($con, $sql_status);

                    if (pg_numrows($res_status) > 0) $descricao_status = pg_result($res_status, 0, 0);

                    echo "<TR>";
                        echo "<TD class='titulo'>".traduz("data")." &nbsp;</TD>";
                        echo "<TD class='titulo'>ADMIN &nbsp;</TD>";
                        echo "<TD class='titulo'>STATUS &nbsp;</TD>";
                        echo "<TD class='titulo' colspan='3'>".traduz("motivo")." &nbsp;</TD>";
                    echo "</TR>";
                    echo "<TR>";
                        echo "<TD class='conteudo'> $data_status </TD>";
                        echo "<TD class='conteudo'>&nbsp;$intervencao_admin </TD>";
                        echo "<TD class='conteudo'>&nbsp;$descricao_status </TD>";
                        echo "<TD class='conteudo' colspan='3'>&nbsp;$status_observacao </TD>";
                    echo "</TR>";

                }

            }

        }

        if (in_array($login_fabrica, array(19,35,96))) $colspan=' colspan="2"';?>

        <?php

        if ($login_fabrica == 148) {
            $colspanDataAbertura = 3;
        }

        if (in_array($login_fabrica, array(143,167,175,177,203)) ) {
            $colspanDataAbertura = 2;
        }
        ?>

        <TR>
            <TD class="titulo"<?=$colspan?>><? if ($sistema_lingua<>'BR') echo "FABRICANTE"; else echo "FABRICANTE";?></TD>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OS FABRICANTE"; else echo "OS FABRICANTE";?></TD>
            <? if($login_fabrica == 157 and !empty($os_posto)){ ?>
                <TD class="titulo">OS INTERNA </TD>
            <? } 


               if (in_array($login_fabrica, [144])) { ?>
                    <TD class="titulo"><?= traduz("Número Único") ?> </TD>
               <?php
               } 

            ?>
            <TD class="titulo" <?="colspan='{$colspanDataAbertura}'"?> ><?=$data_osMaiuscula?></TD>
            <?if($login_fabrica == 104){?>
            <TD class="titulo"><?=$data_os?></TD>
            <?}?>
        </TR>

        <TR>
            <TD class="conteudo"<?=$colspan?>><? echo "<b>".$login_fabrica_nome."</b>" ?></TD>
            <TD class="conteudo"><? echo "<b>".$sua_os."</b>" ?></TD>
            <? if(in_array($login_fabrica, [144,157]) and !empty($os_posto)){ ?>
                <TD class="conteudo"><? echo "<b>".$os_posto."</b>" ?></TD>
            <? } ?>
            <TD class="conteudo" <?="colspan='{$colspanDataAbertura}'"?>><? echo $data_abertura ?></TD>
            <?php if($login_fabrica == 104){ echo "<TD class='conteudo'>$data_recebimento_produto</TD>"; }?>
        </TR>

        <?php
            if($login_fabrica == 87){ ?>
                <tr height='5'>
                    <td colspan='3'>
                        <table class="borda" width="100%" border="0" cellspacing="0" cellpadding="0" style='border: none;'>
                            <tr>
                                <td class="titulo"><?= traduz("tipo.atendimento") ?></td>
                                <td class="titulo"><?= traduz("horas.trabalhadas") ?></td>
                                <td class="titulo"><?= traduz("horas.tecnicas") ?></td>
                                <td class="titulo"><?= traduz("tecnico") ?></td>
                            </tr>
                            <tr height='5'>
                                <td class="conteudo"><? echo $tipo_atendimento ?></td>
                                <td class="conteudo"><? echo $hora_tecnica ?></td>
                                <td class="conteudo"><? echo $qtde_horas ?></td>
                                <td class='conteudo'><?php echo $tecnico_nome?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
        <?php }?>
        <?php if (in_array($login_fabrica, [139])) { ?>
            <tr colspan='4'>
                <td colspan='4' class="titulo text-left">
                    <strong>Peças Utilizadas:</strong>
                </td>
            </tr>
        <?php } ?>
        <TR>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "REFERENCIA"; else echo "REFERÊNCIA";?></TD><?php
            if ($login_fabrica == 96) { ?>
                <TD class="titulo"><?= traduz("modelo") ?></TD><?php
            }?>
            <TD class="titulo" <?=($login_fabrica == 171) ? "colspan='3'" : ""?>><? if ($sistema_lingua<>'BR') echo "DESCRIPCIÓN"; else echo "DESCRIÇÃO";?></TD>

            <?php if($login_fabrica <> 127 && $login_fabrica <> 171){ ?>
            <TD class="titulo" <?=($login_fabrica == 148) ? "colspan='3'" : ""?> ><?php
                if ($login_fabrica == 35) {
                    echo "PO#";
                } else {
                    if ($sistema_lingua<>'BR') echo "NÚM. DE SERIE "; else echo "NÚM. DE SÉRIE";
                }?>
            </TD>
            <?php if ($login_fabrica == 177){ ?>
                <TD class="titulo"><?=strtoupper(traduz("lote"))?></TD>
            <?php } ?>
            <?php if ($login_fabrica == 175){ ?>
                <TD class="titulo"><?=strtoupper(traduz("qtde.disparos"))?></TD>
            <?php } ?>
            <?php if(in_array($login_fabrica, [167, 203])){ ?>
                <td class='titulo'><?= ($login_fabrica == 203) ? "CONTADOR / HRS TRABALHADAS" : "CONTADOR" ?> </td>
            <?php } ?>
            <?php if($login_fabrica == 104){echo "<td></td>"; }?>

            <?php
            }
            if ($login_fabrica == 19) {?>
                <TD class="titulo"><?= traduz("quantidade") ?></TD><?php
            }
            if ($login_fabrica == 143) {
            ?>
                <TD class="titulo"><?= traduz("horimetro") ?></TD>
            <?php
            }
            ?>
        </TR>

        <TR>
            <TD class="conteudo"><? echo $referencia ?>  <? echo ($login_fabrica == 171) ? " / " . $produto_referencia_fabrica : ""; ?></TD><?php
            if ($login_fabrica == 96) { ?>
                <TD class="titulo"><?echo $modelo?></TD><?php
            }?>
            <TD class="conteudo" <?=($login_fabrica == 171) ? "colspan='3'" : "colspan='*'"?>><? echo $descricao ?></TD>
            <?php if(!in_array($login_fabrica, array(171))){ ?>
            <TD class="conteudo" <?=($login_fabrica == 148) ? "colspan='3'" : ""?> ><?=$serie; ?></TD>
            <?php } ?>

            <?php if ($login_fabrica == 177){ ?>
                <TD class="conteudo"><?=$codigo_fabricacao?></TD>
            <?php } ?>
            <?php if ($login_fabrica == 175){ ?>
                <TD class="conteudo"><?=$qtde_disparos?></TD>
            <?php } ?>
            
            <?php if(in_array($login_fabrica, [167, 203])){ ?>
                <td class='conteudo'><?=$contador?></td>
            <?php } ?>
            <?php
            if ($login_fabrica == 19) {?>
                <TD class="conteudo"><? echo $qtde_produtos ?></TD><?php
            }
            if ($login_fabrica == 143) {
            ?>
                <TD class="conteudo"><?=$rg_produto?></TD>
            <?php
            }
            ?>
            <?php if($login_fabrica == 104){echo "<td></td>"; }?>
        </TR>
        <?php
            if($login_fabrica == 153 and $tipo_atendimento == 243){?>
            <TR>
                <TD class="titulo" colspan='3'><? if ($sistema_lingua<>'BR') echo "CODIGO LACRE"; else echo "CÓDIGO LACRE";?></TD>
            </TR>
            <TR>
                <TD class="conteudo" colspan='3'><? echo $codigo_lacre ?></TD>
            </TR>
        <?php }

        if (in_array($login_fabrica, array(169,170))) {
            if (number_format($qtde_km,2,',','.') > 0) { ?>
                <tr>
                    <td class="titulo"><?= traduz("produto.retirado.para.a.oficina") ?></td>
                    <td class="titulo" colspan="2"><?= traduz("emprestimo.de.produto.para.o.consumidor") ?></td>
                </tr>
                <tr>
                    <td class="conteudo"><?= ($recolhimento == "t") ? "Sim" : "Não"; ?></td>
                    <td class="conteudo" colspan="2"><?= ($produto_emprestimo == "t") ? "Sim" : "Não"; ?></td>
                </tr>
            <? }
        }
        if ($login_fabrica == 148) {
        ?>
            <tr>
                <td class="titulo" ><?= traduz("n.de.serie.motor") ?></td>
                <td class="titulo" ><?= traduz("n.de.serie.transmissao") ?></td>
                <td class="titulo" ><?= traduz("horimetro") ?></td>
                <td class="titulo" ><?= traduz("revisao") ?></td>
            </tr>
             <tr>
                <td class="conteudo" ><?=$serie_motor?></td>
                <td class="conteudo" ><?=$serie_transmissao?></td>
                <td class="conteudo" ><?=$os_horimetro?></td>
                <td class="conteudo" ><?=$os_revisao?></td>
            </tr>
        <? }
        if ($login_fabrica == 158 && !empty($serie_justificativa)) { ?>
            <tr>
                <td class="titulo" colspan="3"><?= traduz("patrimonio") ?></td>
            </tr>
            <tr>
                <td class="conteudo" colspan="3"><?= $serie_justificativa; ?></td>
            </tr>
        <? }
        if ($login_fabrica == 86 and $serie_justificativa != 'null') { // HD 328591?>
            <tr>
                <td colspan='6' class='titulo'><?= traduz("justificativa.numero.serie") ?></td>
            </tr>
            <tr>
                <td colspan='6' class='conteudo'><? echo $serie_justificativa ?></td>
            </tr><?php
        }?>

          <?php
            if(in_array($login_fabrica, array(138)) && $coun_os_produto > 1){
        ?>

                <TR>
                    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "REFERENCIA"; else echo "REFERÊNCIA SUBCONJUNTO";?></TD>
                    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DESCRIPCIÓN"; else echo "DESCRIÇÃO SUBCONJUNTO";?></TD>
                    <TD class="titulo">
                    <?php
                        if ($sistema_lingua<>'BR') echo "SERIE "; else echo "NÚM. DE SÉRIE SUBCONJUNTO";
                    ?>
                    </TD>
                </TR>

                <TR height='5'>
                    <TD class="conteudo"><? echo $referencia_subproduto ?></TD>
                    <TD class="conteudo"><? echo $descricao_subproduto ?></TD>
                    <TD class="conteudo"><? echo $serie_subproduto ?></TD>
                </TR>

        <?php
            }
        ?>

    </TABLE>
    <?
    if ($login_fabrica == 20) { //HD 679930

        $sqlC = "
        SELECT tbl_posto.cidade, tbl_posto_fabrica.posto
        FROM tbl_posto
        join tbl_posto_fabrica using(posto)
        join tbl_os on (tbl_posto_fabrica.fabrica = tbl_os.fabrica and tbl_posto.posto = tbl_os.posto)

        where tbl_os.os=$os
        AND tbl_posto_fabrica.fabrica = $login_fabrica
        ";

        $resC = pg_query($con,$sqlC);

        if (pg_num_rows($resC)>0){

            $cidade_posto = strtolower(trim(pg_result($resC,0,0))) ;
            $posto        = pg_result($resC,0,1);

            if ($cidade_posto == 'panama' ){
                ?>
            <TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
                <tr>
                    <td class="titulo">VALOR DE LAS PIEZAS</td>
                    <td class="titulo">VALOR DE LA MANO DE OBRA</td>
                    <td class="titulo">ADICIONES</td>
                    <td class="titulo">TOTAL</td>
                </tr>
                <?
                $sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
                $res = pg_query ($con,$sql);

                if (pg_num_rows ($res) == 1) {
                    $valor_liquido = pg_fetch_result ($res,0,pecas);
                    $mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
                }
                $sql = "SELECT imposto_al FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica};";
                $res = pg_query ($con,$sql);

                if (pg_num_rows ($res) == 1) {
                    $imposto_al   = pg_fetch_result ($res,0,imposto_al);
                    $imposto_al   = $imposto_al / 100;
                    $acrescimo     = ($valor_liquido + $mao_de_obra) * $imposto_al;
                }
                $total = $valor_liquido + $mao_de_obra + $acrescimo;

                $total          = number_format ($total,2,",",".")         ;
                $mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
                $acrescimo      = number_format ($acrescimo ,2,",",".")    ;
                $valor_desconto = number_format ($valor_desconto,2,",",".");
                $valor_liquido  = number_format ($valor_liquido ,2,",",".");
                ?>
                <tr>
                    <td class="conteudo">   <?=$valor_liquido?> </td>
                    <td class="conteudo">   <?=$mao_de_obra?>   </td>
                    <td class="conteudo"> + <?=$acrescimo?>     </td>
                    <td class="conteudo">   <?=$total?>         </td>
                </tr>
            </TABLE>
                <?
            }

        }
    }

    ?>

	    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0" <?=$displayConsumidor?>>
        <TR>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL USUARIO"; else echo ($login_fabrica == 122) ? "NOME DO CLIENTE" : "NOME DO CONSUMIDOR";?></TD>
            <?php if($login_fabrica <> 20){ ?>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "CIUDAD"; else echo "CIDADE";?></TD>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "PROVINCIA"; else echo "ESTADO";?></TD>
            <?php } ?>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "TELÉFONO"; else echo "FONE";?></TD>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "CELULAR"; else echo "CELULAR";?></TD>
            <?php if(in_array($login_fabrica,[120,201]) ){?>
                <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMERCIAL"; else echo "COMERCIAL";?></TD>
            <?php } ?>
            <?php if(in_array($login_fabrica, [167, 203])){ ?>
                <td class='titulo'><?= traduz("contato") ?></td>
            <?php } ?>
            <?php if(in_array($login_fabrica, [203])){ ?>
                <td class='titulo'><?= traduz("email") ?></td>
            <?php } ?>
        </TR>
        <TR>
            <TD class="conteudo"><? echo $consumidor_nome ?></TD>
            <?php if($login_fabrica <> 20){ ?>
            <TD class="conteudo"><? echo $consumidor_cidade ?></TD>
            <TD class="conteudo"><? echo $consumidor_estado ?></TD>
            <?php } ?>
            <TD class="conteudo"><? echo $consumidor_fone ?></TD>
            <TD class="conteudo"><? echo $consumidor_celular ?></TD>
            <?php if(in_array($login_fabrica,[120,201]) ){?>
                <TD class="conteudo"><? echo $consumidor_fonecom ?></TD>
            <?php } ?>
            <?php if(in_array($login_fabrica, [167, 203])){ ?>
                <td class='conteudo'><?=$contato_consumidor?></td>
            <?php } ?>
            <?php if(in_array($login_fabrica, [203])){ ?>
                <td class='conteudo'><?=$consumidor_email?></td>
            <?php } ?>
        </TR>
    </TABLE><?php

    if ($login_fabrica == 3 or $login_fabrica == 52 or $login_fabrica == 74) {
        # HD 30788 - Francisco Ambrozio (11/8/2008)
        # Adicionado tels. celular e comercial do consumidor para Britânia ?>
        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <TR>
                <TD class="titulo"><? echo "TELEFONE CELULAR" ?></TD>
                <TD class="titulo"><? echo "TELEFONE COMERCIAL" ?></TD>
                <TD class="titulo"><? echo "EMAIL" ?></TD>
                <?php if ($login_fabrica == 74): ?>
                <TD class="titulo"><?= traduz("data.de.nascimento") ?></TD>
                <?php endif ?>
            </TR>
            <TR>
                <TD class="conteudo"><? echo $consumidor_celular ?></TD>
                <TD class="conteudo"><? echo $consumidor_fonecom ?></TD>
                <TD class="conteudo"><? echo $consumidor_email ?></TD>
                <?php
                if ($login_fabrica == 74) {
                    $qry_c_adicionais = pg_query($con, "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os");
                    $consumidor_data_nascimento = '';

                    if (pg_num_rows($qry_c_adicionais)) {
                        $os_c_adicionais = json_decode(pg_fetch_result($qry_c_adicionais, 0, 'campos_adicionais'), true);

                        if (array_key_exists("data_nascimento", $os_c_adicionais)) {
                            $consumidor_data_nascimento = $os_c_adicionais["data_nascimento"];
                        }
                    }

                    echo '<td class="conteudo">' . $consumidor_data_nascimento . '</td>';
                }
                ?>
            </TR>
        </TABLE><?php
    }?>

    <?php if($login_fabrica <> 20){ ?>
    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0" <?=$displayConsumidor?>>
        <TR>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECCIÓN"; else echo "ENDEREÇO";?></TD>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NÚMERO"; else echo "NÚMERO";?></TD>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMPLEMIENTO"; else echo "COMPLEMENTO";?></TD>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "BARRIO"; else echo "BAIRRO";?></TD>
                    <TD class="titulo"><? if ($login_fabrica == 52)  {if ($sistema_lingua<>'BR') echo "PUNTO DE REFERENCIA"; else echo "PONTO DE REFERÊNCIA"     ;}?></TD>

        </TR>
        <TR>
            <TD class="conteudo"><? echo $consumidor_endereco ?></TD>
            <TD class="conteudo"><? echo $consumidor_numero ?></TD>
            <TD class="conteudo"><? echo $consumidor_complemento ?></TD>
            <TD class="conteudo"><? echo $consumidor_bairro ?></TD>
            <TD class="conteudo"><? if($login_fabrica == 52) echo $consumidor_referencia ?></TD>
        </TR>
    </TABLE>
    <?php } ?>
    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0" <?=$displayConsumidor?>>
        <TR>
            <?php if($login_fabrica <> 20){?>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARATO POSTAL"; else echo "CEP";?></TD>
            <?php } ?>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "IDENTIFICACIÓN USUARIO"; else echo "CPF";?></TD>
        </TR>
        <TR>
            <?php if($login_fabrica <> 20){?>
            <TD class="conteudo"><? echo $consumidor_cep ?></TD>
            <?php } ?>
            <TD class="conteudo"><? echo $consumidor_cpf ?></TD>
        </TR>
    </TABLE>

    <? if($login_fabrica == 122){ ?>
            <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
                <TR>
                    <TD class="titulo">CPD DO CLIENTE</TD>
                    <TD class="titulo"><?= traduz("contato") ?></TD>
                </TR>
                <TR>
                    <TD class="conteudo"><? echo $obs_adicionais['consumidor_cpd'] ?></TD>
                    <TD class="conteudo"><? echo $obs_adicionais['consumidor_contato'] ?></TD>
                </TR>
            </TABLE>
    <?php }
    ?>


    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <? if($login_fabrica != 122 && $login_fabrica != 143){ ?>
        <TR>
            <?php
            if ($login_fabrica == 145) {
            ?>
                <TD class="titulo" colspan="5"><?= traduz("informacoes.sobre.a.revenda/construtora") ?></TD>
            <?php
            } else {
            ?>
                <TD class="titulo" colspan="5"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre el distribuidor"; else echo "Informações sobre a Revenda";?></TD>
            <?php
            }
            ?>
        </TR>
        <? }else{ ?>
        <TR>
            <TD class="titulo" colspan="5"><?= traduz("informacoes.da.nota.fiscal") ?></TD>
        </TR>
        <? } ?>
        <TR>
            <? if($login_fabrica != 122 && $login_fabrica != 143 && $login_fabrica != 20){ ?>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "Identificación"; else echo "CNPJ";?></TD>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE"; else echo "NOME";?></TD>
            <? } ?>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FACTURA COMERCIAL"; else echo "NF N.";?></TD>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA NF"; else echo "DATA NF";?></TD>
            
            <?php if ($login_fabrica == 174) { ?>
                <TD class="titulo">VALOR NF</TD>
            <?php } ?>
        </TR>
        <TR>
            <? if($login_fabrica != 122 && $login_fabrica != 143 && $login_fabrica != 20){ ?>
            <TD class="conteudo"><? echo ($login_fabrica == 15) ? substr($revenda_cnpj,0,8) : $revenda_cnpj; ?></TD>
            <TD class="conteudo"><? echo $revenda_nome ?></TD>
            <? } ?>

            <TD class="conteudo"><? echo $nota_fiscal ?></TD>
            <TD class="conteudo"><? echo $data_nf ?></TD>

            <?php if ($login_fabrica == 174) {
                $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
                $aux_res = pg_query($con, $aux_sql);
                $aux_arr = json_decode(pg_fetch_result($aux_res, 0, 'campos_adicionais'), true);

                if (empty($aux_arr["valor_nf"])) {
                    $valor_nf = "";
                } else {
                    $valor_nf = $aux_arr["valor_nf"];
                } ?>
                <TD class="conteudo"><? echo $valor_nf ?></TD>
            <?php } ?>
        </TR>
    </TABLE>
    <?php  ?>
    <? if($login_fabrica != 15 && $login_fabrica != 20){ ?>
    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECCIÓN"; else echo "ENDEREÇO";?></TD>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NUMERO"; else echo "NÚMERO";?></TD>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMPLEMIENTO"; else echo "COMPLEMENTO";?></TD>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "BARRIO"; else echo "BAIRRO";?></TD>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARATO POSTAL"; else echo "CEP";?></TD>
        </TR>
        <TR>
            <TD class="conteudo"><? echo $revenda_endereco ?></TD>
            <TD class="conteudo"><? echo $revenda_numero ?></TD>
            <TD class="conteudo"><? echo $revenda_complemento ?></TD>
            <TD class="conteudo"><? echo $revenda_bairro ?></TD>
            <TD class="conteudo"><? echo $revenda_cep ?></TD>
        </TR>
    </TABLE>
    <? }
    if (in_array($login_fabrica, array(169,170))) { ?>
        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="titulo"><?= traduz("contato") ?></td>
            </tr>
            <tr>
                <td class="conteudo"><?= $revenda_contato; ?></td>
            </tr>
        </TABLE>
    <? }
    if($login_fabrica <> 20 AND !$defeitoReclamadoCadastroDefeitoReclamadoCliente){ ?>

    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD class="titulo"><? if ($sistema_lingua <> 'BR') echo "DEFECTO PRESENTADO POR EL USUARIO"; else echo "DEFEITO APRESENTADO PELO CLIENTE";?></TD>
            <?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                <TD class="titulo">BOX / PRATELEIRA</TD>
            <?php } ?>
        </TR>
        <TR>
            <TD class="conteudo"><?echo $defeito_cliente; echo ($defeito_reclamado_descricao != 'null' && !in_array($login_fabrica, array(50))) ? " - ".$defeito_reclamado_descricao : '';?></TD>
            <?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                <TD class="conteudo"><? echo $box_prateleira; ?></TD>
            <?php } ?>
        </TR>
    </TABLE>


        <?php

        if (in_array($login_fabrica, array(169,170))) {

            $sql_cons = "SELECT
                                tbl_defeito_constatado.defeito_constatado,
                                tbl_defeito_constatado.descricao         ,
                                tbl_defeito_constatado.codigo
                                FROM tbl_os_defeito_reclamado_constatado
                                JOIN tbl_defeito_constatado USING(defeito_constatado)
                                WHERE os = $os";

            $res_cons = pg_query($con,$sql_cons);


            ?>

            <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
                <TR>
                    <TD class="titulo"><?= traduz("codigo.defeito.constatado") ?></TD>
                    <TD class="titulo"><?= traduz("descricao.defeito.constatado") ?></TD>
                </TR>

                <?php


                if(pg_num_rows($res_cons)>0) {

                    for($i=0;$i<pg_num_rows($res_cons);$i++) {


                        $defeito_constatado_codigo    = pg_result($res_cons,$i,'codigo');
                        $defeito_constatado_descricao = pg_result($res_cons,$i,'descricao');
                        ?>
                        <TR>
                            <TD class="conteudo"><?=$defeito_constatado_codigo?></TD>
                            <TD class="conteudo"><?=$defeito_constatado_descricao?></TD>

                        </TR>
                        <?php

                    }

                } else {


                    ?>

                    <TR>
                        <TD class="conteudo borda">&nbsp;</TD>
                        <TD class="conteudo borda">&nbsp;</TD>
                    </TR>
                    <TR>
                        <TD class="conteudo borda">&nbsp;</TD>
                        <TD class="conteudo borda">&nbsp;</TD>
                    </TR>

                    <?php

                }



                ?>
            </TABLE>


            <?php
        }
        ?>

    <?php
            if(!empty($peca_itens) AND in_array($login_fabrica, array(87)))
                echo $peca_itens;
    ?>

    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARIENCIA GENERAL DEL PRODUCTO"; else echo "APARÊNCIA GERAL DO PRODUTO";?></TD>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ACCESORIO DEJADOS POR EL USUARIO"; else echo "ACESSÓRIOS DEIXADOS PELO CLIENTE";?></TD>
        </TR>
        <TR>
            <TD class="conteudo"><? echo $aparencia_produto ?></TD>
            <TD class="conteudo"><? echo $acessorios ?></TD>
        </TR>
    </TABLE><?php
        }
    ?>
    <?php if ($defeitoReclamadoCadastroDefeitoReclamadoCliente){
        $sql = "SELECT tbl_defeito_constatado.descricao FROM tbl_defeito_constatado INNER JOIN tbl_os_produto ON tbl_os_produto.defeito_constatado = tbl_defeito_constatado.defeito_constatado WHERE tbl_os_produto.os = {$os}";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $defeito_constatado_descricao = pg_fetch_result($res, 0, "descricao");
        }
    ?>
    <TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
        <TR>
            <TD class="titulo">DEFEITO CONSTATADO</TD>
            <TD class="titulo">DEFEITO RECLAMADO</TD>
            <?php
            if ($login_fabrica != 175) {
            ?>
                <TD class="titulo">DEFEITO RECLAMADO CLIENTE</TD>
            <?php
            }
            ?>
        </TR>
        <TR>
            <TD class="conteudo"><?=$defeito_constatado_descricao?></TD>
            <TD class="conteudo"><?=$defeito_cliente?></TD>
            <?php
            if ($login_fabrica != 175) {
            ?>
                <TD class="conteudo"><?=$defeito_reclamado_descricao?></TD>
            <?php
            }
            ?>
        </TR>
    </TABLE>
    <?php } ?>
    <?php if ($login_fabrica == 11) {?>
        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0"><?php
            echo "<TR>";
             if (strlen($defeito_constatado) > 0) {
                echo "<TD class='titulo'>$temaMaiusculo</TD>";
                echo "<TD class='titulo'>".traduz("solucao")."</TD>";
            } else {
                echo "<TD class='titulo'>$temaMaiusculo (".traduz("preencher.este.campo.a.mao").")</TD>";
                echo "<TD class='titulo'>".traduz("solucao")." (".traduz("preencher.este.campo.a.mao").")</TD>";
            }
            echo "</TR>";
            echo "<TR>";
            if (strlen($defeito_constatado) > 0) {
                echo "<TD class='conteudo'>$defeito_constatado</TD>";
                echo "<TD class='conteudo'>$solucao</TD>";
            } else {
                echo "<TD class='conteudo'>&nbsp;</TD>";
                echo "<TD class='conteudo'>&nbsp;</TD>";
            }?>
            </TR>
        </TABLE><?php

    }

    if( ( ($login_fabrica == 95 || $login_fabrica == 59) and strlen($finalizada) > 0)  || $login_fabrica == 96 ){?>

        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <TR>
                <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE CIERRE";     else echo "DATA DE FECHAMENTO";?></TD>
                <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE REPARACIÓN"; else echo "DATA DE CONSERTO";?></TD>
                <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FALLO CONSTATADO";    else echo $temaMaiusculo;?></TD>
            </TR>
            <TR>
                <TD class="conteudo"><? echo convertDataBR(substr($finalizada,0,10)); ?></TD>
                <TD class="conteudo"><? echo convertDataBR(substr($data_conserto,0,10)); ?></TD>
                <TD class="conteudo"><? echo $defeito_constatado; ?></TD>
            </TR>
        </TABLE><?php
    }
    if(!in_array($login_fabrica,array(120,201,128,138))){
        if ($login_fabrica == 163) {
            $sql_ta = "SELECT descricao FROM tbL_tipo_atendimento WHERE tipo_atendimento = (SELECT tipo_atendimento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) AND fabrica = $login_fabrica";
            $res_ta = pg_query($con, $sql_ta);

            if(pg_num_rows($res_ta) > 0){
                $desc_tipo_atendimento = pg_fetch_result($res_ta, 0, 'descricao');
            }
        }

        $sql_servico = "
            SELECT tbl_os_item.peca,
                tbl_os_item.qtde,
                tbl_os_item.custo_peca,
                tbl_peca.referencia,
                tbl_peca.referencia_fabrica AS peca_referencia_fabrica,
                tbl_peca.descricao,
                tbl_servico_realizado.descricao AS servico_realizado,
                tbl_os_extra.regulagem_peso_padrao,
                tbl_os_item.porcentagem_garantia,
                tbl_os_item.os_por_defeito,
                tbl_os_item.peca_serie,
                tbl_os_item.peca_serie_trocada,
                tbl_os_extra.qtde_horas,
                tbl_os_item.preco,
                tbl_defeito.descricao AS defeito_descricao,
                tbl_os_item.parametros_adicionais
            FROM tbl_os
                LEFT JOIN tbl_os_extra USING(os)
                JOIN tbl_os_produto USING(os)
                JOIN tbl_os_item USING(os_produto)
                JOIN tbl_peca USING(peca)
                JOIN tbl_servico_realizado ON (tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado)
                LEFT JOIN tbl_defeito ON tbl_os_item.defeito = tbl_defeito.defeito
            WHERE tbl_os.os = $os
                AND tbl_os.fabrica = $login_fabrica;";

        $res_servico = pg_exec($con, $sql_servico);

        if (pg_num_rows($res_servico) > 0) {

            echo '<table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">';
                echo '<tr>';
                    if($login_fabrica == 171){
                        echo '<td class="titulo">'.traduz("referencia.fabrica").'</td>';
                    }
                    echo '<td class="titulo">'.traduz("referencia").'</td>';
                    echo '<td class="titulo">'.traduz("descricao").'</td>';

                    if ($login_fabrica == 177){
                        echo '<td class="titulo">'.strtoupper(traduz("lote")).'</td>';
                        echo '<td class="titulo">'.strtoupper(traduz('lote.nova.peca')).'</td>';
                    }

                    if ($login_fabrica == 175){
                        echo '<td class="titulo">SÉRIE</td>';
                        echo '<td class="titulo">QTDE DISPAROS</td>'; 
                        echo '<td class="titulo">COMPONENTE RAIZ</td>';                        
                    }
                    if($login_fabrica != 148){
                        echo "<td class='titulo'>".traduz("quantidade")."</td>";
                    }

                    if (in_array($login_fabrica, array(163)) AND $desc_tipo_atendimento == 'Fora de Garantia') {
                        $valor_total_pecas = 0;
                        echo "
                        <td class='titulo' style='text-align: center;'>".traduz("valor.unitario")."</td>
                        <td class='titulo' style='text-align: center;'>".traduz("preco.total")."</td>
                        ";
                    }

                    if (in_array($login_fabrica, array(169,170,183))) { ?>
                        <td class="titulo"><?= traduz("defeito") ?></td>
                    <? }

                    if($login_fabrica == 96){
                        echo '<td class="titulo">FREE OF CHARGE</td>';
                    } else {
                        echo '<td class="titulo">'.traduz("servico").'</td>';
                        if ($login_fabrica == 171) {
                            echo '<td class="titulo">'.traduz("pressao.da.agua.mca").'</td>';
                            echo '<td class="titulo">'.traduz("tempo.de.uso.mes").'</td>';
                        }
                    }

                    if($login_fabrica == 148){
                        echo "
                        <td class='titulo' style='text-align: center;'>".traduz("quantidade")."</td>
                        <td class='titulo' style='text-align: center;'>".traduz("valor.unitario")."</td>
                        <td class='titulo' style='text-align: center;'>".traduz("valor.total")."</td>
                        ";
                    }
                echo '</tr>';
                for ($x = 0; $x < pg_num_rows($res_servico); $x++) {
                    $_referencia_fabrica = pg_fetch_result($res_servico, $x, 'peca_referencia_fabrica');
                    $_referencia = pg_fetch_result($res_servico,$x,referencia);
                    $_descricao = pg_fetch_result($res_servico,$x,descricao);
                    $_custo_peca = pg_fetch_result($res_servico,$x,custo_peca);
                    $_preco = pg_fetch_result($res_servico,$x,preco);
                    $_descricao_defeito = pg_fetch_result($res_servico,$x,defeito_descricao);
                    $_servico_realizado = pg_fetch_result($res_servico,$x,servico_realizado);
                    $_qtde = pg_fetch_result($res_servico,$x,qtde);

                    $parametrosAdicionais = json_decode(pg_fetch_result($res_servico, $x, "parametros_adicionais"), true);

                    echo '<tr>';
                        if($login_fabrica == 171){
                            echo "<td class='conteudo'>$_referencia_fabrica</td>";
                        }
                        echo "<td class='conteudo'>$_referencia</td>";
                        echo "<td class='conteudo'>$_descricao</td>";

                        if ($login_fabrica == 177){
                            $peca_serie_trocada = pg_fetch_result($res_servico, $x, "peca_serie_trocada");
                            $peca_serie = pg_fetch_result($res_servico, $x, "peca_serie");

                            echo "<td class='conteudo'>$peca_serie_trocada</td>";
                            echo "<td class='conteudo'>$peca_serie</td>";
                        }

                        if ($login_fabrica == 175){
                            $qtde_disparos_peca = pg_fetch_result($res_servico, $x, "porcentagem_garantia");
                            $numero_serie_peca = pg_fetch_result($res_servico, $x, "peca_serie");
                            $componente_raiz = pg_fetch_result($res_servico, $x, "os_por_defeito");
                            echo "<td class='conteudo'>$numero_serie_peca</td>";
                            echo "<td class='conteudo'>$qtde_disparos_peca</td>";
                            echo "<td class='conteudo'>".(($componente_raiz=="t")? "SIM":"NÃO")."</td>";
                        }

                        if ($login_fabrica != 148) {
                            $qtde_peca = (strlen(pg_fetch_result($res_servico,$x,"qtde")) == 0) ? 0 : pg_fetch_result($res_servico,$x,"qtde");
                            echo "<td class='conteudo'>{$qtde_peca}</td>";
                        }

                        if (in_array($login_fabrica, array(163)) AND $desc_tipo_atendimento == 'Fora de Garantia') {
                            $qtde_peca      = (strlen(pg_fetch_result($res_servico,$x,"qtde")) == 0) ? 0 : pg_fetch_result($res_servico,$x,"qtde");
                            $aux_valor_total = (strlen(pg_fetch_result($res_servico,$x,"custo_peca")) == 0) ? 0 : pg_fetch_result($res_servico,$x,"custo_peca");
                            $valor_total_pecas = $valor_total_pecas + $aux_valor_total;

                            $valor_total    = (strlen(pg_fetch_result($res_servico,$x,"custo_peca")) == 0) ? 0 : number_format(pg_fetch_result($res_servico,$x,"custo_peca"), 2);
                            $valor_unitario = number_format($valor_total / $qtde_peca, 2);

                            echo "
                            <td class='conteudo' style='text-align: center;'>{$valor_unitario}</td>
                            <td class='conteudo' style='text-align: center;'>{$valor_total}</td>
                            ";
                        }

                        if (in_array($login_fabrica, array(169,170,183))) { ?>
                            <td class="conteudo"><?= $_descricao_defeito; ?></td>
                        <? }

                        echo "<td class='conteudo'>$_servico_realizado</td>";

                        if($login_fabrica == 148){

                            $qtde_peca      = (strlen(pg_fetch_result($res_servico,$x,"qtde")) == 0) ? 0 : pg_fetch_result($res_servico,$x,"qtde");
                            $valor_total    = (strlen(pg_fetch_result($res_servico,$x,"custo_peca")) == 0) ? 0 : number_format(pg_fetch_result($res_servico,$x,"custo_peca"), 2);
                            $valor_unitario = number_format($valor_total / $qtde_peca, 2);

                            echo "
                            <td class='conteudo'>{$parametrosAdicionais["nf_estoque_fabrica"]}</td>
                            <td class='conteudo' style='text-align: center;'>{$qtde_peca}</td>
                            <td class='conteudo' style='text-align: center;'>{$valor_unitario}</td>
                            <td class='conteudo' style='text-align: center;'>{$valor_total}</td>
                            ";
                        }
                        if ($login_fabrica == 171) {
                            echo "<td class='conteudo' style='text-align: center;'>{$regulagem_peso_padrao}</td>";
                            echo "<td class='conteudo' style='text-align: center;'>{$qtde_horas}</td>";
                        }
                    echo '</tr>';
                }
                if (in_array($login_fabrica, array(163)) AND $desc_tipo_atendimento == 'Fora de Garantia') {
                    $sql_ext = "SELECT valores_adicionais
                                    FROM tbl_os_campo_extra
                                    WHERE os = $os
                                        AND fabrica = $login_fabrica;";
                    $res_ext = pg_query($con,$sql_ext);

                    if (pg_num_rows($res_ext) > 0) {

                        $valores_adicionais = pg_fetch_result($res_ext, 0, "valores_adicionais");
                        $valores_adicionais = json_decode($valores_adicionais, true);

                        $valor_adicional = $valores_adicionais["Valor Adicional"];
                        $desconto        = $valores_adicionais["Desconto"];

                        $total_geral = $valor_total_pecas + $valor_adicional - $desconto;

                        echo "
                        <tr>
                            <td class='conteudo' style='text-align: center;' colspan='2' ></td>
                            <td class='conteudo' style='text-align: left;' colspan='2' >".traduz("valor.total.pecas")."</td>
                            <td class='conteudo' style='text-align: center;'>".number_format($valor_total_pecas, 2)."</td>
                            <td class='conteudo' style='text-align: center;'></td>
                        </tr>
                        <tr>
                            <td class='conteudo' style='text-align: center;' colspan='2' ></td>
                            <td class='conteudo' style='text-align: left;' colspan='2' >".traduz("valor.adicional")."</td>
                            <td class='conteudo' style='text-align: center;'>".number_format($valor_adicional, 2)."</td>
                            <td class='conteudo' style='text-align: center;'></td>
                        </tr>
                        <tr>
                            <td class='conteudo' style='text-align: center;' colspan='2' ></td>
                            <td class='conteudo' style='text-align: left;' colspan='2' >".traduz("valor.de.desconto")."</td>
                            <td class='conteudo' style='text-align: center;'>".number_format($desconto, 2)."</td>
                            <td class='conteudo' style='text-align: center;'></td>
                        </tr>
                        <tr>
                            <td class='conteudo' style='text-align: center;' colspan='2' ></td>
                            <td class='conteudo' style='text-align: left;' colspan='2' >".traduz("valor.total.geral")."</td>
                            <td class='conteudo' style='text-align: center;'>".number_format($total_geral, 2)."</td>
                            <td class='conteudo' style='text-align: center;'></td>
                        </tr>
                        ";
                    }

                }
            echo "</table>";
        }
    }

    ?>

    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD class="titulo"><? if ($login_fabrica == 171) { echo "COMENTÁRIO SOBRE A VISITA"; }else{ if ($sistema_lingua<>'BR') echo "OBSERVACIONES"; else echo "OBSERVAÇÃO"; }?></TD>
        </TR>
        <TR>
            <TD class="conteudo"><? echo $obs ?></TD>
        </TR>
    </TABLE><?php

    //Wellington 05/02/2007 - Alguem retirou este if da fabrica 19 e não comentou o porque... Estou pulando este item para fabrica 11
     if(!in_array($login_fabrica, array(11,87))){?>

        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <TR>
                <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ATENDIMIENTO"; else echo "ATENDIMENTO";?></TD><?php
                if ($login_fabrica == 19) {?>
                    <TD class="titulo"><?= traduz("motivo") ?></TD><?php
                }?>
                <?php if($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){ ?>
                    <TD class="titulo"><?= traduz("motivo.ordem") ?></TD>
                <?php } ?>

                <?php if(!in_array($login_fabrica,array(20,161))){ ?>
                <TD class="titulo">
                    <?if($login_fabrica != 52 && $login_fabrica != 124){
                        if ($sistema_lingua<>'BR'){
                            echo "NOMBRE DEL TÉCNICO";
                        }else{
                            echo "NOME DO TÉCNICO";
                        }
                    }?>
                </TD>
                <?php } ?>
            </TR>
            <TR>
                <TD class="conteudo"><? echo $codigo_atendimento." - ".$nome_atendimento ?></TD><?php
                if ($login_fabrica == 19) {?>
                    <TD class="conteudo"><? echo "$tipo_os_descricao";?></TD><?php
                }?>
                <?php if($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66) ){ ?>
                    <TD class="conteudo"><? echo $motivo_ordem ?></TD>
                <?php } ?>
                <?php if($login_fabrica <> 20){ ?>
                <TD class="conteudo"><? echo $tecnico_nome ?></TD>
                <?php } ?>
            </TR>
        </TABLE>

        <?php //HD-3200578
            if($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){
                $obs_motivo_ordem = array();
                if($motivo_ordem == 'PROCON (XLR)'){
                    $obs_motivo_ordem[] = 'Protocolo:';
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['protocolo']);
                }
                if($motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
                    $obs_motivo_ordem[] = 'CI ou Solicitante:';
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['ci_solicitante']);
                }

                if($motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
                    $obs_motivo_ordem[] = "Descrição Peças:";
                    if(strlen(trim($json_os_remanufatura['descricao_peca_1'])) > 0){
                        $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['descricao_peca_1']);
                    }
                    if(strlen(trim($json_os_remanufatura['descricao_peca_2'])) > 0){
                        $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['descricao_peca_2']);
                    }
                    if(strlen(trim($json_os_remanufatura['descricao_peca_3'])) > 0){
                        $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['descricao_peca_3']);
                    }
                }

                if($motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){
                    if(strlen(trim($json_os_remanufatura['codigo_peca_1'])) > 0 OR strlen(trim(utf8_decode($json_os_remanufatura['codigo_peca_2']))) > 0 OR strlen(trim($json_os_remanufatura['codigo_peca_3'])) > 0){
                        $obs_motivo_ordem[] .= 'Código Peças:';
                    }
                    if(strlen(trim($json_os_remanufatura['codigo_peca_1'])) > 0){
                        $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['codigo_peca_1']);
                    }
                    if(strlen(trim($json_os_remanufatura['codigo_peca_2'])) > 0){
                        $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['codigo_peca_2']);
                    }
                    if(strlen(trim($json_os_remanufatura['codigo_peca_3'])) > 0){
                        $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['codigo_peca_3']);
                    }

                    if(strlen(trim($json_os_remanufatura['numero_pedido_1'])) > 0 OR strlen(trim($json_os_remanufatura['numero_pedido_2'])) > 0 OR strlen(trim($json_os_remanufatura['numero_pedido_3'])) > 0){
                        $obs_motivo_ordem[] .= 'Número Pedidos:';
                    }
                    if(strlen(trim($json_os_remanufatura['numero_pedido_1'])) > 0){
                        $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['numero_pedido_1']);
                    }
                    if(strlen(trim($json_os_remanufatura['numero_pedido_2'])) > 0){
                        $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['numero_pedido_2']);
                    }
                    if(strlen(trim($json_os_remanufatura['numero_pedido_3'])) > 0){
                        $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['numero_pedido_3']);
                    }
                }

                if($motivo_ordem == "Linha de Medicao (XSD)"){
                    $obs_motivo_ordem[] .= 'Linha de Medição(XSD):';
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['linha_medicao']);
                }
                if($motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
                    $obs_motivo_ordem[] .= 'Pedido não fornecido - Valor Mínimo(XSS):';
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['pedido_nao_fornecido']);
                }

                if($motivo_ordem == 'Contato SAC (XLR)'){
                    $obs_motivo_ordem[] .= 'N° do Chamado:';
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['contato_sac']);
                }

                if($motivo_ordem == 'Bloqueio financeiro (XSS)' OR $motivo_ordem == 'Ameaca de Procon (XLR)' OR $motivo_ordem == 'Defeito reincidente (XQR)'){
                    $obs_motivo_ordem[] .= "Detalhes:";
                    $obs_motivo_ordem[] .= utf8_decode($json_os_remanufatura['detalhe']);
                }
            ?>
            <table class='borda' width="600" border="0" cellspacing="0" cellpadding="0">
                <tr><td class='titulo'><?= traduz("observacao.motivo.ordem") ?></td></tr>
                <tr><td class='conteudo'><?php echo implode('<br/>', $obs_motivo_ordem); ?></td></tr>
            </table>
        <?php
            }
            //FIM HD-3200578
        ?>
        <? if($login_fabrica == 117 OR $login_fabrica == 123 OR $login_fabrica == 124 OR $login_fabrica == 127 OR $login_fabrica == 128 OR $login_fabrica == 134 OR $login_fabrica == 136) { ?>

        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <TR>
                    <TD class="titulo" colspan='2'><?= traduz("os.de.cortesia") ?></TD>
                    <TD class="titulo" colspan='2'><?= traduz("os.garantia.estendida") ?></TD>
                </TR>
                <TR>
                    <TD class="conteudo" colspan='2'><? echo $cortesia;?></TD>
                    <TD class="conteudo" colspan='2'><? echo $os_de_garantia;?></TD>
            </TR>
        </TABLE>

        <? if(!in_array($login_fabrica,array(123,124,126,127,128,134,136))) { ?>

            <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
                <TR>
                        <TD class="titulo" colspan='2'><?= traduz("garantia.estendida") ?></TD>
                    </TR>
                    <TR>
                        <TD class="conteudo" colspan='2'><? echo $certificado_garantia;?></TD>
                </TR>
            </TABLE>
        <? }
        }

        if ($login_fabrica == 114) {
            $sql_linha = "SELECT tbl_produto.linha FROM tbl_produto JOIN tbl_os ON tbl_os.produto = tbl_produto.produto AND tbl_os.fabrica = $login_fabrica WHERE tbl_produto.fabrica_i = $login_fabrica AND tbl_os.os = $os";
            $res_linha = pg_query($con, $sql_linha);

            $linha = pg_fetch_result($res_linha, 0, "linha");
        }

        if($login_fabrica != 124 && $login_fabrica != 126 && (($login_fabrica == 114 && !in_array($linha, array(691,692,710)) ) || $login_fabrica != 114)){
        ?>
        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <?php
            $colspan = "2";

            if (in_array($login_fabrica, array(141,144))) {
                $select_os_tipo_posto = "SELECT tbl_posto_fabrica.tipo_posto
                                        FROM tbl_os
                                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                                        WHERE tbl_os.fabrica = {$login_fabrica} AND tbl_os.os = {$os}";
                $res_os_tipo_posto = pg_query($con, $select_os_tipo_posto);

                if (pg_num_rows($res_os_tipo_posto) > 0) {
                    $os_tipo_posto = pg_fetch_result($res_os_tipo_posto, 0, "tipo_posto");
                }

                if (in_array($os_tipo_posto, array(452,453))) {
                    $select_os_remanufatura = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
                    $res_os_remanufatura = pg_query($con, $select_os_remanufatura);

                    if (pg_num_rows($res_os_remanufatura) > 0) {
                        $json_os_remanufatura = json_decode(pg_fetch_result($res_os_remanufatura, 0, "campos_adicionais"), true);
                        $os_remanufatura      = $json_os_remanufatura["os_remanufatura"];
                    }

                    $colspan = "1";
                }
            }

            if (!in_array($login_fabrica, array(150,20,175))) {
            ?>
                <TR>
                    <TD class="titulo" colspan='<?=$colspan?>'><?= traduz("deslocamento") ?></TD>
                    <?php
                    if ($login_fabrica == 171) {
                    ?>
                    <TD class="titulo" colspan='1'><?= traduz("quantidade.de.visitas") ?></TD>
                    <?php
                    }
                    if (in_array($login_fabrica, array(141,144)) && in_array($os_tipo_posto, array(452,453))) {
                    ?>
                        <td class='titulo' ><?= traduz("remanufatura") ?></td>
                    <?php
                    }

    		    if ($login_fabrica == 142) {
                ?>
                    <TD class="titulo" ><?= traduz("visitas") ?></TD>
                <?php
                }

                if (in_array($login_fabrica, array(169,170)) AND !empty($data_agendamento)){
                ?>
                    <td class='titulo'><?= traduz("data.agendamento") ?></td>
                <?php } ?>
                </TR>
                <TR>
                    <TD class="conteudo" colspan='<?=$colspan?>'><? echo number_format($qtde_km,2,',','.');?>&nbsp;KM</TD>
                    <?php
                    if ($login_fabrica == 171) {
                    ?>
                    <TD class="conteudo" colspan='1'><?=$qtde_diaria ;?></TD>
                    <?php
                    }
                    if (in_array($login_fabrica, array(141,144)) && in_array($os_tipo_posto, array(452,453))) {
                    ?>
                        <td class='conteudo'><?=($os_remanufatura == "t") ? "Sim" : "Não"?></td>
                    <?php
                    }
	   	        if (in_array($login_fabrica, array(142, 156))) {
                ?>
                    <TD class="conteudo" ><?=$qtde_diaria?>&nbsp;</TD>
                <?php
                }
                if (in_array($login_fabrica, array(169,170)) AND !empty($data_agendamento)){
                ?>
                    <td class="conteudo"><?=$data_agendamento?></td>
                <?php } ?>
                </TR>
            <?php
            }
            ?>
        </TABLE>
        <?php
        }


         if (in_array($login_fabrica, array(169,170))) { ?>
             <table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
                 <tr>
                     <td class="titulo"><?= traduz("cliente.ausente") ?></td>
                 </tr>
                 <tr>
                     <td class="conteudo">( ) <?= traduz("sim") ?> ( ) <?= traduz("nao") ?></td>
                 </tr>
             </table>
         <?php }

        if (in_array($login_fabrica,array(127))) {
            $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND campos_adicionais notnull";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) > 0){
                $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

                foreach ($campos_adicionais as $key => $value) {
                    $$key = $value;
                }

                $enviar_os = ($enviar_os == "t") ? "Sim" : "Não";
            }
        ?>

            <TABLE width="600" border="0" cellspacing="0" cellpadding="0" class='borda'>
                 <TR>
                    <TD class="titulo">Envio p/ DL</TD>
                    <TD class="titulo"><?= traduz("cod.rastreio") ?>&nbsp;</TD>
                </TR>
                <TR>
                    <TD class="conteudo">&nbsp;<?=$enviar_os?></TD>
                    <TD class="conteudo">&nbsp;<?=$codigo_rastreio?> </TD>
                </TR>
            </TABLE><?php
        }

    }

    if (($login_fabrica == 2 AND strlen($data_fechamento) > 0) || $login_fabrica == 59) {?>

        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0"><?php
            echo "<TR>";
                if (strlen($defeito_constatado) > 0 && $login_fabrica != 59) {
                    echo "<TD class='titulo'>$temaMaiusculo</TD>";
                    echo "<TD class='titulo'>".traduz("solucao")."</TD>";
                    echo "<TD class='titulo'>DT FECHA. OS</TD>";
                }
            echo "</TR>";
            echo "<TR>";

                if (strlen($defeito_constatado) > 0) {

                    if ($login_fabrica == 59) {//HD 337865

                        $sql_cons = "SELECT
                                tbl_defeito_constatado.defeito_constatado,
                                tbl_defeito_constatado.descricao         ,
                                tbl_defeito_constatado.codigo,
                                tbl_solucao.solucao,
                                tbl_solucao.descricao as solucao_descricao
                        FROM tbl_os_defeito_reclamado_constatado
                        JOIN tbl_defeito_constatado USING(defeito_constatado)
                        LEFT JOIN tbl_solucao USING(solucao)
                        WHERE os = $os";

                        $res_dc = pg_query($con, $sql_cons);

                        if (pg_num_rows($res_dc) > 0) {

                            for ($x = 0; $x < pg_num_rows($res_dc); $x++) {

                                $dc_defeito_constatado = pg_fetch_result($res_dc, $x, 'defeito_constatado');
                                $dc_solucao            = pg_fetch_result($res_dc, $x, 'solucao');
                                $dc_descricao          = pg_fetch_result($res_dc, $x, 'descricao');
                                $dc_codigo             = pg_fetch_result($res_dc, $x, 'codigo');
                                $dc_solucao_descricao  = pg_fetch_result($res_dc, $x, 'solucao_descricao');

                                echo "<tr>";

                                echo "<td class='titulo' height='15'>$temaMaiusculo</td>";
                                echo "<td class='conteudo'>&nbsp; $dc_descricao</td>";
                                echo "<td class='titulo' height='15'>".traduz("solucao")."</td>";
                                echo "<td class='conteudo'>&nbsp; $dc_solucao_descricao</td>";

                                echo "</tr>";

                            }

                            echo "<TD class='titulo'>DT FECHA. OS</TD>";
                            echo "<TD class='conteudo'>$data_fechamento</TD>";

                        }

                    } else {
                        echo "<TD class='conteudo'>$defeito_constatado</TD>";
                        echo "<TD class='conteudo'>$solucao</TD>";
                        echo "<TD class='conteudo'>$data_fechamento</TD>";
                    }

                }?>
            </TR>
        </TABLE><?php

    }

    if ($login_fabrica == 19) {

        $sql = "SELECT tbl_laudo_tecnico_os.* FROM tbl_laudo_tecnico_os WHERE os = $os ORDER BY ordem, laudo_tecnico_os;";
        $res = pg_exec($con,$sql);

        if (pg_numrows($res) > 0) {

            echo "<br>";
            echo "<TABLE class='borda' width='600' border='0' cellspacing='0' cellpadding='0'>";
                echo "<TR>";
                    echo "<TD colspan='3' TD class='titulo' style='text-align: center'><b>".traduz("laudo.tecnico")."</b></TD>";
                echo "</TR>";
                echo "<TR>";
                    echo "<TD class='titulo' style='width: 30%'>&nbsp;".traduz("questao")."&nbsp;</TD>";
                    echo "<TD class='titulo' style='width: 10%'>&nbsp;".traduz("afirmacao")."&nbsp;</TD>";
                    echo "<TD class='titulo' style='width: 60%'>&nbsp;".traduz("resposta")."&nbsp;</TD>";
                echo "</TR>";

                for ($i = 0; $i < pg_numrows($res); $i++) {

                    $laudo            = pg_result($res,$i,'laudo_tecnico_os');
                    $titulo           = pg_result($res,$i,'titulo');
                    $afirmativa       = pg_result($res,$i,'afirmativa');
                    $laudo_observacao = pg_result($res,$i,'observacao');

                    echo "<TR>";
                        echo "<TD class='titulo'>&nbsp;$titulo&nbsp;</TD>";
                        if (strlen($afirmativa) > 0) {
                            echo "<TD class='titulo'>"; if($afirmativa == 't') echo "&nbsp;Sim&nbsp;"; else echo "&nbsp;Não&nbsp;"; echo "</TD>";
                        } else {
                            echo "<TD class='titulo'>&nbsp;&nbsp;</TD>";
                        }
                        if (strlen($laudo_observacao) > 0) {
                            echo "<TD class='titulo'>&nbsp;$laudo_observacao&nbsp;</TD>";
                        } else {
                            echo "<TD class='titulo'>&nbsp;&nbsp;</TD>";
                        }
                    echo "</TR>";

                }

            echo "</TABLE>";
            echo "<br />";

        }

    }

if($login_fabrica == 178 AND $troca_garantia == "t"){
?>

	<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <TR>
                <TD class='titulo'><?php echo traduz("opcoes.de.troca.do.produto");?></td>
            </TR>
    <?php
		    if(count($produtosTroca) > 0){

			foreach($produtosTroca AS $key => $value){
	    
			    echo "<tr><td class='conteudo'>{$value['referencia']} - {$value['descricao']}</td></tr>";
			}
		    }
    ?>
        </TABLE>
<?php
    }
    if($login_fabrica != 52){ ?>

        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <TR>
                <TD class='titulo'>
                    <?php 
                        if (in_array($login_fabrica, [139])) { 
                            echo ($sistema_lingua <> 'BR') ? "Problema identificado y solucionado. Técnico:" : "Problema Identificado e Corrigido. Técnico:";
                        } else {
                            echo ($sistema_lingua <> 'BR') ? "Diagnóstico, repuesto utilizado y resolución del problema. Técnico:" : "Diagnóstico, Peças usadas e Resolução do Problema. Técnico:";
                        }
                    ?>
                </TD>
            </TR>
            <TR>
                <TD class='conteudo'>
    <?
            if (empty($os_auditoria)) {

                echo $topo_peca.$peca_dynacom;
            } else {
                echo $msgAviso;
            }
    ?>
                </TD>
            </TR>
        </TABLE>
    <? } ?>

         <?php
        if ($login_fabrica == 131) {

            $query_adicionais = "SELECT campos_adicionais 
                   FROM tbl_os_campo_extra 
                   WHERE os = {$os}";

            $res_adicionais = pg_query($con, $query_adicionais);

            $campos_adicionais = pg_fetch_result($res_adicionais, 0, campos_adicionais);

            $campos_adicionais = json_decode($campos_adicionais); ?>

            <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
                <TR>
                <?php if ($campos_adicionais->tipo_envio_peca == "utilizar_estoque") { ?>
                    <tr>
                        <td class="titulo" width="100">Sobre a(s) peça(s)&nbsp;</td>
                        <td class="titulo" width="100">Prazo de entrega estimado&nbsp;</td>
                    </tr>  
                    <tr>
                        <td class="conteudo">&nbsp;Utilizar as peças do estoque da assistência</td>
                        <td class="conteudo">&nbsp;<?php echo date("d-m-Y", strtotime($campos_adicionais->previsao_entrega)); ?></td>
                    </tr>

                <?php } else { ?>
                    <tr>    
                        <td class="conteudo">&nbsp;Aguardar as peças serem enviadas pela fábrica</td>
                    </tr>  
                <?php } ?>
                </TR>
            </TABLE>
        <?php } 

    if ($login_fabrica == 129) {
        $sql = "SELECT titulo, observacao
                FROM tbl_laudo_tecnico_os
                WHERE fabrica = $login_fabrica
                AND os = $os
                ORDER BY ordem ASC";
        $res = pg_query($con, $sql);

        $rows = pg_num_rows($res);

        unset($laudo_tecnico);

        if ($rows > 0) {
            for ($i = 0; $i < $rows; $i++) {
                $laudo_tecnico[pg_fetch_result($res, $i, "titulo")] = pg_fetch_result($res, $i, "observacao");
            }
        }
    ?>
        <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
            <tr>
                <td class='titulo' colspan="4">&nbsp;Laudo Técnico</td>
            </tr>
            <tr>
                <td class='titulo' colspan="2"><?= traduz("nome.da.assitencia.tecnica.autorizada") ?></td>
                <td class='titulo'><?= traduz("n.da.assitencia") ?></td>
                <td class='titulo'><?= traduz("data") ?></td>
            </tr>
            <tr>
                <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_posto_nome']?></td>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_posto_numero']?></td>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_data_abertura']?></td>
            </tr>
        </table>
        <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
            <tr>
                <td class='titulo' colspan="2"><?= traduz("nome.do.cliente") ?></td>
                <td class='titulo' colspan="2"><?= traduz("endereco") ?></td>
            </tr>
            <tr>
                <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_cliente_nome']?></td>
                <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_cliente_endereco']?></td>
            </tr>
        </table>
        <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
            <tr>
                <td class='titulo'><?= traduz("cidade") ?></td>
                <td class='titulo'><?= traduz("uf") ?></td>
                <td class='titulo'><?= traduz("bairro") ?></td>
                <td class='titulo'>TEL.</td>
            </tr>
            <tr>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_cidade']?></td>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_estago']?></td>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_bairro']?></td>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_telefone']?></td>
            </tr>
        </table>
        <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
            <tr>
                <td class='titulo' colspan="2"><?= traduz("local.da.compra") ?></td>
                <td class='titulo'><?= traduz("nota.fiscal") ?></td>
                <td class='titulo'><?= traduz("data") ?></td>
            </tr>
            <tr>
                <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_local_compra']?></td>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_nota_fiscal']?></td>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_nota_fiscal_data']?></td>
            </tr>
        </table>
        <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
            <tr>
                <td class='titulo'><?= traduz("instalado.em") ?></td>
                <td class='titulo' colspan="3"><?= traduz("nome.da.instaladora") ?></td>
            </tr>
            <tr>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_data_instalado']?></td>
                <td class='conteudo' colspan="3"><?=$laudo_tecnico['laudo_tecnico_instaladora_nome']?></td>
            </tr>
        </table>
        <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
            <tr>
                <td class='titulo'><?= traduz("agua.utilizada") ?></td>
                <td class='titulo'><?= traduz("pressurizador") ?></td>
                <td class='titulo'><?= traduz("tensao") ?></td>
                <td class='titulo'><?= traduz("tipo.de.gas") ?></td>
            </tr>
            <tr>
                <td class='conteudo'>
                    <?php
                    switch ($laudo_tecnico["laudo_tecnico_agua_utilizada"]) {
                        case 'direto_da_rua':
                            echo "DIRETO DA RUA/REDE DE ABASTECIMENTO";
                            break;

                        case 'caixa':
                            echo "CAIXA/REDE DE ABASTECIMENTO";
                            break;

                        case 'poco':
                            echo "POÇO";
                            break;
                    }
                    ?>
                </td>
                <td class='conteudo'>
                    <?php
                    switch ($laudo_tecnico["laudo_tecnico_pressurizador"]) {
                        case 'true':
                            echo "SIM";
                            break;

                        case 'false':
                            echo "NÃO";
                            break;
                    }
                    ?>
                </td>
                <td class='conteudo'>
                    <?php
                    switch ($laudo_tecnico["laudo_tecnico_tensao"]) {
                        case '110v':
                            echo "110V";
                            break;

                        case '220v':
                            echo "220V";
                            break;

                        case 'pilha':
                            echo "PILHA";
                            break;
                    }
                    ?>
                </td>
                <td class='conteudo'>
                    <?php
                    switch ($laudo_tecnico["laudo_tecnico_tipo_gas"]) {
                        case 'gn':
                            echo "GN";
                            break;

                        case 'glp':
                            switch ($laudo_tecnico["laudo_tecnico_gas_glp"]) {
                                case 'estagio_unico':
                                    $estagio = "ESTÁGIO ÚNICO";
                                    break;

                                case 'dois_estagios':
                                    $estagio = "DOIS ESTÁGIOS";
                                    break;
                            }

                            echo "GLP $estagio";
                            break;
                    }
                    ?>
                </td>
            </tr>
        </table>
        <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
            <tr>
                <td class='titulo'><?= traduz("pressao.de.gas.dinamica") ?></td>
                <td class='titulo'><?= traduz("pressao.de.gas.estatica") ?></td>
                <td class='titulo'><?= traduz("pressao.de.agua.dinamica") ?></td>
                <td class='titulo'><?= traduz("pressao.de.agua.estatica") ?></td>
            </tr><tr>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_gas_dinamica']?> (consumo máx.)</td>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_gas_estatica']?></td>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_agua_dinamica']?> (consumo máx.)</td>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_agua_estatica']?></td>
            </tr>
        </table>
        <table width="600px" border="0" cellspacing="1" cellpadding="0" class='borda' style="table-layout: fixed;" >
            <tr>
                <td class='titulo'><?= traduz("diametro.do.duto") ?></td>
                <td class='titulo'><?= traduz("comprimento.total.do.duto") ?></td>
                <td class='titulo'>QUANT. DE CURVAS</td>
            </tr>
            <tr>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_diametro_duto']?></td>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_comprimento_total_duto']?></td>
                <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_quantidade_curvas']?></td>
            </tr>
        </table>
        <table width="600px" border="0" cellspacing="1" cellpadding="0" class='borda'>
            <tr>
                <td class='titulo'><?= traduz("caracteristicas.do.local.de.instalacao") ?></td>
                <td class='titulo'><?= traduz("instalacao.de.acordo.com.o.nbr.13.103") ?></td>
            </tr>
            <tr>
                <td class='conteudo'>
                <?php
                switch ($laudo_tecnico["laudo_tecnico_caracteristica_local_instalacao"]) {
                    case 'externo':
                        echo "EXTERNO";
                        break;

                    case 'interno':
                        echo "INTERNO";

                        switch ($laudo_tecnico["laudo_tecnico_local_instalacao_interno_ambiente"]) {
                            case 'area_servico':
                                echo " ÁREA DE SERVIÇO";
                                break;

                            case 'outro':
                                echo " {$laudo_tecnico['laudo_tecnico_local_instalacao_interno_ambiente_outro']}";
                                break;
                        }
                        break;
                }
                ?>
                </td>
                <td class='conteudo'>
                    <?php
                    switch ($laudo_tecnico["laudo_tecnico_instalacao_nbr"]) {
                        case 'true':
                            echo "SIM";
                            break;

                        case 'false':
                            echo "NÃO";
                            break;
                    }
                    ?>
                </td>
            </tr>
        </table>
        <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
            <tr>
                <td class='titulo' colspan="2"><?= traduz("problema.diagnosticado") ?></td>
            </tr>
            <tr>
                <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_problema_diagnosticado']?></td>
            </tr>
        </table>
        <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
            <tr>
                <td class='titulo' colspan="2"><?= traduz("providencias.adotadas") ?></td>
            </tr>
            <tr>
                <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_providencias_adotadas']?></td>
            </tr>
        </table>
        <table width="600px" border="0" cellspacing="0" cellpadding="0" class='borda'>
            <tr>
                <td class='titulo'  colspan="2"><?= traduz("nome.do.tecnico") ?></td>
            </tr>
            <tr>
                <td class='conteudo'  colspan="2"><?=$laudo_tecnico['laudo_tecnico_tecnico_nome']?></td>
            </tr>
        </table>
    <?php
    }

    if($login_fabrica == 19){
    ?>


        <table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td colspan="2"  class='titulo2'><?= traduz("termo.de.retirada.do.produto.pelo.consumidor") ?></td>
            </tr>
            <!-- <tr>
                <td colspan="2"  style="border-bottom:1px solid #000000; line-height:2px">&nbsp;</td>
            </tr> -->
            <tr>
                <td colspan="2"  class='conteudo2' style="padding:5px;">
                    <?= traduz("e.de.total.responsabilidade.do.consumidor.retirar.o.produto.no.sal.servico.autorizado.lorenzetti.ate.o.prazo.de.conserto.ou.troca.do.produto.informado.na.ocasiao.da.entrega.do.produto.para.conserto.ou.troca.caso.o.consumidor.nao.retire.o.produto.ate.a.data.previamente.informada.o.mesmo.estara.passivel.de.cobranca.de.taxa.da.guarda.do.produto") ?>.
                    <br>
                    <?= traduz("este.termo.comprova.a.data.da.realizacao.do.servico.prestado.em.garantia.ate.a.data.prazo.em.que.o.consumidor.devera.retirar.o.seu.produto") ?>.
                    <center><b><?= traduz("artigo.40.do.codigo.de.defesa.do.consumidor") ?>.</b></center>
                </td>
            </tr>
           <!--  <tr>
                <td colspan="2"  style="border-bottom:1px solid #000000;border-top:1px solid #000000; line-height:2px">&nbsp;</td>
            </tr> -->
            <tr>
                <td colspan="2"  class='conteudo2' style="padding:5px; border-top:1px solid #000000;">
                    <?= traduz("em.caso.de.abandono./.renuncia.do.produto.pelo.consumidor.situacao.caracterizada.como.ato.unilateral.fica.sob.total.responsabilidade.do.consumidor.a.nao.retirada.do.produto.o.mesmo.deve.expressar.a.sua.vontade.de.abandono./.renuncia.de.seu.bem.por.escrito.o.qual.da.o.direito.ao.sal.servico.autorizado.lorenzetti.tomar.decisao.referente.ao.destino.do.produto.procedendo.com.o.descarte.desmonte.venda.etc") ?>...

                    <center> <b><?= traduz("artigo.51.inciso.iv.do.codigo.de.defesa.do.consumidor") ?>;<br>
                    <?= traduz("titulo.iii.capitulo.iv.e.artigo.1.275.caput.do.codigo.civil") ?>.</b></center>

                </td>
            </tr>
            <!-- <tr>
                <td colspan="2" style="border-bottom:1px solid #000000;border-top:1px solid #000000; line-height:2px">&nbsp;</td>
            </tr> -->
            <tr>
                <td style="background-color:#cccccc; width: 50%; font-family: Arial; font-size: 9px; padding: 4px; text-align: center; border-top:1px solid #000000;" ><b><?= traduz("prazo.de.entrega.do.produto") ?>:&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;</b></td>

                <td valign="top" style="padding-left:4px; font-family: Arial; border-top:1px solid #000000; border-left:1px solid #000000; font-size:9px;"><?= traduz("observacoes") ?></td>
            </tr>
        </table>


        <TABLE width="600" border="0" cellspacing="0" cellpadding="0" class="borda">
            <TR>
                <TD class="conteudo"><B><?= traduz("recebi.o.produto") ?> <span style="color:#ff0000"><?= traduz("dentro.de.prazo.estipulado") ?></span> <?= traduz("estando.satisfeito.com.o.servico.e.atendimento") ?></B>
                </TD>
             </TD>
        </TABLE><?php

    }
     if ($login_fabrica == 6 AND $linha == "TABLET"){ ?>
    <TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
    <TR>
        <TD>
        <h2>
            Se houver a necessidade de formatação em seu Tablet, informamos que todos os dados (fotos, vídeos, musicas, etc.) e/ou possíveis aplicativos instalados, SERÃO PERDIDOS,SEM A POSSIBILIDADE DE RECUPERAÇÃO.
        </h2>
        </TD>
    </TR>
    <TR>
    <?php }

    $aparece_garantia = false;
    $estilo           = "";

    if ($login_fabrica == 59) {

        $sql = "SELECT os
                FROM tbl_os
                WHERE finalizada IS NOT NULL
                AND   NOT(solucao_os = 3268)
                AND   os = $os
                AND   fabrica = $login_fabrica";

        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $aparece_garantia = true;
        }

        $estilo = "style='width:101.6px;height:33.9px'";

    }

    if ($login_fabrica == 59) { /* HD 21229 */

        if ($aparece_garantia) {?>

            <TABLE width="600" border="0" cellspacing="0" cellpadding="0">
                <TR>
                    <TD class='conteudo' style='padding: 1ex 2em'>
                        TERMO DE GARANTIA
                        <OL>
                            <LI>A garantia cobre somente os serviços descritos no campo DESCRIÇÃO DO SERVIÇO pelo prazo de 90 dias a partir da data de retirada do equipamento.</LI>
                            <LI>A garantia cobre somente serviços de HARDWARE (Troca de peças e acessórios), NÃO ESTÃO COBERTOS serviços de SOFTWARE (VÍRUS, INSTALAÇÕES DE SISTEMAS OPERACIONAIS, MAPAS OU ALERTA DE RADAR DANIFICADOS POR QUALQUER MOTIVO).</LI>
                        </OL>
                        <br>A GARANTIA PERDERÁ SUA VALIDADE SE:<br>
                        <UL>
                            <LI>HOUVER VIOLAÇÃO DO LACRE COLOCADO POR NÓS NO PRODUTO</LI>
                            <LI>SOFRER QUEDAS OU BATIDAS</LI>
                            <LI>FOR UTILIZADA EM REDE ELÉTRICA IMPRÓPRIA E SUJEITA A FLUTUAÇÕES</LI>
                            <LI>FOR INSTALADA DE MANEIRA INADEQUADA</LI>
                            <LI>SOFER DANOS CAUSADOS POR AGENTES DA NATUREZA</LI>
                            <LI>CONECTADO EM VOLTAGEM ERRADA</LI>
                            <LI>ATINGIDO POR DESCARGAS ELÉTRICAS</LI>
                        </UL>
                        <p style="font:10px Arial">Declaro ter recebido o serviço e/ou aparelho descrito acima em perfeitas condições de uso,</p>
                        <p style="font:10px Arial"><?php
                            echo $posto_cidade.", ".$Dias[$lingua][date('w')].', ' . date('j') . ' de ' . $meses[$lingua][date('n')] . ' de '.date('Y').".";?>
                        </p>
                        <P>&nbsp;</P>

                        <P>Assinatura:____________________________________</P>
                        <P style="padding-left:75px"><?=$consumidor_nome?></P>

                    </TD>
                </TR>
            </TABLE><?php

        } else {?>

            <TABLE width="600" border="0" cellspacing="0" cellpadding="0">
                <TR>
                    <TD class='conteudo' style='padding: 1ex 2em'>
                        <p style="font:10px Arial">Declaro corretas as informações acima,</p>
                        <p style="font:10px Arial"><?php
                            echo $posto_cidade.", ".$Dias[$lingua][date('w')].', ' . date('j') . ' de ' . $meses[$lingua][date('n')] . ' de '.date('Y').".";?>
                        </p>
                        <P>Assinatura:____________________________________</P>
                        <P style="padding-left:75px"><?=$consumidor_nome?></P>

                    </TD>

                </TR>
            </TABLE><?php

        }

    } else if ($login_fabrica == 52) {?>

        <TABLE width="600" border="0" cellspacing="0" cellpadding="0">
            <TR>
                <TD class='conteudo' style='padding: 1ex 2em'>
                    <p style="font:8px Arial"> <?= traduz("declaro.que.o.meu.pedido.de.visita.tecnica.foi.atendido.e.que.o.produto.de.minha.propriedade.ficou.em.perfeita.condicao") ?>.</p>
                </TD>
            </TR>
        </TABLE><?php

        } elseif($login_fabrica <> 3) {

        //fputti hd-2892486
        if (in_array($login_fabrica, array(50))) {
            $sqlOSDec = "SELECT A.consumidor_nome_assinatura, to_char(B.termino_atendimento, 'DD/MM/YYYY')  termino_atendimento
                           FROM tbl_os A
                           JOIN tbl_os_extra B ON B.os=A.os
                          WHERE A.os={$os}";
            $resOSDec = pg_query($con, $sqlOSDec);
            $dataRecebimento = pg_fetch_result($resOSDec, 0, 'consumidor_nome_assinatura');
            $recebidoPor     = pg_fetch_result($resOSDec, 0, 'termino_atendimento');

                echo '
                        <table width="600" border="0" cellspacing="1" style="margin-top: 15px;" cellpadding="0" align="left">
                            <tr>
                                <td align="center">'.traduz("declaracao.de.atendimento").'</TD>
                            </tr>
                            <tr>
                                <td style="font-size: 15px;padding:5px;" align="left">

                                        "'.traduz("declaro.que.houve.o.devido.atendimento.do.posto.autorizado.dentro.do.prazo.legal.sendo.realizado.o.conserto.do.produto.e.apos.a.realizacao.dos.testes.ficou.em.perfeitas.condicoes.de.uso.e.funcionamento.deixando.me.plenamente.satisfeito.a").'"
                                        <p>
                                            <div style="float:left">
                                                '.traduz("produto.entregue.em").': '.$recebidoPor.'
                                            </div>
                                            <div style="float:right">
                                                '.traduz("recebido.em").': '.$dataRecebimento.'
                                            </div>
                                        </p>
                                </td>
                            </tr>
                        </table><br /> <br /> <br /> <br /> <br /> <br /> <br /> <br /> <br />
                        ';
        }
        $width_table = ($login_fabrica == 171) ? 300 : 600;
    ?>
        <TABLE width="<?=$width_table ;?>" border="0" cellspacing="0" cellpadding="0">
        <?php
            if(in_array($login_fabrica, array(169,170))){

            ?>
                <TR>
                    <TD width="50%" style='font-size: 10px'>
                        <?= traduz("hora.inicio.visita") ?> __:__
                    </td>
                    <TD width="50%" style='font-size: 10px'>
                        <?= traduz("hora.termino.visita") ?> __:__
                    </td>
            </TR>
                <TR>
                    <TD style='font-size: 08px;<?=$espacamento?>' colspan="3">
                        <? echo "<br><br>Técnico: " . $tecnico_nome_midea ?>  - Assinatura: _____________________________________________________________________________________________
                    </td>
                </tr>
        <?php
            }
        ?>

            <TR>
                <TD style='font: 10px Arial !important;'><?php
                if ($login_fabrica == 2  AND strlen($data_fechamento) > 0) {
                    $data_hj = date('d/m/Y');
                    echo $posto_cidade .", ". $data_hj;
                } else {
                    echo '<br />'.$posto_cidade .", ". $data_abertura.'<br/>';
                }?>
                </TD>
            </TR>
            
            </TD></TR></TD></TD></TR></TABLE>
            <TABLE>
            <?php
            if ($login_fabrica == 171) {
            ?>
            <TR><TD style='font-size: 08px;' colspan='3'><?= traduz("assinatura.do.cliente") ?></TD></TR>
            <?php
            }
            ?>
            <TR><?php
                if ($login_fabrica <> 95) {?>

                    <!-- <TD style='font-size: 08px'><? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: ";?></TD>
     -->
                <?php
                    if($login_fabrica == 11){
                        echo"<td style='font: 10px Arial !important'> *Declaro estar retirando este produto devidamente testado e funcionando.</td>";
                    } elseif ($login_fabrica == 171) {
                ?>
                    <TD style='font-size: 08px;' colspan='1'><strong>Nome</strong><br /><br /><? echo $consumidor_nome ?></TD>
                    <TD style='font-size: 08px;' colspan='1'><strong>Assinatura:</strong><br /><br /><? echo " _____________________________________________________________________________________________" ?></TD>
                    <TD style='font-size: 08px; margin-left: 5px;' colspan='1'><strong>Data:</strong><br /><br /><? echo "|____/____/______"; ?></TD>
                <?php
                    } else {
                        if (!in_array($login_fabrica, array(184,200))) {
                            if ($login_fabrica == 158) {
                                $espacamento = "padding-bottom: 1px;";
                            } else {
                                $espacamento = "";
                            }

                    ?>
    					<TD style='font: 9px Arial !important;<?=$espacamento?>'><? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: _________________________________________";?>     *Declaro estar retirando este produto devidamente testado e funcionando.
    					<? echo "<p style='font-size: 08px; margin-left: 200px;'>";

    				   	if($login_fabrica == 85){ ?>
                            Data do Atendimento:&nbsp; ______/______/_________ </p> 
                        <?php } ?>
                        </TD>
                <?php
                        }//fecha if
                    }//fecha else
                }//fecha if <> 95
                ?>
            </TR>
            <?php if ($login_fabrica == 178){ ?>
                <TD style='font-size: 08px;<?=$espacamento?>'> Responsável pela visita: ______________________________________________________ - Assinatura: ______________________________________________________</TD>
            <?php } ?>
            <?php if($login_fabrica == 153 and $tipo_atendimento == 243){ ?>
            <tr>
                <td style='font-size: 08px'>Assinatura do Posto: <? echo " _____________________________________________________________________________________________ " ?></td>
            </tr>
            <? } ?>

        </TABLE><br><?php
            if ($login_fabrica == 171) {
                echo "<TABLE width='500' border='0' cellspacing='0' cellpadding='0'>
                <TR>
                    <TD style='font-size: 08px;' colspan='4'>Assinatura do Técnico</TD></TR>
                <TR>
                    <TD style='font-size: 08px;' colspan='1'><strong>Nome</strong><br /><br />{$posto_nome}</TD>
                    <TD style='font-size: 08px;' colspan='2'><strong>Assinatura:</strong><br /><br /> _____________________________________________________________________________________________</TD>
                    <TD style='font-size: 08px; margin-left: 5px;' colspan='1'><strong>Data:</strong><br /><br />|____/____/______</TD>
                </TR>
                </TABLE><br />";
            }

    }
           } else {
                if ($login_fabrica == 158) {
                        $espacamento = (strpos($_SERVER['HTTP_USER_AGENT'],'Chrome')) ? "padding-bottom: 300px;" : "padding-bottom: 110px;";
                    } else {
                        $espacamento = "";
                    }

    if(in_array($login_fabrica, array(145))){
        echo "<div style='text-align: left;'>";
            echo "<strong style='font: 12px arial;'>Informações Gerais</strong>";
            for($i = 0; $i <= 9; $i++){
                echo "<div style='border-bottom: 1px solid #999; width: 600px; height: 20px;'></div>";
            }
        echo "</div> <br />";
    }

}

if(in_array($login_fabrica, [167, 203])){
 ?>
    <table width="600px" border="0" cellspacing="2" cellpadding="0">
    <tr>
        <td style='text-align: justify;'>
            <?php
            if ($posto_interno == true) {
            ?>
                <span class='texto'>
                    <br/>
                    <?= traduz("o.orcamento.sera.encaminhado.via.e.mail.apos.analise.tecnica.o.mesmo.devera.ser.respondido.com.aprovacao.ou.reprovacao.do.conserto") ?>.
                    <br/><br/>
                    <?= traduz("nao.aceitamos.cheque.pagamento.somente.dinheiro.ou.cartao") ?>.
                    <br/><br/>
                    <?= traduz("na.reprovacao.do.conserto.o.cliente.ira.adequar.o.produto.nas.mesmas.condicoes.em.que.a.empresa.o.recebeu") ?>.
                    <br/><br/>
                    <?= traduz("na.reprovacao.ou.conserto.do.equipamento.o.cliente.concedera.um.prazo.de.24horas.para.adequar.o.produto") ?>.
                    <br/><br/>
                    <?= traduz("na.hipotese.do.produto.nao.ser.retirado.na.data.mencionada.o.mesmo.sera.depositado.em.juizo.para.destinacao.legal") ?>.
                </span>
                <br/><br/>
                <span class='texto'>
                    <strong><?= traduz("garantia.do.conserto") ?></strong>
                    <br/>
                    <?= traduz("o.produto.descrito.conta.com.a.garantia.legal.de.90.dias.conforme.determinado.pelo.codigo.de.defesa.do.consumidor.contada.a.partir.de.sua.retirada") ?>.
                    <br/><br/>
                    <?= traduz("a.garantia.perdera.sua.validade.se.houver.violacao.do.lacre.colocado.pela.empresa.no.produto.se.for.utilizado.suprimentos.nao.originais.ligado.a.uma.rede.eletrica.impropria.ou.sujeita.a.flutuacoes.instalado.de.maneira.inadequada.caso.sofra.danos.causados.por.acidentes.ou.agentes.da.natureza.tais.como.quedas.batidas.enchentes.descargas.eletricas.raios.conectada.em.voltagem.errada.etc.ou.algum.tipo.de.manutencao.por.pessoas.nao.autorizada.brother") ?>.
                    <br/><br/>
                    <strong><?= traduz("retirada.do.produto") ?></strong>
                    <br/>
                    <?= traduz("solicitamos.que.a.retirada.do.equipamento.seja.dentro.de.60.dias.para.que.nao.haja.taxa.de.armazenagem.taxa.de.r.5.00.por.dia") ?>.
                    <br/><br/>
                    <?= traduz("precisamos.que.o.cliente.tenha.em.maos.a.ordem.de.servico.de.entrada.para.retirada.do.equipamento") ?>.
                </span>
            <?php
            } else { ?>
                 <span class='texto'>
                    <br/>
                    <?php 
                        $show = traduz("o.produto.acima.identificado.possui.garantia.contra.eventuais.defeitos.de.fabricacao.pelo.prazo.estabelecido.no.termo.de.garantia.ja.incluso.nesse.prazo.o.da.garantia.legal.de.90.noventa.dias.contados.da.data.da.aquisicao.do.produto.pelo.primeiro.consumidor");
                        if (in_array($login_fabrica, [203])) { 
                            $show = str_replace("?", "", $show);
                        } 

                        echo $show;
                        ?>.
                    <br/><br/>
                    <?= traduz("as.partes.plasticas.pecas.avulsas.e.os.suprimentos.possuem.apenas.a.garantia.legal.de.90.dias.corridos.a.partir.da.data.de.compra.o.produto.que.apresentar.defeito.de.fabricacao.durante.esse.prazo.sera.reparado.gratuitamente.pelo.servico.tecnico.autorizado") ?>.
                </span>
                <br/><br/>
                <span class='texto'>
                    <?= traduz("a.validade.da.garantia.e.condicionada.a.apresentacao.do.original.da.primeira.via.da.nota.fiscal.de.venda.no.brasil.guarde.sua.nota.fiscal.a.garantia.e.valida.somente.para.os.produtos.vendidos.no.brasil.e.que.tenham.sido.colocados.no.mercado.brasileiro.pela.brother.international.corporation.do.brasil.ltda") ?>.
                    <br/><br/>

                    <?php if (in_array($login_fabrica, [203])) { 
                        echo "Consulte o status desta ordem de serviço no <a href='https://www.brother.com.br/Support/' target='_blank'>https://www.brother.com.br/Support/</a>. <br /> <br />";
                    
                        echo "BROTHER | Help Line: 4020-6314 (Capitais e regiões metropolitanas) ou 0800 023 0568.";
                    } else { ?>
                        <?= traduz("duvidas.ou.reclamacoes.contate.nosso.canal.de.atendimento") ?>.
                        Help Line Brother: 4020-6314 (Capitais e regiões metropolitanas) ou 0800 023 0568
                    <?php } ?>
                </span>
            <?php
            }
            ?>
        </td>
    </tr>
    </table>
    <table width="600px" border="0" cellspacing="2" cellpadding="0">
        <tr>
            <td style='font: 8px arial;'>
            <br/><br/>
            Em, <?echo $posto_cidade .", ". $data_abertura;?>
            </td>
        </tr>
        <tr>
            <td>
                <br/>
                <hr class='assinatura'></hr>
                <br/>
                <span style='padding-left: 58px; font: 8px arial;'><?= traduz("assinatura.cliente") ?></span>
            </td>
            <td>
                <br/>
                <hr class='data_entrada'></hr>
                <br/>
                <span style='padding-left: 28px; font: 8px arial;'><?= traduz("data.entrada") ?></span>
            </td>
        </tr>
        <tr>
            <td>
                <br/><br/>
                <hr class='assinatura'></hr>
                <br/>
                <span style='padding-left: 58px; font: 8px arial;'><?= traduz("assinatura.cliente") ?></span>
            </td>
            <td>
                <br/><br/>
                <hr class='data_entrada'></hr>
                <br/>
                <span style='padding-left: 8px; font: 8px arial;'><?= traduz("data.retirada.do.produto") ?></span>
            </td>
        </tr>
    </table>
    <br/>
    <?php
    }
    if (in_array($login_fabrica, array(184,200))) {
?>
    <table width="600px" border="0" cellspacing="2" cellpadding="0">
        <tr>
            <td>
                <br/>
                <hr class='assinatura'></hr>
                <br/>
                <span style='padding-left: 58px; font: 8px arial;'><?= traduz("assinatura.cliente") ?></span>
            </td>
            <td>
                <br/>
                <hr class='data_entrada'></hr>
                <br/>
                <span style='padding-left: 28px; font: 8px arial;'><?= traduz("data.entrada") ?></span>
            </td>
        </tr>
        <tr>
            <td>
                <br/><br/>
                <hr class='assinatura'></hr>
                <br/>
                <span style='padding-left: 58px; font: 8px arial;'><?= traduz("assinatura.cliente") ?></span>
            </td>
            <td>
                <br/><br/>
                <hr class='data_entrada'></hr>
                <br/>
                <span style='padding-left: 8px; font: 8px arial;'><?= traduz("data.retirada.do.produto") ?></span>
            </td>
        </tr>
    </table>
    <br/>
    <?php
    }

    if($login_fabrica == 158){ ?>
        <table width="600px" border="0" cellspacing="2" cellpadding="0" style="margin-bottom: 25px">
            <tr>
                <td>
                    <span style='font: 12px arial;'><?= traduz("email"). ':  __________________________________________' ?> </span>
                </td>
                <td>
                    <span style='padding-left: 8px; font: 12px arial;'><?= traduz("celular"). ': <b> (____)____________________ </b>' ?>  </span>
                </td>
            </tr>
            <tr><td colspan="100%"><span style="font: 11px arial">Olá,<br>
                Você receberá através de uma mensagem de texto e/ou e-mail em seu celular, um link para responder a nossa pesquisa
                de satisfação sobre este atendimento. Sua opinião nos ajudará a medir nossos produtos e serviços, a fim de proporcionar melhores
                experiências de consumo.<br>
                Contamos com seu apoio para nos ajudar a sermos melhores!</span></td></tr>
        </table>
    <?php }
if(!in_array($login_fabrica,[161])){
?>

<table style="width:650px;margin:-20px 0 0 -10px;">
    <tr>
        <td>
            <span style="margin-left:8px;font-size:10px;font-family:'Arial'"><?= traduz("anexar.arquivos.via.mobile") ?></span>
        </td>
    </tr>
    <tr>
        <td>
            <img src="" class="qr_press" style="display:none;width:100px;">
        </td>
    </tr>
</table>
<?php
}
if ($login_fabrica == 2 AND strlen($peca) > 0 AND strlen($data_fechamento) > 0) {
    echo $topo_peca.$peca_dynacom;
} else if ($login_posto <> '14236' && $login_fabrica <> 85) { //chamado = 1460 ?>

    <?php
    if($login_fabrica==91) {
        echo $topo_peca.$peca_dynacom;
    }

    if (!in_array($login_fabrica, array(19))) { ?>
    <TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
        </TR>
    </TABLE>

    <?php
    }
    ?>


    <?php

    if($login_fabrica == 52){
        echo "
            <TABLE width='600px' border='0' cellspacing='2' cellpadding='0'>
                <TR>
                    <TD align='right'>
                        <strong style='font: 14px arial; font-weight: bold;''>Via da Fábrica</strong>
                    </td>
                </tr>
            </table>";
        ?>

            <TABLE width="600px" border="0" cellspacing="0" cellpadding="3" style="font: 9px arial;">
                <TR>
                    <TD>
                        <strong><?= traduz("diagnostico.pecas.usadas.e.resolucao.do.problema") ?>:</strong> <br />
                        Técnico:
                    </TD>
                </TR>
                <TR>
                    <TD>
                    Em,
                        <?
                        if($login_fabrica==2  AND strlen($data_fechamento)>0){
                            $data_hj = date('d/m/Y');
                            echo $posto_cidade .", ". $data_hj;
                        }else{
                            echo $posto_cidade .", ". $data_abertura;
                        } ?>
                    </TD>
                </TR>

                <?php if ($login_fabrica == 52) {?>

                    <TR>
                        <TD style="font:8px arial; text-align: center; border: 1px dashed #999; padding: 5px;">
                            <?= traduz("declaro.que.o.meu.pedido.de.visita.tecnica.foi.atendido.e.que.o.produto.de.minha.propriedade.ficou.em.perfeita.condicao") ?>.
                        </TD>
                    </TR>

                <?php } ?>

                <TR>
                    <TD style='<?php echo ($login_fabrica != 52) ? "border-bottom:solid 1px" : ""; ?>;'><? echo $consumidor_nome ?> - Assinatura:</TD>
                </TR>
                    <? /*if($login_fabrica == 11){?>
                <tr>


                      <td> Declaro estar retirando este produto devidamente testado e funcionando.</td>

                </tr>
                   <?php }*/?>

                <tr>
                    <td style="font: 10px arial;"><strong>OS</strong> <?=$sua_os?> &nbsp; <strong>Ref.</strong> <?=$referencia?> &nbsp; <strong>Descr.</strong> <?=$descricao?> &nbsp; <strong>N.Série</strong> <?=$serie?> &nbsp; <strong>Tel.</strong> <?=$consumidor_fone?> </td>
                </tr>

            </TABLE>


        <?php
    }

?>

    <TABLE border='1' cellspacing="0" cellpadding="0">

        <TR><?php

        for ($i = 0; $i < $qtd_etiqueta_os; $i++) {

            if ($i %  $qtd_etiqueta_os == 0) {
                echo "</TR><TR> ";
            }

            if ($login_fabrica <> 59) {?>
                <TD class="etiqueta" <?=$estilo?> ><?php
            } else {?>
              <TD class="etiqueta" style="width:10cm; height:3.5cm;" <?=$estilo?> ><?php # ALTERAÇÃO DA DE LxA da Coluna...HD 337864
            }

            if ($login_fabrica == 43) {

                $sql_cons = "SELECT tbl_defeito_constatado.defeito_constatado,
                                    tbl_defeito_constatado.descricao         ,
                                    tbl_defeito_constatado.codigo,
                                    tbl_solucao.solucao,
                                    tbl_solucao.descricao as solucao_descricao
                            FROM tbl_os_defeito_reclamado_constatado
                            JOIN tbl_defeito_constatado USING(defeito_constatado)
                            LEFT JOIN tbl_solucao USING(solucao)
                            WHERE os = $os";

                $res_dc = pg_exec($con, $sql_cons);

                if (pg_numrows($res_dc) > 0) {

                    for ($x = 0; $x < pg_numrows($res_dc); $x++) {
                        $dc_defeito_constatado .= pg_result($res_dc,$x,'descricao').", ";
                    }
                }
                echo  "<b><font size='2px'>OS $sua_os</font></b><BR>Defeito $dc_defeito_constatado <BR>Posto $posto_nome <BR>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone<br>Nº. OS: $os";
                $dc_defeito_constatado = "";
            } else {
                # HD 337864
                if ($login_fabrica == 59) {
                    echo "Destinatário:<br/>$consumidor_nome<br/>$consumidor_endereco $consumidor_numero $consumidor_complemento<br/>$consumidor_bairro $consumidor_cep<br/>$consumidor_cidade - $consumidor_estado<br>OS: $sua_os";
                }elseif($login_fabrica == 3){
                    echo "<font size='2px'><b>OS $sua_os</b></font><BR></b>Desc. $descricao . <br>$consumidor_nome";
                }else{
                    if(!in_array($login_fabrica, array(117))){

                        echo "<font size='2px'><b>OS $sua_os</b></font><BR>Ref. $referencia </b> <br> $descricao . <br>";

                        if($login_fabrica <> 127){
                            echo "N.Série $serie<br>";
                        }

                        echo "$consumidor_nome<br>$consumidor_fone";
                        if($login_fabrica == 35){
                            echo "<br>$revenda_nome";
                        }
                    }
 
                    if($login_fabrica == 117){
                        if($consumidor_revenda == "CONSUMIDOR"){
                            echo "<font size='2px'><b>OS $sua_os</b></font><br><b>$posto_nome </b><br> $descricao<br>N.Série $serie<br>$consumidor_nome<br>$data_abertura";

                        }else{
                            echo "<font size='2px'><b>OS $sua_os</b></font><BR>$posto_nome </b><br>$descricao <br>N.Série $serie<br>$revenda_nome<br>$data_abertura";
                        }
                    }
                }
            }?>
            </TD><?php
        }?>
        </TR>
    </TABLE>
    <?php if ($login_fabrica == 164) { ?>
    <TABLE width='700' border='0' cellspacing='0' cellpadding='0'>
        <TR>
            <TD style='font-size: 12px;' colspan='4'><br /><br /><u><center>ENTRADA</center></u></TD>
        </TR>
        <TR>
            <TD style='font-size: 12px; padding-left: 5px;' colspan='1'>DATA DE ENTRADA:&nbsp;&nbsp;&nbsp;&nbsp;____/____/______</TD>
        </TR>
        <TR>
            <TD style='font-size: 12px;' colspan='1'><br />Nome: ........................................................................, declaro veracidade das informações descritas.</TD>
        </TR>
        <TR>
            <TD style='font-size: 12px;' colspan='1'><br />CPF: .......... .......... .......... - .......... &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Email: ........................................................................ </TD>
        </TR>
        <TR>
            <TD style='font-size: 12px;' colspan='1'><br />Tel: (.............) ............................................................</TD>
        </TR>
        <TR>
            <TD style='font-size: 12px;' colspan='2'><br /><br />Assinatura: &nbsp; ______________________________________________</TD>
        </TR>
    </TABLE><br />
    <TABLE width='700' border='0' cellspacing='0' cellpadding='0'>
        <TR>
            <TD style='font-size: 12px;' colspan='4'><br /><br /><u><center>SAÍDA</center></u></TD>
        </TR>
        <TR>
            <TD style='font-size: 12px; padding-left: 5px;' colspan='1'>DATA DE SAÍDA:&nbsp;&nbsp;&nbsp;&nbsp;____/____/______</TD>
        </TR>
        <TR>
            <TD style='font-size: 12px;' colspan='1'><br />Nome: ........................................................................, declaro que retirei o produto <span id="select_produto"></span>&nbsp;<select id="tipo_produto"><option value=""></option><option value="consertado">Consertado</option><option value="trocado">Trocado</option></select></TD>
        </TR>
        <TR>
            <TD style='font-size: 12px;' colspan='1'><br />acima descrito na data de ____/____/______ em perfeitas condições de uso. </TD>
        </TR>
        <TR>
            <TD style='font-size: 12px;' colspan='2'><br /><br />Assinatura: &nbsp; ______________________________________________</TD>
        </TR>
    </TABLE><br />
    <?php } ?>

    <script>
    $('#tipo_produto').blur(function () { 
        $('#select_produto').html($('#tipo_produto').find(':selected').val()); 
    });
    </script>

    <?php 

}

// HD 3741276 - QRCode
include_once 'os_print_qrcode.php';

// $os_include = true;
//HD 371911

if ($fabricaFileUploadOS) {

include 'TdocsMirror.php';
include 'controllers/ImageuploaderTiposMirror.php';

$imageUploaderTipos = new ImageuploaderTiposMirror($login_fabrica,$con);

try{
    $comboboxContext = $imageUploaderTipos->get();
}catch(\Exception $e){    
    $comboboxContext = [];
}

foreach ($comboboxContext as $key => $value) {
    foreach ($comboboxContext[$key] as $idx => $value) {
        $value['label'] = traduz(utf8_decode($value['label']));
        $value['value'] = utf8_decode($value['value']);
        $comboboxContext[$key][$idx] = $value;
    }
}    

$comboboxContextJson = [];
$comboboxContextOptionsAux = [];
foreach ($comboboxContext as $context => $options) {
    foreach ($options as $value) {
        $comboboxContextOptionsAux[$value['value']] = $value['label'];
        $comboboxContextJson[$context][] = $value["value"];
    }
}
if($contexto != ""){
    $contextOptions = $comboboxContext[$contexto];
    foreach ($contextOptions as $key => $value) {
        $value['label'] = utf8_encode($value['label']);
        $contextOptionsJson[$key] = $value;
    }
}

?>
<script type="text/javascript">
	getQrCode();
	var fabrica = <?=$login_fabrica?>;
    function getQrCode() {
        $.ajax("controllers/QrCodeImageUploader.php",{
            async: true,
            type: "POST",
            data: {
                "ajax": "requireQrCode",
                "options": <?=json_encode($comboboxContextJson["os"])?>,
                "title": 'Upload de Arquivos',
                "objectId": <?=$_GET['os']?>,
                "contexto": "os",
                "fabrica": <?=$login_fabrica?>,
                "hashTemp": "false",
                "print": "true"
            }
        }).done(function(response){
            $(".qr_press").attr("src",response.qrcode)          
            $(".qr_press").show('fast', function () {
				if (fabrica == 164) {
					window.addEventListener('beforeprint', function () {
						$('#select_produto').append($('#tipo_produto').find(':selected').val());
						$('#tipo_produto').hide();
					});
					$('#tipo_produto').change(function() {
						setTimeout(3000);
						window.print();
					});
				}else{
                    window.print();
				}
                
            });
        });
    }
</script>
<?php } elseif (!isset($os_include)) { ?>
    <script language="JavaScript">
        window.print();
    </script>
<?php } ?>
</body>
</html>
