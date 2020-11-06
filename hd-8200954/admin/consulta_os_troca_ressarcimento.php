<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include "autentica_admin.php";

$ajax = $_GET['ajax'];
$os   = $_GET['os'];
//HD 333333: Permitir trocar novamente produtos que tenham todos os itens da OS cancelados no pedido, sem
//necessariamente cancelar o pedido total (status_pedido == 14) e verifica se tem registro na tbl_os_troca
if (in_array($login_fabrica, array(81, 114)) && $ajax == 'verifica_troca') {

    $sql = "SELECT COUNT(*)
              FROM tbl_os_item
              JOIN tbl_os_produto  ON tbl_os_item.os_produto  = tbl_os_produto.os_produto
              JOIN tbl_os          ON tbl_os_produto.os       = tbl_os.os
              JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
              JOIN tbl_pedido      ON tbl_pedido_item.pedido  = tbl_pedido.pedido
             WHERE tbl_os.os           = $os
              AND tbl_os.fabrica       = $login_fabrica
              AND tbl_pedido_item.qtde = tbl_pedido_item.qtde_cancelada";

    #$res_item_cancelado = pg_query($con, $sql);

    $sql = "SELECT COUNT(tbl_os_item.os_item)
              FROM tbl_os
              JOIN tbl_os_produto ON tbl_os.os                 = tbl_os_produto.os
              JOIN tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
             WHERE tbl_os.os               = $os
               AND tbl_os_item.pedido_item IS NOT NULL
               AND tbl_os.fabrica          = $login_fabrica";

    #$res_total_item = pg_query($con, $sql);

    $sql = "SELECT COUNT(tbl_os_troca.os)
              FROM tbl_os_troca
             WHERE tbl_os_troca.os     = $os
               AND tbl_os_troca.fabric = $login_fabrica";

    $res_total_troca = pg_query($con, $sql);

    #$total_cancelados = pg_result($res_item_cancelado, 0, 0);
    #$total_itens_os   = pg_result($res_total_item, 0, 0);
    $total_troca      = pg_result($res_total_troca, 0, 0);

    if ($total_troca == 0) {
        echo 1;
    } else {
        echo 0;
    }

    exit;

}

$layout_menu = "callcenter";
$title       = "CONSULTA ORDEM DE SERVIÇO - TROCA EM LOTE";

include 'cabecalho.php';
include 'javascript_pesquisas.php';
include_once '../js/js_css.php';

$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];

$codigo_posto       = $_GET['codigo_posto'];
$posto_nome         = $_GET['posto_nome'];
$cnpj_revenda       = $_GET['cnpj_revenda'];
$nome_revenda       = $_GET['nome_revenda'];
$produto_referencia = $_GET['produto_referencia'];
$produto_descricao  = $_GET['produto_descricao'];
$sua_os             = $_GET['sua_os'];
$os_revenda         = $_GET['os_revenda'];

if (!empty($_GET)) {

    if (empty($data_inicial) || empty($data_final)) {

        $msg_erro = "Informe um intervalo para pesquisa!";

    } else {

        list($di, $mi, $yi) = explode("/", $data_inicial);
        list($df, $mf, $yf) = explode("/", $data_final);

        if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
            $msg_erro = 'Data Inválida';
        }

    }

}?>

