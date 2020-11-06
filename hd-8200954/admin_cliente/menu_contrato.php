<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="contrato";
include 'autentica_admin.php';
$layout_menu = "contrato";
$title = "Menu Contrato";
include "cabecalho_novo.php";
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
        GESTÃO DE CONTRATO
    </h3>
    <div style="margin: 0 auto;" class="ui-accordion-content">
        <table style="width: 100%" border="0" id="tbl_menu" cellspadding="0" cellspacing="0" align="center">
            <tr bgcolor='#fafafa'>
                <td width='25'><img src='../admin/imagens/icon/consulta.png'></td>
                <td nowrap width='260'>
                    <a href='consulta_contrato.php' class='menu'>Consulta de Contratos</a>
                </td>
                <td nowrap class='descricao'>Relatório de consultas abertas por essa área</td>
            </tr>
        </table>
    </div>
</div>

<div style="margin: 0 auto;display:none;" class="ui-accordion">
    <h3 class="ui-accordion-header">
        CALL-CENTER
    </h3>
    <div style="margin: 0 auto;" class="ui-accordion-content">
        <table style="width: 100%" border="0" id="tbl_menu" cellspadding="0" cellspacing="0" align="center">
            <tr bgcolor='#f0f0f0'>
                <td width='25'><img src='../admin/imagens/icon/cadastro.png'></td>
                <td nowrap width='260'>
                    <a href='pre_os_cadastro_sac_contrato.php' class='menu'>Cadastro Pré-atendimento</a>
                </td>
                <td nowrap class='descricao'>Cadastro de Atendimento para o Call-Center</td>
            </tr>
            <tr bgcolor='#fafafa'>
                <td width='25'><img src='../admin/imagens/icon/consulta.png'></td>
                <td nowrap width='260'>
                    <a href='consulta_atendimento_cliente_admin_contrato.php' class='menu'>Consulta de Atendimentos</a>
                </td>
                <td nowrap class='descricao'>Consulta de atendimentos para o Call-Center</td>
            </tr>
        </table>
    </div>
</div>
<div style="margin: 0 auto;" class="ui-accordion">
    <h3 class="ui-accordion-header">
        ORDENS DE SERVIÇOS
    </h3>
    <div style="margin: 0 auto;" class="ui-accordion-content">
        <table style="width: 100%" border="0" id="tbl_menu" cellspadding="0" cellspacing="0" align="center">
            <tr bgcolor='#fafafa'>
                <td width='25'><img src='../admin/imagens/icon/consulta.png'></td>
                <td nowrap width='260'>
                    <a href='consulta_atendimento_revenda.php' class='menu'>Consulta de OS</a>
                </td>
                <td nowrap class='descricao'>Consulta Ordens de Serviço Lançadas</td>
            </tr>
        </table>
    </div>
</div>
<div style="margin: 0 auto;" class="ui-accordion">
    <h3 class="ui-accordion-header">
        GERENCIAR USUÁRIO
    </h3>
    <div style="margin: 0 auto;" class="ui-accordion-content">
        <table style="width: 100%" border="0" id="tbl_menu" cellspadding="0" cellspacing="0" align="center">
            <tr bgcolor='#f0f0f0'>
                <td width='25'><img src='../admin/imagens/icon/cadastro.png'></td>
                <td nowrap width='260'>
                    <a href='altera_senha_new.php' class='menu'>Alterar Senha</a>
                </td>
                <td nowrap class='descricao'>Permite alterar a senha do seu usuário no sistema</td>
            </tr>
        </table>
    </div>
</div>

<? include "../admin/rodape.php" ?>
