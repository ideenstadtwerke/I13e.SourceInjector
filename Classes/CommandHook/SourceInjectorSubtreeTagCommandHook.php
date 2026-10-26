<?php

declare(strict_types=1);

namespace I13e\SourceInjector\CommandHook;

use Neos\ContentRepository\Core\CommandHandler\CommandHookInterface;
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\CommandHandler\Commands;
use Neos\ContentRepository\Core\EventStore\PublishedEvents;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;

final class SourceInjectorSubtreeTagCommandHook implements CommandHookInterface
{
    private const NODE_TYPE = 'I13e.SourceInjector:Document.SourceInjectorPage';
    private const SUBTREE_TAG = 'i13e-sourceinjector-injections';

    public function __construct(
        private readonly ContentGraphReadModelInterface $contentGraphReadModel,
        private readonly NodeTypeManager $nodeTypeManager,
    ) {
    }

    public function onBeforeHandle(CommandInterface $command): CommandInterface
    {
        return $command;
    }

    public function onAfterHandle(CommandInterface $command, PublishedEvents $events): Commands
    {
        if (!$command instanceof CreateNodeAggregateWithNode) {
            return Commands::createEmpty();
        }

        $sourceInjectorNodeType = NodeTypeName::fromString(self::NODE_TYPE);
        $createdNodeType = $this->nodeTypeManager->getNodeType($command->nodeTypeName);
        if ($createdNodeType === null) {
            throw new \RuntimeException(sprintf('Node type "%s" was not found.', $command->nodeTypeName->value));
        }
        if (!$createdNodeType->isOfType($sourceInjectorNodeType)) {
            return Commands::createEmpty();
        }

        $contentGraph = $this->contentGraphReadModel->getContentGraph($command->workspaceName);
        $nodeAggregate = $contentGraph->findNodeAggregateById($command->nodeAggregateId);
        if ($nodeAggregate === null) {
            throw new \RuntimeException(sprintf(
                'Created SourceInjector node "%s" could not be found in workspace "%s".',
                $command->nodeAggregateId->value,
                $command->workspaceName->value,
            ));
        }

        $tag = SubtreeTag::fromString(self::SUBTREE_TAG);
        $taggedDimensions = $nodeAggregate->getCoveredDimensionsTaggedBy($tag, false);
        if ($taggedDimensions->count() === $nodeAggregate->coveredDimensionSpacePoints->count()) {
            return Commands::createEmpty();
        }
        if (!$taggedDimensions->isEmpty()) {
            throw new \RuntimeException(sprintf(
                'SourceInjector node "%s" is only partially tagged.',
                $command->nodeAggregateId->value,
            ));
        }

        foreach ($nodeAggregate->coveredDimensionSpacePoints as $dimensionSpacePoint) {
            return Commands::create(TagSubtree::create(
                $command->workspaceName,
                $command->nodeAggregateId,
                $dimensionSpacePoint,
                NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS,
                $tag,
            ));
        }

        throw new \RuntimeException(sprintf(
            'Created SourceInjector node "%s" does not cover any dimension space points.',
            $command->nodeAggregateId->value,
        ));
    }
}
