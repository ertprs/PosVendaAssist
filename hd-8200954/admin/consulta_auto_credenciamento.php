<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include '../helpdesk/mlg_funciones.php';
include 'funcoes.php';
//  Limpa a string para evitar SQL injection
if (!function_exists('anti_injection')) {
	function anti_injection($string) {
		$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
		return strtr(strip_tags(trim($string)), $a_limpa);
	}
}

if (!function_exists('checaCPF')) {
	function checaCPF ($cpf,$return_str = true) {
		global $con;	// Para conectar com o banco...
		$cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
		if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) false;
		$res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
		if ($res_cpf === false) {
			return ($return_str) ? pg_last_error($con) : false;
		}
		return $cpf;
	}
}
//----  Credenciar  ----
// Ex.: consulta_auto_credenciamento.php?posto=19940568000110&cf=86&credenciar=sim
if (anti_injection($_GET['credenciar'])=='sim') {
	$cnpj = anti_injection($_GET['posto']);    //  O parâmetro 'posto' vem com o CNPJ...
	$cf   = anti_injection($_GET['cf']);
	if ($login_fabrica != 10 and ($cf != $login_fabrica)) $msg_erro = "Está tentando credenciar um posto para um outro fabricante!";
	if (count($msg_erro)==0) {
		$fabrica_credenciamento = $cf;
		if (checaCPF($cnpf) != false) {             // Confere o CNPJ
		    $sql = "SELECT posto FROM tbl_posto WHERE cnpj = '$cnpj'";
		    $res = @pg_query($con, $sql);
		    if (!is_resource($res)) {
				$msg_erro[] = "Erro ao conferir os dados do cadastro do posto!";
				$posto = false;
			} else {
			    $posto = @pg_fetch_result($res,0,posto);
			}
		} else $msg_erro[] ="CNPJ INVÁLIDO!! De onde tirou esse nº??";
		if (is_numeric($posto)) {
			$sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $fabrica_credenciamento";
			if (@pg_num_rows(@pg_query($con,$sql)) !== 0) {
				$msg_erro[] = "O posto com CNPJ $cnpj já está no seu cadastro!";
			} else {
				$sql_extra ="SELECT nome_fantasia,
							 endereco, numero, complemento, bairro, cep, cidade, estado,
							 fone AS telefone, fax, contato, email
							FROM tbl_posto WHERE posto = $posto";
				if (is_resource($res_extra = @pg_query($con,$sql_extra))) extract(@pg_fetch_assoc($res_extra,0));
			    $sql = "INSERT INTO tbl_posto_fabrica (
							posto					,
							fabrica					,
							codigo_posto			,
							senha					,
							tipo_posto				,
							login_provisorio		,
							data_alteracao  		,
							credenciamento  		,
							contato_fone_comercial	,
							contato_email           ,
							contato_fax             ,
							contato_nome            ,
							contato_endereco        ,
							contato_numero          ,
							contato_complemento     ,
							contato_bairro          ,
							contato_cidade          ,
							contato_cep             ,
							contato_estado          ,
							nome_fantasia
 							) VALUES (
							$posto					,
							$fabrica_credenciamento	,
							'$cnpj'					,
							'*'						,
							'119'					,
							't'						,
							current_timestamp       ,
							'CREDENCIADO'           ,
							'$telefone'             ,
							'$email'				,
							'$fax'                  ,
                            '$contato'              ,
							'$endereco'             ,
							'$numero'               ,
							'$complemento'          ,
							'$bairro'               ,
							'$cidade'               ,
							'$cep'                  ,
							'$estado'               ,
							'$nome_fantasia'
						)";
				$res = @pg_query($con, $sql);
				if (!is_resource($res)) {
					$msg_erro[] = "Erro ao cadastrar o posto! (".pg_last_error($con).")";
				} else {
				    header("Location: posto_cadastro.php?posto=$posto");
				}
			}
		}
	}
}

//----  Validação, pesquisa, AJAX ----
$estados = array("AC" => "Acre",			"AL" => "Alagoas",			"AM" => "Amazonas",
				 "AP" => "Amapá",			"BA" => "Bahia",			"CE" => "Ceará",
				 "DF" => "Distrito Federal","ES" => "Espírito Santo",	"GO" => "Goiás",
				 "MA" => "Maranhão",		"MG" => "Minas Gerais",		"MS" => "Mato Grosso do Sul",
				 "MT" => "Mato Grosso",		"PA" => "Pará",				"PB" => "Paraíba",
				 "PE" => "Pernambuco",		"PI" => "Piauí",			"PR" => "Paraná",
				 "RJ" => "Rio de Janeiro",	"RN" => "Rio Grande do Norte","RO"=>"Rondônia",
				 "RR" => "Roraima",			"RS" => "Rio Grande do Sul","SC" => "Santa Catarina",
				 "SE" => "Sergipe",			"SP" => "São Paulo",		"TO" => "Tocantins");

// $linhas_padrao = "'TODAS-BRANCA-BRANCA','TODAS-MARRON-MARRON','TODAS-ELETROPORTÁTEIS-ELETROPORTÁTEIS','TODAS-INFORMÁTICA-INFORMÁTICA','TODAS-FERRAMENTAS-FERRAMENTAS'";
$linhas_padrao = "TODAS-BRANCA-BRANCA,TODAS-MARRON-MARRON,TODAS-ELETROPORTÁTEIS-ELETROPORTÁTEIS,TODAS-INFORMÁTICA-INFORMÁTICA,TODAS-FERRAMENTAS-FERRAMENTAS";

$sql_linhas = "SELECT '$login_fabrica_nome'||'-'||codigo_linha||'-'||nome AS linha, codigo_linha, nome AS nome_linha
				 FROM tbl_linha WHERE fabrica = $login_fabrica AND ativo IS TRUE";
