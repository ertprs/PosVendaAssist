<? 
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';
include 'cabecalho.php';
echo "<br><br>";
	$os = $_GET['excluir'];

	if (strlen ($os) > 0) {
		if($login_fabrica==50){//HD 37007 5/9/2008
			$sql = "UPDATE tbl_os SET excluida = 't' WHERE os = $os AND fabrica = $login_fabrica";
			$res = @pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);
			
			#158147 Paulo/Waldir desmarcar se for reincidente
			$sql = "SELECT fn_os_excluida_reincidente($os,$login_fabrica)";
			$res = pg_query($con, $sql);

		} else {
			$sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
			$res = @pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	$os = $_GET['consertado'];
	if (strlen ($os) > 0) {
		$msg_erro = "";

		if($login_fabrica == 11){
			$sqlD = "SELECT os
					FROM tbl_os
					WHERE os = $os
					AND fabrica  = $login_fabrica
					AND defeito_constatado IS NOT NULL
					AND solucao_os IS NOT NULL";
			$resD = @pg_query($con,$sqlD);
			$msg_erro = pg_errormessage($con);
			if(pg_num_rows($resD)==0){
				$msg_erro = traduz("por.favor.verifique.os.dados.digitados.defeito.constatado.e.solucao.na.tela.de.lancamento.de.itens",$con,$cook_idioma);
			}
		}

		if (strlen($msg_erro)==0){
			$sql = "UPDATE tbl_os SET data_conserto = CURRENT_TIMESTAMP WHERE os=$os";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen($msg_erro)==0){
			echo "ok|ok";
		}else{
			echo "erro|$msg_erro";
		}
		exit;
	}

	# ---- fechar ---- #
	$os = $_GET['fechar'];
	if (strlen ($os) > 0) {
	//	include "ajax_cabecalho.php";

		$msg_erro = "";
		$res = pg_query ($con,"BEGIN TRANSACTION");
		if($login_fabrica == 3){
			$sql = "SELECT tbl_os_item.os_item , tbl_os_extra.obs_fechamento
					FROM tbl_os_produto
					JOIN tbl_os_item           ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
					JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
					JOIN tbl_os_extra          ON tbl_os_produto.os             = tbl_os_extra.os
					LEFT JOIN tbl_faturamento_item on tbl_os_item.peca = tbl_faturamento_item.peca and tbl_os_item.pedido = tbl_faturamento_item.pedido
					WHERE tbl_os_produto.os = $os
					AND tbl_servico_realizado.gera_pedido IS TRUE
					AND tbl_faturamento_item.faturamento_item IS NULL
					LIMIT 1";
			$res = @pg_query($con,$sql);
			if(pg_num_rows($res)>0){
				$os_item = trim(pg_fetch_result($res,0,os_item));
				$obs_fechamento = trim(pg_fetch_result($res,0,obs_fechamento));
				if(strlen($os_item)>0 and strlen($obs_fechamento)==0){
					$msg_erro .= traduz("os.com.pecas.pendentes,.favor.informar.o.motivo.do.fechamento",$con,$cook_idioma);
				}
			}

			$sql = "SELECT tbl_os.os FROM tbl_os WHERE tbl_os.os = $os AND tbl_os.defeito_constatado IS NULL";
			$res = pg_query ($con,$sql);
			if (pg_num_rows ($res) > 0) {
				$sql = "UPDATE tbl_os SET defeito_constatado = 0 WHERE tbl_os.os = $os";
				$res = pg_query ($con,$sql);
			}

			$sql = "SELECT tbl_os.os FROM tbl_os WHERE tbl_os.os = $os AND tbl_os.solucao_os IS NULL";
			$res = pg_query ($con,$sql);
			if (pg_num_rows ($res) > 0) {
				$sql = "UPDATE tbl_os SET solucao_os = 0 WHERE tbl_os.os = $os";
				$res = pg_query ($con,$sql);
			}

			$sql = "SELECT tbl_os.os FROM tbl_os JOIN tbl_os_produto USING (os) JOIN tbl_os_item USING (os_produto) WHERE tbl_os.os = $os AND tbl_os_item.peca_serie_trocada IS NULL";
			$res = pg_query ($con,$sql);
			if (pg_num_rows ($res) > 0) {
				$sql = "UPDATE tbl_os_item SET peca_serie_trocada = '0000000000000' FROM tbl_os_produto JOIN tbl_os USING (os) WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os.os = $os";
				$res = pg_query ($con,$sql);
			}
		}

		$sql = "SELECT status_os
				FROM tbl_os_status
				WHERE os = $os
				AND status_os IN (62,64,65,72,73,87,88,116,117)
				ORDER BY data DESC
				LIMIT 1";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res)>0){
			$status_os = trim(pg_fetch_result($res,0,status_os));
			if ($status_os=="72" || $status_os=="62" || $status_os=="87" || $status_os=="116"){
				if ($login_fabrica ==51) { // HD 59408
					$sql = " INSERT INTO tbl_os_status
							(os,status_os,data,observacao)
							VALUES ($os,64,current_timestamp,'OS Fechada pelo posto')";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "UPDATE tbl_os_item SET servico_realizado = 671 FROM tbl_os_produto
							WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
							AND   tbl_os_produto.os = $os";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "UPDATE tbl_os SET defeito_constatado = 10536,solucao_os = 491
							WHERE tbl_os.os = $os";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}else{
					$msg_erro .= traduz("os.com.intervencao,.nao.pode.ser.fechada.",$con,$cook_idioma);
				}
			}
		}

		$sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $os AND fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
		$msg_erro .= pg_errormessage($con) ;

		if (strlen ($msg_erro) == 0 AND $login_fabrica == 1) {
			$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
			$res = @pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
			$res = pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con) ;
			if (strlen ($msg_erro) == 0 and ($login_fabrica==1 or $login_fabrica==24)) {
				$sql = "SELECT fn_estoque_os($os, $login_fabrica)";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
		if (strlen ($msg_erro) == 0 and $login_fabrica==24) { //HD 3426
			$sql = "SELECT fn_estoque_os($os, $login_fabrica)";
			$res = @pg_query ($con,$sql);
		}
			//HD 11082 17347
		if(strlen($msg_erro) ==0 and $login_fabrica==11 and $login_posto==14301){
			$sqlm="SELECT tbl_os.sua_os          ,
						 tbl_os.consumidor_email,
						 tbl_os.serie           ,
						 tbl_posto.nome         ,
						 tbl_produto.descricao  ,
						 to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento
					from tbl_os
					join tbl_produto using(produto)
					join tbl_posto on tbl_os.posto = tbl_posto.posto
					where os=$os";
			$resm=pg_query($con,$sqlm);
			$msg_erro .= pg_errormessage($con) ;

			$sua_osm           = trim(pg_fetch_result($resm,0,sua_os));
			$consumidor_emailm = trim(pg_fetch_result($resm,0,consumidor_email));
			$seriem            = trim(pg_fetch_result($resm,0,serie));
			$data_fechamentom  = trim(pg_fetch_result($resm,0,data_fechamento));
			$nomem             = trim(pg_fetch_result($resm,0,nome));
			$descricaom        = trim(pg_fetch_result($resm,0,descricao));

			if(strlen($consumidor_emailm) > 0){

				$nome         = "TELECONTROL";
				$email_from   = "helpdesk@telecontrol.com.br";
				$assunto      = traduz("ordem.de.servico.fechada",$con,$cook_idioma);
				$destinatario = $consumidor_emailm;
				$boundary = "XYZ-" . date("dmYis") . "-ZYX";

				$mensagem = traduz("a.ordem.de.serviço.%.referente.ao.produto.%.com.número.de.série.%.foi.fechada.pelo.posto.%.no.dia.%",$con,$cook_idioma,array($sua_osm,$descricaom,$seriem,$nomem,$data_fechamentom));


				$body_top = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";
				@mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), "From: ".$email_from." \n $body_top ");
			}
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");

			//Envia e-mail para o consumidor, avisando da abertura da OS
			//HD 150972
			if (($login_fabrica == 14) || ($login_fabrica == 43) || ($login_fabrica == 66))
			{
				$novo_status_os = "FECHADA";
				include('os_email_consumidor.php');
			}

			echo "ok;XX$os";
		}else{
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
			echo "erro;$sql ==== $msg_erro ";
		}


		flush();
		exit;
	}

