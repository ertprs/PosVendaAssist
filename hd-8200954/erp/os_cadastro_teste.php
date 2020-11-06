<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

$cook_posto_fabrica = $_COOKIE['cook_posto_fabrica'];
$cook_fabrica       = $_COOKIE['cook_fabrica'];
$cook_posto         = $_COOKIE['cook_posto'];

if ($cook_posto_fabrica == 'deleted') {
	echo "<center><b>Seu computador está possivelmente infectado por vírus que atrabalha o correto funcionamento deste site. É um vírus que deleta os <i>cookies</i> que o site precisa para trabalhar.<p>Por favor, atualize seu anti-vírus ou entre em contato com o suporte técnico que lhe vendeu este computador.<p>Qualquer dúvida, peça para que seu técnico entre em contato com a TELECONTROL. (14) 3413-6588 ou suporte@telecontrol.com.br </b></center>";
	exit;
}

if (strlen ($cook_posto_fabrica) == 0) {
	header ("Location: index.php");
	exit;
}


$sql = "SELECT  tbl_posto_fabrica.posto,
			tbl_posto_fabrica.fabrica
	FROM    tbl_posto_fabrica
	WHERE	tbl_posto_fabrica.fabrica = $cook_fabrica
	AND     tbl_posto_fabrica.posto   = $cook_posto";
$res = pg_exec ($con,$sql);

$login_fabrica = pg_result ($res,0,fabrica);
$login_posto   = pg_result ($res,0,posto);

$sql = "SELECT	tbl_posto.posto                           ,
			tbl_posto.nome                                ,
			tbl_posto.cnpj                                ,
			tbl_posto.pais                                ,
			tbl_fabrica.nome as fabrica_nome              ,
			tbl_posto_fabrica.pedido_em_garantia          ,
			tbl_posto_fabrica.tipo_posto                  ,
			tbl_posto_fabrica.distribuidor                ,
			tbl_posto_fabrica.reembolso_peca_estoque      ,
			tbl_posto_fabrica.codigo_posto                ,
			tbl_fabrica.fabrica                           ,
			tbl_tipo_posto.distribuidor AS e_distribuidor ,
			tbl_posto_fabrica.pedido_via_distribuidor     ,
			tbl_posto_fabrica.credenciamento              ,
			tbl_fabrica.pedir_causa_defeito_os_item       ,
			tbl_fabrica.pedir_defeito_constatado_os_item  ,
			tbl_fabrica.pedir_solucao_os_item
	FROM	tbl_posto
	JOIN	tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
	JOIN	tbl_fabrica       ON tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
	JOIN    tbl_tipo_posto    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
	WHERE	tbl_posto_fabrica.fabrica = $cook_fabrica
	AND     tbl_posto_fabrica.posto   = $cook_posto";
$res = @pg_exec ($con,$sql);

if (@pg_numrows ($res) == 0) {
header ("Location: index.php");
exit;
}

echo "*****************************************************";
echo "
cook_posto_fabrica = $cook_posto_fabrica <br>
cook_fabrica       = $cook_fabrica       <br>
cook_posto         = $cook_posto         <br>";

echo "*****************************************************";


 //include "rodape.php";
 ?>
