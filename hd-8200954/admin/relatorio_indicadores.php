<?php

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once "funcoes.php";
include "autentica_admin.php";
setlocale(LC_MONETARY, 'pt_BR');

$layout_menu = "callcenter";
$title = "Relatório OS x Pesquisa x Extrato";

include "cabecalho_new.php";

$plugins = array(
    "jquery",
    "datepicker",
    "dataTable",
    "bootstrap"
);
include("../admin/plugin_loader.php");

$dataAtual = new DateTime('now');
$anoAtual = $dataAtual->format("Y");
$quantidadeAnosAnteriores = 5;

$mesesDoAno = [
    '01' => 'Janeiro',
    '02' => 'Fevereiro',
    '03' => 'Março',
    '04' => 'Abril',
    '05' => 'Maio',
    '06' => 'Junho',
    '07' => 'Julho',
    '08' => 'Agosto',
    '09' => 'Setembro',
    '10' => 'Outubro',
    '11' => 'Novembro',
    '12' => 'Dezembro'
];

function createCsvAndReturnLink($data, $admin){
    $path = '../xls/';
    $fileName = "relatorio_indicadores_{$admin}.csv";
    $fullPath = $path . $fileName;
    $delimitador = ';';

    $handler = fopen($fullPath, "w+");

    $header = [
        'OSS DIGITADAS',
        'OSS FECHADAS',
        'OSS ABERTAS',
        'PESQUISAS REALIZADAS(mão de obra aprovada)',
        'PESQUISAS REALIZADAS(mão de obra cancelada)',
        'CHAMADOS FINALIZADOS SEM PESQUISA',
        'PESQUISAS DE SATISFAÇÃO PENDENTES', 
        'EXTRATOS GERADOS', 
        'EXTRATOS PENDENTES',
        'VALORES DE EXTRATOS LIBERADOS',
        'VALORES PAGOS',
        'VALORES PENDENTES DE PAGAMENTOS'
    ];

    fputcsv($handler, $header, $delimitador);
    fputcsv($handler, $data, $delimitador);
  
    return $fullPath;
}


