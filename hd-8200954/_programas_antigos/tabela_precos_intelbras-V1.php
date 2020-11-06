<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';



$liberar_preco = true ;
if ($login_fabrica == 3 AND $login_e_distribuidor <> true AND ($login_distribuidor == 1007 OR $login_distribuidor == 560)) $liberar_preco = false;


$title = "Tabela de Preços";

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

if ($login_fabrica == 3) {
	if (strlen($descricao_produto) == 0 AND strlen($referencia_produto) == 0 AND strlen($descricao_peca) == 0 AND strlen($referencia_peca) == 0) {
		$tabela = "";
	}

}
?>

<? include "javascript_pesquisas.php" ?>

<script language="JavaScript">

/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/
function fnc_pesquisa_produtoXXX (referencia,descricao,tabela) {
	var url = "";
	if (referencia.value != "" || descricao.value != "") {
		url = "pesquisa_tabela.php?referencia=" + referencia.value + "&descricao=" + descricao.value + "&retorno=<?echo $PHP_SELF?>";
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.referencia = referencia;
		janela.descricao  = descricao;
		janela.tabela     = tabela;
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

.Conteudo {
	font-size: 10px;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: normal;
	color: #000000;
}
</style>

<br>

<?
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
					ORDER BY y.referencia;";
		break;
		case "ASTEC" :
			$caso = 2;
			$sql =	"SELECT y.peca                              ,
							y.referencia                        ,
							y.descricao                         ,
							y.ipi                               ,
							y.astec                             ,
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
							AND   tbl_tabela.sigla_tabela = 'ASTEC'
					) AS y
					JOIN tbl_tabela_item ON tbl_tabela_item.peca = y.peca
					JOIN tbl_tabela      ON tbl_tabela.tabela    = tbl_tabela_item.tabela
					JOIN tbl_peca        ON tbl_peca.peca        = tbl_tabela_item.peca
					WHERE tbl_tabela.fabrica      = $login_fabrica
					AND   tbl_tabela.sigla_tabela = 'CONSUMIDOR'
					ORDER BY y.referencia;";
		break;
	}
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<table width='500' border='0' cellpadding='2' cellspacing='1' align='center'>";
		echo "<tr class='Menu'>";
		echo "<td nowrap colspan='5'>PARA LOCALIZAR UMA PEÇA, TECLE \"CTRL + F\" E INFORME A REFERÊNCIA DA PEÇA</td>";
		echo "</tr>";
		
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i % 20 == 0 OR $zz == 1) {
				echo "<tr class='Menu'>";
				echo "<td nowrap>PEÇA</td>";
				echo "<td nowrap>IPI</td>";
				if ($caso == 1) echo "<td nowrap>LAI<br>Com IPI</td>";
				echo "<td nowrap>ASTEC<br>Com IPI</td>";
				echo "<td nowrap>CONSUMIDOR<br>Com IPI</td>";
				echo "</tr>";
			}
			
			$peca             = pg_result($res, $i, peca);
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
			if ($caso == 1) echo "<td nowrap align='right'>R$ " . number_format($preco_lai,5,",", ".") . "</td>";
			echo "<td nowrap align='right'>R$ " . number_format($preco_astec,5,",", ".") . "</td>";
			echo "<td nowrap align='right'>R$ " . number_format($preco_consumidor,5,",", ".") . "</td>";
			echo "</tr>";
		}
		
		echo "</table>";
	}
}
?>

<br>

<? include "rodape.php"; ?>
