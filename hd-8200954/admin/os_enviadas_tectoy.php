<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

/*PRECISA COLOCAR NA FUNCAO DE EXTRATO*/
	/* tectoy paga metade da mao de obra qdo posto envia PCI para a fábrica*/
	/*
	IF t_fabrica = 6 AND t_posto = 6359 THEN
		UPDATE tbl_os SET
			mao_de_obra = mao_de_obra / 2
		WHERE   tbl_os_extra.os      = tbl_os.os
		AND     tbl_os_extra.extrato = tbl_extrato.extrato
		AND     tbl_extrato.extrato  = t_extrato
		AND     tbl_extrato.fabrica  = t_fabrica
		AND     tbl_os.tipo_os       = 8
		AND     tbl_os.solucao_os    = 9
	END IF;
*/
	/* tectoy paga metade da mao de obra qdo posto envia PCI para a fábrica*/




$autorizar = $_GET["autorizar"];
if(strlen($autorizar)>0){
	$sql = "UPDATE tbl_os set 
								solucao_os = 9,
								tipo_os = 8
					WHERE os=$autorizar 
					AND  fabrica=$login_fabrica";
	$res = pg_exec($con,$sql);
//echo "autorizar $sql";
}

$n_autorizar = $_GET["n_autorizar"];
if(strlen($n_autorizar)>0){
	$sql = "UPDATE tbl_os set 
								solucao_os = 9,
								tipo_os = 9
					WHERE os=$n_autorizar 
					AND  fabrica=$login_fabrica";
	$res = pg_exec($con,$sql);
//echo "nao autorizar $sql";
}



$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$sua_os      = trim (strtoupper ($_POST['sua_os']));
	$n_serie     = trim (strtoupper ($_POST['n_serie']));
	$nota_fiscal = trim (strtoupper ($_POST['nota_fiscal']));
	$solucao     = trim (strtoupper ($_POST['solucao']));
	
	if( strlen($n_serie)>0 and strlen($n_serie)<4){
		$msg_error .= "Entre com pelo menos 4 digítos no número de série";
	}
 	if( strlen($sua_os)>0 and strlen($sua_os)<5){
		$msg_error .= "Entre com pelo menos 5 digítos no número da OS";
	}
	if(strlen($sua_os)==0 AND strlen($n_serie)==0 AND strlen($nota_fiscal)==0 AND strlen($solucao)==0){
		$msg_error .= "Entre com valores na pesquisa";
	}
 
	if(strlen($sua_os)>0 and strlen($sua_os)>4){
		$condicao_1 = " AND tbl_os.sua_os like '%$sua_os%' ";
	}
	if(strlen($n_serie)>0 and strlen($n_serie)>3){
		$condicao_2 = " AND tbl_os.serie like '%$n_serie%' ";
	}
	if(strlen($nota_fiscal)>0){
		$condicao_3 = " AND tbl_os.nota_fiscal like '%$nota_fiscal%' ";
	}

	if(strlen($solucao)>0){
		$condicao_4 = " AND (tbl_os.solucao_os = 9 OR tbl_os.solucao_os = 128)";
	}
}

$layout_menu = "gerencia";
$title = "OS com peças enviadas à fábrica";
include 'cabecalho.php';

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
?>
<style type="text/css">
input { 
background-color: #ededed; 
font: 12px verdana;
color:#363738;
border:1px solid #969696;
}
</style>
<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>

<?

echo "<BR><BR><form name='frm_consulta' method='post' action='$PHP_SELF'>";
echo "<table width='400' border='0' bgcolor='#D9E2EF' align='center' cellpadding='3' cellspacing='3' style='font-family: verdana; font-size: 12px'>";
if (strlen($msg_error) > 0) { 
	$sua_os      = trim (strtoupper ($_POST['sua_os']));
	$n_serie     = trim (strtoupper ($_POST['n_serie']));
	$nota_fiscal = trim (strtoupper ($_POST['nota_fiscal']));

	echo "<div class='error'>";
	echo $msg_error; 
	echo "</div>";
} 
echo "<tr>";
	echo "<td colspan='3' align='left' bgcolor='#596D9B'><font color='#FFFFFF'><B>OS com peça enviada para Fábrica</B></font></td>";
echo "</tr>";
echo "<tr>";
	echo "<td align='left' ><font size='1'>Número da OS</font></td>";
	echo "<td align='left' ><font size='1'>Número de série</font></td>";
	echo "<td align='left' ><font size='1'>Nota fiscal</font></td>";
echo "</tr>";
echo "<TR>";
	echo "<td align='left' ><input type='text' name='sua_os' size='10' value='$sua_os'></td>";
	echo "<td align='left' ><input type='text' name='n_serie' size='10' value='$n_serie'></td>";
	echo "<td align='left' ><input type='text' name='nota_fiscal' size='10' value='$nota_fiscal'></td>";
echo "</tr>";
echo "<tr>";
	echo "<td align='left' colspan='3'>";
echo "<input type='checkbox' name='solucao' value='9'";
if (strlen ($solucao) > 0 ) echo " checked ";
echo "> <font size='1'>Apenas OS que a solução é: PCI enviada para Tectoy</font>";
echo "</tr>";

echo "<tr>";
	echo "<TD align='center' colspan='3'>";
	echo "<BR><center><input type='submit' name='btn_acao' value='Pesquisar'></center>";
	echo "</td>";
