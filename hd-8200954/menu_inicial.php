<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once 'regras/menu_posto/menu.helper.php';
include_once 'funcoes.php';
include_once "funcao_altera_senha.php";
include_once 'classes/Posvenda/Seguranca.php';

$objSeguranca = new \Posvenda\Seguranca($login_fabrica,$con);

if ($S3_sdk_OK) {
    include_once S3CLASS;
    $s3 = new anexaS3('ve', (int) $login_fabrica);
    $S3_online = is_object($s3);
}

if ($_POST["ajax_altera_depois"] == true) {
    $retorno = $objSeguranca->gravaAlterarDepois($login_posto, $login_fabrica);
    if ($retorno) {
        setcookie("senha_posto_skip", true, time() + (86400 * 30), "/");
        exit(json_encode(["erro" => false]));
    } else {
        exit(json_encode(["erro" => true]));
    }
}

$posto_pesquisa_opiniao = isFabrica(1, 35, 85, 88, 94, 129, 138, 145, 151, 152, 161, 180, 181, 182);

if (isPosto(6359, 433883)) {
    $msg_alerts['danger'][] = traduz('as.os.e.os.pedidos.do.posto.de.testes.sao.excluidos.diariamente');
}
if ($posto_pesquisa_opiniao and !isFabrica(1)) {

    $sqlX = "SELECT pesquisa
               FROM tbl_pesquisa
              WHERE fabrica    = $login_fabrica
                AND categoria  = 'posto'
                AND ativo     IS TRUE
              ORDER BY pesquisa DESC LIMIT 1 ";

    $res = pg_query($con, $sqlX);

    if (pg_num_rows($res) > 0) {

        $pesquisa = pg_fetch_result($res, 0, 0) ;

        $sqlX = "SELECT  COUNT(1)
                 FROM    tbl_resposta
                 WHERE   pesquisa = $pesquisa
                 AND     posto    = $login_posto";

        $resi = pg_query($con, $sqlX);

        $resi = pg_fetch_result($resi, 0, 0);
        
        if ($resi == 0) {

            header("Location: opiniao_posto_new.php");
            exit;   
        }
    }
}

if (isFabrica(1) and $login_data_input < '2017-01-10') {
    include_once 'regras/1/redir_black.php';
}

$cor  = "#485989";
$cor2 = "#9BC4FF";
$corforum = "#880000";

/**
 * HD 1060482 - Banner publicidade sobre o BANNER Telecontrol para postos autorizados
 **/
$banner_ok = null;
$preencheu = null;

/**
* - AQUI SE INCLUIRÃO AS FÁBRICAS QUE TERÃO ACESSO AO DASHBOARD
*/
$usam_dashboard = (isFabrica(3,19,35,151,152,158,160,169,170,175,178,180,181,182,184,191,193) or $replica_einhell);

/** Fábricas que oferecem treinamentos **/

/*
 * VERIFICA SE O TECNICO ESTA MARCADO COMO tbl_login_unico.tecnico IS TRUE
*/
if (!empty($login_unico)){
    $sql_verifica = "SELECT
                         tbl_login_unico.tecnico_posto AS isTecnico,
                         tbl_tecnico.codigo_externo
                    FROM tbl_login_unico
                          JOIN tbl_tecnico ON tbl_tecnico.codigo_externo = tbl_login_unico.login_unico::VARCHAR
                    WHERE tbl_login_unico.login_unico = {$login_unico}";
    $res_verifica = pg_query($con,$sql_verifica);
    if (pg_num_rows($res_verifica) > 0){
        $isTecnico = pg_fetch_result($res_verifica,0,'isTecnico');
        if ($isTecnico == 't'){
            $isTecnico = true;
        }
    }else{
        $isTecnico = false;
    }
}else{
    $isTecnico = false;
}


$fabrica_treinamento = (isFabrica(1,42, 117, 122, 129, 138, 145, 148, 152, 169, 170, 171, 180, 181, 182)
    or (isFabrica(14, 15, 94, 134) and $cook_tipo_posto_et) or (isFabrica(175) and $isTecnico));

/**
 * Postos exclusivos Makita, Bosch e postos Jacto, não mostrar
 * 17673 é posto interno Bosch e Bosch Security, dá tot_fabricas == 2.
 *
 * 19/02/2013 - Mudando a lógica do $banner_ok.
 * Se banner_ok === true, NÃO MOSTRA A IMAGEM e NEM o TEXTO
 * Se deve mostrar alguma coisa, o valor da variável irá determinar
 * o conteúdo a ser mostrado.
 **/
if (isFabrica(87) or isPosto(17673))
    $banner_ok = true;

if (isFabrica(20, 42, 96)) {
    if (in_array($login_credenciamento, array('CREDENCIADO', 'EM DESCREDENCIAMENTO')))
        $banner_ok = true;
}

/**
 * - VERIFICA SE O POSTO PODE
 * - REALIZAR CONSULTAS DO ESTOQUE
 * - EXCLUSIVO POSTOS ESMALTEC
 * - HD-1229579 - William
 */

/* Postos internos do fabricante, não mostrar */
if (in_array($login_tipo_posto, array(46,70,169,185,214,215,237,242,243,254,258,261,266,268,270,294,304,329,336,341,346))) {
    $banner_ok = true;
}

/* Se ainda é NULL, verifica se o posto já preencheu o formulário de envio do BANNER Teleceontrol */
if (is_null($banner_ok)) {
    $sqlBanner = "SELECT validado,
                        (LENGTH(marca_ser_autorizada) > 3 AND
                         LENGTH(outras_fabricas)      > 3) AS preencheu_fabricas
                    FROM tbl_posto_alteracao
                   WHERE cnpj = '$login_cnpj'
                ORDER BY data_input DESC
                   LIMIT 1";
    $resBanner = pg_query($con, $sqlBanner);

    if (pg_num_rows($resBanner) == 0) {
        $banner_ok = 'imagem';
    } else {
        if (pg_fetch_result($resBanner, 0, 1) == 'f' or is_null(pg_fetch_result($resBanner, 0, 1)))
            $banner_ok = 'msg_campos';
    }
}

//BANNER NÃO IMPORTA A FABRICA POR ISSO FABRICA 10 FIXO
$sqlBanner = "SELECT banner FROM tbl_posto_alteracao WHERE fabrica = 10 AND posto = $login_posto";
$resBanner = pg_query($con, $sqlBanner);

if (pg_num_rows($resBanner) > 0) {
    $bannerNao = pg_fetch_result($resBanner, 0, "banner");
}

// die("SQL: $sqlBanner<br /><br />
//  Resultado: ". pg_num_rows($resBanner) . "registros. Preencheu? " .
//  pg_fetch_result($resBanner, 0, 1) .
//  "Banner OK? $banner_ok"
// );

// Atualização do cadastro do posto, solicitado ao próprio posto autorizado pelas fábricas
if (isFabrica(15, 24)) {
    $data_hora = ($login_fabrica == 15) ? '2010-08-10 09:36:39.548903' : '2010-06-09 09:36:39.548903';
    $corte = is_date($data_hora, 'ISO', 'U');

    if (is_date($atualizacao, 'ISO', 'U') <= $corte) {
        header('Location:posto_cadastro.php');
        exit;
    }
}

if ($login_fabrica == 15) {
    $sql = "SELECT comunicado
              FROM tbl_comunicado
             WHERE fabrica = $login_fabrica
               AND fn_retira_especiais(tipo) = 'Tabela de precos'
               AND ativo IS TRUE
             ORDER BY comunicado DESC
             LIMIT 1";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        $comunicado_tabela_de_preco = pg_fetch_result($res, 0, 'comunicado');
    }
}

/*
if($login_fabrica==10 and $ip=="201.13.180.246"){
    $sql_nova = "SELECT
                        to_char(data_alteracao, 'DD-MM-YYYY') as data_alteracao
                FROM tbl_posto_fabrica
                WHERE posto=$login_posto
                AND fabrica=$login_fabrica";
    $res_nova = pg_exec($con, $sql_nova);
    $data_alteracao = pg_result($res_nova,0,data_alteracao);
    if($data_alteracao<'17-12-2006'){
        header ("Location: posto_cadastro_atualiza.php");
        exit;
    }
}
*/

$digita_os              = $login_posto_digita_os;
$pedido_em_garantia     = $login_pede_peca_garantia;
$categoria              = $login_categoria;
$tipo_posto             = $login_tipo_posto;
$reembolso_peca_estoque = $login_reembolso_peca_estoque;
$contato_estado         = $login_contato_estado;

if ($login_fabrica == 1) {

    $sql_cond[1] = "tbl_comunicado.pedido_em_garantia IS NULL ";
    $sql_cond[2] = "tbl_comunicado.pedido_faturado IS NULL ";
    $sql_cond[3] = "tbl_comunicado.digita_os IS FALSE ";
    $sql_cond[4] = "tbl_comunicado.reembolso_peca_estoque IS FALSE ";

    if ($pedido_em_garantia)     $sql_cond[1] ="tbl_comunicado.pedido_em_garantia IS NOT FALSE ";
    if ($pedido_faturado)        $sql_cond[2] ="tbl_comunicado.pedido_faturado IS NOT FALSE ";
    if ($digita_os)              $sql_cond[3] ="tbl_comunicado.digita_os IS TRUE ";
    if ($reembolso_peca_estoque) $sql_cond[4] ="tbl_comunicado.reembolso_peca_estoque IS TRUE ";

    $sql_cond_total="AND (" . implode('OR ', $sql_cond) . ") ";
//  Tabela de preços
    $link_preco             = "tabela_precos.php";
    if ($login_fabrica==19) $link_preco = "produtos_arvore.php";
    if ($login_fabrica==19) $link_mao_obra = "valores_mao_de_obra.php";

    $sql = "
        SELECT  *
        FROM    tbl_comunicado
        WHERE   tipo                    = 'Comunicado Inicial'
        AND     tbl_comunicado.fabrica  =  $login_fabrica
        AND     (
                    tbl_comunicado.posto    =  $login_posto
                OR  tbl_comunicado.posto    IS NULL
                )
        AND     tbl_comunicado.ativo   IS TRUE
        AND     (
                    tbl_comunicado.linha IS NULL
                OR  tbl_comunicado.linha IN (
                    SELECT  tbl_posto_linha.linha
                    FROM    tbl_linha
                    JOIN    tbl_posto_linha ON  tbl_posto_linha.linha = tbl_linha.linha
                                            AND tbl_posto_linha.posto = $login_posto
                    WHERE   tbl_linha.fabrica = $login_fabrica
                    )
                )
        AND     (
                    tbl_comunicado.destinatario_especifico = '$categoria'
                OR  tbl_comunicado.destinatario_especifico = ''
                )
        AND     (
                    tbl_comunicado.tipo_posto = '$login_tipo_posto'
                OR  tbl_comunicado.tipo_posto is null)
        AND     (
                    UPPER(tbl_comunicado.estado) = UPPER('$contato_estado')
                OR  tbl_comunicado.estado = ''
                )
        $sql_cond_total
  ORDER BY      comunicado DESC
        LIMIT   1";
    $res = pg_exec ($con,$sql);

} else {
    if($login_fabrica == 104){
        $sql = "SELECT *
                  FROM tbl_comunicado
                 WHERE tipo = 'pedido_faturado_parcial'
                   AND fabrica =  $login_fabrica
                   AND ativo   IS TRUE 
                   AND posto = $login_posto ORDER BY comunicado DESC ";
        $res = pg_exec ($con,$sql);
        
        if(pg_num_rows($res) == 0){
            $sql = "SELECT *
                        FROM tbl_comunicado
                        WHERE tipo = 'Comunicado Inicial'
                        AND fabrica =  $login_fabrica
                        AND ativo   IS TRUE 
                        AND posto IS NULL ORDER BY comunicado DESC ";
                    $res = pg_exec ($con,$sql);
        }
    }else{
        $sql = "SELECT *
                  FROM tbl_comunicado
                 WHERE tipo = 'Comunicado Inicial'
                   AND fabrica =  $login_fabrica
                   AND ativo   IS TRUE ";
    
                   if ($login_fabrica == 3) {
                       $sql .= "and data >= (current_timestamp - interval '3 months')";
                   }
    
        $sql .= ' AND posto IS NULL ';
        
        if($login_fabrica==20) $sql .= " AND  pais = '$login_pais' ";
    
        $sql .= "ORDER BY comunicado DESC LIMIT 1";
    
        $res = pg_exec ($con,$sql);
    }

    if (pg_num_rows($res) == 0) {

        if ($login_fabrica == 138) { //HD-2930346
            $sql = "SELECT tbl_comunicado.comunicado,
                            tbl_comunicado.mensagem,
                            tbl_comunicado.descricao,
                            tbl_comunicado.extensao,
                            tbl_comunicado.tipo,
                            tbl_treinamento_posto.ativo,
                            tbl_treinamento.treinamento,
                            tbl_treinamento_posto.posto
                    FROM tbl_comunicado
                    LEFT JOIN tbl_treinamento ON tbl_treinamento.fabrica = tbl_comunicado.fabrica
                    LEFT JOIN tbl_treinamento_posto ON tbl_treinamento.treinamento = tbl_treinamento_posto.treinamento AND tbl_treinamento_posto.posto = $login_posto
                    WHERE tipo='Comunicado Inicial'
                    AND tbl_comunicado.fabrica = $login_fabrica
                    AND tbl_comunicado.posto = $login_posto
                    AND tbl_comunicado.ativo IS TRUE
                    ORDER BY tbl_comunicado.comunicado DESC LIMIT 1";

        } else {
            $sql = "SELECT *
                  FROM tbl_comunicado
                 WHERE tipo = 'Comunicado Inicial'
                   AND fabrica =  $login_fabrica
                   AND posto   =  $login_posto
                   AND ativo   IS TRUE ";
	   if ($login_fabrica == 3) {
		   $sql .= "and data >= (current_timestamp - interval '3 months')";
	   }

            if ($login_fabrica==20)
                $sql .= " AND  pais = '$login_pais' ";

            $sql .= "ORDER BY comunicado DESC LIMIT 1";
        }
         
        $res = pg_query($con, $sql);
    }
}
// echo nl2br($sql);exit;

