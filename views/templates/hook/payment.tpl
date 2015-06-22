<p class="payment_module">
	<a class="bankwire {$this_name}" href="{$link->getModuleLink('bitbayar', 'payment')|escape:'html'}" title="{l s='Pay with Bitcoin' mod='{$this_name}'}">
	{l s='Pay with Bitcoin - BitBayar' mod='{$this_name}'}
	</a>
</p>

<style>
	a.{$this_name} {
		background-image: url("{$modules_dir}{$this_name}/views/img/logo_payment.png") !important;
	}
</style>