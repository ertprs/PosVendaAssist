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

// STATUS DA OS
$sqlStatusdaOS = "SELECT status_checkpoint, descricao, cor FROM tbl_status_checkpoint WHERE status_checkpoint IN (1,2,3,4,8,9)";
$pg_res = pg_query($con, $sqlStatusdaOS);
$listaDeStatusDaOS = pg_fetch_all($pg_res);

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
    global $login_fabrica, $login_cliente_admin;

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
            LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os and tbl_os_extra.i_fabrica = {$login_fabrica}
            JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica and tbl_fabrica.fabrica = {$login_fabrica} 
            LEFT JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
            LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = {$login_fabrica}
            --LEFT JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os
            {joins}
            WHERE tbl_os.fabrica = {$login_fabrica}
	    AND tbl_os.cliente_admin = {$login_cliente_admin}
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
    $nf_compra          = filter_input(INPUT_POST, 'nf_compra');
    $consumidor_cpf     = filter_input(INPUT_POST, 'consumidor_cpf');
    $status_checkpoint  = filter_input(INPUT_POST, 'status_checkpoint');
    $data_inicial       = filter_input(INPUT_POST, 'data_inicial');
    $data_final         = filter_input(INPUT_POST, 'data_final');
    $nome_consumidor    = filter_input(INPUT_POST, 'nome_consumidor');
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
            
            if( $gerar_excel )
                $caminho_download = mountCsv($resultadoPesquisa, null, $listaDeStatusDaOS, $listaDeLegendas);

        // Verifica pelo nÃƒÂºmero de sÃƒÂ©rie
        }else if( $data_inicial AND $data_final ){
            $conditionsArray = [];
            $joinsArray      = [];
            $propertiesArray = [];

            $dateType = 'data_abertura';

            if( $dateType )
                $conditionsArray[] = "AND tbl_os.{$dateType} >= '{$data_inicial->format('Y/m/d')}' AND tbl_os.{$dateType} <= '{$data_final->format('Y/m/d')}'";

            if( $consumidor_cpf )
                $conditionsArray[] = "AND tbl_os.consumidor_cpf = '{$consumidor_cpf}'";

            if( $nf_compra )
                $conditionsArray[] = "AND tbl_os.nota_fiscal = '{$nf_compra}'";
            
            if( $status_checkpoint )
                $conditionsArray[] = "AND tbl_os.status_checkpoint = {$status_checkpoint}";

            if( $nome_consumidor )
                $conditionsArray[] = "AND upper(tbl_os.consumidor_nome) = upper('{$nome_consumidor}')";

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
                <div class="span2"></div>
            </div>
            
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span3">
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
		
		<div class="span3">
                    <label for="nf_compra">NF. Compra</label>
                    <input type="text" maxlength="20" id="nf_compra" name="nf_compra" class="input-block-level" value="<?=$nf_compra ?? ''?>">
                </div>
                <div class="span2"></div>
            </div>
            <input type="hidden" name="action" value="formulario_pesquisa">

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
            </div>
            
            <div class="row-fluid">
                <div class="span2"></div>
		<div class="span3">
                    <label for="consumidor_cpfconsumidor_cpf">CPF/CNPJ Consumidor</label>
                    <input type="text" maxlength="14" id="consumidor_cpf" name="consumidor_cpf" class="input-block-level" value="<?=$consumidor_cpf ?? ''?>"> 
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
