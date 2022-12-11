<?php

declare(strict_types=1);

try {
    $dbh = new PDO('mysql:host=localhost;dbname=test_task', 'root', 'root');
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage();
    die();
}

function getPossibleCodes($dbh): array
{
    $sth = $dbh->prepare('SELECT code FROM ingredient_type');
    $sth->execute();

    return $sth->fetchAll(PDO::FETCH_COLUMN, 0);
}

function getPossibleCodesCount($dbh): array
{
    $sth = $dbh->prepare('SELECT it.code, COUNT(it.id) AS count FROM ingredient INNER JOIN ingredient_type it on ingredient.type_id = it.id GROUP BY type_id');
    $sth->execute();

    return $sth->fetchAll(PDO::FETCH_KEY_PAIR);
}

function getVariationsForCode($dbh, string $code): array
{
    $sth = $dbh->prepare('SELECT ingredient.id FROM ingredient INNER JOIN ingredient_type it on ingredient.type_id = it.id WHERE code=?');
    $sth->execute([$code]);
    return $sth->fetchAll(PDO::FETCH_COLUMN, 0);
}

function getAllIngredients($dbh): array
{
    $sth = $dbh->prepare('SELECT ingredient.id, it.title type, ingredient.title value, ingredient.price  FROM ingredient INNER JOIN ingredient_type it on ingredient.type_id = it.id');
    $sth->execute();

    $result = [];

    foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $result[$item['id']] = $item;
    }

    return $result;
}