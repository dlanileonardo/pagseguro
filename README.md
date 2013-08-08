[![Build Status](https://travis-ci.org/dlanileonardo/pagseguro.png)](https://travis-ci.org/dlanileonardo/pagseguro)
[![Coverage Status](https://coveralls.io/repos/dlanileonardo/pagseguro/badge.png)](https://coveralls.io/r/dlanileonardo/pagseguro)
[![Latest Stable Version](https://poser.pugx.org/dlanileonardo/pagseguro/v/stable.png)](https://packagist.org/packages/dlanileonardo/pagseguro)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/dlanileonardo/pagseguro/badges/quality-score.png?s=4efe7bb52542ff5f25d10a6684daea43905d9b5a)](https://scrutinizer-ci.com/g/dlanileonardo/pagseguro/)

Módulo Pagseguro
================

Módulo do Prestashop para pagamentos via PagSeguro (Open Source).

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

Isenção de Responsabilidade
---------------------------

Este software é gratuito e não está associado com o PagSeguro. PagSeguro é uma marca registrada da empresa UOL. Este módulo não é afiliado com a UOL e portanto não é um produto oficial do PagSeguro.
