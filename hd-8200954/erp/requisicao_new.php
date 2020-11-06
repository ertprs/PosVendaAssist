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
		janela.descricao = document.frm_requisicao.descricao;
		janela.referencia= document.frm_requisicao.referencia;
		//janela.linha     = document.frm_requisicao.linha;
		//janela.familia   = document.frm_requisicao.familia;
		janela.focus();
	}

}

</script>

<script type="text/javascript">
	$(function() {

		$('#container-1').tabs( {fxAutoHeight: true, fxSpeed: 'fast'} );
		$('#container-Principal').tabs( {fxAutoHeight: true} );
	});

$(document).ready(
	function()
	{
		$("a").ToolTipDemo("#FDFAC4", "#645C00");
	}
);
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
	background: #ced7e9;
}

.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
	padding:2px;
}

.Botao{
	FONT: 10pt Arial ;
	BORDER-RIGHT:     #000000 1px solid;
	BACKGROUND-COLOR: #C0C0C0;
	padding:3px;
}

.Label{
	font-family: Verdana;
	font-size: 10px;
}

</style>


<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='600' border='0' class='tabela'>
		<tr height='20' bgcolor='#7392BF'>
			<td class='Titulo_Tabela' align='center' colspan='6'>Requisição</td>
		</tr>
		<tr height='10'>
			<td  align='center' colspan='6'></td>
		</tr>
		<tr>
			<td class='Label'>
				<div id="container-Principal">
					<ul>
						<li><a href="#tab0Incluir"><span><img src='imagens/document-txt-blue-new.png' align=absmiddle> Incluir</span></a></li>
						<li><a href="#tab1Consultar"><span><img src='imagens/lupa.png' align=absmiddle> Consultar</span></a></li>
					</ul>
					<div id="tab0Incluir">
<?
						//OBS: TRATAR PARA TODOS OS POSTOS E FABRICAS
						$empregado =  $login_empregado;

						$requisicao= $_POST["requisicao"];
						if (!$requisicao) 	
							$requisicao= $_GET["requisicao"];

						/*$nova= $_POST["nova"];
						if(strlen($nova)==0){
							$nova= $_GET["nova"];
						}*/

						$botao=$_POST["botao"];

						if($botao== "Adicionar") {
							$erro="";
							$res= pg_exec($con, "begin;");
							if(strlen($requisicao) ==0){

								$sql= " INSERT INTO tbl_requisicao (data_geracao, empregado) 
										VALUES(current_timestamp, $empregado);";
								$res= pg_exec($con, $sql);

								$sql= " SELECT CURRVAL ('tbl_requisicao_requisicao_seq') as requisicao";
								$res= pg_exec($con, $sql);
								
								if(@pg_numrows($res)==0){
									$erro = "Requisição não encontrada!";
								}else{
									$requisicao=trim(pg_result($res,0,requisicao));
								}
							}

							$qtde_solicitada =$_POST["qtde_solicitada"];

							if(strlen($descricao) > 0 and strlen($qtde_solicitada) > 0 and strlen($erro)==0) {
							
								$sql = " SELECT peca 
										 FROM tbl_peca
										 WHERE descricao = '$descricao'
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
										$erro .= "A peça $referencia está bloqueada para compra!";
									}else{
										$sql = " SELECT peca 
												 FROM tbl_requisicao_item
												 WHERE requisicao = $requisicao AND peca = $peca;";
										$res= pg_exec($con, $sql);

										if(@pg_numrows($res)==0){
											$sql= "INSERT INTO tbl_requisicao_item(requisicao, peca, qtde_solicitada, manual) 
													Values($requisicao, $peca, $qtde_solicitada,'t'); ";
											$res= pg_exec($con, $sql);
										}else{
											$erro .= "Peça já requisitada!";
										}
									}
								}else{
									echo "o que vai inserir aqui?? verificar!!";
									exit;
									$xpeca_referencia=$_POST['referencia'];
									if(strlen($xpeca_referencia) ==0) $xpeca_referencia=' ';

									$xpeca_descricao =$_POST['descricao'];

									$sql = "INSERT INTO tbl_peca (
												referencia    ,
												descricao     ,
												origem        ,
												ativo         ,
												fabrica
											)VALUES (
												'$xpeca_referencia'   ,
												'$xpeca_descricao'    ,
												'nacional'            ,
												't'                   ,
												$login_empresa
											)";
									$res = pg_exec ($con,$sql);

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
									
									$sql = "SELECT tbl_estoque.peca
											from tbl_estoque
											where tbl_estoque.peca = $id_peca";
									$res = pg_exec($con, $sql);
									$sql= "INSERT INTO tbl_requisicao_item(
											requisicao   ,
											peca         ,
											quantidade   ,
											status
											) Values (
											$requisicao  ,
											$id_peca        ,
											$quantidade  ,
											'aberto' 
											)";
									$res= pg_exec($con, $sql);
									
									$sql = "SELECT tbl_estoque.peca
											from tbl_estoque
											where tbl_estoque.peca = $id_peca";
									$res = pg_exec($con, $sql);
									if(pg_numrows($res)==0){
										$sql = "INSERT INTO tbl_estoque(peca,qtde)values($id_peca,0)";
										$res = pg_exec($con, $sql);
										
										$sql = "INSERT INTO tbl_estoque_extra(peca,data_atualizacao)values($id_peca,current_date)";
										$res = pg_exec($con, $sql);
									}
								}

								if(pg_errormessage($con) or strlen($erro)>0){
									$erro .= pg_errormessage($con);
									$res= pg_exec($con, "rollback;");
									echo "<script language='JavaScript'>
										window.location= '$PHP_SELF#tab0Incluir&erro=$erro';
									</script>";
									exit;
								}else{
									$res= pg_exec($con, "commit;");
									$peca="";
									$quantidade="";
									$descricao = "";
								}
							}else{
								$erro.= "É necessário selecionar o produto e a quantidade!";
								$res= pg_exec($con, "rollback;");
											echo "<script language='JavaScript'>
										window.location= '$PHP_SELF#tab0Incluir&erro=$erro';
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
												 AND empregado = $empregado
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
										$erro .="Peça não encontrada!";
									}
								}else{
									$erro .= "É necessário digitar o código do item e a quantidade!";
								}
							}
						}

						if(strlen($erro)>0){
							echo "<font color='red'>$erro</font>";
						}
						if(strlen($empregado) > 0 and strlen($requisicao)>0){
							$sql= "	SELECT 
										tbl_requisicao.requisicao,
										to_char(data_geracao,'DD/MM/YYYY') as data_geracao
									FROM tbl_requisicao
									WHERE empregado = $empregado
										AND requisicao= $requisicao";

							$res= pg_exec($con, $sql);
							if(@pg_numrows($res)>0){
								$data_geracao		= trim(pg_result($res,0,data_geracao));
							}
						}
