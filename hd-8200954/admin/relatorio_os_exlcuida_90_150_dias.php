<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj = trim(pg_fetch_result($res,$i,cnpj));
				$nome = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}


$btn_acao = trim($_POST['btn_acao']);
if ($btn_acao == 'PesquisaDados'){
    $data_inicial   = $_POST["data_inicial"];
    $data_final     = $_POST["data_final"];
    $posto_codigo   = $_POST["posto_codigo"];
    $posto_nome     = $_POST["posto_nome"];
    $tipo           = $_POST["tipo"];

    if(empty($data_inicial) OR empty($data_final)){
        $msg_erro = "Data inválida!";
    }else{

        list($di, $mi, $yi) = explode("/", $data_inicial);
		if(@!checkdate($mi,$di,$yi)) 
			$msg_erro = "Data inicial inválida!";
		

		if(empty($msg_erro)){
            list($df, $mf, $yf) = explode("/", $data_final);
            if(@!checkdate($mf,$df,$yf)) 
                $msg_erro = "Data final inválida!";
		}

		if(empty($msg_erro)){
            $aux_data_inicial = "$yi-$mi-$di";
            $aux_data_final = "$yf-$mf-$df";
		}

		if(empty($msg_erro)){
			if(strtotime($aux_data_final) < strtotime($aux_data_inicial) or strtotime($aux_data_final) > strtotime('today')){
				$msg_erro = "Data inválida!";
			}
		}

        if(empty($msg_erro)){
            $sql = "SELECT '$aux_data_inicial'::date + interval '1 months' > '$aux_data_final'";
            $res = pg_query($con,$sql);
            $periodo = pg_fetch_result($res,0,0);
            if($periodo == 'f'){
                $msg_erro = "O período não podem ser maior que um mês";
            }
		}

    }

    if(empty($msg_erro) AND !empty($posto_codigo)){
        $sql = "SELECT 
                    tbl_posto.posto                ,
                    tbl_posto.nome                 ,
                    tbl_posto_fabrica.codigo_posto
                FROM tbl_posto
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE 
                    tbl_posto_fabrica.fabrica = $login_fabrica
                    AND tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
        $res = pg_query($con,$sql);
        if (pg_num_rows($res) == 1) {
            $posto        = trim(pg_fetch_result($res,0,'posto'));
            $posto_codigo = trim(pg_fetch_result($res,0,'codigo_posto'));
            $posto_nome   = trim(pg_fetch_result($res,0,'nome'));
        }else{
            $msg_erro = " Posto não encontrado. ";
        }
    }


}

$layout_menu = "auditoria";
$title = strtoupper("RELATÓRIO DE OS'S EXCLUÍDAS SEM PEÇAS MAIOR QUE 90 E 150 DIAS");
    
include "cabecalho.php";
?>


<? include "javascript_calendario.php"; ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});

    $(document).ready(function() {
        Shadowbox.init();

        function formatItem(row) {
            return row[2] + " - " + row[1];
        }

        function formatResult(row) {
            return row[0];
        }

        /* Busca pelo Código */
        $("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
            minChars: 3,
            delay: 150,
            width: 350,
            matchContains: true,
            formatItem: formatItem,
            formatResult: function(row) {return row[2];}
        });

        $("#posto_codigo").result(function(event, data, formatted) {
            $("#posto_nome").val(data[1]) ;
        });

        /* Busca pelo Nome */
        $("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
            minChars: 3,
            delay: 150,
            width: 350,
            matchContains: true,
            formatItem: formatItem,
            formatResult: function(row) {return row[1];}
        });

        $("#posto_nome").result(function(event, data, formatted) {
            $("#posto_codigo").val(data[2]) ;
            //alert(data[2]);
        });

        $('.ver_posto').click(function() {
            var posto = $(this).attr('rel');
            $("#"+posto).slideToggle("fade");
        });

    });

	function pesquisaPosto(campo,tipo){
		var campo = campo.value;

		if (jQuery.trim(campo).length > 2){
			Shadowbox.open({
				content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
				player:	"iframe",
				title:		"Pesquisa Posto",
				width:	800,
				height:	500
			});
		}else
			alert("Informe toda ou parte da informação para realizar a pesquisa!");
	}

	function gravaDados(name, valor){
		try {
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}

	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,nome,credenciamento){
		gravaDados('posto_codigo',codigo_posto);
		gravaDados('posto_nome',nome);
	}
