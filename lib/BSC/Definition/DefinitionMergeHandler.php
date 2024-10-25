<?php

namespace BSC\Definition;


class DefinitionMergeHandler implements DefinitionMergeInterface
{
    public function mergeGroup(array $group): array
    {
        $mergedGroup = [];

        foreach ($group as $name => $items) {
            $mergedGroup[$name] = $this->recursiveMerge($items);
        }
        return $mergedGroup;
    }

    public function mergeDefinition(array $definitions): array
    {
        $mergedDefinitions = [];

        foreach ($definitions as $key => $group) {
            foreach ($group as $name => $items) {
                $mergedDefinitions[$key][$name] = $this->recursiveMerge($items);
            }
        }

        return $mergedDefinitions;
    }

    protected function recursiveMerge(array $array)
    {
        $result = reset($array); // Initialisiere mit dem ersten Element des Arrays

        foreach (array_slice($array, 1) as $item) {
            if (!is_array($item)) $item = [];
            $result = $this->mergeArrays($result, $item);
        }

        return $result;
    }

    protected function mergeArrays(array $master, array $toMerge)
    {
        foreach ($toMerge as $key => $value) {
            if ($key === 'items' && isset($master['clearItems']) && $master['clearItems']) unset($master['items']);
            if (is_array($value) && array_key_exists($key, $master) && is_array($master[$key])) {
                $master[$key] = $this->mergeArrays($master[$key], $value);
            } else {
                if (array_key_exists($key, $master)) {
                    if (is_array($master[$key]) && is_array($value)) {
                        $master[$key] = $this->mergeArrays($master[$key], $value);
                    } else {
                        $master[$key] = $value;
                    }
                } else {
                    $master[$key] = $value;
                }
            }
        }

        return $master;
    }
}