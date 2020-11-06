<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$nomemes = array(1=> "JANEIRO", "FEVEREIRO", "MARÇO", "ABRIL", "MAIO", "JUNHO", "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO");

if (!empty($_REQUEST['btn_acao'])) {
   $mes                 = trim($_REQUEST["mes"]); 
   $ano                 = trim($_REQUEST["ano"]); 
   $familia             = trim($_REQUEST["familia"]); 
   $linha               = trim($_REQUEST["linha"]); 
   $produto_referencia  = trim($_REQUEST["produto_referencia"]); 
   $produto_descricao   = trim($_REQUEST["produto_descricao"]); 
   $data_referencia     = trim($_REQUEST["data_referencia"]); 

	if ($mes == "" || $ano == "") {
		$msg_erro = "Selecione o mês e o ano para a pesquisa";
	}

	if (empty($mes) AND empty($msg_erro)) {
		$mes = intval($mes);
		if ($mes < 1 || $mes > 12) {
			$msg_erro = "O mês deve ser um número entre 1 e 12";
			$mes = "";
		}
	}

	if (strlen($ano) != 4 AND empty($msg_erro)) {
		$msg_erro = "O ano deve conter 4 dígitos";
		$ano = "";
	}

    if(empty($mes) AND empty($msg_erro)){
        $msg_erro = "Informe um mês";
    }

    if(empty($ano) AND empty($msg_erro)){
        $msg_erro = "Informe um ano";
    }

	if (!empty($familia) AND empty($msg_erro)) {
		$familia = intval($familia);
		$sql = "SELECT familia FROM tbl_familia WHERE familia = $familia AND  fabrica=$login_fabrica";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			$msg_erro = "Família escolhida não existe";
		}
	}

	if (!empty($linha) AND empty($msg_erro)) {
		$linha = intval($linha);
		$sql = "SELECT linha FROM tbl_linha WHERE linha = $linha AND fabrica=$login_fabrica";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			$msg_erro = "Linha escolhida não existe";
		}
	}

	if (!empty($produto_referencia) AND empty($msg_erro)) {
		$sql = "SELECT produto FROM tbl_produto JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha WHERE tbl_linha.fabrica=$login_fabrica AND tbl_produto.referencia ILIKE '$produto_referencia'";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			$msg_erro = "Produto $produto_referencia inexistente";
		}else{
            $produto = pg_fetch_result($res, 0, 'produto');
        }
	}
}

