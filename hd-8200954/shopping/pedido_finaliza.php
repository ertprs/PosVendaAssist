<?
include_once "../dbconfig.php";
include_once "../includes/dbconnect-inc.php";

# -------------------------------------
// dados padronizados para abetura de pedido
$fabrica       = 10;
$posto         = 6359;
$tipo_pedido   = 77;
$status_pedido = 1;
# -------------------------------------

if (strlen($cookie_login['cook_pedido']) > 0) $cook_pedido = $cookie_login['cook_pedido'];
if (strlen($cookie_login['cep']) > 0)         $cep         = $cookie_login['cep'];
if (strlen($cookie_login['valor_cep']) > 0)   $valor_cep   = $cookie_login['valor_cep'];

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim($_POST["btn_acao"]);

if ($btn_acao == 'gravar'){
	if (strlen($_POST["cnpj"]) > 10){
		$cnpj  = trim($_POST['cnpj']);
		$xcnpj = str_replace (".","",$cnpj);
		$xcnpj = str_replace ("-","",$xcnpj);
		$xcnpj = str_replace ("/","",$xcnpj);
		$xcnpj = str_replace (" ","",$xcnpj);
		if (strlen($xcnpj) == 11 OR strlen($xcnpj) == 14){
			$sql = "SELECT tbl_posto_fabrica.posto 
					FROM   tbl_posto_fabrica 
					JOIN   tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto 
					WHERE  tbl_posto_fabrica.fabrica = $fabrica 
					AND    tbl_posto.cnpj            = '$xcnpj'";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) == 0){
				header("Location: pedido_dados.php");
				exit;
			}else{
				$posto = pg_result($res,0,0);
				
				$sql   = "SELECT sum(tbl_pedido_item.preco * tbl_pedido_item.qtde) AS total_compra, sum(tbl_peca.peso * tbl_pedido_item.qtde) AS total_peso FROM tbl_pedido_item join tbl_peca using(peca) WHERE tbl_pedido_item.pedido = $cook_pedido";
				$res   = pg_exec ($con,$sql);
				
				$total_pecas = pg_result ($res,0,total_compra);
				$total = $valor_cep + $total_pecas;
				
				$sql = "UPDATE tbl_pedido SET posto = $posto, total = $total WHERE fabrica = $fabrica and pedido = ".$cook_pedido;
				$res = pg_exec ($con,$sql);
				
				header("Location: pedido_confirmacao.php");
				exit;
			}
		}else{
			$msg_erro = "Digite o CNPJ/CPF corretamente.<br>CNPJ formato&nbsp;: 00.000.000/0000-00<br>ou CPF formato&nbsp;: 000.000.000-00&nbsp;&nbsp;";
		}
	}else{
		$msg_erro = "Digite o CNPJ/CPF corretamente.<br>CNPJ formato&nbsp;: 00.000.000/0000-00<br>ou CPF formato&nbsp;: 000.000.000-00&nbsp;&nbsp;";
	}
}

include"cabecalho.php";

// se deu erro
if (strlen($msg_erro) > 0){
	echo "<tr>\n";
	echo "<td bgcolor='#FF0066'>\n";
	echo "<center><font size='3' color='#ffffff'><b>\n";
	echo $msg_erro;
	echo "</b></font></center>\n";
	echo "</td>\n";
	echo "</tr>\n";
}

?>
		<tr>
			<td>
				<br><br>
				<form name='frm' method='post' action='<? echo $PHP_SELF ?>'>
				<input type="hidden" name="btn_acao" value="">
				<table border="0" cellspacing="2" cellpadding="3" width='80%' align='center'>
<?
if (strlen($cook_pedido) > 0 OR strlen($cookie_login['cook_pedido']) > 0){
	$sql = "SELECT  tbl_pedido_item.pedido_item,
					tbl_peca.peca              ,
					tbl_peca.referencia        ,
					tbl_peca.descricao         ,
					tbl_pedido_item.qtde       ,
					tbl_tabela_item.preco      
			FROM	tbl_pedido_item 
			JOIN	tbl_peca        ON tbl_peca.peca        = tbl_pedido_item.peca
			JOIN	tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca 
			WHERE	tbl_peca.fabrica       = $fabrica
			AND		tbl_pedido_item.pedido = $cook_pedido";
	$res = pg_exec($con,$sql);
	$linhas = pg_numrows($res);

	echo "<tr bgcolor='#FFCC99'>\n";
	echo "<td colspan='2'><B>DIGITE OS DADOS (para confirmação de cadastro)</B></td>\n";
	echo "</tr>\n";
	
	echo "<tr bgcolor='#fdfdfd'>\n";
	echo "<td align='left'><B>CNPJ/CPF</B></td>\n";
	echo "<td align='left'><input type=\"text\" name=\"cnpj\" value='$cnpj' maxlength='19'></td>\n";
	echo "</tr>\n";
	echo "<tr bgcolor='#fdfdfd'>\n";
	echo "<td colspan='2' align=\"center\"><input type='button' name='gravar' value='Confirmar >>' onclick=\"javascript: document.forms[0].btn_acao.value='gravar'; document.forms[0].submit();\"></td>\n";
	echo "</tr>\n";
}else{
	echo "<tr>\n";
	echo "<td align='center'>SEU PEDIDO AINDA ESTÁ SEM PRODUTOS!!!</td>\n";
	echo "</tr>\n";
}

?>
				</table>
				</form>
			</td>
		</tr>

<?
include"rodape.php";
?>
