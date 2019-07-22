<?php
namespace Onion\Framework\Dependency\Traits;

use Onion\Framework\Dependency\Interfaces\AttachableContainer;
use Onion\Framework\Dependency\Interfaces\DelegateContainerInterface;

trait DelegateContainerTrait
{
    private $containers = [];

    public function attach(AttachableContainer $attachable): void
    {
        if ($attachable instanceof DelegateContainerInterface) {
            throw new \InvalidArgumentException(
                "Attachable containers can't be delegates"
            );
        }

        $attachable->attach($this);
        $this->containers[] = $attachable;
    }

    public function getAttachedContainers(): iterable
    {
        return $this->containers;
    }

    public function has($id)
    {
        foreach ($this->getAttachedContainers() as $container) {
            /** @var ContainerInterface $container */
            if ($container->has($id)) {
                return true;
            }
        }

        return false;
    }

    public function count(): int
    {
        return count($this->getAttachedContainers());
    }
}
