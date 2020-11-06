<?
// Tela para a INTELBRAS onde o acesso a essa tela(tabela de preço) é restrita

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';
include "autentica_usuario_tabela_preco.php";

$liberar_preco = true ;

$title = traduz("tabela.de.precos",$con,$cook_idioma);

$layout_menu = 'preco';

include "cabecalho.php";

if($_POST['tabela'])             $tabela             = $_POST['tabela']; 
if($_POST['referencia_produto']) $referencia_produto = $_POST['referencia_produto']; 
if($_POST['descricao_produto'])  $descricao_produto  = $_POST['descricao_produto']; 

if($_GET['tabela'])             $tabela             = $_GET['tabela']; 
if($_GET['referencia_produto']) $referencia_produto = $_GET['referencia_produto']; 
if($_GET['descricao_produto'])  $descricao_produto  = $_GET['descricao_produto']; 

if($_POST['referencia_peca']) $referencia_peca = $_POST['referencia_peca']; 
if($_POST['descricao_peca'])  $descricao_peca  = $_POST['descricao_peca']; 

if($_GET['referencia_peca']) $referencia_peca = $_GET['referencia_peca']; 
if($_GET['descricao_peca'])  $descricao_peca  = $_GET['descricao_peca']; 

?>

<? include "javascript_pesquisas.php" ?>

<script language="JavaScript">

//FAZ A BUSCA DA PEÇA
function fnc_pesquisa_peca2 (campo, campo2, tipo) {
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
.Menu {
	font-size: 10px;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-weight: bold;
	text-align: center;
	color: #FFFFFF;
	background-color: #596D9B
}
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Tabela img{
	padding:5px;
	padding-left:15px;
	}
.Mensagem{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#7192C4;
	font-weight: bold;
}

.Conteudo {
	font-size: 10px;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: normal;
	color: #000000;
}
</style>
<?
if(strlen($msg)>0){
	echo "<br><table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60' align='top'><img src='imagens/cadeado1.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF' align='left'>$msg<br>
	
	Para restringir o acesso novamente, sair do sistema. Caso contrario a TABELA DE PREÇO ficará com o acesso liberado!<br><center><a href='tabela_precos_senha.php?acao=alterar'>Alterar Senha</a> &nbsp; - &nbsp; <a href='tabela_precos_senha.php?acao=libera'>Liberar tela</a></center>";
	echo "</td>";
	echo "</tr>";
	echo "</table><br>";
	echo "";
}else{
	echo "<br><table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/cadeado2.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF' align='left'><a href='tabela_precos_senha.php?acao=inserir' >
	".traduz("esta.area.nao.esta.protegida.por.senha",$con,$cook_idioma)."
	 <br>
	".traduz("para.inserir.senha.para.restricao.da.tabela.de.precos,.clique.aqui.e.saiba.mais",$con,$cook_idioma)."
	
	</a></td>";
	echo "</tr>";
	echo "</table><br>";
}
?>

<br>

<div id="wrapper">
<form name="frm_peca" method="post" action="<? $PHP_SELF ?>" enctype='multipart/form-data'>
<input type="hidden" name="peca" value="<? echo $peca ?>">

<!-- TABELA CONTENDO OS CAMPOS PARA BUSCA DA PEÇA -->
<br>
<table width='500' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF' style='font-family: verdana; font-size: 12px'>
<tr align='center'>
		<td bgcolor='#D9E2EF'><b><?fecho("referencia",$con,$cook_idioma)?></b> (*)</td>
		<td bgcolor='#D9E2EF'><b><?fecho("descricao",$con,$cook_idioma)?></b> (*)</td>
</tr>
<tr align='center'>
		<td bgcolor='#FfFfFF'><input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20"><a href="javascript: fnc_pesquisa_peca2 (document.frm_peca.referencia,document.frm_peca.descricao,'referencia')"><IMG SRC="imagens_admin/btn_lupa.gif" ></a></td>
		<td bgcolor='#FfFfFF'><input class='frm' size='50' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50"><a href="javascript: fnc_pesquisa_peca2 (document.frm_peca.referencia,document.frm_peca.descricao,'descricao')"><IMG SRC="imagens_admin/btn_lupa.gif" ></a></td>
</tr>
<tr>
	<td colspan='2' align='center' bgcolor='#FFFFFF'><INPUT TYPE="submit" name='btn_busca' value='Buscar'></td>
</tr>
</table>
<?/* RAMONNA LIGOU DIA 05/11/2009 E PEDIU PARA TIRAR - NÃO PODE TER ESSE DOWNLOAD
<p align='center'>Para fazer o download da tabela de preços LAI em formato XLS ou TXT, <a href='tabela_precos_xls.php?tabela=43'>clique aqui</a></p>
			
<p align='center'>Para fazer o download da tabela de preços ASTEC em formato XLS ou TXT, <a href='tabela_precos_xls.php?tabela=44'>clique aqui</a></p>
*/?>
</form>
<!-- FIM -->




<?
if (strlen($_POST["btn_busca"]) > 0) {
	$btnacao = trim($_POST["btn_busca"]);
}


$referencia = trim($_POST['referencia']);


//FAZ A PESQUISA DA PEÇA PRA SABER SE A MESMA ESTÁ CADASTRADA NO NOSSO BANCO DE DADOS.
if( $btnacao == 'Buscar'){ 
	if(($referencia > 0) OR ($referencia == 0)) {
	//OBTEM O CODIGO DA PEÇA DO BANCO(SEQUENCE)
		$sql = "SELECT peca FROM tbl_peca
					WHERE	referencia = '$referencia'
					AND		fabrica = $login_fabrica limit 30"; 
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0){
			$peca  = trim(pg_result($res,0,peca));
		}else{ echo "<FONT COLOR=\"#FF0000\"><B>".traduz("peca.nao.encontrada",$con,$cook_idioma)."</B></FONT><br>"; include "rodape.php"; exit; }
	}
}


