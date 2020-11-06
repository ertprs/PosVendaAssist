<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit" AND !isset($_POST['upload'])) {
    $produto_referencia = $_POST['produto_referencia'];
    $produto_descricao  = $_POST['produto_descricao'];
    $numero_serie = $_POST['numero_serie'];

    if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
        $sql = "SELECT produto
            FROM tbl_produto
            WHERE fabrica_i = {$login_fabrica}
            AND (
              (UPPER(referencia) = UPPER('{$produto_referencia}')))
            ";
        $res = pg_query($con ,$sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][]    = "Produto não encontrado";
            $msg_erro["campos"][] = "produto";
        } else {
            $produto = pg_fetch_result($res, 0, "produto");
        }
    }

    if(strlen($numero_serie) > 0){
        $cond_numero_serie = " AND tbl_serie_controle.serie = '{$numero_serie}'";
    }

    if (!empty($produto)){
        $cond_produto = " AND tbl_produto.produto = '{$produto}' ";
    }

    if (!count($msg_erro["msg"])) {

        $limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";

        $sql = "SELECT
                tbl_produto.produto,
                tbl_produto.referencia,
                tbl_produto.descricao,
                tbl_serie_controle.serie_controle,
                tbl_serie_controle.serie
            FROM tbl_produto
            JOIN tbl_serie_controle ON tbl_serie_controle.produto = tbl_produto.produto
            WHERE tbl_produto.fabrica_i = {$login_fabrica}
            AND tbl_serie_controle.fabrica = {$login_fabrica}
            $cond_produto
            $cond_numero_serie
            $limit";
        $resSubmit = pg_query($con, $sql);

        if ($_POST["gerar_excel"]) {
            if (pg_num_rows($resSubmit) > 0) {
                $data = date("d-m-Y-H:i");

                $fileName = "relatorio_numero_serie_bloqueada-{$data}.xls";

                $file = fopen("/tmp/{$fileName}", "w");
                $thead = "
                  <table border='1'>
                    <thead>
                      <tr>
                        <th colspan='3' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
                          RELATÓRIO DE SÉRIES BLOQUEADAS
                        </th>
                      </tr>
                      <tr>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Referência</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Número Série</th>
                      </tr>
                    </thead>
                    <tbody>
                ";
                        fwrite($file, $thead);

                        for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
                            $produto            = pg_fetch_result($resSubmit, $i, 'produto');
                            $referencia         = pg_fetch_result($resSubmit, $i, 'referencia');
                            $descricao          = pg_fetch_result($resSubmit, $i, 'descricao');
                            $serie              = pg_fetch_result($resSubmit, $i, 'serie');
                            $serie_controle     = pg_fetch_result($resSubmit, $i, 'serie_controle');


                            $body .="
                            <tr>
                                <td nowrap align='center' valign='top'>{$referencia}</td>
                                <td nowrap align='center' valign='top'>{$descricao}</td>
                                <td nowrap align='center' valign='top'>{$serie}</td>

                            </tr>";
                        }

                    fwrite($file, $body);
                    fwrite($file, "
                            <tr>
                                <th colspan='3' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
                            </tr>
                    </tbody>
                </table>
                ");

                fclose($file);

                if (file_exists("/tmp/{$fileName}")) {
                  system("mv /tmp/{$fileName} xls/{$fileName}");

                  echo "xls/{$fileName}";
                }
            }
            exit;
        }
    }
}

