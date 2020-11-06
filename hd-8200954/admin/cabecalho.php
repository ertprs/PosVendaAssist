<?php


if ((in_array($login_fabrica, array(101, 72)) || $interacaoOsPosto) && $login_responsavel_postos == 't') {
    $mostra_info_interacao_pendente = true;
}
/** */

if (($login_fabrica == 3 and $login_login <> 'samuel') || ($login_fabrica == 148)) {
    include "autentica_validade_senha.php";
}

if($telecontrol_distrib){
    $interacao_pendente_os_pedido = true;   
}

include "monitora_cabecalho.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . 'funcoes.php';

if (!function_exists('getmicrotime')) {
    function getmicrotime(){
        list($usec, $sec) = explode(" ",microtime());
        return ((float)$usec + (float)$sec);
    }
}

if (!function_exists('TempoExec')) {
    function TempoExec($pagina, $sql, $time_start, $time_end){
        if (1 == 1){
            $time = $time_end - $time_start;
            $time = str_replace ('.',',',$time);
            $sql  = str_replace ('\t',' ',$sql);
        }
    }
}

$micro_time_start = getmicrotime();

$gmtDate = gmdate("D, d M Y H:i:s");
header("Expires: {$gmtDate} GMT");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
header("Last-Modified: {$gmtDate} GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

// O cabeçalho é usado na área do admin/bi/, aqui define os paths relativos,
// Pode ser usado dentro dos programas do BI para pegar as imagens do admin, também.
define('BI_BACK', (strpos($_SERVER['PHP_SELF'],'/bi/') == true)?'../':'');

// Para saber se está numa tela de menu...
define('TELA_MENU', (strpos($PHP_SELF, 'menu_')!==false));  // Define se a tela atual é algum menu

$dir_help_desk     = BI_BACK . '../helpdesk';
$dir_help_desk_img = "$dir_help_desk/imagem";
$path_logo         = BI_BACK . '../logos';
$imagens_admin     = BI_BACK . 'imagens_admin';
$admin_fotos       = BI_BACK . 'admin_fotos';
$altera_logo_TcNet = ($login_fabrica == 46 and in_array($login_login, array('manuel','ronaldo','sergiotelecontrolnet','waldir','paulo')));

/*HD - 4417123*/
if ($_POST["verificar_privilegios"]) {
    unset($_COOKIE["aux_privilegios"]);
    $aux_sql = "SELECT privilegios FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $login_admin";
    $aux_res = pg_query($con, $aux_sql);
    $aux_row = pg_num_rows($aux_res);

    if (pg_last_error()) {
        echo "KO|Erro ao buscar os privilégios do admin";
    } else {
        $privilegios = pg_fetch_result($aux_res, 0, 'privilegios');
        
        if (strlen($privilegios) == 0) {
            setcookie("aux_privilegios", "sem_privilegios");
            echo "OK|sem_privilegios";
        } else {
            if ($privilegios == "*") {
                setcookie("aux_privilegios","master");
            } else {
                $aux_privilegios = str_replace(",", "-", $privilegios);
                setcookie("aux_privilegios","$aux_privilegios");
            }
            echo "OK|$privilegios";
        }
    }
    exit;
}

/**
 * Acesso ADMIN Multi-fábrica:
 * Os Admins cadastrados podrão alterar entre as fábricas usando um SELECT no cabeçalho.
 **/
// if ($telecontrol_distrib) {
    //1981,260,1941,1939,553,602,1940,1952,1806,1991,1992,1583,1995,1995,1997,1994,1896,1996,1628,1838,2019,2016,2013,2007,2017,2012,2008,2014,2018, 417, 1661, 2141 HD 175298, HD 188390, HD 190335
    // 260,270,417,553,602,1164,1216,1279,1405,1516,1583,1628,1661,1796,1806,1838,1939,1940,1941,1952,1981,1991,1992,1994,1995,1996,1997,2007,2008,2011,2012,2013,2014,2016,2017,2018,2019,2023,2058,2138,2139,2141,2145,2663,3229,3210,3230,3231
	/*
	Gera dois arrays:
		$admins       => contém os ids admin que o usuário tem acesso
		$multi_admins => contém os nomes das fábricas acessadas por cada admin
						 para acessar o nome de uma fabrica, use o ID dela
						 como índice. Ex: $fabrica[40]
	*/

	$sql = "SELECT * FROM tbl_admin_igual WHERE admin=$login_admin OR admin_igual=$login_admin LIMIT 1";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {

		$admin_principal = pg_fetch_result($res, 0, 'admin');

		$sql = "SELECT * FROM tbl_admin_igual WHERE admin=$admin_principal";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {

			$admins = array();
			$admins[] = $admin_principal;
			for($i = 0; $i < pg_num_rows($res); $i++) {
				$admins[] = pg_fetch_result($res, $i, 'admin_igual');
			}
			$admins = implode(",", $admins);

			$sql =  "
			  SELECT tbl_admin.admin, tbl_admin.fabrica, tbl_fabrica.nome
				FROM tbl_admin
				JOIN tbl_fabrica
			   USING (fabrica)
			   WHERE tbl_admin.admin IN ($admins)
				 AND tbl_admin.admin <> $login_admin
					";
			$res = pg_query($con, $sql);

			$multi_admins = pg_fetch_all($res);

			// Deixa pronto o select...
			foreach($multi_admins as $mAdmin) {

                $nome_fabrica = ($mAdmin["fabrica"] == 11) ? "Aulik" : $mAdmin['nome'];

				$opts .= sprintf(
					"\t\t<option value='%s|%s'>%s</option>\n",
					$mAdmin['admin'], $mAdmin['fabrica'],
					$nome_fabrica
				);

			}
			// $fabricas = array();
			$multi_admin_html = '<br />Logar em: ' .
				chr(9).'<select name="logar_como" id="logar_como" '.
				"onChange='trocaFabrica(this.value);'>" .
				"\t\t<option>selecione...</option>\n".
				"\n$opts\n\t" .
				"</select>\n";
		}
	}
// }

include (BI_BACK . '../fn_logoResize.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<!-- AQUI COMEÇA O HTML DO MENU -->

<?php
    if ($login_fabrica == 87) {
        $pagina = basename($_SERVER['PHP_SELF']);
        if (!in_array($pagina, array('peca_cadastro.php','peca_consulta.php','preco_cadastro.php','posto_cadastro.php','pedido_parametros.php','pedido_consulta.php','pedido_cadastro.php','pedido_admin_consulta.php','menu_cadastro.php', 'menu_cadastro.php', 'menu_gerencia.php', 'transportadora_cadastro.php','depara_cadastro.php','admin_senha_n.php','menu_tecnica.php','comunicado_produto.php','relatorio_comunicado.php','comunicado_inicial.php','classe_pedido.php')))
        {
            echo "<script>window.location = 'http://posvenda.telecontrol.com.br/assist/admin/menu_cadastro.php'</script>";
            //echo "<script>window.location = 'http://192.168.0.199/~guilherme/assist/admin/menu_cadastro.php'</script>";
        }
    }

    //Abas para usuário admin normal.
    if (in_array($login_fabrica, [180,181,182])){
        switch ($layout_menu) {
        case "gerencia":
            $imgAbas = "<img src='$imagens_admin/btn_gerencia_esab.gif' usemap='#menu_map'>";
                $cor = "#E6D1DE";
                break;
            case "callcenter":
            $imgAbas = "<img src='$imagens_admin/btn_callcenter_esab.gif' usemap='#menu_map'>";
                $cor = "#EDF6DE";
                break;
            case "cadastro":
            $imgAbas = "<img src='$imagens_admin/btn_cadastro_esab.gif' usemap='#menu_map'>";
                $cor = "#FFFDBE";
                break;
            case "tecnica":
            $imgAbas = "<img src='$imagens_admin/btn_tecnica_esab.gif' usemap='#menu_map'>";
                $cor = "#C4E6F8";
                break;
            case "financeiro":
            $imgAbas = "<img src='$imagens_admin/btn_financeiro_esab.gif' usemap='#menu_map'>";
                $cor = "#FEEFB6";
                break;
            case "auditoria":
            $imgAbas = "<img src='$imagens_admin/btn_auditoria_esab.gif' usemap='#menu_map'>";
                $cor = "#C2BCD6";
                break;
            default:
            $imgAbas = "<img src='$imagens_admin/btn_gerencia_esab.gif' usemap='#menu_map'>";
                $cor = "#E6D1DE";
                break;
        }
    }else {
            switch ($layout_menu) {
        case "gerencia":
            $imgAbas = "<img src='$imagens_admin/btn_gerencia.gif' usemap='#menu_map'>";
                $cor = "#E6D1DE";
                break;
            case "callcenter":
            $imgAbas = "<img src='$imagens_admin/btn_callcenter.gif' usemap='#menu_map'>";
                $cor = "#EDF6DE";
                break;
            case "cadastro":
            $imgAbas = "<img src='$imagens_admin/btn_cadastro.gif' usemap='#menu_map'>";
                $cor = "#FFFDBE";
                break;
            case "tecnica":
            $imgAbas = "<img src='$imagens_admin/btn_tecnica.gif' usemap='#menu_map'>";
                $cor = "#C4E6F8";
                break;
            case "financeiro":
            $imgAbas = "<img src='$imagens_admin/btn_financeiro.gif' usemap='#menu_map'>";
                $cor = "#FEEFB6";
                break;
            case "auditoria":
            $imgAbas = "<img src='$imagens_admin/btn_auditoria.gif' usemap='#menu_map'>";
                $cor = "#C2BCD6";
                break;
            default:
            $imgAbas = "<img src='$imagens_admin/btn_gerencia.gif' usemap='#menu_map'>";
                $cor = "#E6D1DE";
                break;
        }
    }
?>
<head>
    <title><? echo $title ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=8"/>
    <meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
    <meta http-equiv="Expires"       content="0">
    <meta http-equiv="Pragma"        content="no-cache, public">
    <meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
    <meta name      ="Author"        content="Telecontrol Networking Ltda">
    <meta name      ="Generator"     content="na mão...">
    <meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
    <meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <? if($login_fabrica == 148) { ?>
        <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/extra.css" />
    <? } ?>
    <link type="text/css" rel="stylesheet" href="<?=BI_BACK?>css/css.css">
    <link type="text/css" rel="stylesheet" href="<?=BI_BACK?>css/tooltips.css"><?php
    if ($dominio == 'conquistar.telecontrol.com.br') {
        echo "<div style='width: 100%; font-size: 12pt; color: #662222; border: 1px solid #EE0000; margin-bottom: 2px; background-color: #EEAAAA'>".traduz('ATENÇÃO: AMBIENTE DE TESTES CONQUISTAR')."</div>";
    }?>

<style>
    .scrollup{
        width:40px;
        height:40px;
        opacity:0.4;
        position:fixed;
        bottom:100px;
        right:100px;
        display:none;
        text-indent:-9999px;
        background: url('imagens_admin/icon_top.png') no-repeat;
    }
    #adm_foto {
        float:right;
        max-height:50px;
        border: 3px solid white;
        margin:auto 0 auto 3px;
        box-shadow: 1px 1px 2px black;
        border-radius:3px;
        -o-transition: max-height 0.3s ease-out;
        -ms-transition: max-height 0.3s ease-out;
        -moz-transition: max-height 0.3s ease-out;
        -webkit-transition: max-height 0.3s ease-out;
        transition-delay: 0.3s;
        -o-transition-delay: 0.3s;
        -ms-transition-delay: 0.3s;
        -moz-transition-delay: 0.3s;
        -webkit-transition-delay: 0.3s;
    }
    #adm_foto:hover {
        max-height:128px;
        box-shadow: 2px 2px 3px black;
        border-radius:4px;
        border: 4px solid white;
    }

    .borda{
        background: white url(<?=BI_BACK?>imagens/fundo.png) 100% 0 no-repeat;
        background: white url(<?=BI_BACK?>imagens/fundo.png) 100% 0 no-repeat;
        background-image: linear-gradient(right, <?php echo $cor; ?> , white);/*Padrão*/
        background-image: -o-linear-gradient(right, <?php echo $cor; ?> , white);/*Opera 11.1+*/
        background-image: -ms-linear-gradient(right, <?php echo $cor; ?> , white);/*IE>=10*/
        background-image: -moz-linear-gradient(right, <?php echo $cor; ?> , white);/*Firefox*/
        background-image: -webkit-gradient(linear, 100% 0, 0 0, from(<?php echo $cor; ?>), white))); /*Chrome 9-*/
        background-image: -webkit-linear-gradient(right, <?php echo $cor; ?> , white);/*Chrome 9+*/
        filter:  progid:DXImageTransform.Microsoft.gradient(GradientType=1,startColorstr='#ffffff', endColorstr='<?php echo $cor; ?>');/*IE 6 e 7*/
        -ms-filter: "progid:DXImageTransform.Microsoft.gradient(GradientType=1,startColorstr='#ffffff', endColorstr='<?php echo $cor; ?>)"; /*IE 8*/
        padding-right: 10px;
    }

    .borda2{
        border-top-width:medium;
        border-top-style:solid;
        border-top-color:#DEE3EF;
    }
    
    #helpdesk-chat {
		text-align: center;
	}

	#helpdesk-chat img {
		width: 40px;
		height: 40px;
		border: 0px;
	}
	
	#helpdesk-chat span {
		position: relative;
		display: block;
		top: -4px;
		/* color: #5A6D9C; */
		color: #999999;
		font-weight: bold;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 9px;
	}
	
	#helpdesk-chat img.offline {
		filter: grayscale(100%);
	}
	
	#helpdesk-chat span.offline {
		color: #E0123F;
		margin-top: -5px;
	}
	
	#helpdesk-chat span.online {
		color: #71BE5F;
		margin-top: -5px;
	}

    #doctelecontrol {
        position: absolute;
        float: right;
        display: block;
        width: 110px;
        text-align: center;
        margin-left: -45px;
        top: 30px;
    }

    #doc_fabrica {
        position: absolute;
        float: right;
        display: block;
        width: 110px;
        text-align: center;
        margin-left: -45px;
        top: 0px;
    }
    #doc_fabrica span {
        position: relative;
        display: block;
        /*top: -4px;
         color: #5A6D9C;*/
        color: #999999;
        font-weight: bold;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: 9px;
    }

    #doctelecontrol img {
        width: 35px;
        height: 35px;
        border: 0px;
    }

    #doctelecontrol_pendencia {
        position: absolute;
        float: right;
        display: block;
        width: 120px;
        text-align: center;
        top: 14px;
        margin-left: -50px;
        font-weight: bold;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        color: #B4696B;
        font-size: 9px;
    }
    #doctelecontrol_text {
        position: absolute;
        float: right;
        display: block;
        width: 120px;
        text-align: center;
        top: 22px;
        margin-left: -103px;
        font-weight: bold;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        color: #999999;
        font-size: 9px;
    }
    #doctelecontrol span {
        position: relative;
        display: block;
        /*top: -4px;
         color: #5A6D9C;*/
        color: #999999;
        font-weight: bold;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: 9px;
    }

    #doctelecontrol:hover a {
        text-decoration: none;
    }


    #chatonline{
		text-align: center;
		-webkit-filter: grayscale(100%);
		-webkit-transition: all 0.5s ease;
		-moz-transition: all 0.5s ease;
		-o-transition: all 0.5s ease;
		-ms-transition: all 0.5s ease;
		transition: all 0.5s ease;
    }

    #chatonline:hover{
		-webkit-filter: grayscale(0%);
    }

    #chatonline img {
		width: 52px;
		border: 0px;
	}
    
    #menu_sidebar {
		position: absolute;
		float: right;
		display: block;
		width: 70px;
		top: 44px;
        padding-left: 10px;
        z-index: 40000;
	}
    
    #menu_sidebar > div {
        margin-bottom: 10px;
    }

    #menu_sidebar2 {
        position: absolute;
        float: right;
        display: block;
        width: 70px;
        top: 44px;
        padding-left: 80px;
        z-index: 30000;
    }

    #menu_sidebar2 > div {
        margin-bottom: 10px;
    }

    #menu_sidebar3 {
        position: absolute;
        float: right;
        display: block;
        width: 70px;
        top: 44px;
        padding-left: 150px;
        z-index: 20000;
    }

    #menu_sidebar3 > div {
        margin-bottom: 10px;
    }

    #menu_sidebar_icon {
        position: absolute;
        float: right;
        display: block;
        width: 150px;
        top: 44px;
    }

    #posicao_icon {
        position: absolute;
        display: block;
        margin-left: 80%;
        top: 2%;
    }

    #span_posicao {
        top : 49%;
        margin-left: 21% !important;
    }
    
    <?php
	if (in_array($login_fabrica, array(175))) {
	?>
		#ferramentas_vencimento {
			text-align: center;
		}

		#ferramentas_vencimento img {
			width: 35px;
			height: 35px;
			border: 0px;
			filter: invert(100%);
		}
		
		#ferramentas_vencimento_count {
			background-color: #F00;
			padding: 0px 3px;
			color: #FFF;
			font-weight: bold;
			position: absolute;
			z-index: 2;
			margin-top: -6px;
			margin-left: 20px;
			border-radius: 20px;
            width: 14px;
		}
		
		#ferramentas_vencimento_titulo {
			font-size: 10px;
			line-height: 13px;
			font-weight: bold;
			color: #FB5125;
            display: inline-block;
        }
        
        #ferramentas_novas {
			text-align: center;
		}

		#ferramentas_novas img {
			width: 35px;
			height: 35px;
			border: 0px;
			filter: grayscale(100%);
		}
		
		#ferramentas_novas_count {
			background-color: #F00;
			padding: 0px 3px;
			color: #FFF;
			font-weight: bold;
			position: absolute;
			z-index: 2;
			margin-top: -6px;
			margin-left: 20px;
			border-radius: 20px;
			width: 14px;
		}
		
		#ferramentas_novas_titulo {
			font-size: 10px;
			line-height: 13px;
			font-weight: bold;
			color: #999;
			display: inline-block;
		}
	<?php
	}
	?>


