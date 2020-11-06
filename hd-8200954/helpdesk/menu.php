<?php
if (strpos($_SERVER['PHP_SELF'], 'adm_') !== false and BS3 !== false and $_COOKIE['menu']!=='original') {
	include 'menu_bs.php';
} else {
	$analista_hd        = in_array($grupo_admin, array(1,2,7,9)) ? 'sim' : false;
	$grupo_admin_valida = (bool)$grupo_admin;
	$supervisor         = ($login_help_desk_supervisor == 't');
	$suporte            = 432;

//Verifica se o usuário está habilitado para usar a ferramenta de Chat.
function base64UrlEncode($_input) {
	return str_replace(array('=','+','/'),array('_','-',','),base64_encode($_input));
}

//Verifica se o usuário está habilitado para usar a ferramenta de Chat.
if ($login_live_help) {
	$habilita_chat = true;
	$chat_nome     = base64UrlEncode($login_nome_completo);
	$chat_email    = base64UrlEncode($login_email);
	$chat_fabrica  = base64UrlEncode($fabrica_nome);
}
$analista_nome  = trim($login_nome_completo);
$analista_login = trim($login_login);
$analista_admin = trim($login_admin);

$filtro = array("<input ", "<form", "</form" );
?>
<html>
<head>
<title><?= $TITULO ?></title>
<link type="text/css" rel="stylesheet" href="css/css.css" />
<link type="text/css" rel="stylesheet" href="css/menu.css" />
<link type="text/css" rel="stylesheet" href="css/styles_navigation.css" />
<link href="../imagens/tc_2009.ico" rel="shortcut icon" />

<?php
include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */

if ($login_fabrica == 10) {
	if(in_array($grupo_admin,array(2,4))){
		echo "<script src='notificacao_inicio_trabalho.js'></script>";
	}else if(in_array($grupo_admin,array(1)) AND !in_array($login_admin,array(1222)) AND basename($_SERVER['PHP_SELF']) == "adm_atendimento_lista.php"){
		echo "<script src='notificacao_inicio_trabalho.js'></script>";
	}else if($login_admin == 586 AND basename($_SERVER['PHP_SELF']) == "adm_chamado_lista_suporte.php"){
		 echo "<script src='notificacao_inicio_trabalho.js'></script>";
	}
}

?>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.js"></script>
<script>

$(function(){
	Shadowbox.init();
	
	var nameStorage = 'dataComunicadoWhatsapp';
	var dataHoje = new Date().asString();
	var dataArmazenada = localStorage.getItem(nameStorage);
	
	if( dataHoje === dataArmazenada ){
		return;
	}

	function shadowboxOnClose(){
		localStorage.setItem(nameStorage, dataHoje);
	}

	// Comunicado TeleZap
	var configShadowboxComunicado = {
		content: "<div> <img src='../imagens/arte-telezap.jpg' style='width: 100%;' /> </div>",
		player: 'html',
		width: 1024,
		height: 800,
		options: {
			onClose: shadowboxOnClose			
		}
	};
	
	window.setTimeout(function(){
		Shadowbox.open(configShadowboxComunicado);
	}, 1500);
});

function saibaMaisTelezap() {

	Shadowbox.init();
	
	var nameStorage = 'dataComunicadoWhatsapp';
	var dataHoje = new Date().asString();
	
	function shadowboxOnClose(){
		localStorage.setItem(nameStorage, dataHoje);
	}

	// Comunicado TeleZap
	var configShadowboxComunicado = {
		content: "<div> <img src='../imagens/arte-telezap.jpg' style='width: 100%;' /> </div>",
		player: 'html',
		width: 1024,
		height: 800,
		options: {
			onClose: shadowboxOnClose			
		}
	};
	
	Shadowbox.open(configShadowboxComunicado);

}

function cadastrarCelularWhatsapp(){
	$('#cadastro-celular-shadowbox').css('display', 'block');

	var configShadowbox = {
		content: "./menu_celular_telezap.php",
		player: 'iframe',
		width: 500,
		height: 350
	};

	Shadowbox.open(configShadowbox);
}

function redirecionarUsuario(){
	var urlWhatsapp = 'https://web.whatsapp.com/send?1=pt_BR&phone=551499605-6588';
	
	$('#img-whatsapp').remove();
	Shadowbox.close();
	window.open(urlWhatsapp);

	// Cria uma imagem com o icone do telezap
	var image = document.createElement('img');
	image.src = 'imagens/telezap.png';
	image.width  = image.height = 100;
	image.style.cursor = 'pointer';
	image.border = 0;
	image.onclick = function(){
		window.open(urlWhatsapp);
	}

	// Adiciona a imagem com o link de redirect
	$('#box-telezap-icone').append(image);
}

function popUp(URL) {
	day = new Date();
	id = day.getTime();
	eval("page" + id +"= window.open(URL, '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300,left = 262,top = 134');");
}
</script>
<style>
.Degrade{
	margin: 10px;
	padding: 10px;
	background: #fff url('imagem/TopoBg.gif') repeat-x;
}
.Sucesso{
	background-color:#339900;
	color:#FFFFFF;
	font-family:Arial;
}
.change_log{
	background-color:#FFCC00;
	color:#330000;
	font-family:Arial;
	font-size: 20px;
}
.botao {
	background: #273977 ;
	color: white;
	cursor: pointer;
	margin: 0;
	padding: 0 3px 2px 3px;
	height: 22px;
	border:  1px solid #fff;
	outline: 1px solid #ccc;
	font-size: 10px;
	text-align: center;
}
div#chat_link {
	float: right;
	right: 200px;
	top: 10px;
	position: relative;
	border: 0;
}
</style>
<script>
_editor_url = "editor/";

