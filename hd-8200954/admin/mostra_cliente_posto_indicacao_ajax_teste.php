<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


$aux_data_inicial = $_GET['data_inicial'];
$aux_data_final = $_GET['data_final'];
$cache = md5(time());
$cond_1 = " 1=1 ";
if(strlen($_GET['posto']>0)) {
	$codigo_posto = $_GET['posto'];
	$sqlposto     = "select posto 
					from tbl_posto_fabrica 
					where fabrica = $login_fabrica 
					and codigo_posto = '$codigo_posto'";
	$res = pg_exec($con,$sqlposto);
	
	$posto = pg_result($res,0,0);
	$cond_1 = "tbl_os.posto = $posto <br>";
}
	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado as callcenter,
					to_char(tbl_hd_chamado_extra.data_abertura,'DD/MM/YYYY') as abertura_callcenter,
					tbl_hd_chamado_extra.nome,
					tbl_hd_chamado_extra.fone ,
					tbl_hd_chamado_extra.celular ,
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto
		LEFT JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto  and tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND tbl_hd_chamado_extra.posto = $posto
		AND tbl_hd_chamado.data between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
		AND tbl_hd_chamado.titulo = 'Indicação de Posto'";
//echo nl2br($sql);

$res = pg_exec ($con,$sql);

echo "$linha|";
	echo "<table border=1 cellpadding=1 cellspacing=0 style=border-collapse: collapse bordercolor=#d2e4fc align=center width=500>";
		echo "<tr class=Titulo>";
		echo "<td ></td>";
		echo "<td>Chamado</td>";
		echo "<td >Cliente</td>";
		echo "<td >Fone</td>";
		echo "<td >Celular</td>";
		echo "<td >Cidade</td>";
		echo "<td >Estado</td>";
		echo "</tr>";
	
		$total = pg_numrows($res);
		$total_pecas = 0;
		
		for ($y=0; $y<pg_numrows($res); $y++){

			$hd_chamado               = trim(pg_result($res,$y,callcenter));
			$nome                     = trim(pg_result($res,$y,nome));
			$fone                     = trim(pg_result($res,$y,fone));
			$fone_s_mascara           = trim(pg_result($res,$y,fone));
			$celular                  = trim(pg_result($res,$y,celular));
			$cidade                   = trim(pg_result($res,$y,cidade_nome));
			$estado                   = trim(pg_result($res,$y,estado));

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
			
			$palavras = explode(' ',$fone_s_mascara);
			$count = count($palavras);
			for($x=0 ; $x < $count ; $x++){
				if(strlen(trim($palavras[$x]))>0){
					$fone_s_mascara = trim($palavras[$x]);
					$fone_s_mascara = str_replace (' ','',$fone_s_mascara);
					$fone_s_mascara = str_replace ('(','',$fone_s_mascara);
					$fone_s_mascara = str_replace (')','',$fone_s_mascara);
					$fone_s_mascara = str_replace ('-','',$fone_s_mascara);
				}
			}

			echo "<tr>";
			echo "<td><a href=# onClick=load2($y);chamaAjax2($y,\"$aux_data_inicial\",\"$aux_data_final\",$posto,\"$cache\")><div id=div_sinal2_$y>+</div></a></td>";
			echo "<td bgcolor=$cor align=center nowrap><a href=callcenter_interativo_new_teste.php?callcenter=$hd_chamado target=_blank>$hd_chamado</a></td>";
			echo "<td bgcolor=$cor align=left nowrap>$nome</td>";
			echo "<td bgcolor=$cor nowrap>$fone</td>";
			echo "<td bgcolor=$cor nowrap>$celular</td>";
			echo "<td bgcolor=$cor nowrap>$cidade</td>";
			echo "<td bgcolor=$cor nowrap>$estado</td>";
			echo "</tr>";
			echo "<tr><td colspan=7>";
			echo "<div id=div_detalhe2_$y></div>";
			echo "</td></tr>";
				}

?>