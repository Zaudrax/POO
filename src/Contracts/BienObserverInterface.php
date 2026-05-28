<?php

declare(strict_types=1);

interface BienObserverInterface
{
    public function update(BienImmobilier $bien, BienStatut $ancienStatut, BienStatut $nouveauStatut): void;
}