var win_ie_ver = parseFloat(navigator.appVersion.split("MSIE")[1]);

if (navigator.userAgent.indexOf('Mac')        >= 0)
	win_ie_ver = 0;

if (navigator.userAgent.indexOf('Windows CE') >= 0)
	win_ie_ver = 0;

if (navigator.userAgent.indexOf('Opera')      >= 0)
	win_ie_ver = 0;

if (win_ie_ver >= 5.5) {
	 document.write('<scr' + 'ipt src="' +_editor_url+ 'editor.php"');
	 document.write(' language="Javascript1.2"></scr' + 'ipt>');
}
else
{
	document.write('<scr'+'ipt>function editor_generate() { return false; }</scr'+'ipt>');
}
</script>
<script language='javascript' src='../ajax.js'></script>
<script language='javascript'>

function retornaExibe(http,componente) {
	var com = document.getElementById(componente);
	if (http.readyState == 1) {
		com.innerHTML = "&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='../imagens/carregar_os.gif' >";
	}

	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = results[1];
					document.getElementById('conteudo').innerHTML = "";
					if (typeof results[2] != "undefined" && results[2] == "atualizar") {
						location.reload(true);
					}
				}else{
					com.innerHTML   = "<h4>Ocorreu um erro</h4>";
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}

function Exibir (componente,solicita,finaliza,hd_chamado) {
	url = "ajax_programa_uso.php?ajax=sim&arquivo="+escape(solicita)+"&finaliza="+escape(finaliza)+"&hd_chamado="+escape(hd_chamado);
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaExibe (http,componente,solicita) ; } ;
	http.send(null);
}

function abrir_chat(){
 	janela =window.open("chat/index.php","_blank","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=350,height=500,top=18,left=0");
	janela.focus();
}
</script>
</head>
<body bgcolor='#ffffff' marginwidth='0' marginheight='0' topmargin='0' leftmargin='0' onload='<?= $ONLOAD ?>'>

