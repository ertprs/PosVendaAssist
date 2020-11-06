<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

define("URL_API", "http://api2.telecontrol.com.br/communicator/emailBlackList");

// AJAX DESBLOQUEAR OS
$debloquearOS = filter_input(INPUT_GET, 'desbloquear_os', FILTER_VALIDATE_BOOLEAN);
if ($debloquearOS) {
    $postoFabrica = filter_input(INPUT_GET, 'posto_fabrica', FILTER_VALIDATE_INT);
    $email = filter_input(INPUT_GET, 'email', FILTER_VALIDATE_EMAIL);

    $context = stream_context_create([
        "http" => [
            "method" => "PUT",
            'timeout' => 30,
            'protocol_version' => 1.1,
            'ignore_errors' => true,
            'max_redirects' => 30,
            'header' => [
                "access-application-key: 701c59e0eb73d5ffe533183b253384bd52cd6973",
                "access-env: PRODUCTION",
                "cache-control: no-cache",
                "Content-Type: application/json"
            ]
        ]
    ]);

    $response = file_get_contents(URL_API . "?email={$email}", 0, $context);

    if( isset($response['exception']) ){
        $response = [
            'error' => true,
            'message' => 'Este email não está na blacklist.'
        ];
        exit(json_encode($response));
    }

    $sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto_fabrica = :postoFabrica";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':postoFabrica', $postoFabrica);

    if( !$stmt->execute() OR $stmt->rowCount() == 0 ){
        $response = [
            'error' => true,
            'message' => 'Não foi possível encontrar as informações do posto.'
        ];
        exit(json_encode($response));
    }

    if( ($parametrosAdicionais = json_decode($stmt->fetch(PDO::FETCH_ASSOC)['parametros_adicionais'], true)) == FALSE ){
        $response = [
            'error' => true,
            'message' => 'Falha ao decodificar JSON (Formato inválido).'
        ];
        exit(json_encode($response));
    }

    unset($parametrosAdicionais['blacklist']);
    $parametrosAdicionais['desbloqueio_admin'] = $login_admin;
    $parametrosAdicionais['desbloqueio_data'] = (new DateTime('now'))->format('d/m/Y');

    $parametrosAdicionais = json_encode($parametrosAdicionais);

	if ($parametrosAdicionais === FALSE) {
		$response = [
            'error' => true,
            'message' => 'Falha ao codificar JSON (Formato inválido).'
        ];
        exit(json_encode($response));
    }
    
	$sql = "UPDATE tbl_posto_fabrica SET parametros_adicionais = '{$parametrosAdicionais}' WHERE posto_fabrica = {$postoFabrica}";
	$stmt = $pdo->query($sql);

	if ($stmt === FALSE OR $stmt->rowCount() == 0) {
        $response = [
            'error' => true,
            'message' => "Falha ao atualizar o campo parametros_adicionais do registro: {$postoFabrica}"
        ];
        exit(json_encode($response));
	}

    $response = [
        'error' => false,
        'message' => 'Email desbloqueado com sucesso.'
    ];

    exit(json_encode($response));
}

// AJAX PESQUISAR EMAIL
$pesquisarEmail = filter_input(INPUT_GET, 'pesquisar_email', FILTER_VALIDATE_BOOLEAN);
if( $pesquisarEmail ){
    $email = trim($_GET['email']);
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);

    if( !$email ){
        $response = [
            'error' => true,
            'message' => 'Email invalido.'
        ];
        exit(json_encode($response));
    }

    $sql = "SELECT tbl_posto_fabrica.posto_fabrica, tbl_posto_fabrica.contato_email, tbl_posto.cnpj, tbl_posto.nome 
            FROM tbl_posto_fabrica 
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica} 
            AND json_field('blacklist', tbl_posto_fabrica.parametros_adicionais)::BOOLEAN IS TRUE
            AND tbl_posto_fabrica.contato_email = '{$email}'";

    $stmt = $pdo->query($sql);

    if( $stmt === FALSE OR $stmt->rowCount() == 0 ) {
        $response = [
            'error' => true,
            'message' => 'Email nao encontrado.'
        ];
        exit(json_encode($response));
    }

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if( empty($result) ){
        $reponse = [
            'error' => true,
            'message' => 'Email não encontrado.'
        ];
        exit(json_encode($response));
    }

    $response = [
        'error' => false,
        'content' => $result
    ];
    
    exit(json_encode($response));
}

