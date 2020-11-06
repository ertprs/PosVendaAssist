<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'menu.php';

if(strlen($_GET["requisicao_sistema"])>0) {
	$sql= "	SELECT 
				tbl_peca.peca
			FROM tbl_peca
			JOIN tbl_peca_item on tbl_peca.peca = tbl_peca_item.peca
			JOIN tbl_estoque on tbl_estoque.peca = tbl_peca.peca
			JOIN tbl_estoque_extra on tbl_estoque_extra.peca = tbl_estoque.peca
			WHERE ( ( tbl_estoque.qtde + tbl_estoque_extra.quantidade_entregar) < (tbl_estoque_extra.media_7 * 7) )
				AND ( ( tbl_estoque_extra.media_7 *7) > 1 ) 
				AND (   tbl_peca_item.status <> 'inativo' OR tbl_peca_item.status IS NULL )";
	$res= pg_exec($con, $sql);
//	echo "sql: $sql";
	if(pg_numrows($res)>0){

		$res= pg_exec($con, "begin;");

		//USUARIO 7 É ADMIN
		$sql="	DELETE FROM 
					tbl_requisicao_item 
				WHERE requisicao_item in(
										SELECT requisicao_item
										FROM tbl_requisicao
										JOIN tbl_requisicao_item using(requisicao)
										WHERE usuario = 7 and tbl_requisicao.status= 'aberto'
										)";
		//echo "sql: $sql";
		$res= pg_exec($con, $sql);

		$sql="	DELETE FROM tbl_requisicao
				WHERE 
					usuario = 7 AND tbl_requisicao.status= 'aberto'";
		//echo "sql: $sql";
		$res= pg_exec($con, $sql);

		$sql= "/* INSERE NA TBL_REQUISICAO, E RESGATA A CHAVE PRIMARIA */
				INSERT INTO tbl_requisicao(data, usuario, status, empresa)					
				VALUES(current_date , 7, 'aberto', $login_empresa);

				SELECT CURRVAL ('tbl_requisicao_requisicao_seq') as requisicao ;";

		$res= pg_exec($con, $sql);	
		$requisicao = trim(pg_result($res,0,requisicao));

		//echo "<br>sql: $sql";

			
		$sql="/* INSERE OS PRODUTOS A SEREM COTADOS*/
				INSERT INTO tbl_requisicao_item
					(requisicao,
					peca,
					quantidade, 
					quantidade_solicitada,
					status)
					(
						SELECT 
							$requisicao, 
							tbl_peca.peca, 
							( ( tbl_estoque_extra.media_7 * 7) - ( tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar)  ) as qc, 
							( ( tbl_estoque_extra.media_7 * 7) - ( tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar)  ) as qcomp, 
							'aberto' 
						FROM tbl_peca 
						JOIN tbl_peca_item on tbl_peca.peca = tbl_peca_item.peca 
						JOIN tbl_estoque on tbl_estoque.peca = tbl_peca.peca 
						JOIN tbl_estoque_extra on tbl_estoque_extra.peca = tbl_estoque.peca 
						WHERE ( ( ( tbl_estoque_extra.media_7 * 7) - ( tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar)  )>0) 
						AND ( tbl_peca_item.status <> 'inativo' OR tbl_peca_item.status IS NULL ) 
					);";

//SQL ANTIGO
/*
					SELECT $requisicao,
						tbl_peca.peca,
						case WHEN (tbl_estoque.qtde + tbl_estoque_extra.quantidade_entregar > 0)
							THEN ((tbl_estoque_extra.media_7 * 7)- (tbl_estoque.qtde + tbl_estoque_extra.quantidade_entregar))
							ELSE ((tbl_estoque_extra.media_7 * 7) + (tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar))
						END  as qc,	
						case WHEN (tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar > 0)
							THEN ((tbl_estoque_extra.media_7 * 7)- (tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar))
							ELSE ((tbl_estoque_extra.media_7 * 7) + (tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar))
						END  as qcomp,
						'aberto'
					FROM tbl_peca
					JOIN tbl_peca_item on tbl_peca.peca = tbl_peca_item.peca
					JOIN tbl_estoque on tbl_estoque.peca = tbl_peca.peca
					JOIN tbl_estoque_extra on tbl_estoque_extra.peca = tbl_estoque.peca
					WHERE ( ( tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar) <	 (tbl_estoque_extra.media_7 * 7) )
						AND ( ( tbl_estoque_extra.media_7 * 7) > 0 ) 
						AND (   tbl_peca_item.status <> 'inativo' OR tbl_peca_item.status IS NULL )
*/					

		//echo "sql: $sql";
		$res= pg_exec($con, $sql);

		if(strlen(pg_errormessage($con))>0){
			$res= pg_exec($con, "ROLLBACK;");
			echo "<font color='red'>erro na geração da requisicao:". pg_errormessage($res)."</font>";
		}else{
			$res= pg_exec($con, "commit;");
			echo "<font color='blue'>Cadastro com sucesso</font>";
			echo "<br><a href='requisicao.php?requisicao=$requisicao'><font color='#0000ff'>$requisicao</font></a>";
		}
	}else{
		$msg_erro ="Não existe produto a ser cotado!";
	}
}

