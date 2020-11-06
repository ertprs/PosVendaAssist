<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$backlog = 1;

if ($login_fabrica == 46) {
	$backlog = 10;
}
//VERIFICA SE O USUÁRIO É SUPERVISOR
$sql="  SELECT * FROM tbl_admin
		WHERE admin=$login_admin
		AND help_desk_supervisor='t'";

$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	$supervisor = true;
	$nome_completo=pg_result($res,0,nome_completo);
}

$colsTblHDs = 7;		//'colspan' tabela HDs em andamento
$colsTblHDsAprova = 8;	//'colspan' tabela HDs em espera

if ($login_fabrica == 3 and $supervisor) {
	$prioriza_hds = true;
	$ordemPR = 'prioridade_supervisor ,';	// Para ordenar as queries por prioridade
	$colPR = "<th title='Prioridade deste chamado.'>Prioridade</th>\n";// Coluna 'Prioridade'
	$colsTblHDs = 8;			//'colspan' tabela HDs em andamento
	$colsTblHDsAprova = 9;		//'colspan' tabela HDs em espera
}


//PEGA O NOME DA FABRICA
$sql = "SELECT   *
		FROM     tbl_fabrica
		WHERE    fabrica=$login_fabrica
		ORDER BY nome";
$res = pg_exec ($con,$sql);
$nome      = trim(pg_result($res,0,nome));

$menu_cor_fundo="EEEEEE";
$menu_cor_linha="BBBBBB";

/*  AJAX altera prioridade  */
include_once('mlg_funciones.php');

if ($_POST['ajax'] == 'prioridade') {
	$hd_chamado	   = anti_injection($_POST['hd']);
	$prioridade_hd = intval(substr(anti_injection($_POST['pr']), 0, 3)); // Limitado a 1 caractere...
	$prioridade_hd = ($prioridade_hd=='') ? 'NULL' : "$prioridade_hd";

	$sql = "UPDATE tbl_hd_chamado
			   SET prioridade_supervisor = $prioridade_hd
			 WHERE hd_chamado = $hd_chamado";
	$res = @pg_query($con, $sql);
	if (is_resource($res)) {
		if (pg_affected_rows($res)==1) {
			$msg_ajax = "OK|Prioridade alterada!";
		} else {
			$msg_ajax = "KO|Erro ao atualizar a prioridade do chamado $hd_chamado|$sql";
		}
	} else {
		$msg_ajax = "KO|Erro ao alterar a prioridade do chamado $hd_chamado|$sql|".pg_last_error($con);
	}
	exit($msg_ajax);
}

if($_GET['conteudo'])  $conteudo  = $_GET['conteudo'];
//echo $conteudo."<br>".$ajuda;


//FIM DO SELECT DA TABELA ESTATISTICAS DE CHAMADAS---------------------------------
$hd_chamado      = $_GET['hd_chamado'];
$aprova          = $_GET['aprova'];
$aprova_execucao = $_GET['aprova_execucao'];

$data       = date("d/m/Y  h:i");

