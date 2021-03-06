<?php

namespace ShopwareAdapter\ResponseParser\VatRate;

use SystemConnector\TransferObject\VatRate\VatRate;

interface VatRateResponseParserInterface
{
    /**
     * @return null|VatRate
     */
    public function parse(array $entry);
}
