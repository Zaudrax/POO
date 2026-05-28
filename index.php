<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

$sqlitePath = __DIR__ . '/data/app.sqlite';
$dbSingletonA = DatabaseConnection::getInstance($sqlitePath);
$dbSingletonB = DatabaseConnection::getInstance();
$isSameInstance = $dbSingletonA === $dbSingletonB ? 'oui' : 'non';

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

function exporterCollectionJson(Countable&IteratorAggregate $collection, ExporterInterface $exporter): string
{
    $rows = [];
    foreach ($collection as $item) {
        $rows[] = $item;
    }

    return $exporter->export($rows);
}

/** @param array<int, BienImmobilier> $biens */
function hasBienWithId(array $biens, int $id): bool
{
    foreach ($biens as $bien) {
        if ($bien->getId() === $id) {
            return true;
        }
    }

    return false;
}

$hydrator = new PropertyHydrator([
    new AppartementHandler(),
    new MaisonHandler(),
]);

$sqliteRepository = new SqliteDataRepository($dbSingletonA->getPdo(), $hydrator);

if ($sqliteRepository->isEmpty()) {
    $jsonBootstrapRepository = new JsonDataRepository(__DIR__ . '/data/database.json', $hydrator);
    $bootstrapDataset = $jsonBootstrapRepository->load();
    $sqliteRepository->save($bootstrapDataset['owners'], $bootstrapDataset['biens'], $bootstrapDataset['loyers']);
    echo 'SQLite seed: donnees JSON importees dans data/app.sqlite.' . PHP_EOL;
}

/** @var DataRepositoryInterface $repository */
$repository = $sqliteRepository;

$dataset = $repository->load();

echo 'Singleton DB (SQLite)' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
echo 'Meme instance ? ' . $isSameInstance . PHP_EOL;
echo 'Fichier DB: ' . $sqlitePath . PHP_EOL;
echo 'Donnees metier chargees via SQLite.' . PHP_EOL . PHP_EOL;

$owners = $dataset['owners'];
$biens = $dataset['biens'];
$loyers = $dataset['loyers'];

// Ajout d'un bien test pour le save ------------------------------------------

$demoBienId = 105;

if (!hasBienWithId($biens, $demoBienId) && isset($owners[0])) {
    $biens[] = new Maison(
        $demoBienId,
        'Rennes',
        280000,
        95,
        3,
        BienStatut::DISPONIBLE,
        $owners[0]
    );
    $loyers[$demoBienId] = 1450;

    $repository->save($owners, $biens, $loyers);

    $dataset = $repository->load();
    $owners = $dataset['owners'];
    $biens = $dataset['biens'];
    $loyers = $dataset['loyers'];

    echo 'Demo save: bien #' . $demoBienId . ' ajoute dans data/database.json.' . PHP_EOL;
}

// -----------------------------------------------------------------------------

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

if (isset($biens[0])) {
    afficherElementRecherche($biens[0], 'Lyon');
}

echo PHP_EOL . 'Contacts proprietaires (DNF)' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
if (isset($owners[0])) {
    echo decrireContact($owners[0]) . PHP_EOL;
}
echo decrireContact('agence@example.com') . PHP_EOL;

echo PHP_EOL . 'Verification proprietaires rattaches' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
foreach ($owners as $owner) {
    echo $owner->getFullName() . ' possede ' . count($owner->getBiens()) . ' bien(s).' . PHP_EOL;
}

$exportableBiens = array_map(
    static fn (BienImmobilier $bien): array => $bien->toExportArray(),
    $biens
);

$jsonExporter = new JsonExporter();
$csvExporter = new CsvExporter();
$exportContext = new ExportContext($jsonExporter);

echo PHP_EOL . 'Strategy Export - JSON (avec enum status)' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
echo $exportContext->export($exportableBiens) . PHP_EOL;

echo PHP_EOL . 'Strategy Export - CSV (avec enum status)' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
$exportContext->setStrategy($csvExporter);
echo $exportContext->export($exportableBiens) . PHP_EOL;

echo PHP_EOL . 'Intersection Countable&IteratorAggregate pour export JSON' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
$collection = new ArrayObject($exportableBiens);
echo exporterCollectionJson($collection, $jsonExporter) . PHP_EOL;

echo PHP_EOL . 'Test readonly id' . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
echo 'Exemple id immutable: bien #' . $biens[0]->getId() . PHP_EOL;
