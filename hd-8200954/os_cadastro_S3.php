<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

#HD 424887 - INICIO
/*

A variavel abaixo será para identificar as fábricas que terão o campo "Defeito_reclamado" sem integridade.
Por enquanto só a Fricon, quando precisar mais fábricas é só colocar adicionar nessa variável que funciona.
*/

$fabricas_defeito_reclamado_sem_integridade = array(52);

#HD 424887 - FIM

// ajax hd 342651
if ($_GET['consulta_split'] == 's') {

	$referencia = $_GET['referencia'];

	$sql = "SELECT linha FROM tbl_produto WHERE referencia = '" . $referencia . "'";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$ref = pg_result($res, 0, 0);
		echo $ref == 623 ? 't' : 'f';
	} else {
		echo 'Produto nao encontrado';
	}

	exit;

}

if ($_GET['verifica_familia'] == 'sim') {

	$prod_referencia = $_GET['produto_referencia_familia'];

	$sql = "SELECT tipo_posto FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
	$res = pg_query($con, $sql);

	$tipo_posto = pg_result($res, 0, 0);

	if (!in_array($login_fabrica, array(40)))
	{
		$sql = "select tbl_produto.produto,
					   tbl_familia.familia
				from tbl_produto
				join tbl_familia using(familia)
				where tbl_familia.paga_km is TRUE
				and tbl_familia.fabrica=$login_fabrica
				and tbl_produto.referencia='$prod_referencia' ";
	}
	else
	{
		$sql = "select tbl_familia.familia
				from tbl_produto
				join tbl_familia on tbl_familia.familia = tbl_produto.familia
				where tbl_produto.referencia = '$prod_referencia'
			   ";
	}

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		if (!in_array($login_fabrica, array(40)))
		{
			header("Content-Type: text/html; charset=ISO-8859-1");

			echo "<option value=\"\"></option><option value=\"21\">01 - Garantia (Com Deslocamento)</option>";
			if ($tipo_posto == 174) echo "<option value=\"22\">02 - Instalação</option>";
			echo "<option value=\"23\">03 - Garantia (Sem Deslocamento)</option>";
		}
		else
		{
			echo pg_result($res,0,familia);
		}

	} else {

		header("Content-Type: text/html; charset=ISO-8859-1");

		if ($tipo_posto == 174)	echo "<option value=\"\"></option><option value=\"22\">02 - Instalação</option>";
		echo "<option value=\"23\">03 - Garantia (Sem Deslocamento)</option>";

	}

	exit;

}

if ($login_fabrica == 11) {
    session_start();
}

//HD 234135
if (in_array($login_fabrica, array(3))) {

	$usa_revenda_fabrica      = true;
	$revenda_fabrica_status   = $_POST["revenda_fabrica_status"];
	$revenda_fabrica_pesquisa = $_POST["revenda_fabrica_pesquisa"];

} else {

	$usa_revenda_fabrica = false;

}

if (!empty($_COOKIE['debug'])) $debug = $_COOKIE['debug'];

include_once 'helpdesk/mlg_funciones.php';

$bd_locacao = array(36,82,83,84,90);// Tipo Posto locação para Black & Decker

if ($login_fabrica == 1 and (in_array($login_tipo_posto, $bd_locacao))) {
    header("Location: os_cadastro_locacao.php");
    exit;
}

if ($login_fabrica == 28) {
    header("Location: os_consulta_lite.php");
    exit;
}

/*  Para testes da tela de pesquisa:
	Quando for testar uma ou mais telas de pesquisa, usar o mesmo "sufixo" da tela de pesquisa com
	a sos_cadastro_tudo, Ex., se usamos 'test1' para a tela de pesquisa em testes:
	pesquisa_numero_serie_test1.php
						 ^^^^^^
	Fazer uma cópia da tela os_cadastro_tudo com o mesmo "sufixo":
	cp os_cadastro_tudo os_cadastro_tudo_test1.php
										^^^^^^
	Este bloco de código detecta o sufixo da 'os_cadastro_tudo', procura se tem uma 'pesquisa_x_sufixo'
	e se achar, vai usar ela e não a que está em produção. Igualmente tem que criar uma cópia da
	tela os_cadastro_tudo, mas não há perigo depois de "esquecer" o sufixo se for fazer alguma alteração.
*/
if (preg_match('/os_cadastro(?:_tudo)?(.*).php/', $PHP_SELF, $a_suffix)) {

	$suffix = $a_suffix[1];

	if (file_exists("pesquisa_numero_serie$suffix.php"))   $ns_suffix = $suffix;
	if (file_exists("pesquisa_revenda$suffix.php"))	       $pr_suffix = $suffix;
	if (file_exists("ajax_produto$suffix.php"))            $ap_suffix = $suffix;
	if (file_exists("ajax_defeito_constatado$suffix.php")) $ap_suffix = $suffix;

}

/*  MLG - 19/11/2009 - HD 171045 - Cont.
*	MLG - 03/12/2010 - HD 321132
*   	Inicializa o array, variáveis e funções.
		Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
		Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
		Para saber se tem anexo:temNF($os, 'bool');
		Para saber se 2º anexo: temNF($os, 'bool', 2);
		Para mostrar a imagem:  echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb]'></a>
								echo temNF($os, , 'url'); // Devolve a imagem (<img src='imagem'>)
								echo temNF($os, , 'link', 2); // Devolve um link da 2ª imagem
*/
include_once('anexaNF_S3.php');

$cpf_obrigatorio        = array(7, 43, 45, 80); // Fábricas que exigem seja colocado o CPF na OS
$calcula_km				= (($login_fabrica == 1  AND $login_posto == 6359) OR
			               ($login_fabrica == 15 AND $login_posto == 6359) OR
			               ($login_fabrica == 24 AND $login_tipo_posto == 256) OR
							in_array($login_fabrica, array(30,46,50,56,57,72,74,85,88,90,91,92, 35)));

$combo_tipo_atendimento = (($login_fabrica == 1  AND $login_posto == 6359) OR
                           ($login_fabrica == 24 AND $login_tipo_posto == 256) OR
                           in_array($login_fabrica, array( 7,15, 19, 20, 30, 35, 40, 46, 50, 56, 58, 72, 74, 85, 88, 90, 91, 92, 96)));

include 'funcoes.php';

if (!function_exists('date_to_timestamp')) {

    function date_to_timestamp($fecha='hoje') { // $fecha formato YYYY-MM-DD H24:MI:SS ou DD-MM-YYYY H24:MI:SS

        if ($fecha == "hoje") $fecha = date('Y-m-d H:i:s');

        list($date, $time)         = explode(' ', $fecha);
        list($year, $month, $day)  = preg_split('/[\/|\.|-]/', $date);

        if (strlen($year) == 2 and strlen($day) == 4) list($day,$year) = array($year,$day); // Troca a ordem de dia e ano, se precisar
        if ($time == "") $time = "00:00:00";

        list($hour, $minute, $second) = explode(':', $time);
        return mktime($hour, $minute, $second, $month, $day, $year);

    }

}

/* MLG HD 175044    */
/*  14/12/2009 - Alteração direta, colquei conferência de 'funcion exists', porque mesmo que o include
                 e 'exit' esteja antes da declaração da função, ela é declarada na primeira passagem
                 do interpretador. */
if (!function_exists('checaCPF')) {
    function checaCPF ($cpf,$return_str = true) {
        global $con, $login_fabrica;// Para conectar com o banco...
        $cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
        //  23/12/2009 HD 186382 - a função pula as pré-OS anteriores à hoje...
        if (($login_fabrica==52 or $login_fabrica == 30 or $login_fabrica == 88)  and
            strlen($_REQUEST['pre_os'])>0 and
            date_to_timestamp($_REQUEST['data_abertura']) < strtotime('2009-12-24')) return $cpf;
        if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) false;

        $res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
        if ($res_cpf === false) {
            return ($return_str) ? pg_last_error($con) : false;
        }
        return $cpf;
    }
}

if (!function_exists('checaFone')) {
    function checaFone ($telefone) {
		if($telefone == 'null' or empty($telefone)) {
			return false;
		}

		if (!preg_match('|\(?[1-9]{2}\)? ?[2-9]{1}[0-9]{3}\-?[0-9]{4}|', $telefone)) {
			return false;
		}
		for($n =0;$n<=9;$n++) {
			$verifica_fone = substr_count($telefone,"$n");
			if($verifica_fone >=10) {
				return false;
			}
		}
		return $telefone;
	}
}

/* HD 35521 */
if (strlen(trim($_GET['pre_os']))>0){
    $pre_os         = trim($_GET['pre_os']);
    $produto_serie  = trim($_GET['serie']);
    $hd_chamado     = trim($_GET['hd_chamado']);
} elseif (strlen(trim($_POST['pre_os']))>0) {
    $pre_os         = trim($_POST['pre_os']);
    $produto_serie  = trim($_POST['serie']);
    $hd_chamado     = trim($_GET['hd_chamado']);
}


if ($pre_os == 't') {

    $sqllinha =    "SELECT tbl_linha.informatica
				FROM    tbl_posto_linha
				JOIN    tbl_linha USING (linha)
				WHERE   tbl_posto_linha.posto = $login_posto
				AND     tbl_linha.informatica = 't'
				AND     tbl_linha.fabrica = $login_fabrica
				LIMIT 1";

    $reslinha = pg_query($con,$sqllinha);

    if (pg_num_rows($reslinha) > 0) {
        $linhainf = trim(pg_fetch_result($reslinha, 0, 'informatica')); //linha informatica
    }

}

if ($_GET["ajax"] == "sim") {

    $referencia = $_GET["produto_referencia"];

    $sql = "SELECT linha
			FROM tbl_produto
			JOIN tbl_linha USING(linha)
			WHERE fabrica  = $login_fabrica
			AND referencia ='$referencia'";

    $res   = pg_query($con,$sql);
    $linha = pg_fetch_result ($res,0,0);

    if ($linha == 3 AND $login_fabrica == 3) {
        echo "ok|Mascara: LLNNNNNNLNNL<br />
                L: Letra<br />
                N: Número";
    }

    exit;

}

if ($_GET["ajax"] == "true" AND $_GET["buscaInformacoes"] == "true") {

    $referencia = trim($_GET["produto_referencia"]);
    $serie      = trim($_GET["serie"]);

    if (strlen($referencia) > 0) {

        $sql = "SELECT produto, capacidade, divisao
                FROM tbl_produto
                JOIN tbl_linha USING(linha)
                WHERE fabrica  = $login_fabrica
                AND referencia = '$referencia'";

        $res = @pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {

            $produto    = trim(pg_fetch_result($res, 0, 'produto'));
            $capacidade = trim(pg_fetch_result($res, 0, 'capacidade'));
            $divisao    = trim(pg_fetch_result($res, 0, 'divisao'));

            if (strlen($serie) > 0) {

                $sql = "SELECT capacidade, divisao, versao
                        FROM tbl_os
                        WHERE fabrica  = $login_fabrica
                        AND   posto    = $login_posto
                        AND   produto  = $produto
                        AND   serie    = '$serie' ;";

                $res = @pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {

                    $capacidade = trim(pg_fetch_result($res, 0, 'capacidade'));
                    $divisao    = trim(pg_fetch_result($res, 0, 'divisao'));
                    $versao     = trim(pg_fetch_result($res, 0, 'versao'));

                    echo "ok|$capacidade|$divisao|$versao";
                    exit;

                }

            }

            echo "ok|$capacidade|$divisao|$versao";
            exit;

        }

    }

    echo "nao|nao";
    exit;

}

if (strlen($_GET["produto_referencia"]) > 0 AND $_GET["produto_troca"] == "sim") {

    $referencia = trim($_GET["produto_referencia"]);

    $sql  = "SELECT produto
            FROM tbl_produto
            JOIN tbl_linha USING(linha)
            WHERE fabrica = $login_fabrica
            AND   referencia ='$referencia'
            AND   troca_obrigatoria IS TRUE";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        echo "sim";
    }

    exit;

}

if ($_GET["ajax"] == "true" AND $_GET["buscaPreOS"] == "true") {#HD 38369

    $serie           = trim($_GET["serie"]);
    $hd_chamado      = trim($_GET["hd_chamado"]);
    $hd_chamado_item = trim($_GET["hd_chamado_item"]);

    if (strlen($hd_chamado_item) > 0) {
		$sql_and = " AND hd_chamado_item = $hd_chamado_item ";
    }

    if (strlen($serie) > 0 or strlen($hd_chamado) > 0) {

        if ($login_fabrica <> 52 and $login_fabrica <> 30 and $login_fabrica <> 96) {

            $sql = "SELECT tbl_hd_chamado_extra.hd_chamado,
                        tbl_hd_chamado_extra.nome,
                        tbl_hd_chamado_extra.endereco ,
                        tbl_hd_chamado_extra.numero ,
                        tbl_hd_chamado_extra.complemento ,
                        tbl_hd_chamado_extra.bairro ,
                        tbl_hd_chamado_extra.cep ,
                        tbl_hd_chamado_extra.fone ,
						tbl_hd_chamado_extra.reclamado AS reclamado_historico ,
                        tbl_hd_chamado_extra.fone2 ,
                        tbl_hd_chamado_extra.email ,
                        tbl_hd_chamado_extra.cpf ,
                        tbl_hd_chamado_extra.rg ,
                        tbl_cidade.nome                                    AS cidade_nome,
                        tbl_cidade.estado                                  AS estado,
                        tbl_produto.referencia                             AS produto_referencia,
                        tbl_produto.descricao                              AS produto_nome,
						tbl_produto.voltagem                               As produto_voltagem,
                        tbl_defeito_reclamado.defeito_reclamado            AS defeito_reclamado,
                        tbl_defeito_reclamado.descricao                    AS defeito_reclamado_descricao,
                        tbl_hd_chamado_extra.defeito_reclamado_descricao   AS defeito_reclamado_descricao2,
                        to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') AS data_nf,
                        to_char(tbl_hd_chamado.data,'DD/MM/YYYY')          AS data_abertura,
                        to_char(current_date,'DD/MM/YYYY')                 AS data_atual,
                        tbl_hd_chamado_extra.nota_fiscal                   AS nota_fiscal,
                        tbl_hd_chamado_extra.os                            AS os,
                        tbl_os.sua_os                                      AS sua_os,
                        tbl_os.data_fechamento                             AS data_fechamento,
                        tbl_hd_chamado_extra.qtde_km,
                        tbl_hd_chamado.admin,
                        tbl_hd_chamado.cliente_admin,
                        /*HD HD 204082: Recuperar dados da revenda da pré-os*/
                        tbl_hd_chamado_extra.revenda_cnpj,
                        tbl_hd_chamado_extra.revenda_nome,
						tbl_hd_chamado_extra.tipo_atendimento
                FROM tbl_hd_chamado
                JOIN tbl_hd_chamado_extra   ON tbl_hd_chamado.hd_chamado  = tbl_hd_chamado_extra.hd_chamado
                LEFT JOIN tbl_produto       ON tbl_produto.produto        = tbl_hd_chamado_extra.produto
                LEFT JOIN tbl_cidade        ON tbl_cidade.cidade          = tbl_hd_chamado_extra.cidade
                LEFT JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto
                    AND tbl_posto_fabrica.fabrica = $login_fabrica
                LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
                LEFT JOIN tbl_os            ON tbl_os.os = tbl_hd_chamado_extra.os
                WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
                AND   tbl_hd_chamado_extra.posto         = $login_posto
				/* 425985: Tinha na condição o número de série e ninguém soube explicar porque, está desde sempre ai. Se alguma fábrica reclamar e for corrigir, falar com Ébano ou Tulio antes. */
                AND   tbl_hd_chamado_extra.hd_chamado = $hd_chamado
                ORDER BY tbl_hd_chamado.data DESC ";

        } else {

            $sql = "SELECT    tbl_hd_chamado_extra.hd_chamado,
                        tbl_hd_chamado_extra.nome,
                        tbl_hd_chamado_extra.endereco ,
                        tbl_hd_chamado_extra.numero ,
                        tbl_hd_chamado_extra.complemento ,
                        tbl_hd_chamado_extra.bairro ,
                        tbl_hd_chamado_extra.cep ,
                        tbl_hd_chamado_extra.fone ,
                        tbl_hd_chamado_extra.fone2 ,
                        tbl_hd_chamado_extra.email ,
                        tbl_hd_chamado_extra.cpf ,
                        tbl_hd_chamado_extra.rg ,
                        tbl_cidade.nome                                    AS cidade_nome,
                        tbl_cidade.estado                                  AS estado,
                        tbl_produto.referencia                             AS produto_referencia,
                        tbl_produto.descricao                              AS produto_nome,";
			if ($login_fabrica == 96){
				$sql .= "tbl_produto.referencia_fabrica                    AS referencia_fabrica,";
			}

			$sql .="
                        tbl_defeito_reclamado.defeito_reclamado            AS defeito_reclamado,
                        tbl_defeito_reclamado.descricao                    AS defeito_reclamado_descricao,
                        tbl_hd_chamado_item.defeito_reclamado_descricao   AS defeito_reclamado_descricao2,
                        to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') AS data_nf,
                        to_char(tbl_hd_chamado.data,'DD/MM/YYYY')          AS data_abertura,
                        tbl_hd_chamado_extra.nota_fiscal                   AS nota_fiscal,
                        to_char(current_date,'DD/MM/YYYY')                 AS data_atual,
                        tbl_hd_chamado_extra.os                            AS os,
                        tbl_os.sua_os                                      AS sua_os,
                        tbl_os.data_fechamento                             AS data_fechamento,
                        tbl_hd_chamado_extra.qtde_km,
                        tbl_hd_chamado.admin,
                        tbl_hd_chamado.cliente_admin,
                        /*HD HD 204082: Recuperar dados da revenda da pré-os*/
                        tbl_hd_chamado_extra.revenda_cnpj,
                        tbl_hd_chamado_extra.revenda_nome,
						tbl_hd_chamado_extra.tipo_atendimento
                FROM tbl_hd_chamado
                JOIN tbl_hd_chamado_extra   ON tbl_hd_chamado.hd_chamado  = tbl_hd_chamado_extra.hd_chamado
                JOIN tbl_hd_chamado_item    ON tbl_hd_chamado.hd_chamado  = tbl_hd_chamado_item.hd_chamado
                LEFT JOIN tbl_produto       ON tbl_produto.produto        = tbl_hd_chamado_item.produto
                LEFT JOIN tbl_cidade        ON tbl_cidade.cidade          = tbl_hd_chamado_extra.cidade
                LEFT JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto
                AND tbl_posto_fabrica.fabrica = $login_fabrica
                LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_item.defeito_reclamado
                LEFT JOIN tbl_os            ON tbl_os.os = tbl_hd_chamado_extra.os
                WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
                $sql_and
                AND   tbl_hd_chamado_extra.posto         = $login_posto
				/* 425985: Tinha na condição o número de série e ninguém soube explicar porque, está desde sempre ai. Se alguma fábrica reclamar e for corrigir, falar com Ébano ou Tulio antes. */
                AND   tbl_hd_chamado_extra.hd_chamado = $hd_chamado
                ORDER BY tbl_hd_chamado.data DESC ";

        }

        $res  = @pg_query($con,$sql);
        $sql2 = $sql;

        if (pg_num_rows($res) == 0) {

            $sql = "SELECT  '' AS hd_chamado,
                            '' AS  nome,
                            '' AS  endereco ,
                            '' AS  numero ,
                            '' AS  complemento ,
                            '' AS  bairro ,
                            '' AS  cep ,
                            '' AS  fone ,
                            '' AS  fone2 ,
                            '' AS  email ,
                            to_char(current_date,'DD/MM/YYYY') as data_atual,
                            '' AS  cpf ,
                            '' AS  rg ,
                            ''                                 AS cidade_nome,
                            ''                                 AS estado,
                            tbl_produto.referencia             AS produto_referencia,";
			if ($login_fabrica == 96){
				$sql .= "   tbl_produto.referencia_fabrica     AS referencia_fabrica,";
			}
			$sql .="
                            tbl_produto.descricao              AS produto_nome,
							tbl_produto.voltagem               AS produto_voltagem,
                            ''                                 AS defeito_reclamado,
                            ''                                 AS defeito_reclamado_descricao,
                            ''                                 AS data_nf,
                            ''                                 AS data_abertura,
                            ''                                 AS nota_fiscal,
                            ''                                 AS os,
                            ''                                 AS sua_os,
                            ''                                 AS data_fechamento,
                            /*HD HD 204082: Recuperar dados da revenda da pré-os*/
                            '' AS revenda_cnpj,
                            '' AS revenda_nome,
							'' AS tipo_atendimento
                    FROM tbl_numero_serie
                    JOIN tbl_produto      ON tbl_produto.produto = tbl_numero_serie.produto
                    JOIN tbl_linha        ON tbl_linha.linha        = tbl_produto.linha
                    WHERE tbl_numero_serie.fabrica = $login_fabrica
                    AND   tbl_linha.fabrica        = $login_fabrica
                    AND   upper(tbl_numero_serie.serie)         = '$serie'
                    ORDER BY tbl_numero_serie.data_venda DESC ";

            $res = @pg_query($con,$sql);

        }

        if (pg_num_rows($res) > 0) {

            $hd_chamado             = trim(pg_fetch_result($res,0,hd_chamado));
            $consumidor_nome        = trim(pg_fetch_result($res,0,nome));
            $consumidor_endereco    = trim(pg_fetch_result($res,0,endereco));
            $consumidor_numero      = trim(pg_fetch_result($res,0,numero));
            $consumidor_complemento = trim(pg_fetch_result($res,0,complemento));
            $consumidor_bairro      = trim(pg_fetch_result($res,0,bairro));
            $consumidor_cep         = trim(pg_fetch_result($res,0,cep));
            $consumidor_fone        = trim(pg_fetch_result($res,0,fone));
            $consumidor_celular     = trim(pg_fetch_result($res,0,fone2));
            $consumidor_email       = trim(pg_fetch_result($res,0,email));
            $consumidor_cpf         = trim(pg_fetch_result($res,0,cpf));
            $consumidor_cidade      = trim(pg_fetch_result($res,0,cidade_nome));
            $consumidor_estado      = trim(pg_fetch_result($res,0,estado));
            $produto_referencia     = trim(pg_fetch_result($res,0,produto_referencia));
			if ($login_fabrica == 96){
            	$referencia_fabrica     = trim(pg_fetch_result($res,0,'referencia_fabrica'));
			}
			if ($login_fabrica == 81){
				$produto_voltagem       = trim(pg_fetch_result($res,0,'produto_voltagem'));
			}
            $produto_descricao      = trim(pg_fetch_result($res,0,'produto_nome'));
            $defeito_reclamado      = trim(pg_fetch_result($res,0,defeito_reclamado));
            $defeito_reclamado_descricao    = trim(pg_fetch_result($res,0,defeito_reclamado_descricao));
            $defeito_reclamado_descricao2    = trim(pg_fetch_result($res,0,defeito_reclamado_descricao2));
            $data_atual             = trim(pg_fetch_result($res,0,data_atual));
            $data_abertura          = trim(pg_fetch_result($res,0,data_abertura));
            $data_nf                = trim(pg_fetch_result($res,0,data_nf));
            $nota_fiscal            = trim(pg_fetch_result($res,0,nota_fiscal));
            $qtde_km                = trim(pg_fetch_result($res,0,qtde_km));
            $os                     = trim(pg_fetch_result($res,0,os));
            $sua_os                 = trim(pg_fetch_result($res,0,sua_os));
            $data_fechamento        = trim(pg_fetch_result($res,0,data_fechamento));
            $admin                  = trim(pg_fetch_result($res,0,admin));
            $cliente_admin          = trim(pg_fetch_result($res,0,cliente_admin));
			$tipo_atendimento       = trim(pg_fetch_result($res,0,'tipo_atendimento'));

			if ($login_fabrica <> 52 and $login_fabrica <> 30 and $login_fabrica <> 96) {
				$reclamado_historico = trim(pg_fetch_result($res, 0, 'reclamado_historico'));
			}

            //HD 204082: Carregar revenda do chamado
            if ($login_fabrica >= 81 || $login_fabrica == 46) {
                $revenda_nome = trim(pg_fetch_result($res, 0, 'revenda_nome'));
                $revenda_cnpj = trim(pg_fetch_result($res, 0, 'revenda_cnpj'));
            }

            /* Retorno:
                ok
                id do campo ## valor
                id do campo ## valor
                .
                A funções explode e coloca os valores nos campos da OS
            */
                //HD 204082: Carregar revenda do chamado
            if (strlen($os)==0 or strlen($data_fechamento)>0){
                echo "ok|consumidor_nome##$consumidor_nome|consumidor_endereco##$consumidor_endereco|consumidor_numero##$consumidor_numero|consumidor_complemento##$consumidor_complemento|consumidor_bairro##$consumidor_bairro|consumidor_cep##$consumidor_cep|consumidor_fone##$consumidor_fone|consumidor_celular##$consumidor_celular|consumidor_email##$consumidor_email|consumidor_cpf##$consumidor_cpf|consumidor_cidade##$consumidor_cidade|consumidor_estado##$consumidor_estado|produto_referencia##$produto_referencia|produto_descricao##$produto_descricao|data_nf##$data_nf|nota_fiscal##$nota_fiscal|revenda_nome##$revenda_nome|revenda_cnpj##$revenda_cnpj|tipo_atendimento##$tipo_atendimento|";

				if ($login_fabrica == 3) {
					echo "obs##$reclamado_historico|";
				}


				if ($login_fabrica == 96) {
					echo "referencia_fabrica##$referencia_fabrica|";
				}

				if ($login_fabrica == 81) {
					echo "produto_voltagem##$produto_voltagem|";
				}

                if (strlen($defeito_reclamado_descricao2) > 0) {
                    echo "defeito_reclamado_descricao##$defeito_reclamado_descricao2|";
                }

                if (strlen($defeito_reclamado_descricao) > 0) {
                    echo "defeito_reclamado_descricao##$defeito_reclamado_descricao|";
                }

                if (strlen($defeito_reclamado) > 0) {
                    echo "defeito_reclamado##$defeito_reclamado|";
                }

                if ($login_fabrica == 80) {
                    echo "data_abertura##$data_atual|";
                }

				if ($login_fabrica == 3) {
                    echo "data_abertura##$data_abertura|";
                }

                if ($login_fabrica == 52 or $login_fabrica == 30 or $login_fabrica == 96) {

                    if (strlen($cliente_admin)) {

                        $sql_cliente = "SELECT  nome,
                                                cnpj,
                                                endereco,
                                                numero,
                                                bairro,
                                                cep,
                                                cidade,
                                                estado,
                                                fone
                                        FROM tbl_cliente_admin
                                        WHERE cliente_admin = $cliente_admin";

                        $res_cliente = pg_query($con,$sql_cliente);

                        $nome_cliente      = pg_fetch_result($res_cliente, 0, nome);
                        $cnpj_cliente      = pg_fetch_result($res_cliente, 0, cnpj);
                        $endereco_cliente  = pg_fetch_result($res_cliente, 0, endereco);
                        $numero_cliente    = pg_fetch_result($res_cliente, 0, numero);
                        $bairro_cliente    = pg_fetch_result($res_cliente, 0, bairro);
                        $cep_cliente       = pg_fetch_result($res_cliente, 0, cep);
                        $cidade_cliente    = pg_fetch_result($res_cliente, 0, cidade);
                        $estado_cliente    = pg_fetch_result($res_cliente, 0, estado);
                        $fone_cliente      = pg_fetch_result($res_cliente, 0, fone);

                    } else {

                        $nome_cliente      = "";
                        $cnpj_cliente      = "";
                        $endereco_cliente  = "";
                        $numero_cliente    = "";
                        $bairro_cliente    = "";
                        $cep_cliente       = "";
                        $cidade_cliente    = "";
                        $estado_cliente    = "";
                        $fone_cliente      = "";

                    }

                	echo "admin##$admin|cliente_admin##$cliente_admin|data_abertura##$data_abertura|revenda_nome##$nome_cliente|revenda_cnpj##$cnpj_cliente|revenda_fone##$fone_cliente|revenda_endereco##$endereco_cliente|revenda_cep##$cep_cliente|revenda_bairro##$bairro_cliente|revenda_cidade##$cidade_cliente|revenda_estado##$estado_cliente|qtde_km##$qtde_km|";

                }

            } else {
                echo "nao|Já existe uma OS em aberto para este número de série. ($sua_os)";
                exit;
            }

        }

    }

    echo "nao|nao";
    exit;

}

if ($_GET["ajax"] == "true" AND $_GET["buscaValores"] == "true") {

    $referencia = trim($_GET["produto_referencia"]);

    if (strlen($referencia) > 0) {

        $sql = "SELECT produto, capacidade, divisao
                FROM tbl_produto
                JOIN tbl_linha USING(linha)
                WHERE fabrica  = $login_fabrica
                AND referencia ='$referencia'";

        $res = @pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {

            $produto = trim(pg_fetch_result($res, 0, 'produto'));

            $sql = "SELECT  taxa_visita,
                            hora_tecnica,
                            valor_diaria,
                            valor_por_km_caminhao,
                            valor_por_km_carro,
                            regulagem_peso_padrao,
                            certificado_conformidade
                    FROM    tbl_familia_valores
                    JOIN    tbl_produto USING(familia)
                    WHERE   tbl_produto.produto = $produto";

            $res = pg_query ($con,$sql);

            if (pg_num_rows($res) > 0) {

                $taxa_visita              = number_format(trim(pg_fetch_result($res,0,taxa_visita)),2,',','.');
                $hora_tecnica             = number_format(trim(pg_fetch_result($res,0,hora_tecnica)),2,',','.');
                $valor_diaria             = number_format(trim(pg_fetch_result($res,0,valor_diaria)),2,',','.');
                $valor_por_km_caminhao    = number_format(trim(pg_fetch_result($res,0,valor_por_km_caminhao)),2,',','.');
                $valor_por_km_carro       = number_format(trim(pg_fetch_result($res,0,valor_por_km_carro)),2,',','.');
                $regulagem_peso_padrao    = number_format(trim(pg_fetch_result($res,0,regulagem_peso_padrao)),2,',','.');
                $certificado_conformidade = number_format(trim(pg_fetch_result($res,0,certificado_conformidade)),2,',','.');

                /* HD 46784 */
                $sql = "SELECT  valor_regulagem, valor_certificado
                        FROM    tbl_capacidade_valores
                        WHERE   fabrica = $login_fabrica
                        AND     capacidade_de <= (SELECT capacidade FROM tbl_produto WHERE produto = $produto )
                        AND     capacidade_ate >= (SELECT capacidade FROM tbl_produto WHERE produto = $produto ) ";
                $res = pg_query ($con,$sql);
                if (pg_num_rows($res) > 0) {
                    $regulagem_peso_padrao    = number_format(trim(pg_fetch_result($res,0,valor_regulagem)),2,',','.');
                    $certificado_conformidade = number_format(trim(pg_fetch_result($res,0,valor_certificado)),2,',','.');
                }
                echo "ok|$taxa_visita|$hora_tecnica|$valor_diaria|$valor_por_km_carro|$valor_por_km_caminhao|$regulagem_peso_padrao|$certificado_conformidade";
                exit;
            }
            exit;
        }
    }
    echo "nao|nao";
    exit;
}

//HD 20682 20/6/2008
if($_GET["verifica_linha"]=="sim"){
    $referencia = $_GET["produto_referencia"];
    if (strlen($referencia)>0){
        $sql = "SELECT linha
                FROM tbl_produto
                JOIN tbl_linha USING(linha)
                WHERE fabrica  = $login_fabrica
                AND referencia ='$referencia' ";
        $res = @pg_query($con,$sql);
        if (pg_num_rows($res)>0){
            $linha = pg_fetch_result ($res,0,0);
            if($login_fabrica==3 AND $linha==335){
                echo "ok";
            }else{
                echo "nao";
            }
        }
    }
    exit;
}


if($ajax=='tipo_atendimento'){
    $sql = "SELECT tipo_atendimento,km_google
            FROM tbl_tipo_atendimento
            WHERE tipo_atendimento = $id
            AND   fabrica          = $login_fabrica";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res)>0){

        $km_google = pg_fetch_result($res,0,km_google);
        if($km_google == 't'){
            echo "ok|sim";
        }else{
            echo "no|nao";
        }
    exit;
    }
}

if($ajax=='valida_garantia'){
    $xdata_nf       = fnc_formata_data_pg(trim($data_nf));
    $xdata_abertura = fnc_formata_data_pg(trim($data_abertura));

    $sql = "SELECT  garantia,
                    produto
            FROM tbl_produto
            JOIN tbl_linha   USING(linha)
            WHERE referencia = '$produto_ref'
            AND   fabrica    = $login_fabrica";
    //echo $sql;

    $res = @pg_query($con,$sql);
    if(pg_num_rows($res)>0){

        $produto  = pg_fetch_result($res,0,produto);
        $garantia = pg_fetch_result($res,0,garantia);

        $sql = "SELECT ($xdata_nf::date + (($garantia || ' months')::interval))::date";
        $res = @pg_query($con,$sql);

        $garantia_menor = pg_fetch_result($res,0,0);
        $sql = "SELECT ($xdata_nf::date + (('50 months')::interval))::date";
        $res = @pg_query($con,$sql);
        $garantia_maior = pg_fetch_result($res,0,0);

        $xdata_abertura = str_replace("'","",$xdata_abertura);
        if($garantia_menor < $xdata_abertura){

            if($garantia_maior>$xdata_abertura){
                $liberar = 'true';
            }
        }

        if(isset($liberar)){
            echo "ok|sim";
        }else{
            echo "no|não";
        }
        exit;
    }else
        echo "no|não";
    exit;

}

#-------- Libera digitação de OS pelo distribuidor ---------------
$posto = $login_posto ;
if ($login_fabrica == 3) {
    $sql = "SELECT tbl_tipo_posto.distribuidor FROM tbl_tipo_posto JOIN tbl_posto_fabrica USING (tipo_Posto) WHERE tbl_posto_fabrica.posto = $login_posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
    $res = @pg_query($con,$sql);
    $distribuidor_digita = pg_fetch_result ($res,0,0);
    if (strlen ($posto) == 0) $posto = $login_posto;
}
#----------------------------------------------------------------

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = @pg_query($con,$sql);
$pedir_sua_os = pg_fetch_result ($res,0,pedir_sua_os);
$pedir_defeito_reclamado_descricao = pg_fetch_result ($res,0,pedir_defeito_reclamado_descricao);

/*======= <PHP> FUNÇOES DOS BOTÕES DE AÇÃO =========*/

$btn_acao = strtolower ($_POST['btn_acao']);
//MLG 06/12/2010 - HD 326935 - Limitar por HTML e PHP o comprimento das strings para campos varchar(x).
$_POST['certificado_garantia'] 		= substr($_POST['certificado_garantia']		, 0, 30);
$_POST['consumidor_bairro']			= substr($_POST['consumidor_bairro']		, 0, 80);
$_POST['consumidor_celular']		= substr($_POST['consumidor_celular']		, 0, 20);
$_POST['consumidor_cep']			= substr(preg_replace('/\D/', '', $_POST['consumidor_cep'])	, 0, 8);
$_POST['consumidor_cpf']			= substr(preg_replace('/\D/', '', $_POST['consumidor_cpf'])	, 0, 14);
$_POST['consumidor_cidade']			= substr($_POST['consumidor_cidade']		, 0, 70);
$_POST['consumidor_complemento']	= substr($_POST['consumidor_complemento']	, 0, 20);
$_POST['consumidor_email']			= substr($_POST['consumidor_email']			, 0, 50);
$_POST['consumidor_estado']			= substr($_POST['consumidor_estado']		, 0, 2);
$_POST['consumidor_fone']			= substr($_POST['consumidor_fone']			, 0, 20);
$_POST['consumidor_fone_comercial']	= substr($_POST['consumidor_fone_comercial'], 0, 20);
$_POST['consumidor_fone_recado']	= substr($_POST['consumidor_fone_recado']	, 0, 20);
$_POST['consumidor_nome']			= substr($_POST['consumidor_nome']			, 0, 50);
$_POST['consumidor_nome_assinatura']= substr($_POST['consumidor_nome_assinatura'],0, 50);
$_POST['consumidor_numero']			= substr($_POST['consumidor_numero']		, 0, 20);
$_POST['consumidor_revenda']		= substr($_POST['consumidor_revenda']		, 0, 1);
$_POST['divisao']					= substr($_POST['divisao']					, 0, 20);
$_POST['nota_fiscal']				= substr($_POST['nota_fiscal']				, 0, 20);
$_POST['nota_fiscal_saida']			= substr($_POST['nota_fiscal_saida']		, 0, 20);
$_POST['os_posto']					= substr($_POST['os_posto']					, 0, 20);
$_POST['prateleira_box']			= substr($_POST['prateleira_box']			, 0, 10);
$_POST['quem_abriu_chamado']		= substr($_POST['quem_abriu_chamado']		, 0, 30);
$_POST['revenda_bairro']      		= substr($_POST['revenda_bairro']     		, 0, 80);
$_POST['revenda_cep']         		= substr(preg_replace('/\D/', '', $_POST['revenda_cep'])	, 0, 8);
$_POST['revenda_cnpj']        		= substr(preg_replace('/\D/', '', $_POST['revenda_cnpj'])	, 0, 14);
$_POST['revenda_complemento'] 		= substr($_POST['revenda_complemento']		, 0, 30);
$_POST['revenda_email']       		= substr($_POST['revenda_email']      		, 0, 50);
$_POST['revenda_endereco']    		= substr($_POST['revenda_endereco']   		, 0, 60);
$_POST['revenda_fone']        		= substr($_POST['revenda_fone']       		, 0, 20);
$_POST['revenda_nome']        		= substr($_POST['revenda_nome']       		, 0, 50);
$_POST['revenda_numero']      		= substr($_POST['revenda_numero']     		, 0, 20);
$_POST['rg_produto']				= substr($_POST['rg_produto']				, 0, 50);
$_POST['produto_voltagem']			= substr($_POST['produto_voltagem']			, 0, 20);
$_POST['produto_serie']				= substr($_POST['produto_serie']			, 0, 20);
$_POST['serie_reoperado']			= substr($_POST['serie_reoperado']			, 0, 20);
$_POST['sua_os']					= substr($_POST['sua_os']					, 0, 20);
$_POST['sua_os_offline']			= substr($_POST['sua_os_offline']			, 0, 20);
$_POST['tecnico_nome']				= substr($_POST['tecnico_nome']				, 0, 20);
$_POST['tipo_os_cortesia']			= substr($_POST['tipo_os_cortesia']			, 0, 20);
$_POST['type']						= substr($_POST['type']						, 0, 10);
$_POST['versao']					= substr($_POST['versao']					, 0, 20);
$_POST['natureza_servico']			= substr($_POST['natureza_servico']			, 0, 20);
$_POST['pac']						= substr($_POST['pac']						, 0, 13);
$_POST['veiculo']					= substr($_POST['veiculo']					, 0, 20);

$_POST = array_filter($_POST, 'anti_injection'); // Exclui todos os ítens do array que estiverem vazios depois de filtrar com a função anti_injection

// if($btn_acao and $login_posto = 6359) die(nl2br(print_r($_POST, true)));

$msg_erro = "";

/*============= HD 121247 VALIDAR CAMPOS DO CONSUMIDOR PARA FABRICA 51/GAMA=========*/
/*============= HD 137679 VALIDAR CAMPOS DO CONSUMIDOR PARA FABRICA 30/ESMALTEC=====*/

if (($login_fabrica == 30 || $login_fabrica == 51 || $login_fabrica == 72 || $login_fabrica == 74) and $btn_acao == "continuar") {
    $validados = 0;
    $consumidor_nome_x = $_POST['consumidor_nome'];
    if ($login_fabrica == 51 or $login_fabrica == 74) {    // Esmaltec não pediu no HD137679 a obrigatoriedade destes campos
        $consumidor_fone_x = $_POST['consumidor_fone'];
        $consumidor_cep_x = $_POST['consumidor_cep'];
    }
    if ($login_fabrica == 72) {//HD 249034
        $consumidor_cep_x = $_POST['consumidor_cep'];
    }
    $consumidor_endereco_x = $_POST['consumidor_endereco'];
    $consumidor_numero_x = $_POST['consumidor_numero'];
    $consumidor_bairro_x = $_POST['consumidor_bairro'];
    $consumidor_cidade_x = $_POST['consumidor_cidade'];
    $consumidor_estado_x = $_POST['consumidor_estado'];

    if(strlen($consumidor_nome_x)<=0){
        $pendentes_arr[] = "Nome";
        $validados = $validados +1;
    }

    if ($login_fabrica == 51 or $login_fabrica == 74) {    // Esmaltec não pediu no HD137679 a obrigatoriedade destes campos
        if(strlen($consumidor_fone_x)<=0){
            $pendentes_arr[] = "Fone";
            $validados = $validados +1;
        }
        if(strlen($consumidor_cep_x)<=0){
            $pendentes_arr[] = "CEP";
            $validados = $validados +1;
        }
    }
    if ($login_fabrica == 72) {//HD 249034
        if(strlen($consumidor_cep_x)<=0){
            $pendentes_arr[] = "CEP";
            $validados = $validados +1;
        }
    }
    if(strlen($consumidor_endereco_x)<=0){
        $pendentes_arr[] = "Endereço";
        $validados = $validados +1;
    }
    if(strlen($consumidor_numero_x)<=0){
        $pendentes_arr[] = "Número";
        $validados = $validados +1;
    }
    if(strlen($consumidor_bairro_x)<=0){
        $pendentes_arr[] = "Bairro";
        $validados = $validados +1;
    }
    if(strlen($consumidor_cidade_x)<=0){
        $pendentes_arr[] = "Cidade";
        $validados = $validados +1;
    }
    if(strlen($consumidor_estado_x)<=0){
        $pendentes_arr[] = "Estado";
        $validados = $validados +1;
    }

	if(count($pendentes_arr) > 0) {
		$pendentes = implode(", ",$pendentes_arr);
		if(count($pendentes_arr) == 1)
			$pendentes = 'O campo '. $pendentes . ' é obrigatório <br />';
		else
			$pendentes = 'Os campos '. $pendentes . ' são obrigatórios. <br />';
	}

    if ($validados == 0){
        $msg_erro = "";
    }else{
        $msg_erro = $pendentes;
    }
    $validados = 0;
    $pendentes = "";
}

/*============= HD 121247 VALIDAR CAMPOS DO CONSUMIDOR PARA FABRICA 51/GAMA=========*/

if ($btn_acao == "continuar") {

    $os = $_POST['os'];
    $imprimir_os = $_POST["imprimir_os"];

	if (in_array($login_fabrica, array(40)))
	{
		$familia     = $_POST['familia'];

		if (!empty($familia))
		{
			$unidade_cor = $_POST['unidade_cor'];

			if (empty($unidade_cor))
			{
				$msg_erro .= "Erro: Selecione a Cor da Unidade.<br>";
			}
		}
	}

	if (in_array($login_fabrica,$fabricas_validam_campos_telecontrol) || $login_fabrica > 99) {

		$msg_erro .= validaCamposOs($campos_telecontrol[$login_fabrica]['tbl_os'], $_REQUEST);

	}

	if ($login_fabrica == 91) {//HD 682454

        $val_data_nf         = strtotime(implode('-',array_reverse(explode('/',trim($_POST['data_nf'])))));
        $val_data_fabricacao = strtotime(implode('-',array_reverse(explode('/',trim($_POST['data_fabricacao'])))));

		if ($val_data_nf < $val_data_fabricacao) {
            $msg_erro .= "Erro: A data de compra não pode ser inferior a data de fabricação.<br />";
		}

	}

    $sua_os_offline = $_POST['sua_os_offline'];

    if (strlen (trim ($sua_os_offline)) == 0) {
        $sua_os_offline = 'null';
    } else {
        $sua_os_offline = "'" . trim ($sua_os_offline) . "'";
    }

    $sua_os = $_POST['sua_os'];
    if (strlen (trim ($sua_os)) == 0) {

        $sua_os = 'null';
        //hd 4617
        if ($pedir_sua_os == 't' AND $login_fabrica<>5 AND $login_fabrica <> 86 and $login_fabrica < 101) {
            $msg_erro .= "Erro: Digite o número da OS Fabricante.";
        }

    } else {

        //ALTERAR DIA 04/01/2007 - WELLINGTON
        if (!in_array($login_fabrica,array(1,3,5,11))) {

            if (strlen($sua_os) < 7) {
                $sua_os = str_pad($sua_os, 7, '0', STR_PAD_LEFT);
				// 30/11/09        $sua_os = "000000" . trim ($sua_os);
				// MLG            $sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
            }
            # inserido pelo Ricardo - 04/07/2006
            //hd 4617 - retirar posto teste
            if ($login_fabrica == 3 and 1==2) {
                if (is_numeric($sua_os)) {
                    // retira os ZEROS a esquerda
                    $sua_os = intval(trim($sua_os));
                }
            }

			#            if (strlen($sua_os) > 6) {
			#                $sua_os = substr ($sua_os, strlen ($sua_os) - 6 , 6) ;
			#            }
			#  CUIDADO para OS de Revenda que já vem com = "-" e a sequencia.
			#  fazer rotina para contar 6 caracteres antes do "-"
        }

        $sua_os = "'$sua_os'" ;

    }

    ##### INÍCIO DA VALIDAÇÃO DOS CAMPOS #####
	//Conforme chamado: 390975 - Fazer com que os campos que a cliente solicitou sejam obrigatórios para a Orbis e para fabrica > 96 (novas)
	//HD 413556 - LeaderShip também (95)
	if($login_fabrica == 88 || $login_fabrica == 95 || $login_fabrica > 96 ) {
		$produto_serie = trim($_POST['produto_serie']);
		$nota_fiscal = trim($_POST['nota_fiscal']);
		$data_nf = trim($_POST['data_nf']);
		$consumidor_nome = trim($_POST['consumidor_nome']);
		$consumidor_fone = trim($_POST['consumidor_fone']);
		$consumidor_endereco = trim($_POST['consumidor_endereco']);
		$consumidor_cidade = trim($_POST['consumidor_cidade']);
		$consumidor_estado = trim($_POST['consumidor_estado']);
		$defeito_reclamado_descricao = trim($_POST['defeito_reclamado_descricao']);
		$data_abertura = trim($_POST['data_abertura']);

		if(strlen($produto_serie) == 0 && $login_fabrica < 104){
			$msg_erro .= "Erro: Digite  o número de série do produto<br />";
		}

		if(strlen($nota_fiscal) == 0){
			$msg_erro .= "Erro: Digite  o número da Nota Fiscal<br />";
		}

		if(strlen($data_nf) == 0){
			$msg_erro .= "Erro: Digite a data de compra <br />";
		}else{
			list($di, $mi, $yi) = explode("/", $data_nf);
			if(!checkdate($mi,$di,$yi))
				$msg_erro .= "Data de Compra Inválida<br />";
			else{
				$_data = "$yi-$mi-$di";
				if($_data > date('Y-m-d'))
					$msg_erro .= "Data de Compra Inválida<br />";

			}
		}

		if(strlen($data_abertura) > 0){
			list($di, $mi, $yi) = explode("/", $data_abertura);
			if(!checkdate($mi,$di,$yi))
				$msg_erro .= "Data de Abertura Inválida<br />";
			else{
				$_data = "$yi-$mi-$di";
				if($_data > date('Y-m-d'))
					$msg_erro .= "Data de Abertura Inválida<br />";

			}
		}

		if(strlen($consumidor_nome) == 0){
			$msg_erro .= "Erro: Digite  o nome do consumidor<br />";
		}

		if(strlen($consumidor_fone) == 0){
			$msg_erro .= "Digite  o telefone do consumidor<br />";
		}
		if ($login_fabrica == 95) { //HD 413556
			if(strlen($consumidor_endereco) == 0){
				$msg_erro .= "Erro: Digite o endereço do consumidor<br />";
			}

			if(strlen($consumidor_cidade) == 0){
				$msg_erro .= "Digite a cidade do consumidor<br />";
			}

			if(strlen($consumidor_estado) == 0){
				$msg_erro .= "Selecione o estado do consumidor<br />";
			}

			if(strlen($defeito_reclamado_descricao) == 0 && $login_fabrica != 95){
				$msg_erro .= 'Digite  o defeito reclamado<br />';
			}
		}

	}

    $locacao = trim($_POST["locacao"]);
    $x_locacao = (strlen($locacao) > 0) ? "7" : "null";

    if ($login_fabrica == 7) { // HD 75762 para Filizola

        $classificacao_os = trim($_POST['classificacao_os']);

        if (strlen($classificacao_os) == 0) {
            $msg_erro .= " Escolha a classificação da OS. ";
        }

    } else {
        $classificacao_os = 'null';
    }

    $tipo_atendimento = $_POST['tipo_atendimento'];

    if (strlen(trim($tipo_atendimento)) == 0) {

        $tipo_atendimento = 'null';

        if ($login_fabrica == 7) {
            $msg_erro .= " A natureza é obrigatória. ";
        }

    }


    $produto_referencia = strtoupper(trim($_POST['produto_referencia']));
    $produto_referencia = str_replace("-","",$produto_referencia);
    $produto_referencia = str_replace(" ","",$produto_referencia);
    $produto_referencia = str_replace("/","",$produto_referencia);
    $produto_referencia = str_replace(".","",$produto_referencia);




	if($login_fabrica == 15) {

		if($tipo_atendimento == 'null'){
			$msg_erro .= "Selecione um Tipo de Atendimento";
		}
		//echo "TIPO ATENDIMENTO =".$tipo_atendimento."<br>";
		if(strlen($msg_erro) == 0) {
			$sql = "select tbl_produto.produto
					from tbl_produto
					join tbl_familia using(familia)
					where tbl_familia.paga_km is TRUE
					and tbl_familia.fabrica=$login_fabrica
					and tbl_produto.referencia='$produto_referencia' ";
					//echo $sql;
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) == 0) {
				if($tipo_atendimento == 21){
					$msg_erro .= "Tipo de atendimento inválido para esse produto";
				}
			}
		}
	}

	if ($login_fabrica == 35 and $tipo_atendimento == 100) {
		$xxproduto_referencia = strtoupper(trim($_POST['produto_referencia']));
		$sqlDesl = "SELECT tbl_linha.linha FROM tbl_linha JOIN tbl_produto USING(linha)
					WHERE tbl_linha.deslocamento IS TRUE
					AND tbl_produto.referencia = '$xxproduto_referencia'";
		echo $sqlDesl;
		$qryDesl = pg_query($con, $sqlDesl);

		if (pg_num_rows($qryDesl) == 0) {
			$msg_erro .= "Tipo de atendimento inválido para esse produto";
		}
	}

	if ($login_fabrica == 42) {//HD 400603

		if ($tipo_atendimento == 103) {
			$produto_referencia = 'GAR-PECAS';
		} else if ($tipo_atendimento == 104) {
			$produto_referencia = 'GAR-ACESS';
		}

	}

	if (strlen($produto_referencia) == 0) {
		$produto_referencia = 'null';
		$msg_erro .= " Erro: Digite o produto.<br />";
	} else {
		$produto_referencia = "'".$produto_referencia."'" ;
	}

	if ($login_fabrica == 30) {

		$sql = "SELECT marca FROM tbl_produto WHERE referencia = $produto_referencia AND marca = 164";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$msg_erro = "Este produto ITATIAIA não pode ser aberto Ordem de Serviço pelo Posto, somente o CALLCENTER poderá abrir. Favor entrar em contato com o CALLCENTER!<br>";
		}

	}

    $produto_capacidade = strtoupper(trim($_POST['produto_capacidade']));

    if (strlen($produto_capacidade) == 0) {
        $xproduto_capacidade = 'null';
    } else {
        $xproduto_capacidade = str_replace(",",".",$produto_capacidade);
    }

    $versao = trim($_POST['versao']);

    if (strlen($versao) == 0) {
        $xversao = 'null';
    } else {
        $xversao = "'".$versao."'";
    }

    $divisao = trim($_POST['divisao']);

    if (strlen($divisao) == 0) {
        $xdivisao = 'null';
    } else {
        $xdivisao = str_replace(",",".",$divisao);
    }

    if($login_fabrica == 42){
		if(strlen($consumidor_nome) == 0){
			$msg_erro .= "Erro: Digite  o nome do consumidor<br />";
		}

		if(strlen($consumidor_fone) == 0){
			$msg_erro .= "Digite  o telefone do consumidor<br />";
		}
	}

    $xdata_abertura = fnc_formata_data_pg(trim($_POST['data_abertura']));
    if ($xdata_abertura == 'null') $msg_erro .= " Erro: Digite a data de abertura da OS.<br />";
    $cdata_abertura = str_replace("'","",$xdata_abertura);

    if ($login_fabrica == 72) {//HD 249034

        //Mallory não pode ter data de abertura > 5 dias
        if (date_to_timestamp(trim($_POST['data_abertura'])) < mktime(0, 0, 0, date("m"), date("d")-5, date("Y"))) {
            $msg_erro .= " Erro: A Mallory não permite que OS com mais de 5 dias sejam inseridas. Favor entrar em contato com a Mallory<br />";
        }

    }

    $hora_abertura = trim($_POST['hora_abertura']);
    if ($login_fabrica == 7 AND strlen($hora_abertura) == 0) {
        $msg_erro .= " Digite a hora de abertura da OS.";
    }

    if (strlen($msg_erro) == 0) {

        if (strlen($hora_abertura) > 0) {
            $xhora_abertura = "'".$hora_abertura."'";
        } else {
            $xhora_abertura = " NULL ";
        }

    }

    ##############################################################
    # AVISO PARA POSTOS DA BLACK & DECKER
    # Verifica se data de abertura da OS é inferior a 01/09/2005
    ##############################################################
    if ($login_fabrica == 1) {
        $sdata_abertura = str_replace("-","",$cdata_abertura);

        // liberados pela Fabiola em 05/01/2006
        if ($login_posto == 5089) { // liberados pela Fabiola em 20/03/2006
            if ($sdata_abertura < 20050101)
                $msg_erro = "Erro. Data de abertura inferior a 01/01/2005.<br />Lançamento restrito às OSs com data de lançamento superior a 01/01/2005.";
        } else if ($login_posto == 5059 OR $login_posto == 5212) {
            if ($sdata_abertura < 20050502)
                $msg_erro = "Erro. Data de abertura inferior a 02/05/2005.<br />Lançamento restrito às OSs com data de lançamento superior a 01/05/2005.";
        } else {
            if ($sdata_abertura < 20050901)
                $msg_erro = "Erro. Data de abertura inferior a 01/09/2005.<br />OS deve ser lançada no sistema antigo até 30/09.";
        }
    }
    ##############################################################

    if (in_array($login_fabrica, array(6, 7, 51))) {
        if (strlen(trim($_POST['consumidor_nome'])) == 0) {
            $msg_erro .= " Erro: Digite o nome do consumidor. <br />";
        } else {
            $xconsumidor_nome = "'".str_replace("'","",trim($_POST['consumidor_nome']))."'";
        }
    } else {
        if (strlen(trim($_POST['consumidor_nome'])) == 0) {
            $xconsumidor_nome = 'null';
        } else {
            $xconsumidor_nome = "'".str_replace("'","",trim($_POST['consumidor_nome']))."'";
        }
    }
    $consumidor_cpf = trim($_POST['consumidor_cpf']);

    if(in_array($login_fabrica, $cpf_obrigatorio) and ($login_fabrica == 43 and $consumidor_revenda == 'C')) {
        $cnpj_valido = (!is_bool($consumidor_cpf = checaCPF($consumidor_cpf,false)));
        if ($cnpj_valido) {
            $xconsumidor_cpf = "'".checaCPF($consumidor_cpf,false)."'";
        } else {
            $msg_erro .= "CPF/CNPJ do consumidor inválido<br />";
            $xconsumidor_cpf = 'null';
        }
    } else {
        $cnpj_valido = false;
        if (strlen($consumidor_cpf) != 0) {
            if (!is_bool(checaCPF($consumidor_cpf, false))) {
                $consumidor_cpf = checaCPF($consumidor_cpf);
                $cnpj_valido = true;
            } else {
                $msg_erro .= "CPF/CNPJ do cliente inválido<br />";
            }
        }
        $xconsumidor_cpf = ($cnpj_valido) ? "'$consumidor_cpf'" : 'null';
    }
    if (strlen(trim($_POST['consumidor_cidade'])) == 0) $xconsumidor_cidade = 'null';
    else             $xconsumidor_cidade = "'".trim($_POST['consumidor_cidade'])."'";

    if (strlen(trim($_POST['consumidor_estado'])) == 0) $xconsumidor_estado = 'null';
    else             $xconsumidor_estado = "'".trim($_POST['consumidor_estado'])."'";

    if (strlen(trim($_POST['consumidor_fone'])) == 0) $xconsumidor_fone = 'null';
    else             $xconsumidor_fone = "'".trim($_POST['consumidor_fone'])."'";

    if (strlen(trim($_POST['consumidor_celular'])) == 0) $xconsumidor_celular = 'null';// hd 15091
    else             $xconsumidor_celular = "'".trim($_POST['consumidor_celular'])."'";

    if (strlen(trim($_POST['consumidor_fone_comercial'])) == 0) $xconsumidor_fone_comercial = 'null';
    else            $xconsumidor_fone_comercial = "'".trim($_POST['consumidor_fone_comercial'])."'";

    if (strlen(trim($_POST['consumidor_fone_recado'])) == 0) $xconsumidor_fone_recado = 'null';
    else             $xconsumidor_fone_recado = "'".trim($_POST['consumidor_fone_recado'])."'";

	//HD 413556 - Campos obrigatórios para a LeaderShip
    if (in_array($login_fabrica, array(7, 14, 45, 50, 51, 80, 95)) and $xconsumidor_fone=='null') {
        $msg_erro .= " Erro: Digite o telefone do consumidor.<br />";
    }

    if (in_array($login_fabrica, array(7, 14, 45)) AND $xconsumidor_cidade == 'null') {
        $msg_erro .= " Digite a cidade do consumidor.<br />";
    }

    if (in_array($login_fabrica, array(7, 14, 45)) AND $xconsumidor_estado == 'null') {
        $msg_erro .= " Digite o estado do consumidor.<br />";
    }

    if ($login_fabrica == 19) {
        if (strlen($xconsumidor_fone) <> 15 AND strlen($xconsumidor_fone) <> 16) {
            $msg_erro .= "Telefone do cosumidor em formato inválido. Formato válido: Ex. 011-1234-5678";
        }
    }

    #takashi 02-09
    $xconsumidor_endereco    = trim ($_POST['consumidor_endereco']) ;
    $xconsumidor_numero      = trim ($_POST['consumidor_numero']);
    $xconsumidor_complemento = trim ($_POST['consumidor_complemento']) ;
    $xconsumidor_bairro      = trim ($_POST['consumidor_bairro']) ;
    $xconsumidor_cep         = trim ($_POST['consumidor_cep']) ;

	//HD 413556 - Campos obrigatórios para a LeaderShip
    if (in_array($login_fabrica, array(1, 2, 7, 45, 51, 80))) {
        if (strlen($xconsumidor_endereco) == 0) $msg_erro .= " Erro: Digite o endereço do consumidor. <br />";
    }

	//HD 413556 - Campos obrigatórios para a LeaderShip
    if (in_array($login_fabrica, array(1, 7, 45, 51, 95))) {
        if (strlen($xconsumidor_numero) == 0) $msg_erro .= " Erro: Digite o número do endereço do consumidor. <br />";
        if (strlen($xconsumidor_bairro) == 0) $msg_erro .= " Erro: Digite o bairro do consumidor. <br />";
        if (strlen($xconsumidor_estado) == 0) { $msg_erro .= " Erro: Digite o estado do consumidor. <br />";
        }
        else $xconsumidor_estado = "'".trim($_POST['consumidor_estado'])."'";
    }

    //--==== OS de Instalação ============================================
	if(strlen($tipo_atendimento) > 0) {
		$automatico = "t";
		$obs_km = " OS Aguardando aprovação de Kilometragem. ";
		$km_auditoria = "FALSE";
		$sql = "SELECT tipo_atendimento,km_google
				FROM tbl_tipo_atendimento
				WHERE tipo_atendimento = $tipo_atendimento";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0) {
			$km_google = pg_fetch_result($res,0,km_google);

			$obs_km="";
			if ($km_google == 't') {
				$qtde_km  = str_replace (",",".",$_POST['distancia_km']);
				$xqtde_km = (!empty($qtde_km)) ?$qtde_km : "0" ;
				$qtde_km = number_format($qtde_km,3,'.','');
				$qtde_km2 = number_format($_POST['distancia_km_conferencia'],3,'.','') ;

				if ($login_fabrica == 30 AND ($xqtde_km <>'0')) {
					$km_auditoria = "TRUE"; # HD 112039
					$obs_km=" Cálculo Automático. ";
				}

				if ($login_fabrica == 15) { //HD 275256 inicio
					if ($qtde_km >= '100'){
						$km_auditoria = "TRUE";
						$obs_km= ($xqtde_km <>'0') ? " Cálculo Automático. " : null;
					}

				}else{

					$qtde_maior_100 = false;

				}//HD 275256 fim

				if ($distancia_km_maps <> 'maps' AND ($qtde_km <> $qtde_km2 AND $qtde_km > 0) OR ($qtde_maior_100 == true) ) {

					/*HD20487 - 04/07/2008*/
					if ($login_fabrica == 30) {
						if (($qtde_km*1.2) > $qtde_km2) {
							$km_auditoria = "TRUE";
						}
					} else {
						$km_auditoria = "TRUE";
					}

					if ($login_fabrica == 24 or $login_fabrica == 30 or $login_fabrica == 91) {
						$xqtde_km  = str_replace(".", ",", $qtde_km);
						$xqtde_km2 = str_replace(".", ",", $qtde_km2);
						// HD 47644
						$obs_km = " Alteração manual de km de $xqtde_km2 km para $xqtde_km km. ";
						$automatico = ($login_fabrica == 15) ? null : "f";
					}

					if ($login_fabrica == 15) {
						$xqtde_km  = str_replace(".", ",", $qtde_km);
						$xqtde_km2 = str_replace(".", ",", $qtde_km2);
						// HD 699862
						$obs_km = " Alteração manual de km de $xqtde_km2 km para $xqtde_km km. ";
					}

					if ($login_fabrica == 35 ) { // HD 708697

						if ($qtde_km < 20) {
							$qtde_km = 0;
						}
						else {

							$xqtde_km  = str_replace(".", ",", $qtde_km);
							$xqtde_km2 = str_replace(".", ",", $qtde_km2);
							$obs_km = " Alteração manual de km de $xqtde_km2 km para $xqtde_km km. ";

						}

					}

					if($login_fabrica == 90 && $qtde_km > 30) { // HD 310122
						$xqtde_km  = str_replace(".", ",", $qtde_km);
						$xqtde_km2 = str_replace(".", ",", $qtde_km2);
						$obs_km = " Alteração manual de km de $xqtde_km2 km para $xqtde_km km. ";
						$automatico = "f";
					}

					if ($login_fabrica == 74) {//HD:358194
						$km_auditoria = "TRUE";
						$obs_km = " Alteração manual de km de $qtde_km2 km para $xqtde_km km. ";
					}

				} else {

					if ($login_fabrica == 50) {//HD: 24813 - PARA

						if ($qtde_km >= 50) {
							$km_auditoria = "TRUE";
						}

						$qtde_km = $qtde_km - 20;
						$qtde_km = ($qtde_km < 0) ? 0 : $qtde_km;

					}

					if ($login_fabrica == 35 ) { // HD 708697

						if ($qtde_km < 20) {
							$qtde_km = 0;
						} else if ($qtde_km > 50) {
							$km_auditoria = "TRUE";
							$obs_km = " Quantidade de KM calculado superior a 50 km. ";
						}

					}

					if ($login_fabrica == 74 AND $qtde_km > 80) {//HD:358194
						$km_auditoria = "TRUE";
						$obs_km = " Quantidade de KM calculado superior a 80 km. ";
					}

					if ($login_fabrica == 24  AND $qtde_km > 100) {
						$km_auditoria = "TRUE";
						$obs_km       = " KM maior que 100km. ";
					}

					if ($login_fabrica == 85  AND $qtde_km > 40) { //HD 323345

						$km_auditoria = "TRUE";
						$obs_kmi      = " KM maior que 40km. ";

					} else if( $login_fabrica == 85 )
						$qtde_km = 0;

					if ($login_fabrica == 90 AND $qtde_km > 40) { // fabrica 90 HD 310122
						$km_auditoria = "TRUE";
						$obs_km       = " KM maior que 40km. ";
					}

					// HD 310122, waldir pediu p alterar p 10, 17/11/2010
					if ($login_fabrica == 90 && $qtde_km < 10) {
						$qtde_km = 0;
					}

					if ($login_fabrica == 91) {//HD 375933

						if ($qtde_km > 15) {

							$km_auditoria = "TRUE";
							$obs_km       = "OS Aguardando aprovação de Kilometragem";
							$automatico   = "t";

						} else {

							$qtde_km = 0;

						}

					}

					if ($login_fabrica == 30 AND $qtde_km > 200) {// HD 47644

						$km_auditoria = "TRUE";
						$obs_km       = " KM maior que 200km. ";

					}

				}

			} else {

				if ($login_fabrica <> 19) $qtde_produtos = 1;

			}

		}

	}

    if (strlen($qtde_km) == 0) {

        $qtde_km      = "NULL";
        $km_auditoria = "FALSE";

    }

    //$msg_erro = "$qtde_km $km_auditoria $qtde_km2";
    //--================================================================

    if (strlen($xconsumidor_complemento) == 0) $xconsumidor_complemento = "null";
    else                                       $xconsumidor_complemento = "'" . $xconsumidor_complemento . "'";

    if ($_POST['consumidor_contrato'] == 't') $contrato    = 't';
    else                                      $contrato    = 'f';

    $xconsumidor_cep = preg_replace('/\D/', '', $xconsumidor_cep);
    $xconsumidor_cep = substr ($consumidor_cep,0,8);

	//HD 413556 - Campos obrigatórios para a LeaderShip
    if($login_fabrica==7 or $login_fabrica==45 or $login_fabrica==95) {
        if (strlen(trim($xconsumidor_cep)) == 0) $msg_erro .= " Erro: Digite o CEP do consumidor. <br />";
        else                                     $xconsumidor_cep = "'" . $xconsumidor_cep . "'";
    }else{
        if (strlen(trim($xconsumidor_cep)) == 0) $xconsumidor_cep = "null";
        else                                     $xconsumidor_cep = "'" . $xconsumidor_cep . "'";
    }
    ##takashi 02-09

    #HD 26730
    if ($login_fabrica == 7 or $login_fabrica == 30){

        if (strlen($msg_erro)==0 and $xconsumidor_cpf <> "null" and strlen($xconsumidor_cidade)>0 and $xconsumidor_cidade <> 'null' and strlen($xconsumidor_estado)>0 and $xconsumidor_estado <> 'null') {
            if ($login_fabrica == 7){
                $sql = "SELECT tbl_posto.posto AS cliente
                        FROM   tbl_posto
                        WHERE  tbl_posto.cnpj = $xconsumidor_cpf";
                $res = pg_query ($con,$sql);

                if (pg_num_rows ($res) == 0){
                    $sql = "INSERT INTO tbl_posto
                                (nome,cnpj,endereco,numero,complemento,bairro,cep,cidade,estado,fone)
                            VALUES
                                ($xconsumidor_nome, $xconsumidor_cpf, '$xconsumidor_endereco', '$xconsumidor_numero', $xconsumidor_complemento, '$xconsumidor_bairro', $xconsumidor_cep, $xconsumidor_cidade,$xconsumidor_estado, $xconsumidor_fone) ";
                    $res = pg_query ($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    $res   = pg_query ($con,"SELECT CURRVAL ('seq_posto') as cliente");
                }

                $xcliente = pg_fetch_result($res,0,cliente);

                $sql = "SELECT tbl_posto_consumidor.posto AS cliente
                        FROM   tbl_posto_consumidor
                        WHERE  tbl_posto_consumidor.fabrica = $login_fabrica
                        AND    tbl_posto_consumidor.posto   = $xcliente";
                $res = pg_query ($con,$sql);

                if (pg_num_rows ($res) == 0){
                     $sql = "INSERT INTO tbl_posto_consumidor
                                (fabrica,posto,obs)
                            VALUES
                                ($login_fabrica, $xcliente, 'Cliente cadastrado automaticamente apartir da OS') ";
                    $res = pg_query ($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }
            }

            if ($login_fabrica == 30){
                $sql = "SELECT    tbl_cliente.cliente,
                                tbl_cliente.nome,
                                tbl_cliente.fone,
                                tbl_cliente.cpf,
                                tbl_cidade.nome AS cidade,
                                tbl_cidade.estado
                        FROM tbl_cliente
                        LEFT JOIN tbl_cidade
                        USING (cidade)
                        WHERE tbl_cliente.cpf = $xconsumidor_cpf";
                $res = pg_query ($con,$sql);

                if (pg_num_rows ($res) == 0){

                    $sql = "SELECT fnc_qual_cidade ($xconsumidor_cidade,$xconsumidor_estado)";
                    $res = pg_query ($con,$sql);
                    $xconsumidor_cidade2 = pg_fetch_result($res,0,0);

                    $sql = "INSERT INTO tbl_cliente
                                (nome,cpf,endereco,numero,complemento,bairro,cep,cidade,fone)
                            VALUES
                                ($xconsumidor_nome, $xconsumidor_cpf, '$xconsumidor_endereco', '$xconsumidor_numero', $xconsumidor_complemento, '$xconsumidor_bairro', $xconsumidor_cep, $xconsumidor_cidade2, $xconsumidor_fone) ";
                    $res = pg_query ($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                }
            }
        }
    }

    $revenda_cnpj = preg_replace('/\D/', '', trim($_POST['revenda_cnpj']));

	// Mesmo que a fábrica não exiga a revenda, se digitou um CNPJ, tem que validar
    if (strlen($revenda_cnpj) <> 0 AND strlen($revenda_cnpj) <> 14) {
        $msg_erro .= " Tamanho do CNPJ da revenda inválido.<br />";
    }
    // email do ronaldo pedindo para validar cnpj da Gama Italy
	//HD 413556 - Campos obrigatórios para a LeaderShip
    if (strlen($revenda_cnpj) == 0 and in_array($login_fabrica, array(3, 51, 72, 81, 95))) {
        $msg_erro     .= " Erro: Insira o CNPJ da Revenda.<br />";
    } else {
        $xrevenda_cnpj = "'$revenda_cnpj'";
    }

    if ($login_fabrica==11 and $login_posto != 20321) {

        if (strlen($revenda_cnpj) == 0) $msg_erro .= " Insira o CNPJ da Revenda.<br />";
        else                            $xrevenda_cnpj = "'".$revenda_cnpj."'";

    } else {

        if (strlen($revenda_cnpj) == 0) {

            $xrevenda_cnpj = 'null';

        } else {

            $xrevenda_cnpj = "'".$revenda_cnpj."'";

            if ($login_fabrica == 7) {//HD 46309

                $sql = "SELECT fn_valida_cnpj_cpf('$revenda_cnpj')";
                $res = @pg_query($con,$sql);
                $cnpj_erro = pg_errormessage($con);

                if (strlen($cnpj_erro) > 0) {
                    $msg_erro .="CNPJ da Revenda inválida";
                }

            }

        }

    }

    if (strlen(trim($_POST['revenda_nome'])) == 0){
        #hd 15835 17136 | HD 25450
        # HD 73415 - Nova
        if ($login_fabrica==7 or $login_fabrica==14
            or ($login_fabrica==11 and $login_posto==20321)
            or $login_fabrica==30 or $login_fabrica == 43 or $login_fabrica == 96){
            $xrevenda_nome = "NULL";
        }else{
            $msg_erro .= " Erro: Digite o nome da revenda. <br />";
        }
    }else{
        $xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";
    }

    if (strlen(trim($_POST['revenda_fone'])) == 0) $xrevenda_fone = 'null';
    else $xrevenda_fone = "'".str_replace("'","",trim($_POST['revenda_fone']))."'";


//=====================revenda
    $xrevenda_cep = preg_replace('/\D/', '', trim($_POST['revenda_cep']));
    $xrevenda_cep = substr ($xrevenda_cep,0,8);
    /*takashi HD 931  21-12*/
    //HD 206869: Exigir CNPJ da Revenda para a Salton
    if (strlen ($_POST['revenda_cnpj']) == 0 and ($login_fabrica == 3 || $login_fabrica == 81)) $msg_erro .= " Digite o CNPJ da Revenda.<br />";

	if (strlen($xrevenda_cep) == 0) $xrevenda_cep = "null";
    else $xrevenda_cep = "'" . $xrevenda_cep . "'";

    //if (strlen(trim($_POST['revenda_cep'])) == 0) $xrevenda_cep = 'null';
    //else $xrevenda_cep = "'".str_replace("'","",trim($_POST['revenda_cep']))."'";

    if (strlen(trim($_POST['revenda_endereco'])) == 0) $xrevenda_endereco = 'null';
    else $xrevenda_endereco = "'".str_replace("'","",trim($_POST['revenda_endereco']))."'";

    if (strlen(trim($_POST['revenda_numero'])) == 0) $xrevenda_numero = 'null';
    else $xrevenda_numero = "'".str_replace("'","",trim($_POST['revenda_numero']))."'";

    if (strlen(trim($_POST['revenda_complemento'])) == 0) $xrevenda_complemento = 'null';
    else $xrevenda_complemento = "'".str_replace("'","",trim($_POST['revenda_complemento']))."'";

    if (strlen(trim($_POST['revenda_bairro'])) == 0) $xrevenda_bairro = 'null';
    else $xrevenda_bairro = "'".str_replace("'","",trim($_POST['revenda_bairro']))."'";

    if (strlen(trim($_POST['revenda_cidade'])) == 0) {
        #hd 15835 17136 25450 73415
        if ($login_fabrica==7 or $login_fabrica==14
            or ($login_fabrica==11 and $login_posto==20321)
            or $login_fabrica==30 or $login_fabrica == 43 or $login_fabrica == 96){
            $xrevenda_cidade='null';
        }else{
            $msg_erro .= " Erro: Digite a cidade da revenda. <br />";
        }
    }else{
        $xrevenda_cidade = "'".str_replace("'","",trim($_POST['revenda_cidade']))."'";
    }
    #hd 15835 17136 25450 73415
    if (strlen(trim($_POST['revenda_estado'])) == 0){
        if ($login_fabrica==7 or $login_fabrica==14
            or ($login_fabrica==11 and $login_posto==20321)
            or $login_fabrica==30 or $login_fabrica == 43 or $login_fabrica == 96){
            $xrevenda_estado='null';
        }else{
            $msg_erro .= " Erro: Selecione o estado da revenda. <br />";
        }
    }else{
        $xrevenda_estado = "'".str_replace("'","",trim($_POST['revenda_estado']))."'";
    }
//=====================revenda

    if (strlen(trim($_POST['nota_fiscal'])) == 0) $xnota_fiscal = 'null';
    else             $xnota_fiscal = "'".trim($_POST['nota_fiscal'])."'";
    // HD 15835
    //HD 206869: Exigir nota fiscal para a Salton - HD 413556 LeaderShip
    if ($xnota_fiscal == 'null' and (in_array($login_fabrica, array(6, 14, 24, 81, 95)) or ($login_fabrica == 11 and $login_posto != 20321))) {
		$msg_erro .= "Erro: Digite o número da nota fiscal.<br />";
    }

    if(strlen($xnota_fiscal)>0 AND $login_fabrica == 30){
        $xnota_fiscal = str_replace (".","",$xnota_fiscal);
        $xnota_fiscal = str_replace ("-","",$xnota_fiscal);
        $xnota_fiscal = str_replace ("/","",$xnota_fiscal);
        $xnota_fiscal = str_replace (",","",$xnota_fiscal);
        $xnota_fiscal = str_replace (" ","",$xnota_fiscal);
    }

    $qtde_produtos = trim ($_POST['qtde_produtos']);
    if (strlen ($qtde_produtos) == 0) $qtde_produtos = "1";

    if (strlen ($_POST['troca_faturada']) == 0) $xtroca_faturada = 'null';
    else        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";
	//pedido por Leandro Tectoy, feito por takashi 04/08
	//HD 413556 - Campos obrigatórios para a LeaderShip
    if (in_array($login_fabrica, array(5, 6, 24)) or ($login_fabrica == 11 and $login_posto != 20321 )){
        if (strlen ($_POST['data_nf']) == 0) $msg_erro .= "Erro: Digite a data de compra.<br />";
    }
	//pedido por Leandrot tectoy, feito por takashi 04/08
    $xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
    if ($xdata_nf == null AND $xtroca_faturada <> 't') $msg_erro .= " Digite a data de compra.";

    // HD 56479
    if (substr($revenda_cnpj, 0, 8) != '59291534' and $login_posto <> 653) {

        if (strlen($xdata_nf) > 0 and $login_fabrica == 30 and $tipo_atendimento == 41) {

            $sql = "SELECT $xdata_nf >= $xdata_abertura::date - interval '3 months' ";
            $res= pg_query($con,$sql);

            if (pg_fetch_result($res,0,0) == 'f') {

                $msg_erro=" Liberação de KM apenas até os primeiros três meses de compra<br />";

                if ((strpos(strtoupper($xconsumidor_cidade),"QUEDAS DO IGUA")) AND $login_posto == 855) {
                    $msg_erro = "";
                }

            }

        }

    } //HD21373

    //HD26244 - Liberado para Esmaltec
    if ($login_fabrica == 30 OR $login_fabrica == 51) {
        $sql = "SELECT  garantia,
                    produto
            FROM tbl_produto
            JOIN tbl_linha   USING(linha)
            WHERE referencia = $produto_referencia
            AND   fabrica    = $login_fabrica";

        $res = @pg_query($con,$sql);
        if(pg_num_rows($res)>0){

            $garantia = pg_fetch_result($res,0,garantia);

            $sql = "SELECT ($xdata_nf::date + (($garantia || ' months')::interval))::date";
            $res = @pg_query($con,$sql);

            $garantia_menor = pg_fetch_result($res,0,0);
            $sql = "SELECT ($xdata_nf::date + (('50 months')::interval))::date";
            $res = @pg_query($con,$sql);
            $garantia_maior = pg_fetch_result($res,0,0);

            $xxdata_abertura = str_replace("'","",$xdata_abertura);

            if($garantia_menor < $xxdata_abertura){
                if($garantia_maior>$xxdata_abertura){
                    $liberar_digi = 'true';
                }
                if ($login_fabrica==30){
                    $liberar_digi = 'true';
                }
            }
        }
    }

    if(strlen(trim($_POST['certificado_garantia']))==0) { // HD 63188
        if($login_fabrica == 30 and $liberar_digi == 'true') {
            $msg_erro .= "Digite 6 digitos para LGI";
        }else{
            $xcertificado_garantia = 'null';
        }
    }else{
        if($login_fabrica == 30 and strlen(trim($_POST['certificado_garantia'])) <> 6 and $liberar_digi =='true') {
            $msg_erro .= "Digite 6 digitos para LGI ";
        }else{
            $xcertificado_garantia = "'$certificado_garantia'";
        }
    }

    if (strlen(trim($_POST['produto_serie'])) == 0) {
        $xproduto_serie = 'null';
    }else{
        if($login_fabrica == 40) { // HD 205803
            if(strlen($_POST['produto_serie_ini']) == 0) {
                $msg_erro = "Por favor, informe os 2 campos de número de série";
            }else{
                $xproduto_serie = $_POST['produto_serie_ini']."".str_pad($_POST['produto_serie'],7,"0",STR_PAD_LEFT);
                $xproduto_serie = "'". strtoupper(trim($xproduto_serie)) ."'";
            }
        }else{
            $xproduto_serie = "'". strtoupper(trim($_POST['produto_serie'])) ."'";
        }
    }
	//MLG 19-04-2011 - HD 396972: Adicionar Ga.Ma Italy. Validação simples: 1 min, 20 máx, só letras e números.
    if($xproduto_serie=='null' and in_array($login_fabrica, array(11, 51, 56, 72, 79))) $msg_erro .= ' Digite o Número de Série.<br />';
	if ($login_fabrica == 51 and $xproduto_serie != 'null' and
		!preg_match('/^[0-9A-Z]{1,20}$/', $_POST['produto_serie'])) $msg_erro .= 'Número de série inválido!<br />';

    if ($login_fabrica == 56 AND strlen($msg_erro) == 0){
        if($tipo_atendimento == 42){
            $sql = "SELECT tipo_atendimento
                        FROM tbl_os
                        WHERE fabrica = $login_fabrica
                        AND tipo_atendimento = 42
                        AND serie = $xproduto_serie";
//            echo "$sql";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) > 0){
                $msg_erro = "O Produto com este número de série já foi instalado.";
            }
        }
        if(strlen($msg_erro) == 0 AND ($tipo_atendimento == 43 OR $tipo_atendimento == 44)){
            $sql = "SELECT tipo_atendimento
                        FROM tbl_os
                        WHERE fabrica = $login_fabrica
                        AND tipo_atendimento = 42";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) == 0){
                $msg_erro = "O Produto com este número de série ainda não foi instalado.";
            }
        }
    }

	if($login_fabrica == 94 AND strlen($produto_referencia) > 0 AND strlen($produto_serie) > 0){
		$sql = "SELECT serie
		            FROM tbl_numero_serie
				   WHERE serie = '$produto_serie'
				   AND referencia_produto = $produto_referencia
				   AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		if(pg_numrows($res) == 0){
			 $msg_erro .= 'Número de série inválido!<br />';
		}
	}

    if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $xcodigo_fabricacao = 'null';
    else             $xcodigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";

    //hd 14269 7/3/2008
    if ($login_fabrica == 45) {
        if (strlen(trim($_POST['preco_produto'])) == 0) $msg_erro = 'Digite o Preço do Produto.';
        else            $xpreco_produto = trim($_POST['preco_produto']);

        if (strlen(trim($_POST['aparencia_produto'])) == 0) $xaparencia_produto = 'null';
        else                                                $xaparencia_produto = trim($_POST['aparencia_produto']);
        $xpreco_produto = " Valor $xpreco_produto";
        $xaparencia_produto = "'".$xaparencia_produto.$xpreco_produto."'";
    }else{
        if (strlen(trim($_POST['aparencia_produto'])) == 0) $xaparencia_produto = 'null';
        else             $xaparencia_produto = "'".trim($_POST['aparencia_produto'])."'";
    }

//pedido leandro tectoy
    if($login_fabrica==6){
        if (strlen ($_POST['aparencia_produto']) == 0) $msg_erro .= " Digite a aparência do produto.<br />";
    }

    if (strlen(trim($_POST['acessorios'])) == 0) $xacessorios = 'null';
    else                                         $xacessorios = "'".trim($_POST['acessorios'])."'";
//pedido leandro tectoy
    if($login_fabrica==6){
        if (strlen ($_POST['acessorios']) == 0) $msg_erro .= " Digite os acessórios do produto.<br />";
    }

    if (strlen(trim($_POST['defeito_reclamado_descricao'])) == 0) {
            $xdefeito_reclamado_descricao = 'null';
    }else{
        $xdefeito_reclamado_descricao = "'".trim($_POST['defeito_reclamado_descricao'])."'";
    }
    $defeito_reclamado_descricao = trim($_POST['defeito_reclamado_descricao']);

	//HD 722524 - Validação do campo "consumidor_email" para LATINATEC
	if ($login_fabrica == 15) {

		if (strlen(trim($_POST['consumidor_email'])) == 0) {

			$msg_erro = "Insira o e-mail do consumidor. Informe caso o mesmo não possua";

		} else if (strlen(trim($_POST['consumidor_email'])) > 0) {

			$email = trim($_POST['consumidor_email']);

			if (!filter_var($email, FILTER_VALIDATE_EMAIL))	{

				if (preg_match('/(.)(?=\1{2,})/',$email)){
					$msg_erro .= "<br />E-mail ou informação com varios caracteres repetidos";
				}

			}

			if (strlen($email) < 5) {
				$msg_erro .= "<br />E-mail muito pequeno, ou informação muito curta.";
			}

		}

		if(empty($msg_erro)){

			$xconsumidor_email = "'".trim(substr($_POST['consumidor_email'],0,49))."'";
			$consumidor_email = trim($_POST['consumidor_email']);

		}

	}else{

		if (strlen(trim($_POST['consumidor_email'])) == 0) {
		    $xconsumidor_email = 'null';
		} else {
		    $xconsumidor_email = "'".trim(substr($_POST['consumidor_email'],0,49))."'";
		}
		$consumidor_email = trim($_POST['consumidor_email']);

	}



    if (strlen(trim($_POST['obs'])) == 0) $xobs = 'null';
    else                                  $xobs = "'".trim($_POST['obs'])."'";

    if (strlen(trim($_POST['quem_abriu_chamado'])) == 0) {
        if ($login_fabrica == 7) {
            $msg_erro .= "Digite quem abriu o Chamado.";
        } else {
            $xquem_abriu_chamado = 'null';
        }
    } else {
        $xquem_abriu_chamado = "'".trim($_POST['quem_abriu_chamado'])."'";
    }

    if (strlen($_POST['consumidor_revenda']) == 0) $msg_erro .= " Selecione consumidor ou revenda.<br />";
    else                                $xconsumidor_revenda = "'".$_POST['consumidor_revenda']."'";

    //if (strlen($_POST['type']) == 0) $xtype = 'null';
    //else             $xtype = "'".$_POST['type']."'";

    if (strlen($_POST['satisfacao']) == 0) $xsatisfacao = "'f'";
    else             $xsatisfacao = "'".$_POST['satisfacao']."'";

    if (strlen ($_POST['laudo_tecnico']) == 0) $xlaudo_tecnico = 'null';
    else        $xlaudo_tecnico = "'".trim($_POST['laudo_tecnico'])."'";

    $defeito_reclamado = trim ($_POST['defeito_reclamado']);

//if ($ip == '201.0.9.216') echo "[ $defeito_reclamado ] e ".strlen($defeito_reclamado);
//    $os = $_POST['os'];

    if ((strlen ($defeito_reclamado) == 0 AND ($login_fabrica == 95 OR $pedir_defeito_reclamado_descricao == 't')))
        $defeito_reclamado = "null";
    else if ((strlen($defeito_reclamado) == 0 AND ($login_fabrica <> 95 OR $pedir_defeito_reclamado_descricao == 't')))
        $msg_erro .= "Selecione o defeito reclamado.";

    # HD 28155
    if ($defeito_reclamado == '0' AND  ($login_fabrica <> 19  || $login_fabrica == 42) ) {
        $msg_erro .= "Selecione o defeito reclamado.<br />";
    }
    if ($defeito_reclamado == '0' AND $login_fabrica == 19){
        if ($tipo_atendimento <> 6){
            $msg_erro .= "Selecione o defeito reclamado.<br />";
        }
    }


	#HD 389165
    if ($login_fabrica <>86 and $login_fabrica <>74){

		if ($pedir_defeito_reclamado_descricao == 't' AND ($xdefeito_reclamado_descricao == 'null' OR strlen($xdefeito_reclamado_descricao) == 0)){
			$msg_erro .= " Erro: Digite o defeito reclamado.<br />";
		}

	} else if ($defeito_reclamado == 'null' and ($login_fabrica==86 or $login_fabrica == 74)){

		$msg_erro .="Erro: Selecione um Defeito Reclamado.<br/>";

	}

    //HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
    if ($login_fabrica == 3) {
        $sql = "
        SELECT
        tbl_linha.linha

        FROM
        tbl_produto
        JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha

        WHERE
        tbl_linha.linha = 528
        AND tbl_produto.referencia='" . $_POST["produto_referencia"] . "'";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res)) {
            if ($xdefeito_reclamado_descricao == 'null' OR strlen($xdefeito_reclamado_descricao) == 0) {
                $msg_erro .= "Digite o defeito reclamado adicional.<br />";
            }
        }
    }

//HD 73930 18/02/2009
    $coa_microsoft = trim($_POST['coa_microsoft']);
    if (strlen($coa_microsoft) == 0) {
        if ($login_fabrica == 43) {
            // email da Gisele para Samuel pedindo para retirar obrigatoriedade. 20/02/2009
            //$msg_erro .= " Digite o COA MIcrosoft.";
        }
    }
//

    if ($login_fabrica == 5) { // hD 61255
        if (strlen($xconsumidor_numero) == 0) $msg_erro .= " Digite o número do endereço do consumidor. <br />";
        if (strlen($xconsumidor_cep)    == 0 or $xconsumidor_cep == 'null') $msg_erro .= " Digite o CEP do consumidor. <br />";
    }

    if ($login_fabrica == 5) {
        if ($xconsumidor_fone == 'null' || $xconsumidor_fone == "") $msg_erro .= " Digite o telefone do consumidor. <br />";
    }

    //HD 206869: Exigir número de telefone do consumidor para a Salton
	//HD 413556 - Campos obrigatórios para a LeaderShip
     if ($login_fabrica == 81 or $login_fabrica == 95 or $login_fabrica == 3) {
        if  (($xconsumidor_fone == 'null' || $xconsumidor_fone == "") &&
             ($xconsumidor_celular == 'null' || $xconsumidor_celular == "") &&
             ($xconsumidor_fone_comercial == 'null' || $xconsumidor_fone_comercial == "")) {
            $msg_erro = "Digite pelo menos um telefone para o consumidor (Telefone, Celular ou Telefone Comercial)";
        }else{
			if($login_fabrica == 3) {
				$fone_verifica = (!empty($xconsumidor_fone) and $xconsumidor_fone <> 'null') ? $xconsumidor_fone : ((!empty($xconsumidor_celular) and $xconsumidor_celular <> 'null')?$xconsumidor_celular:$xconsumidor_fone_comercial);
				if($fone_verifica <> 'null') {
					$fone_valido = (!is_bool($fone_verifica = checaFone($fone_verifica)));
					if(!$fone_valido) {
						$msg_erro .= "Telefone do consumidor inválido";
					}
				}
			}
		}
    }

    if ($login_fabrica == 14 or $login_fabrica == 52) {
        if (strlen($produto_referencia) > 0 AND (strlen($xproduto_serie) == 0 OR $xproduto_serie == 'null')) {
            $sql = "SELECT  tbl_produto.numero_serie_obrigatorio
                    FROM    tbl_produto
                    JOIN    tbl_linha on tbl_linha.linha = tbl_produto.linha
                    WHERE   (upper(tbl_produto.referencia_pesquisa) = upper($produto_referencia) or upper(tbl_produto.referencia) = upper($produto_referencia))
                    AND     tbl_linha.fabrica = $login_fabrica";
            $res = @pg_query($con,$sql);
            if (pg_num_rows($res) > 0) {
                $numero_serie_obrigatorio = trim(pg_fetch_result($res,0,numero_serie_obrigatorio));

                if ($numero_serie_obrigatorio == 't') {
                    $msg_erro .= "<br />Nº de Série $produto_referencia é obrigatório.";
                }
            }
        }
    }

    $serie_auditoria = "FALSE";
    if ($login_fabrica == 50) {
        if (strlen($produto_serie) > 12)       $msg_erro .= "Número de série não pode ser maior que 12 dígitos";
        if (strlen($xproduto_serie) == 'null') $serie_auditoria = "TRUE";
        $sql = "SELECT serie FROM tbl_numero_serie WHERE serie = $xproduto_serie";
        $res = @pg_query($con,$sql);
        if (pg_num_rows($res) == 0) {
            $serie_auditoria = "TRUE";
        }
    }

    //Chamado 2354
    if ($login_fabrica == 15) {
        if ($consumidor_revenda == 'C') {
            if (strlen($xconsumidor_endereco) == 0) $msg_erro .= " Digite o endereço do consumidor. <br />";
            if (strlen($xconsumidor_numero)   == 0) $msg_erro .= " Digite o número do endereço do consumidor. <br />";
            if (strlen($xconsumidor_bairro)   == 0) $msg_erro .= " Digite o bairro do consumidor. <br />";
            if ($xconsumidor_fone == 'null'       ) $msg_erro .= " Digite o telefone do consumidor. <br />";
        }
    }
    ##### FIM DA VALIDAÇÃO DOS CAMPOS #####

    #if ($login_fabrica == 19 and $login_posto == 14068) echo "aqui ";
    #echo "<br />";
    #flush;
    // HD 51964
    if ($login_fabrica == 11) {
        if ($consumidor_revenda == 'R') {
            if ($xrevenda_fone == 'null' or strlen(trim($xrevenda_fone)) == 0) $msg_erro .= " Digite o telefone da revenda. <br />";
        }
        if ($consumidor_revenda == 'C') {
            if ($xconsumidor_fone=='null' or strlen($xconsumidor_fone) == 0) $msg_erro .= " Digite o telefone do consumidor.<br />";
        }
    }
    $os_reincidente = "'f'";

    if ($login_fabrica == 51) {
        if ($xrevenda_fone == 'null' or strlen(trim($xrevenda_fone)) == 0) $msg_erro .= " Erro: Digite o telefone da revenda. <br />";
    }

    ##### Verificação se o nº de série é reincidente para a Tectoy #####
    if ($login_fabrica == 6 and 1==2) {
        $sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
        $resX = @pg_query($con,$sqlX);
        $data_inicial = pg_fetch_result($resX,0,0);

        $sqlX = "SELECT to_char (current_date + INTERVAL '1 day', 'YYYY-MM-DD')";
        $resX = @pg_query($con,$sqlX);
        $data_final = pg_fetch_result($resX,0,0);

        if (strlen($produto_serie) > 0) {
            $sql = "SELECT  tbl_os.os            ,
                            tbl_os.sua_os        ,
                            tbl_os.data_digitacao,
                            tbl_os_extra.extrato
                    FROM    tbl_os
                    JOIN    tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                    WHERE   tbl_os.serie   = '$produto_serie'
                    AND     tbl_os.fabrica = $login_fabrica
                    AND     tbl_os.posto   = $posto
                    AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
                    ORDER BY tbl_os.data_digitacao DESC
                    LIMIT 1";
            $res = @pg_query($con,$sql);

            if (pg_num_rows($res) > 0) {
                $xxxos      = trim(pg_fetch_result($res,0,os));
                $xxxsua_os  = trim(pg_fetch_result($res,0,sua_os));
                $xxxextrato = trim(pg_fetch_result($res,0,extrato));

                if (strlen($xxxextrato) == 0) {
                    $msg_erro .= "Nº de Série $produto_serie digitado é reincidente.<br />
                    Favor reabrir a ordem de serviço $xxxsua_os e acrescentar itens.";
                } else {
                    $os_reincidente = "'t'";
                }
            }
        }
    }

    /*TAKASHI 18-12 HD-854*/
    if ($login_fabrica == 3 and $login_posto == 6359) {
        $sqlX = "SELECT to_char ($xdata_abertura::date - INTERVAL '90 days', 'YYYY-MM-DD')";
        $resX = @pg_query($con,$sqlX);
        $data_inicial = pg_fetch_result($resX,0,0);
        //echo $sqlX;
        $sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
        $resX = @pg_query($con,$sqlX);
        $data_final = pg_fetch_result($resX,0,0);

        if (strlen($produto_serie) > 0) {
            $sql = "SELECT  tbl_os.os            ,
                            tbl_os.sua_os        ,
                            tbl_os.data_digitacao,
                            tbl_os.finalizada,
                            tbl_os.data_fechamento
                    FROM    tbl_os
                    JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
                    WHERE   tbl_os.serie   = '$produto_serie'
                    AND     tbl_os.fabrica = $login_fabrica
                    AND     tbl_produto.numero_serie_obrigatorio IS TRUE
                    AND     tbl_produto.linha=3
                    ORDER BY tbl_os.data_abertura DESC
                    LIMIT 1";
            $res = @pg_query($con,$sql);
            //if ($ip=="201.42.46.223"){ echo "$sql"; }
            //AND     tbl_os.data_fechamento::date BETWEEN '$data_inicial' AND '$data_final'
            //linha 3, pois é a linha audio e video
            if (pg_num_rows($res) > 0) {
                $xxxos      = trim(pg_fetch_result($res,0,os));
                $xxfinalizada   = trim(pg_fetch_result($res,0,finalizada));
                $xx_sua_os   = trim(pg_fetch_result($res,0,sua_os));
                $xxdata_fechamento =   trim(pg_fetch_result($res,0,data_fechamento));

                if (strlen($xxfinalizada) == 0) { //aberta
                    $os_reincidente = "'t'";
                    $msg_erro .= "Este Produto já possui ordem de serviço em aberto. Por favor consultar OS $xx_sua_os.";
                } else {//fechada
                    if (($xxdata_fechamento > $data_inicial) and ($xxdata_fechamento < $data_final)) {
                        $os_reincidente = "'t'";
                    }//se a data de fechamento da ultima OS estiver no periodo de 90 dias.. seta como reincidente
                }
            }
        }
    }

    if ($login_fabrica == 79) { // HD 78055
        $sql = "SELECT cnpj,fone,contato_email
                FROM tbl_posto
                JOIN tbl_posto_fabrica USING(posto)
                WHERE fabrica = $login_fabrica
                AND   tbl_posto.posto = $login_posto ";
        $res = pg_query($con,$sql);

        if(strlen($xconsumidor_cpf) == 0 or $xconsumidor_cpf=='null') {
            $xconsumidor_cpf = preg_replace("/\D/","",pg_fetch_result($res,0,cnpj));
        }
        if(strlen($xconsumidor_fone) == 0 or $xconsumidor_fone=='null') {
            $xconsumidor_fone = pg_fetch_result($res,0,fone);
        }
        if(strlen($xconsumidor_email) == 0 or $xconsumidor_email=='null') {
            $xconsumidor_email = pg_fetch_result($res,0,contato_email);
        }

    }
    /*TAKASHI 18-12 HD-854*/

    #if ($login_fabrica == 7) $xdata_nf = $xdata_abertura;

    #if (strlen ($consumidor_cpf) <> 0 and strlen ($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';

    #if ($login_fabrica == 1 AND strlen($consumidor_cpf) == 0) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';

    $produto = 0;

    if (strlen($_POST['produto_voltagem']) == 0)    $voltagem = "null";
    else    $voltagem = "'". $_POST['produto_voltagem'] ."'";
	//HD 413556
	if ($login_fabirca == 95 and $voltagem == 'null') $msg_erro .= 'Informe a Voltagem do produto.';

    if (strlen($msg_erro) == 0) {
        $sql = "SELECT tbl_produto.produto, tbl_produto.linha
                FROM   tbl_produto
                JOIN   tbl_linha USING (linha)
                WHERE  (UPPER(tbl_produto.referencia_pesquisa) = UPPER($produto_referencia) or UPPER(tbl_produto.referencia) = UPPER($produto_referencia)) ";

        if ($login_fabrica == 1) {
            $voltagem_pesquisa = str_replace("'","",$voltagem);
            $sql .= " AND tbl_produto.voltagem ILIKE '%$voltagem_pesquisa%'";
        }
        $sql .= " AND    tbl_linha.fabrica      = $login_fabrica
                AND    tbl_produto.ativo IS TRUE";

        $res = @pg_query($con,$sql);

        if (@pg_num_rows ($res) == 0) {
            $msg_erro .= " Produto $produto_referencia não cadastrado";
        } else {
            $produto = @pg_fetch_result ($res,0,produto);
            $linha   = @pg_fetch_result ($res,0,linha);
            if ($login_fabrica == 19 and ($linha == 260 or $linha == 263) and $tipo_atendimento == 2) {//hd 4774 takashi 27/09/07
                $msg_erro = "Tipo de atendimento não permitido para o produto";
            }
        }
    }

    if ($login_fabrica == 1) {
        $sql =    "SELECT tbl_familia.familia, tbl_familia.descricao
                FROM tbl_produto
                JOIN tbl_familia USING (familia)
                WHERE tbl_familia.fabrica = $login_fabrica
                AND   tbl_familia.familia = 347
                AND   tbl_produto.produto = $produto;";
        $res = @pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {
            $xtipo_os_cortesia = "'Compressor'";
        }else{
            $xtipo_os_cortesia = 'null';
        }
    }else{
        $xtipo_os_cortesia = 'null';
    }

    #----------- OS digitada pelo Distribuidor -----------------
    $digitacao_distribuidor = "null";
    if ($distribuidor_digita == 't'){
        $codigo_posto = strtoupper (trim ($_POST['codigo_posto']));
        $codigo_posto = str_replace (" ","",$codigo_posto);
        $codigo_posto = str_replace (".","",$codigo_posto);
        $codigo_posto = str_replace ("/","",$codigo_posto);
        $codigo_posto = str_replace ("-","",$codigo_posto);

        if (strlen ($codigo_posto) > 0) {
            $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
            $res = @pg_query($con,$sql);
            if (pg_num_rows ($res) <> 1) {
                $msg_erro = "Posto $codigo_posto não cadastrado";
                $posto = $login_posto;
            }else{
                $posto = pg_fetch_result ($res,0,0);
                if ($posto <> $login_posto) {
                    $sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
                    $res = @pg_query($con,$sql);
                    if (pg_num_rows ($res) <> 1) {
                        $msg_erro = "Posto $codigo_posto não pertence a sua região";
                        $posto = $login_posto;
                    }else{
                        $posto = pg_fetch_result ($res,0,0);
                        $digitacao_distribuidor = $login_posto;
                    }
                }
            }
        }
    }
    #------------------------------------------------------
//CARTÃO CLUBE - LATINATEC
    $cartao_clube = trim($_POST['cartao_clube']);
    $cc = 0;
    if($login_fabrica == 15 AND strlen($cartao_clube) > 0 AND strlen($msg_erro) == 0){
        $sql_5 = "SELECT cartao_clube      ,
                        dt_nota_fiscal   ,
                        dt_garantia
                    FROM tbl_cartao_clube
                    WHERE cartao_clube = '$cartao_clube'
                    AND produto = '$produto' ; ";
        $res_5 = pg_query($con,$sql_5);
        if(pg_num_rows($res_5) > 0){
            $cc = "OK";
        }else{
            $msg_erro = "Verifique o produto do Cartão Clube com o da OS.";
        }
    }
    if($login_fabrica==15 and $tipo_atendimento==22){
        /*
            descricao         | familia
        --------------------------+---------
        Purificador Convencional |     787
        Purificador Eletrônico   |     788
        Purificador Hot & Cold   |     789
        Purificador Purifive          1299
        HD 107103 acrescentada a familia abaixo
        Purificador E Purifive   |     1309
        HD 241943 mais linhas
        1310 | Purificador E Mineralizer
        1311 | Purificador E Sterilizer


        */

        $sql_5 = "SELECT produto
              FROM tbl_produto
              where familia IN (787,788,789,1299,1309,1310,1311)
              AND produto = '$produto' ; ";
        $res_5 = pg_query($con,$sql_5);
        if(pg_num_rows($res_5) == 0){
            $msg_erro = "Esta OS não pode ser aberta porque o produto da instalação não é um purificador.";
        }

    }

    #HD 69612 Retirada a restrição
    /*if($login_fabrica == 3 AND $login_posto == 595 AND $consumidor_revenda == 'C'){
        # HD 54749 - HD 58328
        # HD 59226 - Não Permitir atendimento de consumidor na linha ELETRO (contrario do escrito no chamado "somente Eletro")
        $sqlL = "SELECT tbl_linha.linha, tbl_linha.nome
                FROM tbl_produto
                JOIN tbl_linha USING(linha)
                WHERE fabrica  = $login_fabrica
                AND produto    = $produto";
        $resL = pg_query($con, $sqlL);
        if(pg_num_rows($resL)>0){
            $linha_posto = pg_fetch_result($resL, 0, linha);
            $linha_nome  = pg_fetch_result($resL, 0, nome);
            if($linha_posto == 2){
                $msg_erro = "O posto não está autorizado para atendimento da linha ELETROPORTÁTEIS.";
            }
        }
    }*/


    $res = @pg_query($con,"BEGIN TRANSACTION");

    $os_offline = $_POST['os_offline'];
    if (strlen ($os_offline) == 0) $os_offline = "null";

    if ($login_fabrica<>7 and $login_fabrica <> 11) {
        $prateleira_box = strtoupper(trim($_POST['prateleira_box']));
        if (strlen ($prateleira_box) == 0) $prateleira_box = " ";
    }


    //HD 20862 20/6/2008
    if($login_fabrica==3) {
        if (strlen($_POST['atendimento_domicilio']) == 0) $xatendimento_domicilio = 'null';
        else        $xatendimento_domicilio = $_POST['atendimento_domicilio'];

        if($xatendimento_domicilio=='t'){
            $tipo_atendimento = '37';
        }
    }

    //HD 20862 20/6/2008
    if(strlen(trim($_POST['condicao']))==0) $condicao= "";
    else                                    $condicao= $_POST['condicao'];

    // HD 51454
    if(($login_tipo_posto == 214 OR $login_tipo_posto == 215 OR $login_tipo_posto == 7) and $login_fabrica ==7 ) {
        if(strlen($condicao) == 0) {
            $msg_erro .= "Por favor, selecione a condição de pagamento";
        }
    }

   $desconto_peca            = trim($_POST ['desconto_peca']);

    if(strlen($desconto_peca)==0){
        $xdesconto_peca = '0';
    }else{
        $xdesconto_peca = $desconto_peca;
    }

    if (strlen($desconto_peca)>0 AND $desconto_peca>100){
        $xdesconto_peca = 100;
    }

    $rg_produto          = trim($_POST ['rg_produto']);
    if (strlen ($rg_produto) == 0) $rg_produto = null;
    $os_posto            = trim($_POST ['os_posto']);
    if (strlen ($os_posto) == 0) $os_posto = null;
    if($login_fabrica == 30){
        if (strlen($os_posto) > 0 AND strlen($os_posto) < 8) {
            $msg_erro = "OS Revendedor deve ter no mínimo 8 dígitos.";
        }
    }

    if (in_array($login_fabrica,array(52,30,96))) {
        $admin = trim($_POST['admin']);
        $cliente_admin = trim($_POST['cliente_admin']);
        $hd_chamado = trim($_POST['hd_chamado']);
        $hd_chamado_item = trim($_POST['hd_chamado_item']);
    }

    if (strlen($hd_chamado)==0) {
        $hd_chamado = 'null';
    }

    if (strlen($msg_erro) == 0) {

        /*================ INSERE NOVA OS =========================*/
        //  O campo cidade é de 30 chars... Tem cidades com mais caracteres. Por enquanto, vamos cortar
        //  (combinado com Samuel, 24/02/2010. Manuel.)
        if (strlen($os) == 0) {
			// MLG 03/12/2010 HD 326935 - Campos limitados no início direto no _POST, e o campo
			//                            tbl_os.consumidor_cidade agora tem 70 caracteres

			if (strlen($tipo_atendimento) > 0) {
				$and_tipo_atendimento   = "tipo_atendimento ,";
				$value_tipo_atendimento = $tipo_atendimento.",";
			}

            $sql = "INSERT INTO tbl_os (
                        $and_tipo_atendimento
                        posto                                                          ,
                        fabrica                                                        ,
                        sua_os                                                         ,
                        sua_os_offline                                                 ,
                        data_abertura                                                  ,
                        hora_abertura                                                  ,
                        cliente                                                        ,
                        revenda                                                        ,
                        consumidor_nome                                                ,
                        consumidor_cpf                                                 ,
                        consumidor_fone                                                ,
                        consumidor_celular                                             ,
                        consumidor_fone_comercial                                      ,
                        consumidor_fone_recado                                         ,
                        consumidor_endereco                                            ,
                        consumidor_numero                                              ,
                        consumidor_complemento                                         ,
                        consumidor_bairro                                              ,
                        consumidor_cep                                                 ,
                        consumidor_cidade                                              ,
                        consumidor_estado                                              ,
                        revenda_cnpj                                                   ,
                        revenda_nome                                                   ,
                        revenda_fone                                                   ,
                        nota_fiscal                                                    ,
                        data_nf                                                        ,
                        produto                                                        ,
                        serie                                                          ,
                        qtde_produtos                                                  ,
                        codigo_fabricacao                                              ,
                        aparencia_produto                                              ,
                        acessorios                                                     ,
                        defeito_reclamado_descricao                                    ,
                        consumidor_email                                               ,
                        obs                                                            ,
                        quem_abriu_chamado                                             ,
                        consumidor_revenda                                             ,
                        satisfacao                                                     ,
                        laudo_tecnico                                                  ,
                        tipo_os_cortesia                                               ,
                        troca_faturada                                                 ,
                        os_offline                                                     ,
                        os_reincidente                                                 ,
                        digitacao_distribuidor                                         ,
                        tipo_os                                                        ,
                        qtde_km                                                        ,
                        certificado_garantia                                           ,
                        defeito_reclamado                                              ,
                        capacidade                                                     ,
                        versao                                                         ,
                        divisao                                                        ,
                        rg_produto                                                     ,
                        hd_chamado                                                     ,
                        os_posto                                                       " ;

            if ($login_fabrica == 7 and strlen($condicao) > 0) {
                    $sql .= ", condicao
                             , tabela ";
            }

            if ($login_fabrica<>7 and $login_fabrica <> 11) {
                   $sql.=", prateleira_box ";
            }

            if ($login_fabrica == 52 or $login_fabrica == 96 or ($login_fabrica == 30 and strlen($admin > 0))) {
                $sql .= ($admin) ? ", admin " : '';
                $sql .= ($cliente_admin) ? ", cliente_admin " : '';
            }

            $sql .= ") VALUES (
                        $value_tipo_atendimento
                        $posto                                                         ,
                        $login_fabrica                                                 ,
                        $sua_os                                                        ,
                        $sua_os_offline                                                ,
                        $xdata_abertura                                                ,
                        $xhora_abertura                                                ,
                        null                                                           ,
                        (SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj limit 1)  ,
                        $xconsumidor_nome                                              ,
                        $xconsumidor_cpf                                               ,
                        $xconsumidor_fone                                              ,
                        $xconsumidor_celular                                           ,
                        $xconsumidor_fone_comercial                                    ,
                        $xconsumidor_fone_recado                                       ,
                        '$xconsumidor_endereco'                                        ,
                        '$xconsumidor_numero'                                          ,
                        $xconsumidor_complemento                                       ,
                        '$xconsumidor_bairro'                                          ,
                        $xconsumidor_cep                                               ,
                        $xconsumidor_cidade                                            ,
                        $xconsumidor_estado                                            ,
                        $xrevenda_cnpj                                                 ,
                        $xrevenda_nome                                                 ,
                        $xrevenda_fone                                                 ,
                        $xnota_fiscal                                                  ,
                        $xdata_nf                                                      ,
                        $produto                                                       ,
                        $xproduto_serie                                                ,
                        $qtde_produtos                                                 ,
                        $xcodigo_fabricacao                                            ,
                        $xaparencia_produto                                            ,
                        $xacessorios                                                   ,
                        fn_retira_especiais($xdefeito_reclamado_descricao)             ,
                        $xconsumidor_email                                             ,
                        $xobs                                                          ,
                        $xquem_abriu_chamado                                           ,
                        $xconsumidor_revenda                                           ,
                        $xsatisfacao                                                   ,
                        $xlaudo_tecnico                                                ,
                        $xtipo_os_cortesia                                             ,
                        $xtroca_faturada                                               ,
                        $os_offline                                                    ,
                        $os_reincidente                                                ,
                        $digitacao_distribuidor                                        ,
                        $x_locacao                                                     ,
                        $qtde_km                                                       ,
                        $xcertificado_garantia                                         ,
                        $defeito_reclamado                                             ,
                        $xproduto_capacidade                                           ,
                        $xversao                                                       ,
                        $xdivisao                                                      ,
                        '$rg_produto'                                                  ,
                        $hd_chamado                                                    ,
                        '$os_posto'                                                     ";

			if ($login_fabrica == 7 and strlen($condicao) > 0) {

					$sql.=",
					$condicao													   ,
					(SELECT tabela FROM tbl_condicao
					  WHERE fabrica = $login_fabrica AND condicao = $condicao )	   ";

			}

            if ($login_fabrica<>7 and $login_fabrica <> 11) {
                $sql.=", '$prateleira_box' ";
            }

            if ($login_fabrica == 52 or $login_fabrica == 96 or ($login_fabrica == 30 and strlen($admin > 0))) {
                $sql .= ($admin) ? ", $admin " : '';
                $sql .= ($cliente_admin) ? ", $cliente_admin " : '';
            }

            $sql .= "    );";

            //echo nl2br($sql);
/*            if($login_fabrica == 24 and $login_posto == 669){
                #HD 153152
                #mail("igor@telecontrol.com.br", "SQL", "$sql");
            }*/
            //IDENTIFICA INSERÇÃO DE OS PARA VALIDAR INTERVENÇÃO NA LORENZETTI
            $nova_os = 1;

        } else {

			if (strlen($tipo_atendimento) > 0) {
				$and_tipo_atendimento =  "tipo_atendimento            = ".$tipo_atendimento.",";
			}

            $sql = "UPDATE tbl_os SET
                        $and_tipo_atendimento
                        data_abertura               = $xdata_abertura                   ,
                        hora_abertura               = $xhora_abertura                   ,
                        revenda                     = (SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj limit 1)  ,
                        consumidor_nome             = $xconsumidor_nome                 ,
                        consumidor_cpf              = $xconsumidor_cpf                  ,
                        consumidor_fone             = $xconsumidor_fone                 ,
                        consumidor_celular          = $xconsumidor_celular              ,
                        consumidor_fone_comercial   = $xconsumidor_fone_comercial       ,
                        consumidor_endereco         = '$xconsumidor_endereco'           ,
                        consumidor_numero           = '$xconsumidor_numero'             ,
                        consumidor_complemento      = $xconsumidor_complemento          ,
                        consumidor_bairro           = '$xconsumidor_bairro'             ,
                        consumidor_cep              = $xconsumidor_cep                  ,
                        consumidor_cidade           = $xconsumidor_cidade               ,
                        consumidor_estado           = $xconsumidor_estado               ,
                        revenda_cnpj                = $xrevenda_cnpj                    ,
                        revenda_nome                = $xrevenda_nome                    ,
                        revenda_fone                = $xrevenda_fone                    ,
                        nota_fiscal                 = $xnota_fiscal                     ,
                        data_nf                     = $xdata_nf                         ,
                        serie                       = $xproduto_serie                   ,
                        qtde_produtos               = $qtde_produtos                    ,
                        codigo_fabricacao           = $xcodigo_fabricacao               ,
                        aparencia_produto           = $xaparencia_produto               ,
                        defeito_reclamado_descricao = fn_retira_especiais($xdefeito_reclamado_descricao),
                        consumidor_email            = $xconsumidor_email                ,
                        consumidor_revenda          = $xconsumidor_revenda              ,
                        satisfacao                  = $xsatisfacao                      ,
                        laudo_tecnico               = $xlaudo_tecnico                   ,
                        troca_faturada              = $xtroca_faturada                  ,
                        tipo_os_cortesia            = $xtipo_os_cortesia                ,
                        tipo_os                     = $x_locacao                        ,
                        acessorios                  = $xacessorios                      ,
                        qtde_km                     = $qtde_km                          ,
                        defeito_reclamado           = $defeito_reclamado                ,
                        capacidade                  = $xproduto_capacidade              ,
                        versao                      = $xversao                          ,
                        divisao                     = $xdivisao                         ,
                        rg_produto                  = '$rg_produto'                       ,
                        os_posto                    = '$os_posto'                          ";

            if ($login_fabrica == 7 and strlen($condicao) > 0) {
                    $sql.=", condicao  = $condicao
                           , tabela    = (select tabela from tbl_condicao where fabrica = $login_fabrica and condicao = $condicao ) ";
            }

            if ($login_fabrica <> 7 and $login_fabrica <> 11) {
                $sql.=", prateleira_box= '$prateleira_box' ";
            }

            if ($login_fabrica == 7) {
                $sql.=" , produto = $produto ";
            }

            $sql.="    WHERE os      = $os
                        AND   fabrica = $login_fabrica
                        AND   posto   = $posto;";

        }

        $sql_OS = $sql;

        $res = @pg_query ($con,$sql);

        if (strlen (pg_errormessage($con)) > 0) {

            $msg_erro = pg_errormessage($con);
            $msg_erro = substr($msg_erro,6);

        }

        if (strlen ($msg_erro) == 0) {

            if (strlen($os) == 0) {
                $res = @pg_query ($con,"SELECT CURRVAL ('seq_os')");
                $os  = pg_fetch_result ($res,0,0);
            }

        }

    }

    if (strlen ($msg_erro) == 0) {

        if (strlen($os) == 0) {
            $res = @pg_query ($con,"SELECT CURRVAL ('seq_os')");
            $os  = pg_fetch_result ($res,0,0);
        }

		if (strlen($msg_erro) == 0) {

			if (in_array($login_fabrica, array(40)))
			{
				if (!empty($familia))
				{
					if ($os)
					{
						$sql       = "INSERT INTO tbl_os_campo_extra(os,fabrica,cor_produto) VALUES ($os,$login_fabrica,'$unidade_cor')";
						$res       = pg_query($con,$sql);
						$msg_erro .= pg_last_error();
					}
				}
			}

            //HD 16252 - Rotina de vários defeitos para uma única OS.
            if ($login_fabrica == 19) {

                # HD 28155
                if ($tipo_atendimento <> 6) {

                    $numero_vezes      = 100;
                    $array_integridade = array();

                    for ($i = 0; $i < $numero_vezes; $i++) {

                        $int_reclamado = trim($_POST["integridade_defeito_reclamado_$i"]);

						if (!isset($_POST["integridade_defeito_reclamado_$i"])) continue;
                        if (strlen($int_reclamado) == 0) continue;

                        $aux_defeito_reclamado = $int_reclamado;

                        array_push($array_integridade,$aux_defeito_reclamado);

                        $sql = "SELECT defeito_constatado_reclamado
                                FROM tbl_os_defeito_reclamado_constatado
                                WHERE os                = $os
                                AND   defeito_reclamado = $aux_defeito_reclamado";

                        $res = @pg_query ($con,$sql);
                        $msg_erro .= pg_errormessage($con);

                        if (@pg_num_rows($res) == 0) {

                            $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
                                        os,
                                        defeito_reclamado
                                    ) VALUES (
                                        $os,
                                        $aux_defeito_reclamado
                                    )";

                            $res = @pg_query ($con,$sql);
                            $msg_erro .= pg_errormessage($con);

                        }

                    }

                    //o defeito reclamado recebe o primeiro defeito constatado.
                    if (strlen($aux_defeito_reclamado) == 0) $msg_erro = "Quando lançar o defeito constatado é necessário clicar em adicionar defeito.";
                }

                if ($tipo_atendimento == 6 and $defeito_reclamado <> 0) {

                    $numero_vezes = 100;
                    $array_integridade = array();

                    for ($i = 0; $i < $numero_vezes; $i++) {

                        $int_reclamado = trim($_POST["integridade_defeito_reclamado_$i"]);

                        if (!isset($_POST["integridade_defeito_reclamado_$i"])) continue;
                        if (strlen($int_reclamado)==0) continue;

                        $aux_defeito_reclamado = $int_reclamado;

                        array_push($array_integridade,$aux_defeito_reclamado);

                        $sql = "SELECT defeito_constatado_reclamado
                                FROM tbl_os_defeito_reclamado_constatado
                                WHERE os                = $os
                                AND   defeito_reclamado = $aux_defeito_reclamado";

                        $res = @pg_query ($con,$sql);
                        $msg_erro .= pg_errormessage($con);

                        if (@pg_num_rows($res) == 0) {

                            $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
                                        os,
                                        defeito_reclamado
                                    )VALUES(
                                        $os,
                                        $aux_defeito_reclamado
                                    )";

                            $res = @pg_query ($con,$sql);
                            $msg_erro .= pg_errormessage($con);

                        }

                    }

                    //o defeito reclamado recebe o primeiro defeito constatado.
                    if (strlen($aux_defeito_reclamado) == 0) $msg_erro = "Quando lançar o defeito constatado é necessário clicar em adicionar defeito.";

                }

            }

        }

		//hd 289254 precisava atualizar antes de chamar a funcao valida_os
		if (in_array($login_fabrica,array(74,85,90)) ) {

			$hd_chamado = $_POST['hd_chamado'];

			if (strlen($hd_chamado) > 0) {

				$sqlinf = "UPDATE tbl_hd_chamado_extra
							  SET os = $os
							WHERE tbl_hd_chamado_extra.hd_chamado = $hd_chamado;";

				$resinf = pg_query ($con,$sqlinf);

			}

		}

		if ($login_fabrica == 96 AND !empty($hd_chamado)) { # HD 390996

			$origem_anexo  = dirname(__FILE__) . '/admin_cliente/anexos';
			$destino_anexo = dirNF($os);

			// HD 746286 - MLG - Criar o diretório, se não existe!
			if (!is_dir($destino_anexo)) mkdir($destino_anexo, 0777, true);

			for ($anx = 1; $anx < 4; $anx++) {

				$hd_chamado_anexo = ($anx == 1) ? $hd_chamado : $hd_chamado."-".$anx;
				$os_anexo         = ($anx == 1) ? $os : $os."-".$anx;
				$anexos           = glob($origem_anexo."/".$hd_chamado_anexo.".*");

				foreach($anexos as $anexo) {

					$anexo_ext = end(explode(".",$anexo));

					if (!file_exists("$destino_anexo/$os_anexo.$anexo_ext")) {
						system("cp $anexo $destino_anexo/$os_anexo.$anexo_ext");
					}

				}

			}

		}

        //CARTAO CLUBE - LATINATEC
        if ($login_fabrica == 15 AND $cc == "OK") {

            $sql_cc = "UPDATE tbl_cartao_clube SET os = $os WHERE cartao_clube = '$cartao_clube'";
            $res    = pg_query($con,$sql_cc);

        }

	if ($login_fabrica == 91) {

		$sql = "SELECT os FROM tbl_os_extra where os = $os";
		$res = pg_query($con,$sql);

		if ( !pg_num_rows($res) ) {

			$data_fabricacao = $_POST['data_fabricacao'];

			if (strlen($data_fabricacao)>0) {
			        $xdata_fabricacao = fnc_formata_data_pg(trim($data_fabricacao));
			}

			$sql = "INSERT INTO tbl_os_extra(os,data_fabricacao) VALUES ($os, $xdata_fabricacao)";
			$res = pg_query($con,$sql);
		}

	}

		//VALIDA OS
		$res = @pg_query($con, "SELECT fn_valida_os($os, $login_fabrica)");
		if (strlen(pg_errormessage($con)) > 0) $msg_erro = pg_errormessage($con);
		$msg_alerta = pg_last_notice($con);

        //OS LORENZETTI, INSERE INTERVENÇÃO
        if ($login_fabrica == 19 and $nova_os == 1) {

                if (strpos($msg_alerta, "Não fazer reparo neste produto!") > 0) {

	                $sql_int = "INSERT INTO tbl_os_status (
									os,
									status_os,
									observacao,
									status_os_troca,
									fabrica_status
								) VALUES (
									$os,
									62,
									'Produto com mão de obra maior ou igual a 80% de seu preço.',
									false,
									19
								)";

                $res = pg_query($con, $sql_int);

            }

        }

        if (($login_fabrica == 3 and $linhainf == 't') or in_array($login_fabrica, array(2, 30, 40, 43, 52, 59, 80, 81, 85, 96, 50,99))) {

			$sql        = "SELECT sua_os from tbl_os where os = $os and fabrica = $login_fabrica";
			$res        = @pg_query($con,$sql);
			$sua_os     = @pg_fetch_result($res,0,0);

			$hd_chamado      = $_POST['hd_chamado'];
			$hd_chamado_item = $_POST['hd_chamado_item'];

			if ($login_fabrica == 52  and strlen($hd_chamado) > 0 and strlen($hd_chamado_item) > 0) {

				$sqlHDChamado = "SELECT os FROM tbl_hd_chamado_item WHERE hd_chamado_item = $hd_chamado_item AND hd_chamado = $hd_chamado AND os IS NOT NULL LIMIT 1";
				$resHDChamado = pg_query($con, $sqlHDChamado);

				if (pg_numrows($resHDChamado) > 0) {

					$OSHdChamado = pg_result($resHDChamado,0,os);
					$msg_erro    = "Já existe uma OS com esse chamado: <a href='os_press.php?os=".$OSHdChamado."' target='_blank'>".$OSHdChamado."</a>";

				}

			}

			if (strlen($sua_os) > 0 and strlen($hd_chamado) > 0) {

                if ($login_fabrica <> 52 and $login_fabrica <> 30 and $login_fabrica != 96) {
                    $sqlinf = "UPDATE tbl_hd_chamado_extra SET os = $os WHERE tbl_hd_chamado_extra.hd_chamado = $hd_chamado;";
                } else {
                    $sqlinf = "UPDATE tbl_hd_chamado_item SET os = $os WHERE tbl_hd_chamado_item.hd_chamado_item = $hd_chamado_item;";
                }

                $resinf = @pg_query ($con,$sqlinf);

                if (strlen(pg_errormessage($con)) > 0) {
                    $msg_erro = pg_errormessage($con);
                    $msg_erro = substr($msg_erro,6);
                }

                if ($login_fabrica == 81) {

                    $sql = "SELECT hd_chamado from tbl_hd_chamado_item where status_item <> 'Resolvido' and produto is not null and hd_chamado = $hd_chamado";
                    $res = pg_exec($con,$sql);

                    if (pg_num_rows($res) == 0) {

                        $sql      = "UPDATE tbl_hd_chamado set status = 'Resolvido' where hd_chamado = $hd_chamado";
                        $res      = pg_exec($con,$sql);

                        $msg_erro = pg_errormessage($con);
                        $msg_erro = substr($msg_erro,6);

                    }

                }

                $sqlinf = "INSERT INTO tbl_hd_chamado_item(
								hd_chamado   ,
								data         ,
								comentario   ,
								interno      ,
								admin
							) values (
								$hd_chamado       ,
								current_timestamp ,
								'Foi aberto pelo posto a OS deste chamado com o número $sua_os'       ,
								't',
								(SELECT admin FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado limit 1)
							)";

                $resinf    = pg_query($con,$sqlinf);
                $msg_erro .= pg_errormessage($con);

            }

        }

        #--------- grava OS_EXTRA ------------------
        if (strlen($msg_erro) == 0) {

            //Master Frio ainda não tem valida_os.
            if ($login_fabrica == 40 OR $login_fabrica == 46 OR ($login_fabrica == 5 AND (strlen($sua_os) == 0 OR $sua_os == 'null'))) {

                $sql = "UPDATE tbl_os SET sua_os = $os WHERE os = $os and fabrica = $login_fabrica; ";
                $res = pg_query($con, $sql);

            }

			//===============================REVEND*****AA
            //revenda_cnpj
            if (strlen($msg_erro) == 0 AND strlen ($revenda_cnpj) > 0 and strlen ($xrevenda_cidade) > 0 AND $xrevenda_cidade <> 'null' and strlen ($xrevenda_estado) > 0 AND $xrevenda_estado<>'null') {

                $sql        = "SELECT fnc_qual_cidade($xrevenda_cidade, $xrevenda_estado)";
                $res        = pg_query($con, $sql);
                $monta_sql .= "9: $sql<br />$msg_erro<br /><br />";

                $xrevenda_cidade = pg_fetch_result($res, 0, 0);

                $sql  = "SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj";
                $res1 = pg_query ($con,$sql);

                $monta_sql .= "10: $sql<br />$msg_erro<br /><br />";

                if (pg_num_rows($res1) > 0) {

                    $revenda = pg_fetch_result ($res1, 0, 'revenda');

                    $sql = "UPDATE tbl_revenda SET
                                nome        = $xrevenda_nome          ,
                                cnpj        = $xrevenda_cnpj          ,
                                fone        = $xrevenda_fone          ,
                                endereco    = $xrevenda_endereco      ,
                                numero      = $xrevenda_numero        ,
                                complemento = $xrevenda_complemento   ,
                                bairro      = $xrevenda_bairro        ,
                                cep         = $xrevenda_cep           ,
                                cidade      = $xrevenda_cidade
                            WHERE tbl_revenda.revenda = $revenda";

                    $res3       = @pg_query ($con,$sql);
                    $msg_erro  .= pg_errormessage ($con);
                    $monta_sql .= "11: $sql<br />$msg_erro<br /><br />";

                } else {

                    $sql = "INSERT INTO tbl_revenda (
                                nome,
                                cnpj,
                                fone,
                                endereco,
                                numero,
                                complemento,
                                bairro,
                                cep,
                                cidade
                            ) VALUES (
                                $xrevenda_nome ,
                                $xrevenda_cnpj ,
                                $xrevenda_fone ,
                                $xrevenda_endereco ,
                                $xrevenda_numero ,
                                $xrevenda_complemento ,
                                $xrevenda_bairro ,
                                $xrevenda_cep ,
                                $xrevenda_cidade
                            )";

                    $res3       = @pg_query ($con,$sql);
                    $msg_erro  .= pg_errormessage ($con);
                    $monta_sql .= "12: $sql<br />$msg_erro<br /><br />";

                    $sql     = "SELECT currval ('seq_revenda')";
                    $res3    = @pg_query ($con,$sql);
                    $revenda = @pg_fetch_result ($res3, 0, 0);

                }

                $sql = "UPDATE tbl_os SET revenda = $revenda WHERE os = $os AND fabrica = $login_fabrica";
                $res = @pg_query ($con,$sql);
                $monta_sql .= "13: $sql<br />$msg_erro<br /><br />";

				if ($usa_revenda_fabrica) {//HD 234135

					if ($revenda_fabrica_status == "nao_cadastrado" || $revenda_fabrica_status == "radical") {

                        $sql = "INSERT INTO
									tbl_revenda_fabrica (
									fabrica,
									contato_razao_social,
									cnpj,
									contato_fone,
									contato_cep,
									contato_endereco,
									contato_numero,
									contato_complemento,
									contato_bairro,
									cidade,
                                    revenda
								) VALUES (
									$login_fabrica,
									$xrevenda_nome,
									$xrevenda_cnpj,
									$xrevenda_fone,
									$xrevenda_cep,
									$xrevenda_endereco,
									$xrevenda_numero,
									$xrevenda_complemento,
									$xrevenda_bairro,
									$xrevenda_cidade,
                                    $revenda
								)";

						$res = pg_query($con, $sql);
						if (pg_errormessage($con)) {
							$msg_erro .= "Falha ao cadastrar a revenda <br>";
						}

					} else if ($revenda_fabrica_status == "cadastrado") {

						$sql = "UPDATE tbl_revenda_fabrica SET
								contato_fone = $xrevenda_fone,
								contato_cep = $xrevenda_cep,
								contato_endereco = $xrevenda_endereco,
								contato_numero = $xrevenda_numero,
								contato_complemento = $xrevenda_complemento,
								contato_bairro = $xrevenda_bairro,
								cidade = $xrevenda_cidade
						WHERE fabrica = $login_fabrica
						AND cnpj = $xrevenda_cnpj";

						$res = pg_query($con, $sql);

						if (pg_errormessage($con)) {
							$msg_erro .= "Falha ao cadastrar a revenda <br>";
						}

					}

				}

            }

			//REVENDA

            $taxa_visita                = str_replace (",",".",trim ($_POST['taxa_visita']));
            $visita_por_km              = trim($_POST['visita_por_km']);
            $valor_por_km               = str_replace (",",".",trim ($_POST['valor_por_km']));
            $veiculo                    = trim ($_POST['veiculo']);
            $deslocamento_km            = str_replace (",",".",trim ($_POST['deslocamento_km']));

            $hora_tecnica               = str_replace (",",".",trim ($_POST['hora_tecnica']));

            $regulagem_peso_padrao      = str_replace (".","",trim ($_POST['regulagem_peso_padrao']));
            $regulagem_peso_padrao      = str_replace (",",".",$regulagem_peso_padrao);

            $certificado_conformidade   = str_replace (".","",trim ($_POST['certificado_conformidade']));
            $certificado_conformidade   = str_replace (",",".",$certificado_conformidade);

            $valor_diaria               = str_replace (".","",trim ($_POST['valor_diaria']));
            $valor_diaria               = str_replace (",",".",$valor_diaria);

            $cobrar_deslocamento        = trim ($_POST['cobrar_deslocamento']);
            $cobrar_hora_diaria         = trim ($_POST['cobrar_hora_diaria']);

            $desconto_deslocamento      = str_replace (",",".",trim ($_POST['desconto_deslocamento']));
            $desconto_hora_tecnica      = str_replace (",",".",trim ($_POST['desconto_hora_tecnica']));
            $desconto_diaria            = str_replace (",",".",trim ($_POST['desconto_diaria']));
            $desconto_regulagem         = str_replace (",",".",trim ($_POST['desconto_regulagem']));
            $desconto_certificado       = str_replace (",",".",trim ($_POST['desconto_certificado']));

            $cobrar_regulagem           = trim ($_POST['cobrar_regulagem']);
            $cobrar_certificado         = trim ($_POST['cobrar_certificado']);

            if ($login_tipo_posto == 215) {

                if ($desconto_deslocamento > 7) {
                    $msg_erro .= "O desconto máximo permitido para deslocamento é 7%.<br />";
                }

                if ($desconto_hora_tecnica > 7) {
                    $msg_erro .= "O desconto máximo permitido para hora técnica é 7%.<br />";
                }

                if ($desconto_diaria > 7) {
                    $msg_erro .= "O desconto máximo permitido para diára é 7%.<br />";
                }

                if ($desconto_regulagem > 7) {
                    $msg_erro .= "O desconto máximo permitido para regulagem é 7%.<br />";
                }

                if ($desconto_certificado > 7) {
                    $msg_erro .= "O desconto máximo permitido para o certificado é 7%.<br />";
                }

            }

            if (strlen($veiculo) == 0) {
                $xveiculo = "NULL";
            } else {

                $xveiculo = "'$veiculo'";

                if ($veiculo == 'carro') {
                    $valor_por_km =  str_replace (",",".",trim ($_POST['valor_por_km_carro']));
                }

                if ($veiculo == 'caminhao') {
                    $valor_por_km =  str_replace (",",".",trim ($_POST['valor_por_km_caminhao']));
                }

            }

            if (strlen($valor_por_km) > 0) {
                $xvalor_por_km  = $valor_por_km;
                $xvisita_por_km = "'t'";
            } else {
                $xvalor_por_km  = "0";
                $xvisita_por_km = "'f'";
            }

            if (strlen($taxa_visita) > 0) {
                $xtaxa_visita = $taxa_visita;
            } else {
                $xtaxa_visita = '0';
            }

            if (strlen($deslocamento_km) > 0) {
                $deslocamento_km = $deslocamento_km;
            } else {
                $deslocamento_km = '0';
            }

            /* HD 29838 */
            if ($tipo_atendimento == 63) {
                $cobrar_deslocamento = 'isento';
            }

            if ($cobrar_deslocamento == 'isento') {

                $xvisita_por_km = "'f'";
                $xvalor_por_km  = "0";
                $xtaxa_visita   = '0';
                $xveiculo       = "NULL";

            } else if ($cobrar_deslocamento == 'valor_por_km') {

                $xvisita_por_km = "'t'";
                $xtaxa_visita   = '0';

            } else if ($cobrar_deslocamento == 'taxa_visita') {

                $xvisita_por_km = "'f'";
                $xvalor_por_km  = "0";

            }

            if (strlen($valor_diaria) > 0) {
                $xvalor_diaria = $valor_diaria;
            } else {
                $xvalor_diaria = '0';
            }

            if (strlen($hora_tecnica) > 0) {
                $xhora_tecnica = $hora_tecnica;
            } else {
                $xhora_tecnica = '0';
            }

            if ($cobrar_hora_diaria == 'isento') {
                $xhora_tecnica = '0';
                $xvalor_diaria = '0';
            } else if ($cobrar_hora_diaria == 'diaria') {
                $xhora_tecnica = '0';
            } else if ($cobrar_hora_diaria == 'hora') {
                $xvalor_diaria = '0';
            }

            if (strlen($regulagem_peso_padrao) > 0 and $cobrar_regulagem == 't') {
                $xregulagem_peso_padrao = $regulagem_peso_padrao;
            } else {
                $xregulagem_peso_padrao = '0';
            }

            if (strlen($certificado_conformidade) > 0 and $cobrar_certificado == 't') {
                $xcertificado_conformidade = $certificado_conformidade;
            } else {
                $xcertificado_conformidade = "0";
            }

            /* Descontos */
            if (strlen($desconto_deslocamento) > 0) {
                $desconto_deslocamento = $desconto_deslocamento;
            } else {
                $desconto_deslocamento = '0';
            }

            if (strlen($desconto_hora_tecnica) > 0) {
                $desconto_hora_tecnica = $desconto_hora_tecnica;
            } else {
                $desconto_hora_tecnica = '0';
            }

            if (strlen($desconto_diaria) > 0) {
                $desconto_diaria = $desconto_diaria;
            } else {
                $desconto_diaria = '0';
            }

            if (strlen($desconto_regulagem) > 0) {
                $desconto_regulagem = $desconto_regulagem;
            } else {
                $desconto_regulagem = '0';
            }

            if (strlen($desconto_certificado) > 0) {
                $desconto_certificado = $desconto_certificado;
            } else {
                $desconto_certificado = '0';
            }

			if ($login_fabrica == 91 or $login_fabrica == 96) {

				$data_fabricacao = $_POST['data_fabricacao'];

				if (strlen($data_fabricacao)>0) {
					$xdata_fabricacao = fnc_formata_data_pg(trim($data_fabricacao));
				} else {
					$xdata_fabricacao = 'null';
					$msg_erro = "Favor Digite a data de Fabricação";
				}

			} else {

				$xdata_fabricacao = 'null';

			}

            $sql = "UPDATE tbl_os_extra SET
                        taxa_visita              = $xtaxa_visita             ,
                        visita_por_km            = $xvisita_por_km           ,
                        valor_por_km             = $xvalor_por_km            ,
                        hora_tecnica             = $xhora_tecnica            ,
                        regulagem_peso_padrao    = $xregulagem_peso_padrao   ,
                        certificado_conformidade = $xcertificado_conformidade,
                        valor_diaria             = $xvalor_diaria            ,
                        veiculo                  = $xveiculo                 ,
                        deslocamento_km          = $deslocamento_km          ,
                        desconto_deslocamento    = $desconto_deslocamento    ,
                        desconto_hora_tecnica    = $desconto_hora_tecnica    ,
                        desconto_diaria          = $desconto_diaria          ,
                        desconto_regulagem       = $desconto_regulagem       ,
                        desconto_certificado     = $desconto_certificado     ,
                        desconto_peca            = $xdesconto_peca           ,
                        coa_microsoft            = '$coa_microsoft'          ,
						data_fabricacao          = $xdata_fabricacao         ,
                        classificacao_os         = $classificacao_os ";

            if ($os_reincidente == "'t'") $sql .= ", os_reincidente = $xxxos ";

            $sql .= "WHERE tbl_os_extra.os = $os";
            $res = @pg_query ($con,$sql);
            $msg_erro .= pg_errormessage($con);

			//HD 682454 removido desse arquivo, validacao colocada na valida_os da wanke. HD 805857

			if ($anexaNotaFiscal) {

				if (is_array($_FILES['foto_nf']) and $_FILES['foto_nf']['name'] != '') {

					$anexou = anexaNF($os, $_FILES['foto_nf']);

					if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou;

				}

			}

			if ($login_fabrica == 42 && in_array($tipo_atendimento, array(103,104))) {//HD 400603
				$fabricas_anexam_NF[$login_fabrica]['nf_obrigatoria'] = true;
			}

			// HD 350051 - Obrigatoriedade para as que exigem imagem da NF.
			if ($anexaNotaFiscal and !temNF($os, 'bool') and !$msg_erro and
			(($login_fabrica == 43 and $consumidor_revenda == 'C') or // HD 354997 - ImgNF obrig. para 43 só OS Consumidor
			 ($login_fabrica != 43 and $login_fabrica != 72 and $fabricas_anexam_NF[$login_fabrica]['nf_obrigatoria'] == true))) $msg_erro .= "Não pode ser gravada a OS sem que haja uma imagem da Nota Fiscal.";

			// FIM Anexa imagem NF

			$entra_intervencao_famastil = 'f';

			if ($login_fabrica == 86) {//HD 416877 - INICIO -  Reincidencia famastil

				$sql = "SELECT tbl_os_status.status_os
							FROM tbl_os_status
							JOIN tbl_os using(os)
							WHERE tbl_os.fabrica = $login_fabrica
							AND tbl_os.os = $os
							ORDER BY data DESC LIMIT 1;";

				$res = pg_query($con,$sql);

				if (pg_num_rows($res) > 0) {
					$ultimo_status_interv = pg_result($res,0,'status_os');
				} else {
					$ultimo_status_interv = "";
				}

				if ( $ultimo_status_interv <> 62 || empty($ultimo_status_interv)) {
					$entra_intervencao_famastil = 't';
				}

				if ($entra_intervencao_famastil == 't') {

					$sql = "INSERT INTO tbl_os_status (
								os,
								status_os,
								data,
								observacao
							) values (
								$os,
								62,
								current_timestamp,
								'OS com intervenção técnica'
							)";

					$res = pg_query ($con, $sql);

					if (strlen(pg_errormessage($con)) > 0) {
						$msg_erro = pg_errormessage($con);
					}

				}

			} //HD 416877 - FIM

            if (strlen ($msg_erro) == 0) {

                $res = @pg_query ($con,"COMMIT TRANSACTION");

                if ($login_fabrica == 80) {
                    $sql_hd_chamado = "UPDATE tbl_hd_chamado set status = 'Resolvido' where hd_chamado = $hd_chamado";
                    $res = pg_query($con,$sql_hd_chamado);
                    $msg_erro .= pg_errormessage($con);
                }

                //Envia e-mail para o consumidor, avisando da abertura da OS
                if ($login_fabrica == 14 || $login_fabrica == 43 || $login_fabrica == 66) {//HD 150972
                    $novo_status_os = "ABERTA";
                    include('os_email_consumidor.php');
                }

                if (strlen($_SESSION['fabrica']) > 0) {

                    $sql = "insert into tbl_os_log (
								sua_os,
								fabrica,
								produto,
								posto,
								nota_fiscal,
								data_nf,
								data_abertura,
								digitacao,
								numero_serie,
								cnpj_revenda,
								nome_revenda,
								os_atual
							) values (
								$sua_os                                                        ,
								$login_fabrica                                                 ,
								$produto                                                       ,
								$posto                                                         ,
								$xnota_fiscal                                                  ,
								$xdata_nf                                                      ,
								$xdata_abertura                                                ,
								current_timestamp                                              ,
								$xproduto_serie                                                ,
								$xrevenda_cnpj                                                 ,
								$xrevenda_nome                                                 ,
								$os
							);";

                    $res = @pg_query($con, $sql);

                }

                if ($login_fabrica == 3 and $pedir_sua_os == 'f') {//HD 3371 e 12881

                    $sua_os_repetiu = 't';

                    while ($sua_os_repetiu == 't') {

                        $sql_sua_os = " SELECT sua_os
                                        FROM   tbl_os
                                        WHERE  fabrica =  $login_fabrica
                                        AND    posto   =  $login_posto
                                        AND    sua_os  =  (SELECT sua_os from tbl_os where os = $os)
                                        AND    os      <> $os";

                        $res_sua_os = pg_query($con, $sql_sua_os);

                        if (pg_num_rows($res_sua_os) > 0) {

                            //HD 52457 - Da um sleep se a OS for repetida. Entra neste caso somente quando duas OS estao sendo gravadas no mesmo momento.
							//Entao da um tempo para outra OS passar no processo sem duplicadas a numeracao
                            //Pausa de 1 à 15 segundos. Aleatório. Acho tempo suficiente para ouro processo executar sem repetir
                            $num = mt_rand(1,15);
                            sleep($num);

                            $sql_sua_os = "UPDATE tbl_posto_fabrica SET sua_os = (sua_os + 1) where tbl_posto_fabrica.fabrica = $login_fabrica and tbl_posto_fabrica.posto = $login_posto";
                            $res_sua_os = pg_query($con, $sql_sua_os);

                            $sql_sua_os   = " SELECT sua_os FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
                            $res_sua_os   = pg_query($con, $sql_sua_os);
                            $sua_os_atual = pg_fetch_result($res_sua_os,0,0);

                            if ($login_fabrica == 1) {

                                $sql_sua_os = "UPDATE tbl_os set sua_os = lpad('$sua_os_atual',6,'0') WHERE tbl_os.os = $os and tbl_os.fabrica = $login_fabrica";
                            }

                            #HD 12881
                            #HD 52457 - Corrigi o UPDATE abaixo
                            if ($login_fabrica == 3) {

                                $sql_sua_os = " UPDATE tbl_os SET
													sua_os    =  lpad(tbl_posto_fabrica.codigo_posto,6,'0') || lpad ('$sua_os_atual',6,'0'),
													os_numero =  (lpad(tbl_posto_fabrica.codigo_posto,6,'0') || lpad ('$sua_os_atual',6,'0'))::float
                                                FROM   tbl_posto_fabrica
                                                WHERE  tbl_os.os      = $os
                                                and    tbl_os.fabrica = $login_fabrica
                                                and    tbl_posto_fabrica.posto = tbl_os.posto
                                                and    tbl_posto_fabrica.fabrica = $login_fabrica";

                            }

                            $res_sua_os = pg_query($con, $sql_sua_os);

                        } else {
                            $sua_os_repetiu = 'f';
                        }

                    }

                }

				/*  HD 7998 - TAKASHI 12/12/2007 - VERIFICA SE EXISTE ALGUM CHAMADO DE CALLCENTER PARA ESSE PRODUTO,
					SE TIVER REABRE O CHAMADO INSERE UM CHAMADO ITEM, MANDA EMAIL PARA O SUPERVISOR E PARA QUEM ABRIU O CHAMADO
				 	HD 10359 - takashi 21/12/07 agora qdo o chamado estiver fechado, abre um chamado novo, esta atrapalhando no desempenho*/
				if ($login_fabrica == 6) {

					$res = @pg_query($con,"BEGIN TRANSACTION");

					$sql = "SELECT tbl_hd_chamado_extra.hd_chamado   ,
									tbl_admin.admin                  ,
									tbl_admin.nome_completo          ,
									tbl_hd_chamado.status            ,
									tbl_hd_chamado.categoria         ,
									tbl_hd_chamado_extra.produto     ,
									tbl_hd_chamado_extra.serie       ,
									tbl_admin.email
							FROM tbl_hd_chamado_extra
							JOIN tbl_hd_chamado on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
							JOIN tbl_admin on tbl_hd_chamado.atendente = tbl_admin.admin
							WHERE tbl_hd_chamado_extra.produto = $produto
							AND tbl_hd_chamado_extra.serie     = $xproduto_serie
							AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
							and tbl_hd_chamado.status <> 'Cancelado'
							ORDER BY tbl_hd_chamado.data DESC";

					$res = pg_query($con,$sql);

					if (pg_num_rows($res) > 0) {

						$hd_chamado           = pg_fetch_result($res, 0, 'hd_chamado');
						$atendente            = pg_fetch_result($res, 0, 'admin');
						$atendente_nome       = pg_fetch_result($res, 0, 'nome_completo');
						$atendente_email      = pg_fetch_result($res, 0, 'email');
						$hd_chamado_status    = pg_fetch_result($res, 0, 'status');
						$hd_chamado_categoria = pg_fetch_result($res, 0, 'categoria');
						$hd_chamado_produto   = pg_fetch_result($res, 0, 'produto');
						$hd_chamado_serie     = pg_fetch_result($res, 0, 'serie');

						# HD 46952
						$sqlQato = "SELECT count (hd_chamado) AS chamados FROM tbl_hd_chamado WHERE atendente = 1631 AND status = 'Aberto'";
						$resQato = pg_query($con,$sqlQato);

						if (pg_num_rows($resQato)>0){
							$chamados_aatendente = pg_fetch_result($resQato,0,chamados);
						}

						$sqlQbto = "SELECT count (hd_chamado) AS chamados FROM tbl_hd_chamado WHERE atendente = 1348 AND status = 'Aberto'";
						$resQbto = pg_query($con,$sqlQbto);

						if (pg_num_rows($resQbto) > 0) {
							$chamados_batendente = pg_fetch_result($resQbto,0,chamados);
						}

						if ($chamados_aatendente <= $chamados_batendente) {
							# HD 46952
							#   Encaminhar os callcenter para a renatabrito ou para a queiroz
							#   Verificar quem tem menos chamados e encaminha para esta
							#   Se as duas tiverem com o mesmo número de chamados encaminha para a renatabrito
							$sql = "SELECT admin, nome_completo, email FROM tbl_admin WHERE admin = 1631";
							$res = pg_query($con,$sql);

							if (pg_num_rows($res) > 0) {

								$atendente       = pg_fetch_result($res, 0, 'admin');
								$atendente_nome  = pg_fetch_result($res, 0, 'nome_completo');
								$atendente_email = pg_fetch_result($res, 0, 'email');

							}

						} else {

							$sql = "SELECT admin, nome_completo, email FROM tbl_admin WHERE admin = 1348";
							$res = pg_query($con,$sql);

							if (pg_num_rows($res) > 0) {

								$atendente       = pg_fetch_result($res, 0, 'admin');
								$atendente_nome  = pg_fetch_result($res, 0, 'nome_completo');
								$atendente_email = pg_fetch_result($res, 0, 'email');

							}

						}

						$sql = "SELECT  tbl_os.os                             ,
										tbl_os.sua_os                         ,
										tbl_os.data_abertura                  ,
										tbl_os.hora_abertura                  ,
										tbl_os.nota_fiscal                    ,
										tbl_os.serie                          ,
										tbl_os.data_nf                        ,
										tbl_os.produto                        ,
										tbl_os.posto                          ,
										tbl_os.consumidor_nome                ,
										tbl_os.consumidor_cpf                 ,
										tbl_os.consumidor_email               ,
										tbl_os.consumidor_fone                ,
										tbl_os.consumidor_cep                 ,
										tbl_os.consumidor_endereco            ,
										tbl_os.consumidor_numero              ,
										tbl_os.consumidor_complemento         ,
										tbl_os.consumidor_bairro              ,
										tbl_os.consumidor_cidade              ,
										tbl_os.consumidor_estado              ,
										tbl_os.defeito_reclamado              ,
										tbl_os.data_abertura                  ,
										tbl_os.os_posto                       ,
										tbl_revenda.revenda                   ,
										tbl_revenda.nome as revenda_nome      ,
										tbl_posto_fabrica.codigo_posto        ,
										tbl_posto.nome
								FROM tbl_os
								JOIN tbl_revenda ON tbl_os.revenda = tbl_revenda.revenda
								JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
								JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
								JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
								AND tbl_posto_fabrica.fabrica = $login_fabrica
								WHERE tbl_os.os = $os";

						$res      = pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);

						if (pg_num_rows($res) > 0) {

							$os                     = pg_fetch_result($res,0,os);
							$sua_os                 = pg_fetch_result($res,0,sua_os);
							$data_abertura          = pg_fetch_result($res,0,data_abertura);
							$hora_abertura          = pg_fetch_result($res,0,hora_abertura);
							$nota_fiscal            = pg_fetch_result($res,0,nota_fiscal);
							$serie                  = pg_fetch_result($res,0,serie);
							$data_nf                = pg_fetch_result($res,0,data_nf);
							$produto                = pg_fetch_result($res,0,produto);
							$posto                  = pg_fetch_result($res,0,posto);
							$consumidor_nome        = pg_fetch_result($res,0,consumidor_nome);
							$consumidor_cpf         = pg_fetch_result($res,0,consumidor_cpf);
							$consumidor_email       = pg_fetch_result($res,0,consumidor_email);
							$consumidor_fone        = pg_fetch_result($res,0,consumidor_fone);
							$consumidor_cep         = pg_fetch_result($res,0,consumidor_cep);
							$consumidor_endereco    = pg_fetch_result($res,0,consumidor_endereco);
							$consumidor_numero      = pg_fetch_result($res,0,consumidor_numero);
							$consumidor_complemento = pg_fetch_result($res,0,consumidor_complemento);
							$consumidor_bairro      = pg_fetch_result($res,0,consumidor_bairro);
							$consumidor_cidade      = pg_fetch_result($res,0,consumidor_cidade);
							$consumidor_estado      = pg_fetch_result($res,0,consumidor_estado);
							$defeito_reclamado      = pg_fetch_result($res,0,defeito_reclamado);
							$data_abertura_os       = pg_fetch_result($res,0,data_abertura);
							$os_posto               = pg_fetch_result($res,0,os_posto);
							$revenda                = pg_fetch_result($res,0,revenda);
							$revenda_nome           = pg_fetch_result($res,0,revenda_nome);
							$codigo_posto           = pg_fetch_result($res,0,codigo_posto);
							$posto_nome             = pg_fetch_result($res,0,nome);

							$mensagem_callcenter    = "Esta mensagem é gerada automaticamente, por favor não responda. <br /><br /> O posto $codigo_posto - $posto_nome abriu a OS $sua_os com o mesmo número de série informado no chamado de Call-Center $hd_chamado";

						}

						if (strlen($msg_erro) == 0 and $hd_chamado_status <> "Resolvido") {

							$sql = "INSERT INTO tbl_hd_chamado_item (
										hd_chamado    ,
										data          ,
										comentario    ,
										admin         ,
										interno       ,
										status_item
									) values (
										$hd_chamado             ,
										current_timestamp       ,
										'$mensagem_callcenter'  ,
										$atendente              ,
										'f'                     ,
										'Aberto'
									)";

							$res       = pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);

						}

						if (strlen($msg_erro) == 0 and $hd_chamado_status <> "Resolvido") {

							$sql = "INSERT INTO tbl_hd_chamado_item (
										hd_chamado    ,
										data          ,
										comentario    ,
										admin         ,
										interno       ,
										status_item
									) values (
										$hd_chamado               ,
										current_timestamp         ,
										'$mensagem_callcenter'    ,
										$atendente                ,
										'f'                       ,
										'Aberto'
									)";

							$res       = pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);

						}

						$xxhd_chamado = $hd_chamado;

						if (strlen($msg_erro) == 0 and $hd_chamado_status == "Resolvido") {

							$sql     = "SELECT posto FROM tbl_hd_chamado WHERE hd_chamado = $xxhd_chamado AND fabrica_responsavel = $login_fabrica";
							$res     = pg_query($con,$sql);
							$xxposto = pg_fetch_result($res, 0, 0);

							if (strlen($xxposto) == 0) $xxposto = "NULL";

							/* HD 48987 - a categoria tem que ser Ocorrência. - Samuel*/
							$sql = "INSERT INTO tbl_hd_chamado (
										admin                ,
										data                 ,
										titulo               ,
										status               ,
										atendente            ,
										fabrica_responsavel  ,
										categoria            ,
										posto                ,
										fabrica
									) values (
										$atendente                                   ,
										current_timestamp                            ,
										'Atendimento da OS $sua_os - chamado $xxhd_chamado',
										'Aberto'                                     ,
										$atendente                                   ,
										$login_fabrica                               ,
										'Ocorrência'                      ,
										$xxposto                                    ,
										$login_fabrica
									)";

							$res        = pg_query($con, $sql);
							$msg_erro  .= pg_errormessage($con);
							$res        = pg_query ($con, "SELECT CURRVAL ('seq_hd_chamado')");
							$hd_chamado = pg_fetch_result($res, 0, 0);

							if (strlen($msg_erro) == 0 and strlen($hd_chamado) > 0) {


								$sql = "INSERT INTO tbl_hd_chamado_extra(
											hd_chamado             ,
											produto                ,
											nome                   ,
											endereco               ,
											numero                 ,
											complemento            ,
											bairro                 ,
											cep                    ,
											fone                   ,
											email                  ,
											cpf                    ,
											revenda                ,
											revenda_nome           ,
											posto                  ,
											os                     ,
											data_abertura_os       ,
											serie                  ,
											data_nf                ,
											nota_fiscal            ,
											defeito_reclamado      ,
											posto_nome             ,
											sua_os                 ,
											reclamado              ,
											data_abertura
											) VALUES (
											$hd_chamado,
											$produto,
											'$consumidor_nome',
											'$consumidor_endereco',
											'$consumidor_numero',
											'$consumidor_complemento',
											'$consumidor_bairro',
											'$consumidor_cep',
											'$consumidor_fone',
											'$consumidor_email',
											'$consumidor_cpf',
											$revenda,
											'$revenda_nome',
											$posto,
											$os,
											'$data_abertura_os',
											'$serie',
											'$data_nf',
											'$nota_fiscal',
											$defeito_reclamado,
											'$posto_nome',
											'$sua_os',
											'$mensagem_callcenter' ,
											current_date
											);";
								$res        = pg_query($con,$sql);
								$msg_erro  .= pg_errormessage($con);
							}
						}

						if (strlen($msg_erro) == 0) {

							$sql = "SELECT nome_completo, email FROM tbl_admin WHERE fabrica = $login_fabrica AND callcenter_supervisor IS TRUE";
							$res = pg_query($con, $sql);

							if (pg_num_rows($res) > 0) {

								for ($w = 0; pg_num_rows($res) > $w; $w++) {

									$supervisor_nome  = pg_fetch_result($res, $w, 'nome_completo');
									$supervisor_email = pg_fetch_result($res, $w, 'email');

									if (strlen($msg_erro) == 0) {

										$mensagem      = "";
										$remetente     = "Suporte <helpdesk@telecontrol.com.br>";
										$destinatario  = $supervisor_email;
										$assunto       = "OS $sua_os aberta com o mesmo número de série do chamado $hd_chamado";
										$mensagem      = $mensagem_callcenter;
										$mensagem     .= "<br />Favor acompanhar\n";
										$mensagem     .= "<br /><br />Telecontrol\n";
										$mensagem     .= "<br />www.telecontrol.com.br\n";
										$headers       = "Return-Path: <helpdesk@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";

										if (mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers)) {
											//$msg = "<br />Foi enviado um email para: ".$email_destino."<br />";
										} else {
											$msg_erro .= "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
										}

									}

								}

								if (strlen($msg_erro) == 0) {

									$mensagem      = "";
									$remetente     = "Suporte <helpdesk@telecontrol.com.br>";
									$destinatario  = $atendente_email;
									$assunto       = "OS $sua_os aberta com o mesmo número de série do chamado $hd_chamado";
									$mensagem      =  $mensagem_callcenter;
									$mensagem     .= "<br />Favor acompanhar\n";
									$mensagem     .= "<br /><br />Telecontrol\n";
									$mensagem     .= "<br />www.telecontrol.com.br\n";
									$headers       = "Return-Path: <helpdesk@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";

									if (mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers)) {
										//$msg = "<br />Foi enviado um email para: ".$email_destino."<br />";
									} else {
										$msg_erro .= "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
									}

								}

							}

						}

					}

					if (strlen($msg_erro) == 0) {
						$res = @pg_query ($con,"COMMIT TRANSACTION");
					} else {
						$res = @pg_query ($con,"ROLLBACK TRANSACTION");
					}

				}

				/* HD 7998 - TAKASHI 12/12/2007 - VERIFICA SE EXISTE ALGUM CHAMADO DE CALLCENTER PARA ESSE PRODUTO,
				 SE TIVER REABRE O CHAMADO INSERE UM CHAMADO ITEM, MANDA EMAIL PARA O SUPERVISOR E PARA QUEM ABRIU O CHAMADO*/

                // se o produto tiver TROCA OBRIGATORIA, bloqueia a OS para intervencao da fabrica
                // fabio 17/01/2007 - alterado em 04/07/2007
                // adicionado para HBTech - #HD 14830 - Fabrica 25
                // adicionado para HBTech - #HD 13618 - Fabrica 45
                // adicionado para Gama - #HD 46730 - Fabrica 51
                // Destivado  or ($login_fabrica==45  AND $login_posto==6359) - HD 13618
                // Lenoxx HD 13826
                if ($login_fabrica == 3 or $login_fabrica == 11 or $login_fabrica==25 or $login_fabrica==35 or $login_fabrica==51 or $login_fabrica==98 or $login_fabrica==106 or $login_fabrica==108 or $login_fabrica==111) {
                    $sql = "SELECT  troca_obrigatoria,
                                    intervencao_tecnica,
                                    produto_critico
                            FROM    tbl_produto
                            WHERE   produto = $produto";
                    $res = @pg_query($con,$sql);

                    if (pg_num_rows($res) > 0) {
                        $troca_obrigatoria   = trim(pg_fetch_result($res,0,troca_obrigatoria));
                        $intervencao_tecnica = trim(pg_fetch_result($res,0,intervencao_tecnica));
                        $produto_critico = trim(pg_fetch_result($res,0,produto_critico));

                        if ($troca_obrigatoria == 't' or $intervencao_tecnica=='t' or $produto_critico =='t') {

                            $sql_intervencao = "SELECT status_os
                                                FROM  tbl_os_status
                                                WHERE os = $os
                                                AND status_os IN (62,64,65)
                                                ORDER BY data DESC
                                                LIMIT 1";

                            $res_intervencao = pg_query($con, $sql_intervencao);

                            $status_os = "";

                            if (pg_num_rows ($res_intervencao) > 0){
                                $status_os = trim(pg_fetch_result($res_intervencao,0,status_os));
                            }

                            if (pg_num_rows ($res_intervencao) == 0 or $status_os == "64"){

                                if ($produto_critico == 't') {
                                    $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'O.S. com Produto Crítico')";
                                    $res = pg_query ($con,$sql);
                                }

                                if ($troca_obrigatoria == 't' and $login_fabrica != 11) {
                                    $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'O Produto desta O.S. necessita de troca.')";
                                    $res = @pg_query ($con,$sql);

                                    if ($login_fabrica == 35 and $troca_obrigatoria == 't') {

                                            //hd17603
                                            $sql = "UPDATE tbl_os SET data_fechamento = NULL,finalizada=null WHERE os = $os AND fabrica = $login_fabrica ";
                                            $res = pg_query($con,$sql);
                                            $msg_erro .= pg_errormessage($con);

                                            $sql = "SELECT os_troca,peca,os FROM tbl_os_troca WHERE os = $os AND pedido IS NULL ";
                                            $res = pg_query ($con,$sql);
                                            if(pg_num_rows($res)>0){
                                                $troca_efetuada =  pg_fetch_result($res,0,os_troca);
                                                $troca_os       =  pg_fetch_result($res,0,os);
                                                $troca_peca     =  pg_fetch_result($res,0,peca);

                                                $sql = "UPDATE tbl_os_troca SET os = 4836000 WHERE os_troca = $troca_efetuada";
                                                $res = pg_query ($con,$sql);

                                                // HD 13229
                                                if(strlen($troca_peca) > 0) {
                                                    $sql = "DELETE FROM tbl_os_item WHERE os_item IN (SELECT os_item FROM tbl_os_item JOIN tbl_os_produto USING(os_produto) WHERE os=$troca_os and peca = $troca_peca)";

                                                    $res = pg_query ($con,$sql);
                                                }
                                            }

                                            $sql = "SELECT produto,sua_os,posto FROM tbl_os WHERE os = $os;";
                                            $res = @pg_query($con,$sql);
                                            $msg_erro .= pg_errormessage($con);

                                            $produto = pg_fetch_result($res,0,produto);
                                            $sua_os  = pg_fetch_result($res,0,sua_os);
                                            $posto   = pg_fetch_result($res,0,posto);


                                        // adicionado por Fabio - Altera o status para liberado da Assis. Tec. da Fábrica caso tenha intervencao.
                                            $sql = "SELECT status_os FROM tbl_os_status WHERE os=$os AND status_os IN (62,64,65,72,73,87,88,116,117) ORDER BY data DESC LIMIT 1";
                                            $res = pg_query($con,$sql);
                                            $qtdex = pg_num_rows($res);
                                            if ($qtdex>0){
                                                $statuss=pg_fetch_result($res,0,status_os);
                                                if ($statuss=='62' || $statuss=='65' || $statuss=='72' || $statuss=='87' || $statuss=='116'){

                                                    $proximo_status = "64";

                                                    if ( $statuss == "72"){
                                                        $proximo_status = "73";
                                                    }
                                                    if ( $statuss == "87"){
                                                        $proximo_status = "88";
                                                    }
                                                    if ( $statuss == "116"){
                                                        $proximo_status = "117";
                                                    }

                                                    $sql = "INSERT INTO tbl_os_status
                                                            (os,status_os,data,observacao)
                                                            VALUES ($os,$proximo_status,current_timestamp,'OS Liberada- Troca Automatica')";
                                                    $res = pg_query($con,$sql);
                                                    $msg_erro .= pg_errormessage($con);

                                                    $id_servico_realizado        = 571;
                                                    $id_servico_realizado_ajuste = 573;
                                                    $id_solucao_os               = 472;
                                                    $defeito_constatado          = 11815;


                                                    if (strlen($id_servico_realizado_ajuste) > 0 AND strlen($id_servico_realizado) > 0) {
                                                        $sql =  "UPDATE tbl_os_item
                                                                SET servico_realizado = $id_servico_realizado_ajuste
                                                                WHERE os_item IN (
                                                                    SELECT os_item
                                                                    FROM tbl_os
                                                                    JOIN tbl_os_produto USING(os)
                                                                    JOIN tbl_os_item USING(os_produto)
                                                                    JOIN tbl_peca USING(peca)
                                                                    WHERE tbl_os.os       = $os
                                                                    AND tbl_os.fabrica    = $login_fabrica
                                                                    AND tbl_os_item.servico_realizado = $id_servico_realizado
                                                                    AND tbl_os_item.pedido IS NULL
                                                                )";
                                                        /* ************* retirado TRECHO DO SQL ABAIXO - hd: 50754 - IGOR ********** */
                                                        /*AND tbl_peca.retorna_conserto IS TRUE*/
                                                        /* Segundo Fábio, essa condição é desnecessária, pois todas peças devem ser canceladas*/


                                                        $res = pg_query($con,$sql);
                                                        $msg_erro .= pg_errormessage($con);
                                                    }

                                                    if (strlen($defeito_constatado)>0 AND strlen($id_solucao_os)>0){
                                                            $sql = "UPDATE tbl_os
                                                                SET solucao_os         = $id_solucao_os,
                                                                    defeito_constatado = $defeito_constatado
                                                                WHERE os       = $os
                                                                AND fabrica    = $login_fabrica
                                                                AND solucao_os IS NULL
                                                                AND defeito_constatado IS NULL";
                                                        $res = pg_query($con,$sql);
                                                        $msg_erro .= pg_errormessage($con);
                                                    }
                                                }
                                            }


                                            $troca_garantia_produto = $produto;

                                            $sql = "SELECT * FROM tbl_produto JOIN tbl_familia using(familia) WHERE produto = '$troca_garantia_produto' AND fabrica = $login_fabrica;";
                                            $resProd = @pg_query($con,$sql);
                                            $msg_erro .= pg_errormessage($con);

                                            if (@pg_num_rows($resProd) == 0) {
                                                    $msg_erro .= "Produto informado não encontrado";
                                            }else{
                                                    $troca_produto    = @pg_fetch_result ($resProd,0,produto);
                                                    $troca_ipi        = @pg_fetch_result ($resProd,0,ipi);
                                                    $troca_referencia = @pg_fetch_result ($resProd,0,referencia);
                                                    $troca_descricao  = @pg_fetch_result ($resProd,0,descricao);
                                            }

                                            if (strlen($msg_erro) == 0) {
                                                $sql = "SELECT * FROM tbl_peca WHERE referencia = '$troca_referencia' and fabrica = $login_fabrica;";
                                                $res = pg_query($con,$sql);
                                                $msg_erro .= pg_errormessage($con);

                                                    if (pg_num_rows($res) == 0) {
                                                        if (strlen ($troca_ipi) == 0) $troca_ipi = 10;

                                                        $sql =    "SELECT peca
                                                                FROM tbl_peca
                                                                WHERE fabrica    = $login_fabrica
                                                                AND   referencia = '$troca_garantia_produto'
                                                                LIMIT 1;";
                                                        $res = pg_query($con,$sql);
                                                        $msg_erro .= pg_errormessage($con);

                                                        if (pg_num_rows($res) > 0) {
                                                            $peca = pg_fetch_result($res,0,0);
                                                        }else{
                                                            $sql = "INSERT INTO tbl_peca (fabrica, referencia, descricao, ipi, origem, produto_acabado) VALUES ($login_fabrica, '$troca_referencia', '$troca_descricao' , $troca_ipi , 'NAC','t')" ;
                                                            $res = pg_query($con,$sql);
                                                            $msg_erro .= pg_errormessage($con);

                                                            $sql = "SELECT CURRVAL ('seq_peca')";
                                                            $res = pg_query($con,$sql);
                                                            $msg_erro .= pg_errormessage($con);
                                                            $peca = pg_fetch_result($res,0,0);
                                                        }
                                                        $sql = "INSERT INTO tbl_lista_basica (fabrica, produto,peca,qtde) VALUES ($login_fabrica, $produto, $peca, 1);" ;
                                                        $res = pg_query($con,$sql);
                                                        $msg_erro .= pg_errormessage($con);
                                                    }else{
                                                        $peca = pg_fetch_result($res,0,peca);
                                                    }
                                                        //hd 44901 - sempre inserir um os_produto
                                                    //$sql = "SELECT os_produto FROM tbl_os_produto WHERE os = $os";
                                                    //$res = pg_query($con,$sql);
                                                    //$msg_erro .= pg_errormessage($con);

                                                    //if (pg_num_rows($res) == 0) {
                                                        $sql = "INSERT INTO tbl_os_produto (os, produto) VALUES ($os, $produto);";
                                                        $res = pg_query($con,$sql);
                                                        $msg_erro .= pg_errormessage($con);

                                                        $sql = "SELECT CURRVAL ('seq_os_produto')";
                                                        $res = pg_query($con,$sql);
                                                        $msg_erro .= pg_errormessage($con);

                                                        $os_produto = pg_fetch_result($res,0,0);
                                                    //}else{
                                                    //    $os_produto = pg_fetch_result($res,0,0);
                                                    //}

                                                    $sql = "
                                                        SELECT *
                                                        FROM   tbl_os_item
                                                        JOIN   tbl_servico_realizado USING (servico_realizado)
                                                        JOIN   tbl_os_produto        ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                                        WHERE  tbl_os_produto.os = $os
                                                        AND    tbl_servico_realizado.troca_de_peca
                                                        AND    tbl_os_item.pedido NOTNULL " ;
                                                    $res = pg_query($con,$sql);
                                                    $msg_erro .= pg_errormessage($con);

                                                    if ( pg_num_rows($res) > 0 ) {
                                                        for($w = 0 ; $w < pg_num_rows($res) ; $w++ ) {
                                                            $os_item = pg_fetch_result($res,$w,os_item);
                                                            $qtde    = pg_fetch_result($res,$w,qtde);
                                                            $pedido  = pg_fetch_result($res,$w,pedido);
                                                            $pecaxx  = pg_fetch_result($res,$w,peca);

                                                            //Verifica se está faturado, se esta embarcado devolve para estoque e cancela pedido para os itens da OS

                                                            $sql = "SELECT DISTINCT
                                                                    tbl_pedido.pedido,
                                                                    tbl_peca.peca,
                                                                    tbl_peca.descricao,
                                                                    tbl_peca.referencia,
                                                                    tbl_pedido_item.qtde,
                                                                    tbl_pedido_item.pedido_item,
                                                                    tbl_pedido.exportado,
                                                                    tbl_pedido.posto,
                                                                    tbl_os_item.os_item
                                                                FROM tbl_pedido
                                                                JOIN tbl_pedido_item USING(pedido)
                                                                JOIN tbl_peca        USING(peca) ";
                                                        if($login_fabrica == 51){#HD52537 alterado apenas para a Gama pois não sei se as outras fábrica atualiza o pedido_item
                                                            $sql .= " JOIN tbl_os_item     ON tbl_os_item.pedido_item   = tbl_pedido_item.pedido_item AND tbl_os_item.peca = tbl_pedido_item.peca ";
                                                        }else{
                                                            $sql .= " JOIN tbl_os_item     ON tbl_os_item.pedido        = tbl_pedido_item.pedido AND tbl_os_item.peca = tbl_pedido_item.peca ";
                                                        }
                                                            $sql .= " JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                                                WHERE tbl_pedido.pedido       = $pedido
                                                                AND   tbl_peca.fabrica        = $login_fabrica
                                                                AND   tbl_os_produto.os       = $os
                                                                AND   tbl_pedido_item.peca    = $pecaxx
                                                                AND   tbl_pedido.distribuidor = 4311 ";
                                                            $res_dis = @pg_query($con,$sql);
                                                            $msg_erro .= pg_errormessage($con);

                                                            if (@pg_num_rows($res_dis) > 0) {
                                                                for($x=0;$x<@pg_num_rows($res_dis);$x++){

                                                                    $pedido_pedido          = pg_fetch_result($res_dis,$x,pedido);
                                                                    $pedido_peca            = pg_fetch_result($res_dis,$x,peca);
                                                                    $pedido_item            = pg_fetch_result($res_dis,$x,pedido_item);
                                                                    $pedido_qtde            = pg_fetch_result($res_dis,$x,qtde);
                                                                    $pedido_peca_referencia = pg_fetch_result($res_dis,$x,referencia);
                                                                    $pedido_peca_descricao  = pg_fetch_result($res_dis,$x,descricao);
                                                                    $pedido_posto           = pg_fetch_result($res_dis,$x,posto);
                                                                    $pedido_os_item         = pg_fetch_result($res_dis,$x,os_item);

                                                                    if($pedido_posto==4311) $troca_distribuidor = "TRUE";

                                                                    $sql = "
                                                                        SELECT DISTINCT tbl_embarque.embarque
                                                                        FROM tbl_embarque
                                                                        JOIN tbl_embarque_item USING(embarque)
                                                                        WHERE pedido_item = $pedido_item
                                                                        AND   os_item     = $pedido_os_item
                                                                        AND   faturar IS NOT NULL";

                                                                    $res_x1 = @pg_query($con,$sql);
                                                                    $tem_faturamento = @pg_num_rows($res_x1);
                                                                    if($tem_faturamento>0) {
                                                                        $troca_distribuidor = "TRUE";
                                                                        $troca_faturado     = "TRUE";
                                                                    }

                                                                    $pecas_canceladas .= "$pedido_peca_referencia - $pedido_peca_descricao ($pedido_qtde UN.),";

                                                                    $sql2 = "SELECT fn_pedido_cancela_garantia(4311,$login_fabrica,$pedido_pedido,$pedido_peca,$pedido_os_item,'Troca de Produto','null'); ";

                                                                    $res_x2 = pg_query($con,$sql2);

                                                                    $remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
                                                                    $destinatario = "helpdesk@telecontrol.com.br,";

                                                                    $assunto      = "Troca - Cancelamento de Pedido de Peça do Fabricante";
                                                                    $mensagem     = "$os trocada";
                                                                    $headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
                                                                    //Samuel tirou em 27/02/2009
                                                                    //mail($destinatario,$assunto,$mensagem,$headers);

                                                                }
                                                            }
                                                            //Cancela a peça que ainda não teve o seu pedido exportado //Raphael Giovanini
                                                            $sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde_cancelada + $qtde
                                                                WHERE pedido = $pedido
                                                                AND   pedido = tbl_pedido.pedido
                                                                AND   peca   = $pecaxx
                                                                AND   tbl_pedido.exportado IS NULL ;";
                                                            $res3 = @pg_query($con,$sql);
                                                            $msg_erro .= pg_errormessage($con);
                                                        }
                                                    }

                                                    $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE troca_produto AND fabrica = $login_fabrica" ;
                                                    $res = pg_query($con,$sql);
                                                    $msg_erro .= pg_errormessage($con);
                                                    if(pg_num_rows($res) > 0){
                                                        $servico_realizado = pg_fetch_result($res,0,0);
                                                    }
                                                    if(strlen($servico_realizado)==0) $msg_erro .= "Não existe Serviço Realizado de Troca de Produto, favor cadastrar!";

                                                    if(strlen($msg_erro)==0){
                                                        $sql = "INSERT INTO tbl_os_item (os_produto, peca, qtde, servico_realizado, admin) VALUES ($os_produto, $peca, 1,$servico_realizado, null)";

                                                        $res = pg_query($con,$sql);
                                                        $msg_erro .= pg_errormessage($con);

                                                        $sql = "SELECT data_fechamento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NOT NULL";
                                                        $res = pg_query($con,$sql);
                                                        $msg_erro .= pg_errormessage($con);


                                                        if(($login_fabrica == 3 or $login_fabrica==45 or $login_fabrica==35 OR $login_fabrica==25) AND pg_num_rows($res)==1 ) {
                                                            $sql = "UPDATE tbl_os SET
                                                                    troca_garantia          = 't',
                                                                    ressarcimento           = 'f',
                                                                    troca_garantia_admin    = $login_admin
                                                                    WHERE os = $os AND fabrica = $login_fabrica";
                                                        }else{
                                                            if($login_fabrica == 3){
                                                                $sql = "UPDATE tbl_os SET
                                                                    troca_garantia          = 't',
                                                                    ressarcimento           = 'f',
                                                                    troca_garantia_admin    = $login_admin,
                                                                    data_conserto           = CURRENT_TIMESTAMP
                                                                    WHERE os = $os AND fabrica = $login_fabrica";
                                                            }elseif($login_fabrica == 35){
                                                                # HD 65952
                                                                $sql = "UPDATE tbl_os SET
                                                                    troca_garantia          = 't',
                                                                    ressarcimento           = 'f'
                                                                    WHERE os = $os AND fabrica = $login_fabrica";
                                                            }else{
                                                                $sql = "UPDATE tbl_os SET
                                                                    troca_garantia          = 't',
                                                                    ressarcimento           = 'f',
                                                                    troca_garantia_admin    = $login_admin,
                                                                    data_fechamento         = CURRENT_DATE
                                                                    WHERE os = $os AND fabrica = $login_fabrica";
                                                            }
                                                        }
                                                        $res = @pg_query($con,$sql);
                                                        $msg_erro .= pg_errormessage($con);

                                                        $observacao_pedido = 'Troca de Produto Automatica';

                                                        $sql = "UPDATE tbl_os_extra SET
                                                                obs_nf                     = '$observacao_pedido'
                                                                WHERE os = $os;";

                                                        $res = @pg_query($con,$sql);
                                                        $msg_erro .= pg_errormessage($con);

                                                        $sql = "SELECT * FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NULL";
                                                        $res = @pg_query($con,$sql);
                                                        $msg_erro .= pg_errormessage($con);

                                                        $causa_troca = '25';
                                                        $setor = 'SAP';
                                                        $situacao_atendimento = '0';
                                                        $gerar_pedido = "'t'";

                                                            if(strlen($msg_erro) == 0 ){
                                                                $sql = "INSERT INTO tbl_os_troca (
                                                                            setor                 ,
                                                                            situacao_atendimento  ,
                                                                            os                    ,
                                                                            admin                 ,
                                                                            peca                  ,
                                                                            observacao            ,
                                                                            causa_troca           ,
                                                                            gerar_pedido          ,
                                                                            envio_consumidor      ,
                                                                            ri                    ,
                                                                            fabric
                                                                        )VALUES(
                                                                            '$setor'                 ,
                                                                            $situacao_atendimento    ,
                                                                            $os                      ,
                                                                            null                     ,
                                                                            $peca                    ,
                                                                            '$observacao_pedido'     ,
                                                                            $causa_troca             ,
                                                                            $gerar_pedido            ,
                                                                            'f'                      ,
                                                                            $os                      ,
                                                                            $login_fabrica
                                                                        )";
                                                                $res = @pg_query($con,$sql);
                                                                $msg_erro .= pg_errormessage($con);
                                                            }
                                                    }
                                                }
                                    //termina aqui troca
                                    }

                                }else if ($intervencao_tecnica == 't') { # HD 13826
                                    if ($login_fabrica == 11) {
                                        $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'OS com intervenção técnica')";
                                        $res = @pg_query ($con,$sql);

                                        $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,65,current_timestamp,'Reparo do Produto na Fábrica')";
                                        $res = @pg_query ($con,$sql);

                                        $sql = "INSERT INTO tbl_os_retorno (os) values ($os)";
                                        $res = @pg_query ($con,$sql);
                                    }
                                }
                            }
                        }
                    }
                }
                // fim TROCA OBRIGATORIA

                if ($km_auditoria == "TRUE") {
                    $sql = "SELECT status_os
                            FROM tbl_os_status
                            WHERE os = $os
                            AND status_os IN (98,99,100)
                            ORDER BY data DESC
                            LIMIT 1";
                    $res = @pg_query ($con,$sql);
                    if (pg_num_rows($res) > 0){
                        $status_os  = pg_fetch_result ($res,0,status_os);
                    }
                    if (pg_num_rows($res) == 0 OR $status_os <> "98") {
                        $sql = "INSERT INTO tbl_os_status (os,status_os,observacao,automatico) VALUES ($os,98,'$obs_km','$automatico')";
                        $res = @pg_query ($con,$sql);
                    }
                }

                if ($serie_auditoria == "TRUE") {

                    $sql = "SELECT status_os
                            FROM tbl_os_status
                            WHERE os = $os
                            AND status_os IN (102,103,104)
                            ORDER BY data DESC
                            LIMIT 1";

                    $res = @pg_query ($con,$sql);

                    if (pg_num_rows($res) > 0) {
                        $status_os  = pg_fetch_result($res, 0, 'status_os');
                    }

                    if (pg_num_rows($res) == 0 OR $status_os <> "102") {
                        $sql = "INSERT INTO tbl_os_status (os,status_os,observacao) VALUES ($os,102,'OS Aguardando aprovação de número de Série.')";
                        $res = @pg_query ($con,$sql);
                    }

                }

                if ($login_fabrica == 51 or $login_fabrica == 35) {

                        $sqlT = "SELECT tbl_produto.troca_obrigatoria
                            FROM tbl_os
                            JOIN tbl_produto USING(produto)
                            WHERE os      = $os
                            AND   fabrica = $login_fabrica";

                    $resT = pg_query ($con,$sqlT) ;

                    if (pg_num_rows($resT) > 0) {
                        $troca_obrigatoria = pg_fetch_result($resT, 0, 'troca_obrigatoria');
                    }

                }

                if ($imprimir_os == "imprimir") {

                    if ($login_fabrica == 3) {

                        $sql    = "SELECT sua_os from tbl_os where os = $os and fabrica = $login_fabrica";
                        $res    = @pg_query($con,$sql);
                        $sua_os = @pg_fetch_result($res, 0, 0);

                        header("Location: os_consulta_lite.php?btn_acao=ok&sua_os=$sua_os");
                        exit;

                    } else {

                        $qtde_estiquetas = $_POST['qtde_etiquetas'];

                        if ($login_fabrica == 7) {

                            header("Location: os_filizola_valores.php?os=$os&imprimir=1");
                            exit;

                        }

						if (($login_fabrica == 51 or $login_fabrica == 35) AND $troca_obrigatoria == "t") {

                            header("Location: os_finalizada.php?os=$os");
                            exit;

                        } else {

                            header("Location: os_item_new.php?os=$os&imprimir=1&qtde_etiq=$qtde_estiquetas");
                            exit;

                        }

                    }

                } else {

                    if ($login_fabrica == 3 or $login_fabrica == 85) {

                        $sql    = "SELECT sua_os from tbl_os where os = $os and fabrica = $login_fabrica";
                        $res    = @pg_query($con,$sql);
                        $sua_os = @pg_fetch_result($res,0,0);

                        header("Location: os_consulta_lite.php?btn_acao=ok&sua_os=$sua_os");
                        exit;

                    } else {

                        if ($login_fabrica == 7) {

                            header("Location: os_filizola_valores.php?os=$os");
                            exit;

                        }

						if (($login_fabrica == 51 or $login_fabrica == 35) AND $troca_obrigatoria == "t") {

                            header("Location: os_finalizada.php?os=$os");
                            exit;

                        } else {

                            header("Location: os_item_new.php?os=$os");
                            exit;

                        }

                    }

                }

            } else {

                $res = @pg_query ($con,"ROLLBACK TRANSACTION");

                if ($login_fabrica == 11) {

                    if (strpos ($msg_erro, "Produto fora da Garantia") > 0) {

                        $_SESSION['sua_os']        = $sua_os            ;
                        $_SESSION['fabrica']       = $login_fabrica     ;
                        $_SESSION['produto']       = $produto           ;
                        $_SESSION['posto']         = $posto             ;
                        $_SESSION['nota_fiscal']   = $xnota_fiscal      ;
                        $_SESSION['data_nf']       = $xdata_nf          ;
                        $_SESSION['data_abertura'] = $xdata_abertura    ;
                        $_SESSION['numero_serie']  = $xproduto_serie    ;
                        $_SESSION['cnpj_revenda']  = $xrevenda_cnpj     ;
                        $_SESSION['nome_revenda']  = $xrevenda_nome     ;

                    }

                }

            }

        } else {

            $res = @pg_query($con, "ROLLBACK TRANSACTION");

            if ($login_fabrica == 11) {

                if (strpos($msg_erro, "Produto fora da Garantia") > 0) {

                    $_SESSION['sua_os']        = $sua_os            ;
                    $_SESSION['fabrica']       = $login_fabrica     ;
                    $_SESSION['produto']       = $produto           ;
                    $_SESSION['posto']         = $posto             ;
                    $_SESSION['nota_fiscal']   = $xnota_fiscal      ;
                    $_SESSION['data_nf']       = $xdata_nf          ;
                    $_SESSION['data_abertura'] = $xdata_abertura    ;
                    $_SESSION['numero_serie']  = $xproduto_serie    ;
                    $_SESSION['cnpj_revenda']  = $xrevenda_cnpj     ;
                    $_SESSION['nome_revenda']  = $xrevenda_nome     ;

                }

            }

        }

    } else {

        if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
	        $msg_erro = " Data da compra maior que a data da abertura da Ordem de Serviço.";

        if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura_futura\"") > 0)
    	    $msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";

        if (strpos ($msg_erro,"tbl_os_unico") > 0)
            $msg_erro = " O Número da Ordem de Serviço do fabricante já esta cadastrado.";

        $res = @pg_query ($con,"ROLLBACK TRANSACTION");

        if ($login_fabrica == 11 ) {

            if (strpos($msg_erro, "Produto fora da Garantia") > 0) {

                $_SESSION['sua_os']        = $sua_os            ;
                $_SESSION['fabrica']       = $login_fabrica     ;
                $_SESSION['produto']       = $produto           ;
                $_SESSION['posto']         = $posto             ;
                $_SESSION['nota_fiscal']   = $xnota_fiscal      ;
                $_SESSION['data_nf']       = $xdata_nf          ;
                $_SESSION['data_abertura'] = $xdata_abertura    ;
                $_SESSION['numero_serie']  = $xproduto_serie    ;
                $_SESSION['cnpj_revenda']  = $xrevenda_cnpj     ;
                $_SESSION['nome_revenda']  = $xrevenda_nome     ;

            }

        }

    }

}

/*================ LE OS DA BASE DE DADOS =========================*/
if (strlen($_GET['os'] ) > 0) $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if (strlen ($os) > 0) {
    $sql =    "SELECT tbl_os.sua_os                                                    ,
                    to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura      ,
                    tbl_os.hora_abertura                                             ,
                    tbl_os.consumidor_nome                                           ,
                    tbl_os.consumidor_cpf                                            ,
                    tbl_os.consumidor_cidade                                         ,
                    tbl_os.consumidor_fone                                           ,
                    tbl_os.consumidor_celular                                        ,
                    tbl_os.consumidor_fone_comercial                                 ,
                    tbl_os.consumidor_estado                                         ,
                    tbl_os.consumidor_endereco                                       ,
                    tbl_os.consumidor_numero                                         ,
                    tbl_os.consumidor_complemento                                    ,
                    tbl_os.consumidor_bairro                                         ,
                    tbl_os.consumidor_cep                                            ,
                    tbl_os.revenda_cnpj                                              ,
                    tbl_os.revenda_nome                                              ,
                    tbl_os.revenda                                                   ,
                    tbl_os.nota_fiscal                                               ,
                    to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf            ,
                    tbl_os.consumidor_revenda                                        ,
                    tbl_os.aparencia_produto                                         ,
                    tbl_os.codigo_fabricacao                                         ,
                    tbl_os.type                                                      ,
                    tbl_os.satisfacao                                                ,
                    tbl_os.laudo_tecnico                                             ,
                    tbl_os.tipo_os_cortesia                                          ,
                    tbl_os.serie                                                     ,
                    tbl_os.qtde_produtos                                             ,
                    tbl_os.troca_faturada                                            ,
                    tbl_os.acessorios                                                ,
                    tbl_os.tipo_os                                                   ,
                    tbl_os.condicao                                                  ,
                    tbl_produto.produto                        AS produto            ,
                    tbl_produto.referencia                     AS produto_referencia ,
                    tbl_produto.descricao                      AS produto_descricao  ,
                    tbl_produto.voltagem                       AS produto_voltagem   ,
                    tbl_posto_fabrica.codigo_posto                                   ,
                    tbl_os.prateleira_box                                            ,
                    tbl_os.tipo_atendimento                                          ,
                    tbl_os.defeito_reclamado_descricao                               ,
                    tbl_os.quem_abriu_chamado                                        ,
                    tbl_os.capacidade                           AS produto_capacidade,
                    tbl_os.versao                               AS versao            ,
                    tbl_os.divisao                              AS divisao           ,
                    tbl_os.os_posto                                                  ,
                    tbl_os_extra.taxa_visita                                         ,
                    tbl_os_extra.visita_por_km                                       ,
                    tbl_os_extra.valor_por_km                                        ,
                    tbl_os_extra.hora_tecnica                                        ,
                    tbl_os_extra.regulagem_peso_padrao                               ,
                    tbl_os_extra.certificado_conformidade                            ,
                    tbl_os_extra.valor_diaria                                        ,
                    tbl_os_extra.veiculo                                             ,
                    tbl_os_extra.desconto_deslocamento                               ,
                    tbl_os_extra.desconto_hora_tecnica                               ,
                    tbl_os_extra.desconto_diaria                                     ,
                    tbl_os_extra.desconto_regulagem                                  ,
                    tbl_os_extra.desconto_certificado                                ,
                    tbl_os_extra.deslocamento_km                                     ,
                    tbl_os_extra.coa_microsoft                                       ,
                    tbl_os_extra.classificacao_os
            FROM tbl_os
            LEFT JOIN tbl_produto  ON tbl_produto.produto       = tbl_os.produto
            JOIN      tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $posto
            LEFT JOIN tbl_os_extra ON tbl_os.os                 = tbl_os_extra.os
            WHERE tbl_os.os = $os
            AND   tbl_os.posto = $posto
            AND   tbl_os.fabrica = $login_fabrica";
    $res = @pg_query ($con,$sql);

    if (pg_num_rows ($res) == 1) {
        $sua_os                        = pg_fetch_result ($res,0,sua_os);
        $data_abertura                = pg_fetch_result ($res,0,data_abertura);
        $hora_abertura                = pg_fetch_result ($res,0,hora_abertura);
        $consumidor_nome            = pg_fetch_result ($res,0,consumidor_nome);
        $consumidor_cpf                = pg_fetch_result ($res,0,consumidor_cpf);
        $consumidor_cidade            = pg_fetch_result ($res,0,consumidor_cidade);
        $consumidor_fone            = pg_fetch_result ($res,0,consumidor_fone);
        $consumidor_celular            = pg_fetch_result ($res,0,consumidor_celular);//15091
        $consumidor_fone_comercial    = pg_fetch_result ($res,0,consumidor_fone_comercial);
        $consumidor_estado            = pg_fetch_result ($res,0,consumidor_estado);
        //takashi 02-09
        $consumidor_endereco        = pg_fetch_result ($res,0,consumidor_endereco);
        $consumidor_numero            = pg_fetch_result ($res,0,consumidor_numero);
        $consumidor_complemento        = pg_fetch_result ($res,0,consumidor_complemento);
        $consumidor_bairro            = pg_fetch_result ($res,0,consumidor_bairro);
        $consumidor_cep                = pg_fetch_result ($res,0,consumidor_cep);
        //takashi 02-09
        $revenda_cnpj                = pg_fetch_result ($res,0,revenda_cnpj);
        $revenda_nome                = pg_fetch_result ($res,0,revenda_nome);
        $nota_fiscal                = pg_fetch_result ($res,0,nota_fiscal);
        $data_nf                    = pg_fetch_result ($res,0,data_nf);
        $consumidor_revenda            = pg_fetch_result ($res,0,consumidor_revenda);
        $aparencia_produto            = pg_fetch_result ($res,0,aparencia_produto);
        $acessorios                    = pg_fetch_result ($res,0,acessorios);
        $codigo_fabricacao            = pg_fetch_result ($res,0,codigo_fabricacao);
        $type                        = pg_fetch_result ($res,0,type);
        $satisfacao                    = pg_fetch_result ($res,0,satisfacao);
        $laudo_tecnico                = pg_fetch_result ($res,0,laudo_tecnico);
        $tipo_os_cortesia            = pg_fetch_result ($res,0,tipo_os_cortesia);
        $produto_serie                = pg_fetch_result ($res,0,serie);
        $qtde_produtos                = pg_fetch_result ($res,0,qtde_produtos);
        $produto                    = pg_fetch_result ($res,0,produto);
        $produto_referencia            = pg_fetch_result ($res,0,produto_referencia);
        $produto_descricao            = pg_fetch_result ($res,0,produto_descricao);
        $produto_voltagem            = pg_fetch_result ($res,0,produto_voltagem);
        $troca_faturada                = pg_fetch_result ($res,0,troca_faturada);
        $codigo_posto                = pg_fetch_result ($res,0,codigo_posto);
        $tipo_os                    = pg_fetch_result ($res,0,tipo_os);
        $condicao                    = pg_fetch_result ($res,0,condicao);
        $xxxrevenda                    = pg_fetch_result ($res,0,revenda);
        $tipo_atendimento            = pg_fetch_result ($res,0,tipo_atendimento);
        $defeito_reclamado_descricao= pg_fetch_result ($res,0,defeito_reclamado_descricao);
        $produto_capacidade            = pg_fetch_result ($res,0,produto_capacidade);
        $versao                        = pg_fetch_result ($res,0,versao);
        $divisao                    = pg_fetch_result ($res,0,divisao);
        $os_posto                    = pg_fetch_result ($res,0,os_posto);
        $quem_abriu_chamado            = pg_fetch_result ($res,0,quem_abriu_chamado);
        $taxa_visita                = pg_fetch_result ($res,0,taxa_visita);
        $visita_por_km                = pg_fetch_result ($res,0,visita_por_km);
        $valor_por_km                = pg_fetch_result ($res,0,valor_por_km);
        $hora_tecnica                = pg_fetch_result ($res,0,hora_tecnica);
        $regulagem_peso_padrao        = pg_fetch_result ($res,0,regulagem_peso_padrao);
        $certificado_conformidade    = pg_fetch_result ($res,0,certificado_conformidade);
        $valor_diaria                = pg_fetch_result ($res,0,valor_diaria);
        $veiculo                    = pg_fetch_result ($res,0,veiculo);
        $desconto_deslocamento        = pg_fetch_result ($res,0,desconto_deslocamento);
        $desconto_hora_tecnica        = pg_fetch_result ($res,0,desconto_hora_tecnica);
        $desconto_diaria            = pg_fetch_result ($res,0,desconto_diaria);
        $desconto_regulagem            = pg_fetch_result ($res,0,desconto_regulagem);
        $desconto_certificado        = pg_fetch_result ($res,0,desconto_certificado);
        $deslocamento_km            = pg_fetch_result ($res,0,deslocamento_km);
        $coa_microsoft                = pg_fetch_result ($res,0,coa_microsoft);
        $classificacao_os            = pg_fetch_result ($res,0,classificacao_os);


        if ($regulagem_peso_padrao > 0){
            $cobrar_regulagem = 't';
        }

        if ($certificado_conformidade > 0){
            $cobrar_certificado = 't';
        }

        if ($valor_diaria == 0 AND $hora_tecnica == 0){
            $cobrar_hora_diaria = "isento";
        }
        if ($valor_diaria > 0 AND $hora_tecnica == 0){
            $cobrar_hora_diaria = "diaria";
        }
        if ($valor_diaria == 0 AND $hora_tecnica > 0){
            $cobrar_hora_diaria = "hora";
        }

        if ($valor_por_km == 0 AND $taxa_visita == 0){
            $cobrar_deslocamento = "isento";
        }
        if ($valor_por_km > 0 AND $taxa_visita == 0){
            $cobrar_deslocamento = "valor_por_km";
        }
        if ($valor_por_km == 0 AND $taxa_visita > 0){
            $cobrar_deslocamento = "taxa_visita";
        }

        if ($veiculo == 'carro'){
            $valor_por_km_carro = $valor_por_km;
        }

        if ($veiculo == 'caminhao'){
            $valor_por_km_caminhao = $valor_por_km;
        }

        if($login_fabrica<>7 and $login_fabrica <> 11) {
            $prateleira_box        = pg_fetch_result($res,0, prateleira_box);
        }

        if (strlen($xxxrevenda)>0){
            $xsql  = "SELECT tbl_revenda.revenda,
                            tbl_revenda.nome,
                            tbl_revenda.cnpj,
                            tbl_revenda.fone,
                            tbl_revenda.endereco,
                            tbl_revenda.numero,
                            tbl_revenda.complemento,
                            tbl_revenda.bairro,
                            tbl_revenda.cep,
                            tbl_cidade.nome AS cidade,
                            tbl_cidade.estado
                            FROM tbl_revenda
                            LEFT JOIN tbl_cidade USING (cidade)
                            WHERE tbl_revenda.revenda = $xxxrevenda";
            $res1 = pg_query ($con,$xsql);
            //echo "$xsql";
            if (pg_num_rows($res1) > 0) {
                $revenda_nome = pg_fetch_result ($res1,0,nome);
                $revenda_cnpj = pg_fetch_result ($res1,0,cnpj);
                $revenda_fone = pg_fetch_result ($res1,0,fone);
                $revenda_endereco = pg_fetch_result ($res1,0,endereco);
                $revenda_numero = pg_fetch_result ($res1,0,numero);
                $revenda_complemento = pg_fetch_result ($res1,0,complemento);
                $revenda_bairro = pg_fetch_result ($res1,0,bairro);
                $revenda_cep = pg_fetch_result ($res1,0,cep);
                $revenda_cidade = pg_fetch_result ($res1,0,cidade);
                $revenda_estado = pg_fetch_result ($res1,0,estado);
            }
        }
        else if ($login_fabrica == 94 and $posto == 146534) { // HD 758032

            $sql = "SELECT contato_fone_comercial,
                           contato_cep,
                           contato_endereco,
                           contato_numero,
                           contato_complemento,
                           contato_bairro,
                           contato_cidade,
                           contato_estado
                    FROM tbl_posto_fabrica
                    JOIN tbl_posto USING(posto)
                    WHERE fabrica = $login_fabrica
                    AND cnpj = '$revenda_cnpj'";
            $res1 = pg_query ($con,$sql);
            if (pg_num_rows($res1) > 0) {
                $revenda_fone = pg_fetch_result ($res1,0,'contato_fone_comercial');
                $revenda_endereco = pg_fetch_result ($res1,0,'contato_endereco');
                $revenda_numero = pg_fetch_result ($res1,0,'contato_numero');
                $revenda_complemento = pg_fetch_result ($res1,0,'contato_complemento');
                $revenda_bairro = pg_fetch_result ($res1,0,'contato_bairro');
                $revenda_cep = pg_fetch_result ($res1,0,'contato_cep');
                $revenda_cidade = pg_fetch_result ($res1,0,'contato_cidade');
                $revenda_estado = pg_fetch_result ($res1,0,'contato_estado');
            }

        }

        $sql = "SELECT  tbl_familia_valores.taxa_visita,
                        tbl_familia_valores.hora_tecnica,
                        tbl_familia_valores.valor_diaria,
                        tbl_familia_valores.valor_por_km_caminhao,
                        tbl_familia_valores.valor_por_km_carro,
                        tbl_familia_valores.regulagem_peso_padrao,
                        tbl_familia_valores.certificado_conformidade
                FROM    tbl_os
                JOIN    tbl_produto         USING(produto)
                JOIN    tbl_familia_valores USING(familia)
                WHERE   tbl_os.os = $os
                AND     tbl_os.fabrica = $login_fabrica ";
        #echo nl2br($sql);
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0) {

            if ($cobrar_deslocamento  == 'taxa_visita'){
                $valor_por_km_caminhao    = trim(pg_fetch_result($res,0,valor_por_km_caminhao));
                $valor_por_km_carro       = trim(pg_fetch_result($res,0,valor_por_km_carro));
            }

            if ($cobrar_deslocamento  == 'valor_por_km'){
                $taxa_visita                  = trim(pg_fetch_result($res,0,taxa_visita));
                if ($veiculo == 'carro'){
                    $valor_por_km_caminhao    = trim(pg_fetch_result($res,0,valor_por_km_caminhao));
                }
                if ($veiculo == 'caminhao'){
                    $valor_por_km_carro       = trim(pg_fetch_result($res,0,valor_por_km_carro));
                }
            }

            if ($cobrar_hora_diaria == "diaria"){
                $hora_tecnica             = trim(pg_fetch_result($res,0,hora_tecnica));
            }
            if ($cobrar_hora_diaria == "hora"){
                $valor_diaria             = trim(pg_fetch_result($res,0,valor_diaria));
            }
            if ($cobrar_regulagem != "t"){
                $regulagem_peso_padrao    = trim(pg_fetch_result($res,0,regulagem_peso_padrao));
            }
            if ($cobrar_certificado != "t"){
                $certificado_conformidade = trim(pg_fetch_result($res,0,certificado_conformidade));
            }
        }

        /* HD 46784 */
        $sql = "SELECT  valor_regulagem, valor_certificado
                FROM    tbl_capacidade_valores
                WHERE   fabrica = $login_fabrica
                AND     capacidade_de <= (SELECT capacidade FROM tbl_os WHERE tbl_os.os = $os AND fabrica = $login_fabrica )
                AND     capacidade_ate >= (SELECT capacidade FROM tbl_os WHERE tbl_os.os = $os AND fabrica = $login_fabrica )";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0) {
            if ($cobrar_regulagem != "t"){
                $regulagem_peso_padrao    = trim(pg_fetch_result($res,0,valor_regulagem));
            }
            if ($cobrar_certificado != "t"){
                $certificado_conformidade = trim(pg_fetch_result($res,0,valor_certificado));
            }
        }
    }
}



/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen ($msg_erro) > 0) {
    $os                    = $_POST['os'];
    $hd_chamado         = $_POST['hd_chamado'];
    $sua_os                = $_POST['sua_os'];
    $data_abertura        = $_POST['data_abertura'];
    $hora_abertura        = $_POST['hora_abertura'];
    $consumidor_nome    = $_POST['consumidor_nome'];
    $consumidor_cpf     = $_POST['consumidor_cpf'];
    $consumidor_cidade    = $_POST['consumidor_cidade'];
    $consumidor_fone    = $_POST['consumidor_fone'];
    $consumidor_celular    = $_POST['consumidor_celular'];//hd 15091
    $consumidor_fone_comercial    = $_POST['consumidor_fone_comercial'];

    $consumidor_estado    = $_POST['consumidor_estado'];
    //takashi 02-09
    $consumidor_endereco= $_POST['consumidor_endereco'];
    $consumidor_numero    = $_POST['consumidor_numero'];
    $consumidor_complemento    = $_POST['consumidor_complemento'];
    $consumidor_bairro    = $_POST['consumidor_bairro'];
    $consumidor_cep        = $_POST['consumidor_cep'];
    //takashi 02-09
    $revenda_cnpj        = $_POST['revenda_cnpj'];
    $revenda_nome        = $_POST['revenda_nome'];
    $nota_fiscal        = $_POST['nota_fiscal'];
    $data_nf            = $_POST['data_nf'];
    $produto_referencia    = $_POST['produto_referencia'];
    $produto_descricao    = $_POST['produto_descricao'];
    $produto_voltagem    = $_POST['produto_voltagem'];
    $produto_serie        = ($login_fabrica == 40) ? strtoupper($_POST['produto_serie_ini'])."".str_pad($_POST['produto_serie'],7,"0",STR_PAD_LEFT) : $_POST['produto_serie'];
    $qtde_produtos        = $_POST['qtde_produtos'];
    $cor                = $_POST['cor'];
    $consumidor_revenda    = $_POST['consumidor_revenda'];
    $acessorios            = $_POST['acessorios'];
    $produto_capacidade    = $_POST['produto_capacidade'];
    $versao                = $_POST['versao'];
    $divisao            = $_POST['divisao'];
    $os_posto            = $_POST['os_posto'];
    $type                = $_POST['type'];
    $satisfacao            = $_POST['satisfacao'];
    $laudo_tecnico        = $_POST['laudo_tecnico'];
    $obs                = $_POST['obs'];
    $quem_abriu_chamado = $_POST['quem_abriu_chamado'];
    $taxa_visita                = $_POST['taxa_visita'];
    $visita_por_km                = $_POST['visita_por_km'];
    $valor_por_km                = $_POST['valor_por_km'];
    $hora_tecnica                = $_POST['hora_tecnica'];
    $regulagem_peso_padrao        = $_POST['regulagem_peso_padrao'];
    $certificado_conformidade    = $_POST['certificado_conformidade'];
    $valor_diaria                = $_POST['valor_diaria'];
    $deslocamento_km            = $_POST['deslocamento_km'];
    $codigo_posto                = $_POST['codigo_posto'];
    $locacao                    = $_POST['locacao'];



    if (strlen(trim($_POST['produto_referencia'])) > 0 ) {
        $sql = "SELECT  tbl_familia_valores.taxa_visita,
                        tbl_familia_valores.hora_tecnica,
                        tbl_familia_valores.valor_diaria,
                        tbl_familia_valores.valor_por_km_caminhao,
                        tbl_familia_valores.valor_por_km_carro,
                        tbl_familia_valores.regulagem_peso_padrao,
                        tbl_familia_valores.certificado_conformidade
                FROM    tbl_produto
                JOIN    tbl_familia         USING(familia)
                JOIN    tbl_familia_valores USING(familia)
                WHERE   tbl_produto.referencia = '".trim($_POST['produto_referencia'])."'
                AND     tbl_familia.fabrica = $login_fabrica ";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0) {
            $taxa_visita              = trim(pg_fetch_result($res,0,taxa_visita));
            $hora_tecnica             = trim(pg_fetch_result($res,0,hora_tecnica));
            $valor_diaria             = trim(pg_fetch_result($res,0,valor_diaria));
            $valor_por_km_caminhao    = trim(pg_fetch_result($res,0,valor_por_km_caminhao));
            $valor_por_km_carro       = trim(pg_fetch_result($res,0,valor_por_km_carro));
            $regulagem_peso_padrao    = trim(pg_fetch_result($res,0,regulagem_peso_padrao));
            $certificado_conformidade = trim(pg_fetch_result($res,0,certificado_conformidade));
        }

        /* HD 46784 */
        $sql = "SELECT  valor_regulagem, valor_certificado
                FROM    tbl_capacidade_valores
                WHERE   fabrica = $login_fabrica
                AND     capacidade_de <= (SELECT capacidade
                                            FROM tbl_produto
                                            JOIN tbl_linha USING(linha)
                                            WHERE fabrica= $login_fabrica
                                            AND tbl_produto.referencia = '".trim($_POST['produto_referencia'])."'
                                            LIMIT 1)
                AND     capacidade_ate >= (SELECT capacidade
                                            FROM tbl_produto
                                            JOIN tbl_linha USING(linha)
                                            WHERE fabrica= $login_fabrica
                                            AND tbl_produto.referencia = '".trim($_POST['produto_referencia'])."'
                                            LIMIT 1) ";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0) {
            $regulagem_peso_padrao    = number_format(trim(pg_fetch_result($res,0,valor_regulagem)),2,',','.');
            $certificado_conformidade = number_format(trim(pg_fetch_result($res,0,valor_certificado)),2,',','.');
        }
    }

    if(strpos($msg_erro,"data_nf_superior_data_abertura")){
        $msg_erro = "Data da nota fiscal não pode ser maior que a data de abertura";
    }
}

$body_onload = "javascript: document.frm_os.sua_os.focus()";

/* PASSA PARÂMETRO PARA O CABEÇALHO (não esquecer ===========*/

/* $title = Aparece no sub-menu e no título do Browser ===== */
$title = "Cadastro de Ordem de Serviço";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';

include "cabecalho.php";

if (strlen($pre_os)==0){
    $sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
    $res = @pg_query($con,$sql);
    $digita_os = pg_fetch_result ($res,0,0);
    if ($digita_os == 'f' and strlen($hd_chamado)==0) {
        echo "<H4>Sem permissão de acesso.</H4>";
        exit;
    }
}
?>

<!--=============== <FUNÇÕES> ================================! -->


<? include "javascript_pesquisas.php"; ?>
<style>
.content {
    background:#CDDBF1;
    width: 600px;
    text-align: center;
    padding: 5px 30px; /* padding greater than corner height|width */
    margin: 1em 0.25em;
    color:#000000;
    text-align:center;
}
.content h1 {
    color:black;
    font-size: 120%;
}

fieldset.valores {
    border:1px solid #4E4E4E;
}

fieldset.valores , fieldset.valores div{
    padding: 0.2em;
    font-size:10px;
    width:225px;
}

fieldset.valores label {
    float:left;
    width:43%;
    margin-right:0.2em;
    padding-top:0.2em;
    text-align:right;
}

fieldset.valores span {
    font-size:11px;
    font-weight:bold;
}


</style>

<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput-1.2.2.js"></script>
<link rel="stylesheet" href="css/reset.css" type="text/css" media="projection, screen">
<script src="js/jquery.readonly.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="js/jquery.readonly.css">

<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">

<script language='javascript' src='js/bibliotecaAJAX.js'></script>

<?php
if($login_fabrica <> 95 && $login_fabrica < 99) {
?>
	<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
	<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>
<?php
}
?>
<script language="JavaScript">

	$(document).ready(function() {
		Shadowbox.init();

		var familia = $('#familia').val();

		if (familia == 2467 || familia == 2464 || familia == 2466)
		{
			$('#unidade_cor').show('slow');
		}
		else
		{
			$('#unidade_cor').hide('slow');
			$('#familia').val('');
		}
	});

	<?php if ($login_fabrica == 35) { // HD 692399 ?>

		$().ready(function(){

			$("#produto_referencia, #produto_descricao").blur(function(){

				referencia	= $("#produto_referencia").val();
				descricao	= $("#produto_descricao").val();

				$.get( 'os_cadastro_tudo_ajax_deslocamento.php?deslocamento=t&referencia=' + referencia + '&descricao=' + descricao, function(data){

					if (data === 't') {

						$("#mostra_tipo_atendimento").show();

					}
					else {

						$("#mostra_tipo_atendimento").hide();
						$("#tipo_atendimento").val('');

						var div_mapa_visibility = $("#div_mapa").css('visibility');
						var div_mapa_msg_visibility = document.getElementById('div_mapa_msg').style.visibility;

						if (div_mapa_visibility == "visible") {
							$("#div_mapa").css('visibility', 'hidden');
							$("#div_mapa").css('position', 'absolute');
						}

						if (div_mapa_msg_visibility == "visible") {
							document.getElementById('div_mapa_msg').style.visibility = "hidden";
						}

					}

				});

			});

		});

	<?php } ?>

    //HD 121247
    function valida_consumidor_gama(){
        var pendentes = "Os Campo(s) ";
        var validados = 0;
        var consumidor_nome_x = document.getElementById('consumidor_nome').value;
        var consumidor_fone_x = document.getElementById("consumidor_fone").value;
        var consumidor_cep_x = document.getElementById("consumidor_cep").value;
        var consumidor_endereco_x = document.getElementById('consumidor_endereco').value;
        var consumidor_numero_x = document.getElementById('consumidor_numero').value;
        var consumidor_bairro_x = document.getElementById('consumidor_bairro').value;
        var consumidor_cidade_x = document.getElementById('consumidor_cidade').value;
        var consumidor_estado_x = document.getElementById('consumidor_estado').value;

        if(consumidor_nome_x.length<=0){
            pendentes += "Nome, ";
            validados = validados +1;
        }
        if(consumidor_fone_x.length<=0){
            pendentes += "Fone, ";
            validados = validados +1;
        }
        if(consumidor_cep_x.length<=0){
            pendentes += "CEP, ";
            validados = validados +1;
        }
        if(consumidor_endereco_x.length<=0){
            pendentes += "Endereço, ";
        }
        if(consumidor_numero_x.length<=0){
            pendentes += "Numero, ";
            validados = validados +1;
        }
        if(consumidor_bairro_x.length<=0){
            pendentes += "Bairro, ";
            validados = validados +1;
        }
        if(consumidor_cidade_x.length<=0){
            pendentes += "Cidade, ";
        }
        if(consumidor_estado_x.length<=0){
            pendentes += "Estado, ";
            validados = validados +1;
        }
        pendentes += "do Consumidor são OBRIGATÓRIOS!";

        if (validados == 0){
            if (document.frm_os.btn_acao.value == '' ) {
                    document.frm_os.btn_acao.value='continuar' ;
                    document.frm_os.submit()
            }else{
                    alert ('Aguarde submissão')
            }
        }else{
            alert(pendentes);
        }
        validados = 0;
        pendentes = "";
    }

    function atualizaValorKM(campo){
        if (campo.value == 'carro'){
            $('input[name=valor_por_km]').val( $('input[name=valor_por_km_carro]').val() );
        }
        if (campo.value == 'caminhao'){
            $('input[name=valor_por_km]').val( $('input[name=valor_por_km_caminhao]').val() );
        }
    }

    //HD 275256
    function verifica_familia_atendimento(){

		var fabrica      = '<?=$login_fabrica?>';
		var referencia   = $("#produto_referencia").val();
		if (referencia.length > 0){
			if (fabrica == '40')
			{
				$.ajax({
					type: "GET",
					url: "<?=$PHP_SELF?>",
					data: 'produto_referencia_familia='+referencia+'&verifica_familia=sim',
					success: function(data){
						var familia = data;

						if (familia == 2467 || familia == 2464 || familia == 2466)
						{
							$('#unidade_cor').show('slow');
							$('#familia').val(familia);
						}
						else
						{
							$('#unidade_cor').hide('slow');
							$('#familia').val('');
						}
					}
				});
			}
			else
			{
				$.ajax({
					type: "GET",
					url: "<?=$PHP_SELF?>",
					data: 'produto_referencia_familia='+referencia+'&verifica_familia=sim',
					complete: function(http) {
						results = http.responseText;
						if (results != ''){
							$('#tipo_atendimento').html(results);
						}
					}
				});
			}

		}
		else
		{
			if (fabrica == 40)
			{
				$('#unidade_cor').hide('slow');
				$('#familia').val('');
			}
		}

    }

    function atualizaCobraHoraDiaria(campo){
        if (campo.value == 'isento'){
            $('div[name=div_hora]').css('display','none');
            $('div[name=div_diaria]').css('display','none');
            $('div[name=div_desconto_hora_diaria]').css('display','none');
            $('input[name=hora_tecnica]').attr('disabled','disabled');
            $('input[name=valor_diaria]').attr('disabled','disabled');
        }
        if (campo.value == 'hora'){
            $('div[name=div_hora]').css('display','');
            $('div[name=div_diaria]').css('display','none');
            $('div[name=div_desconto_hora_diaria]').css('display','');
            $('#hora_tecnica').removeAttr("disabled")
            $('#valor_diaria').attr('disabled','disabled');
        }
        if (campo.value == 'diaria'){
            $('div[name=div_hora]').css('display','none');
            $('div[name=div_diaria]').css('display','');
            $('div[name=div_desconto_hora_diaria]').css('display','');
            $('#hora_tecnica').attr('disabled','disabled');
            $('#valor_diaria').removeAttr("disabled")
        }
    }

    function atualizaCobraDeslocamento(campo){
        if (campo.value == 'isento'){
            $('div[name=div_valor_por_km]').css('display','none');
            $('div[name=div_taxa_visita]').css('display','none');
            $('div[name=div_desconto_deslocamento]').css('display','none');
            $('input[name=valor_por_km]').attr('disabled','disabled');
            $('input[name=taxa_visita]').attr('disabled','disabled');
        }
        if (campo.value == 'valor_por_km'){
            $('div[name=div_valor_por_km]').css('display','');
            $('div[name=div_taxa_visita]').css('display','none');
            $('div[name=div_desconto_deslocamento]').css('display','');
            $('input[name=valor_por_km]').removeAttr("disabled")
            $('input[name=taxa_visita]').attr('disabled','disabled');

            $('input[name=veiculo]').each(function (){
                if (this.checked){
                    atualizaValorKM(this);
                }
            });
        }
        if (campo.value == 'taxa_visita'){
            $('div[name=div_valor_por_km]').css('display','none');
            $('div[name=div_taxa_visita]').css('display','');
            $('div[name=div_desconto_deslocamento]').css('display','');
            $('input[name=valor_por_km]').attr('disabled','disabled');
            $('input[name=taxa_visita]').removeAttr("disabled")
        }
    }

    function fnc_pesquisa_produto_serie (campo,form) {
        if (campo.value != "") {
            var url = "";
            url = "produto_serie_pesquisa.php?campo=" + campo.value + "&form=" + form ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
            janela.focus();
        }
    }

    function fnc_pesquisa_produto_modelo (campo,form) {
        if (campo.value != "") {
            var url = "";
            url = "produto_pesquisa_modelo.php?campo=" + campo.value + "&form=" + form ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
            janela.focus();
        }
    }

	function fnc_pesquisa_serie_atlas (serie, referencia, descricao) {
		if (serie.value != "") {
			var url = "";
			url = "produto_pesquisa_new_atlas.php?serie=" + serie.value + "&form=frm_os";
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela.serie   = serie;
			janela.referencia   = referencia;
			janela.descricao    = descricao;
			janela.focus();
		}else{
			alert( 'Favor inserir toda ou parte da informação para realizar a pesquisa' );
			return false;

		}
	}

<?    if ($login_fabrica == 56) {    ?>
    function fnc_pesquisa_produto_serie56 (campo,form) {
        if (campo.value != "") {
            var url = "";
            url = "produto_serie_pesquisa56.php?campo=" + campo.value + "&form=" + form ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
            janela.focus();
        }
    }
<?    }   ?>

    function busca_valores(){
        referencia   = $("input[name='produto_referencia']").val();

        if (referencia.length > 0) {
            var curDateTime = new Date();
            http5[curDateTime] = createRequestObject();
            url = "<?=$PHP_SELF?>?ajax=true&buscaValores=true&produto_referencia="+referencia+'&data='+curDateTime;
            http5[curDateTime].open('get',url);

            http5[curDateTime].onreadystatechange = function(){
                if (http5[curDateTime].readyState == 4){
                    if (http5[curDateTime].status == 200 || http4[curDateTime].status == 304){
                        var results = http5[curDateTime].responseText.split("|");
                        if (results[0] == 'ok') {
                            $('input[name=taxa_visita]').val(results[1]);
                            $('#taxa_visita').html(results[1]);
                            $('input[name=hora_tecnica]').val(results[2]);
                            $('#hora_tecnica').html(results[2]);
                            $('input[name=valor_diaria]').val(results[3]);
                            $('#valor_diaria').html(results[3]);
                            $('input[name=valor_por_km_carro]').val(results[4]);
                            $('#valor_por_km_carro').html('R$ '+results[4]);
                            $('input[name=valor_por_km_caminhao]').val(results[5]);
                            $('#valor_por_km_caminhao').html('R$ '+results[5]);
                            $('input[name=regulagem_peso_padrao]').val(results[6]);
                            $('#regulagem_peso_padrao').html(results[6]);
                            $('input[name=certificado_conformidade]').val(results[7]);
                            $('#certificado_conformidade').html(results[7]);

                            $('input[name=veiculo]').each(function (){
                                if (this.checked){
                                    atualizaValorKM(this);
                                }
                            });
                        }
                    }
                }
            }
            http5[curDateTime].send(null);
        }
    }

    $(document).ready(function(){
        $("input[rel='data']").maskedinput("99/99/9999");
        $("input[rel='hora']").maskedinput("99:99");
        $("input[rel='fone']").maskedinput("(99) 9999-9999");
        $("input[rel='cnpj']").maskedinput("99.999.999/9999-99");
        $("input[rel='coa']").maskedinput("*****-*****-*****-*****-*****"); //HD 73930 18/02/2009

        <? if ($login_fabrica == 43 or $login_fabrica == 24 or $login_fabrica == 88 or $login_fabrica == 89 or $login_fabrica == 74 or $login_fabrica == 99 or $login_fabrica == 101) {  // +89 HD 320189  ?>
  		  if ($('input[name=hd_chamado]').val() != '' && $('input[name=pre_os]').val()=='t') verificaPreOS();
		<?}?>
    });

    function verificaValorPorKm(campo){
        if (campo.checked){
            $('div[name=div_valor_por_km]').css('display','');
            $('div[name=div_taxa_visita]').css('display','none');
            $('input[name=taxa_visita]').attr("disabled", true);
        }else{
            $('div[name=div_valor_por_km]').css('display','none');
            $('div[name=div_taxa_visita]').css('display','');
            $('input[name=taxa_visita]').removeAttr("disabled");
        }
        $("input[name='veiculo']").each( function (){
            if (this.checked){
                atualizaValorKM( this );
            }
        });
    }



//valida numero p/ nota fiscal - esmaltec hd 20685
function mascara(o,f){
    v_obj=o
    v_fun=f
    setTimeout("execmascara()",1)
}

function execmascara(){
    v_obj.value=v_fun(v_obj.value)
}

function soNumeros(campo){
    return campo.replace(/\D/g,"")
}
//-----------------------------

// valida numero de serie
function mostraEsconde(){
    $("div[rel=div_ajuda]").toggle();
}

function insertOption(idObj, param, value_opt) { // hd 342317
	var x=document.getElementById(idObj);
	var options = x.getElementsByTagName("option");
	if (x.selectedIndex==0) {
		var y=document.createElement('option');
		y.text=param;
		y.value=value_opt;
		y.selected = true;
		var sel=x.options[x.selectedIndex];
		try {
			x.add(y,sel);
		}
		catch(ex) {
			x.add(y,x.selectedIndex); // IE
		}
	}
}

function verificaPreOS(){
    var numero_serie = '';
    if ($('#produto_serie').length > 0) numero_serie = $('#produto_serie').val();
    chamado      = $('input[name=hd_chamado]').val();
    chamado_item = $('input[name=hd_chamado_item]').val();

    if (numero_serie.length > 0 || chamado.length>0) {
        var curDateTime = new Date();
        http6[curDateTime] = createRequestObject();
        url = "<?=$PHP_SELF?>?ajax=true&buscaPreOS=true&serie="+numero_serie.replace('#','%23')+'&hd_chamado=' +chamado+'&hd_chamado_item='+chamado_item+'&data='+curDateTime;
        http6[curDateTime].open('get',url);

        var novo_defeito_reclamado = document.createElement("option");

        http6[curDateTime].onreadystatechange = function(){
            if (http6[curDateTime].readyState == 4){
                if (http6[curDateTime].status == 200 || http4[curDateTime].status == 304){
                    var results = http6[curDateTime].responseText.split("|");
                    if (results[0] == 'ok') {
                        for (i=1; i < results.length; i++){
                            var arrayy = results[i].split("##");
                            $('#'+arrayy[0]).val ( arrayy[1] );
                            //No caso de defeito reclamado, a busca é feito por AJAX
                            if (arrayy[0] == 'defeito_reclamado_descricao'){
                                novo_defeito_reclamado.text = arrayy[1];
                            }

                            if (arrayy[0] == 'defeito_reclamado'){
                                novo_defeito_reclamado.value  =  arrayy[1];
                                insertOption('defeito_reclamado', novo_defeito_reclamado.text,novo_defeito_reclamado.value); //adiciona o novo elemento
                            }
                        }
					} else {
						if (results[1] != "nao"){
	                        alert(results[1]);
						}
                    }
                }
            }
        }
        http6[curDateTime].send(null);
    }
}

function verificaProduto(produto,serie){
    referencia   = produto.value;
    numero_serie = serie.value;

    if (referencia.length > 0 || numero_serie.length > 0) {
        var curDateTime = new Date();
        http6[curDateTime] = createRequestObject();
        url = "<?=$PHP_SELF?>?ajax=true&buscaInformacoes=true&produto_referencia="+referencia+"&serie="+numero_serie+'&data='+curDateTime;
        http6[curDateTime].open('get',url);

        http6[curDateTime].onreadystatechange = function(){
            if (http6[curDateTime].readyState == 4){
                if (http6[curDateTime].status == 200 || http4[curDateTime].status == 304){
                    var results = http6[curDateTime].responseText.split("|");
                    if (results[0] == 'ok') {
                        if (document.getElementById('produto_capacidade')){
                            document.getElementById('produto_capacidade').value = results[1];
                        }
                        if (document.getElementById('divisao')){
                            document.getElementById('divisao').value            = results[2];
                        }
                        if (document.getElementById('versao')){
                            document.getElementById('versao').value             = results[3];
                        }
                    }else{
                        if (document.getElementById('produto_capacidade')){
                            document.getElementById('produto_capacidade').value='';
                        }
                        if (document.getElementById('divisao')){
                            document.getElementById('divisao').value='';
                        }
                        if (document.getElementById('versao')){
                            document.getElementById('versao').value='';
                        }
                    }
                }
            }
        }
        http6[curDateTime].send(null);
    }
}

function createRequestObject(){
    var request_;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
         request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
         request_ = new XMLHttpRequest();
    }
    return request_;
}


var http4 = new Array();
function fn_verifica_garantia(){
    var produto_descricao  = document.getElementById('produto_descricao').value;
    var produto_referencia = document.getElementById('produto_referencia').value;
    var serie              = document.getElementById('produto_serie').value;
    var campo              = document.getElementById('div_estendida');
    var curDateTime = new Date();
    http4[curDateTime] = createRequestObject();

    url = "callcenter_interativo_ajax.php?ajax=true&origem=os_cadastro&garantia=tue&produto_nome=" + produto_descricao + "&produto_referencia=" + produto_referencia+"&serie="+serie+"&data="+curDateTime;
    http4[curDateTime].open('get',url);

    http4[curDateTime].onreadystatechange = function(){
        if(http4[curDateTime].readyState == 1) {
            campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
        }
        if (http4[curDateTime].readyState == 4){
            if (http4[curDateTime].status == 200 || http4[curDateTime].status == 304){
                var results = http4[curDateTime].responseText;
                campo.innerHTML   = results;
            }else {
                campo.innerHTML = "Erro";
            }
        }
    }
    http4[curDateTime].send(null);
}
//------------------------------

function txtBoxFormat(objeto, sMask, evtKeyPress) {
    var i, nCount, sValue, fldLen, mskLen,bolMask, sCod, nTecla;

    if(document.all) { // Internet Explorer
        nTecla = evtKeyPress.keyCode;
    } else if(document.layers) { // Nestcape
        nTecla = evtKeyPress.which;
    } else {
        nTecla = evtKeyPress.which;
        if (nTecla == 8) {
            return true;
        }
    }

    sValue = objeto.value;

    // Limpa todos os caracteres de formatação que
    // já estiverem no campo.
    sValue = sValue.toString().replace( "-", "" );
    sValue = sValue.toString().replace( "-", "" );
    sValue = sValue.toString().replace( ".", "" );
    sValue = sValue.toString().replace( ".", "" );
    sValue = sValue.toString().replace( "/", "" );
    sValue = sValue.toString().replace( "/", "" );
    sValue = sValue.toString().replace( ":", "" );
    sValue = sValue.toString().replace( ":", "" );
    sValue = sValue.toString().replace( "(", "" );
    sValue = sValue.toString().replace( "(", "" );
    sValue = sValue.toString().replace( ")", "" );
    sValue = sValue.toString().replace( ")", "" );
    sValue = sValue.toString().replace( " ", "" );
    sValue = sValue.toString().replace( " ", "" );
    fldLen = sValue.length;
    mskLen = sMask.length;

    i = 0;
    nCount = 0;
    sCod = "";
    mskLen = fldLen;

    while (i <= mskLen) {
        bolMask = ((sMask.charAt(i) == "-") || (sMask.charAt(i) == ".") || (sMask.charAt(i) == "/") || (sMask.charAt(i) == ":"))
        bolMask = bolMask || ((sMask.charAt(i) == "(") || (sMask.charAt(i) == ")") || (sMask.charAt(i) == " "))

    if (bolMask) {
        sCod += sMask.charAt(i);
        mskLen++; }
    else {
        sCod += sValue.charAt(nCount);
        nCount++;
    }

      i++;
    }

    objeto.value = sCod;

    if (nTecla != 8) { // backspace
        if (sMask.charAt(i-1) == "9") { // apenas números...
            return ((nTecla > 47) && (nTecla < 58)); }
        else { // qualquer caracter...
            return true;
    }
    }
    else {
        return true;
    }
}

/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Função : fnc_pesquisa_consumidor_nome (nome, cpf)
=================================================================*/
function fnc_pesquisa_consumidor (campo, tipo) {
    var url = "";
    if (tipo == "nome") {
        url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
    }
    if (tipo == "cpf") {
        url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
    }
    if (tipo == "fone") {
        url = "pesquisa_consumidor.php?fone=" + campo.value + "&tipo=fone";
    }
    janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
    janela.cliente        = document.frm_os.consumidor_cliente;
    janela.nome            = document.frm_os.consumidor_nome;
    janela.cpf            = document.frm_os.consumidor_cpf;
    janela.rg            = document.frm_os.consumidor_rg;
    janela.cidade        = document.frm_os.consumidor_cidade;
    janela.estado        = document.frm_os.consumidor_estado;
    janela.fone            = document.frm_os.consumidor_fone;
    janela.endereco        = document.frm_os.consumidor_endereco;
    janela.numero        = document.frm_os.consumidor_numero;
    janela.complemento    = document.frm_os.consumidor_complemento;
    janela.bairro        = document.frm_os.consumidor_bairro;
    janela.cep            = document.frm_os.consumidor_cep;
    janela.focus();
}



function fnc_pesquisa_revenda (campo, tipo) {
    var url = "";
    if (tipo == "nome") {
        url = "pesquisa_revenda<?=$pr_suffix?>.php?nome=" + campo.value + "&tipo=nome";
    }
    if (tipo == "cnpj") {
        url = "pesquisa_revenda<?=$pr_suffix?>.php?cnpj=" + campo.value + "&tipo=cnpj";
    }
    janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
    janela.nome            = document.frm_os.revenda_nome;
    janela.cnpj            = document.frm_os.revenda_cnpj;
    janela.fone            = document.frm_os.revenda_fone;
    janela.cidade        = document.frm_os.revenda_cidade;
    janela.estado        = document.frm_os.revenda_estado;
    janela.endereco        = document.frm_os.revenda_endereco;
    janela.numero        = document.frm_os.revenda_numero;
    janela.complemento    = document.frm_os.revenda_complemento;
    janela.bairro        = document.frm_os.revenda_bairro;
    janela.cep            = document.frm_os.revenda_cep;
    janela.email        = document.frm_os.revenda_email;
    janela.focus();
}

//HD 234135
function fnc_pesquisa_revenda_fabrica() {
	var cnpj = $("#revenda_cnpj_pesquisa").val();
	cnpj = cnpj.replace(/[^0-9]/g, '');

	if (cnpj.length == 14) {
		$("#revenda_fabrica_msg").html("Aguarde enquanto a pesquisa é realizada");
		url = "os_cadastro_tudo_ajax.php?acao=pesquisa_revenda_fabrica&cnpj="+cnpj;
		requisicaoHTTP("GET", url, true, "fnc_pesquisa_revenda_fabrica_retorno");
	}
	else {
		alert('Digite o CNPJ da revenda com 14 dígitos');
	}
}

//HD 234135
function fnc_pesquisa_revenda_fabrica_retorno(retorno) {
	var retorno = retorno.split('|');
	var cnpj = $("#revenda_cnpj_pesquisa").val();
	fnc_limpa_campos_revenda();

	$("#revenda_fabrica_status").val(retorno[0]);

	switch(retorno[0]) {
		case "cnpj_invalido":
			alert('CNPJ inválido');
			$("#revenda_fabrica_msg").html("Digite o CNPJ da revenda com 14 dígitos e clique na lupa");

			$("#revenda_cnpj_pesquisa").focus();
		break;

		case "cadastrado":
			$("#revenda_cnpj").val(cnpj);
			$("#revenda_nome").val(retorno[1]);
			$("#revenda_fone").val(retorno[2]);
			$("#revenda_cep").val(retorno[3]);
			$("#revenda_endereco").val(retorno[4]);
			$("#revenda_numero").val(retorno[5]);
			$("#revenda_complemento").val(retorno[6]);
			$("#revenda_bairro").val(retorno[7]);
			$("#revenda_cidade").val(retorno[8]);
			$("#revenda_estado").val(retorno[9]);

			//350218
			$("#revenda_fabrica_msg").html("CNPJ já cadastrado: confira os dados para dar continuidade");
		break;

		case "radical":
			$("#revenda_cnpj").val(cnpj);
			$("#revenda_nome").val(retorno[1]);

			$("#revenda_fabrica_msg").html("CNPJ não cadastrado: complete os dados da revenda para prosseguir");

			$("#revenda_fone").focus();
		break;

		case "nao_cadastrado":
			$("#revenda_cnpj").val(cnpj);
			$("#revenda_fabrica_msg").html("CNPJ não cadastrado: complete os dados da revenda para prosseguir");

			$("#revenda_nome").focus();
		break;
	}

	fnc_pesquisa_revenda_status(retorno[0]);
}

function fnc_pesquisa_revenda_status(cnpj_status) {
	switch(cnpj_status) {
		case "cnpj_invalido":
			fnc_bloqueia_campos_revenda();
		break;

		case "cadastrado":
			//350218
			fnc_desbloqueia_campos_revenda();
			fnc_bloqueia_campo("revenda_nome");
		break;

		case "radical":
			fnc_desbloqueia_campos_revenda();
			fnc_bloqueia_campo("revenda_nome");
		break;

		case "nao_cadastrado":
			fnc_desbloqueia_campos_revenda();
		break;

		default:
			fnc_bloqueia_campos_revenda();
	}
}

function fnc_pesquisa_revenda_fabrica_onblur() {
	var cnpj_pesquisa = $("#revenda_cnpj_pesquisa").val();
	var cnpj = $("#revenda_cnpj").val();

	if (cnpj.length == 14 && cnpj != cnpj_pesquisa) {
		if (cnpj_pesquisa.length == 14) {
			if (confirm("Efetuar nova pesquisa com o CNPJ " + cnpj_pesquisa + ", descartando todos os dados atuais da revenda?")) {
				fnc_pesquisa_revenda_fabrica();
			}
			else {
				$("#revenda_cnpj_pesquisa").val(cnpj);
			}
		}
		else {
			$("#revenda_cnpj_pesquisa").val(cnpj);
		}
	}
}

//HD 234135
function fnc_bloqueia_campo(id_campo) {
	$("#"+id_campo).attr("readonly", "readonly").css("color", "#999999");
}

//HD 234135
function fnc_bloqueia_campos_revenda() {
	fnc_bloqueia_campo("revenda_nome");
	fnc_bloqueia_campo("revenda_fone");
	fnc_bloqueia_campo("revenda_cep");
	fnc_bloqueia_campo("revenda_endereco");
	fnc_bloqueia_campo("revenda_numero");
	fnc_bloqueia_campo("revenda_complemento");
	fnc_bloqueia_campo("revenda_bairro");
	fnc_bloqueia_campo("revenda_cidade");
	fnc_bloqueia_campo("revenda_estado");

        $("#revenda_fone").unmask();
	$("#revenda_estado option").hide();
	$("#revenda_estado option:selected").show();
}

//HD 234135
function fnc_desbloqueia_campo(id_campo) {
	$("#"+id_campo).attr("readonly", "").css("color", "#000000");
}

//HD 234135
function fnc_desbloqueia_campos_revenda() {
	fnc_desbloqueia_campo("revenda_nome");
	fnc_desbloqueia_campo("revenda_fone");
	fnc_desbloqueia_campo("revenda_cep");
	fnc_desbloqueia_campo("revenda_endereco");
	fnc_desbloqueia_campo("revenda_numero");
	fnc_desbloqueia_campo("revenda_complemento");
	fnc_desbloqueia_campo("revenda_bairro");
	fnc_desbloqueia_campo("revenda_cidade");
	fnc_desbloqueia_campo("revenda_estado");

        $("#revenda_fone").unmask();
		$("#revenda_fone").maskedinput("(99) 9999-9999");
	$("#revenda_estado option").show();
}

//HD 234135
function fnc_limpa_campo(id_campo) {
	$("#"+id_campo).val("");
}

//HD 234135
function fnc_limpa_campos_revenda() {
	fnc_limpa_campo("revenda_cnpj");
	fnc_limpa_campo("revenda_nome");
	fnc_limpa_campo("revenda_fone");
	fnc_limpa_campo("revenda_cep");
	fnc_limpa_campo("revenda_endereco");
	fnc_limpa_campo("revenda_numero");
	fnc_limpa_campo("revenda_complemento");
	fnc_limpa_campo("revenda_bairro");
	fnc_limpa_campo("revenda_cidade");
	fnc_limpa_campo("revenda_estado");
}

function fnc_num_serie_confirma(valor) {

    if(valor  =='sim'){
        document.getElementById('revenda_nome').readOnly =true;
        document.getElementById('revenda_cnpj').readOnly =true;
        document.getElementById('revenda_fone').readOnly =true;
        document.getElementById('revenda_cidade').readOnly =true;
        document.getElementById('revenda_estado').readOnly =true;
        document.getElementById('revenda_endereco').readOnly =true;
        document.getElementById('revenda_numero').readOnly =true;
        document.getElementById('revenda_complemento').readOnly =true;
        document.getElementById('revenda_bairro').readOnly =true;
        document.getElementById('revenda_cep').readOnly =true;
        document.getElementById('revenda_fixo').style.display='none';
    }else{
        document.getElementById('revenda_nome').readOnly =false;
        document.getElementById('revenda_cnpj').readOnly =false;
        document.getElementById('revenda_fone').readOnly =false;
        document.getElementById('revenda_cidade').readOnly =false;
        document.getElementById('revenda_estado').readOnly =false;
        document.getElementById('revenda_endereco').readOnly =false;
        document.getElementById('revenda_numero').readOnly =false;
        document.getElementById('revenda_complemento').readOnly =false;
        document.getElementById('revenda_bairro').readOnly =false;
        document.getElementById('revenda_cep').readOnly =false;
        document.getElementById('revenda_nome').value='';
        document.getElementById('revenda_cnpj').value='';
        document.getElementById('revenda_fone').value='';
        document.getElementById('revenda_cidade').value='';
        document.getElementById('revenda_estado').value='';
        document.getElementById('revenda_endereco').value='';
        document.getElementById('revenda_numero').value='';
        document.getElementById('revenda_complemento').value='';
        document.getElementById('revenda_bairro').value='';
        document.getElementById('revenda_cep').value='';
        document.getElementById('revenda_fixo').style.display='block';
    }
}

/*if(document.formOne.fieldInfo.checked){
       document.forms['myFormId'].myTextArea.setAttribute('readonly','readonly');
}else if(!document.formOne.fieldInfo.checked){
      document.forms['myFormId'].myTextArea.setAttribute('readonly',true);
      // also tried document.formOne.fieldtextarea.focus();
}*/

<? //HD 731643
if ($login_fabrica==50){
?>

	function pesquisaSerie(campo){
		var campo = campo.value;

		var revenda_fixo_url = "";

		if (jQuery.trim(campo).length > 5){
			Shadowbox.open({
				content:	"pesquisa_numero_serie_nv.php?produto_serie="+campo,
				player:	"iframe",
				title:		"Pesquisa de Número de Série",
				width:	800,
				height:	500
			});
		}else
			alert("Informar mais que 5 digitos para realizar esta pesquisa!");

	}

	function retorna_dados_serie(serie,revenda, nome, cnpj, fone, endereco, numero, complemento, bairro, cep, cidade, estado, data_venda, data_fabricacao, referencia, descricao,voltagem){

			gravaDados('revenda_nome',nome);
			gravaDados('revenda_cnpj',cnpj);
			gravaDados('revenda_fone',fone);
			gravaDados('revenda_cep',cep);
			gravaDados('revenda_endereco',endereco);
			gravaDados('revenda_numero',numero);
			gravaDados('revenda_cidade',cidade);
			gravaDados('revenda_estado',estado);
			gravaDados('revenda_complemento',complemento);
			gravaDados('revenda_bairro',bairro);

			if ($('#revenda_fixo'))
			{
				$('#revenda_fixo').show();
			}

			gravaDados('txt_revenda_nome',nome);
			gravaDados('txt_revenda_cnpj',cnpj);
			gravaDados('txt_revenda_fone',fone);
			gravaDados('txt_revenda_cidade',cidade);
			gravaDados('txt_revenda_estado',estado);
			gravaDados('txt_revenda_endereco',endereco);
			gravaDados('txt_revenda_numero',numero);
			gravaDados('txt_revenda_complemento',complemento);
			gravaDados('txt_revenda_bairro',bairro);
			gravaDados('txt_revenda_cep',cep);

			gravaDados('txt_data_venda',data_venda);
			gravaDados('txt_data_fabricacao',data_fabricacao);

			gravaDados('produto_serie',serie);
			gravaDados('produto_referencia',referencia);
			gravaDados('produto_descricao',descricao);
			gravaDados('produto_voltagem',voltagem);

	}

	function gravaDados(nome, valor){

		try{
			if (nome == 'revenda_estado'){
				$("select[name="+nome+"]").val(valor);
			}else{

				$("input[name="+nome+"]").val(valor);

			}
		} catch(err){
			return false;
		}

	}

<?
}
?>
function fnc_pesquisa_numero_serie (campo, tipo) {

    var url = "";
    var revenda_fixo_url = "";

    if (document.getElementById('revenda_fixo')){
        revenda_fixo_url = "&revenda_fixo=1"
    }

    if (tipo == "produto_serie") {
        url = "pesquisa_numero_serie<?=$ns_suffix?>.php?produto_serie=" + campo.value + "&tipo=produto_serie"+revenda_fixo_url;
    }
    if (tipo == "cnpj") {
        url = "pesquisa_numero_serie<?=$ns_suffix?>.php?cnpj=" + campo.value + "&tipo=cnpj"+revenda_fixo_url;
    }

    janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");

    <? if ($login_fabrica <> 43) {?>
    janela.nome            = document.frm_os.revenda_nome;
    janela.cnpj            = document.frm_os.revenda_cnpj;
    janela.fone            = document.frm_os.revenda_fone;
    janela.cidade        = document.frm_os.revenda_cidade;
    janela.estado        = document.frm_os.revenda_estado;
    janela.endereco        = document.frm_os.revenda_endereco;
    janela.numero        = document.frm_os.revenda_numero;
    janela.complemento    = document.frm_os.revenda_complemento;
    janela.bairro        = document.frm_os.revenda_bairro;
    janela.cep            = document.frm_os.revenda_cep;
    janela.email        = document.frm_os.revenda_email;

    janela.txt_nome            = document.frm_os.txt_revenda_nome;
    janela.txt_cnpj            = document.frm_os.txt_revenda_cnpj;
    janela.txt_fone            = document.frm_os.txt_revenda_fone;
    janela.txt_cidade        = document.frm_os.txt_revenda_cidade;
    janela.txt_estado        = document.frm_os.txt_revenda_estado;
    janela.txt_endereco        = document.frm_os.txt_revenda_endereco;
    janela.txt_numero        = document.frm_os.txt_revenda_numero;
    janela.txt_complemento    = document.frm_os.txt_revenda_complemento;
    janela.txt_bairro        = document.frm_os.txt_revenda_bairro;
    janela.txt_cep            = document.frm_os.txt_revenda_cep;

    janela.txt_data_venda    = document.frm_os.txt_data_venda;
    janela.data_fabricacao    = document.frm_os.data_fabricacao;
    if (document.getElementById('revenda_fixo')){
        janela.revenda_fixo        = document.getElementById('revenda_fixo');
    }
    <? }?>
    //PRODUTO
    janela.produto_referencia = document.frm_os.produto_referencia;
    janela.produto_descricao  = document.frm_os.produto_descricao;
    janela.produto_voltagem      = document.frm_os.produto_voltagem;
    janela.focus();
}



/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
        Formata o Campo de CNPJ a medida que ocorre a digitação
        Parâm.: cnpj (numero)
=================================================================*/
function formata_cnpj(campo) {
	var cnpj = campo.value.length;
	if (cnpj ==  2 || cnpj == 6) campo.value += '.';
	if (cnpj == 10) campo.value += '/';
	if (cnpj == 15) campo.value += '-';
}

/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
        Ajusta a formatação da Máscara de DATAS a medida que ocorre
        a digitação do texto.
=================================================================*/
function ajustar_data(input , evento)
{
    var BACKSPACE =  8;
    var DEL       = 46;
    var FRENTE    = 39;
    var TRAS      = 37;
    var key;
    var tecla;
    var strValidos = "0123456789" ;
    var temp;
    tecla= (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

    if (( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS)) {
        return true;
            }
        if ( tecla == 13) return false;
        if ((tecla<48)||(tecla>57)){
            return false;
            }
        key = String.fromCharCode(tecla);
        input.value = input.value+key;
        temp="";
        for (var i = 0; i<input.value.length;i++ )
            {
                if (temp.length==2) temp=temp+"/";
                if (temp.length==5) temp=temp+"/";
                if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
                    temp=temp+input.value.substr(i,1);
            }
            }
                    input.value = temp.substr(0,10);
                return false;
}

function MostraAtencao(atencao) {
    var abertura = document.frm_os.data_abertura.value;
    var xnota_fiscal = document.frm_os.data_nf.value;

    if (document.getElementById){
        var style2 = document.getElementById(atencao);

            style2.style.display = "block";
            retornaAtencao(abertura,xnota_fiscal);

    }
}

var http3 = new Array();
function retornaAtencao(abertura,xnota_fiscal){
    if (abertura.length==10 && xnota_fiscal.length==10){
        var prod_ref = document.frm_os.produto_referencia.value;
        var curDateTime = new Date();
        http3[curDateTime] = createRequestObject();
        url = "ajax_validade.php?produto="+prod_ref + "&data_abertura=" + abertura + "&data_nf=" + xnota_fiscal;
        http3[curDateTime].open('get',url);
        var atencao = document.getElementById('atencao');
        http3[curDateTime].onreadystatechange = function(){
            if(http3[curDateTime].readyState == 1) {
                atencao.innerHTML = "<font size='1'>Calculando validade..</font>";
            }
            if (http3[curDateTime].readyState == 4){
                if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
                    var results = http3[curDateTime].responseText;
                    atencao.innerHTML   = results;
                }else {
                    atencao.innerHTML = "Erro";
                }
            }
        }
        http3[curDateTime].send(null);
    }
}
/* ============= <PHP> VERIFICA SE HÁ COMUNICADOS =============
        VERIFICA SE TEM COMUNICADOS PARA ESTE PRODUTO E SE TIVER, RETORNA UM
        LINK PARA VISUALIZAR-LO
        Fábio 07/12/2006
=============================================================== */
function trim(str)
{  while(str.charAt(0) == (" ") )
  {  str = str.substring(1);
  }
  while(str.charAt(str.length-1) == " " )
  {  str = str.substring(0,str.length-1);
  }
  return str;
}

function createRequestObject(){
    var request_;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
         request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
         request_ = new XMLHttpRequest();
    }
    return request_;
}

var http5 = new Array();
var http6 = new Array();
var http7 = new Array();
var http8 = new Array();
var http9 = new Array();

function checarFoto(fabrica){
    var ximagem = document.getElementById('img_produto');
    var xref = document.frm_os.produto_referencia.value;

    document.frm_os.link_comunicado.value="";
    ximagem.title = "NÃO HÁ FOTO PARA ESTE PRODUTO";
    xref = trim(xref);

    if (xref.length>0){
        var curDateTime = new Date();
        http9[curDateTime] = createRequestObject();
        url = "ajax_os_cadastro_foto.php?fabrica="+fabrica+"&produto="+escape(xref);
        http9[curDateTime].open('get',url);
        http9[curDateTime].onreadystatechange = function(){
            if (http9[curDateTime].readyState == 4)
            {
                if (http9[curDateTime].status == 200 || http9[curDateTime].status == 304)
                {
                    var response = http9[curDateTime].responseText;
                    if (response=="ok"){
                        document.frm_os.link_foto.value="CLIQUE AQUI PARA VER A FOTO DESTE PRODUTO";
                        ximagem.title = "CLIQUE AQUI PARA VER A FOTO DESTE PRODUTO";
                    }
                    else {
                        document.frm_os.link_foto.value="";
                        ximagem.title = "NÃO HÁ FOTO PARA ESTE PRODUTO";
                    }
                }
            }
        }
        http9[curDateTime].send(null);
    }
}

function checarComunicado(fabrica){
    var imagem = document.getElementById('img_comunicado');
    var ref = document.frm_os.produto_referencia.value;

    //imagem.style.visibility = "hidden";
    document.frm_os.link_comunicado.value="";
    imagem.title = "NÃO HÁ COMUNICADO PARA ESTE PRODUTO";
    ref = trim(ref);

    if (ref.length>0){
        var curDateTime = new Date();
        http7[curDateTime] = createRequestObject();
        url = "ajax_os_cadastro_comunicado.php?fabrica="+fabrica+"&produto="+escape(ref);
        http7[curDateTime].open('get',url);
        http7[curDateTime].onreadystatechange = function(){
            if (http7[curDateTime].readyState == 4)
            {
                if (http7[curDateTime].status == 200 || http7[curDateTime].status == 304)
                {
                    var response = http7[curDateTime].responseText;
                    if (response=="ok"){
                        document.frm_os.link_comunicado.value="HÁ COMUNICADO PARA ESTE PRODUTO. CLIQUE AQUI PARA LER";
                        imagem.title = "HÁ COMUNICADO PARA ESTE PRODUTO. CLIQUE AQUI PARA LER";
                    }
                    else {
                        document.frm_os.link_comunicado.value="";
                        imagem.title = "NÃO HÁ COMUNICADO PARA ESTE PRODUTO";
                    }
                }
            }
        }
        http7[curDateTime].send(null);
    }
}

//HD 20682 20/6/2008

function mostraDomicilio(){
    var ref = document.frm_os.produto_referencia.value;
    if (ref.length>0){
        var curDateTime = new Date();
        http8[curDateTime] = createRequestObject();
        url = "<?=$PHP_SELF?>?verifica_linha=sim&produto_referencia="+escape(ref);
        http8[curDateTime].open('get',url);
        http8[curDateTime].onreadystatechange = function(){
            if (http8[curDateTime].readyState == 4)
            {
                if (http8[curDateTime].status == 200 || http8[curDateTime].status == 304)
                {
                    var response = http8[curDateTime].responseText;
                    if (response=="ok"){
                        document.getElementById('atendimento_dominico_span').style.display = "block";
                    } else {
                        document.getElementById('atendimento_dominico_span').style.display = "none";
                    }
                }
            }
        }
        http8[curDateTime].send(null);
    }
}

function abreComunicado(){
    var ref = document.frm_os.produto_referencia.value;
    var desc = document.frm_os.produto_descricao.value;
    if (document.frm_os.link_comunicado.value!=""){
        url = "pesquisa_comunicado.php?produto=" + ref +"&descricao="+desc;
        window.open(url,"comm","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
    }
}

function abreFoto(){
    var xref  = document.frm_os.produto_referencia.value;
    if (document.frm_os.link_foto.value!=""){
        url = "pesquisa_foto_produto.php?produto=" + xref;
        window.open(url);
    }
}

//ajax defeito_reclamado
function listaDefeitos(valor) {
//verifica se o browser tem suporte a ajax
    try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
    catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
        catch(ex) { try {ajax = new XMLHttpRequest();}
                catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
        }
    }
//se tiver suporte ajax
    if(ajax) {
    //deixa apenas o elemento 1 no option, os outros são excluídos
    document.forms[0].defeito_reclamado.options.length = 1;
    //opcoes é o nome do campo combo
    idOpcao  = document.getElementById("opcoes");
    //     ajax.open("POST", "ajax_produto.php", true);
    ajax.open("GET", "ajax_produto<?=$ap_suffix?>.php?produto_referencia="+valor, true);
    ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    ajax.onreadystatechange = function() {
        if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
        if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaCombo(ajax.responseXML);//após ser processado-chama fun
            } else {idOpcao.innerHTML = "Selecione o defeito";//caso não seja um arquivo XML emite a mensagem abaixo
                    }
        }
    }
    //passa o código do produto escolhido
    var params = "produto_referencia="+valor;
    ajax.send(null);
    }
}

function resetaDefeito() { //HD 381252

	defeito = $('#defeito_reclamado').find('option').filter(':selected').text();

	if(defeito !== "") {

		$('#defeito_reclamado').find('option').remove();
		$("#defeito_reclamado").append("<option value='' id='opcoes'>Selecione o Defeito</option>");

	}

}

function montaCombo(obj){
    var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
    if(dataArray.length > 0) {//total de elementos contidos na tag cidade
    for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
         var item = dataArray[i];
        //contéudo dos campos no arquivo XML
        var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
        var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
        idOpcao.innerHTML = "Selecione o defeito";
        //cria um novo option dinamicamente
        var novo = document.createElement("option");
        novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
        novo.value = codigo;        //atribui um valor
        novo.text  = nome;//atribui um texto
        document.forms[0].defeito_reclamado.options.add(novo);//adiciona o novo elemento
        }
    } else { idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
    }
}


function MostraEsconde(dados){
    if (document.getElementById){
        var style2 = document.getElementById(dados);
        if (!style2) return;
        if (style2.style.display=="block"){
            style2.style.display = "none";
        }else{
            style2.style.display = "block";
            retornaLinha(dados);
        }
    }
}
var http2 = new Array();
function retornaLinha (dados) {
    var com = document.getElementById(dados);
    var ref = document.frm_os.produto_referencia.value;

    if (ref.length>0){
        var curDateTime = new Date();
        http2[curDateTime] = createRequestObject();
        url = "os_cadastro_tudo.php?ajax=sim&produto_referencia=" + escape(ref);
        http2[curDateTime].open('get',url);
        http2[curDateTime].onreadystatechange = function(){
            if (http2[curDateTime].readyState == 4){
                if (http2[curDateTime].status == 200 || http2[curDateTime].status == 304){
                    var results = http2[curDateTime].responseText.split("|");
                    if (results[0] == 'ok') {
                        //document.getElementById("dados_01").innerHTML = results[1];
                        //alert(results[1]);
                        com.innerHTML   = results[1];
                    }
                    else {
                    }
                }
            }
        }
        http2[curDateTime].send(null);
    }
}

function formata_data(campo_data, form, campo){
    var mycnpj = '';
    mycnpj = mycnpj + campo_data;
    myrecord = campo;
    myform = form;

    if (mycnpj.length == 2){
        mycnpj = mycnpj + '/';
        window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
    }
    if (mycnpj.length == 5){
        mycnpj = mycnpj + '/';
        window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
    }

}

    window.onload = function(){
        $("#revenda_cnpj").keypress(function(e) {
            var c = String.fromCharCode(e.which);
            var allowed = '1234567890 ';
            if (e.which != 8 && allowed.indexOf(c) < 0) return false;
        });
    }

<? if($login_fabrica == 3) { /* hd 17735 */ ?>
function char(serie){
    try{var element = serie.which    }catch(er){};
    try{var element = event.keyCode    }catch(er){};
    if (String.fromCharCode(element).search(/[0-9]|[A-Z]/gi) == -1){
        if (element != 0 && element != 8){
            return false
        }
    }
}
window.onload = function(){
    document.getElementById('produto_serie').onkeypress = char;
}
<? } ?>

$().ready(function() {
	<?
	if ($usa_revenda_fabrica) {
		echo "fnc_pesquisa_revenda_status('$revenda_fabrica_status');";
	}
	?>
	displayText('&nbsp;');
    $("input[rel='garantia']").blur(function(){
        var campo = $(this);


            $.post('<? echo $PHP_SELF; ?>',
                {
                    gravarDataconserto : campo.val(),
                    produto: campo.attr("alt")
                },
                function(resposta){
                }
            );

    });
});

function createRequestObject(){
    var request_;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
         request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
         request_ = new XMLHttpRequest();
    }
    return request_;
}

var http_forn = new Array();

<?php if($login_fabrica == 3) { ?>
	// HD 342651
	function verifica_split(id) {

		if (document.getElementById('div_mapa')){
			url = "http://posvenda.telecontrol.com.br<?php echo $PHP_SELF ?>?consulta_split=s&referencia="+id;
			$.get(url, function(data){

				if(data === 't') {
					document.getElementById('div_mapa').style.visibility = "visible";
					document.getElementById('div_mapa').style.position = 'static';
					$('<input type="hidden" value="89" name="tipo_atendimento" />').appendTo('form[name=frm_os]');
				}
				else if(data !== 'f' || data === 'f') {
					document.getElementById('div_mapa').style.visibility = "hidden";
					document.getElementById('div_mapa').style.position = 'absolute';
					$("input[name=tipo_atendimento]").remove();
				}
			});
		}

	}
<?php } ?>

function verifica_atendimento() {

    /*Verificacao para existencia de componente - HD 22891 */<?php
	if ($login_fabrica == 42) {?>

        if ($('#tipo_atendimento').val() == 102) {

			$('#produto_referencia').attr('disabled', '');
			$('#produto_descricao').attr('disabled', '');
			$('#produto_serie').attr('disabled', '');
			$('#defeito_reclamado').attr('disabled', '');

        } else if ($('#tipo_atendimento').val() == 103 || $('#tipo_atendimento').val() == 104) {

			$('#produto_referencia').attr('disabled', 'disabled');
			$('#produto_descricao').attr('disabled', 'disabled');
			$('#produto_serie').attr('disabled', 'disabled');
			$('#defeito_reclamado').attr('disabled', 'disabled');

			$('#produto_referencia').val('');
			$('#produto_descricao').val('');
			$('#produto_serie').val('');

		}<?php

	}?>

    if (document.getElementById('div_mapa')){
        var ref = $('#tipo_atendimento').val();
		$.get('<?=$PHP_SELF?>',
			  {'ajax': 'tipo_atendimento',
			   'id'  : ref},
			  function(responseText) {
                    var response = responseText.split("|");
                    if (response[0]=="ok"){
                        document.getElementById('div_mapa').style.visibility = "visible";
                        document.getElementById('div_mapa').style.position = 'static';
                        document.getElementById('div_mapa_msg').style.visibility = "visible";
                    }else{
                        document.getElementById('div_mapa').style.visibility = "hidden";
                        document.getElementById('div_mapa').style.position = 'absolute';
                        document.getElementById('div_mapa_msg').style.visibility = "hidden";
                    }
			  });
	}
}

function verifica_garantia(data_nf,produto_ref,data_abertura) {
    var ref1 = document.getElementById(data_nf).value;
    var ref2 = document.getElementById(produto_ref).value;
    var ref3 = document.getElementById(data_abertura).value;

        url = "<?=$PHP_SELF?>?ajax=valida_garantia&data_nf="+ref1+"&produto_ref="+ref2+"&data_abertura="+ref3;
        var curDateTime = new Date();
        http_forn[curDateTime] = createRequestObject();
        http_forn[curDateTime].open('GET',url,true);
        http_forn[curDateTime].onreadystatechange = function(){
            if (http_forn[curDateTime].readyState == 4)
            {
                if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
                {
                    var response = http_forn[curDateTime].responseText.split("|");
                    //alert(http_forn[curDateTime].responseText);
                    if (response[0]=="ok"){
                        document.getElementById('div_garantia').style.visibility = "visible";
                        document.getElementById('div_garantia').style.position = 'static';
                    }else{
                        document.getElementById('div_garantia').style.visibility = "hidden";
                        document.getElementById('div_garantia').style.position = 'absolute';
                    }
                }
            }
        }
        http_forn[curDateTime].send(null);
}

    function adicionaIntegridade() {

        if(document.getElementById('defeito_reclamado').value=="0") { alert('Selecione o defeito reclamado'); return false}

        var tbl = document.getElementById('tbl_integridade');
        var lastRow = tbl.rows.length;
        var iteration = lastRow;


        if (iteration>0){
            document.getElementById('tbl_integridade').style.display = "inline";
        }


        var linha = document.createElement('tr');
        linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

        // COLUNA 1 - LINHA
        var celula = criaCelula(document.getElementById('defeito_reclamado').options[document.getElementById('defeito_reclamado').selectedIndex].text);
        celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

        var el = document.createElement('input');
        el.setAttribute('type', 'hidden');
        el.setAttribute('name', 'integridade_defeito_reclamado_' + iteration);
        el.setAttribute('id', 'integridade_defeito_reclamado_' + iteration);
        el.setAttribute('value',document.getElementById('defeito_reclamado').value);
        celula.appendChild(el);


        linha.appendChild(celula);

        // coluna 3 - DEFEITO RECLAMADO
        //var celula = criaCelula(document.getElementById('defeito_reclamado').options[document.getElementById('defeito_reclamado').selectedIndex].text);
        //celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
        //linha.appendChild(celula);


        // coluna 6 - botacao
        var celula = document.createElement('td');
        celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';

        var el = document.createElement('input');
        el.setAttribute('type', 'button');
        el.setAttribute('value','Excluir');
        el.onclick=function(){removerIntegridade(this);};
        celula.appendChild(el);
        linha.appendChild(celula);

        // finaliza linha da tabela
        var tbody = document.createElement('TBODY');
        tbody.appendChild(linha);
        /*linha.style.cssText = 'color: #404e2a;';*/
        tbl.appendChild(tbody);

        //document.getElementById('solucao').selectedIndex=0;
    }

    function removerIntegridade(iidd){
        var tbl = document.getElementById('tbl_integridade');
        tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);

    }

    function criaCelula(texto) {
        var celula = document.createElement('td');
        var textoNode = document.createTextNode(texto);
        celula.appendChild(textoNode);
        return celula;
    }

    function checarNumero(campo){
        var num = campo.value;
        campo.value = parseInt(num);
        if (campo.value=='NaN') {
            campo.value='';
            return false;
        }
    }

<? if($login_fabrica == 30  or $login_fabrica == 51 ) {?>
    function char(nota_fiscal){
        try{var element = nota_fiscal.which    }catch(er){};
        try{var element = event.keyCode    }catch(er){};
        if (String.fromCharCode(element).search(/[0-9]/gi) == -1)
        return false
    }
    window.onload = function(){
        document.getElementById('nota_fiscal').onkeypress = char;
    }
<? }?>
<? if($login_fabrica == 7) {?>
    function char(nota_fiscal){
        try{var element = nota_fiscal.which    }catch(er){};
        try{var element = event.keyCode    }catch(er){};
        if (String.fromCharCode(element).search(/[0-9]|[,]|[.]/gi) == -1)
        return false
    }
    window.onload = function(){
            document.getElementById('produto_capacidade').onkeypress = char;
            document.getElementById('divisao').onkeypress = char;
            document.getElementById('deslocamento_km').onkeypress = char;
        }
<? }?>

function verificaProdutoTroca(produto){
    var referencia = produto.value;
    var data = new Date();
    if (referencia.length > 0){
        $.ajax({
            type: "GET",
            url: "<?=$PHP_SELF?>",
            data: 'produto_referencia='+referencia+'&produto_troca=sim&data='+data.getTime(),
            complete: function(http) {
                results = http.responseText;
                if (results =='sim'){
                    document.frm_os.data_abertura.focus();
                    alert('OS irá para intervenção da Fábrica para providenciar a troca do produto e a mão-de-obra será de R$ 2,00. Caso consiga consertar o produto sem necessidade de peças, feche a OS para receber a mão-de-obra integral.') ;
                }
            }
        });
    }
}
function tipoatendimento() {
// HD 54668 para Colormaq
    var referencia = document.frm_os.produto_referencia.value;

	// Se já foi preenchido para o mesmo produto, não faz nada
	if ($('#tipo_atendimento>option').length  > 1 && $('#tipo_atendimento').attr('info_ref')==referencia) {
			return true;
	} else {
		$.ajax({
			type: "GET",
			url: "tipo_atendimento_ajax.php",
			data: "q="+referencia ,
			cache: false,
			success: function(txt) {
				$('#tipo_atendimento').html(txt).attr('info_ref', referencia);
			},
			error: function(txt) {
				alert(txt);
			}
		});
	}
}
<? if ( $login_fabrica==11 or $login_fabrica==50 or $login_fabrica ==45 or $login_fabrica ==80){?>
        $('#data_abertura').readonly(true);
<?}?>

function dataAbertura(){
    $('#data_abertura').focus(function(){
        alert('Não é possível alterar a data da abertura');
        $('#data_abertura').readonly(true);
    }).click(function(){
        $('#data_abertura').readonly(true);
    });
}

//HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
function mostraDefeitoDescricao(fabrica) {
    var referencia = document.frm_os.produto_referencia.value;
    var td = document.getElementById('td_defeito_reclamado_descricao');
    if (typeof td != "undefined") {
	    td.style.display = 'none';
    }

    url = "os_cadastro_tudo_ajax.php?acao=sql&sql=SELECT tbl_linha.linha FROM tbl_produto JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha WHERE tbl_linha.fabrica="+fabrica+" AND tbl_produto.referencia='" + referencia + "'";

    requisicaoHTTP("GET", url, true, "trataDefeitoDescricao", fabrica);
}

//HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
function trataDefeitoDescricao(retorno, fabrica) {
    var td = document.getElementById('td_defeito_reclamado_descricao'); /* MLG 06/12/2010 - Declarar sempre, senão, dá erro!*/
    if (retorno == "528") {
		if (typeof td != "undefined") {
	        td.style.display = 'block';
		}
    }
    else {
		if (typeof td != "undefined") {
	        td.style.display = 'none';
		}
    }
}

var objER = /^[0-9]{2}\.[0-9]{3}-[0-9]{3}$/;

strCEP = Trim(strCEP);
if(strCEP.length > 0){

}


</script>


<script language="JavaScript">

function fnc_pesquisa_serie(campo) {//HD 256659

	var valida = /^\d{10}[A-Z]\d{3}[A-Z]$/;

	//if (campo.value.length == 15) {
	if (campo.value.match(valida)) {

		var url = "produto_serie_pesquisa_britania.php?serie=" + campo.value;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");

		janela.serie      = campo;
		janela.referencia = document.frm_os.produto_referencia;
		janela.descricao  = document.frm_os.produto_descricao;
		janela.voltagem  = document.frm_os.produto_voltagem;
		janela.focus();

	} else {

		alert("A pesquisa válida somente para o serial com 15 caracteres no formato NNNNNNNNNNLNNNL");

	}

}

</script>
<?
	if(in_array($login_fabrica,$fabricas_validam_campos_telecontrol) || $login_fabrica > 99){
		include "javascript_valida_campos_obrigatorios.php";
	}
?>
<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
        Verifica a existência de uma OS com o mesmo número e em
        caso positivo passa a mensagem para o usuário.
=============================================================== -->
<?
//if ($ip == '201.0.9.216') echo $msg_erro;

if (strlen ($msg_erro) > 0) {
    if (strpos ($msg_erro,"tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";
?>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
    <td valign="middle" align="center" class='error' id='erro_msg_'>
<?
    if ($login_fabrica == 1 AND ( strpos($msg_erro,"É necessário informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto não é válido") !== false ) ) {
        $produto_referencia = trim($_POST["produto_referencia"]);
        $produto_voltagem   = trim($_POST["produto_voltagem"]);
        $sqlT =    "SELECT tbl_lista_basica.type
                FROM tbl_produto
                JOIN tbl_lista_basica USING (produto)
                WHERE UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia')
                AND   tbl_produto.voltagem = '$produto_voltagem'
                AND   tbl_lista_basica.fabrica = $login_fabrica
                AND   tbl_produto.ativo IS TRUE
                GROUP BY tbl_lista_basica.type
                ORDER BY tbl_lista_basica.type;";
        $resT = @pg_query ($con,$sqlT);
        if (pg_num_rows($resT) > 0) {
            $s = pg_num_rows($resT) - 1;
            for ($t = 0 ; $t < pg_num_rows($resT) ; $t++) {
                $typeT = pg_fetch_result($resT,$t,type);
                $result_type = $result_type.$typeT;

                if ($t == $s) $result_type = $result_type.".";
                else          $result_type = $result_type.",";
            }
            $msg_erro .= "<br />Selecione o Type: $result_type";
        }
    }

    // retira palavra ERROR:
    if (strpos($msg_erro,"ERROR: ") !== false) {
		if( !in_array( $login_fabrica, array(98,108,111) ) )
	        $erro = "Foi detectado o seguinte erro:<br />";
        $msg_erro = substr($msg_erro, 6);
    }

    // retira CONTEXT:
    if (strpos($msg_erro,"CONTEXT:")) {
        $x = explode('CONTEXT:',$msg_erro);
        $msg_erro = $x[0];
    }
    echo "<!-- ERRO INICIO //-->";
    //echo $erro . $msg_erro . "<br /><!-- " . $sql . "<br />" . $sql_OS . " -->";
    if($login_fabrica == 88 or $login_fabrica == 95 OR $login_fabrica > 96){
		$msg_erro = str_replace("Erro:", "",$msg_erro);
		$erro = str_replace("Erro: ", "",$erro);
		echo $erro . $msg_erro;
	}else{
		if (trim($msg_erro) == 'Foi detectado erro na abertura de sua OS, favor verificar dados do produto, (codigo de referência, modelo e número de serie)') {
			echo $msg_erro;
		} else {
			echo $erro . $msg_erro;
		}
	}
    echo "<!-- ERRO FINAL //-->";
?>
    </td>
</tr>
</table>

<? }else{?>
<table border="0" cellpadding="1" cellspacing="1" align="center"  width = '700' style="display:none" id="tbl_erro_msg">
<tr>
	<td valign="middle" align="center" class='error' id='erro_msg_'>
	</td>
</tr>
</table>

<?} ?>
<?
$sql = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
$res = @pg_query ($con,$sql);
$hoje = @pg_fetch_result ($res,0,0);

//Chamado 1982
if ($login_fabrica == 15) { ?>
<div id="layout">
    <div class="content">
     Duvidas e sugestões, envie um e-mail para telecontrol@latinatec.com.br
    </div>
</div>

<? } ?>

<?
if($login_fabrica==30){ // HD 56479

    echo "<table width='600' border='0' cellpadding='3' cellspacing='5' align='center' bgcolor='#ffffff'>";
    echo "<tr>";
    echo "<td valign='middle' align='center' bgcolor='#6699FF' style='font-color:#ffffff ; font-size:12px'>";
    echo "<B>Atenção:</B> Liberação de KM em garantia apenas até os primeiros três meses de compra, em caso de dúvidas entrar em contato com a fábrica.";
    echo "<br />";
    echo "OS CAMPOS EM VERMELHO SÃO DE PREENCHIMENTO OBRIGATÓRIO";
    echo "</td>";
    echo "</tr>";
    echo "</table>";

}
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <td><img height="1" width="20" src="imagens/spacer.gif"></td>

    <td valign="top" align="left">

        <? if ($login_fabrica == 1 and 1 == 2) { ?>
            <table width='700' border='0' cellspacing='2' cellpadding='5' align='center'>
            <tr>
            <td align='center' bgcolor='#6699FF' style='font-color:#ffffff ; font-size:12px'>
            <B>Conforme comunicado de 04/01/2006, as OSs abertas até o dia 31/12/2005 poderão ser digitadas até o dia 31/01/2006.<br />Pedimos atenção especial com relação a esse prazo, pois depois do dia 01/02/2006 somente aceitaremos a abertura das OS's com data posterior a 02/01/2006.</B>
            </td>
            </tr>
            </table>

<?
    if ($login_tipo_posto == 90 OR $login_tipo_posto == 36 OR $login_tipo_posto == 82 OR $login_tipo_posto == 83 OR $login_tipo_posto == 84 and 1 == 2) {
?>
            <form name="frm_locacao" method="post" action="<? echo $PHP_SELF ?>">
            <input type="hidden" name="btn_acao">
            <fieldset style="padding: 10;">
                <legend align="center"><font color="#000000" size="2">Locação</font></legend>
                <br />
                <center>
                    <font color="#000000" size="2">Nº de Série</font>
                    <input class="frm" type="text" name="serie_locacao" size="15" maxlength="20" value="<? echo $serie_locacao; ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número de série Locação e clique no botão para efetuar a pesquisa.');">
                    <img border="0" src="imagens/btn_continuar.gif" align="absmiddle" onclick="javascript: if (document.frm_locacao.btn_acao.value == '') { document.frm_locacao.btn_acao.value='locacao'; document.frm_locacao.submit(); } else { alert('Não clique no botão voltar do navegador, utilize somente os botões da tela'); }" style="cursor: hand" alt="Clique aqui p/ localizar o número de série">
                </center>
            </fieldset>
            </form>
<?
            }
            if ($tipo_os == "7" && strlen($os) > 0) {
                $sql =    "SELECT TO_CHAR(data_fabricacao,'DD/MM/YYYY') AS data_fabricacao ,
                                pedido                                                   ,
                                execucao
                        FROM tbl_locacao
                        WHERE serie       = '$produto_serie'
                        AND   nota_fiscal = '$nota_fiscal';";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res) == 1) {
                    $data_fabricacao    = trim(pg_fetch_result($res,0,data_fabricacao));
                    $pedido             = trim(pg_fetch_result($res,0,pedido));
                    $execucao           = trim(pg_fetch_result($res,0,execucao));
?>
                <table width="100%" border="0" cellspacing="5" cellpadding="0">
                    <tr valign="top">
                        <td nowrap>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Execução</font>
                            <br />
                            <input type="text" name="execucao" size="12" value="<? echo $execucao; ?>" class="frm" readonly>
                        </td>
                        <td nowrap>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Fabricação</font>
                            <br />
                            <input type="text" name="data_fabricacao" size="15" value="<? echo $data_fabricacao; ?>" class="frm" readonly>
                        </td>
                        <td nowrap>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Pedido</font>
                            <br />
                            <input type="text" name="pedido" size="12" value="<? echo $pedido; ?>" class="frm" readonly>
                        </td>
                    </tr>
                </table>
                <?
                }
            }
        }

		if ($login_fabrica == 24 and $login_posto == 6359) {

		echo "<br /><br /><table width='600' border='0' cellpadding='3' cellspacing='5' align='center' bgcolor='#ecc3c3'>";
			echo "<tr>";
			echo "<td valign='middle' align='center'>";
			echo "<font face='Arial, Helvetica, sans-serif' color='#d03838' size='1'><B>Atenção:</B> Este programa é específico para lançamento de ORDEM DE SERVIÇO DE CONSUMIDOR,<br /> caso a ordem de serviço seja de REVENDA <a href='os_revenda.php'>clique aqui</a>. </font>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";

		}?>

        <!-- ------------- Formulário ----------------- -->

        <form style="margin: 0px;" name="frm_os" id="frm_os" method='post' enctype="multipart/form-data" action="<? echo $PHP_SELF ?>">
		<input class="frm" type="hidden" name="os" value="<? echo $os; ?>">
        <input class="frm" type="hidden" name="pre_os" value="<? echo $pre_os; ?>">
        <?if ($login_fabrica == 52 or $login_fabrica == 96 or $login_fabrica == 30) { ?>
			<input class="frm" type="hidden" id='cliente_admin' name="cliente_admin" value="<? echo $cliente_admin; ?>">
			<input class="frm" type="hidden" id='admin' name="admin" value="<? echo $admin; ?>">
		<?if($login_fabrica != 96){?>
			<input class="frm" type="hidden" id='qtde_km' name="qtde_km" value="<? echo $qtde_km; ?>">
        <? }}?>
        <input class="frm" type="hidden" name="hd_chamado" value="<? echo $hd_chamado; ?>">
        <input class="frm" type="hidden" name="hd_chamado_item" value="<? echo $hd_chamado_item; ?>"><?php

        if ($login_fabrica == 1 && $tipo_os == "7") {
            echo "<input type='hidden' name='locacao' value='$tipo_os'>";
        }

        if ($login_fabrica == 3) {
            echo "<table width='600' border='0' cellspacing='5' cellpadding='0' align='center'>";
            echo "<tr>";
            echo "<td align='center' bgcolor='#66FF99' style='font-color:#ffffff ; font-size:12px'>";
            echo "Não é permitido abrir Ordens de Serviço com data de abertura superior a 20 dias.";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
        }

        if ($login_fabrica == 79) {
            echo "<table width='600' border='0' cellspacing='5' cellpadding='0' align='center'>";
            echo "<tr>";
            echo "<td align='center' bgcolor='#66FF99' style='font-color:#ffffff ; font-size:12px'>";
            echo "*campos obrigatórios - as informações que o consumidor não fornecer deverão ser preenchidas com as informações do Posto Autorizado: e-mail, CNPJ, telefone. ";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
        }

		if ($distribuidor_digita == 't') {?>

            <table width="100%" border="0" cellspacing="5" cellpadding="0">
				<tr valign='top' style='font-size:12px'>
					<td valign='top'>
						Distribuidor pode digitar OS para seus postos.
						<br />
						Digite o código do posto
						<input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
						ou deixe em branco para suas próprias OS.
					</td>
				</tr>
            </table><?php

		} ?>

        <br /><?php

		if ($login_fabrica == 74) { //hd 377814

			echo '<div style="display:none; width:700px;margin:auto;text-align:center;font-weight:bold;" id="data_fabricacao_opener">
						Data de Fabricação do Produto
						<p>&nbsp;</p>
				  </div>';

		} ?>

        <table width="100%" border="0" cellspacing="5" cellpadding="2">

        <? if ($login_fabrica == 7) { // HD 75762 para Filizola ?>
            <tr>
                <td nowrap  valign='top'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Classificação da OS</font></td>
            </tr>
            <tr>
                <td>
                    <select name='classificacao_os' id='classificacao_os' size="1" class="frm">
                        <option <? if (strlen($classificacao_os)==0) {echo "selected";} ?>></option><?php

						$sql = "SELECT    *
								FROM    tbl_classificacao_os
								WHERE    fabrica = $login_fabrica
								AND        ativo IS TRUE
								ORDER BY descricao";

						$res = @pg_query ($con,$sql);

						if (pg_num_rows($res) > 0) {
							for ($i = 0; $i < pg_num_rows($res); $i++) {

								$xclassificacao_os=pg_fetch_result($res, $i, 'classificacao_os');

								if ($xclassificacao_os == 5 and $classificacao_os != 5) {
									continue;
								}

								echo "<option value='$xclassificacao_os'";
								if ($classificacao_os == $xclassificacao_os) echo " selected";
								echo ">".pg_fetch_result($res,$i,descricao)."</option>\n";

							}

						}?>
                    </select>
                </td>
            </tr>
        <? }

		if ($login_fabrica == 42) {?>

			<tr>
				<td align='left' id="mostra_tipo_atendimento" colspan="100%">
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=($login_fabrica == 7) ? "Natureza" : "Tipo Atendimento"?></font><br />
					<select name="tipo_atendimento" id='tipo_atendimento' class='frm' style='width:220px;'<?php
						if ($login_fabrica == 50) { // HD 54668 para Colormaq ?>
							onFocus="tipoatendimento();"<?php
						}?>
						onChange="verifica_atendimento();">
						<option></option><?php

						if ($login_fabrica == 1) $sql_add1 = " AND tipo_atendimento NOT IN (17,18,35,64) ";

						/*HD:22505- COLORMAQ - Tipo atendimento de deslocamento só aparece se o posto tem km cadastrado(maior que 0)*/
						$sql_deslocamento = " ";

						if ($login_fabrica == 50) {

							$sql_deslocamento = " AND tipo_atendimento NOT IN (
														SELECT
															CASE WHEN valor_km > 0
																Then 0
																Else 55
														END as tipo_atendimento
														FROM tbl_posto_fabrica
														WHERE fabrica = $login_fabrica
															AND posto = $posto
													) ";

						}

						$sql = "SELECT *
								FROM tbl_tipo_atendimento
								WHERE fabrica = $login_fabrica
								AND   ativo IS TRUE
								$sql_add1
								$sql_deslocamento
								ORDER BY tipo_atendimento ";

						if ($login_fabrica == 19) {//HD 15937
							$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND codigo IN (0,2,3,5,14) ORDER BY codigo";
						}

						if ($login_fabrica == 15) {

							if ($login_posto == 2405) {
								$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY descricao";
							} else {

								$sql = "SELECT *
										FROM tbl_tipo_atendimento
										WHERE fabrica = $login_fabrica
										AND   ativo   IS TRUE $sql_add1
										ORDER BY tipo_atendimento";

							}

						}

						$res = pg_query ($con, $sql);

						for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {

							$codigo  = str_pad(pg_fetch_result($res, $i, 'codigo'), 2, '0', STR_PAD_LEFT);
							$desc    = pg_fetch_result($res, $i, 'descricao');
							$tipo_at = pg_fetch_result($res, $i, 'tipo_atendimento');

							$txt_option = ($login_fabrica != 90) ? "$codigo - $desc" : $desc;
							$opt_sel    = ($tipo_atendimento == $tipo_at) ? ' SELECTED':'';

							echo "<option value='$tipo_at'$opt_sel>$txt_option</option>";

						}

						if ($login_fabrica == 19 OR $login_posto == 6359) {

							$sql = " SELECT atende_comgas FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
							$res = pg_query($con,$sql);

							$atende_comgas = pg_fetch_result($res,0,0);
							if (strlen($atende_comgas) > 0 and $atende_comgas == 't') {
								echo "<option ";
							if ($tipo_atendimento == 20 ) echo " selected ";
								echo " value='20'>08 - Atend.Comgás</option>\n";
							}

						}?>
					</select>
				</td>
			</tr><?php

		}?>

        <tr>
        <? if ($pedir_sua_os == 't' && ($login_fabrica != 86 && $login_fabrica != 101 && $login_fabrica < 104 )) { ?>
            <td nowrap  valign='top'>

                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='sua_os'>OS Fabricante</span></font>
                <br />
                <input  name="sua_os" id='sua_os' class ="frm" type ="text" size ="10" <?if ($login_fabrica==5){?>maxlength="6" ReadOnly onclick="alert('Mantenha esse campo em branco para geração automática de número de ordem de serviço.\nCaso tenha alguma dúvida, entrar em contato com a Mondial através do 0800-7707810 ou ata@mondialline.com.br');" <?}else{?>maxlength="20"<?}?> value ="<? echo $sua_os ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');"><?php
                } else {
                    echo "&nbsp;";
                    echo "<input type='hidden' name='sua_os'>"; ?>
            </td>
            <?}

            if ( strlen( trim($data_abertura)) == 0 AND ($login_fabrica == 7 OR $login_fabrica==14 or $login_fabrica == 19 or $login_fabrica == 30 or $login_fabrica == 59 or $login_fabrica == 85 or $login_fabrica == 72 or $login_fabrica == 80)) {
                $data_abertura = $hoje;
            }

            if (in_array($login_fabrica,array(6,50,56,43,95))) {?>

				<td nowrap valign='top'>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="produto_serie">N. Série</span></font>
					<br />
					<input class="frm" type="text" name="produto_serie"  id="produto_serie" size="12" maxlength="20"
						   value="<? echo $produto_serie ?>"
						 onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.');"
						  onblur="this.className='frm'; displayText('&nbsp;');<?
							if ($login_fabrica == 50) echo 'pesquisaSerie(document.frm_os.produto_serie);';
							?>">
					<? if($login_fabrica == 74 or $login_fabrica == 95) { ?><img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_serie_atlas (document.frm_os.produto_serie, document.frm_os.produto_referencia,document.frm_os.produto_descricao)"><? } ?>   &nbsp;
					<? if($login_fabrica==6 or $login_fabrica==50 or $login_fabrica == 43) { ?>
						<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle'   style='cursor: pointer' <?
						if($login_fabrica==6) { ?>
						onclick="javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_serie,'frm_os');"></A>
						<?} else if ($login_fabrica == 50){?>
							onclick="javascript: pesquisaSerie (document.frm_os.produto_serie);"></a>

						<?}else{?>

							onclick="javascript: fnc_pesquisa_numero_serie (document.frm_os.produto_serie, 'produto_serie');"></a>
						<?}
					}

					if($login_fabrica == 56) { ?>
					<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_serie56 (document.frm_os.produto_serie,'frm_os')"  style='cursor: pointer'></A>
					<?}?>
				</td><?php

			}

            if ($login_fabrica == 19 OR ($login_fabrica == 1 AND $login_posto == 6359)) {?>
				<td nowrap align='center'  valign='top'>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde.Produtos</font>
					<br />
					<input class="frm" type="text" name="qtde_produtos" size="2" maxlength="3" value="<? echo $qtde_produtos ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Quantidade de produtos atendidos nesta O.S.'); ">
				</td><?php
			}

            if (($login_fabrica == 3 and $linhainf == 't') or $login_fabrica == 59  or $login_fabrica ==2) { ?>
            <td nowrap  valign='top'>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
                <br />
                <input class="frm" type="text" name="produto_serie" id="produto_serie"  size="8" maxlength="<?=($login_fabrica==35) ? '12' : '20' ?>" value="<? echo $produto_serie ?>" <?
                if ($login_fabrica == 50 or $login_fabrica == 43) {
                ?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');<?
                if ($login_fabrica == 3 and $login_posto == 6359) {
                    echo " MostraEsconde('dados_1');";
                }
                echo " verificaPreOS();";
                if ($login_fabrica==7 /*and 1==2*/) {
                    echo "verificaProduto(document.frm_os.produto_referencia,this)";
                } ?>" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.'); <?
                if ($login_fabrica == 3 and $login_posto == 6359) {
                    echo " MostraEsconde('dados_1');";
                } ?> "><? if($login_fabrica == 25) { ?>
                &nbsp;<INPUT TYPE="button" onClick='javascritp:fn_verifica_garantia();' name='Verificar' value='Verificar' <? if($login_fabrica ==3) echo "rel=serie";?>>
                <? } ?>
                <br /><font face='arial' size='1'><? if ($login_fabrica == 1) echo "(somente p/ linha DeWalt)"; ?></font>
                <div id='dados_1' style='position:absolute; display:none; border: 1px solid #949494;background-color: #f4f4f4;'>
                </div>
                <? if ($login_fabrica == 35){
                    echo "<div width='100' style='font-size: 9px;'>Encontra-se na etiqueta de voltagem do aparelho.<br />
                    Caso o produto não possua número de série,<br /> entre em contato com o fabricante.</<div>";
                }
            }

            if ($login_fabrica <> 15) {?>
                <td nowrap  valign='top'><?php
                    if ($login_fabrica == 3) {
                        echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Código do Produto</font>";
                    } else {

                        if ($login_fabrica == 30) {
							echo "<acronym title='Campo Obrigatório'>";
								echo "<font color='#AA0000'size='1 face='Geneva, Arial, Helvetica, san-serif'>Referência do Produto</font>";
							echo "</acronym>";
                        } else {
                            echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
									<span rel='produto_referencia'>Referência do Produto</span>
								</font>";
                        }

                    }

                    // verifica se tem comunicado para este produto (só entra aqui se for abrir a OS) - FN 07/12/2006
                    $arquivo_comunicado  = "";
                    $arquivo_comunicadoi = "";

                    if (strlen ($produto_referencia) > 0) {

                        $sql ="SELECT tbl_comunicado.comunicado, tbl_comunicado.extensao
                            FROM  tbl_comunicado JOIN tbl_produto USING(produto)
                            WHERE tbl_produto.referencia = '$produto_referencia'
                            AND tbl_comunicado.fabrica = $login_fabrica
                            AND tbl_comunicado.ativo IS TRUE";

                        $res = pg_query($con,$sql);

                        if (pg_num_rows($res) > 0) {
                            $arquivo_comunicado= "HÁ ".pg_num_rows($res)." COMUNICADO(S) PARA ESTE PRODUTO";
                        }

                    }?>

                    <br /><?php

					if ($login_fabrica == 1 AND strlen($os) > 0) {?>
	                    <input class="frm" type="text" name="produto_referencia"  id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" readonly><?php
					} else { ?>
						<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>"
                    <? if ($login_fabrica == 50) {?>
                    onChange="javascript: this.value=this.value.toUpperCase(); resetaDefeito();"
                    <?} else {

						#HD 424887 - INICIO

						/* ESTA VERIFICAÇÃO ESTÁ SENDO FEITA PORQUE PARA AS FABRICAS QUE ESTÃO NESTE ARRAY
						NÃO HAVERÁ INTEGRIDADE COM O DEFEITO_RECLAMADO - by: gabriel silveira */

						if (!in_array($login_fabrica,$fabricas_defeito_reclamado_sem_integridade)){
							if($login_fabrica <> 3){
								echo   " onChange=\"resetaDefeito();\" ";
							}
						}

						#HD 424887 - FIM

					} ?>
                    onblur="this.className='frm'; displayText('&nbsp;');
                    <?php if ($login_fabrica == 5){ ?>
                        checarFoto(<? echo $login_fabrica ?>) ;
                    <? } ?>
                    <?php if ($login_fabrica <> 11){ // HD 68996?>
                        checarComunicado(<? echo $login_fabrica ?>);
                    <? } ?>
					<? if($login_fabrica == 3) { ?>
					verifica_split(document.frm_os.produto_referencia.value);
					<? } ?>
                    <? if($login_fabrica==24) {?>
                    fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem);
                    <? }
                    if($login_fabrica==7) {?>
                    busca_valores(this);
                    <? echo "verificaProduto(document.frm_os.produto_referencia,this)";
                    }
                    /*if($login_fabrica==51) { // HD 59408 83858-Retirar
                        echo "verificaProdutoTroca(document.frm_os.produto_referencia);";
                    }?*/
                    ?>
                    " onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');" <? if (strlen($locacao) > 0) echo "readonly"; ?> >&nbsp;
                    <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem) " style='cursor: hand'>
                    <? } ?>
                    <?php if ($login_fabrica <> 11){ // HD 68996?>
                    <img src='imagens/botoes/vista.jpg' height='22px' id="img_comunicado" target="_blank" name='img_comunicado' border='0'
                        align='absmiddle'  title="NÃO HÁ COMUNICADOS PARA ESTE PRODUTO"
                        onclick="javascript:abreComunicado()"
                        style='cursor: pointer;'>
                    <input type="hidden" name="link_comunicado" value="<? echo $arquivo_comunicado; ?>">
                    <?php } ?>
                    <?php if ($login_fabrica == 5){
                            # HD 50627 ?>
                    <img src='imagens/picture_mach.gif' id="img_produto" target="_blank" name='img_produto' border='0'
                        align='absmiddle' title="NÃO HÁ FOTO PARA ESTE PRODUTO"
                        onclick="javascript:abreFoto()"
                        style='cursor: pointer;'>
                    <input type="hidden" name="link_foto" value="<? echo $link_foto; ?>">
                    <?php } ?>
                </td>
                <?if ($login_fabrica == 96){ //HD 746279
                ?>

		            <td>

						<font size="1" face='Geneva, Arial, Helvetica, san-serif'>Modelo do Produto</font>
						<br />
						<input type="text" name="referencia_fabrica" id="referencia_fabrica" value="<?=$referencia_fabrica?>"/>
						<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_modelo (document.frm_os.referencia_fabrica,'frm_os')" style='cursor: pointer'>

		            </td>

                <?}?>

                <td nowrap valign='top'><?php
                    if ($login_fabrica == 3) {
                        echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Modelo do Produto</font>";
                    } else {

                        if ($login_fabrica == 30) {
                            echo "<acronym title='Campo Obrigatório'>";
                                echo "<font color='#AA0000'size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do Produto</font>";
                            echo "</acronym>";
                        }else{
                            echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
								<span rel='produto_descricao'>Descrição do Produto</span></font>";
                        }

                    }?>
                    <br />
                    <? if ($login_fabrica == 1 AND strlen($os) > 0) {?>
                    <input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" readonly>
                    <? }else{ ?>
                    <input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="40" value="<? echo $produto_descricao ?>"
                     <? if($login_fabrica==50){?>
                    onChange="javascript: this.value=this.value.toUpperCase();resetaDefeito();"
                    <? } else {

						#HD 424887 - INICIO

						/* ESTA VERIFICAÇÃO ESTÁ SENDO FEITA PORQUE PARA AS FABRICAS QUE ESTÃO NESTE ARRAY
						A CHAMADA DESTA FUNÇÃO "listaDefeitos" SERÁ FEITA NO ONBLUR DO PRODUTO, POIS NÃO
						HAVERÁ INTEGRIDADE COM O DEFEITO_RECLAMADO - by: gabriel silveira */

						if (!in_array($login_fabrica,$fabricas_defeito_reclamado_sem_integridade)){
							if($login_fabrica <> 3){
								echo " onChange=\"resetaDefeito();\" ";
							}
						}

						#HD 424887 - FIM

					} ?>
                    onblur="this.className='frm'; displayText('&nbsp;');

					<? if($login_fabrica == 3) { ?>
					verifica_split(document.frm_os.produto_referencia.value);
					<? } ?>
                    <? if($login_fabrica==7) { ?>
                    busca_valores();
                    <? echo "verificaProduto(document.frm_os.produto_referencia,this)";
                    }
					if ($login_fabrica == 40)
					{
						echo "verifica_familia_atendimento(document.frm_os.produto_referencia.value)";
					}?>"
                    onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');
                    <?php if ($login_fabrica == 5){ ?>
                        checarFoto(<? echo $login_fabrica ?>) ;
                    <? } ?>
                    <?php if ($login_fabrica <> 11){ // HD 68996?>
                    checarComunicado(<? echo $login_fabrica ?>);
                    <? } ?>
                    "
                    <? if (strlen($locacao) > 0) echo "readonly"; ?>>&nbsp;
                    <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao',document.frm_os.produto_voltagem)" style='cursor: pointer'></A>
                    <? } ?>
                </td>
            <?}else{ ?>
                <td nowrap  valign='top'>
                    <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do Produto</font><br />
                    <input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" onblur="this.className='frm'; displayText('&nbsp;');<?echo (in_array($login_fabrica, array(15))) ? "verifica_familia_atendimento(document.frm_os.produto_referencia.value);" : "";?>" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');checarComunicado(<? echo $login_fabrica ?>);" <? if (strlen($locacao) > 0) echo "readonly"; ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao',document.frm_os.produto_voltagem)"  style='cursor: pointer'></A>
                </td>
                <td nowrap  valign='top'>

                    <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Referência do Produto</font>

                    <?// verifica se tem comunicado para este produto (só entra aqui se for abrir a OS) - FN 07/12/2006
                    $arquivo_comunicado="";
                    $arquivo_comunicado="";
                    if (strlen ($produto_referencia) >0) {
                        $sql ="SELECT tbl_comunicado.comunicado, tbl_comunicado.extensao
                            FROM  tbl_comunicado JOIN tbl_produto USING(produto)
                            WHERE tbl_produto.referencia = '$produto_referencia'
                            AND tbl_comunicado.fabrica = $login_fabrica
                            AND tbl_comunicado.ativo IS TRUE";
                        $res = pg_query($con,$sql);
                        if (pg_num_rows($res) > 0)
                            $arquivo_comunicado= "HÁ ".pg_num_rows($res)." COMUNICADO(S) PARA ESTE PRODUTO";
                    } ?>
                    <br />
                    <input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" onblur="this.className='frm'; displayText('&nbsp;');checarComunicado(<? echo $login_fabrica ?>);<?if($login_fabrica==24) {?> fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem);<? } ?> <?if($login_fabrica==7) {?> busca_valores(); <? } ?> " onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem) " style='cursor: hand'>
                    <img src='imagens/botoes/vista.jpg' height='22px' id="img_comunicado" target="_blank" name='img_comunicado' border='0'
                        align='absmiddle'  title="NÃO HÁ COMUNICADOS PARA ESTE PRODUTO"
                        onclick="javascript:abreComunicado()"
                        style='cursor: pointer;'>
                    <input type="hidden" name="link_comunicado" value="<? echo $arquivo_comunicado; ?>">
                </td>
            <? } ?>
            <? if ($login_fabrica == 7 || $login_fabrica == 59){//HD 188632
            //30544 31/7/2008?>
            <td nowrap  valign='top'>
                <input type="hidden" name="produto_voltagem" size="5" value="<? echo $produto_voltagem ?>">
            </td>
            <?}else{?>
            <td nowrap  valign='top'>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">
					<span rel='produto_voltagem'>Voltagem</span>
				</font>
                <br />
                <input class="frm" id='produto_voltagem' type="text" name="produto_voltagem" size="5" value="<? echo $produto_voltagem ?>" <? if ($login_fabrica != 1 || strlen($tipo_os) > 0) echo "readonly"; ?> >
            </td>
            <?}?>
           <td nowrap  valign='top'>
<?
if ($login_fabrica == 6){
    echo "                <font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\" color='#cc0000'>Data de entrada </font>";
}else{
    echo "                <font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\"><span rel='data_abertura'>Data Abertura</span></font>";
}
?>
                <br/>
<?
//                if (strlen($data_abertura) == 0 and $login_fabrica <> 1) $data_abertura = date("d/m/Y");
    if(($login_fabrica==11 or $login_fabrica==45 or $login_fabrica ==50 or $login_fabrica ==80) and strlen($os) == 0){
        $data_abertura = date("d/m/Y");
        $bloqueia_data =  " DISABLED onclick='javascript:dataAbertura();' ";
        echo "<input name='data_abertura' id='data_abertura' value='$data_abertura' type='hidden'>";
    }
?>
                <input name="data_abertura" id="data_abertura" rel='data' size="12" maxlength="10"
                      value="<? echo $data_abertura; ?>" type="text" class="frm"
                     onblur="this.className='frm'; displayText('&nbsp;');<? if($login_fabrica==24){echo "MostraAtencao('atencao');"; } ?>" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Data da Abertura da OS.');<? if($login_fabrica==24){echo "MostraAtencao('atencao');"; } ?>" tabindex="0" <? echo $bloqueia_data ?>>
                     <br /><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
            </td><?php

			#wanke pediu para adicionar campo data_fabricacao
			if ($login_fabrica == 91 or $login_fabrica == 96) {
				echo "<td nowrap  valign='top'>
						<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data Fabricação</font><br>
						<input name='data_fabricacao' id='data_fabricacao' rel='data' size='12' maxlength='10'
						  title='Favor informar a Data de fabricação'
						  value='$data_fabricacao' type='text' class='frm'>
					  </td>";
			}

			if ($login_fabrica == 7) { #HD 49336?>
				<td nowrap  valign='top'>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">
						Hora da Abertura
					</font>
					<br /><?php
					if (strlen($hora_abertura) == 0) {
						#$hora_abertura = date("H:i"); //Vazio para forçar o preenchimento
					} else {
						$hora_abertura = substr($hora_abertura,0,5);
					}?>
					<input name="hora_abertura" id="hora_abertura" rel='hora' size="7" maxlength="5"
						  value="<? echo $hora_abertura; ?>" type="text" class="frm"
						  title='Favor informar a Data/Hora que o equipamento foi recebido ou da solicitação de atendimento'
						 onblur="this.className='frm'; displayText('&nbsp;');"
						onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Hora da Abertura da OS.');">
				</td><?php
			}

            if ($login_fabrica == 19) {
                if (strlen($data_digitacao) == 0) {
                    $data_digitacao= date('d/m/Y');
                }?>
                <td nowrap valign='top'>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua) echo "Data Digitação";else echo "Data Digitação";?></font>
                    <br />
                    <input class="frm" type="text" name="data_digitacao" size="12" value="<? echo $data_digitacao?>" readonly>
                </td><?php
                echo "</td>";
            }

            if (!in_array($login_fabrica,array(6,24,19,50,43,56,59,2,30,15,85,74,95)) AND $linhainf <> 't') {?>
                <td nowrap  valign='top'>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><?php
				if ($login_fabrica == 35) {
                    echo "PO#";
                } else {
                    echo "<span rel='produto_serie'>N. Série</span>";
                }?>
                </font>
                <br /><?php
				if ($login_fabrica == 40) {
                    $produto_serie_ini = substr($produto_serie,0,2);
                    $produto_serie     = substr($produto_serie,2,7);?>
					<input type='text' name='produto_serie_ini' value='<?=$produto_serie_ini?>' maxlength='2' size='3' class="frm"> -
                <?}

				$serie_ml = '20'; $serie_evt = ''; $serie_blur = '';
				switch ($login_fabrica) {
					case 94: $serie_ml = '6'; break;
					case 40: $serie_ml = '7'; break;
					case 35: $serie_ml = '12'; break;
					case 3:
						$serie_ml  = '20';
						$serie_evt= 'onKeyUp="javascript:somenteMaiusculaSemAcento(this);"';
					break;
					case 51:
						$serie_ml = '20';
						$serie_evt= 'onKeyUp="javascript:somenteMaiusculaSemAcento(this);"';
						break;
					case 50:
					case 43: $serie_evt  = 'onChange="javascript:this.value.toUpperCase();"'; break;
					case  7: $serie_blur = 'verificaProduto(document.frm_os.produto_referencia,this);'; break;
					case  3: $serie_blur = ($login_posto == 6359) ? " MostraEsconde('dados_1');" : ''; break;
				}

				if ($serie_ml != '') $serie_ml = "maxlength='$serie_ml' ";?>

				<input class="frm" type="text" name="produto_serie" id="produto_serie"  size="14" <? echo $serie_ml;?>
					   value="<? echo $produto_serie; ?>" <? echo $serie_evt;?>
					  onblur="this.className='frm'; displayText('&nbsp;');<? echo $serie_blur;?>"
					  onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.');<? echo $serie_blur;?>">
				<?php
				if ($login_fabrica == 3) {//HD 256659?>
					<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='fnc_pesquisa_serie(document.frm_os.produto_serie)' style='cursor: pointer' onBlur="upperMe()"/><?php
				}

				if ($login_fabrica == 25) {?>
					&nbsp;<INPUT TYPE="button" onClick='javascript:fn_verifica_garantia();' name='Verificar' value='Verificar'<? if($login_fabrica ==3) echo " rel='serie'";?>><?php
				}?>
				<br /><font face='arial' size='1'><? if ($login_fabrica == 1) echo "(somente p/ linha DeWalt)"; ?></font>
                <div id='dados_1' style='position:absolute; display:none; border: 1px solid #949494;background-color: #f4f4f4;'></div><?php
				if ($login_fabrica == 35) {
					echo "<div width='100' style='font-size: 9px;'>Encontra-se na etiqueta de voltagem do aparelho.<br />
					Caso o produto não possua número de série,<br />
					entre em contato com o fabricante.</<div>";
				}

				if ($login_fabrica == 40) {
					echo "<div width='100' style='font-size: 9px;'>Ex:AB-1234567</div>";
				}?>
            </td>
            <? } ?>
        </tr>

        <?    //hbtech 4/3/2008 14824
        if ($login_fabrica == 25) {?>
			<tr>
				<td colspan='4'>
					<div id='div_estendida' style='text-align:center;'><?php
					if (strlen($produto_serie) > 0) {
						include "conexao_hbtech.php";

						$sql = "SELECT    idNumeroSerie  ,
										idGarantia     ,
										revenda        ,
										cnpj
								FROM numero_serie
								WHERE numero = '$produto_serie'";
						$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());

						if (mysql_num_rows($res) > 0) {
							$idNumeroSerie = mysql_result($res,0,idNumeroSerie);
							$idGarantia    = mysql_result($res,0,idGarantia);
							$es_revenda       = mysql_result($res,0,revenda);
							$es_cnpj          = mysql_result($res,0,cnpj);

							if(strlen($idGarantia)==0){
								echo "Número de série não encontrado nas vendas";

							}
						}
					}?>
					</div>
				</td>
			</tr><?php
        }//fim hbtech ?>
        </table><?php

		if ($login_fabrica == 7) {?>

			<table width="50%" border="0" cellspacing="5" cellpadding="2">
				<tr>
					<td nowrap  valign='top'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Capacidade</font>
						<br />
						<? if (strlen($produto_capacidade)>0){
							echo "<INPUT TYPE='hidden' name='capacidade' id='capacidade' value='$produto_capacidade'>";
							echo "<INPUT TYPE='text' VALUE='$produto_capacidade' SIZE='9' onClick=\"alert('Não é possível alterar a capacidade')\" disabled>";
						}else{?>
							<INPUT TYPE="text" NAME="produto_capacidade" id='produto_capacidade' VALUE="<?=$produto_capacidade?>" SIZE='9' MAXLENGTH='9'>
						<?}?>
					</td>
					<td nowrap  valign='top'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Divisão</font>
						<br />
						<? if (strlen($produto_divisao)>0){
							echo "<input type='hidden' name='divisao' value='$produto_divisao'>";
							echo "<INPUT TYPE='text' VALUE='$produto_divisao' maxlength='19' SIZE='9' onClick=\"alert('Não é possível alterar a divisão')\" disabled>";
						}else{?>
							<input type="text" name="divisao" id='divisao' value="<?=$divisao?>" size='9' maxlength='9'>
						<?}?>
					</td>
				</tr>
			</table><?php

		} ?>

        <table width="100%" border="0" cellspacing="5" cellpadding="2">
        <tr valign='top'>
        <? if ($login_fabrica==19){ ?>
        <td nowrap  valign='top'>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
            <br />
            <input class="frm" type="text" name="produto_serie" id="produto_serie"  size="8"
               maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');<? if($login_fabrica==3 and $login_posto==6359){ echo " MostraEsconde('dados_1');";} ?>" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.'); <? if($login_fabrica==3 and $login_posto==6359){ echo " MostraEsconde('dados_1');";} ?> ">
            <br />
            <font face='arial' size='1'><? if ($login_fabrica == 1) echo "(somente p/ linha DeWalt)"; ?></font>
            <div id='dados_1' style='position:absolute; display:none; border: 1px solid #949494;background-color: #f4f4f4;'>
            </div>
        </td>
		<? } ?>
        <td width='100' valign='top' nowrap>
        <? if ($login_fabrica == 30){?>
        <acronym title='Campo Obrigatório'>
            <font color='#AA0000'size='1' face='Geneva, Arial, Helvetica, san-serif'>Nota Fiscal</font>
        </acronym>
        <?}else{?>
        <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="nota_fiscal">Nota Fiscal</span></font>
        <br />
        <?}?>
        <?    if($login_fabrica ==45){ // HD 31076
                $maxlength = "14";
            }elseif($login_posto==20314){
                $maxlength = "12";
            }else{
                $maxlength = "8";
            }
        ?>
        <input class="frm" type="text" name="nota_fiscal" id="nota_fiscal" size="10" maxlength="<? echo $maxlength ?>" value="<? echo $nota_fiscal ?>" <? if($login_fabrica==30 or $login_fabrica==45){?> onkeypress="mascara(this,soNumeros)"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número da Nota Fiscal.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>
        </td>
        <? if($login_fabrica==45){ ?>
        <td width='100' valign='top' nowrap>
        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Preço do Produto</font>
        <br />
        <input class="frm" type="text" name="preco_produto" size="10" maxlength="8"  value="<? echo $preco_produto ?>">
        </td>
        <? } ?>
        <td width='110' valign='top' nowrap>
        <? if ($login_fabrica == 30){?>
            <acronym title='Campo Obrigatório'>
                <font color='#AA0000'size='1' face='Geneva, Arial, Helvetica, san-serif'>Data Compra</font>
            </acronym>
        <?}else{?>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='data_nf'>Data Compra</span></font>
            <br />
        <?}?>
        <input class="frm" type="text" name="data_nf" id="data_nf" rel='data' size="12" maxlength="10" value="<? echo $data_nf ?>" onblur="this.className='frm'; displayText('&nbsp;');<? if($login_fabrica==24){echo "MostraAtencao('atencao'); "; } if($login_fabrica==51) echo "verifica_garantia('data_nf','produto_referencia','data_abertura');";?>" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a data da compra. Verifique se o produto está dentro do PRAZO DE GARANTIA.');<? if($login_fabrica==24){echo "MostraAtencao('atencao');"; } ?>" tabindex="0" <? if (strlen($locacao) > 0) echo "readonly"; ?> ><br /><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
        <div id='atencao' style='position:absolute; display:none; border: 1px solid #949494;background-color: #f4f4f4;'>
                        </div>

                <div id='div_garantia' style='background:#efefef;border:#999999 1px solid;font-size:10px;<?if(!isset($liberar_digi)) echo "visibility:hidden;position:absolute;";?>' >
                <?
                /* HD 26244 */
                if ($login_fabrica == 30){
                    echo "<b>LGI:</b>";
                }else{
                    echo "<b>Anexar a cópia do certificado de garantia na OS</b><br /><br />Certificado de Garantia:";
                }
                ?>
                <input type='text' name='certificado_garantia' id='certificado_garantia' value='<?=$certificado_garantia?>' size='5' maxlength='<?=($login_fabrica == 30)?'6':'30'?>'>
        </div>
        </td>
            <? if ($login_fabrica == 30) { ?>
                <td valign='top' align='left'>
                    <acronym title='Campo Obrigatório'>
                        <font color="#AA0000" size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Reclamado</font>
                        <br />
                    </acronym>
                    <?
                        if($pedir_defeito_reclamado_descricao == 't'){
                            //HD 204082: Recuperar defeito reclamado da pré-os
                            echo "<acronym title='Campo Obrigatório'><input type='text' name='defeito_reclamado_descricao' id='defeito_reclamado_descricao' class='frm' ".
                         "value='$defeito_reclamado_descricao' size='40' onKeyUp='somenteMaiusculaSemAcento(this);'></acronym>";
                        }else{
                            if(strlen($defeito_reclamado) >0) {
                                $sql = " SELECT descricao
                                    FROM tbl_defeito_reclamado
                                    WHERE defeito_reclamado = $defeito_reclamado";
                                $res = pg_query($con,$sql);
                                if(pg_num_rows($res) > 0){
                                    $reclamado_descricao = pg_fetch_result($res,0,descricao);
                                }
                            }


                            //HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
                            if ($login_fabrica == 3) {
                                $defeito_reclamado_onchange = "onchange='mostraDefeitoDescricao($login_fabrica)'";
                            }

                            echo "<acronym title='Campo Obrigatório'><select name='defeito_reclamado' id='defeito_reclamado' style='width: 220px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);' class='frm' $defeito_reclamado_onchange>";
                            if(strlen($defeito_reclamado) > 0) {
                                echo "<option id='opcoes' value='$defeito_reclamado'>$reclamado_descricao</option>";
                            }else{
                                echo "<option id='opcoes' value='0'></option>";
                            }
                            echo "</select></acronym>";
                            if ($login_fabrica == 11){
                                echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'><br />*Caso o defeito não seja listado verifique se os dados<br />do <B><U><I>produto</I></U></B> estão corretos pesquisando-o pela lupa.</font>";
                            }
                            echo "</td>";
                        }
                        //HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
                        if ($login_fabrica == 3){
                            echo "<td style='display:none;' nowrap valign='top' id='td_defeito_reclamado_descricao'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado Adicional</font><br /><INPUT TYPE='text' name='defeito_reclamado_descricao' class='frm' value='$defeito_reclamado_descricao' size='30'></td>
                            <script language='javascript'>
                            mostraDefeitoDescricao($login_fabrica);
                            </script>
                            ";
                        }

            } else {?>

                <td valign='top' align='left'><?php
					if ($login_fabrica <> 94) {?>
	                    <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='defeito_reclamado_descricao'>Defeito Reclamado</span></font>
						<br /><?php
					}

					if (!in_array($login_fabrica, array(42,86,94,74,96,94))) //HD314245

                        if ($pedir_defeito_reclamado_descricao == 't') {
                            //HD 204082: Recuperar defeito reclamado da pré-os
                            echo "<input type='text' name='defeito_reclamado_descricao' id='defeito_reclamado_descricao' class='frm' ". "value='$defeito_reclamado_descricao' size='40' onKeyUp='somenteMaiusculaSemAcento(this);'>";
                        } else {
                            if(strlen($defeito_reclamado) >0) {
                                $sql = " SELECT descricao
                                    FROM tbl_defeito_reclamado
                                    WHERE defeito_reclamado = $defeito_reclamado";
                                $res = pg_query($con,$sql);
                                if(pg_num_rows($res) > 0){
                                    $reclamado_descricao = pg_fetch_result($res,0,descricao);
                                }
                            }

                            //HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
                            if ($login_fabrica == 3) {
                                $defeito_reclamado_onchange = "onchange='mostraDefeitoDescricao($login_fabrica)'";
                            }

							#HD 424887 - INICIO

							/* ESTA VERIFICAÇÃO ESTÁ SENDO FEITA PORQUE PARA AS FABRICAS QUE ESTÃO NESTE ARRAY
							A CHAMADA DESTA FUNÇÃO "listaDefeitos" SERÁ FEITA NO ONBLUR DO PRODUTO, POIS NÃO
							HAVERÁ INTEGRIDADE COM O DEFEITO_RECLAMADO - by: gabriel silveira */

							if (!in_array($login_fabrica,$fabricas_defeito_reclamado_sem_integridade)) {
								$onfocus_integridade_def_reclamado = "onfocus='listaDefeitos(document.frm_os.produto_referencia.value);'";
							} else {
								$onfocus_integridade_def_reclamado = null;
							}

							if ($login_fabrica == '3' && $hd_chamado <> '') {?>

								<select name="defeito_reclamado" style='width: 420px;' id="defeito_reclamado"  class="frm">
									<option value="">Selecione um Defeito</option><?php
									$sql ="SELECT DISTINCT(tbl_defeito_reclamado.defeito_reclamado) AS cod_reclamado,
											tbl_defeito_reclamado.descricao AS desc_reclamado
											FROM tbl_diagnostico
											JOIN tbl_familia ON tbl_diagnostico.familia = tbl_familia.familia
											AND tbl_diagnostico.fabrica = tbl_familia.fabrica
											JOIN tbl_defeito_reclamado
											ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
											AND tbl_diagnostico.fabrica = tbl_defeito_reclamado.fabrica
											JOIN tbl_linha
											ON tbl_diagnostico.linha = tbl_linha.linha
											WHERE tbl_diagnostico.linha = 528
											AND tbl_diagnostico.fabrica = $login_fabrica
											AND tbl_diagnostico.ativo = 't'
											ORDER BY tbl_defeito_reclamado.descricao";

									$res = pg_exec($con,$sql);

									if (pg_numrows($res) > 0) {

										for ($x = 0; pg_numrows($res) > $x; $x++) {

											$cod_reclamado   = pg_result($res, $x, 'cod_reclamado');
											$cdesc_reclamado = pg_result($res, $x, 'desc_reclamado');

											$selectd_reclamado = '';

											if ($defeito_reclamado == $cod_reclamado) {
												$selectd_reclamado = " SELECTED ";
											}?>
											<option value="<?php echo $cod_reclamado;?>" <?php echo $selectd_reclamado;?> title="<?php echo $cdesc_reclamado;?>"><?php echo $cdesc_reclamado;?></option> <?php

										}
									}?>
								</select><?php

							} else {

								echo "<select name='defeito_reclamado' id='defeito_reclamado' style='width: 200px;' $onfocus_integridade_def_reclamado class='frm' $defeito_reclamado_onchange>";

								if (strlen($defeito_reclamado) > 0 || strlen($defeito_reclamado) == 0) {

									if (in_array($login_fabrica, $fabricas_defeito_reclamado_sem_integridade)) {

										$sql = " SELECT defeito_reclamado, descricao
													FROM tbl_defeito_reclamado
													WHERE fabrica = $login_fabrica
													and ativo='t' ORDER BY descricao";

										$res = pg_query($con,$sql);

										if (pg_num_rows($res) > 0) {

											for ($y = 0; $y < pg_num_rows($res); $y++) {

												$xdefeito_reclamado  = pg_fetch_result($res, $y, 'defeito_reclamado');
												$reclamado_descricao = pg_fetch_result($res, $y, 'descricao');

												echo "<option id='opcoes' title='$reclamado_descricao' value='$xdefeito_reclamado'";
												if ($defeito_reclamado == $xdefeito_reclamado) echo "selected";
												echo ">$reclamado_descricao</option>";

											}

										} else {
											echo "<option id='opcoes' value='0'></option>";
										}

									} else {

										$sql = " SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado = $defeito_reclamado";
										$res = pg_query($con,$sql);

										if (pg_num_rows($res) > 0) {
											$reclamado_descricao = pg_fetch_result($res, 0, 'descricao');
										}

										echo "<option id='opcoes' value='$defeito_reclamado' title='$reclamado_descricao'>$reclamado_descricao</option>";

									}

								} else {
									echo "<option id='opcoes' value=''></option>";
								}

                            echo "</select>";

                            if ($login_fabrica == 11) {

                                echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'><br />*Caso o defeito não seja listado verifique se os dados<br />do <B><U><I>produto</I></U></B> estão corretos pesquisando-o pela lupa.</font>";

							}

						}

                        echo "</td>";

                        } else{ //mostra os dois, para a makita HD314245, IBBL HD 322650, Atlas, Bosch Security

							if ($login_fabrica <> 94) {

								if (strlen($defeito_reclamado) > 0) {

									$sql = " SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado = $defeito_reclamado";
									$res = pg_query($con, $sql);

									if (pg_num_rows($res) > 0) {
										$reclamado_descricao = pg_fetch_result($res, 0, 'descricao');
									}

								}

								echo "<select name='defeito_reclamado' id='defeito_reclamado' style='width: 200px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);' class='frm' $defeito_reclamado_onchange>";

								if (strlen($defeito_reclamado) > 0) {
									echo "<option id='opcoes' value='$defeito_reclamado'>$reclamado_descricao</option>";
								} else {
									echo "<option id='opcoes' value=''></option>";
								}

								echo '</select></td>'; #HD 389165

							}

							if ($login_fabrica<>86 and $login_fabrica <> 74){

								echo '<td valign="top" align="left" style="padding:0 5px 0 5px;"> <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="defeito_reclamado_descricao">Descrição do Defeito Reclamado</span></font>
								<br />';
								echo "<input type='text' name='defeito_reclamado_descricao' valida='defeito_reclamado_descricao' id='defeito_reclamado_descricao' class='frm' ".
								"value='$defeito_reclamado_descricao' size='22' onKeyUp='somenteMaiusculaSemAcento(this);'> </td>";

							}

						}
                        //HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
                        if ($login_fabrica == 3){
                            echo "<td style='display:none;' nowrap valign='top' id='td_defeito_reclamado_descricao'>".
								 "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado Adicional</font><br />".
								 "<INPUT TYPE='text' name='defeito_reclamado_descricao' class='frm' value='$defeito_reclamado_descricao' size='30'>".
								 "</td>
                            <script language='javascript'>
                            mostraDefeitoDescricao($login_fabrica);
                            </script>
                            ";
                        }
            }

			//hd 24288
            if ($login_fabrica == 3 AND $login_posto == 6359 AND 1==2) {
            if ($linha == 335) {
                $mostrar = "block";
            } else {
                $mostrar = "none";
            }?>
			<td nowrap valign='middle' style='font-size: 10px'>
				<span id='atendimento_dominico_span' style='display:<?=$mostrar?>'>
				<input type="checkbox" NAME="atendimento_domicilio" value="t" <?if($atendimento_domicilio=='t')echo "checked";?> onFocus="mostraDomicilio()" ><font size="1" face="Geneva, Arial, Helvetica, san-serif">Atendimento Domicilio</font>
				</span>
			</td>
        <?}

		if ($login_fabrica == 15) {?>

			<td nowrap valign='top' style='font-size: 10px'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cartão Clube</font>
				<br />
				<input  name ="cartao_clube" class ="frm" type ="text" size ="15" maxlength="15" value ="<? echo $cartao_clube ?>" onblur = "this.className='frm';MostraEsconde('cartao'); displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Cartão Clube, caso tenha.');MostraEsconde('cartao')"><br /><div id='cartao' style='position:absolute; border: 1px solid #949494;background-color: #f4f4f4; font-size:10px; padding:1px; display:none;'><i>Caso o consumidor <b>não</b><br /> tenha, deixe em branco.</i></div>
			</td><?php

		}

        if ($combo_tipo_atendimento) {?>

			<td align='left' id="mostra_tipo_atendimento"
					<?php
					if ($login_fabrica == 35 and (empty($tipo_atendimento) or $tipo_atendimento == 'null')){
						echo 'style="display:none;"';
					}
					?>>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=($login_fabrica == 7) ? "Natureza" : "Tipo Atendimento"?></font><br />
				<select name="tipo_atendimento" id='tipo_atendimento' class='frm' style='width:220px;'<?php
					if ($login_fabrica == 50) { // HD 54668 para Colormaq ?>
						onFocus="tipoatendimento();"<?php
					}?>
					onChange="verifica_atendimento();">
					<option></option><?php

					if ($login_fabrica == 1) $sql_add1 = " AND tipo_atendimento NOT IN (17,18,35,64) ";

					/*HD:22505- COLORMAQ - Tipo atendimento de deslocamento só aparece se o posto tem km cadastrado(maior que 0)*/
					$sql_deslocamento = " ";

					if ($login_fabrica == 50) {

						$sql_deslocamento = " AND tipo_atendimento NOT IN (
													SELECT
														CASE WHEN valor_km > 0
															Then 0
															Else 55
													END as tipo_atendimento
													FROM tbl_posto_fabrica
													WHERE fabrica = $login_fabrica
														AND posto = $posto
												) ";

					}

					$sql = "SELECT *
							FROM tbl_tipo_atendimento
							WHERE fabrica = $login_fabrica
							AND   ativo IS TRUE
							$sql_add1
							$sql_deslocamento
							ORDER BY tipo_atendimento ";

					if ($login_fabrica == 19) {//HD 15937
						$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND codigo IN (0,2,3,5,14) ORDER BY codigo";
					}

					if ($login_fabrica == 15) {

						if ($login_posto == 2405) {
							$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY descricao";
						} else {

							$sql = "SELECT *
									FROM tbl_tipo_atendimento
									WHERE fabrica = $login_fabrica
									AND   ativo   IS TRUE $sql_add1
									ORDER BY tipo_atendimento";

						}

					}

					$res = pg_query ($con, $sql);

					for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {

						$codigo  = str_pad(pg_fetch_result($res, $i, 'codigo'), 2, '0', STR_PAD_LEFT);
						$desc    = pg_fetch_result($res, $i, 'descricao');
						$tipo_at = pg_fetch_result($res, $i, 'tipo_atendimento');

						$txt_option = ($login_fabrica != 90) ? "$codigo - $desc" : $desc;
						$opt_sel    = ($tipo_atendimento == $tipo_at) ? ' SELECTED':'';

						echo "<option value='$tipo_at'$opt_sel>$txt_option</option>";

					}

					if ($login_fabrica == 19 OR $login_posto == 6359) {

						$sql = " SELECT atende_comgas FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
						$res = pg_query($con,$sql);

						$atende_comgas = pg_fetch_result($res,0,0);
						if (strlen($atende_comgas) > 0 and $atende_comgas == 't') {
							echo "<option ";
						if ($tipo_atendimento == 20 ) echo " selected ";
							echo " value='20'>08 - Atend.Comgás</option>\n";
						}

					}?>
				</select>
			</td><?php

		}

        if ($login_fabrica == 6 or $login_fabrica == 11) {

            if ($login_posto == 4262 or $login_fabrica == 11) {

                echo "<td align='left' width='110' valign='top' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'><label for='rg_produto'>Rg do Produto</label></font><br />";
                echo "<input type='text' name='rg_produto' class='frm' id='rg_produto' size='12' maxlength='10' value='$rg_produto'>";
                echo "</td>";

            }

            if ($login_fabrica <> 11) {

                echo "<td align='left' width='110' valign='top' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'><label for='os_posto'>OS Posto</label></font><br />";
    	            echo "<input type='text' name='os_posto' class='frm' id='os_posto' size='12' maxlength='10' value='$os_posto'>";
                echo "</td>";

            }

        } else if ($login_fabrica == 30) {//HD 65178

            echo "<td align='left' width='110' valign='top' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'><label for='os_posto'>OS Revendedor</label></font><br />";
	            echo "<input type='text' name='os_posto' class='frm' id='os_posto' size='12' maxlength='20' value='$os_posto'>";
            echo "</td>";

        } else if ($login_fabrica == 2) { // HD 81252

            echo "<td align='left' width='110' valign='top' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'><label for='os_posto'>OS Posto</label></font><br />";
	            echo "<input type='text' name='os_posto' class='frm' id='os_posto' size='12' maxlength='10' value='$os_posto'>";
            echo "</td>";

        } else if ($login_fabrica == 50) {//HD 79844

            echo "<td align='left' width='110' valign='top' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'><label for='data_fabricacao'>Data Fabricação</label></font><br />";
	            echo "<input type='text' name='data_fabricacao' class='frm' id='data_fabricacao' size='12' maxlength='10' value='$data_fabricacao'>";
            echo "</td>";

        }

		if ($login_fabrica <> 7 and $login_fabrica <> 11) {

            echo "<td nowrap  valign='top'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>&nbsp;<span rel='prateleira_box'>Box/Prateleira</span></font><br />";
            echo "&nbsp;<INPUT TYPE='text' id='prateleira_box' name='prateleira_box' class='frm' value='$prateleira_box' size='8' maxlength='10'>";
            echo "</td>";

        }?>
        </tr><?php

		if ($login_fabrica == 43) {//HD 73930 18/02/2009?>
			<tr>
				<td colspan = '2'></td>
				<td valign='top' align='left'>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">COA Microsoft</font>
					<br />
					<input rel="coa" class="frm" type="text" name="coa_microsoft" id="coa_microsoft" size="40" maxlength="29" value="<? echo $coa_microsoft;?>" onBlur="javascript: this.value=this.value.toUpperCase(); this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o COA Microsoft.');">
				</td>
			</tr><?php
		}

		if (in_array($login_fabrica, array(40)))
		{

		?>

			</table>

			<center>
			<table border='0' id='unidade_cor' style='display: none;'>
			<input type='hidden' name='familia' id='familia' value='<?=$familia?>'>
			<tr>
				<td bgcolor='#FFAE00' width='28px' align='center'>
					<input type='radio' name='unidade_cor' id='unidade_cor' value='amarelo' <? if($unidade_cor == 'amarelo') echo "CHECKED"; ?>>
				</td>
				<td align='center' nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Unidade Amarela</font>
				</td>
				<td>
					&nbsp;
				</td>
				<td bgcolor='#1E1E1E' width='28px' align='center'>
					<input type='radio' name='unidade_cor' id='unidade_cor' value='preto' <? if($unidade_cor == 'preto') echo "CHECKED"; ?>>
				</td>
				<td align='center' nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Unidade Preta</font>
				</td>
			</tr>

			</table>
			</center>

		<?
		}


if ($login_fabrica == 19) {//HD 18153
    echo "<center><font size='-2'>Para gravar a OS é necessário adicionar os defeitos reclamados, basta clicar em ADICIONAR DEFEITOS</font></center>";
    echo "<center><input type='button' onclick=\"javascript: adicionaIntegridade()\" value='Adicionar Defeito' name='btn_adicionar'></center><br />";
    echo "
    <table style=' border:#485989 1px solid; background-color: #e6eef7;font-size:12px;display:none' align='center' width='400' border='0' id='tbl_integridade' cellspacing='3' cellpadding='3'>
    <thead>
    <tr bgcolor='#596D9B' style='color:#FFFFFF;'>
    <td align='center'><b>Defeito Reclamado</b></td>
    <td align='center'><b>Ações</b></td>
    </tr>
    </thead>
    <tbody>";
    if (strlen($os) > 0) {
        $sql_cons = "SELECT
                        tbl_defeito_constatado.defeito_constatado,
                        tbl_defeito_constatado.descricao         ,
                        tbl_defeito_constatado.codigo
                FROM tbl_os_defeito_reclamado_constatado
                JOIN tbl_defeito_constatado USING(defeito_constatado)
                WHERE os = $os";

        $res_dc = pg_query($con, $sql_cons);

        if (pg_num_rows($res_dc) > 0) {

            for ($x = 0; $x < pg_num_rows($res_dc); $x++) {

                $dc_defeito_constatado = pg_fetch_result($res_dc, $x, 'defeito_constatado');
                $dc_descricao          = pg_fetch_result($res_dc, $x, 'descricao');
                $dc_codigo             = pg_fetch_result($res_dc, $x, 'codigo');

                $aa = $x + 1;

                echo "<tr>";
	                echo "<td><font size='1'><input type='hidden' name='integridade_defeito_constatado_$aa' value='$dc_defeito_constatado'>$dc_codigo-$dc_descricao</font></td>";
    	            echo "<td align='right'><input type='button' onclick='removerIntegridade(this);' value='Excluir'></td>";
                echo "</tr>";

            }

            echo "<script>document.getElementById('tbl_integridade').style.display = \"inline\";</script>";

        }

    }

    echo "</tbody></table>";

}

if ($login_fabrica == 1) { ?>

    <table width="100%" border="0" cellspacing="5" cellpadding="0">
        <tr valign='top'>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Código Fabricação</font>
                <br />
                <input  name ="codigo_fabricacao" class ="frm" type ="text" size ="13" maxlength="20" value ="<? echo $codigo_fabricacao ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Código de Fabricação.');">
            </td>
            <td nowrap>
            </td>
            <td nowrap><?// HD15589?>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">30 dias Satisfação DeWALT/Porter Cable</font>
                <br />
                <input name ="satisfacao" class ="frm" type ="checkbox" value="t" <? if ($satisfacao == 't') echo "checked"; ?>>
            </td>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Laudo técnico</font>
                <br />
                <input  name ="laudo_tecnico" class ="frm" type ="text" size ="20" maxlength="50" value ="<? echo $laudo_tecnico; ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o laudo técnico.');">
            </td>
        </tr>
    </table><?php

}?>

<hr>
<input type="hidden" name="consumidor_cliente">
<input type="hidden" name="consumidor_rg">

<table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
    <tr>
        <td>
            <? if ($login_fabrica == 30) { ?>
                <acronym title='Campo Obrigatório'>
                    <font color="#AA0000"size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Consumidor</font>
                </acronym>
                <br />
                <acronym title='Campo Obrigatório'>
                    <input class="frm" type="text" name="consumidor_nome" id="consumidor_nome" size="30" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o nome do Cliente.');">
                    <? if($login_fabrica == 7 OR $login_fabrica == 30){ ?>
                        <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'  style='cursor: pointer'>
                    <?}?>
                    &nbsp;
                </acronym>
            <? } else { ?>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_nome'>Nome Consumidor</span></font>
                    <br />
                    <input class="frm" type="text" name="consumidor_nome" id="consumidor_nome" size="30" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o nome do Cliente.');">
                    <? if($login_fabrica == 7 OR $login_fabrica == 30){ ?>
                        <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'  style='cursor: pointer'>
                    <?}?>
                    &nbsp;
            <? } ?>
        </td>
        <? if($login_fabrica<>19){ ?>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_cpf'>CPF/CNPJ Consumidor</span></font>
                <br />
                <input class="frm" type="text" name="consumidor_cpf"  id="consumidor_cpf" size="17" maxlength="18" value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e traços.');">
                <? if($login_fabrica == 7 OR $login_fabrica == 30 ) { ?>
                    <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")'  style='cursor: pointer'>
                <?}?>
                <? if(($login_fabrica== 79))
                    echo "<font color='#FF0000'>*</font>"; // HD 78055?>
                &nbsp;
            </td>
        <? } ?>
        <td>
            <?if ($login_fabrica == 30) { ?>
                <acronym title='Campo Obrigatório'>
                    <font color="#AA0000" size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
                    <br />
                </acronym>
                <br />
                <acronym title='Campo Obrigatório'>
                    <input class="frm" type="text" rel='fone' name="consumidor_fone" id="consumidor_fone"   size="15" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
                    <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_fone,"fone")'  style='cursor: pointer'>
                </acronym>
            <?}else{?>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_fone'>Fone</span></font>
                <br />
                <input class="frm" type="text"  rel='fone' name="consumidor_fone" id="consumidor_fone"   size="15" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');"
                <?if($login_fabrica==19 or $login_fabrica==3 or $login_fabrica==50){?>
                    maxlength="15"
                <?}else{?>
                    maxlength="20" <?}?>>
                <?if($login_fabrica==19 or $login_fabrica==3){?>
                        <span style='font-size:10px;color:#8F8F8F'></span>
                <?}?>
                <? if($login_fabrica == 30 ) { ?>
                    <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor    (document.frm_os.consumidor_fone,"fone")'  style='cursor: pointer'>
                <?}?>
                <? if($login_fabrica== 79)
                    echo "<font color='#FF0000'>*</fotn>"; // HD 78055?>
            <?}?>
        </td>
         <?if($login_fabrica==11) { // HD 51964?>
             <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone Rec</font>
                <br />
                <input class="frm" type="text" rel='fone' name="consumidor_fone_recado" id="consumidor_fone_recado"   size="15" value="<? echo $consumidor_fone_recado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');"
                maxlength="20">
            </td>
         <? } ?>
        <td>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_cep'>CEP</span></font>
            <br />
            <input class="frm" type="text" name="consumidor_cep" id="consumidor_cep"  size="12" maxlength="10" value="<? echo $consumidor_cep ?>"
		onkeypress="mascara(this,soNumeros)"
		<?php if($login_fabrica != 91){?>
		onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.consumidor_endereco, document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado) ;"
		<? }?>
		onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP do consumidor.');"
	    />
	    <?php if($login_fabrica == 91){?>
		<input type='button' value='Pesquisar' class='frm' onclick="if(document.frm_os.consumidor_cep.value.length < 8) {alert('Informe um CEP válido!');}else{ buscaCEP(document.frm_os.consumidor_cep.value, document.frm_os.consumidor_endereco, document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado) ;}" style='cursor: pointer' />
	    <? }?>
        </td>
    </tr>
</table>

<table width='750' align='center' border='0' cellspacing='5' cellpadding='2'>
    <tr>
        <? if ($login_fabrica == 30) { ?>
            <td align='left' nowrap>
                <acronym title='Campo Obrigatório'>
                    <font color="#AA0000"size="1" face="Geneva, Arial, Helvetica, san-serif">Endereço</font>
                    <br />
                </acronym>
                <acronym title='Campo Obrigatório'>
                    <input class="frm" type="text" name="consumidor_endereco"   id='consumidor_endereco' size="30" maxlength="60" value="<? echo $consumidor_endereco ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço do consumidor.');">
                </acronym>
            </td>
        <? } else { ?>
            <td align='left' nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_endereco'>Endereço</span></font><br />
                <input class="frm" type="text" name="consumidor_endereco"   id='consumidor_endereco' size="30" maxlength="60" value="<? echo $consumidor_endereco ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço do consumidor.');">
            </td>
        <? } ?>

        <? if ($login_fabrica == 30) { ?>
            <td nowrap>
                <acronym title='Campo Obrigatório'>
                    <font color="#AA0000"size="1" face="Geneva, Arial, Helvetica, san-serif">Número</font><br />
                </acronym>
                <acronym title='Campo Obrigatório'>
                    <input class="frm" type="text" name="consumidor_numero"  id='consumidor_numero' size="5" maxlength="10" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço do consumidor.');">
                </acronym>
            </td>
        <? } else { ?>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_numero">Número</span></font><br />
                <input class="frm" type="text" name="consumidor_numero"  id='consumidor_numero' size="5" maxlength="10" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');initialize('');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço do consumidor.');">
            </td>
        <? } ?>
        <td nowrap>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_complemento'>Complemento</span></font><br />
            <input class="frm" type="text" name="consumidor_complemento" id="consumidor_complemento"  size="10" maxlength="20" value="<? echo $consumidor_complemento ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço do consumidor.');">
        </td>
        <? if ($login_fabrica == 30) { ?>
            <td nowrap>
                <acronym title='Campo Obrigatório'>
                    <font color="#AA0000"size="1" face="Geneva, Arial, Helvetica, san-serif">Bairro</font><br />
                </acronym>
                <acronym title='Campo Obrigatório'>
                    <input class="frm" type="text" name="consumidor_bairro"  id='consumidor_bairro' size="15" maxlength="80" value="<? echo $consumidor_bairro ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro do consumidor.');">
                </acronym>
            </td>
        <? } else { ?>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_bairro'>Bairro</span></font><br />
                <input class="frm" type="text" name="consumidor_bairro"  id='consumidor_bairro' size="15" maxlength="80" value="<? echo $consumidor_bairro ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro do consumidor.');">
            </td>
                <? } ?>
        <?
            $cons_cidade_readonly = '';
            if ($login_fabrica == 30) {
                //solicitação de tirar por Eduardo hd: 11318
                //$cons_cidade_readonly = 'readonly';
                $cons_cidade_readonly = '';
            }
        ?>
        <? if ($login_fabrica == 30) { ?>
            <td nowrap>
                <acronym title='Campo Obrigatório'>
                    <font color="#AA0000" size="1" face="Geneva, Arial, Helvetica, san-serif">Cidade</font><br />
                <acronym title='Campo Obrigatório'>
                </acronym>
                    <input class="frm" type="text" name="consumidor_cidade" id='consumidor_cidade'size="12" maxlength="70" value="<? echo $consumidor_cidade; ?>"  <? echo $cons_cidade_readonly ; ?> <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade do consumidor.');">
                </acronym>
            </td>
        <? } else { ?>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_cidade'>Cidade</span></font><br />
                <input class="frm" type="text" name="consumidor_cidade" id='consumidor_cidade'size="12" maxlength="70" value="<? echo $consumidor_cidade; ?>"  <? echo $cons_cidade_readonly ; ?> <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade do consumidor.');">
            </td>
        <? } ?>

        <? if ($login_fabrica == 30) { ?>
            <td nowrap>
                <acronym title='Campo Obrigatório'>
                    <font color="#AA0000"size="1" face="Geneva, Arial, Helvetica, san-serif">Estado</font><br />
                </acronym>
                <center>
                    <acronym title='Campo Obrigatório'>
                        <select name="consumidor_estado" id='consumidor_estado' size="1" class="frm">
                            <option value=""   <? if (strlen($consumidor_estado) == 0)    echo " selected "; ?>></option>
                            <option value="AC" <? if ($consumidor_estado == "AC") echo " selected "; ?>>AC</option>
                            <option value="AL" <? if ($consumidor_estado == "AL") echo " selected "; ?>>AL</option>
                            <option value="AM" <? if ($consumidor_estado == "AM") echo " selected "; ?>>AM</option>
                            <option value="AP" <? if ($consumidor_estado == "AP") echo " selected "; ?>>AP</option>
                            <option value="BA" <? if ($consumidor_estado == "BA") echo " selected "; ?>>BA</option>
                            <option value="CE" <? if ($consumidor_estado == "CE") echo " selected "; ?>>CE</option>
                            <option value="DF" <? if ($consumidor_estado == "DF") echo " selected "; ?>>DF</option>
                            <option value="ES" <? if ($consumidor_estado == "ES") echo " selected "; ?>>ES</option>
                            <option value="GO" <? if ($consumidor_estado == "GO") echo " selected "; ?>>GO</option>
                            <option value="MA" <? if ($consumidor_estado == "MA") echo " selected "; ?>>MA</option>
                            <option value="MG" <? if ($consumidor_estado == "MG") echo " selected "; ?>>MG</option>
                            <option value="MS" <? if ($consumidor_estado == "MS") echo " selected "; ?>>MS</option>
                            <option value="MT" <? if ($consumidor_estado == "MT") echo " selected "; ?>>MT</option>
                            <option value="PA" <? if ($consumidor_estado == "PA") echo " selected "; ?>>PA</option>
                            <option value="PB" <? if ($consumidor_estado == "PB") echo " selected "; ?>>PB</option>
                            <option value="PE" <? if ($consumidor_estado == "PE") echo " selected "; ?>>PE</option>
                            <option value="PI" <? if ($consumidor_estado == "PI") echo " selected "; ?>>PI</option>
                            <option value="PR" <? if ($consumidor_estado == "PR") echo " selected "; ?>>PR</option>
                            <option value="RJ" <? if ($consumidor_estado == "RJ") echo " selected "; ?>>RJ</option>
                            <option value="RN" <? if ($consumidor_estado == "RN") echo " selected "; ?>>RN</option>
                            <option value="RO" <? if ($consumidor_estado == "RO") echo " selected "; ?>>RO</option>
                            <option value="RR" <? if ($consumidor_estado == "RR") echo " selected "; ?>>RR</option>
                            <option value="RS" <? if ($consumidor_estado == "RS") echo " selected "; ?>>RS</option>
                            <option value="SC" <? if ($consumidor_estado == "SC") echo " selected "; ?>>SC</option>
                            <option value="SE" <? if ($consumidor_estado == "SE") echo " selected "; ?>>SE</option>
                            <option value="SP" <? if ($consumidor_estado == "SP") echo " selected "; ?>>SP</option>
                            <option value="TO" <? if ($consumidor_estado == "TO") echo " selected "; ?>>TO</option>
                        </select>
                    </acronym>
                </center>
            </td><?php

		} else { ?>

            <td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_estado'>Estado</span></font><br />
                <center>
                    <select name="consumidor_estado" id='consumidor_estado' size="1" class="frm">
                        <option value=""   <? if (strlen($consumidor_estado) == 0)    echo " selected "; ?>></option>
                        <option value="AC" <? if ($consumidor_estado == "AC") echo " selected "; ?>>AC</option>
                        <option value="AL" <? if ($consumidor_estado == "AL") echo " selected "; ?>>AL</option>
                        <option value="AM" <? if ($consumidor_estado == "AM") echo " selected "; ?>>AM</option>
                        <option value="AP" <? if ($consumidor_estado == "AP") echo " selected "; ?>>AP</option>
                        <option value="BA" <? if ($consumidor_estado == "BA") echo " selected "; ?>>BA</option>
                        <option value="CE" <? if ($consumidor_estado == "CE") echo " selected "; ?>>CE</option>
                        <option value="DF" <? if ($consumidor_estado == "DF") echo " selected "; ?>>DF</option>
                        <option value="ES" <? if ($consumidor_estado == "ES") echo " selected "; ?>>ES</option>
                        <option value="GO" <? if ($consumidor_estado == "GO") echo " selected "; ?>>GO</option>
                        <option value="MA" <? if ($consumidor_estado == "MA") echo " selected "; ?>>MA</option>
                        <option value="MG" <? if ($consumidor_estado == "MG") echo " selected "; ?>>MG</option>
                        <option value="MS" <? if ($consumidor_estado == "MS") echo " selected "; ?>>MS</option>
                        <option value="MT" <? if ($consumidor_estado == "MT") echo " selected "; ?>>MT</option>
                        <option value="PA" <? if ($consumidor_estado == "PA") echo " selected "; ?>>PA</option>
                        <option value="PB" <? if ($consumidor_estado == "PB") echo " selected "; ?>>PB</option>
                        <option value="PE" <? if ($consumidor_estado == "PE") echo " selected "; ?>>PE</option>
                        <option value="PI" <? if ($consumidor_estado == "PI") echo " selected "; ?>>PI</option>
                        <option value="PR" <? if ($consumidor_estado == "PR") echo " selected "; ?>>PR</option>
                        <option value="RJ" <? if ($consumidor_estado == "RJ") echo " selected "; ?>>RJ</option>
                        <option value="RN" <? if ($consumidor_estado == "RN") echo " selected "; ?>>RN</option>
                        <option value="RO" <? if ($consumidor_estado == "RO") echo " selected "; ?>>RO</option>
                        <option value="RR" <? if ($consumidor_estado == "RR") echo " selected "; ?>>RR</option>
                        <option value="RS" <? if ($consumidor_estado == "RS") echo " selected "; ?>>RS</option>
                        <option value="SC" <? if ($consumidor_estado == "SC") echo " selected "; ?>>SC</option>
                        <option value="SE" <? if ($consumidor_estado == "SE") echo " selected "; ?>>SE</option>
                        <option value="SP" <? if ($consumidor_estado == "SP") echo " selected "; ?>>SP</option>
                        <option value="TO" <? if ($consumidor_estado == "TO") echo " selected "; ?>>TO</option>
                    </select>
                </center>
            </td><?php

		}?>
		</tr>
		<tr>
			<td valign='top' align='left'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
					<span rel='consumidor_email'> Email </span><?php
					echo ($login_fabrica == 15) ? "* " : null; //HD 722524
					$style_email = ($login_fabrica == 15) ? "style='background-color:#FFCCCC'" : null; //HD 722524?>
				</font>
				<br />
				<INPUT TYPE='text' <?=$style_email?> name='consumidor_email' id='consumidor_email' class='frm' value='<? echo $consumidor_email ?>' size='30' maxlength='50' <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço do consumidor.');">
				<? if($login_fabrica== 79) echo "<font color='#FF0000'>*</fotn>"; // HD 78055?>
			</td><?php

			if ($login_fabrica == 43 or $login_fabrica == 52 or $login_fabrica == 74) {?>

				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone Celular</font>
					<br />
					<input class="frm" type="text" rel='fone' name="consumidor_celular" id="consumidor_celular"   size="15" value="<? echo $consumidor_celular ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');" />
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone Comercial</font>
					<br />
					<input class="frm" type="text" rel='fone' name="consumidor_fone_comercial" id="consumidor_fone_comercial"   size="15" value="<? echo $consumidor_fone_comercial ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');" />
				</td><?php

			}

			if ($login_fabrica == 7) { ?>

				<td></td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Distância Cliente (KM)</font>
					<br />
					<input class="frm" type="text" name="deslocamento_km"  id='deslocamento_km' size="14" maxlength="7" value="<? echo $deslocamento_km ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';">
				</td><?php

			}

			if ($login_fabrica == 3 or $login_fabrica == 45 or $login_fabrica == 59 or $login_fabrica == 80) { // HD67164 HD 260273?>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone Celular</font>
					<br />
					<input class="frm" type="text" name="consumidor_celular" size="15" value="<? echo $consumidor_celular ?>" onblur="this.className='frm'; displayText('&nbsp;');" maxlength="14" rel='fone'>
					<span style='font-size:10px;color:#8F8F8F'></span>
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone Comercial</font>
					<br />
					<input class="frm" type="text" name="consumidor_fone_comercial" size="15" value="<? echo $consumidor_fone_comercial ?>" onblur="this.className='frm'; displayText('&nbsp;');" maxlength="14" rel='fone' />
					<span style='font-size:10px;color:#8F8F8F'></span>
				</td><?php
			}?>
		</tr>
    </table><?php

	if ($calcula_km || in_array($login_fabrica, array(3,15))) {

		//--== Calculo de Distância com Google MAPS =========================================
		include "gMapsKeys.inc";

		$sql_posto = "SELECT contato_endereco AS endereco,
							 contato_numero   AS numero  ,
							 contato_bairro   AS bairro  ,
							 contato_cidade   AS cidade  ,
							 contato_estado   AS estado  ,
							 contato_cep      AS cep     ,
							 longitude||','||latitude AS latlng
						FROM tbl_posto_fabrica
						JOIN tbl_posto USING(posto)
						WHERE posto   = $login_posto
						AND   fabrica = $login_fabrica ";

		$res_posto = pg_query($con,$sql_posto);

		//  14/07/2010 MLG - HD 264024 - Retirei o bairro do endereço (confunde o GoogleMaps) e adicionei, se tem, a latitude e longitude.
		if (pg_num_rows($res_posto) > 0) {

			$info_posto     = pg_fetch_assoc($res_posto, 0);
			$endereco_posto = $info_posto['endereco'].', '.$info_posto['numero'].' '.$info_posto['cidade'].' '.$info_posto['estado'];

			if (!is_null($info_posto['latlng'])) $coord_posto = $info_posto[latlng];

			if (strlen($distancia_km) == 0) $distancia_km = 0;

			//hd 40389
			$cep_posto = pg_fetch_result($res_posto, 0, 'cep');
		}

		if (strlen($tipo_atendimento) > 0) {

			$sql  = "SELECT tipo_atendimento,km_google FROM tbl_tipo_atendimento WHERE tipo_atendimento = $tipo_atendimento";
			$resa = pg_query($con,$sql);

			if (pg_num_rows($resa) > 0) {
				$km_google = pg_fetch_result($resa, 0, 'km_google');
			}

		}?>

		<div id="mapa2" style=" width:500px; height:10px;visibility:hidden;position:absolute; ">
			<a href='javascript:escondermapa();'>Fechar Mapa</a>
		</div>

		<br />

		<div id="mapa" style=" width:500px; height:300px;visibility:hidden;position:absolute;border: 1px #FF0000 solid; "></div>

		<script src="http://maps.google.com/maps?file=api&v=2&key=<?=$gAPI_key?>" type="text/javascript"></script>
		<script language="javascript">

			function formatar(src, mask) {

				var i = src.value.length;
				var saida = mask.substring(0,1);
				var texto = mask.substring(i)

				if (texto.substring(0,1) != saida) {
					src.value += texto.substring(0,1);
				}

			}

			var map;
			var total = 0;
			var total_teste = 0;
			var verifica_posto = true;

			function initialize(busca_por) {

				var pt1, pt2, coordPosto;

				if (GBrowserIsCompatible()) {
					// Carrega o Google Maps
					map = new GMap2(document.getElementById("mapa"));
					map.setCenter(new GLatLng(-25.429722,-49.271944), 11);

					// Cria o objeto de roteamento
					var dir = new GDirections(map);

					GEvent.addListener(dir,"load", function() {

						for (var i = 0; i < dir.getNumRoutes(); i++) {

							var route = dir.getRoute(i);
							var dist = route.getDistance();
							var x = dist.meters * 2 / 1000;//IDA E VOLTA
							var y = x.toString().replace(".",",");
							var valor_calculado = parseFloat(x);

							if (valor_calculado == 0 && busca_por != 'endereco') {
								//alert('Nao encontrou');
								//initialize('endereco');
								//return false;
							}

							document.getElementById('distancia_km_conferencia').value = x;
							document.getElementById('distancia_km').value             = y;
							document.getElementById('distancia_km_maps').value        = 'maps';
							document.getElementById('div_mapa_msg').innerHTML         = 'Distância calculada <a href= "javascript:vermapa();">Ver mapa</a>';

						}

						<?php if ($login_fabrica == 15) {  /*HD 275256*/ ?>

							var valor_distancia;
							valor_distancia = $('#distancia_km').val();
							valor_distancia = parseFloat(valor_distancia.replace(",","."));

							if (valor_distancia < 60) {

								$('#btn_calcula_distancia').hide();

							}else{

								$('#btn_calcula_distancia').show();

							}

						<?php }?>


					});

					GEvent.addListener(dir,"error", function() {

						if ((busca_por == 'endereco' || busca_por == '') && total < 3) {
							total++;
							initialize('cep');
						} else if (busca_por == 'cep' && total < 3) {
							total++;
							initialize('endereco');
						} else if (busca_por != 'coords' && total < 3) {
							total++;
							initialize('coords');
						} else {

							if (!verifica_posto) {//Testa endereço de Origem do Posto
								alert("O endereço do Posto não pôde ser localizado no GoogleMaps. \nIsto pode ter acontecido por o endereço ser muito recente ou estar incompleto ou incorreto, para evitar este tipo de problema altere seu endereço.");
							} else if (dir.getStatus().code == G_GEO_UNKNOWN_ADDRESS) {
								alert("O endereço informado não pôde ser localizado no GoogleMaps. \nIsto pode ter acontecido por o endereço ser muito recente ou estar incompleto ou incorreto.");
							} else if (dir.getStatus().code == G_GEO_SERVER_ERROR) {
								alert("Não foi possível localizar um dos endereços.");
							} else if (dir.getStatus().code == G_GEO_MISSING_QUERY) {
								alert("Não foi informado um dos endereços.");
							} else if (dir.getStatus().code == G_GEO_BAD_KEY) {
								alert("Erro de configuração. Contate a Telecontrol. Obrigado.");
							} else if (dir.getStatus().code == G_GEO_BAD_REQUEST) {
								alert("GoogleMaps não entendeu algum dos endereços fornecidos.");
							} else {
								alert("Erro desconhecido ao consultar o GoogleMaps.");
							}

							document.getElementById('distancia_km_conferencia').value = 0;
							document.getElementById('distancia_km').value             = 0;
							document.getElementById('distancia_km_maps').value        = 'maps';
							document.getElementById('div_mapa_msg').innerHTML         = '';

							return false;

						}

						return false;

					});

					//hd 40389 - Endereço do posto
					if (busca_por == 'cep') {
						pt1 = document.getElementById("cep_posto").value;
						pt1 = pt1.replace(/\D/g,'');
					} else if (coordPosto != '' && busca_por == 'coords') {
						pt1 = document.getElementById("coordPosto").value;
					}

					if ((busca_por == 'cep' && pt1.length != 8) || busca_por == 'endereco' || busca_por == undefined || pt1 == undefined || pt1 == '') {
						pt1 = document.getElementById("ponto1").value;
						busca_por = 'endereco';
					}

					//Endereço do consumidor
					var consumidorNumero = document.getElementById("consumidor_numero").value;
					var logradouro       = document.getElementById("consumidor_endereco").value;
					var complemento      = document.getElementById("consumidor_complemento").value;
					var cidade           = document.getElementById("consumidor_cidade").value;
					var estado           = document.getElementById("consumidor_estado").value;

					if (document.getElementById("consumidor_cep").value != '' && busca_por == 'cep') {
						var pt2 = document.getElementById("consumidor_cep").value;
							pt2 = pt2.replace(/\D/g,'');
					} else if (consumidorNumero != '' && logradouro != '' && cidade != '' && estado != '' && (busca_por == 'endereco' || busca_por == '')) {
						var pt2 = logradouro + ', ' + consumidorNumero + ' ' + complemento + ', ' + cidade + ', ' + estado;
					} else {
						alert('Você deve preencher todos estes dados: Logradouro, número, bairro, cidade, estado');
						document.getElementById('distancia_km_conferencia').value = 0;
						document.getElementById('distancia_km').value             = 0;
						document.getElementById('distancia_km_maps').value        = 'maps';
						document.getElementById('div_mapa_msg').innerHTML         = '';
						return false;
					}

					// Carrega os pontos dados os endereços
					if (busca_por == 'cep' && pt1.length == 8) {
						pt1 += ', BR';
					}

					if (pt1 != '' && pt2 != '') {
						// O evento load do GDirections é executado quando chega o resultado do geocoding.
						dir.load("from: " + pt1 + " to: " + pt2 + ', BR', {locale:"pt-br", getSteps:true});
					}

				}

			}

			//Função para testar o endereço de Origem do Posto
			function testaEndOrigem(busca_por) {// HD 268504

				var pt1, pt2, coordPosto;

				if (GBrowserIsCompatible()) {
					// Carrega o Google Maps
					map2 = new GMap2(document.getElementById("mapa"));
					map2.setCenter(new GLatLng(-25.429722,-49.271944), 11);

					// Cria o objeto de roteamento
					var dirTest = new GDirections(map2);

					GEvent.addListener(dirTest,"load", function() {

						for (var i = 0; i < dirTest.getNumRoutes(); i++) {

							var route = dirTest.getRoute(i);
							var dist = route.getDistance();
							var x = dist.meters * 2 / 1000;//IDA E VOLTA
							var y = x.toString().replace(".",",");
							var valor_calculado = parseFloat(x);

							if (x != '' && y != '') {
								return true;
							}

						}

						return true;

					});

					GEvent.addListener(dirTest,"error", function() {

						if ((busca_por == 'endereco' || busca_por == '') && total_teste < 3) {
							total_teste++;
							testaEndOrigem('cep');
						} else if (busca_por == 'cep' && total_teste < 3) {
							total_teste++;
							testaEndOrigem('endereco');
						} else if (busca_por != 'coords' && total_teste < 3) {
							total_teste++;
							testaEndOrigem('coords');
						} else {

							if (dirTest.getStatus().code == G_GEO_UNKNOWN_ADDRESS) {
								return false;
							} else if (dirTest.getStatus().code == G_GEO_SERVER_ERROR) {
								return false;
							} else if (dirTest.getStatus().code == G_GEO_MISSING_QUERY) {
								return false;
							} else if (dirTest.getStatus().code == G_GEO_BAD_KEY) {
								return false;
							} else if (dirTest.getStatus().code == G_GEO_BAD_REQUEST) {
								return false;
							} else {
								return false;
							}

						}

					});

					//hd 40389 - Endereço do posto
					if (busca_por == 'cep') {
						pt1 = document.getElementById("cep_posto").value;
						pt1 = pt1.replace(/\D/g,'');
					} else if (coordPosto != '' && busca_por == 'coords') {
						pt1 = document.getElementById("coordPosto").value;
					}

					if ((busca_por == 'cep' && pt1.length != 8) || busca_por == 'endereco' || busca_por == undefined || pt1 == undefined || pt1 == '') {
						pt1 = document.getElementById("ponto1").value;
						busca_por = 'endereco';
					}

					if (cep != '' && busca_por == 'cep') {
						var pt2 = cep;
							pt2 = pt2.replace(/\D/g,'');
					} else if (consumidorNumero != '' && logradouro != '' && cidade != '' && estado != '' && (busca_por == 'endereco' || busca_por == '')) {
						var pt2 = logradouro + ', ' + consumidorNumero + ' ' + complemento + ', ' + cidade + ', ' + estado;
					}

					// Carrega os pontos dados os endereços
					if (busca_por == 'cep' && pt1.length == 8) {
						pt1 += ', BR';
					}

					if (pt1 != '' && pt2 != '') {
						// O evento load do GDirections é executado quando chega o resultado do geocoding.
						dirTest.load("from: " + pt1 + " to: " + pt2 + ', BR', {locale:"pt-br", getSteps:true});
					}

				}

			}

			function compara(campo1,campo2){

				var num1 = campo1.value.replace(".",",");
				var num2 = campo2.value.replace(".",",");

				if (num1 != num2) {
					document.getElementById('div_mapa_msg').style.visibility = "visible";
					document.getElementById('div_mapa_msg').innerHTML = 'A distância percorrida pelo técnico estará sujeito a auditoria';
				} else {
					document.getElementById('div_mapa_msg').style.visibility = "visible";
					document.getElementById('div_mapa_msg').innerHTML='Distância calculada <a href= "javascript:vermapa();">Ver mapa</a>';
				}

			}

			function vermapa() {
				document.getElementById("mapa").style.visibility  = "visible";
				document.getElementById("mapa2").style.visibility = "visible";
			}

			function escondermapa() {
				document.getElementById("mapa").style.visibility  = "hidden";
				document.getElementById("mapa2").style.visibility = "hidden";
			}

		</script>

		<div id='div_mapa' style='background:#efefef;border:#999999 1px solid;font-size:10px;padding:5px;<?if($km_google<>'t' or ( $login_fabrica <> 52 && $login_fabrica <> 35) ) echo "visibility:hidden;position:absolute;";?>' >

			<b>Para Calcular a distância percorrida pelo técnico para execução do serviço(ida e volta):<br />
			Preencha todos os campos de endereço acima ou preencha o campo de distância</b>
			<br />
			<br />

			<input type="hidden" id="ponto1" name="ponto1" value="<?=$endereco_posto?>" />
			<input type='hidden' id='coordPosto' value='<?=$coord_posto?>' />
			<input type="hidden" id="cep_posto"value="<?=$cep_posto?>" />
			<input type="hidden" id="distancia_km_maps"  value="" />
			<input type='hidden' name='distancia_km_conferencia' id='distancia_km_conferencia' value='<?=$distancia_km_conferencia?>'>

			Distância: <input type='text' name='distancia_km' id='distancia_km' value='<?=$distancia_km?>' size='8' onchange="javascript:compara(distancia_km,distancia_km_conferencia)"> Km
			<input  type="button" id='btn_calcula_distancia' onclick="initialize('')" value="Calcular Distância" size='5' ><div id='div_mapa_msg' style='color:#FF0000'></div>
			<br /><B>Endereço do posto:</b> <u><?=$endereco_posto?></u><br />&nbsp;

		</div><?php

	}?>

<hr /><?php

if ($login_fabrica == 50) {?>
    <div id='revenda_fixo' style='display:none; background:#efefef; border:#999999 1px solid;'>
        <table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
            <tr valign='top'>
                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
                    <br />
                    <input class="frm" type="text" name="txt_revenda_nome" id="txt_revenda_nome" size="50" maxlength="50" value="" readonly onkeyup="somenteMaiusculaSemAcento(this)">
                </td>
                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
                    <br />
                    <input class="frm" type="text" name="txt_revenda_cnpj" id="txt_revenda_cnpj" size="20" maxlength="18" id="txt_revenda_cnpj" value="" readonly>
                </td>
                <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
                <br />
                <input class="frm" type="text" name="txt_revenda_fone" id="txt_revenda_fone" size="15" maxlength="15"  rel='fone' value="" readonly>
                </td>
                <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
                <br />
                <input class="frm" type="text" name="txt_revenda_cep" id="txt_revenda_cep"  size="10" maxlength="10" value="" readonly>
                </td>
            </tr>
        </table>
        <table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
            <tr valign='top'>
                <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Endereço</font>
                <br />
                    <input class="frm" type="text" name="txt_revenda_endereco" id="txt_revenda_endereco" size="30" maxlength="50" value="" readonly>
                </td>
                <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número</font>
                <br />
                    <input class="frm" type="text" name="txt_revenda_numero" id="txt_revenda_numero" size="5" maxlength="5" value="" readonly>
                </td>
                <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Complemento</font>
                <br />
                    <input class="frm" type="text" name="txt_revenda_complemento" id="txt_revenda_complemento" size="10" maxlength="10" value="" readonly>
                </td>
                <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Bairro</font>
                <br />
                    <input class="frm" type="text" name="txt_revenda_bairro" id="txt_revenda_bairro" size="10" maxlength="20" value="" readonly>
                </td>
                <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Cidade</font>
                <br />
                    <input class="frm" type="text" name="txt_revenda_cidade" id="txt_revenda_cidade" size="12" maxlength="10" value="" readonly>
                </td>
                <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Estado</font>
                <br />
                    <input class="frm" type="text" name="txt_revenda_estado" id="txt_revenda_estado" size="2" maxlength="2" value="" readonly>
                </td>
            </tr>
        </table>

        <table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
            <tr valign='top'>
                <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Cód. EAN</font>
                <br />
                    <input class="frm" type="text" name="txt_cod_ean" id="txt_cod_ean" size="30" maxlength="50" value="" readonly>
                </td>
                <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Data do Faturamento</font>
                <br />
                    <input class="frm" type="text" name="txt_data_venda" id="txt_data_venda" size="12" maxlength="10" value="" readonly>
                </td>
                <td colspan = '3'>&nbsp; <br />&nbsp;
                </td>
            </tr>
            </table>
            <table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
            <tr valign='top'>
                <td><font size="2" face="Geneva, Arial, Helvetica, san-serif" color='red'>AS INFORMAÇÕES AUTOMÁTICAS QUE ESTÃO ACIMA SÃO AS MESMAS DA NOTA FISCAL DO CONSUMIDOR?</font>
                </td>
                <td>
                    <input class="frm" type="radio" name="nf_confirma_num_serie" onclick="fnc_num_serie_confirma('sim');" value="sim"> Sim
                </td>
                <td>
                    <input class="frm" type="radio" name="nf_confirma_num_serie" onclick="fnc_num_serie_confirma('nao');" value="nao"> Não
                </td>

            </tr>
        </table>
    </div><?php
}?>
<?
	//HD 234135
	if ($usa_revenda_fabrica) {
		echo '<div width="750" align="center" id="revenda_fabrica_msg"
			   style="font:14px Arial;background-color:#7092BE;padding:5px;margin-top:10px;margin-bottom:10px;color:white">
			   	Digite o CNPJ da revenda com 14 dígitos e clique na lupa
			</div>';
	}
?>
   <?if ($login_fabrica <> 96) { ?>
	<table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
        <tr valign='top'>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_nome'>Nome Revenda</span></font>
                <br />
                <input class="frm" type="text" name="revenda_nome" id="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)"
                <? if($login_fabrica==50){
                    ?>onChange="javascript: this.value=this.value.toUpperCase();"
                <?}?>
                onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o nome da REVENDA onde foi adquirido o produto.');">&nbsp;
                <? if($login_fabrica!=24 && !$usa_revenda_fabrica){ //HD 234135?>
                    <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor: pointer'>
                <? } ?>
            </td>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_cnpj'>CNPJ Revenda</span></font>
                <br />
		<? //HD 234135
		if ($usa_revenda_fabrica) {
		?>
			<input type='hidden' name='revenda_cnpj' id='revenda_cnpj' value='<? echo $revenda_cnpj; ?>'>
			<input type='hidden' name='revenda_fabrica_status' id='revenda_fabrica_status' value='<? echo $revenda_fabrica_status; ?>'>
	                <input class="frm" type="text" name="revenda_cnpj_pesquisa" size="16" maxlength="14" id="revenda_cnpj_pesquisa" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;'); fnc_pesquisa_revenda_fabrica_onblur();" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número no Cadastro Nacional de Pessoa Jurídica.');">&nbsp;
			<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda_fabrica();' style='cursor: pointer'>
		<?
		}
		else {
		?>
	                <input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" id="revenda_cnpj" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número no Cadastro Nacional de Pessoa Jurídica.'); " onKeyUp="formata_cnpj(this.value, 'frm_os')">&nbsp;
			<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer'>
		<?
		}
		?>
            </td>
            <td>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_fone'>Fone</span></font>
            <br />
            <input class="frm" type="text" name="revenda_fone"  id="revenda_fone"  size="15" maxlength="15" rel='fone' value="<? echo $revenda_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
            </td>
            <td>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_cep'>Cep</span></font>
            <br />
		<input class="frm" type="text" name="revenda_cep" id="revenda_cep"  size="10" maxlength="10" value="<? echo $revenda_cep ?>"
		<?php if($login_fabrica != 91){?>
		onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.revenda_endereco, document.frm_os.revenda_bairro, document.frm_os.revenda_cidade, document.frm_os.revenda_estado) ;"
		<?php }?>
		onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP da revenda.');"
		/>
	    <?php if($login_fabrica == 91){?>
		<input type='button' value='Pesquisar' class='frm' onclick="if(document.frm_os.revenda_cep.value.length < 8) {alert('Informe um CEP válido!');}else{buscaCEP(document.frm_os.revenda_cep.value, document.frm_os.revenda_endereco, document.frm_os.revenda_bairro, document.frm_os.revenda_cidade, document.frm_os.revenda_estado) ;}" style='cursor: pointer' />
	    <?php }?>
            </td>
        </tr>
        </table>
        <table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
        <tr valign='top'>
        <td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_endereco'>Endereço</span></font>
        <br />
        <input class="frm" type="text" name="revenda_endereco" id="revenda_endereco" size="30" maxlength="60" value="<? echo $revenda_endereco ?>"  <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço da Revenda.');">
        </td>

        <td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_numero'>Número</span></font>
        <br />
        <input class="frm" type="text" name="revenda_numero" id="revenda_numero"  size="5" maxlength="10" value="<? echo $revenda_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço da revenda.');">
        </td>

        <td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_complemento'>Complemento</span></font>
        <br />
        <input class="frm" type="text" name="revenda_complemento" id="revenda_complemento" size="15" maxlength="30" value="<? echo $revenda_complemento ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço da revenda.');">
        </td>
        <td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_bairro'>Bairro</span></font>
        <br />
        <input class="frm" type="text" name="revenda_bairro" id="revenda_bairro" size="13" maxlength="30" value="<? echo $revenda_bairro ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro da revenda.');">
        </td>
        <td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_cidade'>Cidade</span></font>
        <br /><?php
        $rev_cidade_readonly = '';
        if ($login_fabrica == 30) {
            $rev_cidade_readonly = 'readonly';
        }?>
        <input class="frm" type="text" name="revenda_cidade" id="revenda_cidade"  size="15" maxlength="50" value="<? echo $revenda_cidade ?>" <? echo $rev_cidade_readonly ; ?> <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade da revenda.');">
        </td>
        <td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_estado'>Estado</span></font>
        <br />
        <select name="revenda_estado" id="revenda_estado" size="1" class="frm">
        <option value=""   <? if (strlen($revenda_estado) == 0)    echo " selected "; ?>></option>
            <option value="AC" <? if ($revenda_estado == "AC") echo " selected "; ?>>AC</option>
            <option value="AL" <? if ($revenda_estado == "AL") echo " selected "; ?>>AL</option>
            <option value="AM" <? if ($revenda_estado == "AM") echo " selected "; ?>>AM</option>
            <option value="AP" <? if ($revenda_estado == "AP") echo " selected "; ?>>AP</option>
            <option value="BA" <? if ($revenda_estado == "BA") echo " selected "; ?>>BA</option>
            <option value="CE" <? if ($revenda_estado == "CE") echo " selected "; ?>>CE</option>
            <option value="DF" <? if ($revenda_estado == "DF") echo " selected "; ?>>DF</option>
            <option value="ES" <? if ($revenda_estado == "ES") echo " selected "; ?>>ES</option>
            <option value="GO" <? if ($revenda_estado == "GO") echo " selected "; ?>>GO</option>
            <option value="MA" <? if ($revenda_estado == "MA") echo " selected "; ?>>MA</option>
            <option value="MG" <? if ($revenda_estado == "MG") echo " selected "; ?>>MG</option>
            <option value="MS" <? if ($revenda_estado == "MS") echo " selected "; ?>>MS</option>
            <option value="MT" <? if ($revenda_estado == "MT") echo " selected "; ?>>MT</option>
            <option value="PA" <? if ($revenda_estado == "PA") echo " selected "; ?>>PA</option>
            <option value="PB" <? if ($revenda_estado == "PB") echo " selected "; ?>>PB</option>
            <option value="PE" <? if ($revenda_estado == "PE") echo " selected "; ?>>PE</option>
            <option value="PI" <? if ($revenda_estado == "PI") echo " selected "; ?>>PI</option>
            <option value="PR" <? if ($revenda_estado == "PR") echo " selected "; ?>>PR</option>
            <option value="RJ" <? if ($revenda_estado == "RJ") echo " selected "; ?>>RJ</option>
            <option value="RN" <? if ($revenda_estado == "RN") echo " selected "; ?>>RN</option>
            <option value="RO" <? if ($revenda_estado == "RO") echo " selected "; ?>>RO</option>
            <option value="RR" <? if ($revenda_estado == "RR") echo " selected "; ?>>RR</option>
            <option value="RS" <? if ($revenda_estado == "RS") echo " selected "; ?>>RS</option>
            <option value="SC" <? if ($revenda_estado == "SC") echo " selected "; ?>>SC</option>
            <option value="SE" <? if ($revenda_estado == "SE") echo " selected "; ?>>SE</option>
            <option value="SP" <? if ($revenda_estado == "SP") echo " selected "; ?>>SP</option>
            <option value="TO" <? if ($revenda_estado == "TO") echo " selected "; ?>>TO</option>
        </select>
        </td>
        </tr>
        </table>
    <?}?>
						<!--
                        <input type='hidden' name = 'revenda_fone'>
                        <input type='hidden' name = 'revenda_cep'>
                        <input type='hidden' name = 'revenda_endereco'>
                        <input type='hidden' name = 'revenda_numero'>
                        <input type='hidden' name = 'revenda_complemento'>
                        <input type='hidden' name = 'revenda_bairro'>
                        <input type='hidden' name = 'revenda_cidade'>
                        <input type='hidden' name = 'revenda_estado'>
                        -->
                        <input type='hidden' name = 'revenda_email'>
        <?
        if ($login_fabrica == 7) {
#            echo " -->";
        }
        ?>

        <?if ($login_fabrica <> 96) { ?>
		<hr>
		<?}?>
        <table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
        <tr>
            <?
            if ($login_fabrica <> 19) { // HD 717347
                echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
                echo "Consumidor</font>&nbsp;";
                echo "<input type='radio' name='consumidor_revenda' value='C'";
                if ($consumidor_revenda == 'C' or in_array($login_fabrica, array(24,30,40,72,74) ) or $login_fabrica > 80)
                    echo "checked";
                echo "></td>";
                echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>ou</font></td>";
                echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Revenda</font>&nbsp;";
                echo "<input type='radio' name='consumidor_revenda' value='R' ";
                //MALLORY não quer que cadastre OS de revenda por está tela
                if ($login_fabrica == 72)//HD 249034
                    echo ' disabled="disabled" ';
                if ($consumidor_revenda == 'R')
                    echo ' checked="checked"';
                echo '>&nbsp;&nbsp;</td>';
            } else {
                echo "<input type='hidden' name='consumidor_revenda' value='C'>";
            }?>
            <td><?php
                if ($login_fabrica == 11) {
                    //NAO IMPRIME NADA
                    echo "<td width='440px'>&nbsp;";
                } else {
                    echo "<td>";
                    echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
                    echo "<span rel='aparencia_produto'>Aparência do Produto</span>";
                    echo "</font>";
                }?>
                <br />
                <? if ($login_fabrica == 20) {
                    echo "<select name='aparencia_produto' size='1'>";
                    echo "<option value=''></option>";

                    echo "<option value='NEW' ";
                    if ($aparencia_produto == "NEW") echo " selected ";
                    echo "> Bom Estado </option>";

                    echo "<option value='USL' ";
                    if ($aparencia_produto == "USL") echo " selected ";
                    echo "> Uso intenso </option>";

                    echo "<option value='USN' ";
                    if ($aparencia_produto == "USN") echo " selected ";
                    echo "> Uso Normal </option>";

                    echo "<option value='USH' ";
                    if ($aparencia_produto == "USH") echo " selected ";
                    echo "> Uso Pesado </option>";

                    echo "<option value='ABU' ";
                    if ($aparencia_produto == "ABU") echo " selected ";
                    echo "> Uso Abusivo </option>";

                    echo "<option value='ORI' ";
                    if ($aparencia_produto == "ORI") echo " selected ";
                    echo "> Original, sem uso </option>";

                    echo "<option value='PCK' ";
                    if ($aparencia_produto == "PCK") echo " selected ";
                    echo "> Embalagem </option>";

                    echo "</select>";
                } else {
                    if ($login_fabrica == 11) {
                        echo "<input type='hidden' type='text' name='aparencia_produto' value='$aparencia_produto'>";
                    } else if ($login_fabrica == 50) {
                        echo "<input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onChange=\"javascript: this.value=this.value.toUpperCase();\" onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\">";
                    } else {
                        echo "<input class='frm' type='text' id='aparencia_produto' name='aparencia_produto' size='30' value='$aparencia_produto' if($login_fabrica==50){onChange=\"javascript: this.value=this.value.toUpperCase();\"} onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\">";
                    }
                }?>
            </td><?php
            if ($login_fabrica <> 1) {
                if ($login_fabrica == 11) {
                    //nao mostra acessórios
                } else {?>
                    <td>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='acessorios'>Acessórios</span></font>
                        <br />
                        <input class="frm" type="text" name="acessorios" id="acessorios" size="30" value="<? echo $acessorios ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com os acessórios deixados junto ao produto.');">
                    </td><?php
                }
            }
            if ($login_fabrica == 1) {//OR $login_fabrica == 3
                //conforme e-mail de Samuel (sirlei) a partir de 21/08 nao tem troca de produto para britania, somente ressarcimento financeiro?>
                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Troca faturada</font><br />
                    <input class="frm" type="checkbox" name="troca_faturada" value="t"<? if ($troca_faturada == 't') echo " checked";?>>
                </td><?php
            }?>
        </tr>
    </table>

        <? if ($login_fabrica == 5 AND 1==2) { //desabilitei pois tem lah em cima ?>

        <hr>

        <center>
        <font size="1" face="Geneva, Arial, Helvetica, san-serif">
        Descrição do Defeito Reclamado pelo Consumidor
        </font>
        <br />
        <textarea class='frm' name='defeito_reclamado_descricao' cols='70' rows='5'><? echo $xdefeito_reclamado_descricao ?></textarea>


        <? }  # Final do IF do Defeito_Reclamado_Descricao ?>


        <?
        if ($login_fabrica == 30 OR $login_fabrica == 43  OR $login_fabrica == 3) {
            ?>
            <table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
            <tr>

                <td align='center' >
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
                    <br />
					<?php if($login_fabrica == 3){?>
                    <input class="frm" type="text" name="obs" id="obs" size="50" value="<? echo $defeito_reclamado_descricao2;?>">
					<?php }else{?>
					<input class="frm" type="text" name="obs" id="obs" size="50" value="<? echo $obs ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;ObservaçÕes e dados adicionais desta OS.');">
					<?php } ?>
                </td>
            </tr>
            </table>
            <?
        }
        if ($login_fabrica == 7 ) {  ?>
        <hr>
        <table width="750" border="0" cellspacing="5" cellpadding="0">
        <tr>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Chamado aberto por</font>
                <br />
                <input class="frm" type="text" name="quem_abriu_chamado" size="20" maxlength="30" value="<? echo $quem_abriu_chamado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Nome do funcionário do cliente que abriu este chamado.');">
            </td>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
                <br />
                <input class="frm" type="text" name="obs" size="50" value="<? echo $obs ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;ObservaçÕes e dados adicionais desta OS.');">
            </td>
        </tr>
        </table>

        <?PHP
        $sql = "SELECT tipo_posto
                FROM tbl_posto_fabrica
                WHERE fabrica = $login_fabrica AND posto = $login_posto";
        $res = pg_query($con,$sql);
        $tipo_posto = pg_fetch_result($res,0,tipo_posto);
        if ($tipo_posto == 214 OR $tipo_posto == 215 OR $tipo_posto == 7) {

            if ($tipo_posto != 214 AND $tipo_posto != 215){
                $valores_somente_leitura = 't';
            }
        ?>
        <table width="750" border="0" cellspacing="5" cellpadding="0">
        <tr>
            <td><font size="2" face="Geneva, Arial, Helvetica, san-serif">
                    Valores Combinados na Abertura da OS
                </font>
            </td>
        </tr>
        <tr style='font-size:10px' valign='top'>
            <td valign='top'>
                <fieldset class='valores' style='height:140px;'>
                <legend>Deslocamento</legend>
                    <div>
                    <?    /*HD: 55895*/
                    if ($login_fabrica <> 7) {?>
                        <label for="cobrar_deslocamento">Isento:</label>
                        <input type='radio' name='cobrar_deslocamento' value='isento' onClick='atualizaCobraDeslocamento(this)' <? if (strtolower($cobrar_deslocamento) == 'isento') echo "checked";?>>
                        <br />
                    <?}?>
                    <label for="cobrar_deslocamento">Por Km:</label>
                    <input type='radio' name='cobrar_deslocamento' value='valor_por_km' <? if ($cobrar_deslocamento == 'valor_por_km') echo " checked " ?> onClick='atualizaCobraDeslocamento(this)'>
                    <br />
                    <label for="cobrar_deslocamento">Taxa de Visita:</label>
                    <input type='radio' name='cobrar_deslocamento' value='taxa_visita' <? if ($cobrar_deslocamento == 'taxa_visita') echo " checked " ?> onClick='atualizaCobraDeslocamento(this)'>
                    <br />
                    </div>

                    <div name='div_taxa_visita' <? if ($cobrar_deslocamento != 'taxa_visita') echo " style='display:none' "?>>
                        <label for="taxa_visita">Valor:</label>
                        <input type='text' name='taxa_visita' value='<? echo number_format($taxa_visita ,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                        <br />
                    </div>

                    <div <? if ($cobrar_deslocamento != 'valor_por_km' or strlen($cobrar_deslocamento)==0) echo " style='display:none' " ?> name='div_valor_por_km'>
                        <label for="veiculo">Carro:</label>
                        <input type='radio' name='veiculo' value='carro' onClick='atualizaValorKM(this)' <? if (strtolower($veiculo) != 'caminhao') echo "checked";?>>
                        <input type='text' name='valor_por_km_carro' value='<? echo number_format($valor_por_km_carro,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                        <br />
                        <label for="veiculo">Caminhão:</label>
                        <input type='radio' name='veiculo' value='caminhao' onClick='atualizaValorKM(this)' <? if (strtolower($veiculo) == 'caminhao') echo "checked";?> >
                        <input type='text' name='valor_por_km_caminhao' class='frm' value='<? echo number_format($valor_por_km_caminhao,2,',','.') ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                        <input type='hidden' name='valor_por_km' value='<? echo $valor_por_km ?>'>
                    </div>

<?if  (1==2){ #HD 32483 ?>
                    <div <? if ($cobrar_deslocamento == 'isento' OR strlen($cobrar_deslocamento)==0) echo " style='display:none' " ?> name='div_desconto_deslocamento'>
                        <label>Desconto:</label>
                        <input type='text' name='desconto_deslocamento' value="<? echo $desconto_deslocamento ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>> %
                    </div>
<?}?>
                </fieldset>
            </td>
            <td>
                <fieldset class='valores' style='height:140px;'>
                    <legend>Mão de Obra</legend>
                    <div>
                    <label for="cobrar_hora_diaria">Diária:</label>
                    <input type='radio' name='cobrar_hora_diaria' value='diaria' onClick='atualizaCobraHoraDiaria(this)' <? if (strtolower($cobrar_hora_diaria) == 'diaria') echo "checked";?>>
                    <br />
                    <label for="cobrar_hora_diaria">Hora Técnica:</label>
                    <input type='radio' name='cobrar_hora_diaria' value='hora' onClick='atualizaCobraHoraDiaria(this)' <? if (strtolower($cobrar_hora_diaria) == 'hora') echo "checked";?>>
                    <br />
                    </div>
                    <div <? if ($cobrar_hora_diaria != 'hora') echo " style='display:none' " ?> name='div_hora'>
                        <label>Valor:</label>
                        <input type='text' name='hora_tecnica' value='<? echo number_format($hora_tecnica,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                        <br />
<?/*                        <!--<br />
                        <label>Desconto:</label>
                        <input type='text' name='desconto_hora_tecnica' value="<? echo $desconto_hora_tecnica ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %-->
*/?>
                    </div>
                    <div <? if ($cobrar_hora_diaria != 'diaria') echo " style='display:none' " ?> name='div_diaria'>
                        <label>Valor:</label>
                        <input type='text' name='valor_diaria' value="<? echo number_format($valor_diaria,2,',','.') ?>" class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                        <br />
<?/*                        <!--                        <br />
                        <label>Desconto:</label>
                        <input type='text' name='desconto_diaria' value="<? echo $desconto_diaria ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
-->
*/?>
                    </div>
                </fieldset>
            </td>
            <td>
                <fieldset class='valores' style='height:140px;'>
                    <legend>Outros Serviços</legend>
                    <div>
                        <label>Regulagem:</label>
                        <input type="checkbox" name="cobrar_regulagem" value="t" <? if ($cobrar_regulagem=='t') echo "checked" ?>>
                        <br />
                        <label>Valor:</label>
                        <input type="text" name="regulagem_peso_padrao" value="<? echo number_format($regulagem_peso_padrao,2,',','.') ?>"  class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                        <br />
<?/*                        <!--                        <br />
                        <label>Desconto:</label>
                        <input type='text' name='desconto_regulagem' value="<? echo $desconto_regulagem ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
                        <br />
-->
*/?>
                        <br />
                        <label>Certificado:</label>
                        <input type="checkbox" name="cobrar_certificado" value="t" <? if ($cobrar_certificado=='t') echo "checked" ?>>
                        <br />
                        <label>Valor:</label>
                        <input type="text" name="certificado_conformidade" value="<? echo number_format($certificado_conformidade,2,',','.') ?>"  class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                        <br />
<?/*                        <!--                        <br />
                        <label>Desconto:</label>
                        <input type='text' name='desconto_certificado' value="<? echo $desconto_certificado ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
-->
*/?>
                        </div>
                </fieldset>
            </td>
        </tr>
        <tr  style='font-size:10px'>
            <td class="menu_top" colspan='3'>

                <table border="0" cellspacing="10" cellpadding="0">
                <tr style='font-size:10px'>
                    <td class="table_line2">% Desconto Peças</td>
                    <td class="table_line2" >Condição de Pagamento</td>
                </tr>
                <tr style='font-size:10px'>
                    <td class="table_line2">
                        <input type='text' name='desconto_peca' class='frm' value='<?=$desconto_peca?>' size='15' maxlength='5' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                    </td>
                    <td class="table_line2" >
						<select name='condicao' class='frm'>
							<option value=''></option>
                        <?
                        $sql = " SELECT condicao,
                                        codigo_condicao,
                                        descricao
                                FROM tbl_condicao
                                WHERE fabrica = $login_fabrica
                                    AND visivel is true";
                        $res = pg_query ($con,$sql) ;



                        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
							list($cond_cond, $cond_codigo, $cond_desc) = pg_fetch_row($res, $i);
							$sel = ($cond_cond == $condicao) ? ' selected' : '';
                            echo "<option value='$cond_cond'$sel>$cond_codigo - $cond_desc</option>\n";
                        }	?>
						</select>&nbsp;
                    </td>
                </tr>
                </table>

            </td>
        </tr>
        </table>

        <?
            }
        }
        ?>

    </td>

    <td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>

<?
if (strlen($os)==0 and
	strlen($msg_erro)==0 and
	($linhainf == 't' or in_array($login_fabrica, array(2, 24, 40, 46, 52, 59,30, 74,80, 81, 85, 90, 91, 96, 50)))) { //89, ?>
<script  type="text/javascript">
    verificaPreOS();
    //Verifica Endereço Posto
    verifica_posto = testaEndOrigem('');
</script>
<?}?>

<hr width='700'>

<table width="100%" border="0" cellspacing="5" cellpadding="0">
<tr>

    <td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
        <input type="hidden" name="btn_acao" value="">
        <input type="hidden" name="qtde_etiquetas" value="">
        <?
//  MLG - 19/11/2009 - HD 171045 - Para inserir imagem da NF da NKS...
//  MLG - 19/11/2009 - HD 171045 - Para inserir imagem da NF da NKS...
//	MLG - 26/10/2010 - Mudei o sistema:
//  MLG - 06/12/2010 - HD 321132 - O anexo de imagens à OS está 'unificado' em um include que serve
//								   para todas as telas, admin e posto.
        if ($anexaNotaFiscal) echo $inputNotaFiscal;

        if ($login_fabrica != 1) {
        echo "<input type='checkbox' name='imprimir_os' id='imprimir_os' value='imprimir' ";
        if($login_fabrica == 30) { // HD  56871
            echo " CHECKED ";
            echo " onClick='javascript: alert(\"É obrigatório a impressão de OS\"); return false;'";
        }
        echo "> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Imprimir OS</font>";
        }
        ?>
        <? if ($login_fabrica == 1) { ?>
        <img src='imagens/btn_continuar.gif' onclick="javascript:

        if (document.frm_os.btn_acao.value == '' ) {
            document.frm_os.btn_acao.value='continuar' ;
            document.frm_os.submit()
        } else {
            alert ('Não clique no botão voltar do navegador, utilize somente os botões da tela')
        }"

        ALT="Continuar com Ordem de Serviço" border='0' style='cursor: hand;'>
        <? }elseif($login_fabrica==51){ ?>
            <img src='imagens/btn_continuar.gif' onclick="javascript:valida_consumidor_gama();" ALT="Continuar com Ordem de Serviço" border='0' style='cursor: pointer'>
	<?}else { ?>
        <img src='imagens/btn_continuar.gif' onclick="
			<?if(in_array($login_fabrica,$fabricas_validam_campos_telecontrol) || $login_fabrica > 99){?>
				func_submit_os();"
			<?}else{?>
				javascript: if (document.frm_os.btn_acao.value == '' ) {
								document.frm_os.btn_acao.value='continuar' ;

								<? if ($login_fabrica==11) {?>
								if (document.frm_os.imprimir_os.checked == true){
									var qtde_aux = prompt('Quantas etiquetas deseja imprimir? Ou tecle ENTER para imprimir a quantidade padrão','');
									if ( (qtde_aux=='') || (qtde_aux==null) || qtde_aux.length==0 ){
										document.frm_os.qtde_etiquetas.value = '';
									}else{
										document.frm_os.qtde_etiquetas.value = qtde_aux;
									}
								}
								<?}?>
								document.frm_os.submit()
							}"
			  name="sem_submit" class="verifica_servidor"
			<?}?>
			ALT="Continuar com Ordem de Serviço" border='0' style='cursor: pointer'>
        <?}?>
    </td>
</tr>
</table>

</form>
<? if($login_fabrica == 3 && !empty($produto_referencia) ) { ?>
	<script> verifica_split('<?php echo $produto_referencia ?>');</script>
<? } else if ($login_fabrica == 42 && !empty($msg_erro)) {
	echo "<script>verifica_atendimento()</script>";
}?>
<p>

<? include "rodape.php";?>
