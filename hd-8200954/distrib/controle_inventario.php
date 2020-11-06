<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$fabrica    = trim($_GET["fabrica"]);
$tipo       = trim($_GET["tipo"]);
$referencia = trim($_GET['referencia']);
$descricao  = trim($_GET['descricao']);
$data_inicial  = trim($_GET['data_inicial']);
$data_final  = trim($_GET['data_final']);
$arr_data_inicial = explode('/', $data_inicial);
$arr_data_final = explode('/', $data_final);

$xdata_inicial = implode('-', array_reverse($arr_data_inicial));
$xdata_final = implode('-', array_reverse($arr_data_final));



$msg_erro = array();
$data_final=date('Y-m-d');
if (strlen($fabrica) > 0) {
	$fabrica = intval($fabrica);
	$sql = "SELECT nome FROM tbl_fabrica WHERE fabrica=$fabrica";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {
		$fabrica_nome = pg_fetch_result($res, 0, nome);

		if (strlen($referencia) > 0) {
			$sql = "SELECT peca, descricao FROM tbl_peca WHERE referencia='$referencia' AND fabrica=$fabrica";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res)) {
				$peca = pg_fetch_result($res, 0, peca);
				$descricao = pg_fetch_result($res, 0, descricao);
			}
			else {
				$msg_erro[] = "Referência da peça informada inválida";
			}
		}
	}
}
elseif (isset($_GET["fabrica"])) {
	$msg_erro[] = "Informe a fábrica para gerar o relatório";
}

if (strlen($tipo) > 0) {
	if ($tipo != "peca" && $tipo != "produto") {
		$tipo = "";
	}
}

if(isset($_GET['btn_acao'])) {
	if(empty($data_inicial) or empty($data_final)) {
		$msg_erro = "informar intervelo de datas para pesquisa";
	}


}

$msg_erro = implode("<br>", $msg_erro);


