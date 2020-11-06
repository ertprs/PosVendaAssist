<?php 

include ('../class/inspecao_posto/Inspecao.php');

include ('../class/inspecao_posto/Pergunta.php');

include ('../class/inspecao_posto/TipoResposta.php');


include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


$admin_privilegios="info_tecnica";
include 'autentica_admin.php';

include 'funcoes.php';

include_once "../class/aws/s3_config.php";
include_once S3CLASS;

$imagem_upload =  $login_admin."_".date("dmyhmi");

# Verifica permissões do usuario para acessar a tela
$sql = "select login,privilegios,nome_completo, admin_sap from tbl_admin where fabrica = $login_fabrica and admin = $login_admin;";

$res = pg_query ($con,$sql);
$usuario_privilegio = pg_fetch_result($res, 0, 1);
$nome_admin = pg_fetch_result($res, 0, 2);
$admin_sap = pg_fetch_result($res,0,3);
if($usuario_privilegio != '*' && $admin_sap <> 't'){
	header('location: menu_auditoria.php');			
}



$qtdFotos = array();
$arrArquivos = array();

if($login_fabrica == 74){
    $qtdFotos = array("aparenciaInterna" => array("descricao"=>"Aparência Interna", "qtd" => 5),
    "aparenciaExterna" => array("descricao"=>"Aparência Externa", "qtd" => 2)
    );
}

function getArquivos($prefix){
    global $s3;
    global $arrArquivos;

    $s3->getObjectList($prefix);
   
    
    foreach($s3->files as $path){
        $pathInfo = pathinfo($path);
        $nomeArquivo = $pathInfo["basename"];
        $extencao = $pathInfo["extension"];
        
        $fileThumb = $s3->getLink("thumb_".$nomeArquivo, false);			
        $file = $s3->getLink($nomeArquivo, false);
        
        
        list($id,$i) = explode("_", $pathInfo["filename"]);
        $arrArquivos["arquivo_".$i]["thumb"] = $fileThumb;
        $arrArquivos["arquivo_".$i]["file"] = $file;
        $arrArquivos["arquivo_".$i]["name"] = $nomeArquivo;
        
    }
}
if(strlen($_GET["auditoria_online"]) > 0 ){
    
    #cria perguntas para inspeção 
    $inspecao = new Inspecao($login_fabrica, $_GET["auditoria_online"]);
    $s3 = new AmazonTC("inspecao", $login_fabrica);

    $prefix = $inspecao->idAuditoriaOnline;
    getArquivos($prefix);
    


}else if( strlen($_POST["auditoria_online"]) > 0 ){
    #cria perguntas para inspeção 
    $inspecao = new Inspecao($login_fabrica, $_POST["auditoria_online"]);


}else{
    #cria perguntas para inspeção 
    $inspecao = new Inspecao($login_fabrica);
}

if($_POST['btn_acao'] == 'gravar' || $_POST['btn_acao'] == 'parcial'){

    global $arrArquivos;
    $codigo_posto = $_POST["codigo_posto"];
    $sqlVerificaPosto = "SELECT posto 
                         FROM tbl_posto_fabrica
                         WHERE fabrica = {$login_fabrica} AND 
                               codigo_posto = '{$codigo_posto}'";
    $resVerificaPosto = pg_query($con, $sqlVerificaPosto);
    
    if(pg_num_rows($resVerificaPosto) > 0){
        $posto = pg_fetch_result($resVerificaPosto, 0, "posto");
    }

    $inspecao->save($posto);
    if(count($msg_erro["msg"]) == 0){
        if(!empty($_POST['qtd_arquivos'])){

            $s3 = new AmazonTC("inspecao", $login_fabrica);
            $qtdArquivos = $_POST['qtd_arquivos'];
            for($i = 0; $i<$qtdArquivos; $i++){
                if(!empty($_POST["arquivo_".$i])){
                    $arquivo = $_POST["arquivo_".$i];
                    $extencao = explode('.', $arquivo );
                    if(count($extencao) > 0){
                        $extencao = $extencao[1];
                    }else{
                        $extencao = "jpg";
                    }

           
                    $nomeArquivo = $inspecao->idAuditoriaOnline."_".$i.".".$extencao;
                    $aux = array(
                        array("file_temp" => $arquivo,
                        "file_new" => $nomeArquivo
                        )
                    );		
                    $ret = $s3->moveTempToBucket($aux);
                
                    getArquivos($inspecao->idAuditoriaOnline);
                
                }
            }

        }

        $msg = "Gravado com Sucesso";
        //hd-2051756 Trazer os dados que foram 'Gravados Parcial'
        
        //$inspecao = new Inspecao($login_fabrica);
        //var_dump($inspecao->nomePosto);
        //unset($arrArquivos);
    }
      
}


function drawUploadForm($qtdFotos){

/* identificador do form */
$j=0;
    echo "<div class='row'>
                <div class='span2'></div>		
                <div class='span4'>
                	<div class='control-group'>							
                		<div class='controls'>";
foreach($qtdFotos as $item){
/* abre tags iniciais */


    for($i = 0; $i < $item["qtd"]; $i++, $j++){
        echo "<form id='file_form_$j' name='file_form' action='temp_upload_inspecao_posto.php' method='post' enctype='multipart/form-data'> </form>";

    }

  
}
  echo "                </div>
                      </div>
                  </div>
              </div>
          </div>";
}