<?
$atende='chamado';
if ($login_fabrica == "10"){
	if($login_admin==$suporte){
		$atende='chamado';
	}else{
		$atende='atendimento';
	}

	if (empty($grupo_admin_valida)) {
		$prefixo = '';
		$atende='chamado';
	} else {
		$prefixo = 'adm_';
		$pref= '_insere';
	}

}

$sqlAdminWhats = "SELECT pais, whatsapp FROM tbl_admin where admin = $login_admin";
$res   = pg_query($con, $sqlAdminWhats);
$pais_admin = pg_fetch_result($res, 0, 'pais');
$whatsapp_admin = pg_fetch_result($res, 0, 'whatsapp');

?>
<table border='0'height='83' cellpadding='0' cellspacing='0' border='0'>
	<tr bgcolor='#FFFFFF' valign='middle'>
		<td width='100%' class='Degrade'>
			<img src='imagem/logo.gif' rel='nozoom' />
		</td>
		<?php if ($pais_admin == "BR") { ?> 
			<td class='Degrade'>
				<div style="display: flex; align-items: center" id="box-telezap-icone">
					<div style="font-size: 12px; font-weight: bold; width: 200px; margin-right: 15px; text-align: center">
						 Adicione o TeleZap aos seus contatos do WhatsApp: <br/> <span style="color: tomato"> +55 14 99605-6588 </span> <br><br/>
						 <span><a style="cursor: pointer;" class="btn btn-warning" onclick="saibaMaisTelezap()">Saiba mais</a></span>
					 </div>
					<?php if (!empty($whatsapp_admin)) { ?>
						<img src="imagens/telezap.png" id="img-whatsapp"  width="100" height="100" border="0" alt="" onclick="javascript:void(window.open('https://web.whatsapp.com/send?1=pt_BR&phone=551499605-6588'))" style="cursor: pointer"> 
					<?php } else { ?>
						<img src="imagens/telezap.png" id="img-whatsapp" width="100" height="100" border="0" alt="" onclick="cadastrarCelularWhatsapp()" style="cursor: pointer">
					<?php } ?>	
			    </div>
			</td>
		<?php } ?>
		<?  #HD 176617
		$bg = "bgcolor ='white'";
		$color = "#000000";
		$imagem = "navM.gif";
		$txtcolor = "#ffffff";
		if ($login_fabrica <> 10) {
			//HD 53936 adicionado background='imagem/fundo_dh2.jpg'
			if ($habilita_chat) {
			?>
			    <td class="degrade" align="left">


                            <div style="text-align:center;width:238px;">

			    <?php
			    if (strtotime(date('Y-m-d H:i')) >= strtotime(date('Y-m-d 09:00')) && strtotime(date('Y-m-d H:i')) <= strtotime(date('Y-m-d 17:30'))) {
			            if (!empty($login_email)) {
		   	  	    ?>
			                    <a href="javascript:void(window.open('https://tchat.telecontrol.com.br/livechat/084f77e7ff357414d5fe4a25314886fa312b2cff?email=<?=$login_email?>&nome=<?=$login_nome_completo?>&admin=<?=$login_login?>&fabrica=<?=$login_fabrica?>'))">
			            <?php
			            } else {
			            ?>
			                    <a href="javascript:void(alert('Para acessar o Chat é necessário que o usuário tenha um e-mail cadastrado.'))">
			            <?php
			            }
			            ?>
			                    <img src="imagens/chat-online.png" width="238" height="103" border="0" alt="">
			            </a>
				    <?php
			    } else {
			    ?>
			            <img src="imagens/chat-offline.png" width="238" height="103" border="0" alt="">
			    <?php
			    }
			    ?>
			    </div>
			    </td>
		<?	} ?>
			<td align='left' nowrap class='Degrade'>
				<form method='post' action='<?= $prefixo ?>chamado_lista.php' name='frm_pesquisa'>
					<font face='arial' size='-2' color='#000000'>
						<? if($sistema_lingua == "ES")
							echo "Buscar en el Help-Desk";
						else
							echo " Procurar no Help-Desk";?>
					</font>
					<br>
					<input type='text' name='titulo' size='30' maxlength='100' class='caixa' placeholder="Núm chamado ou parte do título">
					<input type='submit' name='btn_pesq' value=' IR ' class='botao'>
					<?php
						$sql_nome_fabrica = "SELECT nome FROM tbl_fabrica WHERE fabrica = $login_fabrica";
						$res_nome_fabrica = pg_query($con, $sql_nome_fabrica);
						$nome_fabrica = pg_fetch_result($res_nome_fabrica, 0, 'nome');
						echo "<br/><strong>".$nome_fabrica."</strong>";
					?>
				</form>

			</td>
		<?}else{
			$sql = "SELECT admin,login,nome_completo
					  FROM tbl_admin
					 WHERE admin = $login_admin";
			$res = @pg_exec ($con,$sql);
			$analista_nome  = trim (pg_result ($res,0,nome_completo));
			$analista_login = trim (pg_result ($res,0,login));
			$analista_admin = trim (pg_result ($res,0,admin));

			?>
			<td align='left' nowrap <?=$bg?> class='Degrade'>
				<form method='post' action='adm_trabalho_finalizado.php' name='frm_pesquisa'>
					<?
					echo "<font size='2' color='$color'>Login: <b>$analista_login</b><br>
					Nome: <b>$analista_nome</b><br></font>";
					?>
					<input type='submit' name='BotaoTermino' value=' TERMINO DE TRABALHO' class='botao'>&nbsp;&nbsp;
				</form>
			</td>
		<?}?>
