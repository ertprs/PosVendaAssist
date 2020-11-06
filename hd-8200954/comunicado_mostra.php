<? // lorenzetti 19
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once 'funcoes.php';
include_once 'helpdesk/mlg_funciones.php';

if ($_GET["gera_contrato"]) {

	if (isFabrica(1)) {

		include "gera_contrato_posto.php";
		exit;

	}

}

$array_mostra_linha   = [
							'vista explodida',
							'manual instrucoes',
							'descritivo tecnico',
							'manual',
							'manual técnico',
							'alterações técnicas',
							'esquema elétrico',
							'manual de instruções / operações'
						];

$msg_erro = $_GET['erro'];

if (isFabrica(14)) {
	// SELECIONA AS FAMILIAS
	$sql = "SELECT familia FROM tbl_posto_linha WHERE posto = $login_posto";
	$res = pg_query ($con,$sql);
	$contador_res = pg_num_rows($res);
	$familia_posto = '';
	for($i=0; $i<$contador_res; $i++){
		if (strlen(pg_fetch_result ($res,$i,0))) {
			$familia_posto .= pg_fetch_result ($res,$i,0);
			$familia_posto .= ", ";
		}
	}
}
$familia_posto .= "0";

$btn_acao = (!empty($_POST["btn_acao"])) ? strtolower($_POST["btn_acao"]) : strtolower($_GET["btn_acao"]);

if (strlen($_POST["linha"]) > 0) $linha = $_POST["linha"];

if ($_POST['chk_opt1'])  $chk1 = $_POST['chk_opt1'];
if ($_POST['chk_opt2'])  $chk2 = $_POST['chk_opt2'];
if ($_POST['chk_opt3'])  $chk3 = $_POST['chk_opt3'];
if ($_POST['chk_opt4'])  $chk4 = $_POST['chk_opt4'];

if ($_GET['chk_opt1'])   $chk1 = $_GET['chk_opt1'];
if ($_GET['chk_opt2'])   $chk2 = $_GET['chk_opt2'];
if ($_GET['chk_opt3'])   $chk3 = $_GET['chk_opt3'];
if ($_GET['chk_opt4'])   $chk4 = $_GET['chk_opt4'];

$data_inicial       = getPost('data_inicial');
$data_final         = getPost('data_final');
$produto_referencia = getPost('produto_referencia');
$produto_nome       = getPost('produto_nome');
$linha              = getPost('linha');
$tipo               = getPost('tipo');
$descricao_pesq     = getPost('descricao');
$familia	    	= getPost('familia');
$ordem_producao     = getPost('ordem_producao');
$palavra_chave      = getPost('palavra_chave');

$acao = strtolower($_GET['acao']);

$sql_estado = "SELECT estado FROM tbl_posto WHERE posto = {$login_posto}";
$res_estado = pg_query($con, $sql_estado);
if (pg_num_rows($res_estado) > 0){
	$estado = pg_fetch_result($res_estado, 0, 'estado');
}


if ($acao && $btn_acao == "pesquisar") {
	if (!$tipo AND !in_array($login_fabrica, array(169,170))){
		if ($login_fabrica == 175){
			if (empty($ordem_producao)){
				$erro = traduz('tipo.do.comunicado.obrigatório', $con);
			}
		}else{
			$erro = traduz('tipo.do.comunicado.obrigatório', $con);
			$r = "tipo";

		}
	}

}

if (in_array($login_fabrica, array(169,170)) && (empty($data) && empty($tipo) && empty($familia))) {
	$erro = traduz("Para pesquisar sem data selecione o tipo e algum outro filtro");
}

if (strlen($erro==0)) {
	//Início Validação de Datas
	if ($acao && $btn_acao == "pesquisar") {
		if (empty($data_inicial) OR empty($data_final)){
		}
	}

	$nova_data_inicial = dateFormat($data_inicial, 'dmy', 'iso');
	$nova_data_final   = dateFormat($data_final,   'dmy', 'iso');
	if ($nova_data_final < $nova_data_inicial) {
		$erro = traduz('data.invalida', $con);
	}
} //Fim Validação de Datas

$title = mb_strtoupper(traduz('comunicados', $con) . ' ' . $login_fabrica_nome);
$layout_menu = "tecnica";

include __DIR__.'/cabecalho_new.php';


$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "price_format",
   "tooltip",
   "select2",
   "leaflet",
   "dataTable"
);

include __DIR__.'/admin/plugin_loader.php';


include_once S3CLASS;

if(in_array($login_fabrica, array(11,172))){
	$s3_11  = new anexaS3('ve', 11);
	$s3_172 = new anexaS3('ve', 172);
}else{
	$s3 = new anexaS3('ve', (int) $login_fabrica);
}

?>

<script type="text/javascript" src="<?php echo $url_base; ?>plugins/shadowbox/shadowbox.js"></script>
<link type="text/css" href="<?php echo $url_base; ?>plugins/shadowbox/shadowbox.css" rel="stylesheet" media="all">

<script type="text/javascript">

	var traducao = {
		tela_de_pesquisa:             '<?=traduz('tela.de.pesquisa', $con)?>',
		informar_parte_para_pesquisa: '<?=traduz('informar.toda.parte.informacao.para.realizar.pesquisa', $con)?>',
		pecas_nao_encontradas:        '<?=traduz('as.seguintes.pecas.nao.foram.encontradas', $con)?>',
		verificar_codigo_pecas:       '<?=traduz('favor.verificar.se.o.codigo.esta.correto', $con)?>',
		aguarde_submissao:			  '<?=traduz('aguarde.submissao', $con, $cook_idioma)?>',
	}

	$(function(){

		$.datepickerLoad(Array("data_final", "data_inicial"));

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			<?php if (in_array($login_fabrica, array(169,170))){ ?>
				var parametros_lupa_produto = ["posto", "ativo"];
				$.lupa($(this), parametros_lupa_produto);
			<?php }else{ ?>
				$.lupa($(this));
			<?php } ?>
		});

		$("#ordem_producao").numeric();

		$("a[name=prod_ve]").click(function () {
		    var attr = $(this).attr("rel").split("/");
		    var comunicado = attr[0];
	        var tipo = attr[1];

	        $("#before-"+comunicado).html("<em>aguarde...</em>");

	        $.ajaxSetup({
				async: true
	        });
	        $.get("verifica_s3_comunicado.php", { comunicado: comunicado,fabrica:"<?=$login_fabrica?>",tipo: tipo}, function (url) {
				if (url.length > 0) {
					Shadowbox.init();
					if (url.search(/.(pdf|xlsx?)/g) != -1) {
						window.open(url, "_blank");
					}else{
	                    Shadowbox.open({
							player  : "html",
							content : "<div style='overflow-y: scroll; width: 800px; height: 600px'><img src='"+url+"' style='width: 100%;'></div>",
							height: 600,
							width: 800
						});
					}
					$("#before-"+comunicado).html("");
				} else {
					$("#before-"+comunicado).html("");
                    alert('<?=traduz("Arquivo não encontrado!")?>');
                }
// if($t<>'lu')include "cabecalho.php";
// else        include "inc_lu_header.php";
	        });
	    });

	    $(".btn-lista-anexos").click(function(){

	    	let comunicado = $(this).attr("comunicado");

	    	Shadowbox.open({
	            content:    "exibe_anexos_boxuploader.php?comunicado="+comunicado,
	            player: "iframe",
	            title:      ('<?=traduz("Anexos do Comunicado")?>'),
	            width:  800,
	            height: 500
	        });
	    });

	    <?php if (in_array($login_fabrica, [203])) { ?> 
	    	$('#tipo option[value="ITB Informativo Técnico Brother"]').attr("selected", "selected");
	    <?php } ?> 
	});

	
	function retorna_produto (retorno) {
		$("input[name=produto_referencia]").val(retorno.referencia);
		$("input[name=produto_descricao]").val(retorno.descricao);
	}

	function MostraEsconde(conteudo){
		if ($('#texto-'+conteudo).is( ":visible" )) {
			$('#texto-'+conteudo).hide();
			$('#img-desc-'+conteudo).attr("src","imagens/sort_desc.gif");
		}else{
			$('#texto-'+conteudo).show();
			$('#img-desc-'+conteudo).attr("src","imagens/sort_asc.gif");
		}
		if ($('#texto2-'+conteudo).is( ":visible" )) {
			$('#texto2-'+conteudo).hide();
			$('#img-desc-'+conteudo).attr("src","imagens/sort_desc.gif");
		}else{
			$('#texto2-'+conteudo).show();
			$('#img-desc-'+conteudo).attr("src","imagens/sort_asc.gif");
		}
	}
</script>

<style type="text/css">

.chapeu {
	color: #0099FF;
	padding: 2px;
	margin-bottom: 4px;
	margin-top: 10px;
	background-image: url(http://img.terra.com.br/i/terramagazine/tracejado3.gif);
	background-repeat: repeat-x;
	background-position: bottom;
	font-size: 13px;
	font-weight: bold;
}
.menu {
	font-size: 11px;
}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 1.5em auto;
    border:1px solid #596d9b;
    padding: 2px 0;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.tipo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	background-color: #D9E2EF
}

.descricao {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	background-color: #FFFFFF
}

.mensagem {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #FFFFFF
}

.txt10Normal {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}

.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #D9E2EF;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
	text-transform: capitalize;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
</style>

<br>

<!-- MONTA ÁREA PARA EXPOSICAO DE COMUNICADO SELECIONADO -->
<?
	
	$cond_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_posto_fabrica.fabrica IN (11,172) " : " tbl_posto_fabrica.fabrica = $login_fabrica ";

	$sql2 = "SELECT tbl_posto_fabrica.codigo_posto           ,
					tbl_posto_fabrica.tipo_posto             ,
					tbl_posto_fabrica.pedido_em_garantia     ,
					tbl_posto_fabrica.pedido_faturado        ,
					tbl_posto_fabrica.digita_os              ,
					tbl_posto_fabrica.reembolso_peca_estoque
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   $cond_fabrica
			AND     tbl_posto.posto   = $login_posto ";


	$res2 = pg_query ($con,$sql2);

	if (pg_num_rows($res2) > 0) {
		$codigo_posto           = trim(pg_fetch_result($res2, 0, 'codigo_posto'));
		$tipo_posto             = trim(pg_fetch_result($res2, 0, 'tipo_posto'));
		$pedido_em_garantia     = pg_fetch_result($res2, 0, 'pedido_em_garantia');
		$pedido_faturado        = pg_fetch_result($res2, 0, 'pedido_faturado');
		$digita_os              = pg_fetch_result($res2, 0, 'digita_os');
		$reembolso_peca_estoque = pg_fetch_result($res2, 0, 'reembolso_peca_estoque');
	}