if($aprova=='sim'){
	$sql = "SELECT count(*) as qtd_chamado
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica
		JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		WHERE tbl_hd_chamado.fabrica_responsavel = 10
		AND tbl_fabrica.fabrica = $login_fabrica
		AND (tbl_hd_chamado.status <> 'Resolvido' and tbl_hd_chamado.status <> 'Cancelado' and tbl_hd_chamado.status <> 'Aprovação' and tbl_hd_chamado.status <> 'Novo')
		AND tbl_hd_chamado.tipo_chamado NOT IN (5, 6)";
	$res = pg_exec ($con,$sql);
	$qtd_chamado = pg_result($res,0,qtd_chamado);
	if ( $qtd_chamado >= $backlog ) {
		$msg_erro = "Somente após resolvido o chamado que está em desenvolvimento que você poderá aprovar o próximo chamado.";
	}

	$res = @pg_exec($con,"BEGIN TRANSACTION");

	$sql= " SELECT TO_CHAR(data,'DD/MM HH24:MI') AS data
			FROM tbl_hd_chamado
			WHERE fabrica = $login_fabrica
			AND hd_chamado = $hd_chamado
			AND status='Aprovação'";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);
	if (pg_numrows($res) > 0) {
		$data_abertura = pg_result($res,0,data);
	}

	//HD 319460 - Alterando status para Aguard.Execução para quando for aprovação
	$sql = "UPDATE tbl_hd_chamado
			SET exigir_resposta = 'f', status = 'Aguard.Execução', data_aprovacao_fila = CURRENT_TIMESTAMP
			WHERE hd_chamado = $hd_chamado
			AND   status='Aprovação'";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);

	if(strlen($msg_erro) ==0 and strlen($data_abertura) >0 ){
		$sql = "INSERT into tbl_hd_chamado_item (
					hd_chamado,
					comentario,
					admin
					) VALUES (
					$hd_chamado,
					'MENSAGEM AUTOMÁTICA - ESTE CHAMADO FOI ABERTO EM $data_abertura E FOI APROVADO EM $data PELO USUÁRIO $nome_completo',
					$login_admin
					)";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	if(strlen($msg_erro) > 0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		$msg_erro .= 'Houve um erro na aprovação do Chamado.';
	}else{
		$res = @pg_exec($con,"COMMIT");
	}
}
if($cancela=='sim'){
	$sql = "UPDATE tbl_hd_chamado SET status = 'Cancelado' WHERE hd_chamado = $hd_chamado";
	$res = pg_exec ($con,$sql);
	if($login_fabrica==6){
	$sql = "INSERT into tbl_hd_chamado_item (
				hd_chamado,
				comentario,
				admin
				) VALUES (
				$hd_chamado,
				'MENSAGEM AUTOMÁTICA-ESTE CHAMADO FOI CANCELADO EM $data PELO USUÁRIO $nome_completo',
				$login_admin
				)";
	$res = pg_exec ($con,$sql);
	}
}
// HD 17195
if($aprova_execucao== 'sim'){
	$sql="SELECT to_char(current_date,'MM')   AS mes,
				 to_char(current_date,'YYYY') AS ano;";
	$res=pg_exec($con,$sql);
	$mes=pg_result($res,0,mes);
	$ano=pg_result($res,0,ano);



	$sql="SELECT hora_desenvolvimento,
				data_aprovacao
			FROM tbl_hd_chamado
			WHERE hd_chamado=$hd_chamado";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res) > 0){
		$hora_desenvolvimento=pg_result($res,0,hora_desenvolvimento);
		$data_aprovacao=pg_result($res,0,data_aprovacao);
		if($hora_desenvolvimento == 0 or strlen($hora_desenvolvimento)==0){
			$msg_erro="Prezado Supervisor, este chamado está sem a hora de desenvolvimento cadastrado, por favor, entrar em contato com o Suporte Telecontrol para cadastrá-lo.";
		}
		if(strlen($data_aprovacao) > 0){
			$msg_erro="Este Chamado já foi aprovado, não pode aprovar mais de uma vez";
		}

	}
	if(strlen($msg_erro) ==0){
		$res = @pg_exec($con,"BEGIN TRANSACTION");

		//HD 319460 - Alterando status para Aguard.Execução para quando for aprovação
		$sql = "UPDATE tbl_hd_chamado
				SET exigir_resposta = 'f', status = 'Aguard.Execução', data_aprovacao = CURRENT_TIMESTAMP
				WHERE hd_chamado = $hd_chamado";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql="SELECT *
				FROM tbl_hd_franquia
				WHERE fabrica = $login_fabrica
				ORDER BY hd_franquia desc limit 1";
		$res=pg_exec($con,$sql);

		if(pg_numrows($res) >0){
			$hd_franquia=pg_result($res,0,hd_franquia);

			$sqlh = "UPDATE tbl_hd_franquia
					SET hora_utilizada=hora_utilizada + hora_desenvolvimento
					FROM tbl_hd_chamado
					WHERE tbl_hd_franquia.fabrica     = tbl_hd_chamado.fabrica
					AND   tbl_hd_chamado.hd_chamado   = $hd_chamado
					AND   tbl_hd_franquia.hd_franquia = $hd_franquia
					AND   tbl_hd_chamado.fabrica      = $login_fabrica";
			$resh = pg_exec ($con,$sqlh);
			$msg_erro .= pg_errormessage($con);
		}

		$sql = "INSERT into tbl_hd_chamado_item (
					hd_chamado,
					comentario,
					admin
					) VALUES (
					$hd_chamado,
					'MENSAGEM AUTOMÁTICA - HORA DE DESENVOLVIMENTO APROVADO EM  $data PELO USUÁRIO $nome_completo',
					$login_admin
					)";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if(strlen($msg_erro) > 0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro .= 'Houve um erro na aprovação do Chamado.';
		}else{
			$email_origem  = "suporte@telecontrol.com.br";
			$email_destino = "suporte@telecontrol.com.br";

			$assunto       = "Chamado $hd_chamado aprovado para execução";
			$corpo = "";
			$corpo.= "<br>O chamado $hd_chamado, que estava aguardando aprovação,foi aprovado.\n\n";
			$corpo.= "<br>Chamado n°: $hd_chamado\n\n";
			$corpo.= "<br><br>Telecontrol\n";
			$corpo.= "<br>www.telecontrol.com.br\n";
			$corpo.= "<br>_______________________________________________\n";
			$corpo.= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

			$body_top  = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";

			if ( mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
				$msg .= "<br>Foi enviado um email para: ".$email_destino."<br>";
			}
			$res = @pg_exec($con,"COMMIT");
		}
	}
}

?>

<html>
<head>
	<title>Telecontrol - Help Desk</title>
	<link type="text/css" rel="stylesheet" href="css/css.css">
	<style>
	.negrito	{font-weight:bold!important;}
	.vermelho	{color: red!important}

	.supervisor{
		font-size: 12px;
	}

	.supervisor ul{
		list-style-type:none;
		margin:0px;
	}

	thead, tr.header, caption {
		background-color:#D9E8FF;
		color: #666;
		height: 24px;
		font: normal bold 14px arial, helvetica, sans-serif;
		text-align: center;
	}
	table table.tabela td {
		text-align: left;
		font-size:12px;
		font-weight:normal;
		cursor:		default;
	}
	table.tabela {margin-bottom: 1em}

	table#tbl_franquia td,
	table#tbl_fr_ant td {font-size: 14px;font-weight:bold;text-align:center;}

	table#tbl_hd_ativos td.Conteudo {font-size: 9px;}
	table#tbl_hd_ativos td

	table.tabela tr:hover,
	#regrasHD td p:hover,
	#regrasHD td ol li:hover {
		background-color:#D9E8FF!important;
	}
	#regrasHD td ol li {
		margin-bottom: 1.2em;
	}
	</style>
	<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
	
	<script type="text/javascript">
	
	$().ready(function() {
		
		$('input[id^=hd_prioridade],input[id^=hda_prioridade]').change(function () {
			var nova_prioridade = $(this).val().toUpperCase();
			var numHD			= $(this).attr('hd');
			var inputPR         = $(this);
// 			if(nova_prioridade=='') return true; // Não faz nada especial se o campo estiver vazio
			if (nova_prioridade != $(this).val()) $(this).val(nova_prioridade);
			$(this).css('background-color', '#ccf');
			$.post(location.pathname,
					{
						'ajax':	'prioridade',
						'hd':	numHD,
						'pr':   nova_prioridade
					},
					function(data) {
						resposta = data.split('|');
						if(resposta[0]=='KO') {
							alert(resposta[1]);
							inputPR.css('background-color', '#fcc');
							return false;
						}
						if(resposta[0]=='OK') {
// 							inputPR.parent().html(nova_prioridade);
							inputPR.css('background-color', '#cfc');
						}
				     });
    	});

		
	});
    </script>
