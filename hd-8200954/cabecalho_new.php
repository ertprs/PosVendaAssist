<?php
// Este arquivo não pode ser chamado diretamente
if ($_SERVER['SCRIPT_FILENAME'] == __FILE__) {
	header('HTTP/1.1 403 Forbidden');
    die("<h2 style='color: white; background: darkred; text-align: center'>Este <i>script</i> não pode ser executado diretamente.</h2>");
}

$versaoCabecalho = 'new'; // flag
$layoutCSS       = 'FMC'; // Quando estiver pronto o Bootstrap 2/3, alterar.
ob_start();
?>
    <link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/bootstrap.css" />
    <link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/extra.css" />
    <link media="screen" type="text/css" rel="stylesheet" href="css/tc_css.css" />
    <link media="screen" type="text/css" rel="stylesheet" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
    <link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/ajuste.css" />

    <!--[if lt IE 10]>
    <link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
    <link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
    <![endif]-->

    <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
    <script type="text/javascript" src="bootstrap/js/bootstrap.js"></script>
<?php
    if (count($plugins)) {
        include_once APP_DIR . 'admin/plugin_loader.php';
    }

    $mainHeaderHTML .= ob_get_clean();
    ob_start();

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

?>
<?php if (PROGRAM_NAME != "menu_inicial.php" ) { ?>
    <div class="container tc_container ">
        <div id="loading-block" style="width:100%;height:100%;position:fixed;left:0px;top:0px;text-align:center;vertical-align: middle;background-color:#000;opacity:0.3;display:none;z-index:10" >
        </div>
        <div id="loading"  >
            <img src="admin/imagens/loading_img.gif" style="z-index:11" />
            <input type="hidden" id="loading_action" value="f" />
            <div style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:10000;"></div>
        </div>
<?
    // $error_alert = true;
    $bodyHTML = ob_get_clean();
    require_once 'cabecalho.php';
    $link_programa =  $_SERVER['SCRIPT_NAME'];

    $link_url = basename($_SERVER["PHP_SELF"]);
    
    if($_serverEnvironment !== "development") {
        $link_programa2 = "/assist/".$link_url;
        $link_programa3 = "http://posvenda.telecontrol.com.br/assist/".$link_url;
        $link_programa4 = "https://posvenda.telecontrol.com.br/assist/".$link_url;
    } else {
        $link_programa2 = "/PosVendaAssist/".$link_url;
        $link_programa3 = "http://novodevel.telecontrol.com.br/~gaspar/PosVendaAssist/".$link_url;
        $link_programa4 = "https://novodevel.telecontrol.com.br/~gaspar/PosVendaAssist/".$link_url;
    }

    $sql = "SELECT tbl_comunicado.comunicado, tbl_comunicado.descricao,
                   tbl_comunicado.mensagem, tbl_comunicado.extensao,
                   TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data,
                   tbl_comunicado.programa
              FROM tbl_comunicado
             WHERE tbl_comunicado.fabrica = $login_fabrica
               AND tbl_comunicado.tipo      = 'Comunicado por tela'
               AND (tbl_comunicado.programa  = '$link_programa' OR tbl_comunicado.programa  = '$link_programa2' OR tbl_comunicado.programa  = '$link_programa3' OR tbl_comunicado.programa  = '$link_programa4')
               AND tbl_comunicado.ativo IS TRUE
          ORDER BY tbl_comunicado.data DESC LIMIT 1";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) { ?>
    <br />

    <table align='center' bgcolor='000000' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 12px;' width='850'>
    <tr bgcolor="#CC4444">
        <TD align='center' style='font-size: 14px; color: #FFFFFF'><B>Importante!</B></TD>
    </tr>

    <tr bgcolor='#FFCC99'>
        <td align='left' style='color: #330000;'>
<?php
        if(strlen(pg_result($res,0,descricao)) > 0){
            echo "<center><span style='color: #330000; font-size:14px; font-weight:bold;'>".pg_result($res,0,descricao)."</span></center> <br />";
        }
        echo pg_result($res,0,mensagem);
?>

            <? if(strlen(pg_result($res,0,extensao)) > 0){ ?>
              <p align='center' style='margin:auto'>
                <a href="comunicados/<?=pg_result($res,0,comunicado).'.'.pg_result($res,0,extensao)?>" target="_blank" style="color:#FF0000">
                <u><?=traduz('veja.mais', $con)?></u>
                </a>
              </p>
            <? } ?>
        </td>
    </tr>
    </table>
    <br />
<?php
    }
}
