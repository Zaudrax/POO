<?php
declare(strict_types=1);

interface ExportableInterface
{
    public function toExportArray(): array;
}