$layout_menu = "os";
$title = "RELATÓRIO DE TEMPO DE PERMANÊNCIA EM CONSERTO";
include 'cabecalho.php';
?>
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css" />
<script type="text/javascript" charset="utf-8">
    $(document).ready(function() {
		Shadowbox.init();
    });

	function pesquisaProduto(campo, tipo){
		var campo	= jQuery.trim(campo.value);

		if (campo.length > 2){   
			Shadowbox.open({
				content	:	"pesquisa_produto_nv.php?"+tipo+"="+campo,
				player	:	"iframe",
				title	:	"<?php fecho('pesquisa.de.produto', $con, $cook_idioma);?>",
				width	:	800,
				height	:	500
			});
		}else
			alert("<?php fecho('informar.toda.parte.informacao.para.realizar.pesquisa', $con, $cook_idioma);?>");
	}

	function retorna_produto(produto,referencia,descricao, posicao, voltagem){
		gravaDados("produto_referencia",referencia);
		gravaDados("produto_descricao",descricao);
	}

	function gravaDados(name, valor){
		try {
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}
</script>
<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
 .titulo_coluna a{
	color:#FFFFFF;
 }

 .titulo_coluna a:hover{
	color:#000000;
 }


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center !important;
    padding: 5px;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.formulario td{
    text-align: left;
}

.subtitulo{
	background-color: #7092BE;
	color:#FFFFFF;
	font:14px Arial;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}
</style>

<body>
<br />
<form name="frm_pesquisa" id="frm_lbm" method="post" action="<? echo $PHP_SELF ?>" enctype='multipart/form-data'>
	<table width='700' align='center' border='0' cellspacing='0' cellpadding='4' class="formulario">
        <?php
            if(!empty($msg_erro)){
                echo "<tr>";
                    echo "<td class='msg_erro' colspan='4'>{$msg_erro}</td>";
                echo "</tr>";
            }
        ?>
		<tr class="titulo_tabela">
            <th colspan="4">Parâmetros de Pesquisa</th>
        </tr>
        <tr>
            <td width='100px'>&nbsp;</td>
            <td width='250px'>&nbsp;</td>
            <td width='250px'>&nbsp;</td>
            <td width='100px'>&nbsp;</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                Mês<br />
				<select name="mes" size="1" class="frm" style='width: 180px;'>
					<option value=''></option>
					<?
					for ($i = 1 ; $i <= count($meses) ; $i++) {
						echo "<option value='$i'";
						if ($mes == $i) echo " selected";
						echo ">" . $meses[$i] . "</option>";
					}
					?>
				</select>
            </td>
            <td>
                Ano<br />
                <select name="ano" size="1" class="frm"  style='width: 180px;'>
					<option value=''></option>
					<?
					for ($i = date('Y'); $i >= 2003; $i--) {
						echo "<option value='$i'";
						if ($ano == $i) echo " selected";
						echo ">$i</option>";
					}
					?>
				</select>
            </td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                Família<br />
                <?php
                    $sqlf = "SELECT  *
                            FROM    tbl_familia
                            WHERE   tbl_familia.fabrica = $login_fabrica
                            ORDER BY tbl_familia.descricao;";
                    $resf = pg_query ($con,$sqlf);

                    if (pg_numrows($resf) > 0) {
                        echo "<select class='frm' name='familia'  style='width: 180px;'>";
                        echo "<option value='' selected>- selecione</option>";

                        for ($x = 0 ; $x < pg_numrows($resf) ; $x++){
                            $aux_familia = trim(pg_fetch_result($resf,$x,familia));
                            $aux_descricao  = trim(pg_fetch_result($resf,$x,descricao));

                            echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
                        }
                        echo "</select>\n";
                    }
				?>
            </td>
            <td>
                Linha<br />
				<?php
				$sql_linha = "
                    SELECT
                    linha,
                    nome
                    
                    FROM
                    tbl_linha
                    
                    WHERE
                    tbl_linha.fabrica = $login_fabrica
                    
                    ORDER BY
                    tbl_linha.nome
				";
				$res_linha = pg_query($con, $sql_linha);

				echo "<select class='frm'  style='width: 180px;' name='linha'>\n";

				if (pg_numrows($res_linha) > 0) {
					echo "<option value='' selected>- selecione</option>";

					for ($x = 0 ; $x < pg_numrows($res_linha) ; $x++){
						$aux_linha = trim(pg_fetch_result($res_linha, $x, linha));
						$aux_nome = trim(pg_fetch_result($res_linha, $x, nome));
						if ($linha == $aux_linha) {
							$selected = "SELECTED";
						}
						else {
							$selected = "";
						}

						echo "<option value='$aux_linha' $selected>$aux_nome</option>\n";
					}
				}
				else {
					echo "<option value=''>Não existem linhas cadastradas</option>\n";
				}

				echo "</select>\n";
				?>
            </td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                Produto Refêrencia<br />
                <input class="frm" type="text" name="produto_referencia"  style='width: 180px;' maxlength="20" value="<? echo $produto_referencia ?>" />&nbsp;
                <img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: pesquisaProduto(document.frm_pesquisa.produto_referencia, 'referencia')" />
            </td>
            <td colspan='2'>
                Produto Descrição<br />
                <input class="frm" type="text" name="produto_descricao"  style='width: 250px;'value="<? echo $produto_descricao ?>" />&nbsp;
                <img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: pesquisaProduto (document.frm_pesquisa.produto_descricao, 'descricao')" />
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                <?php
                    switch ($data_referencia) {
                        case "data_fechamento":
                            $selected_data_fechamento = "selected";
                        break;

                        case "data_conserto":
                            $selected_data_conserto = "selected";
                        break;
                    }
                ?>
                Referência<br />   
                <select id="data_referencia" name="data_referencia" class="frm"  style='width: 180px;'>
                    <option value="data_fechamento" <? echo $selected_data_fechamento; ?>>Data do Fechamento</option>
                    <option value="data_conserto" <? echo $selected_data_conserto; ?>>Data do Conserto</option>
                </select>
            </td>
            <td></td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td style='text-align: center; padding: 20px;' colspan='4'>
                <input type='submit' value=' Pesquisar ' name='btn_acao' />
            </td>
        </tr>
	</table>
</form>
<br />
<?php
    if (!empty($mes) AND !empty($ano) AND empty($msg_erro)){
        $sql = "SELECT fn_dias_mes('$ano-$mes-01',0)";
        $res3 = pg_query($con,$sql);
        $data_inicial = pg_fetch_result($res3,0,0);

        $sql = "SELECT fn_dias_mes('$ano-$mes-01',1)";
        $res3 = pg_query($con,$sql);
        $data_final = pg_fetch_result($res3,0,0);

        if($data_referencia == 'data_fechamento')
           $where_data = " AND tbl_os.finalizada   BETWEEN '$data_inicial' AND '$data_final' ";
        else
           $where_data = " AND tbl_os.data_conserto   BETWEEN '$data_inicial' AND '$data_final' ";

        if(strlen($familia) > 0){
            /*
            $sql = "SELECT produto
                    INTO TEMP temp_rtc_familia
                    FROM tbl_produto PR
                    JOIN tbl_familia FA USING(familia)
                    WHERE FA.fabrica    = $login_fabrica 
                    AND   FA.familia    = $familia;
                    CREATE INDEX temp_rtc_familia_produto ON temp_rtc_familia(produto);";
            $res = pg_query($con,$sql);

            $join_1  =" JOIN temp_rtc_familia FF ON FF.produto = tbl_os.produto";
            */
            $where_familia = " AND tbl_produto.familia = {$familia} ";
        }

        if ($linha) {
            /*
           echo  $sql = "
            SELECT
            produto
            INTO TEMP temp_rtc_linha

            FROM
            tbl_produto
            JOIN tbl_linha USING(linha)

            WHERE
            tbl_linha.fabrica = $login_fabrica 
            AND	tbl_linha.linha = $linha;

            CREATE INDEX temp_rtc_linha_produto ON temp_rtc_linha(produto);
            ";
            $res = pg_query($con,$sql);

            $join_linha = " JOIN temp_rtc_linha LI ON LI.produto = tbl_os.produto";
            */
            $where_linha = " AND tbl_produto.linha = {$linha} ";
        }

        if(strlen($produto_referencia) > 0){
            $sql = "SELECT produto
                    FROM tbl_produto
                    JOIN tbl_linha USING(linha)
                    WHERE fabrica    = $login_fabrica
                    AND   referencia = '$produto_referencia' ;";
            $res = pg_query($con,$sql);
            $produto = @pg_fetch_result($res,0,0);
            $add_3 = "AND tbl_os.produto = $produto";
        }

        //Primeiro Relatorio
		$sql = "SELECT os,data_abertura,$data_referencia::date,posto
			INTO TEMP temp_rtc_{$login_posto}_{$mes}
			FROM tbl_os 
                /*$join_1*/
                /*$join_linha*/
                JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			WHERE tbl_os.fabrica = $login_fabrica 
            {$where_data} 
			AND tbl_os.excluida IS NOT TRUE 
            AND tbl_os.posto = $login_posto
            {$where_linha}
            {$where_familia}
			$add_1 $add_2 $add_3;
            ";
        $res = pg_query($con, $sql);

        $sql = "SELECT os FROM temp_rtc_{$login_posto}_{$mes} LIMIT 1";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res)){

			$sqld = "
					SELECT count(os) AS total,
					SUM(($data_referencia - data_abertura)) AS data_diferenca
					FROM temp_rtc_{$login_posto}_{$mes}
					WHERE $data_referencia::date-data_abertura <= 1;
					";
			$resd = pg_query($con,$sqld);
			$total_1 = @pg_fetch_result($resd,0,0);
			$total_1d = @pg_fetch_result($resd,0,data_diferenca);

            $sqld = "
                    SELECT count(os) AS total,
                    SUM(($data_referencia - data_abertura)) AS data_diferenca
                    FROM temp_rtc_{$login_posto}_{$mes}
                    WHERE $data_referencia::date-data_abertura <= 2
                    AND   $data_referencia::date-data_abertura >  1;
                    ";
            $resd = pg_query($con,$sqld);
            $total_2 = @pg_fetch_result($resd,0,0);
            $total_2d = @pg_fetch_result($resd,0,data_diferenca);

            $sqld = "
                    SELECT count(os) AS total,
                    SUM(($data_referencia - data_abertura)) AS data_diferenca
                    FROM temp_rtc_{$login_posto}_{$mes}
                    WHERE $data_referencia::date-data_abertura  > 2;
                    ";
            $resd = pg_query($con,$sqld);
            $total_3 = @pg_fetch_result($resd,0,0);
            $total_3d = @pg_fetch_result($resd,0,data_diferenca);

            $sqld = "
                    SELECT count(os) AS total
                    FROM tbl_os
                    $join_linha
                    WHERE   tbl_os.fabrica = $login_fabrica 
                    {$where_data} 
                    AND     tbl_os.excluida IS NOT TRUE 
                    AND     $data_referencia::date IS NULL
                    AND tbl_os.posto = $login_posto
                    $cont_cliente_admin
                    $add_1 $add_2 $add_3 ; ";
            $resd = pg_query($con,$sqld);
            $total_4 = @pg_fetch_result($resd,0,0);
            $total_4d = @pg_fetch_result($resd,0,data_diferenca);


            echo "<table align='center' border='0' cellspacing='1' cellpadding='2' class='tabela' width='700'>";
                echo "<tr class='titulo_coluna'>\n";
                    //echo "<td>Posto</td>";
                    echo "<td>Até 1 Dia</td>";
                    echo "<td>Até 2 Dias</td>";
                    echo "<td>Mais que 2 Dias</td>";
                    echo "<td>OS em Aberto</td>";
                    echo "<td>Total de OS</td>";
                echo "</tr>";
                echo "<tr bgcolor='#F7F5F0'>";
                    echo "<td align='center' title='$total_1d'>$total_1</td>";
                    echo "<td align='center' title='$total_2d'>$total_2</td>";
                    echo "<td align='center' title='$total_3d'>$total_3</td>";
                    echo "<td align='center' title='$total_4d'>$total_4</td>\n";
                    echo "<td align='center'>" . (intval($total_1) + intval($total_2) + intval($total_5) + intval($total_3) + intval($total_4)) . "</td>";
                echo "</tr>";
            echo "</table>";

            //Segundo Relatorio
            $sql = "
                SELECT DISTINCT
                    tbl_os.os ,
                    tbl_os.sua_os ,
                    TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento ,
                    TO_CHAR(tbl_os.data_conserto,'DD/MM/YYYY') AS data_conserto ,
                    TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
                    tbl_produto.referencia ,
                    tbl_produto.descricao ,
                    SUM(DISTINCT tbl_os.data_fechamento::date - tbl_os.data_abertura) AS data_diferenca,
                    tbl_os.consumidor_nome,
                    tbl_os.consumidor_cidade,
                    tbl_os.consumidor_estado
                FROM tbl_os
                    JOIN temp_rtc_{$login_posto}_{$mes} ON temp_rtc_{$login_posto}_{$mes}.os = tbl_os.os
                    JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                GROUP BY 
                    tbl_os.os,
                    tbl_os.sua_os , 
                    tbl_os.data_fechamento, 
                    tbl_os.data_conserto, 
                    tbl_os.data_abertura, 
                    tbl_produto.referencia, 
                    tbl_produto.descricao,
                    tbl_os.consumidor_nome,
                    tbl_os.consumidor_cidade,
                    tbl_os.consumidor_estado;";
             $res = pg_query($con,$sql);
             //echo "Total: ".pg_num_rows($res);

            if(pg_num_rows($res)){
                echo "<br />";
                $dados = "<table align='center' border='0' cellspacing='1' cellpadding='2' class='tabela' width='700'>";
                    $dados .= "<tr>";
                        $dados .= "<td style = 'background-color:#596d9b; font: bold 11px \"Arial\"; color:#FFFFFF; text-align:center;'>OS</td>";
                        $dados .= "<td style = 'background-color:#596d9b; font: bold 11px \"Arial\"; color:#FFFFFF; text-align:center;'>Produto</td>";
                        $dados .= "<td style = 'background-color:#596d9b; font: bold 11px \"Arial\"; color:#FFFFFF; text-align:center;'>Abertura</td>";
                        $dados .= "<td style = 'background-color:#596d9b; font: bold 11px \"Arial\"; color:#FFFFFF; text-align:center;'>Conserto</td>";
                        $dados .= "<td style = 'background-color:#596d9b; font: bold 11px \"Arial\"; color:#FFFFFF; text-align:center;'>Fechamento</td>";
                        $dados .= "<td style = 'background-color:#596d9b; font: bold 11px \"Arial\"; color:#FFFFFF; text-align:center;'>Qtde Dias</td>";
                        $dados .= "<td colspan='3' style = 'background-color:#596d9b; font: bold 11px \"Arial\"; color:#FFFFFF; text-align:center;'>Consumidor</td>";
                    $dados .= "</tr>";

                    $total_os = 0;
		            $total_dias = 0;
                    for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                        $os                 = pg_fetch_result($res,$i,'os');
                        $sua_os             = pg_fetch_result($res,$i,'sua_os');
                        $total_diferenca    = pg_fetch_result($res,$i,'data_diferenca');
                        $referencia         = pg_fetch_result($res,$i,'referencia');
                        $descricao          = pg_fetch_result($res,$i,'descricao');
                        $data_conserto      = pg_fetch_result($res,$i,'data_conserto');
                        $data_fechamento    = pg_fetch_result($res,$i,'data_fechamento');
                        $data_abertura      = pg_fetch_result($res,$i,'data_abertura');
                        $consumidor_nome    = pg_fetch_result($res,$i,'consumidor_nome');
                        $consumidor_cidade  = pg_fetch_result($res,$i,'consumidor_cidade');
                        $consumidor_estado  = pg_fetch_result($res,$i,'consumidor_estado');
                        
                        $total_os++;
			            $total_dias += ($total_diferenca);

                        $cor = ($i % 2 ) ? "#F1F4FA" : "#F7F5F0";
                        $dados .= "<tr>";
                            $dados .= "<td nowrap style='background-color: $cor; text-align: center'><a href='os_press.php?os=$os'target='_blank'>{$sua_os}</a></td>";
                            $dados .= "<td nowrap style='background-color: $cor;'>{$referencia} - {$descricao}</td>";
                            $dados .= "<td nowrap style='background-color: $cor;'>{$data_abertura}</td>";
                            $dados .= "<td nowrap style='background-color: $cor;'>{$data_conserto}</td>";
                            $dados .= "<td nowrap style='background-color: $cor;'>{$data_fechamento}</td>";
                            $dados .= "<td nowrap style='background-color: $cor; text-align: center'>{$total_diferenca}</td>";
                            $dados .= "<td nowrap style='background-color: $cor;'>{$consumidor_nome}</td>";
                            $dados .= "<td nowrap style='background-color: $cor;'>{$consumidor_cidade}</td>";
                            $dados .= "<td nowrap style='background-color: $cor;'>{$consumidor_estado}</td>";
                        $dados .= "</tr>";
                    }

                    $dados .= "<tr>";
                        $dados .= "<td nowrap style = 'background-color:#596d9b; font: bold 11px \"Arial\"; color:#FFFFFF; text-align:center;'>{$total_os}</td>";
                        $dados .= "<td nowrap colspan='4'  style = 'background-color:#596d9b; font: bold 11px \"Arial\"; color:#FFFFFF; text-align:center;'>&nbsp;</td>";
                        $dados .= "<td nowrap  style = 'background-color:#596d9b; font: bold 11px \"Arial\"; color:#FFFFFF; text-align:center;'>{$total_dias}</td>";
                        $dados .= "<td nowrap colspan='3'  style = 'background-color:#596d9b; font: bold 11px \"Arial\"; color:#FFFFFF; text-align:center;'>&nbsp;</td>";
                    $dados .= "</tr>";

                    $total = number_format($total_dias / $total_os, 2, ",", "");

                    $dados .= "<tr class='titulo_coluna'>";
                        $dados .= "<td colspan='9'  style = 'background-color:#596d9b; font: bold 11px \"Arial\"; color:#FFFFFF; text-align:center;'>MÉDIA DE DIAS POR OS NO PERÍODO CONSULTADO: {$total}</td>";
                    $dados .= "</tr>";

                $dados .= "</table>";

                echo $dados;

                $conteudo_excel = ob_get_clean();
                $arquivo = fopen("xls/relatorio_tempo_conserto_os_{$login_fabrica}-{$login_posto}.xls", "w+");
                fwrite($arquivo, $dados);
                fclose($arquivo);
                //header("location:xls/relatorio_tempo_conserto_os_{$login_fabrica}-{$login_posto}.xls");

                echo "<br><br>";
                echo "<a href='xls/relatorio_tempo_conserto_os_{$login_fabrica}-{$login_posto}.xls' target='_blank' style='font-size: 10pt;'><img src='imagens/excell.gif'> Clique aqui para download do relatório em Excel</a>";
                echo "<br><br>";
            }
        }else{
            echo "<div style='text-align: center'>Sem resultado!</div>";
        }
    






    }



	include "rodape.php";
?>
</body>
</html>
