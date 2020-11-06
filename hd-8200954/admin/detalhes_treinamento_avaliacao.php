<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include '../ajax_cabecalho.php';

$admin_privilegios="info_tecnica,call_center";
include 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';

?>
<!DOCTYPE HTML />
<html>
<head>
<meta http-equiv=pragma content=no-cache>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<link href="plugins/shadowbox_lupa/shadowbox.css" type="text/css" rel="stylesheet" media="screen"/>

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

$(function(){
    Shadowbox.init();
    $(document).on('click', 'a.detalhes_treinamento', function(){
        var url = $(this).data('url');
        Shadowbox.open({
            content: url,
            player: 'iframe',
            width: 1224,
            height: 600
        });
    });
});
</script>
</head>
<body>
    <div class="container-fluid form_tc" style="height:600px; overflow: auto;">
        <div class="titulo_tabela">Dados do Treinamento</div>

<?php

    $treinamento           = $_GET["treinamento"];
    $avaliar_finalizar     = $_GET["acao"];
    $cor                   = $_GET["cor"];


    $sql = "SELECT DISTINCT
                tbl_treinamento.treinamento,
                tbl_treinamento_tipo.nome AS treinamento_tipo,
                tbl_treinamento.data_input,                
                tbl_treinamento.titulo,
                tbl_treinamento.descricao,
                tbl_treinamento.ativo,
                tbl_treinamento.local,
                tbl_treinamento.vagas,
                tbl_treinamento.vagas_min,
                tbl_admin.nome_completo,
                tbl_treinamento.qtde_participante,
                tbl_treinamento.adicional,
                tbl_treinamento.cidade,
                tbl_treinamento.visivel_portal,
                tbl_treinamento.data_finalizado,
                TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')      AS data_inicio,
                TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')         AS data_fim,
                TO_CHAR(tbl_treinamento.inicio_inscricao,'DD/MM/YYYY') AS inicio_inscricao,
                TO_CHAR(tbl_treinamento.prazo_inscricao,'DD/MM/YYYY')  AS prazo_inscricao,
                ARRAY_TO_STRING(array_agg(DISTINCT(tbl_linha.nome)), ', ', null)         AS linhas,
                ARRAY_TO_STRING(array_agg(DISTINCT(tbl_produto.descricao)), ', ', null)     AS produtos,
                (
                    SELECT COUNT(*)
                    FROM tbl_treinamento_posto
                    WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                    AND   tbl_treinamento_posto.ativo IS TRUE
                    $cond_tecnico
                )                                                      AS qtde_postos
            FROM tbl_treinamento
                LEFT JOIN tbl_treinamento_produto ON tbl_treinamento_produto.treinamento = tbl_treinamento.treinamento    
                LEFT JOIN tbl_linha               ON tbl_linha.linha                     = tbl_treinamento_produto.linha
                LEFT JOIN tbl_produto             ON tbl_produto.produto                 = tbl_treinamento_produto.produto
                     JOIN tbl_admin               ON tbl_admin.admin = tbl_treinamento.admin
                     INNER JOIN tbl_treinamento_tipo ON tbl_treinamento_tipo.treinamento_tipo = tbl_treinamento.treinamento_tipo
            WHERE   tbl_treinamento.fabrica     = {$login_fabrica}
                AND tbl_treinamento.treinamento = {$treinamento}
            GROUP BY
                tbl_treinamento.treinamento,
                tbl_treinamento_tipo.nome,
                tbl_treinamento.data_input,                
                tbl_treinamento.titulo,
                tbl_treinamento.descricao,
                tbl_treinamento.ativo,
                tbl_treinamento.local,
                tbl_treinamento.vagas,
                tbl_treinamento.vagas_min,
                tbl_admin.nome_completo,
                tbl_treinamento.qtde_participante,
                tbl_treinamento.adicional,
                tbl_treinamento.cidade,
                tbl_treinamento.visivel_portal,
                tbl_treinamento.data_finalizado,
                data_inicio,
                data_fim,
                inicio_inscricao,
                prazo_inscricao;";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        
        $treinamento       = trim(pg_fetch_result($res,0,'treinamento'));
        $treinamento_tipo  = trim(pg_fetch_result($res,0,'treinamento_tipo'));
        $titulo            = trim(utf8_encode(pg_fetch_result($res,0,'titulo')));
        $descricao         = trim(utf8_encode(pg_fetch_result($res,0,'descricao')));
        $ativo             = trim(pg_fetch_result($res,0,'ativo'));
        $data_inicio       = trim(pg_fetch_result($res,0,'data_inicio'));
        $data_fim          = trim(pg_fetch_result($res,0,'data_fim'));
        $inicio_inscricao  = trim(pg_fetch_result($res,0,'inicio_inscricao'));
        $prazo_inscricao   = trim(pg_fetch_result($res,0,'prazo_inscricao'));
        $nome_completo     = trim(pg_fetch_result($res,0,'nome_completo'));
        $linhas            = trim(pg_fetch_result($res,0,'linhas'));
        $produtos          = trim(pg_fetch_result($res,0,'produtos'));
        $qtde_participante = trim(pg_fetch_result($res,0,'qtde_participante'));
        $familia_descricao = trim(pg_fetch_result($res,0,'familia_descricao'));
        $marca             = trim(pg_fetch_result($res,0,'marca'));
        $vagas             = trim(pg_fetch_result($res,0,'vagas'));
        $vagas_min         = trim(pg_fetch_result($res,0,'vagas_min'));
        $qtde_postos       = trim(pg_fetch_result($res,0,'qtde_postos'));
        $adicional         = trim(pg_fetch_result($res,0,'adicional'));
        $local             = trim(pg_fetch_result($res,0,'local'));
        $cidade            = trim(pg_fetch_result($res,0,'cidade'));
        $visivel_portal    = trim(pg_fetch_result($res,0,'visivel_portal'));
        $data_finalizado   = trim(pg_fetch_result($res,0,'data_finalizado'));


        $array_resposta['Tema']           = utf8_decode($titulo);
        $array_resposta['Linhas']         = $linhas;
        $array_resposta['Produtos']       = $produtos;
        $array_resposta['Fam&iacute;lia'] = htmlentities($familia_descricao);

        $array_resposta['Data de In&iacute;cio']  = $data_inicio;
        $array_resposta['Data de T&eacute;rmino'] = $data_fim;
        if (!empty($inicio_inscricao)) {
            $array_resposta['In&iacute;cio Inscri&ccedil;&otilde;es']  = $inicio_inscricao;
        }
        if (!empty($prazo_inscricao)) {
            $array_resposta['T&eacute;rmino Inscri&ccedil;&otilde;es'] = $prazo_inscricao;
        }
        $array_resposta['Informa&ccedil;&otilde;es Adicionais'] = $adicional;
        if ($treinamento_vagas_min) {
            $array_resposta['Mínimo de Vagas'] = $vagas_min;
        }
        $array_resposta['Vagas']     = $vagas;
        $array_resposta['Inscritos'] = $qtde_postos;

        if (empty($data_finalizado) || $treinamento_tipo == 'Online'){
            $button_show = "<button type='button' name='gravar_treinamento' id='gravar_treinamento' class='btn btn-success'>Gravar</button> ";
        }else{
            $resposta .= "<div class='alert alert-danger' style='padding-bottom: 20px; padding-top: 20px;'>
                        <h4>Treinamento já finalizado</h4>
                    </div>";    
        }
        
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
            $resposta .= "<div><b>Descri&ccedil;&atilde;o:</b><br>".nl2br(utf8_decode($descricao))."</div></td>";
            $resposta .= "<td class='descricao_detalhe span5'  border=0>";
            $resposta .= "<div><b>Anexo(s):</b><br>";
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
            $resposta .= "</tr>";

            $resposta .= "</table>";

        $resposta .= "</td></tr>";
        $resposta .= "</table>";

        /************************* TÉCNICOS INSCRITOS *************************/
        $sql_tecnicos      = "SELECT DISTINCT
                                 tbl_treinamento_posto.tecnico,
                                 tbl_treinamento_posto.participou,
                                 tbl_treinamento_posto.aprovado,
                                 tbl_login_unico.nome,
                                 tbl_posto.nome                 AS nome_posto,
                                 tbl_posto_fabrica.codigo_posto AS codigo_posto,
                                 tbl_tecnico.tecnico            AS id_tecnico,
                                 (
                                    SELECT COUNT(*)
                                    FROM tbl_treinamento_posto
                                    WHERE tbl_treinamento_posto.treinamento = {$treinamento}
                                    AND   tbl_treinamento_posto.tecnico     = tbl_tecnico.tecnico
                                 )                                                      AS qtde_inscritos
                            FROM tbl_treinamento_posto
                                    JOIN tbl_tecnico       ON tbl_tecnico.tecnico                  = tbl_treinamento_posto.tecnico
                                    JOIN tbl_login_unico   ON tbl_login_unico.login_unico::VARCHAR = tbl_tecnico.codigo_externo
                                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica            = {$login_fabrica}
                                    JOIN tbl_posto         ON tbl_posto.posto                      = tbl_posto_fabrica.posto 
                                    AND tbl_posto_fabrica.posto = tbl_treinamento_posto.posto
                            WHERE tbl_treinamento_posto.tecnico IS NOT NULL
                            AND   tbl_treinamento_posto.treinamento = {$treinamento}
                            AND tbl_treinamento_posto.data_avaliacao IS NULL";
        $res_tecnicos      = pg_query($con,$sql_tecnicos);
        $msg_erro_tecnicos = pg_last_error($con);
        
        if (pg_num_rows($res_tecnicos) == 0 && $treinamento_tipo == 'Online') {
            unset($button_show);
        }

        if (pg_num_rows($res_tecnicos) > 0) {

            $resposta  .=  "<table id='tblTreinamento' class='table table-striped table-bordered table-fixed'>";
            $resposta  .=  "<thead>";
                $resposta  .= "<tr class='titulo_tabela'><th colspan='6'> Técnicos Inscritos</th></tr>";
                    $resposta  .=  "<tr class='titulo_coluna'>";
                    $resposta  .=  "<th>Nome</th>";
                    $resposta  .=  "<th>Posto</th>";
                    $resposta  .=  "<th>Participou</th>";
                    $resposta  .=  "<th>Aprovado</th>";
                    $resposta  .=  "<th>Certificado</th>";

                if ($treinamento_tipo == 'Online'){
                    $resposta  .=  "<th>Qtde Participantes</th>";
                }
                $resposta  .=  "</tr>";
            $resposta  .=  "</thead>";
            $resposta  .=  "<tbody>";
            
                for ($i=0; $i<pg_num_rows($res_tecnicos); $i++){

                    $id_tecnico     = pg_fetch_result($res_tecnicos,$i,'id_tecnico');
                    $nome_tecnico   = pg_fetch_result($res_tecnicos,$i,'nome');
                    $nome_posto     = pg_fetch_result($res_tecnicos,$i,'nome_posto');
                    $codigo_posto   = pg_fetch_result($res_tecnicos,$i,'codigo_posto');
                    $participou     = pg_fetch_result($res_tecnicos,$i,'participou');
                    $aprovado       = pg_fetch_result($res_tecnicos,$i,'aprovado');
                    if ($treinamento_tipo == 'Online'){
                        $qtde_inscritos  = pg_fetch_result($res_tecnicos,$i,'qtde_inscritos');
                    }

                    if ($participou == 'f'){
                        $participou_campo = "<input type='checkbox' name='participou' class='participou' value='t' />";
                    }else if ($participou == 't'){
                        $participou_campo = "<input type='checkbox' name='participou' class='participou' value='t' checked='checked' />";
                    }

                    if ($aprovado == 'f'){
                        $aprovado_campo = "<input type='checkbox' name='aprovado' class='aprovado' value='t' />";
                    }else if ($aprovado == 't'){
                        $aprovado_campo     = "<input type='checkbox' name='aprovado' class='aprovado' value='t' checked='checked' />";
                    }

                    if ($participou == 't' && $aprovado == 't'){
                        $style_anexo = "style='';";
                    }else{
                        $style_anexo = "style='display: none;';";
                    }

                    $resposta  .=  "<tr>";
                        $resposta  .=  "<td class='td_id_tecnico' style='display: none;'>".$id_tecnico."</td>";
                        $resposta  .=  "<td>".$nome_tecnico."</td>";
                        $resposta  .=  "<td><b>".$codigo_posto."</b> - ".$nome_posto."</td>";
                        $resposta  .=  "<td><center>".$participou_campo."</center></td>";
                        $resposta  .=  "<td><center>".$aprovado_campo."</center></td>";
                        $resposta  .=  "<td><center><input type='file' name='anexo' class='anexo' ".$style_anexo."></center></td>";
                    if ($treinamento_tipo == 'Online'){
                        $resposta  .=  "<td><center>".$qtde_inscritos."</center></td>";
                    }
                    $resposta  .=  "</tr>";
                }   

            $resposta  .=  "</tbody>";
            $resposta .= " </table>";
        }

        $resposta .= "<div style='padding-bottom: 25px;'>
                        <span>    
                            <center>
                                {$button_show}
                            </center>
                        </span>
                    </div>";
        echo $resposta;
    }
