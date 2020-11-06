<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$admin_privilegios="financeiro";
include "autentica_admin.php";


// AJAX -> solicita a exportação dos extratos
if (strlen($_GET['exportar'])>0){

	$emails_arr = array();

	if (!empty($_GET['emails'])) {

		foreach ($_GET['emails'] as $email) {
		    $emails_arr[] = $email;
		}
	
	}

	if(in_array($login_fabrica, array(20))){
		$tipo_exportacao = $_GET["tipo_exportacao"];
	}

	$emails = implode(",", $emails_arr);

	system("php ". __DIR__ ."/../rotinas/bosch/exporta-extrato.php $emails $tipo_exportacao", $ret);
	
	if ($ret == 0) {
	    $dados = "$login_fabrica\t$login_admin\t".date("d-m-Y H:m:s");
		exec ("echo '$dados' > /tmp/bosch/exporta/pronto.txt");
		echo "ok|Exportação concluída com sucesso! Dentro de alguns minutos os arquivos de exportação estarão disponíveis no sistema.";
	} else {
		$dados = "$login_fabrica\t$login_admin\t".date("d-m-Y H:m:s")."ERRO";
		exec ("echo '$dados' > /tmp/bosch/exporta/pronto.txt");
		echo "ok|Ocorreu um erro ($ret) ao exportar o extrato. O Suporte Telecontrol foi comunicado. O extrato será exportado após o problema ser solucinado.";
	}
	
	exit;
}
// FIM DO AJAX -> solicita a exportação dos extratos


//--== AJAX -> LIBERAR EXTRATO ==================================================--\\
if($_GET['ajax']=='sim') {

	$extrato  = $_GET['extrato'];
	$liberar  = $_GET['liberar'];

	if(strlen($liberar)>0){
		$sql = "UPDATE tbl_extrato SET liberado = current_date";
		if($login_fabrica == 6 OR $login_fabrica == 11 OR $login_fabrica == 24) $sql .= ", aprovado = current_date";
		$sql .= " WHERE extrato = $extrato";
		$res = @pg_exec($con,$sql);
		$resposta .= "Extrato $extrato foi liberado com sucesso";
	}
	echo  "ok|".$resposta;
	exit;
}
//--== AJAX -> LIBERAR EXTRATO ==================================================--\\

if(strlen($_GET['nao_extrato'])>0) {

	$extrato  = $_GET['nao_extrato'];
	
	$sql = "UPDATE tbl_extrato SET liberado = null";
	if($login_fabrica == 6 OR $login_fabrica == 11 OR $login_fabrica == 24) $sql .= ", aprovado = current_date";
	$sql .= " WHERE extrato = $extrato";
	$res = @pg_exec($con,$sql);
	$resposta .= "Extrato $extrato foi liberado com sucesso";

//	echo  "ok|".$resposta;
//	exit;
}

/*HD-15001 Contar OS*/
if($ajax=='conta'){
			$sql = "SELECT count(*) as qtde_os FROM tbl_os_extra WHERE extrato = $extrato";
			$rres = pg_exec($con,$sql);
			if(pg_numrows($rres)>0){
				$qtde_os = pg_result($rres,0,qtde_os);
			}
			echo "ok|$qtde_os";
			exit;
}

