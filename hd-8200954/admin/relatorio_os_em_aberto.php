<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "financeiro, gerencia, call_center";

include "autentica_admin.php";
include "funcoes.php";

if ($_POST["ajax_explode_tipo"]) {
	$intervalo     = $_POST["intervalo"];
	$tipo_pesquisa = $_POST["tipo_pesquisa"];
	$posto         = $_POST["posto"];
	$familia       = $_POST["familia"];
	$data_final    = $_POST["data_final"];
	$data_inicial  = $_POST["data_inicial"];


	if(strlen($data_inicial) > 0 && strlen($data_final) > 0){
        list($dia, $mes, $ano) = explode("/", $data_inicial);
        $data_inicio = $ano."-".$mes."-".$dia; 

        list($dia, $mes, $ano) = explode("/", $data_final);
        $data_fim = $ano."-".$mes."-".$dia;
    }



	

	if (empty($intervalo) || empty($tipo_pesquisa)) {
		$retorno = array("erro" => utf8_encode("Erro ao carregar informações"));
	} else {
		switch ($tipo_pesquisa) {
			case "posto":
				$coluna_id = "tbl_posto_fabrica.posto AS id";
				$coluna    = "tbl_posto.nome AS coluna";
				$group_by  = "tbl_posto_fabrica.posto, tbl_posto.nome";
				$order_by  = "tbl_posto.nome";

				$file     = "xls/relatorio-os-posto-aberto-{$login_fabrica}.csv";
				$fileTemp = "/tmp/relatorio-os-posto-aberto-{$login_fabrica}.csv" ;
				$head ="Posto Autorizado; Qtde OS's\r\n";

				break;
			
			case "familia":
				$coluna_id = "tbl_familia.familia AS id";
				$coluna    = "tbl_familia.descricao AS coluna";
				$group_by  = "tbl_familia.familia, tbl_familia.descricao";
				$order_by  = "tbl_familia.descricao";

				$file     = "xls/relatorio-os-familia-aberto-{$login_fabrica}.csv";
				$fileTemp = "/tmp/relatorio-os-familia-aberto-{$login_fabrica}.csv" ;
				$head = "Familia(Marca);Qtde OS's\r\n";

				break;
		}

		
		$fp     = fopen($fileTemp,'w');

		fwrite($fp, $head);

		switch ($intervalo) {

			case "0_1":
				$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '1 days') AND CURRENT_DATE) AND tbl_os.data_conserto IS NULL";
				break;

			case "2_3":
				$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '3 days') AND (CURRENT_DATE - INTERVAL '2 days')) AND tbl_os.data_conserto IS NULL";
				break;

			case "3_mais":
				$where = "AND tbl_os.data_abertura < (CURRENT_DATE - INTERVAL '3 days') AND tbl_os.data_conserto IS NULL";
				break;


			case "0_10":
				$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '10 days') AND CURRENT_DATE) AND tbl_os.data_conserto IS NULL";
				break;
			
			case "11_20":
				$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '20 days') AND (CURRENT_DATE - INTERVAL '11 days')) AND tbl_os.data_conserto IS NULL";
				break;

			case "21_30":
				$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '30 days') AND (CURRENT_DATE - INTERVAL '21 days')) AND tbl_os.data_conserto IS NULL";
				break;

			case "30_mais":
				$where = "AND tbl_os.data_abertura < (CURRENT_DATE - INTERVAL '30 days') AND tbl_os.data_conserto IS NULL";
				break;

			case "os_consertadas":
				$where = "AND tbl_os.data_conserto IS NOT NULL";
				break;
		}

		if (!empty($posto)) {
			$where_posto = " AND tbl_os.posto = {$posto} ";
		}

		if (!empty($familia)) {
			$where_familia = " AND tbl_produto.familia = {$familia} ";
		}

		$sql = "SELECT {$coluna_id}, {$coluna}, COUNT(tbl_os.os) AS quantidade_os
				FROM tbl_os
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
				INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.finalizada IS NULL
				AND tbl_os.data_fechamento IS NULL
				{$where}
				{$where_posto}
				{$where_familia}
				AND tbl_os.data_abertura BETWEEN '$data_inicio 00:00:00' AND '$data_fim 23:59:59'
				GROUP BY {$group_by}
				ORDER BY {$order_by} ASC";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$rows = pg_num_rows($res);

			$retorno = array(
				"resultado" => array()
			);
			
			for ($i = 0; $i < $rows; $i++) { 
				$id            = pg_fetch_result($res, $i, "id");
				$coluna        = utf8_encode(pg_fetch_result($res, $i, "coluna"));
				$quantidade_os = pg_fetch_result($res, $i, "quantidade_os");

				$retorno["resultado"][] = array(
					"id"            => $id,
					"coluna"        => $coluna,
					"quantidade_os" => $quantidade_os
				);
				$body .= "$coluna;$quantidade_os \r\n";
			}			

			fwrite($fp, $body);

			fwrite($fp, '');
		    fclose($fp);
		    if(file_exists($fileTemp)){
		        system("mv $fileTemp $file");

		        if(file_exists($file)){
		            //echo $file;
		            $retorno["nome_arquivo"] = $file;
		        }
		    }

		} else {
			$retorno = array("erro" => utf8_encode("Nenhum resultado encontrado"));
		}
	}

	exit(json_encode($retorno));
}

