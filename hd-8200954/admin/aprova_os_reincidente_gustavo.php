<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($cook_cliente_admin) {#HD 253575 - INICIO

	$admin_privilegios = "call_center";
	$layout_menu       = "callcenter";

} else {

	$admin_privilegios = "auditoria";

}#HD 253575 - FIM

include "autentica_admin.php";
include 'funcoes.php';

$sql_tipo  = "67,68,70,134";
$aprovacao = "67,68,70";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

if (isset($_GET["q"])) {

	$tipo_busca = $_GET["busca"];

	if (strlen($q) > 2) {

		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo") {
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		} else {
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);

		if (pg_numrows ($res) > 0) {

			for ($i = 0; $i < pg_numrows($res); $i++) {
				$cnpj         = trim(pg_result($res, $i, 'cnpj'));
				$nome         = trim(pg_result($res, $i, 'nome'));
				$codigo_posto = trim(pg_result($res, $i, 'codigo_posto'));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}

		}

	}

	exit;

}

$os   = $_GET["os"];
$tipo = $_GET["tipo"];

$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);

if (strlen($btn_acao) > 0 AND strlen($select_acao) > 0) {

	$qtde_os    = trim($_POST["qtde_os"]);
	$observacao = trim($_POST["observacao"]);

	if ($select_acao == "13" AND strlen($observacao) == 0) {
		$observacao = "OS recusada pelo fabricante";
	} else if (strlen($observacao) > 0) {
		$observacao = " Motivo: $observacao ";
	}
	
	if ($login_fabrica == 14) {

		if ($select_acao == '163') {
			$observacao = $_POST['motivo_recusa'];
		}

	}

	if ($select_acao == "19" AND strlen($observacao) == 0) {
		$observacao = "OS aprovada pelo fabricante";
	} else if (strlen($observacao) > 0) {
		$observacao = " Motivo: $observacao ";
	}

	if (strlen($qtde_os) == 0) {
		$qtde_os = 0;
	}

	for ($x = 0; $x < $qtde_os; $x++) {

		$xxos = trim($_POST["check_".$x]);

		if ($login_fabrica == 52 && strlen($xxos) > 0) {

			$sql_posto = "SELECT tbl_posto_fabrica.contato_email as email, tbl_posto_fabrica.posto FROM tbl_os JOIN tbl_posto_fabrica USING(posto, fabrica) WHERE os = $xxos;";
			$res_posto = @pg_exec($con, $sql_posto);

			if (@pg_numrows($res_posto) > 0) {

				$posto           = trim(pg_result($res_posto, 0, 'posto'));
				$remetente_email = trim(pg_result($res_posto, 0, 'email'));

			} else {

				$msg_erro = 'Erro ao buscar dados do posto!';

			}

		}

		if (strlen($xxos) > 0 AND strlen($msg_erro) == 0) {

			$res_os = pg_exec($con, "BEGIN TRANSACTION");

			switch ($login_fabrica) {
				case ($login_fabrica == 14 or $login_fabrica == 11 or $login_fabrica == 24 or $login_fabrica >= 90) : $sql_tipo = "67,68,70";
				break;
				case 52: $sql_tipo = "67,70,134";
				break;
			}

			$sql    = "SELECT status_os FROM tbl_os_status WHERE status_os IN ($sql_tipo) AND os = $xxos ORDER BY data DESC LIMIT 1";
			$res_os = pg_exec($con, $sql);

			if (pg_numrows($res_os) > 0) {

				$status_da_os = trim(pg_result($res_os, 0, 'status_os'));

				if ($status_da_os == 67 or $status_da_os == 68 or $status_da_os == 70) {
				
					if ($select_acao == "00") {

						$sql = "INSERT INTO tbl_os_status (os, status_os, data, observacao, admin)
								VALUES ($xxos,19,current_timestamp,'OS aprovada pelo fabricante na auditoria de OS reincidente',$login_admin)";

						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);

					} else {

						if ($login_fabrica == 11) {

							$sql = "INSERT INTO tbl_os_status (
											os        ,
											status_os ,
											observacao,
											admin,
											status_os_troca
										) VALUES (
											'$xxos'      ,
											'13'         ,
											'$observacao',
											$login_admin,
											'f'
										);";

							$res = pg_query ($con,$sql);

						} else if ($login_fabrica == 24 || $login_fabrica == 90) {

								$sql = "INSERT INTO tbl_os_status (os, status_os, data, observacao, admin)
										VALUES ($xxos,15,current_timestamp,'$observacao	- Os excluída pelo fabricante em Reincidência',$login_admin)";

								$res       = pg_exec($con, $sql);
								$msg_erro .= pg_errormessage($con);

								$sql = "UPDATE tbl_os set excluida = 't' where os = $xxos";
								$res = pg_exec($con,$sql);
								
								$sql = "SELECT fn_os_excluida($xxos, $login_fabrica, $login_admin)";
								$res = pg_exec($con, $sql);

								#158147 Paulo/Waldir desmarcar se for reincidente
								$sql = "SELECT fn_os_excluida_reincidente($xxos, $login_fabrica)";
								$res = pg_exec($con, $sql);

						} else {

							$sql_motivo = "SELECT motivo, status_os from tbl_motivo_recusa where motivo_recusa = $select_acao";
							$res_motivo = pg_query($con, $sql_motivo);

							if (pg_num_rows($res_motivo) > 0) {

								if ($select_acao <> '163') {
									$motivo = pg_result($res_motivo, 0, 'motivo');
								} else {
									$motivo = $observacao;
								}

								$status_os = pg_result($res_motivo, 0, 'status_os');
							
							}

							if ($status_os == 13 || (in_array($login_fabrica,array(52,94)) && $select_acao == 13)) {

								$sql = "INSERT INTO tbl_os_status (os, status_os, data, observacao, admin)
										VALUES ($xxos, 131, current_timestamp, '$motivo - Os recusada pelo fabricante', $login_admin)";

								$res       = pg_exec($con, $sql);
								$msg_erro .= pg_errormessage($con);

								if ($login_fabrica <> 94) {

									$sql = "UPDATE tbl_os set finalizada = null, data_fechamento = null where os = $xxos";
									$res = pg_exec($con, $sql);

								}

								if ($login_fabrica == 52) {//HD 676626

									$assunto  = 'A O.S '.$xxos.' FOI RECUSADA PELA AUDITORIA DE REINCIDÊNCIA.';
									$mensagem = 'A O.S de Número '.$xxos.', foi recusada por apresentar irregularidades no seu preenchimento.';

									$header   = 'MIME-Version: 1.0' . "\r\n";
									$header  .= 'FROM: helpdesk@telecontrol.com.br' . "\r\n";
									$header  .= 'Content-type: text/html; charset=utf-8' . "\r\n";

									$sql = "INSERT INTO tbl_comunicado( mensagem ,
																		descricao ,
																		tipo ,
																		fabrica ,
																		obrigatorio_site ,
																		posto ,
																		pais ,
																		ativo ,
																		remetente_email
															) VALUES ( 	'$mensagem' ,
																		'$assunto' ,
																		'Comunicado' ,
																		$login_fabrica ,
																		't' ,
																		$posto ,
																		'BR' ,
																		't' ,
																		'$remetente_email'
															);";

									$res = pg_exec($con, $sql);

									@mail($remetente_email, $assunto, $mensagem, $header);

								}


							}

							if ($status_os == "15") {

								$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao,admin)
										VALUES ($xxos, 136, current_timestamp, '$motivo - Os excluída pelo fabricante em Reincidência', $login_admin)";

								$res       = pg_exec($con, $sql);
								$msg_erro .= pg_errormessage($con);

								$sql = "UPDATE tbl_os set excluida = 't' where os = $xxos";
								$res = pg_exec($con, $sql);

								#158147 Paulo/Waldir desmarcar se for reincidente
								$sql = "SELECT fn_os_excluida_reincidente($xxos, $login_fabrica)";
								$res = pg_exec($con, $sql);

							}

						}

					}

				}

				if ($status_da_os == 134) {

					if ($select_acao == "00") {

						$sql = "INSERT INTO tbl_os_status (os, status_os, data, observacao, admin)
								VALUES ($xxos, 135, current_timestamp, 'OS aprovada pelo fabricante na auditoria de OS reincidente de peças e serviço', $login_admin)";

						$res       = pg_exec($con, $sql);
						$msg_erro .= pg_errormessage($con);

					} else {

						$sql       = "SELECT motivo, status_os from tbl_motivo_recusa where motivo_recusa = $select_acao";
						$res       = pg_exec($con, $sql);
						$motivo    = pg_result($res, 0, 'motivo');
						$status_os = pg_result($res, 0, 'status_os');

						if ($status_os == "13") {

							$sql = "INSERT INTO tbl_os_status(os, status_os, data, observacao, admin)
									VALUES ($xxos, 13, current_timestamp, '$motivo - Os recusada pelo fabricante', $login_admin)";

							$res       = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$sql = "UPDATE tbl_os set finalizada = null, data_fechamento = null where os = $xxos";
							$res = pg_exec($con,$sql);

							if ($login_fabrica == 52) {//HD 676626

								$assunto  = 'A O.S '.$xxos.' FOI RECUSADA PELA AUDITORIA DE REINCIDÊNCIA.';
								$mensagem = 'A O.S de Número '.$xxos.', foi recusada por apresentar irregularidades no seu preenchimento.';

								$header   = 'MIME-Version: 1.0' . "\r\n";
								$header  .= 'FROM: helpdesk@telecontrol.com.br' . "\r\n";
								$header  .= 'Content-type: text/html; charset=utf-8' . "\r\n";

								$sql = "INSERT INTO tbl_comunicado( mensagem ,
																	descricao ,
																	tipo ,
																	fabrica ,
																	obrigatorio_site ,
																	posto ,
																	pais ,
																	ativo ,
																	remetente_email
														) VALUES ( 	'$mensagem' ,
																	'$assunto' ,
																	'Comunicado' ,
																	$login_fabrica ,
																	't' ,
																	$posto ,
																	'BR' ,
																	't' ,
																	'$remetente_email'
														);";

								$res = pg_exec($con, $sql);

								@mail($remetente_email, $assunto, $mensagem, $header);

							}

						}

						if ($status_os == "15") {

							$sql = "INSERT INTO tbl_os_status (os, status_os, data, observacao, admin)
									VALUES ($xxos, 136, current_timestamp, '$motivo - Os excluída pelo fabricante em Reincidência', $login_admin)";

							$res       = pg_exec($con, $sql);
							$msg_erro .= pg_errormessage($con);

							$sql = "UPDATE tbl_os set excluida = 't' where os = $xxos";
							$res = pg_exec($con,$sql);

							#158147 Paulo/Waldir desmarcar se for reincidente
							$sql = "SELECT fn_os_excluida_reincidente($xxos,$login_fabrica)";
							$res = pg_exec($con, $sql);

						}

					}

				}

			}

			if (strlen($msg_erro) == 0) {
				$res = pg_exec($con,"COMMIT TRANSACTION");
			} else {
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}

		}

	}

}

