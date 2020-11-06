<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria";
include 'autentica_admin.php';
include "funcoes.php";

$auditoria_online = $_GET['auditoria_online'];


$sql =	"SELECT tbl_posto.posto                ,
				tbl_posto.nome                 ,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.contato,
				tbl_posto.fone,
				tbl_posto_fabrica.contato_endereco,
				tbl_posto_fabrica.contato_numero,
				tbl_posto_fabrica.contato_cidade,
				tbl_posto_fabrica.contato_estado,
				tbl_posto_fabrica.contato_email ,
				tbl_admin.nome_completo         ,
				tbl_auditoria_online.visita_posto,
				to_char(data_visita,'DD/MM/YYYY') as data_visita,
				to_char(data_pesquisa,'DD/MM/YYYY') as data_pesquisa,
				comentario_qtde_os_atendida      ,
				comentario_qtde_peca_trocada     ,
				comentario_qtde_os_revenda       ,
				comentario_qtde_peca_revenda     ,
				comentario_qtde_sem_peca         ,
				conclusao_auditoria              
		FROM tbl_auditoria_online
		JOIN tbl_admin USING(admin)
		JOIN tbl_posto USING(posto)
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
		WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
		AND   auditoria_online = $auditoria_online ;";
$res = pg_query($con,$sql);
if (pg_num_rows($res) == 1) {
	$posto        = trim(pg_fetch_result($res,0,posto));
	$posto_codigo = trim(pg_fetch_result($res,0,codigo_posto));
	$nome         = pg_fetch_result($res,0,nome);
	$contato      = pg_fetch_result($res,0,contato);
	$fone         = pg_fetch_result($res,0,fone);
	$endereco     = pg_fetch_result($res,0,contato_endereco);
	$numero       = trim(pg_fetch_result($res,0,contato_numero));
	$cidade       = pg_fetch_result($res,0,contato_cidade);
	$estado       = trim(pg_fetch_result($res,0,contato_estado));
	$email        = pg_fetch_result($res,0,contato_email);
	$nome_completo= trim(pg_fetch_result($res,0,nome_completo));
	$visita_posto = trim(pg_fetch_result($res,0,visita_posto));
	$data_visita  = trim(pg_fetch_result($res,0,data_visita));
	$data_pesquisa= trim(pg_fetch_result($res,0,data_pesquisa));
	$comentario_1 = trim(pg_fetch_result($res,0,comentario_qtde_os_atendida));
	$comentario_2 = trim(pg_fetch_result($res,0,comentario_qtde_peca_trocada));
	$comentario_3 = trim(pg_fetch_result($res,0,comentario_qtde_os_revenda));
	$comentario_4 = trim(pg_fetch_result($res,0,comentario_qtde_peca_revenda));
	$comentario_5 = trim(pg_fetch_result($res,0,comentario_qtde_sem_peca));
	$conclusao    = trim(pg_fetch_result($res,0,conclusao_auditoria));
}else{
	$msg_erro .= " Posto não encontrado. ";
}

$sql = " SELECT DISTINCT to_char(data_inicio,'DD/MM/YYYY') as data_inicio,
				to_char(data_final,'DD/MM/YYYY') as data_final
		FROM tbl_auditoria_online_item
		WHERE auditoria_online = $auditoria_online ";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0){
	$data_inicio = trim(pg_fetch_result($res,0,data_inicio));
	$data_final  = trim(pg_fetch_result($res,0,data_final));
}

$title = "RELATÓRIO DE AUDITORIA ONLINE";

$layout_menu = "auditoria";
include 'cabecalho.php';
?>

