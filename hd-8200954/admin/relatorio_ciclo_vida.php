<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

include "funcoes.php";

if(strlen($_POST['acao'])>0) $acao = $_POST['acao'];
else                         $acao = $_GET['acao'];

$produto_referencia = $_POST['produto_referencia'];
$produto_descricao  = $_POST['produto_descricao'];
$familia            = $_POST['familia'];

if($acao=="PESQUISAR"){
	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0 or $_POST["data_inicial_01"]=='dd/mm/aaaa') {
			$msg_erro .= "Favor informar a data inicial para pesquisa<br>";
		}

		if (strlen($msg_erro) == 0) {
			$data_inicial = trim($_POST["data_inicial_01"]);
			$fnc      = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			$msg_erro = pg_errormessage ($con);
			
			if (strlen($msg_erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
		}
	}

	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["data_final_01"]) == 0 or $_POST["data_final_01"] == 'dd/mm/aaaa') {
			$msg_erro .= "Favor informar a data final para pesquisa<br>";
		}
		
		if (strlen($msg_erro) == 0) {
			$data_final = trim($_POST["data_final_01"]);
			$fnc      = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			$msg_erro = pg_errormessage ($con);
			
			if (strlen($msg_erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}

	if(strlen($produto_referencia)>0){
		$sqlP = "SELECT tbl_produto.produto
				 FROM tbl_produto
				 JOIN tbl_linha USING(linha)
				 WHERE tbl_produto.referencia = '$produto_referencia'
				 AND   tbl_linha.fabrica      = $login_fabrica";
		$resP = pg_exec($con,$sqlP);

		if(pg_numrows($resP)>0){
			$produto = pg_result($resP,0,produto);
			$cond_produto = " AND tbl_os.produto = $produto ";
		}
	}

	if(strlen($familia)>0){
		$cond_familia = " AND tbl_produto.familia = $familia ";
	}

	//VERIFICA SE AS DATAS SÃO MAIORES QUE 30 DIAS
	if(strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0){
		$sql = "select '$aux_data_final'::date - '$aux_data_inicial'::date ";
		$res = pg_query($con,$sql);
		if(pg_fetch_result($res,0,0)>30)$msg_erro .= "Período não pode ser maior que 30 dias";

	}
}

$layout_menu = "gerencia";
$title = "Gerência -  Relatório de Clico de Vida do Produto";

include 'cabecalho.php';

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	/*background-color: #485989;*/
}
.Conteudo {
	font-family: Arial;
	font-size: 11px;
	font-weight: normal;
}
.Erro {
	font-family: Arial;
	font-size: 13px;
	font-weight: normal;
	color: #FFFFFF;
	background-color: #FF0000;
}
</style>

<? include "javascript_calendario.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>

<? include "javascript_pesquisas.php" ?>

<?
if(strlen($msg_erro)>0){
	echo "<BR>";
	echo "<div class='Erro'>$msg_erro</div>";
}

?>

