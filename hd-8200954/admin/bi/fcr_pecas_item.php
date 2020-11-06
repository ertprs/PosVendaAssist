<?
$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../admin/':'../');
define('OS_BACK', ($areaAdminCliente == true)?'':'../');

include_once '../dbconfig.php';
include_once '../includes/dbconnect-inc.php';

if ($areaAdminCliente == true) {
    include 'autentica_admin.php';
    include_once '../funcoes.php';
} else {
    $admin_privilegios = "gerencia";
    include_once '../includes/funcoes.php';
    include '../autentica_admin.php';
    include '../monitora.php';
}

if(isset($peca))
    $listar="ok";

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : LINHA DE PRODUTO";

?>

<style type="text/css">
    body,table{
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: x-small;
        margin: 0px,0px,0px,0px;
        padding:  0px,0px,0px,0px;
    }
    #Menu{border-bottom:#485989 1px solid;}
    #Formulario {
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: x-small;
        font-weight: none;
        border: 1px solid #596D9B;
        color:#000000;
        background-color: #D9E2EF;
    }
    #Formulario tbody th{
        text-align: left;
        font-weight: bold;
    }
    #Formulario tbody td{
        text-align: left;
        font-weight: none;
    }
    #Formulario caption{
        color:#FFFFFF;
        text-align: center;
        font-weight: bold;
        background-image: url("imagens_admin/azul.gif");
    }
    #logo{
        BORDER-RIGHT: 1px ;
        BORDER-TOP: 1px ;
        BORDER-LEFT: 1px ;
        BORDER-BOTTOM: 1px ;
        position: absolute;
        top: 1px;
        right: 1px;
        z-index: 5;
    }
</style>

<?

include ADMCLI_BACK."javascript_pesquisas.php";
include ADMCLI_BACK."javascript_calendario.php";

?>

<link rel="stylesheet" href="<?=ADMCLI_BACK?>js/blue/style.css" type="text/css" id="" media="print, projection, screen" />

<script type="text/javascript" src="<?=ADMCLI_BACK?>js/jquery.tablesorter.pack.js"></script>

<script>
    $(document).ready(function(){
        $.tablesorter.defaults.widgets = ['zebra'];
        $("#relatorio").tablesorter();
    });
    $(document).ready(function(){
        $.tablesorter.defaults.widgets = ['zebra'];
        $("#relatorio2").tablesorter();
    });
    $(document).ready(function(){
        $.tablesorter.defaults.widgets = ['zebra'];
        $("#relatorio3").tablesorter();
    });
    $(function(){
        $.tablesorter.defaults.widgets = ['zebra'];
        $("#relatorio_fornecedor").tablesorter();
    });
    function camada( sId ) {
        var sDiv = document.getElementById( sId );
        if( sDiv.style.display == "none" ) {
        sDiv.style.display = "block";
        sDiv.style.position = "absolute";
        } else {
        sDiv.style.display = "none";
        }
    }
</script>

<?