<style type="text/css">

    .titulo_tabela{
        background-color:#596d9b;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    .titulo_coluna{
        background-color:#596d9b;
        font: bold 11px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    .msg_erro{
        background-color:#FF0000;
        font: bold 16px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    .sucesso{
        background-color:green;
        font: bold 16px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    .formulario{
        background-color:#D9E2EF;
        font:11px Arial;
    }

    .subtitulo{
        color: #7092BE
    }

    table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;
    }

    acronym {
        cursor: help;
    }

    label {
        cursor: pointer;
    }

	input[type=button],input[type=submit]{
		cursor:pointer;
	}

</style>

<script type="text/javascript" charset="utf-8" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" charset="utf-8">

    $(function() {
        Shadowbox.init();
        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});
        $("#data_inicial").mask("99/99/9999");
        $("#data_final").mask("99/99/9999");

        $("#ano").mask("9999");
        $("#cnpj_revenda").numeric();

        $("input[name=todos]").click(function(){
            if( $(this).is(":checked")){
                $("input[name^=check]").attr("checked",true);
            }else{
                $("input[name^=check]").attr("checked",false);
            }
        });
    });

    function mascara_cnpj(campo, event) {

        var cnpj  = campo.value.length;
        var tecla = event.keyCode ? event.keyCode : event.which ? event.which : event.charCode;

        if (tecla != 8 && tecla != 46) {

            if (cnpj == 2 || cnpj == 6) campo.value += '.';
            if (cnpj == 10) campo.value += '/';
            if (cnpj == 15) campo.value += '-';

        }

    }

    function mascara_cpf(campo, event) {

        var cpf   = campo.value.length;
        var tecla = event.keyCode ? event.keyCode : event.which ? event.which : event.charCode;

        if (tecla != 8 && tecla != 46) {

            if (cpf == 3 || cpf == 7) campo.value += '.';
            if (cpf == 11) campo.value += '-';

        }

    }

    function formata_cpf_cnpj(campo, tipo) {

        var valor = campo.value;

        valor = valor.replace('.','');
        valor = valor.replace('.','');
        valor = valor.replace('-','');

        if (tipo == 2) {
            valor = valor.replace('/','');
        }

        if (valor.length == 11 && tipo == 1) {

            campo.value = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/,"$1.$2.$3-$4");//CPF

        } else if (valor.length == 14 && tipo == 2) {

            campo.value = valor.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,'$1.$2.$3/$4-$5');//CNPJ

        }

    }

    function fnc_revenda_pesquisa(campo, campo2, tipo) {

        if (tipo == 'nome') {
            var xcampo = campo;
        }

        if (tipo == 'cnpj') {
            var xcampo = campo2;
        }

        if (xcampo.value != '') {
            var url = "";
            url = "revenda_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
            janela.nome = campo;
            janela.cnpj = campo2;
            janela.focus();

        } else {
            alert("Informe toda ou parte da informação para realizar a pesquisa");
        }

    }

    function trocar() {

        if (confirm('Confirma a troca do(s) produto(s) desta(s) OS(s) nas condições estabelecidas?')) {
            var erro = "";
            var os = "";
            var sua_os = "";
            var produto = "";
            var familia = "";
            var marca = "";
            var array_os = new Array();
            var json = {};

            var troca_garantia_produto  = $("#produto").val();
            var causa_troca             = $("select[name=causa_troca]").val();
            var ri                      = $("input[name=ri]").val();
            var setor                   = $("#setor").val();
            var envio_consumidor        = $("#envio_consumidor").val();
            var modalidade_transporte   = $("#modalidade_transporte").val();
            var fabrica_distribuidor    = $("#fabrica_distribuidor").val();
            var observacao_pedido       = $("#observacao_pedido").val();
            var gerar_pedido            = $("input[name=gerar_pedido]:checked").val();

            if(causa_troca == "" ){
                $("select[name=causa_troca]").attr("style","background-color:#FFCCCC;width: 300px;");
                erro = 1;
            }else{
                $("select[name=causa_troca]").attr("style","background-color:#F0F0F0;width: 300px;");
            }

            if(ri == "" ){
                $("input[name=ri]").attr("style","background-color:#FFCCCC");
            }else{
                $("input[name=ri]").attr("style","background-color:#F0F0F0");
            }

            if(setor == "" ){
                $("#setor").attr("style","background-color:#FFCCCC;width: 300px;");
                erro = 1;
            }else{
                $("#setor").attr("style","background-color:#F0F0F0;width: 300px;");
            }

            if(envio_consumidor == "" ){
                $("#envio_consumidor").attr("style","background-color:#FFCCCC;width: 285;");
                erro = 1;
            }else{
                $("#envio_consumidor").attr("style","background-color:#F0F0F0;width: 285;");
            }

            if(modalidade_transporte == "" ){
                $("#modalidade_transporte").attr("style","background-color:#FFCCCC;width: 300px;");
                erro = 1;
            }else{
                $("#modalidade_transporte").attr("style","background-color:#F0F0F0;width: 300px;");
            }

            if(fabrica_distribuidor == "" ){
                $("#fabrica_distribuidor").attr("style","background-color:#FFCCCC;width: 300px;");
                erro = 1;
            }else{
                $("#fabrica_distribuidor").attr("style","background-color:#F0F0F0;width: 300px;");
            }

            if(observacao_pedido == "" ){
                $("#observacao_pedido").attr("style","background-color:#FFCCCC;");
                erro = 1;
            }else{
                $("#observacao_pedido").attr("style","background-color:#F0F0F0;");
            }

            if(erro == 1){
                alert("Preencha todos os campos");
                return false;
            }

            $(document).find("input[name^=check]:checked").each(function(){
                var linha = $(this);
                os = $(this).val();
                sua_os = $(this).parents("tr").find("input[name^=sua_os]").val();
                produto = $(this).parents("tr").find("input[name^=produto]").val();
                marca   = $(this).parents("tr").find("input[name^=marca]").val();

                if(troca_garantia_produto == -3){
                    troca_garantia_produto = produto;
                }

                $.ajax({
                    url: '<?=$PHP_SELF?>?ajax=verifica_troca&os='+os,
                    async:false,
                    complete: function(data) {

                        if (data.responseText == "1") {

                           array_os.push(os);

                        } else {

                            alert('Produto da OS '+os+' já trocado!');
                            return false;

                        }
                    }

                });

            });

            json = array_os;
            json = JSON.stringify(json);
            $.ajax({
                url : "troca_em_lote.php",
                async:false,
                type: "POST",
                data: {osacao:"trocar",oss:json,troca_garantia_produto:troca_garantia_produto,causa_troca:causa_troca,ri:ri,setor:setor,envio_consumidor:envio_consumidor,modalidade_transporte:modalidade_transporte,fabrica_distribuidor:fabrica_distribuidor,observacao_pedido:observacao_pedido,gerar_pedido:gerar_pedido},
                complete : function(data){
                    if (data.responseText == "1") {
                        $(document).find("input[name^=check]:checked").each(function(){
                            $(this).parents("tr").remove();
                        });
                        $("#msg").show();
                        limpaCampos();
                    }else{
                        alert(data.responseText);
                    }
                }
            });

        }

    }

    function limpaCampos(){
        $("select[name=causa_troca]").prop("selectedIndex",0);
        $("input[name=ri]").val("");
        $("#setor").prop("selectedIndex",0);
        $("#envio_consumidor").prop("selectedIndex",0);
        $("#modalidade_transporte").prop("selectedIndex",0);
        $("#fabrica_distribuidor").prop("selectedIndex",0);
        $("#observacao_pedido").val("");
        $("radio[name=gerar_pedido][value=gerar_pedido]").attr("checked",false);
        $("radio[name=gerar_pedido][value=gera_pedido_bestway]").attr("checked",true);
    }

