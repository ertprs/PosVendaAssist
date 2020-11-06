<?php

include '../dbconfig.php';
//echo "aquia".$ip.$programa; exit;
include '../includes/dbconnect-inc.php';
include '../distrib/autentica_usuario.php';
include 'libs/functions.php';
?>
<script type="text/javascript" src="../js/jquery-latest.pack.js"></script>
<link rel="stylesheet" type="text/css" href="../js/datePicker.v1.css" title="default" media="screen" />
<script type="text/javascript" src="../js/datePicker.v1.js"></script>
<script type="text/javascript" src="../js/jquery.maskedinput.js"></script>

<script type="text/javascript">
	$(document).ready(init);
	function init(){
		$.datePicker.setDateFormat('dmy', '/');
		$.datePicker.setLanguageStrings(
			['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
			['Janeiro', 'Fevereiro', 'Março', 'Abril', 'maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
			{p:'Anterior', n:'Próximo', c:'Fechar', b:'Abrir Calendário'}
		);
		$('input.date-picker').datePicker({startDate:'05/03/2006'});
	}
</script>
<?
if ($_POST['btn_voltar']=="Voltar Menu Distrib"){
    header ("Location: ../distrib/index.php");
    exit;
}

$emissao = $_POST['emissao'];
$nota_fiscal = $_POST['nota_fiscal'];
if(strlen($emissao)==0){
	$sqlhoje = "SELECT now()::date as hoje";
	$reshoje = pg_query($con, $sqlhoje);
	$emissao = pg_result($reshoje,0,hoje);
}else{
	$emissao = substr($emissao,6,4)."-".substr($emissao,3,2)."-".substr($emissao,0,2);
}
//echo $emissao;
?>
<html>
    <head>
        <title>Protótipo NFe</title>
        <style>
            body {
                font: normal 12px arial;
            }
			acronym {
				cursor: help;
			}
        </style>
        <script type="text/javascript">

			$().ready(function(){
				$( "#emissao" ).datePicker({startDate : "01/01/2000"});
				$( "#emissao" ).maskedinput("99/99/9999");
			});

			function tecla(event) {

				var tecla = event.keyCode ? event.keyCode : event.which ? event.which : event.charCode;
				if (tecla == 13) return false;
				return true;

			}

			function imprimir_nfe(chave) {

            	//var ambiente = 'homologacao';
            	var ambiente = 'producao';

            	window.open('http://posvenda.telecontrol.com.br/assist/nfephp2/arquivos/'+ambiente+'/pdf/'+chave+'.pdf');
            	
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
	.msg_erro{
	    background-color:#FF0000;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	    margin: 0 auto;
	    width: 900px;
	    padding: 5px 0;
	}
	.formulario{
	    background-color:#D9E2EF;
	    font:11px Arial;
	    text-align:left;
	    margin: 0 auto;
	    width: 900px;
	    border: 1px solid #596d9b ;
	}
	table.tabela tr td{
	    font-family: verdana;
	    font-size: 11px;
	    border-collapse: collapse;
	    border:1px solid #596d9b;
	}

	.sucesso{
	    background-color:#008000;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    text-align:center;
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
	
	.subtitulo{
	    background-color: #7092BE;
	    font:bold 11px Arial;
	    color: #FFFFFF;
	}
</style>

    </head>
    <body>
        <form name="frm_nfe" action="gerencia_nfe_reimpressao.php" method="post" >
            <input type="hidden" name="operacao" id="operacao" value="enviar" />
            <input type="hidden" name="motivo" id="motivo" />
            <table width="700" cellpadding="5" cellspacing="0" class="formulario">
                <tr class="titulo_tabela">
                    <th colspan="100%">Gerenciamento de Reimpressão NFe</th>
                </tr>
                <tr>
                    <td><b>Data do Faturamento:</b>
                    <input type="text" name="emissao" id="emissao" class="frm"/></td>
                    <td><b>Nota Fiscal:</b>
                    <input type="text" name="nota_fiscal" id="nota_fiscal" /></td>
                    <td><input type="submit" name="btn_emissao" id="btn_emissao" value="Consultar Emissão" /></td>
                    <td><input type="submit" name="btn_voltar" id="btn_voltar" value="Voltar Menu Distrib" /></td>
                </tr>
            </table>
        </form><?php

		if(strlen($nota_fiscal)>0){
			$sql = "SELECT faturamento,
						   chave_nfe,
						   status_nfe,
						   TO_CHAR(emissao, 'DD/MM/YYYY') as emissao,
						   embarque,
						   nota_fiscal
					FROM tbl_faturamento
					WHERE fabrica      = 10
					  AND distribuidor = 4311
					  AND chave_nfe    IS NOT NULL
					  AND nota_fiscal like '%$nota_fiscal%'
					  and status_nfe = '100'
					ORDER BY tbl_faturamento.nota_fiscal DESC;";
		}else{
			$sql = "SELECT faturamento,
					   chave_nfe,
					   status_nfe,
					   TO_CHAR(emissao, 'DD/MM/YYYY') as emissao,
					   embarque,
					   nota_fiscal
				FROM tbl_faturamento
				WHERE fabrica      = 10
				  AND distribuidor = 4311
				  AND chave_nfe    IS NOT NULL
				  AND emissao = '$emissao' 
				  and status_nfe = '100'
				ORDER BY tbl_faturamento.nota_fiscal DESC;";

		}
        $res = pg_query($con, $sql);
        $tot = pg_num_rows($res);
        //echo $tot; exit;

        if ($tot) {?>
            <br />
            <br />
            <table width="900" align="center" cellpadding="1" cellspacing="1" class="tabela">
                <tr class="titulo_tabela">
                    <td colspan="100%">NFe Geradas</td>
                </tr>
		<tr class="titulo_coluna">
		    <td>Fábrica</td>
                    <td>Chave de Acesso</td>
		    <td>Notal Fiscal</td>
                    <td>Embarque/Data</td>
                    <td>Status NFe</td>
                    <td colspan="100%">Ação</td>
                </tr><?php
				$chave_nfe_todos = "pdftk ";
                for ($i = 0; $i < $tot; $i++) {
                    $cor = $i % 2 ? '#98C7D3' : '#D9E2EF';
                    $faturamento = pg_result($res, $i, 'faturamento');
					$nota_fiscal = pg_result($res, $i, 'nota_fiscal');
                    $dt_emissao  = pg_result($res, $i, 'emissao');
                    $embarque    = pg_result($res, $i, 'embarque');
                    $status_nfe  = pg_result($res, $i, 'status_nfe');
		    $chave_nfe   = pg_result($res, $i, 'chave_nfe');

		    $sqlF = "select tbl_fabrica.nome 
			    	from tbl_faturamento_item 
				join tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca 
				join tbl_fabrica on tbl_peca.fabrica = tbl_fabrica.fabrica 
				where faturamento = $faturamento";
		    $resF = pg_query($con,$sqlF);
		    $fabrica_nome = pg_result($resF,0,0);
					?>
					
		    <tr bgcolor="<?=$cor?>">
			<td><?=$fabrica_nome?></td>
                        <td><?=$chave_nfe?>&nbsp;</td>
			<td align="center"><?=$nota_fiscal?></td>
                        <td align="center" title="<?=$faturamento?>"><?=$embarque?>&nbsp;<?=$dt_emissao?></td>
                        <td align="center"><acronym title="<?=$status = getStatus($status_nfe)?>"><?=substr($status,0,10)?></acronym>&nbsp;</td>
                        <td align="center"><?php
 	                        if ($status_nfe == 100) {?>
                        		<input type="button" onclick="imprimir_nfe('<?=$chave_nfe?>')" value="Imprimir" /><?php
                        	} else {
                        		echo '&nbsp;';
                        	}?>
                        </td>
                    </tr><?php
                }?>
            </table><?php
       }?>
    </body>
</html>