//if ((strlen($comunicado) > 0) && (pg_num_rows($res) > 0) and isset($_GET['comunicado']) && !isFabrica(161)) {
if (strlen($comunicado) > 0) {
	if (isFabrica(1)) {		//HD 10983
		$sql_cond1=" tbl_comunicado.pedido_em_garantia     IS NULL ";
		$sql_cond2=" tbl_comunicado.pedido_faturado        IS NULL ";
		$sql_cond3=" tbl_comunicado.digita_os              IS NULL ";
		$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS NULL ";

		if ($pedido_em_garantia     == "t") $sql_cond1 =" tbl_comunicado.pedido_em_garantia     IS NOT FALSE ";
		if ($pedido_faturado        == "t") $sql_cond2 =" tbl_comunicado.pedido_faturado        IS NOT FALSE ";
		if ($digita_os              == "t") $sql_cond3 =" tbl_comunicado.digita_os              IS TRUE ";
		if ($reembolso_peca_estoque == "t") $sql_cond4 =" tbl_comunicado.reembolso_peca_estoque IS TRUE ";
		$sql_cond_total = "AND ( $sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4) ";
	}

	$cond_fabrica_produto = (in_array($login_fabrica, array(11,172))) ? " fabrica_i IN(11,172)" : " fabrica_i = $login_fabrica ";
	$cond_fabrica_posto = (in_array($login_fabrica, array(11,172))) ? " fabrica IN (11,172) " : " fabrica = $login_fabrica ";

	$sql_cond_linha = "
						AND (tbl_comunicado.linha IN
								(
									SELECT tbl_linha.linha
									FROM tbl_posto_linha
									JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
									WHERE {$cond_fabrica_posto} 
										AND posto = $login_posto
								)
								OR (
										tbl_comunicado.comunicado IN (
											SELECT tbl_comunicado_produto.comunicado
											FROM tbl_comunicado_produto
											JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
											JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
											WHERE {$cond_fabrica_produto} 
												AND tbl_posto_linha.posto = $login_posto

										)
									AND tbl_comunicado.produto IS NULL
								)
								OR tbl_comunicado.produto in
								(
									SELECT tbl_produto.produto
								 	FROM tbl_produto
								 	JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha
								 	WHERE {$cond_fabrica_produto} 
								 		AND posto = $login_posto
								)
								OR tbl_comunicado.linha IS NULL
							)";

	$cond_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_comunicado.fabrica IN(11,172)" : " tbl_comunicado.fabrica = $login_fabrica ";

	$sql = "SELECT  tbl_comunicado.comunicado                        ,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.referencia ELSE tbl_produto.referencia END AS prod_referencia,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.descricao  ELSE tbl_produto.descricao  END AS prod_descricao,
					tbl_comunicado.descricao                         ,
					tbl_comunicado.mensagem                          ,
					tbl_comunicado.video     						 ,
					tbl_comunicado.tipo                              ,
					tbl_comunicado.extensao                          ,
					to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
			FROM    tbl_comunicado
			LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
			LEFT JOIN tbl_produto            ON tbl_produto.produto               = tbl_comunicado.produto
			LEFT JOIN tbl_produto prod       ON prod.produto                      = tbl_comunicado_produto.produto
			WHERE   {$cond_fabrica}
			AND    (tbl_comunicado.tipo_posto = $tipo_posto   OR tbl_comunicado.tipo_posto IS NULL)
			AND     ((tbl_comunicado.posto    = $login_posto) OR (tbl_comunicado.posto     IS NULL))
			AND     tbl_comunicado.comunicado = $comunicado
			AND    tbl_comunicado.ativo IS TRUE ";


	if (isFabrica($fabrica_multinacional) and $login_pais)  $sql .= " AND tbl_comunicado.pais = '$login_pais' ";
	//HD 10983
	if (isFabrica(1)) {
		$sql .=" $sql_cond_total ";
	}

	$sql.=" $sql_cond_linha ";

	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) == 0) {
		$msg_erro = traduz("comunicado.inexistente",$con);
	}else{
		$Xcomunicado          = trim(pg_fetch_result($res, 0, 'comunicado'));
		$referencia           = trim(pg_fetch_result($res, 0, 'prod_referencia'));
		$descricao            = trim(pg_fetch_result($res, 0, 'prod_descricao'));
		$comunicado_descricao = trim(pg_fetch_result($res, 0, 'descricao'));
		$comunicado_tipo      = trim(pg_fetch_result($res, 0, 'tipo'));
		$comunicado_mensagem  = trim(pg_fetch_result($res, 0, 'mensagem'));
		$video				  = trim(pg_fetch_result($res, 0, 'video'));
		$comunicado_data      = trim(pg_fetch_result($res, 0, 'data'));
		$comunicado_extensao  = trim(pg_fetch_result($res, 0, 'extensao'));

		$gif = "comunicados/$Xcomunicado.gif";
		$jpg = "comunicados/$Xcomunicado.jpg";
		$pdf = "comunicados/$Xcomunicado.pdf";
		$doc = "comunicados/$Xcomunicado.doc";
		$rtf = "comunicados/$Xcomunicado.rtf";
		$xls = "comunicados/$Xcomunicado.xls";
		$ppt = "comunicados/$Xcomunicado.ppt";
	}
}

if ((strlen($comunicado) > 0) && (pg_num_rows($res) > 0) and isset($_GET['comunicado'])) { ?>

	<div class="box margin-top-2">
        <div class="table">
            <div class="row tac">
                <ul>
                    <li>
                    	<h4>
                    		<strong><?= $comunicado_tipo ?></strong> - <?= $comunicado_data ?>
                    	</h4>
                   	</li>
                </ul>
            </div>
            <div class="row tac">
                <hr>
                <h2 class="title"><?= $descricao ?></h2>
                <p><?= $comunicado_mensagem ?></p>
                <br /><br />
                <?php

                if ($S3_online) {
			        $s3->set_tipo_anexoS3($comunicado_tipo);
			        $s3->temAnexos($Xcomunicado);
					$s3link = $s3->url;

					echo "<a class='btn btn-primary' href='$s3link' target='_blank'>" .
						traduz("visualizar.arquivo",$con).'</a>.' . chr(10);
				} else {
					if (file_exists($gif) == true) echo "	<img src='comunicados/$Xcomunicado.gif'>";
					if (file_exists($jpg) == true) echo "<img src='comunicados/$Xcomunicado.jpg'>";
					if (file_exists($doc) == true) echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.doc' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
					if (file_exists($rtf) == true) echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.rtf' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
					if (file_exists($xls) == true) echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.xls' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
					if (file_exists($ppt) == true) echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.ppt' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
					if (file_exists($pdf) == true) {
						echo "<div class='txt10Normal'><font color='#A02828'>">traduz("se.voce.nao.possui.o.acrobat.reader",$con,$cook_idioma)."&reg;</font> , <a href='http://www.adobe.com/products/acrobat/readstep2.html'>".traduz("instale.agora",$con,$cook_idioma)."</a>.</div>";
						echo "<br>";
						echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.pdf' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
					}
				}

				if ($login_fabrica==50 and $video<>''){	?>
					<br />
					<P><A href="javascript:window.open('/assist/video.php?video=<?=$video?>','_blank',
						'toolbar=no, status=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);">
						<?= traduz('Assistir vídeo anexado') ?></A></P><?
				}

                ?>
            </div>
        </div>
    </div>

	<?
	include "rodape.php";
	exit;
}

##### Consulta de comunicados #####
?>

<? if(strlen($erro)>0){ ?>
	<div class="alert alert-error">
		<h4><? echo $erro; ?></h4>
	</div>
<? } ?>
<div class="row">
	<b class="obrigatorio pull-right">  * <?= traduz('Campos obrigatórios') ?> </b>
</div>

<form class='form-search form-inline tc_formulario' name="frm_comunicado" method="get" action="comunicado_mostra.php">
	<input type="hidden" name="acao">
		<div class='titulo_tabela '>
			<?=traduz('parametros.de.pesquisa', $con)?>
		</div>
		<br />
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<label class='control-label' for='data_inicial'><? fecho ("data.inicial",$con);?></label>
					<?php
							if (in_array($login_fabrica, array(3,169,170))) {
							?>
								<div class='control-group <?=(in_array("tipo", $msg_erro["campos"])) ? "error" : ""?>'>
							<?php

							}else{
							?>
							  <div class='controls controls-row'>
							<?php
							
							}
					?>
					
						<div class='span4'>

							<input class='span12' maxlength="10" type="text" name="data_inicial" id="data_inicial" value="<?=$data_inicial?>" onclick="if (this.value == 'dd/mm/aaaa') { this.value=''; }">
						</div>
					</div>
				</div>
				<div class='span4'>
					<label class='control-label' for='data_final'><? fecho ("data.final",$con);?></label>
					<div class='controls controls-row'>
						<div class='span4'>

							<input class='span12' type="text" name="data_final" id="data_final" maxlength="10" class='Caixa' value="<?=$data_final?>" onclick="if (this.value == 'dd/mm/aaaa') { this.value=''; }">
						</div>
					</div>
				</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<label class='control-label' for='data_inicial'>
					</label>

					<?php
					 if (in_array($login_fabrica, array(3))) {
				 	 ?>
						 <h5 class='asteristico'>*</h5>
				 	 <?php
					 }
					 ?>

					<?fecho("tipo",$con,$cook_idioma);?><br>

					<?php
					if ($login_fabrica == 3) {
						?>
						  <div class='control-group <?=($r == 'tipo') ? "error" : ""?>'>
						<?php
 					} else {
 					?>

					<div class='controls controls-row'>
 						<?php
 					}
					?>
						<div class='span4'>
							<? if($login_fabrica != 3){ ?>
								<h5 class='asteristico'>*</h5>
						<?php }

						if(!in_array($login_fabrica, array(1,15,42,117))){

							$cond_fabrica = (in_array($login_fabrica, array(11,172))) ? " fabrica IN (11,172) " : " fabrica = $login_fabrica ";

							$sel_tipos = include('admin/menus/comunicado_option_array.php');
							$sel_tipos2 = include('admin/menus/vista_tipo_array.php');
							$sel_tipos = array_merge($sel_tipos, $sel_tipos2);

							$new_tipos[] = '';

							if (in_array($login_fabrica, [30])) {
								unset($sel_tipos['Alterações Técnicas']);
								unset($sel_tipos['Descritivo técnico']);
								unset($sel_tipos['Manual Técnico']);
								unset($sel_tipos['Orientação de Serviço']);
								unset($sel_tipos['Procedimentos']);
								unset($sel_tipos['Contrato']);
								$sel_tipos['Manual administrativo'] = "Manual administrativo";
								$sel_tipos['Defeito constatado'] = "Defeito constatado";
							}

							if($login_fabrica == 161){
								unset($sel_tipos['Alterações Técnicas']);
								unset($sel_tipos['Vista Explodida']);
								unset($sel_tipos['Esquema Elétrico']);
								unset($sel_tipos['Manual Técnico']);
							}

							foreach ($sel_tipos as $key => $value) {
								$new_tipos[$key] = traduz($value);
							}
							asort($new_tipos);
							$new_tipos = array_filter($new_tipos);
							if($login_fabrica == 30){
								unset($new_tipos['Foto']);
								unset($new_tipos['Informativo']);
							}
							if ($login_fabrica == 3)
								unset($new_tipos['Ajuda Suporte Tecnico']);
								unset($new_tipos['Peças de Reposição']);
								unset($new_tipos['LGR']);
							if ($login_fabrica == 19){
								unset($new_tipos['Formulários']);
							}

							echo array2select('tipo', 'tipo', $new_tipos, $tipo, " style='witdh:20px'", "Selecione",true);

							?>
						<?php
							}elseif(in_array($login_fabrica,[42,117])){

								$sel_tipos = include('comunicado_option_posto.php');

								asort($sel_tipos);
								echo array2select('tipo', 'tipo', $sel_tipos, $tipo, "class='span11'", " ",true);

							}else{

								$sel_tipos = include('admin/menus/comunicado_option_array.php');


								if(in_array($login_fabrica, array(1))){


									$sql_tipo_posto_bd = "SELECT categoria FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$login_posto}";
							        $res_tipo_posto_bd = pg_query($con, $sql_tipo_posto_bd);


							        if(pg_num_rows($res_tipo_posto_bd) > 0){

							            $desc_categoria_posto = pg_fetch_result($res_tipo_posto_bd, 0, "categoria");

							            if(!in_array($desc_categoria_posto, array("Autorizada", "Locadora Autorizada"))){
							            	unset($sel_tipos["Contrato"]);
							            }

							        }

								}

								echo array2select('tipo', 'tipo', $sel_tipos, $tipo, "class='span11'", "Selecione",true);

							}
						?>
					</div>
				</div>
			</div>
			<div class='span4'>
				<label class='control-label' for='data_inicial'>
					<? fecho("descricao.titulo",$con,$cook_idioma);?><br>
				</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<input type="text" name="descricao" size="40" class="frm" value="<?=$descricao_pesq?>">
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<?php

		if (!in_array($login_fabrica, [148]) || (in_array($login_fabrica, [148]) && !in_array(strtolower($tipo), $array_mostra_linha))) { ?>
			<div class="row-fluid">
				<div class='span2'></div>
					<div class='span4'>
						<label class='control-label' for='data_inicial'>
							<? if (in_array($login_fabrica, array(42))) {
								fecho("ref.produto",$con,$cook_idioma);
							} else{
								fecho("referencia",$con,$cook_idioma);
							}
							?>
						</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<input type="text" name="produto_referencia" value="<? echo $produto_referencia ?>">
									<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
									<input type="hidden" name="lupa_config" posto="<?=$login_posto?>" tipo="produto" parametro="referencia" />
							</div>
						</div>
					</div>
					<div class='span4'>
						<label class='control-label' for='data_inicial'>
							<? if (in_array($login_fabrica, array(42))) {
								fecho("descricao.produto",$con,$cook_idioma);
							}else{
								fecho("descricao",$con,$cook_idioma);
							}?>
						</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<input type="text" name="produto_descricao" value="<? echo $produto_descricao ?>">
								<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
								<input type="hidden" name="lupa_config" posto="<?=$login_posto?>" tipo="produto" parametro="descricao" />
							</div>
						</div>
					</div>
				<div class='span2'></div>
			</div>

			<?php if(in_array($login_fabrica, array(42))) : ?>

			<div class="row-fluid">
				<div class="span2"></div>
		        <div class='span4'>
		            <div class='control-group'>
		                <label class='control-label' for='palavra_chave'><?php echo traduz("Palavra-Chave"); ?></label>
		                <div class='controls controls-row'>
		                    <div class='span12'>
		                    	<select name='palavra_chave' id='palavra_chave' data-palavra="<?php echo $palavra_chave; ?>" class="span10"><option value=""></option></select> 
		                    </div>
		                </div>
		            </div>
		        </div>
		    </div>
			<?php endif; ?>

		<?php
		} else if (in_array($login_fabrica, [148]) && in_array(strtolower($tipo), $array_mostra_linha)) { ?>
			<div class='row-fluid'>
		        <div class='span2'></div>
		        <div class='span4'>
		            <div class='control-group'>
		                <label class='control-label' for='produto_referencia_multi'>Linha</label>
		                <div class='controls controls-row'>
		                    <div class='span10 input-append'>
		                        <?php
								$sql_linha = "SELECT  tbl_linha.nome,
													  tbl_linha.linha
												FROM    tbl_linha
												WHERE   tbl_linha.fabrica = $login_fabrica
												AND     tbl_linha.ativo IS TRUE
												AND     tbl_linha.linha IN (
													SELECT tbl_posto_linha.linha
													FROM tbl_posto_linha
													JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
													WHERE tbl_posto_linha.posto = $login_posto
													AND tbl_posto_linha.ativo IS TRUE
												)
												ORDER BY tbl_linha.nome;";
								$res_linha = pg_query ($con,$sql_linha);

								if (pg_num_rows($res_linha) > 0) { ?>
									<select class='span12' name='linha'>
										<option value=''>Selecione</option>

										<?php
										while ($linha_posto = pg_fetch_array($res_linha)) {
											$linha_descricao = $linha_posto['nome'];
											$linha_id  	     = $linha_posto['linha'];
										?>
											<option value="<?= $linha_id ?>" <?= ($linha_id == $linha) ? "selected" : "" ?> > <?= $linha_descricao ?></option>
										<?php	
										} ?>
										
									</select>
								<?php
								}
								?>
		                    </div>
		                </div>
		            </div>
			    </div>
		    </div>
		<?php
		}

		if ($login_fabrica == 175){ ?>
			<div class="row-fluid">
		    	<div class="span2"></div>
		    	<div class="span4">
					<div class='control-group <?=(in_array("ordem_producao", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='ordem_producao'><?=fecho("ordem.de.producao",$con,$cook_idioma);?></label>
						<div class='controls controls-row'>
							<div class='span8' >
								<input type='text' numeric="true" id="ordem_producao" name='ordem_producao' value='<?php echo $ordem_producao; ?>' maxlength='50' class='span12'>
							</div>
						</div>
					</div>
				</div>
				<div class="span6"></div>
			</div>
		<?php }

		if (isFabrica(30,169,170)) {
		?>
			<div class="row-fluid">
				<div class='span2'></div>
					<div class='span4'>
						<label class='control-label' for='data_inicial'>
							<?fecho("familia",$con,$cook_idioma);?><br>
						</label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
							<?php
								##### INÍCIO FAMÍLIA #####
								$cond_fabrica_familia = (in_array($login_fabrica, array(11,172))) ? " tbl_familia.fabrica IN (11,172) " : " tbl_familia.fabrica = $login_fabrica ";

								$sqlFamilia = "SELECT  *
										FROM    tbl_familia
										WHERE   $cond_fabrica_familia
										AND tbl_familia.ativo is true
										ORDER BY tbl_familia.descricao;";
								$resFamilia = pg_query ($con,$sqlFamilia);
								$contador_familia = pg_num_rows($resFamilia);

								if ($contador_familia > 0) {
									echo "<select name='familia' id='familia'>\n";
									echo "<option value=''></option>\n";
									for ($x = 0 ; $x < $contador_familia; $x++){
										$aux_familia = trim(pg_fetch_result($resFamilia,$x,familia));
										$aux_descricao  = trim(pg_fetch_result($resFamilia,$x,descricao));
										echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
									}
									echo "</select>\n";
								}

							?>
							</div>
						</div>
					<div class='span2'></div>
				</div>
			</div>
		<?php
		}
		?>
		<?	if($login_fabrica==3) { ?>
				<div class="row-fluid">
					<div class='span2'></div>
						<div class='span4'>
							<input type="radio" name="administrativo" value="administrativo" class="frm" <? if ($opcao == "1") echo "checked"; ?>><?fecho('administrativos', $con);?>
						</div>
					<div class='span2'></div>
				</div>
		<?	} ?>
		<br />
		<input type="hidden" name="btn_acao" value="pesquisar" />
		<button class="tac btn" type='submit' style="cursor: pointer;"
			 onClick="document.frm_comunicado.acao.value='PESQUISAR';">
			<?fecho("pesquisar",$con,$cook_idioma);?>
		</button><br /><br />
