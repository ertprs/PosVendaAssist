<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';
include 'funcoes.php';

$ajax = $_GET['ajax'];

if ($ajax == 'TRUE') {

    $referencia = $_POST['referencia'];
    $data_ini   = $_POST['data_ini'];
    $data_fim   = $_POST['data_fim'];

    if($login_fabrica == 1){
      $cond_pedidos_status = " AND tbl_pedido.status_pedido NOT IN(1,2,4,5,14) ";
    } else {
      $cond_pedidos_status = " AND tbl_pedido.status_pedido NOT IN(1,2,4,14) ";
    }

    $sql_pedido = "SELECT SUM(tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada) as total,
                          tbl_pedido.pedido,
                          tbl_pedido.seu_pedido,
                          tbl_peca.referencia_fabrica,
                          tbl_peca.referencia,
                          tbl_peca.descricao,
                          TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') as data
                     FROM tbl_pedido
                     JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
                     JOIN tbl_peca        ON tbl_pedido_item.peca   = tbl_peca.peca
                    WHERE tbl_pedido.fabrica  = $login_fabrica
                      AND tbl_pedido.posto    = $login_posto
                      AND tbl_peca.referencia = '$referencia'
                      {$cond_pedidos_status}
                      AND tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada > 0
                      AND tbl_pedido.data BETWEEN '$data_ini' AND '$data_fim'
                    GROUP BY tbl_pedido.pedido,
                             tbl_pedido.seu_pedido,
                             tbl_peca.referencia,
                             tbl_peca.referencia_fabrica,
                             tbl_peca.descricao,
                             data
                    ORDER BY total desc, data;";
    //die(nl2br($sql_pedido));
    $res = @pg_query($con, $sql_pedido);
    $tot = @pg_num_rows($res);

    if ($tot > 0) {
        echo '<table cellpadding="5" cellspacing="1" width="680px" border="0" align="center" class="tabela">';
            echo '<thead><tr class="titulo_coluna">';
            if ($login_fabrica == 171) {
            echo '<th>' . traduz('referencia.fabrica', $con) . '</th>';
            }
				echo '<th>' . traduz('referencia', $con) . '</th>';
				echo '<th>' . traduz('descricao',  $con) . '</th>';
				echo '<th>' . traduz('qtde',       $con) . '</th>';
				echo '<th>' . traduz('data',       $con) . '</th>';
				echo '<th>' . traduz('pedido',     $con) . '</th>';
            echo '</tr></thead>' . chr(10);
            for ($i = 0; $i < $tot; $i++) {
                $cor        = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';
                $pedido     = @pg_fetch_result($res, $i, 'pedido');
                $seu_pedido = @pg_fetch_result($res, $i, 'seu_pedido');
                echo '<tr bgcolor="'.$cor.'">';
                    if ($login_fabrica == 171) {
                    echo '<td align="center">&nbsp;'.@pg_fetch_result($res, $i, 'referencia_fabrica').'</td>';
                    }
                    echo '<td align="center">&nbsp;'.@pg_fetch_result($res, $i, 'referencia').'</td>';
                    echo '<td>&nbsp;'.@pg_fetch_result($res, $i, 'descricao').'</td>';
                    echo '<td align="center">&nbsp;'.@pg_fetch_result($res, $i, 'total').'</td>';
                    echo '<td>'.@pg_fetch_result($res, $i, 'data').'</td>';
					if ($login_fabrica == 1){
						echo '<td align="center"><a href="pedido_blackedecker_finalizado_new.php?pedido='.$pedido.'" target="_blank">'.fnc_so_numeros($seu_pedido).'</a></td>';
					} else {
						echo '<td align="center"><a href="pedido_finalizado.php?pedido='.$pedido.'" target="_blank">'.fnc_so_numeros($pedido).'</a></td>';
					}
                echo '</tr>';
            }
        echo '</table>';
    }

	if ($tot == 0) fecho('nenhum.resultado.encontrado', $con);

    exit;

}

$layout_menu = 'pedido';
$title  	 = traduz(array('consulta','pecas','pendentes'), $con);

