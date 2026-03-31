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
<section class="lenco-payment-section">
    <div class="lenco-payment-container" style="text-align: center; padding: 40px; max-width: 600px; margin: 0 auto;">
        <h2 style="margin-bottom: 20px;">{l s='Complete Your Payment' d='Modules.Lenco.Shop'}</h2>
        
        <div class="lenco-payment-details" style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <p class="lenco-amount" style="font-size: 24px; margin-bottom: 10px;">
                <strong>{l s='Amount:' d='Modules.Lenco.Shop'}</strong> 
                {$currency} {$amount|string_format:"%.2f"}
            </p>
            <p class="lenco-reference" style="color: #666;">
                <strong>{l s='Reference:' d='Modules.Lenco.Shop'}</strong> 
                {$reference}
            </p>
        </div>

        <button type="button" id="lenco-pay-button" class="btn btn-primary btn-lg" style="padding: 15px 40px; margin-bottom: 15px;">
            <i class="material-icons" style="vertical-align: middle;">payment</i>
            {l s='Pay with Lenco' d='Modules.Lenco.Shop'}
        </button>

        <br>

        <a href="{$link->getPageLink('order', true, null, ['step' => 3])|escape:'html'}" class="btn btn-link" id="cancel-link">
            {l s='Cancel and return to checkout' d='Modules.Lenco.Shop'}
        </a>

        <div id="payment-status" style="display: none; margin-top: 20px; padding: 15px; border-radius: 8px;"></div>

        <div id="manual-check" style="display: none; margin-top: 20px;">
            <p style="color: #666; margin-bottom: 10px;">{l s='Payment completed? Click below to verify.' d='Modules.Lenco.Shop'}</p>
            <a href="{$validation_url}" class="btn btn-secondary" id="check-status-btn">
                <i class="material-icons" style="vertical-align: middle;">check_circle</i>
                {l s='Check Payment Status' d='Modules.Lenco.Shop'}
            </a>
        </div>
    </div>
</section>

<script src="{$js_url}"></script>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        var payButton = document.getElementById('lenco-pay-button');
        var statusDiv = document.getElementById('payment-status');
        var manualCheckDiv = document.getElementById('manual-check');
        var checkStatusBtn = document.getElementById('check-status-btn');
        
        var paymentInitiated = false;
        var validationUrl = '{$validation_url}';
        
        function showStatus(message, type) {
            statusDiv.style.display = 'block';
            statusDiv.innerHTML = message;
            if (type === 'success') {
                statusDiv.style.background = '#d4edda';
                statusDiv.style.color = '#155724';
            } else if (type === 'error') {
                statusDiv.style.background = '#f8d7da';
                statusDiv.style.color = '#721c24';
            } else {
                statusDiv.style.background = '#fff3cd';
                statusDiv.style.color = '#856404';
            }
        }
        
        function redirectToValidation(lencoReference) {
            var url = validationUrl;
            if (lencoReference) {
                url += '&lenco_reference=' + encodeURIComponent(lencoReference);
            }
            showStatus('<i class="material-icons" style="vertical-align: middle;">check_circle</i> {l s="Payment successful! Redirecting..." d="Modules.Lenco.Shop"}', 'success');
            window.location.href = url;
        }
        
        payButton.addEventListener('click', function() {
            payButton.disabled = true;
            payButton.innerHTML = '<i class="material-icons" style="vertical-align: middle;">hourglass_empty</i> {l s="Processing..." d="Modules.Lenco.Shop"}';
            paymentInitiated = true;
            
            setTimeout(function() {
                if (paymentInitiated) {
                    manualCheckDiv.style.display = 'block';
                }
            }, 3000);
            
            try {
                LencoPay.getPaid({
                    key: '{$public_key}',
                    reference: '{$reference}',
                    email: '{$email}',
                    amount: {$amount},
                    currency: '{$currency}',
                    {if $channels|count > 0}
                    channels: {$channels|json_encode},
                    {/if}
                    onSuccess: function(response) {
                        paymentInitiated = false;
                        manualCheckDiv.style.display = 'none';
                        var lencoRef = response.reference || response.Reference || response.ref || '';
                        redirectToValidation(lencoRef);
                    },
                    onError: function(error) {
                        showStatus('<i class="material-icons" style="vertical-align: middle;">error</i> {l s="Payment failed. Please try again." d="Modules.Lenco.Shop"}', 'error');
                        payButton.disabled = false;
                        payButton.innerHTML = '<i class="material-icons" style="vertical-align: middle;">payment</i> {l s="Pay with Lenco" d="Modules.Lenco.Shop"}';
                    },
                    onClose: function() {
                        if (paymentInitiated) {
                            showStatus('<i class="material-icons" style="vertical-align: middle;">info</i> {l s="Payment window closed. If you completed payment, click below to verify." d="Modules.Lenco.Shop"}', 'warning');
                            manualCheckDiv.style.display = 'block';
                        }
                        payButton.disabled = false;
                        payButton.innerHTML = '<i class="material-icons" style="vertical-align: middle;">payment</i> {l s="Pay with Lenco" d="Modules.Lenco.Shop"}';
                    }
                });
            } catch(e) {
                showStatus('<i class="material-icons" style="vertical-align: middle;">error</i> {l s="Error loading payment. Please refresh and try again." d="Modules.Lenco.Shop"}', 'error');
                payButton.disabled = false;
                payButton.innerHTML = '<i class="material-icons" style="vertical-align: middle;">payment</i> {l s="Pay with Lenco" d="Modules.Lenco.Shop"}';
            }
        });

        if (checkStatusBtn) {
            checkStatusBtn.addEventListener('click', function(e) {
                e.preventDefault();
                redirectToValidation('');
            });
        }
    });
</script>
{/block}
