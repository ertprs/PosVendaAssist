<?include "dbconfig.php";
include "dbconnect-inc.php";
include 'autentica_admin.php';
?>
<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Consumidores..</title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_consumidor.gif">

<?

if (strlen($_GET["nome"]) > 0) {
	$nome = strtoupper (trim ($_GET["nome"]));

	if (strlen($_GET["tipo2"]) > 0 AND $_GET["tipo2"] == 'assistencia') {

        echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome da Assistência Técnica</b>: <i>$nome</i></font>";
        echo "<p>";

        $sql = "SELECT
                                tbl_posto.posto                       AS id             ,
                                tbl_posto.nome                        AS nome           ,
                                tbl_posto.cnpj                        AS cpf_cnpj       ,
                                tbl_posto.endereco                    AS endereco       ,
                                tbl_posto.numero                      AS numero         ,
                                tbl_posto.complemento                 AS complemento    ,
                                tbl_posto.bairro                      AS bairro         ,
                                tbl_posto.cep                         AS cep            ,
                                tbl_posto.cidade                      AS nome_cidade    ,
                                tbl_posto.fone                        AS fone           ,
                                ''                                    AS cidade         ,
                                ''                                    AS rg             ,
                                tbl_posto.email                       AS email          ,
                                tbl_posto.estado                      AS estado         ,
                                'A'                                   AS tipo


                FROM            tbl_posto
                JOIN            tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
                WHERE           tbl_posto.nome ILIKE '$nome%'
                AND             tbl_posto_fabrica.fabrica = $login_fabrica
                ORDER BY        tbl_posto.nome";

	} else {

        echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do consumidor</b>: <i>$nome</i></font>";
        echo "<p>";

        $sql = "SELECT      tbl_hd_chamado_extra.*                 ,
                            tbl_cidade.nome AS nome_cidade,
                            tbl_cidade.estado
                FROM        tbl_hd_chamado_extra
                LEFT JOIN   tbl_cidade USING (cidade)
                WHERE       tbl_hd_chamado_extra.nome ILIKE '$nome%'
                ORDER BY    tbl_hd_chamado_extra.nome";
        $sql = "SELECT	distinct
                        id              ,
                        nome            ,
                        endereco        ,
                        numero          ,
                        complemento     ,
                        bairro          ,
                        cep             ,
                        cidade          ,
                        fone            ,
                        cpf_cnpj        ,
                        rg              ,
                        email           ,
                        nome_cidade     ,
                        estado          ,
                        tipo
                    FROM (
                            (
                            SELECT  tbl_hd_chamado_extra.hd_chamado as id ,
                                    tbl_hd_chamado_extra.nome         ,
                                    tbl_hd_chamado_extra.endereco     ,
                                    tbl_hd_chamado_extra.numero       ,
                                    tbl_hd_chamado_extra.complemento  ,
                                    tbl_hd_chamado_extra.bairro       ,
                                    tbl_hd_chamado_extra.cep          ,
                                    tbl_hd_chamado_extra.cidade       ,
                                    tbl_hd_chamado_extra.fone         ,
                                    tbl_hd_chamado_extra.cpf as cpf_cnpj ,
                                    tbl_hd_chamado_extra.rg           ,
                                    tbl_hd_chamado_extra.email        ,
                                    tbl_cidade.nome AS nome_cidade,
                                    tbl_cidade.estado        ,
                                    'C' as tipo
                            FROM tbl_hd_chamado_extra
                            JOIN tbl_hd_chamado using (hd_chamado)
                            LEFT JOIN tbl_cidade USING (cidade)
                            WHERE tbl_hd_chamado_extra.nome ILIKE '$nome%'
                            AND   fabrica_responsavel = $login_fabrica
                            )union(
                            SELECT tbl_revenda.revenda as id ,
                                    tbl_revenda.nome         ,
                                    tbl_revenda.endereco     ,
                                    tbl_revenda.numero       ,
                                    tbl_revenda.complemento  ,
                                    tbl_revenda.bairro       ,
                                    tbl_revenda.cep          ,
                                    tbl_revenda.cidade       ,
                                    tbl_revenda.fone         ,
                                    tbl_revenda.cnpj  as cpf_cnpj,
                                    '' as rg                 ,
                                    tbl_revenda.email        ,
                                    tbl_cidade.nome AS nome_cidade,
                                    tbl_cidade.estado        ,
                                    'R' as tipo
                            FROM tbl_revenda
                            LEFT JOIN tbl_cidade USING (cidade)
                            WHERE tbl_revenda.nome ILIKE '$nome%'
                            )
                        ) as X";
	}
	//if ($ip=='200.246.168.156') echo nl2br($sql);
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>Consumidor '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
} else if (strlen($_GET["cpf"]) > 0 AND $login_fabrica <> 3) {
	$cpf = preg_replace('/\D/', '', $_GET['cpf']);
	if (strlen($_GET["tipo2"]) > 0 AND $_GET["tipo2"] == 'assistencia') {
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ DO POSTO AUTORIZADO</b>: <i>$cpf</i></font>";
		echo "<p>";

		$sql = "SELECT
							tbl_posto.posto                       AS id             ,
							tbl_posto.nome                        AS nome           ,
							tbl_posto.cnpj                        AS cpf_cnpj       ,
							tbl_posto.endereco                    AS endereco       ,
							tbl_posto.numero                      AS numero         ,
							tbl_posto.complemento                 AS complemento    ,
							tbl_posto.bairro                      AS bairro         ,
							tbl_posto.cep                         AS cep            ,
							tbl_posto.cidade                      AS nome_cidade    ,
							tbl_posto.fone                        AS fone           ,
							''                                    AS cidade         ,
							''                                    AS rg             ,
							tbl_posto.email                       AS email          ,
							tbl_posto.estado                      AS estado         ,
							'A'                                   AS tipo


			FROM            tbl_posto
			JOIN            tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE           tbl_posto.cnpj = '$cpf'
			AND             tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY        tbl_posto.nome";

	} else {
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CPF do consumidor</b>: <i>$cpf</i></font>";
		echo "<p>";

		$sql = "SELECT      tbl_cliente.*                 ,
							tbl_cidade.nome AS nome_cidade,
							tbl_cidade.estado
				FROM        tbl_cliente
				LEFT JOIN   tbl_cidade USING (cidade)
				WHERE       tbl_cliente.cpf = '$cpf'
				ORDER BY    tbl_cliente.nome";

		$sql = "SELECT	distinct
						id              ,
						nome            ,
						endereco        ,
						numero          ,
						complemento     ,
						bairro          ,
						cep             ,
						cidade          ,
						fone            ,
						cpf_cnpj        ,
						rg              ,
						email           ,
						nome_cidade     ,
						estado          ,
						tipo
					FROM (
							(
							SELECT tbl_hd_chamado_extra.hd_chamado as id ,
									tbl_hd_chamado_extra.nome         ,
									tbl_hd_chamado_extra.endereco     ,
									tbl_hd_chamado_extra.numero       ,
									tbl_hd_chamado_extra.complemento  ,
									tbl_hd_chamado_extra.bairro       ,
									tbl_hd_chamado_extra.cep          ,
									tbl_hd_chamado_extra.cidade       ,
									tbl_hd_chamado_extra.fone         ,
									tbl_hd_chamado_extra.cpf as cpf_cnpj ,
									tbl_hd_chamado_extra.rg           ,
									tbl_hd_chamado_extra.email        ,
									tbl_cidade.nome AS nome_cidade,
									tbl_cidade.estado        ,
									'C' as tipo
							FROM tbl_hd_chamado_extra
							JOIN tbl_hd_chamado using (hd_chamado)
							LEFT JOIN tbl_cidade USING (cidade)
							WHERE (tbl_hd_chamado_extra.cpf = '$cpf' OR tbl_hd_chamado_extra.cpf = '$xcpf')
							AND   fabrica_responsavel = $login_fabrica
							)union(
							SELECT tbl_revenda.revenda as id ,
									tbl_revenda.nome         ,
									tbl_revenda.endereco     ,
									tbl_revenda.numero       ,
									tbl_revenda.complemento  ,
									tbl_revenda.bairro       ,
									tbl_revenda.cep          ,
									tbl_revenda.cidade       ,
									tbl_revenda.fone         ,
									tbl_revenda.cnpj  as cpf_cnpj,
									'' as rg                 ,
									tbl_revenda.email        ,
									tbl_cidade.nome AS nome_cidade,
									tbl_cidade.estado        ,
									'R' as tipo
							FROM tbl_revenda
							LEFT JOIN tbl_cidade USING (cidade)
							WHERE tbl_revenda.cnpj = '$cpf'
							)
						) as X";
	}
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "<h1>CPF/CNPJ '$cpf' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}else if($login_fabrica==3){
		if(strlen($_POST['cpf'])>0) $cpf = strtoupper ($_POST['cpf']);
		else                        $cpf = strtoupper ($_GET['cpf']);

		if(strlen($_POST['os'])>0) $os = strtoupper ($_POST['os']);
		else                       $os = strtoupper ($_GET['os']);

		$xcpf = str_replace (".","",$cpf);
		$xcpf = str_replace ("-","",$xcpf);
		$xcpf = str_replace (",","",$xcpf);
		$xcpf = str_replace ("/","",$xcpf);
		$xcpf = str_replace (" ","",$xcpf);

		if(strlen($xcpf)>0) $busca = $xcpf;

		if(strlen($_POST['telefone'])>0) $telefone = strtoupper ($_POST['telefone']);
		else                             $telefone = strtoupper ($_GET['telefone']);
		if(strlen($telefone)>0) $busca = $telefone;

		if(strlen($_POST['cep'])>0) $cep = strtoupper ($_POST['cep']);
		else                        $cep = strtoupper ($_GET['cep']);

		$xcep = str_replace (".","",$cep);
		$xcep = str_replace ("-","",$xcep);
		$xcep = str_replace ("/","",$xcep);
		$xcep = str_replace (" ","",$xcep);

		if(strlen($xcep)>0) $busca = $xcep;

		if(strlen($_POST['atendimento'])>0) $atendimento = strtoupper ($_POST['atendimento']);
		else                                $atendimento = strtoupper ($_GET['atendimento']);
		if(strlen($atendimento)>0) $busca = $atendimento;

		if(strlen($_POST['os'])>0) $os = trim(strtoupper ($_POST['os']));
		else                       $os = trim(strtoupper ($_GET['os']));
		if(strlen($os)>0) $busca = $os;


		$tipo  = $_GET['tipo'];
		$cond_1 = " 1=1 ";

		if($tipo=="cpf"){
			//hd 45707
			$cond_1  = " replace(replace(tbl_hd_chamado_extra.cpf, '.',''),'-','') ILIKE '%$busca%'";
			$xcond_1 = " tbl_revenda.cnpj LIKE  '%$busca%' ";
			$titulo  = "CPF/CNPJ";
		}
		if($tipo=="telefone"){
			$cond_1 = " tbl_hd_chamado_extra.fone = '$busca' ";
			$xcond_1 = " tbl_revenda.fone like '%$busca%' ";
			$titulo  = "TELEFONE";
		}
		if($tipo=="cep"){
			$cond_1 = " tbl_hd_chamado_extra.cep = '$busca' ";
			$xcond_1 = " tbl_revenda.cep like '%$busca%' ";
			$titulo  = "CEP";
		}
		if($tipo=="atendimento"){
			$cond_1 = " 1 = 2 ";
			$xcond_1 = " 1 = 2 ";
		}

		if($tipo=="os"){
			$titulo  = "OS";
		}

		if(strlen($os)>0) $busca = $os;

		if(strlen($busca)>0 AND strlen($atendimento)==0){
		$busca = str_replace (".","",$busca);

			if($tipo =="os") { // HD 48508
				$sql="SELECT DISTINCT os   as id           ,
					consumidor_nome        as nome         ,
					consumidor_endereco    as endereco     ,
					consumidor_numero      as numero       ,
					consumidor_complemento as complemento  ,
					consumidor_bairro      as bairro       ,
					consumidor_cep         as cep          ,
					consumidor_cidade      as cidade       ,
					consumidor_fone        as fone         ,
					consumidor_cpf         as cpf_cnpj     ,
					''                     as rg           ,
					consumidor_email       as email        ,
					consumidor_cidade      as nome_cidade  ,
					consumidor_estado      as estado       ,
					consumidor_revenda     as tipo
					FROM tbl_os
					WHERE tbl_os.fabrica = $login_fabrica";

				$busca = strtoupper ($busca);

				$pos = strpos($busca, "-");
				if ($pos === false) {
					if(!ctype_digit($busca)){
						$sql .= " AND tbl_os.sua_os = '$busca' ";
					}else{
						$sql .= " AND (tbl_os.os_numero = '$busca' OR tbl_os.sua_os  = '$busca')";
					}
				}else{
					$conteudo = explode("-", $busca);
					$os_numero    = $conteudo[0];
					$os_sequencia = $conteudo[1];
					if(!ctype_digit($os_sequencia)){
						$sql .= " AND tbl_os.sua_os = '$busca' ";
					}else{
						$sql .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
					}
				}
				#echo nl2br($sql);
			}else{
				$sql = "SELECT DISTINCT id,
						nome            ,
						endereco        ,
						numero          ,
						complemento     ,
						bairro          ,
						cep             ,
						cidade          ,
						fone            ,
						cpf_cnpj        ,
						rg              ,
						email           ,
						nome_cidade     ,
						estado          ,
						tipo
					FROM (
							(
							SELECT tbl_hd_chamado_extra.hd_chamado as id ,
									tbl_hd_chamado_extra.nome         ,
									tbl_hd_chamado_extra.endereco     ,
									tbl_hd_chamado_extra.numero       ,
									tbl_hd_chamado_extra.complemento  ,
									tbl_hd_chamado_extra.bairro       ,
									tbl_hd_chamado_extra.cep          ,
									tbl_hd_chamado_extra.cidade       ,
									tbl_hd_chamado_extra.fone         ,
									tbl_hd_chamado_extra.cpf as cpf_cnpj ,
									tbl_hd_chamado_extra.rg           ,
									tbl_hd_chamado_extra.email        ,
									tbl_cidade.nome AS nome_cidade,
									tbl_cidade.estado        ,
									'C' as tipo
							FROM tbl_hd_chamado_extra
							JOIN tbl_hd_chamado using (hd_chamado)
							LEFT JOIN tbl_cidade USING (cidade)
							WHERE $cond_1
							AND   fabrica_responsavel= $login_fabrica
							)union(
							SELECT tbl_revenda.revenda as id ,
									tbl_revenda.nome         ,
									tbl_revenda.endereco     ,
									tbl_revenda.numero       ,
									tbl_revenda.complemento  ,
									tbl_revenda.bairro       ,
									tbl_revenda.cep          ,
									tbl_revenda.cidade       ,
									tbl_revenda.fone         ,
									tbl_revenda.cnpj  as cpf_cnpj,
									'' as rg                 ,
									tbl_revenda.email        ,
									tbl_cidade.nome AS nome_cidade,
									tbl_cidade.estado        ,
									'R' as tipo
							FROM tbl_revenda
							LEFT JOIN tbl_cidade USING (cidade)
							WHERE $xcond_1
							)
						) as X
						ORDER BY nome";
			}
		#echo nl2br($sql);
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			echo "<h1>$titulo '$busca' não encontrado</h1>";
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',2500);";
			echo "</script>";
			exit;
		}
	}
}
/*if (pg_numrows($res) == 1) {
	echo "<script language='javascript'>";
	echo "cliente.value     ='".pg_result($res,0,cliente)."'; ";
	echo "nome.value        ='".str_replace("'","",pg_result($res,0,nome))."'; ";
	echo "cpf.value         ='".pg_result($res,0,cpf)."'; ";
	echo "rg.value          ='".pg_result($res,0,rg)."'; ";
	echo "cidade.value      ='".pg_result($res,0,nome_cidade)."'; ";
	echo "fone.value        ='".pg_result($res,0,fone)."'; ";
	echo "endereco.value    ='".str_replace("'","",pg_result($res,0,endereco))."'; ";
	echo "numero.value      ='".pg_result($res,0,numero)."'; ";
	echo "complemento.value ='".pg_result($res,0,complemento)."'; ";
	echo "bairro.value      ='".pg_result($res,0,bairro)."'; ";
	echo "cep.value         ='".pg_result($res,0,cep)."'; ";
	echo "estado.value      ='".pg_result($res,0,estado)."'; ";
	if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
	echo "this.close(); ";
	echo "</script>";
	exit;
}
*/
if(pg_numrows($res)>0 AND strlen($atendimento)==0){
	echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0'>\n";

		echo "<TR bgcolor='#CCCCCC'>";
			echo "<TD><B>CPF</B></TD>";
			echo "<TD><B>Nome</B></TD>";
			echo "<TD><B>Tipo</B></TD>";
		echo "</TR>";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$cliente     = trim(pg_result($res,$i,id));
		$nome        = str_replace("'","",trim(pg_result($res,$i,nome)));
		$cpf         = trim(pg_result($res,$i,cpf_cnpj));
		$endereco    = str_replace ("'","",trim(pg_result($res,$i,endereco)));
		$numero      = trim(pg_result($res,$i,numero));
		$complemento = trim(pg_result($res,$i,complemento));
		$bairro      = trim(pg_result($res,$i,bairro));
		$cep         = trim(pg_result($res,$i,cep));
		$cidade      = trim(pg_result($res,$i,cidade));
		$fone        = trim(pg_result($res,$i,fone));
		$rg          = trim(pg_result($res,$i,rg));
		$email       = trim(pg_result($res,$i,email));
		$nome_cidade = trim(pg_result($res,$i,nome_cidade));
		$estado      = trim(pg_result($res,$i,estado));
		$tipo        = trim(pg_result($res,$i,tipo));
		if($tipo=="C"){
				$xtipo="Consumidor";
		}else{
			if ($tipo=="R") {
				$xtipo="Revenda";
			} else {
				$xtipo="Assistência";
			}
		}

		if($cor=='#FFFFFF') $cor = '#EEEEEE';
		else                $cor = '#FFFFFF';

		echo "<tr bgcolor='$cor'>\n";
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cpf</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			$retorno = $_GET['retorno'];
			echo "<a href=\"javascript: opener.document.location = '$retorno?cliente=$cliente' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: cliente.value='$cliente' ; nome.value='$nome' ; rg.value='$rg'; cidade.value='$nome_cidade' ; fone.value='$fone' ; endereco.value='$endereco' ; numero.value='$numero' ; complemento.value='$complemento' ; bairro.value='$bairro' ; cep.value='$cep' ; estado.value='$estado' ; ";
			echo ($tipo=='C') ? "if (typeof tipo_c !== 'undefined') tipo_c.click()" : '';
			echo ($tipo=='R') ? "if (typeof tipo_r !== 'undefined') tipo_r.click()" : '';
			echo ($altera_tipo!='t') ? "; tipo.value='$tipo' ":'';
			echo "; cpf.value = '$cpf'; email.value = '$email';";
			if ($_GET["proximo"] == "t") echo "janela.focus(); ";
			echo "this.close(); \">\n";
		}
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		echo "<td>";
            if ($_GET['forma'] == 'reload') {
			$retorno = $_GET['retorno'];
			echo "<a href=\"javascript: opener.document.location = '$retorno?cliente=$cliente' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: cliente.value='$cliente' ; nome.value='$nome' ; rg.value='$rg'; cidade.value='$nome_cidade' ; fone.value='$fone' ; endereco.value='$endereco' ; numero.value='$numero' ; complemento.value='$complemento' ; bairro.value='$bairro' ; cep.value='$cep' ; estado.value='$estado' ; cpf.value = '$cpf'; email.value = '$email';";
			echo ($tipo=='C') ? "if (typeof tipo_c !== 'undefined') tipo_c.click()" : '';
			echo ($tipo=='R') ? "if (typeof tipo_r !== 'undefined') tipo_r.click()" : '';
			echo ($tipo=='C') ? "tipo_c.click()" : "tipo_r.click()";
			echo ($altera_tipo!='t') ? "; tipo.value='$tipo' ;":'';
			if ($_GET["proximo"] == "t") echo "janela.focus(); ";
			echo "this.close(); \">\n";
		}
		echo "$xtipo</td>";

		echo "</tr>";

		if($login_fabrica <>3){
			$sql = "SELECT tbl_hd_chamado.hd_chamado,
							status                  ,
							TO_CHAR(data,'DD/MM/YYYY HH24:MI') AS data_at,
							categoria
					FROM tbl_hd_chamado
					JOIN  tbl_hd_chamado_extra USING(hd_chamado)
					WHERE hd_chamado           = $cliente
					AND   fabrica_responsavel = $login_fabrica
					ORDER BY data DESC";
			$at = pg_exec($con,$sql);
			if(pg_numrows($at)>0){
				echo "<tr bgcolor='$cor'>";
				echo "<td colspan='3'>";
					echo "<table  style='text-family:Verdana,sans; text-size:10px;'>";
					echo "<tr>";
					echo "<th>Atendimento</th>";
					echo "<th>Data</th>";
					echo "<th>Status</th>";
					echo "</tr>";
				for($j=0;$j<pg_numrows($at);$j++){
					$hd_chamado = pg_result($at,$j,hd_chamado);
					$status    = pg_result($at,$j,status);
					$data      = pg_result($at,$j,data_at);
					$categoria = pg_result($at,$j,categoria);//HD 55339

						echo "<tr>";
						echo "<td>";
						echo "<a href=\"javascript: opener.document.location = 'callcenter_interativo_new.php?callcenter=$hd_chamado#$categoria' ; this.close() ;\" > " ;
						echo "$hd_chamado</td>";
						echo "<td>$data</td>";
						echo "<td>$status</td>";
						echo "</tr>";

				}
				echo "</table>";
				echo "</td>";
				echo "</tr>";
			}
		}
	}
	echo "</table>\n";
}

