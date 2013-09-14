<?php

namespace Behat\Gherkin\Node;

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Gherkin scenario-like interface.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface ScenarioLikeInterface extends KeywordNodeInterface, StepContainerInterface
{
    /**
     * Sets scenario feature.
     *
     * @param FeatureNode $feature
     */
    public function setFeature(FeatureNode $feature);

    /**
     * Sets scenario feature.
     *
     * @return FeatureNode
     */
    public function getFeature();
}
