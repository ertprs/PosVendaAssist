<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if(isset($_POST['upload'])){
    $arquivo = $_FILES['tabela_mo'];
    if($arquivo['size'] > 0 ){
        if(!preg_match("/.txt/", $arquivo['name'])){

            $msg_erro["msg"][] = "Arquivo Inválido";

        }else if ($arquivo['size'] > 2000000){
            
            $msg_erro["msg"][] = "Arquivo com Tamanho Superior a 2 Mb";

        }else{

            system ("mkdir /tmp/blackedecker/ 2> /dev/null ; chmod 777 /tmp/blackedecker/" );

            $origem = $arquivo['tmp_name'];
            $destino = "/tmp/blackedecker/".date("dmYHis").$arquivo['name'];
            
            if(move_uploaded_file($origem, $destino)){
                ini_set("auto_detect_line_endings",true);
                $ponteiro = fopen($destino,"r");
                while(!feof($ponteiro)){
                    $linha = fgets($ponteiro,4096);
                    if($linha <> "\n" and strlen(trim($linha))>0){
                        list($referencia,$descricao,$voltagem,$preco) = explode(";", $linha);
			$referencia=trim($referencia);
			$voltagem=trim($voltagem);

                        $sql = "SELECT  tbl_produto.produto
                                FROM    tbl_produto
                                WHERE   trim(tbl_produto.referencia)  = trim('$referencia')
                                AND     trim(tbl_produto.voltagem)    = trim('$voltagem')
                                AND     tbl_produto.fabrica_i   = $login_fabrica
                                AND     tbl_produto.ativo       IS TRUE
                        ";
                        $res = pg_query($con,$sql);
                        if(pg_num_rows($res) > 0){
                            pg_query($con,"BEGIN TRANSACTION");
                            $produto = pg_fetch_result($res,0,produto);
                            $valor_mo = str_replace(",",".",$preco);
                            
                            $sql = "UPDATE  tbl_produto
                                    SET     mao_de_obra = $valor_mo
                                    WHERE   produto     = $produto
                            ";
                            $res = pg_query($con,$sql);
                            if(!pg_last_error()){
                                pg_query($con,"COMMIT TRANSACTION");
                            }else{
                                pg_query($con,"ROLLBACK TRANSACTION");
                                $msg_erro["msg"][] = "Não foi possível realizar a gravação";
                            }
                        }else{
                            $msg_erro["msg"][] = "O produto $referencia -  $descricao - $voltagem não existe no BD, está inativo ou o arquivo está formatado de forma errada";
                        }
                    }
                }
                if(count($msg_erro['msg']) == 0){
                    $msg_success = "Upload Realizado com Sucesso";
                }
                
            }else{
                $msg_erro["msg"][] = "Erro ao Realizar Upload do Arquivo txt";
            }

        }
    }else{
        $msg_erro["msg"][]    = "Por favor selecione o Arquivo txt";
        $msg_erro["campos"][] = "tabela_mo";
    }
}

$layout_menu = "cadastro";
$title = "UPLOAD DA TABELA DE Mão-de-obra";
include 'cabecalho_new.php';

if(count($msg_erro["msg"]) > 0) {
?>
<div class="alert alert-error">
    <h4><?php echo implode("<br>", $msg_erro["msg"]);?></h4>
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
?>

<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data" align='center' class='form-search form-inline tc_formulario'>
    <input type="hidden" name="upload" value="acao" />
    <div class='titulo_tabela '>Parâmetros para Upload</div>

    <br />

    <div class="row-fluid">

            <div class="span1"></div>

            <div class="span10">

                <div class="alert" style="text-align: left !important;">
                    <p>
                        O arquivo selecionado deve estar no seginte formato:
                        <ul>
                            <li>txt e sem cabeçalho</li>
                            <li>Vir com os campos:
                                <ul>
                                    <li>Referência</li>
                                    <li>Descrição</li>
                                    <li>Voltagem</li>
                                    <li>Valor de mo(virgula(,) como separador de decimais)</li>
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
            <div class='control-group <?=(in_array("tabela_acessorios", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='peca_referencia'>Arquivo txt</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="file" id="tabela_mo" name="tabela_mo" class='span12' />
                    </div>
                </div>
            </div>
        </div>

        <div class="span2"></div>

    </div>

    <p>
        <br/>
        <button class='btn btn-info' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Realizar Upload</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p>

    <br/>
</form>
<? include "rodape.php"; ?>
