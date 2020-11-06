<?PHP

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="call_center";
include "autentica_admin.php";

# Fábricas que tem permissão para esta tela
if(!in_array($login_fabrica, array(30))) {
	header("Location: menu_callcenter.php");
	exit;
}

$layout_menu = "callcenter";
$title = "ATUALIZAÇÃO DE PEDIDO";

include "cabecalho.php";
include "../js/js_css.php";
?>

<?
	// Se selecionou um arquivo
	if($_FILES["arquivo"]["size"]) {
		
		$arquivo = (object) $_FILES["arquivo"];
		
		// Separa as linhas em array
		$linhas = file($arquivo->tmp_name);

		// Tamanho máximo do upload de 1MB
		if($arquivo->size > 1048576) {
			$msg_erro[] = "Arquivo muito grande";
		}

		// Valida o tipo de arquivo
		if(substr($arquivo->name, -4) != ".txt") {
			$msg_erro[] = "Formato de arquivo inválido";
		}

		if(!count($msg_erro)) {

			$count_linha = 1;

			// Separa cada linha em coluna
			foreach($linhas as $colunas) {
				pg_query($con, "BEGIN TRANSACTION");			
				unset($msg_erro);
				// Separa o array em variáveis
				list($numero_pedido, $data_entrega, $status_entrega, $referencia_peca) = explode("\t", $colunas);
				
				$numero_pedido   = trim($numero_pedido);
				$data_entrega    = trim($data_entrega);
				$status_entrega  = trim($status_entrega);
				$referencia_peca = trim($referencia_peca);

				list($dia, $mes, $ano) = explode("/", $data_entrega);

				// Valida se é uma data válida
				if(!checkdate($mes, $dia, $ano)) {
					$msg_erro[] = "Linha $count_linha: Data inválida";
				}

				// Verifica se o pedido existe
				$sql = "SELECT pedido
						FROM tbl_pedido
						WHERE pedido = $numero_pedido";

				$res = pg_query($con, $sql);

				// Se não encontrou o pedido
				if(!pg_num_rows($res)) {
					$msg_erro[] = "Linha $count_linha: Pedido $numero_pedido não cadastrado no sistema";
				
				// Se o pedido existe
				} else {

					// Verifica se a peça existe
					$sql = "SELECT tbl_pedido_item.peca
							FROM tbl_pedido_item
							JOIN tbl_peca ON tbl_peca.peca    = tbl_pedido_item.peca
										 AND tbl_peca.fabrica = $login_fabrica
							WHERE tbl_pedido_item.pedido = $numero_pedido
							AND   tbl_peca.referencia    = '$referencia_peca'";

					$res = pg_query($con, $sql);

					// Se não encontrou o pedido
					if(!pg_num_rows($res)) {
						$msg_erro[] = "Linha $count_linha: Peça $referencia_peca não cadastrada para o pedido: $numero_pedido <br>";
					} else {
						$codigo_peca = pg_fetch_result($res, 0, 0);
					}
				}

				// Se já possui erro então não altera pedido
				if(!count($msg_erro)) {
					if ((strlen($numero_pedido) > 0) and (strlen($codigo_peca) > 0)){
						$obs_item = "'$data_entrega;$status_entrega'";
						$sql= "SELECT fn_atualiza_pedido($login_fabrica, $numero_pedido, $codigo_peca, $obs_item)";
	                                        $res = pg_exec($con,$sql);
						if(strlen(pg_last_error())) {
							#$msg_erro[] = pg_last_error($con);
                                                	$msg_erro[] = "Não foi possível alterar o item da linha: $count_linha";
	                                                $msg_erro[] = pg_last_error();
                                        	}
					}

					#$sql = "UPDATE tbl_pedido_item
					#		SET obs = '$data_entrega;$status_entrega'
					#		WHERE pedido = $numero_pedido
					#		AND   peca   = $codigo_peca";

					#$res = pg_query($con, $sql);

					#if(strlen(pg_last_error())) {
					#	$msg_erro[] = "Não foi possível alterar o item da linha: $count_linha";
					#	$msg_erro[] = pg_last_error();
					#}
				}

				$count_linha++;
				if(!count($msg_erro)) {
		                        pg_query($con, "COMMIT TRANSACTION");
		                } else {
					pg_query($con, "ROLLBACK TRANSACTION");
		                        $msg_erro_2 .= implode("<br/>", $msg_erro);
				}
			}
		}

		// Se não encontrou erros
		if(empty($msg_erro_2)) {
			$msg_sucesso = "Upload efetuado com sucesso!";
		} else {
			$msg_erro = $msg_erro_2;
		}
	}
?>

<link type="text/css" href="css/atualiza_pedido.css" rel="stylesheet"></link>

<div class="texto_avulso" style="width:700px; border-radius: 5px;">
	O arquivo deve conter as colunas separadas por "tab". 
	A primeira coluna deve conter o número do pedido, a segunda deve conter a data prevista de entrega, 
	a terceira deve conter o status de entrega e a última o código da peça.
	<br/><br/>
	<font style="color: red">O arquivo deve estar no formato ".txt"</font>
</div>

<br/>

<form method="post" enctype='multipart/form-data'>
	<table border="0" width='700px' align='center' cellspacing="0" cellpadding="3">
		<?
			if(trim($msg_erro) != "") {
		?>
			<tr>
				<td colspan='4' class='error'>
					<?=$msg_erro?>
				</td>
			</tr>
		<? } ?>
		<?
			if(trim($msg_sucesso) != "") {
		?>
			<tr>
				<td colspan='4' class='success'>
					<?=$msg_sucesso?>
				</td>
			</tr>
		<? } ?>
		<tr class="titulo_tabela">
			<td>
				Atualização de pedido
			</td>
		</tr>
		<tr class='table_line'>
			<td class="td_desc">
				Arquivo (máximo 1MB): <input type="file" name="arquivo" />
			</td>
		</tr>
		<tr class='table_line'>
			<td class="td_desc">
				<button>Enviar</button>
			</td>
		</tr>
	</table>
</form>

<? include "rodape.php"; ?>
