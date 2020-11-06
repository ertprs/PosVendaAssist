<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin === true) {
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include __DIR__.'/includes/funcoes.php';
include __DIR__.'/ajax_cabecalho.php';;
include_once __DIR__.'/class/ComunicatorMirror.php';
include_once __DIR__.'/plugins/fileuploader/TdocsMirror.php';
include_once __DIR__.'/helpdesk/mlg_funciones.php';

$comunicatorMirror = new ComunicatorMirror();
// ============================== VER TREINAMENTOS ============================== //
if($_GET['ajax'] == 'sim' AND $_GET['acao'] == 'ver') {

    if(in_array($login_fabrica, array(175))){

        // ============================== TREINAMENTOS DISPONÍVEIS PARA INSCRIÇÃO  ============================== //
        $sql_disponiveis = "
            SELECT
                t.treinamento,
                t.titulo,
                t.descricao,
                t.vagas,
                t.estado,
                c.nome AS cidade,
                t.local,
                tp.nome AS treinamento_tipo,
                t.ativo,
                t.linha,
                t.data_finalizado,
                NULL AS tecnico,
                TO_CHAR(t.data_inicio, 'DD/MM/YYYY') AS data_inicio,
                TO_CHAR(t.data_fim, 'DD/MM/YYYY') AS data_fim,
                TO_CHAR(t.inicio_inscricao, 'DD/MM/YYYY') AS inicio_inscricao,
                TO_CHAR(t.prazo_inscricao, 'DD/MM/YYYY') AS prazo_inscricao,
                ARRAY_TO_STRING(ARRAY_AGG(DISTINCT(l.nome)), ', ', NULL) AS linhas,
                ARRAY_TO_STRING(ARRAY_AGG(DISTINCT(p.descricao)), ', ', NULL) AS produtos,
                (
                    SELECT COUNT(*)
                    FROM tbl_treinamento_posto
                    WHERE treinamento = t.treinamento
                    AND ativo IS TRUE
                ) AS qtde_postos
            FROM tbl_treinamento t
            INNER JOIN tbl_treinamento_produto tprod ON tprod.treinamento = t.treinamento
            INNER JOIN tbl_posto_linha pl ON pl.linha = tprod.linha AND pl.posto = {$login_posto}
            INNER JOIN tbl_linha l ON l.fabrica = {$login_fabrica} AND l.linha = pl.linha
            LEFT JOIN tbl_produto p ON p.fabrica_i = {$login_fabrica} AND p.produto = tprod.produto
            LEFT JOIN tbl_cidade c ON c.cidade = t.cidade
            INNER JOIN tbl_treinamento_tipo tp ON tp.fabrica = {$login_fabrica} AND tp.treinamento_tipo = t.treinamento_tipo
            LEFT JOIN tbl_treinamento_posto tpst ON tpst.treinamento = t.treinamento
            LEFT JOIN tbl_tecnico tec ON tec.tecnico = tpst.tecnico
            LEFT JOIN tbl_login_unico lu ON lu.posto = {$login_posto} AND lu.login_unico::varchar = tec.codigo_externo AND lu.login_unico = {$login_unico}
            WHERE t.fabrica = {$login_fabrica}
            AND t.ativo IS TRUE
            AND (
                (
                    tp.nome = 'Presencial' 
                    AND CURRENT_DATE  >= t.inicio_inscricao
                    AND CURRENT_DATE <= t.prazo_inscricao
                    AND t.data_finalizado IS NULL
                )
                OR 
                (
                    tp.nome = 'Online'
                    AND t.inicio_inscricao IS NULL 
                    AND t.prazo_inscricao IS NULL
                )
            )
            AND lu.login_unico IS NULL
            AND (
                     t.treinamento NOT IN (SELECT treinamento FROM tbl_treinamento_posto JOIN tbl_tecnico USING(tecnico) WHERE tbl_tecnico.codigo_externo = '{$login_unico}' AND  tbl_tecnico.posto = {$login_posto} AND (tbl_tecnico.fabrica = {$login_fabrica} OR tbl_tecnico.fabrica IS NULL))  
                     OR tpst.tecnico IS NULL
                 )
            GROUP BY t.treinamento, c.nome, tp.nome
        ";
        $res_disponiveis = pg_query($con,$sql_disponiveis);
        
        if (pg_numrows($res_disponiveis) > 0) {
            $resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>";
            $resposta  .=  "<TR class='Conteudo2' height='20' bgcolor='#ffffff'>";
            $resposta  .=  "<TD.colspan='10' align='center'><br><img src='imagens/img_ok.gif'> <b>TREINAMENTOS DISPONÍVEIS PARA INSCRIÇÃO</b></td>";
            $resposta  .=  "</TR>";
            $resposta  .=  "</table>";
            $resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>";
            $resposta  .=  "<TR class='Titulo2' height='20' bgcolor='$cor'>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Título</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Tipo de Treinamento</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Data Início</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Data Fim</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Prazo Inscrição</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Produtos</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Descrição</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Vagas Disponíveis</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Região</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Cidade - UF</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Local</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Ação</b></TD>";
            $resposta  .=  "</TR>";

            for ($i=0; $i<pg_numrows($res_disponiveis); $i++) {
                // reset
                $status             = "";
                $btn_acao           = "";

                $treinamento        = trim(pg_result($res_disponiveis,$i,treinamento));
                $titulo             = trim(pg_result($res_disponiveis,$i,titulo));
                $treinamento_tipo   = trim(pg_result($res_disponiveis,$i,treinamento_tipo));
                $data_inicio        = trim(pg_result($res_disponiveis,$i,data_inicio));
                $data_fim           = trim(pg_result($res_disponiveis,$i,data_fim));
                $prazo_inscricao    = trim(pg_result($res_disponiveis,$i,prazo_inscricao));
                $linhas             = trim(pg_result($res_disponiveis,$i,linhas));
                $produtos           = trim(pg_result($res_disponiveis,$i,produtos));
                $ativo              = trim(pg_result($res_disponiveis,$i,ativo));
                $descricao          = trim(pg_result($res_disponiveis,$i,descricao));
                $qtde_postos        = trim(pg_result($res_disponiveis,$i,qtde_postos));
                $local              = trim(pg_result($res_disponiveis,$i,local));
                $cidade             = trim(pg_result($res_disponiveis,$i,cidade));
                $estado             = trim(pg_result($res_disponiveis,$i,estado));
                $vagas              = trim(pg_result($res_disponiveis,$i,vagas));

                // ============================== VAGAS  ============================== //
                $sql_vagas = "SELECT
                                tbl_treinamento.treinamento,
                                tbl_treinamento.vagas,
                                (
                                    SELECT COUNT(*)
                                    FROM tbl_treinamento_posto
                                    WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                    AND   tbl_treinamento_posto.ativo IS TRUE
                                )                                                     AS total_inscritos
                        FROM    tbl_treinamento
                        WHERE   tbl_treinamento.fabrica     = {$login_fabrica}
                            AND tbl_treinamento.treinamento = {$treinamento}";
                $res_vagas = pg_exec ($con,$sql_vagas);

                if (pg_numrows($res_vagas) > 0) {
                    $total_inscritos = trim(pg_result($res_vagas,0,total_inscritos));
                    $vagas           = trim(pg_result($res_vagas,0,vagas));
                    $vagas_disp      = (int)$vagas - (int)$total_inscritos;

                    if (in_array($login_fabrica, array(175))) {
                        if ($treinamento_tipo == 'Online' || empty($vagas_disp)) {
                            $vagas_disp = "N/A";
                        }
                    }
                }

                if ($treinamento_tipo == 'Presencial'){
                    $treinamento_tipo = 'PRESENCIAL';
                    
                    if ($total_inscritos < $vagas){
                        $btn_acao         = '<a class="realizar_inscricao" data-posto="'.$login_posto.'" data-isOnline="f" data-tecnico="'.$login_unico.'" data-treinamento="'.$treinamento.'" style="font-size: 10px; color: blue; font-weight: bold; cursor: pointer;">Realizar Inscrição</a>';
                    }else if ($total_inscritos >= $vagas){
                        $btn_acao         = '<span style="color: #ff6666;">Não há vagas disponíveis</span>';    
                    }

                }else if ($treinamento_tipo == 'Online') {
                    $treinamento_tipo = 'ONLINE';
                    $btn_acao         = '<a class="realizar_inscricao" data-posto="'.$login_posto.'" data-isOnline="t" data-tecnico="'.$login_unico.'" data-treinamento="'.$treinamento.'" style="font-size: 10px; color: blue; font-weight: bold; cursor: pointer;">Realizar Inscrição</a>';
                }

                $resposta  .=  "<TR bgcolor='#F6F6F6'class='Conteudo'>";
                $resposta  .=  "<TD align='left'>$titulo </TD>";
                $resposta  .=  "<TD align='left'nowrap>$treinamento_tipo</TD>";
                $resposta  .=  "<TD align='left'>$data_inicio</TD>";
                $resposta  .=  "<TD align='center'>$data_fim</TD>";
                $resposta  .=  "<TD align='center'>$prazo_inscricao</TD>";
                $resposta  .=  "<TD align='center'>$produtos</TD>";
                $resposta  .=  "<TD align='center'> <a class='show_descricao' data-url='treinamento_tecnico.php?treinamento=".$treinamento."&acao=descricao' style='cursor: pointer;'> visualizar descrição </a> </TD>";
                $resposta  .=  "<TD align='center'>$vagas_disp</TD>";
                $resposta  .=  "<TD align='center'> </TD>";
                $resposta  .=  "<TD align='center'>$cidade - $estado</TD>";
                $resposta  .=  "<TD align='center'>$local</TD>";
                $resposta  .=  "<TD align='center'>$btn_acao</TD>";
                $resposta  .=  "</TR>";
            }
            $resposta      .= " </TABLE>";
        }else{
            $resposta      .= "<b>Nenhum treinamento disponível para inscrição</b>";
        }


        // ============================== TREINAMENTOS PRESENCIAS INSCRITO  ============================== //
        $sql_presencial = "
            SELECT
                t.treinamento,
                t.titulo,
                t.descricao,
                t.vagas,
                t.estado,
                c.nome AS cidade,
                t.local,
                tp.nome AS treinamento_tipo,
                t.ativo,
                t.linha,
                t.data_finalizado,
                tec.tecnico,
                TO_CHAR(t.data_inicio, 'DD/MM/YYYY') AS data_inicio,
                TO_CHAR(t.data_fim, 'DD/MM/YYYY') AS data_fim,
                TO_CHAR(t.inicio_inscricao, 'DD/MM/YYYY') AS inicio_inscricao,
                TO_CHAR(t.prazo_inscricao, 'DD/MM/YYYY') AS prazo_inscricao,
                ARRAY_TO_STRING(ARRAY_AGG(DISTINCT(l.nome)), ', ', NULL) AS linhas,
                ARRAY_TO_STRING(ARRAY_AGG(DISTINCT(p.descricao)), ', ', NULL) AS produtos,
                (
                    SELECT COUNT(*)
                    FROM tbl_treinamento_posto
                    WHERE treinamento = t.treinamento
                    AND ativo IS TRUE
                ) AS qtde_postos
            FROM tbl_treinamento t
            INNER JOIN tbl_treinamento_produto tprod ON tprod.treinamento = t.treinamento
            INNER JOIN tbl_posto_linha pl ON pl.linha = tprod.linha AND pl.posto = {$login_posto}
            INNER JOIN tbl_linha l ON l.fabrica = {$login_fabrica} AND l.linha = pl.linha
            LEFT JOIN tbl_produto p ON p.fabrica_i = {$login_fabrica} AND p.produto = tprod.produto
            LEFT JOIN tbl_cidade c ON c.cidade = t.cidade
            INNER JOIN tbl_treinamento_tipo tp ON tp.fabrica = {$login_fabrica} AND tp.treinamento_tipo = t.treinamento_tipo
            INNER JOIN tbl_treinamento_posto tpst ON tpst.treinamento = t.treinamento
            INNER JOIN tbl_tecnico tec ON tec.tecnico = tpst.tecnico
            INNER JOIN tbl_login_unico lu ON lu.posto = {$login_posto} AND lu.login_unico::varchar = tec.codigo_externo AND lu.login_unico = {$login_unico}
            WHERE t.fabrica = {$login_fabrica}
            AND t.ativo IS TRUE
            AND t.data_finalizado IS NULL
            AND tp.nome = 'Presencial'
            GROUP BY t.treinamento, c.nome, tp.nome, tec.tecnico
        ";
        $res_presencial = pg_query($con,$sql_presencial);
        
        if (pg_numrows($res_presencial) > 0) {
            $resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>";
            $resposta  .=  "<TR class='Conteudo2' height='20' bgcolor='#ffffff'>";
            $resposta  .=  "<TD.colspan='10' align='center'><br><img src='imagens/img_ok.gif'> <b>TREINAMENTOS PRESENCIAS INSCRITO</b></td>";
            $resposta  .=  "</TR>";
            $resposta  .=  "</table>";
            $resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>";
            $resposta  .=  "<TR class='Titulo2' height='20' bgcolor='$cor'>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Título</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Data Início</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Data Fim</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Produtos</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Região</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Cidade - UF</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Local</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Descrição</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Status</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Ação</b></TD>";
            $resposta  .=  "</TR>";

            for ($i=0; $i<pg_numrows($res_presencial); $i++) {
                // reset
                $status             = "";
                $btn_acao           = "";
                
                $treinamento        = trim(pg_result($res_presencial,$i,treinamento));
                $titulo             = trim(pg_result($res_presencial,$i,titulo));
                $treinamento_tipo   = trim(pg_result($res_presencial,$i,treinamento_tipo));
                $data_inicio        = trim(pg_result($res_presencial,$i,data_inicio));
                $data_fim           = trim(pg_result($res_presencial,$i,data_fim));
                $prazo_inscricao    = trim(pg_result($res_presencial,$i,prazo_inscricao));
                $linhas             = trim(pg_result($res_presencial,$i,linhas));
                $produtos           = trim(pg_result($res_presencial,$i,produtos));
                $ativo              = trim(pg_result($res_presencial,$i,ativo));
                $descricao          = trim(pg_result($res_presencial,$i,descricao));
                $qtde_postos        = trim(pg_result($res_presencial,$i,qtde_postos));
                $local              = trim(pg_result($res_presencial,$i,local));
                $cidade             = trim(pg_result($res_presencial,$i,cidade));
                $estado             = trim(pg_result($res_presencial,$i,estado));
                $hoje               = date("d/m/Y");

                if ($data_inicio > $hoje)
                {
                    $status       = "<label class='label-info'>a realizar</label>"; 
                    $btn_acao         = '<a class="remover_inscricao" data-posto="'.$login_posto.'" data-isOnline="t" data-tecnico="'.$login_unico.'" data-treinamento="'.$treinamento.'" style="font-size: 10px; color: blue; font-weight: bold; cursor: pointer;">Remover Inscrição</a>';
                }else if (($hoje >= $data_inicio) && ($hoje <= $data_fim))
                {
                    $status       = '<label class="label-default">em realização</label>';    
                }else if ($data_fim > $hoje)
                {
                    $status       = '<label class="label-warning">aguardando avaliação</label>';    
                }

                $resposta  .=  "<TR bgcolor='#F6F6F6'class='Conteudo'>";
                $resposta  .=  "<TD align='left'>$titulo </TD>";
                $resposta  .=  "<TD align='left'>$data_inicio</TD>";
                $resposta  .=  "<TD align='center'>$data_fim</TD>";
                $resposta  .=  "<TD align='center'>$produtos</TD>";
                $resposta  .=  "<TD align='center'> </TD>";
                $resposta  .=  "<TD align='center'>$cidade - $estado</TD>";
                $resposta  .=  "<TD align='center'>$local</TD>";
                $resposta  .=  "<TD align='center'> <a class='show_descricao' data-url='treinamento_tecnico.php?treinamento=".$treinamento."&acao=descricao' style='cursor: pointer;'> visualizar descrição </a> </TD>";
                $resposta  .=  "<TD align='center'>$status</TD>";
                $resposta  .=  "<TD align='center'>$btn_acao</TD>";
                $resposta  .=  "</TR>";
            }
            $resposta      .= " </TABLE>";
        }

        // ============================== TREINAMENTOS ONLINE  INSCRITO ============================== //
        $sql_online = "
            SELECT
                t.treinamento,
                t.titulo,
                t.descricao,
                t.vagas,
                t.estado,
                c.nome AS cidade,
                t.local,
                tp.nome AS treinamento_tipo,
                t.ativo,
                t.linha,
                t.data_finalizado,
                tec.tecnico,
                TO_CHAR(t.data_inicio, 'DD/MM/YYYY') AS data_inicio,
                TO_CHAR(t.data_fim, 'DD/MM/YYYY') AS data_fim,
                TO_CHAR(t.inicio_inscricao, 'DD/MM/YYYY') AS inicio_inscricao,
                TO_CHAR(t.prazo_inscricao, 'DD/MM/YYYY') AS prazo_inscricao,
                ARRAY_TO_STRING(ARRAY_AGG(DISTINCT(l.nome)), ', ', NULL) AS linhas,
                ARRAY_TO_STRING(ARRAY_AGG(DISTINCT(p.descricao)), ', ', NULL) AS produtos,
                (
                    SELECT COUNT(*)
                    FROM tbl_treinamento_posto
                    WHERE treinamento = t.treinamento
                    AND ativo IS TRUE
                ) AS qtde_postos,
                tpst.participou,
                tpst.aprovado,
                tpst.data_avaliacao
            FROM tbl_treinamento t
            INNER JOIN tbl_treinamento_produto tprod ON tprod.treinamento = t.treinamento
            INNER JOIN tbl_posto_linha pl ON pl.linha = tprod.linha AND pl.posto = {$login_posto}
            INNER JOIN tbl_linha l ON l.fabrica = {$login_fabrica} AND l.linha = pl.linha
            LEFT JOIN tbl_produto p ON p.fabrica_i = {$login_fabrica} AND p.produto = tprod.produto
            LEFT JOIN tbl_cidade c ON c.cidade = t.cidade
            INNER JOIN tbl_treinamento_tipo tp ON tp.fabrica = {$login_fabrica} AND tp.treinamento_tipo = t.treinamento_tipo
            INNER JOIN tbl_treinamento_posto tpst ON tpst.treinamento = t.treinamento
            INNER JOIN tbl_tecnico tec ON tec.tecnico = tpst.tecnico
            INNER JOIN tbl_login_unico lu ON lu.posto = {$login_posto} AND lu.login_unico::varchar = tec.codigo_externo AND lu.login_unico = {$login_unico}
            WHERE t.fabrica = {$login_fabrica}
            AND t.ativo IS TRUE
            AND tp.nome = 'Online'
            GROUP BY t.treinamento, c.nome, tp.nome, tec.tecnico, tpst.participou, tpst.aprovado, tpst.data_avaliacao
        ";
        $res_online = pg_query($con,$sql_online);
        
        if (pg_numrows($res_online) > 0) {
            $resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>";
            $resposta  .=  "<TR class='Conteudo2' height='20' bgcolor='#ffffff'>";
            $resposta  .=  "<TD.colspan='10' align='center'><br><img src='imagens/img_ok.gif'> <b>TREINAMENTOS ONLINE INSCRITO</b></td>";
            $resposta  .=  "</TR>";
            $resposta  .=  "</table>";
            $resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>";
            $resposta  .=  "<TR class='Titulo2' height='20' bgcolor='$cor'>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Título</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Produtos</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Descrição</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Status</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Ação</b></TD>";
            $resposta  .=  "</TR>";

            for ($i=0; $i<pg_numrows($res_online); $i++) {
                // reset
                $status             = "";
                $btn_acao           = "";
                
                $treinamento        = trim(pg_result($res_online,$i,treinamento));
                $titulo             = trim(pg_result($res_online,$i,titulo));
                $treinamento_tipo   = trim(pg_result($res_online,$i,treinamento_tipo));
                $linhas             = trim(pg_result($res_online,$i,linhas));
                $produtos           = trim(pg_result($res_online,$i,produtos));
                $ativo              = trim(pg_result($res_online,$i,ativo));
                $descricao          = trim(pg_result($res_online,$i,descricao));
                $participou         = trim(pg_result($res_online,$i,participou));
                $aprovado           = trim(pg_result($res_online,$i,aprovado));
                $data_finalizado    = trim(pg_result($res_online,$i,data_finalizado));
                $tecnico            = trim(pg_result($res_online,$i,tecnico));
                $data_avaliacao     = trim(pg_result($res_online,$i,data_avaliacao));

                if ($participou == 't' && $aprovado == 't' && !empty($data_avaliacao))
                {
                    $status = "<label class='label-success'>Aprovado</label>";
                }else if ($participou == 't' && $aprovado == 'f')
                {
                    $status = "<label class='label-important'>Reprovado</label>";
                }else if ($participou == 't' && empty($data_finalizado))
                {
                    $status = "<label class='label-info'>Aguardando avaliação</label>";
                }else if ($participou == 'f')
                {
                    $status = "<label class='label-warning'>Não realizado</label>";
                }

                if ($participou == 'f'){
                    $btn_acao = '<a class="ativa_desativa_participou" data-posto="'.$login_posto.'" data-tecnico="'.$login_unico.'" data-treinamento="'.$treinamento.'" style="font-size: 10px; color: blue; font-weight: bold; cursor: pointer;">Concluído</a>';
                }

                if ($participou == 't' && $aprovado == 't' && !empty($data_avaliacao)){
                
                    /************** PEGANDO LINK DO CERTIFICADO **************/
                    $sql_certificado = "SELECT tdocs_id 
                                    FROM  tbl_tdocs 
                                    WHERE fabrica           = {$login_fabrica}
                                          AND referencia    = '".$treinamento."_".$tecnico."'
                                          AND referencia_id = {$treinamento}";
                    $res_certificado = pg_query($con,$sql_certificado);
                    $msg_erro       .= pg_last_error($con);
                    if (pg_num_rows($res_certificado) > 0){

                        for ($i2=0; $i2<pg_num_rows($res_certificado); $i2++){
                            $unique_id        = pg_fetch_result($res_certificado,$i2,tdocs_id);
                            $tdocsMirror      = new TdocsMirror();
                            $resposta_link    = $tdocsMirror->get($unique_id);
                            $link_certificado = $resposta_link["link"];    
                       
                            if (empty($link_certificado)){
                                $btn_acao = '<a style="font-size: 10px; color: #ff6666; font-weight: bold; text-decoration: none; cursor: not-allowed;">Certificado indisponível</a>';
                            }else{
                                $btn_acao = '<a href="'.$link_certificado.'" target="_blank" style="font-size: 10px; color: blue; font-weight: bold; cursor: pointer;">Certificado</a>';
                            }
                        }
                    }                    
                }
                
                $resposta  .=  "<TR bgcolor='#F6F6F6'class='Conteudo'>";
                $resposta  .=  "<TD align='left' class='teste'>$titulo </TD>";
                $resposta  .=  "<TD align='center'>$produtos</TD>";
                $resposta  .=  "<TD align='center'> <a class='show_descricao' data-url='treinamento_tecnico.php?treinamento=".$treinamento."&acao=descricao' style='cursor: pointer;'> visualizar descrição </a> </TD>";
                $resposta  .=  "<TD align='center'>$status</TD>";
                $resposta  .=  "<TD align='center'>$btn_acao</TD>";
                $resposta  .=  "</TR>";
            }
            $resposta      .= " </TABLE>";
        }

        // ============================== TREINAMENTOS PRESENCIAS REALIZADOS ============================== //
        $sql_presencial_realizado = "
            SELECT
                t.treinamento,
                t.titulo,
                t.descricao,
                t.vagas,
                t.estado,
                c.nome AS cidade,
                t.local,
                tp.nome AS treinamento_tipo,
                t.ativo,
                t.linha,
                t.data_finalizado,
                tec.tecnico,
                TO_CHAR(t.data_inicio, 'DD/MM/YYYY') AS data_inicio,
                TO_CHAR(t.data_fim, 'DD/MM/YYYY') AS data_fim,
                TO_CHAR(t.inicio_inscricao, 'DD/MM/YYYY') AS inicio_inscricao,
                TO_CHAR(t.prazo_inscricao, 'DD/MM/YYYY') AS prazo_inscricao,
                ARRAY_TO_STRING(ARRAY_AGG(DISTINCT(l.nome)), ', ', NULL) AS linhas,
                ARRAY_TO_STRING(ARRAY_AGG(DISTINCT(p.descricao)), ', ', NULL) AS produtos,
                (
                    SELECT COUNT(*)
                    FROM tbl_treinamento_posto
                    WHERE treinamento = t.treinamento
                    AND ativo IS TRUE
                ) AS qtde_postos,
                tpst.participou,
                tpst.aprovado,
                tpst.data_avaliacao
            FROM tbl_treinamento t
            INNER JOIN tbl_treinamento_produto tprod ON tprod.treinamento = t.treinamento
            INNER JOIN tbl_posto_linha pl ON pl.linha = tprod.linha AND pl.posto = {$login_posto}
            INNER JOIN tbl_linha l ON l.fabrica = {$login_fabrica} AND l.linha = pl.linha
            LEFT JOIN tbl_produto p ON p.fabrica_i = {$login_fabrica} AND p.produto = tprod.produto
            LEFT JOIN tbl_cidade c ON c.cidade = t.cidade
            INNER JOIN tbl_treinamento_tipo tp ON tp.fabrica = {$login_fabrica} AND tp.treinamento_tipo = t.treinamento_tipo
            INNER JOIN tbl_treinamento_posto tpst ON tpst.treinamento = t.treinamento
            INNER JOIN tbl_tecnico tec ON tec.tecnico = tpst.tecnico
            INNER JOIN tbl_login_unico lu ON lu.posto = {$login_posto} AND lu.login_unico::varchar = tec.codigo_externo AND lu.login_unico = {$login_unico}
            WHERE t.fabrica = {$login_fabrica}
            AND t.ativo IS TRUE
            AND t.data_finalizado IS NOT NULL
            AND tp.nome = 'Presencial'
            GROUP BY t.treinamento, c.nome, tp.nome, tec.tecnico, tpst.participou, tpst.aprovado, tpst.data_avaliacao
            ORDER BY tpst.participou ASC, tpst.data_avaliacao DESC
        ";
        $res_presencial_realizado = pg_query($con,$sql_presencial_realizado);
        
        if (pg_numrows($res_presencial_realizado) > 0) {
            $resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>";
            $resposta  .=  "<TR class='Conteudo2' height='20' bgcolor='#ffffff'>";
            $resposta  .=  "<TD.colspan='10' align='center'><br><img src='imagens/img_ok.gif'> <b>TREINAMENTOS PRESENCIAS REALIZADOS</b></td>";
            $resposta  .=  "</TR>";
            $resposta  .=  "</table>";
            $resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>";
            $resposta  .=  "<TR class='Titulo2' height='20' bgcolor='$cor'>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Título</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Data Início</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Data Fim</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Produtos</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Região</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Cidade - UF</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Local</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Descrição</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Status</b></TD>";
            $resposta  .=  "<TD background='admin/imagens_admin/azul.gif'><b>Ação</b></TD>";
            $resposta  .=  "</TR>";

            for ($i=0; $i<pg_numrows($res_presencial_realizado); $i++) {
                // reset
                $status             = "";
                $btn_acao           = "";
                
                $treinamento        = trim(pg_result($res_presencial_realizado,$i,treinamento));
                $titulo             = trim(pg_result($res_presencial_realizado,$i,titulo));
                $treinamento_tipo   = trim(pg_result($res_presencial_realizado,$i,treinamento_tipo));
                $linhas             = trim(pg_result($res_presencial_realizado,$i,linhas));
                $produtos           = trim(pg_result($res_presencial_realizado,$i,produtos));
                $ativo              = trim(pg_result($res_presencial_realizado,$i,ativo));
                $descricao          = trim(pg_result($res_presencial_realizado,$i,descricao));
                $participou         = trim(pg_result($res_presencial_realizado,$i,participou));
                $aprovado           = trim(pg_result($res_presencial_realizado,$i,aprovado));
                $data_finalizado    = trim(pg_result($res_presencial_realizado,$i,data_finalizado));
                $tecnico            = trim(pg_result($res_presencial_realizado,$i,tecnico));
                $data_avaliacao     = trim(pg_result($res_presencial_realizado,$i,data_avaliacao));
                $cidade             = trim(pg_result($res_presencial_realizado,$i,cidade));
                $estado             = trim(pg_result($res_presencial_realizado,$i,estado));

                if ($participou == 'f')
                {
                    $status = "<label class='label-warning'>Ausente</label>";
                }else if ($participou == 't' && $aprovado == 'f')
                {
                    $status = "<label class='label-important'>Reprovado</label>";
                }else if ($participou == 't' && $aprovado == 't')
                {
                    $status = "<label class='label-success'>Aprovado</label>";
                
                    /************** PEGANDO LINK DO CERTIFICADO **************/
                    $sql_certificado = "SELECT tdocs_id 
                                    FROM  tbl_tdocs 
                                    WHERE fabrica           = {$login_fabrica}
                                          AND referencia    = '".$treinamento."_".$tecnico."'
                                          AND referencia_id = {$treinamento}";
                    $res_certificado = pg_query($con,$sql_certificado);
                    $msg_erro       .= pg_last_error($con);
                    if (pg_num_rows($res_certificado) > 0){
                        for ($i2=0; $i2<pg_num_rows($res_certificado); $i2++){
                            $unique_id        = pg_fetch_result($res_certificado,$i2,tdocs_id);
                            $tdocsMirror      = new TdocsMirror();
                            $resposta_link    = $tdocsMirror->get($unique_id);
                            $link_certificado = $resposta_link["link"];    
                       
                            if (empty($link_certificado)){
                                $btn_acao = '<a style="font-size: 10px; color: #ff6666; font-weight: bold; text-decoration: none; cursor: not-allowed;">Certificado indisponível</a>';
                            }else{
                                $btn_acao = '<a href="'.$link_certificado.'" target="_blank" style="font-size: 10px; color: blue; font-weight: bold; cursor: pointer;">Certificado</a>';
                            }
                        }
                    }
                }
                
                $resposta  .=  "<TR bgcolor='#F6F6F6'class='Conteudo'>";
                $resposta  .=  "<TD align='left'>$titulo </TD>";
                $resposta  .=  "<TD align='left'>$data_inicio</TD>";
                $resposta  .=  "<TD align='center'>$data_fim</TD>";
                $resposta  .=  "<TD align='center'>$produtos</TD>";
                $resposta  .=  "<TD align='center'> </TD>";
                $resposta  .=  "<TD align='center'>$cidade - $estado</TD>";
                $resposta  .=  "<TD align='center'>$local</TD>";
                $resposta  .=  "<TD align='center'> <a class='show_descricao' data-url='treinamento_tecnico.php?treinamento=".$treinamento."&acao=descricao' style='cursor: pointer;'> visualizar descrição </a> </TD>";
                $resposta  .=  "<TD align='center'>$status</TD>";
                $resposta  .=  "<TD align='center'>$btn_acao</TD>";
                $resposta  .=  "</TR>";
            }
            $resposta      .= " </TABLE>";
        }
    }

    echo "ok|".$resposta;
    exit;
}

// ============================== CADASTRAR TECNICO EM TREINAMENTO ============================== //
if ($_GET['ajax'] == 'sim' AND $_GET['acao'] == 'cadastrar_tecnico'){    
    $id_treinamento = addslashes($_GET['treinamento']);
    $id_posto       = addslashes($_GET['posto']);
    $id_tecnico     = addslashes($_GET['tecnico']);

    if (empty($id_treinamento)){
        $msg .= "Informe o treinamento.";
        exit(json_encode(array("erro" => utf8_encode($msg))));
    }

    if (empty($id_posto)){
        $msg .= "Informe o Posto.";
        exit(json_encode(array("erro" => utf8_encode($msg))));
    }

    if (empty($id_tecnico)){
        $msg .= "Informe o Técnico.";
        exit(json_encode(array("erro" => utf8_encode($msg))));
    }

    $isOnline       = $_GET['isOnline'];
    if ($isOnline == 't'){
        $campo_data_inscricao = ", data_inscricao ";
        $valor_data_inscricao = ", CURRENT_TIMESTAMP ";
    }
    
    $get_id_tecnico = "SELECT tbl_tecnico.tecnico 
                    FROM tbl_tecnico
                         INNER JOIN tbl_login_unico ON (tbl_login_unico.login_unico::VARCHAR = tbl_tecnico.codigo_externo or tbl_tecnico.tecnico = tbl_login_unico.tecnico)
                    WHERE tbl_login_unico.login_unico = {$id_tecnico}";
    $res_id_tecnico = pg_query($con,$get_id_tecnico);
    $msg           .= pg_last_error($con);
    
    if (pg_num_rows($res_id_tecnico) > 0){
        $id_tecnico = pg_fetch_result($res_id_tecnico,0,'tecnico');
    
   }
	 $sql = "INSERT INTO tbl_treinamento_posto(
				treinamento,
				posto,
				tecnico,
				confirma_inscricao
				{$campo_data_inscricao}
			) VALUES (
				{$id_treinamento},
				{$id_posto},
				{$id_tecnico},
				't'
				{$valor_data_inscricao}
			)";
	$res  = pg_query($con,$sql);
	$msg .= pg_last_error($con);
    
    if (strlen($msg) > 0){
        exit(json_encode(array("erro" => utf8_encode($msg))));
    }else{
        exit(json_encode(array("ok" => utf8_encode("Inscrição realizada com sucesso"))));
    }
}

