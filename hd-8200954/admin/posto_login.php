<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="auditoria,call_center ";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro  = '';
$msg_debug = '';

//HD 100300 - Pedido de promoção automatica
$abrir = fopen("bloqueio_pedidos/libera_promocao_black.txt", "r");
$ler   = fread($abrir, filesize("bloqueio_pedidos/libera_promocao_black.txt"));
fclose($abrir);

$conteudo_p    = explode(";;", $ler);
$data_inicio_p = $conteudo_p[0];
$data_fim_p    = $conteudo_p[1];
$comentario_p  = $conteudo_p[2];
$promocao      = "f";

if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("$data_fim_p"))) { // DATA DA VOLTA
	if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("$data_inicio_p"))) { // DATA DO BLOQUEIO
		$promocao = "t";
	}
}
//echo "promocao $promocao";
//HD 100300 pedido de promocao automatico.

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];
$visual_black = "auditoria-admin";
$title       = traduz("LOGAR COMO POSTO AUTORIZADO");
$cabecalho   = traduz("Logar como Posto Autorizado");
$layout_menu = "callcenter";

include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

?>
<script language="JavaScript">

$(function(){
	$("#tab tr:even").css("background-color", "#F7F5F0");
	$("#tab tr:odd").css("background-color", "#F1F4FA");

	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});
});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
	$("#id_posto").val(retorno.posto);
	$("form[name=frm_posto]").submit();
}

</script>

<style type="text/css">
	.table{
		min-width: 850px;
	}
</style>
<?php

if (strlen($_GET['posto']) > 0) $posto = trim($_GET['posto']);
if (strlen($_POST['posto']) > 0) $posto = trim($_POST['posto']);