?>
<script language='javascript'>

	function mostrarOs(status) {
		window.location = 'status_os_posto.php?status='+status;
	}

	
	function Trim(s){
		var l=0;
		var r=s.length -1;

		while(l < s.length && s[l] == ' '){
			l++;
		}
		while(r > l && s[r] == ' '){
			r-=1;
		}
		return s.substring(l, r+1);
	}

	function _trim (s) {
	   //   /            open search
	   //     ^            beginning of string
	   //     \s           find White Space, space, TAB and Carriage Returns
	   //     +            one or more
	   //   |            logical OR
	   //     \s           find White Space, space, TAB and Carriage Returns
	   //     $            at end of string
	   //   /            close search
	   //   g            global search

	   return s.replace(/^\s+|\s+$/g, "");
	}

		/* HD 133499 */
		function disp_prompt(os, sua_os){
			var motivo =prompt("Qual o Motivo da Exclusão da os "+sua_os+" ?",'',"Motivo da Exclusão");
			if (motivo !=null && $.trim(motivo) !="" && motivo.length > 0 ){
				var resultado = $.ajax({
					type: "GET",
					url: 'grava_obs_excluida.php',
					data: 'motivo=' + motivo + '&os=' + os,
					cache: false,
					async: false,
					complete: function(resposta) {
						verifica_res = resposta.responseText;
						if (verifica_res =='ok'){
							return true;
						}
					}
				 }).responseText;

				if (resultado =='ok'){
					return true;
				}else{
					alert(resultado,'Erro');
				}
			}else{
				alert('Digite um motivo por favor!','Erro');
				return false;
			}
		}

	function DataHora(evento, objeto){
		var keypress=(window.event)?event.keyCode:evento.which;
		campo = eval (objeto);
		if (campo.value == '00/00/0000')
		{
			campo.value=""
		}

		caracteres = '0123456789';
		separacao1 = '/';
		separacao2 = ' ';
		separacao3 = ':';
		conjunto1 = 2;
		conjunto2 = 5;
		conjunto3 = 10;
		conjunto4 = 13;
		conjunto5 = 16;
		if ((caracteres.search(String.fromCharCode (keypress))!=-1) && campo.value.length < (19))
		{
			if (campo.value.length == conjunto1 )
			campo.value = campo.value + separacao1;
			else if (campo.value.length == conjunto2)
			campo.value = campo.value + separacao1;
			else if (campo.value.length == conjunto3)
			campo.value = campo.value + separacao2;
			else if (campo.value.length == conjunto4)
			campo.value = campo.value + separacao3;
			else if (campo.value.length == conjunto5)
			campo.value = campo.value + separacao3;
		}
		else
			event.returnValue = false;
	}


	function retornaFechamentoOS (http , sinal, excluir, lancar) {
		if (http.readyState == 4) {
			if (http.status == 200) {
				results = http.responseText.split(";");
				if (typeof (results[0]) != 'undefined') {
					if (_trim(results[0]) == 'ok') {
						alert ('OS <? fecho("fechada.com.sucesso",$con,$cook_idioma) ?>');
						sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
						sinal.src='/assist/imagens/pixel.gif';
						excluir.src='/assist/imagens/pixel.gif';
						if(lancar){
							lancar.src='/assist/imagens/pixel.gif';
						}
					}else{
						if (http.responseText.indexOf ('de-obra para instala')>0){
							alert ('<? fecho("esta.os.nao.tem.mao-de-obra.para.instalacao",$con,$cook_idioma) ?>');
						}else if (http.responseText.indexOf ('Nota Fiscal de Devol')>0){
							alert ('<? fecho("por.favor.utilizar.a.tela.de.fechamento.de.os.para.informar.a.nota.fiscal.de.devolucao",$con,$cook_idioma) ?>');
						}else if (http.responseText.indexOf ('o-de-obra para atendimento')>0){
							alert ('<? fecho("esta.os.nao.tem.mao-de-obra.para.este.atendimento",$con,$cook_idioma) ?>');
						}else if (http.responseText.indexOf ('Favor informar aparência do produto e acessórios')>0){
							alert ('<? fecho("por.favor.verifique.os.dados.digitados.aparencia.e.acessorios.na.tela.de.lancamento.de.itens",$con,$cook_idioma) ?>');
						}else if (http.responseText.indexOf ('Type informado para o produto não é válido')>0){
							alert ('<? fecho("type.informado.para.o.produto.nao.e.valido",$con,$cook_idioma) ?>');
						}else if (http.responseText.indexOf ('OS com peças pendentes')>0){
							alert ('<? fecho("os.com.pecas.pendentes,.favor.informar.o.motivo.na.tela.de.fechamento.da.os",$con,$cook_idioma) ?>');
						}else if (http.responseText.indexOf ('OS não pode ser fechada, Favor Informar a Kilometragem')>0){
							alert ('<? fecho("os.nao.pode.ser.fechada,.favor.informar.a.kilometragem",$con,$cook_idioma) ?>');
						}else if (http.responseText.indexOf ('OS não pode ser fechada, Kilometragem Recusada')>0){
							alert ('<? fecho("os.nao.pode.ser.fechada,.kilometragem.recusada",$con,$cook_idioma) ?>');
						}else if (http.responseText.indexOf ('OS não pode ser fechada, aguardando aprovação de Kilometragem')>0){
							alert ('<? fecho("os.nao.pode.ser.fechada,.aguardando.aprovacao.de.kilometragem",$con,$cook_idioma) ?>');
						}else if (http.responseText.indexOf ('Esta OS teve o número de série recusado e não pode ser finalizada')>0){
							alert ('<? fecho("esta.os.teve.o.numero.de.serie.recusado.e.nao.pode.ser.finalizada",$con,$cook_idioma) ?>');
						}else if (http.responseText.indexOf ('Informar defeito constatado (Reparo) para OS')>0){
							alert ('<? fecho("por.favor.verifique.os.dados.digitados.em.defeito.constatado.(reparo).na.tela.de.lancamento.de.itens",$con,$cook_idioma) ?>');
						}else if (http.responseText.indexOf ('Por favor, informar o conserto do produto na tela CONSERTADO')>0){
							alert ('<? fecho("por.favor.informar.o.conserto.do.produto.na.tela.consertado",$con,$cook_idioma) ?>');
						}else if (http.responseText.indexOf ('Favor informar solução tomada para a ordem de serviço')>0){
							alert ('<? fecho("oss.sem.solucao.e.sem.itens.lancados",$con,$cook_idioma) ?>');
						}else if (http.responseText.indexOf ('Favor informar o defeito constatado para a ordem de serviço')>0){
							alert ('<? fecho("oss.sem.defeito.constatado",$con,$cook_idioma) ?>');
						}else {alert ('<? fecho("por.favor.verifique.os.dados.digitados.defeito.constatado.e.solucao.na.tela.de.lancamento.de.itens",$con,$cook_idioma) ?>');
						}
					}
				}else{
					alert ('<? fecho("fechamento.nao.processado",$con,$cook_idioma) ?>');
				}
			}
		}
	}

	function fechaOSnovo(linha) {
		div = document.getElementById('div_fechar_'+linha);
		div.style.display='block';
	}

	function retornaFechamentoOS2(http,sinal,excluir,lancar,linha,div_anterior) {
		var div;
		div = document.getElementById('div_fechar_'+linha);
		if (http.readyState == 4) {
			if (http.status == 200) {
				results = http.responseText.split(";");
				if (typeof (results[0]) != 'undefined'){
					if (_trim(results[0]) == 'ok') {
						sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
						sinal.src='/assist/imagens/pixel.gif';
						excluir.src='/assist/imagens/pixel.gif';
						div.style.display='none';
						if(lancar){
							lancar.src='/assist/imagens/pixel.gif';
						}
						alert ('fechada.com.sucesso');
					}
					else {
						var msg = _trim(results[5]);
						alert(msg);
						div.innerHTML = div_anterior;
						}
				}
			}
		}
	}


	function fechaOSnovo2(os,data,sinal,excluir,lancar,linha) {
		var data_fechamento = data;
		var div = document.getElementById('div_fechar_'+linha);
		var divmostrar = document.getElementById('mostrar_'+linha);
		var hora;
		var div_anterior;
		hora = new Date();


		div.style.display = "none";
		divmostrar.innerHTML = "<img src='admin/a_imagens/ajax-loader.gif'>"
		divmostrar.style.display = "block";

		var url = "ajax_fecha_os.php?fecharnovo=sim&os=" + escape(os) + '&data_fechamento='+data+'&cachebypass='+hora.getTime();
		var fecha = $.ajax({
						type: "GET",
						url: url,
						cache: false,
						async: false
		 }).responseText;

		var fecha_array = 0;
		fecha_array = fecha.split(";");

			if (fecha_array[0]=='ok') {
				sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
				sinal.src='/assist/imagens/pixel.gif';
				excluir.src='/assist/imagens/pixel.gif';
				div.style.display='none';
				if(lancar){
					lancar.src='/assist/imagens/pixel.gif';
				}
				alert('Os Fechada com Sucesso');
				divmostrar.style.display = "none";

			}
			else {
				var msg               = fecha_array[1];
				if (msg == 'tbl_os&quot') {
					alert('Por favor confira a data digitada!');
				}
				else {
					var msg               = fecha_array[1];
					alert('Por favor confira a data digitada!');
				}

			divmostrar.style.display = "none";
			div.style.display = "block";
			$('#ajax_'+linha).val(fecha);
			}
	}

	function fechaOS (os , sinal , excluir , lancar ) {
		var curDateTime = new Date();
		url = "<?= $PHP_SELF ?>?fechar=" + escape(os) + '&dt='+curDateTime;
		http.open("GET", url , true);
		http.onreadystatechange = function () { retornaFechamentoOS (http , sinal, excluir, lancar) ; } ;
		http.send(null);
	}


	function retornaConsertadoOS (http ,botao ) {
		if (http.readyState == 4) {
			if (http.status == 200) {
				var results = http.responseText.split("|");
				if (typeof (results[0]) != 'undefined'){
					if (_trim(results[0]) == 'ok') {
						botao.style.display='none';
					}else{
						if(results[1]){
							alert(results[1]);
						}
						alert('<? fecho("acao.nao.concluida.tente.novamente",$con,$cook_idioma) ?>');
					}
				}else{
					alert ('<? fecho("acao.nao.foi.concluida.com.sucesso",$con,$cook_idioma) ?>');
				}
			}
		}
	}

	function consertadoOS (os , botao ) {
		var curDateTime = new Date();
		url = "<?= $PHP_SELF ?>?consertado=" + escape(os)+'&dt='+curDateTime ;
		http.open("GET", url , true);
		http.onreadystatechange = function () { retornaConsertadoOS (http , botao ) ; } ;
		http.send(null);
	}

