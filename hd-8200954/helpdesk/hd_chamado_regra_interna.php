<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica <> 10){
	exit;
}
?>
<html>
<head>
<title>Telecontrol - Help Desk - REGRAS INTERNAS</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>
<style>
.tabela{
	font:bold;
}
.supervisor{
	font-size: 12px;
}

.supervisor ul{
	list-style-type:none;
	margin:0px;
}

</style>
<body>
<?
include "menu.php";
?>
<table width="700" align="center" bgcolor="#FFFFFF" border='0'>
	<tr>
		<td colspan="2" bgcolor="<?=$menu_cor_linha?>" width="1" height="1"></td>
	</tr>
	<tr>
		<td colspan="2" class="Conteudo" align="center">
		<?
		echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
			echo "<tr>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
				echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='1' align = 'center' width='100%' style='font-family: arial ; color:#666666' nowrap
				><CENTER>Regra b�sica de funcionamento do Help-Desk - COMO SUPERVISOR</CENTER></td>";
				echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='left'>
				Prezado Cliente,
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";				
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'>
				A Telecontrol, visando atender dentro de padr�es internacionais de qualidade, est� melhorando o seu atendimento via Help-Desk com rela��o ao controle do SLA de seus chamados.
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";				
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'>
				A F�brica dever� definir suas prioridades e indicar qual chamado dever� ser desenvolvido pela Telecontrol. Ap�s a indica��o do chamado o Supervisor verificar� facilmente se est� sendo atendido dentro dos prazos estipulados.
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";				
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" >";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='left'>
				Funcionamento do HELPDESK:				
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" >";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='left'><center><br>
				<img src='/assist/imagens/Processo.png' width='650'>
				<br></center>
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'>
				1) O chamado de erro continua tendo prioridade e n�o precisa de aprova��o, basta escolher a op��o �Erro de Programa�. 				
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'>
				2) O demais chamados, Backlog, ser�o gerenciados pelo(s) Supervisor(es) de Help-Desk de cada fabricante, que determinar�o as prioridades dos chamados. A Telecontrol n�o far� mais este controle. 
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='left'> <FONT color=''>
				3) A triagem para desenvolvimento ser� da seguinte forma: 
				<br>				
				<br>				
				</font>
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'> <FONT color=''>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				a) Ap�s verificar o chamado, o Supervisor dever� aprovar (1� Etapa) para seguir a an�lise da Telecontrol, que ter� 48h para iniciar o atendimento.
				<br>				
				<br>				
				</font>
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'> <FONT color=''>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				b) Em seguida, com o aux�lio do autor do chamado, a Telecontrol far� a an�lise final e encaminhar� a solicita��o de aprova��o da quantidade de horas de franquia que ser�o utilizadas para desenvolvimento. 
				<br>				
				<br>				
				</font>
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'> <FONT color=''>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				c) Ap�s aprovada a quantidade de horas (2� Etapa), pelo Supervisor, a Telecontrol ter� 48h para informar a previs�o de t�rmino para resolver o chamado.
				<br>				
				<br>				
				</font>
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'> <FONT color='RED'>
				4) O fabricante ter� $backlog chamado(s) aprovado(s) e em desenvolvimento na Telecontrol, o restante ficar� em sua posse com o status �EM ESPERA�.				
				<br>				
				<br>				
				</font>
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='1' align = 'center' width='100%'></td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
		echo "</table>";
