<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

	$layout_menu = "financeiro";
	$title = "Cobrança";
	include 'cabecalho.php';

$acao = $_GET["acao"];

 
$login = $_POST["login"]; 
	
?>
<br><br>
<FORM METHOD=POST ACTION="cobranca_relatorio.php">
<input type="hidden" name="buscar" value="sim">
&nbsp;&nbsp;&nbsp;<SELECT NAME="busca">
	<OPTION VALUE="data_antiga">data de vencimento mais antiga para mais recente</option>
	<OPTION VALUE="data_recente">data de vencimento mais recente para mais antiga</option>
	<OPTION VALUE="menor_valor">menor saldo em aberto para maior</option>
	<OPTION VALUE="maior_valor">maior saldo em aberto para menor</option>
	<OPTION VALUE="nome">ordem alfabetica da razão social</option>
</SELECT>
<SELECT NAME="historico_busca">
	<OPTION VALUE="mostrar">mostrar histórico</option>
	<OPTION VALUE="nao_mostar">não mostrar histórico</option>
</SELECT>
<INPUT TYPE="submit" value="buscar">
</FORM>

<?



$buscar = $_POST["buscar"];
if ($buscar=="sim"){
			echo "<br><br><b>&nbsp;&nbsp;&nbsp;Relatório de notas ";

$busca = $_POST["busca"];
$status_busca = $_POST["status_busca"];
$historico_busca = $_POST["historico_busca"];
$ordem="";
		if ($busca=="data_antiga"){
			$ordem="order by vencimento";
			echo "com data de vencimento mais antiga para mais recente";
		}
		if ($busca=="data_recente"){
			$ordem="order by vencimento desc";
			echo "data de vencimento mais recente para mais antiga";
		}
		if ($busca=="menor_valor"){
			$ordem="order by valor_saldo";
			echo "com menor saldo em aberto para maior";
		}
		if ($busca=="maior_valor"){
			$ordem="order by valor_saldo desc";
			echo "com maior saldo em aberto para menor";
		}
		if ($busca=="nome"){
			$ordem="order by nome";
			echo "por ordem alfabetica da razão social";
		}
		if ($historico_busca=="mostrar"){
			echo ", mostrando o histórico";
		}


	echo "</b><br><br><table border='1' bordercolor='#D9E2EF' style='font-family:Verdana, Arial, Helvetica, sans-serif; font-size:10px;' align='center' cellpadding='3' cellspacing='0'><tr><td>CNPJ</td><td>REPRESENTANTE</td><td>COD. MATRIZ</td><td>RAZÃO SOCIAL</td><td>ENDEREÇO</td><td>CIDADE</td><td>ESTADO</td><td>CEP</td><td>TELEFONE</td><td>FAX </td><td>FILIAL</td><td>ESPECIE</td><td>SERIE</td><td>Nº DA NOTA</td><td>BANCO</td><td>NOSSO Nº</td><td>PARCELA</td><td>CARTEIRA</td><td>EMISSÃO</td><td>VENCIMENTO</td><td>VLR ORIG</td><td>VLR SALDO</td><td>DESPESAS</td></tr>";

$sql = "SELECT 
										tbl_cobranca_nota.nota as nota, 
										tbl_cobranca_nota.filial as filial, 
										tbl_cobranca_nota.representante as representante, 
										tbl_cobranca_nota.especie as especie, 
										tbl_cobranca_nota.serie as serie, 
										tbl_cobranca_nota.banco as banco, 
										tbl_cobranca_nota.nosso_numero as nosso_numero, 
										tbl_cobranca_nota.parcela as parcela, 
										tbl_cobranca_nota.carteira as carteira, 
										tbl_cobranca_nota.emissao as emissao, 
										tbl_cobranca_nota.vencimento as vencimento, 
										tbl_cobranca_nota.valor_original as valor_original, 
										tbl_cobranca_nota.valor_saldo as valor_saldo, 
										tbl_cobranca_nota.valor_despesas as valor_despesas, 
										tbl_cobranca_nota.posto as posto ,
										tbl_cobranca_nota.id_cobranca_nota as id_cobranca_nota, 
									

										tbl_posto.posto as posto,
										tbl_posto.cnpj as cnpj, 
										tbl_posto.nome as nome, 
										tbl_posto.endereco as endereco, 
										tbl_posto.cidade as cidade, 
										tbl_posto.estado as estado, 
										tbl_posto.cep as cep, 
										tbl_posto.fone as fone, 
										tbl_posto.fax as fax, 

										tbl_posto_fabrica.codigo_posto as codigo_posto 

										from tbl_cobranca_nota

										join tbl_posto on tbl_cobranca_nota.posto=tbl_posto.posto 

										join tbl_posto_fabrica on tbl_cobranca_nota.posto=tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=3  
										
										$ordem";

$res = pg_exec($con,$sql);
   while ($row = pg_fetch_array($res)) {
		$cnpj = $row["cnpj"];
		$representante = $row["representante"];
		$codigo_matriz = $row["codigo_posto"];
		$razao_social = $row["nome"];
		$endereco = $row["endereco"];
		$cidade = $row["cidade"];
		$estado = $row["estado"];
		$cep = $row["cep"];
		$telefone = $row["fone"];
		$fax = $row["fax"];
		$id_cobranca_nota = $row["id_cobranca_nota"];
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
		$despesas = $row["valor_despesas"];
		$posto = $row["posto"];

$emissao = substr($emissao,8,2) . "/" .substr($emissao,5,2) . "/" . substr($emissao,0,4);
$vencimento = substr($vencimento,8,2) . "/" .substr($vencimento,5,2) . "/" . substr($vencimento,0,4);	

		echo "<tr><td>&nbsp;$cnpj</td>
		<td>&nbsp;$representante</td>
		<td>&nbsp;$codigo_matriz</td>
		<td>&nbsp;$razao_social</td>
		<td>&nbsp;$endereco</td>
		<td>&nbsp;$cidade</td>
		<td>&nbsp;$estado</td>
		<td>&nbsp;$cep</td>
		<td>&nbsp;$telefone</td>
		<td>&nbsp;$fax</td>
		<td>&nbsp;$filial</td>
		<td>&nbsp;$especie</td>
		<td>&nbsp;$serie</td>
		<td>&nbsp;$nota</td>
		<td>&nbsp;$banco</td>
		<td>&nbsp;$nosso_numero</td>
		<td>&nbsp;$parcela</td>
		<td>&nbsp;$carteira</td>
		<td>&nbsp;$emissao</td>
		<td>&nbsp;$vencimento</td>
		<td>&nbsp;$valor_original</td>
		<td>&nbsp;$valor_saldo</td>
		<td>&nbsp;$despesas</td></tr>";


		if ($historico_busca=="mostrar"){

			$sql3 = "select historico,data_digitacao,id_historico  from tbl_cobranca_historico where posto=$posto";
			$res3 = pg_exec($con,$sql3);
			if(pg_numrows($res3)> 0){
				while ($row3 = pg_fetch_array($res3)) {
					$historico = $row3["historico"];
					$data = $row3["data_digitacao"];
					$id_historico = $row3["id_historico"]; 

					if ($usuario<>""){
					$sql = "SELECT admin FROM tbl_cobranca_historico WHERE id_historico = $id_historico";
							$res = pg_exec($con,$sql);					
							if(pg_numrows($res)> 0){
								$adminddd=pg_result($res,0,admin);
							}
					}
					if ($usuario<>""){
					$sql = "SELECT login FROM tbl_admin WHERE admin = $adminddd";
							$res = pg_exec($con,$sql);					
							if(pg_numrows($res)> 0){
								$nome_usuario=pg_result($res,0,login);
							}
					}

$data = substr($data,8,2) . "/" .substr($data,5,2) . "/" . substr($data,0,4) . "&nbsp;&nbsp;&nbsp;" . substr($data,11,8) ;


					echo "<tr><td colspan='2'>Histórico</td><td colspan='14'>$historico</td><td colspan='2'>Data</td><td colspan='2'>$data</td><td colspan='2'>Usuário</td><td colspan='2'>$usuario</td></tr>";
				}
			}
		}

   }
   echo "</table><br><br>";
}

include 'rodape.php';
?>
