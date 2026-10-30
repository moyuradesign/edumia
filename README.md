# Edumia — Learning Management System

**Version:** v0.0.1  
**Author:** Ton Samith · https://tonsamith.com  
**Product website:** https://edumia.tonsamith.com  
**License:** Proprietary commercial source — © 2026 Ton Samith. All rights reserved. Third-party packages retain their separate licenses.

Edumia is a multilingual Laravel learning management system for institutions, instructors and students. Its roadmap includes courses, lessons, enrollments, quizzes, progress tracking, certificates and administration.

## Implemented v0.0.1 foundation
An original responsive homepage, preview course catalogue, searchable courses, detail pages, light/dark themes, and eight localized public URL structures. English is default at /; /en redirects to /.

| Language | URL |
|---|---|
| English | / |
| Chinese | /zh |
| French | /fr |
| German | /de |
| Japanese | /ja |
| Khmer | /km |
| Korean | /ko |
| Vietnamese | /vi |

Catalogue: /courses; localized: /{locale}/courses; course: /courses/{slug}. English is unprefixed.

## Framework

Target the supported Laravel 13 release. The first local functional checks used the Laravel 11 dependencies in the uploaded archive; run a fresh Composer install and acceptance tests with Laravel 13 before production deployment.

PHP 8.3+, Laravel 13, Blade, HTML5, one CSS file (resources/css/app.css), one JavaScript file (resources/js/app.js), Vite, Composer.

Install PHP dependencies with composer install, install Node dependencies with npm install, and compile assets with npm run build. Supply a secure APP_KEY and environment settings outside version control; set the site document root to public/. Create writable storage/framework/{cache,sessions,views} and storage/logs at deployment. Do not publish secrets, legacy templates, vendors, media uploads or macOS metadata.

**Scope limitation:** The course catalogue is sample preview data, not a persistent LMS database. Authentication, actual lessons, enrollments, instructors, payments, assessments, certificates, installer, and production deployment have not yet been implemented. A new set of database migrations, authorization policies and tests will be required before release. Do not market this milestone as a full, production-ready LMS.

No legacy branded source code or unverified media assets are included. All code in this repository is original Edumia foundation code using filenames already present in the uploaded directory structure.
