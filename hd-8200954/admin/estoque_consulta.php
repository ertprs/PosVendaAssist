<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$admin_privilegios="gerencia";

$peca = trim ($_GET['peca']);
if(strlen($peca) > 0){
		$sql = "SELECT referencia,descricao
			FROM tbl_peca
			WHERE peca    = $peca
			AND   fabrica = $login_fabrica ";
		$res=pg_exec($con,$sql);
		$referencia = pg_result($res,0,referencia);
		$descricao = pg_result($res,0,descricao);
}

include "cabecalho.php";
?>

<html>
<head>
<title>Estoque de Peças</title>
<link type="text/css" rel="stylesheet" href="css/css.css">

<script language='javascript'>
	function fnc_pesquisa_peca (campo, campo2, tipo) {
		if (tipo == "referencia" ) {
			var xcampo = campo;
		}

		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {
			var url = "";
			url = "peca_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
			janela.retorno = "<? echo $PHP_SELF ?>";
			janela.referencia= campo;
			janela.descricao= campo2;
			janela.focus();
		}
	}
</script>
<style>

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
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

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
</style>
</head>

<body>


<p>

<center>
<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="450" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<caption>Consulta de estoque</caption>

<TBODY>
<tr>
<td>Referência da Peça<br><input type='text' size='10' name='referencia' id='referencia' class="frm" value=<?echo $referencia;?>> &nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_peca (document.frm_pesquisa.referencia,document.frm_pesquisa.descricao,'referencia')"> </td>
	<td>Descrição da Peça<br><input type='text' size='20' name='descricao'   id='descricao' class="frm" value='<?echo $descricao;?>'> &nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_peca (document.frm_pesquisa.referencia,document.frm_pesquisa.descricao,'descricao')"> </td>
</tr>
</tbody>
<TR>
	<TD colspan="2">
		<br>
		<input type='hidden' name='btn_acao' value=''>
		<IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
	</TD>
</TR>
</table>
</form>

</center>


<?

flush();

$referencia   = trim ($_POST['referencia']);
$descricao    = trim ($_POST['descricao']);