if (pg_num_rows($res) > 0) {
    $avisoAberto         = 'fa-minus';
    $comunicado          = trim(pg_result($res,0,'comunicado'));
    $comunicado_mensagem = trim(pg_result($res,0,'mensagem'));
    $comunicado_titulo   = trim(pg_result($res,0,'descricao'));
    $extensao            = trim(pg_result($res,0,'extensao'));
    $tipo                = trim(pg_result($res,0,'tipo'));
    $comunicado_link     = '';

    if ($login_fabrica == 138) {
        $treinamento_posto = trim(pg_result($res,0,'treinamento'));
        $treinamento_ativo = trim(pg_result($res,0,'ativo'));
    }

    $tipo_s3 = in_array($tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co'; //Comunicado técnico?
    if ($s3->tipo_anexo != $tipo_s3) {
        $s3->set_tipo_anexoS3($tipo_s3);
    }
    $s3->temAnexos($comunicado);

    if ($s3->temAnexo) {
        $com_file = $s3->url;
        $comunicado_link = "<a href='$com_file' target='_blank' class='btn'><i class='fa fa-paperclip fa-lg'> </i>".traduz("abrir.anexo")."</a>";
    }

    if (file_exists($com_file)) {
        $comunicado_link = "<a href='$com_file' target='_blank' class='btn'><i class='fa fa-paperclip fa-lg'>".traduz("abrir.anexo")."</a>";
    }
} else {
    $comunicado_titulo   = '<center>' . traduz("bem.vindo.a.telecontrol") . '</center>';
    $comunicado_mensagem = traduz("voce.acessou.o.sistema.telecontrol.pos.venda");
    $comunicado_link = "";
    $avisoAberto = 'fa-plus';
}
//$plugins = array('shadowbox');

ob_start();
// HD 214236
?>
    <style type="text/css">

        .tabela_auditoria {
            border:1px solid #d2e4fc;
            background-color:#485989;
            border-left: 1px solid #dddddd;
        }

        table.nota_fiscal_conferencia td {
            border-top: 1px solid #dddddd;
            border-left: 1px solid #dddddd;
        }

        table.nota_fiscal_conferencia th {
            padding-left: 18px;
            padding-right: 18px;
            background-color: #596D9B !important;
            font-weight: bold;
            padding: 8px;
            line-height: 20px;
            text-align: center !important;
            color: white;
            border-left: 1px solid #dddddd;
        }

        table.nota_fiscal_conferencia{
            margin-left: 20%;
            border: 1px solid #dddddd;
            border-collapse: separate;
            border-left: 0;
            -webkit-border-radius: 4px;
            -moz-border-radius: 4px;
            border-radius: 4px;
            text-align: center;
        }

        .pecas_devolucao td{
            border-top: 1px solid #dddddd;
            border-left: 1px solid #dddddd;
            font-family:Arial, sans-serif;
            font-size:13px;
        }

        .pecas_devolucao .pecas_coluna{
            padding-left: 18px;
            padding-right: 18px;
            background-color: #596D9B !important;
            font-family:Arial, sans-serif;
            font-weight: bold;
            font-size:15px;
            padding: 8px;
            line-height: 20px;
            color: white;
            border-left: 1px solid #dddddd;
        }

        .pecas_devolucao{
            border: 1px solid #dddddd;
            border-collapse: separate;
            border-left: 0;
            -webkit-border-radius: 4px;
            -moz-border-radius: 4px;
            border-radius: 4px;
            text-align: center;
        }

        .inicio_auditoria {
            font-family: Arial;
            FONT-SIZE: 8pt;
            font-weight: bold;
            text-align: left;
            color: #FFFFFF;
            padding-right: 1ex;
            text-transform: uppercase;
        }

        .conteudo_auditoria {
            font-family: Arial;
            FONT-SIZE: 8pt;
            font-weight: bold;
            text-align: left;
            background: #F4F7FB;
        }

        .titulo2_auditoria {
            font-family: Arial;
            font-size: 7pt;
            text-align: center;
            color: #000000;
            background: #ced7e7;
            text-transform: uppercase;
        }
        /*  Tabela de resumo de OS para a Precision */
        table#resumoOS {
            color: #333;
            width: 480px;
            margin:1em 0;
            border-collapse: separate;
            border-spacing: 3px;
            border: 2px solid #d2e4fc;
            border-radius: 6px;
            box-shadow: 3px 3px 1px #444;
        }
        table#resumoOS thead {background-color: #485989;color: white}
        table#resumoOS tr:nth-child(even) {background-color: #eee}
        table#resumoOS tr.bold {
            font-weight:bold;
            cursor: default;
        }
        table#resumoOS td {
            border: 1px dotted #aaa;
            text-align:center;
            cursor: s-resize;
        }
        table#resumoOS td a {
            text-align: center;
            font-size: x-small;
            font-weight: normal;
            text-decoration: none;
            color:#596d9b;
        }
        table#resumoOS td p {font: normal normal 10px Verdana, Geneva, Arial, Helvetica, sans-serif;}
        table#resumoOS td a:hover {color: #405080;text-decoration: underline}
        div.oculto {text-align: left;padding: 8px 16px;}

        img#gChart {
            border: 2px solid #5989FF;
            margin:1em 0;
            padding: 0.5em 0.7em;
            background-image: linear-gradient(top, #CDDDFF, #EDEDFF);
            filter: progid:DXImageTransform.Microsoft.Gradient(GradientType=1,StartColorStr='CDDDFFFF',EndColorStr='EDEDFFFF');
            border-radius:8px;
            box-shadow: 0 0 8px #444;
            *filter:progid:DXImageTransform.Microsoft.DropShadow(color='#444', offX=2, offY=1,enabled=true,positive='false');
        }
    </style>

    <script type="text/javascript">

//        $().ready(function() {
 window.onload = function() {   

    
            if ($('#infouser.botao[href=login_unico.php]').length == 0) {
                $('#bloqPopup').show();
            }

            /* HD-2831042
            $('td a.conteudo img').hover(
                function() {    //  onMouseOver/onMouseEnter
                    $(this).fadeTo('fast',1);
                },
                function() {    //  onMouseOut/onMouseLeave
                    $(this).fadeTo('fast',.6);
                }
            );
            */
            $('.read-more').click(function (e) {
                var text = $("section").hasClass('expanded') ? ("<?=traduz('Leia Mais')?>") : ("<?=traduz('Esconder')?>");
                $(this).text(text);
                $('.message section').toggleClass('expanded');
                e.preventDefault();
                document.getElementById('center_menu').scrollIntoView();
            });

            $('a.close').click(function (e) {
                $("a.close i").toggleClass('fa-minus fa-plus');
                $('.message .the-info').slideToggle();
                e.preventDefault();
            });

            $(document).keyup(function (e) {
                if (e.keyCode == 27) {
                    $('a.close i').removeClass('fa-minus').addClass('fa-plus');
                    $('.message .the-info').slideUp();
                }
            });
        //});
        };

        function fechaBanner() {
            var date = new Date();
            date.setDate(date.getDate() + 2);
            document.cookie = "bannerFechado=fechado; expires="+date.toUTCString();
            $('#bloqPopup').remove();
        }

        function naoBanner () {
            $.ajax({
                url: "<?=$_SERVER['PHP_SELF']?>",
                type: "POST",
                data: { posto: "<?=$cookie_login['cook_posto']?>", naoBanner: true },
                success: function () {
                    $('#bloqPopup').remove();
                }
            });
        }

        function exibeAlertaOsPendente(url = "") {
            Shadowbox.open({
			content : "<div style='background-color: #fff; padding: 20px; text-align: center; text-transform: uppercase; font-family: arial;'><?php
				echo traduz(['existem.os.aguardando.resposta', 'por.favor.acesse.estas.os.e.responda.a.fabric']);?></div>",
                player: 'html',
                title : "O.S's Aguardando Respostas",
                height : 100,
                width : 400,
                options: {
                    onClose: function(){
                        if(url != ""){
                            location.href = url;
                        }
                    }
                }
            });

            // alert("Existem OS's aguardando resposta. Por favor, acesse estas OS's e responda à fábrica.");
        }

        function abrirPop(pagina,largura,altura) {
            w = screen.width;
            h = screen.height;

            meio_w = w/2;
            meio_h = h/2;

            altura2 = altura/2;
            largura2 = largura/2;
            meio1 = meio_h-altura2;
            meio2 = meio_w-largura2;

            // window.open(pagina,'pedido','height=' + altura + ', width=' + largura + ', top='+meio1+', left='+meio2+',scrollbars=yes, resizable=no, toolbar=no');
            window.open(pagina,'pedido','height=' + h + ', width=' + w + ',scrollbars=yes, resizable=no, toolbar=no');
        }

        function buscaPecaCatalogoPecas(cnpj){
            $.ajax({
                url: "pedido_jacto_cadastro.php",
                type: "POST",
                data: "geraToken=sim&cnpj="+cnpj,
                success : function(data){
                    retorno = data.split('|');
                    if (retorno[0] == "ok") {
                        abrirPop("http://www.jacto.net.br/Token.aspx?Token="+retorno[1]+"&Const=SITERETORNOTELECONTROL",750,600);
                    }
                }
            });
        }

        $(function() {

            $("#logo_fabrica").click(function(event) {
                event.preventDefault();
                window.open('<?= $site_fabrica ?>');
            });

            $("#animado").animate({left: 450, top: 200}, 5500);

        //  HD 170502
            $('.oculto').hide();
            $('.toggle_data').click(function () {
                var dias = $(this).attr('dias');
                var item = "#data_"+dias;
                if ($(item).html().length > 30) {
                    $(item).toggle('normal');
                } else{
                    $(item).html('<p>Aguarde...</p>').show('normal');
            //  alert ("Consultando OS de até "+dias+" dias...");
                $.get('posto_consulta_os_aberto.php',
                        {'ajax' :'consulta',
                         'dias' : dias},
                        function(data) {
                        if (data == 'ko' || data == undefined) {
                                $(item).text('Erro ao consultar as OS. Tente em alguns minutos.');
                            } else if (data == 'NO RESULTS' || data.indexOf('<p>') != 0) {
                                $(item).html('').hide('fast');
                            } else {
            //                  alert (data);
                                $(item).html(data).show('normal');
                            }
                });
                }
            });
            function fechar(){
                $("#animado").toggle();
            }
        });
    </script>
