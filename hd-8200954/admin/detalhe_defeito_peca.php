<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

include 'funcoes.php';

$peca         = $_GET["peca"];
$produto      = $_GET["produto"];
$peca_defeito = $_GET["peca_defeito"];
$numero_serie = $_GET["numero_serie"];
$xlinha = $_GET["linha"];
$xfamilia = $_GET["familia"];
$xmotivo_sintetico = $_GET["motivo_sintetico"];
$xmotivo_analitico = $_GET["motivo_analitico"];
$xdefeito_constatado = $_GET["defeito_constatado"];
$xanalise_produto = $_GET["analise_produto"];

$data_inicial = $_GET["data_inicial"];
$data_final = $_GET["data_final"];

$xdata_inicial = dateFormat($data_inicial, 'dmy', 'y-m-d');
$xdata_final   = dateFormat($data_final,   'dmy', 'y-m-d');
$xdata_inicial .= " 00:00:00";
$xdata_final .= " 23:59:59";

$cond_suggar = "";
$join_suggar = "";

if ($login_fabrica == 24) {
    if(!empty($xlinha)){
        $cond_suggar = " AND tbl_produto.linha IN({$xlinha}) ";
    }

    if(!empty($xfamilia)){
        $cond_suggar .= " AND tbl_produto.familia IN({$xfamilia}) ";
    }

    if(!empty($xmotivo_sintetico)){
        $cond_suggar .= " AND tbl_os_laudo.motivo_sintetico IN({$xmotivo_sintetico}) ";
    }

    if(!empty($xmotivo_analitico)){
        $cond_suggar .= " AND tbl_os_laudo.motivo_analitico IN({$xmotivo_analitico}) ";
    }

    if(!empty($xdefeito_constatado)){
        $cond_suggar .= " AND tbl_os_laudo.defeito_constatado IN({$xdefeito_constatado}) ";
    }

    if(!empty($xanalise_produto)){
        $cond_suggar .= " AND tbl_os_laudo.analise_produto IN({$xanalise_produto}) ";
    }

    $join_suggar = "    JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
                        JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
                        JOIN tbl_motivo_sintetico ON tbl_motivo_sintetico.motivo_sintetico = tbl_os_laudo.motivo_sintetico
                        JOIN tbl_motivo_analitico ON tbl_motivo_analitico.motivo_analitico = tbl_os_laudo.motivo_analitico
                        LEFT JOIN tbl_analise_produto ON tbl_analise_produto.analise_produto = tbl_os_laudo.analise_produto ";
}

