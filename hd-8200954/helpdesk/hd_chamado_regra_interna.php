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
				><CENTER>Regra básica de funcionamento do Help-Desk - COMO SUPERVISOR</CENTER></td>";
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
				A Telecontrol, visando atender dentro de padrões internacionais de qualidade, está melhorando o seu atendimento via Help-Desk com relação ao controle do SLA de seus chamados.
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";				
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'>
				A Fábrica deverá definir suas prioridades e indicar qual chamado deverá ser desenvolvido pela Telecontrol. Após a indicação do chamado o Supervisor verificará facilmente se está sendo atendido dentro dos prazos estipulados.
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
				1) O chamado de erro continua tendo prioridade e não precisa de aprovação, basta escolher a opção “Erro de Programa”. 				
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'>
				2) O demais chamados, Backlog, serão gerenciados pelo(s) Supervisor(es) de Help-Desk de cada fabricante, que determinarão as prioridades dos chamados. A Telecontrol não fará mais este controle. 
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='left'> <FONT color=''>
				3) A triagem para desenvolvimento será da seguinte forma: 
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
				a) Após verificar o chamado, o Supervisor deverá aprovar (1ª Etapa) para seguir a análise da Telecontrol, que terá 48h para iniciar o atendimento.
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
				b) Em seguida, com o auxílio do autor do chamado, a Telecontrol fará a análise final e encaminhará a solicitação de aprovação da quantidade de horas de franquia que serão utilizadas para desenvolvimento. 
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
				c) Após aprovada a quantidade de horas (2ª Etapa), pelo Supervisor, a Telecontrol terá 48h para informar a previsão de término para resolver o chamado.
				<br>				
				<br>				
				</font>
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'> <FONT color='RED'>
				4) O fabricante terá $backlog chamado(s) aprovado(s) e em desenvolvimento na Telecontrol, o restante ficará em sua posse com o status “EM ESPERA”.				
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
				><CENTER>Regra básica de funcionamento do Help-Desk - COMO ATENDENTE</CENTER></td>";
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
				A Telecontrol está em pleno crescimento comercial, parabéns a todos pela contribuição, e precisa crescer também no aspecto profissional e técnico. Com este objetivo que estamos criando este material de apoio com a definição dos princípios, diretrizes e critérios que serão utilizados neste trabalho.<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				Os princípios que regem este documento tem a intenção de enquadrar a política de comercialização com a política de atendimento, e para isto temos que entender que a Telecontrol mantém um contrato de comercialização do software, onde uma das cláusulas contém o dever da Telecontrol de responder um chamado de Helpdesk em 24/48 horas, com a penalidade de multa. Para que não sejamos penalizados, ou melhor, manter um relacionamento saudável de atendimento x usuários do sistema, teremos que cumprir com as diretrizes deste documento.<br>				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				As diretrizes abordadas tem como foco os recursos necessários para o desenvolvimento deste trabalho, sendo: equipe de atendimento (analistas de suporte), software Helpdesk de apoio, gerencia de TI, e equipe de analistas de desenvolvimento.<br>";
				echo $td_dir;
			echo "</tr>";

			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				Para que todos inseridos neste contexto tenham um pleno conhecimento do ambiente, vamos destacar como a Telecontrol mantém um relacionamento de comunicação com os usuários do sistema (admin), que é um ícone mantido no canto superior direito no formato de uma bóia. Os admins não têm o mesmo poder no sistema, e um (ou mais) são separados para ser o SUPERVISOR de HELPDESK.<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				Neste ambiente o admin poderá fazer uma comunicação com o Telecontrol em forma de helpdesk, para notificar um erro no sistema, ou para solicitar algo (melhoria, sugestões, mudanças de processos, etc).<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				Para solicitar um atendimento para resolver um erro no sistema, qualquer admin poderá fazer sem pedir para o supervisor aprovar o chamado, e ele irá entrar na fila de atendimento do helpdesk diretamente.<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				Para solicitar um atendimento diferente de erro, o chamado precisa passar pela aprovação do supervisor de helpdesk, que alinhará todas as solicitações com as regras do fabricante. Mesmo o supervisor do helpdesk terá que depois de abrir o chamado, fazer a aprovação do chamado (será explicado mais adiante o motivo).<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'><font color='RED'>
				Nota: Os usuários do sistema poderão, eventualmente, entrar em contato com a Telecontrol através de ligação telefônica, email, msn, talk, skype, etc. Mas todos as pessoas que fizerem o contato com o usuário tem como obrigação abrir o chamado no nome do usuário que fez o contato e informar que foi aberto um helpdesk para atender a solicitação e que através dele que o procedimento será realizado, e que não existe uma outra forma de atendimento que não seja através do helpdesk, e que da próxima vez, fazer a gentileza de abrir o chamado antes de entrar em contato, e que o contato deve ser feito com os atendentes (analistas de suporte). NÃO ESQUECER DE AVISAR O ADMIN QUE O SUPERVISOR PRECISA APROVAR O CHAMADO PARA ELE SER ATENDIDO!<br></font>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				O analista de suporte logado no seu admin vai clicar em atendimento telefone e escolher o fabricante e o admin que fez o contato e escrever tudo que foi tratado e até informações internas como: cliente está bravo, faz tanto tempo que o analista de desenvolvimento está enrolando ele, é a 10 ligação sem retorno, etc; e depois colocar a informação simples para aprovação.<br>
				A tela de supervisão do helpdesk sofreu algumas mudanças, que são os principais motivos desta nova diretriz. A regra está acima como SUPERVISOR.<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				Como as informações sobre as novas diretrizes estão contidas na figura e nas explicações anteriores, vamos então entrar nos critérios de atendimentos:<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				1)	Todos os admins têm acesso ao helpdesk através do botão com a figura de uma bóia<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				2)	Os admins são diferenciados, sendo nomeado um para supervidor do helpdesk<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				3)	Os chamados de helpdesk de erro não precisam da aprovação do supervisor<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				4)	Os chamados de helpdesk diferentes de erro deverão ser aprovadas pelo supervisor do helpdesk, inclusive os abertos por ele mesmo<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				5)	A telecontrol não irá gerenciar as prioridades dos chamados<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				6)	O supervisor do helpdesk irá aprovar um chamado de cada vez<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				7)	Se tiver um chamado sendo atendido pelo Telecontrol (mesmo que este chamado seja de erro) o supervisor não conseguirá aprovar o próximo chamado.<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				8)	A cada interação em qualquer helpdesk, o responsável do atendimento (analistas de suporte) irá receber um email de notificação<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				9)	No mesmo dia, ou no máximo, no dia seguinte de manhã, a equipe de atendimento (analistas de suporte) deverá iniciar o atendimento, reescrevendo tudo que o admin solicitou, ligar para o cliente e comprovar o entendimento, até que não haja mais dúvida sobre o que fazer<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				10)	Todos os dias, será realizado 1 hora de reunião do responsável pelo atendimento (analistas de suporte) com a gerencia dos analistas de desenvolvimento, para que seja orientada as questões de validação da análise, quantidade de horas para aprovação, e prazo de atendimento para os casos de chamados aprovados pela segunda vez, além de estabelecer qual analista será responsável pelo desenvolvimento<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				10.1) Eventualmente, quando a reunião não for possível, o supervisor da equipe de suporte, encaminhará através de email ou talk para que não seja quebrada a rotina (no caso de envio por email ou talk a responsabilidade da gerente de TI vai levar em consideração a análise realizada pelos atendentes (analistas de suporte) e não todo o enunciado do helpdesk). E se não funcionar, provocar reunião de emergência, auxílio do Dir. Túlio, etc. Solicitar trabalho extra (fora do horário, final de semana, etc).<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				11) O chamado estando na responsabilidade do desenvolvedor, deverá ser cobrado a resolução e cumprimento do prazo pela equipe de atendentes (analistas de suporte)<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				12) O analistas de desenvolvimento terão a liberdade de fazer questionamentos para a Gerencia de TI da forma como será realizado o desenvolvimento todos os dias, a qualquer hora<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				13) O chamado que for desenvolvido deverá ser devolvido para o atendimento (analistas de suporte) para fazer a validação com o admin<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'><font color='RED'>
				Nota: A equipe de atendimento vai atender como analistas de suporte (Rodrigo, Gabriel e Juliana), tendo como responsável o Rodrigo. O Rodrigo continua subordinado ao Gerente de Operações Ronaldo (também acompanhará o processo de helpdesk e terá liberdade de propor/sugerir mudanças) porque continuará a desempenhar outras funções, como capa de lote, etc. <br>
				A equipe de desenvolvimento estará subordinada a Marisa e ao Gerente TI Samuel, composta por:<br>
				Perls / Crontab / email    : Boaz<br>
				Chamados de Erros          : Samuel / Marisa / Waldir<br>
				Chamados de desenvolvimento: Ébano / Gustavo / Paulo Lin / Andreus / Manolo<br>
				Chamados de melhoria/teste : Boulivar / Ronald / Ciro (responsável Boulivar) </font><br>
				<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				A implantação ocorrerá da seguinte forma:<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				1)	Antes de ligar para o supervisor de helpdesk, deverá o responsável pelo atendimento (analistas de suporte) verificar todos os chamados que estão em desenvolvimento e pedir uma previsão de término, mesmo os chamados de erro. Os chamados que não estão em desenvolvimento, deverão voltar para a aprovação, para que o supervisor de helpdesk  tenha a opção de escolher a prioridade do próximo atendimento<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				2)	O responsável pelo atendimento (analistas de suporte) irá ligar para todos os supervisores de helpdesk, conforme o cronograma anterior, e avisar que estaremos fazendo melhorias no helpdesk para melhorar o desempenho de atendimento, e marcar na planilha o que estiver 100% implantado<br>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				3)	Caso não seja aceito a forma, ou houver um desgaste durante a ligação, deverá transferir a ligação para o Supervisor Qualidade Boulivar<br>";
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
				><CENTER>Regra básica de funcionamento do Help-Desk - COMO DESENVOLVEDOR</CENTER></td>";
				echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			$tr ="<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" >"; 
			$td_esq = "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			$td_dir = "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";

			echo $tr;
				echo $td_esq;
				echo "<td align='left'>
				O Gerente Samuel irá utilizar a agenda para dar o prazo para o atendimento.<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='left'>
				A Marisa será a pessoa que irá distribuir diariamente os chamados para os desenvolvedores (com base na agenda), e anotar a produtividade de cada um.<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				Cada desenvolvedor terá um chamado para trabalhar, pois:<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				1) O chamado já está com toda a análise pronta, e com as horas já marcadas<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				2) Se tiver dúvida pode levantar e conversar com o Samuel a qualquer hora para debater critérios de como será feito<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				3) Se o chamado enroscar por qualquer motivo, deverá ser transferido para Samuel para que possa receber o próximo chamado<br>
				</td>";
				echo $td_dir;
			echo "</tr>";
			echo $tr;
				echo $td_esq;
				echo "<td align='justify'>
				4) Após desenvolver o chamado, transferir o chamado para o atendimento (Rodrigo) para ele efetivar com o cliente (deixar tudo marcado para que a Marisa consigar trocar os programas de nome), porque o Rodrigo após efetivar com o cliente, vai passar para a Marisa trocar os nomes dos programas, finalizando com o nome do desenvolvedor.<br>
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