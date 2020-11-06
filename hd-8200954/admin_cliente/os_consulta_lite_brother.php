<?php
// Report simple running errors
//error_reporting(E_ALL);

$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../':'');

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once "funcoes.php";
include "autentica_admin.php";

$layout_menu = "callcenter";
$title = "Seleção de Parâmetros para Relação de Ordens de Serviços Lançadas";
$host = "";

if (strstr($_SERVER['PHP_SELF'],"admin_cliente")) {
    include "cabecalho_new.php";
    $host   = $_SERVER['SCRIPT_NAME'];
    $host   = str_replace('admin_cliente','admin',$host);
    $host   = str_replace('/os_consulta_lite.php','',$host)."/";
} else {
    include "cabecalho_new.php";
}

$plugins = array(
    "jquery",
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "multiselect",
    "dataTable",
    "alphanumeric"
);
//include ADMCLI_BACK."plugin_loader.php";
include("../admin/plugin_loader.php");

include "javascript_pesquisas_novo.php";
// include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */

// TIPO DE ATENDIMENTO
$sqlTipoAtendimento = "SELECT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao";
$pg_res = pg_query($con, $sqlTipoAtendimento);
$listaDeTiposDeAtendimentos = pg_fetch_all($pg_res);

// STATUS DA OS
$sqlStatusdaOS = "SELECT status_checkpoint, descricao, cor FROM tbl_status_checkpoint WHERE status_checkpoint IN (0,1,2,3,4,8,9,28)";
$pg_res = pg_query($con, $sqlStatusdaOS);
$listaDeStatusDaOS = pg_fetch_all($pg_res); 

// LINHA
$sqlLinha = "SELECT linha, nome from tbl_linha where fabrica = $login_fabrica and ativo = true order by nome";
$pg_res = pg_query($con, $sqlLinha);
$listaDeLinhas = pg_fetch_all($pg_res);

// FAMÃƒÂLIA
$sqlFamilia = "SELECT familia, descricao from tbl_familia where fabrica = $login_fabrica and ativo = true order by descricao";
$pg_res = pg_query($con, $sqlFamilia);
$listaDeFamilias = pg_fetch_all($pg_res);  

// ESTADOS
 $listaDeEstadosDoBrasil = [
    "AC" => "AC - Acre",
    "AL" => "AL - Alagoas",
    "AM" => "AM - Amazonas",
    "AP" => "AP - Amapá",
    "BA" => "BA - Bahia",
    "CE" => "CE - Ceará",
    "DF" => "DF - Distrito Federal",
    "ES" => "ES - Espírito Santo",
    "GO" => "GO - Goiás",
    "MA" => "MA - Maranhão",
    "MG" => "MG - Minas Gerais",
    "MS" => "MS - Mato Grosso do Sul",
    "MT" => "MT - Mato Grosso",
    "PA" => "PA - Pará",
    "PB" => "PB - Paraíba",
    "PE" => "PE - Pernambuco",
    "PI" => "PI - Piauí",
    "PR" => "PR - Paraná",
    "RJ" => "RJ - Rio de Janeiro",
    "RN" => "RN - Rio Grande do Norte",
    "RO" => "RO - Rondônia",
    "RR" => "RR - Roraima",
    "RS" => "RS - Rio Grande do Sul",
    "SC" => "SC - Santa Catarina",
    "SE" => "SE - Sergipe",
    "SP" => "SP - São Paulo",
    "TO" => "TO - Tocantins"
];

$listaDeLegendas = [
    ['descricao' => 'Reincidências', 'cor' => '#D7FFE1'],
    ['descricao' => 'OSs abertas há mais de 25 dias sem data de fechamento', 'cor' => '#91C8FF'],
    ['descricao' => 'OS reincidente e aberta a mais de 25 dias', 'cor' => '#CC9900'],
    ['descricao' => 'OS com Troca de Produto', 'cor' => '#FFCC66'],
    ['descricao' => 'Os com Ressarcimento', 'cor' => '#CCCCFF']
];

