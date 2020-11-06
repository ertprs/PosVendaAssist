<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "funcoes.php";

if(isFabrica(141,144)){

	include_once S3CLASS;

	$s3 = new AmazonTC("pedido", $login_fabrica);

}

if (in_array($login_fabrica, array(138))) {
	include_once __DIR__ . DIRECTORY_SEPARATOR . 'class/tdocs.class.php';
	$tDocs   = new TDocs($con, $login_fabrica);
}

$mostra_data_aprovacao = in_array($login_fabrica, array(138));

// HD 14351
$pedido_obs = trim($_GET['pedido']);
if ($_GET['obs']==1 and strlen($pedido_obs) > 0) {
	$sql="SELECT obs FROM tbl_pedido WHERE pedido=$pedido_obs";
	$res=pg_exec($con,$sql);
	$pedido_obs = pg_result($res,0,obs);
	echo "<center>"; fecho("observacao.do.pedido:.%",$con,$cook_idioma,$pedido_obs); echo "<br> </center>";
	exit;
}

// function traduz_status_pedido($status_pedido){
//
// 	global  $con;
// 	global  $cook_idioma;
//
// 	$sql = "SELECT msg_id FROM tbl_msg WHERE msg_text = '$status_pedido'::text limit 1;";
// 	$res = pg_exec($con,$sql);
// 	if(pg_numrows($res) > 0){
// 		$status_traduz = pg_result($res,0,0);
// 		$status_traduz = traduz("$status_traduz",$con,$cook_idioma);
// 		return $status_traduz;
// 	}else{
// 		$status_traduz = $status_pedido;
// 		return $status_traduz;
// 	}
// }


function verifica_tipo_posto($tipo, $valor, $id_posto = null) {
    global $con, $login_fabrica, $login_posto, $areaAdmin, $posto_id;

    if (empty($areaAdmin)) {
        $areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
    }

    if (empty($id_posto)) {
        $id_posto  = ($areaAdmin == true) ? $posto_id : $login_posto;    
    }

    $sql = "
        SELECT tbl_tipo_posto.tipo_posto
        FROM tbl_posto_fabrica
        INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
        WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
        AND tbl_posto_fabrica.posto = {$id_posto}
        AND tbl_tipo_posto.{$tipo} IS {$valor}
    ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        return true;
    } else {
        return false;
    }
}


function createHTMLButton($caption, $action, $attr='', $type = 'button') {
	return "<button type='$type' $attr onClick=\"" . str_replace('"', '\"', $action) . "\">" . $caption . "</button>\n";
}

$btn_gravar = $_POST['btn_gravar'];
if(strlen($btn_gravar)>0){


	$total_pedido = $_POST['total_pedido'];
	//echo "total: $total_pedido<BR>";
	if($total_pedido>0){
		for($x=0;$x<$total_pedido;$x++){

			$data_recebimento   = $_POST['data_recebimento_'.$x];
			$pedido_recebimento = $_POST['pedido_recebimento_'.$x];
			$data_recebimento   = fnc_formata_data_pg($data_recebimento);
			$data_recebimento   = str_replace("'","",$data_recebimento);

            //	echo "$pedido_recebimento - $data_recebimento";
            if(strlen($data_recebimento)>0 and $data_recebimento<>'null' and strlen($pedido_recebimento)>0){
                $sql = "UPDATE tbl_pedido
                           SET recebido_posto = '$data_recebimento'
                         WHERE pedido         = $pedido_recebimento
                           AND fabrica        = $login_fabrica";
                $res = pg_exec($con,$sql);
                $msg_erro = pg_errormessage($con);

                if(strlen($msg_erro)==0 and 1==2){
                    $res = pg_exec ($con,"BEGIN TRANSACTION");
                    $sql = "SELECT fn_estoque ($pedido_recebimento,$login_fabrica,'$data_recebimento')";
                    $res = pg_exec($con,$sql);
                    $msg_erro = pg_errormessage($con);
                    //echo $sql;
                    if(strlen($msg_erro)==0){
                    $res = pg_exec ($con,"COMMIT TRANSACTION");
                    }
                }
    //echo $sql;
            }
		}
	}

}

$msg_erro="";
if (strlen($_GET["excluir"]) > 0) $excluir = $_GET["excluir"];
if (strlen($_GET["alterar"]) > 0) $alterar = $_GET["alterar"];
if (strlen($_GET["posto_senha"]) > 0) $posto_senha = trim($_GET["posto_senha"]); // HD 56032



if(isFabrica(3, 85) and strlen($excluir) > 0) {

	if(strlen($posto_senha) > 0) {
		$sql = "SELECT senha FROM tbl_posto_fabrica WHERE posto= $login_posto AND fabrica = $login_fabrica";
		$res=pg_exec($con,$sql);
		$senha = pg_result($res,0,senha);
		if(md5($senha) <> $posto_senha) $msg_erro=traduz("senha.invalida");
	}else{
		$msg_erro=traduz("digite.a.senha.para.excluir.o.pedido");
	}
}

if (strlen($alterar) > 0 and strlen($msg_erro) > 0){
	$pedido = $_POST['pedido'];
	$sql = "UPDATE tbl_pedido SET status_pedido = 1 where pedido = $pedido and fabrica = $login_fabrica";
	$res = pg_query($con,$sql);

	$msg_erro = pg_last_error();

	if (strlen($msg_erro) == 0) {
		header("Location: $PHP_SELF?listar=todas");
		exit;
	}
}


