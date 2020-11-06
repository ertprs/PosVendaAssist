<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
$title = "Produtos";
$layout_menu = 'produtos';

//--==================== TIPO POSTO ====================--\\

include "cabecalho.php";
?>
<style>

    .fundo {
        background-image: url(http://img.terra.com.br/i/terramagazine/fundo.jpg);
        background-repeat: repeat-x;
    }
    .chapeu {
        color: #0099FF;
        padding: 2px;
        margin-bottom: 4px;
        margin-top: 10px;
        background-image: url(http://img.terra.com.br/i/terramagazine/tracejado3.gif);
        background-repeat: repeat-x;
        background-position: bottom;
        font-size: 13px;
        font-weight: bold;
    }

    .menu {
        font-size: 11px;
    }

    hr{
        height: 1px;
        margin: 15px 0;
        padding: 0;
        border: 0 none;
        background: #ccc;
    }

    a:link.menu {
        padding: 3px;
        display:block;
        font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
        color: navy;
        font-size: 13px;
        font-weight: bold;
        text-align: left;
        text-decoration: none;
    }

    a:visited.menu {
        padding: 3px;
        display:block;
        font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
        color: navy;
        font-size: 13px;
        font-weight: bold;
        text-align: left;
        text-decoration: none;
    }

    a:hover.menu {
        padding: 3px;
        display:block;
        font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
        color: black;
        font-size: 13px;
        font-weight: bold;
        text-align: left;
        text-decoration: none;
        background-color: #ced7e7;
    }
    .rodape{
        color: #FFFFFF;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 9px;
        background-color: #FF9900;
        font-weight: bold;
    }
    .detalhes{
        font-family: Arial, Helvetica, sans-serif;
        font-size: 12px;
        color: #333399;
    }

</style>

<table width="700" border="0" cellspacing="0" cellpadding="0" align='center'>
    <tr bgcolor = '#efefef'>
        <td rowspan='3' width='20' valign='top'><img src='imagens/marca25.gif'></td>
        <td  class="chapeu" colspan='2' >Tabelas de Preços Mão de Obra</td>
    </tr>
    <tr bgcolor = '#efefef'><td colspan='2' height='5'></td></tr>
    <tr bgcolor = '#efefef'>
        <td valign='top' class='menu'>
            <?php
                $sql = "SELECT /*tbl_linha.codigo_linha, tbl_linha.nome, tbl_linha.linha*/
                            tbl_linha.linha
                            FROM tbl_posto_linha
                            JOIN tbl_linha using(linha)
                            WHERE fabrica = $login_fabrica
                            AND posto = $login_posto
                            ORDER BY tbl_linha.nome ASC";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {
                    $linha_domesticos = "";
                    $linha_metal = "";

                    for ($i = 0 ; $i < pg_num_rows($res); $i++) {
                        $codigo_linha   = trim(pg_fetch_result($res,$i,codigo_linha));
                        $nome           = trim(pg_fetch_result($res,$i,nome));
                        $linha          = trim(pg_fetch_result($res,$i,linha));

                        if(($linha == 260 OR $linha == 263 OR $linha == 327) AND $linha_domesticos == ""){
                            echo "<br><dt>&nbsp;&nbsp;<b>»</b><strong><a href='lorenzetti_teste/tabela_de_mao_de_obra_domesticos.pdf' target='_blank'>Tabela Mão de Obra: Aparelhos Domésticos, Purificadores/Filtros e Válvulas</strong></a><br></dt>";
                            $linha_domesticos = "true";
                        }

                        if(($linha == 261 OR $linha == 603) AND $linha_metal == ""){
                            echo "<br><dt>&nbsp;&nbsp;<b>»</b><strong><a href='lorenzetti_teste/tabela_de_mao_de_obra_metal.pdf' target='_blank'>Tabela Mão de Obra: Metais e Plásticos</strong></a><br></dt>";
                            $linha_metal = "true";
                        }

                        if($linha == 265){
                            echo "<br><dt>&nbsp;&nbsp;<b>»</b><strong><a href='lorenzetti_teste/tabela_de_mao_de_obra_aquecedores_gas_e_pressurizadores.pdf'>Tabela Mão de Obra: Aquecedores a Gás e Pressurizadores</strong></a><br></dt>";
                        }

                        if($linha == 928){
                            echo "<br><dt>&nbsp;&nbsp;<b>»</b><strong><a href='lorenzetti_teste/tabela_de_mao_de_obra_loucas.pdf' target='_blank'>Tabela Mão de Obra: Louças</strong></a><br></dt>";
                        }
                    }
                }else{ echo "<br><dt>&nbsp;&nbsp;<b>»</b> Nenhum Cadastrado<br></dt>";}
            ?>
            <br>
        </td>
        <td rowspan='2'class='detalhes' width='350'>Escolha ao lado a linha do produto que deseja consultar a Tabela de Mão de Obra.</td>
    </tr>
</table>
<br>
</body>
</html>
