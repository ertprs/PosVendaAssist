<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$q = strtolower(utf8_decode($_GET["q"]));

if (isset($_GET["q"])){

	$q = trim($q);

	$sql = "SELECT   tbl_posto.*, tbl_posto.cnpj
		FROM     tbl_posto
		JOIN     tbl_posto_fabrica USING (posto)
		WHERE    (tbl_posto.nome ILIKE '%$q%' OR tbl_posto.nome_fantasia ILIKE '%$q%' OR tbl_posto.cnpj ILIKE '%$q%')
		AND      tbl_posto_fabrica.fabrica in($telecontrol_distrib)
		ORDER BY tbl_posto.nome";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res)>0) {
		for ( $i = 0 ; $i < @pg_num_rows ($res) ; $i++ ) {
			$cnpj 	= trim(pg_result($res,$i,'cnpj'));
			$nome	= trim(pg_fetch_result($res,$i,'nome'));

			/**
			 * Mudança Telecontrol -> Acácia
			 */
			if ($posto == 4311) {
				$nome = 'Acáciaeletro Paulista Ltda.';
			}

			echo "$nome|$cnpj|";
			echo "\n";
		}
	}else{
		echo "<h2>Posto não encontrado</h2>";
	}

	exit;
}

if(isset($_GET['aprova'])){

	$inadimplencia = $_GET['inadimplencia'];

	$sql = "UPDATE tbl_telecontrol_inadimplencia SET recebimento = CURRENT_DATE WHERE inadimplencia = $inadimplencia";
	$res = pg_query($con,$sql);

	if(strlen(pg_last_error()) == 0){
		echo "ok";
	}else{
		echo pg_last_error();
	}

	exit;

}

if(isset($_GET['email'])){

	$array_meses = array("01"=>"Janeiro","02"=>"Fevereiro","03"=>"Março","04"=>"Abril","05"=>"Maio","06"=>"Junho","07"=>"Julho","08"=>"Agosto","09"=>"Setembro","10"=>"Outubro","11"=>"Novembro","12"=>"Dezembro");
	$dia = date("d");
	$mes = $array_meses[date("m")];
	$ano = date("Y");

	$posto = $_GET['posto'];

	$sql = "SELECT tbl_posto_fabrica.contato_email,
					tbl_posto_fabrica.contato_cidade,
					tbl_posto_fabrica.contato_estado,
					tbl_posto.cnpj,
					tbl_posto.nome 
			FROM tbl_posto_fabrica
			JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE fabrica in(".$fabricas[0].")
			AND tbl_posto.posto = $posto
			ORDER BY tbl_posto_fabrica.atualizacao DESC
			LIMIT 1";
	$res = pg_query($con,$sql);
	$contato_email 	= pg_fetch_result($res, 0, 'contato_email');
	$contato_cidade = pg_fetch_result($res, 0, 'contato_cidade');
	$contato_estado = pg_fetch_result($res, 0, 'contato_estado');
	$cnpj 			= pg_fetch_result($res, 0, 'cnpj');
	$nome 			= pg_fetch_result($res, 0, 'nome');

	include_once '../class/email/mailer/class.phpmailer.php';

	$mailer = new PHPMailer();

	$mensagem = "Marília, $dia de $mes de $ano. <br><br>";
	$mensagem .= "À <br> EMPRESA $nome <br> CNPJ $cnpj <br> $contato_cidade - $contato_estado <br><br>";
	$mensagem .= "Prezados Senhores:<br><br>";
	

	$sql = "SELECT 	nota_fiscal, 
					to_char(vencimento,'DD/MM/YYYY') AS vencimento,
					valor
				FROM tbl_telecontrol_inadimplencia
				WHERE posto = $posto
				AND recebimento IS NULL";
	$res = pg_query($con,$sql);

	for($i = 0; $i < pg_num_rows($res); $i++){

		$nota_fiscal = pg_fetch_result($res, $i, 'nota_fiscal');
		$vencimento = pg_fetch_result($res, $i, 'vencimento');
		$valor = pg_fetch_result($res, $i, 'valor');

		$mensagem .= "REF. Boleto $nota_fiscal, VENC. $vencimento, Valor R$ ".number_format($valor,2,',','.')."<br>";

	}

	$mensagem .= "<br><br>Atenciosamente,<br><br>";
	$mensagem .= "ACÁCIA ELETRO PAULISTA <br><br>";
	$mensagem .= "Depto. Financeiro";

	$from_fabrica           = "no_reply@acaciaeletro.com.br";
    $from_fabrica_descricao = "Acáciaeletro";

	$mailer->IsSMTP();
    $mailer->IsHTML();
    $mailer->AddAddress("ronald.santos@telecontrol.com.br");
    $mailer->SetFrom($from_fabrica,$from_fabrica_descricao);
    $mailer->Subject = $assunto;
    $mailer->Body = $mensagem;

    $mailer->Send();

	exit;

}

$btn_acao     = $_POST['btn_acao'];