if($login_fabrica == 3 AND strlen($atendimento)>0){

	if(is_numeric($atendimento)){
		$sql = "SELECT tbl_hd_chamado.hd_chamado,
						status                  ,
						TO_CHAR(data,'DD/MM/YYYY HH24:MI') AS data_at,
						categoria
				FROM tbl_hd_chamado
				JOIN  tbl_hd_chamado_extra USING(hd_chamado)
				WHERE hd_chamado           = $atendimento
				AND   fabrica_responsavel = $login_fabrica
				ORDER BY data DESC";
		$at = pg_exec($con,$sql);

		if (pg_numrows ($at) == 0) {
			echo "<h1>ATENDIMENTO '$atendimento' não encontrado</h1>";
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',2500);";
			echo "</script>";
			exit;
		}
		if(pg_numrows($at)>0){
			echo "<tr bgcolor='$cor'>";
			echo "<td colspan='3'>";
				echo "<table  style='text-family:Verdana,sans; text-size:10px;'>";
				echo "<tr>";
				echo "<th>Atendimento</th>";
				echo "<th>Data</th>";
				echo "<th>Status</th>";
				echo "</tr>";
			for($j=0;$j<pg_numrows($at);$j++){
				$hd_chamado = pg_result($at,$j,hd_chamado);
				$status = pg_result($at,$j,status);
				$data   = pg_result($at,$j,data_at);
				$categoria = pg_result($at,$j,categoria);

					echo "<tr>";
					echo "<td>";
					echo "<a href=\"javascript: opener.document.location = 'callcenter_interativo_new.php?callcenter=$hd_chamado#$categoria' ; this.close() ;\" > " ;
					echo "$hd_chamado</td>";
					echo "<td>$data</td>";
					echo "<td>$status</td>";
					echo "</tr>";

			}
			echo "</table>";
			echo "</td>";
			echo "</tr>";
		}
	}else{
		echo "<h1>ATENDIMENTO '$atendimento' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
	}
}

?>


</body>
</html>
