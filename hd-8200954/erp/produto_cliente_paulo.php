<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'menu.php';


$pedido	= $_POST["pedido"];
if(strlen($pedido)==0) { $pedido= $_GET["pedido"]; }
$orcamento   =$_POST["orcamento"];
$orcamento   =$_GET["orcamento"];
$pedido_item =$_POST["pedido_item"];
$pedido_item =$_GET["pedido_item"];
$erro	=	"";
?> 

<style type="text/css">
.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
}
.titulo2{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #000000;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
}
.titulo {
	font-family: Arial;
	font-size: 10pt;
	color: #000000;
	background: #ced7e7;
}
</style>

<?

		if($_GET["acao"]== "excluir"){

		if(strlen($pedido_item)>0){
			$sql = " SELECT pedido_item
					 FROM tbl_pedido_item
					 JOIN tbl_pedido using(pedido)
					 WHERE pedido =$pedido 
					 AND pedido_item = $pedido_item;";
			$res= pg_exec($con, $sql);

			if(pg_numrows($res)>0){
				$sql= " DELETE FROM tbl_pedido_item
						WHERE pedido_item = $pedido_item";
				$res= pg_exec($con, $sql);
				if(pg_result_error($res)){
					echo "Erro ao excluir.";
					$res= pg_exec($con, " rollback;");
				}else{
					echo "<font color='blue'>Ok, excluido com sucesso.</font>";
					$sql="SELECT SUM(preco*qtde) as total
						FROM tbl_pedido_item
						WHERE pedido=$pedido";
					$res= pg_exec($con, $sql);
					$total=pg_result($res,0,total);
					$erro .= pg_errormessage($con);
				
					$sql ="UPDATE tbl_pedido set
							total=$total
							WHERE pedido=$pedido";
					$res= pg_exec($con, $sql);
					$erro .= pg_errormessage($con);
					$res= pg_exec($con, " begin;");
				}
			}else{
				$erro .="Peça não encontrada!";
			}
		}else{
			$erro .= "É necessário digitar o código do item e a quantidade!";
		}
	}
	if(strlen($pedido)>0){

		$titulo = "Produto(s) de Cliente";
		$sql= "	SELECT 
					tbl_pedido.pedido,
					tbl_pedido.cotacao_fornecedor,
					tbl_pedido.total,
					tbl_pedido_item.pedido_item,
					tbl_pessoa.nome,
					tbl_cotacao_fornecedor.pessoa_fornecedor as fornecedor,
					tbl_cotacao.cotacao
				FROM tbl_cotacao
				JOIN tbl_cotacao_fornecedor USING(cotacao)
				JOIN tbl_pessoa_fornecedor on tbl_pessoa_fornecedor.pessoa  = tbl_cotacao_fornecedor.pessoa_fornecedor
				JOIN tbl_pessoa ON tbl_pessoa_fornecedor.pessoa  = tbl_pessoa.pessoa
				JOIN tbl_pedido ON tbl_pedido.cotacao_fornecedor = tbl_cotacao_fornecedor.cotacao_fornecedor
				JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
				WHERE tbl_pedido.pedido = $pedido";

		$res= pg_exec($con, $sql);
		if(pg_numrows($res)>0){
			$nome				= trim(pg_result($res, 0, nome));
			$fornecedor			= trim(pg_result($res, 0, fornecedor));
			$pedido_item		= trim(pg_result($res, 0, pedido_item));
			$total_nota			= trim(pg_result($res, 0, total));
		} 
	}

?>

<table width='700' style='border-collapse: collapse'  bordercolor='#ccccff' class='table_line' border='1' cellspacing='1' cellpadding='0'>
<FORM NAME='form' ACTION="<? echo $PHP_SELF ?>" METHOD='POST'>
  <tr>
	<td nowrap colspan='7' align='right' >
       <?echo "<a href='orcamento_cadastro_paulo.php?orcamento=$orcamento'><font color='#0000ff'>Voltar Orçamento de Venda</font></a>";?>
	</td>
  </tr>
  <tr bgcolor='#596D9B' >
	<td nowrap colspan='7'  align='center' background='imagens/azul.gif'>
		<font color='#eeeeee' size='4'><?echo "$titulo : $nome";?></font>
	</td>
  </tr>
  <tr>
	<td nowrap height='5px' colspan='7' class='menu_top' align='center'> </td>
  </tr>


