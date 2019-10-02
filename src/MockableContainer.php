<?php

namespace Intaro\SymfonyTestingTools;

use Symfony\Component\DependencyInjection\Container;

class MockableContainer extends Container
{
    /**
     * @var array
     */
    private static $mockedServices = array();

    /**
     * @param string $id
     * @param object $mock
     *
     * @return object
     * @throws \InvalidArgumentException
     */
    public function mock($id, $mock)
    {
        if (!$this->has($id)) {
            throw new \InvalidArgumentException(sprintf('Cannot mock unexisting service: "%s"', $id));
        }

        if ($this->hasMock($id)) {
            throw new \InvalidArgumentException(sprintf('Service "%s" is already mocked', $id));
        }

        return self::$mockedServices[$id] = $mock;
    }

    /**
     * @param string $id
     */
    public function unmock($id)
    {
        unset(self::$mockedServices[$id]);
    }

    public function clearMocks()
    {
        self::$mockedServices = [];
    }

    /**
     * @param string $id
     * @param int    $invalidBehavior
     *
     * @return object
     * @throws \Exception
     */
    public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
        if ($this->hasMock($id)) {
            return self::$mockedServices[$id];
        }

        return parent::get($id, $invalidBehavior);
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has($id)
    {
        return $this->hasMock($id) || parent::has($id);
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function hasMock($id)
    {
        return isset(self::$mockedServices[$id]);
    }

    /**
     * @return array
     */
    public function getMockedServices()
    {
        return self::$mockedServices;
    }
}
