<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

if(strlen($btn_acao)>0){
	$mes = $_POST['mes'];
	$ano = $_POST['ano'];
	if (strlen($mes) > 0 and strlen($ano) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}else{
		$msg_erro = "Selecione o mês e o ano.";
	}
}
$layout_menu = "gerencia";
$title = "RELATÓRIO DE ORDENS DE SERVIÇO LANÇADAS DO TIPO CORTESIA";

include "cabecalho.php";
$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
?>
<script language="JavaScript">

function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
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



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
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

.espaco{
	padding-left:200px;
}
/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

</style>

<? include "javascript_pesquisas.php" ?>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>

<br>

<FORM name="frm_pesquisa" METHOD="POST" ACTION="<?$PHP_SELF?>">
<TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2" class='formulario'>
<?php if(strlen($msg_erro)>0){ ?>
	<tr class="msg_erro">
		<td colspan="5"><?php echo $msg_erro; ?> </td>
	</tr>
<?php } ?>
<TR class='titulo_tabela'>
	<TD colspan="5">Parâmetros de Pesquisa</TD>
</TR>
<tr >
		<td width='20%' class='espaco'> Mês</td>
		<td width='10%'> Ano</td>
			<TD  colspan='2' >Tipos OS Cortesia</TD>
	</tr>
	<tr  align='left'>
		<td width='20%' class='espaco'>
			<select name="mes" size="1" class="frm">
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
		<td width='10%' >
			<select name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 2003 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
		</td>

	<TD >
		<select name='tipo_os_cortesia' class='frm'>
			<option value='' selected></option>
			<option value='Todas' <? if($tipo_os_cortesia=='Todas') echo 'selected'; ?>>Todas</option>
			<option value='Garantia' <? if($tipo_os_cortesia=='Garantia') echo 'selected'; ?>>Garantia</option>
			<option value='Sem Nota Fiscal' <? if($tipo_os_cortesia=='Sem Nota Fiscal') echo 'selected'; ?>>Sem Nota Fiscal</option>
			<option value='Fora da Garantia' <? if($tipo_os_cortesia=='Fora da Garantia') echo 'selected'; ?>>Fora da Garantia</option>
			<option value='Transformação' <? if($tipo_os_cortesia=='Transformação') echo 'selected'; ?>>Transformação</option>
			<option value='Promotor' <? if($tipo_os_cortesia=='Promotor') echo 'selected'; ?>>Promotor</option>
			<option value='Mau uso' <? if($tipo_os_cortesia=='Mau uso') echo 'selected'; ?>>Mau uso</option>
			<option value='Devolução de valor' <? if($tipo_os_cortesia=='Devolução de valor') echo 'selected'; ?>>Devolução de valor</option>
		</select>
	</TD>
</TR>
<TR>
	<TD colspan="5"  style="text-align: center; padding:10px 0 10px 0;"><input type="submit" name="btn_acao" value="Pesquisar"></TD>
</TR>
</TABLE>
</FORM>
<BR>
<?
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0 and strlen($msg_erro)==0){

	$tipo_os_cortesia = $_POST['tipo_os_cortesia'];
	$cond_1 = " 1=1 ";
	if(strlen($tipo_os_cortesia)>0 and $tipo_os_cortesia<>'Todas'){
		$cond_1 = " tbl_os.tipo_os_cortesia = '".$tipo_os_cortesia."' ";
	}
	if($login_fabrica == 1){

		$campo_peca = " tbl_os.mao_de_obra, ";
		$campo_mao_de_obra = " tbl_os.pecas, ";
	}
	/*echo $data_inicial . "-" . $data_final;
echo "<BR>".$tipo_os_cortesia;*/

	$sql = "SELECT 	tbl_posto_fabrica.codigo_posto                                  ,
					tbl_posto.nome                                                  ,
					tbl_os.os                                                       ,
					tbl_os.sua_os                                                   ,
					tbl_os.consumidor_nome                                          ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') as data_abertura     ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento ,
					tbl_produto.referencia as produto_referencia                    ,
					tbl_produto.descricao as produto_descricao                      ,
					tbl_os.tipo_os_cortesia                                         , ";
					if($login_fabrica == 1){
						$sql .= $campo_peca;
						$sql .= $campo_mao_de_obra;
					}
					
	$sql .=	" tbl_admin.login
			FROM tbl_os
			JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto  
			AND  tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
			JOIN tbl_admin on tbl_os.admin = tbl_admin.admin and tbl_admin.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.cortesia IS TRUE 
			AND tbl_os.excluida is false
			AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
			AND $cond_1 
			ORDER BY tbl_posto_fabrica.codigo_posto";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
	 echo "<center><font face='verdana' size='1'> Resultado(s) encontrado(s) ". pg_numrows($res) ."</font></center>";
		echo "<table border='0' cellpadding='4' cellspacing='1' width='700' align='center' class='tabela'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td align='center' nowrap>OS</td>";
		echo "<td align='center' nowrap>Posto</td>";
		echo "<td align='center' >Consumidor</td>";
		echo "<td align='center'>Abertura</td>";
		echo "<td align='center'>Fechamento</td>";
		echo "<td align='center'>Produto</td>";
		if($login_fabrica == 1){
			echo "<td align='center'>Valor Peças</td>";
			echo "<td nowrap align='center'>Mão de Obra</td>";
		}
		echo "<td align='center'>Admin</td>";
		echo "<td align='center'>Tipo</td>";
		echo "</tr>";
		
		for($x=0;pg_numrows($res)>$x;$x++){
			$os                 = pg_result($res,$x,os);
			$sua_os             = pg_result($res,$x,sua_os);
			$codigo_posto       = pg_result($res,$x,codigo_posto);
			$nome_posto         = pg_result($res,$x,nome);
			$nome_posto         = substr($nome_posto,0,20);
			$data_abertura      = pg_result($res,$x,data_abertura);
			$data_fechamento    = pg_result($res,$x,data_fechamento);
			$produto_referencia = pg_result($res,$x,produto_referencia);
			$produto_referencia = substr($produto_referencia,0,8);
			$produto_descricao  = pg_result($res,$x,produto_descricao);
			$consumidor_nome    = pg_result($res,$x,consumidor_nome);
			$consumidor_nome    = substr($consumidor_nome,0,15);
			$tipo_os_cortesia   = pg_result($res,$x,tipo_os_cortesia);
			$admin_digitou      = pg_result($res,$x,login);
			if($login_fabrica == 1){
				$valor_pecas = pg_result($res,$x,pecas);
				$mao_de_obra = pg_result($res,$x,mao_de_obra);
			}

			$cor = "#F7F5F0"; 
			if ($x % 2 == 0) $cor = '#F1F4FA';

			echo "<tr bgcolor='$cor'>";
			echo "<td align='center' nowrap><a href='os_press.php?os=$os' target='blank'>$codigo_posto$sua_os</a></td>";
			echo "<td align='left' nowrap>$nome_posto</td>";
			echo "<td align='left' nowrap>$consumidor_nome</td>";
			echo "<td align='center'>$data_abertura</td>";
			echo "<td align='center'>$data_fechamento</td>";
			echo "<td align='left'>$produto_referencia</td>";
			echo "<td align='center'>$valor_pecas</td>";
			echo "<td align='center'>$mao_de_obra</td>";
			echo "<td align='left'>$admin_digitou</td>";
			echo "<td align='left'>$tipo_os_cortesia</td>";
			echo "</tr>";


		}
		echo "</table>";

	}else{
		echo "Nenhum Resultado Encontrado para esta Pesquisa";
	}
	
	
}
?>
<? include "rodape.php" ?>