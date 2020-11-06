<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$fabrica    = trim($_GET["fabrica"]);
$tipo       = trim($_GET["tipo"]);
$referencia = trim($_GET['referencia']);
$descricao  = trim($_GET['descricao']);
$busca      = trim($_GET['busca']);
$media      = trim($_GET['media']);
$previsao   = trim($_GET['previsao']);
$ignorar    = trim($_GET['ignorar']);
if (count($_GET)) {
	$legenda_relatorio = "<p>Relatório de previsão para os próximos $previsao meses do estoque ";
	if ($tipo) $legenda_relatorio.= "de {$tipo}s ";
	$legenda_relatorio.= "para a fábrica $fabrica, fazendo uma média dos últimos $media meses";
	$legenda_relatorio.= ($referencia) ? " do ítem $referencia." : '.';
	$legenda_relatorio.= "</p>";
}


$msg_erro = array();
$data_final=date('Y-m-d');
if(!empty($_POST['btn_gerar'])) {
	$fabrica = $_POST['fabrica'];
	$total=$_POST['total'];
	$sql = "SELECT tipo_pedido from tbl_tipo_pedido WHERE fabrica = $fabrica and pedido_em_garantia ";
	$res = pg_query($con,$sql);
	$tipo_pedido_gar = pg_fetch_result($res,0,0);

	$sql = "SELECT tipo_pedido from tbl_tipo_pedido WHERE fabrica = $fabrica and pedido_faturado ";
	$res = pg_query($con,$sql);
	$tipo_pedido_fat = pg_fetch_result($res,0,0);

	$sql = "SELECT condicao from tbl_condicao WHERE fabrica = $fabrica and descricao ~* 'gar' ";
	$res = pg_query($con,$sql);
	$condicao_gar = pg_fetch_result($res,0,0);

	$sql = "SELECT condicao from tbl_condicao WHERE fabrica = $fabrica and descricao ~* 'vista' ";
	$res = pg_query($con,$sql);
	$condicao_fat = pg_fetch_result($res,0,0);
	$pedidos = 2; //Ronaldo pediu para gerar so pedido garantia 
	$pedido_cliente = "REPOSIÇÃO ESTOQUE";
	for($i=1;$i<$pedidos;$i++){
        	$res = pg_query($con,"BEGIN TRANSACTION");
		switch($i){
			case '1':
				$tipo_pedido = $tipo_pedido_gar;
				$condicao = $condicao_gar;

				break;
			case '2':
				$tipo_pedido = $tipo_pedido_fat;
				$condicao = $condicao_fat;

				break;
			case '3':
				$tipo_pedido = $tipo_pedido_fat;
				$condicao = $condicao_fat;

				break;
		}
		$sql = "INSERT INTO tbl_pedido(
				fabrica,
				posto,
				condicao,
				tipo_pedido,
				pedido_cliente,
				status_pedido
			)values(
				$fabrica,
				20682,
				$condicao,
				$tipo_pedido,
				'$pedido_cliente',
				1
			) RETURNING pedido";
		$res = pg_query($con,$sql);
		$pedido = pg_fetch_result($res,0,0);
		$msg_erro = pg_last_error($con);

		for($x=0;$x<=$total;$x++){
			$peca = $_POST['peca_'.$x] ;
			if(empty($peca)) continue;
			switch($i){
				case '1':$qtde = $_POST['qtde_gar_'.$x];break;
				case '2':$qtde = $_POST['qtde_fat_'.$x];break;
				case '3':$qtde = $_POST['qtde_dem_'.$x];break;
			}
			if($qtde <= 0 or empty($qtde)) continue;

			$sql = "INSERT INTO tbl_pedido_item (
					pedido,
					peca,
					qtde
				)values(
					$pedido,
					$peca,
					$qtde
				)";
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
		}

		$sql = "SELECT fn_pedido_finaliza($pedido, $fabrica);";
		$res = pg_query($con, $sql);
		$msg_erro = pg_last_error($con);

		$sql = "UPDATE tbl_pedido SET pedido_via_distribuidor = 'f' , distribuidor = null WHERE pedido = $pedido";
		$res = pg_query($con, $sql);
		$msg_erro = pg_last_error($con);

		if (strlen($msg_erro) == 0) {
			$res = pg_query($con,"COMMIT TRANSACTION");
			switch($i){
				case '1':$msg .="Pedido Garantia gerado: $pedido<br/>";break;
				case '2':$msg .="Pedido Faturado gerado: $pedido<br/>";break;
				case '3':$msg .="Pedido Demanda gerado: $pedido<br/>";break;
			}
		}else {
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
}

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
	if(empty($media)) {
		$msg_erro[]="Por favor, informe o número de meses para a média";
	}

	if(empty($previsao)) {
		$msg_erro[]="Por favor, informe o número de meses para a previsão";
	}

	$sql = " SELECT min(emissao) FROM tbl_faturamento join tbl_faturamento_item using(faturamento) join tbl_peca using(peca) WHERE tbl_faturamento.fabrica in ($fabrica,10) and tbl_peca.fabrica = $fabrica";
	$res = pg_query($con,$sql);
	$data_inicial = pg_fetch_result($res,0,0);


}

$msg_erro = implode("<br>", $msg_erro);


?><html>
	<head>
	<title>Previsão de Estoque</title>
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
	
	</style>
	</head>

	<body>

	<? include 'menu.php';
