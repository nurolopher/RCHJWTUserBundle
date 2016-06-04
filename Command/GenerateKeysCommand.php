<?php

/*
 * This file is part of the RCH package.
 *
 * (c) Robin Chalas <https://github.com/chalasr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RCH\JWTUserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Generates SSL Keys for LexikJWT.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class GenerateKeysCommand extends ContainerAwareCommand
{
    private $io;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('rch:jwt:generate-keys')
          ->setDescription('Generate SSL keys to be consumed by LexikJWTAuthenticationBundle')
          ->addOption('passphrase', 'pp', InputOption::VALUE_REQUIRED, 'Passphrase used to encrypt/decrypt the generated keys', '')
          ->addOption('path', null, InputOption::VALUE_REQUIRED, 'The path where in the keys will be generated.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $fs = new FileSystem();

        $this->io->title('RCHJWTUserBundle - Generate SSL Keys');

        $rootDir = $this->getContainer()->getParameter('kernel.root_dir');
        $passphrase = $input->getOption('passphrase');

        if (!$path = $input->getOption('path')) {
            $path = $rootDir.'/jwt';

            /* Symfony3 directory structure */
            if (is_writable($rootDir.'/../var')) {
                $path = $rootDir.'/../var/jwt';
            }
        }

        if (!$fs->exists($path)) {
            $fs->mkdir($path);
        }

        $this->generatePrivateKey($path, $passphrase, $this->io);
        $this->generatePublicKey($path, $passphrase, $this->io);

        $outputMessage = 'RSA keys successfully generated';

        if ($passphrase) {
            $outputMessage .= $this->io->getFormatter()->format(
                sprintf(' with passphrase <comment>%s</comment></info>', $passphrase)
            );
        }

        $this->io->success($outputMessage);
    }

    /**
     * Generate a RSA private key.
     *
     * @param string          $path
     * @param string          $passphrase
     * @param OutputInterface $output
     *
     * @throws ProcessFailedException
     */
    protected function generatePrivateKey($path, $passphrase)
    {
        if ($passphrase) {
            $processArgs = sprintf('genrsa -out %s/private.pem -aes256 -passout pass:%s 4096', $path, $passphrase);
        } else {
            $processArgs = sprintf('genrsa -out %s/private.pem 4096', $path);
        }

        $this->generateKey($processArgs);
    }

    /**
     * Generate a RSA public key.
     *
     * @param string          $path
     * @param string          $passphrase
     * @param OutputInterface $output
     */
    protected function generatePublicKey($path, $passphrase)
    {
        $processArgs = sprintf('rsa -pubout -in %s/private.pem -out %s/public.pem -passin pass:%s', $path, $path, $passphrase);

        $this->generateKey($processArgs);
    }

    /**
     * Generate a RSA key.
     *
     * @param string          $processArgs
     * @param Outputinterface $output
     *
     * @throws ProcessFailedException
     */
    protected function generateKey($processArgs)
    {
        $process = new Process(sprintf('openssl %s', $processArgs));
        $process->setTimeout(3600);

        $process->run(function ($type, $buffer) {
            $this->io->write($buffer);
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $process->getExitCode();
    }
}
