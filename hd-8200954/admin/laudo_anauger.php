<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
 
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($areaAdmin === true) {
	include "autentica_admin.php";
	include_once "plugins/fileuploader/TdocsMirror.php";
} else {
	include "autentica_usuario.php";
	include_once "plugins/fileuploader/TdocsMirror.php";
}
include "funcoes.php";

$os = $_REQUEST["os"];
$auditoria = $_REQUEST["auditoria"];
$defeito_constatado = $_REQUEST["defeito_constatado"];
$dados_inseridos = false;

if($login_fabrica != 177){
	echo "Acesso não autorizado";exit;
}

if($_POST['btn_acao'] ==  "Enviar") {
	$dados = $_POST;
	
	unset($dados['btn_acao']);
	
	if (empty($defeito_constatado)){
		$msg_erro = "Favor preencher o defeito constatado";
	}

	$sql_laudo = "
		SELECT laudo_tecnico_os, JSON_FIELD('auditoria',observacao) AS id_auditoria
		FROM tbl_laudo_tecnico_os
		WHERE os = {$os}
		AND fabrica = {$login_fabrica}
	";
	$res_laudo = pg_query($con, $sql_laudo);

	if(pg_num_rows($res_laudo) > 0){
		$id_auditoria = pg_fetch_result($res_laudo, 0, 'id_auditoria');

		if($auditoria == $id_auditoria){
			$laudo_tecnico_os = pg_fetch_result($res_laudo, 0, 'laudo_tecnico_os');
			$update_laudo = true;
		} else {
			$update_laudo = false;
		}
	} else {
		$update_laudo = false;
	}

	$sqlPecas = "SELECT tbl_os_item.peca,
						tbl_os_item.qtde,
						tbl_os_item.servico_realizado,
						tbl_os_item.fabrica_i,
						tbl_os.posto,
						tbl_os_item.os_item,
						tbl_servico_realizado.peca_estoque,
						tbl_os_item.pedido_item,
						tbl_pedido_item.pedido,
						CASE WHEN tbl_os_item.pedido_item IS NOT NULL THEN tbl_pedido_item.qtde - (COALESCE(tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)) ELSE 0 END AS qtde_pendente
				FROM tbl_os_item
				JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
				JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = $login_fabrica
				JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
				LEFT JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
				WHERE tbl_os.os = $os";
	$resPecas = pg_query($con, $sqlPecas);

	if(strlen(pg_last_error()) > 0) {
        $msg_erro = "Erro buscar dados da OS ITEM";
    }

    $sql_servico = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = $login_fabrica AND descricao = 'Cancelado'";

    $res_servico = pg_query($con, $sql_servico);
    if(strlen(pg_last_error()) > 0) {
        $msg_erro = "Erro ao encontrar o serviço realizado.";
    }

    if(pg_num_rows($res_servico) > 0){
    	$id_servico = pg_fetch_result($res_servico, 0, 'servico_realizado');
    }

	$valida = $dados;

	unset($valida["pecas"]);
	unset($valida["data_abertura"]);
	unset($valida["numero_os"]);
	unset($valida["nota_fiscal"]);
	unset($valida["data_nf"]);
	unset($valida["referencia_produto"]);
	unset($valida["descricao_produto"]);
	unset($valida["numero_serie"]);
	unset($valida["codigo_fabricacao"]);
	unset($valida["descricao_defeito_reclamado"]);
	unset($valida["defeito_constatado_descricao"]);

	unset($valida["equipamento"]);
	unset($valida["cilindro"]);
	unset($valida["toner"]);
	unset($valida["cartucho_bk"]);
	unset($valida["cartucho_m"]);
	unset($valida["cartucho_y"]);
	unset($valida["cartucho_c"]);
	unset($valida["anexo_laudo_1"]);
	unset($valida["anexo_laudo_2"]);
	unset($valida["anexo_laudo_3"]);
	
	foreach ($valida as $key => $value) {
		if(strlen(trim($value)) == 0 && !in_array($key, array("anexo_laudo_1", "anexo_laudo_2", "anexo_laudo_3"))){				
			$msg_erro = "Favor preencher todos os campos";
			$msg_error["campos"][] = "{$key}";
		}
	}

	if(empty($msg_erro)){

		foreach ($dados as $key => $value) {
			if($key != "pecas"){
				$dados[$key] = utf8_encode($value);
			}
		}
		foreach ($dados["pecas"] as $key1 => $value1) {
			$dados["pecas"][$key1] = utf8_encode($value1);
		}

		$dados_insert = json_encode($dados);
		pg_query($con, "BEGIN");

		if (!empty($defeito_constatado)){
			$updateDefeito = "UPDATE tbl_os_produto SET defeito_constatado = {$defeito_constatado} WHERE os = {$os}";
			$resDefeito = pg_query($con, $updateDefeito);

			if (strlen(pg_last_error()) > 0){
				$msg_erro = "Erro ao gravar defeito constatado";
			}
		}

		if(pg_num_rows($resPecas) > 0 && $update_laudo === false){
			for ($i=0; $i < pg_num_rows($resPecas); $i++) {
				$id_peca 	= pg_fetch_result($resPecas, $i, 'peca');
				$qtde_peca 	= pg_fetch_result($resPecas, $i, 'qtde');
				$id_posto 	= pg_fetch_result($resPecas, $i, 'posto');
				$os_item 	= pg_fetch_result($resPecas, $i, 'os_item');
				$peca_estoque   = pg_fetch_result($resPecas, $i, 'peca_estoque');
				$pedido_item    = pg_fetch_result($resPecas, $i, 'pedido_item');
				$pedido         = pg_fetch_result($resPecas, $i, 'pedido');
				$qtde_pendente  = pg_fetch_result($resPecas, $i, 'qtde_pendente');

				if ($peca_estoque == "t") {
					$insert_movimentacao = "INSERT INTO tbl_estoque_posto_movimento (
														fabrica,
														posto,
														peca,
														qtde_entrada,
														os,
														admin,
														obs
										) VALUES (
											$login_fabrica,
											$id_posto,
											$id_peca,
											$qtde_peca,
											$os,
											$login_admin,
											'OS com garantia recusada'
					)";
					$res_insert_movimentacao = pg_query($con, $insert_movimentacao);
					if(strlen(pg_last_error()) > 0) {
			            		$msg_erro = "Erro ao inserir peça no estoque do Posto.";
			        	}

			        	$update_estoque = "UPDATE tbl_estoque_posto SET
									qtde = qtde + $qtde_peca
									WHERE fabrica = $login_fabrica
									AND posto = $id_posto
									AND peca = $id_peca";
					$res_update_estoque = pg_query($con, $update_estoque);

					if(strlen(pg_last_error()) > 0) {
		            			$msg_erro = "Erro ao inserir peça no estoque do Posto.";
		        		}
				}

		        	$update_servico = "UPDATE tbl_os_item set servico_realizado = $id_servico WHERE peca = $id_peca AND os_item = $os_item";
		        	$res_update_servico = pg_query($con, $update_servico);

		        	if(strlen(pg_last_error()) > 0) {
		            		$msg_erro = "Erro ao atualizar o serviço da peça.";
		        	}


				if (!empty($pedido_item)) {
					$update_cancela_pedido = "
                    			        UPDATE tbl_pedido_item SET
		                                    qtde_cancelada = $qtde_pendente
                    			        WHERE pedido_item = $pedido_item;

		                                SELECT fn_atualiza_status_pedido({$login_fabrica}, $pedido);
                        			";
					$res_update_cancela_pedido = pg_query($con, $update_cancela_pedido);

					if(strlen(pg_last_error()) > 0) {
                                            	$msg_erro = "Erro ao cancelar pedido da peça.";
                                        }
				}
			}
		}

		if ($update_laudo === false) {
			$sql = "INSERT INTO
					tbl_laudo_tecnico_os (
						titulo,
						os,
						observacao,
						fabrica
					)VALUES(
						'LAUDO ORDEM SERVIÇO: {$os}',
						{$dados['numero_os']},
						'{$dados_insert}',
						{$login_fabrica}
					)";				
		} else {
			$sql = "
				UPDATE tbl_laudo_tecnico_os SET
					observacao = '{$dados_insert}'
				WHERE laudo_tecnico_os = {$laudo_tecnico_os}
			";
		}
		$res = pg_query($con, $sql);

		if(strlen(pg_last_error()) > 0) {
            $msg_erro = "Erro ao gravar laudo";
        }

		if ($update_laudo === false) {
			$update = "UPDATE tbl_auditoria_os SET
						reprovada = current_timestamp,
						admin         = {$login_admin},
						justificativa = 'Garantia recusada'
					WHERE os = {$os}
					AND (liberada IS NULL AND cancelada IS NULL)";
			$res_update = pg_query($con, $update);

			if(strlen(pg_last_error()) > 0) {
				$msg_erro = "Erro ao reprovar a OS";
			}

			$mensagem = "A garantia da OS ".$os." foi recusada pela fábrica, o produto deve ser devolvido juntamente com o laudo para o consumidor";

			$insert = "INSERT INTO
							tbl_comunicado(
								mensagem,
								tipo,
								fabrica,
								obrigatorio_site,
								descricao,
								posto,
								ativo
							)VALUES(
								'{$mensagem}',
								'Comunicado',
								{$login_fabrica},
								't',
								'OS RECUSADA PELA FÁBRICA',
								{$dados['posto']},
								't'
							)";
			$res_insert = pg_query($con, $insert);

			if(strlen(pg_last_error()) > 0) {
				$msg_erro = "Erro ao gravar comunicado";
			}

			$sql_atendimento = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND descricao = 'Garantia Negada' ";
			$res_atendimento = pg_query($con, $sql_atendimento);

			if(strlen(pg_last_error()) > 0) {
				$msg_erro = "Erro ao pesquisar tipo de atendimento";
			}else{
				if(pg_num_rows($res_atendimento) > 0){
					$tipo_atendimento = pg_fetch_result($res_atendimento, 0, 'tipo_atendimento');

					
					$sql_update = "UPDATE tbl_os SET laudo_tecnico_numerico = tipo_atendimento, tipo_atendimento = {$tipo_atendimento} WHERE fabrica = {$login_fabrica} AND os = {$os}";
					$res_update = pg_query($con, $sql_update);

					if(strlen(pg_last_error()) > 0) {
						$msg_erro = "Erro ao atualizar o tipo de atendimento";
					}
				}
			}
			
			$sqlUpdateOs = "UPDATE tbl_os SET data_fechamento = now() , finalizada = now() , data_conserto = now() WHERE os = {$os}";
        	$resUpdateOs = pg_query($con, $sqlUpdateOs);

        	if (strlen(pg_last_error()) > 0){
        		$msg_erro = "Erro ao finalizar Ordem de Serviço";
        	}
		}

        if(strlen(trim($msg_erro)) == 0){
        	pg_query($con, "COMMIT");

        	$dados_inseridos = true;
        	$msg_sucesso = "Laudo gravado com sucesso";

        	foreach ($dados as $key => $value) {
				if($key != "pecas"){
					$dados[$key] = utf8_decode($value);
				}
			}
			foreach ($dados["pecas"] as $key1 => $value1) {
				$dados["pecas"][$key1] = utf8_decode($value1);
			}
			if (count($_FILES) > 0) {
				unset($amazonTC, $image, $types);
				$amazonTC = new TDocs($con, $login_fabrica,"laudo_anexo");
				$types = array("png", "jpg", "jpeg");
				foreach ($_FILES as $key => $imagem) {
					if ((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)) {
						$type  = strtolower(preg_replace("/.+\//", "", $imagem["type"]));
						
						if (!in_array($type, $types)) {
							$msg_erro .= "Formato inv&aacute;lido, s&atilde;o aceitos os seguintes formatos: png, jpg, jpeg<br />";
							break;
						} else {
							$imagem['name'] = "laudo_anexo_".$os."_".$auditoria."_{$login_fabrica}_{$key}.$type";
							$subir_anexo = $amazonTC->uploadFileS3($imagem, $os, false, "laudo_os","$os");

							if (!$subir_anexo) {
								$msg_erro .= "Erro ao gravar o anexo<br>";
							}
						}
					}
				}
			}
		}else{
        	pg_query($con, "ROLLBACK");
        }
	}
} else {
	$sql = "SELECT observacao FROM tbl_laudo_tecnico_os WHERE os = $os AND fabrica = $login_fabrica AND JSON_FIELD('auditoria', observacao) = '$auditoria'";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$dados = pg_fetch_assoc($res);
		$dados = json_decode($dados["observacao"],true);

		foreach ($dados as $key => $value) {
			if($key != "pecas"){
				$dados[$key] = utf8_decode($value);
			}
		}
		if($dados["auditoria"] != $auditoria){
			$msg_erro = "Laudo não cadastrado para essa auditoria";
		}
	}
}