if (strlen($posto) > 0) {
	$sql = "SELECT tbl_posto.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_posto.posto = $posto";
	$res = pg_query($con,$sql);
	$posto_codigo = pg_result($res,0,codigo_posto);
	$posto_nome = pg_result($res,0,nome);
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz("Campos obrigatórios")?> </b>
</div>
<div class="form-search form-inline tc_formulario">
	<form name="frm_posto" style='margin-bottom:0' method="post" action="<? echo $PHP_SELF ?>">

			<div class="titulo_tabela"><?=traduz("Parâmetros de Pesquisa")?></div>
			<br />
			<div class='row-fluid'>
				<div class='span2'></div>
					<div class='span3'>
						<div class='control-group'>
							<label class='control-label' for='Código do Posto'><?=traduz("Código do Posto")?></label><br>
							<div class='controls controls-row input-append'>
								<h5 class='asteristico'>*</h5>
								<input class="controls span8" type="text" name="posto_codigo" id="codigo_posto" size="15" value="<? echo $posto_codigo ?>">
								<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</div>	
						</div>	
					</div>
					<div class='span4'>
						<div class='control-group'>
							<label class='control-label' for='Nome do Posto'><?=traduz("Nome do Posto")?></label>
							<div class='controls controls-row input-append'>
								<h5 class='asteristico'>*</h5>
								<input class="controls span12" type="text" id="descricao_posto" name="posto_nome" size="50" value="<? echo $posto_nome ?>" >
								<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</div>
						</div>
					</div>
					<input class="controls span8" type="hidden" name="posto" id="id_posto" size="15" value="<?= $posto ?>">
				<div class='span2'></div>	
			</div>
			<br />

	<?php
	#-------------------- Pesquisa Posto -----------------

	//hd 56366 alterado o campo fone e fax para a tbl_posto_fabrica
	if (strlen($posto) > 0 and strlen($msg_erro) == 0 ) {

		if($login_fabrica == 1){
			$campo_black = ", (
								SELECT tbl_posto_bloqueio.desbloqueio
								FROM tbl_posto_bloqueio
								WHERE tbl_posto_bloqueio.posto = tbl_posto.posto
								AND tbl_posto_bloqueio.fabrica = $login_fabrica
								AND tbl_posto_bloqueio.pedido_faturado
								ORDER BY tbl_posto_bloqueio.posto_bloqueio DESC 
								LIMIT 1	
							  ) as bloqueados ";
		}

		$sql = "SELECT  tbl_posto_fabrica.posto               ,
						tbl_posto_fabrica.credenciamento      ,
						tbl_posto_fabrica.codigo_posto        ,
						tbl_posto_fabrica.tipo_posto          ,
						tbl_posto_fabrica.transportadora_nome ,
						tbl_posto_fabrica.transportadora      ,
						tbl_posto_fabrica.cobranca_endereco   ,
						tbl_posto_fabrica.cobranca_numero     ,
						tbl_posto_fabrica.cobranca_complemento,
						tbl_posto_fabrica.cobranca_bairro     ,
						tbl_posto_fabrica.cobranca_cep        ,
						tbl_posto_fabrica.cobranca_cidade     ,
						tbl_posto_fabrica.cobranca_estado     ,
						tbl_posto_fabrica.obs                 ,
						tbl_posto_fabrica.banco               ,
						tbl_posto_fabrica.agencia             ,
						tbl_posto_fabrica.conta               ,
						tbl_posto_fabrica.nomebanco           ,
						tbl_posto_fabrica.favorecido_conta    ,
						tbl_posto_fabrica.cpf_conta           ,
						tbl_posto_fabrica.tipo_conta          ,
						tbl_posto_fabrica.obs_conta           ,
						tbl_posto.nome                        ,
						tbl_posto.cnpj                        ,
						tbl_posto.ie                          ,
						tbl_posto_fabrica.contato_endereco    AS endereco     ,
						tbl_posto_fabrica.contato_numero      AS numero       ,
						tbl_posto_fabrica.contato_complemento AS complemento  ,
						tbl_posto_fabrica.contato_bairro      AS bairro       ,
						tbl_posto_fabrica.contato_cep         AS cep          ,
						tbl_posto_fabrica.contato_cidade      AS cidade       ,
						tbl_posto_fabrica.contato_estado      AS estado       ,
						tbl_posto_fabrica.contato_email       AS email        ,
						tbl_posto_fabrica.contato_fone_comercial AS fone      ,
						tbl_posto_fabrica.contato_fax            AS fax       ,
						tbl_posto_fabrica.contato_nome     as contato      ,
						tbl_posto.suframa                     ,
						tbl_posto.capital_interior            ,
						tbl_posto_fabrica.nome_fantasia               ,
						tbl_posto_fabrica.item_aparencia      ,
						tbl_posto_fabrica.senha               ,
						tbl_posto_fabrica.desconto            ,
						tbl_posto_fabrica.pedido_em_garantia  ,
						tbl_posto_fabrica.pedido_em_garantia     ,
						tbl_posto_fabrica.pedido_faturado        ,
						tbl_posto_fabrica.digita_os              ,
						tbl_posto_fabrica.reembolso_peca_estoque ,
						tbl_posto_fabrica.pedido_via_distribuidor,
						tbl_posto_fabrica.coleta_peca            ,
						tbl_posto_fabrica.prestacao_servico      ,
						tbl_posto_fabrica.categoria,
						tbl_transportadora.nome as transportadora_nome    ,
						tbl_tipo_posto.descricao as tipo_posto_descricao 	
						$campo_black 				
				FROM	tbl_posto
				LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora  = tbl_posto_fabrica.transportadora
				LEFT JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
				WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
				AND     tbl_posto_fabrica.posto   = $posto ";

	//if ($ip == '192.168.0.66') echo $sql;
		$res = pg_query($con,$sql);

		if (pg_numrows($res) > 0) {
			$posto                   = trim(pg_fetch_result($res, 0, 'posto'));
			$credenciamento          = trim(pg_fetch_result($res, 0, 'credenciamento'));
			$codigo                  = trim(pg_fetch_result($res, 0, 'codigo_posto'));
			$nome                    = trim(pg_fetch_result($res, 0, 'nome'));
			$cnpj                    = trim(pg_fetch_result($res, 0, 'cnpj'));
			$ie                      = trim(pg_fetch_result($res, 0, 'ie'));
			$endereco                = trim(pg_fetch_result($res, 0, 'endereco'));
			$numero                  = trim(pg_fetch_result($res, 0, 'numero'));
			$complemento             = trim(pg_fetch_result($res, 0, 'complemento'));
			$bairro                  = trim(pg_fetch_result($res, 0, 'bairro'));
			$cep                     = trim(pg_fetch_result($res, 0, 'cep'));
			$cidade                  = trim(pg_fetch_result($res, 0, 'cidade'));
			$estado                  = trim(pg_fetch_result($res, 0, 'estado'));
			$email                   = trim(pg_fetch_result($res, 0, 'email'));
			$fone                    = trim(pg_fetch_result($res, 0, 'fone'));
			$fax                     = trim(pg_fetch_result($res, 0, 'fax'));
			$contato                 = trim(pg_fetch_result($res, 0, 'contato'));
			$suframa                 = trim(pg_fetch_result($res, 0, 'suframa'));
			$item_aparencia          = trim(pg_fetch_result($res, 0, 'item_aparencia'));
			$obs                     = trim(pg_fetch_result($res, 0, 'obs'));
			$capital_interior        = trim(pg_fetch_result($res, 0, 'capital_interior'));
			$tipo_posto              = trim(pg_fetch_result($res, 0, 'tipo_posto'));
			$senha                   = trim(pg_fetch_result($res, 0, 'senha'));
			$desconto                = trim(pg_fetch_result($res, 0, 'desconto'));
			$nome_fantasia           = trim(pg_fetch_result($res, 0, 'nome_fantasia'));
			$transportadora          = trim(pg_fetch_result($res, 0, 'transportadora'));

			$cobranca_endereco       = trim(pg_fetch_result($res, 0, 'cobranca_endereco'));
			$cobranca_numero         = trim(pg_fetch_result($res, 0, 'cobranca_numero'));
			$cobranca_complemento    = trim(pg_fetch_result($res, 0, 'cobranca_complemento'));
			$cobranca_bairro         = trim(pg_fetch_result($res, 0, 'cobranca_bairro'));
			$cobranca_cep            = trim(pg_fetch_result($res, 0, 'cobranca_cep'));
			$cobranca_cidade         = trim(pg_fetch_result($res, 0, 'cobranca_cidade'));
			$cobranca_estado         = trim(pg_fetch_result($res, 0, 'cobranca_estado'));

			$pedido_em_garantia      = trim(pg_fetch_result($res, 0, 'pedido_em_garantia'));
			$pedido_faturado         = trim(pg_fetch_result($res, 0, 'pedido_faturado'));
			$digita_os               = trim(pg_fetch_result($res, 0, 'digita_os'));
			$reembolso_peca_estoque  = trim(pg_fetch_result($res, 0, 'reembolso_peca_estoque'));
			$pedido_via_distribuidor = trim(pg_fetch_result($res, 0, 'pedido_via_distribuidor'));
			$coleta_peca             = trim(pg_fetch_result($res, 0, 'coleta_peca'));
			$prestacao_servico       = trim(pg_fetch_result($res, 0, 'prestacao_servico'));
			$banco                   = trim(pg_fetch_result($res, 0, 'banco'));
			$agencia                 = trim(pg_fetch_result($res, 0, 'agencia'));
			$conta                   = trim(pg_fetch_result($res, 0, 'conta'));
			$nomebanco               = trim(pg_fetch_result($res, 0, 'nomebanco'));
			$favorecido_conta        = trim(pg_fetch_result($res, 0, 'favorecido_conta'));
			$cpf_conta               = trim(pg_fetch_result($res, 0, 'cpf_conta'));
			$tipo_conta              = trim(pg_fetch_result($res, 0, 'tipo_conta'));
			$obs_conta               = trim(pg_fetch_result($res, 0, 'obs_conta'));
			$pedido_via_distribuidor = trim(pg_fetch_result($res, 0, 'pedido_via_distribuidor'));

			$transportadora_nome     = trim(pg_fetch_result($res, 0, 'transportadora_nome'));
			$tipo_posto_descricao    = trim(pg_fetch_result($res, 0, 'tipo_posto_descricao'));
			
			if($login_fabrica == 1){
				$bloqueados 			 = trim(pg_fetch_result($res, 0, 'bloqueados'));
				if($bloqueados == 'f'){
					$bloqueados = "SIM";
				}else{
					$bloqueados = "NÃO";
				}
			}

			$categoria_posto        = trim(pg_fetch_result($res, 0, 'categoria'));

			if($categoria_posto == 'mega projeto'){
				$categoria_posto = "Industria/Mega Projeto";
			}

			$endereco                = str_replace("\""   , "", $endereco);
			if (strlen($cnpj) == 14) $cnpj = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
			if (strlen($cnpj) == 11) $cnpj = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cnpj);
		}

		if ($credenciamento != 'DESCREDENCIADO') {
	?>
	</form>	
	<form action="../index.php?ajax=sim&acao=validar&redir=sim" id="logar_como"
		  target='_blank' method='post' class="formulario"
		   style='width:700px;height:3em;margin:0 auto;text-align:center'>
		<input type="hidden" name="login" value='<?=$posto_codigo?>' />
		<input type="hidden" name="senha" value='<?=$senha?>' />
		<input type='hidden' name='btnAcao' value='<?=traduz('Enviar')?>' />
		<button type='submit' class='btn'><?=traduz("Logar como este Posto")?></button>
		<button type='reset' class='btn btn-danger' onclick='window.location="<?=$PHP_SELF?>";'><?=traduz("Limpar")?></button>
		<br /><br />
	</form>
	<?	} else { ?>
			<div style='width:700px;height:3em;margin:0 auto;text-align:center' class='formulario'>
				<button class='btn' type='button' disabled='disabled'><?=traduz("Posto Descredenciado")?></button>
				<button class='btn btn-danger' type='reset' onclick='window.location="<?=$PHP_SELF?>";'><?=traduz("Limpar")?></button>
			</div>
	<?	}?>
