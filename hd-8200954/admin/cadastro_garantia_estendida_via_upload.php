<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if(isset($_POST['btn_acao'])){
    $btn_acao = $_POST['btn_acao'];
}

if (isset($_FILES['arquivo_garantia'])) {
    $arquivo = $_FILES['arquivo_garantia'];
    $erro_csv = array();

    $types = array("csv","text/csv", "txt");
    $type  = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));


    if (!empty($arquivo["error"])) {

        $msg_erro["msg"][] = "Favor anexar o Arquivo";

    } else {

        if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["size"] > 0) {
            if (!in_array($type, $types)) {
                $msg_erro = "Formato inválido, é aceito apenas o formato csv";
            } else {
                $file = fopen($arquivo['tmp_name'],"r");

                if ($file) {
                    $conteudo = explode("\n", file_get_contents($arquivo["tmp_name"]));

                     $conteudo = array_filter(array_map(function($valor){           
                        return explode(";", utf8_encode(trim($valor)));
                    }, $conteudo), function($valor){
                        global $erro_csv;
                        if (count($valor) <> 3) {
                            if (!empty($valor[0])) {
                                $erro_csv[] = "Layout do arquivo fora do padrão";
                            }   
                            return false;
                        } else {
                            return true;
                        }
                    });

                    if (count($erro_csv) > 0) {
                        $msg_erro["msg"][] = $erro_csv[0];
                    }

                    if (count($msg_erro["msg"]) == 0) {

                        foreach ($conteudo as $key => $rows) {

                            $referencia_produto   = trim($rows[0]);
                            $serie                = $rows[1];
                            $qtdemes              = $rows[2];
                            
                            $sqlPD = "SELECT produto
                                        FROM tbl_produto
                                       WHERE referencia = '$referencia_produto'
                                         AND ativo IS TRUE
                                         AND fabrica_i = $login_fabrica";
                            $resPD = pg_query($con, $sqlPD);

                            if (pg_num_rows($resPD) == 0) {
                                continue;
                            }

                            $produto = pg_fetch_result($resPD, 0, 'produto');
                            
                            $sqlVerfGarantia = "SELECT cliente_garantia_estendida
                                                  FROM tbl_cliente_garantia_estendida
                                                 WHERE numero_serie = '$serie'
                                                   AND produto = $produto
                                                   AND fabrica = $login_fabrica";
                            $resVerfGarantia = pg_query($con, $sqlVerfGarantia);

                            if (pg_num_rows($resVerfGarantia) > 0) {

                                $cliente_garantia_estendida = pg_fetch_result($resVerfGarantia, 0, 'cliente_garantia_estendida');

                                $sqlUp = "UPDATE tbl_cliente_garantia_estendida
                                             SET garantia_mes = $qtdemes
                                           WHERE cliente_garantia_estendida = $cliente_garantia_estendida
                                             AND fabrica = $login_fabrica";
                                $resUp = pg_query($con, $sqlUp);

                                if (strlen(pg_last_error($con)) > 0) {
                                    $msg_erro["msg"][] = "Erro ao atualizar o Numero de serie: $serie";
                                } else {
                                    $msg_sucesso = true;
                                }

                            } else {

                                $sqlINS = "INSERT INTO tbl_cliente_garantia_estendida 
                                                                                    (
                                                                                        admin,
                                                                                        nome,
                                                                                        endereco,
                                                                                        numero,
                                                                                        cep,
                                                                                        cidade,
                                                                                        revenda_nome,
                                                                                        nota_fiscal,
                                                                                        data_compra,
                                                                                        estado,
                                                                                        numero_serie,
                                                                                        produto,
                                                                                        fabrica, 
                                                                                        garantia_mes
                                                                                    ) VALUES 
                                                                                    (
                                                                                        $login_admin, 
                                                                                        '', 
                                                                                        '', 
                                                                                        '', 
                                                                                        '', 
                                                                                        '', 
                                                                                        '', 
                                                                                        '', 
                                                                                        '".date('Y-m-d')."', 
                                                                                        '', 
                                                                                        '$serie', 
                                                                                        $produto, 
                                                                                        $login_fabrica, 
                                                                                        $qtdemes
                                                                                    )";
                                $resINS = pg_query($con, $sqlINS);

                                if (strlen(pg_last_error($con)) > 0) {
                                    $msg_erro["msg"][] = "Erro ao inserir o Numero de serie: $serie";
                                } else {
                                    $msg_sucesso = true;
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $msg_erro["msg"][] = "Erro ao fazer o upload do arquivo";
        }
    }
}

$layout_menu = "cadastro";
$title = "Cadastro de Garantia Estendida";
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
    <?php if (count($msg_erro["msg"]) > 0) {?>
        <div class="alert alert-error">
            <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
        </div>
    <?php }?>

    <?php if ($msg_sucesso){?>
        <div class="alert alert-success">
            <h4><?php echo "Arquivo importado com sucesso";?></h4>
        </div>
    <?php }?>

    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>


<form name="frm_upload_garantia" id="frm_upload_garantia" method="POST" action="<?echo $PHP_SELF?>"  class="form-search form-inline tc_formulario" enctype="multipart/form-data">
    <div class='titulo_tabela '>Cadastro de Garantia Estendida - Via upload</div>
    <br/>
    <div class='row-fluid'>
        <div class="span1"></div>
         <div class="span10">
             <div class="alert" style="font-size: 16px;">
                <p>Layout do arquivo: <em><b>REFERENCIA;SERIE;QTDE_MESES</b></em></p>
                Deverá ser no formato CSV (.csv) ou TXT (.txt), Os campos devem ser separados por ponto e virgula(;)
            </div>
        </div>
        <div class="span1"></div>
    </div>

    <div class='row-fluid'>
        <div class="span2"></div>
        <div class="span2">
            <div class='control-group <?=(in_array('arquivo_garantia', $msg_erro['campos'])) ? "error" : "" ?>' >
                <div class="controls controls-row">
                    <div class="span12"><h5 class='asteristico'>*</h5>
                        <label>Upload de arquivo</label>
                        <input type='file' name='arquivo_garantia' id="arquivo_garantia" size='18' />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <p>
        <button type="submit" data-loading-text="Realizando Upload..." class="btn salvar_arquivo">Upload de Arquivo</button> 
        <a href="cadastro_garantia_estendida_new.php" class="btn btn-primary">Voltar</a>
    </p>
    <br />
</form>
<?php include 'rodape.php';?>