</tr>
</table>

<table width='100%' cellpadding='0' cellspacing='0' border='0'>
	<tr bgcolor = '#5175C9' valign='middle'>
		<td align='left' valign='middle' background='imagem/<?=$imagem?>'  height='25'>
			<img src='../imagens/pixel.gif' width='20' height='1'>
			<font color='<?=$txtcolor?>' face='arial' >
			<b>Help-Desk <? if($TITULO) echo "» <FONT SIZE='1'>".$TITULO."</font>";?></b></font>
		</td>
	</tr>
</table>

<table width='100%' cellpadding='0' cellspacing='0' border='0'>
	<tr  bgcolor = '#eeeeee' valign='middle'>
		<td>
		<table height='15' cellpadding='3' cellspacing='0' border='0'>
		<tr valign='middle' style='font-family: arial ; font-size: 11px '>
			<td align='center' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'">
				<?if ($login_fabrica == 10 && !empty($grupo_admin_valida)) {?>
					<ul id="cssdropdown">
						<li class="mainitems"  style="width:100px;">
							<a href="#">
								<font color='black'>CHAMADOS</font>
							</a>
									<ul class="subuls" style="">
										<li><a href='<?= $prefixo ?><?=$atende?>_lista.php' ><?if($sistema_lingua=='ES')echo "Lista de llamados";else echo "Meus Chamados";?></a></li>

									<li><a href='adm_chamado_lista.php'>Todos Chamados</a></li>
									<li><a href='relatorio_chamados_erro.php'>Chamados de Erro</a></li>
									<li><a href='relatorio_chamados_cancelados.php'>Chamados Cancelados</a></li>
									<li><a href='<?= $prefixo ?>chamado_detalhe<?= $pref ?>.php'  ><?if($sistema_lingua=='ES')echo "Nuevo llamado";else echo "Novo Chamado";?></a></li>
									<!-- <li><a href='hd_chamado_melhoria.php'  >Melhorias em Programas</a></li> -->
									<!-- <li><a href='hd_chamado_regra_interna.php'  >Regras internas</a></li> -->
									<li><a href='adm_chamado_lista_suporte.php'  >Posição SUPORTE</a></li>
									<li><a href='adm_chamado_lista_gerencia.php'>Posição Gerencial</a></li>
									</ul>
						</li>
						<li class="mainitems"  style="width:20px;"><a href="#"> | </a></li>
						<li class="mainitems"  style="width:100px;">
							<a href="#">
								<font color='black'>SUPERVISOR</font>
							</a>
									<ul class="subuls" style="">
									<li><a href='supervisor.php'>Supervisor</a></li>
									<?php
									if(in_array($grupo_admin,array(1,2,5,6))){
									?>
									<li><a href='hd_chamado_filas_cadastro.php'>Cadastrar Fila</a></li>
									<li><a href='janela_helpdesk.php'>Cadastro de Janelas de HD</a></li>
									<li><a href='adm_relatorio_horas_faturadas.php'
										  title='Relatório de horas cobradas da franquia de cada fabricante. São considerados os chamados aprovados dentro do mês.' >Relatório de Horas Faturadas </a>
									</li>
									<?php
									}
									if ($login_fabrica == 10)
									{
										$sql = "SELECT admin FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $login_admin";
										$res = pg_query($con, $sql);

										if (pg_num_rows($res) > 0)
										{
											?>
												<li style="cusror: pointer;">
													<a href="relatorio_backlog.php" target="_blank">
														Relatório Backlog
													</a>
												</li>
												<li style="cusror: pointer;">
													<a href="relatorio_chamado.php" target="_blank">
														Relatório Chamado
													</a>
												</li>
											<?
										}
									}
									?>
									</ul>
						</li>

						<li class="mainitems"  style="width:20px;"><a href="#"> | </a></li>
						<li class="mainitems"  style="width:90px;"><a href="adm_suporte.php"><font color='black'>SUPORTE</font></a></li>
						<li style="width:20px;"><a href="#"> | </a></li>
						<li class="mainitems" style="width:115px;">
							<a class="" href="">
								<font color='black'>RELATÓRIOS</font>
							</a>
									<ul class="subuls" style="">
										<li>
											<a href='adm_producao_horas_cobradas.php' title='Relatório de horas cobradas da franquia de cada fabricante. São considerados os chamados aprovados dentro do mês.' >Relatório de Hora Cobrada </a>
										</li>
										<li>
											<a href='adm_producao_fabrica.php'  title='São considerados todas as interações de cada atendente cobrado ou não de cada fabricante'>Relatório Hora Trabalhada Fábrica
											</a>
										</li>
										<li>
											<a href='adm_producao_fabrica_adm.php' title='São considerados todas as interações de cada atendente cobrado ou não de cada fabricante'>Relatório Hora Trabalhada Atendente
											</a>
										</li>
										<li>
											<a href='adm_horas_utilizadas_fabricas.php' title='Consulta as horas utlizadas de fabricas do Mês atual'>Relatório Horas Utilizadas de fabricas
											</a>
										</li>
										<li>
											<a href='adm_relatorio_diario.php'>Relatório Diário
											</a>
										</li>
										<li>
											<a href='adm_rae.php'>Relatório Horas Analìtico</a>
										</li>
										<!-- <li>
											<a href='adm_consulta_programa.php' title='Busca os programas requisitados no help desk pelos Analistas da Telecontrol.'>Consulta Programas Requisitados
											</a>
										</li> -->
										<li>
											<a href='adm_relatorio_fabricas.php' title='Mostra os perls rodados.'>Relatório Perls
											</a>
										</li>
									</ul>
						</li>
						<li style="width:10px;">
							<a href="#"> | </a>
						</li>
					<!--<li class="mainitems" style="width:105px;">
							<a class="" href="#">
								<font color='black'>AGENDA</font>
							</a>
							<ul class="subuls" style="">
								<li>
									<a href='agenda.php' title='AGENDA DE TODOS ADMINS' >AGENDA GERAL</a>
								</li>
								<li>
									<a href='agenda_admin.php' title='MOSTRAR AGENDA DO SEU ADMIN'>AGENDA ADMIN</a>
								</li>
							</ul>
						</li>
						<li style="width:10px;">
							<a href="#"> | </a>
						</li>
						<li class="mainitems" style="width:105px;">
							<a class="" href="#">
								<font color='black'>CHANGE LOG</font>
							</a>
							<ul class="subuls" style="">
								<li>
									<a href='change_log_insere.php' title='INSERIR CHNAGE LOG' >NOVO CHANGE LOG
									</a>
								</li>
								<li>
									<a href='change_log_mostra.php' title='MOSTRAR OS CHANGE LOG AINDA NÃO LIDOS'>CHANGE LOG NÃO LIDOS
									</a>
								</li>
								<li>
									<a href='change_log_lida.php'  title='CHANGE LOG JÁ LIDOS'>CHANGE LOG LIDOS
									</a>
								</li>
							</ul>
						</li>
						<li class="mainitems"  style="width:20px;"><a href="#"> | </a></li>-->
						<li class="mainitems"  style="width:100px;">
							<a href='adm_chamado_telefone.php?acao=INICIAR_ATENDIMENTO' >
								<font color='black'>ABRIR HD</font>
							</a>
						</li>
						<li class="mainitems"  style="width:20px;"><a href="#"> | </a></li>

						<li class="mainitems"  style="width:80px;">
							<a href='idioma.php' target='_blank'  >
							<font color='black'>IDIOMA</font>
							</a>
						</li>
						<li class="mainitems"  style="width:20px;"><a href="#"> | </a></li>
						<li class="mainitems"  style="width:80px;">
							<a href='adm_painel.php' target='_blank'  >
							<font color='black'>KANBAN</font>
							</a>
						</li>					<li class="mainitems"  style="width:20px;"><a href="#"> | </a></li>
						<li class="mainitems"  style="width:80px;">
							<a href='../admin/backlog_cadastro.php' target='_blank'  >
							<font color='black'>BACKLOG</font>
							</a>
						</li>
                        <li class="mainitems"  style="width:20px;"><a href="#"> | </a></li>
                        <li class="mainitems"  style="width:160px;">
							<a href='monitoracron.php'>
							<font color='black'>MONITORAR ROTINAS</font>
							</a>
						</li>
						<?php
						if ($login_fabrica == 10) {
						        $sql = "SELECT grupo_admin FROM tbl_admin WHERE fabrica = {$login_fabrica} AND admin = {$login_admin}";
						        $res = pg_query($con, $sql);

						        $grupo_admin = pg_fetch_result($res, 0, "grupo_admin");

						        if (in_array($grupo_admin, array(1)) || in_array($login_admin, array(586))) {
							?>
								<li class="mainitems"  style="width:20px;"><a href="#"> | </a></li>
					                        <li class="mainitems"  style="width:200px;">
                                                       			 <a href='desenvolvedor_ausencia.php'><font color='black'>AUSÊNCIA DESENVOLVEDORES</font></a>
                                        		        </li>
							<?php
							}
						}
						?>
					</ul>
				</td>

				<?} else {?>
					<?if(strlen($prefixo)>0 && !empty($grupo_admin_valida)) {?>
						<a href='<?= $prefixo ?><?=$atende?>_lista.php' style='text-decoration: none ; color: #000000 '><?if($sistema_lingua=='ES')echo "Lista de llamados";else echo "Meus Chamados";?></a></b></td>
					<?}else{?>
						<a href='<?= $prefixo ?><?=$atende?>_lista.php?status=Análise&exigir_resposta=t' style='text-decoration: none ; color: #000000 '><?if($sistema_lingua=='ES')echo "Lista de llamados";else echo "Lista de Chamados";?></a></b></td>
					<?}
				}?>

		<? if(empty($grupo_admin_valida)) { ?>
			<td align='center'><font color='#666666'> | </font></td>
				<td align='center' width='100' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'"><a href='<?= $prefixo ?>chamado_detalhe<?= $pref ?>.php'  style='text-decoration: none ; color: #000000 '><?if($sistema_lingua=='ES')echo "Nuevo llamado";else echo "Novo Chamado";?></a></b></td>
			<? } ?>
		<?if(empty($grupo_admin_valida)){ if($sistema_lingua<>'ES' || in_array($login_fabrica,[180,181,182])) {?>

			<td align='center'><font color='#666666'> | </font></td>
			<td align='center' width='120' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'"><a href='supervisor.php' style='text-decoration: none ; color: #000000 '>Supervisor</a></b></td>

			<!-- comentado no //hd_chamado=2728371 <td align='center'><font color='#666666'> | </font></td>
			<td align='center' width='120' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'"><a href='senha_cadastro.php' style='text-decoration: none ; color: #000000 ' title='Clique aqui para alterar a sua senha de acesso ao sistema'><?if($sistema_lingua=='ES')echo "Cambiar Clave";else echo "Alterar Senha";?></a></b></td> -->


	<?}}?>

	<?
	if( $login_fabrica<>10){ //hd_chamado=2728371 comentados os links abaixo
	?>
			<!-- <td align='center'><font color='#666666'> | </font></td> -->

			<!-- <td align='center' width='130' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'"><a href='adm_senhas.php' style='text-decoration: none ; color: #000000 '><?if($sistema_lingua=='ES')echo "Clave de Servicios";else echo "Senhas dos Postos";?></a></b></td>

			<td align='center'><font color='#666666'> | </font></td> -->

			<!-- <td align='center' width='130' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'"><a href='help_cadastro.php' style='text-decoration: none ; color: #000000 '><?if($sistema_lingua=='ES')echo "Ayuda de Páginas
			";else echo "Ajudas de Telas";?></a></b></td> -->
	<?
	}
	if( $login_fabrica==10){
	?>


			<!--
			<td align='center' width='130' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'"><a href="javascript:abrir_chat();" style='text-decoration: none ; color: #000000 '>Suporte On-Line</a></b></td>
			<td align='center'><font color='#666666'> | </font></td>
	-->

	<?}
	/*
	 107 | PedidoWeb Orbis
     110 | PedidoWEB Mallory
      75 | PedidoWeb TK
      77 | PedidoWeb Dynacom
      78 | PedidoWeb Olivier
      76 | PedidoWeb Filizola
	 */
	if(in_array($login_fabrica, array(75,76,77,78,103,107,113))) {
	?>
			<td align='center'><font color='#666666'> | </font></td>
			<td align='center' width='130' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'"><a href="logout_pedidoweb.php" style='text-decoration: none ; color: #000000 '>SAIR DO HELPDESK</a></b></td>
	<?}?>
		</tr>
		</table>
		</td>
	</tr>
