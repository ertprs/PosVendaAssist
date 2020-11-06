<?
set_time_limit(0); 
include "funcoes.php";
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
class Upload
{
    var $arquivo = "";
    var $erro = array ( "0" => "upload execultado com sucesso!",
                        "1" => "O arquivo é maior que o permitido pelo Servidor",
                        "2" => "O arquivo é maior que o permitido pelo formulario",
                        "3" => "O upload do arquivo foi feito parcialmente",     
                        "4" => "Não foi feito o upload do arquivo"
                       );
        
    function Verifica_Upload(){ 
        $this->arquivo = isset($_FILES['arquivo']) ? $_FILES['arquivo'] : FALSE;
        if(!is_uploaded_file($this->arquivo['tmp_name'])) {
            return false;
        }    
        $get = getimagesize($this->arquivo['tmp_name']);
        
        if($get["mime"] != "image/jpeg")
        {    
            echo "<span style=\"color: white; border: solid 1px; background: red;\">Esse foto nao é uma imagem valida</span>";
            exit;
        }
        return true;
    }

    function Envia_Arquivo()
    {
        if($this->Verifica_Upload()) {
            $this->gera_fotos();
            echo "fez o up";
            return true;        
        } else {
            echo "nao enviou (".$nome_foto.")";
            #echo "<span style=\"color: white; border: solid 1px; background: red;\">".$this->erro[$this->arquivo['error']]."</span>";
        }
    }
    
    function gera_fotos()
    {
        $diretorio = " /www/assist/www/erp/imagens/fotos/";
         
        if(!file_exists($diretorio)){
            mkdir($diretorio);
        }
        
        $nome_original = $this->arquivo['tmp_name'];
        $nome_foto  = "imagem_$login_empresa-$login_loja-$produto-$nome_original.jpg";        
        $nome_thumb = "imagem_$login_empresa-$login_loja-$produto-$nome_original-thumb_.jpg";

        echo "gerou foto (".$nome_foto.")";

        $sql = "INSERT INTO tbl_peca_item_foto         
                (descricao, caminho,caminha_thumb, peca)
                VALUES ('$diretorio.$nome_foto','$diretorio.$nome_foto',$diretorio.$nome_thumb,10)";
        $res = pg_exec ($con,$sql);
        $msg_erro .= pg_errormessage($con);
                           
                //determino uma resolução maxima e se a imagem for maior ela sera reduzida
        reduz_imagem($this->arquivo['tmp_name'], 400, 300, $diretorio.$nome_foto);          
                //passo o tamanho da thumbnail
        reduz_imagem($this->arquivo['tmp_name'], 120, 90, $diretorio.$nome_thumb); 
        echo "<span style=\"color: white; border: solid 1px; background: blue;\">".$this->erro[$this->arquivo['error']]."</span>";
    }    
}

?>