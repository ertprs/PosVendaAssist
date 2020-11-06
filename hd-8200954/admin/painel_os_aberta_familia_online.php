<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$admin_privilegios  = "gerencia";
$layout_menu        = "gerencia";
$title              = "PRODUTOS AGUARDANDO REPARO NA ASSISTÊNCIA TÉCNICA";

include "cabecalho_new.php";

$sql = "SELECT  tbl_os.os                   ,
                tbl_familia.familia         ,
                tbl_tipo_posto.posto_interno,
                tbl_os.data_abertura        ,
                tbl_os.consumidor_nome      ,
                tbl_os.revenda_nome         ,
                tbl_os.consumidor_cpf
   INTO TEMP    tmp_os_aberta_$login_admin
        FROM    tbl_os
        JOIN    tbl_produto         ON  tbl_produto.produto         = tbl_os.produto
                                    AND tbl_produto.fabrica_i       = $login_fabrica
        JOIN    tbl_familia         ON  tbl_familia.familia         = tbl_produto.familia
                                    AND tbl_familia.fabrica         = $login_fabrica
        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_os.posto
                                    AND tbl_posto_fabrica.fabrica   = $login_fabrica
        JOIN    tbl_tipo_posto      ON  tbl_tipo_posto.tipo_posto   = tbl_posto_fabrica.tipo_posto
                                    AND tbl_tipo_posto.fabrica      = $login_fabrica
        WHERE   tbl_os.fabrica          = $login_fabrica
        AND     tbl_os.data_fechamento  IS NULL
        AND     tbl_os.finalizada       IS NULL
		AND		tbl_os.posto not in (6359)
        AND     tbl_os.excluida         IS NOT TRUE
";
$res = pg_query($con,$sql);

$sqlFamilia = "
            SELECT  familia,
                    upper(descricao) AS descricao
            FROM    tbl_familia
            WHERE   fabrica = $login_fabrica
            AND     ativo IS TRUE
      ORDER BY      descricao
";
$resFamilia = pg_query($con,$sqlFamilia);
$totalFamilia = pg_num_rows($resFamilia);
?>
<style type="text/css">

#painel_os > table {
    margin: 0 auto;
}

table {
    border-collapse: separate;
    width:1200px;
    border-spacing: 7px !important; 
}

td.tabela_resultado {
    vertical-align: top;
}

th.titulo_coluna {
    background-color: #596D9B;
    color: #FFF;
    padding:5px;
    font-size:16px;
}

th.espaco,
td.espaco {
    border: 0px;
    width: 30px;
}

td.logo {
    border: solid 1px #000;
    font-weight: bold;
    font-size: 18px;
    padding:5px;
}

td.total {
    border: solid 1px #000;
    font-weight: bolder;
    font-size: 18px;
    padding:5px;
}

.familia {
    background-color:#CCC;
}

.info {
    text-align:center;
}

td.logo > img {
    width: 80px;
    height: 80px;

}

.zeroCinco {
    background-color:#70AD47;
}
.seisQuinze {
    background-color:#FF0;
}
.dezesseisTrinta {
    background-color:#F7A209;
}
.acima {
    background-color:#F00;
    color:#FFF;
}

td.mais_antigo_pf,
td.mais_antigo_pj,
td.pj,
td.pf {
    border-right: 1px solid #000;
    border-left: 1px solid #000;
    border-bottom: 0px;
    border-top: 0px;
    height: 30px;
    text-align: center;
    font-weight: bold;
    font-size: 15px;
}

td.pj,
td.pf {
    width: 30px;
}

td.totaliza {
    border: 1px solid #000;
    width:  90px;
    height: 60px;
    text-align: center;
    font-weight: bold;
    font-size: 20px;
}

td.seta {
    width:  100px;
    height: 60px;
    text-align: center;
}

td.reparo {
    background-color: #C0C0C0;
    border: 1px solid #000;
    width: 360px;
    height: 60px;
    text-align: center;
    font-weight: bold;
}

td.espaco2 {
    border: 0px;
    width: 30px;
}

td.espaco3 {
    border: 0px;
    width: 10px;
}

.legenda {
    border-right: 1px solid #000;
    border-left: 1px solid #000;
    border-bottom: 1px solid #000;
    border-top: 1px solid #000;
    height: 30px;
    text-align: center;
    font-weight: bold;
}

