<?php
/**
 * Página de listagem de chamado de HelpDesk para os postos autorizados
 *
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "helpdesk.inc.php";

$aChamados = hdBuscarChamados(array("tbl_posto.posto = {$login_posto}"));
?>
<?php 
	$title = 'Listagem de Chamados para Fábrica';
	include 'cabecalho.php'; 
?>
<style>
.titulo_coluna {
	background-color: #596D9B;
	color: white;
	font: normal normal bold 11px/normal Arial;
	text-align: center;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
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
</style>

<br />
<div align="center">
	<input type="button" value="Cadastrar Novo Chamado" onclick="window.location.href='helpdesk_cadastrar.php'"/>
	<br />
	<br />
	<table align="center" width="700" cellspacing="1" class="tabela">
		<thead>
			<tr class="titulo_coluna">
				<th> Chamado </th>
				<th> Abertura </th>
				<th> Fechamento </th>
				<th> Tempo Atendimento</th>
				<th> Tipo Solicitação </th>
				<th> Atendente </th>
				<th> Status </th>
				<th> &nbsp; </th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($aChamados as $i=>$linha): ?>

			<?php
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			?>
			<tr bgcolor="<?php echo $cor ?>">
				<td>&nbsp;<?php echo $linha['hd_chamado']; ?> </td>
				<td>&nbsp;<?php echo $linha['data']; ?> </td>
				<?
				if(strlen($linha['categoria']) > 0) {

					switch($linha['categoria']) {
						case ('atualiza_cadastro') :    $categoria = "Atualização de cadastro";break;
						case ('digitacao_fechamento') : $categoria = "Digitação e/ou fechamento de OS's"; break;
						case ('utilizacao_do_site') :   $categoria ="Dúvidas na utilização do site"; break;
						case ('duvida_troca') :         $categoria ="Dúvidas na troca de produto"; break; 
						case ('duvida_produto') :       $categoria ="Dúvida técnica sobre o produto"; break;
						case ('duvida_revenda') :       $categoria ="Dúvidas sobre atendimento à revenda"; break; 
						case ('falha_no_site') :        $categoria ="Falha no site"; break;
						case ('manifestacao_sac') :     $categoria ="Manifestação sobre o SAC"; break; 
						case ('pendencias_de_pecas') :  $categoria ="Pendências de peças com a fábrica"; break;
						case ('pend_pecas_dist') :      $categoria ="Pendências de peças com o distribuidor"; break;
						case ('outros') :               $categoria ="Outros"; break;
					}
				}
				if(strlen($linha['status']) > 0){
					switch($linha['status']) {
						case ('Ag. Posto') :   $status    = "Aguardando Posto"; break;
						case ('Ag. Fábrica') : $status ="Aguardando Fábrica"; break;
						default:             $status = $linha['status'];
					}
				}
				?>
				<td>&nbsp;<?php echo $linha['data_resolvido']; ?> </td>
				<td>&nbsp;<?php echo $linha['tempo_atendimento']; ?></td>
				<td>&nbsp;<?php echo $categoria; ?> </td>
				<td>&nbsp;<?php echo $linha['atendente_ultimo_login']; ?> </td>
				<td>&nbsp;<?php echo $status; ?> </td>
				<td>&nbsp;
					<input type="button" value="Consultar" title="Consultar Chamado" onclick="window.location.href='helpdesk_cadastrar.php?hd_chamado=<?php echo $linha['hd_chamado']; ?>'"/>
				</td>
			</tr>
			<?
				$sql = " SELECT 
							to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY HH24:MI:SS') as data,
							admin
						FROM tbl_hd_chamado_item
						WHERE hd_chamado = ".$linha['hd_chamado']."
						AND   interno IS NOT TRUE
						ORDER BY data DESC limit 1";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					if(strlen(pg_fetch_result($res,0,admin)) > 0) {
						echo "<tr align='center' class=\"subtitulo\">";
						echo "<td colspan='8' >Help Desk ".$linha['hd_chamado']." respondido em ".pg_fetch_result($res,0,data)."</td>";
						echo "</tr>";
					}
				}
			?>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php include 'rodape.php'; ?>