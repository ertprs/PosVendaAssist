<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center,gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

/*  MLG 25/01/2011 - Toda a rotina de anexo de imagem da NF, inclusive o array com os parâmetros por fabricante, está num include.
	Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
	Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
	Para saber se tem anexo:temNF($os, 'bool');
	Para saber se 2º anexo: temNF($os, 'bool', 2);
	Para mostrar a imagem:  echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb]'></a>
							echo temNF($os, , 'url'); // Devolve a imagem (<img src='imagem'>)
							echo temNF($os, , 'link', 2); // Devolve um link da 2ª imagem
*/
include_once '../anexaNF_inc.php';

// Exclusão da imagem - AJAX - Inicio
if ($_POST['ajax']=='excluir_nf') {
	if (($arquivo = $_POST['excluir_nf']) != '') {
		$ret = (excluirNF($arquivo, 'r')) ? 'ok' : 'KO';
	} else {
		$ret = 'KO';
	}
	if ($ret == 'ok') {
		$nome_anexo = preg_replace("/.*\/([rexs]_)?(\d+)([_-]\d)?\..*/", "$1$2", $arquivo);
		$ret = "ok|" . temNF($nome_anexo, 'linkEx') . "|$img_nf|$nome_anexo";
	}
	exit($ret);
}
// Exclusão da imagem - AJAX - Fim

$msg_erro = "";

if ($login_fabrica == 6) {
	if (strlen($_GET['os_revenda']) == 0 ) {
		$msg_erro = traduz("Sem número da OS....");
	}
}

