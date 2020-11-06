<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include "autentica_admin.php";
include "funcoes.php";

$msg  = $_GET['msg'];
$btn_acao = trim($_POST['btn_acao']);
$codigo_agrupado =  $_GET['codigo_agrupado'] ;
$numero = $_GET['numero'];
$erro_codigo="";
$tipo_pesquisa = $_GET['tipo_pesquisa'];
$ajax = @$_REQUEST['ajaxApagar'];
if(strlen($codigo_agrupado)>0 AND $ajax == 'apagar'){
    
    $sql = "UPDATE tbl_extrato_conferencia SET 
                nota_fiscal          = null,
                data_nf              = null,
                previsao_pagamento   = null,
                valor_nf             = null,
                valor_nf_a_pagar     = null,
                data_lancamento_nota = null,
                cfop                 = null,
                codigo_item          = null,
                serie                = null,
                estabelecimento      = null
            FROM tbl_extrato_agrupado
            WHERE tbl_extrato_conferencia.extrato = tbl_extrato_agrupado.extrato
                AND codigo='$codigo_agrupado'
                AND tbl_extrato_agrupado.aprovado IS NOT NULL
                AND cancelada IS NOT TRUE";

    if(pg_query($con,$sql)){
        echo "ok";
    }else{
        echo "Erro ao apagar dados!";
    }
    exit;
}

