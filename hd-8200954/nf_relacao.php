<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';
$arr_ve_faturamento = array(); // fabricas que os postos NAO veem itens do faturamento, liberei para todas, no HD 415519
$title = traduz("relacao.de.pedido.de.pecas",$con,$cook_idioma);
$layout_menu = 'pedido';
include "cabecalho.php";
?>

<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->
<style>
	.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial" !important; 
    color:#FFFFFF;
    text-align:center;
	}

	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial" !important; 
		color:#FFFFFF;
		text-align:center;
		text-transform: capitalize;
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
	.mostra_itens { cursor:pointer; }

</style>

<p>


<form name='frm_nf_consulta' action='<? echo $PHP_SELF; ?>' method='get'>
<table width="700" border="0" cellpadding="2" cellspacing="0" align="center">
<tr class="titulo_tabela" >
	<td colspan="4"><?=traduz('parametros.de.pesquisa', $con)?></td>
</tr>
</table>


<table width="700" border="0" cellpadding="2" cellspacing="0" align="center" class="formulario">
<input type='hidden' name='btn_acao_pesquisa' value=''>
<tr height="22">
	<td width="200px">
	&nbsp;
	</td>
	<td width="450px" nowrap>
		<?=traduz('nota.fiscal', $con)?><br>
		<input type='text' name='nf' value='' size="28"><br>
	</td>
</tr>

<tr>
	<td>
			<!--<a style='font-size:11px;margin-right:4px' href='<? echo $PHP_SELF."?listar=todas"; ?>'><? fecho("listar.todos.as.notas.fiscais",$con,$cook_idioma); ?></a>-->
	</td>
</tr>
</table>

<table width="700" border="0" cellpadding="2" cellspacing="0" align="center" class="formulario">
	<tr height="22">
		<td width="150px">
		&nbsp;
		</td>
		<td width="10px" style="background:;" nowrap>
			<input type="submit" name="listar" id="listar" title='<? fecho("listar.todos.as.notas.fiscais",$con,$cook_idioma); ?>' value="<?=traduz('listar.todas', $con)?>" />
		</td>
		<td width="10px" style="background:;" width="5px" nowrap>
			<center><input type="submit" name="btn_acao_pesquisa" id="btn_acao_pesquisa" value="<?=traduz('pesquisar', $con)?>" /></center>
		</td>
		<td width="180px">
		&nbsp;
		</td>
	</tr>

	<tr>
		<td>
			<br>
		</td>
	</tr>
</table>
<?
$btn_acao_pesquisa = $_GET['btn_acao_pesquisa'];
if (strlen($_GET['btn_acao_pesquisa']) > 0) $btn_acao_pesquisa = $_GET['btn_acao_pesquisa'];

$listar = $_POST['listar'];
if (strlen($_GET['listar']) > 0) $listar = $_GET['listar'];

if(isFabrica(42) ){	
    $fnc  = @pg_exec($con,"SELECT TO_CHAR(CURRENT_DATE - INTERVAL '180 days','YYYY-MM-DD');");
    $data_inicial_6_meses = @pg_result ($fnc,0,0);
    $data_fim = date("Y-m-d");

	$where_data = " AND tbl_faturamento.emissao BETWEEN '$data_inicial_6_meses 00:00:00' AND '$data_fim 23:59:59' ";
}


$nf = $_POST['nf'];
if (strlen($_GET['nf']) > 0) $nf = $_GET['nf'];

if (strlen($nf) > 0) {
	$nf = trim($nf);
	$nf = str_replace (".","",$nf);
	$nf = str_replace ("-","",$nf);
	$nf = str_replace ("/","",$nf);
}
$login_fabrica_aux = $login_fabrica;

if($telecontrol_distrib){
	$login_fabrica_aux = "$login_fabrica,10";
}

if(in_array($login_fabrica, array(11,172))){
	$login_fabrica_aux= "11,172";
}

