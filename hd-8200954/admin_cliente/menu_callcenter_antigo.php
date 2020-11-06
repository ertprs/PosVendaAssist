<?
include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
$layout_menu = "callcenter";
$title = "Menu Call-Center";
include 'cabecalho.php';



$login_admin;

$sql_om = "
     SELECT substr(tbl_marca.nome, 0, 6) as marca
       FROM tbl_admin
       JOIN tbl_cliente_admin USING(admin)
       JOIN tbl_marca         USING(marca)
      WHERE tbl_admin.admin = $login_admin";

$res_om = pg_exec($con,$sql_om);

if (pg_num_rows($res_om)>0) {
	$marca = pg_result($res_om,0,0);
}


?>

<style type="text/css">

body {
	text-align: center;
}

.cabecalho {
	color: black;
	border-bottom: 2px dotted WHITE;
	font-size: 12px;
	font-weight: bold;
}

.descricao {
	padding: 5px;
	color: black;
	font-size: 12px;
	font-weight: normal;
	text-align: justify;
}

a:link.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:visited.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:hover.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: black;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
	background-color: #ced7e7;
}

</style>

<br>
<?php
if ($login_cliente_admin = 31461 && $login_fabrica == 85) {
?>
	<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF'  align = 'center'>
		<tr>
			<td width="10"><img border="0" src="imagens/corner_se_laranja.gif"></td>
			<td class="cabecalho">RELATÓRIOS</td>
			<td width="10"><img border="0" src="imagens/corner_sd_laranja.gif"></td>
		</tr>
	</table>
	<table border="0" width="700px" border="0" cellpadding="0" cellspacing="0" align = 'center'>
		<tr bgcolor='#FAFAFA'>
			<td width='25'><img src='imagens/rel25.gif'></td>
			<td nowrap width='260'><a href='relatorio_ambev.php' class='menu'>Relatório AMBEV</a></td>
			<td class='descricao'></td>
		</tr>
	</table>
<?php
} else {
?>
	<? if(in_array($login_fabrica, array(6, 11, 24, 15)) OR 1==1 AND !in_array($login_fabrica, array(25, 52))){ ?>
	<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
	<tr>
		<td width='10'><img border="0" src="imagens/corner_se_laranja.gif"></td>
		<td class="cabecalho">CALL-CENTER <? if($login_fabrica==6) echo "NOVO"; ?></TD>
		<td width='10'><img border="0" src="imagens/corner_sd_laranja.gif"></td>
	</tr>
	</table>
	<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
	<!-- ================================================================== -->
<!--
	<? if($login_fabrica <> 3){ ?>
	<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/marca25.gif'></td>
		<td nowrap width='260'><a href='cadastra_callcenter.php' class='menu'>Cadastra Atendimento Call-Center</a></td>
		<td nowrap class='descricao'>Cadastro de atendimento do Call-Center
		<? if($login_fabrica<>  10 AND $login_fabrica <>  7 AND $login_fabrica <> 2 AND $login_fabrica <>  45 AND $login_fabrica <>  5 AND $login_fabrica <>  46 AND $login_fabrica < 50 AND $login_fabrica <>  43 AND $login_fabrica <>  3){ ?>
		<BR><B><font size=1>ESTA TELA SERÁ DESATIVADA EM 05/09/2008<br> E PASSAR A USAR NOVA TELA</font></B>
		<?}?>
		</td>
	</tr>
	<? } ?>
	-->
	<!-- ================================================================== -->

	<? if($login_fabrica==30 || $login_fabrica == 7){
		if ($login_admin <> '3033' and $login_admin <> '3032') {
			?>

		<tr bgcolor='#f0f0f0'>
			<?php
			if($login_fabrica == 7){
				?>

				<td width='25'><img src='imagens/marca25.gif'></td>
					<td nowrap width='260'><a href='pre_os_cadastro_sac_filizola.php' class='menu'>Cadastra Atendimento OS
				</a></td>

				<?php
			}else{
				?>

				<td width='25'><img src='imagens/marca25.gif'></td>
					<td nowrap width='260'><a href='pre_os_cadastro_sac.php' class='menu'>Cadastra 	Atendimento OS
				</a></td>

				<?php
			}
			?>

			<td nowrap class='descricao'>Cadastro de OS para Postos Autorizados</td>
		</tr>
		<tr bgcolor='#FAFAFA'>
			<td width='25'><img src='imagens/marca25.gif'></td>
				<td nowrap width='260'><a href='callcenter_pendente_interativo.php' class='menu'>
					<?php
						if($login_fabrica == 7){
						?>
							Consulta aos Chamados
						<?php
						}else{
							?>
							Pendência de Atendimentos
							<?php
						}
					?>

			</a></td>
			<?php
				if($login_fabrica == 7){
					?>
					<td nowrap class='descricao'>Consulta aos Chamados</td>
					<?php
				}else{
					?>
					<td nowrap class='descricao'>Consulta a pendência de atendimentos (em aberto)</td>
					<?php
				}
			?>

		</tr>
		<? }
			if ($marca=='AMBEV') { ?>
		<tr bgcolor='#f0f0f0'>
			<td width='25'><img src='imagens/marca25.gif'></td>
				<td nowrap width='260'><a href='chamados_ambev.php' class='menu'>Atendimentos AMBEV
			</a></td>
			<td class='descricao'>Verifica Atendimentos integrados ALERTxTELECONTROL</td>
		</tr>
		<tr bgcolor='#FAFAFA'>
			<td width='25'><img src='imagens/marca25.gif'></td>
				<td nowrap width='260'><a href='defeitos_ambev.php' class='menu'>Defeitos em OS AMBEV
			</a></td>
			<td class='descricao'>Relatórios que mostra a quantidade de defeitos nas OS no útimo ano</td>
		</tr>
		<tr bgcolor='#f0f0f0'>
			<td width='25'><img src='imagens/marca25.gif'></td>
				<td nowrap width='260'><a href='pecas_ambev.php' class='menu'>Peças Utilizadas em OS AMBEV
			</a></td>
			<td class='descricao'>Relatórios que mostra a quantidade de peças utilizadas nas OS no útimo ano</td>
		</tr>
		<?}?>
	<?} else if ($login_fabrica == 156) {?>
		<tr bgcolor='#f0f0f0'>
			<td width='25'><img src='imagens/marca25.gif'></td>
			<td nowrap width='260'>
				<a href='pre_os_cadastro_sac.php' class='menu'>Abre Chamados</a>
			</td>
			<td nowrap class='descricao'>Cadastro de Atendimento para o Call-Center</td>
		</tr>
		<tr bgcolor='#FAFAFA'>
			<td width='25'><img src='imagens/marca25.gif'></td>
			<td nowrap width='260'>
				<a href='consulta_atendimento_cliente_admin.php' class='menu'>Consulta aos Chamados</a>
			</td>
			<td nowrap class='descricao'>Consulta de Atendimento para o Call-Center</td>
		</tr>
	<?}else{?>
		<tr bgcolor='#f0f0f0'>
			<td width='25'><img src='imagens/marca25.gif'></td>
				<td nowrap width='260'><a href='pre_os_cadastro_sac.php' class='menu'>Cadastra Atendimento Pré-OS
			<? if($login_fabrica == 6) echo "( NOVO )"; ?>
			</a></td>
			<td nowrap class='descricao'>Cadastro de Pré-Os para Postos Autorizados</td>
		</tr>
	<?}?>
<?}?>

	<tr bgcolor='#D9E2EF'>
		<td colspan="3"><img src="imagens/spacer.gif" height="3"></td>
	</tr>
	</table>


<br>
<?
if ($login_admin <> '3033' and $login_admin <> '3032' AND $login_fabrica <> 156) {
?>
<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF'  align = 'center'>
<tr>
	<td width="10"><img border="0" src="imagens/corner_se_laranja.gif"></td>
	<td class="cabecalho">ORDENS DE SERVIÇO</td>
	<td width="10"><img border="0" src="imagens/corner_sd_laranja.gif"></td>
</tr>
</table>
<table border="0" width="700px" border="0" cellpadding="0" cellspacing="0" align = 'center'>
<!-- ================================================================== -->
<!-- ================================================================== -->
<?php
if ($login_fabrica == 1) {
	$os_consulta_lite = "os_consumidor_consulta.php";
} else if (in_array($login_fabrica, array(96, 52))) {
	$os_consulta_lite = "os_consulta_lite.php";
} else {
	$os_consulta_lite = "cliente_admin_os_consulta_lite.php";
}
?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='<?=$os_consulta_lite?>' class='menu'>Consulta Ordens de Serviço</a></td>
	<td nowrap class='descricao'>Consulta OS Lançadas</td>
</tr>

<? /*if($login_fabrica == 52){ ?>
<tr bgcolor='#D9E2EF'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='aprova_os_reincidente.php' class='menu'>Auditoria da OS Reincidente</a></td>
	<td class='descricao'>Consulta de OS's Reincidentes</td>
</tr>
<? }*/ ?>
</table>

<br>
<?php if (!in_array($login_fabrica,array(7,96, 158))) { ?>
		<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF'  align = 'center'>
		<tr>
			<td width="10"><img border="0" src="imagens/corner_se_laranja.gif"></td>
			<td class="cabecalho">RELATÓRIOS</td>
			<td width="10"><img border="0" src="imagens/corner_sd_laranja.gif"></td>
		</tr>
		</table>
		<table border="0" width="700px" border="0" cellpadding="0" cellspacing="0" align = 'center'>
		<!-- ================================================================== -->
		<!-- ================================================================== -->
		<? if (!in_array($login_fabrica, array(52))) { ?>
		<tr bgcolor='#FAFAFA'>
			<td width='25'><img src='imagens/rel25.gif'></td>
			<td nowrap width='260'><a href='relatorio_tempo_conserto_mes.php' class='menu'>Permanência em conserto no mês</a></td>
			<td class='descricao'>Relatório que mostra o tempo (dias) de permanência do produto na assistência técnica no mês.</td>
		</tr>
		<? } ?>

<? if (in_array($login_fabrica, array(52))) { ?>
	<tr bgcolor="#FAFAFA">
		<td width='25'><img src='imagens/rel25.gif'></td>
		<td nowrap width='260'><a href='relatorio_tempo_os_aberta.php' class='menu'>Relatorio de OS em abertos em dias</a></td>
		<td class='descricao'>Relatorio de OS em abertos em dias, considerando a data de abertura para o dia da geração do relatorio</td>
	</tr>
<? }
if (!in_array($login_fabrica, array(52))) {
?>
		<tr bgcolor='#FAFAFA'>
			<td width='25'><img src='imagens/rel25.gif'></td>
			<td nowrap width='260'><a href='relatorio_callcenter_atendimento.php' class='menu'>Relatório dos atendimentos por posto</a></td>
			<td class='descricao'>Relatório que mostra as OS atendidas de acordo com os filtros empregados.</td>
		</tr>
<? }
	} //HD 397756
	else if (!in_array($login_fabrica, array(158))) {
?>
		<table width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF'  align = 'center'>
			<tr>
				<td width="10"><img border="0" src="imagens/corner_se_laranja.gif"></td>
				<td class="cabecalho">GERENCIAR USUÁRIO</td>
				<td width="10"><img border="0" src="imagens/corner_sd_laranja.gif"></td>
			</tr>
			<table border="0" width="700px" border="0" cellpadding="0" cellspacing="0" align = 'center'>
			<tr bgcolor='#f0f0f0'>
				<td width='25'><img src='imagens/rel25.gif'></td>
				<td nowrap width='260'><a href='altera_senha.php' class='menu'>Alterar Senha</a></td>
				<td class='descricao'>Permite alterar a senha do seu usuário no sistema.</td>
			</tr>
		</table>
<? }
}?>
<!-- ================================================================== -->

<!-- ================================================================== -->
<!-- ================================================================== -->
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<br>
<?}?>
<? include "../admin/rodape.php" ?>