/*HD-4093050*/
if (!empty($peca)) {
    $dados = array();
    $dados["tipo"]    = "porcentagem";
    $relacao_defeitos = array();
    $aux_json_dados[] = array("Produto", 'Percentual');
    $total_geral      = 0;
    $aux_th = "Defeito";

    $aux_sql = "SELECT referencia || ' - ' || descricao AS referencia_descricao FROM tbl_peca WHERE peca = $peca LIMIT 1";
    $aux_res = pg_query($con, $aux_sql);
    $referencia_descricao = pg_fetch_result($aux_res, 0, 'referencia_descricao');

    if (!empty($xdata_inicial) && !empty($xdata_final)) {
        $cond_periodo = "AND (tbl_os_laudo.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final') ";
    }

    $aux_sql = "
        SELECT DISTINCT COUNT(tbl_os_laudo_peca.defeito) as total_defeitos,
            tbl_defeito.descricao as descricao_defeito
        FROM tbl_os_laudo_peca
        JOIN tbl_os_laudo ON tbl_os_laudo_peca.os_laudo = tbl_os_laudo.os_laudo
        JOIN tbl_defeito  ON tbl_defeito.defeito           = tbl_os_laudo_peca.defeito
        join tbl_produto on tbl_os_laudo.produto = tbl_produto.produto and tbl_produto.fabrica_i = $login_fabrica 
        $join_suggar
        WHERE 
            tbl_os_laudo_peca.peca = $peca
            $cond_periodo
            $cond_suggar
        GROUP BY
        tbl_os_laudo_peca.defeito,
        tbl_defeito.descricao
    ";
    $aux_res = pg_query($con, $aux_sql);

    for ($z = 0; $z < pg_num_rows($aux_res); $z++) {
        $total_geral += pg_fetch_result($aux_res, $z, 'total_defeitos');
    }

    for ($z = 0; $z < pg_num_rows($aux_res); $z++) {
        $qtde_defeito = (int) pg_fetch_result($aux_res, $z, 'total_defeitos');
        $defeito      = pg_fetch_result($aux_res, $z, 'descricao_defeito');
        $porcentagem  = ($qtde_defeito * 100) / $total_geral;

        $relacao_defeitos[$z]["qtde_defeito"] = $qtde_defeito;
        $relacao_defeitos[$z]["defeito"]      = $defeito;
        $relacao_defeitos[$z]["porcentagem"]  = $porcentagem;
        $aux_json_dados[] = array(
            utf8_encode($defeito),
            (int)$qtde_defeito
        );
    }

    $dados["referencia_descricao"] = $referencia_descricao;
    $dados["total_geral"]          = $total_geral;
    $dados["relacao_defeitos"]     = $relacao_defeitos;
    $grafico_titulo                = "Ocorrência de Defeitos na Peça $referencia_descricao";
    $jsonDados = json_encode($aux_json_dados, true);
} else if (!empty($produto)) {
    $dados = array();
    $dados["tipo"]    = "porcentagem";
    $relacao_defeitos = array();
    $aux_json_dados[] = array("Produto", 'Percentual');
    $total_geral      = 0;
    $aux_th = "Produto";

    $aux_sql = "SELECT referencia || ' - ' || descricao AS referencia_descricao FROM tbl_produto WHERE produto = $produto LIMIT 1";
    $aux_res = pg_query($con, $aux_sql);
    $referencia_descricao = pg_fetch_result($aux_res, 0, 'referencia_descricao');

    if (!empty($xdata_inicial) && !empty($xdata_final)) {
        $cond_periodo = "AND (tbl_os_laudo.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final') ";
    }

    $aux_sql = "
        SELECT  tbl_produto.descricao as produto_descricao,
                tbl_produto.referencia as produto_referencia, 
                COUNT(tbl_produto.produto) AS total_defeito
        FROM tbl_os_laudo
            left JOIN tbl_defeito_constatado USING(defeito_constatado)
            join tbl_produto on tbl_os_laudo.produto = tbl_produto.produto and tbl_produto.fabrica_i = $login_fabrica 
            $join_suggar
        WHERE   tbl_os_laudo.produto = $produto
                AND tbl_os_laudo.fabrica = $login_fabrica
                $cond_periodo
                $cond_suggar
        GROUP BY
        tbl_produto.descricao,
        tbl_produto.referencia
        ORDER BY tbl_produto.descricao
    ";
    $aux_res = pg_query($con, $aux_sql);

    if(pg_num_rows($aux_res)==0){
        $erro_defeito = "Laudos sem defeito constatado informado. ";
    }

    for ($z = 0; $z < pg_num_rows($aux_res); $z++) {
        $total_geral += pg_fetch_result($aux_res, $z, 'total_defeito');
    }   

    for ($z = 0; $z < pg_num_rows($aux_res); $z++) {
        $qtde_defeito = (int) pg_fetch_result($aux_res, $z, 'total_defeito');
        $produto_descricao      = pg_fetch_result($aux_res, $z, 'produto_descricao');
        $produto_referencia      = pg_fetch_result($aux_res, $z, 'produto_referencia');
        
        $produto_descricao_referencia = $produto_referencia ." - ". $produto_descricao;

        $porcentagem  = ($qtde_defeito * 100) / $total_geral;

        $relacao_defeitos[$z]["qtde_defeito"] = $qtde_defeito;
        $relacao_defeitos[$z]["defeito"]      = $produto_descricao_referencia;
        $relacao_defeitos[$z]["porcentagem"]  = $porcentagem;
        $aux_json_dados[] = array(
            utf8_encode($produto_descricao_referencia),
            (int)$qtde_defeito
        );
    }

    $dados["referencia_descricao"] = $referencia_descricao;
    $dados["total_geral"]          = $total_geral;
    $dados["relacao_defeitos"]     = $relacao_defeitos;
    $grafico_titulo                = "Ocorrência do Produto $referencia_descricao";
    $jsonDados = json_encode($aux_json_dados, true);
} else if (!empty($peca_defeito)) {
    $dados = array();
    $dados["tipo"]    = "porcentagem";
    $relacao_defeitos = array();
    $cont_p           = array();
    $aux_json_dados[] = array("Produto", 'Percentual');
    $total_geral      = 0;
    $aux_th           = "Produto";

    $aux_sql = "SELECT descricao AS referencia_descricao FROM tbl_defeito WHERE defeito = $peca_defeito LIMIT 1";
    $aux_res = pg_query($con, $aux_sql);
    $referencia_descricao = pg_fetch_result($aux_res, 0, 'referencia_descricao');

    if (!empty($xdata_inicial) && !empty($xdata_final)) {
        $cond_periodo = "AND (tbl_os_laudo.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final') ";
    }

    $aux_sql = "
        SELECT DISTINCT(COUNT(tbl_os_laudo_peca.defeito)) AS total_defeito,
               tbl_produto.referencia || ' - ' || tbl_produto.descricao AS descricao_produto
        FROM tbl_os_laudo_peca
            JOIN tbl_os_laudo ON tbl_os_laudo.os_laudo = tbl_os_laudo_peca.os_laudo AND tbl_os_laudo.fabrica = $login_fabrica
            JOIN tbl_produto ON tbl_produto.produto = tbl_os_laudo.produto AND fabrica_i = $login_fabrica
            $join_suggar
        WHERE tbl_os_laudo_peca.defeito = $peca_defeito
        $cond_periodo
        $cond_suggar
        GROUP BY
        tbl_produto.referencia,
        tbl_produto.descricao,
        tbl_os_laudo_peca.defeito
    ";
    $aux_res = pg_query($con,$aux_sql);
    
    for ($z = 0; $z < pg_num_rows($aux_res); $z++) {
        $total_geral += pg_fetch_result($aux_res, $z, 'total_defeito');
    }

    for ($z = 0; $z < pg_num_rows($aux_res); $z++) {
        $qtde_defeito = (int) pg_fetch_result($aux_res, $z, 'total_defeito');
        $defeito      = pg_fetch_result($aux_res, $z, 'descricao_produto');
        $porcentagem  = ($qtde_defeito * 100) / $total_geral;

        $relacao_defeitos[$z]["qtde_defeito"] = $qtde_defeito;
        $relacao_defeitos[$z]["defeito"]      = $defeito;
        $relacao_defeitos[$z]["porcentagem"]  = $porcentagem;
        $aux_json_dados[] = array(
            utf8_encode($defeito),
            (int)$qtde_defeito
        );
    }

    $dados["referencia_descricao"] = $referencia_descricao;
    $dados["total_geral"]          = $total_geral;
    $dados["relacao_defeitos"]     = $relacao_defeitos;
    $grafico_titulo                = "Ocorrência do Defeito ".strtoupper($referencia_descricao);
    $jsonDados = json_encode($aux_json_dados, true);
} else if (!empty($numero_serie)) {
    $dados = array();
    $dados["tipo"]    = "porcentagem";
    $relacao_defeitos = array();
    $aux_json_dados[] = array("Produto", 'Percentual');
    $total_geral      = 0;
    $aux_th = "Produto";

    $referencia_descricao = "Número de Série \"$numero_serie\"";

    if (!empty($xdata_inicial) && !empty($xdata_final)) {
        $cond_periodo = "AND (tbl_os_laudo.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final') ";
    }

    if ($login_fabrica == 24) {
        $num_serie = "tbl_os_laudo.serie ilike '$numero_serie%'";
    } else {
        $num_serie = "tbl_os_laudo.serie = '$numero_serie'";
    }

    $aux_sql = "
        SELECT  tbl_produto.referencia || ' - ' || tbl_produto.descricao as produto,
                COUNT(tbl_os_laudo.serie) AS total_serie
        FROM tbl_os_laudo
            JOIN tbl_produto ON tbl_produto.produto = tbl_os_laudo.produto AND fabrica_i = $login_fabrica
            $join_suggar
        WHERE
            $num_serie
            AND tbl_os_laudo.fabrica = $login_fabrica
            $cond_periodo
            $cond_suggar    
        GROUP BY
        tbl_produto.referencia,
        tbl_produto.descricao
    ";
    
    $aux_res = pg_query($con,$aux_sql);
    
    for ($z = 0; $z < pg_num_rows($aux_res); $z++) {
        $total_geral += pg_fetch_result($aux_res, $z, 'total_serie');
    }

    for ($z = 0; $z < pg_num_rows($aux_res); $z++) {
        $qtde_defeito = (int) pg_fetch_result($aux_res, $z, 'total_serie');
        $defeito      = pg_fetch_result($aux_res, $z, 'produto');
        $porcentagem  = ($qtde_defeito * 100) / $total_geral;

        $relacao_defeitos[$z]["qtde_defeito"] = $qtde_defeito;
        $relacao_defeitos[$z]["defeito"]      = $defeito;
        $relacao_defeitos[$z]["porcentagem"]  = $porcentagem;
        $aux_json_dados[] = array(
            utf8_encode($defeito),
            (int)$qtde_defeito
        );
    }

    $dados["referencia_descricao"] = $referencia_descricao;
    $dados["total_geral"]          = $total_geral;
    $dados["relacao_defeitos"]     = $relacao_defeitos;
    $grafico_titulo                = "Ocorrência do $numero_serie";
    $jsonDados = json_encode($aux_json_dados, true);
}?>

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
<script src="bootstrap/js/bootstrap.js"></script>

