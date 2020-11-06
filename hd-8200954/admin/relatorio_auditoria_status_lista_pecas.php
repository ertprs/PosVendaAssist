<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

$btnEnviar           = filter_input(INPUT_POST, 'btn_enviar');
$response            = []; // Inicializa uma variável com um array para posteriormente servir para armazenar as mensagem de erros

$sqlServicoRealizado = "SELECT servico_realizado FROM tbl_servico_realizado WHERE codigo_servico = 'PEA' AND fabrica = {$login_fabrica}";
$resServicoRealizado = pg_query($con, $sqlServicoRealizado);

if (pg_num_rows($resServicoRealizado))  {
    $idServicoRealizado = (int)pg_fetch_result($resServicoRealizado, 0, 'servico_realizado');
}


if(!empty($btnEnviar)){
    $listaDeOsItem = $_POST['osItem']; // Recupera as os_item
    $os_id         = $_POST['os'];

    try{
        pg_query($con, "BEGIN TRANSACTION");

        if( empty($listaDeOsItem) ){
            throw new Exception("Selecione ao menos 1 peça para excluir");
        }

        foreach ($listaDeOsItem as $osItem) {
            $pgResource = pg_query($con, "UPDATE tbl_os_item SET servico_realizado = $idServicoRealizado WHERE os_item = {$osItem}");
            if( strlen(pg_last_error()) > 0 ){
                throw new Exception("Erro ao excluir peça(s)");
            }
        }

        $sql = "SELECT 
                    COUNT(oi.servico_realizado) as total_peca,
                    COUNT(oi.servico_realizado) FILTER (WHERE oi.servico_realizado = {$idServicoRealizado}) AS total_excluida,
                    array_to_json(array(
                        SELECT oi2.os_item FROM tbl_os_item oi2 JOIN tbl_os_produto ON tbl_os_produto.os_produto = oi2.os_produto WHERE tbl_os_produto.os = {$os}
                    )) AS json_os_item
                FROM tbl_os_item AS oi
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = oi.os_produto 
                    INNER JOIN tbl_peca ON tbl_peca.peca = oi.peca
                WHERE   tbl_os_produto.os = {$os};";
        $resPecas = pg_query($con, $sql);
        
        if (pg_num_rows($resPecas) > 0) {
            $total_peca     = pg_fetch_result($resPecas, 0, 'total_peca');
            $total_excluida = pg_fetch_result($resPecas, 0, 'total_excluida');
            $array_os_item  = json_decode(pg_fetch_result($resPecas, 0, 'json_os_item'), true);
            $array_os_item  = implode(',', $array_os_item);

            if ($total_peca == $total_excluida) {

                $sqlServicoRealizado = "SELECT servico_realizado FROM tbl_servico_realizado WHERE codigo_servico = 'AJU' AND fabrica = {$login_fabrica}";
                $resServicoRealizado = pg_query($con, $sqlServicoRealizado);
                
                if (pg_num_rows($resServicoRealizado))  {
                    $idAjuste = (int)pg_fetch_result($resServicoRealizado, 0, 'servico_realizado');
                } else {
                    $idAjuste = 503; // passa fixo o AJUSTE, caso não encontrar.
                }

                $parametros_adicionais_item = json_encode(array("excluida_auditoria" => true, "adminExcluiu" => $login_admin));

                $resUpdate = pg_query($con, "UPDATE tbl_os_item SET servico_realizado = {$idAjuste}, parametros_adicionais = '$parametros_adicionais_item' WHERE os_item IN ($array_os_item)");

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao atualizar peças.");       
                }
            }
        }
 
        pg_query($con, 'COMMIT TRANSACTION');

        $response['error'] = false;
        $response['message'] = 'Peças excluidas com sucesso!';
    }catch(Exception $e){
        pg_query($con, 'ROLLBACK TRANSACTION');
        
        $response['error'] = true;
        $response['message'] = $e->getMessage();
    }

}
    
