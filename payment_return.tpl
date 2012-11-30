<h3>{l s='Parabéns! Seu pedido foi efetuado com sucesso.' mod='pagseguro'}</h3>

<strong>Pedido nº</strong> #{$id_order} <br/>
<strong>Valor</strong> {convertPriceWithCurrency price=$total currency=$currency} <br/>
<strong>Código da Transação</strong> {$codigo_pagseguro} <br/>
<strong>Status de Pagamento</strong> {$status} <br/>

<br/>

<p>{l s='Em caso de dúvidas favor utilize o' mod='pagseguro'}	<a href="{$link->getPageLink('contact-form.php', true)}">{l s='formulário de contato' mod='cheque'}</a>.</p>
<br />