</form> 

<center>
	<button class="tac btn btn-primary" onClick="window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?listar_recentes=listar';" style="cursor: pointer;"><?fecho("Listar.ultimos.comunicados",$con,$cook_idioma);?>
	</button>
</center>
<br />
<?

$tipo       = $_GET['tipo'];
$comunicado = $_GET['comunicado'];

if (strlen ($comunicado) > 0) {
	$sql = "SELECT tipo FROM tbl_comunicado WHERE comunicado = $comunicado";
	$res = pg_query ($con,$sql);
	$tipo = pg_fetch_result ($res,0,0);
}

if (strlen ($comunicado) == 0 AND strlen(trim($erro)) == 0 and (!empty($btn_acao) or !empty($acao) or !empty($tipo))) {
	if(in_array($login_fabrica, [180,181,182])){
		$array_mostra_produto = array('despiece','esquema eléctrico','alteraciones ténicas','manual técnico');
	}else{
		$array_mostra_produto = array('vista explodida','esquema elétrico','alterações técnicas','manual técnico');
	}
	
	if (in_array($login_fabrica, [175])) {
		$array_mostra_produto[] = 'procedimentos';
	}

	$comunicado_video = "";

	if ($tipo == 'Video' && isFabrica(11,172)) {
		$comunicado_video = " OR (tbl_comunicado.linha IS NULL AND tbl_comunicado.video <> '' AND tbl_comunicado.produto IS NULL)";
	}

	$cond_fabrica_produto = (in_array($login_fabrica, array(11,172))) ? " fabrica_i IN(11,172)" : " fabrica_i = $login_fabrica ";
	$cond_fabrica_posto = (in_array($login_fabrica, array(11,172))) ? " fabrica IN (11,172) " : " fabrica = $login_fabrica ";

	$sql_cond_linha = "
						AND (tbl_comunicado.linha IN
								(
									SELECT tbl_linha.linha
									FROM tbl_posto_linha
									JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
									WHERE {$cond_fabrica_posto}
										AND posto = $login_posto
								) OR tbl_comunicado.linha IS NULL
								OR (
									tbl_comunicado.produto IS NULL AND
									tbl_comunicado.comunicado IN (
										SELECT tbl_comunicado_produto.comunicado
										FROM tbl_comunicado_produto
										JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
										JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
										WHERE {$cond_fabrica_produto} AND
											  tbl_posto_linha.posto = $login_posto
									)
								)
								OR (
									tbl_comunicado.linha IS NULL AND
									tbl_comunicado.produto IN
										(
											SELECT tbl_produto.produto
											FROM tbl_produto
											JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha
											WHERE {$cond_fabrica_produto} AND
											posto = $login_posto
										)
									)

								 OR (tbl_comunicado.linha IS NULL AND tbl_comunicado.produto IS NULL AND
								 		tbl_comunicado.comunicado IN (
											SELECT tbl_comunicado_produto.comunicado
											FROM tbl_comunicado_produto
											JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
											JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
											WHERE {$cond_fabrica_produto} AND
												  tbl_posto_linha.posto = $login_posto
										)

									)
								{$comunicado_video}
							)";
	if (isFabrica(1)) {		//HD 10983
		$sql_cond1=" tbl_comunicado.pedido_em_garantia     IS NULL ";
		$sql_cond2=" tbl_comunicado.pedido_faturado        IS NULL ";
		$sql_cond3=" tbl_comunicado.digita_os              IS NULL ";
		$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS NULL ";

		$sql_cond5=" AND (tbl_comunicado.destinatario_especifico = '$categoria' or tbl_comunicado.destinatario_especifico = '' or tbl_comunicado.destinatario_especifico is null) ";
		$sql_cond6=" AND (tbl_comunicado.tipo_posto = '$tipo_posto' or tbl_comunicado.tipo_posto is null) ";

		if ($pedido_em_garantia == "t")     $sql_cond1 =" tbl_comunicado.pedido_em_garantia     IS NOT FALSE ";
		if ($pedido_faturado == "t")        $sql_cond2 =" tbl_comunicado.pedido_faturado        IS NOT FALSE ";
		if ($digita_os == "t")              $sql_cond3 =" tbl_comunicado.digita_os              IS TRUE ";
		if ($reembolso_peca_estoque == "t") $sql_cond4 =" tbl_comunicado.reembolso_peca_estoque IS TRUE ";
		$sql_cond_total="AND ( $sql_cond1 OR $sql_cond2 OR $sql_cond3 OR $sql_cond4) ";
		$sql_cond_linha = "";
	}

	if (isFabrica(15,42,161))  $sql_cond_linha = "";

	$where_filtro = '';
	$cond_sql = '';
	$cond_sqlProduto = '';
	if (!empty($produto_referencia)) {
		$cond_sql = " AND (prod.referencia = '{$produto_referencia}' OR tbl_produto.referencia = '{$produto_referencia}' OR (prod_familia.referencia = '{$produto_referencia}' and tbl_comunicado.produto isnull and tbl_comunicado_produto.produto isnull) )";
		$cond_sqlProduto = " AND (prod.referencia = '{$produto_referencia}' OR tbl_produto.referencia = '{$produto_referencia}')";
	}
	if (!empty($data_inicial) and !empty($data_final)) {
		$where_filtro .= " AND tbl_comunicado.data BETWEEN '".formata_data($data_inicial)." 00:00:00' AND '".formata_data($data_final)." 23:59:59'";
	}
	if (!empty($descricao_pesq)) {
		$where_filtro .= " AND tbl_comunicado.descricao ilike '%$descricao_pesq%'";
	}

	if (!empty($familia)) {
		$where_filtro .= " AND (tbl_comunicado.familia = $familia or tbl_produto.familia = $familia or prod.familia = $familia)";
	}

	if (in_array($login_fabrica, [20])){
		$where_filtro .= " AND tbl_comunicado.pais = '{$login_pais}'";
	}
	
	if ($login_fabrica == 175){
		if (!empty($ordem_producao)){
			$where_filtro .= " AND tbl_comunicado.versao = '$ordem_producao'";
		}
	}

	if (in_array(strtolower($tipo),$array_mostra_produto)) {
		$where_case = 'CASE WHEN tbl_comunicado.produto IS NULL
	                         THEN prod.produto
	                         ELSE tbl_produto.produto
	                    END                                 AS produto,
	                    CASE WHEN tbl_comunicado.produto IS NULL
	                         THEN prod.referencia
	                         ELSE tbl_produto.referencia
	                    END                                 AS referencia,
	                    CASE WHEN tbl_comunicado.produto IS NULL
	                         THEN prod.referencia
	                         ELSE tbl_produto.referencia
	                    END                                 AS produto_referencia,
	                    CASE WHEN tbl_comunicado.produto IS NULL
	                         THEN prod.descricao
	                         ELSE tbl_produto.descricao
	                    END                                 AS produto_descricao,';
	}else{
		$groupby = 'GROUP BY tbl_comunicado.comunicado,tbl_comunicado.descricao,tbl_comunicado.mensagem,tbl_comunicado.extensao,tbl_comunicado.video,tbl_comunicado.link_externo,data, tbl_comunicado.tipo';
	}

	$cond_ativo = " tbl_comunicado.ativo IS TRUE ";

	if (isFabrica(1)) {
		if ($_GET["tipo"] == "Contrato") {
			$cond_ativo = " tbl_comunicado.tipo = 'Contrato' ";
		}
	}

	if (in_array($login_fabrica, [148]) && in_array(strtolower($tipo), $array_mostra_linha)) {

		$campoLinha = ", tbl_linha.nome as nome_linha";

		if (!empty($linha)) {
			$condLinha  = " AND tbl_comunicado.linha = $linha ";
		}

		$condProduto = " AND tbl_comunicado.produto IS NULL 
						 AND tbl_comunicado.linha IS NOT NULL";
	}

	if ($tipo_posto_multiplo) {
		$condPostoTipo = "
			tbl_comunicado.tipo_posto IN (
				SELECT tipo_posto 
				FROM tbl_posto_tipo_posto
				WHERE tbl_posto_tipo_posto.fabrica = {$login_fabrica}
				AND tbl_posto_tipo_posto.posto = {$login_posto}
			) ";
	} else {
		$condPostoTipo = " tbl_comunicado.tipo_posto = $login_tipo_posto ";
	}

	$cond_fabrica_comunicado = (in_array($login_fabrica, array(11,172))) ? " tbl_comunicado.fabrica IN (11,172) " : " tbl_comunicado.fabrica = $login_fabrica ";

	if ($login_fabrica == 177){
		$cond_estado = "AND ( tbl_comunicado.parametros_adicionais->'estados' IS NULL OR tbl_comunicado.parametros_adicionais->'estados' ? '$estado')";
	}else{
		$cond_estado = "AND ( tbl_comunicado.estado = '$estado' OR tbl_comunicado.estado IS NULL )";
	}

	if ($login_fabrica == 161) {
		$cond_posto_linha = " LEFT JOIN tbl_posto_linha ON prod.linha = tbl_posto_linha.linha AND tbl_posto_linha.posto = {$login_posto} and prod.fabrica_i = {$login_fabrica} 
							  LEFT JOIN tbl_posto_linha pl ON tbl_comunicado_produto.linha = pl.linha AND pl.posto = {$login_posto} and tbl_comunicado.fabrica = {$login_fabrica} 
							  LEFT JOIN tbl_posto_linha plc ON tbl_comunicado.linha = plc.linha AND plc.posto = {$login_posto} and tbl_comunicado.fabrica = {$login_fabrica} ";
		$and_cond_posto   = " AND ( (tbl_posto_linha.linha = tbl_comunicado_produto.linha 
								         OR 
								      pl.linha = tbl_comunicado_produto.linha
								         OR
								      plc.linha = tbl_comunicado_produto.linha
								         OR 
								      tbl_posto_linha.linha = tbl_comunicado.linha
								         OR 
								      pl.linha = tbl_comunicado.linha
								         OR
								      plc.linha = tbl_comunicado.linha) 
								    OR (tbl_comunicado_produto.linha IS NULL AND tbl_comunicado.linha IS NULL)
								   )
						      AND ((prod.produto = tbl_comunicado_produto.produto AND prod.linha = tbl_posto_linha.linha) OR tbl_comunicado_produto.produto IS NULL) ";
	}

	if ($login_fabrica == 139) {
		$distinct_on = " DISTINCT ON (tbl_comunicado.comunicado) ";
	}

	$sql = "SELECT {$distinct_on} tbl_comunicado.comunicado,
					tbl_comunicado.fabrica,
					tbl_comunicado.descricao,
					tbl_comunicado.mensagem,
					tbl_comunicado.extensao,
                    tbl_comunicado.video,
					tbl_comunicado.link_externo,
					tbl_comunicado.tipo AS xtipo_comunicado,
					{$where_case}
					to_char (tbl_comunicado.data,'dd/mm/yyyy') AS data
					{$campoLinha}
			FROM    tbl_comunicado
       LEFT JOIN    tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
       LEFT JOIN    tbl_produto            ON tbl_produto.produto               = tbl_comunicado.produto
       LEFT JOIN    tbl_produto prod       ON prod.produto                      = tbl_comunicado_produto.produto
		LEFT JOIN 	tbl_produto prod_familia ON prod_familia.familia 			= tbl_comunicado.familia
		LEFT JOIN   tbl_linha ON tbl_comunicado.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
		{$cond_posto_linha}
		WHERE {$cond_fabrica_comunicado}
			AND     (
                        {$condPostoTipo}
                    OR  tbl_comunicado.tipo_posto IS NULL
                    )
			AND     (
                        tbl_comunicado.posto     = $login_posto
                    OR tbl_comunicado.posto      IS NULL
                    )
			{$cond_estado}
			{$and_cond_posto}
			AND     {$cond_ativo} {$cond_sql} {$where_filtro} {$condLinha} {$condProduto}";

	if (isFabrica($fabrica_multinacional) and !isFabrica(161))
		$sql .= " AND tbl_comunicado.pais = '$login_pais' ";

	$sql.=" $sql_cond_linha ";

	if (isFabrica(1)) {

		if ($_GET["tipo"] != "Contrato") {

			if ($tipo == 'zero') {
				$tipo = "Sem Título";
				$sql .= "AND	tbl_comunicado.tipo IS NULL ";
			}else{
				$sql .= "AND	tbl_comunicado.tipo = '$tipo' ";
			}

		}

	}else if (isFabrica(203)){ 
		$sql .= "AND (tbl_comunicado.tipo = 'ITB Informativo Técnico Brother' OR tbl_comunicado.tipo ILIKE '%ITB%')";
	}else{

		if ($tipo == 'zero') {
			$tipo = "Sem Título";
			$sql .= "AND tbl_comunicado.tipo IS NULL ";
		}else{
			if (strlen(trim($tipo)) > 0){
				if(in_array($login_fabrica,[180,181,182])){
					$tipo = traduz($tipo);
				}
				$sql .= "AND tbl_comunicado.tipo = '$tipo' ";
			}
		}
	}

	if($login_fabrica == 42 && $palavra_chave != ""){
		$palavra_chave = strtolower($palavra_chave);
		$sql .= " AND tbl_comunicado.palavra_chave @@ tsquery('{$palavra_chave}')";
	}

	if (isFabrica(14)) {
		$sql .= "AND (tbl_comunicado.familia IN ($familia_posto) OR tbl_comunicado.familia IS NULL)";
	}

	//HD 10983
	if (isFabrica(1) && $_GET["tipo"] != "Contrato") {
		$sql.=" $sql_cond_total ";
		$sql.= $sql_cond5;
		$sql.= $sql_cond6;
	}


	//$limit = (isFabrica(161)) ? 10 : 40;
	if($btn_acao == 'pesquisar_menu'){
		if ($login_fabrica == 139) {
			$sql .= "{$groupby} ORDER BY tbl_comunicado.comunicado, tbl_comunicado.data DESC LIMIT 10";
		} else {
			$sql .= "{$groupby} ORDER BY tbl_comunicado.data DESC LIMIT 10";
		}
	} else{
		if ($login_fabrica == 139) {
			$sql .= "{$groupby} ORDER BY tbl_comunicado.comunicado, tbl_comunicado.data DESC";		
		} else {
			$sql .= "{$groupby} ORDER BY tbl_comunicado.data DESC";	
		}
	}

	##### PAGINAÇÃO - INÍCIO #####
    $sqlCount  = "SELECT count(*) FROM (";
    $sqlCount .= $sql;
    $sqlCount .= ") AS count";

    if($btn_acao != 'pesquisar_menu'){
    	require "_class_paginacao.php";	
	    // definicoes de variaveis
	    $max_links = 11;                // máximo de links à serem exibidos
	    $max_res   = 100;                // máximo de resultados à serem exibidos por tela ou pagina
	    $mult_pag= new Mult_Pag();  // cria um novo objeto navbar
	    $mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página
	   	$res = $mult_pag->Executar($sql, $sqlCount, $con, "otimizada", "pgsql");
    } else {
    	$res = pg_query($con, $sql);
    }    

    ##### PAGINAÇÃO - FIM #####

	//$res = pg_query ($con,$sql);

	echo "<br />";
	echo "<form name='frmcomunicado'>";
	$total = pg_num_rows ($res);

	if($total > 0) {
		if($login_fabrica==19){
			echo "<table class='table table-bordered table-striped'>";
			echo "<tr bgcolor = '#fafafa'>";
			echo "<td rowspan='3' width='20' valign='top'><img src='imagens/marca25.gif'></td>";
			if($tipo == 'Promocao') {
				echo "<td  class='chapeu' colspan='2' >Formulários</td>";
			}else{
				echo "<td  class='chapeu' colspan='2' >$tipo</td>";
			}
			echo "</tr>";
			echo "<tr bgcolor = '#fafafa'><td colspan='2' height='5'></td></tr>";
			echo "<tr bgcolor = '#fafafa'>";
			echo "<td valign='top' class='menu'>";
			echo "<dl>";
		}else{
			echo "<table class='table table-striped table-bordered table-fixed' id='table_frmcomunicado'>";
			echo "<thead>";
			echo "<tr class='titulo_coluna'>";
			echo "<th>";
			fecho ("data",$con,$cook_idioma);
			echo "</th>";

			if ($login_fabrica == 42) echo "<th width='80'>".traduz("Título",$con,$cook_idioma)."</th>";

			
			if (!in_array($login_fabrica, [148]) || (in_array($login_fabrica, [148]) && !in_array(strtolower($tipo), $array_mostra_linha))) {
				echo "<th>";
					if (in_array(strtolower($tipo),$array_mostra_produto)) {
						fecho (array('Produto'),$con,$cook_idioma);
					}else{
						fecho (array('Descricao'),$con,$cook_idioma);
						}
				echo "</th>";
			} else if (in_array($login_fabrica, [148])) { ?>
				<th>Linha</th>
				<th>Título</th>
			<?php
			}
			
			if ($opcao == "1"){
				echo "<th>".traduz("produto",$con,$cook_idioma)."</th>";
			}
			if ($login_fabrica == 42 && in_array($_GET["tipo"], array('Video', 'Treinamento Telecontrol'))) {
				$exibir_duracao = true;

				echo "<th width='80'>".traduz("Duração",$con,$cook_idioma)."</th>";	
			}
			echo "<th>";
			fecho ("arquivo",$con,$cook_idioma);

			echo "</th>";
			if ( in_array($login_fabrica, array(11,172)) ) {
				echo "<th width='80'>".traduz("video",$con,$cook_idioma)."</th>";
			}
			echo "</tr>";
			echo "</thead>";
			echo "<tbody class='Conteudo'>";
		}

		if ($login_fabrica == 175){
			$campo_versao = ", tbl_comunicado.versao";
		}else{
			$campo_versao = "";
		}

		if ($login_fabrica == 177){
			$cond_estado_produto = " AND (tbl_comunicado.parametros_adicionais->'estados' IS NULL OR tbl_comunicado.parametros_adicionais->'estados' ? '$estado') ";
		}else{
			$cond_estado_produto = " AND (tbl_comunicado.estado = '{$estado}' OR tbl_comunicado.estado IS NULL) ";
		}

		if ($login_fabrica == 161) {
			$cond_posto_linha = " LEFT JOIN tbl_posto_linha ON prod.linha = tbl_posto_linha.linha AND tbl_posto_linha.posto = {$login_posto} and prod.fabrica_i = {$login_fabrica} 
								  LEFT JOIN tbl_posto_linha pl ON tbl_comunicado_produto.linha = pl.linha AND pl.posto = {$login_posto} and tbl_comunicado.fabrica = {$login_fabrica}
								  LEFT JOIN tbl_posto_linha plc ON tbl_comunicado.linha = plc.linha AND plc.posto = {$login_posto} and tbl_comunicado.fabrica = {$login_fabrica} ";
			$and_cond_posto   = " AND ( (tbl_posto_linha.linha = tbl_comunicado_produto.linha 
								         OR 
								      pl.linha = tbl_comunicado_produto.linha
								         OR
								      plc.linha = tbl_comunicado_produto.linha
								         OR 
								      tbl_posto_linha.linha = tbl_comunicado.linha
								         OR 
								      pl.linha = tbl_comunicado.linha
								         OR
								      plc.linha = tbl_comunicado.linha) 
								    OR (tbl_comunicado_produto.linha IS NULL AND tbl_comunicado.linha IS NULL)
								   )
							      AND ((prod.produto = tbl_comunicado_produto.produto AND prod.linha = tbl_posto_linha.linha) OR tbl_comunicado_produto.produto IS NULL) ";
		}

		if ($login_fabrica == 139) {
			$onDist = " ON (tbl_comunicado.comunicado) ";
		}

		/* CONSULTA DE PRODUTOS RELACIONADOS AO COMUNICADO */
		$sqlProduto = "SELECT DISTINCT $onDist CASE WHEN tbl_comunicado.produto IS NULL
	                        THEN prod.produto
	                        ELSE tbl_produto.produto
	                    END                                 AS produto,
	                    CASE WHEN tbl_comunicado.produto IS NULL
	                        THEN prod.referencia
	                        ELSE tbl_produto.referencia
	                    END                                 AS referencia,
	                    CASE WHEN tbl_comunicado.produto IS NULL
	                        THEN prod.referencia
	                        ELSE tbl_produto.referencia
	                    END                                 AS produto_referencia,
	                    CASE WHEN tbl_comunicado.produto IS NULL
	                        THEN prod.descricao
	                        ELSE tbl_produto.descricao
	                    END                                 AS produto_descricao $campo_versao
	                    FROM tbl_comunicado
	                    LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado =tbl_comunicado.comunicado
	                    LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
	                    LEFT JOIN tbl_produto prod ON prod.produto = tbl_comunicado_produto.produto
						LEFT JOIN tbl_produto prod_familia ON prod_familia.familia = tbl_comunicado.familia
						$cond_posto_linha
	                    WHERE tbl_comunicado.fabrica = {$login_fabrica}
							AND (tbl_comunicado.tipo_posto = {$tipo_posto}
							OR tbl_comunicado.tipo_posto IS NULL)
							AND (tbl_comunicado.posto = {$login_posto}
							OR tbl_comunicado.posto IS NULL)
							$cond_estado_produto
							$and_cond_posto
							AND tbl_comunicado.ativo IS TRUE {$cond_sqlProduto} {$where_filtro}
							AND tbl_comunicado.comunicado = $1;";
	    //exit($sqlProduto);						
		pg_prepare($con, 'ConsultaProduto', $sqlProduto);
		for ($i=0; $i<$total; $i++) {
			$produto = pg_fetch_result ($res,$i,produto);
			$Xcomunicado        = trim(pg_fetch_result($res, $i, 'comunicado'));
			$descricao          = trim(pg_fetch_result($res, $i, 'descricao'));
			$extensao           = trim(pg_fetch_result($res, $i, 'extensao'));
			$mensagem           = trim(pg_fetch_result($res, $i, 'mensagem'));
			$video              = trim(pg_fetch_result($res, $i, 'video'));
			$link				= pg_fetch_result($res, $i, 'link_externo');
			$data               = trim(pg_fetch_result($res, $i, 'data'));
			$fabrica_comunicado = trim(pg_fetch_result($res, $i, 'fabrica'));
			$produto_referencia = trim(@pg_fetch_result($res, $i, 'produto_referencia'));
			$produto_descricao  = trim(@pg_fetch_result($res, $i, 'produto_descricao'));
			$comunicado         = $Xcomunicado;
			$xtipo_comunicado 	= trim(@pg_fetch_result($res, $i, 'xtipo_comunicado'));
			$nome_linha         = pg_fetch_result($res, $i, 'nome_linha');

			if (empty($produto_referencia)) {
				$produto_referencia = pg_fetch_result($res, $i, 'referencia');
			}
			
			if (trim($extensao) == 'm') {$extensao = 'bmp';}//modificar as extensoes de alguns arquivos esta como m onde deveria estar bmp.

			if (!isFabrica(19)) {
				$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA" ;

				echo "<tr bgcolor='$cor'>";
				echo "<td align='center' style='font-size:11px' class='tac'>$data</td>";

				if($login_fabrica == 42 and in_array(strtolower	($tipo), $array_mostra_produto)) {
				echo "<td align='center' style='font-size:11px' class='tac'></td>";
				}
				if (!in_array($login_fabrica, [148]) || (in_array($login_fabrica, [148]) && !in_array(strtolower($tipo), $array_mostra_linha))) {
					echo "<td align='left' style='font-size:11px'>";
						if (in_array(strtolower($tipo),$array_mostra_produto)) {
							echo "<B>$produto_referencia - $produto_descricao";
						}else{
							$resultP = pg_execute($con, 'ConsultaProduto',array($comunicado));
							$arrayP = pg_fetch_all($resultP);
							if (!empty($mensagem) || (is_array($arrayP) && count($arrayP) && (!empty($arrayP[0]['referencia']) || !empty($arrayP[0]['produto_referencia'])))) {
								if ($login_fabrica != 42) {
									echo "<a href =\"javascript:MostraEsconde('$i')\"><img id='img-desc-$i' src='imagens/sort_desc.gif'>";
								}
							}else{
								echo "<a href =\"javascript:return\">";
								echo "<acronym title='Sem descrição'</acronym>";
							}
							if (empty($descricao)) {
								if (strlen(trim($xtipo_comunicado)) > 0){
									echo "<b>".$xtipo_comunicado;
								}else{
									echo "  <B>Sem título";
								}
							}else{
								echo ($login_fabrica != 42) ? "<b>$descricao" : "$descricao";
							}
							if (strlen(trim($descricao)) == 0 and $opcao != "1" and strlen($produto_referencia) > 0)  {
								echo "<acronym title='".traduz("referencia",$con,$cook_idioma).": $produto_referencia | ".traduz("descricao",$con,$cook_idioma).": $produto_descricao'>$produto_descricao</acronym>";
							}
						}
					echo ($login_fabrica != 42) ? "</b></td>" : "</td>";
				} else if (in_array($login_fabrica, [148])) { ?>
					<td><?= $nome_linha ?></td>
					<td><?= $descricao ?></td>
				<?php
				}

				/*HD - 4060618*/
				if ($login_fabrica == 42) {
					$aux_sql = "SELECT mensagem, parametros_adicionais FROM tbl_comunicado WHERE comunicado = $comunicado";
					$aux_res = pg_query($con, $aux_sql);
					$aux_msg = pg_fetch_result($aux_res, 0, 'mensagem');
					echo strlen($aux_msg) > 0 ? "<td align='left' style='font-size:11px'>". $aux_msg ."</td>" : '';
					
					if ($exibir_duracao === true) {
						$parametros_adicionais = json_decode(pg_fetch_result($aux_res, 0, 'parametros_adicionais'), true);
						echo "<td align='left' style='font-size:11px'>". $parametros_adicionais["duracao_video"] ."</td>";
					}
				}

				if ($opcao == "1")
					echo "<td  align='left'><acronym title='".traduz("referencia",$con,$cook_idioma).": $produto_referencia | ".traduz("descricao",$con,$cook_idioma).": $produto_descricao'>$produto_descricao</acronym></td>";
				echo "<td align='center' style='font-size:11px' class='tac'>";
			}
			$flagTemLink = "";

			if(in_array($login_fabrica, array(11,172))){
				$s3 = ($fabrica_comunicado == 172) ? $s3_172 : $s3_11;
			}

			if (strlen($comunicado) > 0 and strlen($extensao) > 0 and !isFabrica(19)){
				if ($S3_online) {
					$s3->temAnexos($comunicado);
					if ($s3->temAnexo){
						if (isFabrica(87))
							$nome_arquivo = "Visualizar";
						else
							$nome_arquivo = traduz("visualizar",$con,$cook_idioma);

						if ($s3->url) {
							echo "<a href='$s3->url' alt='S3' target='_blank'>$nome_arquivo";
						}else{
							echo "<a href='JavaScript:void(0);' name='prod_ve' rel='$comunicado/$tipo'>$nome_arquivo</a>";
						}
					}
				}
			}else{
				if (isFabrica(19)) {
					echo "<br><dd>&nbsp;&nbsp;<b>-»</b> ";
				}else{
					echo "&nbsp;";
				}

				if (isFabrica(1)) {

					if ($tipo == "Contrato") {
						echo "<a href='comunicado_mostra.php?gera_contrato=true' target='_blank' style='text-transform: uppercase;'> Realizar o download do contrato </a>";
					}

				}
				

				$sqlc = "SELECT tdocs_id from tbl_tdocs where
						fabrica = $login_fabrica
						and referencia_id = $comunicado
						and contexto = 'comunicados'
						and situacao = 'ativo'";
				$resc = pg_query($con,$sqlc);				
				$anexos	 = false;
				$contador_resc = pg_num_rows($resc);
				if(pg_num_rows($resc) > 1) {
					$anexos	 = true;
				}else{
					if ($S3_online) {
						$s3->temAnexos($comunicado);
						$flagTemLink = $s3->url;
						if (empty($flagTemLink)) {
							
							unset($s3);

							if(in_array($login_fabrica, array(11,172))){
								$s3 = new anexaS3('co', (int) $fabrica_comunicado);
							}else{
								$s3 = new anexaS3('co', (int) $login_fabrica);
							}
							
							$s3->temAnexos($comunicado);
							$flagTemLink = $s3->url;

						}
					}
				}

				if ($anexos && !in_array($login_fabrica, [148])) {
					for($c = 0; $c<$contador_resc; $c++) {
						$cont = $c+1;
						$tdocs_id = pg_fetch_result($resc,$c, 'tdocs_id') ;
						$link_file = "https://api2.telecontrol.com.br/tdocs/document/id/$tdocs_id";
						echo "<a href='$link_file' target='_blank'>";
						fecho ("abrir.arquivo",$con,$cook_idioma);
						echo " $cont</a>&nbsp;&nbsp;&nbsp;";
					}
				}elseif (!empty($flagTemLink) && !in_array($login_fabrica, [148])) {
					echo "<a href='$flagTemLink' alt='S3' target='_blank'>";
				} else if (in_array($login_fabrica, [148])) {
					echo "<a class='btn-lista-anexos' comunicado='".$comunicado."'>";
				}


				if (isFabrica(50) and $video<>"") {
					echo "<A data-comunicado= href=\"javascript:window.open('video.php?video=$video','_blank'," .
						"'toolbar=no, status=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);\">" .
						"Abrir vídeo";
				}elseif (isFabrica(19)) {
					if (strlen($referencia)>0)  echo "$referencia - ";
					if (strlen($descricao)>0)  {
						if ($descricao == 'TESTE DE PROMOÇÃO') {
							echo "TESTE DE FORMULÁRIOS";
						}else{
							echo $descricao;
						}
					}else{
						if (strlen($comunicado_descricao)==0) {
							fecho(array('comunicado','semtitulo'), $con);
						}else{
							echo $comunicado_descricao;
						}
					}
				}elseif (isFabrica(42) && strlen($link) > 0) {
					echo "<a href='$link' target='_blank'>Link";
				}else{
					if (strlen($flagTemLink) > 0) {
						if (isFabrica(42)) {
							fecho ("visualizar",$con,$cook_idioma);
						}else{
							fecho ("abrir.arquivo",$con,$cook_idioma);
						}
					}
				}
				echo "</a>";
			}
			if (isFabrica(19)) {
				echo"</dd>";
			}else{
				echo "</td>";

				if (isFabrica(11,172)) {
					echo "<td align='center'>";
					if (!empty($video)) {
						echo "<a href=\"javascript:window.open('video.php?video=$video','_blank'," .
						"'toolbar=no, status=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);\">" .
						"Abrir vídeo</a>";
					}
					echo "</td>";

				}
				#print_r($arrayP);
				echo "</tr>";
				$linhaInfo = '';
				$insereHeader = '';
				$colspan = 0;
				if (is_array($arrayP) && count($arrayP) && (!empty($arrayP[0]['referencia']) || !empty($arrayP[0]['produto_referencia']))) { //SELECT DO PRODUTO
					if (empty($mensagem)) {
						$colspan = 3;
					}else{
						$colspan = 1;
					}
					$linhaInfo .= "<td style='color:#383838;' colspan='{$colspan}' ><ul>";
					foreach ($arrayP as $indice => $value) {
						if ($login_fabrica == 175){
							$linhaInfo .= "<li><b>Código:</b> ".$arrayP[$indice]['referencia']." - <b>Descrição:</b> ".$arrayP[$indice]['produto_descricao']." - <b>Ordem de produção:</b> ".$arrayP[$indice]['versao']."</li>";
						}else{
							$linhaInfo .= "<li>".$arrayP[$indice]['referencia']." - ".$arrayP[$indice]['produto_descricao']."</li>";
						}
						
					}
					$linhaInfo .= "</ul></td>";
					$insereHeader .= "<td colspan='{$colspan}' style='background-color:#596d9b;color:white;text-align:center'>".traduz('Produtos')."</td>";
				}

				if (isFabrica(1)) {

					if ($tipo == "Contrato") {

						$mensagem = "
                                        <table width='80%' style='margin: 0 auto;'>
                                            <tr>
                                                <td> <img src='logos/logo_black_2016.png' alt='logo' width='300px'> </td>
                                                <td align='right'> Uberaba, 8 de maio de 2017 </td>
                                            </tr>
                                            <tr>
                                                <td colspan='2' align='center'>
                                                    <br />
                                                    <h4>Prezado parceiro,</h4>
                                                    <br />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan='2'>
                                                    Com o objetivo de estabelecer os direitos e deveres de nossa parceria e obter 100% dessas informações online, disponibilizamos o ACORDO DE PRESTAÇÃO DE SERVIÇOS revisado.
                                                    <br /> <br />
                                                    Portanto, gentileza imprimir este comunicado e seguir as etapas abaixo para liberação do sistema:
                                                    <br /> <br />
                                                    1. O <strong>Representante Legal</strong> da empresa deverá: <br />
                                                    &nbsp; &nbsp; &nbsp; 1.1. Imprimir uma cópia do Acordo anexado ao comunicado <br />
                                                    &nbsp; &nbsp; &nbsp; 1.2. Rubricar (vistar) as páginas 1 e 2 <br />
                                                    &nbsp; &nbsp; &nbsp; 1.3. Assinar e carimbar a página 3 (conforme RG) <br />
                                                    2. A <u>Testemunha</u> da empresa deverá: <br />
                                                    &nbsp; &nbsp; &nbsp; 2.1. Rubricar (vistar) as páginas 1 e 2 <br />
                                                    &nbsp; &nbsp; &nbsp; 2.2. Na página 3, preencher o nome completo no campo da \"testemunha 2\" <br />
                                                    &nbsp; &nbsp; &nbsp; 2.3. Informar o RG ou CPF <br />
                                                    3. Após assinatura do representante e testemunha: <br />
                                                    &nbsp; &nbsp; &nbsp; 3.1. Anexar o acordo completo no Telecontrol (passo a passo abaixo) <br />
                                                    &nbsp; &nbsp; &nbsp; 3.2. Anexar o Contrato Social da empresa ou Requerimento de Empresário <br />
                                                    &nbsp; &nbsp; &nbsp; 3.3. Anexar RG frente e verso do Representante Legal/Administrador <br />
                                                    <strong>PASSO A PASSO</strong>: Menu inicial > Cadastro > Informações do posto > Upload De Contratos
                                                    <br /> <br />
                                                    <strong>Observações importantes:</strong> Caso seu contrato apresente erro ou as informações não estejam de acordo (apenas nesses casos), gentileza abrir um chamado escolhendo o tipo de solicitação \"Atualização de cadastro\" com o contrato anexado.
                                                    Se os dados estiverem corretos, solicitamos que o contrato de prestação de serviço seja anexado em um arquivo PDF e o contrato social da empresa / RG frente e verso em outro arquivo PDF. <br />
                                                    O sistema Telecontrol será bloqueado automaticamente após 30 dias corridos caso não tivermos retorno da solicitação acima.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan='2' align='center'>
                                                    <br /> <br />
                                                    Contamos com a colaboração de todos. <br /> <br />
                                                    Qualquer dúvida, gentileza entrar em contato com o suporte de sua região. <br /> <br />
                                                    Departamento de Assistência Técnica <br />
                                                    STANLEY BLACK&DECKER
                                                </td>
                                            </tr>
                                        </table>
                                        <br /> <br />
                                    ";

					}

				}

				if (!empty($mensagem)) {
					$colspan = ($login_fabrica == 42) ? 5 : 3 - $colspan;
					$linhaInfo .= "<td style='color:#383838;' colspan='{$colspan}'><br>". str_replace("\r"," <br> ",$mensagem)."<br>&nbsp;</td>";
					$insereHeader .= "<td colspan='{$colspan}' style='background-color:#596d9b;color:white;text-align:center'>Mensagem</td>";
				}
				if ($login_fabrica != 42) echo "<tr style='display:none;' id='texto2-$i'>$insereHeader</tr>";
				if ($login_fabrica != 42) echo "<tr bgcolor='$cor' style='display:none;' id='texto-$i'>$linhaInfo</tr>";
				$tipo_anterior = $tipo;
			}
		}

		if (isFabrica(19)) {
			echo "<br>";
			echo "</td>";
			echo "<td rowspan='2'class='detalhes' width='1'></td>";
			echo "</tr>";
			echo "<tr bgcolor='#D9E2EF'>";
			echo "<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>";
			echo "</tr>";
		}else{
			echo "</tbody>";
		}
		echo "</table>";
		
		?>
			<!-- Retirando pois á paginação é feita via banco, tem fábricas que possuem muitos registros e o plugin irá travar a tela
			<script>
				$.dataTableLoad({ table: "#table_frmcomunicado" });
			</script> -->
		<?		

		echo "</form>\n";

	    ##### PAGINAÇÃO - INÍCIO #####
	    if($btn_acao != 'pesquisar_menu'){
		    echo "<br>";
		    echo "<div>";
	    
		    if ($pagina < $max_links)  $paginacao = pagina + 1;
		    else                     $paginacao = pagina;

		    // pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		    if (strlen($btn_acao_pre_os) ==0) {
		        $todos_links = $mult_pag->Construir_Links("strings", "sim");
		    }

		    // função que limita a quantidade de links no rodape
		    if (strlen($btn_acao_pre_os) ==0) {
		        $links_limitados    = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);
		    }

		    $contador_links_limitados = count($links_limitados);

		    for ($n = 0; $n < $contador_links_limitados; $n++) {
		        echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		    }

		    echo "</div>";

		    $resultado_inicial = ($pagina * $max_res) + 1;
		    $resultado_final   = $max_res + ( $pagina * $max_res);
		    if (strlen($btn_acao_pre_os) ==0) {
		        $registros         = $mult_pag->Retorna_Resultado();
		    }

		    $valor_pagina   = $pagina + 1;
		    if (strlen($btn_acao_pre_os) ==0) {
		        $numero_paginas = intval(($registros / $max_res) + 1);
		    }
		    if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		    if ($registros > 0){
		        echo "<br>";
		        echo "<div>";
		        echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
		        echo "<font color='#cccccc' size='1'>";
		        echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		        echo "</font>";
		        echo "</div>";
		    }
		}
	    ##### PAGINAÇÃO - FIM #####
	}else{
		echo "<h4>".traduz('Nenhum resultado encontrado')."</h4>";
	}
}