?><html>
	<head>
	<title>Controle Inventário</title>
	<link rel="stylesheet" href="../js/tinybox2/style.css" />
	<link type="text/css" rel="stylesheet" href="css/css.css">
	<link type="text/css" rel="stylesheet" href="css/fixheadertable.css">
	<style>
	
	.warning {
		font-size: 16px;
   		color: #FFFFFF;
   		background-color: #FF0000;
   		padding: 10px;
   		width: 600px;

	}

	.numero {
		text-align: right;
		padding-right: 5px;
	}

	.btn_excel {
    cursor: pointer;
    width: 185px;
    margin: 0 auto;
	}

	.btn_excel span {
    display: inline-block;
	}

	.btn_excel span img {
    width: 20px;
    height: 20px;
    border: 0px;
    vertical-align: middle;
	}

	.span12,
  	.row-fluid .span12 {
    width: 100%;
    -webkit-box-sizing: border-box;
       -moz-box-sizing: border-box;
            box-sizing: border-box;
    }

    .btn_excel span.txt {
    color: #FFF; 
    font-size: 14px;
    font-weight: bold;
    border-radius: 4px 4px 4px 4px;
    border-width: 1px;
    border-style: solid;
    border-color: #4D8530;
    background: -moz-linear-gradient(top, #559435 0%, #63AE3D 72%);
    background: -webkit-linear-gradient(top, #559435 0%, #63AE3D 72%);
    background: -o-linear-gradient(top, #559435 0%, #63AE3D 72%);
    background: -ms-linear-gradient(top, #559435 0%, #63AE3D 72%);
    background: linear-gradient(top, #559435 0%, #63AE3D 72%);
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#559435', endColorstr='#63AE3D',GradientType=1 );
    line-height: 18px;
    padding-right: 3px;
    padding-left: 3px;
	}
		#container {
			width: 758px;
			margin: 0 auto;
			padding: 10px 20px;
			background: #fff;
		}
		#chooseDateForm li {
			list-style: none;
			padding: 5px;
			clear: both;
		}
		/*
		select {
			width: 100px;
		}
		*/

		
		input {
			/*width: 170px;*/

		}
		
		input.dp-applied {
			/*width: 140px;*/
			float: left;
			margin: 5px 0;
		}

		a.dp-choose-date {
			float: left;
			width: 16px;
			height: 16px;
			padding: 0;
			margin: 5px 3px 0;
			display: block;
			text-indent: -2000px;
			overflow: hidden;
			background: url(js/calendar.png) no-repeat; 
		}
		a.dp-choose-date.dp-disabled {
			background-position: 0 -20px;
			cursor: default;
		}

		#calendar-me {
			margin: 20px;
		}

	</style>
	</head>

	<body>

	<? include 'menu.php';
include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>
<script>

	$(function() {
        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});

		$('.mostra_esconde').hide();
		$("input[rel=numero]").keypress(function(e) {
			var c = String.fromCharCode(e.which);
			var allowed = '1234567890';
			if ((e.keyCode != 9 && e.keyCode != 8) && allowed.indexOf(c) < 0) return false;
		});

		$('#voltar').hide();

		function formatItem(row) {
			return row[0] + " - " + row[1] + " - " + row[2];
		}

		function formatResult(row) {
			return row[0];
		}


		$("#descricao").autocomplete("<?echo 'peca_consulta_ajax.php?busca=nome'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#descricao").result(function(event, data, formatted) {
			$("#referencia").val(data[1]) ;
			$("#descricao").val(data[2]) ;
		});

	});

	function mostraEsconde(){
		$('.mostra_esconde').toggle();

		if ($('#mostra').html() == 'Esconder Ca<u>m</u>pos(Meses)'){
			$('#mostra').html('<u>M</u>ostrar Campos(Meses)');
		}else{
			$('#mostra').html('Esconder Ca<u>m</u>pos(Meses)');
		}
	}

		function fixHeader() {
				$('#result').fixedHeaderTable({ 
						altClass: 'odd',
						width:          '100%',
						height:         '100%',
						themeClass:     'css',
						footer:          true,
						cloneHeadToFoot: true,
						autoResize:      true
				});

				$('#voltar').show();
		}

		function unfixHeader() {
				$('#result').fixedHeaderTable('destroy');
				$('#voltar').hide();
		}
	</script>

	<center><h1>Controle Inventário</h1></center>

	<p>
		<?

		if (strlen($msg_erro) > 0) {
			echo "<div style='border: 1px solid #DD0000; background-color: #FFDDDD; color: #DD0000; font-size: 11pt; margin-bottom: 10px; padding: 5px;'>$msg_erro</div>";
		}

		if (strlen($msg) > 0) {
			echo "<h1>$msg</h1>";
		}


		?>
	<center>
	<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='get'>
	<table>

		<tr>
			<td align='right'>Data Inicial</td>
			<td><input type='text' size='11' name='data_inicial' id='data_inicial' class="frm" value="<? echo $_REQUEST["data_inicial"]; ?>"></td>
			<td align='right'>Data Final</td>
			<td><input type='text' size='11' name='data_final'   id='data_final' class="frm"  value="<? echo $_REQUEST["data_final"]; ?>"></td>
		</tr>
		<tr>
			<td align='right'>Fábrica</td>
			<td align='left'>
			<?
			echo "<select style='width:150px;' name='fabrica' id='fabrica' class='frm'>";
				$sql = "SELECT fabrica,nome FROM tbl_fabrica WHERE fabrica IN ($telecontrol_distrib) ORDER BY nome";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					for($x = 0; $x < pg_numrows($res);$x++) {
						$aux_fabrica = pg_fetch_result($res,$x,fabrica);
						$aux_nome    = pg_fetch_result($res,$x,nome);
						echo "<option value='$aux_fabrica'" ;if($fabrica==$aux_fabrica) echo "selected"; echo ">$aux_nome</option>";
					}
				}
			echo "</select>";
			?>
			</td>
			<td align='right'>Tipo</td>
			<td>
			<select name="tipo" id="tipo" class='frm'>
				<option value="">Tudo</option>
				<option <? if ($tipo == "peca") echo "selected"; ?> value="peca">Peça</option>
				<option <? if ($tipo == "produto") echo "selected"; ?> value="produto">Produto</option>
			</select>
			</td>
		</tr>
		<tr>
			<td align='right'>Referência da Peça</td>
			<td><input type='text' size='10' name='referencia' id='referencia' class="frm" value="<? echo $referencia; ?>"></td>
			<td align='right'>Descrição da Peça</td>
			<td><input type='text' size='20' name='descricao'   id='descricao' class="frm" value="<? echo $descricao; ?>"></td>
		</tr>
		<tr>
			<td align='center' colspan='4'><input type='submit' name='btn_acao' id='btn_acao' value='Pesquisar'></td>
		</tr>
	</table>
	<br>
	</form>

	<?