span.regras {
    text-align:center;
    font-weight:bold;
    display:inline-table;
    width:80px;
}

span.title {
    font-weight:bold;
    font-size:18px;
}
span.value {
    font-size:16px;
}

td.footRegras {
    text-align:right;
    margin-right:5px;
    font-size:16px;
}
.txt_subtotal{
    font-size: 25px !important;
}
.txt_total{
    font-size: 32px !important;
    padding: 10px !important;
}
.txt_regras{
    background: #eee;
}
</style>

<script type="text/javascript">

$(function() {
    $("table").each(function() {
        $(this).find("td.pf").first().css({ "border-top": "1px solid #000" });
        $(this).find("td.pj").first().css({ "border-top": "1px solid #000" });
        $(this).find("td.mais_antigo_pf").first().css({ "border-top": "1px solid #000" });
        $(this).find("td.mais_antigo_pj").first().css({ "border-top": "1px solid #000" });

        $(this).find("td.pf").last().css({ "border-bottom": "1px solid #000" });
        $(this).find("td.pj").last().css({ "border-bottom": "1px solid #000" });
        $(this).find("td.mais_antigo_pf").last().css({ "border-bottom": "1px solid #000" });
        $(this).find("td.mais_antigo_pj").last().css({ "border-bottom": "1px solid #000" });
    });

    setTimeout(function(){ location.reload(); }, 180000);
});

</script>
</div>

<div id="painel_os">
    <table>
        <tr>
            <td class="tabela_resultado">
                <table>

                    <thead>

                        <tr>
                            <th class="titulo_coluna" rowspan="2">Família</th>
                            <th class="titulo_coluna" colspan="4">CONTROLE DE OS (Ordem de Serviço)<br>da ASSISTÊNCIA TECVOZ</th>
                            <th class="titulo_coluna" colspan="3">CONTROLE DE OS (Ordem de Serviço)<br>da ASSISTÊNCIA AUTORIZADA</th>
                        </tr>
                        <tr>
                            <th class="titulo_coluna">PJ</th>
                            <th class="titulo_coluna">PF</th>
                            <th class="titulo_coluna">TECVOZ</th>
                            <th class="titulo_coluna">MAIS ANTIGO PF / PJ</th>
                            <th class="titulo_coluna">PJ</th>
                            <th class="titulo_coluna">PF</th>
                            <th class="titulo_coluna">MAIS ANTIGO PF / PJ</th>
                        </tr>
                    </thead>

                    <tbody>
