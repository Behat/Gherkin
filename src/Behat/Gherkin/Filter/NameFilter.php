<?php

namespace Behat\Gherkin\Filter;

use Behat\Gherkin\Node\FeatureNode,
    Behat\Gherkin\Node\ScenarioNode;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Filters scenarios by feature/scenario name.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class NameFilter extends SimpleFilter
{
    protected $filterString;
    protected $filterLine;

    /**
     * Initializes filter.
     *
     * @param string $filterString Name filter string
     */
    public function __construct($filterString)
    {
        $this->filterString = trim($filterString);
       // main scenario Checking defaults search criteria, Ex #3
        $completeScenario =  explode(', Ex #', $this->filterString);
        if( isset($completeScenario[1]) ){
            $this->filterLine = $completeScenario[1];
            $this->filterString = $completeScenario[0];
        }else{
            $this->filterLine = 0;
        }
        syslog(LOG_WARNING, "main scenario " . $this->filterString);
        
    }

    /**
     * Checks if Feature matches specified filter.
     *
     * @param FeatureNode $feature Feature instance
     *
     * @return Boolean
     */
    public function isFeatureMatch(FeatureNode $feature)
    {
        if ('/' === $this->filterString[0]) {
            return (bool) preg_match($this->filterString, $feature->getTitle());
        }

        return false !== mb_strpos($feature->getTitle(), $this->filterString);
    }

    /**
     * Checks if scenario or outline matches specified filter.
     *
     * @param ScenarioNode $scenario Scenario or Outline node instance
     *
     * @return Boolean
     */
    public function isScenarioMatch(ScenarioNode $scenario)
    {


        if ('/' === $this->filterString[0] && 1 === preg_match($this->filterString, $scenario->getTitle())) {
             
            return true;
        } elseif (false !== mb_strpos($scenario->getTitle(), $this->filterString)) {
            if( $this->filterLine !== 0  && $scenario->hasExamples() ){
                $result = count( $scenario->getExamples()->getNumeratedRows());
                if($result >= $this->filterLine ){
                      //syslog(LOG_WARNING, "test12:  $result " . $this->filterLine );
                    return true;
                   
                }
                 
            }else{
                return true;
            }
        }
      
       
      //  return false;

        if (null !== $scenario->getFeature()) {
            return $this->isFeatureMatch($scenario->getFeature());
        }






        return false;
    }

   
   /**
     * Filters feature according to the filter.
     *
     * @param FeatureNode $feature
     */
    public function filterFeature(FeatureNode $feature)
    {
         if( $this->filterLine == 0 ){
             return parent::filterFeature($feature);
         }
        
        $scenarios = $feature->getScenarios();
        foreach ($scenarios as $i => $scenario) {
            if (!$this->isScenarioMatch($scenario)) {
                unset($scenarios[$i]);
                continue;
            }

            $counter = 1;
            if ($scenario->hasExamples()) {
                $lines = $scenario->getExamples()->getRowLines();
                $rows  = $scenario->getExamples()->getNumeratedRows();

                $scenario->getExamples()->setRows(array());
                $scenario->getExamples()->addRow($rows[$lines[0]], $lines[0]);
                unset($rows[$lines[0]]);
     
               
                foreach ($rows as $line => $row) {
                  
                    if($counter  == $this->filterLine){
                        $scenario->getExamples()->addRow($row, $line);
                    } 
                $counter++;
                }
            }


        }
        $feature->setScenarios($scenarios);
    
    }



}