// Upload Numero Serie
if(isset($_POST['bloquear_serie']) OR isset($_POST['desbloquear_serie'])){

    ### INICIO LOGS ###
    $data['arquivos']     = "xls";
    $data['fabrica_nome']   = 'fabrica_'.$login_fabrica;
    $data['arquivo_log']  = 'erro-cadastro-produto-nserie';
    $data['data_sistema']   = Date('Y-m-d');
    $logs = array();
    extract($data);
    $local = "{$arquivos}/{$fabrica_nome}";
    $arquivo_log = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.txt";

    system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" );

    ### FIM LOGS ###

    $caminho = $_FILES['arquivo']['tmp_name'];
    $arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
    if(strlen($arquivo["tmp_name"])==0){
        $msg_erro["msg"][] = "Selecione um arquivo para continuar";
    }

    if(strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
        $config["tamanho"] = 2048575;
        if ($arquivo["type"] <> "text/plain") {
            $msg_erro["msg"][] = "Arquivo em formato inválido!";
        }
        if ($arquivo["size"] > $config["tamanho"]) {
            $msg_erro["msg"][] = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";
        }
        if(count($msg_erro["msg"]) == 0) {

            // Faz o upload
            $nome_arquivo = $caminho;
            $i=1;

            $file_contants = file_get_contents($nome_arquivo);
            $file_contants = explode("\n",$file_contants);

            foreach ($file_contants as $key => $linha){
                unset($erro_serie);

                $i=$key+1;
                if ($linha == "\n"){
                  continue;
                }
                unset ($ref_produto, $numero_de_serie);
                list($ref_produto, $numero_de_serie) = explode(";", $linha);
                $ref_produto = str_replace("\r","",$ref_produto);
                $numero_de_serie = str_replace("\r","",$numero_de_serie);

                if(!empty($ref_produto) and !empty($numero_de_serie)) {
        			$sql = "SELECT produto
            				FROM tbl_produto
            				WHERE fabrica_i = {$login_fabrica}
            				AND (
            				  (UPPER(referencia) = UPPER('{$ref_produto}')))
            				";
        			$res = pg_query($con ,$sql);

    			    if(!pg_num_rows($res)) {
    			        $logs[] = "Número de série não importado! - Referência $ref_produto não encontrada";
                    }else{
    			        $produto_id = pg_fetch_result($res, 0, "produto");
    			    }

    			    if(isset($_POST['bloquear_serie'])){
                        $sql_upload = "SELECT
    					       tbl_serie_controle.serie_controle
            				FROM tbl_produto
            				JOIN tbl_serie_controle ON tbl_serie_controle.produto = tbl_produto.produto
            				WHERE tbl_serie_controle.fabrica = {$login_fabrica}
            				AND tbl_serie_controle.produto = $produto_id
            				AND tbl_serie_controle.serie = '$numero_de_serie'";
    			        $res_upload = pg_query($con, $sql_upload);

                        if (in_array($login_fabrica, array(169,170,183))){
                            $sql_serie = "
                                    SELECT serie
                                    FROM tbl_numero_serie
                                    WHERE produto = {$produto_id}
                                    AND fabrica = {$login_fabrica}
                                    AND serie = '{$numero_de_serie}' ";
                            $res_serie = pg_query($con, $sql_serie);

                            if (pg_num_rows($res_serie) > 0){
                                $numero_de_serie = pg_fetch_result($res_serie, 0, 'serie');
                            }else{
                                $erro_serie = "erro";
                            }
                        }

                        if(strlen($numero_de_serie) > 30){
                            $logs[] = "Número de série não importado! - Número de série $numero_de_serie não pode passar de 30 Caracteres";
                        }else{
      			           if(pg_num_rows($res_upload) > 0){
                                $logs[] = "Número de série não importado! - Número de série $numero_de_serie já bloqueado para o produto $ref_produto";
                            }else if (in_array($login_fabrica, array(169,170,183)) AND $erro_serie == "erro"){
                                $logs[] = "Número de série não importado! - Número de série $numero_de_serie não encontrado";
                            }else{
        			            $sql_serie_insert = "INSERT INTO tbl_serie_controle(
        												fabrica,
        												produto,
        												serie,
        												quantidade_produzida
        											)VALUES(
        												$login_fabrica,
        												$produto_id,
        												substr('$numero_de_serie',1,30),
        												0
        											)";
        			            $res_serie_insert = pg_query($con, $sql_serie_insert);
                            }
                        }
    			        if(count($logs) == 0) {
    				        $msg_success = "Séries Bloqueadas com Sucesso";
    			        }else{
                            $msg_alert = "Algumas Séries não foram bloqueadas verifique log de Erro";
                        }
    			    }else{
                        $sql_upload = "SELECT
                                tbl_serie_controle.serie_controle
                            FROM tbl_produto
                            JOIN tbl_serie_controle ON tbl_serie_controle.produto = tbl_produto.produto
                            WHERE tbl_serie_controle.fabrica = {$login_fabrica}
                            AND tbl_serie_controle.produto = $produto_id
                            AND tbl_serie_controle.serie = '$numero_de_serie'";
                        $res_upload = pg_query($con, $sql_upload);
                        if(pg_num_rows($res_upload) == 0){
                            $logs[] = "Número de série não desbloqueado! - Número de série $numero_de_serie não está bloqueado para o produto $ref_produto";
                        }else{
                            $sql_delete = "DELETE FROM tbl_serie_controle
                            			  WHERE serie = '$numero_de_serie'
                            			  AND produto = $produto_id
                            			  AND fabrica = $login_fabrica";
                            $res_delet = pg_query($con, $sql_delete);

  			                if (strlen(pg_last_error($con)) > 0) {
  				                $logs[] = "Erro ao Desbloquear a Série $numero_de_serie";
  			                }
                            if(count($logs) == 0) {
                                $msg_success = "Séries Desbloqueadas com Sucesso";
                            }else{
                                $msg_alert = "Algumas Séries não foram desbloqueadas verifique log de Erro";
                            }
                        }
    			    }
    		    }
            }

            if(count($logs) > 0){
                if(file_exists($local)){
                    system ("rm $local/*");
                }
                $file_log = fopen($arquivo_log,"w+");
                fputs($file_log,implode("\r\n", $logs));
                fclose ($file_log);
                $msg_log = "true";
            }else{
                $msg_log = "false";
            }
        }
    }
}

