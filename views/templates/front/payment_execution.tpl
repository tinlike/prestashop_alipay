{capture name=path}
    {l s='Alipay payment.' mod='tinlike'}
{/capture}

<h1 class="page-heading">
    {l s='Order summary' mod='tinlike'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="alert alert-warning">
        {l s='Your shopping cart is empty.' mod='tinlike'}
    </p>
{else}
    <form method="post">
        <div class="box cheque-box">
            <h3 class="page-subheading">
                {l s='Alipay payment.' mod='tinlike'}
            </h3>
            <p class="cheque-indent">
                <strong class="dark">
                    {l s='You have chosen to pay by Alipay.' mod='tinlike'} {l s='Here is a short summary of your order:' mod='tinlike'}
                </strong>
            </p>
            <p>
                - {l s='The total amount of your order is' mod='tinlike'}
                <span id="amount" class="price">{displayPrice price=$total}</span>
                {if $use_taxes == 1}
                    {l s='(tax incl.)' mod='tinlike'}
                {/if}
            </p>
            <p>
                -
                {if $currencies|@count > 1}
                    {l s='We allow several currencies to be sent via Alipay.' mod='tinlike'}
                    <div class="form-group">
                        <label>{l s='Choose one of the following:' mod='tinlike'}</label>
                        <select id="currency_payement" class="form-control" name="currency_payement">
                            {foreach from=$currencies item=currency}
                                <option value="{$currency.id_currency}" {if $currency.id_currency == $cust_currency}selected="selected"{/if}>
                                    {$currency.name}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                {else}
                    {l s='We allow the following currency to be sent via Alipay:' mod='tinlike'}&nbsp;<b>{$currencies.0.name}</b>
                    <input type="hidden" name="currency_payement" value="{$currencies.0.id_currency}" />
                {/if}
            </p>
            <p>
                - {l s='Alipay account information will be displayed on the next page.' mod='tinlike'}
                <br />
                - {l s='Please confirm your order by clicking "I confirm my order."' mod='tinlike'}.
            </p>
        </div><!-- .cheque-box -->

    </form>
	<form action="{$this_path_ssl}" id="alipaysubmit" name="alipaysubmit" method="post">
	    {foreach from=$aliparams key=k item=v}
           <input type="hidden" name="{$k}" value="{$v}">
        {/foreach}
	    <p class="cart_navigation clearfix" id="cart_navigation">
        	<a 
            class="button-exclusive btn btn-default" 
            href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='tinlike'}
            </a>
            <button 
            class="button btn btn-default button-medium" 
            type="submit">
                <span>{l s='I confirm my order' mod='tinlike'}<i class="icon-chevron-right right"></i></span>
            </button>
        </p>
	</form>
{/if}