if(strlen($referencia)>2 and strlen($peca) == 0){
	$sql = "SELECT peca,fabrica 
			FROM tbl_peca
			WHERE upper(referencia) = upper('$referencia')
			AND   fabrica= $login_fabrica
			order by fabrica";

	$res = pg_exec ($con,$sql);

	if(pg_numrows($res)==0) {
		echo "Peça com a refência $referencia não encontrada";
	}else{
		$peca    = pg_result($res,$x,peca);
	}
}
	if(strlen($peca) > 0) {
			//hd 36986 - comentei condições de posto
			$sql = "
				SELECT peca, SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica 
				INTO TEMP tmp_ce1_4311_$x
				FROM tbl_pedido_item 
				JOIN tbl_pedido USING (pedido)
				WHERE (
				(tbl_pedido.posto        = 4311) 
				)
				AND     tbl_pedido.fabrica   = $login_fabrica
				AND     tbl_pedido_item.peca = $peca
				AND     (tbl_pedido.status_pedido NOT IN (3,4,6,13) OR tbl_pedido.status_pedido IS NULL)
				AND     tbl_pedido_item.qtde > (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)
				GROUP BY tbl_pedido_item.peca;

				CREATE INDEX tmp_ce1_peca_4311_$x ON tmp_ce1_4311_$x(peca);

				SELECT peca, SUM (qtde) AS qtde_transp
				INTO TEMP tmp_ce2_4311_$x
				FROM tbl_faturamento_item
				JOIN (
					SELECT faturamento
					FROM tbl_faturamento
					WHERE tbl_faturamento.posto   = 4311
					AND   tbl_faturamento.fabrica = $login_fabrica
					AND   tbl_faturamento.conferencia  IS NULL
					AND   tbl_faturamento.cancelada    IS NULL
					AND   tbl_faturamento.distribuidor IS NULL
				) fat ON tbl_faturamento_item.faturamento = fat.faturamento
				WHERE tbl_faturamento_item.peca = $peca
				GROUP BY tbl_faturamento_item.peca;

				CREATE INDEX tmp_ce2_peca_4311_$x ON tmp_ce2_4311_$x(peca);

				SELECT peca, SUM (qtde) AS qtde_embarcada
				INTO TEMP tmp_ce3_4311_$x
				FROM tbl_embarque_item
				JOIN tbl_embarque USING (embarque)
				WHERE tbl_embarque.faturar   IS NULL
				AND   tbl_embarque_item.peca = $peca
				GROUP BY tbl_embarque_item.peca;

				CREATE INDEX tmp_ce3_peca_4311_$x ON tmp_ce3_4311_$x(peca);

				SELECT	tbl_peca.referencia, 
					tbl_peca.descricao, 
					tbl_peca.ipi, 
					tbl_posto_estoque.qtde, 
					fabrica.qtde_fabrica, 
					transp.qtde_transp, 
					embarque.qtde_embarcada, 
					para.referencia AS para_referencia, 
					para.descricao AS para_descricao, 
					tbl_posto_estoque_localizacao.localizacao, 
					tbl_fabrica.nome,
					tbl_peca.peca,
					(SELECT tbl_tabela_item.preco FROM tbl_tabela_item WHERE tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela IN (SELECT tabela FROM tbl_posto_linha WHERE posto = 4311) ORDER BY preco DESC LIMIT 1) AS preco 
				FROM   tbl_peca 
				LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca        = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = 4311
				LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca        = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = 4311
				LEFT JOIN tbl_depara                    ON tbl_peca.peca        = tbl_depara.peca_de
				LEFT JOIN tbl_peca para                 ON tbl_depara.peca_para = para.peca
				LEFT JOIN tmp_ce1_4311_$x fabrica  ON tbl_peca.peca        = fabrica.peca
				LEFT JOIN tmp_ce2_4311_$x transp   ON tbl_peca.peca        = transp.peca
				LEFT JOIN tmp_ce3_4311_$x embarque ON tbl_peca.peca        = embarque.peca
				JOIN tbl_fabrica ON tbl_peca.fabrica = tbl_fabrica.fabrica
				WHERE (tbl_peca.peca = $peca OR para.peca = $peca)
				AND    tbl_peca.fabrica = $login_fabrica
				ORDER BY tbl_peca.descricao";
			//echo $sql; 
			$res = pg_exec ($con,$sql);
			flush();
			if(pg_numrows ($res)==0){
				echo "<center><b><span class='vermelho'>$referencia </span>- CÓDIGO DE PEÇA NÃO CADASTRADO</center></b><br>";
			}
		




		if (strlen($peca) > 0) {
			
			echo "<br><table width='800' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
			echo "<tr class='menu_top'>";
			echo "<td bgcolor='#485989'>Referência</td>";
			echo "<td bgcolor='#485989'>Descrição</td>";
			echo "<td bgcolor='#485989'>Estoque</td>";
			echo "<td bgcolor='#485989'>Qtde embarcada</td>";
			echo "</tr>";

			for ($xx = 0; $xx < pg_numrows($res); $xx++) {
			
				$cor = "#E8EBEA";
				
				echo "<tr bgcolor='$cor' class='Conteudo'>";

				echo "<td>";
				echo pg_result ($res,$i,referencia);
				if (strlen (trim (pg_result ($res,$xx,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_referencia);
				echo "</td>";

				echo "<td>";
				echo pg_result ($res,$i,descricao);
				if (strlen (trim (pg_result ($res,$xx,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_descricao);
				echo "</td>";

				echo "<td align='right'>&nbsp;";
				echo pg_result ($res,$xx,qtde);
				echo "</td>";

				echo "<td align='right'>&nbsp;";
				echo pg_result($res,$xx,qtde_embarcada);
				echo "</td>";

				echo "</tr>";
				$peca= pg_result($res,$xx,peca);
				
				echo "<tr>";
				$sqlx = "SELECT tbl_os.os,
								tbl_os.sua_os,
								count(tbl_embarque_item.qtde) as qtde_embarque
							FROM  tbl_os
							JOIN  tbl_os_produto USING (os)
							JOIN  tbl_os_item    USING (os_produto)
							JOIN  tbl_embarque_item USING (os_item)
							JOIN  tbl_embarque      USING (embarque)
							WHERE tbl_embarque.faturar   IS NULL
							AND   tbl_embarque_item.peca = $peca
							AND   tbl_os.fabrica = $login_fabrica
							GROUP BY tbl_os.os, tbl_os.sua_os";

				$resx=@pg_exec($con,$sqlx);
				if(@pg_numrows($resx) > 0){
					echo "<br><table align='center' border='0' cellspacing='1' cellpadding='5'>";
					echo "<tr class='menu_top'>";
					echo "<td align='center'>OS</td>";
					echo "<td align='center'>Qtde<br>embarcada</td>";
					echo "<td align='center'>Parcial</td>";
					echo "</tr>";
					for($y=0;$y<pg_numrows($resx);$y++) {
						$os            = pg_result($resx,$y,os);
						$sua_os        = pg_result($resx,$y,sua_os);
						$qtde_embarque = pg_result($resx,$y,qtde_embarque);
						

						$sql_parcial = "
							SELECT tbl_embarque.posto, tbl_embarque_item.embarque, osx.os_item, tbl_embarque_item.pedido_item, tbl_embarque_item.peca, tbl_embarque_item.qtde 
							FROM (
								SELECT DISTINCT oss.os_item
								FROM (
									SELECT tbl_os.os, tbl_os_item.os_item
									FROM tbl_os
									JOIN tbl_os_produto USING (os)
									JOIN tbl_os_item    USING (os_produto)
									JOIN tbl_embarque_item USING (os_item)
									JOIN tbl_embarque      USING (embarque)
									WHERE tbl_embarque.distribuidor  = 4311
									AND   tbl_os.os                  = $os
									AND   tbl_embarque.faturar       IS NULL 
									AND tbl_embarque_item.impresso   IS NULL
								) oss 
								JOIN tbl_os                 ON tbl_os.os                     = oss.os AND tbl_os.os = $os
								JOIN tbl_os_produto         ON oss.os                        = tbl_os_produto.os
								JOIN tbl_os_item            ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
								JOIN tbl_servico_realizado  ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
								LEFT JOIN tbl_embarque_item ON tbl_os_item.os_item           = tbl_embarque_item.os_item
								LEFT JOIN tbl_pedido_cancelado ON tbl_pedido_cancelado.os = tbl_os.os AND tbl_pedido_cancelado.pedido = tbl_os_item.pedido AND tbl_pedido_cancelado.peca = tbl_os_item.peca
								WHERE (tbl_servico_realizado.troca_de_peca OR tbl_servico_realizado.troca_produto OR tbl_servico_realizado.ressarcimento)
								AND tbl_embarque_item.os_item IS NULL
								AND tbl_pedido_cancelado.pedido IS NULL
							) osx
							JOIN tbl_os_item        ON osx.os_item           = tbl_os_item.os_item
							JOIN tbl_embarque_item  ON osx.os_item           = tbl_embarque_item.os_item
							JOIN tbl_embarque       ON tbl_embarque.embarque = tbl_embarque_item.embarque";
						$resParcial = pg_exec ($con,$sql_parcial);
						if(@pg_numrows($resParcial) > 0){
							$os_parcial = "OS Parcial";
						}else{
							$os_parcial = "";
						}
						$cor = ($y % 2 == 0) ? "#FEFEFE": '#E8EBEE';
				
						echo "<tr bgcolor='$cor' class='Conteudo'>";
						echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
						echo "<td>$qtde_embarque</td>";
						echo "<td>$os_parcial</td>";
						echo "</tr>";
					}
					echo "</table>";
				}
					

			}

			echo "</tr>";
			echo "</table>";


		}
	}


flush();
?>


<?


if (isset($_POST['btn_acao']) AND (strlen ($descricao) < 3 AND strlen ($referencia) < 3 AND strlen ($localizacao) < 3)) {
	echo "<br><br><center><b class='vermelho'>DIGITE NO MÍNIMO 3 CARACTERES PARA A BUSCA!</center></b>";
}
echo "<BR>";

?>


<? include "rodape.php"; ?>

</body>
</html>