<?php
$headerHTML .= ob_get_clean();

if ($helpdeskPostoAutorizado) {
    // ! HD 121248 - Criar botão de Help-Desk para gerar/listar callcenter. Todos os postos de testes podem ver por padrão (augusto)
    include_once 'helpdesk.inc.php';

    if (hdPermitePostoAbrirChamado()) {
        $possuiChamadosPendentes = (boolean) $temHDs = hdPossuiChamadosPendentes();

        if ($login_fabrica == 1) {
            /* Pendentes Avaliação do Posto */
            /* Pendentes 48 horas SAC */
            $sql_chamados_pendemtes = "SELECT COUNT(hd_chamado) AS qtde_pendentes FROM tbl_hd_chamado WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND status = 'Ag. Posto' AND data_resolvido IS NULL";
            $res_chamados_pendemtes = pg_query($con, $sql_chamados_pendemtes);

            if (pg_num_rows($res_chamados_pendemtes) > 0) {
                $qtde_chamados_total = pg_fetch_result($res_chamados_pendemtes, 0, "qtde_pendentes");
            }

            /* Chamados Pendentes SAC */
            $sql_chamados_pendentes = "SELECT COUNT(hd_chamado) AS qtde_pendentes FROM tbl_hd_chamado WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND status = 'Ag. Posto' AND categoria = 'servico_atendimeto_sac' AND data_resolvido IS NULL";
            $res_chamados_pendentes = pg_query($con, $sql_chamados_pendentes);

            if(pg_num_rows($res_chamados_pendentes) > 0){
                $qtde_chamados_total = pg_fetch_result($res_chamados_pendentes, 0, "qtde_pendentes");
                $popup_chamados_pendentes = ($qtde_chamados_total > 0) ? true : false;
            }
        }
        unset($possuiChamadosPendentes,$strHrefHelpDesk,$strImlHelpDesk,$strTitleHelpDesk);
        unset($aExibirHelpDesk);
    }
}

if ($login_fabrica == 1 && $popup_chamados_pendentes == true) {

    $texto_pendente = traduz("Existe chamado aberto pelo nosso Call-Center aguardando sua resposta para que possamos prosseguir com o atendimento ao consumidor final. É importante que você verifique o mais breve.");

    $texto_pendente .= "<br /><p style='text-align: center;'>";
    $texto_pendente .= "<a href='helpdesk_listar.php'><button type='button'>Abrir</button></a> &nbsp; &nbsp; <button type='button' onclick='Shadowbox.close();'>Responder depois</button>";
    $texto_pendente .= "</p>";

    $popup_chamados_pendentes = "<div class='box-alerta'><strong>Atenção</strong>:<br /><p style='text-align: justify; font-size: 14px !important;'>$texto_pendente</div>";

    ob_start();
?>
    <style>
        .box-alerta{
            width: 360px;
            height: 210px;
            padding: 20px;
            background-color: #fff;
            font-size: 16px !important;
            font-family: arial;
        }
    </style>

    <script>

    function popup_chamados_pendentes(){
        Shadowbox.open({
            content : "<?=$popup_chamados_pendentes?>",
            player: 'html',
            title : 'Chamados SAC pendentes',
            height : 250,
            width : 400
        });
    }

    setTimeout(function(){
        popup_chamados_pendentes();
    }, 1000);

    </script>
<?php
    $headerHTML .= ob_get_clean();
}

$table_menu = isFabrica(87) ? "700px" : "762px";

if ($login_fabrica == 43) {
    ob_start();
    include ('posto_medias.php');
    $bodyHTML .= ob_get_clean();
}

if (isFabrica(42)) {
    $sql = "SELECT tipo_posto, contato_estado FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
    $res = pg_query($con, $sql);

    $tipo_posto     = pg_fetch_result($res, 0, 'tipo_posto');
    $contato_estado = pg_fetch_result($res, 0, 'contato_estado');

    $sql_comunicado_makita = "SELECT tbl_comunicado.comunicado,
               tbl_comunicado.descricao AS titulo,
               tbl_comunicado.produto,
               tbl_comunicado.mensagem,
               tbl_comunicado.link_externo,
               tbl_comunicado.tipo,
               tbl_comunicado.extensao AS anexo
          FROM tbl_comunicado
         WHERE tbl_comunicado.fabrica     =  $login_fabrica
           AND tbl_comunicado.suframa     IS TRUE
           AND tbl_comunicado.ativo       IS TRUE
           AND (tbl_comunicado.tipo_posto =  $tipo_posto OR tbl_comunicado.tipo_posto IS NULL)
           AND (UPPER(tbl_comunicado.estado) = UPPER('$contato_estado') OR tbl_comunicado.estado IS NULL)
           AND (tbl_comunicado.posto = $login_posto OR tbl_comunicado.posto IS NULL)
         ORDER BY tbl_comunicado.data DESC
         LIMIT 4
        ";

    // echo nl2br($sql_comunicado_makita);
    $res_comunicado_makita = pg_query($con, $sql_comunicado_makita);

    if (pg_num_rows($res_comunicado_makita) > 0) {
        ob_start(); ?>
    <style>
        div#center_menu {
            margin-left: 255px;
        }
        #mural_makita {
          margin: 50px 2ex 55px 0;
          width: 255px;
          background: #f2f2f2;
          padding: 1ex 1em 55px 1ex;
          box-shadow: 0 2px 5px grey;
          height: calc(100vh - 50px);
          overflow-y: auto;
          z-index: 100;
          position: fixed;
          top: 0;
          margin-bottom: 60px;
        }
    </style>
<?php
        $headerHTML .= ob_get_clean();

        // INICIO - HD-3653946
        ob_start(); ?>
            <div id="mural_makita">
<?php
        $comunicado_makita = "<h3 style='text-align: center; font-size: 15px;'>Mural de Avisos Makita</h3>";

        for ($i = 0; $i < pg_num_rows($res_comunicado_makita); $i++) {
            $comunicado      = pg_fetch_result($res_comunicado_makita, $i, 'comunicado');
            $comunicado_tipo = pg_fetch_result($res_comunicado_makita, $i, 'tipo');
            $titulo          = pg_fetch_result($res_comunicado_makita, $i, 'titulo');
            $produto         = pg_fetch_result($res_comunicado_makita, $i, 'produto');
            $mensagem        = pg_fetch_result($res_comunicado_makita, $i, 'mensagem');
            $anexo           = pg_fetch_result($res_comunicado_makita, $i, 'anexo');
            $link_externo    = pg_fetch_result($res_comunicado_makita, $i, 'link_externo');
            $produto         = (strlen($produto) > 0) ? "<strong>Produto:</strong> $produto <br /> <br />" : "";

            if ($S3_online) {
                $s3->set_tipo_anexoS3($comunicado_tipo);
                $s3->temAnexos($comunicado);
                $s3link = $s3->url;
                $link   = "<a href='{$s3link}' target='_blank'>Ver Anexo</a>";
            }

            $anexo  = (strlen($link) > 0) ? "<strong style='font-size: 13px'>Anexo:</strong> $link <br /> <br />" : "";

            $link_externo = (strlen($link_externo) > 0) ? "<a href='$link_externo' target='_blank'>Clique para ver</a><br /> <br />" : "";
            $comunicado_makita .= "
                <br /><strong style='font-size: 13px;'>$titulo</strong> <br /> <br />
                <div style='font-size: 13px'>
                $produto".
                nl2br($mensagem)."<br /> <br />
                $anexo
                $link_externo
                </div>
                <hr />
                ";
        }

        if (strlen($comunicado_makita) > 0) {
            echo "
            <div id='com-makita'>
                <?=$comunicado_makita?>
                <div style='clear: both;'></div>
            </div>";
        }
        echo "</div>\n";
        $bodyHTML .= ob_get_clean();
    }
}

//$plugins = array('shadowbox');
$title   = 'Menu Inicial';

if($login_fabrica == 161 && $cook_idioma != 'pt-br'){

    include_once "os_cadastro_unico/fabricas/161/classes/verificaDebitoPosto.php";
    $verificaDebitoPosto = new verificaDebitoPosto($login_posto);
    $dadosRetorno = $verificaDebitoPosto->retornaDebitos();
    $dadosRetorno = json_decode($dadosRetorno, true);
	$total_duplicata = preg_replace("/\D/","",$dadosRetorno['total_venc']) ;
   if($total_duplicata > 0){
        $msg_alerts['danger'][] = "<b><a href='cadastro_pedido.php' style='color: #FFFFFF;'>Duplicatas em atraso: ".$dadosRetorno['total_venc']."</a></b>";
    }

    $sqlPostoPedido = "SELECT tbl_faturamento.emissao,
                              (current_date - tbl_faturamento.emissao) as dias
                       FROM tbl_faturamento 
                       JOIN tbl_pedido 
                       ON tbl_pedido.posto = tbl_faturamento.posto 
                       AND tbl_pedido.fabrica = {$login_fabrica} 
                       LEFT JOIN tbl_posto_linha 
                       ON tbl_posto_linha.linha = '1247'
                       WHERE tbl_faturamento.posto = {$login_posto} 
                       ORDER BY emissao DESC 
                       LIMIT 1";

    $resPostoPedido = pg_query($con, $sqlPostoPedido);

    $diasUltimaCompra = pg_fetch_result($resPostoPedido, 0, 'dias');

    if ($diasUltimaCompra >= 150) {

        $avisoPedido = "<b><p style='text-align: center; font-family: Times New Roman; font-size: 14px;'></br>Estimado Cliente, </br> Su última compra ha sido realizada a más de cinco meses, para continuar con la exclusividad concedida a la empresa, por favor, programe su próximo pedido.</br>
        Cordialmente, </br> Equipo Cristófoli</p><b>";

    }
}
if($login_fabrica == 161){
	$sql_comunicado_cristofoli = "SELECT tbl_extrato.extrato
					FROM tbl_extrato
					JOIN tbl_extrato_lgr ON tbl_extrato.extrato = tbl_extrato_lgr.extrato AND tbl_extrato_lgr.qtde > coalesce(tbl_extrato_lgr.qtde_nf,0)
					JOIN tbl_peca ON tbl_extrato_lgr.peca = tbl_peca.peca AND tbl_peca.fabrica = {$login_fabrica}                                          
					JOIn tbl_lista_basica ON tbl_peca.peca = tbl_lista_basica.peca
					join tbl_produto on tbl_produto.produto = tbl_lista_basica.produto
					LEFT JOIN tbl_faturamento_item ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao AND tbl_faturamento_item.peca = tbl_extrato_lgr.peca
					LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.distribuidor = tbl_extrato.posto AND tbl_faturamento.cancelada IS NULL AND tbl_faturamento.fabrica = {$login_fabrica} AND tbl_faturamento.natureza ~ 'Devolu'
					WHERE tbl_extrato.fabrica = {$login_fabrica}
					AND tbl_extrato.data_geracao::date < CURRENT_DATE - INTERVAL '60 days'
					AND tbl_produto.fabrica_i = {$login_fabrica}
					AND tbl_produto.linha = 972
					ANd tbl_extrato_lgr.devolucao_obrigatoria
					AND tbl_faturamento.faturamento IS NULL
					AND tbl_extrato.posto = {$login_posto}";
    $res_comunicado_cristofoli = pg_query($con, $sql_comunicado_cristofoli);

    if(pg_num_rows($res_comunicado_cristofoli) > 0){
	    $mensagem = "<b>ATENÇÃO:</b> Extratos pendentes de devolução de peças com retorno obrigatório. Regularize a situação para evitar faturamento.";
        $msg_alerts['danger'][] = $mensagem;
    }
}

