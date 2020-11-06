<?php
set_time_limit(0);
ini_set("memory_limit", "512M");
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
$url_redir = "<meta http-equiv=refresh content=\"0;URL=loja_tabela_preco.php\">";

function trataValor($valor) {
    $valor = str_replace(array("R$ ", "R$"), "", strtoupper(trim($valor)));
    $valorInicial = explode(".", $valor);

    if (count($valorInicial) > 2) {
        $valorTratado  = ''; 
        $valorFinal = $valorInicial[count($valorInicial)-1];
        foreach ($valorInicial as $key => $value) {
            if ($value == $valorFinal) {
                $valorTratado .= "," . $value;
            } else {
                $valorTratado .= $value;
            }
        }
    } else {
        $valorTratado     = str_replace(array("R$ ", "."), "", $valor);
    }
    $valorTratado     = str_replace(",", ".", $valorTratado);
    return $valorTratado;

}
function remove_acentos( $texto ){
    $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@", "'" );
    $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_","" );
    return str_replace( $array1, $array2, $texto );
}
function gravaOs($con, $os, $dados) {
    $xdados = json_encode($dados);
    //$dados = json_encode($dados);

    $sql = "INSERT INTO tbl_os_itatiaia(os,dados) VALUES ('{$os}','$xdados')";
    $res = pg_query($con,  $sql);

    if (pg_last_error()) {
        #echo "<pre>".print_r(pg_last_error(),1)."</pre>";
        #echo "<pre>".print_r($os,1)."</pre>";
        #echo "<pre>".print_r(json_encode($dados),1)."</pre>";
        #echo "<pre>".print_r($dados,1)."</pre>";exit;
        return ["msn" => "Erro ao cadastrar"];
    }

    return ["sucesso" => "insert", "msn" => "Cadastrado com sucesso"];
}

$upload_tabela = $_GET["upload_tabela"];
$nova_tabela   = $_GET["nova_tabela"];

//UPLOAD DE TABELA
if(!empty($_POST["grava_upload_tabela"])) {
    $msg_erro = "";

    $registro    = array();
    $extensao    = strtolower(preg_replace("/.+\./", "", $_FILES["upload"]["name"]));

    if (empty($_FILES["upload"]["name"])) {
        $msg_erro .= "Selecione um Arquivo <br />";
    }

    if (!in_array(strtolower($extensao), array("csv", "txt"))) {
        $msg_erro .= "Formado de arquivo inválido <br />";
    }

    $arquivo = file_get_contents($_FILES['upload']['tmp_name']);
    $trata_arquivo = str_replace("\r\n", "\n", $arquivo);
    $trata_arquivo = str_replace("\r", "\n", $arquivo);
    $arquivo = explode("\n", $trata_arquivo);
    $registro = array_filter($arquivo);

    if (count($registro) > 0 && strlen($msg_erro) == 0) {
        unset($registro[0]);
            $json = [];
        foreach ($registro as $key => $rows) {
            
            $retorno = [];
            list($os, $status, $n_serie, $posto, $cep_posto, $uf_posto, $cidade_posto, $endereco_posto, $codigo_posto, $cliente, $cep_cliente, $uf_cliente, $cidade_cliente, $bairro_cliente, $endereco_cliente, $n_cliente, $data_inicio, $qtde_km, $valor_total_km, $data_aprovacao, $data_atendimento, $os_encerrada, $data_encerramento) = explode(";", $rows);



            $json = [
                "os" => $os, 
                "status" => $status, 
                "n_serie" => str_replace(['"','/',"\\"], "", $n_serie), 
                "posto" =>  retira_acentos(utf8_decode($posto)), 
                "cep_posto" => $cep_posto, 
                "uf_posto" => $uf_posto, 
                "cidade_posto" => str_replace(['°','ª','º','(',')','/','\\'], "", utf8_decode($cidade_posto)), 
                "endereco_posto" => str_replace(['°','ª','º','(',')','/','\\'], "",utf8_decode($endereco_posto)), 
                "codigo_posto" => $codigo_posto, 
                "cliente" => str_replace(['°','ª','º','(',')','/','\\'], "", utf8_decode($cliente)), 
                "cep_cliente" => $cep_cliente, 
                "uf_cliente" => $uf_cliente, 
                "cidade_cliente" => str_replace(['°','ª','º','(',')','/','\\'], "", remove_acentos($cidade_cliente)), 
                "bairro_cliente" => str_replace(['°','ª','º','(',')','/','\\'], "", utf8_decode($bairro_cliente)), 
                "endereco_cliente" => str_replace(['°','ª','º','(',')','/','\\'], "", remove_acentos($endereco_cliente)), 
                "n_cliente" => $n_cliente, 
                "data_inicio" => str_replace('/', "-", $data_inicio), 
                "qtde_km" => $qtde_km, 
                "valor_total_km" => $valor_total_km, 
                "data_aprovacao" => str_replace('/', "-", $data_aprovacao), 
                "data_atendimento" => str_replace('/', "-", $data_atendimento), 
                "os_encerrada" => $os_encerrada, 
                "data_encerramento" => str_replace('/', "-", $data_encerramento)
            ];
            

                $res = pg_query($con,"BEGIN TRANSACTION");

                if (strlen($os) > 0) {
                    $retornoOS = gravaOs($con, $os, $json);

                    if ($retornoOS["sucesso"] == "insert") {

                        $retorno["insert"][] = $retornoOS["msn"];

                    } else {

                        $retorno["erro"][] = $retornoOS["msn"];

                    }   
                }

                if (count($retorno["erro"]) == 0 ) {
                    $res = pg_query($con,"COMMIT TRANSACTION");
                    $msg_sucesso = true;
                } else {
                    $res = pg_query($con,"ROLLBACK TRANSACTION");
                    $msg_sucesso = false;
                }

        }



    } 
}



