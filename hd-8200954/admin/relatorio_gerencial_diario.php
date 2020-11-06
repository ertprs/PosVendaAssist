<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

$btn_acao = $_POST['btn_acao'];
if($btn_acao){
	$codigo_posto = $_POST['codigo_posto_off'];
	if($codigo_posto){
		$sql = "SELECT tbl_posto_fabrica.posto
					 FROM tbl_posto_fabrica
					 WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					 AND   tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
		$res = pg_query($con,$sql);
		if(pg_numrows($res) == 0){
			$msg_erro = traduz("Posto não Encontrado");
		}
	}

}

$layout_menu = "gerencia";
$title = traduz("RELATÓRIO GERENCIAL");
include "cabecalho.php";

?>

<?php include "../js/js_css.php";?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>

<script language='javascript'>

	$(function(){
		$('.mask_date').datepick({startDate:'01/01/2000'}).mask("99/99/9999");
		$('input[id*=data]').datepick({startDate:'01/01/2000'}).mask("99/99/9999");

		function formatItem(row) {
			return row[2] + " - " + row[1];
		}

	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#nome_posto").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#nome_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#nome_posto").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[2]) ;
		//alert(data[2]);
	});

	$('#enviar_email').change(function() {
		if (this.checked == true) $('#email').slideDown('normal');
		else  $('#email').slideUp('normal');
	});
});

</script>

<style>
#meuselect {
font:12px arial, helvetica, sans-serif;
}
#meuselect option.disable {
background-color: #D2D2D2;
color:#003366;
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

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
	text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>

<? include "javascript_pesquisas.php"; ?>

<FORM NAME="frm_pesquisa" METHOD="POST" ACTION="<?echo $PHP_SELF?>">

<?
$relatorios = array(
	'aberta_5'				=> traduz('OS abertas há mais de 5 dias sem análise'),
	'aberta_55'				=> traduz('OS abertas há mais de 5 dias, com lançamento de peças e sem pedido'),
	'pedido_10'				=> traduz('OS com pedido de peças há mais de 10 dias sem faturamento'),
	'peca_enviada_10'		=> traduz('Peças enviadas há mais de 10 dias sem o fechamento da OS'),
	'conserto_5'			=> traduz('Consertos realizados há 5 dias sem a retirada do produto'),
	'pedido_sem_estoque'	=> traduz('Pedido de peças faturadas há mais de 20 dias, sem peça no estoque e sem faturamento'),
	'troca_sem_faturamento'	=> traduz('OS de troca sem o envio do produto novo'),
	'troca_sem_lgr'			=> traduz('OS de troca onde o produto novo foi enviado e o velho ainda não voltou em 30 dias'),
);
if (in_array($login_fabrica, array(35, 152, 180, 181, 182))){
	unset($relatorios['troca_sem_lgr']);
} 
if (empty($telecontrol_distrib)) unset($relatorios['pedido_sem_estoque']);
else unset($relatorios['troca_sem_lgr']);

if($login_fabrica == 85) { # HD 342601
	$relatorios['aberta_30'] = traduz('OS aberta a mais de 30 dias sem finalizar');
	unset($relatorios['peca_enviada_10'],$relatorios['conserto_5'],$relatorios['troca_sem_faturamento'],$relatorios['troca_sem_lgr']);
}

// HD 2863857
if ($login_fabrica == 153) {
    unset($relatorios['aberta_5']);
    $relatorios = array_merge(
        array('aberta_2' => traduz('OS abertas há mais de 2 dias sem análise')),
        $relatorios
    );
}

?>
	<br>
	<? if(strlen($msg_erro) > 0){?>
		<center>
			<div class='msg_erro' style='width:700px;'><?= $msg_erro; ?></div>
		</center>
	<? } ?>
	<table width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='formulario'>

	<caption class='titulo_tabela'><?php echo traduz("Parâmetros de Pesquisa"); ?></caption>
	<tr><td colspan='4'>&nbsp;</td></tr>
	<tbody>
		<tr>
			<td width='130'>&nbsp;</td>
			<td colspan='3'>
				Escolha uma opção <br>
				<select name='tipo' id='meuselect' class='frm'><?// size='<?=(count($relatorios)>6) ? 6 : count($relatorios)?>
			<?	foreach($relatorios as $rel_id=>$rel_desc) {
					$sel = ($_POST['tipo'] == $rel_id) ? 'SELECTED':'';	?>
					<option value='<?=$rel_id?>'<?=$sel?>>- <?=$rel_desc?></option>
			<?	}?>
				</select>
			</td>
		</tr>

		<tr>
			<td>&nbsp;</td>
			<td width='100' nowrap>
				<?=traduz('Posto')?> <br />
				<input type="text" name="codigo_posto_off" id="codigo_posto_off" size="8" value="<? echo $codigo_posto_off ?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor:pointer;" valign="absmiddle"
						alt="Clique aqui para pesquisar postos pelo código"
					onclick="fnc_pesquisa_posto(document.frm_pesquisa.codigo_posto_off, document.frm_pesquisa.posto_nome_off, 'codigo')">
			</td>
			<td>
				<?=traduz('Nome do Posto')?> <br />
				<input type="text" name="posto_nome_off" id="posto_nome_off" size="50" value="<?echo $posto_nome_off ?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor:pointer;" valign="absmiddle"
						alt="Clique aqui para pesquisar postos pelo código"
					onclick="fnc_pesquisa_posto(document.frm_pesquisa.codigo_posto_off, document.frm_pesquisa.posto_nome_off, 'nome')">
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><input type='checkbox' value='sim' name='excel'> <?=traduz('Gerar Excel')?></td>
			<?php if ($login_fabrica == 24) : // HD 687978 ?>
				<td>
					<label for="estado">Estado</label><br />
					<select name="estado" id="estado" class="frm">
						<?php
							$estados = array(
								''=>'Todos os Estados',
								'AC'=>'AC - Acre','AL'=>'AL - Alagoas','AM'=>'AM - Amazonas',
								'AP'=>'AP - Amapá', 'BA'=>'BA - Bahia', 'CE'=>'CE - Ceará','DF'=>'DF - Distrito Federal',
								'ES'=>'ES - Espírito Santo', 'GO'=>'GO - Goiás','MA'=>'MA - Maranhão','MG'=>'MG - Minas Gerais',
								'MS'=>'MS - Mato Grosso do Sul','MT'=>'MT - Mato Grosso', 'PA'=>'PA - Pará','PB'=>'PB - Paraíba',
								'PE'=>'PE - Pernambuco','PI'=>'PI - Piauí','PR'=>'PR - Paraná','RJ'=>'RJ - Rio de Janeiro',
								'RN'=>'RN - Rio Grande do Norte','RO'=>'RO - Rondônia','RR'=>'RR - Roraima',
								'RS'=>'RS - Rio Grande do Sul', 'SC'=>'SC - Santa Catarina','SE'=>'SE - Sergipe',
								'SP'=>'SP - São Paulo','TO'=>'TO - Tocantins'
							);
							foreach ( $estados as $k => $v ) {
								if( !empty ($_POST['estado']) && $k == $_POST['estado'] )
									$selected = 'selected="selected"';
								else
									$selected = '';
								echo '<option value="'.$k.'" '.$selected.'>'.$v.'</option>';
							}
						?>
					</select>
				</td>
			<?php endif; ?>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td colspan='3'>
				<input type='checkbox' value='sim' name='enviar_email' id='enviar_email'>
				<?=traduz('Enviar por e-mail')?>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td colspan='3'>
				<div id='email' style='display:none'>
					<table width='600px' align='center' border='0' cellspacing = '2' cellpadding='3' class='formulario'>
						<?
							$sql = "SELECT email FROM tbl_admin where admin = $login_admin";
							$res = @pg_query($con,$sql);
							if (is_resource($res) && pg_num_rows($res)) {
								$email_remetente = pg_fetch_result($res, 0, 'email');
							}
						?>
						<tr>
							<td colspan="2"><?php echo traduz("E-mail Remetente"); ?></td>
						</tr>
						<tr>
							<td colspan="2">
								<input type="text" name="email_remetente" size="50" value="<? echo $email_remetente ?>" class="frm">
							</td>
						</tr>
						<tr>
							<td colspan="2"><?php echo traduz("E-mail Destinatário"); ?></td>
						</tr>
						<tr>
							<td colspan="2">
								<input type="text" name="email_destinatario" size="50" value="<? echo $email?>" class="frm">
							</td>
						</tr>
						<tr>
							<td colspan="2"><?php echo traduz("Assunto"); ?></td>
						</tr>
						<tr>
							<td colspan="2">
								<input type="text" name="assunto" size="70" value="<? echo $assunto ?>" class="frm">
							</td>
						</tr>
						<tr>
							<td colspan="2"><?php echo traduz("Mensagem"); ?></td>
						</tr>
						<tr>
							<td colspan='2'>
								<textarea name="mens_corpo" rows="8" cols="68" value = "<? echo $mens_corpo ?>" class="frm"></textarea>
							</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
		<tr>
			<td colspan='4' align='center' style='padding:10px 0 10px 0;'><input type='submit' value='Gerar' name='btn_acao'></td>
		</tr>
	</table>