if($login_fabrica == 72){

$mensagem = "<b>Caro Parceiro,<br><br>Como medida de prevenção ao COVID-19, o nosso atendimento encontra-se em funcionamento
		reduzido, nos impossibilitando de atender a sua solicitação.<br><br>Caso precise falar conosco, registre sua solicitação através do Help Desk, no sistema Telecontrol.<br><br>Caso haja procura pelos serviços de assistência técnica dos nossos consumidores e clientes Mallory, gentileza oriente-os a entrar em contato conosco pelo nosso site: http://portal.mallory.com.br/faleconosco/.<br><br>Desejamos que todo esse cenário seja revigorado e que nossa parceria volte mais próxima, intensa e produtiva.<br><br>Desde já, agradecemos a sua compreensão, pois juntos encontraremos a nossa melhor versão<br><br>Time Customer Experience.</b>";
	#$msg_alerts['danger'][] = $mensagem;
}
include_once "cabecalho.php";

// HD-6518915 Postos para vendas a clientes
if ($login_fabrica == 24 && qual_tipo_posto($login_posto) == 696) {

    echo '<div id="menu_inicial">'.$cabecalho->cardsMenu(include(MENU_DIR.'menu_inicial.php')).'</div>';

    if ($banner_ok === 'imagem' or $banner_ok === false) { ?>
                <div style="text-align: center;">
                    <strong>N&atilde;o perca tempo, cadastre-se j&aacute;!</strong> <br />
                    <a style='text-decoration:none;border:0' href='externos/autocredenciamento_new.php' target='_new'>
                        <img src="imagens/autocredenciamento.jpg" style="cursor:pointer; width: 500px;box-shadow: 0 0 2px black;" alt="Telecontrol" />
                    </a>
                </div>
<?php 
}
    include "rodape.php";
?>
</body>
</html>
<?php 
    die;
    }

if (in_array($login_fabrica, [151, 169, 170])) {
    ?>
    <style type="text/css">
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background: black;
            background-color: #fefefe;
            margin: 6% auto;
            padding: 3px;
            border: 1px solid #888;
        }

        .modal-size {
            width: 60%;
            height: 70%;
        }

        .modal-size-Midea {
            width: 38%;
            height: 45%;
        }

        .modal-size-imagem {
            width: 75%;
            height: 42%;
            margin-top: 10%;
        }

        .close, .close_imagem {
            color: white;
            position: absolute;
            font-size: 20px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus,
        .close_imagem:hover,
        .close_imagem:focus,
        .div_close:hover,
        .div_close:focus {
            color: #aaa;
            text-decoration: none;
            cursor: pointer;
        }

        .background_circle {
            background: black;
            border-radius: 50%;
            width: 2.5%;
            height: 4.5%;
            margin-top: 1%;
            margin-left: -1%;
        }
    </style>

    <?php  
        $urlVideo = "https://d171023o4fxvfz.cloudfront.net/posvenda/video_modial_2019-10-03.mp4";
        $modalSize = "modal-size";

        if (in_array($login_fabrica, [169, 170]))  { 
            $urlVideo = "https://d171023o4fxvfz.cloudfront.net/posvenda/Filme_MC_Covid-19_FINAL.mp4"; 
            $modalSize = "modal-size-Midea";
        }
     ?>

    <div id="modal_video_mondial" class="modal">
        <div class="modal-content <?=$modalSize?> ">
            <div style="float: right; margin-top: -3%;" class="div_close">
                <span class="close background_circle">&times;</span>
            </div>
            <video width="100%" height="100%" id="videoHtml" controls>
                <source id="video_mondial" src="<?=$urlVideo?>" type="video/mp4"/>
            </video>
        </div>
    </div>
    <div id="modal_imagem_mondial" class="modal">
        <div class="modal-content modal-size-imagem">
            <div style="float: right; margin-top: -2%;" div="div_close">
                <span class="close_imagem background_circle">&times;</span>
            </div>
            <img src="https://d171023o4fxvfz.cloudfront.net/posvenda/mondial_2019-09-27_16-44-39.jpg" style="width: 100%; height: 100%;" />
        </div>
    </div>

        <script type="text/javascript">

     window.onload = function() { 

       // $().ready(function() {
            var modal_video  = $("#modal_video_mondial")[0];
            var modal_imagem = $("#modal_imagem_mondial")[0];
            var modal_close  = $(".close")[0];

            if(modal_video != ""){
        <?php if ($login_fabrica == 151) { ?>
                modal_video.style.display = "block";
        <?php } else { ?>
                let today = new Date();
                today = today.getDate();

                let checkLocal = localStorage.getItem("modalCovidMidea");
                if(checkLocal == null){
                    modal_video.style.display = "block";
                }else{
                    if(checkLocal != today){
                        modal_video.style.display = "block";
                    }                       
                }
        <?php } ?>
            }

            modal_close.onclick = function() {
                modal_video.style.display  = "none";
                $('#videoHtml')[0].pause();
                
                <?php if (in_array($login_fabrica, [169, 170])) { ?>
                        let today = new Date();
                        today = today.getDate();
                        let checkLocal = localStorage.setItem("modalCovidMidea",today);
                <?php } else { ?>
                        showImagem();
                <?php } ?>
            }

            window.onclick = function(event) {
              if (event.target == modal_video) {
                modal_video.style.display  = "none";
                $('#videoHtml')[0].pause();

                <?php if (in_array($login_fabrica, [169, 170])) { ?>
                        let today = new Date();
                        today = today.getDate();
                        let checkLocal = localStorage.setItem("modalCovidMidea",today);
                <?php } else { ?>
                        showImagem();
                <?php } ?>
              }
            }
        //});

        };

    function showImagem(){
        var modal_imagem = $("#modal_imagem_mondial")[0];
        var modal_close  = $(".close_imagem")[0];

        if(modal_imagem != ""){
            modal_imagem.style.display = "block";
        }

        modal_close.onclick = function() {
            modal_imagem.style.display  = "none";
        }

        window.onclick = function(event) {
          if (event.target == modal_imagem) {
            modal_imagem.style.display  = "none";
          }
        }
    }
</script>
<?php
}
?>
<?php if($login_fabrica == 104){ ?>

<script>
    function confirmacomunicado(comunicado){
            $.ajax({
                type: "GET",
                url: "menu_inicial.php",
                data: 'comunicado='+comunicado,
                success: function(data){
                    window.location.reload()
                }
            });
        }
</script>
<?php 
    if(isset($_GET['comunicado'])){
        $comunicado_lido = $_GET['comunicado'];
        $sql = "UPDATE tbl_comunicado SET 
                        ativo = 'f' 
                    WHERE  comunicado = $comunicado_lido
                    AND    posto      = $login_posto";

        $res = @pg_exec ($con,$sql);

        $campoLeitor = ", leitor";
        $valueLeitor = ", '{$jsonLeitor}'";

        $sql = "INSERT INTO tbl_comunicado_posto_blackedecker (comunicado, posto, data_confirmacao, fabrica) 
                VALUES ($comunicado_lido, $login_posto, CURRENT_TIMESTAMP, $login_fabrica)";

        $res = @pg_exec ($con,$sql);
        $erro = pg_last_error($con);
        
        if(empty($erro)){
            echo "OK";
        } else {
            echo $erro;
        }
        exit;
    }
}

?>

<div id="center_menu">
    <div class="message">
        <div class="main2 tar">
            <a class="close"><i class="btn fa <?=$avisoAberto?>"></i></a>
        </div>
        <div class="main2 p-tb tac the-info" <?=$avisoAberto=='fa-plus'?'style="display:none"':''?>>
    <?php

        $comunicadoHTML = sprintf('
            <hi class="title">%s</hi>
            <section style="text-align:left" class="expanded">
                %s
                <br>
                %s
            </section>',
            '<center>
                <bold>'.
                    $comunicado_titulo.
                '</bold>
            </center>',
            $comunicado_mensagem,
            $comunicado_link
        );
        
        if (!isFabrica(35) or (isFabrica(35) and $login_pais == 'BR')) {
            echo $comunicadoHTML;
        } 
        if($login_fabrica == 104 and $tipo == 'pedido_faturado_parcial'){
            echo '<a class="btn" onclick="confirmacomunicado('.$comunicado.')">Li e confirmo</a>';
        }
        if (isFabrica(161) && $avisoPedido) {
            echo sprintf($avisoPedido);
        } ?>
            <hr>
            <a class="btn read-more">Esconder</a>
        </div>
    </div>
<?php
if (isFabrica(151)) {

    $sql = "SELECT DISTINCT
                   tbl_faturamento.faturamento, tbl_faturamento.nota_fiscal, tbl_faturamento.serie,
                   tbl_faturamento.emissao, faturamento_total_peca.total_peca
              FROM tbl_faturamento_item
        INNER JOIN tbl_faturamento
                ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
        INNER JOIN (
                     SELECT SUM(tbl_faturamento_item.qtde) AS total_peca,
                            tbl_faturamento_item.faturamento
                       FROM tbl_faturamento_item
                 INNER JOIN tbl_faturamento
                         ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                        AND tbl_faturamento.fabrica     = {$login_fabrica}
                   GROUP BY tbl_faturamento_item.faturamento
                   ) AS faturamento_total_peca
                ON faturamento_total_peca.faturamento = tbl_faturamento.faturamento
        INNER JOIN tbl_pedido
                ON tbl_pedido.pedido  = tbl_faturamento_item.pedido
               AND tbl_pedido.fabrica = {$login_fabrica}
        INNER JOIN tbl_tipo_pedido
                ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
               AND tbl_tipo_pedido.fabrica     = {$login_fabrica}
             WHERE tbl_faturamento.fabrica     = {$login_fabrica}
               AND tbl_pedido.posto            = {$login_posto}
               AND tbl_faturamento_item.qtde   > 0
               AND tbl_tipo_pedido.garantia_antecipada IS TRUE
               AND tbl_faturamento_item.qtde_quebrada  IS NULL";

    if (pg_num_rows($resConferencia) > 0) {
        $countConferencia = pg_num_rows($resConferencia);
?>
        <table class='nota_fiscal_conferencia'>
            <thead>
                <tr class='titulo_coluna'><th colspan="2" ><?=traduz("Existém notas para serem conferidas o recebimento de peça(s).")?></th></tr>
                <tr class='titulo_coluna'>
                    <th><?=traduz("Nota Fiscal")?></th>
                    <th><?=traduz("Quantidade de Peças")?></th>
                </tr>
            </thead>
<?php
        for ($i = 0; $i < $countConferencia; $i++) {
            $nota_fiscal = pg_fetch_result($resConferencia, $i, "nota_fiscal");
            $total_peca = pg_fetch_result($resConferencia, $i, "total_peca"); ?>
            <tr>
                <td><?=$nota_fiscal?></td>
                <td><?=$total_peca?></td>
            </tr>
        <?php } ?>
        </table>
<?php
    }
}

