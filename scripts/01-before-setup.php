<?php

/**
 * @file
 * Creates the "before" state: article fields with an unclear form.
 *
 * Run with: ddev drush php:script scripts/01-before-setup.php
 */

declare(strict_types=1);

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\graphql\Entity\Server;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * Creates a field storage and instance if they do not exist yet.
 */
function aw_create_field(string $entity_type, string $bundle, string $name, string $type, string $label, array $storage = [], array $settings = [], int $cardinality = 1): void {
  if (!FieldStorageConfig::loadByName($entity_type, $name)) {
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => $entity_type,
      'type' => $type,
      'cardinality' => $cardinality,
      'settings' => $storage,
    ])->save();
  }
  if (!FieldConfig::loadByName($entity_type, $bundle, $name)) {
    FieldConfig::create([
      'field_name' => $name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $label,
      'settings' => $settings,
    ])->save();
  }
}

// Paragraph type used for extra content blocks.
if (!ParagraphsType::load('text_block')) {
  ParagraphsType::create(['id' => 'text_block', 'label' => 'Text block'])->save();
}
aw_create_field('paragraph', 'text_block', 'field_text', 'text_long', 'Text');
$paragraph_form = EntityFormDisplay::load('paragraph.text_block.default') ?? EntityFormDisplay::create([
  'targetEntityType' => 'paragraph',
  'bundle' => 'text_block',
  'mode' => 'default',
  'status' => TRUE,
]);
$paragraph_form->setComponent('field_text', ['type' => 'text_textarea'])->save();

// Article fields with the kind of unclear labels editors complain about.
aw_create_field('node', 'article', 'field_legacy_subtitle', 'string', 'Subtitle (old)');
aw_create_field('node', 'article', 'field_teaser', 'string_long', 'Teaser Txt');
aw_create_field('node', 'article', 'field_meta_description', 'string', 'meta_desc', ['max_length' => 255]);
aw_create_field('node', 'article', 'field_sections', 'entity_reference_revisions', 'Paragraphs',
  ['target_type' => 'paragraph'],
  [
    'handler' => 'default:paragraph',
    'handler_settings' => ['target_bundles' => ['text_block' => 'text_block']],
  ],
  -1
);

// Messy field order: SEO field first, teaser at the very bottom.
$form = EntityFormDisplay::load('node.article.default');
$form
  ->setComponent('field_meta_description', ['type' => 'string_textfield', 'weight' => -10])
  ->setComponent('field_legacy_subtitle', ['type' => 'string_textfield', 'weight' => -9])
  ->setComponent('field_sections', ['type' => 'paragraphs', 'weight' => 15])
  ->setComponent('field_teaser', ['type' => 'string_textarea', 'weight' => 30])
  ->save();

// GraphQL server for the headless frontend.
if (!Server::load('article_demo')) {
  Server::create([
    'name' => 'article_demo',
    'label' => 'Article demo',
    'schema' => 'article_workflow',
    'endpoint' => '/graphql',
    'schema_configuration' => ['article_workflow' => ['extensions' => []]],
  ])->save();
}
// Demo only: let the local Astro build query without logging in.
user_role_grant_permissions('anonymous', ['execute article_demo arbitrary graphql requests']);

// One existing article, so we can prove old content stays editable.
$existing = \Drupal::entityQuery('node')
  ->accessCheck(FALSE)
  ->condition('type', 'article')
  ->condition('title', 'Five ways editors can publish faster')
  ->execute();
if (!$existing) {
  $section = Paragraph::create([
    'type' => 'text_block',
    'field_text' => [
      'value' => '<p>Extra block: a short checklist editors can follow before publishing.</p>',
      'format' => 'basic_html',
    ],
  ]);
  $section->save();

  Node::create([
    'type' => 'article',
    'title' => 'Five ways editors can publish faster',
    'field_legacy_subtitle' => 'Old subtitle kept for existing content',
    'field_teaser' => 'A clearer article form, grouped fields and automatic SEO descriptions help editors move a story from Google Docs to published in minutes instead of an hour.',
    'body' => [
      'value' => '<h2>Why it matters</h2><p>Editors spend too long on repetitive steps. Small form improvements remove that friction.</p>',
      'format' => 'basic_html',
    ],
    'field_sections' => [$section],
    'status' => 1,
  ])->save();
}

echo "Before state ready. Open /node/add/article to see the original form.\n";