$teste = 1;
if($teste == 1){
$sql =	"SELECT tbl_tipo_posto.descricao,
				((100 - tbl_icms.indice) / 100) AS icms
		FROM tbl_posto
		JOIN tbl_posto_fabrica   on tbl_posto_fabrica.posto   = tbl_posto.posto
								and tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN tbl_fabrica         on tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
		JOIN tbl_tipo_posto      on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
		JOIN tbl_icms            on tbl_icms.estado_destino   = tbl_posto.estado
		WHERE tbl_fabrica.estado        = tbl_icms.estado_origem
		AND   tbl_posto_fabrica.posto   = $login_posto
		AND   tbl_posto_fabrica.fabrica = $login_fabrica;";
//		if($ip=='201.76.78.194') echo nl2br($sql);
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	$icms = pg_result($res, 0, icms);

	$caso = 0;

	switch ( pg_result($res, 0, descricao) ) {
		case "LAI" :
			$caso = 1;
			$sql =	"SELECT y.peca                              ,
							y.referencia                        ,
							y.descricao                         ,
							y.ipi                               ,
							y.lai                               ,
							y.astec                             ,
							tbl_tabela.tabela                   ,
							tbl_tabela_item.preco AS consumidor
					FROM (
							SELECT  x.peca                         ,
									x.referencia                   ,
									x.descricao                    ,
									x.ipi                          ,
									x.preco               AS lai   ,
									tbl_tabela_item.preco AS astec
							FROM (
									SELECT  tbl_peca.peca         ,
											tbl_peca.referencia   ,
											tbl_peca.descricao    ,
											tbl_peca.ipi          ,
											tbl_tabela_item.preco
									FROM tbl_tabela_item
									JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela
									JOIN tbl_peca   ON tbl_peca.peca     = tbl_tabela_item.peca
									WHERE tbl_tabela.fabrica      = $login_fabrica
									AND   tbl_peca.fabrica      = $login_fabrica
									AND   tbl_tabela.sigla_tabela = 'LAI'
							) AS x
							JOIN tbl_tabela_item ON tbl_tabela_item.peca = x.peca
							JOIN tbl_tabela      ON tbl_tabela.tabela    = tbl_tabela_item.tabela
							JOIN tbl_peca        ON tbl_peca.peca        = tbl_tabela_item.peca
							WHERE tbl_tabela.fabrica = $login_fabrica
							AND   tbl_tabela.sigla_tabela = 'ASTEC'
					) AS y
					JOIN tbl_tabela_item ON tbl_tabela_item.peca = y.peca
					JOIN tbl_tabela      ON tbl_tabela.tabela    = tbl_tabela_item.tabela
					JOIN tbl_peca        ON tbl_peca.peca        = tbl_tabela_item.peca
					WHERE tbl_tabela.fabrica      = $login_fabrica
					AND   tbl_tabela.sigla_tabela = 'CONSUMIDOR'
					AND   tbl_peca.referencia = '$referencia'
					ORDER BY y.referencia;";
		break;
		case "ASTEC" :
			$caso = 2;
			$sql =	"SELECT y.peca                              ,
							y.referencia                        ,
							y.descricao                         ,
							y.ipi                               ,
							y.astec                             ,
							tbl_tabela.tabela                   ,
							tbl_tabela_item.preco AS consumidor
					FROM (
							SELECT  tbl_peca.peca                  ,
									tbl_peca.referencia            ,
									tbl_peca.descricao             ,
									tbl_peca.ipi                   ,
									tbl_tabela_item.preco AS astec
							FROM tbl_tabela_item
							JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela
							JOIN tbl_peca   ON tbl_peca.peca     = tbl_tabela_item.peca
							WHERE tbl_tabela.fabrica = $login_fabrica
							AND   tbl_peca.fabrica      = $login_fabrica
							AND   tbl_tabela.sigla_tabela = 'ASTEC'
					) AS y
					JOIN tbl_tabela_item ON tbl_tabela_item.peca = y.peca
					JOIN tbl_tabela      ON tbl_tabela.tabela    = tbl_tabela_item.tabela
					JOIN tbl_peca        ON tbl_peca.peca        = tbl_tabela_item.peca
					WHERE tbl_tabela.fabrica      = $login_fabrica
					AND   tbl_tabela.sigla_tabela = 'CONSUMIDOR'
					AND   tbl_peca.referencia = '$referencia'
					ORDER BY y.referencia;";
		break;
	}
	//echo nl2br($sql);
//	if($ip=='201.76.78.194') echo nl2br($sql);

	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<table width='600' border='0' cellpadding='2' cellspacing='1' align='center'>";
		echo "<tr class='Menu'>";
		echo "<td nowrap colspan='5'>".traduz("tabela.de.preco.da.peca",$con,$cook_idioma)."</td>";
		echo "</tr>";
		
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i % 20 == 0 OR $zz == 1) {
				echo "<tr class='Menu'>";
				echo "<td nowrap>".traduz("peca",$con,$cook_idioma)."</td>";
				echo "<td nowrap>".traduz("ipi",$con,$cook_idioma)."</td>";
				if ($caso == 1) echo "<td nowrap>LAI<br>".traduz("com.ipi",$con,$cook_idioma)."</td>";
				echo "<td nowrap>ASTEC<br>".traduz("com.ipi",$con,$cook_idioma)."</td>";
				if($login_fabrica <> 14){
					echo "<td nowrap>".traduz("consumidor",$con,$cook_idioma)."<br>".traduz("com.ipi",$con,$cook_idioma)."</td>";
				}
				echo "</tr>";
			}
			
			$peca             = pg_result($res, $i, peca);
			$tabela             = pg_result($res, $i, tabela);
			$peca_referencia  = pg_result($res, $i, referencia);
			$peca_descricao   = pg_result($res, $i, descricao);
			$ipi              = pg_result($res, $i, ipi);
			$ipi_agregado     = ($ipi / 100) + 1;
			$icms_consumidor  = (100 - 17) / 100;
			
			if ($caso == 1) $preco_lai        = pg_result($res, $i, lai) * $ipi_agregado / $icms;
			$preco_astec      = pg_result($res, $i, astec) * $ipi_agregado / $icms;
			$preco_consumidor = pg_result($res, $i, consumidor) * $ipi_agregado / $icms_consumidor;
			
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
			
			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td nowrap>$peca_referencia - $peca_descricao</td>";
			echo "<td nowrap align='right'>$ipi%</td>";
			if ($caso == 1) echo "<td nowrap align='right'>".traduz("r$",$con,$cook_idioma).number_format($preco_lai,5,",", ".") . "</td>";
			echo "<td nowrap align='right'>".traduz("r$",$con,$cook_idioma).number_format($preco_astec,5,",", ".") . "</td>";
			if($login_fabrica <> 14){
				echo "<td nowrap align='right'>".traduz("r$",$con,$cook_idioma).number_format($preco_consumidor,5,",", ".") . "</td>";
			}
			echo "</tr>";
		
		}

		

		echo "</table>";
	}
}
}
?>

<br>

<? include "rodape.php"; ?>
