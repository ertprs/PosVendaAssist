<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';

include_once 'helpdesk/mlg_funciones.php';

$sql = "SELECT posto_fabrica
        FROM tbl_fabrica
        WHERE fabrica = $login_fabrica ";
$res2 = pg_exec ($con,$sql);
$posto_da_fabrica = pg_result ($res2,0,0);

if (in_array($login_fabrica,array(50,151))){
    $natureza = "Retorno de remessa para troca";
    $verificacao = "1";
}

$cfop = "5949H";
if($estado !='SP') {
    $cfop = "6949";
}

if($_GET["nota_fiscal"]){

    $faturamento = (int)$_GET["nota_fiscal"];

    $a = 0;
    foreach($os_check as $linha_peca){
        $linha_peca = explode("|", $linha_peca);

        $campos_hidden .= "<input type='hidden' name='os_check[]' value='$os_check[$a]'>";

        $os         = $linha_peca[0];
        $pedido     = $linha_peca[1];
        $oss .= "$os";
        if($a < (count($os_check) -1) ){
            $oss .= ", ";
        }

        $pedidos .= "$pedido";
        if($a < (count($os_check) -1) ){
            $pedidos .= ", ";
        }
        $a++;
    }

    $sql_pecas = "
        SELECT  tbl_faturamento.data_input,
                tbl_faturamento_item.nota_fiscal_origem,
                tbl_faturamento_item.preco,
                tbl_faturamento_item.aliq_icms,
                tbl_faturamento.nota_fiscal,
                tbl_faturamento_item.qtde,
                tbl_faturamento_item.qtde_inspecionada,
                tbl_faturamento.faturamento,
                tbl_faturamento_item.peca,
                tbl_faturamento_item.os,
                tbl_os.sua_os,
                tbl_peca.referencia,
                tbl_peca.descricao,
                tbl_faturamento_correio.qtde_pacote
        FROM    tbl_faturamento
        JOIN    tbl_faturamento_item    ON  tbl_faturamento.faturamento     = tbl_faturamento_item.faturamento
        JOIN    tbl_peca                ON  tbl_peca.peca                   = tbl_faturamento_item.peca
        JOIN    tbl_os                  ON  tbl_faturamento_item.os         = tbl_os.os
                                        AND tbl_os.fabrica                  = $login_fabrica
   LEFT JOIN    tbl_faturamento_correio ON  tbl_faturamento.faturamento     = tbl_faturamento_correio.faturamento
                                        AND tbl_faturamento_correio.fabrica = {$login_fabrica}
        WHERE   tbl_faturamento.fabrica         = $login_fabrica
        AND     tbl_faturamento.distribuidor    = $login_posto
        AND     tbl_faturamento.nota_fiscal     IS NOT NULL
        AND     tbl_faturamento_item.os         IS NOT NULL
        AND     tbl_faturamento_item.pedido     IS NULL
        AND     tbl_faturamento.faturamento     = $faturamento";
        //AND tbl_os_item.pedido in ($pedidos)
    $res_pecas = pg_query($con, $sql_pecas);

    $devolucao = " RETORNO OBRIGATÓRIO ";
    $movimento = "RETORNAVEL";
    $pecas_produtos = "PEÇAS";
}

$menu_os[]['link'] = 'linha_de_separação';

$layout_menu = "os";
$title = traduz('menu.de.ordens.de.servico', $con);

include 'cabecalho.php';
?>
<br>

<?php

    $sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica ";

    if ($login_fabrica == 151) {
        $sql = "
            SELECT  tbl_posto.nome AS razao_social,
                    tbl_posto.cnpj,
                    tbl_posto.ie,
                    tbl_posto.endereco,
                    tbl_posto.cidade,
                    tbl_posto.estado,
                    tbl_posto.cep
            FROM    tbl_posto
            JOIN    tbl_fabrica ON tbl_fabrica.posto_fabrica = tbl_posto.posto
            WHERE   tbl_fabrica.fabrica = $login_fabrica
        ";
    }

    $res = pg_query($con, $sql);
    if(pg_num_rows($res)>0){
        $razao_social       = mb_strtoupper(pg_fetch_result($res, 0, razao_social));
        $cnpj               = pg_fetch_result($res, 0, cnpj);
        $ie                 = pg_fetch_result($res, 0, ie);
        $endereco           = mb_strtoupper(pg_fetch_result($res, 0, endereco));
        $cidade             = mb_strtoupper(pg_fetch_result($res, 0, cidade));
        $estado             = pg_fetch_result($res, 0, estado);
        $cep                = pg_fetch_result($res, 0, cep);

    }

