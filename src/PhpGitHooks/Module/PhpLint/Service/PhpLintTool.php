<?php

namespace PhpGitHooks\Module\PhpLint\Service;

use PhpGitHooks\Module\Git\Contract\Response\BadJobLogoResponse;
use PhpGitHooks\Module\Git\Service\PreCommitOutputWriter;
use PhpGitHooks\Module\PhpLint\Contract\Exception\PhpLintViolationsException;
use PhpGitHooks\Module\PhpLint\Model\PhpLintToolProcessorInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PhpLintTool
{
    const RUNNING_PHPLINT = 'Running PHPLINT';
    /**
     * @var PhpLintToolProcessorInterface
     */
    private $phpLintTool;
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * PhpLintTool constructor.
     *
     * @param PhpLintToolProcessorInterface $phpLintTool
     * @param OutputInterface               $output
     */
    public function __construct(PhpLintToolProcessorInterface $phpLintTool, OutputInterface $output)
    {
        $this->phpLintTool = $phpLintTool;
        $this->output = $output;
    }

    /**
     * @param array $files
     * @param string $errorMessage
     *
     * @throws PhpLintViolationsException
     */
    public function execute(array $files, $errorMessage)
    {
        $outputMessage = new PreCommitOutputWriter(self::RUNNING_PHPLINT);
        $this->output->write($outputMessage->getMessage());

        $errors = [];
        foreach ($files as $file) {
            $errors[] = $this->phpLintTool->process($file);
        }

        $errors = array_filter($errors);

        if (!empty($errors)) {
            $this->output->writeln($outputMessage->getFailMessage());
            $this->output->writeln($outputMessage->setError(implode('', $errors)));
            $this->output->writeln(BadJobLogoResponse::paint($errorMessage));

            throw new PhpLintViolationsException();
        }

        $this->output->writeln($outputMessage->getSuccessfulMessage());
    }
}
