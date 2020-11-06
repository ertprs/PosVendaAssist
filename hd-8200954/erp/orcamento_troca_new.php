<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';
include "menu.php";	

$orcamento          = trim($_GET["orcamento"]);
$cliente            = trim($_GET["cliente"]);
$data_entrega       = date("d/m/Y");
$pedido             = $_POST["pedido"];
if(strlen($pedido)==0) { $pedido= $_GET["pedido"]; }
$btn_acao           = trim($_GET["btn_acao"]);
if (strlen($btn_acao)==0){
	$btn_acao = trim($_POST["btn_acao"]);
}

if (strlen($orcamento)>0 ){
	$sql=" SELECT tbl_orcamento.orcamento       ,
				  tbl_orcamento.empresa         ,
				  tbl_pessoa.pessoa
			FROM tbl_orcamento
			JOIN tbl_pessoa ON tbl_pessoa.pessoa=tbl_orcamento.cliente
			WHERE orcamento = $orcamento
			AND   tbl_orcamento.empresa=$login_empresa";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) > 0) {
		$pessoa          = trim(pg_result($res,0,pessoa));
	}
}




	if ($btn_acao == 'Gravar') {
		
		$msg_erro="";	
		$id_peca               = trim($_POST['peca']);
		$requisicao            = trim($_POST['requisicao']);
		$requisicao_lista      = trim($_POST['requisicao_lista']);
		$cotacao               = trim($_POST['cotacao']);
		$cotacao_fornecedor    = trim($_POST['cotacao_fornecedor']);
		$pessoa                = trim($_POST['pessoa']);
		$orcamento             = trim($_POST['orcamento']);
		$cliente               = trim($_POST['cliente']);
		$consumidor_nome       = trim($_POST['consumidor_nome']);
		$referencia            = trim($_POST['referencia']);
		$descricao             = trim($_POST['descricao']);
		$qtde                  = trim($_POST['qtde']);
		$origem                = trim($_POST['origem']);
		$valor_compra          = trim($_POST['valor_compra']);

		if(strlen($referencia)             > 0) $xreferencia             = "'".$referencia."'";     else $xreferencia             = "null";
		if(strlen($descricao)              > 0) $xdescricao              = "'".$descricao."'";
		else $msg_erro .= "Digite a descrição do produto<BR>";
		if(strlen($valor_compra)           > 0) $xvalor_compra           = "'".$valor_compra."'";
		else $msg_erro .= "Digite o valor do produto<BR>";
		if(strlen($qtde)                   > 0) $xqtde                   = "'".$qtde."'";
		else $msg_erro .= "Digite a quantidade do produto<BR>";


		if($xreferencia!="null"){
				$sql = "SELECT * FROM tbl_peca
						WHERE referencia = $xreferencia
						AND fabrica=$login_empresa";
				$res = pg_exec ($con,$sql) ;
				$msg_erro .= pg_errormessage($con);
				if (pg_num_rows($res)>0){
					$id_peca = pg_result($res,0,peca);	
				}
		}		

		if(strlen($msg_erro) == 0){
			$resX = pg_exec ($con,"BEGIN TRANSACTION");
			if (strlen($id_peca)==0){
				$sql = "INSERT INTO tbl_peca (
							referencia    ,
							descricao     ,
							origem        ,
							ativo         ,
							fabrica
						)VALUES (
							' '           ,
							$xdescricao   ,
							'nacional'    ,
							't'           ,
							$login_empresa
						)";
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
				$sql= " SELECT CURRVAL ('seq_peca') as peca";
				$res= pg_exec($con, $sql);

				$id_peca = pg_result ($res,0,0);
				$peca = $id_peca;
				
				$sql = "INSERT INTO tbl_peca_item (
							familia                 ,
							linha                   ,
							peca                    
							)VALUES(
							767                     ,
							447                     ,
							$id_peca                
							)";
				$res= pg_exec($con, $sql);
				$msg_erro .= pg_errormessage($con);
			}
			$sql =" SELECT * 
					FROM tbl_requisicao 
					WHERE orcamento = $orcamento 
					AND   empresa   =$login_empresa ";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if(pg_num_rows($res)==0){
				$sql = "
						INSERT INTO tbl_requisicao (
							data_geracao     ,
							empregado        ,
							empresa          ,
							orcamento        ,
							status
						) VALUES (
							current_timestamp,
							$login_empregado ,
							$login_empresa   ,
							$orcamento       ,
							'aberto' 
						)";
			$res = pg_exec ($con,$sql);
			$sql= " SELECT CURRVAL ('tbl_requisicao_requisicao_seq') as requisicao";
			$res= pg_exec($con, $sql);
			$msg_erro .= pg_errormessage($con);
			$requisicao=trim(pg_result($res,0,requisicao));
			}else {
				$requisicao=trim(pg_result($res,0,requisicao));
				$sql ="	UPDATE tbl_requisicao SET
							data_geracao    = current_timestamp    ,
							empregado       = $login_empregado     
						WHERE requisicao = $requisicao";
				$res= pg_exec($con, $sql);
				$msg_erro .= pg_errormessage($con);
			}


			$sql= "INSERT INTO tbl_requisicao_item(
					requisicao          ,
					peca                ,
					quantidade          ,
					status              ,
					quantidade_atendida ,
					empregado
					) Values (
					$requisicao         ,
					$id_peca            ,
					$xqtde              ,
					'aberto'            ,
					$xqtde              ,
					$login_empregado
					)";
			$res= pg_exec($con, $sql);
			$msg_erro .= pg_errormessage($con);
		
			$sql = "SELECT * 
					FROM tbl_cotacao
					WHERE orcamento=$orcamento
					AND empresa=$login_empresa";
			$res = pg_exec ($con,$sql) ;

			$msg_erro .= pg_errormessage($con);
			if(pg_num_rows($res)==0){
				$sql="INSERT INTO tbl_cotacao (
						empresa              ,
						data_abertura        ,
						data_fechamento      ,
						orcamento            ,
						status               ,
						tipo_cotacao
						) VALUES (
						$login_empresa       ,
						current_date         ,
						current_date         ,
						$orcamento           ,
						'finalizada'         ,
						'TROCA'
						)";

				$res= pg_exec($con, $sql);
				$msg_erro .= pg_errormessage($con);
				$sql=" SELECT CURRVAL('tbl_cotacao_cotacao_seq') as cotacao;";
				$res= pg_exec($con, $sql);
				$msg_erro .= pg_errormessage($con);
				$cotacao = trim(pg_result($res,0,cotacao));
			}else {
				$cotacao = trim(pg_result($res,0,cotacao));
				$sql ="	UPDATE tbl_cotacao SET
						data_abertura   = current_date  ,
						data_fechamento = current_date  
						WHERE cotacao = $cotacao";
				$res= pg_exec($con, $sql);
				$msg_erro .= pg_errormessage($con);	
			}

			$sql="INSERT INTO tbl_cotacao_item (
						peca,
						cotacao,
						status
					) VALUES (
						$id_peca,
						$cotacao,
						'comprado'
					)";
			
			$res= pg_exec($con, $sql);
			$msg_erro .= pg_errormessage($con);
			$sql=" SELECT CURRVAL('tbl_cotacao_item_cotacao_item_seq') as cotacao_item;";
			$res= pg_exec($con, $sql);
			$msg_erro .= pg_errormessage($con);
			$cotacao_item = trim(pg_result($res,0,cotacao_item));
			
			$sql ="UPDATE tbl_requisicao_item set
						cotacao_item = $cotacao_item     ,
						data_cotacao = current_timestamp
					WHERE requisicao=$requisicao";
			$res= pg_exec($con, $sql);

			$msg_erro .= pg_errormessage($con);
		
			$sql =" SELECT * FROM tbl_pessoa_fornecedor
					WHERE pessoa = $pessoa";
			$res = pg_exec ($con,$sql) ;
			$msg_erro .= pg_errormessage($con);

			if (pg_num_rows($res)==0){
				$sql="INSERT INTO tbl_pessoa_fornecedor (
						pessoa         ,
						empresa        ,
						ativo
						) VALUES (
						$pessoa        ,
						$login_empresa ,
						't'
						)";			
			}
			$res = pg_exec ($con,$sql) ;
			$msg_erro .= pg_errormessage($con);	

			$fornecedor = $pessoa;	
			$sql="SELECT * FROM tbl_cotacao_fornecedor
					WHERE cotacao=$cotacao";
			$res= pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if (pg_num_rows($res)==0){
			$sql="INSERT INTO tbl_cotacao_fornecedor (
						pessoa_fornecedor  ,
						cotacao            ,
						condicao_pagamento ,
						status
					) VALUES (
						$fornecedor        ,
						$cotacao           ,
						938                ,
						'cotada'
					)";
			$res= pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			
			$sql=" SELECT CURRVAL('tbl_cotacao_fornecedor_cotacao_fornecedor_seq') as cotacao_fornecedor;";
			$res= pg_exec($con, $sql);
			$msg_erro .= pg_errormessage($con);
			$cotacao_fornecedor= trim(pg_result($res,0,cotacao_fornecedor));
			}else{
				$cotacao_fornecedor= trim(pg_result($res,0,cotacao_fornecedor));
			}
			$total=$qtde * $valor_compra;
			
			$sql="	INSERT INTO tbl_cotacao_fornecedor_item
					(
						cotacao_fornecedor ,
						quantidade         ,
						peca               ,
						preco_avista       ,
						prazo_entrega      ,
						status_item
					) (
					SELECT 
						$cotacao_fornecedor, 
						$xqtde, 
						$id_peca,
						$total             ,
						0                  ,
						'cotado'
					FROM tbl_cotacao
					JOIN tbl_cotacao_item using(cotacao)
					WHERE cotacao = $cotacao
					);";
					
			$res= pg_exec($con, $sql);
			$msg_erro .= pg_errormessage($con);
			
			if(strlen($pedido)==0 and strlen($msg_erro)==0){

				$sql = " SELECT count(pedido) as cont_ped
						 FROM tbl_pedido 
						 WHERE cotacao_fornecedor in 
							(
							SELECT cotacao_fornecedor
							FROM tbl_cotacao_fornecedor 
							WHERE cotacao = $cotacao AND pessoa_fornecedor = $fornecedor
							);";
				
				$res_cont= pg_exec($con, $sql);
				$msg_erro .= pg_errormessage($con);

				if(pg_num_rows($res_cont)>0){
					$cont_ped = trim(pg_result($res_cont, 0,cont_ped));
				}

				if($cont_ped > 0){
					$cont_ped++;
				}else{
					$cont_ped= 1;
				}
				$pedido_cliente = "$requisicao_lista - $cont_ped";
				

			$sql =" SELECT * FROM tbl_pedido
					WHERE cotacao_fornecedor = $cotacao_fornecedor";
			$res = pg_exec ($con,$sql) ;
			$msg_erro .= pg_errormessage($con);
			if (pg_num_rows($res)==0){
				
				$sql=" INSERT INTO tbl_pedido (
							data              ,
							status_pedido     ,
							transportadora    ,
							total             ,
							condicao          ,
							tipo_frete        ,
							valor_frete       ,
							fabrica           ,
							posto             ,
							tipo_pedido       ,
							cotacao_fornecedor,
							pedido_cliente    
						) VALUES (
							current_date    ,
							16              ,
							null            ,
							$total          ,
							938             ,
							0               ,
							null            ,
							$login_empresa  ,
							$login_loja     ,
							2               ,
							$cotacao_fornecedor     ,
							(select requisicao_lista ||' - $cont_ped' from tbl_cotacao where cotacao= $cotacao)
						)";
				$res= pg_exec($con, $sql);
				$msg_erro .= pg_errormessage($con);
				$sql= " SELECT CURRVAL ('seq_pedido') as pedido";
				$res= pg_exec($con, $sql);
				$msg_erro .= pg_errormessage($con);
				$pedido = trim(pg_result ($res,0,pedido));
			}else {
				$pedido = trim(pg_result ($res,0,pedido));
			}
			
				$sql=" INSERT INTO tbl_pedido_item (
							pedido                          ,
							peca                            ,
							qtde                            ,
							preco                           ,
							status_pedido
						) VALUES (
							$pedido     ,
							$id_peca                        ,
							$xqtde                          ,
							$xvalor_compra                  ,
							16
						)";
				$res= pg_exec($con, $sql);
				$msg_erro .= pg_errormessage($con);

				$sql="SELECT SUM(preco*qtde) as total
						FROM tbl_pedido_item
						WHERE pedido=$pedido";
				$res= pg_exec($con, $sql);
				$total=pg_result($res,0,total);
				$msg_erro .= pg_errormessage($con);
				
				$sql ="UPDATE tbl_pedido set
						total=$total
						WHERE pedido=$pedido";
				$res= pg_exec($con, $sql);
				$msg_erro .= pg_errormessage($con);
			}

		}
				if (strlen($msg_erro) == 0) {
					$resX = pg_exec ($con,"COMMIT TRANSACTION");
				}else{
					$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
				}
	}