//		echo "Programa em manutenção";exit;
	?>
	<script type="text/javascript" src="js/jquery-1.4.1.js"></script>
	<script type="text/javascript" src="js/date.js"></script>
	<script type="text/javascript" src="js/jquery.dimensions.min.js"></script>
	<link rel="stylesheet" type="text/css" href="js/datePicker-2.css" title="default" media="screen" />
	<script type="text/javascript" src="js/jquery.datePicker-2.js"></script>
	<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
	<script>
	Date.firstDayOfWeek = 0;
	</script>


	<style type="text/css">
	/*
		p {
			margin: 1em 0;
		}
		ul {
			margin: 0 0 0 20px;
		}
		dt {
			margin: 1em 0 .2em;
			font-weight: bold;
		}
		dd {
			margin: .2em 0 1em;
		}
	*/
		#container {
			width: 758px;
			margin: 0 auto;
			padding: 10px 20px;
			background: #fff;
		}
	/*
		fieldset {
			margin: 1em 0;
			padding: 0 10px;
			width: 180px;
		}
		label {
			width: 160px;
			display: inline-block;
			line-height: 1.8;
			vertical-align: top;
		}
	*/
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
	<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
	<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
	<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
	<script type='text/javascript' src='js/dimensions.js'></script>
	<script type="text/javascript" src="js/thickbox.js"></script>
	<script type="text/javascript" src="js/jquery.fixedheadertable.min.js"></script>
	<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
	<script src='../js/shortcut.js'></script>


	<script type="text/javascript" src='../js/tinybox2/tinybox.js'></script>
	<script type="text/javascript">
	function showHelp() {
		TINY.box.show({iframe:'manual_estoque_previsao.html',width:600,height:550});
	}
	shortcut.add('m', function() {mostraEsconde();}, {'disable_in_input': true});
	shortcut.add('j', function() {showHelp();}, {'disable_in_input': true});

	$(function() {

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

	<center><h1>Previsão de Estoque</h1></center>

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
			<td align='right'>Média(meses)</td>
			<td><input type='text' size='3' name='media' rel='numero' value='<?=$media?>' class='frm'></td>
			<td align='right'>Previsão(meses)</td>
			<td><input type='text' size='11' name='previsao' rel='numero'  id='previsao' class="frm"  value="<? echo $_GET["previsao"]; ?>"></td>
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
			<td align='center' colspan='2'><input type='checkbox' name='ignorar'  value='ignorar' checked> Ignorar saída total</td>

			<td align='center' colspan='2'><input type='checkbox' name='ex_itens' id='ex_itens' value='s' <? if ($_GET["ex_itens"]) echo "checked"; ?>>Somente itens com pedido</td> 
		</tr>
		<tr>
			<td align='center' colspan='4'><input type='submit' name='btn_acao' id='btn_acao' value='Pesquisar'></td>
		</tr>
	</table>
	<br>
	</form>

	<?

if (strlen($msg_erro) == 0 && strlen($data_inicial) > 0 && strlen($data_final) > 0 && strlen($fabrica) > 0) {
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

	if ($fabrica != 122) {
		$cond_de_para = "

			DELETE FROM tmp_fat_pos_est
			WHERE peca IN (
					SELECT peca_de
					  FROM tbl_depara
					 WHERE tbl_depara.fabrica = $fabrica);
		";
	}

	 $sql = "




        SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque_localizacao.localizacao
          INTO TEMP tmp_fat_pos_est
          FROM tbl_faturamento
          JOIN tbl_faturamento_item          ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
          JOIN tbl_peca                      ON tbl_faturamento_item.peca   = tbl_peca.peca
          JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca               = tbl_posto_estoque_localizacao.peca
										    AND tbl_posto_estoque_localizacao.posto in ($login_distrib_postos)
         WHERE tbl_peca.fabrica                    = $fabrica
	 AND tbl_faturamento.fabrica IN (10, $fabrica)
	 and distribuidor in ($login_distrib_postos)
		   $sql_produto_acabado
		   $sql_peca

	;	SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
		into TEMP tmp_ped_sem_ent
		FROM tbl_pedido_item
		JOIN tbl_pedido USING(pedido)
		JOIN tbl_peca using(peca, fabrica) 
		where tbl_pedido.fabrica = $fabrica
		and distribuidor in ($login_distrib_postos)
		and tbl_peca.ativo
		and tbl_pedido.status_pedido not in (4,14,13)
		$sql_peca
		$sql_produto_acabado
		and tbl_peca.peca not in (select peca from tmp_fat_pos_est) ; 

		{$cond_de_para}

		DELETE FROM tmp_fat_pos_est
		 WHERE localizacao in ('FL','*FL');

		ALTER TABLE tmp_fat_pos_est ADD preco float;
		ALTER TABLE tmp_ped_sem_ent ADD preco float;

        UPDATE tmp_fat_pos_est
           SET preco                = tbl_tabela_item.preco
          FROM tbl_tabela_item
          JOIN tbl_tabela USING (tabela)
	  WHERE fabrica              = $fabrica
	  AND tabela_garantia
           AND tmp_fat_pos_est.peca = tbl_tabela_item.peca;

	UPDATE tmp_ped_sem_ent
           SET preco                = tbl_tabela_item.preco
          FROM tbl_tabela_item
          JOIN tbl_tabela USING (tabela)
          WHERE fabrica              = $fabrica
          AND tabela_garantia
           AND tmp_ped_sem_ent.peca = tbl_tabela_item.peca;

	


		/* Acerto de estoque */
		ALTER TABLE tmp_fat_pos_est ADD estoque_total FLOAT;
		ALTER TABLE tmp_ped_sem_ent ADD estoque_total FLOAT;

		UPDATE tmp_fat_pos_est
		SET estoque_total = qtde
		FROM tbl_posto_estoque
		WHERE tbl_posto_estoque.peca = tmp_fat_pos_est.peca
		AND posto in ($login_distrib_postos);


		 UPDATE tmp_ped_sem_ent
                SET estoque_total = qtde
                FROM tbl_posto_estoque
                WHERE tbl_posto_estoque.peca = tmp_ped_sem_ent.peca
                AND posto in ($login_distrib_postos);


        SELECT DISTINCT peca, referencia, descricao, localizacao, preco, estoque_total
		  FROM tmp_fat_pos_est
		union 
		SELECT DISTINCT peca, referencia, descricao, '' as localizacao, preco, estoque_total
		from tmp_ped_sem_ent
         ORDER BY localizacao ASC ;";
	
	$res_pecas = pg_query($con, $sql);

	$parts = explode("-", $data_final);
	$ano_final = intval($parts[0]);
	$mes_final = intval($parts[1]);

	/*
	echo "<p style='width:1000px'><a href='javascript:mostraEsconde()' title='Aperte \"m\" ou clique aqui para mostrar / ocultar os meses.'
		id='mostra'><u>M</u>ostrar Campos(Meses)</a>
		<a href='javascript:showHelp()' style='float:right' title='Clique ou aperte \"j\" para obter ajuda sobre os cálculos'>A<u>j</u>uda</a>
		</p>";
	 */
	echo "<a href='javascript:showHelp()' style='float:right' title='Clique ou aperte \"j\" para obter ajuda sobre os cálculos'>A<u>j</u>uda</a></p>";

	$hora = time();

	 flush();

        $xlsdata = date ("d/m/Y H:i:s");

        system("rm /tmp/assist/estoque_previsao_".$login_posto."_data_".$hora.".csv");
        $fp = fopen ("/tmp/assist/estoque_previsao_".$login_posto."_data_".$hora.".csv","w");

        fputs ($fp,"Relatório Previsão de Estoque\n");

        $cabecalho = array();



	echo "<button onclick='javascript:fixHeader()'>Fixar Cabeçalho</button>";
	echo "<div id='voltar'><button onclick='javascript:unfixHeader()'>Voltar</button></div>";
	echo "<br><form method='POST' action='$PHP_SELF' name='frm_resultado'>
		<input type='hidden' name='fabrica' value='$fabrica'>
		<table id='result' align='center' border='0' cellspacing='1' cellpadding='1'>";
	echo "<thead>";
	echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:13px' align='center'>";
	
	echo "<th nowrap style='width:80px;'>Localização</th>";
        $cabecalho[] = "Localização";
	echo "<th nowrap>Referência</th>";
        $cabecalho[] = "Referência";

    if ($fabrica == 122) {
		echo "<th nowrap>Referência de-para</th>";
		$cabecalho[] = "Referência de-para";
	}

	echo "<th nowrap>Peça</th>";
		$cabecalho[] = "Peça";

	if ($fabrica == 122) { /*HD - 6277523*/
		echo "<th nowrap>Entrada GAR</th>";
		$cabecalho[] = "Entrada GAR";

		echo "<th nowrap>Entrada FAT</th>";
		$cabecalho[] = "Entrada FAT";
	}

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

	if ($fabrica == 122) { /*HD - 6277523*/
		echo "<th style='width:70px;'>Saída Total</th>";
		$cabecalho[] = "Saída Total";
	}

	$cabecalho[] = "Valor Total FAT";

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
			//exit($mes);
	}

	echo "<th style='width:70px;'>Média $media Meses</th>";
		$cabecalho[] = "Média $media Meses";
	echo "<th style='width:70px;'>Previsão $previsao Meses</th>";
		$cabecalho[] = "Previsão $previsao Meses";
	echo "<th style='width:70px;'>Acerto Estoque</th>";
		$cabecalho[] = "Acerto Estoque";

	echo "<th style='width:70px;'>Estoque Atual Total</th>";
		$cabecalho[] = "Estoque Atual Total";
	echo "<th style='width:70px;'>Qtde embarcada</th>";
		$cabecalho[] = "Qtde embarcada";

	if ($fabrica == 122) {
		echo "<th style='width:70px;'>Demanda GAR</th>";
		$cabecalho[] = "Demanda GAR";

		echo "<th style='width:70px;'>Demanda FAT</th>";
		$cabecalho[] = "Demanda FAT";

		echo "<th style='width:70px;'>Demanda TOTAL</th>";
		$cabecalho[] = "Demanda TOTAL";
	} else {
		echo "<th style='width:70px;'>Demanda Total</th>";
		$cabecalho[] = "Demanda Total";
	}

	echo "<th style='width:70px;'>Média Demanda</th>";
		$cabecalho[] = "Média Demanda";
	echo "<th style='width:70px;'>Pedido<br>Fábrica</th>";
		$cabecalho[] = "Pedido<br>Fabrica";
	echo "<th style='width:70px;'>Valor Total</th>";
		$cabecalho[] = "valor Total";
	echo "<th style='width:70px;'><font color='green'>Pedido Reposição</font></th>";
		$cabecalho[] = "Pedido Reposição";
	if ($fabrica == 81) {
		echo "<th style='width:70px;'>Devolução BestWay</th>";
			$cabecalho[] = "Devolução BestWay";
		echo "<th style='width:70px;'>Valor Devolução BestWay</th>";
			$cabecalho[] = "valor Devolução BestWay";
		echo "<th style='width:70px;'>Devolução JM</th>";
			$cabecalho[] = "Devolução JM";
		echo "<th style='width:70px;'>Valor Devolução JM</th>";
			$cabecalho[] = "Valor Devolução JM";
	}
	else {
		echo "<th style='width:70px;'>Devolução</th>";
			$cabecalho[] = "Devolução";
	}

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

	for ($i = 0 ; $i < pg_num_rows($res_pecas) ; $i++) {		
	
		if($ex_itens == 's') {

		$peca_atual = pg_fetch_result($res_pecas, $i, peca);
		$preco = pg_fetch_result($res_pecas, $i, preco);

		if ($fabrica == 81) {
			$select_depara_acerto_salton = "UNION SELECT peca_de FROM tbl_depara WHERE peca_para=$peca_atual AND tbl_depara.digitacao='2010-02-13 11:33:20.127964'::timestamp AND tbl_depara.fabrica=81";
		}
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

				$res = pg_query($con, $sql);
				$entrada_total_salton = pg_fetch_result($res, 0, entrada_total_salton);
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
				$res = pg_query($con, $sql);
				$entrada_total_jm = pg_fetch_result($res, 0, entrada_total_jm);
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
		}
	
		$sql = "DROP TABLE IF EXISTS tmp_peca_$login_posto;
				SELECT DISTINCT faturamento_item, tbl_faturamento_item.qtde, tbl_tipo_pedido.codigo
				into temp tmp_peca_$login_posto
				  FROM tbl_faturamento
				  JOIN tbl_faturamento_item USING (faturamento)
				  JOIN tbl_peca        ON tbl_peca.peca                = tbl_faturamento_item.peca
				  LEFT JOIN tbl_pedido on tbl_faturamento_item.pedido = tbl_pedido.pedido
				  LEFT JOIN tbl_tipo_pedido ON (tbl_faturamento.tipo_pedido  = tbl_tipo_pedido.tipo_pedido or tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido)
				  WHERE tbl_faturamento.distribuidor in ($login_distrib_postos)
				  and tbl_faturamento.fabrica <>0
				 and status_nfe='100'
					and natureza <>'DEVOLUCAO'
				 and tbl_faturamento.cfop in ('5949','6949','6403','6404','6102','5102','5403','5405')
				   AND tbl_peca.peca IN (
						SELECT $peca_atual $select_depara_acerto_salton)
				   AND tbl_faturamento.emissao BETWEEN '$data_inicial 00:00:00'::timestamp
				   AND '$data_final 23:59:59'::timestamp;

				SELECT SUM(qtde) AS saida_total, codigo
				  FROM tmp_peca_$login_posto
				 GROUP BY codigo
				 ";
		$res_saidas = pg_query($con, $sql);
		$saidas = pg_num_rows($res_saidas);
		$tot_gar = 0; $tot_fat = 0;
		for ($j=0; $j < $saidas; $j++) {
			$st = pg_fetch_result($res_saidas, $j, 'saida_total');
			$tp = pg_fetch_result($res_saidas, $j, 'codigo');
			if ($tp == 'FAT') $tot_fat = $st;
			if ($tp == 'GAR') $tot_gar = $st;
		}
		$saida_total = $tot_gar + $tot_fat;
		$saida_gar0 = $saida_gar0 + $tot_gar;
		$saida_fat0 = $saida_fat0 + $tot_fat; 
		
		$saida_td = "<td class='numero' align='center' title='Total de peças Enviadas para o posto por NF (Garantia)'>$tot_gar</td>

			<td class='numero' align='center' title='Total de peas Enviadas para o posto por NF (Faturadas)'>$tot_fat</td>";
		$linha[] = $tot_gar;
		$linha[] = $tot_fat;
		$total = 0;
		for($m = 0; $m < $media; $m++) {
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
					and natureza <>'DEVOLUCAO'
					 and status_nfe='100'
					 and tbl_faturamento.cfop in ('5949','6949','6403','6404','6102','5102','5403','5405')
					   AND tbl_peca.peca IN (SELECT $peca_atual $select_depara_acerto_salton)
					   AND tbl_faturamento.emissao BETWEEN '$data_inicial_v' AND '$data_final_v'
					 GROUP BY TO_CHAR(emissao, 'YYYY-MM')
					 ORDER BY TO_CHAR(emissao, 'YYYY-MM')
				";

			$res = pg_query($con, $sql);
			if(pg_num_rows($res) > 0){
				if(pg_fetch_result($res, 0, mes) == $ano_mes_v) {
					$total += pg_fetch_result($res, 0, saida_total_mensal);
				}
			}
		}
		$sql_ae = "SELECT SUM(qtde) AS valor_acerto
					 FROM tbl_posto_estoque_acerto
					WHERE peca IN (SELECT $peca_atual $select_depara_acerto_salton)
				   HAVING count(qtde)>0";
		$res_ae = pg_query($con, $sql_ae);
		$t_acerto_estoque = (pg_num_rows($res_ae)) ? pg_fetch_result($res_ae, 0,  0) : '&mdash;';

		if (is_numeric($t_acerto_estoque)){
			$t_acerto_estoque = ($t_acerto_estoque > 0) ? "+$t_acerto_estoque" : $t_acerto_estoque;
		}
		$estoque = pg_fetch_result($res_pecas, $i, 'estoque_total');

		$sql = "SELECT sum(qtde-(qtde_faturada+qtde_cancelada+qtde_faturada_distribuidor)) as demanda from tbl_pedido join tbl_pedido_item using(pedido) 
					where peca = $peca_atual and fabrica > 0 and distribuidor in ($login_distrib_postos) and tbl_pedido.status_pedido not in (4,14,13)";
		$res_demanda = pg_query($con, $sql);
		if(pg_num_rows($res_demanda) > 0) {
			$demanda = pg_fetch_result($res_demanda , 0 , 'demanda');
			$demanda = ($demanda < 0 ) ? 0 : $demanda;
		}else{
			$demanda = 0 ; 
		}
		$sql = "SELECT sum(qtde-(qtde_faturada+qtde_cancelada+qtde_faturada_distribuidor)) as demanda from tbl_pedido join tbl_pedido_item using(pedido) 
					where peca = $peca_atual and fabrica = $fabrica and posto in (20682,4311) and distribuidor isnull and pedido_cliente notnull and tbl_pedido.status_pedido not in (4,14,13)";
		$res_demanda = pg_query($con, $sql);
		if(pg_num_rows($res_demanda) > 0) {
			$demanda_fabrica = pg_fetch_result($res_demanda , 0 , 'demanda');
			$demanda_fabrica = ($demanda_fabrica < 0 ) ? 0 : $demanda_fabrica;
		}else{
			$demanda_fabrica = 0 ;
		}

		$sql = "SELECT sum(qtde) as qtde  from tbl_embarque join tbl_embarque_item using(embarque) 
					where peca = $peca_atual and fabrica = $fabrica and faturar isnull";
		$res_embarque = pg_query($con, $sql);
		if(pg_num_rows($res_embarque) > 0) {
			$embarcado = pg_fetch_result($res_embarque, 0 , 'qtde');
		}else{
			$embarcado = 0 ;
		}

		$demanda_total0 += number_format($demanda, 0, ",", ".");
		$demanda_fabrica_total0 += number_format($demanda_fabrica, 0, ",", ".");
		$total_estoque0 += $estoque;
		$total_valor0 += floatval(number_format(($preco*$estoque), 2, ".", ""));
		$previsao2 = ceil($previsao*$total/$media) - $estoque;

		if ($previsao2 > 0) {
			$tot_dem =  $previsao2 - ($tot_gar+$tot_fat);
			$tot_dem = ($tot_dem<= 0) ? 0 : $tot_dem;
			if($ignorar == 'ignorar') {
				$tot_gar =  number_format(ceil($previsao*$total/$media), 0, ",", ".");
				$tot_gar = ($tot_gar - $estoque >0) ? $tot_gar-$estoque : 0 ;
				$tot_gar = ($estoque == 0 ) ? $tot_gar + $demanda - $demanda_fabrica: $tot_gar;
				$tot_gar = ($tot_gar< 0) ? 0 : $tot_gar;
			}
			if($tot_gar <= 0){
				continue;
			}
			$total_pedido0 += $tot_gar;
		}
		else {
			if($tot_gar > 0 ) {
				$tot_fat = 0 ;
				$tot_dem = 0;
				if($ignorar == 'ignorar') {
					$tot_gar =  number_format(ceil($previsao*$total/$media), 0, ",", ".");
					$tot_gar = ($tot_gar - $estoque >0) ? $tot_gar-$estoque : 0 ;
					$tot_gar = ($estoque == 0 ) ? $tot_gar + $demanda - $demanda_fabrica: $tot_gar;
					$tot_gar = ($tot_gar< 0) ? 0 : $tot_gar;
				}
				if($tot_gar <= 0){
					continue;
				}
				$total_pedido0 += $tot_gar;
			}elseif($estoque == 0 and $demanda > 0){
				$tot_gar = $demanda - $demanda_fabrica;
				$tot_gar = ($tot_gar< 0) ? 0 : $tot_gar;
				if($tot_gar <= 0){
					continue;
				}
				$total_pedido0 += $tot_gar;
			}else{
				continue;
			}
		}
		if ($previsao2 < 0) {
			$previsao2 = $previsao2*(-1);
			if ($fabrica == 81) {
				$sql = "SELECT SUM (tbl_faturamento_item.qtde_estoque) AS entrada_total_geral_jm
						  FROM tbl_faturamento
						  JOIN tbl_faturamento_item
						 USING (faturamento)
						  JOIN tbl_peca
							ON tbl_peca.peca         = tbl_faturamento_item.peca
						 WHERE tbl_faturamento.posto in ($login_distrib_postos)
						   AND (tbl_peca.peca IN (
								SELECT peca_de
								  FROM tbl_depara
								 WHERE peca_para             = $peca_atual
								   AND tbl_depara.digitacao  = '2010-02-13 11:33:20.127964'::timestamp
								   AND tbl_depara.fabrica    = 81) )
						   AND tbl_faturamento.cancelada IS NULL
				";
				$res = pg_query($con, $sql);
				$entrada_total_geral_jm = pg_fetch_result($res, 0, entrada_total_geral_jm);
				$entrada_total_geral_jm = (empty($entrada_total_geral_jm)) ? 0 : $entrada_total_geral_jm;
				$entrada_total_geral_salton = $estoque - $entrada_total_geral_jm;
				if ($entrada_total_geral_salton < 0) {
					$entrada_total_geral_salton = 0;
				}
				if ($previsao2 <= $entrada_total_geral_salton) {
					if($entrada_total_jm > 0 and $entrada_total_salton == 0){
						$previsao_jm = $previsao2;
						$previsao_salton = 0;
					}else{
						$previsao_salton = $previsao2;
						$previsao_js = 0;
					}
				}
				else {
					if($entrada_total_jm > 0 and $entrada_total_salton == 0){
						$previsao_salton = $entrada_total_geral_salton;
						$previsao_jm = $previsao2 - $previsao_salton;
					}else{
						$previsao_jm = $entrada_total_geral_salton;
						$previsao_salton = $previsao2 - $previsao_jm;
					}
				}
				if($previsao_jm > $entrada_total_jm and $previsao_salton == 0){
					$previsao_salton = $previsao_jm - $entrada_total_jm;
					$previsao_jm = $entrada_total_jm;
				}
				if($entrada_total_salton > 0 and $entrada_total_jm > 0 ){
					if($saida_total > $entrada_total_salton ) {
						$previsao_jm +=$previsao_salton;
						$previsao_salton = 0;
					}
					if($saida_total - $entrada_total_salton < 0 and $previsao_jm > 0){
						if($previsao_salton > ($entrada_total_salton - $saida_total)){
							$previsao_jm += ($previsao_salton - ($entrada_total_salton - $saida_total));
							$previsao_salton = $entrada_total_salton - $saida_total;
						}
					}
					if($previsao_jm > $entrada_total_jm){
						$previsao_salton += ($previsao_jm - $entrada_total_jm);
						$previsao_jm  = $entrada_total_jm;
					}
				}
				$total_devolucao_salton += $previsao_salton;
				$total_devolucao_jm += $previsao_jm;
				$total_devolucao_valor_salton0 += floatval(number_format(($preco*$previsao_salton), 2, ".", ""));
				$total_devolucao_valor_jm0 += floatval(number_format(($preco*$previsao_jm), 2, ".", ""));
			}
			else {
				$total_devolucao += $previsao2;
				$total_devolucao_valor0 += floatval(number_format(($preco*$previsao2), 2, ".", ""));
			}
		}
	}
		if($ex_itens == 's') {
			if ($ignorar != 'ignorar' && $tot_gar <=0) {
				continue;
			}
		}

		$cont ++;
		$linha = [];
		// hd 365973 -- Ronaldo pediu para tirar o cabecalho que repete
		if($i % 12 == 0 and $i > 0 and 1==2) {
			flush();
			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:13px' align='center'>";
			echo "<td nowrap>Localização</td>";
			echo "<td nowrap>Referência</td>";
			echo "<td nowrap>Peça</td>";

			if ($fabrica == 81) {
				echo "<td style='width:70px;'>Entrada Total BestWay</td>";
				echo "<td style='width:70px;'>Entrada Total JM</td>";
			}
			else {
				echo "<td style='width:70px;'>Entrada Total</td>";
			}
			echo "<td style='width:70px;'>Saída GAR</td>";
			echo "<td style='width:70px;'>Saída FAT</td>";


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
				echo "<td style='width:70px;background-color:#879BC0;color:white'>Saída $mes</td>";

			}

			echo "<td style='width:70px;'>Média $media Meses</td>";
			echo "<td style='width:70px;'>Previsão $previsao Meses</td>";
			echo "<td style='width:70px;'>Acerto Estoque</td>";
			echo "<td style='width:70px;'>Estoque Atual</td>";
			echo "<td style='width:70px;'>Valor Total</td>";
			echo "<td style='width:70px;'>Pedido Reposição</td>";
			if ($fabrica == 81) {
				echo "<td style='width:70px;'>Devolucao BestWay</td>";
				echo "<td style='width:70px;'>Valor Devolucao BestWay</td>";
				echo "<td style='width:70px;'>Devolucao JM</td>";
				echo "<td style='width:70px;'>Valor Devolucao JM</td>";
			}
			else {
				echo "<td style='width:70px;'>Devolucao</td>";
			}

			echo "</tr>";
		}

		$cor = "cccccc";
		if ($i % 2 == 0) $cor = '#eeeeee';

		$peca_atual = pg_fetch_result($res_pecas, $i, peca);
		$preco = pg_fetch_result($res_pecas, $i, preco);

		//Os depara desta data em específicos foram feitos para a BestWay para a migração das referências da JM para referências da Telecontrol
		if ($fabrica == 81) {
			$select_depara_acerto_salton = "UNION SELECT peca_de FROM tbl_depara WHERE peca_para=$peca_atual AND tbl_depara.digitacao='2010-02-13 11:33:20.127964'::timestamp AND tbl_depara.fabrica=81";
		}

		echo "<tr bgcolor='$cor' style='font-size:11px'>";

		echo "<td align='left' title='Localizaçã da peça no estoque'>";
		echo pg_fetch_result($res_pecas,$i,localizacao);
			$linha[] = pg_fetch_result($res_pecas,$i,localizacao);
		echo "</td>";

		echo "<td align='left' title='Referência da peça'>";
		echo pg_fetch_result($res_pecas,$i,referencia);
			$linha[] = pg_fetch_result($res_pecas,$i,referencia);
		echo "</td>";

		if ($fabrica == 122) {
				
			$peca_id = pg_fetch_result($res_pecas,$i,'peca');

			$sql_de_para = "SELECT tbl_peca.referencia as referencia_para
							FROM tbl_depara
							JOIN tbl_peca ON tbl_peca.fabrica = $fabrica 
							AND tbl_peca.peca = tbl_depara.peca_para
							WHERE tbl_depara.peca_de = $peca_id
							AND (tbl_depara.expira > current_date OR tbl_depara.expira IS NULL)";

			$res_de_para = pg_query($con, $sql_de_para);

			$referencia_de_para = pg_fetch_result($res_de_para, 0, 'referencia_para');

			$linha[] = $referencia_de_para;
			echo "<td><strong>".$referencia_de_para."</strong></td>";
		}

		echo "<td align='left' title='Descrição da peça'>";
		echo pg_fetch_result($res_pecas,$i,descricao);
			$linha[] = pg_fetch_result($res_pecas,$i,descricao);
		echo "</td>";

		if ($fabrica == 122) { /*HD - 6277523*/
			$aux_sql = "
				SELECT count(tbl_faturamento_item.peca) AS total_pecas
				FROM tbl_faturamento
				JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
				WHERE tbl_faturamento.fabrica = $fabrica
				AND tbl_faturamento.emissao BETWEEN '$data_inicial 00:00:00'::timestamp AND '$data_final 23:59:59'::timestamp
				AND tbl_faturamento_item.peca = $peca_id
				AND tbl_faturamento.cfop IN ('5949', '6949')
			";
			$aux_res = pg_query($con, $aux_sql);
			$aux_val = pg_fetch_result($aux_res, 0, 'total_pecas');
			$linha[] = $aux_val;

			echo "<td align='right'>$aux_val</td>";

			$aux_sql = "
				SELECT count(tbl_faturamento_item.peca) AS total_pecas
				FROM tbl_faturamento
				JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
				WHERE tbl_faturamento.fabrica = $fabrica
				AND tbl_faturamento.emissao BETWEEN '$data_inicial 00:00:00'::timestamp AND '$data_final 23:59:59'::timestamp
				AND tbl_faturamento_item.peca = $peca_id
				AND tbl_faturamento.cfop IN ('5101', '5102', '5403', '5405')
			";
			$aux_res = pg_query($con, $aux_sql);
			$aux_val = pg_fetch_result($aux_res, 0, 'total_pecas');
			$linha[] = $aux_val;

			echo "<td align='right'>$aux_val</td>";
		}

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
				}
				else {
					echo "0";
					$linha[] = "0";
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
				echo "<td class='numero' title='Total de entradas das peças'>";
				if (pg_fetch_result($res, 0, entrada_total)) {
					echo pg_fetch_result($res, 0, entrada_total);
					$linha[] = pg_fetch_result($res, 0, entrada_total);
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
				 and status_nfe='100'
				and natureza <>'DEVOLUCAO'
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

		if ($fabrica == 122) { /*HD - 6277523*/
			$aux_val = $tot_gar + $tot_fat;
			$linha[] = $aux_val;
			echo "<td align='right'>$aux_val</td>";
		}

		$linha[] = $tot_preco_fat;
	
		$total = 0;
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
					 and status_nfe='100'
					and natureza <>'DEVOLUCAO'
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
					$total += pg_fetch_result($res, 0, saida_total_mensal);
				}else{
					echo 0;
					$linha[] = "0";
				}
			}else{
				echo 0;
				$linha[] = "0";
			}

			echo "</td>";
		}
		echo "<td class='numero' title='Média de saída de peças dos últimos $media meses.'>";
		echo number_format($total/$media, 2, ",", ".");
		$linha[] = number_format($total/$media, 2, ",", ".");
		echo "</td>";

		echo "<td class='numero' title='Previsão = Total das saídas dos últimos meses dividido pela quantidade de meses (média) e multiplicado pela previsão de meses.'>";
		echo number_format(ceil($previsao*$total/$media), 0, ",", ".");
		$linha[] = number_format(ceil($previsao*$total/$media), 0, ",", ".");
		echo "</td>";