if ($login_fabrica == 52) {
	
	if ($cook_cliente_admin) {

		$layout_menu = "callcenter";
		$title       = "AUDITORIA DE OS REINCIDENTES";

	} else {

		$layout_menu = "auditoria";
		$title       = "AUDITORIA DE OS REINCIDENTES";

	}

} else {

	$layout_menu = "auditoria";
	$title       = "AUDITORIA DE OS REINCIDENTES";

}

include "cabecalho.php"; ?>

<style type="text/css" media="screen">
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.espaco{
		padding-left:120px;
	}
	.subtitulo{
		background-color: #7092BE;
		font:bold 14px Arial;
		color: #FFFFFF;
	}
	.texto_avulso{
		font: 14px Arial; color: rgb(89, 109, 155);
		background-color: #d9e2ef;
		text-align: center;
		width:700px;
		margin: 0 auto;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	.titulo_coluna {
		background-color: #596D9B;
		color: #FFFFFF;
		font: bold 11px "Arial";
		text-align: center;
	}
	/*ELEMENTOS DE POSICIONAMENTO*/
	#container {
	  border: 0px;
	  padding:0px 0px 0px 0px;
	  margin:0px 0px 0px 0px;
	  background-color: white;
	}
	#tooltip{  
		background: #FF9999;
		border:2px solid #000;
		display:none;
		padding: 2px 4px;
		color: #003399;
	}
</style>

