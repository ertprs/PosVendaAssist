<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';

include_once 'helpdesk/mlg_funciones.php';

$sql = "SELECT posto_fabrica
        FROM tbl_fabrica
        WHERE fabrica = $login_fabrica ";
$res2 = pg_query ($con,$sql);
$posto_da_fabrica = pg_fetch_result ($res2,0,0);

if (in_array($login_fabrica,array(50,151))){
    $natureza = "Retorno de remessa para troca";
    $verificacao = "1";
}

$cfop = "5949H";
if($estado !='SP') {
    $cfop = "6949";
}


if (isset($_POST["btnacao_lgr"])) {
    $nota_fiscal = $_POST['nota_fiscal'];

    if (strlen(trim($nota_fiscal))==0) {
        $msg_erro = "Por favor informar o número da Notal Fiscal.";
    }


    $total_nota = trim($_POST["id_nota_total_nota"]);
    $base_icms  = trim($_POST["id_nota_base_icms"]);
    $valor_icms = trim($_POST["id_nota_valor_icms"]);
    $base_ipi   = trim($_POST["id_nota_base_ipi"]);
    $valor_ipi  = trim($_POST["id_nota_valor_ipi"]);
    $cfop       = trim($_POST["id_nota_cfop"]);
    $movimento  = trim($_POST["id_nota_movimento"]);

    $qtde_peca_na_nota = trim($_POST["id_nota_qtde_itens"]);
    if ($qtde_peca_na_nota < 1) {
        $msg_erro .= "Quantidade de Peça inválida!";
    }

    $data_preenchimento = date("Y-m-d");

    $sql_nota = "
        SELECT  nota_fiscal
        FROM    tbl_faturamento
        where   nota_fiscal = '$nota_fiscal'
        and     distribuidor = $login_posto
        and     posto = $posto_da_fabrica
        and     fabrica = $login_fabrica";
    $res_nota = pg_query($con,$sql_nota);

    if (pg_num_rows($res_nota)>0) {
        $nota_fiscal = pg_fetch_result($res_nota,0,0);
        $extrato_devolucao = pg_fetch_result($res_nota,0,1);
        $msg_erro .= "A Nota fiscal $nota_fiscal já foi utilizada no extrato $extrato_devolucao, por favor digite outra nota";
    }

    if (strlen(trim($msg_erro))==0) {

		pg_query ($con,"BEGIN TRANSACTION");
        $sql = "INSERT INTO tbl_faturamento
                    (fabrica, emissao,saida, posto, distribuidor, cfop, total_nota, nota_fiscal, serie, natureza, base_icms, valor_icms, base_ipi, valor_ipi, extrato_devolucao, obs, movimento)

                VALUES ($login_fabrica,'$data_preenchimento','$data_preenchimento',$posto_da_fabrica,$login_posto,'$cfop',$total_nota,'$nota_fiscal','2','$natureza', $base_icms, $valor_icms, $base_ipi, $valor_ipi, null, 'Devolução de peças do posto para à Fábrica','$movimento') returning faturamento";
        $res = pg_query ($con,$sql);
        $faturamento = pg_fetch_result ($res,0,'faturamento');

        for($x=0;$x<$qtde_peca_na_nota;$x++){

            $lgr                = trim($_POST["id_item_LGR_$x"]);
            $peca               = trim($_POST["id_item_peca_$x"]);
            $peca_preco         = trim($_POST["id_item_preco_$x"]);
            $peca_qtde_total_nf = trim($_POST["id_item_qtde_$x"]);
            $peca_aliq_icms     = trim($_POST["id_item_icms_$x"]);
            $peca_aliq_ipi      = trim($_POST["id_item_ipi_$x"]);
            $peca_total_item    = trim($_POST["id_item_total_$x"]);
            $num_os            = trim($_POST["id_num_os_$x"]);
            $id_item_notafiscal = trim($_POST["id_item_notafiscal_$x"]);


            $peca_aliq_icms2 = $peca_aliq_icms;
            $peca_aliq_ipi2  = $peca_aliq_ipi;

            if (strlen($peca_aliq_icms2)>0 AND $peca_aliq_icms2 > 0) {
                    $peca_aliq_icms2 = " AND   tbl_faturamento_item.aliq_icms = ".$peca_aliq_icms2;
            }else{
                $peca_aliq_icms2 = "  AND  (tbl_faturamento_item.aliq_icms = 0 OR tbl_faturamento_item.aliq_icms IS NULL)";
            }

            if (strlen($peca_aliq_ipi2)>0 AND $peca_aliq_ipi2 > 0) {
                $peca_aliq_ipi2 = " AND   tbl_faturamento_item.aliq_ipi = ".$peca_aliq_ipi2;
            }else{
                $peca_aliq_ipi2 = "  AND  (tbl_faturamento_item.aliq_ipi = 0 OR tbl_faturamento_item.aliq_ipi IS NULL)";
            }

            $sql_nf = "
                SELECT distinct tbl_faturamento_item.os_item,
                        tbl_faturamento.nota_fiscal,
                        tbl_os_item.qtde,
                        tbl_faturamento_item.peca,
                        tbl_faturamento_item.preco,
                        tbl_faturamento_item.aliq_icms,
                        tbl_faturamento_item.aliq_ipi,
                        tbl_faturamento_item.base_icms,
                        tbl_faturamento_item.valor_icms,
                        tbl_faturamento_item.linha,
                        tbl_faturamento_item.base_ipi,
                        tbl_faturamento_item.valor_ipi,
                        tbl_faturamento_item.sequencia
                FROM    tbl_faturamento_item
                JOIN    tbl_faturamento USING (faturamento)
                JOIN    tbl_os_item     ON  (
                                                tbl_faturamento_item.pedido     = tbl_os_item.pedido
					    OR  tbl_faturamento_item.os_item    = tbl_os_item.os_item
                                            )
                                        AND (tbl_faturamento_item.peca = tbl_os_item.peca OR tbl_faturamento_item.peca_pedida = tbl_os_item.peca)
                JOIN    tbl_os_produto  ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
                WHERE   tbl_faturamento.fabrica           = $login_fabrica
                AND     tbl_faturamento.posto             = $login_posto
                AND     (
                            tbl_faturamento_item.peca           = $peca
                        OR  tbl_faturamento_item.peca_pedida    = $peca
                        )
                AND     tbl_faturamento_item.preco        = $peca_preco
                AND     (
                            tbl_faturamento.nota_fiscal = '$id_item_notafiscal'
                        OR  tbl_faturamento.nota_fiscal IS NULL
                        )
                AND     tbl_os_produto.os = $num_os
                        $peca_aliq_icms2 ";
            if ($verificacao!='1'){
                $sql_nf .= " $peca_aliq_ipi2  ";
            }
            $sql_nf .= "AND   tbl_faturamento.distribuidor      IS NULL
                        AND   tbl_faturamento.cancelada         IS NULL
                        ORDER BY tbl_faturamento.nota_fiscal";
            #exit(nl2br($sql_nf));
            $resNF = pg_query ($con,$sql_nf);

            $qtde_peca_inserir=0;


            for ($w = 0 ; $w < pg_num_rows ($resNF) ; $w++) {

                if ($qtde_peca_inserir < $peca_qtde_total_nf){

                    $faturamento_item= pg_fetch_result ($resNF,$w,faturamento_item);
                    $peca_nota       = pg_fetch_result ($resNF,$w,nota_fiscal);
                    $peca_qtde       = pg_fetch_result ($resNF,$w,qtde);
                    $peca_peca       = pg_fetch_result ($resNF,$w,peca);
                    $peca_preco      = pg_fetch_result ($resNF,$w,preco);
                    $peca_aliq_icms  = pg_fetch_result ($resNF,$w,aliq_icms);
                    $peca_base_icms  = pg_fetch_result ($resNF,$w,base_icms);
                    $peca_valor_icms = pg_fetch_result ($resNF,$w,valor_icms);
                    $peca_linha      = pg_fetch_result ($resNF,$w,linha);
                    $peca_aliq_ipi   = pg_fetch_result ($resNF,$w,aliq_ipi);
                    $peca_base_ipi   = pg_fetch_result ($resNF,$w,base_ipi);
                    $peca_valor_ipi  = pg_fetch_result ($resNF,$w,valor_ipi);
                    $sequencia       = pg_fetch_result ($resNF,$w,sequencia);

                    #HD 18528
                    if ($verificacao=='1'){
                        $aliq_ipi        = 0;
                        $peca_valor_ipi  = 0;
                        $peca_base_ipi   = 0;
                    }

                    # ICMS
                    if (strlen($peca_aliq_icms)==0) {$peca_aliq_icms = "0";}
                    if (strlen($peca_valor_icms)==0){$peca_valor_icms = "0";}
                    if (strlen($peca_base_icms)==0) {$peca_base_icms = "0";}

                    #IPI
                    if (strlen($peca_aliq_ipi)==0) {$peca_aliq_ipi = "0";}
                    if (strlen($peca_valor_ipi)==0){$peca_valor_ipi = "0";}
                    if (strlen($peca_base_ipi)==0) {$peca_base_ipi = "0";}

                    if (strlen($peca_linha)==0){
                        $peca_linha = " NULL ";
                    }

                    $qtde_peca_inserir += $peca_qtde;

                    if ($qtde_peca_inserir > $peca_qtde_total_nf){
                        $peca_base_icms  = 0;
                        $peca_valor_icms = 0;
                        $peca_base_ipi   = 0;
                        $peca_valor_ipi  = 0;
                        $peca_qtde       = $peca_qtde-$qtde_peca_inserir;
                        $peca_qtde       = $peca_qtde - ($qtde_peca_inserir-$peca_qtde_total_nf);

                        if ($peca_aliq_icms>0){
                            $peca_base_icms = $peca_qtde_total_nf*$peca_preco;
                            $peca_valor_icms= $peca_qtde_total_nf*$peca_preco*$peca_aliq_icms/100;
                        }
                        if ($peca_aliq_ipi>0){
                            $peca_base_ipi = $peca_qtde_total_nf*$peca_preco;
                            $peca_valor_ipi= $peca_qtde_total_nf*$peca_preco*$peca_aliq_ipi/100;
                        }
                    }

                    ///pegar a os
                    $sql = "INSERT INTO tbl_faturamento_item
                            (   faturamento,
                                peca,
                                qtde,
                                preco,
                                aliq_icms,
                                aliq_ipi,
                                base_icms,
                                valor_icms,
                                linha,
                                base_ipi,
                                valor_ipi,
                                nota_fiscal_origem,
                                sequencia,
                                os
                            )
                            VALUES (
                                $faturamento,
                                $peca,
                                $peca_qtde,
                                $peca_preco,
                                $peca_aliq_icms,
                                $peca_aliq_ipi,
                                $peca_base_icms,
                                $peca_valor_icms,
                                $peca_linha,
                                $peca_base_ipi,
                                $peca_valor_ipi,
                                '$peca_nota',
                                '$sequencia',
                                $num_os
                                ) ";
                    $res = pg_query ($con,$sql);

                    $msg_erro .= pg_errormessage($con);
                } else {
                    pg_query($con,"ROLLBACK TRANSACTION");
                    break;
                }
            }

            if ($login_fabrica == 151) {
                $sqlDesbloqueioOs = "
                    UPDATE  tbl_os_extra
                    SET     recolhimento = NULL
                    WHERE   os = $num_os
                ";
                $resDesbloqueioOs = pg_query($con,$sqlDesbloqueioOs);
            }


		}

		if (strlen($msg_erro) == 0) {
			pg_query ($con,"COMMIT TRANSACTION");
			$ok = " Nota $nota_fiscal gravada com sucesso.";
			header('Location: espelho_lgr.php?nota_fiscal='.$faturamento);
		} else {
			pg_query($con,"ROLLBACK TRANSACTION");
		}

    }
}

