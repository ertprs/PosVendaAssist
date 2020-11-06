<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	
	$log_posto = @$_GET['log_posto'];
	if($log_posto == 'posto')
		include 'autentica_usuario.php';
	else
		include 'autentica_admin.php';
	

	$os = $_GET['os'];

	$sql = "SELECT 
				total,
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

	$sql = " SELECT prazo_retorno FROM tbl_orcamento_os_fabrica WHERE os = $os;";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$prazo_retorno = pg_fetch_result($res,0,'prazo_retorno');
		if(strlen($prazo_retorno) == 0) $prazo_retorno = 0;
	}

	$sql = "SELECT obs_nf FROM tbl_os_extra WHERE os = $os;";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$motivo = pg_fetch_result($res,0,'obs_nf');
	}
	
	$data = date('d/m/Y');

	$sql = "SELECT 
			tbl_os.consumidor_nome					,
			tbl_os.obs					,
			tbl_os.consumidor_fone						,
			tbl_os.sua_os as sua_os					,
			tbl_os.defeito_reclamado_descricao				,
			tbl_os.serie								,
			tbl_produto.descricao AS produto				,
			tbl_produto.referencia_fabrica AS referencia_fabrica	,
			tbl_defeito_constatado.descricao AS constatatdo	,
			tbl_solucao.descricao AS solucao				
		FROM tbl_os
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			LEFT JOIN tbl_defeito_constatado USING(defeito_constatado)
			LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica
		WHERE 
			tbl_os.os  =$os;";
	//echo $sql;
	$res = pg_query($con,$sql);
	$obs               = pg_result($res,0,obs);
	$sua_os               = pg_result($res,0,sua_os);
	$nome               = pg_result($res,0,consumidor_nome);
	$contato            = pg_result($res,0,consumidor_fone);
	$defeito_informado  = pg_result($res,0,defeito_reclamado_descricao);
	$serie              = pg_result($res,0,serie);
	$produto            = pg_result($res,0,produto);
	$referencia_fabrica  = pg_result($res,0,referencia_fabrica);
	$defeito_constatado = pg_result($res,0,constatatdo);
	$solucao            = pg_result($res,0,solucao);
	//$motivo            = pg_result($res,0,motivo);
	
	//pecas necessarias
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

	$orcamento = "<title>Orçamento de Assistência Técnica</title>";
 	$orcamento .="<style type='text/css' media='all'>
	*{
		font-family: Verdana, Tahoma, Helvetica, Arial;
	}

	p{
		padding: 5px 0 5px 20px;
		margin: 0;
	}
	table td{
		font-size: 14px;
	}
	</style>";

	$orcamento .= "<table cellpadding='0' cellspacing='0' border='0' width='100%' align='center' style='margin: 0; margin-bottom: 50px'>";
		$orcamento .="<tr>";
			$orcamento .="<td align='right' valign='top'>";		
				$orcamento .="<a href='http://www.bosch.com.br' target='_blank' title='Bosch'><img src='imagens/96/20110413LogoBosch.jpg' width='200px'></a>";
			$orcamento .="</td>";
		$orcamento .="</tr>";
	$orcamento .="</table>";

	$orcamento .="<table cellpadding='2' cellspacing='0' border='0' width='100%' align='center'>";
		$orcamento .="<tr>";
			$orcamento .="<td colspan='2'><b>ORÇAMENTO DE ASSISTÊNCIA TÉCNICA</b></td>";
			$orcamento .="<td align='right'>Data: $data &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		$orcamento .="</tr>";
		$orcamento .="<tr>";
			$orcamento .="<td width='150px'>&nbsp;</td>";
			$orcamento .="<td width='*'>&nbsp;</td>";
			$orcamento .="<td width='230px'>&nbsp;</td>";
		$orcamento .="</tr>";
		$orcamento .="<tr>";
			$orcamento .="<td>Cliente:</td>";
			$orcamento .="<td colspan='2'>$nome</td>";
		$orcamento .="</tr>";
		$orcamento .="<tr>";
			$orcamento .="<td>Contato:</td>";
			$orcamento .="<td colspan='2'>$contato</td>";
		$orcamento .="</tr>";
		$orcamento .="<tr>";
			$orcamento .="<td>Equipamento:</td>";
			$orcamento .="<td colspan='2'>$referencia_fabrica - $produto</td>";
		$orcamento .="</tr>";
		$orcamento .="<tr>";
			$orcamento .="<td>Série:</td>";
			$orcamento .="<td colspan='2'>$serie</td>";
		$orcamento .="</tr>";
		$orcamento .="<tr>";
			$orcamento .="<td>Ordem de Serviço:</td>";
			$orcamento .="<td colspan='2'>Nº $os</td>";
		$orcamento .="</tr>";
		$orcamento .="<tr>";
			$orcamento .="<td colspan='3'>";
				$orcamento .="<p style='padding-left: 0;'>Defeito Informado: $defeito_informado</p>";
				$orcamento .="<p style='padding-left: 0;'>Defeito Constatado: $defeito_constatado</p>";
				$orcamento .="<p style='padding-left: 0;'>Solução: $solucao</p>";
				$orcamento .="<p style='padding-left: 0;'>Elegível à Garantia ?  (&nbsp;&nbsp;) Sim  ( <b>x</b> ) Não</p>";
				$orcamento .="<p style='padding-left: 0;'>Motivo: $motivo</p>";
				//$orcamento .="<p style='padding-left: 0;'>Solução: $solucao</p>";
			$orcamento .="</td>";
		$orcamento .="</tr>";
		$orcamento .="<tr>";
			$orcamento .="<td colspan='3'>Peças necessárias: </td>";
		$orcamento .="</tr>";

			if($total > 0){
				for($i = 0; $i < $total; $i++){
					$peca = pg_result($res,$i,descricao);
					$qtde = pg_result($res,$i,qtde);
					$orcamento .="<tr>";
						$orcamento .="<td colspan='3''>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".str_pad($qtde, 3, '0', STR_PAD_LEFT)."&nbsp; - $peca</td>";
					$orcamento .="</tr>";
				}
			}
					$orcamento .="<tr>";
						$orcamento .="<td colspan='3''><p>Total de Horas : $horas</p></td>";
					$orcamento .="</tr>";

		$orcamento .="<tr>";
			$orcamento .="<td colspan='3'>";
				$orcamento .="<br>Observações: $obs";
				
				$orcamento .="<p><b>".str_pad('Valor do Orçamento: ', 140, '. ', STR_PAD_RIGHT)." R$ ".number_format($valor,2,',','.')."</b></p>";
				$orcamento .="<p>Frete por conta do cliente.</p>";
				$orcamento .="<p>Pagamento à vista  identificado.</p>";
				$orcamento .="<p>Validade do orçamento 05 dias úteis.</p>";
				$orcamento .="<p>Garantia de 90 dias à contar da data do recebimento do equipamento.</p>";
				$orcamento .="<p>Prazo para retorno de conserto $prazo_retorno dias após a aprovação do orçamento.</p>";
				$orcamento .="<p><br>Enviar comprovante de Pagamento para os e-mails: andre.dias@br.bosch.com e renato.lima2@br.bosch.com indicando o numero da OS</p>";
				$orcamento .="<p style='margin-top: 50px; padding: 0;'>
					Atenciosamente,<br>
					Departamento Técnico<br>
					Robert Bosch Ltda - ST / PD";
				$orcamento .="</p>";
			$orcamento .="</td>";
		$orcamento .="</tr>";
	$orcamento .="</table>";
	

	require_once("pdf/dompdf/dompdf_config.inc.php");
	$dompdf = new DOMPDF();
	$dompdf->load_html($orcamento);
	$dompdf->set_paper("A4");
	$dompdf->render();

	$data = Date('Ymd_His_').$sua_os;
	$dompdf->stream("$data.pdf");

	echo $orcamento;
	exit;
	?>