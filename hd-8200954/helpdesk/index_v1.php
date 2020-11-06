<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$menu_cor_fundo="EEEEEE";
$menu_cor_linha="BBBBBB";


if($_GET['conteudo'])  $conteudo  = $_GET['conteudo']; 
//echo $conteudo."<br>".$ajuda; 

//SELECT DA TABELA DE ESTATISTICAS DE CHAMADAS---------------------------

$sql1 = "SELECT count (*) AS total_novo
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'novo'
	AND admin=$login_admin";


$res1 = @pg_exec ($con,$sql1);

if (@pg_numrows($res1) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_novo           = pg_result($res1,0,total_novo);
	}


$sql2 = "SELECT	 COUNT (*) AS total_analise
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'análise' 
	AND admin=$login_admin";

$res2 = @pg_exec ($con,$sql2);

if (@pg_numrows($res2) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_analise           = pg_result($res2,0,total_analise);
	}



$sql3 = "SELECT	 COUNT (*) AS total_aprovacao
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'aprovação'
	AND admin=$login_admin";

$res3 = @pg_exec ($con,$sql3);

if (@pg_numrows($res3) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_aprovacao           = pg_result($res3,0,total_aprovacao);
	}



$sql4 = "SELECT	 COUNT (*) AS total_resolvido
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'resolvido'
	AND admin=$login_admin";

$res4 = @pg_exec ($con,$sql4);

if (@pg_numrows($res4) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_resolvido           = pg_result($res4,0,total_resolvido);
	}

//FIM DO SELECT DA TABELA ESTATISTICAS DE CHAMADAS---------------------------------

?>

<?
/*$sql = "select	 tbl_produto.referencia,
				 tbl_produto.descricao,
				 tbl_produto.produto
			from tbl_produto

	JOIN    tbl_linha     USING (linha)
	LEFT JOIN tbl_familia USING (familia)
	WHERE   tbl_linha.fabrica = 3
	AND     tbl_produto.ativo = 't'
	order by tbl_produto.descricao
	LIMIT 10";
$res = pg_exec ($con,$sql);

for($i = 0; $i < pg_numrows($res); $i++){
	$ref      = pg_result($res,$i,referencia);
	$desc     = pg_result($res,$i,descricao);
	$produto  = pg_result($res,$i,produto);

		$sql2 = "SELECT  tbl_peca.referencia          ,
						tbl_peca.descricao
				FROM    tbl_lista_basica
				JOIN    tbl_peca USING (peca)
				WHERE   tbl_lista_basica.fabrica = 3
				AND     tbl_lista_basica.produto = $produto
				ORDER BY tbl_peca.descricao";
		$res2 = pg_exec ($con,$sql2);

	echo "<br><br>Produto = $ref - $desc <br>";

	for ($j = 0 ; $j < pg_numrows($res2) ; $j++) {
		$ref_peca      = pg_result($res2,$j,referencia);
		$desc_peca     = pg_result($res2,$j,descricao);
		
		echo "$ref_peca - $desc_peca <br>";
	}
}
*/

//LBM
$fabricante = 3;
$sql = "SELECT  trim(tbl_produto.produto)                      AS produto    ,
				trim(tbl_produto.referencia)                   AS produto_referencia    ,
				trim(tbl_peca.peca)                            AS peca       ,
				trim(tbl_peca.referencia)                      AS peca_referencia 
		FROM    tbl_lista_basica
		JOIN    tbl_produto      ON tbl_produto.produto      = tbl_lista_basica.produto
		JOIN    tbl_linha        ON tbl_linha.linha          = tbl_produto.linha
								AND tbl_linha.fabrica        = $fabricante
		JOIN    tbl_peca         ON tbl_peca.peca            = tbl_lista_basica.peca
								AND tbl_peca.fabrica         = $fabricante
		WHERE   tbl_lista_basica.fabrica = $fabricante
		AND     tbl_peca.ativo    IS TRUE
		AND     tbl_produto.ativo IS TRUE ";
$sql .= "ORDER BY tbl_produto.referencia, tbl_peca.referencia LIMIT 10;";
$res = pg_exec($con,$sql);