$res_linhas = @pg_query($con, $sql_linhas);
if (is_resource($res_linhas) and @pg_num_rows($res_linhas) != false) {
	$temp_linhas = pg_fetch_all($res_linhas);
	foreach ($temp_linhas as $info_linha) {
        $linhas_[]	= $info_linha['linha'];
		$codigo		= $info_linha['codigo_linha'];
		$linhas_fabrica[$codigo] = $info_linha['nome_linha'];
	}
	$linhas_padrao = implode(',', $linhas_);
	unset($temp_linhas, $codigo, $linhas_);
}

$special_chars = array('á|â|à|Á|Â|À','é|ê|è|É|Ê|È','í|Í','ó|ô|ò|Ó|Ô|Ò','ú|Ú','ñ|Ñ','ç|Ç');
//  Funções comuns
if (!function_exists('iif')) {
	function iif($condition, $val_true, $val_false = "") {
		if (is_numeric($val_true) and is_null($val_false)) $val_false = 0;
		if (is_null($val_true) or is_null($val_false) or !is_bool($condition)) return null;
		return ($condition) ? $val_true : $val_false;
	}
}
if (!function_exists('is_email')) {
	function is_email($email=""){   // False se não bate...
		return (preg_match("/^([0-9a-zA-Z]+([_.-]?[0-9a-zA-Z]+)*@[0-9a-zA-Z]+[0-9,a-z,A-Z,.,-]*(.){1}[a-zA-Z]{2,4})+$/", $email));
	}
}
//  Função para conferir cada campo do $_POST, devolve 'false' ou o que colocar como último argumento
if (!function_exists('check_post_field')) {
	function check_post_field($fieldname, $returns = false) {
		if (!isset($_POST[$fieldname])) return $returns;
		$data = anti_injection($_POST[$fieldname]);
	// 	echo "<p><b>$fieldname</b>: $data</p>\n";
		return (strlen($data)==0) ? $returns : $data;
	}
}
if (!function_exists('p_echo')) {
	function p_echo ($str, $style = "") {echo "<p $style>".$str."</p>\n";}
}
if (!function_exists('pre_echo')) {
	function pre_echo ($str,$header="") {
		if ($header != "") p_echo ($header, " style='font-weight:bold'");
		echo "<pre>\n";
		print_r($str);
		echo "\n</pre>\n";
	}
}
function print_if($texto, $tag = 'p', $attr = '', $fechar_tag = true){
	if (strlen($texto)) echo "<$tag $attr>$exto".iif($fechar_tag,"</$tag>");
}
//----
function pg_where($campo,$valores,$numeric = false) {
//  Confere valores especiais
	if (is_null($valores))	return "$campo IS ".iif((!is_bool($numeric)),$numeric).'NULL';
	if ($valores===true)	return "$campo IS ".iif((!is_bool($numeric)),$numeric).'TRUE';
	if ($valores===false)	return "$campo IS ".iif((!is_bool($numeric)),$numeric).'FALSE';
	if ($valores=='')       return $valores;
//  Converte valores CSV para array
	if (!is_array($valores) && strpos($valores, ',')!==false) {
		$a_valores = array_map(trim, explode(',',$valores));
	} else {
	    $a_valores = iif((is_array($valores)),$valores,array($valores));
	}
	$sep = ($numeric) ? ',' : "','";    // separa com vírgulas se for numérico, coloca entre aspas se não for
    $tmp_ret = implode($sep, array_filter($a_valores));
	if (!$numeric) $tmp_ret = "'$tmp_ret'";
	if ($campo == '') return $tmp_ret;  // para devolver só os valores separados por vírgula, setar '$campo' como ''
	return (count($a_valores)>1) ? $tmp_ret = "$campo IN ($tmp_ret)" : "$campo = $tmp_ret";
}

//---- Início AJAX
if ($_POST['q'] != '') {
	header("Expires: 0");
	header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
	header("Pragma: no-cache, public");

	if (is_bool($q=check_post_field('q'))) {
		'ko|Invalid query';
		exit;
	}
	if (is_bool($info=check_post_field('info'))) {
		'ko|No field requested';
		exit;
    }
// echo "$q<br>$info<br>\n";
	switch ($q) {
        case 'linhas':
	        if (substr($info, -1)==',') $info = substr($info,0,strlen($info)-1);
			if ($login_fabrica == 10) {
			    $fabricas = 'AND '.pg_where('tbl_fabrica.nome',$info,false);
				$sql = "SELECT DISTINCT tbl_fabrica.nome||'-'||tbl_linha.codigo_linha||'-'||tbl_linha.nome AS linha ".
					   "FROM tbl_linha JOIN tbl_fabrica USING(fabrica) ".
					   "WHERE tbl_fabrica.ativo_fabrica IS TRUE ".
					   	 "$fabricas ".
						 "AND tbl_linha.fabrica NOT IN (10,46)".
					   "ORDER BY linha";
// 				echo "ko|$sql";exit;
			} else {
			    echo 'ko|Ação não permitida!';
			    exit;
			}
        	break;
        case 'cidades':
            $sql = "SELECT DISTINCT ".
				   "CASE WHEN cidade=cidade_pesquisa THEN cidade ELSE cidade_pesquisa END AS cidade ".
				   "FROM tbl_posto WHERE ".pg_where('estado',$info);
        	break;
        case 'bairros':
	        if (substr($info, -1)==',') $info = substr($info,0,strlen($info)-1);
            $estado = check_post_field('estados');
            $sql = "SELECT DISTINCT UPPER(bairro) FROM tbl_posto WHERE ".
					pg_where('cidade',$info).
					iif(($estado),' AND ' . pg_where('estado',$estado),'');
        	break;
	    default:
			echo 'ko|Invalid data';
			exit;
	    	break;
    }
	$res = @pg_query($con, $sql);//echo "ko|".pg_last_error($con)."\n".$sql;
// pre_echo($sql);

	if (is_resource($res)) {
//  Caso o fabricante não tenha linhas próprias... que não é o normal...
		while ($temp_dados = @pg_fetch_result($res,$i++,0)) {
		    if ($temp_dados!==false) $tmp_dados[] = iif(($q=='linhas'),$temp_dados,mb_strtoupper($temp_dados));
		}
		if (count($tmp_dados)) echo implode(',',array_unique($tmp_dados));
	} else echo "ko|".pg_last_error($con)."\n|".$sql;
	exit;
}