// AJAX CADASTRA SERIE
if($_POST["cadastra_serie"] == true){

    $produto_referencia = trim($_POST['produto_referencia']);
    $numero_serie       = trim($_POST['numero_serie']);
    $motivo             = trim($_POST['motivo']);

    if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
        $sql = "SELECT produto
            FROM tbl_produto
            WHERE fabrica_i = {$login_fabrica}
            AND (
              (UPPER(referencia) = UPPER('{$produto_referencia}')))
            ";
        $res = pg_query($con ,$sql);

        if (pg_num_rows($res) > 0) {
            $produto = pg_fetch_result($res, 0, "produto");
        }
    }

    if (in_array($login_fabrica, array(169,170,183)) AND !empty($numero_serie)){
        $sql = "
            SELECT serie
            FROM tbl_numero_serie
            WHERE produto = {$produto}
            AND fabrica = {$login_fabrica}
            AND serie = '{$numero_serie}' ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0){
            $numero_serie = pg_fetch_result($res, 0, 'serie');
        }else{
            unset($numero_serie);
        }
    }

    if (empty($produto) AND empty($numero_serie)){
        $retorno = array("erro" => utf8_encode("Produto e Número de Série não encontrados"));
    }else if (empty($produto)){
        $retorno = array("erro" => utf8_encode("Produto não encontrado"));
    }else if (empty($numero_serie)){
        $retorno = array("erro" => utf8_encode("Número de Série não encontrado"));
    }

    if (!count($retorno)) {
        $sql = "SELECT
              tbl_serie_controle.serie_controle
            FROM tbl_produto
            JOIN tbl_serie_controle ON tbl_serie_controle.produto = tbl_produto.produto
            WHERE tbl_serie_controle.fabrica = {$login_fabrica}
            AND tbl_serie_controle.produto = $produto
            AND tbl_serie_controle.serie = '$numero_serie'";
        $res_serie = pg_query($con, $sql);

        if(pg_num_rows($res_serie) > 0){
            $retorno = array("erro" => utf8_encode("Número de série já cadastrado para esse produto"));
        }else{
            $sql_insert = "INSERT INTO tbl_serie_controle(
                            fabrica,
                            produto,
                            serie,
                            quantidade_produzida,
                            motivo
                          )VALUES(
                            $login_fabrica,
                            $produto,
                            '$numero_serie',
                            0
                            ".(empty($motivo)? ", null" : ",'$motivo'")."
                          )";
            $res_insert = pg_query($con, $sql_insert);
        }
    }

    if(!count($retorno)) {
        $retorno = array("ok" => utf8_encode("Número de série cadastrado com sucesso"));
    }
    exit(json_encode($retorno));
}

