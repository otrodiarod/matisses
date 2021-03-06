
{capture name=path}
	<a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">
		{l s='Mi cuenta'}
	</a>
	<i class="fa fa-angle-right"></i>
	<span class="navigation_page">{l s='Garantías'}</span>
{/capture}
{include file="$tpl_dir./errors.tpl"}
<div class="block-center warranty-order" id="block-history">
<input type="hidden" id="pagegarantias" value="1" />
<h1>{l s='Mis garantías'}</h1>
<p class="info-title">{l s='Lista de pedidos desde la creación de su cuenta.'}</p>
{if $slowValidation}
	<p class="alert alert-warning">{l s='If you have just placed an order, it may take a few minutes for it to be validated. Please refresh this page if your order is missing.'}</p>
{/if}
<div class='tbl-responsive'>
<div class="block-center" id="block-history">
	{if $orders && count($orders)}
		<table id="order-list" class="table table-bordered footab">
			<thead>
				<tr>
					<th data-sort-ignore="true"  class="first_item">{l s='Número de pedido'}</th>
                    <th data-sort-ignore="true"  class="item">{l s='Date'}</th>
                    <th data-sort-ignore="true"  class="item">{l s='Total de pedido'}</th>
                    <th data-sort-ignore="true"  class="item">{l s='Status'}</th>
                    <th data-sort-ignore="true"  class="item">{l s='Número de autorización'}</th>
                    <th data-sort-ignore="true"  class="item">{l s='Número de factura'}</th>
                    <!--
					<th data-sort-ignore="true" data-hide="phone,tablet" class="item">{l s='Payment'}</th>
					<th data-sort-ignore="true" data-hide="phone,tablet" class="item">{l s='Invoice'}</th>
                    -->
					<th data-sort-ignore="true" data-hide="phone,tablet" class="last_item">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$orders item=order name=myLoop}
					<tr class="{if $smarty.foreach.myLoop.first}first_item{elseif $smarty.foreach.myLoop.last}last_item{else}item{/if} {if $smarty.foreach.myLoop.index % 2}alternate_item{/if}">
						
                        <td class="history_link bold">
							{$order.id_order}
						</td>
                        <td data-value="{$order.date_add|regex_replace:"/[\-\:\ ]/":""}" class="history_date bold">
							{dateFormat date=$order.date_add full=0}
						</td>
                        
                        <td class="history_price" data-value="{$order.total_paid}">
							<span class="price">
								{displayPrice price=$order.total_paid currency=$order.id_currency no_utf8=false convert=false}
							</span>
						</td>
                        
                        <td{if isset($order.order_state)} data-value="{$order.id_order_state}"{/if} class="history_state">
							{if isset($order.order_state)}
								<span class="label{if isset($order.order_state_color) && Tools::getBrightness($order.order_state_color) > 128} dark{/if}"{if isset($order.order_state_color) && $order.order_state_color} style="background-color:{$order.order_state_color|escape:'html':'UTF-8'}; border-color:{$order.order_state_color|escape:'html':'UTF-8'};"{/if}>
									{$order.order_state|escape:'html':'UTF-8'}
								</span>
							{/if}
						</td>
                        <td>{$order.cus}</td>
                        
                        <td class="history_link bold">
							{if isset($order.invoice) && $order.invoice && isset($order.virtual) && $order.virtual}
								<img class="icon" src="{$img_dir}icon/download_product.gif"	alt="{l s='Products to download'}" title="{l s='Products to download'}" />
							{/if}
							<a class="color-myaccount" href="javascript:showOrder(1, {$order.id_order|intval}, '{$link->getPageLink('order-detail', true)|escape:'html':'UTF-8'}');">
								{$order.idFacture}
							</a>
						</td>
						
						
						<!--<td class="history_method">{$order.payment|escape:'html':'UTF-8'}</td>-->
						
                        <!--
						<td class="history_invoice">
							{if (isset($order.invoice) && $order.invoice && isset($order.invoice_number) && $order.invoice_number) && isset($invoiceAllowed) && $invoiceAllowed == true}
								<a class="link-button" href="{$link->getPageLink('pdf-invoice', true, NULL, "id_order={$order.id_order}")|escape:'html':'UTF-8'}" title="{l s='Invoice'}" target="_blank">
									<i class="icon-file-text large"></i>{l s='PDF'}
								</a>
							{else}
								-
							{/if}
						</td>
                        -->
						<td class="history_detail">
							<a class="btn btn-default btn-red" href="javascript:showOrder(1, {$order.id_order|intval}, '{$link->getPageLink('order-detail', true)|escape:'html':'UTF-8'}');">
								<span>
									{l s='Detalles'}<i class="icon-chevron-right right"></i>
								</span>
							</a>
							
						</td>
                        
					</tr>
                    <tr>
                    	<td colspan="7" class="detail-order hidden" id="{$order.id_order}">
                        	<div id="block-order-detail"></div>
                        </td>
                    </tr> 
				{/foreach}
			</tbody>
		</table>
		<div id="block-order-detail" class="unvisible">&nbsp;</div>
	{else}
		<p class="alert alert-warning">{l s='No tienes ningún orden.' mod='matisses'}</p>
	{/if}
</div>
</div>
<div class="footer_links cf grid_12 omega alpha">
		<a class="btn btn-default button btn-red" href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">
			<i class="fa fa-chevron-left"></i>{l s='Volver a mi cuenta'}
		</a>
</div>
</div>
