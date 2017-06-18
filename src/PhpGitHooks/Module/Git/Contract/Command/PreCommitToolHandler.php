<?php

namespace PhpGitHooks\Module\Git\Contract\Command;

use Bruli\EventBusBundle\CommandBus\CommandBus;
use Bruli\EventBusBundle\CommandBus\CommandHandlerInterface;
use Bruli\EventBusBundle\CommandBus\CommandInterface;
use Bruli\EventBusBundle\QueryBus\QueryBus;
use PhpGitHooks\Module\Composer\Contract\Command\ComposerTool;
use PhpGitHooks\Module\Configuration\Contract\Query\ConfigurationDataFinder;
use PhpGitHooks\Module\Configuration\Contract\Response\ConfigurationDataResponse;
use PhpGitHooks\Module\Configuration\Contract\Response\PreCommitResponse;
use PhpGitHooks\Module\Files\Contract\Query\PhpFilesExtractor;
use PhpGitHooks\Module\Files\Contract\Response\PhpFilesResponse;
use PhpGitHooks\Module\Git\Contract\Response\GoodJobLogoResponse;
use PhpGitHooks\Module\Git\Infrastructure\Files\FilesCommittedExtractor;
use PhpGitHooks\Module\Git\Infrastructure\OutputWriter\ToolTittleOutputWriter;
use PhpGitHooks\Module\JsonLint\Contract\Command\JsonLintTool;
use PhpGitHooks\Module\PhpCs\Contract\Command\PhpCsTool;
use PhpGitHooks\Module\PhpCsFixer\Contract\Command\PhpCsFixerToolCommand;
use PhpGitHooks\Module\PhpLint\Contract\Command\PhpLintToolCommand;
use PhpGitHooks\Module\PhpMd\Contract\Command\PhpMdToolCommand;
use PhpGitHooks\Module\PhpUnit\Contract\Command\GuardCoverageCommand;
use PhpGitHooks\Module\PhpUnit\Contract\Command\PhpUnitToolCommand;
use PhpGitHooks\Module\PhpUnit\Contract\Command\StrictCoverageCommand;
use Symfony\Component\Console\Output\OutputInterface;

class PreCommitToolHandler implements CommandHandlerInterface
{
    const NO_FILES_CHANGED_MESSAGE = '<comment>-\_(ö)_/- No files changed.</comment>';
    const TITLE = 'Pre-Commit tool';
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var FilesCommittedExtractor
     */
    private $filesCommittedExtractor;
    /**
     * @var CommandBus
     */
    private $commandBus;
    /**
     * @var QueryBus
     */
    private $queryBus;
    /**
     * @var ToolTittleOutputWriter
     */
    private $tittleOutputWriter;

    /**
     * PreCommitTool constructor.
     *
     * @param OutputInterface         $output
     * @param FilesCommittedExtractor $filesCommittedExtractor
     * @param QueryBus                $queryBus
     * @param CommandBus              $commandBus
     * @param ToolTittleOutputWriter  $tittleOutputWriter
     */
    public function __construct(
        OutputInterface $output,
        FilesCommittedExtractor $filesCommittedExtractor,
        QueryBus $queryBus,
        CommandBus $commandBus,
        ToolTittleOutputWriter $tittleOutputWriter
    ) {
        $this->filesCommittedExtractor = $filesCommittedExtractor;
        $this->output = $output;
        $this->commandBus = $commandBus;
        $this->queryBus = $queryBus;
        $this->tittleOutputWriter = $tittleOutputWriter;
    }

    private function execute()
    {
        $this->tittleOutputWriter->writeTitle(self::TITLE);
        $committedFiles = $this->filesCommittedExtractor->getFiles();

        if (1 === count($committedFiles)) {
            $this->output->writeln(static::NO_FILES_CHANGED_MESSAGE);

            return;
        }

        /**
         * @var ConfigurationDataResponse
         */
        $configurationData = $this->queryBus->handle(new ConfigurationDataFinder());
        $preCommit = $configurationData->getPreCommit();

        if (true === $preCommit->isPreCommit()) {
            $this->executeTools($preCommit, $committedFiles);
        }

        $this->output->writeln(GoodJobLogoResponse::paint($preCommit->getRightMessage()));
    }