/////////////////////////////////////COMO ATENDENTE//////////////////////////////////////////////
		echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
			echo "<tr>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
				echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='1' align = 'center' width='100%' style='font-family: arial ; color:#666666' nowrap
				><CENTER>Regra b�sica de funcionamento do Help-Desk - COMO ATENDENTE</CENTER></td>";
				echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			$tr ="<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" >"; 
			$td_esq = "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			$td_dir = "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";

			echo $tr;
				echo $td_esq;
				echo "<td align='left'>
				Prezados,<br><br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				A Telecontrol est� em pleno crescimento comercial, parab�ns a todos pela contribui��o, e precisa crescer tamb�m no aspecto profissional e t�cnico. Com este objetivo que estamos criando este material de apoio com a defini��o dos princ�pios, diretrizes e crit�rios que ser�o utilizados neste trabalho.<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				Os princ�pios que regem este documento tem a inten��o de enquadrar a pol�tica de comercializa��o com a pol�tica de atendimento, e para isto temos que entender que a Telecontrol mant�m um contrato de comercializa��o do software, onde uma das cl�usulas cont�m o dever da Telecontrol de responder um chamado de Helpdesk em 24/48 horas, com a penalidade de multa. Para que n�o sejamos penalizados, ou melhor, manter um relacionamento saud�vel de atendimento x usu�rios do sistema, teremos que cumprir com as diretrizes deste documento.<br>				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				As diretrizes abordadas tem como foco os recursos necess�rios para o desenvolvimento deste trabalho, sendo: equipe de atendimento (analistas de suporte), software Helpdesk de apoio, gerencia de TI, e equipe de analistas de desenvolvimento.<br>";
				echo $td_dir;
			echo "</tr>";

			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				Para que todos inseridos neste contexto tenham um pleno conhecimento do ambiente, vamos destacar como a Telecontrol mant�m um relacionamento de comunica��o com os usu�rios do sistema (admin), que � um �cone mantido no canto superior direito no formato de uma b�ia. Os admins n�o t�m o mesmo poder no sistema, e um (ou mais) s�o separados para ser o SUPERVISOR de HELPDESK.<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				Neste ambiente o admin poder� fazer uma comunica��o com o Telecontrol em forma de helpdesk, para notificar um erro no sistema, ou para solicitar algo (melhoria, sugest�es, mudan�as de processos, etc).<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				Para solicitar um atendimento para resolver um erro no sistema, qualquer admin poder� fazer sem pedir para o supervisor aprovar o chamado, e ele ir� entrar na fila de atendimento do helpdesk diretamente.<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				Para solicitar um atendimento diferente de erro, o chamado precisa passar pela aprova��o do supervisor de helpdesk, que alinhar� todas as solicita��es com as regras do fabricante. Mesmo o supervisor do helpdesk ter� que depois de abrir o chamado, fazer a aprova��o do chamado (ser� explicado mais adiante o motivo).<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'><font color='RED'>
				Nota: Os usu�rios do sistema poder�o, eventualmente, entrar em contato com a Telecontrol atrav�s de liga��o telef�nica, email, msn, talk, skype, etc. Mas todos as pessoas que fizerem o contato com o usu�rio tem como obriga��o abrir o chamado no nome do usu�rio que fez o contato e informar que foi aberto um helpdesk para atender a solicita��o e que atrav�s dele que o procedimento ser� realizado, e que n�o existe uma outra forma de atendimento que n�o seja atrav�s do helpdesk, e que da pr�xima vez, fazer a gentileza de abrir o chamado antes de entrar em contato, e que o contato deve ser feito com os atendentes (analistas de suporte). N�O ESQUECER DE AVISAR O ADMIN QUE O SUPERVISOR PRECISA APROVAR O CHAMADO PARA ELE SER ATENDIDO!<br></font>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				O analista de suporte logado no seu admin vai clicar em atendimento telefone e escolher o fabricante e o admin que fez o contato e escrever tudo que foi tratado e at� informa��es internas como: cliente est� bravo, faz tanto tempo que o analista de desenvolvimento est� enrolando ele, � a 10 liga��o sem retorno, etc; e depois colocar a informa��o simples para aprova��o.<br>
				A tela de supervis�o do helpdesk sofreu algumas mudan�as, que s�o os principais motivos desta nova diretriz. A regra est� acima como SUPERVISOR.<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				Como as informa��es sobre as novas diretrizes est�o contidas na figura e nas explica��es anteriores, vamos ent�o entrar nos crit�rios de atendimentos:<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				1)	Todos os admins t�m acesso ao helpdesk atrav�s do bot�o com a figura de uma b�ia<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				2)	Os admins s�o diferenciados, sendo nomeado um para supervidor do helpdesk<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				3)	Os chamados de helpdesk de erro n�o precisam da aprova��o do supervisor<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				4)	Os chamados de helpdesk diferentes de erro dever�o ser aprovadas pelo supervisor do helpdesk, inclusive os abertos por ele mesmo<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				5)	A telecontrol n�o ir� gerenciar as prioridades dos chamados<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				6)	O supervisor do helpdesk ir� aprovar um chamado de cada vez<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				7)	Se tiver um chamado sendo atendido pelo Telecontrol (mesmo que este chamado seja de erro) o supervisor n�o conseguir� aprovar o pr�ximo chamado.<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				8)	A cada intera��o em qualquer helpdesk, o respons�vel do atendimento (analistas de suporte) ir� receber um email de notifica��o<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				9)	No mesmo dia, ou no m�ximo, no dia seguinte de manh�, a equipe de atendimento (analistas de suporte) dever� iniciar o atendimento, reescrevendo tudo que o admin solicitou, ligar para o cliente e comprovar o entendimento, at� que n�o haja mais d�vida sobre o que fazer<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				10)	Todos os dias, ser� realizado 1 hora de reuni�o do respons�vel pelo atendimento (analistas de suporte) com a gerencia dos analistas de desenvolvimento, para que seja orientada as quest�es de valida��o da an�lise, quantidade de horas para aprova��o, e prazo de atendimento para os casos de chamados aprovados pela segunda vez, al�m de estabelecer qual analista ser� respons�vel pelo desenvolvimento<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				10.1) Eventualmente, quando a reuni�o n�o for poss�vel, o supervisor da equipe de suporte, encaminhar� atrav�s de email ou talk para que n�o seja quebrada a rotina (no caso de envio por email ou talk a responsabilidade da gerente de TI vai levar em considera��o a an�lise realizada pelos atendentes (analistas de suporte) e n�o todo o enunciado do helpdesk). E se n�o funcionar, provocar reuni�o de emerg�ncia, aux�lio do Dir. T�lio, etc. Solicitar trabalho extra (fora do hor�rio, final de semana, etc).<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				11) O chamado estando na responsabilidade do desenvolvedor, dever� ser cobrado a resolu��o e cumprimento do prazo pela equipe de atendentes (analistas de suporte)<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				12) O analistas de desenvolvimento ter�o a liberdade de fazer questionamentos para a Gerencia de TI da forma como ser� realizado o desenvolvimento todos os dias, a qualquer hora<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				13) O chamado que for desenvolvido dever� ser devolvido para o atendimento (analistas de suporte) para fazer a valida��o com o admin<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'><font color='RED'>
				Nota: A equipe de atendimento vai atender como analistas de suporte (Rodrigo, Gabriel e Juliana), tendo como respons�vel o Rodrigo. O Rodrigo continua subordinado ao Gerente de Opera��es Ronaldo (tamb�m acompanhar� o processo de helpdesk e ter� liberdade de propor/sugerir mudan�as) porque continuar� a desempenhar outras fun��es, como capa de lote, etc. <br>
				A equipe de desenvolvimento estar� subordinada a Marisa e ao Gerente TI Samuel, composta por:<br>
				Perls / Crontab / email    : Boaz<br>
				Chamados de Erros          : Samuel / Marisa / Waldir<br>
				Chamados de desenvolvimento: �bano / Gustavo / Paulo Lin / Andreus / Manolo<br>
				Chamados de melhoria/teste : Boulivar / Ronald / Ciro (respons�vel Boulivar) </font><br>
				<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				A implanta��o ocorrer� da seguinte forma:<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				1)	Antes de ligar para o supervisor de helpdesk, dever� o respons�vel pelo atendimento (analistas de suporte) verificar todos os chamados que est�o em desenvolvimento e pedir uma previs�o de t�rmino, mesmo os chamados de erro. Os chamados que n�o est�o em desenvolvimento, dever�o voltar para a aprova��o, para que o supervisor de helpdesk  tenha a op��o de escolher a prioridade do pr�ximo atendimento<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				2)	O respons�vel pelo atendimento (analistas de suporte) ir� ligar para todos os supervisores de helpdesk, conforme o cronograma anterior, e avisar que estaremos fazendo melhorias no helpdesk para melhorar o desempenho de atendimento, e marcar na planilha o que estiver 100% implantado<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				3)	Caso n�o seja aceito a forma, ou houver um desgaste durante a liga��o, dever� transferir a liga��o para o Supervisor Qualidade Boulivar<br>";
				echo $td_dir;
			echo "</tr>";

			echo "<tr>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='1' align = 'center' width='100%'></td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
		echo "</table>";

