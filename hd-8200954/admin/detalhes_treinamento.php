<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include '../ajax_cabecalho.php';
include "../plugins/fileuploader/TdocsMirror.php";
$admin_privilegios="info_tecnica,call_center";
include 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';

if(array_key_exists("ajax", $_GET)){
    header("Content-Type: application/json");
    switch ($_GET['ajax']) {
        case "checkQuestionario":
            
            $sql = "SELECT tp.treinamento_posto, te.tecnico, te.nome, tp.aprovado, tp.nota_tecnico, tp.participou, r.resposta
                    FROM tbl_treinamento_posto tp
                    JOIN tbl_tecnico te USING(tecnico)
                    JOIN tbl_pesquisa tps ON tps.treinamento = tp.treinamento
                    LEFT JOIN tbl_resposta r ON r.pesquisa = tps.pesquisa AND r.tecnico = te.tecnico
                    WHERE tp.treinamento = $1 AND tp.tecnico = $2 AND te.fabrica = $3";
            $res_treinamento_posto = pg_query_params($con,$sql,array($treinamento,$tecnico, $login_fabrica));
            $res_treinamento_posto = pg_fetch_array($res_treinamento_posto);
            
            if($res_treinamento_posto['resposta'] == ""){
                echo json_encode(["respondido" => "false"]);    
            }else{
                echo json_encode(["respondido" => "true"]); 
            }
            
            break;
    }
    exit;
}

?>
<!DOCTYPE html />
<html>
<head>
<meta http-equiv=pragma content=no-cache>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<link href="plugins/shadowbox_lupa/shadowbox.css" type="text/css" rel="stylesheet" media="screen"/>
<?php if (in_array($login_fabrica, [169,170,193])) { ?>
    <link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">
<?php } ?>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="bootstrap/js/bootstrap.js"></script>
<script type="text/javascript" src="plugins/resize.js"></script>
<script type="text/javascript" src="plugins/shadowbox_lupa/lupa.js"></script>
<script type="text/javascript" src="plugins/shadowbox_lupa/shadowbox.js"></script>


<script type="text/javascript" src="plugins/jquery.mask.js"></script>
<script type="text/javascript" src="plugins/jquery.maskedinput_new.js"></script>
<script type="text/javascript">
function createRequestObject(){
    var request_;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
            request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
            request_ = new XMLHttpRequest();
    }
    return request_;
}
var http_forn = new Array();
function ativa_desativa_tecnico(treinamento,id) {
    var com = document.getElementById("tec_ativo_"+id);
    var img = document.getElementById("tec_img_ativo_"+id);
    var tr = document.getElementById("inscricao_"+treinamento);
    com.innerHTML   ="Espere...";
    var acao='ativa_desativa_tecnico';
    url = "ajax_treinamento.php?ajax=sim&acao="+acao+"&treinamento_posto="+treinamento+"&id="+id;
    var curDateTime = new Date();
    http_forn[curDateTime] = createRequestObject();
    http_forn[curDateTime].open('GET',url,true);
    http_forn[curDateTime].onreadystatechange = function(){
        if (http_forn[curDateTime].readyState == 4)
        {
            if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
            {
                var response = http_forn[curDateTime].responseText.split("|");
                if (response[0]=="ok"){
                    com.innerHTML   = response[1];
                    img.src = "imagens_admin/status_"+response[2]+".gif";
                    tr.remove();
                }
            }
        }
    }
    http_forn[curDateTime].send(null);
}
$(function(){
    $("body").on("click","button[id^=alterar_]",function(e){
        e.preventDefault();
        var botao               = $(this).attr("id");
        var aux                 = botao.split("_");
        var treinamento_posto   = aux[1];
        $.ajax({
            url:"ajax_treinamento.php",
            type:"POST",
            dataType:"html",
            data:{
                ajax:true,
                tipo:"mostraTecnico",
                treinamento_posto:treinamento_posto
            },
            beforeSend:function(){
                $("button[id^=alterar_]").each(function(){
                    $(this).prop("disabled",true);
                });
            }
        })
        .done(function(data){
            $("#inscricao_"+treinamento_posto).after(data);
            $(".txt_fone").mask("(99)99999-9999");
            $(".txt_nascimento").mask("99/99/9999");
        });
    });
    /* Função para gerar certificado */
    $(document).on('click', '.gera_certificado_convidado', function() {
        $(this).html('<center>Gerando <i class="fas fa-circle-notch fa-spin"></i></center>');
        var treinamento       = $(this).data("treinamento");
        var treinamento_posto = $(this).data("treinamento-posto");
        var tecnico = $(this).data("tecnico");
        var td                = $(this).parents("td")[0];
        var tr = $(this);

        $.ajax("detalhes_treinamento.php?ajax=checkQuestionario",{
            method: "POST",
            data:{
                treinamento: treinamento,
                treinamento_posto: treinamento_posto,
                tecnico: tecnico,
                isConvidado: true,
                returnLinkText: true    
            }
        }).done(function(response){
            if(response.respondido == "false"){
                alert("Favor acessar a tela de treinamentos realizados e responder a pesquisa de satisfação para liberação do certificado");
                $(tr).html('<center>Emitir Certificado</center>');
            }else{
                $.ajax("../gera_certificado.php",{
                  method: "POST",
                  data: {
                    treinamento: treinamento,
                    treinamento_posto: treinamento_posto,
                    isConvidado: true,
                    returnLinkText: true
                  }
                }).done(function(response){
                    response = JSON.parse(response);
                    if (response.ok !== undefined) {
                        alert('Certificado enviado para o e-mail cadastrado..');
                        $(td).html("<a target='_blank' href='"+response.ok+"'><center>Acessar Certificado</center></a>");
                    }else{
                        alert(response.error);
                        $(td).html("<a class='gera_certificado_convidado' data-treinamento='"+treinamento+"' data-treinamento-posto='"+treinamento_posto+"' style='cursor: pointer;'><center>Emitir Certificado</center></a>");
                    }
                });
            }
        });

        
    });
});
function gravaTecnico(treinamento_posto){
    var nome        = $("#txt_tecnico_nome_"+treinamento_posto).val();
    var rg          = $("#txt_tecnico_rg_"+treinamento_posto).val();
    var cpf         = $("#txt_tecnico_cpf_"+treinamento_posto).val();
    var telefone    = $("#txt_tecnico_fone_"+treinamento_posto).val();
    var email       = $("#txt_tecnico_email_"+treinamento_posto).val();
    var nascimento = $("#txt_tecnico_nasc_" + treinamento_posto).val();
    $.ajax({
        url:"ajax_treinamento.php",
        type:"POST",
        dataType:"JSON",
        data:{
            ajax:true,
            tipo:"gravaTecnico",
            treinamento_posto:treinamento_posto,
            nome:nome,
            rg:rg,
            cpf:cpf,
            nascimento: nascimento,
            telefone:telefone,
            email:email
        }
    })
    .done(function(data){
        if (data.ok) {
            alert(data.msg);
            window.location.reload();
        }
    })
    .fail(function(){
        alert("Erro ao alterar técnico");
        $("#resp_"+treinamento_posto).detach();
        $("button[id^=alterar_]").each(function(){
            $(this).prop("disabled",true);
        });
    });
}
</script>
</head>
<body>
    <div class="container-fluid form_tc" style="height:600px; overflow: auto;">
        <div class="titulo_tabela">Dados do Treinamento</div>

