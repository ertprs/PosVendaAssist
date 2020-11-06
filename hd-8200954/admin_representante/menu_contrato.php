<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="contrato";
include 'autentica_admin.php';
$layout_menu = "contrato";
$title = "Menu Contrato";
include "cabecalho_new.php";
?>

<style type="text/css">

body {
    text-align: center;
}

.cabecalho {
    color: black;
    border-bottom: 2px dotted WHITE;
    font-size: 12px;
    font-weight: bold;
}

.descricao {
    padding: 5px;
    color: black;
    font-size: 12px;
    font-weight: normal;
    text-align: justify;
}

a:link.menu {
    padding: 3px;
    display:block;
    font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
    color: navy;
    font-size: 12px;
    font-weight: bold;
    text-align: left;
    text-decoration: none;
}

a:visited.menu {
    padding: 3px;
    display:block;
    font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
    color: navy;
    font-size: 12px;
    font-weight: bold;
    text-align: left;
    text-decoration: none;
}

a:hover.menu {
    padding: 3px;
    display:block;
    font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
    color: black;
    font-size: 12px;
    font-weight: bold;
    text-align: left;
    text-decoration: none;
    background-color: #ced7e7;
}

</style>
<div style="margin: 0 auto;" class="ui-accordion">
    <h3 class="ui-accordion-header">
        Gestão de Contratos
    </h3>
    <div style="margin: 0 auto;" class="ui-accordion-content">
        <table style="width: 100%" border="0" id="tbl_menu" cellspadding="0" cellspacing="0" align="center">
            <tr bgcolor='#f0f0f0'>
                <td width='25'><img src='imagens/marca25.gif'></td>
                <td nowrap width='260'>
                    <a href='cadastro_contrato.php?tipo=proposta' class='menu'>Cadastra Proposta</a>
                </td>
                <td nowrap class='descricao'>Cadastra Proposta de Contrato com cliente</td>
            </tr>
            <tr bgcolor='#fafafa'>
                <td width='25'><img src='imagens/marca25.gif'></td>
                <td nowrap width='260'>
                    <a href='consulta_contrato.php' class='menu'>Consulta de Proposta</a>
                </td>
                <td nowrap class='descricao'>Relatório de consultas abertas por essa área</td>
            </tr>
        </table>
    </div>
<? include "../admin/rodape.php" ?>