if($dados_inseridos == true){
	$readonly = "readonly";
	$btn_display = "style='display:none'";
}

if ($areaAdmin === true){
	$coluna_defeito_constatado = ",tbl_defeito_constatado.descricao AS defeito_constatado_descricao ";
}else{
	$coluna_defeito_constatado = ",tbl_causa_defeito.descricao AS defeito_constatado_descricao ";
}

$sql = "SELECT 	TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
				tbl_os.sua_os AS numero_os,
				tbl_os.nota_fiscal AS nota_fiscal,
				TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')  AS data_nf,
				tbl_produto.referencia AS referencia_produto,
				tbl_produto.descricao AS descricao_produto,
				tbl_os_produto.serie AS numero_serie,
				tbl_os.codigo_fabricacao AS codigo_fabricacao,
				tbl_os.posto AS posto,
				tbl_os.consumidor_nome,
				tbl_os.consumidor_fone,
				tbl_os.consumidor_cidade,
				tbl_os.consumidor_estado,
				tbl_os.consumidor_cpf,
				tbl_os.consumidor_endereco,
				tbl_os.consumidor_numero,
				tbl_os.consumidor_bairro,
				tbl_os.consumidor_complemento,
				tbl_os.consumidor_email,
				tbl_os.consumidor_celular,
				tbl_os.consumidor_cep,
				tbl_posto.nome as posto_nome,
				tbl_os.defeito_reclamado_descricao AS defeito_reclamado_descricao,
				tbl_defeito_reclamado.descricao AS defeito_reclamado_descricao_original					
				{$coluna_defeito_constatado}
		FROM tbl_os
		JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
		JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
		LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		LEFT JOIN tbl_defeito_reclamado ON tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
		LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_produto.defeito_constatado
		LEFT JOIN tbl_causa_defeito ON tbl_causa_defeito.causa_defeito = tbl_os_produto.causa_defeito AND tbl_causa_defeito.fabrica = {$login_fabrica}
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
		WHERE tbl_os.os = $os";
