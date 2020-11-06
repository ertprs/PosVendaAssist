<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "anexaNFDevolucao_inc.php";
if ($login_e_distribuidor == 't') {
    if ($login_posto <> 4311 AND $login_posto <>725){
        header ("Location: new_extrato_distribuidor.php");
        exit;
    }
}

$sql = "SELECT  faturamento,
        extrato_devolucao,
        nota_fiscal,
        distribuidor,
        NULL as produto_acabado,
        NULL as devolucao_obrigatoria
    FROM tbl_faturamento
    WHERE posto IN (13996,4311)
    AND distribuidor=$login_posto
    AND fabrica=$login_fabrica
    AND extrato_devolucao=$extrato
    ORDER BY faturamento ASC";
$res = pg_query ($con,$sql);
$jah_digitado=pg_num_rows ($res);
if ($login_posto <> 4311){
    if ($jah_digitado==0){
        $sql = "SELECT * FROM tbl_extrato_devolucao WHERE extrato = $extrato AND nota_fiscal IS NULL";
        $res = pg_query ($con,$sql);
        if (pg_num_rows ($res) > 0) {
            header ("Location: new_extrato_posto.php?msg_erro=405");
            exit;
        }
    }
}
$msg_erro = "";

$layout_menu = "os";
$title = "Extratos";

include "cabecalho.php";

$sql = "SELECT extrato FROM tbl_extrato WHERE posto=$login_posto AND extrato=$extrato";
$res = pg_query($con, $sql);
if (pg_num_rows($res) == 0) {
    echo "<div style='width: 100%; padding: 10px; background: #FF0000; text-align:center; color: #FFFFFF; font-size: 12pt;'>Extrato não encontrato</div>";
    echo "<div style='width: 100%; padding: 20px; background: #FFFFFFFF; text-align:center; color: #000000; font-size: 10pt;'><a href='javascript:history.back();'>Voltar</a></div>";
    die;
}
?>
<style>
.table_line3 {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 12px;
    font-weight: normal;
    border: 0px solid;
    background-color: #FE918D
}
.menu_top4 {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: x-small;
    font-weight: bold;
    border: 1px solid;
    color:#ffffff;
    background-color: #CC3333;
}
#comunicado{
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 13px;
    color:#000000;
    border: 1px solid;
    width: 690;
}
.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    border: 1px solid;
    color:#596d9b;
    background-color: #d9e2ef
}

.menu_top2 {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: x-small;
    font-weight: bold;
    color:#ffffff;
    background-color: #596D9B
}

.menu_top3 {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: x-small;
    font-weight: bold;
    color:#ffffff;
    background-color: #880000
}
.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

</style>
<?
if($login_fabrica == 3) {

    $tDocs = new TDocs($con, $login_fabrica);
    $temAnexo = array();

    	$sql = "SELECT distinct tbl_extrato.extrato 
		FROM tbl_extrato
		JOIN tbl_faturamento ON tbl_faturamento.extrato_devolucao = tbl_extrato.extrato
		AND tbl_faturamento.fabrica = $login_fabrica
		AND tbl_faturamento.distribuidor = $login_posto
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND tbl_extrato.posto = $login_posto
		AND data_geracao >= '2017-08-01 00:00:00'
		ORDER BY extrato DESC";
    	$res = pg_query($con, $sql);
	$extratos = pg_fetch_all($res);
	$totalExtratos = count($extratos);

	if ($totalExtratos > 3) {

		foreach ($extratos as $kExtrato => $vExtrato) {

    		$anexo = $tDocs->getDocumentsByRef($vExtrato['extrato'],'comprovantelgr')->attachListInfo;
			if (!empty($anexo)) {
				$temAnexo[] = $vExtrato['extrato'];
			}

		}
		
		$totalSemAnexo = count($extratos)-count($temAnexo);

	    if ($totalSemAnexo > 3) {
	    	echo '
	    	    <table width="650" align="center" border="0" cellspacing="0" cellpadding="2">
                  	<tr>
		                <td class="msg_erro" style="padding:10px">
							Devem ser anexados os Comprovantes de Envio do LGR dos extratos anteriores para liberar a tela de consulta de valores de mão-de-obra.
						</td>
					</tr>
				</table>';
			include "rodape.php";
	    	exit;
	    }

	}

}

$sql = " SELECT     tbl_posto_linha.linha         ,
                    tbl_posto.posto               ,
                    tbl_posto_linha.distribuidor
            FROM   tbl_posto
            JOIN   tbl_posto_linha using (posto)
            JOIN   tbl_linha using (linha)
            WHERE  tbl_posto.posto = $login_posto
            AND    tbl_linha.fabrica = $login_fabrica limit 4  ";

$res = pg_query($con, $sql);

$total = pg_num_rows($res);
if($total>1){
    for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
        //echo //pg_fetch_result($res,$i,linha)."-".pg_fetch_result($res,$i,posto)."-".pg_fetch_result($res,$i,distribuidor);
//      echo "<br>";
        if (strlen(pg_fetch_result ($res,$i,distribuidor))>1) {
            $distribuidor_outro = 1;
        }else{
            $distribuidor_britania = 1;
        }
    }
}ELSE{
    $distribuidor_britania = 1;
}
?>
<?

