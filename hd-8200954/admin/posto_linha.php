<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria";
include "autentica_admin.php";
include 'funcoes.php';
?>



<?
if ($login_fabrica == 117) {
        $title= traduz('RELATÓRIO DE POSTOS E AS MACRO - FAMÍLIAS UTILIZADAS');
} else {
        $title= traduz('RELATÓRIO DE POSTOS E AS LINHAS UTILIZADAS');
}
$layout_menu = "auditoria";
include "cabecalho_new.php";

if ($login_fabrica == 117) {
        //QUANTIDADE DE LINHAS
        $sql="SELECT count( DISTINCT tbl_linha.linha) 
                                FROM tbl_linha 
                                        JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                                        JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
                                WHERE tbl_linha.fabrica = $login_fabrica";
        $res = pg_exec($con,$sql);
        $qtde               = trim(pg_result($res,0,0));
        $qtde2=$qtde+3;
        $final              = $qtde-1;


        $sql = "SELECT DISTINCT codigo_posto                    ,
                        tbl_posto.nome AS posto_nome    ,
                        tbl_posto.nome_fantasia,
                        tbl_posto_fabrica.contato_cidade,
                        (tbl_posto_fabrica.contato_endereco) AS endereco,
                        tbl_posto_fabrica.contato_bairro,
                        contato_estado as estado        ,
                        tbl_linha.linha                           ,
                        tbl_linha.nome  AS linha_nome   ,
                        tbl_tabela.sigla_tabela         ,
                        tbl_posto_linha.distribuidor    ,
                        credenciamento
        FROM  tbl_posto 
        JOIN  tbl_posto_fabrica USING(posto)
        JOIN  tbl_posto_linha   USING(posto) 
        JOIN  tbl_linha         USING(linha)
        JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
        JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
        JOIN tbl_tabela ON tbl_posto_linha.tabela=tbl_tabela.tabela
        WHERE tbl_linha.fabrica         = $login_fabrica 
        AND   tbl_posto_fabrica.fabrica = $login_fabrica 
        AND   tbl_posto_fabrica.credenciamento<>'DESCREDENCIADO'
        ORDER BY codigo_posto,linha_nome";
} else {
	//QUANTIDADE DE LINHAS
	$sql="SELECT count(linha) FROM tbl_linha WHERE fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);
	$qtde               = trim(pg_result($res,0,0));
	$qtde2=$qtde+3;
	$final              = $qtde-1;

	$sql = "SELECT DISTINCT codigo_posto                    ,
				tbl_posto.nome AS posto_nome    ,
				tbl_posto.nome_fantasia,
				tbl_posto_fabrica.contato_cidade,
				(tbl_posto_fabrica.contato_endereco) AS endereco,
				tbl_posto_fabrica.contato_bairro,
				contato_estado as estado        ,
				linha                           ,
				tbl_linha.nome  AS linha_nome   ,
				tbl_tabela.sigla_tabela         ,
				tbl_posto_linha.distribuidor    ,
				credenciamento
		FROM  tbl_posto 
		JOIN  tbl_posto_fabrica USING(posto)
		JOIN  tbl_posto_linha   USING(posto) 
		JOIN  tbl_linha         USING(linha)
		JOIN tbl_tabela ON tbl_posto_linha.tabela=tbl_tabela.tabela
		WHERE tbl_linha.fabrica         = $login_fabrica 
		AND   tbl_posto_fabrica.fabrica = $login_fabrica 
		AND   tbl_posto_fabrica.credenciamento<>'DESCREDENCIADO'
		ORDER BY codigo_posto,linha_nome";
}
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	echo '</div>';
	echo "<div class='row-fluid'>";
	echo "<div class='span1'></div>";
	echo "<table class='table table-striped table-bordered table-fixed'>";
	echo "<thead><tr class='titulo_tabela'>";
	if ($login_fabrica == 117) {
            echo "<td colspan='100%' align='center'>".traduz("Relatório de Postos por Macro - Família")."</td>";
    } else {
            echo "<td colspan='100%' align='center'>".traduz("Relatório de Postos por Linha")."</td>";
    }
	echo "</tr>";
	echo "<tr class='titulo_coluna' >";
	echo "<th  rowspan='2' class='titulo_coluna'>".traduz("Código Posto")."</th>";
	echo "<th width='100' align='center' rowspan='2'>".traduz("Posto")."</th>";

	if(in_array($login_fabrica,array(91))){
		echo "<th rowspan='2' align='center'>".traduz("Nome Fantasia")."</th>";
		echo "<th rowspan='2' align='center'>".traduz("Endereço")."</th>";
		echo "<th rowspan='2' align='center'>".traduz("Bairro")."</th>";
		echo "<th rowspan='2' align='center'>".traduz("Cidade")."</th>";
	}

	echo "<td width='20' rowspan='2'>".traduz("UF")."</td>";
	if( in_array($login_fabrica, array(11,172)) ) { // HD 17304
		echo "<th rowspan='2' align='center'>".traduz("STATUS")."</th>";
	}
    if ($login_fabrica == 117) {
            echo "<td colspan='$qtde'>".traduz("Macro - Famílias")."</td>";
    } else {
            echo "<td colspan='$qtde'>".traduz("Linhas")."</td>";
    }
	echo "</tr><tr class='titulo_coluna'>";
    if ($login_fabrica == 117) {
            $sql2 = "SELECT DISTINCT tbl_linha.linha,
                             tbl_linha.nome
                FROM tbl_linha
                    JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                    JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
                WHERE tbl_macro_linha_fabrica.fabrica = $login_fabrica
                    AND     tbl_linha.ativo = TRUE
                    ORDER BY tbl_linha.nome;";
    } else {	
		$sql2="SELECT nome FROM tbl_linha WHERE fabrica = $login_fabrica order by nome";
	}
	$res2 = pg_exec($con,$sql2);

	for ($i=0; $i<pg_numrows($res2); $i++){
		$nome           = trim(pg_result($res2,$i,nome));
			$linha_nome_array[$i]= $nome;
			$linha_participa[$i] = "";
		echo "<th class='Linha' width='100'>$nome</th>";

	}
	echo "</thead></tr>";

	$familia=0;
	$x=0;
	$y=0;
	//zerando todos arrays
	$familia_total=0;

	$resultado = pg_numrows($res);
