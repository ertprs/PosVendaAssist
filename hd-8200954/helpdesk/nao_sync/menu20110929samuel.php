<?
$sql = "SELECT admin
		from tbl_admin
		where admin = $login_admin
		and fabrica = $login_fabrica
		AND responsabilidade = 'Analista de Help-Desk'";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){ //verifica se eh supervisor do hd da telecontrol
	$analista_hd = "sim";
}

//VERIFICA SE O USUÁRIO É SUPERVISOR
$sql="  SELECT * FROM tbl_admin
		WHERE admin=$login_admin
		AND help_desk_supervisor='t'";

$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	$supervisor='t';
}
$suporte=432;

$filtro = array("<input ", "<form", "</form" );
?>
<html>
<head>
<title><?= $TITULO ?></title>
<link type="text/css" rel="stylesheet" href="css/css.css" />
<link type="text/css" rel="stylesheet" href="css/menu.css" />
<link href="css/styles_navigation.css" rel="stylesheet" type="text/css" />
<link href="/assist/imagens/tc_2009.ico" rel="shortcut icon" />

<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="../js/jquery.maskedinput.js"></script>

<script>
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
	background: #273977 url('/img/bntBg.gif') repeat-x;
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
 	janela =	 window.open("chat/index.php","_blank","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=350,height=500,top=18,left=0");	
	janela.focus();
}
</script>
</head>
<body bgcolor='#ffffff' marginwidth='0' marginheight='0' topmargin='0' leftmargin='0' onload='<?= $ONLOAD ?>'>

<?
$atende='chamado';
if ($login_fabrica == "10"){ 
	$prefixo = 'adm_';
	$pref= '_insere';

	if($login_admin==$suporte){
		$atende='chamado';
	}else{
		$atende='atendimento';
	}
}

