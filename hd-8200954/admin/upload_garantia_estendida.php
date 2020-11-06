<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
$layout_menu = "cadastro";
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include '../class/AuditorLog.php';

$consultar = $_REQUEST['consultar'];
$gerar_excel = $_REQUEST['gerar_excel'];

$array_estados = $array_estados();                            

if ($gerar_excel) {

    $lista_pecas_csv = array();

	$data_inicial = $_POST['data_inicial'];  
	$data_final   = $_POST['data_final'];
	$data_compra  = $_POST['data_compra']; 
	$nome         = $_POST['nome']; 
	$cpf          = $_POST['cpf'];
	$cpf          = str_replace(['.','-'], "", $cpf); 
	$email        = $_POST['email']; 
	$cep          = $_POST['cep']; 
	$cep          = str_replace('-', "", $cep);
	$estado       = $_POST['estado'];
	$cidade       = $_POST['cidade'];
	$revenda_cnpj = $_POST['revenda_cnpj'];
	$revenda_nome = $_POST['revenda_nome'];
	$modelo       = $_POST['modelo'];
	$nf           = $_POST['nf'];

	if (!empty($data_inicial) && !empty($data_final)) {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		$aux_data_inicial = "$yi-$mi-$di";
		list($df, $mf, $yf) = explode("/", $data_final);
		$aux_data_final = "$yf-$mf-$df";
		
		$cond_where = "AND tbl_cliente_garantia_estendida.data_input BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
	}
	
	if (!empty($data_compra)) { 
		list($di, $mi, $yi) = explode("/", $data_compra);
		$aux_data_compra = "$yi-$mi-$di";

		$cond_where .= "AND tbl_cliente_garantia_estendida.data_compra = '$aux_data_compra' ";
	}

	if (!empty($nome)) { 
		$nome_pesquisa = strtoupper($nome);
		$cond_where .= "AND UPPER(tbl_cliente_garantia_estendida.nome) = '$nome_pesquisa' ";
	}

	if (!empty($cpf)) {
		$cond_where .= "AND tbl_cliente_garantia_estendida.cpf = '$cpf' ";
	}

	if (!empty($email)) {
		$cond_where .= "AND tbl_cliente_garantia_estendida.email = '$email' ";
	}

	if (!empty($cep)) { 
		$cond_where .= "AND tbl_cliente_garantia_estendida.cep = '$cep' ";
	}
	
	if (!empty($estado)) {
		$estado_pesquisa = strtoupper($estado);
		$cond_where .= "AND UPPER(tbl_cliente_garantia_estendida.estado) = '$estado_pesquisa' ";
	}
	
	if (!empty($cidade)) {
		$cidade_pesquisa = strtoupper($cidade);
		$cond_where .= "AND UPPER(tbl_cliente_garantia_estendida.cidade) = '$cidade_pesquisa' ";
	}

	if (!empty($revenda_cnpj)) {
		$revenda_cnpj = str_replace(['.','-','/'], "", $revenda_cnpj);
		
		$sql_revenda = "SELECT revenda FROM tbl_revenda WHERE cnpj = '$revenda_cnpj'";
		$res_revenda = pg_query($con, $sql_revenda);
		$revenda = pg_fetch_result($res_revenda, 0, 'revenda');

		if (!empty($revenda)) {
			$cond_where .= "AND tbl_revenda.revenda = $revenda ";		
		}
	}
	
	if (!empty($revenda_nome)) {
		$revenda_nome_pesquisa = strtoupper($revenda_nome); 
		$cond_where .= "AND UPPER(tbl_cliente_garantia_estendida.revenda_nome) = '$revenda_nome_pesquisa' ";
	}
	
	if (!empty($modelo)) { 
		$cond_where .= "AND tbl_cliente_garantia_estendida.campos_adicionais->>'modelo_produto' = '$modelo' ";
	}

	if (!empty($nf)) { 
		$nf_pesquisa = strtoupper($nf);
		$cond_where .= "AND UPPER(tbl_cliente_garantia_estendida.nota_fiscal) = '$nf_pesquisa'";
	}

	$sql = "SELECT tbl_cliente_garantia_estendida.nome, 
				   tbl_cliente_garantia_estendida.cpf,
				   tbl_cliente_garantia_estendida.endereco,
				   tbl_cliente_garantia_estendida.numero,
				   tbl_cliente_garantia_estendida.cep,
				   tbl_cliente_garantia_estendida.cidade,
				   tbl_cliente_garantia_estendida.revenda_nome,
				   tbl_cliente_garantia_estendida.nota_fiscal,
				   tbl_cliente_garantia_estendida.data_compra,
				   tbl_cliente_garantia_estendida.estado,
				   tbl_cliente_garantia_estendida.fone,
				   tbl_cliente_garantia_estendida.motivo,
				   tbl_cliente_garantia_estendida.email,
				   tbl_cliente_garantia_estendida.campos_adicionais,
				   tbl_revenda.cnpj
		    FROM tbl_cliente_garantia_estendida
		    LEFT JOIN tbl_revenda USING(revenda)
		    WHERE tbl_cliente_garantia_estendida.fabrica = $login_fabrica
		    $cond_where
		    ORDER BY tbl_cliente_garantia_estendida.data_input";
	$res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $conteudo_csv    = "CPF Cliente;Nome Cliente;Sobrenome;Data Nascimento Cliente;Cep;Estado;Cidade;EndereÁo;Bairro;N˙mero;Fone;Email;Revenda CNPJ;Revenda Nome;Data Compra;Nota Fiscal;Modelo;Data Date submitted;Time Submitted;Page Name;Motivo;Termo\n";
        $data     = date("d-m-Y-H:i");
        $fileName = "garantia-estendida-{$data}.csv";
        $file     = fopen("/tmp/{$fileName}", "w");

        foreach (pg_fetch_all($res) as $key => $rows) {
        	$r = str_replace("\\\\", "\\", $rows['campos_adicionais']);
        	$camp = json_decode($r, true);
        	$xdata_compra = $rows['data_compra'];

	        $data_formatar = date_create_from_format('Y-m-d', $xdata_compra);
	        $xdata_compra = date_format($data_formatar, 'd/m/Y');
						
            $conteudo_csv .= $rows['cpf'].";".utf8_decode($rows['nome']).";".utf8_decode($camp['sobrenome']).";".$camp['data_nascimento'].";".$rows['cep'].";".$rows['estado'].";".utf8_decode($rows['cidade']).";".utf8_decode($rows['endereco']).";".utf8_decode($camp['bairro']).";".$rows['numero'].";".$rows['fone'].";".$rows['email'].";".$rows['cnpj'].";".utf8_decode($rows['revenda_nome']).";".$xdata_compra.";".$rows['nota_fiscal'].";".utf8_decode($camp['modelo_produto']).";".$camp['date_submitted'].";".$camp['time_submitted'].";".utf8_decode($camp['page_name']).";".utf8_decode($rows['motivo']).";".utf8_decode($camp['termo_de_compromisso'])."\n";
        }
        fwrite($file, $conteudo_csv);


        fclose($file);
        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");

            echo "xls/{$fileName}";
        }
        exit;
	}
}