/*if (strlen($comunicado) > 0) {
	if (isFabrica(1)) {		//HD 10983
		$sql_cond1=" tbl_comunicado.pedido_em_garantia     IS NULL ";
		$sql_cond2=" tbl_comunicado.pedido_faturado        IS NULL ";
		$sql_cond3=" tbl_comunicado.digita_os              IS NULL ";
		$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS NULL ";

		if ($pedido_em_garantia     == "t") $sql_cond1 =" tbl_comunicado.pedido_em_garantia     IS NOT FALSE ";
		if ($pedido_faturado        == "t") $sql_cond2 =" tbl_comunicado.pedido_faturado        IS NOT FALSE ";
		if ($digita_os              == "t") $sql_cond3 =" tbl_comunicado.digita_os              IS TRUE ";
		if ($reembolso_peca_estoque == "t") $sql_cond4 =" tbl_comunicado.reembolso_peca_estoque IS TRUE ";
		$sql_cond_total = "AND ( $sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4) ";
	}

	$sql_cond_linha = "
						AND (tbl_comunicado.linha IN
								(
									SELECT tbl_linha.linha
									FROM tbl_posto_linha
									JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
									WHERE fabrica =$login_fabrica
										AND posto = $login_posto
								)
								OR (
										tbl_comunicado.comunicado IN (
											SELECT tbl_comunicado_produto.comunicado
											FROM tbl_comunicado_produto
											JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
											JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
											WHERE fabrica_i =$login_fabrica AND
												  tbl_posto_linha.posto = $login_posto

										)
									AND tbl_comunicado.produto IS NULL
								)
								OR tbl_comunicado.produto in
								(
									SELECT tbl_produto.produto
								 	FROM tbl_produto
								 	JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha
								 	WHERE fabrica_i = $login_fabrica AND
								 	posto = $login_posto
								)
								OR tbl_comunicado.linha IS NULL
							)";

	$sql = "SELECT  tbl_comunicado.comunicado                        ,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.referencia ELSE tbl_produto.referencia END AS prod_referencia,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.descricao  ELSE tbl_produto.descricao  END AS prod_descricao,
					tbl_comunicado.descricao                         ,
					tbl_comunicado.mensagem                          ,
					tbl_comunicado.video     						 ,
					tbl_comunicado.tipo                              ,
					tbl_comunicado.extensao                          ,
					to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
			FROM    tbl_comunicado
			LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
			LEFT JOIN tbl_produto            ON tbl_produto.produto               = tbl_comunicado.produto
			LEFT JOIN tbl_produto prod       ON prod.produto                      = tbl_comunicado_produto.produto
			WHERE   tbl_comunicado.fabrica    = $login_fabrica
			AND    (tbl_comunicado.tipo_posto = $tipo_posto   OR tbl_comunicado.tipo_posto IS NULL)
			AND     ((tbl_comunicado.posto    = $login_posto) OR (tbl_comunicado.posto     IS NULL))
			AND     tbl_comunicado.comunicado = $comunicado
			AND    tbl_comunicado.ativo IS TRUE ";

	if ($fabrica_multinacional and $login_pais)  $sql .= " AND tbl_comunicado.pais = '$login_pais' ";
	//HD 10983
	if (isFabrica(1)) {
		$sql .=" $sql_cond_total ";
	}

		$sql.=" $sql_cond_linha ";

	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) == 0) {
		$msg_erro = traduz("comunicado.inexistente",$con);
	}else{
		$Xcomunicado          = trim(pg_fetch_result($res, 0, 'comunicado'));
		$referencia           = trim(pg_fetch_result($res, 0, 'prod_referencia'));
		$descricao            = trim(pg_fetch_result($res, 0, 'prod_descricao'));
		$comunicado_descricao = trim(pg_fetch_result($res, 0, 'descricao'));
		$comunicado_tipo      = trim(pg_fetch_result($res, 0, 'tipo'));
		$comunicado_mensagem  = trim(pg_fetch_result($res, 0, 'mensagem'));
		$video				  = trim(pg_fetch_result($res, 0, 'video'));
		$comunicado_data      = trim(pg_fetch_result($res, 0, 'data'));
		$comunicado_extensao  = trim(pg_fetch_result($res, 0, 'extensao'));

		$gif = "comunicados/$Xcomunicado.gif";
		$jpg = "comunicados/$Xcomunicado.jpg";
		$pdf = "comunicados/$Xcomunicado.pdf";
		$doc = "comunicados/$Xcomunicado.doc";
		$rtf = "comunicados/$Xcomunicado.rtf";
		$xls = "comunicados/$Xcomunicado.xls";
		$ppt = "comunicados/$Xcomunicado.ppt";
	}
}*/