// $field = ÃƒÂ© um array que recebe as condiÃƒÂ§oes. Cada linha ÃƒÂ© uma condiÃƒÂ§ÃƒÂ£o.
// retorna o sql montado com as condiÃƒÂ§ÃƒÂµes passadas por porÃƒÂ¢metro
function mountSqlWithConditions(array $conditionsFields = null, array $joinsFields = null, array $propertiesFields = null): string {
    global $login_fabrica;

    $sql = "SELECT
            {properties}
            tbl_os.status_checkpoint,
            tbl_os.os_reincidente,
            tbl_os.os,
            tbl_os.fabrica, 
            tbl_os.sua_os, 
            tbl_os.nota_fiscal, 
            tbl_os.os_numero,
            --tbl_os_troca.troca_revenda,
            TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura,
            TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento,
            TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY') as data_nf,
            replace(tbl_os_produto.serie, 'Ã¢â‚¬â€¹', '') as serie,
            tbl_os.consumidor_estado,
            tbl_os.consumidor_revenda,
            tbl_os.consumidor_nome,
            tbl_os.revenda_nome,
            tbl_os.tipo_atendimento,
            tbl_os.os_posto,
            tbl_tipo_atendimento.descricao, 
            tbl_posto.posto, 
            tbl_posto_fabrica.codigo_posto, 
            tbl_posto_fabrica.contato_estado, 
            tbl_posto_fabrica.contato_cidade, 
            tbl_posto.nome AS posto_nome, 
            tbl_posto.estado, 
            tbl_produto.referencia AS produto_referencia, 
            tbl_produto.descricao AS produto_descricao, 
            tbl_produto.voltagem AS produto_voltagem, 
            status_os_ultimo AS status_os 
            FROM tbl_os 
            LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
            JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
            LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto and tbl_produto.fabrica_i = {$login_fabrica}

            --JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha and tbl_linha.fabrica = {$login_fabrica}
            --JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia and tbl_familia.fabrica = {$login_fabrica}

            LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os and tbl_os_extra.i_fabrica = {$login_fabrica}
            JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica and tbl_fabrica.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
            LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = {$login_fabrica}
            --LEFT JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os
            {joins}
            WHERE tbl_os.fabrica = {$login_fabrica}
            AND (tbl_os_extra.status_os NOT IN (13,15) OR tbl_os_extra.status_os IS NULL) 
            AND (tbl_os.excluida IS NOT TRUE OR (tbl_os.excluida IS TRUE AND tbl_os.status_checkpoint = 28)) 
            {conditions}
            ORDER BY tbl_os.os DESC";

            // Verifica se existe condiÃƒÂ§oes, caso exista montra uma string para ser substituida no sql
            $conditions = ' ';
            if( $conditionsFields ){
                foreach($conditionsFields as $fieldWithCondition){
                    $conditions .= $fieldWithCondition . ' ';
                }
                $conditions .= ' ';
            }else{
                $conditions = null;
            }
            
            // Verifica se existe algum join, casoo exista monta uma string para ser substituida no sql
            $joins = '';
            if( $joinsFields ){
                foreach($joinsFields as $fieldWithCondition){
                    $joins .= $fieldWithCondition . ' ';
                }
                $joins .= ' ';
            }else{
                $joins = null;
            }

            // Verifica se existe alguma propertie, casoo exista monta uma string para ser substituida no sql
            $properties = '';
            if( $propertiesFields ){
                foreach($propertiesFields as $fieldWithCondition){
                    $properties .= $fieldWithCondition . ', ';
                }
            }else{
                $properties = null;
            }

            $sql = str_replace('{conditions}', $conditions, $sql);
            $sql = str_replace('{properties}', $properties, $sql);
            $sql = str_replace('{joins}', $joins, $sql);

            return $sql;
}

function mountCsv($data, $fields = null, $listaStatus = null, $listaDeLegendas = null){
    $defaultFields = [
        'os' => 'OS',
        'serie' => 'SERIE',
        'abertura' => 'DATA ABERTURA',
        'fechamento' => 'DATA FECHAMENTO',
        'descricao' => 'TIPO ATENDIMENTO',
        'consumidor_revenda' => 'C/R',
        'posto_nome' => 'NOME POSTO',
        'contato_cidade' => 'CIDADE',
        'contato_estado' => 'ESTADO',
        'revenda_nome' => 'CONSUMIDOR/REVENDA',
        'nota_fiscal' => 'NF',
        'produto_descricao' => 'PRODUTO',
        'status_checkpoint' => 'STATUS',
        'situacao' => 'SITUACAO'
    ];

    // Se nao foi passado nenhum header entÃ£o coloca os headers default
    if( $fields == null ) 
        $fields = $defaultFields;

    $host   = $_SERVER['SCRIPT_NAME'];
    $host   = str_replace('admin_cliente','admin',$host);
    $host   = str_replace('/os_consulta_lite.php', '', $host);

    $path_2 = getcwd();
    $path_2 = str_replace('admin_cliente','admin/', $path_2);

    $arquivo_nome = "consulta-os-{$login_fabrica}-admin-cliente.csv";

    $arquivo_completo = $path_2 . 'xls/' . $arquivo_nome;
    $caminho_download = $host . '/xls/' . $arquivo_nome;

    // Inicia o arquivo
    $arquivo = fopen($arquivo_completo, "w+");

    // Monta o cabecalho do arquivo
    $header = array_values($fields);
    fputcsv($arquivo, $header, ";");

    // Itera e adiciona o conteudo
    foreach ($data as $row) {
        $resultRow = [];

        foreach ($fields as $key => $value) {
            if( $key == 'produto_descricao' ){
                $resultRow[$key] = $row['produto_referencia'] . ' - ' . $row['produto_descricao'];
                continue;
            }

            if( $key == 'posto_nome' ){
                $resultRow[$key] = $row['codigo_posto'] . ' - ' . $row['posto_nome'];
                continue; 
            }

            if( $key == 'status_checkpoint' ){
                if( !empty($listaStatus) ){
                    foreach ($listaStatus as $status) {
                        if( $status['status_checkpoint'] == $row['status_checkpoint'] ){
                            $resultRow[$key] = $status['descricao'];
                            break;
                        }
                    }
                }
                continue;
            }

            if( $key == 'situacao' ){
                $arrayInfo = verifySubtitles($row, $listaDeLegendas);
                $resultRow[$key] = $arrayInfo['descricao'];
                continue;
            }


            $resultRow[$key] = $row[$key];             
        }

        fputcsv($arquivo, $resultRow, ";");
    }

    // fecha a stream com o arquivo
    fclose($arquivo);

    return $caminho_download;
}

function verifySubtitles(array $os, array $legendas) : array {
    global $con;

    $arraySubtitles = [];
    $data_abertura = DateTime::createFromFormat('d/m/Y', $os['abertura']);
    $data_intervalo = new DateTime('now');
    $diff = $data_abertura->diff($data_intervalo);

    // Verifica OS Reincidente
    if( $os['os_reincidente'] == 't' ) $arraySubtitles = $legendas[0];

    // Verifica OS aberta a mais de 25 dias e sem data de fechamento
    if( strlen($os['fechamento']) <= 0 AND $diff->days > 25 ) $arraySubtitles = $legendas[1];

    // Verifica OS reincidente e aberta a mais de 25 dias
    if( $diff->days > 25 AND $os['os_reincidente'] == 't' AND strlen($os['fechamento'] <= 0) ) $arraySubtitles = $legendas[2];

    // SQL para verificaÃ§Ãµes abaixo
    $pg_res_ = pg_query($con, "SELECT os FROM tbl_os_troca WHERE os = {$os['os']}");
    $res = pg_fetch_all($pg_res_);

    // Verifica OS com troca de produto
    if( $res ) $arraySubtitles = $legendas[3];

    //Verifica OS com ressarcimento
    if( $res AND $res['ressarcimento'] == 't' ) $arraySubtitles = $legendas[4];

    return $arraySubtitles;
}