?>
</div>

<script type="text/javascript">
$(function(){
        
        $('.aprovado').on('click', function(){
            verificaParticipouAprovado();
        });

        $('.participou').on('click', function(){
            verificaParticipouAprovado();
        });

        $("#gravar_treinamento").on('click', function(){
            var id_treinamento    = <?php echo $_GET['treinamento']; ?>;
            var avaliar_finalizar = "<?= $_GET['avaliar_finalizar'] ?>";
            var array_tecnicos = [];
            var datas          = "";

            $("#tblTreinamento").find("tbody > tr").each(function(idx,elem){
                var id_tecnico   = $(elem).find(".td_id_tecnico").text();
                var participou   = $(elem).find(".participou");
                var aprovado     = $(elem).find(".aprovado");
                var files        = $(elem).find(".anexo")[0];
                files            = files.files;
                datas            = new FormData();

                $.each(files, function (key, value) {                    
                    datas.append(key, value);                      
                });

                if ($(participou).is(':checked')){
                    participou = 't';
                }else{
                    participou = 'f';
                }

                if ($(aprovado).is(':checked')){
                    aprovado = 't';
                }else{
                    aprovado = 'f';
                }

                array_tecnicos.push({"tecnico": id_tecnico, "participou": participou, "aprovado": aprovado});
            
                datas.append("ajax", "sim");
                datas.append("acao", "gravar_treinamento_avaliacao");
                datas.append("treinamento", id_treinamento);
                datas.append("tecnicos", JSON.stringify(array_tecnicos));
                datas.append("avaliar_finalizar", avaliar_finalizar);
            });

            $.ajax({
                url: "ajax_treinamento_tecnico.php",
                method: "POST",
                cache: false,
                dataType: 'JSON',
                processData: false,
                contentType: false,
                data: datas
            }).done(function(data) {
                if (data.ok !== undefined) {
                    if (!alert(data.ok)){
                        window.location.reload();
                    }
                }else{
                    if (!alert(data.erro)){
                        window.location.reload();
                    }
                }
            });
        });
});

function verificaParticipouAprovado(){
    var participou = $('.participou').is(':checked');
    var aprovado   = $('.aprovado').is(':checked');

    if (participou == true && aprovado == true){
        $(".anexo").show();
    }else{
        $(".anexo").hide();
    }
}
</script>
</body>
</html>
