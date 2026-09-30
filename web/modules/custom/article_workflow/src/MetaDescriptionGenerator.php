<?php

declare(strict_types=1);

namespace Drupal\article_workflow;

use Drupal\node\NodeInterface;

/**
 * Builds the meta description from the article teaser summary.
 *
 * Agreed rule for the demo:
 * - Empty meta description: filled from the teaser on save.
 * - Meta description still equal to the previous auto value: kept in sync
 *   when the teaser changes.
 * - Meta description edited by an editor: never overwritten.
 * - Editor clears the field: it is auto-filled again.
 */
final class MetaDescriptionGenerator {

  /**
   * Maximum length recommended for search result snippets.
   */
  public const MAX_LENGTH = 160;

  /**
   * Turns teaser text into a clean, length-limited meta description.
   */
  public function generate(string $text, int $max = self::MAX_LENGTH): string {
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim((string) preg_replace('/\s+/u', ' ', $text));

    if ($text === '' || mb_strlen($text) <= $max) {
      return $text;
    }

    // Leave room for the ellipsis and cut on a word boundary when possible.
    $cut = mb_substr($text, 0, $max - 1);
    $space = mb_strrpos($cut, ' ');
    if ($space !== FALSE && $space > (int) ($max * 0.6)) {
      $cut = mb_substr($cut, 0, $space);
    }

    return rtrim($cut, " ,;:.-") . '…';
  }

  /**
   * Applies the meta description rule to an article before it is saved.
   */
  public function apply(NodeInterface $node): void {
    if (!$node->hasField('field_teaser') || !$node->hasField('field_meta_description')) {
      return;
    }

    $teaser = (string) $node->get('field_teaser')->value;
    $current = trim((string) $node->get('field_meta_description')->value);

    $previous_auto = '';
    $original = $this->getOriginal($node);
    if ($original && $original->hasField('field_teaser')) {
      $previous_auto = $this->generate((string) $original->get('field_teaser')->value);
    }

    $is_auto = $current === '' || ($previous_auto !== '' && $current === $previous_auto);
    if ($is_auto) {
      $node->set('field_meta_description', $this->generate($teaser));
    }
  }

  /**
   * Returns the unchanged entity, supporting Drupal 10.3 and 11.x.
   */
  private function getOriginal(NodeInterface $node): ?NodeInterface {
    if ($node->isNew()) {
      return NULL;
    }
    if (method_exists($node, 'getOriginal')) {
      return $node->getOriginal();
    }
    return $node->original ?? NULL;
  }

}
