<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";

	header("Expires: 0");
	header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");

	$admin_privilegios = "financeiro";

	include "autentica_admin.php";
	
	$extrato = $_REQUEST['extrato'];
	if(strlen($extrato) == 0){
		exit('Extrato Inválido');
	}

	if (strlen($extrato) > 0) {
		$sql = "SELECT 
				tbl_extrato.total			, 
				tbl_extrato.posto			, 
				
				tbl_posto_fabrica.agencia	,
				tbl_posto_fabrica.tipo_conta	,
				tbl_posto_fabrica.conta		,
				tbl_banco.nome AS banco	,
				tbl_posto.nome,
				tbl_posto.cnpj
			FROM 
				tbl_extrato 
				JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_banco ON tbl_banco.codigo = tbl_posto_fabrica.banco
				JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
			WHERE 
				tbl_extrato.extrato = $extrato
				AND tbl_extrato.fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if(pg_num_rows($res) == 0 ){
			exit('Extrato Inválido');
		}
		
		$total = pg_fetch_result($res,0,'total');
		$posto = pg_fetch_result($res,0,'posto');
		$banco = pg_fetch_result($res,0,'banco');
		$agencia = pg_fetch_result($res,0,'agencia');
		$conta = pg_fetch_result($res,0,'conta');
		$tipo_conta = pg_fetch_result($res,0,'tipo_conta');

		$nome_posto = pg_fetch_result($res,0,'nome');
		$cnpj = pg_fetch_result($res,0,'cnpj');
	}

	$sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $login_admin ;";
	$res = pg_query ($con,$sql);
	$nome_admin = pg_fetch_result($res,0,'nome_completo');
	

$sec = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
$sec .= "
	<html>
		<head>
			<meta http-equiv='pragma' content='no-cache'>
			<style type='text/css'>
				body {
					margin: 0;
					font-family: Verdnaa, Arial, Verdana, Times, Sans;
					background: #fff;
					font-size: 13px;
				}

				#content{
					margin: 0 auto;
				}
				p{
					text-align: center;
					margin: 0;
					font-weight: bold;
					padding: 0;
					background:#CCC;
					padding: 2px 0;
				}

				table table td div{
					margin: 10px;
					margin-top: 50px;
					margin-bottom: 0;
					border-top: 1px solid #000;
					text-align: center;
					
				}
			</style>
		</head>

		<body>";

$via = "		<div id='content'>
		
				<table width='100%' cellspacing='2' cellpadding='3' border='0'>
					<tr>
						<td>
							<img src='../logos/$login_fabrica_logo' alt=' $login_fabrica_site' border='0' height='40' /><br />
							Emissão: ".Date('d/m/Y H:i:s')."
						</td>
						<td valign='top' align='right'>
							Entregue no Contas a Pagar: ".Date('d/m/Y')."
						</td>
					</tr>
					<tr>
						<td colspan='2'><p>SOLICITAÇÃO E EMISSÃO DE CHEQUES</p></td>
					</tr>
					<tr>
						<td>
							<strong>Solicitante: </strong> $nome_admin<br />
							<strong>C.C.1331:</strong> Assistência Técnica 
						</td>
						<td>
							<strong>Deposito: </strong>  $banco / Agência: $agencia<br />
							<strong>$tipo_conta :</strong> $conta
						</td>
					</tr>
					<tr>
						<td colspan='2'>
							Solicito a emissão de cheque para o dia ___/___/______, NF __________ , Conta Contábil 421.104.0003
							<br />no valor de <b>R$ ".number_format($total,2,',','.')."</b>, referente ao extrato número: <b> $extrato</b>
						</td>
					</tr>
					<tr>
						<td colspan='2'>
							<strong>A Favor: </strong> $nome_posto<br />
							<strong>Empresa: </strong> $nome_posto<br />
							<strong>CNPJ/CPF: </strong> $cnpj<br />
							<strong>Referente: </strong>Pagamento de Mão de Obra do Extrato nº <b> $extrato</b><br />
						</td>
					</tr>
					<tr>
						<td valign='top'>
							<div style='margin: 15px;'>
								<table width='100%' cellspacing='0' cellpadding='0' border='0'>
									<tr>
										<td colspan='2'><p>Assinaturas</p></td>
									</tr>
									<tr>
										<td width='50%' >&nbsp;</td>
										<td width='50%' align='right'>Aprovação de Pagamento</td>
									</tr>
									<tr>
										<td style='border-right: 1px solid #CCC;'>
											<div>Gerente do Contrato</div>
										</td>
										<td>
											<div>Até R$ 2.000,00</div>
										</td>
									</tr>
									<tr>
										<td style='border-right: 1px solid #CCC;'>
											<div>Financeiro</div><br />
										</td>
										<td>
											<div>Acima de R$ 2.000,00</div><br />
										</td>
									</tr>
								</table>
							</div>
						</td>
						<td valign='top'>
							<div style='margin: 15px;'>
								<table width='100%' cellspacing='1' cellpadding='0' border='0'>
									<tr>
										<td colspan='2'><p>Devolução</p></td>
									</tr>
									<tr>
										<td colspan='2'>Preenchimento</td>
									</tr>
									<tr>
										<td colspan='2'>
											<strong><br />Insuficiente/Incorreto: ___________________________</strong>
										</td>
									</tr>
									<tr>
										<td colspan='2'>
											<strong><br />Falha de Assinatura: ____________________________</strong>
										</td>
									</tr>
									<tr>
										<td colspan='2'>
											<strong><br />Outros: _______________________________________</strong>
										</td>
									</tr>
									<tr>
										<td colspan='2'>
											<strong><br />Nrº do Contrato: _______________________________</strong><br /><br />
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
					<tr>
						<td width='50%'>&nbsp;</td>
						<td width='50%'>&nbsp;</td>
					</tr>
				</table>
			</div>";
$sec_footer = "
		</body>
	</html>";


	$print = $sec.$via."<div style='border-top: 1px dashed #CCC; margin: 10px 0;'>&nbsp;</div>".$via.$sec_footer;

	require_once("../pdf/dompdf/dompdf_config.inc.php");
	$dompdf = new DOMPDF();
	$dompdf->load_html($print);
	$dompdf->set_paper("A4");
	$dompdf->render();

	$data = Date('Ymd_His_').$extrato;
	$dompdf->stream("$data.pdf");

	//echo $print;
	exit;
	