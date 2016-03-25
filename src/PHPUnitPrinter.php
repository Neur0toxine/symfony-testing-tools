<?php

namespace Intaro\SymfonyTestingTools;

if (WebTestCase::isTestsDebug()) {
    class PHPUnitPrinter extends \PHPUnit_TextUI_ResultPrinter
    {
    }
} else {
    class PHPUnitPrinter extends \PHPUnit_Util_Log_TAP
    {
        /**
         * An error occurred.
         *
         * @param \PHPUnit_Framework_Test $test
         * @param \Exception              $e
         * @param float                   $time
         */
        public function addError(\PHPUnit_Framework_Test $test, \Exception $e, $time)
        {
            $this->writeNotOk($test, 'Error');

            $message = explode("\n", \PHPUnit_Framework_TestFailure::exceptionToString($e));

            $diagnostic = array(
                'message'  => $message[0],
                'severity' => 'fail'
            );

            $yaml = new \Symfony\Component\Yaml\Dumper;

            $this->write(
                sprintf(
                    "  ---\n%s  ...\n",
                    $yaml->dump($diagnostic, 2, 2)
                )
            );
        }
    }
}