</script>

<form name="frm_rel" method="get" action="<?=$_SERVER['PHP_SELF'];?>">
<table border="0" cellpadding="0" cellspacing="0" align="center" width="700" class="formulario"><?php
    if (strlen($msg_erro) > 0) {?>
        <tr>
            <td class="msg_erro"><?=$msg_erro?></td>
        </tr><?php
    }?>
    <tr class="titulo_tabela">
        <td>Parâmetros de Pesquisa</td>
    </tr>
    <tr>
        <td valign="top" align="left">
            <table align='center' width='700' border='0' cellpadding="2" cellspacing="0">
                <tr>
                    <td colspan="100%">&nbsp;</td>
                </tr>
                <tr>
                    <td width="40">&nbsp;</td>
                    <td>Data Inicial</td>
                    <td>Data Final</td>
                    <td>Posto</td>
                    <td>Nome do Posto</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>
                        <input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<?=$data_inicial?>" class="frm">
                    </td>
                    <td>
                        <input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<?=$data_final?>" class="frm">
                    </td>
                    <td>
                        <input type="text" name="codigo_posto" id="codigo_posto" size="8" value="<?=$codigo_posto?>" class="frm" />
                        <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_posto(document.getElementById('codigo_posto'), document.getElementById('posto_nome'), 'codigo')" />
                    </td>
                    <td>
                        <input type="text" name="posto_nome" id="posto_nome" size="32" value="<?=$posto_nome?>" class="frm" />
                        <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_posto(document.getElementById('codigo_posto'), document.getElementById('posto_nome'), 'nome')" />
                    </td>
                </tr>
                <tr align='left'>
                    <td>&nbsp;</td>
                    <td colspan="4">Número da OS</td>
                    <!-- <td colspan="2">OS de Revenda</td> -->
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td colspan="4">
                        <input type="text" name="sua_os" id="sua_os" class="frm" size="35" maxlength="20" value="<?php echo $sua_os?>" />
                    </td>
                    <!-- <td colspan="2">
                        <input type="text" name="os_revenda" id="os_revenda" size="50" maxlength="60" value="<?php echo $os_revenda ?>" class='frm' />
                    </td> -->
                </tr>
                <tr align='left'>
                    <td>&nbsp;</td>
                    <td colspan="2">CNPJ Revenda</td>
                    <td colspan="2">Razão Social</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td colspan="2">
                        <input type="text" name="cnpj_revenda" id="cnpj_revenda" onkeypress="mascara_cnpj(this, event);" onfocus="formata_cpf_cnpj(this,2);" class="frm" size="35" maxlength="18" value="<?php echo $cnpj_revenda?>" />
                        <img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="fnc_revenda_pesquisa(document.frm_rel.nome_revenda, document.frm_rel.cnpj_revenda, 'cnpj')" />
                    </td>
                    <td colspan="2">
                        <input type="text" name="nome_revenda" id="nome_revenda" size="50" maxlength="60" value="<?php echo $nome_revenda ?>" class='frm' />
                        <img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="fnc_revenda_pesquisa(document.frm_rel.nome_revenda, document.frm_rel.cnpj_revenda, 'nome')" />
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td colspan="2">Ref. Produto</td>
                    <td colspan="2">Descrição Produto</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td colspan="2">
                        <input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="35" maxlength="20" value="<?=$produto_referencia?>" />
                        <img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="fnc_pesquisa_produto(document.frm_rel.produto_referencia, document.frm_rel.produto_descricao, 'referencia')" />
                    </td>
                    <td colspan="2">
                        <input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="50" value="<?php echo $produto_descricao ?>" />
                        <img src='imagens/lupa.png' style="cursor:pointer" border="0" align="absmiddle" onclick="fnc_pesquisa_produto(document.frm_rel.produto_referencia, document.frm_rel.produto_descricao, 'descricao')" />
                    </td>
                </tr>
                <tr><td colspan="100%">&nbsp;</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <input type="submit" value="Pesquisar">
        </td>
    </tr>
    <tr>
        <td>&nbsp;</td>
    </tr>