</style>
<script>
    $(function() {
        setTimeout(function(){ verificar_privilegios() }, 4000); /*HD - 4417123*/
    });

    <? if ($login_fabrica == 117) { ?>
    $(function(){
        if($('#macro_linha_aux').val() !== '' && $('#macro_linha_aux').val() !== undefined) { $('#macro_linha').val($('#macro_linha_aux').val()); }
        if ($('#macro_linha').length) { carrega_macro_familia(); }
        $('#macro_linha').change(function(){
            carrega_macro_familia();
        });

        if (typeof $('#familia') == 'object') {
            $('#linha').change(function(){
                carrega_familia();
            });
        }

        function carrega_familia(){
            $.ajax({
                url: window.location.href,
                type: "POST",
                data: { ajax: 'sim', action: 'carrega_familia', linha: $('#linha').val() },
                timeout: 8000
            }).fail(function(){
            }).done(function(data){
                data = JSON.parse(data);
                $("#familia").html(data.ok);
                $("#familia").val($('#familia_aux').val());
            });
        }

        function carrega_macro_familia(){
            var multiselect = ($("#linha").attr('name') == 'linha[]') ? true : false;

            if ($('#macro_linha').val() !== '') {
                $.ajax({
                    url: window.location.href,
                    type: "POST",
                    data: { ajax: 'sim', action: 'carrega_macro_familia', macro_linha: $('#macro_linha').val(), multiselect: multiselect },
                    timeout: 8000
                }).fail(function(){
                }).done(function(data){
                    data = JSON.parse(data);
                    $("#linha").html(data.ok);
                    if (multiselect) {
                        if($("#linha_aux").val() !== ''){
                            var linhas = $("#linha_aux").val().split(',');
                            $("#linha").val(linhas);
                        }
                        $("#linha").multiselect('refresh');
                    }else{
                        $("#linha").val($("#linha_aux").val());
                    }
                    if (typeof $('#familia') == 'object') { carrega_familia(); }
                });
            }else{
                $("#linha").html('');
            }
        }
    });
    <? } ?>

    function changeIframeHeight(id, height) {
        $("#"+id).css({ height: height+"px" });
    }
