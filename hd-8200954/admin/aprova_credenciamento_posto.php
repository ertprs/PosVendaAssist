<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "cadastros,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$title       = traduz("APROVAÇÃO DE CREDENCIAMENTO DE POSTOS AUTORIZADOS");
$cabecalho   = traduz("APROVAÇÃO DE CREDENCIAMENTO DE POSTOS AUTORIZADOS");
$layout_menu = "cadastro";

/******** SQL CONSULTA ACESSO ********/
$sql_acesso = "SELECT 
                      adm.responsavel_postos
                FROM  tbl_admin adm
                WHERE adm.admin   = {$login_admin}
                AND   adm.fabrica = {$login_fabrica}
                AND   adm.responsavel_postos IS TRUE";
$res_acesso = pg_query($con, $sql_acesso);
if (!pg_num_rows($res_acesso) > 0) {
    header('Location: menu_cadastro.php');
    exit;
}

/******** SQL UPDATE ********/
if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim' && isset($_POST['acao']) && $_POST['acao'] == 'aprova_reprova') {
    $posto_id       = $_POST['posto'];
    $aprova         = $_POST['aprova'];

    $adicional = "";
    
    if ($aprova == 't') {
        $aux_credenciamento = 'CREDENCIADO';
    } else if ($aprova == 'f') {
        
        $aux_credenciamento = 'DESCREDENCIADO';
        
        if ($login_fabrica == 177) {
            $paramAdicionais['contrato'] = 'f';
            $adicional = ", parametros_adicionais = '" . json_encode($paramAdicionais) . "'";
        }
    }
    $sql_update =  "UPDATE tbl_posto_fabrica SET
                          credenciamento = '{$aux_credenciamento}'
                          $adicional
                    WHERE posto          =  {$posto_id}
                    AND   fabrica        =  {$login_fabrica}";

    $res_update =  pg_query($con, $sql_update);
    $msg_erro   = pg_last_error($con);

    $sqlTblCredenciamento = "INSERT INTO tbl_credenciamento (posto, fabrica, status, confirmacao, confirmacao_admin) VALUES ({$posto}, {$login_fabrica}, '{$aux_credenciamento}', now(), {$login_admin}) ";
    $resTblCredenciamento = pg_query($con, $sqlTblCredenciamento);
    if (strlen($msg_erro) > 0) {
        echo json_encode(array("erro" => $msg_erro)); exit;
    } else {
        echo json_encode(array("ok" => "sucesso")); exit;
    }
    exit;
}

/******** SQL CONSULTA ********/
$sql_consulta = "SELECT 
                      p.nome,
                      p.posto
                FROM  tbl_posto_fabrica pf
                INNER JOIN tbl_posto p ON p.posto = pf.posto
                WHERE pf.fabrica                  = {$login_fabrica}
                AND   pf.credenciamento LIKE 'EM APROVA%'";
$res_consulta = pg_query($con, $sql_consulta);


/******** CABEÇALHO ********/
include "cabecalho_new.php";
    $plugins = array(
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);
include("plugin_loader.php");
?>

<link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">
<div id="erro" class='alert alert-error' style="display:none;"><h4></h4></div>
<div id="success" class='alert alert-success' style="display:none;"><h4></h4></div>

<!-------- TABELA COM RESULTADO -------->
<?php if (pg_num_rows($res_consulta) > 0) { ?>
<table class='table table-striped table-bordered table-hover table-fixed'>
    <thead>
        <tr class='titulo_coluna'>
            <td colspan="1" style='text-align: center;'><?=traduz('Posto')?></td>
            <td colspan="2" style='text-align: center;'><?=traduz('Ação')?></td>
        </tr>
    </thead>

    <!-------- PERCORRENDO OS DADOS -------->
    <?php for ($i_consulta=0; $i_consulta<pg_num_rows($res_consulta); $i_consulta++) { 
            $posto_nome = pg_fetch_result($res_consulta, $i_consulta, 'nome');
            $posto_id   = pg_fetch_result($res_consulta, $i_consulta, 'posto');    
    ?>
            <tbody>
                    <tr>
                        <td><?=$posto_nome?></td>
                        <td style='text-align: center;'> <button type='button' class='btn btn-success btn-aprovar' data-posto='<?=$posto_id;?>'>  <?=traduz('aprovar')?>  <i class="fa fa-thumbs-up"></i> </button> </td>
                        <td style='text-align: center;'> <button type='button' class='btn btn-danger  btn-reprovar' data-posto='<?=$posto_id;?>'> <?=traduz('reprovar')?> <i class="fa fa-thumbs-down"></i> </button> </td>
                    </tr>
            </tbody>
    <?php } ?>

</table>
<?php } else { ?>
        <script type="text/javascript"> 
            $("#erro").html('<h4> <i class="fas fa-times-circle"></i> <b><?=traduz("Nenhum Posto Autorizado em Aprovação.")?></b> </h4>'); 
            $("#erro").show(); 
        </script>
<?php } ?>

<!-------- SCRIPTS -------->
<script type="text/javascript">
    $('.btn-aprovar').on('click', function(){
        var td    = $(this).parents('tr');
        var posto = $(this).data('posto');
        aprova_reprova(posto, 't', td);
    });

    $('.btn-reprovar').on('click', function(){
        var td    = $(this).parents('tr');
        var posto = $(this).data('posto');
        aprova_reprova(posto, 'f', td);
    });

    function aprova_reprova(posto,aprova,td) {
        $.ajax("aprova_credenciamento_posto.php",{
          method: "POST",
          data: {
            ajax:  "sim",
            acao:  "aprova_reprova",
            posto:  posto,
            aprova: aprova
          }
        }).done(function(data){
            data = JSON.parse(data);

            if (data.ok !== undefined) {
                $("#success").html('<h4> <b><?=traduz("Posto atualizado com sucesso.")?></b> </h4>');
                $("#success").show();
                td.remove();
            } else {
                $("#erro").html('<h4> <b><?=traduz("Ocorreu um erro ao tentar atualizar o status do posto.")?></b> </h4>');
                $("#erro").show();
            }
        });
    }
</script>