?>
						
						<table class='table_line' width='700' border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>
						<FORM name='frm_requisicao' action="<? $PHP_SELF ?>" METHOD='POST'>
						<tr bgcolor='#fcfcfc'>
							<td nowrap align='center'>
								<table class='table_line' width='100%' border='0' cellspacing='1' cellpadding='2'>
								<tr bgcolor='#596D9B'>
								  <td nowrap colspan='4' class='titulo' align='left'>Selecionar Produto</td>
								</tr>
								<tr bgcolor='#fcfcfc'>
									<td nowrap align='left'><br>Cód. Produto<br>
									  <input type="text" class="frm" name="referencia" value="<? echo $referencia; ?>" size="12" maxlength="20">
										<a href='#'>
										  <img src="imagens/lupa.png" border='0' onclick="javascript: fnc_pesquisa_produto (document.frm_requisicao.referencia, 'referencia')">
										</a>
									</td>
									<td nowrap align='left'><br>Descrição do Produto<br>
										<input type="text" class="frm" size="40" name="descricao" value="<? echo $descricao; ?>" maxlength="50" >
										<a href='#'>
										  <img src="imagens/lupa.png" border='0' onclick="javascript: fnc_pesquisa_produto (document.frm_requisicao.descricao, 'descricao')">
										</a>
									</td>
									<td nowrap align='left'><br>Quantidade<br>
									  <input class='Caixa' type='text' name='qtde_solicitada' value='<?echo $qtde_solicitada;?>' size='10' maxlength=10>
									</td>
									<td nowrap align='right' ><br><br>
										<input type='hidden' name='requisicao' value='<?echo $requisicao;?>'>
										<input class='Botao' type='submit' name='botao' value='Adicionar'>
									</td>
								</tr>
							</table>
						<? //}?>
							</td>
						</tr>
						<tr>
							<td >

							<table width='100%' align='left'  >
							<tr bgcolor='#596D9B'>	
								<td colspan='6' nowrap class='menu_top' align='left' >Produto</td>
							</tr>
							<tr bgcolor='#596D9B'>	
								<td nowrap class='titulo' width='80' align='center'>Cód. Fab.</td>
								<td nowrap class='titulo' width='400' align='center'>Descrição do Produto</td>
								<td nowrap class='titulo' width='80' align='center'>Quantidade</td>
								<td nowrap class='titulo' width='80' align='center'>Status</td>
								<td nowrap class='titulo' colspan='2' align='center'>Ação</td>
							</tr>

