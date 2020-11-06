<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if($login_fabrica == 1){
    include("os_print_blackedecker_matricial.php");
    exit;
}

if($login_fabrica == 30){
    include("os_print_matricial_esmaltec.php");
    exit;
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

$os   = intval($_GET['os']);
//HD 371911
$os              = (!$os && isset($os_include)) ? $os_include : $os;
$modo = $_GET['modo'];

//Adicionando validação da OS para posto e fábrica
if (strlen($os)) {
    $sql = "SELECT os FROM tbl_os WHERE os=$os AND fabrica=$login_fabrica AND posto=$login_posto";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) == 0) {
        echo "OS não encontrada";
        die;
    }
}

if ($login_fabrica == 7) {
#   header ("Location: os_print_filizola.php?os=$os&modo=$modo");
    header ("Location: os_print_manutencao.php?os=$os&modo=$modo");
    exit;
}

#------------ Le OS da Base de dados ------------#
if (strlen ($os) > 0) {
    $col_serie = isset($novaTelaOs)  ? 'tbl_os_produto.serie' : 'tbl_os.serie';
    $sql =  "SELECT tbl_os.os                                                      ,
                    tbl_os.sua_os                                                  ,
                    to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
                    to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                    tbl_produto.produto                                            ,
                    tbl_produto.referencia                                         ,
                    tbl_produto.referencia_fabrica                                 ,
                    tbl_produto.descricao                                          ,
                    tbl_produto.qtd_etiqueta_os                                    ,
                    tbl_os_extra.serie_justificativa                               ,
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
                    tbl_os.capacidade                                              ,
                    tbl_os.revenda_cnpj                                            ,
                    tbl_os.revenda_nome                                            ,
                    tbl_os.nota_fiscal                                             ,
                    to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf        ,
                    tbl_os.defeito_reclamado                                       ,
                    tbl_os.defeito_reclamado_descricao                             ,
                    tbl_os.consumidor_nome_assinatura AS contato_consumidor        ,
                    tbl_os.condicao AS contador                                    ,
                    tbl_os.acessorios                                              ,
                    tbl_os.aparencia_produto                                       ,
                    tbl_os.rg_produto                                              ,
                    tbl_os.finalizada                                              ,
                    tbl_os.data_conserto                                           ,
                    tbl_os.obs                                                     ,
                    tbl_os.qtde_km                                                 ,
                    tbl_posto.nome                                                 ,
                    tbl_posto_fabrica.contato_endereco   as endereco               ,
                    tbl_posto_fabrica.contato_numero     as numero                 ,
                    tbl_posto_fabrica.contato_cep        as cep                    ,
                    tbl_posto_fabrica.contato_cidade     as cidade                 ,
                    tbl_posto_fabrica.contato_estado     as estado                 ,
                    tbl_posto_fabrica.contato_fone_comercial as fone               ,
                    tbl_posto.cnpj                                                 ,
                    tbl_posto.ie                                                   ,
                    tbl_posto.pais                                                 ,
                    tbl_posto_fabrica.contato_email as email                       ,
                    tbl_os.consumidor_revenda                                      ,
                    tbl_os.tipo_os                                                 ,
                    tbl_os.tipo_atendimento                                        ,
                    tbl_os.tecnico_nome                                            ,
                    tbl_os.tecnico                                                 ,
                    tbl_tipo_atendimento.descricao              AS nome_atendimento,
                    tbl_tipo_atendimento.codigo                 AS codigo_atendimento,
                    tbl_os.qtde_produtos                                           ,
                    tbl_os.excluida                                                ,
                    tbl_os.certificado_garantia                                    ,
                    tbl_os.cortesia                                                ,
                    tbl_os.prateleira_box                                          ,
                    tbl_defeito_constatado.descricao          AS defeito_constatado,
                    tbl_os_extra.hora_tecnica                                      ,
                    tbl_os_extra.qtde_horas                                        ,
                    tbl_os_extra.obs_adicionais                                    ,
                    tbl_solucao.descricao                                AS solucao,
                    upper(tbl_linha.nome) AS linha,
                    tbl_os.qtde_hora,
                    tbl_os.hora_tecnica as os_hora_tecnica ";

            if ($login_fabrica == 176)
            {
                $sql .= " , tbl_os.type ";
            }

            if(isset($novaTelaOs)){
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
        $os                             = pg_result ($res,0,os);
        $sua_os                         = pg_result ($res,0,sua_os);
        $data_abertura                  = pg_result ($res,0,data_abertura);
        $data_fechamento                = pg_result ($res,0,data_fechamento);
        $referencia                     = pg_result ($res,0,referencia);
        $modelo                         = pg_result ($res,0,referencia_fabrica);
        $produto                        = pg_result ($res,0,produto);
        $descricao                      = pg_result ($res,0,descricao);
        $serie_justificativa            = pg_result ($res,0,serie_justificativa);
        $serie                          = pg_result ($res,0,serie);
        $codigo_fabricacao              = pg_result ($res,0,codigo_fabricacao);
        $cliente                        = pg_result ($res,0,cliente);
        $revenda                        = pg_result ($res,0,revenda);
        $consumidor_cpf                 = pg_result ($res,0,consumidor_cpf);
        $consumidor_nome                = pg_result ($res,0,consumidor_nome);
        $consumidor_endereco            = pg_result ($res,0,consumidor_endereco);
        $consumidor_numero              = pg_result ($res,0,consumidor_numero);
        $consumidor_complemento         = pg_result ($res,0,consumidor_complemento);
        $consumidor_bairro              = pg_result ($res,0,consumidor_bairro);
        $consumidor_cidade              = pg_result ($res,0,consumidor_cidade);
        $consumidor_estado              = pg_result ($res,0,consumidor_estado);
        $consumidor_cep                 = pg_result ($res,0,consumidor_cep);
        $consumidor_fone                = pg_result ($res,0,consumidor_fone);
        $consumidor_celular             = pg_result ($res,0,consumidor_celular);
        $consumidor_fonecom             = pg_result ($res,0,consumidor_fonecom);
        $consumidor_email               = strtolower(trim (pg_result ($res,0,consumidor_email)));
        $revenda_cnpj                   = pg_result ($res,0,revenda_cnpj);
        $revenda_nome                   = pg_result ($res,0,revenda_nome);
        $nota_fiscal                    = pg_result ($res,0,nota_fiscal);
        $data_nf                        = pg_result ($res,0,data_nf);
        $defeito_reclamado              = pg_result ($res,0,defeito_reclamado);
        $aparencia_produto              = pg_result ($res,0,aparencia_produto);
        $acessorios                     = pg_result ($res,0,acessorios);
        $defeito_cliente                = pg_result ($res,0,defeito_cliente);
        $defeito_reclamado_descricao    = pg_result ($res,0,defeito_reclamado_descricao);
        $posto_nome                     = pg_result ($res,0,nome);
        $posto_endereco                 = pg_result ($res,0,endereco);
        $posto_numero                   = pg_result ($res,0,numero);
        $posto_cep                      = pg_result ($res,0,cep);
        $posto_cidade                   = pg_result ($res,0,cidade);
        $posto_estado                   = pg_result ($res,0,estado);
        $posto_fone                     = pg_result ($res,0,fone);
        $posto_cnpj                     = pg_result ($res,0,cnpj);
        $posto_ie                       = pg_result ($res,0,ie);
        $posto_email                    = pg_result ($res,0,email);
        $sistema_lingua                 = strtoupper(trim(pg_result ($res,0,pais)));
        $consumidor_revenda             = pg_result ($res,0,consumidor_revenda);
        $finalizada                     = pg_result($res,0,finalizada);
        $data_conserto                  = pg_result($res,0,data_conserto);
        $obs                            = pg_result ($res,0,obs);
        if ( !in_array($login_fabrica, array(7,11,15,172)) ) {
            $box_prateleira  = trim(pg_fetch_result($res, 0, 'prateleira_box'));
        }
        $qtde_produtos                  = pg_result ($res,0,qtde_produtos);
        $excluida                       = pg_result ($res,0,excluida);
        $tipo_atendimento               = trim(pg_result($res,0,tipo_atendimento));
        $tecnico_nome                   = trim(pg_result($res,0,tecnico_nome));
        $tecnico                        = trim(pg_result($res,0,tecnico));
        $nome_atendimento               = trim(pg_result($res,0,nome_atendimento));
        $codigo_atendimento               = trim(pg_result($res,0,codigo_atendimento));
        $defeito_constatado             = trim(pg_result($res,0,defeito_constatado));
        $solucao                        = trim(pg_result($res,0,solucao));
        $qtd_etiqueta_os                = trim(pg_result($res,0,qtd_etiqueta_os));
        $tipo_os                        = trim(pg_result($res,0,tipo_os));
        $hora_tecnica                   = pg_fetch_result ($res,0,'hora_tecnica');
        $qtde_horas                     = pg_fetch_result ($res,0,'qtde_horas');
        $qtde_km                        = pg_fetch_result ($res,0,'qtde_km');
        $certificado_garantia           = trim(pg_result($res,0,'certificado_garantia'));

        if ($login_fabrica == 148) {
            $os_horimetro = pg_fetch_result($res, 0, "qtde_hora");
            $os_revisao = pg_fetch_result($res, 0, "os_hora_tecnica");

            $obs_adicionais_json = json_decode(pg_fetch_result($res, 0, "obs_adicionais"));

            $serie_motor       = $obs_adicionais_json->serie_motor;
            $serie_transmissao = $obs_adicionais_json->serie_transmissao;
        }

        if ($login_fabrica == 175){
            $qtde_disparos = pg_fetch_result($res, 0, 'capacidade');
        }

        if(in_array($login_fabrica, [167, 203])){
            $contato_consumidor = pg_fetch_result($res, 0, 'contato_consumidor');
            $contador           = pg_fetch_result($res, 0, 'contador');
        }

        if($login_fabrica == 137){

            $dados  = pg_result($res,0,rg_produto);

            $dados          = json_decode($dados);
            $cfop           = $dados->cfop;
            $valor_unitario = $dados->vu;
            $valor_nota     = $dados->vt;

        }

        if ($login_fabrica == 176)
        {
            $indice = pg_fetch_result($res, 0, type);
        }

        $certificado_garantia = ($certificado_garantia AND $certificado_garantia != "null") ? "$certificado_garantia" : "";

        $cortesia                   = pg_result($res,0,cortesia);
        $cortesia = ($cortesia == "t") ? "Sim" : "Não";
        $os_de_garantia = (strlen($certificado_garantia) > 0 AND $certificado_garantia != "null") ? "Sim" : "Não";

        $obs_adicionais              = json_decode(utf8_encode(pg_fetch_result ($res,0,'obs_adicionais')),true);
        $linha                          = trim(pg_result($res,0,'linha'));

        if(strlen($sistema_lingua) == 0) $sistema_lingua = 'BR';

        if($sistema_lingua <>'BR') {
            $lingua = "ES";
        }
        else {
            $lingua = "BR";
        }

        if (strlen($tecnico) > 0) {
            $sql = "SELECT nome FROM tbl_tecnico WHERE tecnico=$tecnico";
            $res_tecnico = pg_query($con, $sql);

            if (pg_num_rows($res_tecnico)) {
                $tecnico_nome = pg_result($res_tecnico, 0, nome);
            }
        }

        if(strlen($qtd_etiqueta_os)==0){
            $qtd_etiqueta_os=5;
        }

        if(in_array($login_fabrica,array(2,20,46,91,115,116,117,120,201,123,124,125,126,127,128,129,131,134,136)) || isset($novaTelaOs)){//HD 21549 27/6/2008

            $cond_left = (in_array($login_fabrica, array(20))) ? " LEFT " : "";

            $sql_item = "SELECT tbl_os_item.peca                              ,
                tbl_peca.referencia             AS peca_referencia            ,
                tbl_peca.descricao              AS peca_descricao             ,
                tbl_os_item.qtde                AS peca_qtde                  ,
                tbl_os_item.defeito                                           ,
                tbl_os_item.custo_peca                                        ,
                tbl_defeito.descricao           AS  descricao_defeito         ,
                tbl_os_item.porcentagem_garantia                              ,
                tbl_os_item.os_por_defeito                                    ,
                tbl_os_item.peca_serie                                        ,
                tbl_os_item.servico_realizado                                 ,
                tbl_servico_realizado.descricao AS  descricao_servico_realizado
                FROM tbl_os_item
                JOIN tbl_os_produto USING(os_produto)
                JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
                LEFT JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_item.defeito AND tbl_defeito.fabrica = $login_fabrica
                {$cond_left} JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
                JOIN tbl_os ON tbl_os.os = tbl_os_produto.os where tbl_os.os = $os";
            $res_item = pg_exec($con, $sql_item);
            if(pg_numrows($res_item)>0){
                $peca_dynacom  = "<TABLE width='600px' border='0' cellspacing='0' cellpadding='0'>";
                $peca_dynacom .= "<TR>";
                $peca_dynacom .= "<TD colspan='4'><BR></TD>";
                $peca_dynacom .= "</TR>";
                $peca_dynacom .= "<TR>";
                $peca_dynacom .= "<TD class='titulo'>".traduz('peca')."</TD>";

                if ($login_fabrica == 175){
                    $peca_dynacom .= "<TD class='titulo'>SÉRIE</TD>";
                    $peca_dynacom .= "<TD class='titulo'>QTDE DISPAROS</TD>";
                    $peca_dynacom .= "<TD class='titulo'>COMPONENTE RAIZ</TD>";
                }

                $peca_dynacom .= "<TD class='titulo'>".traduz('quantidade')."</TD>";

                if(!in_array($login_fabrica,array(20,46,115,116,117,123,124,125,126,127,128,129,131,134,136))){

                    $peca_dynacom .= "<TD class='titulo'>".traduz('defeito')."</TD>";
                }
                $peca_dynacom .= "<TD class='titulo'>".traduz("servico")."</TD>";

                if($login_fabrica == 148){
                    $peca_dynacom .= "
                    <td class='titulo' style='text-align: center;'>".traduz('defeito')."</td>
                    <td class='titulo' style='text-align: center;'>".traduz('valor.unitario')."</td>
                    <td class='titulo' style='text-align: center;'>".traduz('valor.total')."</td>
                    ";
                }

                $peca_dynacom .= "</TR>";

                for($z=0; $z<pg_numrows($res_item); $z++){
                    $peca                        = pg_result($res_item, $z, peca);
                    $peca_referencia             = pg_result($res_item, $z, peca_referencia);
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

                    if ($login_fabrica == 175){
                        $numero_serie_peca = pg_fetch_result($res_item, $z, "peca_serie");
                        $qtde_disparos_peca = pg_fetch_result($res_item, $z, "porcentagem_garantia");
                        $componente_raiz = pg_fetch_result($res_item, $z, "os_por_defeito");

                        $peca_dynacom .= "<TD class='conteudo'>$numero_serie_peca</TD>";
                        $peca_dynacom .= "<TD class='conteudo'>$qtde_disparos_peca</TD>";
                        $peca_dynacom .= "<TD class='conteudo'>".(($componente_raiz == "t") ? "SIM" : "NÃO")."</TD>";
                    }

                    $peca_dynacom .= "<TD class='conteudo'>$peca_qtde</TD>";

                    if(!in_array($login_fabrica,array(20,46,115,116,117,123,124,125,126,127,128,129,131,134,136))){

                        $peca_dynacom .= "<TD class='conteudo'>$descricao_defeito</TD>";
                    }
                    $peca_dynacom .= "<TD class='conteudo'>$descricao_servico_realizado</TD>";

                    if($login_fabrica == 148){

                        $qtde_peca      = (strlen(pg_fetch_result($res_item,$z,"peca_qtde")) == 0) ? 0 : pg_fetch_result($res_item,$z,"peca_qtde");
                        $valor_total    = (strlen(pg_fetch_result($res_item,$z,"custo_peca")) == 0) ? 0 : number_format(pg_fetch_result($res_item,$z,"custo_peca"), 2);
                        $valor_unitario = number_format($valor_total / $qtde_peca, 2);

                        $peca_dynacom .= "
                        <td class='conteudo' style='text-align: center;'>{$qtde_peca}</td>
                        <td class='conteudo' style='text-align: center;'>{$valor_unitario}</td>
                        <td class='conteudo' style='text-align: center;'>{$valor_total}</td>
                        ";
                    }

                    $peca_dynacom .= "</TR>";
                }
                $peca_dynacom .= "</TABLE>";
            }
        }


        $query_adicionais = "SELECT campos_adicionais 
               FROM tbl_os_campo_extra 
               WHERE os = {$os}";

        $res_adicionais = pg_query($con, $query_adicionais);

        $campos_adicionais = pg_fetch_result($res_adicionais, 0, campos_adicionais);

        $campos_adicionais = json_decode($campos_adicionais);

        if ($login_fabrica == 131) {
            
            $peca_dynacom .= "<TR>";
                $peca_dynacom .= "<TD colspan='4'><BR></TD>";
            $peca_dynacom .= "</TR>";
            $peca_dynacom .= "<TR>";
                $peca_dynacom .= "<TD class='titulo'>Sobre a(s) peça(s)</TD>";
                if ($campos_adicionais->tipo_envio_peca == "utilizar_estoque") {
                    $peca_dynacom .= "<TD class='titulo'>Prazo Entrega</TD>";
                }
            $peca_dynacom .= "</TR>";
                   $peca_dynacom .= "<TR>";
                if ($campos_adicionais->tipo_envio_peca == "utilizar_estoque") {
                    $peca_dynacom .= "<TD class='conteudo'>Utilizar as peças do estoque da assistência</TD>";
                    $peca_dynacom .= "<TD class='conteudo'>" . date("d/m/Y", strtotime($campos_adicionais->previsao_entrega)) . "</TD>";
                } else {
                    $peca_dynacom .= "<TD class='conteudo'>Aguardar as peças serem enviadas pela fábrica</TD>";
                }
            $peca_dynacom .= "</TR>";
        }
   

        if ($login_fabrica == 231) {
            $peca_dynacom  = "<TABLE width='600px' border='0' cellspacing='0' cellpadding='0'>";
            $peca_dynacom .= "<TR>";
            $peca_dynacom .= "<TD colspan='4'><BR></TD>";
            $peca_dynacom .= "</TR>";
            $peca_dynacom .= "<TR>";
            $peca_dynacom .= "<TD class='titulo'>".traduz('Trem')."</TD>";

            $peca_dynacom .= "<TD class='titulo'>".traduz('tremzao')."</TD>";

        }
        //--=== Tradução para outras linguas ============================= Raphael HD:1212
        $sql_idioma = " SELECT * FROM tbl_produto_idioma
                        WHERE produto     = $produto
                        AND upper(idioma) = '$lingua'";
        $res_idioma = @pg_exec($con,$sql_idioma);
        if (@pg_numrows($res_idioma) >0) {
            $descricao  = trim(@pg_result($res_idioma,0,descricao));
        }

       if (strlen($defeito_reclamado)>0) {
            $sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
                            WHERE defeito_reclamado = $defeito_reclamado
                            AND upper(idioma)        = '$lingua'";
            $res_idioma = @pg_exec($con,$sql_idioma);
            if (@pg_numrows($res_idioma) >0) {
                $defeito_cliente  = trim(@pg_result($res_idioma,0,descricao));
            }
       }

    if(strlen($tipo_atendimento)>0){
        $sql_idioma = " SELECT * FROM tbl_tipo_atendimento_idioma
                WHERE tipo_atendimento = '$tipo_atendimento'
                AND upper(idioma)   = '$lingua'";
        $res_idioma = @pg_exec($con,$sql_idioma);
        if (@pg_numrows($res_idioma) >0) {
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
                $revenda_endereco    = strtoupper(trim(pg_result ($res1,0,endereco)));
                $revenda_numero      = trim(pg_result ($res1,0,numero));
                $revenda_complemento = strtoupper(trim(pg_result ($res1,0,complemento)));
                $revenda_bairro      = strtoupper(trim(pg_result ($res1,0,bairro)));
                $revenda_cep         = trim(pg_result ($res1,0,cep));
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
//echo $sql;

}

function convertDataBR($data){
    $dt = explode('-',$data);

    return $dt[2].'/'.$dt[1].'/'.$dt[0];
}

if (strlen($sua_os) == 0) $sua_os = $os;

$title = traduz('ordem.de.servico.balcao')." - ".traduz("impressao");
//echo "$qtde_produtos";
?>

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

</head>

<? if($login_posto <> '14236'){ ?>

<style type="text/css">
body {
    margin: 0px;
    font-family: monospace;
}

.texto_termos{
    width: 600px;
    margin-top:3px;

}

.texto_termos p{
    font: bold 10px "Courier New";
    font-family: monospace;
    text-align: justify;
    margin: 0 0 5px 0;
}

.titulo {
    font-size: 12px;
    font-weight: bold;
    text-align: left;
    color: #000000;
    background: #ffffff;
    border-bottom: dotted 0px #000000;
    /*border-right: dotted 1px #a0a0a0;*/
    border-left: dotted 0   px #000000;
    padding: 0px,0px,0px,0px;
}

.titulo2{
    font-size: 13px;
    font-weight: bold;
    font-family: Arial;
    border-bottom: 1px solid #000000;
    text-align:center;
    background-color: #cccccc;
}

.conteudo {
    font-size: 13px;
    text-align: left;
    background: #ffffff;
    border-right: dotted 0px #a0a0a0;
    border-left: dotted 0px #a0a0a0;
    padding: 1px,1px,1px,1px;
}

.borda {
    border: solid 0px #c0c0c0;
}

.etiqueta {
    font-size: 11px;
    width: 110px;
    text-align: center
}

h2 {
    color: #000000
}
</style>
<? }else{ ?>

<style type="text/css">
body {
    margin: 0px;
    font-family: monospace;
}

.titulo {
    font-size: 12px;
    text-align: left;
    color: #000000;
    background: #ffffff;
    border-bottom: solid 1px #c0c0c0;
    /*border-right: dotted 1px #a0a0a0;*/
    border-left: solid 1px #c0c0c0;
    padding: 1px,1px,1px,1px;
}

.conteudo {
    font-size: 13px;
    text-align: left;
    background: #ffffff;
    border-right: solid 1px #a0a0a0;
    border-left: solid 1px #a0a0a0;
    padding: 1px,1px,1px,1px;
}

.borda {
    border: solid 1px #c0c0c0;
}

.etiqueta {
    font-size: 11px;
    width: 110px;
    text-align: center
}

h2 {
    color: #000000
}
</style>
<? } ?>
<style type='text/css' media='print'>
    body {font-family: Draft;}
    .noPrint {display:none;}
</style>



<?
if ($consumidor_revenda == 'R')
    $consumidor_revenda = 'REVENDA';
else
    if ($consumidor_revenda == 'C')
        $consumidor_revenda = ($login_fabrica == 122) ? 'CLIENTE' : 'CONSUMIDOR';
?>
<body>

<?php
//HD 371911
if(!isset($os_include)):?>
    <div class='noPrint'>
        <input type=button name='fbBtPrint' value='Versão Jato de Tinta / Laser'
        onclick="window.location='os_print.php?os=<? echo $os; ?>'">
        <br>
        <hr class='noPrint'>
    </div>
<?php endif;?>

<TABLE width="600px" border="0" cellspacing="0" cellpadding="0">
<TR class="titulo" style="text-align: center;">
<?
    if($login_fabrica==3){
        $sql = "SELECT logo
                from tbl_marca
                join tbl_produto using(marca)
                where tbl_marca.fabrica = $login_fabrica
                and tbl_produto.produto = $produto";
        $res = pg_exec($con,$sql);
//      echo $sql;
        if(pg_numrows($res)>0){
            $logo = pg_result($res,0,0);
            if($logo<>'britania.jpg'){          $img_contrato = "logos/$logo";}else{
            $img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
            }

        }
    }else{

        if(isset($novaTelaOs)){
            if (in_array($login_fabrica, array(175))){
                $img_contrato = "logos/logo_".strtolower ($login_fabrica_nome).".png";
            }else{
                $img_contrato = "logos/logo_".strtolower ($login_fabrica_nome).".jpg";
            }
        }else{

            if($login_fabrica==80){
                $img_contrato = "logos/".strtolower ($login_fabrica_nome).".gif";
            }else{
                if($login_fabrica==40){
                        $img_contrato = "logos/masterfrio.gif";
                }else{
                    if ($cliente_contrato == 'f') {
                        $img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
                    }else{
                        $img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
                    }
                }
            }

            if($login_fabrica == 52){
                $img_contrato = "logos/logo_fricon.jpg";
            }

            if($login_fabrica == 20){
                $img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".jpg";
	    }

	    if($login_fabrica == 35){
		$img_contrato = "logos/logo_cadence_new.png";
	    }

        }
    }
?>
    <TD rowspan="2">
        <?php if ($login_fabrica == 11): #HD 891549 ?>
            <label style="font:08px;">Aulik Ind. e Com. Ltda.</label>
        <?php else: ?>
            <IMG SRC="<? echo $img_contrato ?>" HEIGHT='30' ALT="ORDEM DE SERVIÇO">
        <?php endif ?>
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

    <TD><?  if ($sistema_lingua <> 'BR'){
                echo "<font size=-2> SERVICIO AUTORIZADO";
            }else{
                if ($login_fabrica <> 3){
                    echo "POSTO AUTORIZADO </font><BR>";
                }
                echo  substr($posto_nome,0,30);
            }?></TD>
    <TD><? if ($sistema_lingua<>'BR') echo "FECHA EMISSIÓN"; else echo "DATA EMISSÃO"?></TD>
    <TD><? if ($sistema_lingua<>'BR') echo "NÚMERO"; else echo "NÚMERO OS";?></TD>
</TR>
<TR class="titulo" style="text-align: center;">
    <TD>
<?
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
    else                        echo " - IE/RG ";
    echo $posto_ie;
?>
    </TD>
    <TD>
<?  ########## DATA DE ABERTURA ########## ?>
        <b><? echo $data_abertura ?></b>
    </TD>
    <TD>
<?  ########## SUA OS ########## ?>
    <?
        if (strlen($consumidor_revenda) == 0){
            echo "<center><b> <span style='font-size: 16px;'> $sua_os </span></b></center>";
        }else{
            echo "<center><b> <span style='font-size: 16px;'> $sua_os </span><br> $consumidor_revenda  </b></center>";
        }
    ?>
    </TD>
</TABLE>

<?
if (($login_fabrica == 1) || ($login_fabrica == 19)) $colspan = 6;
else $colspan = 5;
?>

<?
if ($login_fabrica == 11) {
    echo "<TABLE width='600px' border='0' cellspacing='0' cellpadding='0'>";
    echo "<TR><TD align='left' style='font-family: Draft font-size: 10px'>via do cliente</TD></TR>";
    echo "</TABLE>";
}
?>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">

<? if ($excluida == "t") { ?>
<TR>
    <TD colspan="<? echo $colspan ?>" bgcolor="#FFE1E1" align="center"><h1><?= traduz('ordem.de.servico.excluida') ?></h1></TD>
</TR>
<? } ?>

<?php if($login_fabrica == 124){ ?>
        <tr>
            <td colspan="4" style="text-align:center; font-family: Draft font-size: 10px;"><?= traduz('prezado.consumidor.o.acompanhamento.da.sua.ordem.de.servico.podera.ser.realizado.atraves.do.site') ?> <a href="HTTP://WWW.GAMMAFERRAMENTAS.COM.BR" target="_blank">WWW.GAMMAFERRAMENTAS.COM.BR</a> </td>
        </tr>
<?php }

 if($login_fabrica == 35){ ?>
    <tr>
        <td colspan="4" style="font-family: Draft font-size: 10px;">
            <?= traduz('para.consultar.o.status.da.sua.ordem.de.servico.aberta.em.uma.de.nossas.assistencias.tecnicas.favor.acessar') ?> <a href="http://www.cadence.com.br" target="_blank">www.cadence.com.br</a> <?= traduz('e.informar.o.numero.da.ordem.de.servico.e.seu.cpf') ?>.
        </td>
    </tr>
<?php }

if($login_fabrica == 3) { ?>
            <tr>
                <td colspan="4" style="text-align:center; font-size:11px;"><?= traduz('prezado.consumidor.o.acompanhamento.da.sua.ordem.de.servico.podera.ser.realizado.atraves.do.site') ?> <a href="http://www.britania.com.br/" target="_blank">http://www.britania.com.br/</a> </td>
            </tr>
<? } ?>
<TR>
    <TD class="titulo" colspan="<? echo $colspan ?>"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre la ordem de servicio"; else echo "Informações sobre a Ordem de Serviço";?></TD>
</TR>
<?
    if($login_fabrica==50){
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
                $resultado = pg_numrows($res_status);
                if ($resultado==1){
                    $data_status        = trim(pg_result($res_status,0,data));
                    $status_os          = trim(pg_result($res_status,0,status_os));
                    $status_observacao  = trim(pg_result($res_status,0,observacao));
                    $intervencao_admin  = trim(pg_result($res_status,0,login));

                    if ($status_os==98 or $status_os==99 or $status_os==100 or $status_os==101 or $status_os==102 or $status_os==103 or $status_os==104){
                        $sql_status = "select descricao from tbl_status_os where status_os = $status_os";
                        $res_status = pg_exec($con, $sql_status );
                        if(pg_numrows($res_status)>0) $descricao_status = pg_result($res_status, 0, 0);
                            echo "<TR>";
                                echo "<TD class='titulo'>".traduz('data')." &nbsp;</TD>";
                                echo "<TD class='titulo'>ADMIN &nbsp;</TD>";
                                echo "<TD class='titulo'>STATUS &nbsp;</TD>";
                                echo "<TD class='titulo' colspan='3'>".traduz('motivo')." &nbsp;</TD>";
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

if(in_array($login_fabrica, array(20,104))){
    $select_os_recebimento = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
    $res_os_recebimento = pg_query($con, $select_os_recebimento);

    if (pg_num_rows($res_os_recebimento) > 0) {
        $json_os_recebimento = json_decode(pg_fetch_result($res_os_recebimento, 0, "campos_adicionais"), true);
        if($login_fabrica == 104){
            $data_recebimento_produto     = $json_os_recebimento["data_recebimento_produto"];
        }

        if($login_fabrica == 20){
            $motivo_ordem      = $json_os_recebimento["motivo_ordem"];
        }
    }
}

if($login_fabrica == 157){
    $dt_abertura = 'DT ENTRADA PROD. ASSIST.';
} else {
    $dt_abertura = 'DT ABERT. OS';
}

?>
<TR >
    <TD class="titulo">OS FABRICANTE</TD>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA AP. OS"; else echo $dt_abertura;?></TD>
    <?php if($login_fabrica == 104){?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA AP. OS"; else echo "DT RECEBIMENTO PRODUTO";?></TD>
    <?}?>
</TR>

<TR height='5'>
    <TD class="conteudo"><? echo "<b>".$sua_os."</b>" ?></TD>
    <TD class="conteudo"><? echo $data_abertura ?></TD>
    <?php if($login_fabrica == 104){
        echo "<td>$data_recebimento_produto</td>";
        }?>
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
                <table width="600" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="titulo"><?= traduz('nota.fiscal') ?></td>
                        <td class="titulo"><?= traduz('tipo.atendimento') ?></td>
                        <td class="titulo"><?= traduz('horas.trabalhadas') ?></td>
                        <td class="titulo"><?= traduz('horas.tecnicas') ?></td>
                        <td class="titulo"><?= traduz('tecnico') ?></td>
                    </tr>
                    <tr height='5'>
                        <td class="conteudo"><? echo $nota_fiscal ?></td>
                        <td class="conteudo"><? echo $tipo_atendimento ?></td>
                        <td class="conteudo"><? echo $hora_tecnica ?></td>
                        <td class="conteudo"><? echo $qtde_horas ?></td>
                        <td class='conteudo'><?php echo $tecnico_nome?></td>
                    </tr>
                </table>
            </td>
        </tr>
<?php }?>

<TR >
    <?if ($login_fabrica == 96) {?>
        <TD class="titulo"><?= traduz('modelo') ?></TD>
    <?}else{?>
        <TD class="titulo">REF.</TD>
    <? }?>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DESCRIPCIÓN"; else echo "DESCRIÇÃO";?></TD>

    <?php if($login_fabrica <> 127){ ?>
        <TD class="titulo">
                <?
            if($login_fabrica==35){
                echo "PO#";
            }else{
                if ($sistema_lingua<>'BR') echo "SERIE "; else echo "NÚM. DE SÉRIE";
            }
            ?>
        </TD>
    <? }

    if ($login_fabrica == 175){
    ?>
        <td class="titulo">QTDE DISPAROS</td>
    <?php    
    }

    if ($login_fabrica == 176)
    {
?>  
        <td class="titulo"><?= traduz('indice') ?></td>
<?php
    }

    if(in_array($login_fabrica, [167, 203])){
    ?>
        <td class='titulo'> <?= traduz('contador') ?> </td>
    <?php
    }
    if ($login_fabrica == 1) { ?>
    <TD class="titulo"><?= traduz('cod.fabricacao') ?></TD>
    <? } ?>
    <? if ($login_fabrica == 19) { ?>
    <TD class="titulo"><?= traduz('quantidade') ?></TD>
    <? } ?>
</TR>

<TR height='5'>
    <?php if ($login_fabrica == 96) {?>
        <TD class="conteudo"><? echo $modelo ?></TD>
    <?php }else{?>
        <TD class="conteudo"><? echo $referencia ?></TD>
    <?php }?>
    <TD class="conteudo"><? echo $descricao ?></TD>
    <TD class="conteudo"><? echo $serie ?></TD>

    <?php if ($login_fabrica == 175){ ?>
        <TD class="conteudo"><?=$qtde_disparos?></TD>
    <?php } ?>
    <?php if ($login_fabrica == 176) { ?>
        <td class="conteudo"><?php echo $indice; ?></td>
    <?php } ?>
    <?php if(in_array($login_fabrica, [167, 203])){ ?>
    <td class='conteudo'><?=$contador?></td>
    <?php } ?>
    <? if ($login_fabrica == 1) { ?>
    <TD class="conteudo"><? echo $codigo_fabricacao ?></TD>
    <? } ?>
    <? if ($login_fabrica == 19) { ?>
    <TD class="conteudo"><? echo $qtde_produtos ?></TD>
    <? } ?>
</TR>

<?php
if ($login_fabrica == 148) {
?>
    <tr>
        <td class="titulo" ><?= traduz('n.de.serie.motor') ?></td>
        <td class="titulo" ><?= traduz('n.de.serie.transmissao') ?></td>
        <td class="titulo" ><?= traduz('horimetro') ?></td>
        <td class="titulo" ><?= traduz('revisao') ?></td>
    </tr>
     <tr>
        <td class="conteudo" ><?=$serie_motor?></td>
        <td class="conteudo" ><?=$serie_transmissao?></td>
        <td class="conteudo" ><?=$os_horimetro?></td>
        <td class="conteudo" ><?=$os_revisao?></td>
    </tr>
<?php
}
?>

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

<? if($login_fabrica == 86 and $serie_justificativa != 'null'){ // HD 328591?>
        <tr>
            <td colspan='5' class='titulo'><?= traduz("justificativa.numero.serie") ?></td>
        </tr>
        <tr>
            <td colspan='5' class='conteudo'><? echo $serie_justificativa ?></td>
        </tr>
<? } ?>

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
?>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL USUARIO"; else echo ($login_fabrica == 122) ? "NOME DO CLIENTE" : "NOME DO CONSUMIDOR";?></TD>
    <?php if($login_fabrica <> 20){ ?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "CIUDAD"; else echo "CIDADE";?></TD>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "PROVINCIA"; else echo "ESTADO";?></TD>
    <?php } ?>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "TELÉFONO"; else echo "FONE";?></TD>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "CELULAR"; else echo "CELULAR";?></TD>
    <?php if($login_fabrica == 120){ ?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMERCIAL"; else echo "COMERCIAL";?></TD>
    <?php } ?>
    <?php if(in_array($login_fabrica, [167, 203])){ ?>
        <td class='titulo'>CONTATO</td>
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
    <?php if(in_array($login_fabrica, [167, 203])){ ?>
    <td class='conteudo'><?=$contato_consumidor?></td>
    <?php } ?>
    <?php if($login_fabrica == 120){ ?>
        <TD class="conteudo"><? echo $consumidor_fonecom ?></TD>
    <?php } ?>
</TR>
</TABLE>

<? if ($login_fabrica == 3 or $login_fabrica == 52 or $login_fabrica == 74){
    # HD 30788 - Francisco Ambrozio (11/8/2008)
    # Adicionado tels. celular e comercial do consumidor para Britânia ?>
<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD class="titulo"><? echo "TELEFONE CELULAR" ?></TD>
        <TD class="titulo"><? echo "TELEFONE COMERCIAL" ?></TD>
        <TD class="titulo"><? echo "EMAIL" ?></TD>
        <?php if ($login_fabrica == 74): ?>
        <TD class="titulo">DATA DE NASCIMENTO</TD>
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
</TABLE>
<? }?>

<?php if($login_fabrica <> 20){ ?>
<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECCIÓN"; else echo "ENDEREÇO";?></TD>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NUMERO"; else echo "NÚMERO";?></TD>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMPLEMIENTO"; else echo "COMPLEMENTO";?></TD>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "BARRIO"; else echo "BAIRRO";?></TD>
</TR>
<TR>
    <TD class="conteudo"><? echo $consumidor_endereco ?></TD>
    <TD class="conteudo"><? echo $consumidor_numero ?></TD>
    <TD class="conteudo"><? echo $consumidor_complemento ?></TD>
    <TD class="conteudo"><? echo $consumidor_bairro ?></TD>
</TR>
</TABLE>
<?php } ?>
<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <?php if($login_fabrica <> 20){ ?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARATO POSTAL"; else echo "CEP";?></TD>
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
<? if($login_fabrica == 35){ ?>
<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD class="titulo" colspan="5"><?= traduz("informacoes.sobre.a.revenda") ?></TD>
</TR>

<TR>
    <TD class="titulo"><?= traduz("cnpj") ?></TD>
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
<? } ?>
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
<? }
    if($login_fabrica <> 20 AND !$defeitoReclamadoCadastroDefeitoReclamadoCliente){
?>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DEFECTO PRESENTADO POR EL USUARIO"; else echo "DEFEITO APRESENTADO PELO CLIENTE";?></TD>
    <?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                <TD class="titulo">BOX / PRATELEIRA</TD>
    <?php } ?>
</TR>
<TR>
    <TD class="conteudo"><? echo ($defeito_reclamado_descricao != 'null') ? $defeito_reclamado_descricao . " - " : '';echo $defeito_cliente ?></TD>
    <?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                <TD class="conteudo"><? echo $box_prateleira; ?></TD>
    <?php } ?>
</TR>
</TABLE>

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
        <TD class="titulo">DEFEITO RECLAMADO CLIENTE</TD>
    </TR>
    <TR>
        <TD class="conteudo"><?=$defeito_constatado_descricao?></TD>
        <TD class="conteudo"><?=$defeito_cliente?></TD>
        <TD class="conteudo"><?=$defeito_reclamado_descricao?></TD>
    </TR>
</TABLE>
<?php } ?>

<?php    
    if(in_array($login_fabrica,array(87))){
        $sql_peca = "
                SELECT
                    peca_causadora.referencia AS referencia_causadora,
                    peca_causadora.descricao AS descricao_causadora,
                    tbl_peca.referencia AS peca_referencia,
                    tbl_peca.descricao AS peca_descricao,
                    tbl_os_item.qtde,
                    tbl_os_item.soaf,
                    tbl_defeito.descricao AS defeito_descricao,
                    tbl_servico_realizado.descricao AS servico_realizado
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
                    $peca_itens .=  "<th class='titulo'>&nbsp;".traduz("causa.falha")."</th>";
                    //$peca_itens .=  "<th class='titulo'>ITEM CAUSADOR</th>";
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
                        $peca_itens .=  "<td class='conteudo'>&nbsp;{$defeito_descricao}</td>";
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
<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARIENCIA GENERAL DEL PRODUCTO"; else echo "APARÊNCIA GERAL DO PRODUTO";?></TD>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ACCESORIO DEJADOS POR EL USUARIO"; else echo "ACESSÓRIOS DEIXADOS PELO CLIENTE";?></TD>
</TR>
<TR>
    <TD class="conteudo"><? echo $aparencia_produto ?></TD>
    <TD class="conteudo"><? echo $acessorios ?></TD>
</TR>
</TABLE>
<?php } ?>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OBSERVACIONES"; else echo "OBSERVAÇÃO";?></TD>
</TR>
<TR>
    <TD class="conteudo">
        <?php $obs = wordwrap($obs, 80, '<br/>', true); echo $obs ?>
    </TD>
</TR>
</TABLE>

<?
//if($login_fabrica==19){
//Wellington 05/02/2007 - Alguem retirou este if da fabrica 19 e não comentou o porque... Estou pulando este item para fabrica 11
if (!in_array($login_fabrica,array(11,24,124))) {
        if(strlen($tipo_os)>0 and $login_fabrica==19){
        $sqll = "SELECT descricao from tbl_tipo_os where tipo_os=$tipo_os";
        $ress = pg_exec($con,$sqll);
        $tipo_os_descricao = pg_result($ress,0,0);
    }
?>
    <TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ATENDIMIENTO"; else echo "ATENDIMENTO";?></TD>
        <?      if($login_fabrica==19){ ?>
        <TD class="titulo">MOTIVO</TD>
<?}?>


    <?php if($login_fabrica == 20  and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){?>
         <TD class="titulo"><?= traduz("motivo.ordem") ?></TD>
    <?php }
         if($login_fabrica <> 20){
    ?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL TÉCNICO"; else echo "NOME DO TÉCNICO";?></TD>
    <?php } ?>
    </TR>
    <TR>
        <TD class="conteudo"><? echo $codigo_atendimento." - ".$nome_atendimento ?></TD>
                <?      if($login_fabrica==19){ ?>
        <TD class="titulo"><? echo "$tipo_os_descricao";?></TD>
<?}?>
    <?php if($login_fabrica == 20  and ($tipo_atendimento == 13 or $tipo_atendimento == 66)) {?>
        <TD class="conteudo"><? echo "$motivo_ordem";?></TD>
    <?php } if($login_fabrica <> 20){ ?>
        <TD class="conteudo"><? echo $tecnico_nome ?></TD>
    <?php } ?>
    </TR>


    </TABLE>
<?
}

        //HD-3200578
        if($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){
            $obs_motivo_ordem = array();
            if($motivo_ordem == 'PROCON (XLR)'){
                $obs_motivo_ordem[] = 'Protocolo:';
                $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['protocolo']);
            }
            if($motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
                $obs_motivo_ordem[] = 'CI ou Solicitante:';
                $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['ci_solicitante']);
            }

            if($motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
                $obs_motivo_ordem[] = "Descrição Peças:";
                if(strlen(trim($json_os_recebimento['descricao_peca_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['descricao_peca_1']);
                }
                if(strlen(trim($json_os_recebimento['descricao_peca_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['descricao_peca_2']);
                }
                if(strlen(trim($json_os_recebimento['descricao_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['descricao_peca_3']);
                }
            }

            if($motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){
                if(strlen(trim($json_os_recebimento['codigo_peca_1'])) > 0 OR strlen(trim(utf8_decode($json_os_recebimento['codigo_peca_2']))) > 0 OR strlen(trim($json_os_recebimento['codigo_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= 'Código Peças:';
                }
                if(strlen(trim($json_os_recebimento['codigo_peca_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['codigo_peca_1']);
                }
                if(strlen(trim($json_os_recebimento['codigo_peca_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['codigo_peca_2']);
                }
                if(strlen(trim($json_os_recebimento['codigo_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['codigo_peca_3']);
                }

                if(strlen(trim($json_os_recebimento['numero_pedido_1'])) > 0 OR strlen(trim($json_os_recebimento['numero_pedido_2'])) > 0 OR strlen(trim($json_os_recebimento['numero_pedido_3'])) > 0){
                    $obs_motivo_ordem[] .= 'Número Pedidos:';
                }
                if(strlen(trim($json_os_recebimento['numero_pedido_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['numero_pedido_1']);
                }
                if(strlen(trim($json_os_recebimento['numero_pedido_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['numero_pedido_2']);
                }
                if(strlen(trim($json_os_recebimento['numero_pedido_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['numero_pedido_3']);
                }
            }

            if($motivo_ordem == "Linha de Medicao (XSD)"){
                $obs_motivo_ordem[] .= 'Linha de Medição(XSD):';
                $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['linha_medicao']);
            }
            if($motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
                $obs_motivo_ordem[] .= 'Pedido não fornecido - Valor Mínimo(XSS):';
                $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['pedido_nao_fornecido']);
            }

            if($motivo_ordem == 'Contato SAC (XLR)'){
                $obs_motivo_ordem[] .= 'N° do Chamado:';
                $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['contato_sac']);
            }

            if($motivo_ordem == 'Bloqueio financeiro (XSS)' OR $motivo_ordem == 'Ameaca de Procon (XLR)' OR $motivo_ordem == 'Defeito reincidente (XQR)'){
                $obs_motivo_ordem[] .= "Detalhes:";
                $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['detalhe']);
            }
        ?>
        <table class='borda' width="600" border="0" cellspacing="0" cellpadding="0">
            <tr><td class='titulo'><?= traduz("observacao.motivo.ordem") ?></td></tr>
            <tr><td class='conteudo'><?php echo implode('<br/>', $obs_motivo_ordem); ?></td></tr>
        </table>
    <?php
        }
        //FIM HD-3200578

//}
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
                <TD class="conteudo" colspan='2'><? echo ($certificado_garantia) ? "Sim" : "Não";?></TD>
        </TR>
    </TABLE>
<? }
}

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

if ($login_fabrica == 114) {
    $sql_linha = "SELECT tbl_produto.linha FROM tbl_produto JOIN tbl_os ON tbl_os.produto = tbl_produto.produto AND tbl_os.fabrica = $login_fabrica WHERE tbl_produto.fabrica_i = $login_fabrica AND tbl_os.os = $os";
    $res_linha = pg_query($con, $sql_linha);

    $linha = pg_fetch_result($res_linha, 0, "linha");
}

if(!in_array($login_fabrica,array(124,126)) && (($login_fabrica == 114 && !in_array($linha, array(691,692,710)) ) || $login_fabrica != 114)) {
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
        if (in_array($login_fabrica, array(141,144)) && in_array($os_tipo_posto, array(452,453))) {
        ?>
            <td class='titulo' ><?= traduz("remanufatura") ?></td>
        <?php
        }
        ?>
    </TR>
    <TR>
        <TD class="conteudo" colspan='<?=$colspan?>'><? echo number_format($qtde_km,2,',','.');?>&nbsp;KM</TD>
        <?php
        if (in_array($login_fabrica, array(141,144)) && in_array($os_tipo_posto, array(452,453))) {
        ?>
            <td class='conteudo'><?=($os_remanufatura == "t") ? "Sim" : "Não"?></td>
        <?php
        }
        ?>
    </TR>
    <?php
    }
    ?>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD class='titulo' style="text-align: center;">
             <?php 
                if (in_array($login_fabrica, [139])) { 
                    echo ($sistema_lingua <> 'BR') ? "Diagnóstico y resolución del problema. Técnico:" : "Diagnóstico e Resolução do Problema. Técnico:";
                } else {
                    echo ($sistema_lingua <> 'BR') ? "Diagnóstico, repuesto utilizado y resolución del problema. Técnico:" : "Diagnóstico, Peças usadas e Resolução do Problema. Técnico:";
                }
            ?>
        </TD>
    </TR>
<?php
    if ($login_fabrica <> 3){
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
            echo $peca_dynacom;
        } else {
            echo traduz($msgAviso);
        }
?>
        </TD>
    </TR>
<?php
    }
?>
</table>
<br />
<?php if ($login_fabrica == 35) { ?>
    <br>
        <TABLE width="600" border="0" cellspacing="0" cellpadding="0">
            <TR>
                <TD colspan="4" style="font-family: Draft font-size: 10px;">
                    Os serviços prestados pelo Posto Autorizado dentro do período de garantia do produto, deverão ser realizados no prazo máximo de 30 dias, contados a partir da data de recebimento do produto na assistência. Importante informar seu celular e e-mail para que assim que concluído o reparo seja feito comunicado a você para retirada do produto.
                </TD>
            </TR>
        </TABLE>
<?php } ?>
<?
}

if (in_array($login_fabrica,array(59,127))) {
    $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND campos_adicionais notnull";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){
        $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

        foreach ($campos_adicionais as $key => $value) {
            $$key = $value;
        }
        if ($login_fabrica == 127 ){
        $enviar_os = ($enviar_os == "t") ? "Sim" : "Não";
            ?>
            <TABLE width="600" border="0" cellspacing="0" cellpadding="0" class='borda'>
                 <TR>
                    <TD class="titulo">Envio p/ DL</TD>
                    <TD class="titulo">CÓD. RASTREIO&nbsp;</TD>
                </TR>
                <TR>
                    <TD class="conteudo">&nbsp;<?=$enviar_os?></TD>
                    <TD class="conteudo">&nbsp;<?=$codigo_rastreio?> </TD>
                </TR>
            </TABLE><?php
        }elseif($login_fabrica ==59){
            $sql = "SELECT tipo_posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
            $res = pg_query($con,$sql);
            $tipo_posto = pg_fetch_result($res,0,'tipo_posto');

            if(strlen($os)> 0 and $tipo_posto == 464){

                if ($origem=='recepcao'){
                    $origem = 'Recepção';
                }elseif(strlen($origem)>0){
                    $origem = 'Sedex reverso';
                }
                 ?>
                <TABLE width="600" border="0" cellspacing="0" cellpadding="0" class='borda'>
                     <TR>
                        <TD class="titulo"><?= traduz("origem") ?></TD>
                    </TR>
                    <TR>
                        <TD class="conteudo">&nbsp;<?=$origem?></TD>
                    </TR>
                </TABLE><?php
            }
        }
    }

}

if ($login_fabrica == 2 AND strlen($data_fechamento)>0) {
?>

    <TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
    <? echo "<TR>";
     if(strlen($defeito_constatado) > 0) {
            echo "<TD class='titulo'>$temaMaiusculo</TD>";
            echo "<TD class='titulo'>".traduz("solucao")."</TD>";
            echo "<TD class='titulo'>DT FECHA. OS</TD>";
    }
    echo "</TR>";
    echo "<TR>";
    if(strlen($defeito_constatado) > 0) {
            echo "<TD class='conteudo'>$defeito_constatado</TD>";
            echo "<TD class='conteudo'>$solucao</TD>";
            echo "<TD class='conteudo'>$data_fechamento</TD>";
    } ?>
    </TR>
    <TR>
        <TD>&nbsp;</TD>
    </TR>
    </TABLE>
<?
}
?>

<?php
if( ( ($login_fabrica == 95 || $login_fabrica == 59) and strlen($finalizada) > 0)  || $login_fabrica == 96 ){?>
<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE CIERRE"; else echo "DATA DE FECHAMENTO";?></TD>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE REPARACIÓN"; else echo "DATA DE CONSERTO";?></TD>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FALLO CONSTATADO"; else echo $temaMaiusculo;?></TD>
    </TR>
    <TR>
        <TD class="conteudo"><? echo convertDataBR(substr($finalizada,0,10)); ?></TD>
        <TD class="conteudo"><? echo convertDataBR(substr($data_conserto,0,10)); ?></TD>
        <TD class="conteudo"><? echo $defeito_constatado; ?></TD>
    </TR>
</TABLE>
<?php
    $sql_servico = "
        SELECT
            tbl_os_item.peca,
            tbl_peca.referencia,
            tbl_peca.descricao,
            tbl_servico_realizado.descricao AS servico_realizado
        FROM tbl_os
            JOIN tbl_os_produto USING(os)
            JOIN tbl_os_item USING(os_produto)
            JOIN tbl_peca USING(peca)
            JOIN tbl_servico_realizado ON (tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado)
        WHERE
            tbl_os.os = $os
            AND tbl_os.fabrica = $login_fabrica;";

    $res_servico = pg_exec($con,$sql_servico);
    if(pg_num_rows($res_servico) > 0){
        echo '<table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">';
            echo '<tr>';
                echo '<td class="titulo">'.traduz("referencia").'</td>';
                echo '<td class="titulo">'.traduz("descricao").'</td>';
                if($login_fabrica == 96){
                    echo '<td class="titulo">FREE OF CHARGE</td>';
                } else {
                    echo '<td class="titulo">'.traduz("servico").'</td>';
                }
            echo '</tr>';
        for($x=0;$x < pg_num_rows($res_servico);$x++){
            $_referencia = pg_fetch_result($res_servico,$x,referencia);
            $_descricao = pg_fetch_result($res_servico,$x,descricao);
            $_servico_realizado = pg_fetch_result($res_servico,$x,servico_realizado);

            echo '<tr>';
                echo "<td class='conteudo'>$_referencia</td>";
                echo "<td class='conteudo'>$_descricao</td>";
                echo "<td class='conteudo'>$_servico_realizado</td>";
            echo '</tr>';
        }
        echo "</table>";
    }
}?>

<?
  if ($login_fabrica == 3){
?>
<TABLE width="600px" border="0" cellspacing="0" cellpadding="0">
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
<TABLE width="600px" border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD style='font-size: 11px'><?

            echo $posto_cidade .", ". $data_abertura;

        ?></TD>
    </TR>
    <TR>
        <TD style='font-size: 10px'>
            <? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: ";?>
        </TD>
    </TR>
</TABLE>
<? }
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
<?php } ?>

<TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<?
//WELLINGTON 05/02/2007
if ($login_fabrica == 11) {
    echo "<CENTER>";
    echo "<TABLE width='650px' border='0' cellspacing='0' cellpadding='0'>";
    echo "<TR class='titulo' style='text-align: center;'>";
    echo "<TD>";

    ########## CABECALHO COM DADOS DO POSTOS ##########
    echo $posto_nome."<BR>";
    echo "CNPJ/CPF ".$posto_cnpj ." - IE/RG ".$posto_ie;
    echo "</TD></TR></TABLE></CENTER>";
}
?>

<?
if ($login_fabrica == 11) {
    echo "<TABLE width='600px' border='0' cellspacing='0' cellpadding='0'>";
    echo "<TR><TD align='left' style='font-family: Draft font-size: 10px'>".traduz("via.do.fabricante.assinada.pelo.cliente")."</TD></TR>";
    echo "</TABLE>";
}
?>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD class="titulo" colspan="5"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre la ordem de servicio"; else echo "Informações sobre a Ordem de Serviço";?></TD>
</TR>
<?
    if($login_fabrica==50){
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
                $resultado = pg_numrows($res_status);
                if ($resultado==1){
                    $data_status        = trim(pg_result($res_status,0,data));
                    $status_os          = trim(pg_result($res_status,0,status_os));
                    $status_observacao  = trim(pg_result($res_status,0,observacao));
                    $intervencao_admin  = trim(pg_result($res_status,0,login));

                    if ($status_os==98 or $status_os==99 or $status_os==100 or $status_os==101 or $status_os==102 or $status_os==103 or $status_os==104){
                        $sql_status = "select descricao from tbl_status_os where status_os = $status_os";
                        $res_status = pg_exec($con, $sql_status );
                        if(pg_numrows($res_status)>0) $descricao_status = pg_result($res_status, 0, 0);
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
    <TR>
        <TD class="titulo"<?=$colspan?>><? if ($sistema_lingua<>'BR') echo "FABRICANTE"; else echo "FABRICANTE";?></TD>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OS FABRICANTE"; else echo "OS FABRICANTE";?></TD>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA AP OS"; else echo $dt_abertura;?></TD>
        <?php if($login_fabrica == 104){?>
            <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA AP. OS"; else echo "DT RECEBIMENTO PRODUTO";?></TD>
        <?}

        if ($login_fabrica == 174){ ?>
            <TD class="titulo">VALOR NF</TD>
        <?php } ?>
    </TR>

    <TR>
        <TD class="conteudo"<?=$colspan?>><? echo "<b>".$login_fabrica_nome."</b>" ?></TD>
        <TD class="conteudo"><? echo "<b>".$sua_os."</b>" ?></TD>
        <TD class="conteudo"><? echo $data_abertura ?></TD>
         <?php if($login_fabrica == 104){
        echo "<td>$data_recebimento_produto</td>";
        }

        if ($login_fabrica == 174) { 
            $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
            $aux_res = pg_query($con, $aux_sql);
            $aux_arr = json_decode(pg_fetch_result($aux_res, 0, 'campos_adicionais'), true);

            if (empty($aux_arr["valor_nf"])) {
                $valor_nf = "";
            } else {
                $valor_nf = $aux_arr["valor_nf"];
            } ?>
            <TD class="conteudo"><?=$valor_nf;?></TD>
        <?php } ?>

    </TR>
   <?php
        if($login_fabrica == 87){?>

            <tr height='5'>
                <td colspan='3'>
                    <table width="600" border="0" cellspacing="0" cellpadding="0">
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
    <TR>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "REFERENCIA"; else echo "REFERÊNCIA";?></TD><?php
        if ($login_fabrica == 96) { ?>
            <TD class="titulo">MODELO</TD><?php
        }?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DESCRIPCIÓN"; else echo "DESCRIÇÃO";?></TD>

        <?php if($login_fabrica <> 127){ ?>
            <TD class="titulo"><?php
                if ($login_fabrica == 35) {
                    echo "PO#";
                } else {
                    if ($sistema_lingua<>'BR') echo "NÚM. DE SERIE "; else echo "NÚM. DE SÉRIE";
                }?>
            </TD>
        <?
        }

        if ($login_fabrica == 175){
        ?>
            <td class='titulo'>QTDE DISPAROS</td>
        <?php    
        }

        if(in_array($login_fabrica, [167, 203])){
        ?>
        <td class='titulo'><?= traduz("contador") ?></td>
        <?php
        }
        if ($login_fabrica == 19) { ?>
            <TD class="titulo"><?= traduz("quantidade") ?></TD><?php
        }
        ?>
    </TR>

    <TR>
        <TD class="conteudo"><? echo $referencia ?></TD>
        <?if ($login_fabrica == 96) { ?>
            <TD class="titulo"><?echo $modelo?></TD><?php
        }?>
        <TD class="conteudo" colspan='*'><? echo $descricao ?></TD>
        <TD class="conteudo"><? echo $serie ?></TD>

        <?php if ($login_fabrica == 175){ ?>
            <td class='conteudo'><?=$qtde_disparos?></td>
        <?php } ?>
        <?php if(in_array($login_fabrica, [167, 203])){ ?>
        <td class='conteudo'><?=$contador?></td>
        <?php } ?>
        <?if ($login_fabrica == 19) { ?>
        <TD class="conteudo"><? echo $qtde_produtos ?></TD>
        <? } ?>
    </TR>


    <?php
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
    <?php
    }
    ?>

    <? if($login_fabrica == 86 and $serie_justificativa != 'null'){ // HD 328591?>
        <tr>
            <td colspan='5' class='titulo'><?= traduz("justificativa.numero.serie") ?></td>
        </tr>
        <tr>
            <td colspan='5' class='conteudo'><? echo $serie_justificativa ?></td>
        </tr>
    <? } ?>

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
?>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL USUARIO"; else echo ($login_fabrica == 122) ? "NOME DO CLIENTE" : "NOME DO CONSUMIDOR";?></TD>
    <?php if($login_fabrica <> 20){ ?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "CIUDAD"; else echo "CIDADE";?></TD>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "PROVINCIA"; else echo "ESTADO";?></TD>
    <?php } ?>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "TELÉFONO"; else echo "FONE";?></TD>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "CELULAR"; else echo "CELULAR";?></TD>
    <?php if($login_fabrica == 120){ ?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMERCIAL"; else echo "COMERCIAL";?></TD>
    <?php } ?>

    <?php if(in_array($login_fabrica, [167, 203])){ ?>
        <td class='titulo'><?= traduz("contato") ?></td>
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
    <?php if($login_fabrica == 120){?>
        <TD class="conteudo"><? echo $consumidor_fonecom ?></TD>
    <?php } ?>
    <?php if(in_array($login_fabrica, [167, 203])){ ?>
        <td class='conteudo'><?=$contato_consumidor?></td>
    <?php } ?>
</TR>
</TABLE>

<? if ($login_fabrica == 3 or $login_fabrica ==52 or $login_fabrica == 74){
    # HD 30788 - Francisco Ambrozio (11/8/2008)
    # Adicionado tels. celular e comercial do consumidor para Britânia ?>
<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD class="titulo"><?= traduz("telefone.celular") ?></TD>
        <TD class="titulo"><?= traduz("telefone.comercial") ?></TD>
        <TD class="titulo"><?= traduz("email") ?></TD>
    </TR>
    <TR>
        <TD class="conteudo"><? echo $consumidor_celular ?></TD>
        <TD class="conteudo"><? echo $consumidor_fonecom ?></TD>
        <TD class="conteudo"><? echo $consumidor_email ?></TD>
    </TR>
</TABLE>
<? }?>

<?php if($login_fabrica <> 20){ ?>
<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DIRECCIÓN"; else echo "ENDEREÇO";?></TD>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NÚMERO"; else echo "NÚMERO";?></TD>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "COMPLEMIENTO"; else echo "COMPLEMENTO";?></TD>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "BARRIO"; else echo "BAIRRO";?></TD>
</TR>
<TR>
    <TD class="conteudo"><? echo $consumidor_endereco ?></TD>
    <TD class="conteudo"><? echo $consumidor_numero ?></TD>
    <TD class="conteudo"><? echo $consumidor_complemento ?></TD>
    <TD class="conteudo"><? echo $consumidor_bairro ?></TD>
</TR>
</TABLE>
<?php } ?>
<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <?php if($login_fabrica <> 20){ ?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARATO POSTAL"; else echo "CEP";?></TD>
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

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<? if($login_fabrica != 122){ ?>
<TR>
    <TD class="titulo" colspan="5"><? if ($sistema_lingua<>'BR') echo "Informaciones sobre el distribuidor"; else echo "Informações sobre a Revenda";?></TD>
</TR>
<? }else{ ?>
<TR>
    <TD class="titulo" colspan="5"><?= traduz("informacoes.da.nota.fiscal") ?></TD>
</TR>
<? } ?>
<TR>
    <? if($login_fabrica != 122 AND $login_fabrica <> 20){ ?>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "Identificación"; else echo "CNPJ";?></TD>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE"; else echo "NOME";?></TD>
    <? } ?>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FACTURA COMERCIAL"; else echo "NF N.";?></TD>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA NF"; else echo "DATA NF";?></TD>
</TR>

<TR>
    <? if($login_fabrica != 122 AND $login_fabrica <> 20){ ?>
    <TD class="conteudo"><? echo ($login_fabrica == 15) ? substr($revenda_cnpj,0,8) : $revenda_cnpj ?></TD>
    <TD class="conteudo"><? echo $revenda_nome ?></TD>
<? } ?>
    <TD class="conteudo"><? echo $nota_fiscal ?></TD>
    <TD class="conteudo"><? echo $data_nf ?></TD>
</TR>

</TABLE>

<? if($login_fabrica != 15 AND $login_fabrica <> 20){ ?>
<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
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
<? } if($login_fabrica <> 20 AND !$defeitoReclamadoCadastroDefeitoReclamadoCliente){ ?>
<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "DEFECTO PRESENTADO POR EL USUARIO"; else echo "DEFEITO APRESENTADO PELO CLIENTE";?></TD>
    <?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
            <TD class="titulo">BOX / PRATELEIRA</TD>
    <?php } ?>
</TR>
<TR>
    <TD class="conteudo"><? echo ($defeito_reclamado_descricao != 'null') ? $defeito_reclamado_descricao . " - " : '';echo $defeito_cliente ?></TD>
    <?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
            <TD class="conteudo"><? echo $box_prateleira; ?></TD>
    <?php } ?>
</TR>
</TABLE>

<?php

    if(!empty($peca_itens) AND in_array($login_fabrica, array(87)))
        echo $peca_itens;
?>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "APARIENCIA GENERAL DEL PRODUCTO"; else echo "APARÊNCIA GERAL DO PRODUTO";?></TD>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ACCESORIO DEJADOS POR EL USUARIO"; else echo "ACESSÓRIOS DEIXADOS PELO CLIENTE";?></TD>
</TR>
<TR>
    <TD class="conteudo"><? echo $aparencia_produto ?></TD>
    <TD class="conteudo"><? echo $acessorios ?></TD>
</TR>
</TABLE>
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
        <TD class="titulo">DEFEITO RECLAMADO CLIENTE</TD>
    </TR>
    <TR>
        <TD class="conteudo"><?=$defeito_constatado_descricao?></TD>
        <TD class="conteudo"><?=$defeito_cliente?></TD>
        <TD class="conteudo"><?=$defeito_reclamado_descricao?></TD>
    </TR>
</TABLE>
<?php } ?>

<?php if( ( ($login_fabrica == 95 || $login_fabrica == 59) and strlen($finalizada) > 0)  || $login_fabrica == 96 ){?>
<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE CIERRE"; else echo "DATA DE FECHAMENTO";?></TD>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FECHA DE REPARACIÓN"; else echo "DATA DE CONSERTO";?></TD>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "FALLO CONSTATADO"; else echo $temaMaiusculo;?></TD>
    </TR>
    <TR>
        <TD class="conteudo"><? echo convertDataBR(substr($finalizada,0,10)); ?></TD>
        <TD class="conteudo"><? echo convertDataBR(substr($data_conserto,0,10)); ?></TD>
        <TD class="conteudo"><? echo $defeito_constatado; ?></TD>
    </TR>
</TABLE>
<?php
    $sql_servico = "
        SELECT
            tbl_os_item.peca,
            tbl_peca.referencia,
            tbl_peca.descricao,
            tbl_servico_realizado.descricao AS servico_realizado
        FROM tbl_os
            JOIN tbl_os_produto USING(os)
            JOIN tbl_os_item USING(os_produto)
            JOIN tbl_peca USING(peca)
            JOIN tbl_servico_realizado ON (tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado)
        WHERE
            tbl_os.os = $os
            AND tbl_os.fabrica = $login_fabrica;";

    $res_servico = pg_exec($con,$sql_servico);
    if(pg_num_rows($res_servico) > 0){
        echo '<table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">';
            echo '<tr>';
                echo '<td class="titulo">'.traduz("referencia").'</td>';
                echo '<td class="titulo">'.traduz("descricao").'</td>';
                if($login_fabrica == 96){
                    echo '<td class="titulo">FREE OF CHARGE</td>';
                } else {
                    echo '<td class="titulo">'.traduz("servico").'</td>';
                }
            echo '</tr>';
        for($x=0;$x < pg_num_rows($res_servico);$x++){
            $_referencia = pg_fetch_result($res_servico,$x,referencia);
            $_descricao = pg_fetch_result($res_servico,$x,descricao);
            $_servico_realizado = pg_fetch_result($res_servico,$x,servico_realizado);

            echo '<tr>';
                echo "<td class='conteudo'>$_referencia</td>";
                echo "<td class='conteudo'>$_descricao</td>";
                echo "<td class='conteudo'>$_servico_realizado</td>";
            echo '</tr>';
        }
        echo "</table>";
    }
}?>
<?
if ($login_fabrica == 11) {
?>
    <TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
    <? echo "<TR>";
     if(strlen($defeito_constatado) > 0) {
            echo "<TD class='titulo'>$temaMaiusculo</TD>";
            echo "<TD class='titulo'>".traduz("solucao")."</TD>";
    } else {
            echo "<TD class='titulo'>$temaMaiusculo (".traduz("preencher.este.campo.a.mao").")</TD>";
            echo "<TD class='titulo'>".traduz("solucao")." (".traduz("preencher.este.campo.a.mao").")</TD>";
    }
    echo "</TR>";
    echo "<TR>";
    if(strlen($defeito_constatado) > 0) {
            echo "<TD class='conteudo'>$defeito_constatado</TD>";
            echo "<TD class='conteudo'>$solucao</TD>";
    } else {
            echo "<TD class='conteudo'>&nbsp;</TD>";
            echo "<TD class='conteudo'>&nbsp;</TD>";
    }?>
    </TR>
    </TABLE>
<?
}
?>

<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "OBSERVACIONES"; else echo "OBSERVAÇÃO";?></TD>
</TR>
<TR>
    <TD><? echo $obs ?></TD>
</TR>
</TABLE>

<?
//if($login_fabrica==19){
//Wellington 05/02/2007 - Alguem retirou este if da fabrica 19 e não comentou o porque... Estou pulando este item para fabrica 11
if (!in_array($login_fabrica,array(11,124))) {
?>
    <TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "ATENDIMIENTO"; else echo "ATENDIMENTO";?></TD>
        <?      if($login_fabrica==19){ ?>
        <TD class="titulo">MOTIVO</TD>
<?}?>

    <?php if($login_fabrica == 20  and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){ ?>
        <TD class="titulo">MOTIVO ORDEM</TD>
    <?php } if($login_fabrica <> 20){ ?>
        <TD class="titulo"><? if ($sistema_lingua<>'BR') echo "NOMBRE DEL TÉCNICO"; else echo "NOME DO TÉCNICO";?></TD>
    <?php } ?>
    </TR>
    <TR>
        <TD class="conteudo"><? echo $codigo_atendimento." - ".$nome_atendimento ?></TD>
                <?      if($login_fabrica==19){ ?>
        <TD class="conteudo"><? echo "$tipo_os_descricao";?></TD>
<?}?>

    <?php if($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){ ?>
        <TD class="conteudo"><? echo $motivo_ordem ?></TD>
    <?php } if($login_fabrica <> 20){ ?>
        <TD class="conteudo"><? echo $tecnico_nome ?></TD>
    <?php } ?>
    </TR>
</TABLE>
<?
}

if($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){
            $obs_motivo_ordem = array();
            if($motivo_ordem == 'PROCON (XLR)'){
                $obs_motivo_ordem[] = 'Protocolo:';
                $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['protocolo']);
            }
            if($motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
                $obs_motivo_ordem[] = 'CI ou Solicitante:';
                $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['ci_solicitante']);
            }

            if($motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
                $obs_motivo_ordem[] = "Descrição Peças:";
                if(strlen(trim($json_os_recebimento['descricao_peca_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['descricao_peca_1']);
                }
                if(strlen(trim($json_os_recebimento['descricao_peca_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['descricao_peca_2']);
                }
                if(strlen(trim($json_os_recebimento['descricao_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['descricao_peca_3']);
                }
            }

            if($motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){
                if(strlen(trim($json_os_recebimento['codigo_peca_1'])) > 0 OR strlen(trim(utf8_decode($json_os_recebimento['codigo_peca_2']))) > 0 OR strlen(trim($json_os_recebimento['codigo_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= 'Código Peças:';
                }
                if(strlen(trim($json_os_recebimento['codigo_peca_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['codigo_peca_1']);
                }
                if(strlen(trim($json_os_recebimento['codigo_peca_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['codigo_peca_2']);
                }
                if(strlen(trim($json_os_recebimento['codigo_peca_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['codigo_peca_3']);
                }

                if(strlen(trim($json_os_recebimento['numero_pedido_1'])) > 0 OR strlen(trim($json_os_recebimento['numero_pedido_2'])) > 0 OR strlen(trim($json_os_recebimento['numero_pedido_3'])) > 0){
                    $obs_motivo_ordem[] .= 'Número Pedidos:';
                }
                if(strlen(trim($json_os_recebimento['numero_pedido_1'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['numero_pedido_1']);
                }
                if(strlen(trim($json_os_recebimento['numero_pedido_2'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['numero_pedido_2']);
                }
                if(strlen(trim($json_os_recebimento['numero_pedido_3'])) > 0){
                    $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['numero_pedido_3']);
                }
            }

            if($motivo_ordem == "Linha de Medicao (XSD)"){
                $obs_motivo_ordem[] .= 'Linha de Medição(XSD):';
                $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['linha_medicao']);
            }
            if($motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
                $obs_motivo_ordem[] .= 'Pedido não fornecido - Valor Mínimo(XSS):';
                $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['pedido_nao_fornecido']);
            }

            if($motivo_ordem == 'Contato SAC (XLR)'){
                $obs_motivo_ordem[] .= 'N° do Chamado:';
                $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['contato_sac']);
            }

            if($motivo_ordem == 'Bloqueio financeiro (XSS)' OR $motivo_ordem == 'Ameaca de Procon (XLR)' OR $motivo_ordem == 'Defeito reincidente (XQR)'){
                $obs_motivo_ordem[] .= "Detalhes:";
                $obs_motivo_ordem[] .= utf8_decode($json_os_recebimento['detalhe']);
            }
        ?>
        <table class='borda' width="600" border="0" cellspacing="0" cellpadding="0">
            <tr><td class='titulo'><?= traduz("observacao.motivo.ordem") ?></td></tr>
            <tr><td class='conteudo'><?php echo implode('<br/>', $obs_motivo_ordem); ?></td></tr>
        </table>
    <?php
        }

//}
?>

<? if($login_fabrica == 117 OR $login_fabrica == 123 OR $login_fabrica == 124 OR $login_fabrica == 127 OR $login_fabrica == 128 AND $login_fabrica == 134 AND $login_fabrica == 136) { ?>

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

if(!in_array($login_fabrica,array(124,126)) && (($login_fabrica == 114 && !in_array($linha, array(691,692,710)) ) || $login_fabrica != 114)) {
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
            if (in_array($login_fabrica, array(141,144)) && in_array($os_tipo_posto, array(452,453))) {
            ?>
                <td class='titulo' ><?= traduz("remanufatura") ?></td>
            <?php
            }
            ?>
        </TR>
        <TR>
            <TD class="conteudo" colspan='<?=$colspan?>'><? echo number_format($qtde_km,2,',','.');?>&nbsp;KM</TD>
            <?php
            if (in_array($login_fabrica, array(141,144)) && in_array($os_tipo_posto, array(452,453))) {
            ?>
                <td class='conteudo'><?=($os_remanufatura == "t") ? "Sim" : "Não"?></td>
            <?php
            }
            ?>
        </TR>
    <?php
    }
    ?>
</TABLE>
<?
}

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

if ($login_fabrica == 2 AND strlen($data_fechamento)>0) {
?>
    <TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
    <? echo "<TR>";
     if(strlen($defeito_constatado) > 0) {
            echo "<TD class='titulo'>$temaMaiusculo</TD>";
            echo "<TD class='titulo'>SOLUÇÃO</TD>";
            echo "<TD class='titulo'>DT FECHA. OS</TD>";
    }
    echo "</TR>";
    echo "<TR>";
    if(strlen($defeito_constatado) > 0) {
            echo "<TD class='conteudo'>$defeito_constatado</TD>";
            echo "<TD class='conteudo'>$solucao</TD>";
            echo "<TD class='conteudo'>$data_fechamento</TD>";
    } ?>
    </TR>
    <TR>
        <TD>&nbsp;</TD>
    </TR>
    </TABLE>
<?
}
?>


<? if ($login_fabrica==19) {
    $sql = "SELECT tbl_laudo_tecnico_os.*
                FROM tbl_laudo_tecnico_os
                WHERE os = $os
                ORDER BY ordem, laudo_tecnico_os;";
    $res = pg_exec($con,$sql);

    if(pg_numrows($res) > 0){
        echo "<br>";
        echo "<TABLE class='borda' width='600px' border='0' cellspacing='0' cellpadding='0'>";
        echo "<TR>";
        echo "<TD colspan='3' TD class='titulo' style='text-align: center'><b>LAUDO TÉCNICO</b></TD>";
        echo "</TR>";
        echo "<TR>";
            echo "<TD class='titulo' style='width: 30%'>&nbsp;".traduz("questao")."&nbsp;</TD>";
            echo "<TD class='titulo' style='width: 20%'>&nbsp;".traduz("afirmacao")."&nbsp;</TD>";
            echo "<TD class='titulo' style='width: 50%'>&nbsp;".traduz("resposta")."&nbsp;</TD>";
        echo "</TR>";

        for($i=0;$i<pg_numrows($res);$i++){
            $laudo            = pg_result($res,$i,laudo_tecnico_os);
            $titulo           = pg_result($res,$i,titulo);
            $afirmativa       = pg_result($res,$i,afirmativa);
            $laudo_observacao = pg_result($res,$i,observacao);

            echo "<TR>";
                echo "<TD class='conteudo'>&nbsp;$titulo&nbsp;</TD>";
                if(strlen($afirmativa) > 0){
                    echo "<TD class='conteudo'>"; if($afirmativa == 't') echo "&nbsp;Sim&nbsp;"; else echo "&nbsp;Não&nbsp;"; echo "</TD>";
                }else{
                    echo "<TD class='conteudo'>&nbsp;&nbsp;</TD>";
                }
                if(strlen($laudo_observacao) > 0){
                    echo "<TD class='conteudo'>&nbsp;$laudo_observacao&nbsp;</TD>";
                }else{
                    echo "<TD class='conteudo'>&nbsp;&nbsp;</TD>";
                }
            echo "</TR>";
        }
        echo "</TABLE>";
        echo "<BR>";
    }
} ?>



<TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD class='titulo' style="text-align: center;">
        <?php 
            if (in_array($login_fabrica, [139])) { 
                echo ($sistema_lingua <> 'BR') ? "Diagnóstico y resolución del problema. Técnico:" : "Diagnóstico e Resolução do Problema. Técnico:";
            } else {
                echo ($sistema_lingua <> 'BR') ? "Diagnóstico, repuesto utilizado y resolución del problema. Técnico:" : "Diagnóstico, Peças usadas e Resolução do Problema. Técnico:";
            }
        ?>
    </TD>
</TR>
<?php if ($login_fabrica <> 3){ ?>
<TR>
    <TD class='conteudo'>
<?
     if (empty($os_auditoria)) {
        echo $peca_dynacom;
    } else {
        echo $msgAviso;
    }
?>
    </TD>
</TR>
<TR>
    <TD>&nbsp;</td>
</TR>

<?}?>

</TABLE>

<?php if($login_fabrica == 19) {?>
<table>
    <TR>
        <TD style='font-size: 11px'><?
        if($login_fabrica==2  AND strlen($data_fechamento)>0){
            $data_hj = date('d/m/Y');
            echo $posto_cidade .", ". $data_hj;
        }else{
            echo $posto_cidade .", ". $data_abertura;
        }
        ?></TD>
    </TR>
    <tr>
        <TD style='font-size: 10px'><? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: ";
            echo"<td style='font-size: 10px'> *". traduz("declaro.estar.retirando.este.produto.devidamente.testado.e.funcionando").".</td> "; ?>
        </TD>
    </tr>
</table>

<table>
    <tr>
        <td colspan="2" style="border-bottom:1px solid #000000;border-top:1px solid #000000; line-height:10px">&nbsp;</td>
    </tr>
    <tr>
        <td style="background-color:#cccccc; width: 50%; font-family: Arial; height: 40px; text-align: center " ><b><?= traduz("prazo.de.entrega.do.produto") ?>:&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;</b></td>
        <td valign="top" style="padding-left:5px; font-family: Arial;  border-left:1px solid #000000; font-size:11px;"><?= traduz("observacoes") ?></td>
    </tr>
</table>
<br><br>
<br>
<br>

<table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td colspan="2"  class='titulo2'><?= traduz("termo.de.retirada.do.produto.pelo.consumidor") ?></td>
        </tr>
        <tr>
            <td colspan="2"  style="border-bottom:1px solid #000000; line-height:10px">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="2"  class='conteudo2'>
                <?= traduz("e.de.total.responsabilidade.do.consumidor.retirar.o.produto.no.sal.servico.autorizado.lorenzetti.ate.o.prazo.de.conserto.ou.troca.do.produto.informado.na.ocasiao.da.entrega.do.produto.para.conserto.ou.troca.caso.o.consumidor.nao.retire.o.produto.ate.a.data.previamente.informada.o.mesmo.estara.passivel.de.cobranca.de.taxa.da.guarda.do.produto") ?>.
                <br><br>
                <?= traduz("este.termo.comprova.a.data.da.realizacao.do.servico.prestado.em.garantia.ate.a.data.prazo.em.que.o.consumidor.devera.retirar.o.seu.produto") ?>.
                <center><b><?= traduz("artigo.40.do.codigo.de.defesa.do.consumidor") ?>.</b></center>
            </td>
        </tr>
        <tr>
            <td colspan="2"  style="border-bottom:1px solid #000000;border-top:1px solid #000000; line-height:10px">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="2"  class='conteudo2'>
                <?= traduz("em.caso.de.abandono./.renuncia.do.produto.pelo.consumidor.situacao.caracterizada.como.ato.unilateral.fica.sob.total.responsabilidade.do.consumidor.a.nao.retirada.do.produto.o.mesmo.deve.expressar.a.sua.vontade.de.abandono./.renuncia.de.seu.bem.por.escrito.o.qual.da.o.direito.ao.sal.servico.autorizado.lorenzetti.tomar.decisao.referente.ao.destino.do.produto.procedendo.com.o.descarte.desmonte.venda.etc") ?>...

                <center> <b><?= traduz("artigo.51.inciso.iv.do.codigo.de.defesa.do.consumidor") ?>;<br>
                <?= traduz("titulo.iii.capitulo.iv.e.artigo.1.275.caput.do.codigo.civil") ?>.</b></center>

            </td>
        </tr>
        <tr>
            <td colspan="2" style="border-bottom:1px solid #000000;border-top:1px solid #000000; line-height:10px">&nbsp;</td>
        </tr>
        <tr>
            <td style="background-color:#cccccc; width: 50%; font-family: Arial; height: 40px; text-align: center " ><b><?= traduz("prazo.de.entrega.do.produto") ?>:&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;</b></td>
            <td valign="top" style="padding-left:5px; font-family: Arial;  border-left:1px solid #000000; font-size:11px;"><?= traduz("observacoes") ?></td>
        </tr>
    </table>
    <br><br>
<?php } ?>

<?php if ($login_fabrica == 6 AND $linha == "TABLET"){ ?>
<TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
    <TD>
    <h2>
        <?= traduz("se.houver.a.necessidade.de.formatacao.em.seu.tablet.informamos.que.todos.os.dados.fotos.videos.musicas.etc.e/ou.possiveis.aplicativos.instalados.serao.perdidos.sem.a.possibilidade.de.recuperacao") ?>.
    </h2>
    </TD>
</TR>
<TR>
<?php } ?>

<?if ($login_fabrica <> 3){
    //fputti hd-2892486
    if (in_array($login_fabrica, array(50))) {
        $sqlOSDec = "SELECT A.consumidor_nome_assinatura, to_char(B.termino_atendimento, 'DD/MM/YYYY')  termino_atendimento
                       FROM tbl_os A
                       JOIN tbl_os_extra B ON B.os=A.os
                      WHERE A.os={$os}";
        $resOSDec = pg_query($con, $sqlOSDec);
        $dataRecebimento = pg_fetch_result($resOSDec, 0, 'consumidor_nome_assinatura');
        $recebidoPor     = pg_fetch_result($resOSDec, 0, 'termino_atendimento');

            echo '<br /><br />
                    <table width="600" border="0" cellspacing="1" style="margin-top: 15px;" cellpadding="0" align="left">
                        <tr>
                            <td align="center">'.traduz("declaracao.de.atendimento").'</TD>
                        </tr>
                        <tr>
                            <td style="font-size: 15px;padding:5px;" align="left">

                                    "'.traduz("declaro.que.houve.o.devido.atendimento.do.posto.autorizado.dentro.do.prazo.legal.sendo.realizado.o.conserto.do.produto.e.apos.a.realizacao.dos.testes.ficou.em.perfeitas.condicoes.de.uso.e.funcionamento.deixando.me.plenamente.satisfeito.a").'."
                                    <p>
                                        <div style="float:left">
                                            '.traduz("produto.entregue.em").': '.$recebidoPor.'
                                        </div>
                                        <div style="float:right">
                                            '.traduz("recebido.por").': '.$dataRecebimento.'
                                        </div>
                                    </p>
                            </td>
                        </tr>
                    </table><br /> <br /> <br /> <br /> <br /> <br /> <br /> <br /> <br />
                    ';
    }
    ?>
<TABLE width="600px" border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD style='font-size: 11px'><?
        if($login_fabrica==2  AND strlen($data_fechamento)>0){
            $data_hj = date('d/m/Y');
            echo $posto_cidade .", ". $data_hj;
        }else{
            echo $posto_cidade .", ". $data_abertura;
        }
        ?></TD>
    </TR>


    <TR>
        <?php if($login_fabrica <> 95) {
            if ($login_fabrica == 158) {
                        $espacamento = "padding-bottom: 50px;";
                    } else {
                        $espacamento = "";
                    }

            ?>
            <TD style='font-size: 10px;<?= $espacamento ?>'><? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: ";
            echo"<td style='font-size: 10px;{$espacamento}'> *".traduz("declaro.estar.retirando.este.produto.devidamente.testado.e.funcionando").".</td> "; ?> </TD> 
        <? }else{?>
            <TD style='font-size: 10px'><? echo $consumidor_nome ?> - <?if($sistema_lingua<>'BR')echo "Firma: "; else echo "Assinatura: _____________________________________________________________________________________________";?><br><br></TD>
        <? }?>
    </TR>
</TABLE>
<?

}





if(in_array($login_fabrica, array(145))){
    echo "<br /> <div style='text-align: left;'>";
        echo "<strong style='font: 12px arial;'>Informações Gerais</strong>";
        for($i = 0; $i <= 9; $i++){
            echo "<div style='border-bottom: 1px solid #999; width: 650px; height: 20px;'></div>";
        }
    echo "</div> <br />";
}

if($login_fabrica==2 AND strlen($peca)>0 AND strlen($data_fechamento)>0 OR $login_fabrica==91) {
    echo $peca_dynacom;
}else if($login_posto <> '14236'){ //chamado = 1460 ?>
<TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
<TR>
    <TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<TABLE width="650px" border="1" cellspacing="0" cellpadding="0">
<TR>
    <? for( $i=0 ; $i < $qtd_etiqueta_os ; $i++) { ?>
        <?if ($i%5==0) { echo "</TR><TR> " ;}?>
    <TD class="etiqueta">
        <?if ($login_fabrica <> 3 AND $login_fabrica <> 117){
            echo  "<b>OS <font size='2px'>$sua_os</font></b><BR>Ref. ". $referencia . "</b> <br> " . $descricao . "<br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone";
            if($login_fabrica == 35){
                echo "<br>$revenda_nome";
            }
        }else{
            if($login_fabrica == 117){
                if($consumidor_revenda == "CONSUMIDOR"){
                    echo "<font size='2px'><b>OS $sua_os</b></font><br><b>$posto_nome </b><br> $descricao<br>N.Série $serie<br>$consumidor_nome<br>$data_abertura";
                }else{
                    echo "<font size='2px'><b>OS $sua_os</b></font><BR>$posto_nome </b><br>$descricao <br>N.Série $serie<br>$revenda_nome<br>$data_abertura";
                }
            }else{
                echo  "<b>OS <font size='2px'>$sua_os</font></b><BR>Desc. " . $descricao . "<br>$consumidor_nome";
            }
        }
        ?>
    </TD>
    <? } ?>
</TR>
</TABLE>
<? }

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
                window.print();
            });
        });
    }
</script>
<?php } elseif (!isset($os_include)) { ?>
    <script language="JavaScript">
        window.print();
    </script>
<?php } ?>


</BODY>
</html>
