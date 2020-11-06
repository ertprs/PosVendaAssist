<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if ($areaAdmin === true) {
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include_once __DIR__."/class/tdocs.class.php";

$os = $_GET['os'];

if (empty($os)) {
    exit("Ordem de Serviço inválida");
}

$sql = "
    SELECT 
        o.sua_os, 
        o.posto,
        EXTRACT(YEAR FROM o.data_digitacao) AS ano_os,
        o.consumidor_revenda,
        CASE WHEN o.consumidor_revenda = 'C' THEN
            o.consumidor_nome
        ELSE
            r.nome
        END AS cliente_nome,
        CASE WHEN o.consumidor_revenda = 'C' THEN
            o.consumidor_cpf
        ELSE
            r.cnpj
        END AS cliente_cpf_cnpj,
        CASE WHEN o.consumidor_revenda = 'C' THEN
            o.consumidor_endereco
        ELSE
            r.endereco
        END AS cliente_endereco,
        CASE WHEN o.consumidor_revenda = 'C' THEN
            o.consumidor_numero
        ELSE
            r.numero
        END AS cliente_numero,
        CASE WHEN o.consumidor_revenda = 'C' THEN
            o.consumidor_complemento
        ELSE
            r.complemento
        END AS cliente_complemento,
        CASE WHEN o.consumidor_revenda = 'C' THEN
            o.consumidor_bairro
        ELSE
            r.bairro
        END AS cliente_bairro,
        CASE WHEN o.consumidor_revenda = 'C' THEN
            o.consumidor_cidade
        ELSE
            rc.nome
        END AS cliente_cidade,
        CASE WHEN o.consumidor_revenda = 'C' THEN
            o.consumidor_estado
        ELSE
            rc.estado
        END AS cliente_estado,
        CASE WHEN o.consumidor_revenda = 'C' THEN
            o.consumidor_cep
        ELSE
            r.cep
        END AS cliente_cep,
        p.referencia AS produto_referencia,
        p.descricao AS produto_descricao,
        op.serie,
        t.nome AS tecnico_nome,
        pst.nome AS posto_nome,
        pf.contato_endereco AS posto_endereco,
        pf.contato_numero AS posto_numero,
        pf.contato_complemento AS posto_complemento,
        pf.contato_bairro AS posto_bairro,
        pf.contato_cidade AS posto_cidade,
        pf.contato_estado AS posto_estado,
        pf.contato_cep AS posto_cep,
        lto.titulo AS form,
        lto.observacao AS answers
    FROM tbl_laudo_tecnico_os lto 
    INNER JOIN tbl_os o ON o.os = lto.os AND o.fabrica = {$login_fabrica}
    LEFT JOIN tbl_revenda r ON r.revenda = o.revenda
    LEFT JOIN tbl_cidade rc ON rc.cidade = r.cidade
    INNER JOIN tbl_os_produto op ON op.os = o.os
    INNER JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica}
    INNER JOIN tbl_login_unico lu ON lu.login_unico = lto.ordem
    INNER JOIN tbl_tecnico t ON t.codigo_externo = lu.login_unico::text
    INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
    INNER JOIN tbl_posto pst ON pst.posto = pf.posto
    WHERE lto.fabrica = {$login_fabrica} 
    AND lto.os = {$os}
";
$res = pg_query($con, $sql);
extract(pg_fetch_assoc($res));

$amazonTC = new TDocs($con, 10);
$documents = $amazonTC->getdocumentsByRef($posto, 'logomarca_posto')->attachListInfo;

if (count($documents) > 0){
    foreach ($documents as $key => $value) {
        $link_logo_tdocs = $value['link'];
    }
}

$link_logo_fabrica = 'logos/logo_ibramed.png';

if (!empty($cliente_cpf_cnpj)) {
    if ($consumidor_revenda == 'C') {
        $cliente_cpf_cnpj = substr($cliente_cpf_cnpj, 0, 3).'.'.substr($cliente_cpf_cnpj, 3, 3).'.'.substr($cliente_cpf_cnpj, 6, 3).'-'.substr($cliente_cpf_cnpj, 9);
    } else {
        $cliente_cpf_cnpj = substr($cliente_cpf_cnpj, 0, 2).'.'.substr($cliente_cpf_cnpj, 2, 3).'.'.substr($cliente_cpf_cnpj, 5, 3).'/'.substr($cliente_cpf_cnpj, 8, 4).'-'.substr($cliente_cpf_cnpj, 12);
    }
}

if (!empty($cliente_cep)) {
    $cliente_cep = substr($cliente_cep, 0, 5).'-'.substr($cliente_cep, 5);
}

