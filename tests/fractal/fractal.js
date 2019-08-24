'use strict';

/*
* Require the path module
*/
const path = require('path');

/*
 * Require the Fractal module
 */
const fractal = module.exports = require('@frctl/fractal').create();

/*
 * Give your project a title.
 */
fractal.set('project.title', 'Kirby 3 Handlebars Tests');

/*
 * Tell Fractal where to look for components.
 *
 *  folder => /tests/site/templates
 */
fractal.components.set('path', path.join(__dirname, '/../site/templates'));

/*
 * Tell Fractal where to look for documentation pages.
 */
fractal.docs.set('path', path.join(__dirname, 'docs'));

/*
 * Tell the Fractal web preview plugin where to look for static assets.
 */
fractal.web.set('static.path', path.join(__dirname, 'public'));

/*
 * https://fractal.build/guide/web/exporting-static-html.html
 *
 * folder => /tests/ui
 *
 */
fractal.web.set('builder.dest', __dirname + '/../ui');