//HD 204146: Fechamento automático de OS: Avisar o posto das OS fechadas automaticamente
if ($login_fabrica == 3) {
    $sql = "
    SELECT
    tbl_os.os,
    tbl_os.sua_os,
    tbl_comunicado.comunicado,
    TO_CHAR(tbl_comunicado.data, 'DD/MM/YYYY') AS data,
    tbl_comunicado_posto_blackedecker.leitor,
    TO_CHAR(tbl_comunicado_posto_blackedecker.data_confirmacao, 'DD/MM/YYYY') AS data_confirmacao
    
    FROM
    tbl_os
    JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
    LEFT JOIN tbl_os_comunicado ON tbl_os.os=tbl_os_comunicado.os
    LEFT JOIN tbl_comunicado ON tbl_os_comunicado.comunicado=tbl_comunicado.comunicado
    LEFT JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado.comunicado=tbl_comunicado_posto_blackedecker.comunicado

    WHERE
    tbl_os_extra.extrato=$extrato
    AND tbl_os.sinalizador=18
    AND tbl_os.fabrica=$login_fabrica

    ORDER BY
    tbl_comunicado.comunicado
    ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res)) {
        echo "
        <table width='700' align='center' border='0' cellspacing='2' style='border-bottom: 2px solid #880000'>
            <tr class='menu_top3'>
                <td colspan='5'>
                    OS FECHADAS AUTOMATICAMENTE PELO SISTEMA
                </td>
            </tr>
            <tr class='menu_top3'>
                <td colspan='5'>
                    Posto autorizado, conforme comunicados enviados estamos listando todas as Ordens de Serviço que foram finalizadas automaticamente pela fábrica e que estão neste extrato
                </td>
            </tr>
            <tr class='menu_top3'>
                <td width='110'>OS</td>
                <td width='80'>Comunicado</td>
                <td width='80'>Data</td>
                <td>Lido por</td>
                <td width='80'>Data Leitura</td>
            </tr>
            ";
        for ($i = 0; $i < pg_num_rows($res); $i++) {
            $os = pg_result($res, $i, os);
            $sua_os = pg_result($res, $i, sua_os);
            $comunicado = pg_result($res, $i, comunicado);
            $data = pg_result($res, $i, data);
            $leitor = pg_result($res, $i, leitor);
            $data_confirmacao = pg_result($res, $i, data_confirmacao);

            $cor = "#FFFFFF";
            if ($i % 2 == 0) $cor = "#FFCCDD";

            echo "<tr bgcolor='$cor' style='font-size: 10px; text-align:center;'>";

            echo "<td nowrap>";
            echo "<a href='os_press.php?os=$os' target='_blank'>$sua_os</a>";
            echo "</td>";

            echo "<td nowrap>";
            echo $comunicado;
            echo "</td>";

            echo "<td nowrap>";
            echo $data;
            echo "</td>";

            echo "<td nowrap>";
            echo $leitor;
            echo "</td>";

            echo "<td nowrap>";
            echo $data_confirmacao;
            echo "</td>";

            echo "</tr>";
        }

        echo "</table>";
    }
}

?>
<p>
<center>
<font size='+1' face='arial'>Data do Extrato</font>
<?
if($login_fabrica == 3){
    $sql = "SELECT os into TEMP tmp_reinc_90_$login_posto from tbl_os_status join tbl_os_extra using(os) where tbl_os_status.status_os in (67,70) and tbl_os_status.observacao like '% MAIS 90 DIAS)' and fabrica_status = $login_fabrica and tbl_os_extra.extrato = $extrato;";
    $res = pg_query($con,$sql);
    $count = "COUNT(CASE WHEN (tbl_os_extra.mao_de_obra_desconto is null or (tbl_os_extra.mao_de_obra_desconto is not null AND tbl_os_extra.os IN (select os from tmp_reinc_90_$login_posto where 1=1) )) THEN 1 else null END ) AS qtde ,
    COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto is not null AND tbl_os_extra.os NOT IN (select os from tmp_reinc_90_$login_posto where 1=1) THEN 1 ELSE NULL END) AS qtde_recusada ,";
} else {
    $count = "COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto is null THEN 1 else null END ) AS qtde ,
    COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto is not null  THEN 1 ELSE NULL END) AS qtde_recusada ,";
}

if($login_fabrica==25 OR $login_fabrica==51){//HD 28111 15/8/2008
    $sql_mao_de_obra  = " SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os.mao_de_obra    ELSE 0 END) AS mao_de_obra_posto     , ";
    $join_mao_de_obra = " JOIN tbl_os ON tbl_os.os = tbl_os_extra.os ";

}else{
    $sql_mao_de_obra = " SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra    ELSE 0 END) AS mao_de_obra_posto     , ";
}

$sql = "SELECT  tbl_linha.nome AS linha_nome ,
                tbl_linha.linha              ,
                tbl_os_extra.mao_de_obra AS unitario ,
                $count 
                $sql_mao_de_obra
                SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
                SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
                to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
                distrib.nome_fantasia AS distrib_nome                                        ,
                tbl_extrato.total,
                tbl_fabrica.nome as fabrica_nome
        FROM
            (SELECT tbl_os_extra.os ,tbl_os.sinalizador
            FROM tbl_os_extra 
            JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
            WHERE tbl_os_extra.extrato = $extrato
                    AND tbl_os.fabrica = $login_fabrica
            ) os 
        JOIN tbl_os_extra ON os.os = tbl_os_extra.os
        $join_mao_de_obra
        JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
        JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
        JOIN tbl_fabrica  ON tbl_fabrica.fabrica = tbl_extrato.fabrica
        LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
        GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.nome_fantasia,tbl_extrato.total,tbl_fabrica.nome
        ORDER BY distrib_nome, tbl_linha.nome";
$res = pg_query ($con,$sql);
//echo nl2br($sql);
echo @pg_fetch_result ($res,0,data_geracao);

echo "<br/>";
echo "<a href='new_extrato_posto_mao_obra_download.php?extrato=$extrato' target='_blank'>VER AS OS'S EM EXCEL PARA CONFERÊNCIA DO POSTO</a>";
echo "<br/>";

echo "<table width='700' align='center' border='1' cellspacing='2'>";
echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center' nowrap >Distribuidor</td>";
echo "<td align='center' nowrap >Linha</td>";
#echo "<td align='center' nowrap >M.O.Unit.</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >Recusadas</td>";
echo ($login_fabrica <> 3) ? "<td align='center' nowrap >Mão-de-Obra</td>" : "";
if ($login_fabrica == 51) echo "<td align='center' nowrap >Total NF</td>";
echo "<td align='center' nowrap >&nbsp;</td>";
echo "</tr>";

$total_qtde            = 0 ;
$total_mo_posto        = 0 ;
$total_mo_adicional    = 0 ;
$total_adicional_pecas = 0 ;

$distribuidor_nome = pg_fetch_result ($res,distrib_nome);
$fabrica_nome = pg_fetch_result ($res,fabrica_nome);
if(strlen($distribuidor_nome) == 0){
    if($login_fabrica==25)     $distribuidor_nome = "HBTECH";
    elseif($login_fabrica==51) $distribuidor_nome = "Gama Italy";
    else                       $distribuidor_nome = $fabrica_nome;
}

$distribuidor_nome_ant = $distribuidor_nome;
for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
    $distribuidor_nome = pg_fetch_result ($res,$i,distrib_nome);
    if(strlen($distribuidor_nome) == 0){
        if($login_fabrica==25)     $distribuidor_nome = "HBTECH";
        elseif($login_fabrica==51) $distribuidor_nome = "Gama Italy";
        else                       $distribuidor_nome = $fabrica_nome;
    }