<BR>
<form name="frm_relatorio" method="POST" action="<?echo $PHP_SELF?>">
	<table width="500px" align="center" border="0" cellspacing="0" cellpadding="2">
		<tr class="Titulo">
			<td colspan="4" bgcolor='#485989'>Preencha os campos para realizar a pesquisa.</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td width='10%'></td>
			<td width='40%' align='left'>Data Inicial</td>
			<td width='40%' align='left'>Data Final</td>
			<td width='10%'></td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td></td>
			<TD ><INPUT size="12" maxlength="10" TYPE="text" class='frm' NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>"></TD>
			<TD><INPUT size="12" maxlength="10" TYPE="text" class='frm'  NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; ?>"></TD>
			<td></td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<TD style="width: 10px">&nbsp;</TD>
			<td align='left' nowrap >Ref. Produto</td>
			<td align='left' nowrap>Descrição</td>
			<TD style="width: 10px">&nbsp;</TD>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<TD style="width: 10px">&nbsp;</TD>
			<td align='left' nowrap>
			<input type="text" name="produto_referencia" size="12" class='frm' maxlength="20" value="<? echo $produto_referencia ?>" > 
			<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')">
			</td>
			<td  align='left' nowrap>
			<input type="text" name="produto_descricao" size="30" class='frm' value="<? echo $produto_descricao ?>" >
			<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')">
			</td>
			<TD style="width: 10px">&nbsp;</TD>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<TD style="width: 10px">&nbsp;</TD>
			<td align='left' nowrap >Família</td>
			<td align='left' nowrap>&nbsp;</td>
			<TD style="width: 10px">&nbsp;</TD>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<TD style="width: 10px">&nbsp;</TD>
			<td align='left' colspan="2" nowrap>
				<?
				##### INÍCIO FAMÍLIA #####
				$sql = "SELECT  *
						FROM    tbl_familia
						WHERE   tbl_familia.fabrica = $login_fabrica
						ORDER BY tbl_familia.descricao;";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res) > 0) {
					echo "<select class='frm' style='width: 150px;' name='familia'>\n";
					echo "<option value=''>ESCOLHA</option>\n";

					for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
						$aux_familia = trim(pg_fetch_result($res,$x,familia));
						$aux_descricao  = trim(pg_fetch_result($res,$x,descricao));

						echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
					}
					echo "</select>\n";
				}
				##### FIM FAMÍLIA #####
				?>
			</td>
			<TD style="width: 10px">&nbsp;</TD>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr bgcolor="#D9E2EF">
			<INPUT TYPE="hidden" NAME="acao">
			<td colspan="4" align="center"><img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
		</tr>
	</table>
</form>

