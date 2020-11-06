<? 
include 'menu.php';
if ($logado==""){header("Location: index.php"); }
include 'banco.php';

$abrir = $_GET["abrir"];
if ($abrir=="sim"){
$nota = $_GET["nota"];
$parcela = $_GET["parcela"];
$id_empresa = $_GET["id_empresa"];

$sql2 = "Update tbl_cobranca_nota set status=0 where id_empresa='$id_empresa' and nota='$nota' and parcela='$parcela'";
	$res2 = pg_exec($con,$sql2);
}
// fim baixa nota


$sql = "select tbl_cobranca_empresa.id_empresa as id_empresa, tbl_cobranca_empresa.cnpj as cnpj, tbl_cobranca_empresa.codigo_filial as codigo_filial, tbl_cobranca_empresa.representante as representante, tbl_cobranca_empresa.codigo_matriz as codigo_matriz, tbl_cobranca_empresa.razao_social as razao_social, tbl_cobranca_empresa.endereco as endereco, tbl_cobranca_empresa.cidade as cidade, tbl_cobranca_empresa.estado as estado, tbl_cobranca_empresa.cep as cep, tbl_cobranca_empresa.telefone as telefone, tbl_cobranca_empresa.fax as fax, tbl_cobranca_nota.filial as filial, tbl_cobranca_nota.especie as especie, tbl_cobranca_nota.serie as serie, tbl_cobranca_nota.nota as nota, tbl_cobranca_nota.banco as banco, tbl_cobranca_nota.nosso_numero as nosso_numero, tbl_cobranca_nota.parcela as parcela, tbl_cobranca_nota.carteira as carteira, tbl_cobranca_nota.emissao as emissao, tbl_cobranca_nota.vencimento as vencimento, tbl_cobranca_nota.valor_original as valor_original, tbl_cobranca_nota.valor_saldo as valor_saldo, tbl_cobranca_nota.despesas as despesas, tbl_cobranca_nota.status as status from tbl_cobranca_empresa join tbl_cobranca_nota on tbl_cobranca_empresa.id_empresa=tbl_cobranca_nota.id_empresa  where status=1";

$res = pg_exec($con,$sql);
if(pg_numrows($res)< 1){
echo "<br><br>&nbsp;&nbsp;&nbsp;<b>Não existem notas fechadas</b><br><br>";
}else{
echo "<br><br><table border='1' bordercolor='#D9E2EF' style='font-family:Verdana, Arial, Helvetica, sans-serif; font-size:10px;' align='center' cellpadding='3' cellspacing='0'>";
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
						<td>DESPESAS</td>
						<td>ABRIR NOTA</td></tr>
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
		<td>$despesas</td>
		<FORM METHOD=POST ACTION='abrir_nota.php?abrir=sim&nota=$nota&parcela=$parcela&id_empresa=$id_empresa'><td><input type='submit' value='ABRIR NOTA' size='10'></td></FORM></tr>";



   }
   echo "</table><br><br>";
}
include 'rodape.php';
?>