//--==== LIBERAR TODOS HD 38576 4/9/2008 ========================================================--\\
if ($btnacao == 'liberar_tudo'){
	if (strlen($_POST["total_postos"]) > 0) $total_postos = $_POST["total_postos"];
	
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	for ($i=0; $i < $total_postos; $i++) {
		$extrato    = $_POST["liberar_".$i];
		$imprime_os = $_POST["imprime_os_".$i];
		if (strlen($extrato) > 0 AND strlen($msg_erro) == 0) {
			$sql = "UPDATE tbl_extrato SET liberado = current_date WHERE  tbl_extrato.extrato = $extrato
					 and    tbl_extrato.fabrica = $login_fabrica;";
					 //echo $sql;
			$res = pg_exec($con,$sql);
			$msg_erro = @pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) $res = pg_exec($con,"COMMIT TRANSACTION");
	else                        $res = pg_exec($con,"ROLLBACK TRANSACTION");

}
//--========================================================================================--\\

$msg_erro = "";


if (strlen($_GET["liberar"]) > 0) $liberar = $_GET["liberar"];

if (strlen($liberar) > 0){


}


$layout_menu = "financeiro";
$title = "Liberação de Extratos";

include "cabecalho.php";

?>

<p>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.msg_erro{
	background-color:#FF0000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}
</style>

<script language='javascript' src='../ajax.js'></script>
<script language='javascript' src='js/jquery-latest.pack.js'></script>


<script language="JavaScript">


var checkflag = "false";
function check(field) {
	if (checkflag == "false") {
        for (i = 0; i < field.length; i++) {
            field[i].checked = true;
        }
        checkflag = "true";
        return true;
    }
    else {
        for (i = 0; i < field.length; i++) {
            field[i].checked = false;
        }
        checkflag = "false";
        return true;
    }
}

function AbrirJanelaObs (extrato) {
	var largura  = 400;
	var tamanho  = 250;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "extrato_status.php?extrato=" + extrato;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
function gerarExportacao(but){
	 if (but.value == 'Exportar Extratos' ) {
		if (confirm('Deseja realmente prosseguir com a exportação?\n\nSerá exportado somente os extratos aprovados e liberados.')){
			but.value='Exportando...';
			alert('A exportação poderá demorar. Por favor aguarde.');
			exportar();
		}
	} else {
		 alert ('Aguarde submissão');
	}

}

function retornaExporta(http) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					var btn = document.getElementById("btn_exportar");
					btn.value = "Exportar Extratos";
					alert(results[1]);
				}else{
					alert (results[1]);
				}
			}else{
				alert ("Não existe extratos a serem exportados.");
			}
		}
	}
}

function exportar() {
	var emails_param = '';

	if (document.getElementById('emails_input').value) {
		var emails = document.getElementById('emails_input').value;
		var arr_emails = emails.split(';');
		
		emails_param = '&';

		for (var i in arr_emails) {
			emails_param = emails_param + 'emails[]=' + arr_emails[i] + '&';
		}
	}

	var tipo_exportacao = "";

	<?php if(in_array($login_fabrica, array(20))){ ?>
	tipo_exportacao = document.querySelector("input[name='tipo_exportacao']:checked").value;
	<?php } ?>

	url = "<?= $PHP_SELF ?>?exportar=sim"+emails_param+"&tipo_exportacao="+tipo_exportacao;
	http.onreadystatechange = function () { retornaExporta(http) ; } ;
	http.open("GET", url , true);
	http.send(null);
}



function retornaLiberar(http,componente,componente2) {
	var com  = document.getElementById(componente);
	var com2 = document.getElementById(componente2);
	if (http.readyState == 1) {

		com.innerHTML = "&nbsp;&nbsp;liberando...&nbsp;&nbsp;";

	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.style.visibility = "visible";
					com.innerHTML   = results[1];
					com2.innerHTML  = "<font color='#009900'>OK</font>";
				}else{
					com.innerHTML   = "<h4>Ocorreu um erro</h4>";
				}
			}else{
				alert ('Liberação não processada');
			}
		}
	}
}

function Liberar (componente,componente2,extrato,liberar) {

	url = "?ajax=sim&extrato="+escape(extrato)+"&liberar="+escape(liberar);

	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaLiberar (http,componente,componente2) ; } ;
	http.send(null);
}

/*HD-15001 Contar OS*/
function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}
			
var http_forn = new Array();

function conta_os(extrato,div) {

	var ref = document.getElementById(div);
	ref.innerHTML = "Espere...";
	url = "<?=$PHP_SELF?>?ajax=conta&extrato="+extrato;
	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);
	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4) 
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) 
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
						ref.innerHTML = response[1];
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}

