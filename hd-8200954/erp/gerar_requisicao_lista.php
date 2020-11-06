<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'menu.php';

//print_r($_POST);
if($_POST["botao"]=="Continuar"){

	$erro = "";

	$cont= $_POST["cont"];
	if(strlen($cont)==0) 
		$erro.= "<br>Cont está vazio!";

	###################  FAZER UPDATE DA TBL_REQUISICAO E TBL_REQUISICAO_ITEM ##################

	if(strlen($erro)==0){

		
		$res  = pg_exec($con, "begin;");

		$sql= "INSERT INTO 
				TBL_REQUISICAO_LISTA (data, empregado, status) 
				VALUES(current_timestamp,  $login_empregado, 'em cotacao');";

		$res= pg_exec($con, $sql);

		//echo "<br>1-sql: $sql";
		//$sql=" SELECT (max(requisicao_lista)+1) as requisicao_lista from tbl_requisicao_lista; ";

		$sql=" SELECT CURRVAL('tbl_requisicao_lista_requisicao_lista_seq') as requisicao_lista;";
		$res= pg_exec($con, $sql);
		
		//echo "<br>2-sql: $sql";

		if(strlen(pg_errormessage($con))>0){
			$erro.= "Erro ao inserir a requisicao_lista";
		}else{
			$requisicao_lista= trim(pg_result($res,0,requisicao_lista));
			if(strlen($requisicao_lista)==0){
				$erro.= "<br>Erro: requisicao_lista vazio!";
			}
		}	
		
		for($i=0;$i< $cont;$i++){

			$requisicao				=trim($_POST["requisicao_$i"]);
			$requisicao_item		=trim($_POST["req_item_$i"]);
			$peca					=trim($_POST["peca_$i"]);
			$quantidade_disponivel	=trim($_POST["qtd_disp_$i"]);
			$quantidade_entregar	=trim($_POST["qtd_entr_$i"]);
			$quantidade_solicitada	=trim($_POST["qtd_solic_$i"]);
			$quantidade_comprar		=trim($_POST["qtd_comprar_$i"]);
			$recusar				=trim($_POST["recusar_$i"]);
			$cotar					=trim($_POST["cotar_$i"]);
			$bloquear				=trim($_POST["bloquear_$i"]);
				
			$status		="";
			$selecao	="";

			if($bloquear == "sim"){
				$status		="bloqueado";
				$selecao	="bloqueado";
			}else{
				if($recusar=="sim"){
					$status		="nao autorizado";
					$selecao	="nao";
				}else{
					if($cotar=="sim"){
						$status		="autorizado";
						$selecao	="sim";
					}
				}
			}

			if(strlen($peca)==0){
				$erro.= "Código de peça está vazio!";
			}
			if(strlen($requisicao_item)==0){
				$erro.= "Código da requisicao_item está vazio!";				
			}
			if(strlen($requisicao)==0){
				$erro.= "Código da requisicao está vazio!";				
			}
			if(strlen($quantidade_comprar)==0){
				$erro.= "Quantidade a comprar está vazio!";				
			}
			if(strlen($quantidade_disponivel)==0)
				$quantidade_disponivel=0;
			if(strlen($quantidade_entregar)==0)
				$quantidade_entregar=0;
			if(strlen($quantidade_solicitada)==0)
				$quantidade_solicitada=0;
			if(strlen($quantidade_comprar)==0)
				$quantidade_comprar=0;

			
			if(strlen($erro)==0 and strlen($selecao)>0) {

				//BLOQUEAR A PEÇA PRA COTAÇÃO
				if (strlen($bloquear)>0){
					//VERIFICAR SE VAI REALMENTE INATIVAR AS PEÇAS??
					$sql= "	UPDATE tbl_peca_item
							SET status = 'inativo'
							WHERE peca = $peca";
					$res= pg_exec($con, $sql);
				}

				$sql= " UPDATE tbl_requisicao_item 
						SET 
							status='$status' 
						WHERE requisicao_item= $requisicao_item;";
				//echo "<br>2-UPD - sql: $sql";
				$res= pg_exec($con, $sql);


				if(strlen(pg_errormessage($con))>0){
					$erro.= "Erro no update tbl_requisicao_item";
				}

				$sql="	SELECT	*
						FROM tbl_requisicao 
						JOIN tbl_requisicao_item using(requisicao)
						WHERE requisicao= $requisicao 
							AND tbl_requisicao_item.status = 'aberto' ;";
				$res= pg_exec($con, $sql);

				if(pg_numrows($res) == 0){
					$sql="	UPDATE tbl_requisicao 
							SET status='finalizado' 
							WHERE requisicao= $requisicao;";
					$res= pg_exec($con, $sql);
					//echo "<br>1-UPD - sql: $sql";
					if(strlen(pg_errormessage($con))>0){
						$erro.= "Erro no update tbl_requisicao_item";
					}				
				}


				$sql= " INSERT INTO tbl_requisicao_lista_item 
						(requisicao_lista, 
						requisicao, 
						peca,
						quantidade_disponivel, 
						quantidade_entregar, 
						quantidade_solicitada, 
						quantidade_comprar, 
						selecao)
					VALUES(
						$requisicao_lista, 
						$requisicao, 
						$peca, 
						$quantidade_disponivel, 
						$quantidade_entregar, 
						$quantidade_solicitada, 
						$quantidade_comprar, 
						'$selecao');";
				//echo "<br>1-INS-sql: $sql";					
				$res= pg_exec($con, $sql);
			
				if(strlen(pg_errormessage($con))>0){
					$erro.= "Erro ao inserir requisicao_lista_item";
				}
			}
		}
		
		//SE ITEM FOR SELECIONADO, ENTAO VAI PARA A COTACAO
		if(strlen($requisicao_lista)>0) {
			$sql="  SELECT 
						tbl_requisicao_lista_item.peca, 
						tbl_estoque.qtde, 
						sum(tbl_requisicao_lista_item.quantidade_solicitada) as qtd, 
						sum(tbl_requisicao_lista_item.quantidade_comprar) as qtd2,
						tbl_estoque_extra.media_7,
						tbl_estoque_extra.media_20,
						tbl_estoque_extra.media_40,
						'a comprar'
					FROM tbl_requisicao_lista
					JOIN tbl_requisicao_lista_item USING(requisicao_lista)
					JOIN tbl_estoque on tbl_estoque.peca =  tbl_requisicao_lista_item.peca
					JOIN tbl_estoque_extra on tbl_estoque_extra.peca = tbl_estoque.peca
					WHERE requisicao_lista = $requisicao_lista and selecao ='sim'
					GROUP BY 
						tbl_requisicao_lista_item.peca, 
						tbl_estoque.qtde, 
						tbl_estoque_extra.media_7,
						tbl_estoque_extra.media_20,
						tbl_estoque_extra.media_40";
			$res= pg_exec($con, $sql);
			if(pg_numrows($res)>0){

				$sql="INSERT INTO TBL_COTACAO 
				   (empresa, data_abertura, status, requisicao_lista) 
				Values($login_empresa, current_date, 'aberta', $requisicao_lista);";
				//echo "<br>1-INS-sql: $sql";
				$res= pg_exec($con, $sql);
			
				if(strlen(pg_errormessage($con))>0){
					$erro.= "Erro ao inserir a COTACAO:".$sql;
				}

				$sql=" SELECT CURRVAL('tbl_cotacao_cotacao_seq') as cotacao;";
				$res= pg_exec($con, $sql);	

				$cotacao= trim(pg_result($res,0,cotacao));
				
				//ECHO "<BR><font color='red'> COTACAO: $cotacao</font>" ;

				$sql= "	INSERT INTO tbl_cotacao_item 
							(
									peca, 
									cotacao, 
									quantidade_disponivel, 
									quantidade_acotar, 
									quantidade_comprar, 
									quantidade_entregar,
									media_7,
									media_20,
									media_40,
									media4,
									status
							) 
							(							
								SELECT 
									tbl_requisicao_lista_item.peca, 
									$cotacao,
									tbl_estoque.qtde, 
									sum(tbl_requisicao_lista_item.quantidade_solicitada) as qtd, 
									sum(tbl_requisicao_lista_item.quantidade_comprar) as qtd2,
									tbl_estoque_extra.quantidade_entregar,
									cast(tbl_estoque_extra.media_7 as numeric(12,2)),
									cast(tbl_estoque_extra.media_20 as numeric(12,2)),
									cast(tbl_estoque_extra.media_40 as numeric(12,2)),
									cast(tbl_estoque_extra.media_7 as numeric(12,2)),
									'a comprar'
								FROM tbl_requisicao_lista
								JOIN tbl_requisicao_lista_item USING(requisicao_lista)
								JOIN tbl_estoque on tbl_estoque.peca =  tbl_requisicao_lista_item.peca
								JOIN tbl_estoque_extra on tbl_estoque_extra.peca = tbl_estoque.peca
								WHERE requisicao_lista = $requisicao_lista and selecao ='sim'
								GROUP BY 
									tbl_requisicao_lista_item.peca, 
									tbl_estoque.qtde, 
									tbl_estoque_extra.media_7,
									tbl_estoque_extra.media_20,
									tbl_estoque_extra.media_40,
									tbl_estoque_extra.quantidade_entregar

							);";
/*
SELECT 
									tbl_requisicao_lista_item.peca, 
									$cotacao,
									tbl_estoque.qtde, 
									sum(tbl_requisicao_lista_item.quantidade_solicitada) as qtd, 
									sum(tbl_requisicao_lista_item.quantidade_comprar) as qtd2,
									tbl_estoque_extra.media_7,
									tbl_estoque_extra.media_20,
									tbl_estoque_extra.media_40,
									'a comprar'
								FROM tbl_requisicao_lista
								JOIN tbl_requisicao_lista_item USING(requisicao_lista)
								JOIN tbl_estoque on tbl_estoque.peca =  tbl_requisicao_lista_item.peca
								JOIN tbl_estoque_extra on tbl_estoque_extra.peca = tbl_estoque.peca
								WHERE requisicao_lista = $requisicao_lista and selecao ='sim'
								GROUP BY 
									tbl_requisicao_lista_item.peca, 
									tbl_estoque.qtde, 
									tbl_estoque_extra.media_7,
									tbl_estoque_extra.media_20,
									tbl_estoque_extra.media_40*/

				//ECHO "<BR><font color='red'> COT_ITEM - SQL: $sql</font>" ;

				$res= pg_exec($con, $sql);
				
				if(strlen(pg_errormessage($con)) > 0){
					$erro.= "Erro ao inserir os itens da cotacao:".$sql;
				}
			}
		}else{
			$erro.= "Erro: requisição lista está vazio.";
		}
	}
	if(strlen($erro)==0){
		$res= pg_exec($con, "commit;");
		echo "<font color='blue'>ok, foi cadastrado com sucesso $erro!</font>";
		echo "<script language='JavaScript'>
			window.location= 'cotacao_mapa.php?cotacao=$cotacao';
		</script>";

	}else{		
		$res= pg_exec($con, "rollback;");
		$host  = $_SERVER['HTTP_HOST'];
		header("Location: http://$host/requisicao_lista.php?msg_erro");
		//echo "<font color='red'>$erro</font>";
	}

}else{
	$requisicao_lista= $_GET["requisicao"];
	if($_POST["botao"]=="gravar"){
		echo "<font color='#ff0000'>SEM PRODUTOS SELECIONADOS!</font>";
	}
}