for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$ref_produto      = pg_result($res,$i,produto_referencia);
		$ref_peca         = pg_result($res,$i,peca_referencia);
		
		echo "$ref_produto - $ref_peca <br>";
	}

//


/*
$sql = "SELECT		tbl_peca.referencia,
					tbl_peca.peca,
					tbl_peca.descricao
			FROM    tbl_peca
			WHERE   tbl_peca.fabrica = 3
			ORDER BY    tbl_peca.descricao
			LIMIT 20;";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$ref    = pg_result($res,$i,referencia);
		$desc   = pg_result($res,$i,descricao);
		$peca   = pg_result($res,$i,peca);

		echo "$ref - $desc - $peca<br>";
}
*/

?>


<html>
<head>
<title>Telecontrol - Help Desk</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>
<body>
<?
include "menu.php";
?>
		<table width="98%" align="center">
			<tr>
				<td colspan="2" bgcolor="<?=$menu_cor_linha?>" width="1" height="1"></td>
			</tr>
			<tr>
				<td class="Titulo"><img src="imagem/help.png" width="32" height="32"border='0'align='absmiddle'> HOME</td>
			</tr>
			<tr>
				<td colspan="2" bgcolor="<?=$menu_cor_linha?>" width="1" height="1"></td>
			</tr>
			<tr>
				<td class="Titulo_sub" align="center">Seja bem-vindo ao sistema de Help Desk. Esta é uma ferramenta de suporte, exclusiva para clientes da Telecontrol Assist</td>
			</tr>
			
			<tr>
				<td><div align="justify">
					
					<br>
					<dd>O <b>Help Desk</b> é uma de ferramenta de atendimento ao usuário do sistema em que há uma Equipe de Suporte Técnico especializada no esclarecimento de dúvidas, solicitações de serviços, tais como criações e alterações de telas do Sistema Assist. Atua no levantamento de problemas referentes ao sistema Assist, abrindo chamados e encaminhando à Equipe de Tecnologia para resolução dos mesmos.<br> 
					<dd>Estes chamados encaminhados para a Equipe de Tecnologia possuem um tempo determinado para resolução e uma prioridade, tendo com isso a intenção de organizar e resolver os chamados da melhor maneira possível.<br> 
					<dd>Sua estrutura é composta por atendentes qualificados para esclarecer qualquer tipo de dúvida referente ao sistema Assist.
					
					</div>
				</td>
			</tr>
			<tr>
			<td><br></td>
			</tr>
			<tr>
			<td bgcolor='FFcc00'><a href='chamado_detalhe.php'><img src="imagem/01.jpg" width="32" height="32"border='0'> <b>INSERIR CHAMADO</b></a></td>
			</tr>
<!-- ====================INICIO DA TABELA DE ESTATISTICAS DE CHAMADAS=========================================   -->
			
			<TR>
				<TD>
				<TABLE>
					<TR>
						<TD colspan="2" bgcolor="FFcc00">ESTATÍSTICAS DE CHAMADAS</TD>
					</TR>
					<TR>
						<TD bgcolor="FFcc00"><CENTER>Status</CENTER></TD>
						<TD bgcolor="FFcc00"><CENTER>Total</CENTER></TD>
					</TR>
					<TR bgcolor="eeeeee">
						<TD>Novo</TD>
						<TD><CENTER><? echo $total_novo ?></CENTER></TD>
					</TR>
					<TR bgcolor="eeeeee">
						<TD>Análise</TD>
						<TD><CENTER><? echo $total_analise ?></CENTER></TD>
					</TR>
					<TR bgcolor="eeeeee">
						<TD>Aprovação</TD>
						<TD><CENTER><? echo $total_aprovacao ?></CENTER></TD>
					</TR>
					<TR bgcolor="eeeeee">
						<TD>Resolvido</TD>
						<TD><CENTER><? echo $total_resolvido ?></CENTER></TD></TR>
					</TR>
				</TABLE>
				</TD>
			</TR>
<!-- ====================FIM DA TABELA DE ESTATISTICAS DE CHAMADAS=========================================   -->
		</table>
		</td>
	</tr>
<tr>


		<td height ='7' colspan='2' class='rodape' align='center'>TELECONTROL NETWORKING LTDA - <?=date(Y)?></td>
	</tr>
</table>
</body>

</body>
</html>