<?php
    if ($login_fabrica == 171) {
        $cond_cliente = "AND tbl_treinamento_posto.cliente IS NULL";
    }
    if (in_array($login_fabrica, array(169,170,193))){
        $cond_tecnico = " AND tbl_treinamento_posto.tecnico IS NOT NULL ";
    }
    $treinamento = $_GET["treinamento"];
    $cor         = $_GET["cor"];
    $relacaoLinhaTreinamento = "
        JOIN      tbl_linha   USING(linha)
        LEFT JOIN tbl_familia USING(familia) ";
    $linhaMarcaSql = " tbl_linha.nome AS linha_nome, tbl_familia.descricao  AS familia_descricao, ";
    if (in_array($login_fabrica, [1,175])) {
        $relacaoLinhaTreinamento = " LEFT JOIN tbl_produto on (tbl_produto.linha = tbl_treinamento.linha OR tbl_produto.marca = tbl_treinamento.marca) AND tbl_produto.fabrica_i = {$login_fabrica}
                LEFT JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca
                LEFT JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
                LEFT JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia
                ";
        $linhaMarcaSql = "  array_to_string(array_agg( DISTINCT tbl_marca.nome),', ') AS marca_nome,
                            array_to_string(array_agg( DISTINCT tbl_linha.nome),', ') AS linha_nome,
                            array_to_string(array_agg( DISTINCT tbl_familia.descricao),', ') AS familia_descricao,";
        if (in_array($login_fabrica, [1])) {
            $relacaoLinhaTreinamento = "";
            $linhaMarcaSql = "";        
        }
        $groupBy = " GROUP BY tbl_treinamento.treinamento,
            tbl_treinamento.titulo,
            tbl_treinamento.descricao,
            tbl_treinamento.ativo,
            tbl_treinamento.local,
            tbl_treinamento.vagas,
            tbl_treinamento.vagas_min,
            tbl_treinamento.qtde_participante,
            data_inicio,
            data_fim,
            prazo_inscricao,
            tbl_admin.nome_completo,
            qtde_postos,
            tbl_treinamento.adicional,
            tbl_treinamento.cidade,
            tbl_treinamento.visivel_portal
            ";
    }
    if (in_array($login_fabrica, array(169,170,193)))
    {
        $linhaMarcaSql .= " tbl_treinamento.data_finalizado,";
    }
    if (in_array($login_fabrica, array(175))){
        
        $campos_adicionais      = ", tbl_treinamento_tipo.nome AS treinamento_tipo ";
        $join_treinamento_tipo  = " INNER JOIN tbl_treinamento_tipo ON tbl_treinamento.treinamento_tipo = tbl_treinamento_tipo.treinamento_tipo ";
        $groupBy               .= ", tbl_treinamento_tipo.nome ";
    }
    $sql = "SELECT tbl_treinamento.treinamento,
            tbl_treinamento.titulo,
            tbl_treinamento.descricao,
            tbl_treinamento.ativo,
            tbl_treinamento.local,
            tbl_treinamento.vagas,
            tbl_treinamento.vagas_min,
            $linhaMarcaSql
            tbl_treinamento.qtde_participante,
            TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')     AS data_inicio,
            TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')        AS data_fim,
            TO_CHAR(tbl_treinamento.prazo_inscricao,'DD/MM/YYYY') AS prazo_inscricao,
            tbl_admin.nome_completo,
            (
                SELECT COUNT(*)
                FROM tbl_treinamento_posto
                WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                AND   tbl_treinamento_posto.ativo IS TRUE {$cond_cliente} $cond_tecnico
            )                                                     AS qtde_postos,
            tbl_treinamento.adicional,
            tbl_treinamento.cidade,
            tbl_treinamento.visivel_portal
            {$campos_adicionais}
        FROM tbl_treinamento
        JOIN      tbl_admin   USING(admin)
        $relacaoLinhaTreinamento
        {$join_treinamento_tipo}
        WHERE tbl_treinamento.fabrica = $login_fabrica
        AND   tbl_treinamento.treinamento = $treinamento
        {$where_data_finalizado}
        $groupBy
        ORDER BY tbl_treinamento.data_inicio,tbl_treinamento.titulo" ;
        
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        $treinamento       = trim(pg_fetch_result($res,0,'treinamento'));
        $titulo            = trim(pg_fetch_result($res,0,'titulo'));
        if (mb_check_encoding($titulo, 'UTF-8')){
            $titulo = utf8_decode($titulo);
        }
        $descricao         = trim(pg_fetch_result($res,0,'descricao'));
        $ativo             = trim(pg_fetch_result($res,0,'ativo'));
        $data_inicio       = trim(pg_fetch_result($res,0,'data_inicio'));
        $data_fim          = trim(pg_fetch_result($res,0,'data_fim'));
        $prazo_inscricao   = trim(pg_fetch_result($res,0,'prazo_inscricao'));
        $nome_completo     = trim(pg_fetch_result($res,0,'nome_completo'));
        if ($login_fabrica == 1) {
            unset($array_linha_nome);
            unset($linha_nome);
            $sql_linha = "SELECT (parametros_adicionais -> 'linha') AS linha FROM tbl_treinamento WHERE fabrica = $login_fabrica AND treinamento = $treinamento";
            $res_linha = pg_query($con, $sql_linha);
            if (pg_num_rows($res_linha) > 0) {
                $linha_sql = pg_fetch_result($res_linha, 0, 'linha');
                $linha_sql  = json_decode($linha_sql);    
                $sql_linha_nome = "SELECT nome FROM tbl_linha WHERE fabrica = $login_fabrica AND linha in (".implode(',', $linha_sql).")";
                $res_linha_nome = pg_query($con, $sql_linha_nome);
                if (pg_num_rows($res_linha_nome) > 0) {
                    for ($m=0; $m < pg_num_rows($res_linha_nome); $m++) { 
                        $array_linha_nome[] = pg_fetch_result($res_linha_nome, $m, 'nome'); 
                    }
                }
                $linha_nome = implode(',', $array_linha_nome);    
            }
        } else {
            $linha_nome      = trim(pg_fetch_result($res,$i,'linha_nome'));
        }
        $qtde_participante = trim(pg_fetch_result($res,0,'qtde_participante'));
        $familia_descricao = trim(pg_fetch_result($res,0,'familia_descricao'));
        $marca             = trim(pg_fetch_result($res,0,'marca'));
        $vagas             = trim(pg_fetch_result($res,0,'vagas'));
        $vagas_min         = trim(pg_fetch_result($res,0,'vagas_min'));
        $qtde_postos       = trim(pg_fetch_result($res,0,'qtde_postos'));
        $adicional         = trim(pg_fetch_result($res,0,'adicional'));
        $local             = trim(pg_fetch_result($res,0,'local'));
        if (mb_check_encoding($local, 'UTF-8')) {
            $local = utf8_decode($local);
        }
        $cidade            = trim(pg_fetch_result($res,0,'cidade'));
        $visivel_portal    = trim(pg_fetch_result($res,0,'visivel_portal'));
        if (in_array($login_fabrica,array(169,170,193)))
        {
            $data_finalizado = trim(pg_fetch_result($res,0,'data_finalizado'));
        }

        
        if (mb_check_encoding($titulo, 'UTF-8')) {
            $titulo = utf8_decode($titulo);
        }

        $array_resposta['Tema'] = $titulo;
                
        $array_resposta['Linha'] = htmlentities($linha_nome);

        if (in_array($login_fabrica, array(175))){
            $treinamento_tipo = pg_fetch_result($res,0,'treinamento_tipo');
        }

        if (in_array($login_fabrica, array(175))){
            $treinamento_tipo = pg_fetch_result($res,0,'treinamento_tipo');
            $array_resposta['Tipo de Treinamento'] = $treinamento_tipo;
        }

        
        if (!in_array($login_fabrica, [175])) {
            $array_resposta['Linha'] = htmlentities($linha_nome);
        }
        if(!in_array($login_fabrica,array(169,170,193))){
            if (in_array($login_fabrica, [1])) {
                unset($array_linha_nome);
                unset($marca_nome);
                $sql_marca = "SELECT (parametros_adicionais -> 'marca') AS marca FROM tbl_treinamento WHERE fabrica = $login_fabrica AND treinamento = $treinamento";
                $res_marca = pg_query($con, $sql_marca);
                if (pg_num_rows($res_marca) > 0) {
                    $marca_sql = pg_fetch_result($res_marca, 0, 'marca');
                    $marca_sql  = json_decode($marca_sql);    
                    $sql_marca_nome = "SELECT nome FROM tbl_marca WHERE fabrica = $login_fabrica AND marca in (".implode(',', $marca_sql).")";
                    $res_marca_nome = pg_query($con, $sql_marca_nome);
                    if (pg_num_rows($res_marca_nome) > 0) {
                        for ($m=0; $m < pg_num_rows($res_marca_nome); $m++) { 
                            $array_marca_nome[] = pg_fetch_result($res_marca_nome, $m, 'nome'); 
                        }
                    }
                    $marca_nome = implode(',', $array_marca_nome);    
                }
                $array_resposta['Marca'] = $marca_nome;
            } else {
                $array_resposta['Fam&iacute;lia'] = htmlentities($familia_descricao);
            }
        }else{
            if(is_numeric($qtde_participante)){
                $array_resposta['Quantidade de Participantes'] = htmlentities($qtde_participante);
            }
        }
        $array_resposta['Data de In&iacute;cio'] = $data_inicio;
        $array_resposta['Data de T&eacute;rmino'] = $data_fim;
        if ($treinamento_prazo_inscricao) {
            $array_resposta['Inscri&ccedil;&otilde;es at&eacute;'] = $prazo_inscricao;
        }

        if (in_array($login_fabrica, array(175))){
            $array_resposta['Inscri&ccedil;&otilde;es at&eacute;'] = $prazo_inscricao;
            $array_resposta['Local'] = $local;    
        }
        $array_resposta['Informa&ccedil;&otilde;es Adicionais'] = $adicional;
        if ($login_fabrica == 117) { //elgin
            $visivel_portal = ($visivel_portal == 't') ? 'Sim':'Não';
            $array_resposta['Visualizar Portal'] = htmlentities($visivel_portal);
        }
        if ($treinamento_vagas_min) {
            $array_resposta['Mínimo de Vagas'] = $vagas_min;
        }
        $array_resposta['Vagas'] = $vagas;
        $array_resposta['Inscritos'] = $qtde_postos;
        $resposta .= "<table class='table table-fixed' style='border:0px !important' >
                        <tr>
                            <td valign='top' class='span6'  border=0>";
            $resposta .= "<table  span6' >
            <tbody>";
            foreach ($array_resposta as $dt=>$dd) {
                $corLinha = ($corLinha == '#F7F5F0') ? '#F1F4FA' : '#F7F5F0';
                $resposta .= "<tr bgcolor='$corLinha'>";
                $resposta .= "<td class='span3' align='left'  border=0><b>$dt</b></td>";
                $resposta .= "<td class='span3' align='left'  border=0>$dd</td>";
                $resposta .= "</tr>";
            }
            $resposta .= "</tbody></table>";
            $resposta .= "</td><td class='descricao_detalhe span5'  border=0>";
            #$resposta .= "<div><b>Descri&ccedil;&atilde;o:</b><br>".nl2br(htmlentities($descricao))."</div></td>";
            $resposta .= "<div><b>Descri&ccedil;&atilde;o:</b><br>".nl2br(utf8_decode($descricao))."</div></td>";
            if (in_array($login_fabrica, array(175))){
                $resposta .= "</td><td class='descricao_detalhe span5'  border=0>";
                $resposta .= "<div><b>Anexo(s)</b><br>"; 
                ob_start();
                $boxUploader = array(
                    'context'   => 'treinamento',
                    'titulo'    => 'Anexo(s) Treinamento',
                    'unique_id' => $treinamento,
                    'div_id'    => $treinamento
                );
                include 'box_uploader_viewer.php'; 
                $resposta .=  ob_get_clean();
                $resposta .= "</div></td>";
            }
            if (in_array($login_fabrica, array(1,42,117))) {
                if ($login_fabrica == 117 && $cidade != "") {
                    $sql = "SELECT cidade,nome,estado from tbl_cidade where cidade = $cidade;";
                    $res = pg_query($con,$sql);
                    if(pg_num_rows($res) > 0){
                        $cidade        = pg_fetch_result($res,0,'cidade');
                        $nome_cidade   = pg_fetch_result($res,0,'nome');
                        $estado_cidade = pg_fetch_result($res,0,'estado');
                    }else{
                        $cidade = "";
                        $nome_cidade = "";
                        $estado_cidade = "";
                    }
                    $local = $local.", ".$nome_cidade." - ".$estado_cidade;
                }
                $resposta .= "<td valign='top' align='justify' bgcolor='#F1F4FA'><b>Local:</b><br>".htmlentities($local)."</td>";
            }
            $resposta .= "</tr>";
            $resposta .= "</table>";
        $resposta .= "</td></tr>";
        $resposta .= "</table>";
    }
    if (in_array($login_fabrica, [169,170,175,193])) {
        $select_aprovado        = " tbl_treinamento_posto.aprovado, ";
    }

    if (in_array($login_fabrica, array(169,170,193)))
    {
        $select_data_finalizado = " , tbl_treinamento.data_finalizado ";
        $join_data_finalizao    = " LEFT JOIN tbl_treinamento ON tbl_treinamento.treinamento = tbl_treinamento_posto.treinamento ";
        $select_tipo_tecnico    = " tbl_tecnico.tipo_tecnico, ";
        $select_tipo_tecnico   .= " tbl_tecnico.dados_complementares, ";
    }else{
        $select_data_finalizado = "";
        $join_data_finalizao    = "";
        $where_data_finalizado  = "";
    }
    
    $sql = "SELECT  tbl_treinamento_posto.treinamento_posto,
                    tbl_tecnico.nome                    AS tecnico_nome,
                    tbl_tecnico.rg                      AS tecnico_rg,
                    tbl_tecnico.cpf                     AS tecnico_cpf,
                    tbl_tecnico.email                   AS tecnico_email,
                    tbl_tecnico.telefone                AS tecnico_fone,
                    tbl_tecnico.celular                 AS tecnico_celular,
                    tbl_tecnico.calcado                 AS tecnico_calcado,
                    tbl_tecnico.tipo_sanguineo          AS tecnico_tipo_sanguineo,
                    tbl_tecnico.doencas                 AS tecnico_doencas,
                    tbl_tecnico.medicamento             AS tecnico_medicamento,
                    TO_CHAR(tbl_tecnico.data_nascimento,'DD/MM/YYYY') AS tecnico_nascimento,
                    tbl_tecnico.necessidade_especial    AS tecnico_necessidade_especial,
                    tbl_treinamento_posto.ativo,
                    tbl_treinamento_posto.hotel,
                    tbl_treinamento_posto.participou,
                    {$select_aprovado}
                    {$select_tipo_tecnico}
                    tbl_treinamento_posto.confirma_inscricao,
                    tbl_treinamento_posto.promotor,
                    tbl_treinamento_posto.motivo_cancelamento AS motivo,
                    TO_CHAR(tbl_treinamento_posto.data_inscricao,'DD/MM/YYYY') AS data_inscricao,
                    TO_CHAR(tbl_treinamento_posto.data_inscricao,'HH24:MI:SS') AS hora_inscricao,
                    tbl_posto.nome                                             AS posto_nome,
                    tbl_posto.estado,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto_fabrica.contato_email AS posto_email,
                    tbl_posto_fabrica.credenciamento AS posto_credenciamento,
                    tbl_promotor_treinamento.nome,
                    tbl_treinamento_posto.observacao    AS observacao_antigo,
                    tbl_treinamento_posto.tecnico       AS tecnico,
                    tbl_treinamento_posto.tecnico_nome  AS tecnico_nome_antigo,
                    tbl_treinamento_posto.tecnico_rg    AS tecnico_rg_antigo,
                    tbl_treinamento_posto.tecnico_cpf   AS tecnico_cpf_antigo,
                    tbl_treinamento_posto.tecnico_email AS tecnico_email_antigo,
                    tbl_treinamento_posto.tecnico_fone  AS tecnico_fone_antigo,
                    tbl_treinamento_posto.nota_tecnico  AS nota_tecnico
                    {$select_data_finalizado}
               FROM tbl_treinamento_posto
                    {$join_data_finalizao}
          LEFT JOIN tbl_promotor_treinamento USING(promotor_treinamento)
          LEFT JOIN tbl_posto USING(posto)
          LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto       = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
          LEFT JOIN tbl_admin         ON tbl_treinamento_posto.admin   = tbl_admin.admin
          LEFT JOIN tbl_tecnico       ON tbl_treinamento_posto.tecnico = tbl_tecnico.tecnico
              WHERE tbl_treinamento_posto.treinamento = $treinamento
                AND tbl_treinamento_posto.ativo IS TRUE $cond_cliente $cond_tecnico
                {$where_data_finalizado}
           ORDER BY tbl_posto.nome" ;
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        //$resposta  .=  "<table class='table table-striped table-fixed formulario' >";
        $colIns     = (in_array($login_fabrica, [175])) ? '1' : '2';
        $resposta  .=  "<table border='0' cellpadding='0' cellspacing='0' class='table table-striped table-fixed'  align='center' width='700px'>";
        $resposta  .=  "<thead>";
        $resposta  .=  "<TR class='titulo_coluna'  height='25'>";
        if (in_array($login_fabrica, array(169,170,193))){
            $resposta  .=  "<th>Posto/Convidado</th>";
        }else{
            $resposta  .=  "<th>Posto</th>";
        }
        $resposta  .=  "<th width='25'>UF</th>";
        $resposta  .=  "<th>Informações do T&eacute;cnico</th>";
        if ($adicional) $resposta .= "<th WIDTH=110>".htmlentities($adicional)."</th>";
        if($login_fabrica == 20) $resposta  .=  "<th width='80'>Promotor</th>";
        $resposta  .=  "<th >Data</th>";
        $resposta  .=  "<th width='60' colspan='$colIns'>Inscri&ccedil;&atilde;o</th>";
        if (!in_array($login_fabrica, [175])) {
            $resposta  .=  "<th width='60' colspan='2'>Confirmado<br> por email</th>";
        }
        if (!in_array($login_fabrica, [1,117,169,170,175,193])) {
            $resposta  .=  "<th width='60' colspan='2'>Hotel</th>";
        }
        if (!in_array($login_fabrica, [175])) {
            $resposta  .=  "<th width='60' colspan='2'>Presente</th>";
        }
        if(in_array($login_fabrica, array(169,170,193))){
            $resposta  .=  "<th width='60' colspan>Nota</th>";
            $resposta  .=  "<th width='20' colspan>Aprovado</th>";
            $resposta .= "<th width='60' colspan>Ver Histórico de treinamentos</th>";
        }
        if (in_array($login_fabrica, [175])) {
            $resposta  .=  "<th width='20' colspan>Aprovado</th>";
        }
        if (!in_array($login_fabrica, [175])) {
            $resposta  .=  "<th >Motivo Cancelamento</th>";
        }
        if(in_array($login_fabrica, array(169,170,193))){
            $resposta  .=  "<th>Gerar Certificados</th>";
        }
        $resposta  .=  "</TR>";
        $resposta  .=  "</thead>";
        for ($i=0; $i<pg_num_rows($res); $i++){
            $treinamento_posto = trim(pg_fetch_result($res,$i,'treinamento_posto'));
            $tecnico      = trim(pg_fetch_result($res,$i,'tecnico'));
            // Retirada a codificação estava aparecendo caracteres decodificados no campo tecnico_nome
            $tecnico_nome      = trim(pg_fetch_result($res,$i,'tecnico_nome'));
            if($tecnico_nome == "" and trim(pg_fetch_result($res,$i,'tecnico_nome_antigo')) != ""){
                $tecnico_nome  = trim(pg_fetch_result($res,$i,'tecnico_nome_antigo'));
                $tecnico_rg    = trim(pg_fetch_result($res,$i,'tecnico_rg_antigo'));
                $tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone_antigo'));
                $tecnico_cpf   = trim(pg_fetch_result($res,$i,'tecnico_cpf_antigo'));
                $tecnico_email = trim(pg_fetch_result($res,$i,'tecnico_email_antigo'));
                $tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone_antigo'));
            }else{
                $tecnico_rg    = trim(pg_fetch_result($res,$i,'tecnico_rg'));
                $tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone'));
                $tecnico_cpf   = trim(pg_fetch_result($res,$i,'tecnico_cpf'));
                $tecnico_email = trim(pg_fetch_result($res,$i,'tecnico_email'));
                $tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone'));
            }
            $tecnico_tipo_sanguineo       = trim(pg_fetch_result($res,$i,'tecnico_tipo_sanguineo'));
            $tecnico_calcado              = trim(pg_fetch_result($res,$i,'tecnico_calcado'));
            $tecnico_celular              = trim(pg_fetch_result($res,$i,'tecnico_celular'));
            $tecnico_doencas              = trim(pg_fetch_result($res,$i,'tecnico_doencas'));
            $tecnico_medicamento          = trim(pg_fetch_result($res,$i,'tecnico_medicamento'));
            $tecnico_necessidade_especial = trim(pg_fetch_result($res,$i,'tecnico_necessidade_especial'));
            $tecnico_nascimento = pg_fetch_result($res, $i, 'tecnico_nascimento');
            $motivo                       = trim(pg_fetch_result($res,$i,'motivo'));
            $data_inscricao               = trim(pg_fetch_result($res,$i,'data_inscricao'));
            $hora_inscricao               = trim(pg_fetch_result($res,$i,'hora_inscricao'));
            $posto_nome                   = trim(pg_fetch_result($res,$i,'posto_nome'));
            $estado                       = trim(pg_fetch_result($res,$i,'estado'));
            $codigo_posto                 = trim(pg_fetch_result($res,$i,'codigo_posto'));
            $ativo                        = trim(pg_fetch_result($res,$i,'ativo'));
            $hotel                        = trim(pg_fetch_result($res,$i,'hotel'));
            $participou                   = trim(pg_fetch_result($res,$i,'participou'));
            $promotor                     = trim(pg_fetch_result($res,$i,'promotor'));
            $confirma                     = trim(pg_fetch_result($res,$i,'confirma_inscricao'));
            $nome                         = trim(pg_fetch_result($res,$i,'nome'));
            $observacao                   = trim(pg_fetch_result($res,$i,'observacao'));
            $nota_tecnico                 = trim(pg_fetch_result($res,$i,'nota_tecnico'));
            if (mb_check_encoding($tecnico_nome, 'UTF-8')){
                $tecnico_nome = utf8_decode($tecnico_nome);
            }
            $posto_email                   = trim(pg_fetch_result($res,$i,'posto_email'));
            $posto_credenciamento          = trim(pg_fetch_result($res,$i,'posto_credenciamento'));
            if (in_array($login_fabrica, [169,170,175,193])) {
                $aprovado        = trim(pg_fetch_result($res,$i,'aprovado'));
                $x_aprovado      = ($aprovado == 't') ? "Sim" : "Não";
            }

            if (in_array($login_fabrica, array(169,170,193)))
            {
                $data_finalizado = trim(pg_fetch_result($res,$i,'data_finalizado'));
                $tipo_tecnico    = trim(pg_fetch_result($res,$i,'tipo_tecnico'));
                if ($tipo_tecnico == 'TF'){
                    $dados_complementares = json_decode(pg_fetch_result($res,$i,'dados_complementares'), true);
                    $tecnico_empresa      = $dados_complementares['empresa'];
                    $posto_convidado_empresa = $tecnico_empresa;
                }else{
                    $posto_convidado_empresa = $codigo_posto." - ".$posto_nome;
                }
            }
            if($ativo == 't'){
                $ativo   = "<img src='imagens_admin/status_verde.gif' id='tec_img_ativo_$i'>";
                if (in_array($login_fabrica, array(169,170,175,193)))
                {
                    $x_ativo = "Cancelar";
                }else
                {
                    $x_ativo = "Confirmado";
                }
            }
            else{
                $ativo = "<img src='imagens_admin/status_vermelho.gif' id='tec_img_ativo_$i'>";
                $x_ativo = "Cancelado";
            }
            if($participou == 't'){
                $participou = "<img src='imagens_admin/status_verde.gif' id='participou_img_$i'>";
                $x_participou = "Sim";
            }
            else{
                $participou = "<img src='imagens_admin/status_vermelho.gif' id='participou_img_$i'>";
                $x_participou = "Não";
            }
            if ($confirma == 't') {
                $confirma = "<img src='imagens_admin/status_verde.gif' id='confirma_img_$i'>";
                $x_confirma = "Sim";
            } else {
                $confirma = "<img src='imagens_admin/status_vermelho.gif' id='confirma_img_$i'>";
                $x_confirma = "Não<br><a href='treinamento_cadastro.php?treinamento_posto=$treinamento_posto&ajax=enviar'>Enviar</a>";
            }
            if (!in_array($login_fabrica, [1,117,169,170,193])) {
                if($hotel == 't'){
                    $hotel = "<img src='imagens_admin/status_verde.gif' id='hotel_img_$i'>";
                    $x_hotel = "Sim";
                }
                else{
                    $hotel = "<img src='imagens_admin/status_vermelho.gif' id='hotel_img_$i'>";
                    $x_hotel = "Não";
                }
            }
            if($cor=="#F1F4FA")$cor = '#F7F5F0';
            else               $cor = '#F1F4FA';
            $resposta  .=  "<TR class='Conteudo' id='inscricao_$treinamento_posto' bgcolor='$cor'>";
            switch ($login_fabrica) {
                case 1:
                    $resposta  .=  "<TD align='left'nowrap>$codigo_posto - $posto_nome <br>
                                    <b>$posto_credenciamento</b></TD>";
                    break;
                
                case 169:
                case 170:
                case 193:
                    $resposta  .= "<TD align='left'>".$posto_convidado_empresa."</TD>";
                    
                    break;
                default:
                    $resposta  .=  "<TD align='left'>$codigo_posto - $posto_nome </TD>";
                    break;
            }
            $resposta  .=  "<TD align='center'nowrap>$estado</TD>";
            switch ($login_fabrica) {
                case 1:
                    $resposta  .=  "<TD align='left'nowrap>
                                    <b>Nome: </b>$tecnico_nome <br>
                                    <b>RG:</b> $tecnico_rg<br>
                                    <b>CPF:</b> $tecnico_cpf<br>
                                    <b>Nascimento:</b> $tecnico_nascimento<br>
                                    <b>Celular:</b> $tecnico_celular<br>
                                    <b>E-mail:</b> $tecnico_email<br>
                                    <button type='button' class='btn btn-link' id='alterar_$treinamento_posto'>[Alterar dados]</button>
                                </TD>";
                    break;
                case 42:
                    $resposta  .=  "<TD align='left'nowrap>
                                <b>Nome: </b>".htmlentities($tecnico_nome)." <br>
                                <b>E-mail:</b> $tecnico_email<br>
                                <b>RG:</b> $tecnico_rg<br>
                                <b>CPF:</b> $tecnico_cpf<br>
                                <b>Fone:</b> $tecnico_fone<br>
                                <b>Celular:</b> $tecnico_celular<br>
                                <b>Tipo Sangu&iacute;neo:</b> $tecnico_tipo_sanguineo<br>
                                <b>N&ord; do Calçado:</b> $tecnico_calcado<br>
                                <b>O Participante sofreu ou sofre de alguma doença? - </b> $tecnico_doencas<br>
                                <b>Toma algum medicamento controlado? Qual? - </b> $tecnico_medicamento<br>
                                <b>&Eacute; portador de alguma necessidade especial? Qual? - </b> ".htmlentities($tecnico_necessidade_especial)."<br>
                                <button type='button' class='btn btn-link' id='alterar_$treinamento_posto'>[Alterar dados]</button>
                            </TD>";
                    break;
                case 148:
                    $resposta  .=  "<TD align='left'nowrap>
                                    <b>Nome: </b>$tecnico_nome <br>
                                    <b>RG:</b> $tecnico_rg<br>
                                    <b>CPF:</b> $tecnico_cpf<br>
                                    <b>Fone:</b> $tecnico_fone<br>
                                    <b>E-mail:</b> $posto_email<br>
                                    <button type='button' class='btn btn-link' id='alterar_$treinamento_posto'>[Alterar dados]</button>
                                </TD>";
                    break;
                default:
                    $resposta  .=  "<TD align='left'nowrap>
                                    <b>Nome: </b>$tecnico_nome <br>
                                    <b>RG:</b> $tecnico_rg<br>
                                    <b>CPF:</b> $tecnico_cpf<br>
                                    <b>Fone:</b> $tecnico_fone<br>
                                    <button type='button' class='btn btn-link' id='alterar_$treinamento_posto'>[Alterar dados]</button>
                                </TD>";
                    break;
            }
            if ($adicional) $resposta .= "<TD>$observacao</TD>";
            if($login_fabrica == 20){
                $resposta  .=  "<TD align='left'>";
                if(strlen($nome)>0) $resposta  .=  "$nome";
                else                $resposta  .=  "$promotor";
            }
            $resposta  .=  "</TD>";
            $resposta  .=  "<TD align='center' class='texto-centro'>$data_inscricao <br> $hora_inscricao</TD>";
            if (in_array($login_fabrica, array(169,170,193)) && $data_finalizado != '' || $data_finalizado != NULL)
            {
                $resposta  .=  "<TD align='center' width='30'></TD>";
                $resposta  .=  "<TD align='center'>$x_ativo</TD>";
            }else
            {
                if (!in_array($login_fabrica, [175])) {
                    $resposta  .=  "<TD align='center'>$ativo</TD>";
                }

                $resposta  .=  "<TD align='center' width='60' title='Inscri&ccedil;&atilde;o?'><div id='tec_ativo_$i' class='texto-centro'><a href='javascript:if (confirm(\"Deseja cancelar esta inscrição?\") == true) {ativa_desativa_tecnico(\"$treinamento_posto\",\"$i\")}'>$x_ativo</a></div></TD>";
            }
            if (in_array($login_fabrica, array(169,170,193)) && $data_finalizado != '' || $data_finalizado != NULL)
            {
                $resposta  .=  "<TD align='center' width='30'></TD>";
                $resposta  .=  "<TD align='center'>$confirma</TD>";
            }else
            {
                if (!in_array($login_fabrica, [175])) {
                    $resposta  .=  "<TD align='center'>$confirma</TD>";
                    $resposta  .=  "<TD align='center' width='60'title='Confirmado inscri&ccedil;&atilde;o por email?'><div id='confirma_$i'>$x_confirma</div></TD>";
                }
            }
            if ($login_fabrica == 20){
                $resposta  .=  "<TD align='center'>$hotel</TD>";
                $resposta  .=  "<TD align='center' width='60' title='Agendar Hotel?'><div id='hotel_$i'>$x_hotel</div></TD>";
            }else{
                if (!in_array($login_fabrica, [1,117,169,170,175,193])) {
                    $resposta  .=  "<TD align='center'>$hotel</TD>";
                    $resposta  .=  "<TD align='center' width='60' title='Agendar Hotel?'><div id='hotel_$i'><a href=\"javascript:ativa_desativa_hotel('$treinamento_posto','$i')\">$x_hotel</a></div></TD>";
                }
            }
            if (in_array($login_fabrica, array(169,170,193))  && $data_finalizado != '' || $data_finalizado != NULL) {
                $resposta  .=  "<TD align='center' width='30'></TD>";
                $resposta  .=  "<TD align='center'>$x_participou</TD>";
            } else {
                if (!in_array($login_fabrica, [175])) {
                    $resposta  .=  "<TD align='center'>$participou</TD>";
                    $resposta  .=  "<TD align='center' width='60' title='Esteve presente no treinamento?'><div id='participou_$i'><a href='javascript:ativa_desativa_participou(\"$treinamento_posto\",\"$i\")'>$x_participou</a></div></TD>";
                }
            }
            if(in_array($login_fabrica, array(169,170,193))){
                $resposta  .= "<td align='center'><input type='text' name='nota' readonly data-treinamento-posto='$treinamento_posto' class='input-nota' value='$nota_tecnico'/></td>";
                $resposta  .= "<td align='center'>$x_aprovado</td>";
                $resposta  .= "<td align='center'><a href='historico_treinamento_tecnico.php?treinamento=".$treinamento."&tecnico=".$tecnico."'>Ver Histórico</a></td>";
            }
            if (in_array($login_fabrica, [175])) {
                $resposta  .= "<td align='center' class='texto-centro'>$x_aprovado</td>";
            }
            if (!in_array($login_fabrica, [175])) {
                $resposta  .= "<td>$motivo</td>";
            }
            if(in_array($login_fabrica, array(169,170,193))){
                 // obtendo certificado do técnico
                    $sql_tdocs    = "SELECT tdocs_id
                                FROM tbl_tdocs 
                                    WHERE fabrica = {$login_fabrica}
                                AND contexto      = 'gera_certificado'
                                AND referencia    = 'gera_certificado'
                                AND referencia_id = {$tecnico}
                                AND json_field('treinamento',obs) = '{$treinamento}'";
                    $res_tdocs    = pg_query($con,$sql_tdocs);
                    if (pg_numrows($res_tdocs) > 0){
                        $unique_id = pg_fetch_result($res_tdocs, 0, 'tdocs_id');
                        $tdocsMirror      = new TdocsMirror();
                        $resposta_t       = $tdocsMirror->get($unique_id);
                        $link_certificado = $resposta_t["link"];
                        $resposta        .= "<td><a target='_blank' href='".$link_certificado."' style='cursor: pointer; text-align: center;'><center>Acessar Certificado</center></a></td>";               
                    } else {                                            
                        $resposta  .= "<td><a class='gera_certificado_convidado' data-tecnico='".$tecnico."' data-treinamento='".$treinamento."' data-treinamento-posto='".$treinamento_posto."' style='cursor: pointer; text-align: center;'><center>Emitir Certificado</center></a></td>";

                    }
            }
            $resposta  .=  "</TR>";
        }
        $resposta .= " </TABLE>";
    }else{
        if($qtde_postos == 0)   {
            $resposta .= "<b> Nenhum posto fez a inscri&ccedil;&atilde;o de seu t&eacute;cnico para participar do treinamento</b>";
        }
    }
    if ($login_fabrica <> 171){
        echo $resposta."<p>";
    }
    if ($login_fabrica == 171) {
        $sql = "SELECT
                    tbl_treinamento_posto.tecnico_nome,
                    tbl_treinamento_posto.tecnico_rg,
                    tbl_treinamento_posto.tecnico_cpf,
                    tbl_treinamento_posto.tecnico_email,
                    tbl_treinamento_posto.tecnico_fone
               FROM tbl_treinamento_posto
              WHERE tbl_treinamento_posto.treinamento = $treinamento
                AND tbl_treinamento_posto.ativo IS TRUE AND tbl_treinamento_posto.cliente IS NOT NULL" ;
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $resposta  =  "<table border='0' cellpadding='0' cellspacing='0' class='table table-striped table-fixed'  align='center' width='700px'>";
            $resposta  .= "<thead>";
            $resposta  .= "<tr class='titulo_coluna'>";
            $resposta  .= "<th>Cliente</th>";
            $resposta  .= "<th>Nome</th>";
            $resposta  .= "<th>CPF</th>";
            $resposta  .= "<th>RG</th>";
            $resposta  .= "<th>Email</th>";
            $resposta  .= "<th>Telefone</th>";
            $resposta  .= "</tr>";
            $resposta  .= "</thead>";
            $resposta  .= "<tbody>";
            for ($i = 0; $i < pg_num_rows($res); $i++){
                $resposta  .= "<tr>";
                $resposta  .= "<td>";
                $resposta  .= "</td>";
                $resposta  .= "<td>";
                $resposta  .= pg_fetch_result($res, $i, 'tecnico_nome');
                $resposta  .= "</td>";
                $resposta  .= "<td>";
                $resposta  .= pg_fetch_result($res, $i, 'tecnico_cpf');
                $resposta  .= "</td>";
                $resposta  .= "<td>";
                $resposta  .= pg_fetch_result($res, $i, 'tecnico_rg');
                $resposta  .= "</td>";
                $resposta  .= "<td>";
                $resposta  .= pg_fetch_result($res, $i, 'tecnico_email');
                $resposta  .= "</td>";
                $resposta  .= "<td>";
                $resposta  .= pg_fetch_result($res, $i, 'tecnico_fone');
                $resposta  .= "</td>";
                $resposta  .= "</tr>";
            }
            $resposta  .= "</tbody>";
            $resposta  .= "</table>";
        }
        echo $resposta;
    }
    //exit;
