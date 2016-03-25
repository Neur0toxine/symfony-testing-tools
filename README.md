# Symfony testing tools

The library contains improved classes of WebTestCase and Container for convenient testing.

## Features

### The Client local caching

Web Client is caching in WebTestCase. If you want to get a client you should use:
```php
$client = static::getClient();
```

If you want to get a new client you should use:
```php
$client = static::getClient(true);
```

Full example:
```php
<?php
namespace Foo\BarBundle\Tests\Controller;

use Intaro\SymfonyTestingTools\WebTestCase;

class SomeControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::getClient(true);
        //...
    }
}
```

### Shortcuts for EntityManager and Container

You can simply get EntityManager or Container in the current context:

```php
<?php
namespace Foo\BarBundle\Tests\Controller;

use Intaro\SymfonyTestingTools\WebTestCase;

class SomeControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::getClient(true);
        
        $em = static::getEntityManager();
        $service = static::getContainer()->get('some_service');
        //...
    }
}
```

### Checking a response HTTP code

You can check response result with the following methods:

```php
<?php

namespace Foo\BarBundle\Tests\Controller;

use Intaro\SymfonyTestingTools\WebTestCase;

class SomeControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::getClient();

        $client->request('GET', '/foo/bar/index');
        $this->assertResponseOk($client->getResponse(), 'Page opens');
        $this->assertResponseRedirect($client->getResponse(), 'Page redirects to other page');
        $this->assertResponseNotFound($client->getResponse(), 'Page not found');
        $this->assertResponseForbidden($client->getResponse(), 'Page forbidden');
        $this->assertResponseCode(201, $client->getResponse(), 'JSON returned', 'application/json');
    }
}
```

### Fixtures appending

You can add fixtures before test running:

```php
<?php

namespace Foo\BarBundle\Tests\Controller;

use Foo\BarBundle\DataFixtures\ORM\Test\ActionRecordData;
use Intaro\SymfonyTestingTools\WebTestCase;

class SomeControllerTest extends WebTestCase
{
    public static function setUpBeforeClass()
    {
        static::appendFixture(new ActionRecordData, [
            'purge' => true,
        ]);
    }

    public function testIndex()
    {
        //...
    }
}
```
