<?php
/**
 * @author Brayan L. Rastelli
 * @description Cadastrar Auditoria. HD 896786
 */
 	
	include 'dbconfig.php';
	include_once 'helper.php';
	include 'includes/dbconnect-inc.php';

	$layout_menu      	= "cadastro";
	$admin_privilegios	= "inspetor";

	include 'autentica_admin.php';

	/* Request para gravar */
	if( isset($_POST['enviar']) && $_POST['enviar'] == 'Gravar' ) {
	
		try {

			$codigo_posto   = $_POST['posto_codigo'];
			$pesquisa       = (int) $_POST['pesquisa'];
			$tipo_auditoria = $_POST['tipo_auditoria'];
			$obs 			= $_POST['obs_adicional'];
			$data_inicial 	= $_POST['data_inicial'];
			$horario 		= $_POST['horario'];

			/* Inicio das validações */

	        list($di, $mi, $yi) = explode("/", $data_inicial);
	        
	        if( empty($di) || !checkdate($mi,$di,$yi)) 
	            throw new Exception("Data Inválida");	     

		    if (empty($tipo_auditoria)) {
		    	throw new Exception("Escolha o tipo de auditoria");		    	
		    }

		    if (empty($pesquisa)) {
		    	throw new Exception("Escolha a pesquisa");		    	
		    }

		    if (empty($codigo_posto))
				throw new Exception("Escolha o posto para cadastrar a auditoria");				

			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'";
			$res = pg_query($con, $sql);

			if (!pg_num_rows($res))
				throw new Exception("Posto não encontrado");

			$upload = $helper->file;
			$upload->validate($_FILES['foto_auditoria'], 'image');

			if (empty($_POST['pergunta']))
				throw new Exception("Responda as perguntas para gravar uma auditoria");

			$sql = "SELECT count(*) from tbl_pesquisa_pergunta JOIN tbl_pergunta USING(pergunta) JOIN tbl_tipo_pergunta USING(tipo_pergunta) JOIN tbl_tipo_relacao USING(tipo_relacao) WHERE pesquisa = $pesquisa AND tbl_pergunta.ativo AND tbl_tipo_pergunta.ativo AND sigla_relacao = 'D'";
			$res_pesquisa = pg_query($con, $sql);

			$qtde_perguntas = pg_result($res_pesquisa,0,0);

			if ( $qtde_perguntas > ( count($_POST['pergunta']) + count($_POST['checkbox']) ) ) {
				throw new Exception("Responda todas as perguntas para gravar a auditoria");
			}

			/* Fim validações */

			/* Prepara variaveis necessarias */

			$posto = pg_result($res, $i, 'posto');

			$xdata_inicial 	= implode('/', array_reverse(explode('-', $data_inicial))) . " $horario";
				
			/* Fim tratamentos */

			pg_exec($con, 'BEGIN TRANSACTION');

			$sql = "INSERT INTO tbl_auditoria_online(posto, fabrica, admin, data_pesquisa, pesquisa, tipo_auditoria, conclusao_auditoria)
					VALUES ($posto, $login_fabrica, $login_admin, '$xdata_inicial', $pesquisa, '$tipo_auditoria', '$obs') RETURNING auditoria_online";

			$res = pg_query($con, $sql);

			if (pg_errormessage($con)) {
				throw new Exception("Falha ao executar SQL: " . pg_errormessage($con) );			
			}

			$auditoria = pg_result($res, 0, 0);

			foreach($_POST['pergunta'] as $pergunta => $resposta) {

				if ( !empty($resposta) ) {
					$respostas++;
				}

				$tipo_resposta_item = 'null';
				$nota = 'null';
				$checkbox = false;

				$sql = "SELECT tipo_descricao
						FROM tbl_pergunta
						JOIN tbl_tipo_resposta USING(tipo_resposta)
						WHERE pergunta = $pergunta";

				$res = pg_query($con, $sql);

				switch(pg_result($res,0,0)) {

					case 'text'     :
					case 'textarea' :
						$txt_resposta = $resposta; break;
					case 'radio' :
						$tipo_resposta_item = $resposta; break;
					case 'range' :
						$nota = $resposta; break;

				}

				$sql = "INSERT INTO tbl_resposta(pergunta, tipo_resposta_item, nota, admin, pesquisa, auditoria_online, txt_resposta, observacao)
						VALUES($pergunta, $tipo_resposta_item, $nota, $login_admin, $pesquisa, $auditoria, '$txt_resposta', '".$_POST['obs'][$pergunta]."')";
				$res = pg_query($con, $sql);

				if ( pg_errormessage($con) ) {
					throw new Exception("Falha ao gravar auditoria: " . pg_errormessage($con));					
				}

			}

			// Grava as perguntas do tipo checkbox em tbl_resposta_item (sem relação com tbl_resposta)
			if (is_array($_POST['checkbox'])) {

			    foreach ($_POST['checkbox'] as $pergunta => $item) {

			        foreach ($item as $tipo_resp_item => $v) {

			            $sql = "INSERT INTO tbl_resposta_item (
			                        auditoria_online,
			                        pergunta,
			                        tipo_resposta_item,
			                        admin,
			                        obs
			                    ) VALUES (
			                        $auditoria,
			                        $pergunta,
			                        $tipo_resp_item,
			                        $login_admin,
			                        '".$_POST['obs'][$pergunta]."'
			                    )";

			            $res = pg_query($con, $sql); //@TODO criar campo
			            if ( pg_errormessage($con) ) {
							throw new Exception("Falha ao gravar checkbox: " . pg_errormessage($con));					
						}

			        }

			    }

			}

			$adminInfo = $helper->login->getInfo();

			$nome_admin = $adminInfo['nome_completo'];

			$sql = "INSERT INTO tbl_comunicado (
						fabrica, 
						descricao,
						posto, 
						tipo, 
						ativo,
						obrigatorio_site,
						mensagem
					)
					VALUES (
						$login_fabrica, 
						'Auditoria Cadastrada',
						$posto, 
						'auditoria_online', 
						TRUE,
						TRUE,
						'<strong>Prezado posto autorizado</strong><br />
						O(A) Inspetor(a) $nome_admin fez a inspeção do seu posto autorizado na data de {$data_inicial}, 
						favor abrir o anexo e depois confirmar a inspeção informando seu nome completo.<br /><hr /><br />'
					) RETURNING comunicado";
			
			$res = pg_query($con, $sql);
			if ( pg_errormessage($con) ) {
				throw new Exception("Falha ao gravar comunicado: " . pg_errormessage($con));					
			}

			$comunicado = pg_result($res,0,0);

			$sql = "INSERT INTO tbl_auditoria_online_comunicado (auditoria_online, comunicado)
					VALUES ($auditoria, $comunicado)";

			pg_query($con, $sql);
			if ( pg_errormessage($con) ) {
				throw new Exception("Falha ao gravar comunicado: " . pg_errormessage($con));					
			}

			$upload->setDirectory('anexos/auditoria_online');
			$upload->upload($_FILES['foto_auditoria'], $auditoria);

			pg_exec($con, 'COMMIT');
			header("Location:$PHP_SELF?msg=Gravado com Sucesso");

		} catch (Exception $e) {
			
			pg_exec($con, 'ROLLBACK');
			$msg_erro = $e->getMessage();

		}
	
	}
	/* Fim Request */
	
	/* Mensagem por GET */
	if ( isset($_GET['msg'])  && !empty($_GET['msg']) ) { 

		$msg = $_GET['msg'];

	}
	
	$title="CADASTRO DE AUDITORIA";

	include 'cabecalho.php';
	
