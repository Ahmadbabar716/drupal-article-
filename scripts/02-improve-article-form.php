<?php

/**
 * @file
 * Applies the improved article form: grouping, order, labels and help text.
 *
 * Run with: ddev drush php:script scripts/02-improve-article-form.php
 * Then export with: ddev drush config:export -y
 */

declare(strict_types=1);

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;

// 1. Clear labels and help text written for editors, not developers.
$copy = [
  'field_teaser' => ['Teaser summary', 'One or two sentences shown on listing pages and social shares. Also used for the SEO description if you leave that empty.'],
  'body' => ['Article text', 'Paste from Google Docs here. Headings, bold text and links are kept.'],
  'field_sections' => ['Extra content blocks', 'Optional. Add extra text blocks below the main article text.'],
  'field_image' => ['Main image', 'Shown at the top of the article and when shared. Use a landscape image.'],
  'field_tags' => ['Topics', 'Start typing to pick existing topics. Separate multiple topics with commas.'],
  'field_meta_description' => ['SEO description', 'Shown in Google results. Filled automatically from the teaser summary; only type here to write your own.'],
];
foreach ($copy as $name => [$label, $help]) {
  if ($field = FieldConfig::loadByName('node', 'article', $name)) {
    $field->setLabel($label)->setDescription($help)->save();
  }
}

// Title is a base field, so it is relabelled through a base field override.
$definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article');
$definitions['title']->getConfig('article')
  ->setLabel('Headline')
  ->setDescription('The main title readers see. Keep it clear and under about 70 characters.')
  ->save();

// 2. Field order that follows how editors actually write an article.
$form = EntityFormDisplay::load('node.article.default');
$order = [
  'title' => 0,
  'field_teaser' => 1,
  'body' => 2,
  'field_sections' => 3,
  'field_image' => 10,
  'field_tags' => 20,
  'field_meta_description' => 21,
];
foreach ($order as $name => $weight) {
  if ($component = $form->getComponent($name)) {
    $component['weight'] = $weight;
    $form->setComponent($name, $component);
  }
}

// 3. Hide the unused legacy field. Hidden, not deleted, so existing data
// and any integration reading it stay intact until the dependency check
// is confirmed.
$form->removeComponent('field_legacy_subtitle');

// 4. Group fields into tabs with field_group.
$tab = fn (string $label, array $children, int $weight, string $description, bool $open = FALSE) => [
  'children' => $children,
  'parent_name' => 'group_tabs',
  'weight' => $weight,
  'label' => $label,
  'format_type' => 'tab',
  'format_settings' => [
    'classes' => '',
    'id' => '',
    'formatter' => $open ? 'open' : 'closed',
    'description' => $description,
    'required_fields' => TRUE,
  ],
  'region' => 'content',
];

$form->setThirdPartySetting('field_group', 'group_tabs', [
  'children' => ['group_content', 'group_media', 'group_seo'],
  'parent_name' => '',
  'weight' => 0,
  'label' => 'Article',
  'format_type' => 'tabs',
  'format_settings' => [
    'classes' => '',
    'id' => '',
    'direction' => 'horizontal',
    'width_breakpoint' => 640,
  ],
  'region' => 'content',
]);
$form->setThirdPartySetting('field_group', 'group_content',
  $tab('1. Write', ['title', 'field_teaser', 'body', 'field_sections'], 0, 'Headline, summary and the article itself.', TRUE));
$form->setThirdPartySetting('field_group', 'group_media',
  $tab('2. Image', ['field_image'], 1, 'The main image for the article.'));
$form->setThirdPartySetting('field_group', 'group_seo',
  $tab('3. Topics & SEO', ['field_tags', 'field_meta_description'], 2, 'Usually nothing to do here: the SEO description fills itself.'));

$form->save();

// Turn on the live SEO description preview in the form.
\Drupal::state()->set('article_workflow.form_improved', TRUE);
drupal_flush_all_caches();

echo "Improved article form applied. Reload /node/add/article.\n";