echo "</tr>";

echo "</TABLE>";
echo "</form>";


//LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
//AND tbl_os.solucao_os = 9
if(strlen($btn_acao)>0){
	if(strlen($msg_error)==0){
		$sql = "SELECT 	tbl_os.os                                                         ,
						tbl_os.serie                                                      ,
						tbl_os.sua_os                                                     ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM')  AS digitacao              ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM')   AS abertura               ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM') AS fechamento             ,
						TO_CHAR(tbl_os.finalizada,'DD/MM')      AS finalizada             ,
						tbl_os.solucao_os                                                 ,
						tbl_os.consumidor_nome                                            ,
						tbl_os.produto                                                    ,
						tbl_produto.descricao                                             ,
						tbl_posto.nome                                                    
				FROM tbl_os
				JOIN tbl_produto using(produto)
				JOIN tbl_posto on tbl_posto.posto = tbl_os.posto
				JOIN tbl_os_extra  on tbl_os.os = tbl_os_extra.os
				WHERE tbl_os.fabrica=$login_fabrica
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.tipo_os IS NULL
				AND tbl_os_extra.extrato IS NULL
				$condicao_4
				$condicao_1 
				$condicao_2 
				$condicao_3 
				ORDER BY tbl_posto.nome";
		$res = pg_exec($con, $sql);

//echo $sql;
		if(pg_numrows($res)>0){
		echo "<BR><BR><table width='300' border='0' align='center'>";
			echo "<TR>";
			echo "<td bgcolor='#e5af8a' width='35'>&nbsp;</td>";
			echo "<td><font size='1'>OS posto marcou como 'PCI enviada para Tectoy'</font></td>";
			echo "</TR>";
		echo "</table>";

			echo "<BR><BR><table width='700' border='0' cellspacing='1' bgcolor='#485989' cellpadding='3' align='center' style='font-family: verdana; font-size: 12px'>";
			echo "<TR>";
			echo "<TD align='center' colspan='9'><font color='#FFFFFF'><b>Foram encontradas ".pg_numrows($res)." OSs</b></font></td>";
			echo "</TR>";
			echo "<TR  bgcolor='#f4f7fb'>";
			echo "<TD align='center'>OS</td>";
			echo "<TD align='center'>N.Série</td>";
		//	echo "<TD align='center'>Digitação</td>";
			echo "<TD align='center'>Aber.</td>";
			echo "<TD align='center'>Fech.</td>";
			echo "<TD align='center'>Posto</td>";
			echo "<TD align='center'>Consumidor</td>";
			echo "<TD align='center'>Produto</td>";
			echo "<TD align='center'>Autorizar?</td>";

			echo "</TR>";
			for($x=0;$x<pg_numrows($res);$x++){
				$os                = pg_result($res,$x,os);
				$n_serie           = pg_result($res,$x,serie);
				$sua_os            = pg_result($res,$x,sua_os);
				$digitacao         = pg_result($res,$x,digitacao);
				$abertura          = pg_result($res,$x,abertura);
				$fechamento        = pg_result($res,$x,fechamento);
				$finalizada        = pg_result($res,$x,finalizada);
				$solucao_os        = pg_result($res,$x,solucao_os);
				$consumidor_nome   = pg_result($res,$x,consumidor_nome);
				$produto_descricao = pg_result($res,$x,descricao);
				$posto_nome        = pg_result($res,$x,nome);
				
				if ($x % 2 == 0) {$cor="#f4f7fb";}else{$cor="#f3f2e7";}
				if($solucao_os==9)$cor="#e5af8a";
				echo "</TR>";
				echo "<TR  bgcolor='$cor'>";
				echo "<TD align='center'><font size='1'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a>           </font></td>";
				echo "<TD align='center'><font size='1'>$n_serie          </font></td>";
				//echo "<TD align='center'><font size='1'>$digitacao        </font></td>";
				echo "<TD align='center'><font size='1'>$abertura         </font></td>";
				echo "<TD align='center'><font size='1'>$fechamento       </font></td>";
				echo "<TD align='left' nowrap><font size='1'>". substr($posto_nome,0,15) ."</font></td>";
				echo "<TD align='left' nowrap><font size='1'>". substr($consumidor_nome,0,15) ."  </font></td>";
				echo "<TD align='left' nowrap><font size='1'>". substr($produto_descricao,0,15) ."</font></td>";
				echo "<td><a href=\"javascript: if(confirm('Deseja realmente pagar metade da mão-de-obra para a OS $sua_os ?') ==true )window.location='$PHP_SELF?autorizar=$os';\"}><img border='0' src='imagens/btn_autorizar.gif' alt='Pagamento de metade da mão-de-obra'></a><a href=\"javascript: if(confirm('Deseja pagar a mão-de-obra normal para a OS $sua_os ?') ==true )window.location='$PHP_SELF?n_autorizar=$os';\"}><img border='0' src='imagens/btn_cancelar.gif'  alt='Paga mão-de-obra normalmente'></a></td>";
//				echo "<TD align='center'><a href='$PHP_SELF?autorizar=$os'><img border='0' src='imagens/btn_autorizar.gif'></a></td>";
				echo "</TR>";
			}
		}else{
			echo "<center>Nenhum resultado encontrado</center>";
		}

	}
}

include "rodape.php";
?>