/*if ((strlen($comunicado) > 0) && (pg_num_rows($res) > 0) and isset($_GET['comunicado']) && !isFabrica(161)) {
	echo "<br />";

	echo "<table  align='center' class='table' width='400'>";
	echo "<tr>";
	if ($sistema_lingua <> 'ES')  echo "	<td align='left'><img src='imagens/cab_comunicado.gif'></td>";
	else echo "	<td align='left'><img src='imagens/cab_comunicado_es.gif'></td>";
	echo "</tr>";
	echo "<tr>";
	echo	"<td align='center' class='tipo'><b>$comunicado_tipo</b>&nbsp;&nbsp;-&nbsp;&nbsp;$comunicado_data</td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td align='center' class='descricao'><b>$descricao</b></td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td align='center' class='mensagem'>".nl2br($comunicado_mensagem)."</td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td align='center'>&nbsp;</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='left' >";

	if ($S3_online) {
        $s3->set_tipo_anexoS3($comunicado_tipo);
        $s3->temAnexos($Xcomunicado);
		$s3link = $s3->url;

		echo "<a href='$s3link' alt='S3' target='_blank'>";
		echo traduz("para.visualizar.o.arquivo",$con) .
			", <a href='$s3link' target='_blank'>" .
			traduz("clique.aqui",$con) .
			'</a>.' . chr(10);
	} else {
		if (file_exists($gif) == true) echo "	<img src='comunicados/$Xcomunicado.gif'>";
		if (file_exists($jpg) == true) echo "<img src='comunicados/$Xcomunicado.jpg'>";
		if (file_exists($doc) == true) echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.doc' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
		if (file_exists($rtf) == true) echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.rtf' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
		if (file_exists($xls) == true) echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.xls' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
		if (file_exists($ppt) == true) echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.ppt' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
		if (file_exists($pdf) == true) {
			echo "<div class='txt10Normal'><font color='#A02828'>">traduz("se.voce.nao.possui.o.acrobat.reader",$con,$cook_idioma)."&reg;</font> , <a href='http://www.adobe.com/products/acrobat/readstep2.html'>".traduz("instale.agora",$con,$cook_idioma)."</a>.</div>";
			echo "<br>";
			echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.pdf' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
		}
	}
	if (isFabrica(11,50) and $video<>''){	?>
		<P><A href="javascript:window.open('video.php?video=<?=$video?>','_blank',
			'toolbar=no, status=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);">
			Assistir vídeo anexado</A></P><?
	}*/
	/*
	if (strlen($comunicado_extensao)>0) {
		if ($comunicado_extensao=='ppt')
		echo "Para visualizar o arquivo, <a href='comunicados/$Xcomunicado.ppt' target='_blank'>clique aqui</a>.";
	}
	*/