<?

						if(strlen($empregado)>0 and strlen($requisicao)>0) {
							$sql= "	SELECT 
										tbl_requisicao.requisicao, 
										tbl_requisicao_item.requisicao_item,
										tbl_requisicao_item.qtde_solicitada, 
										tbl_requisicao_item.data_cotacao, 
										tbl_requisicao_item.data_cancelamento, 
										tbl_requisicao_item.data_bloqueada, 
										CASE 
											WHEN tbl_requisicao_item.data_cotacao is not null then 'em cotação'
											WHEN tbl_requisicao_item.data_cancelamento is not null then 'cancelada'
											WHEN tbl_requisicao_item.data_bloqueada is not null then 'bloqueada'
											ELSE 'aberta'
										END as status,
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
									$qtde_solicitada	=trim(pg_result($res,$i,qtde_solicitada));
									$data_cotacao		=trim(pg_result($res,$i,data_cotacao));
									$data_cancelamento	=trim(pg_result($res,$i,data_cancelamento));
									$data_bloqueada		=trim(pg_result($res,$i,data_bloqueada));
									$status				=trim(pg_result($res,$i,status));
									if ($cor=="#fafafa")	$cor= "#eeeeff";
									else					$cor= "#fafafa";

									echo "<tr bgcolor='$cor' class='table_line'>"; 
									echo "<td nowrap align='center'>$referencia</td>";
									echo "<td nowrap align='left'> $descricao</td>";
									echo "<td nowrap align='center' >$quantidade</td>";

									echo "<td nowrap align='center' >$status</td>";
									echo "<td nowrap width='40' align='center' >";
									if(strlen($data_cotacao) == 0 and strlen($data_cancelamento) == 0 and strlen($data_bloqueada) == 0 ){
										echo "<a href='$PHP_SELF?acao=excluir&requisicao=$requisicao&item=$item'><font color='#ff0000'>Excluir</font></a>";
									}else{
										echo "<font color='#cccccc'>Excluir</font>";
									}			  
									echo "</td>";
									echo "</tr>";
									$c++;
								}
							}
						}else{
							/*if(strlen($nova)==0){
								echo "<tr bgcolor='#eeeeff'>
										<td colspan='6' align='center'>
											<font color='red'>Nenhuma requisição cadastrada!</font> 
										</td>
									  </tr>"; 
							}else{*/
								echo "<tr bgcolor='#eeeeff'>
									<td colspan='6' align='center'>
										<font color='red'>&nbsp;</font> 
									</td>
								  </tr>"; 

							//}
						}
						?>  
							</table>
							</td>
						</tr>
						</table>
						</form>
					</div>
					
					<div id="tab1Consultar">