</head>
<body>
<!--[if lt IE 9]>
	<style type="text/css">
	.mouseOver {background-color:#D9E8FF!important;}
    </style>
	<script type="text/javascript">
	$().ready(function() {
    	$('table.tabela tbody tr, #regrasHD td p, #regrasHD td ol li').hover(function() {
			$(this).toggleClass('mouseOver');
		});
    });
    </script>
<![endif]-->
<?
include "menu.php";
?>
<script type="text/javascript" src="../js/jquery.alphanumeric.js"></script>
<script type='text/javascript'>
	$().ready(function() {
		$('input[id^=hd_prioridade],input[id^=hda_prioridade]').numeric();
});
</script>
<table width="700" align="center" bgcolor="#FFFFFF" border='0'>
	<tr>
		<td >
			<table width='100%' border='0'>
				<tr>
					<td valign='middle'>
						<table width="100%" align="center">
							<tbody>
							<tr>
								<td colspan='100%' style="font-family: arial ; color: #666666; font-size:10px" align="justify"></td>
							</tr>
							<tr style="font-family: arial ; color: #666666" align="center">
								<td>
									<img src="/assist/admin/imagens_admin/status_amarelo.gif" valign="absmiddle">&nbsp;
									<a href="chamado_lista.php?status=Aprovação"><?=($sistema_lingua=='ES')?'Aguardando Aprobación':'Aguarda Aprovação'?></a>&nbsp;
								</td>
								<td>
									<img src="/assist/admin/imagens_admin/status_cinza.gif" valign="absmiddle">&nbsp;
									<a href="chamado_lista.php?admin=admin"><?=($sistema_lingua=='ES')?'Mis Solicitudes':'Meus Chamados'?></a>&nbsp;
								</td>
								<td>
									<img src="/assist/admin/imagens_admin/status_azul.gif" valign="absmiddle">&nbsp;
									<a href="chamado_lista.php?status=Análise&amp;exigir_resposta=f"><?=($sistema_lingua=='ES')?'Pendientes Telecontrol':'Pendentes Telecontrol'?></a>&nbsp;
								</td>
								<td>
									<img src="/assist/admin/imagens_admin/status_vermelho.gif" valign="absmiddle">&nbsp;
									<a href="chamado_lista.php?status=Análise&amp;exigir_resposta=t"><?=($sistema_lingua=='ES')?'Aguardando mi respuesta':'Aguarda a minha resposta'?></a>&nbsp;
								</td>
								<td>
									<img src="/assist/admin/imagens_admin/status_verde.gif" valign="absmiddle">&nbsp;
									<a href="chamado_lista.php?status=Resolvido&amp;filtro=1"><?=($sistema_lingua=='ES')?'Mis Resolvidas':'Meus Resolvidos'?></a>&nbsp;
								</td>
								<td>
									<img src="/assist/admin/imagens_admin/status_azul_bb.gif" valign="absmiddle">&nbsp;
									<a href="chamado_lista.php?todos=todos&amp;filtro=1"><?=($sistema_lingua=='ES')?'Todas':'Todos'?></a>&nbsp;
								</td>
								<td>
									<img src="/assist/admin/imagens_admin/status_rosa.gif" valign="absmiddle">&nbsp;
									<a href="relatorio_horas_cobradas.php"><?=($sistema_lingua=='ES')?'Informe Mensual':'Relatório Mensal'?></a>&nbsp;
								</td>
							</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan="2" bgcolor="<?=$menu_cor_linha?>" width="1" height="1"></td>
				</tr>
				<tr>
					<td colspan="2" class="Conteudo" align="center">
		<p>&nbsp;</p>
			<table width="700" align="center" cellpadding="0" cellspacing="0" border="0" id="lista_sup" class='tabela'>
				<tbody>
					<tr class="header">
						<td background="/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif" colspan="3">
							ATENÇÃO - Somente estes administradores abaixo podem aprovar chamados
						</td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
					</tr>
					<tr style="font-family: arial;font-size: 12px;cursor:pointer;background-color:" height="25">
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
							<td>Login</td>
							<td>Fone</td>
							<td>Email</td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
					</tr>
<?
			$sql = "SELECT nome_completo,fone, email FROM tbl_admin WHERE fabrica = $login_fabrica AND help_desk_supervisor IS TRUE ORDER BY nome_completo ASC";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				for($i=0;$i<pg_numrows($res);$i++){
					$nome_completo = pg_result($res,$i,nome_completo);
					$fone          = pg_result($res,$i,fone);
					$email         = pg_result($res,$i,email);
?>
					<tr style="font-family: arial;font-size: 12px;cursor: pointer;" height="25" bgcolor="" onmouseover="this.bgColor='#D9E8FF'" onmouseout="this.bgColor=''">
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						<td nowrap="" align="center"><?=$nome_completo?></td>
						<td nowrap="" align="center"><?=$fone?></td>
						<td nowrap="" align="center"><?=$email?></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
					</tr>
<?				}
			}