/*	echo "</td>";
	echo "</tr>";
	echo "</table>";

	echo "<br><br>";

}else{
	echo "<br />";
}*/
?>

<!-- ------------------- Tipos de Comunicados Disponíveis -------------- -->

<?

	if (isFabrica(1)) {		//HD 10983
		$sql_cond1=" tbl_comunicado.pedido_em_garantia     IS NULL ";
		$sql_cond2=" tbl_comunicado.pedido_faturado        IS NULL ";
		$sql_cond3=" tbl_comunicado.digita_os              IS NULL ";
		$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS NULL ";

		if ($pedido_em_garantia == "t")     $sql_cond1 =" tbl_comunicado.pedido_em_garantia     IS NOT FALSE ";
		if ($pedido_faturado == "t")        $sql_cond2 =" tbl_comunicado.pedido_faturado        IS NOT FALSE ";
		if ($digita_os == "t")              $sql_cond3 =" tbl_comunicado.digita_os              IS TRUE ";
		if ($reembolso_peca_estoque == "t") $sql_cond4 =" tbl_comunicado.reembolso_peca_estoque IS TRUE ";
		$sql_cond_total="AND ( $sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4) ";
	}

	$cond_fabrica_produto = (in_array($login_fabrica, array(11,172))) ? " fabrica_i IN(11,172)" : " fabrica_i = $login_fabrica ";
	$cond_fabrica_posto = (in_array($login_fabrica, array(11,172))) ? " fabrica IN (11,172) " : " fabrica = $login_fabrica ";

	$sql_cond_linha = "
						AND (tbl_comunicado.linha IN
								(
									SELECT tbl_linha.linha
									FROM tbl_posto_linha
									JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
									WHERE $cond_fabrica_posto
										AND posto = $login_posto
								)
								OR (
										tbl_comunicado.comunicado IN (
											SELECT tbl_comunicado_produto.comunicado
											FROM tbl_comunicado_produto
											JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
											JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
											WHERE $cond_fabrica_produto AND
												  tbl_posto_linha.posto = $login_posto

										)
									AND tbl_comunicado.produto IS NULL
								)
								OR tbl_comunicado.produto in
								(
									SELECT tbl_produto.produto
								 	FROM tbl_produto
								 	JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha
								 	WHERE $cond_fabrica_produto AND
								 	posto = $login_posto
								)
								OR tbl_comunicado.linha IS NULL
							)";