/*
if($condicao_descricao=="30/60/90DD (financeiro de 3%)"){ $condicao ="55"; }
    if($condicao_descricao=="30/60DD (financeiro de 1,5%)"){ $condicao ="53"; }
    if($condicao_descricao=="30DD (sem financeiro)"){ $condicao ="51"; }
    if($condicao_descricao=="45DD (financeiro 1,5%)"){ $condicao ="52"; }
    if($condicao_descricao=="60/90/120DD (financeiro 6,1%)"){ $condicao ="57"; }
    if($condicao_descricao=="60/90DD (financeiro 4,5%)"){ $condicao ="73"; }
    if($condicao_descricao=="60DD (financeiro 3%)"){ $condicao ="54"; }
    if($condicao_descricao=="90DD (financeiro 6,1%)"){ $condicao ="56"; }*/

if (isFabrica(3)) {
    $hoje = date("Y-m-d");
    $sql3="SELECT SUM(data_expira_senha-'$hoje') AS data FROM tbl_posto_fabrica WHERE posto=$login_posto AND fabrica=$login_fabrica;";
    $res3 = pg_query($con, $sql3);

    if (pg_num_rows($res3) > 0) {
        $data_expira_senha = pg_fetch_result($res3,0,data);

        // HD-3694944
        if ($data_expira_senha < 15) {
            // Efeito para mostrar o relógio de areio mais cheio conforme chega a data limite
            if ($data_expira_senha <= 1) {
                $des_icon = 'end';
            } elseif ($data_expira_senha <= 8) {
                $des_icon = 'half';
            } else {
                $des_icon = 'start';
            }

            $pwdExpMsg = traduz(array(
                'sua.senha.ira.expirar.em', $data_expira_senha, 'dias', '. ',
                'clique.aqui.para.cadastrar.uma.senha.nova'
			));
			echo "<a href='alterar_senha.php'>";
			echo $cabecalho->alert($pwdExpMsg, 'default', "hourglass-$des_icon");
			echo "</a>";
        }
    }
}

if (isFabrica(80, 81, 114)) {

    $mostra_grafico = false;
    $anterior       = 0;
    $os_dias        = Array();

    for ($i=5; $i<36; $i+=10) {
        $sql = "SELECT DISTINCT count(posto),os,count(os_produto) AS qtde_itens
                  FROM tbl_os
                  LEFT JOIN tbl_os_produto USING(os)
                 WHERE fabrica = $login_fabrica
                   AND posto   = $login_posto
                   AND data_fechamento IS NULL
                   AND tbl_os.excluida IS NOT TRUE
                   AND data_abertura::date BETWEEN current_date - INTERVAL '$i days' AND current_date - INTERVAL '$anterior days'
                 GROUP BY posto,os
        ";

//  Mais de 30 dias...:
        if ($anterior==26) {
            $sql = "SELECT DISTINCT count(posto),os,count(os_produto) AS qtde_itens
                      FROM tbl_os
                      LEFT JOIN tbl_os_produto USING(os)
                     WHERE fabrica = $login_fabrica
                       AND posto   = $login_posto
                       AND data_fechamento IS NULL
                       AND tbl_os.excluida IS NOT TRUE
                       AND data_abertura::date < current_date-INTERVAL '25 days'
                     GROUP BY posto,os";
        }
        $res = pg_query($con, $sql);
        $os_dias[$i]["total"] = pg_num_rows($res);
        $mostra_grafico = ($mostra_grafico or $os_dias[$i]["total"] != 0);
//      if ($os_dias[$i]["total"] == 0) continue;
        $num_row = 0;
        while (is_array($row = @pg_fetch_assoc($res, $num_row++))) {
            $os_dias[$i]["sem_pecas"] += intval(($row['qtde_itens']=="0"));
        }
        if ($os_dias[$i]["total"]==0) $os_dias[$i]["sem_pecas"] = 0;
        $os_dias[$i]["com_pecas"] = $os_dias[$i]["total"] - $os_dias[$i]["sem_pecas"];
        $anterior = $i + 1;
    }
    if ($mostra_grafico) {?>

    <table align='center' id='resumoOS'>
        <thead>
        <tr class='conteudo' style='text-transform:capitalize'>
            <th><?=traduz('ate')?>...</th>
            <th><?=traduz('sem.pecas')?></th>
            <th><?=traduz('com.pedido')?></th>
            <th><?=traduz('total')?></th>
        </tr>
        </thead>
        <tbody>
<?php
        $anterior= 0;
        foreach ($os_dias as $dias => $dados) {
            echo "\t<tr title='Clique para visualizar as OS'>\n";
            echo "\t\t<td class='toggle_data' dias='$dias'>";
            echo ($dias==35)?"> 25":"De $anterior até $dias";
            echo " dias</td>\n";
            echo "\t\t<td class='toggle_data' dias='$dias'>{$dados['sem_pecas']}</td>\n";
            echo "\t\t<td class='toggle_data' dias='$dias'>{$dados['com_pecas']}</td>\n";
            echo "\t\t<td class='toggle_data' dias='$dias'>{$dados['total']}</td>\n";
            echo "\t</tr>\n";
            echo "\t<tr id='fila_data_$dias'>\n";
            echo "<td colspan='4'><div class='oculto' id='data_$dias'></div></td></tr>\n";
            $total   += $dados['total'];
            $sem     += $dados['sem_pecas'];
            $com     += $dados['com_pecas'];
            $anterior = $dias + 1;
        }
?>      <tr class='bold'>
            <td><?=traduz('totais')?>:</td>
            <td><?=$sem?></td>
            <td><?=$com?></td>
            <td><?=$total?></td>
        </tr>
        </tbody>
    </table>
<?php
        $os_dias['t']['total']     = $total;
        $os_dias['t']['sem_pecas'] = $sem;
        $os_dias['t']['com_pecas'] = $com;

    //  Agora, muntar a query para o GoogleChart...
        foreach ($os_dias as $dias => $dados) {
            $max = ($dados['total'] > $max) ? $dados['total'] : $max;
            $a_data_sem[] = $dados['sem_pecas'];
            $a_data_com[] = $dados['com_pecas'];
            $a_totais[]   = $dados['total'];
        }
        $chart_data = implode(",",$a_data_sem);
        $chart_data.= "|".implode(",",$a_data_com);
        $max = max($sem,$com);
        $max = ($max<10) ? 10 : intval(intval(($max)/pow(10,strlen($max)-1))+1)*pow(10,strlen($max)-1);
        $chart_height = 100*strlen($max);

        $chart = "https://chart.apis.google.com/chart?";
        $chart.= "chs=640x".$chart_height."&cht=bvg&chbh=r,0.2,0.8&";       //tipo e tamanho, largura e espaço entre barras
        $chart.= "chco=00D07F|F0F07F|0000FF|FF0000|991111,008040|FF8000|7F00F0|7F3000|2F2F2F&";     //cores das barras
        $chart.= "chf=bg,lg,60,CDDDFF,1,EDEDFF,0&"; //cor de fundo
        $chart.= "chtt=OS+em+aberto&";   //  TÃ­tulo da imagem
        $chart.= "chxt=x,y,r&chxl=0:|At&#233;+5+dias|6-15+dias|16-25+dias|Mais+de+25+dias|Total|&";
        $chart.= "chdl=&lt;5+Dias+Sem+pe&#231;a|6-15+Dias+Sem+pe&#231;a|16-25+Dias+Sem+pe&#231;a|&gt;25+Dias+Sem+pe&#231;a|Total+Sem+pe&#231;as|".
                      "&lt;5+Dias+Com+pe&#231;a|6-15+Dias+Com+pe&#231;a|16-25+Dias+Com+pe&#231;a|&gt;25+Dias+Com+pe&#231;a|Total+Com+pe&#231;as&".
                      "chdlp=t&";   //  Legenda de cores...
        $chart.= "chm=N*f0*+OS,000000,0,-1,12|N*f0*+OS,000099,1,-1,12&"; // Texto em cima das barras
        // Legenda de cores... "chdl=Sem+pedido|Com+pedido&chdlp=b&"
        $chart.= "chd=t:$chart_data&chds=0,$max".
             "&chxr=1,0,$max|2,0,$max";
?>  <img id='gChart' src='<?=$chart?>' alt='Resumo de OS em aberto'>
<?php
    }
}

if ($login_fabrica == 20) {
    /**
     * - Tabela com as dez últimas peças cadastradas pela
     * fábrica com devolução obrigatória, que o posto se
     * encaixa nos requisitos de devolução
     */

    $sqlVerifica = "
        SELECT  tbl_peca.referencia ,
                tbl_peca.descricao  ,
                pecas_solicitadas.qtde
        FROM    tbl_peca
        JOIN    (
                    SELECT  tbl_lgr_peca_solicitacao.peca   ,
                            tbl_lgr_peca_solicitacao.fabrica,
                            tbl_lgr_peca_solicitacao.qtde
                    FROM    tbl_lgr_peca_solicitacao
                    WHERE   tbl_lgr_peca_solicitacao.fabrica = $login_fabrica
                    AND     (
                                tbl_lgr_peca_solicitacao.posto = $login_posto
                            OR  tbl_lgr_peca_solicitacao.cod_ibge = (
                                    SELECT  tbl_posto_fabrica.cod_ibge
                                    FROM    tbl_posto_fabrica
                                    WHERE   tbl_posto_fabrica.posto     = $login_posto
                                    AND     tbl_posto_fabrica.fabrica   = $login_fabrica
                                )
                            OR  (
                                    tbl_lgr_peca_solicitacao.estado = (
                                        SELECT  tbl_posto.estado
                                        FROM    tbl_posto
                                        WHERE   tbl_posto.posto = $login_posto
                                    )
                                AND tbl_lgr_peca_solicitacao.cod_ibge IS NULL
                                )
                            )
                ) pecas_solicitadas USING (fabrica,peca)
  ORDER BY data_input DESC
  LIMIT 10
    ";
    $resVerifica = pg_query($con,$sqlVerifica);

    if (pg_num_rows($resVerifica) > 0) {
        $resultado = pg_fetch_all($resVerifica);
?>
            <table align='center' class="pecas_devolucao">
                <thead>
                    <tr>
                        <th colspan="3" class="pecas_coluna">
                            Peças escolhidas pela <?=$login_fabrica_nome?> para devolução obrigatória, ao entrar em <b>ORDEM DE SERVIÇO</b>
                        </th>
                    </tr>
                    <tr>
                        <th class="pecas_coluna">Código</th>
                        <th class="pecas_coluna">Descricão</th>
                        <th class="pecas_coluna">Qtde</th>
                    </tr>
                </thead>
                <tbody>
<?php
        foreach($resultado as $campo => $valor){
?>
                    <tr>
                        <td style="text-align:center"><?=$valor['referencia']?></td>
                        <td style="text-align:center"><?=$valor['descricao']?></td>
                        <td style="text-align:center"><?=$valor['qtde']?></td>
                    </tr>
<?php
        }
?>
                </tbody>
            </table>
<?php
    }
}

// Verificar Hd-3653946
if ($login_fabrica == 40) { ?>
    <table>
    <tr>
        <td colspan='4' align='center' style='color:#DD0000; font-weight: bold; padding: 10px;'>
<?php
        $sql = "SELECT os
                  FROM tbl_os
                 WHERE data_abertura < NOW() - INTERVAL '20 day'
                   AND data_fechamento IS NULL
                   AND posto           =  $login_posto
                   AND fabrica         =  $login_fabrica
                   AND excluida        IS NOT TRUE

        LIMIT 1";
        @$res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            fecho(array('existem.os.em.aberto.ha.mais.de.%.dias','verificar.com.urgencia'), $con, $cook_idioma, array('20'));
            //fecho('verificar.com.urgencia', $con);
        }
        ?>
        </td>
    </tr>
    </table>
