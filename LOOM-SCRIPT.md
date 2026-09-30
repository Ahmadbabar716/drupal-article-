# Loom video script (3–5 minutes)

1. **Intro (20s)** – "Hi, I built a small demo of the first release from your article workflow brief: a clearer article form, an auto SEO description, and GraphQL output for the Astro site."

2. **Before (40s)** – Open /node/add/article after script 01. Point out: SEO field at the top, unclear labels like "Teaser Txt" and "meta_desc", an old subtitle field, teaser buried at the bottom.

3. **After (60s)** – Run script 02, reload. Show the three tabs (Write → Image → Topics & SEO), the new labels and help text, and the old subtitle hidden.

4. **Auto SEO description (60s)** – Type a teaser and show the live preview in the SEO field. Save and show it filled. Edit the SEO description manually, change the teaser, save again: the manual text is kept. Clear it, save: it auto-fills again.

5. **Existing content (20s)** – Open the sample article created before the change and save it. Everything still works; the old subtitle data is still in the database.

6. **GraphQL + Astro (40s)** – Run the query in the GraphQL explorer, then show the Astro page with the title, teaser and SEO description in the page head.

7. **Code & CI (30s)** – Quick look at MetaDescriptionGenerator.php, the GraphQL schema, and the GitHub Actions run (lint, PHPCS, unit tests passing).

8. **Close (15s)** – "Everything is config-first and exportable, with a simple rollback. Happy to apply the same approach to your platform."