if(isset($_REQUEST["btnacao"])){

    $os_check = $_POST["os_check"];

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

    if ($login_fabrica == 151) {
        $cond = " AND     JSON_FIELD('bloqueio',tbl_os_item.parametros_adicionais)::BOOL IS FALSE";
        $left = " LEFT ";
    }

    if ($login_fabrica == 50) {

        $leftJoin = " LEFT JOIN    tbl_faturamento_item fid ON fid.os = tbl_os.os and fid.peca = tbl_os_item.peca";
        $cond = " AND   fid.faturamento_item isnull";
    }

    $sql_pecas = "
        SELECT  DISTINCT
                tbl_peca.referencia,
                tbl_peca.descricao,
                tbl_os_item.qtde,
                tbl_peca.peca,
                tbl_faturamento_item.preco,
                tbl_faturamento_item.aliq_ipi,
                tbl_faturamento_item.aliq_icms,
                tbl_faturamento_item.valor_icms,
                tbl_faturamento_item.base_icms,
                tbl_faturamento.nota_fiscal,
                tbl_os.os,
                tbl_os.sua_os
        FROM    tbl_os_item
        JOIN    tbl_os_produto          ON  tbl_os_produto.os_produto   = tbl_os_item.os_produto
        JOIN    tbl_os                  ON  tbl_os_produto.os           = tbl_os.os
        JOIN    tbl_peca                ON  tbl_peca.peca               = tbl_os_item.peca
  $left JOIN    tbl_faturamento_item    ON  (
                                                tbl_faturamento_item.os_item    = tbl_os_item.os_item
                                            OR  tbl_faturamento_item.pedido     = tbl_os_item.pedido
                                            )
                                        AND (tbl_os_item.peca                    = tbl_faturamento_item.peca OR tbl_os_item.peca = tbl_faturamento_item.peca_pedida)
  $left JOIN    tbl_faturamento         ON  tbl_faturamento_item.faturamento    = tbl_faturamento.faturamento
                                        AND tbl_faturamento.fabrica             = $login_fabrica
                                        AND tbl_faturamento.posto               = tbl_os.posto
   $leftJoin
   LEFT JOIN    tbl_faturamento fd      ON  fd.distribuidor = tbl_os.posto
        WHERE   tbl_os_item.peca_obrigatoria = 't'
        AND     tbl_os_produto.os in ($oss)
        $cond
        ";
//         echo nl2br($sql_pecas);
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
<b>&nbsp;<b><? echo "$pecas_produtos - $devolucao"?> </b><br>
</td>
</tr>
<tr>
<td>Natureza <br> <b><?=$natureza?></b> </td>
<td>CFOP <br> <b><?php
echo $cfop;
?></b> </td>
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
            $num_os         = pg_fetch_result($res_pecas, $i, 'sua_os');
            $os             = pg_fetch_result($res_pecas, $i, 'os');

            //$notas_fiscais .= $nota_fiscal;
            if(!empty($nota_fiscal)){
                array_push($notas_fiscais,$nota_fiscal);
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

            $totalQtde += $qtde;

    ?>

    <tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >
        <td align='left'><?=$num_os?></td>
        <td align='left'><?=$referencia ?>
        <input type='hidden' name='id_item_LGR_<?=$i?>' value='<?=$extrato_lgr ?>'>
        <input type='hidden' name='id_item_peca_<?=$i?>' value='<?=$peca?>'>
        <input type='hidden' name='id_item_preco_<?=$i?>' value='<?=$preco?>'>
        <input type='hidden' name='id_item_qtde_<?=$i?>' value='<?=$qtde ?>'>
        <input type='hidden' name='id_item_icms_<?=$i?>' value='<?=$aliq_icms ?>'>
        <input type='hidden' name='id_item_ipi_<?=$i?>' value='<?=$aliq_ipi ?>'>
        <input type='hidden' name='id_item_total_<?=$i?>' value='<?=$totalPeca ?>'>
        <input type='hidden' name='id_item_notafiscal_<?=$i?>' value='<?=$nota_fiscal ?>'>
        <input type='hidden' name='id_num_os_<?=$i?>' value='<?=$os ?>'>
        </td>
        <td align='left'><?=$descricao ?></td>
        <td align='center'><?=$qtde ?></td>
        <td align='right' nowrap><?=number_format($preco, 2, ',', ' ') ?></td>
        <td align='right' nowrap><?=number_format($totalPeca, 2, ',', ' ') ?></td>
        <td align='right'><?=$aliq_icms ?></td>
        </tr>
    <? } ?>

</table>
<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >
<tr>
<td>Base ICMS <br> <b> <?=  number_format($total_base_icms, 2, ',', ' ')?> </b> </td>
<td>Valor ICMS <br> <b> <?= number_format($total_valor_icms, 2, ',', ' ')?> </b> </td>
<td>Total de Peças <br> <b> <?= number_format($totalGeral, 2, ',', ' ') ?> </b> </td>
<td>Total da Nota <br> <b> <?= number_format($totalGeral, 2, ',', ' ')?> </b> </td>
</tr>
<tfoot><tr><td colspan='8'> Referente as NFs. <?php echo implode(", ",$notas_fiscais) ?> </td></tr></tfoot></table>
<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' ><tr><td>
<br><input type='hidden' name='id_nota_qtde_itens' value='<?=$totalQtde?>'>
<input type='hidden' name='id_nota_cfop'       value='<?=$cfop?>'>
<input type='hidden' name='id_nota_movimento'  value='<?=$movimento?>'>
<input type='hidden' name='id_nota_total_nota' value='<?=$totalGeral?>'>
<input type='hidden' name='id_nota_base_icms'  value='<?=$totalGeral?>'>
<input type='hidden' name='id_nota_valor_icms' value='<?=$total_valor_icms?>'>
<input type='hidden' name='id_nota_base_ipi'   value='<?=$total_base_ipi?>'>
<input type='hidden' name='id_nota_valor_ipi'  value='<?=$total_valor_ipi?>'>
<center><p><b>Observação:</b> RETORNO DE REMESSA DE MERCADORIA PARA TROCA EM GARANTIA.  PARTES E PECAS DE BEM VENDIDO EM GARANTIA, SUSPENSO DO IPI, ART 42  ITEM XIII, DECRETO No. 4.544/02</p><b>Preencha esta Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado<br><br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da sua Nota Fiscal: <input type='text' name='nota_fiscal' size='10' maxlength='7' value=''><br><br></td></tr></table><br><br>


<?php if(strlen(trim($ok))==0){?>
<table>
    <tr>
        <td>
            <?=$campos_hidden?>
            <input type="hidden" name="btnacao" value="t">
            <input type="submit" name="btnacao_lgr" value="Confirmar Nota de Devolução">
        </td>
    </tr>
</table>
<?php }else{ ?>
<table>
    <tr>
        <td style='color:green;'>
            <?=$campos_hidden?>
            <input type="hidden" name="btnacao" value="t">
            <b><?=$ok?></b>
        </td>
    </tr>
</table>
<?php } ?>
</form>


<? include "rodape.php"; ?>