//  Lista de opstos que atendem aos critérios do filtro
if ($_POST['acao']=='contar') {
//  Pesquisa no banco usando RegExp e Arrays de forma nativa. Exemplo:
/*	SELECT ARRAY_TO_STRING(REGEXP_SPLIT_TO_ARRAY(marca_ser_autorizada,E'\\W\\s+'),',') AS marcas_ser_autorizada
		FROM tbl_posto_extra
		WHERE marca_ser_autorizada IS NOT NULL
		AND REGEXP_SPLIT_TO_ARRAY(UPPER(marca_ser_autorizada),E'\\W\\s+') && ARRAY['BRITâNIA','BRITANIA']
*/
	$sql_regexp = "E'".addslashes("\\s?[,|\\/|\\.|\\-|\\.|\\'']\\s?")."','i'";    //  Para facilitar a sintaxe, leitura e edição
//  Converte a lista de fábricas para pesquisar em cláusula WHERE... se for fábrica 10-Telecontrol
	if ($login_fabrica==10) {
	    $s_fabricas = check_post_field('sel_fabrica');
        if (substr($s_fabricas, -1)==',') $s_fabricas = substr($s_fabricas,0,strlen($s_fabricas)-1);
	    if ($s_fabricas === false) $s_fabricas = '';
// 		foreach($special_chars as $letras_acento) {
// 			$s_fabricas = preg_replace("/[$letras_acento]/", "[$letras_acento]", $s_fabricas);
// 		}
	} else {
		$s_fabricas = $login_fabrica_nome;
	}
	if ($s_fabricas != '') {
		$s_fabricas = mb_strtoupper(pg_where('',$s_fabricas));
		$sql_fabricas = " AND (REGEXP_SPLIT_TO_ARRAY(UPPER(TRANSLATE(marca_ser_autorizada,".
						"'áâàãäéêèëíîìïóôòõúùüçÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ',".
						"'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')),".
						$sql_regexp.") && ARRAY[$s_fabricas] ";
		$sql_fabricas.= " OR REGEXP_SPLIT_TO_ARRAY(UPPER(TRANSLATE(fabricantes,".
						"'áâàãäéêèëíîìïóôòõúùüçÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇ',".
						"'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')),".
						$sql_regexp.") && ARRAY[$s_fabricas]) ";
	} else $sql_fabricas = '';
//  Pula o posto se já está ou foi credenciado do fabricante
	if ($login_fabrica != 10) $sql_fabricas .= " AND $s_fabricas NOT IN ".
	                                " (SELECT UPPER(tbl_fabrica.nome) ".
									"FROM tbl_posto_fabrica JOIN tbl_fabrica USING(fabrica) ".
									"WHERE posto = tbl_posto_extra.posto)";

//  Interpreta a lista de linhas selecionadas, se tiver, e converte em cláusula WHERE
    if (!is_array($a_linhas = $_POST['linhas[]']) or $_POST['linhas[]'][0] == 'null') {
		$sql_linhas = '';
	} else {
		$s_linhas = mb_strtoupper(pg_where('',$a_linhas));
		$sql_linhas = " AND REGEXP_SPLIT_TO_ARRAY(UPPER(tbl_posto_extra.linhas),$sql_regexp) && ARRAY[$s_linhas]";
	}
	$estado = check_post_field('estado','');
	$cidade = check_post_field('cidade','');
	$bairro = check_post_field('bairro','');
        if (substr($estado, -1)==',') $estado = substr($estado,0,strlen($estado)-1);
        if (substr($cidade, -1)==',') $cidade = substr($cidade,0,strlen($cidade)-1);
        if (substr($bairro, -1)==',') $bairro = substr($bairro,0,strlen($bairro)-1);

	$sql  = "SELECT posto,cnpj,nome AS razao_social, ".
			"endereco||', '||numero AS endereco, ".
			"complemento, cep, bairro, cidade, estado, ".
			"LOWER(email) AS email, contato, fone, ".
			"latitude AS long, longitude AS lat ".
			"FROM tbl_posto JOIN tbl_posto_extra USING(posto) ".
			"WHERE marca_ser_autorizada IS NOT NULL ".
			iif(($estado!=''),"  AND ".pg_where('estado',$estado)).
			iif(($cidade!=''),"  AND ".pg_where('cidade',$cidade)).
            iif(($bairro!=''),"  AND ".pg_where('bairro',$bairro)).
            $sql_fabricas.
            $sql_linhas.
			" ORDER BY estado,cidade,razao_social";
	$res = @pg_query($con,$sql);
	if (!is_resource($res)) {
		echo "ko|".pg_last_error($con)."<br>Não foi possível fazer uma lista de postos. Contate com o Help-Desk se persistir este problema.";
		exit;
	}
    $num_postos = pg_num_rows($res);
	if (!$num_postos) {
	    echo "ko|Sem resultados para esta pesquisa...";
	    exit;
	}
    for ($i = 0; $i < $num_postos; $i++) {
    	$dados_posto[pg_fetch_result($res, $i, posto)] = pg_fetch_assoc($res, $i);/*Joga os dados de cada posto num array com índice no id do posto*/
    }
    $cf = $login_fabrica;
	if ($login_fabrica == 10 and $s_fabricas) {
		$res_f = pg_query($con,"SELECT fabrica FROM tbl_fabrica WHERE UPPER(nome) = $s_fabricas");
		if (is_resource($res_f)) $cf = pg_fetch_result($res_f,0,fabrica);
	}
?>
	<table id='tbl_postos' class="tabela" cellspacing="1" border="0">
	    <caption class="titulo_tabela"><?=$num_postos?> posto(s) localizado(s)</caption>
      
        <tr class="titulo_coluna">
            <th width="100">CNPJ</th>
            <th width="140">Razão Social</th>
            <th width="240">Endereço</th>
            <th width="60">Bairro</th>
            <th width="80">Cidade</th>
            <th width="20">UF</th>
            <th width="95">Telefone</th>
            <th width="60">Contato</th>
            <th width="10%">E-Mail</th>
            <th>Credenciar</th>
		</tr>
		
<?	foreach ($dados_posto as $info_posto) {
	    extract($info_posto);   // Cria uma variável por cada ítem de nome $key e o valor do ítem do array
		$map_url  = "http://maps.google.com/maps?".
					"f=q&source=s_q&hl=pt-BR&q=$endereco $complemento,$cep,$cidade,$estado,$pais&ie=windows-1252";
		if (is_numeric($lat) and is_numeric($long)) $map_url .= "&ll=$lat,$long";
		$endereco_mapa = "<a title='Localizar no mapa' href='$map_url' target='_blank' style='position:relative'>".
						"<img src='http://www.google.com/options/icons/maps.gif' width='16' stlye='float:left'>".
						"$endereco $complemento</a>";

		$dir_fotos = '../credenciamento/fotos';
		$tem_fotos = (count(glob("$dir_fotos/$posto*"))>0);
		$ico_foto  = ($tem_fotos) ? '<img src="./imagens_admin/camera_foto.gif" alt="fotos" titles="Há fotos de este posto">' : '' ;
		echo "\t\t<tr>\n";
		echo "\t\t\t<td><span class='link info' alt='$posto'>$cnpj</span></td>\n";
		echo "\t\t\t<td title='$razao_social' valign='middle'>$ico_foto $razao_social</td>\n";
		echo "\t\t\t<td title='$endereco'>$endereco_mapa</td>\n";
		echo "\t\t\t<td title='$bairro'>$bairro</td>\n";
		echo "\t\t\t<td title='$cidade'>$cidade</td>\n";
		echo "\t\t\t<td>$estado</td>\n";
		echo "\t\t\t<td>$fone</td>\n";
		echo "\t\t\t<td title='$contato'>$contato</td>\n";
		echo "\t\t\t<td title='$email'><a href='mailto:$email'>$email</a></td>\n";
		echo "\t\t\t<td><a class='frm' href='$PHP_SELF?posto=$cnpj&cf=$cf&credenciar=sim' target='_blank'>Credenciar</a></td>\n";
		echo "\t\t</tr>\n";
	}
	
	echo "\t<table>\n";
	exit;
}

