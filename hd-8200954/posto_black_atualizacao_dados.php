<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';

$a_linhas_at = array(
    'A' => 'Automotiva',
    'C' => 'Compressores',
    'E' => 'Eletrodomésticos',
    'F' => 'Fechaduras',
    'B' => 'Ferramentas Elétricas',
    'P' => 'Ferramentas Pneumáticas',
    'D' => 'Ferramentas Profissionais',
    'GR' => 'Geradores',
    'LS' => 'Laser',
    'LV' => 'Lavadoras de Pressão',
    'M' => 'Metais Sanitários',
    'GS' => 'Motores à Gasolina' 
);

function pg_array_quote($arr, $valType = 'string') {
    if (!is_array($arr)) return 'NULL';

    if (count($arr) == 0) return '\'{}\'';
    $ret = '$${';
    switch ($valType) {
        case 'str':
        case 'string':
        case 'text':
            foreach($arr as $item) {
                if      (is_bool($item)) $item = ($item) ? 'TRUE' : 'FALSE';
                elseif  (is_null($item) or strtoupper($item) == 'NULL') $item = 'NULL';
                elseif  (is_string($item) and strpos($item, ',') !== false) $item = "\"$item\"";
                $quoted[] = $item;
            }
            $ret .= implode(',',$quoted) . '}$$';
            return $ret;
        break;
        case 'numeric':
        case 'int':
        case 'integer':
        case 'float':
        case 'boolean':
        case 'bool':
            foreach($arr as $item) {
                if (is_string($item) and
                    ($item == 't' or $item == 'f') and
                    $valType == 'bool') $item = ($item == 't');
                if  (is_bool($item)) $item = ($item) ? 'TRUE' : 'FALSE';
                $quoted[] = $item;
            }
            $ret .= implode(',',$quoted) . '}$$';
            return $ret;
        break;
    }
    return 'NULL';
}

// Se não existe registro do posto, considera que é um novo cadastro.
$sql_cn = "SELECT posto FROM tbl_at_postos_black WHERE posto = $login_posto";
$cadastro_novo = (pg_num_rows(pg_query($con, $sql_cn)) == 0);

