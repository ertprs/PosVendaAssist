<?
require '../dbconfig.php';
require '../includes/dbconnect-inc.php';

require 'autentica_revenda.php';
include '../ajax_cabecalho.php';

$aba = 1;
$title = "Sistema de Gerenciamento de Revendas";
include 'cabecalho.php';



$btn_acao = trim($_POST['btn_acao']);

if (strlen($btn_acao)>0 AND $btn_acao == "gravar"){
	$qtde_itens 	= trim($_POST['qtde_itens']);
	$aprovacao 		= trim($_POST['aprovacao']);
	$motivo_reprova	= trim($_POST['motivo_reprova']);

	$motivo_reprova = str_replace("'","´",$motivo_reprova);
	$motivo_reprova = str_replace('"',"´´",$motivo_reprova);
	$motivo_reprova_aux = $motivo_reprova;

	if(strlen($motivo_reprova)>0){
		$motivo_reprova = "'".$motivo_reprova."'";
	}else{
		$motivo_reprova = " NULL ";
	}

	$os_aprovadas_a		= array();
	$os_reprovadas_a	= array();
	$sua_os_a			= array();
	$postos_a			= array();

	for ($i=0; $i<$qtde_itens; $i++){
		$os			= trim($_POST["os_$i"]);
		$sua_os		= trim($_POST["sua_os_$i"]);
		$orcamento	= trim($_POST["orcamento_$i"]);
		$posto		= trim($_POST["posto_$i"]);

		if (strlen($os)>0){

			$res = pg_exec ($con,"BEGIN TRANSACTION");
			
			array_push($postos_a,$posto);
			array_push($sua_os_a,$sua_os);

			if ($aprovacao=="aprovar"){

				array_push($os_aprovadas_a,$os);

				$nr_pecas = 20;
				for ($j=0; $j< $nr_pecas; $j++){

					$item_ckeck = trim($_POST["item_chk_$i-$j"]);
					$item		= trim($_POST["item_peca_$i-$j"]);

					if (strlen($item_ckeck)==0 AND strlen($item)>0){
						$sql = "UPDATE tbl_orcamento_item SET
								servico_realizado =  (SELECT servico_realizado FROM tbl_servico_realizado WHERE descricao LIKE '%juste%' AND fabrica=$login_fabrica AND ativo IS TRUE AND gera_pedido IS NOT TRUE ORDER BY descricao DESC LIMIT 1),
								preco = 0
								WHERE orcamento_item = $item
								AND pedido IS NULL";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}
				$sql = "UPDATE tbl_orcamento SET
						aprovado = 't', data_aprovacao = CURRENT_TIMESTAMP 
						WHERE os=$os 
						AND empresa = $login_fabrica";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				#Criar Help-Desk
				$sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE orcamento = $orcamento";
				$res_chamado = pg_exec($con, $sql);
				if(pg_numrows($res_chamado)>0){
					$hd_chamado = pg_result($res_chamado,0,hd_chamado);
				}else{
					$sql = "INSERT INTO tbl_hd_chamado (titulo,orcamento) VALUES ('Orçamento da OS Nº $sua_os',$orcamento)";
					$res_chamado = pg_exec($con, $sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT currval('seq_hd_chamado')";
					$res_chamado = pg_exec($con, $sql);
					$hd_chamado = pg_result($res_chamado,0,0);
				}

				$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado,comentario) VALUES ($hd_chamado,'Orçamento aprovado')";
				$res_chamado = pg_exec($con, $sql);
				$msg_erro .= pg_errormessage($con);

			}else{

				//Reprovar

				array_push($os_reprovadas_a,$os);

				$sql = "UPDATE tbl_orcamento SET
							aprovado            = 'f',
							data_reprovacao     = CURRENT_TIMESTAMP ,
							motivo_reprovacao   = $motivo_reprova
						WHERE os=$os 
						AND empresa = $login_fabrica";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE tbl_orcamento_item SET
						servico_realizado = (SELECT servico_realizado FROM tbl_servico_realizado WHERE descricao LIKE '%juste%' AND fabrica=$login_fabrica AND ativo IS TRUE AND gera_pedido IS NOT TRUE ORDER BY descricao DESC LIMIT 1),
						preco = 0
						WHERE orcamento = $orcamento
						AND pedido IS NULL";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				#Criar Help-Desk
				$sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE orcamento = $orcamento";
				$res_chamado = pg_exec($con, $sql);
				if(pg_numrows($res_chamado)>0){
					$hd_chamado = pg_result($res_chamado,0,hd_chamado);
				}else{
					$sql = "INSERT INTO tbl_hd_chamado (titulo,orcamento) VALUES ('Orçamento da OS Nº $sua_os',$orcamento)";
					$res_chamado = pg_exec($con, $sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT currval('seq_hd_chamado')";
					$res_chamado = pg_exec($con, $sql);
					$hd_chamado = pg_result($res_chamado,0,0);
				}

				$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado,comentario) VALUES ($hd_chamado,'Orçamento reprovado. Motivo: $motivo_reprova_aux')";
				$res_chamado = pg_exec($con, $sql);
				$msg_erro .= pg_errormessage($con);
			}

			if (strlen ($msg_erro) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");
			}else{
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
				$msg_erro="";
			}
		}
	}

	//Envio de email para o PA.
	if (count($postos_a)>0){

		$postos_a = array_unique($postos_a);

		for ($i=0; $i<count($postos_a); $i++){

			$sua_os_aprovadas  = array();
			$sua_os_reprovadas = array();

			$PA = $postos_a[$i];

			if (strlen($PA)>0){
				$sql = "SELECT email,nome FROM tbl_posto WHERE posto = $PA";
				$res = pg_exec($con, $sql);
				if(pg_numrows($res)>0){
					$posto_email = pg_result($res,$i,email);
					$posto_nome  = pg_result($res,$i,nome);
				}

				$msg_os  = "";
				
				if (count($os_aprovadas_a)>0){
					$sql = "SELECT sua_os FROM tbl_os WHERE posto = $PA AND os IN (".implode(",",$os_aprovadas_a).")";
					$res = pg_exec($con, $sql);
					if(pg_numrows($res)>0){
						for ($j=0; $j<pg_numrows($res); $j++){
							$res_sua_os = pg_result($res,$j,sua_os);
							array_push($sua_os_aprovadas,$res_sua_os);
						}
						$msg_os .= "<b>OS Aprovadas:</b> ".implode(", ",$sua_os_aprovadas);
					}
				}

				$msg_os .= "<br><br>";

				if (count($os_reprovadas_a)>0){
					$sql = "SELECT sua_os FROM tbl_os WHERE posto = $PA AND os IN (".implode(",",$os_reprovadas_a).")";
					$res = pg_exec($con, $sql);
					if(pg_numrows($res)>0){
						for ($j=0; $j<pg_numrows($res); $j++){
							$res_sua_os = pg_result($res,$j,sua_os);
							array_push($sua_os_reprovadas,$res_sua_os);
						}
					}
					$msg_os .= "<b>OS Reprovadas:</b> ".implode(", ",$sua_os_reprovadas);
					$msg_os .= "<br><b>Motivo:</b> $motivo_reprova_aux";
				}

				$nome      = "Telecontrol";
				$email     = 'fabio@telecontrol.com.br';
				$mensagem .= "MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL <br><br><h4>Orçamentos</h4> <br>$msg_os<br><br><br>____________________________________________<br>\n";
				$mensagem .= "Telecontrol Networking<br>\n";
				$mensagem .= 'www.telecontrol.com.br';
				$assunto   = "e-Mail de aprovação de OS";
				$boundary  = "XYZ-" . date("dmYis") . "-ZYX";
				$mens      = "--$boundary\n";
				$mens     .= "Content-Transfer-Encoding: 8bits\n";
				$mens     .= "Content-Type: text/html; charset=\"ISO-8859-1\"\n\n";
				$mens     .= "$mensagem\n";
				$mens     .= "--$boundary\n";
				$headers   = "MIME-Version: 1.0\n";
				$headers  .= "Date: ".date("D, d M Y H:i:s O")."\n";
				$headers  .= "From: \"Telecontrol\" <suporte@telecontrol.com.br>\r\n";
				$headers  .= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n";
				@mail($email, $assunto,$mens, $headers); 
			}
		}
	}
}


?>
<br>

<style>

	.Tabela {
		border-collapse: collapse;
		font-size: 1.1em;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	}

	.Tabela thead {
		background: #596D9B;
		color: #fff;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
	}

	.Tabela tfoot {
		background: #596D9B;
		color: #fff;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
	}

	.Tabela td {
		padding: 4px 11px;
		border-bottom: 1px solid #95bce2;
		vertical-align: middle;
	}

	.Tabela tr.alt td {
		background: #D9E2EF;
	}

	.Tabela tr.over td {
		background: #bcd4ec;
	}
	.Tabela tr.clicado td {
		background: #FF9933;
	}
	.Tabela tr.sem_defeito td {
		background: #FFCC66;
	}
	.Tabela tr.mais_30 td {
		background: #FF0000;
	}
	.Tabela tr.erro_post td {
		background: #99FFFF;
	}


	.TabelaItem {
		border-collapse: collapse;
		font-size: 10px;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	}

	.TabelaItem thead {
		color: #000000;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
	}

	.TabelaItem tfoot {
		background: #596D9B;
		color: #fff;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
	}

	.TabelaItem td {
		padding: 4px 11px;
		border-bottom: 1px solid #CDDEFE;
		vertical-align: middle;
		background: #FFFFFF;
	}

	.TabelaItem tr.alt td {
		background: #FFFFFF;
	}

	.TabelaItem tr.over td {
		background: #bcd4ec;
	}
	.TabelaItem tr.clicado td {
		background: #FF9933;
	}
	.TabelaItem tr.sem_defeito td {
		background: #FFCC66;
	}
	.TabelaItem tr.mais_30 td {
		background: #FF0000;
	}
	.TabelaItem tr.erro_post td {
		background: #99FFFF;
	}
</style>

<script language='javascript'>
	function verificarAprova(sel){
		if (sel.value=='reprovar'){
			document.getElementById('motivo_reprova').style.display='inline';
		}else{
			document.getElementById('motivo_reprova').style.display='none';
		}
	}

	function SelecionaTodos() {
		$("input[@rel='orcamento_item']").each(function(i){
			this.checked = !this.checked;
		});
	}

	function MostraEsconde(linha,img){
		$('#'+linha).toggle();
		if (img.src == 'http://www.telecontrol.com.br/assist/imagens/mais.gif'){
			img.src = '../imagens/menos.gif';
		}else{
			img.src = '../imagens/mais.gif';
		}
		return ;
	}

</script>

<script type="text/javascript">
	$(function() {
		$("a[@rel='ajuda'],img[@rel='ajuda'],input[@rel='ajuda']").Tooltip({
			track: true,
			delay: 0,
			showURL: false,
			opacity: 0.85,
			fixPNG: true,
			showBody: " - ",
			extraClass: "regra"
		});
	});
</script>

<center>
<table width='98%' align='center'>
	<tr><td align='center'><center>

<div id="m_1" class="modbox">
<? /* Samuel tirou a mensagem de sistema novo....30/6
	<h2 class="modtitle">
		<div id="m_1_h">
			<a class="mtlink" id="m_1_url" href="#">
			<span id="m_1_title" class="modtitle_text"><font color="#3366cc">Controle de lotes de produtos para reparo 100% WEB</font></span>
			</a>
		</div>

	</h2>
	<div id="m_1_b" class="modboxin">
		<div id="ftl_1_0" class="uftl" style='text-align:justify'>
		A TELECONTROL está desenvolvendo um meio de informação centralizada para gerenciamento do fluxo de produtos entre a REVENDA <-> REDE AUTORIZADA & REVENDA <-> FABRICANTE.<br>
		Trata-se de um sistema via web onde a revenda estará informando pelo site todos as remessas para conserto, troca e devolução enviadas à Rede e à Fábrica. A grande vantagem é a informação acessível e on-line para todos.<br>
		
		
		Em breve, quando este sistema estiver completo será possível:<br>
		
		<li> consultar o andamento por Produto, por Nota Fiscal, por Data e por Lote.<br>
		
		<li> administração das pendências e divergência nas remessas.<br>
		
		<li> controle eficaz dos prazos.<br>
		
		<li> importar a relação de produtos comercializados entre a FÁBRICA e a REVENDA com os códigos internos de ambas.<br>
		
		<li> solicitações de coletas e confirmação de recebimento.<p>
		Informação precisa e em tempo real é vital para gerenciar recursos de nossas empresas.
		
		</div>
	</div>
*/?>
</div>
<?
$query = "SELECT 	tbl_os.os,
			tbl_os.sua_os, 
			tbl_orcamento.orcamento, 
			tbl_os.serie, 
			tbl_os.posto, 
			tbl_os.nota_fiscal_saida, 
			tbl_os.data_nf_saida,
			tbl_os.produto, 
			tbl_produto.descricao,
			tbl_orcamento.aprovado, 
			tbl_orcamento.total_mao_de_obra,
			tbl_orcamento.total_pecas
	FROM tbl_os
	JOIN tbl_os_revenda_item ON tbl_os_revenda_item.os_lote = tbl_os.os
	JOIN tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
	JOIN tbl_lote_revenda ON tbl_lote_revenda.lote_revenda = tbl_os_revenda.lote_revenda
	JOIN tbl_orcamento ON tbl_orcamento.os = tbl_os.os
	JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
	WHERE tbl_os.fabrica = $login_fabrica 
	AND tbl_os_revenda.lote_revenda IS NOT NULL
	AND tbl_lote_revenda.revenda = $login_revenda
	AND tbl_orcamento.aprovado IS NOT TRUE
	AND tbl_orcamento.aprovado IS NULL";
$orca = pg_exec($con, $query);
$numero_os = pg_numrows($orca);

if($numero_os>0){
?>
<!-- ORÇAMENTOS -->
<form name='frm_orcamento' method='post'>
<div id="m_1" class="modbox">
	<h2 class="modtitle">
		<div id="m_1_h">
			<a class="mtlink" id="m_1_url" href="#">
			<span id="m_1_title" class="modtitle_text"><font color="#3366cc">Ordens de Serviços com Orçamento a Serem Aprovados</font></span>
			</a>
		</div>
	</h2>

	<div id="m_1_b" class="modboxin">

		<div id="ftl_1_0" class="uftl" style='text-align:justify'>
			<table class='Tabela' width='98%' cellpadding='0' cellspacing='0'>
			
			<thead>
				<tr>
					<td></td>
					<td>OS</td>
					<td>Produto</td>
					<td>Nº Série</td>
					<td align='right'>Mão de Obra</td>
					<td align='right'>Peças</td>
					<td align='right'>Total</td>
					<td align='center'></td>
				</tr>
			</thead>
			<tbody>
		<?

				$total_geral=0;
				for ($i=0; $i< $numero_os; $i++){
					$os        			= pg_result($orca,$i,os);
					$sua_os    			= pg_result($orca,$i,sua_os);
					$posto				= pg_result($orca,$i,posto);
					$orcamento 			= pg_result($orca,$i,orcamento);
					$aprovado 			= pg_result($orca,$i,aprovado);
					$produto 			= pg_result($orca,$i,produto);
					$serie	 			= pg_result($orca,$i,serie);
					$nota_fiscal_saida	= pg_result($orca,$i,nota_fiscal_saida);
					$data_nf_saida		= pg_result($orca,$i,data_nf_saida);
					$descricao 			= pg_result($orca,$i,descricao);
					$total_mao_de_obra	= pg_result($orca,$i,total_mao_de_obra);
					$total_pecas 		= pg_result($orca,$i,total_pecas);
					if ($aprovado=='t'){
						$aprovado = "Aprovado";
					}else{
						$aprovado = "";
					}

					$classe="";
					if ($i % 2 == 0){
						$classe=" class='alt' ";
					}
					if (strlen($total_mao_de_obra)==0){
						$total_mao_de_obra = 0;
					}
					if (strlen($total_pecas)==0){
						$total_pecas = 0;
					}
					$total = $total_mao_de_obra + $total_pecas;
					$total_geral += $total;

					echo "<tr $classe>";
					echo "<td><img src='../imagens/mais.gif' onClick=\"javascript:MostraEsconde('linha_$i',this)\" style='cursor: pointer; cursor: hand;'></td>";
					echo "<td>$sua_os</td>";
					echo "<td>$descricao</td>";
					echo "<td>$serie</td>";
					echo "<td align='right'>".number_format($total_mao_de_obra,2,",",".")."</td>";
					echo "<td align='right'>".number_format($total_pecas,2,",",".")."</td>";
					echo "<td align='right'>".number_format($total,2,",",".")."</td>";
					//echo "<td>$aprovado</td>";
					echo "<td align='center'>
						<input type='hidden' name='orcamento_$i' value='$orcamento'>
						<input type='hidden' name='posto_$i' value='$posto'>
						<input type='checkbox' rel='orcamento_item' value='$os' name='os_$i' id='os_$i'>
						</td>";
					echo "</tr>";

					echo "<tr $classe style='display:none' align='right' id='linha_$i'>";
					echo "<td colspan='8' width='100%'>";

						echo "<table class='TabelaItem'>";

						echo "<thead>";
						echo "<tr>";
						echo "<td colspan='6'>Peças Utilizadas: Desmarque as peças para não serem  trocadas</td>";
						echo "</tr>";
						echo "<tr>";
						echo "<td width='20px'></td>";
						echo "<td width='100px'>Referência</td>";
						echo "<td width='300px'>Descrição</td>";
						echo "<td width='100px' align='right'>Preço</td>";
						echo "<td width='100px' align='center'>Qtde</td>";
						echo "<td width='100px' align='right'>Total</td>";
						echo "</tr>";
						echo "</thead>";

						echo "<tbody>";

						$query = "SELECT tbl_orcamento_item.orcamento_item, tbl_peca.referencia, tbl_peca.descricao, tbl_orcamento_item.preco, tbl_orcamento_item.qtde
							FROM tbl_os
							JOIN tbl_orcamento ON tbl_orcamento.os = tbl_os.os
							JOIN tbl_orcamento_item ON tbl_orcamento_item.orcamento = tbl_orcamento.orcamento
							JOIN tbl_peca ON tbl_peca.peca = tbl_orcamento_item.peca
							WHERE tbl_os.fabrica = $login_fabrica
							AND tbl_orcamento.os = $os
							AND tbl_os.os        = $os";
						$pecas = pg_exec($con, $query);
						$nr_pecas = pg_numrows($pecas);
						for ($j=0; $j< $nr_pecas; $j++){
							$orcamento_item	= trim(pg_result($pecas,$j,orcamento_item));
							$referencia		= trim(pg_result($pecas,$j,referencia));
							$descricao		= trim(pg_result($pecas,$j,descricao));
							$preco			= trim(pg_result($pecas,$j,preco));
							$qtde			= trim(pg_result($pecas,$j,qtde));
							if (strlen($preco)==0) $preco = 0;
							$total			= $preco * $qtde;
							echo "<tr>";
							echo "<td align='center'><input type='checkbox' value='t' name='item_chk_$i-$j' CHECKED><input type='hidden' name='item_peca_$i-$j' value='$orcamento_item' rel='ajuda' title='Desmarque para não aceitar o reparo da peça'></td>";
							echo "<td>$referencia</td>";
							echo "<td>$descricao</td>";
							echo "<td align='right'>".number_format($preco,2,",",".")."</td>";
							echo "<td align='center'>$qtde</td>";
							echo "<td align='right'>".number_format($total,2,",",".")."</td>";
							echo "</tr>";
						}
						echo "</tbody>";
						echo "</table>";

					echo "</td>";
					echo "</tr>";
				}
		?>
		</tbody>
		<tfoot>
			<tr>
				<td align='left' colspan='2'>Qtde de OS: <? echo $numero_os; ?></td>
				<td align='right' colspan='2'>Média por OS: <? echo number_format($total_geral/$numero_os,2,",","."); ?></td>
				<td colspan='2'></td>
				<td align='right'>Total:  <? echo number_format($total_geral,2,",","."); ?></td>
				<td align='center' style='background-color:#FFFFFF;'><a href='javascript:SelecionaTodos();'  style='font-size:10px' rel='ajuda' title='Inverter Seleção'>Selecionar<br>Todas</a></td>
			</tr>
			<tr style='background-color:#FFFFFF'>
				<td align='left'></td>
				<td align='right'></td>
				<td colspan='5' align='right'>Com as OS marcadas 
					<input type='hidden' name='qtde_itens' id='qtde_itens' value='<? echo $numero_os; ?>'>
					<input type='hidden' name='btn_acao' value=''>
					<input type='button' name='btn' value='Gravar' style='font-weight:bold;font-size:10px;padding:0px;' onClick="this.form.btn_acao.value='gravar';this.form.submit()"
					rel='ajuda' title="Selecione as OS's e clique aqui para Gravar"
					>
					<select name='aprovacao' style='width:150px;font-weight:bold;font-size:10px;padding:0px;' onChange='verificarAprova(this)'>
						<option value='aprovar' selected>Aprovar Orçamento </option>
						<option value='reprovar'>Reprovar Orçamento</option>
					</select>
					<span id='motivo_reprova' style='display:none;color:#596D9B'> Motivo: <input type='text' value='' name='motivo_reprova' ></span>
				</td>
				<td align='center'><img src='../imagens/seta_new.png' rel='ajuda' title='Selecione as OS, escolha Aprovar/Reprovar e clique em Gravar'></td>
			</tr>
		
		</tfoot>
		
		</table>
			</ul>
		</div>
	</div>
</div>
</form>
<? 
}
?>
	<div id="m_1_b" class="modboxin">

		<div id="ftl_1_0" class="uftl" style='text-align:justify'>
			<table align='center' width='90%'>
				<tr>
				<td><a href='lote_cadastro.php'><img src='imagem/img_cadastro.gif'></a></td>
				<td><a href='lote_cadastro.php' >Cadastro de Lotes</a></td>
				<? /* <td><img src='imagem/img_cadastro2.gif'></td>
				<td>Consulta de Ordens de Serviço</td>
				*/ ?>
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
 