?>
<table width='100%'  border='0'height='83' cellpadding='0' cellspacing='0' border='0'>
	<tr bgcolor='#FFFFFF' valign='middle'>
		<?  #HD 176617
			echo "<td width='100%' class='Degrade'>";
				echo "<img src='imagem/logo.gif'>";
			echo "</td>";
			$bg = "bgcolor ='white'";
			$color = "#000000";
			$imagem = "navM.gif";
			$txtcolor = "#ffffff";
		?>

		<?
		if($login_fabrica<>10){
			//HD 53936 adicionado background='imagem/fundo_dh2.jpg'
			?>
			<td align='left' nowrap class='Degrade'>
				<form method='post' action='<?= $prefixo ?>chamado_lista.php' name='frm_pesquisa'>
					<font face='arial' size='-2' color='#000000'>
						<? if($sistema_lingua == "ES") 
							echo "Buscar en el Help-Desk"; 
						else 
							echo " Procurar no Help-Desk";?>
					</font>
					<br>
					<input type='text' name='titulo' size='30' maxlength='100' class='caixa'> 
					<input type='submit' name='btn_pesq' value=' IR ' class='botao'>
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
			<img src='/assist/imagens/pixel.gif' width='20' height='1'>
			<font color='<?=$txtcolor?>' face='arial' >
			<b>Help-Desk <? if($TITULO) echo "» <FONT SIZE='1'>".$TITULO."</font>";?></b></font>
		</td>
	</tr>
</table>

<table width='100%' cellpadding='0' cellspacing='0' border='0'>
	<tr bgcolor = '#666666' valign='middle'><td><img src='/assist/imagens/pixel.gif' height='1'></td></tr>
</table>

<table width='100%' cellpadding='0' cellspacing='0' border='0'>
	<tr  bgcolor = '#eeeeee' valign='middle'>
		<td>
		<table height='15' cellpadding='3' cellspacing='0' border='0'>
		<tr valign='middle' style='font-family: arial ; font-size: 11px '>
			<td align='center' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'">
				<?if ($login_fabrica == 10) {?>
					<ul id="cssdropdown">
						<li class="mainitems"  style="width:100px;">
							<a href="#">
								<font color='black'>CHAMADOS</font>
							</a>
									<ul class="subuls" style="">
									<li><a href='<?= $prefixo ?><?=$atende?>_lista.php' ><?if($sistema_lingua=='ES')echo "Lista de llamados";else echo "Meus Chamados";?></a></li>
									<li><a href='adm_chamado_lista.php'>Todos Chamados</a></li>
									<li><a href='adm_chamado_lista_novo.php'>Todos chamados<br>(NOVA TELA)</a></li>
									<li><a href='<?= $prefixo ?>chamado_detalhe<?= $pref ?>.php'  ><?if($sistema_lingua=='ES')echo "Nuevo llamado";else echo "Novo Chamado";?></a></li>
									<li><a href='hd_chamado_melhoria.php'  >Melhorias em Programas</a></li>
									<li><a href='hd_chamado_regra_interna.php'  >Regras internas</a></li>
									<?
									if($login_admin == 432 or $login_admin == 1222 or $login_admin == 57 or $login_admin == 1630 || $login_admin == 586 || $login_admin == 1819 || $login_admin == 1749 || $login_admin == 1544){
										echo "<li><a href='adm_chamado_lista_suporte.php'  >Posição SUPORTE</a></li>";
									}
									?>
									</ul>
						</li>
						<li class="mainitems"  style="width:20px;"><a href="#"> | </a></li>
						<li class="mainitems"  style="width:100px;">
							<a href="#">
								<font color='black'>SUPERVISOR</font>
							</a>
									<ul class="subuls" style="">
									<li><a href='supervisor.php'>Supervisor</a></li>
									<li><a href='ponto_digital.php' target='_blank'  >
									<font color='black'>PONTO DIGITAL</font></a></li>
									<?php 
									if($grupo_admin == 1 || $grupo_admin == 2 || $grupo_admin == 6){
									?>
									<li><a href='hd_chamado_filas_cadastro.php'>Cadastrar Fila</a></li>
									<?php 
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
											<a href='adm_consulta_programa.php' title='Busca os programas requisitados no help desk pelos Analistas da Telecontrol.'>Consulta Programas Requisitados
											</a>
										</li>
										<li>
											<a href='adm_relatorio_fabricas.php' title='Mostra os perls rodados.'>Relatório Perls
											</a>
										</li>
									</ul>
						</li>
						<li style="width:10px;">
							<a href="#"> | </a>
						</li>
						<li class="mainitems" style="width:105px;">
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
						<li class="mainitems"  style="width:20px;"><a href="#"> | </a></li>
						<li class="mainitems"  style="width:100px;">
							<a href='adm_chamado_telefone.php?acao=INICIAR_ATENDIMENTO' >
								<font color='black'>TELEFONE</font>
							</a>
						</li>
						<li class="mainitems"  style="width:20px;"><a href="#"> | </a></li>

						<li class="mainitems"  style="width:80px;">
							<a href='idioma.php' target='_blank'  >
							<font color='black'>IDIOMA</font>
							</a>
						</li>
					</ul>
				</td>

				<?} else {?>
					<?if(strlen($prefixo)>0) {?>
						<a href='<?= $prefixo ?><?=$atende?>_lista.php' style='text-decoration: none ; color: #000000 '><?if($sistema_lingua=='ES')echo "Lista de llamados";else echo "Meus Chamados";?></a></b></td>
					<?}else{?>
						<a href='<?= $prefixo ?><?=$atende?>_lista.php?status=Análise&exigir_resposta=t' style='text-decoration: none ; color: #000000 '><?if($sistema_lingua=='ES')echo "Lista de llamados";else echo "Lista de Chamados";?></a></b></td>
					<?}
				}?>

		<? if($login_fabrica <>10) { ?>
			<td align='center'><font color='#666666'> | </font></td>
				<td align='center' width='100' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'"><a href='<?= $prefixo ?>chamado_detalhe<?= $pref ?>.php'  style='text-decoration: none ; color: #000000 '><?if($sistema_lingua=='ES')echo "Nuevo llamado";else echo "Novo Chamado";?></a></b></td>
			<? } ?>
		<?if($login_fabrica<>10 ){ if($sistema_lingua<>'ES'){?>

			<td align='center'><font color='#666666'> | </font></td>
			<td align='center' width='120' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'"><a href='supervisor.php' style='text-decoration: none ; color: #000000 '>Supervisor</a></b></td>

			<td align='center'><font color='#666666'> | </font></td>
			<td align='center' width='120' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'"><a href='senha_cadastro.php' style='text-decoration: none ; color: #000000 ' title='Clique aqui para alterar a sua senha de acesso ao sistema'><?if($sistema_lingua=='ES')echo "Cambiar Clave";else echo "Alterar Senha";?></a></b></td>


	<?}}?>

	<?
	if( $login_fabrica<>10){
	?>
			<td align='center'><font color='#666666'> | </font></td>

			<td align='center' width='130' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'"><a href='adm_senhas.php' style='text-decoration: none ; color: #000000 '><?if($sistema_lingua=='ES')echo "Clave de Servicios";else echo "Senhas dos Postos";?></a></b></td>

			<td align='center'><font color='#666666'> | </font></td>

			<td align='center' width='130' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'"><a href='help_cadastro.php' style='text-decoration: none ; color: #000000 '><?if($sistema_lingua=='ES')echo "Ayuda de Páginas
			";else echo "Ajudas de Telas";?></a></b></td>
	<?
	}
	if( $login_fabrica==10){
	?>


			<!--
			<td align='center' width='130' class='cell_out' onmouseover="this.className='cell_over'" onmouseout="this.className='cell_out'"><a href="javascript:abrir_chat();" style='text-decoration: none ; color: #000000 '>Suporte On-Line</a></b></td>
			<td align='center'><font color='#666666'> | </font></td>
	-->

	<?}?>


		</tr>
		</table>
		</td>
	</tr>
</table>

<table width='100%' cellpadding='0' cellspacing='0' border='0'>
	<tr bgcolor = '#666666' valign='middle'><td><img src='/assist/imagens/pixel.gif' height='1'></td></tr>
</table>

<table width='100%' cellpadding='0' cellspacing='0' border='0'>
	<tr bgcolor = '#ffffff' valign='middle'><td><img src='/assist/imagens/pixel.gif' height='1'></td></tr>
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
<br>