//  18/01/2010 MLG - Ligação Adriana, pode mostrar os comunicados técnicos na tela
// 	    $sql_so_com_admin ="	AND tbl_comunicado.tipo NOT IN ('Vista Explodida','Esquema Elétrico',".
// 																"'Boletim Técnico','Estrutura do Produto',".
// 																"'Informativo tecnico','Descritivo técnico',".
// 																"'Árvore de Falhas','Politica de Manutenção',".
// 																"'Teste Rede Autorizada','Alterações Técnicas',".
// 																"'Apresentação do Produto','Manual Técnico')";

	$cond_fabrica_comunicado = (in_array($login_fabrica, array(11,172))) ? " tbl_comunicado.fabrica IN(11,172) " : " tbl_comunicado.fabrica = $login_fabrica ";
	$sql = "SELECT	tbl_comunicado.tipo,
					count(tbl_comunicado.*) AS qtde
			FROM	tbl_comunicado
			LEFT JOIN tbl_produto USING (produto)
			LEFT JOIN tbl_linha              ON tbl_produto.linha                 = tbl_linha.linha
			LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
			LEFT JOIN tbl_produto prod       ON prod.produto                      = tbl_comunicado_produto.produto
			LEFT JOIN tbl_posto_fabrica      ON tbl_posto_fabrica.posto           = $login_posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE	{$cond_fabrica_comunicado}
			AND    (tbl_comunicado.tipo_posto = $tipo_posto   OR  tbl_comunicado.tipo_posto IS NULL)
			AND    ((tbl_comunicado.posto     = $login_posto) OR (tbl_comunicado.posto      IS NULL))
			$cond_estado
			AND    tbl_comunicado.ativo      IS TRUE";
// 	if (isFabrica(14)) $sql .= $sql_so_com_admin;
	if ($fabrica_multinacional)  $sql .= " AND tbl_comunicado.pais = '$login_pais' ";

	//HD 10983
	if (isFabrica(1)) {
		$sql .=" $sql_cond_total ";
	}


	$sql.=" $sql_cond_linha ";

	if  (isFabrica(14, 66)) {    //29/03/2010 MLG - HD 220853
			$sql .=" AND 	CASE WHEN tbl_comunicado.tipo_posto IS NULL THEN TRUE
			                    ELSE
									CASE WHEN tbl_posto_fabrica.tipo_posto = tbl_comunicado.tipo_posto
									THEN TRUE
									ELSE FALSE
									END
							END ";
	}
	$sql .=" GROUP BY tbl_comunicado.tipo ORDER BY tbl_comunicado.tipo";
	
	$res = pg_query ($con,$sql);
	$contador_res = pg_num_rows($res);

	if ($contador_res > 0) {
		/*
		echo "<table class='table table-striped table-bordered table-fixed' id='table_tipo_comunicado'>";
		echo "<thead>";
		echo "<tr class='titulo_tabela'>";
		echo "<th colspan='2'>" . traduz('tipos.de.comunicados.disponiveis', $con) . "</th>";
		echo "</tr>";

		echo "<tr class='titulo_coluna'>";
		echo "<th>Tipo</th>";
		echo "<th>".traduz("qtde",$con,$cook_idioma)."</th>";
		echo "</tr></thead>";

		for ($i = 0 ; $i < $contador_res; $i++) {

			echo "<tr style='font-size:11px;'>";

			if (pg_fetch_result ($res,$i,tipo) == '') {
				echo "<td nowrap >";
				echo "<a href='comunicado_mostra_pesquisa.php?acao=PESQUISAR&tipo=zero'> ".traduz("sem.titulo",$con,$cook_idioma)." </a>";
				echo "</td>";
				echo "<td class='tac'>";
				echo pg_fetch_result ($res,$i,qtde);
				echo "</td>";
			}else{
				if (pg_fetch_result ($res,$i,tipo) =='Esquema Elétrico') {
					echo "<td nowrap>";
					echo "<a href='info_tecnica_arvore.php'>";
					echo pg_fetch_result ($res,$i,tipo);
					echo "</a>";
					echo "</td>";
					echo "<td class='tac'>";
					echo pg_fetch_result ($res,$i,qtde);
					echo "</td>";
					echo "</tr>";
				}else{
					echo "<td nowrap >";
					if (pg_fetch_result($res,$i,tipo)=='Esquema Elétrico Atual' AND $login_posto == 6359) {
						echo "<a href='comunicado_mostra_pesquisa_agrupado.php?acao=PESQUISAR&tipo=" . pg_fetch_result ($res,$i,tipo) . "'>";
					}
					$tipo = pg_fetch_result ($res,$i,tipo); 
					echo "<a href='comunicado_mostra.php?acao=PESQUISAR&tipo=" . pg_fetch_result ($res,$i,tipo) . "'>";

					if($login_fabrica == 19 && pg_fetch_result ($res,$i,tipo) == 'Promocao'){
						echo 'Formulários';
					}else{
						$x_tipo = pg_fetch_result ($res,$i,tipo);

						$x_tipo = (array_key_exists($x_tipo, $sel_tipos)) ? $sel_tipos["$x_tipo"] : $x_tipo;
						if ($x_tipo == 'Com. Contrato Posto') {
							$x_tipo = "Contrato";
						}

						echo $x_tipo; 
					}
					echo "</a>";
					echo "</td>";
					echo "<td class='tac'>";
					echo pg_fetch_result ($res,$i,qtde);
					echo "</td>";
					echo "</tr>";
				}
			}
			$total += "".pg_fetch_result ($res,$i,qtde);
		}
		if (isFabrica(14))  {?>
			<tr bgcolor='#ffeecc'>
				<td nowrap>
					<a href='comunicado_mostra_pesquisa.php?acao=PESQUISAR&tipo=todos'
					  title='Visualizar todos, inclusive os técnicos'>
						<?=traduz('visualizar.todos.os.comunicados', $con)?>
					</a>
				</td>
				<td align='right'><?=$total?></td>
			</tr>
<?		}

		echo "</table><br />";*/
	}elseif (!empty($btn_acao)) {
        if (!isFabrica(15)) {
            echo "<table width='700' align='center' border='0'>";
            echo "<tr class='texto_avulso'>";
            echo "<td>".traduz("nao.ha.comunicados.disponiveis",$con,$cook_idioma)."</td>";
            echo "</tr>";
            echo "</table>";
        }
	}

	if (isFabrica(14)) { // HD 44360
		?>
		<table align='center'>
			<tr bgcolor='#f0f0f0'>
				<td width='25'><img src='imagens/marca25.gif'></td>
				<td nowrap width='260'>
					<a href='comunicado_mostra_pesquisa_agrupado.php?acao=PESQUISAR' class='menu' target='_blank'>
					<?=traduz("informacoes.tecnicas",$con,$cook_idioma)?>
				</a>
				</td>
				<td nowrap class='descricao'>
					<?=traduz("informacoes.tecnicas.descricao",$con,$cook_idioma)?>
				</td>
			</tr>
		</table>
		<?
		include "rodape.php";
		exit;
	}

/**
 * <!-- ------------------- 10 Comunicados mais recentes -------------- -->
 **/

if (strlen($comunicado) == 0 and strlen($tipo) == 0 && strlen($listar_recentes) > 0){
	if (isFabrica(1)) {		//HD 10983
		$sql_cond1=" tbl_comunicado.pedido_em_garantia     IS NULL ";
		$sql_cond2=" tbl_comunicado.pedido_faturado        IS NULL ";
		$sql_cond3=" tbl_comunicado.digita_os              IS NULL ";
		$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS NULL ";

		if ($pedido_em_garantia == "t")     $sql_cond1 =" tbl_comunicado.pedido_em_garantia     IS TRUE ";
		if ($pedido_faturado == "t")        $sql_cond2 =" tbl_comunicado.pedido_faturado        IS TRUE ";
		if ($digita_os == "t")              $sql_cond3 =" tbl_comunicado.digita_os              IS TRUE ";
		if ($reembolso_peca_estoque == "t") $sql_cond4 =" tbl_comunicado.reembolso_peca_estoque IS TRUE ";
		$sql_cond_total="AND ($sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4 ) ";
	}

	$cond_fabrica_produto = (in_array($login_fabrica, array(11,172))) ? " fabrica_i IN(11,172)" : " fabrica_i = $login_fabrica ";
	$cond_fabrica_posto = (in_array($login_fabrica, array(11,172))) ? " fabrica IN (11,172) " : " fabrica = $login_fabrica ";

	$sql_cond_linha = "
				AND (tbl_comunicado.linha IN
						(
							SELECT tbl_linha.linha
							FROM tbl_posto_linha
							JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
							WHERE $cond_fabrica_posto
								AND posto = $login_posto
						)
						OR (
										tbl_comunicado.comunicado IN (
											SELECT tbl_comunicado_produto.comunicado
											FROM tbl_comunicado_produto
											JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
											JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
											WHERE $cond_fabrica_produto AND
												  tbl_posto_linha.posto = $login_posto

										)
									AND tbl_comunicado.produto IS NULL
								)
								OR tbl_comunicado.produto in
								(
									SELECT tbl_produto.produto
								 	FROM tbl_produto
								 	JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha
								 	WHERE {$cond_fabrica_produto} AND
								 	posto = $login_posto
								)
						OR tbl_comunicado.linha IS NULL
					)";

	$cond_fabrica_comunicado = (in_array($login_fabrica, array(11,172))) ? " tbl_comunicado.fabrica IN(11,172) " : " tbl_comunicado.fabrica = $login_fabrica ";

	$sql = "SELECT	tbl_comunicado.comunicado,
					tbl_comunicado.fabrica,
					tbl_comunicado.descricao,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.referencia ELSE tbl_produto.referencia END AS referencia,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.descricao  ELSE tbl_produto.descricao  END AS produto_descricao,
					to_char (tbl_comunicado.data,'dd/mm/yyyy') AS data ,
					tbl_comunicado.video,
					tbl_comunicado.tipo,
					tbl_comunicado.mensagem
			FROM	tbl_comunicado
			LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
			LEFT JOIN tbl_produto            ON tbl_produto.produto               = tbl_comunicado.produto
			LEFT JOIN tbl_produto prod       ON prod.produto                      = tbl_comunicado_produto.produto
			WHERE	{$cond_fabrica_comunicado}
			AND    (tbl_comunicado.tipo_posto = $tipo_posto   OR  tbl_comunicado.tipo_posto IS NULL)
			AND     ((tbl_comunicado.posto    = $login_posto) OR (tbl_comunicado.posto      IS NULL))
			AND    tbl_comunicado.ativo IS TRUE ";
	  
	 

	if (isFabrica(1)) {
		$sql .=" $sql_cond_total";
	}


	$sql.=" $sql_cond_linha ";

    if (isFabrica(15)) {
        $sql.=" ORDER BY tbl_comunicado.data DESC LIMIT 100" ;
    }else{
        $sql.=" ORDER BY tbl_comunicado.data DESC LIMIT 10" ;
    }


	$res = pg_query ($con,$sql);
	$contador_res = pg_num_rows($res);

	if ($contador_res > 0) {
        echo "<br />";
		echo "<table id='table-list2' class='table table-bordered table-striped table-fixed'>";
	   	echo "<thead>";
	    echo "<tr class='titulo_tabela'>";
	    echo "<th colspan='5'>";
        if($login_fabrica == 15){
			echo " 100 ";
			fecho("comunicados.mais.recentes",$con,$cook_idioma);
        }else{
			echo " 10 ";
			fecho("comunicados.mais.recentes",$con,$cook_idioma);
        }
	    echo "</th>";
	    echo "</tr>";
	    echo "<tr class='titulo_coluna'>";
	    echo "<th>";
	    fecho("data",$con,$cook_idioma);
	    echo "</th>";
	    echo "<th>";
	    fecho ("descricao./.produto",$con,$cook_idioma);
	    echo "</th>";
	    echo "<th>";
	    fecho("tipo",$con,$cook_idioma);
	    echo "</th>";
	    echo "<th>";;
	    fecho("arquivo",$con,$cook_idioma);
	    echo "</th>";
	    echo "</tr>";
		echo "</thead>";
		echo "<tbody class='Conteudo'>";
		for ($i = 0 ; $i < $contador_res; $i++) {
			$fabrica_comunicado = pg_fetch_result ($res,$i,"fabrica");
			$mensagem = pg_fetch_result ($res,$i,"mensagem");
			$referencia = pg_fetch_result($res,$i,'referencia');
			$descricao  = pg_fetch_result($res,$i,'descricao');
			echo "<tr>";
			echo "<td align='center' style='font-size:11px'>";
			echo pg_fetch_result ($res,$i,data);
			echo "</td>";

			echo "<td style='font-size:11px'>";
			if (!empty($referencia)) {
				echo pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,produto_descricao);
			}

			if (!empty($referencia) && !empty($descricao)) {
				echo "<br />";
			}

			if (!empty($descricao)) {
				if (!empty($mensagem)) {
					echo "<a href =\"javascript:MostraEsconde('$i')\"><img id='img-desc-$i' src='imagens/sort_desc.gif'>";
				}
				echo pg_fetch_result ($res,$i,descricao);

			}
			echo "</td>";

			echo "<td style='font-size:11px'>";
			echo pg_fetch_result ($res,$i,'tipo');
			echo "</td>";

			echo "<td nowrap>";
				if (isFabrica(11,50,172) and trim(pg_fetch_result($res,$i,video)<>'')) {
					echo "<A href=\"#\" onclick=\"window.open('video.php?video=".trim(pg_fetch_result($res,$i,video))."','_blank'," .
						 "'toolbar=no, status=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);\">" .
						 traduz('abrir.video', $con) . "</A>";
				}else{
					//echo "<a href='$PHP_SELF?comunicado=" . urlencode (pg_fetch_result ($res,$i,comunicado)) . "' target='_blank'>";
					$comunicado = pg_fetch_result ($res,$i,'comunicado');

					if(in_array($login_fabrica, array(11,172))){
						$s3 = ($fabrica_comunicado == 172) ? $s3_172 : $s3_11;
					}

		            if ($S3_online) {
		                $tipo_s3 = in_array(pg_fetch_result ($res,$i,'tipo'), explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co'; //Comunicado técnico?
		                if ($s3->tipo_anexo != $tipo_s3)
		                    $s3->set_tipo_anexoS3($tipo_s3);

		                $s3->temAnexos($comunicado);
	                    $flagTemLink = $s3->url;
	                    if (!empty($flagTemLink)) {
	                        echo "<a href='$flagTemLink' alt='S3' target='_blank'>";
	                    }
		            }
					fecho(array('visualizar'),$con);
					echo "</a>";
					    }
			echo "</td>";
			echo "</tr>";
			if (!empty($mensagem)) {
				echo "<tr bgcolor='$cor'>";
				echo "<td style='display:none;' colspan='5' id ='texto-$i'><br>". str_replace("\r", "<br>", $mensagem) ."<br>&nbsp;</td>";
				echo "</tr>";
			}
		}
		echo "</tbody>";
		echo "</table>";
		?>
		<?php
	}
}
?>