if(strlen($codigo_agrupado)>0 && strlen($numero)>0){
    $where_nota = $tipo_pesquisa == 'alterar' ? " nota_fiscal IS NOT NULL " : " nota_fiscal IS NULL ";

	$sql = "SELECT  tbl_extrato_conferencia.extrato_conferencia                        AS extrato_conferencia,
						tbl_extrato_conferencia.data_conferencia                           AS data_conferencia,
						to_char(tbl_extrato_conferencia.data_conferencia,'DD/MM/YYYY')     AS data,
						tbl_extrato_conferencia.nota_fiscal                                AS nota_fiscal,
						to_char(tbl_extrato_conferencia.data_nf,'DD/MM/YYYY')              AS data_nf,
						to_char(tbl_extrato.data_geracao,'DD/MM/YYYY')              AS data_geracao,
						tbl_extrato_conferencia.valor_nf                                   AS valor_nf,
						tbl_extrato_conferencia.valor_nf_a_pagar                           AS valor_nf_a_pagar,
						tbl_extrato_conferencia.caixa                                      AS caixa,
						to_char(tbl_extrato_conferencia.previsao_pagamento,'DD/MM/YYYY')   AS previsao_pagamento,
						tbl_admin.login                                                    AS login,
						tbl_extrato_conferencia.extrato                                   ,
						tbl_posto.nome                                                     AS posto_nome,
						tbl_posto.posto                                                    AS posto,
						tbl_posto_fabrica.codigo_posto                                     AS codigo_posto,
						tbl_extrato.valor_agrupado,
						tbl_posto_fabrica_banco.tipo_conta
				FROM tbl_extrato_agrupado
				JOIN tbl_extrato_conferencia   USING(extrato)
				JOIN tbl_extrato  USING(extrato)
				JOIN tbl_posto  USING(posto)
				JOIN tbl_posto_fabrica  on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_admin ON tbl_admin.admin = tbl_extrato_conferencia.admin
				LEFT JOIN tbl_posto_fabrica_banco on (tbl_posto_fabrica.fabrica = tbl_posto_fabrica_banco.fabrica and tbl_posto_fabrica.posto = tbl_posto_fabrica_banco.posto)
				WHERE  cancelada IS NOT TRUE
				AND    tbl_extrato_agrupado.codigo ='$codigo_agrupado'
				AND  tbl_extrato_agrupado.aprovado IS NOT NULL
				AND {$where_nota} ";
    //echo nl2br($sql);
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$resposta =  "<table width='800' border='0' align='center' cellspacing='1' cellpadding='4' class='tabela' style='margin: 5px 0 0 0'>";
            $resposta .= "<tr class='titulo_tabela'>";
                $resposta .= "<td colspan='100%'>conferência</td>";
            $resposta .= "</tr>";
            $resposta .= "<tr class='titulo_coluna'>";
                $resposta .= "<td>Cod. Posto</td>";
                $resposta .= "<td>Nome do Posto</td>";
                $resposta .= "<td>Caixa Arq.</td>";
                $resposta .= "<td>Valor NF</td>";
                $resposta .= "<td>Nota Fiscal</td>";
                $resposta .= "<td>Data NF</td>";
                $resposta .= "<td>Previsão de Pagamento</td>";
                $resposta .= "<td>Tipo Nota</td>";
                $resposta .= "<td>Série</td>";
                $resposta .= "<td>Estabelecimento</td>";
                $resposta .= "<td>Tipo Conta</td>";
                $resposta .= "<td>Ação</td>";
            $resposta .= "</tr>";
		$numero+=1;

		$sqlt = " SELECT sum(tbl_extrato_conferencia_item.mao_de_obra)  as total
								FROM tbl_extrato
								JOIN tbl_extrato_agrupado USING(extrato)
								JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
								JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
								WHERE tbl_extrato_agrupado.codigo ='$codigo_agrupado'
								AND   tbl_extrato.fabrica = $login_fabrica
								AND  tbl_extrato_agrupado.aprovado IS NOT NULL
								and   cancelada IS NOT TRUE";
		$rest = pg_query($con,$sqlt);
		$total = pg_fetch_result($rest,0,total);

		$total_avulso = 0;
		$sql_av = " SELECT
			extrato,
			historico,
			valor,
			tbl_extrato_lancamento.admin,
			debito_credito,
			lancamento
		FROM tbl_extrato_lancamento
		JOIN tbl_extrato_agrupado USING(extrato)
		WHERE tbl_extrato_agrupado.codigo ='$codigo_agrupado'
		AND fabrica = $login_fabrica
		AND  tbl_extrato_agrupado.aprovado IS NOT NULL
		AND (tbl_extrato_lancamento.admin IS NOT NULL OR lancamento in (103,104))";

		$res_av = pg_query ($con,$sql_av);

		if(pg_num_rows($res_av) > 0){
			for($i=0; $i < pg_num_rows($res_av); $i++){
				$extrato         = trim(pg_fetch_result($res_av, $i, extrato));
				$historico       = trim(pg_fetch_result($res_av, $i, historico));
				$valor           = trim(pg_fetch_result($res_av, $i, valor));
				$debito_credito  = trim(pg_fetch_result($res_av, $i, debito_credito));
				$lancamento      = trim(pg_fetch_result($res_av, $i, lancamento));
				
				if($debito_credito == 'D'){ 
					if ($lancamento == 78 AND $valor>0){
						$valor = $valor * -1;
					}
				}

				$total_avulso = $valor + $total_avulso;
			}
		}else{
			$total_avulso = 0 ;
		}
		
		$total += $total_avulso;

		if($total < 0) {
			$total = 0 ;
		}

		for($i =0;$i<pg_num_rows($res);$i++) {
			$extrato_conferencia= pg_fetch_result($res,$i,extrato_conferencia);
			$data               = pg_fetch_result($res,$i,data);
			$nota_fiscal_posto  = pg_fetch_result($res,$i,nota_fiscal);
			$data_nf            = pg_fetch_result($res,$i,data_nf);
			$valor_nf           = pg_fetch_result($res,$i,valor_nf);
			$caixa              = pg_fetch_result($res,$i,caixa);
			$previsao_pagamento = pg_fetch_result($res,$i,previsao_pagamento);
			$admin              = pg_fetch_result($res,$i,login);
			$extrato            = pg_fetch_result($res,$i,extrato);
			$posto_nome         = pg_fetch_result($res,$i,posto_nome);
			$posto              = pg_fetch_result($res,$i,posto);
			$codigo_posto       = pg_fetch_result($res,$i,codigo_posto);
			$tipo_conta         = pg_fetch_result($res,$i,'tipo_conta');
			$valor_nf           = number_format($valor_nf,2,",",".");

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			$resposta .= "<TR bgcolor='$cor' id='tr_{$codigo_agrupado}'>";
                $resposta .= "<input type='hidden' name='extrato_$numero' value='$extrato'>";
                $resposta .= "<input type='hidden' name='posto_$numero'   value='$posto' class='frm'>";
                $resposta .= "<input type='hidden' name='codigo_agrupado_$numero'   value='$codigo_agrupado' class='frm'>";
                $resposta .= "<input type='hidden' name='extrato_conferencia_$numero'   value='$extrato_conferencia' class='frm'>";

                $resposta .= "<TD>$codigo_posto</TD>";
                $resposta .= "<TD nowrap>$posto_nome</TD>";
                $resposta .= "<TD>$caixa</TD>";
                $resposta .= "<TD >";
                    $resposta .= ($i == pg_num_rows($res) - 1) ? number_format($total,2,",",".") : "";
                    $resposta .=  "<input type='hidden' name='valor_nf_$numero' size='12' value='$total' class='frm'>" ;
                $resposta .= "</TD>";
                $resposta .= "<TD>";
                    $resposta .= ($i == pg_num_rows($res) - 1) ?"<input type='text' name='nf_conferencia_$numero' size='12' value='$nota_fiscal_posto' class='frm'>" : "&nbsp;";
                $resposta .= "</TD>";
                $resposta .= "<TD align='left'>";
                    $resposta .= ($i == pg_num_rows($res) - 1) ? "<input type='text' name='data_nf_conferencia_$numero' size='12' value='$data_nf' class='frm' rel='data'>" : "&nbsp;";
                $resposta .= "</TD>";
                $resposta .= "<TD>";
                 $resposta .= ($i == pg_num_rows($res) - 1) ? "<input type='text' name='previsao_pagamento_conferencia_$numero' id='previsao_pagamento_conferencia' size='12' value='$previsao_pagamento' class='frm' rel='data'>" : "&nbsp;";
                $resposta .= "</TD>";
                $resposta .= "<TD>";
                    # HD 258856
                    $sqle = "SELECT extrato_tipo_nota, descricao
                            FROM tbl_extrato_tipo_nota
                            WHERE fabrica = $login_fabrica ";
                    $rese = pg_query($con,$sqle);
                    if(pg_num_rows($rese) > 0){
                        for($j =0;$j<pg_num_rows($rese);$j++) {
                            $option.="<option value=".pg_fetch_result($rese,$j,'extrato_tipo_nota').">".pg_fetch_result($rese,$j,'descricao')."</option>";
                        }
                    }
                    $resposta .= ($i == pg_num_rows($res) - 1) ? "<select name='extrato_tipo_nota_$numero'>$option</select>" : "&nbsp;";
                $resposta .= "</TD>";
                $resposta .= "<TD>";
                    $resposta .= ($i == pg_num_rows($res) - 1) ? "<input type='text' name='serie_$numero' id='serie' size='12' maxlength='10' value='' class='frm' >" : "&nbsp;";
                $resposta .= "</TD>";
                $resposta .= "<TD>";
                    $resposta .= ($i == pg_num_rows($res) - 1) ? "1<input type='radio' name='estabelecimento_$numero' id='estabelecimento' size='12' value='1' checked class='frm' > 22<input type='radio' name='estabelecimento_$numero' id='estabelecimento' size='12' value='22' class='frm' >" : "&nbsp;";
                $resposta .= "</TD>";
                $resposta .= "<TD>";
                    $resposta .= ($i == pg_num_rows($res) - 1) ? "$tipo_conta" : "";
                $resposta .= "&nbsp;</TD>";

                $resposta .= $i == pg_num_rows($res) - 1 ? "<td><input type='button' name='apagar' onclick='javascript: apagarExtrato(\"{$codigo_agrupado}\");' value=' Apagar ' /></td>" : "<td>&nbsp;</td>"; 
			$resposta .= "</TR>";
            

		} 
        $resposta .= "</table> ";
		echo "ok|$numero|$resposta";
	}else{
		echo "nao|Nenhum extrato encontrado";
	}
	exit;
}

