<?include 'index.php';
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

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

</style>

<script type="text/javascript">
	jQuery(function($){
		$("#data_pesquisa").maskedinput("99/99/9999");
	});
</script>

<script type="text/javascript">
function formata_data(campo_data, form, campo){
	var mycnpj = '';
	mycnpj = mycnpj + campo_data;
	myrecord = campo;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 5){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}

}

</script>

<script type="text/javascript">
	$(function() {
		$('#container-Principal').tabs( {fxSpeed: 'fast'} );
		$('#container-1').tabs( { fxSpeed: 'fast'} );
	});

$(document).ready(
	function()
	{
		//$("#busca_por").focus(); // da erro qndo abre com outra TAB
		$("input.text, textarea.text").focusFields()
		//$('a.load-local').cluetip({local:true, cursor: 'pointer'});
	}
);
</script>

<? include 'funcoes.php'; ?>



<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='670' border='0' class='tabela'>

		<tr height='20' bgcolor='#7392BF'>
			<td class='Titulo_Tabela' align='center' colspan='6'>Cotação</td>
		</tr>
		<tr height='10'>
			<td  align='center' colspan='6'></td>
		</tr>
		<tr>
			<td class='Label'>
				<div id="container-Principal">
					<ul>
						<li><a href="#tab0Procurar"><span><img src='imagens/lupa.png' align=absmiddle> Busca</span></a></li>
					</ul>
					<div id="tab0Procurar">
<?
						echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='700' border='0' class='tabela'>";
						echo "<FORM name='frm_consultar' action='$PHP_SELF' METHOD='POST'>";
						echo "<tr bgcolor='#596D9B'>";
						echo "<td width='100%' colspan='3' class='titulo' align='left'>Selecionar os parâmetros para fazer pesquisa</td>";
						echo "</tr>";
						echo "<tr>";
						echo "<td align='left' colspan=3><font size=2>&nbsp;</font>"; 
						echo "<tr>";
						echo "<td align='center'><font size=2>Número Cotação:</font> &nbsp; <input class='Caixa' type='text' size='5' maxlength='5' name='numero_cotacao' value=$numero_cotacao>";
						echo "</td>";
?>
						<td align='center'><font size=2>Data de Abertura:</font>
						&nbsp;<input class="Caixa" type="text" id="data_pesquisa" name="data_pesquisa" size="8" maxlength="10" value="<? echo $xdata_pesquisa; ?>" >
						</td>
						</tr>
						<tr>
