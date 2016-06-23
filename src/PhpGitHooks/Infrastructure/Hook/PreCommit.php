<?php

namespace PhpGitHooks\Infrastructure\Hook;

require_once __DIR__.'/../../../../app/AppKernel.php';

use AppKernel;
use PhpGitHooks\Module\Git\Contract\Command\PreCommitToolCommand;
use PhpGitHooks\Module\Git\Contract\CommandHandler\PreCommitToolCommandHandler;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PreCommit extends Application
{
    /**
     * @var AppKernel
     */
    private $container;

    /**
     * PreCommit constructor.
     */
    public function __construct()
    {
        $this->container = new AppKernel();
        parent::__construct('pre-commit');
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        /** @var PreCommitToolCommandHandler $command */
        $command = $this->container->get('command.bus');
        $command->handle(new PreCommitToolCommand());
    }
}