if(!empty($btn_acao) and $btn_acao=='Gravar'){
	
	$total_numero = $_POST['total_numero'];
	$erro_codigo = array();
	$erro_numero = 0;

	for($i =1;$i<=$total_numero;$i++) {
		$msg_erro_item = "";
		$codigo_agrupado                = trim($_POST['codigo_agrupado_'.$i]);
		$nf_conferencia                 = trim($_POST['nf_conferencia_'.$i]);
		$valor_nf                       = trim($_POST['valor_nf_'.$i]);
		$valor_conferencia_a_pagar      = trim($_POST['valor_conferencia_a_pagar_'.$i]);
		$data_nf_conferencia            = trim($_POST['data_nf_conferencia_'.$i]);
		$previsao_pagamento_conferencia = trim($_POST['previsao_pagamento_conferencia_'.$i]);
		$extrato_tipo_nota              = trim($_POST['extrato_tipo_nota_'.$i]);
		$serie                          = trim($_POST['serie_'.$i]);
		$estabelecimento                = trim($_POST['estabelecimento_'.$i]);

        if(!empty($codigo_agrupado)){
            if(strlen($nf_conferencia)>0)            $xnf_conferencia = "'".$nf_conferencia."'";
            else                                     $msg_erro_item = "Informe o número de nota fiscal do codigo $codigo_agrupado<br>";

            if(strlen($caixa_conferencia)>0)         $xcaixa_conferencia = "'".$caixa_conferencia."'";
            else                                     $xcaixa_conferencia = "null";

            if(!empty($data_nf_conferencia))       
                $xdata_nf_conferencia = "'".formata_data($data_nf_conferencia)."'";
            else
                $msg_erro_item = "Informe a data da nota do codigo $codigo_agrupado<br>";

            if(strlen($previsao_pagamento_conferencia)>0) $xprevisao_pagamento = "'".formata_data($previsao_pagamento_conferencia)."'";
            else{
                #$msg_erro_item .= "Informe previsão de pagamento do codigo $codigo_agrupado<br>";
                $xprevisao_pagamento = "null";
            }

            if(!empty($extrato_tipo_nota)) {
                $sql = "SELECT  contato_estado 
                        FROM tbl_posto_fabrica
                        JOIN tbl_extrato   USING(posto,fabrica)
                        JOin tbl_extrato_agrupado USING(extrato)
                        WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                        AND  tbl_extrato_agrupado.aprovado IS NOT NULL
                        AND   codigo='$codigo_agrupado'";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res) > 0){
                    $contato_estado = pg_fetch_result($res,0,'contato_estado');

                    if(strlen(trim($contato_estado)) > 0) {
                        $sql = "SELECT cfop,codigo_item
                                FROM tbl_extrato_tipo_nota_excecao
                                WHERE extrato_tipo_nota = $extrato_tipo_nota
                                AND   estado = '$contato_estado'";
                        $res = pg_query($con,$sql);
                        if(pg_num_rows($res) > 0){
                            $cfop        = pg_fetch_result($res,0,'cfop');
                            $codigo_item = pg_fetch_result($res,0,'codigo_item');
                        }else{
                            $sql = "SELECT cfop,codigo_item
                                FROM tbl_extrato_tipo_nota
                                WHERE extrato_tipo_nota = $extrato_tipo_nota";
                            $res = pg_query($con,$sql);
                            if(pg_num_rows($res) > 0){
                                $cfop        = pg_fetch_result($res,0,'cfop');
                                $codigo_item = pg_fetch_result($res,0,'codigo_item');
                            }
                        }
                    }else{
                        $sql = "SELECT cfop,codigo_item
                                FROM tbl_extrato_tipo_nota
                                WHERE extrato_tipo_nota = $extrato_tipo_nota";
                        $res = pg_query($con,$sql);
                        if(pg_num_rows($res) > 0){
                            $cfop        = pg_fetch_result($res,0,'cfop');
                            $codigo_item = pg_fetch_result($res,0,'codigo_item');
                        }
                    }
                }
            }

            if(strlen($codigo_agrupado) > 0 and empty($msg_erro_item)) {
                $sql = " SELECT data_conferencia
                        FROM tbl_extrato_conferencia 
                        JOIN tbl_extrato_agrupado USING(extrato)
                        WHERE codigo='$codigo_agrupado'
                        AND   cancelada IS NOT TRUE
                        AND  tbl_extrato_agrupado.aprovado IS NOT NULL
                        ORDER BY data_conferencia DESC LIMIT 1";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res) > 0){
                    $data_conferencia = pg_fetch_result($res,0,data_conferencia);
                }

                $res = pg_query ($con,"BEGIN TRANSACTION");
                
                if(strlen($msg_erro_item)==0){
                    
                    $sql ="UPDATE tbl_extrato_conferencia SET 
                                        nota_fiscal          = $xnf_conferencia           ,
                                        data_nf              = $xdata_nf_conferencia      ,
                                        previsao_pagamento   = $xprevisao_pagamento       ,
                                        valor_nf             = $valor_nf,
                                        valor_nf_a_pagar     = $valor_nf,
                                        data_lancamento_nota = CURRENT_TIMESTAMP,
                                        cfop                 = '$cfop',
                                        codigo_item          = '$codigo_item',
                                        serie                = '$serie',
                                        estabelecimento      = '$estabelecimento'
                            FROM tbl_extrato_agrupado
                            WHERE tbl_extrato_conferencia.extrato = tbl_extrato_agrupado.extrato
                            AND  codigo='$codigo_agrupado'
                            AND  tbl_extrato_agrupado.aprovado IS NOT NULL
                            AND  cancelada IS NOT TRUE";
                    //echo nl2br($sql);
                    $res = @pg_query ($con,$sql);
                    $msg_erro_item = pg_errormessage($con);
                    
                }
                if (strlen ($msg_erro_item) == 0) {
                    $res = @pg_query ($con,"COMMIT TRANSACTION");
                }else{
                    $res = pg_query ($con,"ROLLBACK TRANSACTION");

                    array_push($erro_codigo,$codigo_agrupado);
                    $erro_numero +=1;
                    $msg_erro .= $msg_erro_item;
                }
            }else{
                
                array_push($erro_codigo,$codigo_agrupado);
                $erro_numero +=1;
                $msg_erro .= $msg_erro_item;
            }

            if(strlen($msg_erro)==0){
                //system("/www/cgi-bin/britania/exporta-extrato-tipo-nota.pl agrupado:codigo_agrupado",$ret);
                system("php exporta_extrato_tipo_nota.php agrupado codigo_agrupado",$ret);
                if($ret == 0)
                    $msg = 'Gravado com Sucesso!';
                else
                    $msg = 'Erro ao enviar arquivo via FTP!';
            }
        }
    }
}

