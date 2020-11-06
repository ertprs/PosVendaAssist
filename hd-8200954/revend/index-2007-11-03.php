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
		A BRITÂNIA e a TELECONTROL estão desenvolvendo um meio de informação centralizada para gerenciamento do fluxo de produtos entre a REVENDA <-> REDE AUTORIZADA & REVENDA <-> BRITÂNIA.<br>
		Trata-se de um sistema via web onde a revenda estará informando pelo site todos as remessas para conserto, troca e devolução enviadas à Rede e à Fábrica. A grande vantagem é a informação acessível e on-line para todos.<br>
		
		
		Em breve, quando este sistema estiver completo será possível:<br>
		
		<li> consultar o andamento por Produto, por Nota Fiscal, por Data e por Lote.<br>
		
		<li> administração das pendências e divergência nas remessas.<br>
		
		<li> controle eficaz dos prazos.<br>
		
		<li> importar a relação de produtos comercializados entre a BRITÂNIA e a REVENDA com os códigos internos de ambas.<br>
		
		<li> solicitações de coletas e confirmação de recebimento.<p>
		Informação precisa e em tempo real é vital para gerenciar recursos de nossas empresas.
		
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
				<td>Consulta de Ordens de Serviço (indisponível)</td>
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
 