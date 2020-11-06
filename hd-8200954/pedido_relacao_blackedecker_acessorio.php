<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

//verifica se tem comunicado de acessorio, caso tenha não terá acesso a este programa.
$sql = "SELECT comunicado FROM tbl_comunicado WHERE fabrica = {$login_fabrica} AND tipo = 'Acessório' AND ativo ORDER BY comunicado DESC;";
$res = pg_query($con, $sql);
if(pg_num_rows($res)){
	header("Location: menu_pedido.php");
}

if (strlen($_GET["excluir"]) > 0) $excluir = $_GET["excluir"];

if (strlen($excluir) > 0) {
	$sql =	"SELECT pedido
			FROM tbl_pedido
			WHERE tbl_pedido.fabrica = $login_fabrica
			AND   tbl_pedido.posto   = $login_posto
			AND   tbl_pedido.pedido  = $excluir
			AND   tbl_pedido.exportado IS NULL;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$sql =	"DELETE FROM tbl_pedido
				WHERE tbl_pedido.pedido  = $excluir
				AND   tbl_pedido.posto   = $login_posto
				AND   tbl_pedido.fabrica = $login_fabrica
				AND   tbl_pedido.exportado IS NULL;";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
// 		echo "$sql";
		if (strlen($msg_erro) == 0) {
			header("Location: $PHP_SELF?listar=todas");
			exit;
		}
	}
}


$title = "RELAÇÃO DE PEDIDO DE PEÇAS";
$layout_menu = 'pedido';
include "cabecalho.php";
?>

<!-- Estilos -->
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
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: justify;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:0px solid #596d9b;
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
	padding: 10px 0 10px 0;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #ACACAC;
	empty-cells:show;

}
</style>


<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->


<? if ($login_fabrica == 1) { ?>

<br><br>
<table align="center" class="texto_avulso" width="700px">
<tr>
	<td>
		<b>PREZADO ASSISTENTE:</b> Quando existir um pedido feito pelo pessoal da Black & Decker, 
		irá aparecer na coluna Black o nome do usuário que o efetuou, 
		caso contrário foi um pedido feito pela própria Assistência.
	</td>
</tr>
<tr>
	<td style="text-align:left">
		---
	</td>
</tr>

<tr>
	<td>
	Pedidos não finalizados devem ser cancelados ou finalizados para que sejam faturados.
	Estes pedidos não devem ficar em aberto no sistema, para evitarmos transtornos futuros.
	Caso queria finalizar o pedido ou excluir o mesmo, clique no número do mesmo e delete ou finalize.
	</td>
</tr>
</table>
<br><br>

<? } ?>
<form name='frm_pedido_consulta' action='<? echo $PHP_SELF; ?>' method='get'>

<table width="700" class="formulario" border="0" cellpadding="2" cellspacing="0" align="center">
<input type='hidden' name='btn_acao_pesquisa' value=''>
	<tr class="titulo_tabela">
		<td colspan="2">
			<? fecho("Parâmetros de Pesquisa",$con,$cook_idioma); ?>
		</td>
	</tr>
	<tr height="50">
		<td nowrap align="right" width="55%">
			<font size="2" face="Geneva, Arial, Helvetica, san-serif">Nº do Pedido</font>
			<input type='text' name='pedido' value=''>
		</td>

		<td nowrap style='text-align:left' valign='middle' >

			<INPUT TYPE="button" VALUE="Pesquisar" ONCLICK="javascript: if (document.frm_pedido_consulta.btn_acao_pesquisa.value == '' ) { document.frm_pedido_consulta.btn_acao_pesquisa.value='continuar' ; document.frm_pedido_consulta.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar busca pelo Pedido" border='0' style='cursor: pointer'>
		
		</td>


	</tr>


	<tr>
		<td align="center" valign='top' colspan=2 nowrap>
			<INPUT TYPE="button" VALUE="Listar Todos os Pedidos" ONCLICK='window.location.href="<? echo $PHP_SELF."?listar=todas"; ?>"'>
		</td>
	</tr>
	<tr>
		<td>
			&nbsp;
		</td>
	</tr>

</table>
</form>
</script>