</table>

<hr style="margin: 0 auto; background-color: #666666; height: 1px; display: block; vertical-align: top;" />

<table width='100%' cellpadding='0' cellspacing='0' border='0'>
	<tr bgcolor = '#ffffff' valign='middle'><td><img src='../imagens/pixel.gif' height='1'></td></tr>
</table>
<?if(strlen($msg_erro)>0 OR strlen($msg) >0){
	if(strlen($msg_erro) >0){
		$msg_mostra=$msg_erro;
		$msg_class="Erro";
	}else{
		$msg_mostra=$msg;
		$msg_class="Sucesso";
	}

echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>
<tr bgcolor = '#ffffff' valign='middle' align='center' class='$msg_class'><td><b>$msg_mostra</b></td></tr>
</table>";
}

if ($login_fabrica == 10) {
?>
<script type="text/javascript" src="js/menu.js" ></script>
<?}?>
<map id='calendario_hd' name='calendario_hd'>
	<area shape='rect' nohref title='Reunião Mensal' alt='Reunião Mensal' coords='295,48,321,63'></area>
	<area shape='rect' nohref title='Reunião Mensal' alt='Reunião Mensal' coords='582,48,609,63'></area>
	<area shape='rect' nohref title='Reunião Mensal' alt='Reunião Mensal' coords='747,62,772,78'></area>
	<area shape='rect' nohref title='Reunião Mensal' alt='Reunião Mensal' coords='80,214,106,229'></area>
	<area shape='rect' nohref title='Reunião Mensal' alt='Reunião Mensal' coords='796,214,822,229'></area>
	<area shape='rect' nohref title='Reunião Mensal' alt='Reunião Mensal' coords='6,394,32,409'></area>
	<area shape='rect' nohref title='Reunião Mensal' alt='Reunião Mensal' coords='558,380,583,394'></area>
	<area shape='rect' nohref title='Reunião Mensal' alt='Reunião Mensal' coords='798,396,822,409'></area>
	<area shape='rect' nohref title='Janela para abertura de HDs desenvolvimento' alt='Janela para abertura de HDs desenvolvimento' coords='245,64,370,78'></area>
	<area shape='rect' nohref title='Janela para abertura de HDs desenvolvimento' alt='Janela para abertura de HDs desenvolvimento' coords='485,63,609,79'></area>
	<area shape='rect' nohref title='Janela para abertura de HDs desenvolvimento' alt='Janela para abertura de HDs desenvolvimento' coords='721,76,847,91'></area>
	<area shape='rect' nohref title='Janela para abertura de HDs desenvolvimento' alt='Janela para abertura de HDs desenvolvimento' coords='6,230,132,243'></area>
	<area shape='rect' nohref title='Janela para abertura de HDs desenvolvimento' alt='Janela para abertura de HDs desenvolvimento' coords='246,230,370,244'></area>
	<area shape='rect' nohref title='Janela para abertura de HDs desenvolvimento' alt='Janela para abertura de HDs desenvolvimento' coords='483,230,608,243'></area>
	<area shape='rect' nohref title='Janela para abertura de HDs desenvolvimento' alt='Janela para abertura de HDs desenvolvimento' coords='722,231,846,244'></area>
	<area shape='rect' nohref title='Janela para abertura de HDs desenvolvimento' alt='Janela para abertura de HDs desenvolvimento' coords='7,408,132,423'></area>
	<area shape='rect' nohref title='Janela para abertura de HDs desenvolvimento' alt='Janela para abertura de HDs desenvolvimento' coords='245,382,371,395'></area>
	<area shape='rect' nohref title='Janela para abertura de HDs desenvolvimento' alt='Janela para abertura de HDs desenvolvimento' coords='483,396,608,409'></area>
	<area shape='rect' nohref title='Janela para abertura de HDs desenvolvimento' alt='Janela para abertura de HDs desenvolvimento' coords='721,395,798,409'></area>
	<area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='247,92,277,106'></area>
	<area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='31,213,57,231'></area>
	<area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='106,394,131,409'></area>
	<area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='345,394,370,408'></area>
	<area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='586,379,609,394'></area>
	<area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='558,407,583,422'></area>
	<area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='746,437,771,452'></area>
	<!-- this map has been created with eleomap. http://dhost.info/eleomap/ -->
</map>

<br>
<?
$comunicado_tc_obrigatorio = true;

$sql_c_tc = "SELECT admin,data_confirmacao FROM tbl_comunicado_tc_leitura WHERE admin = $login_admin AND data_confirmacao > '2012-04-09'";
$res_c_tc = pg_query($con, $sql_c_tc);

if (pg_num_rows($res_c_tc) == 0 and file_exists('tc_comunicado_hd.php') and 1==2) {
	include 'tc_comunicado_hd.php';
	if ($comunicado_tc_obrigatorio) {
		include "rodape.php";
		exit();
	}
}
}