if ($_POST["ajax_explode_os"]) {
	$intervalo     = $_POST["intervalo"];
	$posto         = $_POST["posto"];
	$familia       = $_POST["familia"];
	$data_final    = $_POST["data_final"];
	$data_inicial  = $_POST["data_inicial"];


	if(strlen($data_inicial) > 0 && strlen($data_final) > 0){
        list($dia, $mes, $ano) = explode("/", $data_inicial);
        $data_inicio = $ano."-".$mes."-".$dia; 

        list($dia, $mes, $ano) = explode("/", $data_final);
        $data_fim = $ano."-".$mes."-".$dia;
    }



	if (empty($intervalo)) {
		$retorno = array("erro" => utf8_encode("Erro ao carregar informações"));
	} else {
		switch ($intervalo) {
			case "0_1":
				$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '1 days') AND CURRENT_DATE) AND tbl_os.data_conserto IS NULL";
				break;

			case "2_3":
				$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '3 days') AND (CURRENT_DATE - INTERVAL '2 days')) AND tbl_os.data_conserto IS NULL";
				break;

			case "3_mais":
				$where = "AND tbl_os.data_abertura < (CURRENT_DATE - INTERVAL '3 days') AND tbl_os.data_conserto IS NULL";
				break;


			case "0_10":
				$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '10 days') AND CURRENT_DATE) AND tbl_os.data_conserto IS NULL";
				break;
			
			case "11_20":
				$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '20 days') AND (CURRENT_DATE - INTERVAL '11 days')) AND tbl_os.data_conserto IS NULL";
				break;

			case "21_30":
				$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '30 days') AND (CURRENT_DATE - INTERVAL '21 days')) AND tbl_os.data_conserto IS NULL";
				break;

			case "30_mais":
				$where = "AND tbl_os.data_abertura < (CURRENT_DATE - INTERVAL '30 days') AND tbl_os.data_conserto IS NULL";
				break;

			case "os_consertadas":
				$where = "AND tbl_os.data_conserto IS NOT NULL";
				break;
		}

		if (!empty($posto)) {
			$where_posto = " AND tbl_os.posto = {$posto} ";
		}

		if (!empty($familia)) {
			$where_familia = " AND tbl_produto.familia = {$familia} ";
		}

		$sql = "SELECT 
					tbl_os.os, 
					tbl_os.sua_os, 
					TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura, 
					(CURRENT_DATE - tbl_os.data_abertura) AS dias_em_aberto,
					tbl_posto.nome AS posto,
					tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
					TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY') AS data_conserto
				FROM tbl_os
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.finalizada IS NULL
				AND tbl_os.data_fechamento IS NULL
				AND tbl_os.data_abertura BETWEEN '$data_inicio 00:00:00' AND '$data_fim 23:59:59'
				{$where}
				{$where_posto}
				{$where_familia}
				ORDER BY dias_em_aberto DESC";
		$res = pg_query($con, $sql);

		$file     = "xls/relatorio-os-todas-aberto-{$login_fabrica}.csv";
		$fileTemp = "/tmp/relatorio-os-todas-aberto-{$login_fabrica}.csv" ;
		$fp     = fopen($fileTemp,'w');

		$head ="OS;Data Abertura;Dias Aberto; Posto Autorizado; Produto\r\n";

		fwrite($fp, $head);

		if (pg_num_rows($res) > 0) {
			$rows = pg_num_rows($res);

			$retorno = array(
				"resultado" => array()
			);
			
			for ($i = 0; $i < $rows; $i++) { 
				$os             = pg_fetch_result($res, $i, "os");
				$sua_os         = pg_fetch_result($res, $i, "sua_os");
				$data_abertura  = pg_fetch_result($res, $i, "data_abertura");
				$dias_em_aberto = pg_fetch_result($res, $i, "dias_em_aberto");
				$posto          = pg_fetch_result($res, $i, "posto");
				$produto        = pg_fetch_result($res, $i, "produto");
				$data_conserto  = pg_fetch_result($res, $i, "data_conserto");

				$retorno["resultado"][] = array(
					"os"             => $os,   
					"sua_os"         => $sua_os,
					"data_abertura"  => utf8_encode($data_abertura),
					"dias_em_aberto" => utf8_encode($dias_em_aberto),
					"posto"          => utf8_encode($posto),
					"produto"        => utf8_encode($produto),
					"data_conserto"  => utf8_encode($data_conserto) 
				);

				$body .= "$os;".utf8_encode($data_abertura).";$dias_em_aberto;".utf8_encode($posto).";".utf8_encode($produto)."\r\n";
			}

			fwrite($fp, $body);

			fwrite($fp, '');
		    fclose($fp);
		    if(file_exists($fileTemp)){
		        system("mv $fileTemp $file");

		        if(file_exists($file)){
		            //echo $file;
		            $retorno["nome_arquivo"] = $file;
		        }
		    }

		} else {
			$retorno = array("erro" => utf8_encode("Nenhum resultado encontrado"));
		}
	}



	exit(json_encode($retorno));
}

