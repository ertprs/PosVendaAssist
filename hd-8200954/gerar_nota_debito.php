<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin === true) {
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include 'funcoes.php';
include_once __DIR__.'/plugins/fileuploader/TdocsMirror.php';

include_once "classes/mpdf61/mpdf.php";
$pdf    = new mPDF;

if(!empty($_GET['extrato'])){

	$extrato = $_GET['extrato'];

	$sql = "SELECT 	tbl_posto.nome,
			tbl_posto.cnpj,
			tbl_posto_fabrica.contato_endereco,
			tbl_posto_fabrica.contato_numero,
			tbl_posto_fabrica.contato_complemento,
			tbl_posto_fabrica.contato_bairro,
			tbl_posto_fabrica.contato_cidade,
			tbl_posto_fabrica.contato_estado,
			tbl_posto_fabrica.contato_cep,
			to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_extrato,
			tbl_extrato.deslocamento
		FROM tbl_extrato
		INNER JOIN tbl_posto_fabrica USING(posto,fabrica)
		INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
		WHERE tbl_extrato.extrato = {$extrato}";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
		$nome 			 = pg_fetch_result($res,0,'nome');
		$cnpj                    = pg_fetch_result($res,0,'cnpj');
		$contato_endereco 	 = pg_fetch_result($res,0,'contato_endereco');
		$contato_numero 	 = pg_fetch_result($res,0,'contato_numero');
		$contato_complemento 	 = pg_fetch_result($res,0,'contato_complemento');
		$contato_bairro 	 = pg_fetch_result($res,0,'contato_bairro');
		$contato_cidade 	 = pg_fetch_result($res,0,'contato_cidade');
		$contato_estado 	 = pg_fetch_result($res,0,'contato_estado');
		$contato_cep 		 = pg_fetch_result($res,0,'contato_cep');
		$deslocamento 		 = pg_fetch_result($res,0,'deslocamento');
		$data_extrato		 = pg_fetch_result($res,0,'data_extrato');
		
		$sql_lancamento = "
			SELECT tbl_extrato_lancamento.valor, tbl_lancamento.debito_credito 
			FROM tbl_extrato_lancamento 
			JOIN tbl_lancamento ON tbl_lancamento.lancamento = tbl_extrato_lancamento.lancamento AND tbl_lancamento.fabrica = {$login_fabrica}
			WHERE tbl_extrato_lancamento.extrato = {$extrato}
			AND UPPER(tbl_lancamento.descricao) ILIKE '%KM%' ";
		$res_lancamento = pg_query($con, $sql_lancamento);
		
		if (pg_num_rows($res_lancamento) > 0) {
			$soma_total_debito = 0;
			$soma_total_credito = 0;
			for ($x=0; $x < pg_num_rows($res_lancamento); $x++) {
				$debito_credito = pg_fetch_result($res_lancamento, $x, 'debito_credito');
				$valor = pg_fetch_result($res_lancamento, $x, 'valor');
				if ($debito_credito == 'D'){
					$xvalor = explode("-", $valor);
					$soma_total_debito += $xvalor[1];
				}else{
				 	$soma_total_credito += $valor;
				}
				
			}
			$deslocamento = ($deslocamento + $soma_total_credito);
			$deslocamento = ($deslocamento - $soma_total_debito);
		}
	}else{
		$msg = "Extrato {$extrato} não encontrado";
	}
}
?>

<?php
ob_start();
?>
<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/bootstrap.css" />
<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/extra.css" />
<link media="screen" type="text/css" rel="stylesheet" href="css/tc_css.css" />
<link media="screen" type="text/css" rel="stylesheet" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/ajuste.css" />

<!--[if lt IE 10]>
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
<link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
<![endif]-->


<style>

	/* Flex */
	.flex {
		display: flex;
	}

	.flex-wrap {
		flex-wrap: wrap;
	}

	.container{
		border: solid 2px;
	}

	.bloco{
		border-bottom: solid 2px;
	}

	.titulo{
		font-weight: bold;
		padding-left: 2px;
	}
