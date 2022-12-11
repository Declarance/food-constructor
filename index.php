<?php

declare(strict_types=1);

include 'db.php';

$codesString = $argv[1] ?? null;

if ($codesString === null) {
    print "Ошибка. Пустой аргумент!";
    die();
}

$uniqueCodesArray = array_unique(str_split($codesString));

$possibleCodesArray = getPossibleCodes($dbh);

$codesCountArray = [];

foreach ($uniqueCodesArray as $code) {
    if (!in_array($code, $possibleCodesArray)) {
        print "Ошибка. Код: '$code' не существует в базе данных!";
        die();
    }

    $codesCountArray[$code] = substr_count($codesString, $code);
}

$possibleCodesCountArray = getPossibleCodesCount($dbh);

foreach ($codesCountArray as $code => $count) {
    if ($possibleCodesCountArray[$code] < $count) {
        print "Ошибка. Максимальное количество кода '$code' - $possibleCodesCountArray[$code], введено $count!";
        die();
    }
}

$codeVariations = [];

foreach ($uniqueCodesArray as $code) {
    $codeVariations[$code] = getVariationsForCode($dbh, $code);
}

$codesVariationsArray = [];

foreach ($uniqueCodesArray as $code) {
    $needle = $codesCountArray[$code];
    $variations = $codeVariations[$code];
    $combinations = generateCombinations($variations, $needle);
    $codesVariationsArray[$code] = $combinations;
}

/**
 * pull - Массив возможных значений, которые нужно скомбинировать
 * needle - количество элементов из пула которые нужно комбинировать
 */
function generateCombinations(array $pull, int $needle): array
{
    $maxBinaryValue = 2 ** count($pull) - 1;
    $binVariations = [];

    for ($binaryValue = 1; $binaryValue <= $maxBinaryValue; $binaryValue++) {
        $bin = decbin($binaryValue);
        $binArr = str_split($bin);

        if (count($binArr) < count($pull)) {
            $diff = count($pull) - count($binArr);

            for ($i = 0; $i < $diff; $i++) {
                $bin = '0' . $bin;
            }
        }

        $binArr = str_split($bin);
        $bitCount = 0;

        foreach ($binArr as $binVal) {
            if ($binVal === '1') {
                $bitCount++;
            }
        }

        if ($bitCount == $needle) {
            $binVariations[] = $bin;
        }
    }

    $resultVariations = [];

    foreach ($binVariations as $binVariation) {
        $decimalVariation = [];
        $binVariationArray = str_split($binVariation);

        foreach ($binVariationArray as $position => $bit) {
            if ($bit) {
                $decimalVariation[] = $pull[$position];
            }
        }

        $resultVariations[] = $decimalVariation;
    }

    return $resultVariations;
}

$finalVariations = combine($codesVariationsArray);

function combine(array $input): array
{
    $result = [];

    foreach ($input as $key => $values) {
        if (empty($values)) {
            continue;
        }

        if (empty($result)) {
            foreach ($values as $value) {
                $result[] = [$key => $value];
            }
        } else {
            $append = [];

            foreach ($result as &$product) {
                $product[$key] = array_shift($values);

                $copy = $product;

                foreach ($values as $item) {
                    $copy[$key] = $item;
                    $append[] = $copy;
                }

                array_unshift($values, $product[$key]);
            }

            $result = array_merge($result, $append);
        }
    }

    return $result;
}

$k = [];

foreach ($finalVariations as $index => $finalVariation) {
    $va = [];

    foreach ($finalVariation as $id) {
        $va = array_merge($va, $id);
    }

    asort($va);

    $k[$index] = $va;
}

$ingredients = getAllIngredients($dbh);

$l = [];

foreach ($k as $key => $value) {
    $totalPrice = 0;
    $products = [];

    foreach ($value as $id) {
        $products[] = ['type' =>  $ingredients[$id]['type'], 'value' => $ingredients[$id]['value']];
        $totalPrice += $ingredients[$id]['price'];
    }

    $l[$key]['products'] = $products;
    $l[$key]['price'] = $totalPrice;
}

print json_encode($l, JSON_UNESCAPED_UNICODE);