</div>
<br />
<table align='center' border='0' cellpadding="1" cellspacing="1" class='table table-bordered table-hover table-large'>
	<thead>
		<th colspan="5" class="titulo_tabela">
			<?=traduz("Informações Cadastrais")?>
		</th>
	</thead>
	<thead class="titulo_coluna">
		<th><?=traduz("CNPJ/CPF")?></th>
		<th><?=traduz("I.E.")?></th>
		<th><?=traduz("Fone")?></th>
		<?php if ($login_fabrica == 175){ ?>
			<th><?=traduz("Celular")?></th>
		<?php } else { ?>
			<th><?=traduz("Fax")?></th>
		<?php } ?>
		<th><?=traduz("Contato")?></th>
	</thead>
	<tr class="table_line">
		<td><?php

			$rev_cnpj = $cnpj;

			/** retirado filtro para esab internacional */
			if (in_array($login_fabrica,[180, 181, 182])) {

                $rev_cnpj  = explode("-", $cnpj);
                $rev_cnpj  = implode($rev_cnpj);
                $rev_cnpj  = explode(".", $rev_cnpj);
                $rev_cnpj  = implode($rev_cnpj);
                $rev_cnpj  = explode("/", $rev_cnpj);
                $rev_cnpj  = implode($rev_cnpj);
            }

            echo $rev_cnpj;

			?>&nbsp;
		</td>
		<td><?= $ie ?></td>
		<td><?= $fone ?></td>
		<td><?= $fax ?></td>
		<td><?= $contato ?></td>
	</tr>
	<thead class="titulo_coluna">
		<th colspan="2"><?=traduz("Código")?></th>
		<th colspan="5"><?=traduz("Razão Social")?></th>
	</thead>
	<tr class="table_line">
		<td colspan="2"><?= $codigo ?>&nbsp;</td>
		<td colspan="3"><?= $nome ?></td>
	</tr>
