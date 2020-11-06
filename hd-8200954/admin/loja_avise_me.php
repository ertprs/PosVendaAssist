<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
use Lojavirtual\AviseMe;
use Lojavirtual\Comunicacao;

$objAviseMe      = new AviseMe();
$objComunicacao  = new Comunicacao();
$dadosAviseMe    = $objAviseMe->get();

if ($_POST["ajax_envia_avise_me"]) {

    $id    = $_POST["id"];
    $dados =  $objAviseMe->get($id);

    $dadosEnvio["produto"]    = $dados["codigo_peca"] . " - " . $dados["nome_peca"];
    $dadosEnvio["nome_posto"] = $dados["nome_cliente"];
    $dadosEnvio["email_posto"] = $dados["email_cliente"];

    $retorno = $objComunicacao->enviaAviseMe($dadosEnvio);
    if ($retorno) {
        $objAviseMe->updateAvisado($id);
        exit(json_encode(array("erro" => false, "msg" => "Enviado com sucesso.")));
    } else {
        exit(json_encode(array("erro" => true, "msg" => "Erro ao enviar.")));
    }

}

$layout_menu = "cadastro";
$title = "Avise-me - Loja Virtual";
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");
?>
<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        Shadowbox.init();
        $.dataTableLoad("#tabela");
        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });

        $(".btn-avisar").click(function() {
            var id = $(this).data("id");

            $.ajax({
                url : "<?php echo $_SERVER['PHP_SELF']; ?>",
                type: "POST",
                dataType: "json",
                data: { ajax_envia_avise_me : true, id : id },
                complete: function(data){
                    var retorno = JSON.parse(data.responseText);
                    if (retorno.erro == true) {
                        alert(retorno.msg);
                        return false
                    } else {
                        $("#tr-"+id).remove();
                        alert(retorno.msg);
                    }
                }
            });

        });

    });

</script>
    <?php
        if (empty($objAviseMe->_loja)) {
            exit('<div class="alert alert-error"><h4>Loja não encontrada.</h4></div>');
        }
    ?>
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <thead>
            <tr class='titulo_coluna' >
                <th class="tac">Cadastrado em</th>
                <th class="tal">Cliente</th>
                <th class="tal">Produto</th>
                <th class="tac">Avisado</th>
                <th class="tac">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($dadosAviseMe as $kAvise => $rowsAvise) {?>
        <tr id="tr-<?php echo $rowsAvise["loja_b2b_avise_me"];?>">
            <td class='tac'><?php echo $rowsAvise["data"];?></td>
            <td class='tal'><?php echo $rowsAvise["codigo_cliente"];?> - <?php echo $rowsAvise["nome_cliente"];?></td>
            <td class='tal'><?php echo $rowsAvise["codigo_peca"];?> - <?php echo $rowsAvise["nome_peca"];?></td>
            <td class='tac'><?php echo ($rowsAvise["avisado"] == 'f') ? '<span class="label label-important">Não Avisado</span>' : '';?></td>
            <td class='tac'>
                <button type="button" data-id="<?php echo $rowsAvise["loja_b2b_avise_me"];?>" class="btn btn-mini btn-avisar btn-primary"><i class="icon-envelope icon-white"></i> Avisar</button>
            </td>
        </tr>
        <?php }?>
        </tbody>
    </table>
</div> 
<?php include 'rodape.php';?>