<?
						$sstatus=$_POST['sstatus'];
						if(strlen($sstatus) == 0) {
							$sstatus=$_GET['sstatus'];
						}
							
						echo "<td colspan=3 align=center>";
						echo "<BR><center><font size=2>Status:</font> &nbsp; <select class='frm' style='width: 200px;' name='sstatus'>\n";
						echo "<option value=''>TODAS</option>\n";
						echo "<option value='aberta' if ($sstatus == 'aberta') 'selected ' ;>ABERTA</option>";
						echo "<option value='finalizada' if ($sstatus == 'finalizada') 'selected ' ;>FINALIZADA</option>";
						echo "</select></center><BR>";
						echo "</td>";
						echo "</tr>";
						echo "<BR>";
						echo "<tr>";
						echo "<td align='center' colspan=3>";
						echo "<center><input class='Botao' type='submit' name='btn_pesquisa' value='PESQUISAR'</center>";
						echo "</td>";
						echo "</tr>";
						echo "</table>";
						echo "<BR>";

						$btn_pesquisa=$_GET['btn_pesquisa'];
						if(strlen($btn_pesquisa) == 0) {
							$btn_pesquisa=$_POST['btn_pesquisa'];
						}
						$numero_cotacao=$_POST['numero_cotacao'];
						if(strlen($numero_cotacao) == 0) {
							$numero_cotacao=$_GET['numero_cotacao'];
						}
						
						$xdata_pesquisa = fnc_formata_data_pg(trim($_POST['data_pesquisa']));
						$xdata_pesquisa = str_replace("'","",$xdata_pesquisa);

						if(strlen($btn_pesquisa)> 0) {
?>
							<table class='table_line' width='700' border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>
							  <tr bgcolor='#596D9B'>
								<td nowrap colspan='6' class='menu_top' align='center'  background='imagens/azul.gif'>
									<font size='3'>Cotação</font>
								</td>
							  </tr>
							  
							  
							  <tr>
								<td nowrap colspan='6'>
								</td>
							  </tr>

							  <tr class='titulo'>	
								<td nowrap width='20%' align='center'>Nº Cotação</td>
								<td nowrap width='20%' align='center'>Qtd Itens</td>
								<td nowrap width='30%' align='center'>Data de Abertura</td>
								<td nowrap width='30%' align='center'>Data de Fechamento</td>
								<td nowrap width='20%' align='center'>Status</td>
							  </tr>

						<?

								$pg= "cotacao_mapa.php?cotacao=";

								$sql= "SELECT 	cotacao, 
												requisicao_lista,
												to_char(DATA_ABERTURA,'DD/MM/YYYY') as data_abertura,
												to_char(DATA_FECHAMENTO,'DD/MM/YYYY') as data_fechamento,
												tbl_cotacao.status ,
												count(cotacao_item)as qtd_item
										FROM tbl_cotacao 
										JOIN tbl_cotacao_item USING(cotacao)";
								if(strlen($numero_cotacao) > 0 AND strlen($sstatus) > 0) {
									$sql.= " WHERE requisicao_lista ='$numero_cotacao' 
											 AND   tbl_cotacao.status='$sstatus' ";
								} else {
									if(strlen($numero_cotacao) > 0 AND $xdata_pesquisa == 'null')
										$sql.= " WHERE requisicao_lista ='$numero_cotacao' ";
								}
								
								if(strlen($sstatus) > 0 AND $xdata_pesquisa != 'null') {
									$sql.=" WHERE tbl_cotacao.status='$sstatus' 
											AND   data_abertura='$xdata_pesquisa' ";
								} else {
									if(strlen($sstatus) > 0 AND strlen($numero_cotacao) == 0 ) {
										$sql.=" WHERE tbl_cotacao.status='$sstatus' ";
									}
								} 
								if($xdata_pesquisa != 'null' AND strlen($sstatus) == 0 AND strlen($numero_cotacao) == 0 ) {
									$sql.=" WHERE data_abertura='$xdata_pesquisa' ";
								} else {
									if($xdata_pesquisa != 'null' AND strlen($numero_cotacao) > 0 ) {
										$sql.=" WHERE data_abertura='$xdata_pesquisa'
												AND   requisicao_lista='$numero_cotacao' ";
									}
								}
								
								$sql.="GROUP BY cotacao, data_abertura, data_fechamento, tbl_cotacao.status ,requisicao_lista
										ORDER BY tbl_cotacao.status, tbl_cotacao.cotacao   desc";
								//echo "sql:$sql";
								$res= pg_exec($con, $sql);
								

								if(pg_numrows($res)==0){
									echo "<tr bgcolor='#fafafa'>"; 
									echo "<td colspan='5' align='center'>
										<font color='#0000ff'>Nenhuma cotação encontrada!</font></td>";
									echo "</tr>";
								}
								for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
									$cotacao			=trim(pg_result($res,$i,cotacao));
									$requisicao_lista	=trim(pg_result($res,$i,requisicao_lista));
									$data_abertura		=trim(pg_result($res,$i,data_abertura));
									$data_fechamento	=trim(pg_result($res,$i,data_fechamento));
									$status				=trim(pg_result($res,$i,status));
									$qtd_item			=trim(pg_result($res,$i,qtd_item));

									if ($cor=="#fafafa")	$cor= "#eeeeff";
									else					$cor= "#fafafa";

									echo "<tr bgcolor='$cor' style='cursor: pointer;' onClick= \"location.href='$pg$cotacao'\">"; 
							//		echo "<td nowrap align='center'><font color='#0000ff'>$requisicao_lista</font></td>";
									echo "<td nowrap align='center'><font color='#0000ff'>$requisicao_lista</font></td>";
									echo "<td nowrap align='center'>$qtd_item</td>";
									echo "<td nowrap align='center'>$data_abertura</td>";
									echo "<td nowrap align='center'>$data_fechamento</td>";
									echo "<td nowrap align='center'>";
									if ($status== "aberta"){ 
									echo "<font color='#0000ff'>$status</font>";
									}else {
									echo "$status";
									}
									echo "</td>";
									echo "</tr>";
						 }
 ?>  
							  </table>
							  </td>
							  </tr>
							</table>
							
						<?}?>
					<div>
				</form>
			</td>
		</tr>
		<tr height='20'>
			<td  align='center' colspan='6'></td>
		</tr>
</table>
<BR><BR>


</body>
</html>