</table>
<br>
<table align='center' border='0' cellpadding="1" cellspacing="1" class='table table-bordered table-hover table-large'>
	<thead class="titulo_coluna">
		<th colspan="2"><?=traduz("Endereço")?></th>
		<th><?=traduz("Número")?></th>
		<th colspan="2"><?=traduz("Complemento")?></th>
	</thead>
	<tr class="table_line">
		<td colspan="2"><? echo $endereco ?>&nbsp;</td>
		<td><? echo $numero ?></td>
		<td colspan="2"><? echo $complemento ?></td>
	</tr>
	<thead class="titulo_coluna">
		<th colspan="2"><?=traduz("Bairro")?></th>
		<th><?=traduz("CEP")?></th>
		<th><?=traduz("Cidade")?></th>
		<th><?=traduz("Estado")?></th>
	</thead>
	<tr class="table_line">
		<td colspan="2"><? echo $bairro ?>&nbsp;</td>
		<td><? echo $cep ?></td>
		<td><? echo $cidade ?></td>
		<td><? echo $estado ?></td>
	</tr>
</table>
<br>
<table align='center' cellpadding="1" cellspacing="1" class='table table-bordered table-hover table-large'>
	<thead class="titulo_coluna">
		<th><?=traduz("E-mail")?></th>
		<th><?=traduz("Capital/Interior")?></th>
		<th><?=traduz("Tipo do Posto")?></th>

		<?php if($login_fabrica == 1){ ?>
			<th><?=traduz("Categoria do Posto")?></th>
			<th><?=traduz("Data Credenciamento")?></th>
		<?php } ?>
		<th><?=traduz("Desconto")?></th>
		<?php
			if($login_fabrica == 1){ ?>
				<th><?=traduz("Crédito bloqueado")?></th>
		<?		
			}

		?>
	</thead>
	<tr class="table_line">
		<td>
			<? echo $email ?>
		</td>
		<td>
			<? echo ucwords(strtolower($capital_interior));?>
		</td>
		<td>
			<? echo $tipo_posto_descricao; ?>
		</td>
