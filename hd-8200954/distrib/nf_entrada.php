<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once _DIR_ . '/../../class/ComunicatorMirror.php';

#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>

<?php 

if (isset($_POST['encaminhar_nfs'])) { 

	$fabrica        = $_POST['fabrica'];
	$tipoNotaFiscal = $_POST['tipo_nf'];
	$email          = $_POST['email'];
	$faturamentos   = $_POST['fats'];
	$condFat = " AND tbl_faturamento.faturamento IN(".implode(',',$faturamentos).") ";
	$left = ' LEFT ';

	if (strlen($fabrica) > 0) {

		$condFabricante = " JOIN tbl_posto_fabrica 
								ON tbl_posto.posto = tbl_posto_fabrica.posto
								AND tbl_posto_fabrica.fabrica = {$fabrica} ";

		$left = '';
	}

	if (strlen($tipoNotaFiscal) > 0) {

		$condConferencia = ($tipoNotaFiscal == 'conferida') ? 
			' AND tbl_faturamento.conferencia IS NOT NULL ' : 
			' AND tbl_faturamento.conferencia IS NULL ';

		$condConferencia .= " AND tbl_faturamento.emissao > CURRENT_DATE - INTERVAL '90 days' ";
	}

	#Consulta Conferidas:

	if ($tipoNotaFiscal == 'conferidas') {

		$cabecalhoEmail = ['Nota Fiscal', 'Emissão', 'Transportadora', 'Total'];

		$query = "SELECT tbl_faturamento.nota_fiscal ,
						   to_char (tbl_faturamento.emissao,'DD/MM/YYYY') as emissao ,
						   tbl_faturamento.transp ,
						   to_char (tbl_faturamento.total_nota,'999999.99') as total_nota
				   	FROM tbl_faturamento
					JOIN tbl_posto on tbl_posto.posto = tbl_faturamento.distribuidor
					{$left} JOIN tbl_posto_extra on tbl_posto.posto = tbl_posto_extra.posto
					{$condFabricante}
					WHERE tbl_faturamento.posto = {$login_posto}
					AND (tbl_faturamento.distribuidor IS NULL 
						OR (tbl_faturamento.distribuidor IS NOT NULL 
							AND tbl_posto_extra.fornecedor_distrib IS TRUE)
							OR (tbl_faturamento.distribuidor = {$login_posto} 
					AND (tbl_faturamento.cfop like '19%' or tbl_faturamento.cfop like '29%')))
					AND tbl_faturamento.conferencia IS NOT NULL
					$condFat
					ORDER BY tbl_faturamento.emissao DESC, tbl_faturamento.nota_fiscal DESC";
							//#AND tbl_faturamento.nota_fiscal IN ('$tipoNotaFiscal')
	} else {
		
		#Não Conferidas

		$cabecalhoEmail = ['Referência Peça', 'Descrição Peça', 'Preço', 'Qtde NF', 'Nota Fiscal', 'Emissão', 'Transportadora', 'Total'];

		$query = "SELECT tbl_faturamento.nota_fiscal ,
						to_char (tbl_faturamento.emissao,'DD/MM/YYYY') as emissao ,
						tbl_faturamento.transp ,
						to_char (tbl_faturamento.total_nota,'999999.99') as total_nota,
						to_char (tbl_faturamento_item.preco,'999999.99') as preco,
						tbl_faturamento_item.qtde,
						tbl_peca.referencia,
						tbl_peca.descricao
				  FROM tbl_faturamento
				  JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
				  JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
				  JOIN tbl_posto ON tbl_posto.posto = tbl_faturamento.distribuidor
				  LEFT JOIN tbl_posto_extra ON tbl_posto.posto = tbl_posto_extra.posto
				  {$condFabricante}
				  WHERE tbl_faturamento.posto = {$login_posto}
					AND (tbl_faturamento.distribuidor IS NULL 
						OR (tbl_faturamento.distribuidor IS NOT NULL 
							AND tbl_posto_extra.fornecedor_distrib IS TRUE)
				  OR (tbl_faturamento.distribuidor = {$login_posto} 
				  AND (tbl_faturamento.cfop LIKE '19%' OR tbl_faturamento.cfop LIKE '29%')))
				  AND tbl_faturamento.conferencia IS NULL	
				  $condFat
				  ORDER BY tbl_faturamento.emissao DESC, 
						     tbl_faturamento.nota_fiscal DESC";

		#AND tbl_faturamento.nota_fiscal IN($notas_fiscais)
	} 
	$res = pg_query($con, $query);

	if (strlen(pg_last_error()) > 0) { 

		exit(json_encode(['msg' => 'error']));
	}

	$corpoEmail = "<table width='600' border=1><tr>";

	foreach ($cabecalhoEmail as $coluna) {
		
		$corpoEmail .= "<td>" . utf8_encode($coluna) . "</td>";
	}

	$totalRows = pg_num_rows($res);
	for ($i = 0; $i < $totalRows; $i++) {
		
		$corpoEmail .= '<tr>';

		$notaFiscal = pg_fetch_result($res, $i, 'nota_fiscal');

		$emissao = pg_fetch_result($res, $i, 'emissao');
		$transp  = pg_fetch_result($res, $i, 'transp');
		$total   = pg_fetch_result($res, $i, 'total_nota'); 
		
		if ($tipoNotaFiscal != 'conferidas') {
			
			$refPeca  = pg_fetch_result($res, $i, 'referencia');
			$descPeca = pg_fetch_result($res, $i, 'descricao');
			$preco    = pg_fetch_result($res, $i, 'preco');
			$qtdNf    = pg_fetch_result($res, $i, 'qtde');

			$corpoEmail .= "<td>{$refPeca}</td>";
			$corpoEmail .= "<td>" . utf8_encode($descPeca) . "</td>";
			$corpoEmail .= "<td>{$preco}</td>";
			$corpoEmail .= "<td>{$qtdNf}</td>";
		}

		$corpoEmail .= "<td>{$notaFiscal}</td>";
		$corpoEmail .= "<td>{$emissao}</td>";
		$corpoEmail .= "<td>{$transp}</td>";
		$corpoEmail .= "<td>{$total}</td>";

		$corpoEmail .= '</tr>';
	}

	$corpoEmail .= '</tr></table>';

	$tituloEmail = 'Relatorio Nota Fiscal';
	
  	$comunicatorMirror = new ComunicatorMirror();
	$comunicatorMirror->post($email, $tituloEmail, $corpoEmail, 'smtp@posvenda');

	exit(json_encode(['msg' => 'success']));
} ?>


