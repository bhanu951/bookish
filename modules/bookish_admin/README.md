[![Netlify Status](https://api.netlify.com/api/v1/badges/c7036c2e-996a-4bac-9da4-c4bba607ab04/deploy-status)](https://app.netlify.com/sites/bookish-drupal/deploys)

# Bookish

[View demo site]

Bookish is an install profile for Drupal 9+ that tries to make the out of the
box experience for [Tome] users as nice as possible.

In terms of functionality, Bookish is similar to the Standard profile. Most of
the work in this profile has been to make the editing experience and frontend
as modern-feeling as possible.

Some feature highlights are:

* Ability to filter and crop images on upload, in CKEditor or a field
* Blur-up functionality for images, similar to GatsbyJS
* A theme with dark mode support, built using Single File Components
* Already configured Metatag, Pathauto, Lunr, and Simple XML sitemap integrations
* Ability to embed code snippets in CKEditor that are styled in the frontend
* A simplified toolbar that just lists the default shortcuts

# Install (with Tome)

The best way to use Bookish is with the [Tome Composer project].

The requirements for using Tome locally are:

* PHP 7+
* Composer
* Drush
* SQLite and the related PHP extensions

First, run these commands:

```
composer create-project drupal-tome/tome-project my_site --stability dev --no-interaction
cd my_site
composer require mortenson/bookish
drush tome:init # Select Bookish in the prompt
```

You can now commit your initial codebase, content, config, and files to Git.

To re-install your site, run:

```
drush tome:install
```

To start a local webserver, run:

```
drush runserver
```

then in another tab run:

```
drush uli -l 127.0.0.1:8888
```

and click the link to start editing.

To deploy your static site, refer to the hosting guides on https://tome.fyi, for
example [the one for Netlify].

# Install (without Tome)

If you don't want to use Tome, you can run this from any Drupal 9+ install:

```
composer require mortenson/bookish
drush si bookish -y
drush pmu tome -y
```

# Further help

After logging in, click "Help" in the toolbar. This module has extensive
documentation located inside Drupal using the Help Topics module.

[View demo site]: https://bookish-drupal.netlify.app/
[Tome]: https://drupal.org/project/tome
[Tome Composer project]: https://github.com/drupal-tome/tome-project
[the one for Netlify]: https://tome.fyi/docs/hosting/netlify/