function validateOsDate( $data_os ){
    $data_os = DateTime::createFromFormat('d/m/Y', $data_os);
    $data_validacao = DateTime::createFromFormat('d/m/Y', '01/04/2019');

    return ($data_os > $data_validacao );
}

class FieldValidation extends Exception{}

// Recupera os campos do formulÃƒÂ¡rio enviados pelo mÃƒÂ©todo POST
if( $_POST['action'] == 'formulario_pesquisa' ){
    $sua_os             = filter_input(INPUT_POST, 'sua_os', FILTER_SANITIZE_NUMBER_INT);
    $serie              = filter_input(INPUT_POST, 'serie');
    $nf_compra          = filter_input(INPUT_POST, 'nf_compra');
    $consumidor_cpf     = filter_input(INPUT_POST, 'consumidor_cpf');
    $tipo_atendimento   = filter_input(INPUT_POST, 'tipo_atendimento');
    $status_checkpoint  = filter_input(INPUT_POST, 'status_checkpoint');
    $tipo_os            = filter_input(INPUT_POST, 'tipo_os');
    $linha              = filter_input(INPUT_POST, 'linha');
    $familia            = filter_input(INPUT_POST, 'familia');
    $data_tipo          = filter_input(INPUT_POST, 'data_tipo');
    $data_inicial       = filter_input(INPUT_POST, 'data_inicial');
    $data_final         = filter_input(INPUT_POST, 'data_final');
    $os_aberta          = filter_input(INPUT_POST, 'os_aberta');
    $estado             = filter_input(INPUT_POST, 'estado');
    $codigo_posto       = filter_input(INPUT_POST, 'codigo_posto');
    $nome_posto         = filter_input(INPUT_POST, 'nome_posto');
    $nome_consumidor    = filter_input(INPUT_POST, 'nome_consumidor');
    $referencia_produto = filter_input(INPUT_POST, 'referencia_produto');
    $descricao_produto  = filter_input(INPUT_POST, 'descricao_produto');
    $os_situacao        = filter_input(INPUT_POST, 'os_situacao');
    $pre_os             = filter_input(INPUT_POST, 'pre_os');
    $revenda_cnpj       = filter_input(INPUT_POST, 'revenda_cnpj');
    $gerar_excel        = filter_input(INPUT_POST, 'gerar_excel');

    try{
        // Inicio das validaÃ§Ãµes
        if( $data_inicial AND $data_final ){
            $data_inicial_temp = explode('/', $data_inicial);
            $data_final_temp   = explode('/', $data_final);

            // ValidaÃ§Ã£o da data_inicial e data_final
             if( !checkdate($data_inicial_temp[1], $data_inicial_temp[0], $data_inicial_temp[2]) OR !checkdate($data_final_temp[1], $data_final_temp[0], $data_final_temp[2]) ) {
                $data_inicial = $data_final = null;
                throw new FieldValidation("Data Inicial ou Final Inválida");
            } else {
                $data_inicial = DateTime::createFromFormat('d/m/Y', $data_inicial); 
                $data_final   = DateTime::createFromFormat('d/m/Y', $data_final);

                if( $data_inicial < (DateTime::createFromFormat('d/m/Y', '01/04/2019')) )
                    throw new FieldValidation("Data inicial deve ser maior ou igual á 01/04/2019");

                if( $data_final->diff($data_inicial)->m > 6 )
                    throw new FieldValidation("A diferença de datas não pode ser maior que 6 meses");
            }

        }

        // Verifica se foi passado o nÃƒÂºmero da os e realiza a pesquisa pelo nÃƒÂºmero
        if( $sua_os ){
            $sql = mountSqlWithConditions([
                "AND (tbl_os.os_numero = '{$sua_os}' OR tbl_os.sua_os = '{$sua_os}' OR tbl_os.os = '{$sua_os}')"
            ]);

            //echo nl2br($sql);
            $pg_res = pg_query($con, $sql);
            $resultadoPesquisa = pg_fetch_all($pg_res);
            //echo nl2br($sql);

            // Verifica a data da OS caso o usuÃ¡rio tenha tentado consultar uma OS antiga atravÃ©s do nÃºmero da OS
            if( $resultadoPesquisa ){
                if( !validateOsDate($resultadoPesquisa[0]['abertura']) ){
                    $resultadoPesquisa = [];
                    throw new FieldValidation("Os não habilitada para esta consulta");
                }
            }
            
            if( $gerar_excel )
                $caminho_download = mountCsv($resultadoPesquisa, null, $listaDeStatusDaOS, $listaDeLegendas);

        // Verifica pelo nÃƒÂºmero de sÃƒÂ©rie
        }else if( $serie ){
            $sql = mountSqlWithConditions([
                "AND tbl_os.serie = '{$serie}'"
            ]);

            $pg_res = pg_query($con, $sql);
            $resultadoPesquisa = pg_fetch_all($pg_res);

            if( $resultadoPesquisa ){
                if( !validateOsDate($resultadoPesquisa[0]['abertura']) ){
                    $resultadoPesquisa = [];
                    throw new FieldValidation("Os nÃ£o habilitada para esta consulta");
                }
            }

            if( $gerar_excel )
                $caminho_download = mountCsv($resultadoPesquisa, null, $listaDeStatusDaOS, $listaDeLegendas);

            //echo nl2br($sql);
        // Verifica pelo CPF ou CNPJ
        }else if( $data_inicial AND $data_final ){
            $conditionsArray = [];
            $joinsArray      = [];
            $propertiesArray = [];

            if( $data_tipo ){
                if( $data_tipo == 'abertura' )
                    $dateType = 'data_abertura';
                elseif( $data_tipo == 'digitacao' )
                    $dateType = 'data_digitacao';
            }else
                $dateType = 'data_abertura';

            if( $dateType )
                $conditionsArray[] = "AND tbl_os.{$dateType} >= '{$data_inicial->format('Y/m/d')}' AND tbl_os.{$dateType} <= '{$data_final->format('Y/m/d')}'";

            if( $nome_consumidor )
                if( !$codigo_posto AND !$referencia_produto )
                    throw new FieldValidation("Especifique o Posto ou o Produto");

            if( $consumidor_cpf )
                $conditionsArray[] = "AND tbl_os.consumidor_cpf = '{$consumidor_cpf}'";

            if( $tipo_atendimento )
                $conditionsArray[] = "AND tbl_tipo_atendimento.tipo_atendimento = {$tipo_atendimento}";

            if( $tipo_os )
                $conditionsArray[] = "AND consumidor_revenda = '{$tipo_os}'";

            if( $nf_compra )
                $conditionsArray[] = "AND tbl_os.nota_fiscal = '{$nf_compra}'";
            
            if( $status_checkpoint )
                $conditionsArray[] = "AND tbl_os.status_checkpoint = {$status_checkpoint}";

            if( $estado )
                $conditionsArray[] = "AND tbl_os.consumidor_estado IN ('{$estado}')";

            if( $os_situacao ){
                $joinsArray[] = "JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato and tbl_extrato.fabrica = {$login_fabrica}";

                if( $os_situacao == 'APROVADA' ){
                    $conditionsArray[] = "AND tbl_extrato.aprovado IS NOT NULL";
                }else if( $os_situacao == 'PAGA'){
                    $joinsArray[] = "JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
                    $conditionsArray[] = "AND tbl_extrato_financeiro.data_envio IS NOT NULL"; 
                }
            }

            if( $linha ){
                $conditionsArray[] = "AND tbl_linha.linha = {$linha}";
                $joinsArray[] = "JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha and tbl_linha.fabrica = {$login_fabrica}";
            }

            if( $familia ){
                $conditionsArray[] = "AND tbl_familia.familia = {$familia}";
                $joinsArray[] = "JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia and tbl_familia.fabrica = {$login_fabrica}";
            }

            if( $os_troca )
                $joinsArray[] = "JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os";

            if( $os_aberta )
                $conditionsArray[] = "AND tbl_os.os_fechada IS FALSE AND tbl_os.excluida IS NOT TRUE";

            if( $codigo_posto ){
                $pg_res   = pg_query($con, "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '{$codigo_posto}'");
                $posto_id = pg_fetch_assoc($pg_res, 0);
                $conditionsArray[] = "AND tbl_os.posto in ({$posto_id['posto']})";
            }

            if( $referencia_produto ){
                $pg_res     = pg_query($con, "SELECT produto FROM tbl_produto WHERE referencia = '{$referencia_produto}'");
                $produto_id = pg_fetch_assoc($pg_res, 0);
                $conditionsArray[] = "AND tbl_produto.referencia = '{$referencia_produto}' AND tbl_produto.produto = '{$produto_id['produto']}'";
            }

            if( $nome_consumidor )
                $conditionsArray[] = "AND upper(tbl_os.consumidor_nome) = upper('{$nome_consumidor}')";

            if( $revenda_cnpj )
                $conditionsArray[] = "AND tbl_os.revenda_cnpj LIKE '{$revenda_cnpj}%'";
            
            $sql = mountSqlWithConditions($conditionsArray, $joinsArray);
            //echo nl2br($sql);

            // Executa o sql
            $pg_res = pg_query($con, $sql);
            $resultadoPesquisa = pg_fetch_all($pg_res);

            if( $gerar_excel )
                $caminho_download = mountCsv($resultadoPesquisa, null, $listaDeStatusDaOS, $listaDeLegendas);

            //var_dump($resultadoPesquisa, $data_inicial, $data_final);
            //echo nl2br($sql);

        }else{
            throw new FieldValidation("Informe a data inicial e final para pesquisa"); 
        }
    }catch(FieldValidation $e){
        $msg = $e->getMessage();
    }
}