<script language="JavaScript" type="text/javascript">

	window.onload = function(){
		tooltip.init();
	}

	function fnc_pesquisa_posto2 (campo, campo2, tipo) {

		if (tipo == "codigo" ) {
			var xcampo = campo;
		}

		if (tipo == "nome" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {

			var url = "";

			url = "posto_pesquisa_2.php?campo="+xcampo.value+"&tipo="+tipo+"&os=t";
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela.codigo  = campo;
			janela.nome    = campo2;

			if ("<? echo $pedir_sua_os; ?>" == "t") {
				janela.proximo = document.frm_consulta.sua_os;
			} else {
				janela.proximo = document.frm_consulta.data_abertura;
			}

			janela.focus();

		} else {
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
		}

	}

	var ok   = false;
	var cont = 0;

	function checkaTodos() {

		f = document.frm_pesquisa2;

		if (!ok) {

			for (i = 0; i < f.length; i++) {

				if (f.elements[i].type == "checkbox") {

					f.elements[i].checked = true;
					ok = true;

					if (document.getElementById('linha_'+cont)) {
						document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
					}

					cont++;
				}

			}

		} else {

			for (i = 0; i < f.length; i++) {

				if (f.elements[i].type == "checkbox") {

					f.elements[i].checked = false;
					ok = false;

					if (document.getElementById('linha_'+cont)) {
						document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
					}

					cont++;

				}

			}

		}

	}

	function setCheck(theCheckbox,mudarcor,cor) {

		if (document.getElementById(mudarcor)) {
			document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
		}

	}
</script>

<?php include "javascript_calendario.php";?>

<script type="text/javascript" charset="utf-8">

	$(function() {

		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
		
		$('.radio_btn').click(function() {
			
			valor_radio = $(this).val();
			
			if (valor_radio == 'reincidentes_cinco_dias') {
				$('#td_datas1').slideUp('fast');
				$('#td_datas2').slideUp('fast');				
			} else {
				$('#td_datas1').slideDown('fast');
				$('#td_datas2').slideDown('fast');
			}
					
		});
		
	});
	
	
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type="text/javascript" src='ajax.js'></script>
<script type="text/javascript" src="../js/bibliotecaAJAX.js"></script>

<script language="JavaScript">

	$().ready(function() {

		function formatItem(row) {
			return row[2] + " - " + row[1];
		}

		function formatResult(row) {
			return row[2];
		}

		/* Busca pelo Código */
		$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[2];}
		});

		$("#posto_codigo").result(function(event, data, formatted) {
			$("#posto_nome").val(data[1]) ;
		});

		/* Busca pelo Nome */
		$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#posto_nome").result(function(event, data, formatted) {
			$("#posto_codigo").val(data[2]) ;
		});

	});

	function abreInteracao(linha, os, tipo, posto) {

		var div  = document.getElementById('interacao_'+linha);
		var os   = os;
		var tipo = tipo;

		$.ajax({

			url: 'ajax_grava_interacao.php?linha='+linha+'&os='+os+'&tipo='+tipo+'&posto='+posto,
			type: 'GET',
			success: function(campos) {

				campos_array   = campos.split("|");
				resposta       = campos_array[0];
				linha          = campos_array[1];

				var div        = document.getElementById('interacao_'+linha);
				div.innerHTML  = resposta;

				var comentario = document.getElementById('comentario_'+linha);
				comentario.focus();

			}

		});
		
	}

	function div_detalhe_carrega2 (campos) {

		campos_array   = campos.split("|");
		resposta       = campos_array [0];
		linha          = campos_array [1];

		var div        = document.getElementById('interacao_'+linha);
		div.innerHTML  = resposta;

		var comentario = document.getElementById('comentario_'+linha);
		comentario.focus();

	}

	function gravarInteracao(linha, os, tipo, posto, email) {
		
		var comentario = document.getElementById('comentario_'+linha).value;

		$.ajax({

			url: 'ajax_grava_interacao.php?linha='+linha+'&os='+os+'&comentario='+comentario+'&tipo='+tipo+'&posto='+posto+'&email='+email,
			type: 'GET',
			success: function(campos) {

				campos_array = campos.split("|");
				resposta     = campos_array[1];
				linha        = campos_array[2];
				os           = campos_array[3];

				if (resposta == 'ok') {

					document.getElementById('interacao_' + linha).innerHTML     = "Gravado Com sucesso!!!";
					document.getElementById('btn_interacao_' + linha).innerHTML = "<input type='button' value='Interagir' onclick='abreInteracao("+linha+","+os+",\"Mostrar\","+posto+" )'>";
					var table = document.getElementById('linha_'+linha);
					table.style.background = "#FFCC00";
				
				} else {

					alert('Erro ao gravar Registro!');

				}

			}

		});


	}

	function div_detalhe_carrega(campos) {

		campos_array = campos.split("|");
		resposta     = campos_array [1];
		linha        = campos_array [2];
		os           = campos_array [3];

		if (resposta == 'ok') {

			document.getElementById('interacao_' + linha).innerHTML     = "Gravado Com sucesso!!!";
			document.getElementById('btn_interacao_' + linha).innerHTML = "<input type='button' value='Interagir' onclick='abreInteracao("+linha+","+os+",\"Mostrar\")'>";
			var table = document.getElementById('linha_'+linha);
			table.style.background = "#FFCC00";
		
		} else {

			alert('Erro ao gravar Registro!');

		}

	}

	function recusaFabricante() {

		var motivo = prompt("Qual o Motivo da Recusa da(s) OS(s)  ?",'',"Motivo da Recusa");

		if (motivo != null && $.trim(motivo) !="" && motivo.length > 0) {
				document.getElementById('motivo_recusa').value = motivo;
				document.frm_pesquisa2.submit();
		} else {
			alert('Digite um motivo por favor!','Erro');
		}

	}

	function fnc_revenda_pesquisa (campo, tipo) {

		if (campo.value != "") {

			var url = "";
			url = "cliente_admin_pesquisa.php?forma=reduzida&campo=" + campo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
			
			janela.nome	= campo;
			janela.cliente_admin = document.frm_consulta.cliente_admin;
			janela.focus();

		} else {
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
		}

	}

</script><?php

include "javascript_pesquisas.php";

if ($btn_acao == 'Pesquisar') {

	$data_inicial 	= trim($_POST['data_inicial']);
	$data_final   	= trim($_POST['data_final']);
	$aprova       	= trim($_POST['aprova']);
	$os           	= trim($_POST['os']);
	$status_os    	= trim($_POST['status_os']);
	
	if ($login_fabrica == 52) {
		$cliente_admin	= trim($_POST['cliente_admin']);
	}

	if (strlen($os) > 0) {
		$Xos = " AND tbl_os.sua_os = '$os' ";
	}

	if (strlen($aprova) == 0) {
	
		$aprova = "aprovacao";

		switch($login_fabrica) {

			case ($login_fabrica == 14 or $login_fabrica == 11 or $login_fabrica == 90):
				$aprovacao = "67, 68, 70";
			break;
			case 52:
				$aprovacao = "67, 134";
			break;
			case 94:
				$aprovacao = "67, 68, 70";
			break;
			case 24:
				$aprovacao = "67, 68, 70";
			break;

		}

		$sql_add2 = " AND tbl_os.excluida IS NOT TRUE ";
		
	} else if ($aprova == "aprovacao") {
	
		switch ($login_fabrica) {

			case ($login_fabrica == 14 or $login_fabrica == 11  or $login_fabrica == 90):
				$aprovacao = "67, 68, 70";
			break;
			case 52:
				$aprovacao = "67, 70, 134";//HD 676626
			break;
			case 24:
				$aprovacao = "67, 68, 70";
			break;
			case 94:
				$aprovacao = "67, 68, 70";
			break;

		}

		$sql_add2 = " AND tbl_os.excluida IS NOT TRUE";
		
	} else if ($aprova == "aprovadas") {
	
		$aprovacao = "19";
		$sql_add2  = " AND tbl_os_status.extrato IS NULL AND tbl_os.excluida IS NOT TRUE";
		
	} else if($aprova == "reprovadas") {
		
		$aprovacao = ($login_fabrica == 11 or $login_fabrica == 24) ? "13, 15 " : "131";
		$sql_add2  = " AND tbl_os_status.extrato IS NULL ";
		
	} else if ($login_fabrica == 94 and $aprova == 'reincidentes_cinco_dias') {//HD 415029 - gabrielSilveira - 15/06/2011
	
		$aprovacao =  "67, 68, 70";
		
	}

	if (strlen($status_os) > 0) {

		$sql_tipo = $status_os;

	} else {

		switch ($login_fabrica) {

			case ($login_fabrica == 14 or $login_fabrica == 11):
				$sql_tipo = "67, 68, 70, 131, 19, 13";
			break;
			case 52:
				$sql_tipo = "67, 134, 135, 13, 19, 131";
			break;
			case 24:
				$sql_tipo = "67, 68, 70, 131, 19, 13";
			break;
			case 90:
				$sql_tipo = "67, 68, 70, 19";
			break;
			case 94:
				$sql_tipo = "67, 68, 70, 131, 135, 19";
			break;
			case 104:
				$sql_tipo = "67, 68, 70, 134, 19";
			break;

		}

	}
	
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];

	if ($aprova != 'reincidentes_cinco_dias') {//HD 415029 - gabrielSilveira - 15/06/2011
	
		if (empty($data_inicial) OR empty($data_final)) {
		    $msg_erro = "Data Inválida";
		}

		if (strlen($msg_erro) == 0) {

			list($di, $mi, $yi) = explode("/", $data_inicial);
			if (!checkdate($mi,$di,$yi)) $msg_erro = "Data Inválida";

		}

		if (strlen($msg_erro) == 0) {

			list($df, $mf, $yf) = explode("/", $data_final);
			if (!checkdate($mf,$df,$yf)) $msg_erro = "Data Inválida";

		}

		if (strlen($msg_erro) == 0) {

			$aux_data_inicial = "$yi-$mi-$di";
			$aux_data_final   = "$yf-$mf-$df";

		}

		if (strlen($msg_erro) == 0) {

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial) or strtotime($aux_data_inicial) > strtotime('today')) {
				$msg_erro = "Data Inválida.";
			}

		}
	
	}

	$posto_codigo = ($_POST['posto_codigo']) ? $_POST['posto_codigo'] : null;
	$posto_nome   = ($_POST['posto_nome'])   ? $_POST['posto_nome']   : null;
	
}

