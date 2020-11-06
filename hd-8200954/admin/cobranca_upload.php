<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

	$layout_menu = "financeiro";
	$title = "COBRANÇA";
	include 'cabecalho.php';
?>
<style type="text/css">
	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
</style>
<?
// Pasta onde o arquivo vai ser salvo
$_UP['pasta'] = 'cobranca_upload/';

// Tamanho máximo do arquivo (em Kb)
$_UP['tamanho'] = 2048; // 2Mb

// Array com as extensões permitidas
$_UP['extensoes'] = array('txt');

// Renomeia o arquivo? (Se true, o arquivo será salvo como .jpg e um nome único)
$_UP['renomeia'] = true;

// Array com os tipos de erros de upload do PHP
$_UP['erros'][0] = 'Não houve erro';
$_UP['erros'][1] = 'O arquivo no upload é maior do que o limite, 2Mb';
$_UP['erros'][2] = 'O arquivo ultrapassa o limite de tamanho especifiado no HTML';
$_UP['erros'][3] = 'O upload do arquivo foi feito parcialmente';
$_UP['erros'][4] = 'Não foi feito o upload do arquivo';

// Verifica se houve algum erro com o upload. Se sim, exibe a mensagem do erro
if ($_FILES['arquivo']['error'] != 0) {
die("<br><br>&nbsp;&nbsp;&nbsp;Não foi possível fazer o upload, erro:<br />" . $_UP['erros'][$_FILES['arquivo']['error']]."<br><br>&nbsp;&nbsp;&nbsp;");
exit; // Para a execução do script
}

// Caso script chegue a esse ponto, não houve erro com o upload e o PHP pode continuar

// Faz a verificação da extensão do arquivo
$extensao = strtolower(end(explode('.', $_FILES['arquivo']['name'])));
if (array_search($extensao, $_UP['extensoes']) === false) {
echo "<br><br><center><div class='msg_erro' style='width:700px'>Somente podem ser enviados arquivos com a extensão TXT</div></center><br><br>&nbsp;&nbsp;&nbsp;";
echo "<meta http-equiv='Refresh' content='1; url=cobranca_envia_arquivo.php'> ";
}

// Faz a verificação do tamanho do arquivo
else if ($_UP['tamanho'] > $_FILES['arquivo']['size']) {
echo "<br><br>&nbsp;&nbsp;&nbsp;O arquivo enviado é muito grande, envie arquivos de até 3Mb.<br><br>&nbsp;&nbsp;&nbsp;";
}

// O arquivo passou em todas as verificações, hora de tentar movê-lo para a pasta
else {
// Primeiro verifica se deve trocar o nome do arquivo
if ($_UP['renomeia'] == true) {
// Cria um nome baseado no UNIX TIMESTAMP atual e com extensão .txt
$nome_final = 'teste.txt';
} else {
// Mantém o nome original do arquivo
$nome_final = $_FILES['arquivo']['name'];
}

// Depois verifica se é possível mover o arquivo para a pasta escolhida
if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $_UP['pasta'] . $nome_final)) {
// Upload efetuado com sucesso, exibe uma mensagem e um link para o arquivo
echo "<br><br>&nbsp;&nbsp;&nbsp;Upload efetuado com sucesso!<br>";
echo "&nbsp;&nbsp;&nbsp;<a href='cobranca_inclui_txt.php'>Clique aqui para incluir o aquivo no banco de dados</a><br><br>";
} else {
// Não foi possível fazer o upload, provavelmente a pasta está incorreta
echo "<br><br>&nbsp;&nbsp;&nbsp;Não foi possível enviar o arquivo, tente novamente<br><br>";
}

}

include 'rodape.php';
?>