/*****************************************************************
Nome da Função : displayText
        Apresenta em um campo as informações de ajuda de onde
        o cursor estiver posicionado.
******************************************************************/
    function displayText( sText ) {

        if (document.getElementById("displayArea")) {
            document.getElementById("displayArea").innerHTML = sText;
        }

    }

    function atualiza_dado(admin) {

        window.open('atualiza_dado.php?admin='+admin, 'ouverture', 'toolbar=no, status=yes, scrollbars=yes, resizable=no, width=400, height=500');

    }

    function toggleCustomizePopUp(iFrameID) {
        var popUp = document.getElementById(iFrameID);
        popUp.style.display = (popUp.style.display == 'block') ? 'none' : 'block';
    }

    /*HD - 4417123*/
    function verificar_privilegios() {
            $.ajax({
                url: window.location.href,
                type: "POST",
                data: { verificar_privilegios: true }
            }).fail(function(){
            }).done(function(data){
                data = data.split("|");

                if (data[0] == "OK") {
                    var exibir_shadowbox = false;

                    if (data[1] == "sem_privilegios") {
                        var url = "<?=BI_BACK?>logout.php";
                        exibir_shadowbox = true;
                    } else {
                        if (data[1] != "*") {
                            var privilegios = data[1];
                            privilegios = privilegios.replace("_","");
                            var aba_atual   = "<?=$layout_menu;?>";

                            if (aba_atual != "" && privilegios) {
                                var pos = privilegios.indexOf(aba_atual);
                                
                                if (pos == -1) {
                                    exibir_shadowbox = true;
                                    var privilegios_array = privilegios.split(",");

                                    if (privilegios_array[0] == "") {
                                        var url = "<?=BI_BACK?>logout.php";
                                    } else {
                                        switch (privilegios_array[0]) {
                                            case "gerencia":
                                                url = "<?=BI_BACK?>menu_gerencia.php";
                                                break;

                                            case "callcenter":
                                                url = "<?=BI_BACK?>menu_callcenter.php";
                                                break;

                                            case "cadastro":
                                                url = "<?=BI_BACK?>menu_cadastro.php";
                                                break;

                                            case "infotecnica":
                                                url = "<?=BI_BACK?>menu_tecnica.php";
                                                break;

                                            case "financeiro":
                                                url = "<?=BI_BACK?>menu_financeiro.php";
                                                break;

                                            case "auditoria":
                                                url = "<?=BI_BACK?>menu_auditoria.php";
                                                break;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (exibir_shadowbox == true) {
                        var url_atual   = "<?=$_SERVER['PHP_SELF']?>";
                        var auxiliar    = url_atual.split("/");
                        var num_auxliar = auxiliar.length;

                        if (num_auxliar >= 6) {
                            var link_shadowbox = "../shadowbox_usuario_sem_acesso.php";
                        } else {
                            var link_shadowbox = "shadowbox_usuario_sem_acesso.php";
                        }

                        Shadowbox.init();
                        Shadowbox.open({
                            content: link_shadowbox,
                            player: "iframe",
                            title: "Usuário sem Acesso",
                            width: 900,
                            height: 250,
                            options:{ 
                                modal: false,
                                onClose: function() {
                                    window.location = url;
                                } 
                            } 
                        });
                    }
                }
            });
        }
</script>



<? if (isset($multi_admin_html)) { ?>
<script type="text/javascript">
function setCookie(c_name,value,path,expiredays) {
    var exdate=new Date();
    exdate.setDate(exdate.getDate()+expiredays);
    var expireDate = (expiredays==null) ? "" : ";expires="+exdate.toGMTString();
    var c_path     = (path == null) ? "" : ";path="+path;
    document.cookie=c_name+ "=" +escape(value)+c_path;
    window.location.reload();
}

function trocaFabrica(novoLogin) {
	var login = novoLogin.split("|");
    var path = document.location.pathname;
    var newpath = path.substr(0, path.search('/admin'))+'/';
    // setCookie("cook_admin",login[0],newpath);
    // setCookie("cook_fabrica",login[1],newpath);

    <?php
    $self = $_SERVER['PHP_SELF'];
    if (strstr($self,"/bi/")) {
        $self = explode("/", $self);
        unset($self[count($self)-1]);
        unset($self[count($self)-1]);
        unset($self[count($self)-1]);
        $page = implode("/", $self);
        $page = "http://".$_SERVER['HTTP_HOST'].$page."/token_cookie_changes.php";
    } else if(strstr($self,"/admin/")){
        $self = explode("/", $self);
        unset($self[count($self)-1]);
        unset($self[count($self)-1]);
        $page = implode("/", $self);
        $page = "http://".$_SERVER['HTTP_HOST'].$page."/token_cookie_changes.php";
    }else{
        $self = explode("/", $self);
        unset($self[count($self)-1]);
        $page = implode("/", $self);
        $page = "http://".$_SERVER['HTTP_HOST'].$page."/token_cookie_changes.php";
    }
    $pageReturn = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
    ?>

    var pageReturn = "<?=$pageReturn?>";
    var params = "cook_admin="+login[0]+"&cook_fabrica="+login[1]+"&page_return="+pageReturn;

    window.location = "<?=$page?>?"+params;
    // window.location.reload();
}

</script>
<?}

// Para as telas do menu, adicionada funcionalidade para colapsar e expandir as seções dos menus.
// Imagem com  status da conexão, oferecido pelo PingDom
// Barra com validação da versão do Navegador, e links para baixar os últimos navegadores.
if (TELA_MENU) { ?>
    <script src="js/jquery-ui-1.8.23.custom/js/jquery-1.8.0.min.js"></script>
    <script type="text/javascript">
        $(function() {
            $('img.colexpand').parent().click(function(){
                $(this).find('.colexpand').attr('src', ($(this).next('table').is(':visible')) ? 'imagens/icon_expand.png':'imagens/icon_collapse.png');
                $(this).next('table').slideToggle();
            });
            $('table.tabela caption').click(function() {
                $(this).parent()
                       .find('tbody,thead').slideToggle('normal')
                       .delay(200)
                       .end()
                       .toggleClass('oculta');
            });

            //SCROLL DO MOUSE REVELA BOTAO PARA VOLTAR AO TOPO
            $('.scrollup').click(function(){
                $("html, body").animate({ scrollTop: 0 }, 600);
                return false;
            });
            $(window).scroll(function(){
                if ($(this).scrollTop() > 200) {
                    $('.scrollup').slideDown('slow');
                } else {
                    $('.scrollup').slideUp('slow');
                }
            });

        });
    </script>
    <link rel="stylesheet" href="css/menu_tc.css" />
    <style type="text/css">
        /* CSS PingDom */
        #pingDomImg {
            /* Positioning */
            position: fixed;
            top: -128px;
            right: 64px;
            overflow-y: hidden;

            /* Effects */
            border-radius: 0 0 5px 5px;
            -moz-border-radius: 0 0 5px 5px;
            transition: all 0.3s ease-in;
            -o-transition: all 0.3s ease-in;
            -ms-transition: all 0.3s ease-in;
            -moz-transition: all 0.3s ease-in;
            -webkit-transition: all 0.3s ease-in;
        }
        #pingDomImg {
            transition-delay: 0.5s;
            -o-transition-delay: 0.5s;
            -ms-transition-delay: 0.5s;
            -moz-transition-delay: 0.5s;
            -webkit-transition-delay: 0.5s;
        }
        #pingDomImg:hover {
            top: 0;
            z-index: 10000;
        }
    </style>
</head>
<body>
<!--[if lt IE 7]>
<div id='oldIE' style='padding: 0pt 0pt 0pt 15px; position: relative; width: 100%; text-align: center; margin: 0pt auto;'>
    <a href="http://windows.microsoft.com/en-US/internet-explorer/products/ie/home?ocid=ie6_countdown_bannercode" target='_blank'>
        <img src="http://storage.ie6countdown.com/assets/100/images/banners/warning_bar_0010_portuguese.jpg" border="0" height="42" width="820" alt="Você está usando um navegador desatualizado. Para uma experiência de navegação mais rápida, segura atualizar gratuitamente hoje." />
    </a>
</div>
<![endif]-->

    <script type="text/javascript">
    function showPingDomStats() {
        TINY.box.show({
            iframe: 'https://stats.pingdom.com/7amtsbb6gpl3',
            boxid:  'PingDom',
            width:  1024,
            height: 500,
            fixed:  true,
            maskid: 'bluemask',
            maskopacity:70
        });
    }
    </script>
    <img id='pingDomImg' src='https://share.pingdom.com/banners/13582b38' alt=''
        onClick="showPingDomStats()" />
<?
    include BI_BACK . '../inc_browsers.php';
    //stats.pingdom.com/7amtsbb6gpl3 // Endereço de teste
} else {
    echo "</head>\n<body bgcolor='#ffffff' marginwidth='2' marginheight='2' topmargin='2' leftmargin='2' $body_onload />\n";
}

$arquivo_atual = $_SERVER["SCRIPT_FILENAME"];

if ($arquivo_atual) {
    $sql = "SELECT help
              FROM tbl_help
              JOIN tbl_arquivo
                ON tbl_help.arquivo = tbl_arquivo.arquivo
             WHERE tbl_arquivo.descricao ILIKE '%$arquivo_atual%'
               AND tbl_help.fabrica IN ($login_fabrica, 0)
             ORDER BY tbl_help.fabrica DESC LIMIT 1
    ";
    $res = pg_query($sql);
    if (pg_num_rows($res)) {
        $tbl_help_help = pg_fetch_result($res, 1, 0);

        //HD 205958: Help nas telas dos programas. Foi usado um iframe para evitar problemas com includes de JavaScript
        echo "
            <style>
            .div_tbl_help {
                position: absolute;
                top: 0px;
                left: 0px;
                width: 100%;
                height: 100%;
                display: none;
            }
            </style>
            <iframe class=div_tbl_help id='iframe_tbl_help' name='iframe_tbl_help' src='help_iframe_cabecalho.php?help=$tbl_help_help' frameborder=0 allowtransparency='true' scrolling=no width=100% height=100% style='background:none'></iframe>
        ";
    }
}

//include ("email_admin_include.php");

if ($admin_consulta_os == true)
    $sem_menu = true;

if ($sem_menu == false OR strlen ($sem_menu) == 0) {
    echo "<table width='700px' cellpadding='0' cellspacing='0'   style='border-color: transparent; margin: 0 auto; border: 0px; border-collapse: collapse;' class='no-print'>";
    echo "<tr style='background: url(imagens/fundo-cabecalho.png);'>";
    echo "<td style='background: #FFF;'>&nbsp;&nbsp;</td>";
    echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
    echo "<td width='100%' align='center' valign='top'>$imgAbas</td>";
    echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
    echo "<td style='background: #FFF;'>&nbsp;&nbsp;</td>";
    //Foto Admin, link atualiza cadastro

    if ($login_fabrica == "10") {
        $sql = "SELECT tbl_admin.grupo_admin
                FROM tbl_admin
               WHERE tbl_admin.admin={$login_admin}
                 AND tbl_admin.fabrica={$login_fabrica}
                 AND tbl_admin.grupo_admin IS NULL";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $prefixo = '';
        } else {
            $prefixo = 'adm_';
        }
    }

    $sql = "SELECT COUNT (*) FROM (
                SELECT tbl_hd_chamado.hd_chamado, tbl_hd_chamado.status ,tbl_hd_chamado.admin ,
                    (SELECT tbl_hd_chamado_item.admin
                    FROM tbl_hd_chamado_item
                    JOIN tbl_hd_chamado using(hd_chamado)
                    WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
                    ORDER BY hd_chamado_item DESC LIMIT 1) AS admin_item
                FROM tbl_hd_chamado
                WHERE admin = $login_admin and upper(status) <> 'RESOLVIDO'
            ) As help WHERE admin <> admin_item";
    $sql = "SELECT count(*)
            FROM tbl_hd_chamado
            WHERE admin = $login_admin
            AND (
                (exigir_resposta is TRUE and status<>'Resolvido')
                OR
                (resolvido is null and status='Resolvido')
                )
            AND status<>'Cancelado'
            AND fabrica_responsavel <> $login_fabrica";
    $resX = pg_query($con,$sql);
    $qtde_help = pg_fetch_result($resX,0,0);

    $menu_sidebar = ($login_fabrica == 30) ? 'menu_sidebar_icon' : 'menu_sidebar';
    echo "<td width='100' align='center' valign='top' style='font-size: 10px;' class='float-helpdesk'>
    <div id='$menu_sidebar' >";

    //Changelog já estava desativado mais abaixo, estava executanto query sem necessidade. Desativei aqui
    if (($qtde_help == 0 OR strlen ($qtde_help) == 0)) {
        echo "<div>";
        if($login_fabrica == 10){
            echo "<a href='" .BI_BACK . "../helpdesk/adm_atendimento_lista.php' class='helpdesk' target='_blank'>";
        }else{
            echo "<a href='" .BI_BACK . "../helpdesk/".$prefixo."chamado_detalhe.php' class='helpdesk' target='_blank'>";
        }
        echo "<img src='$dir_help_desk_img/help.png' width='35' alt='".traduz('HELP-DESK - Clique aqui para abrir um chamado no Suporte Telecontrol.')."' border='0'>
        <br />
        <span style='color: #5A6D9C' class='txt'>Help-Desk</span></a>
        </div>";
    }elseif ($qtde_help >= 1) {
        if ($qtde_help == 1) {
            $msg_help = traduz("Você tem % chamado pendente, aguardando sua resposta", null, null, [$qtde_help]);
        }else{
            $msg_help = traduz("Você tem % chamados pendentes, aguardando sua resposta", null, null, [$qtde_help]);
        }
        //se não for para telecontrol, tem filtro
        if(strlen($prefixo)>0) {
                echo "<div>";
                if($login_admin <> 822) {
                    echo "<a href='" .BI_BACK . "../helpdesk/".$prefixo."chamado_lista";

                    if($login_admin==432){
                            echo "_novo";
                    }

                    echo ".php";

                    if($login_fabrica ==10) {
                        echo "?atendente_busca=$login_admin";
                    }
                }else{
                    echo "<a href='" .BI_BACK . "../helpdesk/adm_atendimento_lista.php";
                }
                echo "'";
                echo " class='helpdesk' target='_blank'>
                    <span style='color: #B26769' class='txt_p' id='txt_p'>Pendências</span>

                    <br />

                    <img src='$dir_help_desk_img/help-vermelho.jpg' width='35' alt='$msg_help' title='$msg_help' border='0'>

                    <br />

                    <span style='color: #B26769' class='txt'>Help-Desk</span>
                </a>
                </div>";
        } else {
            echo "
                <div>
                <a href='" .BI_BACK . "../helpdesk/".$prefixo."chamado_lista.php?status=Análise&exigir_resposta=t&assist=assist' class='helpdesk' target='_blank'>
                    <span style='color: #B26769' class='txt_p' id='txt_p'>".traduz('Pendências')."</span>

                    <br />

                    <img src='$dir_help_desk_img/help-vermelho.jpg' width='35' alt='$msg_help' title='$msg_help' border='0'>

                    <br />

                    <span style='color: #B26769' class='txt'>Help-Desk</span>
                </a>
                </div>";
        }
    }

    if ($login_fabrica == 177){
        $sql_rp = "SELECT admin FROM tbl_admin WHERE fabrica = {$login_fabrica} AND admin = {$login_admin} AND responsavel_postos IS TRUE";
        $res_rp = pg_query($con, $sql_rp);
        if (pg_num_rows($res_rp)){

            $sql_coleta_atrasada = "
                SELECT COUNT(f.faturamento) AS qtde
                FROM tbl_faturamento f
                INNER JOIN tbl_posto_fabrica pf ON pf.distribuidor = pf.posto AND pf.fabrica = $login_fabrica
                INNER JOIN tbl_posto p ON p.posto = pf.posto
                INNER JOIN tbl_fabrica fb ON fb.fabrica = f.fabrica AND fb.posto_fabrica = f.posto AND fb.fabrica = $login_fabrica
                WHERE f.info_extra->'coleta_solicitada' IS NULL
                AND (CURRENT_DATE - f.emissao) >= 10 ";
            $res_coleta_atrasada = pg_query($con, $sql_coleta_atrasada);

            if (pg_num_rows($res_coleta_atrasada) > 0 ){
                $qtde_coleta_atrasada = pg_fetch_result($res_coleta_atrasada, 0, 'qtde');
                echo "<div><a href='extrato_posto_devolucao_controle_anauger.php?coleta_atrasada=sim' target='_blank' >
                <img style='width: 45px;' src='imagens/delivery-truck.png'>
                    <text style='float: right; margin-right: 14px; font-weight: bold;'>$qtde_coleta_atrasada</text></a>
                </div>";
            }

            $sql_entrega_atrasada = "
                SELECT COUNT(f.faturamento) AS qtde
                FROM tbl_faturamento f
                INNER JOIN tbl_posto_fabrica pf ON pf.distribuidor = pf.posto AND pf.fabrica = $login_fabrica
                INNER JOIN tbl_posto p ON p.posto = pf.posto
                INNER JOIN tbl_fabrica fb ON fb.fabrica = f.fabrica AND fb.posto_fabrica = f.posto AND fb.fabrica = $login_fabrica
                WHERE f.info_extra->'chegada_pedido' IS NULL
                AND (CURRENT_DATE - (f.info_extra->>'coleta_realizada')::date >= 20) ";
            $res_entrega_atrasada = pg_query($con, $sql_entrega_atrasada);

            if (pg_num_rows($res_entrega_atrasada) > 0 ){
                $qtde_entrega_atrasada = pg_fetch_result($res_entrega_atrasada, 0, 'qtde');
                echo "<div><a href='extrato_posto_devolucao_controle_anauger.php?entrega_atrasada=sim' target='_blank' >
                    <img style='width: 45px;' src='imagens/shipped.png'>
                    <text style='float: right; margin-right: 14px; font-weight: bold;'>$qtde_entrega_atrasada</text></a>
                </div>";
            }
        }
    }

    if ($login_fabrica != 10 && $login_live_help == 't') {
    ?>
        <div id='helpdesk-chat'>
            <?php
            if (strtotime(date('Y-m-d H:i')) >= strtotime(date('Y-m-d 09:00')) && strtotime(date('Y-m-d H:i')) <= strtotime(date('Y-m-d 17:30'))) {
                if (!empty($login_email)) {
                ?>
                    <a href="javascript:void(window.open('https://tchat.telecontrol.com.br/livechat/084f77e7ff357414d5fe4a25314886fa312b2cff?email=<?=$login_email?>&nome=<?=$login_nome_completo?>&admin=<?=$login_login?>&fabrica=<?=$login_fabrica?>'))">
                <?php
                } else {
                ?>
                    <a href="javascript:void(alert('Para acessar o Chat é necessário que o usuário tenha um e-mail cadastrado.'))">
                <?php
                }
                ?>
                    <img src="imagens/chat-help.png" /><br />
                </a>
                <span>Help-Desk Chat</span>
                <span class='online' >OnLine</span>
            <?php
            } else {
            ?>
                <span onclick='alert("Horário de atendimento: 09:00 às 17:30");' >
                    <img class='offline' src="imagens/chat-help.png" /><br />
                    <span>Help-Desk Chat</span>
                    <span class='offline' >OffLine</span>
                </span>
            <?php
            }
            ?>
        </div>
    <?php
    }

    if(in_array($login_fabrica, array(1, 3))){
        ?>
            <div id="chatonline">
                <!-- <a href="http://tchat.telecontrol.com.br" target='_blank' > -->
                <a href="../autologin_tchat.php?env=admin" target='_blank' >
                    <center><img src="../imagens/botoes/chatonline.png" /></center>
                </a>
            </div>
        <?php
    }

    $sql = "SELECT privilegios from tbl_admin where admin = $login_admin";
    $res = pg_query($con,$sql);

    $privilegios_adm = pg_fetch_result($res, 0, 0);

    if ((in_array($login_fabrica, array(11,15,156,172)) OR $moduloProvidencia) and (strpos($privilegios_adm, "*") !== false || strpos($privilegios_adm, "call_center") !== false)) {
        if ($login_fabrica == 15) {
            include "pendencia_atendimento_retorno.php";
	    } else if (in_array($login_fabrica, array(11, 156, 172)) OR (!in_array($login_fabrica,array(30)) AND $moduloProvidencia)) {
            include "pendencia_atendimentos.php";
        } else {
            include_once "pendencia_atendimentos_centralizado.php";
        }
    }

    if ($login_fabrica != 10) {
        $classIcon = "menu_sidebar2";
        if (in_array($login_fabrica, [1,42,3,178,203])) {
            $classIcon = "menu_sidebar3";
            echo "</div>";
            echo "<div id='menu_sidebar2'>";
        }
    }

    if ($mostra_info_interacao_pendente) {
        include "os_aguardando_interacao.php";
    }

    if ($interacao_pendente_os_pedido) {
        include "aguardando_interacao.php";
    }

    if (in_array($login_fabrica, array(30,163,203))) {
        if ($login_fabrica == 30) {
            echo "<div id='posicao_icon'>";
            $margin_top = '0px;';
            include_once BI_BACK . "pendencia_helpdesk_posto.php";
            echo "</div>";
        } else {
            $margin_top = '0px;';
            include_once BI_BACK . "pendencia_helpdesk_posto.php";
        }
    }

    if (in_array($login_fabrica, array(175)) && count(array_filter(explode(",", $login_privilegios), function($v) { if (in_array($v, array("cadastros", "auditoria", "*"))) { return true; } })) > 0) {
        $sqlFerramentas = "
            SELECT COUNT(*)
            FROM tbl_posto_ferramenta
            WHERE fabrica = {$login_fabrica}
            AND ativo IS TRUE
            AND aprovado IS NOT NULL
            AND ((validade_certificado - CURRENT_DATE) <= 60);
        ";
        $resFerramentas = pg_query($con, $sqlFerramentas);	
        
        $count_ferramentas = pg_fetch_result($resFerramentas, 0, 0);

	$sqlFerramentasaVencer = "
		SELECT COUNT(*)
                FROM tbl_posto_ferramenta
                WHERE fabrica = {$login_fabrica}
                AND ativo IS TRUE
                AND aprovado IS NOT NULL
                AND ((validade_certificado - CURRENT_DATE) <= 60)
                AND CURRENT_DATE < validade_certificado;
        ";

        $resFerramentasaVencer = pg_query($con, $sqlFerramentasaVencer);

        $count_ferramentas_a_vencer = pg_fetch_result($resFerramentasaVencer, 0, 0);

        if ($count_ferramentas > 0) {
        ?>
            <div id="ferramentas_vencimento" >
		<?php if ($count_ferramentas_a_vencer > 0) { ?>
	                <span id="ferramentas_vencimento_count" ><?=$count_ferramentas?></span>
		<?php } ?>
                <a href="vencimento_ferramentas.php" target='_blank' >
                    <img src="imagens/tools.png" />
                </a>
                <span id="ferramentas_vencimento_titulo" ><?=traduz('Ferramentas a vencer')?></span>
            </div>
        <?php
        }
        
        $sqlFerramentas = "
			SELECT COUNT(*)
			FROM tbl_posto_ferramenta
			WHERE fabrica = {$login_fabrica}
			AND ativo IS TRUE
			AND aprovado IS NULL
			AND reprovado IS NULL
		";
		$resFerramentas = pg_query($con, $sqlFerramentas);
		
		$count_ferramentas = pg_fetch_result($resFerramentas, 0, 0);
		if ($count_ferramentas > 0) {
		?>
			<div id="ferramentas_novas" >
				<span id="ferramentas_novas_count" ><?=$count_ferramentas?></span>
				<a href="auditoria_ferramentas.php" target='_blank' >
					<img src="imagens/tools.png" />
				</a>
				<span id="ferramentas_novas_titulo" ><?=traduz('Ferramentas aguardando auditoria')?></span>
			</div>
		<?php
		}
    }

    if ($login_fabrica != 10) {
        echo "</div>";
        echo "<div id='".$classIcon."'>";
    }
    
    if ($atendimentoML == true) {
        include "pendencia_atendimentos_melibre.php";
    }

    if (in_array($login_fabrica, [138])) {
        include 'pendencia_pesquisas_satisfacao.php';
    }

    echo "</div></td>";

    echo "</tr>";
    echo "</table>";

    #MONTEIRO 2904133
    echo "<div style='width:850px; margin: 0 auto;'>";
        $sql_doc = "SELECT  tbl_change_log.change_log,
                            tbl_change_log.titulo,
                            tbl_change_log.change_log_interno AS rash,
                            tbl_change_log.fabrica,
                            tbl_change_log.data + interval '7 days' - current_date AS new_date
                        FROM tbl_change_log
                        WHERE tbl_change_log.fabrica = $login_fabrica
                        AND tbl_change_log.ativo = 't'";
        $res_doc = pg_query($con, $sql_doc);
            if(pg_num_rows($res_doc) > 0){
                $doc_telecontrol    = BI_BACK . 'imagens';
                include_once BI_BACK . "../class/tdocs.class.php";
                $nova_versao    = pg_fetch_result($res_doc, 0, 'titulo');
                $new_date       = pg_fetch_result($res_doc, 0, 'new_date');
                $rash           = pg_fetch_result($res_doc, 0, 'rash');
                $fabrica        = pg_fetch_result($res_doc, 0, 'fabrica');

                $s3_c = new TDocs($con, $fabrica);
                if($new_date <= 0){
                    $id_doc = "doctelecontrol_text";
                    $text_versao = "Doc. Versão: ";
                }else{
                    $id_doc = "doctelecontrol_pendencia";
                    $text_versao = "Nova versão: ";
                }

                $sql_tdocs = "SELECT tdocs
                                FROM tbl_tdocs
                                WHERE fabrica = $fabrica
                                AND tdocs_id = '$rash'";
                $res_tdocs = pg_query($con, $sql_tdocs);
                if(pg_num_rows($res_tdocs) > 0){
                    $tdocs_id = pg_fetch_result($res_tdocs, 0, 'tdocs');
                    $link_doc_tdocs = $s3_c->getDocumentLocation($tdocs_id);
                }

        ?>
                <div id='doc_fabrica'>
                    <span>Doc-<?=$login_fabrica_nome?></span>
                </div>
                <div id="<?=$id_doc?>" ><?=$text_versao?><?=$nova_versao?> </div>
                <div id="doctelecontrol">
                    <a href="<?=$link_doc_tdocs?>" download >
                        <center><img src="<?=$doc_telecontrol?>/icone_doc_tc.png" alt="Clique para baixar a ultima versão da documentação" /></center>
                    </a>
                    <a href='relatorio_documentacao.php?fabrica=<?=$login_fabrica?>' target='_blank'>
                        <span><?=traduz('Outras versões')?></span>
                    </a>
               </div>
        <?php
        }
    echo "</div>";
    #FIM Monteiro

    echo "<table border='0' style='margin: 0 auto; margin-bottom: 4px;' width='700px' cellpadding='0' cellspacing='0' align = 'center' class='no-print'>";
    echo "<tr>";
    echo "<td class='submenu_item' width='100%' align='center' style='text-align: center;'>";

     //&& !in_array($login_fabrica, [180,181,182])
    if (!$admin_e_promotor_wanke) { // HD 685194
        subMenu(include(BI_BACK . "menus/submenu_$layout_menu.php"));
    }
    echo "</td>";
    echo "</tr>";
    echo "</table>";
    if(!in_array($login_fabrica, array(108,111))){
        if ($login_fabrica == 87){
            echo "
            <map name='menu_map' class='no-print'>
            <area shape='rect' id='liberado'coords='014,0,090,24' href='" . BI_BACK . "menu_gerencia.php'>
            <area shape='rect' coords='100,0,176,24' menu_callcenter.php style='cursor:not-allowed'>
            <area shape='rect' coords='190,0,263,24' href='" . BI_BACK . "menu_cadastro.php' >
            <area shape='rect' coords='276,0,353,24' href='" . BI_BACK . "menu_tecnica.php' >
            <area shape='rect' coords='362,0,439,24' menu_financeiro.php' style='cursor:not-allowed'>
            <area shape='rect' coords='450,0,527,24' menu_auditoria.php' style='cursor:not-allowed'>
            <area shape='rect' coords='541,0,622,24' href='" . BI_BACK . "logout.php'>
            </map>";
        } else {
            echo "
            <map name='menu_map' class='no-print'>
            <area shape='rect' id='liberado'coords='014,0,090,24' href='" . BI_BACK . "menu_gerencia.php'>
            <area shape='rect' coords='100,0,176,24' href='" . BI_BACK . "menu_callcenter.php'>
            <area shape='rect' coords='190,0,263,24' href='" . BI_BACK . "menu_cadastro.php'>
            <area shape='rect' coords='276,0,353,24' href='" . BI_BACK . "menu_tecnica.php'>
            <area shape='rect' coords='362,0,439,24' href='" . BI_BACK . "menu_financeiro.php'>
            <area shape='rect' coords='450,0,527,24' href='" . BI_BACK . "menu_auditoria.php'>
            <area shape='rect' coords='541,0,622,24' href='" . BI_BACK . "logout.php'>
            </map>";
        }
    } else { 
        echo "
        <map name='menu_map'>
        <area shape='rect' coords='014,0,090,24' href='" . BI_BACK . "menu_gerencia.php'>
        <area shape='rect' coords='190,0,263,24' href='" . BI_BACK . "menu_cadastro.php'>
        <area shape='rect' coords='276,0,353,24' href='" . BI_BACK . "menu_tecnica.php'>
        <area shape='rect' coords='362,0,439,24' href='" . BI_BACK . "menu_financeiro.php'>
        <area shape='rect' coords='450,0,527,24' href='" . BI_BACK . "menu_auditoria.php'>
        <area shape='rect' coords='541,0,622,24' href='" . BI_BACK . "logout.php'>
        </map>";
    }

} 
    /* SOLICITADO PELO WALDIR NA DATA 06-09-2016 */
    $domain_teste = $_SERVER['HTTP_HOST'];
    $servidor_teste = strpos($domain_teste, 'devel');
    if($servidor_teste !== false){
    ?>
        <table width="700px" class='no-print' border='0' style="margin:0 auto;" class='no-print' >
            <tr height="45" >
                <td style='font-size: 16px; font-weight: bold; font-family: arial;text-align: center; color:#FFFFFF; border-bottom-width:medium;border-bottom-color:#E4E4E4;background-color:#E0123F; border-radius: 7px;'>AMBIENTE DE TESTES</td>
            </tr>
        </table>
    <?php
    }
    /* FIM */

    if ($login_fabrica == 183 AND ($login_privilegios == '*' OR $admin_sap_login == "t")){ ?>
        <div style="background-color: #cae4e8; width: 700px; text-align: center; margin: auto; border-radius: 7px; height: 38px;">
            <a style="text-decoration: none;" href="relatorio_agendamentos_pendentes.php">
                <span style="line-height: 2.0em !important; font-weight: bold; color: #3a87ad; font-size: 18px; padding-bottom: 4px; padding-top: 4px; padding-left: 190px; padding-right: 190px;">Relatório Agendamentos Pendentes</span>
            </a>
        </div>
    <?php
    }
?>
<!------------------AQUI COMEÇA O SUB MENU ---------------------!-->
<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor="#5A6D9C" align="center" style="margin:0 auto;" class='no-print'>
    <tr height="25">
        <td width='10' style="border-bottom-width:medium; border-bottom-style:solid;
border-bottom-color:#E4E4E4;"><img src="<?=BI_BACK?>imagens/canto_esquerdo.jpg" /></td>
        <td style='font-size: 16px; font-weight: bold; font-family: arial;text-align: center; color:#FFFFFF; border-bottom-width:medium; border-bottom-style:solid;
    border-bottom-color:#E4E4E4;'> <? echo "$title" ?> </td>
        <td width='10' style="border-bottom-width:medium; border-bottom-style:solid;
border-bottom-color:#E4E4E4;"><img src="<?=BI_BACK?>imagens/canto_direito.jpg" /></td>
    </tr>
</table>

<table width="700px"  border="0" align="center" cellpadding="0" cellspacing="0" bordercolor="#D9E2EF" style="margin:0 auto;" class='no-print'>
<tr height="70">
    <?php
    $sql = "SELECT nome_completo, fone, email, dia_nascimento, mes_nascimento FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $login_admin";
    $res = pg_query($con,$sql);
        $msg_atualiza       = "";
        $iDisplay           = 'none';
        $bDisplay           = 'none';
    if (pg_num_rows($res) > 0) {
        $nome_admin         = pg_fetch_result($res, 0, nome_completo);
        $fone_admin         = pg_fetch_result($res, 0, fone);
        $email_admin        = pg_fetch_result($res, 0, email);
        $dia_nascimento     = pg_fetch_result($res, 0, dia_nascimento);
        $mes_nascimento     = pg_fetch_result($res, 0, mes_nascimento);
        $valida_email_admin = preg_match("/^[A-Za-z0-9._%-]+@([A-Za-z0-9.-]+){1,2}([.][A-Za-z]{2,4}){1,2}$/", $email_admin);

        if (!$nome_admin or !$fone_admin or !$email_admin or !$dia_nascimento or !$mes_nascimento or
            !$valida_email_admin) {
                if ($dia_nascimento and $mes_nascimento) {
                    $iDisplay = 'block';
                    $bDisplay = 'inline';
                }
                $msg_atualiza = (!$valida_email_admin) ? traduz("o.email.cadastrado.nao.e.valido.por.favor.informe.o.email.correto")
                                                       : traduz("o.sistema.detectou.que.alguns.dados.seus.estão.desatualizados");
        }
    }

    function escreveData($data) {
        $vardia = substr($data, 8, 2);
        $varmes = substr($data, 5, 2);
        $varano = substr($data, 0, 4);

        $convertedia = date ("w", mktime (0,0,0,$varmes,$vardia,$varano));

        $diaSemana = array(traduz("Domingo"), traduz("Segunda-Feira"), traduz("Terça-Feira"), traduz("Quarta-Feira"), traduz("Quinta-Feira"), traduz("Sexta-Feira"), traduz("Sábado"));

        $mes = array(1=>traduz("Janeiro"), traduz("Fevereiro"), traduz("Março"), traduz("Abril"), traduz("Maio"), traduz("Junho"), traduz("Julho"), traduz("Agosto"), traduz("Setembro"), traduz("Outubro"), traduz("Novembro"), traduz("Dezembro"));

        if ($varmes < 10) $varmes = substr($varmes,1,1);

        return $diaSemana[$convertedia] . ", " . $vardia  . " de " . $mes[$varmes] . " de " . $varano;
    }

    // Utilizar da seguinte maneira
    //echo escreveData("2005-12-02");?>
    <td>
    <div style='float:left;padding-top:1px;padding-bottom:1px;width:220px;'>
<?php
        // Logos cabeçalho admin //

        $imagensLogo = include(BI_BACK . '../logos.inc.php');
        $url_logo = $path_logo . '/' . getFabricaLogo($login_fabrica, $imagensLogo);

		$url_logo = (empty($url_logo)) ? $path_logo.'/'.$login_fabrica_logo : $url_logo;

        switch ($login_login) {
            case 'suggar': $url_logo = "$path_logo/suggar.jpg";      break;
        }
         switch ($login_admin) {
            case   '57': $url_logo = "$path_logo/telecontrol_new.gif"; break;
            //case '1097': $url_logo = "$path_logo/telecontrol_new.gif"; break;
        }
        if ($login_fabrica == 46 and $AWS_sdk_OK) {
            include_once AWS_SDK;
            $s3logo   = new AmazonS3();
            if (is_object($s3logo)) {
                $logoS3 = 'logos/' . basename($url_logo);
                $bucket = 'br.com.telecontrol.posvenda-downloads';
                $url_logo = ($usaLogoS3 = $s3logo->if_object_exists($bucket, $logoS3)) ? $s3logo->get_object_url($bucket, $logoS3) : $url_logo;
            }
        }
        if ($usaLogoS3 or file_exists($url_logo)) {
            $logo_attr = ($usaLogoS3) ? " style='max-height:55px;max-width:210px;'" : logoSetSize($url_logo, 210, 55);
            if ($altera_logo_TcNet) {
                echo "<img src='$url_logo' alt='$login_fabrica_site' onclick='toggleCustomizePopUp(\"admLogoTCNet\")' border='0'$logo_attr />";
            }else{

                // rand() de imagens para cabeçalho admin //
                if($max > -1){

                    $rand = rand(0, $max);
                    $url_logo_fabrica = $path_logo . '/' . $imagensLogo[$login_fabrica][$rand];

                    echo "<a href='$login_fabrica_site' rel='nozoom'>".
                        "<img src='$url_logo_fabrica' alt='$login_fabrica_site' style='float:left;max-height:70px;max-width:210px;' border='0'/>".
                        "</a>";
                }else{
                    if($altera_logo_TcNet) $onclick= "onclick='toggleCustomizePopUp(\"admLogoTCNet\")'";

                    echo "<a href='$login_fabrica_site' rel='nozoom'>";
                    // if($login_fabrica == 11){
                    //     echo "<img src='$url_logo' alt='$login_fabrica_site' $onclick border='0' style='height:60px;max-width:250px;' />";
                    // }else{
                        echo "<img src='$url_logo' alt='$login_fabrica_site' $onclick border='0' style='max-height:55px;max-width:240px;' />";
                    //}
                    echo "</a>";
                
                }
            }// fim rand()
        } else {
            if ($altera_logo_TcNet) {
                echo "<a href='javascript:toggleCustomizePopUp(\"admLogoTCNet\")'>Alterar Logo<img src='$url_logo' alt='$login_fabrica_site' onclick='toggleCustomizePopUp(\"admLogoTCNet\")' border='0'$logo_attr /></a>";
            } else {
                echo "<a href='$login_fabrica_site' target='_new'>$login_fabrica_nome</a>\n";
            }
        }
    $ano_tc = date('Y');
?>
    </div>
    </td>
    <td align='center'>

<?
if($login_fabrica == 151){ //HD-3589712
#    echo '<a id="linkreclameaqui" target="_blank" href="https://premio.reclameaqui.com.br/votacao"><img style="height:80px;" src="../imagens/mondial_reclame_aqui.jpg" border="0" /></a>';
}
// Acesso multi-fábrica
echo $multi_admin_html;

#echo $_SERVER['PHP_SELF'];
if(strpos($_SERVER['PHP_SELF'],"/bi/")){
#echo "primeiro";
?>
    <script type='text/javascript' src='../../js/FancyZoom.js'></script>
    <script type='text/javascript' src='../../js/FancyZoomHTML.js'></script>
<?
}else{
?>
        <script type='text/javascript' src='../js/FancyZoom.js'></script>
        <script type='text/javascript' src='../js/FancyZoomHTML.js'></script>
<?
}
?>
        <script type="text/javascript">
            setupZoom();
            document.getElementById('ZoomImage').useMap = 'calendario_tc';
        </script>
    </td>
    <td style="font-size: 12px; font-family: arial;text-align:right;font-weight:bold" class='borda'>
<?php
	if (!$sem_menu) {
        $arr_cook_avatar = explode('/', $cook_avatar);
        $tDocsId = array_pop($arr_cook_avatar);
        $situacao_tdocs = "";

        $sql_foto = "SELECT situacao FROM tbl_tdocs WHERE fabrica = $login_fabrica AND tdocs_id = '$tDocsId'";
        $res_foto = pg_query($con,$sql_foto);

        if (pg_num_rows($res_foto) > 0) { 
            $situacao_tdocs = pg_fetch_result($res_foto,0,'situacao');
        }

        if ($situacao_tdocs == 'ativo'){
            $imagem_do_admin = $cook_avatar;        
        }else{
            $imagem_do_admin = BI_BACK . "../imagens/sem_imagem.jpg' title='".traduz('Clique aqui para subir sua foto!')."'";
        }
        
        echo "<img src='$imagem_do_admin' id='adm_foto' onClick='toggleCustomizePopUp(\"admCfgFrm\")' />\n";
    }
        
        $data = date("Y-m-d");
        echo escreveData($data);
        //echo date(" - H:i");
    if (!$sem_menu) {
        echo " <br /> ".traduz('Usuário').": <span style='color:red;cursor:help' id='cfgUsr' onClick='toggleCustomizePopUp(\"admCfgFrm\")'>".ucfirst($login_login);
        if(strlen($msg_atualiza) > 0) {
            echo "&nbsp;&nbsp;<img src='" . BI_BACK . "../imagens/alerta2.gif' /><span class='tooltip'><span class='top'></span><span class='middle'>$msg_atualiza</span><span class='bottom'></span></span>";
        }
    } else {
        echo " <br /> Usuário: <span style='color:red;cursor:help' id='cfgUsr' >".ucfirst($login_login);
    }
        echo "</span>";
?>
    </td><!-- HD 205958: Help nas telas dos programas --><?php
    if ($tbl_help_help) {
        echo '<td>';
            echo "<img src='" . BI_BACK . "imagens/help.jpg' title=\"Clique aqui para ajuda sobre este programa\" onclick=\"window.frames.iframe_tbl_help.abre_help_tbl_help($tbl_help_help); document.getElementById('iframe_tbl_help').style.display='block';\" style=\"cursor:pointer;\">";
        echo '</td>';
    }?>
</tr><?php

if (($login_fabrica == 11 or $login_fabrica == 30 or $login_fabrica == 172) and ($title=='Cadastros do Sistema' or $title=='MENU GERÊNCIA')) {

    $sql = "SELECT log_integracao from tbl_log_integracao where fabrica = $login_fabrica and confirmar_leitura = 'f'";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $cor = 'red';
        $texto = "<a href='log_erro_integracao.php'><font color='white' size=+1><b>".traduz('Existem Erros de Integracao - Clique aqui para Visualizar')."</FONT></b></a>";

    }

}

