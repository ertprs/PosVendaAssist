<link href="../admin/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
<?php       

    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    include 'autentica_admin.php';
    include "menu.php";

	// Lendo arquivos de Log	
	$datas = Date('Y-m-d');
	$diretorio = $_POST["fabricas"];
	$parte = $_POST["parte"];
    echo '<div class="container">';
    if ($parte == $diretorio."/gera-pedido") 
        $nomefabrica = "/tmp/".$parte."-os-".$datas.".log";
    else
        $nomefabrica = "/tmp/".$parte."-".$datas.".log";					
	if(file_exists($nomefabrica)) {
		$ponteiro = fopen($nomefabrica,"r");
        echo '<br><center><div class="alert alert-success">Conteudo do arquivo <b>'.$nomefabrica.'</b></div><br><pre>';
		while (!feof($ponteiro)) {
			$linha = fgets($ponteiro, 4096);            
			echo '<center>'.$linha.'</center>';
		}			
		fclose($ponteiro);
        echo '</pre></center>';
	}
	else { echo '<br><center><div class="alert alert-error"><br><h4>Arquivo '.$nomefabrica.'<br><br>NAO EXISTE</h4><br></div></center>'; }	
    echo '</br><center><a class="btn btn-small" href="monitoracron.php"><i class="icon-arrow-left"></i>&nbsp;Voltar</a></center>';
    
    echo '</div>';
    
    include 'rodape.php';
?>