function drawUploadFormItens($qtdFotos){
    global $imagem_upload;
    global $arrArquivos;
    $qtdItemLinha = 0;
/* identificador dos itens */
    $j=0;
    foreach($qtdFotos as $item){
/* abre tags iniciais */
        echo "<br/><div class='row'>
          <div class='span2'></div>";

            for($i = 0; $i < $item["qtd"]; $i++, $j++){
                    if(!empty($arrArquivos["arquivo_".$j]["name"])){
                        $nomeArquivo = $arrArquivos["arquivo_".$j]["name"];
                    }else{
                        $nomeArquivo = $imagem_upload."_".$j;
                    }

                    if(!empty($arrArquivos["arquivo_".$j]["file"])){
                        $href = "href='".$arrArquivos["arquivo_".$j]["file"]."'";
                    }else{
                        $href = "";
                    }
                if($qtdItemLinha == 2){
                    /* fecha row */
            
                    echo "    <div class='span2'></div>
                  </div>";
                    /* abre nova row */
                    echo "<br/><div class='row'>
                      <div class='span2'></div>";
                        $qtdItemLinha = 0;
                }
                /* abre coluna */ ?>
                <div class="span3">
                <label class="control-label"><?=$item["descricao"]?></label>
                <input form="file_form_<?=$j?>" type="file" id="arq<?=$j?>" name="arq<?=$j?>" value=''> 
                <input form="file_form_<?=$j?>" type="hidden" name="nome_arquivo" value="<?php echo $nomeArquivo; ?>">			        
                <input form="file_form_<?=$j?>" type="hidden" name="i" value="<?=$j?>">
                <span  form="file_form_<?=$j?>" id="loading_<?=$j?>" style="display:none">Uploading...</span>
                <a  id="link_file_<?=$j?>" <?=$href?>><img alt=" " form="file_form_<?=$j?>" src="<?=$arrArquivos['arquivo_'.$j]['thumb']?>" id="img_<?=$j?>"/></a>
                </div>
<? $qtdItemLinha ++;
            }
            $qtdItemLinha = 0;
            /* fecha tags */
            echo "    <div class='span2'></div>
          </div><br/>";
    }


}
$title = "CADASTRO DA INSPEÇÃO";
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
<script language="javascript" src="plugins/jquery.form.js"></script>
<script language="javascript" src="../js/FancyZoom.js"></script>
<script language="javascript" src="../js/FancyZoomHTML.js"></script>

<script language="javascript">

	$(function() {
		$.autocompleteLoad(Array("posto"));
        setupZoom();
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
        if($("form[name=file_form]").length > 0){
            

            var container = $("#btn_acao").parent();
            var inputQtdArquivos = $("<input>");
            inputQtdArquivos.attr({
              id:"qtd_arquivos",
                type:"hidden",
                name:"qtd_arquivos",
                value: $("form[name=file_form]").length
                });
            container.append(inputQtdArquivos);
            /* cria inputs para salvar nome da imagem temporaria */
            $("form[name=file_form]").each(function(i, el){
            var inputNomeArquivo = $("<input>");
            inputNomeArquivo.attr({
              id:"arquivo_"+i,
                type:"hidden",
                name:"arquivo_"+i
                });
            container.append(inputNomeArquivo);
            $("#arq"+i).change(function(){
            $("#loading_"+i).fadeIn('1000');
            $("#file_form_"+i).submit();
        });

        });

            $("form[name=file_form]").ajaxForm({
              beforeSend: function(){
                console.log("Sending");
            },
                complete: function(data) {
                console.log("Return");
                if (data.responseText == "erro") {
                    alert("Arquivo inválido, selecione outro arquivo!");                
                } else {
                    data = $.parseJSON(data.responseText);

                
                    $('#img_'+data.i).attr('src',data.thumb);
                    $('#link_file_'+data.i).attr('href',data.file);
                    $('#arquivo_'+data.i).val(data.nome);
                    $("#loading_"+data.i).fadeOut('1000');
                
                    console.log(data);
                    setupZoom();
                }


            }
       });
         }
	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }
</script>

<?php
if(empty($inspecao->dataVisita)){
    $dataVisita = date("d/m/Y");
}else{
    
    $dataVisita = date("d/m/Y", strtotime($inspecao->dataVisita));
}


if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
    <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<? }else if (strlen($msg) > 0 AND count($msg_erro["msg"])==0) {?>

    <div class="alert alert-success">
        <h4><? echo $msg; ?></h4>
    </div>
<? } ?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Inspeção Posto Autorizado</div>
       	<input type="hidden" name="auditoria_online" value="<?=$inspecao->idAuditoriaOnline?>"/>
		<br/>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='inspetor'>Inspetor</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
                            <h5 class='asteristico'>*</h5>
							<input type="text" name="nome_admin" id="nome_admin" class='span12' value="<? echo $nome_admin ?>" >
							<input type="hidden" name="inspetor" value="<?=$login_admin?>"/>
						</div>
					</div>
				</div>
			</div>
            <div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
   								<h5 class='asteristico'>*</h5>
								<input type="text" name="data" id="data" size="12" maxlength="10" class='span12' value="<?=$dataVisita?>">
						</div>
					</div>
				</div>
			</div>
        </div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
                            <h5 class='asteristico'>*</h5>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $inspecao->cnpjPosto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $inspecao->nomePosto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
<?  

         $inspecao->drawForm();

?>
<?

drawUploadFormItens($qtdFotos);

?>
<br/>
		<div class='row-fluid'>
			<div class='span4'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>

					<div class='tac controls controls-row'>

			            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'gravar');">Gravar</button>
                        <button class='btn' id="btn_parcial" type="button"  onclick="submitForm($(this).parents('form'),'parcial');">Gravar Parcial</button>
              			<input type='hidden' id="btn_click" name='btn_acao' value='' />
                        
					</div>
				</div>
			</div>
			<div class='span4'></div>
		</div>
   		<p><br/>

		</p><br/>
</form>

                <?drawUploadForm($qtdFotos);?>

<?php
include "rodape.php" 
?>
