# TcCommunicator  Plug-In
A API TcCommunicator é um serviço de envio de mensagens.

Para informações específicas do **Communicator**, consulte a [documentação do Communicator][tccommdoc] no **APIary**.

Por enquanto gerencia apenas e-mails, mas é prevista uma nova funcionalidade de envio de SMS.

Os métodos desta primeira versão contêm a palavra 'Email' para poder acrescentar em futuras versões os métodos para os outros meios de comunicação que possam ser acrescentados à API.

## TcComm

Histórico:
- Primeira versão (6-01-2016), apenas envia e-mail, por enquanto sem possibilidade de anexo, mas já está prevista para a próxima revisão.
- Segunda versão (15-08-2016), acrescenta anexos e consulta e atualização da _blacklist_ da API.

Esta classe comunica com a **TcCommunicator**, filtrando e processando informações como e-mail e corpo de mensagem para enviar a través da API. A **TcCommunicator**, por exemplo, não aceita o envio de arquivos anexos. Esta classe, a partir da versão 2, permite o processamento de arquivos como anexos do e-mail, gerando manualmente um corpo de mensagem `multipart`.

## Métodos
Para instanciar um objeto desta classe, o único parâmetro obrigatório é o `ExternalId`, que está, salvo na coluna `tbl_fabrica.parametros_adicionais`, e é copiado na variável `$externalId` nos arquivos "autentica(_usuario|_admin)".

    $mailer = new TcComm($externalId);

A classe fornece as seguintes funcionalidades:

### Adicionar destinatários
`TcComm::addEmailDest($endereco)`, `TcComm::setEmailDest($endereco)`

Permite adicionar um ou mais endereços de destino. O parâmetro pode ser *string* ou *array*. Mesmo como string, é possível adicionar mais de um e-mail de destino usando a vírgula ou o ponto-e-vírgula como separador.

    $mailer->setEmailDest($email_cliente);
    $mailer->addEmailDest('email@server1.com,email2@server2.com');

O método `setEmailDest()` tem quase o mesmo comportamento, mas ele sobrescreve o(s) endereço(s), enquanto `addEmailDest()` adiciona o email à lista. A API não aceita a parte do nome da [sintaxe padrão][RFC 5322] para os endereços

### Adicionar o remetente
`TcComm::setEmailFrom($endereco)`
Estabelece o e-mail que irá enviar a mensagem. Pode conter ou não o nome do usuário, seguindo as [RFC][RFC 5322] sobre endereços de e-mail.

```php
    $mailer->setEmailFrom('"Meu Nome" <meu.nome@servidor.me>');
    $mailer->setEmailFrom("meu.nome@servidor.me");
```
### Estabelecer o Assunto da mensagem
`TcComm::setEmailSubject($string)`

Estabelece o assunto ou título da mensagem. é um valor único, portanto substitui qualquer valor anterior.

### Estabelecer o conteúdo da mensagem
`TcComm::setEmailBody($content)`
Estabelece o conteúdo da mensagem, o "corpo". Se é passado um array, ele será simplesmente serializado como *string* na hora do envio. Substitui o valor anterior do _body_.

### Adicionar texto ao corpo da mensagem
`addToEmailBody($content)`
Adiciona o `$content` ao corpo da mensagem. Útil para ir adicionando de maneira seletiva frases ou parágrafos ao corpo da mensagem.

Se o body (antes de ser interpretado) é _String_, `$content` será concatenado diretametne no `body`. Se o body é _array_ (ver  `setBody()`), será adicionado como um novo item e serializado quando a mensagem for enviada. Este método não interpreta arquivos ou nomes de arquivo, portanto não serve para adicionar anexos à mensagem.

### Adicionar arquivo em anexo
`TcComm::addAttachment($path)`
Este método permite informar um ou mais nomes de arquivo, um por vez se `$path` for _string_, ou um array simples com vários nomes de arquivo, eles são acrescentados à lista. Quando a mensagem é enviada, os arquivos são processados, compondo uma mensagem `multipart/mixed` com o corpo da mensagem e o(s) arquivo(s) da lista.

Este é o único método para adicionar arquivos como anexo, assim, deve ser usado antes do `sendMail()`:

```php
    $mailer = new TcComm($extId);
    $mailer->addAttachment('/path/to/file/filename.ext')
        ->sendMail(
        'destino1@server1.com,destino2@server2.com',
        'Assunto do email',
        $corpo,
        'noreply@server.id'
    );
```


### Enviar a mensagem
`TcComm::sendMail($to, $subject, $body, $from)`
Este é o método que envia a mensagem à API **TcCommunicator**.

Todos os parâmetros são opcionais, e servem para realizar o envio usando apenas um método, informado os valores necessários. Assim, este código:

```php
    $mailer = new TcComm($extId);
    $mailer->setEmailFrom('noreply@server.id')
        ->addEmailDest('destino1@server1.com,destino2@server2.com')
        ->setEmailSubject('Assunto do email')
        ->setEmailBody($corpo)
        ->sendMail();
```

E este outro:

```php
    $mailer = new TcComm($extId);
    $mailer->sendMail(
        'destino1@server1.com,destino2@server2.com',
        'Assunto do email',
        $corpo,
        'noreply@server.id'
    );
```