if (strlen($msg_erro) > 0) {
	echo "<div align='center'><div class='msg_erro' style='width:700px'>$msg_erro</div></div>";
}

//HD 415029 - SETA DISPLAY PARA OS INPUTS DAS DATAS DE ACORDO COM O RADIO SELECIONADO NO GRUPO "Mostrar as OS:"
if ($login_fabrica == 94) {
	
	if (empty($_POST['aprova'])) {

		$display_data = "display:none";

	} else {

		if ($aprova != 'reincidentes_cinco_dias') {

			$display_data = "display:true";

		} else {

			$display_data = "display:none";

		}

	}
	
}?>

<div id="page-container">
<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<table align="center" class="formulario" width="700" border="0">
	<caption class="titulo_tabela">Parâmetros de Pesquisa</caption>
	<tbody>
		<tr>
			<td colspan="2" class="espaco">
				Número da OS
				<br>
				<input type="text" name="os" id="os" size="20" maxlength="20" value="<?php echo $os ?>" class="frm">
			</td>
		</tr>
		<tr>
			<td class="espaco">
				<div id='td_datas1' style="<?php echo $display_data ?>" >
					Data Inicial
					<br>
					<input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm">
				</div>
			</td>
			<td>
				<div id='td_datas2' style="<?php echo $display_data ?>" >
					Data Final
					<br>
					<input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm">
				</div>
			</td>
		</tr>
		<tr class="subtitulo">
			<td colspan="2" align="center">Informações do Posto</td>
		</tr>
		<tr>
			<td class="espaco">
				Código Posto
				<br>
				<input type="text" name="posto_codigo" id="posto_codigo" size="15"  value="<? echo $posto_codigo ?>" class="frm">&nbsp;
				<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código"  onclick="fnc_pesquisa_posto2(document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome, 'codigo')">
			</td>
			<td>
				Nome do Posto
				<br>
				<input type="text" name="posto_nome" id="posto_nome" size="40"  value="<? echo $posto_nome ?>" class="frm">&nbsp;
				<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_posto2(document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome, 'nome')">
			</td>
		</tr><?php

		if ($login_fabrica == 52) {//HD 253575?>

			<tr><td>&nbsp;</td></tr>
			
			<tr class="subtitulo">
				<td colspan="2" align="center">Informações do Cliente Fricon</td>
			</tr>
			
			<tr>
				<td class="espaco">Razão Social</td>
			</tr>
			<tr>
				<td class="espaco" colspan="2">
					<input type="text" class='frm' name="nome" size="40" maxlength="60" value="<? echo $nome ?>" style="width:300px">
					<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_revenda_pesquisa (document.frm_consulta.nome,'nome')">
					<input type="hidden" name="cliente_admin" value="<? echo $cliente_admin ?>">
				</td>
			</tr>
			
			<tr><td>&nbsp;</td></tr>

		<?}?>
		
		<tr class="subtitulo">
			<td colspan="2" align="center">Informações da OS</td>
		</tr><?php

		if ($login_fabrica <> 94) {//HD 415029 - 14/06/2011 - gabriel?>
			<tr>
				<td colspan="2" class="espaco">
					Status
					<br>
					<select class='frm' name='status_os'>
						<option> </option><?php 
						$sql = "SELECT * FROM tbl_status_os WHERE status_os IN(67, 68,70,131,19,13)";
						$res = pg_exec($con,$sql);

						for ($i = 0; $i < pg_numrows($res); $i++) {
							$status_os_x = pg_result($res, $i, 'status_os');
							$descricao   = pg_result($res, $i, 'descricao'); ?>
							<option value="<? echo $status_os_x;?>" <? if ($status_os == $status_os_x) echo "SELECTED";?>><?php echo $descricao;?></option><?php
						}?>
					</select>
				</td>
			</tr><?php
		}?>

		<tr>
			<td colspan='2' class="espaco">
				<fieldset style="width:400px;">
					<legend>Mostrar as OS:</legend>
					<p>
						<input type="radio" class='radio_btn' style='cursor:pointer' name="aprova" id='aprovacao' value='aprovacao' <? if ((trim($aprova) == 'aprovacao' OR trim($aprova) == 0) || $login_fabrica != 94) echo "checked='checked'"; ?>>
						<label for="aprovacao" style="cursor:pointer">Em aprovação</label>
					</p>
		
					<p>
						<input type="radio" class='radio_btn' style='cursor:pointer' name="aprova" id='aprovadas' value='aprovadas' <? if (trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>
						<label for="aprovadas" style="cursor:pointer">Aprovadas</label>
					</p><?php

					if ($login_fabrica != 90) {?>
						<p>	
							<label>
								<input type="radio" class='radio_btn' style='cursor:pointer' name="aprova" value='reprovadas' <? if (trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>Reprovadas &nbsp;&nbsp;&nbsp;
							</label>
							
						</p><?php
					}
					
					if ($login_fabrica == 94) {?>
						<p>
							<input type="radio" class='radio_btn' style='cursor:pointer' name="aprova" id='reincidentes_cinco_dias' value='reincidentes_cinco_dias' <? if (trim($aprova) == 'reincidentes_cinco_dias' || ($login_fabrica == 94 && empty($aprova) ) ) echo "checked='checked'"; ?> >
							<label for="reincidentes_cinco_dias" style='cursor:pointer'>OS que entraram em reincidências nos últimos 5 dias</label>
						</p><?php
					}

					if ($login_fabrica == 11) {?>

						<p>	
							<input type="checkbox" class='radio_btn' style='cursor:pointer' name="check_data_fechamento" id="check_data_fechamento" value='t' <? if (trim($check_data_fechamento) == 't') echo "checked='checked'"; ?> />
							<label for="check_data_fechamento" style='cursor:pointer'>Com Data de Fechamento</label>
						</p>

						<p>	
							<input type="checkbox" class='radio_btn' style='cursor:pointer' name="check_defeito_constatado" id="check_defeito_constatado" value='t' <? if (trim($check_defeito_constatado) == 't') echo "checked='checked'"; ?> />
							<label for="check_defeito_constatado" style='cursor:pointer'>Com Defeito Constatado</label>
						</p><?php

					}?>
					
				</fieldset>
			</td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="2" align="center">
				<br>
				<input type='hidden' name='btn_acao' value=''>
				<input type='button' onclick="javascript: if ( document.frm_consulta.btn_acao.value == '' ) { document.frm_consulta.btn_acao.value='Pesquisar'; document.frm_consulta.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " value='Pesquisar'>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center">&nbsp;</td>
		</tr>
	</tfoot>
</table>
</form><?php

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {

	$posto_codigo = trim($_POST["posto_codigo"]);

	if (strlen($posto_codigo) > 0) {

		$sql = "SELECT posto FROM tbl_posto_fabrica where codigo_posto = '$posto_codigo' and fabrica = $login_fabrica";
		$res = pg_exec($con, $sql);

		$posto = pg_result($res, 0, 0);

		$sql_add .= " AND tbl_os.posto = '$posto' ";

	}

	if ($login_fabrica == 11) {

		$sql_data = " AND tbl_os.data_digitacao > '2009-10-01 00:00:00' ";

		if (!empty($check_defeito_constatado)) {
			$sql_defeito_constatado = " AND tbl_os.defeito_constatado IS NOT NULL";
		}

		if (!empty($check_data_fechamento)) {
			$sql_data_fechamento = " AND  tbl_os.data_fechamento IS NOT NULL";
		}

	}

	if (strlen($aux_data_inicial) > 0 AND strlen($aux_data_final) > 0) {
	
		$sql_data2 .= " AND tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
			
	} else {
	
		if ($aprova == 'reincidentes_cinco_dias') {//HD 415029
			
			$sql = "select current_timestamp::date - 5 as data;";
			$res = pg_query($con, $sql);
			
			$data_cinco_dias = pg_result($res, 0, 0);

			$sql_data2     .= " AND tbl_os_status.data >= '$data_cinco_dias 00:00:00' ";
			$join_os_status = " JOIN tbl_os_status on (tbl_os.os = tbl_os_status.os and tbl_os.fabrica = tbl_os_status.fabrica_status) ";

		}
	
	}

	$sql = "SELECT interv.os
			INTO TEMP tmp_interv_$login_admin
			FROM (
				SELECT	ultima.os,
						(	
							SELECT status_os 
							FROM tbl_os_status
							JOIN tbl_os USING(os)
							WHERE status_os IN ($sql_tipo) 
							AND tbl_os_status.os = ultima.os 
							AND tbl_os_status.fabrica_status = tbl_os.fabrica
							AND tbl_os.fabrica = $login_fabrica
							AND tbl_os.os_reincidente IS TRUE
							AND tbl_os_status.extrato IS NULL
							$sql_add2
							$sql_add
							$sql_data
							$sql_data2
							$sql_data_fechamento
							$sql_defeito_constatado
							$Xos
							ORDER BY os_status DESC 
							LIMIT 1
						) AS ultimo_status
				FROM (
						SELECT DISTINCT os 
						FROM tbl_os_status 
						JOIN tbl_os USING(os)
						WHERE status_os IN ($sql_tipo) 
						AND tbl_os_status.fabrica_status = tbl_os.fabrica
						AND tbl_os.fabrica = $login_fabrica
						AND tbl_os.os_reincidente IS TRUE
						$sql_add
						$sql_data
						$sql_data2
						$sql_data_fechamento
						$sql_defeito_constatado
						$Xos
				) ultima
			) interv
			WHERE interv.ultimo_status IN ($aprovacao);

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

			SELECT	tbl_os.os                                                   ,
					tbl_os.serie                                                ,
					tbl_os.sua_os                                               ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.revenda_nome                                         ,
					tbl_os.consumidor_revenda                                   ,
					tbl_os.consumidor_fone                                      ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					tbl_os.nota_fiscal                                          ,
					TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')              AS data_nf,
					tbl_os.fabrica                                              ,
					tbl_os.posto                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_email       AS posto_email        ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_produto.voltagem                                        ,
					tbl_os_extra.os_reincidente                                 ,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) AND tbl_os_status.extrato IS NULL ORDER BY data DESC LIMIT 1) AS status_os         ,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) AND tbl_os_status.extrato IS NULL ORDER BY data DESC LIMIT 1) AS status_observacao,
					(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) AND tbl_os_status.extrato IS NULL ORDER BY data DESC LIMIT 1) AS status_descricao,
					tbl_os.obs_reincidencia                                    ,
					(SELECT descricao FROM tbl_defeito_constatado where tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado) as defeito_constatado,
					(SELECT descricao FROM tbl_defeito_reclamado where tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado) as defeito_reclamado";
				
	if ($login_fabrica == 52) {#HD 253575 - INICIO

		$sql .= " , tbl_cliente_admin.nome            AS cliente_admin        ,
				tbl_cliente_admin.codigo              AS codigo_cliente_admin ,
				tbl_motivo_reincidencia.descricao     AS motivo_reincidencia  ";

	}#HD 253575 - FIM
			
	$sql .= " FROM tmp_interv_$login_admin X
				JOIN tbl_os            ON tbl_os.os           = X.os
				JOIN tbl_os_extra      ON tbl_os.os           = tbl_os_extra.os 
				$join_os_status 
				JOIN tbl_produto       ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_posto         ON tbl_os.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_os.posto        = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
				
	if ($login_fabrica == 52) { #HD 253575 - INICIO

		$sql .= " LEFT JOIN tbl_cliente_admin       ON (tbl_os.cliente_admin = tbl_cliente_admin.cliente_admin)
					LEFT JOIN tbl_motivo_reincidencia ON (tbl_os.motivo_reincidencia = tbl_motivo_reincidencia.motivo_reincidencia and tbl_os.fabrica = tbl_motivo_reincidencia.fabrica) ";

	}#HD 253575 - FIM
	 
	$sql .= " WHERE tbl_os.fabrica = $login_fabrica ";
		
	if ($login_fabrica <> 24) {
		$sql .=	" AND  tbl_os_extra.extrato IS NULL ";
	}
	
	if ($login_fabrica == 14) {
		$sql .= " AND  tbl_os.data_fechamento IS NOT NULL";
	}

	if ($login_fabrica == 52 and $cook_cliente_admin) {//HD 253575 - inicio
		$sql .= " AND tbl_os.cliente_admin = $cook_cliente_admin";
	}
	
	if ($login_fabrica == 52 and $_POST['cliente_admin']) {
		$sql .= " AND tbl_os.cliente_admin = $cliente_admin " ;	
	}//HD 253575 - fim
				
	$sql .= " $sql_add 
			  $sql_data  
			  $sql_data2 ";
		
	$sql .= " GROUP BY tbl_os.os, 
						tbl_os.serie ,
						tbl_os.sua_os ,
						tbl_os.consumidor_nome ,
						tbl_os.revenda_nome ,
						tbl_os.consumidor_revenda ,
						tbl_os.consumidor_fone ,
						data_abertura,
						data_fechamento,
						data_digitacao,
						tbl_os.nota_fiscal ,
						data_nf,
						tbl_os.fabrica ,
						tbl_os.posto ,
						tbl_os.consumidor_nome ,
						posto_nome ,
						tbl_posto_fabrica.codigo_posto ,
						posto_email ,
						produto_referencia ,
						produto_descricao ,
						tbl_produto.voltagem ,
						tbl_os_extra.os_reincidente ,
						tbl_os.obs_reincidencia, 
						tbl_os.defeito_constatado,
						tbl_os.defeito_reclamado "; 

	if ($login_fabrica == 52) {#HD 253575 - INICIO

		$sql .= " , tbl_cliente_admin.nome             ,
					tbl_cliente_admin.codigo           ,
					tbl_motivo_reincidencia.descricao  ";

	}#HD 253575 - FIM

	$sql .= " ORDER BY tbl_posto.nome, status_observacao, tbl_os.os";
	echo nl2br($sql);
	$res  = pg_query($con,$sql);

	if (pg_numrows($res) > 0) {

		if ($login_fabrica == 52) {#HD 253575 -  XLS PARA FRICON - INICIO
			
			$data = date("d-m-Y-H-i");

			$arquivo_nome_c = "os_reincidentes-$login_fabrica-$data.xls";
			$path           = "/www/assist/www/admin/xls/";
			$path_tmp       = "/tmp/assist/";
			
			if (!is_dir($path_tmp)) {//HD 676626
				mkdir($path_tmp);
				chmod($path_tmp, 0777);
			}

			$arquivo_completo     = $path.$arquivo_nome_c;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome_c;
			
			echo `rm $arquivo_completo.zip `;
			echo `rm $arquivo_completo `; 
			echo `rm $arquivo_completo_tmp `;
			echo `rm $arquivo_completo_tmp.zip `;
			
			$fp = fopen ($arquivo_completo_tmp,"w+");
			
			fputs ($fp,"<html>");
				fputs ($fp,"<head>");
				
					fputs ($fp,"<title>OS's REINCIDENTES</title>");
					fputs ($fp,"<meta content=\"text/html; charset=iso-8859-1\">");
				
				fputs ($fp,"</head>");
				
				fputs ($fp,"<body>");
				
					fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='1'>");
					
						fputs ($fp,"<tr>");
						
							fputs ($fp,"<TD align='center'><b>&nbsp</b></TD>");
							fputs ($fp,"<TD align='center'><b>OS</b></TD>");
							fputs ($fp,"<TD align='center'><b>Série</b></TD>");
							fputs ($fp,"<TD align='center'><b>Data Abertura</b></TD>");
							fputs ($fp,"<TD align='center'><b>Data Fechamento</b></TD>");
							fputs ($fp,"<TD align='center'><b>Posto</b></TD>");
							fputs ($fp,"<TD align='center'><b>Nota Fiscal</b></TD>");
							fputs ($fp,"<TD align='center'><b>Consumidor</b></TD>");
							fputs ($fp,"<TD align='center'><b>Produto</b></TD>");
							fputs ($fp,"<TD align='center'><b>Defeito Constatado</b></TD>");
							fputs ($fp,"<TD align='center'><b>Status</b></TD>");
							fputs ($fp,"<TD align='center'><b>Admin</b></TD>");
							fputs ($fp,"<TD align='center'><b>Motivo Reincidência</b></TD>");
							fputs ($fp,"<TD align='center'><b>Motivo do Posto</b></TD>");
						
						fputs ($fp,"</tr>");

		}#HD 253575 -  XLS PARA FRICON
		
		echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

		echo "<input type='hidden' name='data_inicial' value='$data_inicial' />";
		echo "<input type='hidden' name='data_final'   value='$data_final' />";
		echo "<input type='hidden' name='aprova'       value='$aprova' />";
		echo "<input type='hidden' name='posto_codigo' value='$posto_codigo' />";
		echo "<input type='hidden' name='posto_nome'   value='$posto_nome' />";
		
		echo '<table align="center" width="1200px" cellspacing="1" class="tabela">';
		$colspan_fricon = ($login_fabrica == 52) ? "15" : "13";
		echo "<tr>";
			echo "<td align='center' style='font:14px Arial;border:0px !important;' colspan='$colspan_fricon'>";
				echo "Este relatório considera a data de digitação da OS";
			echo "</td>";
		echo "</tr>";
		
		echo "<tr class='titulo_tabela'>";

		if ($login_fabrica == 52) { #HD 253575 - INICIO

			if (!$cook_cliente_admin) {
				echo "<td><img border='0' src='imagens_admin/selecione_todas.gif' onclick='checkaTodos()' alt='Selecionar todos' style='cursor:pointer;' align='center'></td>";
			} else {
				echo "<td>&nbsp;</td>";
			}

		} else {
			echo "<td><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor:pointer;' align='center'></td>";
		}#HD 253575 - FIM

		echo "<td>OS</td>";
		echo "<td>Série</td>";
		echo "<td>Data Abertura</td>";
		echo "<td>Data Fechamento</td>";
		echo "<td width='20'>Posto</td>";
		echo "<td>Nota Fiscal</td>";
		echo "<td>Consum.</td>";
		echo "<td>Produto</td>";
		echo "<td>Defeito Constatado</td>";
		echo "<td>Status</td>";
		echo ($login_fabrica == 52) ? " <td> Cliente Fricon </td> <td>Motivo Reincidência</td>" : null;
		echo "<td>Motivo do posto</td>";

		if ($login_fabrica <> 11) {
			
			if ($login_fabrica == 52) {#HD 253575 - INICIO

				if (!$cook_cliente_admin) {
					echo "<td>Interação</td>";
				}

			} else if ($login_fabrica != 94) {

				echo "<td>Interação</td>";

			}#HD 253575 - FIM
			
		}

		echo "</tr>";

		$cores            = '';
		$qtde_intervencao = 0;

		for ($x = 0; $x < pg_numrows($res); $x++) {

			$os						= pg_result($res, $x, 'os');
			$serie					= pg_result($res, $x, 'serie');
			$data_abertura			= pg_result($res, $x, 'data_abertura');
			$data_fechamento		= pg_result($res, $x, 'data_fechamento');
			$sua_os					= pg_result($res, $x, 'sua_os');
			$codigo_posto			= pg_result($res, $x, 'codigo_posto');
			$posto					= pg_result($res, $x, 'posto');
			$posto_nome				= pg_result($res, $x, 'posto_nome');
			$posto_email			= pg_result($res, $x, 'posto_email');
			$nota_fiscal			= pg_result($res, $x, 'nota_fiscal');
			$data_nf				= pg_result($res, $x, 'data_nf');
			$consumidor_nome		= pg_result($res, $x, 'consumidor_nome');
			$consumidor_revenda     = pg_result($res, $x, 'consumidor_revenda');
			$revenda_nome           = pg_result($res, $x, 'revenda_nome');
			$consumidor_fone		= pg_result($res, $x, 'consumidor_fone');
			$produto_referencia		= pg_result($res, $x, 'produto_referencia');
			$produto_descricao		= pg_result($res, $x, 'produto_descricao');
			$produto_voltagem		= pg_result($res, $x, 'voltagem');
			$data_digitacao			= pg_result($res, $x, 'data_digitacao');
			$data_abertura			= pg_result($res, $x, 'data_abertura');
			$status_os				= pg_result($res, $x, 'status_os');
			$status_observacao		= pg_result($res, $x, 'status_observacao');
			$status_descricao		= pg_result($res, $x, 'status_descricao');
			$os_reincidente			= pg_result($res, $x, 'os_reincidente');
			$obs_reincidencia		= pg_result($res, $x, 'obs_reincidencia');
			$defeito_constatado		= pg_result($res, $x, 'defeito_constatado');
			$defeito_reclamado		= pg_result($res, $x, 'defeito_reclamado');
			
			if ($login_fabrica == 52) {#HD 253575 - INICIO

				$cliente_admin 	      = pg_fetch_result($res, $x, 'cliente_admin');
				$codigo_cliente_admin = pg_fetch_result($res, $x, 'codigo_cliente_admin');
				$motivo_reincidencia  = pg_fetch_result($res, $x, 'motivo_reincidencia');
				
			}#HD 253575 - FIM
			
			if (strlen($os_reincidente) > 0) {

				$sql =  "SELECT	tbl_os.os                                                   ,
								tbl_os.serie                                                ,

								tbl_os.sua_os                                               ,
								tbl_os.consumidor_nome                                      ,
								tbl_os.revenda_nome                                         ,
								tbl_os.consumidor_revenda                                   ,
								tbl_os.consumidor_fone                                      ,
								TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
								TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
								TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
								tbl_os.nota_fiscal                                          ,
								TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')              AS data_nf,
								tbl_os.fabrica                                              ,
								tbl_os.consumidor_nome                                      ,
								tbl_posto.nome                     AS posto_nome            ,
								tbl_posto_fabrica.codigo_posto                              ,
								tbl_posto_fabrica.contato_email       AS posto_email        ,
								tbl_produto.referencia             AS produto_referencia    ,
								tbl_produto.descricao              AS produto_descricao     ,
								tbl_produto.voltagem                                        ,
								tbl_os_extra.os_reincidente                                 ,
								(SELECT descricao FROM tbl_defeito_constatado where tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado) as defeito_constatado,
								(SELECT descricao FROM tbl_defeito_reclamado where tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado) as defeito_reclamado
						FROM tbl_os                   
						JOIN tbl_os_extra             ON tbl_os.os = tbl_os_extra.os
						JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
						JOIN tbl_posto                ON tbl_os.posto        = tbl_posto.posto
						JOIN tbl_posto_fabrica        ON tbl_os.posto     = tbl_posto_fabrica.posto
						AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE tbl_os.os = $os_reincidente 
						LIMIT 1";

				$res_reinc = pg_exec($con, $sql);

				if ($login_admin == 1375) echo "<br />OS $os_reincidente, Núm. registros: " . pg_num_rows($res_reinc) . "<br />";
				
				if (pg_num_rows($res_reinc) > 0) { //HD 347704 INICIO
				
					$reinc_os					= pg_result($res_reinc, 0, 'os');
					$reinc_serie				= pg_result($res_reinc, 0, 'serie');
					$reinc_data_abertura		= pg_result($res_reinc, 0, 'data_abertura');
					$reinc_data_fechamento		= pg_result($res_reinc, 0, 'data_fechamento');
					$reinc_sua_os				= pg_result($res_reinc, 0, 'sua_os');
					$reinc_codigo_posto			= pg_result($res_reinc, 0, 'codigo_posto');
					$reinc_posto_nome			= pg_result($res_reinc, 0, 'posto_nome');
					$reinc_posto_email			= pg_result($res_reinc, 0, 'posto_email');
					$reinc_nota_fiscal			= pg_result($res_reinc, 0, 'nota_fiscal');
					$reinc_data_nf				= pg_result($res_reinc, 0, 'data_nf');
					$reinc_consumidor_nome		= pg_result($res_reinc, 0, 'consumidor_nome');
					$reinc_revenda_nome		    = pg_result($res_reinc, 0, 'revenda_nome');
					$reinc_consumidor_revenda   = pg_result($res_reinc, 0, 'consumidor_revenda');
					$reinc_consumidor_fone		= pg_result($res_reinc, 0, 'consumidor_fone');
					$reinc_produto_referencia	= pg_result($res_reinc, 0, 'produto_referencia');
					$reinc_produto_descricao	= pg_result($res_reinc, 0, 'produto_descricao');
					$reinc_produto_voltagem		= pg_result($res_reinc, 0, 'voltagem');
					$reinc_data_digitacao		= pg_result($res_reinc, 0, 'data_digitacao');
					$reinc_data_abertura		= pg_result($res_reinc, 0, 'data_abertura');
					$reinc_defeito_constatado	= pg_result($res_reinc, 0, 'defeito_constatado');
					$reinc_defeito_reclamado	= pg_result($res_reinc, 0, 'defeito_reclamado');
					
				} //HD 347704 FIM
				
			}

			$cores++;

			if ($login_fabrica == 11) {
				$cor = ($cores % 2 == 0) ? "#E4DECD": '#E2E9F5';
			} else {
				$cor = ($cores % 2 == 0) ? "#F7F5F0": '#F1F4FA';
			}

			$sqlint = "SELECT os_interacao,admin from tbl_os_interacao WHERE os = $os ORDER BY os_interacao DESC limit 1";
			$resint = pg_exec($con, $sqlint);

			if (pg_num_rows($resint) > 0) {
			
				$admin = pg_result($resint, 0, 'admin');

				if (strlen($admin) > 0) {
					$cor = "#FFCC00";
				} else {
					$cor = "#669900";
				}

			}
			
			if ($login_fabrica == 52) {#HD 253575 - XLS - PARA OS Ñ REINCIDENTES

				fputs ($fp,"<tr bgcolor='$cor'>");
				
					fputs ($fp,"<TD ><b>&nbsp</b></TD>");
					fputs ($fp,"<TD ><b>$sua_os</b></TD>");
					fputs ($fp,"<TD ><b>$serie</b></TD>");
					fputs ($fp,"<TD ><b>$data_abertura</b></TD>");
					fputs ($fp,"<TD ><b>$data_fechamento</b></TD>");
					fputs ($fp,"<TD ><b>$posto_nome</b></TD>");
					fputs ($fp,"<TD ><b>$nota_fiscal</b></TD>");
				
					if (strlen($consumidor_nome) == 0) $consumidor_nome = $revenda_nome;

					if ($consumidor_revenda == 'R') {

						if (strlen($revenda_nome) == 0) {
							$revenda_nome = $consumidor_nome;
						}

					}

					fputs ($fp,"<TD ><b>$consumidor_nome</b></TD>");			
					fputs ($fp,"<TD ><b>$produto_descricao</b></TD>");			
					fputs ($fp,"<TD ><b>$defeito_constatado</b></TD>");			
					fputs ($fp,"<TD ><b>$status_descricao</b></TD>");			
					fputs ($fp,"<TD ><b>$cliente_admin</b></TD>");			
					fputs ($fp,"<TD ><b>$motivo_reincidencia</b></TD>");			
					fputs ($fp,"<TD ><b>$obs_reincidencia</b></TD>");			

				
				fputs ($fp,"</tr >");

			}#HD 253575 - XLS
			
			if (strlen($sua_os) == 0) $sua_os = $os;

			echo "<tr bgcolor='$cor' id='linha_$x'>";
			
			if ($login_fabrica == 52) {#HD 253575 - INICIO

				if (!$cook_cliente_admin) {

					echo "<td align='center' width='0'>";

					if ($status_os == 67 or $status_os == 68 or $status_os == 70 or $status_os == 134) {

						echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";

						if (strlen($msg_erro) > 0) {

							if (strlen($_POST["check_".$x]) > 0) {
								echo " CHECKED ";
							}

						}

						echo ">";

					}
						
					echo "</td>";

				} else {
					echo "<td>&nbsp;</td>";
				}

			} else {

				echo "<td align='center' width='0'>";

				if ($status_os == 67 or $status_os == 68 or $status_os == 70 or $status_os == 134) {

					echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";

					if (strlen($msg_erro) > 0) {

						if (strlen($_POST["check_".$x]) > 0) {
							echo " CHECKED ";
						}

					}

					echo ">";

				}
					
				echo "</td>";

			} #HD 253575 _ FIM

			echo "<td><a href='os_press.php?os=$os'  target='_blank'>$sua_os</a> </td>";
			echo "<td>$serie</td>";
			echo "<td>$data_abertura</td>";
			echo "<td>$data_fechamento </td>";
			echo "<td align='left' title='".$codigo_posto." - ".$posto_nome."'>".substr($posto_nome,0,20) ."...</td>";
			echo "<td>$nota_fiscal</td>";

			if (strlen($consumidor_nome) == 0) $consumidor_nome = $revenda_nome;//HD 119665

			if ($consumidor_revenda == 'R') {

				if (strlen($revenda_nome) == 0) {
					$revenda_nome = $consumidor_nome;
				}

			}

			echo "<td>$consumidor_nome</td>";
			echo "<td align='left' title='Produto: $produto_referencia - $produto_descricao' style='cursor:help'>".substr($produto_descricao,0,20)."</td>";
			echo "<td>$defeito_constatado</td>";
			echo "<td title='Observação: ".$status_observacao."'>".str_replace('CNPJ','CNPJ <BR>',$status_descricao). "</td>";

			if ($login_fabrica == 52) {
				echo "<td>$codigo_cliente_admin <br /> $cliente_admin</td>";
				echo "<td>$motivo_reincidencia</td>";
			}

			echo "<td title='$sua_os - Motivo: ".$obs_reincidencia."'>".substr($obs_reincidencia,0,50). "</td>";	

			if ($login_fabrica <> 11) {
			
				$sqlint = "SELECT os_interacao,admin from tbl_os_interacao WHERE os = $os ORDER BY os_interacao DESC limit 1";
				$resint = pg_exec($con,$sqlint);
				
				$onclick = "onclick='abreInteracao($x, $os, \"Mostrar\", \"$posto\");'";

				if (pg_num_rows($resint) == 0) {

					$botao = "<input type='button' value='Interagir' $onclick title='Enviar Interação com Posto'>";

				} else {
					
					$admin = pg_result($resint, 0, 'admin');

					if (strlen($admin) > 0) {
						$botao = "<input type='button' value='Interagir' $onclick title='Aguardando Resposta do Posto'>";
					} else {
						$botao = "<input type='button' value='Interagir' $onclick title='Posto Respondeu, clique aqui para visualizar'>";
					}

				}

				if ($login_fabrica == 52) {

					if (!$cook_cliente_admin) {
						echo "<td><div id=btn_interacao_".$x.">$botao</div></td>";
					}

				} else if ($login_fabrica != 94) {
					echo "<td><div id=btn_interacao_".$x.">$botao</div></td>";
				}

			}

			echo "</tr>";
			
			$colspan_fricon = ($login_fabrica == 52) ? "15" : "13";

			if (!$cook_cliente_admin) {
				$colspan_fricon_reinc = ($login_fabrica == 52) ? "5" : "3";
			} else {
				$colspan_fricon_reinc = ($login_fabrica == 52) ? "4" : "3";
			}

			if ($login_fabrica == 52) {

				if (!$cook_cliente_admin) {

					echo "<tr bgcolor='#C0E2D9'>";
						echo "<td colspan='$colspan_fricon'><div id='interacao_".$x."'></div></td>";
					echo "</tr>";

				}

			} else if ($login_fabrica != 94) {

				echo "<tr bgcolor='#C0E2D9'>";
					echo "<td colspan='13'><div id='interacao_".$x."'></div></td>";
				echo "</tr>";

			}

			/* ---------------- OS REINCIDENTE -------------------*/
			
			#HD 253575 - XLS - OS's REINCIDENTES - INICIO
			if ($login_fabrica == 52) {

				fputs ($fp,"<tr bgcolor='$cor'>");
				
					fputs ($fp,"<TD><font color='#FF0000' >Reinc.</font></TD>");
					fputs ($fp,"<TD>$reinc_sua_os</TD>");
					fputs ($fp,"<TD>$reinc_serie</TD>");
					fputs ($fp,"<TD>$reinc_data_abertura</TD>");
					fputs ($fp,"<TD>$reinc_data_fechamento</TD>");
					fputs ($fp,"<TD>$reinc_posto_nome</TD>");
					fputs ($fp,"<TD>$reinc_nota_fiscal</TD>");
					
					if (strlen($reinc_consumidor_nome) == 0) $reinc_consumidor_nome = $reinc_revenda_nome;
					if ($reinc_consumidor_revenda=='R'){
						if (strlen($reinc_revenda_nome) == 0){
							$reinc_revenda_nome = $reinc_consumidor_nome;
						}
					}
					
					fputs ($fp,"<TD>$reinc_consumidor_nome</TD>");
					fputs ($fp,"<TD>$reinc_produto_descricao</TD>");
					fputs ($fp,"<TD>$reinc_defeito_constatado</TD>");
					fputs ($fp,"<TD>$reinc_status_descricao</TD>");
					
				fputs ($fp,"</tr>");

			}#HD 253575 - XLS - OS's REINCIDENTES - FIM
			
			echo "<tr bgcolor='$cor'>";
			echo "<td align='center' width='0'>Reinc.</td>";
			echo "<td>$reinc_sua_os</a></td>";
			echo "<td>$reinc_serie</td>";
			echo "<td>$reinc_data_abertura</td>";
			echo "<td>$reinc_data_fechamento </td>";
			echo "<td align='left'>".substr($reinc_posto_nome,0,20) ."...</td>";
			echo "<td>$reinc_nota_fiscal</td>";

			if (strlen($reinc_consumidor_nome) == 0) $reinc_consumidor_nome = $reinc_revenda_nome;//HD 119665

			if ($reinc_consumidor_revenda == 'R') {

				if (strlen($reinc_revenda_nome) == 0) {
					$reinc_revenda_nome = $reinc_consumidor_nome;
				}

			}

			echo "<td>$reinc_consumidor_nome</td>";
			echo "<td align='left' style='cursor:help'>$reinc_produto_referencia - ".substr($reinc_produto_descricao ,0,20)."</td>";
			echo "<td>";
			echo $reinc_defeito_constatado;
			echo "</td>";
			echo "<td title='Observação: ".$reinc_status_observacao."' colspan='$colspan_fricon_reinc'>".$reinc_status_descricao. "</td>";
			echo "</tr>";

		}

		if ($login_fabrica == 52) {#HD 253575 |-| FIM XLS |-| INICIO
			
			fputs ($fp,"</table>");

			fputs ($fp,"</body>");
			fputs ($fp, "</html>");
			
			fclose ($fp);
			
			//echo `cd $path_tmp; rm -rf $arquivo_nome_c.zip; zip -o $arquivo_nome_c.zip $arquivo_nome_c > /dev/null ; mv  $arquivo_nome_c.zip $path `;
			echo `cp $arquivo_completo_tmp $arquivo_completo`;
		
		}#HD 253575 |-| FIM XLS |-|FIM

		echo "<input type='hidden' name='qtde_os' value='$x'>";
		
		#HD 253575 - INICIO - IF para cook_cliente_admin
		if (!$cook_cliente_admin) {

			echo "<tr class='titulo_tabela'>";
			echo "<td height='20' colspan='$colspan_fricon' align='left'> ";

			if (trim($aprova) == 'aprovacao' || $login_fabrica == 94) {

				echo "&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; Com Marcados: &nbsp;";
				echo "<select name='select_acao' size='1' class='frm' >";
				echo "<option value=''></option>";

				if ($login_fabrica <> 94) {
					echo "<option value='00'"; if ($_POST["select_acao"] == "00") echo " selected"; echo ">OS APROVADA</option>";
				}

				if ($login_fabrica == 11 or $login_fabrica == 24 or $login_fabrica == 90 or $login_fabrica == 94) {

					echo "<option value='13'"; if ($_POST["select_acao"] == "13") echo " selected"; echo ">RECUSAR OS</option>";

				} else {

					$sql = "SELECT motivo_recusa, motivo FROM tbl_motivo_recusa WHERE fabrica = $login_fabrica AND liberado IS TRUE;";
					$res = pg_exec($con,$sql);

					if (pg_numrows($res) > 0) {

						echo "<option value='13'"; if ($_POST["select_acao"] == "13") echo " selected"; echo ">RECUSAR OS</option>";

						for ($l = 0; $l < pg_numrows($res); $l++) {

							$motivo_recusa = pg_result($res,$l,motivo_recusa);
							$motivo        = pg_result($res,$l,motivo);
							$motivo        = substr($motivo,0,50);

							echo "<option value='$motivo_recusa'>$motivo</option>";

						}

					}

				}

				echo "</select>";

				if ($login_fabrica == 11 or $login_fabrica == 24) {
					echo "&nbsp;&nbsp;Motivo: <input class='frm' type='text' name='observacao' id='observacao' size='50' maxlength='900' value='' ";  echo ">";
				}

				echo "&nbsp;&nbsp;<input type='button' value='Gravar' style='cursor:pointer' onclick='
				if (document.frm_pesquisa2.select_acao.value == 163){recusaFabricante();}else {document.frm_pesquisa2.submit();}'
				style='cursor:pointer;' border='0'></td></tr>";

			}
		
		}
		
			
		if ($login_fabrica == 52) {	#HD 253575 - BOTÃO DE DOWNLOAD EXCEL

			echo "<p>
					<a href='../admin/xls/$arquivo_nome_c' target='_blank'>
						<img src='/assist/imagens/excel.gif'><br><font color='#3300CC'>Fazer download do relatório em  XLS</font>
					</a>
				</p>
				<br />";
			
		}#HD 253575 - FIM

		echo "</table>";
		echo "<input type='hidden' name='motivo_recusa' id='motivo_recusa'>";
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
		
		echo "</form>";

	} else {
		echo "<br />";
		echo "<div align='center' style='font:bold 14px Arial'>Nenhuma OS Encontrada.</div>";
	}

	$msg_erro = '';

}

include "rodape.php" ?>
</div>
