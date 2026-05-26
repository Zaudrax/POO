<?php
declare(strict_types=1);

require_once __DIR__ . '/Appartement.php';
require_once __DIR__ . '/BienStatut.php';
require_once __DIR__ . '/ContactableInterface.php';
require_once __DIR__ . '/CsvExporter.php';
require_once __DIR__ . '/JsonExporter.php';
require_once __DIR__ . '/Maison.php';
require_once __DIR__ . '/Proprietaire.php';
require_once __DIR__ . '/SearchTerm.php';

/** @param array<int, BienImmobilier> $biens */
function rechercherBiens(array $biens, (Stringable&JsonSerializable)|string|int|null $recherche): array
{
    if ($recherche === null) {
        return $biens;
    }

    if (is_int($recherche)) {
        return array_values(array_filter(
            $biens,
            static fn (BienImmobilier $bien): bool => $bien->getId() === $recherche
        ));
    }

    $term = trim((string) $recherche);

    if ($term === '') {
        return $biens;
    }

    return array_values(array_filter(
        $biens,
        static fn (BienImmobilier $bien): bool => $bien->matchesText($term)
    ));
}

function afficherElementRecherche(IdentifiableInterface&TextSearchableInterface $bien, string $term): void
{
    $match = $bien->matchesText($term) ? 'oui' : 'non';
    echo 'Intersection type -> Bien #' . $bien->getId() . ' correspond a "' . $term . '" ? ' . $match . PHP_EOL;
}

function decrireContact((ContactableInterface&JsonSerializable)|string $contact): string
{
    if (is_string($contact)) {
        return 'Contact libre: ' . $contact;
    }

    $json = json_encode($contact, JSON_UNESCAPED_UNICODE);
    return 'Contact objet (DNF): ' . (string) $json;
}

function exporterCollectionJson(Countable&IteratorAggregate $collection, JsonExporter $jsonExporter): string
{
    $rows = [];
    foreach ($collection as $item) {
        $rows[] = $item;
    }

    return $jsonExporter->export($rows);
}

$owner1 = new Proprietaire(1, 'Martin', 'Julie', 'julie.martin@example.com', '0611223344', '10 rue de la Paix, Lyon');
$owner2 = new Proprietaire(2, 'Durand', 'Alex', 'alex.durand@example.com', '0699887766', '25 avenue des Fleurs, Nantes');

$biens = [
    new Appartement(101, 'Lyon', 180000, 45, 2, 3, true, false, BienStatut::DISPONIBLE, $owner1),
    new Appartement(102, 'Marseille', 140000, 38, 2, 'RDC', false, true, BienStatut::INDISPONIBLE, $owner1),
    new Appartement(103, 'Bordeaux', 230000, 62, 3, 4, true, true, BienStatut::DISPONIBLE, $owner2),
    new Maison(104, 'Nantes', 320000, 120, 4, BienStatut::INDISPONIBLE, $owner2),
];

$loyers = [
    101 => 850,
    102 => 730,
    103 => 1100,
    104 => 1600,
];

echo 'Liste complete des biens' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;

foreach ($biens as $bien) {
    $profitability = $bien->calculateProfitability($loyers[$bien->getId()] ?? 0);
    echo $bien->getFullSummary() . PHP_EOL;
    echo 'Rentabilite brute: ' . round($profitability, 2) . ' %' . PHP_EOL;
    echo str_repeat('-', 60) . PHP_EOL;
}

echo PHP_EOL . 'Recherche (union + nullable + DNF)' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;

$testsRecherche = [
    ['label' => 'nullable => tout', 'input' => null],
    ['label' => 'chaine ville', 'input' => 'Nantes'],
    ['label' => 'id numerique', 'input' => 102],
    ['label' => 'chaine vide => tout', 'input' => '   '],
    ['label' => 'objet Stringable+JsonSerializable (DNF)', 'input' => new SearchTerm('Julie')],
];

foreach ($testsRecherche as $test) {
    $resultats = rechercherBiens($biens, $test['input']);
    echo '- ' . $test['label'] . ' -> ' . count($resultats) . ' resultat(s): ';
    $ids = array_map(static fn (BienImmobilier $bien): int => $bien->getId(), $resultats);
    echo implode(', ', $ids) . PHP_EOL;
}

afficherElementRecherche($biens[0], 'Lyon');

echo PHP_EOL . 'Contacts proprietaires (DNF)' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
echo decrireContact($owner1) . PHP_EOL;
echo decrireContact('agence@example.com') . PHP_EOL;

echo PHP_EOL . 'Verification proprietaires rattaches' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
echo $owner1->getFullName() . ' possede ' . count($owner1->getBiens()) . ' bien(s).' . PHP_EOL;
echo $owner2->getFullName() . ' possede ' . count($owner2->getBiens()) . ' bien(s).' . PHP_EOL;

$exportableBiens = array_map(
    static fn (BienImmobilier $bien): array => $bien->toExportArray(),
    $biens
);

$jsonExporter = new JsonExporter();
$csvExporter = new CsvExporter();

echo PHP_EOL . 'Export JSON (avec enum status)' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
echo $jsonExporter->export($exportableBiens) . PHP_EOL;

echo PHP_EOL . 'Export CSV (avec enum status)' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
echo $csvExporter->export($exportableBiens) . PHP_EOL;

echo PHP_EOL . 'Intersection Countable&IteratorAggregate pour export JSON' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
$collection = new ArrayObject($exportableBiens);
echo exporterCollectionJson($collection, $jsonExporter) . PHP_EOL;

echo PHP_EOL . 'Test readonly id' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
echo 'Exemple id immutable: bien #' . $biens[0]->getId() . PHP_EOL;
