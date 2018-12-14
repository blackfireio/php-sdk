<?php

namespace Blackfire\Bridge\PhpUnit;

use PHPUnit\Framework\Constraint\Constraint;
use SebastianBergmann\Comparator\ComparisonFailure;

class TestConstraint5x extends Constraint
{
    use BlackfireTestContraintTrait;

    public function matches($profile)
    {
        return $profile->isSuccessful();
    }

    protected function fail($profile, $description, ComparisonFailure $comparisonFailure = null)
    {
        $this->doFail($profile, $description, $comparisonFailure);
    }

    public function toString()
    {
        return '';
    }
}