$res = pg_query($con, $sql);
if(pg_num_rows($res) > 0){
 	$result = pg_fetch_all($res);
}

?>
<!DOCTYPE html />
<html>
<head>
    <meta http-equiv=pragma content=no-cache />
    <link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/glyphicon.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="bootstrap/js/bootstrap.js" ></script>
    <script src="plugins/shadowbox_lupa/lupa.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js"></script>
    <script src="plugins/jquery.mask.js"></script>
    <script src="../plugins/shadowbox/shadowbox.js" type="text/javascript" ></script>
	<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all" >
    <script type="text/javascript">

    	function confirma(){
			$("#btn_acao").val("Enviar");
			$("#form_laudo").submit();
    	}

    	$(function() {
			Shadowbox.init();
		});

    </script>

   <style type="text/css">
   		.float-img {
			float: left !important;
		}
		@media print {

			@page {
                size: A4;
                margin: 5mm;
            }

            .float-img {
				float: left !important;
				width: 40% !important;
			}
			.floatr-img {
				float: right !important;
				width: 40% !important;
				margin-right: 5% !important;
			}

           .table {
			    border:1px solid #000 !important;
			    width: 100% !important;
			    border-collapse: collapse;
			}

			.table td, .table th {
				padding: 5px;
			}

			.table td.teste, 
			.titulo_tabela > th, 
			.titulo {
				border-right: 1px solid #000 !important;
			}

			.titulo_tabela {
				background-color: #596d9b !important;
        		-webkit-print-color-adjust: exact; 
        		color: white !important;
        		font: bold 16px "Arial";
			}

			.tr_titulo {
				background-color: #dddddd !important;
				-webkit-print-color-adjust: exact;
			}

			.titulos_perguntas {
				margin-top: 7px;
				margin-bottom: 7px;
				width: 100% !important;
				text-align: center;
			}

			textarea {
				width: 96% !important;
			}
		}

    	.class_linha1{
		    margin: 2px 0;
		    border: 5px;
		    border-top: 3px solid #ED1B2F;
		    border-bottom: 1px solid #ED1B2F;
    	}
    	.class_linha2{
		    margin: 2px 0;
		    border: 5px;
		    border-top: 3px solid #7c7c7c;
		    border-bottom: 1px solid #7c7c7c;
    	}
    	.error{
    		border-color: #B94A48 !important;
    	}
    	.error_td{
			color: #B94A48 !important;
    	}
    	.titulos_perguntas{
    	    /*background-color: rgba(0, 0, 0, 0.36);*/
		    width: 205px;
		    /*border-radius: 4px;*/
		    /*border: 10;*/
		    /*border: 3px solid;*/
		    /*padding-left: 15px;*/
		    font-weight: bold;
    	}
    	.table{
    		margin-bottom: 0px !important;
    	}

    	.input_text{
    		height: 30px !important;
    		/*width: 250px !important;*/
    		width: 100% !important;
    	}
    	hr{
    		max-width: 1000px !important;
    	}
    	.txt_cliente{
    		font-size: 11px;
    		font-weight: bold;
    		padding-bottom: 0px;
    		margin-bottom: 0px;
    	}
    	.titulo{
    		background: #dddddd;
    	}
    </style>