?>
					<tr>
						<td background="/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif" colspan="3" align="center" width="100%"></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
					</tr>
				</tbody>
			</table>
			<table width="700" align="center" cellpadding="0" cellspacing="0" border="0" id="regrasHD">
				<thead>
					<tr>
						<th background="/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif"><img src="/assist/imagens/pixel.gif" width="9"></th>
						<th background="/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif" align="center" width="100%">Regras básicas de funcionamento do Help-Desk</th>
						<th background="/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif"><img src="/assist/imagens/pixel.gif" width="9"></th>
					</tr>
				</thead>
				<tbody>
					<tr style="font-size: 12px" height="25">
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						<td style="text-align:justify;padding:1.2em;background-color:">
						Prezado Cliente,
						<p>&nbsp;</p>
						<p>A Telecontrol, visando atender dentro de padrões internacionais de qualidade, está melhorando o seu atendimento via Help-Desk
						com relação ao controle do SLA de seus chamados.
						</p>
						<p>A Fábrica deverá definir suas prioridades e indicar qual chamado deverá ser desenvolvido pela Telecontrol.
						Após a indicação do chamado o <b>Supervisor</b> verificará facilmente se está sendo atendido dentro dos prazos estipulados.
						</p>
						<p>Funcionamento do HELPDESK:</p>
						<center><img src="/assist/imagens/Processo.png" width="650"></center>
						<br>
						<ol style="list-style:outside decimal;">
							<li>O chamado de erro continua tendo prioridade e não precisa de aprovação, basta escolher a opção “Erro de Programa”.</li>
							<li>O demais chamados, o <i>Backlog</i>, serão gerenciados pelo(s) <b>Supervisor(es) de Help-Desk</b> de cada fabricante,
								que determinarão as prioridades dos chamados. A Telecontrol não fará mais este controle.</li>
							<li>A triagem para desenvolvimento será da seguinte forma:
								<ol type="a">
			                     <li>Após verificar o chamado, o Supervisor deverá aprovar (1ª Etapa) para seguir a análise da Telecontrol, que terá 48h para iniciar o atendimento.</li>
			                     <li>Em seguida, com o auxílio do autor do chamado, a <b>Telecontrol</b> fará a análise final e encaminhará a solicitação de aprovação da
								 	 quantidade de horas de franquia que serão utilizadas para desenvolvimento.</li>
			                     <li>Após aprovada a quantidade de horas (2ª Etapa), pelo <b>Supervisor</b>, a Telecontrol terá <b>48h</b> para informar a previsão de término para resolver o chamado.</li>
			                    </ol>
							</li>
							<li class='vermelho'>O fabricante terá <?=$backlog?> chamado(s) aprovado(s) e em desenvolvimento na <b>Telecontrol</b>, o restante ficará em sua posse com o status “EM ESPERA”.</li>
						</ol></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
					</tr>
					<tr>
						<td background="/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif" width="100%"></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
					</tr>
				</tbody>
			</table>

<?
/*
$sql = "SELECT
			hd_chamado ,
			tbl_hd_chamado.admin ,
			tbl_admin.nome_completo ,
			tbl_admin.login ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			titulo ,
			status ,
			atendente ,
			tbl_fabrica.nome AS fabrica_nome
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica
		WHERE tbl_hd_chamado.fabrica_responsavel = 10
		AND tbl_fabrica.fabrica = $login_fabrica
		AND (tbl_hd_chamado.status ='Aprovação')
		AND tbl_hd_chamado.data_envio_aprovacao IS NULL
		ORDER BY tbl_hd_chamado.status,tbl_hd_chamado.data DESC";
$res = pg_exec ($con,$sql);


if (@pg_numrows($res) > 0) {
	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='6' align = 'center' width='100%' style='font-family: arial ; color:#666666'><CENTER>Chamados para serem aprovados</CENTER></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td>N°</td>";
	echo "	<td>Título</td>";
	echo "	<td>Status</td>";
	echo "	<td>Data</td>";
	echo "	<td>Solicitante</td>";
	echo "	<td>Ação</td>";
	echo "</tr>";


//inicio imprime chamados
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$hd_chamado           = pg_result($res,$i,hd_chamado);
		$admin                = pg_result($res,$i,admin);
		$login                = pg_result($res,$i,login);
//		$posto                = pg_result($res,$i,posto);
		$data                 = pg_result($res,$i,data);
		$titulo               = pg_result($res,$i,titulo);
		$status               = pg_result($res,$i,status);
		$atendente            = pg_result($res,$i,atendente);
		$nome_completo        = trim(pg_result($res,$i,nome_completo));
		$fabrica_nome         = trim(pg_result($res,$i,fabrica_nome));


		$sql2 = "SELECT nome_completo, admin
			FROM	tbl_admin
			WHERE	admin='$atendente'";

		$res2 = pg_exec ($con,$sql2);
		$xatendente            = pg_result($res2,0,nome_completo);
		$xxatendente = explode(" ", $xatendente);

		$cor='#F2F7FF';
		if ($i % 2 == 0) $cor = '#FFFFFF';

		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";

		echo "<td><img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'> $hd_chamado&nbsp;</td>";
		echo "<td><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a></td>";
		if (($status != 'Resolvido') and ($status != 'Cancelado')) {
			echo "<td nowrap><font color=#FF0000><B>$status </B></font></td>";
		}else{
			echo "<td nowrap>$status </td>";
		}
		echo "<td nowrap>&nbsp;$data &nbsp;</td>";
		echo "<td>";
		if (strlen ($nome_completo) > 0) {
			echo $nome_completo;

		}else{
			echo $login;
		}
		echo "</td>";
		echo "<td>";
		if ($supervisor=='t' AND $status=='Aprovação'){
			echo "<a href='$PHP_SELF?hd_chamado=$hd_chamado&aprova=sim'><img src='imagem/btn_ok.gif'border='0' align='absmiddle'>APROVA</a><br><a href='$PHP_SELF?hd_chamado=$hd_chamado&cancela=sim'><img src='imagem/btn_deletar.gif'border='0' align='absmiddle'>CANCELA";
		}
		echo"</td>";

		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</a></tr>";

	}

//fim imprime chamados

	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='6' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "</table>";
}
*/
echo "</td>";
echo "</tr>";

