<?php
namespace Onion\Framework\Dependency\Interfaces;

interface DelegateContainerInterface
{
    public function attach(AttachableContainer $container): void;
}