if (strlen($excluir) > 0 and strlen($msg_erro) == 0) {

	$cond_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_pedido.fabrica IN(11,172) " : " tbl_pedido.fabrica = $login_fabrica ";

	$sql = "SELECT pedido,
					tipo_pedido,
					exportado
			FROM tbl_pedido
			WHERE $cond_fabrica
			AND   tbl_pedido.posto   = $login_posto
			AND   tbl_pedido.pedido  = $excluir
			AND   tbl_pedido.exportado IS NULL;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$tipo_pedido = trim(pg_result($res,0,tipo_pedido));
		$exportado   = trim(pg_result($res,0, exportado));

		//hd 17227 9/4/2008
		/*$sql =	"DELETE FROM tbl_pedido
				WHERE tbl_pedido.pedido  = $excluir
				AND   tbl_pedido.posto   = $login_posto
				AND   tbl_pedido.fabrica = $login_fabrica
				AND   tbl_pedido.exportado IS NULL;";*/

		//a pedido de Tulio nao deletar pq esta matando o banco, mover para fabrica 0 (zero)
		if (strlen($exportado)==0){
			$res = @pg_exec($con,"BEGIN TRANSACTION");

			$sql = "UPDATE tbl_pedido_item
					set qtde_cancelada = tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada
					where pedido = $excluir;";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			$sqlx = "SELECT tbl_pedido.posto,
						tbl_pedido.fabrica,
						tbl_pedido_item.pedido,
						tbl_pedido_item.qtde_cancelada,
						tbl_pedido_item.pedido_item,
						tbl_peca.peca,
						tbl_os.os
						FROM    tbl_pedido
						JOIN    tbl_pedido_item  ON tbl_pedido_item.pedido    = tbl_pedido.pedido
						JOIN    tbl_peca         ON tbl_peca.peca             = tbl_pedido_item.peca
						LEFT JOIN    tbl_os_item ON tbl_os_item.peca          = tbl_pedido_item.peca
												AND tbl_os_item.pedido        =  tbl_pedido.pedido
						LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						LEFT JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
						WHERE tbl_pedido_item.pedido = $excluir";
			$resx = pg_exec($con,$sqlx);

			for ($i = 0 ; $i < pg_numrows ($resx) ; $i++) {
					$posto       = pg_fetch_result($resx, $i, 'posto');
					$fabrica     = pg_fetch_result($resx, $i, 'fabrica');
					$pedido      = pg_fetch_result($resx, $i, 'pedido');
					$pedido_item = pg_fetch_result($resx, $i, 'pedido_item');
					$qtde        = pg_fetch_result($resx, $i, 'qtde_cancelada');
					$peca        = pg_fetch_result($resx, $i, 'peca');
					$os          = pg_fetch_result($resx, $i, 'os');

				if(strlen($os)== 0) $os = "null";

				$sql = "INSERT INTO tbl_pedido_cancelado(
							pedido  ,
							posto   ,
							fabrica ,
							os      ,
							peca    ,
							qtde    ,
							motivo  ,
							data    ,
							pedido_item
							)values(
							'$pedido',
							'$posto',
							'$fabrica',
							$os,
							'$peca',
							'$qtde',
							'Pedido cancelado pelo posto em ('||current_timestamp||')',
							current_date,
							$pedido_item
						);";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			$sql = "UPDATE tbl_pedido SET status_pedido = 14
				WHERE pedido  = $excluir;
				SELECT fn_atualiza_status_pedido($login_fabrica,$excluir)";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (strlen ($msg_erro) == 0) {
				$res = @pg_exec ($con,"COMMIT TRANSACTION");
			}else{
				$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			}

		}else{

			$cond_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_pedido.fabrica IN(11,172) " : " tbl_pedido.fabrica = $login_fabrica ";

			$sql =	"UPDATE tbl_pedido
					    SET fabrica = 0
					 WHERE tbl_pedido.pedido  = $excluir
                       AND tbl_pedido.posto   = $login_posto
                       AND $cond_fabrica
                       AND tbl_pedido.exportado IS NULL;";
			$res = @pg_exec($con,$sql);
		}


		# Rotina para voltar a peça para o estoque da peça para a Loja Virtual -- Fabio 13/09/2007
		if(isFabrica(3, 85) AND $tipo_pedido=='2'){

			if (strlen($msg_erro)==0){
				$sql = "UPDATE tbl_peca
						SET qtde_disponivel_site = qtde_disponivel_site + tbl_pedido_item.qtde
						FROM tbl_pedido
						JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
						WHERE tbl_pedido.pedido  = $excluir
						AND tbl_pedido_item.peca = tbl_peca.peca
						AND tbl_pedido.fabrica   = $login_fabrica
						AND tbl_pedido.posto     = $login_posto
						AND tbl_pedido.pedido_loja_virtual IS TRUE
						AND qtde_disponivel_site IS NOT NULL
						AND tbl_pedido.exportado IS NULL";
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
			#outro jeito que eu fiz, mas o anterior é melhor
			if (1==2){

				$cond_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_pedido.fabrica IN(11,172) " : " tbl_pedido.fabrica = $login_fabrica ";

				$sql2 = "SELECT
							tbl_pedido_item.pedido_item,
							tbl_pedido_item.peca,
							tbl_pedido_item.qtde
					FROM  tbl_pedido
					JOIN  tbl_pedido_item USING (pedido)
					JOIN  tbl_peca        USING (peca)
					WHERE tbl_pedido.pedido_loja_virtual IS TRUE
					AND   tbl_pedido.exportado IS NULL
					AND   tbl_pedido.posto   = $login_posto
					AND   {$cond_fabrica}
					AND   tbl_pedido.pedido  =  $excluir
					ORDER BY tbl_pedido.pedido DESC";

				$res2 = pg_exec ($con,$sql2);
				if (pg_numrows($res2) > 0) {
					for($i=0; $i< pg_numrows($res2); $i++) {
						$pedido_item     = trim(pg_result($res2,$i,pedido_item));
						$peca            = trim(pg_result($res2,$i,peca));
						$qtde_remover    = trim(pg_result($res2,$i,qtde));
						if (strlen($msg_erro) == 0) {
							$sql3 = "UPDATE tbl_peca
									SET   qtde_disponivel_site = qtde_disponivel_site + $qtde_remover
									WHERE peca     = $peca
									AND   fabrica  = $login_fabrica";
							$res3 = pg_exec ($con,$sql3);
							$msg_erro = pg_errormessage($con);
						}
					}
				}
			}
		}

		$msg_erro = pg_errormessage($con);



		if (strlen($msg_erro) == 0) {
			header("Location: $PHP_SELF?listar=todas");
			exit;
		}
	}
}

$title = traduz("relacao.de.pedido.de.pecas",$con,$cook_idioma);
$layout_menu = "pedido";
include "cabecalho.php";

?>


<?
include "javascript_calendario_new.php";
include "js/js_css.php";

