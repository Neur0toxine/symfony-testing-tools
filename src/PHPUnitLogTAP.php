<?php
/**
 * PHP version 7.1
 *
 * PHPUnitLogTAP.php
 *
 * @author  Sebastian Bergmann <sebastian@phpunit.de>
 * @license https://github.com/sebastianbergmann/phpunit/blob/53e84c64ad/LICENSE
 * @link https://github.com/sebastianbergmann/phpunit/issues/2683#issuecomment-302125951
 */

namespace Intaro\SymfonyTestingTools;

/*
    * This file is part of PHPUnit.
    *
    * (c) Sebastian Bergmann <sebastian@phpunit.de>
    *
    * For the full copyright and license information, please view the LICENSE
    * file that was distributed with this source code.
    */

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestFailure;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\Framework\Test;
use PHPUnit\Runner\TestHook;
use PHPUnit\Util\Printer;
use Symfony\Component\Yaml\Yaml;

if (!class_exists('PHPUnit_Util_Log_TAP')) {
    /**
     * A TestListener that generates a logfile of the
     * test execution using the Test Anything Protocol (TAP).
     *
     * @since Class available since Release 3.0.0
     */
    class PHPUnit_Util_Log_TAP extends Printer implements TestHook
    {
        /**
         * @var int
         */
        protected $testNumber = 0;

        /**
         * @var int
         */
        protected $testSuiteLevel = 0;

        /**
         * @var bool
         */
        protected $testSuccessful = true;

        /**
         * Constructor.
         *
         * @param mixed $out
         *
         * @throws \Exception
         *
         * @since Method available since Release 3.3.4
         */
        public function __construct($out = null)
        {
            parent::__construct($out);
            $this->write("TAP version 13\n");
        }

        /**
         * An error occurred.
         *
         * @param Test $test
         * @param Exception              $e
         * @param float                  $time
         */
        public function addError(Test $test, Exception $e, $time)
        {
            $this->writeNotOk($test, 'Error');
        }

        /**
         * A warning occurred.
         *
         * @param Test    $test
         * @param \PHPUnit\Framework\Warning $e
         * @param float                     $time
         *
         * @since Method available since Release 5.1.0
         */
        public function addWarning(Test $test, Warning $e, $time)
        {
            $this->writeNotOk($test, 'Warning');
        }

        /**
         * A failure occurred.
         *
         * @param Test                 $test
         * @param AssertionFailedError $e
         * @param float                                  $time
         */
        public function addFailure(Test $test, AssertionFailedError $e, $time)
        {
            $this->writeNotOk($test, 'Failure');

            $message = explode(
                "\n",
                TestFailure::exceptionToString($e)
            );

            $diagnostic = [
                'message' => $message[0],
                'severity' => 'fail'
            ];

            if ($e instanceof ExpectationFailedException) {
                $cf = $e->getComparisonFailure();

                if ($cf !== null) {
                    $diagnostic['data'] = [
                        'got' => $cf->getActual(),
                        'expected' => $cf->getExpected()
                    ];
                }
            }

            $this->write(
                sprintf(
                    "  ---\n%s  ...\n",
                    Yaml::dump($diagnostic, 2, 2)
                )
            );
        }

        /**
         * Incomplete test.
         *
         * @param Test $test
         * @param Exception              $e
         * @param float                  $time
         */
        public function addIncompleteTest(Test $test, Exception $e, $time)
        {
            $this->writeNotOk($test, '', 'TODO Incomplete Test');
        }

        /**
         * Risky test.
         *
         * @param Test $test
         * @param Exception              $e
         * @param float                  $time
         *
         * @since Method available since Release 4.0.0
         */
        public function addRiskyTest(Test $test, Exception $e, $time)
        {
            $this->write(
                sprintf(
                    "ok %d - # RISKY%s\n",
                    $this->testNumber,
                    $e->getMessage() != '' ? ' ' . $e->getMessage() : ''
                )
            );

            $this->testSuccessful = false;
        }

        /**
         * Skipped test.
         *
         * @param Test $test
         * @param Exception              $e
         * @param float                  $time
         *
         * @since Method available since Release 3.0.0
         */
        public function addSkippedTest(Test $test, Exception $e, $time)
        {
            $this->write(
                sprintf(
                    "ok %d - # SKIP%s\n",
                    $this->testNumber,
                    $e->getMessage() != '' ? ' ' . $e->getMessage() : ''
                )
            );

            $this->testSuccessful = false;
        }

        /**
         * A testsuite started.
         *
         * @param TestSuite $suite
         */
        public function startTestSuite(TestSuite $suite)
        {
            $this->testSuiteLevel++;
        }

        /**
         * A testsuite ended.
         *
         * @param TestSuite $suite
         */
        public function endTestSuite(TestSuite $suite)
        {
            $this->testSuiteLevel--;

            if ($this->testSuiteLevel == 0) {
                $this->write(sprintf("1..%d\n", $this->testNumber));
            }
        }

        /**
         * A test started.
         *
         * @param Test $test
         */
        public function startTest(Test $test)
        {
            $this->testNumber++;
            $this->testSuccessful = true;
        }

        /**
         * A test ended.
         *
         * @param Test $test
         * @param float                  $time
         */
        public function endTest(Test $test, $time)
        {
            if ($this->testSuccessful === true) {
                $this->write(
                    sprintf(
                        "ok %d - %s\n",
                        $this->testNumber,
                        \PHPUnit\Util\Test::describe($test)
                    )
                );
            }

            $this->writeDiagnostics($test);
        }

        /**
         * @param Test                   $test
         * @param string                 $prefix
         * @param string                 $directive
         */
        protected function writeNotOk(Test $test, $prefix = '', $directive = '')
        {
            $this->write(
                sprintf(
                    "not ok %d - %s%s%s\n",
                    $this->testNumber,
                    $prefix != '' ? $prefix . ': ' : '',
                    \PHPUnit\Util\Test::describe($test),
                    $directive != '' ? ' # ' . $directive : ''
                )
            );

            $this->testSuccessful = false;
        }

        /**
         * @param Test $test
         */
        private function writeDiagnostics(Test $test)
        {
            if (!$test instanceof TestCase) {
                return;
            }

            if (!$test->hasOutput()) {
                return;
            }

            foreach (explode("\n", trim($test->getActualOutput())) as $line) {
                $this->write(
                    sprintf(
                        "# %s\n",
                        $line
                    )
                );
            }
        }
    }
}
