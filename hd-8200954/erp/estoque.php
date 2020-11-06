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


	$sql= "SELECT  (5.0000+((5.0000 * percento_lucro)/100)) AS RESP FROM TBL_PECA_ITEM WHERE peca = 569998;";
	$res= pg_exec($con, $sql);	
	$resposta= trim(pg_result($res,$i,resp));
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
<table width="700px" border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>

<FORM ACTION='#' METHOD='GET'>
	<td class='menu_top' nowrap colspan='1' align='left' >
		<a href='estoque.php?atualizar_estoque=sim'>
			<font color='#0000ff'>ATUALIZAR ESTOQUE A ENTREGAR</font>
		</a>
	</td>
	<td class='menu_top' nowrap colspan='2' align='right' >
		<a href='estoque.php?atualizar_media=sim'>
			<font color='#0000ff'>ATUALIZAR AS MÉDIAS DE VENDA</font>
		</a>
	</td>
  
  <tr>
  	<td nowrap class='menu_top' colspan='3' align='left' background='imagens/azul.gif'>
		<font size='3'>Estoque</font>
	</td>
  </tr>
  <tr class='titulo'>
	<td nowrap colspan='1' align='left' class='table_line'><b>Busca<b/></td>
	<td nowrap align='right' colspan='2' class='table_line'>
		&nbsp;
		Peça
		&nbsp;
		<input disabled type='text' name='cotacao' value='<?echo "inativo";//$_GET["cotacao"];?>' size='10' maxlength=10>
		&nbsp;
		Descrição
		&nbsp;
		<input disabled type='text' name='fornecedor' value='<?echo "inativo";//$_GET["fornecedor"];?>' size='10' maxlength=10>
		&nbsp;<input type='submit' name='pesquisar' value='Pesquisar'>
		&nbsp;
	</td>
  </tr>
  <tr>
    <td colspan='3'>

  <table width='100%' align='left'>
	<tr class='titulo'>	
	  <td nowrap width='20%' align='center'>Referência</td>
	  <td nowrap width='20%' align='center'>Descrição</td>
	  <td nowrap width='20%' align='center'>Local</td>
	  <td nowrap width='20%' align='center'>40 dias</td>
	  <td nowrap width='20%' align='center'>20 dias</td>
	  <td nowrap width='30%' align='center'>7 dias</td>
	  <td nowrap width='20%' align='center'>Qtde Estoque</td>
	  <td nowrap width='20%' align='center'>Qtde Entregar</td>
	  <td nowrap width='30%' align='center'>Preço de Compra</td>
	  <td nowrap width='30%' align='center'>Preço Venda</td>

	</tr>
