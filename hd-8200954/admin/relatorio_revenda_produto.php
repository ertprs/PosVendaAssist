<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico') {
    include "autentica_admin.php";
}

//include "gera_relatorio_pararelo_include.php";

$layout_menu = "gerencia";
$title = "RELATÓRIO - REVENDA POR PRODUTO (CONTROLE DE FECHAMENTO DE OS)";

include 'cabecalho.php';
include 'javascript_pesquisas.php';
include 'javascript_calendario.php';

$mes = (int) $_GET['mes'];
$ano = (int) $_GET['ano'];

$codigo_posto       = $_GET['codigo_posto'];
$posto_nome         = $_GET['posto_nome'];
$familia            = $_GET['familia'];
$nota_fiscal        = $_GET['nota_fiscal'];
$cnpj_revenda       = $_GET['cnpj_revenda'];
$nome_revenda       = $_GET['nome_revenda'];
$cpf_consumidor     = $_GET['cpf_consumidor'];
$nome_consumidor    = $_GET['nome_consumidor'];
$produto_referencia = $_GET['produto_referencia'];
$produto_descricao  = $_GET['produto_descricao'];

$mes_extenso = array('01' => "janeiro", '02' => "fevereiro", '03' => "março", '04' => "abril", '05' => "maio", '06' => "junho", '07' => "julho", '08' => "agosto", '09' => "setembro", '10' => "outubro", '11' => "novembro", '12' => "dezembro");