if ($_GET['info']=='extra') {
	$posto = anti_injection($_GET['posto']);
//	define(SQL_REGEXP_SPC, "E'".addslashes("\\s?[,+\\s?|\\/|\\.|\\-|\\'']\\s?")."','i'");    //  Para facilitar a sintaxe, leitura e edição
	define(SQL_REGEXP_SPC, "E'".addslashes("[,|\\.\{1\}|\\-|\/|\\'']+\\s?")."','i'");    //  Para facilitar a sintaxe, leitura e edição
	define(SQL_REGEXP, "E'".addslashes("[,|\\/|\\.|\\-|\\'']+\\s?")."','i'");    //  Para facilitar a sintaxe, leitura e edição
	$sql = "SELECT posto,cnpj,nome AS razao_social,
			endereco||', '||numero AS endereco,
			complemento, cep, bairro, cidade, estado, pais,
			LOWER(email) AS email, contato, fone, fax,
			latitude AS long, longitude AS lat,
			ARRAY_TO_STRING(REGEXP_SPLIT_TO_ARRAY(linhas,".SQL_REGEXP_SPC."),', ') AS linhas,
			data_modificado AS ultima_alteracao,
			funcionario_qtde AS qtde_de_funcionarios, os_qtde,
			ARRAY_TO_STRING(REGEXP_SPLIT_TO_ARRAY(atende_cidade_proxima,".SQL_REGEXP."),', ') AS atende_cidade_proxima, descricao,
			ARRAY_TO_STRING(REGEXP_SPLIT_TO_ARRAY(fabricantes,".SQL_REGEXP_SPC."),', ') AS trabalha_com,
			ARRAY_TO_STRING(REGEXP_SPLIT_TO_ARRAY(marca_nao_autorizada,".SQL_REGEXP_SPC."),', ') AS marcas_nao_autorizada,
			ARRAY_TO_STRING(REGEXP_SPLIT_TO_ARRAY(marca_ser_autorizada,".SQL_REGEXP_SPC."),', ') AS marcas_ser_autorizada
			FROM tbl_posto JOIN tbl_posto_extra USING(posto)
			WHERE posto = $posto";
	$res  = @pg_query($con, $sql);
	if (is_resource($res)) {
		$info_extra = pg_fetch_assoc($res, 0);
		extract($info_extra);

//      Interpreta ou formata alguns valores...
		if (is_email($email)) $email = "<a href='mailto:$email' title='Enviar mensagem para $email'>$email</a>";
		$fone	= preg_replace('/.*(\d\d).*(\d{4}).*(\d{4})/','($1) $2-$3',$fone);
		$fax	= preg_replace('/.*(\d\d).*(\d{4}).*(\d{4})/','($1) $2-$3',$fax);
		$a_linhas = explode(', ', $linhas);
        foreach ($a_linhas as $codigo_linha) {
        	$temp_linhas[] = (isset($linhas_fabrica[$codigo_linha])) ? $linhas_fabrica[$codigo_linha] : $codigo_linha;
        }
		$linhas = implode(', ', $temp_linhas);
?>
    <div id="ei_header">
		Informações preenchidas pelo posto no formulário...<br>
	</div>
    <div id="fechar">X</div>
    <div id='ei_container'>
		<div>
	        <dl id="dados">
	            <dt>Razão Social</dt>
	                <dd><?=$razao_social?></dd>
	            <dt>CNPJ</dt>
	                <dd><?=$cnpj?></dd>
	            <dt>Endereço</dt>
	                <dd>
						<?echo $endereco.iif((strlen(trim($complemento))>0),", ".$complemento);?><br>
						CEP: <?=preg_replace('/(\d{5})(\d{3})/','$1-$2',$cep)?>,&nbsp;<?=$bairro?><br>
						<?echo "$cidade - $estado";?>
					</dd>
			</dl>
			<dl id='contato'>
	            <dt>Pessoa de contato</dt>
	                <dd><?=$contato?></dd>
	            <dt>Telefones</dt>
	                <dd>Fone: <?=$fone?><br>Fax:<?=$fax?></dd>
	            <dt>E-Mail</dt>
	                <dd><?=$email?></dd>
			</dl>
		</div>
	    <p>&nbsp;</p>
		<div stlye='height: 40%'>
			<dl id='mais_info'>
	            <dt>Qtde. de Funcionários</dt>
	                <dd><?=$qtde_de_funcionarios?></dd>
	            <dt>Atende Outras Cidades?</dt>
	                <dd><?=$atende_cidade_proxima?></dd>
	            <dt>OS por mês</dt>
	                <dd><?=$os_qtde?></dd>
	            <dt>Aturorizada de:</dt>
	                <dd><?=$trabalha_com?></dd>
			</dl>
<?	if ($login_fabrica == 10) {?>
			<dl id='info_fabricas'>
	            <dt>Melhor sistema</dt>
	                <dd><?=$melhor_sistema?></dd>
	            <dt>Gostaria trabalhar para:</dt>
	                <dd><?=$marcas_ser_autorizada?></dd>
	            <dt>NÃO gostaria trabalhar para:</dt>
	                <dd><?=$marcas_nao_autorizada?></dd>
			</dl>
<?}?>
			<dl>
				<dt>Descrição:</dt>
			    <dd><?=$descricao?></dd>
<?	if ($linhas != '') {	?>
				<dt>Linhas que pode atender:</dt>
			    <dd><?=$linhas?></dd>
<?}?>
			</dl>
<?	if ($login_fabrica == 10) echo "<p>Última atualização do cadastro: $ultima_alteracao</p>";?>
		</div>
	    <div id='fotos'>
<?
//---- Confere se tem fotos do posto, a qtde. e mostra
		$dir_fotos = '../credenciamento/fotos';
		$uri_fotos = '/assist/credenciamento/fotos/';
		$desc_fotos= array(1 => 'Fachada', 'Recepção', 'Oficina');
		$a_fotos = glob("$dir_fotos/$posto*");
		if (count($a_fotos)>0) { ?>
	    <h2>Fotos do posto</h2>
<?			foreach ($a_fotos as $foto) {
				$url_foto = $uri_fotos.basename($foto);
				$desc     = 'Foto da '.$desc_fotos[substr($url_foto,-5,1)];
?>			<img src='<?=$url_foto?>' alt='Fotos do posto' title='<?=$desc?>'>
<?			}
		} else  {
				echo "<p>Não há imagens do posto</p>";
		}
?>		</div>
	</div>
	</div>
<?	} else {    ?>
    <div id="ei_header">
		Informações preenchidas pelo posto no formulário...<br>
	</div>
    <div id="fechar">X</div>
    <div id='ei_container'>
<?	    echo "ko|Não foi possível obter as informações. Tente novamente e, se continuar a acontecer, contate com nosso Help-Desk.";
		pre_echo($sql,pg_last_error($con));
		echo "	</div>\n";
	    exit;
	}
	exit;
}
//---- Fim AJAX

