<?php 
  include "dbconfig.php";
  include "includes/dbconnect-inc.php";
  include 'autentica_usuario.php';
  include_once 'anexaNF_inc.php';

if (isset($_POST['btn_enviar']) && isset($_FILES['foto_termo']) != '') {
    $os = $_POST['os']; 

    $dados_anexo_termo['name']      = $_FILES['foto_termo']['name'];
    $dados_anexo_termo['type']      = $_FILES['foto_termo']['type'];
    $dados_anexo_termo['tmp_name']  = $_FILES['foto_termo']['tmp_name'];
    $dados_anexo_termo['error']     = $_FILES['foto_termo']['error'];
    $dados_anexo_termo['size']      = $_FILES['foto_termo']['size'];
    $dados_anexo_termo['termo_devolucao']     = 'ok';
   
    $anexou_termo = anexaNF($os, $dados_anexo_termo);

    if (strlen($anexou_termo) == 0 or $anexou_termo == 0) {
      echo "<script type='text/javascript'> window.parent.location.reload(); </script>";
    } else {
      echo "<script type='text/javascript'> alert('$anexou_termo'); window.parent.location.reload(); </script>";
      
    }
}

?>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  
  <form name='frm_anexo' action='<? echo $PHP_SELF; ?>' method='post' enctype="multipart/form-data">
    <div class="row-fluid">
      <div class="span12 tac" >
        <h4 style="background-color:#596D9B; color:#fff; font-weight: bold; text-align: center;"> Anexar Termo de Retirada </h4>
      </div>
      <br />
    </div>
    <div class="row-fluid"  style="margin: auto; width: 80%; padding: 10px;">
      <br />
        <input type="file" name="foto_termo"/>
        <input name='os' type='hidden' value='<?=$_GET['os']?>' />
    </div>
    <hr>
    <div class="row-fluid"  style="margin: auto; width: 20%; padding: 10px;">
      <div class="span6 bt" style="text-align: right;">
        <input class="btn btn-primary" type="submit" name="btn_enviar" style="text-align: center;" value="Enviar">   
      </div>
      <div class="span6 carregando" style="text-align: right; display: none;">
        <img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='arial'>Aguarde..</font>   
      </div>
    </div>
  </form>
<script type="text/javascript">
  $(".bt").click(function() {
    $(".bt").hide();
    $(".carregando").show();
  });
</script>