function contar_qtde_os(extrato,div,i){
		var botao = document.getElementById(div);
		botao.innerHTML='Espere...';
		$.ajax({
			type: "POST",
			url: "<? echo $PHP_SELF ?>",
			data: "ajax=conta&extrato="+extrato,
			success: function(msg){
				var mensagem = msg.split("|");
				if (mensagem[0] == 'ok'){
					botao.innerHTML = mensagem[1];
					i++;
					$("a[@name=qtde_os_link_"+i+"]").click();
				}
			}
		});
}

function adicionaEmailExporta() {
	var email = document.getElementById('exporta_email').value;
	var div_display = document.getElementById('emails_envia_exporta').style.display;

	if (div_display == "none") {
		document.getElementById('emails_envia_exporta').style.display = "block";
	}

	if (!document.getElementById(email)) {
		var div_email = document.createElement('div');
		div_email.setAttribute('id', email);
		div_email.setAttribute('style', 'margin-top: 3px;');
		var txt_email = document.createTextNode(email);
		div_email.appendChild(txt_email);
		var btn_excluir = document.createElement('span');
		btn_excluir.setAttribute('id', email);
		btn_excluir.setAttribute('onClick', 'exclui_email_exporta(this.id)');
		btn_excluir.setAttribute('style', 'cursor: pointer; padding-left: 5px;');
		var txt_x = document.createTextNode('[x]');
		btn_excluir.appendChild(txt_x);
		div_email.appendChild(btn_excluir);

		document.getElementById('emails_envia_exporta').appendChild(div_email);

		document.getElementById('emails_input').value = document.getElementById('emails_input').value + email + ';';
		
	}
	
}

function exclui_email_exporta(email) {
	var el = document.getElementById(email);
	var par = el.parentNode;
	par.removeChild(el);

	var emails = document.getElementById('emails_input').value;
	var search = email + ';';
	emails = emails.replace(search, '');
	document.getElementById('emails_input').value = emails;

}

</script> 

<?
	if($login_fabrica<>20)  $cond_1 = " AND       EX.liberado           IS NULL ";

	$sql = "
		SELECT  EX.extrato                                            ,
				EX.posto                                              ,
				EX.fabrica                                            ,
				EX.liberado                                           ,
				EX.aprovado                                           ,
				LPAD (EX.protocolo,5,'0')              AS protocolo   ,
				TO_CHAR (EX.data_geracao,'dd/mm/yyyy') AS data_geracao,
				EX.total                                              ,
				EP.valor_liquido                                      ,
				EE.nota_fiscal_devolucao                              ,
				EE.nota_fiscal_mao_de_obra                            ,
				TO_CHAR (EP.data_pagamento,'dd/mm/yyyy') AS baixado
		INTO TEMP tmp_libera_$login_admin
		FROM      tbl_extrato           EX
		JOIN      tbl_extrato_extra     EE ON EX.extrato = EE.extrato
		LEFT JOIN tbl_extrato_pagamento EP ON EX.extrato = EP.extrato
		WHERE     EX.fabrica = $login_fabrica
		AND       EX.aprovado  IS NOT NULL
		AND       EE.exportado IS     NULL
		$cond_1;

	CREATE INDEX tmp_libera_extrato_$login_admin ON tmp_libera_$login_admin(extrato);
	CREATE INDEX tmp_libera_posto_$login_admin ON tmp_libera_$login_admin(posto);
	CREATE INDEX tmp_libera_fabrica_$login_admin ON tmp_libera_$login_admin(fabrica);
		SELECT distinct  PO.posto                 ,
			PO.nome                      ,
			PO.cnpj                      ,
			PO.email                     ,
			PF.codigo_posto              ,
			PF.distribuidor              ,
			TP.descricao    AS tipo_posto,
			TE.extrato                   ,
			TE.liberado                  ,
			TE.aprovado                  ,
			TE.protocolo                 ,
			TE.data_geracao              ,
			TE.total                     ,
			TE.baixado                   ,
			TE.valor_liquido             ,
			TE.nota_fiscal_mao_de_obra   ,
			TE.nota_fiscal_devolucao
		FROM      tmp_libera_$login_admin TE
		JOIN      tbl_posto                       PO ON TE.posto      = PO.posto
		JOIN      tbl_posto_fabrica               PF ON TE.posto      = PF.posto      AND PF.fabrica = $login_fabrica
		JOIN      tbl_tipo_posto                  TP ON TP.tipo_posto = PF.tipo_posto AND TP.fabrica = $login_fabrica
		LEFT JOIN tbl_os_extra                    OE ON OE.extrato    = TE.extrato
		LEFT JOIN tbl_os                          OS ON OS.os         = OE.os         AND OS.posto   = TE.posto AND OS.fabrica = TE.fabrica		
		WHERE     PO.pais    = 'BR'
		AND       PF.distribuidor IS NULL 
		AND       TE.total >0
		ORDER BY PO.nome, TE.data_geracao";
