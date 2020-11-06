<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../autentica_admin.php';
include '../funcoes.php';
include_once "../../classes/mpdf61/mpdf.php";
include_once '../../class/communicator.class.php';
include_once "../plugins/fileuploader/TdocsMirror.php";

if (isset($_POST['ri_transferencia'])) {

	try {

		$riClass = new \Mirrors\Ri\RiMirror($login_fabrica,$login_admin);

		$retorno = $riClass->transfere($_POST);

		$emails = $riClass->getEmailsByFollowup($_POST["ri_transferencia"]["ri_followup"]);

		$assunto = "Transferência de RI";
		$mensagem = "Prezado, <br /><br /> O relatório Informativo (RI) de número <strong>{$_POST["ri_id"]}</strong> foi transferido para o seu grupo de Follow-up";

		foreach ($emails as $email) {

			$mailTc = new TcComm($externalId);
	        $res = $mailTc->sendMail(
	            $email,
	            $assunto,
	            $mensagem,
	            $externalEmail
	        );

		}

		exit(json_encode([
	    	"success" => true
	    ]));

	} catch(\Exception $e){

	    exit(json_encode([
	    	"error" => utf8_encode($e->getMessage())
	    ]));

	}

}

if (isset($_POST['order'])) {

	try {

		$indexCol 	   = $_POST['order'][0]['column'];
		$colunaOrdenar = $_POST['columns'][$indexCol]['data'];

		$data = [];
		if (!in_array($colunaOrdenar, ["unorderable"])) {

			$riMirror = new \Mirrors\Ri\RiMirror($login_fabrica, $login_admin);

			$indexCol = $_POST['order'][0]['column'];

			$objRequest = [
				"offset"      => $_POST['start'],
				"limit"       => $_POST['length'],
				"orderBy"  	  => $colunaOrdenar,
				"order"    	  => $_POST['order'][0]['dir'],
				"strPesquisa" => urlencode($_POST['search']['value']),
				"paginate"    => true
			];

			$dadosPesquisa = [];
			if (isset($_GET['btn_acao'])) {
				$dadosPesquisa = [
					"dataInicial" 		=> $_GET["data_inicial"],
					"dataFinal"   		=> $_GET["data_final"],
					"codigo"            => $_GET['codigo'],
					"titulo"            => urlencode($_GET['titulo']),
					"familia"           => $_GET['familia'],
					"followup"          => $_GET['followup'],
					"qualidade"         => $_GET['qualidade'],
					"status"            => urlencode($_GET['status'])
				];
			}
			
			$retorno = $riMirror->relatorio($objRequest, $dadosPesquisa);

			$data = [];
			foreach ($retorno as $key => $campos) {

				if ($campos["total_produtos"] <= 5) {
					$cor = "green";
				} else if ($campos["total_produtos"] > 5 && $campos["total_produtos"] <= 10) {
					$cor = "#b5a855";
				} else {
					$cor = "red";
				}

				$btnPdf = "";
				if ($campos["status_ri"] == "Finalizado") {
					$btnPdf = '<button type="button" class="btn btn-sm btn-danger gerar-pdf" data-ri="'.$campos["ri"].'" title="Baixar PDF">
				                <i class="glyphicon glyphicon glyphicon-file"></i>
				            </button>';
				}

				$data[] = [
					"tbl_ri-ri" 			  	  => $campos["ri"],
					"tbl_ri-codigo" 			  => $campos["codigo_ri"],
					"tbl_familia-nome"  		  => $campos["nome_familia"],
					"tbl_ri-qualidade"  		  => $campos["qualidade"],
					"tbl_ri-titulo"               => $campos["titulo"],
					"tbl_ri-data_abertura" 		  => $campos["data_abertura"],
					"tbl_ri-data_chegada"         => $campos["data_chegada"],
					"tbl_admin-nome_completo" 	  => $campos["responsavel"],
					"dias_uteis_aberto" 		  => $campos["dias_uteis_aberto"],
					"tbl_ri_transferencia-status" => $campos["status_ri"],
					"tbl_ri_followup-nome" 		  => $campos["grupo_followup"],
					"total_produtos" 			  => "<span style='color: {$cor};font-weight: bolder;font-size: 16px;'>".$campos["total_produtos"]."</span>",
					"unorderable" => "
						<a target='_blank' href='cadastro_relatorio_informativo.php?ri_id={$campos['ri']}' class='btn btn-primary btn-sm alterar-ri' title='Visualizar / Alterar'><i class='glyphicon glyphicon glyphicon-pencil'></i></a>
						{$btnPdf}
					"
				];

			}

		}

		$response = array(
		  "draw" => (int) $_POST['draw'],
		  "iTotalRecords" => count($retorno),
		  "iTotalDisplayRecords" => $retorno["totalRegistros"],
		  "aaData" => $data
		);


	} catch(\Exception $e){

	}

	$response = array(
	  "draw" => (int) $_POST['draw'],
	  "iTotalRecords" => count($retorno),
	  "iTotalDisplayRecords" => $retorno["totalRegistros"],
	  "aaData" => $data
	);

	exit(json_encode($response));

}