<!-- MOSTRA RESULTADO DE BUSCA OU 5 PRIMEIRO REGISTROS -->
<?
if (1==2 and strlen($comunicado) == 0){

	if ($btn_acao == "pesquisar") {
		$sql = "SELECT  tbl_comunicado.comunicado                        ,
						tbl_produto.referencia AS prod_referencia        ,
						tbl_produto.descricao  AS prod_descricao         ,
						tbl_comunicado.descricao                         ,
						tbl_comunicado.mensagem                          ,
						tbl_comunicado.video     						 ,
						tbl_comunicado.tipo                              ,
						to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
				FROM    tbl_comunicado
				LEFT JOIN    tbl_produto USING (produto)
				LEFT JOIN    tbl_linha   USING (linha)
				WHERE   tbl_comunicado.fabrica    = $login_fabrica
				AND    (tbl_comunicado.tipo_posto = $tipo_posto   OR  tbl_comunicado.tipo_posto IS NULL)
				AND     ((tbl_comunicado.posto    = $login_posto) OR (tbl_comunicado.posto      IS NULL))
				AND    tbl_comunicado.ativo IS TRUE
				AND ( 1=2 ";
				
		// por linha de produto
		if (strlen($chk1) > 0) {
			if (strlen($linha) > 0) {
				$monta_sql .= "OR tbl_linha.linha = $linha ";
				$dt = 1;
			}
		}

		// por tipo de comunicado
		if (strlen($chk4) > 0) {
			if (strlen($tipo) > 0) {
				$monta_sql .= "OR tbl_comunicado.tipo = '$tipo' ";
				$dt = 1;
			}
		}

		// entre datas
		if (strlen($chk2) > 0) {
			if ((strlen($data_inicial_01) == 10) && (strlen($data_final_01) == 10)) {
				$monta_sql .= "OR (tbl_comunicado.data BETWEEN fnc_formata_data('$data_inicial_01') AND fnc_formata_data('$data_final_01')) ";
				$dt = 1;
			}
		}

		// referencia do produto
		if (strlen($chk3) > 0) {
			if ($produto_referencia) {
				if ($dt == 1) $xsql = "AND ";
				else          $xsql = "OR ";

				$monta_sql .= "$xsql tbl_produto.referencia = '". $produto_referencia ."' ";
				$dt = 1;
			}
		}

		$monta_sql .= ") GROUP BY
					tbl_comunicado.comunicado,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_comunicado.descricao,
					tbl_comunicado.mensagem,
					tbl_comunicado.tipo,
					tbl_comunicado.data ";
				if (isFabrica(3))
					$monta_sql .= "ORDER BY tbl_produto.descricao ASC";
				else
					$monta_sql .= "ORDER BY tbl_comunicado.data DESC";

		// ordena sql padrao
		$sql .= $monta_sql;

		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";

		//echo "<br>".nl2br($sql)."<br><br>".nl2br($sqlCount)."<br><BR>";
		// ##### PAGINACAO ##### //
		require "_class_paginacao.php";

		// definicoes de variaveis
		$max_links = 11;				// máximo de links à serem exibidos
		$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

		// ##### PAGINACAO ##### //

	}else{
		if (isFabrica(1)) {		//HD 10983
			$sql_cond1=" tbl_comunicado.pedido_em_garantia     IS NULL ";
			$sql_cond2=" tbl_comunicado.pedido_faturado        IS NULL ";
			$sql_cond3=" tbl_comunicado.digita_os              IS NULL ";
			$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS NULL ";

			if ($pedido_em_garantia     == "t") $sql_cond1 = " tbl_comunicado.pedido_em_garantia     IS TRUE ";
			if ($pedido_faturado        == "t") $sql_cond2 = " tbl_comunicado.pedido_faturado        IS TRUE ";
			if ($digita_os              == "t") $sql_cond3 = " tbl_comunicado.digita_os              IS TRUE ";
			if ($reembolso_peca_estoque == "t") $sql_cond4 = " tbl_comunicado.reembolso_peca_estoque IS TRUE ";
			$sql_cond_total="AND ( $sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4) ";
		}
		// seleciona os 5 ultimos
		$sql = "SELECT  tbl_comunicado.comunicado                        ,
						tbl_produto.referencia AS prod_referencia        ,
						tbl_produto.descricao  AS prod_descricao         ,
						tbl_comunicado.descricao                         ,
						tbl_comunicado.mensagem                          ,
						tbl_comunicado.video     						 ,
						tbl_comunicado.tipo                              ,
						to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
				FROM    tbl_comunicado
				LEFT JOIN tbl_produto USING (produto)
				LEFT JOIN tbl_linha   USING (linha)
				WHERE   tbl_comunicado.fabrica    = $login_fabrica
				AND    (tbl_comunicado.tipo_posto = $tipo_posto    OR tbl_comunicado.tipo_posto IS NULL)
				AND     ((tbl_comunicado.posto    = $login_posto)  OR (tbl_comunicado.posto     IS NULL))
				AND    tbl_comunicado.ativo IS TRUE ";


		//HD 10983
		if (isFabrica(1)) {
			$sql .=" $sql_cond_total ";
		}
		$sql.=" ORDER BY tbl_comunicado.data DESC
				LIMIT 5 OFFSET 0 ";

		$sqlCount = "";
		$res = pg_query($con,$sql);
	}

	$contador_res = pg_num_rows($res);

	##### PAGINAÇÃO - INÍCIO #####
	$sqlCount  = "SELECT count(*) FROM (" . $sql . ") AS count";

	require "_class_paginacao.php";

	// Definições de variáveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
	##### PAGINAÇÃO - FIM #####	

	if ($contador_res > 0) {
		echo "<table id='table_frmcomunicado' class='table tabela scrollableTable' width='700' >";
		echo "<tr>";
		echo "<td align='left'><img src='imagens/cab_outrosregistrosreferentes.gif'></td>";
		echo "</tr>";
		echo "</table>";

		echo "<br>";

		echo "<table class='table' align='center' width='500' border=0>";
		for ($x = 0 ; $x < $contador_res; $x++) {
			$comunicado           = trim(pg_fetch_result($res, $x, 'comunicado'));
			$referencia           = trim(pg_fetch_result($res, $x, 'prod_referencia'));
			$descricao            = trim(pg_fetch_result($res, $x, 'prod_descricao'));
			$comunicado_descricao = trim(pg_fetch_result($res, $x, 'descricao'));
			$comunicado_tipo      = trim(pg_fetch_result($res, $x, 'tipo'));
			$comunicado_mensagem  = trim(pg_fetch_result($res, $x, 'mensagem'));
			$video                = trim(pg_fetch_result($res, $x, 'video'));
			$comunicado_data      = trim(pg_fetch_result($res, $x, 'data'));

			echo "<tr>\n";
			echo "	<td class='txt10Normal'>$comunicado_data</td>\n";
			echo "	<td><a href='$PHP_SELF?comunicado=$comunicado'>$comunicado_tipo</a></td>\n";
			echo "	<td class='txt10Normal'>$descricao\n";
			if (isFabrica(50) and $video<>""){	?>
				<p><a href="javascript:window.open('/assist/video.php?video=<?$video?>','_blank',
					'toolbar=no, status=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);">
					<?=traduz('assistir.video.anexado', $con)?></a></p><?
			}
			echo "\n\t</td>\n</tr>\n";
		}
		echo "</table>\n";

		?>
		<!-- Retirando pois á paginação é feita via banco, tem fábricas que possuem muitos registros e o plugin irá travar a tela
			<script>
				$.dataTableLoad({ table: "#table_frmcomunicado" });
			</script> -->
		<?		

		##### PAGINAÇÃO - INÍCIO #####
		
		// Links da paginação
		echo "<br>";
		echo "<div><center>";

		if($pagina < $max_links) $paginacao = pagina + 1;
		else                     $paginacao = pagina;

		// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		$todos_links		= $mult_pag->Construir_Links("strings", "sim");

		// função que limita a quantidade de links no rodape
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

		for ($n = 0; $n < count($links_limitados); $n++) {
			echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		}

		echo "</center></div>";

		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0){
			echo "<br>";
			echo "<div><center>";
			fecho("resultados.de.%.a.%.do.total.de.%.registros",$con,$cook_idioma,array($resultado_inicial,$resultado_final,$registros));
			echo " <font color='#CCCCCC' size='1'>(".traduz("pagina.%.de.%",$con,$cook_idioma,array($valor_pagina,$numero_paginas))."</font>";

			echo "</center></div>";
		}
		##### PAGINAÇÃO - FIM #####
	}else{
		fecho("nao.ha.registro.para.esta.opcao",$con,$cook_idioma);
	}

	if (strlen($btn_acao) > 0) {

		// ##### PAGINACAO ##### //

		// links da paginacao
		echo "<br>";

		echo "<div>";

		if ($pagina < $max_links)  {
			$paginacao = pagina + 1;
		}else{
			$paginacao = pagina;
		}

		// paginacao com restricao de links da paginacao

		// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		$todos_links		= $mult_pag->Construir_Links("strings", "sim");

		// função que limita a quantidade de links no rodape
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

		$contador_links_limitados = count($links_limitados);

		for ($n = 0; $n < $contador_links_limitados; $n++) {
			echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		}

		echo "</div>";

		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0){
			echo "<br>";
			echo "<div>";
			fecho('resultados.de.%.a.%.do.total.de.%.registros', $con, $cook_idioma, array($resultado_inicial,$resultado_final,$registros));
			echo "<font color='#cccccc' size='1'>";
			fecho('pagina.%.de.%', $con, $cook_idioma, array($valor_pagina, $numero_paginas));
			echo "</font>";
			echo "</div>";
		}

		// ##### PAGINACAO ##### //
	}
}

include "rodape.php";

?>
<script type="text/javascript">
	$('[data-comunicado]').on('click', function(){
		Shadowbox.init();
		
		Shadowbox.open({
			content :   "shadowbox_view_comunicado.php?comunicado=" + $(this).data('comunicado'),
			player  :   "iframe",
			title   :   "Visualização de Comunicado",
			width   :   800,
			height  :   600
		});
	});


<?php if ($login_fabrica == 42) { ?>

	$(document).ready(function() {

		var selectPsq = $('#palavra_chave');
		selectPsq.select2({
			tags: true,
			allowClear:true,
			placeholder:"", 
			language: {noResults: function(){return "";}}
		});

		var dataPsq = selectPsq.attr('data-palavra');
		if(dataPsq != ""){
			var newOption = new Option(dataPsq, dataPsq, false, true);
			selectPsq.append(newOption).trigger('change');
		}
	})

<?php } ?>


</script>


