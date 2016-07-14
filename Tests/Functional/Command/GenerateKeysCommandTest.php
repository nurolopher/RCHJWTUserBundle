<?php

/*
 * This file is part of the RCH package.
 *
 * (c) Robin Chalas <https://github.com/chalasr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RCH\JWTUserBundle\Tests\Functional\Command;

use RCH\JWTUserBundle\Command\GenerateKeysCommand;
use RCH\JWTUserBundle\Tests\Functional\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the GenerateKeysCommand execution.
 */
class GenerateKeysCommandTest extends TestCase
{
    /** @var ContainerInterface */
    private static $container;

    /** @var string */
    private static $path;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        self::bootKernel();

        self::$container = static::$kernel->getContainer();
        self::$path = static::$kernel->getVarDir().'jwt';
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unlink(static::$kernel->getRootDir().'/jwt/private.pem');
        unlink(static::$kernel->getRootDir().'/jwt/public.pem');
    }

    /**
     * Test command.
     */
    public function testGenerateKeysCommand()
    {
        $command = new GenerateKeysCommand();
        $command->setContainer(self::$container);
        $passphrase = self::$container->getParameter('rch_jwt_user.passphrase');

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertFileExists(static::$kernel->getRootDir().'/jwt/public.pem');
        $this->assertFileExists(static::$kernel->getRootDir().'/jwt/private.pem');
        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertContains(sprintf('RSA keys successfully generated with passphrase %s', $passphrase), $tester->getDisplay());
    }

    /**
     * Test command.
     */
    public function testGenerateKeysCommandWithEmptyPassphrase()
    {
        $container = $this->getContainerMock();
        $container
            ->expects($this->at(1))
            ->method('getParameter')
            ->with('rch_jwt_user.passphrase')
            ->willReturn('');
        $container
            ->expects($this->at(0))
            ->method('getParameter')
            ->with('kernel.root_dir')
            ->willReturn(static::$kernel->getRootDir());

        $command = new GenerateKeysCommand();
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertFileExists(static::$kernel->getRootDir().'/jwt/public.pem');
        $this->assertFileExists(static::$kernel->getRootDir().'/jwt/private.pem');
        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertContains('RSA keys successfully generated', $tester->getDisplay());
    }

    private function getContainerMock()
    {
        return $this->getMockBuilder(ContainerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