$cliente_endereco = array(
    $cliente_endereco,
    $cliente_numero,
    $cliente_complemento,
    $cliente_bairro
);

$cliente_endereco = array_filter($cliente_endereco, function($value) {
    if (!strlen($value)) {
        return false;
    } else {
        return true;
    }
});

$cliente_endereco = implode(', ', $cliente_endereco);
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Certificado de Calibração</title>
        
        <?php
        $plugins = array(
            'jquery3',
            'bootstrap3'
        );
        include 'plugin_loader.php';
        ?>
        
        <style>
        
        .tr-info {
            height: 100px;
        }
        
        .logo {
            max-height: 50px;
            float: right;
            margin-right: 50px;
        }
        
        .logo-fabrica {
            max-height: 50px;
            float: left;
            margin-left: 50px;
        }
        
        .info {
            position: absolute;
            float: left;
            width: 400px;
            margin-left: 50%;
            left: -200px !important;
            top: 5px;
        }
        
        .table-cliente,
        .table-produto,
        .table-tool {
            width: 100%;
        }
        
        .table-cliente > thead > th,
        .table-produto > thead > th {
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .table-tool th {
            text-align: center;
        }
        
        .table-tool td {
            font-weight: normal;
        }
        
        .table-cliente > tbody th,
        .table-produto > tbody th {
            padding-left: 10px;
        }
        
        .table-cliente > tbody td,
        .table-produto > tbody td {
            padding-right: 10px;
            font-weight: normal;
        }
        
        .td-laudo-first,
        .td-tool-first {
            padding-left: 10px;
        }
        
        .tr-laudo-th > th {
            text-align: center;
        }
        
        .no-border {
            border: 0 !important;
        }
        
        @media print {
            @page {
                size: A4;
                margin: 5mm;
            }
            
            .logo { 
                max-width: 140px !important; 
                margin-right: 10px; 
            }
            
            .logo-fabrica { 
                max-width: 140px !important; 
                margin-left: 10px; 
            }
            
            .info { 
                position: fixed; 
            }
            
            table.report { 
                page-break-inside: auto; 
            }
            
            table.report > tbody > tr { 
                page-break-inside: avoid; 
                page-break-after: auto; 
            }
            
            table.report > thead { 
                display: table-header-group; 
            }
            
            table.report > tfoot { 
                display: table-footer-group; 
                padding-top: -20px;
            }
            
            body { box-sizing: border-box; border: 1px solid black; }
        }
        
        </style>
    </head>
    <body>
        <div class='page-layout' >
        <table width="100%" border='1' style="border-collapse:collapse" class="report" >
            <thead>
                <tr>
                    <th class='text-center tr-info' colspan='3' >
                        <img src='<?=$link_logo_fabrica?>' class='logo-fabrica' />
                        <div class='info' >
                            <h5 class='text-center' ><?=$posto_nome?></h5>
                            <h3 class='text-center' >CERTIFICADO DE CALIBRAÇÃO</h3>
                            <h5 class='text-center' >Nº <?=$sua_os?>/<?=$ano_os?></h5>
                        </div>
                        <img src='<?=$link_logo_tdocs?>' class='logo' />
                    </th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <tr>
                        <th colspan='3' class='no-border' >&nbsp;</th>
                    </tr>
                    <tr>
                        <th colspan='3' class='no-border' >&nbsp;</th>
                    </tr>
                    <tr>
                        <th colspan='3' class='no-border' >&nbsp;</th>
                    </tr>
                    <th colspan='3' class='no-border'>
                        <table style='width: 100%; table-layout: fixed;'>
                            <tbody>
                                <tr>
                                    <th class='text-center' style='padding-left: 0px;'>
                                        ____________________________________________
                                    </th>
                                    <th class='text-center'>
                                        DATA: ____/____/________
                                    </th>
                                </tr>
                                <tr>
                                    <th class='text-center' style='padding-left: 0px;'>
                                        <?=$tecnico_nome?>
                                    </th>
                                    <th class='text-center'>
                                        &nbsp;
                                    </th>
                                </tr>
                                <tr>
                                    <th class='text-center' style='padding-left: 0px;'>
                                        <?=$posto_nome?>
                                    </th>
                                    <th class='text-center'>
                                        &nbsp;
                                    </th>
                                </tr>
                            </tbody>
                        </table>
                    </th>
                </tr>
                <tr>
                    <th colspan='3' class='no-border' >&nbsp;</th>
                </tr>
                <tr>
                    <th class='text-center' colspan='3'>
                        <?=$posto_endereco?>, <?=$posto_numero?> <?=$posto_complemento?> / Bairro: <?=$posto_bairro?> / Município: <?=$posto_cidade?>/<?=$posto_estado?> / CEP: <?=(!empty($posto_cep)) ? substr($posto_cep, 0, 5).'-'.substr($posto_cep, 5) : null?>
                    </th>
                </tr>
            </tfoot>
            <tbody>
                <tr>
                    <th colspan='3' >&nbsp;</th>
                </tr>
                <tr>
                    <th class='tr-cliente' colspan='3' >
                        <table class='table-cliente' >
                            <thead>
                                <tr>
                                    <th class='text-center' colspan='2'>DADOS DO CLIENTE ou EMPRESA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th style='width: 150px;' >NOME:</th>
                                    <td><?=strtoupper($cliente_nome)?></td>
                                </tr>
                                <tr>
                                    <th><?=($consumidor_revenda == 'C') ? 'CPF' : 'CNPJ'?>:</th>
                                    <td><?=$cliente_cpf_cnpj?></td>
                                </tr>
                                <tr>
                                    <th>ENDEREÇO:</th>
                                    <td><?=strtoupper($cliente_endereco)?></td>
                                </tr>
                                <tr>
                                    <th>CIDADE:</th>
                                    <td><?=$cliente_cidade?>/<?=$cliente_estado?> - CEP: <?=$cliente_cep?></td>
                                </tr>
                            </tbody>
                        </table>
                    </th>
                </tr>
                <tr>
                    <th colspan='3' >&nbsp;</th>
                </tr>
                <tr>
                    <th class='tr-produto' colspan='3' >
                        <table class='table-produto' >
                            <thead>
                                <tr>
                                    <th class='text-center' colspan='2'>IDENTIFICAÇÃO E CARACTERÍSTICAS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th style='width: 150px;' >EQUIPAMENTO:</th>
                                    <td><?=$produto_descricao?></td>
                                </tr>
                                <tr>
                                    <th>MODELO:</th>
                                    <td><?=$produto_referencia?></td>
                                </tr>
                                <tr>
                                    <th>Nº SÉRIE:</th>
                                    <td><?=$serie?></td>
                                </tr>
                                <tr>
                                    <th>Nº ORÇAMENTO:</th>
                                    <td><?=$sua_os?></td>
                                </tr>
                            </tbody>
                        </table>
                    </th>
                </tr>
                <tr>
                    <th colspan='3' class='no-border'>&nbsp;</th>
                </tr>
                <tr>
                    <th class='text-center no-border' colspan='3'>RESULTADOS DA CALIBRAÇÃO</th>
                </tr>
                <tr class='tr-laudo-th'>
                    <th>ENSAIOS/INSPEÇÃO</th>
                    <th>ESPECIFICAÇÃO</th>
                    <th>RESULTADO</th>
                </tr>
                <?php
                $answers = array_map(function($v) {
                    if (!is_array($v)) {
                        return utf8_decode($v);
                    }
                    
                    return $v;
                }, json_decode($answers, true));
		$form = preg_replace('/[[:cntrl:]]/', '', $form);

                $form = json_decode($form, true);
                $tools = array();
                
                foreach ($form as $field) {
                    if ($field['role'] != 'certificado_calibracao') {
                        continue;
                    }
                    
                    $unit = null;
                    $specification = null;
                    
                    $value = $answers[$field['name']];
                    
                    switch ($field['type']) {
                        case 'date':
                            $value = date('d/m/Y', strtotime($value));
                            break;
                            
                        case 'number':
                            if (empty($field['decimalPlaces'])) {
                                $field['decimalPlaces'] = 0;
                            }
                            
                            $value = number_format($value, $field['decimalPlaces'], '.', '');
                        
                            if (!is_null($field['unit'])) {
                                $unit = utf8_decode(" {$field['unit']}");
                            }
                            
                            if (!is_null($field['minimumValue']) && !is_null($field['maximumValue'])) {
                                $specification = "{$field['minimumValue']}{$unit} - {$field['maximumValue']}{$unit}";
                            } else if (!is_null($field['minimumValue'])) {
                                $specification = "min. {$field['minimumValue']}{$unit}";
                            } else if (!is_null($field['maximumValue'])) {
                                $specification = "max. {$field['maximumValue']}{$unit}";
                            }
                            
                            $value .= $unit;
                            
                            if (!is_null($field['tools'])) {
                                $tools[] = $answers[$field['name'].'-tool'];
                            }
                            break;
                            
                        case 'select':
                            if ($field['multiple']) {
                                $values = array();
                                
                                foreach ($value as $v) {
                                    $values[] = utf8_decode($field['values'][array_search($v, array_column($field['values'], 'value'))]['label']);
                                }
                                
                                $value = implode(', ', $values);
                            } else {
                                $value = utf8_decode($field['values'][array_search($value, array_column($field['values'], 'value'))]['label']);
                            }
                            break;
                            
                        case 'checkbox-group':
                            if (is_array($value)) {
                                $values = array();
                                
                                foreach ($value as $v) {
                                    if ($v == 'outro') {
                                        $values[] = 'Outro: '.$answers[$field['name'].'-other'];
                                    } else {
                                        $values[] = utf8_decode($field['values'][array_search($v, array_column($field['values'], 'value'))]['label']);
                                    }
                                }
                                
                                $value = implode(', ', $values);
                            } else {
                                $value = utf8_decode($field['values'][array_search($value, array_column($field['values'], 'value'))]['label']);
                            }
                            break;
                            
                        case 'radio-group':
                            if ($value == 'outro') {
                                $value = 'Outro: '.$answers[$field['name'].'-other'];
                            } else {
                                $value = utf8_decode($field['values'][array_search($value, array_column($field['values'], 'value'))]['label']);
                            }
                            break;
                            
                        case 'starRating':
                            $specification = '0 - 5';
                            break;
                    }
                    ?>
                    <tr class='tr-laudo'>
                        <td class='td-laudo-first' ><?=utf8_decode($field['label'])?></td>
                        <td class='text-center'><?=$specification?></td>
                        <td class='text-center'><?=$value?></td>
                    </tr>
                <?php
                }
                ?>
                <tr>
                    <th colspan='3' class='no-border' >&nbsp;</th>
                </tr>
                <tr>
                    <th class='text-center no-border' colspan='3'>
                        OS DOCUMENTOS RELATIVOS À RASTREABILIDADE ESTÃO EM NOSSOS ARQUIVOS E DISPONÍVEIS PARA CONSULTA.
                        <br/>
                    </th>
                </tr>
                <tr>
                    <th class='text-center no-border' colspan='3'>
                        ESTE CERTIFICADO É VÁLIDO SOMENTE PARA O OBJETO ENSAIADO.<br/>
                    </th>
                </tr>
                <tr>
                    <th class='text-center no-border' colspan='3'>
                        TOTAL OU PARCIAL REPRODUÇÃO DO MESMO, SOMENTE COM A AUTORIZAÇÃO DA IBRAMED.
                    </th>
                </tr>
                <?php if (in_array($login_fabrica, [175])) { ?>
                <tr>
                    <th class='text-center no-border' colspan='3'>
                        VERIFIQUE NO MANUAL DO SEU EQUIPAMENTO A PERIODICIDADE DE CALIBRAÇÃO RECOMENDADO PELA IBRAMED.
                    </th>
                </tr>
                <?php } ?> 
                <?php
                if (count($tools) > 0) {
                ?>
                    <tr>
                        <th colspan='3' class='no-border' >&nbsp;</th>
                    </tr>
                    <tr>
                        <th class='text-center no-border' colspan='3'>PADRÕES UTILIZADOS</th>
                    </tr>
                    <tr>
                        <th class='tr-tool' colspan='3'>
                            <table class='table-tool'>
                                <thead>
                                    <tr>
                                        <th>INSTRUMENTO</th>
                                        <th>FABRICANTE</th>
                                        <th>MODELO</th>
                                        <th>CERTIFICADO</th>
                                        <th>COD. INSTR</th>
                                        <th>VALIDADE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $tools = array_unique($tools);
                                    
                                    foreach ($tools as $tool) {
                                        $sqlTool = "
                                            SELECT *
                                            FROM tbl_posto_ferramenta
                                            WHERE fabrica = {$login_fabrica} 
                                            AND posto_ferramenta = {$tool}
                                        ";
                                        $resTool = pg_query($con, $sqlTool);
                                        
                                        if (pg_num_rows($resTool) > 0) {
                                            $tool = pg_fetch_assoc($resTool);
                                            ?>
                                            <tr>
                                                <td class='td-tool-first' ><?=strtoupper($tool['descricao'])?></td>
                                                <td class='text-center'><?=strtoupper($tool['fabricante'])?></td>
                                                <td class='text-center'><?=strtoupper($tool['modelo'])?></td>
                                                <td class='text-center'><?=strtoupper($tool['certificado'])?></td>
                                                <td><?=$tool['numero_serie']?></td>
                                                <td class='text-center'><?=date('d/m/Y', strtotime($tool['validade_certificado']))?></td>
                                            </tr>
                                        <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </th>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </body>
    <script>
        
    window.print();
        
    </script>
</html>