?>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<link rel="stylesheet" type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" media="all">
<style type="text/css">

	.titulo_tabela {
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.titulo_coluna {
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.msg_erro {
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.formulario {
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	table.tabela tr td {
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	#tabela {display:none;}
	.sucesso {
	    background-color:#008000;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	}
	#relatorio tr td { cursor:pointer; }

</style>

<script type="text/javascript" src="js/jquery-1.4.2.js"></script>

<script type="text/javascript" src="js/jquery.maskedinput.js"></script>

<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>

<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>

<script type="text/javascript">
	function pesquisaPosto(campo,tipo){
		var campo = campo.value;

		if (jQuery.trim(campo).length > 2){
			Shadowbox.open({
				content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo+"&completo=true",
				player:	"iframe",
				title:		"Pesquisa Posto",
				width:	800,
				height:	500
			});
		}else
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}
	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento, numPosto, Endereco, Bairro, Cep, Fone, Fax, Email, Contato){

		gravaDados("posto_codigo",codigo_posto);
		gravaDados("posto_nome",nome);
		gravaDados("Cidade", cidade);
		gravaDados("Estado", estado);
		
		gravaDados("Endereco", Endereco);
		gravaDados("Bairro", Bairro);
		gravaDados("CEP", Cep);
		gravaDados("Fone", Fone);
		gravaDados("Fax", Fax);
		gravaDados("Email", Email);
		gravaDados("Contato", Contato);
		
	}
	
</script>

<?php if ( isset($msg_erro) && !empty($msg_erro) ) { ?>

	<div class="msg_erro" style="width:700px;margin:auto; text-align:center;"><?=$msg_erro?></div>

<?php } ?>

<?php if ( isset($msg) ) { ?>

	<div class="sucesso" style="width:700px;margin:auto; text-align:center;"><?=$msg?></div>

<?php } ?>

<div class="formulario" style="width:700px; margin:auto;">
	
	<div class="titulo_tabela">Cadastro</div>
	<form action="<?=$PHP_SELF?>" method="POST" name="form" enctype="multipart/form-data">
		<div style="padding:10px;">
			<table style="width:390px;margin:auto; text-align:left; border:none;">
				<tr>
					<td>
						<label for="data_inicial">Data Inicial</label><br />
						<input type="text" name="data_inicial" id="data_inicial" class="frm" size="13" value="<?=isset($_POST['data_inicial'])?$_POST['data_inicial'] : '' ?>" />
					</td>
					<td>
						<label for="horario">Horário</label><br />
						<input type="text" name="horario" id="horario" class="frm" size="13" value="<?=isset($_POST['horario'])?$_POST['horario'] : ''?>"/>
					</td>
				</tr>
				<tr>
					<td>
						Código do Posto<br />
						<input class="frm" type="text" id="posto_codigo" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>">
						<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.form.posto_codigo, 'codigo');">
					</td>
					<td colspan="2">
						Nome do Posto<br />
						<input class="frm" id="posto_nome" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>">
						<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.form.posto_nome, 'nome');">
					</td>
				</tr>
				<tr style="display:none;" class="dadosPosto">
					<td>
						<label for="">Endereço</label><br />
						<input type="text" readonly class="frm" value="<?=isset($_POST['Endereco']) ? $_POST['Endereco'] : ''?>" name="Endereco">
					</td>
					<td>
						<label for="">Bairro</label><br />
						<input type="text" readonly class="frm" value="<?=isset($_POST['Bairro']) ? $_POST['Bairro'] : ''?>" name="Bairro">
					</td>
				</tr>
				<tr style="display:none;" class="dadosPosto">
					<td>
						<label for="">Cidade</label><br />
						<input type="text" readonly class="frm" value="<?=isset($_POST['Cidade']) ? $_POST['Cidade'] : ''?>" name="Cidade">
					</td>
					<td>
						<label for="">Estado</label><br />
						<input type="text" readonly class="frm" value="<?=isset($_POST['Estado']) ? $_POST['Estado'] : ''?>" name="Estado">
					</td>
					<td>
						<label for="">CEP</label><br />
						<input type="text" readonly class="frm" value="<?=isset($_POST['CEP']) ? $_POST['CEP'] : ''?>" name="CEP">
					</td>
				</tr>
				<tr style="display:none;" class="dadosPosto">
					<td>
						<label for="">Fone</label><br />
						<input type="text" readonly class="frm" value="<?=isset($_POST['Fone']) ? $_POST['Fone'] : ''?>" name="Fone">
					</td>
					<td>
						<label for="">Fax</label><br />
						<input type="text" readonly class="frm" value="<?=isset($_POST['Fax']) ? $_POST['Fax'] : ''?>" name="Fax">
					</td>
				</tr>
				<tr style="display:none;" class="dadosPosto">
					<td>
						<label for="">E-mail</label><br />
						<input type="text" readonly class="frm" value="<?=isset($_POST['Email']) ? $_POST['Email'] : ''?>" name="Email">
					</td>
					<td>
						<label for="">Pessoa para Contato</label><br />
						<input type="text" readonly class="frm" value="<?=isset($_POST['Contato']) ? $_POST['Contato'] : ''?>" name="Contato">
					</td>
				</tr>
				<tr style="display:none;" class="dadosPosto">
					<td colspan="3">Para alterar os dados do posto, acesse a tela de cadastro, clicando <a href="posto_cadastro.php" target="_blank">aqui</a>.</td>
				</tr>
				<tr>
					<td colspan="2">
						<label for="pesquisa">Escolha uma pesquisa</label><br />
						<select name="pesquisa" id="pesquisa" class="frm">
							<option value=""></option>
							<?php
								$sql = "SELECT pesquisa, descricao
										FROM tbl_pesquisa
										WHERE fabrica = $login_fabrica
										AND ativo";

								$res = pg_query($con, $sql);

								for ($i=0; $i < pg_num_rows($res); $i++) {

									$id_pesquisa 	= pg_result($res, $i, 'pesquisa');
									$descricao 	= pg_result($res, $i, 'descricao');

									$selected = $pesquisa == $id_pesquisa ? 'selected' : '';

									echo '<option value="'.$id_pesquisa.'" '.$selected.'>' . $descricao . '</option>';

								}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="3">
						<label for="">Tipo de Auditoria</label><br />
						<input type="radio" name="tipo_auditoria" <?=$tipo_auditoria == 'Inicial' ? 'checked' : ''; ?> id="Inicial" value="Inicial" /><label for="Inicial">Inicial</label>
						<input type="radio" name="tipo_auditoria" <?=$tipo_auditoria == 'Acompanhamento' ? 'checked' : ''; ?> id="Acompanhamento" value="Acompanhamento" /><label for="Acompanhamento">Acompanhamento</label>
					</td>
				</tr>
				<tr>
					<td colspan="3">
						<div id="anexos">
							<label for="">Anexar Imagens</label>&nbsp;
							<img src="imagens/help.png" title="Tipos permitidos: JPG, PNG e GIF com no máximo 1MB"><br />
							<input type="file" name="foto_auditoria[]" class="frm" /><br />
							<input type="file" name="foto_auditoria[]" class="frm" /><br />
							<input type="file" name="foto_auditoria[]" class="frm" />
						</div>
					</td>
				</tr>
				<tr>
					<td colspan="3">
						<label for="obs_adicional">Informações adicionais</label><br />
						<textarea name="obs_adicional" id="obs_adicional" class="frm" cols="50" rows="5"><?=$_POST['obs_adicional']?></textarea>
						<p style="font-size:9px; margin-top:0px;">Quando necessário, anexar imagens das não-conformidades evidenciadas.</p>
					</td>
				</tr>
			</table>

		</div>

		<div id="perguntas" style="width:700px; margin:auto;">
			<?php
				if (isset($_POST['enviar']) && !empty($msg_erro))
					include 'cadastro_auditoria_ajax.php';
			?>
		</div>

		<div id="submitRow" style="display:none; width:100px; margin:10px auto;">
			<input type="submit" name="enviar" value="Gravar"  />
		</div>

	</form>
</div>

<script type="text/javascript">

	function getPerguntas(pesquisa) {
		
		$.ajax({

			url: 'cadastro_auditoria_ajax.php',
			type: 'GET',
			dataType: 'html',
			data: { 
				ajax: 'true', 
				pesquisa: pesquisa,
				cache: '<?=md5($pesquisa)?>'
			},
			complete: function(xhr, textStatus) {
		    
			},
			success: function(data, textStatus, xhr) {
				$("#perguntas").html(data);
			},
			error: function(xhr, textStatus, errorThrown) {
				alert('Essa pesquisa não possui perguntas cadastradas');
			}

		});
	}

	$(function() {

		<?php if (!empty($msg_erro)) : ?>
			$(".dadosPosto").show();
		<?php endif; ?>

		Window.prototype.gravaDados = function (name, valor){
			try{
				$("input[name="+name+"]").val(valor);
				$(".dadosPosto").show();
			} catch(err){			
				return false;
			}
		}

		Shadowbox.init();
		
		$( "#data_inicial" ).maskedinput("99/99/9999");
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$( "#horario" ).maskedinput("99:99");

		if ($("#pesquisa option:selected").val() != '') {

			$("#submitRow").show();

		}

		$("#pesquisa").change(function(e) {

			obj = $(this);

			if (obj.val() == '' ) {
				$("#submitRow").hide();
				return false;
			}

			$("#submitRow").show();

			getPerguntas(obj.val());

		});

	});
</script>

<?php include 'rodape.php'; ?>
