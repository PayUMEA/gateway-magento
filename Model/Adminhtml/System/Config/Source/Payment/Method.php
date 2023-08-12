<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Adminhtml\System\Config\Source\Payment;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * class Method
 * @package PayU\Gateway\Model\Adminhtml\System\Config\Source\Payment
 */
class Method implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected array $paymentMethods = [
        'CREDITCARD' 		=> 'Credit Card',
        'PAYFLEX' 		    => 'Payflex',
        'CREDITCARD_PAYU' 	=> 'Credit Card (PayU)',
        'LOYALTY' 			=> 'Loyalty',
        'WALLET' 			=> 'Wallet',
        'WALLET_PAYU' 		=> 'Wallet (PayU)',
        'DISCOVERYMILES' 	=> 'Discovery Miles',
        'GLOBALPAY' 		=> 'Global Pay',
        'DEBITCARD' 		=> 'Debit Card',
        'EBUCKS' 			=> 'eBucks',
        'PAYPAL' 			=> 'Paypal',
        'EFT' 				=> 'EFT',
        'EFT_PRO' 			=> 'EFT Pro',
        'MASTERPASS' 		=> 'Master Pass',
        'RCS_PLC' 			=> 'RCS PLC',
        'RCS'				=> 'RCS',
        'FASTA'				=> 'FASTA Instant Credit',
        'MPESA'				=> 'Mpesa',
        'AIRTEL_MONEY'		=> 'Airtel Money',
        'MOBILE_BANKING'	=> 'Mobile Banking',
        'MTN_MOBILE'		=> 'MTN Mobile',
        'TIGOPESA'			=> 'Tigopesa',
        'EQUITEL'			=> 'Equitel',
        'MOBICRED'			=> 'Mobicred',
        'OPEN_BANKING'      => 'Capitec Pay',
        'MORETYME'          => 'MoreTyme'
    ];

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $paymentMethods = [];

        foreach ($this->paymentMethods as $key => $value) {
            $paymentMethods[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $paymentMethods;
    }

    /**
     * Get option value
     *
     * @param string $key
     * @return string
     */
    public function getValue(string $key): string
    {
        return $this->payments[$key] ?? '';
    }
}