<? if (count($dados["relacao_defeitos"]) > 0) { ?>
    <table class="table table-striped table-bordered">
        <thead>
            <tr class="titulo_coluna">
                <th colspan="3"> <?=$dados["referencia_descricao"];?> </th>
            </tr>
            <tr class="titulo_coluna">
                <th><?= $aux_th ?></th>
                <th>Quantidade</th>
                <th>Porcentagem</th>
            </tr>
        </thead>
        <tbody>
            <?
                foreach ($dados["relacao_defeitos"] as $chave => $valor) {
                ?>
                    <tr>
                        <td class="tal"><?= $valor["defeito"]; ?></td>
                        <td class="tac"><?= $valor["qtde_defeito"]; ?></td>
                        <td class="tac"><?= number_format($valor["porcentagem"],2); ?>%</td>
                    </tr>
                <?
                }
            ?>
        </tbody>
    </table>

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        
        var titulo    = "<?=$grafico_titulo;?>";
        var jsonDados =  <?=$jsonDados;?>;
        var grafico   = {'titulo': titulo, 'dados': jsonDados} ;

        function drawChart(grafico) {
            var data = google.visualization.arrayToDataTable(grafico.dados);
            
            chart.draw(data,
                {title: grafico.titulo, is3D: true}
            );
        }

        setTimeout(function() {
            chart = new google.visualization.PieChart(document.getElementById('grafico'));    
            google.charts.setOnLoadCallback(drawChart(grafico));
        }, 3000);
    </script>

    <div class="container">
        <div class='row'>
            <div id="grafico" style="width: 100%;height: 40%;"></div>
        </div>
    </div>
<?
} else {
?>
<div class="alert alert-warning"><h4><?= (!empty($erro_defeito)) ? "$erro_defeito" : "Erro na consulta, verifique novamente o formulário"; ?></h4></div>
<?
} ?>