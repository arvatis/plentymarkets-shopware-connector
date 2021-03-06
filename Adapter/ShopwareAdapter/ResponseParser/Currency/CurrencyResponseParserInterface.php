<?php

namespace ShopwareAdapter\ResponseParser\Currency;

use SystemConnector\TransferObject\Currency\Currency;

interface CurrencyResponseParserInterface
{
    /**
     * @return null|Currency
     */
    public function parse(array $entry);
}
