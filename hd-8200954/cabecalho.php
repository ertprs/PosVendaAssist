<?php

// Este arquivo não pode ser chamado diretamente
if ($_SERVER['SCRIPT_FILENAME'] == __FILE__) {
    header('HTTP/1.1 403 Forbidden');
    die("<h2 style='color: white; background: darkred; text-align: center'>Este <i>script</i> não pode ser executado diretamente.</h2>");
}

/**
 * Carrega e inicializa a classe da barra de navegação, menu e cabeçalho.
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'regras/menu_posto/menu.helper.php';

$layoutCSS = $layoutCSS ? : 'FMC'; // inicializa com FMC se não tem valor anterior.
$navbar    = include(MENU_DIR . 'menu_array.php');
$cabecalho = new MenuPosto(reset($navbar), $layoutCSS);

if ($login_fabrica <> 87 && !in_array($login_posto,[6359,4311]) && empty($cookie_login['cook_admin'])) {
	$sql = "SELECT pesquisa FROM tbl_pesquisa WHERE pesquisa = 677 AND ativo";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){

	    $sql = "SELECT resposta FROM tbl_resposta WHERE pesquisa = 677 AND posto = $login_posto AND data_input::date = CURRENT_DATE";
	    $res = pg_query($con,$sql);

	    if(pg_num_rows($res) == 0){
		header("Location: login.php");
	    }
	}
}

if ($existe_pendencia_tc and false) {
    $msg_alerts['danger'] = "<a href='posicao_financeira_telecontrol.php'>".
        traduz('existe.pendencia.no.distribuidor.telecontrol.regularize.a.sua.situacao')
        .'</a>';
}
if(!empty($login_unico)){
	$plugins = array(
		"shadowbox",
		"fancyzoom"
	);   
}
if ($login_master == 't'){

	include_once "class/tdocs.class.php";

	$amazonTC = new TDocs($con, 10);
	$documents = $amazonTC->getdocumentsByRef($login_posto, 'logomarca_posto')->attachListInfo;
	if (count($documents) > 0){
		foreach ($documents as $key => $value) {
			$link_logo_tdocs = $value['link'];
		}
	}
}
/**
 * Redirecionamentos...
 */
if (!$cookie_login['cook_login_unico'] and isFabrica(3, 148)) {
    include "autentica_validade_senha.php";
}

if (isFabrica(1)) {
    //if ($login_data_input < '2017-01-10') - HD-4236262
        include_once 'regras/1/redir_black.php';

    $sql = "SELECT *
             FROM tbl_comunicado
            WHERE tipo='Comunicado Inicial'
              AND fabrica =  $login_fabrica
              AND (posto = $login_posto OR posto IS NULL)
              AND ativo IS TRUE
              AND linha IS NULL
         ORDER BY comunicado DESC
            LIMIT 1";
    $res = pg_query($con, $sql);

    if (pg_numrows($res) == 0) {
        $sql = "SELECT *
                FROM tbl_comunicado
                JOIN tbl_posto_linha ON tbl_comunicado.linha = tbl_posto_linha.linha
                AND tbl_posto_linha.posto = $login_posto AND tbl_posto_linha.ativo IS TRUE
                WHERE tipo='Comunicado Inicial'
                AND fabrica = $login_fabrica
                AND tbl_comunicado.posto IS NULL
                AND tbl_comunicado.ativo IS TRUE ORDER BY comunicado
                DESC LIMIT 1";
        $res = pg_query($con, $sql);
    }

    $url = basename($_SERVER["PHP_SELF"]);
    
    if($_serverEnvironment !== "development") {
        $link_programa = "/assist/".$url;
        $link_programa2 = "http://posvenda.telecontrol.com.br/assist/".$url;
        $link_programa3 = "https://posvenda.telecontrol.com.br/assist/".$url;
    } else {
        $link_programa = "/PosVendaAssist/".$url;
        $link_programa2 = "http://novodevel.telecontrol.com.br/~gaspar/PosVendaAssist/".$url;
        $link_programa3 = "https://novodevel.telecontrol.com.br/~gaspar/PosVendaAssist/".$url;
    }

	$condicao_black = " AND (tbl_comunicado.destinatario_especifico = '$login_categoria' or tbl_comunicado.destinatario_especifico = '')
			AND (tbl_comunicado.tipo_posto = '$login_tipo_posto'  or tbl_comunicado.tipo_posto is null) ";

    $sqlTela = "SELECT *
             FROM tbl_comunicado
            WHERE tipo='Comunicado por tela'
              AND fabrica =  $login_fabrica
              AND (posto = $login_posto OR posto IS NULL)
			  AND   (tbl_comunicado.estado = '$login_contato_estado' OR tbl_comunicado.estado ISNULL)
              AND   (tbl_comunicado.tipo_posto = $login_tipo_posto OR tbl_comunicado.tipo_posto ISNULL)
			  AND ativo IS TRUE
				$condicao_black
              AND (programa = '$link_programa' OR programa = '$link_programa2' OR programa = '$link_programa3')
         ORDER BY comunicado DESC";
    $resTela = pg_query($con, $sqlTela);

    if (pg_numrows($res) > 0 || pg_num_rows($resTela) > 0) {
        ob_start();
        include "dropdown_mensagem.php";
        $bodyHTML .= ob_get_clean();
    }
}