<!--
		<td>
			<select name='pedido_em_garantia' size='1'>
				<option value=''></option>
				<option value='t' <? if ($pedido_em_garantia == "t") echo " selected "; ?> >Sim</option>
				<option value='f' <? if ($pedido_em_garantia == "f") echo " selected "; ?> >Não</option>
			</select>
		</td>
 -->
 		<?php
 			if($login_fabrica == 1){
 				$sqlCred = " SELECT TO_CHAR(tbl_credenciamento.data, 'DD/MM/YYYY') AS data_credenciamento
				 					FROM tbl_credenciamento
				 					WHERE fabrica = $login_fabrica
				 					AND posto = $posto
				 					AND status = 'CREDENCIADO'
				 					ORDER BY credenciamento DESC LIMIT 1";
				$resCred = pg_query($con, $sqlCred);

				if(pg_num_rows($resCred) > 0){
					$data_credenciamento = pg_fetch_result($resCred, 0, 'data_credenciamento');
				}
		?>
				<td><?=$categoria_posto?></td>
	 			<td><?=$data_credenciamento?></td>
		<?php
 			}
 		?>

		<td><? echo (is_numeric($desconto)) ? "$desconto %" : '&ndash;' ?></td>
		<?php
			if($login_fabrica == 1){
				echo "<td>$bloqueados</td>";
			}

		?>
	</tr>
	</tr>
</table>
<br>

<table align='center' cellpadding="1" cellspacing="1" class='table table-bordered table-hover table-large'>
	<thead class="titulo_coluna">
		<th colspan="2"><?=traduz("Nome Fantasia")?></th>

		<?if ($login_fabrica==1){ ?>
			<th><?=traduz("Senha")?></th>
		<?}?>
		<th><?=traduz("Transportadora")?></th>
		<th><?=traduz("Região Suframa")?></th>
		<th><?=traduz("Item Aparência")?></th>

	</thead>
	<tr class="table_line">
		<td colspan="2">
			<? echo $nome_fantasia ?>&nbsp;
		</td>
		<?
		if ($login_fabrica==1){
			echo "<td>$senha</td>";
		}
		?>
		<td align='center'>
			<? echo $transportadora_nome; ?>
		</td>
		<td>
			<?if ($suframa == 't') echo traduz("SIM");?>
			<?if ($suframa == 'f' or strlen($suframa) == 0) echo traduz("NÃO");?>
		</td>
		<td>
			<?if ($item_aparencia == 't') echo traduz("SIM");?>
			<?if ($item_aparencia <> 't') echo traduz("NÃO");?>
		</td>

	</tr>
	<thead class="titulo_coluna">
		<th colspan="100%" class="titulo_coluna" style="text-align: center;"><?=traduz("Observações")?> </th>
	</thead>
	<tr class="table_line">
		<td colspan="100%">
			<? echo $obs ?>&nbsp;
		</td>
	</tr>