//  Pega as linhas (só do fabricante, ou todas se for Telecontrol, depois pode afinar se o usuário selecionar fábricas)
if ($login_fabrica != 10) { // Linhas da fábrica logada
	$sql = "SELECT '$login_fabrica_nome'||linha||'-'||nome FROM tbl_linha WHERE fabrica = $login_fabrica";
} else {
	if ($_POST['sel_fabrica'] != '') { // Só las linhas das fábricas já escolhidas
	    $tmp_fabricas = array();
	    $info = $_POST['sel_fabrica'];
        if (strpos($info, ',')!==false) $tmp_fabricas = array_map(trim, explode(',',$info));
        $fabricas = 'AND tbl_fabrica.nome '.iif((count($tmp_fabricas)>0),"IN ('".implode("','",$tmp_fabricas)."')", "= '$info'");
	}
	$sql = "SELECT DISTINCT tbl_fabrica.nome||'-'||tbl_linha.linha||'-'||tbl_linha.nome AS linha
				FROM tbl_linha
				JOIN tbl_fabrica USING(fabrica)
			WHERE tbl_fabrica.ativo_fabrica IS TRUE $fabricas
			  AND tbl_linha.fabrica NOT IN (10,46)
			ORDER BY linha";
}
$res = pg_query($con,$sql);
if (!is_resource($res)) {
	echo pg_last_error($con);
} else {
	while ($temp_linha = @pg_fetch_result($res,$i++,linha)) {
		$tmp_linhas[] = $temp_linha;
	}
	if (is_array($tmp_linhas)) $linhas = implode(',',$tmp_linhas);
}