if( $_POST['action'] == 'formulario_pre_os' ){
   $pre_os = filter_input(INPUT_POST, 'pre_os');

    if ( !empty($pre_os) ) 
        $pre_os_sql = "AND tbl_hd_chamado.hd_chamado = {$pre_os}";



   $sql = "SELECT
    tbl_hd_chamado.hd_chamado, 
    '' as sua_os, 
    tbl_hd_chamado_extra.serie, 
    tbl_hd_chamado_extra.nota_fiscal,
    TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
    TO_CHAR(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') AS data_nf,
    tbl_hd_chamado_extra.posto,
    tbl_posto_fabrica.codigo_posto,
    tbl_posto_fabrica.credenciamento,
    tbl_posto.nome AS posto_nome,
    tbl_hd_chamado_extra.fone as consumidor_fone,
    tbl_hd_chamado_extra.nome,
    tbl_hd_chamado_extra.array_campos_adicionais,
    tbl_marca.nome as marca_nome,
    tbl_produto.referencia as produto_referencia,
    tbl_produto.descricao as produto_descricao
    FROM tbl_hd_chamado_extra
    JOIN tbl_hd_chamado using(hd_chamado)
    LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto AND tbl_produto.fabrica_i= {$login_fabrica}
    LEFT JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca
    LEFT JOIN tbl_posto ON  tbl_posto.posto = tbl_hd_chamado_extra.posto
    LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
    LEFT JOIN tbl_os ON tbl_os.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_os.fabrica = {$login_fabrica}
    WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
    AND tbl_hd_chamado_extra.abre_os = 't'
    AND tbl_os.os IS NULL
    {$pre_os_sql}
    ";

    $pg_res = pg_query($con, $sql);
    $resultadoPesquisaPreOs = pg_fetch_all($pg_res);

    if( $gerar_excel )
        $caminho_download = mountCsv($resultadoPesquisaPreOs, [
            'hd_chamado' => 'N ATENDIMENTO',
            'serie' => 'SERIE',
            'data' => 'DATA ABERTURA',
            'df' => 'DATA FECHAMENTO',
            'posto_nome' => 'NOME POSTO',
            'nome' => 'CONSUMIDOR/REVENDA',
            'nota_fiscal' => 'NF',
            'produto_descricao' => 'PRODUTO'
    ]);
}
?>

<!-- NEW -->

<style>
    .container-box{ list-style: none; }
    .container-box li{ display: flex; margin: 5px;}

    .box{ width: 23px; height: 17px; background-color: black; border: 1px solid black; margin-right: 5px}
    .box-subtitles{ width: 60px }
    .box-sm { width: 14px; height: 14px; }

    #tabela thead tr th{
        font-size: 10px;
        text-transform: uppercase;
        padding: 0 15px 0 0;
        margin: 0;
    }