$os = filter_input(INPUT_GET, 'os');

if(!empty($os)){
    $sql = "SELECT tbl_os_item.peca, tbl_os_item.servico_realizado, tbl_os_item.os_item, tbl_peca.descricao, tbl_peca.referencia FROM tbl_os_item 
                INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
                INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
            WHERE   tbl_os_produto.os = {$os}";
    $pgResource   = pg_query($con, $sql);
    $listaDePecas = pg_fetch_all($pgResource);
}

?>

<!DOCTYPE html>
<html lang="pt_BR">
<head>
    <meta charset="iso-8859-1">
    <title>Lista de peças</title>

    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />

    <style>
        body { background-color: #D9E2EF; } 
        .header { background-color: #596d9b; color: #ffffff; padding: 3px; }
        .header > h5 { text-align: center }
        .button-container { display: flex; justify-content: center; margin-bottom: 15px}
    </style>
</head>
<body>

    <div class="row-fluid">
        <div class="span12">
            <div class='header'>
                <h5> Relação de peças da OS: <?= $os ?> </h5>
            </div>
        </div>
    </div>
    <!-- MENSAGENS DE ERRO -->
    <?php if( !empty($response) ) { ?>
        <div class="row-fluid" style="margin-top: 5px; margin-bottom: -20px; text-align: center">
            <?php if( $response['error'] == true ) { ?>
                <div class="alert alert-error">
                    <strong> <?= $response['message'] ?> </strong>
                </div>
            <?php } elseif ( $response['error'] == false ) { ?>
                <div class="alert alert-success">
                    <strong> <?= $response['message'] ?> </strong>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span10">
        <?php if(pg_num_rows($pgResource) > 0) { ?>
            <?php 
                foreach ($listaDePecas as $peca) { 
                    $input = '<input type="checkbox" name="osItem[]" value="'.$peca["os_item"].'" />';
                    $style = '';
		    if ($peca['servico_realizado']  == $idServicoRealizado) {
                        $input = '<input type="checkbox" checked=checked disabled=disabled />';                            
                        $style = 'style="opacity: 0.3 !important; cursor: not-allowed !important;"';
                    }

                    $pecas[]   = '<tr '.$style.'> 
                                        <td>'.$peca["referencia"].' - '.$peca["descricao"].' </td>
                                        <td>'.$input.'</td>
                                    </tr>';
                }
            ?>

            <?php if (count($pecas) > 0 ) { ?>  
            <div class="para_excluir">
                <div class="row-fluid">
                    <div class="span12">
                        <div class="control-group" style="float: right;">
                            <div class="controls controls-row">
                                <div class="span12">
                                   <div class="button-container" style="margin-top: 25px !important;">
                                        <a href="os_cadastro.php?os=<?=$os?>" target="_blank" class='btn btn-primary'> <b>+</b> Adicionar nova Peça</a>
                                    </div>
                                </div>
                            </div>          
                        </div>
                    </div>
                </div>
                <form action="" method="POST" id="form_peca">
                    <table class="table table-striped" style="width: 100%">
                        <tr>
                            <th>Referência - Descrição</th>
                            <th>Ação</th>
                        </tr>   
                        <?php for ($i_pecs = 0; $i_pecs < count($pecas); $i_pecs++) { echo $pecas[$i_pecs]; } ?>
                    </table>

                    <input type="hidden" name="btn_enviar" value="true" />
                    <input type="hidden" name="os" value="<?= $os; ?> " />

                    <div class="button-container">
                        <button type="button" class='btn btn-danger' onclick="document.getElementById('form_peca').submit()"> Excluir selecionados </button>
                    </div> 
                </form>                
            </div>
            <?php } ?>

        <?php } else { ?>
            <div class="alert alert-error">
                <strong>Não foi localizada nenhuma peça.</strong>
        <?php } ?>
        </div>
        <div class="span1"></div>
    </div>    

    <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="bootstrap/js/bootstrap.js"></script>   
</body>
</html>
