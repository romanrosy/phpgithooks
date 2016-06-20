<?php

namespace PhpGitHooks\Module\Configuration\Tests\Behaviour;

use PhpGitHooks\Module\Configuration\Contract\Query\ConfigurationDataFinderQuery;
use PhpGitHooks\Module\Configuration\Contract\QueryHandler\ConfigurationDataFinderQueryHandler;
use PhpGitHooks\Module\Configuration\Contract\Response\ConfigurationDataResponse;
use PhpGitHooks\Module\Configuration\Domain\PhpCs;
use PhpGitHooks\Module\Configuration\Domain\PhpCsFixer;
use PhpGitHooks\Module\Configuration\Domain\PhpUnit;
use PhpGitHooks\Module\Configuration\Service\ConfigurationDataFinder;
use PhpGitHooks\Module\Configuration\Tests\Infrastructure\ConfigurationUnitTestCase;
use PhpGitHooks\Module\Configuration\Tests\Stub\CommitMsgStub;
use PhpGitHooks\Module\Configuration\Tests\Stub\ConfigArrayDataStub;
use PhpGitHooks\Module\Configuration\Tests\Stub\ConfigStub;
use PhpGitHooks\Module\Configuration\Tests\Stub\PreCommitStub;

class ConfigurationDataFinderQueryHandlerTest extends ConfigurationUnitTestCase
{
    /**
     * @var ConfigurationDataFinderQueryHandler
     */
    private $configurationDataFinderQueryHandler;

    protected function setUp()
    {
        $this->configurationDataFinderQueryHandler = new ConfigurationDataFinderQueryHandler(
            new ConfigurationDataFinder(
                $this->getConfigurationFileReader()
            )
        );
    }

    /**
     * @test
     */
    public function itShouldReturnEnabledTools()
    {
        $configArray = ConfigArrayDataStub::hooksEnabledWithEnabledTools();
        $this->shouldReadConfigurationData(ConfigStub::create(
            PreCommitStub::createUndefined(),
            CommitMsgStub::setUndefined()
        ));

        /** @var ConfigurationDataResponse $data */
        $data = $this->configurationDataFinderQueryHandler->handle(new ConfigurationDataFinderQuery());
        
        $toolInterfaces = $data->getPreCommit()->getExecute()->execute();
        $composer = $toolInterfaces[0];
        $jsonLint = $toolInterfaces[1];
        $phpLint = $toolInterfaces[2];
        $phpMd = $toolInterfaces[3];
        /** @var PhpCs $phpCs */
        $phpCs = $toolInterfaces[4];
        /** @var PhpCsFixer $phpCsFixer */
        $phpCsFixer = $toolInterfaces[5];
        /** @var PhpUnit $phpUnit */
        $phpUnit = $toolInterfaces[6];

        $this->assertFalse($data->getPreCommit()->isUndefined());
        $this->assertTrue($data->getPreCommit()->isEnabled());

        $this->assertFalse($data->getCommitMsg()->isUndefined());
        $this->assertTrue($data->getCommitMsg()->isEnabled());

        $this->assertFalse($composer->isUndefined());
        $this->assertTrue($composer->isEnabled());

        $this->assertFalse($jsonLint->isUndefined());
        $this->assertTrue($jsonLint->isEnabled());

        $this->assertFalse($phpLint->isUndefined());
        $this->assertTrue($phpLint->isEnabled());

        $this->assertFalse($phpMd->isUndefined());
        $this->assertTrue($phpMd->isEnabled());

        $this->assertFalse($phpCs->isUndefined());
        $this->assertTrue($phpCs->isEnabled());
        $this->assertSame(ConfigArrayDataStub::PHPCS_STANDARD, $phpCs->getStandard()->value());

        $this->assertFalse($phpCsFixer->isUndefined());
        $this->assertTrue($phpCsFixer->isEnabled());
        $this->assertTrue($phpCsFixer->getLevels()->getPsr0()->value());
        $this->assertTrue($phpCsFixer->getLevels()->getPsr1()->value());
        $this->assertTrue($phpCsFixer->getLevels()->getPsr2()->value());
        $this->assertTrue($phpCsFixer->getLevels()->getSymfony()->value());

        $this->assertFalse($phpUnit->isUndefined());
        $this->assertTrue($phpUnit->isEnabled());
        $this->assertTrue($phpUnit->getRandomMode()->value());
        $this->assertSame(ConfigArrayDataStub::PHPUNIT_OPTIONS, $phpUnit->getOptions()->value());
    }
}
