<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include "autentica_usuario.php";
	include "funcoes.php";

	$layout_menu = "os";
	$title = traduz("tela.consulta.os.fora.garantia", $con, $cook_idioma);

	include "cabecalho.php";

	$btn_acao = trim(strtolower($_REQUEST['btn_acao']));
	if(strlen($btn_acao) > 0){
		$data_inicial = $_POST['data_inicial'];
		$data_final = $_POST['data_final'];
		$os_orcamento = $_REQUEST['os_orcamento'];
		$consumidor_nome = $_POST['consumidor_nome'];
		$produto_referencia = $_POST['produto_referencia'];

		if(strlen($os_orcamento) == 0){
			if(strlen($data_inicial) > 0 && strlen($data_final) > 0){
				list($di, $mi, $yi) = explode("/", $data_inicial);
				if(!checkdate($mi,$di,$yi))
					$msg_erro = traduz("data.inicial.invalida", $con, $cook_idioma);

				list($df, $mf, $yf) = explode("/", $data_final);
				if(!checkdate($mf,$df,$yf))
					$msg_erro = traduz("data.final.invalida", $con, $cook_idioma);

				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final = "$yf-$mf-$df";

				if(strlen($msg_erro)==0){
					if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
						$msg_erro = traduz("data.invalida", $con, $cook_idioma);
					}
				}
			}else{
				$msg_erro = traduz("data.invalida", $con, $cook_idioma);
			}

			if(strlen($produto_referencia) > 0 && strlen($msg_erro) == 0){
				$sql = "	SELECT
							tbl_produto.produto
						FROM tbl_produto
							JOIN tbl_linha ON (tbl_linha.linha = tbl_produto.linha)
						WHERE
							tbl_produto.referencia = '$produto_referencia'
							AND tbl_linha.fabrica = $login_fabrica;";
				$res = pg_exec($con, $sql);
				if(pg_numrows($res) == 1){
					$produto = pg_fetch_result($res,0,produto);
				}else{
					$msg_erro = traduz("produto.invalido", $con, $cook_idioma);
				}
			}
		}

	$sql = "
			SELECT
				tbl_os_orcamento.os_orcamento		,
				tbl_os_orcamento.consumidor_nome	,
				tbl_os_orcamento.consumidor_fone		,
				tbl_os_orcamento.consumidor_email		,
				tbl_produto.referencia				,
				tbl_produto.descricao				,
				tbl_os_orcamento.abertura			,
				tbl_os_orcamento.orcamento_envio		,
				tbl_os_orcamento.orcamento_aprovacao	,
				tbl_os_orcamento.orcamento_aprovado	,
				tbl_os_orcamento.conserto			,
				tbl_os_orcamento.horas_aguardando_orcamento	,
				tbl_os_orcamento.horas_aguardando_aprovacao	,
				tbl_os_orcamento.horas_aguardando_conserto	,
				tbl_os_orcamento.horas_aguardando_retirada	,
				tbl_os_orcamento.fechamento	,
				tbl_posto.nome					,
				tbl_posto.fone					,
				tbl_posto.pais					,
				tbl_posto.email
			FROM
				tbl_os_orcamento
				JOIN tbl_produto ON (tbl_produto.produto = tbl_os_orcamento.produto)
				JOIN tbl_posto ON (tbl_os_orcamento.posto = tbl_posto.posto)
			WHERE
				tbl_os_orcamento.posto = $login_posto
				AND tbl_os_orcamento.fabrica = $login_fabrica ";

			if(strlen($os_orcamento) > 0){
				$sql .=" AND tbl_os_orcamento.os_orcamento = $os_orcamento ";
			}else{

				if(strlen($aux_data_inicial) > 0 && strlen($aux_data_final) > 0){
					$sql .=" AND tbl_os_orcamento.abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
				}

				if(strlen($consumidor_nome) > 0){
					$sql .=" AND tbl_os_orcamento.consumidor_nome = '$consumidor_nome'";
				}

				if(strlen($produto) > 0){
					$sql .=" AND tbl_os_orcamento.produto = '$produto'";
				}
			}

		$sql .="
			ORDER BY os_orcamento DESC;";
		$res = pg_exec($con, $sql);
	}