if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados)) {
        $sql = "SELECT DISTINCT * FROM (
                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
                    UNION (
                        SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                    )
                ) AS cidade
                ORDER BY cidade ASC";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("Nenhuma cidade encontrada para o estado: {$estado}"));
        }
    } else {
        $retorno = array("error" => utf8_encode("Estado n„o encontrado"));
    }

    exit(json_encode($retorno));
}

if (isset($_POST['ajax_busca_cep']) && !empty($_POST['cep'])) {
    require_once __DIR__.'/classes/cep.php';

    $cep = $_POST['cep'];

    try {
        $retorno = CEP::consulta($cep);
        $retorno = array_map('utf8_encode', $retorno);
    } catch(Exception $e) {
        $retorno = array("error" => utf8_encode($e->getMessage()));
    }

    exit(json_encode($retorno));
}

if ($_POST["btn_consultar"] == "consultar") {
	
	$fez_consulta = true;
	$msg_erro     = [];
	$data_inicial = $_POST['data_inicial'];  
	$data_final   = $_POST['data_final'];
	$data_compra  = $_POST['data_compra']; 
	$nome         = $_POST['nome']; 
	$cpf          = $_POST['cpf']; 
	$cpf          = str_replace(['.','-'], "", $cpf);
	$email        = $_POST['email']; 
	$cep          = $_POST['cep']; 
	$cep          = str_replace('-', "", $cep);
	$estado       = $_POST['estado'];
	$cidade       = $_POST['cidade'];
	$revenda_cnpj = $_POST['revenda_cnpj'];
	$revenda_nome = $_POST['revenda_nome'];
	$modelo       = $_POST['modelo'];
	$nf           = $_POST['nf'];

	if (empty($data_inicial) && empty($data_final) && empty($data_compra)) {
		$msg_erro['campos'] = "datas";
		$msg_erro['msg'] = "Preencha os campos obrigatÛrios";
	} else {
		if($data_inicial && $data_final) {
	        list($di, $mi, $yi) = explode("/", $data_inicial);

	        if(!checkdate($mi,$di,$yi)) {
	            $msg_erro['campos'] = "datas";
				$msg_erro['msg'] = "Preencha os campos obrigatÛrios";
	        }

	        list($df, $mf, $yf) = explode("/", $data_final);

	        if(!checkdate($mf,$df,$yf)) {
	        	$msg_erro['campos'] = "datas";
				$msg_erro['msg'] = "Preencha os campos obrigatÛrios";
	        }

	        $aux_data_inicial = "$yi-$mi-$di";
	        $aux_data_final = "$yf-$mf-$df";

	        if(count($msg_erro)==0){
	            if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
	                $msg_erro['campos'] = "datas";
					$msg_erro['msg'] = "Preencha os campos obrigatÛrios";
	            }
	        }

	    } else if(($data_inicial && !$data_final) || (!$data_inicial && $data_final)){
	        $msg_erro['campos'] = "datas";
			$msg_erro['msg'] = "Preencha os campos obrigatÛrios";
	    }

	    if ($data_compra) {
			list($di, $mi, $yi) = explode("/", $data_compra);

	        if(!checkdate($mi,$di,$yi)) {
	            $msg_erro['campos'] = "datas";
				$msg_erro['msg'] = "Preencha os campos obrigatÛrios";
	        }

	        $aux_data_compra = "$yi-$mi-$di";	    	
	    }
	}

	if (count($msg_erro) == 0) {

		if (!empty($data_inicial) && !empty($data_final)) {
			$cond_where = "AND data_input BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
		}
		
		if (!empty($data_compra)) { 
			$cond_where .= "AND data_compra = '$aux_data_compra' ";
		}

		if (!empty($nome)) { 
			$nome_pesquisa = strtoupper($nome);
			$cond_where .= "AND UPPER(nome) = '$nome_pesquisa' ";
		}

		if (!empty($cpf)) {
			$cond_where .= "AND cpf = '$cpf' ";
		}

		if (!empty($email)) {
			$cond_where .= "AND email = '$email' ";
		}

		if (!empty($cep)) { 
			$cond_where .= "AND cep = '$cep' ";
		}
		
		if (!empty($estado)) {
			$estado_pesquisa = strtoupper($estado);
			$cond_where .= "AND UPPER(estado) = '$estado_pesquisa' ";
		}
		
		if (!empty($cidade)) {
			$cidade_pesquisa = strtoupper($cidade);
			$cond_where .= "AND UPPER(cidade) = '$cidade_pesquisa' ";
		}

		if (!empty($revenda_cnpj)) {
			$revenda_cnpj = str_replace(['.','-','/'], "", $revenda_cnpj);
			
			$sql_revenda = "SELECT revenda FROM tbl_revenda WHERE cnpj = '$revenda_cnpj'";
			$res_revenda = pg_query($con, $sql_revenda);
			$revenda = pg_fetch_result($res_revenda, 0, 'revenda');

			if (!empty($revenda)) {
				$cond_where .= "AND revenda = $revenda ";		
			}
		}
		
		if (!empty($revenda_nome)) { 
			$revenda_nome_pesquisa = strtoupper($revenda_nome);
			$cond_where .= "AND UPPER(revenda_nome) = '$revenda_nome_pesquisa' ";
		}
		
		if (!empty($modelo)) { 
			$cond_where .= "AND campos_adicionais->>'modelo_produto' = '$modelo' ";
			
			
		}

		if (!empty($nf)) {
			$nf_pesquisa = strtoupper($nf); 
			$cond_where .= "AND UPPER(nota_fiscal) = '$nf_pesquisa'";
		}


		$sql = "SELECT nome, 
					   cpf,
					   revenda_nome,
					   data_compra
			    FROM tbl_cliente_garantia_estendida
			    WHERE fabrica = $login_fabrica
			    $cond_where
			    ORDER BY data_input LIMIT 500";
			    //echo "<pre>"; print_r($sql); echo "</pre>"; die();
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) > 0) {
			$result_dados = pg_fetch_all($res);
		}
	}
}