// Desabilitado
if (false and isFabrica(3)) {
    if (PROGRAM_NAME != 'perguntas_britania.php' and !in_array($_SERVER['HTTP_X_FORWARDED_FOR'], array('201.0.9.216', '200.198.99.102'))) {

        $sqlX = "SELECT tbl_linha.linha
                FROM   tbl_linha
                JOIN   tbl_posto_linha   using (linha)
                JOIN   tbl_posto_fabrica using (posto)
                WHERE  tbl_posto_fabrica.fabrica = $login_fabrica
                AND    tbl_posto_linha.posto     = $login_posto
                AND    tbl_linha.linha = 3;";
        $res = @pg_exec($con,$sqlX);

        if (@pg_numrows($res) > 0) {
            $sqlX = "SELECT ja_chegaram
                    FROM   britania_fama
                    WHERE  posto     = $login_posto";
            $res = @pg_exec($con,$sqlX);
            if (strlen(@pg_result($res,0,ja_chegaram)) == 0) {
                #echo "<script language='javascript'> location.href=\"perguntas_britania.php\" ; </script>";
                #header("Location: perguntas_britania.php");
               # exit;
            }
        }
    }
}

// Bloqueia tela posto Suggar
if (isFabrica(24)) {
    $observacao = 'Extrato com mais de 60 dias sem fechamento';
    $sql = "
         SELECT desbloqueio, observacao
           FROM tbl_posto_bloqueio
          WHERE posto      = $login_posto
            AND fabrica    = $login_fabrica
            AND observacao = '$observacao'
            AND extrato IS NOT TRUE
       ORDER BY data_input DESC
          LIMIT 1";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $paginas_bloqueadas = array('os_cadastro.php','os_cadastro_troca.php','os_revenda.php','pedido_cadastro.php','os_item_new.php');
        $desb   = pg_fetch_result($res, 0, 'desbloqueio');

        if(in_array(PROGRAM_NAME, $paginas_bloqueadas) AND $desb == 'f') {
            $desabilita_tela = "Acesso Bloqueado, favor regularizar a pendência de emissão de Nota Fiscal de Mão de Obra!";
        }
    }
    include "valida_os_procon.php";
}

// Retirado 07/2015 HD 2306475
// if (isFabrica(30)) {
//     include_once 'regras/30/redir_pesquisa_purificador.php';
// }

/**
 * Includes vários
 * NOTA: para uma próxima iteração do cabeçalho, se resulta interessante para a automatização
 * do processo de dar includes, avaliar a possibilidade de criar um laço com o conteúdo do
 * diretório `regras/$login_fabrica` e ir dando include em todos eles. Assim, sem necessidade
 * de alterar o cabeçalho, seria possível adicionar ou retirar "includes" a serem inseridos
 * pelo `cabecalho.php`.
 */
if (in_array($login_fabrica, array(152,180,181,182))) {
    include_once 'regras/152/redir_contrato_posto_credenciado.php';
    include_once 'regras/180/redir_contrato_posto_credenciado.php';
    include_once 'regras/181/redir_contrato_posto_credenciado.php';
    include_once 'regras/182/redir_contrato_posto_credenciado.php';
}

// SE o posto recebeu um e-mail para se credenciar na Remington, entra aqui,
// a validação está já no include.
if (isFabrica(81))
    include_once 'pesquisa_remington.php';

include_once 'regras/10/tc_pesquisa_opiniao_posto.php';

// Regras de restrição de acesso para o Login Único
if (strlen($cook_login_unico) > 0 and is_numeric($cook_login_unico)) {
    include "restricao_lu.php";
}

