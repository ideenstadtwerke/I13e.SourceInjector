<?php

declare(strict_types=1);

namespace I13e\SourceInjector\CommandHook;

use Neos\ContentRepository\Core\CommandHandler\CommandHookInterface;
use Neos\ContentRepository\Core\Factory\CommandHookFactoryInterface;
use Neos\ContentRepository\Core\Factory\CommandHooksFactoryDependencies;

final class SourceInjectorSubtreeTagCommandHookFactory implements CommandHookFactoryInterface
{
    public function build(CommandHooksFactoryDependencies $commandHooksFactoryDependencies): CommandHookInterface
    {
        return new SourceInjectorSubtreeTagCommandHook(
            $commandHooksFactoryDependencies->contentGraphReadModel,
            $commandHooksFactoryDependencies->nodeTypeManager,
        );
    }
}
