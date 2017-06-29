{*
/**
 * Monetivo for Prestashop
 * 
 *  @author    monetivo <hello@monetivo.com>
 */
*}

<div>
	<h3>{l s='Redirect your customer' mod='monetivo'}:</h3>
	<ul class="alert alert-info">
			<li>{l s='This action should be used to redirect your customer to the website of your payment processor' mod='monetivo'}.</li>
	</ul>
	
	<div class="alert alert-warning">
		{l s='You can redirect your customer with an error message' mod='monetivo'}:
		<a href="{$link->getModuleLink('monetivo', 'redirect', ['action' => 'error'], true)|escape:'htmlall':'UTF-8'}" title="{l s='Look at the error' mod='monetivo'}">
			<strong>{l s='Look at the error message' mod='monetivo'}</strong>
		</a>
	</div>
	
	<div class="alert alert-success">
		{l s='You can also redirect your customer to the confirmation page' mod='monetivo'}:
		<a href="{$link->getModuleLink('monetivo', 'confirmation', ['cart_id' => $cart_id, 'secure_key' => $secure_key], true)|escape:'htmlall':'UTF-8'}" title="{l s='Confirm' mod='monetivo'}">
			<strong>{l s='Go to the confirmation page' mod='monetivo'}</strong>
		</a>
	</div>
</div>
