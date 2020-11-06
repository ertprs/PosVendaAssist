<?php
	/**
	 * @author Brayan L. Rastelli
	 * @description Relatorio Auditoria. HD 896786
	 */ 	
	include 'dbconfig.php';
	include_once 'helper.php';
	include 'includes/dbconnect-inc.php';

	$layout_menu      	= "callcenter";
	$admin_privilegios	= "call_center";

	include 'autentica_admin.php';

	/* Inclui helpers e classe DateHelper, ver admin/helpers/ */

	if (isset($_GET['excluir'])) {

		$id = (int) $_GET['excluir'];

		$sql = "SELECT auditoria_online FROM tbl_auditoria_online WHERE auditoria_online = $id AND fabrica = $login_fabrica";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) == 0) {
			header("HTTP/1.0 404 Not Found");
			echo 'Falha ao excluir, auditoria nao existe ou nao pertence a fabrica ' . $login_fabrica;
		}

		$sql = "DELETE FROM tbl_resposta WHERE auditoria_online = $id;
				DELETE FROM tbl_comunicado USING tbl_auditoria_online_comunicado 
				WHERE tbl_comunicado.comunicado = tbl_auditoria_online_comunicado.comunicado
				AND tbl_auditoria_online_comunicado.auditoria_online = $id;
				DELETE FROM tbl_auditoria_online WHERE auditoria_online = $id";

		$res = pg_query($con, $sql);

		if ( !pg_affected_rows($res) ) {
			header("HTTP/1.0 404 Not Found");
			echo 'Falha ao excluir ' . pg_errormessage($con);
		}

		exit;

	}

	/* Request para gravar */
	if( isset($_POST['enviar']) ) {

		try {
			
			$data_inicial 	= $_POST['data_inicial'];
			$data_final 	= $_POST['data_final'];
			$estado 		= $_POST['estado'];
			$inspetor 		= (int) $_POST['inspetor'];

			$posto_codigo 	= $_POST['posto_codigo'];

			// Valida data e o periodo (padrão 30 dias), caso der erro cai no catch
			DateHelper::validate(array($data_inicial, $data_final));
			DateHelper::validaPeriodo($data_inicial, $data_final);

			$xdata_inicial 	= DateHelper::converte($data_inicial);
			$xdata_final  	= DateHelper::converte($data_final);

			$cond = array();
			$cond[] = "AND data_pesquisa BETWEEN '$xdata_inicial'::date AND '$xdata_final'::date";

			if ( !empty($estado) ) {
				$cond[] = "AND LOWER(tbl_posto_fabrica.contato_estado) = LOWER('$estado')";
			}

			if (!empty($inspetor)) {
				$cond[] = "AND tbl_auditoria_online.admin = $inspetor";
			}

			if (!empty($posto_codigo)) {

				// return Array contendo os dados do posto (campos do primeiro parametro), caso nao encontre, gera exceção e vai para o catch
				$posto = $helper->posto->getInfo(array('tbl_posto.posto'), array ('codigo_posto' => $posto_codigo) );

				$cond[] = "AND tbl_auditoria_online.posto = {$posto['posto']}";

			}

		} catch (Exception $e) {
			$msg_erro = $e->getMessage();
		}

	}
	$title = "RELATÓRIO DE AUDITORIAS";
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
	}/*
	.tabela {
		border-collapse: collapse;
	}*/
	.tabela tbody tr td {
		font-family: verdana;
		font-size: 11px;
		border: 1px solid #596d9b !important;
	}
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

	function gravaDados (name, valor){
		try{
			$("input[name="+name+"]").val(valor);
			$(".dadosPosto").show();
		} catch(err){			
			return false;
		}
	}
	
	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
		gravaDados("codigo_posto",codigo_posto);
		gravaDados("posto_nome",nome);
	}
	
</script>

<?php if ( isset($msg_erro) && !empty($msg_erro) ) { ?>

	<div class="msg_erro" style="width:700px;margin:auto; text-align:center;"><?=$msg_erro?></div>

<?php } ?>

<?php if ( isset($msg) ) { ?>

	<div class="sucesso" style="width:700px;margin:auto; text-align:center;"><?=$msg?></div>

<?php } ?>

<div class="formulario" style="width:700px; margin:auto;">
	
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>

	<form action="<?=$PHP_SELF?>" method="POST" name="form" enctype="multipart/form-data">

		<div style="padding:10px;">
			<table style="width:400px;margin:auto; text-align:left; border:none;">
				<tr>
					<td>
						<label for="data_inicial">Data Inicial</label><br />
						<input type="text" name="data_inicial" id="data_inicial" class="frm" size="13" value="<?=isset($_POST['data_inicial'])?$_POST['data_inicial'] : '' ?>" />
					</td>
					<td>
						<label for="data_final">Data Final</label><br />
						<input type="text" name="data_final" id="data_final" class="frm" size="13" value="<?=isset($_POST['data_final'])?$_POST['data_final'] : '' ?>" />
					</td>
				</tr>
				<tr>
					<td>
						Código do Posto<br />
						<input class="frm" type="text" id="posto_codigo" name="posto_codigo" size="15" value="<?=isset($_POST['posto_codigo'])?$_POST['posto_codigo'] : '' ?>">
						<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.form.posto_codigo, 'codigo');">
					</td>
					<td>
						Nome do Posto<br />
						<input class="frm" id="posto_nome" type="text" name="posto_nome" size="25" value="<?=isset($_POST['posto_nome'])?$_POST['posto_nome'] : '' ?>">
						<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.form.posto_nome, 'nome');">
					</td>
				</tr>
				<tr>
					<td>
						<label for="estado">Estado</label><br />
						<select name="estado" id="estado" class="frm">
							<option value="">Selecione</option>
							<?php 
								foreach($helper->estados as $k => $v) : 
									$selected = $_POST['estado'] == $k ? 'selected' : '';
							?>
								<option value="<?=$k?>" <?=$selected?>><?=$v?></option>
							<?php endforeach; ?>
						</select>
					</td>
					<td>
						<label for="inspetor">Inspetor</label><br />
						<select name="inspetor" id="inspetor" class="frm">
							<option value=""></option>
							<?php
 								$sql = "SELECT admin, nome_completo
 										FROM tbl_admin
 										WHERE fabrica = $login_fabrica
 										AND ativo = TRUE
 										AND privilegios LIKE '%inspetor%'";
 								$res = pg_query($con, $sql);
 								for ($i=0; $i < pg_num_rows($res); $i++) { 
 									
 									$admin 	= pg_result($res, $i, 'admin');
 									$nome  	= pg_result($res, $i, 'nome_completo');

 									$selected = $_POST['inspetor'] == $admin ? 'selected' : '';

 									echo "<option value=\"{$admin}\" {$selected}>{$nome}</option>";

 								}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<input type="submit"name="enviar" value="Pesquisar" />
					</td>
				</tr>
			</table>
		</div>

	</form>