if (!empty($_GET)) {

    if (empty($data) || empty($ano)) {

        $msg_erro = "Os campos Mês e Ano são obrigatórios!";

    } else {

        $data_ini = date("$ano-$mes-01");
        $data_fim = date('Y-m-t', strtotime($data_ini));

        list($yi, $mi, $di) = explode("-", $data_ini);
        list($yf, $mf, $df) = explode("-", $data_fim);

        if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf) || !is_int($ano) || !is_int($mes)) {
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

</style>

<script type="text/javascript" charset="utf-8" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" charset="utf-8">

    $(function() {
        $("#ano").maskedinput("9999");
        $("#cpf_consumidor").numeric();
        $("#cnpj_revenda").numeric();
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

    function fnc_pesquisa_consumidor(campo, tipo) {

        var url = '';

        if (tipo == 'nome') {
            url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
        }

        if (tipo == 'cpf') {
            url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
        }

        if (campo.value != '') {

            if (campo.value.length >= 3) {

                janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
                janela.cliente     = '';
                janela.nome        = document.frm_rel.nome_consumidor;
                janela.cpf         = document.frm_rel.cpf_consumidor;
                janela.rg          = '';
                janela.cidade      = '';
                janela.estado      = '';
                janela.fone        = '';
                janela.endereco    = '';
                janela.numero      = '';
                janela.complemento = '';
                janela.bairro      = '';
                janela.cep         = '';
                janela.focus();

            } else {

                alert("Digite pelo menos 3 caracteres para efetuar a pesquisa!");

            }

        } else {

            alert("Digite pelo menos 3 caracteres para efetuar a pesquisa!");

        }

    }

</script><?php

if (!empty($_GET) && strlen($msg_erro) == 0) {
    //include "gera_relatorio_pararelo.php";
}

if (strlen($codigo_posto) == 0) {
    if ($gera_automatico != 'automatico' and strlen($msg_erro)== 0) {
        //include "gera_relatorio_pararelo_verifica.php";
    }
}?>

<form name="frm_rel" method="get" action="<?=$PHP_SELF;?>">
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
                    <td colspan="5">&nbsp;</td>
                </tr>
                <tr>
                    <td width="40">&nbsp;</td>
                    <td>* Mês</td>
                    <td>* Ano</td>
                    <td>Posto</td>
                    <td>Nome do Posto</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>
                        <select name="mes" class="frm" id="mes"><?php
                            echo '<option value=""></option>';
                            foreach ($mes_extenso as $k => $v) {
                                echo '<option value="'.$k.'"'.($mes == $k ? ' selected="selected"' : '').'>'.ucwords($v)."</option>\n";
                            }?>
                        </select>
                    </td>
                    <td><input size="12" maxlength="4" type="text" name="ano" class="frm" id="ano" value="<?=$ano;?>" /></td>
                    <td>
                        <input type="text" name="codigo_posto" id="codigo_posto" size="8" value="<?=$codigo_posto?>" class="frm" />
                        <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_posto(document.getElementById('codigo_posto'), document.getElementById('posto_nome'), 'codigo')" />
                    </td>
                    <td>
                        <input type="text" name="posto_nome" id="posto_nome" size="30" value="<?=$posto_nome?>" class="frm" />
                        <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_posto(document.getElementById('codigo_posto'), document.getElementById('posto_nome'), 'nome')" />
                    </td>
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
                    <td>Nota Fiscal</td>
                    <td>Ref. Produto</td>
                    <td colspan="2">Descrição Produto</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>
                        <input class="frm" type="text" name="nota_fiscal" id="nota_fiscal" size="12" maxlength="20" value="<?=$nota_fiscal?>" />
                    </td>
                    <td>
                        <input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="17" maxlength="20" value="<?=$produto_referencia?>" />
                        <img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="fnc_pesquisa_produto(document.frm_rel.produto_referencia, document.frm_rel.produto_descricao, 'referencia')" />
                    </td>
                    <td colspan="2">
                        <input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="50" value="<?php echo $produto_descricao ?>" />
                        <img src='imagens/lupa.png' style="cursor:pointer" border="0" align="absmiddle" onclick="fnc_pesquisa_produto(document.frm_rel.produto_referencia, document.frm_rel.produto_descricao, 'descricao')" />
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td colspan="2">Família</td>
                    <td colspan="2">Nome Consumidor</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td colspan="2">
                        <select name="familia" class="frm" id="familia">
                            <option value=""></option><?php

                            $sql = "SELECT familia,
                                           descricao
                                      FROM tbl_familia
                                     WHERE fabrica = $login_fabrica
                                       AND ativo   = true
                                     ORDER BY descricao";

                            $res   = pg_query($con, $sql);
                            $total = pg_num_rows($res);

                            for ($x = 0; $x < $total; $x++) {

                                $id_familia = pg_fetch_result($res, $x, 'familia');
                                $descricao  = pg_fetch_result($res, $x, 'descricao');

                                echo '<option value="'.$id_familia.'"'.($id_familia == $familia ? ' selected="selected"' : '').'>'.$descricao."</option>\n";

                            }?>
                        </select>
                    </td>
                    <td colspan="2">
                        <input class="frm" type="text" name="nome_consumidor" id="nome_consumidor" size="50" maxlength="60" value="<?php echo $nome_consumidor ?>" onkeyup="somenteMaiusculaSemAcento(this); this.value = this.value.toUpperCase();" />
                         <img src='imagens/help.png' title='Clique aqui para ajuda na busca deste campo' onclick='mostrarMensagemBuscaNomes()'>
                    </td>
                </tr>
                <tr><td colspan="5">&nbsp;</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <input type="submit" value="Pesquisar" />
        </td>
    </tr>
    <tr>
        <td>&nbsp;</td>
    </tr>
</table>

<input type='hidden' name='btnacao' value='' />

</form><?php

if (!empty($_GET) && strlen($msg_erro) == 0) {

    echo '<br />';

    //INVERTE POSIÇÂO DA DATA de DD/MM/YYYY para:
    $data_ini = implode('-',array_reverse(explode('/',$data_ini)));//YYYY/MM/DD
    $data_fim = implode('-',array_reverse(explode('/',$data_fim)));//YYYY/MM/DD

    if (!empty($cnpj_revenda)) {

        $cnpj_revenda = str_replace('.', '', $cnpj_revenda);
        $cnpj_revenda = str_replace('/', '', $cnpj_revenda);
        $cnpj_revenda = str_replace('-', '', $cnpj_revenda);

        $where_revenda = " AND bi_os.revenda IN (SELECT revenda FROM tbl_revenda WHERE cnpj LIKE '$cnpj_revenda%') ";

    }

    if (!empty($nome_consumidor)) {
        $where_consumidor = " AND bi_os.consumidor_nome ILIKE '$nome_consumidor%' ";
    }

    if (!empty($codigo_posto)) {
        $where_posto = " AND bi_os.posto IN (SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' and fabrica = $login_fabrica) ";
    }

    if (!empty($produto_referencia)) {
        $where_produto = " AND bi_os.produto IN (SELECT produto FROM tbl_produto WHERE referencia ILIKE '$produto_referencia%') ";
    }

    if (!empty($familia)) {
        $where_familia = " AND bi_os.familia = $familia ";
    }

    if (!empty($nota_fiscal)) {
        $where_nf = " AND bi_os.nota_fiscal = '$nota_fiscal' ";
    }

    ob_start();//INICIA BUFFER

    $sql = "SELECT COALESCE(tbl_revenda.nome, '')                           AS revenda_nome                 ,
                   bi_os.produto                                                                            ,
                   tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto_descricao            ,
                   COALESCE(bi_os.nota_fiscal, '')                          AS nota_fiscal                  ,
                   COALESCE(tbl_revenda.cnpj, '')                           AS cnpj                         ,
                   bi_os.defeito_constatado                                                                 ,
                   tbl_defeito_constatado.descricao                         AS defeito_constatado_descricao ,
                   COUNT(bi_os.defeito_constatado)                          AS count_defeito_constatado
              INTO TEMP TABLE tmp_produto_nota_defeito
              FROM bi_os
              JOIN tbl_produto            ON bi_os.produto            = tbl_produto.produto
              JOIN tbl_defeito_constatado ON bi_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
         LEFT JOIN tbl_revenda            ON bi_os.revenda            = tbl_revenda.revenda
             WHERE bi_os.fabrica          =  $login_fabrica
               AND bi_os.data_digitacao   >= '$data_ini 00:00:00'
               AND bi_os.data_digitacao   <  '$data_fim 23:59:59'
               $where_revenda
               $where_consumidor
               $where_posto
               $where_produto
               $where_familia
               $where_nf
             GROUP BY tbl_revenda.nome         ,
                      bi_os.produto            ,
                      tbl_produto.referencia   ,
                      tbl_produto.descricao    ,
                      bi_os.nota_fiscal        ,
                      tbl_revenda.cnpj         ,
                      bi_os.defeito_constatado ,
                      tbl_defeito_constatado.descricao
             ORDER BY tbl_revenda.nome         ,
                      tbl_produto.referencia   ,
                      tbl_produto.descricao    ,
                      bi_os.nota_fiscal        ,
                      tbl_defeito_constatado.descricao;

            CREATE INDEX tmp_produto_nota_defeito_defeito_constatado ON tmp_produto_nota_defeito(defeito_constatado);
            CREATE INDEX tmp_produto_nota_defeito_nota_fiscal        ON tmp_produto_nota_defeito(nota_fiscal);
            CREATE INDEX tmp_produto_nota_defeito_produto            ON tmp_produto_nota_defeito(produto);

            SELECT DISTINCT defeito_constatado,
                            defeito_constatado_descricao
              INTO TEMP TABLE tmp_defeitos
              FROM tmp_produto_nota_defeito
             ORDER BY defeito_constatado_descricao;

              SELECT DISTINCT cnpj, revenda_nome
                FROM tmp_produto_nota_defeito
               ORDER BY revenda_nome;";

    $res_revenda = pg_exec($con, $sql);
    $tot_revenda = pg_numrows($res_revenda);

    if ($tot_revenda > 0) {

        echo "<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center'>";

        for ($y = 0; $y < $tot_revenda; $y++) {

            echo "<tr style='background-color:#596d9b;font: bold 14px Arial;color:#FFFFFF;text-align:center;'>";
                echo "<th style='background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;'>CNPJ</th>";
                echo "<th style='background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;'>REVENDA</th>";
                echo "<th style='background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;'>NOTA FISCAL</th>";
                echo "<th style='background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;'>PRODUTO</th>";
                echo "<th style='background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;'>RECEBIDO</th>";

                $sql_defeito = "SELECT * FROM tmp_defeitos;";

                $res_defeito = pg_exec($con, $sql_defeito);
                $tot_res_def = pg_numrows($res_defeito);

                for ($x = 0; $x < $tot_res_def; $x++) {

                    $defeito[$x]['defeito'] = trim(pg_result($res_defeito, $x, 'defeito_constatado_descricao'));
                    echo '<th style="background-color:#596d9b; font: bold 11px Arial; color:#FFFFFF; text-align:center;">'.$defeito[$x]['defeito'].'</th>';

                }

            echo "</tr>";

            $cnpj = trim(pg_result($res_revenda, $y, 'cnpj'));

            $sql = "SELECT produto           ,
                           revenda_nome      ,
                           cnpj              ,
                           produto_descricao ,
                           nota_fiscal       ,
                           SUM(count_defeito_constatado) AS total_defeito_constatado
                      FROM tmp_produto_nota_defeito
                     WHERE cnpj = '$cnpj'
                     GROUP BY revenda_nome      ,
                              produto           ,
                              cnpj              ,
                              produto_descricao ,
                              nota_fiscal
                     ORDER BY revenda_nome";

            $res = pg_exec($con, $sql);
            $tot = pg_numrows($res);

            $subtotal      = 0;
            $total_defeito = null;

            for ($i = 0; $i < $tot; $i++) {

                $produto                  = trim(pg_result($res, $i, 'produto'));
                $produto_descricao        = trim(pg_result($res, $i, 'produto_descricao'));
                $revenda_nome             = trim(pg_result($res, $i, 'revenda_nome'));
                $nota_fiscal              = trim(pg_result($res, $i, 'nota_fiscal'));
                $total_defeito_constatado = abs(pg_result($res, $i, 'total_defeito_constatado'));

                $subtotal += $total_defeito_constatado;

                $cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

                echo "<tr bgcolor='$cor' class='Label'>";
                    echo "<td align='left' nowrap='nowrap'>$cnpj</td>";
                    echo "<td align='left' nowrap='nowrap'>$revenda_nome</td>";
                    echo "<td align='left' nowrap='nowrap'>$nota_fiscal</td>";
                    echo "<td align='left' nowrap='nowrap'>$produto_descricao</td>";
                    echo "<td align='center'>$total_defeito_constatado</td>";

                    $sql_defeito = "SELECT CASE WHEN dados.defeito_constatado IS NULL THEN 0
                                           ELSE dados.count_defeito_constatado END AS count_defeito_constatado
                                      FROM tmp_defeitos
                                      LEFT JOIN (SELECT tmp_produto_nota_defeito.defeito_constatado,
                                                        tmp_produto_nota_defeito.count_defeito_constatado
                                                   FROM tmp_produto_nota_defeito
                                                  WHERE tmp_produto_nota_defeito.produto      = $produto
                                                    AND tmp_produto_nota_defeito.nota_fiscal  = '$nota_fiscal'
                                                    AND tmp_produto_nota_defeito.cnpj         = '$cnpj'
                                                ) AS dados ON tmp_defeitos.defeito_constatado = dados.defeito_constatado;";

                    $res_defeito = pg_exec($con, $sql_defeito);
                    $tot_res_def = pg_numrows($res_defeito);

                    for ($x = 0; $x < $tot_res_def; $x++) {

                        $total_defeito[$x]['total']     = trim(pg_result($res_defeito, $x, 'count_defeito_constatado'));
                        $total_defeito[$x]['subtotal'] += $total_defeito[$x]['total'];

                        echo '<td align="center">'.$total_defeito[$x]['total'].'</td>';

                    }

                echo "</tr>";

                flush();

            }

            echo "<tr style='background-color:#596d9b;font: bold 14px Arial;color:#FFFFFF;text-align:center;'>";
                echo "<td colspan='4'>Total</td>";
                echo "<td>$subtotal</td>";
                for ($x = 0; $x < $tot_res_def; $x++) {
                    echo '<td><acronym title="'.$defeito[$x]['defeito'].'">'.$total_defeito[$x]['subtotal'].'</acronym></td>';
                }
            echo "</tr>";
            echo "<tr style='background-color:#596d9b;font: bold 14px Arial;color:#FFFFFF;text-align:center;'>";
                echo "<td colspan='4'>PORCENTAGEM</td>";
                echo "<td>100%</td>";
                for ($x = 0; $x < $tot_res_def; $x++) {
                    $result_perc = $subtotal > 0 ? ($total_defeito[$x]['subtotal'] / $subtotal * 100) : 0;
                    echo '<td nowrap="nowrap"><acronym title="'.$defeito[$x]['defeito'].'">'.number_format($result_perc,2,',','.').' %</acronym></td>';
                }
            echo "</tr>";
            echo "<tr>";
                echo "<td colspan='100%'>&nbsp;</td>";
            echo "</tr>";

        }

        echo "</table>";

        $conteudo = ob_get_contents();//PEGA O CONTEUDO EM BUFFER
        ob_end_clean();//LIMPA O BUFFER

        $arquivo_nome = "relatorio_revenda_produto-$login_fabrica.$login_admin.xls";
        $path         = "/www/assist/www/admin/xls/";
        $path_tmp     = "/tmp/";

        $arquivo_completo     = $path.$arquivo_nome;
        $arquivo_completo_tmp = $path_tmp.$arquivo_nome;

        $file = fopen($arquivo_completo_tmp, 'w');
        fwrite($file, $conteudo);
        fclose($file);

        system("cp $arquivo_completo_tmp $path");//COPIA ARQUIVO PARA DIR XLS

        echo $conteudo;
        echo '<br />';
        echo '<a href="xls/'.$arquivo_nome.'">';
            echo '<img src="/assist/imagens/excel.gif" border="0">';
            echo '<br />';
            echo '<font size="2" color="#000">Fazer download do relatório!</font>';
        echo '</a>';
        echo '<br />';

    } else {

        echo "<font size='2' face='Verdana, Tahoma, Arial' color='#D9E2EF'><b>Nenum registro encontrado!<b></font>";

    }

}

echo "<br />";

include "rodape.php";

?>