</table>

<?
if ($login_fabrica == 117) {
    $sql = "SELECT DISTINCT
                  tbl_linha.linha,
                  tbl_linha.nome AS linha_nome,
                  tbl_tabela.sigla_tabela ,
                  tbl_posto.nome_fantasia
                FROM tbl_linha
                JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                      JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
                      JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha
                      LEFT JOIN tbl_tabela ON tbl_posto_linha.tabela = tbl_tabela.tabela
                      LEFT JOIN tbl_posto ON tbl_posto_linha.distribuidor = tbl_posto.posto
                WHERE tbl_linha.fabrica = {$login_fabrica} AND tbl_linha.ativo IS TRUE AND tbl_posto_linha.posto = $posto
                ORDER BY tbl_linha.nome ";
}else{
	$sql = "SELECT  tbl_linha.nome AS linha_nome ,
					tbl_tabela.sigla_tabela ,
					tbl_posto.nome_fantasia
			FROM    tbl_posto_linha
			JOIN    tbl_linha ON tbl_posto_linha.linha = tbl_linha.linha
			LEFT JOIN tbl_posto ON tbl_posto_linha.distribuidor = tbl_posto.posto
			LEFT JOIN tbl_tabela ON tbl_posto_linha.tabela = tbl_tabela.tabela
			WHERE tbl_posto_linha.posto = $posto
			AND   tbl_linha.fabrica = $login_fabrica";
}
$res = pg_query($con,$sql);
?>
<table align='center' class='table table-bordered table-hover table-large'>
	<thead class='titulo_coluna'>
		<th><?=($login_fabrica == 117) ? traduz('Macro Família Atendida') : traduz('Linha Atendida'); ?></th>
		<th><?=traduz("Tabela")?></th>
		<th><?=traduz("Distribuidor")?></th>
	</thead>
<?
for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
	$nome = str_replace(array('ã','ç','ó','õ','é','í','ú'), array('Ã','Ç','Ó','Õ','É','Í','Ú'), pg_result($res,$i,linha_nome));
?>
	<tr class='table_line'>
	<td><?=strtoupper($nome); ?></td>
	<td class="tac"><?= pg_result($res,$i,sigla_tabela) ?></td>
<?	
	if (strlen(pg_result($res,$i,nome_fantasia)) > 0) {
?>		
		<td><?= pg_result($res,$i,nome_fantasia) ?></td>
<?		
	}else{
?>		
		<td class="tac"><b><?=traduz("Fábrica")?></b></td>
<?		
	}
?>	
	</tr>