$layout_menu = "callcenter";
$title = "CONSULTA DE BLACKLIST EMAIL";
include "cabecalho_new.php";

$plugins = ["jquery", "dataTable"];
include("plugin_loader.php");

// Seleciona todos os emails na blacklist
$sql = "SELECT tbl_posto_fabrica.posto_fabrica, tbl_posto_fabrica.parametros_adicionais, tbl_posto_fabrica.contato_email, tbl_posto.cnpj, tbl_posto.nome 
        FROM tbl_posto_fabrica 
        INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
        WHERE tbl_posto_fabrica.fabrica = {$login_fabrica} AND json_field('blacklist', tbl_posto_fabrica.parametros_adicionais)::BOOLEAN IS TRUE";
$stmt = $pdo->query($sql);

$listaDeEmail = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .titulo-form {
        background-color: #596d9b;
        font: bold 16px "Arial";
        color: #FFFFFF;
        text-align: center;
        padding: 5px 0 5px 0;
    }

    .table thead tr {
        background-color: #596d9b;
    }

    .table thead th {
        font: bold 13px "Arial";
        color: #FFFFFF;
        text-align: center;
    }

    .table tbody td {
        text-align: center;
    }
</style>

<div class="titulo-form">Parâmetros de Pesquisa</div>

<div class="tc_formulario" style="padding: 10px; margin-bottom: 15px;">
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label>Email</label>
                <h5 class="asteristico">*</h5>
                <input type="text" id="email">
            </div>
        </div>
    </div>
    <div class="tac">
        <button class="btn" onclick="onSearch()"> Pesquisar </button>
    </div>
</div>

<table class="table" style="width: 100%;">
    <thead>
        <tr>
            <th> Posto </th>
            <th> CNPJ </th>
            <th> Email </th>
            <th> Ação </th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($listaDeEmail as $email) { ?>
            <tr id="tr_<?= $email['posto_fabrica'] ?>" >
                <td> <?= $email['nome'] ?> </td>
                <td> <?= $email['cnpj'] ?> </td>
                <td> <?= $email['contato_email'] ?> </td>
                <td>
                    <button class="btn btn-success" 
                            data-postofabrica="<?= $email['posto_fabrica'] ?>" 
                            data-email="<?= $email['contato_email'] ?>"
                            onclick="onDesbloquear(this)">
                            
                            Desbloquear
                    </button>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<script>
    $(function() {
        $('.table').DataTable({
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
    });

    async function onDesbloquear(refElement) {
        const formData = {
            desbloquear_os: true,
            posto_fabrica: refElement.dataset.postofabrica,
            email: refElement.dataset.email
        };

        try {
            let response = await $.get(window.location.href, formData);
            response = JSON.parse(response);

            if( response.error == true ){
                alert(response.message);
                return;
            }

            alert(response.message);
            $(refElement).parents('tr').remove();

        } catch (e) {
            alert("Não foi possível realizar a requisição. Tente novamente em instantes..!");
        }
    }

    async function onSearch(){
        const formData = {
            pesquisar_email: true,
            email: document.getElementById('email').value
        };

        try{
            let response = await $.get(window.location.href, formData);
            response = JSON.parse(response);

            if( response.error === true ){
                alert(response.message);
                return;
            }

            const tabela = $('.table').dataTable();
            tabela.fnClearTable();

            tabela.fnAddData([
                response.content.nome,
                response.content.cnpj,
                response.content.contato_email,
                `<button class="btn btn-success" 
                    data-postofabrica="${response.content.posto_fabrica}" 
                    data-email="${response.content.contato_email}"
                    onclick="onDesbloquear(this)">
                    Desbloquear
                 </button>
                `
            ]);
        }catch(e){
            alert("Não foi possível realizar a requisição. Tente novamente em instantes..!");
        }
  
    }
</script>

<?php
include "rodape.php";
?>