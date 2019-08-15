<?php
namespace Onion\Framework\Dependency;

use Onion\Framework\Common\Dependency\Traits\AttachableContainerTrait;
use Onion\Framework\Common\Dependency\Traits\ContainerTrait;
use Onion\Framework\Common\Dependency\Traits\WrappingContainerTrait;
use Onion\Framework\Dependency\Inflector\Inflector;
use Onion\Framework\Dependency\Inflector\Invocation;
use Onion\Framework\Dependency\Interfaces\AttachableContainer;
use Onion\Framework\Dependency\Interfaces\WrappingContainerInterface;
use Psr\Container\ContainerInterface;

class InflectorContainer implements ContainerInterface, WrappingContainerInterface, AttachableContainer
{
    /** @var Inflector[][] */
    private $inflections = [];

    use ContainerTrait, WrappingContainerTrait, AttachableContainerTrait;

    public function inflect(string $key): Inflector
    {
        return $this->inflections[$key][] = new Inflector($key);
    }

    public function get($id)
    {
        /** @var object $result */
        $result = $this->getWrappedContainer()->get($id);

        if (!is_object($result)) {
            return $result;
        }

        if (isset($this->inflections[$id])) {
            $result = $this->doInflect($result, $this->inflections[$id]);
        } else {
            foreach ($this->inflections as $id => $values) {
                $id = (string) $id;

                if ($result instanceof $id) {
                    $result = $this->doInflect($result, $values);
                }
            }
        }

        return $result;
    }

    public function has($id)
    {
        return $this->getWrappedContainer()->has($id);
    }

    private function doInflect(object $target, $inflections): object
    {
        foreach ($inflections as $inflation) {
            foreach ($inflation->getMethods() as $invocation) {
                /** @var Invocation $invocation */
                $sets = $invocation->getParameterSets();
                if (empty($sets)) {
                    $r = $target->{$invocation->getMethod()}();
                } else {
                    foreach ($sets as $set) {
                        $r = $target->{$invocation->getMethod()}(...$set);

                        if ($r == $target) {
                            if ($r !== $target) {
                                $target = $r;
                            }
                        }
                    }
                }
            }
        }

        return $target;
    }
}
