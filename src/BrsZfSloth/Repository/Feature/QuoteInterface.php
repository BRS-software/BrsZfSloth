<?php

namespace BrsZfSloth\Definition\Feature;

interface QuoteInterface
{
    public function quoteValue($value);
    public function quoteIdentifier($ident);
}