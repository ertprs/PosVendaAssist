<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if (empty($_GET['data_inicial']) or empty($_GET['data_final']) or empty($_GET['posto'])) {
	die('Acesso negado');
}

$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];
$posto        = $_GET['posto'];

list($yi, $mi, $di) = explode('-', $data_inicial);
list($yf, $mf, $df) = explode('-', $data_final);

if( !checkdate($mf,$df,$yf) || !checkdate($mi,$di,$yi) ) {

	die('Data Inválida');

}

$aux_data_inicial = $data_inicial;
$aux_data_final   = $data_final;

if(strtotime($aux_data_final) < strtotime($aux_data_inicial) or strtotime($aux_data_final) > strtotime('today')){
    die('Data Inválida');
} 

if (strtotime($aux_data_inicial.'+3 year') < strtotime($aux_data_final) && empty($msg_erro) ) {
	die('O intervalo entre as datas não pode ser maior que 3 anos');
}

$cond[] = "tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";


?>

<style type="text/css">
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
	/* Mensagens de erro */
	.msg_erro{
	        background-color:#FF0000;
	        font: bold 14px "Arial";
	        color:#FFFFFF;
	        text-align:center;
	}

	.formulario{
	        background-color:#D9E2EF;
	        font:11px Arial;
	        text-align:left;
	}
	
	.formulario > form > table {
		width:300px;
		margin:auto;
	}

	table.tabela tr td{
	        font-family: verdana;
	        font-size: 11px;
	        border-collapse: collapse;
	        border:1px solid #596d9b;
		text-align:center;
	}

	label {display:block;}

	.sucesso{
	    background-color:#008000;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    text-align:center;
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
	.subtitulo{
	    background-color: #7092BE;
	    font:bold 11px Arial;
	    color: #FFFFFF;
	}

	#wrapper {
		width:700px;
		margin:auto;
	}

	input.input{
		width:80px;
	}

</style>

<div id="wrapper">

		<?php 
		$sql = "SELECT 
					tbl_os.os,
					tbl_posto_fabrica.codigo_posto || tbl_os.sua_os AS sua_os, 
					TO_CHAR (tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_digitacao,
					TO_CHAR (tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
					tbl_os.mao_de_obra,
					tbl_os.pecas
				FROM tbl_os
				
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
				JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os AND tbl_os_extra.extrato IS NULL
				WHERE tbl_os.fabrica = $login_fabrica
					AND tbl_os.posto = $posto
					AND finalizada IS NOT NULL
					AND excluida IS NOT TRUE
					AND data_fechamento IS NOT NULL
					AND " . implode (" AND ", $cond) .
				"ORDER BY data_digitacao";

		$res = pg_query($con, $sql);

		if ( pg_num_rows($res) ) {

		?>
				<table class="tabela" border="0" cellpadding="2" cellspacing="1" width="700">

					<thead>
						<tr class="titulo_coluna">
							<th>OS</th>
							<th>Data Digitação</th>
							<th>Data de Fechamento</th>
							<th>Peças</th>
							<th>Mão-de-obra</th>
							<th>Total</th>
						</tr>
					</thead>
					<tbody>

						<?php
						     $total = 0; 
						     for ($i = 0; $i < pg_num_rows($res); $i++) : 

								$cor 		 = ($i % 2) ? "#F7F5F0" : "#F1F4FA";  
								$os 		 = pg_result($res,$i,'os');
								$sua_os 	 = pg_result($res,$i, 'sua_os');
								$pecas       = pg_fetch_result($res, $i, 'pecas');
								$mao_de_obra = pg_result($res,$i,'mao_de_obra');
								$total_os    = $pecas + $mao_de_obra;
								$total 		+= $total_os;
						?>

							<tr bgcolor="<?=$cor?>">
								<td><a href="os_press.php?os=<?=$os?>" target="_blank"><?=$sua_os?></a></td>
								<td><?=pg_result($res,$i,'data_digitacao')?></td>
								<td><?=pg_result($res,$i, 'data_fechamento')?></td>
								<td align="right"><?=number_format($pecas, 2, ',', '.')?></td>
								<td align="right"><?=number_format($mao_de_obra, 2, ',', '.')?></td>
								<td align="right"><?=number_format($total_os, 2, ',', '.')?></td>
							</tr>

						<?php endfor; ?>
						
						<tr class="titulo_coluna" >
							<td colspan="5" align="center">TOTAL</td>
							<td><?=number_format($total, 2, ',', '.')?></td>
						</tr>
						
					</tbody>
				</table>

		<?php } else echo 'Nenhum resultado encontrado para esta pesquisa'; ?>

</div>

