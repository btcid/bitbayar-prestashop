{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" rel="nofollow" title="{l s='Go back to the Checkout' mod='bitbayar'}">{l s='Checkout' mod='bitbayar'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Bitcoin payment' mod='bitbayar'}
{/capture}

{* include file="$tpl_dir./breadcrumb.tpl" *}

<h2>{l s='Order summary' mod='bitbayar'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{l s='Bitcoin payment with bitbayar' mod='bitbayar'}</h3>

<p>
	{l s='You have chosen to pay with bitcoins.' mod='bitbayar'}
	<br/><br />
	{l s='The total amount of your order is' mod='bitbayar'}
	<span id="amount_{$currencies.0.id_currency}" class="price">{convertPrice price=$total}</span>
	{if $use_taxes == 1}
		{l s='(tax incl.)' mod='bitbayar'}
	{/if}
</p>
<p>
	<b>{l s='Please confirm your order by clicking \'Bitcoin via BitBayar\'.' mod='bitbayar'}</b>
</p>


<p class="cart_navigation">
		<table width="100%" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td align="left" valign="top">
				<a href="{$this_path}create_invoice.php">
					{if $button_pay=='text'}
						<strong>Bitcoin via BitBayar</strong>
					{else}
						<img src="{$this_path}views/img/bitbayar-pay-{$button_pay}.png" alt="bitbayar">
					{/if}
				</a>
			</td>
			<td align="right" valign="top">
				<a href="{$base_dir_ssl}index.php?controller=order&step=3" class="button_large">{l s='Other payment methods' mod='bitbyar'}</a>
			</td>
		</tr>
		</table>
</p>
