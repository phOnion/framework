<?php declare(strict_types=1);
namespace Onion\Framework\Log;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class VoidLogger implements LoggerInterface, LoggerAwareInterface
{
    use LoggerTrait;

    public function log($level, $message, array $context = array())
    {
        // Emptiness...
    }
}
