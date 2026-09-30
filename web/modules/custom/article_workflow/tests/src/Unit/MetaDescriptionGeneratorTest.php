<?php

declare(strict_types=1);

namespace Drupal\Tests\article_workflow\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\article_workflow\MetaDescriptionGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the meta description rule.
 */
#[CoversClass(MetaDescriptionGenerator::class)]
#[Group('article_workflow')]
class MetaDescriptionGeneratorTest extends UnitTestCase {

  /**
   * The generator under test.
   */
  protected MetaDescriptionGenerator $generator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->generator = new MetaDescriptionGenerator();
  }

  /**
   * Empty and whitespace-only teasers produce an empty description.
   */
  public function testEmptyTeaser(): void {
    $this->assertSame('', $this->generator->generate(''));
    $this->assertSame('', $this->generator->generate("   \n  "));
  }

  /**
   * Short teasers are kept as they are, minus markup and extra spaces.
   */
  public function testShortTeaserIsCleaned(): void {
    $result = $this->generator->generate("<p>Hello   <strong>editors</strong> &amp; readers</p>");
    $this->assertSame('Hello editors & readers', $result);
  }

  /**
   * Long teasers are cut on a word boundary and stay within the limit.
   */
  public function testLongTeaserIsTrimmed(): void {
    $teaser = str_repeat('Drupal editors publish faster with clearer forms. ', 10);
    $result = $this->generator->generate($teaser);

    $this->assertLessThanOrEqual(MetaDescriptionGenerator::MAX_LENGTH, mb_strlen($result));
    $this->assertStringEndsWith('…', $result);
    $this->assertStringNotContainsString('  ', $result);
  }

  /**
   * Multibyte text is never cut in the middle of a character.
   */
  public function testMultibyteTeaser(): void {
    $teaser = str_repeat('Café crème — ', 30);
    $result = $this->generator->generate($teaser, 50);
    $this->assertLessThanOrEqual(50, mb_strlen($result));
    $this->assertTrue(mb_check_encoding($result, 'UTF-8'));
  }

}