?>
<table width='700' align='center' border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>
<FORM ACTION='gerar_requisicao_lista.php' METHOD='POST'>
  <tr >
	<td class='menu_top' nowrap colspan='13' align='right' >
		<a href='requisicao_lista.php?requisicao_sistema=gerar'><font color='#0000ff'>GERAR REQUISIÇÃO DO SISTEMA</font></a>
	</td>
  </tr>
  <tr >
	<td class='menu_top' nowrap colspan='13' align='center'  background='imagens/azul.gif'>
		<font size='3'>Gerar Pedido de Compra</font>
	</td>
  </tr>
  <tr bgcolor='#596D9B'>
	<td class='menu_top' nowrap colspan='13' align='center' ></td>
  </tr>
  <tr bgcolor='#596D9B'>
	<td class='titulo' nowrap colspan='4' align='center' >Produto</td>
	<td class='titulo' nowrap colspan='1' align='center' >Usuário</td>
	<td class='titulo' nowrap colspan='5' align='center' >Quantidade</td>
	<td class='titulo' nowrap colspan='3' align='center' >Ações</td>
  </tr>
  <tr bgcolor='#596D9B'>
  	<td class='titulo' nowrap align='center'>#</td>
	<td class='titulo' nowrap align='center'>Requisição</td>
	<td class='titulo' nowrap align='center'>Código</td>
	<td class='titulo' width='200' nowrap nowrap align='center'>Nome do produto</td>
	<td class='titulo' nowrap align='center'>Nome</td>
	<td class='titulo' nowrap align='center'>Disponível</td>
	<td class='titulo' nowrap align='center'>Entregar</td>
	<td class='titulo' nowrap align='center'>Orçando</td>
	<td class='titulo' nowrap align='center'>Solicitada</td>
	<td class='titulo' nowrap align='center'>Comprar</td>
	<td class='titulo' nowrap align='center'>
		<acronym title='Esta opção inativa a peça para as próximas compras!'>Bloquear</acronym>	
	</td>
	<td class='titulo' nowrap align='center'>Recusar</td>
	<td class='titulo' nowrap align='center'>Selec.</td>
  </tr>