</style>
<!--
<div class="container">
	<div class="row-fluid bloco">
		<div class="span7">Nota de débito</div>
		<div class="span5 flex flex-wrap" style="float:right; border:solid 1px;">
			<table width="100%" style="border:solid 1px;text-align: center;">
				<tr><td style="border-bottom:solid 1px">Número</td></tr>
				<tr><td><b><?=$extrato?></b></td></tr>
			</table>
		</div>
	</div>

	<div class="row-fluid bloco">
		<div class="span3 titulo">COD. RESPONSÁVEL:</div>
                <div class="span1">027828</div>
                <div class="span2 titulo">DATA INÍCIO:</div>
                <div class="span2"><?=$data_extrato?></div>
		<div class="span2 titulo">DATA FIM:</div>
		<div class="span2"><?=$data_extrato?></div>
	</div>

	<div class="row-fluid bloco">
		<div class="row-fluid span12 titulo">EMITENTE</div>
		<div class="row-fluid'">
			<div class="span2 titulo">RAZÃO SOCIAL:</div>
			<div class="span10"><?=$nome?></div>
		</div>
		<div class="row-fluid">
			<div class="span2 titulo">ENDEREÇO:</div>
			<div class="span4"><?=$contato_endereco?> <?=$contato_numero?> <?=$contato_complemento?></div>
			<div class="span2 titulo">BAIRRO:</div>
			<div class="span4"><?=$contato_bairro?></div>
		</div>
		<div class="row-fluid">
			<div class="span2 titulo">MUNICÍPIO/UF:</div>
			<div class="span4"><?=$contato_cidade?>/<?=$contato_estado?></div>
			<div class="span2 titulo">CEP:</div>
			<div class="span4"><?=$contato_cep?></div>
		</div>
		<div class="row-fluid">
			<div class="span2 titulo">CNPJ:</div>
			<div class="span10"><?=$cnpj?></div>
		</div>
	</div>

	<div class="row-fluid bloco">
		<div class="row-fluid">
			<div class="span6 titulo">TIPO DE DOCUMENTO</div>
			<div class="span6 titulo">VLR. TOTAL</div>
		</div>
		<div class="row-fluid">
			<div class="span6">KM</div>
			<div class="span6"><?=number_format($deslocamento,2,',','.')?></div>
		</div>
	</div>

	<div class="row-fluid bloco">
		<div class="span6">SUJEITO A ACEITAÇÃO DA ITATIAIA</div>
		<div class="span6">VALOR TOTAL: <?=number_format($deslocamento,2,',','.')?></div>
	</div>

	<div class="row-fluid bloco">
		<div class="span12">Recebemos da Itatiaia Móveis S/A, a importância de <?=numero_por_extenso($deslocamento)?> referente ao reembolso de despesas conforme lote número <?=$extrato?></div>
	</div>

	<div class="row-fluid bloco">
		<div class="span12">Carimbo CNPJ (Obrigatório)</div>
	</div>

	<div class="row-fluid">
		<div class="span12">Assinatuta Emitente</div>
	</div>
