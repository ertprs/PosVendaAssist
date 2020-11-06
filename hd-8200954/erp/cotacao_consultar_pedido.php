<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

include 'menu.php';
//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'compra') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}

?>

<style type="text/css">
.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	border: 0px;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}

.titulo {
	font-family: Arial;
	font-size: 10pt;
	color: #000000;
	background: #ced7e7;
}
</style>

<table class='table_line' width='700' border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>
<FORM ACTION='#' METHOD='GET'>
  <tr bgcolor='#596D9B'>
	<td nowrap class='menu_top' colspan='2' align='center' background='imagens/azul.gif'>
		<font size='3'>Pedido</font>
	</td>
  </tr>

  <tr>
    <td colspan='2'>

  <table width='100%' align='left'>
	<tr>	
	  <td nowrap class='menu_top' colspan='8' align='left'><font color='#0000ff' size= '1px'>Selecione o Pedido</font></td>
	</tr>
	<tr bgcolor='#596D9B'>	

	  <td nowrap class='menu_top' width='20%' align='center'>Nº Pedido Forn</td>
	  <td nowrap class='menu_top' width='20%' align='center'>Cotação</td>
  	  <td nowrap class='menu_top' width='20%' align='center'>Fornecedor</td>
	  <td nowrap class='menu_top' width='30%' align='center'>Data do Pedido</td>
  	  <td nowrap class='menu_top' width='30%' align='center'>Data de Entrega</td>
	  <td nowrap class='menu_top' width='20%' align='center'>Situação</td>
	</tr>

<?
	$codcotacao= $_GET["cotacao"];
	$fornecedor= $_GET["fornecedor"];
	$pg= "recebimento_confirma.php?pedido=";

	if ((strlen($_GET['pesquisar'])>0)and (strlen($fornecedor)>0)){
		$whr='';
		if(strlen($fornecedor)>0){
			$sql= "	SELECT 
						tbl_pedido.pedido,
						tbl_pedido.pedido_cliente,
						to_char(tbl_pedido.data, 'DD/MM/YY') as data,
						tbl_pedido.entrega,
						tbl_pedido.status_pedido,
						tbl_cotacao.cotacao, 
						tbl_cotacao.requisicao_lista, 
						tbl_pessoa.nome,	
						tbl_pessoa.pessoa as fornecedor,
						tbl_status_pedido.descricao,
					FROM tbl_pedido
					JOIN tbl_cotacao_fornecedor ON tbl_pedido.cotacao_fornecedor			= tbl_cotacao_fornecedor.cotacao_fornecedor
					JOIN tbl_pessoa_fornecedor  ON tbl_cotacao_fornecedor.pessoa_fornecedor	= tbl_pessoa_fornecedor.pessoa	 
					JOIN tbl_pessoa			    ON tbl_pessoa_fornecedor.pessoa				= tbl_pessoa.pessoa
					JOIN tbl_cotacao			ON tbl_cotacao_fornecedor.cotacao			= tbl_cotacao.cotacao			 
					JOIN tbl_status_pedido		ON tbl_pedido.status_pedido					= tbl_status_pedido.status_pedido
					WHERE  tbl_pessoa.nome LIKE UPPER('%$fornecedor%') 
							OR tbl_pessoa_fornecedor.pessoa like '%$fornecedor%'
					ORDER BY tbl_pedido.estatus_pedido desc, tbl_pessoa_fornecedor.pessoa, pedido desc";
			echo "sql:". $sql;
		}
	}else{
			$sql= "	SELECT 
						tbl_pedido.pedido,
						tbl_pedido.pedido_cliente,
						to_char(tbl_pedido.data, 'DD/MM/YY') as data,
						tbl_pedido.entrega,
						tbl_pedido.status_pedido,
						tbl_cotacao.cotacao, 
						tbl_cotacao.requisicao_lista, 
						tbl_pessoa.nome,	
						tbl_pessoa.pessoa as fornecedor,
						tbl_status_pedido.status_pedido,
						tbl_status_pedido.descricao
					FROM tbl_pedido
					JOIN tbl_cotacao_fornecedor ON tbl_pedido.cotacao_fornecedor			= tbl_cotacao_fornecedor.cotacao_fornecedor
					JOIN tbl_pessoa_fornecedor  ON tbl_cotacao_fornecedor.pessoa_fornecedor	= tbl_pessoa_fornecedor.pessoa	 
					JOIN tbl_pessoa			    ON tbl_pessoa_fornecedor.pessoa				= tbl_pessoa.pessoa
					JOIN tbl_cotacao			ON tbl_cotacao_fornecedor.cotacao			= tbl_cotacao.cotacao			 
					JOIN tbl_status_pedido		ON tbl_pedido.status_pedido					= tbl_status_pedido.status_pedido
				WHERE tbl_cotacao.empresa = $login_empresa and tbl_pedido.status_pedido = 16
				ORDER BY tbl_pedido.status_pedido desc,  pedido desc ";
	}
	//echo nl2br($sql);

	$res= pg_exec($con, $sql);
	if(pg_numrows($res)==0){
		echo "
			<tr >	
			  <td nowrap class='menu_top' colspan='6' align='center'>
					<font color='#0000ff'>Nenhum Pedido!</font>
			  </td>
			</tr>";
	}else{
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$cotacao			=trim(pg_result($res,$i,cotacao));
			$requisicao_lista	=trim(pg_result($res,$i,requisicao_lista));
			$pedido				=trim(pg_result($res,$i,pedido));
			$pedido_cliente		=trim(pg_result($res,$i,pedido_cliente));
			$data				=trim(pg_result($res,$i,data));
			$data_entrega		=trim(pg_result($res,$i,entrega));
			$descricao			=trim(pg_result($res,$i,descricao));
			$status_pedido		=trim(pg_result($res,$i,status_pedido));
			$nome				=trim(pg_result($res,$i,nome));
			$fornecedor			=trim(pg_result($res,$i,fornecedor));

			if ($cor=="#fafafa")	$cor= "#eeeeff";
			else					$cor= "#fafafa";
			
			echo "<tr bgcolor='$cor' class='table_line' style='cursor: pointer;' onClick= \"location.href='$pg$pedido'\">"; 
//			echo "<td nowrap align='center'><font color='#0000ff'>$pedido</font></td>";
			echo "<td nowrap align='center'><font color='#0000ff'>$pedido_cliente</font></td>";
			echo "<td nowrap align='center'><font color='#0000ff'>$requisicao_lista</font></td>";
			echo "<td nowrap align='left'>$nome</td>";
			echo "<td nowrap align='center' >$data</td>";
			echo "<td nowrap align='center' >$entrega</td>";
			echo "<td nowrap align='center' >";
			
			if ($status_pedido== "16"){ 
				echo "<font color='#0000ff'>$descricao</font>";
			}else{
				echo $descricao;
			}
			echo "</td>";
			echo "</tr>";
			$c++;
		}
	}
?>  
  </table>
  </td>
  </tr>
 </FORM>
</table>

</body>
</html>
