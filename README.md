Módulo Pagseguro
================

Módulo do Prestashop para pagamentos via PagSeguro.

Features
--------

* Utiliza a API de pagamentos;
* Maior segurança;
* Com um passo a menos no pagamento;

Requisitos
----------

* Ativação da API de pagamentos no sistema do Pagseguro;
* Ativação de retorno e notificações no sistema do Pagseguro;
* Ativação do Retorno do ID da Transação no sistema do Pagseguro;
* Token de segurança;
* Biblioteca CURL;
* PHP 5.3 (ou superior);
* CronJob (Recomendado);

Observações
------------

> Por conta do fluxo de pagamento da API o relacionamento entre o pedido (loja) e a transação (Pagseguro)
> acontece de maneira frágil que depende do retorno do cliente a loja.
> Por isso criei uma rotina que deve ser acionada periodicamente (1 vez por dia no minimo), ela busca
> todas as transações via API e relaciona aos pedidos do Prestashop.

> O Nome do parametro da transação deve ser transaction_id (Configurável no Sistema do Pagseguro)

Observação Importante
---------------------

> Ao fazer o download o github adiciona um prefixo ou sufixo ao nome do arquivo zip ou tar.gz.
> Logo que você baixar o arquivo renomeie-o para pagseguro.zip ou pagseguro.tar.gz