$pesquisar = filter_input(INPUT_POST, 'pesquisar', FILTER_VALIDATE_BOOLEAN);
if($pesquisar){
    $mesPesquisado = filter_input(INPUT_POST, 'mes');
    $anoPesquisado = filter_input(INPUT_POST, 'ano');

    $ossDigitadas = 0;
    $ossFechadas = 0;
    $ossAbertas = 0;
    $pesquisasRealizadas = 0;
    $chamadosFinalizadosSemPesquisa = 0;
    $pesquisasPendentes = 0;
    $naoEnviouPesquisa = 0; 
    $pesquisasRealizadasCancelada = 0; 

    $sql = "SELECT tbl_os.data_digitacao, tbl_os.finalizada, tbl_resposta.os, tbl_resposta.sem_resposta, tbl_os_extra.admin_paga_mao_de_obra, tbl_os_extra.extrato, tbl_hd_chamado_extra.hd_chamado
              FROM tbl_os
         LEFT JOIN tbl_resposta ON tbl_resposta.os = tbl_os.os
         LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
		left join tbl_hd_chamado_extra on tbl_hd_chamado_extra.os = tbl_os.os and tbl_os.fabrica = :fabrica
             WHERE date_part('month', data_digitacao) = :mes
               AND date_part('year', data_digitacao) = :ano
               AND tbl_os.fabrica = :fabrica";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':mes', $mesPesquisado);
    $stmt->bindValue(':ano', $anoPesquisado);
    $stmt->bindValue(':fabrica', $login_fabrica);

    if( $stmt->execute() ){
        $resultOs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($resultOs as $item){
            list($dataDigitacao, $finalizada, $os, $sem_resposta, $mo, $extrato, $hd_chamado) = array_values($item);

            $ossDigitadas += ($dataDigitacao) ? 1 : 0;
            $ossFechadas += ($finalizada) ? 1 : 0;
            $ossAbertas  += ($finalizada == null) ? 1 : 0;
            
            if(isset($sem_resposta) AND $sem_resposta == false AND $mo == true)
                $pesquisasRealizadas += 1;
            elseif(isset($sem_resposta) and $sem_resposta == true and $mo == true)
                $pesquisasPendentes += 1;

            if(isset($sem_resposta) AND $sem_resposta == false AND $mo == false){
                $pesquisasRealizadasCancelada += 1;
            }

            if(!isset($sem_resposta) and $finalizada){
                $naoEnviouPesquisa += 1; 
            }

            $chamadosFinalizadosSemPesquisa += ($mo == false and $sem_resposta == true and !empty($hd_chamado)) ? 1 : 0;
        }
    }

    $extratosGerados = 0;
    $extratosPendentes = 0;
    $valoresExtratosLiberados = 0;
    $valoresPagos = 0;
    $valoresPendentesDePagamento = 0;

    $sql = "SELECT DISTINCT tbl_os_extra.extrato, tbl_extrato.total, tbl_extrato.liberado, tbl_extrato_pagamento.data_pagamento
                       FROM tbl_os
                  LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                  LEFT JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
                  LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
                      WHERE DATE_PART('month', data_digitacao) = :mes
                        AND DATE_PART('year', data_digitacao) = :ano
                        AND tbl_os.fabrica = :fabrica";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':mes', $mesPesquisado);
    $stmt->bindValue(':ano', $anoPesquisado);
    $stmt->bindValue(':fabrica', $login_fabrica);

    if( $stmt->execute() ){
        $resultExtrato = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach($resultExtrato as $item){
            list($extrato, $total, $liberado, $dataPagamento) = array_values($item);

            if($extrato AND $extrato != 0){
                $extratosGerados += 1;

                if( $dataPagamento == null ){
                    $extratosPendentes += 1;
                }
            }
            
            $valoresExtratosLiberados += ($liberado) ? $total : 0;
            $valoresPagos += ($dataPagamento) ? $total : 0;
            $valoresPendentesDePagamento += ($dataPagamento == null) ? $total : 0;
        }
    }

    $data = [];
    $data[] = $ossDigitadas;
    $data[] = $ossFechadas;
    $data[] = $ossAbertas;
    $data[] = $pesquisasRealizadas;
    $data[] = $pesquisasRealizadasCancelada;
    $data[] = $chamadosFinalizadosSemPesquisa;
    $data[] = $pesquisasPendentes;
    $data[] = $extratosGerados;
    $data[] = $extratosPendentes;
    $data[] = money_format('%.2n', $valoresExtratosLiberados);
    $data[] = money_format('%.2n', $valoresPagos);
    $data[] = money_format('%.2n', $valoresPendentesDePagamento);

    $linkDownload = createCsvAndReturnLink($data, $login_admin);
}

?>

