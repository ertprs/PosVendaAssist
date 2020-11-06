<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include '../helpdesk/mlg_funciones.php';

$layout_menu = "cadastro";
$title = traduz("CADASTRAMENTO DE TIPO DE POSTOS");
//include 'cabecalho.php';
include 'cabecalho_new.php';

if ($novaTelaOs) {
    $msg_aviso = traduz('A Manutenção do Tipo de Posto é feita pelo Suporte Telecontrol.');
}

// Parâmetros visualização
$fabrica_tipo_distribuidor = !in_array($login_fabrica, array(148,161,163,191));
$fabrica_tipo_locadora     = in_array($login_fabrica, array(148));
$fabrica_tipo_montadora    = in_array($login_fabrica, array(148));
$fabrica_tipo_revenda      = in_array($login_fabrica, array(148, 160,163,191)) or $replica_einhell;
$fabrica_tipo_gera_pedido   = in_array($login_fabrica, array(3, 131, 137, 140, 141, 144, 157, 161, 163, 164, 165));
$fabrica_desconto_campo	   = in_array($login_fabrica, array(148, 160,163)) or $replica_einhell;

if(isset($novaTelaOs) && !in_array($login_fabrica, array(157))){
	$display = "style='display:none;'";
}else{
	$display = '';
}

/**
 *  Elemento input check
 *  $label       Obrigatório. Nome do input que irá no <label />
 *  $name        Obrigatório. attr name (tabmém ID, se não informado depois)
 *  $defVal      Obrigatório. valor para comparar com $value para saber se stará checked ou não
 *  $value       attr value, default 't'
 *  $id          se for diferente do $name, default NULL
 *  $action      Obrigatório. true para echo, false para return.
 *  $extraClass  classes extra para o label
 **/
	$checkBox = function($label, $name, $value, $defVal='t', $id=null, $action=true, $extraClass='') {
		$id = ($id===null) ? $name : $id;
		$checked = ($value == $defVal) ? "checked='checked'" : '';

		$html = "
            <label class='checkbox inline' for='$id'>
					<input type='checkbox' id='$id' name='$name' $checked value='$defVal' > $label
            </label>";
		if ($action):
			echo $html;
		else:
			return $html;
		endif;
	};

$plugins = array('dataTable');

include 'plugin_loader.php';

if (strlen($_GET['tipo_posto']) > 0)  $tipo_posto = trim($_GET['tipo_posto']);
if (strlen($_POST['tipo_posto']) > 0) $tipo_posto = trim($_POST['tipo_posto']);

if (strlen($_POST['acrescimo_tabela_base']) > 0)       $acrescimo_tabela_base       = trim($_POST['acrescimo_tabela_base']);
if (strlen($_POST['acrescimo_tabela_base_venda']) > 0) $acrescimo_tabela_base_venda = trim($_POST['acrescimo_tabela_base_venda']);
if (strlen($_POST['tx_administrativa_garantia']) > 0)  $tx_administrativa_garantia  = trim($_POST['tx_administrativa_garantia']);
if (strlen($_POST['tx_administrativa']) > 0)           $tx_administrativa = trim($_POST['tx_administrativa']);
if (strlen($_POST['desconto_5estrela']) > 0)           $desconto_5estrela = trim($_POST['desconto_5estrela']);
if (strlen($_POST['desconto2']) > 0)                   $desconto2         = trim($_POST['desconto2']);
if (strlen($_POST['desconto3']) > 0)                   $desconto3         = trim($_POST['desconto3']);
if (strlen($_POST['montadora']) > 0)                   $montadora         = trim($_POST['montadora']);
if (strlen($_POST['revenda']) > 0)                     $revenda           = trim($_POST['revenda']);
if (strlen($_POST['locadora']) > 0)                    $locadora          = trim($_POST['locadora']);

$titulo_form = (strlen($tipo_posto)) ? traduz('Alteração de Cadastro') : traduz('Cadastro');

if (strlen($_POST['btnacao']) > 0) {
	$btnacao = trim($_POST['btnacao']);
}

if ($btnacao == 'deletar' and strlen($tipo_posto) > 0) {
	$res = pg_query($con,'BEGIN TRANSACTION');

	$sql = "DELETE FROM tbl_tipo_posto
			WHERE  fabrica = $login_fabrica
			AND    tipo_posto = $tipo_posto";
	$res = pg_query($con,$sql);
	$msg_erro = pg_last_error($con);

	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_query($con,"COMMIT TRANSACTION");

//HD 956		header ("Location: $PHP_SELF");
//HD 956 	exit;
	} else {
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$tipo_posto              = $_POST['tipo_posto'];
		$codigo                  = $_POST['codigo'];
		$descricao               = $_POST['descricao'];
		$distribuidor            = $_POST['distribuidor'];

		if ($acrescimo_tabela_base == 't')      $acrescimo       = $_POST['acrescimo'];
		if ($acrescimo_tabela_base_base == 't') $acrescimo_venda = $_POST['acrescimo_venda'];

		$res = pg_query($con,'ROLLBACK TRANSACTION');
	}
}

