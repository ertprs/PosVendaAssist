<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

include "funcoes.php";
include "monitora.php";

$msg_erro = "";

if (strlen($_POST["acao"]) > 0 ) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0 )  $acao = strtoupper($_GET["acao"]);
$status=$_POST['status'];
if(strlen($status) == 0) $status=$_GET['status'];

if (strlen($acao) > 0 && $acao == "PESQUISAR") {

	if (strlen(trim($_POST["codigo_posto"])) > 0) $codi_posto = trim($_POST["codigo_posto"]);
	if (strlen(trim($_GET["codigo_posto"])) > 0)  $codi_posto = trim($_GET["codigo_posto"]);

	if (strlen($codi_posto)>0){
		$sql_adicional = " AND tbl_posto_fabrica.codigo_posto = '$codi_posto' ";
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto as cod,
						tbl_posto.nome as nome,
						tbl_posto.posto as posto
			FROM tbl_posto JOIN tbl_posto_fabrica USING(posto)
			WHERE tbl_posto_fabrica.fabrica=$login_fabrica
			$sql_adicional";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res)>0){
			$posto_codigo = pg_result ($res,0,cod);
			$posto_nome  = pg_result ($res,0,nome);
			$posto  = pg_result ($res,0,posto);
		}
	}else {
		$msg_erro="Selecione o posto.";
	}

}
$layout_menu = "auditoria";
$title = "Relatório de status das OSs abertas por posto";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>
<script>
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

</script>

<?
include "cabecalho.php";
?>

<br>

