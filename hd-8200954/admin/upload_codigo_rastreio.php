
<? // MONTEIRO
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

// Upload Numero Serie

//if(isset($_POST['upload'])){

if(isset($_POST['gravar'])){

    ### INICIO LOGS ###

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

		$tipo_arquivo = preg_match("/\.(csv){1}$/i", $arquivo["name"]);
		if($tipo_arquivo== 0){
			$msg_erro["msg"][] = "Arquivo no formato inválido";
		}
		//if($arquivo["type"] <> "text/plain" AND $arquivo["type"] <> "text/csv") {
          //  $msg_erro["msg"][] = "Arquivo em formato inválido!";
        //}

        if($arquivo["size"] > $config["tamanho"]) {
            $msg_erro["msg"][] = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";
        }

        if(count($msg_erro["msg"]) == 0) {
            $nome_arquivo = $caminho;
            $i=1;

            $file_contants = file_get_contents($nome_arquivo);
            $file_contants = explode("\n",$file_contants);
            #pg_query($con,"BEGIN TRANSACTION");

            foreach ($file_contants as $key => $linha){
                $i=$key+1;
                if ($linha == "\n"){
                  continue;
                }
                unset ($ref_produto, $numero_de_serie);
                list($nota_fiscal, $cnpj_posto, $codigo_rastreio) = explode(";", $linha);
                $nota_fiscal = str_replace("\r","",$nota_fiscal);
                $pedido = str_replace("\r","",$cnpj_posto);
                $codigo_rastreio = str_replace("\r","",$codigo_rastreio);
                #$cnpj_posto = preg_replace("/\W/","",$cnpj_posto);

                if(!empty($nota_fiscal) and !empty($cnpj_posto) and !empty($codigo_rastreio)) {
                    $sql = "SELECT tbl_faturamento.faturamento
                        FROM tbl_faturamento
						JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
						WHERE tbl_faturamento.fabrica = $login_fabrica
						AND tbl_faturamento_item.pedido = $pedido
                        AND tbl_faturamento.nota_fiscal = '$nota_fiscal'
                       ";
                    $res = pg_query($con ,$sql);
                    if(!pg_num_rows($res)) {
                        $logs[] = "Não encontrado faturamento para Referência: $nota_fiscal CNPJ: $cnpj_posto ";
                    }else{
                        $id_faturamento = pg_fetch_result($res, 0, 'faturamento');
                        $update = "UPDATE tbl_faturamento set conhecimento = '$codigo_rastreio'
                                    WHERE faturamento = $id_faturamento
                                    AND fabrica = $login_fabrica";
                        $res_update = pg_query($con, $update);
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
                $msg_alert = "Alguns códigos não foram atualizados, verifique o log de erro";
            }else{
                $msg_success = "Códigos atualizados com sucesso";
                $msg_log = "false";
            }
        }
    }
}

$layout_menu = "callcenter";

$title = "Upload Código Rastreio";
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
  <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
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
          O arquivo selecionado deve estar no seginte formato:
          <ul>
            <li>CSV e sem cabeçalho</li>
            <li>Vir com os campos:
              <ul>
                <li>Nota Fiscal</li>
                <li>Pedido</li>
                <li>Código de Rastreio</li>
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
        <label class='control-label' for='tabela_nserie'>Arquivo CSV</label>
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

    <!--
    <button class='btn btn-info' id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));">Realizar Upload</button>
    <input type='hidden' id="btn_click" name='btn_acao' value='' />
    -->
    <input type='submit' name='gravar' class='btn' value='Gravar'>
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

</div>
<?php include 'rodape.php';?>