if ($_POST["btn_acao"] == "submit") {
	$codigo_posto    = $_POST["codigo_posto"];
	$descricao_posto = $_POST["descricao_posto"];
	$familia         = $_POST["familia"];


	if (strlen(trim($_POST["data_inicio"])) > 0) $xdata_inicial = trim($_POST["data_inicio"]);
    if (strlen(trim($_POST["data_fim"])) > 0) $xdata_final = trim($_POST["data_fim"]);


    if ((strlen(trim($_POST["data_inicio"])) == 0  ) OR (strlen(trim($_POST["data_fim"])) == 0  )){
    	$msg_erro["msg"][]    =" Informe as datas de inicio e fim. <br> ";
    }

    if(strlen($xdata_inicial) > 0 && strlen($xdata_final) > 0){
        list($ano, $mes, $dia) = explode("-", $xdata_inicial);
        $data_inicio = $ano."-".$mes."-".$dia;

        list($ano, $mes, $dia) = explode("-", $xdata_final);
        $data_fim = $ano."-".$mes."-".$dia;

        if($xdata_inicial > $xdata_final){
            $msg_erro["msg"][]    ="Data Inicial maior que final";
            $msg_erro["campos"][] = "data_inicial";
        }

        if(strtotime($xdata_final) > strtotime($xdata_inicial . ' +3 month')){
            $msg_erro["msg"][]    = "O período não pode maior que 3 meses";
        }
        if (count($msg_erro) == 0) {
           $sql_cond5 = " AND tbl_os.data_abertura BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' ";
        }
    }


	if (strlen($codigo_posto) > 0 && strlen($descricao_posto) > 0) {
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				WHERE (
					UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}')
					AND
					TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9')
				)";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto Autorizado não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

	if (!count($msg_erro["msg"])) {
		if (!empty($posto)) {
			$where_posto = " AND tbl_os.posto = {$posto} ";
		}

		if (!empty($familia)) {
			$where_familia = " AND tbl_produto.familia = {$familia} ";
		}

		if($login_fabrica == 15){
			$qtde = 1;
		}else{
			$qtde = 10;
		}


		$sql_0_10 = "SELECT COUNT(tbl_os.os) AS quantidade_os
					 FROM tbl_os
					 INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
					 WHERE tbl_os.fabrica = {$login_fabrica}
					 AND tbl_os.finalizada IS NULL
					 AND tbl_os.data_fechamento IS NULL
					 AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '$qtde days') AND CURRENT_DATE)
					 AND tbl_os.data_conserto IS NULL
					 {$where_posto}
					 {$where_familia}
					 {$sql_cond5}";
		$res_0_10 = pg_query($con, $sql_0_10);


		if($login_fabrica == 15){
			$qtde_max = 3;
			$qtde_min = 2;
		}else{
			$qtde_max = 20;
			$qtde_min = 11;
		}

		$sql_11_20 = "SELECT COUNT(tbl_os.os) AS quantidade_os
					  FROM tbl_os
					  INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
					  WHERE tbl_os.fabrica = {$login_fabrica}
					  AND tbl_os.finalizada IS NULL
					  AND tbl_os.data_fechamento IS NULL
					  AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '$qtde_max days') AND (CURRENT_DATE - INTERVAL '$qtde_min days'))
					  AND tbl_os.data_conserto IS NULL
					  {$where_posto}
					  {$where_familia}
					  {$sql_cond5}";
		$res_11_20 = pg_query($con, $sql_11_20);

		if($login_fabrica != 15){

			$sql_21_30 = "SELECT COUNT(tbl_os.os) AS quantidade_os
						  FROM tbl_os
						  INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
						  WHERE tbl_os.fabrica = {$login_fabrica}
						  AND tbl_os.finalizada IS NULL
						  AND tbl_os.data_fechamento IS NULL
						  AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '30 days') AND (CURRENT_DATE - INTERVAL '21 days'))
						  AND tbl_os.data_conserto IS NULL
						  {$where_posto}
						  {$where_familia}";
			$res_21_30 = pg_query($con, $sql_21_30);

		}

		if($login_fabrica == 15){
			$qtde = 3;
		}else{
			$qtde = 30;
		}

		$sql_30_mais = "SELECT COUNT(tbl_os.os) AS quantidade_os
						FROM tbl_os
					 	INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
					 	WHERE tbl_os.fabrica = {$login_fabrica}
					 	AND tbl_os.finalizada IS NULL
					 	AND tbl_os.data_fechamento IS NULL
					 	AND tbl_os.data_abertura < (CURRENT_DATE - INTERVAL '$qtde days')
					 	AND tbl_os.data_conserto IS NULL
					 	{$where_posto}
					 	{$where_familia}
					 	{$sql_cond5}";
		$res_30_mais = pg_query($con, $sql_30_mais);

		$sql_os_consertadas = "SELECT COUNT(tbl_os.os) AS quantidade_os
							   FROM tbl_os
							   INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
							   WHERE tbl_os.fabrica = {$login_fabrica}
							   AND tbl_os.finalizada IS NULL
							   AND tbl_os.data_fechamento IS NULL
							   AND tbl_os.data_conserto IS NOT NULL
							   {$where_posto}
							   {$where_familia}";
		$res_os_consertadas = pg_query($con, $sql_os_consertadas);
	}
}



