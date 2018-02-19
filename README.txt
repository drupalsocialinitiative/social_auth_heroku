CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * How it works
 * Support requests
 * Maintainers

INTRODUCTION
------------

Social Auth Heroku Module is a Heroku Authentication integration for Drupal.

REQUIREMENTS
------------

This module requires the following modules:

 * Social Auth (https://drupal.org/project/social_auth)
 * Social API (https://drupal.org/project/social_api)

INSTALLATION
------------

 * Run composer to install the dependencies.
   composer require "drupal/social_auth_heroku"

 * Install the dependencies: Social API and Social Auth.

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------

 * Add your Heroku project OAuth information in
   Configuration » User Authentication » Heroku.

 * Place a Social Auth Heroku block in Structure » Block Layout.

 * If you already have a Social Auth Login block in the site, rebuild the cache.


HOW IT WORKS
------------

The user can click on the Heroku logo in the Social Auth Login block.
You can also add a button or link anywhere on the site that points
to /user/login/heroku, so theming and customizing the button or link
is very flexible.

When the user opens the /user/login/heroku link, it automatically takes
user to Heroku for authentication. Heroku then returns the user to the
Drupal site. If we have an existing Drupal user with the same email address
provided by Heroku, that user is logged in. Otherwise, a new Drupal user is
created.

SUPPORT REQUESTS
----------------

Before posting a support request, carefully read the installation
instructions provided in module documentation page.

Before posting a support request, check Recent log entries at
admin/reports/dblog

Once you have done this, you can post a support request at module issue queue:
https://www.drupal.org/project/issues/social_auth_heroku

When posting a support request, please inform what does the status report say
at admin/reports/dblog.

MAINTAINERS
-----------

Current maintainers:
 * Getulio Sánchez (gvso) - https://www.drupal.org/u/gvso
 * Himanshu Dixit (himanshu-dixit) - https://www.drupal.org/u/himanshu-dixit
