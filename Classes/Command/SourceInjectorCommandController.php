<?php

declare(strict_types=1);

namespace I13e\SourceInjector\Command;

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

/**
 * @Flow\Scope("singleton")
 */
final class SourceInjectorCommandController extends CommandController
{
    private const NODE_TYPE = 'I13e.SourceInjector:Document.SourceInjectorPage';
    private const SUBTREE_TAG = 'i13e-sourceinjector-injections';

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * Add the SourceInjector authorization tag to existing page trees.
     *
     * Run once after deploying the Neos 9-compatible SourceInjector policy.
     */
    public function backfillAuthorizationTagsCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $repository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $nodeTypeManager = $repository->getNodeTypeManager();
        $targetNodeType = NodeTypeName::fromString(self::NODE_TYPE);
        $tag = SubtreeTag::fromString(self::SUBTREE_TAG);
        $taggedTrees = 0;

        foreach ($repository->findWorkspaces() as $workspace) {
            $contentGraph = $repository->getContentGraph($workspace->workspaceName);
            foreach ($contentGraph->findUsedNodeTypeNames() as $nodeTypeName) {
                $nodeType = $nodeTypeManager->getNodeType($nodeTypeName);
                if ($nodeType === null) {
                    throw new \RuntimeException(sprintf('Node type "%s" was not found.', $nodeTypeName->value));
                }
                if (!$nodeType->isOfType($targetNodeType)) {
                    continue;
                }

                foreach ($contentGraph->findNodeAggregatesByType($nodeTypeName) as $nodeAggregate) {
                    $dimensionSpacePoint = null;
                    foreach ($nodeAggregate->coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
                        $dimensionSpacePoint = $coveredDimensionSpacePoint;
                        break;
                    }
                    if ($dimensionSpacePoint === null) {
                        throw new \RuntimeException(sprintf(
                            'SourceInjector node "%s" in workspace "%s" does not cover any dimension space points.',
                            $nodeAggregate->nodeAggregateId->value,
                            $workspace->workspaceName->value,
                        ));
                    }

                    $subgraph = $contentGraph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::withoutRestrictions());
                    $hasSourceInjectorAncestor = false;
                    foreach ($subgraph->findAncestorNodes(
                        $nodeAggregate->nodeAggregateId,
                        FindAncestorNodesFilter::create(),
                    ) as $ancestorNode) {
                        $ancestorNodeType = $nodeTypeManager->getNodeType($ancestorNode->nodeTypeName);
                        if ($ancestorNodeType === null) {
                            throw new \RuntimeException(sprintf('Node type "%s" was not found.', $ancestorNode->nodeTypeName->value));
                        }
                        if ($ancestorNodeType->isOfType($targetNodeType)) {
                            $hasSourceInjectorAncestor = true;
                            break;
                        }
                    }
                    if ($hasSourceInjectorAncestor) {
                        continue;
                    }

                    $taggedDimensions = $nodeAggregate->getCoveredDimensionsTaggedBy($tag, false);
                    if ($taggedDimensions->count() === $nodeAggregate->coveredDimensionSpacePoints->count()) {
                        continue;
                    }
                    if (!$taggedDimensions->isEmpty()) {
                        throw new \RuntimeException(sprintf(
                            'Node "%s" in workspace "%s" is only partially tagged; resolve this before rerunning the backfill.',
                            $nodeAggregate->nodeAggregateId->value,
                            $workspace->workspaceName->value,
                        ));
                    }
                    $repository->handle(TagSubtree::create(
                        $workspace->workspaceName,
                        $nodeAggregate->nodeAggregateId,
                        $dimensionSpacePoint,
                        NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS,
                        $tag,
                    ));
                    $taggedTrees++;
                }
            }
        }

        $this->outputLine(
            'Tagged %d SourceInjector page tree(s) in content repository "%s".',
            [$taggedTrees, $contentRepositoryId->value],
        );
    }
}