if (isset($_POST['gerar_pdf'])) {
	try {

		$riId = $_POST['ri'];

		$riMirror = new \Mirrors\Ri\RiMirror($login_fabrica, $login_admin);

		$retorno = $riMirror->consulta($riId);

		$retorno = array_map_recursive("utf8_decode", $retorno);

		$pdf = new mPDF(['mode' => 'utf-8', 'format' => 'A4-L']);

		$pdf->allow_html_optional_endtags = true;
	    $pdf->setAutoTopMargin = 'stretch';
	    $pdf->SetTitle("Relatório Informativo");
	    $pdf->SetDisplayMode('fullpage');

		include "template_pdf.php";

	    $caminho = "../xls/relatorio_informativo_{$riId}_".date("Ymdhis").".pdf";
	    
	    $pdf->Output($caminho,'F');

	    exit($caminho);

	} catch(\Exception $e){

	    exit(json_encode([
	    	"error" => utf8_encode($e->getMessage())
	    ]));

	}
}

if (isset($_POST['excluir_ri_grupo'])) {
	try {

		$riMirror = new \Mirrors\Ri\RiMirror($login_fabrica, $login_admin);

		$riGrupo = $_POST["riGrupo"];

		$riMirror->deletaAdminFollowup($riGrupo);

		exit(json_encode(["success" => true]));

	} catch(\Exception $e){

		 exit(json_encode([
	    	"error" => utf8_encode($e->getMessage())
	    ]));

	}

}

if (isset($_POST['icone_atendimentos_ri'])) {
	try {

		$riMirror = new \Mirrors\Ri\RiMirror($login_fabrica, $login_admin);

		$arrFollowups = $riMirror->getFollowupByAdmin();

		$retorno = [];
		if (count($arrFollowups) > 0) {

			$sqlRi = "SELECT tbl_ri.titulo,
					  		 tbl_ri.ri,
					  		 tbl_ri_transferencia.date
					  FROM tbl_ri
					  JOIN tbl_ri_transferencia ON tbl_ri.ri = tbl_ri_transferencia.ri
					  WHERE tbl_ri_transferencia.ri_followup IN (".implode(",", $arrFollowups).")
					  AND tbl_ri_transferencia.status != 'Resolvido'
					  AND tbl_ri.fabrica = {$login_fabrica}";
			$resRi = pg_query($con, $sqlRi);

			while ($dados = pg_fetch_object($resRi)) {

				$retorno[$dados->ri] = [
					"ri" => $dados->ri,
					"titulo" => $dados->titulo,
					"data_transferencia" => mostra_data_hora($dados->date)
				]; 

			}

		}

	} catch(\Exception $e){

		 exit(json_encode([
	    	"error" => utf8_encode($e->getMessage())
	    ]));

	}

	exit(json_encode($retorno));

}