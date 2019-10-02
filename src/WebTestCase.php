<?php

namespace Intaro\SymfonyTestingTools;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;

abstract class WebTestCase extends BaseWebTestCase
{
    /**
     * @var Client
     */
    protected static $client;

    /** @var bool|null */
    protected static $debug;

    public static function isTestsDebug()
    {
        if (null === static::$debug) {
            $debug = getenv('SYMFONY_TESTS_DEBUG');

            if (false === $debug) {
                static::$debug = true;
            } else {
                static::$debug = filter_var($debug, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return static::$debug;
    }

    /**
     * Creates if needed and returns client instance
     *
     * @param bool  $reinitialize
     * @param array $options
     *
     * @return Client
     */
    protected static function getClient($reinitialize = false, array $options = [])
    {
        if (!static::$client || $reinitialize) {
            static::$client = static::createClient($options);
        }

        // core is loaded (for tests without calling of getClient(true))
        static::$client->getKernel()->boot();

        return static::$client;
    }

    /**
     * Append a certain fixture
     *
     * @param  AbstractFixture $fixture
     * @param  array           $options (default: [])
     * @return void
     */
    protected static function appendFixture(AbstractFixture $fixture, array $options = [])
    {
        $em = static::getClient(isset($options['new_client']) ? $options['new_client'] : false)->getContainer()
            ->get('doctrine')
            ->getManager();

        $loader = new Loader();
        $loader->addFixture($fixture);

        if (isset($options['purge']) && $options['purge']) {
            $purger   = new ORMPurger($em);
            $executor = new ORMExecutor($em, $purger);
            $executor->execute($loader->getFixtures(), false);
        } else {
            $executor = new ORMExecutor($em);
            $executor->execute($loader->getFixtures(), true);
        }
    }

    protected static function purge(array $options = [])
    {
        $em = static::getClient(isset($options['new_client']) ? $options['new_client'] : false)->getContainer()
            ->get('doctrine')
            ->getManager();

        $purger = new ORMPurger($em);
        $purger->purge();
    }

    /**
     * @param bool $newClient
     *
     * @return ContainerInterface|MockableContainer|null
     */
    protected static function getContainer($newClient = false)
    {
        return self::getClient($newClient)->getContainer();
    }

    /**
     * @param bool $newClient
     *
     * @return EntityManagerInterface
     * @throws \Exception
     */
    protected static function getEntityManager($newClient = false)
    {
        return self::getContainer($newClient)->get('doctrine')->getManager();
    }

    /**
     * @param Response $response
     * @param string   $message
     * @param string   $type
     */
    public function assertResponseOk($response = null, $message = null, $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isOk', $message, $type);
    }

    /**
     * @param Response $response
     * @param string   $message
     * @param string   $type
     */
    public function assertResponseRedirect($response = null, $message = null, $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isRedirect', $message, $type);
    }

    /**
     * @param Response $response
     * @param string   $message
     * @param string   $type
     */
    public function assertResponseNotFound($response = null, $message = null, $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isNotFound', $message, $type);
    }

    /**
     * @param Response $response
     * @param string   $message
     * @param string   $type
     */
    public function assertResponseForbidden($response = null, $message = null, $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isForbidden', $message, $type);
    }

    /**
     * @param Response $response
     * @param int      $expectedCode
     * @param string   $message
     * @param string   $type
     */
    public function assertResponseCode($expectedCode, $response = null, $message = null, $type = 'text/html')
    {
        if (!is_int($expectedCode)) {
            throw new \InvalidArgumentException('expectedCode must be integer');
        }

        $this->failOnResponseStatusCheck($response, $expectedCode, $message, $type);
    }

    /**
     * @param Response $response
     * @param string   $type
     *
     * @return string
     */
    public function guessErrorMessageFromResponse($response, $type = 'text/html')
    {
        try {
            $crawler = new Crawler();
            $crawler->addContent($response->getContent(), $type);
            if (!count($crawler->filter('title'))) {
                $add = '';
                $content = $response->getContent();

                if ('application/json' === $response->headers->get('Content-Type')) {
                    $data = json_decode($content);

                    if ($data) {
                        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        $add = ' FORMATTED';
                    }
                }

                $title = '[' . $response->getStatusCode() . ']' . $add .' - ' . $content;
            } else {
                $title = $crawler->filter('title')->text();
            }
        } catch (\Exception $e) {
            $title = $e->getMessage();
        }

        return trim($title);
    }

    private function failOnResponseStatusCheck(
        Response $response = null,
        $func = null,
        $message = null,
        $type = 'text/html'
    ) {
        if (null === $func) {
            $func = 'isOk';
        }

        if (null === $response && self::$client) {
            $response = self::$client->getResponse();
        }

        try {
            if (is_int($func)) {
                $this->assertEquals($func, $response->getStatusCode());
            } else {
                $this->assertTrue($response->{$func}());
            }

            return;
        } catch (\Exception $e) {
            // nothing to do
        }

        $err = $this->guessErrorMessageFromResponse($response, $type);

        if ($message) {
            $message = rtrim($message, '.') . ". ";
        }

        if (is_int($func)) {
            $template = "Failed asserting Response status code %s equals %s.";
        } else {
            $template = "Failed asserting that Response[%s] %s.";
            $func = preg_replace('#([a-z])([A-Z])#', '$1 $2', $func);
        }

        $message .= sprintf($template, $response->getStatusCode(), $func, $err);

        $max_length = 100;

        if (mb_strlen($err, 'utf-8') < $max_length) {
            $message .= " " . $this->makeErrorOneLine($err);
        } else {
            $message .= " " . $this->makeErrorOneLine(mb_substr($err, 0, $max_length, 'utf-8') . '...');
            $message .= "\n\n" . $err;
        }

        $this->fail($message);
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object     Instantiated object that we will run method on.
     * @param string  $methodName Method name to call
     * @param array   $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     * @throws \ReflectionException
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    private function makeErrorOneLine($text)
    {
        return preg_replace('#[\n\r]+#', ' ', $text);
    }
}
