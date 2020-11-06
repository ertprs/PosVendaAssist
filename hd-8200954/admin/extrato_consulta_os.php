<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");

$admin_privilegios = "financeiro";
include "autentica_admin.php";
include 'funcoes.php';

include_once __DIR__ . '/../class/AuditorLog.php';

$arrDadosPagamento = array();
$ja_baixado       = false ;

$logExtratoSql = "SELECT *
                    FROM tbl_extrato
                        LEFT JOIN tbl_extrato_status ON tbl_extrato_status.extrato = tbl_extrato.extrato
                        LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
                        LEFT JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
                                            WHERE tbl_extrato.fabrica = {$login_fabrica}
                        AND tbl_extrato.extrato = ";

$logOSExtratoSql = "SELECT * FROM tbl_os_extra WHERE i_fabrica = {$login_fabrica} AND extrato = ";

if(isset($_POST["altera_data_pagamento"])){

    $data_pagamento = $_POST["data_pagamento"];
    $extrato = $_POST["extrato"];

    list($d, $m, $a) = explode("/", $data_pagamento);
    $data_pagamento = $a."-".$m."-".$d;

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

    $sql = "UPDATE tbl_extrato_pagamento SET data_pagamento = '{$data_pagamento}' WHERE extrato = {$extrato}";
    $res = pg_query($con, $sql);

    if(strlen(pg_last_error($con)) == 0){
        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
    }

    echo traduz("Data de Pagamento atualizada com Sucesso!");
    exit;

}

function verificaExtratoExcluido($acao,$extrato = null) {
    global $login_fabrica, $con, $novaTelaOs;

    if ($novaTelaOs && !empty($acao)) {
        switch ($acao) {
            case 'RECUSAR':
                $zera_extrato_devolucao = true;
                break;
            case 'EXCLUIR':
                $zera_extrato_devolucao = true;
                break;
            case 'ACUMULAR':
                $zera_extrato_devolucao = true;
                break;
            default:
                $zera_extrato_devolucao = false;
                break;
        }

        if ($zera_extrato_devolucao) {
            $sql = "SELECT fabrica
                    FROM tbl_extrato
                    WHERE extrato = {$extrato}
                    AND fabrica = 0";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $sql = "UPDATE tbl_faturamento_item
                        SET extrato_devolucao = NULL
                        WHERE tbl_faturamento_item.extrato_devolucao = {$extrato}";
                $res = pg_query($con, $sql);

                $sql = "UPDATE tbl_faturamento
                        SET extrato_devolucao = NULL
                        WHERE tbl_faturamento.extrato_devolucao = {$extrato}";
                $res = pg_query($con, $sql);

                /*HD - 6356801*/
                $sql = "DELETE FROM tbl_extrato_lgr WHERE extrato = {$extrato}";
                $res = pg_query($con, $sql);

                if (pg_last_error($con)) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }
return true;
}

function GeraTabelaEntregaTecnica($res_yanmar, $sem_cabecalho = 0){
    global $login_fabrica, $con, $fabricas_acerto_extrato, $aprovado, $novaTelaOs;

    $extrato                   = pg_fetch_result ($res_yanmar,0,extrato) ;
    $extrato_pagamento         = pg_fetch_result ($res_yanmar,0,extrato_pagamento) ;
    $valor_total               = pg_fetch_result ($res_yanmar,0,valor_total) ;
    $acrescimo                 = pg_fetch_result ($res_yanmar,0,acrescimo) ;
    $desconto                  = pg_fetch_result ($res_yanmar,0,desconto) ;
    $valor_liquido             = pg_fetch_result ($res_yanmar,0,valor_liquido) ;
    $nf_autorizacao            = pg_fetch_result ($res_yanmar,0,nf_autorizacao) ;
    $previsao_pagamento        = pg_fetch_result ($res_yanmar,0,previsao_pagamento) ;
    $data_vencimento           = pg_fetch_result ($res_yanmar,0,data_vencimento) ;
    $data_pagamento            = pg_fetch_result ($res_yanmar,0,data_pagamento) ;
    $obs                       = pg_fetch_result ($res_yanmar,0,obs) ;
    $autorizacao_pagto         = pg_fetch_result ($res_yanmar,0,autorizacao_pagto) ;
    $data_recebimento_nf       = pg_fetch_result ($res_yanmar,0,data_recebimento_nf) ;
    $codigo_posto              = pg_fetch_result ($res_yanmar,0,codigo_posto) ;
    $posto                     = pg_fetch_result ($res_yanmar,0,posto) ;
    $protocolo                 = pg_fetch_result ($res_yanmar,0,protocolo) ;
    $peca_sem_preco            = pg_fetch_result ($res_yanmar,0,peca_sem_preco) ;
    $os_sem_item               = pg_fetch_result ($res_yanmar,0,'os_sem_item') ;
    $admin_aprovou             = pg_fetch_result ($res_yanmar,0,admin_aprovou) ;
    $recalculo_pendente        = pg_fetch_result ($res_yanmar,0,recalculo_pendente) ;

    if (strlen($extrato_pagamento) > 0 ){
        $ja_baixado = true;
    }

    $sql = "SELECT count(*) as qtde
            FROM   tbl_os_extra
            WHERE  tbl_os_extra.extrato = $extrato";
    $resx = pg_query($con,$sql);
    if (pg_num_rows($resx) > 0) $qtde_os = pg_fetch_result($resx,0,qtde);

    if (count($mensagem_extrato) > 0) {
        $display = "block";
        $mensagem_extrato = implode('<br>', $mensagem_extrato);
    }else {
        $display = "none";
    }

    if ($sem_cabecalho == 0) {

        echo '<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" style="font-size: 11px;">
        <tr>
            <td bgcolor="#FFCCCC">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width="100%" valign="middle" align="left">&nbsp;<b>'.traduz('REINCIDÊNCIAS').'</b></td>
        </tr>
        </table>
        <br>';

        echo "<TABLE id='mensagem_extrato' width='700' border='0' align='center' cellspacing='1' cellpadding='0' style='background-color: #EEBBBB; padding: 3px; color: #990000; font-size: 11pt; margin-bottom: 20px; display: $display; text-align: center;'>";
        echo "<tr><td colspan='100%' id='mensagem_extrato_td'>$mensagem_extrato</td></tr>";
        echo "</TABLE>";
        echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='5'>";

        echo"<TR class='menu_top'>";
        echo"<TD align='left'> ".traduz('Extrato').": $extrato";
        echo "</TD>";
        echo "<TD align='left'> ".traduz('Data Geração').": " . pg_fetch_result ($res_yanmar,0,data_geracao) . "</TD>";
        echo"<TD align='left' $cols> ".traduz('Qtde de OS').": ". $qtde_os ."</TD>";
        echo"<TD align='left'> ".traduz('Total').": " . $real . number_format(pg_fetch_result ($res_yanmar,0,total),2,",",".") . "</TD>";
        echo"</TR>";

        echo"<TR class='menu_top'>";
        echo"<TD align='left' $cols > ".traduz('Código').": " . pg_fetch_result ($res_yanmar,0,codigo_posto) . " </TD>";
        $cols = ($login_fabrica == 30) ? 6 : 3 ;
        echo"<TD align='left' colspan='$cols'> ".traduz('Posto').": " . pg_fetch_result ($res_yanmar,0,nome_posto) . "  </TD>";
        echo"</TR>";
        echo"</TABLE>";
        echo"<br>";
        $sql = "SELECT  count(*) as qtde,
                        tbl_linha.nome
                FROM   tbl_os
                JOIN   tbl_os_extra  ON tbl_os_extra.os     = tbl_os.os
                JOIN   tbl_produto   ON tbl_produto.produto = tbl_os.produto
                JOIN   tbl_linha     ON tbl_linha.linha     = tbl_produto.linha
                                    AND tbl_linha.fabrica   = $login_fabrica
                WHERE  tbl_os_extra.extrato = $extrato GROUP BY tbl_linha.nome
                ORDER BY count(*)";

        $resx = pg_query($con,$sql);

        if (pg_num_rows($resx) > 0) {
            echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='5'>";
            echo "<TR class='menu_top'>";
            echo "<TD align='left'>LINHA</TD>";
            echo "<TD align='center'>QTDE OS</TD>";
            echo "</TR>";

            for ($i = 0 ; $i < pg_num_rows($resx) ; $i++) {
                $linha = trim(pg_fetch_result($resx,$i,nome));
                $qtde  = trim(pg_fetch_result($resx,$i,qtde));

                echo "<TR class='menu_top'>";
                echo "<TD align='left'>$linha</TD>";
                echo "<TD align='center'>$qtde</TD>";
                echo "</TR>";
            }
            echo "</TABLE>";
            echo "<br />";
            echo "<br />";
        }
        echo "<div style='display:none'>";
        if(pg_numrows($res_yanmar)>0){
            for($i=0;$i<pg_numrows($res_yanmar);$i++){

                $sua_os = trim(pg_fetch_result ($res_yanmar,$i,'sua_os'));
                $os     = trim(pg_fetch_result ($res_yanmar,$i,'os'));

                echo "<input type='checkbox' name='os[$i]' id='os_$i' value='$os'>";
                echo "<input type='hidden' name='sua_os[$i]' id='sua_os[$i]'  value='$sua_os'>";

            }
        }
        echo "</div>";
    }

    $libera_acesso_acoes = false;
    if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
        if((strlen($aprovado) == 0) or $login_fabrica == 147 ){
            $libera_acesso_acoes = true;
        }
    }elseif ($login_fabrica <> 1){
        $libera_acesso_acoes = true;
    }

    if ($libera_acesso_acoes) {
        $sql = "SELECT pedido
                FROM tbl_pedido
                WHERE pedido_kit_extrato = $extrato
                AND   fabrica            = $login_fabrica";
        $resE = pg_query($con,$sql);
        if (pg_num_rows($resE) == 0 and $login_fabrica == 8) {
            echo "<input type='button' value='".traduz('Pedido de Peças do Kit')."' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='pedido' ; document.frm_extrato_os.submit()\" ALT='".traduz('Pedido de Peças do Kit')."'>";
        }
    }

    $tamanho_tabela = 750;
    echo "<TABLE width='$tamanho_tabela' border='0' align='center' border='0' cellspacing='1' cellpadding='1' class='tabela'>\n";

    if (strlen($msg) > 0) {
        echo "<TR class='menu_top'>\n";
        echo "<TD colspan=10>$msg</TD>\n";
        echo "</TR>\n";
    }

    echo "<TR class='titulo_coluna'>\n";
    echo "<TD colspan='9'>".traduz('Valores de Entrega Técnica')."</TD>";
    echo "</TR>";

    echo "<TR class='titulo_coluna'>\n";
    if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
        if (strlen($aprovado) == 0) {
            echo "<TD align='center' width='30'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='".traduz('Selecionar todos')."' style='cursor: hand;' align='center'></TD>\n";
        }
    }//elseif (($ja_baixado == false AND $login_fabrica <> 6) OR ($ja_baixado==false AND $login_fabrica==6 ANd strlen($liberado)==0)) echo "<TD align='center' width='30'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></TD>\n";

    echo "<TD width='075'>".traduz('OS')."</TD>\n";
    echo "<TD width='075'>".traduz('Série')."</TD>\n";
    echo ($login_fabrica <> 1) ? "<TD width='075'>".traduz('Abertura')."</TD>\n" : "";
    echo ($login_fabrica == 176) ? "<TD width='075'>".traduz('Tipo de Atendimento')."</TD>\n" : "";
    echo "<TD width='130'>".traduz('Consumidor')."</TD>\n";
    echo "<TD width='130'>".traduz('Produto')."</TD>\n";

    if(in_array($login_fabrica,array(51,81,88,95,99,101,106,108,111,122,123,124,126,127,128,131,134,136,137,140,141,144)) || $novaTelaOs){
        echo "<TD width='80'>".traduz('Valor Entrega Tecnica')."</TD>\n";
    }

    if($multimarca =='t') echo "<td nowrap>".traduz('Marca')."</td>";

    if($inf_valores_adicionais || in_array($login_fabrica, array(142,145)) || isset($fabrica_usa_valor_adicional)){
        echo "<TD nowrap style='min-width: 80px;'>".traduz('Valor<br />Adicional')."</TD>\n";
    }

    if (in_array($login_fabrica, array(74,115,116,117,120,201,129,131,140,141,144,138,139,143,145)) || $novaTelaOs) {
        if (!in_array($login_fabrica, array(139))) {
            if (!in_array($login_fabrica, array(115,116,117,120,201,129,131,140,141,144,138,143,145)) && empty($novaTelaOs)) {
                echo "<TD width='130'>".traduz('Qtde KM')."</TD>\n";
            }
            if (isset($novaTelaOs)) {
                $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
            }

            if (!isset($novaTelaOs)) {
                echo "<TD width='130'>".traduz('Total KM')."</TD>\n";
            } else if (isset($novaTelaOs) && !$nao_calcula_km) {
                echo "<TD width='130'>".traduz('Total KM')."</TD>\n";
            }
        }

        if (isset($novaTelaOs)) {
            $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);
        }

        if (isset($novaTelaOs) && !$nao_calcula_peca && $extrato_sem_peca != "t") {
            echo "<TD width='130'>".traduz('Total Peça')."</TD>\n";
        } else if (!in_array($login_fabrica, array(115,116,117,120,201,129,131,140,141,144,139)) && $extrato_sem_peca != "t" && !isset($novaTelaOs)) {
            echo "<TD width='130'>".traduz('Total Peça')."</TD>\n";
        }

        if (!in_array($login_fabrica, array(131,140,143,145)) && empty($novaTelaOs)) {

            echo "<TD width='130'>".traduz('Total MO')."</TD>\n";
        }

        if (!in_array($login_fabrica, array(115,116,117,120,201,129,131,140,141,144,138,139,143,145)) && empty($novaTelaOs)) {

            echo "<TD width='130'>".traduz('Total KM + MO + PEÇAS')."</TD>\n";

            if($login_fabrica == 74){
                echo "<td>".traduz('Situação')."</td>";
                echo "<td>".traduz('Observação')."</td>";
            }

        }
        if(in_array($login_fabrica, array(140,141,143,144)) || isset($novaTelaOs)){
            echo "<TD nowrap style='min-width: 50px;'>".traduz('Total')."</TD>\n";
        }

    }

    $totalizador    = array();
    $ultima_familia = $ultima_familia_exibida = null;
    $qtde_de_inputs = pg_num_rows($res_yanmar);

    for ($i = 0 ; $i < $qtde_de_inputs ; $i++) {
        $os                 = trim(pg_fetch_result ($res_yanmar,$i,'os'));
        $sua_os             = trim(pg_fetch_result ($res_yanmar,$i,'sua_os'));
        $data               = trim(pg_fetch_result ($res_yanmar,$i,'data'));
        $abertura           = trim(pg_fetch_result ($res_yanmar,$i,'abertura'));
        $fechamento         = trim(pg_fetch_result ($res_yanmar,$i,'fechamento'));
        $finalizada         = trim(pg_fetch_result ($res_yanmar,$i,'finalizada'));
        $serie              = trim(pg_fetch_result ($res_yanmar,$i,'serie'));
        $codigo_fabricacao  = trim(pg_fetch_result ($res_yanmar,$i,'codigo_fabricacao'));
        $consumidor_nome    = trim(pg_fetch_result ($res_yanmar,$i,'consumidor_nome'));
        $consumidor_cidade  = trim(pg_fetch_result ($res_yanmar,$i,'consumidor_cidade'));
        $consumidor_fone    = trim(pg_fetch_result ($res_yanmar,$i,'consumidor_fone'));
        $revenda_nome       = trim(pg_fetch_result ($res_yanmar,$i,'revenda_nome'));
        $produto            = trim(pg_fetch_result ($res_yanmar,$i,'produto'));
        $produto_nome       = trim(pg_fetch_result ($res_yanmar,$i,'descricao'));
        $produto_referencia = trim(pg_fetch_result ($res_yanmar,$i,'referencia'));
        $marca              = trim(pg_fetch_result ($res_yanmar,$i,'marca'));
        $data_fechamento    = trim(pg_fetch_result ($res_yanmar,$i,'data_fechamento'));
        $os_reincidente     = trim(pg_fetch_result ($res_yanmar,$i,'os_reincidente'));
        $codigo_posto       = trim(pg_fetch_result ($res_yanmar,$i,'codigo_posto'));
        $total_pecas        = trim(pg_fetch_result ($res_yanmar,$i,'total_pecas'));
        $total_mo           = trim(pg_fetch_result ($res_yanmar,$i,'total_mo'));
        $qtde_km            = trim(pg_fetch_result ($res_yanmar,$i,'qtde_km'));
        $valor_km           = trim(pg_fetch_result ($res_yanmar,$i,'valor_km'));
        $total_km           = trim(pg_fetch_result ($res_yanmar,$i,'qtde_km_calculada'));
        $pedagio            = trim(pg_fetch_result ($res_yanmar,$i,'pedagio'));
        $taxa_visita        = trim(pg_fetch_result ($res_yanmar,$i,'taxa_visita'));
        $cortesia           = trim(pg_fetch_result ($res_yanmar,$i,'cortesia'));
        $os_sem_item        = pg_fetch_result ($res_yanmar,$i,'os_sem_item') ;
        $motivo_atraso      = pg_fetch_result ($res_yanmar,$i,'motivo_atraso') ;
        $motivo_atraso2     = pg_fetch_result ($res_yanmar,$i,'motivo_atraso2') ;
        $obs_reincidencia   = pg_fetch_result ($res_yanmar,$i,'obs_reincidencia') ;
        $nota_fiscal        = pg_fetch_result ($res_yanmar,$i,'nota_fiscal') ;
        $data_nf            = pg_fetch_result ($res_yanmar,$i,'data_nf') ;
        $nota_fiscal_saida  = pg_fetch_result ($res_yanmar,$i,'nota_fiscal_saida') ;
        $observacao         = pg_fetch_result ($res_yanmar,$i,'observacao') ;
        $consumidor_revenda = pg_fetch_result ($res_yanmar,$i,'consumidor_revenda');
        $peca_sem_estoque   = pg_fetch_result ($res_yanmar,$i,'peca_sem_estoque');
        $intervalo          = pg_fetch_result ($res_yanmar,$i,'intervalo');
        $troca_garantia     = pg_fetch_result ($res_yanmar,$i,'troca_garantia');
        $texto              = "";
        $admin              = pg_fetch_result ($res_yanmar,$i,'admin');
        $mao_de_obra_desconto = pg_fetch_result ($res_yanmar,$i,'mao_de_obra_desconto');

        if($os_sem_item > 0) {
            $sqlpr = "SELECT COUNT(*)
                FROM tbl_os_item
                JOIN tbl_os_produto USING (os_produto)
                JOIN tbl_servico_realizado USING (servico_realizado)
                LEFT JOIN tbl_pedido_cancelado USING(pedido,peca)
                WHERE tbl_os_produto.os = $os
                AND tbl_os_item.custo_peca = 0
                AND tbl_pedido_cancelado.pedido isnull
                AND tbl_servico_realizado.troca_de_peca";
            $respr = pg_query($con,$sqlpr);
            $peca_sem_preco = pg_fetch_result($respr,0,0);
        }

        $familia_descr      = pg_fetch_result($res_yanmar,$i,'familia_descr');
        $familia_cod        = pg_fetch_result($res_yanmar,$i,'familia_cod');
        $familia_id         = pg_fetch_result($res_yanmar,$i,'familia_id');
        $valor_adicional    = pg_fetch_result($res_yanmar,$i,'valores_adicionais');
        $entrega_tecnica    = pg_fetch_result($res_yanmar,$i,'entrega_tecnica');
        $total_pecas = ($login_fabrica == 90) ? 0 : $total_pecas;

        if ( isset($totalizador[$familia_id]) ) {
            $totalizador[$familia_id]['total_km']   += (float) $total_km;
            $totalizador[$familia_id]['total_mo']   += (float) $total_mo;
            $totalizador[$familia_id]['total']      += (float) $total_km + $total_mo;
        } else {
            $totalizador[$familia_id]['descr']       = $familia_descr;
            $totalizador[$familia_id]['total_km']   = (float) $total_km;
            $totalizador[$familia_id]['total_mo']   = (float) $total_mo;
            $totalizador[$familia_id]['total']      = (float) $total_km + $total_mo;
        }
        $totalizador['geral']['total_km']   += (float) $total_km;
        $totalizador['geral']['total_mo']   += (float) $total_mo;
        $totalizador['geral']['total_pedagio']  += (float) $pedagio;
        $totalizador['geral']['total']      += (float) $total_km + $total_mo + $pedagio;
        $ultima_familia                      = ( is_null($ultima_familia) ) ? $familia_id : $ultima_familia;
        $exibir_total_familia                = (boolean) ( ! is_null($ultima_familia) && $ultima_familia != $familia_id );

        $nota_fiscal = str_replace(array(";",",","/","-",".","+","!","@","*"),"",$nota_fiscal);
        $nota_fiscal_saida = str_replace(array(";",".","/","-",",","+","!","@","*","''"),"",$nota_fiscal_saida);

        if (strlen($os) > 0) {

            $ultima_familia = (int) $familia_id;

            if ($peca_sem_estoque == "t"){
                $coloca_botao = "sim";
            }

            $cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
            $btn = ($i % 2 == 0) ? "azul" : "amarelo";

            if (strlen($os_reincidente) > 0 && $login_fabrica != 158) {
                $sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
                $res1 = pg_query ($con,$sql);

                $sqlr="SELECT tbl_os_extra.os_reincidente from tbl_os_extra where os=$os";
                $resr=pg_query($con,$sqlr);

                if(pg_num_rows($resr)>0) $os_reinc=pg_fetch_result($resr,0,os_reincidente);
            }
            for($r = 0; $r < $rr; $r++){
                if($reincidencias_os[$r] == $os) $negrito = "<b>";
            }

            $reinc_class = '';
            $cor90 = "";

            if(in_array($login_fabrica, array(176))){
                    $sqlTA = "SELECT tbl_tipo_atendimento.descricao
                                 FROM tbl_os
                                 JOIN tbl_tipo_atendimento USING(tipo_atendimento)
                                WHERE tbl_os.os = $os
                                  AND tbl_os.fabrica = $login_fabrica";

                    $resTA = pg_query($con,$sqlTA);

                    $xtipo_atendimento   = pg_result($resTA,0,'descricao');
            }

            echo "<TR class='table_line{$reinc_class}' style='background-color: $cor;background-color: $cor90;'>\n";

            if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
                if (strlen($aprovado) == 0) {
                    if ($login_fabrica <> 1){
                        $rowspan = "";
                    }
                    echo "<TD align='center' $rowspan><input type='checkbox' name='os_y[$i]' value='$os'><input type='hidden' name='sua_os_y[$i]' value='$sua_os'></TD>\n";
                }
            }elseif ($ja_baixado == false AND $login_fabrica <> 1){
                if (($ja_baixado == false AND $login_fabrica <> 6) OR ($ja_baixado==false AND $login_fabrica==6 ANd strlen($liberado)==0)){
                    //echo "<TD align='center'><input type='checkbox' class='check-os' name='os[$i]_aux' idx='$i' value='$os'><input type='hidden' name='sua_os[$i]_aux' id='sua_os[$i]_aux'  value='$sua_os'></TD>\n";
                }
            }elseif($ja_baixado == false){ // HD 2225 takashi colocou esse if($ja_baixado == false) pois se nao fosse fabrica 1 colocava os checks... se estiver com problema tire
                echo "<TD align='center' rowspan='2'><input type='checkbox' name='os[$i]' value='$os'><input type='hidden' name='sua_os[$i]' value='$sua_os'></TD>\n";
            }
            echo "<TD nowrap><a href='os_press.php?os=$os' target='_blank'>";

            echo $sua_os . $texto . "</a> ";

            echo "</TD>\n";
            echo "<TD nowrap>$serie</TD>\n";
            if ($login_fabrica <> 1) echo "<TD align='center'>$abertura</TD>\n";
            if ($login_fabrica == 176) echo "<TD align='center'>$xtipo_atendimento</TD>\n";
            echo "<TD nowrap>$negrito<ACRONYM TITLE=\"$consumidor_nome\">".substr($consumidor_nome,0,17);
            echo "</ACRONYM></TD>\n";
            echo "<TD nowrap>$negrito<ACRONYM TITLE=\"$produto_referencia - $produto_nome\"><a href='lbm_consulta.php?produto=$produto' target='_blank'>";

            echo substr($produto_nome,0,17);
            echo "</a></ACRONYM></TD>\n";

            if(in_array($login_fabrica,array(51,81,88,95,99,101,106,108,111,122,123,124,126,127,128,131,134,136,137,140,141,144)) or $novaTelaOs){

                $total_mo = (empty($total_mo)) ? 0 : $total_mo;
                echo "<td align='right'>" . number_format($total_mo,2,",",".") . "</td>";
            }

            if($multimarca =='t')
                echo "<TD nowrap>$marca</TD>";

            if($inf_valores_adicionais || in_array($login_fabrica, array(142, 145)) || isset($fabrica_usa_valor_adicional)){
                echo "<TD align='right'>".number_format($valor_adicional,2,",",".")."</TD>";
            }

            if (in_array($login_fabrica,array(30,50,85,90,1,15,52,24,42,91,74,94,35,87,104,114,115,116,117,120,201,121,125,128,129,131,134,139,140,141,144)) || $novaTelaOs) {

                $total_os = $total_mo;

                if (isset($novaTelaOs)) {
                    $total_os = $total_mo;

                    $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
                    $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);

                    if (!$nao_calcula_peca) {
                        $total_os += $total_pecas;
                    }

                    if (!$nao_calcula_km) {
                        $total_os += $total_km;
                    }
                }

                if (!in_array($login_fabrica,array(15,24,35,42,52,74,87,94,104,114,115,116,117,120,201,121,125,128,129,131,134,136,140,141,144,138,139,143,145)) && empty($novaTelaOs)) {
                    echo "<TD align='left' nowrap>$negrito<ACRONYM TITLE=\"$revenda_nome\">". substr($revenda_nome,0,17) . "</ACRONYM></TD>\n";
                }

                if (isset($novaTelaOs)) {
                    $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
                }

                if (isset($novaTelaOs) && !$nao_calcula_km) {
                    echo "<TD class='td-km' align='right' nowrap> $negrito " ;

                    $qtde_km = ($qtde_km>0) ? traduz("Kilometragem: % Km", null, null, [$qtde_km]) : "&nbsp;";
                    if (strlen($total_km) == 0) {
                        $total_km = 0;
                    }
                    echo "<ACRONYM TITLE=\"$qtde_km\">".number_format($total_km,2,",",".")."</ACRONYM>\n";
                    echo "</TD>\n";
                }

                if (isset($novaTelaOs)) {
                    $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);
                }
        
                if (isset($novaTelaOs) && !$nao_calcula_peca && $extrato_sem_peca != "t") {
                    echo "<TD align='right' nowrap >$negrito " ;
                    if ($peca_sem_preco == 0) {
                        if (strlen($total_pecas) == 0) {
                            $total_pecas = 0;
                        }
                        echo number_format($total_pecas,2,",",".");
                    } else {
                        echo ($os_sem_item == 0) ? "":"<font color='#ff0000'><b>".traduz('SEM PREÇO')."</b></font>";
                    }
                    echo "</TD>\n";
                }

                if($inf_valores_adicionais || in_array($login_fabrica, array(140,141,142,144)) || isset($fabrica_usa_valor_adicional)){
                    $total_os = $total_mo + $total_km + $entrega_tecnica + $valor_adicional;
                }

                echo "<TD class='td-total' align='right' nowrap>$negrito".number_format($total_os,2,",",".")."</TD>\n";

                if ($login_fabrica<>15 && strlen($msg_2) > 0) {
                    echo "<TD align='right'>$msg_2</TD>\n"; $msg_2 = '';
                    $colspan_add = 1;
                }
            }

            $sqlD = "SELECT status_os FROM tbl_os_status WHERE os = $os and status_os = 91 and fabrica_status = $login_fabrica order by os_status desc limit 1;";

            $resD = @pg_query($con,$sqlD);
            if(@pg_num_rows($resD) > 0){
                echo "<td bgcolor='#FFCC00'>".traduz('Pendência Doc.')."</td>";
            }

            echo "</TR>\n";
            $negrito ="";
        }
    }

    if($qtde_de_inputs > 0){

        if ($login_fabrica == 1 or $login_fabrica==50 ) $colspan = 10; else $colspan = 7;
        if (in_array($login_fabrica,array(6,42,104,121)))  $colspan = 9;
        if (in_array($login_fabrica,array(2,125)))  $colspan = 8;
        if (in_array($login_fabrica,array(24,94,35,74,115,116,117,120,201,125,129,131,140,141,144))) $colspan = 11;
        if ($login_fabrica == 30) $colspan = 7;
        if (in_array($login_fabrica,array(30,15)) ) $colspan= 12;
        if ($login_fabrica == 50) $colspan = 14;
        $colspan = $login_fabrica == 87 ? 14 : $colspan;
        $colspan += $colspan_add;

        if($login_fabrica == 35){
            $colspan = 12;
        }

        echo "<br /> <table class='table table-bordered' style='margin-top: 20px;'>";
        echo "<TR class='titulo_coluna'>\n";
        echo "<TD colspan='3' align='left'><p>&nbsp; ".traduz("AÇÃO PARA OS's MARCADAS").": &nbsp; </p>";
        echo "<input type='hidden' name='posto' value='$posto'>";
        echo "<select name='select_acao' size='1' class='frm'>";
        echo "<option value=''></option>";

        if (isset($novaTelaOs)) {
            echo "<option value='REABRIR' ".(($_POST["select_acao"] == "REABRIR") ? "selected" : "").">".traduz('REABRIR OS (RETIRA DO EXTRATO)')."</option>";
            if($login_fabrica <> 147){
                echo "<option value='RECUSAR' ".(($_POST["select_acao"] == "RECUSAR") ? "selected" : "").">".traduz('RECUSAR OS (ZERAR VALOR)')."</option>";
            }
            echo "<option value='EXCLUIR' ".(($_POST["select_acao"] == "EXCLUIR") ? "selected" : "").">".traduz('EXCLUIR OS')."</option>";
            echo "<option value='ACUMULAR' ".(($_POST["select_acao"] == "ACUMULAR") ? "selected" : "").">".traduz('ACUMULAR PARA PRÓXIMO EXTRATO')."</option>";
        } else {
            if($login_fabrica == 91){ //hd_chamado=2754972
                $label_acao = traduz("BLOQUEADA NESTE EXTRATO");
                echo "<option value='RECUSADA_PAGAMENTO' ".(($_POST["select_acao"] == "RECUSADA_PAGAMENTO") ? "selected" : "").">RECUSADA PAGAMENTO</option>";
            }else{
                $label_acao = traduz("RECUSADO PELO FABRICANTE");
            }
            echo "<option value='RECUSAR' ".(($_POST["select_acao"] == "RECUSAR") ? "selected" : "").">$label_acao</option>";
            echo "<option value='EXCLUIR' ".(($_POST["select_acao"] == "EXCLUIR") ? "selected" : "").">".traduz('EXCLUÍDA PELO FABRICANTE')."</option>";

            if($login_fabrica <> 91 ) { # HD 303959
                echo "<option value='ACUMULAR' ".(($_POST["select_acao"] == "ACUMULAR") ? "selected" : "").">".traduz('ACUMULAR PARA PRÓXIMO EXTRATO')."</option>";
            }
        }

        if(in_array($login_fabrica,array(88,101))) {

                // HD 406128
                echo '<option value="ZERAR">'.traduz('ZERAR MÃO-DE-OBRA').'</option>';

        }

        if($login_fabrica == 1){
                echo "<option value='RECUSAR_DOCUMENTO' ".(($_POST["select_acao"] == "RECUSAR_DOCUMENTO") ? "selected" : "").">".traduz('PENDÊNCIA DE DOCUMENTO')."</option>";

        }
        if($login_fabrica == 1){
            echo "<option value='RECUSAR_DOCUMENTO' ".(($_POST["select_acao"] == "RECUSAR_DOCUMENTO") ? "selected" : "").">".traduz('PENDÊNCIA DE DOCUMENTO')."</option>";
        }

        if (in_array($login_fabrica, array(66, 11, 172))) {
            $sql = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = $login_fabrica AND status_os = 13 AND liberado IS TRUE;";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) > 0) {
                echo "<option value=''>-->".traduz('RECUSAR OS')."</option>";

                for($l=0;$l<pg_num_rows($res);$l++){
                    $motivo_recusa = pg_fetch_result($res,$l,motivo_recusa);
                    $motivo        = pg_fetch_result($res,$l,motivo);
                    $motivo = substr($motivo,0,50);
                    echo "<option value='$motivo_recusa'>$motivo</option>";
                }
            }
            $sql = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = $login_fabrica AND status_os = 14 AND liberado IS TRUE;";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) > 0) {
                echo "<option value=''>-->".traduz('ACUMULAR OS')."</option>";

                for($l=0;$l<pg_num_rows($res);$l++){
                    $motivo_recusa = pg_fetch_result($res,$l,motivo_recusa);
                    $motivo        = pg_fetch_result($res,$l,motivo);
                    $motivo = substr($motivo,0,50);
                    echo "<option value='$motivo_recusa'>$motivo</option>";
                }
            }
            $sql = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = $login_fabrica AND status_os = 15 AND liberado IS TRUE;";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) > 0) {
                echo "<option value=''>-->".traduz("EXCLUIR OS")."</option>";

                for($l=0;$l<pg_num_rows($res);$l++){
                    $motivo_recusa = pg_fetch_result($res,$l,motivo_recusa);
                    $motivo        = pg_fetch_result($res,$l,motivo);
                    $motivo = substr($motivo,0,50);
                    echo "<option value='$motivo_recusa'>$motivo</option>";
                }
            }
        }

        echo "</select>";
        echo " &nbsp; <input type='button' value='".traduz('Continuar')."' border='0' align='absmiddle' onclick='javascript: document.frm_extrato_os.submit()' style='cursor: pointer;margin-bottom: 10px;'>";
        echo "</TD>\n";
        echo "</TR>\n";
        echo "</table>\n";

    }

    if ($qtde_de_inputs == 0) {
        echo "<TR class='table_line{$reinc_class}' style='background-color: $cor;background-color: $cor90;'>\n";
        echo "<td colspan='9'>".traduz('Nenhum resultado encontrado')."</td>";
        echo "</TR>";
    }

    $libera_acesso_acoes = false;
    if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
        if (strlen($aprovado) == 0) {
            $libera_acesso_acoes = true;
        }
    }elseif ( (strlen($extrato_valor) == 0 AND $ja_baixado == false AND $login_fabrica <> 6) OR (strlen($extrato_valor) == 0 AND $ja_baixado == false AND $login_fabrica == 6) AND strlen($liberado)==0 ) {
        $libera_acesso_acoes = true;
    }
    //echo "<input type='hidden' name='contador' value='$i'>";
    echo "</TABLE>\n";

    if (isset($novaTelaOs) and !in_array($login_fabrica, array("147"))) {
        $sqlExtratoLiberado = "SELECT liberado FROM tbl_extrato WHERE fabrica = {$login_fabrica} AND extrato = {$extrato} AND liberado IS NOT NULL";
        $resExtratoLiberado = pg_query($con, $sqlExtratoLiberado);

        if (pg_num_rows($resExtratoLiberado) > 0) {
            $libera_acesso_acoes = false;
        }
    }
    //echo "<input type='hidden' name='contador' value='$i'>";
    echo "</TABLE>\n";
}

function getPagamentosLancados($extrato){
    global $con;
    global $login_fabrica;

    $sql = " SELECT tbl_extrato_pagamento.valor_total                                               ,
                            tbl_extrato_pagamento.acrescimo                                                 ,
                            tbl_extrato_pagamento.desconto                                                  ,
                            tbl_extrato_pagamento.valor_liquido                                             ,
                            tbl_extrato_pagamento.nf_autorizacao                                            ,
                            to_char (tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
                            to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento  ,
                            tbl_extrato_pagamento.autorizacao_pagto                                         ,
                            tbl_extrato_pagamento.obs                                                       ,
                            tbl_extrato_pagamento.extrato_pagamento,
                            tbl_extrato_pagamento.baixa_extrato
                            FROM tbl_extrato_pagamento
                            WHERE extrato = $extrato ";

    $res = pg_query($con, $sql);
    return pg_fetch_all($res);
}

function jaBaixado($extrato){
    global $con;

    $sql = "SELECT baixa_extrato
        FROM tbl_extrato_pagamento
            WHERE baixa_extrato is not null AND
          extrato = {$extrato}";

    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){
        return true;
    }else{
        return false;
    }

}

function baixarPagamentos($extrato){
    global $con;
    global $login_fabrica;

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

    $baixaPagamentos = "UPDATE tbl_extrato_pagamento
                        SET baixa_extrato = now() WHERE extrato = {$extrato}";
    if(!pg_query($baixaPagamentos)){
        throw new Exception(traduz("Erro ao Baixar extrato"));
    }

    $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
}

function cadastrarPagamento($arrDadosPagamento){
    global $con;
    global $login_fabrica;
    global $login_admin;

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

    pg_query($con, "BEGIN TRANSACTION");
    $insertPagamento = "INSERT INTO tbl_extrato_pagamento (
                                extrato           ,
                                valor_total       ,
                                acrescimo         ,
                                desconto          ,
                                valor_liquido     ,
                                nf_autorizacao    ,
                                data_vencimento   ,
                                data_pagamento    ,
                                autorizacao_pagto ,
                                obs               ,
                                baixa_extrato,
                                admin             ,
                                data_recebimento_nf
                            )VALUES( ".
                                $arrDadosPagamento["extrato"] .           " , ".
                                $arrDadosPagamento["valor_total"] .       " , ".
                                $arrDadosPagamento["acrescimo"].          " , ".
                                $arrDadosPagamento["desconto"] .          " , ".
                                $arrDadosPagamento["valor_liquido"] .     " , ".
                                $arrDadosPagamento["nf_autorizacao"] .    " , ".
                                $arrDadosPagamento["data_vencimento"] .   " , ".
                                $arrDadosPagamento["data_pagamento"] .   " , ".

                                $arrDadosPagamento["autorizacao_pagto"] . " , ".
                                $arrDadosPagamento["obs"] .               " , ".
                                "NULL". " , ".

                                $login_admin  .                            " , ".
                                $arrDadosPagamento["data_recebimento_nf"] ."
                            ) ";
    $res = pg_query($con, $insertPagamento);

    if(strlen(pg_last_error($con)) == 0){

        pg_query($con, "COMMIT TRANSACTION");
        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);

    }else{
        throw new Exception(traduz("ERRO ao cadastrar pagamento."));
        pg_query($con, "ROLLBACK TRANSACTION");
    }
}

if(in_array($login_fabrica, array(152,154,171,178,180,181,182,184,191,200))){
    include_once S3CLASS;
    $s3_extrato = new AmazonTC("extrato", (int) $login_fabrica);
}

if(isset($_POST['ajax_km_mo']) and $login_fabrica == 15){

    $os_ajax = $_POST['os'];
    $mo_ajax = $_POST['mo'];
    $km_ajax = $_POST['km'];

    if(strlen(trim($os_ajax)) > 0){
        $sql = "UPDATE tbl_os SET ";
        if(strlen($mo_ajax) > 0){
            $mo_ajax = str_replace(',', '.', $mo_ajax);
            $sql .= " mao_de_obra = ".$mo_ajax;
        }
        if(strlen(trim($km_ajax)) > 0){
            $km_ajax = str_replace(',', '.', $km_ajax);
            $sql .= " qtde_km_calculada = ".$km_ajax;
        }

        $sql .= " WHERE os = ".$os_ajax;

        $res = pg_query($con,$sql);
        $ret = pg_affected_rows($res);
        if($ret > 0){
            $extrato_ajax = $_GET['extrato'];
            $sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato_ajax);";
            $res = pg_query($con,$sql);
            $res = pg_fetch_all($res);

            if($res[0]['fn_totaliza_extrato'] == 't'){

                $sql = "select qtde_km_calculada, mao_de_obra from tbl_os where os = $os_ajax;";
                $res = pg_query($con,$sql);
                $total = pg_result($res,0,qtde_km_calculada) + pg_result($res,0,mao_de_obra);

                $response = array(
                    "status" => "ok","mo" => number_format($mo_ajax,2,',','.'),
                    "km" => number_format($km_ajax,2,',','.'),
                    "total" => number_format($total,2,',','.')
                );
            }else{
                $response = array("status" => "error1");
            }
        }
    }else{
        $response = array("status" => "error2");
    }

    echo json_encode($response);
    exit;
}

if(in_array($login_fabrica,array(3,11,126,172))){
    if(isset($_POST["gerar_excel"])){

        $gerar_excel  =          $_POST['gerar_excel'];
        $extrato      =      $_POST['extrato'];
        $data_inicial =          $_POST['data_inicial'];
        $data_final   =      $_POST['data_final'];
        $cnpj         =  $_POST['cnpj'];
        $razao        =  $_POST['razao'];

        $case_log  = " CASE WHEN tbl_os_log.os_atual IS NOT NULL
                THEN 1
                ELSE 0
            END as log,
            os_atual AS os_log,";
        $join_log  = " LEFT JOIN tbl_os_log ON tbl_os.os = tbl_os_log.os_atual ";
        $group_log = " os_atual,";

        $sql = " SELECT      lpad (tbl_os.sua_os,10,'0')                                  AS ordem           ,
                        tbl_os.os                                                                       ,
                        tbl_os.sua_os                                                                   ,
                        to_char (tbl_os.data_digitacao,'DD/MM/YYYY')                 AS data            ,
                        to_char (tbl_os.data_abertura ,'DD/MM/YYYY')                 AS abertura        ,
                        to_char (tbl_os.data_fechamento,'DD/MM/YYYY')                AS fechamento       ,
                        to_char (tbl_os.finalizada    ,'DD/MM/YYYY')                 AS finalizada      ,
                        tbl_os.consumidor_revenda                                                       ,
                        tbl_os.serie                                                                    ,
                        tbl_os.codigo_fabricacao                                                        ,
                        tbl_os.consumidor_nome                                                          ,
                        tbl_os.consumidor_fone                                                          ,
                        tbl_os.revenda_nome                                                             ,
                        tbl_os.troca_garantia                                                           ,
                        tbl_os.data_fechamento,

                        (SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) JOIN tbl_servico_realizado USING (servico_realizado) WHERE tbl_os_produto.os = tbl_os.os and ressarcimento) AS total_pecas  ,

                        tbl_os.mao_de_obra                                           AS total_mo        ,
                        tbl_os.qtde_km                                               AS qtde_km         ,
                        tbl_os.qtde_km_calculada                                     AS qtde_km_calculada,
                        COALESCE(tbl_os.pedagio, 0)                                  AS pedagio,
                        tbl_os.cortesia                                                                 ,
                        tbl_os.nota_fiscal                                                              ,
                        to_char(tbl_os.data_nf, 'DD/MM/YYYY')                        AS data_nf         ,
                        tbl_os.nota_fiscal_saida                                                        ,
                        tbl_os.posto                                                                    ,
                        tbl_produto.produto                                                             ,
                        tbl_produto.referencia                                                          ,
                        tbl_produto.descricao                                                           ,
                        tbl_os_extra.extrato                                                            ,
                        tbl_os_extra.os_reincidente                                                     ,
                        tbl_os.observacao                                                               ,
                        tbl_os.motivo_atraso                                                            ,
                        tbl_os_extra.motivo_atraso2                                                     ,
                        tbl_os_extra.taxa_visita                                                        ,
                        tbl_os.obs_reincidencia                                                         ,
                        tbl_os.valores_adicionais                                                       ,
                        to_char (tbl_extrato.data_geracao,'DD/MM/YYYY')              AS data_geracao    ,
                        tbl_extrato.total                                            AS total           ,
                        tbl_extrato.mao_de_obra                                      AS mao_de_obra     ,
                        tbl_extrato.pecas                                            AS pecas           ,
                        tbl_extrato.deslocamento                                     AS total_km        ,
                        tbl_extrato.admin                                            AS admin_aprovou   ,
                        tbl_extrato.recalculo_pendente                                                  ,
                        lpad (tbl_extrato.protocolo::text,6,'0')                     AS protocolo       ,
                        tbl_posto.nome                                               AS nome_posto      ,
                        tbl_posto_fabrica.codigo_posto                               AS codigo_posto    ,
                        tbl_extrato_pagamento.valor_total                                               ,
                        tbl_extrato_pagamento.acrescimo                                                 ,
                        tbl_extrato_pagamento.desconto                                                  ,
                        tbl_extrato_pagamento.valor_liquido                                             ,
                        tbl_extrato_pagamento.nf_autorizacao                                            ,
                        to_char (tbl_extrato.previsao_pagamento,'DD/MM/YYYY') AS previsao_pagamento ,
                        to_char (tbl_extrato.data_recebimento_nf,'DD/MM/YYYY') AS data_recebimento_nf ,
                        to_char (tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
                        to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento  ,
                        tbl_extrato_pagamento.autorizacao_pagto                                         ,
                        tbl_os_extra.valor_por_km as valor_km                                           ,
                        tbl_extrato_pagamento.obs                                                       ,
                        tbl_extrato_pagamento.extrato_pagamento                                         ,
                        (SELECT COUNT(*) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) JOIN tbl_servico_realizado USING (servico_realizado) WHERE tbl_os_produto.os = tbl_os.os AND tbl_os_item.custo_peca = 0 AND tbl_servico_realizado.troca_de_peca IS TRUE) AS peca_sem_preco,
                        (SELECT COUNT(1) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os ) AS os_sem_item,
                        (SELECT peca_sem_estoque FROM tbl_os_item JOIN tbl_os_produto using(os_produto) WHERE tbl_os_produto.os = tbl_os.os and peca_sem_estoque is true limit 1) AS peca_sem_estoque ,
                        $case_log
                        tbl_os.data_fechamento - tbl_os.data_abertura  as intervalo                     ,
                        (SELECT login FROM tbl_admin WHERE tbl_admin.admin = tbl_os.admin AND tbl_admin.fabrica = $login_fabrica) AS admin,
                        tbl_familia.descricao       as familia_descr,
                        tbl_familia.familia         as familia_id,
                        tbl_familia.codigo_familia  as familia_cod
            FROM        tbl_extrato
            LEFT JOIN tbl_extrato_pagamento ON  tbl_extrato_pagamento.extrato  = tbl_extrato.extrato
            LEFT JOIN tbl_os_extra          ON  tbl_os_extra.extrato           = tbl_extrato.extrato
            LEFT JOIN tbl_os                ON  tbl_os.os                      = tbl_os_extra.os
            $join_log
            LEFT JOIN      tbl_produto           ON  tbl_produto.produto            = tbl_os.produto
            JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_extrato.fabrica AND tbl_fabrica.fabrica = $login_fabrica
            JOIN      tbl_posto             ON  tbl_posto.posto                = tbl_extrato.posto
            JOIN      tbl_posto_fabrica     ON  tbl_posto.posto                = tbl_posto_fabrica.posto
                                            AND tbl_posto_fabrica.fabrica      = $login_fabrica
            LEFT JOIN tbl_familia           ON  tbl_produto.familia            = tbl_familia.familia
                                            AND tbl_familia.fabrica            = $login_fabrica
            WHERE       tbl_extrato.fabrica = $login_fabrica
            AND         tbl_extrato.extrato = $extrato
            ORDER BY    tbl_os_extra.os_reincidente,lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')               ASC,
                        replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";

            $res = pg_query($con,$sql);
            $count = pg_num_rows($res);

            $data = date("d-m-Y-H:i");

            $fileName = "relatorio_extrato_consulta-{$data}.csv";

            $file = fopen("/tmp/{$fileName}", "w");
            //head
            fwrite($file, "OS;Série;Abertura;Fechamento;Consumidor;Revenda;NF Entrada;NF Saída;Ref. Prod;M.O.;Admin;\n");

            for ($i = 0; $i < $count; $i++) {
                $os                 = trim(pg_fetch_result ($res,$i,'os'));
                $sua_os             = trim(pg_fetch_result ($res,$i,'sua_os'));
                $data               = trim(pg_fetch_result ($res,$i,'data'));
                $abertura           = trim(pg_fetch_result ($res,$i,'abertura'));
                $fechamento         = trim(pg_fetch_result ($res,$i,'fechamento'));
                $finalizada         = trim(pg_fetch_result ($res,$i,'finalizada'));
                $sua_os             = trim(pg_fetch_result ($res,$i,'sua_os'));
                $serie              = trim(pg_fetch_result ($res,$i,'serie'));
                $codigo_fabricacao  = trim(pg_fetch_result ($res,$i,'codigo_fabricacao'));
                $consumidor_nome    = trim(pg_fetch_result ($res,$i,'consumidor_nome'));
                $consumidor_fone    = trim(pg_fetch_result ($res,$i,'consumidor_fone'));
                $revenda_nome       = trim(pg_fetch_result ($res,$i,'revenda_nome'));
                $produto       = trim(pg_fetch_result ($res,$i,'produto'));
                $produto_nome       = trim(pg_fetch_result ($res,$i,'descricao'));
                $produto_referencia = trim(pg_fetch_result ($res,$i,'referencia'));
                $data_fechamento    = trim(pg_fetch_result ($res,$i,'data_fechamento'));
                $os_reincidente     = trim(pg_fetch_result ($res,$i,'os_reincidente'));
                $codigo_posto       = trim(pg_fetch_result ($res,$i,'codigo_posto'));
                $total_pecas        = trim(pg_fetch_result ($res,$i,'total_pecas'));
                $total_mo           = number_format(pg_fetch_result ($res,$i,'total_mo'),2,",",".");
                $qtde_km            = trim(pg_fetch_result ($res,$i,'qtde_km'));
                $valor_km           = trim(pg_fetch_result ($res,$i,'valor_km'));
                $total_km           = trim(pg_fetch_result ($res,$i,'qtde_km_calculada'));
                $pedagio            = trim(pg_fetch_result ($res,$i,'pedagio'));
                $taxa_visita        = trim(pg_fetch_result ($res,$i,'taxa_visita'));
                $cortesia           = trim(pg_fetch_result ($res,$i,'cortesia'));
                $peca_sem_preco     = pg_fetch_result ($res,$i,'peca_sem_preco') ;
                $os_sem_item        = pg_fetch_result ($res,$i,'os_sem_item') ;
                $motivo_atraso      = pg_fetch_result ($res,$i,'motivo_atraso') ;
                $motivo_atraso2     = pg_fetch_result ($res,$i,'motivo_atraso2') ;
                $obs_reincidencia   = pg_fetch_result ($res,$i,'obs_reincidencia') ;
                $nota_fiscal        = pg_fetch_result ($res,$i,'nota_fiscal') ;
                $data_nf            = pg_fetch_result ($res,$i,'data_nf') ;
                $nota_fiscal_saida  = pg_fetch_result ($res,$i,'nota_fiscal_saida') ;
                $observacao         = pg_fetch_result ($res,$i,'observacao') ;
                $consumidor_revenda = pg_fetch_result ($res,$i,'consumidor_revenda');
                $peca_sem_estoque   = pg_fetch_result ($res,$i,'peca_sem_estoque');
                $intervalo          = pg_fetch_result ($res,$i,'intervalo');
                $troca_garantia     = pg_fetch_result ($res,$i,'troca_garantia');
                $texto              = "";
                $admin              = pg_fetch_result ($res,$i,'admin');
                // HD 107642 (augusto)
                $familia_descr      = pg_fetch_result($res,$i,'familia_descr');
                $familia_cod        = pg_fetch_result($res,$i,'familia_cod');
                $familia_id         = pg_fetch_result($res,$i,'familia_id');
        $valor_adicional    = pg_fetch_result($res,$i,'valores_adicionais');

        $nota_fiscal = str_replace(array(";",",","'",".","/","*","+","-","!","@"),"",$nota_fiscal);
        $nota_fiscal_saida = str_replace(array(";",",","'",".","/","*","+","-","!","@"),"",$nota_fiscal_saida);

                fwrite($file, "$sua_os;$serie;$abertura;$fechamento;$consumidor_nome;$revenda_nome;$nota_fiscal;$nota_fiscal_saida;$produto_referencia;$total_mo;$admin;\n");

            }
            fclose($file);

            if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} xls/{$fileName}");

                echo "xls/{$fileName}";
            }
            exit;


    }

    if ($S3_sdk_OK) {
        include_once S3CLASS;
        $s3ve = new anexaS3('ve', (int) $login_fabrica);
        $S3_online = is_object($s3ve);
    }

    $s3 = new AmazonTC("os", $login_fabrica, true);
}
/*
*   Função para registrar comunicado e enviar e-mail para o posto
*   caso acumular OS no extrato.
*
*   @array_os      Array contendo cada OS como objeto.
*   @extrato       Extrato em que as Os's foram acumuladas
*
*   HD-959039
*/
function comunicar_acumula_os($array_os, $extrato){
    global $con, $login_fabrica, $login_fabrica_nome, $login_admin;

    require_once dirname(__FILE__) . '/../class/email/mailer/class.phpmailer.php';

    // Fábricas que utilizam a comunicação
    $fabricas = array(45);

    if(in_array($login_fabrica, $fabricas)) {

        $res = pg_query ($con,"BEGIN TRANSACTION");

        // Busca o nome do admin e o posto
        $sql = "SELECT tbl_admin.nome_completo AS admin_nome,
                       tbl_extrato.posto,
                       tbl_admin.email,
                       to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
                FROM tbl_extrato
                JOIN tbl_os_status ON tbl_os_status.extrato = tbl_extrato.extrato
                JOIN tbl_admin     ON tbl_admin.admin       = tbl_os_status.admin
                WHERE tbl_extrato.extrato = $extrato AND
                      tbl_admin.admin     = $login_admin
                GROUP BY tbl_extrato.posto,
                         tbl_admin.nome_completo,
                         tbl_extrato.data_geracao,
                         tbl_admin.email";

        $res = pg_query($con,$sql);

        // Recupera os dados
        $admin_nome         = pg_fetch_result($res, 0, admin_nome);
        $admin_email        = pg_fetch_result($res, 0, email);
        $posto              = pg_fetch_result($res, 0, posto);
        $data_geracao       = pg_fetch_result($res, 0, data_geracao);

        // Formata a mensagem e o título
        $mensagem  = traduz("Prezado Posto Autorizado,<br/><br/>");

        if(count($array_os) > 1) {
            $titulo = traduz("OSs acumuladas no extrato % da %", null, null, [$extrato, $login_fabrica_nome]);
            $mensagem .= traduz("As Oss a seguir não serão pagas no extrato % de %, devido terem sido acumuladas pelo admin %:<br/><br/>", null, null, [$extrato, $data_geracao, $admin_nome]);
        } else {
            $titulo = traduz("OS acumulada no extrato % da %", null, null, [$extrato, $login_fabrica_nome]);
            $mensagem .= traduz("A Os a seguir não será paga no extrato % de %, devido ter sido acumulada pelo admin %:<br/><br/>", null, null, [$extrato, $data_geracao, $admin_nome]);
        }

        foreach ($array_os as $os) {
            $sql = "SELECT tbl_os.sua_os,
                           tbl_os.consumidor_revenda
                    FROM tbl_os
                    WHERE tbl_os.os = $os->os";

            $res = pg_query($con,$sql);

            $sua_os             = pg_fetch_result($res, 0, sua_os);
            $consumidor_revenda = pg_fetch_result($res, 0, consumidor_revenda);

            $mensagem .= "Os: " . ($consumidor_revenda == 'R' ? $sua_os : $os->os) . "<br/>";
            $mensagem .= "Motivo: $os->obs<br/><br/>";
        }

        if(count($array_os) > 1) {
            $mensagem .= traduz("Favor regularizar as OSs para efetuarmos o pagamento no próximo extrato.<br/><br/>");
        } else {
            $mensagem .= traduz("Favor regularizar a OS para efetuarmos o pagamento no próximo extrato.<br/><br/>");
        }

        $mensagem .= traduz("Qualquer dúvida entrar em contato com a %.", null, null, [$login_fabrica_nome]);

        // Insere o comunicado para o posto
        $sql = "INSERT INTO tbl_comunicado (mensagem,
                                            fabrica,
                                            posto,
                                            obrigatorio_site,
                                            descricao,
                                            ativo)
                VALUES ('$mensagem',
                        $login_fabrica,
                        $posto,
                        true,
                        '$titulo',
                        true)";

        $res      = pg_query ($con,$sql);
        $msg_erro = pg_errormessage($con);

        // Se não houve erro, envia o e-mail
        if(!strlen($msg_erro)) {

            // Busca e-mail do posto
            $sql = "SELECT contato_email
                    FROM tbl_posto_fabrica
                    WHERE posto   = $posto and fabrica = $login_fabrica";

            $res      = pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con);

            // Se der erro
            if(strlen($msg_erro)) {
                $msg_erro = traduz("Erro ao buscar e-mail do posto.");
            } else if(pg_num_rows($res) != 1) {
                $msg_erro = traduz("Posto não encontrado");
            } else {

                $posto_email = pg_fetch_result($res, 0, 0);

                $mail = new PHPMailer();

                $mail->IsHTML(true);

                $mail->From     = $admin_email;
                $mail->FromName = $admin_email;
                $mail->Subject  = $titulo;
                $mail->Body     = $mensagem;

                $mail->AddAddress($posto_email);

                if (!$mail->Send()) {
                    $msg_erro = traduz('Erro ao enviar email: ').$mail->ErrorInfo;
                }
            }
        }

        if(!strlen($msg_erro)) {
            pg_query($con,"COMMIT TRANSACTION");
        } else {
            pg_query($con,"ROLLBACK TRANSACTION");
        }
    }
}

if (isset($_POST["btn_remove_nf"])) {
    $anexo = $_POST["anexo"];
    $nf_extrato = $_POST["extrato"];

    if (!empty($anexo)) {
        include_once S3CLASS;
        $s3_nf = new AmazonTC("extrato", (int) $login_fabrica);
        $s3_nf->deleteObject($anexo);

        if ($s3_nf) {
            $sql = "INSERT INTO tbl_extrato_status (extrato, data, obs, pendente, arquivo, fabrica, admin_conferiu) VALUES ($nf_extrato, now(), 'Anexo excluido pelo Admin', true, '$anexo', $login_fabrica, $login_admin) ";
            $res = pg_query($con, $sql);

            $sql = "UPDATE tbl_extrato SET nf_recebida = false WHERE extrato = $nf_extrato AND fabrica = $login_fabrica";
            $res = pg_query($con, $sql);

            if (pg_last_error()) {
                $retorno = "error";
            } else {
                $retorno = "ok";
            }
        } else {
            $retorno = "error";    
        }
    } else {
        $retorno = "error";
    }

    exit($retorno);
}

//gravação aprova nf - esab
if(isset($_POST['btn_acao_aprovacao_nf'])){
    
    $observacao_reprova = utf8_decode($_POST['observacao_reprova']);
    $extrato = $_POST['extrato'];
    $resposta = $_POST['resposta'];

    $sql = "SELECT posto FROM tbl_extrato WHERE fabrica = {$login_fabrica} AND extrato = {$extrato}
            ";
    $res = pg_query($con, $sql);
    $posto = pg_fetch_result($res, 0, "posto");

    if($resposta == 'nao'){
        $sql_extrato_status = "INSERT INTO tbl_extrato_status (fabrica, extrato, data, obs, advertencia, arquivo,admin_conferiu) 
                      VALUES ($login_fabrica, $extrato, now(), '".traduz('Aguardando Nota Fiscal do Posto')."', false, '$observacao_reprova',$login_admin) ";

        $sql_comunicado = "INSERT INTO tbl_comunicado
                        (
                            fabrica,
                            posto,
                            obrigatorio_site,
                            tipo,
                            ativo,
                            descricao,
                            mensagem
                        )
                        VALUES
                        (
                            {$login_fabrica},
                            {$posto},
                            true,
                            'Com. Unico Posto',
                            true,
                            '".traduz('Nota Fiscal Reprovada')."',
                            '".traduz('A nota fiscal do extrato % foi reprovada e o extrato está aguardando nota fiscal. <br /> <b>Justificativa: </b>', null, null, [$extrato])."$observacao_reprova '
                        )";
    }elseif($resposta == 'sim'){
        $sql_extrato_status = "INSERT INTO tbl_extrato_status (fabrica, extrato, data, obs, advertencia) 
                      VALUES ($login_fabrica, $extrato, now(), '".traduz('Aguardando Encerramento')."', false) ";

        $sql_comunicado = "INSERT INTO tbl_comunicado
                        (
                            fabrica,
                            posto,
                            obrigatorio_site,
                            tipo,
                            ativo,
                            descricao,
                            mensagem
                        )
                        VALUES
                        (
                            {$login_fabrica},
                            {$posto},
                            true,
                            'Com. Unico Posto',
                            true,
                            '".traduz('Nota Fiscal Aprovada')."',
                            '".traduz('A nota fiscal do extrato % foi aprovada e está aguardando encerramento.', null, null, [$extrato])."'
                        )";
    }
    $res_extrato_status = pg_query($con, $sql_extrato_status);

    $res_comunicado = pg_query($con, $sql_comunicado);

    if(strlen(pg_last_error($con))>0){
        $msg_erro .= traduz("Falha ao liberar o extrato %. <br /> ", null, null, [$liberar]);
    }
    exit;
}

/* Volta Extrato para Manutenção */
if(isset($_POST['extrato_manutencao'])){

    $extrato = $_POST['extrato_manutencao'];

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

    $sql = "UPDATE tbl_extrato SET aprovado = null, liberado = null WHERE extrato = $extrato";
    $res = pg_query($con, $sql);

    if(pg_affected_rows($res) > 0){
        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
        echo "Success";
    }else{
        echo "Error";
    }

    exit;

}

//HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
//           não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
//           de extrato avulso. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
//           SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
//           O array abaixo define quais fábricas estão enquadradas no processo novo
$fabricas_acerto_extrato = array(43, 45, 146, 148);

$posto = $_GET['posto'];
if (strlen($posto) == 0) $posto = $_POST['posto'];// HD 19580

$os      = $_GET['os'];
$op      = $_GET['op'];
$extrato = $_REQUEST['extrato'];
$mensagem_extrato = array();

if (strlen ($os) > 0 AND $op =='zerar' and strlen($extrato) > 0) {

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

    $res = pg_query ($con,"BEGIN TRANSACTION");
    $sql = " UPDATE tbl_os set mao_de_obra =0 where os=$os;
             SELECT fn_totaliza_extrato($login_fabrica,$extrato); ";
    $res = @pg_query($con,$sql);
    $msg_erro = pg_errormessage($con);

    if (strlen($msg_erro) == 0) {
        $res = pg_query($con,"COMMIT TRANSACTION");

        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
    } else {
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }

    // $res = (strlen($msg_erro) == 0) ? pg_query($con,"COMMIT TRANSACTION") : pg_query($con,"ROLLBACK TRANSACTION");

    $resposta = (strlen($msg_erro)>0) ? $msg_erro : traduz("OS % com mão-de-obra zerada!", null, null, [$os]);
    echo "ok|$resposta";exit;
}

if (trim($_POST['btn_obs']) == 'Enviar OBS' && strlen($_POST['obs_extrato']) > 0) {//HD 226679

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

    $res = pg_query($con,"BEGIN TRANSACTION");
    $sql = "UPDATE tbl_extrato_extra set obs = '".trim($_POST['obs_extrato'])."' where extrato = $extrato;";

    $res      = @pg_query($con,$sql);
    $msg_erro = pg_errormessage($con);

    if (strlen($msg_erro) == 0) {
        $res = pg_query($con,"COMMIT TRANSACTION");

        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
    } else {
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }

    //$res = (strlen($msg_erro) == 0) ? pg_query($con,"COMMIT TRANSACTION") : pg_query($con,"ROLLBACK TRANSACTION");

}

#HD 165932
if(isset($_POST['grava_extrato'])) {

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

    if(isset($_POST['nota_fiscal_mao_de_obra'])) {
        $sql = " UPDATE tbl_extrato_extra set
            nota_fiscal_mao_de_obra = '".$_POST['nota_fiscal_mao_de_obra']."'
            WHERE extrato = ".$_POST['grava_extrato'];
        $res = pg_query($con,$sql);

        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
        exit;
    }
    if(isset($_POST['emissao_mao_de_obra'])) {
        $sql = " UPDATE tbl_extrato_extra set
            emissao_mao_de_obra = '".$_POST['emissao_mao_de_obra']."'
            WHERE extrato = ".$_POST['grava_extrato'];
        $res = pg_query($con,$sql);

        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
        exit;
    }

    if(isset($_POST['valor_total_extrato'])) {
        $sql = " UPDATE tbl_extrato_extra set
            valor_total_extrato = '".str_replace(",",".",$_POST['valor_total_extrato'])."'
            WHERE extrato = ".$_POST['grava_extrato'];
        $res = pg_query($con,$sql);

        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
        exit;
    }
}

if (strlen ($os) > 0 AND $op =='mo2' and strlen($extrato) >0) {

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

    $res = pg_query ($con,"BEGIN TRANSACTION");

    $sql = "UPDATE tbl_os set
            mao_de_obra = 2,
            admin = $login_admin,
            obs='Valor da M.O. foi alterado por ' || tbl_admin.nome_completo || ' para $real . 2,00'
            from tbl_admin
            where tbl_admin.admin = $login_admin
            and tbl_os.os=$os;

            SELECT fn_totaliza_extrato($login_fabrica,$extrato); ";
    $res = @pg_query($con,$sql);
    $msg_erro = pg_errormessage($con);
    if (strlen($msg_erro) == 0) {
        $res = pg_query($con,"COMMIT TRANSACTION");

        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
    } else {
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }

    //$res = (strlen($msg_erro) == 0) ? pg_query($con,"COMMIT TRANSACTION") : pg_query($con,"ROLLBACK TRANSACTION");

    $resposta = (strlen($msg_erro)>0) ? $msg_erro : traduz("OS % com mão-de-obra . % . 2,00 (Troca de Produto)!", null, null, [$os, $real]);
    echo "ok|$resposta";exit;
}

$msg_aviso   = $_GET['msg_aviso'];
$ajax_debito = $_GET['ajax_debito'];

if( strlen($ajax_debito) == 0 ){ $ajax_debito = $_POST['ajax_debito']; }

if( strlen($ajax_debito) > 0 )
{
    $btn_acao = $_POST['btn_acao'];

    if( $ajax_debito=="true" )
    {
        $os  = $_GET['os'];
        $sql = "SELECT  tbl_os_extra.extrato,
                        tbl_os.os ,
                        tbl_os.mao_de_obra,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_posto.nome,
                        tbl_produto.referencia,
                        tbl_produto.descricao,
                        (select tbl_os_status.observacao from tbl_os_status where tbl_os_status.os = tbl_os.os order by os_status desc limit 1) as observacao
                FROM tbl_os_extra
                JOIN tbl_os on tbl_os.os = tbl_os_extra.os
                JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = $login_fabrica
                JOIN tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto
                JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
                LEFT JOIN tbl_os_status on tbl_os_status.os = tbl_os.os and tbl_os_status.extrato = tbl_os_extra.extrato
                where tbl_os.fabrica = $login_fabrica
                and tbl_os_extra.os = $os";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res)>0){
            $extrato = pg_fetch_result($res,0,extrato);
            $os      = pg_fetch_result($res,0,os);
            $mao_de_obra  = pg_fetch_result($res,0,mao_de_obra);
            $codigo_posto = pg_fetch_result($res,0,codigo_posto);
            $referencia   = pg_fetch_result($res,0,referencia);
            $descricao    = pg_fetch_result($res,0,descricao);
            $nome_posto   = pg_fetch_result($res,0,nome);
            $observacao   = pg_fetch_result($res,0,observacao);
        }
        echo "<table border='0' cellpadding='4' cellspacing='1' width='700' align='center' style='font-family: verdana; font-size: 10px'>";
            echo "<tr>";
            echo "<td width='50'>Extrato:</td><td colspan='3'> <B>$extrato</B></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td>Posto: </td><td colspan='3'><B>$codigo_posto - $nome_posto</B> </td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td>OS: </td><td><B>$os</b></td>";
            echo "<td >Mão-de-obra: </td><td width='250'><B> ". $real . number_format($mao_de_obra,2,",",".") . "</b></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td>Produto: </td><td colspan='3'><B>$referencia - $descricao</B> </td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td>Observação: </td><td colspan='3'><B><font color='#AF1807'>$observacao</font></B> </td>";
            echo "</tr>";
        echo "</table>";
        echo "<form name='frm_acerto' method='post' action='$PHP_SELF'>";
        echo "<input type='hidden' name='ajax_debito' value='cadastro'>";
        echo "<table border='1' cellpadding='4' cellspacing='1' width='90%' align='center' style='font-family: verdana; font-size: 10px'>";
            echo "<tr>";
            echo "<td colspan='2'>Para alterar o valor da mão-de-obra da OS $os por favor insira os dados abaixo:</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td><B>Valor Mão-de-obra:" . $real . "</B> <input type='text' name='mao_de_obra' size='5' maxlength='5' value='" . number_format($mao_de_obra,2,",",".") . "' class='frm'></td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td colspan='2' align='center'><B>Observação: </B><BR><TEXTAREA NAME='obs_acerto' ROWS='5' COLS='50'  class='frm'></TEXTAREA>";
            echo "<input type='hidden' name='extrato' value='$extrato'>";
            echo "<input type='hidden' name='os' value='$os'>";
            echo "<input type='hidden' name='btn_acao' value=''>";
            echo "<BR><img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_acerto.btn_acao.value == '' ) { document.frm_acerto.btn_acao.value='gravar' ; document.frm_acerto.submit() } else { alert ('Aguarde ') }\" ALT=\"Gravar itens da Ordem de Serviço\" border='0' style=\"cursor:pointer;\">";
            echo "</td>";
            echo "</tr>";
        echo "</table>";
        echo "</form>";
    }

    if( $btn_acao == "gravar" )
    {
        $os          = $_POST['os'];
        $extrato     = $_POST['extrato'];
        $obs_acerto  = $_POST['obs_acerto'];
        if( strlen($obs_acerto) == 0 ){ $msg_erro = "Insira o comentário"; }
        $mao_de_obra = trim($_POST['mao_de_obra']);
        if( strlen($mao_de_obra) == 0 ){ $msg_erro = "Insira o valor da mão-de-obra"; }
        $mao_de_obra = "'".$mao_de_obra."'";
        $mao_de_obra = fnc_limpa_moeda($mao_de_obra);

        $auditorLog = new AuditorLog();
        $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

        if( strlen($msg_erro) == 0 ){
            $res = pg_query ($con,"BEGIN TRANSACTION");
            $sql = "INSERT into tbl_os_status(
                            os         ,
                            status_os  ,
                            data       ,
                            observacao ,
                            extrato    ,
                            admin
                        )values(
                            $os,
                            90,
                            current_timestamp,
                            '$obs_acerto',
                            $extrato,
                            $login_admin
                            );";
            $res = pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

        if(strlen($msg_erro)==0){
            $sql = "UPDATE tbl_os set mao_de_obra = $mao_de_obra
                    WHERE  os = $os and fabrica = $login_fabrica;";
            $res = pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

        if(strlen($msg_erro)==0){
            #HD15716
            if( in_array($login_fabrica, array(11,172)) ){
                $sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
                $res = pg_query($con,$sql);
                $msg_aviso = traduz("Foi agendado o recalculo do extrato % para esta noite!<br />", null, null, [$extrato]);
            }else{
                $sql = "select fn_calcula_extrato($login_fabrica,$extrato)";
                $res = pg_query($con,$sql);
                $msg_erro = pg_errormessage($con);
            }
        }

        if (strlen ($msg_erro) == 0) {
            $res = pg_query ($con,"COMMIT TRANSACTION");

            $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);

            echo traduz("<center>Alteração efetuada com sucesso!!</center>");
        }else{

            $res = pg_query ($con,"ROLLBACK TRANSACTION");
            echo traduz("<center>Ocorreu o seguinte erro: %</center>", null, null, [$msg_erro]);
        }

        /* INSERT into tbl_os_status( os , status_os , data , observacao , extrato , admin )values( 3773040, 90, current_timestamp, 'mao de obra estará zero pq eu quero', 195118, 158 );
        UPDATE tbl_os set mao_de_obra = 0.00 where os = 3773040 and fabrica = 6;
        select fn_calcula_extrato(195118,6);
        Alteração efetuada com sucesso!

        telecontrol=> \d tbl_os_status;
                                                Table "public.tbl_os_status"
           Column   |            Type             |                            Modifiers
        ------------+-----------------------------+-----------------------------------------------------------------
         os_status  | integer                     | not null default nextval(('tbl_os_status_seq'::text)::regclass)
         os         | integer                     |
         status_os  | integer                     | not null
         data       | timestamp without time zone | default ('now'::text)::timestamp(6) with time zone
         observacao | text                        | not null
         extrato    | integer                     |
         os_sedex   | integer                     |
         admin      | integer                     |
        90 */
    }
    exit;
}

$ajax = $_GET["ajax"];

if ($login_fabrica == 30) {
    if ($ajax == "mostrarpecas") {
        $os  = $_GET['os'];

        $sql = "SELECT
                    tbl_peca.referencia,
                    tbl_peca.descricao AS peca_descricao,
                    tbl_defeito.descricao AS defeito_descricao,
                    tbl_servico_realizado.descricao AS servico_realizado_descricao,
                    tbl_os_item.os_item,
                    tbl_os_item.servico_realizado,
                    tbl_os_item.qtde,
                    tbl_os_item.preco,
                    tbl_os_extra.extrato,
                    tbl_extrato.aprovado
                FROM
                    tbl_os_extra
                    JOIN tbl_os_produto ON tbl_os_extra.os=tbl_os_produto.os
                    JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
                    JOIN tbl_peca ON tbl_os_item.peca=tbl_peca.peca
                    JOIN tbl_defeito ON tbl_os_item.defeito=tbl_defeito.defeito
                    JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado=tbl_servico_realizado.servico_realizado
                    JOIN tbl_extrato ON tbl_os_extra.extrato=tbl_extrato.extrato
                WHERE
                tbl_os_produto.os=$os";

        $res = pg_query($con, $sql);
        $n   = pg_num_rows($res);

        if ($n > 0) {
            $sql = "SELECT servico_realizado, descricao FROM tbl_servico_realizado WHERE fabrica=$login_fabrica AND ativo";
            $res_servicos = pg_query($con, $sql);

            echo "
                <table>
                <tr class='titulo_coluna'>
                <td width='80'>REFERÊNCIA</td>
                <td width='400'>DESCRIÇÃO</td>
                <td width='100'>DEFEITO</td>
                <td width='250'>SERVIÇO</td>
                <td width='40'>QTDE</td>
                <td width='50'>VALOR</td>
                </tr>
            ";

            for ($i = 0; $i < $n; $i++) {
                extract(pg_fetch_array($res));

                echo "
                    <tr>
                    <td>$referencia</td>
                    <td>$peca_descricao</td>
                    <td>$defeito_descricao</td>
                    <td>";

                if (strlen($aprovado) == 0) {
                    echo "
                    <input type='hidden' id='servico_inicial$os_item' value='$servico_realizado'>
                    <select id='servico$os_item' onchange='alteraServico($os, $os_item, $(this).val());'>";

                    for ($j = 0; $j < pg_num_rows($res_servicos); $j++) {
                        $codigo = pg_result($res_servicos, $j, 'servico_realizado');
                        $descricao = pg_result($res_servicos, $j, 'descricao');
                        $selected = $servico_realizado == $codigo ? "selected" : "";

                        echo "<option $selected value='$codigo'>$descricao</option>";
                    }

                    echo "</select>";
                } else {
                    echo $servico_realizado_descricao;
                }
                echo "
                </td>
                <td>$qtde</td>
                <td>$preco</td>
                </tr>
                ";
            }
            echo "
            </table>";
        }
        die;
    }

    if ($ajax == "alteraservico") {
        $os                = $_GET["os"];
        $os_item           = $_GET["os_item"];
        $servico_realizado = $_GET["servico_realizado"];

        $auditorLog = new AuditorLog();
        $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

        $sql = "BEGIN";
        @$res = pg_query($con, $sql);
        $msg_erro = pg_errormessage($con);

        if (strlen($msg_erro) == 0) {
            $sql = "SELECT fn_troca_servico_realizado_os_item($login_fabrica, $os, $os_item, $servico_realizado);";
            @$res = pg_query($con, $sql);
            $msg_erro = pg_errormessage($con);
        }

        if (strlen($msg_erro) == 0) {
            $sql = "SELECT extrato, aprovado, liberado FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE os=$os AND extrato IS NOT NULL";
            @$res = pg_query($con, $sql);
            $msg_erro = pg_errormessage($con);

            if (strlen($msg_erro) == 0 && pg_num_rows($res) == 1) {
                extract(pg_fetch_array($res));
                if (strlen($aprovado) == 0) {
                    $sql = "UPDATE tbl_extrato SET recalculo_pendente=TRUE WHERE extrato=$extrato";
                    @$res = pg_query($con, $sql);
                    $msg_erro = pg_errormessage($con);
                }
                else {
                    $msg_erro = traduz("Extrato já aprovado, não pode ser modificado");
                }
            }
        }

        if (strlen($msg_erro) == 0) {
            $sql = "COMMIT";
            $res = pg_query($con, $sql);
            $msg_erro = pg_errormessage($con);
            if (strlen($msg_erro) == 0) {
                $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
                echo "ok";
            }
            else {
                echo traduz("Falha na solicitação: %", null, null, [$msg_erro]);
            }
        }
        else {
            $sql = "ROLLBACK";
            $res = pg_query($con, $sql);
            echo traduz("Falha na solicitação: %", null, null, [$msg_erro]);
        }

        die;
    }
}

if ( in_array($login_fabrica, array(11,172)) ) {
    $extrato = $_GET['extrato'];
    $os      = $_GET['os'];
    $zerarmo = $_GET['zerarmo'];

    if( strlen($os) > 0 AND $zerarmo=='t')
    {
        $auditorLog = new AuditorLog();
        $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

        $auditorLogOS = new AuditorLog();
        $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$extrato );

        $res = pg_query ($con,"BEGIN TRANSACTION");
        $sql = "INSERT into tbl_os_status(
                        os         ,
                        status_os  ,
                        data       ,
                        observacao ,
                        extrato    ,
                        admin
                    )values(
                        $os,
                            90,
                            current_timestamp,
                            '".traduz('Mão de Obra zerada pelo admin na aprovação do extrato.')."',
                            $extrato,
                            $login_admin
                        );";
        $res = pg_query($con,$sql);
        $msg_erro = pg_errormessage($con);

        $sqlZ = "UPDATE tbl_os_extra SET
                admin_paga_mao_de_obra = 'f'
                WHERE os      = $os
                AND   extrato = $extrato";
        $resZ = pg_query($con,$sqlZ);
        $msg_erro = pg_errormessage($con);

        $sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
        $res = pg_query($con,$sql);
        $msg_aviso = traduz("Foi agendado o recalculo do extrato % para esta noite!<br>", null, null, [$extrato]);

        if (strlen ($msg_erro) == 0) {
            $res = pg_query ($con,"COMMIT TRANSACTION");

            $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
            $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato);

        }else{
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
        }
    }
}

if ($_GET["acao"] == apagar && $_GET["xlancamento"]) {
    //HD 238905: Ao excluir um extrato, mandar recalcular
    $msg_erro = array();

    $sql = "BEGIN TRANSACTION";
    $res = pg_query($con, $sql);
    $msg_erro[] = pg_errormessage($con);

    $sql = "DELETE FROM
            tbl_extrato_lancamento
            WHERE
            extrato_lancamento = " . $_GET["xlancamento"] . "
            AND fabrica = $login_fabrica
            AND extrato = " . $_GET["extrato"] . "";
    $res = pg_query($con, $sql);
    $msg_erro[] = pg_errormessage($con);

    $sql = "SELECT fn_calcula_extrato($login_fabrica, " . $_GET["extrato"] . ");";
    $res = pg_query($con, $sql);
    $msg_erro[] = pg_errormessage($con);

    $msg_erro = implode("", $msg_erro);

    if ($msg_erro) {
        $sql = "ROLLBACK TRANSACTION";
        $res = pg_query($con, $sql);
    }
    else {
        $sql = "COMMIT TRANSACTION";
        $res = pg_query($con, $sql);
    }

    header("location:$PHP_SELF?extrato=" . $_GET["extrato"]);
    die;
}

if (strlen($_POST["btn_acao"]) == 0) {
    $data_inicial = $_GET["data_inicial"];
    $data_final   = $_GET["data_final"];
    $cnpj         = $_GET["cnpj"];
    $razao        = $_GET["razao"];
}

if (strlen($_POST["btn_acao"]) == 0 && strlen($_POST["select_acao"]) == 0) {
    setcookie("link", $_SERVER['REQUEST_URI'], time()+60*60*24); # Expira em 1 dia
}

$ajax = $_GET['ajax'];

if (strlen($ajax) > 0) {
    $extrato    = $_GET['extrato'];
    $observacao = $_GET['observacao'];

    $sql = "BEGIN TRANSACTION";
    $res = pg_query($con,$sql);

    $sql = "SELECT os
            FROM tbl_os_extra
            WHERE tbl_os_extra.extrato = $extrato";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        for ($i=0; pg_num_rows($res)>$i; $i++) {
            $os   = pg_fetch_result($res, $i, os);

            $xsql = "SELECT tbl_os.posto       ,
                            tbl_os_item.qtde   ,
                            tbl_os.os          ,
                            tbl_os_item.os_item,
                            tbl_os_item.peca
                    FROM tbl_os
                    JOIN tbl_os_produto using(os)
                    JOIN tbl_os_item using(os_produto)
                    WHERE tbl_os.fabrica = $login_fabrica
                    AND   tbl_os.os      = $os
                    AND   tbl_os_item.peca_sem_estoque IS TRUE";
            $xres = pg_query($con,$xsql);
            $msg_erro .= pg_errormessage($con);

            if (pg_num_rows($res)>0) {
                for ($x=0; pg_num_rows($xres)>$x; $x++) {
                    $posto   = pg_fetch_result($xres,$x,posto);
                    $qtde    = pg_fetch_result($xres,$x,qtde);
                    $os      = pg_fetch_result($xres,$x,os);
                    $os_item = pg_fetch_result($xres,$x,os_item);
                    $peca    = pg_fetch_result($xres,$x,peca);

                    $ysql = "INSERT INTO tbl_estoque_posto_movimento(
                                        fabrica      ,
                                        posto        ,
                                        os           ,
                                        peca         ,
                                        qtde_entrada   ,
                                        data,
                                        os_item,
                                        obs,
                                        admin
                                    )VALUES(
                                        $login_fabrica,
                                        $posto        ,
                                        $os           ,
                                        $peca         ,
                                        $qtde         ,
                                        current_date  ,
                                        $os_item       ,
                                        'Automático: $observacao',
                                        $login_admin
                                )";
                    $yres = pg_query($con,$ysql);
                    $msg_erro .= pg_errormessage($con);

                    if (strlen($msg_erro)==0) {
                        $ysql = "SELECT peca
                                FROM tbl_estoque_posto
                                WHERE peca = $peca
                                AND posto = $posto
                                AND fabrica = $login_fabrica;";
                        $yres = pg_query($con,$ysql);

                        if (pg_num_rows($res)>0) {
                            $ysql = "UPDATE tbl_estoque_posto set
                                    qtde = qtde + $qtde
                                    WHERE peca  = $peca
                                    AND posto   = $posto
                                    AND fabrica = $login_fabrica;";
                            $yres = pg_query($con,$ysql);
                            $msg_erro .= pg_errormessage($con);
                        } else {
                            $ysql = "INSERT into tbl_estoque_posto(fabrica, posto, peca, qtde)values($login_fabrica,$posto,$peca,$qtde)";
                            $yres = pg_query($con,$ysql);
                            $msg_erro .= pg_errormessage($con);
                        }
                    }
                }
            }
        }
    }

    if (strlen($msg_erro) == 0) {
        $res = pg_query($con,"COMMIT TRANSACTION");
        echo traduz("Peça(s) aceita(s) com sucesso!");
    } else {
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        echo traduz("Erro no processo: %", null, null, [$msg_erro]);
    }
    exit;
}

$ajaxx = $_GET['ajaxx'];

if( $ajaxx == "verifica_serie" )
{
    $os_serie = $_GET['os'];
    $extrato  = $_GET['extrato'];
    $serie    = $_GET['serie'];

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );
    $auditorLogOS = new AuditorLog();
    $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$extrato );
    if( empty($serie) )
    {
        $sql = "UPDATE tbl_os_extra SET baixada = CURRENT_TIMESTAMP WHERE os = $os_serie";
        $res = pg_query($con,$sql);
        $sql = "INSERT INTO tbl_extrato_conferencia(extrato,data_conferencia,admin) VALUES($extrato,CURRENT_TIMESTAMP,$login_admin)";
        $res = pg_query($con,$sql);
        if (strlen(pg_last_error($con)) > 0) {
            echo "no";
        } else {
            $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
            $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato);
            echo "ok";
        }
    } else {
        if ((int)$os_serie) {
            $sql = "SELECT serie FROM tbl_os WHERE os = $os_serie AND fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            if ( pg_numrows($res) > 0 )
            {
                $serie_os       = pg_result($res,0,'serie');
                $verifica_serie = strpos($serie,$serie_os);
                if ($verifica_serie === false) {
                    echo "no";
                } else {
                    $sql = "UPDATE tbl_os_extra SET baixada = CURRENT_TIMESTAMP WHERE os = $os_serie";
                    $res = pg_query($con,$sql);
                    $sql = "INSERT INTO tbl_extrato_conferencia(extrato,data_conferencia,admin) VALUES($extrato,CURRENT_TIMESTAMP,$login_admin)";
                    $res = pg_query($con,$sql);
                    if (strlen(pg_last_error($con)) > 0) {
                        echo "no";
                    } else {
                        $sqlOsNaoBaixada = "SELECT os FROM tbl_os_extra WHERE extrato = $extrato AND baixada IS NULL";
                        $resOsNaoBaixada = pg_query($con,$sqlOsNaoBaixada);
                        $baixar_extrato = (pg_numrows($resOsNaoBaixada) > 0) ? 'nao' : 'sim';
                        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
                        $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato);
                        echo "ok|$baixar_extrato";
                    }
                }
            } else {
                echo "no";
            }
        }
    }
    exit;
}

if( strlen($_POST["btn_acao"])        > 0 ) $btn_acao        = trim(strtolower($_POST["btn_acao"]));
if( strlen($_POST["adiciona_sua_os"]) > 0 ) $adiciona_sua_os = trim(strtolower($_POST["adiciona_sua_os"]));
if( strlen($_GET["adiciona_sua_os"])  > 0 ) $adiciona_sua_os = trim(strtolower($_GET["adiciona_sua_os"]));
if( strlen($_POST["select_acao"])     > 0 ) $select_acao     = strtoupper($_POST["select_acao"]);



$xlancamento = $_GET['xlancamento'];
$acao        = $_GET['acao'];
$extrato     = $_GET['extrato'];

if(strlen($xlancamento) > 0 && strlen($acao) > 0 && strlen($extrato) > 0 && in_array($login_fabrica, array(6,45))) { //hd 9482

    $sql = "SELECT extrato_lancamento
            from tbl_extrato_lancamento
            where extrato = $extrato
            and extrato_lancamento = $xlancamento";
    $res = pg_query($con,$sql);

    if( pg_num_rows($res)>0 ){

        $sql = "DELETE from tbl_extrato_extra_item
                where extrato = $extrato
                and extrato_lancamento = $xlancamento";
        $res = pg_query($con,$sql);

        $sql = "DELETE from tbl_extrato_lancamento
                where fabrica=$login_fabrica
                and extrato = $extrato
                and extrato_lancamento = $xlancamento";
        $res = pg_query($con,$sql);

        //HD 6887
        if( $login_fabrica==6 ){
            $sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
            $res = pg_query($con,$sql);
        }else{ //hd 9482
            #$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
            #$res = pg_query($con,$sql);
            #$total_os_extrato = pg_fetch_result($res,0,0);

            #HD15716
             if( in_array($login_fabrica, array(11,172)) ){
                $auditorLog = new AuditorLog();
                $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

                $sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
                $res = pg_query($con,$sql);
                $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);

                $msg_aviso = traduz("Foi agendado o recalculo do extrato % para esta noite!<br>", null, null, [$extrato]);
            }else{
                $sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
                $res = pg_query($con,$sql);
            }
        }
    }
}

$lancamento = $_GET['lancamento'];

if( strlen($lancamento) > 0 ){
    $select_acao = 'RECUSAR';
}

if( strlen($_POST["extrato"]) > 0 ) $extrato = trim($_POST["extrato"]);
if( strlen($_GET["extrato"])  > 0 ) $extrato = trim($_GET["extrato"]);

$msg_erro = "";

if( $login_fabrica == 30 )
{
    if( $btn_acao == "recalculo" )
    {
        $sql = "SELECT total, recalculo_pendente FROM tbl_extrato WHERE extrato=$extrato AND aprovado IS NULL";
        @$res = pg_query($con, $sql);
        $msg_erro = pg_errormessage($con);

        if (strlen($msg_erro) == 0) {

            $auditorLog = new AuditorLog();
            $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

            $sql = "BEGIN";
            @$res_trans = pg_query($con, $sql);
            $msg_erro = pg_errormessage($con);
        }

        if (strlen($msg_erro) == 0) {
            $total_anterior = pg_result($res, 0, "total");
            $recalculo_pendente = pg_result($res, 0, "recalculo_pendente");
            if ($recalculo_pendente == 't') {
                $sql = "SELECT fn_calcula_extrato($login_fabrica, $extrato)";
                @$res = pg_query($con, $sql);
                $msg_erro = pg_errormessage($con);
                $sql = "UPDATE tbl_extrato SET recalculo_pendente = FALSE WHERE extrato=$extrato";
                @$res = pg_query($con, $sql);
                $msg_erro = pg_errormessage($con);
            }
            else {
                $msg_erro = traduz("O extrato não está pendente de recálculo");
            }
        }
        else {
            $msg_erro = traduz("Extrato já liberado, não pode ser recalculado");
        }

        if (strlen($msg_erro) > 0) {
            $sql = "ROLLBACK";
            @$res = pg_query($con, $sql);
            $msg_erro = traduz("Falha ao recalcular extrato");
        }
        else {
            $sql = "COMMIT";
            @$res = pg_query($con, $sql);
            $msg_erro = pg_errormessage($con);

            if (strlen($msg_erro) == 0) {
                $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);

                $mensagem_extrato[] = "Extrato recalculado com sucesso (total anterior: $total_anterior)";
            }
            else {
                $msg_erro = traduz("Falha ao recalcular extrato");
            }
        }
    }
}

if ($btn_acao == 'pedido'){
    header ("Location: relatorio_pedido_peca_kit.php?extrato=$extrato");
    exit;
}
if($btn_acao == 'gravar_pagamento'){

    try{
    if(jaBaixado($extrato)){
            $ja_baixado = true;
        throw new Exception("Pagamento Já Baixado");
    }else{
            if( strlen($_POST["extrato_pagamento"]) > 0 ) $extrato_pagamento = trim($_POST["extrato_pagamento"]);
            if( strlen($_GET["extrato_pagamento"])  > 0 ) $extrato_pagamento = trim($_GET["extrato_pagamento"]);

            $valor_total = trim($_POST["valor_total"]);

            if( strlen($valor_total ) > 0) {
        $xvalor_total = str_replace(".","",$valor_total);
        $xvalor_total = str_replace(",",".",$xvalor_total);
            }else{
                $xvalor_total = 'NULL';
        }


            if(strlen($acrescimo) > 0){
        $xacrescimo = str_replace(".","",$acrescimo);
        $xacrescimo = str_replace(",",".",$xacrescimo);
        }else{
        $xacrescimo = 'NULL';
        }

            $desconto        = trim($_POST["desconto"]) ;

        if(strlen($desconto) > 0) {
        $xdesconto = str_replace(".","",$desconto);
        $xdesconto = str_replace(",",".",$xdesconto);
        }else{

        $xdesconto = 'NULL';
        }

            $valor_liquido   = trim($_POST["valor_liquido"]) ;
            $xvalor_liquido  = (strlen($valor_liquido) > 0) ? "'".str_replace(",",".",$valor_liquido)."'" : 'NULL';
        if(strlen($valor_liquido) > 0) {
        $xvalor_liquido = str_replace(".","",$valor_liquido);
        $xvalor_liquido = str_replace(",",".",$xvalor_liquido);
        }else{

        $xvalor_liquido = 'NULL';
        }

            $nf_autorizacao  = trim($_POST["nf_autorizacao"]) ;
            $xnf_autorizacao = (strlen($nf_autorizacao) > 0) ? "'$nf_autorizacao'" : 'NULL';

            $autorizacao_pagto = trim($_POST["autorizacao_pagto"]);

            $data_recebimento_nf  = trim($_POST["data_recebimento_nf"]) ;
            $xdata_recebimento_nf = (strlen($data_recebimento_nf) > 0) ? "'$data_recebimento_nf'" : 'NULL';

            if( strlen($_POST["data_pagamento"]) > 0 ){
                $data_pagamento  = trim($_POST["data_pagamento"]) ;

                $xdata_pagamento = str_replace("/","",$data_pagamento);
                $xdata_pagamento = str_replace("-","",$xdata_pagamento);
                $xdata_pagamento = str_replace(".","",$xdata_pagamento);
                $xdata_pagamento = str_replace(" ","",$xdata_pagamento);

                $dia = trim(substr($xdata_pagamento,0,2));
                $mes = trim(substr($xdata_pagamento,2,2));
                $ano = trim(substr($xdata_pagamento,4,4));
                if( strlen($ano) == 2 ) $ano = "20" . $ano;

                $verifica = checkdate($mes, $dia, $ano);

                if( $verifica == 1 ){
                    $xdata_pagamento = $ano . "-" . $mes . "-" . $dia ;
                    $xdata_pagamento = "'" . $xdata_pagamento . "'";
                }else{
                    throw new Exception(traduz("A Data de Pagamento não está em um formato válido"));
                }
            }else if($login_fabrica==45){
                $xdata_pagamento = "NULL";
            }else{//hd 26972
                $xdata_pagamento = "NULL";
                throw new Exception(traduz("Por favor, digitar a Data de Pagamento"));
            }

            if( strlen($_POST["data_vencimento"]) > 0 ){
                $data_vencimento  = trim($_POST["data_vencimento"]) ;
                $xdata_vencimento = str_replace("/","",$data_vencimento);
                $xdata_vencimento = str_replace("-","",$xdata_vencimento);
                $xdata_vencimento = str_replace(".","",$xdata_vencimento);
                $xdata_vencimento = str_replace(" ","",$xdata_vencimento);

                $dia = trim(substr($xdata_vencimento,0,2));
                $mes = trim(substr($xdata_vencimento,2,2));
                $ano = trim(substr($xdata_vencimento,4,4));
                if( strlen($ano) == 2 ) $ano = "20" . $ano;

                $verifica = checkdate($mes, $dia, $ano);

                if( $verifica == 1 ){
                    $xdata_vencimento = $ano . "-" . $mes . "-" . $dia ;
                    $xdata_vencimento = "'" . $xdata_vencimento . "'";
                }else{
                    throw new Exception(traduz("<br>A Data de Vencimento não está em um formato válido<br>"));
                }
            }else{
                $xdata_vencimento = "NULL";
            }

            if (strlen($_POST["obs"]) > 0) {
                $obs = trim($_POST["obs"]) ;
                $xobs = "'" . $obs . "'";
            }else{
                $xobs = "NULL";
            }
            $arrDadosPagamento["extrato"]              = $extrato;
            $arrDadosPagamento["valor_total"]         = $xvalor_total;
            $arrDadosPagamento["acrescimo"]           = $xacrescimo   ;
            $arrDadosPagamento["desconto"]            = $xdesconto     ;
            $arrDadosPagamento["valor_liquido"]       = $xvalor_liquido ;
            $arrDadosPagamento["data_vencimento"]     = $xdata_vencimento;
            $arrDadosPagamento["nf_autorizacao"]      = $xnf_autorizacao;
            $arrDadosPagamento["data_pagamento"]      = $xdata_pagamento;
            $arrDadosPagamento["autorizacao_pagto"]   = $autorizacao_pagto;
            $arrDadosPagamento["data_recebimento_nf"] = $xdata_recebimento_nf;
            $arrDadosPagamento["obs"]                 = $xobs;

            cadastrarPagamento($arrDadosPagamento);

    }
    }catch(Exception $ex){
    $msg_erro = $ex->getMessage();
    }
}
if($btn_acao == 'baixar' && $login_fabrica == 134){
    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

    try{
        if(jaBaixado($extrato)){
            $ja_baixado = true;
            throw new Exception(traduz("Pagamento Já Baixado"));
        }

        if(getPagamentosLancados($extrato)==false){

            throw new Exception(traduz("Nenhum pagamento lançado"));

        }
        baixarPagamentos($extrato);
        pg_query($con, "COMMIT TRANSACTION");

        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
    }catch(Exception $ex){
        pg_query($con, "ROLLBACK TRANSACTION");
        $msg_erro = $ex->getMessage();
    }
}else if( $btn_acao == 'baixar'){

    if( strlen($_POST["extrato_pagamento"]) > 0 ) $extrato_pagamento = trim($_POST["extrato_pagamento"]);
    if( strlen($_GET["extrato_pagamento"])  > 0 ) $extrato_pagamento = trim($_GET["extrato_pagamento"]);

    $valor_total = trim($_POST["valor_total"]);
    if( strlen($valor_total ) > 0){
        $xvalor_total = str_replace(".","",$valor_total);
        $xvalor_total = "'".str_replace(",",".",$xvalor_total)."'";
    }else{
        $xvalor_total = 'NULL';
    }

    $acrescimo       = trim($_POST["acrescimo"]);
    if (strlen($acrescimo)>0) {
        //$xacrescimo      = (strlen($acrescimo) > 0) ? "'".str_replace(",",".",$acrescimo)."'" : 'NULL';
        $xacrescimo = str_replace(".","",$acrescimo);
        $xacrescimo = "'".str_replace(",",".",$xacrescimo)."'";
    }else{
        $xacrescimo = 'NULL';
    }


    $desconto        = trim($_POST["desconto"]) ;
    if (strlen($desconto)>0) {
        //$xdesconto       = (strlen($desconto) > 0) ? "'".str_replace(",",".",$desconto)."'" : 'NULL';
        $xdesconto = str_replace(".","",$desconto);
        $xdesconto = "'".str_replace(",",".",$xdesconto)."'";
    }else{
        $xdesconto = 'NULL';
    }

    $valor_liquido   = trim($_POST["valor_liquido"]) ;
    if (strlen($valor_liquido)>0) {
        //$xvalor_liquido  = (strlen($valor_liquido) > 0) ? "'".str_replace(",",".",$valor_liquido)."'" : 'NULL';
        $xvalor_liquido = str_replace(".","",$valor_liquido);
        $xvalor_liquido = "'".str_replace(",",".",$xvalor_liquido)."'";
    }else{
        $xvalor_liquido = 'NULL';
    }

    $nf_autorizacao  = trim($_POST["nf_autorizacao"]) ;
    $xnf_autorizacao = (strlen($nf_autorizacao) > 0) ? "'$nf_autorizacao'" : 'NULL';

    if($login_fabrica==45 AND strlen($nf_autorizacao)==0){
        $msg_erro = traduz("Digite a nota fiscal do pagamento");
    }

    $autorizacao_pagto = trim($_POST["autorizacao_pagto"]);

    //HERE
    if( strlen($autorizacao_pagto) > 0 ) $xautorizacao_pagto = "'$autorizacao_pagto'";
    else                              $xautorizacao_pagto = 'NULL';

    $data_recebimento_nf  = trim($_POST["data_recebimento_nf"]) ;
    $xdata_recebimento_nf = (strlen($data_recebimento_nf) > 0) ? "'$data_recebimento_nf'" : 'NULL';

    if( in_array($login_fabrica, array(50)) )
    {
        $data_recebimento_nf  = trim($_POST["data_recebimento_nf"]) ;
        $xdata_recebimento_nf = str_replace("/","",$data_recebimento_nf);
        $xdata_recebimento_nf = str_replace("-","",$xdata_recebimento_nf);
        $xdata_recebimento_nf = str_replace(".","",$xdata_recebimento_nf);
        $xdata_recebimento_nf = str_replace(" ","",$xdata_recebimento_nf);

        $dia = trim(substr($xdata_recebimento_nf,0,2));
        $mes = trim(substr($xdata_recebimento_nf,2,2));
        $ano = trim(substr($xdata_recebimento_nf,4,4));
        if( strlen($ano) == 2 ) $ano = "20" . $ano;

        $xdata_recebimento_nf = $ano . "-" . $mes . "-" . $dia ;
        $xdata_recebimento_nf = "'" . $xdata_recebimento_nf . "'";
    }

    if( in_array($login_fabrica, array(30)) )
    {
        if( strlen($_POST["data_recebimento_nf"]) > 0 )
        {
            $data_recebimento_nf  = trim($_POST["data_recebimento_nf"]) ;
            $xdata_recebimento_nf = str_replace("/","",$data_recebimento_nf);
            $xdata_recebimento_nf = str_replace("-","",$xdata_recebimento_nf);
            $xdata_recebimento_nf = str_replace(".","",$xdata_recebimento_nf);
            $xdata_recebimento_nf = str_replace(" ","",$xdata_recebimento_nf);

            $dia = trim(substr($xdata_recebimento_nf,0,2));
            $mes = trim(substr($xdata_recebimento_nf,2,2));
            $ano = trim(substr($xdata_recebimento_nf,4,4));
            if( strlen($ano) == 2 ) $ano = "20" . $ano;

            //-=============Verifica data=================-//
            $verifica = checkdate($mes, $dia, $ano);

            if( $verifica == 1 ){
                $xdata_recebimento_nf = $ano . "-" . $mes . "-" . $dia ;
                $xdata_recebimento_nf = "'" . $xdata_recebimento_nf . "'";
            }else{
                $msg_erro = traduz("A Data de Pagamento não está em um formato válido");
            }
        }else{
            $xdata_recebimento_nf = "NULL";
            //HD 9387 Paulo 10/12/2007
            $msg_erro .= traduz("Por favor, digitar a Data de Recebimento da Nota Fiscal!!!");
        }
    }

    if( strlen($_POST["data_pagamento"]) > 0 ){
        $data_pagamento  = trim($_POST["data_pagamento"]) ;

        $xdata_pagamento = str_replace("/","",$data_pagamento);
        $xdata_pagamento = str_replace("-","",$xdata_pagamento);
        $xdata_pagamento = str_replace(".","",$xdata_pagamento);
        $xdata_pagamento = str_replace(" ","",$xdata_pagamento);

        $dia = trim(substr($xdata_pagamento,0,2));
        $mes = trim(substr($xdata_pagamento,2,2));
        $ano = trim(substr($xdata_pagamento,4,4));
        if( strlen($ano) == 2 ) $ano = "20" . $ano;

        $verifica = checkdate($mes, $dia, $ano);

        if( $verifica == 1 ){
            $xdata_pagamento = $ano . "-" . $mes . "-" . $dia ;
            $xdata_pagamento = "'" . $xdata_pagamento . "'";
        }else{
            $msg_erro = traduz("A Data de Pagamento não está em um formato válido");
        }
    }else if($login_fabrica==45){
        $xdata_pagamento = "NULL";
    }else{//hd 26972
        $xdata_pagamento = "NULL";
        $msg_erro .= traduz("Por favor, digitar a Data de Pagamento!!!");
    }

    if( strlen($_POST["data_vencimento"]) > 0 ){
        $data_vencimento  = trim($_POST["data_vencimento"]) ;
        $xdata_vencimento = str_replace("/","",$data_vencimento);
        $xdata_vencimento = str_replace("-","",$xdata_vencimento);
        $xdata_vencimento = str_replace(".","",$xdata_vencimento);
        $xdata_vencimento = str_replace(" ","",$xdata_vencimento);

        $dia = trim(substr($xdata_vencimento,0,2));
        $mes = trim(substr($xdata_vencimento,2,2));
        $ano = trim(substr($xdata_vencimento,4,4));
        if( strlen($ano) == 2 ) $ano = "20" . $ano;

        $verifica = checkdate($mes, $dia, $ano);

        if( $verifica == 1 ){
            $xdata_vencimento = $ano . "-" . $mes . "-" . $dia ;
            $xdata_vencimento = "'" . $xdata_vencimento . "'";
        }else{
            $msg_erro .= traduz("<br />A Data de Vencimento não está em um formato válido<br />");
        }
    }else{
        $xdata_vencimento = "NULL";
    }

    if (strlen($_POST["obs"]) > 0) {
        $obs = trim($_POST["obs"]) ;
        $xobs = "'" . $obs . "'";
    }else{
        $xobs = "NULL";
    }

    if (strlen ($msg_erro) == 0) {

        if (strlen($extrato_pagamento) > 0) {
            $sql = "SELECT extrato FROM tbl_extrato WHERE fabrica = $login_fabrica AND extrato = $extrato";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) == 0) $msg_erro = traduz("Erro ao cadastrar baixa. Extrato não pertence à esta fábrica.");
        }

        if ($login_fabrica == 151) {
            $sql_agrupado = "SELECT codigo FROM tbl_extrato_agrupado WHERE extrato = $extrato";
            $res_agrupado = pg_query($con, $sql_agrupado);
            if (pg_num_rows($res_agrupado) > 0) {
                $codigo = pg_fetch_result($res_agrupado, 0, 'codigo');
                $sql_extratos = "SELECT extrato FROM tbl_extrato_agrupado WHERE codigo = '$codigo'";
                $res_extratos = pg_query($con, $sql_extratos);

                $auditorLog = new AuditorLog();

                foreach (pg_fetch_all($res_extratos) as $key => $value) {
                    $new_extrato = $value["extrato"];

                    if (strlen($new_extrato) > 0 ){
                        $sql = "SELECT extrato_pagamento FROM tbl_extrato_pagamento WHERE extrato = $new_extrato";
                        $res = pg_query($con,$sql);
                        if (pg_num_rows($res) > 0) {
                            $extrato_pagamento = pg_fetch_result($res,0,'extrato_pagamento');
                        }
                    }

                    $auditorLog->retornaDadosSelect( $logExtratoSql.$new_extrato );

                    $res = pg_query ($con,"BEGIN TRANSACTION");

                    if (strlen($msg_erro) == 0) {

                        if (strlen($extrato_pagamento) > 0) {
                            $sql = "UPDATE tbl_extrato_pagamento SET
                                            extrato           = $new_extrato        ,
                                            valor_total       = $xvalor_total       ,
                                            acrescimo         = $xacrescimo         ,
                                            desconto          = $xdesconto          ,
                                            valor_liquido     = $xvalor_liquido     ,
                                            nf_autorizacao    = $xnf_autorizacao    ,
                                            data_vencimento   = $xdata_vencimento   ,
                                            data_pagamento    = $xdata_pagamento    ,
                                            autorizacao_pagto = $xautorizacao_pagto ,
                                            obs               = $xobs               ,
                                            admin             = $login_admin        ,
                                            data_recebimento_nf = $xdata_recebimento_nf
                                    FROM tbl_extrato
                                        WHERE tbl_extrato_pagamento.extrato = tbl_extrato.extrato
                                        AND   tbl_extrato_pagamento.extrato_pagamento = $extrato_pagamento
                                        AND   tbl_extrato_pagamento.extrato           = $new_extrato
                                        AND   tbl_extrato.fabrica                     = $login_fabrica";
                        } else {
                            $sql = "INSERT INTO tbl_extrato_pagamento (
                                        extrato           ,
                                        valor_total       ,
                                        acrescimo         ,
                                        desconto          ,
                                        valor_liquido     ,
                                        nf_autorizacao    ,
                                        data_vencimento   ,
                                        data_pagamento    ,
                                        autorizacao_pagto ,
                                        obs               ,
                                        admin             ,
                                        data_recebimento_nf
                                    )VALUES(
                                        $new_extrato       ,
                                        $xvalor_total      ,
                                        $xacrescimo        ,
                                        $xdesconto         ,
                                        $xvalor_liquido    ,
                                        $xnf_autorizacao   ,
                                        $xdata_vencimento  ,
                                        $xdata_pagamento   ,
                                        $xautorizacao_pagto,
                                        $xobs              ,
                                        $login_admin       ,
                                        $xdata_recebimento_nf
                                    )";
                        }

                        if (strlen($new_extrato) > 0 ) {
                            $res = pg_query ($con,$sql);
                        }

                        $msg_erro .= pg_errormessage($con);
                    }

                    if (strlen ($msg_erro) == 0) {
                        $res = pg_query ($con,"COMMIT TRANSACTION");

                        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$new_extrato);
                    }else{
                        $res = pg_query ($con,"ROLLBACK TRANSACTION");
                    }
                }

                if (strlen(trim($msg_erro)) == 0) {
                    header ("Location: extrato_consulta.php?data_inicial=$data_inicial&data_final=$data_final&cnpj=$cnpj&razao=$razao");
                    exit;
                }

            } else {

                if (strlen($extrato) > 0 ){
                    $sql = "SELECT extrato_pagamento FROM tbl_extrato_pagamento WHERE extrato = $extrato";
                    $res = pg_query($con,$sql);
                    if (pg_num_rows($res) > 0) {
                        $extrato_pagamento = pg_fetch_result($res,0,'extrato_pagamento');
                    }
                }

                $auditorLog = new AuditorLog();
                $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

                $res = pg_query ($con,"BEGIN TRANSACTION");

                if (strlen($msg_erro) == 0) {

                    if (strlen($extrato_pagamento) > 0) {
                        $sql = "UPDATE tbl_extrato_pagamento SET
                                        extrato           = $extrato           ,
                                        valor_total       = $xvalor_total       ,
                                        acrescimo         = $xacrescimo         ,
                                        desconto          = $xdesconto          ,
                                        valor_liquido     = $xvalor_liquido     ,
                                        nf_autorizacao    = $xnf_autorizacao    ,
                                        data_vencimento   = $xdata_vencimento   ,
                                        data_pagamento    = $xdata_pagamento    ,
                                        autorizacao_pagto = $xautorizacao_pagto ,
                                        obs               = $xobs               ,
                                        admin             = $login_admin        ,
                                        data_recebimento_nf = $xdata_recebimento_nf
                                FROM tbl_extrato
                                    WHERE tbl_extrato_pagamento.extrato = tbl_extrato.extrato
                                    AND   tbl_extrato_pagamento.extrato_pagamento = $extrato_pagamento
                                    AND   tbl_extrato_pagamento.extrato           = $extrato
                                    AND   tbl_extrato.fabrica                     = $login_fabrica";
                        } else {
                            $sql = "INSERT INTO tbl_extrato_pagamento (
                                        extrato           ,
                                        valor_total       ,
                                        acrescimo         ,
                                        desconto          ,
                                        valor_liquido     ,
                                        nf_autorizacao    ,
                                        data_vencimento   ,
                                        data_pagamento    ,
                                        autorizacao_pagto ,
                                        obs               ,
                                        admin             ,
                                        data_recebimento_nf
                                    )VALUES(
                                        $extrato           ,
                                        $xvalor_total      ,
                                        $xacrescimo        ,
                                        $xdesconto         ,
                                        $xvalor_liquido    ,
                                        $xnf_autorizacao   ,
                                        $xdata_vencimento  ,
                                        $xdata_pagamento   ,
                                        $xautorizacao_pagto,
                                        $xobs              ,
                                        $login_admin       ,
                                        $xdata_recebimento_nf
                                    )";
                        }

                    if (strlen($extrato) > 0 ) {
                        $res = pg_query ($con,$sql);
                    }

                    $msg_erro = pg_errormessage($con);
                }

                if (strlen ($msg_erro) == 0) {
                    $res = pg_query ($con,"COMMIT TRANSACTION");

                    $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);

                    header ("Location: extrato_consulta.php?data_inicial=$data_inicial&data_final=$data_final&cnpj=$cnpj&razao=$razao");
                    exit;
                }else{
                    $res = pg_query ($con,"ROLLBACK TRANSACTION");
                }
            }

        } else {

            //HD 385125 - INICIO
            if (strlen($extrato) > 0 ){
                $sql = "SELECT extrato_pagamento FROM tbl_extrato_pagamento WHERE extrato = $extrato";
                $res = pg_query($con,$sql);
                if (pg_num_rows($res) > 0) {
                    $extrato_pagamento = pg_fetch_result($res,0,'extrato_pagamento');
                }
            }
            //HD 385125 - FIM


            $auditorLog = new AuditorLog();
            $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );


            $res = pg_query ($con,"BEGIN TRANSACTION");

            //hd-1059101 - Makita
            if($login_fabrica == 42){
                //valida data bordero

                if( strlen($_POST["previsao_pagamento"]) > 0 ){
                    $previsao_pagamento = trim($_POST['previsao_pagamento']);
                    $xprevisao_pagamento = str_replace(array("/", "-", ".", " "), "", $previsao_pagamento);

                    $dia = trim(substr($xprevisao_pagamento,0,2));
                    $mes = trim(substr($xprevisao_pagamento,2,2));
                    $ano = trim(substr($xprevisao_pagamento,4,4));
                    if( strlen($ano) == 2 ) $ano = "20" . $ano;

                    $verifica = checkdate($mes, $dia, $ano);

                    if( $verifica == 1 ){
                        $xprevisao_pagamento = "'$ano-$mes-$dia'";
                    }else{
                        $msg_erro .= traduz("<br />A Previsão Pagamento não está em um formato válido<br />");
                    }
                }else{
                    $xprevisao_pagamento = 'NULL';
                }

                if( strlen($_POST["data_bordero"]) > 0 ){
                    $data_bordero  = trim($_POST["data_bordero"]) ;
                    $xdata_bordero = str_replace("/","",$data_bordero);
                    $xdata_bordero = str_replace("-","",$xdata_bordero);
                    $xdata_bordero = str_replace(".","",$xdata_bordero);
                    $xdata_bordero = str_replace(" ","",$xdata_bordero);

                    $dia = trim(substr($xdata_bordero,0,2));
                    $mes = trim(substr($xdata_bordero,2,2));
                    $ano = trim(substr($xdata_bordero,4,4));
                    if( strlen($ano) == 2 ) $ano = "20" . $ano;

                    $verifica = checkdate($mes, $dia, $ano);

                    if( $verifica == 1 ){
                        $xdata_bordero = $ano . "-" . $mes . "-" . $dia ;
                        $xdata_bordero = "'" . $data_bordero . "'";
                    }else{
                        $msg_erro .= traduz("<br />A Data Borderô não está em um formato válido<br />");
                    }
                }else{
                    $xdata_bordero = "NULL";
                }
                //valida data_envio_aprovacao
                if( strlen($_POST["data_envio_aprovacao"]) > 0 ){
                    $data_envio_aprovacao  = trim($_POST["data_envio_aprovacao"]) ;
                    $xdata_envio_aprovacao = str_replace("/","",$data_envio_aprovacao);
                    $xdata_envio_aprovacao = str_replace("-","",$xdata_envio_aprovacao);
                    $xdata_envio_aprovacao = str_replace(".","",$xdata_envio_aprovacao);
                    $xdata_envio_aprovacao = str_replace(" ","",$xdata_envio_aprovacao);

                    $dia = trim(substr($xdata_envio_aprovacao,0,2));
                    $mes = trim(substr($xdata_envio_aprovacao,2,2));
                    $ano = trim(substr($xdata_envio_aprovacao,4,4));
                    if( strlen($ano) == 2 ) $ano = "20" . $ano;

                    $verifica = checkdate($mes, $dia, $ano);

                    if( $verifica == 1 ){
                        $xdata_envio_aprovacao = $ano . "-" . $mes . "-" . $dia ;
                        $xdata_envio_aprovacao = "'" . $data_envio_aprovacao . "'";
                    }else{
                        $msg_erro .= traduz("<br />A Data Envio Aprovação não está em um formato válido<br />");
                    }
                }else{
                    $xdata_envio_aprovacao = "NULL";
                }
                //valida data_aprovacao
                if( strlen($_POST["data_aprovacao"]) > 0 ){
                    $data_aprovacao  = trim($_POST["data_aprovacao"]) ;
                    $xdata_aprovacao = str_replace("/","",$data_aprovacao);
                    $xdata_aprovacao = str_replace("-","",$xdata_aprovacao);
                    $xdata_aprovacao = str_replace(".","",$xdata_aprovacao);
                    $xdata_aprovacao = str_replace(" ","",$xdata_aprovacao);

                    $dia = trim(substr($xdata_aprovacao,0,2));
                    $mes = trim(substr($xdata_aprovacao,2,2));
                    $ano = trim(substr($xdata_aprovacao,4,4));
                    if( strlen($ano) == 2 ) $ano = "20" . $ano;

                    $verifica = checkdate($mes, $dia, $ano);

                    if( $verifica == 1 ){
                        $xdata_aprovacao = $ano . "-" . $mes . "-" . $dia ;
                        $xdata_aprovacao = "'" . $data_aprovacao . "'";
                    }else{
                        $msg_erro .= traduz("<br />A Data Aprovação não está em um formato válido<br />");
                    }
                }else{
                    $xdata_aprovacao = "NULL";
                }
                //valida data_entrega_financeiro
                if( strlen($_POST["data_entrega_financeiro"]) > 0 ){
                    $data_entrega_financeiro  = trim($_POST["data_entrega_financeiro"]) ;
                    $xdata_entrega_financeiro = str_replace("/","",$data_entrega_financeiro);
                    $xdata_entrega_financeiro = str_replace("-","",$xdata_entrega_financeiro);
                    $xdata_entrega_financeiro = str_replace(".","",$xdata_entrega_financeiro);
                    $xdata_entrega_financeiro = str_replace(" ","",$xdata_entrega_financeiro);

                    $dia = trim(substr($xdata_entrega_financeiro,0,2));
                    $mes = trim(substr($xdata_entrega_financeiro,2,2));
                    $ano = trim(substr($xdata_entrega_financeiro,4,4));
                    if( strlen($ano) == 2 ) $ano = "20" . $ano;

                    $verifica = checkdate($mes, $dia, $ano);

                    if( $verifica == 1 ){
                        $xdata_entrega_financeiro = $ano . "-" . $mes . "-" . $dia ;
                        $xdata_entrega_financeiro = "'" . $data_entrega_financeiro . "'";
                    }else{
                        $msg_erro .= traduz("<br />A Data Entrega Financeiro não está em um formato válido<br />");
                    }
                }else{
                    $xdata_entrega_financeiro = "NULL";
                }
                //valida justificativa
                if (strlen($_POST["justificativa"]) > 0) {
                    $justificativa = trim($_POST["justificativa"]) ;
                    $xjustificativa = "'" . $justificativa . "'";
                }else{
                    $xjustificativa = "NULL";
                }
                //valida desconto_pecas
                $desconto_pecas        = trim($_POST["desconto_pecas"]) ;
                $xdesconto_pecas       = (strlen($desconto_pecas) > 0) ? "'".str_replace(",",".",$desconto_pecas)."'" : 'NULL';
                //valida vlr_nf_pecas
                $vlr_nf_pecas        = trim($_POST["vlr_nf_pecas"]) ;
                $xvlr_nf_pecas       = (strlen($vlr_nf_pecas) > 0) ? "'".str_replace(",",".",$vlr_nf_pecas)."'" : 'NULL';
                //valida $nro_nf_pecas
                $nro_nf_pecas        = trim($_POST["nro_nf_pecas"]) ;
                $xnro_nf_pecas       = (strlen($nro_nf_pecas) > 0) ? "'".str_replace(",",".",$nro_nf_pecas)."'" : 'NULL';
                //valida acrescmo_pecas
                $acrescimo_pecas        = trim($_POST["acrescimo_pecas"]) ;
                $xacrescimo_pecas       = (strlen($acrescimo_pecas) > 0) ? "'".str_replace(",",".",$acrescimo_pecas)."'" : 'NULL';

                $xbordero                   = !empty($_POST["bordero"])                 ? $_POST["bordero"]                 : 'NULL';
                $xmes_referencia            = !empty($_POST["mes_referencia"])          ? $_POST["mes_referencia"]          : 'NULL';

            }
            if (strlen($msg_erro) == 0) {

                if (strlen($extrato_pagamento) > 0) {

                    if($login_fabrica == 42){
                        $sql = "UPDATE tbl_extrato_pagamento SET
                                    extrato                 = $extrato           ,
                                    valor_total             = $xvalor_total       ,
                                    acrescimo               = $xacrescimo         ,
                                    desconto                = $xdesconto          ,
                                    valor_liquido           = $xvalor_liquido     ,
                                    nf_autorizacao          = $xnf_autorizacao    ,
                                    data_vencimento         = $xdata_vencimento   ,
                                    data_pagamento          = $xdata_pagamento    ,
                                    autorizacao_pagto       = $xautorizacao_pagto ,
                                    obs                     = $xobs               ,
                                    admin                   = $login_admin        ,
                                    data_recebimento_nf     = $xdata_envio_aprovacao,
                                    valor_nf_peca           = $xvlr_nf_pecas        ,
                                    nf_peca                 = $xnro_nf_pecas        ,
                                    acrescimo_nf_peca       = $xacrescimo_pecas ,
                                    desconto_nf_peca        = $xdesconto_pecas      ,
                                    duplicata               = $xbordero         ,
                                    data_bordero            = $xdata_bordero        ,
                                    mes_referencia          = $xmes_referencia      ,
                                    data_aprovacao          = $xdata_aprovacao      ,
                                    data_entrega_financeiro = $xdata_entrega_financeiro,
                                    justificativa           = $xjustificativa,
                                    previsao_pagamento      = $xprevisao_pagamento
                            FROM tbl_extrato
                                WHERE tbl_extrato_pagamento.extrato = tbl_extrato.extrato
                                AND   tbl_extrato_pagamento.extrato_pagamento = $extrato_pagamento
                                AND   tbl_extrato_pagamento.extrato           = $extrato
                                AND   tbl_extrato.fabrica                     = $login_fabrica";
                    }else{
                        $sql = "UPDATE tbl_extrato_pagamento SET
                                    extrato           = $extrato           ,
                                    valor_total       = $xvalor_total       ,
                                    acrescimo         = $xacrescimo         ,
                                    desconto          = $xdesconto          ,
                                    valor_liquido     = $xvalor_liquido     ,
                                    nf_autorizacao    = $xnf_autorizacao    ,
                                    data_vencimento   = $xdata_vencimento   ,
                                    data_pagamento    = $xdata_pagamento    ,
                                    autorizacao_pagto = $xautorizacao_pagto ,
                                    obs               = $xobs               ,
                                    admin             = $login_admin        ,
                                    data_recebimento_nf = $xdata_recebimento_nf
                            FROM tbl_extrato
                                WHERE tbl_extrato_pagamento.extrato = tbl_extrato.extrato
                                AND   tbl_extrato_pagamento.extrato_pagamento = $extrato_pagamento
                                AND   tbl_extrato_pagamento.extrato           = $extrato
                                AND   tbl_extrato.fabrica                     = $login_fabrica";
                    }

                }else{
                    if($login_fabrica == 42){

                        $sql = "INSERT INTO tbl_extrato_pagamento (
                                    extrato           ,
                                    valor_total       ,
                                    acrescimo         ,
                                    desconto          ,
                                    valor_liquido     ,
                                    nf_autorizacao    ,
                                    data_vencimento   ,
                                    data_pagamento    ,
                                    autorizacao_pagto ,
                                    obs               ,
                                    admin             ,
                                    data_recebimento_nf,
                                    valor_nf_peca,
                                    nf_peca,
                                    acrescimo_nf_peca,
                                    desconto_nf_peca,
                                    duplicata,
                                    data_bordero,
                                    mes_referencia,
                                    data_aprovacao,
                                    data_entrega_financeiro,
                                    justificativa,
                                    previsao_pagamento
                                )VALUES(
                                    $extrato           ,
                                    $xvalor_total      ,
                                    $xacrescimo        ,
                                    $xdesconto         ,
                                    $xvalor_liquido    ,
                                    $xnf_autorizacao   ,
                                    $xdata_vencimento  ,
                                    $xdata_pagamento   ,
                                    $xautorizacao_pagto,
                                    $xobs              ,
                                    $login_admin       ,
                                    $xdata_envio_aprovacao,
                                    $xvlr_nf_pecas,
                                    $xnro_nf_pecas,
                                    $xacrescimo_pecas,
                                    $xdesconto_pecas,
                                    $xbordero,
                                    $xdata_bordero,
                                    $xmes_referencia,
                                    $xdata_aprovacao,
                                    $xdata_entrega_financeiro,
                                    $xjustificativa,
                                    $xprevisao_pagamento
                                )";
                    }else{
                        $sql = "INSERT INTO tbl_extrato_pagamento (
                                    extrato           ,
                                    valor_total       ,
                                    acrescimo         ,
                                    desconto          ,
                                    valor_liquido     ,
                                    nf_autorizacao    ,
                                    data_vencimento   ,
                                    data_pagamento    ,
                                    autorizacao_pagto ,
                                    obs               ,
                                    admin             ,
                                    data_recebimento_nf
                                )VALUES(
                                    $extrato           ,
                                    $xvalor_total      ,
                                    $xacrescimo        ,
                                    $xdesconto         ,
                                    $xvalor_liquido    ,
                                    $xnf_autorizacao   ,
                                    $xdata_vencimento  ,
                                    $xdata_pagamento   ,
                                    $xautorizacao_pagto,
                                    $xobs              ,
                                    $login_admin       ,
                                    $xdata_recebimento_nf
                                )";
                    }
                }


                if(strlen($extrato) > 0 ) {
                    $res = pg_query ($con,$sql);
                }

                $msg_erro = pg_errormessage($con);
            }

            if(in_array($login_fabrica, [152,180,181,182])){

                $data_baixa = mostra_data($xdata_pagamento);

                $sql_extrato_status = "INSERT INTO tbl_extrato_status (fabrica, extrato, data, obs, advertencia) 
                          VALUES ($login_fabrica, $extrato, now(), 'Encerramento', false) ";
                $res_extrato_status = pg_query($con, $sql_extrato_status);
                if(strlen(pg_last_error($con))>0){
                    $msg_erro .= traduz("Falha ao encerrar o extrato %.<br />", null, null, [$extrato]);
                }

                $sql = "SELECT posto FROM tbl_extrato WHERE fabrica = {$login_fabrica} AND extrato = {$extrato}
                ";
                $res = pg_query($con, $sql);
                $posto = pg_fetch_result($res, 0, "posto");

                $sql_comunicado = "INSERT INTO tbl_comunicado
                            (
                                fabrica,
                                posto,
                                obrigatorio_site,
                                tipo,
                                ativo,
                                descricao,
                                mensagem
                            )
                            VALUES
                            (
                                {$login_fabrica},
                                {$posto},
                                true,
                                'Com. Unico Posto',
                                true,
                                'Extrato Encerrado',
                                '".traduz('O extrato % foi encerrado. Data da baixa: %', null, null, [$extrato, $data_baixa])."'
                            )";
                $res_comunicado = pg_query($con, $sql_comunicado);

                if(strlen(pg_last_error($con))>0){
                    $msg_erro .= traduz("Falha ao enviar comunicado.");
                }
            }

            if (strlen ($msg_erro) == 0) {
                $res = pg_query ($con,"COMMIT TRANSACTION");

                $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);

                if($login_fabrica == 24){// ficava listando sem tem necessidade, takashi tirou conforme conversa com aline 11/12/07
                    echo "<script language='JavaScript'>";
                    echo "if (window.opener){window.opener.refreshTela(50);} "; #HD 22752
                    echo "window.close();";
                    echo "</script>";
                }else{
                    header ("Location: extrato_consulta.php?data_inicial=$data_inicial&data_final=$data_final&cnpj=$cnpj&razao=$razao");
                    exit;
                }
            }else{
                $res = pg_query ($con,"ROLLBACK TRANSACTION");
            }
        }
    }
}

// HD 18066
if( $btn_acao == "excluir_baixa" )
{
    $extrato = $_POST['extrato'];

    if( strlen($extrato) > 0 )
    {
        $res = pg_query ($con,"BEGIN TRANSACTION");
        $sql="DELETE FROM tbl_extrato_pagamento where extrato=$extrato";
        $res = pg_query ($con,$sql);
        $msg_erro = pg_errormessage($con);

        if( strlen ($msg_erro) == 0 ){
            $res = pg_query ($con,"COMMIT TRANSACTION");
            if ($login_fabrica == 24){
                header ("Location: $PHP_SELF?extrato=$extrato");
            }else{
                header ("Location: extrato_consulta.php?extrato=$extrato");
            }
            exit;
        }else{
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
        }
    }
}

if ( $btn_acao == "excluir" ) {

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

    $auditorLogOS = new AuditorLog();

    $qtde_os      = $_POST["qtde_os"];
    $array_os_geo = array();

    for( $k = 0 ; $k < $qtde_os; $k++ )
    {
        $res          = pg_query($con,"BEGIN TRANSACTION");
        $x_os  = trim($_POST["os_" . $k]);
        $x_obs = trim($_POST["obs_" . $k]);
        $x_motivo = trim($_POST["motivo_".$k]);

        $filtro = "$extrato AND os = $x_os ";
        $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$filtro );

        if (!strlen($x_obs) && !strlen($x_motivo)) {
            $msg_erro    = " Informe a observação na OS $x_os. ";
            $linha_erro  = $k;
            $select_acao = "EXCLUIR";
        } else if (!strlen($x_obs) && strlen($x_motivo) > 0) {
            $sqlMotivo = "SELECT motivo FROM tbl_motivo_recusa WHERE fabrica = {$login_fabrica} AND motivo_recusa = {$x_motivo}";
            $resMotivo = pg_query($con, $sqlMotivo);
            $x_obs = pg_fetch_result($resMotivo, 0, "motivo");
        }

        $sql =  "INSERT INTO tbl_os_status (
                        extrato    ,
                        os         ,
                        observacao ,
                        status_os  ,
                        admin
                    ) VALUES (
                        $extrato ,
                        $x_os    ,
                        '$x_obs' ,
                        15       ,
                        $login_admin
                    );";

        $res = @pg_query($con,$sql);

        $msg_erro = pg_errormessage($con);

        if(strlen($msg_erro) > 0){
            $pos = strpos($msg_erro,'CONTEXT');
            $msg_erro = substr($msg_erro,0,$pos);
        }

        if (strlen($msg_erro) == 0) {
                $sql = "UPDATE tbl_os_extra SET extrato = null
                        FROM tbl_extrato_extra, tbl_extrato, tbl_os
                        WHERE  tbl_os_extra.os      = $x_os
                        AND    tbl_os_extra.extrato = $extrato
                        AND    tbl_os_extra.os      = tbl_os.os
                        AND    tbl_os_extra.extrato = tbl_extrato.extrato
                        AND    tbl_extrato.extrato  = tbl_extrato_extra.extrato
                        AND    tbl_extrato_extra.baixado IS NULL
                        AND    tbl_os.fabrica  = $login_fabrica;";
            $res = @pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

        #OS_TROCA - Excluida/Acumulada/Recusada o débito HD14648
        if( strlen($msg_erro) == 0 AND (in_array($login_fabrica,array(1,30,74))) ){
            $sql = "SELECT tbl_os_troca.os_troca    ,
                            tbl_os_troca.total_troca,
                            tbl_os.os               ,
                            tbl_os.sua_os
                        FROM tbl_os
                        JOIN tbl_os_troca USING(os)
                        WHERE os = $x_os";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) > 0){
                $sua_os_troca   = pg_fetch_result($res,0,sua_os);
                $os_sedex_troca = '';
                #troca
                $sql = "SELECT os_sedex
                            FROM tbl_os_sedex
                            WHERE extrato_destino = $extrato
                            AND sua_os_destino = '$sua_os_troca'; ";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
                    $os_sedex_troca = pg_fetch_result($res, 0,os_sedex);
                    #Sedex

                    $sql = "DELETE FROM tbl_extrato_extra_item WHERE os_sedex = $os_sedex_troca ";
                    $res = pg_query($con,$sql);
                    $msg_erro = pg_errormessage($con);

                    $sql = "DELETE FROM tbl_os_sedex WHERE os_sedex = $os_sedex_troca AND sua_os_destino = '$sua_os_troca';";
                    $res = pg_query($con,$sql);
                    $msg_erro = pg_errormessage($con);

                    $sql = "SELECT extrato_lancamento
                                FROM tbl_extrato_lancamento
                                WHERE extrato = $extrato
                                AND   os_sedex = $os_sedex_troca;";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0 AND strlen($msg_erro) == 0){
                        $extrato_lancamento_troca = pg_fetch_result($res,0,extrato_lancamento);
                        #extrato lançamento
                        $sql = "DELETE FROM tbl_extrato_extra_item WHERE extrato_lancamento = $extrato_lancamento_troca;";
                        $res = pg_query($con,$sql);
                        $msg_erro = pg_errormessage($con);

                        $sql = "DELETE FROM tbl_extrato_lancamento WHERE os_sedex = $os_sedex_troca AND extrato_lancamento = $extrato_lancamento_troca;";
                        $res = pg_query($con,$sql);
                        $msg_erro = pg_errormessage($con);
                    }
                }
            }

            /* OS GEO METAL - 83010 */
            $sql = "SELECT os_numero
                    FROM tbl_os
                    WHERE os = $x_os
                        and tipo_os= 13;";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) > 0){
                $os_numero= pg_fetch_result($res,0,os_numero);
                $array_os_geo[$os_numero]= $os_numero;
            }

            # HD 148341 - A movimentação de estoque deve ser retirada quando uma OS é excluida
            $sql = "SELECT fn_estoque_recusa_os($x_os,$login_fabrica,$login_admin);";
            $res = @pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);

        }

        if( strlen($msg_erro) == 0 )
        {
            $sql = "UPDATE tbl_os SET excluida = true
                        WHERE  tbl_os.os           = $x_os
                        AND    tbl_os.fabrica      = $login_fabrica;";
            $res = @pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);

            #158147 Paulo/Waldir desmarcar se for reincidente
            $sql = "SELECT fn_os_excluida_reincidente($x_os,$login_fabrica)";
            $res = pg_query($con, $sql);


            if( $login_fabrica == 1 ){ // HD 28837
                $sql = "insert into tbl_os_excluida (
                        fabrica           ,
                        admin             ,
                        os                ,
                        sua_os            ,
                        posto             ,
                        codigo_posto      ,
                        produto           ,
                        referencia_produto,
                        data_digitacao    ,
                        data_abertura     ,
                        data_fechamento   ,
                        serie             ,
                        nota_fiscal       ,
                        data_nf           ,
                        consumidor_nome
                    )
                    SELECT  tbl_os.fabrica            ,
                        $login_admin                  ,
                        tbl_os.os                     ,
                        tbl_os.sua_os                 ,
                        tbl_os.posto                  ,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_os.produto                ,
                        tbl_produto.referencia        ,
                        tbl_os.data_digitacao         ,
                        tbl_os.data_abertura          ,
                        tbl_os.data_fechamento        ,
                        tbl_os.serie                  ,
                        tbl_os.nota_fiscal            ,
                        tbl_os.data_nf                ,
                        tbl_os.consumidor_nome
                    FROM    tbl_os
                    JOIN    tbl_posto_fabrica        on tbl_posto_fabrica.posto = tbl_os.posto and tbl_os.fabrica          = tbl_posto_fabrica.fabrica
                    JOIN    tbl_produto              on tbl_produto.produto     = tbl_os.produto
                    WHERE   tbl_os.os      = $x_os
                    AND     tbl_os.fabrica = $login_fabrica ";

                $res = @pg_query($con, $sql);
                $msg_erro = pg_errormessage($con);
            }
        }

        /**-Exclusão de os, reembolsando a Black(descontar no extrato) da peça enviada na OS EXCLUIDA.
          * Pedido em garantia = servico_realizado 62.
          * Na OS buscar por peças que estão com o servico realizado : troca de peça gerando pedido
          * Criar uma OS SEDEX como Débito para o posto no mesmo extrato
          * Criar um registro no tbl_extrato_lancamento com valor negativo
          *
          * Posto origem : PA
          * Posto destino: Black
          *
          * Apenas para a Black&Decker: verificar o lancamento(41 soh black) na tbl_extrato_lancamento.
        **/
        if( strlen($msg_erro) == 0 AND ($login_fabrica == 1 OR $login_fabrica == 10) ){
            $sql = "SELECT  SUM(tbl_os_item.custo_peca * tbl_os_item.qtde) AS total
                    FROM tbl_os_produto
                    JOIN tbl_os_item           USING(os_produto)
                    JOIN tbl_servico_realizado USING(servico_realizado)
                    WHERE tbl_os_produto.os             = $x_os
                    AND   tbl_servico_realizado.fabrica = $login_fabrica
                    AND   tbl_servico_realizado.troca_de_peca IS TRUE
                    AND   tbl_servico_realizado.gera_pedido   IS TRUE;";

            $res = pg_query($con,$sql);//somatoria de todas as peças que há troca de peça gerando pedido.
            $total = pg_fetch_result($res,0,total);

            if( strlen($msg_erro) == 0 AND $total > 0 ){

                $sql = "SELECT sua_os FROM tbl_os WHERE os = $x_os;";
                $res = pg_query($con,$sql);
                $sedex_sua_os     = trim(pg_fetch_result($res,0,sua_os));

                $sql = "SELECT posto, protocolo
                        FROM tbl_extrato
                        WHERE extrato = $extrato
                        AND   fabrica = $login_fabrica;";

                $res = pg_query($con,$sql); //busca o posto para ser inserido no posto origem
                                            //busca o protocolo para enviar e-mail.
                $posto_destino    = pg_fetch_result($res,0,posto);
                $sedex_protocolo = pg_fetch_result($res,0,protocolo);

                $sql = "INSERT INTO tbl_os_sedex(
                                        fabrica          ,
                                        posto_destino    ,
                                        posto_origem     ,
                                        sua_os_destino   ,
                                        extrato          ,
                                        total_pecas      ,
                                        total            ,
                                        finalizada       ,
                                        obs              ,
                                        admin            ,
                                        data             ,
                                        extrato_destino
                                ) VALUES (
                                        $login_fabrica   ,
                                        $posto_destino   ,
                                        '6900'           ,
                                        '$sedex_sua_os'  ,
                                        $extrato         ,
                                        $total           ,
                                        $total           ,
                                        current_timestamp,
                                        '$x_obs'         ,
                                        '$login_admin'   ,
                                        current_date     ,
                                        $extrato
                        );";
                $res = pg_query($con,$sql);//insere uma OS SEDEX no extrato atual.
                $msg_erro = pg_errormessage($con);

                $sql = "SELECT CURRVAL ('tbl_os_sedex_seq')";
                $res = pg_query($con,$sql);//busca a os_sedex que foi cadastrada.
                $os_sedex = pg_fetch_result($res,0,0);

                $total_neg = $total * (-1);
                $sql = "INSERT INTO tbl_extrato_lancamento (
                                            fabrica   ,
                                            posto     ,
                                            extrato   ,
                                            automatico,
                                            lancamento,
                                            valor     ,
                                            os_sedex
                                    ) VALUES (
                                            $login_fabrica   ,
                                            $posto_destino   ,
                                            $extrato         ,
                                            't'              ,
                                            '42'             ,
                                            $total_neg       ,
                                            $os_sedex
                                    );";
                $res = pg_query($con,$sql); //insere um lancamento com valor NEGATIVO. Valor das pecas multiplicaod por -1.
                                            //insere para a black o status 41.
                //HD 16545 estava inserindo o lançamento 41 para débito, mas 42 que é debito

                $sql = "UPDATE tbl_os_status SET os_sedex = '$os_sedex'
                        WHERE extrato = $extrato
                        AND   os = $x_os
                        AND   status_os = 15 ;";
                $res = @pg_query($con, $sql);
                $msg_erro = pg_errormessage($con);
            }
        }

        if (!strlen(pg_last_error($con))) {
            $res = pg_query($con,"COMMIT TRANSACTION");
            $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato, null, "OS $x_os EXCLUIDA DO EXTRATO $extrato - MOTIVO: $x_obs");
        } else {
            $res = pg_query($con,"ROLLBACK TRANSACTION");
        }
    }

    pg_query($con,"BEGIN TRANSACTION");

    /*OS GEO - 83010*/
    if( strlen($msg_erro) == 0 and $login_fabrica ==1 and count($array_os_geo)>0 )
    {
        foreach( $array_os_geo as $i => $os_revenda )
        {
            $sql = "SELECT tbl_os_revenda_item.os_lote as os,
                        tbl_os_extra.extrato
                    FROM tbl_os_revenda
                    JOIN tbl_os_revenda_item using(os_revenda)
                    JOIN tbl_os_extra on tbl_os_extra.os = tbl_os_revenda_item.os_lote
                    WHERE tbl_os_revenda.fabrica =$login_fabrica
                        AND tbl_os_revenda.os_revenda = $os_revenda
                        AND tbl_os_revenda.extrato_revenda =$extrato
                        AND tbl_os_extra.extrato = $extrato";
            //echo "sql: $sql";
            $res = pg_query($con,$sql);

            if (pg_num_rows($res) > 0) {
                //Quando existir OS (tbl_os) da OS GEO (tbl_os_revenda) não faz nada
            }else{

                //Se não existir tem que atualizar a OS Revenda para Fábrica zero
                $sql = "UPDATE tbl_os_revenda
                            SET extrato_revenda = null,
                                fabrica = 0
                        WHERE os_revenda = $os_revenda
                            AND extrato_revenda =$extrato;";
                //echo "sql: $sql";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);
            }
        }

        $sql = "SELECT posto
                FROM   tbl_extrato
                WHERE  extrato = $extrato
                AND    fabrica = $login_fabrica";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
        $posto = pg_fetch_result($res,0,posto);

        /*Necessário recalcular o Extrato, pois existem regras de avulso baseada na OS Geo*/
        if (pg_num_rows($res) > 0 AND strlen($msg_erro) == 0) {
            $sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);
        }
    }

    if (strlen($msg_erro) == 0) {
        $sql = "SELECT posto
                FROM   tbl_extrato
                WHERE  extrato = $extrato
                AND    fabrica = $login_fabrica ;";
        $res = @pg_query($con, $sql);
        $msg_erro = pg_errormessage($con);

        if (pg_fetch_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){

            #$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
            #$res = pg_query($con,$sql);
            #$total_os_extrato = pg_fetch_result($res,0,0);

            #HD15716
            if( in_array($login_fabrica, array(11,172)) ){
                $sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
                $res = pg_query($con,$sql);
                $msg_aviso = traduz("Foi agendado o recalculo do extrato % para esta noite!<br />", null, null, [$extrato]);
            }else{
                if (isset($novaTelaOs)) {
                     try {
                        $sql = "SELECT
                                    SUM(tbl_os.mao_de_obra) as total_mo,
                                    SUM(tbl_os.qtde_km_calculada) as total_km,
                                    SUM(tbl_os.pecas) as total_pecas,
                                    SUM(tbl_os.valores_adicionais) as total_adicionais,
                                    tbl_extrato.avulso
                                FROM tbl_os
                                INNER JOIN tbl_os_extra USING(os)
                                INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
                                WHERE tbl_os_extra.extrato = {$extrato}
                                AND tbl_extrato.fabrica = {$login_fabrica}
                                GROUP BY tbl_extrato.avulso";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception(traduz("Erro ao excluir Ordem de Serviço"));
                        }

                        $total_mo         = pg_fetch_result($res, 0, "total_mo");
                        $total_km         = pg_fetch_result($res, 0, "total_km");
                        $total_pecas      = pg_fetch_result($res, 0, "total_pecas");
                        $total_adicionais = pg_fetch_result($res, 0, "total_adicionais");
                        $avulso           = pg_fetch_result($res, 0, "avulso");

                        if (!strlen($total_mo)) {
                            $total_mo = 0;
                        }

                        if (!strlen($total_km)) {
                            $total_km = 0;
                        }

                        if (!strlen($total_pecas)) {
                            $total_pecas = 0;
                        }

                        if (!strlen($total_adicionais)) {
                            $total_adicionais = 0;
                        }

                        if (!strlen($avulso)) {
                            $avulso = 0;
                        }

                        $total = $total_mo + $total_km + $total_pecas + $total_adicionais + $avulso;

                        $sql = "UPDATE tbl_extrato SET
                                    total           = {$total},
                                    mao_de_obra     = {$total_mo},
                                    pecas           = {$total_pecas},
                                    deslocamento    = {$total_km},
                                    valor_adicional = {$total_adicionais}
                                WHERE extrato = {$extrato}
                                AND fabrica = {$login_fabrica}";
                        $res = pg_query($con, $sql);

                        if (($total - $avulso) == 0) {
                            $sql = "UPDATE tbl_extrato SET
                                    fabrica = 0
                                    WHERE extrato = {$extrato};

                                    UPDATE tbl_extrato_lancamento SET
                                        extrato = null
                                    WHERE extrato = $extrato ";
                            $res = pg_query($con, $sql);
                        }

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception(traduz("Erro ao excluir Ordem de Serviço"));
                        }

                        $retira_extrato_do_faturamento = verificaExtratoExcluido('EXCLUIR',$extrato);

                        if (!$retira_extrato_do_faturamento) {
                            throw new Exception(traduz("Erro ao excluir Ordem de Serviço"));
                        }

                    } catch(Exception $e) {
                        $msg_erro = $e->getMessage();
                    }
                } else {
                    $sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
                    $res = @pg_query($con, $sql);
                    $msg_erro = pg_errormessage($con);
                }
            }
        }
    }

    if (strlen($msg_erro) == 0) {
        /* HD-3291983 */
        if ($login_fabrica == 42) {
            $sql = "UPDATE tbl_extrato SET exportado = NULL WHERE extrato = $extrato";
            $res = pg_query($con, $sql);
        }
        $res = pg_query($con,"COMMIT TRANSACTION");

        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);

        $link = $_COOKIE["link"];
        header ("Location: $link?msg_aviso=$msg_aviso&extrato=$extrato");
        exit;
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}

if (strtolower($btn_acao) == "reabrir") {

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

    $auditorLogOS = new AuditorLog();

    $qtde_os = $_POST["qtde_os"];

    for( $k = 0 ; $k < $qtde_os; $k++ ) {
        pg_query($con,"BEGIN");

        $x_os  = trim($_POST["os_" . $k]);
        $x_obs = trim($_POST["obs_" . $k]);
        $x_motivo = trim($_POST["motivo_".$k]);

        $x_os  = trim($_POST["os_" . $k]);
        $x_obs = trim($_POST["obs_" . $k]);
        $x_motivo = trim($_POST["motivo_".$k]);

        $filtro = "$extrato AND os = $x_os ";
        $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$filtro );

        if (!strlen($x_obs) && !strlen($x_motivo)) {
            $msg_erro    = " Informe a observação na OS $x_os. ";
            $linha_erro  = $k;
            $select_acao = "REABRIR";
        } else if (!strlen($x_obs) && strlen($x_motivo) > 0) {
            $sqlMotivo = "SELECT motivo FROM tbl_motivo_recusa WHERE fabrica = {$login_fabrica} AND motivo_recusa = {$x_motivo}";
            $resMotivo = pg_query($con, $sqlMotivo);
            $x_obs = pg_fetch_result($resMotivo, 0, "motivo");
        }
            try {
                if($login_fabrica == 147 ){
                    $sql = "SELECT
                            SUM(tbl_os.mao_de_obra) as total_mo,
                            SUM(tbl_os.qtde_km_calculada) as total_km,
                            SUM(tbl_os.pecas) as total_pecas,
                            SUM(tbl_os.valores_adicionais) as total_adicionais
                        FROM tbl_os
                        WHERE tbl_os.os = {$x_os} ";

                    $res = pg_query($con , $sql);

                    $valor_os_mo = pg_fetch_result($res, 0, 'total_mo');
                    $valor_os_km = pg_fetch_result($res, 0, 'total_km');
                    $valor_os_pecas = pg_fetch_result($res, 0, 'total_pecas');
                    $valor_os_adi = pg_fetch_result($res, 0, 'total_adicionais');

                    $valor_os_total = $valor_os_mo + $valor_os_km + $valor_os_pecas + $valor_os_adi;
                }

                $sql = "UPDATE tbl_os SET
                            finalizada         = NULL,
                            data_fechamento    = NULL,
                            mao_de_obra        = 0,
                            qtde_km_calculada  = 0,
                            pecas              = 0,
                            valores_adicionais = 0
                        WHERE fabrica = {$login_fabrica}
                        AND os = {$x_os}";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception(traduz("Erro ao reabrir a Ordem de Serviço %", null, null, [$x_os]));
                }

                $sql = "INSERT INTO tbl_os_status
                        (os, status_os, observacao, extrato, admin)
                        VALUES
                        ({$x_os}, 14, '{$x_obs}', {$extrato}, {$login_admin})";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception(traduz("Erro ao reabrir a Ordem de Serviço %", null, null, [$x_os]));
                }

                $sql = "UPDATE tbl_os_extra SET
                            extrato = NULL
                        WHERE os = {$x_os}";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception(traduz("Erro ao reabrir a Ordem de Serviço %", null, null, [$x_os]));
                }


                if($login_fabrica == 147){

                    $sql =" INSERT INTO tbl_extrato_lancamento (
                                         posto           ,
                                         fabrica         ,
                                         lancamento      ,
                                         historico       ,
                                         debito_credito  ,
                                         valor           ,
                                         automatico
                                     ) SELECT   posto,
                                                $login_fabrica,
                                                197,
                                                '".traduz('Débito da OS % recusada do extrato %', null, null, [$x_os, $extrato])."',
                                                'D',
                                                -$valor_os_total,
                                                true
                                        FROM tbl_os
                                        WHERE os = $x_os
                                        AND fabrica = $login_fabrica ";
                    $res = pg_query($con , $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception(traduz("Erro ao lançar avulso para o extrato , os : %", null, null, [$x_os]));
                    }

                   $sql =" INSERT INTO tbl_extrato_lancamento (
                                        posto           ,
                                        fabrica         ,
                                        lancamento      ,
                                        historico       ,
                                        debito_credito  ,
                                        valor           ,
                                        automatico      ,
                                        extrato
                                     ) SELECT   posto,
                                                $login_fabrica,
                                                198,
                                                '".traduz('Crédito da OS % recusada do extrato %', null, null, [$x_os, $extrato])."',
                                                'C',
                                                $valor_os_total,
                                                true ,
                                                {$extrato}
                                        FROM tbl_os
                                        WHERE os = $x_os
                                        AND fabrica = $login_fabrica ";
                    $res = pg_query($con , $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception(traduz("Erro ao creditar extrato  os : %", null, null, [$x_os]));
                    }

                    $sql = "SELECT SUM(tbl_extrato_lancamento.valor) as total,
                                        tbl_extrato.avulso as avulso
                            FROM    tbl_extrato_lancamento
                            INNER JOIN tbl_extrato using(extrato)
                            WHERE  tbl_extrato.extrato = $extrato
                                AND tbl_extrato.fabrica = $login_fabrica
                                AND tbl_extrato_lancamento.fabrica = $login_fabrica
                                GROUP BY avulso";
                    $res_lan = pg_query($con ,$sql);
                    $lancamento_extrato = pg_fetch_result($res_lan, 0, "total");
                    $lancamento_extrato_avulso = pg_fetch_result($res_lan, 0, "avulso");

                    if($lancamento_extrato == '0' or !strlen($lancamento_extrato) or empty($lancamento_extrato)){
                        $lancamento_extrato = 0;
                    }

                    if($lancamento_extrato_avulso == '0' or !strlen($lancamento_extrato_avulso) or empty($lancamento_extrato_avulso)){
                        $lancamento_extrato_avulso = 0;
                    }

                    $total_avulso = $lancamento_extrato_avulso + $lancamento_extrato;

                    $sql = "UPDATE tbl_extrato set avulso = $total_avulso where extrato = $extrato and fabrica = $login_fabrica";
                    $res = pg_query($con ,$sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception(traduz("Erro ao atualizar avulso do extrato."));
                    }
                }
            } catch(Exception $e) {
                $msg_erro = $e->getMessage();
            }

            if (!strlen(pg_last_error($con))) {
                $res = pg_query($con,"COMMIT TRANSACTION");

                $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato, null, "OS $x_os RETIRADA DO EXTRATO $extrato - MOTIVO: $x_obs");
            } else {
                $res = pg_query($con,"ROLLBACK TRANSACTION");
            }
    }

    pg_query($con, "BEGIN");

    if (empty($msg_erro)) {
        try {
            $sql = "SELECT
                        SUM(tbl_os.mao_de_obra) as total_mo,
                        SUM(tbl_os.qtde_km_calculada) as total_km,
                        SUM(tbl_os.pecas) as total_pecas,
                        SUM(tbl_os.valores_adicionais) as total_adicionais,
                        tbl_extrato.avulso
                    FROM tbl_os
                    INNER JOIN tbl_os_extra USING(os)
                    INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
                    WHERE tbl_os_extra.extrato = {$extrato}
                    GROUP BY tbl_extrato.avulso";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception(traduz("Erro ao reabrir Ordem de Serviço"));
            }

            $total_mo         = pg_fetch_result($res, 0, "total_mo");
            $total_km         = pg_fetch_result($res, 0, "total_km");
            $total_pecas      = pg_fetch_result($res, 0, "total_pecas");
            $total_adicionais = pg_fetch_result($res, 0, "total_adicionais");
            $avulso           = pg_fetch_result($res, 0, "avulso");

            if (!strlen($total_mo)) {
                $total_mo = 0;
            }

            if (!strlen($total_km)) {
                $total_km = 0;
            }

            if (!strlen($total_pecas)) {
                $total_pecas = 0;
            }

            if (!strlen($total_adicionais)) {
                $total_adicionais = 0;
            }

            if (!strlen($avulso)) {
                $avulso = 0;
            }

            $total = $total_mo + $total_km + $total_pecas + $total_adicionais + $avulso;

            $sql_ver_os = "SELECT count(1) as qtde_os_em_extrato FROM tbl_os_extra WHERE extrato = $extrato ";
            $res_ver_os = pg_query($con, $sql_ver_os);
            if(pg_num_rows($res_ver_os)> 0 ){
                $qtde_os_em_extrato = pg_fetch_result($res_ver_os, 0, qtde_os_em_extrato);
                if($qtde_os_em_extrato == 0 AND $login_fabrica != 138){
                    $sql_extrato_lancamento = "UPDATE tbl_extrato_lancamento SET extrato = null WHERE extrato = $extrato ";
                    $res_extrato_lancamento = pg_query($con, $sql_extrato_lancamento);
                }
            }else{
                $qtde_os_em_extrato = 0;
            }

            if ($total <= 0 && $qtde_os_em_extrato > 0 && $login_fabrica != 148) {
                throw new Exception(traduz("O valor do extrato não pode ser negativo ou 0"));
            } else {
                $sql = "UPDATE tbl_extrato SET
                            total           = {$total},
                            mao_de_obra     = {$total_mo},
                            pecas           = {$total_pecas},
                            deslocamento    = {$total_km},
                            valor_adicional = {$total_adicionais}
                        WHERE extrato = {$extrato}
                        AND fabrica = {$login_fabrica}";
                $res = pg_query($con, $sql);
                if (strlen(pg_last_error()) > 0) {
                    throw new Exception(traduz("Erro ao reabrir Ordem de Serviço"));
                }
            }
        } catch(Exception $e) {
            $msg_erro = $e->getMessage();
        }
    }

    if (!strlen($msg_erro)) {
        pg_query($con, "COMMIT");

        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);

        $link = $_COOKIE["link"];
        header ("Location: $link");
        exit;
    } else {
        pg_query($con, "ROLLBACK");
    }
}

if( strtolower($btn_acao) == "recusar" OR strtolower($btn_acao) == 'recusar_documento' OR strtolower($btn_acao) == 'recusada_pagamento')
{

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

    $qtde_os = $_POST["qtde_os"];
    $auditorLogOS = new AuditorLog();

    $array_os_geo = array();

    for( $k = 0 ; $k < $qtde_os; $k++ )
    {
        $res     = pg_query($con,"BEGIN TRANSACTION");
        $x_os  = trim($_POST["os_" . $k]);
        $x_obs = pg_escape_string(trim($_POST["obs_" . $k]));

        $x_motivo = trim($_POST["motivo_".$k]);

        $filtro = "$extrato AND os = $x_os ";
        $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$filtro );

        if (!strlen($x_obs) && !strlen($x_motivo)) {
            $msg_erro    = traduz(" Informe a observação na OS %. ", null, null, [$x_os]);
            $linha_erro  = $k;
            $select_acao = "RECUSAR";
        } else if (!strlen($x_obs) && strlen($x_motivo) > 0) {
            $sqlMotivo = "SELECT motivo FROM tbl_motivo_recusa WHERE fabrica = {$login_fabrica} AND motivo_recusa = {$x_motivo}";
            $resMotivo = pg_query($con, $sqlMotivo);
            $x_obs = pg_fetch_result($resMotivo, 0, "motivo");
        }

        #OS_TROCA - Excluida/Acumulada/Recusada o débito HD14648
        if( strlen($msg_erro) == 0 AND $login_fabrica == 1 ){
            $sql = "SELECT tbl_os_troca.os_troca    ,
                            tbl_os_troca.total_troca,
                            tbl_os.os               ,
                            tbl_os.sua_os
                        FROM tbl_os
                        JOIN tbl_os_troca USING(os)
                        WHERE os = $x_os";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) > 0){

                $sua_os_troca   = pg_fetch_result($res,0,sua_os);
                $os_sedex_troca = '';
                #troca
                $sql = "SELECT os_sedex
                            FROM tbl_os_sedex
                            WHERE extrato_destino = $extrato
                            AND sua_os_destino = '$sua_os_troca'; ";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
                    $os_sedex_troca = pg_fetch_result($res, 0,os_sedex);
                    #Sedex
                    $sql = "DELETE FROM tbl_extrato_extra_item WHERE os_sedex = $os_sedex_troca ";
                    $res = pg_query($con,$sql);
                    $msg_erro = pg_errormessage($con);

                    $sql = "DELETE FROM tbl_os_sedex WHERE os_sedex = $os_sedex_troca AND sua_os_destino = '$sua_os_troca';";
                    $res = pg_query($con,$sql);
                    $msg_erro = pg_errormessage($con);

                    $sql = "SELECT extrato_lancamento
                                FROM tbl_extrato_lancamento
                                WHERE extrato = $extrato
                                AND   os_sedex = $os_sedex_troca;";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0 AND strlen($msg_erro) == 0){
                        $extrato_lancamento_troca = pg_fetch_result($res,0,extrato_lancamento);
                        #extrato lançamento
                        $sql = "DELETE FROM tbl_extrato_extra_item WHERE extrato_lancamento = $extrato_lancamento_troca;";
                        $res = pg_query($con,$sql);
                        $msg_erro = pg_errormessage($con);

                        $sql = "DELETE FROM tbl_extrato_lancamento WHERE os_sedex = $os_sedex_troca AND extrato_lancamento = $extrato_lancamento_troca;";
                        $res = pg_query($con,$sql);
                        $msg_erro = pg_errormessage($con);

                    }
                }
            }

            /*OS GEO METAL - 83010*/
            $sql = "SELECT os_numero
                    FROM tbl_os
                    WHERE os = $x_os
                        and tipo_os= 13;";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) > 0){
                $os_numero= pg_fetch_result($res,0,os_numero);
                $array_os_geo[$os_numero]= $os_numero;
            }
        }
        if (strlen($msg_erro) == 0) {
            if($btn_acao == 'recusar_documento'){
                $sql_doc = "SELECT finalizada, data_fechamento FROM tbl_os WHERE os = $x_os LIMIT 1;";
                $res_doc = pg_query($con,$sql_doc);
                $msg_erro = pg_errormessage($con);

                $doc_finalizada      = pg_fetch_result($res_doc,0,finalizada);
                $doc_data_fechamento = pg_fetch_result($res_doc,0,data_fechamento);
            }

            if (!isset($novaTelaOs)) {

                if(strtolower($btn_acao) != 'recusada_pagamento') {
                    $sql = "SELECT fn_recusa_os(fabrica, extrato, os, '$x_obs')
                            FROM tbl_os
                            JOIN tbl_os_extra USING(os)
                            WHERE tbl_os.os = $x_os
                            AND   extrato   = $extrato
                            AND   fabrica   = $login_fabrica ;";
                    $res = pg_query($con,$sql);
                    $msg_erro = pg_errormessage($con);

                }else{
                    if($login_fabrica == 91){ //hd_chamado=2754972
                        if(strtoupper($btn_acao) == "RECUSADA_PAGAMENTO"){
                            $sql_up = "UPDATE tbl_os SET mao_de_obra = 0 WHERE os= $x_os AND fabrica = $login_fabrica and mao_de_obra > 0;
                            INSERT INTO tbl_os_status(os,status_os, admin, observacao,extrato) values ($x_os, 13,$login_admin, '$x_obs',$extrato) ;
                            SELECT fn_calcula_extrato($login_fabrica,$extrato) ; ";
                            $res_up =  pg_query($con, $sql_up);
                        }
                    }
                }
            } else {
                try {
                    $sql = "UPDATE tbl_os SET
                                pecas = 0,
                                qtde_km_calculada = 0,
                                mao_de_obra = 0,
                                valores_adicionais = 0
                            WHERE fabrica = {$login_fabrica}
                            AND os = {$x_os}";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception(traduz("Erro ao recusar a Ordem de Serviço %", null, null, [$x_os]));
                    }

                    $sql = "INSERT INTO tbl_os_status
                            (os, status_os, observacao, extrato)
                            VALUES
                            ({$x_os}, 13, '{$x_obs}', {$extrato})";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception(traduz("Erro ao recusar a Ordem de Serviço %", null, null, [$x_os]));
                    }

                    $sqlPosto = "SELECT sua_os, posto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$x_os}";
                    $resPosto = pg_query($con, $sqlPosto);

                    $posto  = pg_fetch_result($resPosto, 0, "posto");
                    $sua_os = pg_fetch_result($resPosto, 0, "sua_os");

                    $sql = "INSERT INTO tbl_comunicado (
                                fabrica,
                                posto,
                                obrigatorio_site,
                                tipo,
                                ativo,
                                descricao,
                                mensagem
                            ) VALUES (
                                {$login_fabrica},
                                {$posto},
                                true,
                                'Com. Unico Posto',
                                true,
                                substr('Ordem de Serviço {$sua_os} teve o pagamento de mão de obra reprovado pela fábrica',1,80),
                                '{$x_obs}'
                            )";
                    $res = pg_query($con, $sql);

                    $retira_extrato_do_faturamento = verificaExtratoExcluido('RECUSAR',$extrato);

                    if (!$retira_extrato_do_faturamento) {
                        throw new Exception(traduz("Erro ao recusar a Ordem de Serviço %", null, null, [$x_os]));
                    }

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception(traduz("Erro ao recusar a Ordem de Serviço %", null, null, [$x_os]));
                    }
                } catch(Exception $e) {
                    $msg_erro = $e->getMessage();
                }
            }

            $sql2 = "UPDATE tbl_os_status set admin = $login_admin
                                    WHERE extrato = $extrato
                                    AND   os      = $x_os
                                    AND   os_status in (SELECT os_status FROM tbl_os_status WHERE extrato = $extrato AND os = $x_os ORDER BY os_status DESC LIMIT 1); ";
            $res2 = pg_query($con,$sql2);
            $msg_erro = pg_errormessage($con);

            if(!in_array($login_fabrica, array(1,30,158))) {
                $sql = "SELECT fn_estoque_recusa_os($x_os,$login_fabrica,$login_admin);";
                $res = pg_query($con,$sql);
                $msg_erro = pg_errormessage($con);
            }

            if($btn_acao == 'recusar_documento' AND $login_fabrica == 1){
                $sql_doc2 = "SELECT os_status FROM tbl_os_status WHERE os = $x_os ORDER BY os_status DESC LIMIT 1;";
                $res_doc2 = pg_query($con,$sql_doc2);
                $msg_erro = pg_errormessage($con);

                $doc_os_status    = pg_fetch_result($res_doc2,0,os_status);

                $sql_doc = "UPDATE tbl_os_status SET status_os = 91 WHERE os = $x_os AND os_status = $doc_os_status;";
                $res_doc = pg_query($con,$sql_doc);
                $msg_erro = pg_errormessage($con);

            /*              $sql_doc = "UPDATE tbl_os SET finalizada = '$doc_finalizada', data_fechamento = '$doc_data_fechamento' WHERE os = $x_os;";
                            $res_doc = pg_query($con,$sql_doc);
                            $msg_erro = pg_errormessage($con);
            */
            }
        }

        if( strlen($msg_erro) == 0 ) // HD 52911
        {
            if( $login_fabrica == 45 )
            {
                # HD 53003
                $sqlGrAdmin = "UPDATE tbl_os_status set admin = $login_admin
                                    WHERE extrato = $extrato
                                    AND   os      = $x_os
                                    AND   os_status in (
                                    SELECT os_status
                                    FROM tbl_os_status
                                    WHERE extrato = $extrato
                                    AND os = $x_os
                                    ORDER BY os_status DESC LIMIT 1); ";
                $resGrAdmin = pg_query($con,$sqlGrAdmin);
                $msg_erro = pg_errormessage($con);

                $sql = "SELECT tbl_os.sua_os,contato_email
                        FROM tbl_os
                        JOIN tbl_posto_fabrica on tbl_os.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
                        WHERE os=$x_os";
                $res = @pg_query($con,$sql);
                $pr_sua_os        = @pg_fetch_result($res,0,sua_os);
                $pr_contato_email = @pg_fetch_result($res,0,contato_email);

                $sqlx= "SELECT email From tbl_admin WHERE admin = $login_admin";
                $resx = @pg_query($con, $sqlx);
                $admin_email = @pg_fetch_result($resx,0,email);

                if( $btn_acao == 'excluir'  ) $conteudo_acao = 'excluída';
                if( $btn_acao == 'acumular' ) $conteudo_acao = 'acumulada';
                if( $btn_acao == 'recusar'  ) $conteudo_acao = 'recusada';

                $destinatario = $pr_contato_email;
                $assunto      = " OS $pr_sua_os $conteudo_acao";
                $mensagem = "<center>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</center><br><br>";
                $mensagem .=  "At. Responsável,<br><br>A OS $pr_sua_os foi $conteudo_acao pelo seguinte motivo: <br> $x_obs. <br>";
                $mensagem .="Qualquer duvida contatar a sua atendente regional.<br>";
                $mensagem .="<b><font color='red'>NKS</font></b>";
                $body_top .= "Content-type: text/html;";
                if( strlen($mensagem) > 0 ) mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $body_top);
            }
        }

        if( strlen($msg_erro) == 0 AND $login_fabrica == 94){

            $sql = "SELECT tbl_os.posto, tbl_os.sua_os
                        FROM tbl_os
                        WHERE os=$x_os";
            $res = pg_query($con,$sql);
            $posto        = pg_fetch_result($res,0,posto);
            $sua_os       = pg_fetch_result($res,0,sua_os);
            $comunicado = "A O.S <b>$sua_os</b> foi recusada pelo seguinte motivo: <br> $x_obs";

            $sql = "INSERT INTO tbl_comunicado(
                                            mensagem,
                                            tipo,
                                            fabrica,
                                            obrigatorio_site,
                                            posto,
                                            ativo) VALUES (
                                            '$comunicado',
                                            'Comunicado Inicial',
                                            $login_fabrica,
                                            true,
                                            $posto,
                                            true)";
            $res = pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

        if (!strlen(pg_last_error($con))) {
            $res = pg_query($con,"COMMIT TRANSACTION");

            $teste = $auditorLogOS->retornaDadosSelect();
            $teste2 = $teste->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato, null, "OS $x_os RECUSADA DO EXTRATO $extrato - MOTIVO: $x_obs");
        } else {
            $res = pg_query($con,"ROLLBACK TRANSACTION");
        }
    }

    $res = pg_query($con,"BEGIN TRANSACTION");

    /* OS GEO - 83010 */
    //print_r($array_os_geo);
    if( strlen($msg_erro) == 0 and $login_fabrica ==1 and count($array_os_geo)>0 )
    {
        foreach( $array_os_geo as $i => $os_revenda )
        {
            $sql = "SELECT tbl_os_revenda_item.os_lote as os,
                        tbl_os_extra.extrato
                    FROM tbl_os_revenda
                    JOIN tbl_os_revenda_item using(os_revenda)
                    JOIN tbl_os_extra on tbl_os_extra.os = tbl_os_revenda_item.os_lote
                    WHERE tbl_os_revenda.fabrica =$login_fabrica
                        AND tbl_os_revenda.os_revenda = $os_revenda
                        AND tbl_os_revenda.extrato_revenda =$extrato
                        AND tbl_os_extra.extrato = $extrato";
            //echo "sql: $sql";
            $res = pg_query($con,$sql);

            if (pg_num_rows($res) > 0) {
                $msg_erro.="Para a OS GEO: $os_revenda deve ser recusado todos os produtos juntos.<br>";
            }else{
                $sql = "UPDATE tbl_os_revenda
                            SET extrato_revenda = null
                        WHERE os_revenda = $os_revenda
                            AND extrato_revenda =$extrato;";
                //echo "sql: $sql";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);
            }
        }

        $sql = "SELECT posto
                FROM   tbl_extrato
                WHERE  extrato = $extrato
                AND    fabrica = $login_fabrica";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
        $posto = pg_fetch_result($res,0,posto);
        /* CONFIRMAR COM FABIOLA SE RECALCULA AQUI ??? */
        if( pg_num_rows($res) > 0 AND strlen($msg_erro) == 0 ){
            $sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
            $res = @pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);
        }
    }

    if (isset($novaTelaOs)) {
		if (file_exists("/www/assist/www/classes/Posvenda/Fabricas/_{$login_fabrica}/Extrato.php")) {
			include_once "/www/assist/www/classes/Posvenda/Fabricas/_{$login_fabrica}/Extrato.php";
			$extratoClassName = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Extrato';
			if($login_fabrica == 158) $extratoClassName = 'ExtratoImbera';
			$extratoClassePropria = true;
		}

		if($extratoClassePropria == true && $login_fabrica != 148){
			$classExtrato = new $extratoClassName($login_fabrica);
		}else{
            include "../classes/Posvenda/Extrato.php";
			$classExtrato = new Extrato($login_fabrica,$extrato);
		}
		$classExtrato->calcula($extrato);
		$sqle = "select total, avulso ,codigo , posto from tbl_extrato left join tbl_extrato_agrupado using(extrato) where tbl_extrato.extrato = $extrato "; 
		$rese = pg_query($con, $sqle);
		if(pg_num_rows($rese) > 0) {
			$total = pg_fetch_result($rese, 0,'total'); 
			$avulso = pg_fetch_result($rese, 0,'avulso'); 
			$codigo = pg_fetch_result($rese, 0,'codigo'); 
			$posto  = pg_fetch_result($rese, 0,'posto'); 
			
			if(!empty($codigo)) {
				$classExtrato->calcula($extrato, $posto,$codigo,$con);

			}

        if (($total - $avulso) == 0) {

                /*HD - 6356801*/
                $sql = "UPDATE tbl_faturamento_item
                        SET extrato_devolucao = NULL
                        WHERE tbl_faturamento_item.extrato_devolucao = {$extrato}";
                $res = pg_query($con, $sql);

                $sql = "UPDATE tbl_faturamento
                        SET extrato_devolucao = NULL
                        WHERE tbl_faturamento.extrato_devolucao = {$extrato}";
                $res = pg_query($con, $sql);

                $sql = "DELETE FROM tbl_extrato_lgr WHERE extrato = {$extrato}";
                $res = pg_query($con, $sql);
            }

            if (strlen(pg_last_error()) > 0) {
                $msg_erro = "Erro ao totalizar Extrato $extrato";
            }
    	} else {
      	$msg_erro = "Erro ao totalizar Extrato $extrato";
      }
    }else{
        if (strlen($msg_erro) == 0) {
            $sql = "SELECT posto
                FROM   tbl_extrato
                WHERE  extrato = $extrato
                AND    fabrica = $login_fabrica";
            $res = @pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);

            if (pg_num_rows($res) > 0) {
                if (@pg_fetch_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){
                    $sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
                    $res = @pg_query($con,$sql);
                    $msg_erro = pg_errormessage($con);
                }
            }
        }
    }

    if (strlen($msg_erro) == 0 && isset($novaTelaOs)) { 
        $sql_ver_os = "SELECT count(1) as qtde_os_em_extrato FROM tbl_os_extra WHERE extrato = $extrato ";
        $res_ver_os = pg_query($con, $sql_ver_os);
        if (pg_num_rows($res_ver_os) > 0) {
            if (pg_fetch_result($res_ver_os, 0, 'qtde_os_em_extrato') == 0) {
                $sql = "UPDATE tbl_extrato SET
                            fabrica = 0
                        WHERE extrato = {$extrato};

                        UPDATE tbl_extrato_lancamento SET
                            extrato = null
                        WHERE extrato = $extrato ";
                $res = pg_query($con, $sql);
                if (pg_last_error()) {
                    $msg_erro = "Erro ao excluir o extrato";
                }
            }
        }
    }

    if (strlen($msg_erro) == 0) {
        /* HD-3291983 */
        if ($login_fabrica == 42) {
            $sql = "UPDATE tbl_extrato SET exportado = NULL WHERE extrato = $extrato";
            $res = pg_query($con, $sql);
        }
        $res = pg_query($con,"COMMIT TRANSACTION");

        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);

        $link = $_COOKIE["link"];
        header ("Location: $link?msg_aviso=$msg_aviso&extrato=$extrato");
        exit;
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}

if( in_array($login_fabrica, array(11,66,172)) ) // Recusas de OS´s
{
    if( strtoupper($select_acao) <> "RECUSAR" AND strtoupper($select_acao) <> "EXCLUIR" AND strtoupper($select_acao) <> "ACUMULAR" AND strlen($select_acao) > 0 )
    {
        $os     = $_POST["os"];
        $sua_os = $_POST["sua_os"];
        $kk     = 0;

        $auditorLog = new AuditorLog();
        $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

        $auditorLogOS = new AuditorLog();
        $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$extrato );

        $res = pg_query($con,"BEGIN TRANSACTION");

        $sql         = "SELECT motivo, status_os from tbl_motivo_recusa where motivo_recusa = $select_acao";
        $res         = pg_query($con, $sql);
        $select_acao = pg_fetch_result($res,0,motivo);
        $status_os   = pg_fetch_result($res,0,status_os);

        if(strlen($status_os) == 0){
            $msg_erro = "Escolha o motivo da Recusa da OS";
        }

        if( $status_os == 13 OR $status_os == 14 AND strlen($msg_erro) == 0 )
        {
            for( $k = 0; $k < $contador; $k++ )
            {
                if (strlen($msg_erro) > 0) {
                    $os[$k]     = $_POST["os_" . $kk];
                    $sua_os[$k] = $_POST["sua_os_" . $kk];
                }

                if (strlen($os[$k]) > 0) {
                    $select_acao = RemoveAcentos($select_acao);
                    $select_acao = strtoupper($select_acao);
                    $kk++;

                    if (strlen($msg_erro) == 0) {
                        if($status_os == 13){
                            $sql = "SELECT fn_recusa_os($login_fabrica, $extrato, $os[$k], '$select_acao');";
                        }else{
                            $sql = "SELECT fn_acumula_os($login_fabrica, $extrato, $os[$k], '$select_acao');";
                        }
                        $res = @pg_query($con,$sql);
                        $msg_erro = pg_errormessage($con);

                        $sql2 = "UPDATE tbl_os_status set admin = $login_admin
                                    WHERE extrato = $extrato
                                    AND   os      = $os[$k]
                                    AND   os_status in (SELECT os_status FROM tbl_os_status WHERE extrato = $extrato AND os = $os[$k] ORDER BY os_status DESC LIMIT 1); ";
                        $res2 = pg_query($con,$sql2);
                        $msg_erro = pg_errormessage($con);
                    }
                }
            }

            if (strlen($msg_erro) == 0) {
                $sql = "SELECT posto
                        FROM   tbl_extrato
                        WHERE  extrato = $extrato
                        AND    fabrica = $login_fabrica";
                $res = @pg_query($con,$sql);
                $msg_erro = pg_errormessage($con);

                if (pg_num_rows($res) > 0) {
                    if (@pg_fetch_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){
                        //hd 10185 - trocado calcula por totaliza
                        //$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
                        #$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
                        #$res = pg_query($con,$sql);
                        #$total_os_extrato = pg_fetch_result($res,0,0);
                        #HD15716
                        if( in_array($login_fabrica, array(11,172)) ){
                            $sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
                            $res = pg_query($con,$sql);
                            $msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
                        }else{
                            $sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
                            $res = @pg_query($con,$sql);
                            $msg_erro = pg_errormessage($con);
                        }
                    }
                }
            }

            if (strlen($msg_erro) == 0) {
                $res = pg_query($con,"COMMIT TRANSACTION");

                $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
                $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato);

                $link = $_COOKIE["link"];
                header ("Location: $link?msg_aviso=$msg_aviso&extrato=$extrato");
                exit;
            }else{
                $res = pg_query($con,"ROLLBACK TRANSACTION");
            }
            $select_acao = '';
        }

        $kk = 0;

        if($status_os == 15 AND strlen($msg_erro) == 0){

            $res = pg_query($con,"BEGIN TRANSACTION");

            for ($k = 0 ; $k < $contador ; $k++) {
                if (strlen($msg_erro) > 0) {
                    $os[$k]     = $_POST["os_" . $kk];
                    $sua_os[$k] = $_POST["sua_os_" . $kk];
                    $kk++;
                }
                if (strlen($os[$k]) > 0) {
                    $sql = "INSERT INTO tbl_os_status (
                                    extrato    ,
                                    os         ,
                                    observacao ,
                                    status_os  ,
                                    admin
                                ) VALUES (
                                    $extrato       ,
                                    $os[$k]        ,
                                    '$select_acao' ,
                                    15             ,
                                    $login_admin
                                );";
                    $res = @pg_query($con,$sql);
                    $msg_erro = pg_errormessage($con);

                    if (strlen($msg_erro) == 0) {
                            $sql = "UPDATE tbl_os_extra SET extrato = null
                                    WHERE  tbl_os_extra.os      = $os[$k]
                                    AND    tbl_os_extra.extrato = $extrato
                                    AND    tbl_os_extra.os      = tbl_os.os
                                    AND    tbl_os_extra.extrato = tbl_extrato.extrato
                                    AND    tbl_extrato.extrato  = tbl_extrato_extra.extrato
                                    AND    tbl_extrato_extra.baixado IS NULL
                                    AND    tbl_os.fabrica  = $login_fabrica;";
                        $res = @pg_query($con,$sql);
                        $msg_erro = pg_errormessage($con);
                    }

                    if (strlen($msg_erro) == 0) {
                            $sql = "UPDATE tbl_os SET excluida = true
                                    WHERE  tbl_os.os           = $os[$k]
                                    AND    tbl_os.fabrica      = $login_fabrica;";
                        $res = @pg_query($con,$sql);
                        $msg_erro = pg_errormessage($con);

                        #158147 Paulo/Waldir desmarcar se for reincidente
                        $sql = "SELECT fn_os_excluida_reincidente($xxos,$login_fabrica)";
                        $res = pg_query($con, $sql);


                    }
                    if ((strlen($msg_erro) == 0) and $fabrica == 51 ) {
                        //samuel colocou 12-12-2008 pq Ronaldo perguntou pq a OS excluída gerou embarque. EX os OS 6197121 - Excluida na consulta distrib (embarque 39363).
                        $distribuidor      = 4311; //Distribuidor Telecontrol
                        // ATENCAO - A rotina abaixo pede como parametro o distribuidor, mas não utiliza, mesmo assim estamos enviando 4311 porque e o distribuidor Telecontrol
                        $fabrica           = 51; //Gama Italy
                        $motivo            = "OS excluída pelo fabricante no extrato";

                        $sql_os = "SELECT DISTINCT tbl_os.os ,
                                tbl_os.posto,
                                tbl_os_item.os_item,
                                tbl_os_item.peca,
                                tbl_os_item.pedido
                                FROM tbl_os
                                JOIN tbl_posto_fabrica         ON tbl_posto_fabrica.fabrica = tbl_os.fabrica and tbl_posto_fabrica.posto = tbl_os.posto
                                JOIN tbl_os_produto USING (os)
                                JOIN tbl_os_item    USING (os_produto)
                                JOIN tbl_peca       USING (peca)
                                LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido
                                            AND tbl_faturamento_item.peca = tbl_os_item.peca
                                LEFT JOIN tbl_pedido_item      ON tbl_pedido_item.pedido = tbl_os_item.pedido
                                                              AND tbl_pedido_item.peca   = tbl_os_item.peca
                                                              AND tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                                LEFT JOIN tbl_pedido           ON tbl_pedido.pedido = tbl_pedido_item.pedido
                                LEFT JOIN tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
                                WHERE tbl_os.fabrica = 51
                                AND tbl_faturamento.nota_fiscal IS NULL
                                AND tbl_os_item.pedido IS NOT NULL
                                AND tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde
                                AND tbl_os.troca_garantia is not true
                                AND tbl_os.os = $os[$k]";
                        $res_os = pg_query($con,$sql_os);

                        if(pg_num_rows($res_os)>0)
                        {
                            for($j=0; $j<pg_num_rows($res_os); $j++){
                                $os        = trim(pg_fetch_result($res_os,$j,os));
                                $os_item   = trim(pg_fetch_result($res_os,$j,os_item));
                                $posto     = trim(pg_fetch_result($res_os,$j,posto));
                                $peca      = trim(pg_fetch_result($res_os,$j,peca));
                                $pedido    = trim(pg_fetch_result($res_os,$j,pedido));

                                $sql_ja = "SELECT count(*) as ja
                                    FROM tbl_pedido_cancelado
                                    WHERE pedido = $pedido
                                    AND posto = $posto
                                    AND fabrica = 51
                                    AND os = $os
                                    AND peca = $peca";

                                $res_ja = pg_query($con, $sql_ja);
                                $ja     = 0;

                                if(pg_num_rows($res_ja)>0){
                                    $ja = pg_fetch_result($res_ja,0,ja);
                                }

                                if($ja==0){
                                    $sql ="SELECT fn_pedido_cancela_garantia($distribuidor,$fabrica,$pedido,$peca,$os_item, '$motivo',$login_admin)";
                                    $res = pg_query ($con,$sql);
                                }
                            }
                        }
                    }
                }
            }

            if (strlen($msg_erro) == 0) {
                $sql = "SELECT posto
                        FROM   tbl_extrato
                        WHERE  extrato = $extrato
                        AND    fabrica = $login_fabrica";
                $res = @pg_query($con,$sql);
                $msg_erro = pg_errormessage($con);

                if (pg_fetch_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){

                    //hd 10185 - trocado calcula por totaliza
                    //$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";

                    #$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
                    #$res = pg_query($con,$sql);
                    #$total_os_extrato = pg_fetch_result($res,0,0);

                    #HD15716
                    if( in_array($login_fabrica, array(11,172)) ){
                        $sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
                        $res = pg_query($con,$sql);
                        $msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
                    }else{
                        $sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
                        $res = @pg_query($con,$sql);
                        $msg_erro = pg_errormessage($con);
                    }
                }
            }

            if (strlen($msg_erro) == 0) {
                $res = pg_query($con,"COMMIT TRANSACTION");

                $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
                $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato);

                $link = $_COOKIE["link"];
                header ("Location: $link?msg_aviso=$msg_aviso&extrato=$extrato");
                exit;
            }else{
                $res = pg_query($con,"ROLLBACK TRANSACTION");
            }
            $select_acao = '';
        }
    }
}

// HD 406128
if( $btn_acao == "zerar" )
{

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

    //@todo
    $qtde_os = $_POST["qtde_os"];
    $res     = pg_query($con,"BEGIN TRANSACTION");

    $array_os_geo = array();
    $msg_erro     = array();

    for( $k = 0 ; $k < $qtde_os; $k++ )
    {
        $x_os  = trim($_POST["os_" . $k]);
        $x_obs = trim($_POST["obs_" . $k]);

        if (strlen($x_obs) == 0) {
            $msg_erro[]    = " Informe a observação na OS $x_os. ";
            $linha_erro  = $k;
            $select_acao = "ZERAR";
            continue;
        }

        /*
        $sql = "UPDATE tbl_os
                SET mao_de_obra = 0, admin = $login_admin
                WHERE os = $x_os AND fabrica = $login_fabrica";

        $res = pg_query($con,$sql);
        */
        $sql = "INSERT INTO tbl_os_status (os,status_os,admin,observacao,extrato)
                VALUES($x_os, 90, $login_admin,'$x_obs',$extrato);";

        $res = pg_query($con,$sql);

        if ($login_fabrica == 101) {
            $sql2 = "UPDATE tbl_os
                SET mao_de_obra = 0
                WHERE os = $x_os AND fabrica = $login_fabrica";

            $res2 = pg_query($con,$sql2);
            if (pg_last_error($con)) {
                $msg_erro[] = 'Erro ao zerar a mão de obra.';
            }

        }
    }
    if( count($msg_erro) != $qtde_os && $qtde_os > 0 )
    {

        $sql = "SELECT fn_calcula_extrato($login_fabrica, $extrato);";
        $res = pg_query($con,$sql);
        $query = pg_query($con,"COMMIT TRANSACTION");

        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
    }
}

if( $btn_acao == "acumular" )
{
    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

    $auditorLogOS = new AuditorLog();
    $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$extrato );

    $qtde_os = $_POST["qtde_os"];

    $array_os_geo = array();
    $array_os     = array();

    if (isset($novaTelaOs)) {
        $sql = "SELECT * FROM tbl_extrato_financeiro WHERE extrato = {$extrato}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $msg_erro = "Extrato {$extrato} já enviado para o Financeiro";
        }
    }

    if (!strlen($msg_erro)) {
        for( $k=0; $k < $qtde_os; $k++ )
        {
            $res     = pg_query($con,"BEGIN TRANSACTION");

            $x_os  = trim($_POST["os_" . $k]);
            $x_obs = trim($_POST["obs_" . $k]);
            $x_motivo = trim($_POST["motivo_".$k]);

            $filtro = "$extrato AND os = $x_os ";
            $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$filtro );

            if (!strlen($x_obs) && !strlen($x_motivo)) {
                $msg_erro    = " Informe a observação na OS $x_os. ";
                $linha_erro  = $k;
                $select_acao = "ACUMULAR";
            } else if (!strlen($x_obs) && strlen($x_motivo) > 0) {
                $sqlMotivo = "SELECT motivo FROM tbl_motivo_recusa WHERE fabrica = {$login_fabrica} AND motivo_recusa = {$x_motivo}";
                $resMotivo = pg_query($con, $sqlMotivo);
                $x_obs = pg_fetch_result($resMotivo, 0, "motivo");
            }

            $array_os[] = (object)array("os" => $x_os, "obs" => $x_obs);

            #OS_TROCA - Excluida/Acumulada/Recusada o débito HD14648
            if(strlen($msg_erro) == 0 AND $login_fabrica == 1){
                $sql = "SELECT tbl_os_troca.os_troca    ,
                                tbl_os_troca.total_troca,
                                tbl_os.os               ,
                                tbl_os.sua_os
                            FROM tbl_os
                            JOIN tbl_os_troca USING(os)
                            WHERE os = $x_os";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
                    $sua_os_troca   = pg_fetch_result($res,0,sua_os);
                    $os_sedex_troca = '';

                    #troca
                    $sql = "SELECT os_sedex
                                FROM tbl_os_sedex
                                WHERE extrato_destino = $extrato
                                AND sua_os_destino = '$sua_os_troca'; ";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0){
                        $os_sedex_troca = pg_fetch_result($res, 0,os_sedex);
                        #Sedex
                        $sql = "UPDATE tbl_os_sedex SET extrato_destino = NULL WHERE os_sedex = $os_sedex_troca AND sua_os_destino = '$sua_os_troca';";
                        $res = pg_query($con,$sql);
                        $msg_erro = pg_errormessage($con);

                        $sql = "SELECT extrato_lancamento
                                    FROM tbl_extrato_lancamento
                                    WHERE extrato = $extrato
                                    AND   os_sedex = $os_sedex_troca;";
                        $res = pg_query($con,$sql);

                        if(pg_num_rows($res) > 0 AND strlen($msg_erro) == 0){
                            $extrato_lancamento_troca = pg_fetch_result($res,0,extrato_lancamento);
                            #Extrato lançamento
                            $sql = "DELETE FROM tbl_extrato_extra_item WHERE extrato_lancamento = $extrato_lancamento_troca;";
                            $res = pg_query($con,$sql);
                            $msg_erro = pg_errormessage($con);

                            $sql = "DELETE FROM tbl_extrato_lancamento WHERE os_sedex = $os_sedex_troca AND extrato_lancamento = $extrato_lancamento_troca;";
                            $res = pg_query($con,$sql);
                            $msg_erro = pg_errormessage($con);
                        }
                    }
                }

                /*OS GEO METAL - 83010*/
                $sql = "SELECT os_numero
                        FROM tbl_os
                        WHERE os = $x_os
                            and tipo_os= 13;";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
                    $os_numero= pg_fetch_result($res,0,os_numero);
                    $array_os_geo[$os_numero]= $os_numero;
                }
            }

            if (strlen($msg_erro) == 0) {
                if (isset($novaTelaOs)) {
                    try {
                        $sql = "INSERT INTO tbl_os_status
                                (os, status_os, observacao, extrato, admin)
                                VALUES
                                ({$x_os}, 14, '{$x_obs}', {$extrato}, {$login_admin})";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao acumular a ordem de serviço {$x_os}");
                        }

                        $sql = "UPDATE tbl_os_extra SET extrato = NULL WHERE os = {$x_os}";
                        $res = pg_query($con, $sql);

                        $retira_extrato_do_faturamento = verificaExtratoExcluido('ACUMULAR',$extrato);

                        if (!$retira_extrato_do_faturamento) {
                            throw new Exception("Erro ao acumular Ordem de Serviço");
                        }

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao acumular a ordem de serviço {$x_os}");
                        }
                    } catch(Exception $e) {
                        $msg_erro = $e->getMessage();
                    }

                } else {
                    $sql = "SELECT fn_acumula_os($login_fabrica, $extrato, $x_os, '$x_obs');";
                    $res = @pg_query($con,$sql);
                    $msg_erro = pg_errormessage($con);

                    $sql_upd = "UPDATE tbl_os_status SET admin = $login_admin WHERE os = $x_os and status_os = 14 and admin isnull";
                    $res_upd = pg_query($con, $sql_upd);
                }

            }

            if( strlen($msg_erro) == 0 ) // HD 52911
            {
                if( $login_fabrica == 45 )
                {
                    # HD 53003
                    $sqlGrAdmin = "UPDATE tbl_os_status set admin = $login_admin
                                        WHERE extrato = $extrato
                                        AND   os      = $x_os
                                        AND   os_status in (
                                        SELECT os_status
                                        FROM tbl_os_status
                                        WHERE extrato = $extrato
                                        AND os = $x_os
                                        ORDER BY os_status DESC LIMIT 1); ";
                    $resGrAdmin = pg_query($con,$sqlGrAdmin);
                    $msg_erro = pg_errormessage($con);

                    $sql = "SELECT tbl_os.sua_os,contato_email
                            FROM tbl_os
                            JOIN tbl_posto_fabrica on tbl_os.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
                            WHERE os=$x_os";
                    $res = @pg_query($con,$sql);
                    $pr_sua_os        = @pg_fetch_result($res,0,sua_os);
                    $pr_contato_email = @pg_fetch_result($res,0,contato_email);

                    $sqlx= "SELECT email From tbl_admin WHERE admin = $login_admin";
                    $resx = @pg_query($con,$sqlx);
                    $admin_email = @pg_fetch_result($resx,0,email);

                    if( $btn_acao == 'excluir'  ) $conteudo_acao = 'excluída';
                    if( $btn_acao == 'acumular' ) $conteudo_acao = 'acumulada';
                    if( $btn_acao == 'recusar'  ) $conteudo_acao = 'recusada';

                    $destinatario = $pr_contato_email;
                    $assunto      = " OS $pr_sua_os $conteudo_acao";
                    $mensagem = "<center>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</center><br><br>";
                    $mensagem .=  "At. Responsável,<br><br>A OS $pr_sua_os foi $conteudo_acao pelo seguinte motivo: <br> $x_obs. <br>";
                    $mensagem .="Qualquer duvida contatar a sua atendente regional.<br>";
                    $mensagem .="<b><font color='red'>NKS</font></b>";
                    $body_top .= "Content-type: text/html;";
                    if( strlen($mensagem) > 0 ) mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $body_top);
                }
            }
            if (!strlen(pg_last_error($con))) {
                $res = pg_query($con,"COMMIT TRANSACTION");

                $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato, null, "OS $x_os ACUMULADA PARA O PR<D3>XIMO EXTRATO - MOTIVO: $x_obs");
            } else {
                $res = pg_query($con,"ROLLBACK TRANSACTION");
            }
        }
    }

    $res = pg_query($con,"BEGIN TRANSACTION");

    // HD-959039
    if (strlen($msg_erro) == 0) {
        comunicar_acumula_os($array_os, $extrato, $login_fabrica);
    }

    if( strlen($msg_erro) == 0 )
    {
        if( strlen($msg_erro) == 0 and $login_fabrica == 1 and count($array_os_geo) > 0 )
        {
            foreach( $array_os_geo as $i => $os_revenda )
            {
                $sql = "SELECT tbl_os_revenda_item.os_lote as os,
                            tbl_os_extra.extrato
                        FROM tbl_os_revenda
                        JOIN tbl_os_revenda_item using(os_revenda)
                        JOIN tbl_os_extra on tbl_os_extra.os = tbl_os_revenda_item.os_lote
                        WHERE tbl_os_revenda.fabrica =$login_fabrica
                            AND tbl_os_revenda.os_revenda = $os_revenda
                            AND tbl_os_revenda.extrato_revenda =$extrato
                            AND tbl_os_extra.extrato = $extrato";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {
                    $msg_erro.="Para a OS GEO: $os_revenda deve ser ACUMULADO todos os produtos juntos.<br>";
                }else{
                    $sql = "UPDATE tbl_os_revenda
                                SET extrato_revenda = null
                            WHERE os_revenda = $os_revenda
                                AND extrato_revenda =$extrato;";
                    #echo "sql: $sql";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }
            }
        }

        $sql = "SELECT posto
                FROM   tbl_extrato
                WHERE  extrato = $extrato
                AND    fabrica = $login_fabrica";
        $res = @pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);


        if( pg_num_rows($res) > 0 )
        {
            if( @pg_fetch_result($res, 0, posto) > 0 AND strlen($msg_erro) == 0)
            {
                #$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
                #$res = pg_query($con,$sql);
                #$total_os_extrato = pg_fetch_result($res,0,0);

                #HD15716
                if( in_array($login_fabrica, array(11,172)) ){
                    $sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
                    $res = pg_query($con,$sql);
                    $msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
                }else{
                    if (isset($novaTelaOs)) {
                        try {
                            $sql = "SELECT
                                        SUM(tbl_os.mao_de_obra) as total_mo,
                                        SUM(tbl_os.qtde_km_calculada) as total_km,
                                        SUM(tbl_os.pecas) as total_pecas,
                                        SUM(tbl_os.valores_adicionais) as total_adicionais,
                                        tbl_extrato.avulso
                                    FROM tbl_os
                                    INNER JOIN tbl_os_extra USING(os)
                                    INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
                                    WHERE tbl_os_extra.extrato = {$extrato}
                                    AND tbl_extrato.fabrica = {$login_fabrica}
                                    GROUP BY tbl_extrato.avulso";
                            $res = pg_query($con, $sql);
                            $total_os = pg_num_rows($res) ;
                            if (strlen(pg_last_error()) > 0) {
                                throw new Exception("Erro ao acumular Ordem de Serviço");
                            }
                            $total_mo         = pg_fetch_result($res, 0, "total_mo");
                            $total_km         = pg_fetch_result($res, 0, "total_km");
                            $total_pecas      = pg_fetch_result($res, 0, "total_pecas");
                            $total_adicionais = pg_fetch_result($res, 0, "total_adicionais");
                            $avulso           = pg_fetch_result($res, 0, "avulso");

                            if (!strlen($total_mo)) {
                                $total_mo = 0;
                            }

                            if (!strlen($total_km)) {
                                $total_km = 0;
                            }

                            if (!strlen($total_pecas)) {
                                $total_pecas = 0;
                            }

                            if (!strlen($total_adicionais)) {
                                $total_adicionais = 0;
                            }

                            if (!strlen($avulso)) {
                                $avulso = 0;
                            }

                            $total = $total_mo + $total_km + $total_pecas + $total_adicionais + $avulso ;


                            if ($total <= 0 and $total_os > 0 ) {
                                throw new Exception("O valor do extrato não pode ser negativo ou 0");
                            } else {
                                $sql = "UPDATE tbl_extrato SET
                                            total           = {$total},
                                            mao_de_obra     = {$total_mo},
                                            pecas           = {$total_pecas},
                                            deslocamento    = {$total_km},
                                            valor_adicional = {$total_adicionais}
                                        WHERE extrato = {$extrato}";
                                $res = pg_query($con, $sql);

                                if ($total_os == 0 AND ($login_fabrica != 138 OR ($login_fabrica == 138 AND $avulso == 0) ) ) {
                                                    
                                    $sql = "UPDATE tbl_extrato SET
                                            fabrica = 0
                                            WHERE extrato = {$extrato};

                                            UPDATE tbl_extrato_lancamento SET
                                                extrato = null
                                            WHERE extrato = $extrato ";
                                    $res = pg_query($con, $sql);
                                }
                                if (strlen(pg_last_error()) > 0) {
                                    throw new Exception("Erro ao totalizar Extrato $extrato");
                                }
                            }

                        } catch (Exception $e) {
                            $msg_erro = $e->getMessage();
                        }
                    } else {
                        $sql = "SELECT fn_totaliza_extrato ($login_fabrica, $extrato);";
                        //retirado por Sono e Samuel pois não há necessidade de atribuir os valores novamente as OSs
                        //$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
                        $res = @pg_query($con,$sql);
                        $msg_erro = pg_errormessage($con);
                    }
                }
            }
        }
    }

    if (strlen($msg_erro) == 0) {
        /* HD-3291983 */
        if ($login_fabrica == 42) {
            $sql = "UPDATE tbl_extrato SET exportado = NULL WHERE extrato = $extrato";
            $res = pg_query($con, $sql);
        }
        $res = pg_query($con,"COMMIT TRANSACTION");
        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
        $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato);

        $link = $_COOKIE["link"];
        header ("Location: $link?msg_aviso=$msg_aviso");
        exit;
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}

if( $btn_acao == "acumulartudo" )
{
    if( strlen($extrato) > 0 )
    {
        $auditorLog = new AuditorLog();
        $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

        $res = pg_query($con,"BEGIN TRANSACTION");

        $sql = "SELECT  os     ,
                        extrato
                INTO TEMP TABLE tmp_acumula_extrato_$login_fabrica
                FROM tbl_os_extra
                JOIN tbl_extrato USING(extrato)
                WHERE extrato = $extrato
                AND fabrica   = $login_fabrica;";
        $res = pg_query($con,$sql);

        $sql = "SELECT fn_acumula_extrato ($login_fabrica, $extrato);";
        $res = @pg_query ($con,$sql);
        $msg_erro = pg_errormessage($con);

        $sql = "UPDATE tbl_os_status SET admin = $login_admin
                FROM   tmp_acumula_extrato_$login_fabrica
                WHERE  tbl_os_status.os        = tmp_acumula_extrato_$login_fabrica.os
                AND    tbl_os_status.extrato   = tmp_acumula_extrato_$login_fabrica.extrato;";
        $res = pg_query($con,$sql);

        if ($login_fabrica==45) {
            if (strlen($msg_erro) == 0) {
                $destinatario = $pr_contato_email;
                $assunto      = " Extrato $extrato";
                $mensagem = "<center>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</center><br><br>";
                $mensagem .=  "At. Responsável,<br><br>A OSs do extrato $extrato foram acumuladas para o próximo mês. <br>";
                $mensagem .="<b><font color='red'>NKS</font></b>";
                $body_top .= "Content-type: text/html;";
                if(strlen($mensagem)>0) mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $body_top);
            }
        }

        if (strlen($msg_erro) == 0) {
            $res = pg_query($con,"COMMIT TRANSACTION");

            $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);

            $link = $_COOKIE["link"];
            header ("Location: $link?msg_aviso=$msg_aviso");
            exit;
        }else{
            $res = pg_query($con,"ROLLBACK TRANSACTION");
        }
    }
}

if( $btn_acao == "cancelar_extrato" ) //24982 - 11/7/2008 - 46967 17/10/2008
{
    if( strlen($extrato) > 0 )
    {
        $auditorLog = new AuditorLog();
        $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

        $auditorLogOS = new AuditorLog();
        $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$extrato );

        $res = pg_query($con,"BEGIN TRANSACTION");

        //EXCLUI A BAIXA NO EXTRATO
        $sql = "SELECT extrato_pagamento
                FROM tbl_extrato_pagamento
                JOIN tbl_extrato USING(extrato)
                WHERE tbl_extrato_pagamento.extrato = $extrato;";
        #echo $sql."<BR>";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res)>0){
            $extrato_pagamento = pg_fetch_result($res, 0, extrato_pagamento);

            $sql="DELETE FROM tbl_extrato_pagamento WHERE extrato_pagamento = $extrato_pagamento AND extrato=$extrato;";
            #echo $sql."<BR>";
            $res = pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

        //TIRA O AVULSO DO EXTRATO
        if (strlen($msg_erro) == 0) {
            $sqlA = "SELECT  tbl_extrato_lancamento.extrato_lancamento,
                            tbl_extrato_lancamento.extrato
                    FROM    tbl_extrato_lancamento
                    JOIN    tbl_extrato ON tbl_extrato.extrato = tbl_extrato_lancamento.extrato
                    AND tbl_extrato.fabrica = $login_fabrica
                    WHERE   tbl_extrato_lancamento.extrato = $extrato
                    AND     tbl_extrato_lancamento.fabrica = $login_fabrica;";
            $resA = pg_query($con,$sqlA);

            if(pg_num_rows($resA)>0){
                for($z=0; $z<pg_num_rows($resA); $z++){
                    $extrato_lancamento = pg_fetch_result($resA, $z, extrato_lancamento);
                    $extrato            = pg_fetch_result($resA, $z, extrato);

                    $sqlAv = "UPDATE tbl_extrato_lancamento SET extrato = NULL WHERE extrato_lancamento = $extrato_lancamento AND extrato = $extrato;";
                    $resAv = @pg_query($con,$sqlAv);
                    $msg_erro = pg_errormessage($con);
                }
            }
        }

        //TIRA AS OSs DO EXTRATO
        if( strlen($msg_erro) == 0 ){
            $sql = "UPDATE tbl_os_extra SET extrato = NULL
                    WHERE  tbl_os_extra.extrato IN(
                                                    SELECT tbl_os_extra.extrato
                                                    FROM tbl_os_extra
                                                    JOIN tbl_os USING(os)
                                                    JOIN tbl_extrato USING(extrato)
                                                    JOIN tbl_extrato_extra USING(extrato)
                                                    WHERE tbl_extrato_extra.baixado IS NULL
                                                    AND   tbl_os.fabrica       = $login_fabrica
                                                    AND   tbl_os_extra.extrato = $extrato
                                                );";
            $res = @pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

        //EXCLUI O EXTRATO
        if (strlen($msg_erro) == 0) {
            $sql = "DELETE FROM tbl_extrato WHERE extrato = $extrato AND fabrica = $login_fabrica;";
            $res = @pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

        if (strlen($msg_erro) == 0) {
            $res  = pg_query($con,"COMMIT TRANSACTION");

            $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
            $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato);

            $link = $_COOKIE["link"];
            header ("Location: extrato_consulta.php");
            exit;
        }else{
            $res = pg_query($con,"ROLLBACK TRANSACTION");
        }
    }
}

/**--------ADICIONA OS NO EXTRATO------------
  *  Só aparece para a fabrica quando o extrato nao foi dado baixa.
  *  Verifica em tbl_os_status e limpar qualquer registro encontrado diferente de 58 = historico da OS
  *  verificar na tbl_os_extra se a os está em algum extrato, se estiver:
  *     copia o numero do extrato atual
  *  Atualiza o historico de movimentacao da OS entre extrato, caso seja a primeira vez
  * é criado um registro na tabela tbl_os_status com o status_os = 58
  *  Busca por registro na tbl_os_status diferente de 58 e apaga.
  *  Atualiza os os extratos:
  *     recalcula o extrato anterior
  *     recalcula o extrato atual
***/

if( strlen($adiciona_sua_os) > 0 AND ( in_array($login_fabrica, array(6,10,11,51,172))) )
{
    $adiciona_sua_os = trim($adiciona_sua_os);//pega a sua_os digitada pelo admin

    $sql = "SELECT posto
                FROM tbl_extrato
            WHERE extrato = '$extrato'
            AND fabrica = '$login_fabrica' ";

    $res = pg_query($con,$sql);// Atraves do extrato, busca-se o posto
    $adiciona_posto = pg_fetch_result($res,0,0);//joga o posto na variavel

    if(strlen($adiciona_posto) > 0 AND strlen($msg_erro) == 0){
        $sql = "SELECT DISTINCT os
                FROM tbl_os
                WHERE UPPER(sua_os) = UPPER('$adiciona_sua_os')
                AND fabrica  = '$login_fabrica'
                AND posto    = '$adiciona_posto' ";

        $res             = pg_query($con,$sql);// Se encontrou posto procura pela OS do posto atraves da SUA_OS.
        $adiciona_os     = @pg_fetch_result($res,0,0);

        if(strlen($adiciona_os) > 0 ){

            $sql2 = "SELECT data_fechamento,extrato FROM tbl_os JOIN tbl_os_extra using(os) WHERE os = '$adiciona_os' ";
            $res2 = @pg_query($con,$sql2);
            $adiciona_fechamento  = @pg_fetch_result($res2,0,data_fechamento);//busca a data de fechamento
            $adiciona_extrato_ant = @pg_fetch_result($res2,0,extrato);//busca o extrato caso ja esteja em um extrato

            if (strlen($adiciona_extrato_ant) > 0 ){
                $sql2 = "SELECT extrato
                    FROM tbl_extrato_pagamento
                    WHERE extrato = '$adiciona_extrato_ant' ";
                $res2 = @pg_query($con,$sql2);// Verifica se o extrato ja foi dado baixa
                $adiciona_baixado = @pg_fetch_result($res2,0,0);//adiciona o extrato anterior
            }

            if(strlen($adiciona_baixado) > 0 and ($login_fabrica == 6 or $login_fabrica == 10)) {//Verifica se o extrato ja foi dado baixa
                $msg_erro = 'O extrato desta OS já foi dado baixa';
            }

            if (strlen($adiciona_extrato_ant) > 0 ){
                $sql3 = "SELECT extrato
                    FROM tbl_extrato
                    WHERE extrato = '$adiciona_extrato_ant'
                    AND liberado IS NOT NULL";
                $res3 = @pg_query($con,$sql3);// Verifica se o extrato ja foi liberado
                $adiciona_liberado = @pg_fetch_result($res3,0,0);//adiciona o extrato anterior
            }

            if(strlen($adiciona_liberado) > 0 and in_array($login_fabrica, array(11,172)) ) {//Verifica se o extrato ja foi liberado
                $msg_erro = 'O extrato desta OS já foi liberado';
            }

            if(strlen($adiciona_fechamento) == 0){//verifica se a OS esta fechada
                $msg_erro = 'A OS está aberta. Deve-se estar fechada para entrar no extrato.<br> ';
            }

            if($adiciona_extrato_ant == $extrato){//se estava no extrato anterior
                $msg_erro = " A OS já faz está neste extrato ";
            }

            if( pg_num_rows($res) == 1 AND strlen($msg_erro) == 0 ){

                $auditorLog = new AuditorLog();
                $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

                $auditorLogOS = new AuditorLog();
                $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$extrato );

                $res = pg_query ($con,"BEGIN TRANSACTION");

                $sql = "SELECT os_status
                            FROM tbl_os_status
                        WHERE os    = '$adiciona_os'
                        AND extrato = '$extrato' ";

                $res = @pg_query($con,$sql);//Se encontrou a OS verifica se a OS ja foi recusada/excludia/acumulada de algum extrato

                $adiciona_status = @pg_num_rows($res);

                if($adiciona_status > 0){
                    $sql = "DELETE FROM tbl_os_status
                            WHERE os    = '$adiciona_os'
                            AND extrato = '$extrato'
                            AND status_os <> '58' ";
                    $res = pg_query($con,$sql);//Caso encontre algum registro ele deleta o registro
                }//58 é os_status historio da OS nas movimentacoes entre extratos

                $sql = "SELECT extrato FROM tbl_os_extra WHERE os = '$adiciona_os' ";
                $res = @pg_query($con,$sql);//Busca se a OS participa de algum extrato

                if(@pg_num_rows($res) > 0){
                    $extrato_anterior = pg_fetch_result($res,0,0);//caso ja faça parte de um extrato
                }

                $sql = "SELECT os_status, observacao FROM tbl_os_status
                        WHERE os = $adiciona_os
                        AND   status_os = 58 ";
                $res = pg_query($con,$sql); //caso haja historico da movimentacao entre extratos,
                                            //copia para concatenar com a nova movimentacao

                if( pg_num_rows($res) > 0 ){ //caso haja da um update na tabela os_status
                    $adiciona_observacao  = pg_fetch_result($res,0,observacao);
                    $adiciona_os_status   = pg_fetch_result($res,0,os_status);
                    $adiciona_observacao .= " Saiu do extrato [ $extrato_anterior ] entrou no extrato [ $extrato ]. ";

                    $sql2 = "UPDATE tbl_os_status SET observacao = '$adiciona_observacao'
                             WHERE os_status = '$adiciona_os_status'
                             AND   status_os = '58' ";
                    $res2 = pg_query($con,$sql2);

                }else{ //caso nao encontre adiciona o registro na tabela
                    $observacao = "Saiu do extrato [ ".$extrato_anterior." ], entrou no extrato [ ".$extrato." ].";

                    $sql = "INSERT INTO tbl_os_status ( os               ,
                                                        status_os        ,
                                                        data             ,
                                                        observacao       ,
                                                        extrato          ,
                                                        admin
                                                    ) VALUES (
                                                        $adiciona_os     ,
                                                        '58'             ,
                                                        current_timestamp,
                                                        '$observacao'     ,
                                                        $extrato         ,
                                                        $login_admin
                                                    );";
                    $res = pg_query($con,$sql);
                }

                $sql = " UPDATE tbl_os_extra set extrato = $extrato WHERE os = $adiciona_os ";
                $res = pg_query($con, $sql);  //Coloca o novo extrato na OS_EXTRA

                if( strlen($extrato) > 0 AND strlen($msg_erro) == 0 )
                {
                    if(strlen($extrato_anterior) > 0){
                        ## AGENDAMENTO DE RECALCULO DE EXTRATO ##

                        #$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato_anterior AND fabrica = $login_fabrica;";
                        #$res = pg_query($con,$sql);
                        #$total_os_extrato = pg_fetch_result($res,0,0);
                        #HD15716
                        if( in_array($login_fabrica, array(11,172)) ){
                            $sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato_anterior AND fabrica = $login_fabrica;";
                            $res = pg_query($con,$sql);
                            $msg_aviso = "Foi agendado o recalculo do extrato $extrato_anterior para esta noite!<br>";
                        }else{
                            $sql = "SELECT fn_calcula_extrato ('$login_fabrica','$extrato_anterior');";
                            $res = @pg_query ($con,$sql);//Recalcula o extrato anterior, caso exista
                            $msg_erro = pg_errormessage($con);
                        }
                    }

                    #$sql = "SELECT count(*) FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE tbl_extrato.extrato = $extrato AND fabrica = $login_fabrica;";
                    #$res = pg_query($con,$sql);
                    #$total_os_extrato = pg_fetch_result($res,0,0);
                    #HD15716
                    if( in_array($login_fabrica, array(11,172)) ){
                        $sql = "UPDATE tbl_extrato SET recalculo_pendente = TRUE WHERE extrato = $extrato AND fabrica = $login_fabrica;";
                        $res = pg_query($con,$sql);
                        $msg_aviso = "Foi agendado o recalculo do extrato $extrato para esta noite!<br>";
                    }else{
                        $sql = "SELECT fn_calcula_extrato ('$login_fabrica','$extrato');";
                        $res = @pg_query ($con,$sql);//Recalcula o extrato atual
                        $msg_erro = pg_errormessage($con);
                    }

                    if (strlen($msg_erro) == 0) {
                        $res = pg_query($con,"COMMIT TRANSACTION");

                        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
                        $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato);

                        $link = $_COOKIE["link"];
                        header ("Location: $link?msg_aviso=$msg_aviso");
                        // header ("Location: extrato_consulta_os.php?extrato=$extrato");
                        exit;//Recarrega a página
                    }else{
                        $res = pg_query($con,"ROLLBACK TRANSACTION");
                    }   //caso ocorra erro rollback;
                }else{
                        $res = pg_query($con,"ROLLBACK TRANSACTION");
                }
            }else{
                if(strlen($msg_erro) == 0){
                    $msg_erro = " Não foi possivel encontrar a OS";
                }
            }
        }else{
            $msg_erro = " OS não encontrada. ";
        }
    }else{
        $msg_erro = " Não foi possível localizar o posto";
    }
}

/* para recusar uma os sedex deve-se:
    tirar o finalizada da os_sedex, setar null;
    Criar uma os_status(13) para informar o motivo da recusa;
    Deletar o extrato_lancamento;
*/
$recusa_sedex = $_POST['recusa_sedex'];

if( ($login_fabrica == 10 OR $login_fabrica == 1) AND strlen($recusa_sedex) > 0 )
{
    $obs                = $_POST['descricao'];
    $os_sedex           = $_POST['os_sedex'];
    $extrato_lancamento = $_POST['extrato_lancamento'];

    if(strlen($obs) > 0 AND strlen($os_sedex) > 0 AND strlen($extrato_lancamento) > 0){

        $auditorLog = new AuditorLog();
        $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

        $res = pg_query ($con,"BEGIN TRANSACTION");

        $sql = "UPDATE tbl_os_sedex set finalizada = null
                    WHERE os_sedex = $os_sedex
                    AND fabrica = $login_fabrica; ";
        $res = pg_query($con,$sql);

        $msg_erro = pg_errormessage($con);

        $sql = "INSERT INTO tbl_os_status (
                os_sedex, status_os, observacao, extrato, admin
            ) VALUES (
                $os_sedex, '13', '$obs', $extrato, $login_admin
            );";
        $res = pg_query($con,$sql);
        $msg_erro = pg_errormessage($con);

        $sql = "DELETE FROM tbl_extrato_extra_item
                WHERE extrato_lancamento = $extrato_lancamento; ";
        $res = pg_query($con,$sql);
        $msg_erro = pg_errormessage($con);

        $sql = "DELETE FROM tbl_extrato_lancamento
                WHERE os_sedex = $os_sedex
                AND   extrato_lancamento = $extrato_lancamento; ";
        $res = pg_query($con,$sql);
        $msg_erro = pg_errormessage($con);

        $sql = "SELECT fn_calcula_extrato ('$login_fabrica','$extrato');";
        $res = @pg_query ($con,$sql);//Recalcula o extrato anterior, caso exista
        $msg_erro = pg_errormessage($con);

        if(strlen($msg_erro) == 0){
            $res = pg_query($con,"COMMIT TRANSACTION");

            $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);

            $corpo.="<br>Status: Correto";//e-mail para fernando
        }else{
            $res = pg_query($con,"ROLLBACK TRANSACTION");
            $corpo.="<br>Status: Verificar";//e-mail para fernando
        }
    }else{
        $msg_erro = "Não é possível realizar a recusa da OS SEDEX";
    }
}

if(in_array($login_fabrica, array(15,45,50)))
{
    if( $_POST["gravar_previsao"] == "Gravar" )
    {
        $data_recebimento_nf  = trim($_POST["data_recebimento_nf"]) ;
        $xdata_recebimento_nf = (strlen($data_recebimento_nf) > 0) ? "'$data_recebimento_nf'" : 'NULL';

        if (strlen($_POST["data_recebimento_nf"]) > 0 )
        {
            $data_recebimento_nf  = trim($_POST["data_recebimento_nf"]) ;
            $xdata_recebimento_nf = str_replace("/","",$data_recebimento_nf);
            $xdata_recebimento_nf = str_replace("-","",$xdata_recebimento_nf);
            $xdata_recebimento_nf = str_replace(".","",$xdata_recebimento_nf);
            $xdata_recebimento_nf = str_replace(" ","",$xdata_recebimento_nf);

            $dia = trim(substr($xdata_recebimento_nf,0,2));
            $mes = trim(substr($xdata_recebimento_nf,2,2));
            $ano = trim(substr($xdata_recebimento_nf,4,4));
            if( strlen($ano) == 2 ) $ano = "20" . $ano;

            //-=============Verifica data=================-//
            $verifica = checkdate($mes, $dia, $ano);
            if( $verifica ==1){
                $xdata_recebimento_nf = $ano . "-" . $mes . "-" . $dia ;
                $xdata_recebimento_nf = "'" . $xdata_recebimento_nf . "'";
            }else{
                $msg_erro="A Data de Pagamento não está em um formato válido";
            }
        }else{
            $xdata_recebimento_nf = "NULL";
            //HD 9387 Paulo 10/12/2007
            $msg_erro.="Por favor, digitar a Data de Recebimento da Nota Fiscal!!!";
        }

        $previsao_pagamento = trim($_POST["previsao_pagamento"]);

        if(strlen($previsao_pagamento) > 0){
            $xprevisao_pagamento = "'$previsao_pagamento'";
        }else{
            $xprevisao_pagamento = 'NULL';
        }

        if( strlen($_POST["previsao_pagamento"]) > 0 )
        {
            $previsao_pagamento  = trim($_POST["previsao_pagamento"]) ;
            $xprevisao_pagamento = str_replace("/","",$previsao_pagamento);
            $xprevisao_pagamento = str_replace("-","",$xprevisao_pagamento);
            $xprevisao_pagamento = str_replace(".","",$xprevisao_pagamento);
            $xprevisao_pagamento = str_replace(" ","",$xprevisao_pagamento);

            $dia = trim(substr($xprevisao_pagamento,0,2));
            $mes = trim(substr($xprevisao_pagamento,2,2));
            $ano = trim(substr($xprevisao_pagamento,4,4));
            if( strlen($ano) == 2 ) $ano = "20" . $ano;

            //-=============Verifica data=================-//
            $verifica = checkdate($mes, $dia, $ano);
            if( $verifica == 1){
                $xprevisao_pagamento = $ano . "-" . $mes . "-" . $dia ;
                $xprevisao_pagamento = "'" . $xprevisao_pagamento . "'";
            }else{
                $msg_erro="A Data de Pagamento não está em um formato válido";
            }
        }else{
            $xprevisao_pagamento = "NULL";
            //HD 9387 Paulo 10/12/2007
            $msg_erro.="Por favor, digitar a Data de Recebimento da Nota Fiscal!!!";
        }

        if (strlen($extrato) > 0) {
            $auditorLog = new AuditorLog();
            $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

            $sql = "UPDATE tbl_extrato SET
                        previsao_pagamento  = $xprevisao_pagamento        ,
                        data_recebimento_nf = $xdata_recebimento_nf       ,
                        admin               = $login_admin
                    WHERE extrato       = $extrato
                    AND   fabrica       = $login_fabrica";
        }
        $res = pg_query ($con,$sql);

        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);

        $msg_erro = pg_errormessage($con);
    }
}


$layout_menu = "financeiro";
$title       = traduz("Relação de Ordens de Serviços");
//echo '<center>';

if( in_array($login_fabrica, array(11,172)) ){
    $plugins = array("mask", "datepicker", "dataTable");

    include "cabecalho_new.php";
    include "plugin_loader.php";
}else{
    include "cabecalho.php";
}


?>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>

<style type="text/css">
    .btn-anexar-nf{
        background: #d90000;
        padding: 10px 20px;
        color: #fff !important;
        cursor: pointer;
    }
    .alert_osr{
        padding: 8px 35px 8px 14px;
        margin-bottom: 20px;
        text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
        background-color: #fcf8e3;
        border: 1px solid #fbeed5;
        -webkit-border-radius: 4px;
        -moz-border-radius: 4px;
        border-radius: 4px;
        width: 690px;
        font-size: 14px;
    }
    .alert_osr_info{
        color: #3a87ad;
        background-color: #d9edf7;
        border-color: #bce8f1;
    }
    .anexos{
        font-size: 10px;
        font-family: verdana;
        text-align:center;
    }
    .menu_top {
        text-align: center;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: x-small;
        font-weight: bold;
        border: 1px solid;
        color:#ffffff;
        background-color: #596D9B
    }
    .menu_top2 {
        text-align: center;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: x-small;
        font-weight: bold;
        color:#ffffff;
        background-color: #596D9B
    }
    .table_line {
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: 10px;
        font-weight: normal;
        border: 0px solid;
        background-color: #D9E2EF;
    }
    .table_line2 {
        text-align: center;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: 10px;
        font-weight: normal;
    }
    .Erro{
        border-right: #990000 1px solid;
        border-top: #990000 1px solid;
        font: 10pt Arial ;
        color: #ffffff;
        border-left: #990000 1px solid;
        border-bottom: #990000 1px solid;
        background-color: #FF0000;
    }
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
    .formulario{
        background-color:#D9E2EF;
        font:11px Arial;
        text-align:left;
    }
    .subtitulo{
        background-color: #7092BE;
        font:bold 11px Arial;
        color: #FFFFFF;
    }
    table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;
    }
    .espaco{
        padding: 0 0 0 50px;
    }

    <?php /* HD 2416981 */ ?>
    table#tabela_obs_ad td {
      vertical-align: top;
      padding: 1ex 1ex;
    }
    table#tabela_obs_ad thead,
    table#tabela_obs_ad caption {
        border-color: #fff;
        height: 22px;
        line-height: 22px;
        text-transform: uppercase;
    }
    table#tabela_obs_ad thead th {text-transform: uppercase;line-height:22px}
    table#tabela_obs_ad td p.servico {margin: 0}
    table#tabela_obs_ad td p.servico:not(:last-of-type) {margin-bottom: 0.5ex}
    td p.servico>span {display: inline-block; width: 7.5em;font-weight:bold}


    .frm{
        margin: 5px;
    }

    .frm-title{
        padding-left: 8px;
    }

    .table tbody tr.reincidente > td {
      background-color: #ffcccc !important;
    }

    <?php if ($login_fabrica == 183){ ?>
        table.tablesorter thead tr th, table.tablesorter tfoot tr th {
            background-color: #596d9b !important;
            border: 1px solid #FFF !important;
            font-size: 8pt !important;
            padding: 4px !important;
        }
    <?php } ?>

    .width_padrao {
        width: auto !important;
    }

</style>
<p>

<?php //include "javascript_calendario_new.php"; // adicionado por Fabio 27-09-2007 ?>
<?php
    if( !in_array($login_fabrica, array(11,172)) ){
        include "../js/js_css.php";
    }
?>


<script type="text/javascript">

    $(function() {
        Shadowbox.init();

        $("#btn_excluir_nf_lote").click(function() {
            let nf_hidden = "";
            let extrato = '<?=$extrato?>';
            nf_hidden = $("#nf_hidden").val();

            if (nf_hidden != "" && nf_hidden != undefined) {
                $.ajax({
                    url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                    type: "POST",
                    data: {
                        btn_remove_nf : true,
                        anexo: nf_hidden,
                        extrato: extrato
                    },
                    complete : function(data){
                        data = data.responseText;
                        if(data == "ok"){
                            alert("Anexo Excluido Com Sucesso !");
                            window.location = "<?php echo $PHP_SELF; ?>?extrato=" + extrato;
                        }
                    }
                });
            } else {
                alert("NF não encontrada !");
            }


        });

        <?php
        if (in_array($login_fabrica, [144])) {
        ?>
            $(".check-os").filter(function(){
                return $("#acoes_marcadas").length == 0;
            }).hide();
        <?php
        }
        ?>

        $(".reprovar").click(function(){
                $("#linha_observacao").show();
        });

        $(".aprovar").click(function(){
                $("#linha_observacao").hide();
                $("#observacao_reprova").val('');
        });

        $("#btn_acao_aprovacao_nf").click(function(){
            var extrato = "<?php echo $extrato; ?>";
            var resposta = $("input[name='aprovar_reprovar']:checked").val();

            if(resposta == 'nao'){
                var msg_sucesso = "Nota fiscal reprovada com sucesso. ";
                var observacao_reprova = $("#observacao_reprova").val();
                if(observacao_reprova.length == 0){
                    alert('Informe o motivo da reprova da nota fiscal.')
                    return false;                    
                }
            }

            if(resposta == 'sim'){
                var msg_sucesso = "Nota fiscal aprovada com sucesso. ";
            }

            $.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                type: "POST",
                data: {
                    btn_acao_aprovacao_nf : true,
                    observacao_reprova : observacao_reprova,
                    extrato : extrato,
                    resposta : resposta
                },
                complete : function(data){
                    data = data.responseText;
                    if(data.length == 0){
                        alert(msg_sucesso);
                        window.location = "<?php echo $PHP_SELF; ?>?extrato=" + extrato;
                    }
                }
            });


        })

        <?php if ($login_fabrica == 183){ ?>
            $("#grid_list").tablesorter({
                widgets: ["zebra"],
                headers:{
                    11:{
                        sorter: false
                    },
                    12:{
                        sorter: false
                    },
                    13:{
                        sorter: false
                    },
                    14:{
                        sorter: false
                    }
                }
            });
        <?php } ?>

    });

<?php if($login_fabrica == 35){ ?>
    function alterarDataPagamento(){

        var extrato = "<?php echo $extrato; ?>";
        var data_pagamento = $("#data_pagamento").val();

        $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
            type: "POST",
            data: {
                altera_data_pagamento : true,
                data_pagamento : data_pagamento,
                extrato : extrato
            },
            complete : function(data){

                data = data.responseText;

                alert(data);

            }
        });

    }
<?php } ?>

<?php if( in_array($login_fabrica, array(11,172)) ){
?>
$(function(){
    $.datepickerLoad(Array("data_vencimento", "data_pagamento"));
   var table = new Object();
   table['table'] = "#resultado_extrato_consulta";
   table['type'] = 'basic';
   table['aaSorting'] = [];
   $.dataTableLoad(table);


   $('.check-os').change(function(){
        var indice = $(this).attr('idx');

        if($(this).is(":checked")){
            $('#os_'+indice).prop('checked',true);
        }else{
            $('#os_'+indice).prop('checked',false);
        }
    });

});
<?php
}elseif($login_fabrica == 15){
    ?>
    $(function(){
        $(".btn-mo").click(function(){

            valorOS = $(this).attr('os');
            var valorMO = window.prompt("Novo valor da Mão de Obra","");
            if(valorMO.match('[a-zA-Z]') == null && valorMO.match('[-/%$#@!*()+;?{}=_"]') == null){
                $(this).val('Alterando');
                if(valorMO != "" && valorOS != ""){
                    var body = "ajax_km_mo=1&os="+valorOS+"&mo="+valorMO;
                    var req;
                    if(window.XMLHttpRequest){
                        req = new XMLHttpRequest();
                    }else{
                        req = new ActiveXObject("Microsoft.XMLHTTP");
                    }
                    req.open("POST",window.location,false);
                    req.setRequestHeader("X-Requested-With","XMLHttpRequest");
                    req.setRequestHeader("Content-type","application/x-www-form-urlencoded")
                    req.send(body);
                    retorno = req.responseText;
                    resposta = JSON.parse(retorno)
                    if(resposta.status == 'ok'){
                        valorMO = resposta.mo;
                        valorTotal = resposta.total;

                        alert("Mão de obra alterada");

                        var tr = $(this).parents('tr');
                        var tdmo = $(tr).find('td[class=td-mo]');
                        var tdtotal = $(tr).find('td[class=td-total]');

                        $(tdmo).html(valorMO);
                        $(tdtotal).html(valorTotal);
                    }else{
                        alert("Erro ao alterar mão de obra");
                    }
                }else{
                    alert("Erro ao alterar mão de obra");
                }
            }else{
                alert("Somente números são permitidos nesse campo");
            }
            $(this).val('Alterar MO');
        });
        $(".btn-km").click(function(){
            valorOS = $(this).attr('os');
            var valorKm = window.prompt("Novo valor do KM","");

            if(valorKm.match('[a-zA-Z]') == null && valorKm.match('[-/%$#@!*()+;?{}=_\'"]') == null){
                $(this).val('Alterando');
                if(valorKm != "" && valorOS != ""){
                    var body = "ajax_km_mo=1&os="+valorOS+"&km="+valorKm;
                    var req;
                    if(window.XMLHttpRequest){
                        req = new XMLHttpRequest();
                    }else{
                        req = new ActiveXObject("Microsoft.XMLHTTP");
                    }
                    req.open("POST",window.location,false);
                    req.setRequestHeader("X-Requested-With","XMLHttpRequest");
                    req.setRequestHeader("Content-type","application/x-www-form-urlencoded")
                    req.send(body);
                    retorno = req.responseText;
                    resposta = JSON.parse(retorno)
                    if(resposta.status == 'ok'){
                        valorTotal = resposta.total;
                        valorKm = resposta.km;

                        alert("KM alterado");

                        var tr = $(this).parents('tr');
                        var tdkm = $(tr).find('td[class=td-km]');
                        var tdtotal = $(tr).find('td[class=td-total]');

                        $(tdkm).html(valorKm);
                        $(tdtotal).html(valorTotal);
                    }else{
                        alert("Erro ao alterar KM");
                    }
                }else{
                    alert("Erro ao alterar KM");
                }
            }else{
                alert("Somente números são permitidos nesse campo");
            }
            $(this).val('Alterar KM');

            console.log(retorno);
        });
    });



    <?php
}
?>

function voltarManutencao(extrato){

        $.ajax({
            type: "POST",
            url: "<?=$PHP_SELF?>",
            data: "extrato_manutencao="+extrato,
            complete: function(http) {
                result = http.responseText;
                alert(result);
                if(result == "Success"){
                    alert('Extrato '+extrato+' voltou para manutenção com Sucesso!');
                }else{
                    alert('O Extrato '+extrato+' não pode voltar para manutenção, pois não foi Aprovado!');
                }
            }
        });

}

<?php if( $login_fabrica == 30 ){ ?>
    function mostrarPecas(os)
    {
        if( document.getElementById('dados_' + os) )
        {
            var style2 = document.getElementById('dados_' + os);

            if( style2==false ) return;
            if( style2.style.display=="block" ){
                $('#dados_'+os).slideUp("slow");
                $('#linha_'+os).hide();
            }else{
                $('#linha_'+os).show();
                $('#dados_'+os).slideDown("slow");
                style2.style.display = "block";
                if( $('#dados_'+os).attr('rel') != '1' ){
                    retornaPecas(os);
                }
                $('#dados_'+os).attr('rel','1');
            }
        }
    }

    function retornaPecas(os)
    {
        var curDateTime = new Date();
        $.ajax({
            type: "GET",
            url: "<?=$PHP_SELF?>",
            data: 'ajax=mostrarpecas&os='+os+"&data="+curDateTime ,
            beforeSend: function(){
                $('#dados_'+os).html("&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='js/loadingAnimation.gif'> ");
            },
            error: function (){
                $('#dados_'+os).html("erro");
            },
            complete: function(http) {
                results = http.responseText;
                $('#dados_'+os).html(results).addClass('z-index','2');
            }
        });
    }

    function alteraServico(os, os_item, servico_realizado, obj)
    {
        var curDateTime = new Date();
        $.ajax({
            type: "GET",
            url: "<?=$PHP_SELF?>",
            data: 'ajax=alteraservico&os='+os+"&os_item="+os_item+"&servico_realizado="+servico_realizado+"&data="+curDateTime ,
            beforeSend: function(){
                $("#servico"+os_item).css('background-color', '#FFFFFF');
                $("#servico"+os_item).attr('disabled', 'disabled');
            },
            error: function (){
                $("#servico"+os_item).removeAttr('disabled');
                $("#servico"+os_item).css('background-color', '#CC0000');
                $("#servico"+os_item).val($("#servico_inicial"+os_item).val());
                alert('Falha na solicitação');
            },
            complete: function(http){
                results = http.responseText;
                if (results == 'ok') {
                    $("#servico"+os_item).css('background-color', '#22CC22');
                    $("#btn_recalcular_extrato").css('display', 'inline');
                    $("#mensagem_extrato").css("display", "block");
                    $("#mensagem_extrato_td").html("Extrato pendente de recálculo");
                }
                else {
                    $("#servico"+os_item).css('background-color', '#CC0000');
                    $("#servico"+os_item).val($("#servico_inicial"+os_item).val());
                    alert(results);
                }
                $("#servico"+os_item).removeAttr('disabled');
            }
        });
    }
    <?php } ?>

    <?php if( !in_array($login_fabrica, array(11,172)) ){ ?>
    $(function()
    {
        $("#data_vencimento").datepick();
        $("#data_pagamento").datepick();
        $("#previsao_pagamento").datepick();
    $("#data_nf").datepick();
        $("#data_pagamento").mask("99/99/9999");
        $("#previsao_pagamento").mask("99/99/9999");
        $("#data_recebimento_nf").mask("99/99/9999");
        $("#previsao_pagamento").mask("99/99/9999");
    $("#data_nf").mask("99/99/9999");
        $("input[name=data_vencimento]").mask("99/99/9999");
        $("input[name=valor_total]").numeric({ allow:".," });
        $("input[name=vlr_nf_pecas]").numeric({ allow:".," });
        $("input[name=acrescimo]").numeric({ allow:".," });
        $("input[name=desconto]").numeric({ allow:".," });
        $("input[name=valor_liquido]").numeric({ allow:".," });

        if("<?=$login_fabrica; ?> "== 42){
            $("input[name=data_bordero]").mask("99/99/9999");
            $("input[name=data_envio_aprovacao]").mask("99/99/9999");
            $("input[name=data_aprovacao]").mask("99/99/9999");
            $("input[name=data_entrega_financeiro]").mask("99/99/9999");
        }
        shortcut.add("Enter",function()
        {
            var obj = $("input[name^=leitor2_]:focus");
            var obj_id = $(obj).attr("id");
            $(obj).verifica_serie({id: obj_id});

        });
    });
    <?php } ?>

    function aprovaSerieManual(os){
        var obj    = $("button[id=btn_"+os+"]");
        var obj_id = "btn_"+os;
        $(obj).verifica_serie({id: obj_id});
    }

    (function($){
        $.fn.verifica_serie = function(options)
        {
            var settings = $.extend({ "id" : "" }, options);
            var serie    = $("#"+settings["id"]).parents('tr').find('td input.valida_serie').val();
            var os       = $("#"+settings["id"]).parents('tr').find('td input.check_serie').val();
            var input_id = $("#"+settings["id"]).parents('tr').find('td input.valida_serie').attr('rel');
            input_id++;

            $.ajax({
                url: "<?=$PHP_SELF?>?ajaxx=verifica_serie&os="+os+"&serie="+serie+"&extrato=<?=$extrato?>",
                success:function(data){
                    retorno = data.split('|');
                    if( retorno[0] == "ok" ){
                        tr = $("#"+settings["id"]).parents('tr');
                        $("#"+settings["id"]).parents('tr').find('#col_'+os).html("<img src='imagens/img_ok.gif'>");
                        $("#btn_"+os).remove();
                        console.log(tr);
                        tr.nextAll('tr').find('td input.valida_serie :visible').focus();
                        for( var i = input_id; i <= $('#qtde_de_inputs').val(); i++ ){

                            if( $('.leitor_'+i).is(':visible') )
                            {
                                $('.leitor_'+i).focus();
                                break;
                            }

                        }

                        //$(".leitor_"+input_id).find('input.valida_serie :visible').focus();
                        //$(".leitor_"+input_id).focus();
                        if( retorno[1] == "sim" ){ $("#baixar_extrato").show(); }

                    } else {
                        $("#"+settings["id"]).parents('tr').find('td input.valida_serie').val('');
                        $("#"+settings["id"]).parents('tr').find('td input.valida_serie').html('');
                        $("#"+settings["id"]).parents('tr').find('td input.check_serie').removeAttr("checked");
                        alert('Série não encontrada');
                    }
                }
            });
        };
    })(jQuery);
</script>

<script type="text/javascript">
var ok = false;
function checkaTodos(){
    if( !ok ){
        $(".check-os").each(function(){
            $(this).attr("checked",true);
        });
        ok = true;
    }else{
        $(".check-os").each(function(){
            $(this).attr("checked",false);
        });
        ok = false;
    }
}

function selecionarTudo()
{
    $('input[rel=osimprime]').each(function(){
        this.checked = !this.checked;
    });
}

function imprimirSelecionados(){
    var qtde_selecionados = 0;
    var linhas_seleciondas = "";
    $('input[rel=osimprime]:checked').each(function(){
        if( this.checked )
        {
            linhas_seleciondas = this.value+", "+linhas_seleciondas;
            qtde_selecionados++;
        }
    });

    if( qtde_selecionados>0 )
    {
        janela = window.open('os_print_multi.php?osimprime='+linhas_seleciondas,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=850,height=600,top=18,left=0");
    }
    else
    {
        alert('Selecione uma OS para impressão');
    }
}

/*substituido.
function fnc_pesquisa_os (sua_os, adiciona_posto, adiciona_data_abertura, adiciona_extrato) {
    url = "pesquisa_os_fer.php?sua_os=" + sua_os.value + "&posto=" + adiciona_posto.value + "&extrato=" + adiciona_extrato.value ;
    janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=200,top=18,left=0");
    janela.retorno = "<? echo $_SERVER['PHP_SELF']; ?>";
    janela.sua_os                   = sua_os;
    janela.adiciona_data_abertura   = adiciona_data_abertura;
    janela.adiciona_extrato         = adiciona_extrato;
    janela.focus();
}
*/

function fnc_pesquisa_os (adiciona_sua_os, adiciona_posto, adiciona_data_abertura, adiciona_extrato)
{
    var url = "";
    url     = "pesquisa_adiciona_os.php?forma=reload&adiciona_sua_os=" + adiciona_sua_os.value + "&posto=" + adiciona_posto.value + "&extrato=" + adiciona_extrato.value ;
    janela  = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=450, height=250, top=0, left=0");
    janela.adiciona_sua_os = adiciona_sua_os;
    janela.retorno         = "<? echo $_SERVER['PHP_SELF']; ?>";
    janela.focus();
}

<?php if( !in_array($login_fabrica, array(11,172)) ){ ?>
$(document).ready(function()
{
    $('#nota_fiscal_mao_de_obra').editable({
        submit:'Baixar',
        cancel:'Cancelar',
        onSubmit:function(valor){
            $.post(
                '<?=$PHP_SELF?>',
                {
                    nota_fiscal_mao_de_obra: valor.current,
                    grava_extrato: '<?=$extrato?>'
                }
            )
        }
    });

    $('#emissao_mao_de_obra').editable({
        type:'date',
        submit:'Baixar',
        cancel:'Cancelar',
        onSubmit:function(valor){
            $.post(
                '<?=$PHP_SELF?>',
                {
                    emissao_mao_de_obra: valor.current,
                    grava_extrato: '<?=$extrato?>'
                }
            )
        }
    });

    $('#valor_total_extrato').editable({
        submit:'Baixar',
        cancel:'Cancelar',
        onSubmit:function(valor){
            $.post(
                '<?=$PHP_SELF?>',
                {
                    valor_total_extrato: valor.current,
                    grava_extrato: '<?=$extrato?>'
                }
            )
        }
    });
});
<?php } ?>

</script>

<script type="text/javascript">
<!--
function Recusa(os_sedex_extrato,os_sedex_sedex)
{
    if( confirm('Deseja realmente recusar essa OS SEDEX?') == true )
    {
        window.location = "<?php echo $PHP_SELF; ?>?extrato=" + os_sedex_extrato + "&os_sedex_sedex=" + os_sedex_sedex;
    }
}
//-->

function createRequestObject()
{
    var request_;
    var browser = navigator.appName;
    if( browser == "Microsoft Internet Explorer" ){
         request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
         request_ = new XMLHttpRequest();
    }
    return request_;
}

var http3 = new Array();

function gravaAutorizao()
{
    var extrato_estoque   = document.getElementById('extrato_estoque');
    var autorizacao_texto = document.getElementById('autorizacao_texto');
    var curDateTime       = new Date();
    http3[curDateTime]    = createRequestObject();

    url = "<?php echo $PHP_SELF;?>?ajax=gravar&extrato="+extrato_estoque.value+"&observacao="+autorizacao_texto.value;
    http3[curDateTime].open('get',url);
    var campo = document.getElementById('div_estoque');

    http3[curDateTime].onreadystatechange = function()
    {
        if( http3[curDateTime].readyState == 1 )
        {
            campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
        }

        if( http3[curDateTime].readyState == 4 )
        {
            if( http3[curDateTime].status == 200 || http3[curDateTime].status == 304 )
            {
                var results = http3[curDateTime].responseText;
                campo.innerHTML   = results;
            }
            else
            {
                campo.innerHTML = "Erro";
            }
        }
    }
    http3[curDateTime].send(null);
}

function createRequestObject()
{
    var request_;
    var browser = navigator.appName;
    if( browser == "Microsoft Internet Explorer" ){
         request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
         request_ = new XMLHttpRequest();
    }
    return request_;
}

var http_forn = new Array();

function zerar_mo(os,extrato,btn)
{
    var botao = document.getElementById(btn);
    var acao  = 'zerar';
    url       = "<?=$PHP_SELF?>?ajax=sim&op="+acao+"&os="+escape(os)+"&extrato="+escape(extrato);
    var curDateTime        = new Date();
    http_forn[curDateTime] = createRequestObject();
    http_forn[curDateTime].open('GET',url,true);
    http_forn[curDateTime].onreadystatechange = function()
    {
        if( http_forn[curDateTime].readyState == 4 )
        {
            if( http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304 )
            {
                var response = http_forn[curDateTime].responseText.split("|");
                if( response[0] == "ok" )
                {
                    alert(response[1]);
                    botao.value='MO ZERADA';
                    botao.disabled='true';
                }

                if( response[0] == "0" )
                {
                    alert(response[1]);
                }
            }
        }
    }
    http_forn[curDateTime].send(null);
}

function mo2(os,extrato,btn,mo)
{
    var botao = document.getElementById(btn);
    var mobra = document.getElementById(mo);
    var acao  = 'mo2';
    url       = "<?=$PHP_SELF?>?ajax=sim&op="+acao+"&os="+escape(os)+"&extrato="+escape(extrato);
    var curDateTime = new Date();
    http_forn[curDateTime] = createRequestObject();
    http_forn[curDateTime].open('GET',url,true);
    http_forn[curDateTime].onreadystatechange = function()
    {
        if( http_forn[curDateTime].readyState == 4 )
        {
            if( http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304 )
            {
                var response = http_forn[curDateTime].responseText.split("|");
                if( response[0]=="ok" )
                {
                    alert(response[1]);
                    botao.value='M.O.' + <?php echo $real ?> + '2,00';
                    botao.disabled='true';
                    mobra.value = '2,00';
                }

                if( response[0] == "0" )
                {
                    alert(response[1]);
                }
            }
        }
    }
    http_forn[curDateTime].send(null);
}
</script>

<?php
function RemoveAcentos($Msg)
{
    $a = array(
        '/[ÂÀÁÄÃ]/' => 'A',
        '/[âãàáä]/' => 'a',
        '/[ÊÈÉË]/'  => 'E',
        '/[êèéë]/'  => 'e',
        '/[ÎÍÌÏ]/'  => 'I',
        '/[îíìï]/'  => 'i',
        '/[ÔÕÒÓÖ]/' => 'O',
        '/[ôõòóö]/' => 'o',
        '/[ÛÙÚÜ]/'  => 'U',
        '/[ûúùü]/'  => 'u',
        '/ç/'       => 'c',
        '/Ç/'       => 'C');
    // Tira o acento pela chave do array
    return preg_replace(array_keys($a), array_values($a), $Msg);
}
?>

<!--aqui-->
<?php if( strlen($msg_erro) > 0 ){ ?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
    <td valign="middle" align="center" class='error'>
        <?php
            if( is_array($msg_erro) )
                $msg_erro = implode('<br />',$msg_erro);
            echo $msg_erro;/* $msg_erro = '';*/
        ?>
    </td>
</tr>
</table>
<?php }
    if( strlen($msg_aviso) > 0 ){
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
    <td valign="middle" align="center" class='error'>
        <? echo $msg_aviso ;
        $msg_aviso = '';
        ?>

    </td>
</tr>
</table>
<?php }
echo "
<center>";
echo "<form name='frm_extrato_os' method='post' action='".$_SERVER['PHP_SELF']."'>";
echo "<input type='hidden' name='extrato' value='$extrato'>";

if( $login_fabrica != 45 || $login_fabrica != 50 ){ // HD 385125 - item 2 da analise
    echo "<input type='hidden' name='extrato_pagamento' value='$extrato_pagamento'>";
}

echo "<input type='hidden' name='btn_acao' value=''>";

$join_log = "";
$case_log = " ";

/* PARA CONSULTAR O LOG DAS OSs FORA DE GARANTIA */
if( in_array($login_fabrica, array(11,172)) ){
    $case_log  = " case when tbl_os_log.os_atual is not null
                    then 1
                    else 0
                end as log,
                os_atual as os_log,";
    $join_log  = " LEFT JOIN tbl_os_log on tbl_os.os = tbl_os_log.os_atual ";
    $group_log = " os_atual,";
}

/*
Verifica se a ação é "RECUSAR" ou "ACUMULAR"
para somente mostrar a tela para a digitação da observação.
*/
if( strlen($select_acao) == 0 && strlen($extrato) > 0 ){
    //HD 205958: Este SQL estava sendo executado dentro do laço que verifica as OSs do extrato
    //           Coloquei fora, pois estava errado
    $sql2 = "SELECT  liberado ,aprovado, protocolo
            FROM tbl_extrato
            WHERE extrato = $extrato
            AND   fabrica = $login_fabrica";
    $res2 = pg_query($con,$sql2);
    $liberado = pg_fetch_result($res2,0,liberado);
    $aprovado = pg_fetch_result($res2,0,aprovado);
    $xxprotocolo = pg_fetch_result($res2,0,protocolo);

    if(isset($novaTelaOs) and $login_fabrica != 138){
        $campos = ", tbl_os_produto.serie ";
        $join_produto = " LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                        LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto ";
    }else{
        $campos = ", tbl_os.serie ";
        $join_produto = " LEFT JOIN tbl_produto ON  tbl_produto.produto = tbl_os.produto ";
    }

    if (in_array($login_fabrica, array(35,138,145)) or $novaTelaOs) {
        $distinct_os = "DISTINCT tbl_os.os AS os,";
    }else {
         $distinct_os = "tbl_os.os,";
    }

    /* Programa: $PHP_SELF ### Fabrica: $login_fabrica ### Admin: $login_admin */

    if($login_fabrica == 74 ){
        $campo_cancelada = " tbl_os.cancelada,  ";
    }

    if (in_array($login_fabrica, [152,180,181,182])) {
        $campo_obs_extrato_status = "( select obs from tbl_extrato_status where extrato = tbl_extrato.extrato order by data desc limit 1) as observacao_extrato_status,  ";
    }

    if ($login_fabrica == 203){
        $campo_os_campo_extra = "tbl_os_campo_extra.campos_adicionais::jsonb->>'produto_recebido_via_correios' AS recebido_via_correios ,";
        $join_os_campo_extra = "JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = $login_fabrica";
    }

    $sql = "
        SELECT
            {$distinct_os}
            tbl_os.os                                                                  ,
            lpad (tbl_os.sua_os,10,'0')                                 AS ordem       ,
            tbl_os.sua_os                                                              ,
            to_char (tbl_os.data_digitacao,'DD/MM/YYYY')                AS data        ,
            to_char (tbl_os.data_abertura ,'DD/MM/YYYY')                AS abertura    ,
            to_char (tbl_os.data_fechamento,'DD/MM/YYYY')               AS fechamento  ,
            to_char (tbl_os.finalizada     ,'DD/MM/YYYY')               AS finalizada  ,
            to_char (tbl_os.data_conserto  ,'DD/MM/YYYY')               AS conserto    ,
            tbl_os.data_abertura - tbl_os.data_conserto::DATE           AS dias         ,
            {$campo_os_campo_extra}
            tbl_os.consumidor_revenda                                                   ,
            tbl_os.codigo_fabricacao                                                    ,
            tbl_os.consumidor_nome                                                      ,
            tbl_os.consumidor_cidade                                                    ,
            tbl_os.consumidor_estado                                                    ,
            tbl_os.consumidor_fone                                                      ,
            tbl_os.revenda_nome                                                         ,
            tbl_os.troca_garantia                                                       ,
            tbl_os.custo_peca                                                           ,
            (
                (DATE_PART('year', tbl_os.data_abertura) - DATE_PART('year', tbl_os.data_nf)) * 12 +
                (DATE_PART('month', tbl_os.data_abertura) - DATE_PART('month', tbl_os.data_nf))
            ) as qtde_mes,
            $campo_cancelada
            tbl_os.data_fechamento                                                      ,";

            if(in_array($login_fabrica, array(30,104,134,145,160,190,200)) or $replica_einhell){
                $sql .= " tbl_os.pecas AS total_pecas ,";
            }elseif($login_fabrica == 42){
                $sql .= "(SELECT SUM (tbl_os_item.qtde * COALESCE(tbl_os_item.custo_peca,tbl_os_item.preco)) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) JOIN tbl_os oss USING(os) WHERE tbl_os_produto.os = tbl_os.os AND oss.pecas > 0) AS total_pecas  ,"; 
            }else{
                $sql .= "(SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) JOIN tbl_servico_realizado USING (servico_realizado) WHERE tbl_os_produto.os = tbl_os.os ) AS total_pecas  ,";
            }

            if ($login_fabrica == 158) {
                $campoProtocolo = "tbl_extrato.protocolo,";
            } else {
                $campoProtocolo = "lpad (tbl_extrato.protocolo::text,6,'0') AS protocolo,";
            }

            $sql .= " tbl_os.mao_de_obra                                           AS total_mo        ,
            tbl_os.qtde_km                                               AS qtde_km         ,
            tbl_os.qtde_km_calculada                                     AS qtde_km_calculada,
            COALESCE(tbl_os.pedagio, 0)                                  AS pedagio,
            tbl_os.cortesia                                                                 ,
            COALESCE(tbl_os.qtde_diaria,0)                               AS qtde_visitas    ,
            tbl_os.nota_fiscal                                                              ,
            to_char(tbl_os.data_nf, 'DD/MM/YYYY')                        AS data_nf         ,
            tbl_os.nota_fiscal_saida                                                        ,
            tbl_os.posto                                                                    ,
            tbl_produto.produto                                                             ,
            tbl_produto.referencia                                                          ,
            tbl_produto.descricao                                                           ,
            tbl_os_extra.extrato                                                            ,
            tbl_os_extra.os_reincidente                                                     ,
            tbl_os.observacao                                                               ,
            tbl_os.motivo_atraso                                                            ,
            tbl_os_extra.motivo_atraso2                                                     ,
            tbl_os_extra.mao_de_obra_desconto                                               ,";
            if($login_fabrica == 30){
                $sql .= " tbl_os_campo_extra.campos_adicionais ,  ";
            }
            if (in_array($login_fabrica, array(125))) {
                $sql .= " tbl_os.taxa_visita                              ,";
            }else{
                $sql .= " tbl_os_extra.taxa_visita                        ,";
            }
            $sql .= " tbl_os_extra.valor_total_deslocamento AS entrega_tecnica                        ,
            tbl_os.obs_reincidencia                                                         ,
            tbl_os.valores_adicionais                                                       ,
            to_char (tbl_extrato.data_geracao,'DD/MM/YYYY')              AS data_geracao    ,
            tbl_extrato.total                                            AS total           ,
            tbl_extrato.mao_de_obra                                      AS mao_de_obra     ,
            tbl_extrato.pecas                                            AS pecas           ,
            tbl_extrato.deslocamento                                     AS total_km        ,
            tbl_extrato.admin                                            AS admin_aprovou   ,
            tbl_extrato.recalculo_pendente                                                  ,
            $campoProtocolo
            tbl_posto.nome                                               AS nome_posto      ,
            tbl_posto_fabrica.codigo_posto                               AS codigo_posto    ,
            tbl_tipo_posto.descricao                                     AS descricao_tipo_posto,
        tbl_posto_fabrica.reembolso_peca_estoque ,
            tbl_posto_fabrica.prestacao_servico                                             ,
            tbl_extrato_pagamento.valor_total                                               ,
            tbl_extrato_pagamento.acrescimo                                                 ,
            tbl_extrato_pagamento.desconto                                                  ,
            tbl_extrato_pagamento.valor_liquido                                             ,
            tbl_extrato_pagamento.nf_autorizacao                                            ,
            tbl_extrato_pagamento.baixa_extrato                                             ,
            to_char (tbl_extrato.previsao_pagamento,'DD/MM/YYYY') AS previsao_pagamento     ,
            to_char (tbl_extrato.data_recebimento_nf,'DD/MM/YYYY') AS data_recebimento_nf   ,
            to_char (tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
            to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento  ,
            tbl_extrato_pagamento.autorizacao_pagto                                         ,
            tbl_os_extra.obs_adicionais                                                     ,
            tbl_os_extra.valor_total_hora_tecnica                                           ,
            case
                when tbl_os.fabrica in(52) then
                    tbl_os_extra.valor_por_km
                else
                    tbl_posto_fabrica.valor_km
            end as valor_km ,
            tbl_extrato_pagamento.obs                                                       ,
            tbl_extrato_pagamento.extrato_pagamento                                         ,
            (SELECT COUNT(1) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os ) AS os_sem_item,
            (SELECT peca_sem_estoque FROM tbl_os_item JOIN tbl_os_produto using(os_produto) WHERE tbl_os_produto.os = tbl_os.os and peca_sem_estoque is true limit 1) AS peca_sem_estoque ,
            $campo_obs_extrato_status
            $case_log
            tbl_os.data_fechamento - tbl_os.data_abertura  as intervalo                     ,
            (SELECT login FROM tbl_admin WHERE tbl_admin.admin = tbl_os.admin AND tbl_admin.fabrica = $login_fabrica) AS admin,

            tbl_familia.descricao       as familia_descr,
            tbl_familia.familia         as familia_id,
            tbl_familia.codigo_familia  as familia_cod,
            tbl_marca.nome as marca
            $campos ";
            if($login_fabrica == 1){
                $sql.= ", tbl_extrato_financeiro.admin_pagto,
                    case when campos_adicionais ~'\\\\\\\\' then tbl_os_campo_extra.campos_adicionais::JSONB->>'txAdm' else replace(campos_adicionais,'\\','\\\\')::JSONB->>'txAdm' end AS taxa_administrativa,
                    case when campos_adicionais ~'\\\\\\\\' then campos_adicionais::JSONB->>'TxAdmGrad' else replace(campos_adicionais,'\\','\\\\')::JSONB->>'TxAdmGrad' end AS taxa_administrativa_gradual
                ";
            }
            if($login_fabrica == 52){ //hd_chamado=2598225
                $sql.=", to_char (tbl_os.data_conserto,'DD/MM/YYYY') AS data_conserto";
            }
            $campoExtrato = "tbl_os_extra.extrato";
            if ($login_fabrica == 190 && $xxprotocolo == 'extrato_recebimento') {
                $campoExtrato = "tbl_os_extra.extrato_recebimento";
            }
            if ($login_fabrica == 183) {
                $sql .= ", (SELECT tbl_tecnico.nome FROM tbl_tecnico_agenda JOIN tbl_tecnico USING(tecnico) WHERE tbl_tecnico_agenda.os = tbl_os.os AND tbl_tecnico_agenda.fabrica = $login_fabrica ORDER BY tbl_tecnico_agenda.data_input DESC LIMIT 1) AS nome_tecnico ";
            }
        $sql .= " FROM tbl_extrato
        LEFT JOIN tbl_extrato_pagamento ON  tbl_extrato_pagamento.extrato  = tbl_extrato.extrato
        LEFT JOIN tbl_os_extra          ON  {$campoExtrato }  = tbl_extrato.extrato
        LEFT JOIN tbl_os                ON  tbl_os.os                      = tbl_os_extra.os
        {$join_os_campo_extra}
        $join_log
        $join_produto
        JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_extrato.fabrica AND tbl_fabrica.fabrica = $login_fabrica
        JOIN      tbl_posto             ON  tbl_posto.posto                = tbl_extrato.posto
        JOIN      tbl_posto_fabrica     ON  tbl_posto.posto                = tbl_posto_fabrica.posto
                                        AND tbl_posto_fabrica.fabrica      = $login_fabrica
        LEFT JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = $login_fabrica
        LEFT JOIN tbl_familia           ON  tbl_produto.familia            = tbl_familia.familia
        AND tbl_familia.fabrica            = $login_fabrica
        LEFT JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca
        ";
        if($login_fabrica == 1){
            $sql .= "
            LEFT JOIN tbl_os_campo_extra ON  tbl_os_campo_extra.os = tbl_os.os
                                         AND tbl_os_campo_extra.fabrica = tbl_os.fabrica
            LEFT JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
            ";
        }
        if($login_fabrica == 30){
            $sql .= "LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica";
        }
        
        $sql .= " WHERE       tbl_extrato.fabrica = $login_fabrica
        AND         tbl_extrato.extrato = $extrato ";
        if( $login_fabrica == 45 ){ //HD 39933
            $sql .= "
                AND    tbl_os.mao_de_obra notnull
                AND    tbl_os.pecas       notnull
                AND    ((SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) IS NULL OR (SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) NOT IN (15)) ";
        }

        if ($login_fabrica == 148) {
            $sql_yanmar = $sql." AND tbl_os.tipo_atendimento = 217 ";
            $sql .= " AND tbl_os.tipo_atendimento <> 217 ";
        }

    if(!in_array($login_fabrica, array(2,50,30,35,138,145)) and !$novaTelaOs){
        $sql .= "ORDER BY tbl_os_extra.os_reincidente, lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')               ASC,
                        replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";
        if ($login_fabrica == 148) {
            $sql_yanmar .= "ORDER BY tbl_os_extra.os_reincidente, lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')               ASC,
                        replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";
        }
    } else if ($login_fabrica == 50) { // HD 107642 (augusto)
        $sql .= "ORDER BY   tbl_familia.descricao ASC,
                            tbl_os_extra.os_reincidente,lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0') ASC,
                            replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";
    } else if (in_array($login_fabrica, array(138,145))) {
        $sql .= " ORDER BY tbl_os.os ";
    } else if ($login_fabrica == 30) {
        $sql .= " ORDER BY tbl_os.consumidor_cidade,tbl_os_extra.os_reincidente, lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')  ASC,
                        replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";
    } elseif ($login_fabrica == 35) {
        $sql .= "ORDER BY tbl_os_extra.os_reincidente, ordem ASC";
    } elseif ($novaTelaOs) {
        $sql .= "ORDER BY tbl_os_extra.os_reincidente, tbl_os.os, data ASC";

    }else {
        $sql .= " ORDER BY replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC ";
    }
 
    if (in_array($login_fabrica, array(1,11,51,172))) { 
        $res = pg_query($con,$sql);
        $registros = pg_num_rows($res);
    }else{
        $sqlCount  = "SELECT count(*) FROM (";
        $sqlCount .= $sql;
        $sqlCount .= ") AS count";
        // ##### PAGINACAO ##### //
        require "_class_paginacao.php";

        // definicoes de variaveis
        $max_links = 11;                // máximo de links à serem exibidos
        $max_res   = 30;                // máximo de resultados à serem exibidos por tela ou pagina
        $mult_pag  = new Mult_Pag();    // cria um novo objeto navbar
        $mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página
        $res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
        // ##### PAGINACAO ##### //

        if( $login_fabrica == 45 ){
            $resxls = pg_query($con,$sql);
        }
    }

    $rr               = 0;
    $reincidencias_os = array();

    if ($login_fabrica == 148) {
        $res_yanmar = pg_query($con,$sql_yanmar);
    }

    if(@pg_num_rows($res) == 0){
        if ($login_fabrica == 148) {
            if(pg_num_rows($res_yanmar) == 0){
                echo "<h1>Nenhum resultado encontrado.</h1>";
            }else{
                $Monta_Tabela_Yanmar = 1;
                GeraTabelaEntregaTecnica($res_yanmar);
            }
        }else{
            echo "<h1>Nenhum resultado encontrado.</h1>";
        }
    } else { ?>
        <table width="700" border="0" cellpadding="0" cellspacing="0" align="center" style="font-size: 11px;">
        <tr>
            <td bgcolor="#FFCCCC">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b><?=traduz('REINCIDÊNCIAS')?></b></td>
        </tr>
        <?php if ($login_fabrica == 203 AND strtolower(pg_fetch_result ($res,0,'descricao_tipo_posto')) == "autorizada premium" ){?>
            <tr>
                <td bgcolor="#93c9a6">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b><?=traduz('RECEBIDO VIA CORREIOS')?></b></td>
            </tr>
        <?php } ?>

            <? if($login_fabrica == 51) { ?>
                <tr>
                    <td bgcolor="#CCFF99">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>PRODUTO TROCADO</b></td>
                </tr>
            <? }
            if ($login_fabrica == 30) { ?>
                <tr>
                    <td bgcolor="#CCFF99">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>REINCIDÊNCIAS MAIS 90 DIAS</b></td>
                </tr>
                <tr>
                    <td bgcolor="#87CEFA">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>OS COM CARÊNCIA DE 90 DIAS</b></td>
                </tr>
            <? }
            if (in_array($login_fabrica, array(30,50,74,85,90,91,115,116,117,120,201))) { ?>
                <tr>
                    <td bgcolor="#FFCC99">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>INTERVENÇÃO DE KM EM ABERTO</b></td>
                </tr>
            <? }
            # HD 62078
            if ($login_fabrica == 45) {
                echo "<tr>";
                echo "<td bgcolor='#CCCCFF'>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                    <td width='100%' valign='middle' align='left'>
                    &nbsp;<b>OS COM RESSARCIMENTO FINANCEIRO</b></td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td bgcolor='#FFCC66'>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                    <td width='100%' valign='middle' align='left'>
                    &nbsp;<b>OS COM TROCA DE PRODUTO</b></td>";
                echo "</tr>";
            }
        if( $login_fabrica == 2 ){ // HD 19580 ?>
        <tr><td height="3"></td></tr>
        <tr>
            <td bgcolor="#FFCC00">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>OS FECHADA ATÉ FINAL DE 2007</b></td>
        </tr>
        <? }
        if( $login_fabrica == 1 ){ ?>
        <tr><td height="3"></td></tr>
        <tr>
            <td bgcolor="#D7FFE1">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>OS CORTESIA</b></td>
        </tr>
        <tr><td height="3"></td></tr>
        <tr>
            <td bgcolor="#d9ce94">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>Troca de peça sem estoque</b></td>
        </tr>
        <tr><td height="3"></td></tr>
        <tr>
            <td bgcolor="#FFCC00">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>Pendência de Documentação</b></td>
        </tr>
        <tr><td height="3"></td></tr>
        <tr>
            <td bgcolor="#FFCCFF">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>Reincidências com mesmo produto e nota</b></td>
        </tr>
        <?php } 
        if( $login_fabrica == 178 ){ ?>
        <tr><td height="3"></td></tr>
        <tr>
            <td bgcolor="#D7FFE1">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>OS MAIOR QUE 12 MESES</b></td>
        </tr>
        <? } ?>
        </table>
        <br>
    <?php

        if( strlen ($msg_erro) == 0 ){
            $extrato_pagamento         = pg_fetch_result ($res,0,extrato_pagamento) ;
            $valor_total               = pg_fetch_result ($res,0,valor_total) ;
            $acrescimo                 = pg_fetch_result ($res,0,acrescimo) ;
            $desconto                  = pg_fetch_result ($res,0,desconto) ;
            $valor_liquido             = pg_fetch_result ($res,0,valor_liquido) ;
            $nf_autorizacao            = pg_fetch_result ($res,0,nf_autorizacao) ;
            $previsao_pagamento        = pg_fetch_result ($res,0,previsao_pagamento) ;
            $data_vencimento           = pg_fetch_result ($res,0,data_vencimento) ;
            $data_pagamento            = pg_fetch_result ($res,0,data_pagamento) ;
            $obs                       = pg_fetch_result ($res,0,obs) ;
            $autorizacao_pagto         = pg_fetch_result ($res,0,autorizacao_pagto) ;
            $data_recebimento_nf       = pg_fetch_result ($res,0,data_recebimento_nf) ;
            $codigo_posto              = pg_fetch_result ($res,0,codigo_posto) ;
            $posto                     = pg_fetch_result ($res,0,posto) ;
            $protocolo                 = pg_fetch_result ($res,0,protocolo) ;
            $peca_sem_preco            = pg_fetch_result ($res,0,peca_sem_preco) ;
            $os_sem_item               = pg_fetch_result ($res,0,'os_sem_item') ;
            $admin_aprovou             = pg_fetch_result ($res,0,admin_aprovou) ;
            $recalculo_pendente        = pg_fetch_result ($res,0,recalculo_pendente) ;

            if($login_fabrica == 1){
                $admin_pagto = pg_fetch_result($res, 0, "admin_pagto");
            }

            if ($login_fabrica == 42) {
                $prestacao_servicos = pg_fetch_result($res,0,prestacao_servico);
            }

            if (in_array($login_fabrica, [152,180,181,182])) {
                $observacao_extrato_status = pg_fetch_result($res, 0, observacao_extrato_status);
            }
            $msg_erro = "";
        }

        if($login_fabrica==45){ //HD 26972 - 8/8/2008
            if (strlen($extrato_pagamento) > 0 AND strlen($valor_liquido) > 0 AND strlen($valor_total) > 0 AND strlen($data_vencimento) > 20/2/20090 AND strlen($data_pagamento) > 0){
                $ja_baixado = true;
            }else{
                $ja_baixado = false;
            }
        }else if (strlen($extrato_pagamento) > 0 ){

            if(($login_fabrica == 134) ){
                $baixa_extrato = pg_fetch_result ($res,0,"baixa_extrato") ;

                if(!empty($baixa_extrato)){
                    $ja_baixado = true;
                }else{
                    $ja_baixado = false;
                }
            }else{
                $ja_baixado = true;
            }
        }

        if($login_fabrica==45){//HD 39377 12/9/2008
        $sql = "SELECT count(*) as qtde
                FROM tbl_os
                JOIN tbl_os_extra USING(os)
                WHERE tbl_os.mao_de_obra notnull
                and tbl_os.pecas       notnull
                and ((
                        SELECT tbl_os_status.status_os
                        FROM tbl_os_status
                        WHERE tbl_os_status.os = tbl_os.os
                        ORDER BY tbl_os_status.data DESC LIMIT 1
                        ) IS NULL
                    OR (SELECT tbl_os_status.status_os
                        FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os
                        ORDER BY tbl_os_status.data DESC LIMIT 1
                        ) NOT IN (15)
                    )
                and tbl_os_extra.extrato = $extrato";
        }else{
	     $campoExtr = "tbl_os_extra.extrato = $extrato";
             if ($login_fabrica == 190 && $xxprotocolo == 'extrato_recebimento') {
                 $campoExtr = "tbl_os_extra.extrato_recebimento=$extrato";
             }

            $sql = "SELECT count(*) as qtde
                    FROM   tbl_os_extra
                    WHERE  $campoExtr";
        }
        $resx = pg_query($con,$sql);

        if (pg_num_rows($resx) > 0) $qtde_os = pg_fetch_result($resx,0,qtde);
        if($login_fabrica == 30) {
            $cols = "colspan='2'";
        }
    
        if($login_fabrica == 30){
            if ($recalculo_pendente == 't') {
                $mensagem_extrato[] = "Extrato pendente de recálculo";
            }

            if (count($mensagem_extrato) > 0) {
                $display = "block";
                $mensagem_extrato = implode('<br>', $mensagem_extrato);
            }
            else {
                $display = "none";
            }

            echo "<TABLE id='mensagem_extrato' width='700' border='0' align='center' cellspacing='1' cellpadding='0' style='background-color: #EEBBBB; padding: 3px; color: #990000; font-size: 11pt; margin-bottom: 20px; display: $display; text-align: center;'>";
            echo "<tr><td colspan='100%' id='mensagem_extrato_td'>$mensagem_extrato</td></tr>";
            echo "</TABLE>";
        }

        echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='5'>";

        echo"<TR class='menu_top'>";
        echo"<TD align='left'> ".traduz('Extrato').": ";
        echo ($login_fabrica == 1) ? $protocolo : $extrato;
        echo "</TD>";
        echo "<TD align='left'> ".traduz('Data Geração').": " . pg_fetch_result ($res,0,data_geracao) . "</TD>";
        if($login_fabrica == 15) {
            $dd = substr($liberado, 8,2);
            $mm = substr($liberado, 5,2);
            $aaaa = substr($liberado, 0,4);

            echo "<TD align='left'> Data Liberação: $dd/$mm/$aaaa</TD>";
        }

        if (in_array($login_fabrica, array(30)))
            $cols = "colspan='3'";

        echo"<TD align='left' $cols> ".traduz('Qtde de OS').": ". $qtde_os ."</TD>";

        //HD 31799 esmaltec
        if($login_fabrica == 30 or $login_fabrica == 91) {
            echo"</TR>";
            echo"<TR class='menu_top'>";
            if($login_fabrica != 91) {
                echo"<TD align='left'> Total de Peças: R$ " . number_format(pg_fetch_result ($res,0,pecas),2,",",".") . "</TD>";
            }
            echo"<TD align='left'> Total de MO: R$ " . number_format(pg_fetch_result ($res,0,mao_de_obra),2,",",".") . "</TD>";
            echo"<TD align='left'> Total de KM: R$ ". number_format(pg_fetch_result ($res,0,total_km),2,",",".") ."</TD>";
            if (in_array($login_fabrica, array(30)) and 1==2) {
                for ($count_entrega=0; $count_entrega < pg_num_rows($res); $count_entrega++) {
                    $total_taxa_entrega += 0; //pg_fetch_result($res, $count_entrega, 'valores_adicionais');
                }
                echo"<TD align='left'> Total Taxa entrega: R$ ". number_format($total_taxa_entrega,2,",",".") ."</TD>";
            }
        }

        if($login_fabrica == 74){

           $sql_km_atlas = "SELECT tbl_posto_fabrica.parametros_adicionais
                            FROM tbl_posto_fabrica
                            JOIN tbl_extrato ON tbl_extrato.fabrica = tbl_posto_fabrica.fabrica
                            WHERE tbl_posto_fabrica.posto = $posto
                            AND tbl_extrato.extrato = $extrato
                            AND tbl_posto_fabrica.fabrica = $login_fabrica
            ";
            $resAtlas = pg_query($con,$sql_km_atlas);

            if(pg_num_rows($resAtlas) > 0){

                $parametros_adicionais = json_decode(pg_fetch_result($resAtlas,0,parametros_adicionais),TRUE);

                $valor_km_fixo = $parametros_adicionais['valor_km_fixo'];
                $valorkm_fixo = number_format(($valor_km_fixo),2,",",".");

                $cols = "colspan = '4' ";
            }else{
                $cols = "colspan = '3' ";
            }

            echo"<TD align='left'> KM FIXO: ". $valorkm_fixo ."</TD>";

        }

        if($login_fabrica == 157){

           $sqlelg = "SELECT  tbl_extrato_pagamento.data_nf    ,
                        tbl_extrato_pagamento.nf_peca
                    FROM    tbl_extrato_pagamento
                    JOIN    tbl_extrato ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato
                    WHERE   tbl_extrato_pagamento.extrato = $extrato
                    AND     tbl_extrato.fabrica = $login_fabrica
                    ORDER BY tbl_extrato_pagamento.extrato_pagamento ASC LIMIT 1";

            $reselg = pg_query($con,$sqlelg);

                if(pg_num_rows($reselg) > 0){

                    echo"<TR class='menu_top'>";
                        echo"<TD align='left'> Data da NF Pagamento: ".mostra_data(pg_fetch_result($reselg, 0, "data_nf"))."</TD>";
                        echo"<TD align='left'> NF Pagamento: ".pg_fetch_result($reselg, 0, "nf_peca")."</TD>";
                    // echo"</TR>";
                }
        }

        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
            if ($login_fabrica == 1) {
                $taxa_administrativa_gradual = pg_fetch_result($res, $i, taxa_administrativa_gradual);
                $total_pecas           = trim(pg_fetch_result ($res,$i,'total_pecas'));
            }
        }

        if (in_array($login_fabrica, array(30))) {
            $total = number_format(pg_fetch_result($res,0,total) + $total_taxa_entrega,2,",",".");
        } else if ($login_fabrica == 1) {
            $totalTx = somaTxExtratoBlack($extrato);

            $total = number_format(pg_fetch_result($res,0,total) + $totalTx ,2,",",".");
        } else {
            $total = number_format(pg_fetch_result ($res,0,total),2,",",".");
	    $total_sem_format = pg_fetch_result ($res,0,total);

        }

        if(in_array($login_fabrica, array(190))){
            $sqlCont = "SELECT DISTINCT tbl_contrato_os.contrato, tbl_contrato.campo_extra
                        FROM tbl_os
                        JOIN tbl_os_extra on tbl_os_extra.os=tbl_os.os
                        JOIN tbl_contrato_os on tbl_contrato_os.os=tbl_os.os
                        JOIN tbl_contrato on tbl_contrato_os.contrato=tbl_contrato.contrato AND tbl_contrato.fabrica = $login_fabrica
                       WHERE (tbl_os_extra.extrato = $extrato OR tbl_os_extra.extrato_recebimento = $extrato)
                         AND tbl_os.fabrica = $login_fabrica";

            $resCont = pg_query($con,$sqlCont);
            if (pg_num_rows($resCont) > 0) {
                foreach (pg_fetch_all($resCont) as $key => $value) {
                    $xcampoExtra = json_decode($value['campo_extra'],1);
                    if (isset($xcampoExtra["valor_mao_obra_fixa"])) {
                        $valor_mao_obra_fixa += $xcampoExtra["valor_mao_obra_fixa"];
                    } else {
                        $valor_mao_obra_fixa += 0;
                    }
                }
             }

            $total = number_format(($total_sem_format+$valor_mao_obra_fixa),2,",",".");
	   }

        echo"<TD align='left'> Total: " . $real . $total . "</TD>";
        echo"</TR>";

        echo"<TR class='menu_top'>";
        echo"<TD align='left' $cols > ".traduz('Código').": " . pg_fetch_result ($res,0,codigo_posto) . " </TD>";
        $cols = ($login_fabrica == 30) ? 6 : 3 ;
        if($login_fabrica == 15){
            $cols += 1;
        }
        echo"<TD align='left' colspan='$cols'> ".traduz('Posto').": " . pg_fetch_result ($res,0,nome_posto) . "  </TD>";
        echo"</TR>";
        if ($login_fabrica == 190) {
            $tipo_extrato = (trim($xxprotocolo) == 'extrato_recebimento') ? "Extrato de Recebimento" : "Extrato de Pagamento";
                echo"<TR class='menu_top'><TD align='left' colspan='100%'> Tipo de Extrato:  " .$tipo_extrato . "  </TD></TR>";
        }
        if ($login_fabrica == 158) {
            $sqlPA = "SELECT preco AS valor_mao_obra
                FROM tbl_posto_preco_unidade
                    JOIN tbl_distribuidor_sla USING(distribuidor_sla)
                    JOIN tbl_extrato ON tbl_extrato.posto = tbl_posto_preco_unidade.posto
                    JOIN tbl_extrato_agrupado USING(extrato)
                WHERE tbl_extrato.extrato = $extrato
                    AND tbl_extrato.fabrica                  = $login_fabrica
                    AND tbl_extrato.protocolo                ='Fora de Garantia'
                    AND tbl_posto_preco_unidade.fabrica      = $login_fabrica
                    AND tbl_distribuidor_sla.unidade_negocio = tbl_extrato_agrupado.codigo
                    ";
            $resPA = pg_query($con,$sqlPA);

            if (pg_num_rows($resPA) > 0) {

                $valor_mao_obra = number_format(pg_fetch_result($resPA,0,valor_mao_obra),2,',','.');

                if ($valor_mao_obra > 0) {
                    $precofixo = "<td align='left' colspan='3' >Preço Fixo Extrato: " . $real . $valor_mao_obra . "</td>";
                    $cols = "";
                }
            } else {
                $cols = "colspan = '4' ";
                $precofixo = "";
            }

            /*$sqlUnidade = "SELECT
                                CASE
                                    WHEN tbl_distribuidor_sla.unidade_negocio = '6800' THEN
                                    tbl_distribuidor_sla.unidade_negocio||' - WOW NUTRICION'
                                ELSE
                                    tbl_distribuidor_sla.unidade_negocio||' - '||tbl_cidade.nome
                                END AS cidade
                        FROM tbl_distribuidor_sla
                        JOIN tbl_cidade USING(cidade)
                        JOIN tbl_extrato_agrupado ON tbl_extrato_agrupado.codigo=tbl_distribuidor_sla.unidade_negocio
                        WHERE tbl_distribuidor_sla.fabrica = $login_fabrica
                        AND tbl_distribuidor_sla.centro IN('BAAA','GRAN','BFAT')
                        AND tbl_extrato_agrupado.extrato={$extrato}";*/

            $sqlUnidade = "SELECT DISTINCT ON (tbl_distribuidor_sla.unidade_negocio)
                                tbl_distribuidor_sla.unidade_negocio||' - '||tbl_unidade_negocio.nome AS cidade
                                FROM tbl_distribuidor_sla
                                JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo = tbl_distribuidor_sla.unidade_negocio
                                WHERE tbl_distribuidor_sla.fabrica = {$login_fabrica}
                                AND tbl_distribuidor_sla.centro IN('BAAA','GRAN','BFAT')
                                AND tbl_extrato_agrupado.extrato = {$extrato}
                                GROUP BY tbl_distribuidor_sla.unidade_negocio, tbl_unidade_negocio.nome
                                ORDER BY tbl_distribuidor_sla.unidade_negocio ASC";

            $resUnidade = pg_query($con, $sqlUnidade);
            if (pg_num_rows($resUnidade) > 0) {
                $unidadeNegocio = "<td align='left' colspan='3' >Unidade de Negócio: ".pg_fetch_result($resUnidade, 0, 'cidade')."</td>";
            } else {
				if(empty($precofixo))  {
					$cols = "colspan = '4' ";
				}

                $unidadeNegocio = "";
            }

            echo "
                <tr class='menu_top'>
                      $unidadeNegocio
                    <td align='left' $cols>Tipo: $protocolo</td>
                    $precofixo
                </tr>
            ";
        }

        if($login_fabrica == 1  ) {
            if (strlen($admin_pagto)>0){
                $sql = "SELECT nome_completo
                    FROM   tbl_admin
                    WHERE  admin = $admin_pagto";
                $res_adm = pg_query($con,$sql);

                if (pg_num_rows($res_adm) > 0){
                    $nome_completo = pg_fetch_result($res_adm,0,nome_completo);
                    echo"<TR class='menu_top'>";
                    echo"<TD align='left' colspan='1'> Admin que aprovou Pagamento:</TD>";
                    echo"<TD align='left' colspan='3'> $nome_completo</TD>";
                    echo"</TR>";
                }
            }

            $verificaAdmin = "SELECT nome_completo, conferido
                 FROM tbl_extrato_status
                 JOIN tbl_admin ON tbl_extrato_status.admin_conferiu = tbl_admin.admin
                 WHERE extrato = {$extrato}
                 and tbl_extrato_status.obs ='Conferido'";
            $resConferido = pg_query($con, $verificaAdmin);
            $numRowsConferido  = pg_num_rows($resConferido);
            if($numRowsConferido > 0){
                $admin_conferido = pg_fetch_result($resConferido, 0, "nome_completo");
                $data_conferido = date("d/m/Y" , strtotime(pg_fetch_result($resConferido, 0, "conferido")));
            }
            if ($numRowsConferido > 0){
                $nome_completo = pg_fetch_result($res_adm,0,nome_completo);
                echo"<TR class='menu_top'>";
                echo"<TD align='left' colspan='1'> Admin que conferiu a Pendência:</TD>";
                echo"<TD align='left' colspan='3'> $admin_conferido em {$data_conferido}</TD>";
                echo"</TR>";
            }

        }

        if($login_fabrica == 43 and strlen($admin_aprovou)>0 ) {
            $sql = "SELECT nome_completo
                    FROM   tbl_admin
                    WHERE  admin = $admin_aprovou";
            $res_adm = pg_query($con,$sql);

            if (pg_num_rows($res_adm) > 0){
                $nome_completo = pg_fetch_result($res_adm,0,nome_completo);
                echo"<TR class='menu_top'>";
                echo"<TD align='left' colspan='1'> Admin que aprovou:</TD>";
                echo"<TD align='left' colspan='3'> $nome_completo</TD>";
                echo"</TR>";
            }
        }
        echo"</TABLE>";
        echo"<br>";

        if ($login_fabrica <> 6) {
            $sql = "SELECT  count(*) as qtde,
                            tbl_linha.nome
                    FROM   tbl_os
                    JOIN   tbl_os_extra  ON tbl_os_extra.os     = tbl_os.os
                    JOIN   tbl_produto   ON tbl_produto.produto = tbl_os.produto
                    JOIN   tbl_linha     ON tbl_linha.linha     = tbl_produto.linha
                                        AND tbl_linha.fabrica   = $login_fabrica
                    WHERE  tbl_os_extra.extrato = $extrato ";
                    if($login_fabrica==45){
                        $sql .= "
                        and    tbl_os.mao_de_obra notnull
                        and    tbl_os.pecas       notnull
                        and    ((SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) IS NULL OR (SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) NOT IN (15)) ";
                    }
                    $sql .= " GROUP BY tbl_linha.nome
                    ORDER BY count(*)";
            $resx = pg_query($con,$sql);

            if (pg_num_rows($resx) > 0) {
                echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='5'>";
                echo "<TR class='menu_top'>";
                echo "<TD align='left'>LINHA</TD>";
                if($login_fabrica == 104){
                    echo "<TD align='center'>MARCA</TD>";
                }
                echo "<TD align='center'>QTDE OS</TD>";
                echo "</TR>";

                for ($i = 0 ; $i < pg_num_rows($resx) ; $i++) {
                    $linha = trim(pg_fetch_result($resx,$i,nome));
                    $qtde  = trim(pg_fetch_result($resx,$i,qtde));

                    echo "<TR class='menu_top'>";
                    echo "<TD align='left'>$linha</TD>";
                    if($login_fabrica == 104){
                        echo "<td align='center'>";
                        echo mostraMarcaExtrato($extrato);
                        echo    "</td>";
                    }
                    echo "<TD align='center'>$qtde</TD>";
                    echo "</TR>";
                }
                echo "</TABLE>";
                echo"<br>";
            }
        }

        ##### INÍCIO - MONTEIRO HD-2380817 #####
        #####          Manuel HD-2416981   #####
        if ($login_fabrica == 85) {
            $sqlOS = "
            SELECT  tbl_os_extra.os as os_comentario,
                    tbl_os_extra.obs_adicionais,
                    tbl_os.mao_de_obra,
                    tbl_os.qtde_km_calculada,
                    tbl_os.pedagio,
                    tbl_extrato_lancamento.valor,
                    to_char(tbl_extrato_lancamento.data_lancamento,'DD/MM/YYYY') AS data_lancamento,
                    tbl_admin.login
            FROM    tbl_os_extra
            JOIN    tbl_os                  ON  tbl_os_extra.os = tbl_os.os
                                            AND tbl_os.fabrica = $login_fabrica
       LEFT JOIN    tbl_extrato_lancamento  ON  tbl_os_extra.extrato            = tbl_extrato_lancamento.extrato
                                            AND tbl_extrato_lancamento.fabrica  = $login_fabrica
                                            AND tbl_extrato_lancamento.os       = tbl_os_extra.os
       LEFT JOIN    tbl_admin               ON  tbl_admin.admin                 = tbl_extrato_lancamento.admin
                                            AND tbl_admin.fabrica = $login_fabrica
            WHERE   tbl_os_extra.extrato = $extrato
            AND     tbl_os.posto = $posto
            AND     obs_adicionais <> 'null'
            AND     obs_adicionais IS NOT NULL
            AND     (tbl_extrato_lancamento.descricao NOT ILIKE '%diferenciado' OR tbl_extrato_lancamento.descricao is null)";
            $resOS = pg_query($con, $sqlOS);
            if(pg_num_rows($resOS) > 0){

                $countOS = pg_num_rows($resOS);

                $comentario_os  = pg_fetch_result($resOS, $p, 'obs_adicionais');

                if(strlen($comentario_os) > 0){

                    echo "<table id='tabela_obs_ad' width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
                    echo "<caption class='menu_top'>VALORES ADICIONAIS LANÇADOS NO EXTRATO</caption>\n";
                    echo "<thead><tr class='menu_top'>\n";
                    echo "<th width='18%'>DATA LAN&Ccedil;AMENTO</th>\n";
                    echo "<th width='18%'>ORDEM DE SERVIÇO</th>\n";
                    echo "<th>HISTÓRICO</th>\n";
                    echo "<th width='10%'>VALOR</th>\n";
                    echo "<th width='18%'>ADMIN</th>\n";
                    echo "</tr></thead>\n";

                    for ($p=0; $p < $countOS; $p++) {

                        $osExtrato        = pg_fetch_result($resOS, $p, 'os_comentario');
                        $comentario_os    = pg_fetch_result($resOS, $p, 'obs_adicionais');
                        $valor_pedagio    = pg_fetch_result($resOS, $p, 'pedagio');
                        $valor_km         = pg_fetch_result($resOS, $p, 'qtde_km_calculada');
                        $valor_mao_obra   = pg_fetch_result($resOS, $p, 'mao_de_obra');
                        $valor_avulso     = pg_fetch_result($resOS, $p, 'valor');
                        $data_lancamento  = pg_fetch_result($resOS, $p, 'data_lancamento');
                        $admin            = pg_fetch_result($resOS, $p, 'login');
                        $comentario_os    = utf8_decode($comentario_os);

                        $colunaValorComentario = '';
                        $comentario_os = json_decode($comentario_os, true);

                        foreach ($comentario_os as $key => $value) {
                            if(!in_array($key,array('mao_de_obra','km','pedagio','avulso'))){
                                unset($comentario_os[$key]);
                            }
                        }

                        if (count($comentario_os)>1)
                            $spanrows = 'rowspan="'.count($comentario_os).'"';
                        else $spanrows = ' ';

                        // HD 2416981 - suporte: deixar sinalizado como lançamento avulso, para não
                        // confundir o Posto Autorizado.
                        //$cor = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';
                        $cor =  '#FFE1E1';

                        foreach ($comentario_os as $key => $value) {
                            $value = utf8_decode($value);
                            switch ($key) {
                                case 'mao_de_obra':
                                $key = "Mão de Obra";
                                $valorComentario = number_format($valor_mao_obra, 2, ',', '.');
                                break;
                                case 'km':
                                $key = "KM";
                                $valorComentario = number_format($valor_km, 2, ',', '.');
                                break;
                                case 'pedagio':
                                $key = "Pedágio";
                                $valorComentario = number_format($valor_pedagio, 2, ',', '.');
                                break;
                                case 'avulso':
                                $key = "Avulso";
                                $valorComentario = number_format($valor_avulso, 2, ',', '.');
                                break;
                            }

                            if (strlen($spanrows)) {
                                echo "<tr class='table_line' style='background-color: $cor;'>\n";
                                echo "<td align='right' $spanrows>$data_lancamento</td>";
                                echo "<td align='right' $spanrows>$osExtrato</td>";
                                $spanrows = ''; // exclui a primeira TD para o resto do TR
                            } else {
                                echo "<tr class='table_line' style='background-color: $cor;'>";
                            }
                            echo "<td><p class='servico'><span class='servico'>$key</span>$value</p></td>";
                            echo "<td align='right'>$valorComentario</td>";
                            echo "<td align='center'>$admin</td>";
                            echo '</tr>';
                        }
                    }
                    echo "</table>";
                }
            }
        }

        #####       Manuel HD-2416981   #####
        ##### FIM - MONTEIRO HD-2380817 #####

    /**
     * Inclusão de OS em um extrato
     *
     * Caso o extrato ja tenha sido liberado, não pode mais dar manutenção no extrato.
     *
    **/

    ?>
    <div style='display:none'>
        <?php 
        if(pg_numrows($res)>0){
            for($i=0;$i<pg_numrows($res);$i++){

                $sua_os             = trim(pg_fetch_result ($res,$i,'sua_os'));
                $os             = trim(pg_fetch_result ($res,$i,'os'));

                echo "<input type='checkbox' name='os[$i]' id='os_$i' value='$os'>";
                echo "<input type='hidden' name='sua_os[$i]' id='sua_os[$i]'  value='$sua_os'>";

            }
        }
        ?>
        </div>

        <?php 
        if(in_array($login_fabrica, array(190))){
            $sqlCont = "SELECT DISTINCT tbl_contrato_os.contrato, tbl_contrato.campo_extra
                        FROM tbl_os
                        JOIN tbl_os_extra on tbl_os_extra.os=tbl_os.os
                        JOIN tbl_contrato_os on tbl_contrato_os.os=tbl_os.os
                        JOIN tbl_contrato on tbl_contrato_os.contrato=tbl_contrato.contrato AND tbl_contrato.fabrica = $login_fabrica
                       WHERE (tbl_os_extra.extrato = $extrato OR tbl_os_extra.extrato_recebimento = $extrato)
                         AND tbl_os.fabrica = $login_fabrica";

            $resCont = pg_query($con,$sqlCont);
            if (pg_num_rows($resCont) > 0) {

            echo "<table border='1' align='center' width='300' cellspancing='0' cellpadding='0'>
                    <tr bgcolor='#D9E9FD'>
                        <td bgcolor='#D9E9FD' style='color:#2e4869;text-align: center;font-weight: bold;font-family: arial; font-size: 12px;'padding:10px;>Nº Contrato</td>
                        <td bgcolor='#D9E9FD' style='color:#2e4869;text-align: center;font-weight: bold;font-family: arial; font-size: 12px;padding:10px;'>Valor Mão Obra Fixo</td>
                    </tr>";

                foreach (pg_fetch_all($resCont) as $key => $value) {
                    $xcampoExtra = json_decode($value['campo_extra'],1);
                    if (isset($xcampoExtra["valor_mao_obra_fixa"])) {
                        $valor_mao_obra_fixa = "R$ ".number_format($xcampoExtra["valor_mao_obra_fixa"],2,",",".");
                    } else {
                        $valor_mao_obra_fixa = "";
                    }
                    echo "
                    <tr>
                        <td style='text-align:center'><a href='print_contrato.php?tipo=contrato&contrato=".$value['contrato']."' target='_blank'>".$value['contrato']."</a></td>
                        <td style='text-align:center'>".$valor_mao_obra_fixa."</td>
                    </tr>";
                }
                echo "
                </table>
                <br><br>";

            }

        }

    if($ja_baixado == false AND ($login_fabrica == 10 OR ($login_fabrica == 6 AND strlen($liberado)==0) OR ( in_array($login_fabrica, array(11,172)) AND strlen($liberado)==0) OR ($login_fabrica==51 AND strlen($liberado)==0)) ) {
        echo "<table border='0' align='center' width='300' cellspancing='0' cellpadding='0'>";

        echo "<tr bgcolor='#D9E9FD'>";
            echo "<td colspan='2' bgcolor='#D9E9FD' style='font-family: verdana; font-size: 10px;'><br><B>OS para ser adicionada neste extrato</B><br>&nbsp;</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td><INPUT TYPE='text' NAME='adiciona_sua_os' size='10' value='$_adiciona_sua_os'><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_os (document.frm_extrato_os.adiciona_sua_os, document.frm_extrato_os.adiciona_posto, document.frm_extrato_os.adiciona_data_abertura, document.frm_extrato_os.adiciona_extrato)' style='cursor: pointer'></td>";
            echo "<INPUT TYPE='hidden' NAME='adiciona_posto' size='10' value='$codigo_posto'>";
            echo "<INPUT TYPE='hidden' NAME='adiciona_extrato' size='10' value='$extrato'>";
            echo "<td><INPUT TYPE='hidden' NAME='adiciona_data_abertura' size='10' value='$adiciona_data_abertura'></td>";
        echo "</tr>";
        echo "</table>";
        echo "<br><br>";
    }
    
    //HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
    //           não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
    //           de extrato avuldo. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
    //           SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
    $libera_acesso_acoes = false;
    if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
        if((strlen($aprovado) == 0) or $login_fabrica == 147 ){
            $libera_acesso_acoes = true;
        }
    }
    //HD 205958: Condicional antigo
    elseif ($login_fabrica <> 1){
        $libera_acesso_acoes = true;
    }

    if ($libera_acesso_acoes) {
        $sql = "SELECT pedido
                FROM tbl_pedido
                WHERE pedido_kit_extrato = $extrato
                AND   fabrica            = $login_fabrica";
        $resE = pg_query($con,$sql);
        if (pg_num_rows($resE) == 0 and $login_fabrica == 8) {
            echo "<input type='button' value='Pedido de Peças do Kit' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='pedido' ; document.frm_extrato_os.submit()\" ALT='Pedido de Peças do Kit'>";
        }
        if($login_fabrica == 30){
        if ($recalculo_pendente == 't') {
            $display = "inline";
        }
        else {
            $display = "none";
        }
        echo "<input id='btn_recalcular_extrato' type='button' value='Recalcular Extrato' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='recalculo'; document.frm_extrato_os.submit();\" style='display:$display;'>";
        }
        echo "<br>";
        echo "<br>";
    }

    //HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
    //           não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
    //           de extrato avuldo. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
    //           SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
    $libera_acesso_acoes = false;

    if (in_array($login_fabrica, $fabricas_acerto_extrato)) {

        if (strlen($aprovado) == 0) {
            $libera_acesso_acoes = true;
        }

    } else {//HD 205958: Condicional antigo
        $libera_acesso_acoes = true;
    }

    if ($libera_acesso_acoes) {
        $wwsql = " SELECT pedido_faturado
                    FROM tbl_posto_fabrica
                    JOIN tbl_extrato on tbl_posto_fabrica.posto = tbl_extrato.posto
                    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                    AND   tbl_extrato.extrato       = $extrato";

        $wwres = pg_query($con,$wwsql);
        $pedido_faturado = pg_fetch_result($wwres,0,0);

        if ($login_fabrica == 1 or $login_fabrica == 45) { //HD 66773
            echo "<input type='button' value='Acumular todo o extrato' border='0'  onclick=\"javascript: document.frm_extrato_os.btn_acao.value='acumulartudo'; document.frm_extrato_os.submit();\" alt='Clique aqui p/ acumular todas OSs deste Extrato' style='cursor: pointer;'><br><br>";
        }

    }

        if ($login_fabrica == 1) {
            if($pedido_faturado=="t"){
                echo "<div id='div_estoque' style='display:block; Position:relative;width:450px;'>";
                echo "<table border='0' cellpadding='4' cellspacing='1' bgcolor='#596D9B' align='center' style='font-family: verdana; font-size: 9px' width='350'>";
                echo "<tr>";
                echo "<td align='center'><b><font color='#FFFFFF'>Acerto de peças do estoque</FONT></b></td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='center' bgcolor='#efeeea'><b>Atenção</b><BR>";
                echo "Para ACEITAR todas as peças que o posto utilizou do <BR>estoque informe o motivo e clique em continuar.<BR>";
                echo "<TEXTAREA NAME='autorizacao_texto' ID='autorizacao_texto' ROWS='5' COLS='40' class='textarea'></TEXTAREA>";
                echo "<input type='hidden' name='extrato_estoque' id='extrato_estoque' value='$extrato'>";
                echo "<BR><BR><img src='imagens_admin/btn_confirmar.gif' border='0' style='cursor:pointer;' onClick='gravaAutorizao();'></td>";
                echo "</tr>";
                echo "</table><BR>";
                echo "</div>";
            }
        }

        if($login_fabrica == 15) { # HD 165932
            $sqlnf = " SELECT nota_fiscal_mao_de_obra,
                            to_char(emissao_mao_de_obra,'DD/MM/YYYY') as emissao_mao_de_obra,
                            valor_total_extrato
                     FROM tbl_extrato_extra
                     WHERE extrato = $extrato ";
            $resnf = pg_query($con,$sqlnf);
            if(pg_num_rows($res) > 0){
                $nota_fiscal_mao_de_obra= trim(pg_result($resnf,0,nota_fiscal_mao_de_obra)) ;
                $emissao_mao_de_obra    = trim(pg_result($resnf,0,emissao_mao_de_obra)) ;
                $valor_total_extrato    = trim(pg_result($resnf,0,valor_total_extrato)) ;

                if(!empty($nota_fiscal_mao_de_obra)) {
                    echo "<br>";
                    echo "<table width='750' border='0' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
                    echo "<caption class='menu_top'>Clique para alterar</caption>";
                    echo "<tr class='menu_top2'>";
                    echo "<td nowrap>";
                    echo "Nota Fiscal: <div id='nota_fiscal_mao_de_obra'>$nota_fiscal_mao_de_obra</div>";
                    echo "</td>";
                    echo "<td nowrap>";
                    echo "Data Emissão: <div id='emissao_mao_de_obra'>$emissao_mao_de_obra</div>";
                    echo "</td>";
                    echo "<td nowrap>";
                    echo "Valor NF: <div id='valor_total_extrato'>".number_format($valor_total_extrato,2,",",".")."</div>";
                    echo "</td>";
                    echo "</tr>";
                    echo "</table>";
                    echo "<br>";
                }
            }

        }

        if($login_fabrica == 30){
            $sqlServ = "select count(tbl_os.os) as qtde,
                                tbl_os_extra.extrato,
                                tbl_esmaltec_item_servico.esmaltec_item_servico,
                                tbl_esmaltec_item_servico.codigo,
                                tbl_esmaltec_item_servico.descricao,
                                SUM(tbl_os.mao_de_obra) AS valor
                              FROM tbl_os
                              JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                              JOIN tbl_defeito_constatado on tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
                              JOIN tbl_extrato_extra USING(extrato)
                              JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
                              LEFT join tbl_esmaltec_item_servico ON tbl_esmaltec_item_servico.esmaltec_item_servico = tbl_defeito_constatado.esmaltec_item_servico
                            WHERE tbl_os.fabrica=$login_fabrica
                AND tbl_extrato.extrato = $extrato
                AND (tbl_os.mao_de_obra > 0 or tbl_os.qtde_km_calculada > 0)
                and (tbl_os_extra.mao_de_obra_desconto = 0 or tbl_os_extra.mao_de_obra_desconto isnull or tbl_os.qtde_km_calculada > 0)
                            GROUP by tbl_os_extra.extrato,
                            tbl_esmaltec_item_servico.esmaltec_item_servico,
                            tbl_esmaltec_item_servico.codigo,
                            tbl_esmaltec_item_servico.descricao
                             ";
            $resServ = pg_query($con,$sqlServ);

            $registros = pg_numrows($resServ);
            $valor_total = 0;
            if($registros > 0){ ?>
                <table align='center' width='700' cellspacing='1' class='tabela'>
                    <caption class='titulo_tabela'> Itens de Serviço </caption>
                    <tr class='titulo_coluna'>
                        <th>Código</th>
                        <th>Descrição</th>
                        <th>Qtde</th>
                        <th>Preço</th>
                        <th>Valor</th>
                    </tr>
        <?php
                for($i = 0; $i < $registros; $i++){
                    $item_servico = pg_result($resServ,$i,codigo);
                    $descricao = pg_result($resServ,$i,descricao);
                    $qtde = pg_result($resServ,$i,qtde);
                    $valor = pg_result($resServ,$i,valor);
                    $esmaltec_item_servico = pg_result($resServ,$i,esmaltec_item_servico);

                   
                    if ($valor == 0) { 
                        
                        continue;
                    }

                    if(strlen($esmaltec_item_servico) > 0){
                        $sql = "
                        SELECT
                        COALESCE (
                            SUM(
                                    COALESCE(tbl_extrato_lancamento.valor, 0)
                            ),
                            0
                        ) AS total_avulso_item

                        FROM
                        tbl_lancamento
                        JOIN tbl_extrato_lancamento ON tbl_lancamento.lancamento=tbl_extrato_lancamento.lancamento

                        WHERE
                        tbl_extrato_lancamento.extrato=$extrato
                        AND tbl_lancamento.esmaltec_item_servico=$esmaltec_item_servico
                        AND tbl_lancamento.fabrica=$login_fabrica
                        AND tbl_extrato_lancamento.fabrica=$login_fabrica
                        ";

                        $resAvulsoItens = pg_query($con, $sql);

                        $total_avulso_item = pg_result($resAvulsoItens, 0, 0);
                        $valor += $total_avulso_item;
                        $preco = $valor / $qtde;

                        $valor_total += $valor;
                        $cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
                ?>

                        <tr bgcolor='<? echo $cor; ?>'>
                            <td><? echo $item_servico; ?></td>
                            <td><? echo $descricao; ?></td>
                            <td align='right'><? echo $qtde; ?></td>
                            <td align='right'><? echo number_format($preco,2,',','.'); ?></td>
                            <td align='right'><? echo number_format($valor,2,',','.'); ?></td>
                        </tr>
                <?php
                    }
                }

                $sqlS = "SELECT    tbl_esmaltec_item_servico.codigo,
                                   tbl_esmaltec_item_servico.descricao,
                                   tbl_esmaltec_item_servico.valor
                                 FROM tbl_esmaltec_item_servico
                                WHERE esmaltec_item_servico = 35";

                $resS         = pg_query($con, $sqlS);
                $preco        = pg_fetch_result($resS, 0, 'valor');
                $item_servico = pg_fetch_result($resS, 0, 'codigo');
                $descricao    = pg_fetch_result($resS, 0, 1);

                $sqlPeca = "SELECT tbl_extrato.pecas
                                  FROM tbl_os
                                  JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                                  JOIN tbl_defeito_constatado on tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
                                  JOIN tbl_extrato_extra USING(extrato)
                                  JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
                                WHERE tbl_os.fabrica=$login_fabrica
                                AND tbl_extrato.extrato = $extrato
                                AND tbl_os.pecas > 0
                                GROUP by tbl_os_extra.extrato,tbl_extrato.pecas ";

                $resPeca   = pg_query($con, $sqlPeca);
                $registros = pg_num_rows($resPeca);

                if ($registros > 0) {
                    $qtde = pg_fetch_result($resPeca, 0, 'pecas');
                }else{
                    $qtde = 0;
                }

                $sql = " SELECT SUM(COALESCE(tbl_extrato_lancamento.valor, 0))
                            FROM tbl_lancamento
                            JOIN tbl_extrato_lancamento ON tbl_lancamento.lancamento=tbl_extrato_lancamento.lancamento

                            WHERE tbl_extrato_lancamento.extrato         = $extrato
                                AND tbl_lancamento.esmaltec_item_servico = 35
                                AND tbl_lancamento.fabrica               = $login_fabrica
                                AND tbl_extrato_lancamento.fabrica       = $login_fabrica ";

                $resAvulsoItens = pg_query($con, $sql);
                $total_avulso_item = pg_fetch_result($resAvulsoItens, 0, 0);
                if($total_avulso_item <> 0 OR $registros <> 0){
                    $total_avulso_item = pg_fetch_result($resAvulsoItens, 0, 0);
                    $qtde += $total_avulso_item;
                    $valor = $qtde;

                    $valor_total += $valor;
                    $cor = ($cor == "#F7F5F0") ? "#F1F4FA" : "#F7F5F0";?>

                    <tr style='background-color: <?echo $cor; ?>' class='table_line'>
                        <td><? echo $item_servico; ?></td>
                        <td><? echo $descricao; ?></td>
                        <td align='right'><? echo number_format($qtde,2,',','.'); ?></td>
                        <td align='right'><? echo number_format($preco,2,',','.'); ?></td>
                        <td align='right'><? echo number_format($valor,2,',','.'); ?></td>
                    </tr><?php
                }
                $sqlS = "SELECT    tbl_esmaltec_item_servico.codigo,
                                   tbl_esmaltec_item_servico.descricao,
                                   tbl_esmaltec_item_servico.valor
                                 FROM tbl_esmaltec_item_servico
                                WHERE esmaltec_item_servico = 36";
                $resS = pg_query($con,$sqlS);

                $preco = pg_result($resS,0,valor);
                $item_servico = pg_result($resS,0,codigo);
                $descricao = pg_result($resS,0,1);

                $sqlPeca = "SELECT tbl_extrato.deslocamento
                                  FROM tbl_os
                                  JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                                  JOIN tbl_defeito_constatado on tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
                                  JOIN tbl_extrato_extra USING(extrato)
                                  JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
                                WHERE tbl_os.fabrica=$login_fabrica
                                AND tbl_extrato.extrato = $extrato
                                AND tbl_os.qtde_km_calculada > 0
                                GROUP by tbl_os_extra.extrato,tbl_extrato.deslocamento ";
                $resPeca = pg_query($con,$sqlPeca);

                $registros = pg_numrows($resPeca);

                if($registros > 0){
                    for($i = 0; $i < $registros; $i++){

                        $deslocamento = pg_result($resPeca,$i,deslocamento);

                        $sql = "SELECT SUM (tbl_os.qtde_km_calculada) AS qtde_km_calculada,
                                       tbl_os_extra.valor_por_km,
                                       SUM (tbl_os_extra.qtde_km) AS qtde_km, null as extrato_lancamento
                                FROM   tbl_os
                                JOIN   tbl_os_extra ON tbl_os.os = tbl_os_extra.os
                                JOIN   tbl_extrato_extra USING(extrato)
                                JOIN   tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
                                WHERE  tbl_os.fabrica = $login_fabrica
                                AND    tbl_extrato.extrato = $extrato
                                AND    tbl_os_extra.qtde_km > 0
                                AND    tbl_os.qtde_km_calculada > 0
                                GROUP BY tbl_os_extra.valor_por_km

                                UNION

                                SELECT tbl_extrato_lancamento.valor AS qtde_km_calculada,
                                    1 AS valor_por_km,
                                    tbl_extrato_lancamento.valor AS qtde_km,extrato_lancamento
                                FROM tbl_extrato_lancamento
                                JOIN tbl_lancamento ON tbl_lancamento.lancamento = tbl_extrato_lancamento.lancamento AND tbl_lancamento.esmaltec_item_servico = 36
                                JOIN tbl_esmaltec_item_servico ON tbl_esmaltec_item_servico.esmaltec_item_servico = tbl_lancamento.esmaltec_item_servico
                                WHERE extrato = $extrato

                                ORDER BY qtde_km_calculada DESC
                                ";

                        $resAvulsoItens    = pg_query($con, $sql);
                        for($k= 0; $k< pg_num_rows($resAvulsoItens); $k++) {

                        $extrato_lancamento = pg_fetch_result($resAvulsoItens, $k, 'extrato_lancamento');
                        $total_avulso_item = pg_result($resAvulsoItens, 0, 0);
                        $qtde_km_calculada= pg_result($resAvulsoItens,$k,qtde_km_calculada);
                        $preco = pg_result($resAvulsoItens,$k,valor_por_km);
                        $qtde = pg_result($resAvulsoItens,$k,qtde_km);
                        $qtde = $deslocamento / $preco;

                        if (!empty($extrato_lancamento)) {
                            $qtde = $qtde_km_calculada;
                        }

                        $cor = ($cor == "#F7F5F0") ? "#F1F4FA" : "#F7F5F0";
            ?>
                        <tr bgcolor='<?php echo $cor; ?>'>
                            <td><?php echo $item_servico; ?></td>
                            <td><?php echo $descricao; ?></td>
                            <td align='right'><? echo number_format($qtde,2,',','.'); ?></td>
                            <td align='right'><? echo number_format($preco,2,',','.'); ?></td>
                            <td align='right'><? echo number_format($qtde_km_calculada,2,',','.'); ?></td>
                        </tr>
            <?php
                        }
                    }
                }else{
                    $sqle = "SELECT REGEXP_REPLACE(
                                        REGEXP_REPLACE(parametros_adicionais, '^.+\"valor_km_fixo\":\"', ''),
                                           E'\"(.*)', '')
                                 AS valor_km_fixo,
                                    deslocamento
                            FROM    tbl_posto_fabrica
                            JOIN    tbl_extrato USING(posto,fabrica)
                            WHERE   tbl_extrato.fabrica = $login_fabrica
                            AND     extrato = $extrato
                            AND     deslocamento > 0
                            AND     parametros_adicionais ~* 'valor_km_fixo'";
                    $rese = pg_query($con,$sqle);
                    if(pg_num_rows($rese) > 0 ) {
                        $valor_km_fixo = pg_fetch_result($res,0,0);
                        $deslocamento = pg_result($rese,0,deslocamento);
            ?>
                         <tr bgcolor='<?php echo $cor; ?>'>
                            <td><?php echo $item_servico; ?></td>
                            <td><?php echo $descricao; ?></td>
                            <td align='right'><? echo 0; ?></td>
                            <td align='right'><? echo number_format($preco,2,',','.'); ?></td>
                            <td align='right'><? echo number_format($deslocamento,2,',','.'); ?></td>
                        </tr>
                <?
                    }else{

                        $sql = "SELECT
                                COALESCE (
                                    SUM(
                                     COALESCE(tbl_extrato_lancamento.valor, 0)
                                    ),
                                    0
                                ) AS total_avulso_item
                                FROM
                                tbl_lancamento
                                JOIN tbl_extrato_lancamento ON tbl_lancamento.lancamento=tbl_extrato_lancamento.lancamento
                                WHERE
                                tbl_extrato_lancamento.extrato=$extrato
                                AND tbl_lancamento.esmaltec_item_servico=36
                                AND tbl_lancamento.fabrica=$login_fabrica
                                AND tbl_extrato_lancamento.fabrica=$login_fabrica";

                        $resAvulsoItens    = pg_query($con, $sql);
                        $total_avulso_item = pg_result($resAvulsoItens, 0, 0);
                        if(pg_num_rows($resAvulsoItens) > 0 and $total_avulso_item > 0) {
                            $deslocamento = $total_avulso_item;
?>
                         <tr bgcolor='<?php echo $cor; ?>'>
                            <td><?php echo $item_servico; ?></td>
                            <td><?php echo $descricao; ?></td>
                            <td align='right'><? echo number_format($total_avulso_item,2,',','.'); ?></td>
                            <td align='right'><? echo '1,00'; ?></td>
                            <td align='right'><? echo number_format($total_avulso_item,2,',','.'); ?></td>
                        </tr>
<?
                        }
                    }
                }

                $sql_desl_avulso = "
                                SELECT sum(tbl_extrato_lancamento.valor) as valor
                                FROM tbl_extrato_lancamento
                                JOIN tbl_lancamento ON tbl_lancamento.lancamento = tbl_extrato_lancamento.lancamento AND tbl_lancamento.esmaltec_item_servico = 36
                                JOIN tbl_esmaltec_item_servico ON tbl_esmaltec_item_servico.esmaltec_item_servico = tbl_lancamento.esmaltec_item_servico
                                WHERE extrato = $extrato";
                $res_desl_avulso = pg_query($con, $sql_desl_avulso);

                if (pg_num_rows($res_desl_avulso) > 0) {
                    $deslocamento += pg_fetch_result($res_desl_avulso, 0, 'valor');
                }

                $valor_total += $deslocamento;
            ?>
                    <tr class='titulo_coluna'>
                        <td colspan='4'> Total Geral</td>
                        <td align='right'><?php echo $total ?> </td>
                    </tr>
                </table> <br>
            <?php
            }

        }
        ?>
    </div>
        <?php 

            $tem_anexo = "nao";

            $sql_tdocs = "SELECT tdocs FROM tbl_tdocs WHERE fabrica = $login_fabrica AND referencia_id = $extrato AND situacao = 'ativo'";
            $res_tdocs = pg_query($con, $sql_tdocs);
            if (pg_num_rows($res_tdocs) > 0) {
                $tem_anexo = "sim";
            }

            if ((in_array($login_fabrica, array(154,171,178,180,181,182,184,191,200))) || ($login_fabrica == 152 && $tem_anexo == 'nao')) {
            $nota_fiscal_servico = $s3_extrato->getObjectList($extrato."-nota_fiscal_servico");

            if(count($nota_fiscal_servico) > 0){
                $nota_fiscal_servico = basename($nota_fiscal_servico[0]);
                ?>
            <table>
                <thead>
                    <tr>
                        <th>Nota Fiscal de Serviço</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td align="center" >
                            <table>
                                <tr>
                        <?php

                            $anexos = $s3_extrato->getObjectList("{$extrato}-", false);

                            $cont = 1;
                            if(count($anexos)>0){
                                foreach($anexos as $anexo){

                                    $dados = $s3_extrato->getFileInfo($anexo);

                                    $ext = preg_replace("/.+\./", "", $anexo);
                                    //$nome_arquivo = "$extrato-nota_fiscal_servico-"."$cont". ".$ext";
                                    $nome_arquivo = basename($anexo);

                                    if(!in_array($ext, array("pdf", "doc", "docx"))){
                                        $thumb_nota_fiscal_servico = $s3_extrato->getLink("thumb_".$nome_arquivo);
                                        if(strlen(trim($thumb_nota_fiscal_servico))==0){
                                            $nome_arquivo = "$extrato-nota_fiscal_servico". ".$ext";;
                                            $thumb_nota_fiscal_servico = $s3_extrato->getLink("thumb_".$nome_arquivo);
                                        }
                                    }else{
                                        switch ($ext) {
                                            case 'pdf':
                                                $thumb_nota_fiscal_servico = 'imagens/pdf_icone.png';
                                                break;
                                            case 'doc':
                                            case 'docx':
                                                $thumb_nota_fiscal_servico = 'imagens/docx_icone.png';
                                                break;
                                        }
                                    }
                                    $nota_fiscal_servico = $s3_extrato->getLink($nome_arquivo);

                                    ?>
                                        <td class='anexos'>
                                            <?=substr($dados['LastModified'],0,16)?><br>
                                            <a href="<?=$nota_fiscal_servico?>" target='_blank'><img src="<?=$thumb_nota_fiscal_servico?>" style="border:1px solid; margin:5px;" /></a>
                                        </td>
                                <?php
                                $cont++;
                                }
                            } 
                             ?>
                                </tr>
                             </table>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
            } else {

                if($login_fabrica == 178) {
            ?>

            <br /><br /><a rel='shadowbox; width= 550; height= 250;' class="btn-anexar-nf" href="upload_nf_servico_extrato.php?extrato=<?=$extrato;?>">Anexar Nota Fiscal de Serviço</a><br /><br />
                
        <?php   
                }
            }
        }

        $tamanho_tabela = ($login_fabrica == 50) ? 1400 : 750;
        if ($login_fabrica == 178){
            echo "<div class='alert_osr alert_osr_info' >Para consultar OS PRINCIPAL clicar no número da OS</div>";
        }
        if( in_array($login_fabrica, array(11,172)) )        {
            echo "<TABLE width='$tamanho_tabela' id='resultado_extrato_consulta' border='0' align='center' border='0' cellspacing='1' cellpadding='1' class='table table-striped table-bordered table-hover table-large'>\n";
            echo "<thead>";
        }else{
            $var_tablesorter = ($login_fabrica == 183) ? "tablesorter" : "";
            $sizeTable = (in_array($login_fabrica, [193])) ? 'style="width: 700px !important;"' : '';
            echo "<TABLE width='$tamanho_tabela' border='0' align='center' border='1' cellspacing='1' cellpadding='1' class='tabela $var_tablesorter' id='grid_list' $sizeTable>\n";
        }

        if (strlen($msg) > 0) {
            echo "<TR class='menu_top'>\n";
            echo "<TD colspan=10>$msg</TD>\n";
            echo "</TR>\n";
        }

        echo "<thead> <TR class='titulo_coluna'>\n";
        //HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
        //           não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
        //           de extrato avulso. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
        //           SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
        if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
            if (strlen($aprovado) == 0) {
                echo "<TD align='center' width='30'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></TD>\n";
            }
        }
        //HD 205958: Rotina antiga
        elseif (($ja_baixado == false AND $login_fabrica <> 6) OR ($ja_baixado==false AND $login_fabrica==6 ANd strlen($liberado)==0)) echo "<th align='center' width='30'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></th>\n";

        echo ($login_fabrica == 183) ? "<th width='075' >OS</th>\n" : "<TD width='075' >OS</TD>\n"; 
        echo (in_array($login_fabrica, array(190))) ? "<TD width='075'>Tipo Atendimento</TD>\n" : "";
        echo (in_array($login_fabrica, array(190))) ? "<TD width='075'>Contrato</TD>\n" : "";
        if (in_array($login_fabrica, array(3,11,126,172))) {
            echo "<td align='center' width='60' >Anexos</td>\n";
        }
        echo ($login_fabrica == 1)
            ? "<TD width='075'>Cód. Fabr.</TD>
                <td>Data Abertura</td>
                <td>Data Conserto</td>
                <td>Dias</td>\n"
            : "";
        echo ($login_fabrica == 158) ? "<TD width='075'>Tipo OS</TD>\n" : "";
        echo ($login_fabrica == 30) ? "<td width='120'>Auditoria</td>\n" : "";

        if ($login_fabrica == 183){
            echo "<th width='075'>Técnico</th>\n";
            echo "<th width='075'>Série</th>\n";
        }else{
            echo "<TD width='075'>".traduz('Série')."</TD>\n";
        }

        echo ($login_fabrica <> 1 AND $login_fabrica <> 183) ? "<TD width='075'>Abertura</TD>\n" : "";

        echo ($login_fabrica == 176) ? "<TD width='075'>Tipo de Atendimento</TD>\n" : "";
        
        if ($login_fabrica == 183){
            echo "
                <th width='075'>Abertura</th>\n
                <th width='120'>Data Fechamento</th>\n
                <th width='120'>Data Conserto</th>\n";
        }

        echo ($login_fabrica == 52) ? "<td width='075'>Data Conserto</td>" : ""; //hd_chamado=2598225
        echo (in_array($login_fabrica, array(11,45,51,158,167,172,203))) ? "<TD width='075'>Fechamento</TD>\n" : "";
        echo ($login_fabrica == 2) ? "<TD width='075'>Finalizada</TD>\n" : "";
        echo ($login_fabrica == 6) ? "<TD width='50'><ACRONYM TITLE=\"Qtde de dias que a OS ficou aberta\">Dias</ACRONYM></TD>\n" : "";

        if (in_array($login_fabrica, array(6,24,51,151,165))) {
            echo "<TD width='130'>Consumidor/Revenda</TD>\n";
        }else if ($login_fabrica <> 183){
            echo "<TD width='130'>".traduz('Consumidor')."</TD>\n";
        }

        if ($login_fabrica == 183){
            echo "
                <th width='100'>Consumidor</th>\n
                <th width='160'>Consumidor Cidade</th>\n
                <th width='160'>Consumidor Estado</th>\n";
        }

        echo (in_array($login_fabrica, array(30,158))) ? "<TD width='075'>Cidade</TD>\n" : "";
        if ( in_array($login_fabrica, array(11,172)) ){
            echo "<TD>REVENDA</TD>\n";
            echo "<TD><ACRONYM TITLE=\"Nota Fiscal de Entrada\">NF Entrada</ACRONYM></TD>\n";
            echo "<TD><ACRONYM TITLE=\"Nota Fiscal de Saída\">NF Saída</ACRONYM></TD>\n";
            echo "<TD><ACRONYM TITLE=\"Referência do Produto\">Ref. Prod.</ACRONYM></TD>\n";
            echo "<TD><ACRONYM TITLE=\"Mão de Obra\">M.O.</ACRONYM></TD>\n";
            echo "<TD>ADMIN</TD>\n";
# HD 196633 pediu para tirar a coluna <NÃO PAGA M.O.>

#           echo "<TD><ACRONYM TITLE='Clique no link para não pagar mão de obra do admin na Ordem de serviço'>NÃO PAGA M.O.</ACRONYM></TD>\n";
        }else if ($login_fabrica == 183){
            echo "<th width='130'>Produto</th>\n";
            echo "<th width='80'>M.O.</th>\n";
        } else {
            if($login_fabrica != 138){
                echo "<TD width='130'>".traduz('Produto')."</TD>\n";
            }

            if (!in_array($login_fabrica, array(35,169,170)) && (in_array($login_fabrica,array(51,81,88,95,99,101,106,108,111,122,123,124,126,127,128,131,134,136,137,140,141,144,72)) || $novaTelaOs)) {
                echo "<TD width='80'>M.O.</TD>\n";
            }
        }

        if($multimarca =='t') echo "<td nowrap>Marca</td>";
        if ($login_fabrica == 52) {
            echo "<TD nowrap>Nota Fiscal</TD>\n";
            echo "<TD nowrap>Data NF</TD>\n";
        }

        if (!in_array($login_fabrica, array(169,170)) && ($inf_valores_adicionais || in_array($login_fabrica, array(142,145)) || isset($fabrica_usa_valor_adicional))) {
            echo ($login_fabrica == 183) ? "<th nowrap style='min-width: 80px;'>Valor<br>Adicional</th>\n" : "<TD nowrap style='min-width: 80px;'>Valor<br>Adicional</TD>\n";
        }

        # HD 45710 17/10/2008 - Permitir selecionar quais OSs imprimir
        echo ($login_fabrica == 51) ? "<TD width='130' nowrap>Mão-de-Obra</TD>\n" : "";
        echo (in_array($login_fabrica, array(2,51))) ? "<TD width='130'>AÇÃO</TD>\n" : "";

        if ($login_fabrica == 1 ) {
            echo "<TD width='100'>Revenda</TD>\n";
            echo "<TD width='130'>Total Peça</TD>\n";
            echo "<TD width='130'>Total MO</TD>\n";
            echo "<TD width='130'>Taxa Adm.</TD>\n";
            echo "<TD width='200' nowrap>Peça + <br />(MO + TA)</TD>\n";
        }
        if (in_array($login_fabrica,array(30,90,91))) {
            echo "<TD width='100'>Revenda</TD>\n";
            echo "<TD width='130'>Total KM</TD>\n";
            if($login_fabrica != 91) { # HD 2978522
                echo "<TD width='130'>Total Peça</TD>\n";
            }
            echo "<TD width='130'>Total MO</TD>\n";
            if($login_fabrica == 90) { # HD 310739
                echo "<TD width='130'>Taxa Visita</TD>\n";
            }
            if($login_fabrica == 91) { # HD 2978522
                echo "<TD width='130'>KM + MO</TD>\n";
            } else {
                echo "<TD width='130'>KM + Peça + MO</TD>\n";
            }
            if ($login_fabrica == 30 and 1==2) {
                echo "<TD width='130'>Avaliação</TD>\n";
                echo "<TD width='130'>Taxa de Entrega</TD>\n";
            }
        }

        if (in_array($login_fabrica,array(85))) {
            echo "<TD width='100'>Revenda</TD>\n";
            echo "<TD width='130'>KM</TD>\n";
            echo "<TD width='130'>Peça</TD>\n";
            echo "<TD width='130'>MO</TD>\n";
            echo "<TD width='130'>Pedágio</TD>\n";
            echo "<TD width='130'>Bonificação</TD>\n";
            echo "<TD width='130'>TOTAL</TD>\n";
        }

        if (in_array($login_fabrica, array(42,104))) {
            echo "<TD width='130'>Total Peça</TD>\n";
            echo "<TD width='130'>Total MO</TD>\n";
            if ($login_fabrica == 42 && $prestacao_servicos == 't') {
                echo "<TD width='130'> Taxa Administrativa</TD>\n";
                echo "<TD width='130'>Peça + Tx. Adm.</TD>\n";
            } else {
                echo "<TD width='130'>Peça + MO</TD>\n";
            }
        }

        if($login_fabrica == 121){
            echo "<TD width='130'>Total MO</TD>\n";
            echo "<TD width='130'>Total</TD>\n";
        }

        if (in_array($login_fabrica, array(169,170))) {
            echo "<TD width='130'>Valor OS SAP</TD>\n";
        }

        if (!in_array($login_fabrica, array(169,170)) && (in_array($login_fabrica, array(74,115,116,117,120,201,129,131,140,141,144,138,139,143,145)) || $novaTelaOs)) {
            if (!in_array($login_fabrica, array(139))) {
                if (!in_array($login_fabrica, array(115,116,117,120,201,129,131,140,141,144,138,143,145)) && empty($novaTelaOs)) {
                    echo "<TD width='130'>Qtde KM</TD>\n";
                }
                if (in_array($login_fabrica, array(74))) {
                    echo "<TD width='130'>Valor KM</TD>\n";
                } else {
                    if (isset($novaTelaOs)) {
                        $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
                    }

                    if (!isset($novaTelaOs) or $login_fabrica == 178) {
                        echo "<TD width='130'>Total KM</TD>\n";
                    } else if (isset($novaTelaOs) && !$nao_calcula_km && $login_fabrica != 35) {
                        echo ($login_fabrica == 183) ? "<th width='130'>Total KM</th>\n" : "<TD width='130'>Total KM</TD>\n";
                    }
                }
            }

            if($login_fabrica == 140){
                echo "<TD nowrap>Entrega<br>Técnica</TD>\n";
            }

            if (isset($novaTelaOs)) {
                $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);
            }

            if (isset($novaTelaOs) && !$nao_calcula_peca && $extrato_sem_peca != "t" && !in_array($login_fabrica, array(35,139,160,183,191,193)) and !$replica_einhell) {
                echo "<TD width='130'>Total Peça</TD>\n";
            } else if (!in_array($login_fabrica, array(115,116,117,120,201,129,131,140,141,144,139)) && $extrato_sem_peca != "t" && !isset($novaTelaOs)) {
                echo "<TD width='130'>Total Peça</TD>\n";
            }

            if (!in_array($login_fabrica, array(131,140,143,145)) && empty($novaTelaOs)) {

                echo "<TD width='130'>Total MO</TD>\n";
            }

            if (!in_array($login_fabrica, array(115,116,117,120,201,129,131,140,141,144,138,139,143,145)) && empty($novaTelaOs)) {

                echo "<TD width='130'>Total KM + MO + PEÇAS</TD>\n";

                if($login_fabrica == 74){
                    echo "<td>Situação</td>";
                    echo "<td>Observação</td>";
                }

            } elseif (in_array($login_fabrica, array(131,139))) {
                echo "<TD width='130'>Total</TD>\n";
            } else {
                if ($login_fabrica != 35) {
                    if(in_array($login_fabrica, array(140,141,143,144)) || isset($novaTelaOs)){
                        echo ($login_fabrica == 183) ? "<th nowrap style='min-width: 50px;'>Total</th>\n" : "<TD nowrap style='min-width: 50px;'>Total</TD>\n";
                        if ($login_fabrica == 178){
                            echo "<TD nowrap style='min-width: 50px;'>Ações</TD>\n";
                        }
                    }else{
                        echo "<TD width='130'>Total KM + MO</TD>\n";
                    }
                }
            }
        }

        if(in_array($login_fabrica, array(141,144,145))){
            echo "<td width='300'>Tipo Atendimento</td>";
            echo (in_array($login_fabrica, array(141,144))) ? "<td>Prazo Atendimento(Dias)</td>" : "";
        }

        if($login_fabrica == 128){
            echo "<TD nowrap>Visita<br>Técnica</TD>\n";
            echo "<TD nowrap>Valor KM</TD>\n";
            echo "<TD nowrap>Total</TD>\n";
        }

        # HD 936143
        if ($login_fabrica == 80) {

            echo '<td>Revenda</td>';
        }

        if ($login_fabrica == 50) {
            echo "<TD width='100'>Revenda</TD>\n";
            echo "<TD width='130'>Total KM</TD>\n";
            echo "<TD width='130'>Total MO</TD>\n";
            echo "<TD width='130'>Total KM + MO</TD>\n";
            # HD 36258 - Permitir selecionar quais OSs imprimir
            echo "<TD width='130'><a href='javascript:selecionarTudo();' style='color:#FFFFFF'><img src='imagens/img_impressora.gif'> </a></TD>\n";
        }

        if (in_array($login_fabrica,array(30,50,90,91))) {
            echo "<TD width='130'>Intervenção KM</TD>\n";
        }

        if ($login_fabrica == 50) {
            echo "<TD>Verifica Série</TD>\n";
            $sqlAdm = "SELECT admin FROM tbl_funcionalidade_admin WHERE admin = $login_admin AND funcionalidade = 2";
            $resAdm = pg_query($con,$sqlAdm);
            $libera_serie = (pg_numrows($resAdm) > 0) ? 'sim' : '';

            if($libera_serie == 'sim'){
                echo "<TD>Aprovar Série</TD>\n";
            }
        }
        echo ($login_fabrica == 52) ? "<TD width='130'>Pedágio</TD>\n":"";

        if (in_array($login_fabrica,array(15,24,35,52,87,94,114,125))) {
            if (!in_array($login_fabrica, array(114,125))) {
                echo "<TD width='130'>Qtde KM</TD>\n";
                echo "<TD width='130'>Valor por KM</TD>\n";
                if ($login_fabrica == 35) {
                    echo "<TD width='130'>Qtde Visitas</TD>\n";
                }
            }
            echo "<TD width='130'>Total KM</TD>\n";
            echo ($login_fabrica == 52) ? "<TD width='130'>Total Peças</TD>\n":"";
            if ($login_fabrica == 87) {

                echo '<td>Tipo OS</td>
                      <td>Qtde Horas</td>
                      <td>Valor/Hora</td>';

            }
            echo "<TD width='130'>Total MO</TD>\n";
            if (in_array($login_fabrica, array(125))) {
                echo "<TD width='130'>Taxa Visita</TD>\n";
                echo "<TD width='250'>";
            }else{
                echo "<TD width='130'>";
            }
            if ($login_fabrica == 52){
                echo "Total KM + MO + PEÇAS";
            } elseif (in_array($login_fabrica,array(35))){
                echo "Total KM + MO + VA";
            } elseif ( in_array($login_fabrica,array(125)) ) {
                echo "Total KM + MO + TAXA V + VA";
            } else {
                echo "Total KM + MO";
            }
            echo "</TD>\n";
            if($login_fabrica == 15 && $ja_baixado == false){
                echo "<TD width='130'>Ações</TD>";
            }
        }

        echo ($login_fabrica == 134) ? "<TD width='130'>Total Peças</TD>\n":"";
        echo ($login_fabrica == 134) ? "<TD width='130'>Total MO + Peças</TD>\n":"";
        echo ($login_fabrica == 15 ) ? "<TD width='30'><a href='javascript:selecionarTudo();' style='color:#FFFFFF'><img src='imagens/img_impressora.gif'> </a></TD>\n" : "";

        echo (in_array($login_fabrica, array(6,43,105))) ? "<TD width='130'>Total MO</TD>\n" : "";

        if ($login_fabrica == 6) {
            echo "<td>NF</td>";
        }

        echo ($login_fabrica ==6 AND strlen($liberado)==0) ? "<TD width='130'>Ação</TD>\n" : "";
        echo "</TR>\n";
        if( in_array($login_fabrica, array(11,172,183)) ){
            echo "</thead><tboby>";
        }

        if($login_fabrica == 1 ){
            // monta array para ver duplicidade
            $busca_array     = array();
            $localizou_array = array();
            for ($x = 0; $x < pg_num_rows($res); $x++) {
                $nota_fiscal   = trim(pg_fetch_result($res,$x,nota_fiscal));
                if (in_array($nota_fiscal, $busca_array)) {
                    $localizou_array[] = $nota_fiscal;
                }
                $busca_array[] = $nota_fiscal;
            }
        }

        $totalizador    = array();
        $ultima_familia = $ultima_familia_exibida = null;
        $qtde_de_inputs = pg_num_rows($res);

        if($login_fabrica == 126 OR $login_fabrica == 3){
            //verifica anexos os_item
            if( in_array($login_fabrica, array(3,11,172)) ){
                $prefix_os_item = "anexo_os_item_{$login_fabrica}_";
            }else{
                $prefix_os_item = "$os";
            }

            $s3->getObjectList($prefix_os_item, "false","","");
            $anexos_os_item = $s3->files;

            //verifica anexos os_cadastro
            $prefix_os_cadastro = "anexo_os_{$login_fabrica}_";
            $s3->getObjectList($prefix_os_cadastro, "false","","");
            $anexos_os = $s3->files;

            //verifica se tem anexo de OS Revenda
            $sqlSuaOS = "   SELECT sua_os
                            FROM tbl_os
                            WHERE tbl_os.os = {$os} AND
                                  fabrica = {$login_fabrica}";
            $resSuaOs = pg_query($con,$sqlSuaOS);
            $suaOs = pg_fetch_result($resSuaOs, 0, "sua_os");
            list($suaOs,$digito) = explode("-", $suaOs);

            $sqlOsRevenda = "   SELECT os_revenda
                                FROM tbl_os_revenda
                                WHERE sua_os = '{$suaOs}' AND
                                      fabrica = {$login_fabrica}";

            $resOsRevenda = pg_query($con,$sqlOsRevenda);
            if(pg_num_rows($resOsRevenda)> 0 ){
                $osRevenda = pg_fetch_result($resOsRevenda, 0, "os_revenda");
                $s3->getObjectList("anexo_os_revenda_{$login_fabrica}_{$osRevenda}_img_os_revenda_");
                $anexo_os_revenda = basename($s3->files[0]);
            }
        }

        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
            $os                 = trim(pg_fetch_result ($res,$i,'os'));
            $sua_os             = trim(pg_fetch_result ($res,$i,'sua_os'));
            $data               = trim(pg_fetch_result ($res,$i,'data'));
            $abertura           = trim(pg_fetch_result ($res,$i,'abertura'));
            $fechamento         = trim(pg_fetch_result ($res,$i,'fechamento'));
            $finalizada         = trim(pg_fetch_result ($res,$i,'finalizada'));
            $conserto           = trim(pg_fetch_result ($res,$i,'conserto'));
            $dias               = trim(pg_fetch_result ($res,$i,'dias'));
            $serie              = trim(pg_fetch_result ($res,$i,'serie'));
            $reembolso_peca_estoque = pg_fetch_result($res, $i, 'reembolso_peca_estoque');
            $codigo_fabricacao  = trim(pg_fetch_result ($res,$i,'codigo_fabricacao'));
            $consumidor_nome    = trim(pg_fetch_result ($res,$i,'consumidor_nome'));
            $consumidor_cidade  = trim(pg_fetch_result ($res,$i,'consumidor_cidade'));
            $consumidor_estado  = trim(pg_fetch_result ($res,$i,'consumidor_estado'));
            $consumidor_fone    = trim(pg_fetch_result ($res,$i,'consumidor_fone'));
            $revenda_nome       = trim(pg_fetch_result ($res,$i,'revenda_nome'));
            $produto            = trim(pg_fetch_result ($res,$i,'produto'));
            $produto_nome       = trim(pg_fetch_result ($res,$i,'descricao'));
            $produto_referencia = trim(pg_fetch_result ($res,$i,'referencia'));
            $marca              = trim(pg_fetch_result ($res,$i,'marca'));
            $data_fechamento    = trim(pg_fetch_result ($res,$i,'data_fechamento'));
            $os_reincidente     = trim(pg_fetch_result ($res,$i,'os_reincidente'));
            $codigo_posto       = trim(pg_fetch_result ($res,$i,'codigo_posto'));
            $total_pecas        = trim(pg_fetch_result ($res,$i,'total_pecas'));
            $total_mo           = trim(pg_fetch_result ($res,$i,'total_mo'));
            $qtde_km            = trim(pg_fetch_result ($res,$i,'qtde_km'));
            $valor_km           = trim(pg_fetch_result ($res,$i,'valor_km'));
            $qtde_visitas       = trim(pg_fetch_result ($res,$i,'qtde_visitas'));
            $total_km           = trim(pg_fetch_result ($res,$i,'qtde_km_calculada'));
            $pedagio            = trim(pg_fetch_result ($res,$i,'pedagio'));
            $taxa_visita        = trim(pg_fetch_result ($res,$i,'taxa_visita'));
            $valor_total_hora_tecnica = trim(pg_fetch_result ($res,$i,'valor_total_hora_tecnica'));
            $cortesia           = trim(pg_fetch_result ($res,$i,'cortesia'));
            $qtde_mes           = trim(pg_fetch_result ($res,$i,'qtde_mes'));
            $os_sem_item        = pg_fetch_result ($res,$i,'os_sem_item') ;
            $motivo_atraso      = pg_fetch_result ($res,$i,'motivo_atraso') ;
            $motivo_atraso2     = pg_fetch_result ($res,$i,'motivo_atraso2') ;
            $obs_reincidencia   = pg_fetch_result ($res,$i,'obs_reincidencia') ;
            $nota_fiscal        = pg_fetch_result ($res,$i,'nota_fiscal') ;
            $data_nf            = pg_fetch_result ($res,$i,'data_nf') ;
            $nota_fiscal_saida  = pg_fetch_result ($res,$i,'nota_fiscal_saida') ;
            $observacao         = pg_fetch_result ($res,$i,'observacao') ;
            $consumidor_revenda = pg_fetch_result ($res,$i,'consumidor_revenda');
            $peca_sem_estoque   = pg_fetch_result ($res,$i,'peca_sem_estoque');
            $intervalo          = pg_fetch_result ($res,$i,'intervalo');
            $troca_garantia     = pg_fetch_result ($res,$i,'troca_garantia');
            $texto              = "";
            $admin              = pg_fetch_result ($res,$i,'admin');
            $mao_de_obra_desconto = pg_fetch_result ($res,$i,'mao_de_obra_desconto');

            if ($login_fabrica == 203){
                $recebido_via_correios = pg_fetch_result($res, $i, "recebido_via_correios");
            }

            if (in_array($login_fabrica, [184])) {
                $total_pecas = pg_fetch_result($res, $i, 'pecas');
            }

	    if ($login_fabrica == 200){
		$total_pecas = pg_fetch_result($res, $i, 'total_pecas');
	    }

            if($login_fabrica == 74) {
                $justificativa_canceladas = "";
                $descricao_cancelada = "";
                $cancelada = pg_fetch_result($res, $i, "cancelada");

                if($cancelada == 't'){
                $descricao_cancelada = "Cancelada";
                $sql_obs_canceladas = "SELECT observacao
                                       from tbl_os_status
                                       where os = $os
                                       and status_os = 156
                                       and fabrica_status = $login_fabrica";
                $res_obs_canceladas = pg_query($con, $sql_obs_canceladas);

                $justificativa_canceladas = pg_fetch_result($res_obs_canceladas, 0, "observacao");
                }
            }

            if ($login_fabrica == 1) {
                $taxa_administrativa_gradual = (float)pg_fetch_result($res, $i, taxa_administrativa_gradual);
                if ($taxa_administrativa_gradual == 0.0) {
                    $taxa_administrativa_gradual = 1;
                }

            }
            if ($login_fabrica == 42 && $prestacao_servicos == 't') {
                $taxa_administrativa = pg_fetch_result($res, $i, custo_peca);
            }

            if($login_fabrica == 52){ //hd_chamado=2598225
                $data_conserto = pg_fetch_result($res, $i, 'data_conserto');
                $obs_adicionais = pg_fetch_result($res, $i, 'obs_adicionais');
            }

            if($os_sem_item > 0) {
                $sqlpr = "SELECT COUNT(*)
                    FROM tbl_os_item
                    JOIN tbl_os_produto USING (os_produto)
                    JOIN tbl_servico_realizado USING (servico_realizado)
                    LEFT JOIN tbl_pedido_cancelado USING(pedido,peca)
                    WHERE tbl_os_produto.os = $os
                    AND tbl_os_item.custo_peca = 0
                    AND tbl_pedido_cancelado.pedido isnull
                    AND tbl_servico_realizado.troca_de_peca";
                $respr = pg_query($con,$sqlpr);
                $peca_sem_preco = pg_fetch_result($respr,0,0);
            }
            // HD 107642 (augusto)
            $familia_descr      = pg_fetch_result($res,$i,'familia_descr');
            $familia_cod        = pg_fetch_result($res,$i,'familia_cod');
            $familia_id         = pg_fetch_result($res,$i,'familia_id');
            $valor_adicional    = pg_fetch_result($res,$i,'valores_adicionais');
            $entrega_tecnica    = pg_fetch_result($res,$i,'entrega_tecnica');
            # HD 340281
            $total_pecas = ($login_fabrica == 90) ? 0 : $total_pecas;

            if ( isset($totalizador[$familia_id]) ) {
                $totalizador[$familia_id]['total_km']   += (float) $total_km;
                $totalizador[$familia_id]['total_mo']   += (float) $total_mo;
                $totalizador[$familia_id]['total']      += (float) $total_km + $total_mo;
            } else {
                $totalizador[$familia_id]['descr']       = $familia_descr;
                $totalizador[$familia_id]['total_km']   = (float) $total_km;
                $totalizador[$familia_id]['total_mo']   = (float) $total_mo;
                $totalizador[$familia_id]['total']      = (float) $total_km + $total_mo;
            }
            //echo $familia_id,'-',$familia_descr,'_';
            $totalizador['geral']['total_km']   += (float) $total_km;
            $totalizador['geral']['total_mo']   += (float) $total_mo;
            $totalizador['geral']['total_pedagio']  += (float) $pedagio;
            $totalizador['geral']['total']      += (float) $total_km + $total_mo + $pedagio;
            $ultima_familia                      = ( is_null($ultima_familia) ) ? $familia_id : $ultima_familia;
            $exibir_total_familia                = (boolean) ( ! is_null($ultima_familia) && $ultima_familia != $familia_id );

            $nota_fiscal = str_replace(array(";",",","/","-",".","+","!","@","*"),"",$nota_fiscal);
            $nota_fiscal_saida = str_replace(array(";",".","/","-",",","+","!","@","*","''"),"",$nota_fiscal_saida);
            // fim HD 107642

            //HD 237498: Aproveitei para fazer esta correção. A sql que busca as OS é a mesma que busca algumas informações do extrato, então sempre traz pelo menos 1 resultado, mas ne sempre tem OS na linha. O correto mesmo seria corrigir a sql principal, mas isso tem que ser feito com MUUUIIITA calma, então está ai o "quebra-galho"
            if (strlen($os) > 0) {

                // HD 107642 (augusto)
                if ( $login_fabrica == 50 && $exibir_total_familia ) {
                    $ultima_familia_exibida = $ultima_familia;
?>
                    <tr class="menu_top">
                        <td colspan="6" align="right"> Total da família (</em><?php echo $totalizador[$ultima_familia]['descr']; ?></em>):  </td>
                        <td align="right"><?php echo number_format($totalizador[$ultima_familia]['total_km'],2,',','.'); ?></td>
                        <td align="right"><?php echo number_format($totalizador[$ultima_familia]['total_mo'],2,',','.'); ?></td>
                        <td align="right"><?php echo number_format($totalizador[$ultima_familia]['total'],2,',','.'); ?></td>
                        <td colspan="4">&nbsp;</td>
                    </tr>
<?php
                }
                $ultima_familia = (int) $familia_id;
                // fim HD 107642

                if ($peca_sem_estoque == "t"){
                    $coloca_botao = "sim";
                }
                if( in_array($login_fabrica, array(11,172)) ){
                    $os_log= trim(pg_fetch_result($res,$i,os_log));
                }

                if ($consumidor_revenda == "R" && in_array($login_fabrica, array(6,24,51,151,165))) {
                    $consumidor_nome = $revenda_nome;
                }

                $cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
                $btn = ($i % 2 == 0) ? "azul" : "amarelo";

                # HD 62078 - OSs com troca mostrar outra cor
                if ($login_fabrica == 45){
                    $sqlTroca = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os";
                    $resTroca = pg_query($con,$sqlTroca);
                    if(pg_num_rows($resTroca)==1){
                        $cor = (pg_fetch_result($resTroca,0,ressarcimento)=='t') ? "#CCCCFF" : "#FFCC66";
                    }
                }

                //takashi 1583  if(substr($motivo_atraso,0,34) == 'Esta OS é reincidente pois o posto')$observacao = "Justificativa do Sistema: ".$motivo_atraso;
                //      echo "<div id='justificativa_$i' style='visibility : hidden; position:absolute; width:500px;left: 200px;opacity:.75;' class='Erro'  >$observacao</div>";
                if (strlen($os_reincidente) > 0 && $login_fabrica != 158) {
                    //HD 15683
                    $sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
                    $res1 = pg_query ($con,$sql);

                    $sqlr="SELECT tbl_os_extra.os_reincidente from tbl_os_extra where os=$os";
                    $resr=pg_query($con,$sqlr);

                    if(pg_num_rows($resr)>0) $os_reinc=pg_fetch_result($resr,0,os_reincidente);
                    if(strlen($os_reinc)>0){
                        if($login_fabrica == 1){

                            $sqlR = "SELECT tbl_os.sua_os, extrato FROM tbl_os_extra JOIN tbl_os USING(os) WHERE os=$os_reinc";
                            $resR = pg_query($con,$sqlR);
                            if (pg_num_rows($resR) > 0){
                                $sos          = pg_fetch_result ($resR,0,sua_os) ;
                                $sextrato     = pg_fetch_result ($resR,0,extrato) ;
                                if($sextrato<>$extrato){
                                    $texto = "-R";
                                    $cor   = "#FFCCCC";
                                    if(strlen(trim($obs_reincidencia))==0){$obs_reincidencia = $motivo_atraso;}
                                    $msg_reincidencia = "<td colspan='8' height='60' style='background-color: $cor; color: #AC1313;' align='left'>$obs_reincidencia</td>"; $msg_2 = "OS anterior:<br><a href='os_press.php?os=$os_reinc' target = '_blank'>$codigo_posto$sos</a><br><span style='font-size:8px'>EM OUTRO EXTRATO</span>";
                                }
                                else{
                                    $reincidencias_os[$rr]=$os_reinc;
                                    if(strlen(trim($obs_reincidencia))==0){$obs_reincidencia = $motivo_atraso;}
                                    $msg_reincidencia = "<td colspan='8' height='50' style='background-color: $cor; color: #AC1313;' align='left'>$obs_reincidencia</td>";
                                    $negrito ="<b>";
                                    $rr++;
                                }
                            }
                        }else{
                            $cor = '#FFCCCC';
                            if($login_fabrica == 30) { # HD 163277
                                $sqls = " SELECT os FROM tbl_os_status where os = $os and status_os=138 ";
                                $ress = pg_query($con,$sqls);
                                if(pg_num_rows($ress) > 0){
                                    $cor = "#CCFF99";
                                }
                            }
                        }
                    }
                }

                if (!empty($os_reincidente) && $login_fabrica == 158) {
                    $cor = '#FFCCCC';
                }

                if ($login_fabrica == 203 AND $recebido_via_correios == 't'){ 
                    $cor = '#93c9a6';
                }

				//HD 237498: Legendar as OS que ainda estejam em intervenção de KM
				if (strlen($os) && (in_array($login_fabrica,array(30,50,74,85,90,91,115,116,117,120,201)))) {
					//Verifica se a OS em algum momento entrou em intervenção de KM, status 98 | Aguardando aprovação da KM
					$sql = "SELECT
						status_os
						FROM tbl_os_status
						WHERE os = $os
						AND status_os IN (98,99,100,101)
						ORDER BY data DESC
						LIMIT 1";

					$res_km = pg_query($con, $sql);

					if (pg_num_rows($res_km)) {
						//Caso a OS algum dia tenha entrado em intervenção de KM, precisa ser verificado se saiu todas as vezes
						//A OS pode sair da intervenção de KM por um dos status abaixo:
						// 99 | KM Aprovada
						//100 | KM Aprovada com alteração
						//101 | km Recusada
						$n_intervencao_km = pg_fetch_result($res_km,0,status_os);

						if ($n_intervencao_km == 98) {
							$cor = "#FFCC99";
							$intervencao_km_os = true;
						}
						else {
							$intervencao_km_os = false;
						}
					}
					else {
						$intervencao_km_os = false;
					}
				}

                for($r = 0; $r < $rr; $r++){
                    if($reincidencias_os[$r] == $os) $negrito = "<b>";
                }
                if($login_fabrica == 1){
                    if (in_array($nota_fiscal, $localizou_array)) {
                        $negrito ="<b>";
                    }
                }
                // HD 18816
                if ($login_fabrica == 1) {
                    $sqlD = "SELECT os_reincidente, status_os FROM tbl_os JOIN tbl_os_status USING(os) WHERE tbl_os.os = $os AND status_os = 95 AND os_reincidente IS TRUE;";
                    $resD = @pg_query($con,$sqlD);
                    if(@pg_num_rows($resD) > 0){//HD 47150
                        $status_os = pg_fetch_result($resD,0,status_os);
                        if($status_os==95) $cor='#FFCCFF';
                    }
                }
                if ($login_fabrica == 1 && $cortesia == "t") $cor = "#D7FFE1";
                if ($login_fabrica == 178 && $qtde_mes > 12) $cor = "#D7FFE1";
                if ($login_fabrica == 6 && $intervalo > 30) $intervalo = "<B><font color='#FF3300'>$intervalo</font></b>";

                if($login_fabrica == 2 && strlen($os)){
                    $btn_zera="f";
                    $sqlc="SELECT os from tbl_os where os=$os and data_fechamento < '2008-01-01 00:00:00'";
                    $resc=pg_query($con,$sqlc);
                    if(pg_num_rows($resc) > 0){
                        $cor='#FFCC00';
                        $btn_zera="t";
                    }
                }
                if($login_fabrica==51 and $troca_garantia == 't' ){
                    $cor='#CCFF99';
                }

                if ( in_array($login_fabrica, array(11,172)) && $cor == '#FFCCCC') {
                    $reinc_class = ' reincidente';
                } else {
                    $reinc_class = '';
                }

                if((!empty($mao_de_obra_desconto)) and (empty($total_mo)) and $login_fabrica == 30) {
                    $cor90 = "#87CEFA";
                }else{
                    $cor90 = "";
                }

            if(in_array($login_fabrica, array(176,190))){
                    $sqlTA = "SELECT tbl_tipo_atendimento.descricao
                                 FROM tbl_os
                                 JOIN tbl_tipo_atendimento USING(tipo_atendimento)
                                WHERE tbl_os.os = $os
                                  AND tbl_os.fabrica = $login_fabrica";

                    $resTA = pg_query($con,$sqlTA);
                    $xtipo_atendimento   = pg_result($resTA,0,'descricao');
            }

            if(in_array($login_fabrica, array(190))){
                    $sqlCont = "SELECT tbl_contrato_os.contrato
                                FROM tbl_os
                                JOIN tbl_contrato_os USING(os)
                               WHERE tbl_os.os = $os
                                 AND tbl_os.fabrica = $login_fabrica";

                    $resCont = pg_query($con,$sqlCont);
                    $n_contrato   = pg_result($resCont,0,'contrato');
            }


                
				echo "<TR class='table_line{$reinc_class}' style='background-color: $cor;background-color: $cor90;'>\n";
				//HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
				//           não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
				//           de extrato avuldo. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
				//           SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
				if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
					if (strlen($aprovado) == 0) {
						if ($login_fabrica <> 1){
							$rowspan = "";
						}
						else {
							$rowspan = "rowspan='2'";
						}
						echo "<TD align='center' $rowspan><input type='checkbox' name='os[$i]' value='$os'><input type='hidden' name='sua_os[$i]' value='$sua_os'></TD>\n";
					}
				}
				//HD 205958: Rotina antiga
				elseif ($ja_baixado == false AND $login_fabrica <> 1){
					if (($ja_baixado == false AND $login_fabrica <> 6) OR ($ja_baixado==false AND $login_fabrica==6 ANd strlen($liberado)==0)){
						echo "<TD align='center'><input type='checkbox' class='check-os' name='os[$i]_aux' idx='$i' value='$os'><input type='hidden' name='sua_os[$i]_aux' id='sua_os[$i]_aux'  value='$sua_os'></TD>\n";
					}
				}elseif($ja_baixado == false){ // HD 2225 takashi colocou esse if($ja_baixado == false) pois se nao fosse fabrica 1 colocava os checks... se estiver com problema tire
					echo "<TD align='center' rowspan='2'><input type='checkbox' name='os[$i]' value='$os'><input type='hidden' name='sua_os[$i]' value='$sua_os'></TD>\n";
				}

				if ($login_fabrica == 1 OR $login_fabrica == 7){
					echo ($login_fabrica ==1 ) ? "<TD nowrap rowspan='2' " : "<TD nowrap ";

					if ($peca_sem_estoque =="t" and $pedido_faturado=='t') echo "bgcolor='#d9ce94'";
					echo "><a href=\"javascript:void(0);\" onclick=\"javascript:window.open('detalhe_ordem_servico.php?os=$os','','width=750,height=500,scrollbars=yes');\">";
				}elseif ($login_fabrica==30){
					echo "<TD nowrap><a href=\"javascript:void(0);\" onclick=\"javascript:window.open('detalhe_os_esmaltec.php?os=$os','','width=750,height=500,scrollbars=yes');\">";
					//echo "<TD nowrap><a href='detalhe_os_esmaltec.php?os=$os' target='_blank'>";
				}else{
					if ($login_fabrica == 178){
                        $os_link = explode("-", $sua_os);
                        $os_link = $os_link[0];
                        echo "<TD nowrap><a href='os_revenda_press.php?os_revenda=$os_link' target='_blank'>";
                    }else{
                        echo "<TD nowrap><a href='os_press.php?os=$os' target='_blank'>";
                    }
                }

				if ($login_fabrica == 1 and strlen($sua_os)>0) echo $codigo_posto;
				echo $sua_os . $texto . "</a> ";
                echo (in_array($login_fabrica, array(190))) ? "<TD nowrap style='text-align:center;'>$xtipo_atendimento</TD>\n" : "";
                echo (in_array($login_fabrica, array(190))) ? "<TD nowrap style='text-align:center;'>$n_contrato</TD>\n" : "";

				if( in_array($login_fabrica, array(3,11,126,172)) ){
					echo "<td align='center'>";
					$temanexo = false;
					if(strlen($anexo_os_revenda) > 0){
						echo "<a target='_blank' href='visualiza_anexos_os.php?os=$os&acesso_admin=true' id='anexar_img_os'><img src='../imagens/clips.gif' title='Visualizar Anexos na OS'/> </a>";
						$temanexo = true;
					}
					if($temanexo == false){
						for($jj = 0;$jj<count($anexos_os_item);$jj++){

							if(strstr($anexos_os_item[$jj], "anexo_os_item_{$login_fabrica}_{$os}_img_os_item_")){
								echo "<a target='_blank' href='visualiza_anexos_os.php?os=$os&acesso_admin=true' id='anexar_img_os'><img src='../imagens/clips.gif' title='Visualizar Anexos na OS'/> </a>";
								$temanexo = true;
								break;
							}
						}

					}

					if($temanexo == false){

						for($jj = 0;$jj<count($anexos_os);$jj++){
							if(strstr($anexos_os[$jj], "anexo_os_{$login_fabrica}_{$os}_img_os_")){
								echo "<a target='_blank' href='visualiza_anexos_os.php?os=$os&acesso_admin=true' id='anexar_img_os'><img src='../imagens/clips.gif' title='Visualizar Anexos na OS'/> </a>";
								$temanexo = true;
								break;
							}
						}
						if($temanexo == false){
							echo "-";
						}else{
							$temanexo == false;
						}
					}else{

						$temanexo = false;;
					}
					echo "</td>";

				}

				# Regras para auditoria de NS e Reincidência - HD 2539696
				if (in_array($login_fabrica, array(30))) {
					$sqlStatus = "SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os_status.os = $sua_os ORDER BY tbl_os_status.data DESC LIMIT 1;";
					$resStatus = pg_query($con,$sqlStatus);
					$statusOs = pg_fetch_result($resStatus, 0, descricao);
				}

				if (in_array($login_fabrica, array(87,158))) {

						$sql2 = "SELECT tbl_tipo_atendimento.descricao, tbl_os_extra.qtde_horas_atendimento, tbl_os_extra.valor_por_hora
							FROM tbl_os
							JOIN tbl_os_extra USING(os)
							JOIN tbl_tipo_atendimento USING(tipo_atendimento)
							WHERE tbl_os.os = $os AND tbl_os.fabrica = $login_fabrica";

						$res2 = pg_query($con,$sql2);
				}
				echo "</TD>\n";
				if ($login_fabrica == 1) echo "
                    <TD nowrap>$negrito$codigo_fabricacao</TD>
                    <TD nowrap>$abertura</TD>
                    <TD nowrap>$conserto</TD>
                    <TD nowrap>".($dias * -1)."</TD>
                    ";
                echo ($login_fabrica == 158) ? '<td nowrap>'. @pg_result($res2,0,0) .'</td>' : '';
                if (in_array($login_fabrica, array(30))) echo "<td nowrap>$statusOs</td>";
                
                if ($login_fabrica == 183) {
                    echo "<TD nowrap>".pg_fetch_result($res, $i, 'nome_tecnico')."</TD>\n";
                }

                echo "<TD nowrap>$serie</TD>\n";
                
                if ($login_fabrica <> 1) echo "<TD align='center'>$abertura</TD>\n";
                
                if ($login_fabrica == 183){
                    echo "
                        <TD align='center'>$fechamento</TD>\n
                        <TD align='center'>$conserto</TD>\n";
                }

                if ($login_fabrica == 176) echo "<TD align='center'>$xtipo_atendimento</TD>\n";

				if($login_fabrica == 52){ //hd_chamado=2598225
					echo "<td align='center'>$data_conserto</td>";
				}
				if ( in_array($login_fabrica, array(11,45,51,158,167,172,203)) ) echo "<TD align='center'>$fechamento</TD>\n";
				if ($login_fabrica == 2) echo "<TD align='center'>$finalizada</TD>\n";
				if ($login_fabrica == 6) echo "<TD align='center'>$intervalo</TD>\n";
				echo "<TD nowrap>$negrito<ACRONYM TITLE=\"$consumidor_nome\">".substr($consumidor_nome,0,17);
				if ($login_fabrica == 30 or $login_fabrica == 158) echo "<td nowrap>$consumidor_cidade</TD>\n";
				if ($login_fabrica == 1) echo " - ".$consumidor_fone;
				echo "</ACRONYM></TD>\n";

                if ($login_fabrica == 183){
                    echo "
                        <TD align='center'>$consumidor_cidade</TD>\n
                        <TD align='center'>$consumidor_estado</TD>\n";
                }

                if ( in_array($login_fabrica, array(11,172)) ) {
                    echo "<TD nowrap>$revenda_nome</TD>";
                    echo "<TD nowrap>$nota_fiscal</TD>";
                    echo "<TD nowrap>$nota_fiscal_saida</TD>";
                    echo "<TD nowrap>$negrito<ACRONYM TITLE=\"$produto_referencia - $produto_nome\">$produto_referencia</ACRONYM></TD>\n";
                    echo "<TD align='right' nowrap>$negrito " . number_format($total_mo,2,",",".") . "</TD>\n";
                    echo "<TD nowrap>$admin</TD>";

                    if(strlen($admin)>0){
                        $sqlMO = "SELECT admin_paga_mao_de_obra FROM tbl_os_extra WHERE os = $os";
                        $resMO = pg_query($con, $sqlMO);

                        $paga_mao_de_obra_admin = pg_fetch_result($resMO,0,admin_paga_mao_de_obra);

                        if($paga_mao_de_obra_admin=='t'){
                            if(strlen($aprovado)==0){
                                if( !in_array($login_fabrica, array(11,172)) ){
                                    echo "<TD>&nbsp;</TD>";
                                }
                            }else{
                                # HD 196633
                                #                           echo "<TD ALIGN='center' nowrap>
                                #                               <a #href='$PHP_SELF?extrato=$extrato&os=$os&zerarmo=t&pagina=$pagina'>Não Pagar M.O</a>
                                #                           </TD>";
                            }
                        }else{
                            # HD 196633
                            #                       echo "<TD ALIGN='center' nowrap>
                            #                           M.O Zerada pelo ADMIN
                            #                       </TD>";
                        }
                    }else{
                        //echo "<TD>&nbsp;</TD>";
                    }
                    if($os_log == $os){
                        echo "<td nowrap align='center'> <span class='text_curto'> <a href='os_log.php?os=$os_log' rel='ajuda1' target='blank' title='OS que Posto tentou cadastrar Fora de Garantia<br> Antes confirmar a nota fiscal e Número Série' class='ajuda'>?</a></span></TD>\n";
                    }
                }else{
                    if($login_fabrica != 138){
                        echo "<TD nowrap>$negrito<ACRONYM TITLE=\"$produto_referencia - $produto_nome\"><a href='lbm_consulta.php?produto=$produto' target='_blank'>";
                    }

                    if ($login_fabrica == 1 OR $login_fabrica == 2) {
                        echo $produto_referencia;
                    } elseif($login_fabrica == 45){
                        echo $produto_nome;
                    }else{
                        echo substr($produto_nome,0,17);
                    }
                    echo "</a></ACRONYM></TD>\n";

                    if (!in_array($login_fabrica, array(35,169,170)) && (in_array($login_fabrica,array(51,81,88,95,99,101,106,108,111,122,123,124,126,127,128,131,134,136,137,140,141,144,72)) || $novaTelaOs)) {

                        $total_mo = (strlen($total_mo) == 0) ? 0 : $total_mo;
                        echo "<td align='right'>" . number_format($total_mo,2,",",".") . "</td>";
                    }
                }

                # HD 936143
                if($login_fabrica == 80){
                    echo "<TD nowrap>$revenda_nome</TD>";
                }

                if($multimarca =='t')
                    echo "<TD nowrap>$marca</TD>";

                if($login_fabrica == 52){
                    echo "<TD>$nota_fiscal</TD>";
                    echo "<TD>$data_nf</TD>";
                    echo "<TD>$pedagio</TD>";
                }

                if (!in_array($login_fabrica, array(169,170)) && ($inf_valores_adicionais || in_array($login_fabrica, array(142, 145)) || isset($fabrica_usa_valor_adicional))) {
                    echo "<TD align='right'>".number_format($valor_adicional,2,",",".")."</TD>";
                }

                if($login_fabrica == 128){
                    echo "<TD align='right'>".number_format($valor_adicional,2,",",".")."</TD>";
                }

                if (in_array($login_fabrica, array(169,170))) {
                    echo "<TD align='right'>".number_format($valor_total_hora_tecnica,2,",",".")."</TD>";
                }

				if (!in_array($login_fabrica, array(169,170)) && (in_array($login_fabrica,array(1,15,24,30,35,42,50,52,74,85,87,90,91,94,104,114,115,116,117,120,201,121,125,128,129,131,134,139,140,141,144)) || $novaTelaOs)) {

                    if($login_fabrica == 128){
                        if(strlen($qtde_km) > 0 && $qtde_km > 30){
                            $sqlKm = "  SELECT  tbl_fabrica.valor_km
                                FROM    tbl_fabrica
                                WHERE   tbl_fabrica.fabrica = $login_fabrica
                                ";
                            $resKm = pg_query($con,$sqlKm);

                            $valor_km = pg_fetch_result($resKm,0,valor_km);
                            $total_km = $valor_km * $qtde_km;
                        }else{
                            $qtde_km = 0;
                            $valor_km = 0;
                            $total_km = 0;
                        }
                    }

					if (in_array($login_fabrica,array(52))){
						$total_os = $total_pecas + $total_mo + $total_km + $pedagio;
					} else if (in_array($login_fabrica,array(30,90,74))){
                      	$total_os = $total_pecas + $total_mo + $total_km;
					} else if (in_array($login_fabrica,array(42,104,120,201,134))){
						$total_os = $total_pecas + $total_mo;
					} else if (in_array($login_fabrica,array(15,50,91,90,24,94, 35,87,114,115,116,117,121,128,129,131,140,141,144))){
						$total_os = $total_mo + $total_km;
					} else if ($login_fabrica == 1){
                        $total_os = $total_mo + ($total_pecas * $taxa_administrativa_gradual);
                    } else {
                        $total_os = $total_mo;
                    }

                    if (isset($novaTelaOs)) {
                        $total_os = $total_mo;

                        $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
                        $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);

                        if (!$nao_calcula_peca) {
                            $total_os += $total_pecas;
                        }

                        if (!$nao_calcula_km) {
                            $total_os += $total_km;
                        }
                    }

                    if($login_fabrica == 90) {
                        $total_os += $taxa_visita;
                    }

					if (!in_array($login_fabrica,array(15,24,35,42,52,74,87,94,104,114,115,116,117,120,201,121,125,128,129,131,134,136,140,141,144,138,139,143,145)) && empty($novaTelaOs)) {
						echo "<TD align='left' nowrap>$negrito<ACRONYM TITLE=\"$revenda_nome\">". substr($revenda_nome,0,17) . "</ACRONYM></TD>\n";
					}

                    if (in_array($login_fabrica,array(15,24,35,52,74,87,94))) {
                        if ($login_fabrica == 35) {

                            $sqlKm = "
                                SELECT  CASE 
                                            WHEN tbl_posto_fabrica.valor_km IS NULL 
                                            THEN tbl_fabrica.valor_km 
                                            ELSE tbl_posto_fabrica.valor_km
                                        END AS valor_km
                                FROM tbl_fabrica
                                JOIN tbl_posto_fabrica USING(fabrica)
                                WHERE tbl_fabrica.fabrica = $login_fabrica
                                AND tbl_posto_fabrica.posto = $posto";
                            $resKm = pg_query($con,$sqlKm);
                            if (pg_num_rows($res)) {
                                $valor_km = pg_result($resKm,0,0);
                            }
                        }
                        echo "<td align='right'>". (strlen($qtde_km) > 0 ? number_format($qtde_km,2,",",".") : "0,00") ."</td>";
                        if(!in_array($login_fabrica,array(74,115,116,117,120,125,129,136))){
                            echo "<td align='right'>".number_format($valor_km,2,",",".")."</td>";
                        }
                    }

                    if ($login_fabrica == 35) {
                        echo "<td align='right'>".$qtde_visitas."</td>";
                    }

                    if (isset($novaTelaOs)) {
                        $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
                    }

                    if (!isset($novaTelaOs) && in_array($login_fabrica,array(15,30,50,85,90,52,24,91,94,35,74,87,114,115,116,117,120,201,125,128,129,131,140,141,144))) {
                        echo "<TD class='td-km' align='right' nowrap> $negrito " ;

                        $qtde_km = ($qtde_km>0) ? "Kilometragem: $qtde_km Km" : "&nbsp;";
                        if (strlen($total_km) == 0) {
                            $total_km = 0;
                        }
                        echo "<ACRONYM TITLE=\"$qtde_km\">".number_format($total_km,2,",",".")."</ACRONYM>\n";
                        echo "</TD>\n";
                    } else if ((isset($novaTelaOs) && !$nao_calcula_km && $login_fabrica != 139) or $login_fabrica == 178) {
                        echo "<TD class='td-km' align='right' nowrap> $negrito " ;

                        $qtde_km = ($qtde_km>0) ? "Kilometragem: $qtde_km Km" : "&nbsp;";
                        if (strlen($total_km) == 0) {
                            $total_km = 0;
                        }

                        echo "<ACRONYM TITLE=\"$qtde_km\">".number_format($total_km,2,",",".")."</ACRONYM>\n";
                        echo "</TD>\n";
                    }

                    if(in_array($login_fabrica, array(140,141))){
                        echo "<TD align='right'>".number_format($entrega_tecnica,2,",",".")."</TD>";
                    }

                    if (isset($novaTelaOs)) {
                        $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);
                    }

					if(!isset($novaTelaOs) && $extrato_sem_peca != "t"){
						if (in_array($login_fabrica,array(1,30,42,52,74,85,90,104,134))) {
							if ($login_fabrica == 30) {
								$mostrar_pecas = "onclick='mostrarPecas($os);' style='cursor:pointer; text-decoration: underline;'";
							} else $mostrar_pecas = "";
							echo "<TD align='right' nowrap $mostrar_pecas>$negrito " ;
							if ($peca_sem_preco == 0) {
								if (strlen($total_pecas) == 0) {
									$total_pecas = 0;
								}
								echo number_format($total_pecas,2,",",".");
							} else {
								if($login_fabrica == 1 and $reembolso_peca_estoque == 't' ){
									echo "<font color='#ff0000'>". number_format($total_pecas,2,",","."). "</font>";
								}else{
									echo ($os_sem_item == 0) ? "":"<font color='#ff0000'><b>SEM PREÇO</b></font>";
								}
							}
							echo "</TD>\n";
						}

					} else if (isset($novaTelaOs) && !$nao_calcula_peca && $extrato_sem_peca != "t" && !in_array($login_fabrica, array(35,139,160,183,191,193)) and !$replica_einhell) {
                        echo "<TD align='right' nowrap >$negrito " ;
                        if ($peca_sem_preco == 0 || in_array($login_fabrica, [184,200])) {
                            if (strlen($total_pecas) == 0) {
                                $total_pecas = 0;
                            }
                            echo number_format($total_pecas,2,",",".");
                        } else if ($peca_sem_preco > 0 && $login_fabrica == 160 or $replica_einhell) {
                            echo ($os_sem_item == 0) ? "":"<font >0,00</font>";
                        } else {
                            echo ($os_sem_item == 0) ? "":"<font color='#ff0000'><b>SEM PREÇO</b></font>";
                        }
                        echo "</TD>\n";
                    }

                    if ($login_fabrica == 87) {
                        echo '<td nowrap>'. @pg_result($res2,0,0) .'</td>
                            <td align="center">'. @pg_result($res2,0,1) .'</td>
                            <td align="right">'. number_format( @pg_result($res2,0,2),2,',','.') .'</td>';

                    }

                    if(!in_array($login_fabrica, array(128,131,134,140,141,143,144,145)) && empty($novaTelaOs)){
                        echo "<TD class='td-mo' align='right' nowrap> $negrito" . number_format($total_mo,2,",",".") . "</TD>\n";
                    }
                    if ($login_fabrica == 1) {
                        $percTx = ($taxa_administrativa_gradual - 1) * 100;
                        echo "<TD align='right' nowrap>" . number_format($percTx,2,",",".") . "%</TD>\n";
                    }
                    if ($login_fabrica == 42 && $prestacao_servicos == 't') {
                        echo "<TD align='right' nowrap>" . number_format($taxa_administrativa,2,",",".") . "</TD>\n";
                    }

                    if ($login_fabrica == 85) {
                        echo "<td align='right'>".number_format($pedagio,2,",",".")."</td>";
                        $sqlValorBonificacao = "
                            SELECT  tbl_extrato_lancamento.valor
                            FROM    tbl_extrato_lancamento
                            WHERE   fabrica = $login_fabrica
                            AND     os = $os
                            AND     tbl_extrato_lancamento.descricao ILIKE '%diferenciado'
                            LIMIT   1
                        ";
                        $resValorBonificacao = pg_query($con,$sqlValorBonificacao);
                        $valor_adicional = pg_fetch_result($resValorBonificacao,0,valor);
                        echo "<td align='right'>".number_format($valor_adicional,2,",",".")."</td>";
                    }
                    if( in_array( $login_fabrica, array(90,125) ) ) {
                        $taxa_visita = (strlen($taxa_visita) ==0) ? "0" : $taxa_visita;
                        echo "<td align='right'>".number_format($taxa_visita,2,",",".")."</td>";
                    }

                    if (in_array($login_fabrica,array(85))) {
                        $total_os = $total_pecas + $total_mo + $total_km + $pedagio + $valor_adicional;
                    } else if ($inf_valores_adicionais || in_array($login_fabrica, array(140,141,142,144)) || isset($fabrica_usa_valor_adicional)){
                        $total_os = $total_mo + $total_km + $entrega_tecnica + $valor_adicional;
                    } else if (in_array($login_fabrica, array(145))) {
                        $total_os = $total_mo + $total_km + $total_pecas + $valor_adicional;
                    } else if ($login_fabrica == 42 && $prestacao_servicos == 't') {
                        $total_os = $total_pecas + $taxa_administrativa;
                    }else if($login_fabrica == 15){
                         $total_os = $total_mo + $total_km;
                    }

                    if (in_array($login_fabrica, array(125))) {
                        $total_os = $total_mo + $taxa_visita + $valor_adicional + $total_km;
                    }

                    if(in_array($login_fabrica,array(15,125,138))){
                        echo "<TD class='td-total' align='right' nowrap>$negrito".number_format($total_os,2,",",".")."</TD>\n";
                    }else if($login_fabrica == 42 ){
			            $resultadoMkt = $total_pecas + $taxa_administrativa;
		    	        echo "<TD class='td-total' align='right' nowrap>$negrito".number_format($resultadoMkt,2,",",".")."</TD>\n";
		            }else{
                        //esse linha estava sem if e estava para todos, fiz o if acima para 138 apenas
                        echo "<TD class='td-total' align='right' nowrap>$negrito".number_format($total_os,2,",",".")."</TD>\n";
                    }					

                    if ($login_fabrica == 35) {
                        $ttl = ($total_km + $total_mo + $valor_adicional);
                        echo "<TD class='td-total' align='right' nowrap>$negrito".number_format($ttl,2,",",".")."</TD>\n";

                    }

                    if($login_fabrica == 74){
                        echo "<td class='td-total' align='right' nowrap>$descricao_cancelada</td>";
                        echo "<td class='td-total' align='right' nowrap>$justificativa_canceladas</td>";

                    }

                    //ATENÇÃO: A rotina abaixo redireciona para a tela de auditoria de KM, para que seja auditada a OS com a rotina já existente, CUIDADO AO MODIFICAR
                    if (in_array($login_fabrica,array(30,50,90,91))) {// Retirada fabrica 85 HD - 2163607 de acordo com Lin.
                        if ($intervencao_km_os) {
                            echo "<td align='center' nowrap><a href='aprova_km.php?os=$os&btn_acao=Pesquisar' target='_blank'>VER INTERV KM</a></td>";
                        }else{
                            echo "<td align='center' nowrap></td>";
                        }
                    }

                    if($login_fabrica == 15 && $ja_baixado ==  false){
                        echo "
                            <TD>
                            <input type='button' os='".$os."' class='btn-mo' value='Alterar MO' />
                            <input type='button' os='".$os."' class='btn-km' value='Alterar KM' />
                            </TD>";
                    }

                    # HD 36258 / HD 45710 17/10/2008
                    if ($login_fabrica == 50 OR $login_fabrica == 15){
                        $arrayos[] = $os;
                        echo "<TD align='center' nowrap><input type='checkbox' name='osimprime[]' id='osimprime' rel='osimprime' value='$arrayos[$i]'></TD>\n";
                    }
                    if ($login_fabrica<>15 && strlen($msg_2) > 0) {
                        echo "<TD align='right'>$msg_2</TD>\n"; $msg_2 = '';
                        $colspan_add = 1;
                    }
                }
                if(in_array($login_fabrica, array(141,144,145))){
                    $sql_ta = "SELECT tbl_tipo_atendimento.descricao, tbl_os.data_fechamento - tbl_os.data_abertura AS dias
                        FROM tbl_os
                        JOIN tbl_os_extra USING(os)
                        JOIN tbl_tipo_atendimento USING(tipo_atendimento)
                        WHERE tbl_os.os = $os AND tbl_os.fabrica = $login_fabrica";

                    $res_ta = pg_query($con,$sql_ta);

                    $tipo_atendimento   = pg_result($res_ta,0,'descricao');
                    $qte_dias_aberto    = pg_result($res_ta,0,'dias');

                    $qte_dias_aberto    = ($qte_dias_aberto == 0) ? "Mesmo Dia" : $qte_dias_aberto;

                    echo "<td>".$tipo_atendimento."</td>";
                    echo (in_array($login_fabrica, array(141,144))) ? "<td align='center'>{$qte_dias_aberto}</td>" : "";
                }

                if($login_fabrica == 50){
                    $sqlOsBaixada = "SELECT baixada FROM tbl_os_extra WHERE os = $os AND baixada IS NOT NULL";
                    $resOsBaixada = pg_query($con,$sqlOsBaixada);

                    if(pg_numrows($resOsBaixada) > 0){
                        echo "<TD align='center'><img src='imagens/img_ok.gif'></TD>";
                        echo "<TD>&nbsp;</TD>";
                    } else {
                        echo "<TD width='200' id='col_$os' align='center'>&nbsp;
                        <input type='hidden' name='leitor' class='check_serie' value='$os'>
                            <input type='text' name='leitor2_$os' id='leitor2_$os' rel='$i' class='valida_serie leitor_$i frm'>
                            </TD>";

                        if($libera_serie == 'sim'){
                            echo "<TD ><input type='button' value='Aprovar' onclick='aprovaSerieManual($os);' id='btn_$os'></TD>\n";
                        }
                    }

                }

                if($login_fabrica==2){
                    // HD 19580
                    echo "<TD align='center' valign='top' nowrap>";
                    if($btn_zera=='t' and $total_mo > 0 and $aprovado ==0){
                        echo "<input type='button' name='zerar_$i' id='zerar_$i' value='RECUSAR MO' onClick=\"if (this.value=='Processando...'){ alert('Aguarde');}else {this.value='Processando...'; zerar_mo('$os','$extrato','zerar_$i');}\" >";
                    }
                    echo "</TD>\n";
                }
                if($login_fabrica==51 ){
                    echo "<TD align='right' nowrap>";
                    echo "<input type='text' name='mo_$i' id='mo_$i' value='" . number_format($total_mo,2,",",".") . "' readonly>";
                    echo "</TD>\n";
                }
                if($login_fabrica==51 and $troca_garantia == 't'){
                    // HD 59408
                    echo "<TD align='center' valign='top' nowrap>";
                    //echo $total_mo;
                    if($total_mo<>2){
                        echo "<input type='button' name='mo2_$i' id='mo2_$i' value='Pagar M.O." . $real . "2,00 para troca' onClick=\"if (this.value=='Processando...'){ alert('Aguarde');}else {this.value='Processando...'; mo2('$os','$extrato','mo2_$i','mo_$i');}\" >";
                    }
                    echo "</TD>\n";
                }
                if ($login_fabrica ==6 or $login_fabrica==43 or $login_fabrica == 105 ){
                    echo "<TD align='right' nowrap>$negrito " . number_format($total_mo,2,",",".") . "</TD>\n";
                }

                if ($login_fabrica == 178){
                    echo "<td nowrap><a href='os_press.php?os=$os' target='_blank'><button type='button'>Consultar OS</button></a></td>";
                }

                if ($login_fabrica == 6) {
                    $status_nf = "";

                    $sql_status_nf = "SELECT status_os FROM tbl_os_status WHERE os = $os AND status_os IN (189, 190, 191) ORDER BY data DESC LIMIT 1";
                    $res_status_nf = pg_query($con, $sql_status_nf);

                    if (pg_num_rows($res) > 0) {
                        switch (pg_fetch_result($res_status_nf, 0, "status_os")) {
                        case 189:
                            $status_nf = "<span style='color: #D88A23; font-weight: bold;'>Auditoria Pendente<span>";
                            break;

                        case 190:
                            $status_nf = "<span style='color: #B94A48; font-weight: bold;'>NF Recusada<span>";
                            break;

                        case 191:
                            $status_nf = "<span style='color: #468847; font-weight: bold;'>NF Aprovada</span>";
                            break;
                        }
                    }

                    echo "<td nowrap>$status_nf</td>";
                }

                if ($login_fabrica ==6 AND strlen($liberado)==0){
                    echo "<TD align='center' nowrap>";
                    echo "<a href=\"$PHP_SELF?ajax_debito=true&os=$os&keepThis=trueTB_iframe=true&height=400&width=500\" title=\"Manutenção de valores da OS\" class=\"thickbox\">Alterar MO</a>";
                    echo "</TD>\n";
                }

                if (!in_array($login_fabrica, [167, 203])) {
			    				$sqlD = "SELECT status_os FROM tbl_os_status WHERE os = $os and status_os = 91 and fabrica_status = $login_fabrica order by os_status desc limit 1;";

                    $resD = @pg_query($con,$sqlD);
                    if(@pg_num_rows($resD) > 0){
                        echo "<td bgcolor='#FFCC00'>Pendência Doc.</td>";
                    }
                }

                echo "</TR>\n";

                if ($login_fabrica == 1){
                    echo "<tr >";
                    echo $msg_reincidencia;
                    $msg_reincidencia='';
                    echo "</TR>\n";
                }

                if ($login_fabrica==6 and strlen($os_reincidente) > 0){
                    echo "<tr  style='background-color: $cor;'>";
                    echo "<td ></td>";
                    echo "<td colspan='7' align='left'><B>Motivo Reincidência:</B> ";
                    echo pg_fetch_result ($res,$i,obs_reincidencia) ;
                    echo "</td>";
                    echo "</TR>\n";
                }

                if ($login_fabrica==6 and strlen($motivo_atraso) > 0){
                    echo "<tr  style='background-color: $cor;'>";
                    echo "<td ></td>";
                    echo "<td colspan='7' align='left'><B>Motivo Atraso:</B> ";
                    echo pg_fetch_result ($res,$i,motivo_atraso) ;
                    echo "</td>";
                    echo "</TR>\n";
                }
                if ($login_fabrica==6 and strlen($motivo_atraso2) > 0){
                    echo "<tr  style='background-color: $cor;'>";
                    echo "<td ></td>";
                    echo "<td colspan='7' align='left'><B>Motivo Atraso 60 dias:</B> ";
                    echo pg_fetch_result ($res,$i,motivo_atraso2) ;
                    echo "</td>";
                    echo "</TR>\n";
                }
                $negrito ="";

                if($login_fabrica == 52 AND strlen($os)>0){

                    $sql_def_const = "select tbl_defeito_constatado.defeito_constatado, tbl_defeito_constatado.descricao as desc_def_constatado, tbl_defeito_constatado_grupo.descricao as desc_def_constatado_grupo
                        from tbl_os
                        join tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
                        join tbl_defeito_constatado_grupo on tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo
                        where tbl_os.os = $os";
                    $res_def_const = pg_query($con, $sql_def_const);
                    if(pg_num_rows($res_def_const)>0){
                        for($z=0;$z<pg_num_rows($res_def_const); $z++){
                            $desc_def_constatado            = pg_result($res_def_const,$z,"desc_def_constatado");
                            $desc_def_constatado_grupo      = pg_result($res_def_const,$z,"desc_def_constatado_grupo");


                            $num_cols = ($login_fabrica == 52) ? 8 : 7;
                            $observacao = ($login_fabrica == 52 && strlen($obs_adicionais ) > 0) ? 'SOBRE KM: '.$obs_adicionais : '&nbsp;';
                            echo "<tr style='background-color: $cor;'>";
                            echo "<TD colspan='4'>$observacao</TD>"; //hd_chamado=2598225
                            echo "<td colspan='4'>GRUPO DEFEITO CONSTATADO: $desc_def_constatado_grupo</td>";
                            echo "<td colspan='$num_cols'>DEFEITO CONSTATADO: $desc_def_constatado</td>";
                            echo "</tr>";
                        }
                    }

                    if(strlen($os_reincidente) > 0){
?>
                        <tr style='background-color: <?=$cor?>;'>
                            <td colspan="16" style="text-align:center;font-weight:bold;">DADOS DA OS REINCIDENTE</td>
                        </tr>
<?
                        $sqlReinc = "
                            SELECT  tbl_os.os                                   AS reinc_os                     ,
                            tbl_os.sua_os                               AS reinc_sua_os                 ,
                            tbl_defeito_constatado.descricao            AS reinc_def_constatado         ,
                            tbl_defeito_constatado_grupo.descricao      AS reinc_grupo_def_constatado
                            FROM    tbl_os
                            JOIN    tbl_defeito_constatado          ON tbl_defeito_constatado.defeito_constatado                = tbl_os.defeito_constatado
                            JOIN    tbl_defeito_constatado_grupo    ON tbl_defeito_constatado_grupo.defeito_constatado_grupo    = tbl_defeito_constatado.defeito_constatado_grupo
                            WHERE   tbl_os.fabrica  = $login_fabrica
                            AND     tbl_os.os       = $os_reincidente
                            ";
                        $resReinc = pg_query($con,$sqlReinc);

                        $reinc_os                   = pg_fetch_result($resReinc,0,reinc_os);
                        $reinc_sua_os               = pg_fetch_result($resReinc,0,reinc_sua_os);
                        $reinc_def_constatado       = pg_fetch_result($resReinc,0,reinc_def_constatado);
                        $reinc_grupo_def_constatado = pg_fetch_result($resReinc,0,reinc_grupo_def_constatado);

?>
                        <tr style='background-color: <?=$cor?>;'>
                            <td colspan="2" style="text-align:right"><a href='os_press.php?os=<?=$reinc_os?>' target = '_blank'><?=$reinc_sua_os?></a></td>
                            <td colspan="5">GRUPO DEFEITO: <?=$reinc_grupo_def_constatado?></td>
                            <td colspan="9">DEF. CONSTATADO: <?=$reinc_def_constatado?></td>
                        </tr>
                        <tr style='background-color: <?=$cor?>;'>
                            <td colspan="16">&nbsp;</td>
                        </tr>
<?
                    }
                }
                if($login_fabrica == 30){
                    echo "<tr>";
                    echo "<td colspan='100%' id='linha_$os' rel='' style='display:none;'>";
                    echo "<div id='dados_$os' style='display:none;border: 1px solid #949494;'></div>";
                    echo "</td>";
                    echo "</tr>";
                }

            }   //FIM IF strlen($os) > 0

        }//FIM FOR

        echo "<input type='hidden' id='qtde_de_inputs' value='$qtde'>";
        // HD 107642 (augusto)
        if ( $login_fabrica == 50 && $familia_id != $ultima_familia_exibida ) {
            ?>
            <tr class="menu_top">
                <td colspan="6" align="right"> Total da família (</em><?php echo $totalizador[$ultima_familia]['descr']; ?></em>):  </td>
                <td align="right"><?php echo number_format($totalizador[$ultima_familia]['total_km'],2,',','.'); ?></td>
                <td align="right"><?php echo number_format($totalizador[$ultima_familia]['total_mo'],2,',','.'); ?></td>
                <td align="right"><?php echo number_format($totalizador[$ultima_familia]['total'],2,',','.'); ?></td>
                <td colspan="4">&nbsp;</td>
            </tr>
            <?php
        }
        if($login_fabrica == 50) {
        ?>
            <tr class="menu_top">
                <td colspan="6" align="right"> Total de todas as famílias:  </td>
                <td align="right"><?php echo number_format($totalizador['geral']['total_km'],2,',','.'); ?></td>
                <td align="right"><?php echo number_format($totalizador['geral']['total_mo'],2,',','.'); ?></td>
                <td align="right"><?php echo number_format($totalizador['geral']['total'],2,',','.'); ?></td>
                <td colspan="4">&nbsp;</td>
            </tr>
        <?php 
        }
        // fim HD 107642

        //HD: 121163 - VAI BLOQUEAR PARA OUTRAS FÁBRICAS TAMBÉM, ESTAVA ERRADA A CONDIÇÃO ABAIXO.
        //HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
        //           não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
        //           de extrato avuldo. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
        //           SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
        $libera_acesso_acoes = false;
        if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
            if (strlen($aprovado) == 0) {
                $libera_acesso_acoes = true;
            }
        }
        //HD 205958: Condicional antigo
        elseif ( (strlen($extrato_valor) == 0 AND $ja_baixado == false AND $login_fabrica <> 6) OR (strlen($extrato_valor) == 0 AND $ja_baixado == false AND $login_fabrica == 6) AND strlen($liberado)==0 ) {
            $libera_acesso_acoes = true;
        }
        echo "<input type='hidden' name='contador' value='$i'>";
        echo "</tbody></TABLE>\n";


          if( in_array($login_fabrica, array(11,172)) ){
            $jsonPOST = excelPostToJson($_GET);?>

            <div id='gerar_excel' class="btn_excel">
                <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
                <span><img src='imagens/excel.png' /></span>
                <span class="txt">Gerar Arquivo Excel</span>
            </div>
            <br>
            <br>
            <?php
        }

        if (isset($novaTelaOs) and !in_array($login_fabrica, array("147"))) {
            $sqlExtratoLiberado = "SELECT liberado FROM tbl_extrato WHERE fabrica = {$login_fabrica} AND extrato = {$extrato} AND liberado IS NOT NULL";
            $resExtratoLiberado = pg_query($con, $sqlExtratoLiberado);

            if (pg_num_rows($resExtratoLiberado) > 0) {
                $libera_acesso_acoes = false;
            }
        }

        if ($libera_acesso_acoes) {
            if ($login_fabrica == 1 or $login_fabrica==50 ) $colspan = 10; else $colspan = 7;
            if (in_array($login_fabrica,array(6,42,104,121)))  $colspan = 9;
            if (in_array($login_fabrica,array(2,125)))  $colspan = 8;
            if (in_array($login_fabrica,array(24,94,35,74,115,116,117,120,201,125,129,131,140,141,144))) $colspan = 11;
            if ($login_fabrica == 30) $colspan = 7;
            if (in_array($login_fabrica,array(30,15)) ) $colspan= 12;
            if ($login_fabrica == 50) $colspan = 14;
            $colspan = $login_fabrica == 87 ? 14 : $colspan;
            $colspan += $colspan_add;

            if($login_fabrica == 35){
                $colspan = 12;
            }

            if($login_fabrica != 148 || pg_num_rows($res_yanmar) == 0){

                echo "<br /> <table class='table table-bordered' id='acoes_marcadas'>";
                echo "<TR class='titulo_coluna'>\n";
                echo "<TD colspan='3' align='left'><p>&nbsp; AÇÃO PARA OS's MARCADAS: &nbsp; </p>";
                echo "<input type='hidden' name='posto' value='$posto'>";
                echo "<select name='select_acao' size='1' class='frm'>";
                echo "<option value=''></option>";

                if (isset($novaTelaOs)) {
                    echo "<option value='REABRIR'";  if ($_POST["select_acao"] == "REABRIR")  echo " selected"; echo ">REABRIR OS (RETIRA DO EXTRATO)</option>";
                    if($login_fabrica <> 147){
                        echo "<option value='RECUSAR'";  if ($_POST["select_acao"] == "RECUSAR")  echo " selected"; echo ">RECUSAR OS (ZERAR VALOR)</option>";
                    }
                    echo "<option value='EXCLUIR'";  if ($_POST["select_acao"] == "EXCLUIR")  echo " selected"; echo ">EXCLUIR OS</option>";
                    echo "<option value='ACUMULAR'"; if ($_POST["select_acao"] == "ACUMULAR") echo " selected"; echo ">ACUMULAR PARA PRÓXIMO EXTRATO</option>";
                    // echo "<option value='REABRIR'";  if ($_POST["select_acao"] == "REABRIR")  echo " selected"; echo ">REABRIR ORDEM DE SERVIÇO (RETIRA DO EXTRATO)</option>";
                    // echo "<option value='RECUSAR'";  if ($_POST["select_acao"] == "RECUSAR")  echo " selected"; echo ">RECUSAR (ZERAR VALOR)</option>";
                    // echo "<option value='EXCLUIR'";  if ($_POST["select_acao"] == "EXCLUIR")  echo " selected"; echo ">EXCLUIR (EXCLUI A ORDEM DE SERVIÇO)</option>";
                } else {
                    if($login_fabrica == 91){ //hd_chamado=2754972
                        $label_acao = "BLOQUEADA NESTE EXTRATO";
                        echo "<option value='RECUSADA_PAGAMENTO'";  if ($_POST["select_acao"] == "RECUSADA_PAGAMENTO")  echo " selected"; echo ">RECUSADA PAGAMENTO</option>";
                    }else{
                        $label_acao = "RECUSADO PELO FABRICANTE";
                    }
                    echo "<option value='RECUSAR'";  if ($_POST["select_acao"] == "RECUSAR")  echo " selected"; echo ">$label_acao</option>";
                    echo "<option value='EXCLUIR'";  if ($_POST["select_acao"] == "EXCLUIR")  echo " selected"; echo ">EXCLUÍDA PELO FABRICANTE</option>";

                    if($login_fabrica <> 91 ) { # HD 303959
                        echo "<option value='ACUMULAR'"; if ($_POST["select_acao"] == "ACUMULAR") echo " selected"; echo ">ACUMULAR PARA PRÓXIMO EXTRATO</option>";
                    }
                }

                if(in_array($login_fabrica,array(88, 101))) {

                        // HD 406128
                        echo '<option value="ZERAR">ZERAR MÃO-DE-OBRA</option>';

                }

                if($login_fabrica == 1){
                        echo "<option value='RECUSAR_DOCUMENTO'"; if ($_POST["select_acao"] == "RECUSAR_DOCUMENTO") echo " selected"; echo ">PENDÊNCIA DE DOCUMENTO</option>";

                }
                if($login_fabrica == 1){
                    echo "<option value='RECUSAR_DOCUMENTO'"; if ($_POST["select_acao"] == "RECUSAR_DOCUMENTO") echo " selected"; echo ">PENDÊNCIA DE DOCUMENTO</option>";
                }

                if (in_array($login_fabrica, array(66, 11, 172))) {
                    $sql = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = $login_fabrica AND status_os = 13 AND liberado IS TRUE;";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0) {
                        echo "<option value=''>-->RECUSAR OS</option>";

                        for($l=0;$l<pg_num_rows($res);$l++){
                            $motivo_recusa = pg_fetch_result($res,$l,motivo_recusa);
                            $motivo        = pg_fetch_result($res,$l,motivo);
                            $motivo = substr($motivo,0,50);
                            echo "<option value='$motivo_recusa'>$motivo</option>";
                        }
                    }
                    $sql = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = $login_fabrica AND status_os = 14 AND liberado IS TRUE;";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0) {
                        echo "<option value=''>-->ACUMULAR OS</option>";

                        for($l=0;$l<pg_num_rows($res);$l++){
                            $motivo_recusa = pg_fetch_result($res,$l,motivo_recusa);
                            $motivo        = pg_fetch_result($res,$l,motivo);
                            $motivo = substr($motivo,0,50);
                            echo "<option value='$motivo_recusa'>$motivo</option>";
                        }
                    }
                    $sql = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = $login_fabrica AND status_os = 15 AND liberado IS TRUE;";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0) {
                        echo "<option value=''>-->EXCLUIR OS</option>";

                        for($l=0;$l<pg_num_rows($res);$l++){
                            $motivo_recusa = pg_fetch_result($res,$l,motivo_recusa);
                            $motivo        = pg_fetch_result($res,$l,motivo);
                            $motivo = substr($motivo,0,50);
                            echo "<option value='$motivo_recusa'>$motivo</option>";
                        }
                    }
                }

                echo "</select>";
                echo " &nbsp; <input type='button' value='Continuar' border='0' align='absmiddle' onclick='javascript: document.frm_extrato_os.submit()' style='cursor: pointer;margin-bottom: 10px;'>";
                echo "</TD>\n";
                echo "</TR>\n";
                echo "</table>\n";

            }

        }
        echo "<input type='hidden' name='contador' value='$i'>";
        echo "</TABLE>\n";

        ?>
        <br>
        <div>
            <a rel='shadowbox' href="relatorio_log_alteracao_new.php?parametro=tbl_extrato_consulta_os_extra&id=<?=$extrato;?>" name="btnAuditorLog">Visualizar Log Auditor</a>
        </div>
        <br>

        <?php

        if(in_array($login_fabrica, [85,144])){
            $sql = "SELECT
                    tbl_posto_fabrica.banco,
                    tbl_posto_fabrica.agencia,
                    tbl_posto_fabrica.conta,
                    tbl_posto_fabrica.nomebanco,
                    tbl_posto_fabrica.favorecido_conta,
                    tbl_posto_fabrica.cpf_conta,
                    tbl_posto_fabrica.obs_conta,
                    tbl_posto_fabrica.tipo_conta
                FROM tbl_extrato
                    INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                WHERE tbl_extrato.fabrica = {$login_fabrica} AND extrato = {$extrato}";
            $resBanco = pg_query($con,$sql);

            if(pg_num_rows($resBanco) > 0){
                while($objeto_banco = pg_fetch_object($resBanco)){
                    ?>
                    </br>
                    <table style="width:550px;" border='0' align='center' cellspacing='1' cellpadding='0' class='formulario frm-pagamento'>
                        <tr class='titulo_tabela'>
                            <td height='20' colspan='4' class='frm-title'>Informações Bancárias</td>
                        </tr>
                        <tr>
                            <td align='left' class='frm-title' >CPF/CNPJ Favorecido</td>
                            <td align='left' class='frm-title' colspan="2">Nome Favorecido</td>
                        </tr>
                        <tr>
                            <td class="">
                                <input type="text" readonly value="<?=$objeto_banco->cpf_conta?>" class='frm'>
                            </td>
                            <td colspan="2">
                                <input type="text" readonly value="<?=$objeto_banco->favorecido_conta?>" class='frm' style="width:97%;">
                            </td>
                        </tr>
                        <tr>
                            <td align='left' class='frm-title' colspan="3">Banco</td>
                        </tr>
                        <tr>
                            <td class="" colspan="3">
                                <input type="text" readonly value="<?=$objeto_banco->banco?> - <?=$objeto_banco->nomebanco?>" class='frm' style="width:97.5%">
                            </td>
                        </tr>
                        <tr>
                            <td align='left' class='frm-title'>Tipo de Conta</td>
                            <td align='left' class='frm-title'>Agência</td>
                            <td align='left' class='frm-title'>Conta</td>
                        </tr>
                        <tr>
                            <td class="">
                                <input type="text" readonly value="<?=$objeto_banco->tipo_conta?>" class='frm'>
                            </td>
                            <td class="">
                                <input type="text" readonly value="<?=$objeto_banco->agencia?>" class='frm'>
                            </td>
                            <td class="">
                                <input type="text" readonly value="<?=$objeto_banco->conta?>" class='frm'>
                            </td>
                        </tr>
                        <tr>
                            <td align='left' class='frm-title' colspan="3">Observações</td>
                        </tr>
                        <tr>
                            <td class="" colspan="3">
                                <textarea class='frm' readonly name="obs_conta" cols="85" rows="2"><?=$objeto_banco->obs_conta?></textarea>
                            </td>
                        </tr>
                    </table>
                    <?php
                }
            }
        } //FIM ELSE DO LOGIN_FABRICA 85

    }//FIM ELSE


    if ( !in_array($login_fabrica, array(1,11,51,172)) ) {
        // ##### PAGINACAO ##### //

        // links da paginacao
        echo "<br>";

        echo "<div>";

        if($pagina < $max_links) {
            $paginacao = pagina + 1;
        }else{
            $paginacao = pagina;
        }

        // paginacao com restricao de links da paginacao
        // pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
        $todos_links        = $mult_pag->Construir_Links("strings", "sim");

        // função que limita a quantidade de links no rodape
        $links_limitados    = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

        for ($n = 0; $n < count($links_limitados); $n++) {
            echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
        }

        echo "</div>";

        $resultado_inicial = ($pagina * $max_res) + 1;
        $resultado_final   = $max_res + ( $pagina * $max_res);
        $registros         = $mult_pag->Retorna_Resultado();

        $valor_pagina   = $pagina + 1;
        $numero_paginas = intval(($registros / $max_res) + 1);

        if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

        if ($registros > 0){
            echo "<br>";
            echo "<div>";
            echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
            echo "<font color='#cccccc' size='1'>";
            echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
            echo "</font>";
            echo "</div>";
        }
        // ##### PAGINACAO ##### //
    }
} // Fecha a visualização dos extratos

if ($login_fabrica == 148 && $Monta_Tabela_Yanmar !== 1) {
    echo "<br />";
    GeraTabelaEntregaTecnica($res_yanmar, 1);
}

if($login_fabrica == 45) { // HD 46595
    if(@pg_num_rows($resxls) >0) {
        flush();
        $data = date ("d/m/Y H:i:s");

        $arquivo_nome     = "extrato-consulta-os-$login_fabrica.xls";
        $path             = "/www/assist/www/admin/xls/";
        $path_tmp         = "/tmp/";

        $arquivo_completo     = $path.$arquivo_nome;
        $arquivo_completo_tmp = $path_tmp.$arquivo_nome;

        echo `rm $arquivo_completo_tmp `;
        echo `rm $arquivo_completo `;

        $fp = fopen ($arquivo_completo_tmp,"w");

        fputs ($fp,"<html>");
        fputs ($fp,"<head>");
        fputs ($fp,"<title>Extrato Consulta - $data");
        fputs ($fp,"</title>");
        fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
        fputs ($fp,"</head>");
        fputs ($fp,"<body>");

        fputs ($fp,"<TABLE width='750' border='0' align='center' cellspacing='1' cellpadding='0'>");
        fputs ($fp,"<TR class='menu_top'>");
        fputs ($fp,"<TD align='left'> Extrato: ");
        fputs ($fp,pg_fetch_result($resxls,0,extrato));
        fputs ($fp,"</TD>");
        fputs ($fp,"<TD align='left'> Data: " . pg_fetch_result ($resxls,0,data_geracao) . "</TD>");
        fputs ($fp,"<TD align='left'> Qtde de OS: ". $qtde_os ."</TD>");
        fputs ($fp,"<TD align='left'> Total: " . $real . number_format(pg_fetch_result ($resxls,0,total),2,",",".") . "</TD>");
        fputs ($fp,"</TR>");
        fputs ($fp,"<TR class='menu_top'>");
        fputs ($fp,"<TD align='left'> Código: " . pg_fetch_result ($resxls,0,codigo_posto) . " </TD>");
        fputs ($fp,"<TD align='left' colspan='3'> Posto: " . pg_fetch_result ($resxls,0,nome_posto) . "  </TD>");
        fputs ($fp,"</TR>");
        fputs ($fp,"</TABLE>");
        fputs ($fp,"<br>");

        fputs ($fp,"<TABLE width='750' align='center' border='1' cellspacing='1' cellpadding='1'>");
        fputs ($fp,"<caption>Relação de Ordens de Serviços</caption>");
        fputs ($fp,"<tr>");
        fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>OS</b></td>");
        fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>SÉRIE</b></td>");
        fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>ABERTURA</b></td>");
        fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>FECHAMENTO</b></td>");
        fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>CONSUMIDOR</b></td>");
        fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>PRODUTO</b></td>");
        fputs ($fp,"</tr>");

        for($i=0;$i<pg_num_rows($resxls);$i++){
            $os                 = trim(pg_fetch_result ($resxls,$i,os));
            $sua_os             = trim(pg_fetch_result ($resxls,$i,sua_os));
            $data               = trim(pg_fetch_result ($resxls,$i,data));
            $abertura           = trim(pg_fetch_result ($resxls,$i,abertura));
            $fechamento         = trim(pg_fetch_result ($resxls,$i,fechamento));
            $serie              = trim(pg_fetch_result ($resxls,$i,serie));
            $consumidor_nome    = trim(pg_fetch_result ($resxls,$i,consumidor_nome));
            $produto       = trim(pg_fetch_result ($resxls,$i,produto));
            $produto_nome       = trim(pg_fetch_result ($resxls,$i,descricao));
            $produto_referencia = trim(pg_fetch_result ($resxls,$i,referencia));

            $cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

            $sqlr="SELECT tbl_os_extra.os_reincidente from tbl_os_extra where os=$os";
            $resr=pg_query($con,$sqlr);
            if(pg_num_rows($resr)>0)  $os_reinc=pg_fetch_result($resr,0,os_reincidente);
            if(strlen($os_reinc) > 0) {
                $cor="#FFCCCC";
            }

            fputs ($fp,"<TR bgcolor='$cor'>\n");
            fputs ($fp,"<TD>".$sua_os."</TD>\n");
            fputs ($fp,"<TD nowrap align='center'>$serie</TD>\n");
            fputs ($fp,"<TD align='center'>$fechamento</TD>\n");
            fputs ($fp,"<TD align='center'>$abertura</TD>\n");
            fputs ($fp,"<TD nowrap>$consumidor_nome</TD>\n");
            fputs ($fp,"<TD nowrap>");
            fputs ($fp,$produto_referencia . "  -  " . $produto_nome);
            fputs ($fp,"</TD>\n");
            fputs ($fp,"</tr>");
        }

        fputs ($fp,"</table>");

        fputs ($fp,"</body>");
        fputs ($fp,"</html>");
        fclose ($fp);

        echo ` cp $arquivo_completo_tmp $path `;
        $data = date("Y-m-d").".".date("H-i-s");

        echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
        echo "<br>";
        echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
        echo"<tr>";
        echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/extrato-consulta-os-$login_fabrica.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
        echo "</tr>";
        echo "</table>";
    }
}

if($telecontrol_distrib){
    echo "<br><a href='lote_capa_conferencia_gama.php?extrato=$extrato&linhas=$registros' target='_BLANK'>CONFERIR LOTE</a><br>";
}

// ##### EXIBE AS OS QUE SERÃO ACUMULADAS OU RECUSADAS ##### //
if (strlen($select_acao) > 0 AND strlen($lancamento) == 0) {
    $os     = $_POST["os"];
    $sua_os = $_POST["sua_os"];

    if($login_fabrica == 148){

        if(isset($_POST["os_y"])){

            foreach ($_POST["os_y"] as $os_post) {
                $os[] = $os_post;
            }

            $sua_os = $os;

        }

    }

    if (!in_array($login_fabrica, array(6,24,35))) {
        switch (strtolower($select_acao)) {
            case 'recusar':
                $motivo_status_os = 13;
                break;

            case 'excluir':
                $motivo_status_os = 15;
                break;

            case 'reabrir':
            case 'acumular':
                $motivo_status_os = 14;
                break;

            default:
                if (isset($motivo_status_os)) {
                    unset($motivo_status_os);
                }
                break;
        }

        if (isset($motivo_status_os)) {
            $sql_motivos_recusa = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = {$login_fabrica} AND liberado IS TRUE AND status_os = {$motivo_status_os}";
            $res_motivos_recusa = pg_query($con, $sql_motivos_recusa);
        }
    }

    echo "<br>";
    echo "<HR WIDTH='600' ALIGN='CENTER'>";
    echo "<br>";
    echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1' style='table-layout: fixed;'>";
    echo "<tr class='menu_top'>";
    echo "<td colspan='".((isset($motivo_status_os) && pg_num_rows($res_motivos_recusa) > 0) ? 3 : 2)."'>";
    echo "Preencha o campo observação ou selecione um motivo pelo qual será ";

    if($login_fabrica == 91){ //hd_chamado=2754972
        $label_acao = "BLOQUEADA NESTE EXTRATO";
    }else{
        $label_acao = "RECUSADO PELO FABRICANTE";
    }

    if (strtoupper($select_acao) == "RECUSAR") echo $label_acao;
    elseif (strtoupper($select_acao) == "EXCLUIR") echo "EXCLUÍDA PELO FABRICANTE";
    elseif (strtoupper($select_acao) == "ACUMULAR") echo "ACUMULAR PARA PRÓXIMO EXTRATO";
    elseif (strtoupper($select_acao) == "REABRIR") echo "REABERTURA DE ORDEM DE SERVIÇO";
    elseif (strtoupper($select_acao) == "RECUSAR_DOCUMENTO") echo "PENDÊNCIA DE DOCUMENTO";
    elseif (strtoupper($select_acao) == 'ZERAR') echo "ZERADA A MÃO-DE-OBRA";
    elseif (strtoupper($select_acao) == 'REABRIR') echo "REABERTA A ORDEM DE SERVIÇO";
    echo "</TD>\n";
    echo "</tr>\n";

    if($login_fabrica == 148){
        $contador = count($os);
    }

    $kk = 0;
    for ($k = 0 ; $k < $contador ; $k++) {
        if ($k == 0) {
            echo "<tr class='menu_top'>";
            echo "<td>OS</td>";
            echo "<td>OBSERVAÇÃO</td>";
            if (!in_array($login_fabrica, array(6,24,35)) && isset($motivo_status_os) && pg_num_rows($res_motivos_recusa) > 0) {
                echo "<td>MOTIVO</td>";
            }
            echo "</tr>\n";
        }

        if (strlen($msg_erro) > 0) {
            $os[$k]     = $_POST["os_" . $kk];
            $sua_os[$k] = $_POST["sua_os_" . $kk];
            $obs        = $_POST["obs_" . $kk];
        }

        $cor = ($kk % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

        if ($linha_erro == $kk && strlen($linha_erro) != 0) $cor = "FF0000";

        if (strlen($os[$k]) > 0) {

            if(strtoupper($select_acao) == "RECUSAR_DOCUMENTO"){

                $sql_doc = "SELECT tbl_extrato.protocolo          ,
                                    to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') as data_geracao      ,
                                    tbl_posto_fabrica.codigo_posto,
                                    tbl_extrato.extrato
                                FROM tbl_os
                                JOIN tbl_os_extra using(os)
                                JOIN tbl_extrato using(extrato)
                                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                                WHERE os = $os[$k];";
                $res_doc = pg_query($con,$sql_doc);
                $codigo_posto = pg_fetch_result($res_doc,0,codigo_posto);
                $data_geracao = pg_fetch_result($res_doc,0,data_geracao);
                $protocolo    = pg_fetch_result($res_doc,0,protocolo);

                $obs = "Constatamos na conferência da documentação do extrato $protocolo do dia $data_geracao, a falta da cópia da nota fiscal da O.S $codigo_posto$sua_os[$k]
                Portanto, essa O.S será aprovada novamente no extrato da próxima semana e caso não possua a documentação gentileza nos comunicar para que possamos excluí-la."; /* HD 944675 - Retirado o nome "Sabrina Amaral" */

            }

            if (strtoupper($select_acao) == "RECUSAR" && in_array($login_fabrica, array(129))){

                $sql_reabre = "UPDATE tbl_os SET finalizada = NULL, data_fechamento = NULL, os_fechada = false WHERE os = {$os[$k]} AND fabrica = {$login_fabrica}";
                $res_reabre = pg_query($con, $sql_reabre);

            }

            echo "<tr class='table_line' style='background-color: $cor;'>\n";
            echo "<td align='center'>";

            if ($login_fabrica == 1) echo $codigo_posto.$sua_os[$k];
            else                     echo $sua_os[$k];

            echo "<input type='hidden' name='os_$kk' value='" . $os[$k] . "'><input type='hidden' name='sua_os_$kk' value='" . $sua_os[$k] . "'></td>\n";
            echo "<td align='center'><textarea name='obs_$kk' class='frm' style='width: 80%;'>$obs</textarea></td>\n";

            if (!in_array($login_fabrica, array(6,24,35)) && isset($motivo_status_os) && pg_num_rows($res_motivos_recusa) > 0) {
                echo "
                    <td style='text-align: center;'>
                        <select class='frm' name='motivo_$kk' >
                            <option value=''></option>";

                pg_result_seek($res_motivos_recusa, 0);

                while ($motivo_recusa = pg_fetch_object($res_motivos_recusa)) {
                    echo "<option value='{$motivo_recusa->motivo_recusa}' >{$motivo_recusa->motivo}</option>";
                }

                echo "
                        </select>
                    </td>";
            }
            echo "</tr>\n";

            $kk++;
            $protocolo = '';
            $data_geracao = '';
        }
    }

    if($login_fabrica == 91){ //hd_chamado=2754972
        $sql = "SELECT fn_totaliza_extrato($login_fabrica,$extrato); ";
        $res = @pg_query($con,$sql);
        $msg_erro = pg_errormessage($con);
    }

    echo "</table>\n";
    echo "<input type='hidden' name='qtde_os' value='$kk'>";
    echo "<input type='hidden' name='contador' value='$contador'>";
    echo "<br>\n";
    echo "<img border='0' src='imagens/btn_confirmaralteracoes.gif' style='cursor: hand;' onclick=\"javascript: if (document.frm_extrato_os.btn_acao.value == '' ) { document.frm_extrato_os.btn_acao.value='$select_acao'; document.frm_extrato_os.submit(); }else{ alert('Aguarde submissão'); }\" alt='Confirmar Alterações'>\n";
    echo "<br>\n";
}

$lancamento = $_GET['lancamento'];
if(strlen($lancamento) > 0 AND ($login_fabrica == 1 OR $login_fabrica == 10)){
    $sql = "SELECT valor, descricao, os_sedex FROM tbl_extrato_lancamento WHERE extrato_lancamento = $lancamento";

    $res = pg_query($con,$sql);

    $descricao = pg_fetch_result($res,0,descricao);
    $valor     = pg_fetch_result($res,0,valor);
    $os_sedex  = pg_fetch_result($res,0,os_sedex);

    echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='formulario'>\n";
    echo "<INPUT TYPE='hidden' NAME='os_sedex' value='$os_sedex'>";
    echo "<INPUT TYPE='hidden' NAME='extrato_lancamento' value='$lancamento'>";
    echo "<tr class='titulo_tabela'>\n";
    echo "<td colspan='2' align='left' style='color: #FFCC00'>RECUSA DE OS SEDEX</td>";
    echo "</tr>\n";
    echo "<tr class='subtitulo'>\n";
        echo "<td colspan='2' style='font-size: 10px'>Descrição: $descricao - Valor: $real . $valor</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
        echo "<td>OS SEDEX</td>\n";
        echo "<td>OBSERVAÇÃO</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
        echo "<td align='center'>$os_sedex</td>";
        echo "<td align='center'><INPUT TYPE=\"text\" size='100' NAME='descricao' class='frm'></td>";
    echo "</tr>\n";
    echo "<tr class='menu_top'>\n";
        echo "<td colspan='2'><INPUT TYPE=\"submit\" name='recusa_sedex' value='Recusar' class='frm'></td>\n";
    echo "</tr>\n";
    echo "</table>\n";
}

echo "<br />";

if ( in_array($login_fabrica, array(11,172)) ) {//HD 226679

    $sql_obs = "SELECT obs FROM tbl_extrato_extra WHERE extrato = " . abs($extrato);
    $res_obs = pg_query($con, $sql_obs);

    if (pg_num_rows($res_obs)) {
        $obs_extrato = pg_fetch_result($res_obs, 0, obs);
    } else {
        $obs_extrato = '';
    }

    echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='formulario'>\n";
        echo "<tr class='titulo_tabela'>\n";
            echo "<td height='20px'><label for='obs_extrato' title='Neste campo você poderá gravar contatos feitos com o PA (Posto de Atendimento) ou alguma observação importante do extrato.'>Observação</label></td>\n";
        echo "</tr>\n";
        echo "<tr >\n";
            echo "<td align='center' style='padding:10px 0 10px 0 ;'><textarea name='obs_extrato' id='obs_extrato' cols='100' rows='5' class='frm'>$obs_extrato</textarea></td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
            echo "<td align='center'><input type='submit' name='btn_obs' value='Enviar OBS' /></td>\n";
        echo "</tr>\n";
    echo "</table>\n";
    echo "<br />";

}

if ($login_fabrica == 158) {

    $sql_obs = "SELECT obs FROM tbl_extrato_extra WHERE extrato = " . abs($extrato);
    $res_obs = pg_query($con, $sql_obs);

    if (pg_num_rows($res_obs)) {
        $obs_extrato = pg_fetch_result($res_obs, 0, obs);
    } else {
        $obs_extrato = '';
    } ?>

    <table width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='formulario'>
        <tr class='titulo_tabela'>
            <td height='20px'><label for='obs_extrato' title='Informações de exportação de Extrato.'>Observação</label></td>
        </tr>
        <tr>
            <td align='center' style='padding:10px 0 10px 0 ;'><?= $obs_extrato; ?></td>
        </tr>
    </table>
    <br />

<? }

##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
if ($login_fabrica == 1 OR $login_fabrica == 10) {
    if($login_fabrica == 1){
        $condL = " AND tbl_extrato_lancamento.extrato_lancamento NOT IN(SELECT tbl_extrato_lancamento.extrato_lancamento
                    FROM tbl_extrato_lancamento
                    JOIN tbl_residuo_solido ON tbl_residuo_solido.extrato_lancamento = tbl_extrato_lancamento.extrato_lancamento
                    WHERE tbl_extrato_lancamento.extrato = $extrato) ";
    }

    $sql = "SELECT  'OS SEDEX' AS descricao                        ,
                    tbl_extrato_lancamento.os_sedex                ,
                    ''      AS historico                           ,
                    tbl_extrato_lancamento.automatico              ,
                    sum(tbl_extrato_lancamento.valor) as valor
            FROM    tbl_extrato_lancamento
            JOIN    tbl_lancamento USING (lancamento)
            WHERE   tbl_extrato_lancamento.extrato = $extrato
            AND     tbl_lancamento.fabrica         = $login_fabrica
            $condL
            GROUP BY tbl_extrato_lancamento.os_sedex               ,
                    tbl_extrato_lancamento.automatico              ,
                    tbl_extrato_lancamento.extrato_lancamento";

    $sql = "SELECT 'OS SEDEX' AS descricao                                     ,
                    tbl_extrato_lancamento.extrato_lancamento                  ,
                    tbl_extrato_lancamento.descricao AS descricao_lancamento   ,
                    tbl_extrato_lancamento.os_sedex                            ,
                    '' AS historico                                            ,
                    tbl_extrato_lancamento.historico AS historico_lancamento   ,
                    tbl_extrato_lancamento.automatico                          ,
                    sum(tbl_extrato_lancamento.valor) as valor                 ,
                    tbl_os_sedex.obs                                           ,
                    tbl_os_sedex.sua_os_destino                                ,
                    tbl_os_sedex.sua_os_origem                                 ,
                    case when tbl_extrato_lancamento.admin notnull then exa.login else tbl_admin.login end as login
            FROM    tbl_extrato_lancamento
            JOIN    tbl_lancamento   USING (lancamento)
            LEFT JOIN tbl_os_sedex   ON tbl_os_sedex.os_sedex = tbl_extrato_lancamento.os_sedex
            LEFT JOIN tbl_admin      ON tbl_admin.admin       = tbl_os_sedex.admin
            LEFT JOIN tbl_admin exa    ON exa.admin       = tbl_extrato_lancamento.admin
            WHERE   tbl_extrato_lancamento.extrato = $extrato
            AND     tbl_lancamento.fabrica         = $login_fabrica
            $condL
            GROUP BY    tbl_extrato_lancamento.os_sedex                ,
                        tbl_extrato_lancamento.automatico              ,
                        tbl_extrato_lancamento.descricao               ,
                        tbl_extrato_lancamento.extrato_lancamento      ,
                        tbl_extrato_lancamento.historico               ,
                        tbl_os_sedex.obs                               ,
                        tbl_os_sedex.sua_os_destino                    ,
                        tbl_os_sedex.sua_os_origem , 12 ;";
}else{
    $sql =  "SELECT tbl_extrato_lancamento.extrato_lancamento,
                    tbl_lancamento.descricao         ,
                    tbl_extrato_lancamento.os_sedex  ,
                    tbl_extrato_lancamento.historico ,
                    tbl_extrato_lancamento.valor     ,
                    tbl_extrato_lancamento.automatico
            FROM    tbl_extrato_lancamento
            JOIN    tbl_lancamento USING (lancamento)
            WHERE   tbl_extrato_lancamento.extrato = $extrato
            AND     tbl_lancamento.fabrica         = $login_fabrica
            ORDER BY    tbl_extrato_lancamento.os_sedex,
                        tbl_extrato_lancamento.descricao,
                        tbl_extrato_lancamento.extrato_lancamento";

    if (in_array($login_fabrica,array(6,45,80,85,160,169,170)) or $replica_einhell) {//hd 9482
        if ($login_fabrica == 85) {
            $cond = "\nAND (tbl_extrato_lancamento.descricao NOT ILIKE '%diferenciado' or tbl_extrato_lancamento.descricao is null)\n";
        }
        $sql =  "
            SELECT  tbl_extrato_lancamento.extrato_lancamento,
                    tbl_lancamento.descricao         ,
                    tbl_extrato_lancamento.os_sedex  ,
                    tbl_extrato_lancamento.historico ,
                    tbl_extrato_lancamento.valor     ,
                    tbl_extrato_lancamento.automatico,
                    tbl_extrato_lancamento.descricao AS descricao_lancamento   ,
                    tbl_extrato_lancamento.os_sedex                            ,
                    tbl_extrato_lancamento.historico AS historico_lancamento   ,
                    tbl_admin.login,
                    to_char(tbl_extrato_lancamento.data_lancamento,'DD/MM/YYYY') AS data_lancamento,
                    tbl_extrato_lancamento.os AS os_avulso
            FROM    tbl_extrato_lancamento
            JOIN    tbl_lancamento USING (lancamento)
       LEFT JOIN    tbl_admin      ON tbl_admin.admin       = tbl_extrato_lancamento.admin
            WHERE   tbl_extrato_lancamento.extrato = $extrato
            AND     tbl_lancamento.fabrica         = $login_fabrica
            $cond
      ORDER BY      tbl_extrato_lancamento.os_sedex,
                    tbl_extrato_lancamento.descricao,
                    tbl_extrato_lancamento.extrato_lancamento";
    }
}
if(strlen($extrato) > 0) {
    $res_avulso = pg_query($con,$sql);

    if (pg_num_rows($res_avulso) > 0) {//hd 9482
        if (in_array($login_fabrica, array(1,6,10,45,80,85,160)) or $replica_einhell):
            $colspan = 7;
        elseif ( in_array($login_fabrica, array(11,172)) ):
            //HD 227632: Habilitar excluir para a Lenoxx
            $colspan = 5;
        else:
            $colspan = 4;
        endif;

        echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='tabela'>\n";
        echo "<tr class='titulo_tabela'>\n";
        echo "<td colspan='$colspan'>LANÇAMENTO DE EXTRATO AVULSO</td>\n";
        echo "</tr>\n";
				echo "<tr class='titulo_coluna'>\n";
    		if(in_array($login_fabrica, array(80,85,160)) or $replica_einhell) {
    			echo "<td>OS</td>";
    			echo "<td>Data Lançamento</td>\n";
    		}
        echo "<td>Descrição</td>\n";
        echo "<td>Histórico</td>\n";
        echo "<td>Valor</td>\n";
        echo "<td>Automático</td>\n";//hd 9482
        if ($login_fabrica == 1 OR $login_fabrica == 10 OR $login_fabrica == 6 or $login_fabrica == 45 OR $login_fabrica == 85){
            echo "<td>Admin</td>\n";
            if ( ($login_fabrica==6 and strlen($liberado)==0) OR $login_fabrica<>6 AND $login_fabrica <> 85)
            echo "<td>Ações</td>\n";
        }
        //HD 227632: Habilitar excluir para a Lenoxx
        if ( in_array($login_fabrica, array(11,172)) ) {
            echo "<td>Ações</td>\n";
        }
        echo "</tr>\n";
        $sqly = "SELECT to_char(data_envio,'DD/MM/YYYY') as data_envio
                    FROM tbl_extrato_financeiro
                    WHERE extrato = $extrato
                    LIMIT 1;";
        $resy = @pg_query($con,$sqly);
        if(@pg_num_rows($resy) > 0){
            $data_envio_financeiro = @pg_fetch_result($resy, 0, data_envio);
        }

        for ($j = 0 ; $j < pg_num_rows($res_avulso) ; $j++) {
            $cor = ($j % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

            $descricao               = pg_fetch_result($res_avulso, $j, descricao);
            $historico               = pg_fetch_result($res_avulso, $j, historico);
            $os_sedex                = pg_fetch_result($res_avulso, $j, os_sedex);
            $extrato_lancamento      = pg_fetch_result($res_avulso, $j, extrato_lancamento);
            $obs_sedex               = @pg_fetch_result($res_avulso, $j, obs);

            $sedex_faturada = stristr($obs_sedex, 'faturada');
            if(strlen($sedex_faturada) > 0){
                $descricao = "TROCA FATURADA";
            }

            $sedex_faturada = stristr($obs_sedex, 'Débito');
            if(strlen($sedex_faturada) > 0 AND $login_fabrica==1){ //HD 57068
                $descricao = $obs_sedex;
            }

            //hd 9482
            if (in_array($login_fabrica, array(6,45,80,85,160,169,170)) or $replica_einhell) {
    		    	if(!in_array($login_fabrica, array(80,85,160)) and !$replica_einhell){

                    $descricao = @pg_fetch_result($res_avulso, $j, 'descricao_lancamento');
                    $historico = @pg_fetch_result($res_avulso, $j, 'historico_lancamento');
                }else{
                    $data_lancamento = @pg_fetch_result($res_avulso, $j, 'data_lancamento');
                    $os_avulso = pg_fetch_result($res_avulso, $j, 'os_avulso');

                    if ($login_fabrica != 160 and !$replica_einhell) {
                        if(strlen($historico) == 0){
                            $historico = @pg_fetch_result($res_avulso, $j, 'descricao_lancamento');;
                        }
                    }
                }

                if ($login_fabrica != 160 and !$replica_einhell) {
                    $admin = pg_fetch_result($res_avulso, $j, 'login');
                }
            }
            if($login_fabrica == 1){
                $sua_os_destino = @pg_fetch_result($res_avulso, $j, 'sua_os_destino');
                $sua_os_origem  = @pg_fetch_result($res_avulso, $j, 'sua_os_origem');

                if($sua_os_destino == "CR"){
                    $sql = "SELECT tbl_os.sua_os FROM tbl_os WHERE os = '$sua_os_origem' AND fabrica = $login_fabrica ;";
                    $res = pg_query($con,$sql);
                    $descricao = "CR " . $codigo_posto;
                    //$descricao .= pg_fetch_result($res,0,sua_os);
                }
            }

            if ($login_fabrica == 1 OR $login_fabrica == 10){
                if (strlen($os_sedex) == 0){
                    $descricao          = @pg_fetch_result($res_avulso, $j, 'descricao_lancamento');
                    $historico          = @pg_fetch_result($res_avulso, $j, 'historico_lancamento');
                }
                $admin              = pg_fetch_result($res_avulso, $j, 'login');
            }

            $historico = str_replace("\n", "<br>", $historico);

	    echo "<tr height='18' class='table_line' style='background-color: $cor;'>\n";
	    if (in_array($login_fabrica, array(80,85,160)) or $replica_einhell) {
    		echo "<td><a href='os_press.php?os=$os_avulso' target='_blank'>$os_avulso</a></td>";
    		echo "<td width='35%'>" . $data_lancamento . "</td>";
	    }

        if($login_fabrica == 1){
            if(empty($descricao)){
                $sql = "SELECT tbl_lancamento.descricao FROM tbl_extrato_lancamento
                    INNER JOIN tbl_lancamento ON tbl_lancamento.lancamento = tbl_extrato_lancamento.lancamento
                        AND tbl_lancamento.fabrica = $login_fabrica
                    WHERE tbl_extrato_lancamento.extrato_lancamento = $extrato_lancamento
                        AND tbl_extrato_lancamento.fabrica = $login_fabrica";
                $resLancamento = pg_query($con,$sql);

                if(pg_num_rows($resLancamento) > 0){
                    $descricao = pg_fetch_result($resLancamento, 0, "descricao");
                }
            }
        }
            echo "<td width='35%'>" . $descricao . "</td>";
            echo "<td width='35%' nowrap>" . $historico. "</td>";
            echo "<td width='10%' align='right' nowrap>  " . number_format( pg_fetch_result($res_avulso, $j, valor), 2, ',', '.') . "</td>";

            echo "<td width='10%' align='center' nowrap>" ;
            echo (pg_fetch_result($res_avulso, $j, automatico) == 't') ? "Sim" : "&nbsp;";
            echo "</td>";
            //hd 9482
            if($login_fabrica == 1 OR $login_fabrica == 10 or $login_fabrica==6 or $login_fabrica == 45 OR $login_fabrica == 85){
                echo (strlen($admin) > 0 ) ? "<td>". $admin ."</td>" : "<td>&nbsp;</td>";
            }
            if (($login_fabrica == 1 OR $login_fabrica == 10) AND strlen($os_sedex) > 0){
                echo "<td width='10%' align='center' nowrap >";
                echo "<a href='sedex_finalizada.php?os_sedex=" . $os_sedex . "' target='_blank'><img border='0' src='imagens/btn_consulta.gif' style='cursor: pointer;' alt='Consultar OS Sedex'></a>";
                echo "&nbsp;&nbsp;";
                echo "<INPUT TYPE=\"hidden\" NAME=\"lancamento\" value='$extrato_lancamento' >";
                if(strlen($data_envio_financeiro)>0 and $login_fabrica == 1){
                    echo "<span TITLE='Extrato não pode recusar porque já foi enviado para o financeiro no dia $data_envio_financeiro!'>Financeiro</span>";
                }else{
                    echo "<a href='extrato_consulta_os.php?extrato=$extrato&lancamento=" . $extrato_lancamento . "' ><img border='0' src='imagens/btn_recusar.gif' style='cursor: hand;' alt='Recusa OS SEDEX'></a>";
                }
                //echo "<a href='javascript:Excluir($extrato,$os_sedex)'><img src='imagens/btn_excluir.gif'></a>";
                echo "</td>";
            }elseif ($login_fabrica <> '124' && $login_fabrica <> 30 && !isset($novaTelaOs)) {
                if(!in_array($login_fabrica,array(80,85))){
                    echo "<td>&nbsp;</td>";
                }
            }//hd 9482


            //HD 205958: Um extrato pode ser modificado até o momento que for APROVADO pelo admin. Após aprovado
            //           não poderá mais ser modificado em hipótese alguma. Acertos deverão ser feitos com lançamento
            //           de extrato avuldo. Verifique as regras definidas neste HD antes de fazer exceções para as fábricas
            //           SERÁ LIBERADO AOS POUCOS, POIS OS PROGRAMAS NÃO ESTÃO PARAMETRIZADOS
            $libera_acesso_acoes = false;

            if (in_array($login_fabrica, $fabricas_acerto_extrato)) {

                if (strlen($aprovado) == 0) {
                    $libera_acesso_acoes = true;
                }

            } else if ( in_array($login_fabrica, array(6,11,45,172)) AND strlen($liberado) == 0) {//HD 205958: Condicional antigo - HD 227632: Habilitar excluir para a Lenoxx
                $libera_acesso_acoes = true;
            }

            if ($libera_acesso_acoes && $login_fabrica != 148) {
                echo "<td width='10%' align='center' nowrap>";
                echo "<INPUT TYPE=\"hidden\" NAME=\"lancamento\" value='$extrato_lancamento'>";
                echo "<a href='$PHP_SELF?acao=apagar&extrato=$extrato&xlancamento=" . $extrato_lancamento . "' ><img border='0' src='imagens/btn_recusar.gif' style='cursor: hand;' alt='Excluir Avulso' onclick='return(confirm(\"Excluir o lançamento selecionado?\"))'></a>";
                //echo "<a href='javascript:Excluir($extrato,$os_sedex)'><img src='imagens/btn_excluir.gif'></a>";
                echo "</td>";
            }

            echo "</tr>";

        }

        echo "</table>\n";
        echo "<br>\n";

    }

    if(in_array($login_fabrica, array(157))){

        $sql = "SELECT data_nf, nf_peca FROM tbl_extrato_pagamento WHERE extrato = {$extrato}";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){

            $data_nf = pg_fetch_result($res, 0, "data_nf");

            if(strlen($data_nf) > 0){
                list($ano, $mes, $dia) = explode("-", pg_fetch_result($res, 0, "data_nf"));
                $data_nf = $dia."/".$mes."/".$ano;
            }else{
                $data_nf = "Não informado";
            }

            $nf_peca = pg_fetch_result($res, 0, "nf_peca");
            $nf_peca = (strlen($nf_peca) > 0) ? $nf_peca : "Não informado";

            ?>

            <table class="tabela" cellpadding="1" cellpadding="1" border="0" width="700px" align="center">
                <tr class="titulo_tabela">
                    <td colspan="2">Resumo do Pagamento</td>
                </tr>
                <tr class="titulo_coluna">
                    <td width="50%">Nota Fiscal</td>
                    <td width="50%">Data da Nota</td>
                </tr>
                <tr>
                    <td align="center"><?php echo $nf_peca; ?></td>
                    <td align="center"><?php echo $data_nf; ?></td>
                </tr>
            </table>

            <?php

        }

    }

}
##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####

##### DEVOLUÇÃO DE BATERIAS - INÍCIO #####
if($login_fabrica == 1){
?>
    <table width="center" align="center" class="tabela">
        <caption class='titulo_tabela'>Devolução de Baterias</caption>
        <tr class='titulo_coluna'>
            <td>Nº Relatório</td>
            <td>Data</td>
            <td>Nº PAC</td>
            <td>Data(PAC)</td>
            <td>Item</td>
            <td>Produto</td>
            <td>Garantia</td>
            <td>Qtde</td>
            <td>Valor M.O</td>
        </tr>
    <?php
    $sql = "SELECT  tbl_residuo_solido.protocolo,
                    TO_CHAR(tbl_residuo_solido.digitacao::date,'DD/MM/YYYY') AS digitacao,
                    tbl_residuo_solido.numero_devolucao,
                    TO_CHAR(tbl_residuo_solido.data_aprova,'DD/MM/YYYY') AS data_aprova,
                    tbl_residuo_solido.qtde,
                    tbl_residuo_solido.total,
                    tbl_peca.referencia AS peca_referencia,
                    tbl_peca.descricao AS peca_descricao,
                    tbl_produto.referencia AS produto_referencia,
                    tbl_produto.descricao AS produto_descricao,
                    tbl_residuo_solido_item.troca_garantia
                FROM tbl_residuo_solido_item
                JOIN tbl_residuo_solido ON tbl_residuo_solido.residuo_solido = tbl_residuo_solido_item.residuo_solido AND tbl_residuo_solido.fabrica = $login_fabrica
                JOIN tbl_peca ON tbl_peca.peca = tbl_residuo_solido_item.peca AND tbl_peca.fabrica = $login_fabrica
                JOIN tbl_produto ON tbl_produto.produto = tbl_residuo_solido_item.produto AND tbl_produto.fabrica_i = $login_fabrica
                JOIN tbl_extrato_lancamento ON tbl_extrato_lancamento.extrato_lancamento = tbl_residuo_solido.extrato_lancamento AND tbl_extrato_lancamento.fabrica = $login_fabrica AND tbl_extrato_lancamento.extrato = $extrato
                WHERE tbl_residuo_solido.confirmar_envio IS NOT NULL";

    $res = pg_query($con,$sql);

    if(pg_numrows($res) > 0){
        $total_mao_obra = 0;
        for($i = 0; $i < pg_numrows($res); $i++){
            $protocolo          = pg_result($res,$i,protocolo);
            $digitacao          = pg_result($res,$i,digitacao);
            $numero_devolucao   = pg_result($res,$i,numero_devolucao);
            $data_aprova        = pg_result($res,$i,data_aprova);
            $qtde               = pg_result($res,$i,qtde);
            $peca_referencia    = pg_result($res,$i,peca_referencia);
            $peca_descricao     = pg_result($res,$i,peca_descricao);
            $produto_referencia = pg_result($res,$i,produto_referencia);
            $produto_descricao  = pg_result($res,$i,produto_descricao);
            $troca_garantia     = pg_result($res,$i,troca_garantia);
            $total_mao_obra     = pg_result($res,$i,total);

            $troca_garantia = ($troca_garantia == "t") ? "Sim" : "Não";

            $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
    ?>
            <tr bgcolor="<?php echo $cor;?>">
                <td><?php echo $protocolo;?></td>
                <td><?php echo $digitacao;?></td>
                <td><?php echo $numero_devolucao;?></td>
                <td><?php echo $data_aprova;?></td>
                <td><?php echo $peca_referencia." - ".$peca_descricao;?></td>
                <td><?php echo $produto_referencia;?></td>
                <td align='center'><?php echo $troca_garantia;?></td>
                <td align='center'>1</td>
                <td align='right'><?php echo number_format(2,2,',','.');?></td>
            </tr>
    <?php
        }

    ?>
            <tr class='titulo_coluna'>
                <td align='right' colspan='7'>TOTAL</td>
                <td align='center'><?php echo $qtde;?> </td>
                <td align='right'><?php echo number_format($total_mao_obra,2,',','.');?> </td>
            </tr>
        </table>
    <?php
    }
}
##### DEVOLUÇÃO DE BATERIAS - FIM #####

if ($login_fabrica == 6) {
    $wsql = "SELECT tbl_os_status.os_status,
                    tbl_os_status.os       ,
                    tbl_os.sua_os,
                    tbl_os_status.status_os  ,
                    tbl_os_status.data as data_order,
                    to_char(tbl_os_status.data,'DD/MM/YYYY') as data      ,
                    tbl_os_status.observacao ,
                    tbl_os_status.extrato    ,
                    tbl_os_status.os_sedex   ,
                    tbl_admin.login
            from tbl_os_status
            JOIN tbl_os on tbl_os.os = tbl_os_status.os
            join tbl_extrato on tbl_os_status.extrato =tbl_extrato.extrato
            JOIN tbl_admin on tbl_admin.admin = tbl_os_status.admin
            where tbl_extrato.extrato=$extrato
            and status_os=90
            order by sua_os, data_order;";
    $wres = pg_query($con,$wsql);
    if(pg_num_rows($wres)>0){
        echo "<BR><BR><table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
        echo "<tr class='menu_top'>\n";
        echo "<td colspan='4'>";
        echo "OBSERVAÇÕES FEITAS PELO FABRICANTE";
        echo "</td>\n";
        echo "</tr>\n";
        echo "<tr class='menu_top' style='background-color: $cor;'>\n";
        echo "<td class='menu_top'>OS</td>";
        echo "<td class='menu_top'>DATA</td>";
        echo "<td class='menu_top'>OBSERVAÇÃO</td>";
        echo "<td class='menu_top'>ADMIN</td>";
        echo "</tr>";
        for($i=0;pg_num_rows($wres)>$i;$i++){
            $sua_os     = pg_fetch_result($wres,$i,sua_os);
            $data       = pg_fetch_result($wres,$i,data);
            $observacao = pg_fetch_result($wres,$i,observacao);
            $login = pg_fetch_result($wres,$i,login);

            echo "<tr class='table_line' style='background-color: $cor;'>\n";
            echo "<td align='center'>$sua_os</td>";
            echo "<td align='center'>$data</td>";
            echo "<td align='left'>$observacao</td>";
            echo "<td align='center'>$login</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

if($login_fabrica==45){
    if (strlen($posto) >0){
        $sql = "SELECT  tbl_excecao_mobra.excecao_mobra ,
                    tbl_posto_fabrica.codigo_posto          ,
                    tbl_posto.cnpj                          ,
                    tbl_posto.nome                          ,
                    tbl_produto.produto                     ,
                    tbl_produto.referencia                  ,
                    tbl_produto.descricao                   ,
                    tbl_linha.nome              AS linha    ,
                    tbl_excecao_mobra.familia                ,
                    tbl_familia.descricao AS familia_descricao,
                    tbl_excecao_mobra.mao_de_obra           ,
                    tbl_excecao_mobra.adicional_mao_de_obra ,
                    tbl_excecao_mobra.percentual_mao_de_obra
                FROM    tbl_excecao_mobra
                JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_excecao_mobra.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                JOIN    tbl_posto            ON tbl_posto.posto           = tbl_posto_fabrica.posto
                LEFT JOIN tbl_produto        ON tbl_produto.produto       = tbl_excecao_mobra.produto
                LEFT JOIN tbl_linha AS l1    ON l1.linha                  = tbl_produto.linha
                AND l1.fabrica                = $login_fabrica
                LEFT JOIN tbl_familia AS ff    ON ff.familia               = tbl_produto.familia
                AND l1.fabrica                = $login_fabrica
                LEFT JOIN tbl_linha          ON tbl_linha.linha           = tbl_excecao_mobra.linha
                AND tbl_linha.fabrica         = $login_fabrica
                LEFT JOIN tbl_familia          ON tbl_familia.familia           = tbl_excecao_mobra.familia
                AND tbl_familia.fabrica         = $login_fabrica
                WHERE   tbl_excecao_mobra.fabrica = $login_fabrica
                AND     tbl_excecao_mobra.posto   = $posto
                ORDER BY tbl_posto.nome;";
        $res = pg_query ($con,$sql);

        if (pg_num_rows($res) > 0) {
            echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='tabela'>";
            echo "<tr>";
            echo "<td  class='titulo_tabela' align='center' colspan='6'>Exceção de Mão de Obra</td>";
            echo "</tr>";
            echo "<tr class='titulo_coluna'>";
            echo "<td align='center'>Linha</td>";
            echo "<td align='center'>Família</td>";
            echo "<td align='center'>Produto</td>";
            echo "<td align='center'>Mão-de-Obra</td>";
            echo "<td align='center'>Adicional</td>";
            echo "<td align='center'>Percentual</td>";
            echo "</tr>";

            for ($z = 0 ; $z < pg_num_rows($res) ; $z++){
                $cor = ($z % 2 == 0) ? '#F1F4FA' : '#E2E9F5';

                $excecao_mobra    = trim(pg_fetch_result($res,$z,excecao_mobra));
                $cnpj             = trim(pg_fetch_result($res,$z,cnpj));
                $cnpj             = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
                $codigo_posto     = trim(pg_fetch_result($res,$z,codigo_posto));
                $posto            = trim(pg_fetch_result($res,$z,nome));
                $produto          = trim(pg_fetch_result($res,$z,produto));
                $produto_descricao= trim(pg_fetch_result($res,$z,referencia)) ."-". trim(pg_fetch_result($res,$z,descricao));
                $linha            = trim(pg_fetch_result($res,$z,linha));
                $familia           = trim(pg_fetch_result($res,$z,familia));
                $familia_descricao = trim(pg_fetch_result($res,$z,familia_descricao));
                if (strlen($familia_descricao) == 0) $familia_descricao = "<i style='color: #959595'>TODAS</i>";
                $mobra            = trim(pg_fetch_result($res,$z,mao_de_obra));
                $adicional_mobra  = trim(pg_fetch_result($res,$z,adicional_mao_de_obra));
                $percentual_mobra = trim(pg_fetch_result($res,$z,percentual_mao_de_obra));

                if(strlen($linha) > 0){
                    $familia_descricao = "<i style='color: #959595;'>TODAS DA LINHA ESCOLHIDA</i>";
                    $produto_descricao = "<i style='color: #959595'>TODOS DA FAMILIA ESCOLHIDA</i>";
                }

                if(strlen($familia) > 0){
                    $linha             = "&nbsp;";
                    $produto_descricao = "<i style='color: #959595'>TODOS DA FAMILIA ESCOLHIDA</i>";
                }

                if(strlen($produto) > 0){
                    $linha             = "&nbsp;";
                    $familia           = "&nbsp;";
                }

                if(strlen($linha) == 0 AND strlen($familia) == 0 AND strlen($produto) == 0){
                    $linha             = "<i style='color: #959595;'>TODAS</i>";
                    $familia_descricao = "<i style='color: #959595;'>TODAS DA LINHA ESCOLHIDA</i>";
                    $produto_descricao = "<i style='color: #959595;'>TODOS DA FAMILIA ESCOLHIDA</i>";
                }

                echo "<tr>";

                echo "<td align='left' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>$linha</font></td>";
                echo "<td align='left' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>$familia_descricao</font></td>";
                echo "<td align='left' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>$produto_descricao</font></td>";
                echo "<td align='right' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>". number_format($mobra,2,",",".") ."</font></td>";
                echo "<td align='right' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>". number_format($adicional_mobra,2,",",".") ."</font></td>";
                echo "<td align='right' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>". number_format($percentual_mobra,2,",",".") ."</font></td>";

                echo "</tr>";
            }
            echo "</table>";
        }
    }
}

##### VERIFICA BAIXA MANUAL #####
$sql = "SELECT posicao_pagamento_extrato_automatico
        FROM tbl_fabrica
        WHERE fabrica = $login_fabrica;";
$res = pg_query($con,$sql);
$posicao_pagamento_extrato_automatico = pg_fetch_result($res,0,posicao_pagamento_extrato_automatico);

if ($posicao_pagamento_extrato_automatico == 'f' and $login_fabrica <> 1) {
?>

<?php if ($login_fabrica == 50 or $login_fabrica == 45 or $login_fabrica == 15) {?>
    <br>
    <TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='formulario'>
        <TR class='titulo_tabela'>
            <TD height='20' colspan='3'>Previsão de Pagamento</TD>
        </TR>
        <TR>
            <TD align='left' class='espaco'>Data de Chegada</TD>
            <TD align='left'>Data Prevista de Pagamento</TD>
            <TD align='left' >Ações</TD>
        </TR>
        <TR>
            <TD class='espaco'>
                <?php
                    echo "<INPUT TYPE='text' NAME='data_recebimento_nf'  size='12' maxlength='10' value='" . $data_recebimento_nf . "' class='frm' id='data_recebimento_nf'>";
                ?>
            </TD>
            <TD>
                <?php
                    echo "<INPUT TYPE='text' NAME='previsao_pagamento'  size='12' maxlength='10' value='" . $previsao_pagamento . "' class='frm' id='previsao_pagamento'>";
                ?>
            </TD>
            <TD>
                <?php
                    echo "<INPUT TYPE='submit' NAME='gravar_previsao' size='10' maxlength='20' value='Gravar' >";
                ?>
            </TD>
        </TR>
    </TABLE>
<?php }
if($login_fabrica == 30){ ?>
    <TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='formulario'>
        <TR>
            <TD align='left' class='espaco'>Data Recebimento NF</TD>
        </TR>
        <TR>
            <TD class='espaco'>
                <?php
                    if ($ja_baixado == false){
                        echo "<INPUT TYPE='text' NAME='data_recebimento_nf'  size='10' maxlength='11' value='" . $data_recebimento_nf . "' class='frm'>";
                    }else{
                        echo $data_recebimento_nf;
                    }
                ?>
            </TD>
        </TR>
    </TABLE>
<?php } ?>
<BR>
<? if($login_fabrica == 134){
    $pagamentos =  getPagamentosLancados($extrato);
    $dataBaixa = new DateTime($pagamentos[0]["baixa_extrato"]);


?>
    <table width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='formulario frm-pagamento'>
    <tr class='titulo_tabela'>
    <td colspan="9">Pagamentos Cadastrados <?=(strlen($pagamentos[0]["baixa_extrato"]) > 0) ? " - Data da Baixa: ".$dataBaixa->format("d/m/Y") : ""?></td>
    </tr>
    <tr class="titulo_coluna">
        <td>Valor Total (<?php echo $real ?>)</td>
        <td>Acréscimo</td>
        <td>Desconto(<?php echo $real ?>)</td>
        <td>Valor Líquido(<?php echo $real ?>)</td>
        <td>Data de Vencimento</td>
        <td>Nº Nota Fiscal</td>
        <td>Data de Pagamento</td>
        <td>Autorização Nº</td>
        <td>Observação</td>

    </tr>
<?  if($pagamentos != false){
            foreach($pagamentos as $pagamento){ ?>
        <tr class="table_line" style="text-align: center;">

            <td><?=number_format($pagamento["valor_total"], 2, ",", ".")?></td>
            <td><?=number_format($pagamento["acrescimo"],2, ",", ".")?></td>
            <td><?=number_format($pagamento["desconto"], 2, ",", ".")?></td>
            <td><?=number_format($pagamento["valor_liquido"],2, ",", ".")?></td>
            <td><?=$pagamento["data_vencimento"]?></td>
        <td><?=$pagamento["nf_autorizacao"]?></td>
        <td><?=$pagamento["data_pagamento"]?></td>
        <td><?=$pagamento["autorizacao_pagto"]?></td>
        <td><?=$pagamento["obs"]?></td>
    </tr>

    <? }
    }else{ ?>
        <tr><td colspan="9" style="text-align:center;">Nenhum Pagamento Lançado</td></tr>
<?  } ?>
    </table>
    <br/>
<? }

    if($login_fabrica == 134 && $ja_baixado){
        $display_none = "style='display:none'";
    }else{
        $display_none = "";
    }

    $mostrar_aprova_reprova = false;
    if(in_array($login_fabrica, [152,180,181,182]) and ($observacao_extrato_status != 'Aguardando Encerramento' and $observacao_extrato_status != 'encerramento') && 1==2){
        $display_none = "style='display:none'";
        if($observacao_extrato_status == 'Aguardando Aprovação de Nota Fiscal'){
            $mostrar_aprova_reprova = true;
        }
    }
?>

<?php if($mostrar_aprova_reprova == true and in_array($login_fabrica, [152,180,181,182])){ ?>
    
        <TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='formulario frm-pagamento'>
            <TR class='titulo_tabela'>
                <TD height='20' colspan='4' class='frm-title'>Aprovação de Nota Fiscal</TD>
            </TR>
            <tr><td colspan='4'>&nbsp;</td></tr>
            <tr>
                <TD align='center'  colspan='4' class='frm-title'>
                    <input type='radio' name='aprovar_reprovar' class='aprovar' value='sim'> Aprovar
                    <input type='radio' name='aprovar_reprovar' class='reprovar' value='nao'> Reprovar
                </TD>
            </tr>
            <tr><td colspan='4'>&nbsp;</td></tr>
            <tr id="linha_observacao" style='display: none'>
                <td width="100"></td>
                <td align="right">Observação:</td>
                <td colspan="2">
                    <textarea name="observacao_reprova" id="observacao_reprova" style="width:300px; height:50px;"></textarea>
                </td>
            </tr>
            <tr><td colspan='4'>&nbsp;</td></tr>
            <tr>
                <td colspan='4' align="center">
                    <button type='button' name='btn_acao_aprovacao_nf' id="btn_acao_aprovacao_nf">Gravar</button>
                </td>
            </tr>
            <tr><td colspan='4'>&nbsp;</td></tr>
        </TABLE>
    
<?php } 

if (in_array($login_fabrica, [152,180,181,182])) { /* HD - 6300570*/
    $display_none = "";
}
?>

<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='formulario frm-pagamento' <?=$display_none?>>
<?php 
    if ($login_fabrica == 151) { 
        $sql_grup = "SELECT codigo FROM tbl_extrato_agrupado WHERE extrato = $extrato";
        $res_grup = pg_query($con, $sql_grup);
        if (pg_num_rows($res_grup) > 0) {
?>
            <TR class='titulo_tabela'>
                <TD height='20' colspan='4' style='background-color: #ff0000 !important;' class='frm-title'><?=traduz("Extrato pertencente ao grupo")?> <?=strtoupper(pg_fetch_result($res_grup, 0, 'codigo'))?>. <?=traduz("Os dados de pagamento irão refletir neste grupo.")?></TD>
            </TR>
<?php
        }
    } 
?>
<TR class='titulo_tabela'>
    <TD height='20' colspan='4' class='frm-title'>Pagamento</TD>
</TR>
<tr><td colspan='4'>&nbsp;</td></tr>
<TR>
    <?php /*hd-1059101*/ if($login_fabrica == 42){ ?>
            <TD align='left' class='frm-title'>Nº NF M.O.</TD>
            <TD align='left' class='frm-title'>Valor NF M.O. (<?php echo $real ?>)</TD>
            <TD align='left' class='frm-title'>Acréscimo M.O. (<?php echo $real ?>)</TD>
            <TD align='left' class='frm-title'>Desconto M.O. (<?php echo $real ?>)</TD>


    <?php } else{ ?>

        <TD align='left' class='frm-title'>Valor Total (<?php echo $real ?>)</TD>
        <TD align='left' class='frm-title'><?=traduz('Acréscimo')?> (<?php echo $real ?>)</TD>
        <TD align='left' class='frm-title'><?=traduz('Desconto')?> (<?php echo $real ?>)</TD>
        <TD align='left' class='frm-title'><?=traduz('Valor Líquido')?> (<?php echo $real ?>)</TD>

    <?php } ?>
</TR>

<TR>
    <?php
    if($login_fabrica == 42){ ?>
            <TD class=''><?php
            //hd-1059101

            //HD 385125 - INICIO
            $sqlP = " SELECT tbl_extrato_pagamento.valor_total                                               ,
                            tbl_extrato_pagamento.acrescimo                                                 ,
                            tbl_extrato_pagamento.desconto                                                  ,
                            tbl_extrato_pagamento.valor_liquido                                             ,
                            tbl_extrato_pagamento.nf_autorizacao                                            ,
                            to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento  ,
                            tbl_extrato_pagamento.autorizacao_pagto                                         ,
                            tbl_extrato_pagamento.obs                                                       ,
                            tbl_extrato_pagamento.extrato_pagamento,
                            tbl_extrato_pagamento.valor_nf_peca,
                            tbl_extrato_pagamento.nf_peca,
                            tbl_extrato_pagamento.acrescimo_nf_peca,
                            tbl_extrato_pagamento.desconto_nf_peca,
                            tbl_extrato_pagamento.duplicata,
                            to_char(tbl_extrato_pagamento.data_bordero,'DD/MM/YYYY') as data_bordero,
                            tbl_extrato_pagamento.mes_referencia,
                            to_char (tbl_extrato_pagamento.data_aprovacao,'DD/MM/YYYY')  AS data_aprovacao,
                            to_char (tbl_extrato_pagamento.data_entrega_financeiro,'DD/MM/YYYY')  AS data_entrega_financeiro,
                            to_char(tbl_extrato_pagamento.data_recebimento_nf,'DD/MM/YYYY') as data_recebimento_nf,
                            tbl_extrato_pagamento.justificativa,
                            to_char (tbl_extrato_pagamento.previsao_pagamento,'DD/MM/YYYY') AS previsao_pagamento
                            FROM tbl_extrato_pagamento
                            WHERE extrato = $extrato ";

            if(strlen($extrato) > 0) {
                $resP = pg_query($con,$sqlP);

                if (pg_num_rows($resP) > 0) {
                    $extrato_pagamento         = pg_fetch_result ($resP,0,'extrato_pagamento') ;
                    $valor_total               = pg_fetch_result ($resP,0,'valor_total') ;
                    $acrescimo                 = pg_fetch_result ($resP,0,'acrescimo') ;
                    $desconto                  = pg_fetch_result ($resP,0,'desconto') ;
                    $valor_liquido             = pg_fetch_result ($resP,0,'valor_liquido') ;
                    $nf_autorizacao            = pg_fetch_result ($resP,0,'nf_autorizacao') ;

                    $data_pagamento            = pg_fetch_result ($resP,0,'data_pagamento') ;
                    $obs                       = pg_fetch_result ($resP,0,'obs') ;
                    $autorizacao_pagto         = pg_fetch_result ($resP,0,'autorizacao_pagto') ;


                    $vlr_nf_pecas               = pg_fetch_result ($resP,0,'valor_nf_peca') ;

                    $nro_nf_pecas               = pg_fetch_result ($resP,0,'nf_peca') ;
                    $acrescimo_pecas            = pg_fetch_result ($resP,0,'acrescimo_nf_peca') ;
                    $desconto_pecas             = pg_fetch_result ($resP,0,'desconto_nf_peca') ;
                    $bordero                    = pg_fetch_result ($resP,0,'duplicata') ;
                    $data_bordero               = pg_fetch_result ($resP,0,'data_bordero') ;
                    $mes_referencia             = pg_fetch_result ($resP,0,'mes_referencia') ;
                    $data_envio_aprovacao       = pg_fetch_result ($resP,0,'data_recebimento_nf') ;
                    $data_aprovacao             = pg_fetch_result ($resP,0,'data_aprovacao') ;
                    $data_entrega_financeiro    = pg_fetch_result ($resP,0,'data_entrega_financeiro') ;
                    $justificativa              = pg_fetch_result ($resP,0,'justificativa') ;
                    $previsao_pagamento         = pg_fetch_result ($resP,0,'previsao_pagamento') ;

                }
            }
            //HD 385125 - FIM
            ?>

            <?php if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='nf_autorizacao'  size='12' maxlength='10' value='" . $nf_autorizacao . "' class='frm'>";
                    else                      echo $nf_autorizacao; ?>
        </TD>
        <td> <?php  if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='valor_total'  id=''valor_total' size='12' maxlength='10' value='" . $valor_total . "' class='frm'>";
            else                      echo number_format($valor_total,2,',','.');?>
            </TD>
            <TD><?php
            if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='acrescimo'  size='12' maxlength='10' value='" . $acrescimo . "' class='frm'>";
            else                      echo number_format($acrescimo,2,',','.');?>
            </TD>
            <TD>
        <?php
            if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='desconto'  size='12' maxlength='10' value='" . $desconto . "' class='frm'>";
            else                      echo number_format($desconto,2,',','.');
        ?>
            </TD>
    <?php } else { ?>
            <TD class=''><?php

            //HD 385125 - INICIO
             $sqlP = " SELECT tbl_extrato_pagamento.valor_total                                             ,
                            tbl_extrato_pagamento.acrescimo                                                 ,
                            tbl_extrato_pagamento.desconto                                                  ,
                            tbl_extrato_pagamento.valor_liquido                                             ,
                            tbl_extrato_pagamento.nf_autorizacao                                            ,
                            to_char (tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
                            to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento  ,
                            tbl_extrato_pagamento.autorizacao_pagto                                         ,
                            tbl_extrato_pagamento.data_nf                                                   ,
                            tbl_extrato_pagamento.serie_nf                                                  ,
                            tbl_extrato_pagamento.obs                                                       ,
                            tbl_extrato_pagamento.extrato_pagamento,
                            tbl_extrato_pagamento.baixa_extrato
                            FROM tbl_extrato_pagamento
                            WHERE extrato = $extrato ";

            if(strlen($extrato) > 0) {
                $resP = pg_query($con,$sqlP);

                if (pg_num_rows($resP) > 0) {

                    $extrato_pagamento = pg_fetch_result ($resP,0,'extrato_pagamento') ;
                    $valor_total       = pg_fetch_result ($resP,0,'valor_total') ;
                    $acrescimo         = pg_fetch_result ($resP,0,'acrescimo') ;
                    $desconto          = pg_fetch_result ($resP,0,'desconto') ;
                    $valor_liquido     = pg_fetch_result ($resP,0,'valor_liquido') ;
                    $nf_autorizacao    = pg_fetch_result ($resP,0,'nf_autorizacao') ;
                    $data_vencimento   = pg_fetch_result ($resP,0,'data_vencimento') ;
                    $data_pagamento    = pg_fetch_result ($resP,0,'data_pagamento') ;
                    $obs               = pg_fetch_result ($resP,0,'obs') ;
                    $autorizacao_pagto = pg_fetch_result ($resP,0,'autorizacao_pagto') ;
                    $data_nf           = pg_fetch_result ($resP,0,'data_nf') ;
                    $baixa_extrato     = mostra_data_hora(pg_fetch_result ($resP,0,'baixa_extrato'));
                    $serie_nf          = (strlen(pg_fetch_result ($resP,0,'serie_nf')) > 0) ? pg_fetch_result ($resP,0,'serie_nf') : "Não informado";

                    if(strlen($data_nf) > 0){
                        list($ano, $mes, $dia) = explode("-", $data_nf);
                        $data_nf = $dia."/".$mes."/".$ano;
                    }else{
                        $data_nf = "Não informado";
                    }

                }
            }
                   if($login_fabrica == 134){


                       $extrato_pagamento = "";
                       $valor_total       = "";
                       $acrescimo         = "";
                       $desconto          = "";
                       $valor_liquido     = "";
                       $nf_autorizacao    = "";
                       $data_vencimento   = "";
                       $data_pagamento    = "";
                       $obs               = "";
                       $autorizacao_pagto = "";


                   }
            //HD 385125 - FIM
            if ($login_fabrica==45 or $login_fabrica==50) echo "<input type='hidden' name='extrato_pagamento' value='$extrato_pagamento'>";

                if (in_array($login_fabrica, array(30)))
                    $valor_total += $total_taxa_entrega;

                if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='valor_total'  id=''valor_total' size='12' maxlength='10' value='" . $valor_total . "' class='frm'>";
                else                      echo "<INPUT TYPE='text' NAME='valor_total'  id=''valor_total' size='12' maxlength='10' value='" . number_format($valor_total,2,',','.') . "' class='frm'>";?>

                </TD>
                <TD><?php
                if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='acrescimo'  size='12' maxlength='10' value='" . $acrescimo . "' class='frm'>";
                else                      echo "<INPUT TYPE='text' NAME='acrescimo'  size='12' maxlength='10' value='" . number_format($acrescimo,2,',','.') . "' class='frm'>";?>
                </TD>
                <TD>
                <?php
                if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='desconto'  size='12' maxlength='10' value='" . $desconto . "' class='frm'>";
                else                      echo "<INPUT TYPE='text' NAME='desconto'  size='12' maxlength='10' value='" . number_format($desconto,2,',','.') . "' class='frm'>";
                ?>
                </TD>
                <TD>
                <?php
                if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='valor_liquido'  size='10' maxlength='10' value='" . $valor_liquido . "' class='frm'>";
                else                      echo "<INPUT TYPE='text' NAME='valor_liquido'  size='10' maxlength='10' value='" . number_format($valor_liquido,2,',','.') . "' class='frm'>";
                ?>
                </TD>
            </TR>
            <?php
    } ?>
<TR>
    <?php if ($login_fabrica == 42) { ?>
        <TD align='left' class='frm-title'>Nº NF Peça</TD>
        <TD align='left' class='frm-title'>Valor NF Peça</TD>
        <TD align='left' class='frm-title'>Acréscimo Peças (<?php echo $real ?>)</TD>
        <TD align='left' class='frm-title'>Desconto Peças (<?php echo $real ?>)</TD>

    <?php } else {   
        if (!in_array($login_fabrica, [169,170])) { ?>
            <TD align='left' class='frm-title'> <?php echo (in_array($login_fabrica, array(157))) ? traduz("Data de Recebimento") : traduz("Data de Vencimento"); ?> </TD>
        <?php } else { ?>
            <td align="left"><?=traduz('NF Cadastrada');?></td>
        <?php } ?>
        <TD align='left' class='frm-title'><?=traduz('Nº Nota Fiscal');?></TD>

        <TD align='left' class='frm-title'>
        <?php if($login_fabrica==43){//HD 84828
                echo traduz("Data Prevista de Pagamento");
            }else if($login_fabrica == 157){
                echo traduz("Previsão de pagamento");
            }else{
                echo ($login_fabrica == 178) ? traduz("Data de Envio Financeiro") : traduz("Data de Pagamento");
            }
        ?>
        </TD>
        <TD align='left' class='frm-title'><?=traduz('Autorização Nº')?></TD>
    <?php } ?>
</TR>

<TR>
    <?php /*hd-1059101*/if ($login_fabrica == 42 ) { ?>
        <TD class=''>
        <?php
            if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='nro_nf_pecas'  size='12' maxlength='10' value='" . $nro_nf_pecas . "' class='frm'>";
            else                      echo $nro_nf_pecas;
        ?>
            </TD>
            <TD >
        <?php
            if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='vlr_nf_pecas'  size='10' maxlength='20' value='" . $vlr_nf_pecas . "' class='frm'>";
            else                      echo number_format($vlr_nf_pecas,2,',','.');
        ?>
            </TD>
            <TD >
        <?php
            if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='acrescimo_pecas' size='10' maxlength='20' value='" . $acrescimo_pecas . "' class='frm'>";
            else                      echo number_format($acrescimo_pecas,2,',','.');
        ?>
            </TD>
            <TD >
        <?php
            if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='desconto_pecas'  size='12' maxlength='10' id='desconto_pecas' value='" . $desconto_pecas . "' class='frm'>";
            else                      echo number_format($desconto_pecas,2,',','.');
        ?>
            </TD>
    <?php }else{
        ?>
        
        <?php
        if (!in_array($login_fabrica, [169, 170])) {
            echo "<TD class=''><INPUT TYPE='text' NAME='data_vencimento' id='data_vencimento'  size='12' maxlength='10' value='" . $data_vencimento . "' class='frm'></TD>";
        } else {
            echo "<TD class=''><strong> {$baixa_extrato}</strong></TD>";
        }

        ?>
            <TD >
        <?php
            echo "<INPUT TYPE='text' NAME='nf_autorizacao'  size='10' maxlength='20' value='" . $nf_autorizacao . "' class='frm'>";

        ?>
            </TD>
            <TD >
        <?php
            echo "<INPUT TYPE='text' NAME='data_pagamento'  size='12' maxlength='10' id='data_pagamento' value='" . $data_pagamento . "' class='frm'>";

        ?>
            </TD>
            <TD >
        <?php
            echo "<INPUT TYPE='text' NAME='autorizacao_pagto' size='10' maxlength='20' value='" . $autorizacao_pagto . "' class='frm'>";

        ?>
            </TD>
    <?php

    } ?>
</TR>

<?php if (in_array($login_fabrica, [169,170])) { ?>
    <tr>
        <td align='left' class='frm-title'>Data Nota Fiscal</td>
        <td align='left' class='frm-title'>Data Vencimento</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>
            <input type='text' name='data_nf' size='12' maxlength='10' id='data_nf' value="<?= $data_nf; ?>" class='frm' />
        </td>
        <td>
            <input type='text' name='data_vencimento' id='data_vencimento' size='12' maxlength='10' value="<?= $data_vencimento ?>" class='frm'>
        </td>
        <td></td>
        <td></td>
    </tr>
<?php }

if ($login_fabrica == 42){ ?>
    <tr>
        <td align='left' class='frm-title'>Data de Pagamento </td>
        <td align='left' class='frm-title'>Previsão de Pagamento </td>
        <td align='left' class='frm-title'>Borderô </td>
        <td align='left' class='frm-title'>Data Borderô </td>

    </tr>
    <tr>
        <td align='left' class=''>
        <?php
            if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_pagamento'  size='12' maxlength='10' id='data_pagamento' value='" . $data_pagamento . "' class='frm'>";
            else                      echo $data_pagamento;
        ?>
        </td>
        <td>
        <?php
            if ($ja_baixado == false)
                echo "<INPUT TYPE='text' NAME='previsao_pagamento'  size='12' maxlength='10' id='previsao_pagamento' value='" . $previsao_pagamento . "' class='frm'>";
            else
                echo $previsao_pagamento;
        ?>
        </td>
        <td >
            <?php if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='bordero' size='15' maxlength='20' value='" . $bordero . "' class='frm'>";
                  else                      echo  $bordero;
            ?>

        </td>
        <td align='left'>
            <?php if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_bordero' size='15' maxlength='20' value='" . $data_bordero . "' class='frm'>";
                  else                      echo $data_bordero;
            ?>
        </td>
    </tr>

    <tr>
        <td align='left' class='frm-title'>Mês Referência</td>
        <td align='left' class='frm-title'>Data Envio Aprovação</td>
        <td align='left' class='frm-title'>Data Aprovação </td>
        <td align='left' class='frm-title'>Data Entrega Financeiro </td>

    </tr>

    <tr>
        <td align='left'>
            <?php if ($ja_baixado == false){
                     echo '<select name="mes_referencia" id="mes_referencia" style="width:120px; font-size:10px" class="frm">',
                                         '<option value="" '  . ($mes_referencia  == ""  ? " selected " : '') . '></option>',
                                         '<option value="1" '  . ($mes_referencia  == "1"  ? " selected " : '') . '>Janeiro</option>',
                                         '<option value="2" '  . ($mes_referencia  == "2"  ? " selected " : '') . '>Fevereiro</option>',
                                         '<option value="3" '  . ($mes_referencia  == "3"  ? " selected " : '') . '>Março</option>',
                                         '<option value="4" '  . ($mes_referencia  == "4"  ? " selected " : '') . '>Abril</option>',
                                         '<option value="5" '  . ($mes_referencia  == "5"  ? " selected " : '') . '>Maio</option>',
                                         '<option value="6" '  . ($mes_referencia  == "6"  ? " selected " : '') . '>Junho</option>',
                                         '<option value="7" '  . ($mes_referencia  == "7"  ? " selected " : '') . '>Julho</option>',
                                         '<option value="8" '  . ($mes_referencia  == "8"  ? " selected " : '') . '>Agosto</option>',
                                         '<option value="9" '  . ($mes_referencia  == "9"  ? " selected " : '') . '>Setembro</option>',
                                         '<option value="10" ' . ($mes_referencia  == "10" ? " selected " : '') . '>Outubro</option>',
                                         '<option value="11" ' . ($mes_referencia  == "11" ? " selected " : '') . '>Novembro</option>',
                                         '<option value="12" ' . ($mes_referencia  == "12" ? " selected " : '') . '>Dezembro</option>',
                            '</select>';

                  }else {

                                    switch( $mes_referencia){
                                         case '1':
                                            echo "Janeiro";
                                         break;
                                         case '2':
                                            echo "Fevereiro";
                                         break;
                                         case '3':
                                            echo "Março";
                                         break;
                                         case '4':
                                            echo "Abril";
                                         break;
                                         case '5':
                                            echo "Maio";
                                         break;
                                         case '6':
                                            echo "Junho";
                                         break;
                                         case '7':
                                            echo "Julho";
                                         break;
                                         case '8':
                                            echo "Agosto";
                                         break;
                                         case '9':
                                            echo "Setembro";
                                         break;
                                         case '10':
                                            echo "Outubro";
                                         break;
                                         case '11':
                                            echo "Novembro";
                                         break;
                                         case '12':
                                            echo "Dezembro";
                                         break;
                                    }
                  }
            ?>
        </td>
        <td align='left' class=''>
            <?php if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_envio_aprovacao' size='15' maxlength='20' value='" . $data_envio_aprovacao . "' class='frm'>";
                  else                      echo $data_envio_aprovacao;
            ?>
        </td>
        <td align='left' >
            <?php if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_aprovacao' size='15' maxlength='20' value='" . $data_aprovacao . "' class='frm'>";
                  else                      echo $data_aprovacao;
            ?>

        </td>
        <td align='left'>
            <?php if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_entrega_financeiro' size='15' maxlength='20' value='" . $data_entrega_financeiro . "' class='frm'>";
                  else                      echo $data_entrega_financeiro;
            ?>
        </td>

    </tr>
    <tr>
        <td colspan='4' class='frm-title'>Justificativa</td>
    </tr>
    <tr>
        <td colspan='4' class=''>
            <?php if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='justificativa' style='width: 93.1%;' maxlength='255' value='" . $justificativa . "' class='frm'>";
                  else                      echo $justificativa;
            ?>
        </td>
    </tr>
<?php } ?>
<?php if($login_fabrica == 30){ ?>
<TR>
    <TD class='frm-title'>Data Recebimento NF</TD>
</TR>
<TR>
    <TD class=''>
<?php
    if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_recebimento_nf'  size='10' maxlength='11' value='" . $data_recebimento_nf . "' class='frm'>";
    else                      echo $data_recebimento_nf;
?>
    </TD>
</TR>
<?php } ?>
<?php
if(in_array($login_fabrica, array(101,151))){
    $colspanX = "3";
    if ($login_fabrica == 101) {
      $colspanX = "";
    }
?>
<tr>
    <td class='frm-title'>Série</td>
    <td colspan="<?php echo $colspanX;?>">Data de Emissão</td>
    <?php
        if ($login_fabrica == 101) {
            echo '<td align="left" class="frm-title">Previsão de Pagamento</td>';
        }
    ?>
</tr>
<tr>
    <td class='frm-title'><?php echo $serie_nf; ?></td>
    <td colspan="<?php echo $colspanX;?>"><?php echo $data_nf; ?></td>
    <?php
    if ($login_fabrica == 101) {
        echo '<td align="left" class="frm-title">';
        if ($ja_baixado == true) {
            echo $previsao_pagamento;
        }
        echo '</td>';
    }
    ?>
</tr>
<?php
}
?>
<TR>
    <TD  colspan='4' class='frm-title'><?=traduz('Observação')?></TD>
</TR>
<TR>
    <TD colspan='4' class=''>
<?php
        echo "<INPUT TYPE='text' NAME='obs'  style='width: 895px;' maxlength='255' value='" . $obs . "' class='frm'>";


?>
    </TD>
</TR>
<?php
if (in_array($login_fabrica, [169,170])) { ?>
    <tr>
        <td colspan="4" align='left' class='frm-title' style="text-align: center;">Nota Fiscal</td>
    </tr>
    <tr>
        <td colspan="4" style="padding-left:8px;text-align: center;">
            <?php
            unset($amazonTC, $anexos, $types);
            $amazonTC = new TDocs($con, $login_fabrica);
            $amazonTC->setContext("extrato", "nf_autorizacao");
            $anexo = array();

            $anexo["nome"] = "nf_autorizacao_{$extrato}_{$login_fabrica}_nota_fiscal_pdf";
            $anexo["url"] = $amazonTC->getDocumentsByName($anexo["nome"],null, $extrato)->url;
            if (strlen($anexo["url"]) > 0) { ?>
                <a href="<?= $anexo['url']; ?>" target="_blank"><img height="90" src="imagens/pdf_transparente.jpg" /></a>
            <?php } ?>
        </td>
    </tr>
<?php
} ?>

<tr><td colspan='4'>&nbsp;</td></tr>
</TABLE>
<br />
<?php
if (in_array($login_fabrica, [169,170])) { 

    $sqlHistorico = "SELECT CASE
                                WHEN pendente THEN 'Nota Fiscal Reprovada'
                                WHEN obs = 'NF Enviada' THEN 'NF Enviada'
                                ELSE 'Nota Fiscal Aprovada'
                            END as acao,
                            data,
                            nome_completo,
                            obs,
                            tbl_extrato_status.parametros_adicionais
                     FROM tbl_extrato_status
                     LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_extrato_status.admin_conferiu
                     AND tbl_admin.fabrica = {$login_fabrica}
                     WHERE extrato = {$extrato}";
    $resHistorico = pg_query($con, $sqlHistorico);

    ?>
    <table class="tabela" width="750" cellspacing="1" cellpadding="1" border="0" align="center">
        <thead>
            <tr class="titulo_tabela">
                <th colspan="5">Histórico de Ações</th>
            </tr>
            <tr class="titulo_coluna">
                <th>Ação</th>
                <th>Data</th>
                <th>Nº Nota Fiscal</th>
                <th>Mensagem</th>
                <th>Admin</th> 
            </tr>
        </thead>
        <tbody>
            <?php
            if (pg_num_rows($resHistorico) == 0) { ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Nenhum resultado encontrado</td>
                </tr>
            <?php
            }

            while ($dados = pg_fetch_object($resHistorico)) { 

                $arrParametrosAdicionais = json_decode($dados->parametros_adicionais, true);

                ?>
                <tr>
                    <td style="text-align: center;"><?= $dados->acao ?></td>
                    <td style="text-align: center;"><?= mostra_data_hora($dados->data) ?></td>
                    <td style="text-align: center;"><?= $arrParametrosAdicionais["notaFiscal"] ?></td>
                    <td><?= $dados->obs ?></td>
                    <td><?= $dados->nome_completo ?></td>
                </tr>
            <?php
            } ?>
        </tbody>
    </table>
<?php
}
?>
<BR>

<?php
if ($ja_baixado == false ){
    echo "<input type='hidden' name='data_inicial' value='$data_inicial'>";
    echo "<input type='hidden' name='data_final' value='$data_final'>";
    echo "<input type='hidden' name='cnpj' value='$cnpj'>";
    echo "<input type='hidden' name='razao' value='$razao'>";

    $liberar_extrato = '';
    if($login_fabrica == 50){
        $sqlOsNaoBaixada = "SELECT os FROM tbl_os_extra WHERE extrato = $extrato AND baixada IS NULL";
        $resOsNaoBaixada = pg_query($con,$sqlOsNaoBaixada);
        $liberar_extrato = (pg_numrows($resOsNaoBaixada) > 0) ? 'nao' : '';
    }
    $style = (empty($liberar_extrato)) ? "" : "style='display:none'";

    echo"<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='0' $style id='baixar_extrato' $display_none >";
    if($login_fabrica == 134){ ?>
        <tr >
            <td align='center' width="70px">
            <input type='button' value='Gravar Pagamento' onclick="javascript: if (document.frm_extrato_os.btn_acao.value == '' ) { if (window.opener){window.opener.refreshTela(5000);} document.frm_extrato_os.btn_acao.value='gravar_pagamento' ; document.frm_extrato_os.submit() } else { alert ('Aguarde submissão') }" ALT='Baixar' border='0' style='cursor:pointer;'>
            </td>

        </tr>
    <?
    }
    echo"<TR>";
    echo"   <TD ALIGN='center' ><input type='button' value='Baixar' onclick=\"javascript: if (document.frm_extrato_os.btn_acao.value == '' ) { if (window.opener){window.opener.refreshTela(5000);} document.frm_extrato_os.btn_acao.value='baixar' ; document.frm_extrato_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Baixar' border='0' style='cursor:pointer;'></TD>";

    echo"</TR>";

    echo"</TABLE>";
}else{
    //HD 18066
    //Fabrica 24 HD 22758
    if($login_fabrica == 24 OR $login_admin==903){
        echo "<input type='hidden' name='extrato' value='$extrato'>";
        echo"<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='0'>";
        echo"<TR>";
        echo "<td align='center'><input type='button' name='excluir_baixa' value='Excluir Baixa' onclick=\"javascript: if (document.frm_extrato_os.btn_acao.value == '' ) {
            if (window.opener){window.opener.refreshTela(5000);} document.frm_extrato_os.btn_acao.value='excluir_baixa' ; document.frm_extrato_os.submit() } else { alert ('Aguarde submissão') }\"></td>";
        echo"</TR>";
        echo"</TABLE>";
    }
        echo "<input type='hidden' name='data_inicial' value='$data_inicial'>";
        echo "<input type='hidden' name='data_final' value='$data_final'>";
        echo "<input type='hidden' name='cnpj' value='$cnpj'>";
        echo "<input type='hidden' name='razao' value='$razao'>";

        $liberar_extrato = '';
        $style = (empty($liberar_extrato)) ? "" : "style='display:none'";

        echo"<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='0' $style id='baixar_extrato'>";

        echo"<TR>";
    if (!in_array($login_fabrica, [169,170])) {
            echo"<TD ALIGN='center' ><input type='button' value='Atualizar' onclick=\"javascript: if (document.frm_extrato_os.btn_acao.value == '' ) { if (window.opener){window.opener.refreshTela(5000);} document.frm_extrato_os.btn_acao.value='baixar' ; document.frm_extrato_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Baixar' border='0' style='cursor:pointer;'></TD>";
        }
        echo"</TR>";

        echo"</TABLE>";
}

} // fecha verificação se fábrica usa baixa manual

if ( in_array($login_fabrica, array(11,172)) ) { //24982 10/7/2008
        $sql = "SELECT  aprovado,
                        liberado
                    FROM tbl_extrato
                    WHERE extrato = $extrato
                    AND   fabrica = $login_fabrica
                    AND   aprovado IS NULL
                    AND   liberado IS NULL";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res)==1){
    ?>
        <BR>
        <TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='0' $style id='baixar_extrato'>
            <TR>
                <TD ALIGN="center">
                    <input type='button' value='Cancela' style="cursor:pointer" onclick="javascript:
                        if (document.frm_extrato_os.btn_acao.value == '' ) {
                        if(confirm('Deseja realmente cancelar o Extrato?') == true) {
                            document.frm_extrato_os.btn_acao.value='cancelar_extrato'; document.frm_extrato_os.submit();
                        }else{
                            return;
                        };
                    }" ALT="Cancelar Extrato" border='0'>

                </TD>
            </TR>
        </TABLE>

<?php   }
}?>

</FORM>
</center>
<br>

<center>

    <?php
    if($login_fabrica == 6){
        echo "<button type='button' onclick='voltarManutencao(\"$_GET[extrato]\");'>Voltar para Manutenção</button> &nbsp; &nbsp; ";
    }
    if(in_array($login_fabrica, array(142))){
        echo "<input type='button' value='Imprimir' onclick=\"javascript: window.open('../print_extrato.php?extrato=$extrato')\"";
    }else{
    ?>

    <input type='button' value='Imprimir' onclick="javascript: window.open('extrato_consulta_os_print.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=no,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir' border='0' style='cursor:pointer;' />
    <?php
    }
        if($login_fabrica == 42){

    ?>
        <input type='button' value='Detalhes' onclick="javascript: window.open('os_extrato_detalhe_print_pecas.php?extrato=<? echo $extrato; ?>&posto=<?echo $posto?>', '_blank')" border='0' style='cursor:pointer;' />
    <?php
        }

        if($login_fabrica == 91){
    ?>
        <input type='button' value='Peças a devolver' onclick="javascript: window.open('os_extrato_pecas_devolver.php?extrato=<? echo $extrato; ?>&posto=<?echo $posto?>', '_blank')" border='0' style='cursor:pointer;' />

    <?php
        }


        if($login_fabrica == 72){
            echo "&nbsp;&nbsp;<input type='button' value=' Imprimir SEC ' onclick=\"window.open('print_sec.php?extrato=$extrato', '_blank'); \" alt='Imprimir SEC' border='0' style='cursor:pointer;' />";
        }
    ?>

<?php # HD 36258
    if ($login_fabrica == 50 OR $login_fabrica == 15) {
?>
        <input type='button' value='Imprimir OS Selecionada' onclick="javascript: imprimirSelecionados()" ALT='Imprimir' border='0' style='cursor:pointer;'>
<?php }

if ($login_fabrica == 1) { ?>
    <a href="os_extrato_print_blackedecker.php?extrato=<? echo $extrato; ?>" target="_blank"> <input type='button' value='Imprimir Simplificado' border='0' style='cursor:pointer;'></a>
    <a href="os_extrato_detalhe_print_blackedecker.php?extrato=<? echo $extrato; ?>" target="_blank"><input type='button' value='Imprimir Detalhado' border='0' style='cursor:pointer;'></a>
    <?php if($coloca_botao == "sim"){ ?>
        <img src='imagens/btn_pecas_negativas.gif' onclick="javascript: window.open('os_extrato_detalhe_pecas_negativas.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=yes,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir Detalhado' border='0' style='cursor:pointer;'>
    <?php } ?>
<?php } ?>
<br><br>
<?php
if (!$_GET['relatorio']){?>
    <!-- <input type='button' value='Voltar' border='0' onclick="javascript: history.back(-1);" alt='Voltar' style='cursor: pointer;'> -->
<?php }

if ($telecontrol_distrib) {
    include_once S3CLASS;
    $s3_extrato = new AmazonTC("extrato", (int) $login_fabrica);
    $nota_fiscal_servico = $s3_extrato->getObjectList($extrato."-nota_fiscal_servico.");
    if(count($nota_fiscal_servico) > 0){
        $nota_fiscal_servico = basename($nota_fiscal_servico[0]);
        $nf_hidden = $nota_fiscal_servico;
        ?>
    <table width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='formulario frm-pagamento'>
        <tr class='titulo_tabela'>
            <td>Nota Fiscal de Serviço</td>
        </tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
            <td>
            <?php
            $ext = preg_replace("/.+\./", "", $nota_fiscal_servico);
            if(!in_array($ext, array("pdf", "doc", "docx"))){
                $thumb_nota_fiscal_servico = $s3_extrato->getLink("thumb_".$nota_fiscal_servico);
            }else{
                switch ($ext) {
                    case 'pdf':
                        $thumb_nota_fiscal_servico = 'imagens/pdf_icone.png';
                        break;
                    case 'doc':
                    case 'docx':
                        $thumb_nota_fiscal_servico = 'imagens/docx_icone.png';
                        break;
                }
            }
            $nota_fiscal_servico = $s3_extrato->getLink($nota_fiscal_servico);
            ?>
                <center>
                    <a href="<?=$nota_fiscal_servico?>"><img src="<?=$thumb_nota_fiscal_servico?>"/></a>
                </center>
            </td>
        </tr>
        <?php 
            $tem_lote = " SELECT tbl_distrib_lote.distrib_lote
                          FROM tbl_distrib_lote_os
                          JOIN tbl_os_extra USING(os)
                          JOIN tbl_distrib_lote using(distrib_lote)
                          WHERE tbl_os_extra.extrato = {$extrato}";
            $res_lote = pg_query($con, $tem_lote);
            if (pg_num_rows($res_lote) == 0) {
            ?>
                <tr>
                    <td>
                        <center>
                            <button type="button" id="btn_excluir_nf_lote">Excluir</button>
                            <input type="hidden" name="nf_hidden" id="nf_hidden" value="<?=$nf_hidden?>">
                        </center>
                    </td>
                </tr>
            <?php    
            }
            ?>
    </table>
    <?php
    }
}  

if(in_array($login_fabrica, array(152,180,181,182))){
    $sqlNF90dias = "SELECT extrato, posto, data_geracao, fabrica
                        FROM tbl_extrato
                        WHERE fabrica = {$login_fabrica}                        
                        AND data_geracao < CURRENT_DATE -90";

    //die(nl2br($sqlNF90dias));
    $resNF90Ddias = pg_query($con, $sqlNF90dias);
    $contador90dias = pg_num_rows($resNF90Ddias);

    /*if($contador90dias > 0){
        $hidden_button = true;             
        $msg90dias = "<br><font style='background-color:#FF0000; font:bold 16px arial; color:#FFFFFF; text-align:center;'>&nbsp;" . utf8_encode(traduz('anexos.bloqueados,.extrato.a.mais.de.90.dias')) . "&nbsp;</font><br><br>";

    } else {
        $hidden_button = false;
    }*/

    $tempUniqueId = $extrato;
    $boxUploader = array(
        "titulo_tabela" => "Anexar Nota Fiscal de Serviço",
        "div_id" => "div_anexos",
        "prepend" => $anexo_prepend,
        "context" => "extrato",
        "unique_id" => $tempUniqueId,
        "hash_temp" => $anexoNoHash,
        "bootstrap" => false,
        "hidden_button" => $hidden_button
    );

    echo $msg90dias;
    echo "<div style='width: 40%;'>";
    include "box_uploader.php";
    echo "</div>";
}
?>
</center>
<br>
<div>
    <a rel='shadowbox' href="relatorio_log_alteracao_new.php?parametro=tbl_extrato_consulta_os&id=<?php echo $extrato; ?>" name="btnAuditorLog">Visualizar Log Auditor</a>
</div>
<br>
<?php include "rodape.php"; ?>