    /**
     * @param PreCommitResponse $preCommitResponse
     * @param array             $committedFiles
     */
    private function executeTools(PreCommitResponse $preCommitResponse, array $committedFiles)
    {
        if (true === $preCommitResponse->isComposer()) {
            $this->commandBus->handle(
                new ComposerTool($committedFiles, $preCommitResponse->getErrorMessage())
            );
        }

        if (true === $preCommitResponse->isJsonLint()) {
            $this->commandBus->handle(
                new JsonLintTool($committedFiles, $preCommitResponse->getErrorMessage())
            );
        }

        $phpFiles = $this->getPhpFiles($committedFiles);

        if ($phpFiles) {
            if (true === $preCommitResponse->isPhpLint()) {
                $this->commandBus->handle(
                    new PhpLintToolCommand($phpFiles, $preCommitResponse->getErrorMessage())
                );
            }

            $phpCsResponse = $preCommitResponse->getPhpCs();

            if (true === $phpCsResponse->isPhpCs()) {
                $this->commandBus->handle(
                    new PhpCsTool(
                        $phpFiles,
                        $phpCsResponse->getPhpCsStandard(),
                        $preCommitResponse->getErrorMessage(),
                        $phpCsResponse->getIgnore()
                    )
                );
            }

            $phpCsFixerResponse = $preCommitResponse->getPhpCsFixer();

            if (true === $phpCsFixerResponse->isPhpCsFixer()) {
                $this->commandBus->handle(
                    new PhpCsFixerToolCommand(
                        $phpFiles,
                        $phpCsFixerResponse->isPhpCsFixerPsr0(),
                        $phpCsFixerResponse->isPhpCsFixerPsr1(),
                        $phpCsFixerResponse->isPhpCsFixerPsr2(),
                        $phpCsFixerResponse->isPhpCsFixerSymfony(),
                        $phpCsFixerResponse->getPhpCsFixerOptions(),
                        $preCommitResponse->getErrorMessage()
                    )
                );
            }

            $phpMdResponse = $preCommitResponse->getPhpMd();

            if (true === $phpMdResponse->isPhpMd()) {
                $this->commandBus->handle(
                    new PhpMdToolCommand(
                        $phpFiles,
                        $phpMdResponse->getPhpMdOptions(),
                        $preCommitResponse->getErrorMessage()
                    )
                );
            }

            $phpunitResponse = $preCommitResponse->getPhpUnit();

            if (true === $phpunitResponse->isPhpunit()) {
                $this->commandBus->handle(
                    new PhpUnitToolCommand(
                        $phpunitResponse->isPhpunitRandomMode(),
                        $phpunitResponse->getPhpunitOptions(),
                        $preCommitResponse->getErrorMessage()
                    )
                );

                $phpunitStrictCoverageResponse = $preCommitResponse->getPhpUnitStrictCoverage();

                if (true === $phpunitStrictCoverageResponse->isPhpunitStrictCoverage()) {
                    $this->commandBus->handle(
                        new StrictCoverageCommand(
                            $phpunitStrictCoverageResponse->getMinimum(),
                            $preCommitResponse->getErrorMessage()
                        )
                    );
                }

                $phpunitGuardCoverageResponse = $preCommitResponse->getPhpUnitGuardCoverage();

                if (true === $phpunitGuardCoverageResponse->isEnabled()) {
                    $this->commandBus->handle(
                        new GuardCoverageCommand(
                            $phpunitGuardCoverageResponse->getWarningMessage()
                        )
                    );
                }
            }
        }
    }

    /**
     * @param array $committedFiles
     *
     * @return array
     */
    private function getPhpFiles(array $committedFiles)
    {
        /**
         * @var PhpFilesResponse
         */
        $phpFilesResponse = $this->queryBus->handle(new PhpFilesExtractor($committedFiles));

        return $phpFilesResponse->getFiles();
    }

    /**
     * @param CommandInterface $command
     */
    public function handle(CommandInterface $command)
    {
        $this->execute();
    }
}
