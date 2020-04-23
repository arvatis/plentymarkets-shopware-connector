<?php

namespace PlentyConnector\Components\CustomProducts\ShopwareAdapter\ResponseParser\Order;

use Shopware\Components\Model\ModelManager;
use ShopwareAdapter\ResponseParser\OrderItem\OrderItemResponseParser;
use ShopwareAdapter\ResponseParser\OrderItem\OrderItemResponseParserInterface;
use SwagCustomProducts\Components\Services\CustomProductsServiceInterface;
use SwagCustomProducts\Models\Option;
use SwagCustomProducts\Models\Value;
use SystemConnector\TransferObject\Order\OrderItem\OrderItem;

class DecoratedOrderItemResponseParser implements OrderItemResponseParserInterface
{
    /**
     * @var OrderItemResponseParserInterface
     */
    private $parentOrderItemResponseParser;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var CustomProductsServiceInterface
     */
    private $customProductsService;

    public function __construct(
        OrderItemResponseParserInterface $parentOrderItemResponseParser,
        ModelManager $modelManager,
        CustomProductsServiceInterface $customProductsService
    ) {
        $this->parentOrderItemResponseParser = $parentOrderItemResponseParser;
        $this->modelManager = $modelManager;
        $this->customProductsService = $customProductsService;
    }

    public function parse(array $entry, $taxFree = false): ?OrderItem
    {
        if (OrderItemResponseParser::ITEM_TYPE_ID_SURCHARGE === $entry['mode']) {
            if (null !== $this->modelManager->getRepository(Value::class)->findOneBy(['ordernumber' => $entry['articleNumber']]) ||
                null !== $this->modelManager->getRepository(Option::class)->findOneBy(['ordernumber' => $entry['articleNumber']])
            ) {
                $configurations = $this->customProductsService->getOptionsFromHash($entry['attribute']['swagCustomProductsConfigurationHash']);

                $name = $entry['articleName'];
                $configuration = array_values(array_filter($configurations, static function (array $configuration) use ($name) {
                    return $name === $configuration['label'];
                }));

                if (!empty($configuration) && !$configuration[0]['multi']) {
                    $entry['articleName'] .= ' [ VALUE: ' . $configuration[0]['value'] . ' ]';
                }

                $entry['mode'] = OrderItem::TYPE_PRODUCT;
            }
        }

        return $this->parentOrderItemResponseParser->parse($entry, $taxFree);
    }
}