<?php }

if ($login_fabrica == 156) {
    $sqlPPA = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
    $qryPPA = pg_query($con, $sqlPPA);

    $posto_parametros_adicionais = json_decode(pg_fetch_result($qryPPA, 0, 'parametros_adicionais'), true);

    $posto_capacitacao = array();

    if (!empty($posto_parametros_adicionais)) {
        foreach ($posto_parametros_adicionais as $key => $val) {
            preg_match("/^data_capacitacao_(.*)/", $key, $matches);

            if ($matches) {
                $posto_capacitacao[$matches[1]] = $val;
            }
        }
    }

    $msg_posto_capacitacao = '';

    foreach ($posto_capacitacao as $k => $pc) {
        $dco = DateTime::createFromFormat('d/m/Y H:i:s', $pc . '00:00:00');
        $hoje = new DateTime(date('Y-m-d 00:00:00'));

        if ($dco < $hoje) {
            $msg_posto_capacitacao .= 'A validade de sua capacitação ' . $k . ' expirou em ' . $pc . ' renove o mais rápido possível.<br/>';
        } else {
            $hoje->add(new DateInterval('P30D'));

            if ($dco <= $hoje) {
                $msg_posto_capacitacao .= 'A validade de sua capacitação ' . $k . ' irá expirar em ' . $pc . ' renove o mais rápido possível.<br/>';
            }
        }
    }

    if (!empty($msg_posto_capacitacao)) {
        echo "<table>
                <tr>
                    <td colspan='4' align='center'>
                        <div style='color:#DA1818; text-decoration: none; font-weight: bold; padding: 20px 10px;'>
                            {$msg_posto_capacitacao}
                        </div>
                    </td>
                </tr>
            </table>";
    }

}

if ($login_fabrica == 122) {
    $link_wurth = "externos/callcenter/produto_fora_garantia.php?cnpj_assist=$cook_posto";
    ?>
        <tr>
        <td colspan='4' align='center'>
            <div style='color:#DA1818; text-decoration: none; font-weight: bold; padding: 20px 10px;'>
                <u><a href="<?php echo $link_wurth; ?>">CADASTRO DE PRODUTO FORA DE GARANTIA</a></u>
            </div>
        </td>
        </tr>
<?php
}

if ($login_fabrica == 168) {
    $sqlPedidosFrete = "
        SELECT  tbl_pedido.pedido,
                TO_CHAR(tbl_pedido.data,'DD/MM/YYYY HH24:MI') AS data_pedido
        FROM    tbl_pedido
        WHERE   status_pedido = 27
        AND     fabrica = $login_fabrica
        AND     posto = $login_posto
    ";
    $resPedidosFrete = pg_query($con,$sqlPedidosFrete);

    if (pg_num_rows($resPedidosFrete) > 0) {
?>
        <tr>
            <td colspan="4" align="center">
                <table id="pedidos_frete" style="margin: 0 auto; width: 400px; border-collapse: collapse;font-family:arial;">
                    <tr >
                        <th style="background-color: #DA1818; color: #FFFFFF;" colspan="2">Pedidos com Valores de Frete enviados pela fábrica</th>
                    </tr>
<?php
        while ($dadosPedidos = pg_fetch_object($resPedidosFrete)) {
?>
                    <tr>
                        <td style="text-align:center;"><a href="pedido_finalizado.php?pedido=<?=$dadosPedidos->pedido?>" target="_blank"><?=$dadosPedidos->pedido?></a></td>
                        <td style="text-align:center;"><?=$dadosPedidos->data_pedido?></td>
                    </tr>
<?php
        }
?>
                </table>
            </td>
        </tr>

<?php
    }
}

/**
 * Interação na Ordem de Serviço
 * fábrica 3 foi retirada porque não usa mais interações na Ordem de Serviço
 */
$array_interacao_os = array(11,14,24,30,35,40,45,50,51,52,72,74,80,81,86,90,91,96,101,104,114,126,127,131,132,136,172);

if ($login_fabrica >= 137 or $interacaoOsPosto) {
    $array_interacao_os = array($login_fabrica);
}

if ($login_fabrica == 156 AND $login_unico_master != "t") {
    $cond_tecnico = " AND tbl_os.tecnico = $login_unico_tecnico ";
}

if ($login_fabrica == 30) {
    $sql_comprovante = "SELECT DISTINCT ON
                            (tbl_hd_chamado_extra.os) tbl_hd_chamado_extra.os,
                            TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY HH24:MI') AS data_interacao
                        FROM tbl_hd_chamado
                            JOIN tbl_hd_chamado_extra USING(hd_chamado)
                            JOIN tbl_hd_chamado_item USING(hd_chamado)
                        WHERE tbl_hd_chamado.status = 'Exigir Comprovante' AND
                                tbl_hd_chamado.fabrica = {$login_fabrica} AND tbl_hd_chamado.posto = {$login_posto} ORDER BY tbl_hd_chamado_extra.os,data_interacao DESC";

    $res_comprovante = pg_query($con, $sql_comprovante);
    if (pg_num_rows($res_comprovante) > 0) {
    ?>
        <br />
        <table id="interacoes_comprovante" class='table_tc' style="margin: 0 auto; width: 55%;" >
            <thead>
                <tr>
                    <th style="background-color: #DA1818; color: #FFFFFF;" colspan="2" >OSs aguardando comprovante</th>
                </tr>
                <tr class='titulo_coluna'>
                    <th style="background-color: #485989; color: #FFFFFF;" >OS</th>
                    <th style="background-color: #485989; color: #FFFFFF;" >Data Interação</th>
                </tr>
            </thead>
            <tbody style="max-height: 50px; overflow-y: auto;">
            <?
            for ($i=0; $i < pg_num_rows($res_comprovante); $i++) {
                $os = pg_fetch_result($res_comprovante, $i, "os");
                $data_interacao = pg_fetch_result($res_comprovante, $i, "data_interacao");
                echo "
                    <tr ".($i > 5 ? "class='interacao_display_none'" : "").">
                        <td style='text-align: center;' ><a href='os_press.php?os={$os}' target='_blank' >{$os}</a></td>
                        <td style='text-align: center;' >{$data_interacao}</td>
                    </tr>
                ";
            }
            ?>
            <tr style="background-color: #494994; color: #FFFFFF;">
                <td colspan="2">Atenção: O.S. só será liberada para fechamento após o recebimento de comprovante da troca</td>
            </tr>
            </tbody>
        </table>
    <?
    }
}

if (in_array($login_fabrica, $array_interacao_os)) {
	if($login_fabrica != 161){
		$cond = " AND tbl_os.finalizada IS NULL ";
	}
    $cond_programa = "";
    if (in_array($login_fabrica, [11, 172])) {
        $cond_programa = "AND tbl_os_interacao.programa <> '/assist/interacao_os.php'";
    }

        $sqlInteracoesPendentes = "
            SELECT DISTINCT ON (tbl_os.os) tbl_os.os, tbl_os.sua_os, TO_CHAR(tbl_os_interacao.data, 'DD/MM/YYYY HH24:MI') AS data_interacao, (select admin from tbl_os_interacao where os = tbl_os.os and fabrica = $login_fabrica and admin is not null order by data desc limit 1) AS admin
            FROM tbl_os_interacao
            INNER JOIN tbl_os ON tbl_os.os = tbl_os_interacao.os AND tbl_os.fabrica = {$login_fabrica} AND tbl_os.posto = {$login_posto}
    WHERE tbl_os_interacao.interno IS NOT TRUE
	    $cond
	    AND tbl_os_interacao.posto = $login_posto
	    AND tbl_os_interacao.fabrica = $login_fabrica
            AND tbl_os_interacao.confirmacao_leitura IS NULL
            AND tbl_os_interacao.admin IS NOT NULL
            AND tbl_os_interacao.data > CURRENT_TIMESTAMP - INTERVAL '1 YEAR'
            AND tbl_os.excluida is not true
            AND tbl_os.cancelada is not true
            $cond_tecnico
            $cond_programa
            ORDER BY tbl_os.os, tbl_os_interacao.data DESC ";
            
        $resInteracoesPendentes = pg_query($con, $sqlInteracoesPendentes);

        if (pg_num_rows($resInteracoesPendentes) > 0) {
            if ($login_fabrica == 91)
            {
                $alertaParaPendencias = true;
            }
        ?>
                    <br />

                    <style>

                    #interacoes_pendentes tr.interacao_display_none {
                        display: none;
                    }

                    </style>

                    <script>

                    $(function() {

                        $("#mostrar_todas_oss_interacoes_pendentes").click(function() {
                            $("#interacoes_pendentes tr.interacao_display_none").each(function() {
                                $(this).removeClass("interacao_display_none");
                            });

                            $("#interacoes_pendentes > tfoot").remove();
                        });

                    });

                    </script>

                    <table id="interacoes_pendentes" class='table_tc' style="margin: 0 auto; width: 55%;" >
                        <thead>
                            <tr>
                                <th style="background-color: #DD0010; color: #FFFFFF; text-align: center;" colspan="2" ><?= traduz('OSs com interações pendentes, favor visualizar cada OS e tomar a devida ação descrita') ?></th>
                            </tr>
                            <tr class='titulo_coluna'>
                                <th style="text-align: center;" ><?= traduz('OS') ?></th>
                                <th style="text-align: center;" ><?= traduz('Data Interação') ?></th>
                            </tr>
                        </thead>
                        <tbody style="max-height: 50px; overflow-y: auto;">
        <?php
        $c = 1;

        while ($osInteracaoPendente = pg_fetch_object($resInteracoesPendentes)) {
            if($osInteracaoPendente->admin == ''){
                continue;
            }
            echo "
                <tr ".($c > 5 ? "class='interacao_display_none'" : "").">
                    <td style='text-align: center;' ><a href='os_press.php?os={$osInteracaoPendente->os}' target='_blank' >".((empty($osInteracaoPendente->sua_os)) ? $osInteracaoPendente->os : $osInteracaoPendente->sua_os)."</a></td>
                    <td style='text-align: center;' >{$osInteracaoPendente->data_interacao}</td>
                </tr>
            ";

            $c++;
        }
        ?>
                        </tbody>
        <?php
        if (pg_num_rows($resInteracoesPendentes) > 5) {
        ?>
                            <tfoot>
                                <tr>
                                    <th id="mostrar_todas_oss_interacoes_pendentes" style="background-color: #485989; color: #FFFFFF; cursor: pointer;" colspan="2" ><?= traduz('Mostrar todas as OSs') ?></th>
                                </tr>
                            </tfoot>
        <?php
        }
        ?>
                    </table>
                    <br />
<?php
		}
		 if ($login_fabrica == 24) {
                        include_once('plugins/fileuploader/TdocsMirror.php');
                        $tDocs = new TdocsMirror();

                        $queryAnexoRetirado = " SELECT tbl_os.sua_os,
                                                       tbl_os.os,
                                                       tbl_auditoria_os.auditoria_os,
                                                       TO_CHAR(tbl_os_troca.data, 'dd/mm/YYYY') AS troca_data,
                                                       (SELECT tdocs_id 
                                                        FROM tbl_tdocs
                                                        WHERE contexto = 'comprovante_retirada'
                                                        AND referencia_id = tbl_os.os) AS link
                                                FROM tbl_os
                                                JOIN tbl_os_troca USING(os)
                                                LEFT JOIN tbl_auditoria_os 
                                                    ON (tbl_os.os = tbl_auditoria_os.os 
                                                        AND tbl_auditoria_os.auditoria_status = 3 
                                                        AND tbl_auditoria_os.liberada IS NULL 
                                                        AND tbl_auditoria_os.reprovada IS NULL 
                                                        AND tbl_auditoria_os.observacao = 'PRODUTOS TROCADOS NA OS')
                                                LEFT JOIN tbl_tdocs on referencia_id = tbl_os.os
                                                WHERE tbl_os.fabrica = {$login_fabrica}
                                                AND tbl_os.posto = {$login_posto}
                                                AND tbl_os.finalizada IS NULL
                                                AND tbl_os.excluida IS NOT TRUE
                                                AND tbl_os_troca.fabric = {$login_fabrica}
                                                AND tbl_os_troca.gerar_pedido IS TRUE";
                        
                        $resAnexoRetirado = pg_query($con, $queryAnexoRetirado); 

                        if (pg_num_rows($resAnexoRetirado) > 0) { 
                            ?>

                            <table id="os_pendentes_auditado" class='table_tc' style="margin: 0 auto; width: 55%;" >
                            <thead>
                                <tr>
                                    <th style="background-color: #DD0010; color: #FFFFFF; text-align: center;" colspan="4" ><?= traduz('OSs com Troca de Produto Aguardando Anexo/Conferência do Comprovante de Entrega ao Cliente.') ?></th>
                                </tr>
                                <tr class='titulo_coluna'>
                                    <th style="text-align: center;" ><?= traduz('OS') ?></th>
                                    <th style="text-align: center;" ><?= traduz('Data Troca') ?></th>
                                    <th style="text-align: center;" ><?= traduz('Situação') ?></th>
                                    <th style="text-align: center;" ><?= traduz('Anexo Comprovantes') ?></th>
                                </tr>
                            </thead>
                            <tbody style="max-height: 50px; overflow-y: auto;">

                            <?php while ($osAnexoRetirado = pg_fetch_object($resAnexoRetirado)) { 

                                    $situacao = 'Comprovante Pendente';

                                    if (strlen($osAnexoRetirado->link) > 0) {

                                        $situacao = 'Aguardando Avaliação do Fabricante';
                                    }

                                ?>

                                <tr class=''>
                                    <td style='text-align: center;' >
                                        <a href='os_press.php?os=<?=$osAnexoRetirado->os?>' target='_blank' >
                                            <?= $osAnexoRetirado->os ?>
                                        </a>
                                    </td>
                                    <td style='text-align: center;' ><?= $osAnexoRetirado->troca_data ?></td>
                                    <td style='text-align: center;' ><?= $situacao ?></td>
                                    <?php  if (strlen($osAnexoRetirado->auditoria_os) > 0 and strlen($osAnexoRetirado->link) > 0) { ?>
                                        <td style='text-align: center;' ><a class="btn btn-primary" href="<?= $tDocs->get($osAnexoRetirado->link)['link'] ?>">Baixar Comprovante</a></td>
                                    <?php } else { ?>
                                        <td style='text-align: center;' ><?= traduz('Pendente') ?></td>
                                    <?php } ?>
                                </tr>

                            <?php } ?>
                            
                            </tbody>
                        </table>
                        <br><br>

                    <?php } 
                    }


}

