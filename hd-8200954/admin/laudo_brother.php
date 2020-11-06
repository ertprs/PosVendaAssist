<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
 
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($areaAdmin === true) {
	include "autentica_admin.php";
} else {
	include "autentica_usuario.php";
}
include "funcoes.php";
	$os = $_REQUEST["os"];
	$auditoria = $_REQUEST["auditoria"];
	$dados_inseridos = false;

	if(!in_array($login_fabrica, [167, 203])){
		echo "Acesso não autorizado";exit;
	}

	if($_POST['btn_acao'] ==  "Enviar") {
		$dados = $_POST;
		unset($dados['btn_acao']);

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
				echo $key;
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

				$sql_atendimento = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND descricao = 'Garantia Recusada' ";
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
		$sql = "SELECT data,observacao FROM tbl_laudo_tecnico_os WHERE os = $os AND fabrica = $login_fabrica AND JSON_FIELD('auditoria', observacao) = '$auditoria'";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res) > 0){
			$dados = pg_fetch_assoc($res);
			$data_laudo = $dados["data"];

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

	$sql = "SELECT 	TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					tbl_os.sua_os AS numero_os,
					tbl_os.nota_fiscal AS nota_fiscal,
					TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')  AS data_nf,
					tbl_produto.referencia AS referencia_produto,
					tbl_produto.descricao AS descricao_produto,
					tbl_produto.voltagem AS voltagem_produto,
					tbl_os_produto.serie AS numero_serie,
					tbl_peca.referencia AS referencia_peca,
					tbl_peca.descricao AS descricao_peca,
					tbl_os.posto AS posto,
					tbl_os.acessorios AS acessorios,
					tbl_os.revenda_nome AS revenda_nome,
					tbl_os.revenda_cnpj AS revenda_cnpj,
					tbl_os.aparencia_produto AS aparencia_produto,
					tbl_os.consumidor_nome AS consumidor_nome,
					tbl_os.consumidor_fone AS consumidor_fone,
					tbl_os.consumidor_cpf AS consumidor_cpf,
					tbl_os.consumidor_celular AS consumidor_celular,
					tbl_os.consumidor_email AS consumidor_email,
					tbl_posto.nome as posto_nome,
					tbl_posto.estado as posto_estado,
					tbl_posto.email as posto_email,
					tbl_posto.fone as posto_telefone,
					tbl_posto.cnpj as posto_cnpj,
					tbl_os.defeito_reclamado_descricao AS defeito_reclamado_descricao,
					tbl_defeito_constatado.descricao AS defeito_constatado_descricao
			FROM tbl_os
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
			JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
			LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			LEFT JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
			JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_produto.defeito_constatado
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE tbl_os.os = $os";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$result = pg_fetch_all($res);

		$sql_defeito = "SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado = {$result[0]['defeito_reclamado_descricao']} AND fabrica = $login_fabrica";
		$res_defeito = pg_query($con, $sql_defeito);

		if(pg_num_rows($res_defeito) > 0){
			$descricao_defeito_reclamado = pg_fetch_result($res_defeito, 0, 'descricao');
		}
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

    <script type="text/javascript">
		$(function(){
			$("input[name=data_analise]").mask("00/00/0000")
			$("input[name=data_analise]").datepicker()

		})
    	function confirma(){
			$("#btn_acao").val("Enviar");
			$("#form_laudo").submit();
    	}
    </script>

   <style type="text/css">
    	.class_linha1{
		    margin: 2px 0;
		    border: 5px;
		    border-top: 3px solid #215968;
		    border-bottom: 1px solid #215968;
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
    	    background-color: rgba(0, 0, 0, 0.36);
		    width: 205px;
		    border-radius: 4px;
		    border: 10;
		    border: 3px solid;
		    padding-left: 15px;
    	}
    	.table{
    		margin-bottom: 0px !important;
    	}
    	.teste {
    		border-top: 0px !important;
    	}
    	.input_text{
    		height: 30px !important;
    		/*width: 250px !important;*/
    		width: 100% !important;
    	}
    	hr{
    		max-width: 1000px !important;
    	}
    	.titulo_tabela2{
    		border-bottom: solid 4px #111; 
    		border-top: solid 4px #111; 
    	}
    	.titulo_tabela2 td{
    		font-size: 25px !important;
    		font-weight: bold;
    	}
    	.titulos_perguntas2{
    	    background-color: #00529c;
		    padding: 5px;
		    color: #fff;
		    font-weight: bold;
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
						window.location = 'consulta_laudo_brother.php?os=<?=$os?>&auditoria=<?=$auditoria?>';

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
				<input type="hidden" name="descricao_defeito_reclamado" value="<?=$descricao_defeito_reclamado?>">
				<input type="hidden" name="defeito_constatado_descricao" value="<?=$result[0]['defeito_constatado_descricao']?>">
				<input type="hidden" name="auditoria" value="<?=$auditoria?>">
				<input type="hidden" name="posto" value="<?=$result[0]['posto']?>">

				<?php if ($login_fabrica != 203) {?>
				<div class="row-fluid">
					<img src="../logos/logo_laudo_brother.jpg">
				</div>
				<hr class='class_linha1'>
				<hr class='class_linha2'>
				<?php } else {?>
					<table id="resultado_os_atendimento" class='table table-large' >
						<thead>
							<tr>
								<td style="text-align: left;">
									<table style="border: solid 1px #333;width: 50%" id="resultado_os_atendimento" class='table table-large' >
										<thead>
											<tr>
												<td style="text-align: left;">
													<h4>INFORMAÇÕES DO POSTO</h4>
													RAZÃO SOCIAL: <?=$result[0]['posto_nome']?><br>
													CNPJ: <?=$result[0]['posto_cnpj']?> - TELEFONE: <?=$result[0]['posto_telefone']?><br>
													E-MAIL: <?=$result[0]['posto_email']?>
													
												</td>
											</tr>
										</thead>
									</table>
								</td>
								<td style="text-align: right;"><img width="200" src="../logos/logo_brother.jpg"></td>
							</tr>
						</thead>
					</table>					
				<?php }?>

				<?php if ($login_fabrica == 203) {?>

				<table id="resultado_os_atendimento" class='table table-large' >
					<tbody>
						<tr>
							<td class='tal teste'><div class='titulos_perguntas2' style="width: auto;">DADOS DO CONSUMIDOR / USUÁRIO FINAL / DESTINADO A</div></td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td class='tal teste'><b>NOME:</b> <?=$result[0]['consumidor_nome']?></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td class='tal teste'><b>CNPJ/CPF:</b> <?=$result[0]['consumidor_cpf']?></td>
											<td class='tal teste'><b>E-MAIL:</b> <?=$result[0]['consumidor_email']?></td>
											<td class='tal teste'><b>TELEFONE:</b> <?=$result[0]['consumidor_fone']?> <?=$result[0]['consumidor_celular']?></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td class='tal teste'> <b>ORDEM DE SERVIÇO:</b> <?=$result[0]['numero_os']?></td>
											<td class='tal teste'> <b>DATA DE ENTRADA NA AT:</b> <?=$result[0]['data_abertura']?></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td class='tal teste'> <b>EQUIPAMENTO:</b> <?=$result[0]['referencia_produto']?> - <?=$result[0]['descricao_produto']?></td>
											<td class='tal teste'> <b>NÚMERO DE SÉRIE:</b> <?=$result[0]['numero_serie']?></td>
											<td class='tal teste'> <b>VOLTAGEM:</b> <?=$result[0]['voltagem_produto']?></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td colspan="2"> <b>NF DE COMPRA:</b> <?=$result[0]['nota_fiscal']?></td>
											<td> <b>DATA DA EMISSÃO:</b> <?=$result[0]['data_nf']?></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td colspan="2"> <b>REVENDA / LOCAL DA COMPRA:</b> <?=$result[0]['revenda_nome']?></td>
											<td> <b>CNPJ REVENDA:</b> <?=$result[0]['revenda_cnpj']?></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td colspan="3"> <b>ACESSÓRIOS QUE ACOMPANHAM O EQPTO:</b> <?=$result[0]['acessorios']?></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td colspan="3"> <b>CONDIÇÕES FÍSICAS / VISUAIS DO EQPTO:</b> <?=$result[0]['aparencia_produto']?></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td colspan="3"> <b>DEFEITO RECLAMADO PELO CONSUMIDOR:</b> <?=$descricao_defeito_reclamado?></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr class='titulo_tabela2'>
							<td colspan="3" class="tac" >LAUDO TÉCNICO -  ORDEM DE SERVIÇO Nº <?=$result[0]['numero_os']?></td>
						</tr>
						<tr>
							<td class='tal teste'><div style="width: auto;" class='titulos_perguntas2'>ANÁLISE TÉCNICA</div></td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td><b>DATA ANÁLISE:</b> </td>
											<td><input <?=$readonly?> class='input_text' type="text" name="data_analise" value="<?=$dados['data_analise']?>"></td>
											<td><b>TÉCNICO RESPONSÁVEL:</b></td>
											<td ><input <?=$readonly?> class='input_text' type="text" name="tecnico_responsavel" value="<?=$dados['tecnico_responsavel']?>"></td>
										</tr>
										<tr>
											<td><b>TIPO DE LAUDO TÉCNICO:</b></td>
											<td>
												<select class='input_text' <?=$readonly?> name="tipo_laudo">
													<option value="">- Selecione</option>
													<option value="GARANTIA REPROVADA / LAUDO DE ORIENTAÇÃO">GARANTIA REPROVADA / LAUDO DE ORIENTAÇÃO</option>
													<option value="EQPTO. REPARADO">EQPTO. REPARADO</option>
												</select>
											</td>
											<td><b>DEFEITO CONSTATADO PELA AUTORIZADA:</b> </td>
											<td><?=$result[0]['defeito_constatado_descricao']?></td>
										</tr>
										<tr>
											<td><b>DEFEITO CONSTATADO PELO TECNICO:</b></td>
											<td><input <?=$readonly?> class='input_text <?=(in_array("defeito_farcomp", $msg_error["campos"])) ? "error" : ""?>' type="text" name="defeito_constatado_tecnico" value="<?=$dados['defeito_constatado_tecnico']?>"></td>
	
											<td><b>CONTADOR PÁG. / HORAS TRABALHADAS:</b>  </td>
											<td><input <?=$readonly?> class='input_text' type="text" name="contador_horas" value="<?=$dados['contador_horas']?>"></td>
																				</tr>
										<tr>
											<td><b>TESTES REALIZADOS:</b></td>
											<td colspan='3'><input <?=$readonly?> class='input_text' type="text" name="testes_realizados" value="<?=$dados['testes_realizados']?>"></td>

										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<?php
							if(strlen(trim($result[0]['referencia_peca'])) > 0 OR strlen(trim($result[0]['descricao_peca'])) > 0){
						?>
						<tr>
							<td class='tal teste'><div style="width: auto;" class='titulos_perguntas2'>PEÇAS SOLICITADAS</div></td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<?php
										foreach ($result as $key => $value) {
											if(!empty($value["referencia_peca"]) OR !empty($value["descricao_peca"])){
										?>
											<tr>
												<td> <b>CÓDIGO:</b> <?=$result[$key]['referencia_peca']?></td>
												<td> <b>DESCRIÇÃO:</b> <?=$result[$key]['descricao_peca']?></td>
												<input type="hidden" name="pecas[]" value="<?=$result[$key]['referencia_peca']?>|<?=$result[$key]['descricao_peca']?>">
											</tr>
										<?php
											}
										}
										?>
									</tbody>
								</table>
							</td>
						</tr>
						<?php
							}
						?>
						<tr>
							<td class='tal teste'><div  style="width: auto;" class='titulos_perguntas2'>LAUDO CONCLUSIVO / CONSTATADO PELO TÉCNICO CREDENCIADO / AUTORIZADA</div></td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td colspan="3">
												<textarea <?=$readonly?> class='<?=(in_array("conclusao_laudo", $msg_error["campos"])) ? "error" : ""?>' style="width: 100%" rows="4" name='conclusao_laudo'><?=$dados['conclusao_laudo']?></textarea>
											</td>
										</tr> 
										<tr><?php /*
											<td><b>Aprovação do Técnico:</b> </td>
											<td><strong>GARANTIA</strong></td>
											<td> <text class='text-error'>REPROVADA</text> <input onclick="return false;" style="margin-bottom: 7px;" type="checkbox" checked="true" name="aprovacao_tecnico" value='t'></td>*/?>
											<?php
											if ($areaAdmin === true) {
											?>
											<td>
												<strong><b>Motivo da Reprova:</b></strong>
												<select name="motivo_reprova">
													<option value=""></option>
													<option value="CASOS FORTUITOS">CASOS FORTUITOS</option>
													<option value="GARANTIA EXPIRADA">GARANTIA EXPIRADA</option>
													<option value="MANUTENÇÃO POR NÃO AUTORIZADOS">MANUTENÇÃO POR NÃO AUTORIZADOS</option>
													<option value="MAU USO">MAU USO</option>
													<option value="QUEDAS OU IMPACTOS">QUEDAS OU IMPACTOS</option>													
													<option value="SEM DOCUMENTAÇÃO">SEM DOCUMENTAÇÃO</option>
													<option value="SUPRIMENTOS NÃO ORIGINAIS">SUPRIMENTOS NÃO ORIGINAIS</option>
													<option value="QUEDAS OU IMPACTOS">QUEDAS OU IMPACTOS</option>
													<option value="TENSÃO NA REDE ELÉTRICA">TENSÃO NA REDE ELÉTRICA</option>
													<option value="UTILIZAÇÃO EXCEDIDA">UTILIZAÇÃO EXCEDIDA</option>
												</select>
											</td>
											<?php
											}
											?>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<?php if (strlen($os) > 0 && strlen($auditoria) > 0) { ?>
							<tr>
								<td class='tal teste'><div style="width: auto;" class='titulos_perguntas2'>ANEXOS</div></td>
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
													<?php }?>
													<input type="file" name="anexo_laudo_1">
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
													<?php }?>
													<input type="file" name="anexo_laudo_2">
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
													<?php }?>
													<input type="file" name="anexo_laudo_3">
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
						<?php } ?>

						<tr>
							<td class='tal teste'><div  style="width: auto;" class='titulos_perguntas2'>ORIENTAÇÕES AO CONSUMIDOR / OBSERVAÇÕES</div></td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td colspan="3">
												<textarea <?=$readonly?> class='<?=(in_array("conclusao_laudo", $msg_error["campos"])) ? "error" : ""?>' style="width: 100%" rows="4" name='orientacao_consumidor'><?=$dados['orientacao_consumidor']?></textarea>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						
						<tr>
							<td class='teste tac'>
								<p>Compre sempre acessórios e suprimentos originais Brother – Consulte as revendas autorizadas</p>
								<p>Conte sempre conosco!<br>