<?
if($acao=="PESQUISAR" AND strlen($msg_erro)==0){
	$sql = "SELECT  tbl_produto.produto   ,
					tbl_produto.referencia,
					tbl_produto.descricao ,
					tbl_os.sua_os         ,
					tbl_os.os             ,
					tbl_os.data_abertura  ,
					tbl_os.data_nf
			INTO TEMP tmp_os_ciclo_vida_$login_admin
			FROM tbl_os
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.consumidor_revenda = 'C'
			$cond_produto
			$cond_familia
			AND   tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59';

			CREATE INDEX tmp_os_ciclo_vida_OS_$login_admin ON tmp_os_ciclo_vida_$login_admin(produto);

			SELECT produto,
			referencia    ,
			descricao     ,
			os            ,
			sua_os        ,
			data_abertura ,
			data_nf       ,
			(data_abertura::date - data_nf::date)/30::numeric(7,2) AS media
			INTO TEMP tmp_os_ciclo_vida_media_$login_admin
			FROM tmp_os_ciclo_vida_$login_admin;

			CREATE INDEX tmp_os_ciclo_vida_media_OS_$login_admin ON tmp_os_ciclo_vida_media_$login_admin(produto);

			SELECT produto, referencia,
			SUM(media) AS media_total
			INTO TEMP tmp_os_ciclo_vida_media_total_$login_admin
			FROM tmp_os_ciclo_vida_media_$login_admin
			GROUP BY produto, referencia;

			CREATE INDEX tmp_os_ciclo_vida_media_total_OS_$login_admin ON tmp_os_ciclo_vida_media_total_$login_admin(produto);

			SELECT produto, referencia,
			count(os) AS qtde
			INTO TEMP tmp_os_ciclo_vida_qtde_$login_admin
			FROM tmp_os_ciclo_vida_$login_admin
			GROUP BY produto, referencia;

			CREATE INDEX tmp_os_ciclo_vida_qtde_OS_$login_admin ON tmp_os_ciclo_vida_qtde_$login_admin(produto);

			SELECT DISTINCT tmp_os_ciclo_vida_media_$login_admin.produto ,
			tmp_os_ciclo_vida_media_$login_admin.referencia     ,
			tmp_os_ciclo_vida_media_$login_admin.descricao      ,
			tmp_os_ciclo_vida_media_$login_admin.os             ,
			tmp_os_ciclo_vida_media_$login_admin.sua_os         ,
			tmp_os_ciclo_vida_media_$login_admin.data_abertura  ,
			tmp_os_ciclo_vida_media_$login_admin.data_nf        ,
			tmp_os_ciclo_vida_media_$login_admin.media    ,
			tmp_os_ciclo_vida_media_total_$login_admin.media_total/tmp_os_ciclo_vida_qtde_$login_admin.qtde::numeric(7,2) AS media_total
			FROM tmp_os_ciclo_vida_media_$login_admin
			JOIN tmp_os_ciclo_vida_media_total_$login_admin USING(produto)
			JOIN tmp_os_ciclo_vida_qtde_$login_admin USING(produto)
			ORDER BY tmp_os_ciclo_vida_media_$login_admin.referencia, tmp_os_ciclo_vida_media_$login_admin.media
			";
	#echo nl2br($sql); exit;
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){

		for($x=0; $x<pg_numrows($res); $x++){
			$produto            = pg_result($res,$x,produto);
			$produto_referencia = pg_result($res,$x,referencia);
			$produto_descricao  = pg_result($res,$x,descricao);
			$media_total        = pg_result($res,$x,media_total);

			if($produto_anterior!=$produto){
				$grafico_periodo[] = $produto_referencia;
				$grafico_qtde[]    = $media_total;
			}
			$produto_anterior = $produto;
		}

		echo "<BR><BR>";
		include ("../jpgraph/jpgraph.php");
		include ("../jpgraph/jpgraph_pie.php");
		include ("../jpgraph/jpgraph_pie3d.php");
		$img = time();
		$image_graph = "png/2_call$img.png";
		
		// seleciona os dados das médias
		setlocale (LC_ALL, 'et_EE.ISO-8859-1');

		$graph = new PieGraph(600,500,"auto");
		$graph->SetShadow();

		$graph->title->Set("Relatório Ciclo de Vida $data_inicial - $data_final");
		$graph->title->SetFont(FF_FONT1,FS_BOLD);

		$p1 = new PiePlot3D($grafico_qtde);
		$p1->SetSize(0.3);

		$p1->SetCenter(0.35);
		//$p1->SetLegends($gDateLocale->GetShortMonth());
		$p1->SetLegends($grafico_periodo);

		$graph->Add($p1);
		$graph->Stroke($image_graph);
		echo "\n\n<img src='$image_graph'>\n\n";

		echo "<br><BR>";
		echo "<table width='550' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<TR  bgcolor='#485989' class='Titulo'>";
			echo "<TD>Referência Produto</TD>";
			echo "<TD colspan='2'>Descrição Produto</TD>";
			echo "<TD>Media Ciclo de Vida</TD>";
		echo "</TR>";
		echo "<TR  bgcolor='#485989' class='Titulo'>";
			echo "<TD>OS</TD>";
			echo "<TD>Data Abertura</TD>";
			echo "<TD>Data NF (compra)</TD>";
			echo "<TD>Ciclo de Vida</TD>";
		echo "</TR>";
		for($i=0; $i<pg_numrows($res); $i++){
			$produto            = pg_result($res,$i,produto);
			$produto_referencia = pg_result($res,$i,referencia);
			$produto_descricao  = pg_result($res,$i,descricao);
			$os                 = pg_result($res,$i,os);
			$sua_os             = pg_result($res,$i,sua_os);
			$data_abertura      = pg_result($res,$i,data_abertura);
			$data_nf            = pg_result($res,$i,data_nf);
			$media              = pg_result($res,$i,media);
			$media_total        = pg_result($res,$i,media_total);

			$media       = number_format($media,2,",",".");
			$media_total = number_format($media_total,2,",",".");

			if($produto_anterior!=$produto){
				echo "<tr bgcolor='#909090' class='Titulo'>";
					echo "<td>$produto_referencia</td>";
					echo "<td colspan='2'>$produto_descricao</td>";
					echo "<td>$media_total</td>";
				echo "</tr>";
			}
			$produto_anterior = $produto;

			if($os_anterior!=$os){
				echo "<tr bgcolor='#E8E8E8' class='Conteudo'>";
					echo "<td><A HREF='os_press.php?os=$os' target='_blank'>$sua_os</A></td>";
					echo "<td>";
					echo mostra_data($data_abertura);
					echo "</td>";
					echo "<td>";
					echo mostra_data($data_nf);
					echo "</td>";
					echo "<td>$media</td>";
				echo "</tr>";
			}
			$os_anterior = $os;
		}
		echo "</table>";
	}else{
		echo "<P>Nenhum resultado encontrado!</P>";
	}
}


?>

<? include "rodape.php";?>