</form>
<?
	if ($_POST["excel"] == "sim") {
		ob_start();
	}
	if (strlen($_POST['btn_acao'])>0 && strlen($msg_erro) == 0) {
		$tipo         = $_POST['tipo'];
		$codigo_posto = $_POST['codigo_posto_off'];

		if(strlen($codigo_posto)>0){
			$sqlP = "SELECT tbl_posto_fabrica.posto
					 FROM tbl_posto_fabrica
					 WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					 AND   tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
			$resP = pg_query($con,$sqlP);

			if(pg_num_rows($resP)>0){
				$posto = pg_fetch_result($resP, 0, posto);
				$cond_posto = " AND tbl_os.posto = $posto ";
			}
		}

		if ($_POST['enviar_email']=='sim') {

			if (strlen($_POST["email_remetente"]) > 0) {
				$aux_email_remetente = trim($_POST["email_remetente"]) ;
			}else{
				$msg_erro = traduz("Informe o email do remetente.");
			}
			if (strlen($_POST["email_destinatario"]) > 0) {
				$aux_email_destinatario = trim($_POST["email_destinatario"]) ;
			}else{
				$msg_erro = traduz("Informe o email do destinatário.");
			}

			if (strlen($_POST["assunto"]) > 0) {
				$aux_assunto =  trim($_POST["assunto"]) ;
			}else{
				$msg_erro = traduz("Informe o assunto.");
			}


		}

		if ( isset ($_POST['estado']) && !empty($_POST['estado']) ) { // HD 687978

			$cond_estado = "AND tbl_posto.estado = '" . $_POST['estado'] . "'";

		}

		if (strlen($msg_erro)==0) {
            if ($tipo == 'aberta_5' or $tipo == 'aberta_2') {
                $num_dias = substr($tipo, -1);

		if ($login_fabrica == 106){
			$cond_join = "";
			$campo_solucao = "";
			$cond_solucao  = "";
		} else{
			$cond_join = "LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica";
			$campo_solucao = ", tbl_solucao.descricao as solucao";
			$cond_solucao = "AND solucao_os IS NULL";
		}

				$sql = "SELECT os,
							tbl_os.sua_os,
							TO_CHAR(data_digitacao,'dd/mm/yyyy HH24:MI') AS data_digitacao,
							TO_CHAR(data_abertura,'dd/mm/yyyy') AS data_abertura,
							tbl_produto.referencia,
							tbl_produto.descricao,
							NULL as defeito_constatado,
							tbl_servico_realizado.descricao as servico_realizado,
							codigo_posto,
							consumidor_nome,
							consumidor_fone,
							TO_CHAR((current_date - data_digitacao),'DD') as dias,
							tbl_posto.nome
							$campo_solucao
						FROM tbl_os
						JOIN tbl_produto                USING(produto)
						JOIN tbl_posto                  USING(posto)
						JOIN tbl_posto_fabrica          ON tbl_posto_fabrica.posto = tbl_Posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						LEFT JOIN tbl_os_produto        USING(os)
						LEFT JOIN tbl_os_item           USING(os_produto)
						LEFT JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
						$cond_join
						WHERE tbl_os.fabrica            = $login_fabrica
						AND finalizada                  IS NULL
						AND data_fechamento             IS NULL
						AND data_digitacao              <= CURRENT_DATE - INTERVAL '{$num_dias} days + 1 sec'
						AND tbl_os.defeito_constatado   IS NULL
						AND tbl_os_item.pedido          IS NULL
						AND tbl_os_item.peca            IS NULL
						AND tbl_os.excluida             IS NOT TRUE
						$cond_posto
						$cond_estado
						$cond_solucao";

				$res = pg_query($con,$sql);
				#echo(nl2br($sql));
				#die;
				if (pg_num_rows($res)>0) {
					$titulo = array('OS', 'DATA DIGITAÇÃO', 'DATA ABERTURA', 'PRODUTO', 'DEFEITO CONSTATADO',
									'SOLUÇÃO', 'SERVIÇO REALIZADO', 'POSTO', 'DIAS');
					echo"<br><br>";
					if (strlen($msg_erro2)>0) {
						echo($msg_erro2); echo "<br>";
					}
?>					<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>
					<caption class='titulo_tabela'><?=$relatorios[$tipo]?></caption>
					<thead>
					<? if($_POST["excel"]){ ?>
						<tr><th colspan='9'>&nbsp;</th></tr>
						<? } ?>
					<tr class='titulo_coluna'>
						<th nowrap><?php echo traduz("OS"); ?></th>
						<th><?php echo traduz("Data Digitação"); ?></th>
						<th><?php echo traduz("Data Abertura"); ?></th>
						<th><?php echo traduz("Produto"); ?></th>
						<th><?php echo traduz("Posto"); ?></th>
<?
/*	HD 304552					<th>Defeito Constatado</th>
						<th>Solucao</th>
						<th>Serviço Realizado</th>

*/					#echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>CONSUMIDOR NOME</strong></font></th>"; HD 239781
					#echo "<td bgcolor='#485989' width='110'><font color='#FFFFFF'><strong>CONSUMIDOR TELEFONE</strong></font></th>";
?>						<th width='110'><strong><?php echo traduz("Dias"); ?></th>
					</tr>
					</thead>
					<tbody>
<?
				 for ($i=0; $i < pg_num_rows($res); $i++) {

					$os					= pg_fetch_result($res,$i,os);
					$sua_os				= pg_fetch_result($res,$i,sua_os);
					$data_digitacao		= pg_fetch_result($res,$i,data_digitacao);
					$data_abertura		= pg_fetch_result($res,$i,data_abertura);
					$produto			= pg_fetch_result($res,$i,referencia).' - '.pg_fetch_result($res,$i,descricao);
/*					$defeito_constatado	= pg_fetch_result($res,$i,defeito_constatado);
					$solucao			= pg_fetch_result($res,$i,solucao);
					$servico_realizado	= pg_fetch_result($res,$i,servico_realizado);
*/					$codigo_posto		= pg_fetch_result($res,$i,codigo_posto);
					$posto_nome			= pg_fetch_result($res,$i,'nome');
					$posto_nome			= substr($posto_nome,0,30);
					#$consumidor_nome = pg_fetch_result($res,$i,consumidor_nome);
					#$consumidor_telefone = pg_fetch_result($res,$i,consumidor_fone);
					$dias = pg_fetch_result($res,$i,dias);

					#HD 239781
					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr bgcolor='$cor'>";
					echo "<td nowrap><a href='os_press.php?os=$os' title='Clique aqui para consultar a OS' target='_blank'>$sua_os</a></td>";
					echo "<td>$data_digitacao</td>";
					echo "<td>$data_abertura</td>";
					echo "<td>$produto</td>";
					echo "<td>$codigo_posto - $posto_nome</td>";
/*					echo"<td>$defeito_constatado</td>";
					echo"<td>$solucao</td>";
					echo"<td>$servico_realizado</td>";
					#echo"<td nowrap>$consumidor_nome</td>";
					#echo"<td nowrap>$consumidor_telefone</td>";
*/					echo"<td nowrap>$dias</td>";
					echo"</tr>";
				 }
					echo"</tbody></table>";
				}
				else echo traduz('Não foram encontrados resultados para esta pesquisa.');
			}

			if ($tipo == 'aberta_55') {

				if (in_array($login_fabrica, array(106, 152, 180, 181, 182))){
					$cond_join = "";
					$campo_solucao = "";
					$cond_solucao  = "";
					if (in_array($login_fabrica, array(152, 180, 181, 182))) {
						$defeito_constatado_array = "((SELECT array_to_string(array_agg(dc2.descricao),', ') FROM tbl_os_defeito_reclamado_constatado AS odc INNER JOIN tbl_defeito_constatado AS dc2 ON dc2.defeito_constatado = odc.defeito_constatado WHERE odc.os = tbl_os.os AND dc2.fabrica = {$login_fabrica})) AS defeito_constatado_array, ";
					}else{
						$defeito_constatado_array = "";
					}
				} else{
					$cond_join = "LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica AND tbl_solucao.troca_peca IS TRUE";
					$campo_solucao = ", tbl_solucao.descricao as solucao";
				}

				$sql = "SELECT os,
							tbl_os.sua_os,
							TO_CHAR(data_digitacao,'dd/mm/yyyy HH24:MI') AS data_digitacao,
							TO_CHAR(data_abertura,'dd/mm/yyyy') AS data_abertura,
							tbl_produto.referencia,
							tbl_produto.descricao,
							tbl_peca.referencia as peca_referencia,
							tbl_peca.descricao as peca_descricao,
							tbl_defeito_constatado.descricao as defeito_constatado,
							$defeito_constatado_array
							tbl_servico_realizado.descricao as servico_realizado,
							tbl_os.posto,
							codigo_posto,
							consumidor_nome,
							consumidor_fone,
							TO_CHAR((current_date - data_digitacao),'DD') as dias
							$campo_solucao
						FROM tbl_os
						JOIN tbl_produto                ON tbl_os.produto = tbl_produto.produto
						JOIN tbl_posto_fabrica          ON tbl_os.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_os_produto             USING(os)
						JOIN tbl_os_item                USING(os_produto)
						JOIN tbl_servico_realizado      ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
						$cond_join
						JOIN tbl_peca                   ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
						JOIN tbl_defeito_constatado     ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
						WHERE tbl_os.fabrica = $login_fabrica
						AND finalizada                  IS NULL
						AND data_fechamento             IS NULL
						AND tbl_os_item.pedido          IS NULL
						AND data_digitacao <= CURRENT_DATE - INTERVAL '5 days + 1 sec'
						AND tbl_servico_realizado.troca_de_peca IS TRUE
						AND tbl_os.excluida             IS NOT TRUE
						$cond_posto
						$cond_estado 		";

				$res = pg_query($con, $sql);
				//echo $sql;exit;
				if (pg_num_rows($res)>0) {
					echo"<br><br>";
					if (strlen($msg_erro2)>0) {
						echo($msg_erro2); echo "<br>";
					}
?>
					<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>
					<caption class='titulo_tabela'><?=$relatorios[$tipo]?></caption>
					<thead>
						<? if($_POST["excel"]){ ?>
						<tr><th colspan='9'>&nbsp;</th></tr>
						<? } ?>
						<tr class='titulo_coluna'>
						<th><?php echo traduz("OS"); ?></th>
						<th><?php echo traduz("Data Digitação"); ?></th>
						<th><?php echo traduz("Data Abertura"); ?></th>
						<th><?php echo traduz("Produto"); ?></th>
						<th><?php echo traduz("Peça"); ?></th>
						<th><?php echo traduz("Defeito Constatado"); ?></th>
						<?
						if (!in_array($login_fabrica, array(106, 152, 180, 181, 182))) {
						?>
						<th><?php echo traduz("Solução"); ?></th>
						<?}?>
						<th><?php echo traduz("Serviço Realizado"); ?></th>
						<th><?php echo traduz("Posto"); ?></th>
						<th width='110'><?php echo traduz("Dias"); ?></th>
					</tr>
					</thead><tbody>
<?
					#echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>CONSUMIDOR NOME</strong></font></td>"; HD 239781
					#echo "<td bgcolor='#485989' width='110'><font color='#FFFFFF'><strong>CONSUMIDOR TELEFONE</strong></font></td>";
				 for ($i=0; $i < pg_num_rows($res); $i++) {
					$os					= pg_fetch_result($res,$i,os);
					$sua_os				= pg_fetch_result($res,$i,sua_os);
					$data_digitacao		= pg_fetch_result($res,$i,data_digitacao);
					$data_abertura		= pg_fetch_result($res,$i,data_abertura);
					$produto			= pg_fetch_result($res,$i,referencia). 		' - ' .pg_fetch_result($res,$i,descricao);
					$peca				= pg_fetch_result($res,$i,peca_referencia). ' - ' .pg_fetch_result($res,$i,peca_descricao);
					$defeito_constatado	= pg_fetch_result($res,$i,defeito_constatado);
					$defeito_constatado_array = pg_fetch_result($res,$i,defeito_constatado_array);
					if ($login_fabrica <> 106){
					$solucao			= pg_fetch_result($res,$i,solucao);
					}
					$servico_realizado	= pg_fetch_result($res,$i,servico_realizado);
					$codigo_posto		= pg_fetch_result($res,$i,codigo_posto);
					#$consumidor_nome = pg_fetch_result($res,$i,consumidor_nome);
					#$consumidor_telefone = pg_fetch_result($res,$i,consumidor_fone);
					$dias = pg_fetch_result($res,$i,dias);

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr bgcolor='$cor'>";
					echo"<td nowrap><a href='os_press.php?os=$os' title='Clique aqui para consultar a OS' target='_blank'>$sua_os</a></td>";
					echo"<td>$data_digitacao</td>";
					echo"<td>$data_abertura</td>";
					echo"<td>$produto</td>";
					echo"<td>$peca</td>";
					if (in_array($login_fabrica, array(152, 152, 180, 181, 182))) {
						//$defeito_constatado_array = implode(', ', $defeito_constatado_array);
						echo"<td>$defeito_constatado_array</td>";						
					}else{
						echo"<td>$defeito_constatado</td>";
					}					
					if (!in_array($login_fabrica, array(106, 152, 180, 181, 182))){
					echo"<td>$solucao</td>";
					}
					echo"<td>$servico_realizado</td>";
					echo"<td>$codigo_posto</td>";
					#echo"<td nowrap>$consumidor_nome</td>";
					#echo"<td nowrap>$consumidor_telefone</td>";
					echo"<td nowrap>$dias</td>";
					echo"</tr>";
				 }
					echo"</table>";
				}
				else echo traduz('Não foram encontrados resultados para esta pesquisa.');
			}

			if ($tipo == 'pedido_10') {

				if (in_array($login_fabrica, array(106, 152, 180, 181, 182))) {
					$cond_join = "";
					$campo_solucao = "";
					$cond_solucao  = "";
					if (in_array($login_fabrica, array(152, 180, 181, 182))) {
						$defeito_constatado_array = "((SELECT array_to_string(array_agg(dc2.descricao),', ') FROM tbl_os_defeito_reclamado_constatado AS odc INNER JOIN tbl_defeito_constatado AS dc2 ON dc2.defeito_constatado = odc.defeito_constatado WHERE odc.os = tbl_os.os AND dc2.fabrica = {$login_fabrica})) AS defeito_constatado_array, ";
					}else{
						$defeito_constatado_array = "";
					}
				} else{
					$cond_join = "LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica AND tbl_solucao.troca_peca IS TRUE";
					$campo_solucao = ", tbl_solucao.descricao as solucao";
				}

				$sql = " SELECT tbl_pedido_item.pedido into TEMP tmp_faturamento_$login_admin
						   FROM tbl_pedido
						   JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
						   JOIN tbl_faturamento_item ON tbl_pedido_item.pedido  = tbl_faturamento_item.pedido AND tbl_pedido_item.peca = tbl_faturamento_item.peca
						   JOIN tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica IN($login_fabrica, 10)
						   WHERE tbl_pedido.fabrica IN($login_fabrica,10); ";

				$sql .= " CREATE INDEX tmp_faturamento_pedido_$login_admin ON tmp_faturamento_$login_admin(pedido); ";

				$sql .= " SELECT os,
							tbl_os.sua_os,
							TO_CHAR(data_digitacao,'dd/mm/yyyy HH24:MI') AS data_digitacao,
							TO_CHAR(data_abertura,'dd/mm/yyyy') AS data_abertura,
							tbl_os_item.pedido,
							tbl_produto.referencia,
							tbl_produto.descricao,
							tbl_peca.referencia as peca_referencia,
							tbl_peca.descricao as peca_descricao,
							tbl_defeito_constatado.descricao as defeito_constatado,
							$defeito_constatado_array
							tbl_servico_realizado.descricao as servico_realizado,
							codigo_posto,
							consumidor_nome,
							consumidor_fone,
							TO_CHAR((current_date - tbl_pedido.data),'DD') as dias
							$campo_solucao
						FROM tbl_os
						JOIN tbl_produto            ON tbl_os.produto = tbl_produto.produto
						JOIN tbl_os_produto         USING(os)
						JOIN tbl_posto_fabrica      USING(posto, fabrica)
						JOIN tbl_os_item            USING(os_produto)
						JOIN tbl_servico_realizado  ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
						$cond_join
						JOIN tbl_peca               ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
						JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
						JOIN tbl_pedido             ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_pedido.fabrica = $login_fabrica
						WHERE tbl_os.fabrica = $login_fabrica
						AND finalizada      IS NULL
						AND data_fechamento IS NULL
						AND tbl_pedido.data <= CURRENT_DATE - INTERVAL '10 days'
						AND tbl_servico_realizado.troca_de_peca IS TRUE
						AND tbl_os_item.pedido              IS NOT NULL
						AND tbl_os.excluida                 IS NOT TRUE
						AND tbl_pedido.pedido NOT IN (SELECT pedido FROM tbl_pedido_cancelado where fabrica = $login_fabrica)
						AND tbl_pedido.pedido NOT IN (SELECT pedido FROM tmp_faturamento_$login_admin)
						$cond_posto
						$cond_estado
						$cond_solucao;";
				#echo(nl2br($sql));
				#die;
				$res = pg_query($con,$sql);

				if (pg_num_rows($res)>0) {
					echo"<br><br>";
					if (strlen($msg_erro2)>0) {
						echo($msg_erro2); echo "<br>";
					}
?>
					<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>
					<caption class='titulo_tabela'><?=$relatorios[$tipo]?></caption>
					<thead>
					<? if($_POST["excel"]){ ?>
						<tr><th colspan='9'>&nbsp;</th></tr>
						<? } ?>
					<tr class='titulo_coluna'>
						<th><?php echo traduz("OS"); ?></th>
						<th><?php echo traduz("Data Digitação"); ?></th>
						<th><?php echo traduz("Data Abertura"); ?></th>
						<th><?php echo traduz("Pedido"); ?></th>
						<th><?php echo traduz("Produto"); ?></th>
						<th><?php echo traduz("Peça"); ?></th>
						<th><?php echo traduz("Defeito Constatado"); ?></th>
						<?
						if (!in_array($login_fabrica, array(152, 180, 181, 182))) {
							?>
						<th><?php echo traduz("Solução"); ?></th>
						<?}?>
						<th><?php echo traduz("Serviço Realizado"); ?></th>
						<th><?php echo traduz("POSTO"); ?></th>
						<th><?php echo traduz("Consumidor Nome");?> </th>
						<th width='110'><?php echo traduz("Consumidor Telefone"); ?></th>
						<th><?php echo traduz("Dias"); ?></th>
					</tr>
					</thead>
					<tbody>
<?
				 for ($i=0; $i < pg_num_rows($res); $i++) {
					$os					= pg_fetch_result($res,$i,os);
					$sua_os				= pg_fetch_result($res,$i,sua_os);
					$data_digitacao		= pg_fetch_result($res,$i,data_digitacao);
					$data_abertura		= pg_fetch_result($res,$i,data_abertura);
					$pedido				= pg_fetch_result($res,$i,pedido);
					$produto			= pg_fetch_result($res,$i,referencia).		' - ' .pg_fetch_result($res,$i,descricao);
					$peca				= pg_fetch_result($res,$i,peca_referencia). ' - ' .pg_fetch_result($res,$i,peca_descricao);
					$defeito_constatado	= pg_fetch_result($res,$i,defeito_constatado);
					$defeito_constatado_array = pg_fetch_result($res,$i,defeito_constatado_array);
					if ($login_fabrica <> 106){
					$solucao			= pg_fetch_result($res,$i,solucao);
					}
					$servico_realizado	= pg_fetch_result($res,$i,servico_realizado);
					$codigo_posto		= pg_fetch_result($res,$i,codigo_posto);
					$consumidor_nome	= pg_fetch_result($res,$i,consumidor_nome);
					$consumidor_telefone= pg_fetch_result($res,$i,consumidor_fone);
					$dias				= pg_fetch_result($res,$i,dias);

// 					#HD 239781
// 					if(strlen($posto)>0){
// 						$sqlP = "SELECT tbl_posto_fabrica.codigo_posto
// 								 FROM tbl_posto_fabrica
// 								 WHERE tbl_posto_fabrica.fabrica = $login_fabrica
// 								 AND   tbl_posto_fabrica.posto   = $posto";
// 						$resP = pg_query($con,$sqlP);
//
// 						if(pg_num_rows($resP)>0){
// 							$codigo_posto = pg_fetch_result($resP,0,codigo_posto);
// 						}
// 					}
//
					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr bgcolor='$cor'>";
					echo"<td nowrap><a href='os_press.php?os=$os' title='Clique aqui para consultar a OS' target='_blank'>$sua_os</a></td>";
					echo"<td>$data_digitacao</td>";
					echo"<td>$data_abertura</td>";
					echo"<td><a href='pedido_admin_consulta.php?pedido=$pedido' target='_blank'>$pedido</a></td>";
					echo"<td>$produto</td>";
					echo"<td>$peca</td>";
					if (in_array($login_fabrica, array(152, 180, 181, 182))) {
						//$defeito_constatado_array = implode(', ', $defeito_constatado_array);
						echo "<td>$defeito_constatado_array</td>";						
					} else {
						echo"<td>$defeito_constatado</td>";
					}
					if (!in_array($login_fabrica, array(106, 152, 180, 181, 182))){
					echo"<td>$solucao</td>";
					}
					echo"<td>$servico_realizado</td>";
					echo"<td>$codigo_posto</td>";
					echo"<td nowrap>$consumidor_nome</td>";
					echo"<td nowrap>$consumidor_telefone</td>";
					echo"<td nowrap>$dias</td>";
					echo"</tr>";
				 }
					echo"</table>";
				} else {
					echo "<center>" . traduz("Não foram encontrados resultados para esta pesquisa") . "</center>";
				}
			}

			if ($tipo == 'troca_sem_faturamento') {

				if (in_array($login_fabrica, array(106, 152, 180, 181, 182))){
					$cond_join = "";
					$campo_solucao = "";
					$cond_solucao  = "";
					if (in_array($login_fabrica, array(152, 180, 181, 182))) {
						$defeito_constatado_array = "((SELECT array_to_string(array_agg(dc2.descricao),', ') FROM tbl_os_defeito_reclamado_constatado AS odc INNER JOIN tbl_defeito_constatado AS dc2 ON dc2.defeito_constatado = odc.defeito_constatado WHERE odc.os = tbl_os.os AND dc2.fabrica = {$login_fabrica})) AS defeito_constatado_array, ";
					}else{
						$defeito_constatado_array = "";
					}
				} else{
					$cond_join = "LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica and tbl_solucao.troca_peca";
					$campo_solucao = ", tbl_solucao.descricao as solucao";
				}

				$sql = " SELECT tbl_pedido_item.pedido into TEMP tmp_faturamento_$login_admin
						   FROM tbl_pedido
						   JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
						   JOIN tbl_faturamento_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido AND tbl_pedido_item.peca = tbl_faturamento_item.peca
						   JOIN    tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
						       AND tbl_faturamento.fabrica in ($login_fabrica, 10)
						   WHERE tbl_pedido.fabrica IN ($login_fabrica, 10); ";

				$sql .= " create index tmp_faturamento_pedido_$login_admin on tmp_faturamento_$login_admin(pedido); ";

				$sql .= " SELECT tbl_os.os,
							tbl_os.sua_os,
							TO_CHAR(data_digitacao,'dd/mm/yyyy HH24:MI') AS data_digitacao,
							TO_CHAR(data_abertura,'dd/mm/yyyy') AS data_abertura,
							tbl_os_item.pedido,
							tbl_produto.referencia,
							tbl_produto.descricao,
							tbl_peca.referencia as peca_referencia,
							tbl_peca.descricao as peca_descricao,
							tbl_defeito_constatado.descricao as defeito_constatado,
							$defeito_constatado_array
							tbl_servico_realizado.descricao as servico_realizado,
							tbl_os.posto,
							consumidor_nome,
							consumidor_fone,
							TO_CHAR((current_date - tbl_pedido.data),'DD') as dias
							$campo_solucao
						FROM tbl_os
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os
						JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
						JOIN tbl_os_item USING(os_produto)
						JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
						$cond_join
						JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
						JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
						JOIN tbl_pedido ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_pedido.fabrica = $login_fabrica
						WHERE tbl_os.fabrica = $login_fabrica
						AND finalizada is null
						AND data_fechamento is null
						AND tbl_servico_realizado.troca_produto is true
						AND tbl_os_item.pedido is not null
						AND tbl_os.excluida  IS NOT TRUE
						AND tbl_pedido.pedido not in (select pedido from tbl_pedido_cancelado where fabrica = $login_fabrica)
						AND tbl_pedido.pedido not in (select pedido from tmp_faturamento_$login_admin)
						$cond_posto
						$cond_estado";

				$res = pg_query($con,$sql);
				//echo(nl2br($sql));
				//die;
				if (pg_num_rows($res)>0) {
					echo"<br><br>";
					if (strlen($msg_erro2)>0) {
						echo($msg_erro2); echo "<br>";
					}
?>
					<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>
					<caption class='titulo_tabela'><?=$relatorios[$tipo]?></caption>
					<thead>
					<? if($_POST["excel"]){ ?>
						<tr><th colspan='9'>&nbsp;</th></tr>
					<? } ?>
					<tr class='titulo_coluna'>
						<th><?php echo traduz("OS"); ?></th>
						<th><?php echo traduz("Data Digitação"); ?></th>
						<th><?php echo traduz("Data Abertura"); ?></th>
						<th><?php echo traduz("Pedido"); ?></th>
						<th><?php echo traduz("Produto"); ?></th>
						<th><?php echo traduz("Peça"); ?></th>
						<th><?php echo traduz("Defeito Constatado"); ?></th>
						<?
						if (!in_array($login_fabrica, array(106, 152, 180, 181, 182))) {
						?>
						<th><?php echo traduz("Solução");?></th>
						<?}?>
						<th><?php echo traduz("Serviço Realizado");?></th>
						<th><?php echo traduz("Posto");?></th>
						<th><?php echo traduz("Consumidor Nome");?></th>
						<th width='110'><?php echo traduz("Consumidor Telefone");?></th>
						<th width='110'><?php echo traduz("dias");?></th>
					</tr>
					</thead>
					<tbody>
<?
				 for ($i=0; $i < pg_num_rows($res); $i++) {
					$os					= pg_fetch_result($res,$i,os);
					$sua_os				= pg_fetch_result($res,$i,sua_os);
					$data_digitacao		= pg_fetch_result($res,$i,data_digitacao);
					$data_abertura		= pg_fetch_result($res,$i,data_abertura);
					$pedido				= pg_fetch_result($res,$i,pedido);
					$produto			= pg_fetch_result($res,$i,referencia).		' - ' .pg_fetch_result($res,$i,descricao);
					$peca				= pg_fetch_result($res,$i,peca_referencia). ' - ' .pg_fetch_result($res,$i,peca_descricao);
					$defeito_constatado = pg_fetch_result($res,$i,defeito_constatado);
					$defeito_constatado_array = pg_fetch_result($res,$i,defeito_constatado_array);
					if ($login_fabrica <> 106){
					$solucao			= pg_fetch_result($res,$i,solucao);
					}
					$servico_realizado	= pg_fetch_result($res,$i,servico_realizado);
					$codigo_posto		= pg_fetch_result($res,$i,codigo_posto);
					$consumidor_nome	= pg_fetch_result($res,$i,consumidor_nome);
					$consumidor_telefone= pg_fetch_result($res,$i,consumidor_fone);
					$dias				= pg_fetch_result($res,$i,dias);

					#HD 239781
// 					if(strlen($posto)>0){
// 						$sqlP = "SELECT tbl_posto_fabrica.codigo_posto
// 								 FROM tbl_posto_fabrica
// 								 WHERE tbl_posto_fabrica.fabrica = $login_fabrica
// 								 AND   tbl_posto_fabrica.posto   = $posto";
// 						$resP = pg_query($con,$sqlP);
//
// 						if(pg_num_rows($resP)>0){
// 							$codigo_posto = pg_fetch_result($resP,0,codigo_posto);
// 						}
// 					}
//
					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr bgcolor='$cor'>";
					echo"<td nowrap><a href='os_press.php?os=$os' title='Clique aqui para consultar a OS' target='_blank'>$sua_os</a></td>";
					echo"<td>$data_digitacao</td>";
					echo"<td>$data_abertura</td>";
					echo"<td><a href='pedido_admin_consulta.php?pedido=$pedido' target='_blank'>$pedido</a></td>";
					echo"<td>$produto</td>";
					echo"<td>$peca</td>";

					if (in_array($login_fabrica, array(152, 180, 181, 182))) {
						//$defeito_constatado_array = implode(', ', $defeito_constatado_array);
						echo"<td>$defeito_constatado_array</td>";						
					}else{
						echo"<td>$defeito_constatado</td>";
					}

					if (!in_array($login_fabrica, array(106, 152, 180, 181, 182))) {
					echo"<td>$solucao</td>";
					}
					echo"<td>$servico_realizado</td>";
					echo"<td>$codigo_posto</td>";
					echo"<td nowrap>$consumidor_nome</td>";
					echo"<td nowrap>$consumidor_telefone</td>";
					echo"<td nowrap>$dias</td>";
					echo"</tr>";
				 }
					echo"</tbody></table>";
				} else {
					echo "<center>" . traduz("Não foram encontrados resultados para esta pesquisa") . "</center>";
				}
			}

			if ($tipo == 'peca_enviada_10') {

				if (in_array($login_fabrica, array(106, 152, 180, 181, 182))) {
					$cond_join = "";
					$campo_solucao = "";
					$cond_solucao  = "";
					if (in_array($login_fabrica, array(152, 180, 181, 182))) {
						$defeito_constatado_array = "((SELECT array_to_string(array_agg(dc2.descricao),', ') FROM tbl_os_defeito_reclamado_constatado AS odc INNER JOIN tbl_defeito_constatado AS dc2 ON dc2.defeito_constatado = odc.defeito_constatado WHERE odc.os = tbl_os.os AND dc2.fabrica = {$login_fabrica})) AS defeito_constatado_array, ";
					}else{
						$defeito_constatado_array = "";
					}
				} else{
					$cond_join = "LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica and tbl_solucao.troca_peca";
					$campo_solucao = ", tbl_solucao.descricao as solucao";
				}

				$sql = "SELECT DISTINCT tbl_os.os,
								(select os_item from tbl_os_item join tbl_os_produto using(os_produto) where tbl_os_produto.os = tbl_os.os order by os_item desc limit 1 ) as os_item 
						INTO temp tmp_os_fatura_$login_admin
						FROM tbl_os
						JOIN tbl_os_produto USING(os)
						WHERE fabrica = $login_fabrica
						AND excluida IS NOT TRUE
						AND  finalizada isnull;
				   
						SELECT DISTINCT tbl_os.os,
							tbl_os.sua_os,
							TO_CHAR(data_digitacao,'dd/mm/yyyy HH24:MI') AS data_digitacao,
							TO_CHAR(data_abertura,'dd/mm/yyyy') AS data_abertura,
							tbl_os_item.pedido,
							tbl_faturamento.faturamento,
							tbl_faturamento.nota_fiscal,
							tbl_faturamento.emissao,
							tbl_produto.referencia,
							tbl_produto.descricao,
							tbl_defeito_constatado.descricao as defeito_constatado,
							$defeito_constatado_array
							codigo_posto,
							consumidor_nome,
							consumidor_fone,
							TO_CHAR((current_date - tbl_faturamento.emissao::timestamp),'DD') as dias
							$campo_solucao
						FROM tbl_os
						JOIN tbl_produto            ON tbl_os.produto = tbl_produto.produto
						JOIN tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_os_produto         ON tbl_os.os = tbl_os_produto.os
						JOIN tbl_os_item            USING(os_produto)
						JOIN tmp_os_fatura_$login_admin ON tbl_os.os = tmp_os_fatura_$login_admin.os and tbl_os_item.os_item = tmp_os_fatura_$login_admin.os_item
						JOIN tbl_servico_realizado  ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
						$cond_join
						JOIN tbl_peca               ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
						LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
						JOIN tbl_pedido             ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_pedido.fabrica = $login_fabrica
						JOIN tbl_faturamento_item   ON tbl_faturamento_item.pedido = tbl_pedido.pedido and ((tbl_faturamento_item.peca = tbl_os_item.peca and tbl_faturamento_item.os isnull) or (tbl_os.os = tbl_faturamento_item.os and tbl_faturamento_item.peca = tbl_os_item.peca))
						JOIN tbl_faturamento        ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica in ($login_fabrica,10)
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_faturamento.emissao <= CURRENT_DATE - INTERVAL '10 days'
						AND finalizada IS NULL
						AND data_fechamento IS NULL
						AND tbl_servico_realizado.troca_de_peca IS TRUE
						AND tbl_os_item.pedido IS NOT NULL
						AND tbl_os.excluida         IS NOT TRUE
						$cond_posto
						$cond_estado";

				$res = pg_query($con,$sql);
				//die;
				if (pg_num_rows($res)>0) {
					echo"<br><br>";
					if (strlen($msg_erro2)>0) {
						echo($msg_erro2); echo "<br>";
					}
?>
					<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>
					<caption class='titulo_tabela'><?=$relatorios[$tipo]?></caption>
					<thead>
					<? if($_POST["excel"]){ ?>
						<tr><th colspan='9'>&nbsp;</th></tr>
					<? } ?>
					<tr class='titulo_coluna'>
						<th><?php echo traduz("OS"); ?></th>
						<th><?php echo traduz("Data Digitação"); ?></th>
						<th><?php echo traduz("Data Abertura"); ?></th>
						<th><?php echo traduz("Pedido"); ?></th>
						<th><?php echo traduz("Nota Fiscal"); ?></th>
						<th><?php echo traduz("Emissão"); ?></th>
						<th><?php echo traduz("Produto"); ?></th>
						<th><?php echo traduz("Defeito Constatado"); ?></th>
						<?
						if (!in_array($login_fabrica, array(106, 152, 180, 181, 182))) {
						?>
						<th><?php echo traduz("Solução");?></th>
						<?}?>
						<th><?php echo traduz("Posto");?></th>
						<th><?php echo traduz("Consumidor Nome");?></th>
						<th width='110'><?php echo traduz("Consumidor Telefone");?></th>
						<th width='110'><?php echo traduz("Dias");?></th>
					</tr>
					</thead>
					<tbody>
<?
				 for ($i=0; $i < pg_num_rows($res); $i++) {
					$os					= pg_fetch_result($res,$i,os);
					$sua_os				= pg_fetch_result($res,$i,sua_os);
					$data_digitacao		= pg_fetch_result($res,$i,data_digitacao);
					$data_abertura		= pg_fetch_result($res,$i,data_abertura);
					$pedido				= pg_fetch_result($res,$i,pedido);
					$faturamento		= pg_fetch_result($res,$i,faturamento);
					$nota_fiscal		= pg_fetch_result($res,$i,nota_fiscal);
					$emissao			= pg_fetch_result($res,$i,emissao);
					$produto			= pg_fetch_result($res,$i,referencia).		' - ' .pg_fetch_result($res,$i,descricao);
					$defeito_constatado	= pg_fetch_result($res,$i,defeito_constatado);
					$defeito_constatado_array = pg_fetch_result($res,$i,defeito_constatado_array);
					if ($login_fabrica <> 106){
					$solucao			= pg_fetch_result($res,$i,solucao);
					}
					$codigo_posto		= pg_fetch_result($res,$i,codigo_posto);
					$consumidor_nome	= pg_fetch_result($res,$i,consumidor_nome);
					$consumidor_telefone= pg_fetch_result($res,$i,consumidor_fone);
					$dias				= pg_fetch_result($res,$i,dias);

// 					#HD 239781
// 					if(strlen($posto)>0){
// 						$sqlP = "SELECT tbl_posto_fabrica.codigo_posto
// 								 FROM tbl_posto_fabrica
// 								 WHERE tbl_posto_fabrica.fabrica = $login_fabrica
// 								 AND   tbl_posto_fabrica.posto   = $posto";
// 						$resP = pg_query($con,$sqlP);
//
// 						if(pg_num_rows($resP)>0){
// 							$codigo_posto = pg_fetch_result($resP,0,codigo_posto);
// 						}
// 					}
//
					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr bgcolor='$cor'>";
					echo"<td nowrap><a href='os_press.php?os=$os' title='Clique aqui para consultar a OS' target='_blank'>$sua_os</a></td>";
					echo"<td>$data_digitacao</td>";
					echo"<td>$data_abertura</td>";
					echo"<td><a href='pedido_admin_consulta.php?pedido=$pedido' target='_blank'>$pedido</a></td>";
					echo"<td><a href='nota_fiscal_detalhe.php?faturamento=$faturamento' target='_blank'>$nota_fiscal</a></td>";
					echo"<td>$emissao</td>";
					echo"<td>$produto</td>";
					if (in_array($login_fabrica, array(152, 180, 181, 182))) {
						//$defeito_constatado_array = implode(', ', $defeito_constatado_array);
						echo"<td>$defeito_constatado_array</td>";
					}else{
						echo"<td>$defeito_constatado</td>";
					}

					if (!in_array($login_fabrica, array(106, 152, 180, 181, 182))) {
					echo"<td>$solucao</td>";
					}
					echo"<td>$codigo_posto</td>";
					echo"<td nowrap>$consumidor_nome</td>";
					echo"<td nowrap>$consumidor_telefone</td>";
					echo"<td nowrap>$dias</td>";
					echo"</tr>";
				 }
					echo"</tbody></table>";
				} else {
					echo "<center>" . traduz("Não foram encontrados resultados para esta pesquisa") . "</center>";
				}
			}
			if ($tipo == 'conserto_5') {

				if (in_array($login_fabrica, array(106, 152, 180, 181, 182))) {
					$cond_join = "";
					$campo_solucao = "";
					$cond_solucao  = "";
					if (in_array($login_fabrica, array(152, 180, 181, 182))) {
						$defeito_constatado_array = "((SELECT array_to_string(array_agg(dc2.descricao), ', ') FROM tbl_os_defeito_reclamado_constatado AS odc INNER JOIN tbl_defeito_constatado AS dc2 ON dc2.defeito_constatado = odc.defeito_constatado WHERE odc.os = tbl_os.os AND dc2.fabrica = {$login_fabrica})) AS defeito_constatado_array, ";
					}else{
						$defeito_constatado_array = "";
					}
				} else{
					$cond_join = "LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica";
					$campo_solucao = ", tbl_solucao.descricao as solucao";

				}

				$sql = "SELECT os,
							tbl_os.sua_os,
							TO_CHAR(data_digitacao,'dd/mm/yyyy HH24:MI') AS data_digitacao,
							TO_CHAR(data_abertura,'dd/mm/yyyy') AS data_abertura,
							TO_CHAR(data_conserto,'dd/mm/yyyy HH24:MI') AS data_conserto,
							tbl_produto.referencia,
							tbl_produto.descricao,
							tbl_peca.referencia as peca_referencia,
							tbl_peca.descricao as peca_descricao,
							tbl_defeito_constatado.descricao as defeito_constatado,
							$defeito_constatado_array
							tbl_servico_realizado.descricao as servico_realizado,
							codigo_posto,
							consumidor_nome,
							consumidor_fone,
							TO_CHAR((CURRENT_DATE - data_conserto),'DDD') as dias
							$campo_solucao
						FROM tbl_os
						JOIN tbl_produto            ON tbl_os.produto = tbl_produto.produto
						JOIN tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_os_produto         USING(os)
						JOIN tbl_os_item            USING(os_produto)
						JOIN tbl_servico_realizado  ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
						$cond_join
						JOIN tbl_peca               ON tbl_peca.peca = tbl_os_item.peca
						AND tbl_peca.fabrica = $login_fabrica
						LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica= $login_fabrica
						WHERE tbl_os.fabrica = $login_fabrica
						  AND finalizada IS NULL
						  AND data_conserto <= CURRENT_DATE - INTERVAL '5 days'
						  AND data_fechamento IS NULL
						  AND tbl_os.excluida IS NOT TRUE
						 $cond_posto
						 $cond_estado
						ORDER BY dias DESC";

				$res = pg_query($con,$sql);
				#echo(nl2br($sql));
				#die;
				if (pg_num_rows($res)>0) {
					echo"<br><br>";
					if (strlen($msg_erro2)>0) {
						echo($msg_erro2); echo "<br>";
					}
?>
					<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>
					<caption class='titulo_tabela'><?=$relatorios[$tipo]?></caption>
					<thead>
					<? if($_POST["excel"]){ ?>
						<tr><th colspan='9'>&nbsp;</th></tr>
					<? } ?>
					<tr class='titulo_coluna'>
						<th><?php echo traduz("OS");?></th>
						<th><?php echo traduz("Data Digitação");?></th>
						<th><?php echo traduz("Data Abertura");?></th>
						<th><?php echo traduz("Data Conserto");?></th>
						<th><?php echo traduz("Produto");?></th>
						<th><?php echo traduz("Peça");?></th>
						<th><?php echo traduz("Defeito Constatado");?></th>
						<?
						if (!in_array($login_fabrica, array(106, 152, 180, 181, 182))) {
						?>
						<th><?php echo traduz("Solução");?></th>
						<?}?>
						<th><?php echo traduz("Serviço Realizado");?></th>
						<th><?php echo traduz("Posto");?></th>
						<th><?php echo traduz("Consumidor Nome");?></th>
						<th width='110'><?php echo traduz("Consumidor Telefone");?></th>
						<th width='110'><?php echo traduz("Dias");?></th>
					</tr>
					</thead>
					<tbody>
<?
				 for ($i=0; $i < pg_num_rows($res); $i++) {
					$os					= pg_fetch_result($res,$i,os);
					$sua_os				= pg_fetch_result($res,$i,sua_os);
					$data_digitacao		= pg_fetch_result($res,$i,data_digitacao);
					$data_abertura		= pg_fetch_result($res,$i,data_abertura);
					$data_conserto		= pg_fetch_result($res,$i,data_conserto);
					$produto			= pg_fetch_result($res,$i,referencia).		' - ' .pg_fetch_result($res,$i,descricao);
					$peca				= pg_fetch_result($res,$i,peca_referencia). ' - ' .pg_fetch_result($res,$i,peca_descricao);
					$defeito_constatado	= pg_fetch_result($res,$i,defeito_constatado);
					$defeito_constatado_array = pg_fetch_result($res,$i,defeito_constatado_array);
					if ($login_fabrica <> 106){
					$solucao			= pg_fetch_result($res,$i,solucao);
					}
					$servico_realizado	= pg_fetch_result($res,$i,servico_realizado);
					$codigo_posto		= pg_fetch_result($res,$i,codigo_posto);
					$consumidor_nome	= pg_fetch_result($res,$i,consumidor_nome);
					$consumidor_telefone= pg_fetch_result($res,$i,consumidor_fone);
					$dias				= pg_fetch_result($res,$i,dias);

					#HD 239781
// 					if(strlen($posto)>0){
// 						$sqlP = "SELECT tbl_posto_fabrica.codigo_posto
// 								 FROM tbl_posto_fabrica
// 								 WHERE tbl_posto_fabrica.fabrica = $login_fabrica
// 								 AND   tbl_posto_fabrica.posto   = $posto";
// 						$resP = pg_query($con,$sqlP);
//
// 						if(pg_num_rows($resP)>0){
// 							$codigo_posto = pg_fetch_result($resP,0,codigo_posto);
// 						}
// 					}
//
					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr bgcolor='$cor'>";
					echo"<td nowrap><a href='os_press.php?os=$os' title='Clique aqui para consultar a OS' target='_blank'>$sua_os</a></td>";
					echo"<td>$data_digitacao</td>";
					echo"<td>$data_abertura</td>";
					echo"<td>$data_conserto</td>";
					echo"<td>$produto</td>";
					echo"<td>$peca</td>";

					if (in_array($login_fabrica, array(152, 180, 181, 182))) {
						//$defeito_constatado_array = implode(', ', $defeito_constatado_array);
						echo"<td>$defeito_constatado_array</td>";						
					}else{
						echo"<td>$defeito_constatado</td>";
					}
					if (!in_array($login_fabrica, array(106, 152, 180, 181, 182))) {
					echo"<td>$solucao</td>";
					}
					echo"<td>$servico_realizado</td>";
					echo"<td>$codigo_posto</td>";
					echo"<td nowrap>$consumidor_nome</td>";
					echo"<td nowrap>$consumidor_telefone</td>";
					echo"<td nowrap>$dias</td>";
					echo"</tr>";
				 }
					echo"</tbody></table>";
				}else {
					echo "<center>" . traduz("Não foram encontrados resultados para esta pesquisa") . "</center>";
				}
			}

			if ($tipo == 'troca_sem_lgr') {

				if ($login_fabrica == 106){
					$cond_join = "";
					$campo_solucao = "";

				} else{
					$cond_join = "JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica";
					$campo_solucao = ", tbl_solucao.descricao as solucao";

				}

				$sql = " SELECT posto_fabrica
						FROM   tbl_fabrica
						WHERE  fabrica = $login_fabrica	";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$posto_fabrica = pg_fetch_result($res,0,0); // Posto distribuidor desta fábrica
				}

				$sql = "SELECT tbl_pedido_item.pedido into TEMP tmp_faturamento_$login_admin
						FROM tbl_pedido_item
						JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido AND
						tbl_pedido.fabrica IN ($login_fabrica,10)
						JOIN tbl_faturamento_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido AND tbl_pedido_item.peca = tbl_faturamento_item.peca
						JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica IN($login_fabrica, 10)
						JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca; ";

				$sql .= "CREATE INDEX tmp_faturamento_pedido_$login_admin ON tmp_faturamento_$login_admin(pedido); ";

				$sql .= " SELECT tbl_os.os,
							tbl_os.sua_os,
							TO_CHAR(data_digitacao,'dd/mm/yyyy HH24:MI') AS data_digitacao,
							TO_CHAR(data_abertura,'dd/mm/yyyy') AS data_abertura,
							tbl_os_item.pedido,
							tbl_os_troca.peca ,
							to_char(tbl_os_troca.data,'YYYY-MM-DD') as data,
							tbl_produto.referencia,
							tbl_produto.descricao,
							tbl_peca.referencia as peca_referencia,
							tbl_peca.descricao as peca_descricao,
							tbl_defeito_constatado.descricao as defeito_constatado,
							tbl_servico_realizado.descricao as servico_realizado,
							tbl_os.posto,
							codigo_posto,
							consumidor_nome,
							consumidor_fone
							$campo_solucao
						FROM tbl_os
						JOIN tbl_produto            ON tbl_os.produto = tbl_produto.produto
						JOIN tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto
						AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_os_troca           ON tbl_os.os = tbl_os_troca.os
						JOIN tbl_os_produto         ON tbl_os.os = tbl_os_produto.os
						JOIN tbl_os_item            ON tbl_os_produto.os_produto = tbl_os_item.os_produto AND tbl_os_item.peca = tbl_os_troca.peca
						JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
						$cond_join
						JOIN tbl_peca              ON tbl_peca.peca = tbl_os_troca.peca
						AND tbl_peca.fabrica = $login_fabrica
						JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
						JOIN tbl_pedido            ON tbl_pedido.pedido = tbl_os_troca.pedido
						AND tbl_pedido.fabrica = $login_fabrica
						WHERE tbl_os.fabrica = $login_fabrica
						AND finalizada          IS NULL
						AND data_fechamento     IS NULL
						AND tbl_os.excluida     IS NOT TRUE
						AND tbl_pedido.pedido   IN(SELECT pedido FROM tmp_faturamento_$login_admin)
						$cond_posto
						$cond_estado;";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res)>0) {
?>                  <br><br>
					<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>
					<caption class='titulo_tabela'><?=$relatorios[$tipo]?></caption>
					<thead>
					<? if($_POST["excel"]){ ?>
						<tr><th colspan='9'>&nbsp;</th></tr>
					<? } ?>
					<tr class='titulo_coluna'>
						<th><?php echo traduz("OS");?></th>
						<th><?php echo traduz("Data Digitação");?></th>
						<th><?php echo traduz("Data Abertura");?></th>
						<th><?php echo traduz("Pedido");?></th>
						<th><?php echo traduz("Produto");?></th>
						<th><?php echo traduz("Peça");?></th>
						<th><?php echo traduz("Defeito Constatado");?></th>
						<?
						if (!in_array($login_fabrica, array(106, 152, 180, 181, 182))) {
						?>
						<th><?php echo traduz("Solução");?></th>
						<?}?>
						<th><?php echo traduz("Serviço Realizado");?></th>
						<th><?php echo traduz("Posto");?></th>
						<th><?php echo traduz("Consumidor Nome");?></th>
						<th width='110'><?php echo traduz("Consumidor Telefone");?></th>
						<th width='110'><?php echo traduz("Dias");?></th>
					</tr>
					</thead>
					<tbody>
<?
				 for ($i=0; $i < pg_num_rows($res); $i++) {
					$os                 = pg_fetch_result($res,$i,os);
					$sua_os             = pg_fetch_result($res,$i,sua_os);
					$data_digitacao     = pg_fetch_result($res,$i,data_digitacao);
					$data_abertura      = pg_fetch_result($res,$i,data_abertura);
					$pedido             = pg_fetch_result($res,$i,pedido);
					$data               = pg_fetch_result($res,$i,data);
					$produto			= pg_fetch_result($res,$i,referencia).		' - ' .pg_fetch_result($res,$i,descricao);
					$peca				= pg_fetch_result($res,$i,peca_referencia). ' - ' .pg_fetch_result($res,$i,peca_descricao);
					$peca_descricao     = pg_fetch_result($res,$i,peca_descricao);
					$defeito_constatado = pg_fetch_result($res,$i,defeito_constatado);
					if ($login_fabrica <> 106){
					$solucao            = pg_fetch_result($res,$i,solucao);
					}
					$servico_realizado  = pg_fetch_result($res,$i,servico_realizado);
					$codigo_posto		= pg_fetch_result($res,$i,codigo_posto);
					$posto				= pg_fetch_result($res,$i,posto);
					$consumidor_nome    = pg_fetch_result($res,$i,consumidor_nome);
					$consumidor_telefone= pg_fetch_result($res,$i,consumidor_fone);

// 					#HD 239781
// 					if(strlen($posto)>0){
// 						$sqlP = "SELECT tbl_posto_fabrica.codigo_posto
// 								 FROM tbl_posto_fabrica
// 								 WHERE tbl_posto_fabrica.fabrica = $login_fabrica
// 								 AND   tbl_posto_fabrica.posto   = $posto";
// 						$resP = pg_query($con,$sqlP);
//
// 						if(pg_num_rows($resP)>0){
// 							$codigo_posto = pg_fetch_result($resP,0,codigo_posto);
// 						}
// 					}
//
					$sqlf = " SELECT faturamento
								FROM tbl_faturamento FA
								JOIN tbl_faturamento_item FI USING(faturamento)
								WHERE FA.fabrica = $login_fabrica
								AND   FI.peca = $peca
								AND   FA.distribuidor = $posto
								AND   FA.posto = $posto_fabrica
								AND   FA.emissao - '$data'::date < 30";
					$resf = pg_query($con,$sqlf);
					if(pg_num_rows($resf) > 0){
						continue;
					}

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr bgcolor='$cor'>";
					echo"<td nowrap><a href='os_press.php?os=$os' title='Clique aqui para consultar a OS' target='_blank'>$sua_os</a></td>";
					echo"<td>$data_digitacao</td>";
					echo"<td>$data_abertura</td>";
					echo"<td><a href='pedido_admin_consulta.php?pedido=$pedido' target='_blank'>$pedido</a></td>";
					echo"<td>$produto_referencia-$produto_descricao</td>";
					echo"<td>$peca_referencia $peca_descricao</td>";
					echo"<td>$defeito_constatado</td>";
					if ($login_fabrica <> 106){
					echo"<td>$solucao</td>";
					}
					echo"<td>$servico_realizado</td>";
					echo"<td>$codigo_posto</td>";
					echo"<td nowrap>$consumidor_nome</td>";
					echo"<td nowrap>$consumidor_telefone</td>";
					echo"</tr>";
				 }
					echo"</table>";
				} else {
					echo "<center>" . traduz("Não foram encontrados resultados para esta pesquisa") . "</center>";
				}
			}

			if($tipo == 'pedido_sem_estoque'){

				$sql = "SELECT tipo_pedido
						 FROM tbl_tipo_pedido
						 WHERE (descricao ILIKE 'fatura%' OR descricao ILIKE 'venda%')
						 AND   fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$tipo_pedido = pg_fetch_result($res,0,0);
				}
				$sql = "SELECT tbl_pedido.pedido      ,
								to_char(tbl_pedido.data,'DD/MM/YYYY') as data,
								tbl_peca.referencia as peca_referencia,
								tbl_peca.descricao  as peca_descricao,
								tbl_posto_fabrica.codigo_posto,
								tbl_status_pedido.descricao as status_pedido
						FROM tbl_pedido
						JOIN tbl_pedido_item USING(pedido)
						JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
						JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca
						LEFT JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_peca.peca
						LEFT JOIN tbl_faturamento_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido AND tbl_pedido_item.peca = tbl_faturamento_item.peca
						WHERE tbl_pedido.fabrica = $login_fabrica
						AND   CURRENT_DATE - tbl_pedido.finalizado  > INTERVAL '20 DAYS'
						AND   tbl_pedido.tipo_pedido = $tipo_pedido
						AND   (tbl_peca.peca IS NULL OR tbl_posto_estoque.qtde = 0)
						AND   tbl_posto_estoque.posto in ( 4311,376542)
						AND   tbl_pedido_item.qtde_cancelada =0
						AND   tbl_status_pedido.status_pedido NOT IN (4,13,14,30,31)
						AND   faturamento_item IS NULL";
				$res = pg_query($con,$sql);
				if (pg_num_rows($res)>0) {
?>                  <br><br>
					<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>
					<caption class='titulo_tabela'><?=$relatorios[$tipo]?></caption>
					<thead>
					<? if($_POST["excel"]){ ?>
						<tr><th colspan='9'>&nbsp;</th></tr>
					<? } ?>
					<tr class='titulo_coluna'>
						<th><?php echo traduz("Pedido");?></th>
						<th><?php echo traduz("Data");?></th>
						<th><?php echo traduz("Peça");?></th>
						<th><?php echo traduz("Posto");?></th>
						<th><?php echo traduz("Status");?></th>
					</tr>
					</thead>
					<tbody>
<?
				 for ($i=0; $i < pg_num_rows($res); $i++) {
					$pedido			= pg_fetch_result($res,$i,pedido);
					$data			= pg_fetch_result($res,$i,data);
					$peca			= pg_fetch_result($res,$i,peca_referencia). ' - ' .pg_fetch_result($res,$i,peca_descricao);
					$codigo_posto	= pg_fetch_result($res,$i,codigo_posto);
					$status_pedido	= pg_fetch_result($res,$i,status_pedido);

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr bgcolor='$cor'>";
					echo"<td nowrap><a href='pedido_admin_consulta.php?pedido=$pedido' title='Clique aqui para consultar o pedido' target='_blank'>$pedido</a></td>";
					echo"<td>$data</td>";
					echo"<td>$peca</td>";
					echo"<td>$codigo_posto</td>";
					echo"<td nowrap>$status_pedido</td>";
					echo"</tr>";
				 }
					echo"</tbody></table>";
				} else {
					echo "<center>" . traduz("Não foram encontrados resultados para esta pesquisa") . "</center>";
				}
			}

			if($tipo == 'aberta_30'){ # HD 342601

				if ($login_fabrica == 106){
					$cond_join = "";
					$campo_solucao = "";

				} else{
					$cond_join = "LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica";
					$campo_solucao = ", tbl_solucao.descricao as solucao";

				}

				$sql = "SELECT os,
							tbl_os.sua_os,
							TO_CHAR(data_digitacao,'dd/mm/yyyy HH24:MI') AS data_digitacao,
							TO_CHAR(data_abertura,'dd/mm/yyyy') AS data_abertura,
							tbl_produto.referencia,
							tbl_produto.descricao,
							NULL as defeito_constatado,
							tbl_servico_realizado.descricao as servico_realizado,
							codigo_posto,
							consumidor_nome,
							consumidor_fone,
							TO_CHAR((current_date - data_digitacao),'DD') as dias,
							tbl_posto.nome
							$campo_solucao
						FROM tbl_os
						JOIN tbl_posto_fabrica          USING(posto, fabrica)
						JOIN tbl_posto                  USING(posto)
						JOIN tbl_produto				USING(produto)
						LEFT JOIN tbl_os_produto		USING(os)
						LEFT JOIN tbl_os_item			USING(os_produto)
						LEFT JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
													   AND tbl_servico_realizado.fabrica = $login_fabrica
						$cond_join
						WHERE tbl_os.fabrica = $login_fabrica
						AND finalizada					IS NULL
						AND data_fechamento				IS NULL
						AND data_digitacao				<= CURRENT_DATE - INTERVAL '31 days -1 sec'
						AND tbl_os.excluida				IS FALSE
						$cond_posto
						$cond_estado";

				$res = pg_query($con,$sql);
				if (pg_num_rows($res)>0) {
					echo"<br><br>";
					if (strlen($msg_erro2)>0) {
						echo($msg_erro2); echo "<br>";
					}
?>					<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>
					<caption class='titulo_tabela'><?=$relatorios[$tipo]?></caption>
					<thead>
					<? if($_POST["excel"]){ ?>
						<tr><th colspan='9'>&nbsp;</th></tr>
						<? } ?>
					<tr class='titulo_coluna'>
						<th nowrap><?php echo traduz("OS")?></th>
						<th><?php echo traduz("Data Digitação")?></th>
						<th><?php echo traduz("Data Abertura")?></th>
						<th><?php echo traduz("Produto")?></th>
						<th><?php echo traduz("Posto")?></th>
						<th width='110'><strong><?php echo traduz("Dias")?></th>
					</tr>
					</thead>
					<tbody>
<?
				 for ($i=0; $i < pg_num_rows($res); $i++) {

					$os					= pg_fetch_result($res,$i,os);
					$sua_os				= pg_fetch_result($res,$i,sua_os);
					$data_digitacao		= pg_fetch_result($res,$i,data_digitacao);
					$data_abertura		= pg_fetch_result($res,$i,data_abertura);
					$produto			= pg_fetch_result($res,$i,referencia).' - '.pg_fetch_result($res,$i,descricao);
					$codigo_posto		= pg_fetch_result($res,$i,codigo_posto);
					$posto_nome			= pg_fetch_result($res,$i,'nome');
					$nome_posto			= substr($posto_nome,0,30);
					$dias = pg_fetch_result($res,$i,dias);

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr bgcolor='$cor'>";
					echo "<td nowrap><a href='os_press.php?os=$os' title='Clique aqui para consultar a OS' target='_blank'>$sua_os</a></td>";
					echo "<td>$data_digitacao</td>";
					echo "<td>$data_abertura</td>";
					echo "<td>$produto</td>";
					echo "<td><acronym title='$codigo_posto - $posto_nome'>$codigo_posto - $nome_posto</acronym></td>";
					echo"<td nowrap>$dias</td>";
					echo"</tr>";
				 }
					echo"</tbody></table>";
				}else{
					echo "<h3>" . traduz("Nenhum resultado encontrado") . "</h3>";
				}
			}
		}
	}

