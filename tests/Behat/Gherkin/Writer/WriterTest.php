<?php

namespace Tests\Behat\Gherkin;

use Behat\Gherkin\Gherkin,
    Behat\Gherkin\Writer,
    Behat\Gherkin\Node\FeatureNode,
    Behat\Gherkin\Node\ScenarioNode,
    Behat\Gherkin\Node\StepNode,
    Behat\Gherkin\Node\OutlineNode,
    Behat\Gherkin\Node\BackgroundNode,
    Behat\Gherkin\Node\TableNode,
    Behat\Gherkin\Keywords\ArrayKeywords;

/**
 * @group writer
 */
class WriterTest extends \PHPUnit_Framework_TestCase
{

    private $keywords;

    public function setUp() {
        $this->keywords = new ArrayKeywords(array(
                    'en' => array(
                        'feature' => 'Feature',
                        'background' => 'Background',
                        'scenario' => 'Scenario',
                        'scenario_outline' => 'Scenario Outline|Scenario Template',
                        'examples' => 'Examples|Scenarios',
                        'given' => 'Given',
                        'when' => 'When',
                        'then' => 'Then',
                        'and' => 'And',
                        'but' => 'But'
                    )
                ));
        $this->keywords->setLanguage('en');
    }

    public function testWriteSimpleTextReturnsWellFormatedContent() {
        $writer = new Writer($this->keywords);
        $this->assertEquals('abc', $writer->writeText('abc'));
    }

    /**
     * @dataProvider providerMultilinesText
     */
    public function testWriteMultilinesTextReturnWellIndentedContent($given, $expected, $indent) {
        $writer = new Writer($this->keywords);
        $this->assertEquals($expected, $writer->writeText($given, $indent));
    }

    public function testWriteCommentReturnsWellFormatedContent() {
        $writer = new Writer($this->keywords);
        $this->assertEquals('# abc', $writer->writeComment('abc'));
    }

    public function testWriteTagsReturnsWellFormatedContent() {
        $writer = new Writer($this->keywords);
        $this->assertEquals('@tag1 @tag2', $writer->writeTags(array('tag1', 'tag2')));
    }

    public function testWriteTagsWithEmptyValueReturnsNothing() {
        $writer = new Writer($this->keywords);
        $this->assertEmpty($writer->writeTags(array()));
    }

    public function testWriteKeywordWithTextReturnsWellFormatedContent() {
        $writer = new Writer($this->keywords);
        $this->assertEquals('key: value', $writer->writeKeyword('key', 'value'));
    }

    /**
     * @dataProvider providerTableNode
     */
    public function testWriteTableNodeReturnsTableNodeInText(TableNode $tableNode, $expected) {
        $writer = new Writer($this->keywords);
        $this->assertEquals($expected, $writer->writeTableNode($tableNode));
    }

    /**
     * @dataProvider providerStep
     */
    public function testWriteStepReturnsValidContentWhenSimpleStepIsGiven(StepNode $step, $expected) {
        $writer = new Writer($this->keywords);
        $this->assertEquals($expected, $writer->writeStep($step));
    }

    /**
     * @expectedException \Behat\Gherkin\Exception\Exception
     */
    public function testWriteStepThrowsExceptionWhenInvalidStepIsGiven() {
        $writer = new Writer($this->keywords);
        $step = new StepNode('NothingAndDoesNotExist', 'some text');
        $writer->writeStep($step);
    }

    public function testWritebackgroundReturnsWellFormatedContent() {
        $writer = new Writer($this->keywords);
        $background = new BackgroundNode('my title');
        $background->addStep(new StepNode('Given', 'I use behat'));

        $expected = 'Background: my title
  Given I use behat';

        $this->assertEquals($expected, $writer->writeBackground($background));
    }

    public function testWriteSimpleScenarioReturnsWellFormatedContent() {
        $writer = new Writer($this->keywords);
        $scenario = new ScenarioNode('my scenario');
        $scenario->addStep(new StepNode('Given', 'my example1'));
        $scenario->addStep(new StepNode('When', 'I do anything'));

        $expected = '
  Scenario: my scenario
    Given my example1
    When I do anything';
        $this->assertEquals($expected, $writer->writeScenario($scenario));
    }

    public function testWriteScenarioWithTagsAddTagsToTheContent() {
        $writer = new Writer($this->keywords);
        $scenario = new ScenarioNode('my scenario');
        $scenario->addStep(new StepNode('Given', 'my example1'));
        $scenario->addTag('tag1');
        $scenario->addTag('tag2');

        $expected = '
  @tag1 @tag2
  Scenario: my scenario
    Given my example1';
        $this->assertEquals($expected, $writer->writeScenario($scenario));
    }

    public function testWriteOutlineScenarioReturnsContentAndTableNode() {
        $writer = new Writer($this->keywords);
        $scenario = new OutlineNode('my scenario');
        $scenario->addStep(new StepNode('Given', 'my example1'));

        // complete table
        $examples = new TableNode();
        $examples->addRow(array('lib1', 'lib2', 'lib3'));
        $examples->addRow(array(1, 2, 3));
        $examples->addRow(array(4, 5, 6));
        $scenario->setExamples($examples);

        $expected = '
  Scenario Outline: my scenario
    Given my example1

  Examples:
    | lib1 | lib2 | lib3 |
    | 1    | 2    | 3    |
    | 4    | 5    | 6    |';

        $this->assertEquals($expected, $writer->writeScenario($scenario));
    }

    /**
     * @dataProvider providerFeatureInText
     */
    public function testWriteFeature($initialContent) {
        $lexer = new \Behat\Gherkin\Lexer($this->keywords);
        $parser = new \Behat\Gherkin\Parser($lexer);
        $feature = $parser->parse($initialContent);

        $writer = new Writer($this->keywords);
        $this->assertEquals($initialContent, $writer->writeFeature($feature));
    }

    public function providerStep() {
        return array(
            array(new StepNode('Given', 'my example1'), 'Given my example1')
            , array(new StepNode('When', 'I do anything'), 'When I do anything')
            , array(new StepNode('And', 'I do anything yet'), 'And I do anything yet')
            , array(new StepNode('Then', 'The result is expected'), 'Then The result is expected')
        );
    }

    public function providerTableNode() {
        // complete table
        $node1 = new TableNode();
        $node1->addRow(array('lib1', 'lib2', 'lib3'));
        $node1->addRow(array(1, 2, 3));
        $node1->addRow(array(4, 5, 6));
        $expected1 = '
| lib1 | lib2 | lib3 |
| 1    | 2    | 3    |
| 4    | 5    | 6    |';

        // empty table
        $node2 = new TableNode();
        $expected2 = '';

        return array(
            array($node1, $expected1)
            , array($node2, $expected2)
        );
    }

    public function providerMultilinesText() {
        return array(
            array(
                "some text\nand the text on the new line with indent"
                , '  some text
  and the text on the new line with indent'
                , 1
            )
            , array(
                "test1\n  test2"
                , '                                test1
                                  test2'
                , 16
            )
        );
    }

    public function providerFeatureInText() {
        return array(
            array(
                '# language: en
Feature: Addition
  In order to avoid silly mistakes
  As a math idiot
  I want to be told the sum of two numbers

  Scenario: Add two numbers
    Given I have entered 11 into the calculator
    And I have entered 12 into the calculator
    When I press add
    Then the result should be 23 on the screen

  Scenario: Div two numbers
    Given I have entered 10 into the calculator
    And I have entered 2 into the calculator
    When I press div
    Then the result should be 5 on the screen'
            )
        );
    }

}