echo "</table>";

$sql="SELECT to_char(current_date,'MM') as mes,
			 to_char(current_date,'YYYY') as ano;";
$res=pg_exec($con,$sql);
$mes=pg_result($res,0,mes);
$ano=pg_result($res,0,ano);
if (strlen($mes) > 0) {
	$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
}

$sql="SELECT saldo_hora            ,
			 mes                   ,
			 ano                   ,
			 hora_franqueada       ,
			 hora_faturada         ,
			 hora_utilizada        ,
			 valor_hora_franqueada ,
			 to_char(periodo_inicio,'DD/MM/YYYY') as periodo_inicio,
			 to_char(periodo_fim,'DD/MM/YYYY') as periodo_fim
		from tbl_hd_franquia
		where fabrica=$login_fabrica
		order by hd_franquia desc limit 2";
//echo $sql;exit;
$res=pg_exec($con,$sql);

if(pg_numrows($res) > 0){
	$saldo_hora            = pg_result($res,0,saldo_hora);
	$hora_franqueada       = pg_result($res,0,hora_franqueada);
	$hora_faturada         = pg_result($res,0,hora_faturada);
	$hora_utilizada        = pg_result($res,0,hora_utilizada);
	$valor_hora_franqueada = pg_result($res,0,valor_hora_franqueada);
	$valor_hora_franqueada = number_format($valor_hora_franqueada,2,',','.');
	$periodo_inicio        = pg_result($res,0,periodo_inicio);
	$periodo_fim           = pg_result($res,0,periodo_fim);
	$mes                   = pg_result($res,0,mes);
	$ano                   = pg_result($res,0,ano);
	$valor_faturado = $hora_faturada * $valor_hora_franqueada;
	$horas_que_ainda_podem_aprovar = $hora_franqueada + $saldo_hora - $hora_utilizada;
?>
				<table width="700" align="center" id="tbl_franquia" class="tabela" cellpadding="0" cellspacing="0" border="0">
						<colgroup>
							<col><col><col align="char" char="."><col>
						</colgroup>
					<thead>
						<tr>
							<th background="/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif"><img src="/assist/imagens/pixel.gif" width="9"></th>
							<th background="/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif" colspan="2" width="100%">
								FRANQUIA DE HORAS<br><span style="font-size: 12px"><?="$mes/$ano Inicio: $periodo_inicio"?></span>
							</th>
							<th background="/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif"><img src="/assist/imagens/pixel.gif" width="9"></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td background="/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
							<td>Total de franquia de horas deste mês:</td>
							<td><?=$hora_franqueada?></td>
							<td background="/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						</tr>
						<tr>
							<td background="/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
							<td>Saldo de Hora:</td>
							<td><?=$saldo_hora?></td>
							<td background="/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						</tr>
						<tr>
							<td background="/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
							<td>Total de horas utilizadas:</td>
							<td><?=$hora_utilizada?></td>
							<td background="/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						</tr>
						<tr>
							<td background="/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
							<td style="color:red">A fabrica pode liberar este mês, sem cobrar, um total de:</td>
							<td><?="$horas_que_ainda_podem_aprovar hora(s)"?></td>
							<td background="/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						</tr>
						<tr>
							<td background="/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
							<td>Hora faturada:</td>
							<td><?=$hora_faturada?></td>
							<td background="/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						</tr>
						<tr>
							<td background="/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
							<td>Valor faturado:</td>
							<td><?=$valor_faturado?></td>
							<td background="/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						</tr>
						<tr>
							<td background="/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
							<td background="/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif" colspan="2" align="center" width="100%"></td>
							<td background="/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						</tr>
					</tbody>
				</table>
<?
	if(pg_numrows($res) > 1){
		$saldo_hora1            = pg_result($res,1,saldo_hora);
		$hora_franqueada1       = pg_result($res,1,hora_franqueada);
		$hora_faturada1         = pg_result($res,1,hora_faturada);
		$hora_utilizada1        = pg_result($res,1,hora_utilizada);
		$valor_hora_franqueada1 = pg_result($res,1,valor_hora_franqueada);
		$valor_hora_franqueada1 = number_format($valor_hora_franqueada1,2,',','.');
		$periodo_inicio1        = pg_result($res,1,periodo_inicio);
		$periodo_fim1           = pg_result($res,1,periodo_fim);
		$mes1                   = pg_result($res,1,mes);
		$ano1                   = pg_result($res,1,ano);
		$valor_faturado1        = $hora_faturada1 * $valor_hora_franqueada1;
?>
				<table width="700" align="center" cellpadding="0" cellspacing="0" border="0" id="tbl_fr_ant" class='tabela'>
					<tbody>
					<tr class="header">
						<td background="/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif" width="100%" colspan="2">
							MÊS ANTERIOR<br><span style="font-size:12px"><?="$mes1/$ano1 Período: $periodo_inicio1 - $periodo_fim1"?></span>
						</td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
					</tr>
					<tr>
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						<td align="center">Total de franquia de horas deste mês:</td>
						<td align="center"><?=$hora_franqueada1?></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
					</tr>
					<tr>
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						<td align="center">Saldo de Hora:</td>
						<td align="center"><?=$saldo_hora1?></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
					</tr>
					<tr>
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						<td align="center">Total de horas utilizadas:</td>
						<td align="center"><?=$hora_utilizada1?></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
					</tr>
					<tr>
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						<td align="center">Hora faturada:</td>
						<td align="center"><?=$hora_faturada1?></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
					</tr>
					<tr>
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						<td align="center">Valor faturado:</td>
						<td align="center"><?=$valor_faturado1?></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
					</tr>
					<tr>
						<td background="/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif" colspan="2" width="100%"></td>
						<td background="/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif"><img src="/assist/imagens/pixel.gif" width="9"></td>
					</tr>
					</tbody>
				</table>
<?	}
}
////////////////////////////Chamados no Telecontrol////////////////////////////////////////
$sql = "SELECT count(*) as numero_hd_no_telecontrol
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica
		JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		WHERE tbl_hd_chamado.fabrica_responsavel = 10
		AND tbl_fabrica.fabrica = $login_fabrica
		AND (tbl_hd_chamado.status <> 'Resolvido' and tbl_hd_chamado.status <> 'Cancelado' and tbl_hd_chamado.status <> 'Aprovação' and tbl_hd_chamado.status <> 'Novo')
		AND tbl_hd_chamado.tipo_chamado NOT IN (5, 6)";
