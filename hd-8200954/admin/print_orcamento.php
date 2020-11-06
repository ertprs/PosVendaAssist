<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	
	$os = $_GET['os'];

	$sql = "SELECT total,
				   total_horas,
				   TO_CHAR(data_orcamento,'DD/MM/YYYY') as data
				FROM tbl_orcamento_os_fabrica
			   WHERE os = $os";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0 ){
		$valor = pg_result($res,0,total);
		$horas = pg_result($res,0,total_horas);
		$data  = pg_result($res,0,data);
	}
	
		$sql = "SELECT tbl_os.consumidor_nome                     ,
					   tbl_os.consumidor_fone                     ,
					   tbl_os.defeito_reclamado_descricao         ,
					   tbl_os.serie                               ,
					   tbl_produto.descricao AS produto           ,
					   tbl_defeito_constatado.descricao AS constatatdo ,
					   tbl_solucao.descricao AS solucao
					FROM tbl_os
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					LEFT JOIN tbl_defeito_constatado USING(defeito_constatado)
					LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica
				  WHERE tbl_os.os = $os";
		//echo nl2br($sql);
		$res = pg_query($con,$sql);

		$nome               = pg_result($res,0,consumidor_nome);
		$contato            = pg_result($res,0,consumidor_fone);
		$defeito_informado  = pg_result($res,0,defeito_reclamado_descricao);
		$serie              = pg_result($res,0,serie);
		$produto            = pg_result($res,0,produto);
		$defeito_constatado = pg_result($res,0,constatatdo);
		$solucao            = pg_result($res,0,solucao);
		

	?>
	<title>Orçamento</title>
	<style type='text/css' media='all'>
	caption{
		border:solid 1px #000;
		font:bold 20px 'Arial';
		text-align:center;
		height: 25px;
		width:700px;
		background:#CCC;
	}
	th{
		font:bold 13px 'Arial';
		text-align:right;
		height: 25px;
		width:150px;
	}
	td{
		font:12px 'Arial';
		height: 25px;
	}

	#tabPeca th{
		border:solid 1px #000;
		font:bold 13px 'Arial';
		text-align:left;
		height: 25px;	
	}
	#tabPeca td{
		border:solid 1px #000;
		font:12px 'Arial';
		height: 25px;
		width:50px;
	}
	.dados{
		font:bold 18px 'Arial';
		text-align:center;
	}

	.atendimento{
		font:bold 15px 'Arial';
	}

	#conteudo{
		position:absolute;
		width:700px;
		left:50%;
		margin-left:-350px;
		border:solid 1px #000;
		font:12px 'Arial';
	}

	#conteudo p{
		padding: 0 0 0 10px;
	}
	</style>

	<script language="JavaScript">
		//window.print();
	</script>
	<div id='conteudo'>
		<table border='0'>
			<tr>
				<td> <img src='../admin/imagens_admin/bosch_secsys_orcamento.jpg'> </td>
				<td width='400' align='center'> &nbsp;</td>
				<td> <img src='../admin/imagens_admin/bosch_orcamento.jpg'> </td>
			</tr>
			<tr>
				<td width='400' align='center' colspan='3'> 
					<span style='font:bold 18px Arial;'>ORÇAMENTO DE ASSISTÊNCIA TÉCNICA</span> 
				</td>
			</tr>
		</table>
	
		<span style='float:right; font:bold 12px Arial;'>Data do Orçamento : <?php echo $data; ?></span>
		<table width='700' align='center' style='background:#CCC;'>
			<tr>
				<th>Cliente : </th>
				<td><?php echo $nome; ?></td>
			</tr>
			<tr>
				<th>Contato : </th>
				<td><?php echo $contato; ?></td>
			</tr>
			<tr>
				<th>Equipamento : </th>
				<td><?php echo $produto; ?></td>
			</tr>
			<tr>
				<th>Série : </th>
				<td><?php echo $serie; ?></td>
			</tr>
			<tr>
				<th>Ordem de Serviço : </th>
				<td>7<?php echo $os; ?></td>
			</tr>
		</table>

		<br />

		<table width='700' align='center' style='background:#CCC;'>
			<tr>
				<th>Defeito Informado : </th>
				<td><?php echo $defeito_informado; ?></td>
			</tr>
			<tr>
				<th>Defeito Constatado : </th>
				<td><?php echo $defeito_constatado; ?></td>
			</tr>
			<tr>
				<th>Elegível à Garantia : </th>
				<td>( )Sim  ( )Não</td>
			</tr>
			<tr>
				<th>Motivo : </th>
				<td><?php echo $motivo; ?></td>
			</tr>
			<tr>
				<th>Solução : </th>
				<td><?php echo $solucao; ?></td>
			</tr>
		</table>
		
		<br />

		<?php
			$sql = "SELECT tbl_peca.descricao,
						   SUM(tbl_os_item.qtde) as qtde
						FROM tbl_os
						JOIN tbl_os_produto USING(os)
						JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
					   WHERE tbl_os.os = $os
					   GROUP BY tbl_peca.descricao";
			$res = pg_query($con,$sql);
			$total = pg_numrows($res);

			if($total > 0){
		?>
				<table width='700' align='center' id='tabPeca' cellspacing='0'>	
					<caption>Peças Necessárias</caption>
					<tr style='background:#CCC;'>
						<th>Descrição Peça</th>
						<th style='text-align:center;'>Qtde.</th>		
					</tr>
					<?php
						for($i = 0; $i < $total; $i++){
							$peca = pg_result($res,$i,descricao);
							$qtde = pg_result($res,$i,qtde);
					?>
							<tr>
								<td><?php echo $peca; ?></td>
								<td align='center'><?php echo $qtde; ?></td>
							</tr>
					<?php
						}
					?>
					
				</table>
		<?php
			}	
		?>
		
		<p style='font:bold 16px Arial;'>Observações:</p>
		
		<p>Total de Horas : <?php echo $horas;?> </p>

		<p>Manutenção e componentes..................................R$ <?php echo number_format($valor,2,',','.'); ?></p>

		<p>Validade do orçamento 05 dias úteis.</p>

		<p>Frete de devolução por conta do cliente.</p>

		<p>Pagamento à vista antecipado e identificado.</p>

		<p>Garantia de 90 dias à contar da data do recebimento do equipamento.</p>

		<p>Prazo para retorno de conserto 03 dias após a aprovação do orçamento.</p>
		
		<p>Atenciosamente,<br>
		Departamento Técnico<br>
		Robert Bosch Ltda - ST / PD</p>
	</div>