////
    if ($distribuidor_nome_ant <> $distribuidor_nome){
        echo "<tr bgcolor='#FF9900' style='font-size:14px ; color:#ffffff ; font-weight:bold ' >";
        echo "<td align='center'>TOTAIS</td>";
        echo "<td align='center' nowrap >&nbsp;</td>";
        echo "<td align='center'>&nbsp;</td>";
        echo "<td align='right'>" . number_format ($total_qtde           ,0,",",".") . "</td>";
        echo "<td align='right'>" . number_format ($total_qtde_recusada  ,0,",",".") . "</td>";
        echo ($login_fabrica <> 3) ? "<td align='right'>" . number_format ($total_mo_posto       ,2,",",".") . "</td>" : "";
        echo "<td align='center'>&nbsp;</td>";
        echo "</tr>";
        echo "<tr style='font-size: 10px; $color' $bgcolor>";

        if($login_fabrica==3){
            if($distribuidor_nome_ant <> "Britania"){
                echo "<br>";
                echo "<table align='center' border='2' size='2' bgcolor='#FFCC33'>";
                echo "<tr><td align='center'>";
                echo "ENVIAR AS ORDENS DE SERVIÇO E A NOTA <br>
                FISCAL PARA O SEU DISTRIBUIDOR ACIMA";
                echo "</td>";
                echo "</tr>";
                echo "</table>";
                echo "<br>";
                echo "<br>";
                echo "<br>";
            }else{
                if (date('Y-m-d') >= '2006-08-01' AND $login_fabrica == 3 AND $extrato > 58445 AND $distribuidor_britania == 1) {
                    echo "<table align='center'>";
                    echo "<tr>";
                    ?>
                    <br>
                    <table align="center" border="2" size="2" bgcolor="#FFCC33">
                    <tr>
                    <td align="center"><font size='+1' face='arial'>ENVIO DE DOCUMENTOS</td>
                    <td align="center"><font size='+1' face='arial'>DESTINO</td>
                    <td align="center"><font size='+1' face='arial'>FORMA DE ENVIO</td>
                    </font>
                    </tr>
                    <tr>
                    <td><font size='+1' face='arial'>Notas Fiscais de Serviço<br> e Ordens de Serviço</td>
                    <td align='center'>
                        Britania Eletrodomésticos S/A - Curitiba<br>
                        Av. Nossa Senhora da Luz, 1330<br>
                        Bairro: Hugo Lange<br>
                        Curitiba - PR - CEP 80.040-265<br>
                        CNPJ: 76.492.701/0001-57<br>
                        I.E.: 10.503.415-65<br>
                    </td>
                    <td>Encomenda Normal-Correios,  <br>com ressarcimento pela Britania (*)</td>
                    </tr>
                    </table>
                    <br>
                    <?
                }
            }
        }


        echo "<table width='300' align='center' border='1' cellspacing='2'>";
        echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
        echo "<td align='center' nowrap >Distribuidor</td>";
        echo "<td align='center' nowrap >Linha</td>";
        echo "<td align='center' nowrap >M.O.Unit.</td>";
        echo "<td align='center' nowrap >Qtde</td>";
        echo "<td align='center' nowrap >Recusadas</td>";
        echo ($login_fabrica <> 3) ? "<td align='center' nowrap >Mão-de-Obra</td>" : "";
        #echo "<td align='center' nowrap >Pago via</td>";
        echo "<td align='center' nowrap >&nbsp;</td>";
        echo "</tr>";

        $total_qtde            = 0 ;
        $total_mo_posto        = 0 ;
        $total_mo_adicional    = 0 ;
        $total_adicional_pecas = 0 ;
        $distribuidor_nome_ant = $distribuidor_nome;
    }
////
    echo "<tr style='font-size: 10px'>";

    echo "<td nowrap >";
    echo $distribuidor_nome;
    echo "</td>";

    echo "<td nowrap >";
    echo pg_fetch_result ($res,$i,linha_nome);
    echo "</td>";

    #echo "<td  nowrap align='right'>";
    #echo number_format (pg_fetch_result ($res,$i,unitario),2,',','.');
    #echo "</td>";

    echo "<td  nowrap align='right'>";
    echo number_format (pg_fetch_result ($res,$i,qtde),0,',','.');
    echo "</td>";

    echo "<td  nowrap align='right'>";
    echo number_format (pg_fetch_result ($res,$i,qtde_recusada),0,',','.');
    echo "</td>";

    if ($login_fabrica <>3) { // HD 103533
        echo "<td  nowrap align='right'>";
        echo number_format (pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
        echo "</td>";
    }
    
    if ($login_fabrica == 51) { // HD 60841
        echo "<td  nowrap align='right'>";
        echo number_format (pg_fetch_result ($res,$i,total),2,',','.');
        echo "</td>";
    }
#   echo "<td  nowrap align='center'>";
#   $distrib_nome = pg_fetch_result ($res,$i,distrib_nome) ;
#   if (strlen ($distrib_nome) == 0) $distrib_nome = "<b>FABR.</b>";
#   echo $distrib_nome;
#   echo "</td>";

    $linha = pg_fetch_result ($res,$i,linha) ;
    $mounit = pg_fetch_result ($res,$i,unitario) ;

    echo "<td align='right' nowrap>";
    echo "<a href='new_extrato_posto_detalhe.php?extrato=$extrato&linha=$linha&mounit=$mounit'>ver O.S.</a>";
    echo "</td>";
    echo "</tr>";
    
    $total_qtde            += pg_fetch_result ($res,$i,qtde) ;
    $total_qtde_recusada   += pg_fetch_result ($res,$i,qtde_recusada);
    $total_mo_posto        += pg_fetch_result ($res,$i,mao_de_obra_posto) ;
    $total_mo_adicional    += pg_fetch_result ($res,$i,mao_de_obra_adicional) ;
    $total_adicional_pecas += pg_fetch_result ($res,$i,adicional_pecas) ;
    $total_valor           += pg_fetch_result ($res,$i,total) ;

}
//alterado HD 7261 7/11/2007 (se alterar aqui tem que mudar em baixo)
if($login_fabrica == 3){
    $sql = " SELECT
            extrato,
            historico,
            valor,
            admin,
            debito_credito,
            lancamento
        FROM tbl_extrato_lancamento
        WHERE extrato = $extrato
        AND fabrica = $login_fabrica
        /* hd 22096 */  /* HD 45942 */ 
         AND (admin IS NOT NULL OR lancamento in (104,103)) 
         and campos_adicionais->>'aprovacao' = 'false'
        ";
#HD 45942
/*Estava sem o 103, acrescentei...*/
    $res = pg_query ($con,$sql);
    
    if(pg_num_rows($res) > 0){
        for($i=0; $i < pg_num_rows($res); $i++){
            $extrato         = trim(pg_fetch_result($res, $i, extrato));
            $historico       = trim(pg_fetch_result($res, $i, historico));
            $valor           = trim(pg_fetch_result($res, $i, valor));
            $debito_credito  = trim(pg_fetch_result($res, $i, debito_credito));
            $lancamento      = trim(pg_fetch_result($res, $i, lancamento));

            if($debito_credito == 'D'){ 
                $bgcolor= "bgcolor='#FF0000'"; 
                $color = " color: #000000; ";
                if ($lancamento == 78 AND $valor>0){
                    $valor = $valor * -1;
                }
            }else{ 
                $bgcolor= "bgcolor='#0000FF'";
                $color = " color: #FFFFFF; ";
            }
            
            //hd 22096 - lançamentos e Valores de ajuste de Extrato
            if ($lancamento==103 or $lancamento==104) {
                $bgcolor= "bgcolor='#339900'";
            }

            if($lancamento == 248) {
                continue;
            }

            echo "<tr style='font-size: 10px; $color' $bgcolor>";
            echo "<TD><b>Avulso</b></TD>";
            echo "<TD colspan='3'><b>$historico</b></TD>";
            echo "<TD align='right'><b>".number_format ($valor ,2,",",".")."</b></TD>";
            echo "</tr>";
            $total_mo_posto = $valor + $total_mo_posto;
        }
    }
}
//----------
echo "<tr bgcolor='#FF9900' style='font-size:14px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center'>TOTAIS</td>";
#echo "<td align='center' nowrap >&nbsp;</td>";
echo "<td align='center'>&nbsp;</td>";
echo "<td align='right'>" . number_format ($total_qtde           ,0,",",".") . "</td>";
echo "<td align='right'>" . number_format ($total_qtde_recusada  ,0,",",".") . "</td>";
echo ($login_fabrica <> 3) ? "<td align='right'>" . number_format ($total_mo_posto       ,2,",",".") . "</td>" : "";