$res = pg_exec ($con,$sql);
$numero_hd_no_telecontrol = pg_result($res,0,numero_hd_no_telecontrol);

$sql = "SELECT
			hd_chamado						,
			tbl_hd_chamado.admin			,
			TO_CHAR(tbl_hd_chamado.previsao_termino,'DD/MM/YY HH24:MI') AS previsao_termino,
			tbl_tipo_chamado.descricao		,
			tbl_admin.nome_completo			,
			tbl_admin.login					,
			TO_CHAR(tbl_hd_chamado.data,'DD/MM/YY HH24:MI') AS data,
			titulo							,
			status							,
			atendente						,
			prioridade_supervisor			,
			tbl_fabrica.nome AS fabrica_nome
		FROM tbl_hd_chamado
		JOIN tbl_admin			ON tbl_hd_chamado.admin			= tbl_admin.admin
		JOIN tbl_fabrica		ON tbl_admin.fabrica			= tbl_fabrica.fabrica
		JOIN tbl_tipo_chamado	ON tbl_tipo_chamado.tipo_chamado= tbl_hd_chamado.tipo_chamado
		WHERE tbl_hd_chamado.fabrica_responsavel = 10
		AND tbl_fabrica.fabrica = $login_fabrica
		AND NOT (tbl_hd_chamado.status IN('Resolvido','Cancelado','Aprovação','Novo'))
		ORDER BY $ordemPR tbl_hd_chamado.data DESC";