if (strlen(trim($texto)) > 0 AND $login_fabrica != 178) {?>
    <tr>
        <td colspan=3 bgcolor='<?echo $cor;?>'><div class="frm-on-os" id="displayArea"><?echo $texto;?>&nbsp;</div></td>
    </tr><?php
}

if (strlen(trim($msg_validade_cadastro)) > 0) {
    echo "<tr height='3'>";
    echo "<td align='center' colspan=3 bgcolor='red' style='border-top-width:medium;
    border-top-style:solid;border-top-color:#E4E4E4;'>$msg_validade_cadastro</td>";
    echo "</tr>";
} else {
    echo "<tr height='6'>";
    echo "<td align='center' colspan=3 bgcolor='#5A6D9C' style='border-top-width:medium;
    border-top-style:solid;border-top-color:#E4E4E4;'></td>";
    echo "</tr>";
}

/*******************************************************
 * Verifica se o e-mail é válido.                      *
 * Se não for, vai mostrar o iframe para ele cadastrar *
 * um e-mail válido.                                   *
 *******************************************************/
?>

</table>
<? if  (!$sem_menu) {?>
<iframe src="<?=BI_BACK?>admin_personaliza.php" id='admCfgFrm' frameborder="0"
      style='width:555px;height:320px;position:fixed;z-index:200;top:22%;left:45%;background:transparent;display:<?=$iDisplay?>'></iframe>
  <br />
<?php
}
if  ($altera_logo_TcNet) {?>
<iframe src="<?=BI_BACK?>logo_tcnet.php" id='admLogoTCNet' frameborder="0"
      style='width:666px;height:456px;position:fixed;z-index:200;top:22%;right:45%;background:transparent;overflow:hide;display:<?=$iDisplay?>'></iframe>
  <br />
<?php
}

