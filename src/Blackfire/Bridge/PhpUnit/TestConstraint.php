<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\PhpUnit;

use PHPUnit\Framework\Constraint\Constraint;
use SebastianBergmann\Comparator\ComparisonFailure;

class TestConstraint extends Constraint
{
    public function matches($profile)
    {
        return $profile->isSuccessful();
    }

    public function toString()
    {
        return '';
    }

    protected function fail($profile, $description, ComparisonFailure $comparisonFailure = null)
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

        throw new \PHPUnit_Framework_ExpectationFailedException($failureDescription, $comparisonFailure);
    }
}