//	echo 'Total de Postos: '.$resultado = pg_numrows($res);;
	for ($i=0; $i<pg_numrows($res); $i++){

		$codigo_posto         = trim(pg_result($res,$i,codigo_posto));
		$posto_nome           = trim(pg_result($res,$i,posto_nome));
		$estado               = trim(pg_result($res,$i,estado));
		$linha_nome           = trim(pg_result($res,$i,linha_nome));
		$sigla_tabela         = trim(pg_result($res,$i,sigla_tabela));
		$distribuidor         = trim(pg_result($res,$i,distribuidor));
		$posto_nome           = substr($posto_nome, 0, 25);
		$credenciamento       = trim(pg_result($res,$i,credenciamento));
		$cidade               = pg_result($res,$i,contato_cidade);
		$endereco             = pg_result($res,$i,endereco);
		$bairro		      = pg_result($res,$i,contato_bairro);
		$nome_fantasia	      = pg_result($res,$i,nome_fantasia);

		if($codigo_posto<>$codigo_posto_anterior){
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
			$linha_nome_anterior='';

			if($i<>0 AND $codigo_posto<>$codigo_posto_anterior){
				$escreve='';
				for($j=0;$j < $qtde ; $j++){
					echo "<td align='center' title='$linha_nome_array[$j]'>&nbsp;$linha_participa[$j]</td>";
					$escreve='';
					$linha_participa [$j]='';
					if($j==$final)echo "</tr>";
				}
			}
			

			echo "<tr bgcolor='$cor' ><td align='center'>$codigo_posto</td><td align='center' nowrap><font size='1'>$posto_nome</font> </td>";

			if(in_array($login_fabrica,array(91))){
				
			       echo "<td>$nome_fantasia</td> <td>$endereco</td> <td>$bairro</td> <td>$cidade</td>";	
		        }

			echo "<td>$estado</td>";
			if( in_array($login_fabrica, array(11,172)) ) { // HD 17304
				echo "<TD align='center'><font size='1' ";
				if ($credenciamento == 'CREDENCIADO')
					echo "color='#3300CC'";
				elseif ($credenciamento == 'DESCREDENCIADO')
					echo "color='#F3274B'";
				elseif ($credenciamento == 'EM DESCREDENCIAMENTO')
					echo "color='#FF9900'";
				elseif ($credenciamento == 'EM CREDENCIAMENTO')
					echo "color='#006633'";
				echo "><B>";
				echo traduz($credenciamento);
				echo "</B></font></TD>";
			}
		}
		
		//if($linha_nome<>$linha_nome_anterior){
		for($j=0;$j < $qtde ; $j++){
			if($linha_nome_array[$j]==$linha_nome){
				if(strlen($distribuidor)>0){$sigla_tabela = "<b>D</b> | ".$sigla_tabela; }
				else                       {$sigla_tabela = "<b>F</b> | ".$sigla_tabela; }
				$linha_participa [$j]=$sigla_tabela;
				//echo " $codigo_posto :$linha_nome - $linha_nome_array[$j]".$linha_participa [$j]."<br><br>";
			}
		}

		$linha_nome_anterior=$linha_nome;

		$codigo_posto_anterior=$codigo_posto;
		if( $i==($resultado-1)){
			$escreve='';
			for($j=0;$j < $qtde ; $j++){
				echo "<td align='center' title='$linha_nome_array[$j]'>&nbsp;$linha_participa[$j]</td>";
				$linha_participa [$j]='';
				$escreve='';
				if($j==$final){
					echo "</tr>";
				}
			}
		}
	}
	echo "</table>";
	echo "</div>";
}
echo "<br>";
include 'rodape.php';

?>