// ============================== FINALIZAR TREINAMENTO/AVALIAÇÃO ============================== //
if ($_POST['ajax'] == 'sim' AND $_POST['acao'] == 'gravar_treinamento_avaliacao'){
    $id_treinamento = $_POST['treinamento'];
    $array_tecnicos = json_decode($_POST['tecnicos'], true);
    $avaliar_finalizar = $_POST['avaliar_finalizar'];

    $get_treinamento = "SELECT 
                              tbl_treinamento.validade_treinamento
                        FROM  tbl_treinamento
                        WHERE tbl_treinamento.treinamento = {$id_treinamento}
                              AND tbl_treinamento.fabrica = {$login_fabrica};";
    $res_treinamento = pg_query($con,$get_treinamento);
    $msg_erro       .= pg_last_error($con,$res_treinamento);

    if (!strlen($msg_erro) > 0){
        $validade_treinamento = pg_fetch_result($res_treinamento,0,'validade_treinamento');
        
        if ($avaliar_finalizar != "avaliar") {
            $sql_data    = "UPDATE tbl_treinamento SET
                           data_finalizado = CURRENT_TIMESTAMP
                        WHERE  treinamento     = {$id_treinamento}
                               AND fabrica     = {$login_fabrica};";
            $query_data  = pg_query($con,$sql_data);
            $msg_erro    = pg_last_error($con);
            $msg_success = "Treinamento finalizado com sucesso";
        } else {
            $msg_success = "Técnicos avaliados com sucesso";
        }

        if (!empty($array_tecnicos) && !strlen($msg_erro) > 0) {
            foreach ($array_tecnicos AS $tecnico){
                $id_tecnico     = $tecnico['tecnico'];
                $participou     = $tecnico['participou'];
                $aprovado       = $tecnico['aprovado'];

               if ($participou == 't' && $aprovado == 't'){
                    $update_participou = ", participou = '".$participou."' ";
                    $update_aprovado   = ", aprovado   = '".$aprovado."'   ";
                    
                    if (count($_FILES) > 0) {

                        $types = array("png", "jpg", "jpeg");
                        foreach ($_FILES as $key => $imagem) {
                            if ((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)) {
                                $type  = strtolower(preg_replace("/.+\//", "", $imagem["type"]));
                                
                                if (!in_array($type, $types)) {
                                    $msg_erro .= "Formato inv&aacute;lido, s&atilde;o aceitos os seguintes formatos: png, jpg, jpeg<br />";
                                    break;
                                } else {
                                    $imagem['name'] = "anexo_".$login_fabrica."_".$id_treinamento."_".$id_tecnico.".".$type."";

                                    /******** Movendo o arquivo temporáriamente  ********/
                                    $caminho     = "/tmp/anexo_{$id_treinamento}_{$id_tecnico}.{$type}";
                                    move_uploaded_file($imagem['tmp_name'], $caminho);

                                    /******** gravando no TDocs ********/
                                    $tdocsMirror = new TdocsMirror();
                                    $response    = $tdocsMirror->post($caminho);

                                    if(array_key_exists("exception", $response)){
                                        header('Content-Type: application/json');
                                        exit(json_encode(array("erro" => "Ocorreu um erro ao realizar o upload: ".$response['message'])));
                                        $msg_erro .= $response['message'];
                                    }
                                    $file = $response[0];

                                    foreach ($file as $filename => $data) {
                                        $unique_id = $data['unique_id'];
                                        $row = [array(
                                            "acao"     => "anexar",
                                            "filename" => $caminho,
                                            "data"     => date("Y-m-d\TH:i:s"),
                                            "fabrica"  => $login_fabrica
                                        )];
                                    }

                                    $array = array("treinamento" => $treinamento);
                                    $obs   = json_encode($array);

                                    $sql_verifica = "SELECT * 
                                                FROM tbl_tdocs
                                                WHERE fabrica = {$login_fabrica} 
                                                AND contexto  = 'treinamento_anexo'
                                                AND tdocs_id  = '$unique_id'";
                                    $res_verifica = pg_query($con,$sql_verifica);
                                    if (!pg_num_rows($res_verifica) > 0){
                                        $sql = "INSERT INTO tbl_tdocs(tdocs_id,fabrica,contexto,situacao,obs,referencia,referencia_id) 
                                            values('$unique_id', $login_fabrica, 'treinamento_anexo', 'ativo', '$obs', '".$id_treinamento."_".$id_tecnico."', $id_treinamento);";                
                                        $res = pg_query($con, $sql);   
                                    }
                                    /******** fim gravando no TDocs ********/

                                }
                            }
                        }
                    }

                } else{
                    $update_participou = ", participou = '".$participou."' ";
                    $update_aprovado   = ", aprovado   = '".$aprovado."'   ";
                }

                if (empty($validade_treinamento) ||  $validade_treinamento == ''){
                    $validade_treinamento = 0;
                }
                if (!strlen($msg_erro) > 0 and ($participou == 't' or $aprovado == 't')){
                    $sql_tecnico   = "UPDATE tbl_treinamento_posto SET
                                            validade_certificado = CURRENT_DATE + INTERVAL '{$validade_treinamento} month',
                                            data_avaliacao       = CURRENT_TIMESTAMP
                                            {$update_participou}
                                            {$update_aprovado}
                                    WHERE   treinamento = {$id_treinamento}
                                        AND tecnico     = {$id_tecnico}";
                    $query_tecnico = pg_query($con,$sql_tecnico);
                    $msg_erro     .= pg_last_error($con);      
                }
            }
        }    
    }
    
    if (strlen($msg_erro) > 0){
        exit(json_encode(array("erro" => utf8_encode($msg_erro))));
    }else{
        exit(json_encode(array("ok" => utf8_encode($msg_success))));
    }
exit;
}