<style type="text/css">
.pesquisa {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 15px;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
.border {
	border: 1px solid #ced7e7;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.espaco{
	padding-left:100px;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.titulo_coluna {
    background-color: #596D9B;
    color: #FFFFFF;
    font: bold 11px "Arial";
    text-align: center;
}

</style>

<input type=hidden name="posto" value="<? echo $posto ?>">
<table class='formulario' width='700' border='0' align='center'>
	<tr>
		<td align="center">
			<table class="formulario" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
				<tr>
					<td colspan="1" rowspan="5">
						<img src='/assist/logos/suggar.jpg' alt='$login_fabrica_site' border='0' height='40'>
					</td>
					<td colspan="3" rowspan="5" align="center">
						<font size='4'><b>Relatório De Auditoria Online</b></font>
					</td>
				</tr>
				<tr>
					<td><strong>Elaboração:</strong></td>
				</tr>
				<tr>
					<td><? echo $nome_completo; ?></td>
				</tr>
				<tr>
					<td><strong>Data Pesquisa:</strong></td>
				</tr>
				<tr>
					<td><? echo $data_pesquisa; ?></td>
				</tr>
			</table>

			<input type='hidden' name='data_inicial' value='<?=$xdata_inicial?>'>
			<input type='hidden' name='data_final' value='<?=$xdata_final?>'>
			<input type='hidden' name='posto' value='<?=$posto?>'>

			<table class='formulario' width='700' border='0' align='center'>
				<tr>
					<td align='left'><strong>Posto Autorizado:   </strong></td>
					<td align='left'><? echo $nome ?></td>
					<td align='left'><strong>Contato:   </strong></td>
					<td align='left'><? echo $contato ?></td>
				</tr>
				<tr>
					<td align='left'><strong>Endereço:   </strong></td>
					<td align='left'><? echo "$endereco &nbsp; $numero"; ?></td>
					<td align='left'><strong>Telefone:   </strong></td>
					<td align='left'><? echo $fone ?></td>
				</tr>
				<tr>
					<td align='left'><strong>Cidade/Estado:&nbsp;</strong></td>
					<td align="left"><? echo "$cidade  &nbsp; $estado"; ?></td>
					<td align='left'><strong>E-mail:   </strong></td>
					<td align='left'><? echo $email ?></td>
				</tr>
			</table>
			<br/>
			
			<table class='formulario' width='700' border='0' align='center'>
				<tr>
					<td align='center'>Data inicio: <?=$data_inicio?> - Data Final: <?=$data_final?></td>
				</tr>
			</table>

			<table class='formulario' width='700' border='0' align='center'>
				<caption class='titulo_coluna'>Quatidade de OS atendida por Cliente</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>Produto</th>
						<th align='right'>Qtde</th>
						<th align='right'>MO</th>
						<th align='right'>Peças</th>
						<th align='right'>Total</th>
					</tr>
				</thead>
				<tbody>
				<?
					$total_mao_de_obra = 0 ;
					$total_pecas = 0 ;
					$total_qtde = 0 ;

					$sql = "SELECT DISTINCT os,sua_os,produto,valor_mo,valor_peca
							into temp auditoria_$login_admin
							FROM tbl_auditoria_online_item
							WHERE consumidor_revenda = 'C'
							AND   tbl_auditoria_online_item.auditoria_online = $auditoria_online
							AND   tbl_auditoria_online_item.os_recusada IS FALSE
							AND   tbl_auditoria_online_item.sem_peca IS FALSE;
					
							CREATE INDEX auditoria_os_$login_admin ON auditoria_$login_admin(os);

							CREATE INDEX auditoria_produto_$login_admin ON auditoria_$login_admin(produto);

							SELECT  count(os) as produto_qtde,
									SUM(valor_mo) as mao_de_obra,
									SUM(valor_peca) as pecas,
									SUM(valor_peca+ valor_mo) as total,
									tbl_produto.referencia,
									tbl_produto.descricao 
							FROM auditoria_$login_admin
							JOIN tbl_produto USING(produto)
							GROUP BY 
									tbl_produto.referencia,
									tbl_produto.descricao 
							ORDER BY count(os) DESC,tbl_produto.referencia ";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0){
						$resultados = pg_fetch_all($res);
						foreach($resultados as $resultado){
							$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

							echo "<tr class='table_line'  style='background-color: $cor;'>";
							echo "<td>".$resultado['referencia']."-".$resultado['descricao']."</td>";
							echo "<td align='right'>".$resultado['produto_qtde']."</td>";
							echo "<td align='right'>".number_format($resultado['mao_de_obra'],2,",",".")."</td>";
							echo "<td align='right'>".number_format($resultado['pecas'],2,",",".")."</td>";
							echo "<td align='right'>".number_format($resultado['total'],2,",",".")."</td>";
							echo "</tr>";

							$total_mao_de_obra += $resultado['mao_de_obra'];
							$total_pecas       += $resultado['pecas'] ;
							$total_qtde        += $resultado['produto_qtde'] ;
						}
						$total = $total_mao_de_obra + $total_pecas ;

				?>
				</tbody>
				<?
				echo "<tfoot>";
				echo "<tr class='titulo_coluna'>";
				
				echo "<td align='center'>Total";
				echo "</td>";

				echo "<td align='right'>";
				echo $total_qtde;
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_mao_de_obra,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_pecas,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total,2,",",".");
				echo "</td>";
				echo "</tr>";
				echo "</tfoot>";
				}
				?>
			</table>
			
			<br/>
			<div class="subtitulo">Comentário</div>
			<div class="texto_avulso"><?echo $comentario_1;?></div>
			<br/>
			<br/>

			<table class='formulario' width='700' border='0' align='center'>
				<caption class='titulo_coluna'>Quatidade de peças trocadas em garantia</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>Peça</th>
						<th>Qtde</th>
						<th>MO</th>
						<th>Peças</th>
						<th>Total</th>
					</tr>
				</thead>
				<tbody>
				<?
				$total_mao_de_obra = 0 ;
				$total_pecas = 0 ;
				$total_qtde = 0 ;

				$sql = " SELECT count(tbl_auditoria_online_item.peca) as peca_qtde,
								SUM(tbl_auditoria_online_item.valor_mo) as mao_de_obra,
								SUM(valor_peca) as pecas,
								SUM(valor_mo + valor_peca) as total,
								tbl_peca.referencia,
								tbl_peca.descricao 
						FROM tbl_auditoria_online_item
						JOIN tbl_peca ON tbl_auditoria_online_item.peca = tbl_peca.peca
						WHERE tbl_peca.fabrica = $login_fabrica
						AND   tbl_auditoria_online_item.servico_realizado = 504
						AND   tbl_auditoria_online_item.consumidor_revenda = 'C'
						AND   tbl_auditoria_online_item.auditoria_online = $auditoria_online
						AND   tbl_auditoria_online_item.os_recusada IS FALSE
						AND   tbl_auditoria_online_item.sem_peca IS FALSE
						GROUP BY tbl_peca.referencia,
								tbl_peca.descricao 
						ORDER BY count(tbl_auditoria_online_item.peca) DESC ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$resultados = pg_fetch_all($res);
					foreach($resultados as $resultado){
						$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

						echo "<tr class='table_line'  style='background-color: $cor;'>";
						echo "<td>".$resultado['referencia']."-".$resultado['descricao']."</td>";
						echo "<td align='right'>".$resultado['peca_qtde']."</td>";
						echo "<td align='right'>".number_format($resultado['mao_de_obra'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['pecas'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['total'],2,",",".")."</td>";
						echo "</tr>";

						$total_mao_de_obra += $resultado['mao_de_obra'];
						$total_pecas       += $resultado['pecas'] ;
						$total_qtde        += $resultado['peca_qtde'] ;
					}
					$total = $total_mao_de_obra + $total_pecas ;

				?>
				</tbody>
				<?
				echo "<tfoot>";
				echo "<tr class='titulo_coluna'>";
				
				echo "<td align='center'>Total";
				echo "</td>";

				echo "<td align='right'>";
				echo $total_qtde;
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_mao_de_obra,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_pecas,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total,2,",",".");
				echo "</td>";
				echo "</tr>";
				echo "</tfoot>";
				}
				?>
			</table>
			
			<br/>
			<div class="subtitulo">Comentário</div>
			<div class="texto_avulso"><?echo $comentario_2;?></div>
			<br/>
			<br/>
			
			<table class='formulario' width='700' border='0' align='center'>
				<caption class='titulo_coluna'>Quatidade de OS atendida Revenda</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>Produto</th>
						<th>Qtde</th>
						<th>MO</th>
						<th>Peças</th>
						<th>Total</th>
					</tr>
				</thead>
				<tbody>
				<?
				$total_mao_de_obra = 0 ;
				$total_pecas = 0 ;
				$total_qtde = 0 ;
				$total = 0;

				$sql = "SELECT DISTINCT os,sua_os,produto,valor_mo,valor_peca
						into temp auditoria2_$login_admin
						FROM tbl_auditoria_online_item
						WHERE consumidor_revenda = 'R'
						AND   tbl_auditoria_online_item.auditoria_online = $auditoria_online
						AND   tbl_auditoria_online_item.os_recusada IS FALSE
						AND   tbl_auditoria_online_item.sem_peca IS FALSE;
				
						CREATE INDEX auditoria2_os_$login_admin ON auditoria2_$login_admin(os);

						CREATE INDEX auditoria2_produto_$login_admin ON auditoria2_$login_admin(produto);

						SELECT count(os) as produto_qtde,
								SUM(valor_mo) as mao_de_obra,
								SUM(valor_peca) as pecas,
								SUM(valor_peca+ valor_mo) as total,
								tbl_produto.referencia,
								tbl_produto.descricao 
						FROM auditoria2_$login_admin
						JOIN tbl_produto USING(produto)
						GROUP BY tbl_produto.referencia,
								tbl_produto.descricao 
						ORDER BY count(os) DESC,tbl_produto.referencia ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$resultados = pg_fetch_all($res);
					foreach($resultados as $resultado){
						$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

						echo "<tr class='table_line'  style='background-color: $cor;'>";
						echo "<td>".$resultado['referencia']."-".$resultado['descricao']."</td>";
						echo "<td align='right'>".$resultado['produto_qtde']."</td>";
						echo "<td align='right'>".number_format($resultado['mao_de_obra'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['pecas'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['total'],2,",",".")."</td>";
						echo "</tr>";

						$total_mao_de_obra += $resultado['mao_de_obra'];
						$total_pecas       += $resultado['pecas'] ;
						$total_qtde        += $resultado['produto_qtde'] ;
					}
					$total = $total_mao_de_obra + $total_pecas ;
				?>
				</tbody>
				<?
				echo "<tfoot>";
				echo "<tr class='titulo_coluna'>";
				
				echo "<td align='center'>Total";
				echo "</td>";

				echo "<td align='right'>";
				echo $total_qtde;
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_mao_de_obra,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_pecas,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total,2,",",".");
				echo "</td>";
				echo "</tr>";
				echo "</tfoot>";
				}
				?>
			</table>
			
			<br/>
			<div class="subtitulo">Comentário</div>
			<div class="texto_avulso"><?echo $comentario_3;?></div>
			<br/>
			<br/>
			
			<table class='formulario' width='700' border='0' align='center'>
				<caption class='titulo_coluna'>Quatidade de peças trocadas Revenda</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>Peça</th>
						<th>Qtde</th>
						<th>MO</th>
						<th>Peças</th>
						<th>Total</th>
					</tr>
				</thead>
				<tbody>
				<?
				$total_mao_de_obra = 0 ;
				$total_pecas = 0 ;
				$total_qtde = 0 ;

				$sql = " SELECT count(tbl_auditoria_online_item.peca) as peca_qtde,
								SUM(tbl_auditoria_online_item.valor_mo) as mao_de_obra,
								SUM(valor_peca) as pecas,
								SUM(valor_mo + valor_peca) as total,
								tbl_peca.referencia,
								tbl_peca.descricao 
						FROM tbl_auditoria_online_item
						JOIN    tbl_peca       ON tbl_auditoria_online_item.peca          = tbl_peca.peca
						JOIN    tbl_servico_realizado USING(servico_realizado)
						WHERE tbl_peca.fabrica = $login_fabrica
						AND   tbl_servico_realizado.gera_pedido
						AND   tbl_auditoria_online_item.consumidor_revenda = 'R'
						AND   tbl_auditoria_online_item.auditoria_online = $auditoria_online
						AND   tbl_auditoria_online_item.os_recusada IS FALSE
						AND   tbl_auditoria_online_item.sem_peca IS FALSE
						GROUP BY tbl_peca.referencia,
								tbl_peca.descricao 
						ORDER BY count(tbl_auditoria_online_item.peca) DESC ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$resultados = pg_fetch_all($res);
					foreach($resultados as $resultado){
						$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

						echo "<tr class='table_line'  style='background-color: $cor;'>";
						echo "<td>".$resultado['referencia']."-".$resultado['descricao']."</td>";
						echo "<td align='right'>".$resultado['peca_qtde']."</td>";
						echo "<td align='right'>".number_format($resultado['mao_de_obra'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['pecas'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['total'],2,",",".")."</td>";
						echo "</tr>";

						$total_mao_de_obra += $resultado['mao_de_obra'];
						$total_pecas       += $resultado['pecas'] ;
						$total_qtde        += $resultado['peca_qtde'] ;
					}
					$total = $total_mao_de_obra + $total_pecas ;

				?>
				</tbody>
				<?
				echo "<tfoot>";
				echo "<tr class='titulo_coluna'>";
				
				echo "<td align='center'>Total";
				echo "</td>";

				echo "<td align='right'>";
				echo $total_qtde;
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_mao_de_obra,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_pecas,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total,2,",",".");
				echo "</td>";
				echo "</tr>";
				echo "</tfoot>";
				}
				?>
			</table>
			
			<br/>
			<div class="subtitulo">Comentário</div>
			<div class="texto_avulso"><?echo $comentario_4;?></div>
			<br/>
			<br/>
			
			<table class='formulario' width='700' border='0' align='center'>
				<caption class='titulo_coluna'>Relatório de OS sem peças trocadas</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>OS</th>
						<th>Produto</th>
						<th>Abertura</th>
						<th>Fechamento</th>
						<th>Defeito Reclamado</th>
						<th>Defeito Constatado</th>
						<th>Solução</th>
					</tr>
				</thead>
				<tbody>
				<?

				$sql = " SELECT DISTINCT tbl_auditoria_online_item.os,
								tbl_auditoria_online_item.sua_os,
								tbl_produto.descricao,
								to_char(tbl_auditoria_online_item.data_abertura,'DD/MM/YYYY') as data_abertura,
								to_char(tbl_auditoria_online_item.data_fechamento,'DD/MM/YYYY') as data_fechamento,
								tbl_defeito_reclamado.descricao as defeito_reclamado,
								tbl_defeito_constatado.descricao as defeito_constatado,
								tbl_solucao.descricao as solucao
						FROM tbl_auditoria_online_item
						JOIN tbl_produto USING(produto)
						JOIN tbl_defeito_reclamado  USING(defeito_reclamado)
						JOIN tbl_defeito_constatado  USING(defeito_constatado)
						JOIN tbl_solucao ON tbl_solucao.solucao = tbl_auditoria_online_item.solucao
						WHERE tbl_auditoria_online_item.auditoria_online = $auditoria_online
						AND   tbl_auditoria_online_item.os_recusada IS FALSE
						AND   tbl_auditoria_online_item.sem_peca IS FALSE
						AND  os in ( SELECT DISTINCT os
											from tbl_auditoria_online_item
											join tbl_servico_realizado on  tbl_servico_realizado.servico_realizado = tbl_auditoria_online_item.servico_realizado and tbl_servico_realizado.troca_de_peca = 'f'
										 )
						AND os not in ( SELECT DISTINCT os
													from tbl_auditoria_online_item
													join tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_auditoria_online_item.servico_realizado and tbl_servico_realizado.troca_de_peca = 't'
												) 
						ORDER BY tbl_auditoria_online_item.os ";

				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					for($i =0;$i<pg_num_rows($res);$i++) {
						$cor = ($i%2) ? "#F7F5F0" : '#F1F4FA';
						echo "<tr class='table_line'  style='background-color: $cor;'>";
						echo "<td>".pg_fetch_result($res,$i,sua_os)."</td>";
						echo "<td align='left'>".pg_fetch_result($res,$i,descricao)."</td>";
						echo "<td align='center'>".pg_fetch_result($res,$i,data_abertura)."</td>";
						echo "<td align='center'>".pg_fetch_result($res,$i,data_fechamento)."</td>";
						echo "<td align='center'>".pg_fetch_result($res,$i,defeito_reclamado)."</td>";
						echo "<td align='center'>".pg_fetch_result($res,$i,defeito_constatado)."</td>";
						echo "<td align='center'>".pg_fetch_result($res,$i,solucao)."</td>";
						echo "</tr>";
					}

			?>
			</tbody>
			<? } ?>
			</table>

			<br/>
			<div class="subtitulo">Comentário</div>
			<div class="texto_avulso"><?echo $comentario_5;?></div>
			<br/>
			<br/>
			
			<table class='formulario' width='700' border='0' align='center'>
			<caption class='titulo_coluna'>Relatório de Extratos que não efetuou devolução de peças</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>Extrato</th>
						<th>Data Geração</th>
						<th>Mão-de-Obra</th>
						<th>Peças</th>
						<th>Total</th>
					</tr>
				</thead>
				<tbody>
				<?
				$sql = " SELECT DISTINCT extrato,
								to_char(data_geracao,'DD/MM/YYYY') as data_geracao,
								valor_mo,
								valor_peca,
								(valor_mo+valor_peca) as total
						FROM tbl_auditoria_online_item
						WHERE auditoria_online = $auditoria_online
						AND   extrato IS NOT NULL
						ORDER BY extrato ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$resultados = pg_fetch_all($res);
					foreach($resultados as $resultado){
						$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

						echo "<tr class='table_line'  style='background-color: $cor;'>";
						echo "<td align='center'>".$resultado['extrato']."</td>";
						echo "<td align='center'>".$resultado['data_geracao']."</td>";
						echo "<td align='center'>".number_format($resultado['mao_de_obra'],2,",",".")."</td>";
						echo "<td align='center'>".number_format($resultado['pecas'],2,",",".")."</td>";
						echo "<td align='center'>".number_format($resultado['total'],2,",",".")."</td>";
						echo "</tr>";
					}

			?>
			</tbody>
			<? } ?>
			</table>
			
			<br>
			<table class='formulario' width='700' border='0' align='center'>
				<caption class='titulo_coluna'>Relatório de OS recusada</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>OS</th>
						<th>Produto</th>
						<th>Abertura</th>
						<th>Fechamento</th>
						<th>Extrato</th>
					</tr>
				</thead>
				<tbody>
				<?

				$sql = " SELECT tbl_auditoria_online_item.os,
								tbl_auditoria_online_item.sua_os,
								tbl_produto.descricao,
								to_char(tbl_auditoria_online_item.data_abertura,'DD/MM/YYYY') as data_abertura,
								to_char(tbl_auditoria_online_item.data_fechamento,'DD/MM/YYYY') as data_fechamento,
								tbl_auditoria_online_item.extrato
						FROM tbl_auditoria_online_item
						JOIN tbl_produto USING(produto)
						WHERE tbl_auditoria_online_item.auditoria_online = $auditoria_online
						AND   os_recusada IS TRUE
						ORDER BY tbl_auditoria_online_item.os ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$resultados = pg_fetch_all($res);
					foreach($resultados as $resultado){
						$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

						echo "<tr class='table_line'  style='background-color: $cor;'>";
						echo "<td>".$resultado['sua_os']."</td>";
						echo "<td align='left'>".$resultado['descricao']."</td>";
						echo "<td align='center'>".$resultado['data_abertura']."</td>";
						echo "<td align='center'>".$resultado['data_fechamento']."</td>";
						echo "<td align='center'>".$resultado['extrato']."</td>";
						echo "</tr>";
					}

				?>
				</tbody>
			<? } ?>
			</table>
			<br>
			
			<table class='formulario' width='700' border='0' align='center'>
				<caption class='titulo_coluna'>Relatório de OS aberta mais de 30 dias sem lançamento de peças</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>OS</th>
						<th>Produto</th>
						<th>Abertura</th>
					</tr>
				</thead>
				<tbody>
				<?

				$sql = " SELECT tbl_auditoria_online_item.os,
								tbl_auditoria_online_item.sua_os,
								tbl_produto.descricao,
								to_char(tbl_auditoria_online_item.data_abertura,'DD/MM/YYYY') as data_abertura
						FROM tbl_auditoria_online_item
						JOIN tbl_produto USING(produto)
						WHERE tbl_auditoria_online_item.auditoria_online = $auditoria_online
						AND   sem_peca
						ORDER BY tbl_auditoria_online_item.os ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$resultados = pg_fetch_all($res);
					foreach($resultados as $resultado){
						$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

						echo "<tr style='background-color: $cor;'>";
						echo "<td>".$resultado['sua_os']."</td>";
						echo "<td align='left'>".$resultado['descricao']."</td>";
						echo "<td align='center'>".$resultado['data_abertura']."</td>";
						echo "</tr>";
					}

				?>
				</tbody>
			<? } ?>
			</table>
			
			<br/>
			<div class="subtitulo">Conclusão</div>
			<div class="texto_avulso"><?echo $conclusao;?></div>
			<br/>
			
			<table class='formulario' width='700' border='0' align='center'>
				<tr class="titulo_coluna">
					<td nowrap >
						Necessário Visita ao Posto?  
						<?php echo ($visita_posto=='t') ? "Sim": "Não"?> </td>
					<td nowrap>
						Data Visita: <?=$data_visita?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<? include "rodape.php" ?>