<?



$sql= "	select 
			tbl_requisicao_lista_item.peca, 

			tbl_peca.descricao as nome_produto, 
			pessoa_empregado.nome as nome_usuario,
			tbl_requisicao_lista_item.quantidade_disponivel as qd,
			tbl_requisicao_lista_item.quantidade_entregar as qe,
			tbl_requisicao_lista_item.quantidade_solicitada as qs, 
			tbl_requisicao_lista_item.quantidade_comprar as qc, 
			tbl_requisicao_lista_item.selecao,
			tbl_requisicao_lista_item.requisicao,
			tbl_requisicao_lista_item.media_7,
			tbl_requisicao_lista_item.media_20,
			tbl_requisicao_lista_item.media_40
		from tbl_requisicao_lista
		join tbl_requisicao_lista_item using(requisicao_lista)
		join tbl_peca using(peca)
		JOIN tbl_empregado on tbl_empregado.empregado = tbl_requisicao_lista.empregado
		JOIN tbl_pessoa as pessoa_empregado on pessoa_empregado.pessoa = tbl_empregado.pessoa
		where requisicao_lista=$requisicao_lista";

//echo "sql: $sql";
$res= pg_exec($con, $sql);
$c=1;

for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

	$check="";
	//$item_cotacao=trim(pg_result($res,$i,item_cotacao));
	$peca			=trim(pg_result($res,$i,peca));
	$nome_produto	=trim(pg_result($res,$i,nome_produto));
	$nome_usuario	=trim(pg_result($res,$i,nome_usuario));
	$qd				=trim(pg_result($res,$i,qd));
	$qe				=trim(pg_result($res,$i,qe));
	$qs				=trim(pg_result($res,$i,qs));
	$qc				=trim(pg_result($res,$i,qc));
	$selecao		= trim(pg_result($res,$i,selecao));

	if ($selecao=="sim") $check="checked";

	if ($c==2){ 
		echo "<tr bgcolor='#eeeeff'>"; 
		$c=0;
	}else {
		echo "<tr bgcolor='#fafafa'>";
	}
	  echo "<td nowrap class='table_line' align='center'>$produto</td>";
	  echo "<td nowrap class='table_line' align='left'> $nome_produto</td>";
	  echo "<td nowrap class='table_line' align='center' >$nome_usuario</td>";
	  echo "<td nowrap class='table_line' align='center' >$qd</td>";
	  echo "<td nowrap class='table_line' align='center' >$qe</td>";
  	  echo "<td nowrap class='table_line' align='center' >$qs</td>";
  	  echo "<td nowrap class='table_line' align='center' >$qc</td>";
	  echo "<td nowrap class='table_line' align='center' >";
	  echo "<input type='checkbox' name='cotar[]' value='sim' $check>";
	  echo "</td>";
	  echo "</tr>";
	  			
  $c++;
}
?>  
	<tr>
		<td nowrap class='table_line' colspan='4' align='left'>
			<input type='button' onClick="abrir();" name='enviar' value='Gravar'>
		</td>
		<td nowrap class='table_line' colspan='4' align='right'>
			<input type='submit' name='enviar' value='Cancelar'>
		</td>
	</tr>
</table>
</body>
</html>