// ============================== REMOVER TECNICO DO TREINAMENTO ============================== //
if ($_GET['ajax'] == 'sim' AND $_GET['acao'] == 'remover_tecnico'){    
    $id_treinamento = addslashes($_GET['treinamento']);
    $id_posto       = addslashes($_GET['posto']);
    $id_tecnico     = addslashes($_GET['tecnico']);

    if (empty($id_treinamento)){
        $msg .= "Informe o treinamento.";
        exit(json_encode(array("erro" => utf8_encode($msg))));
    }

    if (empty($id_posto)){
        $msg .= "Informe o Posto.";
        exit(json_encode(array("erro" => utf8_encode($msg))));
    }

    if (empty($id_tecnico)){
        $msg .= "Informe o Técnico.";
        exit(json_encode(array("erro" => utf8_encode($msg))));
    }

    $get_id_tecnico = "SELECT tbl_tecnico.tecnico 
                    FROM tbl_tecnico
                         INNER JOIN tbl_login_unico ON tbl_login_unico.login_unico::VARCHAR = tbl_tecnico.codigo_externo
                    WHERE tbl_login_unico.login_unico = {$id_tecnico}";
    $res_id_tecnico = pg_query($con,$get_id_tecnico);
    $msg           .= pg_last_error($con);

    if (pg_num_rows($res_id_tecnico) > 0){
        $id_tecnico = pg_fetch_result($res_id_tecnico,0,'tecnico');

        $sql = "DELETE FROM tbl_treinamento_posto
                WHERE tbl_treinamento_posto.treinamento = {$id_treinamento}
                AND   tbl_treinamento_posto.posto       = {$id_posto}
                AND   tbl_treinamento_posto.tecnico     = {$id_tecnico}";
        $res  = pg_query($con,$sql);
        $msg .= pg_last_error($con);  
    }
    
    if (strlen($msg) > 0){
        exit(json_encode(array("erro" => utf8_encode($msg))));
    }else{
        exit(json_encode(array("ok" => utf8_encode("Inscrição removida com sucesso"))));
    }
exit;
}