$btn_acao = trim (strtolower ($_POST['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);

if ($btn_acao == "explodir" AND strlen($msg_erro)==0) {
	// executa funcao de explosao
	//HD 19570

	$res = pg_query ($con,"BEGIN TRANSACTION");

	$sql = "UPDATE tbl_os_revenda SET
				admin = $login_admin
			WHERE os_revenda = $os_revenda
			AND   fabrica    = $login_fabrica";
	$res = pg_query($con,$sql);
	
	$sql = "SELECT fn_explode_os_revenda($os_revenda,$login_fabrica)";
	$res = pg_query($con,$sql);

	$msg_erro = substr(pg_last_error($con),6);
	if($login_fabrica == 1 and empty($msg_erro)){
		$sqlRevenda_item = "SELECT 
							tbl_os_revenda_item.os_lote, 
							tbl_os_revenda.valor_adicional_justificativa,
							tbl_os_revenda.campos_extra 
							FROM tbl_os_revenda_item 
							JOIN tbl_os_revenda on tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda 
							WHERE tbl_os_revenda.os_revenda  = $os_revenda 
							and tbl_os_revenda.fabrica = $login_fabrica "; 
		$resRevenda_item = pg_query($con, $sqlRevenda_item);

		for($ri=0; $ri<pg_num_rows($resRevenda_item); $ri++){
			$valores_adicionais = "";
			$os_lote 						= pg_fetch_result($resRevenda_item, $ri, 'os_lote');
			$valor_adicional_justificativa 	= json_decode(pg_fetch_result($resRevenda_item, $ri, 'valor_adicional_justificativa'),true);

			$campos_extra = json_decode(pg_fetch_result($resRevenda_item, $ri, 'campos_extra'), true);

			$sqlCampoExtra = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os_lote";
			$resCampoExtra = pg_query($con, $sqlCampoExtra);
			
			if(pg_num_rows($resCampoExtra)==0){
				$valores_adicionais['motivo_descontinuado'] = $valor_adicional_justificativa['motivo_descontinuado'];
				$valores_adicionais['produto_descontinuado'] = $valor_adicional_justificativa['produto_descontinuado'];
				if($campos_extra['reverter_produto'] == 'sim'){				
					$valores_adicionais['produto_origem'] = $campos_extra['produto_origem'];
					$valores_adicionais['reverter_produto'] = $campos_extra['reverter_produto'];
				}

				$valores_adicionais = json_encode($valores_adicionais);

				$sqlInsertCamposExtra = "INSERT INTO tbl_os_campo_extra (fabrica, campos_adicionais, os) values ($login_fabrica, '$valores_adicionais', $os_lote)";
			}else{
				$valores_adicionais = pg_fetch_result($resCampoExtra, 0, "campos_adicionais");
				$valores_adicionais = json_decode($valores_adicionais, true);

				$valores_adicionais['motivo_descontinuado'] = $valor_adicional_justificativa['motivo_descontinuado'];
				$valores_adicionais['produto_descontinuado'] = $valor_adicional_justificativa['produto_descontinuado'];
				if($campos_extra['reverter_produto'] == 'sim'){				
					$valores_adicionais['produto_origem'] = $campos_extra['produto_origem'];
					$valores_adicionais['reverter_produto'] = $campos_extra['reverter_produto'];
				}

				$valores_adicionais = json_encode($valores_adicionais);

				$sqlInsertCamposExtra = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$valores_adicionais' WHERE os = $os_lote";
			}
			$resInsertCamposExtra = pg_query($con, $sqlInsertCamposExtra);
		}
	}

	if(strlen($msg_erro)>0){
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}else{
		$res = pg_query ($con,"COMMIT TRANSACTION");
	}

	if (!empty($msg_erro)) {
		/**
		 * @since HD 790271 - retira o contexto da mensagem de erro,
		 *   deixando apenas a mensagem para o usuário.
		 */
		$arr_erro = explode("\n", $msg_erro);
		$msg_erro = $arr_erro[0];

		if(in_array($login_fabrica,array(151)) && strripos($msg_erro,"O número de série é obrigatório")){
			$msg_erro = "";
		}
	}

	if( strpos($msg_erro,'data_nf_superior_data_abertura') ) {
		$msg_erro= traduz("A data de nota fiscal não pode ser maior que a data de abertura. Por favor, clique em botão Alterar para fazer a correção.");
	}
	if( strpos($msg_erro,'fora da garantia') ) {
		$msg_erro= traduz("Produto Fora da Garantia");
	}

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT  sua_os, posto
				FROM	tbl_os_revenda
				WHERE	os_revenda = $os_revenda
				AND		fabrica = $login_fabrica";
		$res = pg_query($con, $sql);
		$sua_os = pg_fetch_result($res,0,sua_os);
		$posto  = pg_fetch_result($res,0,posto);

		$sql_tem_anexo = "SELECT tdocs FROM tbl_tdocs WHERE referencia_id = '$os_revenda' AND fabrica = $login_fabrica";
		$res_tem_anexo = pg_query($con, $sql_tem_anexo);
		if (pg_num_rows($res_tem_anexo) > 0) {
			$oss = [];
			$sql_oss = "SELECT os FROM tbl_os WHERE sua_os LIKE '".$sua_os."-%'	AND	fabrica = $login_fabrica AND posto = $posto";
			$res_oss = pg_query($con, $sql_oss);
			for ($r = 0; $r < pg_num_rows($res_oss); $r++) {
				$oss[] = pg_fetch_result($res_oss, $r, 'os');
			}

			for ($a = 0; $a < pg_num_rows($res_tem_anexo); $a++) {
				$xtdocs = pg_fetch_result($res_tem_anexo, $a, 'tdocs');
				foreach ($oss as $key => $value) {
					$sql_insert_anexo = "INSERT INTO tbl_tdocs 
										 (
										 	tdocs_id,
										 	fabrica,
										 	contexto,
										 	situacao,
										 	obs,
										 	referencia,
										 	referencia_id,
										 	hash_temp
										 )

										 SELECT tdocs_id,
											 	$login_fabrica,
											 	contexto,
											 	situacao,
											 	obs,
											 	referencia,
											 	$value,
											 	hash_temp
										 FROM tbl_tdocs
										 WHERE tdocs = $xtdocs
										 AND fabrica = $login_fabrica";
					$res_insert_anexo = pg_query($con, $sql_insert_anexo);
				}
				$sql_exclui = "DELETE FROM tbl_tdocs WHERE tdocs = $xtdocs AND fabrica = $login_fabrica";
				$res_exclui = pg_query($con, $sql_exclui);
			}
		}
		
		// redireciona para os_revenda_explodida.php
		header("Location: os_revenda_explodida.php?sua_os=$sua_os&posto=$posto");
		exit;
	}
}

if(strlen($os_revenda) > 0){
	if($login_fabrica == 1) $left = " LEFT ";

	if (in_array($login_fabrica, [11,172])) {
		$condFab = "AND   tbl_os_revenda.fabrica IN (11,172)";
	} else {
		$condFab = "AND   tbl_os_revenda.fabrica    = $login_fabrica";
	}

	// seleciona do banco de dados
	$sql = "SELECT   tbl_os_revenda.sua_os                                                ,
					 tbl_os_revenda.obs                                                   ,
					 to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					 to_char(tbl_os_revenda.digitacao,'DD/MM/YYYY')     AS data_digitacao ,
					 tbl_revenda.nome  AS revenda_nome                                    ,
					 tbl_revenda.cnpj  AS revenda_cnpj                                    ,
					 tbl_revenda.fone  AS revenda_fone                                    ,
					 tbl_revenda.email AS revenda_email                                   ,
					 tbl_posto_fabrica.codigo_posto AS posto_codigo                       ,
					 tbl_posto.nome    AS posto_nome                                      ,
					 tbl_os_revenda.tipo_os                                               ,
					 tbl_os_revenda.consumidor_email
			FROM tbl_os_revenda
			$left JOIN tbl_revenda ON tbl_os_revenda.revenda = tbl_revenda.revenda
			LEFT JOIN tbl_posto USING (posto)
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os_revenda.os_revenda = $os_revenda
			{$condFab}";
	$res = pg_query($con, $sql);
	// die(nl2br($sql));
	if (pg_num_rows($res) > 0){
		$sua_os         = pg_fetch_result($res,0,sua_os);
		$data_abertura  = pg_fetch_result($res,0,data_abertura);
		$data_digitacao = pg_fetch_result($res,0,data_digitacao);
		$revenda_nome   = pg_fetch_result($res,0,revenda_nome);
		$revenda_cnpj   = pg_fetch_result($res,0,revenda_cnpj);
		$revenda_fone   = pg_fetch_result($res,0,revenda_fone);
		$revenda_email  = pg_fetch_result($res,0,revenda_email);
		$posto_codigo   = pg_fetch_result($res,0,posto_codigo);
		$posto_nome     = pg_fetch_result($res,0,posto_nome);
		$obs            = pg_fetch_result($res,0,obs);
		$tipo_os        = pg_fetch_result($res,0,tipo_os);
		$consumidor_email  = pg_fetch_result($res,0,consumidor_email);
	}else{
		if($login_fabrica == 15){
			header('Location: os_revenda_latina.php');
		}else{
			header('Location: os_revenda.php');
		}

		exit;
	}
}

$title			= traduz("Cadastro de Ordem de Serviço - Revenda");
$layout_menu	= "callcenter";

include "cabecalho.php";

?>
<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script type="text/javascript" src= "../js/anexaNF_excluiAnexo.js"></script>
<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;
}
.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}
.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #CED7e7;
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
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
.formulario2{
	background-color:#D9E2EF;
	font:11px Arial;

}
table.formulario td{
	border:1px solid #596d9b;
}
.subtitulo{
	background-color: #7092BE;
	font:11px Arial;
	color: #000;
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


<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" class="formulario2">
	<? if (strlen ($msg_erro) > 0) { ?>
		<tr class="msg_erro">
			<td colspan='3'>
				<? echo $msg_erro ?>
			</td>
		</tr>
	<? } ?>
	<tr class="titulo_tabela"><br><td colspan="3"><?=traduz('OS Revenda Finalizada')?></td></tr>
	<tr>
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
		<td valign="top" align="left">
			<table width="100%" border="0" cellspacing="3" >
				<tr><td colspan="3">&nbsp;</td></tr>
				<tr class="subtitulo">
					<td nowrap>
						<?=traduz('OS Fabricante')?>
					</td>
					<td nowrap>
						<?=traduz('Data Abertura')?>
					</td>
					<td nowrap>
						<?=traduz('Data Digitação')?>
					</td>
				</tr>
				<tr>
					<td nowrap align='left' >
						<? if ($login_fabrica == 1) echo $posto_codigo; echo $sua_os; ?>
					</td>
					<td nowrap>
						<? echo $data_abertura ?>
					</td>
					<td nowrap>
						<? echo $data_digitacao ?>
					</td>
				</tr>

			</table>

			<br>

			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario2">
				<tr class="subtitulo">
					<td>
						<?=traduz('Nome Revenda')?>
					</td>
					<td>
						<?=traduz('CNPJ Revenda')?>
					</td>
					<td>
						<?=traduz('Fone Revenda')?>
					</td>
					<td>
						<?=traduz('E-mail Revenda')?>
					</td>
				</tr>
				<tr>
					<td>
						<? echo $revenda_nome ?>
					</td>
					<td>
						<? echo $revenda_cnpj ?>
					</td>
					<td>
						<? echo $revenda_fone ?>
					</td>
					<td>
						<? echo $revenda_email ?>
					</td>
				</tr>
			</table>
			<br>
			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario2">
				<tr class="subtitulo">
					<td width='42%'>
						<?=traduz('Código do Posto')?>
					</td>
					<td width='58%'>
						<?=traduz('Nome do Posto')?>
					</td>
				</tr>
				<tr>
					<td>
						<? echo $posto_codigo ?>
					</td>
					<td>
						<? echo $posto_nome ?>
					</td>
				</tr>
			</table>
			<br>
			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario2">
				<tr class="subtitulo">
					<td>
						<?=traduz('Observações')?>
					</td>
				</tr>
				<tr>
					<td>
						<? echo $obs ?>
					</td>
				</tr>
				<? if($login_fabrica == 1) { ?>
				<tr><td>&nbsp;</td></tr>
				<tr class="subtitulo">
					<td>
						<?=traduz('E-mail de Contato')?>
					</td>
				</tr>
				<tr>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo $consumidor_email ?>
					</td>
				</tr>
				<? } ?>
			</table>
			<br>
		</td>
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>

<table width="700" border="0" cellpadding="2" cellspacing="3" align="center">
	<tr>
		<td>
		<table width="700" border="0" cellpadding="2" cellspacing="1" align="center" class="tabela">

			<tr class="titulo_coluna">
				<? if ($login_fabrica == 1) { ?>
					<td><?=traduz('Cód. Fabric.')?></td>
				<? } ?>
				<td><?=traduz('Produto')?></td>
				<td><?=traduz('Descrição do Produto')?></td>

				<? if(!in_array($login_fabrica, array(151,160)) and !$replica_einhell) { ?>
				<td><?=traduz('Número de Série')?></td>
				<? } if($login_fabrica == 162){ ?>
				<td><?=traduz('IMEI')?></td>
				<?} if((in_array($login_fabrica, array(160)) or $replica_einhell)) { ?>
				<td><?=traduz('Nº lote')?></td>
				<td><?=traduz('Versão Produto')?></td>
				<? } ?>

				<? if ($login_fabrica == 1) { ?>
					<td><?=traduz('Type')?></td>
					<td ><?=traduz('Embalagem Original')?></td>
					<td><?=traduz('Sinal de Uso')?></td>
					<td><?=traduz('Número da NF')?></td>
				<? } ?>
				<?php if($login_fabrica == 121){ ?>
					<td><?=traduz('QTDE')?></td>
				<? } ?>

				<?php if($login_fabrica == 94){ //hd_chamado=2705567 ?>
					<td><?=traduz('Defeito Reclamado')?></td>
				<?php } ?>
			</tr>
		<?
			// monta o FOR
			$qtde_item = 20;

				if ($os_revenda){
					// seleciona do banco de dados
					$sql =	"SELECT tbl_os_revenda_item.os_revenda_item    ,
									tbl_os_revenda.explodida               ,
									tbl_os_revenda_item.produto            ,
									tbl_os_revenda_item.serie              ,
									tbl_os_revenda_item.type               ,
									tbl_os_revenda_item.rg_produto         ,
									tbl_os_revenda_item.qtde               ,
									tbl_os_revenda_item.embalagem_original ,
									tbl_os_revenda_item.sinal_de_uso       ,
									tbl_os_revenda_item.codigo_fabricacao  ,
									tbl_os_revenda_item.nota_fiscal        ,
									tbl_produto.referencia                 ,
									tbl_produto.descricao                  ,
									tbl_produto.voltagem,
									tbl_os_revenda_item.defeito_constatado_descricao
							FROM	tbl_os_revenda
							JOIN	tbl_os_revenda_item ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
							JOIN	tbl_produto ON tbl_produto.produto = tbl_os_revenda_item.produto
							WHERE	tbl_os_revenda.os_revenda = $os_revenda";
					$res = pg_query($con, $sql);

					for ($i=0; $i<pg_num_rows($res); $i++)
					{

						$referencia_produto = pg_fetch_result($res,$i,referencia);
						$produto_descricao  = pg_fetch_result($res,$i,descricao);
						$produto_voltagem   = pg_fetch_result($res,$i,voltagem);
						$produto_serie      = pg_fetch_result($res,$i,serie);
						$type               = pg_fetch_result($res,$i,type);
						$rg_produto         = pg_fetch_result($res,$i,rg_produto);
						$qtde               = pg_fetch_result($res,$i,qtde);
						$embalagem_original = pg_fetch_result($res,$i,embalagem_original);
						$sinal_de_uso       = pg_fetch_result($res,$i,sinal_de_uso);
						$codigo_fabricacao  = pg_fetch_result($res,$i,codigo_fabricacao);
						$nota_fiscal        = pg_fetch_result($res,$i,nota_fiscal);
						$explodida          = pg_fetch_result($res,$i,explodida);
						$defeito_reclamado  = pg_fetch_result($res, $i, defeito_constatado_descricao);
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		?>
			<?  echo "<tr bgcolor='$cor'>";
			if ($login_fabrica == 1) { ?>
				<td>
					<? echo $codigo_fabricacao ?>
				</td>
				<? } ?>
				<td>
					<? echo $referencia_produto ?>
				</td>
				<td align="left">

					<?
					echo $produto_descricao;
					if (strlen($produto_voltagem) > 0) echo " - ".$produto_voltagem;
					?>

				</td>
				<? if(!in_array($login_fabrica, array(151))) { ?>
				<td>
					<? echo $produto_serie ?>
				</td>				
					<?php if($login_fabrica == 160 or $replica_einhell){
						echo "<td>$type</td>";
					}?>
				<? }
				if($login_fabrica == 162){
					echo "<td>$rg_produto</td>";
				}
				if ($login_fabrica == 1) { ?>
				<td>
					<? echo $type ?>
				</td>
				<td>
					<? if ($embalagem_original == 't') echo "Sim"; else echo "Não"; ?>
				</td>
				<td>
					<? if ($sinal_de_uso == 't') echo "Sim"; else echo "Não"; ?>
				</td>
				<td>
					<? echo $nota_fiscal ?>
				</td>
				<? } ?>

				<?php if($login_fabrica == 121){ ?>
					<td>
						<? echo $qtde ?>
					</td>
				<? } ?>

				<?php if($login_fabrica == 94){ //hd_chamado=2705567 ?>
					<td><?=$defeito_reclamado?></td>
				<?php } ?>
			</tr>
		<?
					}
				}
		?>
		</table>
	</td>
  </tr>
 </table>
<? //HD 21501
if ($login_fabrica==1) {
	if(strlen($os_revenda) > 0 ){
		$sql="SELECT tipo_atendimento
				from tbl_os_revenda
				where os_revenda=$os_revenda";
		$res=pg_query($con,$sql);
		$tipo_atendimento=pg_fetch_result($res,0,tipo_atendimento);
		if (strlen($tipo_atendimento) > 0) {

		echo "<table width='650' border='0' cellpadding='0' cellspacing='0' align='center'  class='formulario'>";

		echo "</table>";
		}
	}
}
?>

<input type='hidden' name='btn_acao' value=''>

<table width="700" align="center">
	<?
		$link = ($login_fabrica == 15) ? "os_revenda_latina.php" : "os_revenda.php";
	?>
	<tr>
		<td align="center" style="border:0px;">
			<?php if(strlen( $explodida ) == 0) { ?>
			<input type="button" style="cursor:pointer;" value="<?=traduz('Alterar')?>"  onclick="javascript: document.location='<?=$link?>?os_revenda=<? echo $os_revenda; ?>'" ALT="Alterar" border='0'>
			<input type="button" style="cursor:pointer;" value="<?=traduz('Explodir')?>" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='explodir' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Explodir" border='0' >
			<?php } ?>
			<input type="button" style="cursor:pointer;" value="<?=traduz('Imprimir')?>" onclick="javascript: window.open('os_revenda_print.php?os_revenda=<? echo $os_revenda; ?>','osrevenda');" ALT="Imprimir" border='0'>
		</td>
	</tr>

	<tr><td>&nbsp;</td></tr>
	<?php if(strlen( $explodida ) == 0) { ?>
	<tr>
		<td class='texto_avulso'>
			<?=traduz('Para OS de Troca de Revenda ser encaminhada para aprovação e fechamento, é necessário explodir a OS')?>
		</td>
	<tr>
	<? }
	if ($anexaNotaFiscal) {
		$totNFs =  temNF("r_$os_revenda", 'count');

		if ($totNFs) { ?>
	<tr>
		<td>
			<br>
			<div>
				<?=temNF($os_revenda, 'linkEx', 'r')?>
				<?if ($totNFs) echo $include_imgZoom;?>
			</div>
		</td>
	</tr>
<?	}
}
/*	if ($totNFs < LIMITE_ANEXOS) { ?>
	<tr>
		<td><?=$inputNotaFiscal?></td>
	</tr>
	<?	}
	}*/ // HD 321132 - FIM ?>

	<tr>
		<td>
			<input type="button" style="cursor:pointer;" value="<?=traduz('Voltar para Consulta')?>" onclick="window.location='os_revenda_consulta.php?<?echo $_COOKIE['cookget']; ?>'">
		</td>
	</tr>
</table>

<br>


</form>
<br>

<? include 'rodape.php'; ?>