</style>

<div class="alert alert-warning">
    <h4>Data mínima para pesquisa 01/04/2019</h4>   
</div>

<!-- Mensagens de erro -->
<?php if( !empty( $msg ) ) { ?>
<div class="alert alert-danger">
    <h4 style="text-transform: uppercase;"><?= $msg ?></h4>
</div>
<?php } ?>

<!-- Nenhuma OS encontrada -->
<?php if( (isset($resultadoPesquisa) AND empty($resultadoPesquisa)) OR  (isset($resultadoPesquisaPreOs) AND empty($resultadoPesquisaPreOs)) ) { ?>
<div class="alert alert-danger">
    <h4 style="text-transform: uppercase;">
        Nenhuma OS encontrada
    </h4>
</div>
<?php } ?>

<div class="container tc_container">
    <div class="tc_formulario">
        <form class="form-search form-inline" name="formulario_pesquisa" id="formulario_pesquisa" method="POST" action="">
            <div class="titulo_tabela">Parâmetros de Pesquisa</div>
            <br>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span3">
                    <label for="sua_os">Número da OS</label>
                    <input type="text" maxlength="20" id="sua_os" name="sua_os" class="input-block-level" value='<?=$sua_os ?? ''?>'>    
                </div>
                <div class="span3">
                    <label for="serie">Número de Série</label>
                    <input type="text" maxlength="20" id="serie" name="serie" class="input-block-level" value="<?=$serie ?? ''?>">
                </div>
                <div class="span2">
                    <label for="nf_compra">NF. Compra</label>
                    <input type="text" maxlength="20" id="nf_compra" name="nf_compra" class="input-block-level" value="<?=$nf_compra ?? ''?>">
                </div>
                <div class="span2"></div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4">
                    <label for="consumidor_cpfconsumidor_cpf">CPF/CNPJ Consumidor</label>
                    <input type="text" maxlength="14" id="consumidor_cpf" name="consumidor_cpf" class="input-block-level" value="<?=$consumidor_cpf ?? ''?>"> 
                </div>
                <div class="span4">
                    <label for="tipo_os">Tipo de OS</label>
                    <select id="tipo_os" name="tipo_os" class="input-block-level">
                        <option value=""> Todas </option>
                        <option value="C" <?= !empty($tipo_os) == 'C' ? 'selected' : null ?> > Consumidor </option>
                        <option value="R" <?= !empty($tipo_os) == 'R' ? 'selected' : null ?> > Revenda </option>
                    </select>
                </div>
                <div class="span2"></div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4">
                    <label for="tipo_atendimento">Tipo de Atendimento</label>
                    <select name="tipo_atendimento" class="input-block-level">
                        <option value=""></option>
                        
                        <?php foreach($listaDeTiposDeAtendimentos as $atendimento){ ?>
                            <?php if( $tipo_atendimento AND $tipo_atendimento == $atendimento['tipo_atendimento'] ) {?>
                                <option value="<?= $atendimento['tipo_atendimento'] ?>" selected> <?= $atendimento['descricao'] ?> </option>
                            <?php }else{ ?>
                                <option value="<?= $atendimento['tipo_atendimento'] ?>"> <?= $atendimento['descricao'] ?> </option>
                            <?php } ?>
                        <?php } ?>
                    
                    </select>    
                </div>
                <div class="span4">
                    <label for="status_checkpoint">Status da OS</label>
                    <select name="status_checkpoint" class="input-block-level">
                        <option value=""></option>
                
                        <?php foreach($listaDeStatusDaOS as $statusOS){ ?>
                            <?php if( $status_checkpoint AND $status_checkpoint == $statusOS['status_checkpoint'] ) { ?>
                                <option value="<?= $statusOS['status_checkpoint'] ?>" selected> <?= $statusOS['descricao'] ?> </option>
                            <?php }else{ ?>
                                <option value="<?= $statusOS['status_checkpoint'] ?>"> <?= $statusOS['descricao'] ?> </option>
                            <?php } ?>
                        <?php } ?>

                    </select>
                </div>
                <div class="span2"></div>
            </div>
            <input type="hidden" name="action" value="formulario_pesquisa">
            <button class="btn" type="button" onclick="$('#formulario_pesquisa').submit()"> Pesquisar </button>

            <div class="subtitulo" style="max-height: 15px; height: 15px;"></div>

            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span3">
                    <label for="linha">Linha</label>
                    <select name="linha" class="input-block-level">
                        <option value=""></option>

                        <?php foreach($listaDeLinhas as $linha_) { ?>
                            <?php if( $linha AND $linha == $linha_['linha'] ) { ?>
                                <option value="<?= $linha_['linha'] ?>" selected> <?= $linha_['nome'] ?> </option>
                            <?php }else{?>
                                <option value="<?= $linha_['linha'] ?>"> <?= $linha_['nome'] ?> </option>
                            <?php } ?>
                        <?php } ?>
                    
                    </select>
                </div>
                <div class="span3">
                    <label for="familia">Família</label>
                    <select name="familia" class="input-block-level">
                       <option value=""></option>

                       <?php foreach($listaDeFamilias as $familia_) { ?>
                            <?php if( $familia AND $familia == $familia_['familia'] ){ ?>
                                <option value="<?= $familia_['familia'] ?>" selected> <?= $familia_['descricao'] ?> </option>
                            <?php }else{ ?>
                                <option value="<?= $familia_['familia'] ?>"> <?= $familia_['descricao'] ?> </option>
                            <?php }?>
                       <?php } ?>
                    
                    </select>
                </div>
                <div class="span2" style="padding-top: 23px;">
                    <label class="checkbox">
                        <?php if( !empty($os_troca) ) { ?>
                            <input name="os_troca" type="checkbox" checked> Apenas OS Troca
                        <?php }else{ ?>
                            <input name="os_troca" type="checkbox"> Apenas OS Troca
                        <?php } ?>
                    </label>
                </div>
            </div>

            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span2" style="padding-top: 21px;">
                    <label class="radio">

                        <?php if( !empty($data_tipo) AND $data_tipo == 'abertura' ) { ?>
                            <input type="radio" name="data_tipo" value="abertura" maxlength="10" checked> Data Abertura
                        <?php } else { ?>
                            <input type="radio" name="data_tipo" value="abertura" maxlength="10" checked> Data Abertura
                        <?php } ?>
                    
                    </label>
                </div>
                <div class="span2" style="padding-top: 21px;">
                    <label class="radio">

                        <?php if( !empty($data_tipo) AND $data_tipo == 'digitacao' ) { ?>
                            <input type="radio" name="data_tipo" value="digitacao" maxlength="10" checked> Data Digitação
                        <?php } else { ?>
                            <input type="radio" name="data_tipo" value="digitacao" maxlength="10"> Data Digitação
                        <?php } ?>
                    
                    </label>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span3"> 
                    <label for="data_inicial">Data Inicial</label>

                    <?php if( !empty($data_inicial) AND is_object($data_inicial) ) { ?>
                        <input type="text" name="data_inicial" id="data_inicial" maxlength="10" class="input-block-level" autocomplete="off" onclick="if(this.value == 'dd/mm/aaaa') this.value = ''" value="<?=$data_inicial->format('d/m/Y')?>" />
                    <?php }else{?>
                        <input type="text" name="data_inicial" id="data_inicial" maxlength="10" class="input-block-level" autocomplete="off" onclick="if(this.value == 'dd/mm/aaaa') this.value = ''"/>
                    <?php } ?>

                </div>
                <div class="span3">
                    <label for="data_final">Data Final</label>

                    <?php if( !empty($data_final AND is_object($data_final)) ) { ?>
                        <input type="text" name="data_final" id="data_final" maxlength="10" class="input-block-level" autocomplete="off" onclick="if(this.value == 'dd/mm/aaaa') this.value = ''"value="<?=$data_final->format('d/m/Y')?>"/>
                    <?php }else{ ?>
                        <input type="text" name="data_final" id="data_final" maxlength="10" class="input-block-level" autocomplete="off" onclick="if(this.value == 'dd/mm/aaaa') this.value = ''"/>
                    <?php } ?>
                
                </div>
                <div class="span3" style="padding-top: 23px;">
                    <label class="checkbox">

                        <?php if( !empty($os_aberta) ){ ?>
                            <input type="checkbox" name="os_aberta" checked> Apenas OS em aberto
                        <?php } else { ?>
                            <input type="checkbox" name="os_aberta"> Apenas OS em aberto
                        <?php } ?>

                    </label>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span3">
                    <label for="codigo_posto">Código do Posto</label>
                    <div class="input-append">
                        
                        <?php if( !empty($codigo_posto) ) { ?>
                            <input type="text" style="width: calc(100% - 42px)" name="codigo_posto" value="<?= $codigo_posto ?>">
                        <?php } else { ?>
                            <input type="text" style="width: calc(100% - 42px)" name="codigo_posto" value="">
                        <?php } ?>
                        
                        <span class="add-on" onclick="fnc_pesquisa_posto('', document.formulario_pesquisa.codigo_posto, '');">
                            <i class="icon-search"></i>
                        </span>
                    </div>
                </div>
                <div class="span5">
                    <label for="nome_posto">Nome do Posto</label><br>
                    <div class="input-append" style="width: 100%">

                        <?php if( !empty($nome_posto) ) { ?>
                            <input type="text" style="width: calc(100% - 42px)" name="nome_posto" value="<?= $nome_posto ?>">
                        <?php } else {?>
                            <input type="text" style="width: calc(100% - 42px)" name="nome_posto">
                        <?php } ?>

                        <span class="add-on" onclick="fnc_pesquisa_posto('', '', document.formulario_pesquisa.nome_posto);">
                            <i class="icon-search"></i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span3">
                    <label class="" for="">Estado</label>
                    <div style="display: flex;">
                        <select type="text" name="estado" class="input-block-level">
                            <option value="">Selecione um Estado</option>

                            <?php foreach($listaDeEstadosDoBrasil as $sigla => $estado_) { ?>
                                <?php if( $estado AND ($estado == $sigla) ) { ?>
                                    <option value="<?= $sigla ?>" selected> <?= $estado_ ?> </option>
                                <?php } else { ?>
                                    <option value="<?= $sigla ?>"> <?= $estado_ ?> </option>
                                <?php } ?>
                            <?php } ?>
                        
                        </select>
                    </div>
                </div>
                <div class="span5">
                    <label class="" for="">Nome do Consumidor</label>
                    <div style="display: flex;">
                        
                        <?php if( !empty($nome_consumidor) ) { ?>
                            <input type="text" name="nome_consumidor" class="input-block-level" value="<?= $nome_consumidor ?>">
                        <?php } else { ?>
                            <input type="text" name="nome_consumidor" class="input-block-level">
                        <?php } ?>

                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span3">
                    <label for="referencia_produto">Ref. Produto</label>
                    <div class="input-append">
                        
                        <?php if( !empty($referencia_produto) ) { ?>
                            <input type="text" name="referencia_produto" style="width: calc(100% - 42px)" value="<?= $referencia_produto ?>">
                        <?php } else { ?>
                            <input type="text" name="referencia_produto" style="width: calc(100% - 42px)">
                        <?php } ?>

                        <span class="add-on" onclick="fnc_pesquisa_produto('', document.formulario_pesquisa.referencia_produto, '', '')">
                            <i class="icon-search"></i>
                        </span>
                    </div>
                </div>
                <div class="span5">
                    <label for="descricao_produto">Descrição Produto</label>
                    <div class="input-append" style="width: 100%">
                        
                        <?php if( !empty($descricao_produto) ) { ?>
                            <input type="text" style="width: calc(100% - 42px)" name="descricao_produto" value="<?= $descricao_produto ?>">
                        <?php } else { ?>
                            <input type="text" style="width: calc(100% - 42px)" name="descricao_produto">
                        <?php } ?>

                        <span class="add-on" onclick="fnc_pesquisa_produto(document.formulario_pesquisa.descricao_produto, '', '', '')">
                            <i class="icon-search"></i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span2" style="padding-top: 21px;">
                    <label class="radio">

                        <?php if( !empty($os_situacao) AND $os_situacao == "APROVADA" ) { ?>
                            <input type="radio" name="os_situacao" value="APROVADA" checked> OS's Aprovadas
                        <?php } else { ?>
                            <input type="radio" name="os_situacao" value="APROVADA"> OS's Aprovadas
                        <?php } ?>

                    </label>
                </div>
                <div class="span2" style="padding-top: 21px;">
                    <label class="radio">

                        <?php if( !empty($os_situacao) AND $os_situacao == "PAGA" ) { ?>
                            <input type="radio" name="os_situacao" value="PAGA" checked> OS's Pagas
                        <?php } else { ?>
                            <input type="radio" name="os_situacao" value="PAGA"> OS's Pagas
                        <?php } ?>

                    </label>
                </div>
            </div>
            <div class="subtitulo" style="max-height: 15px; height: 15px; text-align: center;">
                Consultar Pré-Ordem de Serviço
            </div>
            <div class="row-fluid">
                <div class="span3"></div>
                <div class="span4">
                    <label for="pre_os">Número do Atendimento</label>

                    <?php if( !empty($pre_os) ) { ?>
                        <input type="text" name="pre_os" class="input-block-level" value="<?= $pre_os ?>" />
                    <?php } else { ?>
                        <input type="text" name="pre_os" class="input-block-level" />
                    <?php } ?>

                </div>
                <div class="span3" style="padding-top: 20px;">
                    <button type="button" class="btn" onclick="changeAction(event, 'formulario_pre_os')"> Pesquisar Pré-OS </button>
                </div>
                <div class="span3"></div>
            </div>
            <hr style="background-color: #000000">
            <div class="row-fluid">
                <div class="span4"></div>
                <div class="span4">
                    <label for="revenda_cnpj">OS em aberto da Revenda = CNPJ</label>
                    <div style="display: flex;">

                        <?php if( !empty($revenda_cnpj)  ){ ?>
                            <input type="text" name="revenda_cnpj" class="input-block-level" maxlength="8" value="<?= $revenda_cnpj ?>" /> 
                        <?php } else { ?>
                            <input type="text" name="revenda_cnpj" class="input-block-level" maxlength="8" /> 
                        <?php } ?>

                        <div style="width: 70px; margin-top: 5px; margin-left: 3px">/0000-00</div> 
                    </div>
                </div>
                <div class="span4"></div>
            </div>
            <div class="row-fluid" style="display: flex; justify-content: space-between; align-items: center">
                <div class="span3"></div>
                <div class="span4">
                    <label name="lbl_gerar_excel">
                        <input type="checkbox" name="gerar_excel"> Gerar Excel (CSV)
                    </label>
                </div>
                <div class="span3"></div>
            </div>
            <input type="hidden" name="action" class="btn" value="formulario_pesquisa">
            <button type="submit" class="btn" style="margin-bottom: 25px;">Pesquisar</button>
        </form>
    </div>
