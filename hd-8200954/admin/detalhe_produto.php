<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

include 'funcoes.php';

$produto = $_GET["produto"];
$linha = $_GET["linha"];
$familia = $_GET["familia"];
$motivo_sintetico = $_GET["motivo_sintetico"];
$motivo_analitico = $_GET["motivo_analitico"];
$defeito_constatado = $_GET["defeito_constatado"];
$analise_produto = $_GET["analise_produto"];
$data_inicial = $_GET["data_inicial"];
$data_final = $_GET["data_final"];

$xdata_inicial = dateFormat($data_inicial, 'dmy', 'y-m-d');
$xdata_final   = dateFormat($data_final,   'dmy', 'y-m-d');
$xdata_inicial .= " 00:00:00";
$xdata_final .= " 23:59:59";

    if (!empty($xdata_inicial) && !empty($xdata_final)) {
        $cond_periodo = "AND (tbl_os_laudo.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final') ";

        $msg_erro = "Data Inserida no formulário inválida";
    }

    if(!empty($linha)){
        $cond_linha = "AND tbl_produto.linha IN({$linha}) ";
    }

    if(!empty($familia)){
        $cond_familia = "AND tbl_produto.familia IN({$familia}) ";
    }

    if(!empty($motivo_sintetico)){
        $cond_sintetico = "AND tbl_os_laudo.motivo_sintetico IN({$motivo_sintetico}) ";
    }

    if(!empty($motivo_analitico)){
        $cond_analitico = "AND tbl_os_laudo.motivo_analitico IN({$motivo_analitico}) ";
    }

    if(!empty($defeito_constatado)){
        $cond_defeito = "AND tbl_os_laudo.defeito_constatado IN({$defeito_constatado}) ";
    }

    if(!empty($analise_produto)){
        $cond_analise = "AND tbl_os_laudo.analise_produto IN({$analise_produto}) ";
    }


	$sql = "SELECT DISTINCT
			tbl_os_laudo.os_laudo,
            tbl_produto.descricao as descricao_produto,
            tbl_produto.referencia,
            tbl_familia.familia,
            tbl_familia.descricao as descricao_familia,
            tbl_linha.nome,
            tbl_motivo_sintetico.descricao as descricao_sintetico, 
            tbl_motivo_analitico.descricao as descricao_analitico,
            tbl_analise_produto.descricao as analise_produto
            FROM tbl_os_laudo
            JOIN tbl_produto ON tbl_produto.produto = tbl_os_laudo.produto
            JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
            JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
            JOIN tbl_motivo_sintetico ON tbl_motivo_sintetico.motivo_sintetico = tbl_os_laudo.motivo_sintetico
            JOIN tbl_motivo_analitico ON tbl_motivo_analitico.motivo_analitico = tbl_os_laudo.motivo_analitico
            LEFT JOIN tbl_analise_produto ON tbl_analise_produto.analise_produto = tbl_os_laudo.analise_produto
            WHERE tbl_os_laudo.fabrica = $login_fabrica
            AND tbl_os_laudo.produto = $produto
            $cond_periodo
            $cond_linha
            $cond_familia
            $cond_sintetico
            $cond_analitico
            $cond_defeito
            $cond_analise
            $cond_referencia";

            $res = pg_query($con, $sql);
?>
<script src="bootstrap/js/bootstrap.js"></script>
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
<?
if (pg_num_rows($res) > 0) {
	?>
	<table class="table table-striped table-bordered">
		<thead>
            <tr class="titulo_coluna">
            	<th>Devolução</th>
                <th>Referência Produto</th>
                <th>Descrição Produto</th>
                <th>Motivo Sintético</th>
                <th>Motivo Analítico</th>
                <th>Análise Produto</th>
                <th>Qtde. Peças Trocadas</th>
            </tr>
        </thead>
	<?
	for ($i=0;$i < pg_num_rows($res);$i++) {
		$qtde_pecas_trocadas = 0;

		$devolucao = pg_fetch_result($res,$i,"os_laudo"); 
		$referencia = pg_fetch_result($res,$i,"referencia");
		$descricao = pg_fetch_result($res,$i,"descricao_produto");
		$motivo_sintetico = pg_fetch_result($res,$i,"descricao_sintetico");
		$motivo_analitico = pg_fetch_result($res,$i,"descricao_analitico");
		$analise_produto = pg_fetch_result($res,$i,"analise_produto");

		$sql_qtde = "SELECT tbl_os_laudo_peca.qtde,tbl_servico_realizado.troca_de_peca 
					 FROM tbl_os_laudo_peca
					 JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_laudo_peca.servico_realizado
					 WHERE tbl_os_laudo_peca.os_laudo = $devolucao
					 AND tbl_servico_realizado.troca_de_peca IS TRUE";
		$res_qtde = pg_query($con, $sql_qtde);
		
		for ($x=0;$x<pg_num_rows($res_qtde);$x++) {
			$qtde_pecas_trocadas += pg_fetch_result($res_qtde,$x,"qtde");
		}			  
	?>
            <tr>
                <td class="tac"><?= $devolucao ?></td>
                <td><?= $referencia  ?></a></td>
                <td><?= $descricao    ?></td>
                <td><?= $motivo_sintetico ?></td>
                <td class="tac"><?= $motivo_analitico ?></td>
                <td class="tac"><?= $analise_produto ?></td>
                <td><?= $qtde_pecas_trocadas ?></td>
            </tr>
	<?
	}
	?>
	</table>
	<?
} else {
?>
<div class="alert alert-warning"><h4>Erro na consulta, verifique novamente o formulário</h4></div>
<?
}
