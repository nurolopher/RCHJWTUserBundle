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

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides helper methods.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
trait OutputHelperTrait
{
    /**
     * Writes stylized welcome message in Output.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function sayWelcome(OutputInterface $output)
    {
        $breakline = '';
        $output = $this->createBlockTitle($output);
        $title = $this->formatAsTitle('Thank\'s to use RCHJWTUserBundle');
        $welcome = [$breakline, $title, $breakline];

        $output->writeln($welcome);
    }

    /**
     * Create a title block.
     *
     * @param OutputInterface $output
     *
     * @return OutputInterface
     */
    protected function createBlockTitle(OutputInterface $output)
    {
        $style = new OutputFormatterStyle('white', 'blue', ['bold']);
        $output->getFormatter()->setStyle('title', $style);

        return $output;
    }

    /**
     * Formats string as output block title.
     *
     * @param string $content
     *
     * @return string
     */
    protected function formatAsTitle($content)
    {
        $formatter = $this->getHelper('formatter');
        $title = $formatter->formatBlock($content, 'title', true);

        return $title;
    }
}