/* FIM Interação na Ordem de Serviço */
/* INICIO Interções */
if ($telecontrol_distrib) {
    include "lista_interacoes_pendente.php";
}
/* FIM Interções*/

/*
 * Tabela de agendamentos pendentes de confirmação dos postos autorizados
 */
if (in_array($login_fabrica, array(35,169,170,171,178,183,190,195))) {
    include_once "agendamentos_pendentes.php";
}

/* 85 */
if ($login_fabrica == 85) {

   $sql_pecas_promocao = "SELECT
                            DISTINCT
                            tbl_peca.peca,
                            tbl_tabela_item.preco,
                            tbl_peca.referencia,
                            tbl_peca.qtde_disponivel_site,
                            tbl_peca.descricao
                        FROM tbl_tabela_item
                        JOIN tbl_peca ON tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela IN (
                                SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa is true AND descricao = 'LOJA VIRTUAL'
                            )
                        RIGHT JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca
                        WHERE
                            tbl_peca.ativo is true
                            AND tbl_peca.promocao_site is true
                            AND tbl_peca.at_shop is true
                            AND tbl_peca.fabrica = $login_fabrica
                        ";
    $res_pecas_promocao = pg_query($con, $sql_pecas_promocao);

    if(pg_num_rows($res_pecas_promocao) > 0){

        ?>
        <style tyle="text/css">
            .table_peca_promocao{
                width: 99%;
                margin: 0 auto;
                border: 1px solid #ccc;
            }
            .table_peca_promocao th{
                background-color: #485989;
                color: #fff;
                font: 13px arial;
                font-weight: bold;
            }

            .table_peca_promocao td{
                font: 13px arial;
            }

            .table_peca_promocao td a{
                color: #000;
                text-decoration: none;
            }

            .table_peca_promocao td a:hover{
                color: #485989;
            }
        </style>
        <?php

        $table_promocao = "
        <table class='table_peca_promocao' cellpadding='5px' cellspacing='1px'>
            <thead>
                <tr>
                    <th colspan='3'>LISTA DE PRODUTOS EM PROMO&Ccedil;&Atilde;O</th>
                </tr>
                <tr>
                    <th>".traduz('referencia')."</th>
                    <th>".traduz('descricao')."</th>
                    <th>".traduz('preco')."</th>
                </tr>
            </thead>
            <tbody>
        ";

        $i = 0;

        while ($data = pg_fetch_object($res_pecas_promocao)) {

            $peca               = $data->peca;
            $referencia         = $data->referencia;
            $descricao          = $data->descricao;
            $preco              = $data->preco;
            $qtde_disponivel    = $data->qtde_disponivel_site;

            if(strlen($qtde_disponivel) > 0 && $qtde_disponivel != 0){

                $preco = number_format($preco, 2, ',', '');

                $color = ($i%2 == 0) ? "#e6e6e6" : "#ffffff";

                $table_promocao .= "
                    <tr bgcolor='$color'>
                        <td><a href='lv_detalhe.php?cod_produto=$peca' target='_blank'>$referencia</a></td>
                        <td><a href='lv_detalhe.php?cod_produto=$peca' target='_blank'>$descricao</a></td>
                        <td align='center'>R$ $preco</td>
                    </tr>
                ";

            }

            $i++;
        }

        $table_promocao .= "
            </tbody>
        </table>
        <br /> <br />
        ";

        echo $table_promocao;

    }
}


$cards = include(MENU_DIR.'menu_inicial.php'); 

if ($login_fabrica == 161) {
    $sqlPostoInter = "SELECT posto 
		   FROM tbl_posto_linha 
		   JOIN tbl_linha USING(linha)
                   WHERE tbl_posto_linha.posto = {$login_posto}
                   AND tbl_linha.nome = 'INTERNACIONAL';";

    $resPostoInter = pg_query($con, $sqlPostoInter); 

    if (pg_fetch_result($resPostoInter, 0, posto) == $login_posto) {

        unset($cards['extrato']);
    }
}

if ($login_fabrica == 42) {

    $cards['pedido']['links']['default'] = 'menu_pedido.php';
}

echo '<div id="menu_inicial">'.$cabecalho->cardsMenu($cards).'</div>';

if ($login_fabrica==1) {
    echo "<table width='740' border='0' cellspacing='2' cellpadding='0' class='tabela' align='center'><tr align='center'><td width='740'>";
    //tabela antiga aqui em baixo
    $sql =  "SELECT tbl_posto_fabrica.tipo_posto,
                    tbl_posto_fabrica.categoria
            FROM    tbl_posto_fabrica
            WHERE   tbl_posto_fabrica.posto = $login_posto
            AND     tbl_posto_fabrica.fabrica = $login_fabrica";
    // Arquivo do link não existe. HD-3653946.
    // $res = pg_exec($con,$sql);

    if (false and pg_numrows($res) > 0) {
        $tipo_posto = trim(pg_result($res,0,tipo_posto));
        $categoria = trim(pg_result($res,0,categoria));

        // if ($tipo_posto == "364" || ($categoria == "Locadora" || $categoria == "Locadora Autorizada"))
        if (false) {
            $link = "http://ww2.telecontrol.com.br/assist/admin/documentos/politica_dewalt_rental_2.pdf";
    ?>

        <div id="leftCol" bgcolor='#FFCC66'>
            <div class="contentBlockMiddle" style="width: 610;">
                <a href="<?=$link?>" target="_blank"><font face="Verdana, Tahoma, Arial" size="6">Pol&iacute;tica Rental</font></a><br>
                <!-- // <font face="Verdana, Tahoma, Arial" size="3" color="#63798D"><?php fecho("informe-se.sobre.o.que.e.o.projeto.locacao",$con);?></font><br> -->
                <!--<br>
                <a href="http://www.blackdecker.com.br/locacao/comparativo-concorrencia.pdf" target="_blank"><?php fecho("comparativo.com.a.concorrencia",$con);?></a><br>
                <font face="Verdana, Tahoma, Arial" size="2" color="#63798D"><?php fecho("veja.um.comparativo.entre.a.concorrencia",$con);?></font><br>
                <br>
                <a href="http://www.blackdecker.com.br/locacao/informacao-manutencao.pdf" target="_blank"><?php fecho("informacoes.sobre.manutencoes",$con);?></a><br>
                <font face="Verdana, Tahoma, Arial" size="2" color="#63798D"><?php fecho("informe-se.sobre.as.manutencoes",$con);?></font><br>
                <br>
                <a href="http://www.blackdecker.com.br/locacao/precos.pdf" target="_blank"><?php fecho("precos.de.maquinas.e.acessorios",$con);?></a><br>
                <font face="Verdana, Tahoma, Arial" size="2" color="#63798D"><?php fecho("precos.de.maquinas.e.acessorios",$con);?></font><br>
                <br>
                <a href="http://www.blackdecker.com.br/locacao/pecas-estoque.pdf" target="_blank"><?php fecho("pecas.em.garantia.e.estoque.minimo",$con);?></a><br>
                <font face="Verdana, Tahoma, Arial" size="2" color="#63798D"><?php fecho("confira.quais.as.pecas.estao.em.garantia.e.a.quantidade.em.estoque.minima",$con);?></font><br>
                <br>
                <a href="http://www.blackdecker.com.br/locacao/vista-explodida.pdf" target="_blank"><?php fecho("vista.explodida",$con);?></a><br>
                <font face="Verdana, Tahoma, Arial" size="2" color="#63798D"><?php fecho("arquivo.da.vista.explodida.e.relacao.de.pecas",$con);?></font><br>
                <br>
                <a href="http://www.blackdecker.com.br/vistas_dw.php" target="_blank"><?php fecho("vista.explodida.da.linha.dewalt",$con);?></a><br>
                <font face="Verdana, Tahoma, Arial" size="2" color="#63798D"><?php fecho("arquivo.da.vista.explodida.e.relacao.de.pecas.da.linha.dewalt",$con);?></font>-->
            </div>
        </div>

    <?php
        }
    }

    echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'class='tabela' >";
    echo "<tr>";
    echo "<td colspan='2' class='conteudo' align='center'>";

    echo "<table width='200'  border='0'cellpadding='0' valign='top'>
    <!-----
        <tr><td class='menu_title'><a href='promocao.php' class='conteudo'>".mb_strtoupper(traduz("promocoes"))."</a><br><a href='promocao.php' class='conteudo'>".traduz("compre.parafusadeira.para.utilizar.em.sua.oficina",$con)."</a>
    </td>
    </tr>
    ---->
    </table>";
    echo "</td ><td colspan='2' class='conteudo'>";
    
    if ($login_fabrica == 1) {
        $calendario_fiscal = traduz("calendario.fiscal");
        $calendario_fiscal = mb_strtoupper(utf8_encode($calendario_fiscal));
        $calendario_fiscal = utf8_decode($calendario_fiscal); 
    } else {
        $calendario_fiscal = mb_strtoupper(traduz("calendario.fiscal"));
    }

    echo "<table width='200'  border='0' cellpadding='0' class='contentBlockLeft' valign='top'>
    <tr><td>
    <center><font size='1'><a href='http://www.blackanddecker.com.br/xls/Calendário fiscal 2016.pdf' target='_blank'><b>".$calendario_fiscal."</b></a></font><br>
    <font size='1' color='#63798D'>".traduz("para.uma.maior.programacao.dos.pedidos.de.pecas.e..acessorios.consulte.o.nosso")." <b><a href='http://www.blackanddecker.com.br/xls/Calendário fiscal 2016.pdf' target='_blank'>".traduz("calendario.fiscal",$con)."</a></b>, ".traduz("que.contem.a.data.limite.para.o.envio.de.pedidos.para.a.black.&.decker.na.semana.do.fechamento",$con)." <b>".traduz("periodo.do.mes.que.nao.recebemos.pedidos.e.nao.faturamos",$con)."</b></font></center></tr></td>
    </table>";

    echo "</td ><td colspan='2' class='conteudo'>";

    echo "<table width='200'  border='0' cellpadding='0' class='contentBlockLeft' valign='top'>
    <tr><td> ";
    #Retirado a pedido da Fabiola HD 241865
    #echo "<a href='peca_faltante.php'><font color='ff0000' size='2'><B>".traduz("informe.a.black.&.decker")."</B></font></a></center><br><font size='2' color='#63798D'><center>".traduz("informe.a.black.&.decker.quais.equipamentos.estao.parados.em.sua.oficina.por.falta.de.pecas",$con)."</center></font>";
    echo "</tr></td>
    </table>";

    echo "</td ></tr>";
    echo "</table>";

//  'Fale Conosco' da Black&Decker
        if ($login_fabrica == 1) {
?>
            
            <table width='100%' cellpadding='5' cellspacing='5' border='1' >
        <?php } else { ?>
            <table width='100%' cellpadding='5' cellspacing='0' border='0' >
        <? } ?>
                <caption class='menu_title'
                         style='font-weight:bold;color:white;text-align:left;height:16px;padding:2px 0 0 1ex;font-size:10px;border-radius:4px;'>
                    <?=mb_strtoupper(traduz("fale.conosco"))?>
                </caption>
    <br>
    <tr><?php
        $sql = "SELECT * from tbl_fale_conosco ORDER BY ordem";
        $res = pg_query($con,$sql);

        for ($i=1, $j=0; $i<=pg_num_rows($res); $i++, $j++) {
            $ordem     = pg_fetch_result($res,$j,ordem);
            $descricao = pg_fetch_result($res,$j,descricao);
            $descricao = str_replace('Contato via Chamado', '<a href="helpdesk_cadastrar.php">Contato via Chamado</a><br>', $descricao);
            echo "<td  valign='top' id='$ordem' width='33%'>";
            echo "$descricao</TD>";

            if ($i > 0 and $i%3 == 0) {
                echo "</tr><tr>";
            }
        } ?>
                </tr>
            </table>
        </td>
    </tr>
 </table>
<?php
}

