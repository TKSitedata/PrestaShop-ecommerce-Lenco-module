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

<div class="panel lenco-admin-panel">
    <div class="panel-heading">
        <i class="icon-credit-card"></i>
        {l s='Lenco Payment Information' d='Modules.Lenco.Shop'}
    </div>
    <div class="panel-body">
        {if $transaction}
            <p>
                <strong>{l s='Transaction Reference:' d='Modules.Lenco.Shop'}</strong>
                {$transaction.reference}
            </p>
            <p>
                <strong>{l s='Status:' d='Modules.Lenco.Shop'}</strong>
                <span class="badge {if $transaction.status === 'success'}badge-success{elseif $transaction.status === 'pending'}badge-warning{else}badge-danger{/if}">
                    {$transaction.status|capitalize}
                </span>
            </p>
            <p>
                <strong>{l s='Amount:' d='Modules.Lenco.Shop'}</strong>
                {$transaction.currency} {$transaction.amount|string_format:"%.2f"}
            </p>
            <p>
                <strong>{l s='Created:' d='Modules.Lenco.Shop'}</strong>
                {$transaction.created_at}
            </p>
        {else}
            <p>{l s='No transaction information available.' d='Modules.Lenco.Shop'}</p>
        {/if}
    </div>
</div>