<html>
<head>
<title>Conferência de NF de Entrada</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>
<?
include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>
<script>

function encaminhaNf() {

	var email   = $('#encaminhar_email').val();
	var fabrica = $('#encaminhar_fabrica').val();
	var tipo_nf = $('#tipo_nf').val();
	var fats = [];

	$("input[name^=agrupada_]:checked").each(function(){
		fats.push($(this).val());
	});

	if(fats.length == 0) {
		alert("Selecione pelo menos uma Nota Fiscal");
	}

	if (email.length == 0) {

		alert('Campo e-mail não pode estar em branco para encaminhar relatório');

	} else { 

		$.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                encaminhar_nfs: true,
                email: email,
                fabrica: fabrica,
                tipo_nf: tipo_nf,
		fats: fats
            }
        }).done(function(data){

            if (data.msg == "success") {
                
                alert("E-mail enviado com sucesso!");

            } else {

            	alert('Erro ao enviar e-mail!');
            }

        }).fail(function() {

	        alert("Não foi possí­vel realizar a operação.");
	    });
	}
}

$(document).ready(function()
    {
        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});
});
</script>

<body>

<? include 'menu.php' ?>

<center><h1>Conferência de NF de Entrada</h1></center>

<center><a href="nf_divergente.php">Clique aqui para ver os itens de NF com divergência</a></center>

<form name='nf_entrada' method='POST' action='<? echo $PHP_SELF?>'>
<?
echo "</table>\n";
echo "<br><table align='center'>
		<tr>
			<td >Data Inicial</td>
			<td><input type='text'  name='data_inicial' id='data_inicial' class='frm' value='$data_inicial'></td>
			<td >Data Final</td>
			<td><input type='text'  name='data_final'   id='data_final' class='frm'  value='$data_final'></td></tr>";