if($login_fabrica == 15){
	$layout_menu = "auditoria";
}else{
	$layout_menu = "callcenter";
}

$title = "RELATÓRIO DE OS'S EM ABERTO";

include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask"
);

include "plugin_loader.php";

?>

<style>

#informacoes_explodidas, #resultado, #resultado_os {
	display: none;
}

</style>

<script>

$(function() {
	$("#data_inicio").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
    $("#data_fim").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");


	Shadowbox.init();
	$.datepickerLoad(["data_final", "data_inicial"]);
	$.autocompleteLoad(["posto"]);

	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});

	$("button[name=explodir]").click(function() {
		var rel = $(this).attr("rel");

		switch(rel) {
			case "0_1":
				$("#informacoes_explodidas > thead th").text("OS's em aberto de 0 á 1 dia");
				break;

			case "2_3":
				$("#informacoes_explodidas > thead th").text("OS's em aberto de 2 á 3 dias");
				break;

			case "3_mais":
				$("#informacoes_explodidas > thead th").text("OS's em aberto a mais de 3 dias");
				break;


			case "0_10":
				$("#informacoes_explodidas > thead th").text("OS's em aberto de 0 á 10 dias");
				break;

			case "11_20":
				$("#informacoes_explodidas > thead th").text("OS's em aberto de 11 á 20 dias");
				break;

			case "21_30":
				$("#informacoes_explodidas > thead th").text("OS's em aberto de 21 á 30 dias");
				break;

			case "30_mais":
				$("#informacoes_explodidas > thead th").text("OS's em aberto a mais de 30 dias");
				break;

			case "os_consertadas":
				$("#informacoes_explodidas > thead th").text("OS's aguardando retirada");
				break;
		}

		$("#informacoes_explodidas").attr({ rel: rel }).show();

		limpa_resultado();
	});

	$("button[name=explode_tipo]").click(function() {
		limpa_resultado();

		var rel = $(this).attr("rel");

		switch(rel) {
			case "posto":
				$("#tipo_pesquisa").text("Posto Autorizado");
				break;

			case "familia":
				$("#tipo_pesquisa").text("Família(Marca)");
				break;
		}

		var intervalo     = $("#informacoes_explodidas").attr("rel");
		var tipo_pesquisa = rel;
		var posto         = "<?=$posto?>";
		var familia       = "<?=$familia?>";
		var data_inicial  = "<?=$xdata_inicial?>";
		var data_final    = "<?=$xdata_final?>";

		var data = {
			ajax_explode_tipo: true,
			intervalo: intervalo,
			tipo_pesquisa: tipo_pesquisa,
			data_inicial: data_inicial,
			data_final: data_final
		};

		if (typeof posto != "undefined") {
			data.posto = posto;
		}

		if (typeof familia != "undefined") {
			data.familia = familia;
		}

		var loading = "<div class='alert alert-info' ><h4>Carregando informações, por favor aguarde.</h4></div>";

		$.ajax({
			async: false,
			url: "relatorio_os_em_aberto.php",
			type: "post",
			data: data,
			beforeSend: function() {
				$("#resultado").before(loading);
			}
		}).always(function(data) {
			data = $.parseJSON(data);

			$("#resultado").prev("div.alert").remove();

			if (data.erro) {
				$("#resultado").before("<div id='resultado_erro' class='alert alert-error' ><h4>"+data.erro+"</h4></div>");
			} else {
				$.each(data.resultado, function(key, value) {
					$("#resultado > tbody").append("\
						<tr>\
							<td>"+value.coluna+"</td>\
							<td class='tac' >"+value.quantidade_os+"</td>\
							<td class='tac' ><button name='explode_os_tipo_pesquisa' intervalo='"+intervalo+"' tipo_pesquisa='"+rel+"' rel='"+value.id+"' type='button' class='btn btn-success btn-mini'>Ver OS's</button></td>\
						</tr>\
					");
				});

				$("#resultado").show();

				$(".btn_excel").empty();
				$(".btn_excel").append("\
						<span><a href='"+data.nome_arquivo+"'><img src='imagens/excel.png' /><span class='txt'>Gerar Arquivo Excel</span></a></span>");

			}
		});
	});

	$("button[name=explode_os]").click(function() {
		limpa_resultado();

		var intervalo     = $("#informacoes_explodidas").attr("rel");
		var posto         = "<?=$posto?>";
		var familia       = "<?=$familia?>";
		var data_inicial  = "<?=$xdata_inicial?>";
		var data_final    = "<?=$xdata_final?>";

		var data = {
			ajax_explode_os: true,
			intervalo: intervalo,
			data_inicial: data_inicial,
			data_final:data_final
		};

		if (typeof posto != "undefined") {
			data.posto = posto;
		}

		if (typeof familia != "undefined") {
			data.familia = familia;
		}

		var loading = "<div class='alert alert-info' ><h4>Carregando informações, por favor aguarde.</h4></div>";

		$.ajax({
			async: false,
			url: "relatorio_os_em_aberto.php",
			type: "post",
			data: data,
			beforeSend: function() {
				$("#resultado_os").before(loading);
			}
		}).always(function(data) {
			data = $.parseJSON(data);

			$("#resultado_os").prev("div.alert").remove();

			if (data.erro) {
				$("#resultado_os").before("<div id='resultado_erro' class='alert alert-error' ><h4>"+data.erro+"</h4></div>");
			} else {
				$.each(data.resultado, function(key, value) {
					$("#resultado_os > tbody").append("\
						<tr>\
							<td class='tac' ><a href='os_press.php?os="+value.os+"' target='_blank' >"+value.sua_os+"</a></td>\
							<td class='tac' >"+value.data_abertura+"</td>\
							<td class='tac' >"+value.dias_em_aberto+"</td>\
							"+((intervalo == "os_consertadas") ? "<td class='tac' >"+value.data_conserto+"</td>" : "")+"\
							<td class='tac' >"+value.posto+"</td>\
							<td class='tac' >"+value.produto+"</td>\
						</tr>\
					");
				});

				$(".btn_excel").empty();
				$(".btn_excel").append("\
						<span><a href='"+data.nome_arquivo+"'><img src='imagens/excel.png' /><span class='txt'>Gerar Arquivo Excel</span></a></span>");

				if (intervalo == "os_consertadas") {
					$("#resultado_os").find("#titulo_os_consertada").show();
				} else {
					$("#resultado_os").find("#titulo_os_consertada").hide();
				}

				$("#resultado_os").show();
			}
		});
	});

	$(document).on("click", "button[name=explode_os_tipo_pesquisa]", function() {
		var intervalo     = $(this).attr("intervalo");
		var tipo_pesquisa = $(this).attr("tipo_pesquisa");
		var id            = $(this).attr("rel");
		var data_inicial  = "<?=$xdata_inicial?>";
		var data_final    = "<?=$xdata_final?>";
		var codigo_posto  = $("#codigo_posto").val();

		Shadowbox.open({
			content: "relatorio_os_em_aberto_explode_os.php?intervalo="+intervalo+"&tipo_pesquisa="+tipo_pesquisa+"&id="+id+"&data_inicial="+data_inicial+"&data_final="+data_final+"&codigo_posto="+codigo_posto,
			player: "iframe",
			width: 800,
			height: 600
		});
	});
});

