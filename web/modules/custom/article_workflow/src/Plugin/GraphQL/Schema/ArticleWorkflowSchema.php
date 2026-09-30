<?php

declare(strict_types=1);

namespace Drupal\article_workflow\Plugin\GraphQL\Schema;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;

/**
 * Custom GraphQL 4 schema that serves articles to the Astro frontend.
 *
 * @Schema(
 *   id = "article_workflow",
 *   name = "Article workflow schema"
 * )
 */
class ArticleWorkflowSchema extends SdlSchemaPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry() {
    $builder = new ResolverBuilder();
    $registry = new ResolverRegistry();

    $registry->addFieldResolver('Query', 'article',
      $builder->produce('entity_load')
        ->map('type', $builder->fromValue('node'))
        ->map('bundles', $builder->fromValue(['article']))
        ->map('id', $builder->fromArgument('id'))
    );

    $this->addArticleFields($registry, $builder);
    $this->addImageFields($registry, $builder);
    $this->addSectionFields($registry, $builder);

    return $registry;
  }

  /**
   * Resolvers for the Article type.
   */
  protected function addArticleFields(ResolverRegistry $registry, ResolverBuilder $builder): void {
    $registry->addFieldResolver('Article', 'id',
      $builder->produce('entity_id')->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Article', 'title',
      $builder->produce('entity_label')->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Article', 'published',
      $builder->produce('entity_published')->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Article', 'path',
      $builder->compose(
        $builder->produce('entity_url')->map('entity', $builder->fromParent()),
        $builder->produce('url_path')->map('url', $builder->fromParent())
      )
    );

    $properties = [
      'teaser' => 'field_teaser.value',
      'metaDescription' => 'field_meta_description.value',
      'body' => 'body.processed',
    ];
    foreach ($properties as $field => $path) {
      $registry->addFieldResolver('Article', $field,
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:node'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue($path))
      );
    }

    $registry->addFieldResolver('Article', 'image',
      $builder->callback(
        fn ($node) => $node->get('field_image')->isEmpty() ? NULL : $node->get('field_image')->first()
      )
    );

    $registry->addFieldResolver('Article', 'sections',
      $builder->produce('entity_reference')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_sections'))
    );
  }

  /**
   * Resolvers for the Image type (parent is the first image field item).
   */
  protected function addImageFields(ResolverRegistry $registry, ResolverBuilder $builder): void {
    $registry->addFieldResolver('Image', 'url',
      $builder->compose(
        $builder->callback(fn ($item) => $item?->entity),
        $builder->produce('image_url')->map('entity', $builder->fromParent())
      )
    );
    $registry->addFieldResolver('Image', 'alt',
      $builder->callback(fn ($item) => $item?->alt)
    );
  }

  /**
   * Resolvers for the Section (paragraph) type.
   */
  protected function addSectionFields(ResolverRegistry $registry, ResolverBuilder $builder): void {
    $registry->addFieldResolver('Section', 'id',
      $builder->produce('entity_id')->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Section', 'type',
      $builder->produce('entity_bundle')->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Section', 'text',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:paragraph'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_text.processed'))
    );
  }

}
