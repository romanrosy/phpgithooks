<?php

namespace PhpGitHooks\Module\Configuration\Contract\QueryHandler;

use PhpGitHooks\Infrastructure\CommandBus\QueryBus\QueryHandlerInterface;
use PhpGitHooks\Infrastructure\CommandBus\QueryBus\QueryInterface;
use PhpGitHooks\Module\Configuration\Contract\Response\ConfigurationDataResponse;
use PhpGitHooks\Module\Configuration\Service\ConfigurationDataFinder;

class ConfigurationDataFinderQueryHandler implements QueryHandlerInterface
{
    /**
     * @var ConfigurationDataFinder
     */
    private $configurationDataFinder;

    /**
     * ConfigurationDataFinderQueryHandler constructor.
     *
     * @param ConfigurationDataFinder $configurationDataFinder
     */
    public function __construct(ConfigurationDataFinder $configurationDataFinder)
    {
        $this->configurationDataFinder = $configurationDataFinder;
    }

    /**
     * @param QueryInterface $query
     *
     * @return ConfigurationDataResponse
     */
    public function handle(QueryInterface $query)
    {
        return $this->configurationDataFinder->find();
    }
}
