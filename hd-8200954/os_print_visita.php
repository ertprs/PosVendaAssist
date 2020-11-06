<?php

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if ($areaAdmin === true) {
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/admin/autentica_admin.php';
} else {
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/autentica_usuario.php';
}

$os = $_GET['os'];

if (!empty($os)) {
	if ($areaAdmin !== true) {
		$wherePosto = "AND tbl_os.posto = {$login_posto}";
	}

	if($login_fabrica == 145){
		$complemento_sql = "LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda.cidade
							LEFT JOIN tbl_solucao on tbl_solucao.solucao = tbl_os.solucao_os";
		$complemento_campos = "tbl_cidade.nome as nome_cidade_revenda,
                				tbl_cidade.estado as sigla_estado_revenda,
                				tbl_solucao.descricao as descricao_solucao,";
	}

	 $sql = "SELECT
                tbl_os.sua_os,
                tbl_os.nota_fiscal,
                TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
                TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY')       AS data_nota_fiscal,
                tbl_tipo_atendimento.descricao AS tipo_atendimento,
                tbl_os.qtde_km,
                tbl_os.obs AS observacao,
                tbl_os.consumidor_revenda,
                tbl_revenda.*,
				$complemento_campos
                tbl_produto.referencia   AS produto_referencia,
                tbl_produto.descricao    AS produto_descricao,
                tbl_produto.voltagem     AS produto_voltagem,
                tbl_os.aparencia_produto AS aparencia,
                tbl_os.defeito_reclamado_descricao AS defeito_reclamado,
                tbl_defeito_constatado.descricao AS defeito_constatado,
                tbl_os.acessorios,
                tbl_posto.nome AS posto_razao_social,
                tbl_posto.cnpj AS posto_cnpj,
                tbl_posto_fabrica.contato_fone_comercial AS posto_fone,
                tbl_os.revenda_nome,
                tbl_os.consumidor_nome,
                tbl_os.consumidor_cpf AS consumidor_cpf_cnpj,
                tbl_os.consumidor_cep,
                tbl_os.consumidor_estado,
                tbl_os.consumidor_cidade,
                tbl_os.consumidor_bairro,
                tbl_os.consumidor_endereco,
                tbl_os.consumidor_numero,
                tbl_os.consumidor_complemento,
                tbl_os.consumidor_fone AS consumidor_telefone,
                tbl_os.consumidor_email
            FROM tbl_os
            LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
            INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
            LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            INNER JOIN tbl_revenda ON tbl_os.revenda = tbl_revenda.revenda
            $complemento_sql
            WHERE tbl_os.fabrica = {$login_fabrica}
            AND tbl_os.os = {$os}
            {$wherePosto}";
			// echo nl2br($sql);
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$result = pg_fetch_all($res);
		extract($result[0]);
	}

	if ($consumidor_revenda == 'R') { // HD 2507947: mostrar dados da revenda
		$consumidor_nome        = (strlen($contato)) ? $contato : $nome;
		$consumidor_cpf_cnpj    = $cnpj;
		$consumidor_cep         = $cep;
		$consumidor_estado      = $sigla_estado_revenda;
		$consumidor_cidade      = $nome_cidade_revenda;
		$consumidor_bairro      = $bairro;
		$consumidor_endereco    = $endereco;
		$consumidor_numero      = $numero;
		$consumidor_complemento = $complemento;
		$consumidor_telefone    = $fone;
		$consumidor_email       = $email;

		$tipo_consumidor_revenda = " da Revenda";
	}else{
		$tipo_consumidor_revenda = " do Cliente";
	}
}

function formata_cpf_cnpj($cpf_cnpj) {
	$cpf_cnpj = preg_replace('/\D/', '', $cpf_cnpj);
    if (strlen($cpf_cnpj) == 10 or strlen($cpf_cnpj) == 13)
        $cpf_cnpj = "0$cpf_cnpj";

    $reMatch  = (strlen($cpf_cnpj)==14) ? '(\d\d)(\d{3})(\d{3})(\d{4})(\d\d)' : '(\d{3})(\d{3})(\d{3})(\d\d)';
    $reFormat = (strlen($cpf_cnpj)==14) ? '$1.$2.$3/$4-$5' : '$1.$2.$3-$4';
    return preg_replace("/$reMatch/", $reFormat, $cpf_cnpj);
}

/* Peças da OS */
$sqlComp = "SELECT tbl_peca.referencia || ' - ' || tbl_peca.descricao AS componente,
                   tbl_os_item.qtde,
                   tbl_servico_realizado.descricao AS servico
              FROM tbl_os_item
              JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
              JOIN tbl_os         ON tbl_os.os        = tbl_os_produto.os
                                  AND tbl_os.fabrica  = $login_fabrica
              JOIN tbl_peca       ON tbl_peca.peca    = tbl_os_item.peca
                                 AND tbl_peca.fabrica = $login_fabrica
              JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                                        AND tbl_servico_realizado.fabrica = {$login_fabrica}
             WHERE tbl_os.os = $os";
$componentes = pg_query($con, $sqlComp);

switch ($login_fabrica) {
    case '145':
        $logo_fabrica = "logos/fabrimar_print.jpg";
    break;
}

?>

<!DOCTYPE html>
<html>
<head>
	<link type="text/css" rel="stylesheet" media="all" href="bootstrap/css/bootstrap.css" />
	<link type="text/css" rel="stylesheet" media="all" href="bootstrap/css/extra.css" />

	<style type="text/css">

	.titulo_tabela {
		font-weight: bold;
		background-color: #CACACA;
	}

	.box-print {
		width: 210mm;
		font-size: 12px;
		margin: 0 auto;
		page-break-after: always;
	}

	table {
		width: 100%;
	}
	</style>

	<script>

	window.addEventListener("load", function() {
		var segunda_via = document.getElementsByClassName("box-print")[0].cloneNode(true);
		<?php if($login_fabrica != 145){?>
			document.body.appendChild(segunda_via);
		<?php }?>
		window.print();
	});
	/*
	The pageBreakInside property is supported in all major browsers.
	Note: Firefox, Chrome, and Safari do not support the property value "avoid".
	*/
	document.getElementById("footer").style.pageBreakInside = "auto";

	</script>
