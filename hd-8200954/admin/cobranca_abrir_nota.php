<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

	$layout_menu = "financeiro";
	$title = "Cobrança";
	include 'cabecalho.php';

$abrir = $_GET["abrir"];
if ($abrir=="sim"){
$nota = $_GET["nota"];
$parcela = $_GET["parcela"];
$id_empresa = $_GET["id_empresa"];

$sql2 = "Update tbl_cobranca_nota set visivel='t' where id_cobranca_nota='$id_cobranca_nota'";
	$res2 = pg_exec($con,$sql2);
}
// fim baixa nota


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
										tbl_cobranca_nota.visivel as visivel, 

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

										where visivel='f'";

$res = pg_exec($con,$sql);
if(pg_numrows($res)< 1){
echo "<br><br>&nbsp;&nbsp;&nbsp;<b>Não existem notas fechadas</b><br><br>";
}else{
echo "<br><br><table border='1' bordercolor='#D9E2EF' style='font-family:Verdana, Arial, Helvetica, sans-serif; font-size:10px;' align='center' cellpadding='3' cellspacing='0'>";
	echo "<tr><td>CNPJ</td>
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
			<td>ABRIR NOTA</td></tr>";

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
		$visivel = $row["visivel"];

$emissao = substr($emissao,8,2) . "/" .substr($emissao,5,2) . "/" . substr($emissao,0,4);
$vencimento = substr($vencimento,8,2) . "/" .substr($vencimento,5,2) . "/" . substr($vencimento,0,4);	

		echo "<tr><td>$cnpj &nbsp;</td>
				<td>$representante&nbsp;</td>
				<td>$codigo_matriz&nbsp;</td>
				<td>$razao_social&nbsp;</td>
				<td>$endereco&nbsp;</td>
				<td>$cidade&nbsp;</td>
				<td>$estado&nbsp;</td>
				<td>$cep&nbsp;</td>
				<td>$telefone &nbsp;</td>
				<td>$fax&nbsp;</td>
				<td>$filial&nbsp;</td>
				<td>$especie&nbsp;</td>
				<td>$serie&nbsp;</td>
				<td>$nota&nbsp;</td>
				<td>$banco&nbsp;</td>
				<td>$nosso_numero&nbsp;</td>
				<td>$parcela&nbsp;</td>
				<td>$carteira&nbsp;</td>
				<td>$emissao&nbsp;</td>
				<td>$vencimento&nbsp;</td>
				<td>$valor_original&nbsp;</td>
				<td>$valor_saldo&nbsp;</td>
				<td>$despesas&nbsp;</td>
				<FORM METHOD=POST ACTION='cobranca_abrir_nota.php?abrir=sim&id_cobranca_nota=$id_cobranca_nota'><td><input type='submit' value='ABRIR NOTA' size='10'></td></FORM></tr>";



   }
   echo "</table><br><br>";
}
include 'rodape.php';
?>
