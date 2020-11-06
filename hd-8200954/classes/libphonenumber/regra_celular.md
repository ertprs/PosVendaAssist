## Alteração de regras
### Nono dígito para celulares do Brasil
Este arquivo é para informar dos passos necessários para a alteração das regras de validação de
celulares, mais específicamente, a adição do nono dígito de forma gradativa em todo o país.

#### Alterações
Atualmente o celular no **Call Center** é validado tanto em tela (javascript) quanto durante o
processamento do **POST** (PHP).

#### Javascript
A validação em tela é feita pelo _script_ `admin/js/phoneparser.js`. Este arquivo está num repositório
do github, e toda vez que faço alguma alteração, faço um _pull-request_ para eles terem o script
também atualizado.

As alterações são nas linhas `245` (aprox. col. 380), `246` (± col. 400) e `247` (± 190).

#### PHP
Para alterar as regras de validação dos celulares do Brasil, até a implementação final em **2017**,
precisa apenas alterar o arquivo `classes/libphonenumber/data/PhoneNumberMetadata_BR.php`.

Existem três pontos de alteração:

1. **Validação inicial**
Aproximadamente na linha `35` tem a _regex_ que valida se o número é _mobile_. Acrescentar um "atom"
para o prefixo a ser adicionado.  No array o caminho seria `mobile=>NationalNumberPattern`

**`NOTA`**: Quando estiver 100% implementado, esta regra será mais simples, pois apenas será o DDD.

2. **Formatação I e II**
Aproximadamente na linha `172` outra _regex_ serve para iniciar a formatação do número celular. Aqui
tem também que acrescentar o mesmo "atom". AS `keys` seriam `numberFormat=>3=>leadingDigitsPatterns=>0`.
Mesma alteração para a linha `225`+.