$msg_erro.= $_GET["msg_erro"];

$sql= "
		SELECT 
			tbl_peca.peca, 
			tbl_peca.referencia, 
			tbl_peca.descricao, 
			tbl_estoque.qtde as qd, 
			tbl_requisicao.requisicao,
			tbl_requisicao.data,
			tbl_requisicao_item.requisicao_item,  
			tbl_requisicao_item.status,  
			tbl_requisicao_item.quantidade as qc,  
			tbl_pessoa.nome as nome_usuario,
			qtd_cotacao.orcando,
			tbl_estoque_extra.quantidade_entregar,
			tbl_peca_item.familia,
			tbl_peca_item.linha,
			tbl_peca_item.marca
		FROM tbl_requisicao
		JOIN tbl_requisicao_item on tbl_requisicao_item.requisicao  = tbl_requisicao.requisicao
		JOIN tbl_peca			 on tbl_peca.peca					= tbl_requisicao_item.peca
		JOIN tbl_peca_item       on tbl_peca.peca                   = tbl_peca_item.peca
		left JOIN tbl_estoque		 on tbl_estoque.peca				= tbl_peca.peca
		left JOIN tbl_estoque_extra	 on tbl_estoque.peca				= tbl_estoque_extra.peca
		JOIN tbl_empregado		 on tbl_empregado.empregado			= tbl_requisicao.usuario
		JOIN tbl_pessoa			 on tbl_pessoa.pessoa				= tbl_empregado.pessoa
		LEFT JOIN (	
				SELECT peca, sum(quantidade_acotar)as orcando
				FROM tbl_cotacao 
				JOIN tbl_cotacao_item USING(cotacao) 
				WHERE tbl_cotacao.status='aberta' 
					AND tbl_cotacao_item.status='a comprar'
				GROUP BY peca
			) qtd_cotacao on qtd_cotacao.peca = tbl_requisicao_item.peca

		WHERE tbl_requisicao.status ='aberto' AND tbl_requisicao_item.status ='aberto' 
		order by tbl_peca.descricao";

//echo "sql: $sql";

$res= pg_exec($con, $sql);
echo "<br> <font color='red'> $msg_erro</font>";
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

<script language="javascript">

function check_cotar(linha){
	var recusar		= document.getElementById('recusar_'+linha);
	var cotar = document.getElementById('cotar_'+linha);
	var bloquear= document.getElementById('bloquear_'+linha);
	if (cotar.checked){
		recusar.checked= false;
		bloquear.checked= false;
	}
}

function check_recusar(linha){
	
	var recusar		= document.getElementById('recusar_'+linha);
	var cotar = document.getElementById('cotar_'+linha);
	var bloquear= document.getElementById('bloquear_'+linha);
	if (recusar.checked){
		cotar.checked= false;
		bloquear.checked= false;
	}
}
function check_bloquear(linha){
	var recusar	= document.getElementById('recusar_'+linha);
	var cotar	= document.getElementById('cotar_'+linha);
	var bloquear= document.getElementById('bloquear_'+linha);
	if (bloquear.checked){
		cotar.checked= false;
		recusar.checked= true;
	}
}
</script>