$pedido_item =$_POST["pedido_item"];
$pedido_item        = trim($_GET["pedido_item"]);
$erro ="";
	
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
					WHERE pedido=$pedido
					GROUP BY qtde 
					HAVING qtde > 0";
				$res= pg_exec($con, $sql);

				if(pg_numrows($res)>0){
					$total=pg_result($res,0,total);
				} else {
					$total='0';
				}
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

if(strlen($pedido)>0 AND strlen($cliente) > 0){

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
			WHERE tbl_pessoa.pessoa=$cliente";

	$res= pg_exec($con, $sql);

	if(pg_numrows($res)>0){
		$nome			= trim(pg_result($res, 0, nome));
		$fornecedor		= trim(pg_result($res, 0, fornecedor));
		$pedido_item	= trim(pg_result($res, 0, pedido_item));
		$total_nota		= trim(pg_result($res, 0, total));
	} 
} else {
	
	if(strlen($cliente) > 0) {
		$sql="SELECT nome
				FROM tbl_pessoa
				JOIN tbl_orcamento ON tbl_pessoa.pessoa=tbl_orcamento.cliente
				WHERE cliente=$cliente";
		$res= pg_exec($con, $sql);

		if(pg_numrows($res)>0){
			$nome = trim(pg_result($res, 0, nome));
		}
	}
}


