<?php

declare(strict_types=1);

/**
 * Repairs Localizator3 tables after Phinx double-prefix bug (#9).
 *
 * Must run before Phinx Manager::migrate() so the migration log is consistent
 * and already-applied versions are not executed again.
 */
function localizator3RepairPhinxTablePrefixes(modX $modx): void
{
    $modxPrefix = (string)$modx->getOption('table_prefix', null, '');
    if ($modxPrefix === '') {
        return;
    }

    $dataTables = [
        'localizator3_languages',
        'localizator3_content',
        'localizator3_tmplvar_contentvalues',
        'localizator3_option',
        'localizator3_product_option',
    ];

    foreach ($dataTables as $baseTable) {
        localizator3RepairDataTable($modx, $modxPrefix, $baseTable);
    }

    localizator3RepairMigrationTable($modx, $modxPrefix);
}

function localizator3RepairDataTable(modX $modx, string $modxPrefix, string $baseTable): void
{
    $correctTable = $modxPrefix . $baseTable;
    $doubleTable = $modxPrefix . $modxPrefix . $baseTable;

    $hasCorrect = localizator3TableExists($modx, $correctTable);
    $hasDouble = localizator3TableExists($modx, $doubleTable);

    if (!$hasDouble) {
        return;
    }

    if (!$hasCorrect) {
        localizator3ExecuteDdl($modx, sprintf(
            'RENAME TABLE `%s` TO `%s`',
            localizator3QuoteIdentifier($doubleTable),
            localizator3QuoteIdentifier($correctTable),
        ));

        return;
    }

    $correctEmpty = localizator3TableIsEmpty($modx, $correctTable);
    $doubleEmpty = localizator3TableIsEmpty($modx, $doubleTable);

    if ($correctEmpty) {
        localizator3ExecuteDdl($modx, sprintf('DROP TABLE `%s`', localizator3QuoteIdentifier($correctTable)));
        localizator3ExecuteDdl($modx, sprintf(
            'RENAME TABLE `%s` TO `%s`',
            localizator3QuoteIdentifier($doubleTable),
            localizator3QuoteIdentifier($correctTable),
        ));

        return;
    }

    if ($doubleEmpty) {
        localizator3ExecuteDdl($modx, sprintf('DROP TABLE `%s`', localizator3QuoteIdentifier($doubleTable)));

        return;
    }

    throw new RuntimeException(sprintf(
        'Localizator3 prefix repair conflict for "%s": both "%s" and "%s" contain data. Merge manually (#9).',
        $baseTable,
        $doubleTable,
        $correctTable,
    ));
}

function localizator3RepairMigrationTable(modX $modx, string $modxPrefix): void
{
    $unprefixed = 'localizator3_migrations';
    $correct = $modxPrefix . $unprefixed;

    if ($unprefixed === $correct) {
        return;
    }

    $hasCorrect = localizator3TableExists($modx, $correct);
    $hasUnprefixed = localizator3TableExists($modx, $unprefixed);

    if (!$hasUnprefixed) {
        return;
    }

    if (!$hasCorrect) {
        localizator3ExecuteDdl($modx, sprintf(
            'RENAME TABLE `%s` TO `%s`',
            localizator3QuoteIdentifier($unprefixed),
            localizator3QuoteIdentifier($correct),
        ));

        return;
    }

    $correctEmpty = localizator3TableIsEmpty($modx, $correct);
    $unprefixedEmpty = localizator3TableIsEmpty($modx, $unprefixed);

    if ($correctEmpty && !$unprefixedEmpty) {
        localizator3ExecuteDdl($modx, sprintf('DROP TABLE `%s`', localizator3QuoteIdentifier($correct)));
        localizator3ExecuteDdl($modx, sprintf(
            'RENAME TABLE `%s` TO `%s`',
            localizator3QuoteIdentifier($unprefixed),
            localizator3QuoteIdentifier($correct),
        ));

        return;
    }

    if ($unprefixedEmpty) {
        localizator3ExecuteDdl($modx, sprintf('DROP TABLE `%s`', localizator3QuoteIdentifier($unprefixed)));
    }
}

function localizator3TableExists(modX $modx, string $tableName): bool
{
    $quoted = $modx->quote($tableName);
    $sql = "SELECT 1 AS found FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = {$quoted} LIMIT 1";
    $stmt = $modx->prepare($sql);

    return $stmt && $stmt->execute() && (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

function localizator3TableIsEmpty(modX $modx, string $tableName): bool
{
    $sql = sprintf('SELECT COUNT(*) AS row_count FROM `%s`', localizator3QuoteIdentifier($tableName));
    $stmt = $modx->prepare($sql);
    if (!$stmt || !$stmt->execute()) {
        return true;
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (int)($row['row_count'] ?? 0) === 0;
}

function localizator3ExecuteDdl(modX $modx, string $sql): void
{
    $modx->log(modX::LOG_LEVEL_INFO, '[Localizator3] Prefix repair: ' . $sql);
    $modx->exec($sql);
}

function localizator3QuoteIdentifier(string $identifier): string
{
    return str_replace('`', '``', $identifier);
}