</table>

<input type='hidden' name='btnacao' value='' />

</form>
<?php

if (!empty($_GET) && strlen($msg_erro) == 0) {?>
    <br />
    <div id="msg" class="sucesso" style="margin:auto;display:none; width:700px;">Troca efetuada com sucesso</div>
    <br />
    <form name="frm_cad" id="frm_cad" method="post" method="post" target="_blank">
        <input type="hidden" name="pagina" id="pagina" value="" />
        <input type="hidden" name="os" id="os" />
        <input type="hidden" name="btn_troca" id="btn_troca" value="trocar" />
        <input type="hidden" name="troca_garantia_produto" id="troca_garantia_produto" />
        <input type="hidden" name="marca_troca" id="marca_troca" />
        <input type="hidden" name="familia_troca" id="familia_troca" />
        <table border="0" cellpadding="0" cellspacing="0" align="center" width="700" class="formulario">
            <tr class="titulo_tabela">
                <td>Parâmetros de Cadastro</td>
            </tr>
            <tr>
                <td valign="top" align="left">
                    <table align='center' width='700' border='0' cellpadding="2" cellspacing="0">
                        <tr>
                            <td colspan="100%">&nbsp;</td>
                        </tr>
                        <tr>
                            <td width="40">&nbsp;</td>
                            <td>Trocar pelo produto / Ressarcimento Financeiro</td>
                            <td>Causa da Troca / Ressarcimento Financeiro</td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>
                                <select name="produto" id="produto" class="frm">
                                    <option value="-3">MESMO PRODUTO</option>
                                    <option value="-1">RESSARCIMENTO FINANCEIRO</option>
                                    <option value="-2">AUTORIZAÇÃO DE DEVOLUÇÃO DE VENDA</option>
                                </select>
                            </td>
                            <td><?php
                                $sql = "SELECT tbl_causa_troca.causa_troca,
                                               tbl_causa_troca.codigo     ,
                                               tbl_causa_troca.descricao
                                          FROM tbl_causa_troca
                                         WHERE tbl_causa_troca.fabrica = $login_fabrica
                                           AND tbl_causa_troca.ativo   IS TRUE
                                         ORDER BY tbl_causa_troca.codigo,
                                                  tbl_causa_troca.descricao";

                                $resTroca = pg_query($con,$sql);
                                $totTroca = pg_num_rows($resTroca);

                                echo "<select name='causa_troca' class='frm' style='width: 300px;'>";
                                    echo "<option value=''></option>";
                                    for ($i = 0; $i < $totTroca; $i++) {
                                        $aux_causa_troca = pg_fetch_result($resTroca, $i, 'causa_troca');
                                        echo "<option value='".$aux_causa_troca."'".($causa_troca == $aux_causa_troca ? ' selected="selected"' : '').">" . pg_fetch_result($resTroca, $i, 'codigo') . " - " . pg_fetch_result($resTroca, $i, 'descricao')."</option>";
                                    }
                                echo "</select>";?>
                            </td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>Número de Registro</td>
                            <td>Setor Responsável</td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>
                                <input type='text' name='ri' value='' maxlength='10' class='frm' />
                            </td>
                            <td>
                                <select name="setor" id="setor" class="frm" style='width: 300px;'>
                                    <option></option>
                                    <option value="Revenda">Revenda</option>
                                    <option value="Carteira">Carteira</option>
                                    <option value="SAC">SAC</option>
                                    <option value="Procon">Procon</option>
                                    <option value="SAP">SAP</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td >Destino</td>
                            <td>Modalidade de Transporte</td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>
                                <select name="envio_consumidor" id="envio_consumidor" class="frm" style='width: 285px;'>
                                    <option></option>
                                    <option value="t">direto ao consumidor</option>
                                    <option value="f">para o posto</option>
                                </select>
                            </td>
                            <td>
                                <select name="modalidade_transporte" id="modalidade_transporte" class="frm" style='width: 300px;'>
                                    <option></option>
                                    <option value="urgente">RI Urgente</option>
                                    <option value="normal">RI Normal</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td colspan="2">Efetuar Troca Por</td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <!-- <td>
                                <input type="radio" name='gerar_pedido' value="gera_pedido" class="frm">Gerar Pedido
                                <input type="radio" name='gerar_pedido' value="gera_pedido_bestway" class="frm" checked>Gerar Pedido Bestway
                            </td> -->
                            <td colspan="2">
                                <select name="fabrica_distribuidor" id="fabrica_distribuidor" class="frm" style='width: 300px;'>
                                    <option></option>
                                    <option value="fabrica">Fábrica</option>
                                    <option value="distribuidor">Distribuidor</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td colspan="2">Observação para nota fiscal</td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td colspan="2">
                                <textarea name='observacao_pedido' id="observacao_pedido" cols='87' rows='3' class='frm'></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="100%">&nbsp;</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <br />
    </form><?php
}

