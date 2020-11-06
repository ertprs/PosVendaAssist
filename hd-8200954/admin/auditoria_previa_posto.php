<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

//FILTRO - HD 206656
$resposta = '
<FORM name="frm_pesquisa" METHOD="POST" ACTION="'.$PHP_SELF.'">
	<TABLE width="700" align="center" border="0" cellspacing="1" cellpadding="0" class="formulario">
		<TBODY>
			<tr><td class="titulo_tabela" colspan="4">Parâmetros de Pesquisa</td></tr>
			
			<TR>
				<TD colspan="3" align="center" style="padding:10px 0 10px;">Linha&nbsp;';
					
					$sql = "SELECT *
							  FROM tbl_linha
							 WHERE tbl_linha.fabrica = $login_fabrica
							 ORDER BY tbl_linha.nome;";
					
					$res = pg_exec ($con,$sql);

					if (pg_numrows($res) > 0) {
						$resposta .= "<select name='linha' class='frm' onchange='document.frm_pesquisa.submit()'>\n";
						$resposta .=  "<option value=''>ESCOLHA</option>\n";
						for ($x = 0 ; $x < pg_numrows($res) ; $x++){
							$aux_linha = trim(pg_result($res,$x,linha));
							$aux_nome  = trim(pg_result($res,$x,nome));

							$resposta .=  "<option value='$aux_linha'";
							if ($linha == $aux_linha){
								$resposta .=  ' SELECTED="SELECTED"';
								$mostraMsgLinha = "<br> da LINHA $aux_nome";
							}
							$resposta .=  ">$aux_nome</option>\n";
						}
						$resposta .=  "</select>\n&nbsp;";
					}
					$resposta .= '
				</TD>
			</TR>
		</TBODY>
	</TABLE>
</FORM>';

//if($_GET['ajax']=='sim') {
	$posto_problema =  array();
	$total1 = 0;
	$total2 = 0;
	$total3 = 0;

	//hd 7118 - dependendo do dia da semana não deve contar sábado e domingo
	$sql_dia_semana = "SELECT  extract(dow from now()) + 1 as dia_da_semana";
	$res_dia_semana = pg_exec($con,$sql_dia_semana);
	if (pg_result($res_dia_semana,0,0) == 2 or pg_result($res_dia_semana,0,0) == 3 or pg_result($res_dia_semana,0,0) == 4) 
		$intervalo = "5 DAY";
	elseif (pg_result($res_dia_semana,0,0) == 5 or pg_result($res_dia_semana,0,0) == 6 or pg_result($res_dia_semana,0,0) == 7) 
		$intervalo = "3 DAY";
	elseif (pg_result($res_dia_semana,0,0) == 1)
		$intervalo = "4 DAY";

	//hd 7118 foi colocado intervalo, antes estava pegando AND tbl_os_auditar.data::date = current_date
	$sql = "SELECT count(*) 
			FROM tbl_os 
			JOIN tbl_os_auditar    ON tbl_os.os = tbl_os_auditar.os
			JOIN tbl_os_extra      ON tbl_os.os = tbl_os_extra.os
			JOIN tbl_produto       ON tbl_os.produto = tbl_produto.produto
			JOIN tbl_posto         USING (posto)
			JOIN tbl_posto_fabrica ON tbl_posto.posto =  tbl_posto_fabrica.posto 
						AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica 
			AND tbl_os.auditar           IS TRUE 
			AND tbl_os_auditar.liberado  IS FALSE
			AND tbl_os_auditar.cancelada IS NOT TRUE
			AND tbl_os_auditar.data::date >= current_date-INTERVAL'$intervalo'
			AND (tbl_os_extra.data_status < current_date OR tbl_os_extra.data_status ISNULL)";
	if ($_POST['linha'] != '') {
		$sql .= " AND tbl_produto.linha = " . $_POST['linha'];
	}
	$res = pg_exec($con,$sql);