<?php
    for ($i = 0;$i < $totalFamilia;$i++) {
        $familia = pg_fetch_result($resFamilia,$i,'familia');
        $nome_familia = pg_fetch_result($resFamilia,$i,'descricao');
        /*
         * - Posto INTERNO
         */
        $sql = "SELECT  count(os) AS total_pj
                FROM    tmp_os_aberta_$login_admin
                WHERE   familia = $familia
                AND     posto_interno           IS TRUE
                AND     (LENGTH(consumidor_cpf)  = 14 or length(consumidor_cpf) = 0)
                AND     consumidor_nome         NOT ILIKE '%tecvoz%'
                AND     revenda_nome         NOT ILIKE '%tecvoz%'";
        $resIntPJ           = pg_query($con,$sql);
        $totalIntPj         = pg_fetch_result($resIntPJ,0,'total_pj');
        $totalizadorIntPj  += $totalIntPj;

        $sql = "SELECT  count(os) AS total_pf
                FROM    tmp_os_aberta_$login_admin
                WHERE   familia = $familia
                AND     posto_interno           IS TRUE
                AND     LENGTH(consumidor_cpf)  = 11
                AND     consumidor_nome         NOT ILIKE '%tecvoz%'
                AND     revenda_nome         NOT ILIKE '%tecvoz%'";
        $resIntPF           = pg_query($con,$sql);
        $totalIntPf         = pg_fetch_result($resIntPF,0,'total_pf');
        $totalizadorIntPf  += $totalIntPf;

        $sql = "SELECT  count(os) AS total_tecvoz
                FROM    tmp_os_aberta_$login_admin
                WHERE   familia = $familia
                AND     posto_interno   IS TRUE
                AND     (consumidor_nome ILIKE '%tecvoz%' or revenda_nome ~*'tecvoz')";
        $resTecvoz          = pg_query($con,$sql);
        $totalTecvoz        = pg_fetch_result($resTecvoz,0,'total_tecvoz');
        $totalizadorTecvoz += $totalTecvoz;

        $sql = "SELECT  to_char(data_abertura,'DD/MM/YYYY') AS data_abertura,
                        (CURRENT_DATE - data_abertura) AS tempo_espera
                FROM    tmp_os_aberta_$login_admin
                WHERE   familia = $familia
                AND     posto_interno IS TRUE
          ORDER BY      tempo_espera DESC
                LIMIT   1";
        $resIntDT       = pg_query($con,$sql);
        $data_interno   = pg_fetch_result($resIntDT,0,'data_abertura');
        $tempo_interno  = pg_fetch_result($resIntDT,0,'tempo_espera');
		$diasInt = "";
        if (!empty($tempo_interno)) {
            switch($tempo_interno) {
                case $tempo_interno  >= 0 && $tempo_interno <= 5:
                    $diasInt = "zeroCinco";
                    break;
                case $tempo_interno > 5 && $tempo_interno <= 15:
                    $diasInt = "seisQuinze";
                    break;
                case $tempo_interno > 15 && $tempo_interno <= 30:
                    $diasInt = "dezesseisTrinta";
                    break;
                case $tempo_interno > 30:
                    $diasInt = "acima";
                    break;
            }
		}elseif($tempo_interno =='0' and strlen($tempo_interno) > 0){
			$diasInt = "zeroCinco";
		}
        /*
         * - Postos EXTERNOS
         */
        $sql = "SELECT  count(os) AS total_pj
                FROM    tmp_os_aberta_$login_admin
                WHERE   familia = $familia
                AND     posto_interno           IS NOT TRUE
                AND     (LENGTH(consumidor_cpf)  = 14 or length(consumidor_cpf) = 0)";
        $resPJ              = pg_query($con,$sql);
        $total_pj           = pg_fetch_result($resPJ,0,'total_pj');
        $totalizador_pj    += $total_pj;

        $sql = "SELECT  count(os) AS total_pf
                FROM    tmp_os_aberta_$login_admin
                WHERE   familia = $familia
                AND     posto_interno           IS NOT TRUE
                AND     LENGTH(consumidor_cpf)  = 11";
        $resPF              = pg_query($con,$sql);
        $total_pf           = pg_fetch_result($resPF,0,'total_pf');
        $totalizador_pf    += $total_pf;

        $sql = "SELECT  to_char(data_abertura,'DD/MM/YYYY') AS data_abertura,
                        (CURRENT_DATE - data_abertura) AS tempo_espera
                FROM    tmp_os_aberta_$login_admin
                WHERE   familia = $familia
                AND     posto_interno IS NOT TRUE
          ORDER BY      tempo_espera DESC
                LIMIT   1";
        $resDT          = pg_query($con,$sql);
        $data           = pg_fetch_result($resDT,0,'data_abertura');
        $tempo_espera   = pg_fetch_result($resDT,0,'tempo_espera');

        if(!empty($tempo_espera)){
            switch($tempo_espera) {
                case $tempo_espera  >= 0 && $tempo_espera <= 5:
                    $dias = "zeroCinco";
                    break;
                case $tempo_espera > 5 && $tempo_espera <= 15:
                    $dias = "seisQuinze";
                    break;
                case $tempo_espera > 15 && $tempo_espera <= 30:
                    $dias = "dezesseisTrinta";
                    break;
                case $tempo_espera > 30:
                    $dias = "acima";
                    break;
            }
       	}elseif($tempo_interno =='0' and strlen($tempo_interno) > 0){
			$diasInt = "zeroCinco";
		}


?>
                        <tr>
                            <td class='logo familia'><?=$nome_familia?></td>
                            <td class='logo info'><?=$totalIntPj?></td>
                            <td class='logo info'><?=$totalIntPf?></td>
                            <td class='logo info'><?=$totalTecvoz?></td>
                            <td class='logo info <?=$diasInt?>'><?=(strlen($data_interno) > 0) ? $data_interno : "" ?></td>
                            <td class='logo info'><?=$total_pj?></td>
                            <td class='logo info'><?=$total_pf?></td>
                            <td class='logo info <?=$dias?>'><?=(strlen($data) > 0) ? $data : "" ?></td>
                        </tr>
<?php
        $diasInt = "";
        $dias = "";
    }

    $sqlContaTodas = "
        SELECT  COUNT(1) AS total_os,
                CASE WHEN CURRENT_DATE - data_abertura >= 0 AND CURRENT_DATE - data_abertura <= 5
                     THEN 'verde'
                     WHEN CURRENT_DATE - data_abertura > 5 AND CURRENT_DATE - data_abertura <= 15
                     THEN 'amarelo'
                     WHEN CURRENT_DATE - data_abertura > 15 AND CURRENT_DATE - data_abertura <= 30
                     THEN 'laranja'
                     WHEN CURRENT_DATE - data_abertura > 30
                     THEN 'vermelho'
                END AS regra_dias_os
        FROM    tmp_os_aberta_$login_admin
  GROUP BY      regra_dias_os
    ";

    $resContaTodas = pg_query($con,$sqlContaTodas);
    while ($contaOs = pg_fetch_object($resContaTodas)) {
        $mostraValores[$contaOs->regra_dias_os] = $contaOs->total_os;
    }

