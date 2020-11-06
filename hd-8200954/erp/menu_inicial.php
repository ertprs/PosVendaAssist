<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include "menu.php";

$msg_erro = $_GET["msg_erro"];

if (strlen($msg_erro)>0) echo "<div class='error'>$msg_erro</div>";

//echo "$login_empregado_nome";
	$sql = "SELECT nome FROM tbl_posto WHERE posto = $login_posto_empregado;";
	$res = @pg_exec ($con,$sql);
	$l_empregado_nome = trim (@pg_result ($res,0,nome));
//echo $l_empregado_nome;

$sql = "SELECT  tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.ativo,
				tbl_peca_item.valor_venda,
				tbl_estoque.qtde as estoque,
				tbl_estoque_extra.quantidade_entregar,
				to_char(tbl_estoque_extra.data_atualizacao,'DD/MM/YYYY') as data_atualizacao
		FROM    tbl_peca
		LEFT JOIN    tbl_peca_item ON tbl_peca_item.peca=tbl_peca.peca
		LEFT JOIN tbl_estoque ON tbl_estoque.peca=tbl_peca.peca
		LEFT JOIN tbl_estoque_extra ON tbl_estoque_extra.peca = tbl_peca.peca
		WHERE   (tbl_peca.fabrica = $login_empresa OR tbl_peca.fabrica = $login_empresa)
		ORDER BY random() LIMIT 3";

$res = pg_exec ($con,$sql);

echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center'  border='0' class='tabela'>";
echo "<tr>\n";
echo "<td colspan='6' class='Titulo_Tabela' bgcolor='#7392BF'>Promoções</td>";
echo "</tr>";
echo "<tr>";

for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {

	$peca                = trim(pg_result($res,$i,peca));
	$referencia          = trim(pg_result($res,$i,referencia));
	$descricao           = trim(pg_result($res,$i,descricao));
	$preco               = trim(pg_result($res,$i,valor_venda));
	$estoque             = trim(pg_result($res,$i,estoque));
	$quantidade_entregar = trim(pg_result($res,$i,quantidade_entregar));
	$data_atualizacao    = trim(pg_result($res,$i,data_atualizacao));
	$ativo               = trim(pg_result($res,$i,ativo));

	$descricao = str_replace ('"','',$descricao);
	$descricao = str_replace ("'","",$descricao);

	if ($ativo == 't') {
		$mativo = "ATIVO";
	}else{
		$mativo = "INATIVO";
	}

	$fotos     = '';
	$num_fotos = '';

	$sql2 = "SELECT  peca_item_foto,caminho,caminho_thumb,descricao
			FROM tbl_peca_item_foto
			WHERE peca = $peca";
	$res2 = pg_exec ($con,$sql2) ;
	$fotos = array();
	$num_fotos = pg_num_rows($res2);
	if ($num_fotos){
		for ($r=0; $r<$num_fotos ;$r++){
			$caminho        = trim(pg_result($res2,$r,caminho));
			$caminho_thum   = trim(pg_result($res2,$r,caminho_thumb));
			$foto_descricao = trim(pg_result($res2,$r,descricao));    
			$foto_id        = trim(pg_result($res2,$r,peca_item_foto));    
			
			$caminho = str_replace("/www/assist/www/erp/","",$caminho);
			$caminho_thum = str_replace("/www/assist/www/erp/","",$caminho_thum);
			
			$aux=explode("|",$caminho."|".$caminho_thum."|".$foto_descricao."|".$foto_id);               
			array_push($fotos,$aux);
		}
	}

	$cor = '#F2F7FF';
	if ($i % 2 <> 0) $cor = '#FFFFFF';

	echo "<td width='120' bgcolor='$cor' class='Propaganda'>";
	$num_fotos = count($fotos);
	$cont = 0;
	if ($num_fotos>0){
		foreach($fotos as $foto) {
			$cam_foto   = $foto[0];
			$cam_foto_t = $foto[1];
			$desc_foto  = $foto[2];
			$foto_id    = $foto[3];
			
			//echo " <div class='contenedorfoto'><a href='?peca=$peca&excluir_foto=$foto_id'><img src='imagens/cancel.png' width='12px' alt='Excluir foto' style='margin-right:0;float:right;align:right' /></a><a href='$cam_foto' title='$desc_foto' class='thickbox' rel='gallery-plants'><img src='$cam_foto_t' alt='$desc_foto' /><br /><span>$desc_foto</span></a></div>"; 
			if($cont==0){
				echo " <div class='contenedorfoto'><a href='$cam_foto' title='$desc_foto' class='thickbox' rel='$peca'><img src='$cam_foto_t' alt='$desc_foto' /><br /></a></div>"; 
				$cont++;
			}else{
				echo "<a href='$cam_foto' style='display:none' title='$desc_foto' class='thickbox' rel='$peca'>";
			}
		}
	
	}else{
		echo "<div class='contenedorfoto'><img src='imagens/semimagem.jpg' alt='Sem Imagem' /><br /></a></div>";
	}
	echo "</td>";


	echo "<td bgcolor='$cor' class='Propaganda'>";

	echo "<font face='Arial, Verdana, Times, Sans' size='4' color='#0000FF'>$descricao</font>\n";
	echo "</a><br>";


	echo "Código: \n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
	echo "</A><br>";

	echo "\n";

	echo "Estoque: \n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'><b>$estoque</b></font>\n";
	echo "\n<br>";

	echo "Quantidade a Entregar: ";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'><b>$quantidade_entregar</b></font>\n";
	echo "\n<br>";

	echo "\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='3' color='#FF0000'><b>R$ $preco</b></font>\n";
	echo "\n";

	echo "</td>\n";

}
echo "</table>\n";