?>

<? include "javascript_pesquisas.php" ?>
<style>


.Label{
	font-family: Verdana;
	font-size: 10px;
}

.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}

.tabela{
	font-family: Verdana;
	font-size: 10px;
	
}

.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
}

.titulo {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #000000;
}

</style>
<script language="JavaScript">

function fnc_pesquisa_produto (campo, tipo) {
	if (campo.value != "") {
		var url = "";
		url = "produto_pesquisa.php?retorno=<? echo $PHP_SELF ?>&forma=reload&campo=" + campo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.descricao = document.frm.descricao;
		janela.referencia= document.frm.referencia;
		janela.focus();
	}
}

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
</script>

<script language='javascript' src='../ajax.js'></script>
<script language='javascript' src='../ajax_cep.js'></script>
<script language='javascript' src='ajax_orcamento.js'></script>

<? if (strlen($msg_erro)>0) {?>
<div class='error'>
	<? echo $msg_erro; ?>
</div>
<?}?>

<table style=' border:#336600 1px solid; background-color: #e6eef7' align='center' width='700' border='0' class='tabela'>
<? echo "<form method='POST' name='frm' action='$PHP_SELF'>"; ?>
  <tr>
	<td nowrap colspan='7' align='right' >
       <?echo "<a href='orcamento_cadastro.php?orcamento=$orcamento'><font color='#0000ff'>Voltar Orçamento de Venda</font></a>";?>
	</td>
  </tr>
		<tr height='20' bgcolor='#7392BF'>
			<td class='titulo' colspan='7'>Produto do Cliente : <? echo $nome ?></td>
			<input type="hidden" name="orcamento" value="<? echo $orcamento ?>">
			<input type="hidden" name="pessoa" value="<? echo $pessoa ?>">
			<input type="hidden" name="cliente" value="<? echo $cliente ?>">
		</tr>       
		<tr height='3'>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class='Label'>Código</td>
			<td><input class="Caixa" type="text" name="referencia" size="20" maxlength="50" >
			<img src="imagens/lupa.png" border='0' onclick="javascript: fnc_pesquisa_produto (document.frm.referencia, 'referencia')">
			</td>
		</tr>
		<tr>
			<td class='Label'>Produto</td>
			<td><input class="Caixa" type="text" name="descricao" size="30" maxlength="50" ><img src="imagens/lupa.png" border='0' onclick="javascript: fnc_pesquisa_produto (document.frm.descricao, 'descricao')"></td>
		</tr>
		<tr>
		<td class='Label'>Quantidade</td>
			<td><input class="Caixa" type="text" name="qtde" size="3" maxlength="5" style='text-align:right' ></td>
		</tr>
		<tr>
			<td class='Label'>Preço</td>
			<td><input class="Caixa" type="text" name="valor_compra" size="10"  style='text-align:right' maxlength="10" onblur="javascript:checarNumero(this);"></td>
		</tr>
		<tr>
			<td nowrap class='Label'>Data de entrega</td>
			<td><input name="data_entrega" id="data_entrega" size="12" maxlength="10" value="<? echo $data_entrega ?>" type="text" class="Caixa" READONLY ></td>
		</tr>
		<?
			if (strlen($marca)>0)   $btn_msg="Gravar Alterações";
			else                    $btn_msg="Gravar";
		?>		
		<tr>
			<td class='Label' colspan='3' align='center'>
				<input class="botao" type="hidden" name="btn_acao"  value=''>
				<input class="botao" type="button" name="bt"        value='<? echo $btn_msg ?>' onclick="javascript:if (this.form.btn_acao.value!='') alert('Aguarde Submissão'); else if (confirm('Deseja continuar?')){this.form.btn_acao.value='Gravar';this.form.submit();}">
			</td>
		</tr>
		<tr>
			<td nowrap height='5px' colspan='7' class='menu_top' align='center'> </td>
		</tr>
  </form>