<?
						echo "<FORM name='frm_consultar' action='$PHP_SELF#tab1Consultar' METHOD='POST'>";
						echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='700' border='0' class='tabela'>";
						echo "<tr bgcolor='#596D9B'>";
						echo "<td width='100%' colspan='2' class='titulo' align='left'>Selecionar os parâmetros para fazer pesquisa</td>";
						echo "</tr>";
						echo "<tr>";
						echo "<td align='left'><BR>
							<font size=2>Número Requisição:</font> &nbsp; 
							<input class='Caixa' type='text' size='5' maxlength='5' name='numero_requisicao'> <BR><BR>";
						echo "</td>";
						

						$xstatus=$_POST['xstatus'];
						if(strlen($xstatus) == 0) {
							$xstatus=$_GET['xstatus'];
						}


						echo "<td>";
						echo "<BR><center>
							<font size=2>Status:</font> &nbsp; 
							<select class='frm' style='width: 200px;' name='xstatus'>\n";
						echo "<option value='todas'";		if ($xstatus == 'todas')	 echo ' selected '; echo ">TODAS</option>\n";
						echo "<option value='aberta'";		if ($xstatus == 'aberta')	 echo ' selected '; echo ">EM ABERTO</option>";
						echo "<option value='cancelada'";	if ($xstatus == 'cancelada') echo ' selected '; echo ">CANCELADA</option>";
						echo "<option value='bloqueada'";	if ($xstatus == 'bloqueada') echo ' selected '; echo ">BLOQUEADA</option>";
						echo "</select></center><BR>";

						echo "</td>";
						echo "</tr>";
						echo "<tr>";
						echo "<td align='center' colspan=3>";
						echo "<center><input class='Botao' type='submit' name='btn_requisicao' value='PESQUISAR'</center>";
						echo "</td>";
						echo "</tr>";
						echo "</table>";
						echo "<BR>";


						$btn_requisicao=$_GET['btn_requisicao'];
						if(strlen($btn_requisicao) == 0) {
							$btn_requisicao=$_POST['btn_requisicao'];
						}
						$numero_requisicao=$_POST['numero_requisicao'];
						if(strlen($numero_requisicao) == 0) {
							$numero_requisicao=$_GET['numero_requisicao'];
						}

						if($btn_requisicao == "PESQUISAR") {

							$cond_status = "";
							if($xstatus == "em cotação"){
								$cond_status = " 
									AND tbl_requisicao_item.data_cotacao is not null 
									AND tbl_requisicao_item.data_cancelamento is null 
									AND tbl_requisicao_item.data_bloqueada is null ";
							}elseif($xstatus == "cancelada"){
									$cond_status = " 
									AND tbl_requisicao_item.data_cancelamento is not null ";
							}elseif($xstatus == "bloqueada"){
									$cond_status = " 
									AND tbl_requisicao_item.data_bloqueada is not null ";
							}elseif($xstatus == "aberta"){
								$cond_status = " 
									AND tbl_requisicao_item.data_cotacao is null 
									AND tbl_requisicao_item.data_cancelamento is null 
									AND tbl_requisicao_item.data_bloqueada is null ";
							}elseif($xstatus == "todas"){
								$cond_status = "";
							}

							$sql= "
								SELECT 
									tbl_requisicao.requisicao, 
									TO_CHAR(data_geracao,'DD/MM/YYYY') as data_geracao, 
									pessoa_empregado.nome,
									CASE 
										WHEN tbl_requisicao_item.data_cotacao is not null then 'em cotação'
										WHEN tbl_requisicao_item.data_cancelamento is not null then 'cancelada'
										WHEN tbl_requisicao_item.data_bloqueada is not null then 'bloqueada'
										ELSE 'aberta'
									END as status,
									COUNT(requisicao_item) as qtd
								FROM tbl_requisicao
								JOIN tbl_requisicao_item using (requisicao)
								JOIN tbl_empregado on tbl_empregado.empregado = tbl_requisicao.empregado
								JOIN tbl_pessoa as pessoa_empregado on pessoa_empregado.pessoa = tbl_empregado.pessoa
								WHERE tbl_empregado.empregado = $login_empregado 
									$cond_status
								";

							if(strlen($numero_requisicao) > 0) {
								$sql .="AND tbl_requisicao.requisicao=$numero_requisicao";
							}							

							$sql.="	GROUP BY tbl_requisicao.requisicao, 
										 tbl_requisicao.data_geracao, 
										 tbl_requisicao_item.data_cotacao,
										 tbl_requisicao_item.data_cancelamento,
										 tbl_requisicao_item.data_bloqueada,
										 pessoa_empregado.nome,
										 pessoa_empregado.pessoa";
	echo "sql: $sql";
							$res= pg_exec($con, $sql);

?>
							<table class='table_line' width='700' border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>
									<tr>
										<td >

									  <table width='100%' align='left'>
										<tr bgcolor='#596D9B'>	
										  <td nowrap class='titulo' width='20%' align='center'>Nº Requisição</td>
										  <td nowrap class='titulo' width='20%' align='center'>Data</td>
										  <td nowrap class='titulo' width='15%' align='center'>Usuário</td>
										  <td nowrap class='titulo' width='25%' align='center'>Qtd de Itens Solicitados</td>
										  <td nowrap class='titulo' width='20%' align='center'>Status</td>
										</tr>
<?
							if(@pg_numrows($res)>0){
								for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
									$requisicao	=trim(pg_result($res,$i,requisicao));
									$data_geracao		=trim(pg_result($res,$i,data_geracao));
									$nome		=trim(pg_result($res,$i,nome));
									$qtd		=trim(pg_result($res,$i,qtd));

									if ($cor=="#fafafa")	$cor= "#eeeeff";
									else					$cor= "#fafafa";
									
									echo "<tr bgcolor='$cor' class='table_line'>"; 
									echo "<td nowrap align='center'>";
									echo "<a href='$PHP_SELF?requisicao=$requisicao'><font color='#0000ff'><U>$requisicao</U></font></a>";
									echo "</td>";
									echo "<td nowrap align='center'> $data_geracao</td>";
									echo "<td nowrap align='center' >$nome</td>";
									echo "<td nowrap align='center' >$qtd</td>";
									if($status=='aberto')
									  echo "<td nowrap align='center' ><font color='#0000ff'>$status</font></td>";
									else
									  echo "<td nowrap align='center' >$status</td>";
									echo "</tr>";
									$c++;
								}
							}else{
								echo "<tr ><td colspan='5' align='center'> <font color='#0000ff'><b>Nenhuma requisição aberta!</font></b></td></tr>"; 
							}
						}
?>
						</td>
					</tr>
					</table>
					</form>
				</div>
			</td>
		</tr>
		<tr height='20'>
			<td  align='center' colspan='6'></td>
		</tr>
</table>
</table>

</body>
</html>



<? include "rodape.php";?>