<style>
    .titulo-pesquisa{ background-color: #596d9b; margin: 0; color: white; padding: 5px; font-size: 15px; text-align: center;}
    .conteudo-principal{ background-color: #D9E2EF; padding: 15px 0; }
    .box-select{ margin-right: 10px; }

    .datagrid{ margin: 15px 0; }
    .datagrid-row{ display: flex; justify-content: space-between; padding: 5px; font-weight: bold; }
    .datagrid-row-money{ display: flex; justify-content: space-between; min-width: 85px; }
    .datagrid-row:nth-child(1n+2):hover{background-color: #c0c0c04d; border-left: 2px solid #596d9b;}

    .mt-2{ margin-top: 20px; }
    .text-white{ color: white; }
    .bg-blue{ background-color: #90b9f94d; }
    .bg-green{ background-color: #d1f78f4d; }
    .bg-yellow{ background-color: #f4ef8d4d; }
    .bg-telecontrol{ background-color: #596d9b }
</style>

<form method="POST" action="">
    <h5 class="titulo-pesquisa"> Parâmetros de pesquisa </h5>
    <div class="row-fluid conteudo-principal">
        <div class="span2"></div>
            <div class="span3 box-select">
                <label for="">Mês</label>
                <select name="mes" id="">
                    <?php foreach($mesesDoAno as $numeroDoMes => $nomeDoMes): ?>
                        <option value="<?=$numeroDoMes?>" <?= ($numeroDoMes == $mesPesquisado) ? 'selected' : null ?> > <?=$nomeDoMes?> </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="span3 box-select">
                <label for="">Ano</label>
                <select name="ano" id="">
                    <option value="<?=$anoAtual?>"> <?=$anoAtual?> </option>
                    <?php for($i=1; $i<=$quantidadeAnosAnteriores; $i++): ?>
                        <option value="<?=($anoAtual-$i)?>" <?= (($anoAtual-$i) == $anoPesquisado) ? 'selected' : null ?> > <?= ($anoAtual-$i) ?> </option>
                    <?php endfor; ?>
                </select>
            </div>
        <div class="span2">
            <input type="hidden" value="true" name="pesquisar">
            <button class="btn btn-primary mt-2">Pesquisar</button>
        </div>

        <?php if( !empty($linkDownload) ): ?>
            <a href="<?=$linkDownload?>" download target="_blank" style="padding-top: 14px !important; display: block">
                <h5> Baixar Excel </h5> 
            </a>
        <?php endif; ?>
    </div>
</form>

<?php if($pesquisar): ?>
    <div class="datagrid">
        <div>
            <div class="datagrid-row text-white bg-telecontrol">
                <div> Mês / Ano </div>
                <div> <?= $mesesDoAno[$mesPesquisado] ?> / <?= $anoPesquisado ?> </div>
            </div>
            <div class="datagrid-row bg-blue">
                <div> OSs digitadas </div>
                <div> <?= $ossDigitadas ?> </div>
            </div>
            <div class="datagrid-row bg-blue">
                <div> OSs fechadas (atual) </div>
                <div> <?= $ossFechadas ?> </div>
            </div>
            <div class="datagrid-row bg-blue">
                <div> OSs abertas (atual) pendentes de fechamento </div>
                <div> <?= $ossAbertas ?> </div>
            </div>
            <div class="datagrid-row bg-green">
                <div> Pesquisas realizadas(chamados finalizados com aprovação de mão de obra) </div>
                <div> <?= $pesquisasRealizadas ?> </div>
            </div>
            <div class="datagrid-row bg-green">
                <div> Pesquisas realizadas(chamados finalizados com mão de obra cancelada) </div>
                <div> <?= $pesquisasRealizadasCancelada ?> </div>
            </div>
            <div class="datagrid-row bg-green">
                <div> Chamados finalizados sem pesquisa (com mão de obra cancelada) </div>
                <div> <?= $chamadosFinalizadosSemPesquisa ?> </div>
            </div>
            <div class="datagrid-row bg-green">
                <div> Pesquisas de satisfação pendentes </div>
                <div> <?= $pesquisasPendentes ?> </div>
            </div>
            <div class="datagrid-row bg-yellow">
                <div> Extratos gerados </div>
                <div> <?= $extratosGerados ?> </div>
            </div>
            <div class="datagrid-row bg-yellow">
                <div> Extratos pendentes </div>
                <div> <?= $extratosPendentes ?> </div>
            </div>
            <div class="datagrid-row bg-yellow">
                <div> Valores de extratos liberado pela Fujitsu </div>
                <div class="datagrid-row-money">
                    <div> <?= money_format('%.2n', $valoresExtratosLiberados) ?> </div>
                </div>
            </div>
            <div class="datagrid-row bg-yellow">
                <div> Valores pagos </div>
                <div class="datagrid-row-money">
                    <div> <?= money_format('%.2n', $valoresPagos) ?> </div>
                </div>
            </div>
            <div class="datagrid-row bg-yellow">
                <div> Valores pendentes de pagamentos </div>
                <div class="datagrid-row-money">
                    <div> <?= money_format('%.2n', $valoresPendentesDePagamento) ?>  </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>

</script>
