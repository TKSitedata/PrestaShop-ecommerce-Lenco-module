{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Lenco Technologies Inc. <support@lenco.co>
 * @copyright Since 2024 Lenco Technologies Inc.
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{extends file='page.tpl'}

{block name='content'}
<section class="lenco-pending-section">
    <div class="lenco-pending-container">
        <div class="lenco-pending-icon">
            <i class="material-icons">hourglass_empty</i>
        </div>
        
        <h2>{l s='Payment Verification in Progress' d='Modules.Lenco.Shop'}</h2>
        
        <p>{l s='Your payment is being verified. This may take a few moments.' d='Modules.Lenco.Shop'}</p>
        
        <p class="lenco-reference">
            <strong>{l s='Order Reference:' d='Modules.Lenco.Shop'}</strong> {$order_reference}
        </p>

        <div class="lenco-pending-actions">
            <a href="{$link->getPageLink('order-detail', true, null, ['id_order' => $order_id])|escape:'html'}" class="btn btn-primary">
                {l s='View Order Details' d='Modules.Lenco.Shop'}
            </a>
            
            <a href="{$link->getPageLink('index')|escape:'html'}" class="btn btn-secondary">
                {l s='Continue Shopping' d='Modules.Lenco.Shop'}
            </a>
        </div>

        <p class="lenco-pending-note">
            {l s='If you have completed the payment but still see this page, please wait a few minutes and check your order history.' d='Modules.Lenco.Shop'}
        </p>
    </div>
</section>

<script type="text/javascript">
    // Auto-refresh after 30 seconds
    setTimeout(function() {
        location.reload();
    }, 30000);
</script>
{/block}
