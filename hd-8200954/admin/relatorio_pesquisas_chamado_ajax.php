<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
include "autentica_admin.php";

// header('Content-Type: application/json');

$msg_erro = array();

if ($_GET['ajax']){

	if (isset($_GET["tipo_busca"])) {

		$busca      = $_GET["busca"];
		$tipo_busca = $_GET["tipo_busca"];

		if (strlen($q) > 2) {

			if ($tipo_busca == 'posto') {

				$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto,tbl_posto_fabrica.posto
						FROM tbl_posto
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
						WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

				$sql .= ($busca == "codigo") ? " AND UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('$q') " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

				$res = pg_query($con,$sql);

				if (pg_num_rows ($res) > 0) {

					for ($i = 0; $i < pg_num_rows($res); $i++) {

						$cnpj         = trim(pg_fetch_result($res, $i, 'cnpj'));
						$nome         = trim(pg_fetch_result($res, $i, 'nome'));
						$codigo_posto = trim(pg_fetch_result($res, $i, 'codigo_posto'));
						$posto 		  = trim(pg_fetch_result($res, $i, 'posto'));

						echo "$cnpj|$nome|$codigo_posto|$posto";
						echo "\n";

					}

				}

			}
            if($tipo_busca == 'cidade_posto'){
                $sql = "SELECT  DISTINCT
                                fn_retira_especiais(tbl_posto.cidade) AS cidade,
                                tbl_posto.estado
                        FROM    tbl_posto
                        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_posto.posto
                                                    AND tbl_posto_fabrica.fabrica   = $login_fabrica
                        WHERE   tbl_posto_fabrica.credenciamento    IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
                        AND     UPPER(fn_retira_especiais(tbl_posto.cidade))             LIKE UPPER('%$q%')
                  ORDER BY      cidade
                ";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res)){
                    for($c=0;$c<pg_num_rows($res);$c++){
                        $cidade = trim(pg_fetch_result($res,$c,cidade));
                        $estado = trim(utf8_decode(pg_fetch_result($res,$c,estado)));

                        echo "$cidade|$estado";
                        echo "\n";
                    }
                }
            }
		}

	}

	//VALIDAÇÃO DOS CAMPOS PARA EFETUAR A PESQUISA
	if (filter_input(INPUT_GET,'validar',FILTER_VALIDATE_BOOLEAN)) {
        //VALIDAÇÃO DOS CAMPOS DE DATA
		    $tipo_pesquisa = filter_input(INPUT_GET,'pesquisa');

            $data_inicial = filter_input(INPUT_GET,"data_inicial");
			$data_final   = filter_input(INPUT_GET,"data_final");

            if($tipo_pesquisa != "atualizacao_cadastral" AND $tipo_pesquisa != "pesquisa"){
                if(empty($data_inicial)){
                    $msg_erro[] = "Informe a Data Inicial";
                }

                if (empty($data_final)) {
                    $msg_erro[] = "Informe a Data Final";
                }
            }

	    	if ($data_inicial && $data_final){

		        list($di, $mi, $yi) = explode("/", $data_inicial);
		        if(!checkdate($mi,$di,$yi)){
		            $msg_erro[] = "Data Inicial Inválida";
		        }

		        list($df, $mf, $yf) = explode("/", $data_final);
		        if(!checkdate($mf,$df,$yf)) {
		            $msg_erro[] = "Data Final Inválida";
		        }

				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final   = "$yf-$mf-$df";

                if ($login_fabrica == 94) {
                    if (strtotime($aux_data_inicial.'+12 month') < strtotime($aux_data_final)) {
                        $msg_erro[] = 'O intervalo entre as datas não pode ser maior do que 12 meses';
                    }
                } else {
                    if (strtotime($aux_data_inicial.'+3 month') < strtotime($aux_data_final)) {
                        $msg_erro[] = 'O intervalo entre as datas não pode ser maior do que 3 meses';
                    }
                }

                if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
		            $msg_erro[] = "Data Final menor que Data Inicial";
		        }

		    	if (strtotime($aux_data_final) > strtotime('today') and $aux_data_final){
		    		$msg_erro[] = "Data Final maior que a data atual";
		    	}

		    	if (strtotime($aux_data_inicial) > strtotime('today')){
		    		$msg_erro[] = "Data Final maior que a data atual";
		    	}

	    	}

	    //FIM VALIDAÇÃO DE DATAS

	    //VALIDA SE O POSTO DIGITADO EXISTE

	    		if (isset($_GET['codigo_posto']) || isset($_GET['posto_nome'])){

					$codigo_posto = (isset($_GET['codigo_posto'])) ? utf8_decode(trim($_GET['codigo_posto'])) : '';
					$posto_nome   = (isset($_GET['posto_nome'])) ? utf8_decode(trim($_GET['posto_nome'])) : '';

				    $sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
									FROM tbl_posto
									JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
									WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

					if ($posto_nome){
						$sql .= "AND UPPER(tbl_posto.nome) like UPPER('%$posto_nome%')";
					}

					if ($codigo_posto){
						$sql .= "AND UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('$codigo_posto')";
					}

					$res = pg_query($con,$sql);

					if (pg_num_rows ($res) == 0) {

						$msg_erro[] = "Posto não existe";

					}
	    		}
		//FIM VALIDACAO SE POSTO EXISTE

	    if (count($msg_erro)>0) {
	    	$msg_erro = implode('<br>', $msg_erro);
	    	$msg_erro = $msg_erro;
	    	echo "1|$msg_erro";
	    } else {
	    	echo "0|Sem Erros";
	    }

	}

	if (isset($_GET['ver_respostas'])) {

        $local          = $_GET['local'];
        $pesquisa_id    = $_GET['pesquisa'];
        $pesquisa       = $_GET['categoria'];
        $data = "";
        $i = 0;
        $respostasPergunta = array();
        $mediaRespostas = array();
        //percorre o array da consulta principal 1ª vez para jogar as respostas em um array
        if (!in_array($pesquisa,array("posto","ordem_de_servico","ordem_de_servico_email","atualizacao_cadastral","posto_sms","externo_outros", "recadastramento"))) {
            $sql = "SELECT  pergunta,
                            txt_resposta,
                            tipo_resposta_item,
                            hd_chamado
                    FROM    tbl_resposta
                    JOIN    tbl_pergunta USING (pergunta)
                    WHERE   hd_chamado = $local
                    AND     pesquisa = $pesquisa_id
			  ORDER BY      pergunta";
				$pesquisa_atendimento  = true; 
				$cond_chamado = " and hd_chamado = $local ";
        } else if (!in_array($pesquisa,array("ordem_de_servico","ordem_de_servico_email","atualizacao_cadastral","posto_sms","externo_outros"))) {
            $sql = "SELECT  pergunta,
                            txt_resposta,
                            tipo_resposta_item,
                            posto
                    FROM    tbl_resposta
                    JOIN    tbl_pesquisa using (pesquisa)
                    WHERE   posto = $local
                    AND     tbl_pesquisa.pesquisa = $pesquisa_id
              ORDER BY      pergunta";
        } else if (in_array($pesquisa,array("ordem_de_servico","ordem_de_servico_email"))) {
            $sql = "SELECT  pergunta,
                            txt_resposta,
                            tipo_resposta_item,
                            os
                    FROM    tbl_resposta
                    JOIN    tbl_pesquisa using (pesquisa)
                    WHERE   os = $local
                    AND     tbl_pesquisa.pesquisa = $pesquisa_id
              ORDER BY      pergunta";
        } else if ($pesquisa == "atualizacao_cadastral") {//HD-2987225
            $sql = "SELECT  pergunta,
                            txt_resposta,
                            tipo_resposta_item,
                            posto
                    FROM    tbl_resposta
                    JOIN    tbl_pesquisa using (pesquisa)
                    WHERE   posto = $local
                    AND     tbl_pesquisa.pesquisa = $pesquisa_id
              ORDER BY      pergunta";
        } else if (in_array($pesquisa,array("posto_sms"))) {
            $sql = "SELECT  pergunta            ,
                            txt_resposta        ,
                            tbl_resposta.data_input          ,
                            tbl_pesquisa.pesquisa as pesquisa_id , 
                            tbl_resposta.tecnico,
                            tipo_resposta_item
                    FROM    tbl_resposta
                    JOIN    tbl_pesquisa USING(pesquisa)
                    WHERE   tbl_resposta.campos_adicionais->>'treinamento' = '$pesquisa_id'
                    AND     os is null
                    AND     hd_chamado is null
                    AND     pergunta is not null
                ORDER BY pergunta";
        } else if (in_array($pesquisa,array("externo_outros"))) {
            $sql = "SELECT  pergunta,
                            txt_resposta,
                            tipo_resposta_item,
                            venda
                    FROM    tbl_resposta
                    JOIN    tbl_pesquisa using (pesquisa)
                    WHERE   venda = ".$local;
        }

    $resRespostas = pg_query($con,$sql);

        if($pesquisa == "posto_sms"){
            $sqlTecnicos = "SELECT distinct
                            tbl_resposta.tecnico
                    FROM    tbl_resposta
                    JOIN    tbl_pesquisa USING(pesquisa)
                    WHERE   tbl_resposta.campos_adicionais->>'treinamento' = '$pesquisa_id'
                    AND     os is null
                    AND     hd_chamado is null
                    AND     pergunta is not null
                ORDER BY tecnico";
            $resTecnicos = pg_query($con, $sqlTecnicos);

            if (pg_num_rows($resRespostas)>0) {
                foreach (pg_fetch_all($resRespostas) as $keyRespostas) {
                    if (!in_array($pesquisa,array("posto","ordem_de_servico","ordem_de_servico_email","externo_outros", "recadastramento", "posto_sms" ))) {
                        $local = $keyRespostas['hd_chamado'];
                    } else if ($pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email" && $pesquisa != "posto_sms") {
                        $local = $keyRespostas['posto'];
                    } else if ($pesquisa == "ordem_de_servico" OR $pesquisa == "ordem_de_servico_email") {
                        $local = $keyRespostas['os'];
                    } else if ($pesquisa == "externo_outros") {
                        $local = $keyRespostas['venda'];
                    }else if($pesquisa == 'posto_sms'){
                        $pesquisa_id = $keyRespostas['pesquisa_id'];
                    }

                    if (!empty($keyRespostas['tipo_resposta_item'])) {
                        $respostasPergunta[$pesquisa_id][$keyRespostas['tecnico']][$keyRespostas['pergunta']]['respostas'][] = $keyRespostas['tipo_resposta_item'];
                    } else {
                        $respostasPergunta[$pesquisa_id][$keyRespostas['tecnico']][$keyRespostas['pergunta']]['respostas'][] = $keyRespostas['txt_resposta'];
                        $mediaRespostas[] = $keyRespostas['txt_resposta'];
                    }
                }
            }
        }
if($pesquisa == 'posto_sms'){

    if(count($respostasPergunta)==0){
        echo "Pesquisa não respondida";
        exit;
    }

    $sql = "SELECT  tbl_pesquisa_pergunta.ordem     ,
                        tbl_pergunta.pergunta           ,
                        tbl_pergunta.descricao          ,
                        tbl_pergunta.tipo_resposta      ,
                        tbl_tipo_resposta.tipo_descricao,
                        tbl_pesquisa.pesquisa           ,
                        tbl_tipo_resposta.peso
                FROM    tbl_pesquisa_pergunta
                JOIN    tbl_pergunta        USING(pergunta)
                JOIN    tbl_pesquisa        USING(pesquisa)
           LEFT JOIN    tbl_tipo_resposta   ON tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta
                WHERE   tbl_pesquisa.pesquisa   = ".$pesquisa_id."
                AND     tbl_pesquisa.fabrica    = $login_fabrica
                AND     tbl_pergunta.ativo      IS TRUE
          ORDER BY      tbl_pesquisa_pergunta.ordem";
        $resPerguntas = pg_query($con,$sql);

        $nota_final = 0;$mostra_media = true;

    foreach(pg_fetch_all($resTecnicos) as $tec){
        $tecnico = $tec['tecnico'];

        $sqlT = "SELECT nome FROM tbl_tecnico WHERE tecnico = $tecnico and fabrica = $login_fabrica ";
        $resT = pg_query($con, $sqlT);
        if(pg_num_rows($resT)>0){
            $nomeTecnico = pg_fetch_result($resT, 0, nome);
        }

        $data .= "<tr bgcolor='#ccccc'><td colspan='20'>$nomeTecnico</td></tr>";

        foreach (pg_fetch_all($resPerguntas) as $keyForm) {

            $cor = ($i % 2) ? "#E4E9FF" : "#F3F3F3";

            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > ".$keyForm['ordem']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$keyForm['descricao']."
                        </td>";

            if (!empty($keyForm['tipo_resposta'])) {

                $sql = "SELECT  tbl_tipo_resposta_item.descricao            ,
                                tbl_tipo_resposta.label_inicio              ,
                                tbl_tipo_resposta.label_fim                 ,
                                tbl_tipo_resposta.label_intervalo           ,
                                tbl_tipo_resposta.tipo_descricao            ,
                                tbl_tipo_resposta_item.tipo_resposta_item   ,
                                tbl_tipo_resposta_item.peso AS peso_item    ,
                                tbl_tipo_resposta.peso AS peso_resposta
                        FROM    tbl_tipo_resposta
                   LEFT JOIN    tbl_tipo_resposta_item USING(tipo_resposta)
                        WHERE   tbl_tipo_resposta.tipo_resposta = ".$keyForm['tipo_resposta']."
                        AND     tbl_tipo_resposta.fabrica       = $login_fabrica
                  ORDER BY      tbl_tipo_resposta_item.ordem
                    ";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res)>0) {
                    for ($x=0; $x < pg_num_rows($res); $x++) {

                        if (!empty($respostasPergunta)) {
                            $disabled = 'disabled="DISABLED"';
                        }

                        $item_tipo_resposta_desc            = pg_fetch_result($res, $x, 'descricao');
                        $item_tipo_resposta_tipo            = pg_fetch_result($res, $x, 'tipo_descricao');
                        $item_tipo_resposta_label_inicio    = pg_fetch_result($res, $x, 'label_inicio');
                        $item_tipo_resposta_label_fim       = pg_fetch_result($res, $x, 'label_fim');
                        $item_tipo_resposta_label_intervalo = pg_fetch_result($res, $x, 'label_intervalo');
                        $tipo_resposta_item_id              = pg_fetch_result($res, $x, 'tipo_resposta_item');
                        $peso_item                          = pg_fetch_result($res,$x,'peso_item');
                        $peso_resposta                      = pg_fetch_result($res,$x,'peso_resposta');

                        if (in_array($item_tipo_resposta_tipo, array('checkbox','radio'))) {
                            $colspan = "";
                            $width = "";
                        }else{
                            $colspan = "100%";
                            if($login_fabrica == 1 AND $pesquisa == "atualizacao_cadastral"){//HD-2987225
                                $width = "300px";
                            }
                        }

                        $data .= '<td align="center" nowrap colspan="'.$colspan.'" >';

                        if ($item_tipo_resposta_tipo == 'radio' or $item_tipo_resposta_tipo == 'checkbox') {
                            $value_resposta = $tipo_resposta_item_id;
                        }else{
                            $value_resposta = $item_tipo_resposta_desc;
                        }

                        switch ($item_tipo_resposta_tipo) {

                            case 'radio':

                                if (strlen($item_tipo_resposta_desc) > 0) {
                                    $data .= $item_tipo_resposta_desc;
                                    $value_resposta = $tipo_resposta_item_id;

                                    if (is_array($respostasPergunta) and !empty($respostasPergunta)) {

                                        if ($tipo_resposta_item_id == $respostasPergunta[$pesquisa_id][$tecnico][$keyForm['pergunta']]['respostas'][0]) {
                                            $checked_radio = "checked='CHECKED'";
                                            $nota_final += $peso_item;
                                            $check_radio_peso = $peso_item;
                                        }else{
                                            $checked_radio = "";
                                        }

                                    }

                                    $data .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt'.$tecnico.$keyForm['pergunta'].'"  class="frm" value="'.$value_resposta.'" '.$checked_radio.$disabled.' />';

                                }
                                break;

                            case 'textarea':
                            case 'text':

                                $item_tipo_resposta_desc = $keyForm['txt_resposta'];
                                $disabled_resposta = "disabled='DISABLED'";
                                $value_resposta = html_entity_decode($item_tipo_resposta_desc,'ENT_QUOTES','ISO-8859-1');

                                if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                    if (!empty($respostasPergunta[$pesquisa_id][$tecnico][$keyForm['pergunta']]['respostas'])) {
                                        $value_resposta = $respostasPergunta[$pesquisa_id][$tecnico][$keyForm['pergunta']]['respostas'][0];

                                        if($login_fabrica == 129){
                                            if(trim(strlen($value_resposta)) > 0){
                                                $peso_resposta = $peso_resposta;
                                            }else{
                                                $peso_resposta = 0;
                                            }
                                            $nota_final += $peso_resposta;
                                        }else{
                                            $nota_final += $peso_resposta;
                                        }
                                    }
                                }

                                $data .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt'.$tecnico.$keyForm['pergunta'].'"  class="frm" value="'.$value_resposta.'" '.$disabled.' />';

                            break;

                            case 'range':

                                $value_resposta = $item_tipo_resposta_desc;
                                $aux_data = "";
                                for ($z = $item_tipo_resposta_label_inicio; $z <= $item_tipo_resposta_label_fim ; $z += $item_tipo_resposta_label_intervalo) {
                                    if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                        if (in_array($z,$respostasPergunta[$pesquisa_id][$tecnico][$keyForm['pergunta']]['respostas'])) {
                                            $checked_radio = "checked='CHECKED'";
                                        }else{
                                            $checked_radio = "";
                                        }
                                    }

                                    $aux_data .= $z.' <input type="radio" name="perg_opt'.$tecnico.$keyForm['pergunta'].'" value="'.$z.'" '.$checked_radio.$disabled.' /> &nbsp; &nbsp;';

                                }
                                $data .= $aux_data;
                            break;

                            case 'checkbox':

                                $data .= $item_tipo_resposta_desc;
                                $value_resposta = $tipo_resposta_item_id;
                                if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                    if (in_array($tipo_resposta_item_id,$respostasPergunta[$pesquisa_id][$tecnico][$keyForm['pergunta']]['respostas'])) {
                                        $checked_radio = "checked='CHECKED'";
                                        $nota_final += $peso_item;
                                        $check_radio_peso = $peso_item;
                                    }
                                }
                                $data .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt_checkbox_'.$tecnico.$keyForm['pergunta'].'_'.$i.'_'.$value_resposta.'"  class="frm" value="'.$value_resposta.'" '.$checked_radio.$disabled.' />';
                            break;

                            case 'textarea':

                                if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                    if (!empty($respostasPergunta[$pesquisa_id][$tecnico][$keyForm['pergunta']]['respostas'])) {
                                        $value_resposta = $respostasPergunta[$pesquisa_id][$tecnico][$keyForm['pergunta']]['respostas'][0];

                                        if($login_fabrica == 129){
                                            if(trim(strlen($value_resposta)) > 0){
                                                $peso_resposta = $peso_resposta;
                                            }else{
                                                $peso_resposta = 0;
                                            }
                                            $nota_final += $peso_resposta;
                                        }else{
                                            $nota_final += $peso_resposta;
                                        }
                                    }
                                }

                                if($login_fabrica == 129){
                                    $data .= ' <textarea name="perg_opt'.$tecnico.$keyForm['pergunta'].'" class="frm" '.$disabled.' style="width:90%" >'.$value_resposta.'</textarea> ';
                                }else{
                                    $data .= ' <textarea name="perg_opt'.$tecnico.$keyForm['pergunta'].'" class="frm" '.$disabled.' style="width:90%" >'.html_entity_decode($value_resposta,'ENT_QUOTES','ISO-8859-1').'</textarea> ';
                                }
                            break;

                            case 'date':

                                if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                    if (!empty($respostasPergunta[$pesquisa_id][$tecnico][$keyForm['pergunta']]['respostas'])) {
                                        $value_resposta = $respostasPergunta[$pesquisa_id][$tecnico][$keyForm['pergunta']]['respostas'][0];

                                        if($login_fabrica == 129){
                                            if(trim(strlen($value_resposta)) > 0){
                                                $peso_resposta = $peso_resposta;
                                            }else{
                                                $peso_resposta = 0;
                                            }
                                            $nota_final += $peso_resposta;
                                        }else{
                                            $nota_final += $peso_resposta;
                                        }
                                    }
                                }
                                $width="";
                                $data .= ' <input  type="text"  style="width:'.$width.'" name="perg_opt'.$tecnico.$keyForm['pergunta'].'"  class="frm date" value="'.$value_resposta.'" '.$disabled.' />';

                                break;

                            default:

                                break;

                        }

                        $data .= '</td>';
                        unset($checked_radio);
                    }


                    if(trim(strlen($check_radio_peso)) > 0 AND $item_tipo_resposta_tipo == 'radio'){
                        $check_radio_peso = $check_radio_peso;
                        $check_radio_peso = str_replace('.', ',', $check_radio_peso);
                    }elseif(trim(strlen($check_radio_peso)) > 0 AND $item_tipo_resposta_tipo == 'checkbox'){
                        $check_radio_peso = $check_radio_peso;
                        $check_radio_peso = str_replace('.', ',', $check_radio_peso);
                    }elseif(trim(strlen($check_radio_peso)) > 0 AND $item_tipo_resposta_tipo == 'range'){
                        $check_radio_peso = $check_radio_peso;
                        $check_radio_peso = str_replace('.', ',', $check_radio_peso);
                    }else{
                        $check_radio_peso = '';
                    }

                    if(trim(strlen($txtResposta)) > 0 AND $item_tipo_resposta_tipo == 'text'){

                        $peso_resposta = $txtResposta;
                        $peso_resposta = str_replace('.', ',', $peso_resposta);
                    }

                    if (!in_array($login_fabrica, array(1,35,138,145,152,161))) {
                        if($pesquisa != "atualizacao_cadastral"){
                            $data.="<td colspan='100%'>Peso: &nbsp;".$peso_resposta.$check_radio_peso."</td>";
                        }
                    }
                    if (in_array($login_fabrica, [161]) && $mostra_media == true) {
                        $mostra_media = false;
                        $data.="<td colspan='100%' rowspan='".count($mediaRespostas)."' >Média:<br>".number_format((array_sum($mediaRespostas)/count($mediaRespostas)), 2, ",", ".")."</td>";
                    }
                }

            }else{
                $data .= "<td colspan='3'>&nbsp; </td>";
            }

            $data .= "</tr>";
            $i++;
        }
    }







}else{

    if (pg_num_rows($resRespostas)>0) {
            foreach (pg_fetch_all($resRespostas) as $keyRespostas) {
                if (!in_array($pesquisa,array("posto","ordem_de_servico","ordem_de_servico_email","externo_outros", "recadastramento", "posto_sms" ))) {
                    $local = $keyRespostas['hd_chamado'];
                } else if ($pesquisa != "ordem_de_servico" && $pesquisa != "ordem_de_servico_email" && $pesquisa != "posto_sms") {
                    $local = $keyRespostas['posto'];
                } else if ($pesquisa == "ordem_de_servico" OR $pesquisa == "ordem_de_servico_email") {
                    $local = $keyRespostas['os'];
                } else if ($pesquisa == "externo_outros") {
                    $local = $keyRespostas['venda'];
                }else if($pesquisa == 'posto_sms'){
                    $pesquisa_id = $keyRespostas['pesquisa_id'];
                }

                if (!empty($keyRespostas['tipo_resposta_item'])) {
                    $respostasPergunta[$pesquisa_id][$local][$keyRespostas['pergunta']]['respostas'][] = $keyRespostas['tipo_resposta_item'];
                } else {
                    $respostasPergunta[$pesquisa_id][$local][$keyRespostas['pergunta']]['respostas'][] = $keyRespostas['txt_resposta'];
                    $mediaRespostas[] = $keyRespostas['txt_resposta'];
                }
            }
        }

		if($pesquisa_atendimento) {
        $sql = "SELECT  tbl_resposta.resposta as ordem     ,
                        tbl_pergunta.pergunta           ,
                        tbl_pergunta.descricao          ,
                        tbl_pergunta.tipo_resposta      ,
                        tbl_tipo_resposta.tipo_descricao,
                        tbl_pesquisa.pesquisa           ,
                        tbl_tipo_resposta.peso
                FROM    tbl_resposta
                JOIN    tbl_pergunta        USING(pergunta)
                JOIN    tbl_pesquisa        USING(pesquisa)
           LEFT JOIN    tbl_tipo_resposta   ON tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta
                WHERE   pesquisa   = ".$pesquisa_id."
				$cond_chamado
                AND     tbl_pergunta.fabrica    = $login_fabrica
		  ORDER BY      1";
		}else{
			$sql = "SELECT  tbl_pesquisa_pergunta.ordem     ,
                        tbl_pergunta.pergunta           ,
                        tbl_pergunta.descricao          ,
                        tbl_pergunta.tipo_resposta      ,
                        tbl_tipo_resposta.tipo_descricao,
                        tbl_pesquisa.pesquisa           ,
                        tbl_tipo_resposta.peso
                FROM    tbl_pesquisa_pergunta
                JOIN    tbl_pergunta        USING(pergunta)
                JOIN    tbl_pesquisa        USING(pesquisa)
           LEFT JOIN    tbl_tipo_resposta   ON tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta
                WHERE   tbl_pesquisa.pesquisa   = ".$pesquisa_id."
                AND     tbl_pesquisa.fabrica    = $login_fabrica
                AND     tbl_pergunta.ativo      IS TRUE
          ORDER BY      tbl_pesquisa_pergunta.ordem";
		}
        $resPerguntas = pg_query($con,$sql);
	    $nota_final = 0;$mostra_media = true;

        foreach (pg_fetch_all($resPerguntas) as $keyForm) {

            $cor = ($i % 2) ? "#E4E9FF" : "#F3F3F3";

            $data .= "
                    <tr bgcolor='$cor'>
                        <td style='text-align:center;padding: 0px 10px 0px 10px' >
                            <label > ".$keyForm['ordem']." </label>
                        </td>
                        <td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
                            ".$keyForm['descricao']."
                        </td>";

            if (!empty($keyForm['tipo_resposta'])) {

                $sql = "SELECT  tbl_tipo_resposta_item.descricao            ,
                                tbl_tipo_resposta.label_inicio              ,
                                tbl_tipo_resposta.label_fim                 ,
                                tbl_tipo_resposta.label_intervalo           ,
                                tbl_tipo_resposta.tipo_descricao            ,
                                tbl_tipo_resposta_item.tipo_resposta_item   ,
                                tbl_tipo_resposta_item.peso AS peso_item    ,
                                tbl_tipo_resposta.peso AS peso_resposta
                        FROM    tbl_tipo_resposta
                   LEFT JOIN    tbl_tipo_resposta_item USING(tipo_resposta)
                        WHERE   tbl_tipo_resposta.tipo_resposta = ".$keyForm['tipo_resposta']."
                        AND     tbl_tipo_resposta.fabrica       = $login_fabrica
                  ORDER BY      tbl_tipo_resposta_item.ordem
                    ";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res)>0) {
                    for ($x=0; $x < pg_num_rows($res); $x++) {

                        if (!empty($respostasPergunta)) {
                            $disabled = 'disabled="DISABLED"';
                        }

                        $item_tipo_resposta_desc            = pg_fetch_result($res, $x, 'descricao');
                        $item_tipo_resposta_tipo            = pg_fetch_result($res, $x, 'tipo_descricao');
                        $item_tipo_resposta_label_inicio    = pg_fetch_result($res, $x, 'label_inicio');
                        $item_tipo_resposta_label_fim       = pg_fetch_result($res, $x, 'label_fim');
                        $item_tipo_resposta_label_intervalo = pg_fetch_result($res, $x, 'label_intervalo');
                        $tipo_resposta_item_id              = pg_fetch_result($res, $x, 'tipo_resposta_item');
                        $peso_item			                = pg_fetch_result($res,$x,'peso_item');
                        $peso_resposta 			            = pg_fetch_result($res,$x,'peso_resposta');

                        if (in_array($item_tipo_resposta_tipo, array('checkbox','radio'))) {
                            $colspan = "";
                            $width = "";
                        }else{
                            $colspan = "100%";
                            if($login_fabrica == 1 AND $pesquisa == "atualizacao_cadastral"){//HD-2987225
                                $width = "300px";
                            }
                        }

                        $data .= '<td align="center" nowrap colspan="'.$colspan.'" >';

                        if ($item_tipo_resposta_tipo == 'radio' or $item_tipo_resposta_tipo == 'checkbox') {
                            $value_resposta = $tipo_resposta_item_id;
                        }else{
                            $value_resposta = $item_tipo_resposta_desc;
                        }

                        switch ($item_tipo_resposta_tipo) {

                            case 'radio':

                                if (strlen($item_tipo_resposta_desc) > 0) {
                                    $data .= $item_tipo_resposta_desc;
                                    $value_resposta = $tipo_resposta_item_id;

                                    if (is_array($respostasPergunta) and !empty($respostasPergunta)) {

                                        if ($tipo_resposta_item_id == $respostasPergunta[$pesquisa_id][$local][$keyForm['pergunta']]['respostas'][0]) {
                                            $checked_radio = "checked='CHECKED'";
                                            $nota_final += $peso_item;
                                            $check_radio_peso = $peso_item;
                                        }else{
                                            $checked_radio = "";
                                        }

                                    }

                                    $data .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt'.$local.$keyForm['pergunta'].'"  class="frm" value="'.$value_resposta.'" '.$checked_radio.$disabled.' />';

                                }
                                break;

							case 'textarea':
							case 'text':

                                $item_tipo_resposta_desc = $keyForm['txt_resposta'];
                                $disabled_resposta = "disabled='DISABLED'";
                                $value_resposta = html_entity_decode($item_tipo_resposta_desc,'ENT_QUOTES','ISO-8859-1');

                                if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                    if (!empty($respostasPergunta[$pesquisa_id][$local][$keyForm['pergunta']]['respostas'])) {
                                        $value_resposta = $respostasPergunta[$pesquisa_id][$local][$keyForm['pergunta']]['respostas'][0];

                                        if($login_fabrica == 129){
                                            if(trim(strlen($value_resposta)) > 0){
                                                $peso_resposta = $peso_resposta;
                                            }else{
                                                $peso_resposta = 0;
                                            }
                                            $nota_final += $peso_resposta;
                                        }else{
                                            $nota_final += $peso_resposta;
                                        }
                                    }
                                }

                                $data .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt'.$local.$keyForm['pergunta'].'"  class="frm" value="'.$value_resposta.'" '.$disabled.' />';

                            break;

                            case 'range':

                                $value_resposta = $item_tipo_resposta_desc;
                                $aux_data = "";
                                for ($z = $item_tipo_resposta_label_inicio; $z <= $item_tipo_resposta_label_fim ; $z += $item_tipo_resposta_label_intervalo) {
                                    if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                        if (in_array($z,$respostasPergunta[$pesquisa_id][$local][$keyForm['pergunta']]['respostas'])) {
                                            $checked_radio = "checked='CHECKED'";
                                        }else{
                                            $checked_radio = "";
                                        }
                                    }

                                    $aux_data .= $z.' <input type="radio" name="perg_opt'.$local.$keyForm['pergunta'].'" value="'.$z.'" '.$checked_radio.$disabled.' /> &nbsp; &nbsp;';

                                }
                                $data .= $aux_data;
                            break;

                            case 'checkbox':

                                $data .= $item_tipo_resposta_desc;
                                $value_resposta = $tipo_resposta_item_id;
                                if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                    if (in_array($tipo_resposta_item_id,$respostasPergunta[$pesquisa_id][$local][$keyForm['pergunta']]['respostas'])) {
                                        $checked_radio = "checked='CHECKED'";
                    					$nota_final += $peso_item;
                                        $check_radio_peso = $peso_item;
                                    }
                                }
                                $data .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt_checkbox_'.$local.$keyForm['pergunta'].'_'.$i.'_'.$value_resposta.'"  class="frm" value="'.$value_resposta.'" '.$checked_radio.$disabled.' />';
                            break;

                            case 'textarea':

                                if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                    if (!empty($respostasPergunta[$pesquisa_id][$local][$keyForm['pergunta']]['respostas'])) {
                                        $value_resposta = $respostasPergunta[$pesquisa_id][$local][$keyForm['pergunta']]['respostas'][0];

                                        if($login_fabrica == 129){
                                            if(trim(strlen($value_resposta)) > 0){
                                                $peso_resposta = $peso_resposta;
                                            }else{
                                                $peso_resposta = 0;
                                            }
                                            $nota_final += $peso_resposta;
                                        }else{
                                            $nota_final += $peso_resposta;
                                        }
                                    }
                                }

                                if($login_fabrica == 129){
                                    $data .= ' <textarea name="perg_opt'.$local.$keyForm['pergunta'].'" class="frm" '.$disabled.' style="width:90%" >'.$value_resposta.'</textarea> ';
                                }else{
                                    $data .= ' <textarea name="perg_opt'.$local.$keyForm['pergunta'].'" class="frm" '.$disabled.' style="width:90%" >'.html_entity_decode($value_resposta,'ENT_QUOTES','ISO-8859-1').'</textarea> ';
                                }
                            break;

                            case 'date':

                                if (is_array($respostasPergunta) and !empty($respostasPergunta)){
                                    if (!empty($respostasPergunta[$pesquisa_id][$local][$keyForm['pergunta']]['respostas'])) {
                                        $value_resposta = $respostasPergunta[$pesquisa_id][$local][$keyForm['pergunta']]['respostas'][0];

                                        if($login_fabrica == 129){
                                            if(trim(strlen($value_resposta)) > 0){
                                                $peso_resposta = $peso_resposta;
                                            }else{
                                                $peso_resposta = 0;
                                            }
                                            $nota_final += $peso_resposta;
                                        }else{
                                            $nota_final += $peso_resposta;
                                        }
                                    }
                                }
                                $width="";
                                $data .= ' <input  type="text"  style="width:'.$width.'" name="perg_opt'.$local.$keyForm['pergunta'].'"  class="frm date" value="'.$value_resposta.'" '.$disabled.' />';

                                break;

                            default:

                                break;

                        }

                        $data .= '</td>';
                        unset($checked_radio);
                    }


                    if(trim(strlen($check_radio_peso)) > 0 AND $item_tipo_resposta_tipo == 'radio'){
                        $check_radio_peso = $check_radio_peso;
                        $check_radio_peso = str_replace('.', ',', $check_radio_peso);
                    }elseif(trim(strlen($check_radio_peso)) > 0 AND $item_tipo_resposta_tipo == 'checkbox'){
                        $check_radio_peso = $check_radio_peso;
                        $check_radio_peso = str_replace('.', ',', $check_radio_peso);
                    }elseif(trim(strlen($check_radio_peso)) > 0 AND $item_tipo_resposta_tipo == 'range'){
                        $check_radio_peso = $check_radio_peso;
                        $check_radio_peso = str_replace('.', ',', $check_radio_peso);
                    }else{
                        $check_radio_peso = '';
                    }

                    if(trim(strlen($txtResposta)) > 0 AND $item_tipo_resposta_tipo == 'text'){

                        $peso_resposta = $txtResposta;
                        $peso_resposta = str_replace('.', ',', $peso_resposta);
                    }

                    if (!in_array($login_fabrica, array(1,35,138,145,152,161))) {
                        if($pesquisa != "atualizacao_cadastral"){
                            $data.="<td colspan='100%'>Peso: &nbsp;".$peso_resposta.$check_radio_peso."</td>";
                        }
                    }
                    if (in_array($login_fabrica, [161]) && $mostra_media == true) {
                        $mostra_media = false;
                        $data.="<td colspan='100%' rowspan='".count($mediaRespostas)."' >Média:<br>".number_format((array_sum($mediaRespostas)/count($mediaRespostas)), 2, ",", ".")."</td>";
                    }
                }

            }else{
                $data .= "<td colspan='3'>&nbsp; </td>";
            }

            $data .= "</tr>";
            $i++;
        }
    }



        $nota_final = str_replace('.', ',', $nota_final);

        if ($login_fabrica == 129) {
            $data .= "<tr><td colspan='150%'><strong>Nota Final: $nota_final</strong></td></tr>";
        }

        echo $data;
    }

    if (isset($_GET['getChartContents'])) {
        $pesquisa       = $_GET['pesquisa'];
        $posto_nome     = $_GET['posto_nome'];
        $posto_linha    = $_GET['posto_linha'];
        $posto_local    = $_GET['posto_local'];
        $data_inicial   = $_GET['data_inicial'];
        $data_final     = $_GET['data_final'];

        $conditionPosto = (!empty($posto_nome)) ? " AND tbl_posto.nome = '$posto_nome' " : '';
        $conditionLinha = (!empty($posto_linha)) ? " AND tbl_posto_linha.linha = '$posto_linha' " : '';
        $conditionLocal = (!empty($posto_local)) ? " AND UPPER(fn_retira_especiais(tbl_posto.cidade)) = UPPER('$posto_local') " : '';
        $conditionData  = (!empty($data_inicial)) ? " AND tbl_resposta.data_input BETWEEN '$data_inicial' AND '$data_final'" : "";

        $sql = "SELECT  DISTINCT
                        to_ascii(tbl_tipo_resposta_item.descricao, 'LATIN1') AS descricao
                FROM    tbl_pesquisa
                JOIN    tbl_pesquisa_pergunta   USING (pesquisa)
                JOIN    tbl_pergunta            USING (pergunta)
                JOIN    tbl_tipo_resposta_item  USING (tipo_resposta)
                WHERE   tbl_pesquisa.pesquisa   = $pesquisa
                AND     tbl_pergunta.ativo      IS TRUE
        ";
        $res = pg_query($con,$sql);
        for ($i=0; $i < pg_num_rows($res); $i++) {
            $txt_resposta = pg_fetch_result($res, $i, 'descricao');
            $respostas_geral[] = $txt_resposta;
            $tipo_de_respostas[$txt_resposta]['name'] = $txt_resposta;
        }

        $sql = "SELECT  to_ascii(tbl_pergunta.descricao, 'LATIN1') AS descricao ,
                        tbl_pesquisa_pergunta.ordem                             ,
                        tbl_pergunta.pergunta,
                        tbl_tipo_resposta.tipo_descricao,
                        tbl_tipo_resposta.tipo_resposta
                FROM    tbl_pesquisa
                JOIN    tbl_pesquisa_pergunta   USING (pesquisa)
                JOIN    tbl_pergunta            USING (pergunta)
                JOIN    tbl_tipo_resposta       USING (tipo_resposta)
                WHERE   tbl_pesquisa.fabrica                = $login_fabrica
                AND     tbl_tipo_resposta.tipo_descricao    IN ('radio','checkbox', 'range')
                AND     tbl_pesquisa.pesquisa               = $pesquisa
                AND     tbl_pergunta.ativo
          ORDER BY      tbl_pesquisa_pergunta.ordem
            ";
        $resPerguntas = pg_query($con,$sql);

        if (pg_num_rows($resPerguntas)>0) {
            for ($x=0; $x < pg_num_rows($resPerguntas); $x++) {
                $perguntas[$x] = pg_fetch_result($resPerguntas, $x, 'pergunta');
                $tipo_descricao[$x] = pg_fetch_result($resPerguntas, $x, 'tipo_descricao');
                $tipo_resposta[$x] = pg_fetch_result($resPerguntas, $x, 'tipo_resposta');
            }
        }

        $perguntasJson = json_encode($perguntas);
        $respostas_restantes = array();

        foreach ($perguntas as $key => $value) {

            if($tipo_descricao[$key] == 'range'){

                $sql = "select * from tbl_tipo_resposta where tipo_resposta = $tipo_resposta[$key] and fabrica = $login_fabrica";
                $res = pg_query($con, $sql);

                if(pg_num_rows($res)>0){
                    $label_inicio        = pg_fetch_result($res, 0, label_inicio);
                    $label_fim           = pg_fetch_result($res, 0, label_fim);
                    $label_intervalo     = pg_fetch_result($res, 0, label_intervalo);

                    while($label_inicio <= $label_fim){

                        if(!isset($tipo_de_respostas[$label_inicio]['name'])){
                            $tipo_de_respostas[$label_inicio]['name'] = "$label_inicio";
                            $tipo_de_respostas[$label_inicio]['data'] = array(0);
                        }

                        $label_inicio = $label_inicio+ $label_intervalo;
                    }
                }

                $sql = "SELECT txt_resposta FROM tbl_resposta WHERE pergunta = ".$perguntas[$key]." AND pesquisa = $pesquisa;";
                $res = pg_query($con, $sql);
                for($i=0; $i<pg_num_rows($res); $i++){
                    $txt_resposta = pg_fetch_result($res, $i, 'txt_resposta');

                    $valor_resposta = $tipo_de_respostas[$txt_resposta]['data'][$key];
                    $valor_resposta = $valor_resposta + 1;
                    $tipo_de_respostas[$txt_resposta]['data'][$key] = $valor_resposta;
                }
            }

            $respostas = $respostas_geral;
            if($tipo_descricao[$key] != 'range'){
                $sql = "SELECT  COUNT(tbl_resposta.tipo_resposta_item)                  AS qtde_respostas,
                                to_ascii(tbl_tipo_resposta_item.descricao, 'LATIN1')    AS descricao
                        FROM    tbl_tipo_resposta_item
                   LEFT JOIN    tbl_pergunta        USING (tipo_resposta)
                   LEFT JOIN    tbl_resposta        ON  tbl_resposta.tipo_resposta_item = tbl_tipo_resposta_item.tipo_resposta_item
                                                    AND tbl_resposta.pergunta           = tbl_pergunta.pergunta
                                                    AND tbl_resposta.pesquisa           = $pesquisa";
                if(!empty($posto_nome) || !empty($posto_local) || !empty($posto_linha)){
                    $sql .= "
                   LEFT JOIN    tbl_posto           ON  tbl_posto.posto                 = tbl_resposta.posto
                        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto         = tbl_posto.posto
                                                    AND tbl_posto_fabrica.fabrica       = $login_fabrica
                        JOIN    tbl_posto_linha     ON  tbl_posto_linha.posto           = tbl_posto.posto
                        JOIN    tbl_linha           ON  tbl_linha.linha                 = tbl_posto_linha.linha
                                                    AND tbl_linha.fabrica               = $login_fabrica
                    ";
                }
                $sql .= "
                        WHERE   tbl_pergunta.pergunta = $value
                        and tbl_pergunta.ativo
                        $conditionPosto
                        $conditionLinha
                        $conditionLocal
                        $conditionData
                  GROUP BY      tbl_tipo_resposta_item.descricao";

                $resRespostas   = pg_query($con,$sql);

                for ($i=0; $i < pg_num_rows($resRespostas); $i++) {

                    $descricao_tipo_resposta_item = pg_fetch_result($resRespostas, $i, 'descricao');
                    $qtde_respostas = pg_fetch_result($resRespostas, $i, 'qtde_respostas');

                    if ($tipo_de_respostas[$descricao_tipo_resposta_item]['name'] == $descricao_tipo_resposta_item) {

                        $tipo_de_respostas[$descricao_tipo_resposta_item]['data'][] = (int)$qtde_respostas;

                    }

                    $descricoes_respostas_usadas[] = trim($descricao_tipo_resposta_item);

                }
            }

            $total_respostas = count($respostas);

            for ($x=0; $x <$total_respostas; $x++) {

                for ($z=0; $z < count($descricoes_respostas_usadas); $z++) {

                    if ($respostas[$x] === $descricoes_respostas_usadas[$z]) {
                        unset($respostas[$x]);
                        $respostas_restantes = $respostas;

                    }
                }
            }

            foreach ($respostas_restantes as $key => $value) {
                $tipo_de_respostas[$value]['data'][] =(int)0;
            }

            unset($descricoes_respostas_usadas);

        }

        foreach ($tipo_de_respostas as $key => $value) {
            $arraySeries[] = $value;
        }


        echo $respostaJson = json_encode($arraySeries);

    }

	if (isset($_GET['getChartCategories'])) {

		$pesquisa       = $_GET['pesquisa'];
        $posto_nome     = $_GET['posto_nome'];
        $posto_linha    = $_GET['posto_linha'];
        $posto_local    = $_GET['posto_local'];
        $data_inicial   = $_GET['data_inicial'];
        $data_final     = $_GET['data_final'];

        $conditionPosto = (!empty($posto_nome)) ? " AND tbl_posto.nome = '$posto_nome' " : '';
        $conditionLinha = (!empty($posto_linha)) ? " AND tbl_posto_linha.linha = '$posto_linha' " : '';
        $conditionLocal = (!empty($posto_local)) ? " AND UPPER(fn_retira_especiais(tbl_posto.cidade)) = UPPER('$posto_local') " : '';
        $conditionData  = (!empty($data_inicial)) ? " AND tbl_resposta.data_input BETWEEN '$data_inicial' AND '$data_final'" : "";

		$sql = "SELECT  to_ascii(tbl_pergunta.descricao, 'LATIN1') as descricao ,
                        tbl_pesquisa_pergunta.ordem                             ,
                        tbl_pergunta.pergunta
                FROM    tbl_pesquisa
                JOIN    tbl_pesquisa_pergunta   USING (pesquisa)
                JOIN    tbl_pergunta            USING (pergunta)
                JOIN    tbl_tipo_resposta       USING (tipo_resposta)
                WHERE   tbl_pesquisa.fabrica                = $login_fabrica
				AND     tbl_tipo_resposta.tipo_descricao    IN ('radio','checkbox','range')
                AND     tbl_pesquisa.pesquisa               = $pesquisa
                AND     tbl_pergunta.pergunta IN (
                            SELECT  DISTINCT
                                    pergunta
                            FROM    tbl_resposta
                            WHERE   pesquisa = $pesquisa
							$conditionData
                        )
          ORDER BY      tbl_pesquisa_pergunta.ordem
			";

		$resPerguntas = pg_query($con,$sql);

		if (pg_num_rows($resPerguntas)>0) {
			for ($x=0; $x < pg_num_rows($resPerguntas); $x++) {
				$perguntas[$x] = pg_fetch_result($resPerguntas, $x, 'descricao');
			}
		}

		echo json_encode($perguntas);

	}
	exit;

}
