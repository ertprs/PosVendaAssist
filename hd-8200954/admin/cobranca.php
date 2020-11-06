<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

	$layout_menu = "financeiro";
	$title = "Cobrança";
	include 'cabecalho.php';
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

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
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


function janela (URL){
   window.open(URL,"_blank","width=1024,height=768,scrollbars=yes, Menubar=no, Directories=no, Top=0,left=0")
}


</script>
<br>


<?



// selecio o tipo de exibição
$tipo = $_POST["tipo"];
$busca = $_POST["busca"];
$qtd_resultados= $_POST["qtd_resultados"];


if ($tipo==""){
$tipo = $_GET["tipo"];
$busca = $_GET["busca"];
$qtd_resultados= $_GET["qtd_resultados"];

}

if ($tipo=="select"){

	// exibição por razão social em ordem alfabética 
	if ($busca=="nome"){

		// dados Paginação
			$numreg = $qtd_resultados; // Quantos registros por página vai ser mostrado
				$pg = $_GET["pg"];
			if (!isset($pg)) {
				$pg = 0;
			}
			
			$inicial = $pg * $numreg;
			
		// FIM dados Paginação
		
		// Faz o Select pegando o registro inicial até a quantidade de registros para página
		$sql = pg_query("SELECT distinct
								
								tbl_posto.posto as posto ,
								tbl_posto.cnpj as cnpj, 
								tbl_posto.nome as nome, 
								tbl_posto.endereco as endereco, 
								tbl_posto.cidade as cidade, 
								tbl_posto.estado as estado, 
								tbl_posto.cep as cep, 
								tbl_posto.fone as fone, 
								tbl_posto.fax as fax, 
								
								tbl_cobranca_retorno.data_retorno as data_retorno,
								tbl_cobranca_retorno.retorno_status as retorno_status,

								tbl_posto_fabrica.codigo_posto as codigo_posto 

								from tbl_posto  

								join tbl_posto_fabrica on tbl_posto.posto=tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=3 

								join tbl_cobranca_retorno on tbl_posto.posto=tbl_cobranca_retorno.posto 

								join tbl_cobranca_nota on  tbl_cobranca_nota.visivel='t' and tbl_cobranca_nota.posto=tbl_posto.posto 

								where tbl_cobranca_nota.especie<>'AN' and tbl_cobranca_nota.especie<>'NC' and tbl_cobranca_nota.especie<>'DT' and tbl_cobranca_nota.especie<>'AT' and tbl_cobranca_nota.especie<>'JR'

								order by nome 

								LIMIT $numreg OFFSET $inicial");

		// Serve para contar quantos registros você tem na seua tabela para fazer a paginação
		$sql_conta = pg_query("SELECT distinct tbl_posto.posto FROM tbl_posto join tbl_cobranca_retorno on tbl_posto.posto=tbl_cobranca_retorno.posto join tbl_cobranca_nota on  tbl_cobranca_nota.visivel='t' and tbl_cobranca_nota.posto=tbl_posto.posto where tbl_cobranca_nota.especie<>'AN' and tbl_cobranca_nota.especie<>'NC' and tbl_cobranca_nota.especie<>'DT' and tbl_cobranca_nota.especie<>'AT' and tbl_cobranca_nota.especie<>'JR'");
		
		$quantreg = pg_num_rows($sql_conta); // Quantidade de registros pra paginação
		
		include("cobranca_paginacao.php"); // Chama o arquivo que monta a paginação. ex: << anterior 1 2 3 4 5 próximo >>
  

  ?>
<br><br>
<TABLE  width='700px' align='center' border='0' cellspacing="1" cellpadding="3" class="tabela">
	<tr class='titulo_coluna'>

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
			<b>Data de Retorno</b>
		</td>
		<td>
			<b>Cotatado</b>
		</td>
	</tr>
  <?
    
    while ($row = pg_fetch_array($sql)) {

		$cnpj = $row["cnpj"];
		$codigo_matriz = $row["codigo_posto"];
		$razao_social = $row["nome"];
		$endereco = $row["endereco"];
		$cidade = $row["cidade"];
		$estado = $row["estado"];
		$cep = $row["cep"];
		$telefone = $row["fone"];
		$fax = $row["fax"];
		$posto = $row["posto"];
		$total = 0;
		$n_dp = 0;
		$data_retorno = $row["data_retorno"];
		$retorno_status = $row["retorno_status"];

		if ($data_retorno<>""){
		$data_retorno = substr($data_retorno,8,2) . "/" .substr($data_retorno,5,2) . "/" . substr($data_retorno,0,4);
		}

		// select para calcular valor vencido e buscar vencimento mais antigo por empresa
		$sql2 = "select filial,representante,vencimento,valor_saldo,nota,parcela from tbl_cobranca_nota where posto = $posto and visivel='t' and tbl_cobranca_nota.especie<>'AN' and tbl_cobranca_nota.especie<>'NC' and tbl_cobranca_nota.especie<>'DT' and tbl_cobranca_nota.especie<>'AT' and tbl_cobranca_nota.especie<>'JR' order by vencimento desc";
		$res2 = pg_exec($con,$sql2);
					while ($row2 = pg_fetch_array($res2)) {

					$vencimento = $row2["vencimento"];
					$total = $total+$row2["valor_saldo"];
					$parcela = $row2["parcela"];
					$n_dp++;
					$nota = $row2["nota"];
					$filial = $row2["filial"];
					$representante = $row2["representante"];
			}
$vencimento = substr($vencimento,8,2) . "/" .substr($vencimento,5,2) . "/" . substr($vencimento,0,4);

?>

	<tr align='left' bgcolor='ffffff'>
		<td>
			<a name="<?=$posto?>"></a><a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$representante?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$codigo_matriz?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$filial?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$razao_social?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$n_dp?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$total?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$vencimento?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$data_retorno?></a>
		</td>
		<td>
			<INPUT TYPE="checkbox" NAME="contatado" <? if ($retorno_status=='t'){echo "checked";}?>>
		</td>

	</tr>
<?
    }
?>
</table>
<br><br>
<?
	// fim exibição por razão social em ordem alfabética 
	}else{
	// exibição por posto com valor em aberto valor


		// dados Paginação
			$numreg = $qtd_resultados; // Quantos registros por página vai ser mostrado
				$pg = $_GET["pg"];
			if (!isset($pg)) {
				$pg = 0;
			}
			$inicial = $pg * $numreg;
		// FIM dados Paginação
		
		// Faz o Select pegando o registro inicial até a quantidade de registros para página
		$sql = pg_query("SELECT distinct tbl_cobranca_nota.posto,count(tbl_cobranca_nota.nota)as n_dp,  SUM(tbl_cobranca_nota.valor_saldo) AS total from tbl_cobranca_nota join tbl_cobranca_retorno on tbl_cobranca_nota.posto = tbl_cobranca_retorno.posto where tbl_cobranca_nota.especie<>'AN' and tbl_cobranca_nota.especie<>'NC' and tbl_cobranca_nota.especie<>'DT' and tbl_cobranca_nota.especie<>'AT' and tbl_cobranca_nota.especie<>'JR' GROUP BY tbl_cobranca_nota.posto order by total desc LIMIT $numreg OFFSET $inicial ");

		// Serve para contar quantos registros você tem na seua tabela para fazer a paginação
		$sql_conta = pg_query("SELECT distinct tbl_cobranca_retorno.posto FROM tbl_cobranca_retorno join tbl_cobranca_nota on  tbl_cobranca_nota.visivel='t' and tbl_cobranca_nota.posto=tbl_cobranca_retorno.posto where tbl_cobranca_nota.especie<>'AN' and tbl_cobranca_nota.especie<>'NC' and tbl_cobranca_nota.especie<>'DT' and tbl_cobranca_nota.especie<>'AT' and tbl_cobranca_nota.especie<>'JR'");
		
		$quantreg = pg_num_rows($sql_conta); // Quantidade de registros pra paginação
		
		include("cobranca_paginacao.php"); // Chama o arquivo que monta a paginação. ex: << anterior 1 2 3 4 5 próximo >>

		

?>
<br><br>
<TABLE  width='100%' align='center' border='0' cellspacing="1" cellpadding="1" class="tabela">
	<tr align='center' >

	<tr class='titulo_coluna'>
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
			<b>Data de Retorno</b>
		</td>
		<td>
			<b>Cotado</b>
		</td>
	</tr>
<?
	
		$i = 0;
		while ($row4 = pg_fetch_array($sql)) {

			$posto = $row4["posto"];
			$total = $row4["total"];
			$n_dp = $row4["n_dp"];

				$sql_nota = "SELECT 
							tbl_cobranca_nota.vencimento as vencimento, 
							tbl_cobranca_nota.filial as filial,
							tbl_cobranca_nota.representante as representante,

							tbl_posto.nome as nome, 

							tbl_cobranca_retorno.retorno_status  as retorno_status,

							tbl_posto_fabrica.codigo_posto as codigo_posto 

							from tbl_cobranca_nota

							join tbl_posto on tbl_posto.posto=tbl_cobranca_nota.posto 

							join tbl_cobranca_retorno on tbl_posto.posto=tbl_cobranca_retorno.posto 

							join tbl_posto_fabrica on tbl_cobranca_nota.posto=tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=3  
							where tbl_posto.posto=$posto 

							order by vencimento desc";

					$res = pg_exec($con,$sql_nota);
					while ($row2 = pg_fetch_array($res)) {

					$vencimento = $row2["vencimento"];
					$filial = $row2["filial"];
					$razao_social = $row2["nome"];
					$codigo_matriz = $row2["codigo_posto"];
					$representante = $row2["representante"];
					$retorno_status = $row2["retorno_status"];
				

				
$vencimento = substr($vencimento,8,2) . "/" .substr($vencimento,5,2) . "/" . substr($vencimento,0,4);		
					
					}
		$sql5 = "SELECT data_retorno FROM tbl_cobranca_retorno where posto=$posto";
		$res5 = pg_exec($con,$sql5);					

		if(pg_numrows($res5)> 0){
			$data_retorno=pg_result($res5,0,data_retorno);
		}
		if ($data_retorno<>""){
		$data_retorno = substr($data_retorno,8,2) . "/" .substr($data_retorno,5,2) . "/" . substr($data_retorno,0,4);
		}	

		if($i % 2==0)
			$cor = "#F7F5F0";
		else
			$cor = "#F1F4FA";
		$i++;
?>
<tr align='left' bgcolor='<?php echo $cor; ?>'>
		<td>
			<a name="<?=$posto?>"></a><a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$representante?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$codigo_matriz?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$filial?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$razao_social?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$n_dp?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$total?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$vencimento?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$data_retorno?></a>
		</td>
		<td>
			<INPUT TYPE="checkbox" NAME="contatado" <? if ($retorno_status=='t'){echo "checked";}?>>
		</td>

	</tr>

			
<?
		}
?>

</table>
<br><br>
<?
		// exibição por posto com valor em aberto valor
	}

}else{

$texto = $_POST["texto_busca"];

if ($texto==""){
$texto = $_GET["texto_busca"];
}

//busca por CNPJ ou Razão Social

if ($busca=="cnpj"){
$like=" where cnpj LIKE '%$texto%' ";
}
if($busca=="nome"){
$like=" where tbl_posto.nome ILIKE '%$texto%' ";
}
if($busca=="codigo_posto"){
$like=" where codigo_posto LIKE '%$texto%' ";
}

		// dados Paginação
			$numreg = $qtd_resultados; // Quantos registros por página vai ser mostrado
				$pg = $_GET["pg"];
			if (!isset($pg)) {
				$pg = 0;
			}
			
			$inicial = $pg * $numreg;
			
		// FIM dados Paginação
		
		// Faz o Select pegando o registro inicial até a quantidade de registros para página
		$sql = pg_query("select distinct								
						tbl_posto.posto as posto ,
						tbl_posto.cnpj as cnpj, 
						tbl_posto.nome as nome, 
						tbl_posto.endereco as endereco, 
						tbl_posto.cidade as cidade, 
						tbl_posto.estado as estado, 
						tbl_posto.cep as cep, 
						tbl_posto.fone as fone, 
						tbl_posto.fax as fax, 

						tbl_cobranca_retorno.data_retorno as data_retorno,
						tbl_cobranca_retorno.retorno_status as retorno_status,

						tbl_posto_fabrica.codigo_posto as codigo_posto 

						from tbl_posto

						join tbl_cobranca_retorno on tbl_posto.posto=tbl_cobranca_retorno.posto 

						join tbl_posto_fabrica on tbl_posto.posto=tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=3 
 
						join tbl_cobranca_nota on tbl_cobranca_nota.posto=tbl_posto.posto 
						
						$like LIMIT $numreg OFFSET $inicial");

		// Serve para contar quantos registros você tem na seua tabela para fazer a paginação
		$sql_conta = pg_query("select distinct
	
							tbl_posto.posto as posto 
								
								from tbl_posto  

								join tbl_posto_fabrica on tbl_posto.posto=tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=3 

								join tbl_cobranca_retorno on tbl_posto.posto=tbl_cobranca_retorno.posto 

								join tbl_cobranca_nota on tbl_cobranca_nota.posto=tbl_posto.posto 


							$like ");

		$quantreg = pg_num_rows($sql_conta); // Quantidade de registros pra paginação
		
		include("cobranca_paginacao.php"); // Chama o arquivo que monta a paginação. ex: << anterior 1 2 3 4 5 próximo >>
  

  ?>
<br><br>
<TABLE  width='100%' align='center' border='0' cellspacing="1" cellpadding="3" class="tabela">
	<tr>

	<tr class='titulo_coluna'>
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
			<b>Data retorno</b>
		</td>
		<td>
			<b>Cotatado</b>
		</td>
	</tr>
  <?
    $i = 0;
    while ($row = pg_fetch_array($sql)) {
		$cnpj = $row["cnpj"];
		$codigo_matriz = $row["codigo_posto"];
		$razao_social = $row["nome"];
		$endereco = $row["endereco"];
		$cidade = $row["cidade"];
		$estado = $row["estado"];
		$cep = $row["cep"];
		$telefone = $row["fone"];
		$fax = $row["fax"];
		$posto = $row["posto"];
		$data_retorno = $row["data_retorno"];
		$retorno_status = $row["retorno_status"];

		if ($data_retorno<>""){
		$data_retorno = substr($data_retorno,8,2) . "/" .substr($data_retorno,5,2) . "/" . substr($data_retorno,0,4);
		}
		$total = 0;
		$n_dp = 0;

		
		$sql2 = "select filial,representante,nota,vencimento,parcela from tbl_cobranca_nota where posto = $posto and visivel='t' ";
		$res2 = pg_exec($con,$sql2);
					while ($row2 = pg_fetch_array($res2)) {

					$codigo_filial = $row2["filial"];
					$representante = $row2["representante"];
					$parcela = $row2["parcela"];
					$nota = $row2["nota"];
					$vencimento = $row2["vencimento"];

			}

		// select para calcular valor vencido e buscar vencimento mais antigo por empresa
		$sql2 = "select vencimento,valor_saldo from tbl_cobranca_nota where posto = $posto and tbl_cobranca_nota.especie<>'AN' and tbl_cobranca_nota.especie<>'NC' and tbl_cobranca_nota.especie<>'DT' and tbl_cobranca_nota.especie<>'AT' and tbl_cobranca_nota.especie<>'JR' order by vencimento desc";
		$res2 = pg_exec($con,$sql2);
					while ($row2 = pg_fetch_array($res2)) {
					$vencimento = $row2["vencimento"];
					$total = $total+$row2["valor_saldo"];
					$n_dp++;

			}

		$vencimento = substr($vencimento,8,2) . "/" .substr($vencimento,5,2) . "/" . substr($vencimento,0,4);

		if($i % 2==0)
			$cor = "#F7F5F0";
		else
			$cor = "#F1F4FA";
		$i++;
?>

	<tr align='left' bgcolor='ffffff'>
		<td>
			<a name="<?=$posto?>"></a><a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$representante?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$codigo_matriz?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$codigo_filial?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$razao_social?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$n_dp?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$total?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$vencimento?></a>
		</td>
		<td>
			<a href="javascript:janela('cobranca_tela.php?posto=<?=$posto?>')"alt='mostra informações adicionais'><?=$data_retorno?></a>
		</td>
		<td>
			<INPUT TYPE="checkbox" NAME="contatado" <? if ($retorno_status=='t'){echo "checked";}?>>
		</td>

	</tr>
<?
    }
?>
</table>
<br><br>

<?

}

include 'rodape.php';
?>