<?	
}
?>
</table>
<?
if ($login_fabrica == 1) {

	if ($posto){
		$sql = "SELECT visivel FROM tbl_posto_condicao WHERE condicao = 62 AND posto = $posto";

		$res = pg_query($con,$sql);
		if (pg_numrows($res) > 0) {
			$pedido_em_garantia_finalidades_diversas = pg_result($res,0,visivel);
		}

	}?>
<br>
<table cellpadding="1" cellspacing="1" class='table table-bordered table-hover table-large' id="tab">
<thead class='titulo_tabela'>
	<th colspan='2' class='titulo_coluna'><?=traduz("Posto Pode Digitar")?>:</th>
</thead>
<TR  class="table_line">
	<TD align='center'><INPUT TYPE="checkbox" NAME="pedido_faturado" VALUE='t' <? if ($pedido_faturado == 't') echo ' checked ' ?>  ></TD>
	<TD align='left'><?=traduz("Pedido Faturado (Manual)")?></TD>
</TR>
<TR class="table_line">
	<TD align='center'><INPUT TYPE="checkbox" NAME="pedido_em_garantia" VALUE='t' <? if ($pedido_em_garantia == 't') echo ' checked ' ?> ></TD>
	<TD align='left'><?=traduz("Pedido em Garantia (Manual)")?></TD>
</TR>

<TR class="table_line">
	<TD align='center'><INPUT TYPE="checkbox" NAME="pedido_em_garantia_finalidades_diversas" VALUE='t' <? if ($pedido_em_garantia_finalidades_diversas == 't') echo ' checked ' ?> ></TD>
	<TD align='left'><?=traduz("Pedido de Garantia ( Finalidades Diversas)")?></TD>
</TR>

<TR class="table_line">
	<TD align='center'><INPUT TYPE="checkbox" NAME="coleta_peca" VALUE='t' <? if ($coleta_peca == 't') echo 'checked' ?>></TD>
	<TD align='left'><?=traduz("Coleta de Peças")?></TD>
</TR>
<TR class="table_line">
	<TD align='center'><INPUT TYPE="checkbox" NAME="reembolso_peca_estoque" VALUE='t' <? if ($reembolso_peca_estoque == 't') echo 'checked' ?> ></TD>
	<TD align='left'><?=traduz("Reembolso de Peça do Estoque (Garantia Automática)")?></TD>
</TR>

<TR class="table_line">
	<TD align='center'><INPUT TYPE="checkbox" NAME="digita_os" VALUE='t' <? if ($digita_os == 't') echo ' checked ' ?> ></TD>
	<TD align='left'><?=traduz("Digita OS")?> </TD>
</TR>
<TR class="table_line">
	<TD align='center'><INPUT TYPE="checkbox" NAME="prestacao_servico" VALUE='t' <? if ($prestacao_servico == 't') echo ' checked ' ?>  ></TD>
	<TD align='left'><?=traduz("Prestação de Serviço")?><br><font size='-2'>&nbsp;<?=traduz("Posto só recebe mão-de-obra")?>. <?=traduz("Peças são enviadas sem custo")?>.</font></TD>
</TR>
<TR class="table_line">
	<TD align='center'>
	<INPUT TYPE="checkbox" NAME="pedido_via_distribuidor" VALUE='t'

	<?php
	   if (strlen($posto) > 0) {
		if ($pedido_via_distribuidor == 't') echo ' checked '; else echo '';
		$sql = "SELECT		tbl_tipo_posto.distribuidor
				FROM		tbl_tipo_posto
				LEFT JOIN	tbl_posto_fabrica USING (tipo_posto)
				WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
				AND         tbl_posto_fabrica.posto = $posto;";
		$res = pg_query($con,$sql);

		if (@pg_result($res,0,0) == 't') echo ''; else echo 'disabled';
	}?>
	>
	</TD>
	<TD align='left'><?=traduz("Pedido via Distribudor")?></TD>
</TR>
</table>
<?
}
}
?>