?>
                        <tr>
                            <td class="logo familia">TOTAL</td>
                            <td class='total info txt_subtotal'><?=$totalizadorIntPj?></td>
                            <td class='total info txt_subtotal'><?=$totalizadorIntPf?></td>
                            <td class='total info txt_subtotal'><?=$totalizadorTecvoz?></td>
                            <td class='total info txt_subtotal'><?=$totalizadorIntPj+$totalizadorIntPf+$totalizadorTecvoz?></td>
                            <td class='total info txt_subtotal'><?=$totalizador_pj?></td>
                            <td class='total info txt_subtotal'><?=$totalizador_pf?></td>
                            <td class='total info txt_subtotal'><?=$totalizador_pj+$totalizador_pf?></td>
                        </tr>
                        <tr>
                            <td class="logo familia" colspan="7">TOTAL GERAL DE OS AGUARDANDO REPARO</td>
                            <td class='logo info txt_total'><?=$totalizadorIntPj+$totalizadorIntPf+$totalizadorTecvoz+$totalizador_pj+$totalizador_pf?></td>
                        </tr>
                    </tbody>
                </table>
                <table style="width:100%;border-spacing: 0px;">
                        <tr>
                            <td colspan="7" class="logo txt_regras info">REGRA PARA CONTROLE DAS OS</td>
                        </tr>
                        <tr>
                            <td width="40%" class="footRegras total">OS Aberta de 0 até 5 dias:</td>
                            <td width="1%">=</td>
                            <td class="footRegras total zeroCinco"><span class="regras"><?=(!empty($mostraValores["verde"])) ? $mostraValores["verde"] : 0?></span></td>
                            <td width="10%"></td>
                            <td width="40%" class="footRegras total">OS Aberta de 6 até 15 dias: </td>
                            <td width="1%">=</td>
                            <td class="footRegras total seisQuinze"><span class="regras"><?=(!empty($mostraValores["amarelo"])) ? $mostraValores["amarelo"] : 0?></span></td>
                        </tr>
                        <tr>
                            <td class="footRegras total">OS Aberta de 16 até 30 dias:</td>
                            <td>=</td>
                            <td class="footRegras total dezesseisTrinta"><span class="regras"><?=(!empty($mostraValores["laranja"])) ? $mostraValores["laranja"] : 0?></span></td>
                            <td></td>
                            <td class="footRegras total">OS Aberta acima de 30 dias:</td>
                            <td>=</td>
                            <td class="footRegras total acima"><span class="regras"><?=(!empty($mostraValores["vermelho"])) ? $mostraValores["vermelho"] : 0?></span></td>
                        </tr>
                        <tr style="border:1px solid #000;">
                            <td colsapn="7">
                                <span class="title">Data: </span><span class="value"><?=date('d/m/Y')?></span><br />
                                <span class="title">Atualizado em: </span><span class="value"><?=date('d/m/Y H:i:s')?></span>
                            </td>
                        </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
<?php
include "rodape.php";
?>
