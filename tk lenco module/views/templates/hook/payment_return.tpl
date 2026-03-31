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

<section id="lenco-payment-return">
    {if $status === 'ok'}
        <p class="lenco-success">
            {l s='Your payment has been processed successfully.' d='Modules.Lenco.Shop'}
        </p>
        <p class="lenco-reference">
            {l s='Order Reference: %reference%' d='Modules.Lenco.Shop' sprintf=['%reference%' => $reference]}
        </p>
    {else}
        <p class="lenco-error">
            {l s='Your payment could not be processed. Please contact us for assistance.' d='Modules.Lenco.Shop'}
        </p>
    {/if}
</section>