<? /*
							  <tr bgcolor='#fcfcfc'>
								<td>
									<table class='table_line' width='100%' border='0' cellspacing='1' cellpadding='2'>
									  <tr bgcolor='#fcfcfc'>
										<td nowrap align='center'>
											<input type='checkbox' name='cotar' value='sim' 
											if($_GET['cotar']) echo 'checked';>Somente em Aberto
										</td>
										<td nowrap align='left'>Nº Cotação <br>
											<input type='text' name='cotacao' value='<?echo $_GET['cotacao'];?>' size='10' maxlength=10>
										</td>
										<td nowrap align='center'>
										  Mês:<br>
											<select name='mes'>
												<option value='0'>
												<option value='7'>julho
												<option value='8'>agosto
												<option value='9'>setembro
												<option value='10'>outubro
											</select>
										</td>
										<td nowrap align='center'>
										  Ano:<br>
											<select name='ano'>
												<option value='0'>
												<option value='7'>2005
												<option value='8'>2006
											</select>
										</td>
										<td nowrap align='left'><br>
										  <input type='submit' name='enviar' value='Pesquisar'>
										</td>
									  </tr>
									</table>
								</td>
							  </tr>
							  */
							  
							  
							  
							  
	/*
							if (($_GET["enviar"]=="Pesquisar")and( strlen($_GET["cotar"])>0) || (strlen($_GET["cotacao"])>0)) {

								$codcotacao= $_GET["cotacao"];
								$cotar= $_GET["cotar"];
								$pg= "cotacao_mapa.php?cotacao=";
								
								$sql= "SELECT 	cotacao, 
												requisicao_lista,
												to_char(DATA_ABERTURA,'DD/MM/YYYY') as data_abertura,
												to_char(DATA_FECHAMENTO,'DD/MM/YYYY') as data_fechamento,
												tbl_cotacao.status ,
												count(item_cotacao)as qtd_item
										FROM TBL_COTACAO 
										JOIN tbl_cotacao_item USING(COTACAO)
										WHERE cotacao= $codcotacao
										GROUP BY cotacao, data_abertura, data_fechamento, tbl_cotacao.status ,requisicao_lista
										ORDER BY tbl_cotacao.status, tbl_cotacao.status desc";
								if($cotar=="sim"){
									echo "passou";
									$sql.= " and status='aberta'";
									$sql= "SELECT 	cotacao, 
												requisicao_lista,
												to_char(DATA_ABERTURA,'DD/MM/YYYY') as data_abertura,
												to_char(DATA_FECHAMENTO,'DD/MM/YYYY') as data_fechamento,
												tbl_cotacao.status ,
												count(item_cotacao)as qtd_item
										FROM TBL_COTACAO 
										JOIN tbl_cotacao_item USING(COTACAO)
										WHERE status='aberto' 
										GROUP BY cotacao, data_abertura, data_fechamento, tbl_cotacao.status ,requisicao_lista
										ORDER BY cotacao desc";
								}else{
									echo "nao passou:.......>>".$cotar."<<...";
								}
								//echo "sql:".$sql;
								$res= pg_exec($con, $sql);

								for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
									$cotacao		 =trim(pg_result($res,$i,cotacao));
									$requisicao_lista=trim(pg_result($res,$i,requisicao_lista));
									$data_abertura	 =trim(pg_result($res,$i,data_abertura));
									$data_fechamento =trim(pg_result($res,$i,data_fechamento));
									$status			 =trim(pg_result($res,$i,status));
									$qtd_item		 =trim(pg_result($res,$i,qtd_item));

									if ($cor=="#fafafa")	$cor= "#eeeeff";
									else					$cor= "#fafafa";

									echo "<tr bgcolor='$cor' style='cursor: pointer;' onClick= \"location.href='$pg$cotacao'\">"; 
									echo "<td nowrap align='center'><font color='#0000ff'>$cotacao</font></td>";
									echo "<td nowrap align='center'>&nbsp;$qtd_item</td>";
									echo "<td nowrap align='center'> $data_abertura</td>";
									echo "<td nowrap align='center' >$data_fechamento</td>";
									echo "<td nowrap align='center' >";
									if ($status== "aberta"){ 
										echo "<a href='gerar_cotacao.php?cotacao=$cotacao&item=$item_cotacao'><font color='#ff0000'>$status</font></a>";
									}else {
										echo "<a href='gerar_cotacao.php?cotacao=$cotacao&item=$item_cotacao'>$status</a>";
									}
									echo "</td>";
									echo "</tr>";
								}
							}else{*/
							  
							  ?>