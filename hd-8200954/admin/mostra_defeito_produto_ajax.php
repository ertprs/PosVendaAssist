<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$tipo = $_GET['tipo'];
$linha=$_GET['linha'];
$produto=$_GET['produto'];
$id = $_GET['id'];
$valor = $_GET['valor'];
$defeito_constatado = $_GET['defeito'];

switch ($tipo) {
	
	case 'mostrar':
		$sql = "SELECT produto_defeito_constatado, tbl_defeito_constatado.descricao,tbl_produto_defeito_constatado.mao_de_obra
					FROM tbl_produto_defeito_constatado
					JOIN tbl_defeito_constatado USING(defeito_constatado) 
					JOIN tbl_produto USING (produto)
					WHERE produto = $produto
					order by tbl_defeito_constatado.descricao";

		
		$res = pg_exec ($con,$sql);

		echo "$linha|";
			echo "<table border=1 cellpadding=1 cellspacing=0 style=border-collapse: collapse bordercolor=#d2e4fc align=center width=500>";
				echo "<tr class=Titulo>";
				echo "<td align=center colspan=3>Defeitos Constatados do Produto</td>";
				echo "</tr>";
	
			$total = pg_numrows($res);
			$total_pecas = 0;
			
			for ($i=0; $i<pg_numrows($res); $i++){
				$produto_defeito_constatado      = trim(pg_result($res,$i,'produto_defeito_constatado'));
				$descricao                       = trim(pg_result($res,$i,descricao));
				$mao_de_obra                       = trim(pg_result($res,$i,mao_de_obra));
			
				$mao_de_obra = number_format($mao_de_obra,2,",",".");

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
				echo "<tr>";

				echo "<td bgcolor=$cor align=left nowrap>$descricao</td>";
				echo "<td bgcolor=$cor align=left nowrap>R$ <input type=text id=mao_de_obra_$i name=mao_de_obra_$i value=$mao_de_obra class=frm size=6 onchange=alterar($i,$produto_defeito_constatado,this.value)></td>";
				echo "<td onclick=excluir($linha,$produto_defeito_constatado,$produto)><img src=imagens/btn_excluir.gif></td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<table>";
			echo "<tr>";
			echo "<td>Adicionar Defeito ao Produto</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>Defeito</td>";
			echo "<td>Mão de Obra</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td><input type=hidden size=20 class=frm name=defeito_constatado_$linha id=defeito_constatado_$linha><input type=text size=30 class=frm name=descricao_$linha id=descricao_$linha onfocus=autocompletar_descricao(this,$linha)></td>";
			echo "<td><input type=text size=20 class=frm name=valor_$linha id=valor_$linha><input type=hidden size=20 class=frm name=produto_$linha id=produto_$linha value=$produto></td>";
			echo "<td onclick=gravar($produto,$linha)><img src=imagens/btn_gravar.gif></td>";
			echo "</tr>";
			echo "</table>";

			break;
	case 'alterar':

			$valor = str_replace(',','.',$valor);

			$sql = "UPDATE tbl_produto_defeito_constatado
							SET mao_de_obra = '$valor'
							WHERE produto_defeito_constatado = $id";

			$res = pg_exec ($con,$sql);

			$msg_erro = pg_errormessage($con);

			if (strlen($msg_erro)==0){
				echo $linha."|"."ok";
			}
			else {
				echo $linha."|".$msg_erro;
			}
			break;

			case 'excluir':

			$valor = str_replace(',','.',$valor);

			$sql = "delete from tbl_produto_defeito_constatado
							WHERE produto_defeito_constatado = $id";

			$res = pg_exec ($con,$sql);

			$msg_erro = pg_errormessage($con);

			if (strlen($msg_erro)==0){
				echo $linha."|"."ok|".$cache."|".$produto;
			}
			else {
				echo $linha."|".$msg_erro;
			}
			break;


			case 'gravar':
			
			$valor = str_replace(',','.',$valor);

			$sql = "SELECT produto_defeito_constatado, tbl_defeito_constatado.descricao,tbl_produto_defeito_constatado.mao_de_obra
					FROM tbl_produto_defeito_constatado
					JOIN tbl_defeito_constatado USING(defeito_constatado) 
					JOIN tbl_produto USING (produto)
					WHERE produto = $produto
					AND defeito_constatado = $defeito_constatado
					order by tbl_defeito_constatado.descricao";

			$res = pg_exec($con,$sql);

			if (pg_numrows($res)>0) {
				echo "Defeito Já cadastrado para este produto";
			}
			else {
			$sql = "INSERT INTO tbl_produto_defeito_constatado
							 (defeito_constatado,produto,mao_de_obra)
							 values
 							 ($defeito_constatado,$produto,$valor)";

			$res = pg_exec ($con,$sql);

			$msg_erro = pg_errormessage($con);
			$cache= md5(time());
			if (strlen($msg_erro)==0){
					echo $linha."|"."ok|".$cache."|".$produto;
				}
				else {
					echo $linha."|".$msg_erro;
				}
			break;
			}
}
			?>