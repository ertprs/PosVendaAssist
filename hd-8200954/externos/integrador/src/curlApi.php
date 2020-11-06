<?php

class curlApi {

    public $finalResult = null;

    public function init() {
        $ch = curl_init();
        return $ch;
    }

    public function post($url, $headers, $params = null) {
        $ch = $this->init();

        $defaults = array(
            CURLOPT_POST => 1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_HTTPHEADER => $headers
        );



        curl_setopt_array($ch, $defaults);

        if (!$result = curl_exec($ch)) {
            trigger_error(curl_error($ch));
        }
        
        $res = $this->makeResult($ch, $result);        
        return $res;
    }

    public function get($url, $headers) {
        $ch = $this->init();

        $defaults = array(
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HTTPHEADER => $headers
        );

        curl_setopt_array($ch, $defaults);

        if (!$result = curl_exec($ch)) {
            trigger_error(curl_error($ch));
        }

        $res = $this->makeResult($ch, $result);

        return $res;
    }

    public function put($url, $headers, $params = null) {
        $parametros = "";
        foreach ($params as $key => $value) {
            if($parametros == ""){                
                $parametros .= $key."=".$value;
            }else{                
                $parametros .= "&".$key."=".$value;
            }            
        }

        $ch = $this->init();
        $defaults = array(
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_VERBOSE => false,
            CURLOPT_HEADER => 1,
            CURLOPT_POST => count($params),
            CURLOPT_POSTFIELDS => $parametros,
            CURLOPT_HTTPHEADER => $headers
        );
        
        curl_setopt_array($ch, $defaults);

        if (!$result = curl_exec($ch)) {
            trigger_error(curl_error($ch));
        }

        $res = $this->makeResult($ch, $result);                
        return $res;
    }

    public function delete($url, $headers, $params = null) {
        $ch = $this->init();

        $defaults = array(
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_VERBOSE => false,
            CURLOPT_HEADER => 1,
            CURLOPT_HTTPHEADER => $headers
        );

        curl_setopt_array($ch, $defaults);

        if (!$result = curl_exec($ch)) {
            trigger_error(curl_error($ch));
        }

        $res = $this->makeResult($ch, $result);                
        return $res;
    }

    public function makeResult($ch, $result) {
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $res['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $res['last_url'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $res['header'] = substr($result, 0, $header_size);
        $res['body'] = substr($result, $header_size);
        $res['req_header'] = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        
        $this->finalResult = $res;
        return $res;
    }

}

?>