if (_is_in(is_date('agora'), '2017-12-12 00:00:01::2017-12-17 23:00:01')
    and strpos($PHP_SELF, 'menu_') > 0
    and empty($_COOKIE['ComunicadoServidorPosto'])
    /*and !in_array($login_fabrica, array(
          1,   2,   5,   6,   7,  19,  20,  24,
         25,  26,  46,  52,  75,  76,  77,  78, 85, 87,
        107, 110, 112, 113, 135, 138, 142, 143,
        145, 146, 147, 148, 149, 150, 151, 152,
        153, 154, 156, 157, 158, 159, 160, 161,
        162, 163, 164, 165, 166, 167, 169, 170, 171))
    )*/ ){
    setcookie("ComunicadoServidorPosto", "lido");
    $plugins[] = "shadowbox";
    ob_start();
    /*include 'comunicado_geral_posto.html';*/
    include_once 'comunicado_servidor.php';
    $bodyHTML .= ob_get_clean();
}

$jqVer = $jQueryVersion ? : '1.6.2';
?>
<!DOCTYPE html>
<html lang="<?=$cook_idioma?>">
<head>
    <meta charset="ISO-8859-1">
    <title><?=traduz("Telecontrol - Gerenciamento de Pós-Venda &ndash;")?> <?=$title?></title>
    <link href="imagens/tc_2009.ico" rel="shortcut icon">
<?php if ($versaoCabecalho != 'new'): ?>
    <script type="text/javascript" src="js/jquery-<?=$jqVer?>.js"></script>
<?php endif; ?>
    <?=$cabecalho->headers?>
    <style>
	.main2 .right {text-align: right;}
    .cabecalho .table {height: auto}
    div.header div.nav li > h1 > a.alert-submenu:hover {
        color: #333 !important;
    }
    </style>
<?php if (!$error_alert && $versaoCabecalho != 'new'): ?>
    <link rel="stylesheet" href="css/min_css.css">
<?php endif;