if ($_POST["upload_arq"] == "gravar") {

    $msg_erro = "";
    $erro_csv = array();
    $log      = array();
    $upload   = $_FILES["arquivo_uploader"];

    if (empty($upload["name"])) {
        $msg_erro = "Selecione um Arquivo";
    }

    $conteudo = file_get_contents($upload["tmp_name"]);
    $conteudo = explode("\n", $conteudo);

    if (count($conteudo) == 0) {
        $msg_erro = "Arquivo sem conte˙do";
    }

    if (empty($msg_erro)) {
        pg_query($con, "BEGIN");

		foreach ($conteudo as $key => $value) {
			$val = explode(";", $value);

			if ($val[0] == 'date_submitted' || $val[1] == 'time_submitted' || empty($val[0]) || trim($val[0]) == "") {
        		continue;
        	}

        	if (count($val) <> 21 ) {
        		$msg_erro = "Conte˙do do Arquivo Inv·lido";
        		pg_query($con, "ROLLBACK");
        		break;
        	}
        	$end = explode(",", $val[8]);

        	if (empty($msg_erro)) {
        		$campos_add = [];

        		$campos_add['date_submitted']        = trim($val[0]);
				$campos_add['time_submitted']        = trim($val[1]);
				$campos_add['page_name']             = trim(utf8_encode((mb_check_encoding($val[2], "UTF-8")) ? utf8_decode($val[2]) : $val[2]));
				$campos_add['sobrenome']             = trim(utf8_encode((mb_check_encoding($val[3], "UTF-8")) ? utf8_decode($val[3]) : $val[3]));
				$cidade                              = trim(utf8_encode((mb_check_encoding($val[4], "UTF-8")) ? utf8_decode($val[4]) : $val[4]));
				$cep                                 = trim($val[5]);
				$cep                                 = str_replace(['.','-'], "", $cep);
				$email                               = trim($val[6]);
				$campos_add['modelo_produto']        = trim(utf8_encode((mb_check_encoding($val[7], "UTF-8")) ? utf8_decode($val[7]) : $val[7]));
				$end_num                             = explode(",", $val[8]);
				$endereco                            = trim(utf8_encode((mb_check_encoding($end_num[0], "UTF-8")) ? utf8_decode($end_num[0]) : $end_num[0]));
				$numero                              = trim($end_num[1]);
				$campos_add['bairro']                = trim(utf8_encode((mb_check_encoding($val[9], "UTF-8")) ? utf8_decode($val[9]) : $val[9]));
				$cpf                                 = trim($val[10]);
				$cpf                                 = str_replace(['.','-'], "", $cpf);
				$revenda_nome                        = trim(utf8_encode((mb_check_encoding($val[11], "UTF-8")) ? utf8_decode($val[11]) : $val[11])); // nome_da_loja_da_compra
				$campos_add['data_nascimento']       = trim($val[12]);
				$fone                                = trim($val[13]);
				$estado                              = trim($val[14]);
				$motivo                              = trim(utf8_encode((mb_check_encoding($val[15], "UTF-8")) ? utf8_decode($val[15]) : $val[15])); // aceito)_
				$data_compra                         = trim($val[16]);
		        $data_c                              = date_create_from_format('d/m/Y', $data_compra);
		        $data_compra                         = date_format($data_c, 'Y-m-d');
				$nota_fiscal                         = trim($val[17]);
				$nome                                = trim(utf8_encode((mb_check_encoding($val[18], "UTF-8")) ? utf8_decode($val[18]) : $val[18]));
				$campos_add['termo_de_compromisso']  = trim(utf8_encode((mb_check_encoding($val[19], "UTF-8")) ? utf8_decode($val[19]) : $val[19]));
				$rev                                 = str_replace(['.','-','/'], "", $val[20]);
				$revenda_cnpj                        = trim($rev);
				
				$campos_add = "'".json_encode($campos_add)."'";
				$campos_add = str_replace("\\", "\\\\", $campos_add);
				
				$sql_revenda = "SELECT revenda FROM tbl_revenda WHERE cnpj = '$revenda_cnpj'";
				$res_revenda = pg_query($con, $sql_revenda);
				if (pg_num_rows($res_revenda) > 0) {
					$revenda = pg_fetch_result($res_revenda, 0, 'revenda');
				} else {
					$sql = "INSERT INTO tbl_revenda (cnpj, nome) VALUES ('$revenda_cnpj', '$revenda_nome') RETURNING revenda";
					$res = pg_query($con, $sql);
					if (!pg_last_error()) {
						$revenda = pg_fetch_result($res, 0, 'revenda');
					}
				}

				$sql = "INSERT INTO tbl_cliente_garantia_estendida ( 
																		cidade, 
																		cep, 
																		email, 
																		endereco, 
																		numero, 
																		cpf, 
																		revenda_nome, 
																		fone, 
																		estado,
																		motivo, 
																		data_compra, 
																		nota_fiscal, 
																		nome, 
																		fabrica, 
																		garantia_mes,
																		numero_serie,
																		campos_adicionais,
																		revenda
																	) VALUES (
																		'$cidade',
																		'$cep',
																		'$email',
																		'$endereco',
																		'$numero',
																		'$cpf',
																		'$revenda_nome',
																		'$fone',
																		'$estado',
																		'$motivo',
																		'$data_compra',
																		'$nota_fiscal',
																		'$nome',
																		$login_fabrica,
																		6,
																		'',
																		$campos_add,
																		$revenda
																	) ";
				$res = pg_query($con, $sql);
				if (pg_last_error()) {
					$msg_erro = "Erro ao Gravar os Dados";
					pg_query($con, "ROLLBACK");
					break;
				}        		
        	}
		}

		if (empty($msg_erro)) {
            pg_query($con, "COMMIT");
            header("Location: upload_garantia_estendida.php?msg=ok");
		} else {
			pg_query($con, "ROLLBACK");

		}
    }
}