function limpa_resultado() {
	$("#resultado").hide();
	$("#resultado > tbody > tr").remove();

	$("#resultado_os").hide();
	$("#resultado_os > tbody > tr").remove();

	$("#resultado_erro").remove();
}

function retorna_posto(retorno) {
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

</script>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error" >
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>

<div class="row" >
	<b class="obrigatorio pull-right" >  * Campos obrigatórios </b>
</div>

<form name="frm_relatorio_oss_em_aberto" method="post" class="form-search form-inline tc_formulario" >
	<div class="titulo_tabela" >Parâmetros de Pesquisa</div>
	<br />
	<div class='row-fluid'>
        <div class='span2'></div>
        <div class="span2">
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>' >
                <label class="control-label" for="data_inicio">Data Inicio</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class='asteristico'>*</h5>
                        <input id="data_inicio" name="data_inicio" class="span12" type="text" value="<?=$data_inicio?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
        <div class="span2">
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>' >
                <label class="control-label" for="data_fim">Data Fim</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class='asteristico'>*</h5>
                        <input id="data_fim" name="data_fim" class="span12" type="text" value="<?=$data_fim?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4"></div>
    </div>
	<div class="row-fluid" >
		<div class="span2" ></div>

		<div class="span4" >
			<div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="codigo_posto" >Código Posto</label>

				<div class="controls controls-row" >
					<div class="span7 input-append" >
						<input type="text" name="codigo_posto" id="codigo_posto" class="span12" value="<? echo $codigo_posto ?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>

		<div class="span4" >
			<div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="descricao_posto" >Nome Posto</label>

				<div class="controls controls-row" >
					<div class="span12 input-append" >
						<input type="text" name="descricao_posto" id="descricao_posto" class="span12" value="<? echo $descricao_posto ?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>

		<div class="span2" ></div>
	</div>

	<div class="row-fluid" >
		<div class="span2" ></div>

		<div class="span8" >
			<div class="control-group <?=(in_array('familia', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="codigo_posto" >Família</label>

				<div class="controls controls-row" >
					<div class="span7 input-append" >
						<select id="familia" name="familia" >
							<option value="" >Selecione</option>

							<?php

							$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$login_fabrica} ORDER BY descricao ASC";
							$res = pg_query($con, $sql);

							if (pg_num_rows($res) > 0) {
								while ($result = pg_fetch_object($res)) {
									$selected = ($familia == $result->familia) ? "selected" : "";

									echo "<option value='{$result->familia}' {$selected} >{$result->descricao}</option>";
								}
							}

							?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class="span2" ></div>
	</div>

	<p>
		<br/>
		<button class="btn" id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));" >Pesquisar</button>
		<input type="hidden" id="btn_click" name="btn_acao" />
	</p>

	<br/>