if ($btnacao == "gravar") {

	if (strlen($msg_erro) == 0 ) {
		if (strlen($_POST["descricao"]) > 0) {
			$aux_descricao = "'". trim($_POST["descricao"]) ."'";
			$aux_descricao = str_replace('"', "'", $aux_descricao);
		} else {
			$msg_erro = traduz("Preencha todos os campos obrigatórios");
			$msg_erro_input = array('descricao' => true);
		}
	}

	$aux_codigo = trim ($_POST ['codigo']);

	if($fabrica_desconto_campo){
		$descontos = $_POST["desconto"];
		$descontos = str_replace(',','.',$descontos);
		$desconto = "{ $descontos }";
	}

	//Retirada a obrigatoriedade do código do posto, pois a maioria dos postos estavam sem.
	// Correção, HD 379638...
	 if (strlen ($aux_codigo) == 0) {
		$aux_codigo = "null";
		// $msg_erro = "Favor informar o código do Tipo de Posto";
	} else {
		$aux_codigo = "'" . $aux_codigo . "'";
	}

	$distribuidor = $_POST['distribuidor'];
	$aux_distribuidor = (strlen($distribuidor) == 0) ? 'f':$distribuidor;

	$locadora = $_POST['locadora'];
	$aux_locadora = (strlen($locadora) == 0) ? 'f':$locadora;

	$montadora = $_POST['montadora'];
	$aux_montadora = (strlen($montadora) == 0) ? 'f':$montadora;

	$revenda = $_POST['revenda'];
	$aux_revenda = (strlen($revenda) == 0) ? 'f':$revenda;

	$ativo = $_POST['ativo'];
	$aux_ativo = (strlen($ativo) == 0) ? 'f':$ativo;

	$posto_interno = $_POST['posto_interno'];
	$aux_posto_interno = (strlen($posto_interno) == 0) ? 'f':$posto_interno;

	$descontos = '{}';

	if ($acrescimo_tabela_base == 't') {

		$acrescimo       = trim($_POST['acrescimo']);
		$acrescimo_venda = trim($_POST['acrescimo_venda']);

		if ($login_fabrica == 1){ #HD 379638
			if (strlen ($acrescimo_tabela_base) == 0)       $acrescimo_tabela_base = "0,00";
			if (strlen ($acrescimo_tabela_base_venda) == 0) $acrescimo_tabela_base_venda = "0,00";
			if (strlen ($tx_administrativa) == 0)           $tx_administrativa = "0,00";
			if (strlen ($desconto_5estrela) == 0)           $desconto_5estrela = "0,00";
			if (strlen ($desconto2) == 0)                   $desconto2 = "0,00";
			if (strlen ($desconto3) == 0)                   $desconto3 = "0,00";
			if (strlen ($tx_administrativa_garantia) == 0)  $tx_administrativa_garantia = "0,00";
		}else{
			if (strlen ($acrescimo_tabela_base) == 0)       $msg_erro = traduz("Por favor, informar o acréscimo sobre a tabela base para este tipo de posto");
			if (strlen ($acrescimo_tabela_base_venda) == 0) $msg_erro = traduz("Por favor, informar o acréscimo sobre a tabela base para venda para este tipo de posto");
			if (strlen ($tx_administrativa) == 0)           $msg_erro = traduz("Por favor, informar taxa administrativa para este tipo de posto");
			if (strlen ($desconto_5estrela) == 0)           $msg_erro = traduz("Por favor, informar o desconto para este tipo de posto");
		}


		$xacrescimo = str_replace('.','',$acrescimo);
		$xacrescimo = str_replace(",",".",$xacrescimo);
		$xacrescimo = $xacrescimo / 100 + 1;

		$xacrescimo_venda = str_replace('.','',$acrescimo_venda);
		$xacrescimo_venda = str_replace(',','.',$xacrescimo_venda);
		$xacrescimo_venda = $xacrescimo_venda / 100 + 1;

		$xtx_administrativa = str_replace('.','',$tx_administrativa);
		$xtx_administrativa = str_replace(',','.',$xtx_administrativa);
		$xtx_administrativa = $xtx_administrativa / 100 + 1;

		$xtx_administrativa_garantia = str_replace('.','',$tx_administrativa_garantia);
		$xtx_administrativa_garantia = str_replace(',','.',$xtx_administrativa_garantia);
		$xtx_administrativa_garantia = $xtx_administrativa_garantia / 100 + 1;

		$xdesconto_5estrela = str_replace('.','',$desconto_5estrela);
		$xdesconto_5estrela = str_replace(',','.',$xdesconto_5estrela);
		$xdesconto_5estrela = (100 - $xdesconto_5estrela) / 100;

		$desconto2 = str_replace('.','',$desconto2);
		$desconto2 = str_replace(',','.',$desconto2);
		$desconto2 = (100 - $desconto2) / 100;

		$desconto3 = str_replace('.','',$desconto3);
		$desconto3 = str_replace(',','.',$desconto3);
		$desconto3 = (100 - $desconto3) / 100;

		$descontos = "{ $desconto2 , $desconto3 }";

	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"BEGIN TRANSACTION");

		if ($descontos == '{}'):
			$descontos = 'NULL';
		else:
			$descontos = "'$descontos'";
		endif;

		if($fabrica_desconto_campo){
			$descontos = "'$desconto'";
		}

		if (strlen($tipo_posto) == 0) {

			$sql = "SELECT codigo FROM tbl_tipo_posto where codigo = '$codigo' and fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0 ) {
				$msg_erro = traduz("Esse código já está cadastrado em um Tipo de Posto");

			} else {

				###INSERE NOVO REGISTRO
				$sql = "INSERT INTO tbl_tipo_posto (
							fabrica              ,
							codigo               ,
							descricao            ,
							distribuidor         ,
							ativo                ,
							locadora             ,
							montadora            ,
							tipo_revenda         ,
							posto_interno";

				if($fabrica_desconto_campo){
					$sql .= ", descontos ";
				}


				if ($acrescimo_tabela_base == 't') $sql .= ", acrescimo_tabela_base, acrescimo_tabela_base_venda, tx_administrativa, desconto_5estrela, descontos,  tx_administrativa_garantia";

				$sql .= ") VALUES (
							$login_fabrica       ,
							$aux_codigo          ,
							$aux_descricao       ,
							'$aux_distribuidor'  ,
							'$aux_ativo'         ,
							'$aux_locadora'      ,
							'$aux_montadora'     ,
							'$aux_revenda'       ,
							'$aux_posto_interno' ";
				if($fabrica_desconto_campo){
					$sql .= ", $descontos ";
				}

				if ($acrescimo_tabela_base == 't') $sql .= ", $xacrescimo, $xacrescimo_venda, $xtx_administrativa, $xdesconto_5estrela, $descontos, $xtx_administrativa_garantia ";

				$sql .= ")";

				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
				$msg_success = traduz('Gravado com sucesso!');

			}
		} else {
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_tipo_posto SET
						   codigo        = $aux_codigo   ,
						   descricao     = $aux_descricao,
						   distribuidor  = '$aux_distribuidor',
						   locadora      = '$aux_locadora',
						   montadora     = '$aux_montadora',
						   tipo_revenda  = '$aux_revenda',
						   ativo         = '$aux_ativo',
						   posto_interno = '$aux_posto_interno',
						   descontos     = $descontos ";

			if ($acrescimo_tabela_base == 't') {
                $sql .= ",
                           acrescimo_tabela_base       = $xacrescimo,
                           acrescimo_tabela_base_venda = $xacrescimo_venda,
                           tx_administrativa           = $xtx_administrativa,
                           desconto_5estrela           = $xdesconto_5estrela,
                           tx_administrativa_garantia  = $xtx_administrativa_garantia ";
			}
			$sql .= "WHERE fabrica    = $login_fabrica
					   AND tipo_posto = $tipo_posto;";

			$res      = @pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
			$msg_success = traduz('Gravado com sucesso!');
		}

			$descontos = str_replace('{','',$descontos);
			$descontos = str_replace('}','',$descontos);
			$descontos = str_replace("'",'',$descontos);
			$descontos = str_replace(" ",'',$descontos);


	}
	//echo nl2br($sql); exit;
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_query($con,"COMMIT TRANSACTION");


		if($login_fabrica == 1 AND $btnacao == "gravar"){
			// HD 21333 33129
			$email_origem  = "helpdesk@telecontrol.com.br";
			$email_destino = "helpdesk@telecontrol.com.br, paulo@telecontrol.com.br";
			$assunto       = "Alteração ou Inclusão de novo tipo do posto";

			$corpo.="<br>O admin $login_admin-  alterou ou cadastrou novo tipo do posto com descricao - $aux_descricao - Tem que verificar se a rotina de calculo de peças na funcao calcula_os_item. ";

			$body_top = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";

			@mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top ");

		}
		//HD 956		header ("Location: $PHP_SELF");
		//HD 956		exit;
	} else {
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$tipo_posto    = $_POST['tipo_posto'];
		$codigo        = $_POST['codigo'];
		$descricao     = $_POST['descricao'];
		$distribuidor  = $_POST['distribuidor'];
		$locadora      = $_POST['locadora'];
		$montadora     = $_POST['montadora'];
		$revenda       = $_POST['revenda'];
		$ativo         = $_POST['ativo'];
		$posto_interno = $_POST['posto_interno'];

		$res = pg_query($con,'ROLLBACK TRANSACTION');
	}

}
###CARREGA REGISTRO
if ($btnacao != 'gravar') {
	if (strlen($tipo_posto) > 0) {

		$sql = "SELECT  codigo                                           ,
						descricao                                        ,
						acrescimo_tabela_base       as acrescimo         ,
						acrescimo_tabela_base_venda as acrescimo_venda   ,
						tx_administrativa                                ,
						tx_administrativa_garantia                       ,
						distribuidor                                     ,
						desconto_5estrela                                ,
						descontos[1] AS desconto2                        ,
						descontos[2] AS desconto3                        ,
						locadora                                         ,
						montadora                                        ,
						tipo_revenda                                     ,
						ativo                                            ,
						posto_interno                                    ,";
				if($fabrica_desconto_campo){
					$sql .= "
						descontos[1] ";
				}else{
					$sql .= "
						descontos ";
				}

				$sql .= "
				FROM    tbl_tipo_posto
				WHERE   fabrica    = $login_fabrica
				AND     tipo_posto = $tipo_posto;";

		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0 ) {

			$codigo            = trim(pg_fetch_result($res, 0, 'codigo'));
			$descricao         = trim(pg_fetch_result($res, 0, 'descricao'));
			$acrescimo         = trim(pg_fetch_result($res, 0, 'acrescimo'));
			$acrescimo_venda   = trim(pg_fetch_result($res, 0, 'acrescimo_venda'));
			$tx_administrativa = trim(pg_fetch_result($res, 0, 'tx_administrativa'));
			$desconto_5estrela = trim(pg_fetch_result($res, 0, 'desconto_5estrela'));
			$distribuidor      = trim(pg_fetch_result($res, 0, 'distribuidor'));
			$ativo             = trim(pg_fetch_result($res, 0, 'ativo'));
			$locadora          = trim(pg_fetch_result($res, 0, 'locadora'));
			$montadora         = trim(pg_fetch_result($res, 0, 'montadora'));
			$revenda           = trim(pg_fetch_result($res, 0, 'tipo_revenda'));
			$posto_interno     = trim(pg_fetch_result($res, 0, 'posto_interno'));
			$desconto2     	   = trim(pg_fetch_result($res, 0, 'desconto2'));

			if($fabrica_desconto_campo){
				$desconto_tipo    	   = trim(pg_fetch_result($res, 0, 'descontos'));
			}

			$desconto3     	   = trim(pg_fetch_result($res, 0, 'desconto3'));
			$tx_administrativa_garantia = trim(pg_fetch_result($res, 0, 'tx_administrativa_garantia'));

			//incluida rotina para recuperar o acrescimo sobre tabela
			$xacrescimo = $acrescimo * 100 - 100;
			//$xacrescimo = str_replace('.',',',$xacrescimo);
			$xacrescimo = number_format($xacrescimo,2,',','.');

			//Rotina de inclusao de acrescimo nas vendas
			$xacrescimo_venda = $acrescimo_venda * 100 - 100;
			//$xacrescimo_venda = str_replace('.',',',$xacrescimo_venda);
			$xacrescimo_venda = number_format($xacrescimo_venda,2,',','.');

			//Rotina sobre taxa administrativa sobre troca de pecas
			$xtx_administrativa = $tx_administrativa *  100 - 100;
			//$xtx_administrativa = str_replace('.',',',$xtx_administrativa);
			$xtx_administrativa = number_format($xtx_administrativa,2,',','.');

			$descontos = str_replace('{','',$descontos);
			$descontos = str_replace('}','',$descontos);

			//Rotina de inclusao de desconto 5 estrelas
			$xdesconto_5estrela = 100 - $desconto_5estrela * 100;
			//$xdesconto_5estrela = str_replace('.',',',$xdesconto_5estrela);
			$xdesconto_5estrela = number_format($xdesconto_5estrela,2,',','.');

			$xtx_administrativa_garantia = $tx_administrativa_garantia *  100 - 100;
			//$xtx_administrativa_garantia = str_replace('.',',',$xtx_administrativa_garantia);
			$xtx_administrativa_garantia = number_format($xtx_administrativa_garantia,2,',','.');

			$desconto2 = 100 - $desconto2 * 100;
			$desconto2 = str_replace('.',',',$desconto2);

			$desconto3 = 100 - $desconto3 * 100;
			$desconto3 = str_replace('.',',',$desconto3);



		}


	} else if (strlen($msg_erro)>0) { #HD 379638


			//incluida rotina para recuperar o acrescimo sobre tabela

			$xacrescimo = $acrescimo;


			//Rotina de inclusao de acrescimo nas vendas
			$xacrescimo_venda = $acrescimo_venda;


			//Rotina sobre taxa administrativa sobre troca de pecas
			$xtx_administrativa = $tx_administrativa;


			//Rotina de inclusao de desconto 5 estrelas
			$xdesconto_5estrela = $desconto_5estrela;


	}
} else {

	if ($tipo_posto == '' && $msg_erro == '') {
		$codigo            = '';
		$descricao         = '';
		$acrescimo         = '';
		$acrescimo_venda   = '';
		$tx_administrativa = '';
		$desconto_5estrela = '';
		$distribuidor      = '';
		$ativo             = '';
		$posto_interno     = '';
		$desconto2     	   = '';
		$descontos     	   = '';
		$desconto3     	   = '';
		$tx_administrativa_garantia = '';

		//incluida rotina para recuperar o acrescimo sobre tabela
		$xacrescimo = '';


		//Rotina de inclusao de acrescimo nas vendas
		$xacrescimo_venda = '';
		$xacrescimo_venda = '';

		//Rotina sobre taxa administrativa sobre troca de pecas
		$xtx_administrativa = '';
		$xtx_administrativa = '';

		//Rotina de inclusao de desconto 5 estrelas
		$xdesconto_5estrela = '';
		$xdesconto_5estrela = '';

		$xtx_administrativa_garantia = '';
		$xtx_administrativa_garantia = '';

		$desconto2 = '';
		$desconto2 = '';

		$desconto3 = '';
		$desconto3 = '';
	}

}
?>