<?
if($login_fabrica ==1 and strlen($posto)>0) { // HD 50933?>
	<br>
	<?php

	$condicoes = array();

	$sql_black_posto_condicao = "
		SELECT DISTINCT(id_condicao) as condicao
		FROM tbl_black_posto_condicao
		WHERE posto = $posto
		AND id_condicao <> 62
		ORDER BY condicao
	";
	$res_black_posto_condicao = pg_query($con, $sql_black_posto_condicao);

	if (pg_num_rows($res_black_posto_condicao) > 0) {
		while ($fetch = pg_fetch_assoc($res_black_posto_condicao)) {
		    $condicoes[] = $fetch['condicao'];
		}
	} else {
    	$condicoes[] = "51";
	}

	$sql = "
		SELECT tipo_posto, categoria
		FROM tbl_posto_fabrica
		WHERE posto = $posto
		AND fabrica = $login_fabrica;
	";

	$res = pg_query($con, $sql);
	$tipo_posto = pg_fetch_result($res, 0, "tipo_posto");
	$categoria = pg_fetch_result($res, 0, "categoria");
	$aux_data = date("j");

	//HD 100300 - Pedido de promoção automatica
	$abrir = fopen(__DIR__ . "/../bloqueio_pedidos/libera_promocao_black.txt", "r");
	$ler = fread($abrir, filesize(__DIR__ . "/../bloqueio_pedidos/libera_promocao_black.txt"));
	fclose($abrir);
	$conteudo_p = explode(";;", $ler);
	$data_inicio_p = $conteudo_p[0];
	$data_fim_p    = $conteudo_p[1];
	$comentario_p  = $conteudo_p[2];
	$promocao = "f";
	if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("$data_fim_p"))) {
		if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("$data_inicio_p"))) {
			$promocao = "t";
		}
	}

	$sql_tipo_posto_condicao = "
   		SELECT DISTINCT( tbl_condicao.condicao) AS condicao,
				tbl_condicao.promocao AS promocao,
				tbl_condicao.dia_inicio,
				tbl_condicao.dia_fim
		FROM tbl_condicao
			JOIN tbl_tipo_posto_condicao ON tbl_tipo_posto_condicao.condicao = tbl_condicao.condicao
		WHERE ((tbl_condicao.dia_inicio <= $aux_data AND tbl_condicao.dia_fim >= $aux_data) OR (tbl_condicao.dia_inicio IS NULL AND tbl_condicao.dia_fim IS NULL))
		AND (tbl_tipo_posto_condicao.tipo_posto = $tipo_posto OR tbl_tipo_posto_condicao.categoria = '$categoria')
		AND tbl_condicao.visivel IS TRUE
		ORDER BY tbl_condicao.condicao
   	";
    $res_tipo_posto_condicao = pg_query($con, $sql_tipo_posto_condicao);
    $aux_total = pg_num_rows($res_tipo_posto_condicao);

    for ($y = 0; $y < $aux_total; $y++) {
    	$tbl_promocao = pg_fetch_result($res_tipo_posto_condicao, $y, 'promocao');
    	$dia_inicio   = pg_fetch_result($res_tipo_posto_condicao, $y, 'dia_inicio');
    	$dia_fim      = pg_fetch_result($res_tipo_posto_condicao, $y, 'dia_fim');
    	$aux_promocao = $promocao;

    	if ($tbl_promocao == 't' && strlen($dia_inicio) == 0 && strlen($dia_fim) == 0) {
			$aux_promocao = 't';
		}

    	if ($tbl_promocao == 't' && $aux_promocao == 'f') {
    		continue;
    	} else {
    		$aux_condicao = pg_fetch_result($res_tipo_posto_condicao, $y, 'condicao');
    		$condicoes[] = $aux_condicao;
    	}
    }
	$condicoes = array_unique ($condicoes);

	if (!empty($condicoes)) {
	?>	
		<table class='table table-bordered table-hover table-large' align='center' border='1' cellpadding='1' cellspacing='1'>
			<thead class='titulo_tabela'>
				<th COLSPAN='2'><?=traduz("Condições do Posto")?></th>
			</thead>
		<thead class='titulo_coluna'>
			<th><?=traduz("Código")?></th>
			<th><?=traduz("Condição")?></th>
		</thead>
		<?

		foreach ($condicoes as $xcondicao) {
            $sql = "
                SELECT codigo_condicao, descricao, visivel
                FROM tbl_condicao
                WHERE condicao = $xcondicao
                LIMIT 1
            ";
            $res       = pg_query($con, $sql);
            $codigo_condicao = pg_fetch_result($res, 0, 'codigo_condicao');
            $descricao = pg_fetch_result($res, 0, 'descricao');
            $visivel   = pg_fetch_result($res, 0, 'visivel');

            ?>
			<TR>
				<TD class="tac" nowrap><?= $codigo_condicao ?>
			</TD>
			<TD align='center' nowrap><?= $descricao ?></TD>
			<?
        }

		?>
		</table><br>
<?		
	}

}
?>

<p>

<? include "rodape.php"; ?>