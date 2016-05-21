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
use Symfony\Component\Yaml\Dumper;

/**
 * Generates SSL Keys for LexikJWT.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class GenerateKeysCommand extends ContainerAwareCommand
{
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
        $io = new SymfonyStyle($input, $output);
        $fs = new FileSystem();

        $io->title('RCHJWTUserBundle - Generate SSL Keys');

        $rootDir = $this->getContainer()->getParameter('kernel.root_dir');
        $passphrase = $input->getOption('passphrase');

        if (!$path = $input->getOption('path')) {
            $path = $rootDir.'/jwt';

            /* Symfony3 directory structure */
            if (is_writable($rootDir.'/../var')) {
                $path = $rootDir.'/../var/jwt';
            }
        }

        if (!$passphrase) {
            $passphrase = 'test';
        }
        // var_dump($path);die;

        if (!$fs->exists($path)) {
            $fs->mkdir($path);
        }

        $this->generatePrivateKey($path, $passphrase, $output);
        $this->generatePublicKey($path, $passphrase, $output);

        $output->writeln(sprintf('<info>RSA keys successfully generated with passphrase <comment>%s</comment></info>', $passphrase));
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
    protected function generatePrivateKey($path, $passphrase, OutputInterface $output)
    {
        $processArgs = sprintf('genrsa -out %s/private.pem  -aes256 -passout pass:%s 4096', $path, $passphrase);

        $this->generateKey($processArgs, $output);
    }

    /**
     * Generate a RSA public key.
     *
     * @param string          $path
     * @param string          $passphrase
     * @param OutputInterface $output
     */
    protected function generatePublicKey($path, $passphrase, OutputInterface $output)
    {
        $processArgs = sprintf('rsa -pubout -in %s/private.pem -out %s/public.pem -passin pass:%s', $path, $path, $passphrase);

        $this->generateKey($processArgs, $output);
    }

    /**
     * Generate a RSA key.
     *
     * @param string          $processArgs
     * @param Outputinterface $output
     *
     * @throws ProcessFailedException
     */
    protected function generateKey($processArgs, OutputInterface $output)
    {
        $process = new Process(sprintf('openssl %s', $processArgs));
        $process->setTimeout(3600);

        $process->run(function ($type, $buffer) use ($output) {
            if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $output->write($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $process->getExitCode();
    }

    /**
     * Write in parameters.yml (work in progress).
     *
     * @param string $rootDir
     * @param string $keysPath
     * @param string $passphrass
     *
     * @return array $config
     */
    protected function writeParameters($rootDir, $keysPath, $passphrase, OutputInterface $output)
    {
        $config = [
            'lexik_jwt_authentication' => [
                'private_key_path' => '%jwt_private_key_path%',
                'public_key_path'  => '%jwt_public_key_path%',
                'pass_phrase'      => '%jwt_key_pass_phrase%',
            ],
        ];

        $parameters = [
            'lexik_jwt_authentication' => [
                'jwt_private_key_path' => $keysPath.'/private.pem',
                'jwt_public_key_path'  => $keysPath.'/public.pem',
                'jwt_key_pass_phrase'  => $passphrase,
            ],
        ];

        $dumper = new Dumper();
        $yamlParameters = $dumper->dump($parameters);
        $parametersPath = $rootDir.'/config/parameters.yml';

        $output->writeln($dumper->dump($config));
        file_put_contents($parametersPath.'.dist', $yamlParameters);

        return $config;
    }
}