if ($listar == "ok") {

    // AQUI COMEÇA A GERAR O ARQUIVO EXCEL
    $arquivo_nome     = "item-pecas$login_fabrica.xls";
    $path             = ADMCLI_BACK."xls/";
    $path_tmp         = "/tmp/";

    $arquivo_completo     = $path.$arquivo_nome;
    $arquivo_completo_tmp = $path_tmp.$arquivo_nome;

    $sql2 = "SELECT referencia,descricao
            FROM tbl_peca
            WHERE peca = $peca
            AND   fabrica = $login_fabrica";
    $res2 = pg_exec ($con,$sql2);
    $peca_referencia = pg_result($res2,0,0);
    $peca_descricao  = pg_result($res2,0,1);
    echo "<table width='100%' cellspacing='0' cellpadding='0' border='0' id='Menu'>";
        echo "<tr>";
            echo "<td bgcolor='#F5F9FC'>";
                echo "<h5>Peça: $peca_referencia - $peca_descricao";

                echo "&nbsp;&nbsp;<span style='display:inline-block;position:relative;right:0;text-align:right'>".
                        "<a href='".ADMCLI_BACK."xls/$arquivo_nome' target='_blank'>".
                            "<br>".
                             "<img src='".ADMCLI_BACK."../imagens/excel.gif' border='0' height='24'>".
                            "<font color='#3300CC'>Fazer download do arquivo em  XLS".
                            "</font>".
                    "</a></span>";
                echo "</h5>";
                if(strlen($data_inicial)>0)
                    echo "Resultado de pesquisa entre os dias <b>$data_inicial</b> e <b>$data_final</b>";
                echo "$mostraMsgLinha $mostraMsgEstado $mostraMsgPais";
                if ($login_fabrica == 50) { // HD 41116
                    echo "<br><br><br>";
                }
            echo "</td>";
        echo "</tr>";
    echo "</table>";
    if ($login_fabrica == 50) { // HD 41116
        echo "<span id='logo'>";
            echo "<img src='../imagens_admin/colormaq_.gif' border='0' width='160' height='55'>";
        echo "</span>";
    }
    if(strlen($codigo_posto)>0){
        $sql = "SELECT  posto
                FROM    tbl_posto_fabrica
                WHERE   fabrica      = $login_fabrica
                AND     codigo_posto = '$codigo_posto';";
        $res = pg_exec ($con,$sql);
        if (pg_numrows($res) > 0)
            $posto = trim(pg_result($res,0,posto));
    }

    switch($estado){
        case 'Norte':
            $consulta_estado = "AC','AP','AM','PA','RO','RR','TO";
        break;

        case 'Nordeste':
            $consulta_estado = "AL','BA','CE','MA','PB','PE','PI','RN','SE";
        break;

        case 'Centro_oeste':
            $consulta_estado = "DF','GO','MT','MS";
        break;

        case 'Sudeste':
            $consulta_estado = "ES','MG','RJ','SP";
        break;

        case 'Sul':
            $consulta_estado = "PR','RS','SC";
        break;

        default: $consulta_estado = $estado;
    }

    if (strlen ($linha)    > 0)
        $cond_1 = " AND   BI.linha   = $linha ";
    if (strlen ($estado)   > 0)
        $cond_2 = " AND   BI.estado  IN ('$consulta_estado') ";
    if (strlen ($posto)    > 0)
        $cond_3 = " AND   BI.posto   = $posto ";
    if (strlen ($posto) > 0 AND !empty($exceto_posto)) {
        $cond_3 = " AND   NOT (BI.posto   = $posto) ";
    }
    if (strlen ($produto)  > 0)
        $cond_4 = " AND   BI.produto in ($produto) ";
    if (strlen ($pais)     > 0)
        $cond_6 = " AND   BI.pais    = '$pais' ";
    if (strlen ($marca)    > 0)
        $cond_7 = " AND   BI.marca   = $marca ";
    if (strlen ($origem)   > 0)
        $cond_8 = " AND   BI.origem  = '$origem' ";

    if($tipo_data == 'data_abertura'){
        $tipo_data = 'data_abertura';
    }
    if($tipo_data == 'data_digitacao'){
        $tipo_data = 'data_digitacao';
    }

    if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0){
        $cond_9 = "AND   BI.$tipo_data BETWEEN '$aux_data_inicial' AND '$aux_data_final'";
    }


    if (strlen($tipo_data) == 0 ){
        if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0){
            $cond_9 = "AND   BI.$tipo_data BETWEEN '$aux_data_inicial' AND '$aux_data_final'";
        }
    }

    if($login_fabrica == 20 and $pais !='BR'){
        $produto_descricao   ="tbl_produto_idioma.descricao ";
        $join_produto_idioma =" LEFT JOIN tbl_produto_idioma ON tbl_produto.produto = tbl_produto_idioma.produto and tbl_produto_idioma.idioma = 'ES' ";
    }else{
        $produto_descricao   ="tbl_produto.descricao ";
        $join_produto_idioma =" ";
    }

    if($login_fabrica == 24){

        $join_bi = " JOIN bi_os AS B ON B.os = BI.os ";
        $campo = " ,B.mao_de_obra";
        $campo_mao_obra = " , mao_de_obra";
        if(strlen($tipo_os) > 0 AND $tipo_os != 'todos'){
            $cond_10 = " AND B.consumidor_revenda = '$tipo_os' ";
        }
        if(strlen($nome_revenda) > 0 ){
                $cond_10 .= " AND B.revenda_nome LIKE '$nome_revenda%' ";
        }

        if(strlen($familia) > 0){
            $cond_11 = " AND BI.familia = $familia ";
        }
    }

	if($login_fabrica == 158){
		if ($areaAdminCliente == true ) {
			$join_tipo_atendimento = " JOIN tbl_os ON tbl_os.os = BI.os JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento 
				AND tbl_tipo_atendimento.fabrica = {$login_fabrica} 
				AND   tbl_os.cliente_admin = {$login_cliente_admin} ";
		}else{
			$join_tipo_atendimento = " JOIN tbl_os ON tbl_os.os = BI.os JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento 
				AND tbl_tipo_atendimento.fabrica = {$login_fabrica} ";

		}
	}


   if($login_fabrica == 158 AND strlen($_GET['tipo_atendimento']) > 0){
        	if($_GET['tipo_atendimento'] == 'fora_garantia'){
    		$join_tipo_atendimento .= " AND tbl_tipo_atendimento.fora_garantia IS TRUE ";
    	}else{
    		$join_tipo_atendimento .= " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
    	}
    }

	if($nao_posto_interno == 't'){
		$join_posto_interno = " JOIN tbl_posto_fabrica ON BI.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
					JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica} AND tbl_tipo_posto.posto_interno IS NOT TRUE ";
	}


    /*
        FABRICA 148 => hd_chamado=3049906
        Marisa vai criar o campo cancelada na tabela bi_os
        assim que criado o campo remover o JOIN
    */
    if(in_array($login_fabrica, array(74,148))){ //hd_chamado=3049906
        $join_cancelada = " JOIN tbl_os ON tbl_os.os = BI.os";
        $cond_cancelada = " AND tbl_os.cancelada IS NOT TRUE ";
    }

	if($BiMultiDefeitoOs == 't') {
		$join_dc = " JOIN tbl_os_defeito_reclamado_constatado ON BI.os = tbl_os_defeito_reclamado_constatado.os
			JOIN tbl_defeito_constatado DC ON DC.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado ";
	}else{
		$join_dc = " LEFT JOIN tbl_defeito_constatado DC ON DC.defeito_constatado = BI.defeito_constatado ";
	}
    //Relatório por Defeito Reclamado
    $sql = "SELECT  count(distinct BI.os)       AS os,
                    SUM(BI.custo_peca * BI.qtde)     AS custo_peca  ,
                    DC.codigo                        AS dc_codigo   ,
                    DC.descricao                     AS dc_descricao,
                    BI.troca_de_peca                 AS troca_de_peca,
                    to_char(BI.$tipo_data,'mm')      As mes
                    $campo
                    into tmp_defeito_reclamado_$login_fabrica
        FROM      bi_os_item             BI
        JOIN      tbl_peca               PE ON PE.peca               = BI.peca
        $join_bi
		$join_tipo_atendimento
		$join_cancelada
		$join_dc
		 $join_posto_interno
        WHERE BI.fabrica = $login_fabrica
        AND   BI.peca    = $peca
         $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_cancelada
        GROUP BY    dc_codigo   ,
                    dc_descricao,
                    troca_de_peca,
                    mes
                    $campo;

        SELECT SUM(os) as total_os, mes as total_mes
            FROM tmp_defeito_reclamado_$login_fabrica
            GROUP BY mes
            ORDER BY mes";

    /* Alterei para pegar o defeito da Peça - HD 43710 */
    if ($login_fabrica == 50){
            $qtde_pecas = "
                SUM(CASE WHEN (SR.gera_pedido is true)  THEN BI.qtde ELSE 0 END) as qtde_pecas,
            ";

            $join_sr = " JOIN      tbl_servico_realizado  SR on SR.servico_realizado = BI.servico_realizado ";

        }
    if ($login_fabrica == 50 OR $login_fabrica == 5){
        $sql = "SELECT  count(distinct BI.os)              AS os          ,
                        SUM(BI.custo_peca * BI.qtde)       AS custo_peca  ,
                        $qtde_pecas
                        DE.codigo_defeito                  AS dc_codigo   ,
                        DE.descricao                       AS dc_descricao,
                        BI.troca_de_peca                   ,
                        to_char(BI.$tipo_data,'mm')        As mes
                        $campo
                into tmp_defeito_reclamado_$login_fabrica
                FROM      bi_os_item             BI
                JOIN      tbl_peca               PE ON PE.peca               = BI.peca
                $join_bi
                LEFT JOIN tbl_defeito            DE ON DE.defeito            = BI.defeito
                $join_sr
                WHERE BI.fabrica = $login_fabrica
                AND   BI.peca    = $peca
                 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9
                GROUP BY    dc_codigo   ,
                            dc_descricao,
                            BI.troca_de_peca,
                            mes
                            $campo ;

                SELECT SUM(os) as total_os, mes as total_mes
                    FROM tmp_defeito_reclamado_$login_fabrica
                    GROUP BY mes
                    ORDER BY mes";
    }
    $meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

    /*Alterado para listar qtde de defeitos por mes - HD 110479*/
    $res = pg_exec ($con,$sql);

    if ($login_fabrica==11){
        if (pg_numrows($res) > 0) {
            for ($i=0; $i<pg_numrows($res); $i++){
                $os              = trim(pg_result($res,$i,total_os));
                $mes             = (int)trim(pg_result($res,$i,total_mes));

                $meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

                $mes = $meses[$mes];

                if ($mes_antigo==""){
                    $cabecalho = "<DIV ID=\"total\" STYLE='POSITION: static; BORDER: 10px solid #FFFFFF; BACKGROUND: #FFFFFF; margin:0px;'><table style='font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 11px;' cellspacing='3'  cellpadding='7'><tr align='center'><td width='100' style='background-color:#D9E2EF;'><b>MÊS</b></td><td width='60' style='background-color:#D9E2EF;'><b>$mes</b></td>";
                    $linha = "<tr><td align='center' style='background-color:#D9E2EF;'><b>TOTAL</b></td><td align='center'>$os</td>";
                }else{
                        $cabecalho = $cabecalho."<td width=60 style='background-color:#D9E2EF;'><b>$mes</b></td>";
                        $linha = $linha."<td align='center'>$os</td>";
                }
                $mes_antigo=$mes;
            }
            $div=$cabecalho."</tr>".$linha."</tr></table></div>";
        }
    }

    if (pg_numrows($res) > 0) {
        $total = 0;
        if ($login_fabrica == 50){
            $qtde_pecas = " SUM(qtde_pecas) as qtde_pecas , ";
        }
            $sql2 = "select     sum(os) as soma_os,
                                sum(custo_peca) as soma_custo_peca,
                                $qtde_pecas
                                dc_codigo                    AS dc_codigo   ,
                                dc_descricao                 AS dc_descricao
                                $campo_mao_obra
                        from tmp_defeito_reclamado_$login_fabrica
                        GROUP BY   dc_codigo   ,
                                dc_descricao
                                $campo_mao_obra
                        order by dc_descricao";
        $res2 = pg_exec ($con,$sql2);
        // AQUI COMEÇA A GERAR O ARQUIVO EXCEL
        $arquivo_nome     = "item-pecas$login_fabrica.xls";
        $path             = ADMCLI_BACK."xls/";
        $path_tmp         = "/tmp/";

        $arquivo_completo     = $path.$arquivo_nome;
        $arquivo_completo_tmp = $path_tmp.$arquivo_nome;

        $fp = fopen ($arquivo_completo,"w+");

        $conteudo .= "<center>";
            $conteudo .= "<div style='width:98%;'>";
                $conteudo .= "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'  style=' border:#485989 1px solid; background-color: #e6eef7' name='relatorio' id='relatorio' class='tablesorter'>";

                    $conteudo .= "<thead>";

                        $conteudo .= "<tr>";

                            if ($login_fabrica == 50 or $login_fabrica == 5){
                                $conteudo .= "<td height='15'><b>Defeito</b></TD>";
                                if ($login_fabrica == 50){
                                    $conteudo .= "<td height='15' width='100'><b>Qtde. Peça Trocada</b></TD>";
                                }
                            }else{
                                $conteudo .= "<td height='15'><b>Defeito Constatado</b></td>";
                            }
                            $conteudo .= "<td width='100' height='15'><b>Qtde OS</b></td>";
                            $conteudo .= "<td width='100' height='15'><b>Porcentagem OS</b></td>";
                            if ($areaAdminCliente != true) {
                                $conteudo .= "<td width='50' height='15'><b>Custo</b></td>";
                            }

                        $conteudo .= "</tr>";

                    $conteudo .= "</thead>";

                    $conteudo .= "<tbody>";

                        for ($i=0; $i<pg_numrows($res2); $i++){
                            $os           = trim(pg_result($res2,$i,soma_os));
                            $total_de_os  = $total_de_os + $os;
                        }
                        $total_pecas  ="";
                        for ($i=0; $i<pg_numrows($res2); $i++){
                            $de_codigo      = trim(pg_result($res2,$i,dc_codigo));
                            $de_descricao   = trim(pg_result($res2,$i,dc_descricao));
                            $os             = trim(pg_result($res2,$i,soma_os));
                            if ($login_fabrica == 50){
                                $qtde_pecas = trim(pg_result($res2,$i,'qtde_pecas'));
                                if ($qtde_pecas == 0) continue;
                            }
                            if($login_fabrica == 24){
                                $custo_peca = trim(pg_result($res2,$i,mao_de_obra))." ";
                                $custo_peca = $custo_peca * $os;
                            }else{
                                $custo_peca     = trim(pg_result($res2,$i,soma_custo_peca));
                            }
                            $total_pecas  = $total_pecas + $custo_peca;

                            $custo_peca   = number_format($custo_peca,2,",",".");

                            $conteudo .= "<tr>";
                            $conteudo .= "<td align='left' nowrap>";
                            $conteudo .= "$de_codigo - $de_descricao</td>";
                            if ($login_fabrica == 50){
                                $conteudo .= "<td align='center' nowrap>$qtde_pecas</td>";
                            }
                            $conteudo .= "<td align='center' nowrap>$os</td>";
                            // Aqui efetua a regra para verificar a porcentagem de OS
                            $porcentagem_os = $os*100/$total_de_os;
                            $porcentagem_os = number_format($porcentagem_os,2,",",".");
                            $conteudo .= "<td align='center' nowrap>$porcentagem_os</td>";
                            if ($areaAdminCliente != true) {
                                $conteudo .= "<td align='right' nowrap>$custo_peca</td>";
                            }
                            $conteudo .= "</tr>";
                        }
                        $total_pecas = number_format($total_pecas,2,",",".");
                        $conteudo .= "</tbody>";
                        if ($areaAdminCliente != true) {
                            $conteudo .= "<tr class='table_line'>";
                            // Aqui é feita a regra de tres para encontrar o percentual de OS HD 201880
                            // PORCENTAGEM = Numero de OS Total / Numero de OS mostrada
                            // Aqui é a primeira soma
                            $colspan_colormaq = ($login_fabrica == 50) ? "4" : "3";
                            $conteudo .= "<td colspan='$colspan_colormaq'><font size='2'><b><CENTER>TOTAL</b></td>";
                            $conteudo .= "<td><font size='2' color='009900'><b>$total_pecas</b></td>";
                            $conteudo .= "</tr>";
                        }
                        if ($login_fabrica==11){
                            $conteudo .= "<tr class='table_line'>";
                            $conteudo .= "<td colspan='3'>$div</td>";
                            $conteudo .= "</tr>";
                        }
                        $conteudo .= " </table>";
                        $conteudo .= "</div>";
                        echo $conteudo;
                        echo "<a href='javascript:history.back()'>[Voltar]</a>";
                    }else{echo"vazio";}

    if($login_fabrica == 120){
        $sql = "SELECT  count(distinct BI.os)       AS os,
                        count(BI.peca) AS peca,
                    SUM(BI.custo_peca * BI.qtde)     AS custo_peca  ,
                    DC.codigo_defeito                AS de_codigo   ,
                    DC.descricao                     AS de_descricao,
                    BI.troca_de_peca                 AS troca_de_peca,
                    to_char(BI.$tipo_data,'mm')      As mes
                    $campo
                    into temp tmp_defeito_$login_fabrica
        FROM      bi_os_item             BI
        JOIN      tbl_peca               PE ON PE.peca               = BI.peca
        $join_bi
        LEFT JOIN tbl_defeito DC ON DC.defeito = BI.defeito
        WHERE BI.fabrica = $login_fabrica
        AND   BI.peca    = $peca
         $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9
        GROUP BY    de_codigo   ,
                    de_descricao,
                    troca_de_peca,
                    mes
                    $campo;

        SELECT SUM(os) as total_os, SUM(peca) AS pecas, mes as total_mes
            FROM tmp_defeito_$login_fabrica
            GROUP BY mes
            ORDER BY mes";
        $res = pg_query($con,$sql);

        if(pg_num_rows($res) > 0){
            $sql = "select sum(os) as soma_os,
                    sum(peca) as soma_peca,
                    sum(custo_peca) as soma_custo_peca,
                    de_codigo                    AS de_codigo   ,
                    de_descricao                 AS de_descricao
                    from tmp_defeito_$login_fabrica
                    GROUP BY   de_codigo   ,
                    de_descricao
                    order by de_descricao";
            $res2 = pg_query($con,$sql);

            $total_de_os = 0;
            $total_de_peca = 0;

            for ($i=0; $i<pg_numrows($res2); $i++){
                $os             = trim(pg_result($res2,$i,'soma_os'));
                $pecas          = trim(pg_result($res2,$i,'soma_peca'));
                $total_de_os    = $total_de_os + $os;
                $total_de_peca  = $total_de_peca + $pecas;
            }

            $conteudo3 .= "<div style='width:98%;'><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'  style=' border:#485989 1px solid; background-color: #e6eef7' name='relatorio2' id='relatorio2' class='tablesorter'>";
            $conteudo3 .= "<thead>";
            $conteudo3 .= "<TR>";
            $conteudo3 .= "<TD height='15'><b>Defeito da peça</b></TD>";
            $conteudo3 .= "<TD width='100' height='15'><b>Qtde Peça</b></TD>";
            $conteudo3 .= "<TD width='100' height='15'><b>Qtde OS</b></TD>";
            $conteudo3 .= "<TD width='50' height='15'><b>Porcentagem de Peça</b></TD>";
            $conteudo3 .= "<TD width='50' height='15'><b>Porcentagem de OS</b></TD>";
            if ($areaAdminCliente != true) {
                $conteudo3 .= "<TD width='50' height='15'><b>Custo Peças</b></TD>";
            }
            $conteudo3 .= "</TR>";
            $conteudo3 .= "</thead>";
            $conteudo3 .= "<tbody>";
            $total_pecas = 0;
            for ($i=0; $i<pg_numrows($res2); $i++){
                if (!empty(pg_result($res2, $i, 'de_codigo'))) {
                    $de_codigo    = trim(pg_result($res2,$i,'de_codigo'));
                    $de_descricao = trim(pg_result($res2,$i,'de_descricao'));
                } else {
                    $de_codigo = "00";
                    $de_descricao = "Não especificado";
                }
                $os           = trim(pg_result($res2,$i,'soma_os'));
                $pecas        = trim(pg_result($res2,$i,'soma_peca'));
                $custo_peca   = trim(pg_result($res2,$i,'soma_custo_peca'));
                $total_pecas  = $total_pecas + $custo_peca;
                $custo_peca   = number_format($custo_peca,2,",",".");

                $conteudo3 .= "<tr>";
                $conteudo3 .= "<td align='left' nowrap>";
                $conteudo3 .= "$de_codigo - $de_descricao</td>";
                $conteudo3 .= "<td align='center' nowrap>$pecas</td>";
                $conteudo3 .= "<td align='center' nowrap>$os</td>";
                $porcentagem_peca = $pecas*100/$total_de_peca;
                $porcentagem_peca = number_format($porcentagem_peca,2,",",".");
                $conteudo3 .= "<td align='center' nowrap>$porcentagem_peca</td>";
                $porcentagem_os = $os*100/$total_de_os;
                $porcentagem_os = number_format($porcentagem_os,2,",",".");
                $conteudo3 .= "<td align='center' nowrap>$porcentagem_os</td>";
                if ($areaAdminCliente != true) {
                    $conteudo3 .= "<td align='right' nowrap>$custo_peca</td>";
                }
                $conteudo3 .= "</tr>";
            }
            $total_pecas = number_format($total_pecas,2,",",".");
            $conteudo3 .= "</tbody>";
            if ($areaAdminCliente != true) {
                $conteudo3 .= "<tr class='table_line'>";
                $conteudo3 .= "<td colspan='5'><font size='2'><b><CENTER>TOTAL</b></td>";
                $conteudo3 .= "<td><font size='2' color='009900'><b>$total_pecas</b></td>";
                $conteudo3 .= "</tr>";
            }
            $conteudo3 .= "</table>";
            $conteudo3 .= "</div>";

        echo $conteudo3;
        echo "<a href='javascript:history.back()'>[Voltar]</a>";
        }else{
            echo "vazio";
        }
    }

   /**
    * - TABELA DE FORNECEDORES
    */
    if($login_fabrica == 91){
        $res = pg_query($con,"DROP TABLE IF EXISTS tmp_fornecedor_$login_fabrica;");

        $sqlF = "
            SELECT  tbl_fornecedor.nome                                                         ,
                    COUNT(DISTINCT BI.os)                               AS quantidade_fornecedor
       --INTO TEMP    tmp_fornecedor_$login_fabrica
            FROM    bi_os_item BI
            JOIN    tbl_os_item         USING (os_item)
       LEFT JOIN    tbl_fornecedor      ON  tbl_fornecedor.fornecedor       = tbl_os_item.fornecedor
       LEFT JOIN    tbl_fornecedor_peca ON  tbl_fornecedor_peca.fornecedor  = tbl_fornecedor.fornecedor
       LEFT JOIN    tbl_peca            ON  tbl_peca.peca                   = tbl_fornecedor_peca.peca
            WHERE   BI.fabrica  = $login_fabrica
            AND     BI.peca     = $peca
                    $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_11
	GROUP BY	tbl_fornecedor.nome
      ;

    ";
//              SELECT * FROM tmp_fornecedor_$login_fabrica;
        $resF = pg_query($con,$sqlF);

        if(pg_numrows($resF) > 0){
?>
        <div>
            <table border='0' cellspacing='2' cellpadding='2' style="width:600px;border:#485989 1px solid; background-color: #e6eef7" name='relatorio_fornecedor' id='relatorio_fornecedor' class='tablesorter'">
                <thead>
                    <tr>
                        <th>Fornecedor</th>
                        <th>Qtde</th>
                    </tr>
                </thead>
                <tbody>
<?
                    $total_fornecedor = 0;
                    for($f = 0; $f < pg_numrows($resF); $f++){
                        $fornecedor_nome        = pg_fetch_result($resF,$f,nome);
                        $quantidade_fornecedor  = pg_fetch_result($resF,$f,quantidade_fornecedor);
                        $total_fornecedor += (int)$quantidade_fornecedor;
?>
                    <tr>
                        <td align="left"><?=$fornecedor_nome?></td>
                        <td align="center"><?=$quantidade_fornecedor?></td>
                    </tr>
<?
                    }
?>
                </tbody>
                <?php
                if ($areaAdminCliente != true) { ?>
                    <tfoot>
                        <tr class='table_line'>
                            <td>TOTAL</td>
                            <td style=" color:#090;text-align:center;"><?=$total_fornecedor?></td>
                        </tr>
                    </tfoot>
                <?php
                } ?>
            </table>
        </div>
<?
        }
    }

   

    $sql ="drop table tmp_defeito_reclamado_$login_fabrica;";
    $res = pg_exec($con, $sql);

    //Relatório por Defeito Reclamado
    $sql = "SELECT  count( DISTINCT BI.os)             AS os          ,
                    SUM(BI.custo_peca * BI.qtde)    AS custo_peca  ,
                    PR.referencia                                  ,
                    PR.descricao                                   ,
                    to_char(BI.$tipo_data,'mm') as mes
                    $campo
            into tmp_defeito_reclamado_$login_fabrica
            FROM      bi_os_item             BI
            JOIN      tbl_peca               PE ON PE.peca    = BI.peca
            JOIN      tbl_produto            PR ON PR.produto = BI.produto
            $join_bi
        $join_cancelada
            WHERE BI.fabrica = $login_fabrica
            AND   BI.peca    = $peca
             $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_11 $cond_cancelada
            GROUP BY    PR.referencia   ,
                        PR.descricao,
                        mes
                        $campo
            ORDER BY PR.referencia   ,
                        PR.descricao;

            SELECT sum(os) as os_total,mes
            FROM tmp_defeito_reclamado_$login_fabrica group by mes order by mes";

    //echo nl2br($sql);
    $res = pg_exec ($con,$sql);

    if (pg_numrows($res) > 0) {
        $total = 0;

        /*Alterado para listar qtde de produtos com defeito por mes - HD 110479*/
        if ($login_fabrica==11){
            for ($i=0; $i<pg_numrows($res); $i++){
                $os             = trim(pg_result($res,$i,os_total));
                $mes             = (int)trim(pg_result($res,$i,mes));

                $meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
                $mes = $meses[$mes];
                                if ($mes_antigo==""){
                    $cabecalho = "<DIV ID=\"total\" STYLE='POSITION: absolute; BORDER: 10px solid #FFFFFF; BACKGROUND: #FFFFFF; margin:0px;'><table style='font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 11px;' cellspacing='3'  cellpadding='7'><tr align='center'><td width='100' style='background-color:#D9E2EF;'><b>MÊS</b></td><td width='60' style='background-color:#D9E2EF;'><b>$mes</b></td>";
                    $linha = "<tr><td align='center' style='background-color:#D9E2EF;'><b>TOTAL</b></td><td align='center'>$os</td>";
                }else{
                        $cabecalho = $cabecalho."<td width=60 style='background-color:#D9E2EF;'><b>$mes</b></td>";
                        $linha = $linha."<td align='center'>$os</td>";
                }
                $mes_antigo=$mes;
            }

            $total= $cabecalho."</tr></thead>".$linha."</tr></table></div>";
        }
        $sql2 = "SELECT SUM(os)                 AS soma_os,
                        SUM(custo_peca)         AS soma_custo_peca,
                        referencia              AS referencia,
                        descricao               AS descricao
                        $campo_mao_obra
                FROM tmp_defeito_reclamado_$login_fabrica
                GROUP BY    referencia,
                                descricao
                                $campo_mao_obra
                ORDER BY    referencia,
                                descricao";
        $res2 = pg_exec ($con,$sql2);
        $conteudo1 .= "<center><div style='width:98%;'><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'  style=' border:#485989 1px solid; background-color: #e6eef7' name='relatorio2' id='relatorio2' class='tablesorter'>";
        $conteudo1 .= "<thead>";
        $conteudo1 .= "<TR>";
        $conteudo1 .= "<TD height='15'><b>Produto</b></TD>";
        $conteudo1 .= "<TD width='100' height='15'><b>Qtde OS</b></TD>";
        $conteudo1 .= "<TD width='50' height='15'><b>Porcentagem de OS</b></TD>";
        if ($areaAdminCliente != true) {
            $conteudo1 .= "<TD width='50' height='15'><b>Custo</b></TD>";
        }
        $conteudo1 .= "</TR>";
        $conteudo1 .= "</thead>";
        $conteudo1 .= "<tbody>";

        $porcentagem_os = "";
        for ($i=0; $i<pg_numrows($res2); $i++){
            $referencia     = trim(pg_result($res2,$i,referencia));
            $descricao      = trim(pg_result($res2,$i,descricao));
            $os             = trim(pg_result($res2,$i,soma_os));
            if($login_fabrica == 24){
                $custo_peca = trim(pg_result($res2,$i,mao_de_obra));
                $custo_peca = $custo_peca * $os;
            }else{
                $custo_peca     = trim(pg_result($res2,$i,soma_custo_peca));
            }
            // Aqui a variável estava acumulando e por isso a soma estava errada HD 201880
            //$total_pecas += $custo_peca;
            $total_pecas2  = $total_pecas2 + $custo_peca; // Nova variável HD 201880
            $custo_peca   = number_format($custo_peca,2,",",".");
            $div_e='div'.str_replace('.', '',str_replace(' ', '', $referencia));
            $div_ex=$$div_e;
            $conteudo1 .= "<TR>";
            $conteudo1 .= "<TD align='left' nowrap>";
            $conteudo1 .="$referencia - $descricao</TD>";
            $conteudo1 .= "<TD align='center' nowrap>$os</TD>";

            // Aqui efetua a regra para verificar a porcentagem de OS
            $porcentagem_os = $os*100/$total_de_os;
            $porcentagem_os = number_format($porcentagem_os,2,",",".");
            $conteudo1 .= "<TD align='center' nowrap>$porcentagem_os</TD>";

            if ($areaAdminCliente != true) {
                $conteudo1 .= "<TD align='right' nowrap>$custo_peca</TD>";
            }
            $conteudo1 .= "</TR>";

        }
        $total_pecas2 = number_format($total_pecas2,2,",",".");
        $conteudo1 .= "</tbody>";
        // Aqui é a segunda soma
        if ($areaAdminCliente != true) {
            $conteudo1 .= "<tr class='table_line'><td colspan='3'><font size='2'><b><CENTER>TOTAL</b></td>";
            $conteudo1 .= "<td><font size='2' color='009900'><b>$total_pecas2</b></td>";
            $conteudo1 .= "</tr>";
        }
        if ($login_fabrica==11){
            $conteudo1 .= "<tr class='table_line'>";
            $conteudo1 .= "<td colspan='3'>$div</td>";
            $conteudo1 .= "</tr>";
        }
        $conteudo1 .= " </TABLE></div>";
        echo $conteudo1;
        echo "<a href='javascript:history.back()'>[Voltar]</a>";
    }


    $sql ="drop table tmp_defeito_reclamado_$login_fabrica;";
    $res = pg_exec($con, $sql);

    if(strlen($peca)>0){
	if($login_fabrica == 42){
		$join_bi = " JOIN bi_os AS B ON B.os = BI.os ";
		$campo = ",B.serie";
	}

	if($nao_posto_interno == 't'){
		$join_posto_interno = " JOIN tbl_tipo_posto ON PF.tipo_posto = tbl_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica} AND tbl_tipo_posto.posto_interno IS NOT TRUE ";
	}

        $sql = "SELECT  PE.peca                                ,
                        PE.ativo                               ,
                        PE.referencia                          ,
                        PE.descricao                           ,
                        PF.codigo_posto             AS posto_codigo ,
                        PO.nome                     AS posto_nome   ,
                        BI.os                                  ,
                        (BI.custo_peca * BI.qtde)   AS custo_peca,
                        BI.sua_os                              ,
                        DR.codigo                   AS dr_codigo    ,
                        DR.descricao                AS dr_descricao ,
                        DC.codigo                   AS dc_codigo    ,
                        DC.descricao                AS dc_descricao ,
                        DC.defeito_constatado       AS dc_id ,
                        DE.codigo_defeito           AS de_codigo    ,
                        BI.troca_de_peca            AS troca_de_peca,
                        DE.descricao                AS de_descricao ,
                        SR.descricao                AS sr_descricao
                        $campo
            FROM      bi_os_item             BI
            $join_bi
            JOIN      tbl_peca               PE ON PE.peca               = BI.peca
            JOIN      tbl_posto              PO ON PO.posto              = BI.posto
            JOIN      tbl_posto_fabrica      PF ON PF.posto              = BI.posto
	    $join_posto_interno
            $join_cancelada
            LEFT JOIN tbl_defeito_reclamado  DR ON DR.defeito_reclamado  = BI.defeito_reclamado
            LEFT JOIN tbl_defeito_constatado DC ON DC.defeito_constatado = BI.defeito_constatado
            LEFT JOIN tbl_servico_realizado  SR ON SR.servico_realizado  = BI.servico_realizado
            LEFT JOIN tbl_defeito            DE ON DE.defeito            = BI.defeito
	    $join_tipo_atendimento
            WHERE BI.fabrica = $login_fabrica
            AND   PF.fabrica = $login_fabrica
            AND   BI.peca    = $peca
             $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11 $cond_cancelada";
        $res = pg_exec ($con,$sql);

        if (pg_numrows($res) > 0) {
            $total = 0;

            $conteudo2 .= "<center><div style='width:98%;'><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'  style=' border:#485989 1px solid; background-color: #e6eef7' name='relatorio' id='relatorio' class='tablesorter'>";
            $conteudo2 .= "<thead>";
            $conteudo2 .= "<TR>";
            $conteudo2 .= "<TD height='15'><b>OS</b></TD>";
            $conteudo2 .= "<TD height='15'><b>Cód. Posto</b></TD>";
	    $conteudo2 .= "<TD height='15'><b>Posto</b></TD>";
	    if($login_fabrica == 42) $conteudo2.= "<TD height='15'>Série</TD>";
            $conteudo2 .= "<TD height='15'><b>Defeito Reclamado</b></TD>";
            $conteudo2 .= "<TD height='15'><b>Defeito Constatado</b></TD>";
            if ($login_fabrica==5 or $login_fabrica==50 or $login_fabrica==51){#HD 43647, 216673
                $conteudo2 .= "<TD height='15'><b>Defeito da Peça</b></TD>";
            }
            $conteudo2 .= "<TD width='100' height='15'><b>Serviço Realizado</b></TD>";
            if ($areaAdminCliente != true) {
                $conteudo2 .= "<TD width='50' height='15'><b>Custo</b></TD>";
            }
            $conteudo2 .= "</TR>";
            $conteudo2 .= "</thead>";
            $conteudo2 .= "<tbody>";

            for ($i=0; $i<pg_numrows($res); $i++){
                $posto_codigo   = trim(pg_result($res,$i,posto_codigo));
                $posto_nome     = trim(pg_result($res,$i,posto_nome));
                $dr_codigo      = trim(pg_result($res,$i,dr_codigo));
                $dr_descricao   = trim(pg_result($res,$i,dr_descricao));
                $dc_codigo      = trim(pg_result($res,$i,dc_codigo));
                $dc_descricao   = trim(pg_result($res,$i,dc_descricao));
                $dc_id          = trim(pg_result($res,$i,dc_id));
                $de_codigo      = trim(pg_result($res,$i,de_codigo));
                $de_descricao   = trim(pg_result($res,$i,de_descricao));
                $sr_descricao   = trim(pg_result($res,$i,sr_descricao));
                if($login_fabrica == 24){
                    $custo_peca     = trim(pg_result($res,$i,mao_de_obra));
                }else{

                    if ($login_fabrica==50){
                        if ($sr_descricao  == "Troca de peça") {
                            $custo_peca     = trim(pg_result($res,$i,custo_peca));
                        }else{
                            $custo_peca     = 0;
                        }
                    }else{
                        $custo_peca     = trim(pg_result($res,$i,custo_peca));
                    }

		}

		if($login_fabrica == 42){
			$serie = trim(pg_result($res,$i,serie));
		}
                $os             = trim(pg_result($res,$i,os));
                $sua_os         = trim(pg_result($res,$i,sua_os));


                // Aqui a variável estava acumulando e por isso a soma estava errada HD 201880
                //$total_pecas += $custo_peca;
                if ($sr_descricao  == "Troca de peça") { // Condição para ser somado somente troca de peças HD 201880
                    $total_pecas3  = $total_pecas3 + $custo_peca;  // Nova variável HD 201880
                }

				if($dc_id == '0' and $BiMultiDefeitoOs =='t') {
					$sql_dc = "SELECT codigo, descricao FROM tbl_os_defeito_reclamado_constatado JOIN tbl_defeito_constatado using(defeito_constatado)
								WHERE os = $os order by defeito_constatado_reclamado limit 1 ";
					$res_dc = pg_query($con,$sql_dc);
					if(pg_num_rows($res_dc) > 0) {
						$dc_codigo = pg_fetch_result($res_dc, 0, 'codigo');
						$dc_descricao = pg_fetch_result($res_dc, 0, 'descricao');
					}
				}

                $custo_peca   = number_format($custo_peca,2,",",".");

                $conteudo2 .= "<TR>";
                $conteudo2 .= "<TD align='left' nowrap><a href='".OS_BACK."os_press.php?os=$os' target='_blanck'>$sua_os</a></td>";
                $conteudo2 .= "<TD align='left' nowrap>$posto_codigo</TD>";
		$conteudo2 .= "<TD align='left' nowrap>$posto_nome</TD>";

		if($login_fabrica == 42){
			$conteudo2 .= "<TD align='left' nowrap>$serie</TD>";
		}

                if($login_fabrica == 5){
                    $sqlx="SELECT defeito_reclamado_descricao from tbl_os where os=$os and fabrica= $login_fabrica";
                    $resx = @pg_exec($con,$sqlx);
                    $dr_descricao = @pg_result($resx,0,defeito_reclamado_descricao);
                }

                $conteudo2 .= "<TD align='left' nowrap>$dr_codigo - $dr_descricao</TD>";
		$conteudo2 .= "<TD align='left' nowrap>$dc_codigo - $dc_descricao</TD>";

                if ($login_fabrica==50 or $login_fabrica==5){ #HD 43647
                    $conteudo2 .= "<TD align='left' nowrap>$de_codigo - $de_descricao</TD>";
                }
                if ($login_fabrica==51){    #HD 216673
                    $conteudo2 .= "<TD align='left' nowrap>$de_descricao</TD>";
                }
                $conteudo2 .= "<TD align='center' nowrap>$sr_descricao</TD>";
                if ($areaAdminCliente != true) {                    
                    if ($login_fabrica==50){
                        if ($sr_descricao  == "Troca de peça") {                        
                            $conteudo2 .= "<TD align='right' nowrap>$custo_peca</TD>";
                        }else{
                            echo "<td>&nbsp;&nbsp;</td>";
                        }
                    }else{
                        $conteudo2 .= "<TD align='right' nowrap>$custo_peca</TD>";
                    }
                }
                $conteudo2 .= "</TR>";
            }
            $total_pecas3 = number_format($total_pecas3,2,",",".");
            $conteudo2 .= "</tbody>";
            if ($areaAdminCliente != true) {
                $conteudo2 .= "<tr class='table_line'>";
                if ($login_fabrica == 50){
                    $conteudo2 .= "<td colspan='7'>";
                }else{
                    $conteudo2 .= "<td colspan='6'>";
                }
                // Aqui é a terceira soma soma
                $conteudo2 .= "<font size='2'><b><CENTER>TOTAL</b>";
                $conteudo2 .= "</td>";
                $conteudo2 .= "<td><font size='2' color='009900'><b>$total_pecas3</b></td>";
                $conteudo2 .= "</tr>";
            }
            $conteudo2 .= " </TABLE></div>";
            echo $conteudo2;

            fputs ($fp,$conteudo.$conteudo3.$conteudo1.$conteudo2);
            fclose ($fp);
            echo "<a href='javascript:history.back()'>[Voltar]</a>";
        }
    }
}

flush();

?>