#	if ($ip=="201.71.54.144") $resposta = $sql."<BR><BR>";
	
	if(pg_numrows ($res) > 0){
		$total_para_auditar = trim(pg_result($res,0,0));

		$resposta .= "<br><table class='tabela' align='center' width='700' cellspacing='1'><tr class='titulo_tabela'><td colspan='7' style='font-size:14px;'>";
		$resposta .= "<b>$total_para_auditar</b> OS para Auditar</center>";
		$resposta .= "</td></tr>";
	}

	//hd 7118 foi colocado intervalo, antes estava pegando AND tbl_os_auditar.data::date = current_date
	$sql = "SELECT  count(*)                         AS total,
					tbl_os_auditar.auditar                   ,
					tbl_posto.posto                          ,
					tbl_posto.nome                           ,
					tbl_posto_fabrica.codigo_posto           ,
					tbl_posto_fabrica.contato_estado          
			FROM tbl_os 
			JOIN tbl_os_auditar    ON tbl_os.os = tbl_os_auditar.os
			JOIN tbl_os_extra      ON tbl_os.os = tbl_os_extra.os
			JOIN tbl_produto       ON tbl_os.produto = tbl_produto.produto
			JOIN tbl_posto         USING (posto)
			JOIN tbl_posto_fabrica ON tbl_posto.posto =  tbl_posto_fabrica.posto 
						AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica 
			AND tbl_os.auditar           IS TRUE 
			AND tbl_os_auditar.liberado  IS FALSE
			AND tbl_os_auditar.cancelada IS NOT TRUE
			AND tbl_os_auditar.data::date >= current_date-INTERVAL'$intervalo'
			AND (tbl_os_extra.data_status < current_date OR tbl_os_extra.data_status ISNULL)";
	
	if ($_POST['linha'] != '') {
		$sql .= " AND tbl_produto.linha = " . $_POST['linha'];
	}
	
	$sql .= " GROUP BY tbl_os_auditar.auditar , 
					 tbl_posto.posto , 
					 tbl_posto.nome , 
					 tbl_posto_fabrica.codigo_posto,
					 contato_estado
			ORDER BY tbl_posto.posto , 
					 tbl_os_auditar.auditar";
	
