<?php 
    include_once('dbconfig.php');
    include_once('includes/dbconnect-inc.php');
    include_once('autentica_usuario.php');
    if ($login_fabrica <> 3) {
        header("Location: menu_inicial.php");
        exit;
    }
    $layout_menu = 'pedido';
    $title       = 'Tipo de Pedido';

    include_once('cabecalho_new.php');
?>
<style>
    .botão_pedido{
        background-color: #fff;
        border: solid 2px #eeeeee;
        padding: 20px;
        cursor: pointer;
    }
    .botão_pedido:hover{
        background-color: #fff;
        border: solid 2px #ddd;
        padding: 20px;
        cursor: pointer;
    }
    .tabela_adicional tr td{
        padding: 5px;
    }
</style>
<div class="container-fluid">
    <div class="row-fluid">
        <div class="span12 tac">
            <h4>Escolha a melhor forma para efetuar seu pedido de peça.</h4>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span6 botão_pedido tac" onclick="window.location.href='pedido_cadastro_normal.php'">
            <img src="imagens/botoes/novos_icones/pedido_normal_128.png">
            <h3>Pedido Normal</h3>
        </div>
        <div class="span6 botão_pedido tac" onclick="window.location.href='loja_new.php'">
            <img src="imagens/botoes/novos_icones/b2b_128.png">
            <h3>Pedido com Pagamento</h3>
        </div>
    </div><br />
    <div class="row-fluid">
        <div class="span6">
            <table border="1"  class="tabela_adicional" width="100%">
                <tr>
                    <td class="tac" colspan="2"><b>Condições de pagamento</b></td>
                </tr>
                <tr>
                    <td class="tac">Faixa de valores do pedido</td>
                    <td class="tac">Prazo para Pagamento</td>
                </tr>
                <tr>
                    <td>R$ 50,00 à R$ 400,00</td>
                    <td>30 dias</td>
                </tr>
                <tr>
                    <td>R$ 400,01 à R$ 800,00</td>
                    <td>30 e 60 dias</td>
                </tr>
                <tr>
                    <td>Acima de R$ 800,01</td>
                    <td>30, 60 e 90 dias</td>
                </tr>
                <tr>
                    <td>Crédito</td>
                    <td>Sujeito a análise</td>
                </tr>
                <tr>
                    <td>Estoque</td>
                    <td>Sujeito a análise</td>
                </tr>
            </table>
        </div>
        <div class="span6">
            <table border="1"  class="tabela_adicional" width="100%">
                <tr>
                    <td class="tac" colspan="2"><b>Condições de pagamento</b></td>
                </tr>
                <tr>
                    <td class="tac">Faixa de valores do pedido</td>
                    <td class="tac">Prazo para Pagamento</td>
                </tr>
                <tr>
                    <td>Acima de R$ 50,00 </td>
                    <td>
                        Boleto à vista ou Conforme<BR >
                        Cartão de Crédito
                    </td>
                </tr>
                <tr>
                    <td>Crédito</td>
                    <td>
                        Boleto à vista ou Conforme<BR >
                        Cartão de Crédito
                    </td>
                </tr>
                <tr>
                    <td>Estoque</td>
                    <td>Garantido</td>
                </tr>
            </table>
        </div>
	</div>
</div>