$res = pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {    ?>
	<table width='750' align='center' cellpadding='0' cellspacing='0' border='0' id='tbl_hd_ativos' class='tabela'>
		<thead>
			<tr>
				<th background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></th>
				<th background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='<?=$colsTblHDs?>' width='100%'>
					Chamados em análise/desenvolvimento no TELECONTROL <span style='font-size:9px'>(<?=@pg_numrows($res)?>)</span>
				</th>
				<th background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></th>
			</tr>
			<tr style='font-size:9px;'>
				<th>N°</th>
				<?=$colPR //Coluna Prioridade?>
				<th nowrap>Título</th>
				<th nowrap>Tipo</th>
				<th nowrap>Status&nbsp;</th>
				<th nowrap>Data</th>
				<th nowrap>Solicitante&nbsp;</th>
				<th nowrap>Previsão Término</th>
			</tr>
		</thead>
		<tbody>
<?
	$cor='#F2F7FF';
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$hd_chamado           = pg_result($res,$i,hd_chamado);
		$admin                = pg_result($res,$i,admin);
		$login                = pg_result($res,$i,login);
		$tipo_chamado         = pg_result($res,$i,descricao);
		$data                 = pg_result($res,$i,data);
		$titulo               = pg_result($res,$i,titulo);
		$status               = pg_result($res,$i,status);
		$atendente            = pg_result($res,$i,atendente);
		$prioridade_hd        = pg_result($res,$i,prioridade_supervisor);
		$nome_completo        = trim(pg_result($res,$i,nome_completo));
		$fabrica_nome         = trim(pg_result($res,$i,fabrica_nome));
		$previsao_termino     = pg_result($res,$i,previsao_termino);

		$cor = ($cor == '#FFFFFF') ? '#F2F7FF' : '#FFFFFF';

		if($status =="Análise" AND $exigir_resposta <> "t"){
			$bolinha = 'status_azul.gif';
		}elseif($exigir_resposta == "t" AND $status<>'Cancelado'OR ($status == "Resolvido" AND strlen($resolvido)==0 )) {
			$bolinha = 'status_vermelho.gif';
		}elseif (($status == "Resolvido" AND strlen($resolvido)>0) OR $status == "Cancelado") {
			$bolinha = 'status_verde.gif';
		}elseif ($status == "Aprovação") {
			$bolinha = 'status_amarelo.gif';
		}else{
			$bolinha = 'status_azul.gif';
		}
		// HDs NÃO resolvidos ou cancelados, mostrar em vermelho
		$class_status = (($status != 'Resolvido') and ($status != 'Cancelado')) ? ' class="negrito vermelho"' : '';
		$nome         = ($nome_completo) ? $nome_completo : $login;

		if ($prioriza_hds) {
			if (in_array($prioridade_hd, array(null, true, false, 't', 'f'), true)) $prioridade_hd = ''; // Só acieta valores não booleanos
			$select_pr = "<input type='text' name='prioridade_$i' id='hd_prioridade_$i' hd='$hd_chamado'
					value='$prioridade_hd' size='1' maxlength='3' style='text-align:center'>\n";
		}

?>
		<tr style='background-color:<?=$cor?>;height:25px'>
			<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
			<td><img src='/assist/admin/imagens_admin/<?=$bolinha?>' align='absmiddle'>&nbsp;<?=$hd_chamado?>&nbsp;</td>
			<?if ($prioriza_hds) echo "<td>$select_pr</td>";	?>
			<td><a href='chamado_detalhe.php?hd_chamado=<?=$hd_chamado?>'><?=$titulo?></a></td>
			<td nowrap><?=$tipo_chamado?>&nbsp;</td>
			<td nowrap<?=$class_status?>><?=$status?></td>
			<td nowrap>&nbsp;<?=$data?>&nbsp;</td>
			<td class='Conteudo'><?=$nome?></td>
			<td nowrap>&nbsp;<?=$previsao_termino?>&nbsp;</td>
			<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
		</tr>
<?	}
//fim imprime chamados
?>
		<tr>
			<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
			<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='<?=$colsTblHDs?>' align = 'center' width='100%'></td>
			<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
		</tr>
		</tbody>
	</table>
<?}
/////////////////////FIM DOS CHAMADOS NO TELECONTROL

//fim imprime chamados

//conta chamados em aprovação que já estão na segunda fase, ou seja, já tem horas para aprovar.
$sql2etapa = "SELECT count(*) as qtd_hd_2etapa
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica
		WHERE tbl_hd_chamado.fabrica_responsavel = 10
		AND tbl_fabrica.fabrica = $login_fabrica
		AND (tbl_hd_chamado.status ='Aprovação' or tbl_hd_chamado.status ='Novo')
		AND hora_desenvolvimento > 0
		/* AND tbl_hd_chamado.data_envio_aprovacao IS NOT NULL */;
		";
$res2etapa     = pg_exec ($con,$sql2etapa);
$qtd_hd_2etapa = pg_result($res2etapa,0,qtd_hd_2etapa);
//echo "qtde".$qtd_hd_2etapa;
$sql = "SELECT
			hd_chamado                      ,
			tbl_hd_chamado.admin            ,
			tbl_admin.nome_completo         ,
			tbl_admin.login                 ,
			TO_CHAR(tbl_hd_chamado.data, 'DD/MM HH24:MI') AS data,
			titulo                          ,
			status                          ,
			atendente                       ,
			prioridade_supervisor           ,
			tbl_fabrica.nome AS fabrica_nome,
			hora_desenvolvimento
		FROM tbl_hd_chamado
		JOIN tbl_admin   ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica    = tbl_fabrica.fabrica
		WHERE tbl_hd_chamado.fabrica_responsavel = 10
		AND tbl_fabrica.fabrica = $login_fabrica
		AND (tbl_hd_chamado.status ='Aprovação' OR tbl_hd_chamado.status ='Novo')
		/* AND tbl_hd_chamado.data_envio_aprovacao IS NOT NULL */
		ORDER BY $ordemPR tbl_hd_chamado.status,tbl_hd_chamado.data DESC";
