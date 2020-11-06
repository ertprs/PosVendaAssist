<!-- Todas libs js, jquery e css usadas no Assist - HD 969678 -->
<?php
	//$url_base     = "http://192.168.0.199/~guilherme/assist/"; // Local;
	$url_base     = "https://posvenda.telecontrol.com.br/assist/"; // Online;
	#$url_base     = "http://novodevel.telecontrol.com.br/~kaique/PosVenda/"; // Local;
	$http_host = $_SERVER[HTTP_HOST];
	$pagina_atual = basename($_SERVER['SCRIPT_FILENAME']); /* retorna o nome_do_arquivo.php */
?>

<!-- admin/os_cadastro.php -->
<script type="text/javascript" src="<?php echo $url_base; ?>js/bibliotecaAJAX.js"></script>
<script type="text/javascript" src="<?php echo $url_base; ?>inc_soMAYSsemAcento.js"></script>
<script type="text/javascript" src="<?php echo $url_base; ?>ajax.js"></script>

<?php if( $login_fabrica != 74 ){ /* estava no arquivo: os_cadastro_tudo.php */ ?>
    <script type="text/javascript" src="<?php echo $url_base; ?>ajax_cep.js"></script>
<?php }else{ ?>
    <script type="text/javascript" src="<?php echo $url_base; ?>ajax_cep_new.js"></script>
<?php } ?>

<script type="text/javascript" src="<?php echo $url_base; ?>js/jquery-1.7.2.js"></script>
<!--
<script type="text/javascript" src="<?php echo $url_base; ?>js/jquery-1.7.2.js"></script>

<script src="http://code.jquery.com/jquery.min.js" type="text/javascript"></script>
-->

<script type="text/javascript" src="<?php echo $url_base; ?>admin/js/jquery.mask.js"></script>
<script type="text/javascript">
    /* 9º Dígito para São Paulo-SP, digitando o DDD 11 + 9 deixará entrar o 9º caracter */
    /* Exemplos de uso das máscaras: http://igorescobar.github.com/jQuery-Mask-Plugin/ */

    <?php if($login_fabrica == 0){ ?>

    $(function()
    {
        /* Uso do input: <input maxlength="15" name="nome_do_campo" id="id_do_campo" class="telefone" /> */
        $('.telefone').each(function()
        {
            /* Carrega a máscara default do post/get conforme o valor que já vier no value */
            /* Para adicionar mais DDD's  =>  $(this).val().match(/^\(11|21\) 9/i) */
            if( $(this).val().match(/^\(1\d\) 9/i) )
            {
                $(this).mask('(00) 00000-0000', $(this).val());  // 9º Dígito
            }
            else
            {
                $(this).mask('(00) 0000-0000',  $(this).val()); /* Máscara default */
            }
        });

        $('.telefone').keypress(function()
        {
            if( $(this).val().match(/^\(1\d\) 9/i) )
            {
                $(this).mask('(00) 00000-0000'); /* 9º Dígito */
            }
            else
            {
               $(this).mask('(00) 0000-0000');  /* Máscara default */
            }
        });
    });
    /* fim - 9º Dígito para São Paulo-SP, digitando o DDD 11 + 9 deixará entrar o 9º caracter */

    <?php } ?>


    // CAMPO TELEFONE ACEITA APENAS PARENTESES, HIFEN E NUMEROS

    $(function() {
     $(".telefone").keyup(function() {
            $(this).val($(this).val().replace(/[^0-9\(\)\-]/g,''));
      });
    });
</script>


<!--
<script type="text/javascript" src="js/jquery.js"></script> - jQuery 1.2.3
-->
<script type="text/javascript" src="<?php echo $url_base; ?>js/jquery.alphanumeric.js"></script>
<!--
<script type="text/javascript" src="http://malsup.github.com/jquery.corner.js"></script> jQuery corner 2.12
-->
<script type="text/javascript" src="<?php echo $url_base; ?>js/jquery.corner.js"></script><!-- jQuery corner 1.7 -->
<!--
<script type="text/javascript" src="../js/jquery.maskedinput2.js"></script> - Masked Input 1.2.2
-->
<script type="text/javascript" src="<?php echo $url_base; ?>admin/js/jquery.maskmoney.js"></script>
<script type="text/javascript" src="<?php echo $url_base; ?>plugins/shadowbox/shadowbox.js"></script>
<link type="text/css" href="<?php echo $url_base; ?>plugins/shadowbox/shadowbox.css" rel="stylesheet" media="all">
<!-- fim - admin/os_cadastro.php -->