#	if ($ip=="201.71.54.144") $resposta .= $sql."<BR><BR>";
	$res = pg_exec($con,$sql);

	if(pg_numrows ($res) > 0){

		$qtde =  array();
	
		$prox            = 0;
		$total_auditoria = 4;
		//--==== Cria os vetores dos posto zerados ==========================================
		for ($i=0; $i<pg_numrows($res); $i++){
			$posto              = trim(pg_result($res,$i,posto));
			$nome               = trim(pg_result($res,$i,nome));
			$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
			$estado             = trim(pg_result($res,$i,contato_estado));
			if($posto <> $posto_anterior){
				for($x=0 ; $x <= $total_auditoria ; $x++){
					if($x == 0 )  $qtde[$prox][0] = $posto;
					else          $qtde[$prox][$x]  = 0;
					
				}
				$qtde[$prox][5] = "$codigo_posto - $nome";
				if($login_fabrica == 3){
					$qtde[$prox][6] = "$codigo_posto";
					$qtde[$prox][7] = "$nome";
					$qtde[$prox][8] = "$estado";
				}
				$posto_anterior = $posto;
				$prox   = $prox + 1;
			}
		}
		if($login_fabrica == 51){
			$prox            = 0;
		}else{
			$prox            = -1;
		}

		$total_auditoria = 4;
		$posto_anterior = "";
		for ($i=0; $i<pg_numrows($res); $i++){
			$posto              = trim(pg_result($res,$i,posto));
			$auditar              = pg_result ($res,$i,auditar);
			$total                = pg_result ($res,$i,total);

			if($posto <> $posto_anterior)$prox   = $prox + 1;
	
			if( $qtde[$prox][0] == $posto ){

				if($auditar == '1' )    $qtde[$prox][1] = $total;
				elseif($auditar == '2') $qtde[$prox][2] = $total;
				elseif($auditar == '3') $qtde[$prox][3] = $total;
				elseif($auditar == '4') $qtde[$prox][4] = $total;
			}
	
			$posto_anterior = $posto;
	
		}
		//print_r($qtde);

		//IMPRIME O ARRAY DE POSTOS
		for ($i = 0 ; $i <= $prox ; $i++) {
			if ($i == 0){
				
				$resposta .= "<tr class='titulo_coluna'>";
				if($login_fabrica == 3){
					$resposta .= "<td width='100' align='center'>Codigo do posto</td>";
					$resposta .= "<td width='300'>Nome</td>";
					$resposta .= "<td width='60'>UF</td>";
				}else{
					$resposta .= "<td width='400'>Posto</td>";
				}
				$resposta .= "<td width='120'>OS's Reincidentes</td>";
				$resposta .= "<td width='120'>OS's + 3 pc</td>";
				$resposta .= "<td width='120'>Peças com datas diferentes</td>";
				$resposta .= "<td width='120'>OS's Reincidentes (mais 90 dias)</td>";
				$resposta .= "</tr><tr class='Titulo'>";
			}
	
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
	
			$resposta .= "<tr bgcolor='$cor'>";
			if($login_fabrica ==3 ){
				$resposta .= "<td align='center'><a href='os_press.php?auditoria=t&posto=".$qtde[$i][0]."&linha=".$_POST['linha']."' target='_blank'>".$qtde[$i][6]."</a></td>";
				$resposta .= "<td align='left'><a href='os_press.php?auditoria=t&posto=".$qtde[$i][0]."&linha=".$_POST['linha']."' target='_blank'>".$qtde[$i][7]."</a></td>";
				$resposta .= "<td title='UF' align='center'>".$qtde[$i][8]."</td>";
			}else{
				$resposta .= "<td align='left'><a href='os_press.php?auditoria=t&posto=".$qtde[$i][0]."' target='_blank'>".$qtde[$i][5]."</a></td>";
			}
			$resposta .= "<td title='OSs Reincidentes' align='center'>";
			$resposta .= $qtde[$i][1];
			$resposta .= "</td>";
		
			$resposta .= "<td title='Com mais de 3 peças' align='center'>";
			$resposta .= $qtde[$i][2];
			$resposta .= "</td>";
	
			$resposta .= "<td title='Com peças lançadas em datas diferentes' align='center'>";
			$resposta .= $qtde[$i][3];
			$resposta .= "</td>";
	
			$resposta .= "<td title='OSs Reincidentes a mais de 90 dias' align='center'>";
			$resposta .= $qtde[$i][4];
			$resposta .= "</td>";
	
			$resposta .= "</tr>";
	
			$total1 = $total1 + $qtde[$i][1];
			$total2 = $total2 + $qtde[$i][2];
			$total3 = $total3 + $qtde[$i][3];
			$total4 = $total4 + $qtde[$i][4];
			
			if($login_fabrica ==3){
				$coluna=3;
			}else{
				$coluna=1;
			}
			if( $i==($prox)){
				$resposta .= "<tr bgcolor='#FFFF99' class='Conteudo'><td colspan='$coluna'><b>TOTAL</b></td><td align='center'><b>$total1</b></td><td align='center'><b>$total2</b></td><td align='center'><b>$total3</b></td><td align='center'><b>$total4</b></td></tr>";
				$resposta .= "</table>";
			}
		}

	}

	//echo "ok|".$resposta;
	//exit;
//}

$layout_menu = "auditoria";
$title = "AUDITORIA PRÉVIA DE POSTOS";
include "cabecalho.php";

?>

<script language='javascript' src='../ajax.js'></script>
<script language='javascript'>

function retornaOS (http,componente) {
	var com = document.getElementById(componente);

	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = " "+results[1];
				} else {
					com.innerHTML   = "<h4>Ocorreu um erro</h4>";
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}

function Exibir (componente) {
	url = "<?=$PHP_SELF?>?ajax=sim" ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaOS (http,componente) ; } ;
	http.send(null);
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
}
</style>

<?
flush;
echo  "<div class='texto_avulso'><a href='/assist/admin/documentos/criteriosdeauditoria.xls' target='_blank'>Regras de Auditoria</a></div><br />";

//HD:42395 - IGOR 23/09/2008 foi retirado pois em visita a britania, estava mostrando a qtd 2, mas depois ao verificar o problema, mostrou 0 (zero)
//Entao foi retirado o ajax por ser possível problema no cache
//echo "<DIV class='exibe' id='dados' value='1' align='center'><font size='1'>Por favor aguarde um momento, carregando os dados...<br><img src='../imagens/carregar_os.gif'></DIV>";
//echo "<script language='javascript'>Exibir('dados');</script>";

echo $resposta;
echo "<br>";
include "rodape.php";

?>