<table width='700' align='center' border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>
<FORM ACTION='gerar_requisicao_lista.php' METHOD='POST'>
  <tr >
	<td class='menu_top' nowrap colspan='13' align='right' >
		<a href='requisicao_lista.php?requisicao_sistema=gerar'><font color='#0000ff'>GERAR REQUISIÇÃO DO SISTEMA</font></a>
	</td>
  </tr>
  <tr >
	<td class='menu_top' nowrap colspan='13' align='center'  background='imagens/azul.gif'>
		<font size='3'>Gerar Cotação de Compra</font>
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
	//IMPRESSAO DAS PEÇAS
  if(pg_numrows($res)>0){
	for($i=0; $i<pg_numrows($res); $i++){

		if ($cor== "#eeeeff")	$cor= "#fafafa";			
		else					$cor= "#eeeeff"; 

		$checked= "checked";
		$requisicao		= pg_result($res,$i,requisicao);
		$peca			= pg_result($res,$i,peca);
		$descricao		= pg_result($res,$i,descricao);		
		$nome_usuario	= pg_result($res,$i,nome_usuario);
		$qtd_disponivel = pg_result($res,$i,qd);
		$referencia		= pg_result($res,$i,referencia);	
		$orcando		= pg_result($res,$i,orcando);	
		$qtd_ent		= pg_result($res,$i,quantidade_entregar);	
		$qtd_solicitada = trim(pg_result($res,$i,qc));// usado no hidden
		$requisicao_item= trim(pg_result($res,$i,requisicao_item));
		$marca          = trim(pg_result($res,$i,marca));	
		$linha          = trim(pg_result($res,$i,linha));	
		$familia        = trim(pg_result($res,$i,familia));	
		if($linha == "447" OR $familia == "767" or $marca==""){$desativado = "DISABLED";}
		$check= $checked;

		if(strlen($qtd_disponivel)==0)
			$qtd_disponivel = 0;
		if(strlen($qtd_ent)==0)
			$qtd_ent = 0;


		echo "<tr bgcolor='$cor' style='font-size: 10px'>";
		echo "<td align='center' nowrap>".($i+1)."</td>";
		echo "<td align='center' nowrap>";
		echo "<a href='requisicao.php?requisicao=$requisicao'><font color='#0000ff'>$requisicao</font></a>";
		echo "</td>";
		echo "<td align='left' nowrap>$referencia</td>";
		echo "<td align='left' nowrap>";
		if($linha == "447" OR $familia == "767" or $marca==""){echo "<a href='cadastro_produto.php?btn_acao=alterar&peca=$peca'>$descricao</a>";}else{
		echo "$descricao";
		}
		echo "</td>";
		echo "<td align='center' nowrap>$nome_usuario</td>";
		echo "<td align='center' nowrap >$qtd_disponivel</td>";
		echo "<td align='center' nowrap >$qtd_ent</td>";
		echo "<td align='center' nowrap >$orcando</td>";
		echo "<td align='center' nowrap >$qtd_solicitada</td>";

		echo "<td align='center' nowrap >
			<input type='text' name='qtd_comprar_$i' value='$qtd_solicitada' size=2 maxlength=10>
		</td>";
		echo "<td align='center' nowrap >";
		if($linha == "447" OR $familia == "767" or $marca==""){
			echo "<input title='Esta opção inativa a peça para as próximas compras!' type='checkbox' name='bloquear_$i' value='sim' $desativado onclick='javascript:check_bloquear(\"$i\")'>";
			echo "</td>";
			echo "<td align='center' nowrap >";
			echo "<input type='checkbox' name='recusar_$i'$desativado value='sim'  onclick='javascript:check_recusar(\"$i\")'>";
		} else {
			echo "<input title='Esta opção inativa a peça para as próximas compras!' type='checkbox' name='bloquear_$i' value='sim' onclick='javascript:check_bloquear(\"$i\")'>";
			echo "</td>";
			echo "<td align='center' nowrap >";
			echo "<input type='checkbox' name='recusar_$i' value='sim'  onclick='javascript:check_recusar(\"$i\")'>";
		}
		echo "</td>";
		echo "<td align='center' nowrap >";
		echo "<input type='hidden' name='qtd_disp_$i' value='$qtd_disponivel'>";
		echo "<input type='hidden' name='qtd_entr_$i' value='$qtd_entregar'>";
		echo "<input type='hidden' name='qtd_solic_$i' value='$qtd_solicitada'>";
		echo "<input type='hidden' name='requisicao_$i' value='$requisicao'>";
		echo "<input type='hidden' name='req_item_$i' value='$requisicao_item'>";
		echo "<input type='hidden' name='peca_$i' value='$peca'>";
		if($linha == "447" OR $familia == "767" or $marca==""){
			echo "<input type='checkbox' name='cotar_$i' $desativado value='sim' $check onclick='javascript:check_cotar(\"$i\")'>";
		} else{
			echo "<input type='checkbox' name='cotar_$i' value='sim' onclick='javascript:check_cotar(\"$i\")'>";

		}
		echo "</td>";
		echo "</tr>";
	}
  }else{
		echo "<tr ><td colspan='13' align='center'> <font color='#0000ff'><b>Sem Requisição Cadastrada!</font></b></td></tr>"; 
  }
?>
  <tr style='font-size: 10px'>
    <td align='right' colspan='13' nowrap>
	  <input type='hidden' name='cont' value='<?echo "$i";?>'>
      <input type='submit' name='botao' value="Continuar">
	</td>
  </tr>
</form>
</table>