include "cabecalho.php";
include "javascript_calendario.php";?>
<style>
    a {
        text-decoration: none;
        color: #000000;
    }
    a:hover {
        text-decoration: underline;
    }
    .titulo_coluna{
           background-color:#596d9b;
           font: bold 11px "Arial";
           color:#FFFFFF;
           text-align:center;
    }

	.titulo_tabela{
           background-color:#596d9b;
           font: bold 14px "Arial";
           color:#FFFFFF;
           text-align:center;
    }

    table.tabela tr td{
           font-family: verdana;
           font-size: 11px;
           border-collapse: collapse;
           border:1px solid #596d9b;
    }
    .formulario{
           background-color:#D9E2EF;
           font:11px Arial;
           text-align:left;
    }
    .msg_erro {
        background: #FF0000;
        color: #FFFFFF;
        font: bold 16px "Arial";
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

.nao_disponivel {
 font: 14px Arial; color: rgb(200, 109, 89);
 background-color: #ffddff;
 border:1px solid #DD4466;
}

.espaco{
	padding:0 0 0 120px;
}
</style>
<script src="js/jquery-ui.min.js" type="text/javascript"></script>
<script type="text/javascript">
	
	var program_self = window.location.pathname;
	
	var traducao = { // Tradução dos textos para o JavasScript
		fechar_pedidos:   '<?=traduz('fechar.pedidos', $con)?>',
		carregando:       '<?=traduz('carregando', $con)?>',
		consulta_pedidos: '<?=traduz(array('consulta','pedidos'), $con)?>',
		informar_parte:   '<?=traduz('informar.toda.parte.informacao.para.realizar.pesquisa', $con)?>'
	}

    function getPedidos(ref,i) {


		if (document.getElementById('conteudo_pai_'+i).style.display == 'none') {

			var data_ini = document.getElementById('data_ini').value;
			var data_fim = document.getElementById('data_fim').value;

			document.getElementById('conteudo_pai_'+i).style.display = '';

			$.post(
				program_self + "?ajax=TRUE",
				{
					referencia: ref,
					data_ini: data_ini,
					data_fim: data_fim
				},
				function(resposta) {
					document.getElementById('conteudo_'+i).innerHTML    = resposta;
					document.getElementById('menu_pedido_'+i).innerHTML = traducao.fechar_pedidos;
				}
			)

		} else {

			document.getElementById('conteudo_pai_'+i).style.display = 'none';
			document.getElementById('conteudo_'+i).innerHTML         = '<br /><b>' + traducao.carregando + '</b><br /><br />';
			document.getElementById('menu_pedido_'+i).innerHTML      = traducao.consulta_pedidos;

	}

}

$(function() {
	$('#data_inicial').datePicker({startDate:'01/01/2000'});
        $('#data_final').datePicker({startDate:'01/01/2000'});
        $("#data_inicial").maskedinput("99/99/9999");
        $("#data_final").maskedinput("99/99/9999");
    });

	function fnc_pesquisa_peca (campo, campo2, tipo) {
		if (tipo == "referencia" ) {
			var xcampo = campo;
		}

		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {
			var url = "";
			url = "peca_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
			janela.retorno = program_self;
			janela.referencia= campo;
			janela.descricao= campo2;
			janela.focus();
		}
		else{
			alert(traducao.informar_parte);
		}
	}

</script>
<body><?php

if (!empty($_POST)) {

    $fnc  = @pg_exec($con,"SELECT TO_CHAR(CURRENT_DATE - INTERVAL '180 days','YYYY/MM/DD');");
    $data_inicial_6_meses = @pg_result ($fnc,0,0);

    $peca   = trim($_POST['peca']);
    $pedido = trim($_POST['pedido']);

    $data_inicial = trim($_POST['data_inicial']);
    $data_final   = trim($_POST['data_final']);

	define('DATA_MINIMA', '01/07/2010');

    if (strlen($peca) > 0) {
        $where_peca = " AND tbl_peca.referencia = '$peca' ";
    }

    if (strlen($pedido) > 0) {
        $where_pedido = " AND tbl_pedido.pedido = $pedido ";
    }

    if (strlen($data_inicial) > 0 && $data_inicial != 'dd/mm/aaaa' && strlen($data_final) > 0 && $data_final != 'dd/mm/aaaa') {

		$data_ini = dateFormat($data_inicial, 'dmy');
		$data_fim = dateFormat($data_final,   'dmy');
        if ($data_ini and $data_fim) {

            if ($data_fim > $data_ini) {

        if($login_fabrica == 42 and strtotime($data_ini) < strtotime($data_inicial_6_meses)){
          $msg_erro .= "O limite para a pesquisa é de 6 meses ";
        }else {
					$where_data = " AND tbl_pedido.data BETWEEN '$data_ini' AND '$data_fim' ";
				}

            } else {

				$msg_erro = traduz('data.invalida', $con);

            }

        } else {

            $msg_erro = traduz('data.invalida', $con);

        }

    } else {

       $msg_erro = traduz('data.invalida', $con);

    }

    $pesquisa = true;

} else {

    $pesquisa = false;

}?>

<br>

<div class='texto_avulso' style="width:700px;">O limite para a pesquisa é de 6 meses</div>
<br>

<?
if(strlen($msg_erro)>0){
	echo '<div class="msg_erro" style="width:700px">'.$msg_erro.'</div>';
}
?>

<form name="frm_peca" method="post" action="<?=$_SERVER['PHP_SELF']?>"><?php
    /*Colocado para pegar a data do POST pois poderia ser mudado e na hora da consulta daria problemas */?>
    <input type="hidden" name="data_ini" id="data_ini" value="<?=$data_ini?>" />
    <input type="hidden" name="data_fim" id="data_fim" value="<?=$data_fim?>" />
    <table cellpadding="5" cellspacing="1" width="700px" border="0" class="formulario" align="center">
		<thead>
			<tr class="titulo_tabela">
				<th colspan="4"><?=traduz('parametros.de.pesquisa', $con)?></th>
			</tr>
        </thead>
		<tbody>
			<tr>
        <td width="100"></td>
				<td>
					<?=traduz('data.inicial', $con)?> <br />
					<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo substr($data_inicial,0,10);?>" onclick="if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
				</td>
				
				<td>
					<?=traduz('data.final', $con)?> <br />
					<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10);?>" onclick="if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
				</td>
			</tr>
			<tr>
				<td width="100"></td>
				<td>
					<?=traduz('referencia', $con)?> <br />
					<input type="text" name="peca" id="peca" size="13" value="<?=$peca?>" />
					<img src='imagens/lupa.png' onclick="javascript: fnc_pesquisa_peca (document.frm_peca.peca,document.frm_peca.descricao,'referencia')" style='cursor:pointer;'>
				</td>
				
				<td>
					<?=traduz('descricao.da.peca', $con)?> <br />
					<input type="text" name="descricao" id="descricao" size="40" value="<?=$descricao?>" />
					<img src='imagens/lupa.png' onclick="javascript: fnc_pesquisa_peca (document.frm_peca.peca,document.frm_peca.descricao,'descricao')" style='cursor:pointer;'>
				</td>
			</tr>
			<tr>
				<td width="100"></td>
				<td colspan='2'>
					<?=traduz('n.pedido', $con)?><br />
					<input type="text" name="pedido" id="pedido" size="13" value="<?=$pedido?>" />
				</td>
			</tr>
			<tr>
				<td colspan="3" align="center">
					<input type="submit" value="<?=traduz('pesquisar', $con)?>" />
          <br>
          <br>
				</td>
			</tr>
        </tbody>
    </table>