?>


<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<link rel='stylesheet' type='text/css' href='plugins/shadowbox/shadowbox.css' />
<script src='plugins/shadowbox/shadowbox.js'></script>


<script type="text/javascript">
    $(function(){
        $("#qtde_pacote").blur(function(){
            if($(this).val() > 10){
                $(this).val(10);
            }else if($(this).val() < 1){
                $(this).val(1);
            }
        });
    });

    Shadowbox.init();

    function solicitaPostagemPosto(posto,total,faturamento) {
        <?php
        if ($login_fabrica == 50) { ?>
            var qtdePostagem =  document.getElementById('qtde_pacote').value;
            var url = "solicitacao_postagem_positron.php?posto="+ posto+"&total="+total+"&faturamento="+faturamento+"&qtdePostagem="+qtdePostagem;
        <?php
        } else { ?>
            var url = "solicitacao_postagem_positron.php?posto="+ posto+"&total="+total+"&faturamento="+faturamento;
        <?php
        } ?>

        Shadowbox.open({
            content : url,
            player : "iframe",
            title : "Autorização de Postagem",
            width : 900,
            height : 500
        });
    }
</script>
<? if (strlen($msg_erro) > 0) { ?>
<br>
<table width="650" border="0" align="center" class="error">
    <tr>
        <td><?php echo $msg_erro ?></td>
    </tr>
</table>
<br>
<?php }?>
<form name="frm_faturamento_lgr" method="POST" action="">
    <table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >
        <tr align='left'  height='16'>
            <td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>
                <b>&nbsp;<b><? echo "$pecas_produtos - $devolucao"?> </b>
                <br>
            </td>
        </tr>
        <tr>
            <td>Natureza <br> <b><?=$natureza?></b> </td>
            <td>CFOP <br> <b><?php echo $cfop; ?></b> </td>
            <td>Emissão <br> <b><?=date("d/m/Y")?></b> </td>
        </tr>
    </table>
    <table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >
        <tr>
            <td>Razão Social <br> <b><?=$razao_social?></b> </td>
            <td>CNPJ <br> <b><?=$cnpj?></b> </td>
            <td>Inscrição Estadual <br> <b><?=$ie?></b> </td>
        </tr>
    </table>
    <table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >
        <tr>
            <td>Endereço <br> <b><?=$endereco?></b> </td>
            <td>Cidade <br> <b><?=$cidade?></b> </td>
            <td>Estado <br> <b><?=$estado?></b> </td>
            <td>CEP <br> <b><?=$cep?></b> </td>
        </tr>
    </table>
    <table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_2'>
        <thead>
            <tr align='center'>
                <td><b>OS</b></td>
                <td><b>Código</b></td>
                <td><b>Descrição</b></td>
                <td><b>Qtde.</b></td>
                <td><b>Preço</b></td>
                <td><b>Total</b></td>
                <td><b>% ICMS</b></td>
            </tr>
        </thead>

        <?php
        $notas_fiscais = array();

        for($i=0; $i<pg_num_rows($res_pecas); $i++){
            $peca           = pg_fetch_result($res_pecas, $i, 'peca');
            $referencia     = pg_fetch_result($res_pecas, $i, 'referencia');
            $descricao      = pg_fetch_result($res_pecas, $i, 'descricao');
            $qtde           = pg_fetch_result($res_pecas, $i, 'qtde');
            $aliq_ipi       = pg_fetch_result($res_pecas, $i, 'aliq_ipi');
            $preco          = pg_fetch_result($res_pecas, $i, 'preco');
            $aliq_icms      = pg_fetch_result($res_pecas, $i, 'aliq_icms');
            $nota_fiscal    = pg_fetch_result($res_pecas, $i, 'nota_fiscal');
        $num_os         = pg_fetch_result($res_pecas, $i, 'os');
        $sua_os         = pg_fetch_result($res_pecas, $i, 'sua_os');
            $nota_fiscal_origem = pg_fetch_result($res_pecas, $i, 'nota_fiscal_origem');

            $qtde_pacote = pg_fetch_result($res_pecas, $i, 'qtde_pacote');

            //$notas_fiscais .= $nota_fiscal;
            if(!empty($nota_fiscal_origem)){
                array_push($notas_fiscais,$nota_fiscal_origem);
            }
            $notas_fiscais = array_unique($notas_fiscais);
            asort($notas_fiscais);

            $totalPeca = $preco * $qtde;
            $totalGeral += $totalPeca;

            if (strlen($aliq_ipi)==0) {
                $aliq_ipi=0;
            }

            if ($aliq_icms==0){
                $base_icms=0;
                $valor_icms=0;
            }else{
                $base_icms  = $totalPeca;
                $valor_icms = $totalPeca * $aliq_icms / 100;
            }

            if ($aliq_ipi==0)   {
                $base_ipi=0;
                $valor_ipi=0;
            }else {
                $base_ipi  = $total_item;
                $valor_ipi = $total_item*$aliq_ipi/100;
            }

            $total_base_icms  += $base_icms;
            $total_valor_icms += $valor_icms;


            $total_base_ipi   += $base_ipi;
            $total_valor_ipi  += $valor_ipi;

            //$totalPeca = number_format($totalPeca, 2, ',', ' ');
            //$totalGeral = number_format($totalGeral, 2, ',', ' ');

            $totalQtde += $qtde;?>


            <tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >
                <td align='left'><?=$sua_os?></td>
                <td align='left'><?=$referencia ?>
                    <input type='hidden' name='id_item_LGR_<?=$i?>' value='<?=$extrato_lgr ?>'>
                    <input type='hidden' name='id_item_peca_<?=$i?>' value='<?=$peca?>'>
                    <input type='hidden' name='id_item_preco_<?=$i?>' value='<?=$preco?>'>
                    <input type='hidden' name='id_item_qtde_<?=$i?>' value='<?=$qtde ?>'>
                    <input type='hidden' name='id_item_icms_<?=$i?>' value='<?=$aliq_icms ?>'>
                    <input type='hidden' name='id_item_ipi_<?=$i?>' value='<?=$aliq_ipi ?>'>
                    <input type='hidden' name='id_item_total_<?=$i?>' value='<?=$totalPeca ?>'>
                    <input type='hidden' name='id_item_notafiscal_<?=$i?>' value='<?=$nota_fiscal ?>'>
                    <input type='hidden' name='id_num_os_<?=$i?>' value='<?=$num_os ?>'>
                </td>
                <td align='left'><?=$descricao ?></td>
                <td align='center'><?=$qtde ?></td>
                <td align='right' nowrap><?=number_format($preco, 2, ',', ' ') ?></td>
                <td align='right' nowrap><?=number_format($totalPeca, 2, ',', ' ') ?></td>
                <td align='right'><?=$aliq_icms ?></td>
            </tr>
        <?php
        } ?>

    </table>
    <table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >
        <tr>
            <td>Base ICMS <br> <b> <?=  number_format($total_base_icms, 2, ',', ' ')?> </b> </td>
            <td>Valor ICMS <br> <b> <?= number_format($total_valor_icms, 2, ',', ' ')?> </b> </td>
            <td>Total de Peças <br> <b> <?= number_format($totalGeral, 2, ',', ' ') ?> </b> </td>
            <td>Total da Nota <br> <b> <?= number_format($totalGeral, 2, ',', ' ')?> </b> </td>
        </tr>
        <tfoot>
            <tr>
                <td colspan='8'> Referente as NFs. <?php echo implode(", ",$notas_fiscais) ?> </td>
            </tr>
        </tfoot>
    </table>
    <br>
    <?php
    if (in_array($login_fabrica, array(50))) {
    ?>
    <table cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px' width='650' border="0">
        <tr align='left'>
            <td align="right">Quantidade de Pacotes: </td>
            <td align="left">
                <input type="number" id="qtde_pacote" name="qtde_pacote" min="1" max="10" value="<?=$qtde_pacote;?>" class="frm" <?php if(!empty($qtde_pacote)){echo 'readonly=""';}  ?> >
            </td>
        </tr>

        <tr>
            <td>&nbsp; </td>
        </tr>
    </table>
    <?php
    }
    ?>
</form>
<?php
if (in_array($login_fabrica, array(50))) {
    $faturamento = $_GET['nota_fiscal']; ?>
    <input type="button" name="btnacao_solicita_autorizacao" onclick="javascript: solicitaPostagemPosto(<?="{$login_posto},{$totalGeral},{$faturamento}";?>);" value="Solicitar Autorização de Postagem">
<?php
}

include "rodape.php"; ?>
