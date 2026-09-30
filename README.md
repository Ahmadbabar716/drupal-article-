# Drupal 11 Article Publishing Workflow – Demo

![CI](../../actions/workflows/ci.yml/badge.svg)

A small working demo of the first release described in the "Article Publishing Workflow Improvement" brief: a faster, clearer article form for editors on a headless Drupal 11 platform, without breaking GraphQL output.

## What it shows

| Brief requirement | What this demo does |
|---|---|
| Group related fields in the order editors use them | Article form split into tabs: **1. Write → 2. Image → 3. Topics & SEO** (field_group) |
| Remove or hide unnecessary fields | Legacy "Subtitle (old)" field hidden from the form, **not deleted**, so existing data and integrations stay safe |
| Rewrite unclear labels and help text | "Teaser Txt" → "Teaser summary", "meta_desc" → "SEO description", etc., with plain-language help text |
| Auto meta description from teaser, editable | Custom module `article_workflow` fills the SEO description from the teaser on save, trimmed to 160 characters on a word boundary. Manual edits are never overwritten. Live preview while typing. |
| Existing articles stay editable | A sample article is created in the "before" state and still edits and saves after the change |
| GraphQL output stays valid | Custom GraphQL 4 schema + resolvers (`article_workflow` schema) serving the article to an Astro page |
| Config-first, small compatible changes | All form changes are configuration, exportable with `drush config:export` |
| CI quality checks | GitHub Actions: PHP 8.3 lint, Drupal coding standards (PHPCS), PHPUnit unit tests |

### Meta description rule

| Case | Result on save |
|---|---|
| SEO description empty | Filled from teaser summary |
| Teaser changes, SEO description still auto-generated | Updated to match the new teaser |
| Editor typed their own SEO description | Kept exactly as written |
| Editor clears the SEO description | Auto-filled again |
| Teaser empty and SEO description empty | Stays empty |
| Long teaser | Cut on a word boundary, max 160 characters, ends with "…" |

## Stack

Drupal 11 · PHP 8.3 · DDEV · Composer · Drush · Paragraphs · Field Group · CKEditor 5 · GraphQL 4 · Astro · GitHub Actions

## Setup (about 10 minutes)

Requires [DDEV](https://ddev.com) and Docker.

```bash
git clone <this-repo> drupal-article-demo && cd drupal-article-demo
ddev config --project-type=drupal11 --docroot=web --php-version=8.3
ddev start
ddev composer install

ddev drush site:install standard --account-name=admin --account-pass=admin -y
ddev drush en article_workflow -y

# "Before" state: unclear form + sample article
ddev drush php:script scripts/01-before-setup.php

# Apply the improvements
ddev drush php:script scripts/02-improve-article-form.php
ddev drush config:export -y
ddev launch /node/add/article
```

Log in with `admin` / `admin`.

### GraphQL

GraphQL explorer: `/admin/config/graphql` → *Article demo* → Explorer. Example query:

```graphql
query {
  article(id: 1) {
    title
    teaser
    metaDescription
    image { url alt }
    sections { type text }
  }
}
```

### Astro frontend

```bash
cd frontend
npm install
DRUPAL_GRAPHQL=http://drupal-article-demo.ddev.site/graphql ARTICLE_ID=1 npm run dev
```

### Tests

```bash
ddev exec vendor/bin/phpunit -c web/core web/modules/custom/article_workflow/tests/src/Unit
ddev exec vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom
```

## Rollback

All form changes are configuration. Rollback = revert the config commit and run `ddev drush config:import -y`. `scripts/rollback-article-form.php` also turns off the live preview for the local demo.

## Project structure

```
web/modules/custom/article_workflow/
  article_workflow.info.yml
  article_workflow.module              # presave hook + form alter
  article_workflow.services.yml
  article_workflow.libraries.yml
  js/meta-preview.js                   # live SEO description preview
  src/MetaDescriptionGenerator.php     # the meta description rule
  src/Plugin/GraphQL/Schema/ArticleWorkflowSchema.php
  graphql/article_workflow.graphqls    # GraphQL SDL
  tests/src/Unit/MetaDescriptionGeneratorTest.php
scripts/
  01-before-setup.php                  # original (unclear) form + sample content
  02-improve-article-form.php          # grouped, relabelled, reordered form
  rollback-article-form.php
frontend/src/pages/index.astro         # Astro page reading from GraphQL
.github/workflows/ci.yml               # lint, PHPCS, PHPUnit
```

## Next steps from the brief (not in this demo)

Paragraphs usability, reliable previews, Google Docs import, Gemini one-line summary (after feasibility and cost review), deployment smoke tests across Pantheon environments, Config Split cleanup and CKEditor image persistence to S3.