//  Caso o fabricante não tenha linhas próprias... o que não deveria acontecer...
if (strpos($linhas, ',') === false) $linhas = $linhas_padrao;

$layout_menu = "gerencia";
$title = 'PESQUISA DE POSTOS AUTO-CREDENCIADOS';
include 'cabecalho.php';

?>
<style type="text/css">
    <!--
    body {font: normal normal 12px/16px Verdana,Arial,Helvetica,sans-serif}
	div.oculto {text-align: left;padding: 8px 16px;background-color: #f0f0fa;}

	form {
		margin: 2em 0 1em 0;
		color: black;
		font-weight:bold;
		font-size: 10.5px;
		width: 640px;
	}
	fieldset {
	    padding: 0 0 0.5em 0;
		border: 1px solid #d2e4fc;
		position:relative;
	}
	form fieldset p.legend {
	    position:relative;
	    display: block;
	    text-align: center;
	    margin: 0;
	    padding:0;
	    padding-top: 5px;
	    top: -10px;
	    left: 0;
	    width: 100%;
		height: 2em;
	    background-color: #596d9b;
	    color: white;
	    font-size: 14px;
	    font-weight: bold;
	}
	#resultados {
	    width: 1000px;
	    /*display: none;*/
		overflow-x: auto;
	}
	table#tbl_postos {
		position: relative;
		table-layout: fixed;
		width: 1000px;
		margin: 10px;
	    background-color: white;
		background-color: #F1F4FA; 
		padding: 0;
	    border-collapse: collapse;
		border:1px solid #596d9b;
	}
	table#tbl_postos caption {
		font: normal bold 1.2em "Trebuchet MS",Helvetica,Arial,sans-serif;
		padding-bottom: 4px;
		border-bottom: 1px solid white;
		text-align: center;
	}
	table#tbl_postos thead {
        height: 29px;
        color: white;
        margin: 0;
        padding-top: 0;
		background-image: -webkit-gradient(linear,  0 0, 0 100%,
												from(#3e83c9),
													color-stop(0.75,#60B0F0),
													color-stop(0.95,white),
												to(white));
	}
	table#tbl_postos th {
        height: 28px;
        margin: -5px 0 0;
        padding-top: 1px;
		vertical-align:top;
	}
	table#tbl_postos td {
/*		background: #cccccc;
		font-weight: bold;*/
		padding: 1px 2px;
		text-align: left;
	    color: black;
		white-space: nowrap;
		overflow: hidden;
		cursor: default;
	}
	table#tbl_postos tbody tr:nth-child(2n) {
		background-color: #F7F5F0;
	}

	.link {font-weight:bold;cursor:pointer}
	label {display:inline-block;_zoom:1;width: 20%;text-align:right;vertical-align:top}
	#extra_info {
	    display: none;
	    opacity: 0.85;
	    background-color: #ffffff;
	    position: fixed;
		text-align: left;
	    top:   5%;
	    left:   5%;
	    width: 90%;
	    height:85%;
        padding-top: 32px;
	    border: 2px solid #5090dd;
        border-radius: 8px;
        -moz-border-radius: 8px;
        overflow: hidden;
	}
	#extra_info:hover {
		opacity: 1;
		box-shadow: 3px 3px 3px #ccc;
	}
	#extra_info #ei_container div {
	    width: 100%;
	    margin-left: 2%;
		min-height: 150px;
		float: left;
	}
	#extra_info #ei_header {
		position: absolute;
		top:	0;
		left:	0;
		margin:	0;
		width: 100%;
		height: 30px;
		background-image: url('./imagens_admin/azul.gif');    /* IE */
		background-image: -moz-linear-gradient(top, #3e83c9, #60B0F0 27px, white);
		background-image: -webkit-gradient(linear,  0 0, 0 100%,
												from(#3e83c9),
													color-stop(0.80,#60B0F0),
													color-stop(0.95,white),
												to(white));
	    padding: 2px 1em;
	    color: white;
	    font: normal bold 13px Segoe UI, Verdana, MS Sans-Serif, Arial, Helvetica, sans-serif;
	}
	#extra_info #ei_container {
		margin: 1px;
		padding-bottom: 1ex;
		overflow-y: auto;
        overflow-x: hidden;
	    height: 100%;
        background-color: #fdfdfd;
	}
	#extra_info #fechar {
		position: absolute;
		top: 3px;
		right: 5px;
		width: 16px;
		height:16px;
		font: normal bold 12px Verdana, Arial, Helvetica, sans-serif;
		color:white;
	    cursor: pointer;
		margin:0;padding:0;
		vertical-align:top;
		text-align:center;
		background-color: #f44;
		border:	1px solid #d00;
		border-radius: 3px;
		-moz-border-radius: 3px;
		box-shadow: 2px 2px 2px #900;
		-moz-box-shadow: 1px 1px 1px #900;
		-webkit-box-shadow: 2px 2px 2px #900;
	}
	#extra_info dl {
		width: 43%;
		position: relative;
		float: left;
		clear: none;
		margin: 0 3% 10px 3%;
        padding: 5px 2px;
		border:	2px solid #bbb;
		background-color: #f5f5f5;
        box-shadow: 2px 2px 4px #b0b0b0;
        -moz-box-shadow: 2px 2px 4px #b0b0b0;
        -webkit-box-shadow: 2px 2px 4px #b0b0b0;
        filter:progid:DXImageTransform.Microsoft.DropShadow(color='#b0b0b0', offX=2px, offY=2px,enabled=true,positive='false');
        border-radius: 5px;
        -moz-border-radius: 5px;
	}
	#extra_info dl dt {
		display: block;
		padding: 5px;
		margin: 0.3em 0 0 0.5em;
		color: #999;
		font-weight: bold;
		text-shadow: 2px 2px 3px #ccc;
		width: 60%;
		border-top-left-radius: 5px;
		border-top-right-radius:5px;
		-moz-border-radius: 5px 5px 0 0;
		background-color: #ddd;
		border-bottom: 1px dashed #c5c5c5;
	}
	#extra_info dl dd {
		display: block;
		padding: 5px 8px 5px 10px;
		margin: 0 0.5em 0.5em 0.5em;
		margin-bottom: 0.5em;
		color: #3373BF;
		width: 90%;
		min-height: 1.2em;
		border-bottom-left-radius: 6px;
		border-bottom-right-radius:6px;
		border-top-right-radius:6px;
		-moz-border-radius: 0 6px 6px 6px;
		background-color: #e5e5e5;
	}
	#extra_info #ei_container #fotos {
	position: relative;
	width: 100%;
	margin: 0 10px 1ex 0;
	text-align: center;
	}
	#extra_info #ei_container #fotos img {
		position: relative;
		bottom: 5px;