echo "  <tr>
			<td >Nota Fiscal</td>
			<td><input type='text'  name='nota_fiscal_busca' class='frm' value='$nota_fiscal_busca'></td>
			<td >CNPJ</td>
			<td><input type='text'  name='cnpj' class='frm'  value='$cnpj'></td></tr>";

$fabricas = "SELECT fabrica,nome 
			 FROM tbl_fabrica 
			 WHERE ativo_fabrica IS TRUE 
			 AND parametros_adicionais::jsonb->>'telecontrol_distrib' = 't';";

$resFabricas = pg_query($con, $fabricas); ?> 

<tr>
	<td>Fábrica</td>
	<td>
		<select id="encaminhar_fabrica" name="encaminhar_fabrica" class="frm">
			<option value="">Selecione</option>

			<?php for ($i = 0; $i < pg_num_rows($resFabricas); $i++) { 

				$fabricasInfo = pg_fetch_object($resFabricas); 

				if ($fabricasInfo->fabrica == $_POST['encaminhar_fabrica']) { 

					$selected = 'selected';
				} ?>

				<option <?= $selected ?> value="<?=$fabricasInfo->fabrica?>">

					<?= $fabricasInfo->nome ?>

				</option>
				
				<?php $selected = ''; 

			} ?>

		</select>
	</td>
	<td>
		Tipo Nota Fiscal
	</td>
	<td>
		<select id="tipo_nf" name="tipo_nota_fiscal" class="frm">
			<option value="">Selecione</option>
			<option <?= ($_POST['tipo_nota_fiscal'] == 'conferidas') ? 'selected' : ''; ?> value="conferidas">
				Conferidas
			</option>
			<option <?= ($_POST['tipo_nota_fiscal'] == 'faltando_itens') ? 'selected' : ''; ?> value="faltando_itens">
				Faltando Itens
			</option>
		</select>
	</td>
</tr> 
<br>
<?php

echo "<tr><td colspan='4' align='center'><center><input type='submit' name='btn_procura' value='Procurar'></center></td></tr></table>";
echo "</form>";

?>

<?php if (!empty($_POST['tipo_nota_fiscal']) && !empty($_POST['encaminhar_fabrica'])) { ?>
	<center>
		<h2>Encaminhar dados ao Fábricante:</h2>
	</center>
	<table width='400' align='center'>
		<tr>
			<td height="60">E-mail</td>
			<td>
				<input id="encaminhar_email" type="text" name="encaminhar_email">
			</td>
			<td colspan='4' align='center'>
				<center>
					<button class="btn" onclick="encaminhaNf()">Enviar</button>
				</center>
			</td>
		</tr>
	</table>
	<br>
<?php } ?>
<p>
<center><b>Suas notas ainda não conferidas</b></center>


<table width='500' align='center'>
<tr bgcolor='#FF9933' style='color:#ffffff ; font-weight:bold'>
	<td align='center'>&nbsp;</td>
	<td align='center'>OK</td>
	<td align='center'>Fábrica</td>
	<td align='center'>Fornecedor</td>
	<td align='center'>Nota Fiscal</td>
	<td align='center'>Emissão</td>
	<td align='center'>CFOP</td>
	<td align='center'>Transp.</td>
	<td align='center'>Total</td>
</tr>

<form name='nf_entrada' method='post' action='nf_entrada_item.php'>

<?


/*alterado em 22/09/2011 por waldir e marisa, pois mostrava todas as notas do posto 4311 e deste posto só pode mostrar entrada;

antes:

AND     (tbl_faturamento.distribuidor IS NULL OR (tbl_faturamento.distribuidor IS NOT NULL AND tbl_posto_extra.fornecedor_distrib IS TRUE))


depois:
AND     (tbl_faturamento.distribuidor IS NULL OR
						 (tbl_faturamento.distribuidor IS NOT NULL AND tbl_posto_extra.fornecedor_distrib IS TRUE)
					      OR (tbl_faturamento.distribuidor = 4311 AND (tbl_faturamento.cfop like '19%' or tbl_faturamento.cfop like '29%')))
*/