if ((strlen($nf) > 0 AND $btn_acao_pesquisa == traduz('pesquisar', $con)) OR strlen($listar) > 0){
	$sql = "SELECT  to_char(tbl_faturamento.emissao, 'DD/MM/YYYY')            AS emissao         ,
					to_char(tbl_faturamento.saida, 'DD/MM/YYYY')              AS saida           ,
					to_char(tbl_faturamento.previsao_chegada, 'DD/MM/YYYY')   AS previsao_chegada,
					trim(tbl_faturamento.pedido::text)                        AS pedido          ,
					trim(tbl_faturamento.nota_fiscal::text)                   AS nota_fiscal     ,
					trim(tbl_faturamento.total_nota::text)                    AS total_nota      ,
					tbl_faturamento.nf_os,
					TO_CHAR(tbl_faturamento.cancelada, 'DD/MM/YYYY') as cancelada,
					tbl_faturamento.faturamento,
					tbl_faturamento.transp
			FROM    tbl_faturamento
			WHERE   tbl_faturamento.posto   = $login_posto AND    tbl_faturamento.fabrica in ($login_fabrica_aux) 
			$where_data ";
	
	if (strlen($nf) > 0) $sql .= "AND tbl_faturamento.nota_fiscal ILIKE '%$nf' ";
	
	$sql .= "ORDER BY tbl_faturamento.emissao DESC, tbl_faturamento.total_nota DESC";

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
		?>
		<table width='700' border='0' cellpadding='2' cellspacing='1' align='center'>
			<tr>
				<td valign='top' align='center'>
					<p>
		<?php
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";


			$emissao          = trim(pg_result($res,$i,'emissao'));
			$saida            = trim(pg_result($res,$i,'saida'));
			$previsao_chegada = trim(pg_result($res,$i,'previsao_chegada'));
			$pedido           = trim(pg_result($res,$i,'pedido'));
			$nota_fiscal      = trim(pg_result($res,$i,'nota_fiscal'));
			$total_nota       = trim(pg_result($res,$i,'total_nota'));
			$faturamento      = trim(pg_result($res,$i,'faturamento'));
			$cancelada		  = pg_result($res,$i,'cancelada');
			$transp			  = pg_result($res,$i,'transp');
			if (isFabrica(43)){
				if (strlen($pedido) == 0) {
					$sql_pedido = "SELECT DISTINCT pedido FROM tbl_faturamento_item WHERE faturamento = $faturamento";
					$res_pedido = @pg_exec($con,$sql_pedido);
					if (@pg_numrows($res_pedido) > 0) {
						$pedido = trim(pg_result($res_pedido,0,'pedido'));
					}
				}
			}

			if (!empty($pedido))
			{
				$sql = "SELECT tbl_pedido.pedido
						FROM   tbl_pedido
						WHERE  tbl_pedido.pedido  = $pedido
						AND    tbl_pedido.posto   = $login_posto
						AND    tbl_pedido.fabrica = $login_fabrica;";
				$resx = @pg_exec ($con,$sql);
			}
			if (@pg_numrows($resx) > 0) {
				$xpedido = trim(pg_result($resx,0,pedido));
			}
			
			if ($i == 0) {
				?>
				<table  border='0' cellpadding='2' cellspacing='1' align='center' style="table-layout:fixed;">
				<tr height='20' class="titulo_coluna">
				<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><?php fecho("nota.fiscal",$con,$cook_idioma);?></b></font></td>
				<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b><?php fecho("emissao",$con,$cook_idioma);?></b></font></td>
				<?php
				if(isFabrica(87)) {
					?>
					<td align='center' style='font-weight:bold;font-size:13px; font-family: Geneva,Arial,san-serif;'>Status</td>
					<?php
				}
				?>
				<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b> <?php fecho("saida",$con,$cook_idioma);?></b></font></td>
				<?php if(isFabrica(88)) { ?>
							<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b> <?=traduz('transportadora', $con)?></b></font></td>
				<?php	
				} 

				if(!isFabrica(87)){
					if(isFabrica(152,180,181,182)){
					?>
						<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Previsão do Faturamento</b></font></td>
					<?php
					}else{
					?>
						<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b><?php fecho("previsao.chegada",$con,$cook_idioma);?></b></font></td>
					<?php
					}
				?>
					<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b> <?php fecho("pedido",$con,$cook_idioma);?></b></font></td>
				<?php 
				}
				?>				
				<td align='center' <?if(isFabrica(87) ) echo " colspan='2' ";?>><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b><?php fecho("total.nota",$con,$cook_idioma);?></b></font></td>
				</tr>
				<?php
			}
			?>
			<tr bgcolor='<?php echo $cor; if(!isFabrica($arr_ve_faturamento)) echo '\'id="'.$faturamento.'" class="mostra_itens"'; ?>'>
			
			<td align='center'>
			<?php
			if (strlen ($cancelada) == 0 AND strlen($xpedido) > 0) {
				?>
				<a href='pedido_finalizado.php?pedido=<?=$pedido;?>'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><?php echo $nota_fiscal;?></font></a>
				<?php
			}elseif (strlen ($cancelada) == 0 AND strlen($faturamento) > 0 AND (isFabrica(51) or isFabrica(81))) {
				?>
				<font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='nf_detalhe.php?faturamento=<?php echo $faturamento; ?>'><?php echo $nota_fiscal;?></a></font>
				<?php
			}else{
				if(isFabrica(11) or isFabrica(45)) { 
					?><a href='nf_detalhe.php?faturamento=<?php echo $faturamento; ?>'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><?php echo $nota_fiscal;?></font></a>
					<?php
				} else {
					if(!isFabrica($arr_ve_faturamento))
						echo '<img src="imagens/mais.bmp" title=""  />';
					?>
					<font size='2' face='Geneva, Arial, Helvetica, san-serif'><?php echo $nota_fiscal;?></font>
					<?php
				}
			}
			?>
			</td>
			<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><?php echo $emissao;?></font></td>
			<?php
			if(isFabrica(87)) {
				?>
                 <td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>
				 <?php
                     echo !empty($cancelada) ? traduz('cancelada', $con) . ' - ' . $cancelada : '&nbsp;';
				?>
                 </font></td>
				 <?php
            }
			?>
			<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><?php echo $saida;?></font></td>
			<?php if(isFabrica(88)) { ?>
						<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b> <?php echo $transp;?></b></font></td>
			<?php	} ?>
			<?php if(!isFabrica(87)): ?>
				<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><?php echo $previsao_chegada;?></font></td>
				<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><?php echo $pedido;?></font></td>
			<?php endif; ?>
			<td align='right' <?if(isFabrica(87) ) echo " colspan='2' ";?>><font size='2' face='Geneva, Arial, Helvetica, san-serif'><?php echo number_format($total_nota,2,",",".");?></font></td>
			</tr>
			<?php
		}
		?>
		</table>
		
		</td>
		<!--cho "<td><img height='1' width='16' src='imagens/spacer.gif'></td>";-->
		
		</tr>
		</table>
		<!--
		// ##### PAGINACAO ##### //
		// links da paginacao
		-->
		<br>
		
		<div>
		<?php
		
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
			?>
			<font color='#DDDDDD'><?php echo $links_limitados[$n];?></font>&nbsp;&nbsp;
			<?php
		}
		?>
		</div>
		<?php
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
		?>
		<p>
		
		<table width='700' border='0' cellpadding='2' cellspacing='2' align='center'>
			<tr>
				<td valign='top' align='center'>
					<?php 
					//echo("nao.foi.encontrado.notas.fiscais",$con,$cook_idioma);
					fecho('sem.resultados.para.esta.pesquisa', $con);
					?>
				</td>
		
			</tr>
		</table>
		<?php
	}
}
?>
</form>
<p>
<?php if (!isFabrica($arr_ve_faturamento)): ?>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript">
	$(".mostra_itens").click(function(e){
		id = $(this).attr("id");

		if( $("#"+id).find('td > img').attr('src') === 'imagens/mais.bmp' )
			$("#"+id).find('td > img').attr('src','imagens/menos.bmp');
			
		else if( $("#"+id).find('td > img').attr('src') === 'imagens/menos.bmp' ){
			$("."+id).each(function(){
				$(this).hide();
			});
			$("#"+id).find('td > img').attr('src','imagens/mais.bmp');
			e.preventDefault;
			return false;
		}
		else return true;
		
		url = "nf_relacao_ajax.php?faturamento="+id+"&mostra_itens=s";
		loading = '<tr id="loading_'+id+'"><td colspan="5" style="font-size:11px; text-align:center;"><?=traduz('carregando', $con)?></td></tr>';
		$("#"+id).after(loading);
		$.get(url,function(data) { 
			$("#loading_"+id).hide();
			$("#"+id).after(data);
		});

	});
</script>
<?php endif; ?>
<? include "rodape.php"; ?>