/*		float: left;    */
		width: 150px;
		max-width: 150px;
		max-height:90px;
		margin-right: 2%;
		border: 4px solid white;
        border-radius: 5px;
        -moz-border-radius: 5px;
        box-shadow: 2px 2px 4px #999;
        -moz-box-shadow: 2px 2px 4px #999;
        -webkit-box-shadow: 2px 2px 4px #999;
        filter:progid:DXImageTransform.Microsoft.DropShadow(color='#999999', offX=2px, offY=2px,enabled=true,positive='false');
        cursor: pointer;
	}
	#extra_info #ei_container #fotos img:hover {
		bottom: 15px;
	    width: 80%;
		max-width: 640px;
		max-height:400px;
	    border-width: 8px;
        box-shadow: 4px 4px 7px #999;
        -moz-box-shadow: 4px 4px 7px #999;
        -webkit-box-shadow: 4px 4px 7px #999;
        z-index: 100;
        cursor: default;
        bottom: 5px;
	}
    //-->

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
<link rel="stylesheet" href="/js/jquery.autocomplete.css" type="text/css">
<script src="/js/jquery-1.4.2.min.js" type="text/javascript"></script>
<script src="/js/jquery.autocomplete.min.js" type="text/javascript"></script>
<script type="text/javascript">
<?
if ($multifabrica = ($login_fabrica == 10)) {
	$sql_fabricas = "SELECT DISTINCT nome FROM tbl_fabrica WHERE ativo_fabrica IS TRUE AND fabrica NOT IN (10,46)";
	$temp_fabricas = pg_fetch_all(pg_query($con, $sql_fabricas));
	foreach ($temp_fabricas as $fabrica_temp) {
		$fabricas_tc[] = '"'.$fabrica_temp['nome'].'"';
	}
	echo "	var fabricas = [".implode(",",$fabricas_tc)."];\n";
} else echo "	var fabricas = ['$login_fabrica_nome'];";
	echo "	var linhas = [".pg_where('',$linhas)."];\n";
	$multifabrica = ($multifabrica)?'true':'false'; // Deveria ir no 'autrocomplete' do 'se_fabricas', mas por enquanto está inhabilitado
?>
$().ready(function() {
	function atualizaLinhas(linhas){
	    $('#linhas').html('');
	    arr = $.map(linhas, function(linha, idx){
	        linha_data = linha.split('-');
			$('#linhas').append("<option title='"+linha_data[0]+"' value='"+linha_data[1]+"'>"+linha_data[2]+'</option>');
	    });
	}
	atualizaLinhas(linhas);
	$('#sel_fabrica').autocomplete(fabricas, {
		multiple: false,
		mustMatch: true,
		autoFill: true
	})
	.change(function () {
/*  Atualiza as linhas  */
        if ($('#sel_fabrica').val().length > 0) {
            $('#linhas').html('<option>Atualzando linhas para '+$(this).val()+'</option>');
            $.post('<?=$PHP_SELF?>','q=linhas&info='+$(this).val(),function(data) {
				if (data.substr(0,2) != 'ko') {
					$('#linhas').html('');
				    linhas_fabricas = data.split(',');
				    if (linhas_fabricas.length == 0) {
				        linhas_fabricas = linhas.split(',');
					}
					atualizaLinhas(linhas_fabricas);
				} else {
				    var erro = data.split('|');
				    alert(erro[1]);
				}
			});
		}
	});

	if ($('#estado').val().length != 2) {
	    $('#cidade').attr('disabled','disabled');
	    $('#bairro').attr('disabled','disabled');
	}

	$('#estado').change(function () {
		$.post('<?=$PHP_SELF?>',
			  {'q':'cidades',
			   'info':$(this).val()
			   },
				function(cidades) {
					if (cidades.substr(0,2) != 'ko') {
						$('#cidade').autocomplete(
							cidades.split(','),
							{
							multiple: true,
							mustMatch: true,
							autoFill: true
							}
					)
					.attr('title','Selecione uma ou várias cidades do estado de '+$('#estado option:selected').text())
					.removeAttr('disabled');
					$('#bairro').removeAttr('disabled');
				} else {
				    var erro = cidades.split('|');
				    alert(erro[1]);
				}
	    });
	});
	$('#cidade').blur(function () {
	    var cidades = $('#cidade').val();
		if (cidades != '') {
			$.post('<?=$PHP_SELF?>',
				{'q':'bairros',
				 'info':cidades,
				 'estados':$('#estado').val()
			    },
				function(bairros) {
					if (bairros.substr(0,2) != 'ko') {
						$('#bairro').autocomplete(
							bairros.split(','),
							{
							multiple: true,
							mustMatch: true,
							autoFill: true
							}
						)
						.attr('title','Selecione um bairro de '+$('#cidade').val())
						.removeAttr('disabled');
					} else {
					    var erro = bairros.split('|');
					    alert(erro[1]);
					}
			});
		}
	});
	$('form[name=frm_autocred]').submit(function (){
	    $('#resultados').slideUp('fast').empty().show('normal').html('<h3>Aguarde enquanto os dados são recuperados e formatados...</h3>');
		$.post('<?=$PHP_SELF?>',
			  {'estado':$('#estado').val(),
			   'cidade':$('#cidade').val(),
			   'bairro':$('#bairro').val(),
<?	if ($login_fabrica == 10) echo "				'sel_fabrica':$('#sel_fabrica').val(),\n"?>
			   'linhas':$('#linhas').val(),
			   'acao':'contar'
			   },
			   function(data) {
				if (data.substr(0,2) != 'ko') {
				    $('#resultados').html(data).slideDown('fast');
// 			alert(data);return false;
                $('#tbl_postos tr td span.info').click(function () {
					var posto = $(this).attr('alt');
				    $('#extra_info').hide().empty()
									.slideDown('normal')
									.html('<h3 style="text-align: center">Aguarde enquanto os dados são recuperados e formatados...</h3')
									.load('<?=$PHP_SELF?>','info=extra&posto='+posto,function() {
						$('#fechar').click(function () {
							$('#extra_info').slideUp('normal');
						});
					}).show(300);
                }).attr('title','Mais informações do posto');
			} else {
			    var erro = data.split('|');
			    alert(erro[1]);
                $('#resultados').slideUp('fast').delay(300).empty().hide(5);
			}
    	});
    	return false;
	});
});
</script>

