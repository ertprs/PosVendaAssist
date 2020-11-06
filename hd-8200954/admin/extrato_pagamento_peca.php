<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
require_once 'autentica_admin.php';

include_once 'funcoes.php';
include_once "monitora.php";
include_once "../helpdesk/mlg_funciones.php";

error_reporting(E_ERROR);
# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj			= trim(pg_result($res,$i,cnpj));
				$nome			= trim(pg_result($res,$i,nome));
				$codigo_posto	= trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

$sql_link = "SELECT fabrica FROM tbl_defeito WHERE fabrica = $login_fabrica";
$res_link = pg_query($con, $sql_link);
if(pg_num_rows($res_link) > 0){
	$tem_link = "true";
}

if($_POST['consulta']){

	$data_inicial = getPost('data_inicial');
	$data_final   = getPost('data_final');

	if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
	if (strlen($_GET['data_final']) > 0)   $data_final   = $_GET['data_final']  ;

	if(!$data_inicial OR !$data_final)
		$msg_erro = traduz("Data Inválida.");
	else{
	//Início Validação de Datas
		if($data_inicial) {
			list($di, $mi, $yi) = explode("/", $data_inicial); //tira a barra
			if(!checkdate($mi, $di, $yi)) $msg_erro = traduz("Data Inválida");
		}
		if($data_final) {
			list($df, $mf, $yf) = explode ("/", $data_final );//tira a barra
			if(!checkdate($mf, $df, $yf)) $msg_erro = traduz("Data Inválida");
		}
		if(!$msg_erro) {
			$nova_data_inicial = strtotime("$yi-$mi-$di");
			$nova_data_final   = strtotime("$yf-$mf-$df");

			if($nova_data_final < $nova_data_inicial){
				$msg_erro = traduz("Data Inválida.");
			}

			$max_days = 30;
			if($nova_data_final > ($nova_data_inicial + (86400*$max_days))) {
				$msg_erro= traduz("O intervalo entre as datas não pode ser maior que $max_days dias.");
			}

			//Fim Validação de Datas
		}
	}

}


$layout_menu = "financeiro";
$title = traduz("RELATÓRIO PEÇAS X CUSTO");

include 'cabecalho_new.php';
	$plugins = array(
		"mask",
		"datepicker",
		"shadowbox",
		"dataTable",
		"autocomplete"
	);

	include "plugin_loader.php";

$form = array(
		"data_inicial" => array(
			"span"      => 4,
			"label"     => traduz("Data Início"),
			"type"      => "input/text",
			"width"     => 10,
			"required"  => true,
			"maxlength" => 10
		),
		"data_final" => array(
			"span"      => 4,
			"label"     => traduz("Data Final"),
			"type"      => "input/text",
			"width"     => 10,
			"required"  => true,
			"maxlength" => 10
		),
		"codigo_posto" => array(
			"span"      => 4,
			"label"     => traduz("Código Posto"),
			"type"      => "input/text",
			"width"     => 10,
			"lupa"		=> array(
					"name"   	=> "codigo_posto",
					"tipo"   	=> "posto",
					"parametro" => "codigo"
				)
		),
		"descricao_posto" => array(
			"span"      => 4,
			"label"     => traduz("Posto"),
			"type"      => "input/text",
			"width"     => 10,
			"lupa"		=> array(
					"name"   	=> "descricao_posto",
					"tipo"   	=> "posto",
					"parametro" => "nome"
				)
		),
		"peca_referencia" => array(
			"span"      => 4,
			"label"     => traduz("Referência"),
			"type"      => "input/text",
			"width"     => 10,
			"lupa"		=> array(
					"name"   	=> "peca_referencia",
					"tipo"   	=> "peca",
					"parametro" => "referencia"
				)
		),
		"peca_descricao" => array(
			"span"      => 4,
			"label"     => traduz("Descrição"),
			"type"      => "input/text",
			"width"     => 10,
			"lupa"		=> array(
					"name"   	=> "peca_descricao",
					"tipo"   	=> "peca",
					"parametro" => "descricao"
				)
		)

);

	if($login_fabrica == 20) {

		$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY tipo_atendimento";
		$res = pg_exec ($con,$sql) ;

		$tipo_atendimentos = array();
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				$tipo_atendimento = pg_fetch_result($res,$i,'tipo_atendimento');
				$codigo           = pg_fetch_result($res,$i,'codigo');
				$descricao        = pg_fetch_result($res,$i,'descricao');
				$tipo_atendimentos[$tipo_atendimento] = "$codigo - $descricao";
		}
		$form['tipo_atendimento'] = array(
				"span"      => 4,
				"label"     => "Tipo Atendimento",
				"type"      => "select",
				"width"     => 10,
				"options"=> $tipo_atendimentos
		);

		$sql = "SELECT * FROM tbl_familia WHERE fabrica = $login_fabrica ORDER BY familia";
		$res = pg_exec ($con,$sql) ;
		$familias = array();
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				$familia = pg_fetch_result($res,$i,'familia');
				$descricao = pg_fetch_result($res,$i,'descricao');
				$familias[$familia] = $descricao;
		}

		$form['familia'] = array(
				"span"      => 4,
				"label"     => "Família",
				"type"      => "select",
				"width"     => 10,
				"options"=> $familias
		);

		$form['origem'] = array(
				"span"      => 2,
				"label"     => "Origem",
				"type"      => "select",
				"width"     => 10,
				"options"=> array('Nac' => 'Nacional', 'Imp'=>'Importado')
		);

		$form['serie_inicial'] = array(
			"span"      => 3,
			"label"     => "Serie Inicial",
			"type"      => "input/text",
			"width"     => 10
		);

		$form['serie_final'] =	array(
			"span"      => 3,
			"label"     => "Serie Final",
			"type"      => "input/text",
			"width"     => 10
		);

	}