<?
	$referencia= $_GET["referencia"];



	if(strlen($_GET["atualizar_media"])>0) {

		$res= pg_exec($con, "begin;");
		
		$sql= "update tbl_estoque_extra
				set	
					media_7 = cast(vw_tecnoplus_media.media_7 as numeric(12,2)),
					media_20 = cast(vw_tecnoplus_media.media_20 as numeric(12,2)),
					media_40 = cast(vw_tecnoplus_media.media_40 as numeric(12,2))
				where tbl_estoque_extra.peca = vw_tecnoplus_media.peca";
		$res= pg_exec($con, $sql);

		if(strlen(pg_errormessage($con))>0){
			$res= pg_exec($con, "ROLLBACK;");
			echo "<font color='red'>erro na geração da requisicao:". pg_errormessage($res)."</font>";
		}else{
			$res= pg_exec($con, "commit;");
			echo "<font color='blue'>Atualizado com sucesso!</font>";
		}
	}

	//ATUALIZAR ESTOQUE - QTDE_ENTREGAR
	if(strlen($_GET["atualizar_estoque"])>0) {
		$sql= "	UPDATE tbl_estoque_extra
				SET quantidade_entregar =0;";
		$res_upd= pg_exec($con, $sql);		

		//ESTA ROTINA DEVERÁ SE REALIZADA A CADA CADASTRO DE PEDIDO DE COMPRA
		$sql= "	SELECT tbl_pedido_item.peca,
					sum(tbl_pedido_item.qtde) AS qtde_entregar 
				FROM tbl_pedido 
				JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido 
				WHERE tbl_pedido.fabrica = 27 
					AND tbl_pedido.status_pedido = 9 
				GROUP BY tbl_pedido_item.peca";
		$res= pg_exec($con, $sql);
		//echo "sql: $sql";
		if(pg_numrows($res) > 0){
			for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
				$peca	=trim(pg_result($res,$i,peca));
				$qtde	=trim(pg_result($res,$i,qtde_entregar));
				$sql= "	UPDATE tbl_estoque_extra
						SET quantidade_entregar =$qtde
						WHERE peca = $peca;";
				$res_upd= pg_exec($con, $sql);		
			}
		}
	}

	if ((strlen($_GET['pesquisar'])>0)and (strlen($fornecedor)>0)){

		$whr='';
		if(strlen($fornecedor)>0){

		
		$sql= "	SELECT 
					tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca_item.valor_compra,
					tbl_peca_item.valor_venda,
					tbl_estoque.qtde,
					tbl_estoque.almoxarifado,
					tbl_estoque_extra.media_7,
					tbl_estoque_extra.media_20,
					tbl_estoque_extra.media_40,
					tbl_estoque_extra.quantidade_entregar as qe
				FROM tbl_peca
				JOIN tbl_peca_item USING(peca)
				JOIN tbl_estoque USING(peca)						
				JOIN tbl_estoque_extra USING(peca)
				WHERE tbl_peca_item.empresa = $login_empresa
				ORDER BY tbl_peca.descricao";
					/*AND (tbl_posto.nome LIKE UPPER('%$fornecedor%') 
							OR tbl_posto.posto like '%$fornecedor%')
				ORDER BY tbl_pedido.status_pedido asc,  pedido desc ";*/
		}
	}else{
		$sql= "	SELECT 
					tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca_item.valor_compra,
					tbl_peca_item.valor_venda,
					tbl_estoque.qtde,
					tbl_estoque.almoxarifado,
					tbl_estoque_extra.media_7,
					tbl_estoque_extra.media_20,
					tbl_estoque_extra.media_40,
					tbl_estoque_extra.quantidade_entregar as qe
				FROM tbl_peca
				JOIN tbl_peca_item USING(peca)
				JOIN tbl_estoque USING(peca)						
				JOIN tbl_estoque_extra USING(peca)
				WHERE tbl_peca.fabrica = $login_empresa
				ORDER BY tbl_peca.descricao";
	}
	//echo "sql:". $sql;

	$res= pg_exec($con, $sql);
	if(pg_numrows($res)==0){
		echo "
			<tr >	
			  <td nowrap class='menu_top' colspan='6' align='center'>
					<font color='#ff0000'>SEM PRODUTOS SELECIONADOS!</font>
			  </td>
			</tr>";
	}else{

		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$peca			=trim(pg_result($res,$i,peca));
			$referencia		=trim(pg_result($res,$i,referencia));
			$descricao		=trim(pg_result($res,$i,descricao));
			$valor_compra	=trim(pg_result($res,$i,valor_compra));
			$valor_venda	=trim(pg_result($res,$i,valor_venda));
			$qtde			=trim(pg_result($res,$i,qtde));
			$almoxarifado	=trim(pg_result($res,$i,almoxarifado));
			$media_7		=trim(pg_result($res,$i,media_7));
			$media_20		=trim(pg_result($res,$i,media_20));
			$media_40		=trim(pg_result($res,$i,media_40));
			$qe				=trim(pg_result($res,$i,qe));

			if ($cor=="#eeeeff")	$cor ="#fafafa";
			else					$cor ="#eeeeff";

			echo "<tr bgcolor='$cor' class='table_line' >"; 
			echo "<td nowrap align='left'>$referencia</td>";
			echo "<td nowrap align='left'>$descricao</td>";
			echo "<td nowrap align='center'>$almoxarifado</td>";
			echo "<td nowrap align='center'>$media_40</td>";
			echo "<td nowrap align='center'>$media_20</td>";
			echo "<td nowrap align='center'>$media_7</td>";
			echo "<td nowrap align='center'>$qtde</td>";
			echo "<td nowrap align='center'>$qe</td>";
			echo "<td nowrap align='right'>$valor_compra</td>";
			echo "<td nowrap align='right'>$valor_venda</td>";
			echo "</tr>";
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