</div>

<?php if (isset($_POST['enviar']) && empty($msg_erro)) : ?>

	<?php
		try {

			$sql = "SELECT 
					tbl_auditoria_online.auditoria_online, 
					TO_CHAR(tbl_auditoria_online.data_digitacao, 'dd/mm/YYYY HH24:MI') AS data_digitacao,
					tbl_admin.nome_completo,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.contato_cidade,
					tbl_posto_fabrica.contato_estado,
					tbl_comunicado_posto_blackedecker.leitor
					FROM tbl_auditoria_online
					JOIN tbl_admin USING(admin)
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = tbl_auditoria_online.fabrica AND tbl_posto_fabrica.posto = tbl_auditoria_online.posto
					JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
					LEFT JOIN tbl_auditoria_online_comunicado USING(auditoria_online)
					LEFT JOIN tbl_comunicado USING(comunicado)
					LEFT JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
					WHERE tbl_auditoria_online.fabrica = $login_fabrica
					" . (implode(' ', $cond)) . ' ORDER BY tbl_posto.nome, tbl_admin.nome_completo';

			//echo nl2br($sql); exit;
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) == 0) {

				throw new Exception("Nenhum resultado encontrado");

			}

			$file = fopen("xls/relatorio_auditoria_online_$login_fabrica.html", 'w+');

			echo $content = 
				'<table class="tabela" cellpadding="0" cellspacing="1" style="min-width:700px; margin:10px auto;">
					<thead>
						<tr class="titulo_coluna">
							<th>Data</th>
							<th>Inspetor</th>
							<th>Posto</th>
							<th>Cidade</th>
							<th>Estado</th>
							<th>Responsável</th>';

			fwrite($file, $content);

			echo '			<th>Ações</th>';
						
			echo $content = '
						</tr>
					</thead>
					<tbody>';

			fwrite($file, $content);

			for ($i = 0; $i < pg_num_rows($res); $i++) {

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				$auditoria = pg_result($res, $i, 'auditoria_online');

				echo $content = '<tr bgcolor="'.$cor.'">
						<td>' . pg_result($res, $i, 'data_digitacao') . '</td>
						<td>' . pg_result($res, $i, 'nome_completo') . '</td>
						<td>' . pg_result($res, $i, 'codigo_posto') . ' - ' . pg_result($res, $i, 'nome') . '</td>
						<td>' . pg_result($res, $i, 'contato_cidade') . '</td>
						<td>' . pg_result($res, $i, 'contato_estado') . '</td>
						<td>' . pg_result($res, $i, 'leitor') . '&nbsp;</td>';

				fwrite($file, $content);

				echo '	<td>
							<a href="visualiza_auditoria.php?auditoria='.$auditoria.'" class="visualiza">Visualizar</a>
							<a href="visualiza_auditoria.php?auditoria='.$auditoria.'&imprimir=true" class="visualiza">Imprimir</a>
							<a href="?excluir='.$auditoria.'" class="excluir">Excluir</a>
						</td>';

				echo $content = '</tr>';
				fwrite($file, $content);

			}

			echo $content = 
					'</tbody>
				</table>';

			fwrite($file, $content);
			fclose($file);

			system("mv xls/relatorio_auditoria_online_$login_fabrica.html xls/relatorio_auditoria_online_$login_fabrica.xls");

			echo '<p style="width:300px; margin:auto"><button type="button" onclick="window.open(\'xls/relatorio_auditoria_online_'.$login_fabrica.'.xls\')">Download XLS</button></p>';

		} catch(Exception $e) {

			echo $e->getMessage();

		}
	?>

<?php endif; ?>

<script type="text/javascript">

	$(function() {

		Shadowbox.init();
		
		$( "#data_inicial" ).maskedinput("99/99/9999");
		$( "#data_final" ).maskedinput("99/99/9999");

		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});

		$(".visualiza").click(function(e) {
			window.open( $(this).attr('href'), '', 'width=800,height=600');
			e.preventDefault();
		});

		$('.excluir').click(function(e) {
			link = $(this).attr('href');
			if (confirm("Deseja mesmo excluir essa auditoria?")) {

				$.ajax({
				  url: link,
				  type: 'GET',
				  dataType: 'html',
				  complete: function(xhr, textStatus) {
				    
				  },
				  success: function(data, textStatus, xhr) {
				    alert('Excluido com sucesso');
				  },
				  error: function(xhr, textStatus, errorThrown) {
				    alert('Falha ao excluir auditoria');
				  }
				});
				

			}

			e.preventDefault();

		});

	});
</script>

<?php include 'rodape.php'; ?>