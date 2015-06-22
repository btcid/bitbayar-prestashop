{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='bitbayar'}">{l s='Checkout' mod='bitbayar'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='BitBayar payment' mod='bitbayar'}
{/capture}


<h2 class="page-heading">{l s='Order summary' mod='bitbayar'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $total_products <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='bitbayar'}</p>
{else}

<form action="{$link->getModuleLink('bitbayar', 'validation', [], true)|escape:'html'}" method="post">
<div class="box cheque-box">
	<h3 class="page-subheading">{l s='BitBayar payment' mod='bitbayar'}</h3>
	<p class="cheque-indent">
		<img src="{$this_path_bw}views/img/logo-bitbayar.png" alt="{l s='BitBayar' mod='bitbayar'}" style="float:left; margin: 0px 10px 5px 0px;" />
		<strong class="dark">{l s='You have chosen to pay by BitBayar. ' mod='bitbayar'}
		<br/>
		{l s='Here is a short summary of your order:' mod='bitbayar'}</strong>
	</p>
	<p style="margin-top:20px;">
		- {l s='The total amount of your order is' mod='bitbayar'}
		<span id="amount" class="price">{displayPrice price=$total}</span>
		{if $use_taxes == 1}
			{l s='(tax incl.)' mod='bitbayar'}
		{/if}
	</p>

	{if $currencies|@count > 1}
		<p>
			{$def_currency}{l s='- We only allow Rupiah(IDR) currency to be sent via BitBayar.' mod='bitbayar'}
			<br /><br />
			<div class="form-group">
				<label>{l s='Choose rupiah currency:' mod='bitbayar'}</label>
				
				<select id="currency_payement" name="currency_payement" onchange="setCurrency($('#currency_payement').val());">
					{foreach from=$currencies item=currency}
						<option value="{$currency.id_currency}" {if $currency.id_currency == $cust_currency}selected="selected"{/if}>{$currency.name}</option>
					{/foreach}
				</select>
			</div>
		</p>
		<p>
			<br/><b>{l s='Please confirm your order by clicking "Bitcoin via BitBayar".' mod='bitbayar'}</b>
		</p>
	{else}
		<p>
			{l s='- We only allow Rupiah(IDR) currency to be sent via BitBayar:' mod='bitbayar'}</br>
			<input type="hidden" name="currency_payement" value="{$currencies.0.id_currency}" />
		<p>
		<p>
			<br/><b>{l s='Please contact this site administrator".' mod='bitbayar'}</b>
		</p>
	{/if}


</div>

<p class="cart_navigation clearfix" id="cart_navigation">
	<a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}">
		<i class="icon-chevron-left"></i>Other payment methods
	</a>
	<a href="{$this_path}create_invoice.php">
		{if $button_pay=='text'}
			<button class="button btn btn-default button-medium" type="submit">
                <span>Bitcoin via BitBayar<i class="icon-chevron-right right"></i></span>
            </button>
		{else}
			<img src="{$this_path}views/img/bitbayar-pay-{$button_pay}.png" style="float:right;" alt="bitbayar">
		{/if}
	</a>
</p>

</form>
{/if}