?>
</div>


<?php
if(in_array($login_fabrica, array(169,170,193))){
    ?>
    <div class="container-fluid">
        <div class="row-fluid env-tdocs-uploads">

        </div>
    </div>
    <?php
}
?>


<style type="text/css">
    .texto-centro {
        text-align: center !important;
    }
    .input-nota{
        width: 80px;
        text-align: center;
    }
    .update-input-success{
        background: #afffa3 !important;
    }
</style>
<script type="text/javascript">
    function ativa_desativa_participou(treinamento,id) {
        var com = document.getElementById("participou_"+id);
        var img = document.getElementById("participou_img_"+id);
        com.innerHTML   ="Espere...";
        var acao='ativa_desativa_participou';
        url = "ajax_treinamento.php?ajax=sim&acao="+acao+"&treinamento_posto="+treinamento+"&id="+id;
        var curDateTime = new Date();
        http_forn[curDateTime] = createRequestObject();
        http_forn[curDateTime].open('GET',url,true);
        http_forn[curDateTime].onreadystatechange = function(){
            if (http_forn[curDateTime].readyState == 4)
            {
                if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
                {
                    var response = http_forn[curDateTime].responseText.split("|");
                    if (response[0]=="ok"){
                        com.innerHTML   = response[1];
                        img.src = "imagens_admin/status_"+response[2]+".gif";
                        <?php if (in_array($login_fabrica, [175])) { ?>
                             window.location.reload();
                        <?php } ?>
                    }
                }
            }
        }
        http_forn[curDateTime].send(null);
    }
    <?php
    if(in_array($login_fabrica, array(169,170,193))){
    ?>
    var tdocs_uploader_url = "plugins/fileuploader/fileuploader-iframe.php?context=treinamento&reference_id=<?=$treinamento ?>&no_hash=true";
    var updateEnvTdocs = function(){
        var tokens = [];
        $.ajax(tdocs_uploader_url+"&ajax=get_tdocs").done(function(response){
            console.log(response.length);
            if(response.length > 0){
                var p = $('<p>Carregando Arquivos...</p>');
                $(".env-tdocs-uploads").append(p);
                setTimeout(function(){
                    $(p).fadeOut(1000);
                },10000);
            }
            $(response).each(function(idx,elem){
                tokens.push(elem.tdocs_id);
                if($("#"+elem.tdocs_id).length == 0){
                    var div = $("<div class='env-img' style='display: none'>");
                    $(".env-tdocs-uploads").append(div);
                    elem.obs = JSON.parse(elem.obs);
                    console.log(elem);
                    loadImage(elem.tdocs_id,function(responseTdocs){
                        $(div).html("");
                        $(div).attr("id",elem.tdocs_id);
                        var img = $("<img class=''>");
                        if(responseTdocs.fileType == 'image'){
                            $(img).attr("src",responseTdocs.link);
                            var span = $("<span>"+responseTdocs.file_name+" - "+elem.obs[0].typeName+"</span>")
                            var a = $("<a target='_BLANK'>");
                            $(a).attr("href",responseTdocs.link);
                            $(a).append(span);
                            $(div).append(a);
                        }else{
                            $(img).attr("src","plugins/fileuploader/file-placeholder.png");
                            var span = $("<span>"+responseTdocs.file_name+"</span>")
                            var a = $("<a target='_BLANK'>");
                            $(a).attr("href",responseTdocs.link);
                            $(a).append(span);
                            $(div).append(a);
                        }
                        $(div).prepend(img);
                        setTimeout(function(){
                            $(div).fadeIn(1500);
                        },1000);
                    });
                }
            });
            setTimeout(function(){
                $(".env-img").each(function(idx,elem){
                    var id = $(elem).attr("id");
                    if($.inArray(id,tokens) == -1){
                        $("#"+id).fadeOut(1500,function(){
                            $("#"+id).remove();
                        });
                    }
                });
            },3000);
        });
    }
    function loadImage(uniqueId, callback){
        $.ajax("plugins/fileuploader/fileuploader-iframe.php?loadTDocs="+uniqueId).done(callback);
    }
    updateEnvTdocs();
    <?php
    }
    ?>
</script>
<style type="text/css">
    .env-tdocs-uploads{
        /*padding-top: 10px;*/
    }
    .env-tdocs-uploads > p{
        text-align: center;
        font-size: 29px;
    }
    .env-img {
        padding-top: 3px;
        float: left;
        width: 20%;
        /*margin-bottom: 17px;*/
        min-height: 130px;
        max-height: 130px;
        overflow: hidden;
        text-align: center;
        cursor: pointer;
        background: #fff;
        transition: all ease-in-out .3s;
    }
    .env-img > img {
        height: 80px !important;
        display: block;
        margin: 0 auto;
    }
    .env-img:hover{
        transform: scale(1.2);
        transition: all ease-in-out .3s;
        background: #e2e2e2;
        border-radius: 3px;
    }
    .env-img > span {
        display: block;
        margin-top: 5px;
    }
</style>
</body>
</html>