//die($sql);
		// HD 365973 - MLG - Adicionando o valor do acerto de estoque.
		$sql_ae = "SELECT SUM(qtde) AS valor_acerto
					 FROM tbl_posto_estoque_acerto
					WHERE peca IN (SELECT $peca_atual $select_depara_acerto_salton)
				   HAVING count(qtde)>0";
		$res_ae = pg_query($con, $sql_ae);
		$t_acerto_estoque = (pg_num_rows($res_ae)) ? pg_fetch_result($res_ae, 0,  0) : '&mdash;';

		//MLG - Colocar um '+' se o acerto for positivo. Ajuda visual.
		if (is_numeric($t_acerto_estoque))
			$t_acerto_estoque = ($t_acerto_estoque > 0) ? "+$t_acerto_estoque" : $t_acerto_estoque;

		echo "<td class='numero' title='Total de acerto realizado na peça para conferência de estoque (entrada - saída + acertos)'>$t_acerto_estoque</td>";
		if (is_numeric($t_acerto_estoque)) {
			$linha[] = $t_acerto_estoque;
		}else{
		$linha[] = "0";
		}
		// FIM HD 365973
		
		$estoque = pg_fetch_result($res_pecas, $i, 'estoque_total');

		echo "<td class='numero' title='Saldo em estoque.'>";
		echo number_format($estoque, 0, ",", "."); 
		$linha[] = number_format($estoque, 0, ",", ".");
		echo "</td>";

		$sql = "SELECT sum(qtde-(qtde_faturada+qtde_cancelada+qtde_faturada_distribuidor)) as demanda from tbl_pedido join tbl_pedido_item using(pedido) 
					where peca = $peca_atual and fabrica > 0 and distribuidor in ($login_distrib_postos) and tbl_pedido.status_pedido not in (4,14,13)";
		$res_demanda = pg_query($con, $sql);
		if(pg_num_rows($res_demanda) > 0) {
			$demanda = pg_fetch_result($res_demanda , 0 , 'demanda');
			$demanda = ($demanda < 0 ) ? 0 : $demanda;
		}else{
			$demanda = 0 ; 
		}
		$sql = "SELECT sum(qtde-(qtde_faturada+qtde_cancelada+qtde_faturada_distribuidor)) as demanda from tbl_pedido join tbl_pedido_item using(pedido) 
					where peca = $peca_atual and fabrica = $fabrica and posto in (20682,4311) and distribuidor isnull and pedido_cliente notnull and tbl_pedido.status_pedido not in (4,14,13)";
		$res_demanda = pg_query($con, $sql);
		if(pg_num_rows($res_demanda) > 0) {
			$demanda_fabrica = pg_fetch_result($res_demanda , 0 , 'demanda');
			$demanda_fabrica = ($demanda_fabrica < 0 ) ? 0 : $demanda_fabrica;
		}else{
			$demanda_fabrica = 0 ;
		}

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
		$total_embarcado += number_format($embarcado, 0, ",", ".");
		echo "</td>";

		if ($fabrica == 122) { /*HD - 6277523*/
			$aux_sql = "
				SELECT SUM (qtde - (qtde_faturada + qtde_cancelada + qtde_faturada_distribuidor)) AS demanda
				FROM tbl_pedido
				JOIN tbl_pedido_item USING(pedido) 
				WHERE peca = $peca_atual
				AND fabrica > 0
				AND distribuidor IN ($login_distrib_postos)
				AND tbl_pedido.status_pedido NOT IN (4,14,13) 
				AND tipo_pedido = 247
			";
			$aux_res = pg_query($con, $aux_sql);
			$aux_val = pg_fetch_result($aux_res, 0, 'demanda');

			if ($aux_val <= 0) {
				$aux_val = 0;
			}

			$linha[] = $aux_val;

			echo "<td align='right'>$aux_val</td>";

			$aux_sql = "
				SELECT SUM (qtde - (qtde_faturada + qtde_cancelada + qtde_faturada_distribuidor)) AS demanda
				FROM tbl_pedido
				JOIN tbl_pedido_item USING(pedido) 
				WHERE peca = $peca_atual
				AND fabrica > 0
				AND distribuidor IN ($login_distrib_postos)
				AND tbl_pedido.status_pedido NOT IN (4,14,13) 
				AND tipo_pedido = 246
			"; 
			$aux_res = pg_query($con, $aux_sql);
			$aux_val = pg_fetch_result($aux_res, 0, 'demanda');

			if ($aux_val <= 0) {
				$aux_val = 0;
			}

			$linha[] = $aux_val;

			echo "<td align='right'>$aux_val</td>";
		}

		echo "<td class='numero' title='Demanda de pedido'>";
		echo number_format($demanda, 0, ",", ".");
		$demanda_total += number_format($demanda, 0, ",", ".");
		$linha[] = number_format($demanda, 0, ",", ".");
		echo "<td class='numero' title='Média de Demanda'>";
		$media_demanda = $demanda / $media;
		echo number_format($media_demanda, 2, ",", ".");
		$linha[] = number_format($media_demanda, 2, ",", ".");
		echo "</td>";
		echo "<td class='numero' title='Pedido Fabrica'>";
		echo number_format($demanda_fabrica, 0, ",", ".");
		$demanda_fabrica_total += number_format($demanda_fabrica, 0, ",", ".");
		$linha[] = number_format($demanda_fabrica, 0, ",", ".");
		echo "</td>";
		$total_estoque += $estoque;
		$total_valor += floatval(number_format(($preco*$estoque), 2, ".", ""));

		echo "<td class='numero' title='Valor Unitário: " . number_format($preco, 2, ",", ".") . "'>";
		echo number_format(($preco*$estoque), 2, ",", ".");
		$linha[] = number_format(($preco*$estoque), 2, ",", ".");
		echo "</td>";

		$demanda_pedido =  $media_demanda * $previsao ; 
		$demanda_pedido = round($demanda_pedido, 0);
		$previsao2 = ceil($previsao*$total/$media) - $estoque;

		if ($previsao2 > 0) {
			echo "<td class='numero' title='Sugestão de pedido = Previsão - Estoque' bgcolor='#B9FDC1'><font color='green'><b>";
			$tot_dem =  $previsao2 - ($tot_gar+$tot_fat);
			$tot_dem = ($tot_dem<= 0) ? 0 : $tot_dem;
			if($ignorar == 'ignorar') {
				$tot_gar =  number_format(ceil($previsao*$total/$media), 0, ",", ".");
				$tot_gar = ($tot_gar - $estoque >0) ? $tot_gar-$estoque : 0 ;
				$tot_gar = ($estoque <= 0 ) ? $tot_gar + $demanda - $demanda_fabrica: $tot_gar;
				$tot_gar = ($media_demanda > 0 ) ? $tot_gar+ $demanda_pedido : $tot_gar;
				$tot_gar = ($tot_gar< 0) ? 0 : $tot_gar;
			}
			$saida_td = "<td>$tot_gar</td>";
			echo "<input type='hidden' name='peca_$i' value='$peca_atual'>";
			echo "<input type='hidden' name='qtde_gar_$i' value='$tot_gar'>";
			echo "<input type='hidden' name='qtde_fat_$i' value='$tot_fat'>";
			echo "<input type='hidden' name='qtde_dem_$i' value='$tot_dem'>";
			echo "<table cellpadding='3' cellspacing='2' border='0' style='text-align: center;margin:auto'>".
				"<tr style='color:black;'>$saida_td</tr>".
			 "</table>";
			echo "</b></font></td>";
			$linha[] = $tot_gar;
			$total_pedido += $tot_gar;

		}
		else {
			if($tot_gar > 0 ) {
				echo "<td class='numero' title='Sugestão de pedido = Previsão - Estoque' bgcolor='#B9FDC1'><font color='green'><b>";
				$tot_fat = 0 ;
				$tot_dem = 0;
	
				if($ignorar == 'ignorar') {
					$tot_gar =  number_format(ceil($previsao*$total/$media), 0, ",", ".");
					$tot_gar = ($tot_gar - $estoque >0) ? $tot_gar-$estoque : 0 ;
					$tot_gar = ($estoque <= 0 ) ? $tot_gar + $demanda - $demanda_fabrica: $tot_gar;
					$tot_gar = ($media_demanda > 0 ) ? $tot_gar+ $demanda_pedido : $tot_gar;
					$tot_gar = ($tot_gar< 0) ? 0 : $tot_gar;

				}
				$saida_td = "<td>$tot_gar</td>";
				echo "<input type='hidden' name='peca_$i' value='$peca_atual'>";
				echo "<input type='hidden' name='qtde_gar_$i' value='$tot_gar'>";
				echo "<input type='hidden' name='qtde_fat_$i' value='$tot_fat'>";
				echo "<input type='hidden' name='qtde_dem_$i' value='$tot_dem'>";
				echo "<table cellpadding='3' cellspacing='2' border='0' style='text-align: center;margin:auto'>".
					"<tr style='color:black;'>$saida_td</tr>".
				 "</table>";
				echo "</b></font></td>";
				$linha[] = $tot_gar;
				$total_pedido += $tot_gar;

			}elseif($estoque == 0 and $demanda > 0){
				echo "<td class='numero' title='Sugestão de pedido = Previsão - Estoque' bgcolor='#B9FDC1'><font color='green'><b>";
				$tot_gar = $demanda - $demanda_fabrica;
				$tot_gar = ($media_demanda > 0 ) ? $tot_gar+ $demanda_pedido : $tot_gar;
				$tot_gar = ($tot_gar< 0) ? 0 : $tot_gar;
				$saida_td = "<td>$tot_gar</td>";
				echo "<input type='hidden' name='peca_$i' value='$peca_atual'>";
				echo "<input type='hidden' name='qtde_gar_$i' value='$tot_gar'>";
				echo "<table cellpadding='3' cellspacing='2' border='0' style='text-align: center;margin:auto'>".
					"<tr style='color:black;'>$saida_td</tr>".
				 "</table>";
				echo "</b></font></td>";
				$linha[] = $tot_gar;
				$total_pedido += $tot_gar;
			
			}else{
				echo "<td class='numero' title='Sugestão de pedido = Previsão - Estoque' bgcolor='#B9FDC1'>";
				echo "0";
				$linha[] = "0";
				echo "</td>";
			}
		}

		if ($previsao2 < 0) {
			$previsao2 = $previsao2*(-1);
			echo "<td class='numero'>";

			if ($fabrica == 81) {
				$sql = "SELECT SUM (tbl_faturamento_item.qtde_estoque) AS entrada_total_geral_jm
						  FROM tbl_faturamento
						  JOIN tbl_faturamento_item
						 USING (faturamento)
						  JOIN tbl_peca
							ON tbl_peca.peca         = tbl_faturamento_item.peca
						 WHERE tbl_faturamento.posto in ($login_distrib_postos)
						   AND (tbl_peca.peca IN (
								SELECT peca_de
								  FROM tbl_depara
								 WHERE peca_para             = $peca_atual
								   AND tbl_depara.digitacao  = '2010-02-13 11:33:20.127964'::timestamp
								   AND tbl_depara.fabrica    = 81) )
						   AND tbl_faturamento.cancelada IS NULL
				";
				$res = pg_query($con, $sql);
				$entrada_total_geral_jm = pg_fetch_result($res, 0, entrada_total_geral_jm);
				$entrada_total_geral_jm = (empty($entrada_total_geral_jm)) ? 0 : $entrada_total_geral_jm;
				$entrada_total_geral_salton = $estoque - $entrada_total_geral_jm;
				if ($entrada_total_geral_salton < 0) {
					$entrada_total_geral_salton = 0;
				}

				if ($previsao2 <= $entrada_total_geral_salton) {
					if($entrada_total_jm > 0 and $entrada_total_salton == 0){
						$previsao_jm = $previsao2;
						$previsao_salton = 0;
					}else{
						$previsao_salton = $previsao2;
						$previsao_js = 0;
					}
				}
				else {
					if($entrada_total_jm > 0 and $entrada_total_salton == 0){
						$previsao_salton = $entrada_total_geral_salton;
						$previsao_jm = $previsao2 - $previsao_salton;
					}else{
						$previsao_jm = $entrada_total_geral_salton;
						$previsao_salton = $previsao2 - $previsao_jm;
					}
				}

				if($previsao_jm > $entrada_total_jm and $previsao_salton == 0){
					$previsao_salton = $previsao_jm - $entrada_total_jm;
					$previsao_jm = $entrada_total_jm;
				}

				if($entrada_total_salton > 0 and $entrada_total_jm > 0 ){
					if($saida_total > $entrada_total_salton ) {
						$previsao_jm +=$previsao_salton;
						$previsao_salton = 0;
					}

					if($saida_total - $entrada_total_salton < 0 and $previsao_jm > 0){
						if($previsao_salton > ($entrada_total_salton - $saida_total)){
							$previsao_jm += ($previsao_salton - ($entrada_total_salton - $saida_total));
							$previsao_salton = $entrada_total_salton - $saida_total;
						}
					}

					if($previsao_jm > $entrada_total_jm){
						$previsao_salton += ($previsao_jm - $entrada_total_jm);
						$previsao_jm  = $entrada_total_jm;
					}

				}

				echo number_format($previsao_salton, 0, ",", ".");
				$linha[] = number_format($previsao_salton, 0, ",", ".");
				echo "</td>";
				echo "<td class='numero' title='Previsão de devolução de peças para BESTWAY em valor.'>";
				echo number_format(($preco*$previsao_salton), 2, ",", ".");
				$linha[] = number_format(($preco*$previsao_salton), 2, ",", ".");
				echo "</td>";
				echo "<td class='numero' title='Previsão de devolução de peças para JM'>";
				echo number_format($previsao_jm, 0, ",", ".");
				$linha[] = number_format($previsao_jm, 0, ",", ".");
				echo "</td>";
				echo "<td class='numero' title='Previsão de devolução de peças para JM em Valor.'>";
				echo number_format(($preco*$previsao_jm), 2, ",", ".");
				$linha[] = number_format(($preco*$previsao_jm), 2, ",", ".");
				
				$total_devolucao_salton += $previsao_salton;
				$total_devolucao_jm += $previsao_jm;

				$total_devolucao_valor_salton += floatval(number_format(($preco*$previsao_salton), 2, ".", ""));
				$total_devolucao_valor_jm += floatval(number_format(($preco*$previsao_jm), 2, ".", ""));
			}
			else {
				echo number_format($previsao2, 0, ",", ".");
				$linha[] = number_format($previsao2, 0, ",", ".");
				$total_devolucao += $previsao2;
				$total_devolucao_valor += floatval(number_format(($preco*$previsao2), 2, ".", ""));
			}
			echo "</td>";
		}
		else {
			if ($fabrica == 81) {
				echo "<td class='numero'>";
				echo "0";
				$linha[] = "0";
				echo "</td>";
				echo "<td class='numero'>";
				echo "0";
				$linha[] = "0";
				echo "</td>";
				echo "<td class='numero'>";
				echo "0";
				$linha[] = "0";
				echo "</td>";
			}

			echo "<td class='numero'>";
			echo "0";
			$linha[] = "0";
			echo "</td>";
		}

		echo "</tr>";

		fputs($fp, implode(";", $linha)."\n");
	
	}

	if($cont == 0 ){
		?>
		<div class="warning">Nenhum Pedido de Reposição</div>
		<?php
	}else{
		echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:13px' align='center'>";
		echo "<td nowrap>TOTAIS</td>";
		echo "<td></td>";
		echo "<td></td>";
		echo "<td></td>";
		//Retirada  class='mostra_esconde'
		if ($fabrica==81){ 
			echo "<td></td>";
			echo "<td class='numero'>$saida_gar</td>";
			echo "<td class='numero'>$saida_fat</td>";
		}else{
		echo "<td class='numero'>$saida_gar</td>";
		echo "<td class='numero'>$saida_fat</td>";
		}
		echo "<td colspan='$media'></td>";
		echo "<td></td>";
		echo "<td></td>";
		echo "<td></td>";
		echo "<td class='numero'>$total_estoque</td>";
		echo "<td class='numero'>$total_embarcado</td>";
		echo "<td class='numero'>$demanda_total</td>";
		echo "<td></td>";
		echo "<td class='numero'>$demanda_fabrica_total</td>";
		echo "<td class='numero'>" . number_format($total_valor, 2, ',', '.') . "</td>";
		if($ignorar == 'ignorar') {
			echo "<td class='numero'>$total_pedido</td>";
		}else{
			echo "<td class='numero'>$saida_gar</td>";
		}
		if ($fabrica == 81) {
			echo "<td></td>";
			echo "<td class='numero' title='Total de peças: $total_devolucao_salton'>" . number_format($total_devolucao_valor_salton, 2, ',', '.') . "</td>";
			echo "<td></td>";
			echo "<td class='numero' title='Total de peças: $total_devolucao_jm'>" . number_format($total_devolucao_valor_jm, 2, ',', '.') . "</td>";
		} else {
			echo "<td class='numero' title='Total de peças: $total_devolucao'>" . number_format($total_devolucao_valor, 2, ',', '.') . "</td>";
		}
		echo "</tr>";
		echo "</table>";
		echo "<input type='hidden' name='total' value='$i'>";
		echo "<input type='submit' name='btn_gerar' value='Gerar Pedido'>";
		echo "</form>";
	}

		//Redireciona a saida da tela, que estava em buffer, para a variÃ¡vel
		fclose ($fp);

        $data = date("Y-m-d").".".date("H-i-s");

        rename("/tmp/assist/estoque_previsao_".$login_posto."_data_".$hora.".csv","xls/relatorio-estoque-previsao-$login_posto.$data.csv");

	   ?>       
         <div class="span12">
        	<div class="btn_excel" > 
        	<a href='xls/relatorio-estoque-previsao-<?=$login_posto?>.<?=$data?>.csv' target='_blank'>       
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