<!--[if lt IE 9]>
<script src="http://ie7-js.googlecode.com/svn/version/2.1(beta3)/IE9.js"></script>
<![endif]-->

<? if (count($msg_erro)) { ?>
<table width="700" border="0" cellpadding="2" cellspacing="0" align="center" class="error">
	<tr>
		<td>
			<?echo implode("<br>\n",$msg_erro);?>
		</td>
	</tr>
</table>
<br>
<?
include 'rodape.php';
exit;
}
?>

	<center>
	<form name="frm_autocred" id="frm_autocred"
	   enctype="application/x-www-form-urlencoded"
		action="<?=$PHP_SELF?>" accept-charset="windows-1252"
	   enctype="application/x-www-form-urlencoded"
		 
		method="post" class="formulario" style="width:700px;">
			
	
	
	
		<table align="center" class="formulario" width="700" border="0">
			<tr style="background-color:#596d9b;font: bold 14px Arial;color:#FFFFFF;text-align:center;"><td colspan="4">Parâmetros de Pesquisa</td></tr>
			<tr>
				<td width="25">&nbsp;</td>
				<td>Estado</td>
				<td>Cidade(s)</td>
				<td><label for='bairro' id='bairro_label' title='Digite o bairro'>Bairro</label></td>
			</tr>
			<tr>
				<td width="55">&nbsp;</td>
				<td>
					<select name="estado" class='frm' id="estado" <?$readonly?>>
						<option value=""></option>
					<?
					  foreach ($estados as $sigla=>$nome_estado) {
						echo "\t\t\t<option value='$sigla'";
						if ($sigla == $estado) echo " selected";
						echo ">$nome_estado</option>\n";
					  }
					?>		
					</select>
				</td>
				<td><input class='frm' type="text" name='cidade' id='cidade' value='<?=$_POST['cidade']?>'></td>
				<td><input class="frm" type="text" name="bairro" id='bairro' value="<?=$bairro?>" title='Digite o bairro'></td>
			</tr>

			<tr>
				<td width="25">&nbsp;</td>
				<td colspan="3"></td>
			</td>
			<tr>
				<td width="25">&nbsp;</td>
				<td>
					<?	if ($login_fabrica==10) {?>
							<label for="sel_fabrica" >Fabricante</label>
					<? }?>
				</td>
				<td colspan="2">Linha(s)</td>
			</tr>
			<tr>
				<td width="25">&nbsp;</td>
				<td valign="top">
					 <? if ($login_fabrica==10) {?>
								<input class='frm marcas' type="text" name='sel_fabrica' id='sel_fabrica' value='<?=$_POST['sel_fabrica']?>'>
					 <? } else { ?>
							<input type="hidden" name='sel_fabrica' id='sel_fabrica' value='<?=$login_fabrica_nome?>'>
				     <?	} ?>
				</td>
				<td colspan="2">
					<select name="linhas[]" id="linhas" class='frm' disabled='disabled' multiple size='4' <?$readonly?> style="width:320px;">
					</select>
				</td>
			</tr>

			<tr>
				<td colspan="4"></td>
			</td>

			<tr>
				<td colspan="2" valign="middle" align="right" width="50%">
					<button name="acao" id="acao" value="contar" onclick="javascript: document.frm_opiniao_posto.submit(contar)">Consultar</button>
				</td>
				<td align="left" valign="top" colspan="2" width="50%">
					<button name="acao" id="acao" value="contar" onclick="window.location='<? echo $PHP_SELF?>'">Limpar</button>
				</td>
			</tr>
		</table>

       
	</form>
	<div id='resultados'></div>
	<div id='extra_info'>
	</div>
	</center>
<?
include 'rodape.php';
?>
