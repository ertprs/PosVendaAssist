<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'token_cookie.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

$cook_posto  = $cookie_login['cook_posto'];
if (strlen($cookie_login['cook_pedido_lu']) > 0) $cook_pedido_lu = $cookie_login['cook_pedido_lu'];

# -------------------------------------
// dados padronizados para abetura de pedido
$fabrica       = 10;
$posto         = 6359;
$tipo_pedido   = 77;
$status_pedido = 1;
# -------------------------------------

if (strlen($cookie_login['cook_pedido_lu']) > 0) $cook_pedido_lu = $cookie_login['cook_pedido_lu'];
if (strlen($cookie_login['cep']) > 0)            $cep            = $cookie_login['cep'];

if (strlen($cook_pedido_lu) > 0 OR strlen($cookie_login['cook_pedido_lu']) > 0){
	// DADOS DO POSTO
	$sql = "SELECT  tbl_pedido.pedido,
					to_char(tbl_pedido.data, 'DD/MM/YYYY') as data,
					tbl_posto.posto,
					tbl_posto.cnpj ,
					tbl_posto.nome ,
					tbl_posto.endereco,
					tbl_posto.cep,
					tbl_posto.bairro,
					tbl_posto.cidade,
					tbl_posto.estado
			FROM	tbl_pedido
			JOIN	tbl_posto         ON tbl_posto.posto = tbl_pedido.posto
			WHERE    tbl_peca.fabrica = $fabrica
			and     tbl_pedido.pedido = $cook_pedido_lu";
	$resPosto = pg_exec($con,$sql);
	
	if (pg_numrows($resPosto) > 0){
		$pedido   = pg_result($resPosto,0,pedido);
		$data     = pg_result($resPosto,0,data);
		$posto    = pg_result($resPosto,0,posto);
		$cnpj     = pg_result($resPosto,0,cnpj);
		$nome     = pg_result($resPosto,0,nome);
		$endereco = pg_result($resPosto,0,endereco);
		$cep      = pg_result($resPosto,0,cep);
		$bairro   = pg_result($resPosto,0,bairro);
		$cidade   = pg_result($resPosto,0,cidade);
		$estado   = pg_result($resPosto,0,estado);
	}
}

setcookie ("cook_pedido_lu","");
setcookie ("cep","");
setcookie ("valor_cep","");

$layout_menu = 'pedido';
$title="Detalhes do produto!";
include "login_unico_cabecalho.php";


?>

				<table border="0" cellspacing="2" cellpadding="3" width='80%' align='center'>
					<tr>
						<td>Pedido:</td>
						<td><B><? echo $pedido; ?></B></td>
						<td>Data:</td>
						<td><B><? echo $data; ?></B></td>
					</tr>
					<tr>
						<td>Cliente:</td>
						<td colspan='3'><B><? echo $cnpj . " - " .$nome; ?></B></td>
					</tr>
					<tr>
						<td>Endereço:</td>
						<td><B><? echo $endereco; ?></B></td>
						<td>CEP:</td>
						<td><B><? echo $cep; ?></B></td>
					</tr>
					<tr>
						<td>Bairro:</td>
						<td><B><? echo $bairro; ?></B></td>
						<td>Cidade/Estado:</td>
						<td><B><? echo $cidade. " / " .$estado; ?></B></td>
					</tr>
				</table>

				<table border="0" cellspacing="2" cellpadding="3" width='80%' align='center'>
<?
if (strlen($pedido) > 0){
	// DADOS DO PEDIDO
	$sql = "SELECT  tbl_pedido_item.pedido_item,
					tbl_peca.peca              ,
					tbl_peca.referencia        ,
					tbl_peca.descricao         ,
					tbl_pedido_item.qtde       ,
					tbl_tabela_item.preco
			FROM    tbl_pedido_item 
			JOIN    tbl_peca        ON tbl_peca.peca        = tbl_pedido_item.peca
			JOIN    tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca 
			WHERE   tbl_peca.fabrica       = $fabrica
			AND     tbl_pedido_item.pedido = $pedido";
	$res = pg_exec($con,$sql);
	$linhas = pg_numrows($res);

	if ($linhas > 0){
		echo "<tr bgcolor='#FFCC99'>\n";
		echo "<td colspan='4'><B>Este é a confirmação dos dados de seu pedido</B></td>\n";
		echo "</tr>\n";

		echo "<tr bgcolor='#FFCC99'>\n";
		echo "<td><B>PRODUTO</B></td>\n";
		echo "<td width='50' align='center'><B>QTDE</B></td>\n";
		echo "<td width='70' align='center'><B>R$ UNIT</B></td>\n";
		echo "<td width='70' align='center'><B>R$ TOTAL</B></td>\n";
		echo "</tr>\n";
		
		for ($i=0; $i<$linhas; $i++){
			$pedido_item = pg_result($res,$i,pedido_item);
			$peca        = pg_result($res,$i,peca);
			$referencia  = pg_result($res,$i,referencia);
			$descricao   = pg_result($res,$i,descricao);
			$qtde        = pg_result($res,$i,qtde);
			$preco       = pg_result($res,$i,preco);
			
			$Xpreco      = number_format($preco,2,',','.');
			
			$total       = $preco * $qtde;
			$Xtotal      = number_format($total,2,',','.');
			
			$total_pedido += $total;
			
			echo "<tr bgcolor='#fdfdfd'>\n";
			echo "<td align='left'>&middot; $referencia - $descricao<input type='hidden' name='pedido_item_$i' value='$pedido_item'></td>\n";
			echo "<td align='center'>$qtde</td>\n";
			echo "<td align='right'>R$ $Xpreco</td>\n";
			echo "<td align='right'>R$ $Xtotal</td>\n";
			echo "</tr>\n";
		}

		// TOTALIZACAO
		$total_pedido += $valor_cep;
		$Xtotal_pedido = number_format($total_pedido,2,',','.');
		echo "<tr bgcolor='#ffcc99'>\n";
		echo "<td colspan='3' align='right'><B>TOTAL DO PEDIDO</B></td>\n";
		echo "<td align='right'><B>R$ $Xtotal_pedido</B></td>\n";
		echo "</tr>\n";
/*
		// BOT?ES
		echo "<tr>\n";
		echo "<td colspan='3' align=\"center\"><input type='button' name='continuar' value='Fechar >>' onclick=\"javascript: document.forms[0].btn_acao.value='continuar'; document.forms[0].submit();\"></td>\n";
		echo "</tr>\n";
*/
	}else{
		echo "<tr>\n";
		echo "<td align='center'>SEU PEDIDO AINDA ESTÁ SEM PRODUTOS!!!</td>\n";
		echo "</tr>\n";
	}
}
?>
				</table>


<?
include "login_unico_rodape.php";
?>