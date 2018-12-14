<?php

namespace Blackfire\Bridge\PhpUnit;

use PHPUnit\Framework\Constraint\Constraint;
use SebastianBergmann\Comparator\ComparisonFailure;

class TestConstraint71 extends Constraint
{
    use BlackfireTestContraintTrait;

    public function matches($profile): bool
    {
        return $profile->isSuccessful();
    }

    protected function fail($profile, $description, ComparisonFailure $comparisonFailure = null): void
    {
        $this->doFail($profile, $description, $comparisonFailure);
    }

    public function toString(): string
    {
        return '';
    }
}
