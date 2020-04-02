<?php

namespace ShopwareAdapter\ResponseParser\OrderItem;

use Doctrine\ORM\EntityRepository;
use Shopware\Models\Tax\Tax;
use Shopware_Components_Config;
use ShopwareAdapter\ResponseParser\GetAttributeTrait;
use ShopwareAdapter\ShopwareAdapter;
use SystemConnector\ConfigService\ConfigServiceInterface;
use SystemConnector\IdentityService\Exception\NotFoundException;
use SystemConnector\IdentityService\IdentityServiceInterface;
use SystemConnector\TransferObject\Order\OrderItem\OrderItem;
use SystemConnector\TransferObject\VatRate\VatRate;

class OrderItemResponseParser implements OrderItemResponseParserInterface
{
    use GetAttributeTrait;

    public const ITEM_TYPE_ID_VOUCHER = 2;
    public const ITEM_TYPE_ID_DISCOUNT = 3;
    public const ITEM_TYPE_ID_SURCHARGE = 4;

    /**
     * @var EntityRepository
     */
    private $taxRepository;

    /**
     * @var ConfigServiceInterface
     */
    private $configService;

    /**
     * @var IdentityServiceInterface
     */
    private $identityService;

    /**
     * @var Shopware_Components_Config
     */
    private $shopwareConfig;

    public function __construct(
        IdentityServiceInterface $identityService,
        EntityRepository $taxRepository,
        ConfigServiceInterface $configService,
        Shopware_Components_Config $shopwareConfig
    ) {
        $this->identityService = $identityService;
        $this->taxRepository = $taxRepository;
        $this->configService = $configService;
        $this->shopwareConfig = $shopwareConfig;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotFoundException
     */
    public function parse(array $entry, $taxFree = false)
    {
        if (empty($entry['attribute'])) {
            $entry['attribute'] = [];
        }

        /**
         * @var OrderItem $orderItem
         */
        return OrderItem::fromArray([
            'type' => $this->getItemType($entry),
            'quantity' => (float) $entry['quantity'],
            'name' => $entry['articleName'],
            'number' => $entry['articleNumber'],
            'price' => $this->getPrice($entry, $taxFree),
            'vatRateIdentifier' => $this->getVatRateIdentifier($entry, $taxFree),
            'attributes' => $this->getAttributes($entry['attribute']),
        ]);
    }

    private function getVatRateIdentifier(array $entry, bool $taxFree): string
    {
        if ($taxFree || $entry['taxId'] === 0) {
            /**
             * @var null|Tax $taxModel
             */
            $taxModel = $this->taxRepository->findOneBy(['tax' => $entry['taxRate']]);

            if (null === $taxModel) {
                throw new NotFoundException('no matching tax rate found - ' . $entry['taxRate']);
            }

            $entry['taxId'] = $taxModel->getId();
        }

        $vatRateIdentity = $this->identityService->findOneBy([
            'adapterIdentifier' => (string) $entry['taxId'],
            'adapterName' => ShopwareAdapter::NAME,
            'objectType' => VatRate::TYPE,
        ]);

        if (null === $vatRateIdentity) {
            throw new NotFoundException('missing vat rate identity for taxId ' . $entry['taxId']);
        }

        return $vatRateIdentity->getObjectIdentifier();
    }

    private function getItemType(array $entry): string
    {
        switch ($entry['mode']) {
            case self::ITEM_TYPE_ID_VOUCHER:
                return OrderItem::TYPE_VOUCHER;
            case self::ITEM_TYPE_ID_DISCOUNT:
                return OrderItem::TYPE_DISCOUNT;
            case self::ITEM_TYPE_ID_SURCHARGE:
                if (json_decode($this->configService->get('surcharge_as_product'), false)) {
                    return OrderItem::TYPE_PRODUCT;
                }

                if ($entry['articleNumber'] === $this->shopwareConfig->get('shippingSurchargeNumber') ||
                    $entry['articleNumber'] === $this->shopwareConfig->get('sSHIPPINGDISCOUNTNUMBER', 'SHIPPINGDISCOUNT')
                ) {
                    return OrderItem::TYPE_SHIPPING_COSTS;
                }

                return OrderItem::TYPE_PAYMENT_SURCHARGE;
            default:
                return OrderItem::TYPE_PRODUCT;
        }
    }

    private function getPrice(array $entry, bool $taxFree)
    {
        return $taxFree ? $entry['price'] + (($entry['price'] / 100) * $entry['taxRate']) :
            (float) $entry['price'];
    }
}