// ============================== ATIVA/DESATIVA PARTICIPOU  ============================== //
if ($_GET['ajax'] == 'sim' AND $_GET['acao'] == 'ativa_desativa_participou'){    
    $id_treinamento = addslashes($_GET['treinamento']);
    $id_posto       = addslashes($_GET['posto']);
    $id_tecnico     = addslashes($_GET['tecnico']);

    if (empty($id_treinamento)){
        $msg .= "Informe o treinamento.";
        exit(json_encode(array("erro" => utf8_encode($msg))));
    }

    if (empty($id_posto)){
        $msg .= "Informe o Posto.";
        exit(json_encode(array("erro" => utf8_encode($msg))));
    }

    if (empty($id_tecnico)){
        $msg .= "Informe o Técnico.";
        exit(json_encode(array("erro" => utf8_encode($msg))));
    }

    $get_id_tecnico = "SELECT tbl_tecnico.tecnico 
                    FROM tbl_tecnico
                         INNER JOIN tbl_login_unico ON tbl_login_unico.login_unico::VARCHAR = tbl_tecnico.codigo_externo
                    WHERE tbl_login_unico.login_unico = {$id_tecnico}";
    $res_id_tecnico = pg_query($con,$get_id_tecnico);
    $msg           .= pg_last_error($con);

    if (pg_num_rows($res_id_tecnico) > 0){
        $id_tecnico = pg_fetch_result($res_id_tecnico,0,'tecnico');

        $sql = "UPDATE tbl_treinamento_posto SET
                      participou  = TRUE
                WHERE treinamento = {$id_treinamento}
                AND   posto       = {$id_posto}
                AND   tecnico     = {$id_tecnico}";
        $res  = pg_query($con,$sql);
        $msg .= pg_last_error($con);
    }
    
    if (strlen($msg) > 0){
        exit(json_encode(array("erro" => utf8_encode($msg))));
    }else{
        exit(json_encode(array("ok" => utf8_encode("Status modificado com sucesso"))));
    }
exit;
}