$res = pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {    ?>
	<table width='700' align='center' cellpadding='0' cellspacing='0' border='0' id='tbl_hd_aprova' class='tabela'>
	<thead>
		<tr>
			<th background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></th>
			<th background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='<?=$colsTblHDsAprova?>' align='center' width='100%'>
				Chamados Aguardando Aprovação <span style='font-size:9px;vertical-align:middle'>(<?=@pg_numrows($res)?>)</span>
			</th>
			<th background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></th>
		</tr>
		<tr style='font-size: 9px;'>
			<th>N°</th>
			<th nowrap>Título</th>
			<th>Status&nbsp;</th>
			<th>Data</th>
			<th>Solicitante</th>
			<th>Etapa</th>
			<?=$colPR //Coluna Prioridade?>
			<th>Franquia/Hora</th>
			<th align='center'>Ação</th>
		</tr>
	</thead>
	<tbody>
<?
//inicio imprime chamados
	$cor='#F2F7FF';
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$hd_chamado           = pg_result($res,$i,hd_chamado);
		$admin                = pg_result($res,$i,admin);
		$login                = pg_result($res,$i,login);
		$data                 = pg_result($res,$i,data);
		$titulo               = pg_result($res,$i,titulo);
		$status               = pg_result($res,$i,status);
		$atendente            = pg_result($res,$i,atendente);
		$prioridade_hd        = pg_result($res,$i,prioridade_supervisor);
		$nome_completo        = trim(pg_result($res,$i,nome_completo));
		$fabrica_nome         = trim(pg_result($res,$i,fabrica_nome));
		$hora_desenvolvimento = pg_result($res,$i,hora_desenvolvimento);

		$cor = ($cor == '#FFFFFF') ? '#F2F7FF' : '#FFFFFF';

		$etapa = (strlen($hora_desenvolvimento)==0) ? "1ª Etapa" : "2ª Etapa";

		$class_status = (($status != 'Resolvido') and ($status != 'Cancelado')) ? '' : '';
		$nome         = ($nome_completo) ? $nome_completo : $login;

		if ($prioriza_hds) {
			if (in_array($prioridade_hd, array(null, true, false, 't', 'f'), true)) $prioridade_hd = ''; // Só acieta valores não booleanos
			$select_pr = "<input type='text' name='prioridade_$i' id='hda_prioridade_$i' hd='$hd_chamado'
					value='$prioridade_hd' size='1' maxlength='3' style='text-align:center'>\n";
		}

		echo "<tr style='cursor:default;height:25;background-color:$cor'>\n";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>\n";
		echo "<td nowrap><img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'> $hd_chamado</td>\n";
		echo "<td style='width:300px;text-align:left;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;'><a href='chamado_detalhe.php?hd_chamado=$hd_chamado' title='$titulo'>&nbsp;$titulo&nbsp;</a></td>\n";
		echo "<td nowrap class='negrito vermelho'>&nbsp;$status&nbsp;</td>\n";
		echo "<td nowrap>&nbsp;$data &nbsp;</td>\n";
		echo "<td nowrap>$nome</td>\n";
		echo "<td nowrap>&nbsp;$etapa&nbsp;</td>\n";
		if ($prioriza_hds) echo "<td>$select_pr</td>\n";
		echo "<td align='center'>&nbsp;$hora_desenvolvimento&nbsp;</td>\n";
		if ($supervisor AND ($status=='Aprovação' or $status=='Novo')){
			if(in_array($login_fabrica,array(76,80,59,40,52))) {
				$cond_hora = " hora_franqueada *2 ";
			}else{
				$cond_hora = " hora_franqueada+saldo_hora ";
			}
			$sql3 = "SELECT hora_utilizada
						FROM  tbl_hd_franquia
						JOIN  tbl_hd_chamado ON tbl_hd_chamado.fabrica = tbl_hd_franquia.fabrica
						WHERE tbl_hd_franquia.fabrica   = $login_fabrica
						AND   tbl_hd_chamado.hd_chamado = $hd_chamado
						AND   periodo_fim is null
						GROUP BY hora_utilizada,hora_franqueada, saldo_hora,hd_franquia,hora_desenvolvimento
						HAVING  ($cond_hora) < (hora_utilizada + hora_desenvolvimento)
						ORDER BY hd_franquia desc limit 1";
			$res3 = pg_exec ($con,$sql3);
			if(pg_numrows($res3)>0){
				if(strlen($hora_desenvolvimento)>0){
					$href = "<a href='aprova_faturada.php?hd_chamado=$hd_chamado' target='_blank'>";
				}else{
					$href = "<a href='$PHP_SELF?hd_chamado=$hd_chamado&aprova=sim'>";
				}
			}else{
				if(strlen($hora_desenvolvimento)>0){
					$href = "<a href='$PHP_SELF?hd_chamado=$hd_chamado&aprova_execucao=sim'>";
				}else{
					$href = "<a href='$PHP_SELF?hd_chamado=$hd_chamado&aprova=sim'>";
				}
			}
			if($numero_hd_no_telecontrol >= ($backlog )){
				echo "<td  nowrap title='Somente após resolvido o chamado que está em desenvolvimento que poderá aprovar o próximo chamado.'>";
				echo "<img src='imagem/btn_ok.gif'border='0' align='absmiddle'>EM ESPERA";
			}else{
				//echo "qtde".$qtd_hd_2etapa;
				if(strlen($qtd_hd_2etapa)>0 and $qtd_hd_2etapa>0 and $etapa =='1ª Etapa'){
					echo "<td  nowrap title='Somente após resolvido o chamado que está em desenvolvimento que poderá aprovar o próximo chamado.'>";
					echo "<img src='imagem/btn_ok.gif'border='0' align='absmiddle'>EM ESPERA";
				}else{
					echo "<td nowrap>";
					echo "$href<img src='imagem/btn_ok.gif'border='0' align='absmiddle'>APROVA</a><br>".
						 "<a href='$PHP_SELF?hd_chamado=$hd_chamado&cancela=sim'><img src='imagem/btn_deletar.gif'border='0' align='absmiddle'>CANCELA</a>\n";
				}
			}
		}else{
			echo "<td  nowrap title='Somente após resolvido o chamado que está em desenvolvimento que poderá aprovar o próximo chamado.'>";
			echo "<img src='imagem/btn_ok.gif'border='0' align='absmiddle'>EM ESPERA";
		}
		echo"</td>";

		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";

	}

//fim imprime chamados

	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='$colsTblHDsAprova' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "</table>";
}

echo "</td>";
echo "</tr>";

echo "</table>";
// if ($supervisor) echo "Fábrica $login_fabrica, usuário: $login_admin ($login_login)<br>Banco $dbnome";
include "rodape.php" ?>
</body>
</html>