$title     = "Upload de Garantia Estendida";
$cabecalho = "Upload de Garantia Estendida";

include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "dataTable",
    "shadowbox",
    "datepicker",
    "mask"
);

include 'plugin_loader.php';
?>
<script>
    $(function(){

    	$("#consultar").click(function() {
    		window.location.href = "upload_garantia_estendida.php?consultar=ok";
    	});

    	$("#upload").click(function() {
    		window.location.href = "upload_garantia_estendida.php";
    	});

    	$("#data_inicial").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$("#data_final").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$("#data_compra").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$("#cep").mask("99999-999");
		$("#cpf").mask("999.999.999-99");
		 $("#revenda_cnpj").mask("99.999.999/9999-99");

		$("#estado").change(function() {
	        busca_cidade($(this).val());
	    });

	    $("#cep").blur(function() {
	        if ($(this).attr("readonly") == undefined) {
	            busca_cep($(this).val());
	        }
	    });

        Shadowbox.init();
        $.dataTableLoad({
            table: "#content"
        });

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

    });

    function retorna_revenda(retorno) {
	    $("#revenda_nome").val(retorno.razao);
	    $("#revenda_cnpj").val(retorno.cnpj);
	}

    function retiraAcentos(palavra){
	    if (!palavra) {
	        return "";
	    }

	    var com_acento = '·‡„‚‰ÈËÍÎÌÏÓÔÛÚıÙˆ˙˘˚¸Á¡¿√¬ƒ…» ÀÕÃŒœ”“’÷‘⁄Ÿ€‹«';
	    var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
	    var newPalavra = "";

	    for(i = 0; i < palavra.length; i++) {
	        if (com_acento.search(palavra.substr(i, 1)) >= 0) {
	            newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
	        } else {
	            newPalavra += palavra.substr(i, 1);
	        }
	    }

	    return newPalavra.toUpperCase();
	}

	function busca_cidade(estado, cidade) {
	    $("#cidade").find("option").first().nextAll().remove();

	    if (estado.length > 0) {
	        $.ajax({
	            async: false,
	            url: "upload_garantia_estendida.php",
	            type: "POST",
	            data: { ajax: true, ajax_busca_cidade: true, estado: estado },
	            beforeSend: function() {
	                if ($("#cidade").next("img").length == 0) {
	                    $("#cidade").hide().after($("<img />", { src: "../imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
	                }
	            },
	            complete: function(data) {
	                data = $.parseJSON(data.responseText);

	                if (data.error) {
	                    alert(data.error);
	                } else {
	                    $.each(data.cidades, function(key, value) {
	                        var option = $("<option></option>", { value: value, text: value });
	                        $("#cidade").append(option);
	                    });
	                }

	                $("#cidade").show().next().remove();
	            }
	        });
	    }

	    if(typeof cidade != "undefined" && cidade.length > 0){

	        $("#cidade option[value='"+cidade+"']").attr('selected','selected');

	    }

	}

	function busca_cep(cep, method) {
	    if (cep.length > 0) {
	        var img = $("<img />", { src: "../imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

	        if (typeof method == "undefined" || method.length == 0) {
	            method = "webservice";

	            $.ajaxSetup({
	                timeout: 3000
	            });
	        } else {
	            $.ajaxSetup({
	                timeout: 5000
	            });
	        }

	        $.ajax({
	            async: true,
	            url: "../ajax_cep.php",
	            type: "GET",
	            data: { ajax: true, cep: cep, method: method },
	            beforeSend: function() {
	                $("#estado").next("img").remove();
	                $("#cidade").next("img").remove();

	                $("#estado").hide().after(img.clone());
	                $("#cidade").hide().after(img.clone());
	            },
	            error: function(xhr, status, error) {
	                busca_cep(cep, "database");
	            },
	            success: function(data) {
	                results = data.split(";");

	                if (results[0] != "ok") {
	                    alert(results[0]);
	                    $("#cidade").show().next().remove();
	                } else {
	                    $("#estado").val(results[4]);

	                    busca_cidade(results[4]);
	                    results[3] = results[3].replace(/[()]/g, '');

	                    $("#cidade").val(retiraAcentos(results[3]).toUpperCase());

	                }

	                $("#estado").show().next().remove();

	                $.ajaxSetup({
	                    timeout: 0
	                });
	            }
	        });
	    }
	}

</script>

<?php
	if (is_array($msg_erro) && count($msg_erro) > 0) {
		$msg = $msg_erro['msg'];
	} else {
		$msg = $msg_erro;
	}

    if (!empty($msg)) {
    	echo "<div class='row-fluid'>";
        echo '<div class="alert alert-danger"><h4>'.$msg.'</h4></div>';
    	echo "</div>";
    }
?>
<?php
    if (isset($_GET['msg'])) {
    	echo "<div class='row-fluid'>";
        echo '<div class="alert alert-success"><h4>Gravado com Sucesso</h4></div>';
    	echo "</div>";
    }
?>
<?php if (!$consultar) { ?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatÛrios </b>
</div>
<form name='frm_upload' method='post' action='<?=$PHP_SELF?>' class="form-search form-inline" enctype="multipart/form-data">
    <div id="div_consulta" class="tc_formulario container">
        <div class="titulo_tabela">Upload de Garantia Estendida</div>
        <br>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class="alert">
                    <p>A extens„o do arquivo para Upload deve ser .CSV</p>
                    <p>
                    	Layout do anexo com delimitador 'ponto e vÌrgula': 
                    	<b>
                    		date_submitted;time_submitted;page_name;sobrenome;cidade_;cep_;
                    		email;modelo_do_produto_;endereÁo__rua_e_complemento_;bairro_;cpf_;
                    		nome_da_loja_da_compra_;data_de_nascimento_;celular_;estado_;
                    		aceito_;data_da_compra_;nota_fiscal_;nome_;termo_de_compromisso_;cnpj_revenda	
                    	</b>
                    </p>
                </div>
            </div>
        </div>
        <div class="row-fluid">
        	<div class="span2"></div>
            <div class='span8'>
				<div class='control-group'>
					<label class='control-label' for='arquivo_uploader'>Arquivo CSV</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="file" id="arquivo_uploader" name="arquivo_uploader" class='span12' />
						</div>
					</div>
				</div>
			</div>
            <div class="span2"></div>
        </div>
        <br /><br />
        <div class="row-fluid">
        	<div class="span2"></div>
        	<div class="span8">
        		<div class="tac span12">
        			<button type="submit" class="btn btn-info" name="upload_arq" value="gravar">Upload do Arquivo</button>
        			<button type="button" class="btn" id="consultar" name="consultar">Consultar</button>
        		</div>
        	</div>
        </div>
    </div>
</form>

<?php if (!empty($log)) { ?>
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <caption class='titulo_coluna'><h4>Demandas Atualizadas</h4></caption>
        <thead>
            <tr class='titulo_coluna' >
                <th>CÛdigo</th>
                <th class="tal">DescriÁ„o</th>
                <th>Qtde Demanda</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        if (isset($log["pecas_atualizadas"])) {  
            foreach ($log["pecas_atualizadas"] as $k => $rows) {
        ?>
        <tr>
            <td class='tac'><?php echo $rows["referencia"];?></td>
            <td class='tal'><?php echo $rows["descricao"];?></td>
            <td class='tac'><?php echo $rows["qtd_demanda"];?></td>
            <td class='tac'>
                <span class="label label-success"><?php echo $rows["status"];?></span>
            </td>
        </tr>
        <?php }}?>
        <?php 
        if (isset($log["pecas_nao_atualizadas"])) {  
            foreach ($log["pecas_nao_atualizadas"] as $k => $rows) {
        ?>
        <tr>
            <td class='tac'><?php echo $rows["referencia"];?></td>
            <td class='tal'><?php echo $rows["descricao"];?></td>
            <td class='tac'><?php echo $rows["qtd_demanda"];?></td>
            <td class='tac'>
                <span class="label label-important"><?php echo $rows["status"];?></span>
            </td>
        </tr>
        <?php }}?>
        <?php 
        if (isset($log["pecas_nao_encontradas"])) {  
            foreach ($log["pecas_nao_encontradas"] as $rows) {
        ?>
        <tr>
            <td class='tal' colspan="3">CÛdigo de ReferÍncia: <b><?php echo $rows;?></b></td>
            <td class='tac' colspan="2">
                <span class="label label-important">PeÁa n„o encontrada</span>
            </td>
        </tr>
        <?php }}?>
        </tbody>
    </table>
<?php }
} 
?>

<br />

<?php if ($consultar) { ?>
<form name='frm_pesquisa_cadastro' method='post' action='upload_garantia_estendida.php?consultar=true' class="form-search form-inline tc_formulario" enctype="multipart/form-data">

    <div id="div_consulta" class="container">
        <div class="titulo_tabela">Par‚metros de Pesquisa</div>
        <br>
 		<div class='row-fluid'>
			<div class='span2'></div>
			<div class="span3">
				<div class="span8">
					<div class="control-group <?=('datas' == $msg_erro['campos']) ? 'error' : ''?>">
						<label class='control-label'>Data Inicial</label>
						<div class='controls controls-row'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
			<div class="span3">
				<div class="span8">
					<div class="control-group <?=('datas' == $msg_erro['campos']) ? 'error' : ''?>">
						<label class='control-label'>Data Final</label>
						<div class='controls controls-row'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value= "<?=$data_final?>">
						</div>
					</div>
				</div>
	   		</div>
	   		<div class="span3">
	   			<div class="span8">
					<div class="control-group <?=('datas' == $msg_erro['campos']) ? 'error' : ''?>">
						<label class='control-label'>Data Compra</label>
						<div class='controls controls-row'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_compra" id="data_compra" size="12" maxlength="10" class='span12' value= "<?=$data_compra?>">
						</div>
					</div>
	   			</div>
	   		</div>
	   	</div>
	   	<div class="row-fluid">
	   		<div class="span2"></div>
	   		<div class="span5">
	   			<div class="span12">
					<div class="control-group">
						<label class='control-label'>Nome Consumidor</label>
						<div class='controls controls-row'>
							<input type="text" name="nome" id="nome" class="span12" value= "<?=$nome?>">
						</div>
					</div>
	   			</div>
	   		</div>
	   		<div class="span3">
	   			<div class="span12">
					<div class="control-group">
						<label class='control-label'>CPF Consumidor</label>
						<div class='controls controls-row'>
							<input type="text" name="cpf" id="cpf" class="span12" value= "<?=$cpf?>">
						</div>
					</div>
	   			</div>
	   		</div>
	   	</div>
	   	<div class="row-fluid">
	   		<div class="span2"></div>
	   		<div class="span5">
	   			<div class="span12">
					<div class="control-group">
						<label class='control-label'>E-mail Consumidor</label>
						<div class='controls controls-row'>
							<input type="text" name="email" id="email" class="span12" value= "<?=$email?>">
						</div>
					</div>
	   			</div>
	   		</div>
	   	</div>
	   	<div class="row-fluid">
	   		<div class="span2"></div>
	   		 <div class="span2">
	   			<div class="span12">
					<div class="control-group">
						<label class='control-label'>Cep</label>
						<div class='controls controls-row'>
							<input type="text" name="cep" id="cep" size="12" maxlength="9" class='span12' value= "<?=$cep?>">
						</div>
					</div>
	   			</div>
	   		</div>
	   		<div class="span1"></div>
	   		<div class="span2">
                <div class="span12">
                	<div class="control-group">
                    	<label class="control-label">Estado</label>
                    	<div class="controls controls-row">
                            <select id="estado" name="estado" class="span12">
                                <option value="" ><?php echo traduz('selecione');?></option>
                                <?php
                                	foreach ($array_estados as $sigla => $nome_estado) {
                                    	$selected = ($sigla == $estado) ? "selected" : "";
                                    	echo "<option value='{$sigla}' {$selected}>".$nome_estado."</option>";
                                	}
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
	   		<div class="span3">
                <div class="control-group">
                    <label class="control-label" for="revenda_cidade">Cidade</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <select id="cidade" name="cidade" class="span12" >
                                <option value="" >Selecione</option>
                                <?php
	                                if (strlen($estado) > 0) {
	                                    $sql = "SELECT * FROM (
	                                                SELECT UPPER(TRIM(fn_retira_especiais(cidade))) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('$estado')
	                                                UNION (
	                                                    SELECT UPPER(TRIM(fn_retira_especiais(nome))) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('$estado')
	                                                )
	                                            ) AS cidade
	                                            ORDER BY cidade ASC";
	                                    $res = pg_query($con, $sql);

	                                    if (pg_num_rows($res) > 0) {
	                                        $cidade = $_POST["cidade"];
	                                        $sql = "SELECT UPPER(TRIM(fn_retira_especiais('$cidade')))";
	                                        $resUpperCidade = pg_query($con,$sql);
	                                        $cidade = pg_fetch_result($resUpperCidade,0,0);

	                                        while ($result = pg_fetch_object($res)) {
	                                            $selected = ($result->cidade == $cidade) ? " selected" : "";

	                                            echo "\t<option value='{$result->cidade}'{$selected}>{$result->cidade}</option>\n";
	                                        }
	                                    }
	                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid'>
        	<div class="span2"></div>
        	<div class="span3">
                <div class='control-group' >
                    <label class="control-label" for="revenda_cnpj">Revenda CNPJ</label>
                    <div class="controls controls-row">
                        <div class="span10 input-append">
                            <input id="revenda_cnpj" name="revenda_cnpj" class="span12" type="text" value="<?=$revenda_cnpj?>" />
                            <span class="add-on" rel="lupa" >
                                <i class="icon-search"></i>
                            </span>
                            <input type="hidden" name="lupa_config" tipo="revenda" parametro="cnpj" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
            <div class="span5">
                <div class='control-group' >
                    <label class="control-label" for="revenda_nome">Nome Revenda</label>
                    <div class="controls controls-row">
                        <div class="span9 input-append">
                            <input id="revenda_nome" name="revenda_nome" class="span11" type="text" maxlength="50" value="<?=$revenda_nome?>" />
                            <span class="add-on" rel="lupa" >
                                <i class="icon-search"></i>
                            </span>
                            <input type="hidden" name="lupa_config" tipo="revenda" parametro="razao_social" />
                        </div>
                    </div>
                </div>
            </div>    
        </div>
        <div class="row-fluid">
	   		<div class="span2"></div>
	   		<div class="span3">
	   			<div class="span12">
					<div class="control-group">
						<label class='control-label'>Modelo</label>
						<div class='controls controls-row'>
							<input type="text" name="modelo" id="modelo" class="span12" value= "<?=$modelo?>">
						</div>
					</div>
	   			</div>
	   		</div>
	   		<div class="span1"></div>
	   		<div class="span2">
	   			<div class="span12">
					<div class="control-group">
						<label class='control-label'>Nota Fiscal</label>
						<div class='controls controls-row'>
							<input type="text" name="nf" id="nf" class="span12" value= "<?=$nf?>">
						</div>
					</div>
	   			</div>
	   		</div>
	   	</div>
        <br />
        <div class="row-fluid">
        	<div class="span2"></div>
        	<div class="span8">
        		<div class="tac span12">
        			<button class="btn" type="submit" id="btn_consultar" name="btn_consultar" value="consultar">Consultar</button>&nbsp;&nbsp;
        			<button class="btn btn-primary" type="button" id="upload" name="upload">Realizar Upload</button>	
        		</div>
        	</div>
        </div>
   </div>
</form>
<?php

 if (count($result_dados) > 0) { ?>
	<div class="row-fluid">
		<div class="sapn12">
		    <div class="alert">
		        Em tela ser„o mostrados no m·ximo 500 registros, para visualizar todos os registros baixe o arquivo CSV no final da tela.
		    </div>
		</div>
	</div>
    <table class='table table-striped table-bordered table-hover table-fixed' id='content'>
        <thead>
            <tr class='titulo_coluna' >
                <th>Nome Consumidor</th>
                <th>CPF Consumidor</th>
                <th>Nome Revenda</th>
                <th>Data Compra</th>
            </tr>
        </thead>
        <tbody>
        <?php 
            foreach ($result_dados as $r => $rows) {
            	$data_comp =  $rows["data_compra"];

		        $data_formatar = date_create_from_format('Y-m-d', $data_comp);
		        $data_comp = date_format($data_formatar, 'd/m/Y');
        ?>
        <tr>
            <td class='tac'><?php echo $rows["nome"];?></td>
            <td class='tac'><?php echo $rows["cpf"];?></td>
            <td class='tac'><?php echo $rows["revenda_nome"];?></td>
            <td class='tac'><?php echo $data_comp; ?></td>
        </tr>
        <?php }?>
        </tbody>
    </table>
    <?php 
        $jsonPOST = excelPostToJson($_POST);
    ?>
    <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' /><br /><br />
    <div class="btn_excel" id='gerar_excel'>         
        <span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
        <span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
    </div>

<?php 
	} else if ($fez_consulta) {
?>
		<div class="row-fluid">
			<div class="span12">
				<div class="alert">
					<h4>Nenhum resultado encontrado.</h4>
			    </div>
			</div>
		</div>
<?php
	}
} 
?>

<br />
<?php include "rodape.php";?>
