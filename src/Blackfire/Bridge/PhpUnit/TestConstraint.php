<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\PhpUnit;

use SebastianBergmann\Comparator\ComparisonFailure;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;

if (!class_exists(Constraint::class) && class_exists(\PHPUnit_Framework_Constraint::class)) {
    class_alias(\PHPUnit_Framework_Constraint::class, Constraint::class);
}

if (!class_exists(ExpectationFailedException::class) && class_exists(\PHPUnit_Framework_ExpectationFailedException::class)) {
    class_alias(\PHPUnit_Framework_ExpectationFailedException::class, ExpectationFailedException::class);
}

trait BlackfireTestContraintTrait
{
    protected function doFail($profile, $description, ComparisonFailure $comparisonFailure = null)
    {
        $failureDescription = sprintf('An error occurred when profiling the test. More information at %s', $profile->getUrl().'?settings%5BtabPane%5D=assertions');

        if (!$profile->isErrored()) {
            $tests = $profile->getTests();

            $failures = 0;
            $details = '';
            foreach ($tests as $test) {
                if ($test->isSuccessful()) {
                    continue;
                }

                ++$failures;
                $details .= sprintf("    %s: %s\n", $test->getState(), $test->getName());
                foreach ($test->getFailures() as $assertion) {
                    $details .= sprintf("      - %s\n", $assertion);
                }
            }
            $details .= sprintf("\nMore information at %s.", $profile->getUrl().'?settings%5BtabPane%5D=assertions');

            $failureDescription = "Failed asserting that Blackfire tests pass.\n";
            $failureDescription .= sprintf("%d tests failures out of %d.\n\n", $failures, count($tests));
            $failureDescription .= $details;
        }

        // not used
        if (!empty($description)) {
            $failureDescription = $description."\n".$failureDescription;
        }

        throw new ExpectationFailedException($failureDescription, $comparisonFailure);
    }
}

if (PHP_VERSION_ID > 70100) {
    class TestConstraint extends Constraint
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
} else {
    class TestConstraint extends Constraint
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
}