//--==== INICIO - Promoção ======================================================================
$sql = "SELECT  
		tbl_hd_chamado.hd_chamado,
		tbl_hd_chamado.empregado,
		to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
		tbl_hd_chamado.titulo,
		tbl_hd_chamado.status
	FROM       tbl_hd_chamado
	JOIN       tbl_empregado  USING(empregado)
	WHERE      tbl_hd_chamado.fabrica   = $login_empresa
	AND        tbl_hd_chamado.empregado = $login_empregado 
	AND        tbl_hd_chamado.categoria = 'Promoção'
	AND        tbl_hd_chamado.orcamento IS NULL
	ORDER BY   tbl_hd_chamado.data ";

$res = pg_exec ($con,$sql);
if (pg_numrows($res) > 0) {
echo "<br><table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='200' border='0' class='tabela'>";
echo "<tr><td colspan='4' class='Titulo_Tabela' bgcolor='#7392BF'><b>Informativo de Marketing</b></td></tr>";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$hd_chamado           = pg_result($res,$i,hd_chamado);
		$empregado            = pg_result($res,$i,empregado);
		$data                 = pg_result($res,$i,data);
		$titulo               = pg_result($res,$i,titulo);

		$sql2 = "SELECT nome FROM tbl_empregado JOIN tbl_pessoa USING(pessoa) WHERE empregado = $empregado";
		$res2 = @pg_exec ($con,$sql2);
		$empregado_nome = pg_result($res2,0,0);

		$cor='#F2F7FF';
		if ($i % 2 == 0) $cor = '#FFFFFF';

		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td><a href='hd_chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a></td>";
		echo "<td nowrap>$data</td>";
		//echo "<td nowrap>$empregado_nome</td>";
		echo "</a></tr>"; 
	}
	echo "</table>"; //fim da tabela de chamadas


}else{
	echo "<center><h3>NENHUMA PROMOÇÃO VIGENTE</h3></center>";
}
//--==== FIM - Promoção ======================================================================


//--==== Associação Comercial =======================================================================================
echo "<br><a href='http://www.acim.org.br' target='_blank'><font size='2'>Associação Comercial da Cidade</font></a><br><br>";
echo "<img src='http://www.acim.org.br/calendario/jan2007.png' width='147' height='93'>";
echo "<img src='http://www.acim.org.br/calendario/fev2007.png' width='147' height='93'>";
echo "<img src='http://www.acim.org.br/calendario/mar2007.png' width='147' height='93'>";
echo "<img src='http://www.acim.org.br/calendario/abril2007.png' width='147' height='93'>";
echo "<img src='http://www.acim.org.br/calendario/maio2007.png' width='147' height='93'>";
echo "<img src='http://www.acim.org.br/calendario/jun2007.png' width='147' height='93'>";
echo "<img src='http://www.acim.org.br/calendario/julho2007.png' width='147' height='93'>";
echo "<img src='http://www.acim.org.br/calendario/ago2007.png' width='147' height='93'>";
echo "<img src='http://www.acim.org.br/calendario/set2007.png' width='147' height='93'>";
echo "<img src='http://www.acim.org.br/calendario/out2007.png' width='147' height='93'>";
echo "<img src='http://www.acim.org.br/calendario/nov2007.png' width='147' height='93'>";
echo "<img src='http://www.acim.org.br/calendario/dez2007.png' width='147' height='93'><br>";

echo "<table style='font-size:10px'>";
echo "<tr>";
echo "<td bgcolor='#FFFF00' width='20' style='border:#000000 1px solid;'>&nbsp;&nbsp;</td><td>&nbsp;Das 9 às 17h</td>";
//	echo "</tr>";
//	echo "<tr>";
echo "<td bgcolor='#FF9900' width='20' style='border:#000000 1px solid;'>&nbsp;&nbsp;</td><td>&nbsp;Das 9 às 13h</td>";
	//echo "</tr>";
//	echo "<tr>";
echo "<td bgcolor='#3366FF' width='20' style='border:#000000 1px solid;'>&nbsp;&nbsp;</td><td>&nbsp;Das 9 às 22h</td>";
//	echo "</tr>";
//	echo "<tr>";
echo "<td bgcolor='#007700' width='20' style='border:#000000 1px solid;'>&nbsp;&nbsp;</td><td>&nbsp;Das 12 às 18h</td>";
//	echo "</tr>";
//	echo "<tr>";
echo "<td bgcolor='#DDDDDD' width='20' style='border:#000000 1px solid;'>&nbsp;&nbsp;</td><td>&nbsp;Fechado</td>";
//	echo "</tr>";
//	echo "<tr>";
echo "<td bgcolor='#FFFFFF' width='20' style='border:#000000 1px solid;'>&nbsp;&nbsp;</td><td>&nbsp;Das 8 às 18h</td>";
echo "</tr>";
echo "</table>";
//--==== Associação Comercial =======================================================================================
?>