if (strlen($msg_erro) == 0 && strlen($data_inicial) > 0 && strlen($data_final) > 0 && strlen($fabrica) > 0) {
	$sql = "select ('$xdata_final'::date - '$xdata_inicial'::date)/30";
	$res = pg_query($con,$sql);
	$media = pg_fetch_result($res,0, 0);

	if (strlen($peca) > 0) {
		$sql_peca = "AND tbl_peca.peca=$peca";
	}

	switch($tipo) {
		case "peca":
			$sql_produto_acabado = "AND tbl_peca.produto_acabado IS NOT TRUE";
		break;

		case "produto":
			$sql_produto_acabado = "AND tbl_peca.produto_acabado IS TRUE";
		break;

		default:
			$sql_produto_acabado = "";
	}

	 $sql = "




        SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque_localizacao.localizacao
          INTO TEMP tmp_fat_pos_est
          FROM tbl_faturamento
          JOIN tbl_faturamento_item          ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
          JOIN tbl_peca                      ON tbl_faturamento_item.peca   = tbl_peca.peca
          LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca               = tbl_posto_estoque_localizacao.peca
										    AND tbl_posto_estoque_localizacao.posto in ($login_distrib_postos,20682)
         WHERE tbl_peca.fabrica                    = $fabrica
		 AND tbl_faturamento.fabrica IN (10)
		 and tbl_faturamento.posto in ($login_distrib_postos, 20682)
		 and tbl_faturamento.emissao between '$xdata_inicial' and '$xdata_final'
		 $sql_produto_acabado
		 $sql_peca ;

		DELETE FROM tmp_fat_pos_est
		 WHERE peca IN (
				SELECT peca_de
				  FROM tbl_depara
				 WHERE tbl_depara.fabrica = $fabrica);

		DELETE FROM tmp_fat_pos_est
		 WHERE localizacao in ('FL','*FL');

		ALTER TABLE tmp_fat_pos_est ADD preco float;

        UPDATE tmp_fat_pos_est
           SET preco                = tbl_tabela_item.preco
          FROM tbl_tabela_item
          JOIN tbl_tabela USING (tabela)
	  WHERE fabrica              = $fabrica
	  AND tabela_garantia
           AND tmp_fat_pos_est.peca = tbl_tabela_item.peca;



		/* Acerto de estoque */
		ALTER TABLE tmp_fat_pos_est ADD estoque_total FLOAT;

		UPDATE tmp_fat_pos_est
		SET estoque_total = qtde
		FROM tbl_posto_estoque
		WHERE tbl_posto_estoque.peca = tmp_fat_pos_est.peca
		AND posto in ($login_distrib_postos,20682);

        SELECT DISTINCT peca, referencia, descricao, localizacao, preco, estoque_total
		  FROM tmp_fat_pos_est
         ORDER BY referencia ASC ;";

	$res_pecas = pg_query($con, $sql);

	$parts = explode("-", $xdata_final);
	$ano_final = intval($parts[0]);
	$mes_final = intval($parts[1]);

	$hora = time();

	 flush();

        $xlsdata = date ("d/m/Y H:i:s");

        system("rm /tmp/assist/controle_inventario_".$login_posto."_data_".$hora.".csv");
        $fp = fopen ("/tmp/assist/controle_inventario_".$login_posto."_data_".$hora.".csv","w");

        $cabecalho = array();



	echo "<button onclick='javascript:fixHeader()'>Fixar Cabeçalho</button>";
	echo "<div id='voltar'><button onclick='javascript:unfixHeader()'>Voltar</button></div>";
	echo "<br><form method='POST' action='$PHP_SELF' name='frm_resultado'>
		<input type='hidden' name='fabrica' value='$fabrica'>
		<table id='result' align='center' border='0' cellspacing='1' cellpadding='1'>";
	echo "<thead>";
	echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:13px' align='center'>";
	
	echo "<th nowrap>Referência</th>";
        $cabecalho[] = "Referência";
	echo "<th nowrap>Peça</th>";
		$cabecalho[] = "Peça";

	if ($fabrica == 81) {
		echo "<th style='width:70px;'>Entrada Total BestWay</th>";
			$cabecalho[] = "Entrada Total BestWay";
		echo "<th style='width:70px;'>Entrada Total JM</th>";
			$cabecalho[] = "Entrada Total JM";
	}
	else {
		echo "<th style='width:70px;'>Entrada Total</th>";
			$cabecalho[] = "Entrada Total";
	}
	echo "<th style='width:70px;'>Saída GAR</th>";
		$cabecalho[] = "Saída GAR";
	echo "<th style='width:70px;'>Saída FAT</th>";
		$cabecalho[] = "Saída FAT";

	for($m=$media-1;$m>=0;$m--) {
		# HD 285329
		$mes = $mes_final - $m;

		$ano = $ano_final;

		if ($mes <= 0) {
			$mes = 12 + $mes;
			$ano = $ano_final - 1;
		}

		if($m == $media -1 ) {
			$data_inicial_meses = $ano."-" . substr("0" . $mes, -2) . "-01 00:00:00";
		}


		if($m == 0) {
			$data_final_meses = $ano."-" . substr("0" . ($mes+1), -2) . "-01 00:00:00";
		}

		$mes = substr("0".$mes, -2) . "/" . $ano;

		//Retirada  class='mostra_esconde'
		echo "<th style='width:70px;background-color:#879BC0;color:white'>Saída $mes</th>";
			$cabecalho[] = "Saída $mes";
	}

	echo "<th style='width:70px;'>Acerto Estoque</th>";
		$cabecalho[] = "Acerto Estoque";
	echo "<th style='width:70px;'>Estoque Atual</th>";
		$cabecalho[] = "Estoque Atual";
	echo "<th style='width:70px;'>Qtde embarcada</th>";
		$cabecalho[] = "Qtde embarcada";
	echo "<th style='width:70px;'>Estoque Total</th>";
		$cabecalho[] = "Estoque Total";
	echo "</tr></thead>";

	fputs ($fp, implode(";", $cabecalho)."\n");

	$total_valor = 0;
	$total_valor0 = 0;
	$total_estoque = 0;
	$total_estoque0 = 0;
	$total_pedido = 0;
	$total_pedido0 = 0;
	$total_devolucao = 0;
	$total_devolucao0 = 0;
	$total_devolucao_valor = 0;
	$total_devolucao_valor0 = 0;
	$saida_gar = 0;
	$saida_fat = 0;
	$saida_gar0 = 0;
	$saida_fat0 = 0;
	$cont = 0;
	$saida_total_mensal = array();

	for ($i = 0 ; $i < pg_num_rows($res_pecas) ; $i++) {		
	
		$cont ++;
		$linha = [];

		$cor = "cccccc";
		if ($i % 2 == 0) $cor = '#eeeeee';

		$peca_atual = pg_fetch_result($res_pecas, $i, peca);
		$preco = pg_fetch_result($res_pecas, $i, preco);

		//Os depara desta data em específicos foram feitos para a BestWay para a migração das referências da JM para referências da Telecontrol
		if ($fabrica == 81) {
			$select_depara_acerto_salton = "UNION SELECT peca_de FROM tbl_depara WHERE peca_para=$peca_atual AND tbl_depara.digitacao='2010-02-13 11:33:20.127964'::timestamp AND tbl_depara.fabrica=81";
		}

		echo "<tr bgcolor='$cor' style='font-size:11px'>";

		echo "<td align='left' title='Referência da peça'>";
		echo pg_fetch_result($res_pecas,$i,referencia);
			$linha[] = pg_fetch_result($res_pecas,$i,referencia);
		echo "</td>";

		echo "<td align='left' title='Descrição da peça'>";
		echo pg_fetch_result($res_pecas,$i,descricao);
			$linha[] = pg_fetch_result($res_pecas,$i,descricao);
		echo "</td>";

		switch($fabrica) {
			case 81:
				$sql = "
						SELECT SUM (tbl_faturamento_item.qtde_estoque) AS entrada_total_salton
						  FROM tbl_faturamento
						  JOIN tbl_faturamento_item
						 USING (faturamento)
						  JOIN tbl_peca
							ON tbl_peca.peca           =  tbl_faturamento_item.peca
						 WHERE tbl_faturamento.posto   in ($login_distrib_postos)
						   AND tbl_peca.peca IN (
								SELECT $peca_atual $select_depara_acerto_salton)
						   AND tbl_faturamento.cancelada IS NULL
						   AND tbl_faturamento.emissao BETWEEN '$data_inicial 00:00:00'::timestamp
						   AND '$data_final 23:59:59'::timestamp
						   AND (distribuidor           <> 59773 /* JM */
							OR (distribuidor IS NULL
						   AND tbl_faturamento.fabrica =  81))
				";
				//if($peca_atual == '922274'){ echo $sql; }

				$res = pg_query($con, $sql);
				$entrada_total_salton = pg_fetch_result($res, 0, entrada_total_salton);

				/*$sql = "
                SELECT SUM (tbl_faturamento_item.qtde_estoque) AS entrada_total_jm
                  FROM tbl_faturamento
                  JOIN tbl_faturamento_item
                 USING (faturamento)
                  JOIN tbl_peca
                    ON tbl_peca.peca         = tbl_faturamento_item.peca
                 WHERE tbl_faturamento.posto in ($login_distrib_postos)
                   AND tbl_peca.peca IN (
						SELECT peca_de
						  FROM tbl_depara
						 WHERE peca_para             = $peca_atual
						   AND tbl_depara.digitacao  = '2010-02-13 11:33:20.127964'::timestamp
						   AND tbl_depara.fabrica    = 81)
                   AND tbl_faturamento.cancelada IS NULL
                   AND tbl_faturamento.emissao BETWEEN '$data_inicial 00:00:00'::timestamp
												   AND '$data_final 23:59:59'::timestamp
												   ";*/
				$sql = "
						SELECT SUM (tbl_faturamento_item.qtde_estoque) AS entrada_total_jm
						  FROM tbl_faturamento
						  JOIN tbl_faturamento_item
						 USING (faturamento)
						  JOIN tbl_peca
							ON tbl_peca.peca           = tbl_faturamento_item.peca
						 WHERE tbl_faturamento.posto   in ($login_distrib_postos)
						   AND tbl_peca.peca IN (
								SELECT $peca_atual $select_depara_acerto_salton)
						   AND tbl_faturamento.cancelada IS NULL
						   AND tbl_faturamento.emissao BETWEEN '$data_inicial 00:00:00'::timestamp
														   AND '$data_final 23:59:59'::timestamp
						   AND (distribuidor           =  59773 /* JM */
						   OR (distribuidor            IS NULL
						   AND tbl_faturamento.fabrica =  10))
				";
				//if($peca_atual == '922274'){ echo $sql; }
				//echo $sql;
				$res = pg_query($con, $sql);
				$entrada_total_jm = pg_fetch_result($res, 0, entrada_total_jm);

				echo "<td class='numero'  title='Total de entradas ds peças SALTON (distribuidor diferente do posto 04822966000165 da fabrica Telecontrol) '>";
				if ($entrada_total_salton) {
					echo $entrada_total_salton;
					$linha[] = $entrada_total_salton;
					$entrada_total_salton_totais += $entrada_total_salton;
				}
				else {
					echo "0";
					$linha[] = "0";
				}
				echo "</td>";

				echo "<td class='numero' title='Total de entradas ds peças JM (distribuidor posto 04822966000165 da fabrica Telecontrol)' >";
				if ($entrada_total_jm) {
					echo $entrada_total_jm;
					$linha[] = $entrada_total_jm;
					$entrada_total_jm_totais += $entrada_total_jm;
				}
				else {
					echo "0";
					$linha[] = "0";
					$entrada_total_jm_totais = "0";
				}
				echo "</td>";
			break;

			default:
				$sql = "
						SELECT SUM (tbl_faturamento_item.qtde_estoque) AS entrada_total
						  FROM tbl_faturamento
						  JOIN tbl_faturamento_item
						 USING (faturamento)
						  JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
						 WHERE tbl_faturamento.posto     in ($login_distrib_postos)
						   AND tbl_peca.peca             = $peca_atual
						   AND tbl_faturamento.cancelada IS NULL
						   AND tbl_faturamento.emissao BETWEEN '$data_inicial 00:00:00'::timestamp
						   AND '$data_final 23:59:59'::timestamp
				";
				$res = pg_query($con, $sql);
				echo "<td class='numero' title='Total de entradas das peas'>";
				if (pg_fetch_result($res, 0, entrada_total)) {
					echo pg_fetch_result($res, 0, entrada_total);
					$linha[] = pg_fetch_result($res, 0, entrada_total);
					$total_entrada_pecas += pg_fetch_result($res, 0, entrada_total); 
				}
				else {
					echo "0";
					$linha[] = "0";
				}
				echo "</td>";
		}

		$sql = "DROP TABLE IF EXISTS tmp_peca_$login_posto;
					SELECT DISTINCT faturamento_item, tbl_faturamento_item.qtde, tbl_tipo_pedido.codigo,tbl_faturamento_item.preco
					into temp tmp_peca_$login_posto
					FROM tbl_faturamento
					JOIN tbl_faturamento_item USING (faturamento)
					JOIN tbl_peca        ON tbl_peca.peca                = tbl_faturamento_item.peca
					LEFT JOIN tbl_pedido on tbl_faturamento_item.pedido = tbl_pedido.pedido
					LEFT JOIN tbl_tipo_pedido ON (tbl_faturamento.tipo_pedido  = tbl_tipo_pedido.tipo_pedido or tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido)
					WHERE tbl_faturamento.distribuidor in ($login_distrib_postos)
					and tbl_faturamento.fabrica <>0
					--and status_nfe='100'
					and tbl_faturamento.cfop in ('5949','6949','6403','6404','6102','5102','5403','5405')

					AND tbl_peca.peca IN (
					SELECT $peca_atual $select_depara_acerto_salton)
					AND tbl_faturamento.emissao BETWEEN '$data_inicial 00:00:00'::timestamp
					AND '$data_final 23:59:59'::timestamp;

					SELECT SUM(qtde) AS saida_total, codigo, sum(preco) as preco
					FROM tmp_peca_$login_posto
					GROUP BY codigo
				 ";
		$res_saidas = pg_query($con, $sql);

		//echo "<td class='numero' align='center' title='Total de peças Enviadas para o posto por NF (faturadas e garantia)'>";

		$saidas = pg_num_rows($res_saidas);
		$tot_gar = 0; $tot_fat = 0;
		//pre_echo(pg_fetch_all($res_saidas),'Saidas');
		$tot_preco_fat = 0; 
		for ($j=0; $j < $saidas; $j++) {
			$st = pg_fetch_result($res_saidas, $j, 'saida_total');
			$tp = pg_fetch_result($res_saidas, $j, 'codigo');
			$preco = pg_fetch_result($res_saidas, $j, 'preco');
			if ($tp == 'FAT') {
				$tot_fat = $st;
				$tot_preco_fat = $preco;
			}
			if ($tp == 'GAR') $tot_gar = $st;
		}
		$saida_total = $tot_gar + $tot_fat;

		$saida_gar = $saida_gar + $tot_gar;
		$saida_fat = $saida_fat + $tot_fat; 
		
		$saida_td = "<td class='numero' align='center' title='Total de peças Enviadas para o posto por NF (Garantia)'>$tot_gar</td>

			<td class='numero' align='center' title='Total de peças Enviadas para o posto por NF (Faturadas)'>$tot_fat</td>";
		echo ($saida_td);
		$linha[] = $tot_gar;
		$linha[] = $tot_fat;
	
		for($m = 0; $m < $media; $m++) {
			# HD 285329
			$sqlm = "SELECT
						TO_CHAR('$data_inicial_meses'::DATE + INTERVAL '$m months','YYYY-MM'),
						TO_CHAR('$data_inicial_meses'::DATE + INTERVAL '$m months','YYYY-MM-DD 00:00:00')";

			$resm = pg_query($con,$sqlm);
			$ano_mes_v = pg_fetch_result($resm,0,0);
			list($ano_v, $mes_v) = explode('-',$ano_mes_v);
			$data_inicial_v = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes_v, 2, $ano_v));
			$data_final_v   = date("Y-m-t 23:59:59",  mktime(0, 0, 0, $mes_v, 2, $ano_v));


			$sql = "SELECT SUM(tbl_faturamento_item.qtde) AS saida_total_mensal, TO_CHAR(emissao, 'YYYY-MM') AS mes
					  FROM tbl_faturamento
					  JOIN tbl_faturamento_item USING (faturamento)
					  JOIN tbl_peca        ON tbl_peca.peca                = tbl_faturamento_item.peca
					  LEFT JOIN tbl_tipo_pedido ON tbl_faturamento.tipo_pedido  = tbl_tipo_pedido.tipo_pedido
					 WHERE tbl_faturamento.distribuidor in ($login_distrib_postos)
					 --and status_nfe='100'
					 and tbl_faturamento.cfop in ('5949','6949','6403','6404','6102','5102','5403','5405')
					   AND tbl_peca.peca IN (SELECT $peca_atual $select_depara_acerto_salton)
					   AND tbl_faturamento.emissao BETWEEN '$data_inicial_v' AND '$data_final_v'
					 GROUP BY TO_CHAR(emissao, 'YYYY-MM')
					 ORDER BY TO_CHAR(emissao, 'YYYY-MM')
				";

			$res = pg_query($con, $sql);

			//Retirada  class='mostra_esconde'
			echo "<td class='numero' title='Total de peças enviadas NF dos últimos meses (Média(meses))'>";
			if(pg_num_rows($res) > 0){
				if(pg_fetch_result($res, 0, mes) == $ano_mes_v) {
					echo pg_fetch_result($res, 0, saida_total_mensal);
					$linha[] = pg_fetch_result($res, 0, saida_total_mensal);
					$saida_total_mensal [$m] += pg_fetch_result($res, 0, saida_total_mensal);
				}else{
					echo 0;
					$linha[] = "0";
					$saida_total_mensal [$m] += 0;
				}
			}else{
				echo 0;
				$linha[] = "0";
				$saida_total_mensal [$m] += 0;
			}

			echo "</td>";
		}


		$sql_ae = "SELECT SUM(qtde) AS valor_acerto
					 FROM tbl_posto_estoque_acerto
					WHERE peca IN (SELECT $peca_atual $select_depara_acerto_salton)
				   HAVING count(qtde)>0";
		$res_ae = pg_query($con, $sql_ae);
		$t_acerto_estoque = (pg_num_rows($res_ae)) ? pg_fetch_result($res_ae, 0,  0) : 0;

		if (is_numeric($t_acerto_estoque))
			$t_acerto_estoque = ($t_acerto_estoque > 0) ? "+$t_acerto_estoque" : $t_acerto_estoque;

		echo "<td class='numero' title='Total de acerto realizado na peça para conferência de estoque (entrada - saída + acertos)'>$t_acerto_estoque</td>";
		if (is_numeric($t_acerto_estoque)) {
			$linha[] = $t_acerto_estoque;
		}else{
			$linha[] = "0";
		}

		$estoque = pg_fetch_result($res_pecas, $i, 'estoque_total');

		echo "<td class='numero' title='Saldo em estoque.'>";
		echo number_format($estoque, 0, ",", "."); 
		$linha[] = number_format($estoque, 0, ",", ".");
		$estoque_ttl +=  number_format($estoque, 0, ",", ".");
		echo "</td>";

		$sql = "SELECT sum(qtde) as qtde  from tbl_embarque join tbl_embarque_item using(embarque) 
					where peca = $peca_atual and fabrica = $fabrica and faturar isnull";
		$res_embarque = pg_query($con, $sql);
		if(pg_num_rows($res_embarque) > 0) {
			$embarcado = pg_fetch_result($res_embarque, 0 , 'qtde');
		}else{
			$embarcado = 0 ;
		}

		echo "<td class='numero' title='Qtde embarcada'>";
		echo number_format($embarcado, 0, ",", "."); 
		$linha[] = number_format($embarcado, 0, ",", ".");
		$embarcado_total += number_format($embarcado, 0, ",", ".");
		echo "</td>";

		$total_estoque = $estoque + $embarcado + $t_acerto_estoque;
		$total_estoque = ($total_estoque < 0 ) ? 0 : $total_estoque;

		echo "<td class='numero' title='Total estoque'>";
		echo number_format($total_estoque, 0, ",", "."); 
		$linha[] = number_format($total_estoque, 0, ",", ".");
		$total_estoque_ttl += number_format($total_estoque, 0, ",", ".");
		echo "</td>";
		echo "</tr>";

		fputs($fp, implode(";", $linha)."\n");
	
	}
	$l_totais = array();
	if($cont == 0 ){
		?>
		<div class="warning">Nenhum resultado</div>
		<?php
	}else{
		echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:13px' align='center'>";
		echo "<td nowrap>TOTAIS</td>";
		$l_totais[] = "TOTAIS";
		echo "<td></td>";
		$l_totais[] = "";
		if ($fabrica == 81) {
			echo "<td class='numero'>$entrada_total_salton_totais</td>";
			$l_totais[] = $entrada_total_salton_totais;
			echo "<td class='numero'>$entrada_total_jm_totais</td>";
			$l_totais[] = $entrada_total_jm_totais;
			echo "<td class='numero'>$saida_gar</td>";
			$l_totais[] = $saida_gar;
			echo "<td class='numero'>$saida_fat</td>";
			$l_totais[] = $saida_fat;
		}else{
		echo "<td class='numero'>$total_entrada_pecas</td>";
		$l_totais[] = $total_entrada_pecas;
		echo "<td class='numero'>$saida_gar</td>";
		$l_totais[] = $saida_gar;
		echo "<td class='numero'>$saida_fat</td>";
		$l_totais[] = $saida_fat;
		}
		//Retirada  class='mostra_esconde'
		if($media > 0){
				for ($t = 0; $t < $media; $t++) {
					echo "<td class='numero'>$saida_total_mensal[$t]</td>";
					$l_totais[] = $saida_total_mensal[$t];	
				}
		}	
		echo "<td></td>";
		$l_totais[] = "";
		echo "<td class='numero'>$estoque_ttl</td>";
		$l_totais[] = $estoque_ttl;
		echo "<td class='numero'>$embarcado_total</td>";
		$l_totais[] = $embarcado_total;
		echo "<td class='numero'>$total_estoque_ttl</td>"; // para fab 81
		$l_totais[] = $total_estoque_ttl;
		echo "</tr>";
		echo "</table>";
		echo "<input type='hidden' name='total' value='$i'>";
		echo "</form>";
	}
		fputs ($fp, implode(";", $l_totais)."\n");

		//Redireciona a saida da tela, que estava em buffer, para a variÃ¡vel
		fclose ($fp);

        $data = date("Y-m-d").".".date("H-i-s");

        rename("/tmp/assist/controle_inventario_".$login_posto."_data_".$hora.".csv","xls/relatorio-controle-inventario-$login_posto.$data.csv");

	   ?>       
         <div class="span12">
        	<div class="btn_excel" > 
        	<a href='xls/relatorio-controle-inventario-<?=$login_posto?>.<?=$data?>.csv' target='_blank'>       
        	    <span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
                <span><img style="width:40px ; height:40px;" src="../imagens/icon_csv.png"></span>
            </a>
            </div>
        </div>

           <?php 
}

if (isset($_POST['btn_acao']) AND (strlen ($descricao) < 3 AND strlen ($referencia) < 3 AND strlen ($localizacao) < 3)) {
	echo "<br><br><center><b class='vermelho'>DIGITE NO MÍNIMO 3 CARACTERES PARA A BUSCA!</center></b>";
}

include "rodape.php";

?>
