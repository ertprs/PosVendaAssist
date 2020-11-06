<?php
/**
 * @author Brayan L. Rastelli
 * @description Cadastrar Auditoria. HD 896786
 */
 
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';

	$admin_privilegios	= "inspetor";

	include 'autentica_admin.php';

	/**
	 * Ajax que retorna uma table com as perguntas, e os inputs/radios/etc de acordo com o tipo de resposta, 
	 * Agrupando por Requisitos (tipo de pergunta)
	 */
	if ( ( isset($_GET['ajax']) && isset($_GET['pesquisa']) ) || (isset($_POST['pesquisa'])) ) {

        if (isset($_GET['ajax']))
		  header('Content-type: text/html; charset=iso-8859-1');

		$pesquisa = (int) isset($_GET['pesquisa']) ? $_GET['pesquisa'] : $_POST['pesquisa'];

		$sql = "SELECT  tbl_pergunta.pergunta           ,
                    	tbl_pergunta.descricao          ,
	                    tipo_resposta                   ,
	                    tbl_tipo_resposta.tipo_descricao,
	                    tbl_tipo_pergunta.descricao as tipo_pergunta_descricao,
	                    label_inicio                    ,
	                    label_fim                       ,
	                    label_intervalo
               	FROM 	tbl_pesquisa_pergunta
               	JOIN 	tbl_pergunta      USING(pergunta)
               	JOIN 	tbl_tipo_resposta USING(tipo_resposta)
               	JOIN 	tbl_tipo_pergunta USING(tipo_pergunta)
               	JOIN    tbl_tipo_relacao  ON tbl_tipo_relacao.tipo_relacao = tbl_tipo_pergunta.tipo_relacao AND tbl_tipo_relacao.sigla_relacao = 'D'
              	WHERE tbl_pesquisa_pergunta.pesquisa = $pesquisa
                AND tbl_pergunta.ativo IS TRUE
            	ORDER BY tbl_pesquisa_pergunta.ordem , tbl_tipo_pergunta.descricao ";

        $res = pg_query($con, $sql);

        if (pg_errormessage($con) || pg_num_rows($res) == 0) {

        	header("HTTP/1.0 404 Not Found");
        	// Envia msg do banco para debugar, a mensagem será tratada via javascript
        	echo (pg_errormessage($con)) ? pg_errormessage($con) : 'Nenhuma pergunta encontrada'; 
        	return;

        }

        $data = array();

    	for ($i=0; $i < pg_num_rows($res); $i++) { 
    		
    		$requisito = pg_result($res, $i, 'tipo_pergunta_descricao');

    		$data[$requisito][] = array(

    			'pergunta' 			=>	pg_result($res, $i, 'pergunta'),
    			'descricao' 		=>	pg_result($res, $i, 'descricao'),
    			'tipo_resposta'		=> 	pg_result($res, $i, 'tipo_resposta'),
    			'tipo_resposta'		=> 	pg_result($res, $i, 'tipo_resposta'),
    			'tipo_descricao' 	=> 	pg_result($res, $i, 'tipo_descricao'),
    			'inicio' 			=>	pg_result($res, $i, 'label_inicio'),
    			'fim'	 			=>	pg_result($res, $i, 'label_fim'),
    			'intervalo'	 		=>	pg_result($res, $i, 'label_intervalo'),

    		);

    	}
        echo '<table class="tabela" width="700">' . PHP_EOL;
            
            foreach ($data as $requisito => $perguntas) {

                echo '<tr class="titulo_coluna">
                        <td colspan="3">'.$requisito.'</td>
                      </tr>
                      <tr class="titulo_coluna">
                        <td>Pergunta</td>
                        <td>Resposta</td>
                        <td>Evidência</td>
                      </tr>';

                foreach ($perguntas as $pergunta) {

        			switch ($pergunta['tipo_descricao']) {

                        case 'text' : $input = '<input type="text" class="frm" name="pergunta['.$pergunta['pergunta'].']" value="'. $_POST['pergunta'][$pergunta['pergunta']]. ' " />'; break;

                        case 'range':

                            $input = '';

                            if ($pergunta['inicio'] < $pergunta['fim']) {

                                for ($k = $pergunta['inicio']; $k <= $pergunta['fim']; $k += $pergunta['intervalo']) {

                                    $input_checked = $_POST['pergunta'][$pergunta['pergunta']] == $k ? 'checked' : '';
                                    $input .= '<input type="radio" class="frm" '.$input_checked.' name="pergunta['.$pergunta['pergunta'].']" value="'.$k.'" id="pergunta['.$pergunta['pergunta'].']['.$k.']" /><label for="pergunta['.$pergunta['pergunta'].']['.$k.']">' . $k . '</label> &nbsp;';

                                }

                            } else {

                                for ($k = $pergunta['inicio']; $k >= $pergunta['fim']; $k -= $pergunta['intervalo']) {

                                    $input_checked = $_POST['pergunta'][$pergunta['pergunta']] == $k ? 'checked' : '';
                                    $input .= '<input type="radio" class="frm" '.$input_checked.' name="pergunta['.$pergunta['pergunta'].']" value="'.$k.'" id="pergunta['.$k.']" /><label for="pergunta['.$k.']">' . $k . '</label> &nbsp;';

                                }

                            }

                            break;

                        case 'textarea': $input = '<textarea name="pergunta['.$pergunta['pergunta'].']" class="frm" style="width: 345px; height: 120px;"></textarea>'; break;

                        case 'radio':

                            $sql_pesquisa = "SELECT descricao,
                                                    tipo_resposta_item
                                               FROM tbl_tipo_resposta_item
                                              WHERE tipo_resposta = {$pergunta['tipo_resposta']}
                                           ORDER BY ordem DESC";

                            $res3_pesquisa = pg_query($con, $sql_pesquisa);
                            $input         = '';
                            
                            for ($k = 0; $k < pg_num_rows($res3_pesquisa); $k++) {

                                $tipo_resposta_item = pg_result($res3_pesquisa, $k, 'tipo_resposta_item');
                                $descricao_item     = pg_result($res3_pesquisa, $k, 'descricao');

                                $input_checked = $_POST['pergunta'][$pergunta['pergunta']] == $tipo_resposta_item ? 'checked' : '';

                                $input .= '<input type="radio" name="pergunta['.$pergunta['pergunta'].']" value="'.$tipo_resposta_item.'" id="radio['.$pergunta['pergunta'].']['.$tipo_resposta_item.']"  ' . $input_checked . ' /><label for="radio['.$pergunta['pergunta'].']['. $tipo_resposta_item .']">' . $descricao_item . '</label> &nbsp;';

                            }

                            break;

                        case 'checkbox' :

                            $sql_pesquisa = "SELECT descricao,
                                                    tipo_resposta_item
                                               FROM tbl_tipo_resposta_item
                                              WHERE tipo_resposta = {$pergunta['tipo_resposta']}
                                           ORDER BY ordem DESC";

                            $res3_pesquisa = pg_query($con, $sql_pesquisa);
                            $input         = '';

                            for ($k = 0; $k < pg_num_rows($res3_pesquisa); $k++) {

                                $tipo_resposta_item = pg_result($res3_pesquisa, $k, 'tipo_resposta_item');
                                $descricao_item     = pg_result($res3_pesquisa, $k, 'descricao');

                                $checked = ($_POST['checkbox'][$pergunta['pergunta']][$tipo_resposta_item] == 't') ? 'checked' : '';

                                $input .= '<input type="checkbox" name="checkbox['.$pergunta['pergunta'].']['.$tipo_resposta_item.']" '.$checked.' value="t" id="checkbox['.$pergunta['pergunta'].']['.$tipo_resposta_item.']" />
                                          <label for="checkbox['.$pergunta['pergunta'].']['. $tipo_resposta_item .']">' . $descricao_item . '</label> &nbsp;';

                            }

                            break;

                        default : $input = null;

                    }

        			echo '<tr>

        					<td>'.$pergunta['descricao'].'</td>
        					<td nowrap>'.$input.'</td>
        					<td align="center"><input type="text" name="obs['.$pergunta['pergunta'].']" value=" '. $_POST['obs'][$pergunta['pergunta']]. '" class="frm" /></td>

        				  </tr>';

        		}

        	}

        echo '</table>';
        
        return;

	}
