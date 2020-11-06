<?php
// Report simple running errors
//error_reporting(E_ALL);

$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../':'');

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include "autentica_admin.php";

$layout_menu = "call_center";
$title = "Seleção de Parâmetros para Relação de Ordens de Serviços Lançadas";
$host = "";

if (!$moduloGestaoContrato) {
    include_once "funcoes.php";
}

if (strstr($_SERVER['PHP_SELF'],"admin_cliente")) {
    if ($moduloGestaoContrato) {
        include "cabecalho_novo.php";
    } else {
        include "cabecalho_new.php";
    }
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

        if( $data_inicial AND $data_final ){
            $data_inicial_temp = explode('/', $data_inicial);
            $data_final_temp   = explode('/', $data_final);

          
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


        if( $sua_os ){
            $condi_os = "AND (tbl_os.os_numero = '{$sua_os}' OR tbl_os.sua_os = '{$sua_os}' OR tbl_os.os = '{$sua_os}')";
        }else if( $data_inicial AND $data_final ){
            $conditionsArray = [];
            $joinsArray      = [];
            $propertiesArray = [];

            $dateType = 'data_abertura';

            if( $dateType ){
                $conditionsArrayOs[] = "AND tbl_os.data_abertura BETWEEN '{$data_inicial->format('Y/m/d')}' and '{$data_final->format('Y/m/d')}'"; 
                $conditionsArrayPreOs[] = "AND tbl_hd_chamado.data BETWEEN '{$data_inicial->format('Y/m/d')} 00:00:00' and '{$data_final->format('Y/m/d')} 23:59:59'";

            }

            if( $consumidor_cpf ){
                $conditionsArrayOs[] = "AND tbl_os.consumidor_cpf = '{$consumidor_cpf}'";
                $conditionsArrayPreOs[] = "AND tbl_hd_chamado_extra.cpf = '{$consumidor_cpf}'";
            }

            if( $nf_compra ){
                $conditionsArrayOs[] = "AND tbl_os.nota_fiscal = '{$nf_compra}'";
                $conditionsArrayPreOs[] = "AND tbl_hd_chamado_extra.nota_fiscal = '{$nf_compra}'";
            }
            

            if( $nome_consumidor ){
                $conditionsArrayOs[] = "AND upper(tbl_os.consumidor_nome) = upper('{$nome_consumidor}')";
                $conditionsArrayPreOs[] = "AND upper(tbl_os.consumidor_nome) = upper('{$nome_consumidor}')";
            }


            // Executa o sql

            $sql = "SELECT
                    tbl_os.os,
                    tbl_os.status_checkpoint,
                    tbl_os.sua_os, 
                    tbl_os.nota_fiscal, 
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura,
                    TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento,
                    TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY') as data_nf,
                    tbl_os.consumidor_estado,
                    tbl_os.consumidor_cidade,
                    tbl_os.consumidor_nome,
                    tbl_tipo_atendimento.descricao, 
                    tbl_posto.nome || ' - ' ||  tbl_posto_fabrica.contato_estado AS posto_nome, 
                    tbl_produto.referencia AS produto_referencia, 
                    tbl_produto.descricao AS produto_descricao
            FROM tbl_os 
            JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
            JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
            JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto and tbl_produto.fabrica_i = {$login_fabrica}
            WHERE tbl_os.fabrica = {$login_fabrica}
            AND tbl_os.cliente_admin = {$login_cliente_admin}
            AND (tbl_os.excluida IS NOT TRUE OR (tbl_os.excluida IS TRUE AND tbl_os.status_checkpoint = 28))".implode(" ",$conditionsArrayOs)." 
                   
            UNION

            SELECT
                    0 AS os,
                    0 AS status_checkpoint, 
                    'PRÉ-ATENDIMENTO' AS sua_os,  
                    tbl_hd_chamado_extra.nota_fiscal,
                    TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY') AS abertura,
                    '' AS fechamento,
                    TO_CHAR(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') AS data_nf,
                    tbl_cidade.estado AS consumidor_estado,
                    tbl_cidade.nome AS consumidor_cidade,
                    tbl_hd_chamado_extra.nome as consumidor_nome,
                    tbl_tipo_atendimento.descricao, 
                    tbl_posto.nome || ' - ' ||  tbl_posto_fabrica.contato_estado AS posto_nome, 
                    tbl_produto.referencia as produto_referencia,
                    tbl_produto.descricao as produto_descricao
            FROM tbl_hd_chamado_extra
            JOIN tbl_hd_chamado using(hd_chamado)
            JOIN tbl_posto ON  tbl_posto.posto = tbl_hd_chamado_extra.posto
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
            JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
            LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_hd_chamado_extra.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
            LEFT JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_extra.os
            WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
            AND tbl_hd_chamado_extra.abre_os = 't'
            AND tbl_os.os IS NULL
            ".implode(" ",$conditionsArrayPreOs);

            $pg_res = pg_query($con, $sql);
            $resultadoPesquisa = pg_fetch_all($pg_res);


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
           
            <input type="hidden" name="action" class="btn" value="formulario_pesquisa">
            <button type="submit" class="btn" style="margin-bottom: 25px;">Pesquisar</button>
        </form>
    </div>
</div> 
       
<?php if( !empty($resultadoPesquisa) ) { ?>
    <div style="width: 1200px !important; margin-left: -180px;">
        <table class="table table-bordered" id="tabela" width="100%">
            <thead>
                <tr style="background-color: #596d9b; color: #FFFFFF">
                    <th scope="col">OS</th>
                    <th scope="col">AB</th>
                    <th scope="col">Tipo de Atendimento</th>
                    <th scope="col">Filial</th>
                    <th scope="col">Consumidor</th>
                    <th scope="col">Cidade</th>
                    <th scope="col">Estado</th>                    
                    <th scope="col">NF</th>
                    <th scope="col">Produto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach( $resultadoPesquisa as $row ) { ?>
                        <tr style="background-color: <?= verifySubtitles($row, $listaDeLegendas)['cor'];?> !important">
                            <?php
                            if(strpos($row['sua_os'], 'ATENDIMENTO') == false){
                            ?>
                                <td> <a href="os_press_espelho.php?os=<?=$row['os']?>" target="_blank"><?= $row['sua_os'] ?></a> </td>
                            <?php
                            }else{
                            ?>
                                <td> <?= $row['sua_os'] ?> </td>
                            <?php
                            }
                            ?>
                            <td> <?= $row['abertura'] ?> </td>
                            <td> <?= $row['descricao'] ?> </td>
                            <td> <?= $row['posto_nome'] ?> </td>
                            <td> <?= $row['consumidor_nome'] ?> </td>
                            <td> <?= $row['consumidor_cidade'] ?> </td>
                            <td> <?= $row['consumidor_estado'] ?> </td>                            
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