if (!empty($_GET) && strlen($msg_erro) == 0) {

    echo '<br />';

    //INVERTE POSIÇÂO DA DATA de DD/MM/YYYY para:
    $data_ini = "$yi-$mi-$di";//YYYY/MM/DD
    $data_fim = "$yf-$mf-$df";//YYYY/MM/DD

    if (!empty($cnpj_revenda)) {

        $cnpj_revenda  = preg_replace('/\D/','',$cnpj_revenda);//deixa apenas os numeros
        $where_revenda = " AND tbl_os.revenda IN (SELECT revenda FROM tbl_revenda WHERE cnpj LIKE '$cnpj_revenda%') ";

    }

    if (!empty($codigo_posto)) {
        $where_posto = " AND tbl_os.posto IN (SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' and fabrica = $login_fabrica) ";
    }

    if (!empty($produto_referencia)) {
        $where_produto = " AND tbl_os.produto IN (SELECT produto FROM tbl_produto WHERE referencia ILIKE '$produto_referencia%') ";
    }

    if (!empty($sua_os)) {
        $where_sua_os = " AND tbl_os.sua_os ILIKE '%$sua_os%' ";
    }

    if (!empty($os_revenda)) {
        $where_os_revenda = " AND tbl_os.os IN (SELECT os_lote FROM tbl_os_revenda_item WHERE os_revenda = $os_revenda)";
    }

    $sql = "SELECT tbl_os.sua_os                                                                 ,
                   tbl_os.os                                                                     ,
                   tbl_os.consumidor_revenda                                AS tipo_os           ,
                   tbl_os.produto                                                                ,
                   tbl_produto.marca                                                             ,
                   tbl_produto.familia                                                           ,
                   tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto_descricao ,
                   tbl_posto.nome                                           AS posto             ,
                   tbl_os.revenda_nome AS consumidor_revenda
              FROM tbl_os
              LEFT JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os
              JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os AND tbl_os_extra.extrato isnull
              JOIN tbl_produto           ON tbl_os.produto = tbl_produto.produto
              JOIN tbl_posto             ON tbl_os.posto   = tbl_posto.posto
         LEFT JOIN tbl_revenda           ON tbl_os.revenda = tbl_revenda.revenda
             WHERE tbl_os.fabrica        = $login_fabrica
               AND tbl_os.data_digitacao BETWEEN '$data_ini 00:00:00' AND '$data_fim 23:59:59'
               AND tbl_os.consumidor_revenda = 'R'
               AND tbl_os_troca.os_troca isnull
               $where_revenda
               $where_consumidor
               $where_posto
               $where_produto
               $where_sua_os
               $where_os_revenda
             ORDER BY tbl_posto.nome, tbl_produto.descricao ";
    #echo nl2br($sql);exit;
    $sqlCount  = "SELECT count(*) FROM (";
    $sqlCount .= $sql;
    $sqlCount .= ") AS count";

    require "_class_paginacao.php";

    // definicoes de variaveis
    $max_links = 11;                    // máximo de links à serem exibidos
    $max_res   = 100;                   // máximo de resultados à serem exibidos por tela ou pagina
    $mult_pag  = new Mult_Pag();        // cria um novo objeto navbar
    $mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

    $res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
    $tot = pg_numrows($res);

    if ($tot > 0) {

        echo "<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center'>";

            echo "<tr style='background-color:#596d9b;font: bold 14px Arial;color:#FFFFFF;text-align:center;'>";
                echo "<th style='background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;'><input type='checkbox' name='todos' class='frm'></th>";
                echo "<th style='background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;'>OS</th>";
                echo "<th style='background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;'>POSTO</th>";
                echo "<th style='background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;'>PRODUTO</th>";
                echo "<th style='background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;'>TIPO</th>";
                echo "<th style='background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;'>REVENDA</th>";
                //echo "<th style='background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;'>TROCAR</th>";
            echo "</tr>";

            for ($i = 0; $i < $tot; $i++) {

                $sua_os             = trim(pg_result($res, $i, 'sua_os'));
                $os                 = trim(pg_result($res, $i, 'os'));
                $tipo_os            = trim(pg_result($res, $i, 'tipo_os'));
                $consumidor_revenda = trim(pg_result($res, $i, 'consumidor_revenda'));
                $produto            = trim(pg_result($res, $i, 'produto'));
                $produto_descricao  = trim(pg_result($res, $i, 'produto_descricao'));
                $posto              = trim(pg_result($res, $i, 'posto'));
                $marca              = trim(pg_result($res, $i, 'marca'));
                $familia            = trim(pg_result($res, $i, 'familia'));

                $cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

                echo "<tr bgcolor='$cor' class='Label'>";
                    echo "<td align='center'>
                            <input type='hidden' name='produto[]' value='$produto'>
                            <input type='hidden' name='marca[]' value='$marca'>
                            <input type='hidden' name='familia[]' value='$familia'>
                            <input type='hidden' name='sua_os[]' value='$familia'>
                            <input type='checkbox' name='check[]' value='$os' class='frm'>
                          </td>";
                    echo "<td align='center' nowrap='nowrap'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
                    echo "<td align='left' nowrap='nowrap'><acronym title='$posto'>".substr($posto,0,20)."</acronym></td>";
                    echo "<td align='left' nowrap='nowrap'><acronym title='$produto_descricao'>".substr($produto_descricao,0,20)."</acronym></td>";
                    echo "<td align='center' nowrap='nowrap'><acronym title='".($tipo_os == 'C' ? 'Consumidor' : 'Revenda')."'>$tipo_os</acronym></td>";
                    echo "<td align='left' nowrap='nowrap'><acronym title='$consumidor_revenda'>".substr($consumidor_revenda,0,20)."</acronym></td>";
                    //echo "<td align='center' nowrap='nowrap'><input type='button' value='Trocar' onclick='trocar($os, $produto, $marca, $familia)' /></td>";
                echo "</tr>";

                flush();

            }
        echo "<tr><td colspan='100%' align='center'><input type='button' value='Gerar Troca em Lote' onclick='trocar()' /></td></tr>";
        echo "</table>";

        echo "<br />";
        echo "<div>";

        if ($pagina < $max_links) $paginacao = pagina + 1;
        else                      $paginacao = pagina;

        // pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
        @$todos_links = $mult_pag->Construir_Links("strings", "sim");

        // função que limita a quantidade de links no rodape
        $links_limitados = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

        for ($n = 0; $n < count($links_limitados); $n++) {
            echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
        }

        echo "</div>";

        $resultado_inicial = ($pagina * $max_res) + 1;
        $resultado_final   = $max_res + ($pagina * $max_res);
        $registros         = $mult_pag->Retorna_Resultado();

        $valor_pagina   = $pagina + 1;
        $numero_paginas = intval(($registros / $max_res) + 1);

        if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

        if ($registros > 0) {
            echo "<br />";
            echo "<div>";
                echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
                echo "<font color='#cccccc' size='1'>(Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)</font>";
            echo "</div>";
        }

    } else {

        echo '
		<table border="0" cellpadding="0" cellspacing="0" align="center" width="700" class="formulario">
            <tr class="msg_erro">
                <td>Não foram encontrados resultados para esta pesquisa!</td>
			</tr>
		</table>';

    }

}

echo "<br />";

include "rodape.php";

?>