<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="js/jquery.maskmoney.js"></script>



<script type="text/javascript" language="javascript">

$(function(){

	$(".msk_porcentagem").maskMoney({symbol:"", decimal:",", thousands:'', precision:2, maxlength: 6});

	$('#btngravar').click(function(){
		console.log($('#btnacao').value);
		if($('#btnacao').value == '' || $('#btnacao').value == undefined){
			$('#btnacao').attr('value','gravar') ;
			$('#frm_tipoposto').submit();
		}else{
			alert ('<?=traduz("Aguarde submissão")?>')
		}
	});




	$('#descricao').blur(function(){
		if($(this).val() != '' && $(this).val() != undefined){
			$(this).parent().parent().removeClass('error');
		}
	});
});

</script>

<style type="text/css">
	table.bordasimples {border-collapse: collapse;}

	table.bordasimples tr td {
		border:1px solid #D9E2EF;
		font-size: 11px;
	}

	.subtitulo{
		background-color:#7092BE;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
</style>

<?php
if($msg_erro_input['descricao'] == true){
	$showAsError = 'error';
	unset($msg_erro_input['descricao']);
}

if (strlen($msg_aviso) > 0) {?>

        <div class="alert alert-warning">
            <h4><? echo $msg_aviso; ?></h4>
        </div>
<?php
}
if (strlen($msg_erro) > 0) {?>

        <div class="alert alert-error">
            <h4><? echo $msg_erro; ?></h4>
        </div>
<?php
} else if (strlen($msg_success) > 0) {?>
	<div class="alert alert-success">
            <h4><?=traduz('Gravado com sucesso')?></h4>
        </div>
	<!-- <TABLE  width='700px' align='center' border='0' cellspacing="1" cellpadding="0" class='Titulo'>
		<TR align='center'>
			<TD class='sucesso'>Gravado com sucesso!</TD>
		</TR>
	</TABLE> -->
	<?php
}?>

<div class="row" <?=$display?> >
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<form name="frm_tipoposto" <?=$display?> id='frm_tipoposto' method="post" action="<?=$PHP_SELF?>" class="form-inline tc_formulario">
	<input type="hidden" name="tipo_posto" value="<?=$tipo_posto?>" />
	<input type="hidden" name="acrescimo_tabela_base" value="<?=$acrescimo_tabela_base?>" />
	<input type="hidden" name="acrescimo_tabela_base_venda" value="<?=$acrescimo_tabela_base?>" />
	<input type="hidden" name="tx_administrativa" value="<?=$tx_administrativa?>" />

	<div class="titulo_tabela ">
        <?php echo $titulo_form; ?>
	</div>

	<br />

    <div class='row-fluid'>
    	<div class="span2"></div>
        <div class='span4'>
            <div class='control-group <?php echo $showAsError ?>'>
                <label class="control-label" for="data_inicial"><?=traduz('Tipo de Posto')?></label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class="asteristico">*</h5>
                        <input class="span12" type="text" id='descricao' name="descricao" value="<?=$descricao?>" size="30" maxlength="30">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class="control-label" for="data_inicial"><?=traduz('Código')?></label>
                <div class="controls controls-row">
                    <div class="span12">
                        <input class="span12" type="text" name="codigo" value="<?=$codigo?>" size="10" maxlength="6">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- adicinado para einhell -->
    <?php if($fabrica_desconto_campo){?>
    <div class='row-fluid'>
    	<div class="span2"></div>
		<div class='span3'>
			<div class='control-group'>
				<label class="control-label" for="data_inicial"><?=traduz('Desconto')?></label>
				<div class="controls controls-row">
					<div class="input-prepend input-append">
					  <input class='span12 msk_porcentagem' type="text" name="desconto" id="desconto_einhell"value="<?=$desconto_tipo?>" size="6" >
					  <span class="add-on">%</span>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<? } ?>

    <div class='row'>
    	<div class="span2"></div>
        <div class='span8' style="margin-left: 5px;">
            <?php if ($fabrica_tipo_distribuidor) echo $checkBox('Distribuidor', 'distribuidor', $distribuidor); ?>
            <?php if ($fabrica_tipo_locadora)     echo $checkBox('Locadora',     'locadora',     $locadora); ?>
            <?php if ($fabrica_tipo_montadora)    echo $checkBox('Montadora',    'montadora',    $montadora); ?>
            <?php if ($fabrica_tipo_revenda)      echo $checkBox('Revenda',      'revenda',      $revenda); ?>
            <?php if ($fabrica_tipo_gera_pedido)      echo $checkBox('Gera Pedido',      'gera_pedido',      $revenda); ?>
            <?php echo $checkBox('Posto Interno', 'posto_interno', $posto_interno); ?>
        </div>
    </div>
    <br />
    <div class="row">
    	<div class="span2"></div>
        <div class='span8' style="margin-left: 5px;">
            <?php echo $checkBox('Ativo', 'ativo', $ativo); ?>
        </div>
    </div>
    <br />
<?php
 if ($acrescimo_tabela_base == 't') { ?>

	<?if($login_fabrica != 1) { ?>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span3'>
				<div class='control-group'>
					<label class="control-label" for="data_inicial"><?=traduz('Acréscimo Tabela')?></label>
					<div class="controls controls-row">
						<div class="input-prepend input-append">
						  <input class='span12 msk_porcentagem' type="text" name="acrescimo" id="acrescimo" value="<?=$xacrescimo;?>" size="6" maxlength="5">
						  <span class="add-on">%</span>
						</div>
					</div>

				</div>
			</div>
			<div class='span3'>
				<div class='control-group'>
					<label class="control-label" for="data_inicial"><?=traduz('Acréscimo Tab. Venda')?></label>
					<div class="controls controls-row">
						<div class="input-prepend input-append">
						  <input class='span12 msk_porcentagem' type="text" name="acrescimo_venda" id="acrescimo_venda" value="<?=$xacrescimo_venda;?>" size="6" maxlength="5"> %
						  <span class="add-on">%</span>
						</div>
					</div>

				</div>
			</div>
		</div>
	<? } ?>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span3'>
			<div class='control-group'>
				<label class="control-label" for=""><?=traduz('Taxa Adm. Troca Peças')?></label>
				<div class="controls">
					<div class="input-prepend input-append">
					  <input class='span12 msk_porcentagem' type="text" name="tx_administrativa" id="tx_administrativa" value="<?=$xtx_administrativa;?>" size="6" maxlength="5">
					  <span class="add-on">%</span>
					</div>
				</div>
			</div>
		</div>
		<?php if($login_fabrica == 1){ ?>
			<div class='span3'>
				<div class='control-group'>
					<label class="control-label" for=""><?=traduz('Taxa Adm. Garantia')?> </label>
					<div class="controls controls-row">
						<div class="input-prepend input-append">
						  <input class='span12 msk_porcentagem' type="text" name="tx_administrativa_garantia" id="tx_administrativa_garantia" value="<?=$xtx_administrativa_garantia;?>" size="6" maxlength="5">
						  <span class="add-on">%</span>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>
		<div class='span4'>
			<div class='control-group'>
				<?php
				if($login_fabrica == 1){
					$titulo = traduz("Desconto 1");
				}else{
					$titulo = traduz("Desc. Esp. em Cálc. de Peças");
				}
				?>
				<label class="control-label" for="">
					<?php echo $titulo ?>
					<? if($login_fabrica != 1){ ?>
						<img src='imagens/help.png' title='<?=traduz("Utilizado Inicialmente para Postos 5 Estrelas")?>' />
					<? } ?></label>
				<div class="controls controls-row">
					<div class="input-prepend input-append">
					  <input class='span12 msk_porcentagem' type="text" name="desconto_5estrela" id="desconto_5estrela"value="<?=$xdesconto_5estrela;?>" size="6" >
					  <span class="add-on">%</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php if($login_fabrica == 1){ ?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span3'>
			<div class='control-group'>
				<label class="control-label" for="data_inicial"><?=traduz('Desconto')?> 2</label>
				<div class="controls controls-row">
					<div class="input-prepend input-append">
					  <input class='span12 msk_porcentagem' type="text" name="desconto2" id="desconto2"value="<?=$desconto2;?>" size="6" >
					  <span class="add-on">%</span>
					</div>
				</div>
			</div>
		</div>

		<div class='span3'>
			<div class='control-group'>
				<label class="control-label"><?=traduz('Desconto')?> 3</label>
				<div class="controls controls-row">
					<div class="input-prepend input-append">
					  <input class='span12 msk_porcentagem' type="text" name="desconto3" id="desconto3"value="<?=$desconto3;?>" size="6" >
					  <span class="add-on">%</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php
	}
}?>
	
	<br />
	
	<div class='row-fluid form-horizontal'>

		<div class='span12 tac' >
			<p>
				<input type='hidden' id='btnacao' name='btnacao' value=''>
				<input type="button" class='btn btn-default' value='<?=traduz("Gravar")?>' id='btngravar'  ALT='<?=traduz("Gravar formulário")?>'>
				<?php
				if($tipo_posto != ''){
				?>
					<input type="button" class='btn btn-warning' value='<?=traduz("Limpar")?>' onclick="window.location='<? echo $PHP_SELF ?>'; return false;" ALT='<?=traduz("Limpar campos")?>'>
				<?php
				}
				?>

			</p>
		</div>
	</div>
</form>

<div class='container'>
	<div class='alert' <?=$display?>>
		<h4><?=traduz('Para efetuar alterações clique na descrição do Tipo do Posto')?></h4>
	</div>
</div>

<div class='container'>
	<table id='tbl_tipo_posto' class='table table-striped table-bordered table-hover table-fixed' >
		<thead>
			<tr class="titulo_coluna">
				<th><?=traduz('Código')?></th>
				<th><?=traduz('Tipo do Posto')?></th>
				<?php
				if ($acrescimo_tabela_base == 't') {?>

					<?php if($login_fabrica != 1){ ?>
					<th><?=traduz('Acréscimo sobre Tabela')?></th>
					<th><?=traduz('Acréscimo sobre Tabela Venda')?></th>
					<?php } ?>
					<th class='span2'><?=traduz('Taxa Adm.')?></th>
					<?php if($login_fabrica == 1){ ?>
						<th class='span2'><?=traduz('Taxa Adm. Garantia')?></th>
					<?php } ?>

					<?php
					if($login_fabrica == 1){ ?>
						<th nowrap class='span1'><?=traduz('Desconto 1')?></th>
						<th nowrap class='span1'><?=traduz('Desconto 2')?></th>
						<th nowrap class='span1'><?=traduz('Desconto 3')?></th>
				<?php } else{
						echo "<th>".traduz("Desconto preço peça")."</th>";
					}
				}?>
				<?php if($fabrica_desconto_campo){ ?>
				<th><?=traduz('Desconto')?></th>
				<?php } ?>
                <?php if ($fabrica_tipo_distribuidor): ?>
				<th><?=traduz('Distribuidor')?></th>
                <?php endif; ?>
                <?php if ($fabrica_tipo_locadora): ?>
				<th><?=traduz('Locadora')?></th>
                <?php endif; ?>
                <?php if ($fabrica_tipo_montadora): ?>
				<th><?=traduz('Montadora')?></th>
                <?php endif; ?>
                <?php if ($fabrica_tipo_revenda): ?>
				<th><?=traduz('Revenda')?></th>
                <?php endif; ?>
                <?php if ($fabrica_tipo_gera_pedido): ?>
				<th><?=traduz('Gera Pedido')?></th>
                <?php endif; ?>
				<th><?=traduz('Status')?></th>
				<th><?=traduz('Posto Interno')?></th>
			</tr>
		</thead>
		<?php

		$sql = "SELECT  tipo_posto    ,
						descricao     ,
						codigo        ,
						acrescimo_tabela_base       as acrescimo         ,
						acrescimo_tabela_base_venda as acrescimo_venda   ,
						tx_administrativa                                ,
						tx_administrativa_garantia                       ,
						desconto_5estrela                                ,
						descontos[1] AS desconto2                        ,
						descontos[2] AS desconto3                        ,
						distribuidor                                     ,
						descontos,
						tipo_revenda                                     ,
						locadora                                         ,
						montadora                                        ,
						ativo                                            ,
						posto_interno
				   FROM tbl_tipo_posto
				  WHERE fabrica = $login_fabrica
				  ORDER BY descricao";

		$res0 = pg_query($con,$sql);
		$tot0 = pg_num_rows($res0);

        $img_ativo   = '<img src="imagens/status_verde.png"> ';
        $img_inativo = '<img src="imagens/status_vermelho.png">';

		if($tot0 > 50){
			$tipo_tabela = 'full';
		}else{
			$tipo_tabela = 'basic';
		}

		for ($y = 0 ; $y < $tot0; $y++) {

			$tipo_posto        = trim(pg_fetch_result($res0, $y, 'tipo_posto'));
			$codigo            = trim(pg_fetch_result($res0, $y, 'codigo'));
			$descricao         = trim(pg_fetch_result($res0, $y, 'descricao'));
			$ativo2            = trim(pg_fetch_result($res0, $y, 'ativo'));
			$posto_interno2    = trim(pg_fetch_result($res0, $y, 'posto_interno'));
			$acrescimo         = trim(pg_fetch_result($res0, $y, 'acrescimo'));
			$acrescimo_venda   = trim(pg_fetch_result($res0, $y, 'acrescimo_venda'));
			$tx_administrativa = trim(pg_fetch_result($res0, $y, 'tx_administrativa'));
			$tx_administrativa_garantia = trim(pg_fetch_result($res0, $y, 'tx_administrativa_garantia'));
			$desconto_5estrela = trim(pg_fetch_result($res0, $y, 'desconto_5estrela'));
			$distribuidor      = trim(pg_fetch_result($res0, $y, 'distribuidor'));
			$locadora          = trim(pg_fetch_result($res0, $y, 'locadora'));
			$montadora         = trim(pg_fetch_result($res0, $y, 'montadora'));
			$revenda           = trim(pg_fetch_result($res0, $y, 'tipo_revenda'));
			$desconto2         = trim(pg_fetch_result($res0, $y, 'desconto2'));
			$desconto3         = trim(pg_fetch_result($res0, $y, 'desconto3'));

			if($fabrica_desconto_campo){

				$descontos = trim(str_replace(array("{","}"), "", pg_fetch_result($res0, $y, 'descontos')));
				$descontos = (strlen($descontos) > 0) ? $descontos." %" : "0%";

			}

			$cor = ($y % 2 == 0) ? '#F1F4FA' : '#F7F5F0';

			if (strlen($acrescimo) > 0) {
				$xacrescimo = ($acrescimo - 1) * 100;
				//$xacrescimo = str_replace(".",",",$xacrescimo);
				$xacrescimo = number_format($xacrescimo,2,',','.');
			}
			if (strlen($acrescimo_venda) > 0){
				$xacrescimo_venda = ($acrescimo_venda - 1) * 100;
				//$xacrescimo_venda = str_replace(".",",",$xacrescimo_venda);
				$xacrescimo_venda = number_format($xacrescimo_venda,2,',','.');
			}
			if (strlen($tx_administrativa) > 0){
				$xtx_administrativa = ($tx_administrativa - 1) * 100;
				//$xtx_administrativa = str_replace(".",",",$xtx_administrativa);
				$xtx_administrativa = number_format($xtx_administrativa,2,',','.');
			}

			if (strlen($tx_administrativa_garantia) > 0){
				$xtx_administrativa_garantia = ($tx_administrativa_garantia - 1) * 100;
				//$xtx_administrativa_garantia = str_replace(".",",",$xtx_administrativa_garantia);
				$xtx_administrativa_garantia = number_format($xtx_administrativa_garantia,2,',','.');

			}else{
				$xtx_administrativa_garantia = 0;
			}

			if (strlen($desconto_5estrela) > 0){
				$xdesconto_5estrela = 100 - ($desconto_5estrela * 100);
				//$xdesconto_5estrela = str_replace(".",",",$xdesconto_5estrela);
				$xdesconto_5estrela = number_format($xdesconto_5estrela,2,',','.');

			}

			if (strlen($desconto2) > 0){
				$xdesconto2 = 100 - ($desconto2 * 100);
				//$xdesconto2 = str_replace(".",",",$xdesconto2);
				$xdesconto2 = number_format($xdesconto2,2,',','.');
			}else{
				$xdesconto2 = 0;
			}

			if (strlen($desconto3) > 0){
				$xdesconto3 = 100 - ($desconto3 * 100);
				//$xdesconto3 = str_replace(".",",",$xdesconto3);
				$xdesconto3 = number_format($xdesconto3,2,',','.');
			}else{
				$xdesconto3 = 0;
			}

			// booleans
			$x_ativo     = ($ativo2 == 't')       ? $img_ativo : $img_inativo;
			$x_distrib   = ($distribuidor == 't') ? $img_ativo : $img_inativo;
			$x_locadora  = ($locadora == 't')     ? $img_ativo : $img_inativo;
			$x_montadora = ($montadora == 't')    ? $img_ativo : $img_inativo;
			$x_revenda   = ($revenda == 't')      ? $img_ativo : $img_inativo;

			echo "<tr class='tac'>";
			echo "<td class='tal'><a title='".traduz("Descrição")."' href='$PHP_SELF?tipo_posto=$tipo_posto'>{$codigo}</a></td>";
			echo "<td class='tal'>";
			echo "<a title='".traduz("Descrição")."' href='$PHP_SELF?tipo_posto=$tipo_posto'>$descricao</a>";
			echo "</td>";
			if ($acrescimo_tabela_base == 't') {

				if($login_fabrica != 1){
					echo "<td title='".traduz("Acrescimo")."' class='tac'>";
					echo "$xacrescimo%";
					echo "</td>";
					echo "<td title='".traduz("Acrescimo de Venda")."' class='tac'>";
					echo "$xacrescimo_venda%";
					echo "</td>";
				}
				echo "<td title='".traduz("Taxa Administrativa")."' class='tac'>";
				echo "$xtx_administrativa%";
				echo "</td>";
				if($login_fabrica == 1){
					echo "<td title='".traduz("Taxa Administrativa de Garantia")."' class='tac'>";
					echo "$xtx_administrativa_garantia%";
					echo "</td>";
				}
				echo "<td title='".traduz("Desconto")." 1' class='tac'>";
				echo "$xdesconto_5estrela%";
				echo "</td>";

				if($login_fabrica == 1){

					echo "<td title='".traduz("Desconto"). "2' class='tac'>";
					echo "$xdesconto2%";
					echo "</td>";

					echo "<td title='".traduz("Desconto")." 3' class='tac'>";
					echo "$xdesconto3%";
					echo "</td>";
				}
			}
			if($fabrica_desconto_campo){
				echo "<td class='tac'>{$descontos}</td>";
			}
			if ($fabrica_tipo_distribuidor) {
				echo "<td title='".traduz("Distribuidor")."' class='tac' >$x_distrib</td>";
			}
			if ($fabrica_tipo_locadora) {
				echo "<td title='".traduz("Locadora")."' class='tac' >$x_locadora</td>";
			}
			if ($fabrica_tipo_montadora) {
				echo "<td title='".traduz("Montadora")."' class='tac' >$x_montadora</td>";
			}
			if ($fabrica_tipo_revenda) {
				echo "<td title='".traduz("Revenda")."' class='tac' >$x_revenda</td>";
			}
			if ($fabrica_tipo_gera_pedido) {
				echo "<td title='".traduz("Revenda")."' class='tac' >$</td>";
			}
			echo "<td title='".traduz("Ativo")."' nowrap class='tac'>$x_ativo</td>";
			echo "<td title='".traduz("Posto Interno")."' nowrap class='tac'>";
			if($posto_interno2 == 't'){
				echo "<img src='imagens/status_verde.png'> ";
			}else{
				echo "<img src='imagens/status_vermelho.png'>";
			}
			echo "</td>";
			echo "</tr>";
		}?>
	</table>
</div>

</form>

<div class='row-fluid'>
	<div class='span12'></div>
</div>


<script type="text/javascript">
	var tipo_tabela = '<?php echo $tipo_tabela ?>';
	if(tipo_tabela == "full"){
		$.dataTableLoad({
			table: "#tbl_tipo_posto"
		});
	}else{
		$.dataTableLoad({
			table: "#tbl_tipo_posto",
			type: "custom",
			config: [ "pesquisa" ]
		});
	}

</script>

<div class='container'>

<? include "rodape.php"; ?>