#------------- Programa Restrito ------------------#
$sql = "SELECT privilegios from tbl_admin where admin = $login_admin";
$res = pg_query($con,$sql);

$privilegios_adm = pg_fetch_result($res, 0, 0);

if (strpos ($privilegios_adm,"*") === false) { // 1 - Usuário 'master'
    /* Define os ítens do menu...
     * HD 684194
     * - Consulta OS:       http://www.telecontrol.com.br/assist/admin/os_consulta_lite.php
     * - Consulta Pedidos:  http://www.telecontrol.com.br/assist/admin/pedido_parametros.php
     * - Abre Chamado:      http://www.telecontrol.com.br/assist/admin/callcenter_interativo_new.php
     * - Consulta Chamado:  http://www.telecontrol.com.br/assist/admin/callcenter_parametros_interativo.php
     * - Cadastrar pedido:  http://www.telecontrol.com.br/assist/admin/pedido_cadastro.php
     * - Consultar posto:   http://www.telecontrol.com.br/assist/admin/posto_consulta.php
     * - Vista Explodida e Comunicados (apenas visualizar, conforme esta na aba Call Center):
     *                      http://www.telecontrol.com.br/assist/admin/comunicado_produto_consulta.php
     */

    if ($login_fabrica == 91 and $admin_e_promotor_wanke) {
         $a_telas_promotor_wanke = array(
             'os_press',
             'posto_consulta',
             'pedido_cadastro',
             'menu_callcenter',
             'pedido_consulta',
             'os_consulta_lite',
             'pedido_parametros',
             'pedido_admin_consulta',
             'callcenter_interativo_new',
             'comunicado_produto_consulta',
             'callcenter_parametros_interativo',
             'callcenter_consulta_lite_interativo',
         );
        // Tela que está tentando acessar, sem extensão (assim, pode pegar _test _teste _685194, etc...)
        $pw_tela_atual = preg_replace('/_\d{6}/', '', basename($PHP_SELF, '.php'));

        //echo "Conferindo login Promotor...<br>".preg_replace('/_\d{6}/', '', basename($PHP_SELF, '.php'));
        if (!in_array($pw_tela_atual, $a_telas_promotor_wanke)) {
            echo "<p><hr><center><h1>*".traduz('Sem permissão para acessar este programa')."</h1></center><p><hr>";
            exit;
        }
    } else {

        $sql = "  SELECT programa
                    FROM tbl_programa_restrito
                   WHERE tbl_programa_restrito.programa = '$PHP_SELF'
                     AND tbl_programa_restrito.fabrica  = $login_fabrica";
        $res = pg_query($con,$sql);


        if (pg_num_rows($res) > 0) {
            $programa = pg_fetch_result($res,0,programa); //HD 72857

            if($login_fabrica <> 3 OR ($login_fabrica == 3 AND $programa <> '/assist/admin/os_cadastro.php')){
                $sql = "SELECT programa
                        FROM   tbl_programa_restrito
                        JOIN   tbl_admin USING (admin)
                        WHERE  tbl_programa_restrito.programa = '$PHP_SELF'
                        AND    tbl_programa_restrito.admin    = $login_admin
                        AND    tbl_programa_restrito.fabrica  = $login_fabrica ";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res) == 0) {
                    echo "<p><hr><center><h1>*".traduz('Sem permissão para acessar este programa')."</h1></center><p><hr>";
                    exit;
                }
            }
        }
    }
}

