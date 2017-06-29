{*
/**
 * Monetivo for Prestashop
 *
 *  @author    monetivo <hello@monetivo.com>
 */
*}

<div class="row">
	<div class="col-xs-12 col-md-12">
		<p class="payment_module" id="monetivo_payment_button">
			<a href="{$link->getModuleLink('monetivo', 'redirect', array(), true)|escape:'htmlall':'UTF-8'}" title="{l s='Pay with my payment module' mod='monetivo'}">
				<img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/logo.png" style="width:95px;" alt="{l s='Pay with monetivo' mod='monetivo'}" />
				{l s='Pay with monetivo' mod='monetivo'}
			</a>
		</p>
	</div>
</div>
