<?php

declare(strict_types=1);

final class ExportContext
{
    public function __construct(private ExporterInterface $strategy)
    {
    }

    public function setStrategy(ExporterInterface $strategy): void
    {
        $this->strategy = $strategy;
    }

    /**
     * @param array<int|string, mixed> $items
     */
    public function export(array $items): string
    {
        return $this->strategy->export($items);
    }
}
