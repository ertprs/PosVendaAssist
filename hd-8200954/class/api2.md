## Refatoração das classes que interagem com a API2.

[TOC]

[tdocsDoc]: https://docs.tdocs.apiary.io/ "Documentação TDocs"

Após a criação da ***TcComm***, que faz de _interface_ com a ***API Communicator*** para envio de e-mail, temos mais
duas vindo:

- ***TDocs*** (para armazenamento de arquivos no S3) e
- ***Image Uploader***, também para armazenamento, mas desta vez com um _client mobile_.

Todas elas precisam se comunicar com a API2, que precisa de `HEADER` específicos, assim como em alguns casos a geração
de `Token`, baseado no ambiente de trabalho e a aplicação (_service_) a ser usado.

### Criada nova **Abstract** Class API2 (`class/abstractAPI2.class.php`)
Para não ter que repetir o mesmo código, com a carga extra de manutenção que isso levaria, temos agora uma _Abstract
Class_ onde são criadas as propriedades e os metodos comuns à comunicação com a API2. Sempre que for necessário abstrair
propriedades ou ações que tem a ver com a API2 e não com o serviço, deve ser incluso o código nesta _class_.

**Esta *class* fornece:**

- a URL geral da API (`const API2`)
- carrega a class [simpleREST](./simpleRestClient.md) e disponibiliza um objeto `api`
- carrega a `application key` conforme o objeto filho (serviço e ambiente) no objeto `api`
- método `fetchToken()`, que já adiciona o token recebido aos _headers_ do objeto `api`

### Class **TcComm** refatorada para usar a API2
Além da refatoração, tem algumas atualizações:

- novo método para adicionar anexo(s) ao e-mail `addAttachment(string $pathArquivo)`
- novo método `addToEmailBody(String $texto)` para adicionar texto ao corpo da mensagem
- **BlackList**
  - consulta e-mail com `TcComm::isBlocked(string $email)`, `true` ou `false`, se `true`, ler a propriedade `why`
  - desbloqueia um e-mail na API com `blackListVerify(string $email)`

