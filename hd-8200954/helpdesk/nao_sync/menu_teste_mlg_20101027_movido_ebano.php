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
<link type="text/css" rel="stylesheet" href="css/css.css">
<link type="text/css" rel="stylesheet" href="css/menu.css">
<link href="css/styles_navigation.css" rel="stylesheet" type="text/css" />
<SCRIPT LANGUAGE="JavaScript">
<!-- Begin
function popUp(URL) {
day = new Date();
id = day.getTime();
eval("page" + id +"= window.open(URL, '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300,left = 262,top = 134');");
}
// End -->
</script>

<style>

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
</style>
<script language="Javascript1.2">

<!--

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

// -->

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

	url = "ajax_programa_uso?ajax=sim&arquivo="+escape(solicita)+"&finaliza="+escape(finaliza)+"&hd_chamado="+escape(hd_chamado);

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
<link type="text/css" rel="stylesheet" href="css/css.css">

<script type="text/javascript" src="../js/jquery-1.2.1.pack.js"></script>
<script type="text/javascript" src="../js/jquery.maskedinput.js"></script>


<?
$atende='chamado';
if ($login_fabrica == "10"){ $prefixo = 'adm_';
$pref= '_insere';
if($login_admin==$suporte){$atende='chamado';
}else{$atende='atendimento';}
}

?>
<table width='100%'  border='0'height='83' cellpadding='0' cellspacing='0' border='0'>
<tr bgcolor='#FFFFFF' valign='middle'>
		<?
// Sinto muito, mas tem versão nova... ;)
// 		if($login_admin == 822 or $login_admin ==1553) {
// 			echo "<td width='100%' align='left' ><img src='imagem/tc_logo_2009_md-trans.png'></td>";
// 			$bg = "bgcolor ='white'";
// 			$color = "#000000";
// 			$imagem = "fundo_dh73.jpg";
// 			$txtcolor = "#000099";
// 		} else {
			echo "<td width='100%' align='left' background='imagem/fundo_dh5.jpg'>&nbsp;</td>";
			$bg = "background='imagem/fundo_dh2.jpg'";
			$color = "#FFFFFF";
			$imagem = "fundo_dh7.jpg";
			$txtcolor = "#eeeeee";
// 		}
		?>
	
<?
if($login_fabrica<>10){
//HD 53936 adicionado background='imagem/fundo_dh2.jpg'
?>
	<form method='post' action='<?= $prefixo ?>chamado_lista.php' name='frm_pesquisa'>
	<td background='imagem/fundo_dh2.jpg' align='left' nowrap >
		<font face='arial' size='-2' color='#FFFFFF'><? if($sistema_lingua == "ES") echo "Buscar en el Help-Desk"; else echo " Procurar no Help-Desk";?></font><br>
		<input type='text' name='titulo' size='30' maxlength='100' class='caixa'> <input type='submit' name='btn_pesq' value=' IR ' class='botao'>
	</td>
	</form>
<?}else{
$sql = "SELECT admin,login,nome_completo
FROM tbl_admin
WHERE admin = $login_admin";
$res = @pg_exec ($con,$sql);
$analista_nome  = trim (pg_result ($res,0,nome_completo));
$analista_login = trim (pg_result ($res,0,login));
$analista_admin = trim (pg_result ($res,0,admin));

?>
	<form method='post' action='adm_trabalho_finalizado.php' name='frm_pesquisa'>
	<td align='left' nowrap <?=$bg?>>
<?
	echo "<font size='2' color='$color'>Login: <b>$analista_login</b><br>
	Nome: <b>$analista_nome</b><br></font>";
?>
	<input type='submit' name='BotaoTermino' value=' TERMINO DE TRABALHO' class='botao'>&nbsp;&nbsp;
	</td>
	</form>
<?}?>
</tr>
</table>


<table width='100%' cellpadding='0' cellspacing='0' border='0'>
<tr bgcolor = '#5175C9' valign='middle'>
<td alig='left' valign='middle' background='imagem/<?=$imagem?>'  height='25'>
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
							</div>
						</div>
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
					<li class="mainitems"  style="width:20px;"><a href="#"> | </a></li>

					<li class="mainitems"  style="width:120px;">
						<a href='ponto_digital.php' target='_blank'  >
								<font color='black'>PONTO DIGITAL</font>
						</a>
					</li>
				</ul>
			</div>
		</div>
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
<?
if($login_fabrica == 10){
	$sql = "SELECT count(hd_chamado) as total_interno
		FROM tbl_hd_chamado
		WHERE hd_chamado IN (
			SELECT hd_chamado FROM tbl_hd_chamado_item
			WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
			AND interno IS TRUE
			AND tbl_hd_chamado_item.admin <> $login_admin
			ORDER BY hd_chamado_item desc
			)
		AND ((tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido' AND tbl_hd_chamado.status <> 'Aprovação'))
		AND atendente = $login_admin;";
	$res = @pg_exec ($con,$sql);

	if (@pg_numrows($res) > 0) {
		$xtotal_interno = pg_result($res,0,total_interno);

		if($xtotal_interno >0){
		//	echo "<div style='position: absolute; top: 160px; right: 5px;opacity:.85;' class='Chamados'><center><img src='../admin/imagens_admin/star_on.gif' title='Contém chamado interno' border='0'>  Você tem<br><b>$xtotal_interno</b><br>chamado(s) internos</center></div>";

			$sql = "SELECT hd_chamado as chamado_interno
			FROM tbl_hd_chamado
			WHERE hd_chamado IN (
				SELECT hd_chamado FROM tbl_hd_chamado_item
				WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
				AND interno IS TRUE
				AND tbl_hd_chamado_item.admin <> $login_admin
				ORDER BY hd_chamado_item desc
				)
			AND ((tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido'))
			AND atendente = $login_admin;";

			$res = @pg_exec ($con,$sql);
			$chamado_interno = array();
			for($i = 0 ; $i < pg_numrows($res) ; $i++){
				array_push($chamado_interno,pg_result($res,$i,chamado_interno));

			}
		}
	}
	$sqlmeuchamado =	"SELECT count(*) AS total_meuchamado
						FROM tbl_hd_chamado
						WHERE (status NOT ILIKE 'Resolvido'
						AND status NOT ILIKE 'Cancelado'
						AND status NOT ILIKE 'Aprovação'
						OR status IS NULL)
						AND atendente = $login_admin";
	$resmeuchamado     = pg_exec ($con,$sqlmeuchamado);
	$xtotal_meuchamado = pg_result($resmeuchamado,0,total_meuchamado);

	if ($xtotal_meuchamado > 0) {
	//	echo "<div style='position: absolute; top: 105px; right: 5px;opacity:.85;' class='Chamados2' width='200'><CENTER>Você tem<br> <b>$xtotal_meuchamado</b><br> chamados pendentes.</CENTER></div>";
	}
	if ($login_fabrica == 10) {
	//	echo "<div style='position: absolute; top: 217px; right: 5px;opacity:.85;' class='Chamados2' width='200'><CENTER>Atalho para <a href='adm_chamado_lista.php'>Suporte</a>.</CENTER></div>";
	}

}?>
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

?>
<script type="text/javascript" src="js/menu.js" ></script>

<br>