<!-- admin/os_consulta_lite.php -->
<!--
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js" type="text/javascript"></script>
-->

<? if ($pagina_atual <> "os_item_new.php") { ?>
<? } ?>
<script type='text/javascript' src='<?php echo $url_base; ?>js/jquery.autocomplete.js'></script>
<link type="text/css" href="<?php echo $url_base; ?>js/jquery.autocomplete.css" rel="stylesheet" />
<script type='text/javascript' src='<?php echo $url_base; ?>js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='<?php echo $url_base; ?>js/dimensions.js'></script>
<script type="text/javascript" src="<?php echo $url_base; ?>js/assist.js"></script>
<script type="text/javascript" src="<?php echo $url_base; ?>plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="<?php echo $url_base; ?>plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<link type="text/css" href="<?php echo $url_base; ?>plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />
<!-- fim - admin/os_consulta_lite.php -->


<!-- admin/posto_consulta.php - Já tinha todos js/css acima -->
<!--
<script type="text/javascript" src="js/jquery-1.4.1.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<link type="text/css" href="../plugins/shadowbox/shadowbox.css" rel="stylesheet" media="all" />
<script type="text/javascript" src="../plugins/shadowbox/shadowbox.js"></script>
linha 235
<script type="text/javascript" src="js/cal2.js"></script>
<script type="text/javascript" src="js/cal_conf2.js"></script>
-->
<!-- fim - admin/posto_consulta.php -->


<!-- admin/posto_cadastro.php -->
<!--
<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link type="text/css" href="js/jquery.autocomplete.css" rel="stylesheet" />
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script type='text/javascript' src='ajax.js'></script>
<script type='text/javascript' src='ajax_cep.js'></script>
<script type='text/javascript' src='js/jquery.maskedinput.js'></script>
-->
<script type='text/javascript' src='<?php echo $url_base; ?>admin/js/jquery.datePicker.js'></script>
<!-- fim - admin/posto_cadastro.php -->


<!-- admin/admin_senha_n.php -->
<!--
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js"></script>
<script type="text/javascript" src="../js/jquery.maskedinput.js"></script>
-->
<script type="text/javascript" src="<?php echo $url_base; ?>plugins/fixedtableheader/jquery.fixedtableheader.min.js"></script>
<script type='text/javascript' src="<?php echo $url_base; ?>plugins/jquery/apprise/apprise-1.5.min.js"></script>
<link type="text/css" href="<?php echo $url_base; ?>plugins/jquery/apprise/apprise.min.css" rel="stylesheet" />
<!-- fim - admin/admin_senha_n.php -->


<!-- admin/depara_cadastro.php -->
<!--
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link type="text/css" href="js/jquery.autocomplete.css" rel="stylesheet" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
-->
<!-- fim - admin/depara_cadastro.php -->


<!-- admin/javascript_calendario.php -->
<link type="text/css" href="<?php echo $url_base; ?>js/datePicker.v1.css" title="default" media="screen" rel="stylesheet" />
<script type="text/javascript" src="<?php echo $url_base; ?>js/datePicker.v1.js"></script>
<script type="text/javascript">
    $(document).ready(init);

    function init()
    {
        $.datePicker.setDateFormat('dmy', '/');
        $.datePicker.setLanguageStrings(
            ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
            ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
            { p : 'Anterior', n : 'Próximo', c : 'Fechar', b : 'Abrir Calendário' }
        );
        $('input.date-picker').datePicker({ startDate :'05/03/2006' });
	}
	function mostrarMensagemBuscaNomes() {
	alert("Para busca de Ordens de Serviço no sistema da Telecontrol, seguir as regras:\n\n Informar o nome completo sempre a partir do início em maiúsculas e sem acentos. Exemplo: JOSE DA SILVA SANTOS; correto: JOSE DA SILVA SANTOS; errado: SILVA SANTOS");
}
</script>
<!-- fim - admin/javascript_calendario.php -->


<!-- admin/revenda_cadastro.php -->
<!--
<script type='text/javascript' src="js/jquery-1.6.1.min.js"></script>
<link type="text/css" href="js/jquery.autocomplete.css" rel="stylesheet" />
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type='text/javascript' src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src='../ajax.js'></script>
<script type="text/javascript" src='ajax_cep.js'></script>
<link type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all" rel="stylesheet" />
<script type="text/javascript" src="../plugins/shadowbox/shadowbox.js"></script>
-->
<!-- fim - admin/revenda_cadastro.php -->


<!-- admin/fornecedor_cadastro.php -->
<!--
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
-->
<!-- fim - admin/fornecedor_cadastro.php -->