</form>

<?php

if ($_POST["btn_acao"] == "submit" && !count($msg_erro["msg"])) {
	$oss_em_aberto = array(
		"0_10"           => pg_fetch_result($res_0_10, 0, "quantidade_os"),
		"11_20"          => pg_fetch_result($res_11_20, 0, "quantidade_os"),
		"21_30"          => pg_fetch_result($res_21_30, 0, "quantidade_os"),
		"30_mais"        => pg_fetch_result($res_30_mais, 0, "quantidade_os"),
		"os_consertadas" => pg_fetch_result($res_os_consertadas, 0, "quantidade_os")
	);

	?>

	<table class="table table-striped table-bordered" style="table-layout: fixed;" >
		<thead>
			<tr class="titulo_coluna" >

				<th colspan="<? echo ($login_fabrica == 15)? 3 : 5; ?>" >OS's em Aberto</th>
			</tr>
			<?php if($login_fabrica == 15){?>
				<tr class="titulo_coluna" >
					<th>0 á 1 dia</th>
					<th>2 á 3 dias</th>					
					<th>Mais de 3 dias</th>					
				</tr>
			<?php }else{ ?>
				<tr class="titulo_coluna" >
					<th>0 á 10 dias</th>
					<th>11 á 20 dias</th>
					<th>21 á 30 dias</th>
					<th>Mais de 30 dias</th>
					<th>Aguardando retirada</th>
				</tr>
			<?php } ?>
		</thead>
		<tbody>
			<tr>
				<td class="tac" >
					<?=$oss_em_aberto["0_10"]?>

					<?php if ($oss_em_aberto["0_10"] > 0) { ?>
						<br />
						<?php if($login_fabrica == 15){
							$rel = "0_1";
						}else{
							$rel = "0_10";
						}?>
						<button name="explodir" rel="<?=$rel?>" type="button" class="btn btn-info btn-small btn-block" >Explodir</button>
					<?php } ?>
				</td>
				<td class="tac" >
					<?=$oss_em_aberto["11_20"]?>

					<?php if ($oss_em_aberto["11_20"] > 0) { ?>
						<?php if($login_fabrica == 15){
							$rel = "2_3";
						}else{
							$rel = "11_20";
						}?>
						<br />
						<button name="explodir" rel="<?=$rel?>" type="button" class="btn btn-info btn-small btn-block" >Explodir</button>
					<?php } ?>
				</td>
				<?php if($login_fabrica != 15 ){ ?>
				<td class="tac" >
					<?=$oss_em_aberto["21_30"]?>

					<?php if ($oss_em_aberto["21_30"] > 0) { ?>
						<br />
						<button name="explodir" rel="21_30" type="button" class="btn btn-info btn-small btn-block" >Explodir</button>
					<?php } ?>
				</td>
				<?php } ?>
				<td class="tac" >
					<?=$oss_em_aberto["30_mais"]?>

					<?php if ($oss_em_aberto["30_mais"] > 0) { ?>
						<?php if($login_fabrica == 15){
							$rel = "3_mais";
						}else{
							$rel = "30_mais";
						}?>
						<br />
						<button name="explodir" rel="<?=$rel?>" type="button" class="btn btn-info btn-small btn-block" >Explodir</button>
					<?php } ?>
				</td>
				<?php if($login_fabrica != 15 ){?>
				<td class="tac" >
					<?=$oss_em_aberto["os_consertadas"]?>

					<?php if ($oss_em_aberto["os_consertadas"] > 0) { ?>
						<br />
						<button name="explodir" rel="os_consertadas" type="button" class="btn btn-info btn-small btn-block" >Explodir</button>
					<?php } ?>
				</td>
				<?php } ?>
			</tr>
		</tbody>
	</table>

	<table id="informacoes_explodidas" class="table table-striped table-bordered" style="table-layout: fixed;" >
		<thead>
		 	<tr class="titulo_coluna" >
		 		<th colspan="<?=(!empty($posto) || !empty($familia)) ? 2 : 3?>" ></th>
		 	</tr>
		</thead>
		<tbody>
			<tr>
				<?php
				if (empty($posto)) {
				?>
					<td class="tac" >
						<button name="explode_tipo" rel="posto" type="button" class="btn btn-info btn-small" >Visualizar por Posto Autorizado</button>
					</td>
				<?php
				}

				if (empty($familia)) {
				?>
					<td class="tac" >
						<button name="explode_tipo" rel="familia" type="button" class="btn btn-info btn-small" >Visualizar por Família(Marca)</button>
					</td>
				<?php
				}
				?>
				<td class="tac" >
					<button name="explode_os" type="button" class="btn btn-info btn-small" >Visualizar todas as OS's</button>
				</td>
			</tr>
		</tbody>
	</table>

	<table id="resultado" class="table table-striped table-bordered" >
		<thead>
			<tr class="titulo_coluna" >
				<th id="tipo_pesquisa" ></th>
				<th>OS's</th>
				<th>Ver OS's</th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>

	<table id="resultado_os" class="table table-striped table-bordered" >
		<thead>
			<tr class="titulo_coluna" >
				<th>OS</th>
				<th>Data de Abertura</th>
				<th>Dias em Aberto</th>
				<th id="titulo_os_consertada" style="display: none;" >Data de Conserto</th>
				<th>Posto Autorizado</th>
				<th>Produto</th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>

	<div class="btn_excel">
                
    </div>

<?php

}

include "rodape.php";

?>