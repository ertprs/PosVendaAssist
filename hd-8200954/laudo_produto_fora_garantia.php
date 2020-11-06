<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

include_once S3CLASS;

$s3 = new AmazonTC("inspecao", $login_fabrica);

$os = $_GET["os"];

if (!empty($os)) {
	$sql = "SELECT produto, laudo_tecnico FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
	$res = pg_query($con, $sql);

	if (!pg_num_rows($res)) {
		exit("Ordem de Servi�o n�o encontrada");
	} else {
		$produto = pg_fetch_result($res, 0, "produto");
		$laudo   = pg_fetch_result($res, 0, "laudo_tecnico");

		$foto_produto = $s3->getObjectList("{$os}_{$produto}");

		if (count($foto_produto) > 0) {
			$foto_produto = $s3->getLink(basename($foto_produto[0]));
		}
	}

	switch ($laudo) {
		case 'descarga_eletrica':
			?>
			<table border="1" style="table-layout: fixed; border-collapse: collapse; width: 700px; margin: 0 auto;" >
				<thead>
					<tr>
						<th style="padding: 10px;">
							<img src="logos/logo_unicoba.jpg" style="max-height: 70px; max-width: 210px;" />
						</th>
						<th style="padding: 10px;">
							Relat�rio de Qualidade<br />
							DESCARGA EL�TRICA
						</th>
						<th style="padding: 10px;">
							&nbsp;
						</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<th colspan="3" style="text-align: left;">
							Defeito Constatado
						</th>
					</tr>
					<tr>
						<td colspan="3">
							<table style="table-layout: fixed;">
								<tr>
									<td>
										[ ]N�o apresentou defeito<br />
										[ ]Fonte Alim. Queimado<br />
										[ ]Porta LAN/WAN danificado<br />
										[ ]Firmware corrompido<br />
										[ ]ADSL danificado [ ]Antena danificado<br />
										[ ]Configura��o errada/fora do padr�o<br />
									</td>
									<td>
										[ ]Produto sofreu descarga el�trica<br />
										[ ]Lacre de garantia violado<br />
										[ ]Prazo de garantia vencido<br />
										[ ]Produto n�o importado pela Unicoba<br />
										[ ]N�o liga<br />
										[ ]Outros ver Obs.<br />
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<th colspan="3" style="text-align: left;">
							Diagnostico
						</th>
					</tr>
					<tr>
						<td colspan="3">
							<p>
							De acordo com o termo de garantia, excluem-se da garantia os produtos que apresentarem sinais de queda,
							exposi��o a umidade excessiva, a��o dos agentes da natureza, DESCARGA EL�TRICA, mau uso, uso inadequado por
							exposi��o do produto em ambientes com temperatura muito elevada
							</p>
							<b>
							NESTE CASO SEU PRODUTO SOFREU UMA <u>DESCARGA EL�TRICA N�O COBERTA PELA GARANTIA</u> E 
							CONSEQUENTEMENTE INVIABILIZANDO O REPARO EM GARANTIA E FORA DE GARANTIA<br />
							<br />
							ESTAMOS DEVOLVENDO O PRODUTO NAS MESMAS CONDI��ES RECEBIDAS<br />
							</b>

							<br />

							<div style="width: 100%; text-align: center;">
								<img src="<?=$foto_produto?>" style="max-height: 400px; max-width: 500px;" />
							</div>

							<br />
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<th style="text-align: left;" colspan="2">
							T�cnico Respons�vel:
						</th>
						<th style="text-align: left;">
							Data:
						</th>
					</tr>
				</tfoot>
			</table>
			<?php
			break;
		
		case 'nao_atende_requisitos_garantia':
			?>
			<table border="1" style="table-layout: fixed; border-collapse: collapse; width: 700px; margin: 0 auto;" >
				<thead>
					<tr>
						<th style="padding: 10px;">
							<img src="logos/logo_unicoba.jpg" style="max-height: 70px; max-width: 210px;" />
						</th>
						<th style="padding: 10px;">
							Relat�rio de Qualidade<br />
							MAU USO
						</th>
						<th style="padding: 10px;">
							&nbsp;
						</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<th colspan="3" style="text-align: left;">
							Defeito Constatado
						</th>
					</tr>
					<tr>
						<td colspan="3">
							<table style="table-layout: fixed;">
								<tr>
									<td>
										[ ]N�o apresentou defeito<br />
										[ ]Fonte Alim. Queimado<br />
										[ ]Porta LAN/WAN danificado<br />
										[ ]Firmware corrompido<br />
										[ ]ADSL danificado [ ]Antena danificado<br />
										[ ]Configura��o errada/fora do padr�o<br />
									</td>
									<td>
										[ ]Oxida��o<br />
										[ ]Lacre de garantia violado<br />
										[ ]Prazo de garantia vencido<br />
										[ ]Produto n�o importado pela Unicoba<br />
										[ ]N�o liga<br />
										[ ]Mau uso<br />
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<th colspan="3" style="text-align: left;">
							Diagnostico
						</th>
					</tr>
					<tr>
						<td colspan="3">
							<p>
							De acordo com o termo de garantia, excluem-se da garantia os produtos que apresentarem sinais de queda,
							exposi��o a umidade excessiva, a��o dos agentes da natureza, descarga el�trica, MAU USO, uso inadequado por
							exposi��o do produto em ambientes com temperatura muito elevada
							</p>
							<b>
							NESTE CASO SEU PRODUTO SOFREU MAU USO(FOI SUBMETIDO AO CONTATO DE ALGUM L�QUIDO E 
							CONSEQUENTEMENTE OXIDOU A PLACA)N�O COBERTO PELA GARANTIA E ASSIM INVIABILIZANDO O
							REPARO EM GARANTIA E FORA DE GARANTIA<br />
							<br />
							ESTAMOS DEVOLVENDO O PRODUTO NAS MESMAS CONDI��ES RECEBIDAS<br />
							</b>

							<br />

							<div style="width: 100%; text-align: center;">
								<img src="<?=$foto_produto?>" style="max-height: 400px; max-width: 500px;" />
							</div>

							<br />
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<th style="text-align: left;" colspan="2">
							T�cnico Respons�vel:
						</th>
						<th style="text-align: left;">
							Data:
						</th>
					</tr>
				</tfoot>
			</table>
			<?php
			break;

		case 'nao_comercializado':
			?>
			<table border="1" style="table-layout: fixed; border-collapse: collapse; width: 700px; margin: 0 auto;" >
				<thead>
					<tr>
						<th style="padding: 10px;">
							<img src="logos/logo_unicoba.jpg" style="max-height: 70px; max-width: 210px;" />
						</th>
						<th style="padding: 10px;">
							Comunicado
						</th>
						<th style="padding: 10px;">
							&nbsp;
						</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="3">
							&nbsp;
						</td>
					</tr>
					<tr>
						<td colspan="3" style="text-align: center;">
							<u>PRODUTO N�O FOI REPARADO</u><br />
							POIS N�O FOI PRODUZIDO E/OU COMERCIALIZADO PELA<br />
							<b>UNICOBA</b><br />

							<br />

							<div style="width: 100%; text-align: center;">
								<img src="<?=$foto_produto?>" style="max-height: 400px; max-width: 500px;" />
							</div>

							<br />
						</td>
					</tr>
					<tr>
						<td colspan="3">
							&nbsp;
						</td>
					</tr>
				</tbody>
			</table>
			<?php
			break;
	}
}
?>

<script>
	window.print();
</script>