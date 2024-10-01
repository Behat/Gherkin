<?php

namespace Behat\Gherkin\Cucumber;

use Behat\Gherkin\Node\BackgroundNode;
use Cucumber\Messages\Background;
use Cucumber\Messages\FeatureChild;

final class BackgroundNodeMapper
{
    /**
     * @var StepNodeMapper
     */
    private $stepNodeMapper;

    public function __construct(StepNodeMapper $stepNodeMapper)
    {
        $this->stepNodeMapper = $stepNodeMapper;
    }

    /**
     * @param FeatureChild[] $children
     *
     * @return BackgroundNode|null
     */
    public function map(array $children) : ?BackgroundNode
    {
        foreach($children as $child) {
            if ($child->background) {

                $title = $child->background->name;
                if ($child->background->description) {
                    $title .= "\n" . $child->background->description;
                }

                return new BackgroundNode(
                    MultilineStringFormatter::format(
                        $title,
                        $child->background->location
                    ),
                    $this->stepNodeMapper->map($child->background->steps),
                    $child->background->keyword,
                    $child->background->location->line
                );
            }
        }

        return null;
    }
}
