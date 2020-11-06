<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($areaAdmin === true) {
	include "autentica_admin.php";
} else {
	include "autentica_usuario.php";
	include_once 'class/tdocs.class.php';
}

include 'plugins/fileuploader/TdocsMirror.php';

	include_once __DIR__.'/funcoes.php';
	$os = $_REQUEST["os"];

	$sql = "SELECT TO_CHAR(data_conserto, 'DD/MM/YYYY') AS data_conserto, TO_CHAR(data_fechamento, 'DD/MM/YYYY') AS data_fechamento, defeito_constatado , finalizada FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$data_conserto = pg_fetch_result($res, 0, 'data_conserto');
		$data_fechamento = pg_fetch_result($res, 0, 'data_fechamento');
		$defeito_constatado = pg_fetch_result($res, 0, 'defeito_constatado');
		$finalizada = pg_fetch_result($res, 0, 'finalizada');

		// Enviar SMS - HD 6677078
		// INICIO
		if(!empty($data_conserto) && in_array($login_fabrica, [167, 203])){
			include "class/sms/sms.class.php";
			$sms = new SMS();

			$sqlSMS = "SELECT consumidor_celular, sua_os, referencia, descricao, nome, os_troca
	                    FROM tbl_os
	                    JOIN tbl_produto USING(produto)
	                    JOIN tbl_posto USING(posto)
	                    LEFT JOIN tbl_os_troca USING(os)
	                    WHERE os = {$os}";

	        $resSMS = pg_query($con, $sqlSMS);
	        $envia_sms = false;

	        if (pg_num_rows($resSMS) > 0) {
				$consumidor_celular   = pg_fetch_result($resSMS, 0, 'consumidor_celular');
				$sms_os               = pg_fetch_result($resSMS, 0, 'sua_os');
				$sms_produto          = pg_fetch_result($resSMS, 0, 'referencia') . ' - ' . pg_fetch_result($resSMS, 0, 'descricao');
				$sms_produto_descricao= pg_fetch_result($resSMS, 0, 'descricao');
				$sms_posto            = pg_fetch_result($resSMS, 0, 'nome');
				$sms_os_troca         = pg_fetch_result($resSMS, 0, 'os_troca');

	            if (!empty($consumidor_celular)) {
	                $envia_sms = true;
	            }

                $sqlEnviouSms = "SELECT JSON_FIELD('enviou_sms',campos_adicionais) AS enviou_sms
                    				FROM tbl_os_campo_extra
                    				WHERE os = {$os}
                				";

                $resEnviouSms = pg_query($con, $sqlEnviouSms);

                $enviouSms = pg_fetch_result($resEnviouSms, 0, 'enviou_sms');

                if ($enviouSms  == 't') {
                    $envia_sms = false;
                }                

	            if (true === $envia_sms) {
					$fabnome = $sms->nome_fabrica;

					if(in_array($login_fabrica, [167, 203])){
						$sms_msg = traduz("Informarmos que a Ordem de serviço {$os} referente ao produto {$sms_produto} se encontra disponível para retirada. Caso tenha dúvidas acesse nossa central de atendimento 11-2256-9100");						
					}

					$enviando_sms = $sms->enviarMensagem($consumidor_celular, $os, '', $sms_msg);					
					
					if ($enviando_sms === true) {						
						$ins_campos_adicionais = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($os, $login_fabrica, '{\"enviou_sms\": \"t\"}') on conflict(os) do  update set campos_adicionais = jsonb_set(regexp_replace(tbl_os_campo_extra.campos_adicionais,'(\w)\\\\u','\1\\\\\\\\u','g')::jsonb,'{enviou_sms}','\"t\"'::jsonb);";
						$qry_campos_adicionais = pg_query($con, $ins_campos_adicionais);
					}	
                }
            }
        }
        // FIM

		if(strlen(trim($data_conserto)) > 0 && !in_array($login_fabrica, [167,193,203])){
			if(!empty($finalizada)) {
				$msg_erro["msg"][]    = "Data de conserto já gravada para essa OS: ".$os;
				$readonly = 'readonly';
				$display = "style='display:none;'";
			}
		}else if(in_array($login_fabrica, [167,193,203])){

			if(strlen(trim($data_conserto)) > 0 && strlen(trim($data_fechamento)) == 0){
				$readonly = 'disabled';
			}else if(strlen(trim($data_fechamento)) > 0){
				$msg_erro["msg"][]    = "Data de fechamento já gravada para essa OS: ".$os;
				$readonly = 'disabled';
				$display = "style='display:none;'";
			}

			if (in_array($login_fabrica, [167, 203])) {
				if(empty($defeito_constatado)) {
					$msg_erro["msg"][] = "Os sem defeito constatado, não pode ser finalizada";
					$readonly = 'disabled';
					$display = "style='display:none;'";

				}
			}
		}
	}

	if($_POST['btn_acao'] ==  "Enviar") {

		$data_conserto = $_POST['data_conserto'];
		$data_fechamento = $_POST['data_fechamento'];
		$file_data_conserto  = $_FILES['file_data_conserto'];

		if(count($msg_erro["msg"]) == 0){

			if(in_array($login_fabrica, [167,193,203])){
				$sql = "
					SELECT 
						to_char(data_conserto,'DD/MM/YYYY') AS data_conserto,  
						tbl_tipo_atendimento.descricao AS descricao_tipo_atendimento
					FROM tbl_os 
					JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica
					WHERE os = $os";
				$res = pg_query($con,$sql);

				$data_conserto = pg_fetch_result($res,0,'data_conserto');
				$descricao_tipo_atendimento = pg_fetch_result($res, 0, 'descricao_tipo_atendimento');
			}

			if (!strlen($data_conserto)) {
				$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
				$msg_erro["campos"][] = "data";
			} else {
				list($di, $mi, $yi) = explode("/", $data_conserto);

				if (!checkdate($mi, $di, $yi)) {
					$msg_erro["msg"][]    = "Data Inválida";
					$msg_erro["campos"][] = "data";
				} else {
					$aux_data_conserto = "{$yi}-{$mi}-{$di}";

				}
			}

			if(in_array($login_fabrica, [167,193,203]) AND strlen($data_fechamento) > 0){

				list($di, $mi, $yi) = explode("/", $data_fechamento);

				if (!checkdate($mi, $di, $yi)) {
					$msg_erro["msg"][]    = "Data Fechamento Inválida";
					$msg_erro["campos"][] = "data";
				} else {
					$aux_data_fechamento = "{$yi}-{$mi}-{$di}";

				}
			}

			if((strlen(trim($file_data_conserto['name'])) == 0 && !in_array($login_fabrica, [167,193,203])) || (strlen(trim($file_data_conserto['name'])) == 0 && in_array($login_fabrica, [167,193,203])) && !empty($data_fechamento) ){

				$sql = "select tdocs from tbl_tdocs where fabrica = $login_fabrica and referencia_id = $os and obs ~'assinatura' and situacao ='ativo' "; 
				$res = pg_query($con, $sql); 
				if(pg_num_rows($res) == 0 ) {
					$msg_erro["msg"][]    = "Por favor, inserir o anexo.";
					$msg_erro["campos"][] = "file_documentacao";
				}
		    }

		    if(count($msg_erro["msg"]) == 0){
		    	//VALIDAÇÃO DE DATA
		    	$sql = "SELECT '$aux_data_conserto' > CURRENT_TIMESTAMP ";
		    	$res = @pg_query($con,$sql);
		    	$erro = pg_last_error($con);

				if (pg_fetch_result($res,0,0) == 't'){
					$msg_erro["msg"][] = "Data de conserto não pode ser superior a data atual";
					$msg_erro["campos"][] = "data";
				}

				$sql = "SELECT '$aux_data_conserto' < tbl_os.data_abertura FROM tbl_os where os=$os";
				$res = pg_query($con,$sql);
				$erro .= pg_last_error($con);

				if (pg_fetch_result($res,0,0) == 't'){
					$msg_erro["msg"][] = "Data de conserto não pode ser anterior a data de abertura";
					$msg_erro["campos"][] = "data";
				}

				if(strlen($erro) > 0){
					$msg_erro["msg"][] = "Data de conserto inválida";
					$msg_erro["campos"][] = "data";
				}

				if(in_array($login_fabrica, [167,193,203]) AND strlen($aux_data_fechamento) > 0){
					//VALIDAÇÃO DE DATA
			    	$sql = "SELECT '$aux_data_fechamento' > CURRENT_TIMESTAMP ";
			    	$res = @pg_query($con,$sql);
			    	$erro = pg_last_error($con);

					if (pg_fetch_result($res,0,0) == 't'){
						$msg_erro["msg"][] = "Data de fechamento não pode ser superior a data atual";
						$msg_erro["campos"][] = "data";
					}

					$sql = "SELECT '$aux_data_fechamento' < tbl_os.data_abertura FROM tbl_os where os=$os";
					$res = pg_query($con,$sql);
					$erro .= pg_last_error($con);

					if (pg_fetch_result($res,0,0) == 't'){
						$msg_erro["msg"][] = "Data de fechamento não pode ser anterior a data de abertura";
						$msg_erro["campos"][] = "data";
					}

					if(strlen($erro) > 0){
						$msg_erro["msg"][] = "Data de fechamento inválida";
						$msg_erro["campos"][] = "data";
					}
				}

				if(count($msg_erro["msg"]) == 0){
					$hora_fechamento = date('H:i:s');
					$aux_data_conserto = $aux_data_conserto.' '.$hora_fechamento;

					try {
						if (in_array($login_fabrica, [203])) {
							$valor_mo        = 0;
							$adicionalMO     = 25;
							$temAdicionalMO  = false;
							$recebidoCorreio = false;

							/*
							* VERIFICA SE A OS TEM A FLAG 'PRODUTO_RECEBIDO_VIA_CORREIOS'
							*/
							$sqlFlag = "SELECT JSON_FIELD('produto_recebido_via_correios',campos_adicionais) AS produto_recebido_via_correios
	                    				FROM tbl_os_campo_extra
	                    				WHERE os = {$os}";
			                $resFlag = pg_query($con, $sqlFlag);
			                $row     = pg_fetch_result($resFlag, 0, 'produto_recebido_via_correios');

			                $recebidoCorreio = ($row  == 't') ? true : false;

							/*
							*	VERIFICA SE A OS TEM PEÇA LANÇADA
							*   SE TIVER SETA O JOIN E CONDIÇÃO DE SERVIÇO REALIZADO
							*/

							$sqlServico   = "SELECT servico_realizado
											FROM tbl_servico_realizado
											WHERE fabrica = {$login_fabrica}
											AND UPPER(descricao) = UPPER('AJUSTE')";
							$resServico   = pg_query($con, $sqlServico);

							if (pg_num_rows($resServico) > 0){
								$codServico = pg_fetch_result($resServico, 0, 'servico_realizado');
							}

							$sqlPeca = "SELECT 
										tbl_os.sua_os
										FROM tbl_os
										INNER JOIN tbl_os_produto 	     ON tbl_os_produto.os 			  = tbl_os.os
										INNER JOIN tbl_os_item 	  		 ON tbl_os_item.os_produto 		  = tbl_os_produto.os_produto
										INNER JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
										WHERE tbl_os.fabrica = {$login_fabrica}
										AND tbl_os.os  	     = {$os}";
							$resPeca = pg_query($con,$sqlPeca);

							if (pg_num_rows($resPeca) > 0) {
								$join_item    = "JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto";
								$cond_servico = "AND mosr.servico_realizado = tbl_os_item.servico_realizado";
							} else {
								$cond_servico = (!empty($codServico)) ? "AND mosr.servico_realizado = {$codServico}" : "";
							}

							if (strtolower($descricao_tipo_atendimento) == "garantia recusada"){
								$cond_servico = "AND mosr.servico_realizado = {$codServico}";
							}

							if (!empty($login_posto)) {
								$cond_posto = " AND tbl_os.posto = $login_posto ";
								
								/*
								* VERIFICA SE O POSTO É AUTORIZADA PREMIUM
								* SE FOR, SET A FLAG DE ADICIONAL DE M.O
								*/
								$sqlPremium = " SELECT tbl_tipo_posto.codigo
                                              FROM tbl_posto_fabrica
                                              INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                                              WHERE tbl_posto_fabrica.fabrica  = {$login_fabrica}
                                              AND tbl_posto_fabrica.posto      = {$login_posto}
                                              AND UPPER(tbl_tipo_posto.codigo) = UPPER('PREMIUM')";
	                            $resPremium = pg_query($con,$sqlPremium);

								$temAdicionalMO = (pg_num_rows($resPremium) > 0) ? true : false;
							}

							/*
							* VERIFICA SE EXISTE UMA MÃO DE OBRA AMARRADA AO TIPO DO POSTO, PRODUTO, E SERVIÇO REALIZADO
							* SE TIVER, ATUALIZA O CAMPO mao_de_obra DA TBL_OS, PARA CONSEGUIR FAZER O CALCULO NA GERAÇÃO DO EXTRATO
							*/
							$sqlMO = "
						        	SELECT 
						        		tbl_os.os,
						        		mosr.mao_de_obra
						        	FROM tbl_os
									JOIN tbl_produto        ON tbl_produto.produto = tbl_os.produto
									JOIN tbl_os_produto     ON tbl_os_produto.produto = tbl_os.produto 
									AND tbl_os_produto.os = tbl_os.os
									{$join_item}
									JOIN tbl_posto_fabrica  ON tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = tbl_os.fabrica
									JOIN tbl_tipo_posto     ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica 
									JOIN tbl_fabrica        ON tbl_fabrica.fabrica = tbl_os.fabrica
									JOIN tbl_mao_obra_servico_realizado mosr ON mosr.fabrica = {$login_fabrica}
									{$cond_servico}
									AND mosr.tipo_posto 	   = tbl_posto_fabrica.tipo_posto
									AND mosr.familia   		   = tbl_produto.familia
									WHERE tbl_os.os     	   = {$os}
									AND tbl_os.fabrica  	   = {$login_fabrica} 
									{$cond_posto}";
							$queryMO = pg_query($con, $sqlMO);
							$resMO   = pg_fetch_all($queryMO);

							if (pg_num_rows($queryMO) > 0) {
								foreach ($resMO as $dadosLinha) {
						            $valor_mo   = ($temAdicionalMO && $recebidoCorreio) ? $adicionalMO + $dadosLinha['mao_de_obra'] : $dadosLinha['mao_de_obra'];
						            $os         = $dadosLinha['os'];
						            
						            $sqlMO2     = "UPDATE tbl_os SET mao_de_obra = {$valor_mo} WHERE os = {$os} AND fabrica = {$login_fabrica}";
						            $queryMO2   = pg_query($con, $sqlMO2);
						        }
							} else {
								if ($temAdicionalMO && $recebidoCorreio) {
									$sqlMO3     = "UPDATE tbl_os SET mao_de_obra = {$adicionalMO} WHERE os = {$os} AND fabrica = {$login_fabrica}";
						            $queryMO3   = pg_query($con, $sqlMO3);
								}
							}
						}

						if (!in_array($login_fabrica, array(167,203))) {
							$classOs = new \Posvenda\Os($login_fabrica, $os);
							$classOs->calculaOs();
						}

						if (in_array($login_fabrica, [203])) {
							$classOs 		= new \Posvenda\Os($login_fabrica, $os);
							$pedidoPendente = $classOs->_model->verificaPedidoPecasNaoFaturadasOS($con);

							if (!empty($pedidoPendente)) {
								throw new Exception($pedidoPendente);
								$msg_erro["msg"][] = $pedidoPendente;
							}
						}

						if (in_array($login_fabrica, array(173, 176))) {
							$classOs->finaliza($con);
						}

						if(in_array($login_fabrica, [167,193,203]) AND strlen($aux_data_fechamento) > 0){
							$campos_finaliza_os = ", data_fechamento = '{$aux_data_fechamento}', finalizada = now() ";

							if ($login_fabrica == 203) {
								$campos_finaliza_os .= " , status_checkpoint =  8";
							}
						}

						$sql = " UPDATE tbl_os
									SET data_conserto = '{$aux_data_conserto}' $campos_finaliza_os
									WHERE os = {$os}
									AND fabrica = {$login_fabrica}
									AND posto = {$login_posto} "; 
						$res = pg_query($con, $sql);

						$erro .= pg_last_error($con);

						if(strlen($erro) > 0){
							$msg_erro["msg"][] = "Erro ao gravar data de conserto";
							$msg_erro["campos"][] = "data";
						}else{

							if(strlen(trim($file_data_conserto['name'])) > 0){
								
			                    $data_hora = date("Y-m-d\TH:i:s");
			                    $destino   = '/tmp/';
			                    $tamanho   = 1024 * 1024 * 2;
			                    $extensoes = array('jpg', 'png', 'gif', 'pdf');

			                    $anx_nf    = $_FILES['file_data_conserto'];
			                    $extensao  = strtolower(end(explode('.', $_FILES['file_data_conserto']['name'])));
			                    
			                    if (array_search($extensao, $extensoes) === false) {
			                        $msg_erro["msg"][] = "Por favor, envie arquivos com as seguintes extensões: jpg, png, pdf ou gif <br />";
			                    }

			                    $nome_final = $login_fabrica.'_'.$os.'.'.$extensao;
			                    $caminho    = $destino.$nome_final;

			                    if (move_uploaded_file($_FILES['file_data_conserto']['tmp_name'], $caminho)) {
			                        $tdocsMirror = new TdocsMirror();
			                        $response    = $tdocsMirror->post($caminho);

			                        $file = $response[0];

			                        foreach ($file as $filename => $data) {
			                            $unique_id = $data['unique_id'];
			                        }

			                        $sql_verifica = "SELECT * 
			                                    FROM tbl_tdocs
			                                    WHERE fabrica = {$login_fabrica} 
			                                    AND contexto  = 'os'
			                                    AND tdocs_id  = '$unique_id'";
			                        $res_verifica = pg_query($con,$sql_verifica);
			                        if (pg_num_rows($res_verifica) == 0){
			                            $obs = json_encode(array(
			                                "acao"     => "anexar",
			                                "filename" => "{$nome_final}",
			                                "filesize" => "".$_FILES['file_data_conserto']['size']."",
			                                "data"     => "{$data_hora}",
			                                "fabrica"  => "{$login_fabrica}",
			                                "page"     => "anexo_data_conserto.php",
			                                "typeId"   => "assinatura",
			                                "descricao"=> ""
			                            ));

			                            $sql = "INSERT INTO tbl_tdocs(tdocs_id,fabrica,contexto,situacao,obs,referencia,referencia_id) 
			                                values('$unique_id', $login_fabrica, 'os', 'ativo', '[$obs]', 'os', $os);";
			                            $res       = pg_query($con, $sql);
			                            if (pg_last_error()) {
			                            	$msg_erro["msg"][] = "Erro ao inserir anexo";
			                        	}

			                        }
			                    } else {
			                      $msg_erro["msg"][] = "Não foi possível enviar o arquivo, tente novamente <br />";
			                    }
				            }
						}

						if (count($msg_erro["msg"]) == 0) {
				            $msg_success = "Dados gravados com sucesso.";
				            $gravado = "true";
				            $readonly = 'readonly';
							$display = "style='display:none;'";

				            $sql = "SELECT TO_CHAR(data_conserto, 'DD/MM/YYYY') AS data_conserto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
							$res = pg_query($con, $sql);

							if(pg_num_rows($res) > 0){
								$data = pg_fetch_result($res, 0, 'data_conserto');
							}
							/*
							* AO FECHAR A OS E A MESMA ESTIVER AMARRADA AO UM ATENDIMENTO, FINALIZO O ATENDIMENTO
							*/
							if ($login_fabrica == 176) {

								$sql = "SELECT tbl_os.hd_chamado
									      FROM tbl_os
									     WHERE tbl_os.fabrica = $login_fabrica
										   AND tbl_os.posto = $login_posto
										   AND tbl_os.os = $os
										   AND tbl_os.hd_chamado IS NOT NULL";

								$res = pg_query ($con, $sql);
								if (pg_num_rows($res) > 0) {
									$hd_chamado = pg_fetch_result($res, 0, 'hd_chamado');
									$sql = "UPDATE tbl_hd_chamado
									           SET status = 'Resolvido'
										     WHERE hd_chamado = $hd_chamado";
									$res = pg_query ($con,$sql);
								}
							}

				        }else{

				        	$sqlRollback = "
								UPDATE tbl_os SET
									finalizada = null,
									data_fechamento = null,
									data_conserto = null
								WHERE fabrica = {$login_fabrica}
								AND os = {$os}
							";
							$resRollback = pg_query($con, $sqlRollback);
				        }
					} catch (Exception $e) {
						if (preg_match("/\\u/", $e->getMessage())) {
							$erro = utf8_decode($e->getMessage());
						} else {
							$erro = $e->getMessage();
						}
						$msg_erro["msg"][] = $erro;
					}
				}
		    }
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

    <style type="text/css">
		.fileUpload {
		    position: relative;
		    overflow: hidden;
		    margin: 10px;
		}
		.fileUpload input.upload {
		    position: absolute;
		    top: 0;
		    right: 0;
		    margin: 0;
		    padding: 0;
		    font-size: 20px;
		    cursor: pointer;
		    opacity: 0;
		    filter: alpha(opacity=0);
		}
	</style>


    <script type="text/javascript">

    	function confirma(){
    		var confirm1 = confirm('Deseja realmente gravar os dados ? Após a gravação não será possível alterar');
		  	if (confirm1) {
		  		$("#btn_acao").val("Enviar");
			    $("#form_laudo").submit();			    
			} else {
			    return false;
			}
    	}

    	$(function() {			
			<?php if(!in_array($login_fabrica, [167,193,203])){?>
				$.datepickerLoad(Array("data_fechamento"));
			<?php } ?>

			$.datepickerLoad(Array("data_conserto"));

			$('#uploadBtn').change(function(){
	            var upload = $(this).val();
	            $("#uploadFile").val(upload);
	        });

			<?php
			if(in_array($login_fabrica, [167,193,203])){
			?>
				$("#data_conserto").change(function(){

					var dataConserto = $(this).val();
					var os = $("#os").val();

			        $.ajax({
			        	url: "os_fechamento.php",
			        	type: "POST",
			        	data: {"gravarDataconserto": dataConserto, "os": os},
			        	complete: function(data){
			        		if(data.responseText == ""){
			        			$("#data_conserto").attr({"readonly": "readonly"});
			        			alert("Data de Conserto gravada com sucesso");
			        			window.location.href = "anexo_data_conserto.php?os=" + os;
			        		}else{
			        			alert("Erro ao gravar Data de Conserto");
			        		}
			        	}
			        });
			    });
		    <?php
			}
			?>
		});


    </script>
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
		if (strlen(trim($os)) == 0) {
		?>
			<div class="alert alert-error" >Ordem de Serviço <?=$os?> não encontrada</div>
		<?php
		}else {					
			if (strlen(trim($os)) > 0) {
				if (count($msg_erro["msg"]) > 0) { ?>
				    <div class="alert alert-error">
						<h4><?=implode("<br />", $msg_erro["msg"])?></h4>						
				    </div>
				<?php
				}

				if(strlen(trim($msg_success)) > 0){ ?>
					<div class='alert alert-success'>
						<h4><?=$msg_success?></h4>
					</div>
				<?php
				}

				$texto_data = "Data Conserto";
				if (in_array($login_fabrica, array(173,176))) {
					$texto_data = "Data Fechamento";
				}

				?>

				<form name='anexo_data_conserto' enctype="multipart/form-data" id="form_laudo" METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
					<input type="hidden" name="os" id="os" value="<?=$os?>">

					<div class='titulo_tabela'>Favor anexar a OS com a assinatura do consumidor</div>
					<br/>
					<div class='row-fluid'>
						<div class='span2'></div>
						<div class='<?=(in_array($login_fabrica, [167,193,203])) ? "span8" : "span3"?>'>
							<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
								<label class='control-label' for='data_conserto'><?=$texto_data;?></label>
								<div class='controls controls-row'>
									<div class="<?=(in_array($login_fabrica, [167,193,203])) ? "span12" : "span6"?>" style='padding-top: 8px;'>
										<h5 class='asteristico'>*</h5>
											<input type="text" name="data_conserto" <?=$readonly?> id="data_conserto" size="12" maxlength="10" class='<?=(in_array($login_fabrica, [167,193,203])) ? "span3" : "span12"?>' value= "<?=$data_conserto?>"><label class='control-label'><?=(in_array($login_fabrica, [167,193,203])) ? "&nbsp;<b>Somente preencha o campo para informar o conserto</b>" : "";?></label>
									</div>
								</div>
							</div>
						</div>
						<?php
						if(!in_array($login_fabrica, [167,193,203])){
						?>
						<div class='span5'>
							<div class='control-group <?=(in_array("upload", $msg_erro["campos"])) ? "error" : ""?>'>
	                    		<label class='control-label' for='upload'>Anexo</label>
	                    		<div class='controls controls-row'>
	                        		<div class='span12'>
	                            		<h5 class='asteristico'>*</h5>
	                            		<input id="uploadFile"  placeholder="" disabled="disabled" />
			                            <div class="fileUpload btn">
			                                <span>Upload</span>
			                                <input id="uploadBtn" name='file_data_conserto' type="file" class="upload" />
			                            </div>
	                        		</div>
	                    		</div>
	                		</div>
						</div>
						<?php
						}
						?>
						<div class='span2'></div>
					</div>
					<?php
						if(in_array($login_fabrica, [167,193,203])){

							$data_fechamento  = (strlen(trim($data_fechamento))>0) ? $data_fechamento : date("d/m/Y") ;
					?>

							<div class='row-fluid'>
								<div class='span2'></div>
								<div class='span3'>
									<div class='control-group <?=(in_array("data_fechamento", $msg_erro["campos"])) ? "error" : ""?>'>
										<label class='control-label' for='data_fechamento'>Data Fechamento</label>
										<div class='controls controls-row'>
											<div class='span6' style='padding-top: 8px;'>
													<input type="text" name="data_fechamento" id="data_fechamento" size="12" maxlength="10" class='span12' readonly="true" value="<?=$data_fechamento?>">
											</div>
										</div>
									</div>
								</div>
								<div class='span5'>
									<div class='control-group <?=(in_array("upload", $msg_erro["campos"])) ? "error" : ""?>'>
			                    		<label class='control-label' for='upload'>Anexo</label>
			                    		<div class='controls controls-row'>
			                        		<div class='span12'>
			                            		<input id="uploadFile"  placeholder="" disabled="disabled" />
					                            <div class="fileUpload btn">
					                                <span>Upload</span>
					                                <input id="uploadBtn" name='file_data_conserto' type="file" class="upload" />
					                            </div>
			                        		</div>
			                    		</div>
			                		</div>
								</div>
								<div class='span2'></div>
							</div>
					<?php
						}
					?>
					<input type="hidden" id="btn_acao" name="btn_acao" value="">
				</form> 
				<div class="row-fluid">
					<div class="span2"></div>
					<div class="span8">
						<div class="tac">
							<button <?=$display?> class='btn btn-info' name='btn_acao' <?=$btn_display?> onclick="confirma();"><?=(in_array($login_fabrica, [167,193,203])) ? "Fechar OS" : "Fechar"?></button>
							<button type='button' onclick="window.parent.retorna_data_conserto('<?=$os?>','<?=$data?>','<?=$gravado?>')" class="btn">Sair</button>
						</div>
					</div>
					<div class="span2"></div>
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