/////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////COMO DESENVOLVEDOR//////////////////////////////////////////////
		echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
			echo "<tr>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
				echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='1' align = 'center' width='100%' style='font-family: arial ; color:#666666' nowrap
				><CENTER>Regra b�sica de funcionamento do Help-Desk - COMO DESENVOLVEDOR</CENTER></td>";
				echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			$tr ="<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" >"; 
			$td_esq = "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			$td_dir = "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";

			echo $tr;
				echo $td_esq;
				echo "<td align='left'>
				O Gerente Samuel ir� utilizar a agenda para dar o prazo para o atendimento.<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='left'>
				A Marisa ser� a pessoa que ir� distribuir diariamente os chamados para os desenvolvedores (com base na agenda), e anotar a produtividade de cada um.<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				Cada desenvolvedor ter� um chamado para trabalhar, pois:<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				1) O chamado j� est� com toda a an�lise pronta, e com as horas j� marcadas<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				2) Se tiver d�vida pode levantar e conversar com o Samuel a qualquer hora para debater crit�rios de como ser� feito<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				3) Se o chamado enroscar por qualquer motivo, dever� ser transferido para Samuel para que possa receber o pr�ximo chamado<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				4) Ap�s desenvolver o chamado, transferir o chamado para o atendimento (Rodrigo) para ele efetivar com o cliente (deixar tudo marcado para que a Marisa consigar trocar os programas de nome), porque o Rodrigo ap�s efetivar com o cliente, vai passar para a Marisa trocar os nomes dos programas, finalizando com o nome do desenvolvedor.<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo "<tr>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='1' align = 'center' width='100%'></td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
		echo "</table>";

/////////////////////////////////////////////////////////////////////////////////////////////////
		?>
	</tr>


</table>
<?

include "rodape.php" 

?>
</body>
</html>