<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

include 'funcoes.php';

function getInteracoesAbandonadas($abandonadaUniqueId) {

    $curl_url  = "https://api2.telecontrol.com.br/telefonia/ligacoes-abandonadas-interacao/abandonadaUniqueId/{$abandonadaUniqueId}";

    $curlData = curl_init();

    curl_setopt_array($curlData, array(
        CURLOPT_URL => $curl_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => array(
            "Access-Application-Key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
            "Access-Env: PRODUCTION",
            "Cache-Control: no-cache",
            "Content-Type: application/json"
        ),
    ));

    $responseData = curl_exec($curlData);


    return json_decode($responseData, true);
}

if (isset($_POST['ajax_discar'])) {

    $consultar = "SELECT external_id FROM tbl_admin WHERE fabrica = $login_fabrica AND login = '{$login_login}';";

    $res_consultar = pg_query($con, $consultar);

    if (pg_num_rows($res_consultar) > 0) {
        $external_id = pg_fetch_result($res_consultar, 0, "external_id");

        if (empty($external_id)) {
            echo json_encode(['exception' => 'Usuário não vinculado com telefonia']);
            exit;
        }

        try {
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api2.telecontrol.com.br/telefonia/discar",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'numerodiscar' => $_POST['numerodiscar'], 
                "externalId" => $external_id, 
                'fabricaTelecontrol' => $login_fabrica
            ]),
            CURLOPT_HTTPHEADER => array(
                "Access-Application-Key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
                "Access-Env: PRODUCTION",
                "Cache-Control: no-cache",
                "Content-Type: application/json"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            $objdiscar = json_decode($response,1);

            if (array_key_exists("exception", $objdiscar)) {
                echo json_encode(['exception' => 'Ocorreu um erro ao ligar']);
                curl_close($curl);
                exit;
            } else {

                curl_close($curl);
                echo json_encode(['message' => 'success']);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    exit;
}

if (isset($_POST['ajax_gravar_interacao'])) {


    $consultar = "SELECT external_id FROM tbl_admin WHERE fabrica = $login_fabrica AND login = '{$login_login}';";
    $res_consultar = pg_query($con, $consultar);


    if (pg_num_rows($res_consultar) > 0) {                
        $external_id = pg_fetch_result($res_consultar, 0, "external_id");
    
        if (empty($external_id)) {
            echo json_encode(['exception' => 'Usuário não vinculado com telefonia']);
            exit;
        }

        try {
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api2.telecontrol.com.br/telefonia/ligacoes-abandonadas-interacao",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'mensagem' => $_POST['mensagem'],
                'abandonada_unique_id' => $_POST['unique_id'],
                "usuario_external_id" => $external_id
            ]),
            CURLOPT_HTTPHEADER => array(
                "Access-Application-Key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
                "Access-Env: PRODUCTION",
                "Cache-Control: no-cache",
                "Content-Type: application/json"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            $objdiscar = json_decode($response,1);


            if (array_key_exists("exception", $objdiscar)) {
                echo json_encode(['exception' => 'Ocorreu um erro ao gravar a interação']);
                curl_close($curl);
                exit;
            } else {

                curl_close($curl);
                echo json_encode(['message' => 'success']);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }else{
        echo "->";exit;
    }
    exit;
}

$fila         = $_GET['fila'];
$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];

$curl_url  = "https://api2.telecontrol.com.br/telefonia/ligacoes-abandonadas/fila/{$fila}/dataInicial/{$data_inicial}/dataFinal/{$data_final}";

$curlData = curl_init();

curl_setopt_array($curlData, array(
    CURLOPT_URL => $curl_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 90,
    CURLOPT_HTTPHEADER => array(
        "Access-Application-Key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
        "Access-Env: PRODUCTION",
        "Cache-Control: no-cache",
        "Content-Type: application/json"
    ),
));

$responseData = curl_exec($curlData);
$responseData = json_decode($responseData, true);



?>
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<script src='plugins/shadowbox_lupa/shadowbox.js'></script>
<link rel='stylesheet' type='text/css' href='plugins/shadowbox_lupa/shadowbox.css' />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
<script src='plugins/dataTable.js?v=20181011164349'></script>
<link rel='stylesheet' type='text/css' href='plugins/dataTable.css?v=20181011164349' />
<script>
    $(function(){
        $(document).on("click", ".btn-ligar", function(){

            var telefone = $(this).data("fone");

            if (telefone == "") {
                window.alert("Campo telefone não pode ser vazio");
            } else {
                $.ajax ({
                    url: window.location,
                    type: "POST",
                    data: {
                        numerodiscar: telefone,
                        ajax_discar: true
                    },
                    async: true,
                    timeout: 10000
                }).done(function(response) {
                    response = JSON.parse(response);
                    if (response.exception != undefined) {
                        alert('Ocorreu ao tentar efetuar a ligação: ' + response.exception);
                    }
                });
            }

        });

        $(document).on("click", ".gravar-interacao", function(){

            var tbody      = $(this).closest(".modal").find("tbody");
            var unique_id  = $(this).data("unique-id");
            var mensagem   =$(this).parent().find(".mensagem-interacao").val();

            $(this).parent().find(".mensagem-interacao").val("");

            if (mensagem == "") {
                alert("Informe a mensagem");
            } else {
                $.ajax ({
                    url: window.location,
                    type: "POST",
                    data: {
                        unique_id: unique_id,
                        mensagem: mensagem,
                        ajax_gravar_interacao: true
                    },
                    async: true,
                    timeout: 10000
                }).done(function(response) {

                    response = JSON.parse(response);

                    if (response.exception != undefined) {
                        alert('Ocorreu um erro ao gravar a interação: ' + response.exception);
                    } else {
                        var data = new Date();
                        var criado_em = data.getDate()+"/"+(data.getMonth()+1)+"/"+data.getFullYear()+" "+data.getHours()+":"+data.getMinutes();
                        $(tbody).append("<tr><td>"+mensagem+"</td><td style='text-align:center'>"+'<?= ucwords($login_login) ?>'+"</td><td>"+criado_em+"</td></tr>");
                    }

                });
            }

        });

    });
</script>
<br />
<table class="table table-striped table-bordered tabela-abandon" style="width: 100%;">
	<thead>
        <tr class="titulo_coluna">
        	<th>Nr. Telefone</th>
            <th>Última Obs.</th>
        </tr>
    </thead>
    <tbody>
    <?php
    foreach ($responseData['ligacoes'] as $posicao => $dados) {

		if (strlen($dados['telefone_cliente']) >= 11) {
			$nove_dig = substr($dados['telefone_cliente'], -9);
			$is_cel = ($nove_dig[0] == 9) ? 11 : 10;
			$dados['telefone_cliente'] = trim(substr($dados['telefone_cliente'], -$is_cel));
		}

        $interacoesAbandonadas = getInteracoesAbandonadas($dados['unique_id']);


    ?>
        <tr>
            <td class="tac">
                <a href="#modalInteracoes<?= $posicao ?>" role="button" class="lista-interacoes" data-toggle="modal" data-unique-id="<?= $dados['unique_id'] ?>" >
                    <?= trim($dados['telefone_cliente']) ?>
                </a>
                <img style="cursor: pointer; margin-bottom: -4px;margin-left: 5px;"  title="Discar Telefone" src="imagens/telefone_002.png" width="15" height="15" id='icone_telefone' class='btn-ligar' data-fone="<?= $dados['telefone_cliente'] ?>" />
                <div id="modalInteracoes<?= $posicao ?>" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 id="myModalLabel" class="tac">
                        <?= trim($dados['telefone_cliente']) ?>
                        <img style="cursor: pointer; margin-bottom: -4px;margin-left: 5px;"  title="Discar Telefone" src="imagens/telefone_002.png" width="50" height="50" id='icone_telefone' class='btn-ligar' data-fone="<?= $dados['telefone_cliente'] ?>" />
                    </h3>
                  </div>
                  <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Mensagem</th>
                                <th>Admin</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($interacoesAbandonadas as $dadosInteracoes) { ?>
                                <tr>
                                    <td><?= utf8_decode($dadosInteracoes['mensagem']) ?></td>
                                    <td class="tac"><?= utf8_decode($dadosInteracoes['nome']) ?></td>
                                    <td class="tac"><?= mostra_data($dadosInteracoes['criado_em']) ?></td>
                                </tr>
                            <?php
                            } ?>
                        </tbody>
                    </table>
                  </div>
                  <div class="modal-footer tac">
                    <textarea class="mensagem-interacao" rows="5" style="margin-right: 40px;text-align: left;width: 50%;"></textarea>
                    <button class="btn btn-primary gravar-interacao" data-unique-id="<?= $dados['unique_id'] ?>">Gravar Interação</button>
                  </div>
                </div>
            </td>
            <td><?= utf8_decode($interacoesAbandonadas[count($interacoesAbandonadas)-1]['mensagem']) ?></td>
        </tr>
    <?php
    }
    ?>
    </tbody>
</table>
<script>
    $.dataTableLoad({ table: ".tabela-abandon" });
</script>

