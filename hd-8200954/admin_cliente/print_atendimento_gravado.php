<?php
	include 'dbconfig.php';
	include 'dbconnect-inc.php';
	$admin_privilegios="call_center";
	include 'autentica_admin.php';
	
	$hd_chamado = $_GET['hd_chamado'];

	$sql = "SELECT tbl_cliente_admin.nome                ,
				   tbl_cliente_admin.cnpj                ,
				   tbl_cliente_admin.endereco            ,
				   tbl_cliente_admin.numero              ,
				   tbl_cliente_admin.complemento         ,
				   tbl_cliente_admin.bairro              ,
				   tbl_cliente_admin.cep                 ,
				   tbl_cliente_admin.cidade              ,
				   tbl_cliente_admin.estado              ,
				   tbl_cliente_admin.email               ,
				   tbl_cliente_admin.fone                ,
				   tbl_cliente_admin.ie                  ,
				   TO_CHAR(tbl_hd_chamado_extra.data_nf,'DD/MM/YYY') AS data_nota         ,
				   tbl_hd_chamado_extra.nota_fiscal      ,
				   tbl_hd_chamado_extra.codigo_postagem  ,
				   tbl_hd_chamado_extra.tipo_atendimento ,
				   tbl_hd_chamado_extra.consumidor_final_nome,
				   tbl_tipo_atendimento.descricao AS atendimento
				FROM tbl_hd_chamado
				JOIN tbl_cliente_admin USING(cliente_admin)
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				LEFT JOIN tbl_tipo_atendimento ON tbl_hd_chamado_extra.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
			  WHERE tbl_hd_chamado.hd_chamado = $hd_chamado";
	$res = pg_query($con,$sql);

	$nome             = pg_result($res,0,nome);
	$cnpj             = pg_result($res,0,cnpj);
	$endereco         = pg_result($res,0,endereco);
	$numero           = pg_result($res,0,numero);
	$complemento      = pg_result($res,0,complemento);
	$bairro           = pg_result($res,0,bairro);
	$cep              = pg_result($res,0,cep);
	$cidade           = pg_result($res,0,cidade);
	$estado           = pg_result($res,0,estado);
	$email            = pg_result($res,0,email);
	$fone             = pg_result($res,0,fone);
	$ie               = pg_result($res,0,ie);
	$data_nf          = pg_result($res,0,data_nota);
	$nota_fiscal      = pg_result($res,0,nota_fiscal);
	$postagem         = pg_result($res,0,codigo_postagem);
	$tipo_atendimento = pg_result($res,0,tipo_atendimento);
	$atendimento = pg_result($res,0,atendimento);
	$consumidor_final = pg_result($res,0,consumidor_final_nome);

?>
<style rel="stylesheet" type="text/css" media='all'>
caption{
	border:solid 1px #000;
	font:bold 20px 'Arial';
	text-align:center;
	height: 25px;
	width:696px;
}
th{
	border:solid 1px #000;
	font:bold 13px 'Arial';
	text-align:left;
	height: 25px;
}
td{
	border:solid 1px #000;
	font:11px 'Arial';
	height: 25px;
}
.dados{
	font:bold 18px 'Arial';
	text-align:center;
}

.atendimento{
	font:bold 15px 'Arial';
}

.quebra_pagina{
	page-break-after: always;
}
</style>

<script language="JavaScript">
	window.print();
</script>

<title>Abertura de Pré-OS</title>

