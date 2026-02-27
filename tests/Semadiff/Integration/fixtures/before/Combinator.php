<?php

declare(strict_types=1);

namespace DT\Component\OFL\Calculator\Utils;

class Combinator
{
    public function __construct(private readonly int $maximumItemsToUseSimpleTraverse = 10)
    {
    }

    public function combineItems(array $items, int $targetValue): ItemsCombinations
    {
        $itemsCombinations = new ItemsCombinations();
        return $itemsCombinations->getMostRelevantCombinations();
    }

    /**
     * @param Item[] $items
     */
    protected function checkCombinationsBySummarizeSmallItems(
        ItemsCombinations $itemsCombinations,
        array &$items,
        int $targetValue,
    ): void {
        $count = count($items);
        foreach ($items as $index => $item) {
            $totalValue = $item->getValue();
        }
    }

    /**
     * @param Item[] $items
     */
    protected function checkAndAddAllCombinationsOfTwoItems(
        ItemsCombinations $itemsCombinations,
        array &$items,
        int $targetValue,
    ): void {
        $this->addAllCombinationsInRange($itemsCombinations, $items, 2, $targetValue);
    }

    /**
     * @param Item[] $items
     */
    protected function checkAndAddCombinationsForExistingCombinationsWithNegativeDelta(
        ItemsCombinations $itemsCombinations,
        array &$items,
    ): void {
        $combinationsWithNegativeDelta = $itemsCombinations->getCombinationsWithNegativeDelta();
    }

    /**
     * @param Item[] $items
     */
    protected function addAllPossibleCombinations(
        ItemsCombinations $combinations,
        array &$items,
        int $targetValue,
    ): void {
        $this->addAllCombinationsInRange($combinations, $items, count($items), $targetValue);
    }

    private function addAllCombinationsInRange(
        ItemsCombinations $combinations,
        array &$items,
        int $maxTupleLength,
        int $targetValue,
    ): void {
        $lastLevelTuples = [];
    }
}
