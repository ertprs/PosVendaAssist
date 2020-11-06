<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
//include '../admin/autentica_admin.php';
include 'autentica_usuario_empresa.php';

include 'menu.php';

$erro = $_GET["erro"];
?>

<script language="JavaScript">

function fnc_pesquisa_produto (campo, tipo) {
	if (campo.value != "") {
		var url = "";
		url = "produto_pesquisa.php?retorno=<? echo $PHP_SELF ?>&forma=reload&campo=" + campo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.descricao = document.frm_produto.descricao;
		janela.referencia= document.frm_produto.referencia;
		//janela.linha     = document.frm_produto.linha;
		//janela.familia   = document.frm_produto.familia;
		janela.focus();
	}
}
</script>

<body bgcolor='#ffffff' marginwidth='2' marginheight='2' topmargin='2' leftmargin='2' >

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
<?
//OBS: TRATAR PARA TODOS OS POSTOS E FABRICAS
$usuario =  $login_empregado;

$requisicao= $_POST["requisicao"];
if (!$requisicao) 	
	$requisicao= $_GET["requisicao"];

$nova= $_POST["nova"];
if(strlen($nova)==0){
	$nova= $_GET["nova"];
}

$botao=$_POST["botao"];

if($botao== "Adicionar") {
	$erro="";
	$res= pg_exec($con, "begin;");
	if(strlen($requisicao) ==0){

		$sql= " INSERT INTO tbl_requisicao (data, hora, usuario, status) 
				VALUES(current_date, current_time, $usuario, 'aberto');";
		$res= pg_exec($con, $sql);

		$sql= " SELECT CURRVAL ('tbl_requisicao_requisicao_seq') as requisicao";
		$res= pg_exec($con, $sql);
		
		if(@pg_numrows($res)==0){
			$erro = "Requisi��o n�o encontrada!";
		}else{
			$requisicao=trim(pg_result($res,0,requisicao));
		}
	}

	$quantidade=$_POST["quantidade"];

	if(strlen($referencia) > 0 and strlen($quantidade) > 0 and strlen($erro)==0) {
	
		$sql = " SELECT peca 
				 FROM tbl_peca
				 WHERE referencia = '$referencia'
						and fabrica = $login_empresa";

		$res= pg_exec($con, $sql);

		if(pg_numrows($res)>0){
			$peca = trim(pg_result($res,0,peca));

			$sql = " SELECT status
					 FROM tbl_peca_item
					 WHERE peca = $peca;";
			$res= pg_exec($con, $sql);
			$status = trim(pg_result($res,0,status));
			if($status=="inativo"){
				$erro .= "A pe�a $referencia est� bloqueada para compra!";
			}else{
				$sql = " SELECT peca 
						 FROM tbl_requisicao_item
						 WHERE requisicao = $requisicao AND peca = $peca;";
				$res= pg_exec($con, $sql);

				if(@pg_numrows($res)==0){
					$sql= "INSERT INTO tbl_requisicao_item(requisicao, peca, quantidade, status) 
							Values($requisicao, $peca, $quantidade,'aberto'); ";
					$res= pg_exec($con, $sql);
				}else{
					$erro .= "Pe�a j� requisitada!";
				}
			}
		}else{
			$erro .= "Pe�a n�o encontrada!";
		}
		if(pg_errormessage($con) or strlen($erro)>0){
			$erro .= pg_errormessage($con);
			$res= pg_exec($con, "rollback;");
/*			echo "<script language='JavaScript'>
				window.location= 'requisicao.php?nova=nova&erro=$erro';
			</script>";
			exit;*/
		}else{
			$res= pg_exec($con, "commit;");
			$peca="";
			$quantidade="";
			$descricao = "";
		}
	}else{
		$erro.= "� necess�rio selecionar o produto e a quantidade!";
		$res= pg_exec($con, "rollback;");
					echo "<script language='JavaScript'>
				window.location= 'requisicao.php?nova=nova&erro=$erro';
			</script>";
	}
}else{
	if($_GET["acao"]== "excluir"){
		$item		=$_GET["item"];
		if(strlen($item)>0){
			$sql = " SELECT requisicao_item
					 FROM tbl_requisicao_item
					 JOIN tbl_requisicao using(requisicao)
					 WHERE requisicao =$requisicao 
						 AND usuario = $usuario
						 AND requisicao_item = $item;";
			$res= pg_exec($con, $sql);
			if(@pg_numrows($res)>0){
				$sql= " DELETE FROM tbl_requisicao_item
						WHERE requisicao_item = $item";
				$res= pg_exec($con, $sql);
				if(pg_result_error($res)){
					echo "Erro ao excluir.";
					$res= pg_exec($con, " rollback;");
				}else{
					echo "<font color='blue'>Ok, excluido com sucesso.</font>";
					$res= pg_exec($con, " begin;");
				}
			}else{
				$erro .="Pe�a n�o encontrada!";
			}
		}else{
			$erro .= "� necess�rio digitar o c�digo do item e a quantidade!";
		}
	}
}

if(strlen($erro)>0){
	echo "<font color='red'>$erro</font>";
}
if(strlen($usuario) > 0 and strlen($requisicao)>0){
	$sql= "	SELECT 
				tbl_requisicao.requisicao, 
				tbl_requisicao.status as status_requisicao,
				to_char(data,'DD/MM/YYYY') as data 
			FROM tbl_requisicao
			WHERE usuario = $usuario
				AND requisicao= $requisicao";

	$res= pg_exec($con, $sql);
	if(@pg_numrows($res)>0){
		$data				=trim(pg_result($res,0,data));
		$status_requisicao	= trim(pg_result($res,0,status_requisicao));
	}
}
?>

