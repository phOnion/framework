<?php

declare(strict_types=1);

namespace Onion\Framework\Router;

use Closure;
use Onion\Framework\Router\Interfaces\{
    CollectorInterface,
    ParserInterface
};
use Onion\Framework\Router\Route;
use Psr\Http\Server\MiddlewareInterface;
use Traversable;

class Collector implements CollectorInterface
{
    private const FRAME_SIZE = 64;

    private int $mark = 0;
    private array $markData = [];
    private array $patterns = [];

    private string $groupPrefix = '';

    public function __construct(private readonly ParserInterface $parser)
    {
    }

    public function add(
        array $methods,
        string $path,
        Closure|MiddlewareInterface $action,
    ): void {
        $this->markData[++$this->mark] = (new Route($path))->withMethods($methods)->withAction($action);

        $this->patterns[] = $this->groupPrefix . $this->parser->parse($path) . "(*MARK:{$this->mark})";
    }

    public function group(string $prefix, Closure $fn): void
    {
        $this->groupPrefix = $this->parser->parse($prefix);
        $fn($this);
        $this->groupPrefix = '';
    }

    public function getIterator(): Traversable
    {
        $patternChunks = \array_chunk($this->patterns, static::FRAME_SIZE);
        $dataChunk = \array_chunk($this->markData, static::FRAME_SIZE, true);

        foreach ($patternChunks as $idx => $chunk) {
            yield \implode('|', $chunk) => $dataChunk[$idx];
        }
    }
}
