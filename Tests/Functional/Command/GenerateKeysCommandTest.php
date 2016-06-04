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

/**
 * Tests the GenerateKeysCommand execution.
 */
class GenerateKeysCommandTest extends TestCase
{
    /**
     * @var string
     */
    private static $path;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        self::bootKernel();

        self::$path = static::$kernel->getVarDir().'jwt';
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unlink(self::$path.'/private.pem');
        unlink(self::$path.'/public.pem');
    }

    /**
     * Test command.
     */
    public function testGenerateKeysCommand()
    {
        $command = new GenerateKeysCommand();
        $command->setContainer(static::$kernel->getContainer());
        $passphrase = 'test_pass';

        $tester = new CommandTester($command);
        $result = $tester->execute([
            '--passphrase' => $passphrase,
            '--path'       => self::$path,
        ]);

        $this->assertFileExists(self::$path.'/public.pem');
        $this->assertFileExists(self::$path.'/private.pem');
        $this->assertEquals(0, $result);
        $this->assertContains(sprintf('RSA keys successfully generated with passphrase %s', $passphrase), $tester->getDisplay());
    }

    /**
     * Test command.
     */
    public function testGenerateKeysCommandWithEmptyPassphrase()
    {
        $command = new GenerateKeysCommand();
        $command->setContainer(static::$kernel->getContainer());

        $tester = new CommandTester($command);
        $result = $tester->execute([
            '--path' => self::$path,
        ]);

        $this->assertFileExists(self::$path.'/public.pem');
        $this->assertFileExists(self::$path.'/private.pem');
        $this->assertEquals(0, $result);
        $this->assertContains('RSA keys successfully generated', $tester->getDisplay());
    }
}
