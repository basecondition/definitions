<?php
/**
 * @package definitions
 * @author Joachim Doerr
 * @copyright (C) hello@basecondition.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BSC\Definition;


interface DefinitionMergeInterface
{
    public function mergeGroup( array $group): array;

    public function mergeDefinition(array $definitions): array;
}