$layout_menu = "financeiro";
$title = "GRAVA DADOS DA NOTA FISCAL DO POSTO";
include "cabecalho.php";

if(!empty($btn_acao) and $btn_acao=='Gravar'){

	echo "<script>
            window.open('nota_fiscal_pagamento_britania_print.php?total=menor','','height=600, width=750, top=20, left=20, scrollbars=yes');
		    window.open('nota_fiscal_pagamento_britania_print.php?total=maior','','height=600, width=750, top=20, left=20, scrollbars=yes');
	        window.open('nota_fiscal_pagamento_britania_print.php?total=menor&tipo=p','','height=600, width=750, top=20, left=20, scrollbars=yes');
		    window.open('nota_fiscal_pagamento_britania_print.php?total=maior&tipo=p','','height=600, width=750, top=20, left=20, scrollbars=yes');
	        window.open('nota_fiscal_pagamento_britania_print.php?total=menor&tipo=f','','height=600, width=750, top=20, left=20, scrollbars=yes');
		    window.open('nota_fiscal_pagamento_britania_print.php?total=maior&tipo=f','','height=600, width=750, top=20, left=20, scrollbars=yes');
          </script>";

    $arquivo = "xls/integracao_ems.txt";
    if(file_exists($arquivo)){
        //system("php exporta_extrato_tipo_nota_download.php ");
        echo "<div style='margin: 20px; text-align: center;'><a href='exporta_extrato_tipo_nota_download.php' target='_blank'>Download do Arquivo</a> </div>";

        echo "<script>";
            echo "window.open('exporta_extrato_tipo_nota_download.php'); ";
        echo "</script>";
    }

}
?>
<script type="text/javascript" src="../js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>

