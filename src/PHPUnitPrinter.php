<?php

namespace Intaro\SymfonyTestingTools;

use PHPUnit\Framework\TestFailure;
use PHPUnit\TextUI\ResultPrinter;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\Test;
use Symfony\Component\Yaml\Yaml;

if (WebTestCase::isTestsDebug()) {
    class PHPUnitPrinter extends ResultPrinter
    {
    }
} else {
    class PHPUnitPrinter extends PHPUnit_Util_Log_TAP
    {
        /**
         * An error occurred.
         *
         * @param Test $test
         * @param Exception              $e
         * @param float                   $time
         */
        public function addError(Test $test, Exception $e, $time)
        {
            $this->writeNotOk($test, 'Error');

            $message = explode("\n", TestFailure::exceptionToString($e));

            $diagnostic = array(
                'message'  => $message[0],
                'severity' => 'fail'
            );

            $this->write(
                sprintf(
                    "  ---\n%s  ...\n",
                    Yaml::dump($diagnostic, 2, 2)
                )
            );
        }
    }
}