// Permite bloquear ou desbloquear a aprovação de chamados de desenvolvimento, de acordo com a tbl_hd_janela
$sql_lock =  "SELECT hd_janela, data_final::DATE = CURRENT_DATE AS data_fim
                FROM tbl_hd_janela
               WHERE CURRENT_TIMESTAMP BETWEEN data_inicial AND data_final
                 AND (fabricas IS NULL OR fabricas @> ARRAY[$login_fabrica])";

$res_lock = pg_query($con, $sql_lock);
$libera_hds = false;// Vai que dá erro...

if (is_resource($res_lock)) {

    $libera_hds = (pg_num_rows($res_lock)>0); // Se a query devolve resultados, é porque tem janela aberta.
    if (pg_num_rows($res_lock)>0){

        $mostrarAvisoFim = (pg_fetch_result($res_lock, 0, 'data_fim')== 't');
    }
}

// echo array2table(array(
//      0=>array(
//          'libera_hds' => $libera_hds,
//          'Cookie'     => $_COOKIE['HDComunicadoJanela'],
//          'DataFim'    => pg_fetch_result($res_lock, 0, 'data_fim'),
//          'adminSup'   => array(
//              $login_admin,$login_login,var_export($login_supervisor, true)
//          )
//      )
//  ),'Teste');