if(strlen($data_inicial)==0 AND strlen($data_final)==0){
	$fnc  = @pg_exec($con,"SELECT TO_CHAR(CURRENT_DATE - INTERVAL '30 days','DD/MM/YYYY');");
	$data_inicial = @pg_result ($fnc,0,0);

	if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
	$data_final = date("d/m/Y");
}
?>
<script type="text/javascript" src="js/thickbox.js"></script>
<script type="text/javascript" src="js/md5.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script src="plugins/shadowbox/shadowbox.js"    type="text/javascript"></script>
<script type="text/javascript">

	var traducao = {
		aguarde_submissao:	   '<?=traduz('aguarde.submissao', $con, $cook_idioma)?>',
		senha_exclusao_pedido: '<?=traduz('informe.a.senha.para.excluir.o.pedido', $con)?>'
	};

	$(function()
	{
        $('#data_inicial').datepick({startdate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});

	function excluirPedido(pedido){
		var senha = prompt(traducao.senha_exclusao_pedido, '');
		if(senha.value!="") {
			window.location = "<?=$PHP_SELF?>?excluir="+pedido+"&posto_senha="+hex_md5(senha);
		}
	}

	<?php

	if(isFabrica(141,144)){

		?>

		function inserirComprovante(pedido){

			Shadowbox.init();
			Shadowbox.open({ content: "upload_comprovante_pagamento.php?pedido="+pedido, player: "iframe", width: 540, height: 300 });

		}

		<?php

	}

	?>

</script>
<style>
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Pesquisa{
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: none;
	color: #333333;
	border:#485989 1px solid;
	background-color: #EFF4FA;
}

.Pesquisa caption {
	font-size:14px;
	font-weight:bold;
	color: #FFFFFF;
	background-color: #596D9B;
	text-align:'left';
	text-transform:uppercase;
	padding:0px 5px;
}

.Pesquisa thead td{
	text-align: center;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}

.Pesquisa tbody th{
	font-size: 12px;
	font-weight: none;
	text-align:'left';
	color: #333333;
}
.Pesquisa tbody td{
	font-size: 10px;
	font-weight: none;
	text-align:'left';
	color: #333333;
}

.Pesquisa tfoot td{
	font-size:10px;
	font-weight:bold;
	color: #000000;
	text-align:'left';
	text-transform:uppercase;
	padding:0px 5px;
}

.texto_avulso{
    font: 14px Arial;
	color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: justify;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
	text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.espaco{
	padding: 0 0 0 140px
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
	text-align:center;
	text-transform: capitalize;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #ACACAC;
	empty-cells:show;
}
</style>
<p>

<? if (isFabrica(1)) { ?>
<? ##FRASE TRADUÇÂO ?>
<font size="2" face="Geneva, Arial, Helvetica, san-serif" color="#FF0000"><b>
	<?php echo traduz("prezado.assistente.quando.existir.um.pedido.feito.pelo.pessoal.da.black.e.decker.ira.aparecer.na.coluna.black.o.nome.do.usuario.que.o.efetuou.caso.contrario.foi.um.pedido.feito.pela.propria.assistencia");?>.
</b></font>
<br><br><br>
<font size="2" face="Geneva, Arial, Helvetica, san-serif" color="#FF0000"><b>
<?php echo traduz("pedidos.nao.finalizados.devem.ser.cancelados.ou.finalizados.para.que.sejam.faturados");?>.
<?php echo traduz("estes.pedidos.nao.devem.ficar.em.aberto.no.sistema.para.evitarmos.transtornos.futuros");?>.
<?php echo traduz("caso.queria.finalizar.o.pedido.ou.excluir.o.mesmo.clique.no.numero.do.mesmo.e.delete.ou.finalize");?>.
</b></font>
<br><br><br>
<? } ?>

<?php

if (isFabrica(138, 143)) {
	$status_pedido = isFabrica(138) ? array(17, 23) : (array)19;

	$sql = "SELECT
				tbl_pedido.pedido,
				TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data,
				tbl_tipo_pedido.descricao AS tipo_pedido,
				tbl_status_pedido.descricao AS status_pedido,
				tbl_status_pedido.status_pedido AS id_status_pedido,
				tbl_pedido.total
			FROM tbl_pedido
			INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
			INNER JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
			WHERE tbl_pedido.fabrica = {$login_fabrica}
			AND tbl_pedido.posto = {$login_posto}
			AND tbl_pedido.status_pedido IN(".implode(",", $status_pedido).")";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$rows = pg_num_rows($res);
		?>

		<br />
		<table class="tabela" style="width: 700px;" >
			<thead>
				<tr>
					<th colspan="5" style="color: #FFFFFF; background-color: #D90000;" ><?php echo traduz("pedidos.pendentes.de.aprovacao");?></th>
				</tr>
				<tr class="titulo_coluna">
					<th><?php echo traduz("pedido");?></th>
					<th><?php echo traduz("data");?></th>
					<th><?php echo traduz("tipo.pedido");?></th>
					<th><?php echo traduz("status");?></th>
					<th><?php echo traduz("valor.total");?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $rows; $i++) {
					$pedido = pg_fetch_result($res, $i, "pedido");
					$data = pg_fetch_result($res, $i, "data");
					$tipo_pedido = pg_fetch_result($res, $i, "tipo_pedido");
					$status_pedido = pg_fetch_result($res, $i, "status_pedido");
					$id_status_pedido = pg_fetch_result($res, $i, "id_status_pedido");
					$total = number_format(pg_fetch_result($res, $i, "total"), 2, ",", ".");
					$xtotal = "-------";
                    if (!in_array($login_fabrica, array(143)) || in_array($login_fabrica, array(143)) && $id_status_pedido <> 19) {
                    	$xtotal = $total;
                    }

					echo "
					<tr>
						<td><a href='pedido_finalizado.php?pedido=$pedido' target='_blank'>{$pedido}</a></td>
						<td>$data</td>
						<td>$tipo_pedido</td>
						<td>$status_pedido</td>
						<td class='tac'>{$xtotal}</td>
					</tr>
					";

				}
				?>
			</tbody>
		</table>
		<br />
	<?php
	}
}

?>

<? if(isFabrica(3)) { ?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
	<? echo $msg_erro;?>
	</td>
</tr>
</table>
<? } ?>
<table width="700px" align="center" border="0" cellspacing="2" cellpadding="1" bgcolor='#D9E2EF' class='formulario'>
<? if(strlen($msg_erro)>0) { ?>
<tr>
	<td valign="middle" align="center" class='error'>
	<? echo $msg_erro;?>
	</td>
</tr>

<? } ?>

<caption class='titulo_tabela'><? fecho("pesquisa.de.pedido",$con,$cook_idioma); ?></caption>
	<form name='frm_pedido_consulta' action='<? echo $PHP_SELF; ?>' method='get'>
	<input type='hidden' name='btn_acao_pesquisa' value=''>
		<tr>
			<td width='150px'>&nbsp;</td>
			<td width='200px'>&nbsp;</td>
			<td width='200px'>&nbsp;</td>
			<td width='150px'>&nbsp;</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<? fecho("numero.do.pedido",$con,$cook_idioma); ?><br>
				<input type='text' name='pedido' value='<?php echo is_array($pedido) ? '' : $pedido;?>' tabindex='3' class='numericos'>
			</td>
			<td>
				<? echo ucwords(traduz("consulta.pelo.codigo.da.peca",$con,$cook_idioma)); ?><br>
				<input type='text' name='referencia' value='<?php echo $referencia;?>' tabindex='4' />
			</td>
			<td></td>
		</tr>
		<?php if(isFabrica(87)){ ?>
			<tr>
				<td></td>
				<td colspan='2'>
					<? fecho("ordem.de.compra",$con,$cook_idioma); ?><br>
					<INPUT maxlength="250" TYPE="text" NAME="pedido_cliente" id="pedido_cliente" value="<? if (strlen($pedido_cliente) > 0) echo $pedido_cliente; ?>" tabindex='5'>
				</td>
				<td></td>
			</tr>
		<?php }?>
		<tr>
			<td></td>
			<td>
				<? fecho("data.inicial",$con,$cook_idioma); ?><br>
				<INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial" id="data_inicial" value="<? if (strlen($data_inicial) > 0 AND strlen(@$_REQUEST['btn_acao_pesquisa']) == 0) echo $data_inicial; ?>" tabindex='5'>
			</td>
			<td>
				<? fecho("data.final",$con,$cook_idioma); ?><br>
				<INPUT size="12" maxlength="10" TYPE="text" NAME="data_final" id="data_final" value="<? if (strlen($data_final) > 0 AND strlen(@$_REQUEST['btn_acao_pesquisa']) == 0) echo $data_final; ?>" tabindex='6'>
			</td>
			<td></td>
		</tr>

		<?  if (isFabrica(45, 51)) { //HD 49364 ?>
		<tr>
			<td></td>
			<td colspan='3'>
				<? fecho("status.pedido",$con,$cook_idioma); ?><br>
				<?
					if(isFabrica(45)){
						$cond_status = " status_pedido IN(1, 2, 3, 4, 5, 8, 9, 14) ";
					}else if(isFabrica(51)){
						$cond_status = " status_pedido IN(1, 2, 4, 5, 7, 8, 11, 12, 13, 14) ";
					}else{
						$cond_status = " 1=1 ";
					}

					$sqlS = "SELECT status_pedido,
									descricao
							 FROM tbl_status_pedido
							 WHERE $cond_status;";
					#echo $sqlS;
					$resS = pg_exec($con, $sqlS);

					if(pg_numrows($resS)>0){
						echo "<select name='status_pedido' tabindex='5' class='frm'>";
							echo "<option value=''></option>";
						for($s=0; $s<pg_numrows($resS); $s++){
							$status_pedido    = pg_result($resS, $s, status_pedido);
							$status_descricao = pg_result($resS, $s, descricao);
							echo "<option value='$status_pedido'>$status_descricao</option>";
						}
						echo "</select>";
					}
				?>
			</td>
		</tr>
		<? } ?>
		<tr>
			<td></td>
			<?php
				if(isFabrica(87)){
			?>
					<td><?php
							$sqlS = "SELECT classe_pedido,
											classe
									 FROM tbl_classe_pedido";

							$resC = pg_exec($con, $sqlS);
							if(pg_num_rows($resC) > 0){
								$arrayClassePedido = pg_fetch_all($resC);
							}else{
								$arrayClassePedido = array();
							}



						?>
						<? fecho("Classe",$con,$cook_idioma); ?><br>
						<select name="classe_pedido" style="width:150px" class="frm">
							<option value=""></option>
							<?php
							for($i=0;$i<count($arrayClassePedido);$i++){
								echo "<option value='".$arrayClassePedido[$i]['classe_pedido']."'>".$arrayClassePedido[$i]['classe']."</option>";
							}
							?>
						</select>

					</td>
			<?php
				}
			?>
			<td></td>
			<td></td>
		</tr>
		<tr>
			<td colspan='4' style='text-align:center' valign='middle' nowrap>
				<br>
				<button alt="" style="cursor:pointer" tabindex='6' type="submit"
					onclick="if (document.frm_pedido_consulta.btn_acao_pesquisa.value == '' ) { document.frm_pedido_consulta.btn_acao_pesquisa.value='continuar'} else { alert (traducao.aguarde_submissao) }">
					<?=traduz('pesquisar', $con)?>
				</button>
				<br><br>

				<?php if (in_array($login_fabrica, [193]) && verifica_tipo_posto('posto_interno', 'true') == true) { ?>
					<a href="consulta_pedido_nao_faturado.php">
						<input class="btn btn-default" type="button" id="pedido_faturar" name="pedido_faturar" value="Pedidos a Faturar" style="margin-top: 5px; margin-bottom: 5px; cursor: pointer;">
					</a>
					<a href="upload_faturar_pedido.php">
						<input class="btn btn-primary" type="button" id="upload_faturamento" name="upload_faturamento" value="Realizar Faturamento" style="margin-top: 5px; margin-bottom: 5px; cursor: pointer;">
					</a>
				<?php } ?>
			</td>
		</tr>
	</form>
</table>


<? if (!isFabrica(87) and 1 == 2) {?>
<div id='slimlist_105129' style='width:640px;height:290px;margin:40px 40px 40px 40px'><span style='padding:.5em 1em;background:#fff;color:#bbb;font-size:12px;'>Loading playlist ...</span></div><script type='text/javascript' src='http://get-embed.wistia.com/javascripts/head.load.min.js'></script><script type='text/javascript'>var proto = document.location.protocol,playlistUrl = proto + '//' + 'static.wistia.com/playlists/playlist.js',playlistEmbedUrl = proto + '//' + 'get-embed.wistia.com/embed/playlists/3e92655eab.js?theme=trim&amp;playthrough=true&amp;autoplay=false';head.js(playlistUrl, playlistEmbedUrl);</script>

<? } ?>

<?
$btn_acao_pesquisa = $_REQUEST['btn_acao_pesquisa'];
if (empty($_REQUEST["btn_acao_pesquisa"])) {
	$btn_acao_pesquisa = $_REQUEST["btn_acao"];
}

$listar            = $_REQUEST['listar'];
$pedido            = $_REQUEST['pedido'];
$data_inicial      = $_REQUEST['data_inicial'];
$data_final        = $_REQUEST['data_final'];
$pedido_cliente    = $_REQUEST['pedido_cliente'];
$referencia        = $_REQUEST['referencia'];
$status_pedido     = $_REQUEST['status_pedido'];
$classe_pedido     = $_REQUEST['classe_pedido'];
$estado_pedido     = $_REQUEST['estado_pedido'];
$dash              = $_REQUEST['dash'];

/*HD 15618 - Alterar para data*/
	

$fnc  = @pg_exec($con,"SELECT TO_CHAR(CURRENT_DATE - INTERVAL '180 days','YYYY-MM-DD');");
$data_inicial_6_meses = @pg_result ($fnc,0,0);


if(isFabrica(42) ){
	if(strlen($data_inicial) > 0){
		list($di, $mi, $yi) = explode("/", $data_inicial);
		if (!checkdate($mi, $di, $yi)) {
			$erro = traduz('data.invalida');
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
		}
	}
	if( (strtotime($aux_data_inicial) < strtotime($data_inicial_6_meses) ) AND empty($pedido) ){
		$erro .= traduz("o.limite.para.a.pesquisa.e.de.6.meses").".";
	}
}


if(strlen($data_inicial)>0 and strlen($data_final)>0 and (strlen($pedido) == 0) AND strlen($erro) == 0) {

	list($di, $mi, $yi) = explode("/", $data_inicial);
	list($df, $mf, $yf) = explode("/", $data_final);

	if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
		$erro = traduz('data.invalida');
	} else {
		$aux_data_inicial = "{$yi}-{$mi}-{$di}";
		$aux_data_final   = "{$yf}-{$mf}-{$df}";
	}

	$fnc  = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
	$erro = pg_errormessage ($con) ;

	if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);

	$fnc  = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
	$erro = pg_errormessage ($con);


		

		if(strlen($pedido_cliente) == 0 AND strlen($erro) == 0){

			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
			$add_1 = " AND tbl_pedido.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";


			$sql = " SELECT '$aux_data_inicial'::date <= '$aux_data_final'::date";
			$res = @pg_query($con,$sql);
			$erro = pg_last_error($con);

			if(!empty($erro)) {
				$erro = traduz("data.invalida");
			}elseif(pg_num_rows($res) > 0){
				if(pg_fetch_result($res,0,0) == 'f'){
					$erro = traduz('a.data.inicial.nao.pode.ser.maior.que.a.data.final', $con);
				}
			}
		}else{
			$add_2 = "AND tbl_pedido.pedido_cliente = '$pedido_cliente'  ";
		}

	}else{
		if(isFabrica(42)){
			$add_1 = " AND tbl_pedido.data BETWEEN '$data_inicial_6_meses 00:00:00' AND '".date("Y-m-d")." 23:59:59' ";
		}
		if(strlen($add_2) == 0 AND strlen($pedido_cliente) > 0){
			$add_2 = "AND tbl_pedido.pedido_cliente = '$pedido_cliente'  ";
		}
	}
	if(!is_numeric($pedido_obs) and !empty($pedido_obs) && !isFabrica(30) && $login_fabrica != 1){

		$erro = traduz("o.campo.numero.do.pedido.deve.ser.apenas.numerico");
	}
	if (isFabrica(88)) {
		$add3 = " JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
		$add4 = " ,tbl_posto_fabrica.desconto ";
	}

	if($classe_pedido != ""){
		$add5 = " AND tbl_classe_pedido.classe_pedido = $classe_pedido ";
	}

	if(isFabrica(24)){
		$add_5 = " AND tbl_pedido.data > '2013-09-30 00:00:00' ";
	}

	if(isFabrica(87)){
        $matriz = false;
        $filial = false;

        $sql_matriz = "SELECT posto FROM tbl_posto_fabrica
                INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                        AND tbl_tipo_posto.codigo = 'REVENDA'
                        AND  tbl_tipo_posto.fabrica = $login_fabrica
                WHERE tbl_posto_fabrica.posto = $login_posto
                        AND tbl_posto_fabrica.fabrica = $login_fabrica";
        $res_matriz = pg_query($con,$sql_matriz);

        if(pg_num_rows($res_matriz) > 0){
                $matriz = true;
        } else {
            $sql_filial = "SELECT posto FROM tbl_posto_fabrica
                INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                AND tbl_tipo_posto.codigo = 'FILIAL'
                AND  tbl_tipo_posto.fabrica = $login_fabrica
                WHERE tbl_posto_fabrica.posto = $login_posto
                AND tbl_posto_fabrica.fabrica = $login_fabrica";
            $res_filial = pg_query($con, $sql_filial);

            if (pg_num_rows($res_filial)) {
                $filial = true;
            }
        }
    }

    if (( (strlen($pedido) > 0 OR count($pedido) > 0 OR strlen($classe_pedido) > 0 OR strlen($pedido_cliente) > 0 OR strlen($referencia) > 0 OR (strlen($data_inicial)>0 and strlen($data_final)>0)) AND $btn_acao_pesquisa == 'continuar') OR strlen($listar) > 0){

	if(strlen($pedido) > 0 and strlen($erro)==0){
		echo "<table width='700px' border='0' cellspacing='0' cellpadding='0' align='center' bgcolor='#f1f1f1'>";
		echo "<tr height='25'>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("voce.esta.pesquisando.o.pedido.%",$con,$cook_idioma,$pedido); echo "</b></font></td>";
		echo "</tr>";
	echo "</table>";
}

	if(empty($erro)) {
		if (isFabrica(146)) {
			$column_marca = ",tbl_marca.nome AS marca";
			$join_marca = "inner join tbl_marca on tbl_marca.marca = tbl_pedido.visita_obs::integer";
			$group_by_marca = ", tbl_marca.nome";
		}

		if(isFabrica(151)){
			$left_join_os = " LEFT JOIN tbl_os_item ON tbl_os_item.pedido = tbl_pedido_item.pedido  and tbl_pedido_item.peca = tbl_os_item.peca
			left join tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			left join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os_produto.os";
		}

		if (isFabrica(42)) {
			$join_posto_filial = "LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.filial_posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
			$column_posto_filial = ", tbl_posto_fabrica.nome_fantasia AS filial_nome_fantasia";
			$group_by_posto_filial = ", tbl_posto_fabrica.posto_fabrica";
		}

		$sql = "SELECT  tbl_pedido.pedido,
					case
						when tbl_pedido.pedido_blackedecker > 499999 then
							lpad ((tbl_pedido.pedido_blackedecker-500000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 399999 then
							lpad ((tbl_pedido.pedido_blackedecker-400000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 299999 then
							lpad ((tbl_pedido.pedido_blackedecker-300000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 199999 then
							lpad ((tbl_pedido.pedido_blackedecker-200000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 99999 then
							lpad ((tbl_pedido.pedido_blackedecker-100000)::text,5,'0')
					else
						lpad ((tbl_pedido.pedido_blackedecker)::text,5,'0')
					end                             AS pedido_blackedecker,
					tbl_status_pedido.status_pedido AS id_status,
					tbl_status_pedido.descricao     AS xstatus_pedido,
					tbl_pedido.data::date AS data,
					TO_CHAR(tbl_pedido.finalizado,       'DD/MM/YYYY') AS finalizado,
					TO_CHAR(tbl_pedido.aprovado_cliente, 'DD/MM/YYYY') AS aprovado_cliente,
					TO_CHAR(tbl_pedido.recebido_posto,   'DD/MM/YYYY') AS recebido_posto,
					tbl_tipo_pedido.descricao                          AS tipo_pedido_descricao,
					tbl_linha.nome                                     AS linha_descricao,
					tbl_pedido.exportado,
					tbl_pedido.distribuidor,
					tbl_pedido.total,
					tbl_pedido.pedido_sedex,
					tbl_pedido.pedido_loja_virtual,
					tbl_pedido.pedido_cliente,
					NULL  AS  pedido_status,
					tbl_pedido.obs,
					tbl_pedido.seu_pedido,
					tbl_pedido.tabela,
					tbl_classe_pedido.classe,
					tbl_pedido.permite_alteracao, ";

        if(isFabrica(87) && $matriz){
                $sql            .= " tbl_posto_fabrica.nome_fantasia, tbl_posto.nome, ";
                $join_marca     .= " INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto
                        AND tbl_posto_fabrica.fabrica = $login_fabrica
                        INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto ";
                $group_by_marca = ", tbl_posto_fabrica.nome_fantasia, tbl_posto.nome ";
        }

		if (!isFabrica(1, 24, 42, 88, 126,143))
			$sql .= "SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco * ((tbl_peca.ipi / 100)+1))::numeric AS preco_ipi {$column_marca} ";
		else if (isFabrica(126))
			$sql .= "to_char(SUM( (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) * tbl_pedido_item.preco)::numeric,'999999990.99' )::float AS preco_ipi $add4";
		else if(isFabrica(143))
			$sql .= "SUM(tbl_pedido_item.qtde * tbl_pedido_item.acrescimo_tabela_base)::numeric AS preco_ipi";
		else
			$sql .= "to_char(SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco)::numeric,'999999990.99' )::float  AS preco_ipi ";

		$sql .= $column_posto_filial;

		if(isFabrica(160) or $replica_einhell)
			$sql .= ", round(sum((tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) * tbl_pedido_item.preco)::numeric,2) AS ttl";

			$left_black = (isFabrica(1)) ? 'LEFT ' : '' ;
			$sql .= " FROM    tbl_pedido
					JOIN    tbl_tipo_pedido     USING (tipo_pedido)
					$left_black JOIN    tbl_pedido_item     USING (pedido)
					$left_black JOIN    tbl_peca            USING (peca)
					LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
					LEFT JOIN tbl_linha         ON tbl_linha.linha = tbl_pedido.linha
					LEFT JOIN tbl_classe_pedido ON tbl_classe_pedido.classe_pedido = tbl_pedido.classe_pedido
					$add3
					{$left_join_os}
					{$join_marca} 
					{$join_posto_filial} WHERE";

            if(!isFabrica(87)){
                    $sql .= "   tbl_pedido.posto   = $login_posto ";
            }else{

                    if($matriz){
                            $sql .= " (tbl_pedido.posto = $login_posto
                                    OR tbl_pedido.posto IN (SELECT filial_posto FROM tbl_posto_filial
                                            WHERE fabrica = $login_fabrica AND posto = $login_posto
                                    )
                            ) ";

                    }else{
                        if (true === $filial) {
                            $sql .= "   (tbl_pedido.posto = $login_posto OR tbl_pedido.filial_posto = $login_posto) ";
                        } else {
                            $sql .= "   tbl_pedido.posto   = $login_posto ";
                        }
                    }
            }

            $cond_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_pedido.fabrica IN(11,172) " : " tbl_pedido.fabrica = $login_fabrica ";

            $sql .= "AND tbl_pedido.finalizado is not null
					AND {$cond_fabrica}
					$add_1
					$add_2
					$add_5";
			if(isFabrica(151)){
				$sql .= " AND tbl_os_campo_extra.os_bloqueada is not true ";
			}

			if (isFabrica(1)) $sql .= " AND tbl_pedido.pedido_acessorio IS FALSE ";

			if (strlen($pedido) > 0 AND isFabrica(1)) {
				//$sql .= "AND tbl_pedido.pedido_blackedecker::text LIKE '%$pedido%' ";
				#HD 34403
				$sql .= "AND (substr(tbl_pedido.seu_pedido,4) like '%$pedido' OR tbl_pedido.seu_pedido = '$pedido' ) ";
			}

			if (strlen($pedido) > 0 AND !isFabrica(1)) {
				if(isFabrica( 88)){
					$sql .= "AND tbl_pedido.pedido = $pedido OR tbl_pedido.seu_pedido = '$pedido'";
				}else{
                    $pedidoAux = $pedido;
                    if(isFabrica(30)){
                        $pedidoAux = $pedido;
                        $pedido = str_replace(array("T","F"), "", $pedido);

                    }
					$sql .= "AND tbl_pedido.pedido = $pedido ";
                    $pedido = $pedidoAux;
				}
			}

			if (strlen($status_pedido) > 0 and isFabrica(45, 51, 158)) {
				if ($status_pedido == "pendente") {
					$sql .= " AND tbl_pedido.status_pedido NOT IN(4,5,14) ";
				} else {
					$sql .= "AND tbl_pedido.status_pedido = $status_pedido ";
				}
			}

            if(strlen($dash) > 0){
                if($estado_pedido == 1){
                    $sql .= "
                        AND tbl_pedido.status_pedido NOT IN (4,14)
                    ";
                }else{
                    $sql .= "
                        AND tbl_pedido.status_pedido IN (4,14)
                    ";
                }
            }
			
			if (isset($_REQUEST["pedido_status"]) && is_numeric($_REQUEST["pedido_status"])) {
				$status_pedido = $_REQUEST['pedido_status'];
				if ($status_pedido == 0) {
					$status_pedido = "1, 2";
				}
				
				$sql .= "
					AND tbl_pedido.status_pedido IN({$status_pedido})
				";
			}
			
            if(isFabrica(30) && is_array($pedido)){

                $sql .= "AND tbl_pedido.pedido IN (".implode(",",$pedido).") ";
            }

            if(isFabrica(1)){
            	$sql .= " AND (tbl_pedido.status_pedido not in(18) OR tbl_pedido.tipo_pedido = 94) ";
            }


			# Troquei ILIKE por LIKE - Fabio- HD 14504
			if (strlen($referencia) > 0) $sql .= "AND tbl_peca.referencia LIKE '%$referencia%' ";

			$sql .= "GROUP BY tbl_pedido.pedido,
							tbl_pedido.pedido_blackedecker,
							tbl_pedido.data,
							tbl_pedido.aprovado_cliente,
							tbl_pedido.finalizado,
							tbl_pedido.recebido_posto,
							tbl_pedido.total,
							tbl_tipo_pedido.descricao,
							tbl_status_pedido.status_pedido,
							tbl_status_pedido.descricao,
							tbl_pedido.exportado,
							tbl_pedido.distribuidor,
							tbl_pedido.pedido_sedex,
							tbl_linha.nome,
							tbl_pedido.valores_adicionais  ,
							tbl_pedido.pedido_loja_virtual,
							tbl_pedido.pedido_cliente,
							tbl_pedido.obs,
							tbl_pedido.seu_pedido,
							tbl_pedido.tabela,
							tbl_pedido.tabela,
							tbl_pedido.permite_alteracao,
							tbl_classe_pedido.classe
							{$group_by_marca}
							$add4
							{$group_by_posto_filial}
					ORDER BY tbl_pedido.data DESC";

				$res = pg_exec ($con,$sql);
			//echo pg_last_error();

			$sqlCount  = "SELECT count(*) FROM (";
			$sqlCount .= $sql;
			$sqlCount .= ") AS count";

			// ##### PAGINACAO ##### //
			require "_class_paginacao.php";

			// definicoes de variaveis
			$max_links = 11;				// máximo de links à serem exibidos
			$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
			$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
			$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

			$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

			// ##### PAGINACAO ##### //

			if (@pg_numrows($res) > 0) {
		echo "<form name='frm_pedido_lista' method='post' action='$PHP_SELF'>";
				echo "<table width='700px' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffffff'>";
				echo "<tr>";
				echo "<td><img height='1' width='20' src='imagens/spacer.gif'></td>";
				echo "<td valign='top' align='center'>";

				echo "<p>";

				if (strlen($referencia) > 0){
					echo "<table width='700px' border='0' cellspacing='0' cellpadding='0' align='center' bgcolor='#f1f1f1'>";
					echo "<tr height='25'>";
					echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("pedidos.com.a.peca",$con,$cook_idioma,$referencia); echo "</b></font></td>";
					echo "</tr>";
					echo "</table>";
				}
				echo "<p>";

				echo "<table width='700px' border='0' cellspacing='1' cellpadding='2' align='center' class='tabela '>";
                echo "<tr class='titulo_coluna'>";
                if (isFabrica(87)) {
                    echo "<td>"; fecho("ordem.de.compra",$con,$cook_idioma); echo "</td>";

                    if($matriz){
                        echo "<td>"; fecho("Revenda",$con,$cook_idioma); echo "</td>";
                    }
                }
                echo "<td>";fecho("pedido",$con,$cook_idioma); echo "</td>";
                
                echo "<td>"; fecho("data",$con,$cook_idioma); echo "</td>";

                if ($mostra_data_aprovacao) {
                    echo "<td>"; fecho("data.de.aprovacao",$con,$cook_idioma); echo "</td>";
                }
                echo "<td>"; fecho("finalizado",$con,$cook_idioma); echo "</td>";
                echo "<td>"; fecho("status",$con,$cook_idioma); echo"</td>";
                echo "<td>"; fecho("tipo.pedido",$con,$cook_idioma); echo"</td>";
                if (!isFabrica(1, 42, 168) && !isset($novaTelaOs)){
                    echo "<td>"; fecho("linha",$con,$cook_idioma); echo"</td>";
                } elseif (isFabrica(42)) {
                	echo "<td>"; fecho("Filial", $con, $cook_idioma); echo "</td>";
                }

                if (!isset($novaTelaOs) && !isFabrica(42)) {
                    echo "<td>"; fecho("Classe",$con,$cook_idioma); echo "</td>";
                } elseif (isFabrica(42)) {
                	echo "<td>"; fecho("Tipo.de.entrega", $con, $cook_idioma); echo "</td>";
                }

                if (isFabrica(146)) {
                    echo "<td>Marca</td>";
                }
                echo "<td>"; fecho("total",$con,$cook_idioma); echo"</td>";
                if((isFabrica(24)) OR (isFabrica(1) and $login_posto==5197) ){
                echo "<td>"; fecho("data.recebimento",$con,$cook_idioma); echo"</td>";
                }
                echo "<td>"; fecho("acao",$con,$cook_idioma); echo"</td>";
                //HD 14351
                if(isFabrica(1)){
                    echo "<td>"; fecho("obs",$con,$cook_idioma); echo "</td>";
                }

                if(in_array($login_fabrica, array(138))){
                	echo "<td>"; fecho("boleto.bancário",$con,$cook_idioma); echo "</td>";
                }

            echo "</tr>";

                $campo_total = (!isFabrica(35,87,120,138,143) && !isset($telaPedido0315)) ?
                    'preco_ipi' : 'total';

                 $campo_total = (isFabrica(160) or $replica_einhell) ? 'ttl' : $campo_total;  

            for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
                $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

                $pedido                    = trim(pg_fetch_result($res, $i, 'pedido'));
                $seu_pedido                = trim(pg_fetch_result($res, $i, 'seu_pedido'));
                $tabela                    = trim(pg_fetch_result($res, $i, 'tabela'));
                $pedido_blackedecker       = trim(pg_fetch_result($res, $i, 'pedido_blackedecker'));
                $data                      = trim(pg_fetch_result($res, $i, 'data'));
                $aprovado_cliente          = trim(pg_fetch_result($res, $i, 'aprovado_cliente'));
                $finalizado                = trim(pg_fetch_result($res, $i, 'finalizado'));
                $pedido_sedex              = trim(pg_fetch_result($res, $i, 'pedido_sedex'));
                $pedido_loja_virtual       = trim(pg_fetch_result($res, $i, 'pedido_loja_virtual'));
                $id_status                 = trim(pg_fetch_result($res, $i, 'id_status'));
                $pedido_status             = (isFabrica(2)) ? 'OK'
                                           : trim(pg_fetch_result($res, $i, 'pedido_status'));
                $status_pedido             = trim(pg_fetch_result($res, $i, 'xstatus_pedido'));
                $tipo_pedido_descricao     = trim(pg_fetch_result($res, $i, 'tipo_pedido_descricao'));
                $linha                     = trim(pg_fetch_result($res, $i, 'linha_descricao'));
                $exportado                 = trim(pg_fetch_result($res, $i, 'exportado'));
                $distribuidor              = trim(pg_fetch_result($res, $i, 'distribuidor'));
                $recebido_posto            = trim(pg_fetch_result($res, $i, 'recebido_posto'));
                $obs                       = trim(pg_fetch_result($res, $i, 'obs'));
                $classe                    = trim(pg_fetch_result($res, $i, 'classe'));
                $seu_pedido                = trim(pg_fetch_result($res, $i, 'seu_pedido'));
                $permite_alteracao         = trim(pg_fetch_result($res, $i, 'permite_alteracao'));
                $pedido_cliente_2          = trim(pg_fetch_result($res, $i, 'pedido_cliente'));
                $marca                     = trim(pg_fetch_result($res, $i, 'marca'));
                $desconto                  = trim(pg_fetch_result($res, $i, 'desconto'));
                $pedido_valores_adicionais = pg_fetch_result($res,$i,'valores_adicionais');
				$total                     = pg_fetch_result($res, $i, $campo_total);

				if (isFabrica(42)) {
					$filial = pg_fetch_result($res, $i, "filial_nome_fantasia");
					$obs = json_decode($obs, true);
				}

                if (strlen($seu_pedido)>0){
                    $pedido_blackedecker = fnc_so_numeros($seu_pedido);
                }

					echo "<tr bgcolor='$cor'>";

					if (isFabrica(87)) {
						echo "<td nowrap>".$pedido_cliente_2."</td>";

                        if($matriz){
                                $nome_fantasia_filial = pg_fetch_result($res, $i, "nome_fantasia");

                        if(empty($nome_fantasia_filial) || strlen($nome_fantasia_filial) == 0){
                            $nome_fantasia_filial = pg_fetch_result($res, $i, "nome");
                        }

                        echo "<td nowrap>".$nome_fantasia_filial."</td>";
                    }
                }

                $pedido_aux = (isFabrica(30,88) AND (!empty($seu_pedido))) ? $seu_pedido : $pedido;

                if (!isFabrica(1)) {
                    echo "<td align='center'><a href='pedido_finalizado.php?pedido=$pedido'>$pedido_aux</a></td>";
                }else{
                    echo "<td align='center'><a href='pedido_finalizado.php?pedido=$pedido'>$pedido_blackedecker</a></td>";
                }

				echo "<td align='center' >". mostra_data ($data) ."</td>";

                if ($mostra_data_aprovacao) {
                    $aprovado_cliente = $aprovado_cliente ? : '&nbsp;';
                    echo "<td align='center' >". $aprovado_cliente ."</td>";
                }
                echo "<td align='center'>". $finalizado ."</td>";

                if (isFabrica(14)) {

                    $sqldata = "SELECT CASE WHEN '$data' < '2009-08-27' THEN 'sim' ELSE 'nao' END";
                    $resdata = pg_exec($con,$sqldata);

                    $resposta = pg_result($resdata,0,0);

                    if ($resposta == 'sim') {
                        $status_pedido = '';
                    }

                }

                if (strlen($pedido_status) > 0) {
                    echo "<td >";fecho($status_pedido); echo "</td>";
                }else{
                        /*if ($login_fabrica==1 AND $pedido_sedex=='f' AND $tipo_pedido_descricao=="FATURADO" AND $pedido>457066){
                            echo "<td nowrap>-</td>";
                        }else{*/
                    echo "<td>"; fecho($status_pedido); echo "</td>";
                    //}
                }

                # Adicionado por Fábio - HD 4285
                if (isFabrica(3, 85) AND $pedido_loja_virtual=='t') {
                    $tipo_pedido_descricao = "Loja Virtual";
                }
                echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$tipo_pedido_descricao</td>";
                if (!isFabrica(1, 42, 43, 168) && !isset($novaTelaOs)){
                    if(strlen($linha)==0){
                        $sqll = "SELECT DISTINCT tbl_linha.nome AS nome_linha
                            FROM tbl_pedido_item
                            JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_pedido_item.peca
                            JOIN tbl_produto      ON tbl_produto.produto   = tbl_lista_basica.produto
                            JOIN tbl_linha        ON tbl_produto.linha     = tbl_linha.linha
                            WHERE pedido = $pedido LIMIT 1";
                        //if($ip=='200.228.76.7') echo nl2br($sqll);
                        $resl=pg_exec($con,$sqll);
                        if(strlen(@pg_result($resl,0,nome_linha))>0){
                            $linha = pg_result($resl,0,nome_linha);
                        }
                    }
                    echo "<td nowrap>$linha</td>";
                } elseif (isFabrica(42)) {
                	echo "<td>$filial</td>";
                }

                if (!isset($novaTelaOs) && !isFabrica(42)) {
                    echo "<td nowrap align='center'>$classe</td>";
                } else if (isFabrica(42)) {
                	$tipo_entrega = "";
                	$style_retira = "";

                	switch ($obs['transporte']) {
                		case 'SEDEX':
                			$tipo_entrega = "Sedex A Cobrar";
                			break;
                		case 'RETIRA':
                			$style_retira = 'style="border-bottom:0px;"';
                			$tipo_entrega = "Retirada";
                			break;
                		default:
                			$tipo_entrega = "Padrão";
                	}

					echo "<td " . $style_retira . ">$tipo_entrega</td>";
                }

                if (isFabrica(146)) {

						echo "<td>$marca</td>";
					}

					if(isFabrica(88)){
						$total = $total - ( ($total * $desconto) / 100 );
					}

					if(isFabrica(167)){
						$pedido_valores_adicionais = json_decode($pedido_valores_adicionais,true);

						$valor_desconto = $pedido_valores_adicionais['valor_desconto_fabricante'];
                        $valor_adicionais_pedido = $pedido_valores_adicionais['adicional_fabricante'];

                        $valor_desconto = str_replace(",", ".", $valor_desconto);
                        $valor_adicionais_pedido = str_replace(",", ".", $valor_adicionais_pedido);

                        $total_com_desconto = $total - ($total * $valor_desconto / 100);
						$total = $total_com_desconto + $valor_adicionais_pedido;

						echo "<td align='right'>". number_format($total,2,",",".") ."</td>";
					}else{
						if (!in_array($login_fabrica, array(143)) || in_array($login_fabrica, array(143)) && $id_status <> 19) {
							echo "<td class='tac'>". number_format($total,2,",",".") ."</td>";
						} else {
							echo "<td class='tac'>------</td>";
						}
					}

					if((isFabrica(24)) OR (isFabrica(1) and $login_posto==5197)){
						echo "<td nowrap align='center'>";
						if(strlen($recebido_posto)==0){
							echo "<input type='hidden' name='pedido_recebimento_$i' value='$pedido'>";
							echo "<input type='text' name='data_recebimento_$i' value='' size='10' maxlength='10'>";
						}else{
							echo "$recebido_posto";
						}
						echo "</td>";
					}

					if (isFabrica(143) && $id_status == 19) {
						echo "<td>&nbsp;</td>";
					} else {
						echo "<td align='left'>";
						if (strlen ($exportado) == 0 AND strlen ($distribuidor) == 0 AND $id_status <> 14) {
							if(isFabrica(7,127,143)){
								if (!in_array($tipo_pedido_descricao, array("Garantia", "Consignação" ,"Empréstimo" ,"GARANTIA"))) { /*NÃO É PARA EXCLUIR PEDIDOS DE OS*/
									if($id_status==19 and isFabrica(143)){
										echo createHTMLButton(
											traduz('aprovar', $con),
											"window.location=window.location.pathname+'?aprovar=$pedido'"
										);
									}
									echo createHTMLButton(
										traduz('excluir', $con),
										"window.location=window.location.pathname+'?excluir=$pedido'"
									);

								}
							}else{
								if(isFabrica(3,85)) { // HD56032
									echo createHTMLButton(
										traduz('excluir', $con),
										"excluirPedido('$pedido');"
									);
								}else{
									if(!isFabrica(15)) {#HD 236986	
										if(isFabrica(42)) {#HD-6968940
											$minutos = date(i);
											if($minutos < 25 OR $minutos > 35){
												echo createHTMLButton(
													traduz('excluir', $con),
													"window.location=window.location.pathname+'?excluir=$pedido'"
												);
											} 
										} else {
											echo createHTMLButton(
												traduz('excluir', $con),
												"window.location=window.location.pathname+'?excluir=$pedido'"
											);											
										}
									}
								}
							}
						}
						#HD 47695
						if (isFabrica(7) AND strlen ($exportado) == 0 AND $permite_alteracao == 't' AND $id_status <> 14) {
							echo createHTMLButton(
										traduz('alterar', $con),
										"window.location='pedido_cadastro.php?pedido=$pedido'"
									);
						}

						// MLG retirei a fábrica 5 porque não é mais cliente.
						if (isFabrica(94) and $data == date('Y-m-d') and strlen($exportado) == 0) {
							echo createHTMLButton(
                                traduz('alterar'),
                                "window.location='pedido_cadastro.php?pedido=$pedido'"
                            );
						}

                    if(isFabrica(141,144) && $tipo_pedido_descricao == "VENDA"){

                        $comprovante = $s3->getObjectList("thumb_comprovante_pedido_{$login_fabrica}_{$pedido}");
                        $link_img	 = $comprovante[0];

                        if(empty($link_img)){

                            echo "<button type='button' onclick='inserirComprovante(\"$pedido\")'>".traduz("inserir.comprovante")."</button>";

                        }else{

                            $link_img = str_replace("thumb_", "", $link_img);
                            $link_img = explode("/", $link_img);
                            $link_img = $link_img[count($link_img) -1];

                            $comprovante = basename($comprovante[0]);
                            $comprovante = $s3->getLink($comprovante);

                            echo "
                                        <p align='center'>
                                            <br />
                                            <a href='{$comprovante}' target='_blank'><img src='{$comprovante}' style='max-width: 100px; max-height: 100px;_height:100px;*height:100px;' /></a> <br />
                                            <strong>".traduz('comprovante.de.pagamento')."</strong>
                                        </p>";

                        }

                    }

                    echo "</td>";
                }

                // HD 14351
                if(isFabrica(1)){
                    echo "<td>";
                    if(strlen($obs) >0){
                        echo "<a href=\"pedido_relacao.php?obs=1&pedido=$pedido&keepThis=trueTB_iframe=true&height=200&width=300\" title=\"Observação\" class=\"thickbox\"\">".traduz("ver")."</a>";
                    }
                    echo "</td>";
                }

                if(in_array($login_fabrica, array(138))){

                	$idAnexo = $tDocs->getDocumentsByRef($pedido,'pedido')->attachListInfo;
                    $countDocs = 0;

                    $fabrica_qtde_anexos = 1;

                    for ($j = 0; $j < $fabrica_qtde_anexos; $j++) {
                        unset($anexo_link);

                        $anexo_item_imagem = "imagens/imagem_upload.png";
                        $anexo_s3          = false;
                        $anexo             = "";

                        if(strlen($pedido) > 0) {

                            if (count($idAnexo) > 0) {
                                foreach($idAnexo as $anexo) {
                                    if ($countDocs != $j) {
                                        continue;
                                    }

                                    $ext_item = pathinfo($anexo['filename'], PATHINFO_EXTENSION);

                                    if ($ext_item == "pdf") {
                                        $anexo_item_imagem = "imagens/pdf_icone.png";
                                    } else if (in_array($ext_item, array("doc", "docx"))) {
                                        $anexo_item_imagem = "imagens/docx_icone.png";
                                    } else {
                                        $anexo_item_imagem = $anexo['link'];
                                    }

                                    $anexo_item_link = $anexo['link'];
                                    $countDocs++;

                                }

                                $anexo        = basename($anexos[0]);
                                $anexo_s3     = true;
                            }
                        }
                        ?>

                        <td align="center">
                            <?php if ($anexo_s3 === true) { ?>
                                <button id="baixar_<?=$i?>" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_item_link?>')"> <?php echo traduz("visualizar.boleto");?> </button>
                            <?php } ?>
                        </td>
                    <?php
                    }

                }

                echo "</tr>";
                
                if (isFabrica(42) && $obs["transporte"] == "RETIRA") {
                	echo "<tr bgcolor='$cor'>";
                	echo "<td colspan='9' style='border-top:0px;padding:5px 10px'>";
                	echo "<span style='margin-right: 10px'><b>Responsável:</b> " . $obs["responsavel_retirada"]["nome"] . "</span>";
                	echo "<span style='margin-right: 10px'><b>RG do Responsável:</b> " . $obs["responsavel_retirada"]["rg"] . "</span>";
                	echo "<span><b>WhatsApp do Responsável:</b> " . $obs["responsavel_retirada"]["wapp"] . "</span>";
                	echo "</td>";
                	echo "</tr>";
                }
            }

		if((isFabrica(24)) OR (isFabrica(1) and $login_posto==5197)){
		echo "<input type='hidden' name='btn_gravar' value=''>";
		echo "<input type='hidden' name='total_pedido' value='$i'>";
		echo "<tr>";
		echo "<td colspan='9' align='center'>";
		echo createHTMLButton(
			traduz('gravar', $con),
			"if (document.frm_pedido_lista.btn_gravar.value == '') {document.frm_pedido_lista.btn_gravar.value='gravar'} else {alert ('" .
			traduz('aguarde.submissao', $con) .
			"'); }"
		);
		echo "</td>\n</tr>";

		}

				echo "</table>";
				echo "</form>";
				echo "</td>";
				echo "<td><img height='1' width='16' src='imagens/spacer.gif'></td>";

				echo "</tr>";
				//echo "<tr>";

				//echo "<td height='27' valign='middle' align='center' colspan='3' bgcolor='#FFFFFF'>";
				//echo "<a href='pedido_cadastro.php'><img src='imagens/btn_lancarnovopedido.gif'></a>";
				//echo "</td>";

				//echo "</tr>";
				echo "</table>";

				// ##### PAGINACAO ##### //
				// links da paginacao
				echo "<br>";

				echo "<div>";

				if($pagina < $max_links) {
					$paginacao = pagina + 1;
				}else{
					$paginacao = pagina;
				}

				// paginacao com restricao de links da paginacao

				// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
				$todos_links		= $mult_pag->Construir_Links("strings", "sim");

				// função que limita a quantidade de links no rodape
				$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

				for ($n = 0; $n < count($links_limitados); $n++) {
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
					fecho("resultados.de.%.a.%.do.total.de.%.registros",$con,$cook_idioma,array("<b>$resultado_inicial</b>","<b>$resultado_final</b>","<b>$registros</b>"));
					echo "<font color='#cccccc' size='1'>";
					fecho("pagina.%.de.%",$con,$cook_idioma,array("<b>$valor_pagina</b>","<b>$numero_paginas</b>"));
					echo "</font>";
					echo "</div>";
				}
				// ##### PAGINACAO ##### //
			}else{
				echo "<p>";

				echo "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>";
				echo "<tr>";

				echo "<td valign='top' align='center'>";
				//echo $sql ;
				echo "<h4>"; fecho("nenhum.pedido.encontrado",$con,$cook_idioma); echo "</h4>";
				echo "</td>";

				echo "</tr>";
				echo "</table>";
			}
	}else{
		if(strpos($erro,"valid input syntax for type timestamp")) {
			$erro = traduz("data.invalida.para.pesquisa");
		}

		echo "<table width='700' border='0' cellpadding='0' cellspacing='0' align='center'>";
		echo "<tr>";
		echo "<td valign='middle' align='center' class='error'>";

			if (strpos($erro,"ERROR: ") !== false) {
				$erro = substr($erro, 6);
			}

			if (strpos($erro,"CONTEXT:")) {
				$x = explode('CONTEXT:',$erro);
				$erro = $x[0];
			}

			echo $erro . $msg_erro;

		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}

}
?>

<p>

<? include "rodape.php"; ?>