<style type="text/css">


.table_obs2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.table_line3 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #FFFFBB;
}

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


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{
	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>

<script language="JavaScript">

$(function(){
	$("input[rel=data]").maskedinput("99/99/9999");
	<?if ($erro_codigo=='0' or empty($erro_codigo)){ ?>

	$('#botao').attr('disabled','disabled');
	<?}?>

	<?php if(!empty($msg)){ ?>
		setTimeout("$('.sucesso').hide()",5000);
	<?}?>
})

var numero = 0;

function consulta(tipo_pesquisa){
	var codigo_agrupado = $("#codigo").val();
	if (codigo_agrupado.length > 0) {
		$.ajax({
			type: "GET",
			url: "<?=$PHP_SELF?>",
			data:"codigo_agrupado="+codigo_agrupado+"&numero="+numero+"&tipo_pesquisa="+tipo_pesquisa,
			beforeSend: function(){
				$("#loading").html("Aguarde...<br/><img src='imagens/ajax-loader.gif' border=0 />");
			},
			complete:function(resposta){
				var resposta= resposta.responseText.split('|');
				if(resposta[0] == "ok") {
                    $("#extratos").css('display','block');
					//$("#extratos").append(resposta[1]);
                    $("#dados_extrato").append(resposta[2]);
					$("input[rel=data]").unmask("99/99/9999");
					$("input[rel=data]").maskedinput("99/99/9999");
					$('#botao').removeAttr("disabled"); 

                    $('#total_numero').val(resposta[1]); 
				}else{
					alert(resposta[1]);
				}
				numero++;
				$("#loading").html("");
				
			}
		});
	}else{
		alert("Informe o número de código para consultar");
	}
}

function apagarExtrato(codigo_agrupado){

	if (codigo_agrupado.length > 0) {
		$.ajax({
			type: "GET",
			url: "<?=$PHP_SELF?>",
			data:"codigo_agrupado="+codigo_agrupado+"&ajaxApagar=apagar",
			beforeSend: function(){
				$(this).val("Aguarde!");
			},
			complete:function(resposta){
				var resposta= resposta.responseText.split('|');
				if(resposta[0] == "ok") {
                    $("#tr_"+codigo_agrupado).parent().css('display','none');        
				}else{
					alert(resposta[0]);
				}

                $(this).val(" Apagar ");
			}
		});
	}
}
</script>
<?php $display = empty($msg_erro) ? " display: none; " : " display: block; ";?>
<form action='<?=$PHP_SELF?>' method='post' style='text-align: center'>
<div id='extratos' style='text-align: center; margin: 20px auto; <?php echo $display; ?> '>
            <span id="loading"></span>
                <?
                if(count($erro_codigo)>0 and is_array($erro_codigo)){
                    $erro_numero = count($erro_codigo);
                    //print_r($erro_codigo);
                    foreach($erro_codigo as $erro_numeros => $codigo_erro) {
                    $erro_numero = ++$erro_numeros;
                    $sql = "SELECT  tbl_extrato_conferencia.extrato_conferencia                        AS extrato_conferencia,
                                        tbl_extrato_conferencia.data_conferencia                           AS data_conferencia,
                                        to_char(tbl_extrato_conferencia.data_conferencia,'DD/MM/YYYY')     AS data,
                                        tbl_extrato_conferencia.nota_fiscal                                AS nota_fiscal,
                                        to_char(tbl_extrato_conferencia.data_nf,'DD/MM/YYYY')              AS data_nf,
                                        tbl_extrato_conferencia.valor_nf                                   AS valor_nf,
                                        tbl_extrato_conferencia.valor_nf_a_pagar                           AS valor_nf_a_pagar,
                                        tbl_extrato_conferencia.caixa                                      AS caixa,
                                        to_char(tbl_extrato_conferencia.previsao_pagamento,'DD/MM/YYYY')   AS previsao_pagamento,
                                        tbl_admin.login                                                    AS login,
                                        tbl_extrato_conferencia.extrato                                   ,
                                        tbl_posto.nome                                                     AS posto_nome,
                                        tbl_posto.posto                                                    AS posto,
                                        tbl_posto_fabrica.codigo_posto                                     AS codigo_posto,
                                        tbl_extrato.valor_agrupado,
                                        tbl_posto_fabrica.tipo_conta
                                FROM tbl_extrato_agrupado
                                JOIN tbl_extrato_conferencia   USING(extrato)
                                JOIN tbl_extrato  USING(extrato)
                                JOIN tbl_posto  USING(posto)
                                JOIN tbl_posto_fabrica  on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
                                JOIN tbl_admin ON tbl_admin.admin = tbl_extrato_conferencia.admin
                                WHERE  cancelada IS NOT TRUE
                                AND    tbl_extrato_agrupado.codigo ='$codigo_erro'
                                AND  tbl_extrato_agrupado.aprovado IS NOT NULL
                                 ";/* AND    nota_fiscal IS NULL */
                                // echo nl2br($sql);
                    $res = pg_query($con,$sql);
                    if(pg_num_rows($res) > 0){

                        $sqlt = "SELECT sum(tbl_extrato_conferencia_item.mao_de_obra)  as total
                                                FROM tbl_extrato
                                                JOIN tbl_extrato_agrupado USING(extrato)
                                                JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
                                                JOIN tbl_extrato_conferencia_item USING(extrato_conferencia)
                                                WHERE tbl_extrato_agrupado.codigo ='$codigo_agrupado'
                                                AND   tbl_extrato.fabrica = $login_fabrica
                                                AND  tbl_extrato_agrupado.aprovado IS NOT NULL
                                                and   cancelada IS NOT TRUE ";
                        $rest = pg_query($con,$sqlt);
                        $total = pg_fetch_result($rest,0,total);

                        $total_avulso = 0;
                        $sql_av = " SELECT
                            extrato,
                            historico,
                            valor,
                            tbl_extrato_lancamento.admin,
                            debito_credito,
                            lancamento
                        FROM tbl_extrato_lancamento
                        JOIN tbl_extrato_agrupado USING(extrato)
                        WHERE tbl_extrato_agrupado.codigo ='$codigo_agrupado'
                        AND fabrica = $login_fabrica
                        AND  tbl_extrato_agrupado.aprovado IS NOT NULL
                        AND (tbl_extrato_lancamento.admin IS NOT NULL OR lancamento in (103,104))";

                        $res_av = pg_query ($con,$sql_av);

                        if(pg_num_rows($res_av) > 0){

                            for($i=0; $i < pg_num_rows($res_av); $i++){
                                $extrato         = trim(pg_fetch_result($res_av, $i, extrato));
                                $historico       = trim(pg_fetch_result($res_av, $i, historico));
                                $valor           = trim(pg_fetch_result($res_av, $i, valor));
                                $debito_credito  = trim(pg_fetch_result($res_av, $i, debito_credito));
                                $lancamento      = trim(pg_fetch_result($res_av, $i, lancamento));
                                
                                if($debito_credito == 'D'){ 
                                    if ($lancamento == 78 AND $valor>0){
                                        $valor = $valor * -1;
                                    }
                                }

                                $total_avulso = $valor + $total_avulso;
                            }
                        }else{
                            $total_avulso = 0 ;
                        }
                        
                        $total += $total_avulso;

                        if($total < 0) {
                            $total = 0 ;
                        }

                        $resposta =  "<table width='800' border='0' align='center' cellspacing='1' cellpadding='4' class='tabela' style='margin: 5px 0 0 0'>";
                            $resposta .= "<tr class='titulo_tabela'>";
                                $resposta .= "<td colspan='100%'>conferência</td>";
                            $resposta .= "</tr>";
                            $resposta .= "<tr class='titulo_coluna'>";
                                $resposta .= "<td>Cod. Posto</td>";
                                $resposta .= "<td>Nome do Posto</td>";
                                $resposta .= "<td>Caixa Arq.</td>";
                                $resposta .= "<td>Valor NF</td>";
                                $resposta .= "<td>Nota Fiscal</td>";
                                $resposta .= "<td>Data NF</td>";
                                $resposta .= "<td>Previsão de Pagamento</td>";
                                $resposta .= "<td>Tipo Nota</td>";
                                $resposta .= "<td>Série</td>";
                                $resposta .= "<td>Estabelecimento</td>";
                                $resposta .= "<td>Tipo Conta</td>";
                                $resposta .= "<td>Ação</td>";
                            $resposta .= "</tr>";

                            for($i =0;$i<pg_num_rows($res);$i++) {
                                $extrato_conferencia= pg_fetch_result($res,$i,extrato_conferencia);
                                $data               = pg_fetch_result($res,$i,data);
                                $nota_fiscal_posto  = pg_fetch_result($res,$i,nota_fiscal);
                                $data_nf            = pg_fetch_result($res,$i,data_nf);
                                $valor_nf           = pg_fetch_result($res,$i,valor_nf);
                                $caixa              = pg_fetch_result($res,$i,caixa);
                                $previsao_pagamento = pg_fetch_result($res,$i,previsao_pagamento);
                                $admin              = pg_fetch_result($res,$i,login);
                                $extrato            = pg_fetch_result($res,$i,extrato);
                                $posto_nome         = pg_fetch_result($res,$i,posto_nome);
                                $posto              = pg_fetch_result($res,$i,posto);
                                $codigo_posto       = pg_fetch_result($res,$i,codigo_posto);
                                $valor_nf           = number_format($valor_nf,2,",",".");
                                
                                $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
                                $checked1 = (empty($estabelecimento) or $estabelecimento == 1) ?" CHECKED ":"";
                                $checked22 = ($estabelecimento == 22) ?" CHECKED ":"";

                                $resposta .= "<TR bgcolor='$cor' id='tr_{$codigo_agrupado}'>";
                                    $resposta .= "<input type='hidden' name='extrato_$erro_numero' value='$extrato'>";
                                    $resposta .= "<input type='hidden' name='posto_$erro_numero'   value='$posto'>";
                                    $resposta .= "<input type='hidden' name='codigo_agrupado_$erro_numero'   value='$codigo_erro'>";
                                    $resposta .= "<input type='hidden' name='extrato_conferencia_$erro_numero'   value='$extrato_conferencia'>";
                                    $resposta .= "<TD>$codigo_posto</TD>";
                                    $resposta .= "<TD nowrap>$posto_nome</TD>";
                                    //$resposta .= "<TD>$extrato</TD>";
                                    $resposta .= "<TD>";
                                    $resposta .= "$caixa";
                                    $resposta .= "</TD>";
                                    $resposta .= "<TD>";
                                    $resposta .= number_format($total,2,",",".");
                                    $resposta .= ($i == pg_num_rows($res) - 1) ?"<input type='hidden' class='frm' name='valor_nf_$erro_numero' size='12' value='$total'>" : "&nbsp;";
                                    $resposta .= "</TD>";
                                    $resposta .= "<TD >";
                                    $resposta .= ($i == pg_num_rows($res) - 1) ?"<input type='text' class='frm' name='nf_conferencia_$erro_numero' size='12' value='$nota_fiscal_posto'>" : "&nbsp;";
                                    $resposta .= "</TD>";
                                    $resposta .= "<TD>";
                                    $resposta .= ($i == pg_num_rows($res) - 1) ? "<input type='text' class='frm' name='data_nf_conferencia_$erro_numero' size='12' value='$data_nf' class='input' rel='data'>" : "&nbsp;";
                                    $resposta .= "</TD>";
                                    $resposta .= "<TD>";
                                    $resposta .= ($i == pg_num_rows($res) - 1) ? "<input type='text' class='frm' name='previsao_pagamento_conferencia_$erro_numero' id='previsao_pagamento_conferencia' size='12' value='$previsao_pagamento' class='input' rel='data'>" : "&nbsp;";
                                    $resposta .= "</TD>";
                                    $resposta .= "<TD>";
                                    # HD 258856
                                    $sqle = "SELECT extrato_tipo_nota, descricao
                                            FROM tbl_extrato_tipo_nota
                                            WHERE fabrica = $login_fabrica ";
                                    $rese = pg_query($con,$sqle);
                                    if(pg_num_rows($rese) > 0){
                                        for($j =0;$j<pg_num_rows($rese);$j++) {
                                            $option.="<option value=".pg_fetch_result($rese,$j,'extrato_tipo_nota').">".pg_fetch_result($rese,$j,'descricao')."</option>";
                                        }
                                    }
                                    $resposta .= ($i == pg_num_rows($res) - 1) ? "<select name='extrato_tipo_nota_$erro_numero' class='frm'  class='frm'>$option</select>" : "&nbsp;";
                                    $resposta .= "</TD>";
                                    $resposta .= "<TD>";
                                    $resposta .= ($i == pg_num_rows($res) - 1) ? "<input type='text' name='serie_$erro_numero' size='12' value='$serie' class='input frm' maxlength='10'>" : "&nbsp;";
                                    $resposta .= "</TD>";
                                    $resposta .= "<TD>";
                                    $resposta .= ($i == pg_num_rows($res) - 1) ? "1<input type='radio' name='estabelecimento_$erro_numero' size='12' value='1' class='input' $checked1 > 22<input type='radio' name='estabelecimento_$erro_numero' size='12' value='22' class='input' $checked22>" : "&nbsp;";
                                    $resposta .= "</TD>";

                                    $resposta .= "<TD>";
                                        $resposta .= ($i == pg_num_rows($res) - 1) ? "$tipo_conta" : "";
                                    $resposta .= "&nbsp;</TD>";

                                    $resposta .= $i == pg_num_rows($res) - 1 ? "<td><input type='button' name='apagar' onclick='javascript: apagarExtrato(\"{$codigo_agrupado}\");' value=' Apagar ' /></td>" : "<td>&nbsp;</td>"; 

                                $resposta .= "</TR>";
                            } 
                            $resposta .= "</table>";
                        echo "$resposta";
                    }
                    }
                }
                ?>
        <div id='dados_extrato'></div>
        <br /><br />
</div>

<table width="700" border='0' align="center" class="formulario" cellspacing='1' cellpadding='1'>
    <?php if(strlen($msg_erro)>0){ ?>
        <tr class="msg_erro"><td colspan='3'><?php echo $msg_erro; ?></td></tr>
    <?php } ?>
    <?php if(!empty($msg)){ ?>
        <tr class="sucesso"><td colspan='3'><?php echo $msg; ?></td></tr>
    <?php } ?>
    <tr class="titulo_tabela"><td colspan='3'>Parâmetros de Pesquisa</td></tr>
    <tr>
        <td width='255px'>&nbsp;</td>
        <td width='*'>&nbsp;</td>
        <td width='100px'>&nbsp;</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td align='left'>
            Digite o Código<br />
            <input type='text' name='codigo_agrupado' id='codigo' value='<? echo $codigo_agrupado; ?>' size='20' class='frm' />
        </td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td align='center' colspan='3'>
            <div><?php echo $options;?></div><br />
            <input type='button' value='Consultar' onclick="consulta('consulta');" />
            <input type='button' value='Alterar' onclick="consulta('alterar');" />
            <br /><br />
            <input type='submit' name='btn_acao' value='Gravar' id='botao' />
            <br /><br />
        </td> 
    </tr>
</table>
        
        <input type='hidden' name='total_numero' id='total_numero' value='<?php echo $erro_numero; ?>' />
    </form>
<br /><br />
<?php  include "rodape.php"; ?>
