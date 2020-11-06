<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';

    /* Configurações */

    $data_inicial   = "2014-03-01 00:00:00";
    $data_final     = "2014-03-30 23:59:59";

    //$data_inicial   = "2009-12-19 00:00:00";
    //$data_final     = "2010-01-09 23:59:59";
 
    $sql = "SELECT 
                COUNT(hd_chamado) AS total
             FROM tbl_hd_chamado
             WHERE
                status NOT IN ('Resolvido','Cancelado', 'Novo','Suspenso')
                AND titulo <> 'Atendimento interativo'
                AND fabrica_responsavel = 10;";

    $res = pg_query($con, $sql);
    $total_hd = pg_fetch_result($res,0,'total');

    $sqlErro = "SELECT 
                COUNT(hd_chamado) AS total
             FROM tbl_hd_chamado
             WHERE
                status NOT IN ('Resolvido','Cancelado')
                AND titulo <> 'Atendimento interativo'
		AND tipo_chamado = 5
		AND data > '$data_inicial'
                AND fabrica_responsavel = 10;";

    $resErro = pg_query($con, $sqlErro);
    $total_hd_erro = pg_fetch_result($resErro,0,'total');

     $sqlDepois = "SELECT 
                COUNT(hd_chamado) AS total
             FROM tbl_hd_chamado
             WHERE
                status NOT IN ('Resolvido','Cancelado')
                AND titulo <> 'Atendimento interativo'
		AND data_aprovacao_fila > '$data_inicial'
                AND fabrica_responsavel = 10;";

    $resDepois = pg_query($con, $sqlDepois);
    $total_hd_depois = pg_fetch_result($resDepois,0,'total');
    
	$sql = "SELECT 
                COUNT(hd_chamado) AS total,
                EXTRACT(YEAR FROM data_resolvido) AS ano,
                EXTRACT(MONTH FROM data_resolvido) AS mes,
                EXTRACT(DAY FROM data_resolvido) AS dia,
                EXTRACT(WEEK FROM data_resolvido) AS semana
            FROM 
                tbl_hd_chamado 
            WHERE 
                tipo_chamado IS NOT NULL 
                AND status in ('Resolvido')
                AND titulo <> 'Atendimento interativo'
                AND data_resolvido BETWEEN '{$data_inicial}' AND '{$data_final}'
            GROUP BY ano, mes, dia, semana
            ORDER BY ano, mes, dia, semana ASC;";
    //echo nl2br($sql);
    $res = pg_query($con, $sql);
    
    for($i = 0; $i < pg_num_rows($res); $i++){
        $dados[$i] = pg_fetch_object($res);
    }

    print_r($dados);

    function printTotal($dia = null,$mes = null){
        global $dados;
        $key = null;
        if($mes != null)
            $mes = sprintf("%02d", $mes);

        //if(!empty($dia) AND !empty($mes) AND count($dados) > 0){
            foreach($dados as $i => $valor){
                if($valor->dia == $dia && $valor->mes == $mes){
                    $key = $i;
                    break;
                } 
            }


            if(strlen($key) >= 0){
                $ano    = $dados[$key]->ano;
                $mes    = $dados[$key]->mes;
                $semana = $dados[$key]->semana;
                $dia    = $dados[$key]->dia;
                $total  = $dados[$key]->total;

                echo "<div>{$dia}/{$mes}/{$ano}</div>";
                echo "<div>". sprintf("%02d", $total)."</div>";
            }else{
                echo "0";
            }
       // }else{
            echo "&nbsp;";
       // }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>HDs Batalha</title>
        <META HTTP-EQUIV="Refresh" CONTENT="300;URL=painel_fim_ano.php">
		<script src="http://code.jquery.com/jquery-latest.js"></script>
		<script type="text/javascript" src="../plugins/jquery/slideshow/jquery.cycle.all.js"></script>
		<script type="text/javascript">

		</script>
		<style type="text/css">
			*{
				font-family: Verdana,Arial,sans-serif;
                color: #0E4700;
                font-weight: bolder;
                text-align: center
			}

			body, html{
				padding: 0;
				margin: 0;
				background: url('../imagens/verde_da_floresta_da_camuflagem.jpg');
			}

            table{
                width: 100%;
                background: #0E4700; 
                color: #202B30; 
            }

            .total_hd{
                background: url('../imagens/verde_da_floresta_da_camuflagem.jpg');
            }
            .total_hd1{
                background: #C62D2D; 
            }
            .total_hd1 div, .total_hd2 div{
                font-size: 160px;
                color: #FFF !important;
            }
            .total_hd2{
                background: #1D1791; 
                font-size: 160px;
            }

            .total_hd div{
                font-size: 160px;
                text-align: left !important;
                vertical-align: middle;
                padding: 20px;
                color: #FFF;
            }

            .semana td{
                width: 16.66%;
                background: #F1F7E2;
            }

            .semana td div:first-child{
                background: url('../imagens/verde_da_floresta_da_camuflagem.jpg');
                color: #F1F7E2;
            }

            .semana td div:last-child{
                background: F1F7E2; 
                font-size: 70px;
                padding: 10px;
            }
			
		</style>
	</head>

	<body>
		<div id='content'>
            <table border='0' cellspacing='1' cellpadding='1'>
                <tr>
                    <td class='total_hd' colspan='3' >
                        <div><?php echo $total_hd;?></div>
                    </td>
                    <td class='total_hd1' colspan='1'>
                        <div><?php echo $total_hd_erro; ?></div>
                    </td>
                    <td class='total_hd2' colspan='2'>
                        <div><?php echo $total_hd_depois; ?></div>
                    </td>
                </tr>
                <tr class='semana'>
                    <td><?php printTotal(03,03);?></td>
                    <td><?php printTotal(04,03);?></td>
                    <td><?php printTotal(05,03);?></td>
                    <td><?php printTotal(06,03);?></td>
                    <td><?php printTotal(07,03);?></td>
                </tr>
                <tr class='semana'>
                    <td><?php printTotal(10,03);?></td>
                    <td><?php printTotal(11,03);?></td>
                    <td><?php printTotal(12,03);?></td>
                    <td><?php printTotal(13,03);?></td>
                    <td><?php printTotal(14,03);?></td>
                </tr>
                <tr class='semana'>
                    <td><?php printTotal(17,03);?></td>
                    <td><?php printTotal(18,03);?></td>
                    <td><?php printTotal(19,03);?></td>
                    <td><?php printTotal(20,03);?></td>
                    <td><?php printTotal(21,03);?></td>
                </tr>
                <tr class='semana'>
                    <td><?php printTotal(24,03);?></td>
                    <td><?php printTotal(25,03);?></td>
                    <td><?php printTotal(26,03);?></td>
                    <td><?php printTotal(27,03);?></td>
                    <td><?php printTotal(28,03);?></td>
                </tr>
            </table>
        </div>
	</body>
</html>
<?php
    /*
    echo "<pre>";
        print_r($dados);
    echo "</pre>";
    */
?>
