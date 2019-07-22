<?php
namespace Onion\Framework\Dependency\Traits;

use Onion\Framework\Dependency\Interfaces\DelegateContainerInterface;
use Onion\Framework\Dependency\Interfaces\AttachableContainer;

trait AttachableContainerTrait
{
    private $delegate;

    public function attach(DelegateContainerInterface $delegate): void
    {
        if ($delegate instanceof AttachableContainer) {
            throw new \InvalidArgumentException(
                "Delegate containers can't be attachable"
            );
        }

        $this->delegate = $delegate;
    }

    protected function getDelegate(): DelegateContainerInterface
    {
        return $this->delegate;
    }
}