echo $mainHeaderHTML;
if ($versaoCabecalho != 'new' and count($plugins)) {
    include_once APP_DIR . 'admin/plugin_loader.php';
}
?>
    <script type='text/javascript' src='inc_soMAYSsemAcento.js'></script>
    <script src="js/cabecalho.js"></script>
    <script>
    var idioma_verifica_servidor = "<?=$cook_idioma?>";
    var pagina  = document.location.pathname; // PHP_SELF
    <?if($login_fabrica == 30){?>
    var checkCom = setInterval(verificaComunicado(), 300000);
    window.onload = function (){
        verificaComunicado();
    }
    <?}?>

    <?php if (isFabrica(87)): ?>
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
    <?php endif; ?>

    function subir_logo_empresa(posto){
        Shadowbox.init();

        Shadowbox.open({
            content: "anexa_logo_posto.php?posto="+posto,
            player: "iframe",
            title: "Anexar logo da empresa",
            width: 1000,
            height: 450,
            options: {
                modal: true,
                enableKeys: false,
                displayNav: false
            }
        });
    }

    function retorna_anexo_logo(link, posto){
        if (link != '' && posto != ''){
            $("#visualizar_logo").show();
            $("#visualizar_logo").attr("href", link);
        }
        Shadowbox.close();
    }
    <?php 
	  if(!empty($login_unico)) {
        $sql_usuario = "SELECT 
            parametros_adicionais
            FROM tbl_login_unico WHERE posto = $login_posto limit 1";

        $res_usuarios = pg_query($con, $sql_usuario);
        $parametros_adicionais = pg_fetch_result($res_usuarios,0, 'parametros_adicionais');
        $data = json_decode($parametros_adicionais, true);
        $data_atual = new DateTime();
        $data_abertura_modal = new DateTime($data['data_abertura_modal']);
        $data_abertura_modal->modify('+ 30 days');
        
    if(strtotime($data_abertura_modal->format('d-m-Y')) < strtotime($data_atual->format('d-m-Y'))){ ?>
        function usuariosLoginUnico(posto){
            setTimeout(function(){ 
                Shadowbox.init();
                Shadowbox.open({
                    content: "usuarios_login_unico_posto.php?posto_id="+posto,
                    player: "iframe",
                    title: "Usuários login Unico",
                    width: 1000,
                    height: 650,
                    options: {
                        modal: false,
                        enableKeys: true,
                        displayNav: true,
                        onClose: function(){
                            modal_usuario(posto);
                        }
                    }
                });
            }, 1000);
        }

        function modal_usuario(posto) {
            $.ajax("usuarios_login_unico_posto.php", {
                type: 'POST',
                async: false,
                data: {
                    modal_usuarios: true,
                    posto_modal: posto
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
	<?php }
	} ?>
    </script>
<?php
echo $headerHTML;
// Mensagem sobre o Chat do HelpDesk
if (false)
    include 'admin/dropdown_mensagem_admin.html';
?>
</head>
<body style="margin-top: 45px;">
<?php
echo $cabecalho->setFw($menuFw)->navBar($layout_menu);
?>
<div class="clearfix">&nbsp;</div>

<?php

$cabecalho->site = $site_fabrica;

if (isFabrica(151)) {
#    $banner = '<a id="linkreclameaqui" target="_blank" href="https://premio.reclameaqui.com.br/votacao"><img style="height:80px;" src="imagens/mondial_reclame_aqui.jpg" border="0" /></a>';
}

if ($login_fabrica == 115 && in_array($login_categoria, ['standard','master','premium'])) {
    $banner = '<img style="height:130px;" src="imagens/at-'.$login_categoria.'.png" border="0" />';
}

// Se é para mostrar o cabeçalho...
if (!$hideHeader) {
    if (!in_array(PROGRAM_NAME, array('menu_inicial.php'))) {
        echo $cabecalho->cabecalho($title, $banner, $login_master, $link_logo_tdocs);
    } else {

        if (in_array($login_fabrica, [177])) {
            $width_logo  = 190;
            $height_logo = 80;
        } else {
            $width_logo  = 200;
            $height_logo = 52;
        }

        // O cabeçalho do Menu Inicial ainda é diferenciado, mas pelo menos ficou muito mais simples.
        $logo = MenuPosto::getLogoFabrica($login_fabrica, $width_logo, $height_logo);

         ?>
    <div class="header">
        <div class="sub-header">
            <div class="main2">
                <div class="table">
                    <div><?=$logo['html']?></div>
                <?php
                if (strlen($banner)) { ?>
                    <div style=''><?=$banner?></div>
                <?php } ?>
                <?php if($login_fabrica != 87) { ?>
                    <div class="telecontrol"><img class="f-right" src="logos/logo_telecontrol_2017.png" style=" max-height: 52px; max-width: 160px;" ></div>
                    <?if($login_fabrica == 1){
                        echo "<div style='padding-left:20px'>";
                        echo verificaVideoTela(PROGRAM_NAME);
                        echo "</div>";
                    }?>
                <?php } ?>
                </div>
            </div>
        </div>
    </div>
<?php
    }
}

// HD-3589712
// Uma vez usado, tem que tirar para evitar conflito com outros métodos
if (isFabrica(3, 151) and !is_null($cabecalho->logo)) {
    $cabecalho->logo = null;
}

/**
 * Mensagens de erro e sucesso/êxito
 * ---------------------------------
 * Se `$error_alert` é TRUE, processa as variáveis $msg_erro (string ou array)
 * $msg (string ou array).
 * Se não tem outras informações, a primeira é para erro (vermelho) e a segunda
 * para informações (azul), usando as cores e formatação formecidas pela FMC.
 */
if ($msg_alerts):
    echo $cabecalho->alert($msg_alerts, 'danger', 'exclamation-triangle');
endif;

if (count($msg) and $error_alert === true):
    echo $cabecalho->alert($msg, 'info', 'check-circle');
endif;

if (count($msg_erro) and $error_alert === true):
    if (array_key_exists('msg', $msg_erro)):
        if (count($msg_erro['msg'])) {
            echo $cabecalho->alert($msg_erro['msg'], 'danger', 'ban');
        }
        if (array_key_exists('campos')):
            echo $cabecalho->alert($msg_erro['campos'], 'danger', 'exclamation-triangle');
        endif;
    else:
        echo $cabecalho->alert($msg_erro, 'danger', 'ban');
    endif;
endif;

if ($desabilita_tela):
    echo $cabecalho->alert($desabilita_tela, 'danger', 'lock');
    include_once('rodape.php');
    exit;
endif;

echo $bodyHTML;

if (!$versaoCabecalho == 'new')
    echo ("<center>\n");

if (pg_num_rows($resTela) > 0 && empty($bodyHTML) && $login_fabrica == 1) {
    $descricao    = pg_fetch_result($resTela, 0, 'descricao');
    $mensagem     = str_replace("&nbsp;", "", strip_tags(trim(pg_fetch_result($resTela, 0, 'mensagem')), "<br><br/><p><i><b><strong><em><span><h1><h2><h3><h4>"));
    $mensagem     = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $mensagem); /*Retirar os style's do Word*/
    $mensagem     = preg_replace('/(<[^>]+) class=".*?"/i', '$1', $mensagem); /*Retirar as classes do Word*/

    echo "<div style='width: 800px;'>" . $cabecalho->alert(
        "
            <b><center>$descricao</center></b>
            <br>
            $mensagem
        ",
        "info"
    );
    echo "<br> </div>";
}