if($btn_acao == "Gravar"){
	$posto_codigo = $_POST['posto_codigo'];
	$posto_nome   = $_POST['posto_nome'];
	$arquivo      = $_FILES['arquivo'];
	/*$vencimento   = $_POST['vencimento'];
	$nota_fiscal  = $_POST['nota_fiscal'];
	$valor        = $_POST['valor'];*/

	if(strlen($arquivo['name']) > 0){

		$postos = file_get_contents($arquivo['tmp_name']);
		$postos = explode("\n", $postos);

		pg_query("BEGIN",$con);

		$sql = "DELETE FROM tbl_telecontrol_inadimplencia";
		$res = pg_query($con,$sql);
		$postos = array_filter($postos);
		
		foreach ($postos as $key => $value) {

			list($cnpj,$nome) = explode(";", $value);
			$cnpj_erro = $cnpj;
			$cnpj = str_replace(".", "", $cnpj);
			$cnpj = str_replace("-", "", $cnpj);
			$cnpj = str_replace("/", "", $cnpj);
			$cnpj = str_replace("\"","",$cnpj);
			$cnpj = substr($cnpj,0,8);

			if(empty($cnpj)) continue;

			$sql = "SELECT posto FROM tbl_posto WHERE cnpj like '{$cnpj}%'";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) == 0){
				$msg_erro[] = $cnpj_erro." - ".$nome. "não encontrado";
			}else{
				for($i = 0; $i < pg_num_rows($res); $i++){
					
					$posto = pg_fetch_result($res, $i, 'posto');

					$sql = "INSERT INTO tbl_telecontrol_inadimplencia(posto,nota_fiscal,vencimento,valor) VALUES($posto,'12345',CURRENT_DATE,0)";
					$resP = pg_query($con,$sql);

					if( strlen(pg_last_error()) > 0 ){
						$msg_erro[] = pg_last_error();
					}
				}
			}
		}

		if(count($msg_erro) > 0){
			pg_query("ROLLBACK",$con);
		}else{
			pg_query("COMMIT",$con);
			header("Location: posto_atraso_pagamento.php?msg=ok");
		}

	}else{

		if(empty($posto_codigo)){
			$msg_erro[] = "Todos os campos são de preenchimento obrigatório";
		}

		if(count($msg_erro) == 0){

			$cnpj = str_replace(".", "", $posto_codigo);
			$cnpj = str_replace("-", "", $cnpj);
			$cnpj = str_replace("/", "", $cnpj);
			$cnpj = substr($cnpj,0,8);

			$sql = "SELECT posto FROM tbl_posto WHERE cnpj like '{$cnpj}%'";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) == 0){
				$msg_erro[] = "Posto não encontrado";
			}else{
				
				pg_query("BEGIN",$con);

				$posto = pg_fetch_result($res, 0, 'posto');
				
				for($i = 0; $i < pg_num_rows($res); $i++){

					$posto = pg_fetch_result($res, $i, 'posto');

					$sql = "INSERT INTO tbl_telecontrol_inadimplencia(posto,nota_fiscal,vencimento,valor) VALUES($posto,'12345',CURRENT_DATE,0)";
					$resP = pg_query($con,$sql);

					if( strlen(pg_last_error()) > 0 ){
						$msg_erro[] = pg_last_error();
					}
				}

				if(count($msg_erro) == 0){
					pg_query("COMMIT",$con);
					header("Location: posto_atraso_pagamento.php?msg=ok");
				}else{
					pg_query("ROLLBACK",$con);
				}
			}	

		}

	}

}

?>

<html>
<head>
<title>Postos em atraso</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>


<?php
	 include 'menu.php';

	 include '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>

<style>
	.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.sucesso td a{
    color:white !important;
    text-decoration:underline !important;
}

table.sucesso td a:hover{
    color: #C6E2FF !important;
    text-decoration:underline;
}

span{
	font-size: 11px;
	font-weight: bold;
}

</style>

<script type="text/javascript">

	function formatItem(row) {
		return row[1] + " - " + row[0];
	}

	$(document).ready(function()
    {
        $('#vencimento').datepick({startDate:'01/01/2000'});
        $("#nota_fiscal").numeric();
        $('#valor').maskMoney({symbol:"", decimal:",", thousands:'.', precision:2, maxlength: 15});
        
		/* Busca pelo Nome */
		$("#posto_nome, #posto_codigo").autocomplete("<?echo $PHP_SELF; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#posto_nome, #posto_codigo").result(function(event, data, formatted) {
			$("#posto_nome").val(data[0]) ;
			$("#posto_codigo").val(data[1]) ;
		});

		$(".aprovar").click(function(){

			var inadimplencia = $(this).attr("rel");
			var obj = $(this);
			var total = 0;

			$.ajax({

				url: "<?echo $PHP_SELF; ?>",
				type: "GET",
				data: {aprova:"sim",inadimplencia:inadimplencia},
				complete: function(data){

					if(data.responseText == "ok"){
						$(obj).parents("tr").remove();

						$("input[name^=valor_]").each(function(){
							total = parseFloat(total) + parseFloat( $(this).val() );
						});

						$(".total").html(total.toFixed(2).replace(".",","));
					}

				}

			});
		});

		$(".email").click(function(){
			
			var posto = $(this).attr("rel");

			$.ajax({

				url: "<?echo $PHP_SELF; ?>",
				type: "GET",
				data: {email:"sim",posto:posto},
				complete: function(data){

					if(data.responseText == "ok"){
						
					}

				}

			});

		});
	});
