<?php

/**
 * This file is part of the RCH package.
 *
 * Robin Chalas <robin.chalas@gmail.com>
 *
 * For more informations about license, please see the LICENSE
 * file distributed in this source code.
 */
namespace RCH\JWTUserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Dumper;

// TODO :
// - generate lexik_jwt config and parameters yml
// - Create exceptionresponselistener depending on 'rch_jwt_user.exception_listener.format'

/**
 * Generates RSA Keys for LexikJWT.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class GenerateKeysCommand extends ContainerAwareCommand
{
    use OutputHelperTrait;

    protected $configTemplate = '

';

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

        if (!$passphrase || 'random' == $passphrase) {
            $passphrase = random_bytes(10);
        }

        $path = $kernelRootDir.'/jwt';

        /* Compatibility Symfony3 directory structure */
        if (is_writable($kernelRootDir.'/../var')) {
            $path = $kernelRootDir.'/../var/jwt';
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
     * Write in parameters.yml
     *
     * @param string $rootDir
     * @param string $keysPath
     * @param string $passphrass
     *
     * @return array $config
     */
    protected function writeParameters($rootDir, $keysPath, $passphrase)
    {
        $config = array(
            'lexik_jwt_authentication' => array(
                'private_key_path' => "%jwt_private_key_path%"
                'public_key_path'  => "%jwt_public_key_path%"
                'pass_phrase'      => "%jwt_key_pass_phrase%",
            ),
        );

        $parameters = array(
            'lexik_jwt_authentication' => array(
                'jwt_private_key_path' => $keysPath.'/private.pem',
                'jwt_public_key_path'  => $keysPath.'/public.pem',
                'jwt_key_pass_phrase'  => $passphrase,
            ),
        );

        $dumper = new Dumper();
        $yamlParameters = $dumper->dump($parameters);
        $yamlConfig = $dumper->dump($config);
        $parametersPath = $rootDir.'/config/parameters.yml';

        file_put_contents($path.'.dist', $yamlParameters);

        return $config;
    }
}