www.brother.com.br</p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php } else {?>
				<table id="resultado_os_atendimento" class='table table-large' >
					<thead>
						<tr class='titulo_tabela'>
							<th colspan="3" >LAUDO TÉCNICO</th>
						</tr>
					</thead>
					<tdboy>
						<tr>
							<td style="" class='tal'><div class='titulos_perguntas'>EMPRESA SOLICITANTE</div></td>
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
											<td> DATA DE ENTRADA NA AT: <?=$result[0]['data_abertura']?></td>
											<td> ORDEM DE SERVIÇO: <?=$result[0]['numero_os']?></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class='tal teste'><div class='titulos_perguntas'>DADOS DO CLIENTE</div></td>
						</tr>
						<?php if (strlen($result[0]['consumidor_nome']) > 0) { ?>
							<tr>
								<td class='teste'>
									<table class='table table-bordered table-large'>
										<tbody>
											<tr>
												<td class='tal teste'><?=$result[0]['consumidor_nome']?></td>
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
											<td> NF DE COMPRA: <?=$result[0]['nota_fiscal']?></td>
											<td> DATA DA EMISSÃO: <?=$result[0]['data_nf']?></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class='tal teste'><div class='titulos_perguntas'>DADOS DO EQUIPAMENTO</div></td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td> MODELO: <?=$result[0]['referencia_produto']?> - <?=$result[0]['descricao_produto']?></td>
											<td> NS: <?=$result[0]['numero_serie']?></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class='tal teste'><div class='titulos_perguntas'>CONTADORES SUPRIMENTOS</div></td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td>Equipamento</td>
											<td><input <?=$readonly?> class='input_text' type="text" name="equipamento" value="<?=$dados['equipamento']?>"></td>
											<td colspan="4">
												<table class='table table-bordered table-fixed'>
													<tr>
														<td style="border-bottom:1px solid #dddddd;">Cartucho BK</td>
														<td style="border-bottom:1px solid #dddddd;"><input <?=$readonly?> class='input_text' type="text" name="cartucho_bk" value="<?=$dados['cartucho_bk']?>"></td>
													</tr>
													<tr>
														<td>Cartucho M</td>
														<td><input <?=$readonly?> class='input_text' type="text" name="cartucho_m" value="<?=$dados['cartucho_m']?>"></td>
													</tr>
												</table>
											</td>
										</tr>
										<tr>
											<td>Cilindro</td>
											<td><input <?=$readonly?> class='input_text' type="text" name="cilindro" value="<?=$dados['cilindro']?>"></td>
											<td>Cartucho Y</td>
											<td><input <?=$readonly?> class='input_text' type="text" name="cartucho_y" value="<?=$dados['cartucho_y']?>"></td>
										</tr>
										<tr>
											<td>Toner</td>
											<td><input <?=$readonly?> class='input_text' type="text" name="toner" value="<?=$dados['toner']?>"></td>
											<td>Cartucho C</td>
											<td><input <?=$readonly?> class='input_text' type="text" name="cartucho_c" value="<?=$dados['cartucho_c']?>"></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<?php
							if(strlen(trim($result[0]['referencia_peca'])) > 0 OR strlen(trim($result[0]['descricao_peca'])) > 0){
						?>
						<tr>
							<td class='tal teste'><div class='titulos_perguntas'>PEÇAS SOLICITADAS</div></td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<?php
										foreach ($result as $key => $value) {
											if(!empty($value["referencia_peca"]) OR !empty($value["descricao_peca"])){
										?>
											<tr>
												<td> CÓDIGO: <?=$result[$key]['referencia_peca']?></td>
												<td> DESCRIÇÃO: <?=$result[$key]['descricao_peca']?></td>
												<input type="hidden" name="pecas[]" value="<?=$result[$key]['referencia_peca']?>|<?=$result[$key]['descricao_peca']?>">
											</tr>
										<?php
											}
										}
										?>
									</tbody>
								</table>
							</td>
						</tr>
						<?php
							}
						?>
						<tr>
							<td class='tal teste'><div class='titulos_perguntas'>DEFEITO DIAGNOSTICADO</div></td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td> PELO CLIENTE: </td>
											<td> <?=$descricao_defeito_reclamado?></td>
										</tr>
										<tr>
											<td colspan="2">PELA AUTORIZADA: <?=$result[0]['defeito_constatado_descricao']?></td>
										</tr>
										<tr>
											<td class='<?=(in_array("defeito_farcomp", $msg_error["campos"])) ? "error_td" : ""?>'>PELA FARCOMP:</td>
											<td>
												<input <?=$readonly?> class='input_text <?=(in_array("defeito_farcomp", $msg_error["campos"])) ? "error" : ""?>' type="text" name="defeito_farcomp" value="<?=$dados['defeito_farcomp']?>">
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class='tal teste'><div class='titulos_perguntas'>TESTES REALIZADOS</div></td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td class='<?=(in_array("analise_tecnico", $msg_error["campos"])) ? "error_td" : ""?>'>Teste do Técnico:<br/>
												<input <?=$readonly?> class='input_text <?=(in_array("teste_realizado", $msg_error["campos"])) ? "error" : ""?>' type="text" name="teste_realizado" value="<?=$dados['teste_realizado']?>">
											</td>

											<td class='<?=(in_array("analise_tecnico", $msg_error["campos"])) ? "error_td" : ""?>'>Analíse feita pelo Técnico: <br/>
												<input <?=$readonly?> type="text" class='input_text <?=(in_array("analise_tecnico", $msg_error["campos"])) ? "error" : ""?>' name="analise_tecnico" value='<?=$dados['analise_tecnico']?>'>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class='tal teste'><div class='titulos_perguntas'>CONCLUSÃO DO LAUDO</div></td>
						</tr>
						<tr>
							<td class='teste'>
								<table class='table table-bordered table-large'>
									<tbody>
										<tr>
											<td colspan="3">
												<textarea <?=$readonly?> class='<?=(in_array("conclusao_laudo", $msg_error["campos"])) ? "error" : ""?>' style="width: 100%" rows="4" name='conclusao_laudo'><?=$dados['conclusao_laudo']?></textarea>
											</td>
										</tr>
										<tr>
											<td>Aprovação do Técnico: </td>
											<td><strong>GARANTIA</strong></td>
											<td> <text class='text-error'>REPROVADA</text> <input onclick="return false;" style="margin-bottom: 7px;" type="checkbox" checked="true" name="aprovacao_tecnico" value='t'></td>
											<?php
											if ($areaAdmin === true) {
											?>
											<td>
												<strong>Motivo da Reprova</strong>
												<select name="motivo_reprova">
													<option value=""></option>
													<option value="CASOS FORTUITOS">CASOS FORTUITOS</option>
													<option value="GARANTIA EXPIRADA">GARANTIA EXPIRADA</option>
													<option value="MANUTENÇÃO POR NÃO AUTORIZADOS">MANUTENÇÃO POR NÃO AUTORIZADOS</option>
													<option value="MAU USO">MAU USO</option>
													<option value="QUEDAS OU IMPACTOS">QUEDAS OU IMPACTOS</option>													
													<option value="SEM DOCUMENTAÇÃO">SEM DOCUMENTAÇÃO</option>
													<option value="SUPRIMENTOS NÃO ORIGINAIS">SUPRIMENTOS NÃO ORIGINAIS</option>
													<option value="QUEDAS OU IMPACTOS">QUEDAS OU IMPACTOS</option>
													<option value="TENSÃO NA REDE ELÉTRICA">TENSÃO NA REDE ELÉTRICA</option>
													<option value="UTILIZAÇÃO EXCEDIDA">UTILIZAÇÃO EXCEDIDA</option>
												</select>
											</td>
											<?php
											}
											?>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<?php if (strlen($os) > 0 && strlen($auditoria) > 0) { ?>
							<tr>
								<td class='tal teste'><div class='titulos_perguntas'>ANEXOS</div></td>
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
													<?php }?>
													<input type="file" name="anexo_laudo_1">
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
													<?php }?>
													<input type="file" name="anexo_laudo_2">
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
													<?php }?>
													<input type="file" name="anexo_laudo_3">
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<td class='teste'>
								<p>Farcomp Comércio e Informática Ltda - Av. Imirim, 1206 - Imirim - São Paulo/SP - (11) 2256-9100 - www.farcomp.com.br</p>
							</td>
						</tr>
					</tdboy>
				</table>
				<?php }?>

				<input type="hidden" id="btn_acao" name="btn_acao" value="">
			</form>
			<div class="tac">
				<button class='btn btn-info' name='btn_acao' <?=$btn_display?> onclick="confirma();">Gravar</button>
				<button type='button' onclick="window.parent.retorno_laudo('<?=$dados_inseridos?>','<?=$auditoria?>','<?=$os?>')" class="btn">Fechar</button>
			</div>
			<?php
			}
			?>
		<?php
		}
		?>
	</div>
</body>
</html>