if ($libera_hds and $login_supervisor and $_COOKIE['HDComunicadoJanela'] != 'ja_li') {

    if ($mostrarAvisoFim) {
        include "tc_comunicado_janela_fim.html";
    } else {
        include "tc_comunicado_janela.html";
    }

}

//Mensagem que bloqueia o HelpDesk
$mlg_hoje = strtotime('now');

if ($mlg_hoje >= strtotime('2017-11-24') and $mlg_hoje < strtotime('2017-11-30 23:00:00')
    and strpos($PHP_SELF, 'menu_') > 0
    and $login_fabrica <> 87) {
	if($_COOKIE['HdBlackFriday2'] <> 'ja_li') {
		include "tc_comunicado_bf.html";
	}
}

if ($mlg_hoje >= strtotime('2012-11-10 00:00:01') and $mlg_hoje < strtotime('2012-11-24 23:00:00'))
    include BI_BACK . 'dropdown_mensagem_noHD.html';
if (strpos($PHP_SELF, 'menu_') > 0) {
    include BI_BACK . '../helpdesk/popup_anivs.php';
}

$comunicado_tc_obrigatorio = true;

/*
 * Comentado temporáriamente
 *
// Comunicado de leitura obrigatória sobre o novo HelpDesk e o chat
$sql_c_tc = "SELECT admin,data_confirmacao
               FROM tbl_comunicado_tc_leitura
              WHERE admin            = $login_admin
                AND comunicado_tc    = 1
                AND data_confirmacao > '2012-02-10'";
$res_c_tc = pg_query($con, $sql_c_tc);

if (pg_num_rows($res_c_tc) == 0 and file_exists('../tc_comunicado.php')) {
    include '../tc_comunicado.php';
    if ($comunicado_tc_obrigatorio) {
        include "rodape.php";
        exit();
    }
}
 **/

