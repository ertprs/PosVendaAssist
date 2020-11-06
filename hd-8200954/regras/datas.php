<?php

/* * **************************************
 * Desenvolvido para PHP 5.0 ou superior *
 * ************************************** */

/*
Esta classe possui funçs para validar datas, formatáas na forma brasileira (DD/MM/YYYY) ou internacional (YYYY-MM-DD) e .

Os formatos de data suportados pelas funçs sã

1. DD/MM/YYYY
2. DD/MM/YYYY hh:mm
3. DD/MM/YYYY hh:mm:ss
4. YYYY-MM-DD
5. YYYY-MM-DD hh:mm
6. YYYY-MM-DD hh:mm:ss
*/
class Data {
    /*
      bool ValidarData (string data)

      Essa funç valida a data passada como parâtro para o construtor da classe. Retorna TRUE se a data for váda. Caso contráo, retorna FALSE.
     */

    public function ValidarData($data) {
        if (!($dados = $this->explode_date($data))){
            return false;
	}

        //verifica se hálementos em $data
        if (count($dados) > 0) {//se houver elementos no array
            if (!checkdate($dados['mes'], $dados['dia'], $dados['ano'])) {
                return false;
            }

            //se houver o elemento 'horas', existiráminutos'
            if (array_key_exists("horas", $dados)) {
                if ($dados['horas'] > 23) {
                    return false;
                }
                if ($dados['minutos'] > 59) {
                    return false;
                }
            }
            if (array_key_exists("segundos", $dados)) {
                if ($dados['segundos'] > 59) {
                    return false;
                }
            }
        } else {//se nãhouver elementos no array
            return false;
        }

        return true;
    }

    /*
      mixed explode_date (string $data)

      Essa funç divide o argumento 'data' em dia, mêe ano. Se houver especificaç de horáo, divide-o em horas, minutos e segundos, se houver. Apóssa separaç, os valores sãcolocados no array $dados.

      As seguintes chaves (íices) do array podem ser retornadas:

      'dia'     => retorna o dia
      'mê     => retorna o mê      'ano'     => retorna o ano
      'hora'    => retorna a hora
      'minuto'  => retorna os minutos
      'segundo' => retorna os segundos

      Se o formato de 'data' for invádo, retorna FALSE.
     */

    private function explode_date($data) {
        //retira o excesso de espaç no inío e no final da data
        $data = trim($data);

        //retira o excesso de espaç, se existir
        $data = preg_replace("/( ){2,}/", " ", $data);

        switch (true) {
            case preg_match("/^([0-9]{2}\/){2}[0-9]{4}$/", $data):
                list ($dia, $mes, $ano) = explode("/", $data);
                break;
            case preg_match("/^([0-9]{2}\/){2}[0-9]{4} [0-9]{2}:[0-9]{2}$/", $data):
                $explode = explode(" ", $data);
                list ($dia, $mes, $ano) = explode("/", $explode[0]);
                list ($horas, $minutos) = explode(":", $explode[1]);
                break;
            case preg_match("/^([0-9]{2}\/){2}[0-9]{4} [0-9]{2}(:[0-9]{2}){2}$/", $data):
                $explode = explode(" ", $data);
                list ($dia, $mes, $ano) = explode("/", $explode[0]);
                list ($horas, $minutos, $segundos) = explode(":", $explode[1]);
                break;
            case preg_match("/^[0-9]{4}(-[0-9]{2}){2}$/", $data):
                list ($ano, $mes, $dia) = explode("-", $data);
                break;
            case preg_match("/^[0-9]{4}(-[0-9]{2}){2} [0-9]{2}:[0-9]{2}$/", $data):
                $explode = explode(" ", $data);
                list ($ano, $mes, $dia) = explode("-", $explode[0]);
                list ($horas, $minutos) = explode(":", $explode[1]);
                break;
            case preg_match("/^[0-9]{4}(-[0-9]{2}){2} [0-9]{2}(:[0-9]{2}){2}$/", $data):
                $explode = explode(" ", $data);
                list ($ano, $mes, $dia) = explode("-", $explode[0]);
                list ($horas, $minutos, $segundos) = explode(":", $explode[1]);
                break;
            default:
                return false;
                break;
        }

        $dados['dia'] = $dia;
        $dados['mes'] = $mes;
        $dados['ano'] = $ano;

        // se existir $hora, tambéexistiráminuto
        if (isset($horas)) {
            $dados['horas']   = $horas;
            $dados['minutos'] = $minutos;
        }
        if (isset($segundos)) {
            $dados['segundos'] = $segundos;
        }
        return $dados;
    }

    /*
	
      string FormatarData (string data[, string formato])

      Formata 'data' segundo o valor de 'formato', que deve ter um destes valaores:

      "pt" => retirna a data no formato DD/MM/YYYY hh:mm:ss
      "en" => retorna a data no formato YYYY-MM-DD hh:mm:ss

      Se formato nãfor especificado ou tiver um valor invádo, a data seráetornada na forma "pt".
	  
     */

    public function FormatarData($data, $formato = "pt") {
        $formato = strtolower($formato);
        if ($formato != "pt" AND $formato != "en")
            $formato = "pt";

        if (!$this->ValidarData($data))
            return false;

        $dados = $this->explode_date($data);

        if ($formato == "pt") {
            $return = $dados['dia'] . "/" . $dados['mes'] . "/" . $dados['ano'];
            if (array_key_exists("horas", $dados)) {
                $return .= " " . $dados['horas'] . ":" . $dados['minutos'];
                if (array_key_exists("segundos", $dados))
                    $return .= ":" . $dados['segundos'];
            }
        }
        else {
            $return = $dados['ano'] . "-" . $dados['mes'] . "-" . $dados['dia'];
            if (array_key_exists("horas", $dados)) {
                $return .= " " . $dados['horas'] . ":" . $dados['minutos'];
                if (array_key_exists("segundos", $dados))
                    $return .= ":" . $dados['segundos'];
            }
        }

        return $return;
    }

}

?>
