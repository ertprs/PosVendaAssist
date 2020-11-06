<? 
include 'menu.php';
if ($logado==""){header("Location: index.php"); }
include 'banco.php';

$acao = $_GET["acao"];

 
$login = $_POST["login"]; 
	
?>
<br><br>
<FORM METHOD=POST ACTION="relatorio.php">
<input type="hidden" name="buscar" value="sim">
&nbsp;&nbsp;&nbsp;<SELECT NAME="busca">
	<OPTION VALUE="data_antiga">data de vencimento mais antiga para mais recente</option>
	<OPTION VALUE="data_recente">data de vencimento mais recente para mais antiga</option>
	<OPTION VALUE="menor_valor">menor saldo em aberto para maior</option>
	<OPTION VALUE="maior_valor">maior saldo em aberto para menor</option>
	<OPTION VALUE="nome">ordem alfabetica da razão social</option>
</SELECT>
<SELECT NAME="status_busca">
	<OPTION VALUE="aberto">notas em aberto</option>
	<OPTION VALUE="fechada">notas fechadas</option>
	<OPTION VALUE="todas">todas as notas</option>
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
			echo "Relatório de notas ";

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
		if ($busca=="menor_valor"){
			$ordem="order by razao_social";
			echo "por ordem alfabetica da razão social";
		}
		if ($status_busca=="aberto"){
			$ordem="where tbl_cobranca_nota.status=0 ".$ordem;
			echo ", com notas em aberto";
		}
		if ($status_busca=="fechada"){
			$ordem="where tbl_cobranca_nota.status=1 ".$ordem;
			echo ", com notas fechadas";
		}
		if ($status_busca=="todas"){
			echo ", com todas notas, abertas e fechadas";
		}
		if ($historico_busca=="mostrar"){
			echo ", mostrando o histórico";
		}


echo "<br><br><table border='1' bordercolor='#D9E2EF' style='font-family:Verdana, Arial, Helvetica, sans-serif; font-size:10px;' align='center' cellpadding='3' cellspacing='0'>";
$sql = "select tbl_cobranca_empresa.id_empresa as id_empresa, tbl_cobranca_empresa.cnpj as cnpj, tbl_cobranca_empresa.codigo_filial as codigo_filial, tbl_cobranca_empresa.representante as representante, tbl_cobranca_empresa.codigo_matriz as codigo_matriz, tbl_cobranca_empresa.razao_social as razao_social, tbl_cobranca_empresa.endereco as endereco, tbl_cobranca_empresa.cidade as cidade, tbl_cobranca_empresa.estado as estado, tbl_cobranca_empresa.cep as cep, tbl_cobranca_empresa.telefone as telefone, tbl_cobranca_empresa.fax as fax, tbl_cobranca_nota.filial as filial, tbl_cobranca_nota.especie as especie, tbl_cobranca_nota.serie as serie, tbl_cobranca_nota.nota as nota, tbl_cobranca_nota.banco as banco, tbl_cobranca_nota.nosso_numero as nosso_numero, tbl_cobranca_nota.parcela as parcela, tbl_cobranca_nota.carteira as carteira, tbl_cobranca_nota.emissao as emissao, tbl_cobranca_nota.vencimento as vencimento, tbl_cobranca_nota.valor_original as valor_original, tbl_cobranca_nota.valor_saldo as valor_saldo, tbl_cobranca_nota.despesas as despesas, tbl_cobranca_nota.status as status from tbl_cobranca_empresa join tbl_cobranca_nota on tbl_cobranca_empresa.id_empresa=tbl_cobranca_nota.id_empresa $ordem";

$res = pg_exec($con,$sql);
   while ($row = pg_fetch_array($res)) {
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


		echo "			<tr><td>CNPJ</td>
						<td>COD. FILIAL</td>
						<td>REPRESENTANTE</td>
						<td>COD. MATRIZ</td>
						<td>RAZÃO SOCIAL</td>
						<td>ENDEREÇO</td>
						<td>CIDADE</td>
						<td>ESTADO</td>
						<td>CEP</td>
						<td>TELEFONE</td>
						<td>FAX </td>
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
						<td>DESPESAS</td></tr>
		<tr><td>$cnpj</td>
		<td>$codigo_filial</td>
		<td>$representante</td>
		<td>$codigo_matriz</td>
		<td>$razao_social</td>
		<td>$endereco</td>
		<td>$cidade</td>
		<td>$estado</td>
		<td>$cep</td>
		<td>$telefone</td>
		<td>$fax</td>
		<td>$filial</td>
		<td>$especie</td>
		<td>$serie</td>
		<td>$nota</td>
		<td>$banco</td>
		<td>$nosso_numero</td>
		<td>$parcela</td>
		<td>$carteira</td>
		<td>$emissao</td>
		<td>$vencimento</td>
		<td>$valor_original</td>
		<td>$valor_saldo</td>
		<td>$despesas</td></tr>";


		if ($historico_busca=="mostrar"){

			$sql3 = "select historico,data,usuario from tbl_cobranca_historico where id_empresa='$id_empresa' and parcela='$parcela' and nota='$nota' order by data";
			$res3 = pg_exec($con,$sql3);
			if(pg_numrows($res3)> 0){
				while ($row3 = pg_fetch_array($res3)) {
					$historico = $row3["historico"];
					$data = $row3["data"];
					$usuario = $row3["usuario"]; 

					echo "<tr><td colspan='2'>Histórico</td><td colspan='14'>$historico</td><td colspan='2'>Data</td><td colspan='2'>$data</td><td colspan='2'>Usuário</td><td colspan='2'>$usuario</td></tr>";
				}
			}
		}

   }
   echo "</table><br><br>";
}

include 'rodape.php';
?>
