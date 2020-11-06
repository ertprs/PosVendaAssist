<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "cadastros";

include "autentica_admin.php";
include 'funcoes.php';

if($_FILES){
	$caminho = "/tmp/nks";
	$arquivo = $_FILES['arquivo'];

	list($nome,$ext) = explode('.',$arquivo['name']);
	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
		// Tamanho máximo do arquivo (em bytes) 
		$config["tamanho"] = 2048000;

		// Verifica o mime-type do arquivo
		if ($ext != 'txt') {
			$msg_erro = "Arquivo em formato inválido!";
		} else {
			// Verifica tamanho do arquivo 
			if ($arquivo["size"] > $config["tamanho"]) 
				$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";
		}

		if (strlen($msg_erro) == 0) {

			// Exclui anteriores, qquer extensao
			exec("mv -f $caminho/representante_comercial.txt $caminho/nao_bkp/repre_comercial".date('Y-m-d').".txt");
			
			// Faz o upload
			if (strlen($msg_erro) == 0) {
				if (!copy($arquivo["tmp_name"], $caminho."/representante_comercial.txt")) {
					$msg_erro = "Arquivo '".$arquivo['name']."' não foi enviado!!!";
				}
				
				$file = fopen($caminho."/representante_comercial.txt","r+");

				$dados = fread($file, filesize($caminho."/representante_comercial.txt"));

				$linhas = explode("\n",$dados);
				$i = 1;
				foreach ($linhas as $linha) {
					list($cnpj,$ger_codigo,$ger_nome,$rep_codigo,$rep_nome) = explode(';',$linha);

					$cnpj = str_replace('-', '', $cnpj);
					$cnpj = str_replace('/', '', $cnpj);
					$cnpj = str_replace('.', '', $cnpj);
					$cnpj = trim($cnpj);

					$ger_nome = strtoupper($ger_nome);
					$rep_nome = strtoupper($rep_nome);
					$ger_nome = trim($ger_nome);
					$rep_nome = trim($rep_nome);

					$ger_codigo = trim($ger_codigo);
					$rep_codigo = trim($rep_codigo);
					
					$sql = "SELECT revenda FROM tbl_revenda WHERE cnpj = '$cnpj'";
					$res = pg_query($con,$sql);

					if(pg_num_rows($res) > 0){

						$revenda = pg_fetch_result($res, 0, 'revenda');

						$sql = "SELECT revenda_comercial FROM tbl_revenda_comercial WHERE fabrica = $login_fabrica AND codigo_gerente = $ger_codigo AND codigo_representante = $rep_codigo AND revenda = $revenda";
						$res = pg_query($con,$sql);

						if(pg_num_rows($res) > 0){

							$revenda_comercial = pg_fetch_result($res, 0, 'revenda_comercial');
							$sql = "UPDATE tbl_revenda_comercial SET 
											revenda = $revenda,
											codigo_gerente = $ger_codigo,
											nome_gerente = '$ger_nome',
											codigo_representante = $rep_codigo,
											nome_representante = '$rep_nome'
										WHERE revenda_comercial = $revenda_comercial";
							$res = pg_query($con,$sql);
						}else{

							$sql = "INSERT INTO tbl_revenda_comercial(
																		fabrica,
																		revenda,
																		codigo_gerente,
																		nome_gerente,
																		codigo_representante,
																		nome_representante) 
																	VALUES(
																		$login_fabrica,
																		$revenda,
																		$ger_codigo,
																		'$ger_nome',
																		$rep_codigo,
																		'$rep_nome')";
							$res = pg_query($con,$sql);
						}
					}
					if(pg_last_error($con)){
						$msg_erro = "Erro ao importar linha: $i <br>";
					}

					$i++;
				}			
			}			
		}
	}
}

$layout_menu = "cadastro";
$title = "Upload de Revendas e Representantes";
include "cabecalho.php";

?>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}


.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>

<?php
	if(!empty($msg_erro)){
		echo "<table align='center' width='700'>
				<tr class='msg_erro'>
					<td> $msg_erro </td>
				</tr>
			  </table>";
	}

	if(empty($msg_erro) AND $_FILES){
		echo "<table align='center' width='700'>
				<tr class='sucesso'>
					<td> Arquivo importado com sucesso! </td>
				</tr>
			  </table>";
	}
?>

<form method='post' action="<?=$PHP_SELF?>" enctype="multipart/form-data">
	<table align='center' width='700' class='formulario'>
	<caption class='titulo_tabela'>Upload do arquivo</caption>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td align='center'>
			Arquivo : <input type='file' name='arquivo'>
			<input type='submit' value='Enviar'>
			<br>
			<span style='font-size:10px;'>Fazer UPLOAD de arquivos no formato TXT</span>
		</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
</form>

<?php
	include "rodape.php";
?>