<!-- admin/feriado_cadastra.php -->
<!--
<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script type='text/javascript' src='js/jquery.maskedinput.js'></script>
<script type='text/javascript' src='js/jquery.datePicker.js'></script>
<link type='text/css' href="js/tinybox2/style.css" rel="stylesheet" />
<script type="text/javascript" src="js/tinybox2/tinybox.js"></script>
-->
<!-- fim - admin/feriado_cadastra.php -->


<!-- admin/cabecalho.php -->
<link type="text/css" href="<?php echo $url_base; ?>js/tinybox2/style.css" rel="stylesheet" />
<script type="text/javascript" src="<?php echo $url_base; ?>js/tinybox2/tinybox.js"></script>
<!-- fim - admin/cabecalho.php -->


<?php if( $pagina_atual == 'relatorio_field_call_rate_produto.php' ){ ?>

    <!-- admin/relatorio_field_call_rate_produto.php -->
    <link type="text/css" href="<?php echo $url_base; ?>js/blue/style.css" rel="stylesheet" media="print, projection, screen" />
    <!--
    <script type="text/javascript" src="js/cal2.js"></script>
    <script type="text/javascript" src="js/cal_conf2.js"></script>
    -->
    <script type="text/javascript" src="<?php echo $url_base; ?>js/jquery.tablesorter.pack.js"></script>
    <!-- fim - admin/relatorio_field_call_rate_produto.php -->

<?php } ?>


<?php if( $pagina_atual == 'pedido_parametros.php' ){ ?>

    <!-- admin/pedido_parametros.php -->
    <!--
    <script type='text/javascript' src='js/jquery.autocomplete.js'></script>
    <link type="text/css" href="js/jquery.autocomplete.css" rel="stylesheet" />
    <script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
    <script type='text/javascript' src='js/dimensions.js'></script>
    <script type='text/javascript' src="js/assist.js"></script>
    <script type='text/javascript' src='ajax.js'></script>
    -->
    <!-- fim - admin/pedido_parametros.php -->

<?php } ?>


<!-- admin/consumidor_cadastro.php -->
<!--
<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script type='text/javascript' src='js/jquery.maskedinput.js'></script>
-->
<!-- fim - admin/consumidor_cadastro.php -->


<!-- os_cadastro_ajax.php -->
<!--
<script type="text/javascript" src="admin/js/jquery-latest.pack.js"></script>
<script type="text/javascript" src="admin/js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="admin/js/jquery.blockUI.js"></script>
<script type="text/javascript" src="ajax.js"></script>
<script type="text/javascript" src="ajax_cep.js"></script>
-->
<script type="text/javascript" src="<?php echo $url_base; ?>ajax_os_cadastro.js"></script>
<!-- fim - os_cadastro_ajax.php -->


<!-- os_revenda.php -->
<!--
<script src="js/jquery-1.3.2.js" type="text/javascript"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script src="plugins/shadowbox/shadowbox.js"    type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<?php echo $url_base; ?>js/jquery.blockUI_2.39.js
-->
<link type="text/css" href="<?php echo $url_base; ?>js/jquery.readonly.css" rel="stylesheet" />
<script type="text/javascript" src="<?php echo $url_base; ?>js/jquery.readonly.js" ></script>
<script type="text/javascript" src="<?php echo $url_base; ?>js/plugin_verifica_servidor.js"></script>
<script type="text/javascript" src="<?php echo $url_base; ?>admin/js/jquery.blockUI.js"></script>
<!-- fim - os_revenda.php -->


<!-- os_revenda_consulta_lite.php -->
<!--
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
-->
<!-- fim - os_revenda_consulta_lite.php -->


<!-- admin/callcenter_interativo_new.php -->
<!--
<script type='text/javascript' src='ajax.js'></script>
<script type='text/javascript' src='js/bibliotecaAJAX.js'></script>
<script type='text/javascript' src='ajax_cep.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link type="text/css" href="js/jquery.autocomplete.css" rel="stylesheet" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>
<script type="text/javascript" src="../plugins/shadowbox/shadowbox.js"></script>
<link type="text/css" href="../plugins/shadowbox/shadowbox.css" rel="stylesheet" media="all">
-->
<script type="text/javascript" src="<?php echo $url_base; ?>js/thickbox.js"></script>
<link type="text/css" href="<?php echo $url_base; ?>js/thickbox.css" rel="stylesheet" media="screen" />
<script type="text/javascript" src="<?php echo $url_base; ?>admin/js/jquery.tabs.pack.js"></script>
<link type="text/css" href="<?php echo $url_base; ?>admin/js/jquery.tabs.css" rel="stylesheet" media="print, projection, screen" />
<!-- Additional IE/Win specific style sheet (Conditional Comments) -->
<!--[if lte IE 7]>
    <link type="text/css" href="<?php echo $url_base; ?>js/jquery.tabs-ie.css" rel="stylesheet" media="projection, screen" />
