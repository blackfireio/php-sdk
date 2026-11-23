<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace {
    final class BlackfireProbe
    {
        /**
         * Returns a global singleton and enables it by default.
         *
         * @return self
         */
        public static function getMainInstance()
        {
        }

        /**
         * Tells whether any probes are currently profiling or not.
         *
         * @return bool
         */
        public static function isEnabled()
        {
        }

        /**
         * Instantiate a probe object.
         */
        public function __construct($query, $envId = null, $envToken = null, $agentSocket = null)
        {
        }

        /**
         * Tells if the probe is cryptographically verified, i.e. if the signature in $query is valid.
         *
         * @return bool
         */
        public function isVerified()
        {
        }

        /**
         * Gets the response message/status/line.
         *
         * This lines gives details about the status of the probe. That can be:
         * - an error: `Blackfire-Error: $errNumber $urlEncodedErrorMessage`
         * - or not: `Blackfire-Response: $rfc1738EncodedMessage`
         *
         * @return string The response line
         */
        public function getResponseLine()
        {
        }

        /**
         * Enables profiling instrumentation and data aggregation.
         *
         * One and only one probe can be enabled at the same time.
         *
         * @return bool false if enabling failed
         * @see getResponseLine() for error/status reporting
         *
         */
        public function enable()
        {
        }

        /**
         * Discard collected data and disables instrumentation.
         *
         * Does not close the profile payload, allowing to re-enable the probe and aggregate data in the same profile.
         *
         * @return bool false if the probe was not enabled
         */
        public function discard()
        {
        }

        /**
         * Disables profiling instrumentation and data aggregation.
         *
         * Does not close the profile payload, allowing to re-enable the probe and aggregate data in the same profile.
         * As a side-effect, flushes the collected profile to the output.
         *
         * @return bool false if the probe was not enabled
         */
        public function disable()
        {
        }

        /**
         * Disables and closes profiling instrumentation and data aggregation.
         *
         * Closing means that a later enable() will create a new profile on the output.
         * As a side-effect, flushes the collected profile to the output.
         *
         * @return bool false if the probe was not enabled
         */
        public function close()
        {
        }

        /**
         * Adds a marker for the Timeline View.
         * Production safe. Operates a no-op if no profile is requested.
         *
         * @param string $markerName
         */
        public static function addMarker($label = '')
        {
        }

        /**
         * Creates a sub-query string to create a new profile linked to the current one.
         * This query must be set in the X-Blackire-Query HTTP header or in the BLACKFIRE_QUERY environment variable.
         *
         * @return string|null the sub-query or null if profiling is disabled
         */
        public function createSubProfileQuery()
        {
        }

        /**
         * Set the transaction name.
         */
        public static function setTransactionName($transactionName)
        {
        }

        public static function startTransaction($transactionName = null)
        {
        }

        /**
         * Stops the current transaction.
         *
         * @param int|null $exitCode The exit code of the transaction, a non-zero value reports it as failed.
         *                           Like a process exit code, only its low byte is kept: 300 becomes 44,
         *                           256 becomes 0 and -1 becomes 255.
         *                           Defaults to the exit status of the process.
         */
        public static function stopTransaction($exitCode = null)
        {
        }

        public static function ignoreTransaction()
        {
        }

        /**
         * Returns the browser probe snippet.
         *
         * @param bool        $withTags Whether to return a <script> tag or the JavaScript loader code only
         * @param string|null $nonce    The CSP nonce to add to the <script> tag. With null, it is read from the
         *                              Content-Security-Policy header already set by PHP. An empty string adds none.
         *                              Ignored without tags.
         *
         * @throws \ValueError When $nonce contains a null byte (PHP 8+)
         */
        public static function getBrowserProbe($withTags = true, $nonce = null)
        {
        }
    }
}
