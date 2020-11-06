<?
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once "funcoes.php";

if ($login_fabrica == 7) {
    header ("Location: os_print_filizola.php?os=$os");
    exit;
}

if($login_fabrica == 1) {
    include("os_print_blackedecker.php");
    exit;
}

if($login_fabrica == 14) {
    include("os_print_intelbras.php");
    exit;
}

if (in_array($login_fabrica, array(144,167,203))) {// Verifica se o posto da os é Interno

    $sql = "SELECT tbl_os.posto
            FROM tbl_os
            JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = $login_fabrica
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica AND tbl_tipo_posto.posto_interno
            WHERE tbl_posto_fabrica.fabrica = $login_fabrica
            AND tbl_os.os = ".$_GET['os'];

    $res = pg_query($con,$sql);

    if( pg_num_rows($res) > 0) {

        $posto_interno = true;

    }else{

        $posto_interno = false;

    }

}

if ($login_fabrica == 145) {
    $os = trim($_GET["os"]);

    $sql_tipo_os = "SELECT tbl_tipo_atendimento.grupo_atendimento
                        FROM tbl_os
                        LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                        WHERE tbl_os.os = {$os} AND tbl_os.fabrica = {$login_fabrica}";
    $res_tipo_os = pg_query($con, $sql_tipo_os);

    if(pg_num_rows($res_tipo_os) > 0){

        $grupo_atendimento = strtoupper(pg_fetch_result($res_tipo_os, 0, "grupo_atendimento"));

        if($grupo_atendimento == "R" and $login_fabrica == 145){
            header("Location: os_print_revisao.php?os={$os}");
            exit;
        }else{
            header("Location: os_print_visita.php?os={$os}");
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
	0   => 'Data Abertura',
	3   => 'Data de Entrada do Produto No Posto',
    101 => 'Entrada',
	104 => 'Data de Recebimento do Produto',
    157 => 'Data Entrada Prod Assist'
]);
$data_osMaiuscula = getValorFabrica([
	0   => 'DATA ABERTURA',
	3   => 'DATA DE ENTRADA DO PRODUTO NO POSTO',
    101 => 'ENTRADA',
	104 => 'DATA DE RECEBIMENTO DO PRODUTO',
    157 => 'DATA ENTRADA PROD ASSIST'
]);

#------------ Le OS da Base de dados ------------#
$os = $_GET['os'];
if (strlen ($os) > 0) {

    if (in_array($login_fabrica, [169,170])) {
        $campoCausaDefeito = "tbl_causa_defeito.descricao AS causa_defeito,";
        $joinCausaDefeito = "   JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
                                JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                LEFT JOIN tbl_causa_defeito ON tbl_causa_defeito.causa_defeito = tbl_os_item.causa_defeito AND tbl_causa_defeito.fabrica = {$login_fabrica}";
    }

    $col_serie = in_array($login_fabrica,array(138)) ? 'tbl_os_produto.serie' : 'tbl_os.serie';

    $sql = "SELECT  tbl_os.os                                                      ,
                    tbl_os.sua_os                                                  ,
                    to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
                    to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                    tbl_os.consumidor_nome                                         ,
                    tbl_os.consumidor_fone                                         ,
                    tbl_os.consumidor_celular                                      ,
                    tbl_os.consumidor_fone_comercial AS consumidor_fonecom         ,
                    tbl_os.consumidor_endereco                                     ,
                    tbl_os.consumidor_numero                                       ,
                    tbl_os.consumidor_complemento                                  ,
                    tbl_os.consumidor_bairro                                       ,
                    tbl_os.consumidor_cep                                          ,
		    {$campoCausaDefeito}
                    tbl_os.consumidor_cidade                                       ,
                    tbl_os.consumidor_estado                                       ,
                    tbl_os.consumidor_cpf                                          ,
                    tbl_os.revenda_cnpj                                            ,
                    tbl_os.revenda_nome                                            ,
                    tbl_os.nota_fiscal                                             ,
                    tbl_os.qtde_diaria,
                    to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf        ,
                    tbl_os.defeito_reclamado_descricao                             ,
                    tbl_os.acessorios                                              ,
                    tbl_os.produto,
                    tbl_os.aparencia_produto                                       ,
                    tbl_defeito_reclamado.descricao AS defeito_reclamado_cliente   ,
                    tbl_os.consumidor_revenda                                      ,
                    tbl_os.excluida                                                ,
                    tbl_os.capacidade                                              ,
                    tbl_os.prateleira_box                                          ,
                    tbl_os.consumidor_nome_assinatura AS contato_consumidor        ,
                    tbl_os.condicao AS contador                                    ,
                    tbl_produto.referencia                                         ,
                    tbl_produto.referencia_fabrica                                 ,
                    tbl_produto.descricao                                          ,
                    tbl_produto.familia                                            ,
                    $col_serie                                                     ,
                    tbl_os.rg_produto                                              ,
                    tbl_os.tipo_atendimento                                        ,
                    tbl_os.serie_reoperado,
                    tbl_os.embalagem_original,
                    tbl_os_extra.serie_justificativa                               ,
                    to_char(tbl_os_extra.inicio_atendimento,'DD/MM/YYYY HH24:MI:SS') AS inicio_atendimento,
                    to_char(tbl_os_extra.termino_atendimento,'DD/MM/YYYY HH24:MI:SS') AS termino_atendimento,
                    tbl_os_extra.regulagem_peso_padrao                               ,
                    tbl_os_extra.hora_tecnica                                      ,
                    tbl_os_extra.qtde_horas                                        ,
                    tbl_os_extra.recolhimento                                      ,
                    tbl_os_extra.obs                                               ,
                    tbl_os.observacao                                               ,
                    tbl_os.obs  AS obs_abertura                                             ,
                    tbl_os_extra.obs_adicionais                                    ,
                    tbl_os.tecnico,
                    tbl_os.tecnico_nome,
                    tbl_tipo_atendimento.descricao             AS nome_descricao   ,
                    tbl_os.qtde_produtos                                           ,
                    tbl_os.tipo_os                                                 ,
                    tbl_os.codigo_fabricacao                                       ,
                    tbl_defeito_constatado.descricao          AS defeito_constatado,
                    tbl_solucao.descricao                                AS solucao,
                    tbl_os.finalizada                                               ,
                    tbl_os.data_conserto                                            ,
                    tbl_os.qtde_km                                                  ,
                    tbl_os.certificado_garantia                                     ,
                    tbl_os.cortesia                                                 ,
                    tbl_os.contrato                                                 ,
                    tbl_os.justificativa_adicionais                                 ,
                    tbl_posto_fabrica.contato_cidade                                ,
                    tbl_posto_fabrica.contato_endereco       as endereco_posto      ,
                    tbl_posto_fabrica.contato_numero         as numero_posto        ,
                    tbl_posto_fabrica.contato_bairro         as bairro_posto        ,
                    tbl_posto_fabrica.contato_cep            as cep_posto           ,
                    tbl_posto_fabrica.contato_cidade         as cidade_posto        ,
                    tbl_posto_fabrica.contato_estado         as estado_posto        ,
                    tbl_posto_fabrica.contato_fone_comercial as fone                ,
                    tbl_posto.nome                           AS nome_posto          ,
                    tbl_posto.posto                          AS id_posto            ,
                    tbl_posto.cnpj                           AS posto_cnpj          ,
                    tbl_posto.ie                             AS posto_ie            ,    
                    upper(tbl_linha.nome)                    AS linha               ,
                    tbl_os.qtde_hora                                                ,
                    tbl_os.hora_tecnica                      AS os_hora_tecnica     ,
                    tbl_os.troca_garantia ";

    if ($login_fabrica == 176)
    {
        $sql .= " , tbl_os.type ";
    }

    if(in_array($login_fabrica, array(138,142,143,145,158))){
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
		    {$joinCausaDefeito}
                    WHERE   tbl_os.os = $os";
    }

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) == 1) {
	if (in_array($login_fabrica, [169, 170])) {
            $array_causa_defeito = pg_result ($res,0,'causa_defeito');
        }
        $os                 = pg_result ($res,0,os);
        $sua_os             = pg_result ($res,0,sua_os);
        $data_abertura      = pg_result ($res,0,data_abertura);
        $data_fechamento    = pg_result ($res,0,data_fechamento);
        if ( !in_array($login_fabrica, array(7,11,15,172)) ) { 
            $box_prateleira =  trim(pg_result ($res,0,prateleira_box));
        }
        $consumidor_nome             = pg_result ($res,0,consumidor_nome);
        $consumidor_endereco         = pg_result ($res,0,consumidor_endereco);
        $consumidor_numero           = pg_result ($res,0,consumidor_numero);
        $consumidor_complemento      = pg_result ($res,0,consumidor_complemento);
        $consumidor_bairro           = pg_result ($res,0,consumidor_bairro);
        $consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
        $consumidor_estado           = pg_result ($res,0,consumidor_estado);
        $consumidor_cep              = pg_result ($res,0,consumidor_cep);
        $consumidor_cpf              = pg_result ($res,0,consumidor_cpf);
        $consumidor_referencia       = pg_result ($res,0,obs);
        $consumidor_fone             = pg_result ($res,0,consumidor_fone);
        $consumidor_celular             = pg_result($res,0,'consumidor_celular');
        $consumidor_fonecom             = pg_result($res,0,'consumidor_fonecom');
        $revenda_cnpj       = pg_result ($res,0,revenda_cnpj);
        $revenda_nome       = pg_result ($res,0,revenda_nome);
        $nota_fiscal        = pg_result ($res,0,nota_fiscal);
        $data_nf            = pg_result ($res,0,data_nf);
        $defeito_reclamado  = pg_result ($res,0,defeito_reclamado_cliente);
        $aparencia_produto  = pg_result ($res,0,aparencia_produto);
        $produto    = pg_result ($res,0,produto);
        $acessorios         = pg_result ($res,0,acessorios);
        $defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
        $consumidor_revenda = pg_result ($res,0,consumidor_revenda);
        $excluida           = pg_result ($res,0,excluida);
        $referencia         = pg_result ($res,0,referencia);
        $modelo             = pg_result ($res,0,referencia_fabrica);
        $produto_referencia_fabrica = pg_result ($res,0,referencia_fabrica);
        $descricao          = pg_result ($res,0,descricao);
        $serie              = pg_result ($res,0,serie);
        $hora_tecnica                   = pg_fetch_result ($res,0,'hora_tecnica');
        $qtde_horas                     = pg_fetch_result ($res,0,'qtde_horas');
        $serie_justificativa = pg_result ($res,0,serie_justificativa);
        $codigo_fabricacao  = pg_result ($res,0,codigo_fabricacao);
        $tipo_atendimento   = trim(pg_result($res,$i,tipo_atendimento));
        $qtde_diaria = pg_fetch_result($res, 0, "qtde_diaria");
        $serie_reoperado             = pg_fetch_result ($res,0, serie_reoperado);
        $embalagem_original     = pg_fetch_result($res, 0, embalagem_original);
        $inicio_atendimento     = pg_fetch_result($res, 0, inicio_atendimento);
        $termino_atendimento     = pg_fetch_result($res, 0, termino_atendimento);
        $regulagem_peso_padrao     = pg_fetch_result($res, 0, regulagem_peso_padrao);
        $obs_abertura     = pg_fetch_result($res, 0, obs_abertura);
        $observacao     = pg_fetch_result($res, 0, observacao);
	$troca_garantia	= pg_fetch_result($res,0,'troca_garantia');

        if ($login_fabrica == 175){
            $id_posto = pg_fetch_result($res, 0, 'id_posto');
        }

        if (in_array($login_fabrica, array(156))) {
            $void = $serie_reoperado;
            $sem_ns = $embalagem_original;
        }

        if ($login_fabrica == 143) {
            $rg_produto = pg_fetch_result($res, 0, "rg_produto");
        }

        if(in_array($login_fabrica, [167, 203])){
            $contato_consumidor = pg_fetch_result($res, 0, "contato_consumidor");
            $contador = pg_fetch_result($res, 0, "contador");
        }

        if ($login_fabrica == 175){
            $qtde_disparos = pg_fetch_result($res, 0, 'capacidade');
        }
        if ($login_fabrica == 148) {
            $os_horimetro = pg_fetch_result($res, 0, "qtde_hora");
            $os_revisao = pg_fetch_result($res, 0, "os_hora_tecnica");

            $obs_adicionais_json = json_decode(pg_fetch_result($res, 0, "obs_adicionais"));

            $serie_motor       = $obs_adicionais_json->serie_motor;
            $serie_transmissao = $obs_adicionais_json->serie_transmissao;
        }

        if($login_fabrica == 137){

            $dados  = pg_result($res, 0, rg_produto);

            $dados          = json_decode($dados);
            $cfop           = $dados->cfop;
            $valor_unitario = $dados->vu;
            $valor_nota     = $dados->vt;

        }

        if($login_fabrica == 158){
            $dadoscockpit = json_decode(pg_result($res, 0, dadoscockpit), true);
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

        $tecnico                      = trim(pg_fetch_result($res,0,tecnico));
        $tecnico_nome                 = trim(pg_fetch_result($res,0,tecnico_nome));
        $nome_atendimento   = trim(pg_result($res,0,nome_descricao));
        $qtde_produtos                  = pg_result($res,0,qtde_produtos);
        $tipo_os                        = trim(pg_result($res,0,tipo_os));
        $defeito_constatado             = trim(pg_result($res,0,defeito_constatado));
        $solucao                        = trim(pg_result($res,0,solucao));
        $familia                        = trim(pg_result($res,0,familia));
        $finalizada                     = pg_result($res,0,finalizada);
        $data_conserto                  = pg_result($res,0,data_conserto);
        $qtde_km                        = pg_result($res,0,qtde_km);

        /* FRICON */
        $nome_posto                       = pg_result($res,0,nome_posto);
        $endereco_posto                   = pg_result($res,0,endereco_posto);
        $numero_posto                     = pg_result($res,0,numero_posto);
        $bairro_posto                     = pg_result($res,0,bairro_posto);
        $cidade_posto                     = pg_result($res,0,cidade_posto);
        if ($login_fabrica == 19) {
            $posto_cnpj                   = pg_result($res,0,posto_cnpj);
            $posto_ie                     = pg_result($res,0,posto_ie);
            $posto_fone                   = pg_result($res,0,fone);
        }
        $estado_posto                     = pg_result($res,0,estado_posto);
        $cep_posto                        = pg_result($res,0,cep_posto);

        $certificado_garantia           = trim(pg_result($res,0,'certificado_garantia'));
        $linha                          = trim(pg_result($res,0,'linha'));

        $certificado_garantia = ($certificado_garantia AND $certificado_garantia != "null") ? "$certificado_garantia" : "";

        $posto_cidade                   = pg_result($res,0,contato_cidade);
        $cortesia                   = pg_result($res,0,cortesia);
        $cortesia = ($cortesia == "t") ? "Sim" : "Não";

        $obs_adicionais              = json_decode(utf8_encode(pg_result ($res,0,'obs_adicionais')),true);

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

    if (pg_num_rows($res) > 1 && in_array($login_fabrica, [169, 170])) {
        unset($array_causa_defeito);
        for ($c = 0; $c < pg_num_rows($res); $c++) {
            $array_causa_defeito[] = pg_result ($res,$c,'causa_defeito');
        }
        $array_causa_defeito = implode(',', $array_causa_defeito);
    }

    if (strlen($tecnico) > 0) {
        $sql = "SELECT nome FROM tbl_tecnico WHERE tecnico = {$tecnico};";
        $res_tecnico = pg_query($con, $sql);

        if (pg_num_rows($res_tecnico)) {
            $tecnico_nome = pg_result($res_tecnico, 0, nome);
        }
    }

    if (in_array($login_fabrica,array(2,20,115,116,117,120,201,123,124,125,126,127,128,129,131,134,136))) { //HD 21549 27/6/2008
        $cond_left = (in_array($login_fabrica, array(20))) ? " LEFT " : "";
        $sql_item = "
            SELECT
            tbl_os_item.peca,
            tbl_peca.referencia AS peca_referencia,
            tbl_peca.descricao AS peca_descricao,
            tbl_os_item.qtde AS peca_qtde,
            tbl_os_item.defeito,
            tbl_defeito.descricao AS descricao_defeito,
            tbl_os_item.servico_realizado,
            tbl_servico_realizado.descricao AS descricao_servico_realizado
            FROM tbl_os_item
            JOIN tbl_os_produto USING(os_produto)
            JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
            LEFT JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_item.defeito AND tbl_defeito.fabrica = {$login_fabrica}
            {$cond_left} JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
            JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
            WHERE tbl_os.os = {$os};
        ";

        $res_item = pg_query($con,$sql_item);

        if(pg_num_rows($res_item) > 0) {

            if(!in_array($login_fabrica,array(20,115,116,117,123,124,125,126,127,128,129,131,134,136,138,143)) && !isset($novaTelaOs)){

                $peca_dynacom  = "<TABLE  width='600px' align='center' border='0' cellspacing='0' cellpadding='0'>";
                $peca_dynacom .= "<TR>";
                $peca_dynacom .= "<TD colspan='4'><BR></TD>";
                $peca_dynacom .= "</TR>";
                $peca_dynacom .= "</TABLE>";
            }
            $peca_dynacom  .= "<TABLE class='borda'  align='center' width='600px' border='0' cellspacing='0' cellpadding='0'>";
            $peca_dynacom .= "<TR>";
            $peca_dynacom .= "<TD class='titulo'>PEÇA</TD>";

            $peca_dynacom .= "<TD class='titulo'><center>QTDE</center></TD>";

            if(!in_array($login_fabrica,array(20,115,116,117,123,124,125,126,127,128,129,131,134,136,138,143)) && !isset($novaTelaOs)){

                $peca_dynacom .= "<TD class='titulo'>DEFEITO</TD>";
            }
            $peca_dynacom .= "<TD class='titulo'>SERVIÇO</TD>";
            $peca_dynacom .= "</TR>";

            for($z=0; $z<pg_numrows($res_item); $z++){
                $peca                        = pg_result($res_item, $z, peca);
                $peca_referencia             = pg_result($res_item, $z, peca_referencia);
                $peca_descricao              = pg_result($res_item, $z, peca_descricao);
                $peca_qtde                   = pg_result($res_item, $z, peca_qtde);
                $descricao_defeito           = pg_result($res_item, $z, descricao_defeito);
                $descricao_servico_realizado = pg_result($res_item, $z, descricao_servico_realizado);

                $peca_dynacom .= "<TR>";
                $peca_dynacom .= "<TD class='conteudo'>$peca_referencia - ".substr($peca_descricao,0,25)."</TD>";
                $peca_dynacom .= "<TD class='conteudo'><center>$peca_qtde</center></TD>";

                if(!in_array($login_fabrica,array(20,115,116,117,123,124,125,126,127,128,129,131,134,136,138,143)) && !isset($novaTelaOs)){

                    $peca_dynacom .= "<TD class='conteudo'>$descricao_defeito</TD>";
                }

                $peca_dynacom .= "<TD class='conteudo'>$descricao_servico_realizado</TD>";
                $peca_dynacom .= "</TR>";
            }
            $peca_dynacom .= "</TABLE>";
        }
    }
}

if (strlen($sua_os) == 0) $sua_os = $os;

if ($consumidor_revenda == 'C'){
    $consumidor_revenda = ($login_fabrica == 122) ? 'CLIENTE' : 'CONSUMIDOR';
}else if ($consumidor_revenda == 'R'){
    $consumidor_revenda = 'REVENDA';
}else if ($login_fabrica == 178 AND $consumidor_revenda == "S"){
    $consumidor_revenda = "CONSTRUTORA";
}

function convertDataBR($data){
    $dt = explode('-',$data);

    return $dt[2].'/'.$dt[1].'/'.$dt[0];
}


$title = "Ordem de Serviço Balcão - Impressão";
?>

<style type="text/css">

body {

    font: <?=(in_array($login_fabrica,array(52,158))) ? "13px" : "8px"; ?> arial;
    margin: 0px;
}

.texto_termos{
    width: 600px;
    margin-top:3px;

    font: <?=(in_array($login_fabrica,array(52,158))) ? "13px" : "8px"; ?> arial;
}

.texto_termos p{
    font: <?=(in_array($login_fabrica,array(52,158))) ? "13px" : "8px"; ?> 'Arial' !important;
    text-align: justify;
    margin: 0 0 5px 0;
}

.titulo {
    font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: <?=(in_array($login_fabrica,array(52,158))) ? "13px" : "8px"; ?>;
    text-align: left;
    color: #000000;
    background: #D0D0D0;
    border-bottom: dotted 1px #a0a0a0;
    border-right: dotted 1px #a0a0a0;
    border-left: dotted 1px #a0a0a0;
    padding: 1px,1px,1px,1px;
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
    font-size: 10px;
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
.conteudo {
    font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: <?=(in_array($login_fabrica,array(52,158))) ? "13px" : "8px"; ?>;
    text-align: left;
    background: #ffffff;
    border-right: dotted 1px #a0a0a0;
    border-left: dotted 1px #a0a0a0;
    padding: 1px,1px,1px,1px;
}
.conteudo_destaque {
    font-size: 9px;
    text-align: left;
    background: #ffffff;
    border-right: dotted 1px #a0a0a0;
    border-left: dotted 1px #a0a0a0;
    padding: 1px,1px,1px,1px;
}

.borda {
    border: solid 1px #c0c0c0;
}

.etiqueta {
    font: <?=(in_array($login_fabrica,array(52,158))) ? "13px" : "8px"; ?> arial;
    width: 120px;
    text-align: center;
}

table tr td{
    font: <?=(in_array($login_fabrica,array(52,158))) ? "13px" : "8px"; ?> arial;
}
</style>

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
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <link type="text/css" rel="stylesheet" href="css/css_press.css">

</head><?php

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
                $img_contrato = "logos/$logo";
            } else {
                $img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
            }

        }

    } elseif($login_fabrica <> 59) { //2132809

        if(isset($novaTelaOs)){

            if (in_array($login_fabrica, array(174,175)) ){
                $img_contrato = "../logos/logo_".strtolower($login_fabrica_nome).".png";
            }else{
                $img_contrato = "../logos/logo_".str_replace(" ", "_", strtolower($login_fabrica_nome)).".jpg";
            }

        }else{

            if ($cliente_contrato == 'f'){
                $img_contrato = ($login_fabrica == 59) ? "logos/sight_admin1.jpg" : "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
            }else{
                $img_contrato = ($login_fabrica == 59) ? "logos/sight_admin1.jpg" : "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
            }

            if ($familia == 2680 || $familia == 2681) {//HD 246018
                $img_contrato = "logos/cabecalho_print_itatiaia.jpg";
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

    if ($login_fabrica == 177) {
        $img_contrato = "logos/logo_anauger.png";
    }
    if ($login_fabrica == 186) {
        $img_contrato = "logos/mq_professional_logo.png";
    }
    
    if ($login_fabrica == 144) {
        $img_contrato = "logos/logo_hikari.jpg";
    }
    
    ?>
<body>

<TABLE width="600px" border="0" cellspacing="1" cellpadding="0" style="font: 12px arial;">
    <?php
    if($login_fabrica == 52){
        ?>
            <tr>
                <td colspan="4" align="right">
                    <strong style="font: 12px arial; font-weight: bold;">Via do Consumidor</strong>
                </td>
            </tr>
            <TR class="conteudo">
                <TD>
                    <IMG SRC="<? echo ($img_contrato); ?>" height="40" ALT="ORDEM DE SERVIÇO">
                </TD>
                <td align="center">
                    <strong>POSTO AUTORIZADO</strong> <br />
                    <?php
                        echo ($nome_posto != "") ? $nome_posto."<br />" : "";
                        echo ($endereco_posto != "") ? $endereco_posto : "";
                        echo ($numero_posto != "") ? $numero_posto.", " : "";
                        echo ($bairro_posto != "") ? $bairro_posto.", " : "";
                        echo ($cep_posto != "") ? " <br /> CEP: ".$cep_posto." " : "";
                        echo ($cidade_posto != "") ? $cidade_posto." - " : "";
                        echo ($estado_posto != "") ? $estado_posto : "";
                    ?>
                </td>
                <td align="center" class="borda" style="padding: 5px;">
                    <strong>DATA EMISSÃO</strong> <br />
                    <?=date("d/m/Y");?>
                </td>
                <td align="center" class="borda" style="padding: 5px;">
                    <strong>NÚMERO OS</strong> <br />
                    <?=$os;?>
                </td>
            </TR>
        <?php
    }else{
        ?>
            <TR>
                <TD style="padding-top: 5px; padding-bottom: 5px;">
                    <?php if ($login_fabrica == 11): #HD 891549 ?>
                        <label style="font:bold 12px;">Aulik Ind. e Com. Ltda.</label>
                    <?php else: ?>
                        <IMG SRC="<? echo ($img_contrato); ?>" height="40" width='240' ALT="ORDEM DE SERVIÇO">
                    <?php endif ?>
                </TD>
                <?php 
                    if ($login_fabrica == 175){
                        include_once "class/tdocs.class.php";
                        $amazonTC = new TDocs($con, 10);
                        $documents = $amazonTC->getdocumentsByRef($id_posto, 'logomarca_posto')->attachListInfo;
                        if (count($documents) > 0){
                            foreach ($documents as $key => $value) {
                                $link_logo_tdocs = $value['link'];
                            }
                        }
                        if (!empty($link_logo_tdocs)){
                ?>
                            <TD style="padding-top: 5px; padding-bottom: 5px;">
                                <IMG SRC="<?=$link_logo_tdocs?>" height="60">
                            </TD>
                <?php            
                        }
                    }
                ?>
            </TR>
        <?php
    }
    ?>
    <?php if($login_fabrica == 19) { ?>
      <TD style="font-size: 08px; text-align: center;" >
                    <? if ($login_fabrica <> 3){

                            echo "POSTO AUTORIZADO </font><BR>";
                        }
                        echo substr($nome_posto,0,30);
                    
                        ?>
                    </TD>
                <TD style="font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 07px;"><? echo "DATA EMISSÃO"?></TD>
                <TD style="font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 07px;"><? echo "NÚMERO OS";?></TD>
            </TR>
            <TR style="font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 07px;">
                <TD style="font-size: 09px; text-align: center; width: 350px; "><?php
                    ########## CABECALHO COM DADOS DO POSTOS ##########
                    echo $endereco_posto .",".$numero_posto." - CEP ".$cep_posto."<br>";
                    echo $cidade_posto ." - ".$estado_posto." - Telefone: ".$posto_fone."<br>";
                    echo "CNPJ/CPF ";
                    echo $posto_cnpj;
                    echo " - IE/RG ";
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
        <?php }?>
    


</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">

<? if ($excluida == "t") { ?>
<TR>
    <TD colspan="<? if ($login_fabrica == 1) echo '6'; else echo '5'; ?>" bgcolor="#FFE1E1" align="center"><h1>ORDEM DE SERVIÇO EXCLUÍDA</h1></TD>
</TR>
<? } ?>

<TR>
    <TD class="titulo" colspan="<? if ($login_fabrica==1 or $login_fabrica==96) echo '6'; else echo '5'; ?>">Informações sobre a Ordem de Serviço </TD>
</TR>
<TR>
     <?php
    $colspanDataAbertura = 2;

    if ($login_fabrica == 148) {
        $colspanDataAbertura = 4;
    }

    if (in_array($login_fabrica, array(143,167,175,176,177,203))) {
        $colspanDataAbertura = 3;
    }
    ?>
    <TD class="titulo">OS FABR.</TD>
    <TD class="titulo" colspan='<?=$colspanDataAbertura?>'><?=$data_osMaiuscula?> </TD>
    <!-- <TD <?=($login_fabrica==1 or $login_fabrica==96)?'colspan="2" ':''?>class="titulo">REF.</TD>//-->
</TR>

<?php
    if(in_array($login_fabrica, [167, 203])){
        $class_os = "class='conteudo_destaque'";
    }else{
        $class_os = "class='conteudo'";
    }
?>
<TR>
    <TD <?=$class_os?>>
        <strong>
    <?
    echo implode(' - ', array_filter(array($sua_os, $consumidor_revenda)));
    /* if (strlen($consumidor_revenda) > 0){
            echo $sua_os ." - ". $consumidor_revenda;
        }else if (strlen($consumidor_revenda) == 0){
                echo $sua_os;
        }*/
    ?>
        </strong>
    </TD>
    <TD class="conteudo" colspan='<?=$colspanDataAbertura?>'><? echo $data_abertura ?></TD>
    <!-- <TD class="conteudo"><? echo $referencia ?></TD>//-->
</TR>
<?php
if ($login_fabrica == 30) {
    $sqlV = "SELECT  to_char(data,'DD/MM/YYYY') AS data_agendamento
                    FROM tbl_os_visita WHERE os = {$os}
                ORDER BY data";
    $resV = pg_query($con,$sqlV);
    if (pg_num_rows($resV) > 0) {
        for($j = 0; $j < pg_num_rows($resV); $j++){
                $data_agendamento = pg_fetch_result($resV, $j, 'data_agendamento');
                $ln = $j + 1;
        ?>
                <tr>
                    <td class="titulo" height='15' nowrap colspan="3">DATA AGENDAMENTO <?=$ln?></td>

                </tr>
                <tr>
                    <td class="conteudo" height='15' colspan="3"><?=$data_agendamento?></td>
                </tr>
        <?php
            }
    }
}

if(in_array($login_fabrica, array(87))){?>
    <TR>
        <TD class="titulo" colspan="3">
            <table width='100%' border="0" cellspacing="0" cellpadding="0" class='Tabela' align='center'>
                <tr>
                    <td class="titulo" height='15' nowrap>TIPO ATENDIMENTO</td>
                    <td class="titulo" height='15' nowrap>HORAS TRABALHADAS</td>
                    <td class="titulo" height='15' nowrap>HORAS TÉCNICAS</td>
                </tr>
                <tr>
                    <td class="conteudo"  height='15' >&nbsp;
                    <?php
                        if(intval($tipo_atendimento) > 0){
                            $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                            $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);

                            echo  pg_fetch_result($res_tipo_atendimento,0,'descricao');
                        }
                    ?>
                    </td>
                    <td class="conteudo" height='15'>&nbsp;<? echo $hora_tecnica; ?></td>
                    <td class="conteudo" height='15'>&nbsp;<? echo $qtde_horas; ?></td>
                </tr>
            </table>
        </TD>
    </TR>
<?php }?>
<TR>
    <? if ($login_fabrica == 96) { ?>
        <TD class="titulo">MODELO</TD>
    <? } else { ?>
        <TD class="titulo">REF.</TD>
    <? } ?>
        <TD class="titulo">DESCRIÇÃO</TD>
    <? if($login_fabrica <> 127 && $login_fabrica <> 171){ ?>
        <td class="titulo" <?=($login_fabrica == 148) ? "colspan='3'" : ""?>>SÉRIE</td>
        <? if (in_array($login_fabrica, array(156))) { ?>
            <td class="titulo">VOID</td>
        <? }
    }
    
    if ($login_fabrica == 177){
    ?>
        <td class="titulo">LOTE</td>
    <?php
    }

    if ($login_fabrica == 175){
    ?>
        <td class="titulo">QTDE DISPAROS</td>
    <?php    
    }

        if ($login_fabrica == 176)
        {
    ?>
            <td class="titulo">ÍNDICE</td>
    <?php
        }

    if(in_array($login_fabrica, [167, 203])){
    ?>
    <td class='titulo'>CONTADOR</td>
    <?
    }
    if (in_array($login_fabrica, array(143))) { ?>
        <TD class="titulo">HORIMETRO</TD>
    <? }
    if ($login_fabrica == 1) { ?>
    <TD class="titulo">CÓD. FABRICAÇÃO</TD>
    <? }
    if ($login_fabrica == 19) { ?>
        <TD class="titulo">QTDE</TD><?php
    } ?>
</TR>

<TR>
    <?if ($login_fabrica == 96) { ?>
        <TD class="conteudo"><?= $modelo ?></TD>
    <? } else { ?>
        <TD class="conteudo"><? echo $referencia ?> <? echo ($login_fabrica == 171) ? " / " . $produto_referencia_fabrica : ""; ?></TD>
    <? } ?>
    <TD class="conteudo"><?= $descricao ?></TD>
    <? if($login_fabrica != 127 && $login_fabrica != 171) { ?>
        <td class="conteudo" <?=($login_fabrica == 148) ? "colspan='3'" : ""?>>
            <? if (in_array($login_fabrica, array(156)) && $sem_ns == "t") {
                $serie = "Sem número de série";
            }
            echo $serie;
            ?>
        </td>
        <?php if ($login_fabrica == 177){ ?>
            <td class="conteudo"><?=$codigo_fabricacao?></td>
        <?php } ?>
        <?php if ($login_fabrica == 175){ ?>        
            <td class="conteudo"><?=$qtde_disparos?></td>
        <?php } ?>
        <? if (in_array($login_fabrica, array(156))) { ?>
            <td class="conteudo"><?= $void; ?></td>
        <? }

        if (in_array($login_fabrica, [167, 203])) { ?>
            <td class='conteudo'><?=$contador?></td>
        <? }
    }

    if ($login_fabrica == 176)
    {
?>
        <td class='conteudo'><?php echo $indice; ?></td>
<?php
    }

    if (in_array($login_fabrica, array(143))) { ?>
        <TD class="conteudo"><?=$rg_produto?></TD>
    <? }
    if ($login_fabrica == 1) { ?>
    <TD class="conteudo"><?= $codigo_fabricacao ?></TD>
    <? } ?>
    <?if ($login_fabrica == 19) {?>
        <TD class="conteudo"><?= $qtde_produtos ?></TD><?php
    }?>
</TR>
<? if (in_array($login_fabrica, array(169,170))) {
    if (number_format($qtde_km,2,',','.') > 0) { ?>
        <tr>
            <td class="titulo">PRODUTO RETIRADO PARA A OFICINA</td>
            <td class="titulo" colspan="2">EMPRÉSTIMO DE PRODUTO PARA O CONSUMIDOR</td>
        </tr>
        <tr>
            <td class="conteudo"><?= ($recolhimento == "t") ? "Sim" : "Não"; ?></td>
            <td class="conteudo" colspan="2"><?= ($produto_emprestimo == "t") ? "Sim" : "Não"; ?></td>
        </tr>
    <? }
}
if (in_array($login_fabrica, array(158))) { ?>
<TR>
    <TD class="titulo">NÚMERO DA MATRICULA DO CLIENTE</TD>
    <TD class="titulo">COMENTARIO KOF</TD>
    <TD class="titulo"></TD>
</TR>
<TR>
    <TD class="conteudo"><? echo $dadoscockpit['idCliente'] ?></TD>
    <TD class="conteudo"><? echo $dadoscockpit['comentario'] ?></TD>
    <TD class="conteudo"></TD>
</TR>
<? }
if ($login_fabrica == 148) { ?>
    <tr>
        <td class="titulo" >N. DE SÉRIE MOTOR</td>
        <td class="titulo" >N. DE SÉRIE TRANSMISSÃO</td>
        <td class="titulo" >HORIMETRO</td>
        <td class="titulo" >REVISÃO</td>
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
        <td class="titulo" colspan="3">PATRIMÔNIO</td>
    </tr>
    <tr>
        <td class="conteudo" colspan="3"><?= $serie_justificativa; ?></td>
    </tr>
<? }
if($login_fabrica == 86 and $serie_justificativa != 'null'){ // HD 328591?>
    <tr>
        <td colspan='5' class='titulo'>JUSTIFICATIVA NÚMERO SÉRIE</td>
    </tr>
    <tr>
        <td colspan='5' class="conteudo"><? echo $serie_justificativa ?></td>
    </tr>
<? } ?>

     <?php
        if(in_array($login_fabrica, array(138)) && $coun_os_produto > 1){
    ?>

            <TR>
                <TD class="titulo">REFERÊNCIA SUBCONJUNTO</TD>
                <TD class="titulo">DESCRIÇÃO SUBCONJUNTO</TD>
                <TD class="titulo">SÉRIE SUBCONJUNTO</TD>
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

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<?php
    if($login_fabrica == 20){
?>
        <TR>
            <TD class="titulo">NOME DO CONSUMIDOR</TD>
            <TD class="titulo">FONE</TD>
            <TD class="titulo">CELULAR</TD>
            <TD class="titulo">CPF/CNPJ CONSUMIDOR</TD>
        </TR>
        <TR>
            <TD class="conteudo"><? echo $consumidor_nome ?></TD>
            <TD class="conteudo"><? echo $consumidor_fone ?></TD>
            <TD class="conteudo"><? echo $consumidor_celular ?></TD>
            <TD class="conteudo"><? echo $consumidor_cpf ?></TD>
        </TR>
<?php
    }else{
?>
        <TR>
            <TD class="titulo"><? echo ($login_fabrica == 122) ? "NOME DO CLIENTE" : "NOME DO CONSUMIDOR"; ?></TD>
            <TD class="titulo">CIDADE</TD>
            <TD class="titulo">ESTADO</TD>
            <TD class="titulo">FONE</TD>
            <?php if(in_array($login_fabrica, [167, 203])){ ?>
            <td class='titulo'>CONTATO</td>
            <?php } ?>
            <?php if($login_fabrica == 158){ ?>
            <td class='titulo'>CELULAR</td>
            <?php } ?>
        </TR>
        <TR>
            <TD class="conteudo"><? echo $consumidor_nome ?></TD>
            <TD class="conteudo"><? echo $consumidor_cidade ?></TD>
            <TD class="conteudo"><? echo $consumidor_estado ?></TD>
            <TD class="conteudo"><? echo $consumidor_fone ?>
            <?php
            if($login_fabrica == 52){
                $sql = "SELECT tbl_hd_chamado_extra.celular FROM tbl_hd_chamado_extra INNER JOIN tbl_os ON tbl_os.os = $os AND tbl_os.hd_chamado = tbl_hd_chamado_extra.hd_chamado";
                $res = pg_query($con, $sql);
                if(pg_num_rows($res) > 0){
                    $consumidor_celular = pg_fetch_result($res, 0, 'celular');
                    if(strlen($consumidor_celular) > 0){
                        $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = '$os'";
                        $res = pg_query($con, $sql);
                        if(pg_num_rows($res) > 0){
                            $campos_adicionais2 = pg_fetch_result($res, 0, 'campos_adicionais');
                            $dados = json_decode($campos_adicionais2);
                            $operadora_celular = $dados->operadora;
                            echo "<br />".$consumidor_celular." / Operadora: ".$operadora_celular;
                        }else{
                            echo "";
                        }
                    }
                }
            }
            if ($login_fabrica == 158) {
                echo "<td>$consumidor_celular</td>\n";
            }
                ?>

            </TD>
            <?php if(in_array($login_fabrica, [167, 203])){ ?>
            <td class='conteudo'><?=$contato_consumidor?></td>
            <?php } ?>
        </TR>
<?php }
    if($login_fabrica <> 20){
?>
<TR>
    <TD class="titulo">ENDEREÇO</TD>
    <TD class="titulo">BAIRRO</TD>
    <TD class="titulo">CEP</TD>
    <TD class="titulo" colspan='2'><?echo (in_array($login_fabrica, array(52,183))) ? 'PONTO DE REFERÊNCIA' : 'CPF';   ?></TD>
</TR>
<TR>
    <TD class="conteudo"><? echo $consumidor_endereco . " " . $consumidor_numero . " " . $consumidor_complemento ?></TD>
    <TD class="conteudo"><? echo $consumidor_bairro ?></TD>
    <TD class="conteudo"><? echo $consumidor_cep ?></TD>
    <TD class="conteudo" colspan='2'><? echo (in_array($login_fabrica, array(52,183))) ? $consumidor_referencia : $consumidor_cpf; ?></TD>
</TR>
</TABLE>

<?php
}
if($login_fabrica == 20){
     $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND campos_adicionais notnull";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){
        $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

        $motivo_ordem = $campos_adicionais["motivo_ordem"];

    }
}

if (in_array($login_fabrica,array(74, 120,201))) {
?>
    <TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD class="titulo">TELEFONE CELULAR</TD>
            <TD class="titulo">TELEFONE COMERCIAL</TD>
        </TR>
        <TR>
            <TD class="conteudo"><? echo $consumidor_celular ?></TD>
            <TD class="conteudo"><? echo $consumidor_fonecom ?></TD>
        </TR>
    </TABLE>
<?php
}
?>

<? if($login_fabrica == 122){ ?>
        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <TR>
                <TD class="titulo">CPD DO CLIENTE</TD>
                <TD class="titulo">CONTATO</TD>
            </TR>
            <TR>
                <TD class="conteudo"><? echo $obs_adicionais['consumidor_cpd'] ?></TD>
                <TD class="conteudo"><? echo $obs_adicionais['consumidor_contato'] ?></TD>
            </TR>
        </TABLE>
<? } ?>

<? if($login_fabrica == 35){ ?>
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
    <TD class="titulo" colspan="5">Informações sobre a Revenda</TD>
</TR>
<TR>
    <TD class="titulo">CNPJ</TD>
    <TD class="titulo">NOME</TD>
    <TD class="titulo">NF N.</TD>
    <TD class="titulo">DATA NF</TD>
</TR>
<TR>
    <TD class="conteudo"><? echo $revenda_cnpj ?></TD>
    <TD class="conteudo"><? echo $revenda_nome ?></TD>
    <TD class="conteudo"><? echo $nota_fiscal ?></TD>
    <TD class="conteudo"><? echo $data_nf ?></TD>
</TR>
</TABLE>
<? }
if ($login_fabrica != 20 AND !$defeitoReclamadoCadastroDefeitoReclamadoCliente) { ?>
    <TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
    <TR>
        <TD class="titulo">DEFEITO APRESENTADO PELO CLIENTE</TD>
        <?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                <TD class="titulo">BOX / PRATELEIRA</TD>
            <?php } ?>
        </TR>
    <TR>
        <TD class="conteudo"><?= strtoupper($defeito_reclamado) ?><?= ($defeito_reclamado_descricao && !in_array($login_fabrica, array(50,158))) ? " - ".$defeito_reclamado_descricao : ""; ?></TD>
        <?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                <TD class="conteudo"><? echo $box_prateleira; ?></TD>
        <?php } ?>
    </TR>
    </TABLE>
<? } ?>

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
        <TD class="conteudo"><?=$defeito_reclamado?></TD>
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
<?if(in_array($login_fabrica,array(87))){
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
                $peca_itens .=  "<th class='titulo'>COMPONENTE</th>";
                $peca_itens .=  "<th class='titulo'>QTD</th>";
                $peca_itens .=  "<th class='titulo'>&nbsp;CAUSA FALHA</th>";
                //$peca_itens .=  "<th class='titulo'>ITEM CAUSADOR</th>";
                $peca_itens .=  "<th class='titulo'>SERVIÇO</th>";
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
                    //$peca_itens .=  "<td class='conteudo'>{$referencia_causadora} - {$descricao_causadora}</td>";
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
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
    <TD class="titulo">APARÊNCIA GERAL DO PRODUTO</TD>
    <TD class="titulo">ACESSÓRIOS DEIXADOS PELO CLIENTE</TD>
</TR>
<TR>
    <TD class="conteudo"><? echo $aparencia_produto ?></TD>
    <TD class="conteudo"><? echo $acessorios ?></TD>
</TR>
</TABLE>
<?
}
if($login_fabrica != 124){
?>
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
    <TD class="titulo">ATENDIMENTO</TD>

    <? if ($login_fabrica == 20 && in_array($tipo_atendimento, array(13,66))) { ?>
        <TD class="titulo">MOTIVO ORDEM</TD>
    <? }
    if ($login_fabrica == 19) {
        if(strlen($tipo_os)>0){
            $sqll = "SELECT descricao from tbl_tipo_os where tipo_os=$tipo_os";
            $ress = pg_exec($con,$sqll);
            $tipo_os_descricao = pg_result($ress,0,0);
        } ?>
        <TD class="titulo">MOTIVO</TD>
    <? }
    if(!in_array($login_fabrica, array(20,161))){ ?>
        <TD class="titulo">NOME DO TÉCNICO</TD>
    <? }
    if (in_array($login_fabrica, [144]) && $posto_interno) { ?>
        <td class="titulo"><?= traduz("Código Rastreio") ?></td>
    <?php
    } ?>
</TR>
<TR>
    <TD class="conteudo"><? echo  $tipo_atendimento . "-" . $nome_atendimento ?></TD>
<?      if($login_fabrica==19){ ?>
        <TD class="conteudo"><? echo "$tipo_os_descricao";?></TD>
<?}?>
    <?php if($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){?>
        <TD class="conteudo"><? echo $motivo_ordem ?></TD>
    <?php } if(!in_array($login_fabrica, array(20,161))){ ?>

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

<?
}

//HD-3200578
if($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){
    $obs_motivo_ordem = array();
    if($motivo_ordem == 'PROCON (XLR)'){
        $obs_motivo_ordem[] = 'Protocolo:';
        $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['protocolo']);
    }
    if($motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
        $obs_motivo_ordem[] = 'CI ou Solicitante:';
        $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['ci_solicitante']);
    }

    if($motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
        $obs_motivo_ordem[] = "Descrição Peças:";
        if(strlen(trim($campos_adicionais['descricao_peca_1'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['descricao_peca_1']);
        }
        if(strlen(trim($campos_adicionais['descricao_peca_2'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['descricao_peca_2']);
        }
        if(strlen(trim($campos_adicionais['descricao_peca_3'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['descricao_peca_3']);
        }
    }

    if($motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){
        if(strlen(trim($campos_adicionais['codigo_peca_1'])) > 0 OR strlen(trim(utf8_decode($campos_adicionais['codigo_peca_2']))) > 0 OR strlen(trim($campos_adicionais['codigo_peca_3'])) > 0){
            $obs_motivo_ordem[] .= 'Código Peças:';
        }
        if(strlen(trim($campos_adicionais['codigo_peca_1'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['codigo_peca_1']);
        }
        if(strlen(trim($campos_adicionais['codigo_peca_2'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['codigo_peca_2']);
        }
        if(strlen(trim($campos_adicionais['codigo_peca_3'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['codigo_peca_3']);
        }

        if(strlen(trim($campos_adicionais['numero_pedido_1'])) > 0 OR strlen(trim($campos_adicionais['numero_pedido_2'])) > 0 OR strlen(trim($campos_adicionais['numero_pedido_3'])) > 0){
            $obs_motivo_ordem[] .= 'Número Pedidos:';
        }
        if(strlen(trim($campos_adicionais['numero_pedido_1'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['numero_pedido_1']);
        }
        if(strlen(trim($campos_adicionais['numero_pedido_2'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['numero_pedido_2']);
        }
        if(strlen(trim($campos_adicionais['numero_pedido_3'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['numero_pedido_3']);
        }
    }

    if($motivo_ordem == "Linha de Medicao (XSD)"){
        $obs_motivo_ordem[] .= 'Linha de Medição(XSD):';
        $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['linha_medicao']);
    }
    if($motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
        $obs_motivo_ordem[] .= 'Pedido não fornecido - Valor Mínimo(XSS):';
        $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['pedido_nao_fornecido']);
    }

    if($motivo_ordem == 'Contato SAC (XLR)'){
        $obs_motivo_ordem[] .= 'N° do Chamado:';
        $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['contato_sac']);
    }

    if($motivo_ordem == 'Bloqueio financeiro (XSS)' OR $motivo_ordem == 'Ameaca de Procon (XLR)' OR $motivo_ordem == 'Defeito reincidente (XQR)'){
        $obs_motivo_ordem[] .= "Detalhes:";
        $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['detalhe']);
    }
?>
<table class='borda' width="600" border="0" cellspacing="0" cellpadding="0">
    <tr><td class='titulo'>OBSERVAÇÃO MOTIVO ORDEM</td></tr>
    <tr><td class='conteudo'><?php echo implode('<br/>', $obs_motivo_ordem); ?></td></tr>
</table>
<?php
}


if(in_array($login_fabrica, array(117,123,124,127,128,134,136))) { ?>

    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
                <TD class="titulo" colspan='2'>OS DE CORTESIA</TD>
            </TR>
            <TR>
                <TD class="conteudo" colspan='2'><? echo $cortesia;?></TD>
        </TR>
    </TABLE>

    <? if(!in_array($login_fabrica,array(123,124,126,127,128,134,136))) { ?>

    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
                <TD class="titulo" colspan='2'>GARANTIA ESTENDIDA</TD>
            </TR>
            <TR>
                <TD class="conteudo" colspan='2'><? echo ($certificado_garantia) ? "Sim" : "Não";?></TD>
        </TR>
    </TABLE>
<? }
}
if(!in_array($login_fabrica,array(124,126))) {
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

    if (!in_array($login_fabrica, array(150,20,175))) { ?>
        <TR>
            <TD class="titulo" colspan='<?=$colspan?>'>DESLOCAMENTO</TD>
            <?php
            if ($login_fabrica == 171) {
            ?>
            <TD class="titulo" colspan='1'>QUANTIDADE DE VISITAS</TD>
            <?php
            }
            if (in_array($login_fabrica, array(141,144)) && in_array($os_tipo_posto, array(452,453))) {
            ?>
                <td class='titulo' >REMANUFATURA</td>
            <?php
            }

            if (in_array($login_fabrica, array(142,156,169,170))) { ?>
                <TD class="titulo" >VISITA</TD>
            <? } ?>
        </TR>
        <TR>
            <TD class="conteudo" colspan='<?=$colspan?>'><?= number_format($qtde_km,2,',','.');?>&nbsp;KM</TD>
            <?php
            if ($login_fabrica == 171) {
            ?>
            <TD class="conteudo" colspan='1'><?=$qtde_diaria;?></TD>
            <?php
            }
            if (in_array($login_fabrica, array(141,144)) && in_array($os_tipo_posto, array(452,453))) { ?>
                <td class='conteudo'><?=($os_remanufatura == "t") ? "Sim" : "Não"?></td>
            <? }

            if (in_array($login_fabrica, array(142,156,169,170))) { ?>
                <TD class="conteudo"><?=$qtde_diaria?>&nbsp;</TD>
            <? } ?>
        </TR>
    <? }

    if (in_array($login_fabrica, array(169,170)) && strlen($motivo_visita) > 0) { ?>
        <tr>
            <td class="titulo" colspan="3">MOTIVO DA(S) VISITA(S)</td>
        </tr>
        <tr>
            <td class="conteudo" colspan="3"><?= $motivo_visita; ?></td>
        </tr>
    <? }

     if($login_fabrica == 178 AND $troca_garantia == "t"){
?>

        <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
            <TR>
                <TD class='titulo'><?php echo traduz("opcoes.de.troca.do.produto");?></td>
            </TR>
    <?php
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
                    if(count($produtosTroca) > 0){

                        foreach($produtosTroca AS $key => $value){
                    
                            echo "<tr><td class='conteudo'>{$value['referencia']} - {$value['descricao']}</td></tr>";
                        }
                    }
    ?>
        </TABLE>
<?php
    }
    if($login_fabrica == 42){ ?>
    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD class='titulo'>Diagnóstico, Peças usadas e Resolução do Problema. Técnico:</td>
        </TR>
        <?php
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
        ?>
        <TR>
            <TD class='conteudo'>
            <?php
            if (empty($os_auditoria)) {

                echo $topo_peca.$peca_dynacom;
            } else {
                echo $msgAviso;
            }
            ?>
            </TD>
        </TR>

    </TABLE>
<?php } ?>

<?php
}

if ((!empty($observacao) || !empty($obs_abertura)) && !in_array($login_fabrica, array(35, 50))) { ?>
    <table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <?php if (!empty($observacao)) { ?>
        <tr>
            <td class="titulo">OBSERVAÇÃO DO SAC AO POSTO AUTORIZADO</td>

        </tr>
        <tr>
            <td class="conteudo"><?= $observacao; ?></td>

        </tr>
        <?php } if (!empty($obs_abertura) && !in_array($login_fabrica, [169,170])) { ?>
        <tr>
            <td class="titulo"><?=($login_fabrica == 171) ? 'COMENTÁRIO SOBRE A VISITA' : 'OBSERVAÇÃO'; ?></td>
        </tr>
        <tr>
            <td class="conteudo"><?= $obs_abertura; ?></td>
        </tr>
	<?php } if (!empty($array_causa_defeito) && in_array($login_fabrica, [169, 170])) { ?>
        <tr>
            <td class="titulo">Motivo 2ª Solicitação</td>
        </tr>
        <tr>
            <td class="conteudo"><?= $array_causa_defeito; ?></td>
        </tr>
        <?php } ?>
    </table>
<? }

if (in_array($login_fabrica, array(158))) { ?>
    <table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td class="titulo">INÍCIO ATENDIMENTO</td>
            <td class="titulo">TÉRMINO ATENDIMENTO</td>
            <td class="titulo">AMPERAGEM</td>
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
<? }

if($login_fabrica == 137){ ?>
<TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">

    <TR>
        <TD class="titulo">CFOP</TD>
        <TD class="titulo">VALOR UNITÁRIO</TD>
        <TD class="titulo">TOTAL DA NOTA</TD>
    </TR>
    <TR>
        <TD class="conteudo"><? echo $cfop ?></TD>
        <TD class="conteudo"><? echo $valor_unitario ?></TD>
        <TD class="conteudo"><? echo $valor_nota ?></TD>
    </TR>

</TABLE>

<?php
}
if ($login_fabrica==59) {
            $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND campos_adicionais notnull";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) > 0){
                $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

                foreach ($campos_adicionais as $key => $value) {
                    $$key = $value;
                }
                if (strlen($origem)>0) {
                    $origem = ($origem == "recepcao") ? "Recepção" : "Sedex Reverso";
                }
                ?>
                <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
                    <TR>
                        <TD class="titulo" width="80">ORIGEM&nbsp;</TD>
                        <TD class="conteudo">&nbsp;<?=$origem?> </TD>
                    </TR>
                </TABLE>
                <?php
    }
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
            <TD class="titulo">CÓD. RASTREIO&nbsp;</TD>
        </TR>
        <TR>
            <TD class="conteudo">&nbsp;<?=$enviar_os?></TD>
            <TD class="conteudo">&nbsp;<?=$codigo_rastreio?> </TD>
        </TR>
    </TABLE><?php
}

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
    <table width="600px" border="0" cellspacing="1" cellpadding="0" class='borda'>
        <tr>
            <td class='titulo' colspan="4">&nbsp;Laudo Técnico</td>
        </tr>
        <tr>
            <td class='titulo' colspan="2">NOME DA ASSITÊNCIA TÉCNICA AUTORIZADA</td>
            <td class='titulo'>Nº DA ASSITÊNCIA</td>
            <td class='titulo'>DATA</td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_posto_nome']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_posto_numero']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_data_abertura']?></td>
        </tr>
        <tr>
            <td class='titulo' colspan="2">NOME DO CLIENTE</td>
            <td class='titulo' colspan="2">ENDEREÇO</td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_cliente_nome']?></td>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_cliente_endereco']?></td>
        </tr>
        <tr>
            <td class='titulo'>CIDADE</td>
            <td class='titulo'>UF</td>
            <td class='titulo'>BAIRRO</td>
            <td class='titulo'>TEL.</td>
        </tr>
        <tr>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_cidade']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_estago']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_bairro']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_telefone']?></td>
        </tr>
        <tr>
            <td class='titulo' colspan="2">LOCAL DA COMPRA</td>
            <td class='titulo'>NOTA FISCAL</td>
            <td class='titulo'>DATA</td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_local_compra']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_nota_fiscal']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_nota_fiscal_data']?></td>
        </tr>
        <tr>
            <td class='titulo'>INSTALADO EM</td>
            <td class='titulo' colspan="3">NOME DA INSTALADORA</td>
        </tr>
        <tr>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_data_instalado']?></td>
            <td class='conteudo' colspan="3"><?=$laudo_tecnico['laudo_tecnico_instaladora_nome']?></td>
        </tr>
        <tr>
            <td class='titulo'>ÁGUA UTILIZADA</td>
            <td class='titulo'>PRESSURIZADOR</td>
            <td class='titulo'>TENSÃO</td>
            <td class='titulo'>TIPO DE GÁS</td>
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
        <tr>
            <td class='titulo'>PRESSÃO DE GÁS DINÂMICA</td>
            <td class='titulo'>PRESSÃO DE GÁS ESTÁTICA</td>
            <td class='titulo'>PRESSÃO DE ÁGUA DINÂMICA</td>
            <td class='titulo'>PRESSÃO DE ÁGUA ESTÁTICA</td>
        </tr><tr>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_gas_dinamica']?> (consumo máx.)</td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_gas_estatica']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_agua_dinamica']?> (consumo máx.)</td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_agua_estatica']?></td>
        </tr>
    </table>
    <table width="600px" border="0" cellspacing="1" cellpadding="0" class='borda' style="table-layout: fixed;" >
        <tr>
            <td class='titulo'>DIÂMETRO DO DUTO</td>
            <td class='titulo'>COMPRIMENTO TOTAL DO DUTO</td>
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
            <td class='titulo'>CARACTERÍSTICAS DO LOCAL DE INSTALAÇÃO</td>
            <td class='titulo'>INSTALAÇÃO DE ACORDO COM O NBR 13.103</td>
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
        <tr>
            <td class='titulo' colspan="2">PROBLEMA DIAGNOSTICADO</td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_problema_diagnosticado']?></td>
        </tr>
        <tr>
            <td class='titulo' colspan="2">PROVIDÊNCIAS ADOTADAS</td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_providencias_adotadas']?></td>
        </tr>
        <? if (!in_array($login_fabrica,array(124))) { ?>
            <tr>
                <td class='titulo'  colspan="2">NOME DO TÉCNICO</td>
            </tr>
            <tr>
                <td class='conteudo'  colspan="2"><?=$laudo_tecnico['laudo_tecnico_tecnico_nome']?></td>
            </tr>
        <? } ?>
    </table>
<? }

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

if($login_fabrica == 52){
    // HD-896985
    $sqlTecnico = "SELECT tecnico FROM tbl_os_extra WHERE os = {$os};";
    $resTecnico = pg_query($con,$sqlTecnico);
    $tecnicoData = pg_fetch_result ($resTecnico,0,tecnico);
    $explodeTecnico = explode("|", $tecnicoData);
    $tecnicoNome = $explodeTecnico[0];
    $tecnicoRg = $explodeTecnico[1]; ?>
    <table width="600">
        <tr>
            <td class="titulo">RG DO TÉCNICO</td>
            <td class="conteudo" colspan='1'>&nbsp;<?php echo $tecnicoRg;?></td>
            <td class="titulo">NOME DO TÉCNICO</td>
            <td class="conteudo" colspan='1'>&nbsp;<?php echo $tecnicoNome;?></td>
        </tr>
    </table>
<? }
if((in_array($login_fabrica, array(59,95)) && strlen($finalizada) > 0) || in_array($login_fabrica, [96,148])) { ?>
    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD class="titulo">DATA DE FECHAMENTO</TD>
            <TD class="titulo">DATA DE CONSERTO</TD>
            <?php
            if (!in_array($login_fabrica, [148])) { ?>
                <TD class="titulo"><?=$temaMaiusculo?></TD>
            <?php
            } else {
                echo '<TD class="titulo">DATA FALHA</TD>';
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
<? }

if ($login_fabrica == 163) {
    $sql_ta = "SELECT descricao FROM tbL_tipo_atendimento WHERE tipo_atendimento = (SELECT tipo_atendimento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) AND fabrica = $login_fabrica";
    $res_ta = pg_query($con, $sql_ta);

    if(pg_num_rows($res_ta) > 0){
        $desc_tipo_atendimento = pg_fetch_result($res_ta, 0, 'descricao');
    }
}

$sql_servico = "
    SELECT
        tbl_os_item.peca,
        tbl_os_item.qtde,
        tbl_os_item.custo_peca,
        tbl_os_item.preco,
        tbl_peca.referencia_fabrica,
        tbl_peca.referencia,
        tbl_os_item.porcentagem_garantia,
        tbl_os_item.peca_serie,
        tbl_os_item.peca_serie_trocada,
        tbl_os_item.os_por_defeito,
        tbl_peca.descricao,
        tbl_defeito.descricao AS defeito_descricao,
        tbl_servico_realizado.descricao AS servico_realizado,
        tbl_os_extra.regulagem_peso_padrao,
        tbl_os_extra.qtde_horas,
        tbl_os_item.parametros_adicionais
    FROM tbl_os
    LEFT JOIN tbl_os_extra USING(os)
    JOIN tbl_os_produto USING(os)
    JOIN tbl_os_item USING(os_produto)
    JOIN tbl_peca USING(peca)
    LEFT JOIN tbl_defeito ON tbl_os_item.defeito = tbl_defeito.defeito
    LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
    WHERE tbl_os.os = {$os}
    AND tbl_os.fabrica = {$login_fabrica};
";

$res_servico = pg_query($con,$sql_servico);
?>
    <table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <?php if (in_array($login_fabrica, [139])) { ?>
            <tr colspan='4'>
                <td colspan='4' class="titulo text-left">
                    <strong>Peças Utilizadas:</strong>
                </td>
            </tr>
        <?php } ?>
        <tr>
            <?php
            if($login_fabrica == 171){
                echo '<td class="titulo">REFERÊNCIA FÁBRICA</td>';
            }
            ?>
            <td class="titulo">REFERÊNCIA</td>
            <td class="titulo">DESCRIÇÃO</td>

            <?php if ($login_fabrica == 177){ ?>
            <td class='titulo' style='text-align: center;'>LOTE</td>
            <td class='titulo' style='text-align: center;'>LOTE NOVA PEÇA</td>
            <?php } ?>

            <?php if ($login_fabrica == 175){ ?>
            <td class='titulo' style='text-align: center;'>SÉRIE</td>
            <td class='titulo' style='text-align: center;'>QTDE DISPAROS</td>
            <td class='titulo' style='text-align: center;'>COMPONENTE RAIZ</td>
            <?php } ?>

            <? if ($login_fabrica != 148) { ?>
                <td class="titulo">QTDE</td>
            <? }

            if (in_array($login_fabrica, array(163)) && $desc_tipo_atendimento == 'Fora de Garantia') {
                $valor_total_pecas = 0; ?>
                <td class='titulo' style='text-align: center;'>VALOR UNITÁRIO</td>
                <td class='titulo' style='text-align: center;'>VALOR TOTAL</td>
            <? }
            if(in_array($login_fabrica, [167, 203]) && $nome_atendimento == 'Orçamento') { ?>
                <td class='titulo' style='text-align: center;'>VALOR UNITÁRIO</td>
                <td class='titulo' style='text-align: center;'>VALOR TOTAL</td>
            <? }
            if (in_array($login_fabrica, array(120,201,169,170,183))) { ?>
                <td class="titulo">DEFEITO</td>
            <? }
            if ($login_fabrica == 96) { ?>
                <td class="titulo">FREE OF CHARGE</td>
            <? } else { ?>
                <?php if (!in_array($login_fabrica, array(167,203))){ ?>
                    <td class="titulo">SERVIÇO</td>
                <?php } ?>

            <?  if ($login_fabrica == 171) {
                    echo '<td class="titulo">PRESSÃO DA ÁGUA (MCA)</td>';
                    echo '<td class="titulo">TEMPO DE USO (MÊS)</td>';
                }

                if (in_array($login_fabrica, [148])) {
                    echo "<td class='titulo'>NOTA FISCAL ESTOQUE</td>";
                }

            }

            if ($login_fabrica == 148) { ?>
                <td class='titulo' style='text-align: center;'>QTDE</td>
                <td class='titulo' style='text-align: center;'>VALOR UNITÁRIO</td>
                <td class='titulo' style='text-align: center;'>VALOR TOTAL</td>                
            <? } ?>
        </tr>
        <?
	if (pg_num_rows($res_servico) > 0) {
	for($x = 0; $x < pg_num_rows($res_servico); $x++) {

            $_referencia = pg_fetch_result($res_servico,$x,referencia);
            $_referencia_fabrica = pg_fetch_result($res_servico,$x,referencia_fabrica);
            $_descricao = pg_fetch_result($res_servico,$x,descricao);
            $_custo_peca = pg_fetch_result($res_servico,$x,custo_peca);
            $_preco = pg_fetch_result($res_servico,$x,preco);
            $_descricao_defeito = pg_fetch_result($res_servico,$x,defeito_descricao);
            $_servico_realizado = pg_fetch_result($res_servico,$x,servico_realizado);
            $_qtde = pg_fetch_result($res_servico,$x,qtde);
            $_regulagem_peso_padrao = pg_fetch_result($res_servico, $x, 'regulagem_peso_padrao');
            $_qtde_horas = pg_fetch_result($res_servico, $x, 'qtde_horas'); 

            $parametrosAdicionais = json_decode(pg_fetch_result($res_servico, $x, "parametros_adicionais"), true);

            if ($login_fabrica == 175){
                $qtde_disparos_peca = pg_fetch_result($res_servico, $x, "porcentagem_garantia");
                $componente_raiz = pg_fetch_result($res_servico, $x, "os_por_defeito");
            }

            if ($login_fabrica == 177){
                $peca_serie         = pg_fetch_result($res_servico, $x, "peca_serie");
                $peca_serie_trocada = pg_fetch_result($res_servico, $x, "peca_serie_trocada");
            }

            $numero_serie_peca = pg_fetch_result($res_servico, $x, "peca_serie");
        ?>
            <tr>
                <?php
                if($login_fabrica == 171){
                    echo "<td class='conteudo'>$_referencia_fabrica</td>";
                }
                ?>
                <td class='conteudo'><?= $_referencia; ?></td>
                <td class='conteudo'><?= $_descricao; ?></td>

                <?php if ($login_fabrica == 177){ ?>
                    <td class='conteudo'><?=$peca_serie_trocada?></td>
                    <td class='conteudo'><?=$peca_serie?></td>
                <?php } ?>

                <?php if ($login_fabrica == 175){ ?>
                <td class='conteudo'><?=$numero_serie_peca?></td>
                <td class='conteudo'><?=$qtde_disparos_peca?></td>
                <td class='conteudo'><?=(($componente_raiz == "t")? "SIM":"NÃO")?></td>
                <?php } ?>
                <? if ($login_fabrica != 148) { ?>
                    <td class='conteudo'><?= $_qtde; ?></td>
                <? }
                if (in_array($login_fabrica, array(163)) && $desc_tipo_atendimento == 'Fora de Garantia') {
                    $qtde_peca          = (strlen($_qtde) == 0) ? 0 : $_qtde;
                    $aux_valor_total    = (strlen($_custo_peca) == 0) ? 0 : $_custo_peca;
                    $valor_total_pecas  = $valor_total_pecas + $aux_valor_total;
                    $valor_total        = (strlen($_custo_peca) == 0) ? 0 : number_format($_custo_peca, 2);
                    $valor_unitario     = number_format($valor_total / $qtde_peca, 2); ?>
                    <td class='conteudo' style='text-align: center;'><?= $valor_unitario; ?></td>
                    <td class='conteudo' style='text-align: center;'><?= $valor_total; ?></td>
                <? }
                if(in_array($login_fabrica, [167, 203]) && $nome_atendimento == 'Orçamento'){
                    $valor_unitario     = (strlen($_preco) == 0) ? 0 : number_format($_preco, 2);
                    $preco_total_aux    = number_format($valor_unitario*$_qtde, 2);
                    $valor_total_pecas += $preco_total_aux; ?>
                    <td style='text-align: center;' class='conteudo'><?= $valor_unitario; ?></td>
                    <td style='text-align: center;' class='conteudo'><?= $preco_total_aux; ?></td>
                <? }
                if ($login_fabrica == 120 or $login_fabrica == 201) { ?>
                    <td class='conteudo'><?= $_descricao_defeito; ?></td>
                <? }
                if (in_array($login_fabrica, array(169,170,183))) { ?>
                    <td class="conteudo"><?= $_descricao_defeito; ?></td>
                <? } ?>
                    <?php if (!in_array($login_fabrica, array(167,203))){ ?>
                        <td class='conteudo'><?= $_servico_realizado; ?></td>
                    <?php } ?>
                <? if ($login_fabrica == 148) {
                    $qtde_peca = (strlen($_qtde) == 0) ? 0 : $_qtde;
                    $valor_total = (strlen($_custo_peca) == 0) ? 0 : number_format($_custo_peca, 2);
                    $valor_unitario = number_format($valor_total / $qtde_peca, 2);?>

                    <td><?= $parametrosAdicionais["nf_estoque_fabrica"] ?></td>
                    <td style='text-align: center;'><?= $qtde_peca; ?></td>
                    <td style='text-align: center;'><?= $valor_unitario; ?></td>
                    <td style='text-align: center;'><?= $valor_total; ?></td>
                <? }
                if ($login_fabrica == 171) {
                    echo "<td class='conteudo' style='text-align: center;'>{$_regulagem_peso_padrao}</td>";
                    echo "<td class='conteudo' style='text-align: center;'>{$_qtde_horas}</td>";
                }
                ?>
            </tr>
        <? }
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
                    <td class='conteudo' style='text-align: left;' colspan='2' >VALOR TOTAL PEÇAS</td>
                    <td class='conteudo' style='text-align: center;'>".number_format($valor_total_pecas, 2)."</td>
                    <td class='conteudo' style='text-align: center;'></td>
                </tr>
                <tr>
                    <td class='conteudo' style='text-align: center;' colspan='2' ></td>
                    <td class='conteudo' style='text-align: left;' colspan='2' >VALOR ADICIONAL</td>
                    <td class='conteudo' style='text-align: center;'>".number_format($valor_adicional, 2)."</td>
                    <td class='conteudo' style='text-align: center;'></td>
                </tr>
                <tr>
                    <td class='conteudo' style='text-align: center;' colspan='2' ></td>
                    <td class='conteudo' style='text-align: left;' colspan='2' >VALOR DE DESCONTO</td>
                    <td class='conteudo' style='text-align: center;'>".number_format($desconto, 2)."</td>
                    <td class='conteudo' style='text-align: center;'></td>
                </tr>
                <tr>
                    <td class='conteudo' style='text-align: center;' colspan='2' ></td>
                    <td class='conteudo' style='text-align: left;' colspan='2' >VALOR TOTAL GERAL</td>
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
                    <td class='titulo' colspan='4'><strong>Valor Mão de Obra</strong></td>
                    <td style='text-align: center;' class='titulo'><strong>{$campo_adicional}</strong></td>
                    <td colspan='1' class='titulo'></td>
                </tr>";
                echo "<tr>
                    <td class='titulo' colspan='4'><strong>Valor Total</strong></td>
                    <td style='text-align: center;' class='titulo'><strong>{$total_geral}</strong></td>
                    <td colspan='1' class='titulo'></td>
                </tr>";
            }

            if(count($valores_adicionais) > 0){
                echo"<tr>
                    <td style='text-align: center;' class='titulo' colspan='6'>CUSTOS ADICIONAIS DA OS</td>
                </tr>";

                echo "<tr>
                        <td class='titulo' colspan='3'>SERVIÇO</td>
                        <td class='titulo' colspan='3'>VALOR</td>
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

if (isset($_GET['tipo']) && $_GET['tipo'] == 'detalhado') {
?>
    <table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td class="titulo" colspan="5">INTERAÇÕES</td>
        </tr>
        <tr>
            <td class="titulo">Nº</td>
            <td class="titulo">Data</td>
            <td class="titulo">Mensagem</td>
            <td class="titulo">Admin</td>
        </tr>
        <tr>
        <?php
        $sqlInteracoes = "SELECT
                            tbl_os_interacao.os_interacao AS id,
                            (CASE WHEN tbl_os_interacao.admin IS NULL THEN
                                'Posto Autorizado'
                            ELSE
                                tbl_admin.nome_completo
                            END) AS admin,
                            TO_CHAR(tbl_os_interacao.data, 'DD/MM/YYYY HH24:MI') AS data,
                            TO_CHAR(tbl_os_interacao.data_contato, 'DD/MM/YYYY') AS data_contato,
                            tbl_os_interacao.comentario AS mensagem,
                            tbl_os_interacao.interno,
                            tbl_os_interacao.posto,
                            tbl_os_interacao.sms,
                            tbl_os_interacao.exigir_resposta,
                            tbl_os_interacao.atendido,
                            TO_CHAR(tbl_os_interacao.confirmacao_leitura, 'DD/MM/YYYY HH24:MI') AS confirmacao_leitura
                        FROM tbl_os_interacao
                            LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin AND tbl_admin.fabrica = {$login_fabrica}
                        WHERE tbl_os_interacao.fabrica = {$login_fabrica}
                          AND tbl_os_interacao.os = {$os}
                        ORDER BY tbl_os_interacao.data DESC";
        $resInteracoes = pg_query($con, $sqlInteracoes);
        if (pg_num_rows($resInteracoes) > 0) {
            $i = pg_num_rows($resInteracoes);

            while ($interacao = pg_fetch_object($resInteracoes)) {
            ?>

                <tr <?=($interacao->interno == "t" && !empty($interacao->admin)) ? "class='error'" : ""?> >
                    <td class='conteudo'>
                        <?php
                        echo $i;

                        if (in_array("interacao_email", $inputs_interacao) && $interacao->exigir_resposta == "t" && !in_array("interacao_email_consumidor", $inputs_interacao)) {
                            echo "&nbsp;<i class='icon-envelope pull-right' ></i>";
                        }

                        if (in_array("interacao_transferir", $inputs_interacao) && preg_match("/^transferido para o admin/", strtolower($interacao->mensagem))) {
                            echo "&nbsp;<i class='icon-retweet pull-right' ></i>";
                        }

                        if (in_array("interacao_sms_consumidor", $inputs_interacao)) {
                            if ($interacao->sms == "t") {
                                echo "&nbsp;<i class='glyphicon icon-phone pull-right' ></i>";
                            }
                        }

                        if (in_array("interacao_email_consumidor", $inputs_interacao)) {
                            if ($interacao->interno == "t" && $interacao->exigir_resposta == "t" && preg_match("/^enviou email para o consumidor/", strtolower($interacao->mensagem))) {
                                echo "&nbsp;<i class='icon-envelope pull-right' ></i><i class='icon-user' ></i>";
                            } else if ($interacao->exigir_resposta == "t") {
                                echo "&nbsp;<i class='icon-envelope pull-right' ></i>";
                            }
                        }

                        if (in_array("interacao_atendido", $inputs_interacao)) {
                            if ($interacao->atendido == "t") {
                                echo "&nbsp;<i class='icon-ok pull-right' ></i>";
                            }
                        }
                        ?>
                    </td>
                    <td class="tac conteudo"><?=$interacao->data?></td>
                    <?php
                    if (in_array("interacao_data_contato", $inputs_interacao)) {
                    ?>
                        <td class="tac" ><?=$interacao->data_contato?></td>
                    <?php
                    }
                    ?>
                    <td class='conteudo'><?=$interacao->mensagem?></td>
                    <td class='conteudo'><?=$interacao->admin?></td>
                </tr>

                <?php
                $i--;
            }
        }
        ?>
        </tr>
    </table>
<?php
}

if (in_array($login_fabrica, array(2,59)) && strlen($data_fechamento) > 0) {
?>
    <TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
    <? echo "<TR>";
     if(strlen($defeito_constatado) > 0 && $login_fabrica != 59) {
            echo "<TD class='titulo'>$temaMaiusculo</TD>";
            echo "<TD class='titulo'>SOLUÇÃO</TD>";
            echo "<TD class='titulo'>DT FECHA. OS</TD>";
    }
    echo "</TR>";
    echo "<TR>";
    if(strlen($defeito_constatado) > 0) {
            if($login_fabrica == 59){ //HD 337865
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
                if(pg_num_rows($res_dc) > 0){
                    for($x=0;$x<pg_num_rows($res_dc);$x++){
                        $dc_defeito_constatado = pg_fetch_result($res_dc,$x,defeito_constatado);
                        $dc_solucao = pg_fetch_result($res_dc,$x,solucao);

                        $dc_descricao = pg_fetch_result($res_dc,$x,descricao);
                        $dc_codigo    = pg_fetch_result($res_dc,$x,codigo);
                        $dc_solucao_descricao = pg_fetch_result($res_dc,$x,solucao_descricao);

                        echo "<tr>";

                        echo "<td class='titulo' height='15'>$temaMaiusculo</td>";
                        echo "<td class='conteudo'>&nbsp; $dc_descricao</td>";
                        echo "<td class='titulo' height='15'>SOLUÇÃO</td>";
                        echo "<td class='conteudo'>&nbsp; $dc_solucao_descricao</td>";

                        echo "</tr>";

                    }
                    echo "<TD class='titulo'>DT FECHA. OS</TD>";
                    echo "<TD class='conteudo'>$data_fechamento</TD>";
                }
            }
            else {
                echo "<TD class='conteudo'>$defeito_constatado</TD>";
                echo "<TD class='conteudo'>$solucao</TD>";
                echo "<TD class='conteudo'>$data_fechamento</TD>";
            }
    } ?>
    </TR>
    </TABLE>
<?
}
?>

<?php if($login_fabrica == 3){ ?>
<div class='texto_termos'>

    <p>
        1) Declaro para os devidos fins que o equipamento/acessório(s) referente(s) a esta ORDEM DE SERVIÇO é(são)
        usado(s) e de minha propriedade, e estará(ão) nesta assistência técnica para o reparo, portanto assumo toda
        a responsabilidade quanto a sua procedência.
    </p>

    <p>
        2) Desde já autorizo a assistência técnica a entregar o(s) objeto(s) aqui identificado(s) a quem apresentar esta
        ORDEM DE SERVIÇO (1ª. Via) e também a cobrar o valor de R$1,00 (Hum Real), por dia, a título de “guarda” do
        equipamento, caso não venha retirá-los no prazo de 10 dias após o comunicado que o reparo foi efetuado, ou da não
        aprovação do orçamento, se houver.
    </p>

    <p>
        Declaro e concordo com os dados acima:
    </p>

    <p>
        De acordo:___/___/____ Visto do cliente:_________________________________________<br /><br />
        Retirada:___/___/_____  Quem:__________________________ Documento:_____________
    </p>

</div>

<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
    <TD>
    <h2>Em,
        <?

            echo $posto_cidade .", ". $data_abertura;
        ?>
    </h2>
    </TD>
</TR>
<TR>
    <TD><h2><? echo $consumidor_nome ?> - Assinatura: _________________________________________________</h2></TD>
</TR>
</TABLE>
<?php }

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

    if(in_array($login_fabrica, [167, 203]) && $nome_atendimento == 'Orçamento'){
?>
    <TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
    <TR>
        <TD style='text-align: justify;'>
            <span class='texto'>
                <br/>
                O orçamento será encaminhado via e-mail após analise técnica, o mesmo deverá ser respondido com aprovação ou reprovação do conserto.
                <br/><br/>
                Não aceitamos cheque, pagamento somente dinheiro ou cartão.
                <br/><br/>
                Na reprovação do conserto, o cliente irá adequar o produto nas mesmas condições em que a empresa o recebeu.
                <br/><br/>
                Na reprovação ou conserto do equipamento, o cliente concederá um prazo de 24horas para adequar o produto.
                <br/><br/>
                Na hipótese do produto não ser retirado na data mencionada, o mesmo será depositado em juízo para destinação legal.
            </span>
            <br/><br/>
            <span class='texto'>
                <strong>Garantia</strong>
                <br/>
                O produto descrito conta com a garantia legal de 90 dias, conforme determinado pelo CÓDIGO DE DEFESA DO CONSUMIDOR, contada a partir de sua retirada.
                <br/><br/>
                A garantia perderá sua validade se houver violação do lacre colocado pela empresa no produto; se for utilizado suprimentos não originais; ligado a uma rede elétrica imprópria ou sujeita a flutuações; instalado de maneira inadequada; caso sofra danos causados por acidentes ou agentes da natureza tais como quedas, batidas, enchentes, descargas elétricas, raios, conectada em voltagem errada , etc...; ou algum tipo de manutenção por pessoas não autorizada Brother.
                <br/><br/>
                <strong>Retirada do produto</strong>
                <br/>
                Solicitamos que a retirada do equipamento seja dentro de 60 dias para que não haja taxa de armazenagem. (taxa de R$5,00 por dia).
                <br/><br/>
                Precisamos que o cliente tenha em mãos a Ordem de Serviço de entrada para retirada do equipamento.
            </span>
        </TD>
    </TR>
    </TABLE>
    <br/><br/>
<?php
    }

    if(!in_array($login_fabrica, [167, 203])){
?>

<?php

if(!in_array($login_fabrica, array(42,59,128,161,167,203))){ ?>
<TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
    <TR>
        <TD>
            <div id="container" style='width:600px;'>
                <div id="page" style='border:0px;'>
                    <?php if(in_array($login_fabrica, [139])) { ?> 
                        <h2>Problema Identificado e Corrigido: </h2>
                    <?php } else { ?>
                        <h2>Diagnóstico, Peças usadas e Resolução do Problema: </h2>
                    <?php } ?>
                        <?php

                            if(in_array($login_fabrica,array(20,115,116,117,123,124,125,126,127,134,136))){

                                echo "<center>".$peca_dynacom."</center>";
                            }
                         ?>
                        Técnico:
                </div>
            </div>
        </TD>
    </TR>

</TABLE>

<?php } 
if(!in_array($login_fabrica,[161])){
?>
<table style="width:650px;margin:10px 0 0 -10px;">
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
<?php } ?>
<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
    <TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<?php
// FRICON
if($login_fabrica == 52 ){
    ?>
    <br />
    <TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
        <tr>
            <td colspan="4" align="right">
                <strong style="font: 12px arial; font-weight: bold;">Via da Posto</strong>
            </td>
        </tr>
        <TR class="conteudo">
            <TD>
                <IMG SRC="<? echo ($img_contrato); ?>" height="40" ALT="ORDEM DE SERVIÇO">
            </TD>
            <td align="center">
                <strong>POSTO AUTORIZADO</strong> <br />
                <?php
                    echo ($nome_posto != "") ? $nome_posto."<br />" : "";
                    echo ($endereco_posto != "") ? $endereco_posto : "";
                    echo ($numero_posto != "") ? $numero_posto.", " : "";
                    echo ($bairro_posto != "") ? $bairro_posto.", " : "";
                    echo ($cep_posto != "") ? " <br /> CEP: ".$cep_posto." " : "";
                    echo ($cidade_posto != "") ? $cidade_posto." - " : "";
                    echo ($estado_posto != "") ? $estado_posto : "";
                ?>
            </td>
            <td align="center" class="borda" style="padding: 5px;">
                <strong>DATA EMISSÃO</strong> <br />
                <?=date("d/m/Y");?>
            </td>
            <td align="center" class="borda" style="padding: 5px;">
                <strong>NÚMERO OS</strong> <br />
                <?=$os;?>
            </td>
        </TR>
    </table>
    <?php
} ?>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">

<TR>
    <TD class="titulo" colspan="5">Informações sobre a Ordem de Serviço</TD>
</TR>
<TR>
    <TD class="titulo">OS FABR.</TD>
    <?php
    $colspanDataAbertura = 2;

    if ($login_fabrica == 148) {
        $colspanDataAbertura = 4;
    }

    if (in_array($login_fabrica, array(143,167,175,177,203)) ) {
        $colspanDataAbertura = 3;
    }
    ?>
    <TD class="titulo" colspan='<?=$colspanDataAbertura?>'><?=$data_osMaiuscula?> </TD>
    <!-- <TD <?=($login_fabrica==1 or $login_fabrica==96)?'colspan="2" ':''?>class="titulo">REF.</TD>//-->
</TR>
<TR>
    <TD class="conteudo">
    <?
    echo implode(' - ', array_filter(array($sua_os, $consumidor_revenda)));
    /* if (strlen($consumidor_revenda) > 0){
            echo $sua_os ." - ". $consumidor_revenda;
        }else if (strlen($consumidor_revenda) == 0){
                echo $sua_os;
        }*/
    ?>
    </TD>
    <TD class="conteudo" colspan='<?=$colspanDataAbertura?>'><? echo $data_abertura ?></TD>
    <!-- <TD class="conteudo"><? echo $referencia ?></TD>//-->
</TR>


<?php if(in_array($login_fabrica, array(87))){?>
    <TR>
        <TD class="titulo" colspan="3">
            <table width='100%' border="0" cellspacing="0" cellpadding="0" class='Tabela' align='center'>
                <tr>
                    <td class="titulo" height='15' nowrap>TIPO ATENDIMENTO</td>
                    <td class="titulo" height='15' nowrap>HORAS TRABALHADAS</td>
                    <td class="titulo" height='15' nowrap>HORAS TÉCNICAS</td>
                </tr>
                <tr>
                    <td class="conteudo"  height='15' >&nbsp;
                    <?php
                        if(intval($tipo_atendimento) > 0){
                            $sql_tipo_atendimento = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                            $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);

                            echo  pg_fetch_result($res_tipo_atendimento,0,'descricao');
                        }
                    ?>
                    </td>
                    <td class="conteudo" height='15'>&nbsp;<? echo $hora_tecnica; ?></td>
                    <td class="conteudo" height='15'>&nbsp;<? echo $qtde_horas; ?></td>
                </tr>
            </table>
        </TD>
    </TR>
<?php }?>
<TR>
    <?if ($login_fabrica == 96) { ?>
        <TD class="titulo">MODELO</TD>
    <? }else{ ?>
        <TD class="titulo">REF.</TD>
    <? }?>
        <TD class="titulo">DESCRIÇÃO</TD>
        <?php if ($login_fabrica != 171) {
        ?>
        <TD class="titulo" <?=($login_fabrica == 148) ? "colspan='3'" : ""?> >SÉRIE</TD>
        <?php } ?>

    <?php if($login_fabrica == 177){  ?>
        <TD class="titulo">LOTE</TD>
    <?php } ?>
    <?php if ($login_fabrica == 175){ ?>
        <TD class="titulo">QTDE DISPAROS</TD>
    <?php } ?>
    <?php
    if(in_array($login_fabrica, [167, 203])){
    ?>
    <td class='titulo'>CONTADOR</td>
    <?php
    }
    if (in_array($login_fabrica, array(143))) {
    ?>
        <TD class="titulo">HORIMETRO</TD>
    <?php
    }

    if ($login_fabrica == 1) { ?>
    <TD class="titulo">CÓD. FABRICAÇÃO</TD>
    <? } ?>
    <?if ($login_fabrica == 19) {?>
        <TD class="titulo">QTDE</TD><?php

    }?>
</TR>

<TR>
    <?if ($login_fabrica == 96) { ?>
        <TD class="conteudo"><? echo $modelo ?></TD>
    <? }else{ ?>
        <TD class="conteudo"><? echo $referencia ?> <? echo ($login_fabrica == 171) ? " / " . $produto_referencia_fabrica : ""; ?></TD>
    <? }?>
    <TD class="conteudo"><? echo $descricao ?></TD>
    <?php if ($login_fabrica != 171) { ?>
    <TD class="conteudo" <?=($login_fabrica == 148) ? "colspan='3'" : ""?> ><? echo $serie ?></TD>
    <?php } ?>

    <?php if ($login_fabrica == 177){ ?>
    <td class="conteudo"><?=$codigo_fabricacao?></td>
    <?php } ?>
    <?php if ($login_fabrica == 175){ ?>
    <TD class="conteudo"><?=$qtde_disparos?></TD>
    <?php } ?>
    <?php
    if(in_array($login_fabrica, [167, 203])){
    ?>
        <td class='conteudo'><?=$contador?></td>
    <?php
    }
    if (in_array($login_fabrica, array(143))) {
    ?>
        <TD class="conteudo"><?=$rg_produto?></TD>
    <?php
    }
    if ($login_fabrica == 1) { ?>
    <TD class="conteudo"><? echo $codigo_fabricacao ?></TD>
    <? } ?>
    <?if ($login_fabrica == 19) {?>
        <TD class="conteudo"><? echo $qtde_produtos ?></TD><?php
    }?>
</TR>

<? if (in_array($login_fabrica, array(169,170))) {
    if (number_format($qtde_km,2,',','.') > 0) { ?>
        <tr>
            <td class="titulo">PRODUTO RETIRADO PARA A OFICINA</td>
            <td class="titulo" colspan="2">EMPRÉSTIMO DE PRODUTO PARA O CONSUMIDOR</td>
        </tr>
        <tr>
            <td class="conteudo"><?= ($recolhimento == "t") ? "Sim" : "Não"; ?></td>
            <td class="conteudo" colspan="2"><?= ($produto_emprestimo == "t") ? "Sim" : "Não"; ?></td>
        </tr>
    <? }
}
if ($login_fabrica == 148) { ?>
    <tr>
        <td class="titulo" >N. DE SÉRIE MOTOR</td>
        <td class="titulo" >N. DE SÉRIE TRANSMISSÃO</td>
        <td class="titulo" >HORIMETRO</td>
        <td class="titulo" >REVISÃO</td>
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
        <td class="titulo" colspan="3">PATRIMÔNIO</td>
    </tr>
    <tr>
        <td class="conteudo" colspan="3"><?= $serie_justificativa; ?></td>
    </tr>
<? }
if($login_fabrica == 86 and $serie_justificativa != 'null'){ // HD 328591?>
        <tr>
            <td colspan='5' class='titulo'>JUSTIFICATIVA NÚMERO SÉRIE</td>
        </tr>
        <tr>
            <td colspan='5' class="conteudo"><? echo $serie_justificativa ?></td>
        </tr>
    <? } ?>

    <?
     if($login_fabrica == 86 and $serie_justificativa != 'null'){ // HD 328591?>
        <tr>
            <td colspan='5' class='titulo'>JUSTIFICATIVA NÚMERO SÉRIE</td>
        </tr>
        <tr>
            <td colspan='5' class="conteudo"><? echo $serie_justificativa ?></td>
        </tr>
    <? } ?>

     <?php
        if(in_array($login_fabrica, array(138)) && $coun_os_produto > 1){
    ?>

            <TR>
                <TD class="titulo">REFERÊNCIA SUBCONJUNTO</TD>
                <TD class="titulo">DESCRIÇÃO SUBCONJUNTO</TD>
                <TD class="titulo">SÉRIE SUBCONJUNTO</TD>
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
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
    <TD class="titulo"><? echo ($login_fabrica == 122) ? "NOME DO CLIENTE" : "NOME DO CONSUMIDOR"; ?></TD>
    <?php if($login_fabrica <> 20){ ?>
        <TD class="titulo">CIDADE</TD>
        <TD class="titulo">ESTADO</TD>
    <?php } ?>
    <TD class="titulo">FONE</TD>
    <?php if(!in_array($login_fabrica, array(74,120,201,52,20))) { ?>
        <td class='titulo'>CELULAR</td>
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
    <TD class="conteudo">
    <? echo $consumidor_fone ?>
    <?php
        if($login_fabrica == 52){
            $sql = "SELECT tbl_hd_chamado_extra.celular FROM tbl_hd_chamado_extra INNER JOIN tbl_os ON tbl_os.os = $os AND tbl_os.hd_chamado = tbl_hd_chamado_extra.hd_chamado";
            $res = pg_query($con, $sql);
            if(pg_num_rows($res) > 0){
                $consumidor_celular = pg_fetch_result($res, 0, 'celular');
                if(strlen($consumidor_celular) > 0){
                    $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = '$os'";
                    $res = pg_query($con, $sql);
                    if(pg_num_rows($res) > 0){
                        $campos_adicionais2 = pg_fetch_result($res, 0, 'campos_adicionais');
                        $dados = json_decode($campos_adicionais2);
                        $operadora_celular = $dados->operadora;
                        echo "<br />".$consumidor_celular." / Operadora: ".$operadora_celular;
                    }else{
                        echo "";
                    }
                }
            }
        }

        if (!in_array($login_fabrica, array(74,120,201,52,20))) {
            echo "<td>$consumidor_celular</td>\n";
        }
    ?>
    </TD>
    <?php if(in_array($login_fabrica, [167, 203])){ ?>
        <td class='conteudo'><?=$contato_consumidor?></td>
    <?php } ?>
</TR>

<?php

    if($login_fabrica == 52){

        ?>

        <TR>
            <TD class="titulo">ENDEREÇO</TD>
            <TD class="titulo">BAIRRO</TD>
            <TD class="titulo">CEP</TD>
            <TD class="titulo"><?echo ($login_fabrica == 52) ? 'PONTO DE REFERÊNCIA' : 'CPF';   ?></TD>
        </TR>
        <TR>
            <TD class="conteudo"><? echo $consumidor_endereco . " " . $consumidor_numero . " " . $consumidor_complemento ?></TD>
            <TD class="conteudo"><? echo $consumidor_bairro ?></TD>
            <TD class="conteudo"><? echo $consumidor_cep ?></TD>
            <TD class="conteudo"><? echo ($login_fabrica == 52) ? $consumidor_referencia : $consumidor_cpf; ?></TD>
        </TR>

        <?php

    }

?>

</TABLE>
<?php

//HD-3200578
if($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){
    $obs_motivo_ordem = array();
    if($motivo_ordem == 'PROCON (XLR)'){
        $obs_motivo_ordem[] = 'Protocolo:';
        $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['protocolo']);
    }
    if($motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
        $obs_motivo_ordem[] = 'CI ou Solicitante:';
        $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['ci_solicitante']);
    }

    if($motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
        $obs_motivo_ordem[] = "Descrição Peças:";
        if(strlen(trim($campos_adicionais['descricao_peca_1'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['descricao_peca_1']);
        }
        if(strlen(trim($campos_adicionais['descricao_peca_2'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['descricao_peca_2']);
        }
        if(strlen(trim($campos_adicionais['descricao_peca_3'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['descricao_peca_3']);
        }
    }

    if($motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){
        if(strlen(trim($campos_adicionais['codigo_peca_1'])) > 0 OR strlen(trim(utf8_decode($campos_adicionais['codigo_peca_2']))) > 0 OR strlen(trim($campos_adicionais['codigo_peca_3'])) > 0){
            $obs_motivo_ordem[] .= 'Código Peças:';
        }
        if(strlen(trim($campos_adicionais['codigo_peca_1'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['codigo_peca_1']);
        }
        if(strlen(trim($campos_adicionais['codigo_peca_2'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['codigo_peca_2']);
        }
        if(strlen(trim($campos_adicionais['codigo_peca_3'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['codigo_peca_3']);
        }

        if(strlen(trim($campos_adicionais['numero_pedido_1'])) > 0 OR strlen(trim($campos_adicionais['numero_pedido_2'])) > 0 OR strlen(trim($campos_adicionais['numero_pedido_3'])) > 0){
            $obs_motivo_ordem[] .= 'Número Pedidos:';
        }
        if(strlen(trim($campos_adicionais['numero_pedido_1'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['numero_pedido_1']);
        }
        if(strlen(trim($campos_adicionais['numero_pedido_2'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['numero_pedido_2']);
        }
        if(strlen(trim($campos_adicionais['numero_pedido_3'])) > 0){
            $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['numero_pedido_3']);
        }
    }

    if($motivo_ordem == "Linha de Medicao (XSD)"){
        $obs_motivo_ordem[] .= 'Linha de Medição(XSD):';
        $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['linha_medicao']);
    }
    if($motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
        $obs_motivo_ordem[] .= 'Pedido não fornecido - Valor Mínimo(XSS):';
        $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['pedido_nao_fornecido']);
    }

    if($motivo_ordem == 'Contato SAC (XLR)'){
        $obs_motivo_ordem[] .= 'N° do Chamado:';
        $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['contato_sac']);
    }

    if($motivo_ordem == 'Bloqueio financeiro (XSS)' OR $motivo_ordem == 'Ameaca de Procon (XLR)' OR $motivo_ordem == 'Defeito reincidente (XQR)'){
        $obs_motivo_ordem[] .= "Detalhes:";
        $obs_motivo_ordem[] .= utf8_decode($campos_adicionais['detalhe']);
    }
?>
<table class='borda' width="600" border="0" cellspacing="0" cellpadding="0">
    <tr><td class='titulo'>OBSERVAÇÃO MOTIVO ORDEM</td></tr>
    <tr><td class='conteudo'><?php echo implode('<br/>', $obs_motivo_ordem); ?></td></tr>
</table>
<?php
}

if (in_array($login_fabrica,array(74,120,201))) { ?>
    <table class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td class="titulo">TELEFONE CELULAR</td>
            <td class="titulo">TELEFONE COMERCIAL</td>
        </tr>
        <tr>
            <td class="conteudo"><? echo $consumidor_celular ?></td>
            <td class="conteudo"><? echo $consumidor_fonecom ?></td>
        </tr>
    </table>
<?php
}
if ($login_fabrica != 20 AND !$defeitoReclamadoCadastroDefeitoReclamadoCliente) { ?>
    <TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
        <TR>
            <? if (!in_array($login_fabrica, array(122,143))) {
                if ($login_fabrica == 145) { ?>
                    <TD class="titulo" colspan="5">Informações sobre a Revenda/Construtora</TD>
                <? } else { ?>
                    <TD class="titulo" colspan="5">Informações sobre a Revenda</TD>
                <? }
            } else { ?>
                <TD class="titulo" colspan="5">Informações sobre a nota fiscal</TD>
            <? } ?>
        </TR>
        <TR>
            <? if(!in_array($login_fabrica, array(20,122,143))) { ?>
                <TD class="titulo">CNPJ</TD>
                <TD class="titulo">NOME</TD>
            <? } ?>
            <TD class="titulo">NF N.</TD>
            <TD class="titulo">DATA NF</TD>
            <?php if ($login_fabrica == 174) echo '<TD class="titulo">VALOR NF</TD>'; ?>
        </TR>
        <TR>
            <? if(!in_array($login_fabrica, array(20,122,143))) { ?>
                <TD class="conteudo"><?= ($login_fabrica == 15) ? substr($revenda_cnpj,0,8) : $revenda_cnpj; ?></TD>
                <TD class="conteudo"><?= $revenda_nome ?></TD>
            <? } ?>
            <TD class="conteudo"><?= $nota_fiscal ?></TD>
            <TD class="conteudo"><?= $data_nf ?></TD>
            <?php if ($login_fabrica == 174) { /*HD - 6015269*/
                if (empty($os_campos_adicionais["valor_nf"])) {
                    $aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
                    $aux_res = pg_query($con, $aux_sql);
                    $aux_arr = json_decode(pg_fetch_result($aux_res, 0, 'campos_adicionais'), true);

                    if (empty($aux_arr["valor_nf"])) {
                        $valor_nf = "";
                    } else {
                        $valor_nf = $aux_arr["valor_nf"];
                    }
                } else {
                    $valor_nf = $os_campos_adicionais["valor_nf"];
                } ?> 
                <TD class="conteudo"><?=$valor_nf;?></TD>
            <?php } ?>
        </TR>
    </TABLE>

    <? if (in_array($login_fabrica, array(169,170))) { ?>
        <TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
            <tr>
                <td class="titulo">CONTATO</td>
            </tr>
            <tr>
                <td class="conteudo"><?= $revenda_contato; ?></td>
            </tr>
        </TABLE>
    <? } ?>

    <TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
        <TR>
            <TD class="titulo">DEFEITO APRESENTADO PELO CLIENTE</TD>
            <?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                <TD class="titulo">BOX / PRATELEIRA</TD>
            <?php } ?>
        </TR>
        <TR>
            <TD class="conteudo"><?php echo strtoupper($defeito_reclamado); echo ($defeito_reclamado_descricao && !in_array($login_fabrica, array(50,158))) ? " - ".$defeito_reclamado_descricao : ""; ?></TD>
            <?php if ( !in_array($login_fabrica, array(7,11,15,172)) && !empty($box_prateleira)) { ?>
                <TD class="conteudo"><? echo $box_prateleira; ?></TD>
            <?php } ?>
        </TR>
    </TABLE>
<? } ?>

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
        <TD class="conteudo"><?=$defeito_reclamado?></TD>
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

<?php
if(!empty($peca_itens) AND in_array($login_fabrica, array(87))) {
    echo $peca_itens;
}

if (in_array($login_fabrica, array(30,161))) { ?>
    <table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <?php if (!empty($observacao)) { ?>
        <tr>
            <td class="titulo">OBSERVAÇÃO DO SAC AO POSTO AUTORIZADO</td>

        </tr>
        <tr>
            <td class="conteudo"><?= $observacao; ?></td>

        </tr>
        <?php } if (!empty($obs_abertura)) { ?>
        <tr>
            <td class="titulo">OBSERVAÇÃO</td>
        </tr>
        <tr>
            <td class="conteudo"><?= $obs_abertura; ?></td>
        </tr>
        <?php } ?>
    </table>
<? }

if ($login_fabrica != 20) { ?>
    <TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
    <TR>
        <TD class="titulo">APARÊNCIA GERAL DO PRODUTO</TD>
        <TD class="titulo">ACESSÓRIOS DEIXADOS PELO CLIENTE</TD>
    </TR>
    <TR>
        <TD class="conteudo"><?= $aparencia_produto ?></TD>
        <TD class="conteudo"><?= $acessorios ?></TD>
    </TR>
    </TABLE>
<? }

if (in_array($login_fabrica, array(52,114))) { ?>
    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD class="titulo" colspan='2'>DESLOCAMENTO</TD>
        </TR>
        <TR>
            <TD class="conteudo" colspan='2'><? echo number_format($qtde_km,2,',','.');?>&nbsp;KM</TD>
        </TR>
    </TABLE>
    <br />
<? }

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
if (in_array($login_fabrica,array(127))) {
    $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND campos_adicionais notnull";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){
        $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

        foreach ($campos_adicionais as $key => $value) {
            $$key = $value;
        }

        $enviar_os = ($enviar_os == "t") ? "Sim" : "Não";
    } ?>

    <TABLE width="600" border="0" cellspacing="0" cellpadding="0" class='borda'>
         <TR>
            <TD class="titulo">Envio p/ DL</TD>
            <TD class="titulo">CÓD. RASTREIO&nbsp;</TD>
        </TR>
        <TR>
            <TD class="conteudo">&nbsp;<?=$enviar_os?></TD>
            <TD class="conteudo">&nbsp;<?=$codigo_rastreio?> </TD>
        </TR>
    </TABLE>
<? }

if ($login_fabrica == 19) {
    if (strlen($tipo_os) > 0) {
        $sqll = "SELECT descricao FROM tbl_tipo_os WHERE tipo_os = {$tipo_os};";
        $ress = pg_exec($con,$sqll);
        $tipo_os_descricao = pg_result($ress,0,0);
    } ?>
    <TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
        <TR>
            <TD class="titulo">ATENDIMENTO</TD>
            <? if ($login_fabrica == 19) { ?>
                <TD class="titulo">MOTIVO</TD>
            <? } ?>
            <TD class="titulo">NOME DO TÉCNICO</TD>
        </TR>
        <TR>
            <TD class="conteudo"><?= $tipo_atendimento.$nome_atendimento ?></TD>
            <? if ($login_fabrica == 19) { ?>
                <TD class="conteudo"><?= $tipo_os_descricao;?></TD>
            <? } ?>
            <TD class="conteudo"><?= $tecnico_nome; ?></TD>
        </TR>
    </TABLE>
<? }

if (in_array($login_fabrica, array(2,59)) && strlen($data_fechamento) > 0) { ?>
    <TABLE class="borda" width="600px" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <? if(strlen($defeito_constatado) > 0 && $login_fabrica != 59) { ?>
                <TD class='titulo'><?= $temaMaiusculo; ?></TD>
                <TD class='titulo'>SOLUÇÃO</TD>
                <TD class='titulo'>DT FECHA. OS</TD>
            <? } ?>
        </TR>
        <TR>
            <? if (strlen($defeito_constatado) > 0) {
                if ($login_fabrica == 59) { //HD 337865
                    $sql_cons = "
                        SELECT
                            tbl_defeito_constatado.defeito_constatado,
                            tbl_defeito_constatado.descricao,
                            tbl_defeito_constatado.codigo,
                            tbl_solucao.solucao,
                            tbl_solucao.descricao AS solucao_descricao
                        FROM tbl_os_defeito_reclamado_constatado
                        JOIN tbl_defeito_constatado USING(defeito_constatado)
                        LEFT JOIN tbl_solucao USING(solucao)
                        WHERE os = {$os};
                    ";

                    $res_dc = pg_query($con, $sql_cons);
                    if (pg_num_rows($res_dc) > 0) {
                        for($x = 0; $x < pg_num_rows($res_dc); $x++) {
                            $dc_defeito_constatado = pg_fetch_result($res_dc,$x,defeito_constatado);
                            $dc_solucao = pg_fetch_result($res_dc,$x,solucao);
                            $dc_descricao = pg_fetch_result($res_dc,$x,descricao);
                            $dc_codigo    = pg_fetch_result($res_dc,$x,codigo);
                            $dc_solucao_descricao = pg_fetch_result($res_dc,$x,solucao_descricao); ?>

                            <tr>
                                <td class='titulo' height='15'><?= $temaMaiusculo; ?></td>
                                <td class='conteudo'>&nbsp;<?= $dc_descricao; ?></td>
                                <td class='titulo' height='15'>SOLUÇÃO</td>
                                <td class='conteudo'>&nbsp;<?= $dc_solucao_descricao; ?></td>
                            </tr>
                        <? } ?>
                        <TD class='titulo'>DT FECHA. OS</TD>
                        <TD class='conteudo'><?= $data_fechamento; ?></TD>
                    <? }
                } else { ?>
                    <TD class='conteudo'><?= $defeito_constatado; ?></TD>
                    <TD class='conteudo'><?= $solucao; ?></TD>
                    <TD class='conteudo'><?= $data_fechamento; ?></TD>
                <? }
            } ?>
        </TR>
    </TABLE>
<? }

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
    <table width="600px" border="0" cellspacing="1" cellpadding="0" class='borda'>
        <tr>
            <td class='titulo' colspan="4">&nbsp;Laudo Técnico</td>
        </tr>
        <tr>
            <td class='titulo' colspan="2">NOME DA ASSITÊNCIA TÉCNICA AUTORIZADA</td>
            <td class='titulo'>Nº DA ASSITÊNCIA</td>
            <td class='titulo'>DATA</td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_posto_nome']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_posto_numero']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_data_abertura']?></td>
        </tr>
        <tr>
            <td class='titulo' colspan="2">NOME DO CLIENTE</td>
            <td class='titulo' colspan="2">ENDEREÇO</td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_cliente_nome']?></td>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_cliente_endereco']?></td>
        </tr>
        <tr>
            <td class='titulo'>CIDADE</td>
            <td class='titulo'>UF</td>
            <td class='titulo'>BAIRRO</td>
            <td class='titulo'>TEL.</td>
        </tr>
        <tr>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_cidade']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_estago']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_bairro']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_cliente_telefone']?></td>
        </tr>
        <tr>
            <td class='titulo' colspan="2">LOCAL DA COMPRA</td>
            <td class='titulo'>NOTA FISCAL</td>
            <td class='titulo'>DATA</td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_local_compra']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_nota_fiscal']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_nota_fiscal_data']?></td>
        </tr>
        <tr>
            <td class='titulo'>INSTALADO EM</td>
            <td class='titulo' colspan="3">NOME DA INSTALADORA</td>
        </tr>
        <tr>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_data_instalado']?></td>
            <td class='conteudo' colspan="3"><?=$laudo_tecnico['laudo_tecnico_instaladora_nome']?></td>
        </tr>
        <tr>
            <td class='titulo'>ÁGUA UTILIZADA</td>
            <td class='titulo'>PRESSURIZADOR</td>
            <td class='titulo'>TENSÃO</td>
            <td class='titulo'>TIPO DE GÁS</td>
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
        <tr>
            <td class='titulo'>PRESSÃO DE GÁS DINÂMICA</td>
            <td class='titulo'>PRESSÃO DE GÁS ESTÁTICA</td>
            <td class='titulo'>PRESSÃO DE ÁGUA DINÂMICA</td>
            <td class='titulo'>PRESSÃO DE ÁGUA ESTÁTICA</td>
        </tr><tr>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_gas_dinamica']?> (consumo máx.)</td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_gas_estatica']?></td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_agua_dinamica']?> (consumo máx.)</td>
            <td class='conteudo'><?=$laudo_tecnico['laudo_tecnico_pressao_agua_estatica']?></td>
        </tr>
    </table>
    <table width="600px" border="0" cellspacing="1" cellpadding="0" class='borda' style="table-layout: fixed;" >
        <tr>
            <td class='titulo'>DIÂMETRO DO DUTO</td>
            <td class='titulo'>COMPRIMENTO TOTAL DO DUTO</td>
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
            <td class='titulo'>CARACTERÍSTICAS DO LOCAL DE INSTALAÇÃO</td>
            <td class='titulo'>INSTALAÇÃO DE ACORDO COM O NBR 13.103</td>
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
        <tr>
            <td class='titulo' colspan="2">PROBLEMA DIAGNOSTICADO</td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_problema_diagnosticado']?></td>
        </tr>
        <tr>
            <td class='titulo' colspan="2">PROVIDÊNCIAS ADOTADAS</td>
        </tr>
        <tr>
            <td class='conteudo' colspan="2"><?=$laudo_tecnico['laudo_tecnico_providencias_adotadas']?></td>
        </tr>
<?
            if($login_fabrica != 124){
?>
        <tr>
            <td class='titulo'  colspan="2">NOME DO TÉCNICO</td>
        </tr>
        <tr>
            <td class='conteudo'  colspan="2"><?=$laudo_tecnico['laudo_tecnico_tecnico_nome']?></td>
        </tr>
<?
            }
?>
    </table>
<?php
}

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
if ($login_fabrica == 19){
    $sql = "SELECT tbl_laudo_tecnico_os.*
            FROM tbl_laudo_tecnico_os
            WHERE os = $os
            ORDER BY ordem;";
    $res = pg_exec($con,$sql);
    if(pg_numrows($res) > 0){
?>
        <BR>
        <TABLE width="600px" border="0" cellspacing="1" cellpadding="0" class='borda'>
        <TR>
        <TD colspan="9" class='titulo'>&nbsp;LAUDO TÉCNICO</TD>
<?
        echo "<tr>";
        echo "<td class='titulo' style='width: 30%'><CENTER>";
        if ($login_fabrica==19) echo "QUESTÃO";
        else                    echo "TÍTULO";
        echo "</CENTER></td>";
        echo "<td class='titulo' style='width: 10%'><CENTER>AFIRMATIVA</CENTER></td>";
        echo "<td class='titulo' style='width: 60%'><CENTER>OBSERVAÇÃO</CENTER></td>";
        echo "</tr>";

        for($i=0;$i<pg_numrows($res);$i++){
            $laudo       = pg_result($res,$i,laudo_tecnico_os);
            $titulo      = pg_result($res,$i,titulo);
            $afirmativa  = pg_result($res,$i,afirmativa);
            $observacao  = pg_result($res,$i,observacao);

            echo "<tr>";
            echo "<td class='borda' align='left' style='width: 30%'>&nbsp;$titulo</td>";
            if(strlen($afirmativa) > 0){
                echo "<td class='borda' style='width: 10%'><CENTER>"; if($afirmativa == 't'){ echo "Sim</CENTER></td>";} else { echo "Não</CENTER></td>";}
            }else{
                echo "<td class='borda' style='width: 10%'>&nbsp;</td>";
            }
            if(strlen($observacao) > 0){
                echo "<td class='borda' style='width: 60%'><CENTER>$observacao</CENTER></td>";
            }else{
                echo "<td class='borda' style='width: 60%'>&nbsp;</td>";
            }
            echo "</tr>";
        }
?>
</TR>
</TABLE>
<BR>
<?
    }
}

if ((in_array($login_fabrica, array(59,95)) && strlen($finalizada) > 0) || $login_fabrica == 96) { ?>
    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD class="titulo">DATA DE FECHAMENTO</TD>
            <TD class="titulo">DATA DE CONSERTO</TD>
            <TD class="titulo"><?=$temaMaiusculo?></TD>
        </TR>
        <TR>
            <TD class="conteudo"><? echo convertDataBR(substr($finalizada,0,10)); ?></TD>
            <TD class="conteudo"><? echo convertDataBR(substr($data_conserto,0,10)); ?></TD>
            <TD class="conteudo"><? echo $defeito_constatado; ?></TD>
        </TR>
    </TABLE>
<? }
if ($login_fabrica == 163) {
    $sql_ta = "SELECT descricao FROM tbL_tipo_atendimento WHERE tipo_atendimento = (SELECT tipo_atendimento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) AND fabrica = $login_fabrica";
    $res_ta = pg_query($con, $sql_ta);

    if(pg_num_rows($res_ta) > 0){
        $desc_tipo_atendimento = pg_fetch_result($res_ta, 0, 'descricao');
    }
}

$sql_servico = "
    SELECT
        tbl_os_item.peca,
        tbl_os_item.qtde,
        tbl_os_item.custo_peca,
        tbl_os_item.preco,
        tbl_peca.referencia_fabrica,
        tbl_peca.referencia,
        tbl_peca.descricao,
        tbl_os_item.porcentagem_garantia,
        tbl_os_item.peca_serie,
        tbl_os_item.peca_serie_trocada,
        tbl_os_item.os_por_defeito,
        tbl_defeito.descricao AS defeito_descricao,
        tbl_servico_realizado.descricao AS servico_realizado,
        tbl_os_extra.regulagem_peso_padrao,
        tbl_os_extra.qtde_horas
    FROM tbl_os
    LEFT JOIN tbl_os_extra USING(os)
    JOIN tbl_os_produto USING(os)
    JOIN tbl_os_item USING(os_produto)
    JOIN tbl_peca USING(peca)
    LEFT JOIN tbl_defeito USING(defeito)
    JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
    WHERE tbl_os.os = {$os}
    AND tbl_os.fabrica = {$login_fabrica};
";

$res_servico = pg_query($con,$sql_servico);

if (pg_num_rows($res_servico) > 0) { ?>
    <table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <?php if (in_array($login_fabrica, [139])) { ?>
            <tr colspan='4'>
                <td colspan='4' class="titulo text-left">
                    <strong>Peças Utilizadas:</strong>
                </td>
            </tr>
        <?php } ?>
        <tr>
            <?php
            if($login_fabrica == 171){
                echo "<td class='titulo'>REFERÊNCIA FÁBRICA</td>";
            }
            ?>
            <td class="titulo">REFERÊNCIA</td>
            <td class="titulo">DESCRIÇÃO</td>

            <?php if ($login_fabrica == 177){ ?>
                <td class='titulo' style='text-align: center;'>LOTE</td>
                <td class='titulo' style='text-align: center;'>LOTE NOVA PEÇA</td>
            <?php } ?>

            <?php if ($login_fabrica == 175){ ?>
            <td class='titulo' style='text-align: center;'>SÉRIE</td>
            <td class='titulo' style='text-align: center;'>QTDE DISPAROS</td>
            <td class="titulo" style="text-align: center;">COMPONENTE RAIZ</td>
            <?php } ?>

            <? if ($login_fabrica != 148) { ?>
                <td class="titulo">QTDE</td>
            <? }

            if (in_array($login_fabrica, array(163)) && $desc_tipo_atendimento == 'Fora de Garantia') {
                $valor_total_pecas = 0; ?>
                <td class='titulo' style='text-align: center;'>VALOR UNITÁRIO</td>
                <td class='titulo' style='text-align: center;'>VALOR TOTAL</td>
            <? }
            if(in_array($login_fabrica, [167, 203]) && $nome_atendimento == 'Orçamento') { ?>
                <td class='titulo' style='text-align: center;'>VALOR UNITÁRIO</td>
                <td class='titulo' style='text-align: center;'>VALOR TOTAL</td>
            <? }
            if (in_array($login_fabrica, array(120,201,169,170,183))) { ?>
                <td class="titulo">DEFEITO</td>
            <? }
            if ($login_fabrica == 96) { ?>
                <td class="titulo">FREE OF CHARGE</td>
            <? } else { ?>
                <td class="titulo">SERVIÇO</td>
            <?php
                if ($login_fabrica == 171) {
                    echo '<td class="titulo">PRESSÃO DA ÁGUA (MCA)</td>';
                    echo '<td class="titulo">TEMPO DE USO (MÊS)</td>';
                }
            }
            ?>
            <?php if ($login_fabrica == 148) { ?>
                <td class='titulo' style='text-align: center;'>QTDE</td>
                <td class='titulo' style='text-align: center;'>VALOR UNITÁRIO</td>
                <td class='titulo' style='text-align: center;'>VALOR TOTAL</td>
            <? } ?>
        </tr>
        <?php for($x = 0; $x < pg_num_rows($res_servico); $x++) {

            $_referencia = pg_fetch_result($res_servico,$x,referencia);
            $_referencia_fabrica = pg_fetch_result($res_servico,$x,referencia_fabrica);
            $_descricao = pg_fetch_result($res_servico,$x,descricao);
            $_custo_peca = pg_fetch_result($res_servico,$x,custo_peca);
            $_preco = pg_fetch_result($res_servico,$x,preco);
            $_descricao_defeito = pg_fetch_result($res_servico,$x,defeito_descricao);
            $_servico_realizado = pg_fetch_result($res_servico,$x,servico_realizado);
            $_qtde = pg_fetch_result($res_servico,$x,qtde);
            $_regulagem_peso_padrao = pg_fetch_result($res_servico, $x, 'regulagem_peso_padrao');
            $_qtde_horas = pg_fetch_result($res_servico, $x, 'qtde_horas'); 

            if ($login_fabrica == 177){
                $peca_serie         = pg_fetch_result($res_servico, $x, "peca_serie");
                $peca_serie_trocada = pg_fetch_result($res_servico, $x, "peca_serie_trocada");
            }

            if ($login_fabrica == 175){
                $qtde_disparos_peca = pg_fetch_result($res_servico, $x, "porcentagem_garantia");
                $numero_serie_peca = pg_fetch_result($res_servico, $x, 'peca_serie');
                $componente_raiz = pg_fetch_result($res_servico, $x, 'os_por_defeito');
            }
        ?>
            <tr>
                <?php
                if($login_fabrica == 171){
                    echo "<td class='conteudo'>$_referencia_fabrica</td>";
                }
                ?>
                <td class='conteudo'><?= $_referencia; ?></td>
                <td class='conteudo'><?= $_descricao; ?></td>

                <?php if ($login_fabrica == 177){ ?>
                <td class='conteudo'><?=$peca_serie_trocada;?></td>
                <td class='conteudo'><?=$peca_serie;?></td>
                <?php } ?>

                <?php if ($login_fabrica == 175){ ?>
                <td class='conteudo'><?=$numero_serie_peca?></td>
                <td class='conteudo'><?=$qtde_disparos_peca?></td>
                <td class='conteudo'><?=(($componente_raiz == "t")? "SIM":"NÃO")?></td>
                <?php } ?>

                <? if ($login_fabrica != 148) { ?>
                    <td class='conteudo'><?= $_qtde; ?></td>
                <? }
                if (in_array($login_fabrica, array(163)) && $desc_tipo_atendimento == 'Fora de Garantia') {
                    $qtde_peca          = (strlen($_qtde) == 0) ? 0 : $_qtde;
                    $aux_valor_total    = (strlen($_custo_peca) == 0) ? 0 : $_custo_peca;
                    $valor_total_pecas  = $valor_total_pecas + $aux_valor_total;
                    $valor_total        = (strlen($_custo_peca) == 0) ? 0 : number_format($_custo_peca, 2);
                    $valor_unitario     = number_format($valor_total / $qtde_peca, 2); ?>
                    <td class='conteudo' style='text-align: center;'><?= $valor_unitario; ?></td>
                    <td class='conteudo' style='text-align: center;'><?= $valor_total; ?></td>
                <? }
                if(in_array($login_fabrica, [167, 203]) && $nome_atendimento == 'Orçamento'){
                    $valor_unitario     = (strlen($_preco) == 0) ? 0 : number_format($_preco, 2);
                    $preco_total_aux    = number_format($valor_unitario*$_qtde, 2);
                    $valor_total_pecas += $preco_total_aux; ?>
                    <td style='text-align: center;' class='conteudo'><?= $valor_unitario; ?></td>
                    <td style='text-align: center;' class='conteudo'><?= $preco_total_aux; ?></td>
                <? }
                if ($login_fabrica == 120 or $login_fabrica == 201) { ?>
                    <td class='conteudo'><?= $_descricao_defeito; ?></td>
                <? }
                if (in_array($login_fabrica, array(169,170,183))) { ?>
                    <td class="conteudo"><?= $_descricao_defeito; ?></td>
                <? } ?>
                <td class='conteudo'><?= $_servico_realizado; ?></td>
                <? if ($login_fabrica == 148) {
                    $qtde_peca = (strlen($_qtde) == 0) ? 0 : $_qtde;
                    $valor_total = (strlen($_custo_peca) == 0) ? 0 : number_format($_custo_peca, 2);
                    $valor_unitario = number_format($valor_total / $qtde_peca, 2); ?>

                    <td style='text-align: center;'><?= $qtde_peca; ?></td>
                    <td style='text-align: center;'><?= $valor_unitario; ?></td>
                    <td style='text-align: center;'><?= $valor_total; ?></td>
                <? }
                if ($login_fabrica == 171) {
                    echo "<td class='conteudo' style='text-align: center;'>{$_regulagem_peso_padrao}</td>";
                    echo "<td class='conteudo' style='text-align: center;'>{$_qtde_horas}</td>";
                } ?>
            </tr>
        <? }

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
                    <td class='conteudo' style='text-align: left;' colspan='2' >VALOR TOTAL PEÇAS</td>
                    <td class='conteudo' style='text-align: center;'>".number_format($valor_total_pecas, 2)."</td>
                    <td class='conteudo' style='text-align: center;'></td>
                </tr>
                <tr>
                    <td class='conteudo' style='text-align: center;' colspan='2' ></td>
                    <td class='conteudo' style='text-align: left;' colspan='2' >VALOR ADICIONAL</td>
                    <td class='conteudo' style='text-align: center;'>".number_format($valor_adicional, 2)."</td>
                    <td class='conteudo' style='text-align: center;'></td>
                </tr>
                <tr>
                    <td class='conteudo' style='text-align: center;' colspan='2' ></td>
                    <td class='conteudo' style='text-align: left;' colspan='2' >VALOR DE DESCONTO</td>
                    <td class='conteudo' style='text-align: center;'>".number_format($desconto, 2)."</td>
                    <td class='conteudo' style='text-align: center;'></td>
                </tr>
                <tr>
                    <td class='conteudo' style='text-align: center;' colspan='2' ></td>
                    <td class='conteudo' style='text-align: left;' colspan='2' >VALOR TOTAL GERAL</td>
                    <td class='conteudo' style='text-align: center;'>".number_format($total_geral, 2)."</td>
                    <td class='conteudo' style='text-align: center;'></td>
                </tr>
                ";
            }
        }

        if(in_array($login_fabrica, [167, 203])){
            $sql_adicionais = "SELECT valores_adicionais, campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND valores_adicionais notnull";
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

            $total_geral = $valor_total_pecas2 + $campo_adicional;
            $total_geral = number_format($total_geral, 2, ".", ",");
            if($nome_atendimento == "Orçamento"){
                echo "<tr>
                    <td class='titulo' colspan='4'>Valor Adicional</td>
                    <td style='text-align: center;' class='titulo'>{$campo_adicional}</td>
                    <td colspan='1' class='titulo'></td>
                </tr>";
                echo "<tr>
                    <td class='titulo' colspan='4'>Valor Total</td>
                    <td style='text-align: center;' class='titulo'>{$total_geral}</td>
                    <td colspan='1' class='titulo'></td>
                </tr>";
            }

            if(count($valores_adicionais) > 0){
                echo"<tr>
                    <td style='text-align: center;' class='titulo' colspan='6'>CUSTOS ADICIONAIS DA OS</td>
                </tr>";

                echo "<tr>
                        <td class='titulo' colspan='3'>SERVIÇO</td>
                        <td class='titulo' colspan='3'>VALOR</td>
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

if (isset($_GET['tipo']) && $_GET['tipo'] == 'detalhado') {
?>
    <table class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td class="titulo" colspan="5">INTERAÇÕES</td>
        </tr>
        <tr>
            <td class="titulo">Nº</td>
            <td class="titulo">Data</td>
            <td class="titulo">Mensagem</td>
            <td class="titulo">Admin</td>
        </tr>
        <tr>
        <?php
        $sqlInteracoes = "SELECT
                            tbl_os_interacao.os_interacao AS id,
                            (CASE WHEN tbl_os_interacao.admin IS NULL THEN
                                'Posto Autorizado'
                            ELSE
                                tbl_admin.nome_completo
                            END) AS admin,
                            TO_CHAR(tbl_os_interacao.data, 'DD/MM/YYYY HH24:MI') AS data,
                            TO_CHAR(tbl_os_interacao.data_contato, 'DD/MM/YYYY') AS data_contato,
                            tbl_os_interacao.comentario AS mensagem,
                            tbl_os_interacao.interno,
                            tbl_os_interacao.posto,
                            tbl_os_interacao.sms,
                            tbl_os_interacao.exigir_resposta,
                            tbl_os_interacao.atendido,
                            TO_CHAR(tbl_os_interacao.confirmacao_leitura, 'DD/MM/YYYY HH24:MI') AS confirmacao_leitura
                        FROM tbl_os_interacao
                            LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin AND tbl_admin.fabrica = {$login_fabrica}
                        WHERE tbl_os_interacao.fabrica = {$login_fabrica}
                          AND tbl_os_interacao.os = {$os}
                        ORDER BY tbl_os_interacao.data DESC";
        $resInteracoes = pg_query($con, $sqlInteracoes);
        if (pg_num_rows($resInteracoes) > 0) {
            $i = pg_num_rows($resInteracoes);

            while ($interacao = pg_fetch_object($resInteracoes)) {
            ?>

                <tr <?=($interacao->interno == "t" && !empty($interacao->admin)) ? "class='error'" : ""?> >
                    <td class='conteudo'>
                        <?php
                        echo $i;

                        if (in_array("interacao_email", $inputs_interacao) && $interacao->exigir_resposta == "t" && !in_array("interacao_email_consumidor", $inputs_interacao)) {
                            echo "&nbsp;<i class='icon-envelope pull-right' ></i>";
                        }

                        if (in_array("interacao_transferir", $inputs_interacao) && preg_match("/^transferido para o admin/", strtolower($interacao->mensagem))) {
                            echo "&nbsp;<i class='icon-retweet pull-right' ></i>";
                        }

                        if (in_array("interacao_sms_consumidor", $inputs_interacao)) {
                            if ($interacao->sms == "t") {
                                echo "&nbsp;<i class='glyphicon icon-phone pull-right' ></i>";
                            }
                        }

                        if (in_array("interacao_email_consumidor", $inputs_interacao)) {
                            if ($interacao->interno == "t" && $interacao->exigir_resposta == "t" && preg_match("/^enviou email para o consumidor/", strtolower($interacao->mensagem))) {
                                echo "&nbsp;<i class='icon-envelope pull-right' ></i><i class='icon-user' ></i>";
                            } else if ($interacao->exigir_resposta == "t") {
                                echo "&nbsp;<i class='icon-envelope pull-right' ></i>";
                            }
                        }

                        if (in_array("interacao_atendido", $inputs_interacao)) {
                            if ($interacao->atendido == "t") {
                                echo "&nbsp;<i class='icon-ok pull-right' ></i>";
                            }
                        }
                        ?>
                    </td>
                    <td class="tac conteudo"><?=$interacao->data?></td>
                    <?php
                    if (in_array("interacao_data_contato", $inputs_interacao)) {
                    ?>
                        <td class="tac" ><?=$interacao->data_contato?></td>
                    <?php
                    }
                    ?>
                    <td class='conteudo'><?=$interacao->mensagem?></td>
                    <td class='conteudo'><?=$interacao->admin?></td>
                </tr>

                <?php
                $i--;
            }
        }
        ?>
        </tr>
    </table>
<?php
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
<?php } ?>

<?php

    if(!in_array($login_fabrica, array(20,59))){

        ?>

        <TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
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
            <TABLE width='600px' border='0' cellspacing='0' cellpadding='0'>
                <TR>
                    <TD align='right'>
                        <br /><strong style='font: 12px arial; font-weight: bold;''>Via da Fábrica</strong>
                    </td>
                </tr>
            </table>";
    }

?>

<!--
<div id="container">
    <div id="page">
        <h2>Diagnóstico, Peças usadas e Resolução do Problema:
        <div id="contentcenter" style="width: 600px;">
            <div id="contentleft" style="width: 600px; height: 80px; ">
                <p>Técnico:</p>
                <p></p>
            </div>
        </div>
        </h2>
    </div>
</div>
//-->
<?php
    }
if(!in_array($login_fabrica, array(42,59,128,161,167,203))){ ?>
<TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
    <TR>
        <TD>
            <div id="container" style='width:600px;'>
                <div id="page" style='border:0px;'>
                    <?php if(in_array($login_fabrica, [139])) { ?> 
                        <h2>Problema Identificado e Corrigido: </h2>
                    <?php } else { ?>
                        <h2>Diagnóstico, Peças usadas e Resolução do Problema: </h2>
                    <?php } ?>
                        <?php

                            if(in_array($login_fabrica,array(20,115,116,117,123,124,125,126,127,134,136))){

                                echo "<center>".$peca_dynacom."</center>";
                            }
                         ?>
                        <?=($login_fabrica <> 171) ? 'Técnico:' : ''; ?>
                </div>
            </div>
        </TD>
    </TR>

</TABLE>

<?php }else if($login_fabrica == 42){ ?>

    <TABLE class="borda" width="600" border="0" cellspacing="0" cellpadding="0">
        <TR>
            <TD class='titulo'>Diagnóstico, Peças usadas e Resolução do Problema. Técnico:</td>
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
            <?php
            if (empty($os_auditoria)) {

                echo $topo_peca.$peca_dynacom;
            } else {
                echo $msgAviso;
            }
            ?>
            </TD>
        </TR>

    </TABLE>

<?php

}

?>

<?php

if ($login_fabrica == 59) { /* HD 21229 */

    $aparece_garantia = false;
    $estilo           = "";

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

}

?>
<?php

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
                            <td align="center" style="font-size: 15px;"">DECLARAÇÃO DE ATENDIMENTO</TD>
                        </tr>
                        <tr>
                            <td style="font-size: 13px;padding:5px;" align="left">

                                    "Declaro que houve o devido atendimento do Posto Autorizado, dentro do prazo legal, sendo realizado o conserto do produto, e após a realização dos testes, ficou em perfeitas condições de uso e funcionamento, deixando-me plenamente satisfeito (a)."
                                    <p>
                                        <div style="float:left">
                                            Produto entregue em: '.$recebidoPor.'
                                        </div>
                                        <div style="float:right">
                                            Recebido por: '.$dataRecebimento.'
                                        </div>
                                    </p>
                            </td>
                        </tr>
                    </table><br /> <br /> <br /> <br /><br /> <br /><br /> <br /><br /> <br /> <br /> <br /> <br /> <br />
                    ';
    }

?>


<?php if ($login_fabrica <> 3){ ?>

<TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
<?php if ($login_fabrica <> 171) {
?>
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
<?php } if ($login_fabrica == 52) {?>

    <TR>
        <TD style="font:8px arial; text-align: center; border: 1px dashed #999; padding: 5px;">
            DECLARO QUE O MEU PEDIDO DE VISITA TÉCNICA, FOI ATENDIDO E QUE O PRODUTO DE MINHA PROPRIEDADE FICOU EM PERFEITA CONDIÇÃO.
        </TD>
    </TR>

<?php
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
                        O orçamento será encaminhado via e-mail após analise técnica, o mesmo deverá ser respondido com aprovação ou reprovação do conserto.
                        <br/><br/>
                        Não aceitamos cheque, pagamento somente dinheiro ou cartão.
                        <br/><br/>
                        Na reprovação do conserto, o cliente irá adequar o produto nas mesmas condições em que a empresa o recebeu.
                        <br/><br/>
                        Na reprovação ou conserto do equipamento, o cliente concederá um prazo de 24horas para adequar o produto.
                        <br/><br/>
                        Na hipótese do produto não ser retirado na data mencionada, o mesmo será depositado em juízo para destinação legal.
                    </span>
                    <br/><br/>
                    <span class='texto'>
                        <strong>Garantia do conserto</strong>
                        <br/>
                        O produto descrito conta com a garantia legal de 90 dias, conforme determinado pelo CÓDIGO DE DEFESA DO CONSUMIDOR, contada a partir de sua retirada.
                        <br/><br/>
                        A garantia perderá sua validade se houver violação do lacre colocado pela empresa no produto; se for utilizado suprimentos não originais; ligado a uma rede elétrica imprópria ou sujeita a flutuações; instalado de maneira inadequada; caso sofra danos causados por acidentes ou agentes da natureza tais como quedas, batidas, enchentes, descargas elétricas, raios, conectada em voltagem errada , etc...; ou algum tipo de manutenção por pessoas não autorizada Brother.
                        <br/><br/>
                        <strong>Retirada do produto</strong>
                        <br/>
                        Solicitamos que a retirada do equipamento seja dentro de 60 dias para que não haja taxa de armazenagem. (taxa de R$5,00 por dia).
                        <br/><br/>
                        Precisamos que o cliente tenha em mãos a Ordem de Serviço de entrada para retirada do equipamento.
                    </span>
                <?php
                } else { ?>
                     <span class='texto'>
                        <br/>
                        O produto acima identificado possui garantia contra eventuais defeitos de fabricação, pelo prazo estabelecido no “termo de garantia”, já incluso nesse prazo o da garantia legal de 90 (noventa) dias, contados da data da aquisição do produto pelo primeiro consumidor.
                        <br/><br/>
                        As partes plásticas peças avulsas e os suprimentos possuem apenas a garantia legal de 90 dias corridos, a partir da data de compra.
                        O produto que apresentar defeito de fabricação durante esse prazo será reparado gratuitamente pelo Serviço Técnico Autorizado.
                    </span>
                    <br/><br/>
                    <span class='texto'>
                        A validade da garantia é condicionada à apresentação do original da primeira via da Nota Fiscal de venda no Brasil. Guarde sua Nota Fiscal.
                        A garantia é válida somente para os produtos vendidos no Brasil e que tenham sido colocados no mercado brasileiro pela Brother International Corporation do Brasil Ltda.
                        <br/><br/>

                        Dúvidas ou Reclamações contate nosso canal de atendimento.
                        Help Line Brother: (11) 2256-9110
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
                    <span style='padding-left: 58px; font: 8px arial;'>Assinatura Cliente</span>
                </td>
                <td>
                    <br/>
                    <hr class='data_entrada'></hr>
                    <br/>
                    <span style='padding-left: 28px; font: 8px arial;'>Data entrada</span>
                </td>
            </tr>
            <tr>
                <td>
                    <br/><br/>
                    <hr class='assinatura'></hr>
                    <br/>
                    <span style='padding-left: 58px; font: 8px arial;'>Assinatura Cliente</span>
                </td>
                <td>
                    <br/><br/>
                    <hr class='data_entrada'></hr>
                    <br/>
                    <span style='padding-left: 8px; font: 8px arial;'>Data retirada do produto</span>
                </td>
            </tr>
        </table>
        <br/>
        <?php
    }elseif ($login_fabrica == 171) {
    ?>
        <TR><TD style='font-size: 08px;' colspan='3'>Assinatura do Cliente</TD></TR>
        <TD style='font-size: 08px;' colspan='1' width='60px'><strong>Nome</strong><br /><br /><? echo $consumidor_nome ?></TD>
        <TD style='font-size: 08px;' colspan='1'><strong>Assinatura:</strong><br /><br /><? echo " _____________________________________________________________________________________________" ?></TD>
        <TD style='font-size: 08px; margin-left: 5px;' colspan='1'><strong>Data:</strong><br /><br /><? echo "|____/____/______"; ?></TD>
    <?php
    }elseif (in_array($login_fabrica, array(184,200))) {
?>


        <table width="600px" border="0" cellspacing="2" cellpadding="0">
            
            <tr>
                <td>
                    <br/>
                    <hr class='assinatura'></hr>
                    <br/>
                    <span style='padding-left: 58px; font: 8px arial;'>Assinatura Cliente</span>
                </td>
                <td>
                    <br/>
                    <hr class='data_entrada'></hr>
                    <br/>
                    <span style='padding-left: 28px; font: 8px arial;'>Data entrada</span>
                </td>
            </tr>
            <tr>
                <td>
                    <br/><br/>
                    <hr class='assinatura'></hr>
                    <br/>
                    <span style='padding-left: 58px; font: 8px arial;'>Assinatura Cliente</span>
                </td>
                <td>
                    <br/><br/>
                    <hr class='data_entrada'></hr>
                    <br/>
                    <span style='padding-left: 8px; font: 8px arial;'>Data retirada do produto</span>
                </td>
            </tr>
        </table>
        <br/>



<?
    }else{
    ?>
        <TR>
            <TD style='<?php echo ($login_fabrica != 52) ? "border-bottom:solid 1px" : ""; ?>;'><h2><? echo $consumidor_nome ?> - Assinatura:</h2></TD>
        </TR>
    <?php
    }
    ?>
</TABLE>
<?php
}
if ($login_fabrica == 171) {
    echo "<br /><TABLE width='600px' border='0' cellspacing='2' cellpadding='0'>
                <TR>
                    <TD style='font-size: 08px;' colspan='3'>Assinatura do Técnico</TD>
                </TR>
                <TR>
                    <TD style='font-size: 08px;' colspan='1' width='60px'><strong>Nome</strong><br /><br />_____________</TD>
                    <TD style='font-size: 08px;' colspan='1'><strong>Assinatura:</strong><br /><br /> _____________________________________________________________________________________________</TD>
                    <TD style='font-size: 08px;' colspan='1'><strong>Data:</strong><br /><br />|____/____/______</TD>
                </TR>
                </TABLE><br />";
}
if(($login_fabrica==2 ) AND strlen($peca)>0 AND strlen($data_fechamento)>0){
    echo $peca_dynacom;
}else{
//IMG CORTE

    if($login_fabrica != 52){
        echo "<div id='container'>";
            echo "<IMG SRC='imagens/cabecalho_os_corte.gif' ALT=''>";
        echo "</div>";
    }

$sql = "SELECT  distinct
                tbl_produto.referencia,
                tbl_produto.descricao
        FROM    tbl_os_produto
        JOIN    tbl_produto USING (produto)
        WHERE   tbl_os_produto.os = $os
        ORDER BY tbl_produto.referencia;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0 && !in_array($login_fabrica, array(20))) {
?>
<div id="container">
    <div id="contentleft2" style="width: 110px;">
        <div id="page">
            <div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
                <?php
                    if($login_fabrica == 117){
                        if($consumidor_revenda == "CONSUMIDOR"){
                            echo "<b>OS. $sua_os </b><br><b>". $nome_posto . "</b> <br>" . pg_result ($res,0,descricao) . "<br>N.Série ".$serie."<br>". $consumidor_nome ."<br />$data_os:". $data_abertura;
                        }else{
                            echo "<b>OS. $sua_os <br>". $nome_posto . "</b> <br> " . pg_result ($res,0,descricao) . "<br>N.Série: ".$serie."<br>". $revenda_nome ."<br /><b>$data_os:". $data_abertura ."</b>";
                        }
                    }else{
                        echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) . "<br />". $revenda_nome;
                    }
                ?>
            </div>
        </div>
    </div>
    <div id="contentleft2" style="width: 110px;">
        <div id="page">
            <div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
                <?php
                    if($login_fabrica == 117){
                        if($consumidor_revenda == "CONSUMIDOR"){
                            echo "<b>OS. $sua_os </b><br><b>". $nome_posto . "</b> <br>" . pg_result ($res,0,descricao) . "<br>N.Série ".$serie."<br>". $consumidor_nome ."<br />$data_os:". $data_abertura;
                        }else{
                            echo "<b>OS. $sua_os <br>". $nome_posto . "</b> <br> " . pg_result ($res,0,descricao) . "<br>N.Série: ".$serie."<br>". $revenda_nome ."<br /><b>$data_os:". $data_abertura ."</b>";
                        }
                    }else{
                        echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) . "<br />". $revenda_nome;
                    }
                ?>
            </div>
        </div>
    </div>
    <div id="contentleft2" style="width: 110px;">
        <div id="page">
            <div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
                <?php
                    if($login_fabrica == 117){
                        if($consumidor_revenda == "CONSUMIDOR"){
                            echo "<b>OS. $sua_os </b><br><b>". $nome_posto . "</b> <br>" . pg_result ($res,0,descricao) . "<br>N.Série ".$serie."<br>". $consumidor_nome ."<br />$data_os:". $data_abertura;
                        }else{
                            echo "<b>OS. $sua_os <br>". $nome_posto . "</b> <br> " . pg_result ($res,0,descricao) . "<br>N.Série: ".$serie."<br>". $revenda_nome ."<br /><b>$data_os:". $data_abertura ."</b>";
                        }
                    }else{
                        echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) . "<br />". $revenda_nome;
                    }
                ?>
            </div>
        </div>
    </div>
    <div id="contentleft2" style="width: 110px;">
        <div id="page">
            <div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
                <?php
                    if($login_fabrica == 117){
                        if($consumidor_revenda == "CONSUMIDOR"){
                            echo "<b>OS. $sua_os </b><br><b>". $nome_posto . "</b> <br>" . pg_result ($res,0,descricao) . "<br>N.Série ".$serie."<br>". $consumidor_nome ."<br />$data_os:". $data_abertura;
                        }else{
                            echo "<b>OS. $sua_os <br>". $nome_posto . "</b> <br> " . pg_result ($res,0,descricao) . "<br>N.Série: ".$serie."<br>". $revenda_nome ."<br /><b>$data_os:". $data_abertura ."</b>";
                        }
                    }else{
                        echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) . "<br />". $revenda_nome;
                    }
                ?>
            </div>
        </div>
    </div>
    <div id="contentleft2" style="width: 110px;">
        <div id="page">
            <div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
                <?php
                    if($login_fabrica == 117){
                        if($consumidor_revenda == "CONSUMIDOR"){
                            echo "<b>OS. $sua_os </b><br><b>". $nome_posto . "</b> <br>" . pg_result ($res,0,descricao) . "<br>N.Série ".$serie."<br>". $consumidor_nome ."<br />$data_os:". $data_abertura;
                        }else{
                            echo "<b>OS. $sua_os <br>". $nome_posto . "</b> <br> " . pg_result ($res,0,descricao) . "<br>N.Série: ".$serie."<br>". $revenda_nome ."<br /><b>$data_os:". $data_abertura ."</b>";
                        }
                    }else{
                        echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) . "<br />". $revenda_nome;
                    }
                ?>
            </div>
        </div>
    </div>
</div>
<? }
}?>
</div>