if ($_GET['depois'] == '1' and $cadastro_novo) {
    //Insere o registro para saber que já passou por aqui...
    $sql = "INSERT INTO tbl_at_postos_black(
    			posto,
    			fantasia,
    			telefone,
    			contato_1,
    			email_contato_1,
    			distrib_black,
    			consumidor_revenda,
             	consumidor_revenda_per,
             	linhas_black,
             	per_linhas_bd,
             	outras_atende,
              	outras_treino,
              	dados_banco_ok,
              	responsavel_cadastro,
              	treinamento_bd
            )
            VALUES(
            	$login_posto,
            	'',
            	'',
            	'',
            	'',
            	false,
            	0,
            	0,
            	'',
            	'',
            	false,
            	false,
            	false,
            	'',
            	false
            )";
    pg_query($con, $sql);
    if (pg_last_error($con)) {
    	echo "Ocorreu um erro ao tentar pular a Pesquisa de Atualização Cadastral do Posto e você pode <a href='{$PHP_SELF}' title='Preencher o formulário depois'>Clicar aqui para retornar</a><br />";
    	exit;
    }else{
        setcookie('black_frm_at', '1');
        header("Location: menu_inicial.php");    	
    }
}
if (count(array_filter($_POST))) {
	if ($_POST['ajax']=='true' or 1==1) {

        // Validar
        include 'helpdesk/mlg_funciones.php';
        $form = array_map('utf8_decode', array_filter($_POST, 'anti_injection')); //o POST vem em utf-8
        $form['linhas'] = array_map('utf8_decode',array_filter($_POST['linhas'],'anti_injection'));
        $form['linhas_per'] = array_map('utf8_decode',array_filter($_POST['linhas_per'],'anti_injection'));

        $msg_erro = array();
        if (count($form)<15) $msg_erro[] = 'Alguns campos não foram preenchidos:';

        $a_campos_obrig = explode(',',
                                'fantasia,contato_fone_comercial,contato_fax,contato_nome,contato_email,'.
                                'distrib_fabrica,consumidor_revenda,consumidor_revenda_per,'.
                                'linhas,linhas_per,treinou,atende_marcas,banco_ok,responsavel_questionario');
        if ($banco_ok == 'f') array_push($a_campos_obrig, 'banco', 'banco_agencia', 'banco_conta');

        foreach($a_campos_obrig as $forced_field) {
            if (!isset($form[$forced_field])) $missing[] = $forced_field;
        }
        if (count($missing)) {
            $msg_erro[] = 'Os campos:<br />' . ucfirst(str_replace('_', ' ', implode(',', $missing))) . '<br />são obrigatórios.';
        } else {
            $form['linhas']     = array_map('utf8_decode', array_filter($_POST['linhas'],     'anti_injection'));
            $form['linhas_per'] = array_map('utf8_decode', array_filter($_POST['linhas_per'], 'anti_injection'));
            extract($form);
            if (count(array_filter($linhas)) == 0) $msg_erro[] = 'Informe a(s) linhas da B&D que a sua AT atende.';
            if (count(array_filter($linhas)) > count(array_filter($linhas_per)))
                $msg_erro[] = 'Algumas informações sobre o percentual não foram preenchidas.';

            //Valida os e-mails:
            if (!is_email($contato_email)) $msg_erro[] = 'E-mail de contato inválido!';
            if ($contato_email_extra != '' and !is_email($contato_email_extra)) $msg_erro[] = 'E-mail de contato inválido!';

            //Treinamentos Black & Decker
            $a_treinos_bd_linhas   = array();
            $a_treinos_bd_tecnicos = array();
            $a_treinos_bd_datas    = array();
            $a_treinos_bd_ativos   = array();
            if ($treinou == 't') {
                // Confere se tem pelo menos um, e se os que tiver tem os três campos
                $a_treinos = array('D', 'C', 'MT');
                foreach($a_treinos as $tr_linha) {
                    $tr_t = "tr_$tr_linha".'_tecnico';
                    $tr_d = "tr_$tr_linha".'_data';
                    $tr_a = "tr_$tr_linha".'_ativo';

                    if ($$tr_t != '') {
                        list($d,$m,$y) = explode('/', $$tr_d);
                        if (!checkdate($m, $d, $y)) $msg_erro[] = "Data do treinamento da linha $tr_linha inválida!"; 
                        $$tr_a = ($$tr_a == 'on');
                        if (count($msg_erro) == 0) {
                            $a_treinos_bd_linhas[]  = $tr_linha;
                            $a_treinos_bd_tecnicos[]= $$tr_t;
                            $a_treinos_bd_datas[]   = "$y-$m-$d";
                            $a_treinos_bd_ativos[]  = $$tr_a;
                        }
                    }
                }
                if (count($msg_erro) == 0 and count($a_treinos_bd_datas) == 0)
                    $msg_erro[] = 'Informou que recebeu treinamento da Black & Decker, mas não informou quais, quem e em que datas!';
            }
        } //Fim atende linhas e treinamento B&D

        //3. Atendimento outras marcas
        $a_tr_o_linhas  = array();
        $a_tr_o_tecnicos= array();
        $a_tr_o_datas   = array();
        $a_tr_o_ativos  = array();
        $a_outras_marcas= array();
        if ($atende_marcas == 't') {
            $a_linhas_outras = array_keys($a_linhas_at);

            //Confere que preencheu as marcas das linhas
            foreach($a_linhas_outras AS $o_linha) {
                $o_atende_linha = "o_$o_linha";
                $o_marcas_linha = "o_marcas_$o_linha";
                $nome_linha     = $a_linhas_at[$o_linha];

                if ($$o_atende_linha == 'on') {
                    if ($$o_marcas_linha != '') {
                        $a_outras_marcas[$o_linha] = $$o_marcas_linha;
                    } else {
                        $msg_erro[] = "Não informou quais marcas atende da linha $nome_linha.";
                    }
                }
            }
            if (count($a_outras_marcas) == 0) $msg_erro[] = 'Informou que atende outras marcas, mas não informou nem as linhas e nem as marcas.';

            //Confere os treinamentos, se marcou que tem
            if (count($a_outras_marcas) and $outras_treinou == 't') {
                foreach($a_linhas_outras as $tr_linha) {
                    $tr_t = "o_tr_$tr_linha".'_tecnico';
                    $tr_d = "o_tr_$tr_linha".'_data';
                    $tr_a = "o_tr_$tr_linha".'_ativo';
                    $nome_linha = $a_linhas_at[$tr_linha];

                    if ($$tr_t != '') {
                        if ($$tr_d != '') {
                            list($d,$m,$y) = explode('/', $$tr_d);
                            if (!checkdate($m, $d, $y)) $msg_erro[] = "Data do treinamento da linha $nome_linha inválida!"; 
                            $$tr_a = ($$tr_a == 'on');
                            if (count($msg_erro) == 0) { //Insere no array
                                $a_treinos_outras[$tr_linha] = array(
                                    'tecnico'   => $$tr_t,
                                    'data'      => "$y-$m-$d", //Já converte para ISO...
                                    'ativo'     => $$tr_a
                                    );
                            }
                        } else {
                            $msg_erro[] = "Informou o nome do técnico ({$$tr_t}), mas não informou a data do treinamento!";
                        }
                    }
                }
                if (count($a_treinos_outras) == 0) {
                    $msg_erro[] = 'Informou que receberam treinamentos de outras marcas, mas não informou os dados de nenhum treinamento.';
                } else {
                    $count = 0;

                    foreach($a_treinos_outras as $tr_linha=>$a_tr_dados) {
                        $a_tr_o_linhas[$count]  = $tr_linha;
                        $a_tr_o_tecnicos[$count]= $a_tr_dados['tecnico'];
                        $a_tr_o_datas[$count]   = $a_tr_dados['data'];
                        $a_tr_o_ativos[$count]  = $a_tr_dados['ativo'];
                        $count++;
                    }
                    for ($i = $count; $i < 12; $i++) {
                        $a_tr_o_linhas[$i]  = 'NULL';
                        $a_tr_o_tecnicos[$i]= 'NULL';
                        $a_tr_o_datas[$i]   = 'NULL';
                        $a_tr_o_ativos[$i]  = 'NULL';
                    }
                }
            }
        }

        if (count($msg_erro)) {
            echo 'KO|' . implode("<br />\n", $msg_erro);
            exit;
        }

        //Prepara os dados para salvar no banco de dados
        $posto  = $login_posto;
        $fabrica= 1;

        $campos_insert = array(
            'posto'                  => pg_quote($posto, true),
            'responsavel_cadastro'   => pg_quote($responsavel_questionario),
            'fantasia'               => pg_quote($fantasia),
            'telefone'               => pg_quote($contato_fone_comercial),
            'telefone_fax'           => pg_quote($contato_fax),
            'contato_1'              => pg_quote($contato_nome),
            'email_contato_1'        => pg_quote($contato_email),
            'contato_2'              => pg_quote($contato_nome_extra),
            'email_contato_2'        => pg_quote($contato_email_extra),
            'distrib_black'          => pg_quote(($distrib_fabrica=='t')),
            'distribuidor'           => pg_quote($distrib_nao_fabrica),
            'consumidor_revenda'     => pg_quote($consumidor_revenda, true),
            'consumidor_revenda_per' => pg_quote($consumidor_revenda_per, true),
            'linhas_black'           => pg_quote(implode(',', $linhas)),
            'per_linhas_bd'          => pg_quote(implode(',', $linhas_per)),
            'treinamento_bd'         => pg_quote(($treinou=='t')),
            'treino_linhas'          => pg_array_quote($a_treinos_bd_linhas  , 'string'), 
            'treino_tecnicos'        => pg_array_quote($a_treinos_bd_tecnicos, 'string'), 
            'treino_datas'           => pg_array_quote($a_treinos_bd_datas   , 'string'), 
            'treino_ativos'          => pg_array_quote($a_treinos_bd_ativos  , 'bool')  , 
            'outras_atende'          => pg_quote(($atende_marcas=='t')),
            'outras_linhas'          => pg_array_quote(array_keys(array_filter($a_outras_marcas)), 'string'),
            'outras_marcas'          => pg_array_quote(array_filter($a_outras_marcas), 'string'),
            'outras_treino'          => pg_quote(($outras_treinou=='t')),
            'o_tr_linhas'            => pg_array_quote($a_tr_o_linhas,   'string'), 
            'o_tr_tecnicos'          => pg_array_quote($a_tr_o_tecnicos, 'string'), 
            'o_tr_datas'             => pg_array_quote($a_tr_o_datas,    'string'), 
            'o_tr_ativos'            => pg_array_quote($a_tr_o_ativos,   'bool')  , 
            'dados_banco_ok'         => pg_quote(($banco_ok == 't')),
        );

        //Prepara o UPDATE da tbl_posto_fabrica, atualizando nome fantasia, dados de contato e dados bancários
        $sql_pf = "UPDATE tbl_posto_fabrica
                      SET nome_fantasia         = '$fantasia',
                          contato_fone_comercial= '$contato_fone_comercial',
                          contato_fax           = '$contato_fax',
                          contato_nome          = '$contato_nome',
                          contato_email         = '$contato_email'
                          ";

        if ($banco_ok == 'f' and count($msg_erro)==0) {
            $sql_pf   .= ",
                          banco                 = '$banco',
                          agencia               = '$banco_agencia',
                          conta                 = '$banco_conta',
                          nomebanco             = '$banco_nome',
                          tipo_conta            = 'Conta Corrente'
                    ";
        }
        $sql_pf .= "WHERE posto   = $login_posto
                             AND fabrica = $login_fabrica";

        if ($cadastro_novo == false) unset($campos_insert['posto']); // Se já existe registro, tira o campo 'posto', vai no WHERE
        $campos = implode(",\n\t\t", array_keys($campos_insert));
        $valores= implode(",\n\t\t", $campos_insert);

        if ($cadastro_novo) {
            $sql = "INSERT INTO tbl_at_postos_black
                                ($campos) VALUES ($valores)";
        } else {
            $sql = "UPDATE tbl_at_postos_black
                           SET ($campos) = ($valores)
                           WHERE posto = $login_posto";
        }

        $res = @pg_query($con, $sql);
        if (is_resource($res)) {
            if (pg_affected_rows($res) == 1) {
                $gravou = true;
                $res_banco = @pg_query($con, $sql_pf);
                $gravou = ($gravou and (pg_affected_rows($res_banco) == 1));

                if ($gravou) {
                    exit('OK|Cadastro finalizado com êxito! Pode continuar a usar o sistema Telecontrol. Obrigado.');
                } else {
                    exit('KO|' . pg_last_error($con));
                }
            }
        } else {
            exit("KO|$sql\n".pg_last_error($con));
            $msg_erro[] = 'Erro ao gravar seu cadastro. Por favor, tente novamente em alguns segundos.<br />Se o erro continuar a acontecer, contate com seu Atendente ou com a Telecontrol.';
        }
        exit();
    } //FIM da resposta AJAX (validação do formulário)	
}else{
	$sql_posto  = " SELECT 
						tbl_posto.nome AS razao_social,
	                  	tbl_posto_fabrica.banco,
	                  	tbl_banco.nome AS banco_nome,
	                  	tbl_posto_fabrica.agencia,
	                  	tbl_posto_fabrica.conta
	             	FROM tbl_posto_fabrica
	             	JOIN tbl_posto USING(posto)
	             	LEFT JOIN tbl_banco ON tbl_banco.codigo = tbl_posto_fabrica.banco
	            	WHERE posto = $login_posto AND fabrica = 1";

	$res = pg_query($con, $sql_posto);
	if (is_resource($res)) {
	    extract(pg_fetch_assoc($res, 0), EXTR_PREFIX_ALL, 'campo');
	} else {
	    exit('Erro ao acesar ao sistema. Tente novamente em alguns instantes.');
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Strict//EN">
<html>
  	<head>
	  	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1">
	  	<meta name="generator" content="PSPad editor, www.pspad.com">
	  	<title>Formulário Cadastro Posto Autorizado Black & Decker</title>
	    <style type="text/css">
	        body {margin:0;padding:0}
	        .oculto {display: none;}
	        #janela {
	            display: block;
	            background-color: #ffffff;
	            position: relative;
	            text-align: left;
	            font-family: Segou UI, Verdana, Arial, Helvetica, Sans-serif;
	            font-size: 12px;
	            padding: 32px 0 10px 10px;
	            margin: 20px auto;
	            border: 2px solid #b8bac6;
	            border-radius: 8px;
	            -moz-border-radius: 8px;
	            box-shadow: 3px 3px 3px #ccc;
	            -moz-box-shadow: 3px 3px 3px #ccc;
	            -webkit-box-shadow: 3px 3px 3px #ccc;
	            overflow: hidden;
	            width:800px;
	            _margin: 10px 15%;
	            *margin: 10px 15%;
	        }
	        #janela #ei_container p {
	            font-size: 12px;
	            padding: .5ex 1ex;
	            overflow-y:auto;
	        }
	        #janela #ei_header {
	            position: absolute;
	            top:    0;
	            left:   0;
	            margin: 0;
	            vertical-align: middle;
	            width: 100%;
	            _width: 798px;
	            *width: 798px;
	            height:  28px;
	            border-radius: 7px 7px 0 0 ;
	            -moz-border-radius: 7px 7px 0 0 ;
	            background-color: #b8bac6;
	            background: linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* W3C */
	            background: -o-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* Opera11.10+ */
	            background: -ms-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* IE10+ */
	            background: -moz-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* FF3.6+ */
	            background: -webkit-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* Chrome10+,Safari5.1+ */
	            padding: 2px 10px;
	            color: black;
	            font: normal bold 13px Segoe UI, Verdana, MS Sans-Serif, Arial, Helvetica, sans-serif;
	        }
	        #janela #ei_container {
	            background-color: #fdfdfd;
	            margin: 1px;
	            padding: 0;
	            padding-bottom: 1ex;
	            overflow-y: auto;
	            overflow-x: hidden;
	            height: 480px;
	            font-size: 11px;
	            color: #313452;
	            width: 100%;
	            line-height: 1.6em;
	            position: relative;
	        }
	        #ei_container #msgErro {
	            background-color: rgba(255, 127, 127, 0.7);
	            color: #fff;
	            display: none;
	            font-weight:bold;
	            font-size: 11px;
	            border: 2px solid red;
	            border-radius: 5px;
	            -moz-border-radius: 5px ;
	            box-shadow: 2px 2px 3px #005;
	            -moz-box-shadow: 2px 2px 3px #005;
	            -webkit-box-shadow: 2px 2px 3px #005;
	            width: 700px;
	            margin: auto;
	            padding: 6px 18px;
	        }

	    	/*  Validação e erro    */
	        #janela .valid {background-color: #cfc;}
	        #ei_container .error {display:inline;color:darkred;font-weight:bold;}
	        #ei_container span.error {display:inline!important;color:white;background-color: #900;font-weight:bold;font-size: 11px;}
	        #ei_container p.erro {
	            display:block;
	            width:90%;
	            /* Se entrar pelo login único, sobreescrever fundo e borda da class .erro ... */
	            background-color: white;
	            border: 0 solid transparent;
	        }
	        #ei_container p.erro > label {display:inline;color:darkred;width:auto!important;zoom:none;font-size: 10px;background:#f09090;text-align: left;font-weight:bold;}
	        #janela form input.error {background-color:#fcc;font-size: 11px;text-align: left;}
	        #janela form input.error:active {background-color:#ccf;}

	        #janela form {line-height:1.8em;}
	        #ei_container form input[type=text],
	        #ei_container form input[type=date],
	        #ei_container form input[type=number],
	        #ei_container form select,
	        #ei_container form input[type=text] {
	            height: 16px;
	            border: 1px double #aaaaaa;
	        }
	        #o_fs_linhas input[type=text] {
	        	width: 235px;
	        }
	        .tbl_treino caption {color:#009}
	        #ei_container form label, dt {
	            color: #009;
	            text-align:right;
	            width: 130px;
	            display:inline-block;
	            _zoom:1;
	        }
	        dt {max-width: 50px;text-align:left;}
	        dd {display:inline;}
	        #ei_container form fieldset label {
	            font-size: 11px;
	        }
	        #ei_container form fieldset, form div {
	            margin: 18px auto;
	            _margin-top: 4em;
	            *margin-top: 4em;
	            padding: auto 10px;
	            border: 1px solid #d3d3d3;
	            border-radius: 6px;
	            _padding-bottom: 0.5em;
	            *padding-bottom: 0.5em;
	            position:relative;
	            width: 700px;
	        }
	        #ei_container form #fs_end label {width:70px}
	        #ei_container form .fs_linhas label {
	            width: 170px;
	            text-align: left;
	        }
	        #ei_container form fieldset input[type=radio] + label,
	        #ei_container form fieldset input[type=checkbox] + label,
	        #ei_container form label.normal {
	            display: inline;
	            text-align:left;
	            width: auto; /* O IE não entende o width quando coloca inline-block, mas o mantém mesmo voltando para inline!*/
	        }
	        #ei_container form fieldset table.tbl_treino input {
	            margin: auto;
	        }
	        #ei_container form legend {
	            border-radius: 4px 4px 0 0;
	            -moz-border-radius: 4px 4px 0 0;
	            background-color: #d3d3d3;
	            border-top: 2px solid #f4781e;
	            background-color: #b8bac6;
	            background: linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* W3C */
	            background: -o-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* Opera11.10+ */
	            background: -ms-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* IE10+ */
	            background: -moz-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* FF3.6+ */
	            background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#f5f6f6), color-stop(21%,#dbdce2), color-stop(49%,#b8bac6), color-stop(80%,#dddfe3), color-stop(100%,#f5f6f6)); /* Chrome,Safari4+ */
	            background: -webkit-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* Chrome10+,Safari5.1+ */
	            margin-left: 2ex;
	            position: absolute;
	            top: -22px;
	            height: 16px;
	            -o-top: -16px;
	            padding: 0 4px 2px 4px;
	            font-weight: bold;
	            color: #333;
	        }
	          #ei_container fieldset#info_cad label {
	            width: 170px!important;
	          }
	          #ei_container fieldset#info_cad label.info {width: auto!important;zoom:none}
	          #ei_container fieldset#info_cad input {width: 121px}
	          #ei_container fieldset > fieldset {width: 660px; margin: 18px auto}
	          #ei_container fieldset > fieldset > fieldset, .tbl_treino {width: 620px; margin: 18px auto}
	          #ei_container div > fieldset {width: 90%; margin: 18px auto}
	          #ei_container fieldset > fieldset > label {width: 150px}
	          #ei_container table.tbl_treino {
	              font-size: 11px;
	              table-layout: fixed;
	              border-collapse: separate;
	              border: 1px solid #d3d3d3;
	              border-radius: 6px;
	              -moz-border-radius: 6px;
	              margin-top: 10px;
	          }
	          #ei_container table.tbl_treino thead th {
	              border-radius: 4px 4px 0 0;
	              -moz-border-radius: 4px 4px 0 0;
	              background-color: #d3d3d3;
	              height: 20px;
	              padding: 0 4px 2px 4px;
	              font-weight: bold;
	              color: #333;
	              height: 1.2em;
	              overflow: hidden;
	              vertical-align: middle;
	          }
	        #ei_container form table.tbl_treino td {
	            color: #009;
	            margin: auto;
	        }
	        #ei_container form fieldset#fs_banco {text-align: left;}
	        #ei_container form #fs_banco label {width:70px}
	        #ei_container form fieldset.bool {border: 0}
	        input#fantasia {width:420px!important}
	        #alertaErro h4{
	        	background-color: red;
	        	color: white;
	        	width: 810px;
	        }
	    </style>
	    
	    <script type="text/javascript" src="js/jquery-1.6.2.js"></script>
	    <script type="text/javascript" src="js/jquery.maskedinput.js "></script>
	    <script type="text/javascript" src="js/jquery.validate.min.js"></script>
	    <script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
	    <script type="text/javascript" src="js/validate_localization/messages_ptbr.js"></script>
	    <script type="text/javascript" src="js/validate_localization/br.validate.extend.js"></script>
	    <script type="text/javascript" src="js/validate_localization/additional-methods.min.js"></script>

	    <script type="text/javascript">
		    $(function(){
		    	$('#janela').fadeIn('normal');

		        $('input:radio').not('[name=consumidor_revenda]').click(function() {
					var status  = ($(this).val()=='t') ? 't' : 'f';
		            var fs_id   = '#' + $(this).attr('data');
		            // Aqui os ID dos que mostram quando é 'NAO' e ocultam quando é 'SIM'
		            if (fs_id == '#dnf_block' || fs_id == '#dados_banco') status = (status == 't')?'f':'t';//Inverte para estes campo

		            (status=='t') ? $(fs_id).removeClass('oculto') : $(fs_id).addClass('oculto');
		        });

                $('#fs_linhas input:text').change(function () {
                    tot = 0; // Reinicializa para não dar falsos valores
                    $('#fs_linhas input:text').filter(':not(:disabled)').each(function(item) {
                        if (!isNaN(parseFloat($(this).val())))
                            tot += parseFloat($(this).val());
                    });
                    if (tot > 100) {
                        $('#tot_per_linhas').text(tot.toFixed(2) + '%').addClass('error').show('fast');
                        alert('O total de percentuais supera o 100%. Por favor, confira as informações.');
                    } else {
                        if (tot <= 100) {
                            $('#tot_per_linhas').text(tot).removeClass('error');
                        }
                    }
                });

		        $('#banco').change(function(){
		        	$.ajax({
		        		url: 'posto_atualizacao_dados.php',
		        		method: 'GET',
		        		data : { ajax: 'banco', codigo: $('#banco').val()},
		        		timeout: 7000
		        	}).fail(function(){
		        		alert('erro');
		        	}).done(function(data){
		        		data = data.split(';');
		        		$('#banco_nome').val(data[2]);		        		
		        	});
		        });

                $('#btnGravar').click(function(){
    	 			var program_self = '<?echo basename(__FILE__)?>'; //Nome deste aquivo, e não da tela atual
                    var postData = $('#frm_at_posto').serialize();
                    $.post(program_self,
                        postData + '&ajax=true',
                        function(retorno) {
                            var data = retorno.split('|');

                            if (data[0] == 'OK') {
                                alert('Seus dados foram cadastrados com sucesso! Obrigado. Clique em <OK> para continuar...');
                                $('#janela').hide('fast').remove();
                                window.location.reload();
                            } else {
                                $("#alertaErro").show().find("h4").html(data[1]);
                                $('button[type=submit]').removeAttr('disabled');
                            }
                    });
                });

		    	/* MASCARA DOS CAMPOS */
		    	$('#pr_cr').css('text-align', 'right');
        		$('#fone,#fax').keypress(function(){
        			Mascara(this);
        		});
        		$('input[name*=_data]').maskedinput('99/99/9999').css('text-align', 'right').addClass('dateBRNaoFutura');        		
		    });

		    function Mascara(objeto){ 
			   	if(objeto.value.length == 0)
			    	objeto.value = '(' + objeto.value;

			   	if(objeto.value.length == 3)
			    	objeto.value = objeto.value + ')';

			 	if(objeto.value.length == 8)
			    	objeto.value = objeto.value + '-';
			}

		    function somente_numero(campo){
		        var digits="0123456789-"
		        var campo_temp 
		        for (var i=0;i<campo.value.length;i++){
		          campo_temp=campo.value.substring(i,i+1)   
		          if (digits.indexOf(campo_temp)==-1){
		                campo.value = campo.value.substring(0,i);
		                break;
		           }
		        }
		    }
	    </script>
	</head>
	<body>
		<div id="alertaErro" style="display: none;"><h4></h4></div>
     	<div id="janela" style='display:none'>
        	<div id="ei_header">
            	<img src='/assist/logos/blackDecker_logo.png' style='width:16px;vertical-align:middle;padding: 4px 1ex 0 0' />Pesquisa de Atualização Cadastral do Posto
        	</div>
        	<div id="ei_container">
            	<img src='/assist/logos/black_decker.png' alt='Black & Decker' />
            	<input type="text" id="void" tabindex='0' style='background-color:transparent;border:0 solid transparent;color:transparent' readonly />
            	<p style='text-align:justify'>
            		Prezado Autorizado,<br /><br />
		            Para que possamos fazer uma atualização no cadastro do seu posto autorizado junto à <b><i>Black & Decker</i></b>
		            gostaríamos que respondesse o questionário abaixo.
		            Solicitamos atenção especial no preenchimento de cada informação, pois esses dados serão atualizados em seu cadastro,
		            e informações incorretas podem gerar transtornos no funcionamento normal dos nossos processos. Portanto, se houver
		            dúvida com relação ao preenchimento correto entre em contato com o seu suporte para mais informações.<br /><br />
				<?  if ($cadastro_novo) { ?>
		            Se não for possível responder o questionário agora por falta de conhecimento de todas as informações
		            <a href="<?=$PHP_SELF?>?depois=1" title="Preencher o formulário depois">clique aqui</a> para ir para a página inicial do site.
		            <b>PORÉM, GOSTARÍAMOS DE RESSALTAR QUE <u>SERÁ OBRIGATÓRIO O PREENCHIMENTO COMPLETO</u> DESSA PESQUISA NO PRÓXIMO ACESSO PARA QUE POSSA PROSSEGUIR.</b>
		            Na hipótese de ser necessário contatar outra pessoa na empresa para obter as respostas, sugerimos que verifique
		            as informações com a máxima urgência para que não tenha transtorno ao acessar o site novamente.<br /><br />
				<?} else { ?>
            		<b><u>É OBRIGATÓRIO O PREENCHIMENTO COMPLETO</u> DESSA PESQUISA NESTE ACESSO</b> PARA QUE POSSA PROSSEGUIR.<br /><br />
				<?}?>
            	</p>
            	<form action="" id='frm_at_posto' method='post' autocomplete='off'>
                	<input type='hidden' name='posto' value='<?=$login_posto?>' />
                	<fieldset id='info_cad'>
	                    <legend>1. Dados de Contato</legend>
	                    <p>Razão Social: <b><?=$campo_razao_social?></b></p>
	                    <label for="fantasia">Nome Fantasia:</label>
	                    	<input type="text" maxlength='50' name='fantasia' tabindex='1' class='required {minlength:5}' id='fantasia' tabindex='1' />
	                    <br />
	                    <label for="fone" style="margin-top: 6px;">Telefone:</label>
	                        <input type='text' id='fone' class='required foneBR' tabindex='2' name='contato_fone_comercial' maxlength="14" />
	                    <label for="fax">Fax</label>
	                        <input type='text' id='fax'  class='required foneBR' tabindex='3' name='contato_fax' maxlength="13" />
	                    <br />
	                    <label for="contato_1" style="margin-top: 6px;">Contato (1):</label>
	                        <input type='text' id='contato_1' tabindex='4' 
	                              class='required alpha' minlength='3' maxlength='30' name='contato_nome' placeholder="Pessoa responsável" />
	                    <label for="email_1">E-mail:</label>
	                        <input type="text" id="email_1" name="contato_email" tabindex='5' class='required email' />
	                    <br />
	                    <label for="contato_2" style="margin-top: 6px;">Contato (2):</label>
	                        <input type='text' id='contato_2' class='alpha' maxlength='30' tabindex='6' name='contato_nome_extra' placeholder="Pessoa responsável" />	                    
	                    <label for="email_2">E-mail:</label>
	                        <input type="text" id="email_2" name="contato_email_extra" tabindex='7' class='email'/>
                	</fieldset>
                	<p class="erro"></p>
                	<fieldset id='fs_oper'>
                    	<legend>2. Sobre as operações do Posto Autorizado</legend>
                    	<p>A compra de peças é feita direto com a fábrica?</p>
                    	<fieldset id='fs_dnf' class='bool'>
	                        <input type="radio" class='required' name="distrib_fabrica" data='dnf_block' tabindex='8' id="df_sim" value='t' />
	                        <label for="df_sim">Sim</label>
	                        <input type="radio" name="distrib_fabrica" data='dnf_block' tabindex='9' id="df_nao" value='f' />
	                        <label for="df_nao">Não</label>
	                        <p id='dnf_block' class='oculto'>
	                            <label for="distrib_nao_fabrica" class='normal'>Informe seu Distribuidor:</label>
	                            <input type='text' id='dnf' name='distrib_nao_fabrica' tabindex='10' size='50'
	                              maxlength='50' minlength='4' />
	                        </p>
                    	</fieldset>
                    	<p class="erro"></p>
                    	<fieldset id='fs_atend'>
	                        <legend>2.1. Nº de Atendimentos</legend>
	                        <p>Qual é o maior volume de atendimento no seu posto autorizado?<br />
	                           Selecione a resposta e informe o percentual que representa no total de atendimentos.</p>
	                        <div style="background:;float:left;height:45px;width:200px;border:0px;">
	                            <input type='radio' name='consumidor_revenda' value='C' id='cr_c' tabindex='11' />
	                            <label for='cr_c' style='width:170px'>Cliente final</label>
	                            <br />
	                            <input type='radio' name='consumidor_revenda' value='R' id='cr_r' tabindex='12' />
	                            <label for='cr_r' style='width:170px'>Estoque de revenda</label>
	                        </div>

	                        <div style="background:;float:left;height:45px;width:100px;border:0px;">
	                            <div style="background:;float:left;width:100%;margin-top:10px;border:0px;">
	                                &nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="pr_cr" size='4' align='right' tabindex='13' class='required' name="consumidor_revenda_per" />%
	                            </div>
	                        </div>
	                        <br />
	                    </fieldset>
                    	<p class="erro"></p>
	                    <fieldset id='fs_linhas' class='fs_linhas'>
	                        <legend>2.2. Linhas Credenciadas</legend>
	                        <p>A sua empresa é credenciada no atendimento de qual(is) linha(s) de produto(s) Black & Decker?<br />
	                            Na primeira coluna selecione a(s) linha(s) credenciada(s) e na segunda coluna
	                            informe qual percentual essa linha de produto representa no seu negócio.</p>

	                        <label for='AUTO'>Automotiva</label>
	                            <input type='checkbox' name='linhas[]' tabindex='14' id='AUTO' value='A'>&nbsp;
	                            <input type='text' name='linhas_per[]' tabindex='15' size='3' maxlength='3' />%
	                            <br />
	                        <label for='COMP'>Compressores</label>
	                            <input type='checkbox' name='linhas[]' tabindex='16' id='COMP' value='C'>&nbsp;
	                            <input type='text' name='linhas_per[]' size='3' maxlength='3' align='right' />%
	                            <br />
	                        <label for='ELET'>Eletrodomésticos</label>
	                            <input type='checkbox' name='linhas[]' tabindex='17' id='ELET' value='E'>&nbsp;
	                            <input type='text' name='linhas_per[]' tabindex='18' size='3' maxlength='3' />%
	                            <br />
	                        <label for='FECH'>Fechaduras</label>
	                            <input type='checkbox' name='linhas[]' tabindex='19' id='FECH' value='F'>&nbsp;
	                            <input type='text' name='linhas_per[]' tabindex='20' size='3' maxlength='3' />%
	                            <br />
	                        <label for='DWLT'>Ferramentas DEWALT</label>
	                            <input type='checkbox' name='linhas[]' tabindex='21' id='DWLT' value='D'>&nbsp;
	                            <input type='text' name='linhas_per[]' tabindex='22' size='3' maxlength='3' />%
	                            <br />
	                        <label for='FELE'>Ferramentas Elétricas</label>
	                            <input type='checkbox' name='linhas[]' tabindex='23' id='FELE' value='B'>&nbsp;
	                            <input type='text' name='linhas_per[]' tabindex='24' size='3' maxlength='3' />%
	                            <br />
	                        <label for='FPNE'>Ferramentas Pneumáticas</label>
	                            <input type='checkbox' name='linhas[]' tabindex='25' id='FPNE' value='P'>&nbsp;
	                            <input type='text' name='linhas_per[]' tabindex='26' size='3' maxlength='3' />%
	                            <br />
	                        <label for='GERA'>Geradores</label>
	                            <input type='checkbox' name='linhas[]' tabindex='27' id='GERA' value='GR'>&nbsp;
	                            <input type='text' name='linhas_per[]' tabindex='28' size='3' maxlength='3' />%
	                            <br />
	                        <label for='LASR'>Laser</label>
	                            <input type='checkbox' name='linhas[]' tabindex='29' id='LASR' value='LS'>&nbsp;
	                            <input type='text' name='linhas_per[]' tabindex='30' size='3' maxlength='3' />%
	                            <br />
	                        <label for='LAVP'>Lavadoras de Pressão</label>
	                            <input type='checkbox' name='linhas[]' tabindex='31' id='LAVP' value='LV'>&nbsp;
	                            <input type='text' name='linhas_per[]' tabindex='32' size='3' maxlength='3' />%
	                            <br />
	                        <label for='GEOM'>Metais Sanitários</label>
	                            <input type='checkbox' name='linhas[]' tabindex='33' id='GEOM' value='M'>&nbsp;
	                            <input type='text' name='linhas_per[]' tabindex='34' size='3' maxlength='3' />%
	                            <br />
	                        <label for='MOTG'>Motores à Gasolina</label>
	                            <input type='checkbox' name='linhas[]' tabindex='35' id='MOTG' value='GS'>&nbsp;
	                            <input type='text' name='linhas_per[]' tabindex='36' size='3' maxlength='3' />%
	                            <br />
	                        <label style='text-align: left;font-weight:bold'>Total:</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	                        <span id="tot_per_linhas"></span>
	                    </fieldset>
                    	<p class="erro"></p>
                </fieldset>
                <fieldset id='fs_tr_bd'>
                    <legend title="Treinamentos">3. Treinamentos</legend>
                    <p> A sua empresa já recebeu treinamento da Black & Decker?<br />
                        Caso a resposta seja sim, selecione também a(s) linha(s), informe o nome do técnico,
                        data do treinamento e se o técnico treinado ainda trabalha na sua empresa selecione a opção "ativo".</p>
                    <fieldset class='bool' id='fs_treinou'>
                        <input type="radio" class='required' id="tr_sim" name='treinou' tabindex='37' data='info_treinamentos' value='t' /><label for="tr_sim">Sim</label>&nbsp;&nbsp;
                        <input type="radio" id="tr_nao" name='treinou' tabindex='38' data='info_treinamentos' value='f' /><label for="tr_nao">Não</label>
                    </fieldset>
                    <p class="erro"></p>
                    <fieldset class='bool'> <?  /* Class bool apenas para tirar a borda... O:) */   ?>
	                    <table class='tbl_treino oculto' align='center' id='info_treinamentos'>
	                    	<caption>Dados dos Treinamentos</caption>
	                        <thead>
	                            <tr style='height:20px;vertical-align:middle'>
	                                <th style='width: 200px'>Linha</th>
	                                <th style='width: 240px'>Técnico</th>
	                                <th style='width: 120px'>Data</th>
	                                <th style='width:  50px'title='O técnico ainda trabalha no Posto?'>Ativo <sup>?</sup></th>
	                            </tr>
	                        </thead>
	                        <tbody>
	                            <tr>
	                                <td><input type="checkbox" id="tr_D" name="tr_D" tabindex='39' />&nbsp;<label for="tr_D">Ferramentas DEWALT</label></td>
	                                <td><input type="text" maxlength='40' style='width: 240px' tabindex='40'
	                                    placeholder='Nome do técnico' name='tr_D_tecnico' /></td>
	                                <td><input type="text" maxlength='40' style='width: 100px' tabindex='41' name='tr_D_data' /></td>
	                                <td><input type="checkbox" name="tr_D_ativo" tabindex='42' /></td>
	                            </tr>
	                            <tr>
	                                <td><input type="checkbox" id="tr_C" name='tr_C' tabindex='43' />&nbsp;<label for="tr_C">Compressores</label></td>
	                                <td><input type="text" maxlength='40' style='width: 240px' tabindex='44'
	                                    placeholder='Nome do técnico' name='tr_C_tecnico' /></td>
	                                <td><input type="text" maxlength='40' style='width: 100px' tabindex='45' name='tr_C_data' /></td>
	                                <td><input type="checkbox" name="tr_C_ativo" tabindex='46' /></td>
	                            </tr>
	                            <tr>
	                                <td><input type="checkbox" id="tr_MT" name='tr_MT' tabindex='47' />&nbsp;<label for="tr_MT">Martelos</label></td>
	                                <td><input type="text" maxlength='40' style='width: 240px' tabindex='48'
	                                    placeholder='Nome do técnico' name='tr_MT_tecnico' /></td>
	                                <td><input type="text" maxlength='40' style='width: 100px' tabindex='49' name='tr_MT_data' /></td>
	                                <td><input type="checkbox" name="tr_MT_ativo" tabindex='50' /></td>
	                            </tr>
	                        </tbody>
	                    </table>
                    </fieldset>
                    <p class="erro"></p>
                </fieldset>
                <fieldset id='fs_outras'>
                    <legend>4. Outras Marcas</legend>
                    <p>O seu posto de serviços é credenciado para atendimento de outras marcas? Selecione
                    Sim ou Não. Caso a resposta seja sim, selecione também a(s) linha(s) atendida(s) e
                    informe o nome da(s) marca(s).</p>
                    <fieldset id='fs_atente_outras' class='bool' >
                        <input type="radio" class='required' id="atende_outras_sim" data='o_fs_linhas' name='atende_marcas' tabindex='51' value='t' />
                        <label for="atende_outras_sim">Sim</label>&nbsp;&nbsp;
                        <input type="radio" id="atende_outras_nao" data='o_fs_linhas' name='atende_marcas' tabindex='52' value='f' />
                        <label for="atende_outras_nao">Não</label>
                    </fieldset>
                
                    <p class="erro"></p>
                    <fieldset id='o_fs_linhas' class='oculto fs_linhas'>
                        <legend>4.1. Linhas</legend>
                        <div class="oculto">
                            <label>void</label>
                            <input tabindex='300' type="checkbox" disabled checked='checked' />&nbsp;
                            <input type="text" tabindex='200' value='void' />
                            <br />
                        </div>
                        <label for="o_A">Automotiva</label>
                        <input id="o_A" name="o_A" tabindex='53' type="checkbox" />&nbsp;
                            <input type="text" tabindex='54' name='o_marcas_A' id="o_todas" maxlength='50' placeholder='Digite as marcas que atende desta linha' />
                            <br />
                        <label for="o_C">Compressores</label>
                        <input id="o_C" name="o_C" tabindex='55' type="checkbox" />&nbsp;
                            <input type="text" tabindex='56' name='o_marcas_C' id="o_todas" maxlength='50' placeholder='Digite as marcas que atende desta linha'  />
                            <br />
                        <label for="o_E">Eletrodomésticos</label>
                        <input id="o_E" name="o_E" tabindex='57' type="checkbox" />&nbsp;
                            <input type="text" tabindex='58' name='o_marcas_E'  maxlength='50' placeholder='Digite as marcas que atende desta linha'  />
                            <br />
                        <label for="o_F">Fechaduras</label>
                        <input id="o_F" name="o_F" tabindex='59' type="checkbox" />&nbsp;
                            <input type="text" tabindex='60' name='o_marcas_F'  maxlength='50' placeholder='Digite as marcas que atende desta linha'  />
                            <br />
                        <label for="o_B">Ferramentas Elétricas</label>
                        <input id="o_B" name="o_B" tabindex='61' type="checkbox" />&nbsp;
                            <input type="text" tabindex='62' name='o_marcas_B'  maxlength='50' placeholder='Digite as marcas que atende desta linha'  />
                            <br />
                        <label for="o_P">Ferramentas Pneumáticas</label>
                        <input id="o_P" name="o_P" tabindex='63' type="checkbox" />&nbsp;
                            <input type="text" tabindex='64' name='o_marcas_P'  maxlength='50' placeholder='Digite as marcas que atende desta linha'  />
                            <br />
                        <label for="o_D">Ferramentas Profissionais</label>
                        <input id="o_D" name="o_D" tabindex='65' type="checkbox" />&nbsp;
                            <input type="text" tabindex='66' name='o_marcas_D' maxlength='50' placeholder='Digite as marcas que atende desta linha'  />
                            <br />
                        <label for="o_GR">Geradores</label>
                        <input id="o_GR" name="o_GR" tabindex='67' type="checkbox" />&nbsp;
                            <input type="text" tabindex='68' name='o_marcas_GR'  maxlength='50' placeholder='Digite as marcas que atende desta linha'  />
                            <br />
                        <label for="o_LS">Laser</label>
                        <input id="o_LS" name="o_LS" tabindex='69' type="checkbox" />&nbsp;
                            <input type="text" tabindex='70' name='o_marcas_LS'  maxlength='50' placeholder='Digite as marcas que atende desta linha'  />
                            <br />
                        <label for="o_LV">Lavadoras de Pressão</label>
                        <input id="o_LV" name="o_LV" tabindex='71' type="checkbox" />&nbsp;
                            <input type="text" tabindex='72' name='o_marcas_LV'  maxlength='50' placeholder='Digite as marcas que atende desta linha'  />
                            <br />
                        <label for="o_M">Metais Sanitários</label>
                        <input id="o_M" name="o_M" tabindex='73' type="checkbox" />&nbsp;
                            <input type="text" tabindex='74' name='o_marcas_M'  maxlength='50' placeholder='Digite as marcas que atende desta linha'  />
                            <br />
                        <label for="o_GS">Motores à Gasolina</label>
                        <input id="o_GS" name="o_GS" tabindex='75' type="checkbox" />&nbsp;
                            <input type="text" tabindex='76' name='o_marcas_GS'  maxlength='50' placeholder='Digite as marcas que atende desta linha'  />
                            <br />
                    </fieldset>
                    <p class="erro"></p>
                    <fieldset id='o_info_treinamentos' class="oculto">
                        <legend>4.2. Treinamentos - Outras Marcas</legend>
                         <p>A sua empresa já recebeu treinamento para as linha(s) selecionada(s) na resposta anterior? 
                            Ou seja, as linhas credenciadas para outros fabricantes. Caso a resposta seja sim, selecione
                            também a(s) linha(s), informe o nome do técnico, data do treinamento e se o técnico treinado 
                            ainda trabalha na sua empresa selecione a opção "ativo".
                        </p>
                        <fieldset id='fs_o_treinou' class='bool'>
                            <input type="radio" class='required' id="o_tr_sim" name='outras_treinou' tabindex='77' data='fs_o_treino' value='t' />
                            <label for="o_tr_sim">Sim</label>&nbsp;&nbsp;
                            <input type="radio" id="o_tr_nao" name='outras_treinou' tabindex='78' value='f' data='fs_o_treino' />
                            <label for="o_tr_nao">Não</label>
                        </fieldset>
                        <p class="erro"></p>
                        <table id='fs_o_treino' class='tbl_treino oculto' align='center'>
                            <caption>Dados dos Treinamentos</caption>
                            <thead>
                                <tr style='height:20px;vertical-align:middle'>
                                    <th style='width: 200px'>Linha</th>
                                    <th style='width: 230px'>Técnico</th>
                                    <th style='width: 120px'>Data</th>
                                    <th style='width:  60px'title='O técnico ainda trabalha no Posto?'>Ativo  <sup>?</sup></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input name="o_tr_A" id="o_tr_A" tabindex='79' type="checkbox" />&nbsp;<label for="o_tr_A">Automotiva</label></td>
                                    <td><input type="text" maxlength='40' style='width: 230px' tabindex='80' name='o_tr_A_tecnico' /></td>
                                    <td><input type="text" maxlength='40' style='width: 100px' tabindex='81' name='o_tr_A_data' /></td>
                                    <td><input type="checkbox" name="o_tr_A_ativo" tabindex='82' /></td>
                                </tr>
                                <tr>
                                    <td><input name="o_tr_C" id="o_tr_C" tabindex='83' type="checkbox" />&nbsp;<label for="o_tr_C">Compressores</label></td>
                                    <td><input type="text" maxlength='40' style='width: 230px' tabindex='84' name='o_tr_C_tecnico' /></td>
                                    <td><input type="text" maxlength='40' style='width: 100px' tabindex='85' name='o_tr_C_data' /></td>
                                    <td><input type="checkbox" name="o_tr_C_ativo" tabindex='86' /></td>
                                </tr>
                                <tr>
                                    <td><input name="o_tr_E" id="o_tr_E" tabindex='87' type="checkbox" />&nbsp;<label for="o_tr_E">Eletrodomésticos</label></td>
                                    <td><input type="text" maxlength='40' style='width: 230px' tabindex='88' name='o_tr_E_tecnico' /></td>
                                    <td><input type="text" maxlength='40' style='width: 100px' tabindex='89' name='o_tr_E_data' /></td>
                                    <td><input type="checkbox" name="o_tr_E_ativo" tabindex='90' /></td>
                                </tr>
                                <tr>
                                    <td><input name="o_tr_F" id="o_tr_F" tabindex='91' type="checkbox" />&nbsp;<label for="o_tr_F">Fechaduras</label></td>
                                    <td><input type="text" maxlength='40' style='width: 230px' tabindex='92' name='o_tr_F_tecnico' /></td>
                                    <td><input type="text" maxlength='40' style='width: 100px' tabindex='93' name='o_tr_F_data' /></td>
                                    <td><input type="checkbox" name="o_tr_F_ativo" tabindex='94' /></td>
                                </tr>
                                <tr>
                                    <td><input name="o_tr_B" id="o_tr_B" tabindex='95' type="checkbox" />&nbsp;<label for="o_tr_B">Ferramentas Elétricas</label></td>
                                    <td><input type="text" maxlength='40' style='width: 230px' tabindex='96' name='o_tr_B_tecnico' /></td>
                                    <td><input type="text" maxlength='40' style='width: 100px' tabindex='97' name='o_tr_B_data' /></td>
                                    <td><input type="checkbox" name="o_tr_B_ativo" tabindex='98' /></td>
                                </tr>
                                <tr>
                                    <td><input name="o_tr_P" id="o_tr_P" tabindex='99' type="checkbox" />&nbsp;<label for="o_tr_P">Ferramentas Pneumáticas</label></td>
                                    <td><input type="text" maxlength='40' style='width: 230px' tabindex='100' name='o_tr_P_tecnico' /></td>
                                    <td><input type="text" maxlength='40' style='width: 100px' tabindex='101' name='o_tr_P_data' /></td>
                                    <td><input type="checkbox" name="o_tr_P_ativo" tabindex='102' /></td>
                                </tr>
                                <tr>
                                    <td><input name="o_tr_D" id="o_tr_D" tabindex='103' type="checkbox" />&nbsp;<label for="o_tr_FP">Ferramentas Profissionais</label></td>
                                    <td><input type="text" maxlength='40' style='width: 230px' tabindex='104' name='o_tr_D_tecnico' /></td>
                                    <td><input type="text" maxlength='40' style='width: 100px' tabindex='105' name='o_tr_D_data' /></td>
                                    <td><input type="checkbox" name="o_tr_D_ativo" tabindex='106' /></td>
                                </tr>
                                <tr>
                                    <td><input name="o_tr_GR" id="o_tr_GR" tabindex='107' type="checkbox" />&nbsp;<label for="o_tr_GR">Geradores</label></td>
                                    <td><input type="text" maxlength='40' style='width: 230px' tabindex='108' name='o_tr_GR_tecnico' /></td>
                                    <td><input type="text" maxlength='40' style='width: 100px' tabindex='109' name='o_tr_GR_data' /></td>
                                    <td><input type="checkbox" name="o_tr_V_ativo" tabindex='110' /></td>
                                </tr>
                                <tr>
                                    <td><input name="o_tr_LS" id="o_tr_LS" id="o_tr_LS" tabindex='111' type="checkbox" />&nbsp;<label for="o_tr_LS">Laser</label></td>
                                    <td><input type="text" maxlength='40' style='width: 230px' tabindex='112' name='o_tr_LS_tecnico' /></td>
                                    <td><input type="text" maxlength='40' style='width: 100px' tabindex='113' name='o_tr_LS_data' /></td>
                                    <td><input type="checkbox" name="o_tr_LS_ativo" tabindex='114' /></td>
                                </tr>
                                <tr>
                                    <td><input name="o_tr_LV" id="o_tr_LV" tabindex='115' type="checkbox" />&nbsp;<label for="o_tr_LV">Lavadoras de Pressão</label></td>
                                    <td><input type="text" maxlength='40' style='width: 230px' tabindex='116' name='o_tr_LV_tecnico' /></td>
                                    <td><input type="text" maxlength='40' style='width: 100px' tabindex='117' name='o_tr_LV_data' /></td>
                                    <td><input type="checkbox" name="o_tr_LV_ativo" tabindex='118' /></td>
                                </tr>
                                <tr>
                                    <td><input name="o_tr_M" id="o_tr_M" tabindex='119' type="checkbox" />&nbsp;<label for="o_tr_M">Metais Sanitários</label></td>
                                    <td><input type="text" maxlength='40' style='width: 230px' tabindex='120' name='o_tr_M_tecnico' /></td>
                                    <td><input type="text" maxlength='40' style='width: 100px' tabindex='121' name='o_tr_M_data' /></td>
                                    <td><input type="checkbox" name="o_tr_M_ativo" tabindex='122' /></td>
                                </tr>
                                <tr>
                                    <td><input name="o_tr_GS" id="o_tr_GS" tabindex='123' type="checkbox" />&nbsp;<label for="o_tr_GS">Motores à Gasolina</label></td>
                                    <td><input type="text" maxlength='40' style='width: 230px' tabindex='124' name='o_tr_GS_tecnico' /></td>
                                    <td><input type="text" maxlength='40' style='width: 100px' tabindex='125' name='o_tr_GS_data' /></td>
                                    <td><input type="checkbox" name="o_tr_GS_ativo" tabindex='126' /></td>
                                </tr>
                            </tbody>
                        </table>
                    </fieldset>
                    <p class="erro"></p>
            </fieldset>
            <fieldset id='fs_banco'>
                <legend>5. Dados Bancários</legend>
                <p>Por favor, verifique os dados bancários abaixo. Esses são os dados cadastrados para
                a sua empresa junto à B&D. Solicitamos que faça a confirmação se estiverem corretos
                e apenas se houve alguma alteração ou existir algum erro nos dados faça a correção
                informando os dados corretos. É importante que a conta informada seja jurídica e esteja
                em nome da empresa, caso contrário o sistema recusará a operação no momento do
                depósito, o que causará atraso no pagamento.</p><br />
                <fieldset id='fs_info_banco'>
                    <legend>5.1. Dados cadastrados para a sua empresa:</legend>
                    <dl>
                        <dt>Banco:</dt>
                        <dd> <?php if (isset($campo_banco)) {
                        	echo "$campo_banco ($campo_banco_nome)"; 
                        	$confirmar = 't';
                        	}else{
                        		$confirmar = 'f';
                        	}?></dd>
                        <br />
                        <dt>Agência:</dt>
                        <dd><?=$campo_agencia?></dd>
                        <dt>Conta:</dt>
                        <dd><?=$campo_conta?></dd>
                    </dl>
                    <p>Confirmar os dados?</p>
                    <fieldset class='bool'>
                        <input type="radio" class='required' id="banco_sim" name='banco_ok' tabindex='127' data='dados_banco' value='<?=$confirmar?>' />
                        <label for="banco_sim">Sim</label>&nbsp;&nbsp;
                        <input type="radio" id="banco_nao" name='banco_ok' tabindex='128' data='dados_banco' value='f' />
                        <label for="banco_nao">Não</label>
                    </fieldset>
                    <p class="erro"></p>
                </fieldset>
                <fieldset id="dados_banco" class='oculto'>
                    <legend>5.2. Alterar Dados Bancários</legend>
                    <label for="banco">Banco</label>
					<?
		            $sqlB = "SELECT codigo, nome FROM tbl_banco ORDER BY codigo";
		            $resB = pg_query($con,$sqlB);
		            if (pg_num_rows($resB) > 0) {
		                echo "<select class='frm' name='banco' id='banco' size='1'";
		                echo ">";
		                echo "<option value=''></option>";
		                for ($x = 0 ; $x < pg_num_rows($resB) ; $x++) {
		                    $aux_banco     = trim(pg_fetch_result($resB,$x,codigo));
		                    $aux_banconome = pg_fetch_result($resB,$x,nome);
		                    echo "<option value='" . $aux_banco . "'";
		                    echo ">" . $aux_banco . " - " . $aux_banconome . "</option>";
		                }
		                echo "</select>";
		            }
		            ?>
		            <input type='hidden' id='banco_nome' name='banco_nome' />
                    <br />
                    <label for="agencia" style="margin-top: 6px;">Agência</label>
                        <input type="text" name="banco_agencia" id="banco_agencia" onKeyUp="somente_numero(this);" style='width:100px;margin-right : 52px;' tabindex='131' />
                    <label for="conta" style='width:80px;'>Nº de Conta</label>
                        <input type="text" name="banco_conta" id="banco_conta" maxlength='10' onKeyUp="somente_numero(this);" style='width:120px' tabindex='132' />
                    <br>
                </fieldset>
                <p class="erro"></p>
                <fieldset style="border:0;" id='responsavel'><label for="responsavel_questionario" style="width:80%;" class='normal'>Informe o nome do responsável pelas respostas desse questionário:</label>
                    <input type="text" name="responsavel_questionario" maxlength="30" id="responsavel_questionario" tabindex='133' />
                </fieldset>
                <p class="erro"></p>
            </fieldset>
            <div id="msgErro"></div>
            <div id='acoes' style='text-align:center;margin:auto;border:0;'>
                <button type='button' id="btnGravar" tabindex='134' style='cursor:pointer'>Gravar</button>
                <button type='reset'  tabindex='135' style='cursor:pointer'>Limpar Formulário</button>
            </div>
        </fieldset>
    </form>
    </div>
</body>
</html>	