?>


<script>
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("posto","peca"));
		Shadowbox.init();

		$("span[rel=descricao_posto]").click(function () {
			$.lupa($(this));
		});

		$("span[rel=codigo_posto]").click(function () {
			$.lupa($(this));
		});
		$("span[rel=peca_referencia]").click(function () {
			$.lupa($(this));
		});
		$("span[rel=peca_descricao]").click(function () {
			$.lupa($(this));
		});
	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	function retorna_peca(retorno){
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
    }

</script>

	<div class="row"> <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b> </div>

		<form name='frm_relatorio' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>

			<div class='titulo_tabela'><?=traduz('Parâmetros de Pesquisa')?></div> <br/>

			<? echo montaForm($form,null);?>
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/><br>
</form>
<?
flush();



if(strlen($msg_erro)==0){

if(strlen($data_inicial) > 0 AND strlen($data_final)>0){

	$tipo_atendimento = trim($_POST["tipo_atendimento"]);
	$familia          = trim($_POST["familia"]);
	$origem           = trim($_POST["origem"]);
	$serie_inicial    = trim($_POST["serie_inicial"]);
	$serie_final      = trim($_POST["serie_final"]);
	$codigo_posto     = trim($_POST["codigo_posto"]);

	if(strlen($tipo_atendimento) == 0) $tipo_atendimento = trim($_GET["tipo_atendimento"]);
	if(strlen($familia) == 0)          $familia          = trim($_GET["familia"]);
	if(strlen($codigo_posto) == 0)     $codigo_posto     = trim($_GET["codigo_posto"]);
	if(strlen($origem) == 0)           $origem           = trim($_GET["origem"]);
	if(strlen($serie_inicial) == 0)    $serie_inicial    = trim($_GET["serie_inicial"]);
	if(strlen($serie_final) == 0)      $serie_final      = trim($_GET["serie_final"]);


	if(strlen($serie_inicial) > 0 AND strlen($serie_final)>0 AND ($serie_final - $serie_inicial < 13)) {

		for($x = $serie_inicial ; $x <= $serie_final ; $x++){
			if($x == $serie_final) $aux = "$aux'$x'";
			else                   $aux = "'$x',".$aux;

		}
	}

	$peca_descricao  = $_POST['peca_descricao'];//hd 2003 TAKASHI
	$peca_referencia = $_POST['peca_referencia'];//hd 2003 TAKASHI

	if(strlen($peca_descricao)>0 and strlen($peca_referencia)>0){//hd 2003 TAKASHI
		$sql = "SELECT tbl_peca.peca
			FROM tbl_peca
			WHERE tbl_peca.fabrica = $login_fabrica
			AND tbl_peca.referencia = '$peca_referencia'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$peca = pg_result($res,0,peca);
		}
	}

	if(strlen($codigo_posto)>0 ){
		$sql = "SELECT posto
			FROM tbl_posto_fabrica
			WHERE fabrica = $login_fabrica
			AND codigo_posto = '$codigo_posto'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,posto);
		}
	}

	if (strlen ($tipo_atendimento)> 0 ) $cond_5 = " AND tbl_os.tipo_atendimento = $tipo_atendimento";
	if (strlen ($familia)         > 0 ) $cond_6 = " AND tbl_produto.familia     = $familia ";
	if (strlen ($origem)          > 0 ) $cond_7 = " AND tbl_produto.origem      = '$origem' ";
	if (strlen ($aux)             > 0 ) $cond_8 = " AND substr(serie,0,4) IN ($aux)";
	if (strlen ($posto)           > 0 ) $cond_9 = " AND tbl_extrato.posto       = '$posto' ";

	$condicao = " and 1=1 ";//takashi hd 2003
	if(strlen($peca)>0) $condicao = " and tbl_peca.peca = $peca ";//takashi hd 2003


	if($login_fabrica <> 20){
		if (strlen ($data_inicial) > 0 AND strlen ($data_final) > 0)
			$tipo_data = " tbl_extrato.data_geracao ";
	}else{
		if (strlen ($data_inicial) > 0 AND strlen ($data_final) > 0)
			$tipo_data = " tbl_extrato_extra.exportado ";
	}

	if ( in_array($login_fabrica, array(74)) ) {
		$sql_preco_peca = 'tbl_os_item.preco';
		$sql_group_peca = 'tbl_os_item.preco,';
	} elseif ( in_array($login_fabrica, array(117)) ) {
		$sql_preco_peca = 'tbl_os_item.custo_peca';
		$sql_group_peca = 'tbl_os_item.custo_peca,';
	} else {
		$sql_preco_peca = 'SUM(coalesce(tbl_os_item.preco,0))';
		$sql_group_peca = '';
	}




	$sql = "SELECT
			$sql_preco_peca             AS preco           ,
			SUM(coalesce(tbl_os_item.custo_peca,0)) AS custo_peca      ,
			SUM(tbl_os_item.qtde)       AS qtde            ,
			tbl_peca.peca					               ,
			tbl_peca.referencia         AS peca_referencia ,
			tbl_peca.descricao          AS peca_descricao
			FROM tbl_os_extra
			JOIN tbl_os            ON    tbl_os_extra.os           = tbl_os.os AND tbl_os.fabrica = $login_fabrica
			JOIN tbl_produto       ON    tbl_os.produto            = tbl_produto.produto
			JOIN tbl_extrato       ON    tbl_extrato.extrato       = tbl_os_extra.extrato
			JOIN tbl_extrato_extra ON    tbl_extrato_extra.extrato = tbl_os_extra.extrato
			JOIN tbl_os_produto    ON    tbl_os_produto.os         = tbl_os_extra.os
			JOIN tbl_os_item       USING (os_produto)
			JOIN tbl_peca          USING (peca)
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND  $tipo_data  BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
			$condicao
			$cond_5
			$cond_6
			$cond_7
			$cond_8
			$cond_9
			GROUP BY $sql_group_peca tbl_peca.peca,
					 peca_referencia,
					 peca_descricao
			ORDER BY qtde DESC,peca_descricao ";
	if ($login_fabrica == 74) { // HD 892365
		$res = pg_query($con, $sql); // sql é o "geral", o "sqlCount" é para a paginação

		// if ($_SERVER['SERVER_NAME'] == '192.168.0.199') {
		// 	$sql = "SELECT
		// 					'JW3P49UP34', 'PECA DE TESTE 1',25,2,453453
		// 		";
		// }

		if (pg_num_rows($res) > 0) {

			$data_xls = date("Y-m-d_H-i-s");
			$xls_file = "xls/relatorio_field_call_rate_defeitos-$login_fabrica-$data_xls.xls";

			$xls_header = "<html lang='pt-br'>
<head>
	<title>RELATÓRIO PEÇA X CUSTO" . date('d/m/Y') . "</title>
	<meta name='Author' content='TELECONTROL NETWORKING LTDA' />
</head>
<body>\n";

			$a_res  = pg_fetch_all($res);

			// Processa os valores
			foreach($a_res as $idx => $rec) {
				$rec = array_map('trim', $rec);

				extract($rec, EXTR_PREFIX_ALL, 'xls');

				$new_rec['Peça']       = "$xls_peca_referencia - $xls_peca_descricao";
				$new_rec['Preço']      = (is_numeric($xls_preco)) ?  $real . number_format($xls_preco, 2, ",", ".") : $real .'0,00';
				$new_rec['Qtde']       = $xls_qtde;
				$new_rec['Total_Peça'] = $real . number_format($xls_qtde * $xls_preco, 2, ",", ".");

				$link_filters = array(
					'btnacao'          => 'filtrar',
					'posto'            => $posto,
					'data_inicial'     => $data_inicial,
					'data_final'       => $data_final,
					'peca'             => $xls_peca,
					'familia'          => $familia,
					'tipo_atendimento' => $tipo_atendimento,
					'aux'              => $aux,
					'origem'           => $origem,
					'preco_peca'       => ($xls_preco == 0) ? '0.00': $xls_preco,
				);

				$link_filters = array_filter($link_filters);

				foreach ($link_filters as $campo=>$valor) {
					$link_params[] = "$campo=$valor";
				}

				$a_xls_links[$idx]['Peça']   = "<a href='relatorio_field_call_rate_defeitos.php?" . implode('&', $link_params) . "' target='_blank'>" . $new_rec['Peça'] . '</a>';

				$a_xls[] = $new_rec;
				unset($new_rec, $link_filters, $link_params);
			}
			// Configuração da tabela
			$a_xls['attrs'] = array(
				'tableAttrs'  => " id='res' border='1' class='tabela' cellpadding='2' cellspacing='1' style='font-size: 11px!important' bordercolor='#d2e4fc' width='700'",
				'headerAttrs' => " style='font:bold 11px Arial;color:#ffffff;text-transform:capitalize;background-color:#596d9b;'",
				'captionAttrs'=> " font-size:13px!important;color:white;background:#596d9b;text-align:center;",
			);
			//$a_xls['headers'] = array('Peça', 'Valor Un.', 'Qtde.', 'Preço');

			file_put_contents($xls_file, $xls_header . array2table($a_xls, "RELATÓRIO PEÇA X CUSTO", true) . "</body></html>");

			if (is_readable($xls_file)) {
				chmod($xls_file, 0777);
				echo "<div class='btn_excel'>
				<span><img src='imagens/excel.png' /></span>
				<span class='txt' onclick='javascript: window.open(\"$xls_file\")'; >Gerar Arquivo Excel</span>
			</div><br>";

			}
			// FIM Geração Excel dados completos


			// Adiciona o link para consulta da peça (FCR) em tela
			foreach ($a_xls as $idx=>$rec) {
				$a_xls[$idx]['Peça'] = $a_xls_links[$idx]['Peça'];
			}

			echo $file_link;

			$a_xls['attrs']['rowAttrs'] = " class='Conteudo'";

			echo array2table($a_xls, "RELATÓRIO PEÇA X CUSTO", true);
			//echo array2table($a_xls, '', true, false, true); ?>
			<div id='controls'>
				<div id='perpage'>
					<select onchange='sorter.size(this.value)'>
					<option value='5'>5</option>
						<option value='10'>10</option>
						<option value='20' selected='selected'>20</option>
						<option value='50'>50</option>
						<option value='100'>100</option>
					</select>
					<span>filas por pág.</span>
				</div>
				<div id='navigation'>
					<img width='16' height='16' alt='Pág. 1'    src='css/images/first.gif'    onclick='sorter.move(-1,true)' />
					<img width='16' height='16' alt='Pág. Ant.' src='css/images/previous.gif' onclick='sorter.move(-1)' />
					<img width='16' height='16' alt='Próx. Pg.' src='css/images/next.gif'     onclick='sorter.move(1)' />
					<img width='16' height='16' alt='últ. Pág.' src='css/images/last.gif'     onclick='sorter.move(1,true)' />
				</div>
				<div id='text'>Pág. <span id='currentpage'></span> de <span id='pagelimit'></span></div>
			</div>

			<link rel="stylesheet"  href="css/ss.css" />
			<link rel="stylesheet"  href="css/tc_tinyTableSort_2.css" />
			<script type="text/javascript" src="js/tinyTableSorter.js"></script>
			<script type="text/javascript">
				var isIE = false;
				//$('#res th').addClass('nosort'); // Mover esta linha para o bloco de IE para ativar a ordenação.
			</script>
				<style type="text/css">
					table#res th h3 {cursor:default}
				</style>
<?	/* No IE está dando algum probleminha. Como a tela não tinha ordenação, tirei para o IE */ ?>
			<!--[if IE]>
				<script type="text/javascript">
					isIE = true;
				</script>
			<![endif]-->
			<script type="text/javascript">
				var program_self = location.pathname;
				var sorter       = new TINY.table.sorter('sorter');

				sorter.head      = 'head';         // header class name
				sorter.asc       = 'asc';          // ascending header class name
				sorter.desc      = 'desc';         // descending header class name
				sorter.even      = 'evenrow';      // even row class name
				sorter.odd       = 'oddrow';       // odd row class name
				sorter.evensel   = 'evenselected'; // selected column even class
				sorter.oddsel    = 'oddselected';  // selected column odd class
				sorter.paginate  = true;           // toggle for pagination logic
				sorter.pagesize  = 20;             // toggle for pagination logic
				sorter.currentid = 'currentpage';  // current page id
				sorter.limitid   = 'pagelimit';    // page limit id

				sorter.init('res');
				sorter.size(sorter.pagesize); //Dispara a paginação, para deixar a tabela OK desde o começo
			</script>
<?	} else {
		echo "<center>".traduz("Nenhum Resultado Encontrado")."</center>";
	}
	} else {
		$res = pg_query($con,$sql);
		$count = pg_numrows($res);
		if (pg_numrows($res) > 0) {

		    $file = "/tmp/assist/relatorio_field_call_rate_defeitos-$login_fabrica.xls";
			$fp = fopen ($file,"w");

			$excel= "<table id='resultado' class=\"table table-striped table-bordered table-hover table-fixed\" style='margin: 0 auto;' >";
			$excel .= "<thead><tr class='titulo_coluna'>";
			$excel .= "<th >".traduz("Peça")."</th>";
			$excel .= "<th >".traduz("Preço")."</th>";
			$excel .= "<th >".traduz("Qtde")."</th>";
			$excel .= "</tr></thead><tbody>";
			fputs($fp,$excel);
			echo $excel;
			$excel="";
			for ($i=0; $i<pg_numrows($res); $i++){

				$peca_descricao          = trim(pg_result($res,$i,peca_descricao))    ;
				$peca_referencia         = trim(pg_result($res,$i,peca_referencia))   ;
				$preco                   = trim(pg_result($res,$i,preco))             ;
				$qtde                    = trim(pg_result($res,$i,qtde))              ;
				$peca                    = trim(pg_result($res,$i,peca))              ;
				if($login_fabrica == 1) $preco = trim(pg_result($res,$i,custo_peca))  ;

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';

				$preco       = number_format ($preco,2,",",".")      ;

				$excel .= "<tr class='Conteudo'>";
				if($tem_link == "true"){
					$excel .= "<td bgcolor='$cor' align='left'><a href='relatorio_field_call_rate_defeitos.php?btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final&peca=$peca&familia=$familia&tipo_atendimento=$tipo_atendimento&aux=$aux&origem=$origem' target='_blank'>$peca_referencia - $peca_descricao</a></td>";
				}else{
					$excel .= "<td bgcolor='$cor' align='left'>$peca_referencia - $peca_descricao</td>";
				}
				$excel .= "<td bgcolor='$cor' align='right'>$real .$preco</td>";
				$excel .= "<td bgcolor='$cor' align='right'>$real . $qtde</td>";
				$excel .= "</tr>";
			}
			$excel .= "</tbody></table>";
			echo $excel;
			fputs($fp,$excel);
			fclose($fp);
			if(file_exists($file)) {
				system("mv $file xls/relatorio_peca_extrato_$login_fabrica.xls");
			}
		}
		else{
			echo "<center>".traduz("Nenhum Resultado Encontrado")."</center>";
		}



		echo "</table>";
			if ($count > 50) {
				echo '<script>
					$.dataTableLoad({ table: "#resultado" });
				</script>';
			}

			echo '<br />';

			echo "<div class='btn_excel'>
				<span><img src='imagens/excel.png' /></span>
				<span class='txt' onclick='javascript: window.open(\"xls/relatorio_peca_extrato_$login_fabrica.xls\")'; >".traduz("Gerar Arquivo Excel")."</span>
			</div>";

	}
}


}

include 'rodape.php';
?>