</form><?php

if ($pesquisa === TRUE) {

    if($login_fabrica == 1){
      $cond_pedidos_status = " AND tbl_pedido.status_pedido NOT IN(1,2,4,5,14) ";
    } else {
      $cond_pedidos_status = " AND tbl_pedido.status_pedido NOT IN(1,2,4,14) ";
    }

    if (strlen($msg_erro) == 0) {

        $sql = "SELECT SUM(tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada) as total,
                       tbl_pedido_item.peca
                  FROM tbl_pedido_item
                  JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
                  JOIN tbl_peca   ON tbl_pedido_item.peca   = tbl_peca.peca
                 WHERE tbl_pedido.fabrica = $login_fabrica
                   AND tbl_pedido.posto   = $login_posto
                   {$cond_pedidos_status}
                   AND tbl_peca.ativo IS TRUE
                   $where_data
                   $where_peca
                   $where_pedido
                   AND (tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada) > 0
                 GROUP BY tbl_pedido_item.peca
                 ORDER BY total desc;";
        //die(nl2br($sql));
        $res   = @pg_query($con,$sql);
        $total = @pg_num_rows($res);

        if ($total > 0) {?>
            <br />
            <table cellpadding="5" cellspacing="1" width="700px" border="0" class="tabela" align="center">
				<thead>
					<tr class="titulo_coluna">
            <?PHP if ($login_fabrica == 171) {?>
              <th><?=traduz('referência.fábrica', $con)?></th>
            <?PHP }?>
						<th><?=traduz('referencia', $con)?></th>
						<th><?=traduz('descricao',  $con)?></th>
						<th><?=traduz('qtde',       $con)?></th>
						<th nowrap="nowrap"><?=traduz('data.primeira.pendencia.', $con)?></th>
						<th><?=traduz('acao',       $con)?></th>
					</tr>
				</thead>
				<tbody>
<?
                for ($i = 0; $i < $total; $i++) {

                    $cor  = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';
                    $peca = @pg_fetch_result($res, $i, 'peca');

                    $sql_peca = "SELECT tbl_peca.referencia,
                                        tbl_peca.referencia_fabrica,
                                        tbl_peca.descricao,
                                        TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') as data
                                   FROM tbl_pedido_item
                                   JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
                                   JOIN tbl_peca   ON tbl_peca.peca          = tbl_pedido_item.peca
                                  WHERE tbl_pedido_item.peca = $peca
                                    AND (tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada) > 0
                                    AND tbl_pedido.posto     = $login_posto
                                    AND tbl_pedido.status_pedido NOT IN(1,2,4,14)
                                    AND tbl_peca.ativo IS TRUE
                                    $where_data
                                    $where_peca
                                    $where_pedido
                                  ORDER BY tbl_pedido_item.data_item
                                  LIMIT 1;";

                    $res_peca = pg_query($con, $sql_peca);

                    if (@pg_num_rows($res_peca) > 0) {
                        $referencia = @pg_fetch_result($res_peca, 0, 'referencia');
                        $referencia_fabrica = @pg_fetch_result($res_peca, 0, 'referencia_fabrica');
                        $descricao  = @pg_fetch_result($res_peca, 0, 'descricao');
                        $data       = @pg_fetch_result($res_peca, 0, 'data');
                    }

                    echo '<tr bgcolor="'.$cor.'">';
                        if ($login_fabrica == 171) {
                            echo '<td align="center">&nbsp;'.$referencia_fabrica.'</td>';
                          
                        }
                        echo '<td align="center">&nbsp;'.$referencia.'</td>';
                        echo '<td>&nbsp;'.$descricao.'</td>';
                        echo '<td align="center">&nbsp;'.@pg_fetch_result($res, $i, 'total').'</td>';
                        echo '<td align="center">'.$data.'</td>';
                        echo '<td align="center" nowrap="nowrap"><a href="javascript:void(0)" onclick="getPedidos(\''.$referencia.'\', '.$i.')" title="'.
							 traduz(array('consulta','pedidos'), $con) .
							 '" id="menu_pedido_'.$i.'">' .  traduz(array('consultar','pedidos'), $con) . '</a></td>';
                    echo '</tr>';

                    echo '<tr style="display:none" id="conteudo_pai_'.$i.'">';
                        echo '<td align="center" colspan="5" id="conteudo_'.$i.'"><br /><b>' .
							 traduz('carregando', $con) .
							 '...</b><br /><br /></td>';
                    echo '</tr>';

                }?>
            </table><?php

        } else {

			echo '<p>&nbsp;</p><div style="width:700px" class="texto_avulso">' . traduz('nenhum.resultado.encontrado', $con) . '</div>';

        }

    }


}

include "rodape.php";?>

</body>
</html>
