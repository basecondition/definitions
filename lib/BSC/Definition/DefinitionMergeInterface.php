<?php

namespace BSC\Definition;


interface DefinitionMergeInterface
{
    public function mergeGroup( array $group): array;

    public function mergeDefinition(array $definitions): array;
}