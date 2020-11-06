<?php

namespace Posvenda;
use \PhpSigep\Model\PreListaDePostagem as PreListaDePostagem;

Class GeraColeta extends PreListaDePostagem{

    private $dados_coleta;
    private $pre_lista_postagem;

    public function execute($dados){
        $this->setDadosColeta($dados);
        $this->criar_coleta();
        $this->gerarEncomendas();
    }

    public function setDadosColeta($dados_coleta){
        $this->dados_coleta = $dados_coleta;
    }

    public function getPreListaPostagem(){
        return $this->pre_lista_postagem;
    }

    public function criar_coleta(){
        $contrato = $this->dados_coleta['contrato'];

        $accessData = new \PhpSigep\Model\AccessData();
        $accessData->setCodAdministrativo($contrato['codigo_administrativo']);
        $accessData->setUsuario($contrato['usuario']); //$contrato['usuario']
        $accessData->setSenha($contrato['senha']);
        $accessData->setCartaoPostagem($contrato['cartao']);
        $accessData->setCnpjEmpresa($contrato['cnpj']);
        $accessData->setNumeroContrato($contrato['contrato']);
        $accessData->setAnoContrato($contrato['ano']);

        $diretoria = new \PhpSigep\Model\Diretoria(74);
        $diretoria->setNumero(74);
        $diretoria->setNome("DR - Sao Paulo Interior");
        $diretoria->setSigla("SPI");
        $diretoria->setSigla("SPI");

        $accessData->setDiretoria($diretoria);

        $this->setAccessData($accessData);

        $remetente          = new \PhpSigep\Model\Remetente();
        $dados_remetente    = $this->dados_coleta['remetente'];
        $consultaCEP        = new \PhpSigep\Services\SoapClient\Real();
        $endereco_remetente = $consultaCEP->consultaCep($dados_remetente['cep']);
        
        $remetente->setNumeroContrato(NULL);
        $remetente->setDiretoria('74');
        $remetente->setCodigoAdministrativo(NULL);
        $remetente->setNome($this->acentos($dados_remetente['nome']));
        $remetente->setLogradouro(strtoupper($this->acentos($endereco_remetente->getResult()->getEndereco())));
        $remetente->setNumero($dados_remetente['numero']);
        $remetente->setComplemento("");
        $remetente->setBairro($this->acentos($endereco_remetente->getResult()->getBairro()));
        $remetente->setCep($dados_remetente['cep']);
        $remetente->setCidade(strtoupper($this->acentos($endereco_remetente->getResult()->getCidade())));
        $remetente->setUf($dados_remetente['estado']);
        $remetente->setTelefone($dados_remetente["telefone"]);
        $remetente->setFax("");
        $remetente->setEmail("");

        $this->setRemetente($remetente);
    }

    public function gerarEncomendas(){
        $this->dados_coleta = $this->dados_coleta['dados_coleta'];

        for($i=0; isset($this->dados_coleta[$i]); $i++){
            $total_nota = (float) str_replace(',', '.', trim($this->dados_coleta[$i]['total_nota']));

            $etiqueta = new \PhpSigep\Model\Etiqueta();
           $etiqueta->setEtiquetaComDv($this->dados_coleta[$i]['etiqueta']); 
           $etiqueta2 = $this->dados_coleta[$i]['etiqueta']; 
           $etiqueta2 = substr($etiqueta2,0,-3)."BR";
           //$etiqueta->setEtiquetaSemDv($this->dados_coleta[$i]['etiqueta']);
           $etiqueta->setEtiquetaSemDv($etiqueta2);
            //$etiqueta->setDv($this->dados_coleta[$i]['digito']);

            $servicoDePostagem = new \PhpSigep\Model\ServicoDePostagem($this->dados_coleta[$i]['codigo']);
            $servicoDePostagem->setCodigo($this->dados_coleta[$i]['codigo']);
            $servicoDePostagem->setIdServico($this->dados_coleta[$i]['chave_servico']);
            $servicoDePostagem->setNome($this->dados_coleta[$i]['descricao']);

            $destinatario = new \PhpSigep\Model\Destinatario();
            $destinatario->setNome($this->acentos($this->dados_coleta[$i]['nome']));
            if(isset($this->dados_coleta[$i]['embarque'])){
                $destinatario->setEmbarque($this->dados_coleta[$i]['embarque']);
            } else {
                $destinatario->setEmbarque(NULL);
            }
            $destinatario->setTelefone(NULL);
            $destinatario->setCelular(NULL);
            $destinatario->setEmail(NULL);
            $destinatario->setLogradouro($this->acentos($this->dados_coleta[$i]['endereco']));
            $destinatario->setComplemento("");
            $destinatario->setNumero($this->dados_coleta[$i]['numero']);

            $destino = new \PhpSigep\Model\DestinoNacional();
            $destino->setBairro($this->acentos($this->dados_coleta[$i]['bairro']));
            $destino->setCep($this->dados_coleta[$i]['cep']);
            $destino->setCidade($this->acentos($this->dados_coleta[$i]['cidade']));
            $destino->setUf($this->dados_coleta[$i]['estado']);
            $destino->setNumeroNotaFiscal($this->dados_coleta[$i]['nota_fiscal']);
            $destino->setSerieNotaFiscal(NULL);
            $destino->setValorNotaFiscal($this->dados_coleta[$i]['total_nota']);
            $destino->setNaturezaNotaFiscal(NULL);
            $destino->setDescricaoObjeto(NULL);
            $destino->setValorACobrar(NULL);

            $servicosAdicionais[0] = new \PhpSigep\Model\ServicoAdicional();
            //$servicosAdicionais[0]->setCodigoServicoAdicional(25);
            $pacs = ['PAC', 'PAC CONTRATO AGENCIA'];
            if(in_array(trim($this->dados_coleta[$i]['descricao']), $pacs)) {
                $servicosAdicionais[0]->setCodigoServicoAdicional(64);
            }else{
                $servicosAdicionais[0]->setCodigoServicoAdicional(19);
            }
            if($total_nota > 20) { 
                $valorDeclarado = str_replace('.', ',', trim($this->dados_coleta[$i]['total_nota']));
                $servicosAdicionais[0]->setValorDeclarado($valorDeclarado);
            } else {
                $servicosAdicionais[0]->setValorDeclarado($this->dados_coleta[$i]['preco']);
            }
            $dimensao = new \PhpSigep\Model\Dimensao();
            $dimensao->setTipo('002');
            $dimensao->setAltura($this->dados_coleta[$i]['altura']);
            $dimensao->setLargura($this->dados_coleta[$i]['largura']);
            $dimensao->setComprimento($this->dados_coleta[$i]['comprimento']);
            $dimensao->setDiametro(NULL);

            $encomendas[$i] = new \PhpSigep\Model\ObjetoPostal();
            $encomendas[$i]->setEtiqueta($etiqueta);
            $encomendas[$i]->setServicoDePostagem($servicoDePostagem);
            $encomendas[$i]->setCubagem(NULL);
           // $encomendas[$i]->setCubagem($this->dados_coleta[$i]['cubagem']);
            $encomendas[$i]->setPeso($this->dados_coleta[$i]['peso']);
            $encomendas[$i]->setDestinatario($destinatario);
            $encomendas[$i]->setDestino($destino);
            $encomendas[$i]->setServicosAdicionais($servicosAdicionais);
            $encomendas[$i]->setDimensao($dimensao);
        }

        $this->setEncomendas(array($encomendas));
    }

    public function acentos($string) {
        $array1 = array("á" => "a", "à" => "a", "â" => "a", "ã" => "a", "ä" => "a", 
            "é" => "e", "è" => "e", "ê" => "e", "ë" => "e", 
            "í" => "i", "ì" => "i", "î" => "i", "ï" => "i", 
            "ó" => "o", "ò" => "o", "ô" => "o", "õ" => "o", "ö" => "o", 
            "ú" => "u", "ù" => "u", "û" => "u", "ü" => "u", 
            "ç" => "c", 
            "Á" => "A", "À" => "A", "Â" => "A", "Ã" => "A", "Ä" => "A", 
            "É" => "E", "È" => "E", "Ê" => "E", "Ë" => "E", 
            "Í" => "I", "Ì" => "I", "Î" => "I", "Ï" => "I", 
            "Ó" => "O", "Ò" => "O", "Ô" => "O", "Õ" => "O", "Ö" => "O", 
            "Ú" => "U", "Ù" => "U", "Û" => "U", "Ü" => "U", "Ç" => "C");
        $string = strtr(utf8_decode($string), $array1);

        return $string;
    }
}


?>
