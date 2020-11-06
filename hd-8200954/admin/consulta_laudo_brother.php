<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($areaAdmin === true) {
	include "autentica_admin.php";
} else {
	include "autentica_usuario.php";
	include "class/tdocs.class.php";
}

include "funcoes.php";
	$os = $_REQUEST["os"];
	$auditoria = $_REQUEST["auditoria"];

	if(empty($os)){
		$msg_erro = "Ordem de Serviço".$os."não encontrada";
	}

	if(empty($msg_erro)){
		$sql = "SELECT data,observacao FROM tbl_laudo_tecnico_os WHERE os = $os AND fabrica = $login_fabrica";
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
			$readonly = "readonly";
		}else{
			$msg_erro = "Laudo não cadastrado para essa auditoria";
		}

		$sql_posto_consumidor = "
			SELECT tbl_os.consumidor_nome AS nome_consumidor,
					tbl_posto.nome AS nome_posto,
					tbl_posto.email as posto_email,
					tbl_posto.fone as posto_telefone,
					tbl_posto.cnpj as posto_cnpj,
					tbl_posto.estado as posto_estado,

					tbl_os.revenda_nome AS revenda_nome,
					tbl_os.revenda_cnpj AS revenda_cnpj,

					tbl_os.consumidor_fone AS consumidor_fone,
					tbl_os.consumidor_cpf AS consumidor_cpf,
					tbl_os.consumidor_celular AS consumidor_celular,
					tbl_os.consumidor_email AS consumidor_email,
					tbl_produto.voltagem AS voltagem_produto,
					tbl_os.acessorios AS acessorios,
					tbl_os.aparencia_produto AS aparencia_produto,
					tbl_os.defeito_reclamado_descricao AS defeito_reclamado_descricao


			FROM tbl_os
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica

			WHERE
				tbl_os.os = $os
		";
		$res_posto_consumidor = pg_query($con, $sql_posto_consumidor);

		if (pg_num_rows($res_posto_consumidor) > 0) {
			$consumidor = pg_fetch_result($res_posto_consumidor, 0, 'nome_consumidor');
			$consumidor_cpf = pg_fetch_result($res_posto_consumidor, 0, 'consumidor_cpf');
			$consumidor_email = pg_fetch_result($res_posto_consumidor, 0, 'consumidor_email');
			$consumidor_fone = pg_fetch_result($res_posto_consumidor, 0, 'consumidor_fone');
			$consumidor_celular = pg_fetch_result($res_posto_consumidor, 0, 'consumidor_celular');
			$revenda_nome = pg_fetch_result($res_posto_consumidor, 0, 'revenda_nome');
			$revenda_cnpj = pg_fetch_result($res_posto_consumidor, 0, 'revenda_cnpj');
			$posto      = pg_fetch_result($res_posto_consumidor, 0, 'nome_posto');
			$posto_email      = pg_fetch_result($res_posto_consumidor, 0, 'posto_email');
			$posto_estado      = pg_fetch_result($res_posto_consumidor, 0, 'posto_estado');
			$posto_telefone      = pg_fetch_result($res_posto_consumidor, 0, 'posto_telefone');
			$posto_cnpj      = pg_fetch_result($res_posto_consumidor, 0, 'posto_cnpj');
			$voltagem_produto      = pg_fetch_result($res_posto_consumidor, 0, 'voltagem_produto');
			$acessorios      = pg_fetch_result($res_posto_consumidor, 0, 'acessorios');
			$aparencia_produto      = pg_fetch_result($res_posto_consumidor, 0, 'aparencia_produto');



		}
	}

	