</script>

<center><h1>Postos em Atraso</h1></center>
	
	<?php
		if(count($msg_erro) > 0){
	?>
			<table align="center" width="700">
				<tr class="msg_erro">
					<td>
						<?php
							foreach ($msg_erro as $msg) {
								echo $msg . "<br>";
							}
						?>
					</td>
				</tr>
			</table>
	<?php
		}
	?>

	<?php
		if(isset($_GET['msg'])){
	?>
			<table align="center" width="700">
				<tr class="sucesso">
					<td>Gravado com sucesso</td>
				</tr>
			</table>
	<?php
		}
	?>

	<form name='frm_pesquisa' width="700" action='<?=$PHP_SELF?>' method='POST' enctype="multipart/form-data">
	<table align='center' class="formulario">
		<caption class="titulo_tabela">Pesquisa</caption>
		<tr>
			<td>CNPJ do Posto</td>
			<td>Nome do Posto</td>
		</tr>
		<tr>
			<td><input type='text' name='posto_codigo' id='posto_codigo' size='15' value='<?=$posto_codigo?>'></td>
			<td><input type='text' name='posto_nome'  id='posto_nome' size='30' value='<?=$posto_nome?>'></td>
		</tr>
		<!-- <tr>
			<td>Nota Fiscal</td>
			<td>Valor</td>
			<td>Vencimento</td>
		</tr>
		<tr>
			<td><input type='text' name='nota_fiscal' id='nota_fiscal' size='12' maxlength='10' value='<?=$nota_fiscal?>'></td>
			<td><input type='text' name='valor' id='valor' size='12' maxlength='10' value='<?=$valor?>'></td>
			<td><input type='text' name='vencimento' id='vencimento' size='12' maxlength='10' value='<?=$vencimento?>'></td>
		</tr> -->

		<tr>
			<td colspan="2" align="center">
				<br />
				<span> Caso queria anexar uma arquivo, deve segurir o seguinte layout (CNPJ;Nome Posto). <br> Anexar apenas arquivos TXT ou CSV.</span>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<br />
				<input type="file" name="arquivo" />
			</td>
		</tr>

		<tr>
			<td colspan='3' align='center'>
				<br />
				<input type='submit' name='btn_acao' value='Gravar'>
			</td>
		</tr>
	</table>
</form>
<?

//if ($btn_acao == 'Pesquisar'){

	$sql = "SELECT 	tbl_posto.cnpj,
					tbl_posto.nome,
					tbl_posto.fone, 
					tbl_posto.email,
					tbl_telecontrol_inadimplencia.inadimplencia,
					tbl_telecontrol_inadimplencia.posto,
					tbl_telecontrol_inadimplencia.valor,
					to_char(tbl_telecontrol_inadimplencia.vencimento,'DD/MM/YYYY') AS vencimento,
					tbl_telecontrol_inadimplencia.nota_fiscal
			FROM tbl_posto
			JOIN tbl_telecontrol_inadimplencia USING(posto)
			WHERE tbl_telecontrol_inadimplencia.recebimento ISNULL";
	
	$res = pg_exec ($con,$sql);

	if(pg_num_rows($res) > 0){
		echo "<br><table width='700' align='center' class='tabela' cellpadding='1' cellspacing='1'>";
			echo "<tr class='titulo_coluna'>";
				echo "<td width=180>CNPJ - Posto</td>";
				echo "<td width=80>Fone/Email</td>";		
				echo "<td width=70>Ações</td>";			
			echo "</tr>";

		$total = 0 ;
		$ja_imprimiu = array();

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$posto         = pg_result ($res,$i,posto);
			$inadimplencia = pg_result ($res,$i,inadimplencia);
			$cnpj          = pg_result ($res,$i,cnpj);
			$nome          = pg_result ($res,$i,nome);
			$fone          = pg_result ($res,$i,fone);
			$email         = pg_result ($res,$i,email);
			$nota_fiscal   = pg_result ($res,$i,nota_fiscal);
			$vencimento    = pg_result ($res,$i,vencimento);
			$valor         = pg_result ($res,$i,valor);

			$total += $valor;

			$cor = "#cccccc";
			if ($i % 2 == 0) $cor = '#eeeeee';
			
			echo "<tr bgcolor='$cor'>";

				echo "<td nowrap>";
					echo $cnpj." - ".$nome;
				echo "</td>";
				
				echo "<td width=80>";
				echo $fone."<br>".str_replace(array(";"),"<br>",$email);
				echo "</td>";
				
				echo "<td  align='center' nowrap>";
				echo "<input type='button' value='Aprovar' class='aprovar' rel='$inadimplencia' />";
				//echo "<input type='button' value='Enviar Email' class='email' rel='$posto' />";
				echo "</td>";

			echo "</tr>";
		}

		$total = number_format ($total,2,",",".");

		echo "</table>";
	}
//}
?>
<? include "rodape.php"; ?>

</body>
</html>