//AJAX EXCLUI SERIE
if($_POST["btn_acao"] == "excluir_serie"){

    $produto_serie = $_POST["produto_serie"];
    $serie_controle = $_POST["serie_controle"];

    pg_query($con, "BEGIN");

    $sql_delete = "DELETE FROM tbl_serie_controle
                  WHERE serie_controle = $serie_controle
                  AND fabrica = $login_fabrica
                  AND produto = $produto_serie";
    $res_delete = pg_query($con, $sql_delete);

    if (!pg_last_error()) {
        pg_query($con, "COMMIT");
        ob_clean();
        echo "success";
    } else {
        ob_clean();
        pg_query($con, "ROLLBACK");
        echo "error";
    }
    exit;
}

$layout_menu = "cadastro";

$title = "CADASTRO NÚMERO DE SÉRIE BLOQUEADA";
include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $.autocompleteLoad(Array("produto", "peca", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
          $.lupa($(this));
        });
    });

    function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }

    $(document).on("click", "button[name=cadastra_serie]", function () {
        var numero_serie = '';
        var produto_referencia = '';
        var produto_descricao = '';
        var motivo = '';

        numero_serie       = $("#numero_serie").val();
        produto_referencia = $("#produto_referencia").val();
        produto_descricao  = $("#produto_descricao").val();
        
        <?php if ($login_fabrica == 183){ ?>
            motivo = $("#motivo").val();
        <?php } ?>
        
        if ((produto_referencia == '' || produto_referencia == undefined) && (numero_serie == '' || numero_serie == undefined)){
            alert("Preencha os campos Produto e Número de Série");
        }else if ((produto_referencia == '' || produto_referencia == undefined) || (produto_descricao == '' || produto_descricao == undefined)){
            alert("Favor preencher o Produto");
        }else if (numero_serie == '' || produto_referencia == undefined){
            alert("Favor preencher o Numero de Série");
        }
        
        if(produto_referencia != '' && numero_serie != ''){
            $.ajax({
                    url: "cadastro_produto_nserie.php",
                    type: "POST",
                    data: { cadastra_serie: true, numero_serie: numero_serie, produto_referencia: produto_referencia, motivo: motivo },
                }).always(function(data) {
                    data = $.parseJSON(data);

                if(data.erro) {
                    alert(data.erro);
                } else {
                    alert(data.ok);
                }
            });
        }
    });

    $(document).on("click", "button[name=excluir_serie]", function () {
        if(confirm('Deseja Excluir o Número de Série ?')) {
            var produto_serie = $(this).parent().find("input[name=produto_serie]").val();
            var serie_controle = $(this).parent().find("input[name=serie_controle]").val();
            var that     = $(this);

            $.ajax({
                async: false,
                url: "<?=$_SERVER['PHP_SELF']?>",
                type: "POST",
                dataType: "JSON",
                data: { btn_acao: "excluir_serie", produto_serie: produto_serie, serie_controle: serie_controle  },
                complete: function (data) {
                    data = data.responseText;
                    if (data == "success") {
                        alert("Número de série excluido com sucesso");
                        $(that).parents("tr").find("td").hide();
                    }
                }
            });
        }
    });
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
    <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}

if (!empty($msg_success)) {
?>
    <div class="alert alert-success">
        <h4><?=$msg_success?></h4>
    </div>
<?php
}

if (!empty($msg_alert)) {
?>
    <div class="alert alert-info">
        <h4><?=$msg_alert?></h4>
    </div>
<?php
}