//echo nl2br($sql);
//exit;
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<center><h2>Nenhum extrato aprovado para ser liberado</h2></center>";
	}
// echo "$sql";
	if (pg_numrows ($res) > 0) {

		echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='0' align='center'>";
		echo "<tr>";
		echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
		echo "<td align='left'><font size=1><b>&nbsp; Extrato Avulso</b></font></td>";
		echo "</tr>";
		echo "</table>";

		echo "<form name='Selecionar' method='post' action='$PHP_SELF'>\n";
		echo "<input type='hidden' name='btnacao' value=''>";
		echo "<br><table border='1' width ='700'align='center' cellspacing='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";

		echo "<tr class='Titulo'>\n";
		echo "<td colspan='11' background='imagens_admin/azul.gif' height='25'>Extratos Aprovados e Auditados para serem Liberados</td>\n";
		echo "</tr>";

		echo "<tr class='Titulo' height='25'>\n";
		echo "<td>Código</td>\n";
		echo "<td nowrap>Nome do Posto</td>\n";
		echo "<td>Extrato</td>\n";
		echo "<td>Data</td>\n";
		echo "<td nowrap>Qtde. OS</td>\n";
		echo "<td>Total Peça</td>\n";
		echo "<td>Total MO</td>\n";
		echo "<td>Total Avulso</td>\n";
		echo "<td>Total Geral</td>\n";
		echo "<td> </td>\n";
		if($login_fabrica==20){
			echo "<td align='center'>Marcar/Desmarcar <BR> <input type='checkbox' class='frm' name='marcar' value='tudo' title='Selecione ou desmarque todos' onClick='check(this.form.liberar);'></td>\n";
		}
		echo "</tr>\n";

		$funcao_contar_qtde_os_todos = "";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$posto          = trim(pg_result($res,$i,posto));
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$nome           = trim(pg_result($res,$i,nome));
			$email          = trim(pg_result($res,$i,email));
			$tipo_posto     = trim(pg_result($res,$i,tipo_posto));
			$extrato        = trim(pg_result($res,$i,extrato));
			$data_geracao   = trim(pg_result($res,$i,data_geracao));
			//$qtde_os        = trim(pg_result($res,$i,qtde_os));
			$total          = trim(pg_result($res,$i,total));
			$baixado        = trim(pg_result($res,$i,baixado));
			$extrato        = trim(pg_result($res,$i,extrato));
			$distribuidor   = trim(pg_result($res,$i,distribuidor));
			$total	        = number_format ($total,2,',','.');
			$liberado       = trim(pg_result($res,$i,liberado));
			$aprovado       = trim(pg_result($res,$i,aprovado));
			$protocolo      = trim(pg_result($res,$i,protocolo));

			if (trim(pg_result($res,$i,valor_liquido)) <> '') $valor_liquido = number_format (trim(pg_result($res,$i,valor_liquido)),2,',','.');
			else                                              $valor_liquido = number_format (trim(pg_result($res,$i,total)),2,',','.')        ;

			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
			if (strlen($extrato) > 0) {
				$sql = "SELECT count(*) as existe
						FROM   tbl_extrato_lancamento
						WHERE  extrato = $extrato
						and    fabrica = $login_fabrica";
				$res_avulso = pg_exec($con,$sql);

				if (@pg_numrows($res_avulso) > 0) {
					if (@pg_result($res_avulso, 0, existe) > 0) $cor = "#FFE1E1";
				}
			}


			echo "<tr bgcolor='$cor' class='Conteudo' height='20'>\n";

			echo "<td align='left'>$codigo_posto</td>\n";
			echo "<td align='left' nowrap>".substr($nome,0,20)."</td>\n";
			if ($login_fabrica == 1) echo "<td align='center' nowrap>$tipo_posto</td>\n";
			if($login_fabrica == 20)echo "<td align='center'><a href='extrato_os_aprova";
			else echo "<td align='center'><a href='extrato_consulta_os";
			if ($login_fabrica == 14) echo "_intelbras";
			echo ".php?extrato=$extrato&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome' target='_blank'>";
			if ($login_fabrica == 1 OR $login_fabrica == 19 ) echo $protocolo;
			else                     echo $extrato;
			echo "</a></td>\n";
			echo "<td align='left' nowrap>$data_geracao</td>\n";
			//HD 284169: Não estava contando a quantidade de OS quando clicado no link para mostrar todas as quantidades. Montei uma variável com todos os javascripts.
			$funcao_contar_qtde_os = "contar_qtde_os('$extrato','qtde_os_$i',$i); ";
			$funcao_contar_qtde_os_todos .= $funcao_contar_qtde_os;
			echo "<td align='center' title='Clique aqui para ver a quantidade de OS'><div id='qtde_os_$i'><a href=\"javascript:$funcao_contar_qtde_os\" name='qtde_os_link_$i'>???</a></div></td>";

			//HD 210531: Alterei a query, pois quando era extrato avulso estava fazendo JOIN e não retornava
			//			 nenhum resultado, não preenchendo a coluna avulso. Problemas, verifica nao_sync. Ébano
			$sql = "
			SELECT
			SUM(tbl_os.pecas) AS total_pecas,
			SUM(tbl_os.mao_de_obra) AS total_maodeobra,
			tbl_extrato.avulso AS total_avulso

			FROM
			tbl_extrato
			LEFT JOIN (
			tbl_os_extra
			JOIN tbl_os ON tbl_os_extra.os=tbl_os.os
			) ON tbl_extrato.extrato=tbl_os_extra.extrato

			WHERE
			tbl_extrato.extrato = $extrato
			GROUP BY tbl_extrato.avulso
			";
			$resT = pg_exec($con,$sql);

			if (pg_numrows($resT) == 1) {
				echo "<td align='right' nowrap> " . number_format(pg_result($resT,0,total_pecas),2,',','.') . "</td>\n";
				echo "<td align='right' nowrap> " . number_format(pg_result($resT,0,total_maodeobra),2,',','.') . "</td>\n";
				echo "<td align='right' nowrap> " . number_format(pg_result($resT,0,total_avulso),2,',','.') . "</td>\n";
			}
			//HD 210531: acertando o posicionamento das colunas em caso de não ter linhas no result
			else {
				echo "<td></td><td></td><td></td>";
			}
			echo "<td align='right' nowrap> $total</td>\n";
			echo "<td align='right' nowrap><div id='libera_$i'>";
			if(strlen($liberado == 0)) echo "<a href=\"javascript:Liberar('dados','libera_$i','$extrato','sim');\"> Liberar</div></a>";
			else                       echo "<a href='$PHP_SELF?nao_extrato=$extrato'>Não pagar</a>";

			echo "</td>\n";
			if($login_fabrica==20){
				echo "<td>\n";
					echo " <input type='checkbox' class='frm' name='liberar_$i' id='liberar' value='$extrato'>";
				echo "</td>\n";
			}
			echo "</tr>\n";
		}
		$extrato = trim(pg_result($res,0,extrato));
		echo "<thead>";
		echo "<tr><td colspan='11'>";
		echo "<a href=\"javascript: $funcao_contar_qtde_os_todos\">Ver a quantidade de OS em todos extratos</a>";
		echo "</td></tr>";
		echo "</thead>";
		if($login_fabrica==20){
		echo "<tr>";
			echo "<td colspan='10' align='right'>";
				echo "&nbsp;";
			echo "</td>\n";
			echo "<td align='center'>";
			echo "<input type='hidden' name='total_postos' value='$i'>";
			echo "<a href='javascript: document.Selecionar.btnacao.value=\"liberar_tudo\" ; document.Selecionar.submit() '><font size='2'>Liberar Selecionados</font></a>";

			echo "</td>\n";
		echo "</tr>";
		}
		echo "</table>\n";
		echo "<center><br><div id='dados' class='Exibe' style='visibility:hidden'></div></center>";


		echo "</form>\n";

		$queryAdminEmail = pg_query($con, "SELECT email FROM tbl_admin WHERE admin = $login_admin AND email <> '' AND email IS NOT NULL");
		if (pg_num_rows($queryAdminEmail) == 0) {
			echo '<br/>';
			echo '<center>';
				echo '<div class="quadro">';
					echo 'Para exportar os extratos é necessário <strong>ter email cadastrado no sistema.</strong>';
					echo '<a href="admin_senha.php">Clique aqui</a> para cadastrar.';
				echo '</div>';
			echo '</center>';
		} else {
			$admin_email = pg_fetch_result($queryAdminEmail, 0, 'email');

			if(in_array($login_fabrica, array(20))){

				?>

				<div style="text-align: left; width: 300px; border: 1px solid #cccccc; background-color: #dedede; padding: 20px; margin: 0 auto;">
					<label>
						<input type="radio" name="tipo_exportacao" value="valor_pecas_avulsos" checked="checked"> Valor das Peças + Avulsos 
					</label>
					<br />
					<label>
						<input type="radio" name="tipo_exportacao" value="valor_mo_pecas_avulsos"> Mão-de-Obra + Valor das Peças + Avulsos
					</label>
				</div>

				<?php

			}
			
			echo "<br><center><div class='quadro'><input type='button' name='btn_exportar' id='btn_exportar' class='botao' value='Exportar Extratos' onclick=\"javascript:gerarExportacao(this)\"><br>Só serão exportados os Extratos que foram <B>Aprovados e Liberados</b><br/><br/>";
			echo "O arquivo será enviado <strong>para o seu email.</strong> Caso deseje enviar para mais admins, selecione abaixo:<br/><br/>";
			
			echo '<select name="exporta_email" id="exporta_email">';
			$sqlEmails = "SELECT DISTINCT email FROM tbl_admin WHERE ativo = 't' AND fabrica = 20 AND pais = 'BR' AND email <> '' AND email IS NOT NULL ORDER BY email";
			$queryEmails = pg_query($con, $sqlEmails);
			while ($fetch = pg_fetch_array($queryEmails)) {
				echo '<option value="' , $fetch['email'] , '">' , $fetch['email'] , '</option>';
			}
			echo '</select>';
			echo '<input type="button" value="Adicionar Email" onClick="adicionaEmailExporta()" />';
			echo '<div id="emails_envia_exporta" style="display: none; margin-top: 10px;">';
				echo '<strong>Emails adicionais:</strong><br/>';
			echo '</div>';
			echo '<input type="hidden" value="' , $admin_email , ';" id="emails_input">';
			
			echo "</div></center>";
		}
	}

?>

<br>

<? include "rodape.php"; ?>