<![endif]-->
<!-- fim - admin/callcenter_interativo_new.php -->


<!-- admin/callcenter_interativo_new_britania.php -->
<!--
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script type="text/javascript" src="js/firebug.js"></script>
<?php //include 'javascript_calendario.php'; ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>
<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">
-->
<!-- fim - admin/callcenter_interativo_new_britania.php -->


<!-- revenda_cadastro.php -->
<!--
<script type="text/javascript" src="js/jquery-1.5.2.min.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.1.1.js"></script>
<script type="text/javascript" src="js/jquery.bgiframe.min.js"></script>
<link type="text/css" href="js/jquery.autocomplete.css" rel="stylesheet" />
<link type="text/css" href="css/css.css" rel='stylesheet' />
<script type="text/javascript" language="JavaScript" src="js/jquery.alphanumeric.js"></script>
-->
<!-- fim - revenda_cadastro.php -->


<!-- os_cadastro_tudo.php -->
<!--
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput-1.2.2.js"></script>
<script src="js/jquery.readonly.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="js/jquery.readonly.css">
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script language='javascript' src='js/bibliotecaAJAX.js'></script>
<script type="text/javascript" src="admin/js/jquery.maskmoney.js"></script>
-->
<?php if( $login_fabrica <> 95 && $login_fabrica < 99 ){ ?>
 <!--
    <script type="text/javascript" src="<?php echo $url_base; ?>js/jquery.blockUI_2.39.js"></script>
    <script type="text/javascript" src="<?php echo $url_base; ?>js/plugin_verifica_servidor.js"></script>
-->
<?php } ?>
<!-- fim - os_cadastro_tudo.php -->


<!-- extrato_consulta.php -->
<!--
<style type="text/css">
    @import "../plugins/jquery/datepick/telecontrol.datepick.css";
</style>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<link type="text/css" rel="stylesheet" href="js/jquery.autocomplete.css" />
<link type="text/css" rel="stylesheet" href="../plugins/shadowbox/shadowbox.css" media="all" />
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<?php /* <script type='text/javascript' src='js/jquery.ajaxQueue.js'></script> */ ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type='text/javascript' src="js/bibliotecaAJAX.js"></script>
<script type="text/javascript" src="../plugins/shadowbox/shadowbox.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="js/assist.js"></script>
-->
<script type="text/javascript" src="<?php echo $url_base; ?>js/date.js"></script>
<link type="text/css" href="<?php echo $url_base; ?>admin/js/blue/style.css" rel="stylesheet" id="" media="print, projection, screen" />
<script type="text/javascript" src="<?php echo $url_base; ?>js/jquery.tablesorter.js"></script>
<!--<script type="text/javascript" src="<?php echo $url_base; ?>admin/js/jquery.tablesorter.pager.js"></script>-->
<script type="text/javascript" src="<?php echo $url_base; ?>admin/js/chili-1.8b.js"></script>
<script type="text/javascript" src="<?php echo $url_base; ?>admin/js/docs.js"></script>
<!-- fim - extrato_consulta.php -->



<!-- extrato_consulta_os.php -->
<!--
    era do include "javascript_calendario_new.php";
<script type="text/javascript" src="js/jquery-1.3.2.js"></script>
<script type="text/javascript" src="js/date.js"></script>
<link rel="stylesheet" type="text/css" href="js/datePicker-2.css" title="default" media="screen" />
<script type="text/javascript" src="js/jquery.datePicker-2.js"></script>
<script type="text/javascript" src="../js/jquery.maskedinput2.js"></script>

<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link type="text/css" href="js/thickbox.css" rel="stylesheet" media="screen" />
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<script type="text/javascript" src="<?php echo $url_base; ?>js/shortcut.js"></script>
<script type="text/javascript" src="<?php echo $url_base; ?>admin/js/jquery.editable-1.3.3.js"></script>
<!-- fim - extrato_consulta_os.php -->


<!-- aprova_exclusao.php -->
<!--
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
-->

<!-- fim - aprova_exclusao.php -->







<!-- fim - Todas libs js, jquery e css usadas no Assist - HD 969678 -->