?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios (para bloqueio) </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class="row-fluid">
        <div class="span2"></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("numero_serie", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='numero_serie'>Numero de Série</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="numero_serie" name="numero_serie" class='span9' maxlength="30" value="" >
                    </div>
                </div>
            </div>
        </div>
        <?php if ($login_fabrica == 183){ ?>
        <div class="span4">
            <div class="control-group">
                <label class='control-label' for='motivo'>Motivo</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <input type="text" id="motivo" name="motivo" class='span12' value="" >
                    </div>
                </div>
            </div>
        </div>
        <?php }else{ ?>
        <div class="span4">
            <div class="control-group">
                <label class='control-label' for='numero_serie'></label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <h5 class='asteristico'>*</h5>
                        <button type="button" name='cadastra_serie' id='cadastra_serie' class='btn btn-danger'>Bloquear Série</button>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
        <div class="span2"></div>
    </div>

    <p><br/>
        <?php if ($login_fabrica == 183){ ?>
        <button type="button" name='cadastra_serie' id='cadastra_serie' class='btn btn-danger'>Bloquear Série</button> &nbsp;&nbsp;
        <?php } ?>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>

<!-- Numero de Serie -->
<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data" align='center' class='form-search form-inline tc_formulario'>

    <!-- <input type="hidden" name="upload" value="acao" /> -->
    <div class='titulo_tabela '>Parâmetros para Upload</div>
    <br />
    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span10">
            <div class="alert" style="text-align: left !important;">
                <p>
                    O arquivo selecionado deve estar no seguinte formato:
                    <ul>
                        <li>txt e sem cabeçalho</li>
                        <li>Vir com os campos:
                            <ul>
                                <li>Referência</li>
                                <li>Número de Série</li>
                            </ul>
                        </li>
                        <li>Os valores devem vir separados por ponto-e-vírgula (;)</li>
                    </ul>
                </p>
            </div>
        </div>
        <div class="span1"></div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class='span8'>
            <div class='control-group'>
                <label class='control-label' for='tabela_nserie'>Arquivo txt</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="file" name="arquivo" class='span12' />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
    <p>
        <br/>
        <input type='submit' name='bloquear_serie' class='btn btn-danger' value='Bloquear Série'>
        <?if(in_array($login_fabrica, array(3,169,170))){?>
            <input type='submit' name='desbloquear_serie' class='btn btn-info' value='Desbloquear Série'>
        <?php } ?>
    </p>
    <?php
        if($msg_log == "true"){
            echo "<br/><div clas='row-fluid'>
                <div class='span12 tac'>
                    <a href='$arquivo_log' target='_blank'><span class='label label-important'>Download log erro</span></a>
                    </div>
                </div><br/>
            ";
        }
    ?>
    <br/>
</form>
<!-- Fim Numero de Serie -->

<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {

        if (pg_num_rows($resSubmit) > 500) {
            $count = 500;
        ?>
            <div id='registro_max'>
                <h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
            </div>
      <?php
        } else {
            $count = pg_num_rows($resSubmit);
        }
    ?>
        <table id="resultado_serie_bloqueada" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class='titulo_coluna' >
                    <th>Referência</th>
                    <th>Descricão</th>
                    <th>Numero Série</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    for ($i = 0; $i < $count; $i++) {
                        $produto            = pg_fetch_result($resSubmit, $i, 'produto');
                        $referencia         = pg_fetch_result($resSubmit, $i, 'referencia');
                        $descricao          = pg_fetch_result($resSubmit, $i, 'descricao');
                        $serie              = pg_fetch_result($resSubmit, $i, 'serie');
                        $serie_controle     = pg_fetch_result($resSubmit, $i, 'serie_controle');
                        $body = "<tr>
                              <td class='tal'>{$referencia}</td>
                              <td class='tac'>{$descricao}</td>
                              <td class='tal'>{$serie}</td>
                              <td class='tac'>
                                <input type='hidden' name='produto_serie' value='$produto'>
                                <input type='hidden' name='serie_controle' value='$serie_controle'>
                                <button type='button' name='excluir_serie' class='btn btn-danger' id=''>Excluir</button>
                              </td>
                            </tr>";
                        echo $body;
                    }
                ?>
            </tbody>
        </table>
        <?php if ($count > 50) { ?>
                <script>
                    $.dataTableLoad({ table: "#resultado_serie_bloqueada" });
                </script>
        <?php } ?>
        <br />
        <?php
            $jsonPOST = excelPostToJson($_POST);
        ?>

        <div id='gerar_excel' class="btn_excel">
            <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
            <span><img src='imagens/excel.png' /></span>
            <span class="txt">Gerar Arquivo Excel</span>
        </div>
      <?php
    }else{
        echo '
            <div class="container">
                <div class="alert">
                    <h4>Nenhum resultado encontrado</h4>
                </div>
            </div>';
    }
}
include 'rodape.php';?>
