<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if(strlen($_GET['extrato']) == 0){
    echo "<script>";
    echo "close();";
    echo "</script>";
    exit;
}

$extrato = trim($_GET['extrato']);
$posto = $_GET['posto'];

$title = "PEÇAS PARA DEVOLUÇÃO";
?>

<style type="text/css">
    .error{
        border-right: #990000 1px solid;
        border-top: #990000 1px solid;
        font: 10pt Arial ;
        color: #ffffff;
        border-left: #990000 1px solid;
        border-bottom: #990000 1px solid;
        background-color: #FF0000;
    }
    .Titulo {
        text-align: center;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: x-small;
        font-weight: bold;

        color:#000000;
    }
    .Titulo2 {
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: 10px;
        font-weight: bold;
        color:#000000;
    }
    .Conteudo {
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: 11px;
        font-weight: normal;
        color:#000000;
    }

    .Conteudo2 {
        text-align: left;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: 10px;
        font-weight: normal;
        color:#000000;
    }

</style>
<p>
<?
    $sql = "SELECT tbl_peca.referencia,
                tbl_peca.descricao,
                tbl_extrato_lgr.qtde,
                (
                    SELECT array_to_string(array_agg(tbl_faturamento.nota_fiscal), '<br>')
                    FROM tbl_faturamento
                    INNER JOIN tbl_faturamento_item AS FI USING(faturamento)
                    WHERE tbl_faturamento.fabrica = $login_fabrica
                    AND FI.extrato_devolucao = $extrato
                    AND FI.peca = tbl_extrato_lgr.peca
                ) AS nota_fiscal
                FROM tbl_extrato_lgr
                JOIN tbl_peca ON tbl_peca.peca = tbl_extrato_lgr.peca AND tbl_peca.fabrica = $login_fabrica
                WHERE tbl_extrato_lgr.extrato = $extrato
                AND tbl_extrato_lgr.posto = $posto
                AND tbl_peca.devolucao_obrigatoria IS TRUE
                AND tbl_extrato_lgr.qtde_nf IS NULL";
    $res = pg_query($con,$sql);
    $totalRegistros = pg_num_rows($res);
    if ($totalRegistros == 0){
    ?>
        <table width="650" border="0" align="center" class="error">
            <tr>
                <td align="center">Não tem peças para devolução</td>
            </tr>
        </table>
    <?php
    }elseif ($totalRegistros > 0){

    ?>
        <TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='1' style='border-collapse: collapse' bordercolor='#000000'>
            <tr>
                <td class='Titulo' colspan='4' align='center'>
                    <BR><b><?=$title?><br>
                    Extrato <?=$extrato?>
                    </b><BR><BR>
                </td>
            </tr>
        </TABLE>
        <br>
        <TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='1' style='border-collapse: collapse' bordercolor='#000000'>
            <TR class='Titulo2'>
                <TD width='17%'>Ref. Peça</TD>
                <TD align='center' width='35%'>Descrição</TD>
                <TD align='center' width='35%'>Qtde</TD>
                <TD align='center' width='35%'>Nota Fiscal</TD>
            </TR>
            <?php

                for ($i = 0 ; $i < $totalRegistros; $i++){
                    $referencia     = pg_fetch_result($res, $i, 'referencia');
                    $descricao      = pg_fetch_result($res, $i, 'descricao');
                    $qtde           = pg_fetch_result($res, $i, 'qtde');
                    $nota_fiscal    = pg_fetch_result($res, $i, 'nota_fiscal');
            ?>
                    <TR class='Conteudo2' align='center'>
                        <TD><?=$referencia?></TD>
                        <TD align='center'><?=$descricao?></TD>
                        <TD align='center'><?=$qtde?></TD>
                        <TD align='center'><?=$nota_fiscal?></TD>
                    </TR>
            <?php
                }
            ?>
        </TABLE>
    <?php
    }
    ?>
<br>
