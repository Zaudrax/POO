<?php

declare(strict_types=1);

final class ConsoleBienObserver implements BienObserverInterface
{
    public function __construct(private string $canal)
    {
    }

    public function update(BienImmobilier $bien, BienStatut $ancienStatut, BienStatut $nouveauStatut): void
    {
        echo '[' . $this->canal . '] Bien #' . $bien->getId()
            . ' (' . $bien->getCity() . ') statut: '
            . $ancienStatut->value . ' -> ' . $nouveauStatut->value
            . PHP_EOL;
    }
}