?>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
<style type="text/css">
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
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
		width: 700px;
		padding: 3px 0;
		margin: 0 auto;
	}


	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
		border: 1px solid #596d9b;
	}

	.subtitulo{

		background-color: #7092BE;
		font:bold 14px Arial;
		color: #FFFFFF;
		text-align:center;
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

	.informacao{
		font: 14px Arial; color:rgb(89, 109, 155);
		background-color: #C7FBB5;
		text-align: center;
		width:700px;
		margin: 0 auto;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	.espaco{
		padding-left:80px;
		width: 220px;
	}

	#gridRelatorio td{
		cursor: default;
	}
</style>

<script src="js/jquery-1.3.2.js" type="text/javascript"></script>
<? include "javascript_calendario.php"; ?>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script src="plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<script type="text/javascript">
	$(document).ready(function() {
		Shadowbox.init();

		$('.data').datePicker({startDate:'01/01/2000'});
		$(".data").maskedinput("99/99/9999");

		$('.number').numeric();
	});


	function pesquisaProduto(campo, tipo){
		var campo	= jQuery.trim(campo.value);

		if (campo.length > 2){
			Shadowbox.open({
				content:	"pesquisa_produto_nv.php?"+tipo+"="+campo,
				player:	"iframe",
				title:		"Pesquisa Produto",
				width:	800,
				height:	500
			});
		}else
			alert("<?php echo traduz('informar.toda.parte.informacao.para.realizar.pesquisa', $con, $cook_idioma);?>");
	}

	function retorna_produto(produto,referencia,descricao){
		gravaDados("produto_referencia",referencia);
		gravaDados("produto_descricao",descricao);
	}

	function gravaDados(name, valor){
		try {
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}
</script>
<br>
<?
if (strlen($msg_erro) > 0) {
	echo "<div class='msg_erro'>$msg_erro</div>";
} ?>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">
	<table align="center" class="formulario" width="700" border="0" cellspacing='1' cellpadding='3'>
		<tr>
			<td class="titulo_tabela" align="center" colspan='4'><?php echo traduz('parametros.de.pesquisa', $con, $cook_idioma);?></td>
		</tr>
		<tr>
			<td width='100'>&nbsp;</td>
			<td width='250'>&nbsp;</td>
			<td width='250'>&nbsp;</td>
			<td width='100'>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>
				<? fecho("numero.da.os",$con,$cook_idioma)?><br>
				<input type="text" name="os_orcamento" size="13" value="<?echo $os_orcamento?>" class="frm number">
			</td>
			<td>
				<? fecho ("nome.do.consumidor",$con,$cook_idioma);?>
				<input type="text" name="consumidor_nome" size="30" value="<? echo $consumidor_nome; ?>" class="frm">
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>
				<? fecho("data.inicial",$con,$cook_idioma); ?><br>
				<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? echo substr($data_inicial,0,10);?>" class="frm data">
			</td>
			<td>
				<? fecho("data.final",$con,$cook_idioma); ?><br>
				<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? echo substr($data_final,0,10);?>" class="frm data">
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>
				<? fecho ("ref.produto",$con,$cook_idioma); ?><br>
				<input class="frm" type="text" name="produto_referencia" size="13" maxlength="20" value="<? echo $produto_referencia ?>" id='produto_referencia'>
				<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" onclick="javascript: pesquisaProduto (document.frm_consulta.produto_referencia, 'referencia')">
			</td>
			<td>
				<? fecho ("descricao.produto",$con,$cook_idioma); ?><br>
				<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" id='produto_descricao'>
				<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" onclick="javascript: pesquisaProduto (document.frm_consulta.produto_descricao, 'descricao')">
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan='4' align='center'><br><input type="submit" name="btn_acao" value="<? fecho ("pesquisar",$con,$cook_idioma);?>"><br><br></td>
		</tr>
	</table>
</form>
<br />
<?php
	if(strlen($btn_acao) > 0 && pg_numrows($res) > 0){?>

		<div style='width: 700px; margin: 0 auto; text-align: left; margin-bottom: 20px;'>
			<fieldset style='width: 180px; text-align: left; font-size: 13px; border: 1px solid #CCC'>
				<legend>&nbsp;<? fecho ("status.os.fora.garantia",$con,$cook_idioma);?>&nbsp;</legend>
				<span style='width: 20px; height: 10px; display: inline-block; background: #CC0000; margin-left: 10px;'></span> <? fecho ("reprovado",$con,$cook_idioma);?><br/>
				<span style='width: 20px; height: 10px; display: inline-block; background: #009966; margin-left: 10px;'></span> <? fecho ("aprovado",$con,$cook_idioma);?>
			</fieldset>
		</div>

		<table border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio' style='width: 1024px'>
			<thead>
				<tr>
					<th colspan='2'><? fecho ("orcamento",$con,$cook_idioma);?></th>
					<th><? fecho ("nome.consumidor",$con,$cook_idioma);?></th>
					<th><? fecho ("produto",$con,$cook_idioma);?></th>
					<th><? fecho ("defeito",$con,$cook_idioma);?></th>
					<th><? fecho ("data.de.entrada",$con,$cook_idioma);?></th>
					<th><? fecho ("envio.do.orcamento",$con,$cook_idioma);?></th>
					<th><? fecho ("aprovacao.do.orcamento",$con,$cook_idioma);?></th>
					<th><? fecho ("termino.conserto",$con,$cook_idioma);?></th>
					<th colspan='3'><? fecho ("acoes",$con,$cook_idioma);?></th>
				</tr>
			</thead>
			<tbody><?
				function verificaValorCampo($campo){
					return strlen($campo) > 0 ? $campo : "&nbsp;";
				}

				function MostraDataHora($data) {

					if (strlen ($data) == 0)
						return null;

					$month   = substr($data,5,2);
					$date    = substr($data,8,2);
					$year    = substr($data,0,4);
					$hour    = substr($data,11,2);
					$minutes = substr($data,14,2);
					$seconds = substr($data,17,4);

					return $date."/".$month."/".$year." ".$hour.":".$minutes;
				}

				function verificaStatusForaGarantia($status){
					return ($status == 'f') ? " style='background: #CC0000;' " :  " style='background: #009966;' " ;
				}

				function m2h($mins) {
					// Se os minutos estiverem negativos
					// função abs retorna o valor absoluto do campo
					if ($mins < 0)
					$min = abs($mins);
					else
					$min = $mins;

					// Arredonda a hora - função floor
					$h = floor($min / 60);
					$m = ($min - ($h * 60)) / 100;
					$horas = $h + $m;

					if ($mins < 0)
					$horas *= -1;

					// Separa a hora dos minutos
					$sep = explode('.', $horas);
					$h = $sep[0];
					if (empty($sep[1]))
					$sep[1] = 00;
					$m = $sep[1];

					// Aqui coloca um zero no final
					if (strlen($m) < 2)
					$m = $m . 0;

					return sprintf('%02d:%02d', $h, $m);
				}

				//echo pg_num_rows($res);
				for ($i = 0 ; $i < pg_num_rows($res); $i++) {
					$os_orcamento		= pg_fetch_result($res,$i,os_orcamento);
					$consumidor_nome	= pg_fetch_result($res,$i,consumidor_nome);
					$consumidor_fone	 	= pg_fetch_result($res,$i,consumidor_fone);
					$produto			= pg_fetch_result($res,$i,referencia)." - ".pg_fetch_result($res,$i,descricao);
					$abertura			= MostraDataHora(pg_fetch_result($res,$i,abertura));
					$orcamento_aprovacao	= MostraDataHora(pg_fetch_result($res,$i,orcamento_aprovacao));
					$orcamento_aprovado	= pg_fetch_result($res,$i,orcamento_aprovado); // se true == Aprovado else Reprovado
					$conserto			= MostraDataHora(pg_fetch_result($res,$i,conserto));
					$orcamento_envio	= MostraDataHora(pg_fetch_result($res,$i,orcamento_envio));
					$nome			= pg_fetch_result($res,$i,nome);
					$fone				= pg_fetch_result($res,$i,fone);
					$pais				= pg_fetch_result($res,$i,pais);
					$email			= pg_fetch_result($res,$i,email);

					$cor = ($i % 2) ? "#F1F4FA" : "#F7F5F0";
					echo "<tr style='background: $cor'>";
						echo "<td ".verificaStatusForaGarantia($orcamento_aprovado)." width='5'></td>";
						echo "<td align='center'>".verificaValorCampo($os_orcamento)."</td>";
						echo "<td>".verificaValorCampo($consumidor_nome)."</td>";
						echo "<td>".verificaValorCampo($produto)."</td>";
						echo "<td align='center'>".verificaValorCampo($defeito_constatado)."</td>";
						echo "<td align='center'>".verificaValorCampo($abertura)."</td>";
						echo "<td align='center'>".verificaValorCampo($orcamento_envio)."</td>";
						echo "<td align='center'>".verificaValorCampo($orcamento_aprovacao)."</td>";
						echo "<td align='center'>".verificaValorCampo($conserto)."</td>";
						echo "<td align='center'><input type='button' value=' ".traduz ("consultar",$con,$cook_idioma)." ' onclick=\"javascript: window.open('os_orcamento_press.php?os_orcamento=$os_orcamento');\" /></td>";
						echo "<td align='center'><input type='button' value=' ".traduz ("alterar",$con,$cook_idioma)." ' onclick=\"javascript: window.location.href='cadastro_orcamento.php?os_orcamento=$os_orcamento';\" /></td>";
						echo "<td align='center'><input type='button' value=' ".traduz ("imprimir",$con,$cook_idioma)." ' onclick=\"javascript: window.open('os_orcamento_print.php?os_orcamento=$os_orcamento');\" /></td>";
					echo "</tr>";
				}
			echo "</tbody>";
		echo "</table>";

        $arquivo_nome	= "relatorio-orcamento-consulta-$login_posto.xls";
        $path			= "xls/";
        $arquivo_completo	= $path.$arquivo_nome;
        echo `rm -f $arquivo_completo `;

        echo  "<div style='margin: 10px auto;'><input type='button' onclick=\"window.location='$arquivo_completo'\" value='".traduz ("download.do.arquivo",$con,$cook_idioma)."'></div>";

        $style_header = " style='background-color:#596d9b;font: 14px \"Arial\";color:#FFFFFF;text-align:center; border: 1px solid #000' ";
        $header = "<table border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio' style='width: 1024px'>";
            $header .= "<thead>";
                $header .= "<tr>";
                    $header .= "<th $style_header colspan='2'>".traduz ("nome.do.posto",$con,$cook_idioma)."</th>";
                    $header .= "<th $style_header>Pais</th>";
                    $header .= "<th $style_header>".traduz ("nome.consumidor",$con,$cook_idioma)."</th>";
                    $header .= "<th $style_header>".traduz ("consumidor.fone",$con,$cook_idioma)."</th>";
                    $header .= "<th $style_header>".traduz ("consumidor.email",$con,$cook_idioma)."</th>";
                    $header .= "<th $style_header>".traduz ("produto",$con,$cook_idioma)."</th>";
                    $header .= "<th $style_header>".traduz ("data.de.entrada",$con,$cook_idioma)."</th>";
                    $header .= "<th $style_header>".traduz ("envio.do.orcamento",$con,$cook_idioma)."</th>";
                    $header .= "<th $style_header>".traduz ("aprovacao.do.orcamento",$con,$cook_idioma)."</th>";
                    $header .= "<th $style_header>".traduz ("termino.conserto",$con,$cook_idioma)."</th>";
                    $header .= "<th $style_header>".traduz ("fechamento",$con,$cook_idioma)."</th>";
                    $header .= "<th $style_header>".traduz ("horas.aguardando.orcamento",$con,$cook_idioma)."</th>";
                    $header .= "<th $style_header>".traduz ("horas.aguardando.aprovacao",$con,$cook_idioma)."</th>";
                    $header .= "<th $style_header>".traduz ("horas.aguardando.conserto",$con,$cook_idioma)."</th>";
                    $header .= "<th $style_header>".traduz ("horas.aguardando.retirada",$con,$cook_idioma)."</th>";
                    $header .= "<th $style_header>".traduz ("horas.orcamento.conserto",$con,$cook_idioma)."</th>";
                $header .= "</tr>";
            $header .= "</thead>";

            $header .= "<tbody>";
                for ($i = 0 ; $i < pg_num_rows($res); $i++) {
                    $os_orcamento		= pg_fetch_result($res,$i,os_orcamento);
                    $consumidor_nome	= pg_fetch_result($res,$i,consumidor_nome);
                    $consumidor_fone	 	= pg_fetch_result($res,$i,consumidor_fone);
                    $consumidor_email	= pg_fetch_result($res,$i,consumidor_email);
                    $produto			= pg_fetch_result($res,$i,referencia)." - ".pg_fetch_result($res,$i,descricao);
                    $abertura			= MostraDataHora(pg_fetch_result($res,$i,abertura));
                    $orcamento_aprovacao	= MostraDataHora(pg_fetch_result($res,$i,orcamento_aprovacao));
                    $orcamento_aprovado	= pg_fetch_result($res,$i,orcamento_aprovado); // se true == Aprovado else Reprovado
                    $conserto			= MostraDataHora(pg_fetch_result($res,$i,conserto));
                    $orcamento_envio	= MostraDataHora(pg_fetch_result($res,$i,orcamento_envio));
                    $nome			= pg_fetch_result($res,$i,nome);
                    $fone				= pg_fetch_result($res,$i,fone);
                    $pais				= pg_fetch_result($res,$i,pais);
                    $email			= pg_fetch_result($res,$i,email);

                    $horas_aguardando_orcamento = pg_result($res,$i,horas_aguardando_orcamento);
                    $horas_aguardando_aprovacao = pg_result($res,$i,horas_aguardando_aprovacao);
                    $horas_aguardando_conserto  = pg_result($res,$i,horas_aguardando_conserto);
                    $horas_aguardando_retirada  = pg_result($res,$i,horas_aguardando_retirada);
                    $fechamento  = pg_result($res,$i,fechamento);

                    $horas_orcamento_conserto   = $horas_aguardando_orcamento + $horas_aguardando_conserto;

                    $horas_aguardando_orcamento = m2h($horas_aguardando_orcamento);
                    $horas_aguardando_aprovacao = m2h($horas_aguardando_aprovacao);
                    $horas_aguardando_conserto  = m2h($horas_aguardando_conserto);
                    $horas_aguardando_retirada  = m2h($horas_aguardando_retirada);
                    $horas_orcamento_conserto   = m2h($horas_orcamento_conserto);


                    $cor = ($i % 2) ? "#F1F4FA" : "#CCC";
                    $header .= "<tr>";
                        $header .= "<td  style='background: $cor;' ".verificaStatusForaGarantia($orcamento_aprovado)." width='10'>&nbsp;</td>";
                        $header .= "<td  style='background: $cor;'>$nome</td>";
                        $header .= "<td  style='background: $cor;' >$pais</td>";
                        $header .= "<td  style='background: $cor;' >$consumidor_nome</td>";
                        $header .= "<td  style='background: $cor;'>$consumidor_fone</td>";
                        $header .= "<td  style='background: $cor;'>$consumidor_email</td>";
                        $header .= "<td  style='background: $cor;'>$produto</td>";
                        $header .= "<td  style='background: $cor;'>$abertura</td>";
                        $header .= "<td  style='background: $cor;'>$orcamento_envio</td>";
                        $header .= "<td  style='background: $cor;'>$orcamento_aprovacao</td>";
                        $header .= "<td  style='background: $cor;'>$conserto</td>";
                        $header .= "<td  style='background: $cor;'>$fechamento</td>";
                        $header .= "<td  style='background: $cor;'>$horas_aguardando_orcamento</td>";
                        $header .= "<td  style='background: $cor;'>$horas_aguardando_aprovacao</td>";
                        $header .= "<td  style='background: $cor;'>$horas_aguardando_conserto</td>";
                        $header .= "<td  style='background: $cor;'>$horas_aguardando_retirada</td>";
                        $header .= "<td  style='background: $cor;'>$horas_orcamento_conserto</td>";
                    $header .= "</tr>";
                }
            $header .= "</tbody>";
        $header .= "</table>";

        $fp = fopen ($arquivo_completo,"w");
            fputs ($fp,$header);
        fclose ($fp);

	}elseif(strlen($btn_acao) > 0){
		echo "<div style='margin: 10px; text-align: center; font-size: 14px'>".traduz ("nenhum.resultado.encontrado",$con,$cook_idioma)."!</div>";
	}
?>
<span class=""></span>
<?php include "rodape.php" ?>