$layout_menu = "cadastro";
$title = "Carga de OS´s para Calculo de KM";
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");
?>
<style>
    .icon-edit {
        background-position: -95px -75px;
    }
    .icon-remove {
        background-position: -312px -3px;
    }
    .icon-search {
        background-position: -48px -1px;
    }
</style>
<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        Shadowbox.init();
        $.dataTableLoad("#tabela");
        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });


    });
</script>
<!-- UPLOAD TABELA -->
    <?php if (count($retorno["erro"]) > 0) { ?>
        <div class="alert alert-error">
            <h4><?php echo "Ocorreu erro em ".count($retorno["erro"])." de ".count($registro).", ao efetuar a importação.";?></h4>
        </div>
    <?php }?>
    <?php if (strlen($msg_erro) > 0) { ?>
        <div class="alert alert-error">
            <h4><?php echo $msg_erro;?></h4>
        </div>
    <?php }?>

    <?php if (count($retorno["insert"]) > 0) {?>
        <div class="alert alert-success">
            <p style="font-size: 16px;margin-top: 10px;">
            <?php echo (count($retorno["insert"]) > 0) ? "Foram <b>inserido(s) ".count($retorno["insert"])."</b>.<br />" : "" ;?>
            </p>
        </div>
    <?php }?>
    <form name='frm_relatorio' METHOD='POST' enctype="multipart/form-data" ACTION='upload_os_calculo_km.php?upload_tabela=true' align='center' class='form-search form-inline tc_formulario' >
        <input type='hidden' name='grava_upload_tabela' value='true' />

        <div class='titulo_tabela'>Selecione o Arquivo</div><br/>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span10">
                <div class="alert">
                    <b>Arquivo deve ser no formato .CSV ou .TXT</b>, separados por ponto e virgula(;).<br />
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <b>Arquivo:</b>
                <div class='control-group tac'>
                    <h5 class='asteristico'>*</h5>
                    <input type='file' required="required" name='upload' id='upload'/>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <p><br/>
            <button class='btn btn-success' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Efetuar o Upload</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
    </form> <br />
<!-- FIM UPLOAD TABELA -->
<?php include "rodape.php";?>