<table class='table_line' width='700' border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>
<FORM name='frm_produto' action="<? $PHP_SELF ?>" METHOD='POST'>
  <tr bgcolor='#596D9B'>
	<td nowrap class='menu_top' align='left' background='imagens/azul.gif'>
		<font size='3'>Requisi��o <? echo $requisicao; if(strlen($requisicao)>0) echo "  Data: $data";?></font>
	</td>
  </tr>
  <tr bgcolor='#fcfcfc'>
	<td nowrap align='center'>

<?	if(($status_requisicao == "aberto") or (strlen($nova)>0)) {?>

	  <table class='table_line' width='100%' border='0' cellspacing='1' cellpadding='2'>
	    <tr bgcolor='#596D9B'>
		  <td nowrap colspan='4' class='titulo' align='left'>Selecionar Produto</td>
		</tr>
        <tr bgcolor='#fcfcfc'>
  	      <td nowrap align='left'><br>
		    C�d. Produto<br>
			  <input type="text" class="frm" name="referencia" value="<? echo $peca; ?>" size="12" maxlength="20">
			    <a href='#'>
				  <img src="imagens/lupa.png" border='0' onclick="javascript: fnc_pesquisa_produto (document.frm_produto.referencia, 'referencia')">
				</a>
		  </td>
  	      <td nowrap align='left'><br>
		    Descri��o do Produto<br>
			  <input type="text" class="frm" size="40" name="descricao" value="<? echo $descricao; ?>" maxlength="50" >
			    <a href='#'>
				  <img src="imagens/lupa.png" border='0' onclick="javascript: fnc_pesquisa_produto (document.frm_produto.descricao, 'descricao')">
			    </a>
		  </td>
		  <td nowrap align='left'><br>
		    Quantidade<br>
			  <input type='text' name='quantidade' value='<?echo $quantidade;?>' size='10' maxlength=10>
		  </td>
  		  <td nowrap align='right' ><br><br>
			  <input type='hidden' name='requisicao' value='<?echo $requisicao;?>'>
			  <input type='submit' name='botao' value='Adicionar'>
		  </td>
		</tr>
	  </table>
<?}?>
    </td>
  </tr>
  <tr>
    <td >

  <table width='100%' align='left'  >
	<tr bgcolor='#596D9B'>	
	  <td colspan='6' nowrap class='menu_top' align='left' >Produto</td>
	</tr>
	<tr bgcolor='#596D9B'>	
	  <td nowrap class='titulo' width='80' align='center'>C�d. Fab.</td>
	  <td nowrap class='titulo' width='400' align='center'>Descri��o do Produto</td>
	  <td nowrap class='titulo' width='80' align='center'>Quantidade</td>
	  <td nowrap class='titulo' width='80' align='center'>Status</td>
	  <td nowrap class='titulo' colspan='2' align='center'>A��o</td>
	</tr>

<?

if(strlen($usuario)>0 and strlen($requisicao)>0) {
	$sql= "	SELECT 
				tbl_requisicao.requisicao, 
				tbl_requisicao_item.requisicao_item,
				tbl_requisicao_item.quantidade, 
				tbl_requisicao_item.status as status,
				tbl_peca.peca, 
				tbl_peca.referencia, 
				tbl_peca.descricao
			FROM tbl_requisicao
			JOIN tbl_requisicao_item using (requisicao)
			JOIN tbl_peca using (peca)
			WHERE requisicao= $requisicao";
// excluido: 	AND usuario = $usuario
		
	//echo $sql;
	$res= pg_exec($con, $sql);
	if(@pg_numrows($res)>0){
		$c=1;
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$item		=trim(pg_result($res,$i,requisicao_item));
			$requisicao	=trim(pg_result($res,$i,requisicao));
			$peca		=trim(pg_result($res,$i,peca));
			$referencia	=trim(pg_result($res,$i,referencia));
			$descricao	=trim(pg_result($res,$i,descricao));
			$quantidade	=trim(pg_result($res,$i,quantidade));
			$status		=trim(pg_result($res,$i,status));

			if ($cor=="#fafafa")	$cor= "#eeeeff";
			else					$cor= "#fafafa";

			echo "<tr bgcolor='$cor' class='table_line'>"; 
			echo "<td nowrap align='center'>$referencia</td>";
			echo "<td nowrap align='left'> $descricao</td>";
			echo "<td nowrap align='center' >$quantidade</td>";
			echo "<td nowrap align='center' >$status</td>";
			echo "<td nowrap width='40' align='center' >";
			if($status=="aberto"){
				echo "<a href='requisicao.php?acao=excluir&requisicao=$requisicao&item=$item'><font color='#ff0000'>Excluir</font></a>";
			}else{
				echo "<font color='#cccccc'>Excluir</font>";
			}			  
			echo "</td>";
			echo "</tr>";
			$c++;
		}
	}
}else{
	if(strlen($nova)==0){
		echo "<tr bgcolor='#eeeeff'>
				<td colspan='6' align='center'>
					<font color='red'>Nenhuma requisi��o cadastrada!</font> 
				</td>
			  </tr>"; 
	}else{
		echo "<tr bgcolor='#eeeeff'>
			<td colspan='6' align='center'>
				<font color='red'>&nbsp;</font> 
			</td>
		  </tr>"; 

	}
}
?>  
   </table>
   </td>
  </tr>
 </form>
</table>
</body>
</html>

