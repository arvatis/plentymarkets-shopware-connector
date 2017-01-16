<?php

namespace PlentymarketsAdapter\QueryBus\QueryHandler\PaymentStatus;

use PlentyConnector\Connector\QueryBus\Query\PaymentStatus\FetchAllPaymentStatusesQuery;
use PlentyConnector\Connector\QueryBus\Query\QueryInterface;
use PlentyConnector\Connector\QueryBus\QueryHandler\QueryHandlerInterface;
use PlentymarketsAdapter\PlentymarketsAdapter;
use PlentymarketsAdapter\ResponseParser\ResponseParserInterface;

/**
 * Class FetchAllPaymentStatusesQueryHandler
 */
class FetchAllPaymentStatusesQueryHandler implements QueryHandlerInterface
{
    /**
     * @var ResponseParserInterface
     */
    private $responseParser;

    /**
     * FetchAllPaymentStatusesQueryHandler constructor.
     *
     * @param ResponseParserInterface $responseParser
     */
    public function __construct(ResponseParserInterface $responseParser)
    {
        $this->responseParser = $responseParser;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(QueryInterface $query)
    {
        return $query instanceof FetchAllPaymentStatusesQuery &&
            $query->getAdapterName() === PlentymarketsAdapter::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(QueryInterface $query)
    {
        $paymentStatuses = array_map(function ($status) {
            return $this->responseParser->parse($status);
        }, $this->getPaymentStatuses());

        return array_filter($paymentStatuses);
    }

    /**
     * @return array
     */
    private function getPaymentStatuses()
    {
        return [
            [
                'id' => 1,
                'name' => 'Awaiting approval',
            ],
            [
                'id' => 2,
                'name' => 'Approved',
            ],
            [
                'id' => 3,
                'name' => 'Captured',
            ],
            [
                'id' => 4,
                'name' => 'Partially captured',
            ],
            [
                'id' => 5,
                'name' => 'Cancelled',
            ],
            [
                'id' => 6,
                'name' => 'Refused',
            ],
            [
                'id' => 7,
                'name' => 'Awaiting renewal',
            ],
            [
                'id' => 8,
                'name' => 'Expired',
            ],
            [
                'id' => 9,
                'name' => 'Refunded',
            ],
            [
                'id' => 10,
                'name' => 'Partially refunded',
            ],
        ];
    }
}