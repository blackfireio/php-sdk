<?php

namespace Blackfire\Bridge\PhpUnit;

use PHPUnit\Framework\Constraint\Constraint;
use SebastianBergmann\Comparator\ComparisonFailure;

class TestConstraint11x extends Constraint
{
    use BlackfireTestContraintTrait;

    protected function matches(mixed $other): bool
    {
        return $other->isSuccessful();
    }

    protected function fail(mixed $other, string $description, ?ComparisonFailure $comparisonFailure = null): never
    {
        $this->doFail($other, $description, $comparisonFailure);
    }

    public function toString(): string
    {
        return '';
    }
}