if ($_POST["excel"] == "sim" && strlen($msg_erro) == 0 && pg_numrows($res) > 0) {
	//Redireciona a saida da tela, que estava em buffer, para a variÃ¡vel
	$hora = time();
	$xls = "xls/relatorio_gerencial".$login_posto."_data_".$hora.".xls";
	if (strlen($_POST["mens_corpo"]) > 0) {
		$aux_mens_corpo =  trim($_POST["mens_corpo"]) ;
		$aux_mens_corpo .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'><tr><td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><BR>" . traduz("RELATÓRIO EM EXCEL") . "<BR>" . traduz("Clique aqui para fazer o") . "</font><a href='http://posvenda.telecontrol.com.br/assist/admin/$xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>" . traduz("download do arquivo em EXCEL") . "</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>" . traduz("Você pode ver, imprimir e salvar a tabela para consultas off-line.") . "</font></td>
	</tr>
	</table>";
	}else{
		$msg_erro = traduz("Informe a mensagem.");
	}
	$saida = ob_get_clean();
	$arquivo = fopen($xls, "w");
	fwrite($arquivo, $saida);
	fclose($arquivo);
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo"<tr>";
			echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><BR><input type='button' value='Download em Excel' onclick=\"window.location='$xls'\"></td>";
			echo "</tr>";
		echo "</table>";
	if ($_POST['enviar_email']=='sim' and strlen($msg_erro) == 0) {

		$headers = "From: $aux_email_remetente\n";
		$headers .= "MIME-Version: 1.0\n";
		$headers .= "Content-type: text/html; charset=iso-8859-1\n";

			if(mail($aux_email_destinatario, stripslashes(utf8_encode($aux_assunto)), utf8_encode($aux_mens_corpo), $headers  )){
					echo traduz("Mensagem enviada corretamente!");
					$msg_erro = $msg_ok;
			}else{
				echo traduz("Mensagem não enviada");
			}
		}
	}


?>

<? include "rodape.php" ?>