?>
<map name="calendario_tc">
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='243,70,362,83'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='471,70,591,83'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='700,83,820,97'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='12,230,134,242'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='243,229,362,244'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='472,230,591,243'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='700,230,820,243'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='13,402,133,417'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='243,376,362,388'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='472,389,590,402'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='701,388,771,402'></area>
    <area shape='rect' nohref title='Data Inicial para novos projetos' alt='Data Inicial para novos projetos' coords='700,70,724,84'></area>
    <area shape='rect' nohref title='Data final para novos projetos' alt='Data final para novos projetos' coords='290,428,313,443'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='266,96,290,109'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='796,69,819,84'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='37,214,60,228'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='110,388,132,401'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='337,388,362,402'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='568,374,591,389'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='544,401,567,416'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='724,427,749,442'></area>
    <area shape='poly' nohref title='Férias Área Comercial' alt='Férias Área Comercial' coords='702,415,701,428,750,428,750,442,820,443,820,416,701,416'></area>
    <area shape='rect' nohref title='Data final visita comercial' alt='Data final visita comercial' coords='701,403,723,417'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='243,111,315,124'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='472,110,591,124'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='700,111,820,124'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='12,269,110,283'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='339,214,361,231'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='242,270,362,283'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='471,270,590,284'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='700,270,821,284'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='13,429,133,444'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='243,416,361,430'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='471,430,591,443'></area>
    <area shape='rect' nohref title='Prazo final novos contratos' alt='Prazo final novos contratos' coords='544,56,567,70'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='289,56,313,71'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='569,56,590,69'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='727,70,747,84'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='84,214,108,230'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='771,214,796,231'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='13,388,37,403'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='243,390,267,401'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='544,375,566,389'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='773,388,796,402'></area>
    <area shape='rect' nohref title='Data Final alteração agenda semestral' alt='Data Final alteração agenda semestral' coords='472,98,495,111'></area>
    <area shape='rect' nohref title='Data Final alteração agenda semestral' alt='Data Final alteração agenda semestral' coords='40,388,59,405'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='472,85,494,99'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='700,99,723,110'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='13,245,37,256'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='243,244,266,256'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='471,244,494,256'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='699,245,724,255'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='14,418,36,431'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='269,390,288,402'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='472,403,495,416'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='798,388,819,402'></area>
    <!-- this map has been created with eleomap. http://dhost.info/eleomap/ -->
</map>
<script type="text/javascript">
    var idioma_verifica_servidor = "<?=$cook_idioma?>";
    function toJSON (data)   {
        return $.parseJSON(data);
    }

</script>
<a href="#" class="scrollup">Scroll</a>

<?php if(in_array($login_fabrica, array(11,172))){ ?>

    <div style="margin: 0 auto; text-align: center; color: #E0123F; font-size: 16px;">
        <?=traduz('Você está logado no ambiente')?>:
        <strong style="text-transform: uppercase;"><?php echo ($login_fabrica == 11) ? "Aulik" : "Pacific"; ?></strong>
    </div>

<?php } ?>

<?
ob_end_flush();

if (!isset($plugins) && empty($plugins)) {
    $plugins = array( "shadowbox" );
    include_once BI_BACK . "plugin_loader.php";
}