</script>
<style type="text/css">
    .titulo_tabela{
        background-color:#596d9b;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;	
        padding: 2px;
    }

    .titulo_coluna{
        background-color:#95A4C6;
        font: bold 11px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    .titulo_coluna_2{
        background-color:#B8C2D8;
        font: bold 11px "Arial";
        color:#FFFFFF;
        text-align:center;    
    }

    .msg_erro{
        background-color:#FF0000;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;
        padding: 3px 0;
        margin: 0 auto;
        width: 700px;
    }

    .formulario{
        background-color:#D9E2EF;
        font:11px Arial;
        text-align:left;
    }

    table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;
    }

    .texto_avulso{
        font: 14px Arial; color: rgb(89, 109, 155);
        background-color: #d9e2ef;
        text-align: center;
        width:700px;
        margin: 0 auto;
        border-collapse: collapse;
        border:1px solid #596d9b;
    }

    .no-result{
        padding: 20px; 
        text-align: center;
        font-size: 18px;
    }

    #msg{ width:700px; margin:auto; }
</style>


<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
    <input type="hidden" name="btn_acao" value='PesquisaDados' />
    <?php
        if (!empty($msg_erro))
            echo "<div class='msg_erro'>{$msg_erro}</div>";
    ?>
    <table width="700" align="center" border="0" cellspacing='1' cellpadding='2' class='formulario'>
        <tbody>
            <tr>
                <td class="titulo_tabela" colspan="4">Parâmetros de Pesquisa</td>
            </tr>
            <tr>
                <td width='100px'>&nbsp;</td>
                <td width='250px'>&nbsp;</td>
                <td width='250px'>&nbsp;</td>
                <td width='100px'>&nbsp;</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td width="150px">
                    Data Inicial<br>
                    <INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' id='data_inicial' value='<? echo $data_inicial ?>' class='frm'>
                </td>
                <td>
                    Data Final <br>
                    <INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' id='data_final' value='<? echo $data_final ?>' class='frm'>
                </td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td nowrap>
                    Código do Posto<br>
                    <input class="frm" type="text" name="posto_codigo"  id="posto_codigo" style='width: 200px;' value="<? echo $posto_codigo ?>" />
                    <img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: pesquisaPosto (document.frm_relatorio.posto_codigo, 'codigo');" />
                </td>

                <td nowrap>
                    Nome do Posto<br>
                    <input class="frm" type="text" name="posto_nome" id="posto_nome" style='width: 200px;' value="<? echo $posto_nome ?>" />
                    <img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: pesquisaPosto (document.frm_relatorio.posto_nome, 'nome');" style="cursor:pointer;" />
                </td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td colspan='2'>
                    Tipo de OS<br>
                    <?php
                        if(empty($tipo))
                            $tipo = "consumidor_revenda";
                    ?>
                    <select name='tipo' id='tipo' style='width: 200px;' class="frm" >
                        <option value='consumidor_revenda' <?php if($tipo == 'consumidor_revenda') echo " selected ";?>>Todas</option>
                        <option value='consumidor' <?php if($tipo == 'consumidor') echo " selected ";?>>Consumidor</option>
                        <option value='revenda' <?php if($tipo == 'revenda') echo " selected ";?>>Revenda</option>
                    </select>
                </td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td colspan="4" style="padding: 20px; text-align: center">
                    <input type="submit"  value=" Pesquisar " />
                </td>
            </tr>
        </tbody>
    </table>
</form>
<br />