</div> 
       
 <!-- Download arquivo CSV -->
<?php if( !empty($caminho_download) ){ ?> 
<div class="row-fluid">
    <div class="span4"></div>
    <div class="span4" style="text-align: center; padding: 5px; background-color: #d9e2ef; font-weight: bold">
        <a href="<?= $caminho_download ?>" target="_blank" style="text-decoration: none; ">
            <img src="/~jpcorreia/PosVendaAssist/imagens/icon_csv.png" height="40px" width="40px" align="absmiddle">&nbsp;&nbsp;&nbsp;
            <span class="">Baixar Arquivo CSV</span>
        </a>
    </div>
    <div class="span4"></div>
</div>
<?php } ?>

<?php if( !empty($resultadoPesquisa) OR !empty($resultadoPesquisaPreOs) ) { ?>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span3">
            <ul class="container-box">
                <li>
                    <small>
                        <b style="color: #63798D">
                            Status das OS's
                        </b>
                    </small>
                </li>

                <?php foreach( $listaDeStatusDaOS as $status ) { ?>
                    <li> 
                        <div class="box" style="background-color: <?= $status['cor'] ?>"></div>  
                        <small>
                            <b> <?= $status['descricao'] ?> </b>
                        </small> 
                    </li>
                <?php } ?>

            </ul>
        </div>
        <div class="span6">
            <ul class="container-box">
                <li>
                    <small>
                        <b style="color: #63798D">
                            Legenda das OS's
                        </b>
                    </small>
                </li>

                <?php foreach( $listaDeLegendas as $legenda) { ?>
                    <li> 
                        <div class="box box-subtitles" style="background-color: <?= $legenda['cor'] ?>"></div>  
                        <small>
                            <b> <?= $legenda['descricao'] ?> </b>
                        </small> 
                    </li>
                <?php } ?>

            </ul>
        </div>
        <div class="span1"></div>
    </div>
<?php } ?>

