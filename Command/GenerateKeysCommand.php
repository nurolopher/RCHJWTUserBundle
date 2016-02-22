<?php

/**
 * This file is part of the RCHJWTUserBundle package.
 *
 * Robin Chalas <robin.chalas@gmail.com>
 *
 * For more informations about license, please see the LICENSE
 * file distributed in this source code.
 */
namespace RCH\JWTUserBundle\Command;

use RCH\JWTUserBundle\Util\OutputHelperTrait as OutputHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GenerateKeysCommand extends ContainerAwareCommand
{
    use OutputHelper;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('rch:jwt:generate-keys')
          ->setDescription('Generate RSA keys used by LexikJWTAuthenticationBundle');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->sayWelcome($output);
        $kernelRootDir = $this->getContainer()->getParameter('kernel.root_dir');
        $question = new Question('Choose the passphrase of your private RSA key : ');

        $questionHelper = $this->getHelper('question');
        $passphrase = $questionHelper->ask($input, $output, $question);

        if (!$passphrase) {
            $passphrase = random_bytes(10);
        }

        if (is_writable($kernelRootDir.'/../var')) {
            $path = $kernelRootDir.'/../var/jwt';
        } else {
            $path = $kernelRootDir.'/var/jwt';
        }

        $fs = new FileSystem();
        $fs->mkdir($path);

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
        $processArgs = 'genrsa '
            .sprintf('-out %s/private.pem  -aes256 ', $path)
            .sprintf('-passout pass:%s ', $passphrase)
            .'4096'
        ;

        try {
            $this->generateKey($processArgs, $output);
        } catch (ProcessFailedException $e) {
            $output->writeln('<error>An error occured while generating the private key.</error>');
        }
    }

    /**
     * Generate a RSA public key.
     *
     * @param string          $path
     * @param string          $passphrase
     * @param OutputInterface $output
     *
     * @throws ProcessFailedException
     */
    protected function generatePublicKey($path, $passphrase, OutputInterface $output)
    {
        $processArgs = 'rsa '
            .sprintf('-pubout -in %s/private.pem ', $path)
            .sprintf('-out %s/public.pem ', $path)
            .sprintf('-passin pass:%s', $passphrase)
        ;

        try {
            $this->generateKey($processArgs, $output);
        } catch (ProcessFailedException $e) {
            $output->writeln('<error>An error occured while generating the public key.</error>');
        }
    }

    /**
     * Generate a RSA key.
     *
     * @param array $args
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
}