</head>
<body>
	<div class="box-print" >

		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;" >
			<tr>
				<td class='tac'>
					<img src="<?=$logo_fabrica;?>" style="max-height:80px;max-width:210px;" border="0">
				</td>
			</tr>
		</table>

		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;" >
			<tr>
				<td><b>Ordem de Serviço:</b> <?=$sua_os?></td>
				<td><b>Data Abertura:</b> <?=$data_abertura?></td>
				<td><b>Tipo Atendimento:</b> <?=$tipo_atendimento?></td>
				<td><b>Deslocamento:</b> <?php echo (!empty($qtde_km)) ? number_format($qtde_km, 2, ".", "").'KM'  : '' ; ?> </td>
			</tr>
			<tr>
				<td><b>Nota Fiscal:</b> <?=$nota_fiscal?></td>
				<td><b>Data Nota Fiscal:</b> <?=$data_nota_fiscal?></td>
                <td colspan='2'><b>Revenda/Construtora:</b><?=$revenda_nome?></td>
			</tr>
		</table>

		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;" >
			<tr>
				<th class="titulo_tabela" colspan="2" >Informações do Produto</th>
			</tr>
			<tr>
				<td><b>Produto:</b> <?=$produto_referencia?> - <?=$produto_descricao?> <?=$produto_voltagem?> </td>
				<!-- <td><b>Voltagem:</b> <?=$produto_voltagem?></td> -->
				<td><b>Defeito Reclamado:</b> <?=$defeito_reclamado?></td>
			</tr>
			<?php if($login_fabrica == 145){
				echo "<tr>";
					echo "<td><b>Defeito Constatado:</b> $defeito_constatado</td>";
					echo "<td><b>Solução:</b> $descricao_solucao</td>";
				echo "</tr>";

				}else{?>
			<tr>
				<td colspan="2" ><b>Defeito Constatado:</b> <?=$defeito_constatado?></td>
			</tr>
			<?}?>
			<tr>
				<td><b>Aparência:</b> <?=$aparencia?></td>
				<td><b>Acessórios:</b> <?=$acessorios?></td>
			</tr>
		</table>

		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;" >
			<tr>
				<th class="titulo_tabela" colspan="3" >Informações do Posto Autorizado</th>
			</tr>
			<tr>
				<td><b>Razão Social:</b> <?=$posto_razao_social?></td>
				<td><b>CNPJ:</b> <?=formata_cpf_cnpj($posto_cnpj)?></td>
				<td><b>Fone:</b> <?=($posto_fone)?></td>
			</tr>
		</table>

		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;" >
			<tr>
				<th class="titulo_tabela" colspan="4" >Informações <?php echo "$tipo_consumidor_revenda"?></th>
			</tr>
			<tr>
				<td colspan="2" ><b>Nome:</b> <?=$consumidor_nome?></td>
				<td colspan="2" ><b><?=(strlen($consumidor_cpf_cnpj) == 11) ? "CPF" : "CNPJ"?>:</b> <?=formata_cpf_cnpj($consumidor_cpf_cnpj)?></td>
			</tr>
			<tr>
				<td><b>CEP:</b> <?=$consumidor_cep?></td>
				<td><b>Estado:</b> <?=$consumidor_estado?></td>
				<td><b>Cidade:</b> <?=$consumidor_cidade?></td>
				<td><b>Bairro:</b> <?=$consumidor_bairro?></td>
			</tr>
			<tr>
				<td colspan="2" ><b>Endereço:</b> <?=$consumidor_endereco?></td>
				<td><b>Número:</b> <?=$consumidor_numero?></td>
				<td><b>Complemento:</b> <?=$consumidor_complemento?></td>
			</tr>
			<tr>
				<td colspan="2" ><b>Telefone:</b> <?=$consumidor_telefone?></td>
				<td colspan="2" ><b>Email:</b> <?=$consumidor_email?></td>
			</tr>
		</table>

		<table class="table table-bordered" style="margin: 0 auto;" >
			<tr>
				<td><b>Componente</b></td>
				<td style="width: 25px;" ><b>Qtde</b></td>
				<td><b>Serviço</b></td>
			</tr>
			<?php

			if (pg_num_rows($componentes) > 0) {
				$a = 0;
				while ($componente = pg_fetch_object($componentes)) {
					$a++;
					echo "
						<tr>
							<td>{$componente->componente}</td>
							<td>{$componente->qtde}</td>
							<td>{$componente->servico}</td>
						</tr>
					";
				}
			}
			$a =  7 - $a ;
			while ($a >= 1) {
				echo "<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					</tr>";
					$a--;
			}
			?>
			<!-- <tr>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr> -->
		</table>

		<table class="table table-bordered" style="margin: 0 auto;" >
			<tr>
				<td style="vertical-align: top; height: 120px;"><b>Observações:</b><p> <?=$observacao?> </td>
			</tr>
		</table>

		<table class="table table-bordered" style="margin: 0 auto; table-layout: fixed;" >
			<tr>
				<td><b>Técnico:</b> </td>
				<th><b>Assinatura:</b> </th>
			</tr>
			<tr>
				<td><b>Assinatura do Cliente:</b> </td>
				<th><b>Data:</b> </th>
			</tr>
		</table>
	</div>

</body>
</html>