<?php if( !empty($resultadoPesquisa) ) { ?>
    <div style="width: 1200px !important; margin-left: -180px;">
        <table class="table table-bordered" id="tabela" width="100%">
            <thead>
                <tr style="background-color: #596d9b; color: #FFFFFF">
                    <th scope="col">OS</th>
                    <th scope="col">Série</th>
                    <th scope="col">AB</th>
                    <th scope="col">FC</th>
                    <th scope="col">Tipo de Atendimento</th>
                    <th scope="col">C/R</th>
                    <th scope="col">Nome Posto</th>
                    <th scope="col">Cidade</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Consumidor/Revenda</th>
                    <th scope="col">NF</th>
                    <th scope="col">Produto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach( $resultadoPesquisa as $row ) { ?>
                        <tr style="background-color: <?= verifySubtitles($row, $listaDeLegendas)['cor'];?> !important">
                            <td style="display: flex; align-items: center;"> 

                                <?php foreach( $listaDeStatusDaOS as $status ) { ?>
                                    <?php if( $status['status_checkpoint'] == $row['status_checkpoint'] ) { ?>
                                        <div class="box box-sm" style="background-color: <?= $status['cor'] ?>"></div> 
                                        <a href="os_press.php?os=<?= $row['os'] ?>" target='_blank'> <?= $row['os'] ?> </a>
                                        <?php break; ?>
                                    <?php } ?>
                                <?php } ?> 

                            </td>
                            <td> <?= $row['serie'] ?> </td>
                            <td> <?= $row['abertura'] ?> </td>
                            <td> <?= $row['fechamento'] ?> </td>
                            <td> <?= $row['descricao'] ?> </td>
                            <td> 

                                <?php if( $row['consumidor_revenda'] == 'C'){ ?>
                                    <?= 'Consumidor' ?>
                                <?php } elseif( $row['consumidor_revenda'] == 'R' ) { ?>
                                    <?= 'Revenda' ?>
                                <?php } ?>

                            </td>
                            <td> <?= $row['posto_nome'] ?> </td>
                            <td> <?= $row['contato_cidade'] ?> </td>
                            <td> <?= $row['contato_estado'] ?> </td>
                            <td> <?= $row['revenda_nome'] ?> </td>
                            <td> <?= $row['nota_fiscal'] ?> </td>
                            <td> <?= $row['produto_descricao'] ?> </td>
                        </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>

<?php if( !empty($resultadoPesquisaPreOs) ) { ?>
    <div style="width: 1200px !important; margin-left: -180px;">
        <table class="table table-bordered" id="tabela" width="100%">
            <thead>
                <tr style="background-color: #596d9b; color: #FFFFFF">
                    <th scope="col">Nº Atendimento</th>
                    <th scope="col">Série</th>
                    <th scope="col">AB</th>
                    <th scope="col">DF</th>
                    <th scope="col">Nome Posto</th>
                    <th scope="col">Consumidor/Revenda</th>
                    <th scope="col">NF</th>
                    <th scope="col">Produto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach( $resultadoPesquisaPreOs as $row ) { ?>
                        <tr>
                            <td> <?= $row['hd_chamado'] ?> </td>
                            <td> <?= $row['serie'] ?> </td>
                            <td> <?= $row['data'] ?> </td>
                            <td> <?= $row['df'] ?> </td>
                            <td> <?= $row['posto_nome'] ?> </td>
                            <td> <?= $row['nome'] ?> </td>
                            <td> <?= $row['nota_fiscal'] ?> </td>
                            <td> <?= $row['produto_referencia'] . ' - ' . $row['produto_descricao'] ?> </td>
                        </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>

<script>
    $(function(){
        // configura o datepicker
        $('#data_inicial').datepicker();
        $('#data_final').datepicker();

        // configura o datatable
        $('#tabela').DataTable({
            aaSorting: [[0, 'desc']],
            "oLanguage": {
                "sLengthMenu": "Mostrar <select>" +
                                '<option value="10"> 10 </option>' +
                                '<option value="50"> 50 </option>' +
                                '<option value="100"> 100 </option>' +
                                '<option value="150"> 150 </option>' +
                                '<option value="200"> 200 </option>' +
                                '<option value="-1"> Tudo </option>' +
                                '</select> resultados',
                "sSearch": "Procurar:",
                "sInfo": "Mostrando de _START_ até _END_ de um total de _TOTAL_ registros",
                "oPaginate": {
                    "sFirst": "Primeira página",
                    "sLast": "Última página",
                    "sNext": "Próximo",
                    "sPrevious": "Anterior"
                }
            }
        });

        // Mascaras
        $('#data_inicial').mask('00/00/0000');
        $('#data_final').mask('00/00/0000');

        // Inicia o ShadowBox
        Shadowbox.init();   
    })

    // Altera a ação do formulario conforme o botão de ação
    function changeAction(event, action){
        event.preventDefault();

        let form = $('#formulario_pesquisa');
        let input = $('input[name=action]');

        input.val(action);
        form.submit();
    }

</script>

<? include "rodape.php"; ?>