<?php
    if($login_fabrica == 52){
        echo "<p align='left'> <strong>OS</strong> $sua_os &nbsp; <strong>Ref.</strong> $referencia &nbsp; <strong>Descr.</strong> $descricao &nbsp; <strong>N.Série</strong> $serie &nbsp; <strong>Tel.</strong> $consumidor_fone </p>";
    }
?>

<?if(in_array($login_fabrica, array(19,20))){?>
<BR>
<TABLE width="600px" border="1" cellspacing="0" cellpadding="0" style="display: block; margin-top: 20px">
    <TR>
        <TD class="etiqueta">
            <? echo "<b><font size='2px'>OS $sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
        </TD>
        <TD class="etiqueta">
            <? echo "<b><font size='2px'>OS $sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
        </TD>
        <TD class="etiqueta">
            <? echo "<b><font size='2px'>OS $sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
        </TD>
        <TD class="etiqueta">
            <? echo "<b><font size='2px'>OS $sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
        </TD>
        <TD class="etiqueta">
            <? echo "<b><font size='2px'>OS $sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
        </TD>
    </TR>
    <?php if(!in_array($login_fabrica, array(20))){ ?>
    <TR>
        <TD class="etiqueta">
            <? echo "<b><font size='2px'>OS $sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
        </TD>
        <TD class="etiqueta">
            <? echo "<b><font size='2px'>OS $sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
        </TD>
        <TD class="etiqueta">
            <? echo "<b><font size='2px'>OS $sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
        </TD>
        <TD class="etiqueta">
            <? echo "<b><font size='2px'>OS $sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
        </TD>
        <TD class="etiqueta">
            <? echo "<b><font size='2px'>OS $sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
        </TD>
    </TR>
    <?php } ?>
</TABLE>

<?
}

if ($fabricaFileUploadOS) { 

    include 'TdocsMirror.php';
    include 'controllers/ImageuploaderTiposMirror.php';

    $imageUploaderTipos = new ImageuploaderTiposMirror();

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
<?php } else { ?>
    <script language="JavaScript">
      window.print();
    </script>
<?php } ?>
</BODY>

</html>