if (in_array($login_fabrica, array(11,172))) {//HD 34540 2/9/2008
    $sql = "SELECT * FROM tbl_admin
             WHERE fabrica = $login_fabrica
               AND tela_inicial_posto IS TRUE
               AND ativo              IS TRUE
          ORDER BY nome_completo";
    $res = pg_exec($con, $sql);

    if (pg_numrows($res)>0) {
        echo "<table width='98%' cellpadding='3' cellspacing='0' style='margin: 0 auto; border: 1px solid #cccccc;'>";
        echo "<tr>";
        echo "<td colspan='5' bgcolor='$cor' class='conteudo' style='text-transform: uppercase; padding-left: 10px;'>
            <font size='1' face='Arial' color='#ffffff'><b>".strtoupper(traduz("contatos.uteis"))."</b></font>";
        echo "</td >";
        echo "</tr><br>";
        for($i=0; $i<pg_numrows($res); $i++){
            $admin              = pg_result($res, $i, admin);
            $nome_completo      = pg_result($res, $i, nome_completo);
            $fone               = pg_result($res, $i, fone);
            $email              = pg_result($res, $i, email);
            $responsabilidade   = pg_result($res, $i, responsabilidade);
            $tela_inicial_posto = pg_result($res, $i, tela_inicial_posto);

            if($i==0){
                echo "<TR>";
                $X=0;
            }
            echo "<td style='padding-left: 10px;'>
                <font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>
                <A HREF='mailto:$email'>$nome_completo</A><BR>";
            if(strlen($responsabilidade)>0) echo $responsabilidade . "<BR>";
            if(strlen($email)>0)            echo $email . "<BR>";
            if(strlen($fone)>0)             echo $fone;
            echo "</font>
                </TD>";
            if ($X++==2) {
                echo "</TR>";
                $X=0;
            }
        }
        echo "</table> <br /> <br />";
    }
}

// Banner HD Makita
if ($login_fabrica == 42) { ?>
    <div>
        <div style="text-align: center;">
            <img src="imagens/faq_makita_inicio.jpg" onClick='window.open("helpdesk_listar.php");' style="cursor:pointer; "  /><br/><br/>

        </div>
    </div>
<?php }

if ($_POST["naoBanner"]) {
    $posto = (int)$_POST["posto"];

    if (strlen($posto) > 0) { //banner não importa a fabrica por isso a fabrica 10 fixo
        $sql = "SELECT posto_alteracao FROM tbl_posto_alteracao WHERE fabrica = 10 AND posto = $posto";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $sql = "UPDATE tbl_posto_alteracao
                       SET banner        = FALSE,
                           valida_banner = 'A',
                           data_alterado = CURRENT_TIMESTAMP
                     WHERE fabrica = 10 AND posto = $posto";
            $res = pg_query($con, $sql);
        } else {
            $sql = "INSERT INTO tbl_posto_alteracao (
                        posto, fabrica, banner,valida_banner
                    ) VALUES (
                        $posto, 10, false, 'B'
                    )";
            $res = pg_query($con, $sql);
        }
    }
    exit;
}

if ($banner_ok === 'imagem' or $banner_ok === false) { ?>
                <div style="text-align: center;">
                    <strong><?=traduz('N&atilde;o perca tempo, cadastre-se j&aacute;!')?></strong> <br />
                    <a style='text-decoration:none;border:0' href='externos/autocredenciamento_new.php' target='_new'>
                        <img src="imagens/autocredenciamento.jpg" style="cursor:pointer; width: 500px;box-shadow: 0 0 2px black;" alt="Telecontrol" />
                    </a>
                </div>
<?php }

if ($login_fabrica <> 20) { ?>
    <!--
    <br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;
    <div style='float:left'>
        <script type='text/javascript'>
        <!--
        google_ad_client = 'pub-4175670423523903';
        /* site telecontrol */
        google_ad_slot = '1731533565';
        google_ad_width = 120;
        google_ad_height = 90;
        //-->
    <!--
        </script>
        <script type='text/javascript' src='https://pagead2.googlesyndication.com/pagead/show_ads.js'></script>
    </div> -->
<?php } ?>
</div>
<?php 
$plugins = array(
    "shadowbox"   
);

if(strtotime(date('Y-m-d')) <= strtotime('2020-05-22')){  
	$plugins[] = "bootstrap3";
}

include("plugin_loader.php");
$dadosPost = $objSeguranca->getPostoFabrica($login_posto,$login_fabrica);
$xxvalidaSenhaAntiga = $objSeguranca->getAlteracaoSenha($dadosPost["posto_fabrica"]);
?>
<script type="text/javascript">

<?php 
    
    $altera_senha = false;

    if (isset($xxvalidaSenhaAntiga) && $xxvalidaSenhaAntiga && !isset($login_unico) && !isset($_COOKIE["senha_posto_skip"])){
        $altera_senha = true;
    }

    if($login_fabrica == 91 && isset($cookie_login['cook_admin']) && !empty($cookie_login['cook_admin'])){
        $altera_senha = false;
    }
?>

<?php if ($altera_senha) { ?> 
 
    window.onload = function() {   

        Shadowbox.init({
            skipSetup: true
    });


        carregaBoxAlterarSenha();
    }
    
<?php }?>
function retornaLink(sucesso = false) {
        if (!sucesso) {
            gravaAlterarDepois();
        }
        window.location = 'menu_inicial.php';

    }
    function gravaAlterarDepois() {
        $.ajax("menu_inicial.php", {
            type: 'POST',
            async: false,
            data: {
                ajax_altera_depois: true
            }
        }).done(function (response) {

            response = JSON.parse(response);
            if (response.erro == true) {
                return false;
            }
            return true;

        });


        return false;
    }
    function carregaBoxAlterarSenha() {
        Shadowbox.init({
            skipSetup: true
        });

        Shadowbox.open({
            content : "modal_altera_senha.php?tipo=login",
            player: 'iframe',
            title : "Alterar senha",
            width   :   600,
            height  :   400,
            options: {
                modal: true,
                enableKeys: false,
                displayNav: false
            }
        });
    }

<?php
if(strtotime(date('Y-m-d')) <= strtotime('2020-05-25')){   
?>

	function showComunicadoFeriado(){
		Shadowbox.init({
		         skipSetup: true
		});

		Shadowbox.open({
		content: "<div class='alert alert-warning' style='text-align:left'><center><h2><b>Comunicado</b></h2></center><br>A Telecontrol comunica que devido a antecipação do feriado Estadual de 09 de Julho não haverá expediente no dia 25 de Maio. <br>Voltaremos às nossas atividades normalmente no dia 26 de Maio a partir das 08:00hs.</b></div>",
			player: "html",
			width: 600,
			height: 180,
			options: {
			onClose: function(){                                            
					var today = new Date();
					today = today.getDate();
					var checkLocal = localStorage.setItem("modalFeriadoTc",today);
				} 
			}
		});
	}	

	window.onload = function() {
		var today = new Date();
		today = today.getDate();

		var checkLocal = localStorage.getItem("modalFeriadoTc");
		if(checkLocal == null){
			showComunicadoFeriado();
		}else{
			if(checkLocal != today){
				showComunicadoFeriado();
			} 
		}
	}
<?php
	}
?>

</script>


<?php
if ($alertaParaPendencias == true) { ?>
        <script>
 window.onload = function() { 
//        $(function(){
	Shadowbox.init();   
         $("a").click(function(){
                var url = $(this).attr("href");
                exibeAlertaOsPendente(url);
            });
        //});

        /* var a = document.querySelectorAll("a");

        a.forEach.call(a, function(e) {
            e.addEventListener("click", function() {
                exibeAlertaOsPendente();
            }, false);
        }); */

        setTimeout(function(){
	Shadowbox.init();   
            exibeAlertaOsPendente();
        }, 1000);

        };
    </script>
<?php } ?>
</div>
</center>
<?php if ($login_fabrica == 35 ){ ?>
	<link rel='stylesheet' type='text/css' href='plugins/shadowbox_lupa/shadowbox.css' />  
	<script src='plugins/shadowbox_lupa/shadowbox.js'></script>
<?php } ?>
<?php if ($login_fabrica <> 87) {?>
    </div>
<?php
    include "rodape.php";
}?>
</body>
</html>