#echo "<td align='center'>&nbsp;</td>";
if ($login_fabrica == 51) { // HD 60841
    echo "<td align='right'>" . number_format ($total_valor       ,2,",",".") . "</td>";
}
echo "<td align='center'>&nbsp;</td>";
echo "</tr>";
echo "</table>";

//alterado HD 7261 7/11/2007 (alterar aqui tambem)
if($login_fabrica == 3){
    $sqlg = " SELECT motivo_reprovacao from tbl_extrato_agrupado where extrato = $extrato and reprovado IS NOT NULL";
    $resg = pg_query($con,$sqlg);
    if(pg_num_rows($resg) > 0){
        echo "<br/><table width='500' align='center' border='1' cellspacing='2'>";
        echo "<tr>";
            echo "<td style='background-color:#FF0000; font-size: 25px'>Pagamento Não Autorizado</td>";
        echo "</tr>";
        echo "<tr>";
            echo "<td style='font-size: 25px'>Motivo: ".pg_fetch_result($resg,0,'motivo_reprovacao')."</td>";
        echo "</tr>";
        echo "</table><br/>";
    }

    if(pg_num_rows($res) > 0){
    echo "<table>";
        echo "<tr>";
            echo "<td><BR></td>";
        echo "</tr>";
        echo "<tr>";
            echo "<td bgcolor='#FF0000' width='15'>&nbsp;</td>";
            echo "<td style='font-size: 10px'>Débito</td>";
        echo "</tr>";
        echo "<tr>";
            echo "<td bgcolor='#0000FF' width='15'>&nbsp;</td>";
            echo "<td style='font-size: 10px'>Crédito</td>";
        echo "</tr>";

        //hd 22096
        echo "<tr>";
            echo "<td bgcolor='#339900' width='15'>&nbsp;</td>";
            echo "<td style='font-size: 10px'>Valores de ajuste de Extrato</td>";
        echo "</tr>";
    echo "</table>";
    }

    echo "<br/><br/>";


    $sqlg = " SELECT codigo from tbl_extrato_agrupado where extrato = $extrato and aprovado IS NOT NULL";
    $resg = pg_query($con,$sqlg);
    if(pg_num_rows($resg) > 0){
        $sql = "SELECT  tbl_linha.nome AS linha_nome         ,
                        tbl_linha.linha                      ,
                        tbl_extrato_conferencia_item.mao_de_obra_unitario AS unitario ,
                        tbl_extrato_conferencia_item.mao_de_obra AS mao_de_obra_posto ,
                        tbl_extrato_conferencia_item.qtde_conferida as qtde
                FROM tbl_extrato_conferencia
                JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
                JOIN tbl_linha ON tbl_extrato_conferencia_item.linha = tbl_linha.linha
                WHERE tbl_extrato_conferencia.extrato = $extrato
                AND tbl_extrato_conferencia.cancelada IS NOT TRUE
                ORDER BY tbl_linha.nome;";

        $res = pg_query ($con,$sql);

        echo "<table width='700' align='center' border='0' cellspacing='2'><TR class='menu_top2'><TD colspan='5'>RESUMO DE CONFERÊNCIA DO EXTRATO</TD></TR>";
        echo "<tr class='menu_top2'>";
        echo "<td align='center' nowrap >Obs. Fab.</td>";
        echo "<td align='center' nowrap >Linha</td>";
        echo "<td align='center' nowrap >M.O.Unit.</td>";
        echo "<td align='center' nowrap >Qtde</td>";
        echo "<td align='center' nowrap >Mão-de-Obra</td>";
        echo "</tr>";

        $total_qtde            = 0 ;
        $total_mo_posto        = 0 ;
        $total_mo_adicional    = 0 ;
        $total_adicional_pecas = 0 ;


        for($i=0; $i<pg_num_rows($res); $i++){
            $acao_sinalizador  = "OK";
            $linha_nome        = pg_fetch_result ($res,$i,linha_nome);
            $unitario          = number_format(pg_fetch_result ($res,$i,unitario),2,',','.');
            $qtde              = number_format(pg_fetch_result ($res,$i,qtde),0,',','.');
            $mao_de_obra_a_pagar += pg_fetch_result ($res,$i,mao_de_obra_posto);
            $mao_de_obra_posto = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');

            $cor = "#FFFFFF";
            if ($i % 2 == 0) $cor = "#FEF2C2";

            echo "<tr bgcolor='$cor' style='font-size: 10px'>";

            echo "<td nowrap >";
            echo $acao_sinalizador;
            echo "</td>";

            echo "<td nowrap >";
            echo $linha_nome;
            echo "</td>";

            echo "<td  nowrap align='right'>";
            echo $unitario;
            echo "</td>";

            echo "<td  nowrap align='right'>";
            echo $qtde;
            echo "</td>";

            echo "<td  nowrap align='right'>";
            echo $mao_de_obra_posto;
            echo "</td>";

            echo "</tr>";

            $total_qtde            += pg_fetch_result ($res,$i,qtde) ;
            $total_mo_posto        += pg_fetch_result ($res,$i,mao_de_obra_posto) ;

        }


        $sql = " SELECT
                extrato,
                historico,
                valor,
                admin,
                debito_credito,
                lancamento
            FROM tbl_extrato_lancamento
            WHERE extrato = $extrato
            AND fabrica = $login_fabrica
            AND (admin IS NOT NULL OR lancamento in (103,104))";

        $res = pg_query ($con,$sql);

        echo "<INPUT TYPE='hidden' NAME='qtde_avulso' id='qtde_avulso' value=". pg_num_rows($res) .">";
        $total_avulso = 0;

        if(pg_num_rows($res) > 0){
            for($i=0; $i < pg_num_rows($res); $i++){
                $extrato         = trim(pg_fetch_result($res, $i, extrato));
                $historico       = trim(pg_fetch_result($res, $i, historico));
                $valor           = trim(pg_fetch_result($res, $i, valor));
                $debito_credito  = trim(pg_fetch_result($res, $i, debito_credito));
                $lancamento      = trim(pg_fetch_result($res, $i, lancamento));
                
                if($debito_credito == 'D'){ 
                    $bgcolor= "bgcolor='#FF0000'"; 
                    $color = " color: #000000; ";
                    if ($lancamento == 78 AND $valor>0){
                        $valor = $valor * -1;
                    }
                }else{ 
                    $bgcolor= "bgcolor='#0000FF'";
                    $color = " color: #FFFFFF; ";
                }

                //hd 22096 - lançamentos e Valores de ajuste de Extrato
                if ($lancamento==103 or $lancamento==104) {
                    $bgcolor= "bgcolor='#339900'";
                }

                echo "<tr style='font-size: 10px; $color' $bgcolor>";
                echo "<TD><b>Avulso</b></TD>";
                echo "<TD colspan='3'><b>$historico</b></TD>";
                echo "<TD align='right'><b>".number_format ($valor ,2,",",".")."</b>
                <INPUT TYPE='hidden' NAME='valor_avulso_$i' id='valor_avulso_$i' value='$valor'>
                </TD>";
                echo "</tr>";
                $total_avulso = $valor + $total_avulso;
            }
        }

        $total_nota = ($mao_de_obra_a_pagar+$total_avulso);

        echo "<TR class='menu_top2'><TD colspan='3'>TOTAL PARA PAGAMENTO</TD><TD align='right'>".$total_qtde."</TD><TD align='right'>".number_format ($total_nota,2,",",".")."</TD></TR></table>";


        /*echo "<br>";
        $sql = "SELECT  tbl_linha.nome AS linha_nome         ,
                        tbl_linha.linha                      ,
                        tbl_os_extra.mao_de_obra AS unitario ,
                        tbl_sinalizador_os.acao              ,
                        tbl_sinalizador_os.solucao           ,
                        COUNT(tbl_os.os) AS qtde                      ,
                        SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS not NULL THEN tbl_os_extra.mao_de_obra           ELSE 0 END) AS mao_de_obra_posto     ,
                        tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria     ,
                        SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS not NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
                        SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS not NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
                        to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
                        distrib.nome_fantasia AS distrib_nome                                  ,
                        distrib.posto    AS distrib_posto                                 
                FROM
                    (SELECT tbl_os_extra.os 
                    FROM tbl_os_extra 
                    JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
                    WHERE tbl_os_extra.extrato = $extrato
                    ) os 
                JOIN tbl_os_extra ON os.os = tbl_os_extra.os
                JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
                JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
                JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
                JOIN tbl_sinalizador_os    ON tbl_sinalizador_os.sinalizador = tbl_os.sinalizador 
                LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
                WHERE tbl_sinalizador_os.debito='S' AND tbl_os.sinalizador = 3
                GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.nome_fantasia, distrib.posto, tbl_os_extra.mao_de_obra, tbl_sinalizador_os.acao,tbl_sinalizador_os.solucao
                ORDER BY tbl_linha.nome";

        $res = pg_query ($con,$sql);

        echo "<table width='700' align='center' border='0' cellspacing='2'><TR class='menu_top2'><TD colspan='100%'>RESUMO DE CONFERÊNCIA OS REINCIDENTE</TD></TR>";
        echo "<tr class='menu_top2'>";
        echo "<td align='center' nowrap >Linha</td>";
        echo "<td align='center' nowrap >M.O.Unit.</td>";
        echo "<td align='center' nowrap >Qtde</td>";
        echo "<td align='center' nowrap >Mão-de-Obra</td>";
        echo "<td align='center' nowrap >Obs. Fab.</td>";
        echo "<td align='center' nowrap >Obs. Posto.</td>";
        echo "</tr>";

        $total_qtde            = 0 ;

        for($i=0; $i<pg_num_rows($res); $i++){
            $acao_sinalizador             = pg_fetch_result ($res,$i,acao);
            $linha             = pg_fetch_result ($res,$i,linha);
            $linha_nome        = pg_fetch_result ($res,$i,linha_nome);
            $unitario          = number_format(pg_fetch_result ($res,$i,unitario),2,',','.');
            $qtde              = number_format(pg_fetch_result ($res,$i,qtde),0,',','.');
            $total_os_r += number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
            $mao_de_obra_posto = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
            $mao_de_obra_posto_unitaria = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto_unitaria),2,',','.');

            $cor = "#FFFFFF";
            if ($i % 2 == 0) $cor = "#FEF2C2";

            echo "<tr bgcolor='$cor' style='font-size: 10px'>";

            echo "<td nowrap >";
            echo $linha_nome;
            echo "</td>";

            echo "<td  nowrap align='right'>";
            echo $unitario;
            echo "</td>";

            echo "<td  nowrap align='right'>";
            echo $qtde;
            echo "</td>";

            echo "<td  nowrap align='right'>";
            echo $mao_de_obra_posto;
            echo "</td>";

            echo "<td nowrap >";
            echo $acao_sinalizador;
            echo "</td>";

            echo "<td nowrap >";
            echo pg_fetch_result($res,$i,solucao);
            echo "</td>";

            echo "</tr>";

            $total_qtde            += pg_fetch_result ($res,$i,qtde) ;

        }
        echo "<TR class='menu_top2'><TD colspan='2'>TOTAIS</TD><TD align='right'>".$total_qtde."</TD><TD align='right'>".number_format ($total_os_r,2,",",".")."</td><td></td><td></td></TR></table>";
        echo "</table>";*/
        //------------------------------------resumo irregulares
        echo "<br>";
        $sql = "SELECT  tbl_linha.nome AS linha_nome         ,
                        tbl_linha.linha                      ,
                        tbl_os_extra.mao_de_obra AS unitario ,
                        tbl_sinalizador_os.acao              ,
                        tbl_sinalizador_os.solucao           ,
                        tbl_os.sua_os                        ,
                        tbl_os.os                            ,
                        COUNT(tbl_os.os) AS qtde             ,
                        SUM  (tbl_os_extra.mao_de_obra ) AS mao_de_obra_posto     ,
                        tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria     ,
                        SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS not NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
                        SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS not NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
                        to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
                        distrib.nome_fantasia AS distrib_nome                                  ,
                        distrib.posto    AS distrib_posto                                 
                FROM
                    (SELECT tbl_os_extra.os, tbl_os.sua_os
                    FROM tbl_os_extra 
                    JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
                    WHERE tbl_os_extra.extrato = $extrato
                    ) os 
                JOIN tbl_os_extra ON os.os = tbl_os_extra.os
                JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
                JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
                JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
                JOIN tbl_sinalizador_os    ON tbl_sinalizador_os.sinalizador = tbl_os.sinalizador 
                LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
                WHERE tbl_sinalizador_os.debito='S' AND tbl_os.sinalizador <> 3
                GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.nome_fantasia, distrib.posto, tbl_os_extra.mao_de_obra, tbl_sinalizador_os.acao,tbl_sinalizador_os.solucao,tbl_os.sua_os,tbl_os.os
                ORDER BY tbl_linha.nome,tbl_os.os";

        $res = pg_query ($con,$sql);

        echo "<table width='700' align='center' border='0' cellspacing='2'><TR class='menu_top2'><TD colspan='100%'>RESUMO DE CONFERÊNCIA COM IRREGULARIDADE</TD></TR>";
        echo "<tr class='menu_top2'>";
        echo "<td align='center' nowrap >OS</td>";
        echo "<td align='center' nowrap >Linha</td>";
        echo "<td align='center' nowrap >Mão-de-Obra</td>";
        echo "<td align='center' nowrap >Observação Britania</td>";
        echo "<td align='center' nowrap >Observação para Posto</td>";
        echo "</tr>";

        $total_qtde            = 0 ;

        echo "<input type='hidden' name='qtde_linha' id='qtde_linha' value='".pg_num_rows($res)."'>";
        $qtde_item_enviada = pg_num_rows($res);

        for($i=0; $i<pg_num_rows($res); $i++){
            $acao_sinalizador  = pg_fetch_result ($res,$i,acao);
            $linha             = pg_fetch_result ($res,$i,linha);
            $sua_os            = pg_fetch_result ($res,$i,sua_os);
            $os                = pg_fetch_result ($res,$i,os);
            $linha_nome        = pg_fetch_result ($res,$i,linha_nome);
            $unitario          = number_format(pg_fetch_result ($res,$i,unitario),2,',','.');
            $qtde              = number_format(pg_fetch_result ($res,$i,qtde),0,',','.');
            $mao_de_obra_posto = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
            $mao_de_obra_posto_unitaria = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto_unitaria),2,',','.');
            $total_os_i += number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');

            $cor = "#FFFFFF";
            if ($i % 2 == 0) $cor = "#FEF2C2";

            echo "<tr bgcolor='$cor' style='font-size: 10px'>";

            echo "<td nowrap >";
            echo "$sua_os";
            echo "</td>";

            echo "<td nowrap >";
            echo $linha_nome;
            echo "</td>";


            echo "<td  nowrap align='right'>";
            echo $mao_de_obra_posto;
            echo "</td>";

            echo "<td nowrap >";
            echo $acao_sinalizador;
            echo "</td>";

            echo "<td nowrap >";
            echo pg_fetch_result($res,$i,solucao);
            echo "</td>";

            echo "</tr>";

            $total_qtde            += pg_fetch_result ($res,$i,qtde) ;

        }
        echo "<TR class='menu_top2'><TD colspan='2'>TOTAIS</TD><TD align='right'>".number_format ($total_os_i,2,",",".")."</TD><TD></td><TD></td></TR></table>";
            echo "</table>";

        $sql = "SELECT  DISTINCT 
                            to_char(tbl_extrato_conferencia.data_conferencia,'DD/MM/YYYY') as   data_conferencia
                    FROM tbl_extrato_conferencia
                    JOIN tbl_extrato ON tbl_extrato.extrato=tbl_extrato_conferencia.extrato
                    WHERE tbl_extrato_conferencia.extrato = $extrato
                    AND   cancelada IS NOT TRUE
                    ORDER BY data_conferencia DESC LIMIT 1";
        $res = pg_query($con,$sql);
    
        if(pg_num_rows($res) > 0){
            $data_conferencia = pg_fetch_result($res,0,data_conferencia);
        }

        echo "<br>";
        $sql = "SELECT  tbl_linha.nome AS linha_nome         ,
                        tbl_linha.linha                      ,
                        tbl_os_extra.mao_de_obra AS unitario ,
                        tbl_sinalizador_os.acao              ,
                        tbl_sinalizador_os.solucao           ,
                        tbl_os.sua_os                        ,
                        tbl_os.os                            ,
                        COUNT(tbl_os.os) AS qtde             ,
                        SUM  (tbl_os_extra.mao_de_obra ) AS mao_de_obra_posto     ,
                        tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria     ,
                        SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS not NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
                        SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS not NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
                        to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
                        distrib.nome_fantasia AS distrib_nome                                  ,
                        distrib.posto    AS distrib_posto                                 
                FROM
                    (SELECT tbl_os_extra.os, tbl_os.sua_os
                    FROM tbl_os_extra 
                    JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
                    WHERE tbl_os_extra.extrato = $extrato
                    ) os 
                JOIN tbl_os_extra ON os.os = tbl_os_extra.os
                JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
                JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
                JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
                JOIN tbl_sinalizador_os    ON tbl_sinalizador_os.sinalizador = tbl_os.sinalizador 
                LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
                WHERE tbl_os.sinalizador =9
                GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.nome_fantasia, distrib.posto, tbl_os_extra.mao_de_obra, tbl_sinalizador_os.acao,tbl_sinalizador_os.solucao,tbl_os.sua_os,tbl_os.os
                ORDER BY tbl_linha.nome,tbl_os.os";

        $res = pg_query ($con,$sql);

        echo "<table width='700' align='center' border='0' cellspacing='2'><TR class='menu_top2'><TD colspan='100%'>RESUMO PARA PRÓXIMO EXTRATO</TD></TR>";
        echo "<tr class='menu_top2'>";
        echo "<td align='center' nowrap >OS</td>";
        echo "<td align='center' nowrap >Linha</td>";
        echo "<td align='center' nowrap >Mão-de-Obra</td>";
        echo "<td align='center' nowrap >Observação Britania</td>";
        echo "<td align='center' nowrap >Observação para Posto</td>";
        echo "</tr>";

        $total_qtde            = 0 ;
        $total_os_i = 0;
        echo "<input type='hidden' name='qtde_linha' id='qtde_linha' value='".pg_num_rows($res)."'>";
        $qtde_item_enviada = pg_num_rows($res);

        for($i=0; $i<pg_num_rows($res); $i++){
            $acao_sinalizador  = pg_fetch_result ($res,$i,acao);
            $linha             = pg_fetch_result ($res,$i,linha);
            $sua_os            = pg_fetch_result ($res,$i,sua_os);
            $os                = pg_fetch_result ($res,$i,os);
            $linha_nome        = pg_fetch_result ($res,$i,linha_nome);
            $unitario          = number_format(pg_fetch_result ($res,$i,unitario),2,',','.');
            $qtde              = number_format(pg_fetch_result ($res,$i,qtde),0,',','.');
            $mao_de_obra_posto = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
            $mao_de_obra_posto_unitaria = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto_unitaria),2,',','.');
            $total_os_i += number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');

            $cor = "#FFFFFF";
            if ($i % 2 == 0) $cor = "#FEF2C2";

            echo "<tr bgcolor='$cor' style='font-size: 10px'>";

            echo "<td nowrap >";
            echo "$sua_os";
            echo "</td>";

            echo "<td nowrap >";
            echo $linha_nome;
            echo "</td>";


            echo "<td  nowrap align='right'>";
            echo $mao_de_obra_posto;
            echo "</td>";

            echo "<td nowrap >";
            echo "Conferida em $data_conferencia";
            echo "</td>";

            echo "<td nowrap >";
            echo pg_fetch_result($res,$i,solucao);
            echo "</td>";

            echo "</tr>";

            $total_qtde            += pg_fetch_result ($res,$i,qtde) ;

        }
        echo "<TR class='menu_top2'><TD colspan='2'>TOTAIS</TD><TD align='right'>".number_format ($total_os_i,2,",",".")."</TD><TD></td><TD></td></TR></table>";
            echo "</table>";

            echo "<br/><br/>";
            $sql = "SELECT  DISTINCT nota_fiscal,
                            valor_nf,
                            to_char(tbl_extrato_conferencia.previsao_pagamento,'DD/MM/YYYY') as previsao_pagamento,
                            tbl_extrato_agrupado.codigo
                    FROM tbl_extrato_conferencia
                    JOIN tbl_extrato_agrupado USING(extrato)
                    JOIN tbl_extrato ON tbl_extrato.extrato=tbl_extrato_conferencia.extrato
                    WHERE tbl_extrato_conferencia.extrato = $extrato
                    AND   cancelada IS NOT TRUE
                    AND   tbl_extrato_agrupado.aprovado IS NOT NULL
                    LIMIT 1";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) > 0){
                $nota_fiscal = pg_fetch_result($res,0,nota_fiscal);
                $valor_nf = pg_fetch_result($res,0,valor_nf);
                $previsao_pagamento = pg_fetch_result($res,0,previsao_pagamento);
                $codigo = pg_fetch_result($res,0,codigo);
                if(strlen($nota_fiscal) > 0) {
                    echo "<p style='font-weight:bold; font-size:16px'>EXTRATO PAGO COM:</p>";       
                }
            }
            echo "<table width='700' align='center' border='1' cellspacing='2'>";
            /*echo "<tr bgcolor='#FF9900' style='font-size:15px ; color:#ffffff ; font-weight:bold ' >";
            echo "<td align='center' colspan='3'>CÓDIGO DA DESCRIÇÃO</td>";
            echo "</tr>";
            echo "<tr bgcolor='$cor' style='font-size:15px ; color:#000000 ; font-weight:bold ' >";
            echo "<td align='center' colspan='3'>".$codigo."</td>";
            echo "</tr>";*/

            echo "<tr bgcolor='#FFFFFF' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
            echo "<td align='center' colspan='3'></td>";
            echo "</tr>";

            $sqlt = " SELECT count(*) FROM tbl_extrato_agrupado where codigo='$codigo' AND aprovado IS NOT NULL";
            $rest = pg_query($con,$sqlt);
            $total_agrupado = pg_fetch_result($rest,0,0);

            echo "<tr bgcolor='#FFFFFF' style='font-size:15px ; color:#000000 ; font-weight:bold ' >";
            echo "<td align='center' colspan='3'>TOTAL EXTRATOS AGRUPADOS:    $total_agrupado </td>";
            echo "</tr>";
            
            echo "<tr bgcolor='#FFFFFF' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
            echo "<td align='center' colspan='3'></td>";
            echo "</tr>";

            echo "<tr bgcolor='#FFFFFF' style='font-size:15px ; color:#000000 ; font-weight:bold ' >";
            echo "<td align='center'>NF</td>";
            echo "<td align='center'>Valor (R$)</td>";
            echo "<td align='center'>Previsão Pagamento</td>";
            echo "</tr>";

            if(strlen($nota_fiscal) > 0) {

                echo "<tr bgcolor='#FFFFFF' style='font-size:15px ; color:#000000 ; font-weight:bold ' >";
                echo "<td align='center'>$nota_fiscal</td>";
                echo "<td align='center'>".number_format($valor_nf,2,",",".")."</td>";
                echo "<td align='center'>$previsao_pagamento</td>";
                echo "</tr>";
            }
            echo "</table>";

            $sql = " SELECT  
                            extrato_nota_avulsa,
                            nota_fiscal   ,
                            valor_original,
                            to_char(data_lancamento,'DD/MM/YYYY') as data_lancamento,
                            to_char(data_emissao,'DD/MM/YYYY') as data_emissao,
                            to_char(previsao_pagamento,'DD/MM/YYYY') as previsao_pagamento,
                            observacao
                        FROM tbl_extrato_nota_avulsa
                        WHERE extrato = $extrato
                        AND   fabrica = $login_fabrica ";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res) > 0){
                    echo "<br/>";
                    echo "<table width='700' align='center' border='1' cellspacing='2'>";
                    echo "<tr bgcolor='#FF9900' style='font-size:15px ; color:#ffffff ; font-weight:bold ' >";
                    echo "<td align='center' colspan='3'>Nota Avulsa</td>";
                    echo "</tr>";
                    echo "<tr bgcolor='#FFFFFF' style='font-size:15px ; color:#000000 ; font-weight:bold ' >";
                    echo "<td align='center'>NF</td>";
                    echo "<td align='center'>Valor (R$)</td>";
                    echo "<td align='center'>Previsão Pagamento</td>";
                    echo "</tr>";
                    for($i =0;$i<pg_num_rows($res);$i++) {
                        $extrato_nota_avulsa= pg_fetch_result($res,$i,extrato_nota_avulsa);
                        $data_lancamento   = pg_fetch_result($res,$i,data_lancamento);
                        $nota_fiscal       = pg_fetch_result($res,$i,nota_fiscal);
                        $data_emissao      = pg_fetch_result($res,$i,data_emissao);
                        $valor_original    = number_format(pg_fetch_result($res,$i,valor_original),2,",","."); 
                        $observacao        = pg_fetch_result($res,$i,observacao);
                        $previsao_pagamento= pg_fetch_result($res,$i,previsao_pagamento);

                        echo "<tr style='font-size: 10px;text-align:center'>";
                        echo "<td>$nota_fiscal</td>";
                        echo "<td>$valor_original</td>";
                        echo "<td nowrap>$previsao_pagamento</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    
                }
        }
    }
