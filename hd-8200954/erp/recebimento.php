<?
include 'index.php';
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
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
<table class ='table_line' width="700px" border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>

<FORM ACTION='#' METHOD='GET'>
  <tr>
	<td nowrap colspan='3' class='menu_top' align='left' background='imagens/azul.gif'>
		<font size='3'>Recebimento de Produto</font>
	</td>
  </tr>
  <tr >
	<td nowrap colspan='3' align='left' class='titulo'><b>Busca </b></td>
  </tr>
  <tr>
    <td colspan='3'>

  <table width='100%' align='left' class='table_line'>
	<tr>	
		<td nowrap class='menu_top' colspan='3' align='left'>
  		  <font color='#0000ff' size= '1px'>Selecione o Pedido</font>
		</td>
		<td nowrap align='right' colspan='4' >
			Nº Pedido
			<input type='text' disabled name='cotacao' value='inativo' size='10' maxlength=10>
			&nbsp; 
			Fornecedor
			<input type='text' name='fornecedor' value='<?echo $_GET["fornecedor"];?>' size='10' maxlength=10>
			&nbsp; 
		  <input type='submit' name='pesquisar' value='Pesquisar'>
		</td>
	</tr>
	<tr class='titulo'>	

	  <td nowrap width='20%' align='center'>Nº Pedido</td>
  	  <td nowrap width='20%' align='center'>Nota Fiscal</td>
	  <td nowrap width='20%' align='center'>Fornecedor</td>
	  <td nowrap width='30%' align='center'>Data do Pedido</td>
  	  <td nowrap width='30%' align='center'>Data de Entrega</td>
	  <td nowrap width='20%' align='center'>Situação</td>
	</tr>

<?
	$codcotacao= $_GET["cotacao"];
	$fornecedor= $_GET["fornecedor"];
	//$cotar= $_GET["cotar"];
	$pg= "recebimento_confirma.php?pedido=";
//	$conexao = new bdtc();
	if ((strlen($_GET['pesquisar'])>0)and (strlen($fornecedor)>0)){

		$whr='';
		if(strlen($fornecedor)>0){
			$sql= "	SELECT 
					tbl_pedido.pedido,
					to_char(tbl_pedido.data,'DD/MM/YYYY') as data,
					tbl_pedido.entrega,
					tbl_pedido.status_pedido,
					tbl_pedido.pedido_cliente,
					tbl_cotacao.cotacao, 
					tbl_cotacao.requisicao_lista, 
					tbl_pessoa.nome,	
					tbl_cotacao_fornecedor.pessoa_fornecedor as fornecedor,
					tbl_status_pedido.status_pedido,
					tbl_status_pedido.descricao,
					tbl_faturamento.nota_fiscal
				FROM tbl_pedido
				LEFT JOIN tbl_faturamento on tbl_faturamento.pedido = tbl_pedido.pedido
				JOIN tbl_cotacao_fornecedor using(cotacao_fornecedor)
				JOIN tbl_pessoa_fornecedor ON tbl_pessoa_fornecedor.pessoa = tbl_cotacao_fornecedor.pessoa_fornecedor
				JOIN tbl_pessoa			   ON tbl_pessoa.pessoa			   = tbl_pessoa_fornecedor.pessoa 
				JOIN tbl_cotacao		   ON tbl_cotacao.cotacao		   = tbl_cotacao_fornecedor.cotacao
				JOIN tbl_status_pedido	   ON tbl_pedido.status_pedido	   = tbl_status_pedido.status_pedido 
				WHERE tbl_cotacao.empresa = $login_empresa 			
						AND (UPPER(tbl_pessoa.nome) LIKE UPPER('%$fornecedor%') 
							OR tbl_pessoa.pessoa like '%$fornecedor%')
				ORDER BY tbl_pedido.status_pedido desc,  pedido desc ";
		}
	}else{
			$sql= "	SELECT 
					tbl_pedido.pedido,
					to_char(tbl_pedido.data,'DD/MM/YYYY') as data,
					tbl_pedido.entrega,
					tbl_pedido.status_pedido,
					tbl_pedido.pedido_cliente,
					tbl_cotacao.cotacao, 
					tbl_cotacao.requisicao_lista, 
					tbl_pessoa.nome,	
					tbl_cotacao_fornecedor.pessoa_fornecedor as fornecedor,
					tbl_status_pedido.status_pedido,
					tbl_status_pedido.descricao,
					tbl_faturamento.nota_fiscal
				FROM tbl_pedido
				LEFT JOIN tbl_faturamento on tbl_faturamento.pedido = tbl_pedido.pedido
				JOIN tbl_cotacao_fornecedor using(cotacao_fornecedor)
				JOIN tbl_pessoa_fornecedor ON tbl_pessoa_fornecedor.pessoa = tbl_cotacao_fornecedor.pessoa_fornecedor
				JOIN tbl_pessoa			   ON tbl_pessoa.pessoa			   = tbl_pessoa_fornecedor.pessoa 
				JOIN tbl_cotacao		   ON tbl_cotacao.cotacao		   = tbl_cotacao_fornecedor.cotacao
				JOIN tbl_status_pedido	   ON tbl_pedido.status_pedido	   = tbl_status_pedido.status_pedido 
				WHERE tbl_cotacao.empresa = $login_empresa 	AND tbl_pedido.status_pedido  = 16
				ORDER BY tbl_pedido.status_pedido desc,  pedido desc ";
	}
//	echo "sql:". $sql;

	$res= pg_exec($con, $sql);
	if(pg_numrows($res)==0){
		echo "
			<tr >	
			  <td nowrap class='menu_top' colspan='7' align='center'>
					<font color='#0000ff'>Sem Produtos Selecionados!</font>
			  </td>
			</tr>";
	}else{

		$c=1;
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$cotacao			=trim(pg_result($res,$i,cotacao));
			$requisicao_lista	=trim(pg_result($res,$i,requisicao_lista));
			$pedido				=trim(pg_result($res,$i,pedido));
			$pedido_cliente		=trim(pg_result($res,$i,pedido_cliente));
			$data				=trim(pg_result($res,$i,data));
			$data_entrega		=trim(pg_result($res,$i,entrega));
			$st_pedido			=trim(pg_result($res,$i,status_pedido));
			$status_pedido		=trim(pg_result($res,$i,descricao));
			$nome				=trim(pg_result($res,$i,nome));
			$nota_fiscal		=trim(pg_result($res,$i,nota_fiscal));

			if ($c==2){ 
				echo "<tr bgcolor='#eeeeff' style='cursor: pointer;' onClick= \"location.href='$pg$pedido'\">"; 
				$c=0;
			}else {
				echo "<tr bgcolor='#fafafa' style='cursor: pointer;' onClick= \"location.href='$pg$pedido'\">"; 
			}
			echo "<td nowrap align='center'><font color='#0000ff'>$pedido_cliente</font></td>";
			echo "<td nowrap align='center'>$nota_fiscal</td>";
			echo "<td nowrap align='left'>$nome</td>";
			echo "<td nowrap align='center' >$data</td>";
			echo "<td nowrap align='center' >$entrega</td>";
			echo "<td nowrap align='center' >";
			
			if ($st_pedido== "16"){ 
				echo "<font color='#0000ff'>$status_pedido</font>";
			}else {
				echo $status_pedido;
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