</script>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #000000;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.transparencia
{
	display: none;
	position: fixed !important;
	position: absolute;
	z-index: 10;
	height: 100%;
	width: 100%;
	opacity: 0.6;
	color: #000000;
	background-color: #000000;    
}

*.transparencia
{
	filter: alpha(opacity = 40);
}

.aguarde
{
	display: block;
	opacity: 1.0;
	position: absolute;
	top: 20px;
	left: 230;
	width: 95%;
	height: 380px;
	text-align: center;
	vertical-align: middle;
	z-index: 15;
	overflow-x:auto;
	overflow-y:auto;
}

.aguardetexto
{
	text-align: center;
	background: none;
}
</style>


<?
	$status = $_GET['status'];

	if (strlen($status)>0) {

		switch ($status) {
			
			case 'vermelho':

			$sqlMostra = "SELECT	DISTINCT os,
										sua_os,
										data_digitacao,
										to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
										tbl_os.os_reincidente,
										tbl_os.serie,
										excluida,
										motivo_atraso,
										tipo_os_cortesia,
										tbl_os.consumidor_revenda,
										tbl_os.consumidor_nome,
										tbl_os.revenda_nome,
										impressa,
										tbl_os.nota_fiscal,
										tbl_os.nota_fiscal_saida,
										tbl_produto.referencia,
										tbl_produto.descricao,
										tbl_produto.voltagem,
										tipo_atendimento,
										tecnico_nome,
										tbl_os.admin,
										sua_os_offline,
										status_os,
										rg_produto,
										tbl_produto.linha,
										data_conserto,
										tbl_marca.marca,
										tbl_marca.nome as marca_nome,
										consumidor_email
										into TEMP tmp_mostra_vermelho_$login_posto
							FROM tbl_os
					JOIN tbl_os_extra USING(os)
					JOIN tbl_produto USING(produto)
					LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
					WHERE tbl_os.defeito_constatado is null
					AND	  tbl_os.solucao_os is null
					AND tbl_os.posto = $login_posto
					AND tbl_os.fabrica = $login_fabrica
					AND data_conserto IS NULL
					AND tbl_os.finalizada is NULL
					AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')";

					$resTemp = pg_exec($con,$sqlMostra);
					$sqlMostra = "SELECT *FROM tmp_mostra_vermelho_$login_posto";
			break;
			case 'amarelo':

			$sqlMostra = "SELECT	DISTINCT os into TEMP tmp_mostra_vermelho_$login_posto
									FROM tbl_os
									JOIN tbl_os_extra USING(os)
									JOIN tbl_produto USING(produto)
									LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
									WHERE tbl_os.defeito_constatado is null
									AND	  tbl_os.solucao_os is null
									AND tbl_os.posto = $login_posto
									AND tbl_os.fabrica = $login_fabrica
									AND data_conserto IS NULL
									AND tbl_os.finalizada is NULL
									AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')";

					$resTemp = pg_exec($con,$sqlMostra);


				$sqlMostra = "SELECT DISTINCT os,
										sua_os,
										data_digitacao,
										to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
										tbl_os.os_reincidente,
										tbl_os.serie,
										excluida,
										motivo_atraso,
										tipo_os_cortesia,
										tbl_os.consumidor_revenda,
										tbl_os.consumidor_nome,
										tbl_os.revenda_nome,
										impressa,
										tbl_os.nota_fiscal,
										tbl_os.nota_fiscal_saida,
										tbl_produto.referencia,
										tbl_produto.descricao,
										tbl_produto.voltagem,
										tipo_atendimento,
										tecnico_nome,
										tbl_os.admin,
										sua_os_offline,
										status_os,
										rg_produto,
										tbl_produto.linha,
										data_conserto,
										tbl_marca.marca,
										tbl_marca.nome as marca_nome,
										consumidor_email
					FROM tbl_os
					JOIN tbl_os_extra USING(os)
					JOIN tbl_os_produto using (os)
					JOIN tbl_os_item USING (os_produto)
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
					JOIN tbl_peca USING (peca)
					LEFT JOIN tbl_defeito USING (defeito)
					LEFT JOIN tbl_servico_realizado USING (servico_realizado)
					LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
					LEFT JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
					LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
					LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
					WHERE
					tbl_os.posto = $login_posto
					AND tbl_os.fabrica = $login_fabrica
					AND tbl_os.finalizada is NULL
					AND tbl_os.data_conserto IS NULL
					AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
					AND tbl_os_item.peca not in (select peca from tbl_faturamento_item where tbl_faturamento_item.pedido = tbl_os_item.pedido)
					AND os not in (SELECT os FROM tmp_mostra_vermelho_$login_posto)";
			break;

			case 'rosa':
				
			$sqlMostra = "SELECT	DISTINCT os
										into TEMP tmp_mostra_vermelho_$login_posto
									FROM tbl_os
									JOIN tbl_os_extra USING(os)
									JOIN tbl_produto USING(produto)
									LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
									WHERE
									tbl_os.defeito_constatado is null
									AND	tbl_os.solucao_os is null
									AND tbl_os.posto = $login_posto
									AND tbl_os.fabrica = $login_fabrica
									AND data_conserto IS NULL
									AND tbl_os.finalizada is NULL
									AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')";

				$resTemp = pg_exec($con,$sqlMostra);
				//echo nl2br($sqlMostra);

				$sqlTemp = "SELECT DISTINCT os
										into TEMP tmp_os_amarelo_$login_posto
									FROM tbl_os
									JOIN tbl_os_extra USING(os)
									JOIN tbl_os_produto using (os)
									JOIN tbl_os_item USING (os_produto)
									JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
									LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
									JOIN tbl_peca USING (peca)
									LEFT JOIN tbl_defeito USING (defeito)
									LEFT JOIN tbl_servico_realizado USING (servico_realizado)
									LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
									LEFT JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
									LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
									LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
									WHERE
									tbl_os.posto = $login_posto
									AND tbl_os.fabrica = $login_fabrica
									AND tbl_os.finalizada is NULL
									AND tbl_os.data_conserto IS NULL
									AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
									AND tbl_os_item.peca not in (select peca from tbl_faturamento_item where tbl_faturamento_item.pedido = tbl_os_item.pedido)
									AND os not in (SELECT os FROM tmp_mostra_vermelho_$login_posto)";

					$resTemp = pg_exec($con,$sqlTemp);
					//echo nl2br($sqlTemp);

				$sqlMostra = "SELECT DISTINCT os,
										sua_os,
										data_digitacao,
										to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
										tbl_os.os_reincidente,
										tbl_os.serie,
										excluida,
										motivo_atraso,
										tipo_os_cortesia,
										tbl_os.consumidor_revenda,
										tbl_os.consumidor_nome,
										tbl_os.revenda_nome,
										impressa,
										tbl_os.nota_fiscal,
										tbl_os.nota_fiscal_saida,
										tbl_produto.referencia,
										tbl_produto.descricao,
										tbl_produto.voltagem,
										tipo_atendimento,
										tecnico_nome,
										tbl_os.admin,
										sua_os_offline,
										status_os,
										rg_produto,
										tbl_produto.linha,
										data_conserto,
										tbl_marca.marca,
										tbl_marca.nome as marca_nome,
										consumidor_email
									FROM tbl_os
									JOIN tbl_os_extra USING(os)
									JOIN tbl_produto USING(produto)
									LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
									WHERE posto = $login_posto
									AND   tbl_os.fabrica = $login_fabrica
									AND   data_conserto IS NULL
									AND   finalizada is NULL
									AND   (excluida IS NULL OR excluida = 'f')
									AND os not in (SELECT os from  tmp_os_amarelo_$login_posto)
									AND os not in (SELECT os FROM tmp_mostra_vermelho_$login_posto)";
							//echo nl2br($sqlMostra);
			break;
			case 'azul':
				$sqlMostra = "SELECT		os,
									sua_os,
									data_digitacao,
									to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
									tbl_os.os_reincidente,
									tbl_os.serie,
									excluida,
									motivo_atraso,
									tipo_os_cortesia,
									tbl_os.consumidor_revenda,
									tbl_os.consumidor_nome,
									tbl_os.revenda_nome,
									impressa,
									tbl_os.nota_fiscal,
									tbl_os.nota_fiscal_saida,
									tbl_produto.referencia,
									tbl_produto.descricao,
									tbl_produto.voltagem,
									tipo_atendimento,
									tecnico_nome,
									tbl_os.admin,
									sua_os_offline,
									status_os,
									rg_produto,
									tbl_produto.linha,
									data_conserto,
									tbl_marca.marca,
									tbl_marca.nome as marca_nome,
									consumidor_email
									FROM tbl_os
									JOIN tbl_os_extra USING(os)
									JOIN tbl_produto USING(produto)
									LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
									WHERE posto = $login_posto
									AND tbl_os.	fabrica = $login_fabrica
									AND finalizada is NULL
									AND data_conserto is not null
									AND (excluida IS NULL OR excluida = 'f')";
			break;
		
			case 'todas': 

				case 'azul':
				$sqlMostra = "SELECT		os,
									sua_os,
									data_digitacao,
									to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
									tbl_os.os_reincidente,
									tbl_os.serie,
									excluida,
									motivo_atraso,
									tipo_os_cortesia,
									tbl_os.consumidor_revenda,
									tbl_os.consumidor_nome,
									tbl_os.revenda_nome,
									impressa,
									tbl_os.nota_fiscal,
									tbl_os.nota_fiscal_saida,
									tbl_produto.referencia,
									tbl_produto.descricao,
									tbl_produto.voltagem,
									tipo_atendimento,
									tecnico_nome,
									tbl_os.admin,
									sua_os_offline,
									status_os,
									rg_produto,
									tbl_produto.linha,
									data_conserto,
									tbl_marca.marca,
									tbl_marca.nome as marca_nome,
									consumidor_email
									FROM tbl_os
									JOIN tbl_os_extra USING(os)
									JOIN tbl_produto USING(produto)
									LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
									WHERE posto = $login_posto
									AND tbl_os.	fabrica = $login_fabrica
									AND finalizada is NULL
									AND (excluida IS NULL OR excluida = 'f')";
			break;

		}

		##### PAGINAÇÃO - INÍCIO #####
		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sqlMostra;
		$sqlCount .= ") AS count";

		#require "_class_paginacao_teste.php";
		require "_class_paginacao.php";

		// definicoes de variaveis
		$max_links = 12;				// máximo de links à serem exibidos
		$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag= new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$resMostra = $mult_pag->Executar($sqlMostra, $sqlCount, $con, "otimizada", "pgsql");

		##### PAGINAÇÃO - FIM #####


		if (pg_num_rows($resMostra)>0) {
				for ($i = 0 ; $i < pg_num_rows($resMostra) ; $i++) {

				if ($i % 50 == 0) {
					$html .= "</table>";
					flush();
					$html .= "<table border=\"1\" cellpadding=\"2\" cellspacing=\"0\" style=\"border-collapse: collapse\" bordercolor=\"#000000\" width=\"900\">";
				}

				if ($i % 25 == 0) {
					$html .= "<tr class=\"Titulo\" height=\"25\" background=\"admin/imagens_admin/azul.gif\">";
					$html .= "<td class=\"table_line\"><b>OS</td>";
					$html .= "<td  nowrap><b>SÉRIE</td>";
					$html .= "<td nowrap><b>NF</td>";
					$html .= "</td>";
					$html .= "<td><b>AB</td>";
					//HD 14927
					$html .= "<td><b><acronym title=\"".traduz("data.de.conserto.do.produto",$con,$cook_idioma)."\" style=\"cursor:help;\"><b>DC</a></td>";
					$html .= "<td><acronym title=\"".traduz("data.de.fechamento.registrada.pelo.sistema",$con,$cook_idioma)."\" style=\"cursor:help;\"><b>".traduz("fc",$con,$cook_idioma)."</a></td>";
					$html .= "<td><b>".strtoupper(traduz("consumidor",$con,$cook_idioma))."</td>";
					$html .= "<td><b>".strtoupper(traduz("marca",$con,$cook_idioma))."</td>";
					$html .= "<td><b>";
					$html .= strtoupper(traduz("produto",$con,$cook_idioma));
					$html .= "</td>";
					$html .= "<td><img border=\"0\" src=\"imagens/img_impressora.gif\" alt=\"Imprimir OS\"></td>";	
					$colspan = "6";
					$html .= "<td colspan=\"$colspan\"><b>";
					$html .= strtoupper(traduz("acoes",$con,$cook_idioma));
					$html .= "</td>";
				}

					$os                 = trim(pg_fetch_result($resMostra,$i,os));
					$sua_os             = trim(pg_fetch_result($resMostra,$i,sua_os));
					$digitacao          = trim(pg_fetch_result($resMostra,$i,data_digitacao));
					$abertura           = trim(pg_fetch_result($resMostra,$i,data_abertura));
					$serie              = trim(pg_fetch_result($resMostra,$i,serie));
					$excluida           = trim(pg_fetch_result($resMostra,$i,excluida));
					$motivo_atraso      = trim(pg_fetch_result($resMostra,$i,motivo_atraso));
					$tipo_os_cortesia   = trim(pg_fetch_result($resMostra,$i,tipo_os_cortesia));
					$consumidor_revenda = trim(pg_fetch_result($resMostra,$i,consumidor_revenda));
					$consumidor_nome    = trim(pg_fetch_result($resMostra,$i,consumidor_nome));
					$revenda_nome       = trim(pg_fetch_result($resMostra,$i,revenda_nome));
					$impressa           = trim(pg_fetch_result($resMostra,$i,impressa));
					$nota_fiscal        = trim(pg_fetch_result($resMostra,$i,nota_fiscal));//hd 12737 31/1/2008
					$nota_fiscal_saida  = trim(pg_fetch_result($resMostra,$i,nota_fiscal_saida));	//
					$reincidencia       = trim(pg_fetch_result($resMostra,$i,os_reincidente));
					$produto_referencia = trim(pg_fetch_result($resMostra,$i,referencia));
					$produto_descricao  = trim(pg_fetch_result($resMostra,$i,descricao));
					$produto_voltagem   = trim(pg_fetch_result($resMostra,$i,voltagem));
					$tecnico_nome       = trim(pg_fetch_result($resMostra,$i,tecnico_nome));
					$admin              = trim(pg_fetch_result($resMostra,$i,admin));
					$sua_os_offline     = trim(pg_fetch_result($resMostra,$i,sua_os_offline));
					$status_os          = trim(pg_fetch_result($resMostra,$i,status_os));
					$rg_produto         = trim(pg_fetch_result($resMostra,$i,rg_produto));
					$linha              = trim(pg_fetch_result($resMostra,$i,linha));
					$marca     = trim(pg_fetch_result($resMostra,$i,marca));
					$marca_nome= trim(pg_fetch_result($resMostra,$i,marca_nome));
					$data_conserto=trim(pg_fetch_result($resMostra,$i,data_conserto));
					$consumidor_email   = trim(pg_fetch_result($resMostra,$i,consumidor_email));
				
				if ($i % 2 == 0) {
					$cor   = "#F1F4FA";
					$botao = "azul";
				}else{
					$cor   = "#F7F5F0";
					$botao = "amarelo";
				}

				if (strlen($sua_os) == 0) $sua_os = $os;
				if ($login_fabrica == 1) $xsua_os =  $codigo_posto.$sua_os ;
				
				$html .= "<tr class=\"Conteudo\" height=\"15\" bgcolor=\"$cor\" align=\"left\">";
				$html .= "<td  width=\"60\" nowrap>" ;
				$html .= $sua_os;
				$html .= "</td>";
				$html .= "<td width=\"55\" nowrap>" . $serie . "</td>";
				$html .= "<td nowrap>" ;
				$html .= $nota_fiscal;
				$html .= "</td>";

				$html .= "<td nowrap ><acronym title=\"".traduz("data.abertura",$con,$cook_idioma).": $abertura\" style=\"cursor: help;\">" . substr($abertura,0,5) . "</acronym></td>";

				$html .= "<td nowrap ><acronym title=\"".traduz("data.do.conserto",$con,$cook_idioma).": $data_conserto\" style=\"cursor: help;\">" . substr($data_conserto,0,5) . "</acronym></td>";
				$aux_fechamento = $fechamento;
				$html .= "<td nowrap><acronym title=\"".traduz("data.fechamento",$con,$cook_idioma).": ";

				$html .= "<td>$aux_fechamento\" style=\"cursor: help;\">" . substr($aux_fechamento,0,5) . "</acronym></td>";

				$html .= "<td width=\"120\" nowrap><acronym title=\"".traduz("consumidor",$con,$cook_idioma).": $consumidor_nome\" style=\"cursor: help;\">" . substr($consumidor_nome,0,15) . "</acronym></td>";
				$html .= "<td nowrap>$marca_nome</td>";
				$produto = $produto_referencia . " - " . $produto_descricao;

				$html .= "<td width=\"150\" nowrap>". substr($produto,0,20) . "</td>";
				
				##### VERIFICAÇÃO SE A OS FOI IMPRESSA #####
				$html .= "<td width=\"30\" align=\"center\">";
				if (strlen($admin) > 0 and $login_fabrica == 19) $html .= "<img border=\"0\" src=\"imagens/img_sac_lorenzetti.gif\" alt=\"OS lançada pelo SAC Lorenzetti\">";
				else if (strlen($impressa) > 0)                  $html .= "<img border=\"0\" src=\"imagens/img_ok.gif\" alt=\"OS já foi impressa\">";
				else                                             $html .= "<img border=\"0\" src=\"imagens/img_impressora.gif\" alt=\"Imprimir OS\">";
				$html .= "</td>";

				$html .= "<td width=\"60\" align=\"center\">";
					 $html .= "<a href=\"os_press.php?os=$os\" target=\"_blank\"><img border=\"0\" src=\"imagens/btn_consulta.gif\"></a>";
				$html .= "</td>";

				$html .= "<td width=\"60\" align=\"center\">";

				if ($excluida == "f" || strlen($excluida) == 0 and strlen($btn_acao_pre_os)==0) {
					$html .= "<img border=\"0\" src=\"imagens/btn_imprime.gif\"></a>";
				}
				$html .= "</td>";


				$sql_critico = "select produto_critico from tbl_produto where referencia = '$produto_referencia'";
				$res_critico = pg_query($con,$sql_critico);

				if (pg_num_rows($res_critico)>0) {
					$produto_critico = pg_fetch_result($res_critico,0,produto_critico);
				}

				$html .= "<td width='60' align='center' nowrap>";
				if ($troca_garantia == "t" OR (($status_os=="62" and $produto_critico <> 't') || $status_os=="65" || $status_os=="72" || $status_os=="87" || $status_os=="116" || $status_os=="120" || $status_os=="122" || $status_os=="126" || $status_os=="140" || $status_os=="141" || $status_os=="143")) {
				}elseif (($login_fabrica == 3 || $login_fabrica == 6) && strlen ($fechamento) == 0) {
					if ($excluida == "f" || strlen($excluida) == 0) {
						$html .= "<a href='os_item.php?os=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
					}
				}elseif ($login_fabrica == 1 && strlen ($fechamento) == 0 ) {
					if ($excluida == "f" || strlen($excluida) == 0) {
						if ($login_fabrica == 1 AND $tipo_os_cortesia == "Compressor") {
							if($login_posto=="6359"){
								$html .= "<a href='os_item.php?os=$os' target='_blank'>";
							}else{
								$html .= "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
							//takashi alterou 03/11
							}
						}else{
							$html .= "<a href='os_item.php?os=$os' target='_blank'>";
						}//
						if($login_fabrica == 1 AND $tipo_atendimento <> 17 AND $tipo_atendimento <> 18)
							$html .= "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
						else
							$html .= "<p id='lancar_$i' border='0'></p></a>";
					}
				}elseif ($login_fabrica == 7 && strlen ($fechamento) == 0 ) {
					$html .= "<a href='os_filizola_valores.php?os=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
				}elseif (strlen($fechamento) == 0 ) {
					if ($excluida == "f" OR strlen($excluida) == 0) {
						if ($login_fabrica == 1) {
							if($tipo_os_cortesia == "Compressor"){
								if($login_posto=="6359"){
									$html .= "<a href='os_item.php?os=$os' target='_blank'>";
								}else{
									$html .= "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
								//takashi alterou 03/11
								}
							}
							if(strlen($tipo_atendimento) == 0){
								$html .= "<a href='os_item.php?os=$os' target='_blank'>";
							}
						}else{
							//
							if($login_fabrica==19){
								if($consumidor_revenda<>'R'){
									$html .= "<a href='os_item.php?os=$os' target='_blank'>";
									if($sistema_lingua == "ES"){
										$html .= "<img id='lancar_$i' border='0' src='imagens/btn_lanzar.gif'></a>";
									}else{
										$html .= "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
									}
								}
							}else{
								$html .= "<a href='os_item.php?os=$os' target='_blank'>";
								if($sistema_lingua == "ES"){
									$html .= "<img id='lancar_$i' border='0' src='imagens/btn_lanzar.gif'>";
								}else{
									// $data_conserto > "03/11/2008" HD 50435
									$xdata_conserto = fnc_formata_data_pg($data_conserto);

									$sqlDC = "SELECT $xdata_conserto::date > '2008-11-03'::date AS data_anterior";
									#$html .= $sqlDC;
									$resDC = pg_query($con, $sqlDC);
									if(pg_num_rows($resDC)>0) $data_anterior = pg_fetch_result($resDC, 0, 0);

									if($login_fabrica==11 AND strlen($data_conserto)>0 AND $data_anterior == 't'){
										$html .= "";
									}else{
										$html .= "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'>";
									}
								}
								$html .= "</a>";
							}
							//
						}
					}
				}elseif (strlen($fechamento) > 0 && strlen($extrato) == 0 AND strlen($rg_produto)==0) {
					if ($excluida == "f" || strlen($excluida) == 0) {
						if (strlen ($importacao_fabrica) == 0) {
							if($login_fabrica == 20){
								/*if($status_os<>'13' AND ($tipo_atendimento<>13 and $tipo_atendimento <> 66))
									$html .= "<a href='os_cadastro.php?os=$os&reabrir=ok'><img border='0' src='imagens/btn_reabriros.gif'></a>";*/
								// HD 61323
							}
							else if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18)) $html .= "&nbsp;";
								else{
									//HD 15368 - Raphael, se a os for troca não pode irá reabrir
									$sqlX = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os";
									$resX = @pg_query($con,$sqlX);
									if(@pg_num_rows($resX)==0) {
										if($login_fabrica <>11){ // HD 45935
											$html .= "<a href='os_item.php?os=$os&reabrir=ok'><img border='0' src='imagens/btn_reabriros.gif'></a>";
										}else{
											$html .= "&nbsp;";
										}
									}
								}
						}
					}
				}else{
					$html .= "&nbsp;";
				}
				$html .= "</td>";


				$html .= "<td width='60' align='center'>";
				if (strlen($fechamento) == 0 && strlen($pedido) == 0) {
					if (($status_os!="62" && $status_os!="65" && $status_os!="72" && $status_os!="87" && $status_os!="116" && $status_os!="120" && $status_os!="122" && $status_os!="126" && $status_os!="140" && $status_os!="141" && $status_os!="143") ||($reincidencia=='t')){
						if ($excluida == "f" || strlen($excluida) == 0) {
							if (strlen ($admin) == 0) {
								if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18) AND $valores_adicionais > 0)
									$html .= "<a href='javascript: if (confirm('".traduz("deseja.realmente.excluir.a.os",$con,$cook_idioma)." $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }'><p id='excluir_$i' border='0'></p></a>";
								else
									$html .= "<a href=\"javascript: if (confirm('".traduz("deseja.realmente.excluir.a.os",$con,$cook_idioma)." $sua_os ?') == true) { if(disp_prompt($os, '$sua_os') == true){window.location='$PHP_SELF?excluir=$os';} }\"><img id='excluir_$i' border='0' src='imagens/btn_excluir.gif'></a>";

							}else {
								if($login_fabrica == 20) { # 148322
									$html .= "<a href='javascript: if (confirm('".traduz("deseja.realmente.excluir.a.os",$con,$cook_idioma)." $sua_os ?') == true) { if(disp_prompt($os, '$sua_os') == true){window.location='$PHP_SELF?excluir=$os';} }'><img id='excluir_$i' border='0' src='imagens/btn_excluir.gif'></a>";
								}else{
									$html .= "<img id='excluir_$i' border='0' src='imagens/pixel.gif'>";
								}
							}
						}
					}
				}else{
					$html .= "&nbsp;";
				}
				$html .= "</td>";
				$html .=  "<td width='60' align='center'>";
				if (strlen($fechamento) == 0 AND $status_os!="62"  && $status_os!="65" && $status_os!="72" && $status_os!="87" && $status_os!="116" && $status_os!="120" && $status_os!="122" && $status_os!="126" && $status_os!="140" && $status_os!="141" && $status_os!="143" && $status_os != "98") {
					if ($excluida == "f" || strlen($excluida) == 0) {
						if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18)){
							if($nota_fiscal_saida > 0 OR ($valores_adicionais == 0 AND $nota_fiscal_saida == 0))
								$html .=  "<a href='javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }'><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
						}else{
							if($login_fabrica==19){
								if($consumidor_revenda<>'R'){
									$html .=  "<a href='javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }'><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
								}
							}else{
								if($login_fabrica<>15){
									if($login_fabrica==11 and strlen($consumidor_email)>0 and $login_posto==14301){
										$html .=  "<a href='javascript: if(confirm('".traduz("esta.os.devera.ser.fechada.na.tela.fechamento.de.os",$con,$cook_idioma)."') == true) {window.location='os_fechamento.php?sua_os=$sua_os&btn_acao_pesquisa=continuar';}'><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
									}else{
										if ($login_fabrica == 20 and $login_posto == 6359) {
											$html .=  "<a href='#' onclick='fechaOSnovo($i);data_fechamento_$i.focus();'><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
										}
										else {
											//$html .=  $consumidor_revenda;
											if($consumidor_revenda=='R' and $login_fabrica == 11){
												#HD 111421 ----->
												$sua_os_x = $sua_os;
												$ache = "-";
												$posicao = strpos($sua_os_x,$ache);
												$sua_os_x = substr($sua_os_x,0,$posicao);
												#--------------->
												$html .=  "<a href='javascript: if(confirm('".traduz("os.revenda.devera.ser.fechada.na.tela.fechamento.de.os",$con,$cook_idioma)."') == true) {window.location='os_fechamento.php?sua_os=$sua_os_x&btn_acao_pesquisa=continuar';}'><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
											}else{
													$confirme = traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os));

													$html .=  "<a href=\"javascript: if (confirm('$confirme') == true) { 
															fechaOS ($os,sinal_$i,excluir_$i, document.getElementById('lancar')) ; }\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
											}
										}
									}
								}else{
									if($consumidor_revenda<>'R'){

										$html .=  "<a href='javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }'><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
									}else{
										$html .=  "<a href='javascript: if(confirm('".traduz("os.revenda.devera.ser.fechada.na.tela.fechamento.de.os",$con,$cook_idioma)."') == true) {window.location='os_fechamento.php?sua_os=$sua_os&btn_acao_pesquisa=continuar';}'><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
									}
								}
							}
						}
					}
				}else{
					if ($login_fabrica == 51 AND $status_os =='62') {
						$html .=  "<a href='javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,sinal_$i,'', '') ; }'><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
					}else{
						$html .=  "&nbsp;";
					}
				}
			
					$html .=  "</td>";
			
				
				if ($login_fabrica == 11 or $login_fabrica == 45 or $login_fabrica == 3){ //HD 13239
					$html .=  "<td width='60' align='center'>";
					//HD:44202
					if($login_fabrica == 3 AND ($status_os=="120" || $status_os=="122" || $status_os=="126" || $status_os=="140" || $status_os=="141" || $status_os=="143")){
						$html .=  "&nbsp;";
					}else{
						$os_troca = false;

						if ( (strlen($data_conserto) ==0) ) {

							$botao_consertado =  "<a href=\"javascript: if (confirm('".traduz("apenas.clicar.ok.se.tiver.certeza.que.a.data.de.conserto.do.produto.da.%.seja.hoje",$con,$cook_idioma,array($sua_os))."!') == true) { consertadoOS ($os,consertado_$i) ; }\"><img id='consertado_$i' border='0' src='/assist/imagens/btn_consertado.gif'></a>";

							if ($login_fabrica == 11){
								$sqlX ="SELECT os_troca,ressarcimento 
										FROM tbl_os_troca 
										WHERE os = $os";
								$resX = pg_query($con,$sqlX);
								if(pg_num_rows($resX)==1){
									$os_troca = true;
								}
								if ($os_troca == false){
									$html .=  $botao_consertado;
								}
							}else{
								$html .=  $botao_consertado;
							}
						}
					}

					$html .=  "</td>";
				}
				}
			$html .=  "</table>";
			$html .=  "</form>";

		##### PAGINAÇÃO - INÍCIO #####

		$html .=  "<div>";

		if($pagina < $max_links) $paginacao = pagina + 1;
		else                     $paginacao = pagina;

		// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		if (strlen($btn_acao_pre_os) ==0) {
			$todos_links = $mult_pag->Construir_Links("strings", "sim");
		}
		// função que limita a quantidade de links no rodape
		if (strlen($btn_acao_pre_os) ==0) {
			$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);
		}
		for ($n = 0; $n < count($links_limitados); $n++) {
			$html .=  "<font color=\"#DDDDDD\">".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		}

		$html .=  "</div>";
		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		if (strlen($btn_acao_pre_os) ==0) {
			$registros         = $mult_pag->Retorna_Resultado();
		}

		$valor_pagina   = $pagina + 1;
		if (strlen($btn_acao_pre_os) ==0) {
			$numero_paginas = intval(($registros / $max_res) + 1);
		}
		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;
		if ($registros > 0){
			$html .=  "<div>";
			$html .=  "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
			$html .=  "<font color=\"#cccccc\" size=\"1\">";
			$html .=  " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			$html .=  "</font>";
			$html .=  "</div>";
		}
		echo $html;
		}
	}