//-----------

if (date('Y-m-d') >= '2006-08-01' AND $login_fabrica == 3 AND $extrato > 58445 AND $distribuidor_nome_ant == "Britania") {
    echo "<table align='center'>";
    echo "<tr>";
    ?>
    <br />

    <!-- <table align="center" border="2" size="2" >
        <tr>
            <td align="center" bgcolor="#FFCC00"><font size='+1' face='arial'>ENVIO DE DOCUMENTOS</td>
            <td align="center"><font size='+1' face='arial'>DESTINO</td>
            <td align="center"><font size='+1' face='arial'>FORMA DE ENVIO</td>
            </font>
        </tr>
        <tr>
            <td bgcolor="#FFCC00"><font size='+1' face='arial'>Notas Fiscais de Serviço<br> e Ordens de Serviço</td>
            <td align='center'>
                Britania Eletrodomésticos S/A - Curitiba<br>
                Av. Nossa Senhora da Luz, 1330<br>
                Bairro: Hugo Lange<br>
                Curitiba - PR - CEP 80.040-265<br>
                CNPJ: 76.492.701/0001-57<br>
                I.E.: 10.503.415-65<br>
            </td>
            <td>Encomenda Normal-Correios,  <br>com ressarcimento pela Britania (*)</td>
        </tr>
    </table> -->

    <table align="center" border="2" cellpadding="5" style="width: 773px;">
        <tr style="font-size: 14px;">
            <th align="center" bgcolor="#FFCC00" width="34%">
                ENDEREÇO PARA ENVIO DE NOTAS 
                FISCAIS DE SERVIÇO E ORDENS DE 
                SERVIÇO REFERENTES AO 
                PAGAMENTO DE MÃO-DE-OBRA 
            </th>
            <th align="center" width="33%">
                DADOS PARA A EMISSÃO DA NF DE 
                PRESTAÇÃO DE SERVIÇOS
            </th>
            <th align="center" width="33%">FORMA DE ENVIO</th>
        </tr>
        <tr style="font-size: 12px;">
            <td bgcolor="#FFCC00">
                BRITANIA ELETRODOMESTICOS SA - Curitiba <br />
                Av. Nossa Senhora da Luz, 1330 <br />
                Bairro: Hugo Lange <br />
                Curitiba - PR - CEP 80.040-265
            </td>
            <td align='center'>
                BRITANIA ELETRONICOS S.A. <br />
                R. DONA FRANCISCA, 12340<br />
                Bairro: PIRABEIRABA<br />
                JOINVILLE - SC - CEP 89.239-270<br />
                CNPJ: 07.019.308/0001-28<br />
                I.E.: 25.486.166-0
            </td>
            <td>Encomenda Normal-Correios, <br /> com ressarcimento pela Britania (*)</td>
        </tr>
    </table>

    <br />
    <?
}else{
    if ($login_fabrica==25) {//HD 28111 15/8/2008
        echo "<BR>";
        echo "<TABLE width='650' align='center' border='0' cellspacing='2' cellpadding='2'>";
        echo "<tr class='table_line3'>\n";
        echo "<td class='menu_top4'>";
        echo "<div align='center' style='font-size:16px'>ATENÇÃO</div>";
        echo "</td></tr>";
        echo "<tr class='table_line3'>\n";
        echo "<td align=\"center\"><B>EMITIR E ENVIAR NOTA FISCAL DE MÃO DE OBRA JUNTO COM AS OS's PARA:</B><BR>
        HB ASSISTÊNCIA TÉCNICA LTDA.<br>
        Av. Yojiro Takaoka, 4.384 - Conj. 2156 - Loja 17 - Alphaville<br>
        Santana de Parnaíba, SP, CEP 06.541-038<br>
        CNPJ: 08.326.458/0001-47 </td>\n";
        echo "</tr>\n";
        /*echo "<tr class='table_line3'>\n";
        echo "<td align=\"center\"><B>ENVIAR AS OS's JUNTO COM A NF DE MÃO DE OBRA PARA:</b><BR>
        HBFLEX S.A.<br>
        Av. Yojiro Takaoka, 4.384 - Conj. 2156 - Loja 17 - Alphaville<br>
        Santana de Parnaíba, SP, CEP 06.541-038<br>
        CNPJ: 08.326.458/0001-47 </td>\n";*/
        echo "</table>";
    }elseif($login_fabrica==51){
        echo "<BR>";
        echo "<TABLE width='650' align='center' border='0' cellspacing='2' cellpadding='2'>";
        echo "<tr class='table_line3'>\n";
        echo "<td align=\"center\"><B>EMITIR NOTA FISCAL DE MÃO DE OBRA PARA:</B><BR>
        BRASVINCI COMÉRCIO DE ACESSÓRIOS E EQUIPAMENTOS DE BELEZA LTDA.<br>
        Rua Bogaert, 152 - Vila Vermelha, SP, CEP 04.298-020<br>
        CNPJ: 07.881.054/0001-52<br>
        IE: 149.256.240-117
        </td>\n";
        echo "</tr>\n";
        echo "<tr class='table_line3'>\n";
        echo "<td align=\"center\"><B>ENVIAR AS OS's JUNTO COM A NF DE MÃO DE OBRA PARA:</b><BR>
        TELECONTROL NETWORKING LTDA.<br>
        AV. Carlos Artêncio, 420 B - Fragata C<br>
        Marília, SP, CEP 17519-255 <br>
        CNPJ: 04.716.427/0001-41 <br></td>\n";
        echo "</tr>\n";
        echo "</table>";
    }else{
        echo "<br>";
        echo "<table align='center' border='2' size='2' bgcolor='#FFCC33'>";
        echo "<tr><td align='center'>";
        echo "ENVIAR AS ORDENS DE SERVIÇO E A NOTA <br>FISCAL PARA O SEU DISTRIBUIDOR ACIMA";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
    }
}

echo "<p align='center'>";
#echo "<font style='font-size:12px'>Enviar Nota Fiscal de Prestação de Serviços para o fabricante<br>no valor de <b>R$ " . trim (number_format ($total_mo_posto + $total_mo_adicional + $total_adicional_pecas,2,",",".")) . "</b> descontados o