$btn_procura = $_POST["btn_procura"];
if($btn_procura == "Procurar"){
	$nota_procura = $_POST["nota_fiscal_busca"];
	if(!empty($nota_procura)) {
		$cond_nf = " AND     tbl_faturamento.nota_fiscal = '$nota_procura' ";
	}
	$cnpj         = $_POST["cnpj"];
	if(!empty($cnpj)) {
		$cnpj = preg_replace('/\D/','',$cnpj);
		$cond_cnpj = " AND     tbl_posto.cnpj = '$cnpj'  ";
	}
	$data_inicial = $_POST["data_inicial"];
	$data_final   = $_POST["data_final"];
	$tipoNotaFiscal = $_POST['tipo_nota_fiscal'];
	$pesquisaFabrica = $_POST['encaminhar_fabrica'];
	
	$cond_fabrica = " AND tbl_faturamento.fabrica <> 0";

	if (strlen($pesquisaFabrica) > 0) {
		 $condFabricante = " JOIN tbl_posto_fabrica on tbl_faturamento.distribuidor = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = {$pesquisaFabrica} ";
	}

	if(!empty($data_inicial) && !empty($data_final)) {

		list($di,$mi,$yi) = explode("/",$data_inicial);
		list($df,$mf,$yf) = explode("/",$data_final);

		$data_ini = "$yi-$mi-$di";
		$data_fim = "$yf-$mf-$df";
		$cond_datas = "AND tbl_faturamento.emissao BETWEEN '$data_ini' and '$data_fim' ";
	}

	$sem_conferencia = $_POST['tipo_nota_fiscal'];

	if (strlen($sem_conferencia) > 0) {

		$cond_sem_conferencia = ($sem_conferencia == "conferidas") ? " AND tbl_faturamento.conferencia IS NOT NULL" : " AND tbl_faturamento.conferencia IS NULL";

		if(strlen($cond_datas) == 0){
			$cond_sem_conferencia .= " AND tbl_faturamento.emissao > CURRENT_DATE - INTERVAL '90 days' ";
		}

	}

	if(empty($nota_procura) and empty($data_inicial)) {
		$cond ="		AND     tbl_faturamento.emissao > CURRENT_DATE - INTERVAL '90 days' ";
	}

	$sql = "SELECT tbl_faturamento.faturamento ,
				tbl_fabrica.nome AS fabrica_nome ,
				tbl_faturamento.nota_fiscal ,
				to_char (tbl_faturamento.emissao,'DD/MM/YYYY') as emissao ,
				to_char (tbl_faturamento.conferencia,'DD/MM/YYYY') as conferencia ,
				to_char (tbl_faturamento.cancelada,'DD/MM/YYYY') as cancelada ,
				tbl_faturamento.cfop ,
				tbl_faturamento.transp ,
				tbl_transportadora.nome AS transp_nome ,
				tbl_transportadora.fantasia AS transp_fantasia ,
				to_char (tbl_faturamento.total_nota,'999999.99') as total_nota,
				tbl_posto.nome as fornecedor_distrib
		FROM    tbl_faturamento
		JOIN    tbl_fabrica USING (fabrica)
		$condFabricante
		LEFT JOIN tbl_posto on tbl_posto.posto = tbl_faturamento.distribuidor 
		LEFT JOIN tbl_posto_extra on tbl_posto.posto = tbl_posto_extra.posto
		LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora = tbl_faturamento.transportadora
		WHERE   tbl_faturamento.posto = $login_posto
		AND     (tbl_faturamento.distribuidor IS NULL OR
						 (tbl_faturamento.distribuidor IS NOT NULL AND tbl_posto_extra.fornecedor_distrib IS TRUE)
					      OR (tbl_faturamento.distribuidor = 4311 AND (tbl_faturamento.cfop like '19%' or tbl_faturamento.cfop like '29%')))
		$cond
		$cond_nf
		$cond_cnpj
		$cond_datas
		$cond_sem_conferencia
		$cond_fabrica
		ORDER BY tbl_faturamento.emissao DESC, tbl_faturamento.nota_fiscal DESC ";
}else{
	$sql = "SELECT	tbl_faturamento.faturamento ,
				tbl_fabrica.nome AS fabrica_nome ,
				tbl_faturamento.nota_fiscal ,
				to_char (tbl_faturamento.emissao,'DD/MM/YYYY') as emissao ,
				to_char (tbl_faturamento.conferencia,'DD/MM/YYYY') as conferencia ,
				to_char (tbl_faturamento.cancelada,'DD/MM/YYYY') as cancelada ,
				tbl_faturamento.cfop ,
				tbl_faturamento.transp ,
				tbl_transportadora.nome AS transp_nome ,
				tbl_transportadora.fantasia AS transp_fantasia ,
				to_char (tbl_faturamento.total_nota,'999999.99') as total_nota,
				tbl_posto.nome as fornecedor_distrib
		FROM    tbl_faturamento
		JOIN    tbl_fabrica USING (fabrica)
		LEFT JOIN tbl_posto on tbl_posto.posto = tbl_faturamento.distribuidor 
		LEFT JOIN tbl_posto_extra on tbl_posto.posto = tbl_posto_extra.posto
		LEFT JOIN tbl_transportadora USING (transportadora)
		WHERE   tbl_faturamento.posto = $login_posto
		AND     (tbl_faturamento.distribuidor IS NULL OR
						 (tbl_faturamento.distribuidor IS NOT NULL AND tbl_posto_extra.fornecedor_distrib IS TRUE)
					      OR (tbl_faturamento.distribuidor = 4311 AND (tbl_faturamento.cfop like '19%' or tbl_faturamento.cfop like '29%')))
	 	AND     tbl_faturamento.fabrica <> 0
		AND     tbl_faturamento.emissao > CURRENT_DATE - INTERVAL '60 days'
		ORDER BY tbl_faturamento.emissao DESC, tbl_faturamento.nota_fiscal DESC ";
}
$res = pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$conferencia      = trim(pg_result($res,$i,conferencia)) ;
	$faturamento      = trim(pg_result($res,$i,faturamento)) ;
	$fabrica_nome     = trim(pg_result($res,$i,fabrica_nome)) ;
	$nota_fiscal      = trim(pg_result($res,$i,nota_fiscal));
	$emissao          = trim(pg_result($res,$i,emissao));
	$cancelada        = trim(pg_result($res,$i,cancelada));
	$cfop             = trim(pg_result($res,$i,cfop));
	$transp           = trim(pg_result($res,$i,transp));
	$transp_nome      = trim(pg_result($res,$i,transp_nome));
	$transp_fantasia  = trim(pg_result($res,$i,transp_fantasia));
	$total_nota       = trim(pg_result($res,$i,total_nota));
	$fornecedor_distrib = trim(pg_result($res,$i,fornecedor_distrib));

	if (strlen ($transp_nome) > 0) $transp = $transp_nome;
	if (strlen ($transp_fantasia) > 0) $transp = $transp_fantasia;
	$transp = strtoupper ($transp);

	
	$cor = "#ffffff";
	if ($i % 2 == 0) $cor = "#FFEECC";

	
	if (strlen ($cancelada) > 0) $cor = '#FF6633';

	echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";

	if (strlen ($conferencia) > 0) {
		$conferencia = "OK";
	}else{
		$conferencia = "--";
	}
	echo "<td align='left' nowrap>";
	echo "<input type='checkbox' name='agrupada_$i' value='$faturamento'>" ;
	echo "</td>\n";
	echo "<td align='left' nowrap>$conferencia</td>\n";
	echo "<td align='left' nowrap>$fabrica_nome</td>\n";
	echo "<td align='left' nowrap>$fornecedor_distrib</td>\n";
	echo "<td align='left' nowrap><a href='nf_entrada_item.php?faturamento=$faturamento'>$nota_fiscal</a></td>\n";
	echo "<td align='left' nowrap>$emissao</td>\n";
	echo "<td align='left' nowrap>$cfop</td>\n";
	echo "<td align='left' nowrap>$transp</td>\n";
	$total_nota = number_format ($total_nota,2,',','.');
	echo "<td align='right' nowrap>$total_nota</td>\n";
	echo "</tr>\n";
}

echo "</table>\n";
echo "<input type='hidden' name='qtde_nf' value='$i'>";
echo "<center><input type='submit' name='btn_conf' value='Conferir Agrupado'></center>";

echo "</form>";
?>

<p>

</body>
<?
include'rodape.php';
?>
