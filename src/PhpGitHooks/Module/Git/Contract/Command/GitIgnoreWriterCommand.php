<?php

namespace PhpGitHooks\Module\Git\Contract\Command;

use PhpGitHooks\Infrastructure\CommandBus\CommandBus\CommandInterface;

class GitIgnoreWriterCommand implements CommandInterface
{
    /**
     * @var string
     */
    private $content;

    /**
     * GitIgnoreWriterCommand constructor.
     *
     * @param string $content
     */
    public function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
}
