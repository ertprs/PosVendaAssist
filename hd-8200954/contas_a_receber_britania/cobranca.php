<? 
include 'menu.php';
if ($logado==""){header("Location: index.php"); }
include 'banco.php';
?>
<style>
.body{
	font-family:Arial, Helvetica, sans-serif;
	font-size:9px;
}

table.bordasimples {border-collapse: collapse;}

table.bordasimples tr td {
	border:1px solid #D9E2EF;
	font-family:Arial, Helvetica, sans-serif;
	font-size:9px;

	
}

.pgoff {font-family: Verdana, Arial, Helvetica; font-size: 11px; color: #FF0000; text-decoration: none}
a.pg {font-family: Verdana, Arial, Helvetica; font-size: 11px; color: #003366; text-decoration: none}
a:hover.pg {font-family: Verdana, Arial, Helvetica; font-size: 11px; color: #0066cc; text-decoration:underline}


</style>

<script type="text/javascript">
function exibe(id) {
	if(document.getElementById(id).style.display=="none") {
		document.getElementById(id).style.display = "inline";
	}
	else {
		document.getElementById(id).style.display = "none";
	}
}
</script>
<br>


<?
// baixa nota
$baixar = $_GET["baixar"];
if ($baixar=="sim"){
$nota = $_GET["nota"];
$parcela = $_GET["parcela"];
$id_empresa = $_GET["id_empresa"];

$sql2 = "Update tbl_cobranca_nota set status=1 where id_empresa='$id_empresa' and nota='$nota' and parcela='$parcela'";
	$res2 = pg_exec($con,$sql2);
}
// fim baixa nota


// grava historico
$acao = $_GET["acao"];
if ($acao=="gravar"){

$historico = $_POST["historico"];
$id_empresa = $_POST["id_empresa"];
$nota = $_POST["nota"];
$parcela = $_POST["parcela"];

	$sql2 = "insert into tbl_cobranca_historico (historico,usuario,id_empresa,parcela,nota) values ('$historico','$nome','$id_empresa','$parcela','$nota')";
	$res2 = pg_exec($con,$sql2);

}
//fim grava historico

// selecio o tipo de exibição
$tipo = $_POST["tipo"];
$busca = $_POST["busca"];
if ($tipo==""){
$tipo = $_GET["tipo"];
$busca = $_GET["busca"];
}


if ($tipo=="select"){

	// exibição por razão social em ordem alfabética 
	if ($busca=="nome"){

		// dados Paginação
			$numreg = 40; // Quantos registros por página vai ser mostrado
				$pg = $_GET["pg"];
			if (!isset($pg)) {
				$pg = 0;
			}
			
			$inicial = $pg * $numreg;
			
		// FIM dados Paginação
		
		// Faz o Select pegando o registro inicial até a quantidade de registros para página
		$sql = pg_query("SELECT cnpj,codigo_filial,representante,codigo_matriz,razao_social,endereco,cidade,estado,cep,telefone,fax,id_empresa from tbl_cobranca_empresa LIMIT $numreg OFFSET $inicial");

		// Serve para contar quantos registros você tem na seua tabela para fazer a paginação
		$sql_conta = pg_query("SELECT id_empresa FROM tbl_cobranca_empresa");
		
		$quantreg = pg_num_rows($sql_conta); // Quantidade de registros pra paginação
		
		include("paginacao.php"); // Chama o arquivo que monta a paginação. ex: << anterior 1 2 3 4 5 próximo >>
  

  ?>
<br><br>
<TABLE  width='800px' align='center' border='0' cellspacing="1" cellpadding="3" class="bordasimples">
	<tr align='center' bgcolor="#D9E2EF">

	<tr align='center'>
		<td>
			<b>Representante</b>
		</td>
		<td>
			<b>Cod. Matriz</b>
		</td>
		<td>
			<b>Cod. Filial</b>
		</td>
		<td>
			<b>Razão Social</b>
		</td>
		<td>
			<b>Nº DP's Vencidas</b>
		</td>
		<td>
			<b>Total Vencido</b>
		</td>
		<td>
			<b>Vencimento mais antigo</b>
		</td>
		<td>
			<b>Responsável</b>
		</td>
	</tr>
  <?
    
    while ($row = pg_fetch_array($sql)) {
		$cnpj = $row["cnpj"];
		$codigo_filial = $row["codigo_filial"];
		$representante = $row["representante"];
		$codigo_matriz = $row["codigo_matriz"];
		$razao_social = $row["razao_social"];
		$endereco = $row["endereco"];
		$cidade = $row["cidade"];
		$estado = $row["estado"];
		$cep = $row["cep"];
		$telefone = $row["telefone"];
		$fax = $row["fax"];
		$id_empresa = $row["id_empresa"];
		
		$total = 0;
		$n_dp = 0;

		// select para calcular valor vencido e buscar vencimento mais antigo por empresa
		$sql2 = "select vencimento,valor_saldo,nota,parcela from tbl_cobranca_nota where id_empresa = $id_empresa and status=0 order by vencimento desc";
		$res2 = pg_exec($con,$sql2);
					while ($row2 = pg_fetch_array($res2)) {

					$vencimento = $row2["vencimento"];
					$total = $total+$row2["valor_saldo"];
					$parcela = $row2["parcela"];
					$n_dp++;
					$nota = $row2["nota"];
			}
?>

	<tr align='left' bgcolor='ffffff'>
		<td>
			<a name="<?=$id_empresa?>"></a><a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$representante?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$codigo_matriz?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$codigo_filial?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$razao_social?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$n_dp?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$total?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$vencimento?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$vencimento?></a>
		</td>

	</tr>
	<tr align='center' bgcolor='ffffff'>
		<td colspan='8'>
			<div id="conteudo<?=$id_empresa?>" style="display: none;"> 
				<table width='800'  border='0' cellspacing="0" cellpadding="3" class="bordasimples">
					<tr>
						<td colspan='1'>CNPJ</td>
						<td colspan='6' align='left'><?=$cnpj?></td>
						<td colspan='1'>ENDEREÇO</td>
						<td colspan='6' align='left'><?=$endereco?></td>
					</tr>
					<tr>
						<td colspan='1'>CIDADE</td>
						<td colspan='4' align='left'><?=$cidade?></td>
						<td colspan='1'>ESTADO</td>
						<td colspan='3' align='left'><?=$estado?></td>
						<td colspan='1'>CEP</td>
						<td colspan='4' align='left'><?=$cep?></td>
					</tr>
					<tr>
						<td colspan='1'>TELEFONE</td>
						<td colspan='6' align='left'><?=$telefone?></td>
						<td colspan='1'>FAX </td>
						<td colspan='6' align='left'><?=$fax?></td>
					</tr>
					<tr>
						<td colspan='14'>Relação de notas em Aberto</td>
					</tr>
<?
	// busca informações da nota		
	$sql3 = "select filial,especie,serie,nota,banco,nosso_numero,parcela,carteira,emissao,vencimento,valor_original,valor_saldo,despesas, status from tbl_cobranca_nota where id_empresa = $id_empresa order by vencimento";
			$res3 = pg_exec($con,$sql3);
			while ($row3 = pg_fetch_array($res3)) {
				$filial = $row3["filial"];
				$especie = $row3["especie"];
				$serie = $row3["serie"];
				$nota = $row3["nota"];
				$banco = $row3["banco"];
				$nosso_numero = $row3["nosso_numero"];
				$parcela = $row3["parcela"];
				$carteira = $row3["carteira"];
				$emissao = $row3["emissao"];
				$vencimento = $row3["vencimento"];
				$valor_original = $row3["valor_original"];
				$valor_saldo = $row3["valor_saldo"];
				$despesas = $row3["despesas"];
				$status = $row3["status"];
				
?>
					<tr>
						<td>FILIAL</td>
						<td>ESPECIE</td>
						<td>SERIE</td>
						<td>Nº DA NOTA</td>
						<td>BANCO</td>
						<td>NOSSO Nº</td>
						<td>PARCELA</td>
						<td>CARTEIRA</td>
						<td>EMISSÃO</td>
						<td>VENCIMENTO</td>
						<td>VLR ORIG</td>
						<td>VLR SALDO</td>
						<td>DESPESAS</td>
						<td>BAIXAR NOTA</td>
					</tr>			
					<tr>
						<td><?=$filial?></td>
						<td><?=$especie?></td>
						<td><?=$serie?></td>
						<td><?=$nota?></td>
						<td><?=$banco?></td>
						<td><?=$nosso_numero?></td>
						<td><?=$parcela?></td>
						<td><?=$carteira?></td>
						<td><?=$emissao?></td>
						<td><?=$vencimento?></td>
						<td><?=$valor_original?></td>
						<td><?=$valor_saldo?></td>
						<td><?=$despesas?></td>
						<FORM METHOD=POST ACTION="cobranca.php?pg=<?=$pg?>&tipo=<?=$tipo?>&busca=<?=$busca?>&texto=<?=$texto?>&baixar=sim&nota=<?=$nota?>&parcela=<?=$parcela?>&id_empresa=<?=$id_empresa?>"><td><input type='submit' value="excluir" size='10'></td></FORM>
					</tr>
					<tr>
						<td colspan='14'>Histórico</td>
					</tr>
					<?
					// busca histórico da nota
					$sql4 = "select historico,data,usuario from tbl_cobranca_historico where id_empresa='$id_empresa' and parcela='$parcela' and nota='$nota' order by data";
					$res4 = pg_exec($con,$sql4);
					if(pg_numrows($res4)> 0){
						while ($row4 = pg_fetch_array($res4)) {
							$historico = $row4["historico"];
							$data = $row4["data"];
							$usuario = $row4["usuario"]; 

							echo "<tr><td colspan='10' rowspan='2' align='left'>$historico</td><td>Data</td><td colspan='3' align='left'>$data</td></tr><tr><td>Usuário</td><td colspan='3' align='left'>$usuario</td></tr>";
						}
					}
					if ($status==0){
						echo "<FORM METHOD=POST ACTION='cobranca.php?pg=$pg&acao=gravar'>
<INPUT TYPE='hidden' NAME='id_empresa' value='$id_empresa'><INPUT TYPE='hidden' NAME='nota' value='$nota'><INPUT TYPE='hidden' NAME='parcela' value='$parcela'><tr><td colspan='14'><TEXTAREA NAME='historico' ROWS='3' COLS='100'></TEXTAREA></td></tr><tr><td colspan='14'><input type='submit' value='Gravar histórico' size='10'></td></tr></form>";
					}
		
			}
?>
				</table>
			</div>
<?
    }
?>
		</td>
	</tr>
</table>
<br><br>
<?
	// fim exibição por razão social em ordem alfabética 
	}else{
	// exibe por nota

		// seleciona orde de exibição por vencimento ou valor
		if ($busca=="data_antiga"){
			$ordem="order by vencimento";
		}
		if ($busca=="data_recente"){
			$ordem="order by vencimento desc";
		}
		if ($busca=="menor_valor"){
			$ordem="order by valor_saldo";
		}
		if ($busca=="maior_valor"){
			$ordem="order by valor_saldo desc";
		}
		// fim seleciona orde de exibição por vencimento ou valor

		// dados Paginação
			$numreg = 40; // Quantos registros por página vai ser mostrado
				$pg = $_GET["pg"];
			if (!isset($pg)) {
				$pg = 0;
			}
			$inicial = $pg * $numreg;
		// FIM dados Paginação
		
		// Faz o Select pegando o registro inicial até a quantidade de registros para página
		$sql = pg_query("select filial,especie,serie,nota,banco,nosso_numero,parcela,carteira,emissao,vencimento,valor_original,valor_saldo,despesas,id_empresa,status from tbl_cobranca_nota where status=0 $ordem LIMIT $numreg OFFSET $inicial ");

		// Serve para contar quantos registros você tem na seua tabela para fazer a paginação
		$sql_conta = pg_query("SELECT nota FROM tbl_cobranca_nota");
		
		$quantreg = pg_num_rows($sql_conta); // Quantidade de registros pra paginação
		
		include("paginacao.php"); // Chama o arquivo que monta a paginação. ex: << anterior 1 2 3 4 5 próximo >>

?>
<br><br>
<TABLE  width='800px' align='center' border='0' cellspacing="1" cellpadding="3" class="bordasimples">
	<tr align='center' bgcolor="#D9E2EF">

	<tr align='center'>
		<td>
			<b>Representante</b>
		</td>
		<td>
			<b>Cod. Matriz</b>
		</td>
		<td>
			<b>Cod. Filial</b>
		</td>
		<td>
			<b>Razão Social</b>
		</td>
		<td>
			<b>Nº DP's Vencidas</b>
		</td>
		<td>
			<b>Total Vencido</b>
		</td>
		<td>
			<b>Vencimento mais antigo</b>
		</td>
		<td>
			<b>Responsável</b>
		</td>
	</tr>
<?

		while ($row = pg_fetch_array($sql)) {
				$filial = $row["filial"];
				$especie = $row["especie"];
				$serie = $row["serie"];
				$nota = $row["nota"];
				$banco = $row["banco"];
				$nosso_numero = $row["nosso_numero"];
				$parcela = $row["parcela"];
				$carteira = $row["carteira"];
				$emissao = $row["emissao"];
				$vencimento = $row["vencimento"];
				$valor_original = $row["valor_original"];
				$valor_saldo = $row["valor_saldo"];
				$despesas = $row["despesas"];
				$status = $row["status"];
				$id_empresa = $row["id_empresa"];
					
					$sql2 = "SELECT cnpj,codigo_filial,representante,codigo_matriz,razao_social,endereco,cidade,estado,cep,telefone,fax from tbl_cobranca_empresa where id_empresa='$id_empresa'";
					$res2 = pg_exec($con,$sql2);	
					if(pg_numrows($res2)> 0){ 
						$cnpj=pg_result($res2,0,cnpj);
						$codigo_filial=pg_result($res2,0,codigo_filial);
						$representante=pg_result($res2,0,representante);
						$codigo_matriz=pg_result($res2,0,codigo_matriz);
						$razao_social=pg_result($res2,0,razao_social);
						$endereco=pg_result($res2,0,endereco);
						$cidade=pg_result($res2,0,cidade);
						$estado=pg_result($res2,0,estado);
						$cep=pg_result($res2,0,cep);
						$telefone=pg_result($res2,0,telefone);
						$fax=pg_result($res2,0,fax);
					}
?>
<tr align='left' bgcolor='ffffff'>
		<td>
			<a name="<?=$id_empresa?>"></a><a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$representante?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$codigo_matriz?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$codigo_filial?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$razao_social?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$n_dp?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$total?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$vencimento?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$vencimento?></a>
		</td>

	</tr>
	<tr align='center' bgcolor='ffffff'>
		<td colspan='8'>
			<div id="conteudo<?=$id_empresa?>" style="display: none;"> 
				<table width='800'  border='0' cellspacing="0" cellpadding="3" class="bordasimples">
					<tr>
						<td colspan='1'>CNPJ</td>
						<td colspan='6' align='left'><?=$cnpj?></td>
						<td colspan='1'>ENDEREÇO</td>
						<td colspan='6' align='left'><?=$endereco?></td>
					</tr>
					<tr>
						<td colspan='1'>CIDADE</td>
						<td colspan='4' align='left'><?=$cidade?></td>
						<td colspan='1'>ESTADO</td>
						<td colspan='3' align='left'><?=$estado?></td>
						<td colspan='1'>CEP</td>
						<td colspan='4' align='left'><?=$cep?></td>
					</tr>
					<tr>
						<td colspan='1'>TELEFONE</td>
						<td colspan='6' align='left'><?=$telefone?></td>
						<td colspan='1'>FAX </td>
						<td colspan='6' align='left'><?=$fax?></td>
					</tr>
					<tr>
						<td colspan='14'>Dados da Nota</td>
					</tr>
					<tr>
						<td>FILIAL</td>
						<td>ESPECIE</td>
						<td>SERIE</td>
						<td>Nº DA NOTA</td>
						<td>BANCO</td>
						<td>NOSSO Nº</td>
						<td>PARCELA</td>
						<td>CARTEIRA</td>
						<td>EMISSÃO</td>
						<td>VENCIMENTO</td>
						<td>VLR ORIG</td>
						<td>VLR SALDO</td>
						<td>DESPESAS</td>
						<td>BAIXAR NOTA</td>
					</tr>			
					<tr>
						<td><?=$filial?></td>
						<td><?=$especie?></td>
						<td><?=$serie?></td>
						<td><?=$nota?></td>
						<td><?=$banco?></td>
						<td><?=$nosso_numero?></td>
						<td><?=$parcela?></td>
						<td><?=$carteira?></td>
						<td><?=$emissao?></td>
						<td><?=$vencimento?></td>
						<td><?=$valor_original?></td>
						<td><?=$valor_saldo?></td>
						<td><?=$despesas?></td>
						<FORM METHOD=POST ACTION="cobranca.php?pg=<?=$pg?>&tipo=<?=$tipo?>&busca=<?=$busca?>&texto=<?=$texto?>&baixar=sim&nota=<?=$nota?>&parcela=<?=$parcela?>&id_empresa=<?=$id_empresa?>"><td><input type='submit' value="excluir" size='10'></td></FORM>
					</tr>
					<tr>
						<td colspan='14'>Histórico</td>
					</tr>
<?
					// busca histórico da nota
					$sql3 = "select historico,data,usuario from tbl_cobranca_historico where id_empresa='$id_empresa' and parcela='$parcela' and nota='$nota' order by data";
					$res3 = pg_exec($con,$sql3);
					if(pg_numrows($res3)> 0){
						while ($row3 = pg_fetch_array($res3)) {
							$historico = $row3["historico"];
							$data = $row3["data"];
							$usuario = $row3["usuario"]; 

							echo "<tr><td colspan='10' rowspan='2' align='left'>$historico</td><td>Data</td><td colspan='3' align='left'>$data</td></tr><tr><td>Usuário</td><td colspan='3' align='left'>$usuario</td></tr>";
						}
					}
					if ($status==0){
						echo "<FORM METHOD=POST ACTION='cobranca.php?pg=$pg&acao=gravar'>
<INPUT TYPE='hidden' NAME='id_empresa' value='$id_empresa'><INPUT TYPE='hidden' NAME='nota' value='$nota'><INPUT TYPE='hidden' NAME='parcela' value='$parcela'><tr><td colspan='14'><TEXTAREA NAME='historico' ROWS='3' COLS='100'></TEXTAREA></td></tr><tr><td colspan='14'><input type='submit' value='Gravar histórico' size='10'></td></tr></form>";
					}
?>
				</table>
			</div>
<?
		}
?>
		</td>
	</tr>
</table>
<br><br>
<?
		// fim exibe por nota
	}

}else{

$texto = $_POST["texto"];

if ($texto==""){
$texto = $_GET["texto"];
}

//busca por CNPJ ou Razão Social

if ($busca=="cnpj"){
$like="where cnpj LIKE '%$texto%'";
}else{
$like="where razao social LIKE '%$texto%'";
}

		// dados Paginação
			$numreg = 40; // Quantos registros por página vai ser mostrado
				$pg = $_GET["pg"];
			if (!isset($pg)) {
				$pg = 0;
			}
			
			$inicial = $pg * $numreg;
			
		// FIM dados Paginação
		
		// Faz o Select pegando o registro inicial até a quantidade de registros para página
		$sql = pg_query("SELECT cnpj,codigo_filial,representante,codigo_matriz,razao_social,endereco,cidade,estado,cep,telefone,fax,id_empresa from tbl_cobranca_empresa $like LIMIT $numreg OFFSET $inicial");

		// Serve para contar quantos registros você tem na seua tabela para fazer a paginação
		$sql_conta = pg_query("SELECT id_empresa FROM tbl_cobranca_empresa $like");
		
		$quantreg = pg_num_rows($sql_conta); // Quantidade de registros pra paginação
		
		include("paginacao.php"); // Chama o arquivo que monta a paginação. ex: << anterior 1 2 3 4 5 próximo >>
  

  ?>
<br><br>
<TABLE  width='800px' align='center' border='0' cellspacing="1" cellpadding="3" class="bordasimples">
	<tr align='center' bgcolor="#D9E2EF">

	<tr align='center'>
		<td>
			<b>Representante</b>
		</td>
		<td>
			<b>Cod. Matriz</b>
		</td>
		<td>
			<b>Cod. Filial</b>
		</td>
		<td>
			<b>Razão Social</b>
		</td>
		<td>
			<b>Nº DP's Vencidas</b>
		</td>
		<td>
			<b>Total Vencido</b>
		</td>
		<td>
			<b>Vencimento mais antigo</b>
		</td>
		<td>
			<b>Responsável</b>
		</td>
	</tr>
  <?
    
    while ($row = pg_fetch_array($sql)) {
		$cnpj = $row["cnpj"];
		$codigo_filial = $row["codigo_filial"];
		$representante = $row["representante"];
		$codigo_matriz = $row["codigo_matriz"];
		$razao_social = $row["razao_social"];
		$endereco = $row["endereco"];
		$cidade = $row["cidade"];
		$estado = $row["estado"];
		$cep = $row["cep"];
		$telefone = $row["telefone"];
		$fax = $row["fax"];
		$id_empresa = $row["id_empresa"];
		
		$total = 0;
		$n_dp = 0;

		// select para calcular valor vencido e buscar vencimento mais antigo por empresa
		$sql2 = "select vencimento,valor_saldo,nota,parcela from tbl_cobranca_nota where id_empresa = $id_empresa and status=0 order by vencimento desc";
		$res2 = pg_exec($con,$sql2);
					while ($row2 = pg_fetch_array($res2)) {

					$vencimento = $row2["vencimento"];
					$total = $total+$row2["valor_saldo"];
					$parcela = $row2["parcela"];
					$n_dp++;
					$nota = $row2["nota"];
			}
?>

	<tr align='left' bgcolor='ffffff'>
		<td>
			<a name="<?=$id_empresa?>"></a><a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$representante?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$codigo_matriz?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$codigo_filial?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$razao_social?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$n_dp?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$total?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$vencimento?></a>
		</td>
		<td>
			<a href="#<?=$id_empresa?>" onclick="javascript: exibe('conteudo<?=$id_empresa?>');" alt='mostra informações adicionais'><?=$vencimento?></a>
		</td>

	</tr>
	<tr align='center' bgcolor='ffffff'>
		<td colspan='8'>
			<div id="conteudo<?=$id_empresa?>" style="display: none;"> 
				<table width='800'  border='0' cellspacing="0" cellpadding="3" class="bordasimples">
					<tr>
						<td colspan='1'>CNPJ</td>
						<td colspan='6' align='left'><?=$cnpj?></td>
						<td colspan='1'>ENDEREÇO</td>
						<td colspan='6' align='left'><?=$endereco?></td>
					</tr>
					<tr>
						<td colspan='1'>CIDADE</td>
						<td colspan='4' align='left'><?=$cidade?></td>
						<td colspan='1'>ESTADO</td>
						<td colspan='3' align='left'><?=$estado?></td>
						<td colspan='1'>CEP</td>
						<td colspan='4' align='left'><?=$cep?></td>
					</tr>
					<tr>
						<td colspan='1'>TELEFONE</td>
						<td colspan='6' align='left'><?=$telefone?></td>
						<td colspan='1'>FAX </td>
						<td colspan='6' align='left'><?=$fax?></td>
					</tr>
					<tr>
						<td colspan='14'>Relação de notas em Aberto</td>
					</tr>
<?
	// busca informações da nota		
	$sql3 = "select filial,especie,serie,nota,banco,nosso_numero,parcela,carteira,emissao,vencimento,valor_original,valor_saldo,despesas from tbl_cobranca_nota where id_empresa ='$id_empresa' and status='0' order by vencimento";
			$res3 = pg_exec($con,$sql3);
			while ($row3 = pg_fetch_array($res3)) {
				$filial = $row3["filial"];
				$especie = $row3["especie"];
				$serie = $row3["serie"];
				$nota = $row3["nota"];
				$banco = $row3["banco"];
				$nosso_numero = $row3["nosso_numero"];
				$parcela = $row3["parcela"];
				$carteira = $row3["carteira"];
				$emissao = $row3["emissao"];
				$vencimento = $row3["vencimento"];
				$valor_original = $row3["valor_original"];
				$valor_saldo = $row3["valor_saldo"];
				$despesas = $row3["despesas"];
				$status = $row3["status"];
?>
					<tr>
						<td>FILIAL</td>
						<td>ESPECIE</td>
						<td>SERIE</td>
						<td>Nº DA NOTA</td>
						<td>BANCO</td>
						<td>NOSSO Nº</td>
						<td>PARCELA</td>
						<td>CARTEIRA</td>
						<td>EMISSÃO</td>
						<td>VENCIMENTO</td>
						<td>VLR ORIG</td>
						<td>VLR SALDO</td>
						<td>DESPESAS</td>
						<td>BAIXAR NOTA</td>
					</tr>			
					<tr>
						<td><?=$filial?></td>
						<td><?=$especie?></td>
						<td><?=$serie?></td>
						<td><?=$nota?></td>
						<td><?=$banco?></td>
						<td><?=$nosso_numero?></td>
						<td><?=$parcela?></td>
						<td><?=$carteira?></td>
						<td><?=$emissao?></td>
						<td><?=$vencimento?></td>
						<td><?=$valor_original?></td>
						<td><?=$valor_saldo?></td>
						<td><?=$despesas?></td>
						<FORM METHOD=POST ACTION="cobranca.php?pg=<?=$pg?>&tipo=<?=$tipo?>&busca=<?=$busca?>&texto=<?=$texto?>&baixar=sim&nota=<?=$nota?>&parcela=<?=$parcela?>&id_empresa=<?=$id_empresa?>"><td><input type='submit' value="excluir" size='10'></td></FORM>
					</tr>
					<tr>
						<td colspan='14'>Histórico</td>
					</tr>
					<?
					// busca histórico da nota
					$sql4 = "select historico,data,usuario from tbl_cobranca_historico where id_empresa='$id_empresa' and parcela='$parcela' and nota='$nota' order by data";
					$res4 = pg_exec($con,$sql4);
					if(pg_numrows($res4)> 0){
						while ($row4 = pg_fetch_array($res4)) {
							$historico = $row4["historico"];
							$data = $row4["data"];
							$usuario = $row4["usuario"]; 

							echo "<tr><td colspan='10' rowspan='2' align='left'>$historico</td><td>Data</td><td colspan='3' align='left'>$data</td></tr><tr><td>Usuário</td><td colspan='3' align='left'>$usuario</td></tr>";
						}
					}
					if ($status==0){
						echo "<FORM METHOD=POST ACTION='cobranca.php?pg=$pg&acao=gravar'>
<INPUT TYPE='hidden' NAME='id_empresa' value='$id_empresa'><INPUT TYPE='hidden' NAME='nota' value='$nota'><INPUT TYPE='hidden' NAME='parcela' value='$parcela'><tr><td colspan='14'><TEXTAREA NAME='historico' ROWS='3' COLS='100'></TEXTAREA></td></tr><tr><td colspan='14'><input type='submit' value='Gravar histórico' size='10'></td></tr></form>";
					}
		
			}
?>
				</table>
			</div>
<?
    }
?>
		</td>
	</tr>
</table>
<br><br>

<?

}

include 'rodape.php';
?>