<? if (strlen($msg_erro) > 0) { ?>
<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" class="Error">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">
<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>

	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Relatório de status da OSs abertas</td>
	</tr>

	<tr>
		<td bgcolor='#DBE5F5'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>
				<tr width='100%' >
					<td align='right' height='20'>Código Posto:&nbsp;</td>
					<td align='left'>
						<input class="frm" type="text" name="codigo_posto" size="10" value="<? echo $codi_posto ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></A>
					</td>
				</tr>
				<tr>
					<td align='right'>Razão Social:&nbsp;</td>
					<td align='left'><input class="frm" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
				</tr>
				<tr>
					<td align='right'>Status da OS</td>
					<td >
					<select name="status" class="frm_relatorio">
					<option value=''>TODOS</option>
					<option value='analise' <? if($status=="analise") echo "selected"; ?>>Aguardando Análise</option>
					<option value='peca' <? if($status=="peca") echo "selected"; ?>>Aguardando Peça</option>
					<option value='conserto' <? if($status=="conserto") echo "selected"; ?>>Aguardando Conserto</option>
					<option value='consertada' <? if($status=="consertada") echo "selected"; ?>>Os Consertada</option>
					</td>
				</tr>
				<tr bgcolor="#D9E2EF">
					<td colspan="4" align="center" ><br><img border="0" src="imagens/btn_pesquisar_400.gif"
					onClick="if (document.frm_relatorio.acao.value=='PESQUISAR')
					alert('Aguarde submissão');
					else{
					document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();}" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
				</tr>

			</table>
		</td>
	</tr>
</table>

<br>

<?
if (strlen($acao) > 0  and strlen($msg_erro) == 0) {
	$sql_adicional=" and 1=1";
	$sql_join =" ";
	if($status=='analise'){

		$sql_adicional=" AND tbl_os.defeito_constatado is null
						 AND tbl_os.solucao_os is null ";
	}
	if($status=='peca'){
		$sql_status="SELECT distinct tbl_os.os
				INTO temp temp_status_os
				FROM tbl_os
				JOIN tbl_os_produto ON tbl_os_produto.os= tbl_os.os
				JOIN tbl_os_item using(os_produto)
				WHERE tbl_os_item.pedido is not null
				AND tbl_os_item.faturamento_item is null
				AND tbl_os.data_fechamento IS NULL
				AND (tbl_os.excluida       IS NULL OR tbl_os.excluida IS FALSE)
				AND tbl_os.posto= $posto
				AND tbl_os.fabrica=$login_fabrica	";
		$res_status=pg_exec($con,$sql_status);

		$sql_join = "JOIN temp_status_os on tbl_os.os = temp_status_os.os ";
	}
	if($status=='conserto'){
		$sql_status="SELECT distinct tbl_os.os
				INTO temp temp_status_os
				FROM tbl_os
				JOIN tbl_os_produto ON tbl_os_produto.os= tbl_os.os
				JOIN tbl_os_item using(os_produto)
				JOIN tbl_faturamento_item on tbl_os_item.faturamento_item = tbl_faturamento_item.faturamento_item
				JOIN tbl_faturamento using (faturamento)
				WHERE tbl_faturamento.nota_fiscal is not null
				AND tbl_os.data_fechamento IS NULL
				AND (tbl_os.excluida       IS NULL OR tbl_os.excluida IS FALSE)
				AND tbl_os.data_conserto IS NULL
				AND tbl_os.posto= $posto
				AND tbl_os.fabrica=$login_fabrica";
		$res_status=pg_exec($con,$sql_status);
		$sql_join = "JOIN temp_status_os on tbl_os.os = temp_status_os.os ";

	}
	if($status == 'consertada'){
			$sql_adicional=" AND data_conserto is not null";
	}

	$sql="SELECT	distinct tbl_os.os                                            ,
					tbl_os.sua_os                                                 ,
					tbl_os.serie                                                  ,
					tbl_os.data_conserto                                          ,
					tbl_produto.produto                                           ,
					tbl_produto.referencia                                        ,
					tbl_produto.descricao                                         ,
					tbl_produto.nome_comercial                                    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura ,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao,
					(SELECT to_char(digitacao_item,'DD/MM/YYYY') AS digitacao_item FROM tbl_os_produto JOIN tbl_os_item USING (os_produto) WHERE tbl_os.os = tbl_os_produto.os ORDER BY digitacao_item ASC LIMIT 1) AS digitacao_item
					FROM	tbl_os
					JOIN tbl_produto using(produto)
					$sql_join
					WHERE	tbl_os.data_fechamento IS NULL
					AND		(tbl_os.excluida       IS NULL OR tbl_os.excluida IS FALSE)
					AND		tbl_os.fabrica = $login_fabrica
					AND     tbl_os.posto=$posto
					$sql_adicional
					GROUP by tbl_os.os                     ,
							 tbl_os.sua_os                 ,
							 tbl_os.serie                  ,
							 tbl_produto.produto           ,
							 tbl_produto.referencia        ,
							 tbl_produto.descricao         ,
							 tbl_produto.nome_comercial    ,
							 data_abertura                 ,
							 data_digitacao                ,
							 data_conserto
							 ORDER BY tbl_os.sua_os DESC ;
					";

	$res = pg_exec ($con,$sql);

	if(pg_numrows($res) > 0){
	##### LEGENDAS - INÍCIO #####
		echo "<br>";
		echo "<div align='left' style='position: relative; left: 10'>";
		echo "<table border='0' align='center' cellspacing='0' cellpadding='0'>";
		echo "<tr height='18'>";
		echo "<td width='18' ><img src='imagens/status_vermelho' width='10' align='absmiddle'/></td>";
		echo "<td align='left'><font size='1'><b>&nbsp; OS Aguardando Análise</b></font></td><BR>";
		echo "</tr>";
		echo "<tr height='18'>";
		echo "<td width='18'><img src='imagens/status_amarelo' width='10' align='absmiddle'/></td>";
		echo "<td align='left'><font size='1'><b>&nbsp;  OS Aguardando Peça</b></font></td>";
		echo "</tr>";
		echo "<tr height='18'>";
		echo "<td width='18'><img src='imagens/status_rosa' width='10' align='absmiddle'/></td>";
		echo "<td align='left'><font size='1'><b>&nbsp;  OS Aguardando Conserto</b></font></td>";
		echo "</tr>";
		echo "<tr height='18'>";
		echo "<td width='18'><img src='imagens/status_azul' width='10' align='absmiddle'/></td>";
		echo "<td align='left'><font size='1'><b>&nbsp;  OS Consertada</b></font></td>";
		echo "</tr>";
		echo "</table>";
		echo "</div>";
		echo "<BR>";
	##### LEGENDAS - FIM  ######

		echo "<table width='600' border='0' cellspacing='1' cellpadding='4' align='center' style='font-family: verdana; font-size: 10px' class='tabela_resultado sample'>";
		echo "<thead class='titulo'>";
		echo "<th nowrap><b>OS Fabricante</b></th>";
		echo "<th nowrap><b>Data Abertura</b></th>";
		echo "<th nowrap><b>Data Pedido</b></th>";
		if( in_array($login_fabrica, array(11,172)) ){ //HD 81052
			echo "<th nowrap><b>Data NF</b></th>";
		}
		echo "<th nowrap><b>Produto</b></th>";
		echo "</thead>";
		echo "<tbody>";
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			echo "<tr bgcolor='$cor'>";
			$data_abertura    = trim(pg_result ($res,$i,data_abertura));
			$data_digitacao   = trim(pg_result ($res,$i,data_digitacao));
			$digitacao_item   = trim(pg_result ($res,$i,digitacao_item));
			$os               = trim(pg_result ($res,$i,os));
			$sua_os           = trim(pg_result ($res,$i,sua_os));
			$serie            = trim(pg_result ($res,$i,serie));
			$produto          = trim(pg_result ($res,$i,produto));
			$data_conserto    = trim(pg_result ($res,$i,data_conserto));
			$referencia       = trim(pg_result ($res,$i,referencia));

			if (strlen($sua_os) == 0) $sua_os = $os;
			$descricao = pg_result ($res,$i,nome_comercial) ;
			if (strlen ($descricao) == 0) $descricao = pg_result ($res,$i,descricao) ;

			$bolinha="";
			if($status=='analise') {
				$bolinha="vermelho";
			}
			if($status=='peca') {
				$bolinha="amarelo";
			}
			if($status=='conserto') {
				$bolinha="rosa";
			}
			if($status=='consertada') {
				$bolinha="azul";
			}

			$data_nf="";
			if(strlen($status) ==0){
				$sqlcor="SELECT *
							FROM tbl_os
							WHERE defeito_constatado is null
							AND	  solucao_os is null
							AND	  os=$os";
				$rescor=pg_exec($con,$sqlcor);
				if(pg_numrows($rescor) > 0) {
					$bolinha="vermelho";
				} else {
					$sqlcor2 = "SELECT	tbl_os_item.pedido   ,
										tbl_os_item.faturamento_item
								FROM    tbl_os_produto
								JOIN    tbl_os_item USING (os_produto)
								JOIN    tbl_produto USING (produto)
								JOIN    tbl_peca    USING (peca)
								LEFT JOIN tbl_defeito USING (defeito)
								LEFT JOIN tbl_servico_realizado USING (servico_realizado)
								LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
								LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
								LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
								LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
								WHERE   tbl_os_produto.os = $os";

					$rescor2 = pg_exec($con,$sqlcor2);

					if(pg_numrows($rescor2) > 0) {
						for ($j = 0 ; $j < pg_numrows ($rescor2) ; $j++) {
							$pedido               = trim(pg_result($rescor2,$j,pedido));
							$faturamento_item     = trim(pg_result($rescor2,$j,faturamento_item));
							$bolinha="";
							if (strlen($faturamento_item)>0){
								$sql  = "SELECT nota_fiscal, to_char(emissao, 'DD/MM/YYYY') AS emissao
											FROM    tbl_faturamento
											JOIN    tbl_faturamento_item USING (faturamento)
											WHERE   tbl_faturamento.fabrica=$login_fabrica
											AND     tbl_faturamento_item.faturamento_item = $faturamento_item";

								$resx = pg_exec ($con,$sql);

								if (pg_numrows ($resx) == 0) {
									$bolinha="amarelo";
								}else {
									$nota_fiscal= pg_result($resx,0,nota_fiscal);
									$data_nf    = pg_result($resx,0,emissao);
									if(strlen($nota_fiscal) > 0){
										$bolinha="rosa";
									}
								}
							}else{
								if (strlen($pedido) > 0) {
									$bolinha="amarelo";
								}else{
									//Paulo falo para colocar bolinha rosa qdo não tem pedido e tem peça
									$bolinha="rosa";
								}
							}
						}
					}
				}
				if(strlen($data_conserto) > 0) {
					$bolinha="azul";
				}
			}else if(strlen($status)>0 && in_array($login_fabrica, array(11,172)) ){
				//HD 68469
				$sqly = "SELECT tbl_os_item.pedido   ,
								tbl_os_item.faturamento_item
						FROM    tbl_os_produto
						JOIN    tbl_os_item USING (os_produto)
						JOIN    tbl_produto USING (produto)
						JOIN    tbl_peca    USING (peca)
						LEFT JOIN tbl_defeito USING (defeito)
						LEFT JOIN tbl_servico_realizado USING (servico_realizado)
						LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
						LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
						LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
						LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
						WHERE   tbl_os_produto.os = $os";
				$resy = pg_exec($con,$sqly);

				if(pg_numrows($resy) > 0) {
					for ($x=0; $x<pg_numrows($resy); $x++) {
						$faturamento_item = trim(pg_result($resy,$x,faturamento_item));

						if (strlen($faturamento_item)>0){
							$sql  = "SELECT nota_fiscal, to_char(emissao, 'DD/MM/YYYY') AS emissao
										FROM    tbl_faturamento
										JOIN    tbl_faturamento_item USING (faturamento)
										WHERE   tbl_faturamento.fabrica=$login_fabrica
										AND     tbl_faturamento_item.faturamento_item = $faturamento_item";

							$resx = pg_exec ($con,$sql);

							if (pg_numrows ($resx)>0){
								$data_nf    = pg_result($resx,0,emissao);
							}
						}
					}
				}
			}

			$bolinha = (strlen($bolinha) > 0) ? "<img src='imagens/status_$bolinha' width='10' align='absmiddle'>" : "";

			echo "<td align='center'>$bolinha <a href='os_press.php?os=$os' target='_blank'>".$login_codigo_posto.$sua_os."</a></td>";
			echo "<td align='center'>$data_abertura</td>";
			echo "<td align='center'>$digitacao_item</td>";
			if( in_array($login_fabrica, array(11,172)) ){
				echo "<td align='center'>$data_nf</td>";
			}
			echo "<td align='center'>$referencia</td>";
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";
	} else {
		echo "Não foi encontrado nenhum resultado.";
	}
}
include "rodape.php";
?>