São funcionalmente iguais. Um detalhe a considerar é que ao usar os parâmetros do método `sendMail()` os valores dos argumentos **SOBRESCREVEM** os valores que já estiverem armazenados, para garantir que o que está na chamada à função será enviado à API.

Este método retorna o ID interno do TcCommunicator se teve êxito, ou `FALSE` se houve algum erro. Próximas versões poderão copiar o erro da API num atributo "erro" do objeto.

### BlackList de endereços
A API Communicators mantém uma lista de endereços que tem dado erros em algum momento ao enviar mensagens. é a chamada _blacklist_. quando uma mensagem nao é enviada, é provável que seja porque o destinatário está na lista.
Para saber se está ou não, e para verificar que o endereçõ agora é válido e recebe e-mails, temos dois métodos disponíveis.

#### Consultar se um e-mail está bloqueado
`TcComm::isBlocked($emails)`

Método que retorna `TRUE` ou `FALSE`, dependendo se o endereço informado está na _blacklist_
da API Communicators.

Se for passado um array de endereços (ou endereços separados por `,` ou `;`, conforme aceita o método `parseEmail()`), este método retorna um array contendo **apenas** os endereços **que estiverem na *blacklist* **, juntamente com o motivo. O retorno deste método é salvo na propriedade `TcComm::$why`.

    $bloqueado = $this->isBlocked('endereco@email.com');

Neste caso, `$bloqueado` poderá ser `TRUE` ou `FALSE`.

    $bloqueado = $this->isBlocked('endereco@email.com')->why;

Neste outro, `$bloqueado` contém o texto do motivo do bloqueio, pois recebe o valor da propriedade `why`.

    $bloqueado = $this->isBlocked(array('end1@email.com', 'end2@email.com'));

Se ambos os endereços estão na _blacklist_, pode ser algo assim:

    array(
        0 => array(
            'email' => 'end1@email.com',
            'motivo' => '2016-02-25 - SC-001 (BAY004-MC5F7) Unfortunately ...'
        ),
        1 => array(
            'email' => 'end1@email.com',
            'motivo' => '2016-02-25 - SC-001 (BAY004-MC5F7) Unfortunately ...'
        )
     )

Cabe então ao desenvolvedor interpretar o resultado.

#### Validar um e-mail como apto para receber mensagens
`TcComm::blackListVerify($email)`

Com este método podemos alterar o status de **um endereço de e-mail** que está na _blacklist_ da API para "válido", permitindo assim o envio de mensagens para esse destinatário. O método retorna `TRUE` ou `FALSE` dependendo da resposta da API.

#### Interpretar e validar endereços de e-mail
`static TcComm::parseEmail(mixed $emails)`

A classe fornece um método estático para interpretar, validar e formatar endereços de correio eletrônico. Foi deixado como público para oferecer um método de validação rápida de endereços, e poder assim informar o desenvolvedor se o endereço ou a lista de endereços será aceita ou não.

Este método aceita string, que pode conter mais de um e-mail, separado por vírgulas, ou um array. Os itens do array podem também conter mais de um e-mail, desde que estejam separados por vírgula.

O retorno é um array com os endereços **válidos** encontrados, um por item. Os endereços acompanhados pelos nomes são formatados conforme a correspondente [RFC][RFC 5322].

### TO-DOs
06-01-2016<br />
Funcionalidades ainda não presentes e consideradas importantes:

  - [x] Envio de arquivos anexos
  - [ ] Aceitar URL como arquivos para anexos
  - [ ] estabelecer endereços para os campos
    - `Return-Path`
    - `Reply-To`
    - `CC` e `BCC` do e-mail.
  - [ ] retorno do erro enviado pela API (TcComm::erro), e tratar os mais comuns
  - [ ] Getter para o documento: getEml() retornaria um texto pronto para ser importado, exportado ou enviado para um cliente de e-mail ou um servidor

Parte destes recursos dependem da disponibilidade dos mesmos na API, como foi o caso do acesso à _blacklist_.

##### Histórico
- 24-06-2016 Acrescentados métodos para blacklist.
  - Revisão da documentação
  - métodos para criar um body com anexos:
    - `addAttachment()`    (public)
    - `multipartEmail()`   (private)
    - `file_to_eml_part()` (private)
- 28-04-2016 Revisão da documentação
- 20-07-2016 Acrescentando novas funcionalidades (blacklist)
- 10-08-2016 Revisão da doc., acrescentando novas funcionalidades (anexos)


*[RFC]: Documento do tipo Request For Comments da Internet Engineering Task Force (IETF®)
*[IETF]: Internet Engineering Task Force®
[RFC 5322]: https://tools.ietf.org/html/rfc5322 "RFC 5322: Multipurpose Interchange Mail Extensions"
[RFC 2822]: https://tools.ietf.org/html/rfc5322 "RFC 2822: Multipurpose Interchange Mail Extensions"
[RFC 822]: https://tools.ietf.org/html/rfc5322 "RFC 822: E-Mail. Original version after ARPANET standard"
[tccommdoc]: http://docs.tccommunicators.apiary.io/#reference "Documentação Communicators"