<?
$btn_acao_pesquisa = $_POST['btn_acao_pesquisa'];
if (strlen($_GET['btn_acao_pesquisa']) > 0) $btn_acao_pesquisa = $_GET['btn_acao_pesquisa'];

$listar = $_POST['listar'];
if (strlen($_GET['listar']) > 0) $listar = $_GET['listar'];

$pedido = $_POST['pedido'];
if (strlen($_GET['pedido']) > 0) $pedido = $_GET['pedido'];

if($login_fabrica == 1){

	$campos_group_by = " tbl_representante.codigo, tbl_representante.nome, ";
	$left_join = " LEFT JOIN    tbl_representante ON tbl_pedido.representante  = tbl_representante.representante 
											AND tbl_pedido.fabrica = $login_fabrica ";
	$campos_sql = " tbl_representante.codigo  AS representante_codigo, tbl_representante.nome  AS representante_nome,  ";
}

if ((strlen($pedido) > 0 AND $btn_acao_pesquisa == 'continuar') OR strlen($listar) > 0){
	$sql = "SELECT  tbl_pedido.pedido                                              ,
					lpad(substr(tbl_pedido.pedido_blackedecker::text,2,7),5,'0') AS pedido_blackedecker,
					tbl_pedido.seu_pedido                                          ,
					tbl_pedido.data                                                ,
					TO_CHAR(tbl_pedido.finalizado,'DD/MM/YYYY') AS finalizado      ,
					tbl_pedido.total                                               ,
					tbl_tipo_pedido.descricao AS tipo_pedido_descricao             ,
					tbl_linha.nome            AS linha_descricao                   ,
					tbl_pedido.exportado          ,
					tbl_pedido.distribuidor       ,
					$campos_sql 
					(
						SELECT tbl_status.descricao AS status
						FROM   tbl_pedido_status
						JOIN   tbl_status USING (status)
						WHERE  tbl_pedido_status.pedido = tbl_pedido.pedido
						ORDER BY tbl_pedido_status.data DESC
						LIMIT 1
					) AS pedido_status                                            ,
					tbl_status_pedido.descricao AS xstatus_pedido                 , ";
if ($login_fabrica <> 1) $sql .= "to_char(SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco * ((tbl_peca.ipi / 100)+1)),'999999990.99' )::float AS preco_ipi ";
else					 $sql .= "to_char(SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco),'999999990.99' )::float  AS preco_ipi ";
// HD 11606 Paulo
	$sql .= "FROM    tbl_pedido
			JOIN    tbl_tipo_pedido     USING (tipo_pedido)
			LEFT JOIN tbl_status_pedido USING (status_pedido)
			LEFT JOIN    tbl_pedido_item     USING (pedido)
			LEFT JOIN    tbl_peca            USING (peca)
			LEFT JOIN    tbl_linha           USING (linha)
			$left_join
			WHERE   tbl_pedido.posto   = $login_posto
			AND     tbl_pedido.fabrica = $login_fabrica ";

	if ($login_fabrica == 1) $sql .= " AND tbl_pedido.pedido_acessorio IS TRUE ";

	if (strlen($pedido) > 0) {
		#$sql .= "AND tbl_pedido.pedido_blackedecker::text ilike '%$pedido%' ";
		#HD 34403
		$sql .= " AND (substr(tbl_pedido.seu_pedido,4) like '%$pedido' OR tbl_pedido.seu_pedido = '$pedido' ) ";
	}
	
	$sql .= "GROUP BY tbl_pedido.pedido           ,
					tbl_pedido.pedido_blackedecker,
					tbl_pedido.seu_pedido         ,
					tbl_pedido.data               ,
					tbl_pedido.finalizado         ,
					tbl_pedido.total              ,
					tbl_tipo_pedido.descricao     ,
					tbl_status_pedido.descricao   ,
					tbl_pedido.exportado          ,
					tbl_pedido.distribuidor       ,
					$campos_group_by
					tbl_linha.nome 				  
						
			ORDER BY tbl_pedido.data DESC";
	//$res = pg_exec ($con,$sql);

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
		echo "<br>";
		echo "<table width='700' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffffff'>";
		echo "<tr>";
		echo "<td valign='top' align='center'>";
		
		echo "<p>";
		
		echo "<table width='700' border='0' cellspacing='1' cellpadding='0' align='center' class='tabela' >";
		echo "<tr height='20' class='titulo_coluna'>";
		echo "<td align='center'><b>Pedido</b></td>";
		echo "<td align='center'><b>Data</b></td>";
		echo "<td align='center'><b>Finalizado</b></td>";
		echo "<td align='center'><b>Status</b></td>";
		echo "<td align='center'><b>Tipo Pedido</b></td>";
		if($login_fabrica == 1){
			echo "<td align='center'><b>Representante</b></td>";
		}
		if ($login_fabrica <> 1){
			echo "<td align='center'><b>Linha</b></td>";
		}
		echo "<td align='center'><b>Valor Total</b></td>";
		echo "<td align='center'><b>Ação</b></td>";
		echo "</tr>";
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		
			 $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			
			$total                 = pg_result($res,$i,preco_ipi);
			$pedido                = trim(pg_result($res,$i,pedido));
			$pedido_blackedecker   = trim(pg_result($res,$i,pedido_blackedecker));
			$seu_pedido            = trim(pg_result($res,$i,seu_pedido));
			$data                  = trim(pg_result($res,$i,data));
			$finalizado            = trim(pg_result($res,$i,finalizado));
			if ($login_fabrica == 2)
				$pedido_status         = "OK";
			else
				$pedido_status         = trim(pg_result($res,$i,pedido_status));
			$status_pedido         = trim(pg_result($res,$i,xstatus_pedido));
			$tipo_pedido_descricao = trim(pg_result($res,$i,tipo_pedido_descricao));
			$linha                 = trim(pg_result($res,$i,linha_descricao));
			$exportado             = trim(pg_result($res,$i,exportado));
			$distribuidor          = trim(pg_result($res,$i,distribuidor));

			if($login_fabrica == 1){	
				$representante_codigo          	= trim(pg_result($res,$i,representante_codigo));
				$representante_nome		        = trim(pg_result($res,$i,representante_nome));				
			}

			#HD 34403
			if (strlen($seu_pedido)>0){
				$pedido_blackedecker = fnc_so_numeros($seu_pedido);
			}

			echo "<tr bgcolor='$cor' >";
			if ($login_fabrica <> 1) {
				echo "<td align='center'><a href='pedido_finalizado.php?pedido=$pedido'>$pedido</a></td>";
			}else{
				echo "<td align='center'><a href='pedido_finalizado.php?pedido=$pedido'>";
				//$pedido_blackedecker = intval($pedido_blackedecker+1000);
				echo "$pedido_blackedecker</a></td>";
			}
			echo "<td align='center'>". mostra_data ($data) ."</td>";
			echo "<td align='center'>". $finalizado ."</td>";
			
			if (strlen($pedido_status) > 0) {
				echo "<td nowrap align='center'>$pedido_status</td>";
			}else{
				echo "<td nowrap align='center'>$status_pedido</td>";
			}
			
			echo "<td align='center'>$tipo_pedido_descricao</font></td>";

			if($login_fabrica == 1){
				echo "<td align='center'>$representante_codigo - $representante_nome</font></td>";
			}

			if ($login_fabrica <> 1){
				echo "<td>$linha</td>";
			}
			echo "<td align='center'><b>". number_format($total,2,",",".") ."</b></td>";
			echo "<td align='center'>";

			if (strlen ($exportado) == 0 AND strlen ($distribuidor) == 0) {
				/* imagem de excluir funcionando
				echo "<a href='$PHP_SELF?excluir=$pedido'><img border='0' src='imagens/btn_excluir.gif'></a>";
				*/
				//botão de excluir
				echo "<input type='button' value='Excluir' style='cursor:pointer;font:11px Arial' ONCLICK=\"window.location='$PHP_SELF?excluir=$pedido'\" >";
			}
		
			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
		echo "</td>";
		
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
			echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
			echo "<font color='#cccccc' size='1'>";
			echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>";
			echo "</div>";
		}
		// ##### PAGINACAO ##### //
	}else{
		echo "<p>";
		
		echo "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr>";
		
		echo "<td valign='top' align='center'>";
		echo "<h4>Não foi encontrado Pedidos.</h4>";
		echo "</td>";
		
		echo "</tr>";
		echo "</table>";
	}
}
?>

<p>

<? include "rodape.php"; ?>