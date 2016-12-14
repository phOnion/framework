<?php
declare(strict_types=1);
namespace Onion\Framework\Http\Header\Interfaces;

interface Header
{
    public function __construct(string $headerValue);
}
