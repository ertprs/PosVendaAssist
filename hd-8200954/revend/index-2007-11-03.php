<?
require '../dbconfig.php';
require '../includes/dbconnect-inc.php';

require 'autentica_revenda.php';
include '../ajax_cabecalho.php';

$aba = 1;
$title = "Sistema de Gerenciamento de Revendas";
include 'cabecalho.php';

?>
<br>
<center>
<table width='98%' align='center'>
	<tr><td align='center'><center>
<div id="m_1" class="modbox">
	<h2 class="modtitle">
		<div id="m_1_h">
			<a class="mtlink" id="m_1_url" href="#">
			<span id="m_1_title" class="modtitle_text"><font color="#3366cc">Seja bem vindo!</font></span>
			</a>
		</div>

	</h2>

	<div id="m_1_b" class="modboxin">

		<div id="ftl_1_0" class="uftl" style='text-align:justify'>
		A BRIT�NIA e a TELECONTROL est�o desenvolvendo um meio de informa��o centralizada para gerenciamento do fluxo de produtos entre a REVENDA <-> REDE AUTORIZADA & REVENDA <-> BRIT�NIA.<br>
		Trata-se de um sistema via web onde a revenda estar� informando pelo site todos as remessas para conserto, troca e devolu��o enviadas � Rede e � F�brica. A grande vantagem � a informa��o acess�vel e on-line para todos.<br>
		
		
		Em breve, quando este sistema estiver completo ser� poss�vel:<br>
		
		<li> consultar o andamento por Produto, por Nota Fiscal, por Data e por Lote.<br>
		
		<li> administra��o das pend�ncias e diverg�ncia nas remessas.<br>
		
		<li> controle eficaz dos prazos.<br>
		
		<li> importar a rela��o de produtos comercializados entre a BRIT�NIA e a REVENDA com os c�digos internos de ambas.<br>
		
		<li> solicita��es de coletas e confirma��o de recebimento.<p>
		Informa��o precisa e em tempo real � vital para gerenciar recursos de nossas empresas.
		
		</div>
	</div>
</div>
	<div id="m_1_b" class="modboxin">

		<div id="ftl_1_0" class="uftl" style='text-align:justify'>
			<table align='center' width='90%'>
				<tr>
				<td><a href='lote_cadastro.php'><img src='imagem/img_cadastro.gif'></a></td>
				<td><a href='lote_cadastro.php' >Cadastro de Lotes</a></td>
				<td><img src='imagem/img_cadastro2.gif'></td>
				<td>Consulta de Ordens de Servi�o (indispon�vel)</td>
			</tr>
			<tr>
				<td><a href='lote_consulta.php'><img src='imagem/img_lupa.gif'></a></td>
				<td><a href='lote_consulta.php'>Consulta de Notas Fiscais</td>
				<td><a href='revenda_cadastro.php'><img src='imagem/img_mala.gif'></a></td>
				<td><a href='revenda_cadastro.php'>Dados da Empresa</a></td>
			</tr>
			<tr>
				<td><a href='nf_entrada.php'><img src='imagem/img_prancheta.gif'></a></td>
				<td><a href='nf_entrada.php'>Recebimento de Produtos</a></td>
				<td><a href='produto_lista.php'><img src='imagem/img_foto.gif'></a></td>
				<td><a href='produto_lista.php'>Lista de Produtos</a></td>
			</tr>
			</table>
		</div>
	</div>
</div>
</td></tr></table>

<?
include "rodape.php";
?>
 