</table>
<BR><BR>

<?
	echo "<table style=' border:#336600 1px solid; background-color: #e6eef9' align='center' width='700' border='0' class='tabela'>";
	echo "<form method='POST' name='frm_exc' action=' $PHP_SELF'>"; 
	echo "<input type='hidden' name='pedido' value='$pedido'>";
	echo "<tr>";	
	echo "<td nowrap height='5px' colspan='7' class='menu_top' align='center'> </td>";	
	echo "</tr>";
	echo "</td>
	</tr>";

	echo "<tr>";	
	echo "<td nowrap height='5px' colspan='7' class='menu_top' align='center'> </td>";	
	echo "</tr>";

	echo "	<tr>
		<td class='Label' colspan='7' align='left'>
		</td>
		</tr>";

	echo "
		<tr bgcolor='#fafafa' class='Label'>
		  <td nowrap colspan='7' align='left' >
		<table width='100%' border='0' bordercolor='black' cellspacing='2' cellpadding='3'>
		<tr class='titulo'>
		  <td class='Label'> &nbsp;</td>
		  <td class='Label'> Codigo</td>
		  <td class='Label'> Descrição</td>
		  <td class='Label' align='center'> Qde. Ped.</td>
		  <td class='Label' align='center'> Valor Unitário</td>
		  <td class='Label' align='center'> Valor Total</td>
		  <td class='Label' align='center'>Ação</td>
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


			if ($cor=="#fafafa")	$cor= "#eeeeff";
			else					$cor= "#fafafa";

			echo "<tr bgcolor='$cor' style='font-size: 10px'>";
			echo "<td class='Label'> ".($i+1).
				 "<input type='hidden' name='peca_$i' value='$peca'>
				  <input type='hidden' name='qtde_$i' value='$qtde'>
			</td>";
			echo "<td class='Label'> $peca</td>";
			echo "<td class='Label'> $nome</td>";
			echo "<td class='Label' align='center'>$qtde</td>";
			echo "<td class='Label' align='right'>$preco</td>";
			echo "<td class='Label' align='right'>$sub_total</td>";
			if ($status_pedido != 16) {
				echo "<td class='Label' align=center><font color='#33CC00'>Excluir</font></td>";
			} else {
				echo "<td class='Label' align=center><a href='$PHP_SELF?acao=excluir&pedido=$pedido&pedido_item=$pedido_item&orcamento=$orcamento&cliente=$cliente'><font color='#ff0000'>Excluir</font></a></td>";
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

  <tr class='Label'>
	<td class='Label' nowrap colspan='7' align='right'><font size=3><?echo $total;?></font></td>
  </tr>
  <tr>
<?
	echo "<td colspan='5' class='menu_top' nowrap align='left'>Pedido nº <font color='#ffffff'><b>$pedido</b></font></td>";
?>
  </tr>

</form>
</table>
<?include "rodape.php"?>