#=================================FIM OPCOES=========================================================="
			echo "<br>";
	
			echo "<table style='font-family: verdana, arial; font-size: 16px; border: 2px dotted #300; border-collapse: collapse; background-color: #FFFFFF;' width='600' border='0' align='center' cellpadding='0' cellspacing='0' bordercolor='#000000'>";
			echo "<TR class='Titulo'>";
			echo "	<TD colspan='2' align='center' nowrap height='30' ><FONT SIZE='3' COLOR='#FF0000'><B>";
			
			echo "Clique em um status abaixo para visualizar as Ordens de Serviço";
			
			echo "</B></FONT></TD>";
			echo "</TR>";
			echo "</table>";

			echo"<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
			echo "<tr>";
			echo"<td>";
			echo "<div align='left' style='position: relative; left: 10'>";
			echo "<table border='0' cellspacing='0' cellpadding='0'>";
			echo "<tr height='18'>";

			$sqlStatus = "SELECT count(*),os
							into TEMP tmp_os_vermelha_$login_posto
							FROM tbl_os
							WHERE defeito_constatado is null
							AND	  solucao_os is null
							AND posto = $login_posto
							AND fabrica = $login_fabrica
							AND data_conserto is NULL
							AND finalizada is NULL
							AND (excluida IS NULL OR excluida = 'f')
							group by os";
							
			$resStatus = pg_exec($con,$sqlStatus);
		//	echo nl2br($sqlStatus);
			$sqlStatus = "SELECT count(*) from tmp_os_vermelha_$login_posto";
			$resStatus = pg_exec($con,$sqlStatus);
			if(pg_num_rows($resStatus)>0) {
				$vermelho = pg_result($resStatus,0,0);
			} else {
				$vermelho = '0';
			}

			echo "<td width='18' ><img src='imagens/status_vermelho' width='10' align='absmiddle'/></td>";
			echo "<td align='left'><font size='2'><b>&nbsp;  <a href=\"javascript: mostrarOs('vermelho')\">";
			fecho ("os.aguardando.analise", $con, $cook_idioma); /*OS Aguardando Análise*/
			echo " ($vermelho)</a></b></font></td><BR>";
			echo "</tr>";
			
			$sqlStatus = "SELECT DISTINCT os
			into TEMP tmp_amerelo_os_$login_posto
			FROM tbl_os
			JOIN tbl_os_produto using (os)
			JOIN tbl_os_item USING (os_produto)
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			JOIN tbl_peca USING (peca)
			LEFT JOIN tbl_defeito USING (defeito)
			LEFT JOIN tbl_servico_realizado USING (servico_realizado)
			LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
			LEFT JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
			LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
			LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			WHERE
			tbl_os.posto = $login_posto
			AND tbl_os.fabrica = $login_fabrica
			AND tbl_os.finalizada is NULL
			AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
			AND data_conserto IS NULL
			AND tbl_os_item.peca not in (select peca from tbl_faturamento_item where tbl_faturamento_item.pedido = tbl_os_item.pedido)
			AND os not in (select os from tmp_os_vermelha_$login_posto)" ;
			$resStatus = pg_exec($con,$sqlStatus);
		
			$sqlStatus = "SELECT * FROM tmp_amerelo_os_$login_posto";
			$resStatus = pg_exec($con,$sqlStatus);
			if(pg_num_rows($resStatus)>0) {
				$amarelo = pg_num_rows($resStatus);
			} else {
				$amarelo = '0';
			}
			echo "<tr height='18'>";
			echo "<td width='18'><img src='imagens/status_amarelo' width='10' align='absmiddle'/></td>";
			echo "<td align='left'><font size='2'><b>&nbsp;  <a href=\"javascript: mostrarOs('amarelo')\">";
			fecho ("os.aguardando.peca", $con, $cook_idioma); /*OS Aguardando Peça*/
			echo " ($amarelo)</a></b></font></td>";
			echo "</tr>";
			
			$sqlStatus = "SELECT count(*)
							FROM tbl_os
							WHERE  posto = $login_posto
							AND   fabrica = $login_fabrica
							AND   finalizada is NULL
							AND   data_conserto is NULL
							AND   (excluida IS NULL OR excluida = 'f')
							AND os not in (select os from tmp_os_vermelha_$login_posto)
							AND os not in (select os from tmp_amerelo_os_$login_posto)";
							//echo nl2br($sqlStatus);
			$resStatus = pg_exec($con,$sqlStatus);
			if(pg_num_rows($resStatus)>0) {
				$rosa = pg_result($resStatus,0,0);
				if ($rosa<0) {
					$rosa = 0;
				}
			} else {
				$rosa = '0';
			}

			echo "<tr height='18'>";
			echo "<td width='18'><img src='imagens/status_rosa' width='10' align='absmiddle'/></td>";
			echo "<td align='left'><font size='2'><b>&nbsp;  <a href=\"javascript: mostrarOs('rosa')\">";
			fecho ("os.aguardando.conserto", $con, $cook_idioma); /*OS Aguardando Conserto*/
			echo " ($rosa)</a></b></font></td>";
			echo "</tr>";
			$sqlStatus = "SELECT count(*)
							FROM tbl_os
							WHERE posto = $login_posto
							AND fabrica = $login_fabrica
							AND finalizada is NULL
							AND data_conserto is not null
							AND (excluida IS NULL OR excluida = 'f')
							AND os not in (select os from tmp_os_vermelha_$login_posto)";
			
			$resStatus = pg_exec($con,$sqlStatus);
			if(pg_num_rows($resStatus)>0) {
				$azul = pg_result($resStatus,0,0);
			} else {
				$azul = '0';
			}
			echo "<tr height='18'>";
			echo "<td width='18'><img src='imagens/status_azul' width='10' align='absmiddle'/></td>";
			echo "<td align='left'><font size='2'><b>&nbsp;  <a href=\"javascript: mostrarOs('azul')\">";
			fecho ("os.consertada", $con, $cook_idioma); /*OS Consertada*/
			echo " ($azul)</a></b></font></td>";
			echo "</tr>";

			//$todas = $vermelho+$amarelo+$rosa+$azul;

			$sqlStatus = "SELECT count(*)
			FROM tbl_os
			WHERE
			posto = $login_posto
			AND fabrica = $login_fabrica
			AND finalizada is NULL
			AND (excluida IS NULL OR excluida = 'f')";
			$resStatus = pg_exec($con,$sqlStatus);
			if(pg_num_rows($resStatus)>0) {
				$todas = pg_result($resStatus,0,0);
			}


			echo "<tr height='18'>";
			echo "<td width='18'></td>";
			echo "<td align='left'><font size='2'><b>&nbsp;  <a href=\"javascript: mostrarOs('todas')\">";
			fecho ("todas", $con, $cook_idioma); /*Todas*/
			echo " ($todas)</a></b></font></td>";
			echo "</tr>";
			echo "</table>";
			echo "</div>";
			echo "</td>";
			echo "</tr>";
			echo"</table>";
			echo "<BR>";

include "rodape.php";
?>