<?

	echo "<tr>";	
	echo "<td nowrap height='5px' colspan='7' class='menu_top' align='center'> </td>";	
	echo "</tr>";
	echo "</td>
	</tr>";

	echo "<tr>";	
	echo "<td nowrap height='5px' colspan='7' class='menu_top' align='center'> </td>";	
	echo "</tr>";

	echo "	<tr>
		<td colspan='7' align='left'>
		</td>
		</tr>";

	echo "
		<tr bgcolor='#fafafa'>
		  <td nowrap colspan='7' align='left' >
		<table width='100%' border='0' bordercolor='black' cellspacing='2' cellpadding='3'>
		<tr class='titulo'>
		  <td> &nbsp;</td>
		  <td> Codigo</td>
		  <td> Descrição</td>
		  <td align='center'> Qde. Ped.</td>
		  <td align='center'> Valor Unitário</td>
		  <td align='center'> Valor Total</td>
		  <td nowrap class='titulo' colspan='2' align='center'>Ação</td>
		</tr> ";
if (strlen($pedido) > 0) {
	$sql= "	SELECT 
				pedido_item,
				tbl_pedido.status_pedido,
				peca, 
				descricao, 
				qtde, 
				preco ,
				(qtde * preco) as SUB_TOTAL
			FROM tbl_pedido
			JOIN tbl_pedido_item USING(pedido)
			JOIN tbl_peca USING(peca)
			WHERE pedido = $pedido
			ORDER BY tbl_peca.descricao";


	$res= pg_exec($con, $sql);
	$c=1;
	$total=0;
	if(pg_numrows($res) > 0){
		for($i=0;$i<pg_numrows($res);$i++){
			$pedido_item	= trim(pg_result($res, $i, pedido_item));
			$peca			= trim(pg_result($res, $i, peca));
			$nome			= trim(pg_result($res, $i, descricao));
			$qtde			= trim(pg_result($res, $i, qtde));
			$preco			= trim(pg_result($res, $i, preco));
			$sub_total		= trim(pg_result($res, $i, sub_total));
			$status_pedido	= trim(pg_result($res, 0, status_pedido));


			$total			= ($total			+ $sub_total);

			$preco				= number_format(str_replace( ',', '', $preco), 2, ',','');
			$sub_total			= number_format(str_replace( ',', '', $sub_total), 2, ',','');
;

			if ($cor=="#fafafa")	$cor= "#eeeeff";
			else					$cor= "#fafafa";

			echo "<tr bgcolor='$cor' style='font-size: 10px'>";
			echo "<td> ".($i+1).
				 "<input type='hidden' name='peca_$i' value='$peca'>
				  <input type='hidden' name='qtde_$i' value='$qtde'>
			</td>";
			echo "<td> $peca</td>";
			echo "<td> $nome</td>";
			echo "<td align='center'>$qtde</td>";
			echo "<td align='right'>$preco</td>";
			echo "<td align='right'>$sub_total</td>";
			if ($status_pedido != 16) {
				echo "<td align=center><font color='#33CC00'>Excluir</font></td>";
			} else {
				echo "<td align=center><a href='produto_cliente_paulo.php?acao=excluir&pedido=$pedido&pedido_item=$pedido_item&orcamento=$orcamento'><font color='#ff0000'>Excluir</font></a></td>";
			}
			echo "</tr> ";
		}
		$tot			= $total;
		$total			= number_format(str_replace( ',', '', $total), 2, ',','');
	}
}else {
	echo "<font color=#FF0000 size=5>Nenhum produto encontrado</font>";
}
?>

	</table>
	</td>
  </tr>

  <tr>
    <td nowrap height='5px' colspan='7' class='menu_top' align='center'> </td>
  </tr>
  <tr>
    <td colspan='7' align='left'>
		<input type='hidden' name='cont_item' value='<?echo $i;?>'>
	</td>
  </tr>

  <tr class = 'titulo'>
	<td nowrap colspan='7' align='center' ><b>Valor Total de Produto(s)</b></td>
  </tr>

  <tr >
	<td nowrap colspan='7' align='right'><font size=3><?echo $total;?></font></td>
  </tr>
  <tr>
<?
	echo "<td colspan='5' class='menu_top' nowrap align='left'>Pedido nº <font color='#ffffff'><b>$pedido</b></font></td>";
?>
  </tr>

</form>
</table>
<?include "rodape.php"?>