</div>
-->
<?php
$html ="<table cellspacing='0' cellpadding='0' align='center' width='100%' style='border: 2px solid;'>
	<tr>
		<td colspan='1' style='border-bottom: 2px solid;'>Nota de débito</td>
		<td colspan='2' style='border-bottom: 2px solid;'>
			<table align='right' style='border: 2px solid; margin-bottom: 20px;' width='50%'>
				<tr style='border: 2px solid;';><td style='border-bottom: 2px solid;' align='center'>Número</td></tr>
				<tr style='border: 2px solid;';><td align='center'>{$extrato}</td></tr>
			</table>
		</td>
	</tr>
	<tr>
		<td valign='top' style='padding-bottom: 20px;'>
			<span style='font-weight: bold;'>COD. RESPONSÁVEL: </span> 027828
		</td>
		<td valign='top' style='padding-bottom: 20px;'>
			<span style='font-weight: bold;'>DATA INÍCIO: </span> {$data_extrato}
		</td>
		<td valign='top' style='padding-bottom: 20px;'>
			<span style='font-weight: bold;'>DATA FIM: </span> {$data_extrato}
		</td>
	</tr>
	<tr>
		<td colspan='3' valign='top' style='padding-bottom: 45px; padding-top: 10px; font-weight: bold'>EMITENTE</td>
	</tr>
	<tr>
		<td colspan='3' style='padding-bottom: 15px;'><span style='font-weight: bold'>RAZÃO SOCIAL:</span>{$nome}</td>
	</tr>
	<tr>
		<td colspan='1' style='padding-bottom: 15px;'>
			<span style='font-weight: bold'>ENDEREÇO:</span> {$contato_endereco} {$contato_numero} {$contato_complemento}
		</td>
	</tr>
	<tr>
		<td colspan='2' style='padding-bottom: 15px;'>
			<span style='font-weight: bold'>BAIRRO:</span> {$contato_bairro}
		</td>
	</tr>
	<tr>
		<td style='padding-bottom: 15px;' colspan='1'><span style='font-weight: bold'>MUNICÍPIO/UF:</span>{$contato_cidade}/{$contato_estado}</td>
		<td style='padding-bottom: 15px;' colspan='2'><span style='font-weight: bold'>CEP:</span> {$contato_cep}</td>
	</tr>
	<tr>
		<td style='padding-bottom: 60px;' colspan='3'><span style='font-weight: bold'>CNPJ:</span> {$cnpj}</td>
	</tr>
	<tr>
		<td colspan='1' style='padding-bottom: 10px; border-top: 2px solid;'><span style='font-weight: bold'>TIPO DE DOCUMENTO:</span></td>
		<td colspan='2' style='padding-bottom: 10px; border-top: 2px solid;'><span style='font-weight: bold'>VLR. TOTAL</span></td>
	</tr>
	<tr>
		<td colspan='1'>KM</td>
		<td colspan='2'> ".number_format($deslocamento,2,',','.')."</td>
	</tr>
	<tr>
		<td valign='top' style='border-top: 2px solid; padding-bottom: 30px; padding-top: 10px;' colspan='1'>SUJEITO A ACEITAÇÃO DA ITATIAIA</td>
		<td valign='top' style='border-top: 2px solid; padding-bottom: 30px; padding-top: 10px;' colspan='2'><span style='font-weight: bold'>VALOR TOTAL:</span>".number_format($deslocamento,2,',','.')."</td>
	</tr>
	<tr>
		<td valign='top' colspan='3' style='padding-bottom: 35px; border-top: 2px solid; padding-top: 10px;'>
			Recebemos da Itatiaia Móveis S/A, a importância de ".numero_por_extenso($deslocamento)." referente ao reembolso de despesas conforme lote número {$extrato}
		</td>
	</tr>
	<tr>
		<td valign='top' colspan='3' style='border-top: 2px solid; padding-bottom: 35px; padding-top: 10px;'>
			Carimbo CNPJ (Obrigatório)
		</td>
	</tr>
	<tr>
		<td valign='top' colspan='3' style='border-top: 2px solid; padding-bottom: 35px; padding-top: 10px;'>
			Assinatuta Emitente
		</td>
	</tr>
</table>";
#$html = ob_get_contents();
$mpdf = new mPDF();
$mpdf->SetDisplayMode('fullpage');
$mpdf->charset_in = 'windows-1252';
$mpdf->WriteHTML($html);
ob_clean();
$nomePDF = "/tmp/nota_debito".$extrato."_".date("Y_m_d_H_i_s").".pdf";
$nomePDFx = "nota_debito_".$extrato."_".date("Y_m_d_H_i_s").".pdf";
$mpdf->Output($nomePDF,"F");
$mpdf->Output($nomePDF,"D");

$sql_tdocs = "SELECT tdocs FROM tbl_tdocs WHERE fabrica = $login_fabrica AND referencia_id = $extrato AND referencia = 'nf_debito'";
$res_tdocs = pg_query($con, $sql_tdocs);

if (pg_num_rows($res_tdocs) == 0){
	$tdocsMirror = new TdocsMirror();
	$response    = $tdocsMirror->post($nomePDF);

	foreach ($response[0] as $key => $value) {
		$unique_id = $value["unique_id"];
		$obs[]= array(
			"acao" => "anexar",
			"filename" => $key,
			"data" => $value["date"],
			"fabrica" => $login_fabrica,
			"usuario" => "$login_admin",
			"page" => "admin/gerar_nota_debito.php"
		);
	}

	if (!empty($response)){
		$obs = json_encode($obs);
		$sql = "
			INSERT INTO tbl_tdocs(
				tdocs_id,
				fabrica,
				contexto,
				situacao,
				obs,
				referencia,
				referencia_id
			)VALUES(
				'$unique_id', 
				$login_fabrica, 
				'extrato', 
				'ativo', 
				'$obs', 
				'nf_debito',
				$extrato
			)"; 
		$res = pg_query($con, $sql); 
	}
}
unlink($nomePDF);
?>