<?php
    if(empty($msg_erro)){
        $exibeDados = " style='display: none' ";
        if(!empty($posto)){
            $cond_1 = " AND tbl_os.posto = {$posto} ";
            $exibeDados = "";
        }

        if(!empty($tipo)){
            switch($tipo){
               case 'consumidor'  : $cond_2 = "AND tbl_os.consumidor_revenda = 'C' ";        break;
               case 'revenda'     : $cond_2 = "AND tbl_os.consumidor_revenda = 'R' ";                   break;
               default            : $cond_2 = "AND tbl_os.consumidor_revenda IN ('C','R') ";
            }
        }

        $sql = "SELECT DISTINCT
                    tbl_os.os,
                    tbl_os.sua_os,
                    tbl_os.data_digitacao, 
                    tbl_os.posto, 
                    tbl_produto.mao_de_obra, 
                    tbl_produto.produto,
                    tbl_produto.linha,
                    tbl_os.consumidor_revenda
                    INTO TEMP tmp_os_excluida_$login_admin
                FROM tbl_os
                    JOIN tbl_os_excluida    ON tbl_os_excluida.os = tbl_os.os AND tbl_os_excluida.fabrica = $login_fabrica
                    JOIN tbl_produto        ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
                    JOIN tbl_os_status      ON tbl_os_status.os = tbl_os.os
                WHERE tbl_os.fabrica = 0
                    {$cond_1}
                    {$cond_2}
                    AND tbl_os_status.status_os = 15
                    AND tbl_os_status.admin = 1020
                    AND tbl_os_status.automatico
                    AND tbl_os_status.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59';
                
                SELECT 
                    consumidor_revenda, 
                    SUM(mao_de_obra)    AS mao_de_obra,
                    COUNT(*)              AS qtde_os
                FROM tmp_os_excluida_$login_admin
                GROUP BY consumidor_revenda
                ORDER BY consumidor_revenda ASC;";
        //echo nl2br($sql);
        $res = @pg_query($con, $sql);

        if(@pg_num_rows($res)){
  
            echo "<div style='text-align: left; width: 700px; margin: 0 auto;'>";
                echo "<table width='300' border='0' cellspacing='1' cellpadding='0' class='formulario'>";

                    echo "<tr class='titulo_tabela'>";
                        echo "<td>Descrição</td>";
                        echo "<td>Quantidade</td>";
                        echo "<td>M.O</td>";
                    echo "</tr>";

                    for ($i = 0; $i < pg_num_rows($res); $i++) {
                        extract(pg_fetch_array($res));
                        $cor   = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

                        if($consumidor_revenda == 'C'){
                            echo "<tr bgcolor='$cor'>";
                                echo "<td>Consumidor</td>";
                                echo "<td style='text-align: center'>{$qtde_os}</td>";
                                echo "<td style='text-align: right'>R$ ".number_format($mao_de_obra,2,',','.')."</td>";
                            echo "</tr>";
                            $total_mao_obra += $mao_de_obra;
                            $total_os += $qtde_os;

                        }

                        if($consumidor_revenda == 'R'){
                            echo "<tr bgcolor='$cor'>";
                                echo "<td>Revenda</td>";
                                echo "<td style='text-align: center'>{$qtde_os}</td>";
                                echo "<td style='text-align: right'>R$ ".number_format($mao_de_obra,2,',','.')."</td>";
                            echo "</tr>";
                            $total_mao_obra += $mao_de_obra;
                            $total_os += $qtde_os;
                        }                
                    }
                    
                    echo "<tr class='titulo_coluna'>";
                        echo "<td>Total</td>";
                        echo "<td style='text-align: center'>{$total_os}</td>";
                        echo "<td style='text-align: right'>R$ ".number_format($total_mao_obra,2,',','.')."</td>";
                    echo "</tr>";
                echo "</table>";
            echo "</div>";

            
            //Lista por posto!
            $sql = "SELECT DISTINCT
                        tbl_posto.nome,
                        tbl_posto.posto,
                        tbl_posto_fabrica.codigo_posto
                    FROM tmp_os_excluida_$login_admin
                        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tmp_os_excluida_$login_admin.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                        JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
                    ORDER BY tbl_posto.nome;";
            $res = pg_query($con, $sql);

            echo "<br><table width='700' border='0' cellspacing='1' cellpadding='0' class='formulario' align='center'>";
                for ($i = 0; $i < pg_num_rows($res); $i++) {
                    extract(pg_fetch_array($res));
                    $codigo_posto = str_pad($codigo_posto, 6, "0", STR_PAD_LEFT);

                    echo "<tr class='titulo_tabela'>";
                        echo "<td style='text-align: left; text-transform: uppercase; cursor: pointer'  rel='{$posto}' class='ver_posto' >&nbsp;&nbsp;{$codigo_posto} - {$nome}</td>";
                    echo "</tr>";

                    echo "<tr id='{$posto}' {$exibeDados}>";
                        echo "<td>";

                            $sql_2 = "SELECT
                                        tmp_os_excluida_$login_admin.os,
                                        tmp_os_excluida_$login_admin.sua_os,
                                        tmp_os_excluida_$login_admin.mao_de_obra,
                                        tmp_os_excluida_$login_admin.consumidor_revenda,
                                        tbl_linha.nome AS nome_linha
                                    FROM tmp_os_excluida_$login_admin
                                        JOIN tbl_linha ON tbl_linha.linha = tmp_os_excluida_$login_admin.linha AND tbl_linha.fabrica = {$login_fabrica}
                                    WHERE 
                                        tmp_os_excluida_$login_admin.posto = {$posto};";
                            $res_2 = pg_query($con, $sql_2);

                            echo "<table width='700' border='0' cellspacing='1' cellpadding='2' class='formulario' align='center'>";

                                echo "<tr class='titulo_coluna'>";
                                    echo "<td width='100px'>OS</td>";
                                    echo "<td width='90px'>C/R</td>";
                                    echo "<td width='*'>Linha</td>";
                                    echo "<td width='80px'>M.O</td>";
                                echo "</tr>";
                                $total_mo = 0;
                                for ($a = 0; $a < pg_num_rows($res_2); $a++) {
                                    extract(pg_fetch_array($res_2));
                                    $total_mo += $mao_de_obra;
                                    $mao_de_obra = "R$ ".number_format($mao_de_obra,2,',','.');

                                    $cor   = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
                                    switch($consumidor_revenda){
                                       case 'C'  : $consumidor_revenda = "Consumidor"; break;
                                       case 'R'  : $consumidor_revenda = "Revenda";    break;
                                    }

                                    echo "<tr style='background: {$cor}'>";
                                        echo "<td style='text-align: center;'><a href='os_press.php?os={$os}' target='_blank' title='Abrir OS {$os}'>{$sua_os}</a></td>";
                                        echo "<td>{$consumidor_revenda}</td>";
                                        echo "<td>{$nome_linha}</td>";
                                        echo "<td style='text-align: right;'>{$mao_de_obra}</td>";
                                    echo "</tr>";
                                }

                                //Só vai mostra o total quando tiver mais de uma linha
                                if(pg_num_rows($res_2) > 1){
                                    $total_mo = "R$ ".number_format($total_mo,2,',','.');
                                    echo "<tr class='titulo_coluna'>";
                                        echo "<td style='text-align: right;' colspan='3'>Total&nbsp;</td>";
                                        echo "<td style='text-align: right;'>{$total_mo}</td>";
                                    echo "</tr>";

                                    $sql_3 = "SELECT
                                                tbl_linha.nome AS nome_linha,
                                                COUNT(1) AS total_linha
                                            FROM tmp_os_excluida_$login_admin
                                                JOIN tbl_linha ON tbl_linha.linha = tmp_os_excluida_$login_admin.linha AND tbl_linha.fabrica = {$login_fabrica}
                                            WHERE 
                                                tmp_os_excluida_$login_admin.posto = {$posto}
                                            GROUP BY tbl_linha.nome
                                            ORDER BY tbl_linha.nome;";
                                    $res_3 = pg_query($con, $sql_3);

                                    if(pg_num_rows($res_3) > 0){
                                        echo "<tr class='titulo_coluna_2'>";
                                            echo "<td width='190px'>Linha</td>";
                                            echo "<td width='*'>Qtde</td>";
                                            echo "<td colspan='2'>&nbsp;</td>";
                                        echo "</tr>";
                                        for ($a = 0; $a < pg_num_rows($res_3); $a++) {
                                            extract(pg_fetch_array($res_3));

                                            $cor   = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
                                            echo "<tr style='background: {$cor}'>";
                                                echo "<td width='190px' class='titulo_coluna_2' style='text-align: left;'>{$nome_linha}</td>";
                                                echo "<td width='*'>&nbsp;{$total_linha}</td>";
                                                echo "<td colspan='2'>&nbsp;</td>";
                                            echo "</tr>";
                                        }
                                    }
                                }

                                echo "<tr style='background: #fff;'><td colspan='4'>&nbsp;<br /></td></tr>";
                            echo "</table>";
                        echo "</td>";
                    echo "</tr>";
                             
                }
            echo "</table>";        


                    
        }else{
            echo "<div class='no-result'>Nenhum resultado encontrado!</div>";
        }

    }
?>

<br /><br />
<?php include "rodape.php"; ?>