?>
<!DOCTYPE html />
<html>
<head>
    <meta http-equiv=pragma content=no-cache />
    <link type="text/css" rel="stylesheet" media="all" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
    <link type="text/css" rel="stylesheet" media="all" href="bootstrap/css/bootstrap.css" />
    <link type="text/css" rel="stylesheet" media="all" href="css/tooltips.css" />
    <link type="text/css" rel="stylesheet" media="all" href="bootstrap/css/glyphicon.css" />
    <link type="text/css" rel="stylesheet" media="all" href="bootstrap/css/extra.css" />
    <link type="text/css" rel="stylesheet" media="all" href="css/tc_css.css" />
    <link type="text/css" rel="stylesheet" media="all" href="bootstrap/css/ajuste.css" />

    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="bootstrap/js/bootstrap.js" ></script>
    <script src="plugins/shadowbox_lupa/lupa.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js"></script>
    <script src="plugins/jquery.mask.js"></script>

    <script type="text/javascript">
    	function imprimir(){
    		$(".table th, .table td").css("padding", "3px");
    		$(".table th, .table td").css("line-height", "18px");
    		$(".imprimir, .ocultar_impressao").hide();
    		$(".exibir_impressao").show();
    		window.print();

    		$(".table th, .table td").css("padding", "8px");
    		$(".table th, .table td").css("line-height", "20px");
    		$(".exibir_impressao").hide();
    		$(".imprimir, .ocultar_impressao").show();

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
    	.titulos_perguntas{
    	    background-color: rgba(0, 0, 0, 0.36);
		    width: 205px;
		    border-radius: 4px;
		    border: 10;
		    border: 3px solid;
		    padding-left: 15px;
    	}
    	.titulos_perguntas2{
    	    background-color: #00529c;
		    padding: 5px;
		    color: #fff;
		    font-weight: bold;
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

	@media screen {
		.exibir_impressao {
			display: none;
		}
	}

	@media print {
		.ocultar_impressao {
			display: none;
		}
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

	<div class='container' style="overflow-y: <?=$overflow_y?>;" >
		<?php
		if (empty($msg_erro)) {
		?>
			<?php if ($login_fabrica != 203) {?>
			<div class="row-fluid">
				<img src="logos/logo_laudo_brother.jpg">
			</div>
			<?php } else {?>
				<table id="resultado_os_atendimento" class='table table-large' >
						<thead>
							<tr>
								<td style="text-align: left;">
									<table style="border: solid 1px #333;width: 60%" id="resultado_os_atendimento" class='table table-large' >
										<thead>
											<tr>
												<td style="text-align: left;">
													<h4>INFORMAÇÕES DO POSTO</h4>
													RAZÃO SOCIAL: <?=$posto?><br>
													CNPJ: <?=$posto_cnpj?> - TELEFONE: <?=$posto_telefone?><br>
													E-MAIL: <?=$posto_email?>
													
												</td>
											</tr>
										</thead>
									</table>
								</td>
								<?php if ($areaAdmin === true) {?>
								<td style="text-align: right;"><img width="200" src="../logos/logo_brother.jpg"></td>
								<?php } else {?>
								<td style="text-align: right;"><img width="200" src="logos/logo_brother.jpg"></td>
								<?php }?>
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
										<td class='tal teste'><b>NOME:</b>	 <?=$consumidor?></td>
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
										<td class='tal teste'><b>CNPJ/CPF:</b> <?=$consumidor_cpf?></td>
										<td class='tal teste'><b>E-MAIL:</b> <?=$consumidor_email?></td>
										<td class='tal teste'><b>TELEFONE:</b> <?=$consumidor_fone?> <?=$consumidor_celular?></td>
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
										<td class='tal teste'> <b>ORDEM DE SERVIÇO:</b> <?=$dados["numero_os"]?></td>
										<td class='tal teste'> <b>DATA DE ENTRADA NA AT:</b> <?=$dados["data_abertura"]?></td>
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
										<td class='tal teste'> <b>EQUIPAMENTO:</b> <?=$dados["referencia_produto"]?> - <?=$dados["descricao_produto"]?></td>
										<td class='tal teste'> <b>NÚMERO DE SÉRIE:</b> <?=$dados["numero_serie"]?></td>
										<td class='tal teste'> <b>VOLTAGEM:</b> <?=$voltagem_produto?></td>
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
										<td colspan="2"> <b>NF DE COMPRA:</b> <?=$dados["nota_fiscal"]?></td>
										<td> <b>DATA DA EMISSÃO:</b> <?=$dados["data_nf"]?></td>
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
										<td colspan="2"> <b>REVENDA / LOCAL DA COMPRA:</b> <?=$revenda_nome?></td>
										<td> <b>CNPJ REVENDA:</b> <?=$revenda_cnpj?></td>
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
										<td colspan="3"> <b>ACESSÓRIOS QUE ACOMPANHAM O EQPTO:</b> <?=$acessorios?></td>
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
										<td colspan="3"> <b>CONDIÇÕES FÍSICAS / VISUAIS DO EQPTO:</b> <?=$aparencia_produto?></td>
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
										<td colspan="3"> <b>DEFEITO RECLAMADO PELO CONSUMIDOR:</b> <?=$dados['descricao_defeito_reclamado']?></td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
					<tr class='titulo_tabela2'>
						<td colspan="3" class="tac" >LAUDO TÉCNICO -  ORDEM DE SERVIÇO Nº <?=$dados['numero_os']?></td>
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
										<td colspan="3">
											<input <?=$readonly?> class='input_text' type="text" name="tipo_laudo" value="<?=$dados['tipo_laudo']?>">

										</td>
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
						if(!empty($dados['pecas']) && count($dados['pecas']) > 0){
					?>
					
					<tr>
						<td class='tal teste'><div  style="width: auto;" class='titulos_perguntas2'>PEÇAS SOLICITADAS</div></td>
					</tr>
					<tr>
						<td class='teste'>
							<table class='table table-bordered table-large'>
								<tbody>
									<?php
									foreach ($dados["pecas"] as $key => $value) {
										$conteudo = explode("|",$value);
										$referencia = utf8_decode($conteudo[0]);
										$descricao = utf8_decode($conteudo[1]);
									?>
										<tr>
											<td> <b>CÓDIGO:</b> <?=$referencia?></td>
											<td> <b>DESCRIÇÃO: </b><?=$descricao?></td>
										</tr>

									<?php
									}
									?>
								</tbody>
							</table>
						</td>
					</tr>
					<?php }?>

					
					<tr>
						<td class='tal teste'><div style="width: auto;" class='titulos_perguntas2'>LAUDO CONCLUSIVO / CONSTATADO PELO TECNICO CREDENCIADO / AUTORIZADA</div></td>
					</tr>
					<tr>
						<td class='teste'>
							<table class='table table-bordered table-large'>
								<tbody>
									<tr>
										<td colspan="4">
											<textarea <?=$readonly?> style="width: 100%" rows="4" name='conclusao_laudo'><?=$dados['conclusao_laudo']?></textarea>
										</td>
									</tr> 
									<tr><?php /*
										<td><b>Aprovação do Técnico:</b> </td>
										<td><strong>GARANTIA</strong></td>
										<td> <text class='text-error'>REPROVADA</text> <input onclick="return false;" style="margin-bottom: 7px;" type="checkbox" checked="true" name="aprovacao_tecnico" value='t'></td>*/?>
										<td>
											<text class='text-error'><b>Motivo da Reprova:</b>&nbsp;</text><?=$dados['motivo_reprova']?>
										</td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
					<?php
						unset($amazonTC, $anexos, $types);
						$amazonTC = new TDocs($con, $login_fabrica,"laudo_anexo");
						$anexos = array();
						$exibir_anexo = false;

						for ($i = 1; $i <= 3; $i++){
							$anexos["$i"]["nome"] = "laudo_anexo_".$os."_".$auditoria."_{$login_fabrica}_anexo_laudo_$i";
							$anexos["$i"]["url"] = $amazonTC->getDocumentsByName($anexos["$i"]["nome"])->url;
							if (strlen($anexos["$i"]["url"]) > 0) {
								$exibir_anexo = true;
							} else {
								$anexos["$i"]["nome"] = "laudo_anexo_".$os."_{$login_fabrica}_anexo_laudo_$i";
								$anexos["$i"]["url"] = $amazonTC->getDocumentsByName($anexos["$i"]["nome"])->url;

								if (strlen($anexos["$i"]["url"]) > 0) {
									$exibir_anexo = true;
								}
							}
						}

						if($exibir_anexo == true){ ?>
							<tr class="ocultar_impressao">
								<td class='tal teste'><div style="width: auto;" class='titulos_perguntas2'>ANEXOS</div></td>
							</tr>
							<tr>
								<td class='teste'>
									<table class='table'>
										<tbody>
												<?php
													$count = 1;
													foreach ($anexos as $key) {
														if (strlen($key["url"]) > 0) { ?>
														<tr><td>Anexo <?=$count?></td></tr>
														<tr>
														<td class="tac" style="border: none !important;">
															<div class="ocultar_impressao">
																<a href="<?=$anexos["$count"]['url']?>" target="_blank" >
																	<img id="imagem_anexo_$count" src="<?=$anexos["$count"]['url']?>" style="max-height: 240px !important; max-width: 240px !important;" border="0">
																</a>
															</div>
															<div class="exibir_impressao">
																<img id="imagem_anexo_$count" src="<?=$anexos["$count"]['url']?>" border="0">
															</div>
														</td>
														</tr>
												<?php
													}
													$count++;
												} ?>
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
												<textarea <?=$readonly?> class='' style="width: 100%" rows="4" name='orientacao_consumidor'><?=$dados['orientacao_consumidor']?></textarea>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					<tr>
						<td style="text-align: right;"><?php echo $estadosBR[$posto_estado];?>, <?php echo date("d/m/Y H:i:s",strtotime($data_laudo));?></td>
					</tr>
<tr>
    <td class='teste'>
        <table class='table table-bordered table-large'>
            <thead>
                <tr>
                    <th style='font-size:12px;'>Técnico Responsável</th>         
                    <th style='font-size:12px;'>Número de identificação CREA</th>        
                    <th style='font-size:12px;'>Assinatura</th>                        
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td height="100"></td>         
                    <td></td>        
                    <td></td>
                </tr>
                <tr>
                    <td colspan="100%">Este documento não pode ser cedido ou copiado sem previa autorização da Brother Internacional Corporation do Brasil Ltda.</td>                        
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
				</tdboy>
			</table>

			<?php } else {?>
							<hr class='class_linha1'>
			<hr class='class_linha2'>

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
					<tr>
						<td class='teste'>
							<table class='table table-bordered table-large'>
								<tbody>
									<tr>
										<?php
											if (strlen($posto) > 0) {
										?>
												<td><?=$posto;?></td>
										<?php }	?>
										<td> DATA DE ENTRADA NA AT: <?=$dados['data_abertura']?></td>
										<td> ORDEM DE SERVIÇO: <?=$dados['numero_os']?></td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
					<tr>
						<td class='tal teste'><div class='titulos_perguntas'>DADOS DO CLIENTE</div></td>
					</tr>
					<tr>
						<td class='teste'>
							<table class='table table-bordered table-large'>
								<tbody>
									<tr>
										<?php
											if (strlen($consumidor) > 0) {
										?>
											<td><?=$consumidor;?></td>
										<?php }	?>
										<td> NF DE COMPRA: <?=$dados['nota_fiscal']?></td>
										<td> DATA DA EMISSÃO: <?=$dados['data_nf']?></td>
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
										<td> MODELO: <?=$dados['referencia_produto']?> - <?=$dados['descricao_produto']?></td>
										<td> NS: <?=$dados['numero_serie']?></td>
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
					<tr>
						<td class='tal teste'><div class='titulos_perguntas'>PEÇAS SOLICITADAS</div></td>
					</tr>
					<tr>
						<td class='teste'>
							<table class='table table-bordered table-large'>
								<tbody>
									<?php
									foreach ($dados["pecas"] as $key => $value) {
										$conteudo = explode("|",$value);
										$referencia = utf8_decode($conteudo[0]);
										$descricao = utf8_decode($conteudo[1]);
									?>
										<tr>
											<td> CÓDIGO: <?=$referencia?></td>
											<td> DESCRIÇÃO: <?=$descricao?></td>
										</tr>

									<?php
									}
									?>
								</tbody>
							</table>
						</td>
					</tr>
					<tr>
						<td class='tal teste'><div class='titulos_perguntas'>DEFEITO DIAGNOSTICADO</div></td>
					</tr>
					<tr>
						<td class='teste'>
							<table class='table table-bordered table-large'>
								<tbody>
									<tr>
										<td> PELO CLIENTE: </td>
										<td><?=$dados['descricao_defeito_reclamado']?></td>
									</tr>
									<tr>
										<td colspan="2">PELA AUTORIZADA: <?=$dados['defeito_constatado_descricao']?></td>
									</tr>
									<tr>
										<td>PELA FARCOMP:</td>
										<td><input <?=$readonly?> class='input_text' type="text" name="defeito_farcomp" value="<?=$dados['defeito_farcomp']?>"></td>
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
										<td><br/><input <?=$readonly?> class='input_text' type="text" name="teste_realizado" value="<?=$dados['teste_realizado']?>"></td>
										<td>Analíse feita pelo Técnico: <br/><input <?=$readonly?> type="text" class='input_text' name="analise_tecnico" value='<?=$dados['analise_tecnico']?>'></td>
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
										<td colspan="4">
											<textarea <?=$readonly?> style="width: 100%" rows="4" name='conclusao_laudo'><?=$dados['conclusao_laudo']?></textarea>
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
											<text class='text-error'>Motivo da Reprova:&nbsp;</text><?=$dados['motivo_reprova']?>
										</td>
										<?php
										}
										?>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
					<?php
						unset($amazonTC, $anexos, $types);
						$amazonTC = new TDocs($con, $login_fabrica,"laudo_anexo");
						$anexos = array();
						$exibir_anexo = false;

						for ($i = 1; $i <= 3; $i++){
							$anexos["$i"]["nome"] = "laudo_anexo_".$os."_".$auditoria."_{$login_fabrica}_anexo_laudo_$i";
							$anexos["$i"]["url"] = $amazonTC->getDocumentsByName($anexos["$i"]["nome"])->url;
							if (strlen($anexos["$i"]["url"]) > 0) {
								$exibir_anexo = true;
							} else {
								$anexos["$i"]["nome"] = "laudo_anexo_".$os."_{$login_fabrica}_anexo_laudo_$i";
								$anexos["$i"]["url"] = $amazonTC->getDocumentsByName($anexos["$i"]["nome"])->url;

								if (strlen($anexos["$i"]["url"]) > 0) {
									$exibir_anexo = true;
								}
							}
						}

						if($exibir_anexo == true){ ?>
							<tr class="ocultar_impressao">
								<td class='tal teste'><div class='titulos_perguntas'>ANEXOS</div></td>
							</tr>
							<tr>
								<td class='teste'>
									<table class='table'>
										<tbody>
												<?php
													$count = 1;
													foreach ($anexos as $key) {
														if (strlen($key["url"]) > 0) { ?>
														<tr><td>Anexo <?=$count?></td></tr>
														<tr>
														<td class="tac" style="border: none !important;">
															<div class="ocultar_impressao">
																<a href="<?=$anexos["$count"]['url']?>" target="_blank" >
																	<img id="imagem_anexo_$count" src="<?=$anexos["$count"]['url']?>" style="max-height: 240px !important; max-width: 240px !important;" border="0">
																</a>
															</div>
															<div class="exibir_impressao">
																<img id="imagem_anexo_$count" src="<?=$anexos["$count"]['url']?>" border="0">
															</div>
														</td>
														</tr>
												<?php
													}
													$count++;
												} ?>
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


			<div class='row-fluid' style="text-align: center;">
				<button class='imprimir btn btn-small' onclick="imprimir();" >Imprimir</button>
				<?php
				if ($areaAdmin) {
				?>
					<button class='btn btn-small btn-primary' onclick="window.location = 'laudo_brother.php?os=<?=$_GET['os']?>&auditoria=<?=$_GET['auditoria']?>'" >Alterar</button>
				<?php
				}
				?>
			</div>
		<?php
		}else{
		?>
			<div class="alert alert-error" ><?=$msg_erro?></div>
		<?php
		}
		?>
	</div>
</body>
</html>