</head>
<body>
	<?php
	if (isset($_GET["iframe"])) {
		$overflow_y = "none";
		$iframe = "&iframe=true";
	} else {
		$overflow_y = "auto";
	}
	?>

	<div id="container_lupa" style="overflow-y: <?=$overflow_y?>;" >
		<?php
		if (strlen(trim($os)) == 0) {
		?>
			<div class="alert alert-error" >Ordem de Serviço <?=$os?> não encontrada</div>
		<?php
		}else {
			if (strlen(trim($os)) > 0) {
				if(strlen(trim($msg_erro)) > 0){
				?>
				<div class="alert alert-error" ><?=$msg_erro?></div>
				<?php
				}
				if(strlen(trim($msg_sucesso)) > 0){
					if ($update_laudo === true) {
					?>
						<script>

						alert("<?=$msg_sucesso?>");
						window.location = 'consulta_laudo_anauger.php?os=<?=$os?>&auditoria=<?=$auditoria?>';

						</script>
					<?php
					} else {
					?>
						<div class="alert alert-success" ><?=$msg_sucesso?></div>
					<?php
					}
				}
			?>

			<form enctype="multipart/form-data" name='frm_relatorio' id="form_laudo" METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline' >
				<input type="hidden" name="os" value="<?=$os?>">
				<input type="hidden" name="data_abertura" value="<?=$result[0]['data_abertura']?>">
				<input type="hidden" name="numero_os" value="<?=$result[0]['numero_os']?>">
				<input type="hidden" name="nota_fiscal" value="<?=$result[0]['nota_fiscal']?>">
				<input type="hidden" name="data_nf" value="<?=$result[0]['data_nf']?>">
				<input type="hidden" name="referencia_produto" value="<?=$result[0]['referencia_produto']?>">
				<input type="hidden" name="descricao_produto" value="<?=$result[0]['descricao_produto']?>">
				<input type="hidden" name="numero_serie" value="<?=$result[0]['numero_serie']?>">
				<input type="hidden" name="codigo_fabricacao" value="<?=$result[0]['codigo_fabricacao']?>">
				<input type="hidden" name="descricao_defeito_reclamado" value="<?=$descricao_defeito_reclamado?>">
				<input type="hidden" name="defeito_constatado_descricao" value="<?=$result[0]['defeito_constatado_descricao']?>">
				<input type="hidden" name="auditoria" value="<?=$auditoria?>">
				<input type="hidden" name="posto" value="<?=$result[0]['posto']?>">
				<input type="hidden" name="defeito_constatado" value="<?=$defeito_constatado?>">
				<div class="row-fluid">
					<img src="logos/logo_anauger.png" class="img-responsive" style="height: 100px">
				</div>
				<hr class='class_linha1'>
				<hr class='class_linha2'>

				<table id="resultado_os_atendimento" class='table table-large' >
					<thead>
						<tr class='titulo_tabela'>
							<th colspan="3" >LAUDO DE GARANTIA NEGADA</th>
						</tr>
					</thead>
					<tdboy>
						<tr class="tr_titulo">
							<td style="" class='tal titulo titulo'><div class='titulos_perguntas'>EMPRESA SOLICITANTE</div></td>
						</tr>
						<?php if (strlen($result[0]['posto_nome']) > 0) {?>
							<tr>
								<td class='teste'>
									<table class='table table-bordered table-large'>
										<tbody>
											<tr>
												<td style="" class='tal'><?=$result[0]['posto_nome']?></td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td> 
												<p class="txt_cliente">DATA DE ENTRADA NA AT</p>
												<?=$result[0]['data_abertura']?>
											</td>
											<td> 
												<p class="txt_cliente">ORDERM DE SERVIÇO</p>
												<?=$result[0]['numero_os']?>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr class="tr_titulo">
							<td class='tal teste titulo'><div class='titulos_perguntas'>DADOS DO CLIENTE</div></td>
						</tr>
						<?php if (strlen($result[0]['consumidor_nome']) > 0) {?>
							<tr>
								<td class='teste'>
									<table class='table table-bordered table-large'>
										<tbody>
											<tr>
												<td class='tal teste'>
													<p class="txt_cliente">NOME</p>
													<?=$result[0]['consumidor_nome']?>
												</td>
												<td class='tal teste'>
													<p class="txt_cliente">TELEFONE</p>
													<?=$result[0]['consumidor_fone']?>
												</td>
												<td class='tal teste'>
													<p class="txt_cliente">CELULAR</p>
													<?=$result[0]['consumidor_celular']?>
												</td>
												<td class='tal teste'>
													<p class="txt_cliente">CPF CONSUMIDOR</p>
													<?=$result[0]['consumidor_cpf']?>
												</td>
											</tr>
										</tbody>
									</table><br />
									<table class='table table-bordered table-large'>
										<tbody>
											<tr>
												<td class='tal teste' colspan="100%">
													<p class="txt_cliente">E-MAIL</p>
													<?=$result[0]['consumidor_email']?>
												</td>
											</tr>
										</tbody>
									</table><br />
									<table class='table table-bordered table-large'>
										<tbody>
											<tr>
												<td class='tal teste'>
													<p class="txt_cliente">CEP</p>
													<?=$result[0]['consumidor_cep']?>
												</td>
												<td class='tal teste'>
													<p class="txt_cliente">ENDEREÇO</p>
													<?=$result[0]['consumidor_endereco']?>
												</td>
												<td class='tal teste'>
													<p class="txt_cliente">NÚMERO</p>
													<?=$result[0]['consumidor_numero']?>
												</td>
												<td class='tal teste'>
													<p class="txt_cliente">COMPLEMENTO</p>
													<?=$result[0]['consumidor_complemento']?>
												</td>
											</tr>
										</tbody>
									</table><br />
									<table class='table table-bordered table-large'>
										<tbody>
											<tr>
												<td class='tal teste'>
													<p class="txt_cliente">BAIRRO</p>
													<?=$result[0]['consumidor_bairro']?>
												</td>
												<td class='tal teste'>
													<p class="txt_cliente">CIDADE</p>
													<?=$result[0]['consumidor_cidade']?>
												</td>
												<td class='tal teste'>
													<p class="txt_cliente">ESTADO</p>
													<?=$result[0]['consumidor_estado']?>
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td class="tal teste"> 
												<p class="txt_cliente">NF DE COMPRA</p>
												<?=$result[0]['nota_fiscal']?>
											</td>
											<td class="tal teste"> 
												<p class="txt_cliente">DATA DA EMISSÃO</p>
												<?=$result[0]['data_nf']?>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr class="tr_titulo">
							<td class='tal teste titulo'><div class='titulos_perguntas'>DADOS DO EQUIPAMENTO</div></td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td class="tal teste"> 
												<p class="txt_cliente">MODELO</p>
												<?=$result[0]['referencia_produto']?> - <?=$result[0]['descricao_produto']?>
											</td>
											<td class="tal teste"> 
												<p class="txt_cliente">Lote / Série</p>
												<?php
													if (!empty($result[0]['codigo_fabricacao'])) {

														echo $result[0]['codigo_fabricacao'];

													} else {

														echo $result[0]['numero_serie'];

													}
												?>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>		
						<tr class="tr_titulo">
							<td class='tal teste titulo'><div class='titulos_perguntas'>DEFEITO DIAGNOSTICADO</div></td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td class="tal teste"> <p class="txt_cliente">PELO CLIENTE: </p>
											<?=trim($result[0]['defeito_reclamado_descricao']) != ""?  $result[0]['defeito_reclamado_descricao'] : $result[0]['defeito_reclamado_descricao_original'] ?>
											</td>

											<?php if ($login_fabrica == 177) { ?>
												<td class="tal teste"> <p class="txt_cliente">PELA FÁBRICA: </p>
										
											<?php } else { ?> 
												<td class="tal teste"> <p class="txt_cliente">PELA AUTORIZADA: </p>
											<?php } ?>

											<?=$result[0]['defeito_constatado_descricao']?></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						
						<tr class="tr_titulo">
							<td class='tal teste titulo'><div class='titulos_perguntas'>CONCLUSÃO DO LAUDO</div></td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
												<td class="tal teste">
													<strong>Motivo da Recusa: </strong>
													<?php

													if($_GET["print"] == "true") {

														$sql = "SELECT motivo FROM tbl_motivo_recusa WHERE motivo_recusa = ". $dados['motivo_fabricante'];
														$resMotivo = pg_query($con, $sql);

														echo pg_fetch_result($resMotivo, 0, 'motivo');

													} else {

														$sql = "SELECT motivo, motivo_recusa FROM tbl_motivo_recusa 
																WHERE fabrica = $login_fabrica 
																AND conclusao IS NULL
																AND liberado IS TRUE";												
														$resSqlMotivo = pg_query($con,$sql);												
														?>
														
														<select <?=$readonly?> name="motivo_fabricante" class="motivo_fabrica">
															<option value=""></option>
															<?php
															while ($dadosMotivo = pg_fetch_object($resSqlMotivo)) {

																$selected = ($dados['motivo_fabricante'] == $dadosMotivo->motivo_recusa) ? "selected" : "";

																?>
																<option <?= $selected ?> value="<?= $dadosMotivo->motivo_recusa ?>">
																	<?= $dadosMotivo->motivo ?>
																</option>
																<?php
															}
															?>	
														</select>
													<?php
													} ?>
												</td>
									<?php if ($login_fabrica != 177) { ?>
										<td class="tal teste">
											<strong>Conclusão do Fabricante: </strong>
											<?php

												if($_GET['print'] == 'true'){
													$sql = "SELECT conclusao FROM tbl_motivo_recusa WHERE motivo_recusa = ".$dados['conclusao_fabricante'];
													$resConclusao = pg_query($con, $sql);

													echo pg_fetch_result($resConclusao, 0, 'conclusao');

												} else {

													$sql = "SELECT conclusao, motivo_recusa FROM tbl_motivo_recusa
															WHERE fabrica = $login_fabrica
															AND conclusao IS NOT NULL
															AND liberado IS TRUE";												
													$resSqlConclusao = pg_query($con,$sql);												
													?>
													
													<select <?=$readonly?> name="conclusao_fabricante" class="conclusao_fabrica">
														<option value=""></option>
														<?php
														while ($dadosConclusao = pg_fetch_object($resSqlConclusao)) {

															$selected = ($dados['conclusao_fabricante'] == $dadosConclusao->motivo_recusa) ? "selected" : "";

															?>
															<option <?= $selected ?> value="<?= $dadosConclusao->motivo_recusa ?>">
																<?= $dadosConclusao->conclusao ?>
															</option>
															<?php
														}
														?>	
													</select>
												<?php
												}
												?>
											</td>
										<? } ?>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>

						<tr class="tr_titulo">
							<td class='tal teste titulo'><div class='titulos_perguntas'>OBSERVAÇÃO</div></td>
						</tr>
						<tr>
							<td class="tal teste">
								<tr>
									<td colspan="4">
										<?php
										if ($_GET['print'] == 'true') { ?>
											<?=$dados['observacao']?>
										<?php
										} else { ?>
											<textarea <?=$readonly?> class='' style="width: 100%;resize: none;overflow: hidden;" rows="10" name='observacao'><?=$dados['observacao']?></textarea>
										<?php
										}
										?>
									</td>
								</tr>
							</td>
						</tr>

						<?php if (strlen($os) > 0 && strlen($auditoria) > 0 AND 1==2) { ?>
							<tr class="tr_titulo">
								<td class='tal teste titulo'><div class='titulos_perguntas'>ANEXOS</div></td>
							</tr>
							<tr>
								<td class='teste'>
									<table class='table table-bordered table-large'>
										<tbody>
											<tr>
												<td>
													<label>Anexo 1</label>
													<?php
														unset($amazonTC, $anexos, $types);
														$amazonTC = new TDocs($con, $login_fabrica,"laudo_anexo");

														$anexos = array();
														$anexos["1"]["nome"] = "laudo_anexo_".$os."_".$auditoria."_{$login_fabrica}_anexo_laudo_1";
														$anexos["1"]["url"] = $amazonTC->getDocumentsByName($anexos["1"]["nome"])->url;

														if (strlen($anexos["1"]["url"]) > 0) { ?>
															<br> <br>
															<img id="imagem_anexo_1" src="<?=$anexos['1']['url']?>" style="max-height: 150px !important; max-width: 150px !important;" border="0">
															<br> <br>
													<?php } else { ?>
															<br>
													<?php }
													if ($_GET['print'] != 'true') { ?>
														<input type="file" name="anexo_laudo_1">
													<?php
													}
													?>
												</td>
											</tr>
											<tr>
												<td>
													<label>Anexo 2</label>
													<?php
														$anexos["2"]["nome"] = "laudo_anexo_".$os."_".$auditoria."_{$login_fabrica}_anexo_laudo_2";
														$anexos["2"]["url"] = $amazonTC->getDocumentsByName($anexos["2"]["nome"])->url;

														if (strlen($anexos["2"]["url"]) > 0) { ?>
															<br> <br>
															<img id="imagem_anexo_2" src="<?=$anexos['2']['url']?>" style="max-height: 150px !important; max-width: 150px !important;" border="0">
															<br> <br>
													<?php } else { ?>
															<br>
													<?php }

													if ($_GET['print'] != 'true') { ?>
														<input type="file" name="anexo_laudo_2">
													<?php
													}
													?>
												</td>
											</tr>
											<tr>
												<td>
													<label>Anexo 3</label>
													<?php
														$anexos["3"]["nome"] = "laudo_anexo_".$os."_".$auditoria."_{$login_fabrica}_anexo_laudo_3";
														$anexos["3"]["url"] = $amazonTC->getDocumentsByName($anexos["3"]["nome"])->url;

														if (strlen($anexos["3"]["url"]) > 0) { ?>
															<br> <br>
															<img id="imagem_anexo_3" src="<?=$anexos['3']['url']?>"  style="max-height: 150px !important; max-width: 150px !important;" border="0">
															<br> <br>
													<?php } else { ?>
															<br>
													<?php }

													if ($_GET['print'] != 'true') { ?>
														<input type="file" name="anexo_laudo_3">
													<?php
													}
													?>
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<td class='teste'>
								<p>Indústria de Motores Anauger S.A. - R. Prefeito José Carlos, 2555 - Itupeva - São Paulo/SP - (11) 4591-1661 - www.anauger.com.br</p>
							</td>
						</tr>
					</tdboy>
				</table>
				<input type="hidden" id="btn_acao" name="btn_acao" value="">
			</form>
			<?php
				if($_GET['print'] != 'true'){
					$width = "style='width:850px !important'";
					echo "<div $width >";
	                $tempUniqueId = $os;
	                $boxUploader = array(
	                    "div_id" => "div_anexos",
	                    "prepend" => $anexo_prepend,
	                    "context" => "os",
	                    "unique_id" => $tempUniqueId,
	                    "hash_temp" => $anexoNoHash,
	                    "bootstrap" => false,
	                    "hidden_button" => false
	                );
	                include "../box_uploader.php";
	                echo "</div>";
				}else{ 
					$tdocs = new TdocsMirror;

					$sqlTdocs = "SELECT tdocs_id, obs FROM tbl_tdocs WHERE fabrica = {$login_fabrica} AND referencia_id = {$os} AND referencia = 'os'";
					$resTdocs = pg_query($con, $sqlTdocs);
				?>
					<table class='table table-bordered table-large' >
						<thead>
							<tr class='tr_titulo'>
								<td colspan="4" class='teste titulo'><div class="titulos_perguntas">ANEXOS</div></td>
							</tr>
						</thead>
						<tbody>
							<tr>
                         		<td class="tal teste">
									<?php

										if (pg_num_rows($resTdocs) > 0) {
											for ($y=1; $y < pg_num_rows($resTdocs); $y+=2) { 
												$tdocs_id 	= pg_fetch_result($resTdocs, $y, "tdocs_id");
												
												$contadorAnterior = $y - 1;

												$obs 		= pg_fetch_result($resTdocs, $y, "obs");
												$obs 		= json_decode($obs, true);
												$titulo 	= $obs[0]["typeId"];
												$titulo = strtoupper($titulo);
												$titulo = str_replace("_", " ", $titulo);
												$link = $tdocs->get($tdocs_id);

												$tdocs_id2 =  pg_fetch_result($resTdocs, $contadorAnterior, "tdocs_id");
												$obs2 		= pg_fetch_result($resTdocs, $contadorAnterior, "obs");
												$obs2 = json_decode($obs2, true);
												$tituloImagem2 	= $obs2[0]["typeId"];
												$tituloImagem2 = strtoupper($tituloImagem2);
												$tituloImagem2 = str_replace("_", " ", $tituloImagem2);
												$linkImagem2 = $tdocs->get($tdocs_id2);
									?>
					
												<tr>
													<td style="width: 50%; padding-left: 5%">
														<?=$titulo?><br/><br/>
														<img class="float-img" src="<?=$link['link']?>" style="width:100%; ">
													</td>
													<td style="width: 50%; padding-left: 5%">
														<?=$tituloImagem2?><br/><br/>
														<img class="float-img" src="<?=$linkImagem2['link']?>" style="width:100%;">
													</td>
												</tr>

										<?php
											}
										}
									?>	
                         		</td>
                     		</tr>	
						</tbody>
					</table>
					
				<?php } ?>

				<?php if ($login_fabrica == 177) { ?>
					<table style="text-align: right; padding: 70px; text-indent: 50px;width: 100%;page-break-inside: avoid;">
						<tr>
							<td>
								Depto – Assistência técnica <br>
								Industria de Motores Anauger
							</td>
						</tr>
					</table>
				<? } ?>

				<?php
				if($_GET['print'] != 'true'){
				?>
					<br/>
					<div class="tac">
						<button class='btn btn-info' name='btn_acao' <?=$btn_display?> onclick="confirma();">Gravar</button>
						<button type='button' onclick="window.parent.retorno_laudo('<?=$dados_inseridos?>','<?=$auditoria?>','<?=$os?>')" class="btn">Fechar</button>
					</div>
				<?php
				}
			}
			?>
		<?php
		}
		?>
	</div>
	<script type="text/javascript">

		<?php
		if($_GET['print'] == 'true'){
			?>
			window.print();
			<?php
		}
		?>
	</script>
</body>
</html>