<div style="page-break-after: always; width: 700px; margin: 0 auto;";>
<table width='700' align='center'>
	<caption>Abertura de Pré-OS</caption>
	<tr >
		<td colspan='3' style='font-size:14px;'><b>Nº do Chamado :</b> <?php echo $hd_chamado;?></td>
	</tr>
	<tr >
		<td colspan='3' class='dados'>Dados do Solicitante</td>
	</tr>

	<tr>
		<th>Nome</th>
		<th>CNPJ</th>
		<th>I.E</th>
	</tr>
	<tr>
		<td><?php echo $nome;?></td>
		<td><?php echo $cnpj;?></td>
		<td><?php echo $ie;?></td>
	</tr>

	<tr>
		<th>E-mail</th>
		<th>Telefone</th>
		<th>CEP</th>
	</tr>
	<tr>
		<td><?php echo $email;?></td>
		<td><?php echo $fone;?></td>
		<td><?php echo $cep;?></td>
	</tr>

	<tr>
		<th>Endereço</th>
		<th>Número</th>
		<th>Complemento</th>
	</tr>
	<tr>
		<td><?php echo $endereco;?></td>
		<td><?php echo $numero;?></td>
		<td><?php echo (strlen($complemento) > 0) ? $complemento : "&nbsp;";?></td>
	</tr>

	<tr>
		<th>Bairro</th>
		<th>Cidade</th>
		<th>Estado</th>
	</tr>
	<tr>
		<td><?php echo $bairro;?></td>
		<td><?php echo $cidade;?></td>
		<td><?php echo $estado;?></td>
	</tr>
	
	<tr ><td colspan='3' style='border:0px;'>&nbsp;</td></tr>

	<tr>
		<td colspan='3' class='dados'>Dados do Produto</td>
	</tr>
	
	<tr>
		<td class='atendimento' colspan='3'>Tipo do Atendimento : <?php echo $atendimento;?></td>
	</tr>
	<?php
		if($tipo_atendimento ==92 or $tipo_atendimento ==94 ){
	?>
			<tr>
				<th>Data da NF</th>
				<th>Numero da NF</th>
				<th>Código de Postagem</th>
			</tr>
			<tr>
				<td><?php echo $data_nf;?></td>
				<td><?php echo $nota_fiscal;?></td>
				<td><?php echo $postagem;?></td>
			</tr>

			<tr>
				<th colspan='3'>Consumidor Final</th>
			</tr>
			<tr>
				<td colspan='3'><?php echo $consumidor_final;?></td>
			</tr>
	<?php
		}
		else{
	?>
			<tr>
				<th>Código de Postagem</th>
				<th colspan='2'>Consumidor Final</th>
			</tr>
			<tr>
				<td ><?php echo $postagem;?></td>
				<td colspan='2'><?php echo $consumidor_final;?></td>
			</tr>
	<?php
		}
		
	?>	
</table>

<table width='700' align='center'>
	<tr>
		<th>Modelo</th>
		<th>Descrição Produto</th>
		<th>Série / Lote</th>
		<th style='text-align:right;'>Qtde.</th>
		<th>Defeito</th>
		<th>Defeito Descrição</th>
	</tr>
	<?php
		$sqlProd= "SELECT COUNT(tbl_produto.referencia_fabrica) AS qtde,
						  tbl_produto.referencia_fabrica,
						  tbl_produto.descricao ,
						  tbl_hd_chamado_item.serie,
						  tbl_hd_chamado_item.defeito_reclamado,
						  tbl_hd_chamado_item.defeito_reclamado_descricao
						FROM tbl_hd_chamado_item
						JOIN tbl_produto USING(produto)
					  WHERE tbl_hd_chamado_item.hd_chamado = $hd_chamado
					  GROUP BY tbl_produto.referencia_fabrica,tbl_produto.descricao,tbl_hd_chamado_item.serie,tbl_hd_chamado_item.defeito_reclamado_descricao, tbl_hd_chamado_item.defeito_reclamado";
		//echo $sqlProd;
		$resProd = pg_query($con,$sqlProd);
		$totalProd = pg_numrows($resProd);

		if($totalProd > 0){
			for($i = 0; $i < $totalProd; $i++){
				$referecia = pg_result($resProd,$i,referencia_fabrica);
				$descricao = pg_result($resProd,$i,descricao);
				$serie     = pg_result($resProd,$i,serie);
				$qtde      = pg_result($resProd,$i,qtde);
				$defeito   = pg_result($resProd,$i,defeito_reclamado_descricao);
				$defeito_referencia   = pg_result($resProd,$i,defeito_reclamado);

			
	?>
				<tr>
					<td><?php echo $referecia; ?></td>
					<td><?php echo $descricao; ?></td>
					<td><?php echo $serie; ?></td>
					<td align='right'><?php echo $qtde; ?></td>
					<td><?php echo $defeito_referencia; ?></td>
					<td><?php echo $defeito; ?></td>
				</tr>
	<?php
		}
		}
	?>
</table>
</div>


<div style="page-break-after: always; width: 700px; margin: 0 auto;";>
<table width='700' align='center' border='0' cellspacing='0' cellpadding='0' class='quebra_pagina' style="page-break-after: always;">
	<tr>
		<td colspan='2' style='border: none'><div style='border-bottom: 2px dotted #000; margin: 30px 5px; text-align: right; font-size: 9px'>Recorte e Cole na Embalagem da Remessa</div></td>
	</tr>
	<tr>
		<td style='border: none; vertical-align: top; text-align: right; font-size: 16px;'><b>DESTINATÁRIO</b></td>
		<td style='border: none; vertical-align: top; text-align: left; font-size: 16px;'>
			<br>
			<b>Robert Bosch Ltda - CHAMADO Nº <?php echo $hd_chamado;?></b><br>
			Divisão: ST - CA 140 SS<br>
			A/C: Sr. Renato / André (Depto. Técnico) Ramal: 2874 / 2867<br>
			Via Anhanguera, km 98 - Vila Boa Vista - CEP: 13065-900 - Campinas – SP<br>
		</td>
	</tr>
</table>
</div>
